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

		// The widths the preview can be looked at in. A header set is mostly a
		// decision about where the menu goes when there is no room for it, and
		// that decision is invisible at one width
		WIDTHS	: [
			{ key : 'phone', 	 px :  390 },
			{ key : 'tablet',  px :  820 },
			{ key : 'desktop', px : 1280 },
		],

		// Which of them is on screen, and the debounce behind every select
		_width	: 1280,
		_timer	: null,
		// The header and footer the frame was last built with. Everything else
		// is a stylesheet, and a stylesheet can be swapped inside it
		_frames	: null,

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

			// Controls on one side, what they mean on the other. One column
			// below the breakpoint, where a preview beside a select would be
			// too narrow to be a preview
			const layout = dc.createElement('div');
			layout.id = 'design-layout';

			const controls = dc.createElement('div');
			controls.id = 'design-controls';

			controls.appendChild( Nino.admin.design._renderState() );
			controls.appendChild( Nino.admin.design._renderGlobals() );
			controls.appendChild( Nino.admin.design._renderParts() );

			( data.notes || [] ).forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = note;
				controls.appendChild( p );
			} );

			layout.appendChild( controls );
			layout.appendChild( Nino.admin.design._renderPreview() );
			wrap.appendChild( layout );

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

			/*	A rendered screen has an empty frame in it, and the frame is the
				answer to what every select above it means - so it is filled
				straight away rather than on a button somebody has to find */
			Nino.admin.design._frames = null;
			Nino.admin.design._preview( 0 );
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
				} ), edit.step, function( value ) { edit.step = value; Nino.admin.design._preview() }
			) );

			box.appendChild( Nino.admin.design._select(
				'design-size', Nino.content.getText('/_admin/design/label/size'),
				( data.sizes || [] ).map( function( size ) {
					return { value : size, label : Nino.content.getText('/_admin/design/size/'+ size ) };
				} ), edit.size, function( value ) { edit.size = value; Nino.admin.design._preview() }
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
						Nino.admin.design._preview();
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
						function( value ) { chosen.step = value === '' ? null : value; Nino.admin.design._preview() }
					) );

				part.appendChild( desc );
				box.appendChild( part );
				Nino.admin.design._describe( desc, row, chosen.set );
			} );

			return box;
		},

		/**
		 *	The other half of the screen: the selection, as a page.
		 *
		 *	An iframe rather than a corner of this document, because a design
		 *	brings its own :root, its own body rules and its own reset, and the
		 *	workbench is a page too - the two would style each other. What the
		 *	frame shows is built by design/preview and written nowhere
		 *
		 *	@return		{Element}
		 */
		_renderPreview : function() {

			const box = dc.createElement('div');
			box.id = 'design-preview';

			const heading = dc.createElement('h3');
			heading.textContent = Nino.content.getText('/_admin/design/label/preview');
			box.appendChild( heading );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/design/hint/preview');
			box.appendChild( hint );

			const bar = dc.createElement('div');
			bar.className = 'design-preview-bar';

			bar.appendChild( Nino.admin.design._select(
				'design-width', Nino.content.getText('/_admin/design/label/width'),
				Nino.admin.design.WIDTHS.map( function( width ) {
					return { value : String( width.px ), label : Nino.content.getText('/_admin/design/width/'+ width.key ) };
				} ), String( Nino.admin.design._width ), function( value ) {
					Nino.admin.design._width = parseInt( value, 10 );
					Nino.admin.design._fit();
				}
			) );

			const reload = dc.createElement('button');
			reload.type = 'button';
			reload.id = 'design-reload';
			reload.textContent = Nino.content.getText('/_admin/design/label/reload');
			reload.addEventListener( 'click', function() {
				// From scratch: the one control for "the library changed under
				// me", which is what writing a set looks like from here
				Nino.admin.design._frames = null;
				Nino.admin.design._preview( 0 );
			} );
			bar.appendChild( reload );
			box.appendChild( bar );

			const stage = dc.createElement('div');
			stage.className = 'design-stage';

			const frame = dc.createElement('iframe');
			frame.id = 'design-frame';
			frame.title = Nino.content.getText('/_admin/design/label/preview');
			/*	allow-scripts, because half of what a header set is only happens
				when it scrolls, and the cover heights are set in js. Plus
				allow-same-origin, or the frame gets an opaque origin and the
				webfaces stop loading with it - a design shown in the wrong
				typeface is worse than no preview. What stays denied is what a
				preview has no business doing: submitting the specimen's form,
				navigating the workbench away under itself, opening windows,
				starting downloads */
			frame.setAttribute( 'sandbox', 'allow-scripts allow-same-origin' );
			stage.appendChild( frame );
			box.appendChild( stage );

			const msg = dc.createElement('p');
			msg.id = 'design-preview-msg';
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'aria-live', 'polite' );
			box.appendChild( msg );

			return box;
		},

		/**
		 *	Ask for a preview, once the clicking has stopped. Every select on
		 *	the screen calls this, and a person trying three sets in a row wants
		 *	the third one rendered, not all three
		 *
		 *	@param		{number}	[delay]		Milliseconds, 0 for now
		 *
		 *	@return		void
		 */
		_preview : function( delay ) {
			wn.clearTimeout( Nino.admin.design._timer );
			Nino.admin.design._timer = wn.setTimeout( Nino.admin.design._previewNow, delay === undefined ? 350 : delay );
		},

		/**
		 *	The request behind it.
		 *
		 *	A frame - header or footer - brings markup, so changing one rebuilds
		 *	the document. Everything else is a stylesheet, and a stylesheet is
		 *	swapped inside the frame that is already standing: no reload, no
		 *	jump back to the top, and the page does not have to travel again
		 *
		 *	@return		void
		 */
		_previewNow : function() {

			const frame = dc.getElementById('design-frame');
			const msg 	= dc.getElementById('design-preview-msg');
			const edit 	= Nino.admin.design._edit;

			if( frame === null || edit === null )
				return;

			const frames = ( edit.parts.header || {} ).set + '|' + ( edit.parts.footer || {} ).set;
			const full 	 = Nino.admin.design._frames !== frames;

			if( msg !== null ) {
				msg.className = 'nino-admin-hint';
				msg.textContent = Nino.content.getText('/_admin/design/msg/previewing');
			}

			Nino.admin.design._apiCall( 'preview', {
				parts : edit.parts, step : edit.step, size : edit.size, full : full
			}, function( status, response ) {

				if( status !== 200 || response === null ) {
					if( msg !== null ) {
						msg.className = 'nino-admin-error';
						msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/design/error/preview') );
					}
					return;
				}

				if( typeof response.document === 'string' && response.document !== '' ) {
					Nino.admin.design._frames = frames;
					frame.srcdoc = response.document;
				}
				else if( Nino.admin.design._restyle( frame, response ) === false ) {
					// The frame is not reachable after all - ask again, for the
					// whole document this time, rather than showing a stale one
					Nino.admin.design._frames = null;
					return Nino.admin.design._preview( 0 );
				}

				Nino.admin.design._fit();

				if( msg === null )
					return;

				const notes = response.notes || [];
				msg.className = notes.length === 0 ? 'nino-admin-hint' : 'nino-admin-error';
				msg.textContent = notes.join(' ');
			} );
		},

		/**
		 *	Swap the compiled sheet inside a frame that is already standing
		 *
		 *	@param		{Element}	frame
		 *	@param		{Object}	response	The answer of design/preview
		 *
		 *	@return		{boolean}					false when the frame could not be reached
		 */
		_restyle : function( frame, response ) {
			try {
				const style = frame.contentDocument.getElementById( response.style );
				if( style === null )
					return false;
				style.textContent = response.css;
				return true;
			}
			catch( e ) {
				return false;
			}
		},

		/**
		 *	The frame renders at the width that is chosen and is then scaled to
		 *	whatever the column has room for - a desktop layout in half a pane
		 *	is still a desktop layout, and a frame simply made narrow would be
		 *	the phone view with a lie on the label
		 *
		 *	@return		void
		 */
		_fit : function() {

			const frame = dc.getElementById('design-frame');

			if( frame === null || frame.parentNode === null )
				return;

			const stage = frame.parentNode;
			const width = Nino.admin.design._width;
			const scale = Math.min( 1, ( stage.clientWidth || width ) / width );

			/*	The box keeps its height and the frame is given whatever renders
				into it at this scale - so switching from desktop to phone
				changes what is on screen and not how far the page below it
				moves. The frame scrolls the rest itself */
			const visible = Math.max( 420, Math.min( 900, wn.innerHeight - 300 ) );
			const height = Math.round( visible / scale );

			frame.style.width 	= width+ 'px';
			frame.style.height 	= height+ 'px';
			frame.style.transform = 'scale('+ scale+ ')';
			stage.style.height 	= visible+ 'px';
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
	// The column the frame is scaled into changes width when the window does,
	// and when the rail is folded away
	Nino.events.bindCallback( 'resize', Nino.admin.design._fit );

})(window, document, document.documentElement, document.body);
