/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Design					The feature's /_admin panel, "Design": a pane of three tabs - Themes, Typefaces and the
 *													Settings tab the kernel attaches - over one setup, drawn by one script of two namespaces,
 *													as the shell asks for a screen by the uri of its tab (Nino.admin[<uri>]):
 *													Nino.admin.design is the panel's own screen, which the strip calls Themes, and holds what
 *													the screen has in common with the setup - the state, the preview and the bar -,
 *													Nino.admin.design.views is the setup by its objects and its aspects, which the shell never asks for,
 *													and Nino.admin['design-typefaces'] is the loader.
 *													The panel's screen has two modes. The first is the list of the themes, the library
 *													that keeps the whole setup under a name with a css of its own: one row for each theme,
 *													with its chips, its description and the buttons Load, Duplicate, Export, Delete (a theme
 *													of the project) and Edit, a pencil on the row of a theme of the project that turns its
 *													name and description into two fields to rename it, and under the list the field that takes
 *													a theme file in. Edit opens the second mode, the setup by its objects, of the theme of the
 *													row - at once for the theme that is loaded, whose draft stays as it is, else after it was
 *													loaded -, and the bar on top of the pane begins with Back to list, which returns to the
 *													list and discards nothing: the draft stays, and the chip says so. Which of the two was
 *													open is remembered while the panel lives. The chip "changed", which says that the screen
 *													differs from the loaded theme, stands in the kernel's button of the strip that reads
 *													Themes, in both modes; Save theme, which puts the screen into the loaded theme of the
 *													project, stands in the action bar while the views are open.
 *													The views draw the setup in two axes, the objects it is set on and the aspects of the design,
 *													and the objects come first: on top, a strip of tabs with the page, the header, the footer,
 *													a section, a card, the buttons, the lists and the tables and, after them, each feature that
 *													brings variants under its name - below 64rem, the width where the rail of the workbench
 *													gives its links up for a select, the same choice is a select - and under it the aspects of the
 *													object that is open, an accordion of one open block: Base, Text, Surface, Colour and
 *													Motion, and for the page CSS as well, in that order and only those the object has a
 *													control on. Base has the choice of the set of the part of the object - the header, the
 *													footer, the section, the card, the buttons, the lists or the tables - with its name, its
 *													description and the word of the one chosen, under it a select for each style the template brings -
 *													"None" first, then the css files that stand beside the template, each under its name, and the
 *													description of the one chosen under it - and the strips the table puts there, such as the position and the
 *													call to action of the header; Text has the root size and the five font
 *													roles of the page and the sizes of the titles and a switch for the case of the thing - the
 *													navigation, a title, a subtitle, a button -, which is a knob of two positions; Surface the
 *													width, the whitespace and the two radii of the page and the space, the corners and the lines of the
 *													others, a strip of five positions or of three words for each knob, greyed out where the chosen
 *													template does not answer to it; Colour the two colours, the five knobs and the mode the palette
 *													is solved from under the page and the one knob each of a section, a card and the tables has of its
 *													own, and Motion the animation of the page and the hover of a card and of the buttons. The head
 *													of a closed aspect names, small and muted, the controls that stand elsewhere than at the
 *													defaults, and in Base the template that is chosen. CSS is one field: the css of the theme, which
 *													every input mirrors into the preview and which is saved with the draft and with the theme.
 *													A feature that brings variants of its own (Setup::features()) is an object like the others:
 *													in Base the choice of its variant and the styles it declares, and in each aspect its knobs
 *													are put on by their heads the knobs of the chosen variant - a scale and a choice as the knobs of
 *													a part are, a ground and an ink as a select of the surfaces and of the tones of the
 *													palette, a colour as two inputs, a light half and a dark half. The choices are part of
 *													the setup, and of a theme of the project.
 *													Typefaces, the loader, has no preview and no bar and keeps nothing in the setup - the
 *													families the project has installed, and the signed list of those it can install, which
 *													is read when its button is pressed: an install or a removal happens when it is pressed,
 *													and is no part of the draft.
 *													The compile turns all of it into assets/theme.css (see Modules\Design\Admin beside this
 *													file). Which aspect, which object and which place in its row a control has is the table's
 *													(the `layout` design/list hands over, see Setup::LAYOUT): the script draws from it and
 *													decides nothing of the kind - the tabs are its objects, the blocks under them its tabs, transposed.
 *													The bar of the objects, with Back to list and the strip of the tabs or the select, stands
 *													at the top of the pane of the tab, under the strip of the shell.
 *													Beside the list and the views is one specimen page of the selection, under
 *													a head with the viewport switch, the switch that lets the frame follow and the reload;
 *													the frame fills the window down to the action bar and scrolls to the anchor of the open
 *													object, or to where a change of a control shows. The bar holds what the panel says - what
 *													a save or an apply did, or why not - and the state of the files in a word. Ships with
 *													the feature and is loaded exactly while it is active.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	/*	The panel: Design, in the rail, and the screen of its own, which the strip on top of the pane calls Themes. It holds
		the state, which is one: the data the library and the stored setup gave, the working copy the screen edits, the theme
		it was taken from, the draft - and the preview and the bar, which the screen stands on in both of its modes: the list
		of the themes, and the setup by its objects (Nino.admin.design.views). The loader of the typefaces
		(Nino.admin['design-typefaces']) has neither and keeps nothing here but what it reads. The shell asks each of the
		two for its screen by the uri of its tab (see script.js, show())	*/
	Nino.admin.design = {

		_ready	: false,
		/*	Which of the two modes of the screen is drawn: 'themes', the list of the themes, or 'setup', the setup by its objects.
			There is one preview and one bar and one mount, and the mode is remembered while the panel lives - the first
			time the screen is shown it is the list. The loader, which has neither, leaves this as it was and the draft with it	*/
		_screen	: 'themes',
		// What the library offers and what is chosen - the answer of design/list
		_data		: null,
		// The working copy the screen edits. Leaving without saving changes
		// nothing on disk
		_edit		: null,
		/*	What reading the setup replaced or dropped, as the server told it: a note is a sentence, or an object - a
			code and the params of it, the way a refusal is answered (see _noteText()). The list over the open tab
			draws them, and a theme that is loaded or a draft that is reset gives them anew: they belong to what was read	*/
		_notes		: [],

		/*	The list of the themes. The theme the working copy was taken from or kept in, as the
			server describes it - slug, name, description, shipped, setup, css - against
			which the chip "changed" compares the screen, and null where it has none. The slug of
			the theme whose row is a pair of fields for its name and its description, '' where none
			is, and what is typed in them, which a redraw of the list keeps. Whether an action of
			the list is on its way, with the count that tells which one is the latest, the way
			the loader holds its own	*/
		_active		: null,
		_themeRename	: '',
		_themeFields	: { name : '', description : '' },
		_themeBusy	: false,
		_themeStamp	: 0,
		// The id of the control that had the focus when the controls were switched off: a disabled control drops it, and has it back when they are on again (see _themeWork())
		_busyFocus	: '',

		// Where the list of the themes is looked at: the top of the page, where a loaded theme shows all of what it is
		THEMES_ANCHOR	: 'global',

		/*	The id of the element on the specimen page the frame scrolls to when an object is opened (see _jump()), and where a
			change of one control is looked at, by the object it stands under (see Setup::OBJECTS): the page at the
			top of it (#global) - the root size and the roles of the fonts as much as the width, the corners and the
			animation -, a header at the top of it and a footer at its foot (#footer, the id the page has at its end), a section in the first section of the
			page, which the hero is (#content), the tiles in their own section (#tiles), a card in the cards (#cards), the buttons in their block and the lists
			and the tables in theirs (#lists, #tables). An aspect has none: opening one moves nothing, the object is what is looked
			at. A control with an entry of its own (CONTROL_ANCHORS) goes there. A feature is looked at in the block it brings to the
			specimen (its `anchor`, see _anchorOf()), and at the top of the page where it has none. The page has an id at its end,
			footer, which only the footer is looked at in. The list of the themes has an anchor of its own, THEMES_ANCHOR. The maps are
			told together in the docblock of _jump()	*/
		OBJECT_ANCHORS	: { page : 'global', header : 'global', footer : 'footer', section : 'content', tiles : 'tiles', card : 'cards', buttons : 'buttons', lists : 'lists', tables : 'tables', forms : 'forms' },

		/*	The controls that are looked at elsewhere than their object is, by the key the table has them under: the
			eight controls of the palette, which are the page's and are looked at in the grounds (#colours), where the
			page's own anchor is the top of the page; and the two controls of the hover of the pictures, which are the
			page's as well and are looked at in the cards (#cards), whose first row of pictures says nino-hover. The
			templates have none: a template is looked at where its object is (OBJECT_ANCHORS). Nor have the quiet and
			the loud variant of a section: the loud one sizes the hero, and the two are looked at in the first section
			(#content) with the other knobs of the section. The knobs of a section that act on a band - the alignment, the measure,
			the letter-spacing, the rule with its length and colour, the shadow and the ground of the title - are looked at in the
			first section that has a title and a subtitle of its own, which is the cards (#cards): the hero is centred by a class
			of its page, which the alignment leaves alone, has no rule, and stands on a ground the black shadow is lost on	*/
		CONTROL_ANCHORS	: { 'colours/primary' : 'colours', 'colours/secondary' : 'colours', 'colours/harmony' : 'colours', 'colours/temperature' : 'colours', 'colours/saturation' : 'colours', 'colours/contrast' : 'colours', 'colours/depth' : 'colours', 'colours/mode' : 'colours',
			'motion/hover' : 'cards', 'motion/hover-strength' : 'cards',
			'knobs/section/align' : 'cards', 'knobs/section/measure' : 'cards', 'knobs/section/title-tracking' : 'cards', 'knobs/section/rule' : 'cards', 'knobs/section/rule-length' : 'cards', 'knobs/section/title-shadow' : 'cards', 'knobs/section/title-ground' : 'cards', 'knobs/section/rule-color' : 'cards', },

		/*	The positions of a knob, by the kind of the knob, as the least and the most of them: a
			scale has the five positions -2 to +2, a choice the three -1 to +1, a toggle the two 0 and
			1, off and on, and an ink the middle alone: what it stands at is a tone and no position (see Setup::positions()).
			The root size, the width, the whitespace and the two radii of the page are scales	*/
		RANGE		: { scale : [ -2, 2 ], choice : [ -1, 1 ], toggle : [ 0, 1 ], grade : [ 0, 4 ], ink : [ 0, 0 ] },

		// The namespace the elements of an icon are made in
		SVG			: 'http://www.w3.org/2000/svg',

		/*	The icons in front of the names of the rows and on the buttons of the
			viewport switch: Lucide's (lucide.dev, ISC - the notice is
			THIRD-PARTY-NOTICES.md), 24 by 24 and drawn with a stroke, as the
			parts each of them is made of - an element and its attributes, in the order
			the shape is given in. Built as elements rather than written as markup, the
			way the rest of the panel is. The class of the svg carries the stroke and the
			size (see .design-icon in admin.css). One is no Lucide's: tracking, the
			letter-spacing of a button, is drawn for the panel in the same grid and
			with the same stroke	*/
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
			'list' : [
				[ 'path', { d : 'M3 12h.01' } ],
				[ 'path', { d : 'M3 18h.01' } ],
				[ 'path', { d : 'M3 6h.01' } ],
				[ 'path', { d : 'M8 12h13' } ],
				[ 'path', { d : 'M8 18h13' } ],
				[ 'path', { d : 'M8 6h13' } ],
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
			// Where the words of a section and of a card stand: Lucide's align-center
			'align-center' : [
				[ 'path', { d : 'M17 12H7' } ],
				[ 'path', { d : 'M19 18H5' } ],
				[ 'path', { d : 'M21 6H3' } ],
			],
			// How wide the column of a text is: Lucide's unfold-horizontal
			'unfold-horizontal' : [
				[ 'path', { d : 'M16 12h6' } ],
				[ 'path', { d : 'M8 12H2' } ],
				[ 'path', { d : 'M12 2v2' } ],
				[ 'path', { d : 'M12 8v2' } ],
				[ 'path', { d : 'M12 14v2' } ],
				[ 'path', { d : 'M12 20v2' } ],
				[ 'path', { d : 'm19 15 3-3-3-3' } ],
				[ 'path', { d : 'm5 9-3 3 3 3' } ],
			],
			// The letter-spacing of the label of a button, drawn for the panel: the two edges of a box and the arrows of the space between them
			'tracking' : [
				[ 'path', { d : 'M3 4v16' } ],
				[ 'path', { d : 'M21 4v16' } ],
				[ 'path', { d : 'M7 12h10' } ],
				[ 'path', { d : 'm10 9-3 3 3 3' } ],
				[ 'path', { d : 'm14 9 3 3-3 3' } ],
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
			// What a bar does when the page is scrolled down: Lucide's arrow-down-to-line
			'arrow-down-to-line' : [
				[ 'path', { d : 'M12 17V3' } ],
				[ 'path', { d : 'm6 11 6 6 6-6' } ],
				[ 'path', { d : 'M19 21H5' } ],
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
			// The pencil on the row of a theme of the project, which opens its name and its description for a change: Lucide's pencil
			'pencil' : [
				[ 'path', { d : 'M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z' } ],
				[ 'path', { d : 'm15 5 4 4' } ],
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
			name of the Colour aspect starts at the same left edge. A row of the loader,
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
			'page/radii-tile' : 'square-round-corner',
			'page/radii-card' : 'square-round-corner',
			'page/radii-image' : 'square-round-corner',
			'typefaces/family' : 'a-large-small',
			'header/nav' : 'a-large-small',
			'header/height' : 'move-vertical',
			'header/space' : 'align-vertical-space-around',
			'header/logo' : 'scaling',
			'header/ground' : 'contrast',
			'header/border-size' : 'minus',
			'header/border-color' : 'palette',
			'header/shadow' : 'square-stack',
			'header/position' : 'move-horizontal',
			'header/cta' : 'square-round-corner',
			'header/behaviour' : 'arrow-down-to-line',
			'footer/scale' : 'a-large-small',
			'footer/height' : 'move-vertical',
			'footer/space' : 'align-vertical-space-around',
			'footer/logo' : 'scaling',
			'footer/ground' : 'contrast',
			'footer/border-size' : 'minus',
			'footer/border-color' : 'palette',
			'footer/divider' : 'minus',
			'footer/position' : 'move-horizontal',
			'section/quiet' : 'a-large-small',
			'section/loud' : 'a-large-small',
			'section/title' : 'a-large-small',
			'section/subtitle' : 'a-large-small',
			'section/title-tracking' : 'chevrons-left-right',
			'section/space' : 'scaling',
			'section/rule' : 'minus',
			'section/rule-length' : 'move-horizontal',
			'section/title-shadow' : 'square-stack',
			'section/dim' : 'contrast',
			'section/title-ground' : 'square',
			'section/rule-color' : 'palette',
			'section/title-ink' : 'palette',
			'section/subtitle-ink' : 'palette',
			'section/align' : 'align-center',
			'section/measure' : 'unfold-horizontal',
			'tiles/title' : 'a-large-small',
			'tiles/subtitle' : 'a-large-small',
			'tiles/text' : 'a-large-small',
			'tiles/title-tracking' : 'chevrons-left-right',
			'tiles/space' : 'align-vertical-space-around',
			'tiles/gap' : 'unfold-horizontal',
			'tiles/place' : 'align-center',
			'tiles/dim' : 'contrast',
			'tiles/hover' : 'move-vertical',
			'tiles/title-ink' : 'palette',
			'tiles/subtitle-ink' : 'palette',
			'card/title' : 'a-large-small',
			'card/subtitle' : 'a-large-small',
			'card/title-tracking' : 'chevrons-left-right',
			'card/space' : 'align-vertical-space-around',
			'card/border' : 'square',
			'card/shadow' : 'square-stack',
			'card/tone' : 'palette',
			'card/title-ground' : 'square',
			'card/title-ink' : 'palette',
			'card/subtitle-ink' : 'palette',
			'card/hover' : 'move-vertical',
			'card/align' : 'align-center',
			'buttons/size' : 'a-large-small',
			'buttons/spread' : 'chevrons-left-right',
			'buttons/tracking' : 'tracking',
			'buttons/space' : 'align-vertical-space-around',
			'buttons/corners' : 'square-round-corner',
			'buttons/outline' : 'minus',
			'buttons/hover' : 'move-vertical',
			'lists/scale' : 'a-large-small',
			'lists/space' : 'align-vertical-space-around',
			'lists/marker' : 'list',
			'tables/scale' : 'a-large-small',
			'tables/space' : 'align-vertical-space-around',
			'tables/border' : 'square',
			'tables/zebra' : 'rows-3',
			'tables/stripes' : 'contrast',
			'forms/scale' : 'a-large-small',
			'forms/space' : 'align-vertical-space-around',
			'forms/corners' : 'square-round-corner',
			'forms/line' : 'minus',
			'forms/labels' : 'move-horizontal',
			'forms/edge' : 'palette',
			'forms/label' : 'palette',
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
		// The header and footer the frame was last built with. Everything else
		// is a stylesheet, and a stylesheet can be swapped inside it
		_frames	: null,
		// Whether the animation of the page was changed since the frame last played it
		_replays	: false,
		// Whether the window's resizing is listened to yet, and the timer that waits
		// for it to stop (see _fitLater())
		_bound	: false,
		_fitTimer	: null,

		/**
		 *	Load the library and the stored setup, then draw the screen that carries the preview and the bar - unless it
		 *	was the loader that asked, which has neither: the screens that have are drawn when they are shown
		 *
		 *	@param		{Function}	[then]			Run once the state is there (and the screen drawn, where one was asked for)
		 *	@param		{Element}		[wrap]			Where a failure is said - the mount of the screen that asked, the loader's where it did;
		 *																	else the one of the screen that is drawn
		 *
		 *	@return		void
		 */
		init : function( then, wrap ) {

			wrap = wrap || Nino.admin.design._mount();
			if( wrap === null )
				return;

			Nino.admin.design._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.design._showError( wrap, status, response );

				Nino.admin.design._data		= response;
				Nino.admin.design._takeStored( response );
				Nino.admin.design._ready	= true;

				// A preview and a bar drawn behind the loader would be a request for nothing, in a mount nobody sees
				if( wrap === Nino.admin.design._mount() )
					Nino.admin.design._render();

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	The shell shows the panel's own screen, which the strip on top of the pane calls Themes: the mode that was
		 *	open, the list of the themes the first time (see _screen)
		 *
		 *	@return		void
		 */
		showCurrent : function() {
			Nino.admin.design._show();
		},

		/**
		 *	Put the screen on show, in the mode it is in. The preview and the bar are drawn with it, and one at a time,
		 *	since there is one of each: the frame is built anew where the screen is drawn
		 *
		 *	@return		void
		 */
		_show : function() {

			const design = Nino.admin.design;

			// The loader is left, if it was the tab that was shown: what it held is let go of
			Nino.admin['design-typefaces']._leave();

			if( design._ready === false )
				return design.init();

			// The shell brings the screen on again after a Save it asked for failed: drawn anew, the reason the
			// save gave would be gone, and so would the selection nobody has stored
			if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty.isDirty( [ 'design' ] ) === true )
				return design._refreshThemeChip();

			design._render();
		},

		/**
		 *	The mount the screen is drawn in: the one the pane of the panel holds (see Admin::panes())
		 *
		 *	@return		{?Element}
		 */
		_mount : function() {
			return dc.getElementById('design-form');
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
		 *	@return		{Object}					{ parts, knobs, styles, features, size, width, whitespace, radius, radius-alt, radii, motion, fonts, colours, theme, css }
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
				styles 	: Nino.admin.design._stylesOf( data.styles, Object.keys( parts ) ),
				features : Nino.admin.design._entriesOf( data.features, 'picked' ),
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
		 *	The styles chosen for the templates out of an answer - design/list's, or the setup of a theme: for each part the style =>
		 *	option pairs, as a copy of its own, and an entry for every part, an empty one where it chose none: the server answers a
		 *	part with none as an empty list, and the screen holds the same for it as for the server's own
		 *
		 *	@param		{(Object|Array)}	given		part => { style : option }, or an empty list
		 *	@param		{Array}						parts		The keys of the parts
		 *
		 *	@return		{Object}									part => { style : option }
		 */
		_stylesOf : function( given, parts ) {

			const styles = {};

			parts.forEach( function( part ) {

				const chosen = given === undefined || given === null || Array.isArray( given ) === true ? undefined : given[part];

				styles[part] = chosen === undefined || chosen === null || Array.isArray( chosen ) === true ? {} : Object.assign( {}, chosen );
			} );

			return styles;
		},

		/**
		 *	Make the stored selection the screen's: the working copy and the theme it names as the
		 *	server describes it. An action of the list that is still on its way answers over
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

			// design_set_missing: the part - or the feature -, the set the file named, the set that stands in its place
			if( note.code === 'design_set_missing' && params.length === 3 ) {

				const feature = design._featuresOf()[ params[0] ];

				if( feature !== undefined ) {
					params[2] = ( ( feature.catalogue || {} )[ params[2] ] || {} ).name || params[2];
					params[0] = feature.name;
				}
				else {
					params[2] = design._setName( params[0], params[2] );
					params[0] = Nino.content.getText('/_admin/design/part/'+ params[0] ) || params[0];
				}
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
		 *	The head of the pane the screen stands in: the row the shell
		 *	renders over every panel, with the panel's name and the strip of
		 *	the tabs in it (see Nino.adminUi.panelHead()).
		 *
		 *	null where there is none - the script drawn somewhere other than its
		 *	pane - and the bar of the objects then opens the column of controls,
		 *	where it stood before there was a head to put it in
		 *
		 *	@return		{Object|null}			{ element, title, actions, tabs( strip ) }
		 */
		_head : function() {
			const wrap = Nino.admin.design._mount();
			return wrap !== null ? Nino.adminUi.panelHead( wrap ) : null;
		},

		/**
		 *	The whole screen that carries the preview: what the screen has in one column,
		 *	a preview of the lot in the other, and the bar under both.
		 *
		 *	The list of the themes is the library; the setup is one object at a
		 *	time, because a block for every aspect of every object is a list to read and one object is a
		 *	decision to make. The bar above the controls says which one is open and takes
		 *	the way back to the list; the preview beside it is the whole page either way,
		 *	scrolled to where the open object is
		 *
		 *	@return		void
		 */
		_render : function() {

			const design = Nino.admin.design;
			const wrap = design._mount();
			const data = design._data;
			if( wrap === null || data === null )
				return;

			wrap.innerHTML = '';

			/*	No heading of its own: the pane's head names the panel, and the strip of the
				tabs on top of the pane is the shell's. The bar of the objects - Back to list and
				the strip of the tabs, or the select - goes to the top of the pane of the tab, under the shell's,
				the way the Features panel's strip goes to the top of its tab: it switches the
				controls on the left and nothing else, so it belongs with the name of the screen
				and not above one of its two halves. Handed over on every render; tabs() takes
				the place of the bar drawn before it, so coming back to the views does not
				stack a second one. The list of the themes has no bar: the one drawn
				before it is taken away	*/
			const head = design._head();

			if( design._screen === 'setup' && head !== null ) {

				head.tabs( design.views._renderTabs() );

				// A strip wider than the pane scrolls in its own box: the object that is open is brought into sight
				const selected = dc.getElementById( 'design-tab-'+ design.views._current() );

				if( selected !== null && typeof selected.scrollIntoView === 'function' )
					selected.scrollIntoView( { block : 'nearest', inline : 'nearest' } );
			}

			if( design._screen !== 'setup' && dc.getElementById('design-tabs') !== null )
				dc.getElementById('design-tabs').remove();

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

			/*	Save theme: the screen into the theme that is loaded, with the draft and the website left alone. It stands
				with the views, where the setup is edited, and while the theme is one of the project's - a shipped theme is a
				file of the feature and has no way to be saved into, and with none loaded there is nothing to save into. Before
				the way back in the document, as the buttons that keep stand in front of the one that lets go	*/
			if( design._screen === 'setup' && design._active !== null && design._active.shipped === false )
				actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/design/theme/save'), function() { design._saveTheme() },
					{ id : 'design-theme-save', disabled : design._themeBusy } ) );

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
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
		},

		/**
		 *	The controls column, on its own so a switch of the object can redraw it
		 *	without touching the frame beside it - or the bar of the objects, whose strip is
		 *	the element the switch was made on, and keeps the focus an arrow key put
		 *	on it.
		 *
		 *	What the server dropped or replaced on the way stands first, over the
		 *	screen that is open: it is true whichever it is. The state of the files is
		 *	not here, it is the chip in the action bar
		 *
		 *	@return		{HTMLElement}
		 */
		_renderControls : function() {

			const controls = dc.createElement('div');
			controls.id = 'design-controls';

			// In the setup the column is the panel of the tab that is open
			if( Nino.admin.design._screen === 'setup' ) {
				controls.setAttribute( 'role', 'tabpanel' );
				controls.setAttribute( 'aria-labelledby', 'design-tab-'+ Nino.admin.design.views._current() );
			}

			// Only where there is no head does the bar of the objects open the column (see
			// _head()) - and is drawn again with it on every switch
			if( Nino.admin.design._screen === 'setup' && Nino.admin.design._head() === null )
				controls.appendChild( Nino.admin.design.views._renderTabs() );

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
		 *	The aspects of the design, in the order the table gives them: the tabs of the table, which the blocks under an object are
		 *
		 *	@return		{Array}					Keys of the aspects
		 */
		_tabKeys : function() {
			return ( ( Nino.admin.design._data || {} ).layout || {} ).tabs || [];
		},

		/**
		 *	The objects of the setup, in the order the table gives them: the eight of Setup::OBJECTS and one for each feature that brings
		 *	variants. They are the tabs of the screen
		 *
		 *	@return		{Array}					Keys of the objects
		 */
		_objectKeys : function() {
			return ( ( Nino.admin.design._data || {} ).layout || {} ).objects || [];
		},

		/**
		 *	Where a control stands: the entry of the table for the key its choice is kept under
		 *
		 *	@param		{string}	key				'size', 'radii/card', 'knobs/section/quiet' ...
		 *
		 *	@return		{Array}						[ tab, object, order ]; [] for a key the table does not have
		 */
		_place : function( key ) {
			return ( ( ( Nino.admin.design._data || {} ).layout || {} ).controls || {} )[key] || [];
		},

		/**
		 *	What the screen that is drawn holds, into the box the column keeps for it: in the list mode the card of the
		 *	themes, in the setup the aspects of the object that is open (see Nino.admin.design.views._renderRows())
		 *
		 *	@param		{Element}	body
		 *
		 *	@return		void
		 */
		_fillBody : function( body ) {

			const design = Nino.admin.design;

			if( design._screen === 'setup' )
				return design.views._renderRows( design.views._current(), body );

			// By the node, not by its id: this column is not in the document yet on the first render
			const themes = design._card('design-themes');

			body.appendChild( themes );
			design._paintThemes( themes );
		},

		/**
		 *	A strip of tabs: a tablist of buttons, one of them selected. Nino.adminUi.buttonRow()
		 *	keeps that, with the arrow keys and the one tab stop of a strip. The objects of the
		 *	setup are drawn with it (see Nino.admin.design.views._renderTabs())
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
				button.id = 'design-tab-'+ tab.key;
				button.setAttribute( 'aria-controls', 'design-controls' );
				button.textContent = tab.label;
				buttons[tab.key] = button;
				bar.appendChild( button );
			} );

			Nino.adminUi.buttonRow( buttons, active, onSwitch, 'aria-selected' );

			return bar;
		},

		/**
		 *	Scroll the page in the frame to where it is looked at, once it stands at the
		 *	height the stage was just fitted to.
		 *
		 *	In one column the stage stands under the controls, so a column of another
		 *	height moves it - another object, the brand warning
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
		 *	(_switchTab()) and the answer of a preview whose stylesheet was swapped in (_previewNow())
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
		 *	A card of the column, the workbench's in the measures of this panel, under the id the actions of the
		 *	list find it by
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
		 *	Draw the list of the themes again, which the actions of the screen change - where the list is on screen -
		 *	and the chip "changed" beside it
		 *
		 *	@return		void
		 */
		_paintTheme : function() {

			const design = Nino.admin.design;
			const themes = dc.getElementById('design-themes');

			if( themes !== null )
				design._paintThemes( themes );

			// The label of the screen says what an action did to the theme whichever of its modes is open
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
		 *	The card of the list of the themes: the themes there are, one row each, and under them the
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
		 *	One theme of the list: its name with the chips it has - shipped; active where the website
		 *	runs it, which is the theme of the last apply; and loaded, not applied where the working
		 *	copy was taken from it and the website does not run it - and, for a theme of the project,
		 *	the pencil that renames it; the description under it, and what can be done with it: load,
		 *	duplicate, export, delete (a theme of the project only) and edit, in that order. A button
		 *	says which theme it is about to the ones who hear it read out. While the theme is the one
		 *	that is renamed (_themeRename) its name and its description are two fields instead, and
		 *	the buttons are Save and Cancel - Enter in a field saves, Escape cancels
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
			const renaming = design._themeRename === entry.slug && entry.shipped === false;

			const row = ui.el( 'li', 'design-theme-row'+ ( draft === true ? ' design-theme-row--active' : '' )+ ( renaming === true ? ' design-theme-row--renaming' : '' ) );
			row.dataset.slug = entry.slug;

			if( draft === true )
				row.setAttribute( 'aria-current', 'true' );

			const text = ui.el( 'div', 'design-theme-text' );
			const buttons = ui.el( 'div', 'design-theme-buttons' );

			row.appendChild( text );
			row.appendChild( buttons );

			if( renaming === true ) {
				design._renameFields( text, buttons, entry );
				return row;
			}

			const head = ui.el( 'div', 'design-theme-head' );
			head.appendChild( ui.el( 'span', 'design-theme-name', entry.name ) );

			if( entry.shipped === true )
				head.appendChild( design._chip( 'design-theme-shipped', Nino.content.getText('/_admin/design/theme/shipped'), 'muted' ) );

			if( running === true )
				head.appendChild( design._chip( 'design-theme-active', Nino.content.getText('/_admin/design/theme/active'), 'ok' ) );

			if( draft === true && running === false )
				head.appendChild( design._chip( 'design-theme-loaded', Nino.content.getText('/_admin/design/theme/loaded'), 'warn' ) );

			if( entry.shipped === false ) {

				const word = Nino.content.getText('/_admin/common/label/rename');
				const pencil = ui.button( '', function() { design._renameTheme( entry.slug ) }, {
					id 				: 'design-theme-rename-'+ entry.slug,
					className : 'design-theme-pencil',
					name 			: word+ ' - '+ entry.name,
					disabled 	: design._themeBusy,
				} );

				pencil.appendChild( design._svg('pencil') );
				head.appendChild( pencil );
			}

			text.appendChild( head );

			if( entry.description !== '' )
				text.appendChild( ui.el( 'small', 'design-theme-description', entry.description ) );

			[ [ 'load', function() { design._loadTheme( entry.slug ) } ], [ 'duplicate', function() { design._duplicateTheme( entry.slug ) } ],
				[ 'export', function() { design._exportTheme( entry.slug ) } ], [ 'delete', function() { design._deleteTheme( entry.slug ) } ],
				[ 'edit', function() { design._editTheme( entry.slug ) } ] ].forEach( function( button ) {

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

			return row;
		},

		/**
		 *	The row of the theme that is renamed: its name and its description as two fields, held to the lengths
		 *	the server cuts them to and keeping what is typed in them when the list is drawn again, and Save and
		 *	Cancel in the place of the buttons. Enter in a field saves, Escape cancels
		 *
		 *	@param		{Element}	text			Where the words of the row stand
		 *	@param		{Element}	buttons		Where its buttons stand
		 *	@param		{Object}	entry			The row of design/list's `themes`
		 *
		 *	@return		void
		 */
		_renameFields : function( text, buttons, entry ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;

			const keys = function( field ) {
				field.querySelector('input').addEventListener( 'keydown', function( event ) {

					if( event.key === 'Enter' ) {
						event.preventDefault();
						design._saveRename();
					}

					if( event.key === 'Escape' ) {
						event.preventDefault();
						design._cancelRename();
					}
				} );
			};

			const name = ui.textField( {
				id 				: 'design-theme-name',
				label 		: Nino.content.getText('/_admin/design/theme/field/name'),
				value 		: design._themeFields.name,
				maxLength : 80,
				required 	: true,
				onInput 	: function( value ) { design._themeFields.name = value },
			} );

			const description = ui.textField( {
				id 				: 'design-theme-description',
				label 		: Nino.content.getText('/_admin/design/theme/field/description'),
				value 		: design._themeFields.description,
				maxLength : 400,
				onInput 	: function( value ) { design._themeFields.description = value },
			} );

			keys( name );
			keys( description );
			text.appendChild( name );
			text.appendChild( description );

			[ [ 'save', 'rename-save', function() { design._saveRename() }, 'nino-admin-btn-primary' ],
				[ 'cancel', 'rename-cancel', function() { design._cancelRename() }, '' ] ].forEach( function( button ) {

				const word = Nino.content.getText('/_admin/common/label/'+ button[0] );

				buttons.appendChild( ui.button( word, button[2], {
					id 				: 'design-theme-'+ button[1],
					className : button[3],
					name 			: word+ ' - '+ entry.name,
					disabled 	: design._themeBusy,
				} ) );
			} );
		},

		/**
		 *	The setup of a working copy as a theme keeps it: the keys that choose something, without
		 *	the theme it names and the css it carries - the screen's side of the compare the chip makes
		 *	with the file's (see _themeChanged()). The colours are among them where the theme names
		 *	colours - the theme the feature ships names none and has no say over them. The styles of the templates
		 *	and the choices for
		 *	the features are always among them: they are the theme's own whatever its file says, and
		 *	the server answers them for every theme (see Themes::setup())
		 *
		 *	@param		{Object}	edit
		 *	@param		{Object}	[theme]		The setup of the theme it is compared with; with no colours in it the snapshot has none either
		 *
		 *	@return		{Object}
		 */
		_snapshotOf : function( edit, theme ) {

			const snapshot = { size : edit.size, width : edit.width, whitespace : edit.whitespace, radius : edit.radius, 'radius-alt' : edit['radius-alt'], radii : edit.radii,
				motion : edit.motion, parts : edit.parts, knobs : edit.knobs, styles : edit.styles, fonts : edit.fonts };

			if( theme === undefined || theme.colours !== undefined )
				snapshot.colours = edit.colours;

			snapshot.features = edit.features;

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
		 *	Draw the chip "changed" on the button of the panel's own screen in the strip on top of the pane, which says
		 *	Themes, or take it away: it is there while the screen differs from the loaded theme (see _themeChanged()),
		 *	whichever tab and mode are open. The button is the shell's (see Panels::panesHtml(), id admin-tabbutton-<uri>):
		 *	the kernel has no place in a tab's label for a word of the panel's, so the script appends the chip to the
		 *	button - and has to do it again whenever the strip may have been drawn anew, which is each time one of the tabs
		 *	is shown (see _show() and Nino.admin['design-typefaces'].showCurrent()). Asked for after every change of a
		 *	control, beside the way back, and by everything that draws the list again
		 *
		 *	@return		void
		 */
		_refreshThemeChip : function() {

			const design = Nino.admin.design;
			const changed = design._themeChanged();
			const tab = dc.getElementById('admin-tabbutton-design');

			if( tab === null )
				return;

			const chip = tab.querySelector('.design-tab-changed');

			if( changed === true && chip === null )
				tab.appendChild( design._chip( 'design-tab-changed', Nino.content.getText('/_admin/design/theme/current/changed'), 'warn' ) );

			if( changed === false && chip !== null )
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
		 *	Switch the buttons and the file field of the list off - and the button Save theme of the action bar - while one of
		 *	its requests is on its way, and on again when it is back: a second action over the first would answer over a
		 *	state the screen does not have any more
		 *
		 *	@param		{boolean}	on
		 *
		 *	@return		{string}					Switching on: ''. Switching off: the id of the control that had the focus when the work began, '' for none
		 */
		_themeWork : function( on ) {

			const design = Nino.admin.design;
			const had = dc.activeElement;

			design._themeBusy = on;

			if( on === true )
				design._busyFocus = had !== null && had !== undefined && typeof had.id === 'string' ? had.id : '';

			const card = dc.getElementById('design-themes');

			if( card !== null )
				card.querySelectorAll('button, input[type="file"]').forEach( function( control ) { control.disabled = on } );

			const save = dc.getElementById('design-theme-save');

			if( save !== null )
				save.disabled = on;

			if( on === true )
				return '';

			/*	A control that is switched off drops the focus to the page: it has it back with the control, the Save theme of
				the bar among them - where the focus fell, and not where somebody moved it on meanwhile. The id is answered
				for an action that draws the list anew, whose control is then another element (see _takeTheme())	*/
			const pressed = design._busyFocus;
			const now = dc.activeElement;

			design._busyFocus = '';

			if( pressed !== '' && ( now === null || now === undefined || now === dc.body ) ) {

				const back = dc.getElementById( pressed );

				if( back !== null && back.disabled !== true )
					design._focus( back.id );
			}

			return pressed;
		},

		/**
		 *	A failure of an action of the list, told in the line of the action bar in red: the sentence of its
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
		 *	Give the focus to an element of the screen that was drawn anew - the press that had it is gone with the
		 *	drawing, and the focus falls to the page
		 *
		 *	@param		{string}	id
		 *
		 *	@return		void
		 */
		_focus : function( id ) {

			const control = dc.getElementById( id );

			if( control !== null && typeof control.focus === 'function' )
				control.focus();
		},

		/**
		 *	Put a theme on the screen as a draft. The server answers what the screen takes over and which of
		 *	the typefaces the theme names the project has not installed. Those the list of the Typefaces tab
		 *	has are named in one question - with a yes they are installed, one after the other, and the list of
		 *	design/list is read again, for the selects of the roles, before anything is drawn. Nothing is
		 *	stored: the Reset button appears, the shell asks before it loses the draft and Save draft keeps it
		 *
		 *	@param		{string}		slug
		 *	@param		{boolean}		[edit]			Open the views of the setup once the theme is taken over, in the one drawing of
		 *																	the screen: what Edit does for a theme that is not the loaded one
		 *
		 *	@return		void
		 */
		_loadTheme : function( slug, edit ) {

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
						design._relist( function() { design._takeTheme( response, said, failure, stamp, edit === true ) } );
					} );

				design._takeTheme( response, [], '', stamp, edit === true );
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
		 *	is what the chip compares the screen with.
		 *	What reading the theme replaced is told in the list over the tab, replacing the notes of what was
		 *	on the screen before.
		 *	A family the role is not offered here - not in the list, declined, or not installed after all -
		 *	is set to the face of Nino and told in the line of the bar with the rest. The screen is drawn
		 *	again whole, the preview with it - on the setup by its objects where the theme was loaded to be edited
		 *
		 *	@param		{Object}		response		design/theme/load's answer
		 *	@param		{Array}			said				What the installs before it said
		 *	@param		{string}		failure			Why an install failed, '' where none did
		 *	@param		{number}		stamp				The count of the request, against a newer state
		 *	@param		{boolean}		[edit]			Draw the views of the setup, not the list
		 *
		 *	@return		void
		 */
		_takeTheme : function( response, said, failure, stamp, edit ) {

			const design = Nino.admin.design;

			if( stamp !== design._themeStamp )
				return;

			const pressed = design._themeWork( false );

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

			design._edit = { parts : setup.parts, knobs : setup.knobs, styles : design._stylesOf( setup.styles, Object.keys( setup.parts ) ), size : setup.size, width : setup.width, whitespace : setup.whitespace,
				radius : setup.radius, 'radius-alt' : setup['radius-alt'], radii : setup.radii, motion : setup.motion, fonts : setup.fonts,
				colours : setup.colours === undefined ? design._edit.colours : setup.colours,
				features : design._entriesOf( setup.features ), theme : response.slug, css : response.css };
			design._active = { slug : response.slug, name : response.name, description : response.description, shipped : response.shipped,
				setup : JSON.parse( JSON.stringify( design._snapshotOf( design._edit, setup ) ) ), css : response.css };
			design._notes = Array.isArray( response.notes ) === true ? response.notes : [];

			if( edit === true ) {
				design._screen = 'setup';
				design._target = null;
			}

			design._render();

			// The press that asked is gone with the drawing: the focus goes to the open object's tab where the theme is edited, else back to the row's button
			if( edit === true )
				design._focusViews();
			else
				design._focus( pressed );

			const lines = said.slice();

			if( failure !== '' )
				lines.push( failure );

			lines.push( design._text( '/_admin/design/theme/msg/loaded', response.name ) );

			if( names.length > 0 )
				lines.push( design._text( '/_admin/design/theme/msg/unavailable', names.join(', ') ) );

			design._say( dc.getElementById('design-msg'), lines.join(' '), failure !== '' ? 'nino-admin-error' : ( names.length > 0 ? 'nino-admin-hint' : '' ) );
		},

		/**
		 *	Put the screen into the loaded theme - the working copy and its css, with the name and the description the
		 *	theme has: a rename is the pencil of its row. The answer is the theme as the server holds it now, and the list
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

			design._apiCall( 'theme/save', Object.assign( {}, design._edit, { slug : slug } ), function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/save', status, response );

				design._data.themes = response.themes;
				design._active = response.active;

				// The theme the stored setup was taken from is what the way back goes back to
				if( design._data.theme === slug )
					design._data.active = response.active;

				design._paintTheme();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/saved', response.active.name ) );
			} );
		},

		/**
		 *	Open the name and the description of a theme of the project as two fields in its row, with what it has now in
		 *	them. One row at a time: another that was open is closed without a word, and what was typed in it is let go of
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_renameTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			const entry = design._themeEntry( slug );

			design._themeRename = slug;
			design._themeFields = { name : entry.name, description : entry.description };
			design._paintTheme();

			const field = dc.getElementById('design-theme-name');

			if( field !== null )
				field.focus();
		},

		/**
		 *	Close the fields of the row without a request: the name and the description are what they were
		 *
		 *	@return		void
		 */
		_cancelRename : function() {

			const design = Nino.admin.design;
			const slug = design._themeRename;

			if( slug === '' )
				return;

			design._themeRename = '';
			design._paintTheme();

			const pencil = dc.getElementById('design-theme-rename-'+ slug );

			if( pencil !== null )
				pencil.focus();
		},

		/**
		 *	Give the theme whose fields are open the name and the description that are typed in them. Only those two:
		 *	its setup and its css are the file's. The answer is the list and the theme, which the list shows with the
		 *	new words; where it is the loaded theme the record the chip compares with takes the new words and keeps
		 *	its setup and its css, which are the ones the screen was taken from
		 *
		 *	@return		void
		 */
		_saveRename : function() {

			const design = Nino.admin.design;
			const slug = design._themeRename;

			if( design._themeBusy === true || slug === '' )
				return;

			const stamp = ++design._themeStamp;

			design._themeWork( true );
			design._say( dc.getElementById('design-msg'), '' );

			// What an earlier refusal put under the field goes: this one has its own
			if( dc.getElementById('design-themes') !== null )
				Nino.adminUi.clearFieldErrors( dc.getElementById('design-themes') );

			design._apiCall( 'theme/rename', { slug : slug, name : design._themeFields.name, description : design._themeFields.description }, function( status, response ) {

				if( stamp !== design._themeStamp )
					return;

				design._themeWork( false );

				if( status !== 200 || response === null ) {

					design._themeFail( '/_admin/design/theme/error/rename', status, response );

					// A name the server refuses is said under its field as well, and the focus goes there: the line of the bar says that something is wrong, the field says what
					const field = dc.getElementById('design-theme-name');

					if( response !== null && typeof response === 'object' && response.code === 'design_name' && field !== null ) {
						Nino.adminUi.fieldError( field.parentNode, field, 'design-theme-name-error', Nino.adminUi.api.errorText( status, response, '/_admin/design/theme/error/rename' ) );
						field.focus();
					}

					return;
				}

				design._data.themes = response.themes;
				design._themeRename = '';

				if( design._data.theme === slug )
					design._data.active = response.active;

				if( design._active !== null && design._active.slug === slug )
					design._active = Object.assign( {}, design._active, { name : response.active.name, description : response.active.description } );

				design._paintTheme();
				design._focus( 'design-theme-rename-'+ slug );
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/renamed', response.active.name ) );
			} );
		},

		/**
		 *	Edit a theme: open the setup by its objects, on the object that was open last. The loaded theme opens at once, without
		 *	a request, and the draft with it - what was changed and not stored stays; any other is loaded first, as Load
		 *	does, and the views open in the one drawing that takes it over
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_editTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

			if( design._edit === null || design._edit.theme !== slug )
				return design._loadTheme( slug, true );

			design._screen = 'setup';
			design._target = null;
			design._render();
			design._focusViews();
		},

		/**
		 *	The focus after the views were opened by a press on Edit, which the list took with it: on the object that is open, the one
		 *	tab of the strip that is a tab stop - the select where the strip gives way to it -, else on Back to list
		 *
		 *	@return		void
		 */
		_focusViews : function() {

			const design = Nino.admin.design;

			const tab = 'design-tab-'+ design.views._current();

			// Below the width where the strip gives way to the select, the strip is not on screen and cannot take the focus: the select does
			if( typeof wn.matchMedia === 'function' && wn.matchMedia( design.views.NARROW ).matches === true && dc.getElementById('design-object-select') !== null )
				return design._focus('design-object-select');

			design._focus( dc.getElementById( tab ) !== null ? tab : 'design-back' );
		},

		/**
		 *	Back to the list of the themes from the views. Nothing is discarded: the draft stays as it is, and the chip on the
		 *	button of Themes says that it differs from the theme
		 *
		 *	@return		void
		 */
		_back : function() {

			const design = Nino.admin.design;

			design._screen = 'themes';
			design._target = null;
			design._render();

			// The press that had the focus went with the views: it goes to the Edit of the theme that is loaded - the first row's where none is
			const loaded = design._edit !== null && design._edit.theme !== '' ? design._edit.theme : ( ( ( design._data || {} ).themes || [] )[0] || {} ).slug;

			if( typeof loaded === 'string' )
				design._focus( 'design-theme-edit-'+ loaded );
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

				const pressed = design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/duplicate', status, response );

				design._data.themes = response.themes;
				design._paintTheme();
				design._focus( pressed );
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
				}

				design._paintTheme();
				design._refreshDirty();
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/deleted', name ) );
			} );
		},

		/**
		 *	Take the theme of a row out as a file: the answer is its text and the name to save it under, and
		 *	becomes a download the way the Language panel's export does - a blob, a link clicked and removed,
		 *	the address revoked
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_exportTheme : function( slug ) {

			const design = Nino.admin.design;

			if( design._themeBusy === true )
				return;

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

				const pressed = design._themeWork( false );

				if( status !== 200 || response === null )
					return design._themeFail( '/_admin/design/theme/error/import', status, response );

				design._data.themes = response.themes;
				design._paintTheme();
				design._focus( pressed );
				design._say( dc.getElementById('design-msg'), design._text( '/_admin/design/theme/msg/imported', design._themeEntry( response.slug ).name ) );
			} );
		},

		/**
		 *	The features that bring variants, as design/list answered them: key => { name, anchor, active, default, catalogue, styles, set, knobs, picked }. An
		 *	empty answer is an empty list on the wire, since php has no empty object - and no feature here
		 *
		 *	@param		{Object}	[data]		design/list's answer; the one the screen is drawn from where none is given
		 *
		 *	@return		{Object}
		 */
		_featuresOf : function( data ) {

			const given = ( data === undefined ? Nino.admin.design._data : data ) || {};

			return given.features === undefined || given.features === null || Array.isArray( given.features ) === true ? {} : given.features;
		},

		/**
		 *	The choices for the features out of an answer - design/list's, or the setup of a theme: for each feature that has a variant chosen,
		 *	the variant, the value of each knob of it and the style => option pairs chosen for the feature, as a copy of its own. A feature
		 *	that chose none has no entry. The answer of design/list keeps the styles a feature offers under `styles` and the ones chosen under
		 *	`picked`; the setup of a theme has the ones chosen under `styles`
		 *
		 *	@param		{(Object|Array)}	given		key => { set, knobs, ... }, or an empty list
		 *	@param		{string}					[field]	Where the entry keeps the styles chosen: 'styles' (a setup), or 'picked' (design/list)
		 *
		 *	@return		{Object}									key => { set, knobs, styles }
		 */
		_entriesOf : function( given, field ) {

			const entries = {};
			const copy = function( value ) {
				return value === undefined || value === null || Array.isArray( value ) === true ? {} : JSON.parse( JSON.stringify( value ) );
			};

			if( given === undefined || given === null || Array.isArray( given ) === true )
				return entries;

			Object.keys( given ).forEach( function( key ) {

				const entry = given[key] || {};

				if( typeof entry.set === 'string' && entry.set !== '' )
					entries[key] = { set : entry.set, knobs : copy( entry.knobs ), styles : copy( entry[ field || 'styles' ] ) };
			} );

			return entries;
		},

		/**
		 *	Take over what design/list says now of the families - after an install or a removal
		 *	of the loader, or of the typefaces a theme that is loaded asked for, the selects of the
		 *	roles have one more or one less, and the state of the files may be another one, since a
		 *	family a role uses can have been updated. The selection being edited is not touched; the
		 *	loader, where it is open, is drawn again, since the roles and the settings of the
		 *	repository it reads are what design/list says
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
					Nino.admin['design-typefaces']._paintFaces();
				}

				if( typeof then === 'function' )
					then();
			} );
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

			if( response.contrasts !== undefined )
				data.contrasts = response.contrasts;

			const second = dc.getElementById('design-colour-secondary');

			// Only while it stands for a colour nobody chose. One that was
			// chosen is the value in it, and nothing here may move it
			if( second !== null && String( ( Nino.admin.design._edit.colours || {} ).secondary || '' ) === '' )
				second.value = String( data.accent || second.value );

			Nino.admin.design.views._paintBrandWarning();
			Nino.admin.design.views._paintContrasts();
		},

		/**
		 *	Whether the selection on screen is still the stored one.
		 *
		 *	Canonical rather than JSON.stringify() on both: a knob moved and
		 *	then put back leaves a key behind in a different order, and a bar
		 *	offering to undo nothing is worse than no bar. An empty list and an
		 *	empty object are one thing: php has no empty object, and answers the
		 *	choices for the features of a project that chose none as a list
		 *
		 *	@param		{*}	value
		 *
		 *	@return		{string}
		 */
		_canonical : function( value ) {

			if( value === null || typeof value !== 'object' )
				return JSON.stringify( value === undefined ? null : value );

			if( Array.isArray( value ) === true )
				return value.length === 0 ? '{}' : '['+ value.map( Nino.admin.design._canonical ).join(',')+ ']';

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
		 *	that stand in view already - the hero, under the Motion of the page where the
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
		 *	Scroll the page in the frame to where the open object is looked at, smoothly.
		 *
		 *	The specimen is one page with an element for each place the panel looks at (ids global,
		 *	content, cards, buttons, lists, tables, forms, colours and, at its end, footer - see OBJECT_ANCHORS), and
		 *	the frame is the page of this very origin: the panel scrolls the
		 *	frame's own window by hand, which needs neither a message to the page
		 *	nor a hash it would have to read. Done after an object is opened and after
		 *	every change, when the answer to the change is in the frame.
		 *
		 *	Three maps say where, all of them the panel's and the ids the page's. Opening an object goes to its
		 *	anchor (see _aimOf()): the id OBJECT_ANCHORS names for it, the block a feature brings to the specimen - which design/list
		 *	answers as `anchor` - for a feature. An aspect has none, and opening one moves nothing. A change of a control goes to the
		 *	place that control is looked at in, which follows from the table (see _anchorOf()): the id CONTROL_ANCHORS names for its
		 *	key - the controls of the palette to the grounds (#colours) -, else the anchor of the object it stands under - the
		 *	page's and the header's the top of the page (#global), the footer's its foot, a section's, the quiet and the loud variant
		 *	of its titles among them, the first section (#content), a card's the cards (#cards), the buttons', the lists' and the tables'
		 *	their blocks, the forms' the form, a feature's the block it brings. The place of a change is _target, one for each change - it is put back
		 *	to the object's own when an object is opened, and it is what is jumped to until then.
		 *
		 *	A place the page in the frame has not got is looked at where the object is: the id of a block that the specimen of a
		 *	project does not carry goes to the anchor of the open object, and that to the top of the page, which is also where the
		 *	screen of the themes looks. Only where that is missing too, the frame stays where it is
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

				// Where no change was looked at: the list of the themes at the top of the page, an object of the setup at its own anchor
				const tab = Nino.admin.design._screen === 'setup' ? Nino.admin.design.views._aimOf( Nino.admin.design.views._current() ) : Nino.admin.design.THEMES_ANCHOR;
				let aim = Nino.admin.design._target || tab;
				let target = doc.getElementById( aim );
				const view = doc.defaultView;

				// An id the page has not got - the block of a feature, and the specimen may be a project's own - is looked at where the object is,
				// and where that is missing too, at the top of the page
				[ tab, Nino.admin.design.THEMES_ANCHOR ].forEach( function( fallback ) {

					if( target === null && aim !== fallback ) {
						aim = fallback;
						target = doc.getElementById( aim );
					}
				} );

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
		 *	The list of the typefaces on offer has its own room to take, for the same
		 *	reasons (see Nino.admin['design-typefaces']._fitFaces()): a window that is
		 *	resized gives it the room the window has left under it, as it gives the stage
		 *	its own - and the loader stands on no stage, so it is asked for first
		 *
		 *	It is asked for wherever the stage may have moved or the window changed: as
		 *	the screen is drawn, when the window is resized (see _fitLater()), when a
		 *	viewport is chosen, when a view is opened and when a preview has come back.
		 *	The last two are for one column, where the controls stand over the stage and
		 *	a column of another height - another object, the brand warning of the colours -
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

			Nino.admin['design-typefaces']._fitFaces();

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

	/*	The setup by its objects and its aspects - the page, the header, the footer, a section, a card, the buttons, the lists, the tables and
		the features that bring variants (Setup::OBJECTS and the features) are the tabs; the aspects the design has (Setup::TABS: Base, Text,
		Surface, Colour, Motion and, for the page, CSS) are the blocks of an accordion under the tab that is open -, one object at a time, with the preview
		beside it and the bar under it, which are the panel's (see Nino.admin.design): the second mode of its screen, reached by Edit on a row
		of the list of the themes and left by Back to list. The shell does not ask for it, the screen of the panel draws it. It is drawn from the
		state of the panel and writes through it: the working copy, the selection and the draft are not copied here, only which object is open
		and which aspect of it. An object draws a block for each aspect it has a control on, from the table (see Setup::LAYOUT) - the table
		says for every control its aspect and its object, and the screen reads it the other way round than it used to: by the object first.
		CSS has no object in the table - it is one field, the css of the theme - and is the aspect of the page	*/
	Nino.admin.design.views = {

		/*	The word the label and the hint of a strip of the page stand under (/_admin/design/label/<word> and
			/_admin/design/hint/<word>), by the key the table has the strip under	*/
		PAGE_WORDS	: { size : 'size', width : 'pagewidth', whitespace : 'whitespace', radius : 'radius', 'radius-alt' : 'radius-alt' },

		/*	The parts of the motion that wait for another, by the part: the part it follows and the values of that part at which it
			means nothing. The strength and the speed of the animation mean something once there is an effect, the strength of the hover
			once there is a kind of it that has one - not the empty kind, which is Nino.css's own, and not none. Until then their selects
			are switched off (see _motionOff()) and their heads say nothing	*/
		MOTION_FOLLOWS	: { strength : [ 'effect', [ '' ] ], speed : [ 'effect', [ '' ] ], 'hover-strength' : [ 'hover', [ '', 'none' ] ] },

		/*	The aspect of the design that has no control in the table and is the field of the css of the theme, and the object it is the
			aspect of: the page, which every theme is the css of. Both are named here because the table cannot say them	*/
		CSS_TAB		: 'css',
		CSS_OBJECT	: 'page',

		/*	The width below which the strip of the objects is a select: the one at which the rail of the workbench gives its links up for a
			select of the panels (_admin/assets/style.css, the same 64rem), as a media query, which the stylesheet of the panel names again
			(admin.css, .design-tabs)	*/
		NARROW		: '(max-width: 63.999rem)',

		// The object that is open - the page at the start of a visit, the first of the tabs. Read through _current(), which falls back to the first where this is none of them any more
		_tab		: 'page',
		/*	The block that is open under an object (see _renderRows()), by the object: the key of its aspect, or null once the person has
			closed it. An object with no entry has its first aspect open. Kept while the panel lives, like the mode of the screen and the
			object that is open, and no part of the selection and nothing that is stored: another block changes nothing but the block	*/
		_open	: {},

		// The observer of the strip's box (see _edges()): one at a time
		_observer	: null,

		/**
		 *	The object that is open: the one the screen last switched to, else - where that is none of the table's objects, a feature
		 *	that was switched off since, or a table that is another - the first
		 *
		 *	@return		{string}
		 */
		_current : function() {

			const objects = Nino.admin.design._objectKeys();

			return objects.indexOf( Nino.admin.design.views._tab ) !== -1 || objects.length === 0 ? Nino.admin.design.views._tab : objects[0];
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
		 *	scale, -1 to +1 for a choice, 0 and 1 for a toggle, 0 to 4 for a grade, which only grows from the
		 *	kernel's own (see RANGE)
		 *
		 *	@param		{string}	kind				'scale', 'choice', 'toggle' or 'grade'
		 *
		 *	@return		{Array}
		 */
		_positions : function( kind ) {

			const range = Nino.admin.design.RANGE[kind] || [ 0, 0 ];
			const positions = [];

			for( let position = range[0]; position <= range[1]; position++ )
				positions.push( position );

			return positions;
		},

		/**
		 *	The word a position is declared under in a stylesheet - the middle one of
		 *	the five design/list handed over is the position 0 (see Setup::word()). A
		 *	choice knob names its three positions by these words, a toggle its two by the
		 *	middle and the one after it
		 *
		 *	@param		{number}	position
		 *
		 *	@return		{string}					least, less, default, more or most
		 */
		_word : function( position ) {

			const steps = ( Nino.admin.design._data || {} ).steps || [];

			return steps[ Math.max( 0, Math.min( steps.length - 1, position + Nino.admin.design.RANGE.scale[1] ) ) ];
		},

		/**
		 *	The rows an object is drawn in, out of the table and nothing else: for each aspect the object has a control on, in the
		 *	order the table gives the aspects, the controls of it in the order of their numbers. A control is the keys that stand at
		 *	one place: the cover, the row and the column of a section are one, and so are the second colour and the row of Harmony it
		 *	ends. An aspect the object has no control on has no row - but for the css, which is no control of the table and is a row
		 *	of the page, with no controls
		 *
		 *	@param		{string}	object		A key of the table's objects
		 *
		 *	@return		{Array}						{ tab, object, controls : [ [ key, ... ], ... ] } for each row; empty for an object with no control
		 */
		_rows : function( object ) {

			const views = Nino.admin.design.views;
			const layout = ( Nino.admin.design._data || {} ).layout || {};
			const controls = layout.controls || {};

			return ( layout.tabs || [] ).map( function( tab ) {

				const places = {};

				Object.keys( controls ).forEach( function( key ) {

					if( controls[key][0] === tab && controls[key][1] === object )
						( places[ controls[key][2] ] = places[ controls[key][2] ] || [] ).push( key );
				} );

				return {
					tab 			: tab,
					object 		: object,
					controls 	: Object.keys( places ).sort( function( a, b ) { return a - b } ).map( function( order ) { return places[order] } ),
				};
			} ).filter( function( row ) { return row.controls.length > 0 || ( row.tab === views.CSS_TAB && row.object === views.CSS_OBJECT ) } );
		},

		/**
		 *	The objects as the tabs and the select name them: each with its word - the panel's for the page and the parts, the feature's
		 *	own name for a feature (see _objectName()) - in the order of the table. The one model both are drawn from
		 *
		 *	@return		{Array}					{ key, label } for each object
		 */
		_objectTabs : function() {

			const views = Nino.admin.design.views;

			return Nino.admin.design._objectKeys().map( function( object ) {
				return { key : object, label : views._objectName( object ) };
			} );
		},

		/**
		 *	The bar of the objects: a row of Back to list, the tablist of the objects and a select of the same. Back to list is a button
		 *	of its own, no tab - it opens no object, it leaves them - and stands outside the tablist, so that the arrow keys walk the
		 *	objects and nothing else. The tablist is a tablist rather than a button group, because these really are panels: aria-selected,
		 *	not aria-pressed. It scrolls in its own box where it is wider than the bar, the edge that has more of it shadowed (see
		 *	_edges()). Below the width where the rail of the workbench gives up its links (NARROW) the stylesheet shows the select
		 *	instead of the strip, and the way back above it: the one choice, drawn twice from one model (_objectTabs()), and
		 *	_switchTab() puts both at the object that is open. The bar stands at the top of the pane of the tab, under the strip of
		 *	the shell (see Nino.admin.design._render())
		 *
		 *	@return		{HTMLElement}
		 */
		_renderTabs : function() {

			const design = Nino.admin.design;
			const views = design.views;
			const objects = views._objectTabs();
			const name = Nino.content.getText('/_admin/design/label/object');

			const bar = Nino.adminUi.el( 'div', 'nino-admin-tabs nino-admin-tabs--bar design-tabs' );
			bar.id = 'design-tabs';

			const back = Nino.adminUi.el( 'button', 'nino-admin-tab design-back', Nino.content.getText('/_admin/common/label/back') );
			back.type = 'button';
			back.id = 'design-back';
			back.addEventListener( 'click', function() { design._back() } );

			const list = design._tabs( name, 'design-views', 'tab', objects, views._current(), function( key ) {
				views._switchTab( key );
			} );

			const wrap = Nino.adminUi.el( 'div', 'design-views-wrap' );
			wrap.appendChild( list );
			views._edges( wrap, list );

			const select = Nino.adminUi.el( 'select', 'design-object-select' );
			select.id = 'design-object-select';
			select.setAttribute( 'aria-label', name );

			objects.forEach( function( object ) {
				const option = dc.createElement('option');
				option.value = object.key;
				option.textContent = object.label;
				option.selected = object.key === views._current();
				select.appendChild( option );
			} );

			select.addEventListener( 'change', function() { views._switchTab( select.value ) } );

			bar.appendChild( back );
			bar.appendChild( wrap );
			bar.appendChild( select );

			return bar;
		},

		/**
		 *	Shadow the edge of the strip that has more of it behind: the wrapper carries is-more-start while the strip is scrolled
		 *	away from its start and is-more-end while there is more to the right of it, and the stylesheet draws a shadow with each
		 *	(.design-views-wrap::before and ::after). Painted when the strip scrolls and when its box changes size
		 *	- a window that is resized, the words of another language - for as far as the browser tells the script of it.
		 *	A strip that was not there - the select stood in its place - and is, its box going from no width to some, brings the
		 *	tab that is open into sight: it may have been chosen in the select, out of the strip's reach. The observer of the bar
		 *	that was drawn before is let go of (see _observer), so that a screen drawn again does not leave one behind
		 *
		 *	@param		{Element}	wrap			The box the strip stands in
		 *	@param		{Element}	list			The strip, which is the box that scrolls
		 *
		 *	@return		void
		 */
		_edges : function( wrap, list ) {

			const paint = function() {
				wrap.classList.toggle( 'is-more-start', list.scrollLeft > 1 );
				wrap.classList.toggle( 'is-more-end', list.scrollLeft + list.clientWidth < list.scrollWidth - 1 );
			};

			list.addEventListener( 'scroll', paint );

			const views = Nino.admin.design.views;

			if( views._observer !== null ) {
				views._observer.disconnect();
				views._observer = null;
			}

			if( typeof wn.ResizeObserver === 'function' ) {

				let width = 0;

				views._observer = new wn.ResizeObserver( function() {

					const now = list.clientWidth;
					const open = dc.getElementById( 'design-tab-'+ views._current() );

					if( width === 0 && now > 0 && open !== null && typeof open.scrollIntoView === 'function' )
						open.scrollIntoView( { block : 'nearest', inline : 'nearest' } );

					width = now;
					paint();
				} );

				views._observer.observe( list );
			}

			paint();
		},

		/**
		 *	Mark the object that is open on both the strip and the select, as a press on the one has to show on the other. The
		 *	buttons are the strip's own business when one of them was pressed (Nino.adminUi.buttonRow()); this is for the select
		 *	being used, and the object being set from anywhere else
		 *
		 *	@param		{string}	key				A key of the table's objects
		 *
		 *	@return		void
		 */
		_markTab : function( key ) {

			Nino.admin.design._objectKeys().forEach( function( object ) {

				const tab = dc.getElementById( 'design-tab-'+ object );

				if( tab === null )
					return;

				tab.classList.toggle( 'is-active', object === key );
				tab.setAttribute( 'aria-selected', object === key ? 'true' : 'false' );
				tab.tabIndex = object === key ? 0 : -1;
			} );

			const select = dc.getElementById('design-object-select');

			if( select === null )
				return;

			select.value = key;

			Array.prototype.forEach.call( select.querySelectorAll('option'), function( option ) { option.selected = option.value === key } );

			const tab = dc.getElementById( 'design-tab-'+ key );

			if( tab !== null && typeof tab.scrollIntoView === 'function' )
				tab.scrollIntoView( { block : 'nearest', inline : 'nearest' } );
		},

		/**
		 *	Open another object.
		 *
		 *	Only the column is redrawn, never the frame beside it: the page in
		 *	it is the same page under every object, so rebuilding it would cost a
		 *	request and a flash for a click that changed which controls are on
		 *	screen and nothing at all about the design. What the frame does is
		 *	scroll, smoothly, to the anchor of the object that was opened - once the
		 *	page in it stands at the height the stage has for that object.
		 *
		 *	Nor the strip of the objects. The tab the switch was made on is
		 *	the one the keyboard is on, and the strip used to be drawn again
		 *	with the column: an arrow key opened the next tab and dropped the
		 *	focus onto the page, so the next arrow went nowhere. The select and the strip are
		 *	each put at the object that is open (_markTab()) instead
		 *
		 *	@param		{string}	key			A key of the table's objects
		 *
		 *	@return		void
		 */
		_switchTab : function( key ) {

			if( key === Nino.admin.design.views._current() )
				return;

			Nino.admin.design.views._tab = key;
			Nino.admin.design.views._markTab( key );

			// Where a change was looked at belonged to the object that was left: the
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
		 *	The body of the aspect CSS: the css of the theme, in a field of its own, as tall as the stylesheet makes it - the
		 *	aspect has nothing else. Every input sets it in the working copy and asks for a preview - the debounce of 350 ms every
		 *	control has - which compiles it as the last section of the sheet; the draft and the theme keep it with the rest of
		 *	the setup. The head of the block names the aspect, so the field carries the title alone
		 *
		 *	@param		{Element}	card			The body of the block
		 *
		 *	@return		void
		 */
		_paintCustomCss : function( card ) {

			const design = Nino.admin.design;
			const ui = Nino.adminUi;
			const title = Nino.content.getText('/_admin/design/theme/css/title');

			card.innerHTML = '';

			const label = ui.el( 'label', 'nino-admin-field' );
			label.appendChild( ui.el( 'span', 'design-theme-css-name', title ) );

			const box = ui.el( 'textarea', 'nino-admin-input design-theme-css' );
			box.id = 'design-theme-css';
			box.rows = 30;
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
		 *	The rows of an object into a box, one card each, in the order the table gives them: the blocks of an accordion, an aspect
		 *	each. The head of a block is a button with the word of its aspect, and the row itself, the body of the block, is drawn
		 *	while the block is open and not before. One block is open, the one the person left open under the object and the first
		 *	where there is none (see _blockOf()). An object that has one aspect is an accordion of one block, open to begin with and
		 *	to be closed like any other
		 *
		 *	@param		{string}	object		A key of the table's objects
		 *	@param		{Element}	box
		 *
		 *	@return		void
		 */
		_renderRows : function( object, box ) {

			const views = Nino.admin.design.views;
			const rows = views._rows( object );
			const open = views._blockOf( object, rows );

			rows.forEach( function( row ) {
				box.appendChild( views._renderRow( row, row.tab === open ) );
			} );
		},

		/**
		 *	The block of an object that is open: the one the person left open under it, else - where nothing was chosen under the
		 *	object yet - its first
		 *
		 *	@param		{string}	object		A key of the table's objects
		 *	@param		{Array}		rows			The rows of the object, see _rows()
		 *
		 *	@return		{?string}						The aspect of the row that is open; null where the person closed it, and for an object with no rows
		 */
		_blockOf : function( object, rows ) {

			const chosen = Nino.admin.design.views._open[object];
			const tabs = rows.map( function( row ) { return row.tab } );

			if( chosen === null || tabs.length === 0 )
				return null;

			return tabs.indexOf( chosen ) !== -1 ? chosen : tabs[0];
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
		 *	One row: a card of the controls the table gives an object on an aspect, a block of the accordion - the head and, while the
		 *	block is open, the body under it (see _setBlock()). The card says which aspect and which object it is the row of, and every
		 *	control in it says which keys it is (see _fillRow()), so that what is drawn can be read against the table
		 *
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *	@param		{boolean}	[open]		Whether the block is open
		 *
		 *	@return		{Element}
		 */
		_renderRow : function( row, open ) {

			const views = Nino.admin.design.views;

			const card = dc.createElement('div');
			card.id = views._rowId( row.tab, row.object );
			card.className = 'nino-admin-card design-form-section design-accordion';
			card.dataset.tab = row.tab;
			card.dataset.object = row.object;

			card.appendChild( views._renderHead( row ) );
			views._setBlock( card, row, open === true );

			return card;
		},

		/**
		 *	The head of a block: a button that opens and closes it, with the word of its aspect, the line that names what
		 *	stands elsewhere than at the defaults while the block is closed (see _paintHead()) and a chevron, which is
		 *	decoration. The word is the button's name and the line its description; aria-expanded says whether the block
		 *	is open and, while it is, aria-controls which body it is the head of (see _paintHead()) - a closed block has no
		 *	body in the document, and its head names none
		 *
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *
		 *	@return		{Element}
		 */
		_renderHead : function( row ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const id = views._rowId( row.tab, row.object );

			const name = dc.createElement('span');
			name.id = id+ '-name';
			name.className = 'design-accordion-name';
			name.textContent = Nino.content.getText('/_admin/design/tab/'+ row.tab+ '/label');

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
			head.addEventListener( 'click', function() { views._toggleBlock( row.object, row.tab, head ) } );

			head.appendChild( name );
			head.appendChild( summary );
			head.appendChild( chevron );

			return head;
		},

		/**
		 *	The body of a block: the controls of its row (see _fillRow()), in the box the head names in aria-controls
		 *
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *
		 *	@return		{Element}
		 */
		_renderBody : function( row ) {

			const body = dc.createElement('div');
			body.id = Nino.admin.design.views._rowId( row.tab, row.object )+ '-body';
			body.className = 'design-accordion-body';

			Nino.admin.design.views._fillRow( body, row );

			return body;
		},

		/**
		 *	Put a block in the state it is to be in, where it stands: the body is drawn when the block is opened and taken away
		 *	when it is closed - the controls of a closed block are not drawn - and the head is painted for it. Nothing else is
		 *	drawn again, so that the head that was pressed is still the element the focus is on
		 *
		 *	@param		{Element}	card			The card of the row
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *	@param		{boolean}	open
		 *
		 *	@return		void
		 */
		_setBlock : function( card, row, open ) {

			const views = Nino.admin.design.views;
			const body = card.querySelector('.design-accordion-body');

			if( open === true && body === null )
				card.appendChild( views._renderBody( row ) );

			if( open === false && body !== null )
				body.remove();

			views._paintHead( card.querySelector('.design-accordion-head'), row, open );
		},

		/**
		 *	The head of a block as the state of the block has it: aria-expanded says whether it is open, and the line says -
		 *	while it is closed - which of the controls of its row stand elsewhere than at the defaults (see _summary()): nothing
		 *	where none does, and nothing while the block is open, where the controls themselves are in view. The line is
		 *	cut with dots where the head is too narrow, so its whole text is its title
		 *
		 *	@param		{Element}	head
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *	@param		{boolean}	open
		 *
		 *	@return		void
		 */
		_paintHead : function( head, row, open ) {

			const summary = head.querySelector('.design-accordion-summary');
			const line = open === true ? '' : Nino.admin.design.views._summary( row );

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
		 *	open is kept for the object (see _open). It is a matter of the column and of nothing else: the selection stays as it is, no
		 *	preview is asked for and the frame is not moved - a change inside a block jumps, as it did, and this does not. The head
		 *	that was pressed stays where it stood in the window (see _holdHead())
		 *
		 *	@param		{string}	object		The object the block is under
		 *	@param		{string}	tab				The aspect whose head was pressed
		 *	@param		{Element}	head			That head
		 *
		 *	@return		void
		 */
		_toggleBlock : function( object, tab, head ) {

			const views = Nino.admin.design.views;
			const rows = views._rows( object );
			const was = views._blockOf( object, rows );
			const top = head.getBoundingClientRect().top;

			views._open[object] = was === tab ? null : tab;

			rows.forEach( function( row ) {

				const card = row.tab === was || row.tab === tab ? dc.getElementById( views._rowId( row.tab, object ) ) : null;

				if( card !== null )
					views._setBlock( card, row, views._open[object] === row.tab );
			} );

			views._holdHead( head, top );
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
		 *	says the word of the set that is chosen, whichever it is, so that a person sees which one is on, and after it the name of each option of a style chosen for it
		 *
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *
		 *	@return		{string}
		 */
		_summary : function( row ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			let lines = [];

			row.controls.forEach( function( keys ) {

				const path = keys[0].split('/');

				if( path[0] === 'parts' )
					lines = lines.concat( [ design._setName( path[1], design._edit.parts[ path[1] ] ) ], views._stylesSaid( path[1] ) );
				else if( path[0] === 'features' && path[2] === 'set' )
					lines = lines.concat( views._variantSaid( path[1] ), views._stylesSaid( path[1] ) );
				else
					lines = lines.concat( views._deviations( keys, row.object ) );
			} );

			return lines.join(' · ');
		},

		/**
		 *	What one control says where it stands elsewhere than at the default: its label and the word of the position it is at,
		 *	as its strip shows them - made of the very texts and options the strip is drawn from (see _knobStrip(), _radiiStrip()
		 *	and _pageName()). A font role says it for each of the three cells of its row that differs, by the name of the cell
		 *	and the word it shows. A knob the chosen template does not answer to is greyed out, and no part of what the design
		 *	is: it says nothing. The corners of a section - one choice for the cover, the row and the column - count once, and
		 *	say the word the strip shows, which is the cover's. A part of the palette says its hex - the brand colour, and the
		 *	second colour where one is set, which stands for the Harmony it ends - or its label and the word of its position,
		 *	the mode too (see _colourSaid()); a part of the motion says its label and the word it is set to, the strength and the
		 *	speed of the animation only while there is an effect and the strength of the hover of the pictures only while there is
		 *	a kind of it that has one, since their selects are switched off until then (see MOTION_FOLLOWS). A template says
		 *	nothing here: it is no position, and the head of its block names it whatever it is (see _summary())
		 *
		 *	@param		{Array}		keys			The keys of the control, as _rows() has them
		 *	@param		{string}	object		The object the control stands under
		 *
		 *	@return		{Array}							The lines, none where the control stands at the default
		 */
		_deviations : function( keys, object ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const edit = design._edit;
			const normal = ( design._data || {} ).defaults || {};
			const path = keys[0].split('/');

			if( path[0] === 'knobs' ) {

				if( views._answered( path[1] ).indexOf( path[2] ) === -1 )
					return [];

				const chosen = views._knobValue( edit.knobs, path[1], path[2] );
				const strip = views._knobStrip( path[1], path[2] );

				return chosen === views._knobValue( normal.knobs || {}, path[1], path[2] ) ? [] : [ views._said( strip.name, strip.options, chosen ) ];
			}

			// A knob a feature brings: said as the knobs of a part are, by the label its head gives it and the word of its value - a variant says it in its own row (see _variantSaid())
			if( path[0] === 'features' ) {

				if( path[2] !== 'knobs' )
					return [];

				const found = views._featureKnob( path[1], path[3] );

				if( found.answered === false )
					return [];

				const value = views._featureValue( path[1], path[3], found.meta );

				return JSON.stringify( value ) === JSON.stringify( found.meta['default'] ) ? [] : [ views._featureSaid( found.meta, value ) ];
			}

			if( path[0] === 'radii' ) {

				const strip = views._radiiStrip( keys, object );
				const given = normal.radii || {};

				return strip.kinds.some( function( kind ) { return edit.radii[kind] !== given[kind] } ) === true ? [ views._said( strip.name, strip.options, edit.radii[ strip.kinds[0] ] ) ] : [];
			}

			if( path[0] === 'fonts' ) {

				const role = path[1];
				const chosen = edit.fonts[role] || {};
				const given = ( normal.fonts || {} )[role] || {};
				const lines = [];

				if( ( chosen.family || '' ) !== ( given.family || '' ) )
					lines.push( views._fontLabel( role, 'family' )+ ' '+ views._familyLabel( role, chosen.family || '' ) );

				if( ( chosen.weight || 'normal' ) !== ( given.weight || 'normal' ) )
					lines.push( views._fontLabel( role, 'weight' )+ ' '+ Nino.content.getText('/_admin/design/weight/'+ ( chosen.weight || 'normal' ) ) );

				if( String( chosen.size || '' ) !== String( given.size || '' ) )
					lines.push( views._fontLabel( role, 'size' )+ ' '+ views._sizeInForce( role )+ ' '+ Nino.content.getText('/_admin/design/fontsize/unit') );

				return lines;
			}

			if( path[0] === 'colours' )
				return keys.map( function( key ) { return views._colourSaid( key.split('/')[1] ) } ).filter( function( line ) { return line !== '' } );

			if( path[0] === 'motion' ) {

				const part = path[1];
				const value = ( edit.motion || {} )[part] || '';
				const follows = views.MOTION_FOLLOWS[part];

				// A part that waits for another means something once that has a value (MOTION_FOLLOWS): until then its select is switched off, and it says nothing
				if( value === ( ( normal.motion || {} )[part] || '' ) || ( follows !== undefined && follows[1].indexOf( ( edit.motion || {} )[ follows[0] ] || '' ) !== -1 ) )
					return [];

				return [ views._said( Nino.content.getText('/_admin/design/motion/'+ part+ '/label'), views._motionOptions( part ), value ) ];
			}

			if( views.PAGE_WORDS[ keys[0] ] === undefined )
				return [];

			return edit[ keys[0] ] === normal[ keys[0] ] ? [] : [ views._said( views._pageName( keys[0] ), views._numbered( views._positions('scale'), 0 ), edit[ keys[0] ] ) ];
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
			const views = Nino.admin.design.views;
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
				return views._said( Nino.content.getText('/_admin/design/colour/mode/label'), views._modeOptions( meta ), value );

			const strip = views._colourStrip( knob, meta );

			return views._said( strip.name, strip.options, value );
		},

		/**
		 *	The controls of a row into the body of its block, in the order of the table. What a control is drawn as
		 *	follows from the first segment of its key: a template (parts), a knob (knobs), the corners of a kind of
		 *	surface (radii), a font role (fonts), a part of the motion of the page (motion), a part of the palette (colours) - and a
		 *	strip of the page for the rest: the root size, the width, the whitespace and the two radii. Every control
		 *	carries the keys it stands for in data-control, joined by a space. The five roles are the rows of one table
		 *	under the heading of the fonts, the five parts of the motion of the page the fields of one grid, and the palette's
		 *	knobs follow the brand colour under the heading Tuning
		 *
		 *	@param		{Element}	card			The body of the block
		 *	@param		{Object}	row				{ tab, object, controls }, one of _rows()
		 *
		 *	@return		void
		 */
		_fillRow : function( card, row ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const palette = ( design._data || {} ).palette || {};
			const fields = {};
			let table = null;
			let grid = null;
			let tuned = false;

			// The css of the theme is no control of the table: its block is the one field
			if( row.tab === views.CSS_TAB )
				return views._paintCustomCss( card );

			row.controls.forEach( function( keys ) {

				const path = keys[0].split('/');

				// A control is put where it stands and told what it is
				const put = function( node, into ) {
					node.dataset.control = keys.join(' ');
					( into || card ).appendChild( node );
				};

				// The select of the template and, under it, one for each style the template brings: no control of the table, so no key to stand for. The same for a feature: the select of its variant, and under it the styles it declares for all its variants
				if( path[0] === 'parts' ) {
					put( views._partField( design._row( path[1] ) ) );
					return views._styleFields( path[1] ).forEach( function( field ) { card.appendChild( field ) } );
				}

				// A feature that brings variants: the choice of the variant on Templates, a knob of the variant on the tab its head names
				if( path[0] === 'features' && path[2] === 'set' ) {
					put( views._featureField( path[1] ) );
					return views._styleFields( path[1] ).forEach( function( field ) { card.appendChild( field ) } );
				}

				if( path[0] === 'features' )
					return put( views._featureKnobRow( path[1], path[3] ) );

				if( path[0] === 'knobs' )
					return put( views._knobRow( path[1], path[2], views._answered( path[1] ).indexOf( path[2] ) !== -1 ) );

				if( path[0] === 'radii' )
					return put( views._radiiRow( keys, row.object ) );

				if( path[0] === 'fonts' ) {

					if( table === null ) {
						card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/label/fonts') ) );
						table = views._rolesTable();
						card.appendChild( table.table );
					}

					return put( views._fontRow( path[1] ), table.body );
				}

				if( path[0] === 'motion' ) {

					if( grid === null ) {
						grid = dc.createElement('div');
						grid.className = 'design-form-grid';
						card.appendChild( grid );
					}

					fields[ path[1] ] = views._motionField( path[1] );
					return put( fields[ path[1] ], grid );
				}

				if( path[0] === 'colours' ) {

					if( path[1] === 'primary' ) {
						put( views._primaryField() );
						return card.appendChild( views._brandWarning() );
					}

					// The knobs under the colour the palette is solved from stand under a small heading, as a block of rows does
					if( tuned === false ) {
						card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/label/tuning') ) );
						tuned = true;
					}

					// Harmony *is* the second colour: the swatch that overrides its four positions ends its row
					if( keys.indexOf('colours/harmony') !== -1 )
						return put( views._colourKnobRow( 'harmony', palette.harmony, views._secondField() ) );

					if( path[1] === 'mode' )
						return put( views._modeField( palette.mode ) );

					return put( views._colourKnobRow( path[1], palette[ path[1] ], null ) );
				}

				put( views._pageStrip( keys[0] ) );
			} );

			// A part of the motion that waits for another only means something once that has a value
			const selects = {};

			Object.keys( fields ).forEach( function( key ) { selects[key] = fields[key].querySelector('select') } );
			views._motionOff( selects );
		},

		/**
		 *	The label of one of the strips of the page: what it is called, by the key the table has it under
		 *
		 *	@param		{string}	key				size, width, whitespace, radius or radius-alt
		 *
		 *	@return		{string}
		 */
		_pageName : function( key ) {
			return Nino.content.getText('/_admin/design/label/'+ Nino.admin.design.views.PAGE_WORDS[key] );
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
			const views = Nino.admin.design.views;
			const edit = design._edit;
			const word = views.PAGE_WORDS[key];

			return design._stripRow(
				'page/'+ key, views._pageName( key ), Nino.content.getText('/_admin/design/hint/'+ word ),
				views._numbered( views._positions('scale'), 0 ), edit[key], function( position ) {
					edit[key] = position;
					design._target = views._anchorOf( key );
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
			const views = Nino.admin.design.views;
			const edit = design._edit;
			const strip = views._radiiStrip( keys, object );
			const kinds = strip.kinds;
			const differ = kinds.some( function( kind ) { return edit.radii[kind] !== edit.radii[ kinds[0] ] } );

			const field = design._stripRow(
				'page/radii-'+ strip.word, strip.name, differ === true ? Nino.content.getText('/_admin/design/hint/radii') : '',
				strip.options, edit.radii[ kinds[0] ], function( word ) {

					kinds.forEach( function( kind ) { edit.radii[kind] = word } );

					// The three agree now: nothing is left to say
					field.querySelector('small').textContent = '';

					design._target = views._anchorOf( keys[0] );
					design._preview();
				} );

			return field;
		},

		/**
		 *	One of the five parts of the motion of the page, as a select: the effect, the strength and the speed of its animation, the kind and
		 *	the strength of the hover of its pictures. '' is what Nino.css does by itself, and is what a fresh setup has
		 *
		 *	@param		{string}	key				effect, strength, speed, hover or hover-strength
		 *
		 *	@return		{Element}
		 */
		_motionField : function( key ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;

			return views._select(
				'design-motion-'+ key, Nino.content.getText('/_admin/design/motion/'+ key+ '/label'), views._motionOptions( key ), design._edit.motion[key] || '',
				function( value ) { views._setMotion( key, value ) } );
		},

		/**
		 *	What the select of one of the five parts of the motion offers, and what the head of its block says a value
		 *	of it is: the default first, then a word for each value of the answer - the strength of the hover has the three
		 *	of the animation
		 *
		 *	@param		{string}	key				effect, strength, speed, hover or hover-strength
		 *
		 *	@return		{Array}						{ value, label }
		 */
		_motionOptions : function( key ) {

			const design = Nino.admin.design;
			const values = { effect : design._data.effects, strength : design._data.strengths, speed : design._data.speeds, hover : design._data.hovers, 'hover-strength' : design._data.strengths }[key] || [];

			return [ { value : '', label : Nino.content.getText('/_admin/design/motion/default') } ].concat( values.map( function( value ) {
				return { value : value, label : Nino.content.getText('/_admin/design/motion/'+ key+ '/'+ value ) };
			} ) );
		},

		/**
		 *	The parts of the motion that wait for another only mean something once that has a value (MOTION_FOLLOWS): the strength and
		 *	the speed of the animation once there is an effect, the strength of the hover once there is a kind of it that has one. Until
		 *	then their selects are switched off rather than left to say nothing
		 *
		 *	@param		{Object}	selects			The selects that are drawn, by the part: one that is not there is left out
		 *
		 *	@return		void
		 */
		_motionOff : function( selects ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;

			Object.keys( views.MOTION_FOLLOWS ).forEach( function( part ) {

				const select = selects[part];
				const follows = views.MOTION_FOLLOWS[part];

				if( select !== null && select !== undefined )
					select.disabled = follows[1].indexOf( design._edit.motion[ follows[0] ] || '' ) !== -1;
			} );
		},

		/**
		 *	The select a template is chosen in: the one word of its label over it - the head of its block says which part it
		 *	is, and the object a second time would be too much -, the catalogue as its options and the description of the
		 *	chosen one as the line under it. The same for each of the five, in the Base block of its object
		 *
		 *	@param		{Object}	row			One part, as design/list answered it
		 *
		 *	@return		{Element}
		 */
		_partField : function( row ) {

			const field = Nino.admin.design.views._select(
				'design-set-'+ row.part, Nino.content.getText('/_admin/design/label/template'),
				Object.keys( row.catalogue || {} ).map( function( set ) {
					return { value : set, label : Nino.admin.design._setLabel( set, row.catalogue[set] ) };
				} ), Nino.admin.design._edit.parts[row.part], function( value ) {
					Nino.admin.design.views._setPart( row.part, value );
				},
				( ( row.catalogue || {} )[ Nino.admin.design._edit.parts[row.part] ] || {} ).description || ''
			);

			return field;
		},

		/**
		 *	The styles the template chosen for a part brings, as design/list answered them
		 *
		 *	@param		{string}	part
		 *
		 *	@return		{Object}					style => { label, options : option => { name, description } }; {} where the template brings none
		 */
		_stylesOfPart : function( part ) {

			const design = Nino.admin.design;
			const styles = ( ( design._row( part ).catalogue || {} )[ design._edit.parts[part] ] || {} ).styles;

			return styles === undefined || styles === null || Array.isArray( styles ) === true ? {} : styles;
		},

		/**
		 *	The styles an object of the panel brings, as design/list answered them: those of the template chosen for a part, or - for a feature -
		 *	the ones it declares for all its variants. A key of a feature is never the name of a part (see Setup::features())
		 *
		 *	@param		{string}	object		A part, or the key of a feature
		 *
		 *	@return		{Object}					style => { label, options : option => { name, description } }; {} where there are none
		 */
		_stylesOfObject : function( object ) {

			const feature = Nino.admin.design._featuresOf()[object];

			if( feature === undefined )
				return Nino.admin.design.views._stylesOfPart( object );

			return feature.styles === undefined || feature.styles === null || Array.isArray( feature.styles ) === true ? {} : feature.styles;
		},

		/**
		 *	The style => option pairs the screen holds for an object: the part's in the selection, or the feature's in its entry. An empty
		 *	object where nothing is chosen
		 *
		 *	@param		{string}	object		A part, or the key of a feature
		 *
		 *	@return		{Object}
		 */
		_chosenStyles : function( object ) {

			const design = Nino.admin.design;

			if( design._featuresOf()[object] === undefined )
				return design._edit.styles[object] || {};

			return ( ( design._edit.features || {} )[object] || {} ).styles || {};
		},

		/**
		 *	The selects of the styles of an object, in the order the head declares them: each headed with the label the head gives the style,
		 *	"None" first and the files beside it under the names their heads give them, with the description of the one chosen under it. They
		 *	stand under the select of the template in its block (see _fillRow()), and under the select of the variant in the block of a feature,
		 *	where they hold for every variant
		 *
		 *	@param		{string}	object		A part, or the key of a feature
		 *
		 *	@return		{Array}						The fields, none where the object brings no style
		 */
		_styleFields : function( object ) {

			const views = Nino.admin.design.views;
			const styles = views._stylesOfObject( object );

			return Object.keys( styles ).map( function( style ) {

				const meta = styles[style];
				const options = meta.options || {};
				const chosen = views._chosenStyles( object )[style];
				const current = options[chosen] === undefined ? '' : chosen;

				const field = views._select(
					'design-style-'+ object+ '-'+ style, meta.label,
					[ { value : '', label : Nino.content.getText('/_admin/design/style/none') } ].concat( Object.keys( options ).map( function( option ) {
						return { value : option, label : options[option].name };
					} ) ), current, function( value ) {
						views._setStyle( object, style, value, field );
					}, current === '' ? '' : options[current].description );

				field.classList.add('design-style-select');
				field.dataset.style = style;

				return field;
			} );
		},

		/**
		 *	Choose an option of a style of a part or a feature: None is the object as it is and leaves no entry, as a feature that is as it is
		 *	leaves none (see _pruneFeature()). The description under the select is written in place, and the frame goes to where the object is
		 *	looked at: where the template is (see OBJECT_ANCHORS), or the block of the feature (see _anchorOf()). The styles of a feature are
		 *	the feature's, not the variant's: the feature is chosen at the variant it stands at, if nothing was chosen for it yet
		 *
		 *	@param		{string}		object		A part, or the key of a feature
		 *	@param		{string}		style			A style the object declares
		 *	@param		{string}		option		An option of it, '' for None
		 *	@param		{Element}		[field]		The select field of the style, whose note is the description of the option
		 *
		 *	@return		void
		 */
		_setStyle : function( object, style, option, field ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const options = ( views._stylesOfObject( object )[style] || {} ).options || {};
			const description = option === '' || options[option] === undefined ? '' : options[option].description;
			const ofFeature = design._featuresOf()[object] !== undefined;
			let note = field ? field.querySelector('small') : null;
			let chosen;

			if( ofFeature === true ) {
				const entry = design._edit.features[object] || { set : views._chosenSet( object ), knobs : {}, styles : {} };
				entry.styles = entry.styles || {};
				design._edit.features[object] = entry;
				chosen = entry.styles;
			}
			else {
				design._edit.styles[object] = design._edit.styles[object] || {};
				chosen = design._edit.styles[object];
			}

			if( option === '' )
				delete chosen[style];
			else
				chosen[style] = option;

			if( ofFeature === true )
				views._pruneFeature( object );

			// A select on None has no line under it: it is made when there is something to say
			if( field && note === null && description !== '' ) {
				note = dc.createElement('small');
				field.appendChild( note );
			}

			if( note !== null )
				note.textContent = description;

			design._target = views._anchorOf( ofFeature === true ? 'features/'+ object+ '/set' : 'parts/'+ object );
			design._preview();
		},

		/**
		 *	What the head of a closed block says of the styles chosen for its object: the name of each option chosen, as the select shows it.
		 *	A choice the object does not have is none (it cannot be made on this screen, and the server drops it)
		 *
		 *	@param		{string}	object		A part, or the key of a feature
		 *
		 *	@return		{Array}						The names, none where no style is chosen
		 */
		_stylesSaid : function( object ) {

			const views = Nino.admin.design.views;
			const styles = views._stylesOfObject( object );
			const chosen = views._chosenStyles( object );

			return Object.keys( styles ).filter( function( style ) {
				return ( styles[style].options || {} )[ chosen[style] ] !== undefined;
			} ).map( function( style ) {
				return styles[style].options[ chosen[style] ].name;
			} );
		},

		/**
		 *	Choose a template for a part.
		 *
		 *	A template brings its own knobs, and the ones the last one had are not
		 *	this one's: a position the new template does not answer to goes back
		 *	to the default's position, the way the server holds it anyway - so the
		 *	screen never shows a setup the stored one could not be. The same goes for
		 *	the styles: they are the old template's, and the new one's start on None.
		 *	The rows of the knobs stand on several tabs and are drawn when those are
		 *	opened, with the knobs this template does not answer to greyed out.
		 *
		 *	The whole column of controls of the object that is open is drawn anew, as a
		 *	switch of the object draws it, so that it shows what the model holds - the line
		 *	under the select says what the template chosen is, and the strips of the part
		 *	are the knobs the set answers to - and the focus stays on the select, which is
		 *	a new one; the block that is open stays open. The head of the block says the word of the set that
		 *	is chosen once the block is closed, painted from the selection as it
		 *	stands then (see _paintHead()). The frame goes to where the template is
		 *	looked at (OBJECT_ANCHORS) - a header at the top of the page, a footer at
		 *	its foot
		 *
		 *	@param		{string}		part
		 *	@param		{string}		set
		 *
		 *	@return		void
		 */
		_setPart : function( part, set ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const edit = design._edit;
			const row = design._row( part );
			const answered = ( ( row.catalogue || {} )[set] || {} ).knobs || [];
			const normal = ( ( design._data || {} ).defaults || {} ).knobs || {};

			edit.parts[part] = set;

			// The styles are the old template's: the new one brings its own, and starts on None
			edit.styles[part] = {};

			Object.keys( edit.knobs[part] || {} ).forEach( function( knob ) {
				if( answered.indexOf( knob ) === -1 )
					edit.knobs[part][knob] = views._knobValue( normal, part, knob );
			} );

			/*	The whole column of controls, drawn from the model the way a switch of the object draws it (see _switchTab()): the
				select with its line, the strips of the knobs of the part - and every other view, which is drawn from the model
				when it is opened, shows the knobs of the set that is chosen as well. The block that is open stays open (it is
				the object's, see _open), and the select that was pressed is part of the column that is replaced: the focus follows
				it to the new one	*/
			const column = dc.getElementById('design-controls');

			if( column !== null ) {

				const had = dc.activeElement !== null && dc.activeElement === dc.getElementById( 'design-set-'+ part );

				column.replaceWith( design._renderControls() );

				const again = had === true ? dc.getElementById( 'design-set-'+ part ) : null;

				if( again !== null )
					again.focus();
			}

			design._target = views._anchorOf( 'parts/'+ part );
			design._preview();
		},

		/**
		 *	What an object of the panel is called on its tab and in the select: its word in the panel's texts for the page and the parts, and for
		 *	a feature the name its manifest gives it, which is the feature's own and no text of this panel
		 *
		 *	@param		{string}	object		A key of the table's objects
		 *
		 *	@return		{string}
		 */
		_objectName : function( object ) {

			const feature = Nino.admin.design._featuresOf()[object];

			return feature !== undefined ? feature.name : Nino.content.getText('/_admin/design/object/'+ object+ '/label');
		},

		/**
		 *	The variant a feature stands at on the screen: the one chosen for it, else the one it has before anything is chosen
		 *
		 *	@param		{string}	key				The key of a feature
		 *
		 *	@return		{string}
		 */
		_chosenSet : function( key ) {

			const feature = Nino.admin.design._featuresOf()[key] || {};
			const entry = ( Nino.admin.design._edit.features || {} )[key];

			return entry !== undefined && ( feature.catalogue || {} )[entry.set] !== undefined ? entry.set : ( feature['default'] || '' );
		},

		/**
		 *	A knob of a feature as the variant chosen has it, else - where that variant does not declare it - as the first variant
		 *	that does, in the order of the catalogue: the row is drawn, greyed out, since the table gives the knobs of every variant
		 *
		 *	@param		{string}	key				The key of a feature
		 *	@param		{string}	knob
		 *
		 *	@return		{Object}					{ meta : kind, tab, label, options, default; answered : whether the chosen variant declares it }
		 */
		_featureKnob : function( key, knob ) {

			const catalogue = ( Nino.admin.design._featuresOf()[key] || {} ).catalogue || {};
			const chosen = ( ( catalogue[ Nino.admin.design.views._chosenSet( key ) ] || {} ).knobs || {} )[knob];

			if( chosen !== undefined )
				return { meta : chosen, answered : true };

			return {
				meta : Object.keys( catalogue ).map( function( set ) { return ( catalogue[set].knobs || {} )[knob] } ).filter( function( meta ) { return meta !== undefined } )[0],
				answered : false,
			};
		},

		/**
		 *	The value a knob of a feature stands at on the screen: the one chosen, else the one its head says it stands at
		 *
		 *	@param		{string}	key
		 *	@param		{string}	knob
		 *	@param		{Object}	meta			The knob, see _featureKnob()
		 *
		 *	@return		{*}								A position, a surface, a tone or a pair of hex colours
		 */
		_featureValue : function( key, knob, meta ) {

			const entry = ( Nino.admin.design._edit.features || {} )[key];
			const value = entry !== undefined ? ( entry.knobs || {} )[knob] : undefined;

			return value === undefined ? meta['default'] : value;
		},

		/**
		 *	What the strip of a scale or a choice a feature brings is made of: the label its head gives it and its options, from the least to
		 *	the most - the numbers -2 to +2 of a scale, the three words the head gives a choice
		 *
		 *	@param		{Object}	meta			The knob, see _featureKnob()
		 *
		 *	@return		{Object}						{ name, options }, the options as { value, label, title }
		 */
		_featureStrip : function( meta ) {

			const views = Nino.admin.design.views;
			const positions = views._positions( meta.kind );

			return {
				name 		: meta.label,
				options	: meta.kind === 'choice'
					? positions.map( function( position ) {
						const label = ( meta.options || {} )[ views._word( position ) ];
						return { value : position, label : label, title : label };
					} )
					: views._numbered( positions, 0 ),
			};
		},

		/**
		 *	What a head says of a knob of a feature where it stands elsewhere than at the default: its label and the word of the value - the
		 *	position of a ladder as its strip shows it, a surface or a tone by the word of the panel's texts, a colour by its two halves
		 *
		 *	@param		{Object}	meta			The knob, see _featureKnob()
		 *	@param		{*}				value
		 *
		 *	@return		{string}
		 */
		_featureSaid : function( meta, value ) {

			const views = Nino.admin.design.views;

			if( meta.kind === 'scale' || meta.kind === 'choice' ) {
				const strip = views._featureStrip( meta );
				return views._said( strip.name, strip.options, value );
			}

			if( meta.kind === 'colour' )
				return meta.label+ ' '+ value.join(' / ');

			return meta.label+ ' '+ Nino.content.getText('/_admin/design/'+ meta.kind+ '/'+ value );
		},

		/**
		 *	What the head of the block of a feature on Templates says: the name of the variant chosen, where it is not the one the feature
		 *	has before anything is chosen
		 *
		 *	@param		{string}	key
		 *
		 *	@return		{Array}						The line, none where the variant is the feature's own
		 */
		_variantSaid : function( key ) {

			const feature = Nino.admin.design._featuresOf()[key] || {};
			const set = Nino.admin.design.views._chosenSet( key );

			return set === feature['default'] ? [] : [ ( ( feature.catalogue || {} )[set] || {} ).name || set ];
		},

		/**
		 *	The id of a control of a feature - the select of its variants, a knob of it, a half of a colour or the line that says what the colour
		 *	measures - for the label that names it and for the line that is painted in place. The parts are joined by an underscore, which neither
		 *	a key nor a knob has: joined by a hyphen, the light half of a colour "x" and a knob that is called "x-light" would be one id
		 *
		 *	@param		{string}		key				The key of a feature
		 *	@param		{string}		[knob]
		 *	@param		{string}		[part]		light, dark or contrast, for what stands with a colour
		 *
		 *	@return		{string}
		 */
		_featureId : function( key, knob, part ) {
			return 'design-feature-'+ key+ ( knob === undefined ? '' : '_'+ knob+ ( part === undefined ? '' : '_'+ part ) );
		},

		/**
		 *	The choice of the variant of a feature, in the block of the feature on Templates: the select of its variants with the description of the
		 *	chosen one under it - the label is the one word the selects of the parts have - and, for a feature with one variant only, the
		 *	description alone: there is nothing to choose
		 *
		 *	@param		{string}	key				The key of a feature
		 *
		 *	@return		{Element}
		 */
		_featureField : function( key ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const catalogue = ( design._featuresOf()[key] || {} ).catalogue || {};
			const variants = Object.keys( catalogue );
			const describe = function( set ) { return ( catalogue[set] || {} ).description || '' };

			if( variants.length < 2 ) {

				const only = dc.createElement('div');
				only.className = 'design-field';

				const small = dc.createElement('small');
				small.textContent = describe( variants[0] );
				only.appendChild( small );

				return only;
			}

			const field = views._select(
				views._featureId( key ), Nino.content.getText('/_admin/design/label/template'),
				variants.map( function( set ) { return { value : set, label : catalogue[set].name || set } } ),
				views._chosenSet( key ), function( value ) {
					views._setFeatureSet( key, value, field );
				}, describe( views._chosenSet( key ) ) );

			return field;
		},

		/**
		 *	One knob of a feature, in the block of the feature on the tab its head puts it on: the strip of a scale or a choice, the select of
		 *	the eleven surfaces of a ground or of the seven tones of an ink, the two colours of a pair. Greyed out where the variant chosen does
		 *	not declare it, in the words of a knob of a part that its template does not answer to
		 *
		 *	@param		{string}	key				The key of a feature
		 *	@param		{string}	knob
		 *
		 *	@return		{Element}
		 */
		_featureKnobRow : function( key, knob ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const found = views._featureKnob( key, knob );
			const meta = found.meta;
			const value = views._featureValue( key, knob, meta );
			const note = found.answered === true ? '' : design._text( '/_admin/design/hint/unanswered', meta.label );
			let field;

			if( meta.kind === 'colour' )
				field = views._featureColours( key, knob, meta, value, found.answered );
			else if( meta.kind === 'ground' || meta.kind === 'ink' ) {

				field = views._select( views._featureId( key, knob ), meta.label,
					( ( design._data || {} )[ meta.kind === 'ground' ? 'grounds' : 'inks' ] || [] ).map( function( word ) {
						return { value : word, label : Nino.content.getText('/_admin/design/'+ meta.kind+ '/'+ word ) };
					} ), value, function( chosen ) { views._setFeatureKnob( key, knob, chosen ) }, note );

				field.querySelector('select').disabled = found.answered === false;
				field.classList.add('design-feature-select');
			}
			else {

				const strip = views._featureStrip( meta );

				field = design._stripRow( 'features/'+ key+ '/'+ knob, strip.name, note, strip.options, value, function( position ) {
					views._setFeatureKnob( key, knob, position );
				}, found.answered === false );
			}

			field.dataset.knob = knob;

			return field;
		},

		/**
		 *	A colour a feature brings: the name of the knob over two colours, the light one and the dark one, each a swatch under the word of its
		 *	half, and under them the line that says what the two measure on the page where one of them does not reach the ratio a mark on a page
		 *	needs (see _paintContrast()) - the row of the brand colour and the line under it, said of a colour of a feature. Nothing refuses a colour
		 *
		 *	@param		{string}	key
		 *	@param		{string}	knob
		 *	@param		{Object}	meta			The knob, see _featureKnob()
		 *	@param		{Array}		value			[ light, dark ]
		 *	@param		{boolean}	answered	Whether the variant chosen declares the knob
		 *
		 *	@return		{Element}
		 */
		_featureColours : function( key, knob, meta, value, answered ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;

			const wrap = dc.createElement('div');
			wrap.className = 'design-colour-feature';

			const field = dc.createElement('div');
			field.className = 'design-colour-row design-colour-pair';

			const label = dc.createElement('span');
			label.className = 'design-colour-label design-knob-label';
			design._labelContent( label, 'features/'+ key+ '/'+ knob, meta.label, answered === true ? '' : design._text( '/_admin/design/hint/unanswered', meta.label ) );
			field.appendChild( label );

			[ 'light', 'dark' ].forEach( function( half, at ) {

				const box = dc.createElement('label');
				box.className = 'design-colour-half';

				const caption = dc.createElement('small');
				caption.textContent = Nino.content.getText('/_admin/design/label/'+ half );
				box.appendChild( caption );

				const input = dc.createElement('input');
				input.type = 'color';
				input.id = views._featureId( key, knob, half );
				input.setAttribute( 'aria-label', caption.textContent+ ' - '+ meta.label );
				input.className = 'design-colour-input';
				input.value = String( value[at] );
				input.disabled = answered === false;
				input.addEventListener( 'change', function() {

					const pair = views._featureValue( key, knob, meta ).slice();

					pair[at] = this.value;
					views._setFeatureKnob( key, knob, pair );
				} );
				box.appendChild( input );

				field.appendChild( box );
			} );

			wrap.appendChild( field );

			const line = dc.createElement('p');
			line.id = views._featureId( key, knob, 'contrast' );
			line.className = 'nino-admin-hint design-colour-warning';
			views._paintContrast( line, key, knob );
			wrap.appendChild( line );

			return wrap;
		},

		/**
		 *	What the two halves of a colour of a feature measure on the page, said under the colour where one of them is below the ratio a mark
		 *	needs - the number is the server's (design/list and every preview carry it), the words the panel's. Written in place: the column holds
		 *	colour pickers somebody may have open
		 *
		 *	@param		{?Element}	line			The line itself
		 *	@param		{string}		key
		 *	@param		{string}		knob
		 *
		 *	@return		void
		 */
		_paintContrast : function( line, key, knob ) {

			if( line === null || line === undefined )
				return;

			const measured = ( ( ( Nino.admin.design._data || {} ).contrasts || {} )[key] || {} )[knob] || null;
			const unsafe = measured !== null && ( measured.light.safe === false || measured.dark.safe === false );

			line.hidden = unsafe === false;

			if( unsafe === true )
				line.textContent = Nino.admin.design._text( '/_admin/design/label/contrast', measured.light.ratio, measured.dark.ratio, measured.light.target );
		},

		/**
		 *	Paint the line of every colour of a feature that is on the screen, from what the last answer measured. Runs after every preview, beside
		 *	the brand's (see _absorb())
		 *
		 *	@return		void
		 */
		_paintContrasts : function() {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const features = design._featuresOf();

			Object.keys( features ).forEach( function( key ) {

				const painted = {};

				Object.keys( features[key].catalogue || {} ).forEach( function( set ) {

					const knobs = features[key].catalogue[set].knobs || {};

					Object.keys( knobs ).forEach( function( knob ) {

						if( knobs[knob].kind !== 'colour' || painted[knob] === true )
							return;

						painted[knob] = true;
						views._paintContrast( dc.getElementById( views._featureId( key, knob, 'contrast' ) ), key, knob );
					} );
				} );
			} );
		},

		/**
		 *	Choose a variant for a feature. The knobs the last one declared and this one does not are not this one's: they go, as the position of
		 *	a knob a template does not answer to goes back to the middle - the screen never holds a choice the stored one could not be. A choice
		 *	that comes to the feature as it is leaves no entry (see _pruneFeature()). The styles chosen for the feature stay: they are the
		 *	feature's and hold for every variant. The description under the select is written in place.
		 *	The frame goes to the block of the feature, else to where the tab is (see _anchorOf())
		 *
		 *	@param		{string}		key
		 *	@param		{string}		set				A variant of the feature
		 *	@param		{Element}		[field]		The select field of the feature, whose note is the description of the variant
		 *
		 *	@return		void
		 */
		_setFeatureSet : function( key, set, field ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const catalogue = ( design._featuresOf()[key] || {} ).catalogue || {};
			const declared = ( catalogue[set] || {} ).knobs || {};
			const entry = design._edit.features[key] || { set : set, knobs : {}, styles : {} };
			const note = field ? field.querySelector('small') : null;

			entry.set = set;

			Object.keys( entry.knobs ).forEach( function( knob ) {
				if( declared[knob] === undefined )
					delete entry.knobs[knob];
			} );

			design._edit.features[key] = entry;
			views._pruneFeature( key );

			if( note )
				note.textContent = ( catalogue[set] || {} ).description || '';

			design._target = views._anchorOf( 'features/'+ key+ '/set' );
			design._preview();
		},

		/**
		 *	Move a knob of a feature: a position, a surface, a tone or a pair of colours. The feature is chosen at the variant it stands at, if
		 *	nothing was chosen for it yet; a choice that comes to the feature as it is leaves no entry (see _pruneFeature()). The frame
		 *	goes to the block of the feature, else to where the tab is (see _anchorOf())
		 *
		 *	@param		{string}	key
		 *	@param		{string}	knob
		 *	@param		{*}				value
		 *
		 *	@return		void
		 */
		_setFeatureKnob : function( key, knob, value ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const entry = design._edit.features[key] || { set : views._chosenSet( key ), knobs : {}, styles : {} };

			entry.knobs[knob] = value;
			design._edit.features[key] = entry;
			views._pruneFeature( key );

			design._target = views._anchorOf( 'features/'+ key+ '/knobs/'+ knob );
			design._preview();
		},

		/**
		 *	Take the entry of a feature away where it says what the feature is without it: the variant it has before anything is chosen, no
		 *	style chosen and every knob at the default its head gives it. The server holds no entry for such a feature either, so the screen that was put back
		 *	is the stored one
		 *
		 *	@param		{string}	key
		 *
		 *	@return		void
		 */
		_pruneFeature : function( key ) {

			const design = Nino.admin.design;
			const feature = design._featuresOf()[key];
			const entry = design._edit.features[key];

			if( feature === undefined || entry === undefined || entry.set !== feature['default'] || Object.keys( entry.styles || {} ).length > 0 )
				return;

			const declared = ( ( feature.catalogue || {} )[entry.set] || {} ).knobs || {};

			if( Object.keys( entry.knobs ).every( function( knob ) {
				return declared[knob] === undefined || JSON.stringify( entry.knobs[knob] ) === JSON.stringify( declared[knob]['default'] );
			} ) === true )
				delete design._edit.features[key];
		},

		/**
		 *	Choose one thing of the motion of the page: a part of its animation or of the hover of its pictures
		 *
		 *	@param		{string}	key				effect, strength, speed, hover or hover-strength
		 *	@param		{string}	value			'' is what Nino.css does by itself
		 *
		 *	@return		void
		 */
		_setMotion : function( key, value ) {

			const design = Nino.admin.design;
			const views = Nino.admin.design.views;
			const selects = {};

			design._edit.motion[key] = value;
			design._target = views._anchorOf( 'motion/'+ key );

			// A change of the animation plays it again; the hover of the pictures is seen under the pointer, and nothing is played
			if( key.indexOf('hover') !== 0 )
				design._replays = true;

			Object.keys( views.MOTION_FOLLOWS ).forEach( function( part ) { selects[part] = dc.getElementById( 'design-motion-'+ part ) } );
			views._motionOff( selects );
			design._preview();
		},

		/**
		 *	The table of the five roles of Nino.css, without its rows: the head of three columns - the family, the
		 *	weight and the size - and the body the rows of the roles are put into one by one, as the table
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

			[ 'role', 'family', 'weight', 'size' ].forEach( function( key ) {
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
		 *	What a cell of a role is called: the role and the column, "Title - Weight" - the name of its control
		 *
		 *	@param		{string}	role			A key of data.roles
		 *	@param		{string}	column		family, weight or size
		 *
		 *	@return		{string}
		 */
		_fontLabel : function( role, column ) {
			return Nino.content.getText('/_admin/design/font/role/'+ role )+ ' - '+ Nino.content.getText('/_admin/design/font/head/'+ column );
		},

		/**
		 *	One row of the table: a role, and the three things a role is given
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
				control.setAttribute( 'aria-label', Nino.admin.design.views._fontLabel( role, column ) );
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
				control.addEventListener( 'change', function() { Nino.admin.design.views._setFont( role, key, control.value ) } );
				return cell( control, key );
			};

			select( 'family', ( ( data.options || {} )[role] || [ '' ] ).map( function( slug ) {
				return { value : slug, label : Nino.admin.design.views._familyLabel( role, slug ) };
			} ), chosen.family || '' );

			select( 'weight', ( data.weights || [] ).map( function( weight ) {
				return { value : weight, label : Nino.content.getText('/_admin/design/weight/'+ weight ) };
			} ), chosen.weight || 'normal' );

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
			size.value = Nino.admin.design.views._sizeInForce( role );
			size.addEventListener( 'change', function() {
				Nino.admin.design.views._setFont( role, 'size', Nino.admin.design.views._keepSize( role, size.value ) );
				size.value = Nino.admin.design.views._sizeInForce( role );
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

			const kept = Nino.admin.design.views._clampSize( role, typed );
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
		 *	@param		{string}		key				family, weight or size
		 *	@param		{*}					value
		 *
		 *	@return		void
		 */
		_setFont : function( role, key, value ) {

			Nino.admin.design._edit.fonts[role][key] = value;
			Nino.admin.design._target = Nino.admin.design.views._anchorOf( 'fonts/'+ role );
			Nino.admin.design._preview();
		},

		/**
		 *	Where a knob stands in a set of choices (the working copy, or the defaults design/list hands over): a position, or for an
		 *	ink a tone - '' where none is chosen, the tone the template gives. A knob the set of choices has no entry for stands at the middle
		 *
		 *	@param		{Object}		knobs			part => knob => value
		 *	@param		{string}		part
		 *	@param		{string}		knob
		 *
		 *	@return		{number|string}
		 */
		_knobValue : function( knobs, part, knob ) {

			const value = ( knobs[part] || {} )[knob];
			const kind = ( ( Nino.admin.design._data.kinds || {} )[part] || {} )[knob];

			return value === undefined || value === null ? ( kind === 'ink' ? '' : 0 ) : value;
		},

		/**
		 *	The options of the select of an ink: none first - the tone the template itself gives, which is the middle of an ink - and then
		 *	the tones of the page, by the words the feature inks have (design/list hands the tones over as `inks`)
		 *
		 *	@return		{Array}						{ value, label, title }
		 */
		_inkOptions : function() {

			const said = function( value, key ) {
				const label = Nino.content.getText( key );
				return { value : value, label : label, title : label };
			};

			return [ said( '', '/_admin/design/label/own' ) ].concat( ( Nino.admin.design._data.inks || [] ).map( function( word ) {
				return said( word, '/_admin/design/ink/'+ word );
			} ) );
		},

		/**
		 *	What the strip of a knob is made of: its label and its options, from the least to the most - the numbers
		 *	-2 to +2 of a scale, the three words that knob has for a choice, the words off and on for a toggle, which the
		 *	panel draws as a switch and says in the head of a closed block by these two, and for an ink the options of its select
		 *	(see _inkOptions()). The kind is what design/list says of the knob in its part
		 *
		 *	@param		{string}		part			The part the knob belongs to: one of the eight parts (see Setup::PARTS)
		 *	@param		{string}		knob
		 *
		 *	@return		{Object}							{ name, options }, the options as { value, label, title }
		 */
		_knobStrip : function( part, knob ) {

			const base = '/_admin/design/knob/'+ part+ '/'+ knob;
			const kind = ( ( Nino.admin.design._data.kinds || {} )[part] || {} )[knob];
			const positions = Nino.admin.design.views._positions( kind );

			// An ink is no strip: its options are the tones of the page and, first, none - the tone the template gives
			if( kind === 'ink' )
				return { name : Nino.content.getText( base+ '/label' ), options : Nino.admin.design.views._inkOptions() };

			// An option that says a position in the words of a text key
			const said = function( key, position ) {
				const label = Nino.content.getText( key );
				return { value : position, label : label, title : label };
			};

			return {
				name 		: Nino.content.getText( base+ '/label' ),
				options	: kind === 'toggle'
					? positions.map( function( position ) { return said( '/_admin/common/label/'+ ( position === 1 ? 'on' : 'off' ), position ) } )
					: ( kind === 'choice'
						? positions.map( function( position ) { return said( base+ '/'+ Nino.admin.design.views._word( position ), position ) } )
						: Nino.admin.design.views._numbered( positions, 0 ) ),
			};
		},

		/**
		 *	One knob, as a strip. A scale is five numbers, -2 to +2, and the note under
		 *	its name says which way they go; a choice is three alternatives, each in the
		 *	words that knob has for it - "tight, normal, airy" is not the same sentence
		 *	as "sharp, normal, round". A toggle is no strip but the workbench's switch,
		 *	with the name of the knob for its label and the note for its hint: on at the
		 *	position 1, a change moves the knob to 1 or to 0 (see _setStep()), and the
		 *	whole row, the switch with it, is greyed out and switched off where the
		 *	template does not answer to it, with the line that says so in the place
		 *	of the note, as a strip is. The kind is what design/list says of the knob
		 *	in its part
		 *
		 *	@param		{string}		part			The part the knob belongs to: one of the eight parts (see Setup::PARTS)
		 *	@param		{string}		knob
		 *	@param		{boolean}		answered		Whether the chosen template declares it
		 *
		 *	@return		{Element}
		 */
		_knobRow : function( part, knob, answered ) {

			const base = '/_admin/design/knob/'+ part+ '/'+ knob;
			const strip = Nino.admin.design.views._knobStrip( part, knob );
			const name = strip.name;

			if( Nino.admin.design._data.kinds[part][knob] === 'toggle' )
				return Nino.admin.design.views._knobSwitch( part, knob, answered, name,
					answered === true ? Nino.content.getText( base+ '/note' ) : Nino.admin.design._text( '/_admin/design/hint/unanswered', name ) );

			// An ink is a select of the tones of the page, as the ink of a feature is, with none first: the tone the template gives
			if( Nino.admin.design._data.kinds[part][knob] === 'ink' )
				return Nino.admin.design.views._knobSelect( part, knob, answered, strip,
					answered === true ? Nino.content.getText( base+ '/note' ) : Nino.admin.design._text( '/_admin/design/hint/unanswered', name ) );

			/*	The knob's own name and the terse note under it, both out of the
				panel's text files: a knob is one key in every locale. Where the
				template does not answer to it the note is the reason it is grey.
				An icon stands before them, for what the knob sets	*/
			const field = Nino.admin.design._stripRow( part+ '/'+ knob, name,
				answered === true ? Nino.content.getText( base+ '/note' ) : Nino.admin.design._text( '/_admin/design/hint/unanswered', name ),
				strip.options, ( Nino.admin.design._edit.knobs[part] || {} )[knob] || 0, function( position ) {
					Nino.admin.design.views._setStep( part, knob, position );
				}, answered === false );

			field.dataset.knob = knob;

			return field;
		},

		/**
		 *	One ink, as a row of its own: the name of the knob with its icon and its note, as a strip has them, and under it a select of the tones
		 *	of the page with none first - the tone the template gives. A change moves the knob to the tone, or to '' for none (see _setStep());
		 *	where the template does not answer to the knob the select is switched off and the row greyed out, with the line that says so in the
		 *	place of the note, as a strip is
		 *
		 *	@param		{string}		part			The part the knob belongs to
		 *	@param		{string}		knob			An ink of that part
		 *	@param		{boolean}		answered	Whether the chosen template declares it
		 *	@param		{Object}		strip			{ name, options }, see _knobStrip()
		 *	@param		{string}		note			The line under the name
		 *
		 *	@return		{Element}
		 */
		_knobSelect : function( part, knob, answered, strip, note ) {

			const design = Nino.admin.design;
			const views = design.views;
			const id = 'design-knob-'+ part+ '-'+ knob;
			const stands = views._knobValue( design._edit.knobs, part, knob );

			const row = dc.createElement('div');
			row.className = 'design-knob-row design-knob-row--select'+ ( answered === false ? ' design-knob-row--off' : '' );
			row.dataset.knob = knob;

			const label = dc.createElement('label');
			label.className = 'design-knob-label';
			label.setAttribute( 'for', id );
			design._labelContent( label, part+ '/'+ knob, strip.name, note );
			row.appendChild( label );

			const select = dc.createElement('select');
			select.id = id;
			select.className = 'design-knob-steps';
			select.disabled = answered === false;

			strip.options.forEach( function( option ) {
				const node = dc.createElement('option');
				node.value = option.value;
				node.textContent = option.label;
				node.selected = option.value === stands;
				select.appendChild( node );
			} );

			select.addEventListener( 'change', function() { views._setStep( part, knob, select.value ) } );
			row.appendChild( select );

			return row;
		},

		/**
		 *	One toggle, as a row of its own: the workbench's switch (Nino.adminUi.switchField()) in the box a strip's row is,
		 *	so that a row is a row whatever it is drawn as. The switch is on at the position 1 and moves the knob to 1 and to
		 *	0; where the template does not answer to the knob it is off and switched off, whatever the model holds, and the
		 *	row is greyed out the way a strip's is
		 *
		 *	@param		{string}		part			The part the knob belongs to
		 *	@param		{string}		knob			A toggle of that part
		 *	@param		{boolean}		answered	Whether the chosen template declares it
		 *	@param		{string}		name			The label of the switch
		 *	@param		{string}		note			The hint under it
		 *
		 *	@return		{Element}
		 */
		_knobSwitch : function( part, knob, answered, name, note ) {

			const views = Nino.admin.design.views;

			const field = Nino.adminUi.switchField( {
				key 		: 'design-knob-'+ part+ '-'+ knob,
				label 	: name,
				hint 		: note,
				checked	: answered === true && ( ( Nino.admin.design._edit.knobs[part] || {} )[knob] || 0 ) === 1,
			} );

			const input = field.querySelector('input');
			input.id = 'design-knob-'+ part+ '-'+ knob;
			input.disabled = answered === false;
			input.addEventListener( 'change', function() { views._setStep( part, knob, input.checked === true ? 1 : 0 ) } );

			const row = dc.createElement('div');
			row.className = 'design-knob-row design-knob-row--switch'+ ( answered === false ? ' design-knob-row--off' : '' );
			row.dataset.knob = knob;
			row.appendChild( field );

			return row;
		},

		/**
		 *	Move a knob
		 *
		 *	@param		{string}		part			The part the knob belongs to
		 *	@param		{string}		knob			A knob of that part
		 *	@param		{number|string}	position	-2 to +2 for a scale, -1 to +1 for a choice, 0 or 1 for a toggle, a tone of the page or '' for an ink
		 *
		 *	@return		void
		 */
		_setStep : function( part, knob, position ) {

			Nino.admin.design._edit.knobs[part][knob] = position;
			// A change is looked at where it shows: one target for it, which the jump
			// that follows the preview goes to
			Nino.admin.design._target = Nino.admin.design.views._anchorOf( 'knobs/'+ part+ '/'+ knob );
			Nino.admin.design._preview();
		},

		/**
		 *	The element a change of one control is looked at in: the id CONTROL_ANCHORS names for its key, else the anchor of
		 *	the object it stands under (see _aimOf()), which is the one OBJECT_ANCHORS names for it. Where the control stands is
		 *	the table's (see _place()); how many aspects the object has is no part of it.
		 *
		 *	A control of a feature - its key starts with features/, and the object it stands under is the feature - has no
		 *	entry in CONTROL_ANCHORS, the choice of its variant included. It is looked at in the block the feature brings to the
		 *	specimen (design/preview.tpl, see Setup::previewOf()) - its anchor, which the answer of design/list gives with
		 *	the feature -, and where the feature has none at the top of the page. Whether the page in the frame has the id
		 *	is _jump()'s to find out
		 *
		 *	@param		{string}		key				The key the control has in the table: 'size', 'knobs/section/quiet' ...
		 *
		 *	@return		{string}							The id of an element of the specimen
		 */
		_anchorOf : function( key ) {

			const design = Nino.admin.design;

			if( key.indexOf('features/') === 0 )
				return design.views._aimOf( design._place( key )[1] );

			return design.CONTROL_ANCHORS[key] || design.views._aimOf( design._place( key )[1] );
		},

		/**
		 *	Where an object is looked at: the id OBJECT_ANCHORS names for it, for a feature the block it brings to the specimen (the
		 *	`anchor` of design/list), and the top of the page for an object that has neither
		 *
		 *	@param		{string}		object		A key of the table's objects
		 *
		 *	@return		{string}							The id of an element of the specimen
		 */
		_aimOf : function( object ) {

			const design = Nino.admin.design;
			const named = ( design._featuresOf()[object] || {} ).anchor;

			if( typeof named === 'string' && named !== '' )
				return named;

			return design.OBJECT_ANCHORS[object] || design.OBJECT_ANCHORS.page;
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
			Nino.admin.design.views._paintBrandWarning( warn );

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
			input.addEventListener( 'change', function() { Nino.admin.design.views._setColour( 'primary', this.value ) } );
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
			input.addEventListener( 'change', function() { Nino.admin.design.views._setColour( 'secondary', this.value ) } );
			box.appendChild( input );

			if( derived === false ) {
				const reset = dc.createElement('button');
				reset.type = 'button';
				reset.className = 'design-knob-reset';
				reset.textContent = '\u21ba';
				reset.title = Nino.content.getText('/_admin/design/hint/derived');
				reset.setAttribute( 'aria-label', Nino.content.getText('/_admin/design/hint/derived') );
				reset.addEventListener( 'click', function() { Nino.admin.design.views._setColour( 'secondary', '' ) } );
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

				options = Nino.admin.design.views._numbered( positions, meta['default'] );
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
			const strip = Nino.admin.design.views._colourStrip( key, meta );

			/*	A hex somebody typed overrides the whole knob - Colours::palette()
				takes the Secondary as given and never reaches for Harmony at all.
				So no position is lit while one is set: an active button there
				would claim a derivation that is not happening	*/
			const active = key === 'harmony' && String( colours.secondary || '' ) !== ''
				? null
				: ( colours[key] || meta['default'] );

			const field = Nino.admin.design._stripRow( 'colours/'+ key, strip.name, Nino.content.getText( '/_admin/design/colour/'+ key+ '/note' ),
				strip.options, active, function( position ) { Nino.admin.design.views._setColourKnob( key, position ) } );

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

			const field = Nino.admin.design.views._select(
				'design-colour-mode', '', Nino.admin.design.views._modeOptions( meta ), String( ( edit.colours || {} ).mode || meta['default'] ),
				function( value ) {
					Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
					Nino.admin.design._edit.colours.mode = value;
					Nino.admin.design._target = Nino.admin.design.views._anchorOf( 'colours/mode' );
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
			Nino.admin.design._target = Nino.admin.design.views._anchorOf( 'colours/'+ key );
			Nino.admin.design.views._redrawColours();
		},

		_setColourKnob : function( key, position ) {

			Nino.admin.design._edit.colours = Nino.admin.design._edit.colours || {};
			Nino.admin.design._edit.colours[key] = parseInt( position, 10 );
			Nino.admin.design._target = Nino.admin.design.views._anchorOf( 'colours/'+ key );

			/*	Choosing where the second colour sits is choosing to let Harmony
				put it there. A hex left over from before would override the very
				knob that was just moved, and the knob would look moved and do
				nothing - which is the worst of the three possible states	*/
			if( key === 'harmony' ) {
				Nino.admin.design._edit.colours.secondary = '';
				return Nino.admin.design.views._redrawColours();
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
			const views = Nino.admin.design.views;
			const place = design._place('colours/secondary');
			const rows = views._rows( place[1] );
			const row = rows.filter( function( each ) { return each.tab === place[0] } )[0];
			const old = dc.getElementById( views._rowId( place[0], place[1] ) );

			if( old !== null && row !== undefined )
				old.replaceWith( views._renderRow( row, views._blockOf( place[1], rows ) === row.tab ) );

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

	};

	/*	The loader of the typefaces: the tab Schriftarten of the pane, with no preview and no bar - two cards, the
		families the project has installed and the list of those it can install. It keeps nothing in the setup: an
		install or a removal happens when it is pressed and is no part of the draft, so what it says of it stands over
		the cards and not in the bar of a screen that has one. What it reads of the panel is the data of design/list - the
		families the roles list, the settings of the repository - and the working copy, to tell which roles use a family (see
		Nino.admin.design)	*/
	Nino.admin['design-typefaces'] = {

		// The categories of the typefaces the Available half is told apart by, as the chips
		// of its strip, and how many families a page of it holds
		FACE_CATEGORIES	: [ 'all', 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ],
		FACES_PER_PAGE	: 24,

		/*	The loader, the tab Schriftarten. The last answer of design/webfonts/* - families (the list),
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
		 *	The shell shows the tab: the loader is drawn afresh whenever it is opened, from what is known then - the
		 *	list the server kept, which is asked for again where no action is on its way. Where the list of the panel is
		 *	not there yet - the tab is the first screen of the pane that was asked for - it is waited for, and a
		 *	failure of it is said in the mount of this tab
		 *
		 *	The chip "changed" stands on the button of the panel's own screen, which every tab of the pane has in the
		 *	strip on top of it, so it is drawn here too (see Nino.admin.design._refreshThemeChip())
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			const design = Nino.admin.design;
			const loader = Nino.admin['design-typefaces'];
			const wrap = loader._mount();

			if( wrap === null )
				return;

			const draw = function() {
				loader._render();
				design._refreshThemeChip();
			};

			if( design._ready === false )
				return design.init( draw, wrap );

			draw();
		},

		/**
		 *	The mount of the tab: the one its pane holds (see Webfonts\Tab::panes())
		 *
		 *	@return		{?Element}
		 */
		_mount : function() {
			return dc.getElementById('design-typefaces-form');
		},

		/**
		 *	The tab is left: another screen of the pane is shown (see Nino.admin.design._show()). The faces the names were
		 *	set in are let go of and the mount is emptied, so that the answer of a request that outlived the tab is kept and
		 *	draws nothing - there are no cards for it to draw into - and the tab is drawn again from what is known when it
		 *	is opened. A tab that is not drawn has nothing to let go of
		 *
		 *	@return		void
		 */
		_leave : function() {

			const loader = Nino.admin['design-typefaces'];
			const wrap = loader._mount();

			loader._dropFaces();

			if( wrap !== null )
				wrap.innerHTML = '';
		},

		/**
		 *	The loader, drawn into its mount: what it said last, which an action has to say, and the two cards under it
		 *
		 *	@return		void
		 */
		_render : function() {

			const loader = Nino.admin['design-typefaces'];
			const wrap = loader._mount();

			if( wrap === null )
				return;

			wrap.innerHTML = '';
			loader._renderTypefaces( wrap );

			// The box of the list is in the document now: it takes the room the window has under it
			loader._fitFaces();
		},

		/**
		 *	The two cards of the loader: the first holds the families the
		 *	project has installed, each with its name set in its own face, its weights, its
		 *	version and licence, and the button that removes it; the second the list of those
		 *	the repository offers, read when its button is pressed, with a search field, the
		 *	categories as a strip and pages of FACES_PER_PAGE. Over them the line the loader says what its
		 *	actions did in, which is empty until one has.
		 *
		 *	Both halves are drawn from the answer the server gives after each action of the
		 *	tab, kept in _faces. The cached list is asked for whenever the tab is opened -
		 *	design/webfonts/list answers from data/ without a request of its own, and not
		 *	while an action is on its way, whose answer is the state - and the repository
		 *	only by the button. An install and a removal are made when their button is
		 *	pressed, and are no part of the draft: the line over the cards says what they
		 *	did, and there is no bar, so no Reset to bring anything back.
		 *
		 *	@param		{Element}	body		The box the tab keeps for it
		 *
		 *	@return		void
		 */
		_renderTypefaces : function( body ) {

			const loader = Nino.admin['design-typefaces'];

			const line = dc.createElement('p');
			line.id = 'design-faces-msg';
			line.setAttribute( 'aria-live', 'polite' );
			body.appendChild( line );

			const cards = dc.createElement('div');
			cards.className = 'design-faces-cards';
			body.appendChild( cards );

			const installed = dc.createElement('div');
			installed.id = 'design-faces-installed';
			installed.className = 'nino-admin-card design-block';

			const available = dc.createElement('div');
			available.id = 'design-faces-available';
			available.className = 'nino-admin-card design-block';

			cards.appendChild( installed );
			cards.appendChild( available );

			loader._paintInstalled( installed );
			loader._paintAvailable( available );

			// An action that is on its way answers with the state it leaves, and that answer draws the screen: a list read now could be older than it
			if( loader._facesBusy === false )
				loader._loadFaces( false );
		},

		/**
		 *	Say something in the line over the cards, the way the bar's line says it (see Nino.admin.design._say())
		 *
		 *	@param		{string}		text			'' empties the line
		 *	@param		{string}		[kind]		nino-admin-error for a failure, none for the answer itself
		 *	@param		{string}		[detail]	What stands behind the sentence, the server's words
		 *
		 *	@return		void
		 */
		_tell : function( text, kind, detail ) {
			Nino.admin.design._say( dc.getElementById('design-faces-msg'), text, kind, detail );
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
				Nino.admin['design-typefaces']._paintInstalled( installed );

			if( available !== null )
				Nino.admin['design-typefaces']._paintAvailable( available );
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
			const loader = Nino.admin['design-typefaces'];
			const stamp = ++loader._facesStamp;

			if( refresh === true ) {
				loader._facesBusy = true;
				loader._tell('');
				loader._paintFaces();
			}

			design._apiCall( 'webfonts/list', { refresh : refresh }, function( status, response ) {

				if( refresh === true )
					loader._facesBusy = false;

				if( status !== 200 || response === null ) {
					loader._paintFaces();
					return loader._tell( Nino.adminUi.api.errorText( status, response, '/_admin/design/typefaces/error/list' ), 'nino-admin-error' );
				}

				// Only the latest answer is the state: one asked for before an install is the state before it
				if( stamp === loader._facesStamp ) {
					loader._faces = response;
					loader._facesPage = 0;

					// A list loaded again starts at its first page, and that page at the top of its box: what the
					// box before was scrolled to was another list's (_paintAvailable() carries the scroll over)
					const box = dc.getElementById('design-faces-results');

					if( box !== null )
						box.scrollTop = 0;
				}

				loader._paintFaces();

				if( refresh === true )
					loader._tell( response.refusal !== ''
						? loader._refusal( response )
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
			Nino.admin['design-typefaces']._faceAction( 'webfonts/install', slug, '/_admin/design/typefaces/error/install' );
		},

		/**
		 *	Remove an installed family
		 *
		 *	@param		{string}	slug
		 *
		 *	@return		void
		 */
		_removeFace : function( slug ) {
			Nino.admin['design-typefaces']._faceAction( 'webfonts/remove', slug, '/_admin/design/typefaces/error/remove' );
		},

		/**
		 *	One action of the tab on one family. The buttons are switched off while it is on
		 *	its way, and the server's answer - the same as the list gives, after the change -
		 *	draws both halves again; the line over the cards says what happened, or why not.
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
			const loader = Nino.admin['design-typefaces'];

			if( loader._facesBusy === true )
				return;

			const stamp = ++loader._facesStamp;

			loader._facesBusy = true;
			loader._tell('');
			loader._paintFaces();

			design._apiCall( endpoint, { slug : slug }, function( status, response ) {

				loader._facesBusy = false;

				if( status !== 200 || response === null ) {
					loader._paintFaces();

					// A family a role still uses is told with the roles, in the words of the workbench (the
					// server names them as the params of the code); what else the server refused for, in its
					// own words; a failure that has none is told the way the workbench tells one
					const roles = response !== null && response.code === 'used' && Array.isArray( response.params )
						? response.params.map( function( role ) { return Nino.content.getText( '/_admin/design/font/role/'+ role ) } ).join( ', ' ) : null;
					const reason = response !== null && typeof response.error === 'string' && response.error !== ''
						? response.error : Nino.adminUi.api.errorText( status, response, '' );

					return loader._tell( roles !== null ? design._text( '/_admin/design/typefaces/error/used', roles ) : design._text( failure, reason ), 'nino-admin-error' );
				}

				if( stamp === loader._facesStamp )
					loader._faces = response;

				loader._paintFaces();
				loader._tell( response.msg );
				design._relist();
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

			const saved = ( ( Nino.admin['design-typefaces']._faces || {} ).uses || {} )[slug] || [];
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
			const loader = Nino.admin['design-typefaces'];
			const faces = loader._faces;

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/typefaces/installed/title') ) );

			if( faces === null )
				return;

			const slugs = Object.keys( faces.installed || {} );
			const broken = Object.keys( faces.broken || {} );

			if( slugs.length === 0 && broken.length === 0 )
				return card.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/installed/empty') ) );

			slugs.forEach( function( slug ) {
				card.appendChild( loader._installedRow( slug, faces.installed[slug] ) );
			} );

			broken.forEach( function( slug ) {
				card.appendChild( loader._brokenRow( slug, faces.broken[slug] ) );
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
			const loader = Nino.admin['design-typefaces'];
			const roles = loader._rolesOf( slug );
			const used = design._text( '/_admin/design/typefaces/installed/used', roles.map( function( role ) {
				return Nino.content.getText('/_admin/design/font/role/'+ role );
			} ).join(', ') );

			const notes = [ loader._note( [ loader._weights( family.weights, family.axis ), family.version, family.license ].join(' · ') ) ];

			if( roles.length > 0 )
				notes.push( loader._note( used ) );

			const row = loader._faceRow( slug, family.name, notes );

			loader._setFace( 'installed', slug, family.face, row.name );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/design/typefaces/installed/remove');
			remove.title = roles.length > 0 ? used : remove.textContent;
			// A row of buttons that all say Remove is read out with the name of its family
			remove.setAttribute( 'aria-label', remove.textContent+ ' - '+ family.name );
			remove.disabled = roles.length > 0 || loader._facesBusy === true;
			remove.addEventListener( 'click', function() { loader._removeFace( slug ) } );
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
			const loader = Nino.admin['design-typefaces'];
			const row = loader._faceRow( slug, slug, [ loader._note( design._text( '/_admin/design/typefaces/installed/broken', reason ) ) ] );

			row.element.classList.add('design-face-row--off');

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/design/typefaces/installed/remove');
			remove.setAttribute( 'aria-label', remove.textContent+ ' - '+ slug );
			remove.disabled = loader._facesBusy === true;
			remove.addEventListener( 'click', function() { loader._removeFace( slug ) } );
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
			const loader = Nino.admin['design-typefaces'];
			const faces = loader._faces;

			/*	What design/list said when the panel was opened, until the screen has an answer of its own: the settings
				of the repository are changed in another panel of the same page, and the answer is read on every visit	*/
			const web = faces === null ? ( ( design._data || {} ).webfonts || {} ) : { url : faces.url !== '', key : faces.key === true };

			const before = dc.getElementById('design-faces-results');
			const scrolled = before === null ? 0 : before.scrollTop;

			card.innerHTML = '';
			card.appendChild( design._sectionLabel( Nino.content.getText('/_admin/design/typefaces/available/title') ) );

			if( web.url !== true )
				return card.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/available/off') ) );

			if( web.key !== true )
				return card.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/available/nokey') ) );

			const bar = dc.createElement('div');
			bar.className = 'design-faces-load';

			const load = dc.createElement('button');
			load.type = 'button';
			load.id = 'design-faces-load';
			load.className = 'nino-admin-btn-secondary';
			load.textContent = Nino.content.getText('/_admin/design/typefaces/available/load');
			load.disabled = loader._facesBusy === true;
			load.addEventListener( 'click', function() { loader._loadFaces( true ) } );
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

				const refusal = loader._hint( loader._refusal( faces ), true );

				// The reason is the server's, in English: what stands behind the sentence
				refusal.title = faces.refusal;

				return card.appendChild( refusal );
			}

			if( faces.fetched === 0 )
				return card.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/available/unloaded') ) );

			if( ( faces.families || [] ).length === 0 )
				return card.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/available/none') ) );

			const search = dc.createElement('input');
			search.type = 'search';
			search.id = 'design-faces-search';
			search.className = 'design-faces-search';
			search.placeholder = Nino.content.getText('/_admin/design/typefaces/available/search');
			search.setAttribute( 'aria-label', search.placeholder );
			search.value = loader._facesQuery;
			search.addEventListener( 'input', function() {
				loader._facesQuery = search.value;
				loader._facesPage = 0;
				loader._paintResults();
			} );
			card.appendChild( search );

			const categories = design._stripRow( 'typefaces/category', Nino.content.getText('/_admin/design/typefaces/available/category'), '',
				loader.FACE_CATEGORIES.map( function( key ) {
					return { value : key, label : Nino.content.getText('/_admin/design/typefaces/category/'+ key ) };
				} ), loader._facesCategory, function( key ) {
					loader._facesCategory = key;
					loader._facesPage = 0;
					loader._paintResults();
				} );

			// Seven names are one row of the strip in a third of the screen, every word whole (see .design-faces-categories)
			categories.classList.add('design-faces-categories');
			card.appendChild( categories );

			// The rows of a page scroll in this box, and the pager stands after it in the card (see _paintResults())
			const results = dc.createElement('div');
			results.id = 'design-faces-results';
			card.appendChild( results );

			loader._paintResults( results );
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

			const loader = Nino.admin['design-typefaces'];

			results = results || dc.getElementById('design-faces-results');

			if( results === null || results === undefined || loader._faces === null )
				return;

			results.innerHTML = '';

			// A page starts at the top of its box, whatever the page before was scrolled to
			results.scrollTop = 0;

			// The pager of the page before goes with its rows
			const before = results.parentNode.querySelector('.design-faces-pager');

			if( before !== null )
				before.remove();

			// The rows of the page before are gone: what watched them has nothing left to watch
			if( loader._facesObserver !== null )
				loader._facesObserver.disconnect();

			const query = loader._facesQuery.trim().toLowerCase();

			const found = ( loader._faces.families || [] ).filter( function( entry ) {
				return ( loader._facesCategory === 'all' || entry.category === loader._facesCategory )
					&& ( query === '' || entry.family.toLowerCase().indexOf( query ) !== -1 );
			} );

			if( found.length === 0 ) {
				results.appendChild( loader._hint( Nino.content.getText('/_admin/design/typefaces/available/nomatch') ) );

				// A sentence has no rows to fit the window to: the box lets go of the height it had
				return loader._fitFaces();
			}

			const pages = Math.ceil( found.length / loader.FACES_PER_PAGE );

			loader._facesPage = Math.max( 0, Math.min( pages - 1, loader._facesPage ) );

			found.slice( loader._facesPage * loader.FACES_PER_PAGE, ( loader._facesPage + 1 ) * loader.FACES_PER_PAGE ).forEach( function( entry ) {
				results.appendChild( loader._availableRow( entry ) );
			} );

			if( pages > 1 )
				results.insertAdjacentElement( 'afterend', loader._pager( pages ) );

			loader._fitFaces();
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
			const loader = Nino.admin['design-typefaces'];
			const installed = ( ( loader._faces || {} ).installed || {} )[entry.slug] || null;
			const brought = ( ( loader._faces || {} ).builtin || [] ).indexOf( entry.slug ) !== -1;

			const licence = dc.createElement('a');
			licence.href = entry.source;
			licence.target = '_blank';
			licence.rel = 'noopener noreferrer';
			licence.title = entry.source;
			licence.textContent = entry.license;

			const separator = dc.createElement('span');
			separator.textContent = ' · ';

			const weights = dc.createElement('span');
			weights.textContent = loader._weights( entry.weights );

			const note = dc.createElement('small');
			note.className = 'design-face-note';
			note.appendChild( weights );
			note.appendChild( separator );
			note.appendChild( licence );

			const row = loader._faceRow( entry.slug, entry.family, [ note ] );

			// Set in its own face when the row comes into view: one file for a family, the preview the repository publishes
			row.element.dataset.preview = entry.preview;
			loader._observe().observe( row.element );

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
			install.disabled = loader._facesBusy === true;
			install.addEventListener( 'click', function() { loader._installFace( entry.slug ) } );
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
			const loader = Nino.admin['design-typefaces'];

			const pager = dc.createElement('div');
			pager.className = 'design-faces-pager';

			const step = function( label, towards ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-btn-secondary';
				button.textContent = Nino.content.getText('/_admin/design/typefaces/page/'+ label );
				button.disabled = loader._facesPage + towards < 0 || loader._facesPage + towards > pages - 1;
				button.addEventListener( 'click', function() {
					loader._facesPage += towards;
					loader._paintResults();
				} );
				return button;
			};

			const place = dc.createElement('span');
			place.className = 'design-faces-place';
			place.textContent = design._text( '/_admin/design/typefaces/page/of', loader._facesPage + 1, pages );

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

			const loader = Nino.admin['design-typefaces'];

			if( loader._facesObserver === null )
				loader._facesObserver = new wn.IntersectionObserver( function( entries ) {

					entries.forEach( function( seen ) {

						if( seen.isIntersecting !== true )
							return;

						loader._facesObserver.unobserve( seen.target );

						loader._setFace( 'available', seen.target.dataset.slug, seen.target.dataset.preview, seen.target.querySelector('.design-face-name') );
					} );
				} );

			return loader._facesObserver;
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

			const loader = Nino.admin['design-typefaces'];
			const family = 'design-'+ kind+ '-'+ slug;

			if( loader._facesLoaded[family] === undefined ) {

				const face = new wn.FontFace( family, 'url('+ JSON.stringify( url )+ ')' );

				loader._facesLoaded[family] = face;
				dc.fonts.add( face );

				// A file that is not there, or not to be read from here, leaves the name in the face of the page
				face.load().catch( function() {} );
			}

			name.style.fontFamily = '"'+ family+ '", system-ui, sans-serif';
		},

		/**
		 *	Fit the list of the typefaces on offer to the window: the box the rows of a page stand in is as
		 *	tall as the window has room for under it, and the rows that are more than that scroll in the box -
		 *	not the page, which a page of 24 rows would otherwise be scrolled for. The room is the height of the
		 *	window less the top of the box with the page scrolled to the top, less the pager, which stands after the
		 *	box and so stays in reach, less the foot of the card - its padding and the space between two cards,
		 *	1.5 rem - and less the room every pane of the workbench keeps under its content for a bar (see
		 *	_bar(), whose declared height it is): the tab has no bar, but the pane's padding is the same, and a
		 *	list that ended at the foot of the window would be scrolled for the padding.
		 *
		 *	Never under five rows: five times the height of the first, and the four hairlines between them -
		 *	11 rem where the row has no height to measure. Where the box stands far down - the two cards stand one
		 *	over the other in a narrow window - and the room is less than five rows there, the box is made for the
		 *	window it fills when the page is scrolled to its card, with the top of the card at the top of the
		 *	window: the window less how far the box stands inside the card, so that the search and the categories
		 *	are in view with it, less the pager, the foot of the card and the bar's room.
		 *
		 *	A box that holds the sentence of no match has no rows to fit the window to: its height is cleared, and
		 *	the least the stylesheet gives it is what it has. A tab that is not open has no box to give room to.
		 *	Asked for wherever the box has new rows (_paintResults()), when the tab is drawn and when the window is
		 *	resized (see Nino.admin.design._fit())
		 *
		 *	@return		void
		 */
		_fitFaces : function() {

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
			const rem = parseFloat( wn.getComputedStyle( dE ).fontSize ) || 16;
			const row = first.getBoundingClientRect().height;
			const least = row > 0 ? 5 * row + 4 : 11 * rem;

			// What stands under the box up to the foot of the pager, and what the card keeps under that: its padding
			// and the space the cards have between them - and the room the pane keeps for a bar
			const foot = pager === null ? rect : pager.getBoundingClientRect();
			const under = foot.top + foot.height - rect.top - rect.height + 1.5 * rem + Nino.admin.design._bar().declared;

			// How far the box stands inside its card, for the card to be brought to the top of the window
			const inside = rect.top - card.getBoundingClientRect().top;

			const room = wn.innerHeight - ( rect.top + Nino.admin.design._scrolled( box ) ) - under;

			box.style.height = Math.max( least, room >= least ? room : wn.innerHeight - inside - under )+ 'px';
		},

		/**
		 *	Let go of the faces the names were set in, and of what watched the rows: the tab is left, and
		 *	a face is a file the browser holds
		 *
		 *	@return		void
		 */
		_dropFaces : function() {

			const loader = Nino.admin['design-typefaces'];

			if( loader._facesObserver !== null ) {
				loader._facesObserver.disconnect();
				loader._facesObserver = null;
			}

			Object.keys( loader._facesLoaded ).forEach( function( family ) {
				dc.fonts.delete( loader._facesLoaded[family] );
			} );

			loader._facesLoaded = {};
		},

	};

	/*	The shell asks Save, Discard or Cancel before a log out or a language
		change would lose a selection nobody has stored, and a reload gets
		the browser's own question. Save stores the draft - applying it is its
		own question. The draft is one, and so is the screen that carries it:
		it has its place in the shell's account of what is unsaved under the uri
		of its tab, in both of its modes, and a Save that failed brings the
		screen on, where the reason it gave is said (see _show())	*/
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

				Nino.admin.design._takeStored( Nino.admin.design._data );
				Nino.admin.design._render();
			},
		} );

	Nino.events.bindCallback( 'ready', Nino.admin.design.init );

})(window, document, document.documentElement, document.body);
