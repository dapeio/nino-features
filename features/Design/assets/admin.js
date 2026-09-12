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
		// Which half of a design is open: the structure - which set a part is
		// on and where its knobs stand - or the palette everything is drawn in
		_tab		: 'structure',
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
			return {
				parts 	: parts,
				knobs 	: Object.assign( {}, data.knobs || {} ),
				size 		: data.size,
				colours	: Object.assign( {}, data.colours || {} ),
			};
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

			// Controls on one side, what they mean on the other. One column
			// below the breakpoint, where a preview beside a select would be
			// too narrow to be a preview
			const layout = dc.createElement('div');
			layout.id = 'design-layout';

			layout.appendChild( Nino.admin.design._renderControls() );
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

			/*	The way back, on the far side of the bar from the two buttons
				that commit: it appears the moment the selection on screen stops
				being the stored one and goes again when it is back. Which is
				also the honest version of what the green state panel used to
				imply - that one spoke about the last compile and was read as
				speaking about the screen	*/
			const reset = dc.createElement('button');
			reset.type = 'button';
			reset.id = 'design-reset';
			reset.className = 'design-reset-all';
			reset.textContent = Nino.content.getText('/_admin/design/label/reset');
			reset.addEventListener( 'click', function() { Nino.admin.design._revert() } );

			const actions = dc.createElement('div');
			actions.appendChild( reset );
			actions.appendChild( save );
			actions.appendChild( apply );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );
			wrap.appendChild( msg );

			Nino.admin.design._refreshDirty();

			// Only the structure half has a part below the picker to fill
			if( Nino.admin.design._tab !== 'colours' )
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
				} ), edit.size, function( value ) { edit.size = value; Nino.admin.design._preview() },
				Nino.content.getText('/_admin/design/hint/size')
			) );

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

			box.appendChild( Nino.admin.design._select(
				'design-set', Nino.content.getText('/_admin/design/label/variant'),
				Object.keys( row.catalogue || {} ).map( function( set ) {
					return { value : set, label : ( row.catalogue[set] || {} ).name || set };
				} ), chosen.set, function( value ) {
					chosen.set = value;
					/*	A variant brings its own knob rows, and the ones the last
						one had are not this one's - so the whole part is drawn
						again rather than only the select that changed. Which is
						also what writes the new variant's own description into
						the field above them */
					Nino.admin.design._renderCurrentPart();
					Nino.admin.design._preview();
				},
				( ( row.catalogue || {} )[chosen.set] || {} ).description || ''
			) );

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
		/**
		 *	The controls column, on its own so a tab switch can redraw it
		 *	without touching the frame beside it.
		 *
		 *	@return		{HTMLElement}
		 */
		_renderControls : function() {

			const controls = dc.createElement('div');
			controls.id = 'design-controls';

			controls.appendChild( Nino.admin.design._renderTabs() );

			if( Nino.admin.design._tab === 'colours' )
				controls.appendChild( Nino.admin.design._renderColours() );
			else {
				controls.appendChild( Nino.admin.design._renderPicker() );

				const part = dc.createElement('div');
				part.id = 'design-part';
				controls.appendChild( part );
			}

			( ( Nino.admin.design._data || {} ).notes || [] ).forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = note;
				controls.appendChild( p );
			} );

			// What the file on disk is, at the bottom: true and worth saying,
			// and not what somebody opening this screen came to find out
			controls.appendChild( Nino.admin.design._renderState() );

			return controls;
		},

		/**
		 *	The two halves of a design. Structure is which set a part is on and
		 *	where its knobs stand; colours is the palette every one of those
		 *	sets draws in - one question about shape, one about colour, and
		 *	nine parts' worth of rows between them if they share a column.
		 *
		 *	A tablist rather than a button group, because these really are two
		 *	panels: aria-selected, not aria-pressed.
		 *
		 *	@return		{HTMLElement}
		 */
		_renderTabs : function() {

			const bar = dc.createElement('div');
			bar.className = 'nino-admin-tabs design-tabs';
			bar.setAttribute( 'role', 'tablist' );
			bar.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/title') );

			const buttons = {};

			[ 'structure', 'colours' ].forEach( function( key ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.setAttribute( 'role', 'tab' );
				button.textContent = Nino.content.getText('/_admin/design/tab/'+ key);
				buttons[key] = button;
				bar.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, Nino.admin.design._tab, function( key ) {
				Nino.admin.design._switchTab( key );
			}, 'aria-selected' );

			return bar;
		},

		/**
		 *	Open the other half.
		 *
		 *	Only the column is redrawn, never the frame beside it: the page in
		 *	it is the same page under either tab, so rebuilding it would cost a
		 *	request and a flash for a click that changed which controls are on
		 *	screen and nothing at all about the design.
		 *
		 *	@param		{string}	key			'structure' or 'colours'
		 *
		 *	@return		void
		 */
		_switchTab : function( key ) {

			if( key === Nino.admin.design._tab )
				return;

			Nino.admin.design._tab = key;

			const column = dc.getElementById('design-controls');

			if( column === null )
				return Nino.admin.design._render();

			column.replaceWith( Nino.admin.design._renderControls() );

			// ...and the part below the picker, which needs the new column to
			// be in the document before it can find its own node in it
			if( Nino.admin.design._tab !== 'colours' )
				Nino.admin.design._renderCurrentPart();
		},

		/**
		 *	The palette half: two colours, and the five knobs that decide what
		 *	the solver does with them.
		 *
		 *	@return		{HTMLElement}
		 */
		_renderColours : function() {

			const wrap = dc.createElement('div');
			wrap.id = 'design-colours';

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/design/hint/colours');
			wrap.appendChild( hint );

			wrap.appendChild( Nino.admin.design._primaryField() );

			/*	brand and accent are the two surfaces with no contrast promise -
				they are the hex the picker returned, byte for byte, so there is
				no lightness left to solve with. Everything a theme writes on
				uses the -safe roles instead, and this is the one place the
				panel can say that the colour as picked is not one of them.

				Always built, shown only while it applies: every colour knob
				moves the number in it, and the answer to a preview updates the
				line in place rather than rebuilding a column that holds an
				open colour picker - see _absorb()	*/
			const warn = dc.createElement('p');
			warn.id = 'design-brand-warning';
			warn.className = 'nino-admin-hint design-colour-warning';
			wrap.appendChild( warn );

			// Drawn out of what design/list handed over, so a knob added in
			// Colours appears here without this file gaining a line
			const palette = ( Nino.admin.design._data || {} ).palette || {};

			Object.keys( palette ).forEach( function( key ) {
				/*	Harmony *is* the second colour: it is where that colour comes
					from when nobody names one. Two rows a line apart - one asking
					for a hex, one asking where to derive it - read as two
					independent questions, and the second answer silently beat the
					first. One row, the four automatic positions and the swatch
					that overrides them, says which it is	*/
				wrap.appendChild( Nino.admin.design._colourKnobRow( key, palette[key],
					key === 'harmony' ? Nino.admin.design._secondField() : null ) );
			} );

			// By the node, not by its id: this column is not in the document
			// yet on the first render - _renderControls() appends it after
			Nino.admin.design._paintBrandWarning( warn );

			return wrap;
		},

		/**
		 *	The brand colour: a row of its own, because it is the one value the
		 *	whole palette is solved out of
		 *
		 *	@return		{HTMLElement}
		 */
		_primaryField : function() {

			const edit = Nino.admin.design._edit;

			const field = dc.createElement('div');
			field.className = 'design-colour-row';

			const label = dc.createElement('label');
			label.className = 'design-colour-label';
			label.setAttribute( 'for', 'design-colour-primary' );
			label.textContent = Nino.content.getText('/_admin/design/label/primary');

			const note = dc.createElement('small');
			note.textContent = Nino.content.getText('/_admin/design/hint/primary');
			label.appendChild( note );

			field.appendChild( label );

			const input = dc.createElement('input');
			input.type = 'color';
			input.id = 'design-colour-primary';
			input.className = 'design-colour-input';
			input.value = String( ( edit.colours || {} ).primary || '#4faae8' );
			input.addEventListener( 'change', function() { Nino.admin.design._setColour( 'primary', this.value ) } );
			field.appendChild( input );

			return field;
		},

		/**
		 *	The second colour, at the end of the Harmony row it belongs to.
		 *
		 *	It has a state the brand does not: empty, which is the ordinary
		 *	answer rather than a missing one - it means "let Harmony put it on
		 *	the wheel". An <input type="color"> cannot be empty, so while it is
		 *	empty the swatch shows the colour the wheel actually produced, as
		 *	the server computed it, and is drawn quietly to say that nobody
		 *	chose it. Opening it is how you stop deriving it; the way back is
		 *	the reset beside it, or any of the four positions to its left
		 *
		 *	@return		{HTMLElement}
		 */
		_secondField : function() {

			const edit		= Nino.admin.design._edit;
			const value		= String( ( edit.colours || {} ).secondary || '' );
			const derived	= value === '';

			const box = dc.createElement('div');
			box.className = 'design-colour-second'+ ( derived === true ? ' design-colour-row--derived' : '' );

			const input = dc.createElement('input');
			input.type = 'color';
			input.id = 'design-colour-secondary';
			input.className = 'design-colour-input';
			input.value = derived === true
				? String( ( Nino.admin.design._data || {} ).accent || ( edit.colours || {} ).primary || '#4faae8' )
				: value;
			input.title = Nino.content.getText('/_admin/design/label/secondary');
			input.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/secondary') );
			input.addEventListener( 'change', function() { Nino.admin.design._setColour( 'secondary', this.value ) } );
			box.appendChild( input );

			if( derived === false ) {
				const reset = dc.createElement('button');
				reset.type = 'button';
				reset.className = 'design-knob-reset';
				reset.textContent = '\u21ba';
				reset.title = Nino.content.getText('/_admin/design/hint/derived');
				reset.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/hint/derived') );
				reset.addEventListener( 'click', function() { Nino.admin.design._setColour( 'secondary', '' ) } );
				box.appendChild( reset );
			}

			return box;
		},

		/**
		 *	One colour knob. Same row as a part's knob, over as many positions
		 *	as the knob publishes - three for a scale, four for a choice like
		 *	Harmony, where the positions are alternatives rather than a track
		 *
		 *	@param		{string}	key
		 *	@param		{Object}	meta		One entry of Colours::choices()
		 *	@param		{?Element}	[extra]	Drawn after the positions. Harmony's is the
		 *														second colour itself, which is what those
		 *														positions are for
		 *
		 *	@return		{HTMLElement}
		 */
		_colourKnobRow : function( key, meta, extra ) {

			const field = dc.createElement('div');
			field.className = 'design-knob-row';

			const label = dc.createElement('span');
			label.className = 'design-knob-label';
			label.textContent = Nino.content.getText('/_admin/design/colour/'+ key+ '/label');

			const note = dc.createElement('small');
			note.textContent = Nino.content.getText('/_admin/design/colour/'+ key+ '/note');
			label.appendChild( note );

			field.appendChild( label );

			const group = dc.createElement('div');
			group.className = 'nino-admin-tabs design-knob-steps';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/colour/'+ key+ '/label') );

			const buttons = {};

			( meta.steps || [] ).forEach( function( name, index ) {
				const position = String( index + 1 );
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				// The position's own name is the label here, not a step word:
				// "Monochrom" and "Triadisch" are not less and more of anything
				button.textContent = Nino.content.getText('/_admin/design/colour/'+ key+ '/'+ position) || name;
				buttons[position] = button;
				group.appendChild( button );
			} );

			/*	A hex somebody typed overrides the whole knob - Colours::palette()
				takes the Secondary as given and never reaches for Harmony at all.
				So no position is lit while one is set: an active button there
				would claim a derivation that is not happening	*/
			const active = key === 'harmony' && String( ( Nino.admin.design._edit.colours || {} ).secondary || '' ) !== ''
				? ''
				: String( ( Nino.admin.design._edit.colours || {} )[key] || meta['default'] );

			Nino.adminUi.buttonRow( buttons, active, function( position ) {
				Nino.admin.design._setColourKnob( key, position );
			} );

			field.appendChild( group );

			if( extra )
				field.appendChild( extra );

			return field;
		},

		_setColour : function( key, value ) {
			Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
			Nino.admin.design._edit.colours[key] = value;
			Nino.admin.design._redrawColours();
		},

		_setColourKnob : function( key, position ) {

			Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
			Nino.admin.design._edit.colours[key] = parseInt( position, 10 );

			/*	Choosing where the second colour sits is choosing to let Harmony
				put it there. A hex left over from before would override the very
				knob that was just moved, and the knob would look moved and do
				nothing - which is the worst of the three possible states	*/
			if( key === 'harmony' ) {
				Nino.admin.design._edit.colours.secondary = '';
				return Nino.admin.design._redrawColours();
			}

			Nino.admin.design._preview();
		},

		/*	A colour change redraws its own rows - whether the second colour is
			derived decides how it is drawn and whether the reset is beside it -
			where a knob only ever moves the frame. Both then ask for a new
			stylesheet */
		_redrawColours : function() {

			const old = dc.getElementById('design-colours');

			if( old !== null )
				old.replaceWith( Nino.admin.design._renderColours() );

			Nino.admin.design._preview();
		},

		/**
		 *	What the brand as picked measures, under the row that picked it -
		 *	written in place rather than redrawn, because the column it sits in
		 *	holds a colour picker somebody may have open
		 *
		 *	@param		{Element}	[node]		The line itself, for a column that is
		 *															built but not mounted yet
		 *
		 *	@return		void
		 */
		_paintBrandWarning : function( node ) {

			const line = node || dc.getElementById('design-brand-warning');

			if( line === null || line === undefined )
				return;

			const brand = ( ( Nino.admin.design._data || {} ).brand || {} ).light || null;

			line.hidden = brand === null || brand.safe !== false;

			if( line.hidden === false )
				line.textContent = Nino.admin.design._text( '/_admin/design/msg/brand-unsafe', brand.ratio, brand.target );
		},

		/**
		 *	Take over what a preview answered about the colours themselves: the
		 *	second colour as the wheel derived it, and what the brand measures.
		 *
		 *	Written into the two elements that show them rather than redrawing
		 *	the column - the column holds an <input type="color">, and replacing
		 *	one whose native picker is open closes it under the hand that opened
		 *	it. This runs after every preview
		 *
		 *	@param		{Object}	response		The answer of design/preview
		 *
		 *	@return		void
		 */
		_absorb : function( response ) {

			const data = Nino.admin.design._data;

			if( data === null )
				return;

			if( typeof response.accent === 'string' && response.accent !== '' )
				data.accent = response.accent;

			if( response.brand )
				data.brand = response.brand;

			const second = dc.getElementById('design-colour-secondary');

			// Only while it stands for a colour nobody chose. One that was
			// chosen is the value in it, and nothing here may move it
			if( second !== null && String( ( Nino.admin.design._edit.colours || {} ).secondary || '' ) === '' )
				second.value = String( data.accent || second.value );

			Nino.admin.design._paintBrandWarning();
		},

		/**
		 *	Whether the selection on screen is still the stored one.
		 *
		 *	Canonical rather than JSON.stringify() on both: a knob moved and
		 *	then put back leaves a key behind in a different order, and a bar
		 *	offering to undo nothing is worse than no bar
		 *
		 *	@param		{*}	value
		 *
		 *	@return		{string}
		 */
		_canonical : function( value ) {

			if( value === null || typeof value !== 'object' )
				return JSON.stringify( value === undefined ? null : value );

			if( Array.isArray( value ) === true )
				return '['+ value.map( Nino.admin.design._canonical ).join(',')+ ']';

			return '{'+ Object.keys( value ).sort().map( function( key ) {
				return JSON.stringify( key )+ ':'+ Nino.admin.design._canonical( value[key] );
			} ).join(',')+ '}';
		},

		/**
		 *	Show or hide the way back, from whether there is one
		 *
		 *	@return		void
		 */
		_refreshDirty : function() {

			const button = dc.getElementById('design-reset');
			const data 	 = Nino.admin.design._data;

			if( button === null || data === null )
				return;

			button.hidden = Nino.admin.design._canonical( Nino.admin.design._edit )
				=== Nino.admin.design._canonical( Nino.admin.design._selection( data ) );
		},

		/**
		 *	Back to the stored selection. Never further back than that: what was
		 *	saved is saved, and a button that quietly returned a project to the
		 *	delivered design would be a different and much larger promise
		 *
		 *	@return		void
		 */
		_revert : function() {

			const data = Nino.admin.design._data;

			if( data === null )
				return;

			Nino.admin.design._edit = Nino.admin.design._selection( data );
			Nino.admin.design._render();

			const msg = dc.getElementById('design-msg');

			if( msg !== null ) {
				msg.className = '';
				msg.textContent = Nino.content.getText('/_admin/design/msg/reverted');
			}
		},

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

			let key = 'current', warn = false;

			if( data.exists === true && data.ours === false ) { key = 'foreign'; warn = true; }
			else if( data.exists === false || data.compiled === '' ) { key = 'missing'; warn = true; }
			else if( data.current === false ) { key = 'drifted'; warn = true; }

			/*	Only a warning is marked. "The file matches this selection" used
				to be a green panel, and a green panel is a thing the eye keeps
				checking - while this one says nothing about the selection on
				screen, only about the last compile, so it stayed green through
				every change somebody made after it. What is unsaved is the
				action bar's job now: see _refreshDirty()	*/
			const line = dc.createElement('p');
			line.className = warn === true ? 'design-state design-state--warn' : 'nino-admin-hint';
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
			// Every change on the screen ends here, so this is where the bar
			// finds out that there is something to go back from. Not in
			// _previewNow(): that one is debounced, and a button appearing a
			// third of a second after the click that caused it reads as a glitch
			Nino.admin.design._refreshDirty();
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
				parts : edit.parts, knobs : edit.knobs, size : edit.size, colours : edit.colours, full : full
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
				Nino.admin.design._absorb( response );

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

				const view = doc.defaultView;

				if( target === null || view === null )
					return;

				/*	The frame's own window, scrolled by hand - not
					scrollIntoView(). That one walks every scrollable ancestor of
					the element, and an element inside a same-origin iframe has
					the workbench's own pane among them: the frame jumped to the
					part *and* the column beside it slid away under the selects
					that had just been used. This moves one scroller, which is the
					one the switch is about	*/
				const top = target.getBoundingClientRect().top
					+ ( view.scrollY || doc.documentElement.scrollTop || 0 );

				view.scrollTo( {
					top 			: Math.max( 0, Math.round( top ) ),
					behavior	: instant === true ? 'auto' : 'smooth',
				} );
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
		 *	A labelled select that reports its own changes
		 *
		 *	@param		{string}		id
		 *	@param		{string}		label			'' draws none
		 *	@param		{Array}			options		{ value, label }
		 *	@param		{string}		current
		 *	@param		{Function}	onChange
		 *	@param		{string}		[hint]		One line between the name and the control.
		 *															Inside the field rather than a paragraph after
		 *															it: a sentence floating under a select reads as
		 *															the next thing on the screen rather than as
		 *															something about the select above it
		 *
		 *	@return		{Element}
		 */
		_select : function( id, label, options, current, onChange, hint ) {

			const field = dc.createElement('div');
			field.className = 'nino-admin-field';

			if( label !== '' ) {
				const tag = dc.createElement('label');
				tag.setAttribute( 'for', id );
				tag.textContent = label;
				field.appendChild( tag );
			}

			if( hint ) {
				const said = dc.createElement('small');
				said.className = 'design-field-hint';
				said.textContent = hint;
				field.appendChild( said );
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
