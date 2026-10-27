/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Design					The feature's /_admin panel, "Design": seven tabs over one setup,
 *													in two axes - the properties of the design, which are the tabs,
 *													and the objects they are set on, which are the blocks of five of
 *													them. Theme, the first, keeps the whole setup under a name with a
 *													css of its own, in three screens: Load, the list of the themes to
 *													load, duplicate and delete, with the field that takes a theme file
 *													in; Properties, the loaded theme with its name and its
 *													description, saved and exported from the screen, and under it the
 *													row that creates a theme out of the screen; and Custom CSS, the
 *													field of the css, which every input mirrors into the preview.
 *													Templates, Type, Surfaces, Colours and Motion draw a block for
 *													each object that has a control on the tab, in one fixed order -
 *													the page, the header, the footer, the content, a section, a card,
 *													the buttons, the fields and tables - the blocks of an accordion,
 *													one open: the head of a closed one names, small and muted, the
 *													controls that stand elsewhere than at the defaults, and on
 *													Templates the template that is chosen. A block of Templates has
 *													the choice of the set of its part - the header, the footer, the
 *													content, the buttons or the components - with its name, its
 *													description and the word of the one chosen; one of Type the root
 *													size and the five font roles under the page and the sizes of the
 *													titles under the others; one of Surfaces the width, the whitespace
 *													and the two radii under the page and the space, the corners and
 *													the lines under the others, a strip of five positions or of three
 *													words for each knob, greyed out where the chosen template does not
 *													answer to it; Colours has the two colours, the five knobs and the
 *													mode the palette is solved from, and Motion the animation of the
 *													page, each one block of the page. Typefaces, the loader, is a tab
 *													of its own and keeps nothing in the setup - the families the
 *													project has installed, and the signed list of those it can
 *													install, which is read when its button is pressed: an install or a
 *													removal happens when it is pressed, and is no part of the draft.
 *													The compile turns all of it into assets/theme.css (see
 *													Modules\Design\Admin beside this file). Which tab, which object
 *													and which place in its row a control has is the table's (the
 *													`layout` design/list hands over, see Setup::LAYOUT): the script
 *													draws from it and decides nothing of the kind. A tab with screens
 *													draws their strip under the strip of the tabs, and the screen that
 *													is open under it.
 *													Beside the controls is one specimen page of the selection,
 *													under a head with the viewport switch, the switch that lets the
 *													frame follow and the reload; the frame fills the window down to
 *													the action bar and scrolls to the open tab's anchor, or to
 *													where a change of a control shows. The bar holds what the panel
 *													says - what a save or an apply did, or why not - and the state
 *													of the files in a word. Ships with the feature and is loaded
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
		/*	What reading the setup replaced or dropped, as the server told it: a note is a sentence, or an object - a
			code and the params of it, the way a refusal is answered (see _noteText()). The list over the open tab
			draws them, and a theme that is loaded or a draft that is reset gives them anew: they belong to what was read	*/
		_notes		: [],

		/*	The Theme tab. The theme the working copy was taken from or kept in, as the
			server describes it - slug, name, description, shipped, setup, css - against
			which the chip "changed" compares the screen, and null where it has none. The
			name and the description as they are typed on the tab, which a switch of the tab
			or of the screen keeps and which belong to no draft: the button of the theme saves
			them. The name typed into the field of the row that makes a theme, and whether an action
			of the tab is on its way with the count that tells which one is the latest, the
			way the loader holds its own	*/
		_active		: null,
		_themeText	: { name : '', description : '' },
		_themeNew		: '',
		_themeBusy	: false,
		_themeStamp	: 0,

		/*	The tabs that are cut into screens, and the screens of each in the order they are shown in: a strip of
			them under the strip of the tabs - the tabs themselves, and what stands on them, are the table's (see
			Setup::LAYOUT) - and under it the screen that is open, one at a time, drawn again whenever it is opened
			(see _renderScreens()). The screens are the panel's own: the Theme tab is the one that has them, its three.
			The loader, the Typefaces tab, is a tab of its own with none, and the blocks of the other tabs are no screens	*/
		SCREENS	: { theme : [ 'load', 'properties', 'css' ] },

		/*	The id of the element on the specimen page each tab scrolls the frame to when it is opened (see _jump()). The
			page is templates/preview-specimen.tpl, which carries the same ids: Theme, Templates, Surfaces and Motion are the
			top of the page - a theme is all of it, a header is looked at there, and so are the width and the whitespace of
			the page and its animation -, Typefaces and Type the typography block - a typeface shows where the type is -,
			Colours the grounds. The page has an id at its end, footer, which no tab has: a footer that is chosen is looked
			at there (see CONTROL_ANCHORS)	*/
		ANCHORS	: { theme : 'global', templates : 'global', typefaces : 'type', type : 'type', surfaces : 'global', colours : 'colours', motion : 'global' },

		/*	Where a change of one control is looked at, by the object it stands under (see Setup::OBJECTS): the page at the
			top of it (#global) - the root size and the roles of the fonts as much as the width, the corners and the
			animation -, a section in the first section of the page, which the hero is (#content), a card in the cards
			(#cards), the buttons in their block and the fields and tables in the form. A control with an entry of its own
			(CONTROL_ANCHORS) goes there, and one under an object with no entry to the anchor of its tab. The maps are told
			together in the docblock of _jump()	*/
		OBJECT_ANCHORS	: { page : 'global', section : 'content', card : 'cards', buttons : 'buttons', components : 'components' },

		/*	The controls that are looked at elsewhere than their object is, by the key the table has them under: the
			two variants, quiet and loud, which are sizes of the type of a section and are looked at where the specimen
			sets every title three times, in the typography block (#type); the five templates - a header at the top, a
			footer at the foot of the page and the others where their sets are shown -; and the eight controls of the
			palette, which are the page's and are looked at in the grounds (#colours), where the page's own anchor is the
			top of the page	*/
		CONTROL_ANCHORS	: { 'knobs/content/quiet' : 'type', 'knobs/content/loud' : 'type', 'parts/header' : 'global', 'parts/footer' : 'footer', 'parts/content' : 'content', 'parts/buttons' : 'buttons', 'parts/components' : 'components',
			'colours/primary' : 'colours', 'colours/secondary' : 'colours', 'colours/harmony' : 'colours', 'colours/temperature' : 'colours', 'colours/saturation' : 'colours', 'colours/contrast' : 'colours', 'colours/depth' : 'colours', 'colours/mode' : 'colours' },

		/*	How far from the middle a position of a knob can be, by the kind of the knob:
			a scale has the five positions -2 to +2, a choice the three -1 to +1 (see
			Setup::range()). The root size, the width, the whitespace and the two radii of the page are scales	*/
		RANGE		: { scale : 2, choice : 1 },

		/*	The word the label and the hint of a strip of the page stand under (/_admin/design/label/<word> and
			/_admin/design/hint/<word>), by the key the table has the strip under	*/
		PAGE_WORDS	: { size : 'size', width : 'pagewidth', whitespace : 'whitespace', radius : 'radius', 'radius-alt' : 'radius-alt' },

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
			// The rule under the title of a section
			'minus' : [
				[ 'path', { d : 'M5 12h14' } ],
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
			// How far the small and the big button stand from the normal one, and how wide the page is
			'chevrons-left-right' : [
				[ 'path', { d : 'm9 7-5 5 5 5' } ],
				[ 'path', { d : 'm15 7 5 5-5 5' } ],
			],
			'move-horizontal' : [
				[ 'path', { d : 'm18 8 4 4-4 4' } ],
				[ 'path', { d : 'M2 12h20' } ],
				[ 'path', { d : 'm6 8-4 4 4 4' } ],
			],
			// How much air the page has
			'move-vertical' : [
				[ 'path', { d : 'M12 2v20' } ],
				[ 'path', { d : 'm8 18 4 4 4-4' } ],
				[ 'path', { d : 'm8 6 4-4 4 4' } ],
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
			// The chevron of the head of a block, which is turned while the block is open: Lucide's chevron-down
			'chevron-down' : [
				[ 'path', { d : 'm6 9 6 6 6-6' } ],
			],
		},

		/*	Which icon a row opens with, by the part and the knob it sets - so that what
			is being set is felt before it is read: the size of a text, of a surface, a
			space, the corners, a line, a shadow. The knobs are the feature's own (see
			Setup::KNOBS), and so are the strips of the page, under page: the root size,
			the width, the whitespace and the radius, with the corners a section, a card
			and the pictures take by default; and the rows of the palette - the brand
			colour, the second colour, which is the row of Harmony, temperature,
			saturation, contrast, depth and the mode: all of them have one, so that every
			name of the Colours tab starts at the same left edge. A row of the loader,
			an installed family and an available one, opens with the text size as
			well. A row with no entry has no icon, and no stand-in shape takes its
			place	*/
		KNOB_ICONS	: {
			'page/size' : 'a-large-small',
			'page/width' : 'move-horizontal',
			'page/whitespace' : 'move-vertical',
			'page/radius' : 'square-round-corner',
			'page/radius-alt' : 'square-round-corner',
			'page/radii-section' : 'square-round-corner',
			'page/radii-card' : 'square-round-corner',
			'page/radii-image' : 'square-round-corner',
			'typefaces/family' : 'a-large-small',
			'content/hero-title' : 'a-large-small',
			'content/hero-text' : 'a-large-small',
			'content/quiet' : 'a-large-small',
			'content/loud' : 'a-large-small',
			'content/section-title' : 'a-large-small',
			'content/section-subtitle' : 'a-large-small',
			'content/section-space' : 'scaling',
			'content/section-line' : 'minus',
			'content/card-title' : 'a-large-small',
			'content/card-subtitle' : 'a-large-small',
			'content/card-space' : 'align-vertical-space-around',
			'content/border' : 'square',
			'content/shadow' : 'square-stack',
			'buttons/size' : 'a-large-small',
			'buttons/spread' : 'chevrons-left-right',
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
		// element the last change of a control is looked at in (see _anchorOf()). null is the
		// tab's own, which is where opening a tab puts it back to
		_target	: null,
		// The tab that is open - the Theme tab at the start of a visit: the first thing a person does is pick a theme
		_tab		: 'theme',
		// The screen that a tab with screens shows, by the tab; a tab with no entry shows its first. Kept
		// while the panel lives and no part of the selection: another screen changes nothing but the screen
		_group	: {},
		/*	The row that is open on a tab that draws its rows as an accordion (see _renderRows()), by the tab: the object it is the
			row of, or null once the person has closed it. A tab with no entry has its first row open. Kept while the panel lives, like
			the screen of a tab, and no part of the selection and nothing that is stored: another block changes nothing but the block	*/
		_open	: {},
		// The header and footer the frame was last built with. Everything else
		// is a stylesheet, and a stylesheet can be swapped inside it
		_frames	: null,
		// Whether the animation of the page was changed since the frame last played it
		_replays	: false,
		// Whether the window's resizing is listened to yet, and the timer that waits
		// for it to stop (see _fitLater())
		_bound	: false,
		_fitTimer	: null,

		// The categories of the typefaces the Available half is told apart by, as the chips
		// of its strip, and how many families a page of it holds
		FACE_CATEGORIES	: [ 'all', 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ],
		FACES_PER_PAGE	: 24,

		/*	The loader, the Typefaces tab. The last answer of design/webfonts/* - families (the list),
			fetched, url, key, builtin, installed, broken, uses, refusal, refusalKind - from
			which both halves are drawn; null until the first has come. Beside it what the
			Available half looks at, the counter that tells which of the requests asked is
			the latest, whether an action of the screen is on its way, and the faces the names
			have been set in: the FontFace of every family by the font-family it was added to
			the document under, and the observer that loads those of the rows that come into
			view	*/
		_faces			: null,
		_facesQuery		: '',
		_facesCategory	: 'all',
		_facesPage		: 0,
		_facesStamp		: 0,
		_facesBusy		: false,
		_facesLoaded	: {},
		_facesObserver	: null,

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
				Nino.admin.design._takeStored( response );
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
			if( Nino.admin.dirty.isDirty( [ 'design' ] ) === true )
				return;

			Nino.admin.design._render();
		},

		/**
		 *	One action of this panel, through the workbench's request helper - it
		 *	knows the project's directory and what to do when the page has
		 *	outlived its session
		 *
		 *	@param		{string}		endpoint
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		( status, parsed json or null )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.adminUi.api.call( 'design/'+ endpoint, payload, callback );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.adminUi.api.errorText( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	The posted shape, out of what design/list answered: every key of the
		 *	model the screen can change, as a copy of its own
		 *
		 *	@param		{Object}	data
		 *
		 *	@return		{Object}					{ parts, knobs, size, width, whitespace, radius, radius-alt, radii, motion, fonts, colours, theme, css }
		 */
		_selection : function( data ) {

			const parts = {};
			( data.parts || [] ).forEach( function( row ) { parts[row.part] = row.set } );

			const knobs = {};
			Object.keys( data.kinds || {} ).forEach( function( part ) {
				knobs[part] = Object.assign( {}, ( data.knobs || {} )[part] || {} );
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
				whitespace : data.whitespace,
				radius 	: data.radius,
				'radius-alt' : data['radius-alt'],
				radii 	: Object.assign( {}, data.radii || {} ),
				motion 	: Object.assign( {}, data.motion || {} ),
				fonts 	: fonts,
				colours	: Object.assign( {}, data.colours || {} ),
				theme 	: String( data.theme || '' ),
				css 		: String( data.css || '' ),
			};
		},

		/**
		 *	Make the stored selection the screen's: the working copy, the theme it names as the
		 *	server describes it and the name and the description of that theme as the fields of
		 *	the Theme tab have them. An action of that tab that is still on its way answers over
		 *	a state it does not know any more, so what it brings is let go of (see _themeStamp)
		 *
		 *	@param		{Object}	data			The answer of design/list
		 *
		 *	@return		void
		 */
		_takeStored : function( data ) {

			const design = Nino.admin.design;

			design._edit = design._selection( data );
			design._notes = Array.isArray( data.notes ) === true ? data.notes : [];
			design._active = data.active || null;
			design._themeText = data.active ? { name : data.active.name, description : data.active.description } : { name : '', description : '' };

			++design._themeStamp;
			design._themeBusy = false;
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
		 *	What a template is called in a list: its name, where its file gives one, and
		 *	the set - the slug of its file - where it does not. A name is a name and is
		 *	not translated, and a slug is what a theme's file and a setup call a
		 *	template by, so there is no need to say it a second time beside the name
		 *
		 *	@param		{string}	set
		 *	@param		{Object}	named			One entry of a part's catalogue
		 *
		 *	@return		{string}
		 */
		_setLabel : function( set, named ) {
			return named && named.name && named.name !== set ? named.name : set;
		},

		/**
		 *	The knobs the template chosen for a part answers to. The rest of the
		 *	part's knobs are drawn, greyed out
		 *
		 *	@param		{string}	part			A part with knobs: a key of design/list's `kinds`
		 *
		 *	@return		{Array}
		 */
		_answered : function( part ) {
			const row = Nino.admin.design._row( part );
			return ( ( row.catalogue || {} )[ Nino.admin.design._edit.parts[part] ] || {} ).knobs || [];
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
		 *	A template as the screen names it: its name where its file gives one, its slug where
		 *	it does not or where the library has no such template
		 *
		 *	@param		{string}	part			A key of the parts
		 *	@param		{string}	set
		 *
		 *	@return		{string}
		 */
		_setName : function( part, set ) {
			return Nino.admin.design._setLabel( set, ( ( Nino.admin.design._row( part ).catalogue ) || {} )[set] );
		},

		/**
		 *	A note of what reading the setup replaced, in the words of the workbench: a note that
		 *	carries a code is said the way a refusal is (its text is /_admin/error/<code>, the params
		 *	in it), with the part and the replacing template by the names the screen has for them;
		 *	a note that is a sentence is the sentence
		 *
		 *	@param		{(string|Object)}	note		A sentence, or { error, code, params }
		 *
		 *	@return		{string}
		 */
		_noteText : function( note ) {

			if( note === null || typeof note !== 'object' )
				return String( note );

			const design = Nino.admin.design;
			const params = Array.isArray( note.params ) === true ? note.params.slice() : [];

			// design_set_missing: the part, the set the file named, the set that stands in its place
			if( note.code === 'design_set_missing' && params.length === 3 ) {
				params[2] = design._setName( params[0], params[2] );
				params[0] = Nino.content.getText('/_admin/design/part/'+ params[0] ) || params[0];
			}

			return Nino.adminUi.api.errorText( 0, { code : note.code, params : params, error : note.error }, '' );
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
		 *	The small heading over a block of rows: the title, and nothing under it -
		 *	the rows say the rest
		 *
		 *	@param		{string}	title
		 *	@param		{?Element}	[icon]		Before the title, where the block has one (see _icon())
		 *
		 *	@return		{Element}
		 */
		_sectionLabel : function( title, icon ) {

			const heading = dc.createElement('div');
			heading.className = 'design-section-label';

			const strong = dc.createElement('strong');
			strong.textContent = title;

			if( icon )
				strong.insertBefore( icon, strong.firstChild );

			heading.appendChild( strong );

			return heading;
		},

		/**
		 *	The icon of a row, drawn from the shapes in ICONS (see _svg())
		 *
		 *	@param		{string}	key				'<part>/<knob>', as KNOB_ICONS has it
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
		 *	@param		{string}	key				'<part>/<knob>', for the icon
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
		 *	One strip: the row of a setting that is chosen by pressing one of a few
		 *	buttons - a knob of a template, a setting of the page - the root size, the width, the
		 *	whitespace, the two radii and the strength of each kind of surface -, a knob of the
		 *	palette. The label with the icon of the key, the name and the note under it (see
		 *	_labelContent()), and the workbench's own segmented
		 *	control for the options: filled where it is, flat where it is not, the
		 *	active one lit by Nino.adminUi.buttonRow().
		 *
		 *	role=group and aria-pressed rather than a tablist, because the buttons are
		 *	the values of one setting and not panels - buttonRow() defaults to exactly
		 *	that flag for exactly this case
		 *
		 *	@param		{string}		key					'<part>/<knob>', for the icon (see KNOB_ICONS)
		 *	@param		{string}		name
		 *	@param		{string}		note				The line under the name
		 *	@param		{Array}			options			{ value, label, title }, from the least to the most
		 *	@param		{*}					active			The value of the option that is lit; a value no option
		 *																	has lights none
		 *	@param		{Function}	onChange		Called with the value of the option that was pressed
		 *	@param		{boolean}		[disabled]	Greys the row out, switches the buttons off and lights none
		 *
		 *	@return		{Element}
		 */
		_stripRow : function( key, name, note, options, active, onChange, disabled ) {

			const field = dc.createElement('div');
			field.className = 'design-knob-row'+ ( disabled === true ? ' design-knob-row--off' : '' );

			const label = dc.createElement('span');
			label.className = 'design-knob-label';
			Nino.admin.design._labelContent( label, key, name, note );

			field.appendChild( label );

			const group = dc.createElement('div');
			group.className = 'nino-admin-tabs design-knob-steps';
			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', name );

			/*	The buttons are keyed by their place in the list, as the string of it: a
				key that is a number is kept in the order of the numbers, and the position
				-2 would come after +2	*/
			const buttons = {};
			let lit = '';

			options.forEach( function( option, index ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.dataset.value = String( option.value );
				button.textContent = option.label;
				button.title = option.title || option.label;
				button.disabled = disabled === true;
				buttons[String( index )] = button;
				group.appendChild( button );

				if( String( option.value ) === String( active ) )
					lit = String( index );
			} );

			Nino.adminUi.buttonRow( buttons, disabled === true ? '' : lit, function( index ) {
				onChange( options[parseInt( index, 10 )].value );
			} );

			field.appendChild( group );

			return field;
		},

		/**
		 *	The options of a strip of numbers: one for each value, written as its
		 *	distance from the middle - "−2", "−1", "0", "+1", "+2". The minus is U+2212, the
		 *	one a number is set with, and not the hyphen; the title is the label
		 *
		 *	@param		{Array}			values			From the least to the most
		 *	@param		{number}		middle			The value that is 0
		 *
		 *	@return		{Array}								{ value, label, title }
		 */
		_numbered : function( values, middle ) {

			return values.map( function( value ) {
				const distance = value - middle;
				const label = distance === 0 ? '0' : ( distance < 0 ? '\u2212' : '+' )+ String( Math.abs( distance ) );
				return { value : value, label : label, title : label };
			} );
		},

		/**
		 *	The positions of a kind of knob, from the least to the most: -2 to +2 for a
		 *	scale, -1 to +1 for a choice (see RANGE)
		 *
		 *	@param		{string}	kind				'scale' or 'choice'
		 *
		 *	@return		{Array}
		 */
		_positions : function( kind ) {

			const range = Nino.admin.design.RANGE[kind] || 0;
			const positions = [];

			for( let position = -range; position <= range; position++ )
				positions.push( position );

			return positions;
		},

		/**
		 *	The word a position is declared under in a stylesheet - the middle one of
		 *	the five design/list handed over is the position 0 (see Setup::word()). A
		 *	choice knob names its three positions by these words
		 *
		 *	@param		{number}	position
		 *
		 *	@return		{string}					least, less, default, more or most
		 */
		_word : function( position ) {

			const steps = ( Nino.admin.design._data || {} ).steps || [];

			return steps[ Math.max( 0, Math.min( steps.length - 1, position + Nino.admin.design.RANGE.scale ) ) ];
		},

		/**
		 *	The head of the pane the screen stands in: the row the shell
		 *	renders over every panel, with the panel's name in it (see
		 *	Nino.adminUi.panelHead()).
		 *
		 *	null where there is none - the script drawn somewhere other than its
		 *	pane - and the strip then opens the column of controls, where it
		 *	stood before there was a head to put it in
		 *
		 *	@return		{Object|null}			{ element, title, actions, tabs( strip ) }
		 */
		_head : function() {
			const wrap = dc.getElementById('design-form');
			return wrap !== null ? Nino.adminUi.panelHead( wrap ) : null;
		},

		/**
		 *	The whole screen: the open tab in one column, a preview of the lot in
		 *	the other.
		 *
		 *	A tab at a time rather than seven blocks at once, because seven blocks is
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

			/*	What the panel says stands in the action bar, where the eyes are when
				a button has just been pressed: the line that tells what a save, an
				apply, a restore or a reset did - or why it did not - and beside it, as
				a chip, what the files Design writes are, in a word. The bar takes a
				paragraph for its status line (see the rule for the children of
				.nino-admin-actionbar in the workbench's style.css), and both come
				before the buttons in the document as they do on the screen	*/
			const msg = dc.createElement('p');
			msg.id = 'design-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const state = dc.createElement('span');
			state.id = 'design-state-word';
			state.className = 'design-state-word';
			Nino.admin.design._paintState( state );

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
			actions.appendChild( msg );
			actions.appendChild( state );

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

			Nino.admin.design._refreshDirty();

			// The stage takes the room the window has left under it as soon as it
			// stands there, not when the first preview has come back
			Nino.admin.design._fit();

			/*	A rendered screen has an empty frame in it, and the frame is the
				answer to what every select above it means - so it is filled
				straight away rather than on a button somebody has to find */
			Nino.admin.design._frames = null;
			Nino.admin.design._preview( 0 );

			// A screen drawn from the stored selection has nothing unsaved
			// to mark any more
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
		 *	not here, it is the chip in the action bar
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

			Nino.admin.design._notes.forEach( function( note ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = Nino.admin.design._noteText( note );
				controls.appendChild( p );
			} );

			const body = dc.createElement('div');
			body.id = 'design-body';
			controls.appendChild( body );

			Nino.admin.design._fillBody( body );

			return controls;
		},

		/**
		 *	The tabs of the panel, in the order the table gives them
		 *
		 *	@return		{Array}					Keys of the tabs
		 */
		_tabKeys : function() {
			return ( ( Nino.admin.design._data || {} ).layout || {} ).tabs || [];
		},

		/**
		 *	Where a control stands: the entry of the table for the key its choice is kept under
		 *
		 *	@param		{string}	key				'size', 'radii/card', 'knobs/content/quiet' ...
		 *
		 *	@return		{Array}						[ tab, object, order ]; [] for a key the table does not have
		 */
		_place : function( key ) {
			return ( ( ( Nino.admin.design._data || {} ).layout || {} ).controls || {} )[key] || [];
		},

		/**
		 *	The rows a tab is drawn in, out of the table and nothing else: for each object that has a control on
		 *	the tab, in the order the table gives the objects, the controls of it in the order of their numbers. A
		 *	control is the keys that stand at one place: the cover, the row and the column of a section are one,
		 *	and so are the second colour and the row of Harmony it ends. An object with no control on the tab has
		 *	no row
		 *
		 *	@param		{string}	tab				A key of the table's tabs
		 *
		 *	@return		{Array}						{ object, controls : [ [ key, ... ], ... ] } for each row; empty for a tab with no control
		 */
		_rows : function( tab ) {

			const layout = ( Nino.admin.design._data || {} ).layout || {};
			const controls = layout.controls || {};

			return ( layout.objects || [] ).map( function( object ) {

				const places = {};

				Object.keys( controls ).forEach( function( key ) {

					if( controls[key][0] === tab && controls[key][1] === object )
						( places[ controls[key][2] ] = places[ controls[key][2] ] || [] ).push( key );
				} );

				return {
					object 		: object,
					controls 	: Object.keys( places ).sort( function( a, b ) { return a - b } ).map( function( order ) { return places[order] } ),
				};
			} ).filter( function( row ) { return row.controls.length > 0 } );
		},

		/**
		 *	What the open tab holds, into the box the column keeps for it: the strip of its screens and the
		 *	screen that is open where it has some, the two cards of the loader on the Typefaces tab, else its
		 *	blocks
		 *
		 *	@param		{Element}	body
		 *
		 *	@return		void
		 */
		_fillBody : function( body ) {

			const tab = Nino.admin.design._tab;

			if( Nino.admin.design.SCREENS[tab] !== undefined )
				return Nino.admin.design._renderScreens( tab, body );

			if( tab === 'typefaces' )
				return Nino.admin.design._renderTypefaces( body );

			Nino.admin.design._renderRows( tab, body );
		},

		/**
		 *	The seven tabs, the ones the table names. A tablist rather than a button group, because these
		 *	really are seven panels: aria-selected, not aria-pressed. The label of
		 *	the Theme tab carries the chip "changed" while the screen differs from its
		 *	theme, so a strip that is drawn anew is drawn with it (see _refreshThemeChip())
		 *
		 *	@return		{HTMLElement}
		 */
		_renderTabs : function() {

			const design = Nino.admin.design;

			const strip = design._tabs( Nino.content.getText('/_admin/design/label/title'), 'design-tabs', 'tab',
				design._tabKeys().map( function( key ) {
					return { key : key, label : Nino.content.getText('/_admin/design/tab/'+ key+ '/label') };
				} ), design._tab, function( key ) {
					design._switchTab( key );
				} );

			// Found again by its id when a change asks for the chip (the strip is mounted by then)
			strip.id = 'design-tabs';
			design._refreshThemeChip( null, strip );

			return strip;
		},

		/**
		 *	A strip of tabs: a tablist of buttons, one of them selected. Nino.adminUi.buttonRow()
		 *	keeps that, with the arrow keys and the one tab stop of a strip. The seven tabs of the
		 *	panel are drawn with it, and so are the screens of Theme (see _renderScreens())
		 *
		 *	@param		{string}		name				What the strip is called by assistive technology
		 *	@param		{string}		className		The class the strip has beside the workbench's own
		 *	@param		{string}		attribute		The data attribute a tab carries its key in: 'tab' for data-tab
		 *	@param		{Array}			tabs				{ key, label }, in the order they stand in
		 *	@param		{string}		active			The key of the tab that is selected
		 *	@param		{Function}	onSwitch		Called with the key of the tab that was pressed
		 *
		 *	@return		{HTMLElement}
		 */
		_tabs : function( name, className, attribute, tabs, active, onSwitch ) {

			const bar = dc.createElement('div');
			// --bar: the underline strip every panel with several screens uses,
			// the Users and Features panels among them - not the segmented row of
			// boxes, which is a control for a value, not for a screen
			bar.className = 'nino-admin-tabs nino-admin-tabs--bar '+ className;
			bar.setAttribute( 'role', 'tablist' );
			bar.setAttribute( 'aria-label', name );

			const buttons = {};

			tabs.forEach( function( tab ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.setAttribute( 'role', 'tab' );
				button.dataset[attribute] = tab.key;
				button.textContent = tab.label;
				buttons[tab.key] = button;
				bar.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, active, onSwitch, 'aria-selected' );

			return bar;
		},

		/**
		 *	Open another tab.
		 *
		 *	Only the column is redrawn, never the frame beside it: the page in
		 *	it is the same page under every tab, so rebuilding it would cost a
		 *	request and a flash for a click that changed which controls are on
		 *	screen and nothing at all about the design. What the frame does is
		 *	scroll, smoothly, to the anchor of the tab that was opened - once the
		 *	page in it stands at the height the stage has for that tab.
		 *
		 *	Nor the strip in the pane's head. The tab the switch was made on is
		 *	the one the keyboard is on, and the strip used to be drawn again
		 *	with the column: an arrow key opened the next tab and dropped the
		 *	focus onto the page, so the next arrow went nowhere.
		 *
		 *	@param		{string}	key			A key of the table's tabs
		 *
		 *	@return		void
		 */
		_switchTab : function( key ) {

			if( key === Nino.admin.design._tab )
				return;

			// The faces the names of the typefaces were set in are the loader's: gone with it
			if( Nino.admin.design._onInstall() === true )
				Nino.admin.design._dropFaces();

			Nino.admin.design._tab = key;

			// Where a change was looked at belonged to the tab that was left: the
			// frame goes to the anchor of this one
			Nino.admin.design._target = null;

			const column = dc.getElementById('design-controls');

			if( column === null )
				return Nino.admin.design._render();

			column.replaceWith( Nino.admin.design._renderControls() );

			// In one column the stage stands under the controls, so a column of another
			// height moves it: fitted again, and the jump waits for the page in the frame
			// (see _jumpFitted())
			const frame = dc.getElementById('design-frame');
			const was = frame === null ? '' : frame.parentNode.style.height;

			Nino.admin.design._fit();
			Nino.admin.design._jumpFitted( frame, was );
		},

		/**
		 *	Scroll the page in the frame to where it is looked at, once it stands at the
		 *	height the stage was just fitted to.
		 *
		 *	In one column the stage stands under the controls, so a column of another
		 *	height moves it - another tab or screen, the brand warning
		 *	of the colours - and the fit gives the frame another height. The page in it
		 *	lays itself out again only after its window has heard the resize: the covers
		 *	take their height from the window's, in an animation frame that follows it
		 *	(Nino.js, Nino.ui.js). An anchor measured at once is where it stood at the
		 *	old height - a tab opened after a taller one ended hundreds of pixels off,
		 *	and so did an answer that took the brand warning away. So where the fit
		 *	changed the height the jump waits for that resize and is made in the
		 *	animation frame after it, which is queued after the page's own; a timer takes
		 *	it there should the frame not be resized after all. Where the fit left the
		 *	height as it was, or the frame cannot be reached, the jump is made at once
		 *
		 *	Asked for by whatever fits the stage and then jumps: a tab that is opened
		 *	(_switchTab()), another screen of a tab (_switchGroup(), which asks only
		 *	where the fit changed the height: the page is the same under every screen, and
		 *	a stage as tall as it was leaves nothing to put back) and the answer of a
		 *	preview whose stylesheet was swapped in (_previewNow())
		 *
		 *	@param		{?Element}	frame		The frame, as found before the fit; null where there is none
		 *	@param		{string}		was			The height of its stage before the fit
		 *
		 *	@return		void
		 */
		_jumpFitted : function( frame, was ) {

			if( frame === null || frame.parentNode === null || frame.parentNode.style.height === was )
				return Nino.admin.design._jump();

			let view = null;

			try {
				view = frame.contentDocument.defaultView;
			}
			catch( e ) {
				// A frame that cannot be reached has no page to wait for
			}

			if( view === null || typeof view.addEventListener !== 'function' )
				return Nino.admin.design._jump();

			let done = false;

			const settled = function() {

				if( done === true )
					return;

				done = true;
				view.removeEventListener( 'resize', resized );
				Nino.admin.design._jump();
			};

			const resized = function() { view.requestAnimationFrame( settled ) };

			view.addEventListener( 'resize', resized );
			wn.setTimeout( settled, 300 );
		},

		/**
		 *	The Theme tab, the screen that is open of its three: Load, with the card of the list of
		 *	themes and the file field that takes one in; Properties, with the card of the loaded
		 *	theme - its name and description, the chip that says the screen differs from it and the
		 *	buttons that keep the screen in it and take it out as a file - and the card of the row
		 *	that makes a theme out of the screen; and Custom CSS, with the card of the css of its own,
		 *	which every input mirrors into the preview. The cards of the first two are drawn again
		 *	by what the tab does (see _paintTheme()), the css once each time its screen is opened:
		 *	it holds what is being typed
		 *
		 *	@param		{Element}	body		The box the screen is drawn into
		 *
		 *	@return		void
		 */
		_renderTheme : function( body ) {

			const design = Nino.admin.design;
			const screen = design._groupOf('theme');

			// By the node, not by its id: this column is not in the document yet on the first render
			if( screen === 'load' ) {
				const themes = design._card('design-themes');
				body.appendChild( themes );
				design._paintThemes( themes );
				return;
			}

			if( screen === 'properties' ) {
				const current = design._card('design-theme-current');
				const make = design._card('design-theme-make');
				body.appendChild( current );
				body.appendChild( make );
				design._paintCurrent( current );
				design._paintMake( make );
				return;
			}

			const css = design._card('design-theme-css-card');
			body.appendChild( css );
			design._paintCustomCss( css );
		},

		/**
		 *	A card of the column, the workbench's in the measures of this panel, under the id the actions of a
		 *	tab find it by
		 *
		 *	@param		{string}	id
		 *
		 *	@return		{Element}
		 */
		_card : function( id ) {

			const card = dc.createElement('div');
			card.id = id;
			card.className = 'nino-admin-card design-block';

			return card;
		},

		/**
		 *	Draw the cards the actions of the tab change again - those of the screen that is open -
		 *	and put the css of the working copy into its field in place: a load changes it, and
		 *	the field is left alone otherwise, so that the caret of what is being typed stays
		 *
		 *	@return		void
		 */
		_paintTheme : function() {

			const design = Nino.admin.design;
			const themes = dc.getElementById('design-themes');
			const current = dc.getElementById('design-theme-current');
			const make = dc.getElementById('design-theme-make');
			const css = dc.getElementById('design-theme-css');

			if( themes !== null )
				design._paintThemes( themes );

			if( current !== null )
				design._paintCurrent( current );

			if( make !== null )
				design._paintMake( make );

			if( css !== null && design._edit !== null && css.value !== design._edit.css )
				css.value = design._edit.css;

			// The label of the tab says what an action did to the theme whichever screen is open
			design._refreshThemeChip();
		},

		/**
		 *	A word as a chip beside a name: the workbench's pill, the state its colour
		 *
		 *	@param		{string}	className
		 *	@param		{string}	text
		 *	@param		{string}	state				ok, warn, error or muted
		 *	@param		{string}	[id]
		 *
		 *	@return		{Element}
		 */
		_chip : function( className, text, state, id ) {

			const chip = Nino.adminUi.el( 'span', 'nino-admin-badge '+ className, text );
			chip.dataset.state = state;

			if( id )
				chip.id = id;

			return chip;
		},

		/**
		 *	The card of the Load screen: the themes there are, one row each, and under them the
		 *	file that takes another one in
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintThemes : function( card ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/theme/list/title') ) );

			const list = ui.el( 'ul', 'design-theme-list' );
			list.id = 'design-theme-list';

			( ( design._data || {} ).themes || [] ).forEach( function( entry ) {
				list.appendChild( design._themeRow( entry ) );
			} );

			card.appendChild( list );

			const file = ui.el( 'label', 'nino-admin-field design-theme-import' );
			file.appendChild( ui.el( 'span', '', Nino.content.getText('/_admin/design/theme/import/label') ) );

			const input = ui.el( 'input', 'nino-admin-input' );
			input.type = 'file';
			input.id = 'design-theme-import';
			input.accept = '.json,application/json';
			input.disabled = design._themeBusy;
			input.addEventListener( 'change', function() { design._readTheme( input ) } );

			file.appendChild( input );
			card.appendChild( file );
		},

		/**
		 *	The card of the Properties screen under the box of the loaded theme: the row that makes a
		 *	theme out of the screen - a name, and the button that keeps the current state under it, as
		 *	Save theme keeps it in the loaded theme. It stands whether or not a theme is loaded
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintMake : function( card ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;

			card.innerHTML = '';

			const make = ui.el( 'div', 'design-theme-new' );

			const field = ui.textField( {
				id 					: 'design-theme-new',
				label 			: Nino.content.getText('/_admin/design/theme/new/label'),
				placeholder : Nino.content.getText('/_admin/design/theme/new/placeholder'),
				value 			: design._themeNew,
				maxLength 	: 80,
				onInput 		: function( value ) { design._themeNew = value },
			} );

			field.querySelector('input').addEventListener( 'keydown', function( event ) {
				if( event.key === 'Enter' ) {
					event.preventDefault();
					design._createTheme();
				}
			} );

			make.appendChild( field );
			make.appendChild( ui.button( Nino.content.getText('/_admin/design/theme/create'), function() { design._createTheme() },
				{ id : 'design-theme-create', className : 'nino-admin-btn-primary', disabled : design._themeBusy } ) );
			card.appendChild( make );
		},

		/**
		 *	One theme of the list: its name with the chips it has - shipped; active where the website
		 *	runs it, which is the theme of the last apply; and loaded, not applied where the working
		 *	copy was taken from it and the website does not run it - the description under it, and what
		 *	can be done with it: load, duplicate and, for a theme of the project only, delete. A button
		 *	says which theme it is about to the ones who hear it read out
		 *
		 *	@param		{Object}	entry			One of design/list's `themes`: slug, name, description, shipped
		 *
		 *	@return		{Element}
		 */
		_themeRow : function( entry ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;
			const draft = design._edit !== null && design._edit.theme === entry.slug;
			const running = ( design._data || {} ).running === entry.slug;

			const row = ui.el( 'li', 'design-theme-row'+ ( draft === true ? ' design-theme-row--active' : '' ) );
			row.dataset.slug = entry.slug;

			if( draft === true )
				row.setAttribute( 'aria-current', 'true' );

			const text = ui.el( 'div', 'design-theme-text' );
			const head = ui.el( 'div', 'design-theme-head' );
			head.appendChild( ui.el( 'span', 'design-theme-name', entry.name ) );

			if( entry.shipped === true )
				head.appendChild( design._chip( 'design-theme-shipped', Nino.content.getText('/_admin/design/theme/shipped'), 'muted' ) );

			if( running === true )
				head.appendChild( design._chip( 'design-theme-active', Nino.content.getText('/_admin/design/theme/active'), 'ok' ) );

			if( draft === true && running === false )
				head.appendChild( design._chip( 'design-theme-loaded', Nino.content.getText('/_admin/design/theme/loaded'), 'warn' ) );

			text.appendChild( head );

			if( entry.description !== '' )
				text.appendChild( ui.el( 'small', 'design-theme-description', entry.description ) );

			row.appendChild( text );

			const buttons = ui.el( 'div', 'design-theme-buttons' );

			[ [ 'load', function() { design._loadTheme( entry.slug ) } ], [ 'duplicate', function() { design._duplicateTheme( entry.slug ) } ],
				[ 'delete', function() { design._deleteTheme( entry.slug ) } ] ].forEach( function( button ) {

				if( button[0] === 'delete' && entry.shipped === true )
					return;

				const word = Nino.content.getText('/_admin/design/theme/'+ button[0] );

				buttons.appendChild( ui.button( word, button[1], {
					id 				: 'design-theme-'+ button[0]+ '-'+ entry.slug,
					className : button[0] === 'delete' ? 'nino-admin-btn-danger' : '',
					name 			: word+ ' - '+ entry.name,
					disabled 	: design._themeBusy,
				} ) );
			} );

			row.appendChild( buttons );

			return row;
		},

		/**
		 *	The box of the Properties screen: the loaded theme - the one the working copy was taken
		 *	from or kept in. With none there is a sentence and nothing else. With one, its head
		 *	says its name and, while the screen differs from it, the chip (see _refreshThemeChip());
		 *	under it stand its name and its description as they are typed - those of a shipped theme
		 *	are read-only and say why - and the buttons that put the screen into it and take it out
		 *	as a file. A shipped theme is a file of the feature: there is no way to save into it
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintCurrent : function( card ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;
			const slug = design._edit === null ? '' : design._edit.theme;
			const active = design._active !== null && design._active.slug === slug ? design._active : null;
			const title = Nino.content.getText('/_admin/design/theme/current/title');

			card.innerHTML = '';

			if( slug === '' || active === null ) {
				card.appendChild( design._sectionLabel( title ) );
				card.appendChild( ui.hint( Nino.content.getText('/_admin/design/theme/current/none') ) );
				return;
			}

			const head = design._sectionLabel( title );
			head.id = 'design-theme-current-head';
			head.firstChild.appendChild( ui.el( 'span', 'design-theme-current-name', active.name ) );
			card.appendChild( head );

			const shipped = active.shipped === true;

			const name = ui.textField( {
				id 				: 'design-theme-name',
				label 		: Nino.content.getText('/_admin/design/theme/field/name'),
				value 		: design._themeText.name,
				maxLength : 80,
				hint 			: shipped === true ? Nino.content.getText('/_admin/design/theme/current/shipped') : undefined,
				onInput 	: function( value ) { design._themeText.name = value },
			} );

			const description = ui.textField( {
				id 				: 'design-theme-description',
				label 		: Nino.content.getText('/_admin/design/theme/field/description'),
				value 		: design._themeText.description,
				maxLength : 400,
				onInput 	: function( value ) { design._themeText.description = value },
			} );

			if( shipped === true ) {
				name.querySelector('input').disabled = true;
				description.querySelector('input').disabled = true;
			}

			card.appendChild( name );
			card.appendChild( description );

			const buttons = ui.el( 'div', 'design-theme-actions' );

			if( shipped === false )
				buttons.appendChild( ui.button( Nino.content.getText('/_admin/design/theme/save'), function() { design._saveTheme() },
					{ id : 'design-theme-save', className : 'nino-admin-btn-primary', disabled : design._themeBusy } ) );

			buttons.appendChild( ui.button( Nino.content.getText('/_admin/design/theme/export'), function() { design._exportTheme() },
				{ id : 'design-theme-export', disabled : design._themeBusy } ) );

			card.appendChild( buttons );

			design._refreshThemeChip( head );
		},

		/**
		 *	The card of the Custom CSS screen: the css of the theme, in a field of its own. Every
		 *	input sets it in the working copy and asks for a preview - the debounce of 350 ms every
		 *	control has - which compiles it as the last section of the sheet. The label is the
		 *	card's title again, for whoever hears the field and not the heading
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintCustomCss : function( card ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;
			const title = Nino.content.getText('/_admin/design/theme/css/title');

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( title ) );

			const label = ui.el( 'label', 'nino-admin-field' );
			label.appendChild( ui.el( 'span', 'design-theme-css-name', title ) );

			const box = ui.el( 'textarea', 'nino-admin-input design-theme-css' );
			box.id = 'design-theme-css';
			box.rows = 14;
			box.spellcheck = false;
			box.setAttribute( 'autocapitalize', 'off' );
			box.setAttribute( 'wrap', 'off' );
			box.value = design._edit === null ? '' : design._edit.css;
			box.addEventListener( 'input', function() {
				design._edit.css = box.value;
				design._preview();
			} );

			label.appendChild( box );
			card.appendChild( label );
			card.appendChild( ui.hint( Nino.content.getText('/_admin/design/theme/css/hint') ) );
		},

		/**
		 *	The setup of a working copy as a theme keeps it: the keys that choose something, without
		 *	the theme it names and the css it carries - the screen's side of the compare the chip makes
		 *	with the file's (see _themeChanged()). The colours are among them where the theme names
		 *	colours: a theme that names none - every theme the feature ships - has no say over them
		 *
		 *	@param		{Object}	edit
		 *	@param		{Object}	[theme]		The setup of the theme it is compared with; with no colours in it the snapshot has none either
		 *
		 *	@return		{Object}
		 */
		_snapshotOf : function( edit, theme ) {

			const snapshot = { size : edit.size, width : edit.width, whitespace : edit.whitespace, radius : edit.radius, 'radius-alt' : edit['radius-alt'], radii : edit.radii,
				motion : edit.motion, parts : edit.parts, knobs : edit.knobs, fonts : edit.fonts };

			if( theme === undefined || theme.colours !== undefined )
				snapshot.colours = edit.colours;

			return snapshot;
		},

		/**
		 *	Whether the screen differs from the theme it was taken from: the snapshot of the
		 *	working copy and its css against those of the theme's file, as the server holds them
		 *	- canonical, as the way back compares, so that a knob moved and put back is no change
		 *
		 *	@return		{boolean}					false where there is no theme
		 */
		_themeChanged : function() {

			const design = Nino.admin.design;
			const edit = design._edit;
			const active = design._active;

			if( edit === null || edit.theme === '' || active === null || active.slug !== edit.theme )
				return false;

			return design._canonical( { setup : design._snapshotOf( edit, active.setup ), css : edit.css } ) !== design._canonical( { setup : active.setup, css : active.css } );
		},

		/**
		 *	Draw the chip "changed" in the head of the active theme and on the label of the Theme tab, or take
		 *	it away: it is there while the screen differs from the theme (see _themeChanged()), in both places
		 *	or in neither, whichever tab and screen are open. Asked for after every change of a control, beside
		 *	the way back, by everything that draws the card of the theme and by the strip of the tabs when it is drawn
		 *
		 *	@param		{?Element}	[head]		The head, for one that is built and not mounted yet
		 *	@param		{?Element}	[strip]		The strip of the tabs, for one that is built and not mounted yet
		 *
		 *	@return		void
		 */
		_refreshThemeChip : function( head, strip ) {

			const design = Nino.admin.design;
			const changed = design._themeChanged();

			head = head || dc.getElementById('design-theme-current-head');
			strip = strip || dc.getElementById('design-tabs');

			if( head !== null )
				design._flagChanged( head.firstChild, 'design-theme-changed', changed, 'design-theme-changed' );

			if( strip !== null )
				design._flagChanged( strip.children[ design._tabKeys().indexOf('theme') ], 'design-tab-changed', changed );
		},

		/**
		 *	The chip "changed" in a place: drawn there while `on` and it is not, taken away while not `on` and it
		 *	is, and left as it is otherwise - one chip at most, found by its class
		 *
		 *	@param		{Element}		host			What the chip stands in: the title of the box of the theme, or the label of the tab
		 *	@param		{string}		className	The class the chip has beside the workbench's own
		 *	@param		{boolean}		on
		 *	@param		{string}		[id]
		 *
		 *	@return		void
		 */
		_flagChanged : function( host, className, on, id ) {

			const chip = host.querySelector('.'+ className );

			if( on === true && chip === null )
				host.appendChild( Nino.admin.design._chip( className, Nino.content.getText('/_admin/design/theme/current/changed'), 'warn', id ) );

			if( on === false && chip !== null )
				chip.remove();
		},

		/**
		 *	One row of design/list's `themes`, by its slug
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		{Object}					{ slug, name, description, shipped } - with the slug for a name where the theme is not in the list
		 */
		_themeEntry : function( slug ) {

			const found = ( ( Nino.admin.design._data || {} ).themes || [] ).filter( function( entry ) { return entry.slug === slug } )[0];

			return found || { slug : slug, name : slug, description : '', shipped : false };
		},

		/**
		 *	Switch the buttons and the file field of the tab off while one of its requests is on its way, and
		 *	on again when it is back: a second action over the first would answer over a state the screen
		 *	does not have any more
		 *
		 *	@param		{boolean}	on
		 *
		 *	@return		void
		 */
		_themeWork : function( on ) {

			Nino.admin.design._themeBusy = on;

			[ 'design-themes', 'design-theme-current', 'design-theme-make' ].forEach( function( id ) {

				const card = dc.getElementById( id );

				if( card === null )
					return;

				card.querySelectorAll('button, input[type="file"]').forEach( function( control ) { control.disabled = on } );
			} );
		},

		/**
		 *	A failure of an action of the tab, told in the line of the action bar in red: the sentence of its
		 *	code where it has one, else the sentence of the action with the status in front
		 *
		 *	@param		{string}	failure			The text of what was being done
		 *	@param		{number}	status
		 *	@param		{*}				response
		 *
		 *	@return		void
		 */
		_themeFail : function( failure, status, response ) {
			Nino.admin.design._say( dc.getElementById('design-msg'), Nino.adminUi.api.errorText( status, response, failure ), 'nino-admin-error' );
		},

		/**
		 *	Put a theme on the screen as a draft. The server answers what the screen takes over and which of
		 *	the typefaces the theme names the project has not installed. Those the list of the Typefaces tab
		 *	has are named in one question - with a yes they are installed, one after the other, and the list of
		 *	design/list is read again, for the selects of the roles, before anything is drawn. Nothing is
		 *	stored: the Reset button appears, the shell asks before it loses the draft and Save draft keeps it
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_loadTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/load', { slug : slug }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				if( status !== 200 || response === null ) {
					design._themeWork( false );
					return design._themeFail( '/_admin/design/theme/error/load', status, response );
				}

				const offered = ( response.missing || [] ).filter( function( entry ) { return entry.inList === true } );

				if( offered.length > 0 && wn.confirm( design._text( '/_admin/design/theme/confirm/fonts', offered.map( function( entry ) { return entry.name } ).join(', ') ) ) === true )
					return design._installMissing( offered, function( said, failure ) {
						design._relist( function() { design._takeTheme( response, said, failure, stamp ) } );
					} );

				design._takeTheme( response, [], '', stamp );
			} );
		},

		/**
		 *	Install the typefaces a theme names, one after the other, as the loader installs one: a
		 *	failure stops the chain and is said, and what is installed stays
		 *
		 *	@param		{Array}			list				design/theme/load's `missing` that the list has: slug, name
		 *	@param		{Function}	then				Called with the sentences of what was installed and, where one failed, why
		 *
		 *	@return		void
		 */
		_installMissing : function( list, then ) {

			const design = Nino.admin.design;
			const said = [];

			const next = function( at ) {

				if( at >= list.length )
					return then( said, '' );

				design._apiCall( 'webfonts/install', { slug : list[at].slug }, function( status, response ) {

					if( status !== 200 || response === null ) {
						const reason = response !== null && typeof response.error === 'string' && response.error !== ''
							? response.error : Nino.adminUi.api.errorText( status, response, '' );

						return then( said, design._text( '/_admin/design/typefaces/error/install', reason ) );
					}

					said.push( response.msg );
					next( at + 1 );
				} );
			};

			next( 0 );
		},

		/**
		 *	The theme that was asked for becomes the screen: the working copy is the theme's setup with its
		 *	slug and its css - and the colours it already had, where the theme names none: a brand colour is
		 *	the project's - the active theme is its record - the setup of it as it was taken over, which
		 *	is what the chip compares the screen with - and the name and description fields are its own.
		 *	What reading the theme replaced is told in the list over the tab, replacing the notes of what was
		 *	on the screen before.
		 *	A family the role is not offered here - not in the list, declined, or not installed after all -
		 *	is set to the face of Nino and told in the line of the bar with the rest. The screen is drawn
		 *	again whole, the preview with it
		 *
		 *	@param		{Object}		response		design/theme/load's answer
		 *	@param		{Array}			said				What the installs before it said
		 *	@param		{string}		failure			Why an install failed, '' where none did
		 *	@param		{number}		stamp				The count of the request, against a newer state
		 *
		 *	@return		void
		 */
		_takeTheme : function( response, said, failure, stamp ) {

			const design = Nino.admin.design;

			if( stamp !== design._themeStamp )
				return;

			design._themeWork( false );

			const setup = JSON.parse( JSON.stringify( response.setup ) );
			const options = ( design._data || {} ).options || {};
			const lacking = {};

			Object.keys( setup.fonts ).forEach( function( role ) {

				const family = setup.fonts[role].family;

				if( family !== '' && ( options[role] || [] ).indexOf( family ) === -1 ) {
					lacking[family] = true;
					setup.fonts[role].family = '';
				}
			} );

			const names = Object.keys( lacking ).map( function( family ) {
				return ( response.missing || [] ).filter( function( entry ) { return entry.slug === family } ).map( function( entry ) { return entry.name } )[0] || family;
			} );

			design._edit = { parts : setup.parts, knobs : setup.knobs, size : setup.size, width : setup.width, whitespace : setup.whitespace,
				radius : setup.radius, 'radius-alt' : setup['radius-alt'], radii : setup.radii, motion : setup.motion, fonts : setup.fonts,
				colours : setup.colours === undefined ? design._edit.colours : setup.colours, theme : response.slug, css : response.css };
			design._active = { slug : response.slug, name : response.name, description : response.description, shipped : response.shipped,
				setup : JSON.parse( JSON.stringify( design._snapshotOf( design._edit, setup ) ) ), css : response.css };
			design._themeText = { name : response.name, description : response.description };
			design._notes = Array.isArray( response.notes ) === true ? response.notes : [];
			design._render();

			const lines = said.slice();

			if( failure !== '' )
				lines.push( failure );

			lines.push( design._text( '/_admin/design/theme/msg/loaded', response.name ) );

			if( names.length > 0 )
				lines.push( design._text( '/_admin/design/theme/msg/unavailable', names.join(', ') ) );

			design._say( dc.getElementById('design-msg'), lines.join(' '), failure !== '' ? 'nino-admin-error' : ( names.length > 0 ? 'nino-admin-hint' : '' ) );
		},

		/**
		 *	Keep the screen as a theme of the project under the name in the field of the row that makes
		 *	one, in the card under the loaded theme on Properties. The draft then names it - unsaved,
		 *	so that the way back takes it away again, which is right - and the name and description
		 *	fields are the theme's, the chip none
		 *
		 *	@return		void
		 */
		_createTheme : function() {

			const design = Nino.admin.design;

			if( design._themeBusy === true || design._edit === null )
				return;

			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/create', Object.assign( {}, design._edit, { name : design._themeNew, description : '' } ), function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/create', status, response );

				design._data.themes = response.themes;
				design._active = response.active;
				design._edit.theme = response.slug;
				design._themeText = { name : response.active.name, description : response.active.description };
				design._themeNew = '';
				design._paintTheme();
				design._refreshDirty();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/created', response.active.name ) );
			} );
		},

		/**
		 *	Put the screen into the active theme - the working copy, its css, and the name and description
		 *	as they are typed. The answer is the theme as the server holds it now, and the list
		 *
		 *	@return		void
		 */
		_saveTheme : function() {

			const design = Nino.admin.design;

			if( design._themeBusy === true || design._edit === null || design._edit.theme === '' )
				return;

			const slug = design._edit.theme;
			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/save', Object.assign( {}, design._edit, { slug : slug, name : design._themeText.name, description : design._themeText.description } ), function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/save', status, response );

				design._data.themes = response.themes;
				design._active = response.active;
				design._themeText = { name : response.active.name, description : response.active.description };

				// The theme the stored setup was taken from is what the way back goes back to
				if( design._data.theme === slug )
					design._data.active = response.active;

				design._paintTheme();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/saved', response.active.name ) );
			} );
		},

		/**
		 *	Copy a theme - a shipped one too - into one of the project. Nothing is loaded: the list is drawn again
		 *	with the copy in it, and the line of the bar names both
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_duplicateTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			const stamp = ++design._themeStamp;
			const from = design._themeEntry( slug ).name;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/duplicate', { slug : slug }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/duplicate', status, response );

				design._data.themes = response.themes;
				design._paintTheme();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/duplicated', from, design._themeEntry( response.slug ).name ) );
			} );
		},

		/**
		 *	Delete a theme of the project, after asking. Where it is the one the working copy was taken from,
		 *	the working copy has none any more, and the slug the stored setup has is what the answer says
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_deleteTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			const name = design._themeEntry( slug ).name;

			if( wn.confirm( design._text( '/_admin/design/theme/confirm/delete', name ) ) === false )
				return;

			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/delete', { slug : slug }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/delete', status, response );

				design._data.themes = response.themes;
				design._data.theme = response.theme;

				if( design._data.active !== null && design._data.active.slug === slug )
					design._data.active = null;

				if( design._edit.theme === slug ) {
					design._edit.theme = '';
					design._active = null;
					design._themeText = { name : '', description : '' };
				}

				design._paintTheme();
				design._refreshDirty();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/deleted', name ) );
			} );
		},

		/**
		 *	Take the active theme out as a file: the answer is its text and the name to save it under, and
		 *	becomes a download the way the Language panel's export does - a blob, a link clicked and removed,
		 *	the address revoked
		 *
		 *	@return		void
		 */
		_exportTheme : function() {

			const design = Nino.admin.design;

			if( design._themeBusy === true || design._edit === null || design._edit.theme === '' )
				return;

			const slug = design._edit.theme;
			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/export', { slug : slug }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/export', status, response );

				const blob = new Blob( [ response.json ], { type : 'application/json' } );
				const url = URL.createObjectURL( blob );
				const link = dc.createElement('a');

				link.href = url;
				link.download = response.file;
				dc.body.appendChild( link );
				link.click();
				link.remove();
				URL.revokeObjectURL( url );

				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/exported', design._themeEntry( slug ).name ) );
			} );
		},

		/**
		 *	Read the file the field of the list was given, as text, and take it in
		 *
		 *	@param		{HTMLInputElement}	input
		 *
		 *	@return		void
		 */
		_readTheme : function( input ) {

			const design = Nino.admin.design;
			const file = input.files !== null && input.files.length > 0 ? input.files[0] : null;

			if( file === null )
				return;

			// The same file can be chosen again: the field does not keep it
			const reader = new FileReader();

			reader.addEventListener( 'load', function() {
				input.value = '';
				design._importTheme( String( reader.result ) );
			} );

			reader.addEventListener( 'error', function() {
				input.value = '';
				design._say( dc.getElementById('design-msg'), Nino.content.getText('/_admin/design/theme/error/import'), 'nino-admin-error' );
			} );

			reader.readAsText( file );
		},

		/**
		 *	Take a theme file in as a theme of the project. A refusal is told by the sentence of its code - a file
		 *	that is no theme file, a slug that is taken, a css that is refused - and the list is drawn again with
		 *	what came in
		 *
		 *	@param		{string}	text			The file
		 *
		 *	@return		void
		 */
		_importTheme : function( text ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			design._apiCall( 'theme/import', { json : text }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/import', status, response );

				design._data.themes = response.themes;
				design._paintTheme();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/imported', design._themeEntry( response.slug ).name ) );
			} );
		},

		/**
		 *	The rows of a tab into a box, one card each, in the order the table gives them: the blocks of an accordion on
		 *	every tab that is drawn from the table, the templates, the type, the surfaces, the colours and the motion alike.
		 *	The head of a block is a button with the word of its object, and the row itself, the body of the block, is drawn
		 *	while the block is open and not before. One block is open, the one the person left open on the tab and the first
		 *	where there is none (see _blockOf()). A tab that has one row - the colours and the motion are the page's alone -
		 *	is an accordion of one block, open to begin with and to be closed like any other
		 *
		 *	@param		{string}	tab				A key of the table's tabs
		 *	@param		{Element}	box
		 *
		 *	@return		void
		 */
		_renderRows : function( tab, box ) {

			const design = Nino.admin.design;
			const rows = design._rows( tab );
			const open = design._blockOf( tab, rows );

			rows.forEach( function( row ) {
				box.appendChild( design._renderRow( tab, row, row.object === open ) );
			} );
		},

		/**
		 *	The block of a tab that is open: the one the person left open on it, else - where nothing was chosen on
		 *	the tab yet - its first
		 *
		 *	@param		{string}	tab				A key of the table's tabs
		 *	@param		{Array}		rows			The rows of the tab, see _rows()
		 *
		 *	@return		{?string}						The object of the row that is open; null where the person closed it, and for a tab with no rows
		 */
		_blockOf : function( tab, rows ) {

			const chosen = Nino.admin.design._open[tab];
			const objects = rows.map( function( row ) { return row.object } );

			if( chosen === null || objects.length === 0 )
				return null;

			return objects.indexOf( chosen ) !== -1 ? chosen : objects[0];
		},

		/**
		 *	The id of the card of a row: where it stands, so that a change finds the card it redraws
		 *
		 *	@param		{string}	tab
		 *	@param		{string}	object
		 *
		 *	@return		{string}
		 */
		_rowId : function( tab, object ) {
			return 'design-row-'+ tab+ '-'+ object;
		},

		/**
		 *	One row: a card of the controls the table gives an object on a tab, a block of the accordion - the head and, while the
		 *	block is open, the body under it (see _setBlock()). The card says which tab and which object it is the row of, and every
		 *	control in it says which keys it is (see _fillRow()), so that what is drawn can be read against the table
		 *
		 *	@param		{string}	tab
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *	@param		{boolean}	[open]		Whether the block is open
		 *
		 *	@return		{Element}
		 */
		_renderRow : function( tab, row, open ) {

			const design = Nino.admin.design;

			const card = dc.createElement('div');
			card.id = design._rowId( tab, row.object );
			card.className = 'nino-admin-card design-form-section design-accordion';
			card.dataset.tab = tab;
			card.dataset.object = row.object;

			card.appendChild( design._renderHead( tab, row ) );
			design._setBlock( card, tab, row, open === true );

			return card;
		},

		/**
		 *	The head of a block: a button that opens and closes it, with the word of its object, the line that names what
		 *	stands elsewhere than at the defaults while the block is closed (see _paintHead()) and a chevron, which is
		 *	decoration. The word is the button's name and the line its description; aria-expanded says whether the block
		 *	is open and, while it is, aria-controls which body it is the head of (see _paintHead()) - a closed block has no
		 *	body in the document, and its head names none
		 *
		 *	@param		{string}	tab
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *
		 *	@return		{Element}
		 */
		_renderHead : function( tab, row ) {

			const design = Nino.admin.design;
			const id = design._rowId( tab, row.object );

			const name = dc.createElement('span');
			name.id = id+ '-name';
			name.className = 'design-accordion-name';
			name.textContent = Nino.content.getText('/_admin/design/object/'+ row.object+ '/label');

			const summary = dc.createElement('span');
			summary.id = id+ '-summary';
			summary.className = 'design-accordion-summary';

			const chevron = design._svg('chevron-down');
			chevron.classList.add('design-accordion-chevron');

			const head = dc.createElement('button');
			head.type = 'button';
			head.className = 'design-accordion-head';
			head.setAttribute( 'aria-labelledby', name.id );
			head.setAttribute( 'aria-describedby', summary.id );
			head.addEventListener( 'click', function() { design._toggleBlock( tab, row.object, head ) } );

			head.appendChild( name );
			head.appendChild( summary );
			head.appendChild( chevron );

			return head;
		},

		/**
		 *	The body of a block: the controls of its row (see _fillRow()), in the box the head names in aria-controls
		 *
		 *	@param		{string}	tab
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *
		 *	@return		{Element}
		 */
		_renderBody : function( tab, row ) {

			const body = dc.createElement('div');
			body.id = Nino.admin.design._rowId( tab, row.object )+ '-body';
			body.className = 'design-accordion-body';

			Nino.admin.design._fillRow( body, row );

			return body;
		},

		/**
		 *	Put a block in the state it is to be in, where it stands: the body is drawn when the block is opened and taken away
		 *	when it is closed - the controls of a closed block are not drawn - and the head is painted for it. Nothing else is
		 *	drawn again, so that the head that was pressed is still the element the focus is on
		 *
		 *	@param		{Element}	card			The card of the row
		 *	@param		{string}	tab
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *	@param		{boolean}	open
		 *
		 *	@return		void
		 */
		_setBlock : function( card, tab, row, open ) {

			const design = Nino.admin.design;
			const body = card.querySelector('.design-accordion-body');

			if( open === true && body === null )
				card.appendChild( design._renderBody( tab, row ) );

			if( open === false && body !== null )
				body.remove();

			design._paintHead( card.querySelector('.design-accordion-head'), row, open );
		},

		/**
		 *	The head of a block as the state of the block has it: aria-expanded says whether it is open, and the line says -
		 *	while it is closed - which of the controls of its row stand elsewhere than at the defaults (see _summary()): nothing
		 *	where none does, and nothing while the block is open, where the controls themselves are in view. The line is
		 *	cut with dots where the head is too narrow, so its whole text is its title
		 *
		 *	@param		{Element}	head
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *	@param		{boolean}	open
		 *
		 *	@return		void
		 */
		_paintHead : function( head, row, open ) {

			const summary = head.querySelector('.design-accordion-summary');
			const line = open === true ? '' : Nino.admin.design._summary( row );

			head.setAttribute( 'aria-expanded', open === true ? 'true' : 'false' );

			// The body is in the document while the block is open and nowhere while it is closed: the head names it only then
			if( open === true )
				head.setAttribute( 'aria-controls', head.parentNode.id+ '-body' );
			else
				head.removeAttribute('aria-controls');

			summary.textContent = line;
			summary.title = line;
		},

		/**
		 *	Open a block, or close it where it is the open one. Opening a block closes the one that was open; which of them is
		 *	open is kept for the tab (see _open). It is a matter of the column and of nothing else: the selection stays as it is, no
		 *	preview is asked for and the frame is not moved - a change inside a block jumps, as it did, and this does not. The head
		 *	that was pressed stays where it stood in the window (see _holdHead())
		 *
		 *	@param		{string}	tab
		 *	@param		{string}	object		The object whose head was pressed
		 *	@param		{Element}	head			That head
		 *
		 *	@return		void
		 */
		_toggleBlock : function( tab, object, head ) {

			const design = Nino.admin.design;
			const rows = design._rows( tab );
			const was = design._blockOf( tab, rows );
			const top = head.getBoundingClientRect().top;

			design._open[tab] = was === object ? null : object;

			rows.forEach( function( row ) {

				const card = row.object === was || row.object === object ? dc.getElementById( design._rowId( tab, row.object ) ) : null;

				if( card !== null )
					design._setBlock( card, tab, row, design._open[tab] === row.object );
			} );

			design._holdHead( head, top );
		},

		/**
		 *	Keep the head that was pressed where it stood. The block that was open can stand above it, and its body goes with the
		 *	press: the head, and with it what the person was looking at, would move up the window by the height of that body - off
		 *	the top of it where the body is the taller. The page is scrolled by what the head moved, by whatever scrolls the column:
		 *	the pane in a wide window, the window itself in a narrow one (see _scrolled())
		 *
		 *	@param		{Element}	head			The head that was pressed
		 *	@param		{number}	top				Where its top stood in the window before the blocks were changed
		 *
		 *	@return		void
		 */
		_holdHead : function( head, top ) {

			const by = head.getBoundingClientRect().top - top;

			if( by === 0 )
				return;

			let box = head.parentNode;

			while( box !== null && box !== bd && box !== dE && ( box.scrollHeight <= box.clientHeight || [ 'auto', 'scroll' ].indexOf( wn.getComputedStyle( box ).overflowY ) === -1 ) )
				box = box.parentNode;

			( box === null || box === bd || box === dE ? dc.scrollingElement || dE : box ).scrollTop += by;
		},

		/**
		 *	What a closed block says of its row: the controls that stand elsewhere than a fresh setup has them (design/list
		 *	hands that setup over as `defaults`), each as its label and the word of its position, joined by a middle dot. Nothing
		 *	for a row that stands at the defaults - but for a template, which is a choice and no position: the block of a part
		 *	says the word of the set that is chosen, whichever it is, so that a person sees which one is on
		 *
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *
		 *	@return		{string}
		 */
		_summary : function( row ) {

			const design = Nino.admin.design;
			let lines = [];

			row.controls.forEach( function( keys ) {

				const path = keys[0].split('/');

				lines = lines.concat( path[0] === 'parts' ? [ design._setName( path[1], design._edit.parts[ path[1] ] ) ] : design._deviations( keys, row.object ) );
			} );

			return lines.join(' · ');
		},

		/**
		 *	What one control says where it stands elsewhere than at the default: its label and the word of the position it is at,
		 *	as its strip shows them - made of the very texts and options the strip is drawn from (see _knobStrip(), _radiiStrip()
		 *	and _pageName()). A font role says it for each of the four cells of its row that differs, by the name of the cell
		 *	and the word it shows. A knob the chosen template does not answer to is greyed out, and no part of what the design
		 *	is: it says nothing. The corners of a section - one choice for the cover, the row and the column - count once, and
		 *	say the word the strip shows, which is the cover's. A part of the palette says its hex - the brand colour, and the
		 *	second colour where one is set, which stands for the Harmony it ends - or its label and the word of its position,
		 *	the mode too; a part of the animation says its label and the word it is set to, the strength and the speed only
		 *	while there is an effect, since their selects are switched off until then (see _colourSaid()). A template says
		 *	nothing here: it is no position, and the head of its block names it whatever it is (see _summary())
		 *
		 *	@param		{Array}		keys			The keys of the control, as _rows() has them
		 *	@param		{string}	object		The object the control stands under
		 *
		 *	@return		{Array}							The lines, none where the control stands at the default
		 */
		_deviations : function( keys, object ) {

			const design = Nino.admin.design;
			const edit = design._edit;
			const normal = ( design._data || {} ).defaults || {};
			const path = keys[0].split('/');

			if( path[0] === 'knobs' ) {

				if( design._answered( path[1] ).indexOf( path[2] ) === -1 )
					return [];

				const chosen = ( edit.knobs[path[1]] || {} )[path[2]] || 0;
				const strip = design._knobStrip( path[1], path[2] );

				return chosen === ( ( ( normal.knobs || {} )[path[1]] || {} )[path[2]] || 0 ) ? [] : [ design._said( strip.name, strip.options, chosen ) ];
			}

			if( path[0] === 'radii' ) {

				const strip = design._radiiStrip( keys, object );
				const given = normal.radii || {};

				return strip.kinds.some( function( kind ) { return edit.radii[kind] !== given[kind] } ) === true ? [ design._said( strip.name, strip.options, edit.radii[ strip.kinds[0] ] ) ] : [];
			}

			if( path[0] === 'fonts' ) {

				const role = path[1];
				const chosen = edit.fonts[role] || {};
				const given = ( normal.fonts || {} )[role] || {};
				const lines = [];

				if( ( chosen.family || '' ) !== ( given.family || '' ) )
					lines.push( design._fontLabel( role, 'family' )+ ' '+ design._familyLabel( role, chosen.family || '' ) );

				if( ( chosen.weight || 'normal' ) !== ( given.weight || 'normal' ) )
					lines.push( design._fontLabel( role, 'weight' )+ ' '+ Nino.content.getText('/_admin/design/weight/'+ ( chosen.weight || 'normal' ) ) );

				if( ( chosen.uppercase === true ) !== ( given.uppercase === true ) )
					lines.push( design._fontLabel( role, 'uppercase' )+ ' '+ Nino.content.getText('/_admin/common/label/'+ ( chosen.uppercase === true ? 'on' : 'off' ) ) );

				if( String( chosen.size || '' ) !== String( given.size || '' ) )
					lines.push( design._fontLabel( role, 'size' )+ ' '+ design._sizeInForce( role )+ ' '+ Nino.content.getText('/_admin/design/fontsize/unit') );

				return lines;
			}

			if( path[0] === 'colours' )
				return keys.map( function( key ) { return design._colourSaid( key.split('/')[1] ) } ).filter( function( line ) { return line !== '' } );

			if( path[0] === 'motion' ) {

				const part = path[1];
				const value = ( edit.motion || {} )[part] || '';

				// Strength and speed mean something once there is an effect: until then their selects are switched off, and they say nothing
				if( value === ( ( normal.motion || {} )[part] || '' ) || ( part !== 'effect' && ( edit.motion.effect || '' ) === '' ) )
					return [];

				return [ design._said( Nino.content.getText('/_admin/design/motion/'+ part+ '/label'), design._motionOptions( part ), value ) ];
			}

			if( design.PAGE_WORDS[ keys[0] ] === undefined )
				return [];

			return edit[ keys[0] ] === normal[ keys[0] ] ? [] : [ design._said( design._pageName( keys[0] ), design._numbered( design._positions('scale'), 0 ), edit[ keys[0] ] ) ];
		},

		/**
		 *	A label and the word of a position, as a head says them: the label of the strip and the label of the option that
		 *	holds the value
		 *
		 *	@param		{string}	name			The label of the strip
		 *	@param		{Array}		options		{ value, label, title }, the options of the strip
		 *	@param		{*}				value			The value the strip stands at
		 *
		 *	@return		{string}
		 */
		_said : function( name, options, value ) {

			const found = options.filter( function( option ) { return String( option.value ) === String( value ) } )[0];

			return name+ ' '+ ( found === undefined ? String( value ) : found.label );
		},

		/**
		 *	What a head says of one part of the palette where it stands elsewhere than at the default: the brand colour and the
		 *	second colour by their label and their hex - an empty second colour is the one Harmony derives and says nothing -, a
		 *	knob by its label and the word its strip shows for the position (see _colourStrip()), the mode by its label and the word
		 *	of its select. Harmony says nothing while a second colour is set, since its strip lights no position then
		 *
		 *	@param		{string}	knob			primary, secondary, harmony, temperature, saturation, contrast, depth or mode
		 *
		 *	@return		{string}						'' where it stands at the default
		 */
		_colourSaid : function( knob ) {

			const design = Nino.admin.design;
			const chosen = ( design._edit || {} ).colours || {};
			const given = ( ( design._data || {} ).defaults || {} ).colours || {};
			const meta = ( ( design._data || {} ).palette || {} )[knob];

			if( knob === 'primary' )
				return String( chosen.primary || '' ) !== String( given.primary || '' ) ? Nino.content.getText('/_admin/design/label/primary')+ ' '+ chosen.primary : '';

			if( knob === 'secondary' )
				return String( chosen.secondary || '' ) !== '' ? Nino.content.getText('/_admin/design/label/secondary')+ ' '+ chosen.secondary : '';

			if( meta === undefined || ( knob === 'harmony' && String( chosen.secondary || '' ) !== '' ) )
				return '';

			const value = chosen[knob] || meta['default'];

			if( String( value ) === String( given[knob] || meta['default'] ) )
				return '';

			if( knob === 'mode' )
				return design._said( Nino.content.getText('/_admin/design/colour/mode/label'), design._modeOptions( meta ), value );

			const strip = design._colourStrip( knob, meta );

			return design._said( strip.name, strip.options, value );
		},

		/**
		 *	The controls of a row into the body of its block, in the order of the table. What a control is drawn as
		 *	follows from the first segment of its key: a template (parts), a knob (knobs), the corners of a kind of
		 *	surface (radii), a font role (fonts), a part of the animation (motion), a part of the palette (colours) - and a
		 *	strip of the page for the rest: the root size, the width, the whitespace and the two radii. Every control
		 *	carries the keys it stands for in data-control, joined by a space. The five roles are the rows of one table
		 *	under the heading of the fonts, the three parts of the animation the fields of one grid, and the palette's
		 *	knobs follow the brand colour under the heading Tuning
		 *
		 *	@param		{Element}	card			The body of the block
		 *	@param		{Object}	row				{ object, controls }, one of _rows()
		 *
		 *	@return		void
		 */
		_fillRow : function( card, row ) {

			const design = Nino.admin.design;
			const palette = ( design._data || {} ).palette || {};
			const fields = {};
			let table = null;
			let grid = null;
			let tuned = false;

			row.controls.forEach( function( keys ) {

				const path = keys[0].split('/');

				// A control is put where it stands and told what it is
				const put = function( node, into ) {
					node.dataset.control = keys.join(' ');
					( into || card ).appendChild( node );
				};

				if( path[0] === 'parts' )
					return put( design._partField( design._row( path[1] ) ) );

				if( path[0] === 'knobs' )
					return put( design._knobRow( path[1], path[2], design._answered( path[1] ).indexOf( path[2] ) !== -1 ) );

				if( path[0] === 'radii' )
					return put( design._radiiRow( keys, row.object ) );

				if( path[0] === 'fonts' ) {

					if( table === null ) {
						card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/label/fonts') ) );
						table = design._rolesTable();
						card.appendChild( table.table );
					}

					return put( design._fontRow( path[1] ), table.body );
				}

				if( path[0] === 'motion' ) {

					if( grid === null ) {
						grid = dc.createElement('div');
						grid.className = 'design-form-grid';
						card.appendChild( grid );
					}

					fields[ path[1] ] = design._motionField( path[1] );
					return put( fields[ path[1] ], grid );
				}

				if( path[0] === 'colours' ) {

					if( path[1] === 'primary' ) {
						put( design._primaryField() );
						return card.appendChild( design._brandWarning() );
					}

					// The knobs under the colour the palette is solved from stand under a small heading, as a block of rows does
					if( tuned === false ) {
						card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/label/tuning') ) );
						tuned = true;
					}

					// Harmony *is* the second colour: the swatch that overrides its four positions ends its row
					if( keys.indexOf('colours/harmony') !== -1 )
						return put( design._colourKnobRow( 'harmony', palette.harmony, design._secondField() ) );

					if( path[1] === 'mode' )
						return put( design._modeField( palette.mode ) );

					return put( design._colourKnobRow( path[1], palette[ path[1] ], null ) );
				}

				put( design._pageStrip( keys[0] ) );
			} );

			// Strength and speed only mean something once there is an effect
			if( fields.strength !== undefined || fields.speed !== undefined )
				design._motionOff( [ fields.strength, fields.speed ].map( function( field ) { return field === undefined ? null : field.querySelector('select') } ) );
		},

		/**
		 *	The label of one of the strips of the page: what it is called, by the key the table has it under
		 *
		 *	@param		{string}	key				size, width, whitespace, radius or radius-alt
		 *
		 *	@return		{string}
		 */
		_pageName : function( key ) {
			return Nino.content.getText('/_admin/design/label/'+ Nino.admin.design.PAGE_WORDS[key] );
		},

		/**
		 *	One of the strips of the page: the root size, the width, the whitespace or one of the two radii - five
		 *	positions, -2 to +2. What the ends of a strip are is the line under its name
		 *
		 *	@param		{string}	key				size, width, whitespace, radius or radius-alt
		 *
		 *	@return		{Element}
		 */
		_pageStrip : function( key ) {

			const design = Nino.admin.design;
			const edit = design._edit;
			const word = design.PAGE_WORDS[key];

			return design._stripRow(
				'page/'+ key, design._pageName( key ), Nino.content.getText('/_admin/design/hint/'+ word ),
				design._numbered( design._positions('scale'), 0 ), edit[key], function( position ) {
					edit[key] = position;
					design._target = design._anchorOf( key );
					design._preview();
				} );
		},

		/**
		 *	What a strip of corners is made of: the kinds of surface its keys stand for, the word its label is under - the
		 *	kind, or the object where the choice is one for several kinds -, its label and its two words
		 *
		 *	@param		{Array}		keys			The keys of the control: 'radii/<kind>', one or more
		 *	@param		{string}	object		The object the control stands under
		 *
		 *	@return		{Object}						{ kinds, word, name, options }, the options as { value, label, title }
		 */
		_radiiStrip : function( keys, object ) {

			const kinds = keys.map( function( key ) { return key.split('/')[1] } );
			const word = kinds.length > 1 ? object : kinds[0];

			return {
				kinds 	: kinds,
				word 		: word,
				name 		: Nino.content.getText('/_admin/design/radii/'+ word ),
				options	: ( Nino.admin.design._data.radiusWords || [] ).map( function( radius ) {
					const label = Nino.content.getText('/_admin/design/radii/'+ radius );
					return { value : radius, label : label, title : label };
				} ),
			};
		},

		/**
		 *	The corners a kind of surface takes where nothing chooses: a strip of the two words the radius has. A
		 *	control of several kinds - the cover, the row and the column of a section - is one choice that writes
		 *	them all the same. It reads as the word the kinds agree on; where they differ it shows the first one's
		 *	and says in its note that they do until it is chosen once. The label is the word of the kind, or - for
		 *	a choice of several - of the object they are the surfaces of
		 *
		 *	@param		{Array}		keys			The keys of the control: 'radii/<kind>', one or more
		 *	@param		{string}	object		The object the control stands under
		 *
		 *	@return		{Element}
		 */
		_radiiRow : function( keys, object ) {

			const design = Nino.admin.design;
			const edit = design._edit;
			const strip = design._radiiStrip( keys, object );
			const kinds = strip.kinds;
			const differ = kinds.some( function( kind ) { return edit.radii[kind] !== edit.radii[ kinds[0] ] } );

			const field = design._stripRow(
				'page/radii-'+ strip.word, strip.name, differ === true ? Nino.content.getText('/_admin/design/hint/radii') : '',
				strip.options, edit.radii[ kinds[0] ], function( word ) {

					kinds.forEach( function( kind ) { edit.radii[kind] = word } );

					// The three agree now: nothing is left to say
					field.querySelector('small').textContent = '';

					design._target = design._anchorOf( keys[0] );
					design._preview();
				} );

			return field;
		},

		/**
		 *	One of the three parts of the animation of the page, as a select: its effect, its strength or its speed.
		 *	'' is what Nino.css does by itself, and is what a fresh setup has
		 *
		 *	@param		{string}	key				effect, strength or speed
		 *
		 *	@return		{Element}
		 */
		_motionField : function( key ) {

			const design = Nino.admin.design;

			return design._select(
				'design-motion-'+ key, Nino.content.getText('/_admin/design/motion/'+ key+ '/label'), design._motionOptions( key ), design._edit.motion[key] || '',
				function( value ) { design._setMotion( key, value ) } );
		},

		/**
		 *	What the select of one of the three parts of the animation offers, and what the head of its block says a value
		 *	of it is: the default first, then a word for each value of the answer
		 *
		 *	@param		{string}	key				effect, strength or speed
		 *
		 *	@return		{Array}						{ value, label }
		 */
		_motionOptions : function( key ) {

			const design = Nino.admin.design;
			const values = { effect : design._data.effects, strength : design._data.strengths, speed : design._data.speeds }[key] || [];

			return [ { value : '', label : Nino.content.getText('/_admin/design/motion/default') } ].concat( values.map( function( value ) {
				return { value : value, label : Nino.content.getText('/_admin/design/motion/'+ key+ '/'+ value ) };
			} ) );
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
		 *	The select a template is chosen in: the one word of its label over it - the head of its block says which part it
		 *	is, and the object a second time would be too much -, the catalogue as its options and the description of the
		 *	chosen one as the line under it. The same for each of the five, in the block of its object on the Templates tab
		 *
		 *	@param		{Object}	row			One part, as design/list answered it
		 *
		 *	@return		{Element}
		 */
		_partField : function( row ) {

			const field = Nino.admin.design._select(
				'design-set-'+ row.part, Nino.content.getText('/_admin/design/label/template'),
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
		 *	never shows a setup the stored one could not be. The rows of the knobs
		 *	stand on Type and Surfaces and are drawn when those are opened, with
		 *	the knobs this template does not answer to greyed out.
		 *
		 *	The line under the select says what the template chosen is, written in
		 *	place: nothing else of the tab changes. The head of the block says the
		 *	word of the set that is chosen once the block is closed - which paints
		 *	it from the selection as it stands then (see _paintHead()), so that no
		 *	redraw is needed for it. The frame goes to where the template is looked
		 *	at (CONTROL_ANCHORS) - a header at the top of the page, a footer at its foot
		 *
		 *	@param		{string}		part
		 *	@param		{string}		set
		 *	@param		{Element}		[field]		The select field of the part, whose note is the template's description
		 *
		 *	@return		void
		 */
		_setPart : function( part, set, field ) {

			const design = Nino.admin.design;
			const edit = design._edit;
			const row = design._row( part );
			const answered = ( ( row.catalogue || {} )[set] || {} ).knobs || [];
			const note = field ? field.querySelector('small') : null;

			edit.parts[part] = set;

			Object.keys( edit.knobs[part] || {} ).forEach( function( knob ) {
				if( answered.indexOf( knob ) === -1 )
					edit.knobs[part][knob] = 0;
			} );

			if( note )
				note.textContent = ( ( row.catalogue || {} )[set] || {} ).description || '';

			design._target = design._anchorOf( 'parts/'+ part );
			design._preview();
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
			Nino.admin.design._target = Nino.admin.design._anchorOf( 'motion/'+ key );
			Nino.admin.design._replays = true;
			Nino.admin.design._motionOff( [ dc.getElementById('design-motion-strength'), dc.getElementById('design-motion-speed') ] );
			Nino.admin.design._preview();
		},

		/**
		 *	The table of the five roles of Nino.css, without its rows: the head of four columns - the family, the
		 *	weight, the case and the size - and the body the rows of the roles are put into one by one, as the table
		 *	gives them to the page (see _fillRow() and _fontRow()). The controls stand bare in the cells and the words
		 *	are in the column heads, which is what keeps the table readable in a third of the screen; each control
		 *	still carries its role and its column as its accessible name
		 *
		 *	@return		{Object}					{ table, body }: the table and the box of its rows
		 */
		_rolesTable : function() {

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
			table.appendChild( tbody );

			return { table : table, body : tbody };
		},

		/**
		 *	What a cell of a role is called: the role and the column, "Title - Weight" - the name of its control, and the
		 *	label of the case switch
		 *
		 *	@param		{string}	role			A key of data.roles
		 *	@param		{string}	column		family, weight, uppercase or size
		 *
		 *	@return		{string}
		 */
		_fontLabel : function( role, column ) {
			return Nino.content.getText('/_admin/design/font/role/'+ role )+ ' - '+ Nino.content.getText('/_admin/design/font/head/'+ column );
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
				control.setAttribute( 'aria-label', Nino.admin.design._fontLabel( role, column ) );
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
					label 	: Nino.admin.design._fontLabel( role, 'uppercase' ),
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

			/*	The size is typed, in rem, and the field shows the size that is in
				force: the one that was chosen, else the one Nino.css gives the role.
				It is the field's value and not its placeholder, so that the spinner
				counts from the size the page has - from an empty field it begins at
				the least of the role. The number is the one the field shows and
				takes, with the point: a number field drops a typed comma. What is typed
				is held inside the bounds of the role the way the server holds it, and
				the field then shows what was kept; a size that comes to the one
				Nino.css gives the role is kept as empty, and the role goes on following
				its default. The field steps by a tenth, which every bound is on: that
				is the spinner's step and the browser's own hint of what is valid, and
				not the model's - a typed 1.25 stays 1.25	*/
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
			size.value = Nino.admin.design._sizeInForce( role );
			size.addEventListener( 'change', function() {
				Nino.admin.design._setFont( role, 'size', Nino.admin.design._keepSize( role, size.value ) );
				size.value = Nino.admin.design._sizeInForce( role );
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
		 *	What was typed in a size, as the model keeps it: what _clampSize() makes
		 *	of it, and '' where that comes to the size Nino.css gives the role. A role
		 *	set to its default and a role left alone are one thing, and the model has
		 *	one word for it
		 *
		 *	@param		{string}	role
		 *	@param		{string}	typed				The value of the field
		 *
		 *	@return		{string}							'2.4', or '' for the default
		 */
		_keepSize : function( role, typed ) {

			const kept = Nino.admin.design._clampSize( role, typed );
			const given = ( ( Nino.admin.design._data || {} ).inherited || {} )[role];

			return kept !== '' && Number( kept ) === Number( given ) ? '' : kept;
		},

		/**
		 *	The size of a role that is in force: the one that was chosen, else the one
		 *	Nino.css gives the role. What its field shows
		 *
		 *	@param		{string}	role
		 *
		 *	@return		{string}							'1.6'
		 */
		_sizeInForce : function( role ) {

			const given = ( ( Nino.admin.design._data || {} ).inherited || {} )[role];

			return ( Nino.admin.design._edit.fonts[role] || {} ).size || ( given === undefined ? '' : String( given ) );
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
			Nino.admin.design._target = Nino.admin.design._anchorOf( 'fonts/'+ role );
			Nino.admin.design._preview();
		},

		/**
		 *	The Typefaces tab, the loader: two cards. The first holds the families the
		 *	project has installed, each with its name set in its own face, its weights, its
		 *	version and licence, and the button that removes it; the second the list of those
		 *	the repository offers, read when its button is pressed, with a search field, the
		 *	categories as a strip and pages of FACES_PER_PAGE.
		 *
		 *	Both halves are drawn from the answer the server gives after each action of the
		 *	tab, kept in _faces. The cached list is asked for whenever the tab is opened -
		 *	design/webfonts/list answers from data/ without a request of its own, and not
		 *	while an action is on its way, whose answer is the state - and the repository
		 *	only by the button. An install and a removal are made when their button is
		 *	pressed, and are no part of the draft: the line of the action bar says what they
		 *	did, and Reset has nothing to bring back.
		 *
		 *	@param		{Element}	body		The box the column keeps for the tab
		 *
		 *	@return		void
		 */
		_renderTypefaces : function( body ) {

			const installed = dc.createElement('div');
			installed.id = 'design-faces-installed';
			installed.className = 'nino-admin-card design-block';

			const available = dc.createElement('div');
			available.id = 'design-faces-available';
			available.className = 'nino-admin-card design-block';

			body.appendChild( installed );
			body.appendChild( available );

			// By the node, not by its id: this column is not in the document yet on the first render
			Nino.admin.design._paintInstalled( installed );
			Nino.admin.design._paintAvailable( available );

			// An action that is on its way answers with the state it leaves, and that answer draws the screen: a list read now could be older than it
			if( Nino.admin.design._facesBusy === false )
				Nino.admin.design._loadFaces( false );
		},

		/**
		 *	Draw both halves of the loader again, where the tab is open
		 *
		 *	@return		void
		 */
		_paintFaces : function() {

			const installed = dc.getElementById('design-faces-installed');
			const available = dc.getElementById('design-faces-available');

			if( installed !== null )
				Nino.admin.design._paintInstalled( installed );

			if( available !== null )
				Nino.admin.design._paintAvailable( available );
		},

		/**
		 *	Ask for the list. Without `refresh` the server answers with the list it kept, which is what
		 *	opening the tab does; with it the repository is asked, which only the button does
		 *
		 *	@param		{boolean}	refresh
		 *
		 *	@return		void
		 */
		_loadFaces : function( refresh ) {

			const design = Nino.admin.design;
			const stamp = ++design._facesStamp;

			if( refresh === true ) {
				design._facesBusy = true;
				design._say( dc.getElementById('design-msg'), '' );
				design._paintFaces();
			}

			design._apiCall( 'webfonts/list', { refresh : refresh }, function( status, response ) {

				const line = dc.getElementById('design-msg');

				if( refresh === true )
					design._facesBusy = false;

				if( status !== 200 || response === null ) {
					design._paintFaces();
					return design._say( line, Nino.adminUi.api.errorText( status, response, '/_admin/design/typefaces/error/list' ), 'nino-admin-error' );
				}

				// Only the latest answer is the state: one asked for before an install is the state before it
				if( stamp === design._facesStamp ) {
					design._faces = response;
					design._facesPage = 0;

					// A list loaded again starts at its first page, and that page at the top of its box: what the
					// box before was scrolled to was another list's (_paintAvailable() carries the scroll over)
					const box = dc.getElementById('design-faces-results');

					if( box !== null )
						box.scrollTop = 0;
				}

				design._paintFaces();

				if( refresh === true )
					design._say( line, response.refusal !== ''
						? design._refusal( response )
						: design._text( '/_admin/design/typefaces/msg/loaded', response.families.length ), response.refusal !== '' ? 'nino-admin-error' : '', response.refusal );
			} );
		},

		/**
		 *	Install a family of the list, or update it
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_installFace : function( slug ) {
			Nino.admin.design._faceAction( 'webfonts/install', slug, '/_admin/design/typefaces/error/install' );
		},

		/**
		 *	Remove an installed family
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_removeFace : function( slug ) {
			Nino.admin.design._faceAction( 'webfonts/remove', slug, '/_admin/design/typefaces/error/remove' );
		},

		/**
		 *	One action of the tab on one family. The buttons are switched off while it is on
		 *	its way, and the server's answer - the same as the list gives, after the change -
		 *	draws both halves again; the line of the action bar says what happened, or why not.
		 *	The selects of the roles list the families too, so design/list is asked again
		 *
		 *	@param		{string}	endpoint		webfonts/install or webfonts/remove
		 *	@param		{string}	slug
		 *	@param		{string}	failure			The text of what a refusal is told as: the reason is its %s
		 *
		 *	@return		void
		 */
		_faceAction : function( endpoint, slug, failure ) {

			const design = Nino.admin.design;

			if( design._facesBusy === true )
				return;

			const stamp = ++design._facesStamp;

			design._facesBusy = true;
			design._say( dc.getElementById('design-msg'), '' );
			design._paintFaces();

			design._apiCall( endpoint, { slug : slug }, function( status, response ) {

				const line = dc.getElementById('design-msg');

				design._facesBusy = false;

				if( status !== 200 || response === null ) {
					design._paintFaces();

					// A family a role still uses is told with the roles, in the words of the workbench (the
					// server names them as the params of the code); what else the server refused for, in its
					// own words; a failure that has none is told the way the workbench tells one
					const roles = response !== null && response.code === 'used' && Array.isArray( response.params )
						? response.params.map( function( role ) { return Nino.content.getText( '/_admin/design/font/role/'+ role ) } ).join( ', ' ) : null;
					const reason = response !== null && typeof response.error === 'string' && response.error !== ''
						? response.error : Nino.adminUi.api.errorText( status, response, '' );

					return design._say( line, roles !== null ? design._text( '/_admin/design/typefaces/error/used', roles ) : design._text( failure, reason ), 'nino-admin-error' );
				}

				if( stamp === design._facesStamp )
					design._faces = response;

				design._paintFaces();
				design._say( line, response.msg );
				design._relist();
			} );
		},

		/**
		 *	Take over what design/list says now of the families - after an install or a removal
		 *	the selects of the roles have one more or one less, and the state of the files
		 *	may be another one, since a family a role uses can have been updated. The selection
		 *	being edited is not touched; the Typefaces tab, where it is open, is drawn
		 *	again, since the roles and the settings of the repository it reads are what
		 *	design/list says
		 *
		 *	@param		{Function}	[then]		Called when the answer is taken over, and when it could not be: whoever
		 *																waits for the list goes on with the one it has
		 *
		 *	@return		void
		 */
		_relist : function( then ) {

			Nino.admin.design._apiCall( 'list', {}, function( status, response ) {

				if( status === 200 && response !== null && Nino.admin.design._data !== null ) {
					Nino.admin.design._data = response;
					Nino.admin.design._paintState();
					Nino.admin.design._paintFaces();
				}

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	The roles that have a family: those of the saved setup, as the server says, and those the selection
		 *	being edited gives it - a family one has been chosen for and not yet saved is as little to be removed
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		{Array}						Keys of data.roles, in their order
		 */
		_rolesOf : function( slug ) {

			const saved = ( ( Nino.admin.design._faces || {} ).uses || {} )[slug] || [];
			const edit = ( Nino.admin.design._edit || {} ).fonts || {};

			return ( ( Nino.admin.design._data || {} ).roles || [] ).filter( function( role ) {
				return saved.indexOf( role ) !== -1 || ( edit[role] || {} ).family === slug;
			} );
		},

		/**
		 *	The first half: the families the project has installed, one row each, then the
		 *	directories that are none - greyed, with the reason, and a button that removes them
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintInstalled : function( card ) {

			const design = Nino.admin.design;
			const faces = design._faces;

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/typefaces/installed/title') ) );

			if( faces === null )
				return;

			const slugs = Object.keys( faces.installed || {} );
			const broken = Object.keys( faces.broken || {} );

			if( slugs.length === 0 && broken.length === 0 )
				return card.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/installed/empty') ) );

			slugs.forEach( function( slug ) {
				card.appendChild( design._installedRow( slug, faces.installed[slug] ) );
			} );

			broken.forEach( function( slug ) {
				card.appendChild( design._brokenRow( slug, faces.broken[slug] ) );
			} );
		},

		/**
		 *	One installed family. Its name is set in the face of its first normal file, which
		 *	the project serves itself and so is loaded as the row is drawn. The button that removes
		 *	it is off while a role uses it, and its title names the roles - and so does the line
		 *	under the name
		 *
		 *	@param		{string}	slug
		 *	@param		{Object}	family			One entry of the answer's `installed`
		 *
		 *	@return		{Element}
		 */
		_installedRow : function( slug, family ) {

			const design = Nino.admin.design;
			const roles = design._rolesOf( slug );
			const used = design._text( '/_admin/design/typefaces/installed/used', roles.map( function( role ) {
				return Nino.content.getText('/_admin/design/font/role/'+ role );
			} ).join(', ') );

			const notes = [ design._note( [ design._weights( family.weights, family.axis ), family.version, family.license ].join(' · ') ) ];

			if( roles.length > 0 )
				notes.push( design._note( used ) );

			const row = design._faceRow( slug, family.name, notes );

			design._setFace( 'installed', slug, family.face, row.name );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/design/typefaces/installed/remove');
			remove.title = roles.length > 0 ? used : remove.textContent;
			// A row of buttons that all say Remove is read out with the name of its family
			remove.setAttribute( 'aria-label', remove.textContent+ ' - '+ family.name );
			remove.disabled = roles.length > 0 || design._facesBusy === true;
			remove.addEventListener( 'click', function() { design._removeFace( slug ) } );
			row.element.appendChild( remove );

			return row.element;
		},

		/**
		 *	A directory below assets/fonts/ that is no family: greyed out, with the reason - and the
		 *	button that removes it, which works whatever the reason is
		 *
		 *	@param		{string}	slug				The name of the directory
		 *	@param		{string}	reason
		 *
		 *	@return		{Element}
		 */
		_brokenRow : function( slug, reason ) {

			const design = Nino.admin.design;
			const row = design._faceRow( slug, slug, [ design._note( design._text( '/_admin/design/typefaces/installed/broken', reason ) ) ] );

			row.element.classList.add('design-face-row--off');

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/design/typefaces/installed/remove');
			remove.setAttribute( 'aria-label', remove.textContent+ ' - '+ slug );
			remove.disabled = design._facesBusy === true;
			remove.addEventListener( 'click', function() { design._removeFace( slug ) } );
			row.element.appendChild( remove );

			return row.element;
		},

		/**
		 *	The weights of a family as a row says them: the numbers, or 'variable 100-900' for a weight axis
		 *
		 *	@param		{Array}		weights			Numbers, and the axis of a file as '100 900'
		 *	@param		{Array}		[axis]			[ from, to ], where the family has one
		 *
		 *	@return		{string}
		 */
		_weights : function( weights, axis ) {

			if( Array.isArray( axis ) === true && axis.length === 2 )
				return Nino.admin.design._text( '/_admin/design/typefaces/weights/variable', axis[0]+ '-'+ axis[1] );

			return ( weights || [] ).map( function( weight ) {
				return String( weight ).indexOf(' ') === -1 ? String( weight )
					: Nino.admin.design._text( '/_admin/design/typefaces/weights/variable', String( weight ).replace( ' ', '-' ) );
			} ).join(', ');
		},

		/**
		 *	A row of the screen: the label a knob row has - the icon, and a box with the name over its
		 *	notes - and, on its far side, whatever button the caller puts there
		 *
		 *	@param		{string}	slug
		 *	@param		{string}	name
		 *	@param		{Array}		notes			Elements, see _note()
		 *
		 *	@return		{{element: Element, name: Element}}		The row, and the element the name is in: the one that is set in the face
		 */
		_faceRow : function( slug, name, notes ) {

			const row = dc.createElement('div');
			row.className = 'design-face-row';
			row.dataset.slug = slug;

			const label = dc.createElement('span');
			label.className = 'design-knob-label design-face-label';

			const icon = Nino.admin.design._icon('typefaces/family');

			if( icon !== null )
				label.appendChild( icon );

			const box = dc.createElement('span');
			box.className = 'design-knob-text';

			const title = dc.createElement('span');
			title.className = 'design-face-name';
			title.textContent = name;
			box.appendChild( title );

			notes.forEach( function( note ) { box.appendChild( note ) } );

			label.appendChild( box );
			row.appendChild( label );

			return { element : row, name : title };
		},

		/**
		 *	The line under the name of a row
		 *
		 *	@param		{string}	text
		 *
		 *	@return		{Element}
		 */
		_note : function( text ) {

			const small = dc.createElement('small');
			small.className = 'design-face-note';
			small.textContent = text;

			return small;
		},

		/**
		 *	A sentence where there is nothing to draw
		 *
		 *	@param		{string}	text
		 *	@param		{boolean}	[failure]		The sentence is why there is nothing
		 *
		 *	@return		{Element}
		 */
		_hint : function( text, failure ) {

			const p = dc.createElement('p');
			p.className = failure === true ? 'nino-admin-error' : 'nino-admin-hint';
			p.textContent = text;

			return p;
		},

		/**
		 *	The second half: the button that loads the list, and what the list is. Where the repository is switched
		 *	off, or has no key to be read with, it is one sentence; where it could not be read, a sentence of the
		 *	kind of refusal stands in the place of the list, and the reason is its title. With a list there are a
		 *	search field, the categories as a strip and the families, a page at a time, in a box that scrolls.
		 *	A card that is drawn again - after an install or a removal, or a list that was loaded - has its box
		 *	scrolled to where the box before it was
		 *
		 *	@param		{Element}	card
		 *
		 *	@return		void
		 */
		_paintAvailable : function( card ) {

			const design = Nino.admin.design;
			const faces = design._faces;

			/*	What design/list said when the panel was opened, until the screen has an answer of its own: the settings
				of the repository are changed in another panel of the same page, and the answer is read on every visit	*/
			const web = faces === null ? ( ( design._data || {} ).webfonts || {} ) : { url : faces.url !== '', key : faces.key === true };

			const before = dc.getElementById('design-faces-results');
			const scrolled = before === null ? 0 : before.scrollTop;

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/typefaces/available/title') ) );

			if( web.url !== true )
				return card.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/available/off') ) );

			if( web.key !== true )
				return card.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/available/nokey') ) );

			const bar = dc.createElement('div');
			bar.className = 'design-faces-load';

			const load = dc.createElement('button');
			load.type = 'button';
			load.id = 'design-faces-load';
			load.className = 'nino-admin-btn-secondary';
			load.textContent = Nino.content.getText('/_admin/design/typefaces/available/load');
			load.disabled = design._facesBusy === true;
			load.addEventListener( 'click', function() { design._loadFaces( true ) } );
			bar.appendChild( load );

			if( faces !== null && faces.fetched > 0 ) {
				const when = dc.createElement('span');
				when.className = 'design-faces-when';
				when.textContent = design._text( '/_admin/design/typefaces/available/loaded', design._when( new Date( faces.fetched * 1000 ).toISOString() ) );
				// Where the list came from
				when.title = faces.url;
				bar.appendChild( when );
			}

			card.appendChild( bar );

			if( faces === null )
				return;

			if( faces.refusal !== '' ) {

				const refusal = design._hint( design._refusal( faces ), true );

				// The reason is the server's, in English: what stands behind the sentence
				refusal.title = faces.refusal;

				return card.appendChild( refusal );
			}

			if( faces.fetched === 0 )
				return card.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/available/unloaded') ) );

			if( ( faces.families || [] ).length === 0 )
				return card.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/available/none') ) );

			const search = dc.createElement('input');
			search.type = 'search';
			search.id = 'design-faces-search';
			search.className = 'design-faces-search';
			search.placeholder = Nino.content.getText('/_admin/design/typefaces/available/search');
			search.setAttribute( 'aria-label', search.placeholder );
			search.value = design._facesQuery;
			search.addEventListener( 'input', function() {
				design._facesQuery = search.value;
				design._facesPage = 0;
				design._paintResults();
			} );
			card.appendChild( search );

			const categories = design._stripRow( 'typefaces/category', Nino.content.getText('/_admin/design/typefaces/available/category'), '',
				design.FACE_CATEGORIES.map( function( key ) {
					return { value : key, label : Nino.content.getText('/_admin/design/typefaces/category/'+ key ) };
				} ), design._facesCategory, function( key ) {
					design._facesCategory = key;
					design._facesPage = 0;
					design._paintResults();
				} );

			// Seven names are one row of the strip in a third of the screen, every word whole (see .design-faces-categories)
			categories.classList.add('design-faces-categories');
			card.appendChild( categories );

			// The rows of a page scroll in this box, and the pager stands after it in the card (see _paintResults())
			const results = dc.createElement('div');
			results.id = 'design-faces-results';
			card.appendChild( results );

			design._paintResults( results );
			results.scrollTop = scrolled;
		},

		/**
		 *	The sentence for a list that could not be loaded, in the words of the workbench: one for
		 *	each thing a refusal can be about (see Webfonts::REFUSALS in the feature's class). The
		 *	reason the server gives is English and goes beside it, as the title of what says it
		 *
		 *	@param		{Object}	faces			An answer of design/webfonts/list with a refusal
		 *
		 *	@return		{string}
		 */
		_refusal : function( faces ) {
			return Nino.content.getText('/_admin/design/typefaces/available/'+ faces.refusalKind );
		},

		/**
		 *	The families of the list that the search and the category let through, a page of them in
		 *	the box, and the pager after the box in the card - not in it: the box scrolls and the pager
		 *	does not, so Previous and Next are always in reach. The box is fitted to the window once it
		 *	has its rows (see _fitFaces()). Drawn alone as the words are typed, so that the field keeps
		 *	its focus
		 *
		 *	@param		{Element}	[results]		The box, for one that is built and not mounted yet
		 *
		 *	@return		void
		 */
		_paintResults : function( results ) {

			const design = Nino.admin.design;

			results = results || dc.getElementById('design-faces-results');

			if( results === null || results === undefined || design._faces === null )
				return;

			results.innerHTML = '';

			// A page starts at the top of its box, whatever the page before was scrolled to
			results.scrollTop = 0;

			// The pager of the page before goes with its rows
			const before = results.parentNode.querySelector('.design-faces-pager');

			if( before !== null )
				before.remove();

			// The rows of the page before are gone: what watched them has nothing left to watch
			if( design._facesObserver !== null )
				design._facesObserver.disconnect();

			const query = design._facesQuery.trim().toLowerCase();

			const found = ( design._faces.families || [] ).filter( function( entry ) {
				return ( design._facesCategory === 'all' || entry.category === design._facesCategory )
					&& ( query === '' || entry.family.toLowerCase().indexOf( query ) !== -1 );
			} );

			if( found.length === 0 ) {
				results.appendChild( design._hint( Nino.content.getText('/_admin/design/typefaces/available/nomatch') ) );

				// A sentence has no rows to fit the window to: the box lets go of the height it had
				return design._fitFaces();
			}

			const pages = Math.ceil( found.length / design.FACES_PER_PAGE );

			design._facesPage = Math.max( 0, Math.min( pages - 1, design._facesPage ) );

			found.slice( design._facesPage * design.FACES_PER_PAGE, ( design._facesPage + 1 ) * design.FACES_PER_PAGE ).forEach( function( entry ) {
				results.appendChild( design._availableRow( entry ) );
			} );

			if( pages > 1 )
				results.insertAdjacentElement( 'afterend', design._pager( pages ) );

			design._fitFaces();
		},

		/**
		 *	One family the repository offers: its name, which is set in its own face once the row is in
		 *	view, its weights and its licence - a link to where the files came from -, and on the far side
		 *	what can be done about it: install it; or, where the same version is there, that it is
		 *	installed; or, where another version is, update to the one of the list. A family the feature
		 *	brings itself is installed, in a way - the answer names those, and design/list does not: what
		 *	it says of the families has the installed ones among them
		 *
		 *	@param		{Object}	entry				One family of the list
		 *
		 *	@return		{Element}
		 */
		_availableRow : function( entry ) {

			const design = Nino.admin.design;
			const installed = ( ( design._faces || {} ).installed || {} )[entry.slug] || null;
			const brought = ( ( design._faces || {} ).builtin || [] ).indexOf( entry.slug ) !== -1;

			const licence = dc.createElement('a');
			licence.href = entry.source;
			licence.target = '_blank';
			licence.rel = 'noopener noreferrer';
			licence.title = entry.source;
			licence.textContent = entry.license;

			const separator = dc.createElement('span');
			separator.textContent = ' · ';

			const weights = dc.createElement('span');
			weights.textContent = design._weights( entry.weights );

			const note = dc.createElement('small');
			note.className = 'design-face-note';
			note.appendChild( weights );
			note.appendChild( separator );
			note.appendChild( licence );

			const row = design._faceRow( entry.slug, entry.family, [ note ] );

			// Set in its own face when the row comes into view: one file for a family, the preview the repository publishes
			row.element.dataset.preview = entry.preview;
			design._observe().observe( row.element );

			if( installed !== null && installed.version === entry.version || brought === true ) {
				const state = dc.createElement('span');
				state.className = 'design-face-state';
				state.textContent = Nino.content.getText('/_admin/design/typefaces/available/installed');
				row.element.appendChild( state );

				return row.element;
			}

			const install = dc.createElement('button');
			install.type = 'button';
			install.className = 'nino-admin-btn-primary';
			install.textContent = installed === null ? Nino.content.getText('/_admin/design/typefaces/available/install')
				: design._text( '/_admin/design/typefaces/available/update', entry.version );
			install.setAttribute( 'aria-label', install.textContent+ ' - '+ entry.family );
			install.disabled = design._facesBusy === true;
			install.addEventListener( 'click', function() { design._installFace( entry.slug ) } );
			row.element.appendChild( install );

			return row.element;
		},

		/**
		 *	Previous, the place in the pages, Next
		 *
		 *	@param		{number}	pages
		 *
		 *	@return		{Element}
		 */
		_pager : function( pages ) {

			const design = Nino.admin.design;

			const pager = dc.createElement('div');
			pager.className = 'design-faces-pager';

			const step = function( label, towards ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-btn-secondary';
				button.textContent = Nino.content.getText('/_admin/design/typefaces/page/'+ label );
				button.disabled = design._facesPage + towards < 0 || design._facesPage + towards > pages - 1;
				button.addEventListener( 'click', function() {
					design._facesPage += towards;
					design._paintResults();
				} );
				return button;
			};

			const place = dc.createElement('span');
			place.className = 'design-faces-place';
			place.textContent = design._text( '/_admin/design/typefaces/page/of', design._facesPage + 1, pages );

			pager.appendChild( step( 'previous', -1 ) );
			pager.appendChild( place );
			pager.appendChild( step( 'next', 1 ) );

			return pager;
		},

		/**
		 *	What loads the face of a row of the list when the row comes into view: one observer for the visit,
		 *	made when the first row is drawn. A row that enters the view is not watched any more, and sets
		 *	its name in the preview of its family
		 *
		 *	@return		{IntersectionObserver}
		 */
		_observe : function() {

			const design = Nino.admin.design;

			if( design._facesObserver === null )
				design._facesObserver = new wn.IntersectionObserver( function( entries ) {

					entries.forEach( function( seen ) {

						if( seen.isIntersecting !== true )
							return;

						design._facesObserver.unobserve( seen.target );

						design._setFace( 'available', seen.target.dataset.slug, seen.target.dataset.preview, seen.target.querySelector('.design-face-name') );
					} );
				} );

			return design._facesObserver;
		},

		/**
		 *	Set a name in the face of the family it is the name of. The face is a FontFace the document gets
		 *	once for each family of a kind, however often the row is drawn again - a page turned back
		 *	to, a search emptied - and it loads from the url it is given: the project's own file for an
		 *	installed family, the preview the repository publishes for one on offer
		 *
		 *	@param		{string}	kind				installed or available: the same family is two faces
		 *	@param		{string}	slug
		 *	@param		{string}	url
		 *	@param		{Element}	name				The element the name stands in
		 *
		 *	@return		void
		 */
		_setFace : function( kind, slug, url, name ) {

			const design = Nino.admin.design;
			const family = 'design-'+ kind+ '-'+ slug;

			if( design._facesLoaded[family] === undefined ) {

				const face = new wn.FontFace( family, 'url('+ JSON.stringify( url )+ ')' );

				design._facesLoaded[family] = face;
				dc.fonts.add( face );

				// A file that is not there, or not to be read from here, leaves the name in the face of the page
				face.load().catch( function() {} );
			}

			name.style.fontFamily = '"'+ family+ '", system-ui, sans-serif';
		},

		/**
		 *	Let go of the faces the names were set in, and of what watched the rows: the tab is left, and
		 *	a face is a file the browser holds
		 *
		 *	@return		void
		 */
		_dropFaces : function() {

			const design = Nino.admin.design;

			if( design._facesObserver !== null ) {
				design._facesObserver.disconnect();
				design._facesObserver = null;
			}

			Object.keys( design._facesLoaded ).forEach( function( family ) {
				dc.fonts.delete( design._facesLoaded[family] );
			} );

			design._facesLoaded = {};
		},

		/**
		 *	Whether the loader is on show: the Typefaces tab is the one that is open
		 *
		 *	@return		{boolean}
		 */
		_onInstall : function() {
			return Nino.admin.design._tab === 'typefaces';
		},

		/**
		 *	A tab with screens (see SCREENS): the strip of them and under it the box the screen that is open is
		 *	drawn into. The strip is the element a switch is made on, and it stays where it is while the screen
		 *	under it is drawn again (see _switchGroup())
		 *
		 *	@param		{string}	tab				A key of SCREENS
		 *	@param		{Element}	body			The box the column keeps for the open tab
		 *
		 *	@return		void
		 */
		_renderScreens : function( tab, body ) {

			const design = Nino.admin.design;

			body.appendChild( design._groupStrip( tab, design.SCREENS[tab] ) );

			const screen = dc.createElement('div');
			screen.id = 'design-screen';
			body.appendChild( screen );

			design._fillScreen( screen, tab );
		},

		/**
		 *	The screen that is open of a tab with screens, into the box that is kept for it: the Theme tab's, which is
		 *	the one that has them (see SCREENS). A screen is drawn again whenever it is opened, from what is known
		 *	then: the theme a create has just made is in the list
		 *
		 *	@param		{Element}	box
		 *	@param		{string}	tab				A key of SCREENS
		 *
		 *	@return		void
		 */
		_fillScreen : function( box, tab ) {

			if( tab === 'theme' )
				Nino.admin.design._renderTheme( box );
		},

		/**
		 *	The strip of the screens of a tab: a tablist of the workbench's make, one tab for each, the open
		 *	one selected, named by the tab it belongs to as the strip in the head is by the panel. A press on one
		 *	switches to it (see _switchGroup())
		 *
		 *	@param		{string}	tab				A key of SCREENS
		 *	@param		{Array}		keys			The screens, in the order they stand in
		 *
		 *	@return		{HTMLElement}
		 */
		_groupStrip : function( tab, keys ) {

			return Nino.admin.design._tabs( Nino.content.getText('/_admin/design/tab/'+ tab+ '/label'), 'design-groups', 'group',
				keys.map( function( key ) {
					return { key : key, label : Nino.content.getText('/_admin/design/group/'+ tab+ '/'+ key+ '/label') };
				} ), Nino.admin.design._groupOf( tab ), function( key ) {
					Nino.admin.design._switchGroup( tab, key );
				} );
		},

		/**
		 *	The screen of a tab with screens that is shown: the one chosen since the panel was opened, else
		 *	the first
		 *
		 *	@param		{string}	tab				A key of SCREENS
		 *
		 *	@return		{string}						'' for a tab that has none
		 */
		_groupOf : function( tab ) {

			const keys = Nino.admin.design.SCREENS[tab] || [];
			const chosen = Nino.admin.design._group[tab];

			return keys.indexOf( chosen ) !== -1 ? chosen : ( keys[0] || '' );
		},

		/**
		 *	Show another screen of a tab that has some. Only what stands under the strip is drawn again - the
		 *	screen, made afresh from what is known (see _fillScreen()): the strip is the element the switch was
		 *	made on, and the focus an arrow key put on a tab of it stays there (see _switchTab() for the strip in
		 *	the head).
		 *
		 *	A screen belongs to what is on screen and not to the selection, so nothing is marked as changed and
		 *	no preview is asked for - the page in the frame is the same under every screen, and the frame stays
		 *	on the place it showed. The screen is another height, which in one column moves the stage that stands
		 *	under it: the stage is fitted again. Where that gave the frame another height the page in it lays
		 *	itself out again and the place it showed moves, so it is put back there - the place of the last
		 *	change, else the anchor of the tab - once the page has heard the resize, as it is after a tab (see
		 *	_jumpFitted()). A screen that leaves the stage as tall as it was has nothing to put back and scrolls
		 *	nothing. The screen that is open already is not opened again, as the tab that is open is not (see
		 *	_switchTab())
		 *
		 *	@param		{string}	tab				A key of SCREENS
		 *	@param		{string}	group			A key of the screens of that tab
		 *
		 *	@return		void
		 */
		_switchGroup : function( tab, group ) {

			const design = Nino.admin.design;

			if( group === design._groupOf( tab ) )
				return;

			design._group[tab] = group;

			const screen = dc.getElementById('design-screen');

			if( screen === null )
				return;

			screen.innerHTML = '';
			design._fillScreen( screen, tab );

			// The stage stands under the screen in one column, so a screen of another height moves it: fitted
			// again, and the page in the frame is put back once it has laid itself out at the height
			// the fit gave it (see _jumpFitted())
			const frame = dc.getElementById('design-frame');
			const was = frame === null ? '' : frame.parentNode.style.height;

			design._fit();

			if( frame !== null && frame.parentNode !== null && frame.parentNode.style.height !== was )
				design._jumpFitted( frame, was );
		},

		/**
		 *	What the strip of a knob is made of: its label and its options, from the least to the most - the numbers
		 *	-2 to +2 of a scale, the three words that knob has for a choice. The kind is what design/list says of the
		 *	knob in its part
		 *
		 *	@param		{string}		part			The part the knob belongs to: content, buttons or components
		 *	@param		{string}		knob
		 *
		 *	@return		{Object}							{ name, options }, the options as { value, label, title }
		 */
		_knobStrip : function( part, knob ) {

			const base = '/_admin/design/knob/'+ part+ '/'+ knob;
			const kind = ( ( Nino.admin.design._data.kinds || {} )[part] || {} )[knob];
			const positions = Nino.admin.design._positions( kind );

			return {
				name 		: Nino.content.getText( base+ '/label' ),
				options	: kind === 'choice'
					? positions.map( function( position ) {
						const label = Nino.content.getText( base+ '/'+ Nino.admin.design._word( position ) );
						return { value : position, label : label, title : label };
					} )
					: Nino.admin.design._numbered( positions, 0 ),
			};
		},

		/**
		 *	One knob, as a strip. A scale is five numbers, -2 to +2, and the note under
		 *	its name says which way they go; a choice is three alternatives, each in the
		 *	words that knob has for it - "tight, normal, airy" is not the same sentence
		 *	as "sharp, normal, round". The kind is what design/list says of the knob
		 *	in its part
		 *
		 *	@param		{string}		part			The part the knob belongs to: content, buttons or components
		 *	@param		{string}		knob
		 *	@param		{boolean}		answered		Whether the chosen template declares it
		 *
		 *	@return		{Element}
		 */
		_knobRow : function( part, knob, answered ) {

			const base = '/_admin/design/knob/'+ part+ '/'+ knob;
			const strip = Nino.admin.design._knobStrip( part, knob );
			const name = strip.name;

			/*	The knob's own name and the terse note under it, both out of the
				panel's text files: a knob is one key in every locale. Where the
				template does not answer to it the note is the reason it is grey.
				An icon stands before them, for what the knob sets	*/
			const field = Nino.admin.design._stripRow( part+ '/'+ knob, name,
				answered === true ? Nino.content.getText( base+ '/note' ) : Nino.admin.design._text( '/_admin/design/hint/unanswered', name ),
				strip.options, ( Nino.admin.design._edit.knobs[part] || {} )[knob] || 0, function( position ) {
					Nino.admin.design._setStep( part, knob, position );
				}, answered === false );

			field.dataset.knob = knob;

			return field;
		},

		/**
		 *	Move a knob
		 *
		 *	@param		{string}		part			The part the knob belongs to
		 *	@param		{string}		knob			A knob of that part
		 *	@param		{number}		position	-2 to +2 for a scale, -1 to +1 for a choice
		 *
		 *	@return		void
		 */
		_setStep : function( part, knob, position ) {

			Nino.admin.design._edit.knobs[part][knob] = position;
			// A change is looked at where it shows: one target for it, which the jump
			// that follows the preview goes to
			Nino.admin.design._target = Nino.admin.design._anchorOf( 'knobs/'+ part+ '/'+ knob );
			Nino.admin.design._preview();
		},

		/**
		 *	The element a change of one control is looked at in: the id CONTROL_ANCHORS names for its key, else the one
		 *	OBJECT_ANCHORS names for the object it stands under, else the anchor of its tab. Where the control stands is
		 *	the table's (see _place()); how many rows its tab has is no part of it
		 *
		 *	@param		{string}		key				The key the control has in the table: 'size', 'knobs/content/quiet' ...
		 *
		 *	@return		{string}							The id of an element of the specimen
		 */
		_anchorOf : function( key ) {

			const design = Nino.admin.design;
			const place = design._place( key );

			return design.CONTROL_ANCHORS[key] || design.OBJECT_ANCHORS[ place[1] ] || design.ANCHORS[ place[0] ];
		},

		/**
		 *	What the brand as picked measures, as a line of its own under the row that picked it. Always
		 *	built, shown only while it applies: every colour knob moves the number in it, and the answer to
		 *	a preview updates the line in place rather than rebuilding a column that holds an open colour
		 *	picker - see _absorb().
		 *
		 *	brand and accent are the two surfaces with no contrast promise - they are the hex the picker
		 *	returned, byte for byte, so there is no lightness left to solve with. Everything a theme writes on
		 *	uses the -safe roles instead, and this is the one place the panel can say that the colour as
		 *	picked is not one of them
		 *
		 *	@return		{HTMLElement}
		 */
		_brandWarning : function() {

			const warn = dc.createElement('p');
			warn.id = 'design-brand-warning';
			warn.className = 'nino-admin-hint design-colour-warning';

			// By the node, not by its id: this column is not in the document yet on the first render
			Nino.admin.design._paintBrandWarning( warn );

			return warn;
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
		 *	What the strip of a knob of the palette is made of: its label and its options, from the least to the most - the
		 *	numbers -2 to +2 of a scale, which stand at distance from where it starts so that the framework's own is the 0, and
		 *	the names of the positions of a choice like Harmony, which are not a track
		 *
		 *	@param		{string}	key				harmony, temperature, saturation, contrast or depth
		 *	@param		{Object}	meta			One entry of Colours::choices()
		 *
		 *	@return		{Object}						{ name, options }, the options as { value, label, title }
		 */
		_colourStrip : function( key, meta ) {

			const base = '/_admin/design/colour/'+ key;

			let options;

			if( meta.kind === 'scale' ) {
				const positions = [];

				for( let position = 1; position <= meta.steps; position++ )
					positions.push( position );

				options = Nino.admin.design._numbered( positions, meta['default'] );
			}
			else
				// The position's own name is the label here, not a step word:
				// "Monochrom" and "Triadisch" are not less and more of anything
				options = ( meta.steps || [] ).map( function( name, index ) {
					const label = Nino.content.getText( base+ '/'+ ( index + 1 ) ) || name;
					return { value : index + 1, label : label, title : label };
				} );

			return { name : Nino.content.getText( base+ '/label' ), options : options };
		},

		/**
		 *	One colour knob. The same strip as a part's knob, over as many positions as
		 *	the knob publishes (see _colourStrip())
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

			const colours = Nino.admin.design._edit.colours || {};
			const strip = Nino.admin.design._colourStrip( key, meta );

			/*	A hex somebody typed overrides the whole knob - Colours::palette()
				takes the Secondary as given and never reaches for Harmony at all.
				So no position is lit while one is set: an active button there
				would claim a derivation that is not happening	*/
			const active = key === 'harmony' && String( colours.secondary || '' ) !== ''
				? null
				: ( colours[key] || meta['default'] );

			const field = Nino.admin.design._stripRow( 'colours/'+ key, strip.name, Nino.content.getText( '/_admin/design/colour/'+ key+ '/note' ),
				strip.options, active, function( position ) { Nino.admin.design._setColourKnob( key, position ) } );

			// What the stylesheet takes a row of the palette by: the row itself, not the place the table gives it
			field.classList.add('design-colour-knob');

			if( extra )
				field.appendChild( extra );

			return field;
		},

		/**
		 *	The mode of the palette: follow the visitor's setting, or be light, or
		 *	be dark - one select under a heading with its icon
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
				Nino.admin.design._icon('colours/mode') ) );

			const field = Nino.admin.design._select(
				'design-colour-mode', '', Nino.admin.design._modeOptions( meta ), String( ( edit.colours || {} ).mode || meta['default'] ),
				function( value ) {
					Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
					Nino.admin.design._edit.colours.mode = value;
					Nino.admin.design._target = Nino.admin.design._anchorOf( 'colours/mode' );
					Nino.admin.design._preview();
				} );

			field.querySelector('select').setAttribute( 'aria-label', Nino.content.getText('/_admin/design/colour/mode/label') );
			mode.appendChild( field );

			return mode;
		},

		/**
		 *	What the select of the mode offers, and what the head of the block of the palette says a mode is: a word for each
		 *	of the modes the palette has
		 *
		 *	@param		{Object}	meta		The mode entry of Colours::choices()
		 *
		 *	@return		{Array}					{ value, label }
		 */
		_modeOptions : function( meta ) {

			return ( meta.steps || [] ).map( function( name ) {
				return { value : name, label : Nino.content.getText('/_admin/design/colour/mode/'+ name ) };
			} );
		},

		_setColour : function( key, value ) {
			Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
			Nino.admin.design._edit.colours[key] = value;
			Nino.admin.design._target = Nino.admin.design._anchorOf( 'colours/'+ key );
			Nino.admin.design._redrawColours();
		},

		_setColourKnob : function( key, position ) {

			Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
			Nino.admin.design._edit.colours[key] = parseInt( position, 10 );
			Nino.admin.design._target = Nino.admin.design._anchorOf( 'colours/'+ key );

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
			where a knob only ever moves the frame. The row is the one the table
			puts the second colour in, with the Harmony it ends. Both then ask for
			a new stylesheet */
		_redrawColours : function() {

			const design = Nino.admin.design;
			const place = design._place('colours/secondary');
			const rows = design._rows( place[0] );
			const row = rows.filter( function( each ) { return each.object === place[1] } )[0];
			const old = dc.getElementById( design._rowId( place[0], place[1] ) );

			if( old !== null && row !== undefined )
				old.replaceWith( design._renderRow( place[0], row, design._blockOf( place[0], rows ) === row.object ) );

			design._preview();
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

			Nino.admin.design._takeStored( data );
			Nino.admin.design._render();
			Nino.admin.design._say( dc.getElementById('design-msg'), Nino.content.getText('/_admin/design/msg/reverted') );
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
		 *	chip in the action bar says it in one word. Foreign is any of the three
		 *	files - the stylesheet and the two frames - not being Design's, since a
		 *	header somebody edited is not an up to date design either
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
		 *	The chip in the action bar: the state of the files Design writes in one
		 *	word (see _stateKey()), and as its title what that word is about. It is
		 *	drawn with the screen, so it is drawn again by everything that draws the
		 *	screen again - a load, a save, an apply, a restore, a reset - and by
		 *	nothing else: a tab or a change on the screen touches no file
		 *
		 *	@param		{Element}		[chip]		The chip, for one that is built and not mounted yet
		 *
		 *	@return		void
		 */
		_paintState : function( chip ) {

			chip = chip || dc.getElementById('design-state-word');

			if( chip === null )
				return;

			const key = Nino.admin.design._stateKey();

			chip.dataset.state = key;
			chip.textContent = Nino.content.getText('/_admin/design/state/short/'+ key );
			chip.title = Nino.content.getText('/_admin/design/label/state')+ ': '+ chip.textContent;
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
				padding and this owns the rhythm between heading, toolbar and
				frame - and the gap the frame keeps from the action bar (see _room())	*/
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
			/*	A picture of the design, not a page to use: its links and buttons are out of the tab order - a
				walk of the keyboard would spend every one of them before it reached the bar - and out of the
				page for assistive technology; the panel scrolls it by script, which an inert frame allows. The
				title says what the frame is, for the one who passes it	*/
			frame.setAttribute( 'inert', '' );
			frame.setAttribute( 'tabindex', '-1' );
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

			// An inert frame lets the pointer through to the stage under it: the wheel is handed on to the page in it
			stage.addEventListener( 'wheel', function( event ) { Nino.admin.design._wheel( event, frame ) }, { passive : false } );

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

			/*	The stage is as tall as the window has room for under it (see _fit()),
				so a window that is resized asks for it again. Once for the visit,
				however often the screen is built	*/
			if( Nino.admin.design._bound === false ) {
				Nino.admin.design._bound = true;
				wn.addEventListener( 'resize', Nino.admin.design._fitLater );
			}

			return box;
		},

		/**
		 *	A turn of the wheel over the preview, turned on the page in the frame - which an inert frame no
		 *	longer takes by itself. By the unit the wheel counts in, and only while the page can still go
		 *	that way: at its end the wheel is left alone and goes on to the panel, as it did when the
		 *	frame took it
		 *
		 *	@param		{WheelEvent}	event
		 *	@param		{Element}			frame			The preview's frame
		 *
		 *	@return		void
		 */
		_wheel : function( event, frame ) {

			let doc = null;

			// A frame that cannot be reached is not the page it was: nothing to turn
			try {
				doc = frame.contentDocument;
			}
			catch( error ) {
				return;
			}

			if( doc === null || doc.defaultView === null )
				return;

			const view = doc.defaultView;
			const root = doc.documentElement;
			const unit = event.deltaMode === 1 ? 16 : ( event.deltaMode === 2 ? view.innerHeight : 1 );
			const top = event.deltaY * unit;
			const left = event.deltaX * unit;

			const down = top === 0 ? false : ( top < 0 ? view.scrollY > 0 : view.scrollY + view.innerHeight < root.scrollHeight - 1 );
			const across = left === 0 ? false : ( left < 0 ? view.scrollX > 0 : view.scrollX + view.innerWidth < root.scrollWidth - 1 );

			if( down === false && across === false )
				return;

			event.preventDefault();
			view.scrollBy( { top : top, left : left, behavior : 'instant' } );
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
			Nino.admin.design._refreshThemeChip();
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
						msg.textContent = Nino.adminUi.api.errorText( status, response, '/_admin/design/error/preview' );
						msg.title = msg.textContent;
					}
					return;
				}

				// The stage's height before the answer is taken over, for the jump to tell
				// whether the fit moved it
				const was = frame.parentNode === null ? '' : frame.parentNode.style.height;
				let swapped = false;

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
					// The stylesheet was swapped inside the frame that stands
					swapped = true;
				}

				// Fitted once the answer is taken over: the brand warning of the colours comes
				// and goes with it, and in one column that moves the stage
				Nino.admin.design._absorb( response );
				Nino.admin.design._fit();

				// A change was made on this tab, and where it can be seen - the tab's
				// anchor, or the place of the knob that was moved - is where the page goes
				// after every one: once it stands at the height the fit gave the stage
				// (see _jumpFitted())
				if( swapped === true ) {
					Nino.admin.design._jumpFitted( frame, was );

					if( Nino.admin.design._replays === true )
						Nino.admin.design._replay();
				}

				Nino.admin.design._replays = false;

				if( msg === null )
					return;

				const notes = response.notes || [];
				msg.className = notes.length === 0 ? 'nino-admin-hint' : 'nino-admin-error';
				msg.textContent = notes.map( function( note ) { return Nino.admin.design._noteText( note ) } ).join(' ');
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
		 *	that stand in view already - the hero, on the Motion tab where the
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
		 *	The specimen is one page with an element for each place the panel looks at (ids global,
		 *	type, content, cards, buttons, components, colours and, at its end, footer - see ANCHORS), and
		 *	the frame is the page of this very origin: the panel scrolls the
		 *	frame's own window by hand, which needs neither a message to the page
		 *	nor a hash it would have to read. Done after a tab is opened and after
		 *	every change, when the answer to the change is in the frame.
		 *
		 *	Three maps say where, all of them the panel's and the ids the page's. Opening a tab goes to the
		 *	anchor of the tab (ANCHORS). A change of a control goes to the place that control is looked at in,
		 *	which follows from the table (see _anchorOf()): the id CONTROL_ANCHORS names for its key - the quiet
		 *	and the loud variant to the typography block (#type), a header to the top of the page, a footer to its
		 *	foot, the others where their sets are shown, the controls of the palette to the grounds (#colours) -,
		 *	else the id OBJECT_ANCHORS names for the object it stands under - the page's to the top of the page
		 *	(#global), a section's to the first section (#content), a card's to the cards (#cards), the buttons' and
		 *	the fields and tables' to their blocks -, else the anchor of its tab. The place of a change is _target,
		 *	one for each change - it is put back to the tab's own when a tab is opened, and it is what is jumped to
		 *	until then.
		 *
		 *	The top of the page is the exception to the room for the header: the element with the id global is
		 *	the top of the page, header included - its anchor stands right below it -, and the footer is where the
		 *	page ends, so that choosing one shows what was chosen and not the top of a page that is hardly changed
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
				const aim = Nino.admin.design._target || Nino.admin.design.ANCHORS[tab];
				const target = doc.getElementById( aim );
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

				// The top of the page is the top of the page, header included - the anchor
				// stands right below it - and the footer is where the page ends: the
				// room for the header is no concern of an anchor at the foot
				const placed = target.getBoundingClientRect().top + ( view.scrollY || doc.documentElement.scrollTop || 0 );
				const top = aim === 'footer' ? placed : ( aim === 'global' ? 0 : placed - clear );

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
		 *	The stage takes its room first: it is as tall as the window has left
		 *	under it (see _room()), and is that tall when the port is measured. The
		 *	workbench owns the rest of the arithmetic (Nino.adminUi.scaleFrame): it
		 *	solves the height back through the scale, never magnifies past 1:1,
		 *	and watches the port with a ResizeObserver - so the frame follows
		 *	the rail folding away without anything here having to hear about it
		 *
		 *	The list of the typefaces on offer is fitted after the stage, for the same
		 *	reasons (see _fitFaces()): a screen that is drawn, a window that is resized
		 *	and a tab, or a screen of a tab, that is opened give it the room the window has left under it, as
		 *	they give the stage its own
		 *
		 *	It is asked for wherever the stage may have moved or the window changed: as
		 *	the screen is drawn, when the window is resized (see _fitLater()), when a
		 *	viewport is chosen, when a tab is opened, when another screen of a tab
		 *	is shown and when a preview has come back. The last three are for one column,
		 *	where the controls stand over the stage and a column of another height -
		 *	another tab or screen, the brand warning of the colours -
		 *	moves it; beside the controls the pane sticks, and the stage stays where it is
		 *
		 *	The frame is the last thing on the page, and the page clears its foot for
		 *	the action bar at the height the workbench declares for it. A bar that
		 *	wraps, as it does on a phone, is taller, and would cover the foot of the
		 *	frame at the end of the page: the layout takes the difference
		 *
		 *	@return		void
		 */
		_fit : function() {

			const frame = dc.getElementById('design-frame');

			if( frame === null || frame.parentNode === null )
				return;

			const room = Nino.admin.design._room( frame.parentNode );

			if( room !== null )
				frame.parentNode.style.height = room+ 'px';

			const layout = dc.getElementById('design-layout');

			if( layout !== null ) {
				const bar = Nino.admin.design._bar();
				const over = Math.round( bar.height - bar.declared );
				layout.style.paddingBottom = over > 0 ? over+ 'px' : '';
			}

			// Asked afresh every time: the width is a choice, and scaleFrame
			// closes over the one it was given
			Nino.adminUi.scaleFrame( frame, frame.parentNode, Nino.admin.design._width );

			Nino.admin.design._fitFaces();
		},

		/**
		 *	Fit the list of the typefaces on offer to the window, the way the stage is: the box
		 *	the rows of a page stand in is as tall as the window has room for under it, and the
		 *	rows that are more than that scroll in the box - not the page, which a page of 24 rows
		 *	would otherwise be scrolled for. The room is the height of the window less the top of
		 *	the box with the page scrolled to the top, less the action bar, less the pager, which
		 *	stands after the box and so stays in reach, and less the gap the stack of the preview
		 *	keeps from the bar: the pager ends that gap above the bar, where the stage does (see
		 *	_room(), whose bar, gap and scroll of the page these are).
		 *
		 *	Never under five rows: five times the height of the first, and the four hairlines
		 *	between them - 11 rem where the row has no height to measure. Beside the preview, in
		 *	two columns, that is all a low window gets: five rows, and the page scrolls by what
		 *	they overhang - the whole page scrolled for the list is what the box is there to
		 *	spare. In one column the box stands under the controls and may be far down: where
		 *	the room is less than five rows there, the box is made for the window it fills when
		 *	the page is scrolled to its card, with the top of the card at the top of the window -
		 *	the window less how far the box stands inside the card, so that the search and the
		 *	categories are in view with it, less the bar, the gap and the pager. That is the rule
		 *	_room() has for the stage, with the card for the pane.
		 *
		 *	A box that holds the sentence of no match has no rows to fit the window to: its height
		 *	is cleared, and the least the stylesheet gives it is what it has. A screen that is not
		 *	open has no box to give room to. Asked for wherever the box has new rows
		 *	(_paintResults()) and by _fit(), so that a box that was drawn before the column was in
		 *	the document is fitted once it is
		 *
		 *	@return		void
		 */
		_fitFaces : function() {

			const design = Nino.admin.design;
			const box = dc.getElementById('design-faces-results');

			if( box === null )
				return;

			const first = box.querySelector('.design-face-row');

			if( first === null ) {
				box.style.height = '';
				return;
			}

			const rect = box.getBoundingClientRect();

			// A pane that is not shown has no box to give room to
			if( rect.width === 0 && rect.height === 0 )
				return;

			const card = box.parentNode;
			const pager = card.querySelector('.design-faces-pager');
			const frame = dc.getElementById('design-frame');
			const pane = dc.getElementById('design-preview');
			// Beside the preview the pane sticks (see #design-preview in admin.css); under the controls it does not
			const beside = pane !== null && wn.getComputedStyle( pane ).position === 'sticky';
			const row = first.getBoundingClientRect().height;
			const bar = design._bar().height;
			const gap = frame === null || frame.parentNode === null ? 0 : design._gap( frame.parentNode );
			const least = row > 0 ? 5 * row + 4 : 11 * ( parseFloat( wn.getComputedStyle( dE ).fontSize ) || 16 );

			// What stands under the box up to the foot of the pager, and the room the card keeps between them
			const foot = pager === null ? rect : pager.getBoundingClientRect();
			const under = foot.top + foot.height - rect.top - rect.height;

			// How far the box stands inside its card, for the card to be brought to the top of the window
			const inside = rect.top - card.getBoundingClientRect().top;

			const room = wn.innerHeight - ( rect.top + design._scrolled( box ) ) - bar - gap - under;

			box.style.height = Math.max( least, room >= least || beside === true ? room : wn.innerHeight - inside - bar - gap - under )+ 'px';
		},

		/**
		 *	How tall the action bar is, in pixels: the height the workbench declares
		 *	for it (--nino-admin-actionbar-height, 4.25 rem as shipped), which is what
		 *	the page clears the foot of its content by, and the height it has - more
		 *	where it wraps, as it does on a phone
		 *
		 *	@return		{{declared: number, height: number}}
		 */
		_bar : function() {

			const root = wn.getComputedStyle( dE );
			const value = String( root.getPropertyValue('--nino-admin-actionbar-height') );
			const declared = ( parseFloat( value ) || 4.25 ) * ( /px\s*$/.test( value ) === true ? 1 : parseFloat( root.fontSize ) || 16 );
			const line = dc.getElementById('design-msg');
			let height = declared;

			if( line !== null && line.parentNode !== null && typeof line.parentNode.getBoundingClientRect === 'function' )
				height = Math.max( height, line.parentNode.getBoundingClientRect().height );

			return { declared : declared, height : height };
		},

		/**
		 *	The gap the stack of the preview keeps from the action bar, in pixels: the row gap of
		 *	the stack the stage stands in, which is the room between the foot of the stage and the
		 *	bar once the stage is as tall as the window has room for. What is fitted to the window
		 *	ends that gap above the bar, the stage (see _room()) and the list of the typefaces
		 *	(see _fitFaces()) alike
		 *
		 *	@param		{Element}		stage		The box the frame is scaled into
		 *
		 *	@return		{number}
		 */
		_gap : function( stage ) {
			return parseFloat( wn.getComputedStyle( stage.parentNode ).rowGap ) || 0;
		},

		/**
		 *	How far the page has been scrolled over an element, in pixels: the scroll of every
		 *	element it stands in, which is the pane that scrolls in a wide window and the window
		 *	itself in a narrow one. Added to the top the element has where it stands, it is the
		 *	top it has with the page scrolled to the top
		 *
		 *	@param		{Element}		node
		 *
		 *	@return		{number}
		 */
		_scrolled : function( node ) {

			let by = 0;

			for( let up = node.parentNode; up !== null && up !== undefined; up = up.parentNode )
				by += up.scrollTop || 0;

			return by;
		},

		/**
		 *	How tall the stage can be: the height of the window less the top of the
		 *	stage, less the action bar, which stands over the foot of the window, and
		 *	less the gap the stack keeps between its parts - so the frame ends that gap
		 *	above the bar (see _bar() for how tall that is). Never under 16 rem: a
		 *	window too low for that is scrolled.
		 *
		 *	The top of the stage is where it stands with the page scrolled to the top.
		 *	The preview of a wide window sticks to the top of its pane once the controls
		 *	are scrolled, and a narrow one moves up the window with the page, so a stage
		 *	measured where it happens to be would have another height with every step of
		 *	that. Where this leaves less than the least - on a phone the preview is
		 *	under the controls, below the foot of the window - the stage is made for the
		 *	window it fills when the page is scrolled to it, with the top of its pane at
		 *	the top of the window
		 *
		 *	@param		{Element}		stage
		 *
		 *	@return		{?number}					Pixels; null where the stage is not on screen
		 */
		_room : function( stage ) {

			const rect = stage.getBoundingClientRect();

			// A pane that is not shown has no stage to give room to
			if( rect.width === 0 && rect.height === 0 )
				return null;

			const rem = parseFloat( wn.getComputedStyle( dE ).fontSize ) || 16;
			const bar = Nino.admin.design._bar().height;
			const gap = Nino.admin.design._gap( stage );
			const pane = dc.getElementById('design-preview');
			const layout = dc.getElementById('design-layout');
			const least = 16 * rem;

			let top = rect.top;
			let inside = null;

			if( pane !== null && layout !== null && typeof pane.getBoundingClientRect === 'function' && typeof layout.getBoundingClientRect === 'function' ) {

				// The pane has the top of its row - the layout's - while the page is not scrolled,
				// and sticks to the top of the scroller once it is, in a wide window; in a narrow
				// one it is under the controls and has no other top than its own
				const paneRect = pane.getBoundingClientRect();
				const sticks = wn.getComputedStyle( pane ).position === 'sticky';

				inside = rect.top - paneRect.top;
				top = ( sticks === true ? layout.getBoundingClientRect().top : paneRect.top ) + inside;

				// ...less how far the page has been scrolled, by the pane that scrolls in a wide
				// window or by the window itself
				top += Nino.admin.design._scrolled( stage );
			}

			const room = wn.innerHeight - top - bar - gap;

			return Math.max( least, room >= least || inside === null ? room : wn.innerHeight - inside - bar - gap );
		},

		/**
		 *	Fit the stage again once the window has stopped being resized. The wait is
		 *	the previews' own, for the same reason: one answer to the end of the
		 *	movement and not one to every step of it
		 *
		 *	@return		void
		 */
		_fitLater : function() {
			wn.clearTimeout( Nino.admin.design._fitTimer );
			Nino.admin.design._fitTimer = wn.setTimeout( Nino.admin.design._fit, 150 );
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
		 *	Say something in the line of the action bar. It is one line, cut where
		 *	the bar has no more room for it, so all of it is its title as well
		 *
		 *	@param		{?Element}	line			#design-msg; nothing is said where there is none
		 *	@param		{string}		text			'' empties the line
		 *	@param		{string}		[kind]		The class it takes: nino-admin-error for a failure,
		 *															nino-admin-hint for a note beside an answer,
		 *															none for the answer itself
		 *	@param		{string}		[detail]	What stands behind the sentence, the server's words: in brackets after it, in the title
		 *
		 *	@return		void
		 */
		_say : function( line, text, kind, detail ) {

			if( line === null )
				return;

			line.className = kind || '';
			line.textContent = text;
			line.title = detail ? text+ ' ('+ detail+ ')' : text;
		},

		/**
		 *	Store the selection, and - when asked - apply it afterwards.
		 *	Two steps rather than one: a draft is not a website, and the
		 *	screen stays honest about which of the two just happened.
		 *
		 *	Applying asks first, every time. design/plan says what would happen
		 *	to each of the three files, the dialog says it in words (see
		 *	_askApply()), and only a yes sends the apply - with `force` where a
		 *	file is not Design's, which the dialog has just said it is about to
		 *	replace. The same question is what gets a project out of a stylesheet
		 *	that is Design's beside a header that is not, which used to be refused
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
			Nino.admin.design._say( msg, '' );

			Nino.admin.design._apiCall( 'save', edit, function( status, response ) {

				if( status !== 200 || response === null ) {
					btn.disabled = false;
					Nino.admin.design._say( msg, Nino.adminUi.api.errorText( status, response, '/_admin/design/error/save' ), 'nino-admin-error' );
					return finish( false );
				}

				// The draft is stored either way; what asked for it may go on
				// while the screen is drawn from it again
				finish( true );

				if( apply === false ) {
					btn.disabled = false;
					return Nino.admin.design.init( function() {
						Nino.admin.design._say( dc.getElementById('design-msg'), Nino.content.getText('/_admin/design/msg/saved') );
					} );
				}

				Nino.admin.design._apiCall( 'plan', {}, function( planStatus, planned ) {

					if( planStatus !== 200 || planned === null ) {
						btn.disabled = false;
						Nino.admin.design._say( msg, Nino.adminUi.api.errorText( planStatus, planned, '/_admin/design/error/apply' ), 'nino-admin-error' );
						return;
					}

					const files = planned.files || [];

					Nino.admin.design._askApply( files, function( yes ) {
						Nino.admin.design._applyAnswered( yes, files, btn, msg );
					} );
				} );
			} );
		},

		/**
		 *	What the answer to the question before an apply does: a no leaves the draft saved and the
		 *	website as it was, a yes sends the apply - with `force` where a file is not Design's, which
		 *	the dialog has just said it is about to replace - and says what happened in the line of the bar
		 *
		 *	@param		{boolean}		yes
		 *	@param		{Array}			files					design/plan's answer
		 *	@param		{Element}		btn						The button that asked, disabled until this is over
		 *	@param		{Element}		msg
		 *
		 *	@return		void
		 */
		_applyAnswered : function( yes, files, btn, msg ) {

			/*	The screen is drawn again from the answer, and the button that asked is gone with it - the dialog gave
				the focus back to it while it was still switched off, which a button does not take. The new one has it	*/
			const back = function( text ) {

				Nino.admin.design._say( dc.getElementById('design-msg'), text );

				const again = dc.getElementById('design-apply');

				if( again !== null )
					again.focus();
			};

			// The draft is saved either way; only the website waits
			if( yes === false ) {
				btn.disabled = false;
				return Nino.admin.design.init( function() { back( Nino.content.getText('/_admin/design/msg/cancelled') ) } );
			}

			const force = files.some( function( file ) { return file.exists === true && file.state !== 'ours' } );

			Nino.admin.design._apiCall( 'apply', { force : force }, function( applyStatus, applied ) {

				btn.disabled = false;

				if( applyStatus !== 200 ) {
					Nino.admin.design._say( msg, Nino.adminUi.api.errorText( applyStatus, applied, '/_admin/design/error/apply' ), 'nino-admin-error' );
					btn.focus();
					return;
				}

				const lost = [];
				files.forEach( function( file ) {
					( file.lost || [] ).forEach( function( token ) { lost.push( token ) } );
				} );

				const said = [ Nino.content.getText('/_admin/design/msg/applied') ];

				if( force === true )
					said.push( Nino.content.getText('/_admin/design/msg/takenover') );

				if( lost.length > 0 )
					said.push( Nino.admin.design._text( '/_admin/design/msg/lost', lost.join(', ') ) );

				Nino.admin.design.init( function() { back( said.join(' ') ) } );
			} );
		},

		/**
		 *	The question before an apply, in the workbench's own dialog (Nino.adminUi.choiceDialog():
		 *	modal, Escape says no, the focus goes back to the button): what happens is in words - the
		 *	sets that were replaced since the setup was read, which files of the website are written,
		 *	which of them are not Design's, which shortcodes the new frames lose and that the present
		 *	state stays as the previous version - and the paths of the files stand in a <details> under
		 *	them, closed. Where the browser has no <dialog> the words are asked as a confirm()
		 *
		 *	The dialog has no place for a <details>, so it is put in behind the text the dialog has
		 *	drawn, which the dialog's own aria-describedby names; a dialog that was drawn otherwise gets
		 *	the question without the paths
		 *
		 *	@param		{Array}			files					design/plan's answer
		 *	@param		{Function}	then					Called with true for an apply, false for none - and for a question that could not be asked
		 *
		 *	@return		void
		 */
		_askApply : function( files, then ) {

			const design = Nino.admin.design;

			const asked = Nino.adminUi.choiceDialog( {
				title 		: Nino.content.getText('/_admin/design/label/apply'),
				message 	: design._confirmText( files ),
				choices 	: [
					{ value : 'apply', label : Nino.content.getText('/_admin/design/confirm/yes'), kind : 'primary' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				],
				onChoose 	: function( value ) { then( value === 'apply' ) },
			} );

			if( asked === false )
				return then( false );

			const text = dc.getElementById('nino-admin-choice-text');

			if( text === null )
				return;

			// One line to a fact: the line breaks of the text are the breaks of the lines
			text.classList.add('design-confirm-text');
			text.parentNode.insertBefore( design._confirmFiles( files ), text.nextSibling );
		},

		/**
		 *	The question before an apply, one line per fact and every file by what it is for, never
		 *	by its path (see _confirmFiles()): the sets that were replaced since the setup was read,
		 *	which files are rewritten, which of them Design did not write, which shortcodes the new
		 *	frames lose, and that the present state stays as the previous version - with the date of
		 *	the version that keeping it replaces. Built here from the fragments in the text files;
		 *	none of them carries a line break, and the lines are joined with one
		 *
		 *	@param		{Array}		files					design/plan's answer
		 *
		 *	@return		{string}
		 */
		_confirmText : function( files ) {

			const design = Nino.admin.design;
			const words = function( list ) {
				return list.map( function( file ) { return design._fileWord( file ) } ).join(', ');
			};

			const lines = design._replaced();

			lines.push( design._text( '/_admin/design/confirm/apply', words( files ) ) );

			const foreign = files.filter( function( file ) { return file.exists === true && file.state !== 'ours' } );

			if( foreign.length > 0 )
				lines.push( design._text( '/_admin/design/confirm/foreign', words( foreign ) ) );

			files.forEach( function( file ) {
				( file.lost || [] ).forEach( function( token ) {
					lines.push( design._text( '/_admin/design/confirm/lost', token ) );
				} );
			} );

			// Nothing is kept where nothing would change, and so nothing is replaced
			if( files.some( function( file ) { return file.exists === true && file.changes === true } ) ) {

				lines.push( Nino.content.getText('/_admin/design/confirm/copy') );

				const previous = design._data.previous;

				if( previous )
					lines.push( design._text( '/_admin/design/confirm/replaces', design._when( previous.at ) ) );
			}

			return lines.join('\n');
		},

		/**
		 *	What reading the setup replaced - a theme that is not there, a template that is not in the
		 *	library - as far as it is still true of the draft: the lines the dialog before an apply opens
		 *	with. The templates are one line, those that were not there and those that stand in their
		 *	place, by the names the screen has for them. A note the draft has moved past - another
		 *	template chosen, a theme named since - is not said
		 *
		 *	@return		{Array}						Sentences, none where nothing was replaced
		 */
		_replaced : function() {

			const design = Nino.admin.design;
			const edit = design._edit;
			const lines = [];
			const gone = [];
			const used = [];

			design._notes.forEach( function( note ) {

				if( note === null || typeof note !== 'object' || Array.isArray( note.params ) === false )
					return;

				if( note.code === 'design_theme_missing' && edit.theme === '' )
					lines.push( design._noteText( note ) );

				if( note.code === 'design_set_missing' && edit.parts[ note.params[0] ] === note.params[2] ) {
					gone.push( note.params[1] );
					used.push( design._setName( note.params[0], note.params[2] ) );
				}
			} );

			// The names carry commas of their own ("Classic, default"), so each is quoted, as the notes quote them
			const quote = ( document.documentElement.lang || '' ).indexOf( 'de' ) === 0 ? function( name ) { return '\u201e'+ name +'\u201c' } : function( name ) { return '"'+ name +'"' };
			if( gone.length > 0 )
				lines.unshift( design._text( '/_admin/design/confirm/replaced', gone.map( quote ).join(', '), used.map( quote ).join(', ') ) );

			return lines;
		},

		/**
		 *	A file Design writes, by what it is for: the look, the header, the footer. The file of a part
		 *	that has no word is told by its name
		 *
		 *	@param		{Object}	file			One of design/plan's files: target, part
		 *
		 *	@return		{string}
		 */
		_fileWord : function( file ) {
			return Nino.content.getText('/_admin/design/confirm/file/'+ ( file.part || 'look' ) ) || Nino.admin.design._name( file.target );
		},

		/**
		 *	The paths of the files an apply writes, closed in a <details> under the question: each with
		 *	what happens to it, in words
		 *
		 *	@param		{Array}		files					design/plan's answer
		 *
		 *	@return		{Element}
		 */
		_confirmFiles : function( files ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;
			const details = ui.el( 'details', 'design-confirm-files' );
			const list = ui.el( 'ul', 'design-confirm-list' );

			details.appendChild( ui.el( 'summary', '', Nino.content.getText('/_admin/design/confirm/files') ) );

			files.forEach( function( file ) {

				const state = file.exists !== true ? 'missing' : ( file.changes === false ? 'same' : file.state );
				const item = ui.el( 'li', '' );

				// The space is the span's own: a screen reader reads the path and what happens to it as two words
				item.appendChild( ui.el( 'code', '', design._name( file.target ) ) );
				item.appendChild( ui.el( 'span', 'design-confirm-state', ' '+ Nino.content.getText('/_admin/design/confirm/state/'+ state ) ) );
				list.appendChild( item );
			} );

			details.appendChild( list );

			return details;
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
					Nino.admin.design._say( dc.getElementById('design-msg'), Nino.adminUi.api.errorText( status, response, '/_admin/design/error/restore' ), 'nino-admin-error' );
					return;
				}

				Nino.admin.design.init( function() {
					// What the slot did not hold, or could not keep, is told
					// beside the answer: the site has changed either way
					const notes = ( response.notes || [] ).map( function( note ) { return Nino.admin.design._noteText( note ) } );
					Nino.admin.design._say( dc.getElementById('design-msg'),
						[ Nino.content.getText('/_admin/design/msg/restored') ].concat( notes ).join(' '), notes.length > 0 ? 'nino-admin-hint' : '' );
				} );
			} );
		},
	};

	/*	The shell asks Save, Discard or Cancel before a log out or a language
		change would lose a selection nobody has stored, and a reload gets
		the browser's own question. Save stores the draft - applying it is its
		own question	*/
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

			Nino.admin.design._takeStored( Nino.admin.design._data );
			Nino.admin.design._render();
		},
	} );

	Nino.events.bindCallback( 'ready', Nino.admin.design.init );

})(window, document, document.documentElement, document.body);
