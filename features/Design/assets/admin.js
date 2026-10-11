/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Design					The feature's /_admin panel, "Design": six tabs over one
 *													setup - Global, with the header and footer templates,
 *													the root size, the width and the animation of the page;
 *													Fonts, with the five font roles; Content, Buttons and
 *													Components, each with the choice of its template and one
 *													named knob for each thing the template lets go of; and
 *													Colours, with the two colours, the five knobs and the
 *													mode the palette is solved from - and the compile that
 *													turns all of it into assets/theme.css (see
 *													Modules\Design\Admin beside this file). Beside the
 *													controls is one specimen page of the selection, under a
 *													head with the viewport switch, the switch that lets the
 *													frame follow and the reload; the frame scrolls to the
 *													open tab's anchor, or to where a change of a knob shows.
 *													Ships with the feature and is loaded exactly while it
 *													is active.
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

		// The six tabs, in the order they are shown in
		TABS		: [ 'global', 'fonts', 'content', 'buttons', 'components', 'colours' ],

		// The tabs that have named knobs, and the part each of them is the
		// template of: a knob belongs to the tab, the tab to one template
		KNOB_TABS	: [ 'content', 'buttons', 'components' ],

		/*	The id of the element on the specimen page each tab scrolls the frame
			to. The page is templates/preview-specimen.tpl, which carries the
			same six ids; Global is the top of the page, Fonts the typography
			block, Colours the grounds	*/
		ANCHORS	: { global : 'global', fonts : 'type', content : 'content', buttons : 'buttons', components : 'components', colours : 'colours' },

		/*	Where a change of one knob is looked at, by the tab: the id of the element
			on the specimen the frame jumps to in place of the tab's anchor, which is
			where opening the tab goes. For Content the two knobs of the hero go to the
			hero (#content) and every other knob of the tab ('*') to the cards, which
			the specimen's Cards section carries the id of (#cards). A tab with no entry
			and a knob with neither its own nor a '*' use the tab's anchor. The two maps
			are told together in the docblock of _jump()	*/
		KNOB_ANCHORS	: { content : { 'hero-title' : 'content', 'hero-text' : 'content', '*' : 'cards' } },

		// The three widths of the page, by the number the model keeps
		PAGE_WIDTHS	: { '-1' : 'narrow', '0' : 'normal', '1' : 'wide' },

		// The namespace the elements of an icon are made in
		SVG			: 'http://www.w3.org/2000/svg',

		/*	The icons in front of the names of the rows and on the buttons of the
			viewport switch: Lucide's (lucide.dev, ISC - the notice is
			THIRD-PARTY-NOTICES.md), 24 by 24 and drawn with a stroke, as the
			parts each of them is made of - an element and its attributes, in the order
			the shape is given in. Built as elements rather than written as markup, the
			way the rest of the panel is. The class of the svg carries the stroke and the
			size (see .design-icon in admin.css)	*/
		ICONS	: {
			'a-large-small' : [
				[ 'path', { d : 'm15 16 2.536-7.328a1.02 1.02 1 0 1 1.928 0L22 16' } ],
				[ 'path', { d : 'M15.697 14h5.606' } ],
				[ 'path', { d : 'm2 16 4.039-9.69a.5.5 0 0 1 .923 0L11 16' } ],
				[ 'path', { d : 'M3.304 13h6.392' } ],
			],
			'scaling' : [
				[ 'path', { d : 'M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' } ],
				[ 'path', { d : 'M14 15H9v-5' } ],
				[ 'path', { d : 'M16 3h5v5' } ],
				[ 'path', { d : 'M21 3 9 15' } ],
			],
			'align-vertical-space-around' : [
				[ 'rect', { width : '10', height : '6', x : '7', y : '9', rx : '2' } ],
				[ 'path', { d : 'M22 20H2' } ],
				[ 'path', { d : 'M22 4H2' } ],
			],
			'square-round-corner' : [
				[ 'path', { d : 'M21 11a8 8 0 0 0-8-8' } ],
				[ 'path', { d : 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4' } ],
			],
			'square' : [
				[ 'rect', { width : '18', height : '18', x : '3', y : '3', rx : '2' } ],
			],
			'square-stack' : [
				[ 'path', { d : 'M4 10c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2' } ],
				[ 'path', { d : 'M10 16c-1.1 0-2-.9-2-2v-4c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2' } ],
				[ 'rect', { width : '8', height : '8', x : '14', y : '14', rx : '2' } ],
			],
			'rows-3' : [
				[ 'rect', { width : '18', height : '18', x : '3', y : '3', rx : '2' } ],
				[ 'path', { d : 'M21 9H3' } ],
				[ 'path', { d : 'M21 15H3' } ],
			],
			'text-cursor-input' : [
				[ 'path', { d : 'M12 20h-1a2 2 0 0 1-2-2 2 2 0 0 1-2 2H6' } ],
				[ 'path', { d : 'M13 8h7a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-7' } ],
				[ 'path', { d : 'M5 16H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h1' } ],
				[ 'path', { d : 'M6 4h1a2 2 0 0 1 2 2 2 2 0 0 1 2-2h1' } ],
				[ 'path', { d : 'M9 6v12' } ],
			],
			'palette' : [
				[ 'path', { d : 'M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z' } ],
				[ 'circle', { cx : '13.5', cy : '6.5', r : '.5', fill : 'currentColor' } ],
				[ 'circle', { cx : '17.5', cy : '10.5', r : '.5', fill : 'currentColor' } ],
				[ 'circle', { cx : '6.5', cy : '12.5', r : '.5', fill : 'currentColor' } ],
				[ 'circle', { cx : '8.5', cy : '7.5', r : '.5', fill : 'currentColor' } ],
			],
			'thermometer' : [
				[ 'path', { d : 'M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z' } ],
			],
			'droplet' : [
				[ 'path', { d : 'M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z' } ],
			],
			'contrast' : [
				[ 'circle', { cx : '12', cy : '12', r : '10' } ],
				[ 'path', { d : 'M12 18a6 6 0 0 0 0-12v12z' } ],
			],
			'sun-moon' : [
				[ 'path', { d : 'M12 2v2' } ],
				[ 'path', { d : 'M14.837 16.385a6 6 0 1 1-7.223-7.222c.624-.147.97.66.715 1.248a4 4 0 0 0 5.26 5.259c.589-.255 1.396.09 1.248.715' } ],
				[ 'path', { d : 'M16 12a4 4 0 0 0-4-4' } ],
				[ 'path', { d : 'm19 5-1.256 1.256' } ],
				[ 'path', { d : 'M20 12h2' } ],
			],
			// The three devices of the viewport switch: the shapes the Builder's symbols carry
			'smartphone' : [
				[ 'rect', { width : '14', height : '20', x : '5', y : '2', rx : '2', ry : '2' } ],
				[ 'path', { d : 'M12 18h.01' } ],
			],
			'tablet' : [
				[ 'rect', { width : '16', height : '20', x : '4', y : '2', rx : '2', ry : '2' } ],
				[ 'line', { x1 : '12', x2 : '12.01', y1 : '18', y2 : '18' } ],
			],
			'monitor' : [
				[ 'rect', { width : '20', height : '14', x : '2', y : '3', rx : '2' } ],
				[ 'line', { x1 : '8', x2 : '16', y1 : '21', y2 : '21' } ],
				[ 'line', { x1 : '12', x2 : '12', y1 : '17', y2 : '21' } ],
			],
			// The reload of the preview, beside the devices: Lucide's refresh-cw
			'refresh-cw' : [
				[ 'path', { d : 'M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8' } ],
				[ 'path', { d : 'M21 3v5h-5' } ],
				[ 'path', { d : 'M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16' } ],
				[ 'path', { d : 'M8 16H3v5' } ],
			],
		},

		/*	Which icon a row opens with, by the tab and the knob it sets - so that what
			is being set is felt before it is read: the size of a text, of a surface, a
			space, the corners, a line, a shadow. The knobs are the feature's own (see
			Setup::KNOBS), and so are the rows of the palette - the brand colour, the
			second colour, which is the row of Harmony, temperature, saturation,
			contrast, depth and the mode: all of them have one, so that every name of
			the Colours tab starts at the same left edge. A row with no entry has no
			icon, and no stand-in shape takes its place	*/
		KNOB_ICONS	: {
			'content/hero-title' : 'a-large-small',
			'content/hero-text' : 'a-large-small',
			'content/quiet' : 'a-large-small',
			'content/loud' : 'a-large-small',
			'content/section-space' : 'scaling',
			'content/article-space' : 'align-vertical-space-around',
			'content/corners' : 'square-round-corner',
			'content/border' : 'square',
			'content/shadow' : 'square-stack',
			'buttons/small' : 'a-large-small',
			'buttons/big' : 'a-large-small',
			'buttons/space' : 'align-vertical-space-around',
			'buttons/corners' : 'square-round-corner',
			'components/scale' : 'a-large-small',
			'components/space' : 'align-vertical-space-around',
			'components/corners' : 'square-round-corner',
			'components/border' : 'square',
			'components/zebra' : 'rows-3',
			'components/labels' : 'text-cursor-input',
			'colours/primary' : 'palette',
			'colours/harmony' : 'palette',
			'colours/temperature' : 'thermometer',
			'colours/saturation' : 'droplet',
			'colours/contrast' : 'contrast',
			'colours/depth' : 'square-stack',
			'colours/mode' : 'sun-moon',
		},

		// The widths the preview can be looked at in, one button of the viewport
		// switch each, with the icon it is drawn with (see ICONS). A header set is
		// mostly a decision about where the menu goes when there is no room for it,
		// and that decision is invisible at one width
		WIDTHS	: [
			{ key : 'phone', 	 px :  390, icon : 'smartphone' },
			{ key : 'tablet',  px :  820, icon : 'tablet' },
			{ key : 'desktop', px : 1280, icon : 'monitor' },
		],

		// Which of them is on screen, and the debounce behind every select
		_width	: 1280,
		_timer	: null,
		// Whether the frame jumps by itself: to the anchor of a tab that is opened and to
		// where a change is looked at. On at the start of every visit and kept nowhere -
		// a convenience of the visit, nothing a project should have to store
		_follow	: true,
		// Where the next jump goes when it is not the anchor of the open tab: the id of the
		// element the last change of a knob is looked at in (see KNOB_ANCHORS). null is
		// the tab's own, which is where opening a tab and choosing a template put it back to
		_target	: null,
		// The tab that is open
		_tab		: 'global',
		// The header and footer the frame was last built with. Everything else
		// is a stylesheet, and a stylesheet can be swapped inside it
		_frames	: null,
		// Whether the animation of the page was changed since the frame last played it
		_replays	: false,

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

			// The shell brings the panel on screen again after a Save it asked
			// for failed: drawn anew, the reason the save gave would be gone,
			// and so would the selection nobody has stored
			if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty.isDirty( [ 'design' ] ) === true )
				return;

			Nino.admin.design._render();
		},

		/**
		 *	One action of this panel. The workbench's own request helper posts
		 *	where this Nino has one - it knows the project's directory and what
		 *	to do when the page has outlived its session; the post below is
		 *	what every panel did before it, with the base the asset bundle
		 *	fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		( status, parsed json or null )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'design/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'design/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the server's code in the workbench's
		 *	language, then its own message, then this panel's sentence - where
		 *	this Nino has errorText(). Before it, "(status) message"
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key					Fill key of the panel's own sentence
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( key ) );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.design._errorText( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	The posted shape, out of what design/list answered: every key of the
		 *	model the screen can change, as a copy of its own
		 *
		 *	@param		{Object}	data
		 *
		 *	@return		{Object}					{ parts, knobs, size, width, motion, fonts, colours }
		 */
		_selection : function( data ) {

			const parts = {};
			( data.parts || [] ).forEach( function( row ) { parts[row.part] = row.set } );

			const knobs = {};
			Object.keys( data.tabs || {} ).forEach( function( tab ) {
				knobs[tab] = Object.assign( {}, ( data.knobs || {} )[tab] || {} );
			} );

			const fonts = {};
			Object.keys( data.fonts || {} ).forEach( function( role ) {
				fonts[role] = Object.assign( {}, data.fonts[role] );
			} );

			return {
				parts 	: parts,
				knobs 	: knobs,
				size 		: data.size,
				width 	: data.width,
				motion 	: Object.assign( {}, data.motion || {} ),
				fonts 	: fonts,
				colours	: Object.assign( {}, data.colours || {} ),
			};
		},

		/**
		 *	One part, as design/list answered it
		 *
		 *	@param		{string}	part
		 *
		 *	@return		{Object}					{} where there is none
		 */
		_row : function( part ) {
			return ( ( Nino.admin.design._data || {} ).parts || [] ).filter( function( row ) {
				return row.part === part;
			} )[0] || {};
		},

		/**
		 *	What a template is called in a list: the version, then its name
		 *
		 *	A part with five templates is a list somebody scans, and "v3" is what
		 *	they say and type about it. A file with no @name is offered under its
		 *	own name already, so prefixing that one would read "v3 - v3"
		 *
		 *	@param		{string}	set
		 *	@param		{Object}	named			One entry of a part's catalogue
		 *
		 *	@return		{string}
		 */
		_setLabel : function( set, named ) {
			return named && named.name && named.name !== set ? set+ ' - '+ named.name : set;
		},

		/**
		 *	The knobs the template chosen for a tab answers to. The rest of the
		 *	tab's knobs are drawn, greyed out
		 *
		 *	@param		{string}	tab				A key of KNOB_TABS
		 *
		 *	@return		{Array}
		 */
		_answered : function( tab ) {
			const row = Nino.admin.design._row( tab );
			return ( ( row.catalogue || {} )[ Nino.admin.design._edit.parts[tab] ] || {} ).knobs || [];
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
			// A function rather than the string: a value is replaced as it is, and
			// a shortcode that carries a "$" is not a replacement pattern
			values.forEach( function( value ) { out = out.replace( /%[sdn]/, function() { return String( value ) } ) } );
			return out;
		},

		/**
		 *	When something was written, the way the panel says it: the date and
		 *	the minute, in the zone the server wrote it in
		 *
		 *	@param		{string}	iso						An ISO 8601 timestamp
		 *
		 *	@return		{string}
		 */
		_when : function( iso ) {
			return String( iso ).substring( 0, 16 ).replace( 'T', ' ' );
		},

		/**
		 *	A file Design writes, as a project names it: without the leading slash
		 *
		 *	@param		{string}	target				A virtual path
		 *
		 *	@return		{string}
		 */
		_name : function( target ) {
			return String( target ).replace( /^\//, '' );
		},

		/**
		 *	The eyebrow over the title of the preview.
		 *
		 *	The workbench owns the type - .nino-admin-eyebrow is its own - and
		 *	this file owns where the line sits
		 *
		 *	@param		{string}	label
		 *
		 *	@return		{Element}
		 */
		_eyebrow : function( label ) {
			const line = dc.createElement('span');
			line.className = 'nino-admin-eyebrow design-eyebrow';
			line.textContent = label;
			return line;
		},

		/**
		 *	The small heading and the hint line over a block of rows - what a block
		 *	of knob rows needs: the rows are a list of values, and a list of
		 *	values needs a sentence saying what they are
		 *
		 *	@param		{string}	title
		 *	@param		{string}	[note]		'' draws no second line
		 *	@param		{?Element}	[icon]		Before the title, where the block has one (see _icon())
		 *
		 *	@return		{Element}
		 */
		_sectionLabel : function( title, note, icon ) {

			const heading = dc.createElement('div');
			heading.className = 'design-section-label';

			const strong = dc.createElement('strong');
			strong.textContent = title;

			if( icon )
				strong.insertBefore( icon, strong.firstChild );

			heading.appendChild( strong );

			if( note ) {
				const small = dc.createElement('small');
				small.textContent = note;
				heading.appendChild( small );
			}

			return heading;
		},

		/**
		 *	The icon of a row, drawn from the shapes in ICONS (see _svg())
		 *
		 *	@param		{string}	key				'<tab>/<knob>', as KNOB_ICONS has it
		 *
		 *	@return		{?Element}				null where the row has none
		 */
		_icon : function( key ) {
			return Nino.admin.design._svg( Nino.admin.design.KNOB_ICONS[key] );
		},

		/**
		 *	An icon by its Lucide name, drawn from the shapes in ICONS: an svg that
		 *	is decoration - out of reach of assistive technology and of the tab key -
		 *	and says in data-icon which of them it is. The rows of the tabs and the
		 *	buttons of the viewport switch are drawn with it
		 *
		 *	@param		{string}	name			A key of ICONS
		 *
		 *	@return		{?Element}				null where there is no such shape
		 */
		_svg : function( name ) {

			const shapes = Nino.admin.design.ICONS[name];

			if( shapes === undefined )
				return null;

			const svg = dc.createElementNS( Nino.admin.design.SVG, 'svg' );
			svg.classList.add('design-icon');
			svg.setAttribute( 'viewBox', '0 0 24 24' );
			svg.setAttribute( 'aria-hidden', 'true' );
			svg.setAttribute( 'focusable', 'false' );
			svg.dataset.icon = name;

			shapes.forEach( function( shape ) {
				const part = dc.createElementNS( Nino.admin.design.SVG, shape[0] );
				Object.keys( shape[1] ).forEach( function( attribute ) { part.setAttribute( attribute, shape[1][attribute] ) } );
				svg.appendChild( part );
			} );

			return svg;
		},

		/**
		 *	What the label of a row is made of: the icon of the row where it has
		 *	one, and a box that holds the name with its note under it. The label
		 *	itself is the row of the two (see .design-knob-label in admin.css)
		 *
		 *	@param		{Element}	label
		 *	@param		{string}	key				'<tab>/<knob>', for the icon
		 *	@param		{string}	name
		 *	@param		{string}	note
		 *
		 *	@return		void
		 */
		_labelContent : function( label, key, name, note ) {

			const icon = Nino.admin.design._icon( key );

			if( icon !== null )
				label.appendChild( icon );

			const box = dc.createElement('span');
			box.className = 'design-knob-text';
			box.textContent = name;

			const small = dc.createElement('small');
			small.textContent = note;
			box.appendChild( small );

			label.appendChild( box );
		},

		/**
		 *	The head of the pane the screen stands in: the row the shell
		 *	renders over every panel, with the panel's name in it (see
		 *	Nino.adminUi.panelHead()).
		 *
		 *	null where there is none - a kernel from before the head, or the
		 *	script drawn somewhere other than its pane - and the strip then
		 *	opens the column of controls, where it stood before there was a
		 *	head to put it in
		 *
		 *	@return		{Object|null}			{ element, title, actions, tabs( strip ) }
		 */
		_head : function() {
			const wrap = dc.getElementById('design-form');
			return wrap !== null && typeof Nino.adminUi.panelHead === 'function' ? Nino.adminUi.panelHead( wrap ) : null;
		},

		/**
		 *	The whole screen: the open tab in one column, a preview of the lot in
		 *	the other.
		 *
		 *	A tab at a time rather than six blocks at once, because six blocks is
		 *	a list to read and one tab is a decision to make. The strip in the
		 *	pane's head says which one is open; the preview beside it is the
		 *	whole page either way, scrolled to where the open tab is
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = dc.getElementById('design-form');
			const data = Nino.admin.design._data;
			if( wrap === null || data === null )
				return;

			wrap.innerHTML = '';

			/*	No heading of its own: the pane's head names the panel. The
				strip goes into that row beside the name, the way the Features
				panel's does, rather than over the column - it switches the
				controls on the left and nothing else, so it belongs with the
				name of the screen and not above one of its two halves. Handed
				over on every render; tabs() takes the place of the strip
				drawn before it, so coming back to the panel does not stack a
				second one	*/
			const head = Nino.admin.design._head();

			if( head !== null )
				head.tabs( Nino.admin.design._renderTabs() );

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
			// Always the same button: what applying will do to which file is
			// asked in a confirmation after the click, from design/plan, rather
			// than guessed here from one of the three files
			apply.textContent = Nino.content.getText('/_admin/design/label/apply');
			apply.addEventListener( 'click', function() { Nino.admin.design._save( true, apply, msg ) } );

			/*	The way back, on the far side of the bar from the two buttons
				that commit: it appears the moment the selection on screen stops
				being the stored one and goes again when it is back	*/
			const reset = dc.createElement('button');
			reset.type = 'button';
			reset.id = 'design-reset';
			reset.className = 'design-reset-all';
			reset.textContent = Nino.content.getText('/_admin/design/label/reset');
			reset.addEventListener( 'click', function() { Nino.admin.design._revert() } );

			const actions = dc.createElement('div');

			/*	The other way back, before it: to the three files as they were
				before the last apply - which is neither what is on screen nor what
				is stored, so a button of its own, there only while there is a
				version to go back to. Its title dates that version. Restoring swaps
				it with the present one, so the same button is there afterwards (see
				_restore())	*/
			if( data.previous ) {
				const restore = dc.createElement('button');
				restore.type = 'button';
				restore.id = 'design-restore';
				restore.textContent = Nino.content.getText('/_admin/design/label/restore');
				restore.title = Nino.admin.design._text( '/_admin/design/state/previous', Nino.admin.design._when( data.previous.at ) );
				restore.addEventListener( 'click', function() { Nino.admin.design._restore( restore ) } );
				actions.appendChild( restore );
			}

			actions.appendChild( reset );
			actions.appendChild( save );
			actions.appendChild( apply );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );

			// What the two buttons are, under them: a draft is not the website
			const hint = dc.createElement('p');
			hint.id = 'design-actions-hint';
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/design/hint/actions');
			wrap.appendChild( hint );
			wrap.appendChild( msg );

			Nino.admin.design._refreshDirty();

			/*	A rendered screen has an empty frame in it, and the frame is the
				answer to what every select above it means - so it is filled
				straight away rather than on a button somebody has to find */
			Nino.admin.design._frames = null;
			Nino.admin.design._preview( 0 );

			// A screen drawn from the stored selection has nothing unsaved
			// to mark any more
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
		},

		/**
		 *	The controls column, on its own so a tab switch can redraw it
		 *	without touching the frame beside it - or the strip in the pane's
		 *	head, which is the element the switch was made on, and keeps the
		 *	focus an arrow key put on it.
		 *
		 *	What the server dropped or replaced on the way stands first, over the
		 *	open tab: it is true whichever tab is open. The state of the files is
		 *	not here, it is the last row of the summary under the frame
		 *
		 *	@return		{HTMLElement}
		 */
		_renderControls : function() {

			const controls = dc.createElement('div');
			controls.id = 'design-controls';

			// Only where there is no head does the strip open the column (see
			// _head()) - and is drawn again with it on every switch
			if( Nino.admin.design._head() === null )
				controls.appendChild( Nino.admin.design._renderTabs() );

			( ( Nino.admin.design._data || {} ).notes || [] ).forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = note;
				controls.appendChild( p );
			} );

			const body = dc.createElement('div');
			body.id = 'design-body';
			controls.appendChild( body );

			Nino.admin.design._fillBody( body );

			return controls;
		},

		/**
		 *	What the open tab holds, into the box the column keeps for it
		 *
		 *	@param		{Element}	body
		 *
		 *	@return		void
		 */
		_fillBody : function( body ) {

			const tab = Nino.admin.design._tab;

			if( tab === 'global' )
				return Nino.admin.design._renderGlobal( body );

			if( tab === 'fonts' )
				return body.appendChild( Nino.admin.design._renderFonts() );

			if( tab === 'colours' )
				return body.appendChild( Nino.admin.design._renderColours() );

			body.appendChild( Nino.admin.design._renderKnobs( tab ) );
		},

		/**
		 *	The six tabs. A tablist rather than a button group, because these
		 *	really are six panels: aria-selected, not aria-pressed.
		 *
		 *	@return		{HTMLElement}
		 */
		_renderTabs : function() {

			const bar = dc.createElement('div');
			// --bar: the underline strip every panel with several screens uses,
			// the Users and Features panels among them - not the segmented row of
			// boxes, which is a control for a value, not for a screen
			bar.className = 'nino-admin-tabs nino-admin-tabs--bar design-tabs';
			bar.setAttribute( 'role', 'tablist' );
			bar.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/title') );

			const buttons = {};

			Nino.admin.design.TABS.forEach( function( key ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.setAttribute( 'role', 'tab' );
				button.dataset.tab = key;
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
		 *	Open another tab.
		 *
		 *	Only the column is redrawn, never the frame beside it: the page in
		 *	it is the same page under every tab, so rebuilding it would cost a
		 *	request and a flash for a click that changed which controls are on
		 *	screen and nothing at all about the design. What the frame does is
		 *	scroll, smoothly, to the anchor of the tab that was opened.
		 *
		 *	Nor the strip in the pane's head. The tab the switch was made on is
		 *	the one the keyboard is on, and the strip used to be drawn again
		 *	with the column: an arrow key opened the next tab and dropped the
		 *	focus onto the page, so the next arrow went nowhere.
		 *
		 *	@param		{string}	key			A key of TABS
		 *
		 *	@return		void
		 */
		_switchTab : function( key ) {

			if( key === Nino.admin.design._tab )
				return;

			Nino.admin.design._tab = key;

			// Where a change was looked at belonged to the tab that was left: the
			// frame goes to the anchor of this one
			Nino.admin.design._target = null;

			const column = dc.getElementById('design-controls');

			if( column === null )
				return Nino.admin.design._render();

			column.replaceWith( Nino.admin.design._renderControls() );

			/*	The list under the frame reads back whichever tab is open, and
				this is the one change on the screen that reaches no preview -
				so it is drawn here rather than left to _preview()	*/
			Nino.admin.design._paintSummary();
			Nino.admin.design._jump();
		},

		/**
		 *	The Global tab: the two frames, the one size the page is measured in,
		 *	the width of the page and its animation.
		 *
		 *	Drawn once per visit of the tab and changed in place after that: a
		 *	select that redrew its card when it was used would take the focus
		 *	away from the control that was just operated. The other three
		 *	templates are chosen on the tabs they belong to (see _renderKnobs()),
		 *	where the choice does redraw the tab - and gives the focus back
		 *
		 *	@param		{Element}	body		The box the column keeps for the open tab
		 *
		 *	@return		void
		 */
		_renderGlobal : function( body ) {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;

			// The two frames, which bring markup and have no knobs
			const templates = dc.createElement('div');
			templates.className = 'nino-admin-card design-form-section';

			( data.parts || [] ).filter( function( row ) { return row.kind === 'frame' } ).forEach( function( row ) {
				templates.appendChild( Nino.admin.design._partField( row ) );
			} );

			body.appendChild( templates );

			// The page
			const page = dc.createElement('div');
			page.className = 'nino-admin-card design-form-section';

			const grid = dc.createElement('div');
			grid.className = 'design-form-grid';

			grid.appendChild( Nino.admin.design._select(
				'design-size', Nino.content.getText('/_admin/design/label/size'),
				( data.sizes || [] ).map( function( size ) {
					return { value : size, label : Nino.content.getText('/_admin/design/size/'+ size ) };
				} ), edit.size, function( value ) { edit.size = value; Nino.admin.design._preview() },
				Nino.content.getText('/_admin/design/hint/size')
			) );

			grid.appendChild( Nino.admin.design._select(
				'design-pagewidth', Nino.content.getText('/_admin/design/label/pagewidth'),
				[ -1, 0, 1 ].map( function( width ) {
					return { value : String( width ), label : Nino.content.getText('/_admin/design/pagewidth/'+ Nino.admin.design.PAGE_WIDTHS[String( width )] ) };
				} ), String( edit.width ), function( value ) { edit.width = parseInt( value, 10 ); Nino.admin.design._preview() },
				Nino.content.getText('/_admin/design/hint/pagewidth')
			) );

			page.appendChild( grid );
			body.appendChild( page );

			// The animation of the page
			body.appendChild( Nino.admin.design._renderMotion() );
		},

		/**
		 *	The animation of the page: what the Viewport animations of Nino.css do
		 *	to everything that carries no animation of its own. Three selects, and
		 *	the first decides whether the other two have anything to say
		 *
		 *	@return		{Element}
		 */
		_renderMotion : function() {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;

			const card = dc.createElement('div');
			card.className = 'nino-admin-card design-form-section';
			card.appendChild( Nino.admin.design._sectionLabel(
				Nino.content.getText('/_admin/design/label/motion'),
				Nino.content.getText('/_admin/design/hint/motion') ) );

			const grid = dc.createElement('div');
			grid.className = 'design-form-grid';

			// '' is what Nino.css does by itself, and is what a fresh setup has
			const options = function( key, values ) {
				return [ { value : '', label : Nino.content.getText('/_admin/design/motion/default') } ].concat( values.map( function( value ) {
					return { value : value, label : Nino.content.getText('/_admin/design/motion/'+ key+ '/'+ value ) };
				} ) );
			};

			const effect = Nino.admin.design._select(
				'design-motion-effect', Nino.content.getText('/_admin/design/motion/effect/label'),
				options( 'effect', data.effects || [] ), edit.motion.effect || '',
				function( value ) { Nino.admin.design._setMotion( 'effect', value ) } );
			const strength = Nino.admin.design._select(
				'design-motion-strength', Nino.content.getText('/_admin/design/motion/strength/label'),
				options( 'strength', data.strengths || [] ), edit.motion.strength || '',
				function( value ) { Nino.admin.design._setMotion( 'strength', value ) } );
			const speed = Nino.admin.design._select(
				'design-motion-speed', Nino.content.getText('/_admin/design/motion/speed/label'),
				options( 'speed', data.speeds || [] ), edit.motion.speed || '',
				function( value ) { Nino.admin.design._setMotion( 'speed', value ) } );

			grid.appendChild( effect );
			grid.appendChild( strength );
			grid.appendChild( speed );
			card.appendChild( grid );

			Nino.admin.design._motionOff( [ strength.querySelector('select'), speed.querySelector('select') ] );

			return card;
		},

		/**
		 *	Strength and speed only mean something once there is an effect: until
		 *	then they are switched off rather than left to say nothing
		 *
		 *	@param		{Array}		selects			The two controls
		 *
		 *	@return		void
		 */
		_motionOff : function( selects ) {

			const none = ( Nino.admin.design._edit.motion.effect || '' ) === '';

			selects.forEach( function( select ) {
				if( select !== null && select !== undefined )
					select.disabled = none;
			} );
		},

		/**
		 *	The select a template is chosen in: the name of the part over it, the
		 *	catalogue as its options and the description of the chosen one as the
		 *	line under it. The same for a frame on Global and for the template of
		 *	a knob tab
		 *
		 *	@param		{Object}	row			One part, as design/list answered it
		 *
		 *	@return		{Element}
		 */
		_partField : function( row ) {

			const field = Nino.admin.design._select(
				'design-set-'+ row.part, Nino.content.getText('/_admin/design/part/'+ row.part ),
				Object.keys( row.catalogue || {} ).map( function( set ) {
					return { value : set, label : Nino.admin.design._setLabel( set, row.catalogue[set] ) };
				} ), Nino.admin.design._edit.parts[row.part], function( value ) {
					Nino.admin.design._setPart( row.part, value, field );
				},
				( ( row.catalogue || {} )[ Nino.admin.design._edit.parts[row.part] ] || {} ).description || ''
			);

			return field;
		},

		/**
		 *	Choose a template for a part.
		 *
		 *	A template brings its own knobs, and the ones the last one had are not
		 *	this one's: a position the new template does not answer to goes back
		 *	to the middle, the way the server holds it anyway - so the screen
		 *	never shows a setup the stored one could not be.
		 *
		 *	A frame changes the document and nothing else on its tab, so the line
		 *	under its select is written in place and the card stays as it is. The
		 *	template of a knob tab decides which of the rows under it are greyed
		 *	out, so the box of the tab is drawn again - which takes the select that
		 *	was just used out of the document. It is found again by its id and given
		 *	the focus back, or the keyboard that chose the template would have
		 *	nothing to choose the next one with
		 *
		 *	@param		{string}		part
		 *	@param		{string}		set
		 *	@param		{Element}		[field]		A frame's select field, whose note is the template's description
		 *
		 *	@return		void
		 */
		_setPart : function( part, set, field ) {

			const edit = Nino.admin.design._edit;
			const row = Nino.admin.design._row( part );

			edit.parts[part] = set;

			// Another template is a change of the whole tab, not of the knob that was
			// looked at last: the frame goes to the anchor of the tab
			Nino.admin.design._target = null;

			if( Nino.admin.design.KNOB_TABS.indexOf( part ) === -1 ) {

				const note = field ? field.querySelector('small') : null;

				if( note )
					note.textContent = ( ( row.catalogue || {} )[set] || {} ).description || '';
			}
			else {

				const answered = ( ( row.catalogue || {} )[set] || {} ).knobs || [];

				Object.keys( edit.knobs[part] || {} ).forEach( function( knob ) {
					if( answered.indexOf( knob ) === -1 )
						edit.knobs[part][knob] = 0;
				} );

				const body = dc.getElementById('design-body');

				if( body !== null ) {
					body.innerHTML = '';
					Nino.admin.design._fillBody( body );
				}

				const again = dc.getElementById('design-set-'+ part);

				if( again !== null )
					again.focus();
			}

			Nino.admin.design._preview();
		},

		/**
		 *	Choose one thing of the animation
		 *
		 *	@param		{string}	key				effect, strength or speed
		 *	@param		{string}	value			'' is what Nino.css does by itself
		 *
		 *	@return		void
		 */
		_setMotion : function( key, value ) {

			Nino.admin.design._edit.motion[key] = value;
			Nino.admin.design._replays = true;
			Nino.admin.design._motionOff( [ dc.getElementById('design-motion-strength'), dc.getElementById('design-motion-speed') ] );
			Nino.admin.design._preview();
		},

		/**
		 *	The Fonts tab: the five roles of Nino.css in a table of five rows and
		 *	four columns - the family, the weight, the case and the size. The
		 *	controls stand bare in the cells and the words are in the column heads,
		 *	which is what keeps the table readable in a third of the screen; each
		 *	control still carries its role and its column as its accessible name
		 *
		 *	@return		{Element}
		 */
		_renderFonts : function() {

			const data = Nino.admin.design._data;

			const card = dc.createElement('div');
			card.className = 'nino-admin-card design-form-section';

			card.appendChild( Nino.admin.design._sectionLabel(
				Nino.content.getText('/_admin/design/label/fonts'),
				Nino.content.getText('/_admin/design/hint/fonts') ) );

			// The roles of a table are said out loud: the stylesheet lays the rows
			// out as grids, and a display other than a table's takes the
			// semantics of the element away
			const table = dc.createElement('table');
			table.className = 'design-fonts';
			table.setAttribute( 'role', 'table' );

			const thead = dc.createElement('thead');
			thead.setAttribute( 'role', 'rowgroup' );
			const heads = dc.createElement('tr');
			heads.setAttribute( 'role', 'row' );

			[ 'role', 'family', 'weight', 'uppercase', 'size' ].forEach( function( key ) {
				const th = dc.createElement('th');
				th.setAttribute( 'scope', 'col' );
				th.setAttribute( 'role', 'columnheader' );
				th.textContent = key === 'role' ? '' : Nino.content.getText('/_admin/design/font/head/'+ key );
				heads.appendChild( th );
			} );

			thead.appendChild( heads );
			table.appendChild( thead );

			const tbody = dc.createElement('tbody');
			tbody.setAttribute( 'role', 'rowgroup' );

			( data.roles || [] ).forEach( function( role ) {
				tbody.appendChild( Nino.admin.design._fontRow( role ) );
			} );

			table.appendChild( tbody );
			card.appendChild( table );

			return card;
		},

		/**
		 *	One row of the table: a role, and the four things a role is given
		 *
		 *	@param		{string}	role			A key of data.roles
		 *
		 *	@return		{Element}
		 */
		_fontRow : function( role ) {

			const data = Nino.admin.design._data;
			const chosen = Nino.admin.design._edit.fonts[role] || {};
			const name = Nino.content.getText('/_admin/design/font/role/'+ role );

			const row = dc.createElement('tr');
			row.dataset.role = role;
			row.setAttribute( 'role', 'row' );

			const head = dc.createElement('th');
			head.setAttribute( 'scope', 'row' );
			head.setAttribute( 'role', 'rowheader' );
			head.textContent = name;
			row.appendChild( head );

			// A cell around a control that names itself: "Title - Weight"
			const cell = function( control, column ) {
				const td = dc.createElement('td');
				td.setAttribute( 'role', 'cell' );
				control.setAttribute( 'aria-label', name+ ' - '+ Nino.content.getText('/_admin/design/font/head/'+ column ) );
				td.appendChild( control );
				row.appendChild( td );
				return td;
			};

			const select = function( key, options, current ) {
				const control = dc.createElement('select');
				control.id = 'design-font-'+ role+ '-'+ key;
				control.dataset.role = role;
				control.dataset.key = key;
				options.forEach( function( option ) {
					const node = dc.createElement('option');
					node.value = option.value;
					node.textContent = option.label;
					if( option.value === current )
						node.selected = true;
					control.appendChild( node );
				} );
				control.addEventListener( 'change', function() { Nino.admin.design._setFont( role, key, control.value ) } );
				return cell( control, key );
			};

			select( 'family', ( ( data.options || {} )[role] || [ '' ] ).map( function( slug ) {
				return { value : slug, label : Nino.admin.design._familyLabel( role, slug ) };
			} ), chosen.family || '' );

			select( 'weight', ( data.weights || [] ).map( function( weight ) {
				return { value : weight, label : Nino.content.getText('/_admin/design/weight/'+ weight ) };
			} ), chosen.weight || 'normal' );

			// The code has no case switch: capitals in a block of code are not a
			// choice anybody makes. Its cell says so rather than staying empty
			if( role === 'code' ) {
				const none = dc.createElement('td');
				none.setAttribute( 'role', 'cell' );
				none.className = 'design-fonts-none';
				none.textContent = '—';
				row.appendChild( none );
			}
			else {
				const field = Nino.adminUi.switchField( {
					key 		: 'design-font-'+ role+ '-uppercase',
					label 	: name+ ' - '+ Nino.content.getText('/_admin/design/font/head/uppercase'),
					checked	: chosen.uppercase === true,
				} );
				const input = field.querySelector('input');
				input.id = 'design-font-'+ role+ '-uppercase';
				input.dataset.role = role;
				input.dataset.key = 'uppercase';
				input.addEventListener( 'change', function() { Nino.admin.design._setFont( role, 'uppercase', input.checked ) } );
				const td = dc.createElement('td');
				td.setAttribute( 'role', 'cell' );
				td.className = 'design-fonts-switch';
				td.appendChild( field );
				row.appendChild( td );
			}

			/*	The size is typed, in rem. Empty is what Nino.css gives the role, and
				the field says what that is where it would show a number - the number
				as the field shows and takes it, with the point: a number field drops a
				typed comma, so the decimal sign of the language is for the summary
				only (see _sizeLabel()). What is typed is held inside the bounds of the
				role the way the server holds it, and the field then shows what was
				kept. The field steps by a tenth, which every bound is on: that is the
				spinner's step and the browser's own hint of what is valid, and not the
				model's - a typed 1.25 stays 1.25	*/
			const bounds = ( data.bounds || {} )[role] || [];
			const size = dc.createElement('input');
			size.type = 'number';
			size.id = 'design-font-'+ role+ '-size';
			size.dataset.role = role;
			size.dataset.key = 'size';
			size.setAttribute( 'inputmode', 'decimal' );
			size.setAttribute( 'step', '.1' );
			size.setAttribute( 'min', String( bounds[0] ) );
			size.setAttribute( 'max', String( bounds[1] ) );
			size.setAttribute( 'placeholder', String( ( data.inherited || {} )[role] ) );
			size.value = chosen.size || '';
			size.addEventListener( 'change', function() {
				size.value = Nino.admin.design._clampSize( role, size.value );
				Nino.admin.design._setFont( role, 'size', size.value );
			} );

			const td = cell( size, 'size' );
			td.className = 'design-fonts-size';

			const unit = dc.createElement('span');
			unit.className = 'design-fonts-unit';
			unit.textContent = Nino.content.getText('/_admin/design/fontsize/unit');
			td.appendChild( unit );

			return row;
		},

		/**
		 *	A family as a select names it: a system stack in the words of the
		 *	workbench, a webface by its own name
		 *
		 *	@param		{string}	role
		 *	@param		{string}	slug				'' is the system's own, for the code the monospace
		 *
		 *	@return		{string}
		 */
		_familyLabel : function( role, slug ) {

			if( slug === '' )
				return Nino.content.getText('/_admin/design/family/'+ ( role === 'code' ? 'default-code' : 'default' ) );

			const family = ( ( Nino.admin.design._data || {} ).families || {} )[slug] || {};

			return family.system === true ? Nino.content.getText('/_admin/design/family/'+ slug ) : String( family.name || slug );
		},

		/**
		 *	A size as a number, with the decimal sign of the workbench's language:
		 *	for the words of the summary. The field does not use it - a number field
		 *	takes and shows the point, whatever the language
		 *
		 *	@param		{string}	size				'1.6'
		 *
		 *	@return		{string}							'1,6'
		 */
		_sizeNumber : function( size ) {
			return String( size ).replace( '.', Nino.content.getText('/_admin/design/fontsize/decimal') );
		},

		/**
		 *	A size as the summary says it: rem, with the decimal sign of the
		 *	workbench's language
		 *
		 *	@param		{string}	size				'2.4'
		 *
		 *	@return		{string}							'2,4 rem'
		 */
		_sizeLabel : function( size ) {
			return Nino.admin.design._sizeNumber( size )+ ' '+ Nino.content.getText('/_admin/design/fontsize/unit');
		},

		/**
		 *	What was typed in a size, as the model keeps it: a number of rem held
		 *	inside the bounds of the role, rounded to three decimals and written
		 *	as short as it can be - what Fonts::size() answers to the same input.
		 *	'' is the default, and so is whatever is no number
		 *
		 *	@param		{string}	role
		 *	@param		{string}	typed				The value of the field
		 *
		 *	@return		{string}							'2.4', or '' for the default
		 */
		_clampSize : function( role, typed ) {

			const bounds = ( ( Nino.admin.design._data || {} ).bounds || {} )[role];
			const number = String( typed ).trim() === '' ? NaN : Number( typed );

			if( bounds === undefined || isFinite( number ) === false )
				return '';

			return String( Math.round( Math.max( bounds[0], Math.min( bounds[1], number ) ) * 1000 ) / 1000 );
		},

		/**
		 *	Change one thing of one role
		 *
		 *	@param		{string}		role
		 *	@param		{string}		key				family, weight, uppercase or size
		 *	@param		{*}					value
		 *
		 *	@return		void
		 */
		_setFont : function( role, key, value ) {

			Nino.admin.design._edit.fonts[role][key] = value;
			Nino.admin.design._preview();
		},

		/**
		 *	The knob tabs: the select the template of the tab is chosen in, at the
		 *	top of the card, and one row per knob the tab has under it, each at
		 *	less / as the template has it / more. A tab belongs to one template
		 *	and the knobs are the tab's own, so there is nothing to follow and no
		 *	way back to draw beside a row - the middle is the template as it is.
		 *
		 *	A knob the chosen template does not answer to is not hidden. It stands
		 *	where it always does, greyed out, with a line that says whose
		 *	decision that is: a row that comes and goes with the template would
		 *	be a screen nobody could learn
		 *
		 *	@param		{string}	tab				A key of KNOB_TABS
		 *
		 *	@return		{Element}
		 */
		_renderKnobs : function( tab ) {

			const data = Nino.admin.design._data;
			const row = Nino.admin.design._row( tab );

			const box = dc.createElement('div');
			box.id = 'design-knobs';
			box.className = 'nino-admin-card design-block';

			// The template these knobs are the knobs of, chosen where they are: the select is what names it
			box.appendChild( Nino.admin.design._partField( row ) );

			const answered = Nino.admin.design._answered( tab );

			( ( data.tabs || {} )[tab] || [] ).forEach( function( knob ) {
				box.appendChild( Nino.admin.design._knobRow( tab, knob, answered.indexOf( knob ) !== -1 ) );
			} );

			return box;
		},

		/**
		 *	What the summary and the rows say a knob stands at
		 *
		 *	@param		{string}	tab
		 *	@param		{string}	knob
		 *
		 *	@return		{string}					less, default or more
		 */
		_stepOf : function( tab, knob ) {
			const steps = ( Nino.admin.design._data || {} ).steps || [ 'less', 'default', 'more' ];
			return steps[ Math.max( 0, Math.min( steps.length - 1, ( ( Nino.admin.design._edit.knobs[tab] || {} )[knob] || 0 ) + 1 ) ) ];
		},

		/**
		 *	One of them: three steps, each in the words that knob has for it
		 *
		 *	@param		{string}		tab
		 *	@param		{string}		knob
		 *	@param		{boolean}		answered		Whether the chosen template declares it
		 *
		 *	@return		{Element}
		 */
		_knobRow : function( tab, knob, answered ) {

			const base = '/_admin/design/knob/'+ tab+ '/'+ knob;
			const name = Nino.content.getText( base+ '/label' );

			const field = dc.createElement('div');
			field.className = 'design-knob-row'+ ( answered === false ? ' design-knob-row--off' : '' );
			field.dataset.knob = knob;

			/*	The knob's own name and the terse note under it, both out of the
				panel's text files: a knob is one key in every locale. Where the
				template does not answer to it the note is the reason it is grey.
				An icon stands before them, for what the knob sets	*/
			const label = dc.createElement('span');
			label.className = 'design-knob-label';
			Nino.admin.design._labelContent( label, tab+ '/'+ knob, name,
				answered === true ? Nino.content.getText( base+ '/note' ) : Nino.admin.design._text( '/_admin/design/hint/unanswered', name ) );

			field.appendChild( label );

			/*	The workbench's own segmented control, for its look: filled
				where it is, flat where it is not. role=group and aria-pressed
				rather than a tablist, because three steps of one value are a
				choice and not two panels - buttonRow() defaults to exactly that
				flag for exactly this case */
			const group = dc.createElement('div');
			group.className = 'nino-admin-tabs design-knob-steps';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', name );

			const buttons = {};

			( Nino.admin.design._data.steps || [] ).forEach( function( step ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.dataset.step = step;
				// Each knob names its own three positions - "tight, normal, airy"
				// is not the same sentence as "sharp, normal, round"
				button.textContent = Nino.content.getText( base+ '/'+ step );
				button.title = button.textContent;
				button.disabled = answered === false;
				buttons[step] = button;
				group.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, answered === true ? Nino.admin.design._stepOf( tab, knob ) : '', function( step ) {
				Nino.admin.design._setStep( tab, knob, step );
			} );

			field.appendChild( group );

			return field;
		},

		/**
		 *	Move a knob
		 *
		 *	@param		{string}		tab				A key of KNOB_TABS
		 *	@param		{string}		knob			A knob of that tab
		 *	@param		{string}		step			less, default or more
		 *
		 *	@return		void
		 */
		_setStep : function( tab, knob, step ) {

			const steps = Nino.admin.design._data.steps || [];

			Nino.admin.design._edit.knobs[tab][knob] = Math.max( 0, steps.indexOf( step ) ) - 1;
			// A change is looked at where it shows: one target for it, which the jump
			// that follows the preview goes to
			Nino.admin.design._target = Nino.admin.design._anchorOf( tab, knob );
			Nino.admin.design._paintSummary();
			Nino.admin.design._preview();
		},

		/**
		 *	The element a change of one knob is looked at in: the id KNOB_ANCHORS
		 *	names for the knob, else the one it names for the rest of the tab's
		 *	knobs, else the anchor of the tab
		 *
		 *	@param		{string}		tab				A key of KNOB_TABS
		 *	@param		{string}		knob			A knob of that tab
		 *
		 *	@return		{string}							The id of an element of the specimen
		 */
		_anchorOf : function( tab, knob ) {

			const anchors = Nino.admin.design.KNOB_ANCHORS[tab] || {};

			return anchors[knob] || anchors['*'] || Nino.admin.design.ANCHORS[tab];
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
			wrap.className = 'nino-admin-card design-form-section';

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

			/*	The knobs under the colour the palette is solved from, opened by
				the same small heading a block of rows carries in the other half
				of the screen	*/
			wrap.appendChild( Nino.admin.design._sectionLabel(
				Nino.content.getText('/_admin/design/label/tuning'),
				Nino.content.getText('/_admin/design/hint/tuning') ) );

			// Drawn out of what design/list handed over, so a knob added in
			// Colours appears here without this file gaining a line
			const palette = ( Nino.admin.design._data || {} ).palette || {};

			Object.keys( palette ).forEach( function( key ) {

				// The mode is a choice between words, not a position on a scale:
				// it has a select of its own, after the knobs
				if( key === 'mode' )
					return;

				/*	Harmony *is* the second colour: it is where that colour comes
					from when nobody names one. Two rows a line apart - one asking
					for a hex, one asking where to derive it - read as two
					independent questions, and the second answer silently beat the
					first. One row, the four automatic positions and the swatch
					that overrides them, says which it is	*/
				wrap.appendChild( Nino.admin.design._colourKnobRow( key, palette[key],
					key === 'harmony' ? Nino.admin.design._secondField() : null ) );
			} );

			if( palette.mode )
				wrap.appendChild( Nino.admin.design._modeField( palette.mode ) );

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
			label.className = 'design-colour-label design-knob-label';
			label.setAttribute( 'for', 'design-colour-primary' );
			Nino.admin.design._labelContent( label, 'colours/primary', Nino.content.getText('/_admin/design/label/primary'),
				Nino.content.getText('/_admin/design/hint/primary') );

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
			Nino.admin.design._labelContent( label, 'colours/'+ key, Nino.content.getText('/_admin/design/colour/'+ key+ '/label'),
				Nino.content.getText('/_admin/design/colour/'+ key+ '/note') );

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

		/**
		 *	The mode of the palette: follow the visitor's setting, or be light, or
		 *	be dark - one select with the sentence about what it does to the
		 *	mode switch under it
		 *
		 *	@param		{Object}	meta		The mode entry of Colours::choices()
		 *
		 *	@return		{HTMLElement}
		 */
		_modeField : function( meta ) {

			const edit = Nino.admin.design._edit;
			const mode = dc.createElement('div');
			mode.className = 'design-colour-mode';

			mode.appendChild( Nino.admin.design._sectionLabel(
				Nino.content.getText('/_admin/design/colour/mode/label'),
				Nino.content.getText('/_admin/design/colour/mode/note'),
				Nino.admin.design._icon('colours/mode') ) );

			const field = Nino.admin.design._select(
				'design-colour-mode', '',
				( meta.steps || [] ).map( function( name ) {
					return { value : name, label : Nino.content.getText('/_admin/design/colour/mode/'+ name ) };
				} ), String( ( edit.colours || {} ).mode || meta['default'] ),
				function( value ) {
					Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
					Nino.admin.design._edit.colours.mode = value;
					Nino.admin.design._preview();
				} );

			field.querySelector('select').setAttribute( 'aria-label', Nino.content.getText('/_admin/design/colour/mode/label') );
			mode.appendChild( field );

			return mode;
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
		 *	Whether the selection on screen is not the stored one - which is
		 *	what the way back is shown for, and what the shell asks about
		 *	before it would lose the selection
		 *
		 *	@return		{boolean}
		 */
		_isDirty : function() {

			const data = Nino.admin.design._data;

			return data !== null && Nino.admin.design._edit !== null
				&& Nino.admin.design._canonical( Nino.admin.design._edit ) !== Nino.admin.design._canonical( Nino.admin.design._selection( data ) );
		},

		/**
		 *	Show or hide the way back, from whether there is one
		 *
		 *	@return		void
		 */
		_refreshDirty : function() {

			const button = dc.getElementById('design-reset');

			if( button === null || Nino.admin.design._data === null )
				return;

			button.hidden = Nino.admin.design._isDirty() === false;
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

		/**
		 *	The files Design writes that it did not write itself, as design/list
		 *	answered them: the delivered stylesheet or template, or one somebody
		 *	edited. Both are somebody else's as far as an apply is concerned
		 *
		 *	@return		{Array}						Virtual paths
		 */
		_foreign : function() {
			return ( ( Nino.admin.design._data || {} ).files || [] ).filter( function( file ) {
				return file.exists === true && file.state !== 'ours';
			} ).map( function( file ) { return file.target } );
		},

		/**
		 *	Which of the four things the files Design writes currently are, as the
		 *	last row of the summary under the frame says it in one word. Foreign is
		 *	any of the three files - the stylesheet and the two frames - not being
		 *	Design's, since a header somebody edited is not an up to date design
		 *	either
		 *
		 *	@return		{string}					'current', 'drifted', 'missing' or 'foreign'
		 */
		_stateKey : function() {

			const data = Nino.admin.design._data;

			if( data === null )
				return 'current';

			if( Nino.admin.design._foreign().length > 0 )
				return 'foreign';

			if( data.exists === false || data.compiled === '' )
				return 'missing';

			return data.current === false ? 'drifted' : 'current';
		},

		/**
		 *	The other half of the screen: the selection, as one page.
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

			/*	One stack inside the pane, so the pane owns the surface and the
				padding and this owns the rhythm between heading, toolbar, frame
				and summary	*/
			const sticky = dc.createElement('div');
			sticky.className = 'design-preview-sticky';
			box.appendChild( sticky );

			/*	The head of the preview is one row: the heading on one side, the bar of
				the frame's controls on the other. Where the pane has no room for both
				the bar goes under the heading - nothing is fixed here, the stylesheet's
				flex-wrap does it	*/
			const head = dc.createElement('div');
			head.className = 'design-preview-head';
			sticky.appendChild( head );

			/*	An eyebrow over the title, and the line the preview says things
				in beside them: what is in the frame is this selection, not the
				site, and the eyebrow is where that fits without a sentence	*/
			const heading = dc.createElement('div');
			heading.className = 'design-preview-heading';

			const copy = dc.createElement('div');
			copy.appendChild( Nino.admin.design._eyebrow( Nino.content.getText('/_admin/design/preview/eyebrow') ) );

			const title = dc.createElement('strong');
			title.textContent = Nino.content.getText('/_admin/design/label/preview');
			copy.appendChild( title );
			heading.appendChild( copy );

			head.appendChild( heading );

			const bar = dc.createElement('div');
			bar.className = 'design-preview-bar';

			bar.appendChild( Nino.admin.design._renderViewports() );
			bar.appendChild( Nino.admin.design._renderFollow() );

			/*	The reload, an icon button like the three devices: its words are
				its title, so the bar stays narrow enough to stand beside the
				heading in a pane of 555px - the preview's column at a window of
				1280px - and a sentence on a button is one the row has no room for	*/
			const reload = dc.createElement('button');
			reload.type = 'button';
			reload.id = 'design-reload';
			reload.className = 'design-viewport design-reload';
			reload.title = Nino.content.getText('/_admin/design/label/reload');
			reload.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/reload') );
			reload.appendChild( Nino.admin.design._svg('refresh-cw') );
			reload.addEventListener( 'click', function() {
				// From scratch: the one control for "the library changed under
				// me", which is what writing a set looks like from here
				Nino.admin.design._frames = null;
				Nino.admin.design._preview( 0 );
			} );
			bar.appendChild( reload );

			head.appendChild( bar );

			const stage = dc.createElement('div');
			stage.className = 'design-stage';

			const frame = dc.createElement('iframe');
			frame.id = 'design-frame';
			frame.title = Nino.content.getText('/_admin/design/label/preview');
			/*	allow-scripts, because half of what a header set is only happens
				when it scrolls, and the cover heights are set in js. Plus
				allow-same-origin, or the frame gets an opaque origin and the
				webfaces stop loading with it - a design shown in the wrong
				typeface is worse than no preview - and the panel could not
				scroll the page to the open tab's anchor. What stays denied is
				what a preview has no business doing: submitting the specimen's
				form, navigating the workbench away under itself, opening
				windows, starting downloads */
			frame.setAttribute( 'sandbox', 'allow-scripts allow-same-origin' );
			stage.appendChild( frame );

			/*	The preview's own line, over the frame's top corner rather than
				in the heading or under the frame: it says that a preview is
				being built, or why there is none, where the eyes are - and it
				takes no room of its own, so the head keeps its one row and the
				frame stays where it is while the line comes and goes	*/
			const msg = dc.createElement('p');
			msg.id = 'design-preview-msg';
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'aria-live', 'polite' );
			stage.appendChild( msg );
			sticky.appendChild( stage );

			// What the frame shows, in words, under the frame itself
			const summary = dc.createElement('div');
			summary.id = 'design-summary';
			summary.className = 'design-summary';
			sticky.appendChild( summary );

			Nino.admin.design._paintSummary( summary );

			return box;
		},

		/**
		 *	The viewport switch of the preview, the way the Builder's preview has
		 *	it: a button for each of the widths the frame can be looked at in, an
		 *	icon on it and the name of the width as its title, one of them pressed
		 *	(Nino.adminUi.buttonRow() keeps that). There is no button for all of
		 *	them at once: that is a view of the Builder's and means nothing in a frame
		 *
		 *	@return		{Element}
		 */
		_renderViewports : function() {

			const group = dc.createElement('div');
			group.className = 'design-viewports';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/label/viewports') );

			const buttons = {};
			let current = '';

			Nino.admin.design.WIDTHS.forEach( function( width ) {

				const name = Nino.content.getText('/_admin/design/width/'+ width.key );

				const button = dc.createElement('button');
				button.type = 'button';
				button.id = 'design-viewport-'+ width.key;
				button.className = 'design-viewport';
				// An icon alone has no name of its own
				button.title = name;
				button.setAttribute( 'aria-label', name );

				const icon = Nino.admin.design._svg( width.icon );

				if( icon !== null )
					button.appendChild( icon );

				if( width.px === Nino.admin.design._width )
					current = width.key;

				buttons[width.key] = button;
				group.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, current, function( key ) {
				Nino.admin.design._width = Nino.admin.design.WIDTHS.filter( function( width ) { return width.key === key } )[0].px;
				Nino.admin.design._fit();
			} );

			return group;
		},

		/**
		 *	The switch that lets the frame follow. On, the page in it jumps to
		 *	where a tab is opened and where a change is looked at; off, it stays
		 *	where it is - and a page that is built anew stays at its top. The
		 *	workbench draws the switch; what the panel keeps of it is the flag
		 *	_follow, for the visit and no longer: it is a convenience, and nothing
		 *	a project should have to store
		 *
		 *	@return		{Element}
		 */
		_renderFollow : function() {

			const field = Nino.adminUi.switchField( {
				key 		: 'design-follow',
				label 	: Nino.content.getText('/_admin/design/label/follow'),
				checked	: Nino.admin.design._follow,
			} );

			field.classList.add('design-preview-follow');

			const input = field.querySelector('input');
			input.id = 'design-follow';
			input.addEventListener( 'change', function() {

				Nino.admin.design._follow = input.checked;

				// Switched on it goes where it would have been all along, once
				if( input.checked === true )
					Nino.admin.design._jump();
			} );

			return field;
		},

		/**
		 *	The summary under the frame: label left, value right, one row per
		 *	answer the open tab currently gives.
		 *
		 *	A preview shows what a design looks like and says nothing about
		 *	which selection produced it - two templates a step apart are a
		 *	picture somebody has to compare from memory. The list is the other
		 *	half of the answer, and it is what makes the frame readable: every
		 *	control of the open tab is in it, in the interface language, as the
		 *	value that will compile
		 *
		 *	@param		{Element}		[list]		The list, for one that is built and not mounted yet
		 *
		 *	@return		void
		 */
		_paintSummary : function( list ) {

			list = list || dc.getElementById('design-summary');

			if( list === null || Nino.admin.design._data === null || Nino.admin.design._edit === null )
				return;

			list.innerHTML = '';

			Nino.admin.design._summaryRows().forEach( function( entry ) {

				const row = dc.createElement('div');
				row.className = 'design-summary-row';

				const label = dc.createElement('span');
				label.textContent = entry[0];
				row.appendChild( label );

				const value = dc.createElement('strong');
				value.textContent = entry[1];
				row.appendChild( value );

				list.appendChild( row );
			} );
		},

		/**
		 *	What the summary says, as [ label, value ] pairs.
		 *
		 *	The choices of the tab that is open, because that is the tab the
		 *	controls beside it are about - and the file's state last, which is
		 *	true under every tab
		 *
		 *	@return		{Array}
		 */
		_summaryRows : function() {

			const data = Nino.admin.design._data;
			const edit = Nino.admin.design._edit;
			const tab = Nino.admin.design._tab;
			const rows = [];

			if( tab === 'global' ) {

				( data.parts || [] ).forEach( function( row ) {
					rows.push( [
						Nino.content.getText('/_admin/design/part/'+ row.part ),
						Nino.admin.design._setLabel( edit.parts[row.part], ( row.catalogue || {} )[ edit.parts[row.part] ] ),
					] );
				} );

				rows.push( [ Nino.content.getText('/_admin/design/label/size'), Nino.content.getText('/_admin/design/size/'+ edit.size ) ] );
				rows.push( [ Nino.content.getText('/_admin/design/label/pagewidth'),
					Nino.content.getText('/_admin/design/pagewidth/'+ Nino.admin.design.PAGE_WIDTHS[String( edit.width )] ) ] );

				// Nothing chosen is what Nino.css does by itself, and says so
				const motion = edit.motion || {};
				rows.push( [ Nino.content.getText('/_admin/design/label/motion'), motion.effect
					? [ Nino.content.getText('/_admin/design/motion/effect/'+ motion.effect ) ]
						.concat( [ 'strength', 'speed' ].filter( function( key ) { return motion[key] } ).map( function( key ) {
							return Nino.content.getText('/_admin/design/motion/'+ key+ '/'+ motion[key] );
						} ) ).join(', ')
					: Nino.content.getText('/_admin/design/motion/default') ] );
			}
			else if( tab === 'fonts' ) {

				( data.roles || [] ).forEach( function( role ) {
					const chosen = edit.fonts[role] || {};
					rows.push( [
						Nino.content.getText('/_admin/design/font/role/'+ role ),
						[
							Nino.admin.design._familyLabel( role, chosen.family || '' ),
							Nino.content.getText('/_admin/design/weight/'+ ( chosen.weight || 'normal' ) ),
						].concat( chosen.uppercase === true ? [ Nino.content.getText('/_admin/design/font/head/uppercase') ] : [] )
							.concat( [ chosen.size ? Nino.admin.design._sizeLabel( chosen.size ) : Nino.admin.design._text( '/_admin/design/fontsize/default',
								Nino.admin.design._sizeLabel( ( data.inherited || {} )[role] ) ) ] ).join(', '),
					] );
				} );
			}
			else if( tab === 'colours' ) {

				const palette = data.palette || {};
				const colours = edit.colours || {};
				const second 	= String( colours.secondary || '' );

				/*	One position per knob: the one that was chosen, else the one
					the knob publishes as its own default - which is what the row
					on the left lights up and what the solver reads	*/
				const position = function( key ) {
					return String( colours[key] || ( palette[key] || {} )['default'] || '1' );
				};

				rows.push( [ Nino.content.getText('/_admin/design/label/primary'), String( colours.primary || '' ) ] );

				/*	The second colour is a hex when somebody picked one and the
					place on the wheel when nobody did - which is exactly what the
					row it belongs to offers, and what compiles either way	*/
				rows.push( [ Nino.content.getText('/_admin/design/label/secondary'), second !== ''
					? second
					: Nino.content.getText('/_admin/design/colour/harmony/'+ position('harmony') ) ] );

				Object.keys( palette ).forEach( function( key ) {

					if( key === 'harmony' )
						return;

					rows.push( key === 'mode'
						? [ Nino.content.getText('/_admin/design/colour/mode/label'), Nino.content.getText('/_admin/design/colour/mode/'+ String( colours.mode || palette.mode['default'] ) ) ]
						: [ Nino.content.getText('/_admin/design/colour/'+ key+ '/label'), Nino.content.getText('/_admin/design/colour/'+ key+ '/'+ position( key ) ) ] );
				} );
			}
			else {

				const row = Nino.admin.design._row( tab );
				const answered = Nino.admin.design._answered( tab );

				rows.push( [ Nino.content.getText('/_admin/design/part/'+ tab ),
					Nino.admin.design._setLabel( edit.parts[tab], ( row.catalogue || {} )[ edit.parts[tab] ] ) ] );

				// The value that will compile, which is what the rows on the left
				// show too. A knob the template does not answer to compiles
				// nothing, and is not a choice
				( ( data.tabs || {} )[tab] || [] ).forEach( function( knob ) {

					if( answered.indexOf( knob ) === -1 )
						return;

					rows.push( [
						Nino.content.getText('/_admin/design/knob/'+ tab+ '/'+ knob+ '/label'),
						Nino.content.getText('/_admin/design/knob/'+ tab+ '/'+ knob+ '/'+ Nino.admin.design._stepOf( tab, knob ) ),
					] );
				} );
			}

			rows.push( [
				Nino.content.getText('/_admin/design/label/state'),
				Nino.content.getText('/_admin/design/state/short/'+ Nino.admin.design._stateKey() ),
			] );

			return rows;
		},

		/**
		 *	Ask for a preview, once the clicking has stopped. Every control on
		 *	the screen calls this, and a person trying three templates in a row
		 *	wants the third one rendered, not all three
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
			Nino.admin.design._paintSummary();
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

			const frames = edit.parts.header + '|' + edit.parts.footer;
			const full 	 = Nino.admin.design._frames !== frames;

			/*	The line is one line, whatever it says - the head the chip stands in does
				not move with it - and shows as much of it as the room the bar leaves has,
				so the whole of it is its title as well	*/
			if( msg !== null ) {
				msg.className = 'nino-admin-hint';
				msg.textContent = Nino.content.getText('/_admin/design/msg/previewing');
				msg.title = msg.textContent;
			}

			Nino.admin.design._apiCall( 'preview', Object.assign( {}, edit, { full : full } ), function( status, response ) {

				if( status !== 200 || response === null ) {
					if( msg !== null ) {
						msg.className = 'nino-admin-error';
						msg.textContent = Nino.admin.design._errorText( status, response, '/_admin/design/error/preview' );
						msg.title = msg.textContent;
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
				else {
					// A change was made on this tab, and where it can be seen - the
					// tab's anchor, or the place of the knob that was moved - is where
					// the page goes after every one
					Nino.admin.design._jump();

					if( Nino.admin.design._replays === true )
						Nino.admin.design._replay();
				}

				Nino.admin.design._replays = false;

				Nino.admin.design._fit();
				Nino.admin.design._absorb( response );

				if( msg === null )
					return;

				const notes = response.notes || [];
				msg.className = notes.length === 0 ? 'nino-admin-hint' : 'nino-admin-error';
				msg.textContent = notes.join(' ');
				msg.title = msg.textContent;
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
		 *	Play the animation of the page again in the frame.
		 *
		 *	A viewport animation plays when an element comes into view, so the ones
		 *	that stand in view already - the hero, on the Global tab where the
		 *	animation is chosen - would show nothing of a choice made while they are
		 *	there. Each of them goes back to where it starts, without a transition,
		 *	and is let in again with the one the page has now. The classes are put
		 *	back as they were, which is what the page's own script believes they are
		 *
		 *	@return		void
		 */
		_replay : function() {

			const frame = dc.getElementById('design-frame');

			if( frame === null )
				return;

			try {

				const doc = frame.contentDocument;

				if( doc === null || doc.body === null )
					return;

				const shown = Array.prototype.slice.call( doc.querySelectorAll('.nino-vpa--visible') );
				const kept = shown.map( function( el ) { return el.style.transition } );

				shown.forEach( function( el ) {
					el.style.transition = 'none';
					el.classList.remove( 'nino-vpa--visible', 'nino-vpa--visible-once' );
				} );

				// Measured, so that the start is laid out before it is left
				void doc.body.offsetHeight;

				shown.forEach( function( el, i ) {
					el.style.transition = kept[i];
					el.classList.add( 'nino-vpa--visible', 'nino-vpa--visible-once' );
				} );
			}
			catch( e ) {
				// A frame that cannot be reached has nothing to play
			}
		},

		/**
		 *	Scroll the page in the frame to where the open tab is looked at, smoothly.
		 *
		 *	The specimen is one page with an element for each tab (ids global,
		 *	type, content, buttons, components and colours - see ANCHORS), and
		 *	the frame is the page of this very origin: the panel scrolls the
		 *	frame's own window by hand, which needs neither a message to the page
		 *	nor a hash it would have to read. Done after a tab is opened and after
		 *	every change, when the answer to the change is in the frame.
		 *
		 *	Two maps say where. Opening a tab goes to the anchor of the tab
		 *	(ANCHORS). A change of one knob goes to the place that knob is looked
		 *	at in (KNOB_ANCHORS): on Content the hero's two knobs to the hero, which
		 *	is also the anchor of the tab (#content), and every other knob to the
		 *	Cards section (#cards); a tab or a knob with no entry there keeps the
		 *	anchor of the tab. The place of a change is _target, one for each
		 *	change - it is put back to the tab's own when a tab is opened and when a
		 *	template is chosen, and it is what is jumped to until then.
		 *
		 *	With the follow switch off (_follow) the frame never scrolls by itself:
		 *	not after a change, not after a tab is opened, and a page that was built
		 *	anew stays at its top. Switching it on jumps once, to where it would be
		 *
		 *	@param		{boolean}	[instant]		Without the smooth scroll - after a
		 *															rebuild there is nothing to scroll away from
		 *	@param		{boolean}	[settled]		The webfonts are back; set by the call
		 *															that waited for them
		 *
		 *	@return		void
		 */
		_jump : function( instant, settled ) {

			const frame = dc.getElementById('design-frame');

			if( frame === null || Nino.admin.design._follow === false )
				return;

			try {

				const doc = frame.contentDocument;

				if( doc === null || doc.body === null )
					return;

				const tab = Nino.admin.design._tab;
				const target = doc.getElementById( Nino.admin.design._target || Nino.admin.design.ANCHORS[tab] );
				const view = doc.defaultView;

				if( target === null || view === null )
					return;

				/*	A sheet that was swapped declares its webfaces again, and the
					page lays out with the fallback faces until they are back - an
					anchor measured there is not where it stands a moment later, and
					the scroll would end that much too far. Measuring forces the
					layout, which is what starts the loading, so the state is asked
					for after it; the jump is made again when the faces are in	*/
				void doc.body.offsetHeight;

				if( settled !== true && doc.fonts && doc.fonts.status === 'loading' )
					return doc.fonts.ready.then( function() { Nino.admin.design._jump( instant, true ) } );

				/*	The frame's own window, scrolled by hand - not
					scrollIntoView(). That one walks every scrollable ancestor of
					the element, and an element inside a same-origin iframe has
					the workbench's own pane among them: the frame jumped to the
					anchor *and* the column beside it slid away under the controls
					that had just been used. This moves one scroller, which is the
					one the switch is about	*/

				/*	The header is fixed, and a page keeps room for it: main's padding at
					the top, which each header template sets for itself. An anchor stands
					that far from the top, where the first section of a page does, and not
					under the bar. The bar's own height is the wrong thing to ask for - it
					folds away when the page is scrolled down and is back when it is
					scrolled up, so the answer would be the state the last scroll left it in */
				const main = doc.querySelector('main');
				const clear = main === null ? 0 : parseFloat( view.getComputedStyle( main ).paddingTop ) || 0;

				// Global is the top of the page, header included - the anchor
				// stands right below it
				const top = tab === 'global' ? 0 : target.getBoundingClientRect().top
					+ ( view.scrollY || doc.documentElement.scrollTop || 0 ) - clear;

				// Smooth unless there is nothing to scroll away from - and not for a visitor who asked for less motion
				const calm = typeof view.matchMedia === 'function' && view.matchMedia('(prefers-reduced-motion: reduce)').matches === true;

				view.scrollTo( {
					top 			: Math.max( 0, Math.round( top ) ),
					behavior	: instant === true || calm === true ? 'instant' : 'smooth',
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

			// Asked afresh every time: the width is a choice, and scaleFrame
			// closes over the one it was given
			Nino.adminUi.scaleFrame( frame, frame.parentNode, Nino.admin.design._width );
		},

		/**
		 *	A labelled select that reports its own changes
		 *
		 *	@param		{string}		id
		 *	@param		{string}		label			'' draws none
		 *	@param		{Array}			options		{ value, label }
		 *	@param		{string}		current
		 *	@param		{Function}	onChange
		 *	@param		{string}		[note]		What the control is for, as the line under it.
		 *															It used to be the title attribute, because three
		 *															controls in a column with a sentence under each
		 *															were more of the screen than the controls were -
		 *															in the field grid they stand side by side, and a
		 *															hint a hand has to hover for is one nobody reads
		 *
		 *	@return		{Element}
		 */
		_select : function( id, label, options, current, onChange, note ) {

			/*	A label over the control and a small under it. The name is a
				<label for>: the control is a real one with an id, and a label
				that names it is what a screen reader reads out	*/
			const field = dc.createElement('div');
			field.className = 'design-field';

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

			if( note ) {
				const small = dc.createElement('small');
				small.textContent = note;
				field.appendChild( small );
			}

			return field;
		},

		/**
		 *	Store the selection, and - when asked - apply it afterwards.
		 *	Two steps rather than one: a draft is not a website, and the
		 *	screen stays honest about which of the two just happened.
		 *
		 *	Applying asks first, every time. design/plan says what would happen
		 *	to each of the three files, the confirmation lists it, and only a
		 *	yes sends the apply - with `force` where a file is not Design's,
		 *	which the confirmation has just said it is about to replace. The
		 *	same question is what gets a project out of a stylesheet that is
		 *	Design's beside a header that is not, which used to be refused
		 *	with nothing on screen to say yes to
		 *
		 *	@param		{boolean}		apply
		 *	@param		{Element}		btn
		 *	@param		{Element}		msg
		 *	@param		{Function}	[done]			Told whether the draft was saved - the shell's Save asks
		 *
		 *	@return		void
		 */
		_save : function( apply, btn, msg, done ) {

			const edit = Nino.admin.design._edit;
			const finish = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			// A second click while one is on its way
			if( btn.disabled === true )
				return finish( false );

			btn.disabled = true;
			msg.className = '';
			msg.textContent = '';

			Nino.admin.design._apiCall( 'save', edit, function( status, response ) {

				if( status !== 200 || response === null ) {
					btn.disabled = false;
					msg.className = 'nino-admin-error';
					msg.textContent = Nino.admin.design._errorText( status, response, '/_admin/design/error/save' );
					return finish( false );
				}

				// The draft is stored either way; what asked for it may go on
				// while the screen is drawn from it again
				finish( true );

				if( apply === false ) {
					btn.disabled = false;
					return Nino.admin.design.init( function() {
						const back = dc.getElementById('design-msg');
						if( back !== null )
							back.textContent = Nino.content.getText('/_admin/design/msg/saved');
					} );
				}

				Nino.admin.design._apiCall( 'plan', {}, function( planStatus, planned ) {

					if( planStatus !== 200 || planned === null ) {
						btn.disabled = false;
						msg.className = 'nino-admin-error';
						msg.textContent = Nino.admin.design._errorText( planStatus, planned, '/_admin/design/error/apply' );
						return;
					}

					const files = planned.files || [];

					// The draft is saved either way; only the website waits
					if( wn.confirm( Nino.admin.design._confirmText( files ) ) === false ) {
						btn.disabled = false;
						return Nino.admin.design.init( function() {
							const back = dc.getElementById('design-msg');
							if( back !== null )
								back.textContent = Nino.content.getText('/_admin/design/msg/cancelled');
						} );
					}

					const force = files.some( function( file ) { return file.exists === true && file.state !== 'ours' } );

					Nino.admin.design._apiCall( 'apply', { force : force }, function( applyStatus, applied ) {

						btn.disabled = false;

						if( applyStatus !== 200 ) {
							msg.className = 'nino-admin-error';
							msg.textContent = Nino.admin.design._errorText( applyStatus, applied, '/_admin/design/error/apply' );
							return;
						}

						const lost = [];
						files.forEach( function( file ) {
							( file.lost || [] ).forEach( function( token ) { lost.push( token ) } );
						} );

						Nino.admin.design.init( function() {
							const back = dc.getElementById('design-msg');

							if( back === null )
								return;

							const said = [ Nino.content.getText('/_admin/design/msg/applied') ];

							if( force === true )
								said.push( Nino.content.getText('/_admin/design/msg/takenover') );

							if( lost.length > 0 )
								said.push( Nino.admin.design._text( '/_admin/design/msg/lost', lost.join(', ') ) );

							back.textContent = said.join(' ');
						} );
					} );
				} );
			} );
		},

		/**
		 *	The question before an apply, one line per fact: which files are
		 *	rewritten, which of them Design did not write, which shortcodes the
		 *	new frames lose, and that the present state is kept - with the date
		 *	of the version that keeping it replaces. Built here from the
		 *	fragments in the text files; none of them carries a line break, and
		 *	the lines are joined with one
		 *
		 *	@param		{Array}		files					design/plan's answer
		 *
		 *	@return		{string}
		 */
		_confirmText : function( files ) {

			const lines = [ Nino.admin.design._text( '/_admin/design/confirm/apply',
				files.map( function( file ) { return Nino.admin.design._name( file.target ) } ).join(', ') ) ];

			const foreign = files.filter( function( file ) { return file.exists === true && file.state !== 'ours' } );

			if( foreign.length > 0 )
				lines.push( Nino.admin.design._text( '/_admin/design/confirm/foreign',
					foreign.map( function( file ) { return Nino.admin.design._name( file.target ) } ).join(', ') ) );

			files.forEach( function( file ) {
				( file.lost || [] ).forEach( function( token ) {
					lines.push( Nino.admin.design._text( '/_admin/design/confirm/lost', token ) );
				} );
			} );

			// Nothing is kept where nothing would change, and so nothing is replaced
			if( files.some( function( file ) { return file.exists === true && file.changes === true } ) ) {

				lines.push( Nino.content.getText('/_admin/design/confirm/copy') );

				const previous = Nino.admin.design._data.previous;

				if( previous )
					lines.push( Nino.admin.design._text( '/_admin/design/confirm/replaces', Nino.admin.design._when( previous.at ) ) );
			}

			return lines.join('\n');
		},

		/**
		 *	Put the previous version back, after asking. The answer is the new
		 *	state, so the panel is drawn again from it - and the button now
		 *	offers the version that was just replaced
		 *
		 *	@param		{Element}		btn
		 *
		 *	@return		void
		 */
		_restore : function( btn ) {

			if( wn.confirm( Nino.content.getText('/_admin/design/confirm/restore') ) === false )
				return;

			btn.disabled = true;

			Nino.admin.design._apiCall( 'restore', {}, function( status, response ) {

				btn.disabled = false;

				if( status !== 200 || response === null ) {
					const msg = dc.getElementById('design-msg');

					if( msg !== null ) {
						msg.className = 'nino-admin-error';
						msg.textContent = Nino.admin.design._errorText( status, response, '/_admin/design/error/restore' );
					}
					return;
				}

				Nino.admin.design.init( function() {
					const back = dc.getElementById('design-msg');
					if( back === null )
						return;

					// What the slot did not hold, or could not keep, is told
					// beside the answer: the site has changed either way
					const notes = response.notes || [];
					back.textContent = [ Nino.content.getText('/_admin/design/msg/restored') ].concat( notes ).join(' ');
					if( notes.length > 0 )
						back.className = 'nino-admin-hint';
				} );
			} );
		},
	};

	/*	The shell asks Save, Discard or Cancel before a log out or a language
		change would lose a selection nobody has stored, and a reload gets
		the browser's own question. Save stores the draft - applying it is its
		own question. A panel registers where this Nino has the registry and
		does without where it has not - panel scripts run on older
		workbenches too	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.register( 'design', {
			isDirty : function() { return Nino.admin.design._isDirty() },
			save : function( done ) {

				const btn = dc.getElementById('design-save');
				const msg = dc.getElementById('design-msg');

				if( btn === null || msg === null )
					return done( false );

				Nino.admin.design._save( false, btn, msg, done );
			},
			// Drawn again with the stored selection: if the exit then does not
			// leave the page, the controls show what the model holds
			discard : function() {

				if( Nino.admin.design._data === null )
					return;

				Nino.admin.design._edit = Nino.admin.design._selection( Nino.admin.design._data );
				Nino.admin.design._render();
			},
		} );

	Nino.events.bindCallback( 'ready', Nino.admin.design.init );

})(window, document, document.documentElement, document.body);
