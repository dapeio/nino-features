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
		// The part being edited. 'global' is the tenth entry of the picker: the
		// root size, and the knob position every part follows until it does not
		_part		: 'global',
		// Whether the frame follows the picker to the part that was opened
		_follow	: true,
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
				// knobs is only what this part was moved at on its own, so one
				// still following the global position keeps following it
				parts[row.part] = { set : row.set, knobs : Object.assign( {}, row.moved || {} ) };
			} );
			return { parts : parts, knobs : Object.assign( {}, data.knobs || {} ), size : data.size };
		},

		/**
		 *	One part, as design/list answered it
		 *
		 *	@param		{string}	[part]		Defaults to the one on screen
		 *
		 *	@return		{Object}					{} where there is none
		 */
		_row : function( part ) {
			const want = part || Nino.admin.design._part;
			return ( ( Nino.admin.design._data || {} ).parts || [] ).filter( function( row ) {
				return row.part === want;
			} )[0] || {};
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
		 *	The whole screen: one part at a time, what it can be given, and the
		 *	knob under it - beside a preview of the lot.
		 *
		 *	A part at a time rather than nine rows at once, because nine rows is
		 *	a list to read and one part is a decision to make. The picker says
		 *	which one is open; everything below it belongs to that one, and the
		 *	preview beside it is the whole page either way
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

			controls.appendChild( Nino.admin.design._renderPicker() );

			const part = dc.createElement('div');
			part.id = 'design-part';
			controls.appendChild( part );

			( data.notes || [] ).forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = note;
				controls.appendChild( p );
			} );

			// What the file on disk is, at the bottom: true and worth saying,
			// and not what somebody opening this screen came to find out
			controls.appendChild( Nino.admin.design._renderState() );

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

			Nino.admin.design._renderCurrentPart();

			/*	A rendered screen has an empty frame in it, and the frame is the
				answer to what every select above it means - so it is filled
				straight away rather than on a button somebody has to find */
			Nino.admin.design._frames = null;
			Nino.admin.design._preview( 0 );
		},

		/**
		 *	Which part is open. Global is one of them rather than a section of
		 *	its own: the root size and the knob everything follows are a part of
		 *	the page like the others, and having them in the same picker is what
		 *	keeps the screen one screen
		 *
		 *	@return		{Element}
		 */
		_renderPicker : function() {

			const data = Nino.admin.design._data;

			const options = ( data.parts || [] ).map( function( row ) {
				return { value : row.part, label : Nino.content.getText('/_admin/design/part/'+ row.part ) };
			} );

			options.push( { value : 'global', label : Nino.content.getText('/_admin/design/label/global') } );

			return Nino.admin.design._select(
				'design-picker', Nino.content.getText('/_admin/design/label/picker'), options,
				Nino.admin.design._part, function( value ) {
					Nino.admin.design._part = value;
					Nino.admin.design._renderCurrentPart();
					// Only here, not in _renderCurrentPart(): that one runs on
					// every knob move too, and a frame that jumps every time
					// somebody presses +1 is a frame nobody can work in
					Nino.admin.design._jump();
				}
			);
		},

		/**
		 *	Everything below the picker, redrawn for whichever part it names -
		 *	the variant, what that variant is, and the knob
		 *
		 *	@return		void
		 */
		_renderCurrentPart : function() {

			const box = dc.getElementById('design-part');
			if( box === null )
				return;

			box.innerHTML = '';

			if( Nino.admin.design._part === 'global' )
				box.appendChild( Nino.admin.design._renderGlobal() );
			else
				box.appendChild( Nino.admin.design._renderVariant() );

			box.appendChild( Nino.admin.design._renderKnob() );
		},

		/**
		 *	The global part: the one size the whole page is measured in
		 *
		 *	@return		{Element}
		 */
		_renderGlobal : function() {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;
			const box = dc.createElement('div');

			box.appendChild( Nino.admin.design._select(
				'design-size', Nino.content.getText('/_admin/design/label/size'),
				( data.sizes || [] ).map( function( size ) {
					return { value : size, label : Nino.content.getText('/_admin/design/size/'+ size ) };
				} ), edit.size, function( value ) { edit.size = value; Nino.admin.design._preview() }
			) );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint design-description';
			hint.textContent = Nino.content.getText('/_admin/design/hint/size');
			box.appendChild( hint );

			return box;
		},

		/**
		 *	A part: which variant it is given, and what that variant is
		 *
		 *	@return		{Element}
		 */
		_renderVariant : function() {

			const row = Nino.admin.design._row();
			const edit = Nino.admin.design._edit;
			const chosen = edit.parts[row.part] || {};
			const box = dc.createElement('div');

			const desc = dc.createElement('p');
			desc.className = 'nino-admin-hint design-description';

			box.appendChild( Nino.admin.design._select(
				'design-set', Nino.content.getText('/_admin/design/label/variant'),
				Object.keys( row.catalogue || {} ).map( function( set ) {
					return { value : set, label : ( row.catalogue[set] || {} ).name || set };
				} ), chosen.set, function( value ) {
					chosen.set = value;
					Nino.admin.design._describe( desc, row, value );
					/*	A variant brings its own knob rows, and the ones the last
						one had are not this one's - so the whole part is drawn
						again rather than only the select that changed */
					Nino.admin.design._renderCurrentPart();
					Nino.admin.design._preview();
				}
			) );

			box.appendChild( desc );
			Nino.admin.design._describe( desc, row, chosen.set );

			// A frame brings markup rather than only a look, and the knob is
			// about a set's own triples - so there is nothing under this one
			if( row.kind === 'frame' ) {
				const note = dc.createElement('p');
				note.className = 'nino-admin-hint';
				note.textContent = Nino.content.getText('/_admin/design/hint/frames');
				box.appendChild( note );
			}

			return box;
		},

		/**
		 *	The knob: one row per value the chosen variant declares three steps
		 *	for, each at less / as it is / more.
		 *
		 *	A row follows the level above it until somebody moves it, and says
		 *	so by being drawn quietly: what is on screen is the value that will
		 *	compile either way, so a row nobody has touched is not a row with no
		 *	answer - it is one whose answer is still somebody else's. Moving it
		 *	makes it its own and puts the way back beside it
		 *
		 *	@return		{Element}
		 */
		_renderKnob : function() {

			const box = dc.createElement('div');
			box.id = 'design-knob';

			const heading = dc.createElement('h3');
			heading.textContent = Nino.content.getText('/_admin/design/label/knob');
			box.appendChild( heading );

			const rows = Nino.admin.design._knobRows();

			if( rows.length === 0 ) {
				const empty = dc.createElement('p');
				empty.className = 'nino-admin-hint';
				empty.textContent = Nino.content.getText( Nino.admin.design._part === 'global'
					? '/_admin/design/hint/knob'
					: '/_admin/design/knob/empty' );
				box.appendChild( empty );
				return box;
			}

			rows.forEach( function( row ) { box.appendChild( Nino.admin.design._knobRow( row ) ) } );

			if( Nino.admin.design._part !== 'global' ) {
				const hint = dc.createElement('p');
				hint.className = 'nino-admin-hint';
				hint.textContent = Nino.content.getText('/_admin/design/hint/knob');
				box.appendChild( hint );
			}

			return box;
		},

		/**
		 *	What the knob has rows for. Global has exactly one - the position
		 *	every part follows - and a part has whatever its variant declares
		 *
		 *	@return		{Array}					{ key, label, value, inherited }
		 */
		_knobRows : function() {

			const edit = Nino.admin.design._edit;

			/*	Global lists the knobs any chosen set answers to at all - a
				position nothing follows is a position worth not offering - and
				a part lists the ones its own set answers to */
			if( Nino.admin.design._part === 'global' )
				return ( ( Nino.admin.design._data || {} ).global || [] ).map( function( knob ) {
					return {
						key 			: knob,
						value 		: edit.knobs[knob] || 'default',
						inherited	: false,
					};
				} );

			const row = Nino.admin.design._row();
			const chosen = edit.parts[row.part] || {};

			return ( row.knobs || [] ).map( function( knob ) {
				const own = ( chosen.knobs || {} )[knob];
				return {
					key 			: knob,
					// What will compile: this part's own where it was moved
					// here, else the global position. The screen shows the
					// answer, not the gap
					value 		: own || edit.knobs[knob] || 'default',
					inherited	: ( own === undefined || own === null ),
				};
			} );
		},

		/**
		 *	One of them: three steps and, once it is its own, the way back
		 *
		 *	@param		{Object}	row			{ key, label, value, inherited }
		 *
		 *	@return		{Element}
		 */
		_knobRow : function( row ) {

			const field = dc.createElement('div');
			field.className = 'design-knob-row'+ ( row.inherited === true ? ' design-knob-row--inherited' : '' );

			/*	The knob's own name and the terse note beside it, both out of
				the panel's text files: one knob is one key in every locale, so
				"Abstände" reads the same on a section as on a form */
			const label = dc.createElement('span');
			label.className = 'design-knob-label';
			label.textContent = Nino.content.getText('/_admin/design/knob/'+ row.key+ '/label');

			const note = dc.createElement('small');
			note.textContent = Nino.content.getText('/_admin/design/knob/'+ row.key+ '/note');
			label.appendChild( note );

			field.appendChild( label );

			/*	The workbench's own segmented control, for its look: filled
				where it is, flat where it is not. role=group and aria-pressed
				rather than a tablist, because three steps of one value are a
				choice and not two panels - buttonRow() defaults to exactly that
				flag for exactly this case */
			const group = dc.createElement('div');
			group.className = 'nino-admin-tabs design-knob-steps';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/knob/'+ row.key+ '/label') );

			const buttons = {};

			( Nino.admin.design._data.steps || [] ).forEach( function( step ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.dataset.step = step;
				// The short label on the button, the word behind it: three
				// buttons reading weniger/normal/mehr is a sentence per row
				button.textContent = Nino.content.getText('/_admin/design/step/'+ step );
				// Each knob names its own three positions - "eng, Standard,
				// luftig" is not the same sentence as "scharf, Standard, rund"
				button.title = Nino.content.getText('/_admin/design/knob/'+ row.key+ '/'+ step );
				buttons[step] = button;
				group.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, row.value, function( step ) {
				Nino.admin.design._setStep( row.key, step );
			} );

			field.appendChild( group );

			// Only where there is something to go back to - a row that follows
			// has no reset, and the global position follows nothing
			if( row.inherited === false && Nino.admin.design._part !== 'global' ) {
				const reset = dc.createElement('button');
				reset.type = 'button';
				reset.className = 'design-knob-reset';
				reset.textContent = '↺';
				reset.title = Nino.content.getText('/_admin/design/label/follow');
				reset.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/follow') );
				reset.addEventListener( 'click', function() { Nino.admin.design._setStep( row.key, null ) } );
				field.appendChild( reset );
			}

			return field;
		},

		/**
		 *	Move one - or let it follow again
		 *
		 *	@param		{string}				key			A token, '' for the global knob
		 *	@param		{string|null}		step		null puts it back to following
		 *
		 *	@return		void
		 */
		_setStep : function( key, step ) {

			const edit = Nino.admin.design._edit;

			if( Nino.admin.design._part === 'global' )
				edit.knobs[key] = step;
			else {
				const chosen = edit.parts[ Nino.admin.design._part ] || {};
				chosen.knobs = chosen.knobs || {};

				if( step === null )
					delete chosen.knobs[key];
				else
					chosen.knobs[key] = step;
			}

			Nino.admin.design._renderCurrentPart();
			Nino.admin.design._preview();
		},

		/**
		 *	What assets/theme.css is right now - true, worth saying, and not
		 *	what somebody opening this screen came to find out, so it stands at
		 *	the bottom rather than over the controls
		 *
		 *	@return		{Element}
		 */
		_renderState : function() {

			const data = Nino.admin.design._data;
			const box = dc.createElement('div');
			box.id = 'design-state';

			const heading = dc.createElement('h3');
			heading.textContent = Nino.content.getText('/_admin/design/label/state');
			box.appendChild( heading );

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

			/*	The frame follows the picker by default: opening "Blocks" and
				then hunting for the pricing row is work the screen can do. A
				switch rather than a setting, because it is a per-visit
				convenience and nothing a project should have to store */
			const follow = Nino.adminUi.switchField( {
				key 		: 'design-follow',
				label 	: Nino.content.getText('/_admin/design/label/jump'),
				checked	: Nino.admin.design._follow,
			} );

			follow.className += ' design-preview-follow';
			follow.querySelector('input').addEventListener( 'change', function() {
				Nino.admin.design._follow = this.checked;
				if( this.checked === true )
					Nino.admin.design._jump();
			} );

			bar.appendChild( follow );
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
				parts : edit.parts, knobs : edit.knobs, size : edit.size, full : full
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
					// A fresh document scrolls to the top of itself, so the jump
					// has to wait for it rather than happen beside it
					frame.addEventListener( 'load', function once() {
						frame.removeEventListener( 'load', once );
						Nino.admin.design._jump( true );
					} );
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
		 *	Put the part that is open on screen inside the frame.
		 *
		 *	The seven sets have a section of their own in the specimen, named
		 *	after the part; a frame is the <header> or the <footer> around it,
		 *	which needs no id of ours in markup that is the project's. Global is
		 *	the whole page, so it is the top of it
		 *
		 *	@param		{boolean}	[instant]		Without the smooth scroll - after a
		 *															rebuild there is nothing to scroll away from
		 *
		 *	@return		void
		 */
		_jump : function( instant ) {

			const frame = dc.getElementById('design-frame');

			if( frame === null || Nino.admin.design._follow === false )
				return;

			try {

				const doc = frame.contentDocument;
				const part = Nino.admin.design._part;

				if( doc === null || doc.body === null )
					return;

				const target = part === 'header' || part === 'global'
					? doc.body
					: ( part === 'footer' ? doc.querySelector('footer') : doc.getElementById( part ) );

				if( target === null )
					return;

				target.scrollIntoView( { block : 'start', behavior : instant === true ? 'auto' : 'smooth' } );
			}
			catch( e ) {
				// A frame that cannot be reached is a frame that has not been
				// built yet - the load handler above jumps when it has
			}
		},

		/**
		 *	The frame renders at the width that is chosen and is then scaled to
		 *	whatever the column has room for - a desktop layout in half a pane
		 *	is still a desktop layout, and a frame simply made narrow would be
		 *	the phone view with a lie on the label.
		 *
		 *	The workbench owns that arithmetic (Nino.adminUi.scaleFrame): it
		 *	solves the height back through the scale, never magnifies past 1:1,
		 *	and watches the port with a ResizeObserver - so the frame follows
		 *	the rail folding away without anything here having to hear about it
		 *
		 *	@return		void
		 */
		_fit : function() {

			const frame = dc.getElementById('design-frame');

			if( frame === null || frame.parentNode === null )
				return;

			// Rebuilt rather than kept: the width is a choice, and scaleFrame
			// closes over the one it was given
			Nino.admin.design._refit = Nino.adminUi.scaleFrame( frame, frame.parentNode, Nino.admin.design._width );
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
