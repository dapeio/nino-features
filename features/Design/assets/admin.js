/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Design					The feature's /_admin panel, "Design": one variant per
 *													part of a page, the finetune knob, the root size, and
 *													the compile that turns all of it into
 *													assets/theme.css (see Modules\Design\Admin beside
 *													this file). Ships with the feature and is loaded
 *													exactly while it is active.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.design = {

		_ready	: false,
		// What the library offers and what is chosen - the answer of design/list
		_data		: null,
		// The working copy the screen edits. Leaving without saving changes
		// nothing on disk
		_edit		: null,

		/**
		 *	Load the library and the stored setup, then draw
		 *
		 *	@param		{Function}	[then]			Run once the screen is back
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('design-form');
			if( wrap === null )
				return;

			Nino.admin.design._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.design._showError( wrap, status, response );

				Nino.admin.design._data		= response;
				Nino.admin.design._edit		= Nino.admin.design._selection( response );
				Nino.admin.design._ready	= true;
				Nino.admin.design._render();

				if( typeof then === 'function' )
					then();
			} );
		},

		showCurrent : function() {
			if( Nino.admin.design._ready === false )
				return Nino.admin.design.init();
			Nino.admin.design._render();
		},

		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'design/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/load') );
			container.appendChild( p );
		},

		/**
		 *	The posted shape, out of what design/list answered
		 *
		 *	@param		{Object}	data
		 *
		 *	@return		{Object}					{ parts, step, size }
		 */
		_selection : function( data ) {
			const parts = {};
			( data.parts || [] ).forEach( function( row ) {
				parts[row.part] = { set : row.set, step : row.step };
			} );
			return { parts : parts, step : data.step, size : data.size };
		},

		/**
		 *	A text fill with its %s filled in
		 *
		 *	@param		{string}	key
		 *	@param		{...*}		values
		 *
		 *	@return		{string}
		 */
		_text : function( key ) {
			const values = Array.prototype.slice.call( arguments, 1 );
			let out = Nino.content.getText( key );
			values.forEach( function( value ) { out = out.replace( /%[sdn]/, String( value ) ) } );
			return out;
		},

		/**
		 *	The whole screen: the nine parts, the two global controls, what the
		 *	stylesheet on disk currently is, and the two actions
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = dc.getElementById('design-form');
			const data = Nino.admin.design._data;
			if( wrap === null || data === null )
				return;

			wrap.innerHTML = '';

			const heading = dc.createElement('h2');
			heading.textContent = Nino.content.getText('/_admin/design/label/title');
			wrap.appendChild( heading );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/design/hint/intro');
			wrap.appendChild( hint );

			wrap.appendChild( Nino.admin.design._renderState() );
			wrap.appendChild( Nino.admin.design._renderGlobals() );
			wrap.appendChild( Nino.admin.design._renderParts() );

			( data.notes || [] ).forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = note;
				wrap.appendChild( p );
			} );

			const msg = dc.createElement('p');
			msg.id = 'design-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const save = dc.createElement('button');
			save.type = 'button';
			save.id = 'design-save';
			save.textContent = Nino.content.getText('/_admin/design/label/save');
			save.addEventListener( 'click', function() { Nino.admin.design._save( false, save, msg ) } );

			const apply = dc.createElement('button');
			apply.type = 'button';
			apply.id = 'design-apply';
			apply.className = 'nino-admin-btn-primary';
			// The first compile in a project meets the wizard's own theme.css.
			// The button says which of the two it is about to do
			apply.textContent = Nino.content.getText( data.exists === true && data.ours === false
				? '/_admin/design/label/takeover'
				: '/_admin/design/label/apply' );
			apply.addEventListener( 'click', function() { Nino.admin.design._save( true, apply, msg ) } );

			const actions = dc.createElement('div');
			actions.appendChild( save );
			actions.appendChild( apply );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );
			wrap.appendChild( msg );
		},

		/**
		 *	What assets/theme.css is right now - the one thing a screen about
		 *	compiling must not be vague about
		 *
		 *	@return		{Element}
		 */
		_renderState : function() {

			const data = Nino.admin.design._data;
			const box = dc.createElement('div');
			box.id = 'design-state';

			let key = 'current', level = 'ok';

			if( data.exists === true && data.ours === false ) { key = 'foreign'; level = 'warn'; }
			else if( data.exists === false || data.compiled === '' ) { key = 'missing'; level = 'warn'; }
			else if( data.current === false ) { key = 'drifted'; level = 'warn'; }

			const line = dc.createElement('p');
			line.className = 'design-state design-state--'+ level;
			line.textContent = Nino.admin.design._text( '/_admin/design/state/'+ key, data.target );
			box.appendChild( line );

			if( data.compiled !== '' ) {
				const when = dc.createElement('p');
				when.className = 'nino-admin-hint';
				when.textContent = Nino.admin.design._text( '/_admin/design/state/compiled', data.compiled.substring( 0, 16 ).replace( 'T', ' ' ) );
				box.appendChild( when );
			}

			return box;
		},

		/**
		 *	The two decisions that are about the whole page
		 *
		 *	@return		{Element}
		 */
		_renderGlobals : function() {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;

			const box = dc.createElement('div');
			box.id = 'design-globals';

			const heading = dc.createElement('h3');
			heading.textContent = Nino.content.getText('/_admin/design/label/global');
			box.appendChild( heading );

			box.appendChild( Nino.admin.design._select(
				'design-step', Nino.content.getText('/_admin/design/label/knob'),
				( data.steps || [] ).map( function( step ) {
					return { value : step, label : Nino.content.getText('/_admin/design/step/'+ step ) };
				} ), edit.step, function( value ) { edit.step = value }
			) );

			box.appendChild( Nino.admin.design._select(
				'design-size', Nino.content.getText('/_admin/design/label/size'),
				( data.sizes || [] ).map( function( size ) {
					return { value : size, label : Nino.content.getText('/_admin/design/size/'+ size ) };
				} ), edit.size, function( value ) { edit.size = value }
			) );

			[ '/_admin/design/hint/knob', '/_admin/design/hint/size' ].forEach( function( key ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-hint';
				p.textContent = Nino.content.getText( key );
				box.appendChild( p );
			} );

			return box;
		},

		/**
		 *	One row per part: the variant, its description, and - for a set - the
		 *	step it may deviate at
		 *
		 *	@return		{Element}
		 */
		_renderParts : function() {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;

			const box = dc.createElement('div');
			box.id = 'design-parts';

			const heading = dc.createElement('h3');
			heading.textContent = Nino.content.getText('/_admin/design/label/parts');
			box.appendChild( heading );

			const frames = dc.createElement('p');
			frames.className = 'nino-admin-hint';
			frames.textContent = Nino.content.getText('/_admin/design/hint/frames');
			box.appendChild( frames );

			( data.parts || [] ).forEach( function( row ) {

				const part = dc.createElement('div');
				part.className = 'design-part';
				part.setAttribute( 'data-part', row.part );

				/*	Built here and handed to _describe() as a node rather than
					looked up by id: nothing in this loop is in the document yet,
					so getElementById() finds nothing and every description would
					silently stay empty */
				const desc = dc.createElement('p');
				desc.className = 'nino-admin-hint design-description';
				desc.id = 'design-description-'+ row.part;

				const keys = Object.keys( row.catalogue || {} );
				const chosen = edit.parts[row.part];

				part.appendChild( Nino.admin.design._select(
					'design-set-'+ row.part, Nino.content.getText('/_admin/design/part/'+ row.part ),
					keys.map( function( set ) {
						return { value : set, label : row.catalogue[set].name || set };
					} ), chosen.set, function( value ) {
						chosen.set = value;
						Nino.admin.design._describe( desc, row, value );
					}
				) );

				// A frame brings markup rather than only a look, so it has no
				// step to deviate at - the knob is about a set's own triples
				if( row.kind === 'set' )
					part.appendChild( Nino.admin.design._select(
						'design-step-'+ row.part, '',
						[ { value : '', label : Nino.content.getText('/_admin/design/label/follow') } ].concat(
							( data.steps || [] ).map( function( step ) {
								return { value : step, label : Nino.content.getText('/_admin/design/step/'+ step ) };
							} ) ),
						chosen.step === null ? '' : chosen.step,
						function( value ) { chosen.step = value === '' ? null : value }
					) );

				part.appendChild( desc );
				box.appendChild( part );
				Nino.admin.design._describe( desc, row, chosen.set );
			} );

			return box;
		},

		/**
		 *	Show what the chosen variant is, under its row
		 *
		 *	@param		{Element}	at				The row's own description node
		 *	@param		{Object}	row				One part, as design/list answered it
		 *	@param		{string}	set
		 *
		 *	@return		void
		 */
		_describe : function( at, row, set ) {
			at.textContent = ( ( row.catalogue || {} )[set] || {} ).description || '';
		},

		/**
		 *	A labelled select that reports its own changes
		 *
		 *	@param		{string}		id
		 *	@param		{string}		label			'' draws none
		 *	@param		{Array}			options		{ value, label }
		 *	@param		{string}		current
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_select : function( id, label, options, current, onChange ) {

			const field = dc.createElement('div');
			field.className = 'nino-admin-field';

			if( label !== '' ) {
				const tag = dc.createElement('label');
				tag.setAttribute( 'for', id );
				tag.textContent = label;
				field.appendChild( tag );
			}

			const select = dc.createElement('select');
			select.id = id;

			if( options.length === 0 ) {
				const empty = dc.createElement('option');
				empty.value = '';
				empty.textContent = '—';
				select.appendChild( empty );
				select.disabled = true;
			}

			options.forEach( function( option ) {
				const node = dc.createElement('option');
				node.value = option.value;
				node.textContent = option.label;
				if( option.value === current )
					node.selected = true;
				select.appendChild( node );
			} );

			select.addEventListener( 'change', function() { onChange( select.value ) } );
			field.appendChild( select );

			return field;
		},

		/**
		 *	Store the selection, and - when asked - compile it afterwards.
		 *	Two steps rather than one: a decision is not a stylesheet, and the
		 *	screen stays honest about which of the two just happened
		 *
		 *	@param		{boolean}		compile
		 *	@param		{Element}		btn
		 *	@param		{Element}		msg
		 *
		 *	@return		void
		 */
		_save : function( compile, btn, msg ) {

			const edit = Nino.admin.design._edit;
			const foreign = Nino.admin.design._data.exists === true && Nino.admin.design._data.ours === false;

			btn.disabled = true;
			msg.className = '';
			msg.textContent = '';

			Nino.admin.design._apiCall( 'save', edit, function( status, response ) {

				if( status !== 200 || response === null ) {
					btn.disabled = false;
					msg.className = 'nino-admin-error';
					msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/design/error/save') );
					return;
				}

				if( compile === false ) {
					btn.disabled = false;
					return Nino.admin.design.init( function() {
						const back = dc.getElementById('design-msg');
						if( back !== null )
							back.textContent = Nino.content.getText('/_admin/design/msg/saved');
					} );
				}

				// `force` only where the screen already said the file is not
				// ours, and the button already said it is about to take it over
				Nino.admin.design._apiCall( 'apply', { force : foreign }, function( applyStatus, applied ) {

					btn.disabled = false;

					if( applyStatus !== 200 ) {
						msg.className = 'nino-admin-error';
						msg.textContent = '('+ applyStatus+ ') '+ ( ( applied && applied.error ) ? applied.error : Nino.content.getText('/_admin/design/error/apply') );
						return;
					}

					Nino.admin.design.init( function() {
						const back = dc.getElementById('design-msg');
						if( back !== null )
							back.textContent = Nino.content.getText( foreign === true ? '/_admin/design/msg/takenover' : '/_admin/design/msg/applied' );
					} );
				} );
			} );
		},
	};

	Nino.events.bindCallback( 'ready', Nino.admin.design.init );

})(window, document, document.documentElement, document.body);
