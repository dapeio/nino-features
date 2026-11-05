/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Builder					The panel of the Builder feature: the page templates of
 *													the project in a list, and one of them in an editor that
 *													is its preview - a static drawing of its sections,
 *													columns and components, from the model alone. Every
 *													frame of it has a head of three parts - its title, what
 *													is said of it, the tools that change it (settings, up,
 *													down, duplicate, copy, paste, delete; and for a section to
 *													edit it as HTML+) -, every level ends in a button that adds to it,
 *													and the settings of a frame open in a dialog of blocks, an
 *													accordion of the ones the element has - content, link,
 *													surface, spacing, width, loop and properties - drawn from one
 *													table. A component that holds
 *													components is a frame of its own among the components of
 *													its column, with its children in it and a form of its own.
 *													A section, a column or a component is copied once and pasted
 *													where it may stand, on the page or on another page of the
 *													project: the clip is kept in the browser's storage, with
 *													the texts of its keys, and a paste is a change of the model
 *													like a duplicate is. Ctrl+C and Ctrl+V - Cmd on a Mac -
 *													do it to the selected frame, outside the fields.
 *													The source of a text or a picture is changed in a dialog
 *													of its own, over that one, and the name of a section where
 *													it stands. The text of a key and the picture of a slot
 *													are edited where the component is, in the language the bar
 *													over the preview chooses: the text is kept in the model and
 *													written when the document is saved, the picture is stored
 *													at once.
 *
 *													The server reads and writes the file (see Document,
 *													Reader and Writer): this script knows the model only,
 *													as Reader describes it, changes it in place and sends it
 *													back. The preview is drawn from that model; the first line
 *													of the text a key holds and the picture a slot has are shown
 *													in it where they are known, and a placeholder where they are
 *													not, so nothing it shows can fail on a text or a picture.
 *													The pure half of the script - the operations on the model,
 *													the red sources, the preview model, what a save's answer
 *													means - touches no element and is what builder-js-smoke.js
 *													drives; the rest draws what the template
 *													(templates/panel.tpl) holds the fragments of.
 *
 *													The document is registered with the shell's unsaved
 *													input (Nino.admin.dirty): leaving it asks Save, Discard
 *													or Cancel, and it survives a switch to another panel.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.builder = {

		// The page templates that were listed, and what the forms are built
		// from (builder/registry): components, stacks, element types with their
		// fields, image slots, the frames a page may name
		_ready		: false,
		// Whether the first load was asked for, and whether the template's own
		// controls are wired
		_started	: false,
		_bound		: false,
		_files		: [],
		_registry	: null,
		// What the other panels had written - how often, by kind (see Nino.admin.stamp()) - when the list, the keys, the types and the
		// slots of this one were asked for: a kind that is another number now is data that is not what it shows (see _reloadMoved())
		_seen			: { templates : 0, routes : 0, texts : 0, elements : 0, slots : 0 },

		// The document on screen: { file, hash, model, saved, usedBy }. saved is
		// the model as it was last loaded or saved, as json, which is what the
		// unsaved state is measured against. null while the list is on screen
		_doc			: null,

		// What is selected, as a path into the model: [] the template, [b] a
		// section, [b, c] a column, [b, c, k] a component, [b, c, 'x'] the loop,
		// [b, c, k, j] a component inside one that holds components
		_sel			: [],
		// The viewport the preview shows - s, m or l, or g for all of them at once - and
		// the frame the pointer is over: the innermost one, whose tools are seen
		_viewport	: 'l',
		_hovered	: null,
		// The language the editor works in: the texts its dialogs show and write and the
		// preview shows are those of this one of the site's languages. The native one when
		// the editor is opened, and kept nowhere
		_locale		: '',
		// The sections the editor made since the document was loaded, by id: their
		// keys and slots are new, so a rename moves them in the model and no
		// request has to move them
		_fresh		: {},
		// What the last Copy took - { kind, file, section, node, values }, see _copyAt() - or null. It is kept in the browser's storage under CLIP_KEY as
		// well, so that it survives a reload and a change of page: it is read from there when the editor opens
		_clip			: null,
		CLIP_KEY	: 'nino-admin-builder-clip',
		// The text keys of the project, for the source field: null until they were
		// asked for, [] where this account may not read them
		_keys			: null,
		_keysAsked: false,
		// Whether the answer is in: before it, no key is there and none was refused
		_keysAnswered: false,
		// Whether this account may read the keys - the answer was a 200 - and so may make one from the dialog of a link; and the pages of the site as the answer
		// listed them (see _pageKey()): none where the Routes panel is not there
		_keysRead	: false,
		_pages		: [],
		// What the last save refused: the sentences of a 400, and the block each names
		_problems	: [],
		_saving		: false,
		// The line in the bar that says whether the document is saved
		_status		: null,

		// What paints the button of a view of the preview as the chosen one (see _bind()),
		// and how many groups of radio buttons the forms have made: each has a name of its own
		_selectView	: null,
		_segments		: 0,

		// The values of Nino.css the forms offer, by the setting they are for (see
		// Reader). A '' is the setting off
		COLORS		: [ '', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ],
		// A column paints them too, and counter between none and them: the ground that sets it off from its section. So does a component
		// that holds components, the card of the kernel
		COL_COLORS: [ '', 'counter', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ],
		// The ground counter is on each ground, the pairs of Nino.css: default and alt, tint to default, dark and black, primary and brand-alt
		COUNTERS	: { '' : 'alt', alt : '', tint : '', dark : 'black', black : 'dark', primary : 'brand-alt', 'brand-alt' : 'primary' },
		BORDERS		: [ '', '1', '2', '3' ],
		// The maximum width of a row that has one: the grid's own, which is none, and two narrower ones. None at all is the full width, which is a choice of its own (WIDTHS)
		ROWS			: [ '', 'narrow', 'wide' ],
		// How wide the row of a section is: as wide as the content, as wide as the page, or to the edge of the page, with no padding at its sides. The first two are the
		// setting row (none, or full), the third is full with the padding at the left and the right of the row at 0 (see _widthOf())
		WIDTHS		: [ 'content', 'full', 'edge' ],
		// Where a row narrower than the page stands, in the order it is seen: at the left, in the middle - none - and at the right
		ROW_PLACES	: [ 'left', '', 'right' ],
		// Where a row puts its columns, up and down: at the top, which is none, in the middle,
		// at the bottom. nino-grid-center puts them in the middle across, and is for a file
		// written by hand: the form leaves it as it is and has no icon for it
		ROW_ALIGN	: [ '', 'middle', 'bottom' ],
		// Where the row of a cover or a parallax stands in it: in the middle, which is none, at the top, at the bottom
		CONTENT_POS	: [ '', 'top', 'bottom' ],
		// The height of the tiles of a section of tiles, the ratio of the image component (Reader::RATIO): none is a tile as high as its words
		RATIOS		: [ '', '1-1', '4-3', '3-2', '16-9', '21-9', '4-1', '5-1' ],
		// The kinds of section: the content kind, which has no setting of its own, and the tiles
		KINDS			: [ 'content', 'tiles' ],
		// What a new tiles section starts as: the ratio of its tiles, and the widths of the two tiles it has - the whole width on a phone, a half from 768px
		TILES_RATIO	: '4-3',
		TILES_WIDTH	: { s : 100, m : 50, l : 50 },
		// How much room a section keeps, on the scale of the page's whitespace (the Design's): none is the page's own
		WHITESPACE	: [ '', 'least', 'less', 'more', 'most' ],
		// Where a component that is a link opens: the second of the two attributes every one has (the schema does not declare them)
		LINK_TARGETS	: [ '', '_blank' ],
		TEXTS			: [ '', 'left', 'center', 'right' ],
		SPACES		: [ '', '0', '1', '2', '3', '4', '5', '6' ],
		// The six sides of the spacing of an element, in the order the Reader keeps them (Reader::SPACING): outside above and below, inside above, below, left and right
		SIDES			: [ 'mt', 'mb', 'pt', 'pb', 'pl', 'pr' ],
		// The padding of the row of a section on its four sides, above, below, left and right: the row is the frame its columns stand in and has no outside
		ROW_SIDES	: [ 'rowPt', 'rowPb', 'rowPl', 'rowPr' ],
		// How far in from the left and the right the ground or the picture of a row, a column or a card starts: the steps of the space scale up to the third, none being the page's own
		OFFSETS		: [ '', '0', '1', '2', '3' ],
		// The effects of Nino.css, each of them in three strengths, which the model keeps with
		// the effect as one word (zoom-soft). The plain one, which fades in and rises, has neither
		EFFECTS		: [ '', 'zoom', 'zoom-out', 'slide-left', 'slide-right', 'slide-up', 'slide-down', 'flip', 'blur' ],
		STRENGTHS	: [ 'soft', 'medium', 'hard' ],
		SPEEDS		: [ '', 'fast', 'medium', 'slow' ],
		MODES			: [ '', 'repeat', 'visible', 'visible-once' ],
		// What an animation is set to run as: once, which is the page's default, or each time it comes into view
		ANIMATION_MODES	: [ '', 'repeat' ],
		COL_WIDTHS: [ '25', '33', '50', '66', '75', '100' ],
		STACK_ALIGN: [ '', 'start', 'center', 'end' ],
		// The nine places of the focus of a picture, by the words of the panel, in the order
		// Nino.css numbers them: 1 is the top left, 5 the middle, 9 the bottom right
		FOCUS			: [ 'top-left', 'top', 'top-right', 'left', 'center', 'right', 'bottom-left', 'bottom', 'bottom-right' ],
		VIEWPORTS	: [ 's', 'm', 'l' ],
		// ...and what the preview shows: one of them, or all at once (g), each with its icon
		VIEWS			: [ 's', 'm', 'l', 'g' ],
		DEVICES		: { s : 'smartphone', m : 'tablet', l : 'monitor', g : 'monitor-smartphone' },
		// The icon of a value of an alignment, where it has one: the others are shown as their word
		ALIGN_ICONS	: {
			text			: { left : 'align-left', center : 'align-center', right : 'align-right' },
			rowAlign	: { '' : 'align-top', middle : 'align-middle', bottom : 'align-bottom' },
			stackAlign: { start : 'stack-start', center : 'stack-center', end : 'stack-end' },
			// Where the content stands in a box, as the attribute of a component says it (the kernel's alignments): the last element at the bottom has an icon of its own
			vertical	: { '' : 'align-top', top : 'align-top', middle : 'align-middle', bottom : 'align-bottom', 'last-bottom' : 'align-last-bottom' },
		},
		// What the icon of a component is built from (see _shape()): the namespace of its elements, and the seven tags it may be
		// made of with the attributes each of them takes - the rule the kernel holds an icon to before the registry has it
		SVG				: 'http://www.w3.org/2000/svg',
		SHAPE_TAGS	: { path : [ 'd' ], circle : [ 'cx', 'cy', 'r' ], ellipse : [ 'cx', 'cy', 'rx', 'ry' ], rect : [ 'x', 'y', 'width', 'height', 'rx', 'ry' ], line : [ 'x1', 'y1', 'x2', 'y2' ], polyline : [ 'points' ], polygon : [ 'points' ] },
		// The custom property of Nino.css that holds the ground a colour paints
		GROUNDS		: { '' : '--color-section-default-bg', alt : '--color-section-alt-bg', tint : '--color-section-tint-bg', dark : '--color-section-dark-bg', black : '--color-section-black-bg', primary : '--color-primary', 'brand-alt' : '--color-brand-alt' },
		// How many characters of a text the head of a block says of it
		SAID_MAX	: 28,
		// The attributes of a component that are its link and stand in that block, besides the address (the type href) and the two a component that is a link has: the window
		// it opens in and - of a surface - whether all of it is the link
		LINK_KEYS	: [ 'target', 'linked' ],
		// The words the Builder has for the values of an attribute a component declares, by the name of the attribute: the group of their
		// words (see _optionLabel()). The select of an attribute with another name keeps its values as they are
		ATTRIBUTE_WORDS	: { padding : 'space', align : 'text', vertical : 'vertical', target : 'target' },

		// How many visible characters the editor of an [html] component keeps: the
		// editor trims to it after every edit, so it is a limit and never none
		CONTENT_MAX	: 100000,

		// The formats of a text key, as \Nino\Html knows them; the size a slot is made in
		// where nothing else says it, as the form of a new slot starts; and how many
		// characters of the first line of a text a placeholder of the preview shows
		FORMATS			: [ 'plain', 'inline', 'lines', 'blocks' ],
		SLOT_SIZE		: { width : 1600, height : 900 },
		LINE_MAX		: 60,

		// What a text key and a section id are made of: lower-case words of letters
		// and digits joined by hyphens - the grammar \Nino\Text::isGrammarKey() holds
		// a key to, which the server decides again
		SEGMENT		: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
		KEY				: /^\/(?:template|project|feature|module)(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){3}$/,

		// An address that leads to a page of the site and goes on from it: the directory the site is served from, [[/nino/dir]], the fill of the key that holds the address of the
		// page - which has no directory, the kernel puts it in front of a bare key - and the sub-path after it: characters that are none of a space, a quote, a backslash or a
		// bracket, and the fills of the element of a loop - the one form \Nino\Modules\Builder\Reader::address() holds a value to
		// Built from a string: the guard of keys-smoke against the old key family of a page takes the escaped slash of a literal for one of its forms
		ADDRESS		: new RegExp( '^\\[\\[/nino/dir\\]\\]\\[\\[(/_nino/webpage/[^\\[\\]\\s"\'\\\\]+/uri)\\]\\]((?:[^\\s"\'\\\\\\[\\]]|\\[\\[\\.?[A-Za-z0-9_-]+\\]\\])*)$' ),
		SUBPATH		: /^(?:[^\s"'\\\[\]]|\[\[\.?[A-Za-z0-9_-]+\]\])*$/,
		FILE			: /^page-[a-z0-9]+(?:-[a-z0-9]+)*$/,

		// The preview images the registry may name, and the one for the rest
		PREVIEWS	: [ 'title', 'text', 'image', 'button', 'block', 'cells', 'table' ],

		// The places a loop holds the calls of, and a column of a page does not offer (the 'in' of a component, the 'holds' of a loop)
		LOOP_PLACES	: [ 'table' ],

		// The components a list's row is made of, as the kernel names them: a field of the element is [text <field>], a line of free text with fields in it is [line text="..."]
		ROW_FIELD	: 'text',
		ROW_LINE	: 'line',
		// The types of field a cell can show as text; a column shows a picture besides
		SHOWN			: [ 'string', 'integer', 'double', 'boolean', 'date', 'datetime' ],
		// What a select of values calls a line of free text
		FREE			: '*',

		// What the dialog of an element shows, and the one place that says it: for each kind of element the blocks it has, in the order they are drawn in - the same seven for every kind, in
		// this order: content, link, surface, spacing, width, loop and properties - and in each block its controls, in the order they stand in, by the key of the setting a control writes. A
		// control that writes several settings stands under each of them and is drawn once, at the first (the box of the six sides, the two offsets of a ground, the width of a column and where
		// it is hidden); one that is no setting of its own stands under its own name (the width of the row of a section, which is the maximum width, the padding at its sides and the full
		// width in one choice). _blocksOf() draws what stands here and nothing else: a control that is moved is drawn in its new block, one that is struck out is not drawn at all, and a
		// block none of whose controls the element has is not drawn (a card has no content: its title, its text and its picture are components with a form of their own). The controls of
		// a section and of a column are made by _sectionControls() and _colControls(). A component has the controls of its schema, by group - the source, the attributes that are a
		// link, those on the tab of the background, the variants and the other attributes, each group a control - and the settings every component has (_componentControls()). The
		// surface of a section and of a column is the colours and the lines first and then, from the picture on, one group of its own, the picture behind it with what depends on it
		// (the table lists the controls of the group in a row and each of them builds into it, see _groupIn()); the loop of a section and of a column is a block of its own, which a
		// card has no control for
		BLOCKS		: {
			section : [
				{ id : 'surface', controls : [ 'border', 'borderPrimary', 'color', 'inner', 'radius', 'rowRadius', 'background', 'cover', 'parallax', 'dim', 'padded', 'rowBgPl', 'rowBgPr', 'arrow' ] },
				// The block of a section of tiles: the controls that are the tiles' own - the ratio, and the gap and the text of the row under the words of tiles - have names of their own, so that a content section has none of them
				{ id : 'tiles', controls : [ 'ratio', 'tileGap', 'tileText' ] },
				{ id : 'spacing', controls : [ 'rowGap', 'whitespace', 'mt', 'mb', 'pt', 'pb', 'pl', 'pr', 'rowPt', 'rowPb', 'rowPl', 'rowPr' ] },
				{ id : 'width', controls : [ 'width', 'row', 'rowPlace', 'text', 'rowAlign', 'contentPos' ] },
				// The order of the columns of the row, turned round for each viewport by itself - for a content section and for tiles alike
				{ id : 'order', controls : [ 'reverse' ] },
				{ id : 'loop', controls : [ 'stack' ] },
				{ id : 'properties', controls : [ 'kind', 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration', 'custom', 'rowCustom', 'id' ] },
			],
			column : [
				{ id : 'surface', controls : [ 'color', 'radius', 'background', 'dim', 'linked', 'bgPl', 'bgPr' ] },
				{ id : 'spacing', controls : [ 'stackGap', 'mt', 'mb', 'pt', 'pb', 'pl', 'pr' ] },
				{ id : 'width', controls : [ 'width', 'hidden', 'stackAlign', 'text' ] },
				{ id : 'loop', controls : [ 'stack' ] },
				{ id : 'properties', controls : [ 'vpa', 'vpaSpeed', 'vpaMode', 'custom' ] },
			],
			component : [
				{ id : 'content', controls : [ 'source' ] },
				{ id : 'link', controls : [ 'link' ] },
				{ id : 'surface', controls : [ 'background', 'bgPl', 'bgPr', 'radius', 'hover' ] },
				{ id : 'spacing', controls : [ 'mt', 'mb', 'pt', 'pb', 'pl', 'pr' ] },
				{ id : 'properties', controls : [ 'variants', 'attributes', 'class' ] },
			],
		},
		// --------------------------------------------------------- The model

		/**
		 *	A copy of a value that shares nothing with it
		 *
		 *	@param		{*}					value
		 *
		 *	@return		{*}
		 */
		_clone : function( value ) {
			return value === undefined ? undefined : JSON.parse( JSON.stringify( value ) );
		},

		/**
		 *	What php sends an empty map as - [] - is a map here, where the model
		 *	keeps the hidden viewports of a column and the attributes of a call:
		 *	a copy that is only ever compared as json needs one shape for it
		 *
		 *	@param		{Object}		model
		 *
		 *	@return		{Object}									The same model
		 */
		_normalise : function( model ) {

			const map = function( value ) { return Array.isArray( value ) === true || value === null || typeof value !== 'object' ? {} : value };

			( model.blocks || [] ).forEach( function( block ) {

				if( block.kind !== 'section' )
					return;

				block.settings = map( block.settings );
				block.settings.reverse = Object.assign( { s : false, m : false, l : false }, map( block.settings.reverse ) );

				if( block.stack !== null && typeof block.stack === 'object' )
					block.stack.attributes = map( block.stack.attributes );

				( block.cols || [] ).forEach( function( col ) {

					col.hidden = map( col.hidden );
					col.width = map( col.width );
					col.components = Array.isArray( col.components ) === true ? col.components : [];

					if( col.stack !== null && typeof col.stack === 'object' )
						col.stack.attributes = map( col.stack.attributes );

					col.components.forEach( function( component ) {

						component.attributes = map( component.attributes );

						( component.components || [] ).forEach( function( child ) { child.attributes = map( child.attributes ) } );
					} );
				} );
			} );

			return model;
		},

		/**
		 *	The node a path names
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							[], [b], [b, c], [b, c, k], [b, c, 'x'] or - inside a component that holds components - [b, c, k, j]
		 *
		 *	@return		{Object|null}							null for a path the model has nothing at
		 */
		_get : function( model, path ) {

			if( path.length === 0 )
				return model;

			const block = ( model.blocks || [] )[path[0]];

			if( block === undefined || path.length === 1 )
				return block === undefined ? null : block;

			const col = block.kind === 'section' ? ( block.cols || [] )[path[1]] : undefined;

			if( col === undefined || path.length === 2 )
				return col === undefined ? null : col;

			if( path[2] === 'x' )
				return path.length === 3 && col.stack !== null && col.stack !== undefined ? col.stack : null;

			const component = col.components[path[2]] ?? null;

			if( path.length === 3 || component === null )
				return component;

			return path.length === 4 ? ( component.components ?? [] )[path[3]] ?? null : null;
		},

		/**
		 *	The list a node stands in and its place there - the one a node can be
		 *	moved in: the components of a column, or the children of a component
		 *	that holds components. The template and a stack stand in none
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *
		 *	@return		{Object|null}							{ list, index }
		 */
		_place : function( model, path ) {

			if( path.length === 0 || path[path.length - 1] === 'x' || Nino.admin.builder._get( model, path ) === null )
				return null;

			const parent = Nino.admin.builder._get( model, path.slice( 0, -1 ) );
			const list = path.length === 1 ? parent.blocks : ( path.length === 2 ? parent.cols : parent.components );

			return { list : list, index : path[path.length - 1] };
		},

		/**
		 *	Whether two paths name the same node
		 *
		 *	@param		{Array}			a
		 *	@param		{Array}			b
		 *
		 *	@return		{boolean}
		 */
		_same : function( a, b ) {
			return a.length === b.length && a.every( function( part, at ) { return part === b[at] } );
		},

		/**
		 *	What a node is: template, section, html, col, stack or component - a component
		 *	that holds components is one, and so are its children
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *
		 *	@return		{string}
		 */
		_kind : function( model, path ) {

			if( path.length === 0 )
				return 'template';

			if( path.length === 1 )
				return ( model.blocks[path[0]] || {} ).kind === 'section' ? 'section' : 'html';

			return path.length === 2 ? 'col' : ( path[2] === 'x' ? 'stack' : 'component' );
		},

		/**
		 *	Move a node to another place in the list it stands in. Across levels
		 *	nothing is moved, and neither does a node go to another parent: a child
		 *	does not leave the component that holds it, and a component does not
		 *	enter one by moving
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			from							The path of the node
		 *	@param		{Array}			to								The path of the node whose place it takes
		 *
		 *	@return		{boolean}									Whether it was moved
		 */
		_move : function( model, from, to ) {

			const builder = Nino.admin.builder;

			if( from.length === 0 || from.length !== to.length || builder._same( from.slice( 0, -1 ), to.slice( 0, -1 ) ) === false )
				return false;

			const source = builder._place( model, from );
			const target = builder._place( model, to );

			if( source === null || target === null || source.index === target.index )
				return false;

			source.list.splice( target.index, 0, source.list.splice( source.index, 1 )[0] );

			return true;
		},

		/**
		 *	One step up or down in the list a node stands in
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *	@param		{number}		delta							-1 or 1
		 *
		 *	@return		{Array|null}							The new path, null where there is no step to take
		 */
		_step : function( model, path, delta ) {

			const to = path.slice( 0, -1 ).concat( [ path[path.length - 1] + delta ] );

			return Nino.admin.builder._move( model, path, to ) === true ? to : null;
		},

		/**
		 *	Take a node out of the model
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							A section, a column or a component. A loop is not taken out
		 *															here: it is set to Static in its column
		 *
		 *	@return		{boolean}
		 */
		_remove : function( model, path ) {

			const place = Nino.admin.builder._place( model, path );

			if( place === null )
				return false;

			place.list.splice( place.index, 1 );

			return true;
		},

		/**
		 *	What the tools of a frame offer, and whether each can be used now: a
		 *	section can be edited as HTML+, a node moves where it has a neighbour
		 *	in its level, and the one column of a section is not deleted - a
		 *	section has at least one. A loop is no frame and has no tools: it is
		 *	set in the block Loop of its column
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *
		 *	@return		{Object|null}							settings, html, up, down, duplicate, copy, paste and delete: null for a tool the
		 *															frame does not have, false for one it has and cannot use now - paste is so
		 *															where nothing was copied or the copy does not fit the frame (see _pasteable());
		 *															null for the template and a loop, which have none
		 */
		_toolbar : function( model, path ) {

			const builder = Nino.admin.builder;
			const kind = builder._kind( model, path );

			if( kind === 'template' || kind === 'stack' )
				return null;

			const place = builder._place( model, path );

			return {
				settings	: true,
				html			: kind === 'section' ? true : null,
				up				: place !== null && place.index > 0,
				down			: place !== null && place.index < place.list.length - 1,
				duplicate	: place !== null,
				copy			: place !== null,
				paste			: place !== null && builder._pasteable( model, builder._registry, path, builder._clip ) === '',
				delete		: place !== null && ( kind !== 'col' || place.list.length > 1 ),
			};
		},

		/**
		 *	How much a frame holds that is somebody's work: the components of a
		 *	column, and its loop, everything in a section, the children of a
		 *	component that holds components. A frame that holds none is deleted
		 *	without asking
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *
		 *	@return		{number}
		 */
		_children : function( model, path ) {

			const builder = Nino.admin.builder;
			const kind = builder._kind( model, path );
			const node = builder._get( model, path );
			const inside = function( col ) { return col.components.length + ( col.stack === null || col.stack === undefined ? 0 : 1 ) };

			if( node === null )
				return 0;

			if( kind === 'section' )
				return node.cols.reduce( function( sum, col ) { return sum + inside( col ) }, 0 );

			if( kind === 'col' )
				return inside( node );

			return kind === 'component' && Array.isArray( node.components ) === true ? node.components.length : 0;
		},

		// ------------------------------------------------------- Names and keys

		/**
		 *	The first name the list does not have yet: the name, then name-2, name-3
		 *
		 *	@param		{string}		base
		 *	@param		{Function}	taken							Whether a name is in use
		 *
		 *	@return		{string}
		 */
		_free : function( base, taken ) {

			let name = base;

			for( let n = 2; taken( name ) === true; n++ )
				name = base+ '-'+ n;

			return name;
		},

		/**
		 *	The ids of the sections that are a loop of sections: a loop makes
		 *	<its id>-<n> for the sections it renders, and the save refuses a
		 *	static section named like one of them
		 *
		 *	@param		{Object}		model
		 *
		 *	@return		{Array}										ids
		 */
		_loopNames : function( model ) {
			return model.blocks.filter( function( block ) { return block.kind === 'section' && block.stack !== null && block.stack !== undefined } ).map( function( block ) { return block.id } );
		},

		/**
		 *	Whether a section id is taken: by a block, or by a loop of sections
		 *	whose own sections it would be named like
		 *
		 *	@param		{Object}		model
		 *	@param		{string}		name
		 *
		 *	@return		{boolean}
		 */
		_sectionTaken : function( model, name ) {
			return model.blocks.some( function( block ) { return block.id === name } )
				|| Nino.admin.builder._loopNames( model ).some( function( id ) { return new RegExp( '^'+ id.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )+ '-\\d+$' ).test( name ) } );
		},

		/**
		 *	A word as one segment of a key: lower case, anything else a hyphen
		 *
		 *	@param		{string}		word
		 *
		 *	@return		{string}									'' where nothing is left
		 */
		_segment : function( word ) {
			return String( word ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );
		},

		/**
		 *	The key of a static component or the slot of a picture: the file, the
		 *	section and a name
		 *
		 *	@param		{string}		file							page-home
		 *	@param		{string}		section						The section's id
		 *	@param		{string}		name
		 *
		 *	@return		{string}
		 */
		_keyUri : function( file, section, name ) {
			return '/template/'+ file+ '/'+ section+ '/'+ name;
		},

		/**
		 *	The id the keys and slots of a section stand under: its own, and - where it was renamed and the rename is not saved yet - the one it had,
		 *	for the server moves them at the save and until then the sources of its components name the old one
		 *
		 *	@param		{Object}		section
		 *
		 *	@return		{string}
		 */
		_keyId : function( section ) {
			return section.renamedFrom ?? section.id;
		},

		/**
		 *	The last segments of every source the section has under its own keys
		 *	(and the keys the project holds there) - the names a new one may not take
		 *
		 *	@param		{Object}		section
		 *	@param		{string}		file
		 *	@param		{Array}			[known]						Keys of the project, entries as text/keys answers them
		 *
		 *	@return		{Object}									name => true
		 */
		_names : function( section, file, known ) {

			const prefix = Nino.admin.builder._keyUri( file, section.id, '' );
			const names = {};
			const note = function( source ) {
				if( typeof source === 'string' && source.indexOf( prefix ) === 0 )
					names[source.slice( prefix.length )] = true;
			};

			if( section.background )
				note( section.background.slot );

			// A source, whatever an attribute names that stands under the keys of the section (a picture), and the same in the children of a
			// component that holds components
			const visit = function( component ) {

				note( component.source );
				Object.keys( component.attributes || {} ).forEach( function( name ) { note( component.attributes[name] ) } );
				( component.components || [] ).forEach( visit );
			};

			( section.cols || [] ).forEach( function( col ) {

				if( col.background )
					note( col.background.slot );

				col.components.forEach( visit );
			} );

			( known || [] ).forEach( function( entry ) { note( entry.key ) } );

			return names;
		},

		/**
		 *	What the project holds under keys and slots already, as _names() takes it: the text keys it knows (the editor asked for them) and the
		 *	image slots of the registry. A name a copy is given is not one of these - a key or a slot left over under the prefix of a section is
		 *	not written into
		 *
		 *	@param		{Object}		registry
		 *
		 *	@return		{Array}										Entries with a key
		 */
		_knownKeys : function( registry ) {
			return ( Nino.admin.builder._keys || [] ).concat( ( registry.slots || [] ).map( function( slot ) { return { key : slot.uri } } ) );
		},

		/**
		 *	The name a new key or slot of a section is given: the kind of the
		 *	component (title, text, button), and with a number where the section
		 *	has it already (title-2) - what 1.6 of the concept says
		 *
		 *	@param		{Object}		section
		 *	@param		{string}		file
		 *	@param		{string}		base							The component's name
		 *	@param		{Array}			[known]
		 *
		 *	@return		{string}
		 */
		_keyName : function( section, file, base, known ) {

			const builder = Nino.admin.builder;
			const names = builder._names( section, file, known );

			return builder._free( builder._segment( base ) || 'item', function( name ) { return names[name] === true } );
		},

		/**
		 *	Why a name for a new key or slot is not one: the grammar, or a name
		 *	the section has
		 *
		 *	@param		{Object}		section
		 *	@param		{string}		file
		 *	@param		{string}		name
		 *	@param		{Array}			[known]
		 *
		 *	@return		{string}									'' for a name that will do, else 'grammar' or 'taken'
		 */
		_nameProblem : function( section, file, name, known ) {

			const builder = Nino.admin.builder;

			if( builder.SEGMENT.test( name ) === false || builder.KEY.test( builder._keyUri( file, section.id, name ) ) === false )
				return 'grammar';

			return builder._names( section, file, known )[name] === true ? 'taken' : '';
		},

		/**
		 *	What a component is by its schema: text, image, href, content or none
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name
		 *
		 *	@return		{string}
		 */
		_sourceKind : function( registry, name ) {
			return ( ( registry.components || {} )[name] || {} ).source || 'text';
		},

		/**
		 *	The place a component holds components of, as its schema says it ('holds'): its children are the
		 *	components of that place. The Builder knows no component by name - what holds components is a
		 *	container, whatever it is called
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name
		 *
		 *	@return		{string}									'' for a component that holds none
		 */
		_holds : function( registry, name ) {

			const holds = ( ( registry.components || {} )[name] || {} ).holds;

			return typeof holds === 'string' ? holds : '';
		},

		/**
		 *	The attributes of a schema that name a picture - the ones of the type image - in its order
		 *
		 *	@param		{Object}		declared					name => the schema's entry, as _declared() has them
		 *
		 *	@return		{Array}
		 */
		_pictures : function( declared ) {
			return Object.keys( declared ).filter( function( name ) { return declared[name].type === 'image' } );
		},

		/**
		 *	Whether two lists hold the same values, in whatever order
		 *
		 *	@param		{Array}			a
		 *	@param		{Array}			b
		 *
		 *	@return		{boolean}
		 */
		_sameValues : function( a, b ) {
			return a.length === b.length && a.every( function( value ) { return b.indexOf( value ) !== -1 } ) && b.every( function( value ) { return a.indexOf( value ) !== -1 } );
		},

		/**
		 *	Whether an attribute is the focus of a picture: a select over none and the nine places, whatever it is called
		 *
		 *	@param		{Object}		declared					The schema's entry of the attribute
		 *
		 *	@return		{boolean}
		 */
		_isFocus : function( declared ) {

			const builder = Nino.admin.builder;
			const places = [ '' ].concat( builder.FOCUS.map( function( place, at ) { return String( at + 1 ) } ) );

			return declared.type === 'select' && Array.isArray( declared.options ) === true && builder._sameValues( declared.options, places );
		},

		/**
		 *	Whether an attribute is a ground: a select over the grounds a column may paint (see COL_COLORS), whatever it is called
		 *
		 *	@param		{Object}		declared					The schema's entry of the attribute
		 *
		 *	@return		{boolean}
		 */
		_isGround : function( declared ) {

			const builder = Nino.admin.builder;

			return declared.type === 'select' && Array.isArray( declared.options ) === true && builder._sameValues( declared.options, builder.COL_COLORS );
		},

		/**
		 *	What the attributes of a schema are to a picture, by their type and their place - never by their name: a focus is
		 *	a select over the nine places (see _isFocus()), and a switch right after a picture, or after the focus that follows it,
		 *	is the dimming of that picture
		 *
		 *	@param		{Object}		declared					name => the schema's entry, as _declared() has them
		 *
		 *	@return		{Object}									name => { role, picture } for the focus and the dimming: role being focus or dim, picture the
		 *																	name of the attribute of the type image before it - '' for a focus that has none, which is the
		 *																	picture of the component itself. The other attributes are not in it
		 */
		_roles : function( declared ) {

			const builder = Nino.admin.builder;
			const roles = {};
			// The last picture, and the one the attribute before this one belongs to: the picture itself, or its focus
			let picture = '';
			let chain = '';

			Object.keys( declared ).forEach( function( name ) {

				if( declared[name].type === 'image' ) {
					picture = name;
					chain = name;
					return;
				}

				if( builder._isFocus( declared[name] ) === true ) {
					roles[name] = { role : 'focus', picture : picture };
					return;
				}

				if( declared[name].type === 'bool' && chain !== '' )
					roles[name] = { role : 'dim', picture : chain };

				chain = '';
			} );

			return roles;
		},

		/**
		 *	A section as the editor makes a new one: a name that is free, the
		 *	animation the template gives, one column over the whole width, nothing
		 *	in it. The template gives the bare nino-vpa where it animates its
		 *	sections and none where it does not. A section of tiles starts with the
		 *	ratio of TILES_RATIO and two tiles, a half each from 768px
		 *
		 *	@param		{Object}		model
		 *	@param		{string}		[kind]						content, the default, or tiles
		 *
		 *	@return		{Object}
		 */
		_newSection : function( model, kind ) {

			const builder = Nino.admin.builder;
			// A loop called section would take every section-<n>: the new one is then section-new
			const id = builder._free( builder._loopNames( model ).indexOf( 'section' ) === -1 ? 'section' : 'section-new', function( name ) { return builder._sectionTaken( model, name ) } );

			return {
				kind			: 'section',
				id				: id,
				settings	: { row : '', rowPlace : '', rowAlign : '', reverse : { s : false, m : false, l : false }, rowGap : '', rowPl : '', rowPr : '', rowPt : '', rowPb : '', rowRadius : '', rowCustom : '', rowBgPl : '', rowBgPr : '', color : '', border : '', borderPrimary : false, cover : null, parallax : false, dim : false, padded : false, radius : '', contentPos : '', inner : false, tiles : kind === 'tiles', ratio : kind === 'tiles' ? builder.TILES_RATIO : '', whitespace : '', mt : '', mb : '', pt : '', pb : '', pl : '', pr : '', text : '', arrow : false, vpa : model.animate === true ? '' : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '', custom : '' },
				background : null,
				stack			: null,
				cols			: kind === 'tiles' ? [ builder._newCol( builder.TILES_WIDTH ), builder._newCol( builder.TILES_WIDTH ) ] : [ builder._newCol() ],
			};
		},

		/**
		 *	A column as the editor makes a new one: the whole width everywhere, or the widths it is given
		 *
		 *	@param		{Object}		[width]						{ s, m, l }
		 *
		 *	@return		{Object}
		 */
		_newCol : function( width ) {
			return { width : width === undefined ? { s : 100, m : 100, l : 100 } : Object.assign( {}, width ), hidden : {}, color : '', dim : false, linked : false, radius : '', background : null, text : '', stackAlign : '', stackGap : '', mt : '', mb : '', pt : '', pb : '', pl : '', pr : '', bgPl : '', bgPr : '', vpa : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '', custom : '', stack : null, components : [] };
		},

		/**
		 *	A column added to the row of a section: the last of the row, the whole width everywhere. The columns the row has keep the widths they have - what was set
		 *	is the editor's, and a share made behind its back is not - and the width of the new one is for the editor to set
		 *
		 *	@param		{Object}		section
		 *
		 *	@return		{Object}									The column, which is now the last of the row
		 */
		_addedCol : function( section ) {

			const col = Nino.admin.builder._newCol();

			section.cols.push( col );

			return col;
		},

		/**
		 *	The loop of a column as it is made for a stack of the registry: the
		 *	attributes the stack has, the ones an earlier loop of the column set
		 *	and this one has too kept, and the type the earlier one looped or the
		 *	first the project has
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name							The registered stack
		 *	@param		{Object|null}	old							The loop the column has
		 *
		 *	@return		{Object}									{ name, source, attributes }
		 */
		_stackFor : function( registry, name, old ) {

			const builder = Nino.admin.builder;
			const defaults = builder._clone( ( ( registry.stacks || {} )[name] || {} ).defaults || {} );
			const types = registry.types || [];

			Object.keys( defaults ).forEach( function( key ) {
				if( old !== null && Object.prototype.hasOwnProperty.call( old.attributes, key ) === true )
					defaults[key] = old.attributes[key];
			} );

			return { name : name, source : old !== null ? old.source : ( types.length > 0 ? types[0].uri : '' ), attributes : defaults };
		},

		/**
		 *	What a stack is called in the panel: the plain one of the kernel is the
		 *	loop - its own label is the name of the shortcode - and the others have
		 *	the label they registered
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name
		 *
		 *	@return		{string}
		 */
		_stackLabel : function( registry, name ) {
			return name === 'stack' ? Nino.content.getText('/_admin/builder/tree/stack') : ( ( registry.stacks || {} )[name] || {} ).label || name;
		},

		/**
		 *	The stack whose cell is a whole section, as the schema says it ('cell'): the loop that makes sections. The Builder asks the
		 *	registry and knows no stack by name
		 *
		 *	@param		{Object}		registry
		 *
		 *	@return		{string}									'' where the registry has none
		 */
		_sectionLoop : function( registry ) {

			const stacks = registry.stacks || {};

			return Object.keys( stacks ).find( function( name ) { return stacks[name].cell === 'section' } ) ?? '';
		},

		/**
		 *	Whether a section makes a section of every element: it is the call of the loop that does, and has the id and the classes of
		 *	a call and no attribute of a tag
		 *
		 *	@param		{Object}		section
		 *
		 *	@return		{boolean}
		 */
		_looping : function( section ) {
			return section.stack !== null && section.stack !== undefined;
		},

		/**
		 *	The loop the components of a column are the cell of: the column's own - or, where it has none and its section makes
		 *	sections out of the elements, the section's: the columns of such a section are the cell, once for each element
		 *
		 *	@param		{Object}		section
		 *	@param		{Object}		col
		 *
		 *	@return		{Object|null}							{ name, source, attributes }
		 */
		_loopOf : function( section, col ) {

			if( col.stack !== null && col.stack !== undefined )
				return col.stack;

			return section.stack === null || section.stack === undefined ? null : section.stack;
		},

		/**
		 *	An effect and its strength as the one word the model keeps them as
		 *
		 *	@param		{string}		effect						'' for the plain one, else zoom, zoom-out, slide-left, slide-right, slide-up, slide-down, flip or blur
		 *	@param		{string}		strength					soft, medium or hard
		 *
		 *	@return		{string}									zoom-soft - and '' for the plain effect, which has no strength
		 */
		_effectJoin : function( effect, strength ) {
			return effect === '' ? '' : effect+ '-'+ strength;
		},

		/**
		 *	The effect and the strength a word of the model is made of
		 *
		 *	@param		{string}		word							zoom-out-soft, or '' for the plain effect
		 *
		 *	@return		{Object}									{ effect, strength } - both '' for the plain effect
		 */
		_effectSplit : function( word ) {

			const text = String( word ?? '' );
			const strength = Nino.admin.builder.STRENGTHS.find( function( candidate ) { return text.endsWith( '-'+ candidate ) } );

			return strength === undefined ? { effect : '', strength : '' } : { effect : text.slice( 0, -( strength.length + 1 ) ), strength : strength };
		},

		/**
		 *	The animation as the classes of an element say it
		 *
		 *	@param		{string|null}	classes						nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium, or null for none
		 *
		 *	@return		{Object}									vpa, vpaSpeed, vpaMode - the settings of a section, as Reader reads the classes
		 */
		_vpaParse : function( classes ) {

			const builder = Nino.admin.builder;
			const settings = { vpa : null, vpaSpeed : '', vpaMode : '' };

			if( typeof classes !== 'string' )
				return settings;

			classes.split( /\s+/ ).forEach( function( token ) {

				const found = /^nino-vpa--(.+)$/.exec( token );

				if( token === 'nino-vpa' ) {
					settings.vpa = settings.vpa ?? '';
					return;
				}

				if( found === null )
					return;

				const variant = builder._effectSplit( found[1] );

				if( builder.EFFECTS.indexOf( variant.effect ) > 0 && ( settings.vpa ?? '' ) === '' ) {
					settings.vpa = found[1];
					return;
				}

				const speed = /^speed-(fast|medium|slow)$/.exec( found[1] );

				if( speed !== null && settings.vpaSpeed === '' ) {
					settings.vpa = settings.vpa ?? '';
					settings.vpaSpeed = speed[1];
				} else if( builder.MODES.indexOf( found[1] ) > 0 && settings.vpaMode === '' ) {
					settings.vpa = settings.vpa ?? '';
					settings.vpaMode = found[1];
				}
			} );

			return settings;
		},

		/**
		 *	The classes an animation is written as, in the order the Writer writes them
		 *
		 *	@param		{Object}		settings					vpa, vpaSpeed, vpaMode
		 *
		 *	@return		{string|null}							null for none
		 */
		_vpaClasses : function( settings ) {

			if( settings.vpa === null || settings.vpa === undefined )
				return null;

			const classes = [ 'nino-vpa' ];

			if( settings.vpa !== '' )
				classes.push( 'nino-vpa--'+ settings.vpa );

			if( ( settings.vpaSpeed ?? '' ) !== '' )
				classes.push( 'nino-vpa--speed-'+ settings.vpaSpeed );

			if( ( settings.vpaMode ?? '' ) !== '' )
				classes.push( 'nino-vpa--'+ settings.vpaMode );

			return classes.join(' ');
		},

		/**
		 *	Which of three the animation of a section is: like the template, off or its
		 *	own. The kernel reads the classes of the section and nothing of the template,
		 *	so it is worked out of the two. Where the template animates its sections, none
		 *	is off, the bare nino-vpa - no variant, no speed, no mode, no delay, no
		 *	duration - is like the template, and everything else is the section's own.
		 *	Where it does not, none is like the template (which is off as well), and
		 *	everything else is the section's own, the bare class among it: the plain effect
		 *
		 *	@param		{boolean}		animate						Whether the template animates its sections
		 *	@param		{Object}		settings					Of a section
		 *
		 *	@return		{string}									like, off or own
		 */
		_animationMode : function( animate, settings ) {

			const classes = Nino.admin.builder._vpaClasses( settings );

			if( classes === null )
				return animate === true ? 'off' : 'like';

			return animate === true && classes === 'nino-vpa' && ( settings.vpaDelay ?? '' ) === '' && ( settings.vpaDuration ?? '' ) === '' ? 'like' : 'own';
		},

		/**
		 *	A section made to carry exactly the classes given (or none): its own delay
		 *	and duration go, which no class has a word for
		 *
		 *	@param		{Object}		settings					Of a section; changed in place
		 *	@param		{string|null}	classes
		 *
		 *	@return		void
		 */
		_vpaApply : function( settings, classes ) {

			Object.assign( settings, Nino.admin.builder._vpaParse( classes ), { vpaDelay : '', vpaDuration : '' } );
		},

		/**
		 *	The animation of a section made like the template, off, or its own. Like the
		 *	template is the bare nino-vpa where the template animates and none where it
		 *	does not; the section gets the variant and the speed of the template from the
		 *	wrap, through the custom properties. Its own starts as the plain animation
		 *	where there was none
		 *
		 *	@param		{boolean}		animate						Whether the template animates its sections
		 *	@param		{Object}		settings					Of a section; changed in place
		 *	@param		{string}		mode							like, off or own
		 *
		 *	@return		void
		 */
		_animationSet : function( animate, settings, mode ) {

			if( mode === 'own' ) {

				if( settings.vpa === null || settings.vpa === undefined )
					settings.vpa = '';

				return;
			}

			Nino.admin.builder._vpaApply( settings, mode === 'like' && animate === true ? 'nino-vpa' : null );
		},

		/**
		 *	The sections that are like the template now
		 *
		 *	@param		{Object}		model
		 *
		 *	@return		{Array}										The indexes of their blocks
		 */
		_likeSections : function( model ) {

			const builder = Nino.admin.builder;
			const found = [];

			model.blocks.forEach( function( block, b ) {
				if( block.kind === 'section' && builder._animationMode( model.animate === true, block.settings ) === 'like' )
					found.push( b );
			} );

			return found;
		},

		/**
		 *	What the sections that were like the template are given when its switch is
		 *	turned: the animation that is like the template now. Those with an animation
		 *	of their own are not among them
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			indexes						As _likeSections() found them before the switch was turned
		 *
		 *	@return		void
		 */
		_followSections : function( model, indexes ) {

			indexes.forEach( function( b ) {
				if( model.blocks[b] !== undefined && model.blocks[b].kind === 'section' )
					Nino.admin.builder._animationSet( model.animate === true, model.blocks[b].settings, 'like' );
			} );
		},

		/**
		 *	The order of a loop as the loop reads it: a field, and a minus in front
		 *	of it for the other way round - or, for a list of several fields, the
		 *	text itself
		 *
		 *	@param		{Object}		stack
		 *
		 *	@return		{Object}									{ field, descending, several }
		 */
		_sortState : function( stack ) {

			const sort = String( stack.attributes.sort || '' );

			if( sort.indexOf(',') !== -1 )
				return { field : sort, descending : false, several : true };

			return { field : sort.charAt(0) === '-' ? sort.slice( 1 ) : sort, descending : sort.charAt(0) === '-', several : false };
		},

		/**
		 *	The order of a loop, written: a field and a direction; none for no field
		 *
		 *	@param		{Object}		stack
		 *	@param		{string}		field
		 *	@param		{boolean}		descending
		 *
		 *	@return		void
		 */
		_sortSet : function( stack, field, descending ) {
			stack.attributes.sort = field === '' ? '' : ( descending === true ? '-' : '' )+ field;
		},

		/**
		 *	The width of a column in one viewport, written
		 *
		 *	@param		{Object}		col
		 *	@param		{string}		viewport					s, m or l
		 *	@param		{string}		width							25, 33, 50, 66, 75 or 100
		 *
		 *	@return		void
		 */
		_setWidth : function( col, viewport, width ) {
			col.width[viewport] = parseInt( width, 10 );
		},

		/**
		 *	Whether a column is hidden in one viewport, written - a viewport that
		 *	shows it is no entry at all
		 *
		 *	@param		{Object}		col
		 *	@param		{string}		viewport
		 *	@param		{boolean}		hidden
		 *
		 *	@return		void
		 */
		_setHidden : function( col, viewport, hidden ) {

			if( hidden === true )
				col.hidden[viewport] = true;
			else
				delete col.hidden[viewport];
		},

		/**
		 *	A component as the editor makes a new one: the attributes the schema
		 *	has, the six sides of the spacing, and in a static column a key of its own where it reads a text -
		 *	made when the document is saved, with the text that is typed for it in
		 *	each language and none in the others: the component's label is no text
		 *	of its key, the field and the preview show it where nothing is typed.
		 *	A component that holds components has none of its own: it starts with no
		 *	children. A component that is a surface starts with no radius of its own, and a picture with the hover off
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		file
		 *	@param		{Array}			at								The path of the column - or of the component that holds components, for a child
		 *	@param		{string}		name							The component's name
		 *	@param		{Array}			[known]
		 *
		 *	@return		{Object}
		 */
		_newComponent : function( model, registry, file, at, name, known ) {

			const builder = Nino.admin.builder;
			const schema = ( registry.components || {} )[name] || {};
			const kind = builder._sourceKind( registry, name );
			const section = model.blocks[at[0]];
			const component = { name : name, source : '', text : null, attributes : builder._clone( schema.defaults || {} ) };

			// Every component has the six sides of the spacing, none until the editor sets them
			builder.SIDES.forEach( function( side ) { component[side] = '' } );

			// A component that is a surface has a radius of its own, which is none until the editor sets it: it follows the default of its kind. The card, which paints a ground,
			// has the two offsets of it as well
			if( builder._surfaceOf( schema ) !== '' )
				component.radius = '';

			if( builder._surfaceOf( schema ) === 'card' ) {
				component.bgPl = '';
				component.bgPr = '';
			}

			// A picture - a component whose source is an image, the picture of a card among them - has the hover of the page, off until the editor switches it on
			if( kind === 'image' )
				component.hover = '';

			// What stands between the tags of a component that holds components is its children; of any other it is a content
			if( kind === 'content' && builder._holds( registry, name ) !== '' )
				component.components = [];
			else if( kind === 'content' )
				component.content = '';

			if( builder._loopOf( section, section.cols[at[1]] ) === null && ( kind === 'text' || kind === 'image' ) && builder.SEGMENT.test( section.id ) === true ) {

				const slug = builder._keyName( section, file, name, known );

				component.source = builder._keyUri( file, section.id, slug );
				// The mark of a key that is made says no more than that: what it holds is what is typed for it (values)
				component.create = kind === 'image' ? Object.assign( { label : schema.label || name }, builder.SLOT_SIZE ) : {};
				builder._placeholders( registry, component );
			}

			return component;
		},

		/**
		 *	What a component is called in each language of the site: the label the registry gives it there, and where it
		 *	has none for a language the one it has in the language of the workbench
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name							The component
		 *
		 *	@return		{Object}									locale => label
		 */
		_labels : function( registry, name ) {

			const schema = ( registry.components || {} )[name] || {};
			const labels = {};

			( registry.locales || [] ).forEach( function( entry ) {

				const own = ( schema.labels || {} )[entry.locale];

				labels[entry.locale] = typeof own === 'string' && own !== '' ? own : ( schema.label || name );
			} );

			return labels;
		},

		/**
		 *	The placeholder of a component that is new, where it is written into the languages: the slot of a picture is made
		 *	with the alt text of each language - the label of its component in it. A placeholder alt text is better than
		 *	none, and an alt text has no fallback of its own to stand in for one. A text key has no placeholder to write:
		 *	it is made with what is typed for it, and the label of its component is what the field shows as long as it is
		 *	empty (see _labelIn()). A component that makes nothing keeps no placeholder
		 *
		 *	@param		{Object}		registry
		 *	@param		{Object}		component				With its create, the mark of the key or the slot it is made with
		 *
		 *	@return		void
		 */
		_placeholders : function( registry, component ) {

			const builder = Nino.admin.builder;
			const create = component.create;

			if( builder._sourceKind( registry, component.name ) !== 'image' || create === undefined || create === null || typeof create !== 'object' )
				return;

			const labels = builder._labels( registry, component.name );

			if( Object.keys( labels ).length > 0 )
				create.alt = labels;
		},

		/**
		 *	What a component is called in one language of the site: its label there, or - where it has none for the language -
		 *	the one it has in the language of the workbench. The words an empty text field shows in the language it is typed in
		 *
		 *	@param		{Object}		registry
		 *	@param		{string}		name							The component
		 *	@param		{string}		locale
		 *
		 *	@return		{string}
		 */
		_labelIn : function( registry, name, locale ) {

			const schema = ( registry.components || {} )[name] || {};
			const own = ( schema.labels || {} )[locale];

			return typeof own === 'string' && own !== '' ? own : ( schema.label || name );
		},

		/**
		 *	A copy of a node for a place beside it: the copy has an id of its own
		 *	where it is a section, and the keys and slots a section owns are new
		 *	ones in the copy - made at save, with the text the key has now in each
		 *	language it has one in, and with the picture and the alt texts the slot has
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		file
		 *	@param		{Array}			path							A section, a column or a component
		 *	@param		{Function}	[valuesOf]				The text of a key in each language it has one in: locale => text; none where it is not known
		 *
		 *	@return		{Array|null}							The path of the copy
		 */
		_duplicate : function( model, registry, file, path, valuesOf ) {

			const builder = Nino.admin.builder;
			const place = builder._place( model, path );

			if( place === null )
				return null;

			const copy = builder._clone( place.list[place.index] );
			const section = model.blocks[path[0]];

			if( path.length === 1 && copy.kind === 'section' ) {

				// A copy of a loop of sections, or of a section named like one, cannot be <id>-<n>: that is what the loop makes
				const id = builder._free( builder._loopNames( model ).indexOf( copy.id ) === -1 ? copy.id : copy.id+ '-copy', function( name ) { return builder._sectionTaken( model, name ) } );
				const from = builder._keyUri( file, builder._keyId( copy ), '' );

				copy.id = id;
				delete copy.renamedFrom;
				builder._rekey( copy, registry, from, builder._keyUri( file, id, '' ), false, undefined, undefined, valuesOf );
			} else if( path.length >= 2 ) {

				const prefix = builder._keyUri( file, builder._keyId( section ), '' );

				builder._rekey( path.length === 2 ? copy : { cols : [ { components : [ copy ] } ] }, registry, prefix, prefix, true, section, file, valuesOf );
			}

			place.list.splice( place.index + 1, 0, copy );

			return path.slice( 0, -1 ).concat( [ path[path.length - 1] + 1 ] );
		},

		/**
		 *	The keys and slots of a copy, made new: every source that stands under
		 *	a prefix is moved under another, or - in a copy that stays in its
		 *	section - given a free name beside the original. The picture an attribute
		 *	names is a slot of the section like any, and the children of a component
		 *	that holds components are copied with it. A key is made with the text the
		 *	original has in each language it has one in - typed for the copy, so that
		 *	the save makes it that way - and with none in the others, and a slot says
		 *	which slot it is a copy of (create.from), at its size: the save hands the
		 *	picture and the alt texts of that one on
		 *
		 *	@param		{Object}		node							A section, or a column (or a stand-in with its cols)
		 *	@param		{Object}		registry
		 *	@param		{string}		from							The prefix the sources stand under
		 *	@param		{string}		to								The prefix they go to
		 *	@param		{boolean}		within						Whether the copy stays in the section, so the last segment changes
		 *	@param		{Object}		[section]					Its section, for the names that are taken: those it has, the keys of the project and the slots of the registry
		 *	@param		{string}		[file]
		 *	@param		{Function}	[valuesOf]				See _duplicate()
		 *
		 *	@return		void
		 */
		_rekey : function( node, registry, from, to, within, section, file, valuesOf ) {

			const builder = Nino.admin.builder;
			const columns = node.kind === 'section' ? node.cols : ( node.cols || [ node ] );
			const taken = within === true ? builder._names( Object.assign( {}, section, { id : builder._keyId( section ) } ), file, builder._knownKeys( registry ) ) : {};
			const remake = function( source, kind, label ) {

				if( typeof source !== 'string' || source.indexOf( from ) !== 0 )
					return { source : source };

				const last = source.slice( from.length );
				const name = within === true ? builder._free( last.replace( /-\d+$/, '' ) || 'item', function( candidate ) { return taken[candidate] === true } ) : last;

				taken[name] = true;

				if( kind === 'image' ) {

					const old = ( registry.slots || [] ).find( function( slot ) { return slot.uri === source } );
					const size = old !== undefined && old.width > 0 && old.height > 0 ? { width : old.width, height : old.height } : builder.SLOT_SIZE;

					return { source : to+ name, create : Object.assign( { label : label }, size, { from : source } ) };
				}

				return { source : to+ name, create : {}, values : typeof valuesOf === 'function' ? valuesOf( source ) : {} };
			};

			// The source of a component, the pictures its attributes name, and the same in each of its children
			const component = function( node ) {

				const schema = ( registry.components || {} )[node.name] || {};
				const declared = builder._declared( schema );
				const made = remake( node.source, builder._sourceKind( registry, node.name ), schema.label || node.name );

				node.source = made.source;

				if( made.create !== undefined )
					node.create = made.create;

				// What was typed for the key of the component and is not saved yet is the copy's too, and goes over the text of the other languages
				if( made.values !== undefined && Object.keys( made.values ).length > 0 )
					node.values = Object.assign( {}, made.values, node.values );

				builder._pictures( declared ).forEach( function( attribute ) {

					if( typeof ( node.attributes || {} )[attribute] !== 'string' )
						return;

					const picture = remake( node.attributes[attribute], 'image', ( schema.label || node.name )+ ' - '+ ( declared[attribute].label || attribute ) );

					node.attributes[attribute] = picture.source;

					if( picture.create !== undefined ) {
						node.slots = node.slots || {};
						node.slots[attribute] = picture.create;
					}
				} );

				( node.components || [] ).forEach( component );
			};

			// The picture behind a copy of a section is a new slot, and so is the one behind each of its columns - and behind a copy of a column
			const picture = function( holder ) {

				const made = remake( holder.background.slot, 'image', Nino.content.getText('/_admin/builder/source/background') );

				holder.background.slot = made.source;

				if( made.create !== undefined )
					holder.background.create = made.create;
			};

			if( node.kind === 'section' && node.background )
				picture( node );

			columns.forEach( function( col ) {

				if( col.background )
					picture( col );

				( col.components || [] ).forEach( component );
			} );
		},

		// ------------------------------------------------------- Copy and paste

		/**
		 *	What a clip is told by in a title: the word of the kind of node it holds (tree/*) and, for a section, its id
		 *
		 *	@param		{Object}		clip
		 *
		 *	@return		{string}
		 */
		_clipWord : function( clip ) {

			const word = { section : 'section', html : 'html-block', column : 'col', component : 'component' }[clip.kind];

			return Nino.content.getText( '/_admin/builder/tree/'+ word )+ ( clip.kind === 'section' ? ' '+ clip.node.id : '' );
		},

		/**
		 *	The first thing a clip holds that this project does not have: a component the registry does not know, a loop it does not know, or the
		 *	type that loop runs over. The Builder knows none of them by name - it is the registry that is asked
		 *
		 *	@param		{Object}		registry
		 *	@param		{Object}		clip
		 *
		 *	@return		{string}									Its name; '' where the project has all of it
		 */
		_clipMissing : function( registry, clip ) {

			const has = function( map, name ) { return Object.prototype.hasOwnProperty.call( map || {}, name ) };
			const types = registry.types || [];
			const loop = function( stack ) {

				if( stack === null || stack === undefined )
					return '';

				if( has( registry.stacks, stack.name ) === false )
					return stack.name;

				return types.some( function( type ) { return type.uri === stack.source } ) === true ? '' : ( stack.source || stack.name );
			};
			const component = function( node ) {

				if( has( registry.components, node.name ) === false )
					return node.name;

				return list( node.components );
			};
			const list = function( components ) {

				for( const node of components || [] ) {

					const missing = component( node );

					if( missing !== '' )
						return missing;
				}

				return '';
			};
			const column = function( col ) { return loop( col.stack ) || list( col.components ) };

			if( clip.kind === 'component' )
				return component( clip.node );

			if( clip.kind === 'column' )
				return column( clip.node );

			if( clip.kind === 'section' )
				return loop( clip.node.stack ) || ( clip.node.cols || [] ).reduce( function( found, col ) { return found || column( col ) }, '' );

			return '';
		},

		/**
		 *	Why components cannot stand where the place says, or - in one that holds components - why their children cannot: the first reason there is
		 *
		 *	@param		{Object}		registry
		 *	@param		{Array}			components				Nodes of the model; each is known to the registry
		 *	@param		{Object}		where							As _placeOf() has it
		 *
		 *	@return		{string}									'' where all of them can stand there
		 */
		_componentsProblem : function( registry, components, where ) {

			const builder = Nino.admin.builder;

			for( const node of components ) {

				const problem = builder._placeProblem( where, registry.components[node.name] );

				if( problem !== '' )
					return problem;

				if( Array.isArray( node.components ) === true ) {

					const inner = builder._componentsProblem( registry, node.components, { place : builder._holds( registry, node.name ), inside : true, looped : where.looped } );

					if( inner !== '' )
						return inner;
				}
			}

			return '';
		},

		/**
		 *	Where a clip goes for a path: the list it is put into and the place in it, and the path of the node that list belongs to. A section or a
		 *	block of html goes after a section or a block (a path of one part) or - for the template, the path [] - at the end of the page; a column
		 *	after a column or at the end of the columns of a section; a component after a component or a child, at the end of a column and at the
		 *	end of a component that holds components when that one takes it, else after that component
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{Array}			path
		 *	@param		{Object}		clip
		 *
		 *	@return		{Object|null}							{ list, index, parent }; null where the kind of the clip does not fit the node
		 */
		_pastePlace : function( model, registry, path, clip ) {

			const builder = Nino.admin.builder;
			const node = builder._get( model, path );

			if( node === null || path[path.length - 1] === 'x' )
				return null;

			if( clip.kind === 'section' || clip.kind === 'html' ) {

				if( path.length > 1 )
					return null;

				return { list : model.blocks, index : path.length === 0 ? model.blocks.length : path[0] + 1, parent : [] };
			}

			if( clip.kind === 'column' ) {

				if( path.length === 1 && node.kind === 'section' )
					return { list : node.cols, index : node.cols.length, parent : path };

				return path.length === 2 ? { list : model.blocks[path[0]].cols, index : path[1] + 1, parent : path.slice( 0, 1 ) } : null;
			}

			if( path.length === 2 )
				return { list : node.components, index : node.components.length, parent : path };

			if( path.length < 3 )
				return null;

			// Into the component that holds components, where it takes the clip: the place its 'holds' names, and whatever the column is
			if( path.length === 3 && builder._holds( registry, node.name ) !== '' && Array.isArray( node.components ) === true
				&& builder._componentsProblem( registry, [ clip.node ], { place : builder._holds( registry, node.name ), inside : true, looped : builder._placeOf( model, path.slice( 0, 2 ), registry ).looped } ) === '' )
				return { list : node.components, index : node.components.length, parent : path };

			const place = builder._place( model, path );

			return { list : place.list, index : place.index + 1, parent : path.slice( 0, -1 ) };
		},

		/**
		 *	Whether the clip can be pasted at a path, and if not why: nothing was copied, the clip holds something the project does not have, the
		 *	kind of the clip does not fit the node, a component may not stand there (the answers of the lightbox, see _placeProblem()), a loop
		 *	cannot stand in a loop
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{Array}			path							The node the tool is of, or [] for the end of the page
		 *	@param		{Object|null}	clip
		 *
		 *	@return		{string}									'' where it can; else the reason, in the words of the panel
		 */
		_pasteable : function( model, registry, path, clip ) {

			const builder = Nino.admin.builder;
			const text = function( key, name ) { return Nino.adminUi.format( Nino.content.getText( '/_admin/builder/reason/'+ key ), name ) };

			if( clip === null || clip === undefined )
				return text('paste-empty');

			const missing = builder._clipMissing( registry, clip );

			if( missing !== '' )
				return text( 'paste-unknown', missing );

			const place = builder._pastePlace( model, registry, path, clip );

			if( place === null )
				return text( 'paste-place', builder._clipWord( clip ) );

			if( clip.kind === 'column' ) {

				const section = model.blocks[place.parent[0]];
				const looping = clip.node.stack !== null && clip.node.stack !== undefined;

				if( looping === true && builder._looping( section ) === true )
					return text('stack-in-stack');

				return builder._componentsProblem( registry, clip.node.components || [], { place : builder._where( model, place.parent.slice( 0, 1 ), registry ), inside : false, looped : looping || builder._looping( section ) } );
			}

			return clip.kind === 'component' ? builder._componentsProblem( registry, [ clip.node ], builder._placeOf( model, place.parent, registry ) ) : '';
		},

		/**
		 *	Put the clip beside or into the node at the path: a copy of it that shares nothing with it, with keys and slots of its own where it owns
		 *	any - under the section it goes into, which may be one of another page. The keys are made with the words the clip carries, the slots
		 *	say which slot they are copies of. The clip itself stays
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		file							The page the model is of
		 *	@param		{Array}			path
		 *	@param		{Object}		clip
		 *
		 *	@return		{Array|null}							The path of the copy; null where the clip does not fit the path (see _pasteable() for why)
		 */
		_paste : function( model, registry, file, path, clip ) {

			const builder = Nino.admin.builder;
			const place = builder._pastePlace( model, registry, path, clip );

			if( place === null )
				return null;

			const copy = builder._clone( clip.node );
			const valuesOf = function( source ) { return builder._clone( ( clip.values || {} )[source] ) ?? {} };

			if( clip.kind === 'section' ) {

				// As a duplicate has it: an id of its own, and not one the loop of sections would make
				const id = builder._free( builder._loopNames( model ).indexOf( copy.id ) === -1 ? copy.id : copy.id+ '-copy', function( name ) { return builder._sectionTaken( model, name ) } );
				const from = builder._keyUri( clip.file, clip.section, '' );

				copy.id = id;
				delete copy.renamedFrom;
				builder._rekey( copy, registry, from, builder._keyUri( file, id, '' ), false, undefined, undefined, valuesOf );
			} else if( clip.kind !== 'html' ) {

				const section = model.blocks[place.parent[0]];

				builder._rekey( clip.kind === 'column' ? copy : { cols : [ { components : [ copy ] } ] }, registry, builder._keyUri( clip.file, clip.section, '' ), builder._keyUri( file, builder._keyId( section ), '' ), true, section, file, valuesOf );
			}

			place.list.splice( place.index, 0, copy );

			return place.parent.concat( [ place.index ] );
		},

		/**
		 *	What the browser's storage holds as the clip: the clip, null where it holds none or what is no clip, undefined where there is no storage
		 *	to ask (a browser that keeps none, or refuses it) - then the clip lives in memory, for as long as the session does
		 *
		 *	@return		{Object|null|undefined}
		 */
		_storedClip : function() {

			const builder = Nino.admin.builder;
			let text;

			try {
				text = wn.localStorage.getItem( builder.CLIP_KEY );
			} catch(e) {
				return undefined;
			}

			if( typeof text !== 'string' )
				return null;

			let clip;

			try {
				clip = JSON.parse( text );
			} catch(e) {
				return null;
			}

			const object = function( value ) { return value !== null && typeof value === 'object' && Array.isArray( value ) === false };

			return object( clip ) === true && [ 'section', 'html', 'column', 'component' ].indexOf( clip.kind ) !== -1 && typeof clip.file === 'string' && typeof clip.section === 'string'
				&& object( clip.node ) === true && object( clip.values ) === true ? clip : null;
		},

		// ------------------------------------------------------- Red sources

		/**
		 *	What a source means where it stands, and the ones that mean nothing: a
		 *	field in a static column (it has no element), a field the stack's type
		 *	does not have, a stack that loops a type the Elements panel does not
		 *	know. The picture an attribute names counts as a source, and so do the
		 *	children of a component that holds components. The editor marks them red
		 *	and does not save with one - the server refuses them as well, and says
		 *	it a request later
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *
		 *	@return		{Array}										[ { path, source, why } ], why being static, field or type
		 */
		_red : function( model, registry ) {

			const builder = Nino.admin.builder;
			const red = [];

			model.blocks.forEach( function( block, b ) {

				if( block.kind !== 'section' )
					return;

				// The type a loop of sections runs over: red on the section, whose loop it is
				let around = null;

				if( block.stack !== null && block.stack !== undefined ) {

					const kind = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === block.stack.source } );

					if( kind === undefined )
						red.push( { path : [ b ], source : block.stack.source, why : 'type' } );
					else
						around = kind.fields || {};
				}

				block.cols.forEach( function( col, c ) {

					let fields = around;

					if( col.stack !== null && col.stack !== undefined ) {

						const type = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === col.stack.source } );

						if( type === undefined )
							red.push( { path : [ b, c, 'x' ], source : col.stack.source, why : 'type' } );
						else
							fields = type.fields || {};
					}

					const looped = builder._loopOf( block, col ) !== null;
					// Whether a value that is no key and no slot means something here: a field of the element in a loop, nothing in a column without one
					const mark = function( source, path, plain ) {

						if( looped === false )
							red.push( { path : path, source : source, why : 'static' } );
						else if( fields !== null && ( plain === true || [ '.id', '.uri', '.slug' ].indexOf( source ) === -1 ) && Object.prototype.hasOwnProperty.call( fields, source ) === false )
							red.push( { path : path, source : source, why : 'field' } );
					};
					// The picture behind a column may be an image field of the element where the section is a loop: elsewhere, or for a field the type has not or that is no image, it is red
					if( col.background !== null && col.background !== undefined && ( col.background.field ?? '' ) !== '' ) {

						if( block.stack === null || block.stack === undefined )
							red.push( { path : [ b, c ], source : col.background.field, why : 'background-static' } );
						else if( around !== null && ( Object.prototype.hasOwnProperty.call( around, col.background.field ) === false || around[col.background.field].type !== 'image' ) )
							red.push( { path : [ b, c ], source : col.background.field, why : 'background-field' } );
					}

					const check = function( component, path ) {

						const kind = builder._sourceKind( registry, component.name );
						const source = component.source || '';
						const declared = builder._declared( ( registry.components || {} )[component.name] || {} );

						if( source !== '' && kind !== 'none' && kind !== 'content' && source.charAt( 0 ) !== '/' && kind !== 'href' )
							mark( source, path, false );

						// What a column of a table shows is a field of the element, and so are the fills of a line of free text
						const called = ( registry.components || {} )[component.name] || {};
						const shown = builder._columnar( called ) === true ? String( ( component.attributes || {} ).field ?? '' ) : '';

						if( shown !== '' )
							mark( shown, path, false );

						if( called.fields === true && typeof component.text === 'string' )
							( component.text.match( /\[\[[A-Za-z0-9_.\/-]+\]\]/g ) || [] ).map( function( fill ) { return fill.slice( 2, -2 ) } ).forEach( function( fill ) {
								if( fill.charAt(0) === '/' )
									return;

								// The path of a file is no text: an image field is no fill of a line
								if( fields !== null && fields[fill] !== undefined && fields[fill].type === 'image' )
									red.push( { path : path, source : fill, why : 'field' } );
								else
									mark( fill, path, false );
							} );

						// The picture an attribute names is a slot or - in a loop - a field of the element, and not one of the special sources
						builder._pictures( declared ).forEach( function( attribute ) {

							const picture = String( ( component.attributes || {} )[attribute] ?? '' );

							if( picture !== '' && picture.charAt( 0 ) !== '/' )
								mark( picture, path, true );
						} );

						( component.components || [] ).forEach( function( child, j ) { check( child, path.concat( [ j ] ) ) } );
					};

					col.components.forEach( function( component, k ) { check( component, [ b, c, k ] ) } );
				} );
			} );

			return red;
		},

		// --------------------------------------------------------- The preview

		/**
		 *	The ground a column paints, said as the colour of a section is: its own, or - for counter - the counter of the
		 *	ground of the section it is in (see COUNTERS). A column that has none of its own paints none: that is for the
		 *	caller to say
		 *
		 *	@param		{string}		color							A value of COL_COLORS, none but ''
		 *	@param		{string}		around						The colour of the section
		 *
		 *	@return		{string}									A value of COLORS, '' being the ground of the page
		 */
		_paints : function( color, around ) {
			return color === 'counter' ? ( Nino.admin.builder.COUNTERS[around] ?? '' ) : color;
		},

		/**
		 *	The ground the content of a column stands on, which is what a component that paints a ground of its own is set off
		 *	from: the one the column paints - dark where a picture behind it is dimmed -, else the one of its section
		 *
		 *	@param		{Object}		section
		 *	@param		{Object}		col
		 *
		 *	@return		{string}									A value of COLORS, '' being the ground of the page
		 */
		_surface : function( section, col ) {

			const around = section.settings.color || '';

			if( col.background !== null && col.background !== undefined && col.dim === true )
				return 'dark';

			return col.color === undefined || col.color === '' ? around : Nino.admin.builder._paints( col.color, around );
		},

		/**
		 *	How a component that holds components looks from its attributes, which are found by their type and their place in the
		 *	schema: the ground it paints (the first select over the grounds), the picture behind it (the first of the type image)
		 *	and whether that is dimmed. A schema without one has none of it. A dimmed picture makes the ground dark, as it does in a
		 *	column; counter is the counter of the ground the component stands on
		 *
		 *	@param		{Object}		schema						The component's entry of the registry
		 *	@param		{Object}		node
		 *	@param		{string}		surface						As _surface() answers
		 *
		 *	@return		{Object}									{ color, picture } - color being '' for none, plain for the ground of the page, else a value of COLORS
		 */
		_look : function( schema, node, surface ) {

			const builder = Nino.admin.builder;
			const declared = builder._declared( schema );
			const attributes = node.attributes || {};
			const roles = builder._roles( declared );
			const names = Object.keys( declared );
			const first = builder._pictures( declared )[0];
			const ground = names.find( function( name ) { return builder._isGround( declared[name] ) } );
			const picture = first !== undefined && String( attributes[first] ?? '' ) !== '';
			const dimmed = picture === true && names.some( function( name ) {
				return roles[name] !== undefined && roles[name].role === 'dim' && roles[name].picture === first && String( attributes[name] ?? '' ) === '1';
			} );
			const color = ground === undefined ? '' : String( attributes[ground] ?? '' );

			return { color : dimmed === true ? 'dark' : ( color === '' ? '' : ( builder._paints( color, surface ) || 'plain' ) ), picture : picture };
		},

		/**
		 *	Where a component would stand, as a component says where it belongs (its 'in'): inside a component that holds
		 *	components the place its 'holds' names; in a column the hero where the column is in the first section of the page
		 *	(the hero, with a picture behind it or without), else a section. That is so in the cell of a loop as well: it holds
		 *	no place of its own, a cell that has no card is a column of the page. Whether a loop takes the component is another
		 *	matter (its 'loop'), and is not asked here
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							The column, or the component that holds components
		 *	@param		{Object}		[registry]					Where the 'holds' is read, the panel's own by default
		 *
		 *	@return		{string}									hero or section - or the place the container holds
		 */
		_where : function( model, path, registry ) {

			const builder = Nino.admin.builder;

			if( path.length > 2 )
				return builder._holds( registry ?? builder._registry, builder._get( model, path ).name );

			return path[0] === 0 ? 'hero' : 'section';
		},

		/**
		 *	The place components would stand in, as _placeProblem() asks it: the place its schema names ('in'), whether they stand inside a component
		 *	that holds components, and whether they are the cell of a loop
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							The column, or the component that holds components
		 *	@param		{Object}		registry
		 *
		 *	@return		{Object}									{ place, inside, looped }
		 */
		_placeOf : function( model, path, registry ) {

			const builder = Nino.admin.builder;
			const section = model.blocks[path[0]];

			return { place : builder._where( model, path, registry ), inside : path.length > 2, looped : builder._loopOf( section, section.cols[path[1]] ) !== null };
		},

		/**
		 *	Why a component cannot stand where the place says - what the lightbox greys it for, and what a paste is refused for: it belongs in other places
		 *	(its 'in'), a component that holds components is not put in another, a loop does not take it (its 'loop'). The words are the registry's own
		 *
		 *	@param		{Object}		where							As _placeOf() has it: { place, inside, looped }
		 *	@param		{Object}		schema						The component's entry of the registry
		 *
		 *	@return		{string}									'' for a component that can stand there
		 */
		_placeProblem : function( where, schema ) {

			const text = function( key ) { return Nino.content.getText( '/_admin/builder/reason/'+ key ) };
			const places = Array.isArray( schema.in ) === true ? schema.in : null;

			if( places !== null && places.indexOf( where.place ) === -1 ) {

				if( where.inside === true )
					return text( 'pick-not-'+ where.place ) || text('pick-elsewhere');

				if( places.indexOf('card') !== -1 && places.indexOf('section') === -1 )
					return text('pick-only-card');

				return text( places.indexOf('hero') !== -1 && places.indexOf('section') === -1 ? 'pick-only-hero' : 'pick-elsewhere' );
			}

			// A component that holds components is not put in another
			if( where.inside === true && typeof schema.holds === 'string' && schema.holds !== '' )
				return text( 'pick-not-'+ where.place ) || text('pick-elsewhere');

			return where.looped === true && schema.loop === false ? text('pick-no-loop') : '';
		},

		/**
		 *	Every component of the registry as the picker shows it where a component is added - in a column, in the cell of a loop,
		 *	in a component that holds components: in groups, by who registered it (what the kernel and the modules of the project
		 *	registered by the 'group' its schema names - the registry takes none that names no group of its list -, in the order of
		 *	that list, which the registry's answer carries as 'groups'; what a feature registered by the feature, after them) and, in
		 *	each, in the order of the registry. Every one is
		 *	there, and one that cannot go here says why: it belongs in other places (its 'in'), a component that holds components is
		 *	not put in another, a loop does not take it (its 'loop'). The Builder knows no component by name: the groups and the
		 *	reasons are the registry's own words
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							The column, or the component that holds components
		 *	@param		{Object}		registry
		 *
		 *	@return		{Array}										[ { id, label, rows: [ { label, items: [ { name, label, icon, reason } ] } ] } ] - id is the
		 *															word of the group, or features; the label of a row is the name of its feature where more than one
		 *															feature registered a component, else '' - and reason '' for a component that can be added. A
		 *															group with no component is not in it
		 */
		_pickChoices : function( model, path, registry ) {

			const builder = Nino.admin.builder;
			const where = builder._placeOf( model, path, registry );
			const names = Object.keys( registry.components || {} );
			const groups = {};
			const features = {};

			registry.groups.forEach( function( word ) { groups[word] = [] } );

			names.forEach( function( name ) {

				const schema = registry.components[name];
				const by = typeof schema.by === 'string' ? schema.by : 'nino';

				// A column of a table is made in the form of a table and offered nowhere else
				if( builder._columnar( schema ) === true )
					return;

				const item = { name : name, label : schema.label || name, icon : Array.isArray( schema.icon ) === true ? schema.icon : null, reason : builder._placeProblem( where, schema ) };

				if( by !== 'nino' )
					( features[by] = features[by] || [] ).push( item );
				else if( registry.groups.indexOf( schema.group ) !== -1 )
					groups[schema.group].push( item );
			} );

			const found = Object.keys( features );
			const result = registry.groups.map( function( word ) {
				return { id : word, label : Nino.content.getText( '/_admin/builder/pick/'+ word ), rows : [ { label : '', items : groups[word] } ] };
			} ).concat( [
				{ id : 'features', label : Nino.content.getText('/_admin/builder/pick/features'), rows : found.map( function( key ) {
					return { label : found.length > 1 ? ( ( registry.features || {} )[key] ?? key ) : '', items : features[key] };
				} ) },
			] );

			return result.filter( function( group ) { return group.rows.some( function( row ) { return row.items.length > 0 } ) } );
		},

		/**
		 *	What a component draws, as the second line of its placeholder says it: the 'draws' of its schema with each
		 *	{name} put in - the value the component has, else the default of the schema - and the tag, which is the first
		 *	word, in upper case: H3 .nino-section-title
		 *
		 *	@param		{Object}		schema						The component's entry of the registry
		 *	@param		{Object}		component
		 *
		 *	@return		{string}									'' where the schema says nothing of it
		 */
		_draws : function( schema, component ) {

			if( typeof schema.draws !== 'string' || schema.draws === '' )
				return '';

			const defaults = schema.defaults || {};
			const attributes = component.attributes || {};
			const text = schema.draws.replace( /\{([a-z0-9-]+)\}/g, function( all, name ) { return String( attributes[name] ?? defaults[name] ?? '' ) } );
			const space = text.indexOf(' ');

			return space === -1 ? text.toUpperCase() : text.slice( 0, space ).toUpperCase()+ text.slice( space );
		},

		/**
		 *	What the preview draws, from the model: the sections in order, each
		 *	with its colour and whether it has a picture behind it, its columns at the
		 *	width and the visibility of one viewport and in the ground each paints - dark
		 *	where its picture is dimmed -, in a column its components as
		 *	placeholders with the image the registry names, and for a column that runs a
		 *	loop what the loop is, and the type it runs over by the title the Elements
		 *	panel gives it - it is said in the head of the column, whose components are
		 *	the cell of each element. A viewport changes nothing but
		 *	widths and visibility; all of them at once (g) is drawn at the widths of
		 *	the widest, with no column greyed - which viewports hide a column is
		 *	what it says in its head. Where it is told the language and the keys, the
		 *	placeholder of a component that has a text says its first line - in that language, or
		 *	in the one the site shows in its place where the language has none (see _shownText()) -
		 *	and that of a picture has the picture of its slot, where the slot has one. A component that holds
		 *	components is a frame with its children in it, in the ground it paints and whether it
		 *	has a picture behind it (see _look())
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		viewport					s, m, l or g
		 *	@param		{Object}		[texts]						{ locale, keys }: the language the texts are shown in, and the keys of the project they are taken from
		 *
		 *	@return		{Array}										One entry per block
		 */
		_preview : function( model, registry, viewport, texts ) {

			const builder = Nino.admin.builder;
			const shown = viewport === 'g' ? 'l' : viewport;
			const place = function( component, path, surface ) {

				const schema = ( registry.components || {} )[component.name] || {};
				const text = texts === undefined ? null : builder._textOf( component, texts.locale, texts.keys, registry );
				const slot = texts === undefined || builder._sourceKind( registry, component.name ) !== 'image' ? undefined : ( registry.slots || [] ).find( function( candidate ) { return candidate.uri === component.source } );

				const made = {
					path		: path,
					name		: component.name,
					label		: schema.label || component.name,
					image		: builder.PREVIEWS.indexOf( schema.preview ) === -1 ? 'block' : schema.preview,
					line		: text === null ? '' : builder._firstLine( builder._shownText( component, text, texts.keys, registry ), builder.LINE_MAX ),
					picture	: slot !== undefined && typeof slot.url === 'string' ? slot.url : '',
					draws		: builder._draws( schema, component ),
					// A component that is a link and has an address to go to
					linked	: schema.link === true && String( ( component.attributes || {} ).href ?? '' ) !== '',
				};

				if( builder._holds( registry, component.name ) === '' )
					return made;

				// What holds components is a frame of its own: its tint, the picture behind it, and its children
				const look = builder._look( schema, component, surface );

				return Object.assign( made, {
					holds			: true,
					color			: look.color,
					background	: look.picture,
					children		: ( component.components || [] ).map( function( child, j ) { return place( child, path.concat( [ j ] ), surface ) } ),
				} );
			};

			return model.blocks.map( function( block, b ) {

				if( block.kind !== 'section' )
					return { path : [ b ], kind : 'html', id : '', color : '', rowColor : '', inner : false, padded : false, whitespace : '', loop : null, background : false, tiles : false, ratio : '', reason : block.reason || null, cols : [] };

				const covered = block.settings.cover !== null && block.settings.cover !== undefined;
				// A cover and a parallax are the section's own picture: the background is inside - on the row - for no other
				const inner = block.settings.inner === true && covered === false && block.settings.parallax !== true;
				const loop = block.stack === null || block.stack === undefined ? null : block.stack;
				const loops = loop === null ? undefined : ( registry.types || [] ).find( function( candidate ) { return candidate.uri === loop.source } );

				return {
					path				: [ b ],
					kind				: 'section',
					id					: block.id,
					// The ground of the frame is the section's - and where the background is inside, the row's, which is the body of the frame
					color				: inner === true ? '' : ( block.settings.color || '' ),
					rowColor		: inner === true ? ( block.settings.color || '' ) : '',
					inner				: inner,
					padded			: block.settings.padded === true && ( covered === true || block.settings.parallax === true ),
					whitespace	: block.settings.whitespace || '',
					loop				: loop === null ? null : { path : [ b ], type : loops === undefined || loops.title === '' ? loop.source : loops.title },
					background	: block.background !== null && block.background !== undefined,
					// A section of tiles is drawn edge to edge, its tiles in its ratio
					// The order of the columns: the viewports the row is turned round in, and whether it is in the one the preview shows
					reversedIn	: builder.VIEWPORTS.filter( function( at ) { return ( block.settings.reverse || {} )[at] === true } ),
					reversed		: viewport !== 'g' && ( block.settings.reverse || {} )[viewport] === true,
					tiles				: block.settings.tiles === true,
					ratio				: block.settings.tiles === true ? ( block.settings.ratio || '' ) : '',
					reason			: null,
					cols				: block.cols.map( function( col, c ) {

						const stack = col.stack === null || col.stack === undefined ? null : col.stack;
						const type = stack === null ? undefined : ( registry.types || [] ).find( function( candidate ) { return candidate.uri === stack.source } );
						const picture = col.background !== null && col.background !== undefined;

						return {
							path			: [ b, c ],
							width			: parseInt( col.width[shown], 10 ) || 100,
							hidden		: viewport !== 'g' && col.hidden[viewport] === true,
							hiddenIn	: builder.VIEWPORTS.filter( function( at ) { return col.hidden[at] === true } ),
							// The ground the frame is painted in: none where the column paints none, plain where its ground is the page's - and dark where its picture is
							// dimmed, whatever colour it names: Nino.css paints a dimmed column as the dark ground (a dimming without a picture is no ground)
							color			: picture === true && col.dim === true ? 'dark' : ( col.color === undefined || col.color === '' ? '' : ( builder._paints( col.color, block.settings.color || '' ) || 'plain' ) ),
							background : picture,
							// The image field of the element that is the picture of a column in a loop of sections, '' for a slot
							backgroundField : picture === true ? String( col.background.field ?? '' ) : '',
							linked		: block.settings.tiles === true && col.linked === true,
							text			: col.text || '',
							stackAlign : col.stackAlign || '',
							stack			: stack === null ? null : { path : [ b, c, 'x' ], label : builder._stackLabel( registry, stack.name ), type : type === undefined || type.title === '' ? stack.source : type.title },
							// A loop whose content is its columns is drawn as the table it makes, and a list whose cell is a row as its rows: they have no components to place
							table			: stack !== null && ( ( registry.stacks || {} )[stack.name] || {} ).preview === 'table' ? col.components.map( function( node, k ) {

								const text = texts === undefined ? null : builder._textOf( node, texts.locale, texts.keys, registry );

								return {
									title	: text === null ? '' : builder._firstLine( builder._shownText( node, text, texts.keys, registry ), builder.LINE_MAX ),
									value	: typeof node.text === 'string' ? node.text : String( ( node.attributes || {} ).field ?? '' ),
									// A column the save refuses - a field the type has not, a fill that is none - is red where it is drawn
									red		: builder._red( model, registry ).some( function( entry ) { return builder._same( entry.path, [ b, c, k ] ) } ),
								};
							} ) : null,
							rows			: stack !== null && builder._cellKind( registry, stack ) === 'row' && builder._rowOf( registry, col ) !== null ? ( function( row ) { return [ row, row, row ].map( function( one ) { return one.text !== undefined ? one.text : '[['+ one.field+ ']]' } ) } )( builder._rowOf( registry, col ) ) : null,
							components : col.components.map( function( component, k ) { return place( component, [ b, c, k ], builder._surface( block, col ) ) } ),
						};
					} ),
				};
			} );
		},

		// --------------------------------------------------- Saving and answers

		/**
		 *	Whether the document holds what nobody has saved
		 *
		 *	@param		{Object|null}	doc
		 *
		 *	@return		{boolean}
		 */
		_unsaved : function( doc ) {
			return doc !== null && doc.saved !== JSON.stringify( doc.model );
		},

		/**
		 *	What a save writes besides the file, as the model says it: texts for the keys that are made, moved or given a text, and
		 *	slots for the pictures that are made, moved or marked - the stores other panels show, so that they know to look again
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *
		 *	@return		{Object}									{ texts, slots } - whether it wrote any
		 */
		_writes : function( model, registry ) {

			const builder = Nino.admin.builder;
			const writes = { texts : false, slots : false };
			const visit = function( component ) {

				const image = builder._sourceKind( registry, component.name ) === 'image';

				if( component.create !== undefined && component.create !== null )
					writes[ image === true ? 'slots' : 'texts' ] = true;

				if( component.values !== undefined && Object.keys( component.values ).length > 0 )
					writes.texts = true;

				if( component.slots !== undefined && Object.keys( component.slots ).length > 0 )
					writes.slots = true;

				( component.components || [] ).forEach( visit );
			};

			model.blocks.forEach( function( block ) {

				if( block.kind !== 'section' )
					return;

				// A section that has a new name takes its keys and its slots along
				if( typeof block.renamedFrom === 'string' && block.renamedFrom !== '' && block.renamedFrom !== block.id ) {
					writes.texts = true;
					writes.slots = true;
				}

				if( block.background !== null && block.background !== undefined && block.background.create !== undefined )
					writes.slots = true;

				block.cols.forEach( function( col ) {

					if( col.background !== null && col.background !== undefined && col.background.create !== undefined )
						writes.slots = true;

					col.components.forEach( visit );
				} );
			} );

			return writes;
		},

		/**
		 *	What a save's answer means for the panel
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		{Object}									{ kind: saved | conflict | invalid | error, problems }
		 */
		_outcome : function( status, response ) {

			const body = response !== null && typeof response === 'object' ? response : {};

			if( status === 200 && typeof body.model === 'object' && body.model !== null )
				return { kind : 'saved', problems : [] };

			if( status === 409 && body.code === 'builder_conflict' )
				return { kind : 'conflict', problems : [] };

			if( status === 400 && ( body.code === 'builder_invalid' || body.code === 'builder_value' ) )
				return { kind : 'invalid', problems : Array.isArray( body.params ) === true ? body.params : [] };

			return { kind : 'error', problems : Array.isArray( body.params ) === true && status === 409 ? body.params : [] };
		},

		/**
		 *	The request a choice in the conflict question makes: Reload takes what
		 *	the file is now and drops the changes, Save anyway sends the model again
		 *	and says to write it whatever the file is
		 *
		 *	@param		{Object}		doc
		 *	@param		{string}		choice						'reload', 'force' or anything else for Cancel
		 *
		 *	@return		{Object|null}							{ action, payload }, null for Cancel
		 */
		_conflictRequest : function( doc, choice ) {

			if( choice === 'reload' )
				return { action : 'builder/load', payload : { file : doc.file } };

			if( choice === 'force' )
				return { action : 'builder/save', payload : Nino.admin.builder._saveRequest( doc, true ) };

			return null;
		},

		/**
		 *	@param		{Object}		doc
		 *	@param		{boolean}		force
		 *
		 *	@return		{Object}									The payload of builder/save
		 */
		_saveRequest : function( doc, force ) {

			const payload = { file : doc.file, model : doc.model, hash : doc.hash };

			if( force === true )
				payload.force = true;

			return payload;
		},

		/**
		 *	The blocks the sentences of a refused save name: it says the section by its
		 *	id, or the block by its number
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			problems					Sentences, in English
		 *
		 *	@return		{Object}									{ blocks : { index : [ sentence ] }, general : [ sentence ] }
		 */
		_blame : function( model, problems ) {

			const blame = { blocks : {}, general : [] };

			problems.forEach( function( sentence ) {

				const id = /the section "([^"]*)"/.exec( sentence );
				const number = /\bblock (\d+)\b/.exec( sentence );
				let index = -1;

				if( id !== null )
					index = model.blocks.findIndex( function( block ) { return block.kind === 'section' && block.id === id[1] } );
				else if( number !== null )
					index = parseInt( number[1], 10 ) - 1;

				if( index < 0 || index >= model.blocks.length ) {
					blame.general.push( sentence );
					return;
				}

				( blame.blocks[index] = blame.blocks[index] || [] ).push( sentence );
			} );

			return blame;
		},

		/**
		 *	The sentence for a reason a section was not read: the panel's own words
		 *	for its code, with the detail where the sentence has a place for it - or
		 *	two, which the Reader puts together with a line break in the detail
		 *
		 *	@param		{Object}		reason						{ line, code, detail, text }
		 *
		 *	@return		{string}
		 */
		_reasonText : function( reason ) {

			const words = Nino.content.getText( '/_admin/builder/reason/'+ reason.code );
			const detail = String( reason.detail ?? '' );
			const cut = detail.indexOf('\n');
			const two = words.split('%s').length > 2 && cut !== -1;
			const text = words === '' ? ( reason.text || '' ) : ( two === true ? Nino.adminUi.format( words, detail.slice( 0, cut ), detail.slice( cut + 1 ) ) : Nino.adminUi.format( words, reason.detail ) );

			return reason.line > 0 ? Nino.adminUi.format( Nino.content.getText('/_admin/builder/state/line'), reason.line, text ) : text;
		},

		// ----------------------------------------------------------- Requests

		/**
		 *	Call an action of this panel through the workbench's request helper - it
		 *	knows the project's directory and what to do when the page has outlived
		 *	its session
		 *
		 *	@param		{string}		endpoint			The name of the action below builder/
		 *	@param		{Object}		payload				Sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( status, parsed json or null )
		 *	@param		{Object}		[extra]				Extra multipart fields (eg. { file : File })
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback, extra ) {
			Nino.adminUi.api.call( 'builder/'+ endpoint, payload, callback, extra );
		},

		/**
		 *	Call an action of another panel - the Text panel's keys, the Images panel's
		 *	slots and pictures - through the same helper
		 *
		 *	@param		{string}		action				<panel>/<name>, eg. images/upload
		 *	@param		{Object}		payload				Sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( status, parsed json or null )
		 *	@param		{Object}		[extra]				Extra multipart fields (eg. { file : File }), as the Images panel posts a picture
		 *
		 *	@return		void
		 */
		_panelCall : function( action, payload, callback, extra ) {
			Nino.adminUi.api.call( action, payload, callback, extra );
		},

		/**
		 *	What a failed request says: the panel's own sentence for the code the
		 *	server gave (builder_conflict is /_admin/builder/error/conflict) - with
		 *	the params of the answer where the sentence has a %s for them -, else
		 *	what the workbench says of the answer
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key						Fill key of the sentence for what the panel was doing
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			const body = response !== null && typeof response === 'object' ? response : {};

			if( typeof body.code === 'string' && /^builder_[a-z_]+$/.test( body.code ) === true ) {

				const own = Nino.content.getText( '/_admin/builder/error/'+ body.code.slice( 8 ).replace( /_/g, '-' ) );

				// A sentence with a place for what is named - the ids a loop of sections makes - says it
				if( own !== '' )
					return own.indexOf('%s') === -1 ? own : Nino.adminUi.format( own, ( Array.isArray( body.params ) === true ? body.params : [] ).filter( function( param ) { return typeof param === 'string' } ).join(', ') );
			}

			return Nino.adminUi.api.errorText( status, response, key );
		},

		/**
		 *	What a refused request names besides its sentence, in the panel's own
		 *	words. The params of the answer are bare uris, as the refusal to delete
		 *	gets the keys of the routes: for a copy that would take over keys or
		 *	image slots that are there already (builder_key_exists) two lists, the
		 *	keys and then the slots - for a key or a slot that a panel refused
		 *	(builder_key, builder_slot) the one uri. The sentence about them is put
		 *	together here; what the server says in words is never shown
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		{string}									A space and the sentences, '' where the answer names none
		 */
		_inTheWay : function( status, response ) {

			const body = response !== null && typeof response === 'object' ? response : {};
			const params = status === 409 && Array.isArray( body.params ) === true ? body.params : [];

			// Only what is text: the panel says nothing of whatever else an answer holds
			const uris = function( list ) {
				return ( Array.isArray( list ) === true ? list : [] ).filter( function( uri ) { return typeof uri === 'string' && uri !== '' } );
			};

			if( body.code !== 'builder_key_exists' )
				return uris( params ).length === 0 ? '' : ' '+ uris( params ).join(', ');

			const keys = uris( params[0] );
			const slots = uris( params[1] );

			return ( keys.length === 0 ? '' : ' '+ Nino.adminUi.format( Nino.content.getText('/_admin/builder/error/keys-in-the-way'), keys.join(', ') ) )
				+ ( slots.length === 0 ? '' : ' '+ Nino.adminUi.format( Nino.content.getText('/_admin/builder/error/slots-in-the-way'), slots.join(', ') ) );
		},

		/**
		 *	@param		{Element}		container
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		void
		 */
		_showError : function( container, status, response ) {
			container.innerHTML = '';
			container.appendChild( Nino.admin.builder._errorLine( status, response ) );
		},

		/**
		 *	What went wrong, as a line
		 *
		 *	@param		{number}		status
		 *	@param		{Object|null}	response
		 *
		 *	@return		{Element}
		 */
		_errorLine : function( status, response ) {
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.builder._errorText( status, response, '/_admin/builder/error/load' );
			return p;
		},

		/**
		 *	What went wrong on the way to something the list leads to: said over the
		 *	list, which is drawn again, so the templates and the button for a new
		 *	one stay where they were
		 *
		 *	@param		{number}		status
		 *	@param		{Object|null}	response
		 *
		 *	@return		void
		 */
		_listError : function( status, response ) {

			const builder = Nino.admin.builder;

			builder._loadList( function() {

				const mount = dc.getElementById('builder-templates');

				mount.insertBefore( builder._errorLine( status, response ), mount.firstChild );
			} );
		},

		/**
		 *	One element: a tag, a class, a text
		 *
		 *	@param		{string}		tag
		 *	@param		{string}		[className]
		 *	@param		{string}		[text]
		 *
		 *	@return		{Element}
		 */
		_el : function( tag, className, text ) {

			const el = dc.createElement( tag );

			if( className )
				el.className = className;

			if( text !== undefined )
				el.textContent = text;

			return el;
		},

		/**
		 *	A copy of a fragment of the panel's template - the markup lives in
		 *	templates/panel.tpl, in <template> elements the script clones
		 *
		 *	@param		{string}		name
		 *
		 *	@return		{Element}
		 */
		_fragment : function( name ) {
			return dc.getElementById( 'builder-tpl-'+ name ).content.firstElementChild.cloneNode( true );
		},

		/**
		 *	@param		{Element}		root
		 *	@param		{string}		selector
		 *
		 *	@return		{Element}
		 */
		_one : function( root, selector ) {
			return root.querySelector( selector );
		},

		/**
		 *	Whether the editor may change anything: under 38rem it is for reading
		 *
		 *	@return		{boolean}
		 */
		_mutable : function() {
			return typeof wn.matchMedia !== 'function' || wn.matchMedia('(max-width: 38rem)').matches === false;
		},

		// ------------------------------------------------------ The two screens

		/**
		 *	Load the templates and draw whichever level the address names
		 *
		 *	@return		void
		 */
		init : function() {

			const builder = Nino.admin.builder;

			if( dc.getElementById('builder-templates') === null || builder._started === true )
				return;

			builder._started = true;
			builder._bind();
			builder._loadList( function() {

				builder._ready = true;

				const hash = Nino.admin.router.current();
				const file = hash.panel === 'builder' && hash.parts.length > 0 ? hash.parts[0] : '';

				if( builder.FILE.test( file ) === true )
					builder._openEditor( file );
				else
					builder._showScreen( 'list' );
			} );
		},

		/**
		 *	Show the level in memory again - the shell calls this when the panel
		 *	is opened - or the one the address names, where it names another: a step
		 *	through the browser's history changes the address and nothing else
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			const builder = Nino.admin.builder;

			if( builder._ready === false )
				return builder.init();

			const hash = Nino.admin.router.current();

			if( hash.panel === 'builder' && builder._follow( hash.parts ) === true )
				return;

			builder._showLevel();
			builder._reloadMoved();
		},

		/**
		 *	The stamps of some kinds of data now, as they are when a request for it goes out: what a panel remembers it read at - a
		 *	write that happens while the answer is on its way is after the stamp, and is found when the panel is shown again
		 *
		 *	@param		{Array}			topics
		 *
		 *	@return		{Object}									kind => stamp
		 */
		_stamped : function( topics ) {

			const found = {};

			topics.forEach( function( topic ) { found[topic] = Nino.admin.stamp( topic ) } );

			return found;
		},

		/**
		 *	This panel wrote a kind of data: the panels that show it look again when they are shown (Nino.admin.touch()). What this panel
		 *	wrote it has in a copy of its own - kept current in place, or asked for at once - so the stamp it remembers moves with its
		 *	write, and its own write is no news to it: asking for the registry again would draw the preview again, under the focus. Unless
		 *	another panel wrote before it did: that stays news
		 *
		 *	@param		{string}		topic
		 *
		 *	@return		void
		 */
		_touch : function( topic ) {

			const builder = Nino.admin.builder;
			const current = Nino.admin.stamp( topic ) === builder._seen[topic];

			Nino.admin.touch( topic );

			if( current === true )
				builder._seen[topic] = Nino.admin.stamp( topic );
		},

		/**
		 *	Ask again for what another panel changed since it was read: the list where it is on screen (the templates, and the routes that
		 *	name them), and in the editor the keys of the texts and the registry - the element types, the slots and the frames, which are
		 *	templates. A counter and a comparison, as Nino.admin.stamp() has it. Nothing is asked for while a dialog is open, and nothing
		 *	is drawn under it: what moved is looked at when the dialog is gone
		 *
		 *	@return		void
		 */
		_reloadMoved : function() {

			const builder = Nino.admin.builder;
			const moved = function( topics ) { return topics.some( function( topic ) { return Nino.admin.stamp( topic ) !== builder._seen[topic] } ) };

			if( builder._ready === false || builder._dialogOptions !== null )
				return;

			if( builder._doc === null ) {

				if( moved( [ 'templates', 'routes' ] ) === true )
					builder._loadList();

				return;
			}

			if( moved( [ 'texts' ] ) === true ) {
				builder._keysAsked = false;
				builder._loadKeys();
			}

			if( moved( [ 'templates', 'elements', 'slots' ] ) === true )
				builder._refreshRegistry();
		},

		/**
		 *	Move to the level the address names, if it is not the one on screen.
		 *	Leaving a document with changes asks first (see Nino.admin.router.leave())
		 *
		 *	@param		{Array}			parts					The address behind the panel's name
		 *
		 *	@return		{boolean}								Whether a move was made or is being asked about
		 */
		_follow : function( parts ) {

			const builder = Nino.admin.builder;
			const file = parts.length > 0 && builder.FILE.test( parts[0] ) === true ? parts[0] : '';

			if( file === ( builder._doc === null ? '' : builder._doc.file ) )
				return false;

			// A save that is on its way is not interrupted, and the step through
			// the history that brought the address here is taken back
			if( builder._saving === true ) {
				if( typeof Nino.admin.router.refuse === 'function' )
					Nino.admin.router.refuse();
				return false;
			}

			Nino.admin.router.leave( [ 'builder' ], builder._doc !== null, function() {
				if( file === '' )
					builder._showList();
				else
					builder._openEditor( file );
			}, builder._showLevel );

			return true;
		},

		/**
		 *	Show the level the panel is on and write it into the address
		 *
		 *	@return		void
		 */
		_showLevel : function() {

			const builder = Nino.admin.builder;

			builder._showScreen( builder._doc === null ? 'list' : 'editor' );
			Nino.admin.router.set( 'builder', builder._doc === null ? [] : [ builder._doc.file ] );
		},

		/**
		 *	@param		{string}		level					'list' or 'editor'
		 *
		 *	@return		void
		 */
		_showScreen : function( level ) {

			[ 'list', 'editor' ].forEach( function( name ) {
				dc.getElementById( 'builder-'+ name ).classList.toggle( 'admin-hidden', name !== level );
			} );
		},

		/**
		 *	The list again, from the server
		 *
		 *	@param		{Function}	[then]
		 *
		 *	@return		void
		 */
		_loadList : function( then ) {

			const builder = Nino.admin.builder;
			const stamp = builder._stamped( [ 'templates', 'routes' ] );

			builder._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null ) {
					// Asked for again the next time the panel is shown
					builder._started = builder._ready;
					return builder._showError( dc.getElementById('builder-templates'), status, response );
				}

				Object.assign( builder._seen, stamp );
				builder._files = response.templates || [];
				builder._renderList();

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	Back to the list, with what the document was dropped
		 *
		 *	@return		void
		 */
		_showList : function() {

			const builder = Nino.admin.builder;

			builder._doc = null;
			builder._watchKeys( false );
			builder._closeDialog();
			builder._showScreen( 'list' );
			Nino.admin.router.set( 'builder', [] );
			builder._loadList();
		},

		/**
		 *	The list: one row per page template, and what starts a new one
		 *
		 *	@return		void
		 */
		_renderList : function() {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-templates');
			const none = Nino.content.getText('/_admin/builder/label/none');

			mount.innerHTML = '';

			const create = builder._el( 'button', 'nino-admin-btn-primary', Nino.content.getText('/_admin/builder/label/new') );
			create.type = 'button';
			create.addEventListener( 'click', builder._newTemplate );

			if( builder._files.length === 0 ) {
				mount.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/builder/empty/headline') ) );
				mount.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/builder/empty/lead') ) );
				mount.appendChild( Nino.adminUi.listActions( [ create ] ) );
				return;
			}

			const table = builder._el( 'div' );
			mount.appendChild( table );

			Nino.adminUi.table( {
				mount		: table,
				rowKey	: 'file',
				columns	: [
					{ key : 'name', label : Nino.content.getText('/_admin/builder/label/name'), type : 'string', render : function( value, row ) { return builder._nameCell( row ) } },
					{ key : 'file', label : Nino.content.getText('/_admin/builder/label/file'), type : 'string' },
					{ key : 'sections', label : Nino.content.getText('/_admin/builder/label/sections'), type : 'integer' },
					{ key : 'header', label : Nino.content.getText('/_admin/builder/label/header'), type : 'string' },
					{ key : 'footer', label : Nino.content.getText('/_admin/builder/label/footer'), type : 'string' },
					{ key : 'usedBy', label : Nino.content.getText('/_admin/builder/label/used-by'), type : 'string' },
					{ key : 'actions', label : '', type : 'string', sortable : false, render : function( value, row ) { return builder._rowActions( row ) } },
				],
				rows		: builder._files.map( function( entry ) {
					return {
						file		: entry.file,
						name		: entry.name === '' ? entry.file : entry.name,
						header	: entry.header === '' ? none : entry.header,
						footer	: entry.footer === '' ? none : entry.footer,
						sections: entry.sections,
						usedBy	: builder._addresses( entry.usedBy ).join(', '),
						// A cell the table does not take for empty: the buttons stand in it
						actions	: entry.file,
						editable: entry.editable === true,
						// Why the Builder does not read the file completely, where it does not
						warning	: entry.readable === true ? '' : ( entry.reason === null || entry.reason === undefined ? Nino.content.getText('/_admin/builder/label/not-read') : builder._reasonText( entry.reason ) ),
					};
				} ),
				labels	: { search : Nino.content.getText('/_admin/builder/label/search'), empty : Nino.content.getText('/_admin/builder/empty/headline'), noMatch : Nino.content.getText('/_admin/builder/label/no-match') },
				onRowClick : function( row ) { if( row.editable === true ) builder._visit( row.file ) },
			} );

			mount.appendChild( Nino.adminUi.listActions( [ create ] ) );
		},

		/**
		 *	The name of a template in the list, and beside it a mark where the Builder
		 *	does not read the file completely, with the reason of the first block it
		 *	failed at for a tooltip
		 *
		 *	@param		{Object}		row
		 *
		 *	@return		{Element}
		 */
		_nameCell : function( row ) {

			const builder = Nino.admin.builder;
			const cell = builder._fragment( 'name' );
			const mark = builder._one( cell, '.builder-name-warning' );

			builder._one( cell, '.builder-name-text' ).textContent = row.name;
			mark.hidden = row.warning === '';
			mark.title = row.warning;
			mark.setAttribute( 'aria-label', row.warning );

			return cell;
		},

		/**
		 *	The buttons of one row: open, duplicate, delete
		 *
		 *	@param		{Object}		row
		 *
		 *	@return		{Element}
		 */
		_rowActions : function( row ) {

			const builder = Nino.admin.builder;
			const cell = builder._el( 'div', 'builder-row-actions' );

			const open = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText('/_admin/builder/label/open') );
			open.type = 'button';
			open.disabled = row.editable === false;
			open.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._visit( row.file ) } );

			const copy = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText('/_admin/builder/label/duplicate') );
			copy.type = 'button';
			copy.disabled = row.editable === false;
			copy.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._duplicateTemplate( row.file ) } );

			const remove = builder._el( 'button', 'nino-admin-btn-danger', Nino.content.getText('/_admin/builder/label/delete') );
			remove.type = 'button';
			remove.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._confirmDelete( row.file ) } );

			cell.appendChild( open );
			cell.appendChild( copy );
			cell.appendChild( remove );

			return cell;
		},

		/**
		 *	A copy of a template: asks for the name of the new one, has the server make
		 *	it - its file, its text keys and its image slots - and opens it
		 *
		 *	@param		{string}		file
		 *
		 *	@return		void
		 */
		_duplicateTemplate : function( file ) {

			const builder = Nino.admin.builder;
			const entry = builder._files.find( function( candidate ) { return candidate.file === file } );
			const draft = { name : Nino.adminUi.format( Nino.content.getText('/_admin/builder/label/copy-of'), entry === undefined || entry.name === '' ? file : entry.name ) };

			builder._dialog( {
				title		: Nino.content.getText('/_admin/builder/label/duplicate'),
				build		: function( body ) {
					body.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/duplicate'), file+ '.tpl' ) ) );
					body.appendChild( builder._textField( Nino.content.getText('/_admin/builder/label/name'), Nino.content.getText('/_admin/builder/hint/name'), draft.name, function( value ) { draft.name = value } ) );
				},
				actions	: [ { label : Nino.content.getText('/_admin/builder/label/duplicate'), kind : 'primary', onClick : function( problem ) {

					builder._apiCall( 'duplicate', { file : file, name : draft.name }, function( status, response ) {

						if( status !== 200 || response === null ) {
							// A copy that would take over keys or image slots says which, in the panel's own words
							problem( builder._errorText( status, response, '/_admin/builder/error/name' )+ builder._inTheWay( status, response ) );
							return;
						}

						// A copy is a file, and the keys and the slots that were made for it
						[ 'templates', 'texts', 'slots' ].forEach( function( topic ) { builder._touch( topic ) } );
						builder._closeDialog();
						builder._visit( response.model.file );
					} );

					return false;
				} } ],
			} );
		},

		/**
		 *	Open a template the way a person does: a step Back returns to
		 *
		 *	@param		{string}		file
		 *
		 *	@return		void
		 */
		_visit : function( file ) {
			Nino.admin.router.go( 'builder', [ file ] );
			Nino.admin.builder._openEditor( file );
		},

		/**
		 *	Ask, and delete a template that no route renders
		 *
		 *	@param		{string}		file
		 *
		 *	@return		void
		 */
		_confirmDelete : function( file ) {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-templates');

			Nino.adminUi.choiceDialog( {
				title		: Nino.content.getText('/_admin/builder/label/delete'),
				message	: Nino.content.getText('/_admin/builder/confirm/delete'),
				choices	: [
					{ value : 'delete', label : Nino.content.getText('/_admin/builder/label/delete'), kind : 'danger' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				],
				onChoose	: function( choice ) {

					if( choice !== 'delete' )
						return;

					builder._apiCall( 'delete', { file : file }, function( status, response ) {

						if( status !== 200 ) {
							// The params are the keys of the routes; the list shows their addresses, and so does this
							const routes = status === 409 && response !== null && Array.isArray( response.params ) === true ? ' '+ builder._addresses( response.params.map( function( route ) { return { route : route } } ) ).join(', ') : '';
							const line = builder._el( 'p', 'nino-admin-error', builder._errorText( status, response, '/_admin/builder/error/load' )+ routes );
							mount.insertBefore( line, mount.firstChild );
							return;
						}

						builder._touch('templates');
						builder._loadList( function() { mount.insertBefore( Nino.adminUi.notice( Nino.content.getText('/_admin/builder/msg/deleted') ), mount.firstChild ) } );
					} );
				},
			} );
		},

		/**
		 *	A new template: its name, and the frames above and below it
		 *
		 *	@return		void
		 */
		_newTemplate : function() {

			const builder = Nino.admin.builder;

			builder._withRegistry( function( registry ) {

				const draft = { name : '', header : registry.headers.indexOf('html-header') === -1 ? '' : 'html-header', footer : registry.footers.indexOf('html-footer') === -1 ? '' : 'html-footer' };

				builder._dialog( {
					title		: Nino.content.getText('/_admin/builder/label/new'),
					build		: function( body ) {
						body.appendChild( builder._textField( Nino.content.getText('/_admin/builder/label/name'), Nino.content.getText('/_admin/builder/hint/name'), draft.name, function( value ) { draft.name = value } ) );
						body.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/header'), registry.headers, draft.header, function( value ) { draft.header = value } ) );
						body.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/footer'), registry.footers, draft.footer, function( value ) { draft.footer = value } ) );
					},
					actions	: [ { label : Nino.content.getText('/_admin/builder/label/create'), kind : 'primary', onClick : function( problem ) {

						builder._apiCall( 'create', draft, function( status, response ) {

							if( status !== 200 || response === null ) {
								problem( builder._errorText( status, response, '/_admin/builder/error/name' ) );
								return;
							}

							builder._touch('templates');
							builder._closeDialog();
							builder._visit( response.model.file );
						} );

						return false;
					} } ],
				} );
			} );
		},

		// ------------------------------------------------------------ The editor

		/**
		 *	Wire what the template draws once: the buttons of the views, the choice of
		 *	the language, the bar, the back link, the buttons below the preview, the
		 *	frame the pointer is over and the close of the dialogs
		 *
		 *	@return		void
		 */
		_bind : function() {

			const builder = Nino.admin.builder;

			if( builder._bound === true )
				return;

			builder._bound = true;

			const back = dc.getElementById('builder-back');
			back.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				Nino.admin.router.go( 'builder', [] );
				builder._showList();
			} );

			const buttons = {};
			builder.VIEWS.forEach( function( viewport ) { buttons[viewport] = dc.getElementById( 'builder-viewport-'+ viewport ) } );
			builder._selectView = Nino.adminUi.buttonRow( buttons, builder._viewport, function( viewport ) { builder._setView( viewport ) } );

			dc.getElementById('builder-locale').addEventListener( 'change', function( ev ) { builder._setLocale( ev.target.value ) } );

			builder._status = Nino.adminUi.status( dc.getElementById('builder-status') );

			dc.getElementById('builder-save').addEventListener( 'click', function() { builder._save() } );
			dc.getElementById('builder-source').addEventListener( 'click', builder._showSource );
			dc.getElementById('builder-add-content').addEventListener( 'click', function() { builder._addSection( 'content' ) } );
			dc.getElementById('builder-add-tiles').addEventListener( 'click', function() { builder._addSection( 'tiles' ) } );
			dc.getElementById('builder-add-html').addEventListener( 'click', function() { builder._editBlock( null ) } );
			dc.getElementById('builder-template-settings').addEventListener( 'click', function() { builder._select( [] ); builder._openSettings( [] ) } );

			// Where the pointer is over a frame its tools are seen - the innermost frame's
			const preview = dc.getElementById('builder-preview');
			preview.addEventListener( 'mouseover', function( ev ) { builder._hover( ev.target.closest('.builder-pick') ) } );
			preview.addEventListener( 'mouseleave', function() { builder._hover( null ) } );

			dc.getElementById('builder-dialog-close').addEventListener( 'click', function() { builder._closeDialog() } );
			dc.getElementById('builder-dialog').addEventListener( 'close', builder._dialogClosed );
			dc.getElementById('builder-picker-close').addEventListener( 'click', builder._closePicker );
		},

		/**
		 *	Show the preview in another view, which is a viewport or all of them at
		 *	once: the buttons follow, and so does the preview. The tables of a dialog
		 *	that is open show the rows of the viewport, and are drawn again by whoever
		 *	changes it from there
		 *
		 *	@param		{string}		viewport					s, m, l or g
		 *
		 *	@return		void
		 */
		_setView : function( viewport ) {

			const builder = Nino.admin.builder;

			builder._viewport = viewport;
			builder._selectView( viewport );
			builder._renderPreview();
		},

		/**
		 *	Open a template in the editor: its model with the hash of the file, the
		 *	registry the forms are built from, and the routes that use it
		 *
		 *	@param		{string}		file
		 *
		 *	@return		void
		 */
		_openEditor : function( file ) {

			const builder = Nino.admin.builder;
			const token = ++builder._token;
			const answers = {};
			const stamp = builder._stamped( [ 'templates', 'routes', 'elements', 'slots' ] );
			let pending = 3;
			let failure = null;

			builder._closeDialog();
			builder._showScreen( 'editor' );

			const arrived = function( name, status, response ) {

				if( token !== builder._token )
					return;

				if( status !== 200 || response === null )
					failure = failure ?? { status : status, response : response };

				answers[name] = response;

				if( --pending > 0 )
					return;

				if( failure !== null ) {
					builder._doc = null;
					builder._watchKeys( false );
					builder._showScreen( 'list' );
					Nino.admin.router.set( 'builder', [] );
					builder._listError( failure.status, failure.response );
					return;
				}

				const model = builder._normalise( answers.load.model );
				const entry = ( answers.list.templates || [] ).find( function( candidate ) { return candidate.file === file } );

				Object.assign( builder._seen, stamp );
				builder._registry = answers.registry;
				builder._locale = builder._nativeLocale( answers.registry );
				builder._doc = { file : file, hash : answers.load.hash, model : model, saved : JSON.stringify( model ), usedBy : entry === undefined ? [] : entry.usedBy };
				builder._sel = [];
				builder._fresh = {};
				builder._problems = [];
				builder._saving = false;

				// What was copied, here or on another page, before the editor was opened. Where the storage holds none - or cannot be asked - the clip of
				// this session, which a storage that does not write could not keep, stays
				const stored = builder._storedClip();

				if( stored !== undefined && stored !== null )
					builder._clip = stored;

				builder._renderEditor();
				builder._watchKeys( true );
				builder._keysAsked = false;
				builder._loadKeys();
				Nino.admin.router.set( 'builder', [ file ] );
			};

			builder._apiCall( 'load', { file : file }, function( status, response ) { arrived( 'load', status, response ) } );
			builder._apiCall( 'registry', {}, function( status, response ) { arrived( 'registry', status, response ) } );
			builder._apiCall( 'list', {}, function( status, response ) { arrived( 'list', status, response ) } );
		},

		// The answer of a request that was asked for before the last one is let fall away
		_token : 0,

		/**
		 *	The registry, for a dialog of the list screen that opens without an
		 *	editor behind it
		 *
		 *	@param		{Function}	then					Called with the registry
		 *
		 *	@return		void
		 */
		_withRegistry : function( then ) {

			const builder = Nino.admin.builder;

			if( builder._registry !== null )
				return then( builder._registry );

			builder._apiCall( 'registry', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return builder._listError( status, response );

				builder._registry = response;
				then( response );
			} );
		},

		/**
		 *	The text keys of the project, for the source field, for the texts of the
		 *	components - their values, and the first line of them in the preview - and
		 *	for a copy that has to know the text of the key it takes over. An account
		 *	that may not read them gets none and types a key instead. They are asked
		 *	for when a document is opened and after it is saved, and what was there
		 *	stays until the answer is: the preview is drawn again with it
		 *
		 *	@return		void
		 */
		_loadKeys : function() {

			const builder = Nino.admin.builder;

			if( builder._keysAsked === true )
				return;

			const stamp = builder._stamped( [ 'texts' ] );

			builder._keysAsked = true;
			builder._keys = builder._keys ?? [];

			builder._panelCall( 'text/keys', {}, function( status, response ) {

				builder._keysAnswered = true;
				Object.assign( builder._seen, stamp );

				if( status === 200 && response !== null && Array.isArray( response.keys ) === true ) {

					builder._keys = response.keys;
					builder._keysRead = true;
					builder._pages = Array.isArray( response.pages ) === true ? response.pages.filter( function( page ) {
						return page !== null && typeof page === 'object' && typeof page.uri === 'string' && page.uri !== '';
					} ) : [];
				}

				if( builder._doc !== null )
					builder._redraw();
			} );
		},

		/**
		 *	The text a key has now in each language it has one in, for a copy that has to give each language the text of the
		 *	original - and none where the original has none. A key every language shares has one text, and the copy has the
		 *	same in each language of the site: the key of a copy is one of its own languages
		 *
		 *	@param		{string}		key
		 *
		 *	@return		{Object}									locale => text, none that is empty; nothing for a key that is not known
		 */
		_valuesOf : function( key ) {

			const builder = Nino.admin.builder;
			const entry = ( builder._keys || [] ).find( function( candidate ) { return candidate.key === key } );
			const held = {};

			if( entry === undefined )
				return held;

			if( entry.global === true ) {

				if( typeof entry.values['*'] === 'string' && entry.values['*'] !== '' )
					( builder._registry.locales || [] ).forEach( function( language ) { held[language.locale] = entry.values['*'] } );

				return held;
			}

			Object.keys( entry.values || {} ).forEach( function( locale ) {
				if( typeof entry.values[locale] === 'string' && entry.values[locale] !== '' )
					held[locale] = entry.values[locale];
			} );

			return held;
		},

		/**
		 *	Draw the whole editor from the document
		 *
		 *	@return		void
		 */
		_renderEditor : function() {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			dc.getElementById('builder-editor-title').textContent = doc.model.name === '' ? doc.file : doc.model.name;
			dc.getElementById('builder-editor-file').textContent = doc.file+ '.tpl';

			builder._showScreen( 'editor' );
			builder._drawLocales();
			builder._repaint();
			builder._status.idle( '' );
		},

		/**
		 *	The preview from the model, and the line in the bar
		 *
		 *	@return		void
		 */
		_repaint : function() {

			const builder = Nino.admin.builder;

			if( builder._doc === null )
				return;

			builder._renderPreview();
			builder._renderProblems();
			builder._markState();
		},

		/**
		 *	The model changed: what is drawn from it follows, and so does whether
		 *	there is something to save
		 *
		 *	@return		void
		 */
		_changed : function() {

			const builder = Nino.admin.builder;

			builder._problems = [];
			builder._repaint();
			builder._saySummaries();

			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
		},

		/**
		 *	The line in the bar: unsaved changes while there are some, and the
		 *	Save button, which is off while a save runs and where the editor is for
		 *	reading only
		 *
		 *	@return		void
		 */
		_markState : function() {

			const builder = Nino.admin.builder;
			const unsaved = builder._unsaved( builder._doc );
			const state = builder._status.state;

			dc.getElementById('builder-save').disabled = builder._saving === true || builder._mutable() === false;

			if( builder._saving === true )
				return;

			if( unsaved === true && state !== 'dirty' )
				builder._status.dirty();
			else if( unsaved === false && state === 'dirty' )
				builder._status.idle( '' );
		},

		/**
		 *	What a refused save said, at the blocks it names: the frames of those
		 *	blocks carry the sentence, and the sentences no block owns are listed
		 *	over the preview
		 *
		 *	@return		void
		 */
		_renderProblems : function() {

			const builder = Nino.admin.builder;
			const list = dc.getElementById('builder-problems');
			const blame = builder._blame( builder._doc.model, builder._problems );

			list.innerHTML = '';
			list.hidden = blame.general.length === 0;

			blame.general.forEach( function( sentence ) { list.appendChild( builder._el( 'li', '', sentence ) ) } );

			Object.keys( blame.blocks ).forEach( function( index ) {
				dc.querySelectorAll( '[data-path="'+ index+ '"]' ).forEach( function( el ) {
					el.classList.add('has-problem');
					el.title = blame.blocks[index].join( '\n' );
				} );
			} );
		},

		// ------------------------------------------------------------ Selection

		/**
		 *	Select a node: its frame in the preview
		 *
		 *	@param		{Array}			path
		 *	@param		{boolean}		[scroll]					False for a selection by a click on what is in view already - which must not move the page; every other way
		 *															to select (a step, a copy, a new node, a refused save) brings the frame into view
		 *
		 *	@return		void
		 */
		_select : function( path, scroll ) {

			const builder = Nino.admin.builder;

			builder._sel = path;
			builder._paintSelection( scroll !== false );
		},

		/**
		 *	Mark the selected frame, and bring it into view
		 *
		 *	@param		{boolean}		scroll
		 *
		 *	@return		void
		 */
		_paintSelection : function( scroll ) {

			const builder = Nino.admin.builder;
			const key = builder._sel.join('.');

			dc.querySelectorAll('#builder-preview [data-path]').forEach( function( el ) {

				const on = el.dataset.path === key;

				el.classList.toggle( 'is-selected', on );

				if( on === true && scroll === true && typeof el.scrollIntoView === 'function' )
					el.scrollIntoView( { block : 'nearest' } );
			} );

			const link = dc.getElementById('builder-open-page');

			if( builder._doc !== null )
				builder._pageLink( link );
		},

		/**
		 *	The addresses a template is served at: what the key of each route that
		 *	answers a GET says (GET://contact is /contact), each once. A route's own
		 *	uri is the webpage's, which is not where the page is
		 *
		 *	@param		{Array}			usedBy						[ { route, uri } ]
		 *
		 *	@return		{Array}
		 */
		_addresses : function( usedBy ) {

			const found = [];

			( usedBy || [] ).forEach( function( entry ) {

				const address = /^GET:\/(\/.*)$/.exec( String( entry.route ?? '' ) );

				if( address !== null && found.indexOf( address[1] ) === -1 )
					found.push( address[1] );
			} );

			return found;
		},

		/**
		 *	The link to the real page: the first route that uses the template, with
		 *	the anchor of the section the selection is in
		 *
		 *	@param		{Element}		link
		 *
		 *	@return		void
		 */
		_pageLink : function( link ) {

			const builder = Nino.admin.builder;
			const address = builder._addresses( builder._doc.usedBy )[0];
			const block = builder._sel.length > 0 ? builder._doc.model.blocks[builder._sel[0]] : undefined;

			// A path of the site: nothing else is made into a link
			const usable = address !== undefined && /^\/(?!\/)/.test( address );

			link.hidden = usable === false;

			if( usable === true )
				link.href = dc.getElementById('builder-root').dataset.dir+ address+ ( block !== undefined && block.kind === 'section' ? '#'+ block.id : '' );
		},

		// ------------------------------------------------------------ The preview

		/**
		 *	The preview: drawn from the model with the placeholders of the template
		 *	and never from content. Click selects, double click opens the form. Every
		 *	frame has a head of three parts - its title, what is said of it, its tools -
		 *	and every level ends in a button that adds to it
		 *
		 *	@return		void
		 */
		_renderPreview : function() {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-preview');
			const model = builder._doc.model;
			const plan = builder._preview( model, builder._registry, builder._viewport, { locale : builder._locale, keys : builder._keys } );

			mount.innerHTML = '';
			mount.dataset.viewport = builder._viewport;
			builder._hovered = null;

			plan.forEach( function( block ) {

				const frame = builder._fragment( 'frame' );
				const body = builder._one( frame, '.builder-frame-body' );
				const head = builder._one( frame, '.builder-frame-head' );
				const name = builder._one( frame, '.builder-frame-name' );
				const status = builder._one( frame, '.builder-head-status' );

				frame.dataset.path = block.path.join('.');
				frame.classList.add( block.kind === 'section' ? 'builder-color-'+ ( block.color === '' ? 'plain' : block.color ) : 'is-html' );

				// The background inside is the row's: the area of the row is tinted, and not the whole frame
				if( block.rowColor !== '' )
					body.classList.add( 'builder-color-'+ block.rowColor );

				// The title: a section has its name, which is changed where it stands, and a section of tiles says what it is after it - and is drawn edge to edge, its tiles in its ratio
				name.textContent = block.kind === 'section' ? block.id : 'HTML+';

				if( block.kind === 'section' )
					name.parentNode.appendChild( builder._renamer( name, block.path, builder._barSays ) );

				if( block.tiles === true ) {
					frame.classList.add('is-tiles');
					name.parentNode.appendChild( builder._el( 'span', 'builder-frame-kind', Nino.content.getText('/_admin/builder/tree/tiles') ) );

					if( block.ratio !== '' )
						frame.style.setProperty( '--builder-ratio', block.ratio.replace( '-', ' / ' ) );
				}

				// What is said of the frame
				if( block.background === true )
					status.appendChild( builder._statusItem( 'background', 'image', Nino.content.getText( block.inner === true ? '/_admin/builder/preview/row-background' : '/_admin/builder/preview/background' ), '' ) );

				if( block.inner === true )
					status.appendChild( builder._statusItem( 'inner', 'inner', Nino.content.getText('/_admin/builder/preview/inner'), '' ) );

				// The canvas keeps the markup order, and draws a row as a plain flex row: in the view of one viewport the row is turned round where that viewport is, and in
				// every view the head names the viewports that are
				frame.classList.toggle( 'is-reversed', block.reversed === true );

				if( Array.isArray( block.reversedIn ) === true && block.reversedIn.length > 0 ) {

					const said = Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/reverse-said'), block.reversedIn.map( function( viewport ) { return Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) } ).join(', ') );

					status.appendChild( builder._statusItem( 'reverse', 'arrow-left-right', said, said ) );
				}

				if( block.padded === true )
					status.appendChild( builder._statusItem( 'padded', 'frame', Nino.content.getText('/_admin/builder/preview/padded'), '' ) );

				if( block.whitespace !== '' )
					status.appendChild( builder._statusItem( 'whitespace', 'move-vertical', Nino.adminUi.format( Nino.content.getText('/_admin/builder/preview/whitespace'), builder._optionLabel( 'whitespace', block.whitespace ) ), '' ) );

				// A section that makes a section of every element says so as a column that runs a loop does: it is a button, which opens the block Loop of the section
				if( block.loop !== null ) {

					const says = Nino.content.getText('/_admin/builder/stack/sections')+ ' · '+ block.loop.type;

					status.appendChild( builder._statusItem( 'loop', 'loop', Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/loop'), says ), says, function() {

						if( builder._mutable() === false )
							return;

						builder._select( block.path, false );
						builder._sectionDialog( block.path, builder._blockOf( 'section', 'stack' ) );
					} ) );
				}

				if( block.reason !== null )
					status.appendChild( builder._statusItem( 'warning', 'warning', builder._reasonText( block.reason ), '' ) );

				builder._pick( frame, block.path );
				head.appendChild( builder._tools( block.path, block.kind === 'section' ? Nino.content.getText('/_admin/builder/tree/section')+ ' '+ block.id+ ( block.tiles === true ? ', '+ Nino.content.getText('/_admin/builder/tree/tiles') : '' ) : Nino.content.getText('/_admin/builder/tree/html-block') ) );

				if( block.kind === 'html' )
					body.appendChild( builder._placeholder( { path : block.path, label : 'HTML+', image : 'block' }, 'none' ) );

				block.cols.forEach( function( col ) { body.appendChild( builder._previewCol( col ) ) } );

				// The end of the row: a column more
				if( block.kind === 'section' )
					body.appendChild( builder._addButton( block.tiles === true ? 'tile' : 'col', function() { builder._addCol( block.path ) }, 'builder-add-col' ) );

				mount.appendChild( frame );
			} );

			builder._paintAddBar();
			builder._markRed( mount, builder._red( model, builder._registry ) );
			builder._paintSelection( false );
		},

		/**
		 *	The paste button at the end of the add bar of the page, made new with the preview: it puts what was copied at the end of the page, and
		 *	where that cannot be done it is off and says why
		 *
		 *	@return		void
		 */
		_paintAddBar : function() {

			const builder = Nino.admin.builder;
			const bar = dc.getElementById('builder-add-bar');

			if( bar === null )
				return;

			bar.querySelectorAll('.builder-add-paste').forEach( function( old ) { old.remove() } );

			const button = builder._addButton( 'paste', function() { builder._pasteAt( [] ) }, 'builder-add-paste' );

			builder._pasteTitle( button, [] );
			button.disabled = builder._mutable() === false || builder._pasteable( builder._doc.model, builder._registry, [], builder._clip ) !== '';
			bar.appendChild( button );
		},

		/**
		 *	Mark the frames whose source means nothing where it stands
		 *
		 *	@param		{Element}		mount
		 *	@param		{Array}			red								As _red() answers
		 *
		 *	@return		void
		 */
		_markRed : function( mount, red ) {

			red.forEach( function( entry ) {

				const el = mount.querySelector( '[data-path="'+ entry.path.join('.')+ '"]' );

				if( el === null )
					return;

				el.classList.add('is-red');
				el.title = Nino.content.getText( '/_admin/builder/tree/red-'+ entry.why );
			} );
		},

		/**
		 *	A click selects what the element stands for, a double click opens its form
		 *
		 *	@param		{Element}		el
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_pick : function( el, path ) {

			const builder = Nino.admin.builder;

			// The browser selects the word under the pointer on the second mousedown of a double click; prevented there,
			// the dblclick still fires and the dialog opens
			el.addEventListener( 'mousedown', function( ev ) {
				if( ev.detail > 1 )
					ev.preventDefault();
			} );
			el.addEventListener( 'click', function( ev ) {
				if( ev.target.closest('.builder-pick') === el )
					builder._select( path, false );
			} );
			el.addEventListener( 'dblclick', function( ev ) {
				if( ev.target.closest('.builder-pick') === el )
					builder._openSettings( path );
			} );
			el.classList.add('builder-pick');
		},

		/**
		 *	The frame the pointer is over is the innermost one that can be picked:
		 *	its tools are seen, those of the frames around it are not
		 *
		 *	@param		{Element|null}	el
		 *
		 *	@return		void
		 */
		_hover : function( el ) {

			const builder = Nino.admin.builder;

			if( builder._hovered === el )
				return;

			if( builder._hovered !== null )
				builder._hovered.classList.remove('is-hover');

			builder._hovered = el;

			if( el !== null )
				el.classList.add('is-hover');
		},

		/**
		 *	The tools of a frame: settings, up, down, duplicate, copy, paste, delete - and for a
		 *	section to edit it as HTML+. A tool the frame cannot use now is off - the one that
		 *	pastes says why, as its title. The
		 *	toolbar is named by what its tools are of - "Tools: Image" -, for there are
		 *	as many buttons that read Settings, Duplicate or Delete on a page as it has
		 *	frames, and a screen reader hears nothing that tells them apart
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		[name]						What the frame is called: a component by its label, a section by its id, a column by its place
		 *
		 *	@return		{Element}
		 */
		_tools : function( path, name ) {

			const builder = Nino.admin.builder;
			const tools = builder._fragment( 'tools' );
			const offered = builder._toolbar( builder._doc.model, path );

			if( typeof name === 'string' && name !== '' )
				tools.setAttribute( 'aria-label', Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/tools-of'), name ) );
			const run = {
				settings	: function() { builder._select( path, false ); builder._openSettings( path ) },
				html			: function() { builder._editBlock( path ) },
				up				: function() { builder._stepNode( path, -1 ) },
				down			: function() { builder._stepNode( path, 1 ) },
				duplicate	: function() { builder._duplicateAt( path ) },
				copy			: function() { builder._copyAt( path ) },
				paste			: function() { builder._pasteAt( path ) },
				delete		: function() { builder._deleteAt( path ) },
			};

			Object.keys( run ).forEach( function( name ) {

				const button = builder._one( tools, '[data-tool="'+ name+ '"]' );

				button.hidden = offered[name] === null;
				// Copy and paste are for an editor that may change the document: where it is for reading, both are off
				button.disabled = offered[name] === false || ( ( name === 'copy' || name === 'paste' ) && builder._mutable() === false );
				button.addEventListener( 'click', function( ev ) {
					ev.stopPropagation();
					run[name]();
				} );
			} );

			builder._pasteTitle( builder._one( tools, '[data-tool="paste"]' ), path );

			return tools;
		},

		/**
		 *	The words of a button that pastes: where the clip can be pasted, its name says what is pasted (Paste: Section hero) and so does its title;
		 *	where it cannot, the name stays the word of the tool and the reason is its title and its description, for whoever does not see the title.
		 *	Where the editor is for reading only the tool has its plain word, as it does nothing. A tool that is an icon has its name as its label; the
		 *	button of the add bar has its word for a name
		 *
		 *	@param		{Element}		button
		 *	@param		{Array}			path							The node the tool is of, [] for the end of the page
		 *
		 *	@return		void
		 */
		_pasteTitle : function( button, path ) {

			const builder = Nino.admin.builder;
			let name = Nino.content.getText('/_admin/builder/menu/paste');
			let title = name;
			let reason = '';

			if( builder._mutable() === true ) {

				const problem = builder._pasteable( builder._doc.model, builder._registry, path, builder._clip );

				if( problem === '' ) {
					name = Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/paste-of'), builder._clipWord( builder._clip ) );
					title = name;
				} else {
					title = problem;
					reason = problem;
				}
			}

			button.title = title;

			if( button.hasAttribute('aria-label') === true )
				button.setAttribute( 'aria-label', name );

			if( reason !== '' )
				button.setAttribute( 'aria-description', reason );
			else
				button.removeAttribute('aria-description');
		},

		/**
		 *	The button at the end of a level that adds to it
		 *
		 *	@param		{string}		what							section, col, tile, component, stack or html-block - the part of its words under tree/ - or paste, which
		 *															says the word of the tool and is no plus: it puts what was copied
		 *	@param		{Function}	onClick						Called with the button
		 *	@param		{string}		[className]
		 *
		 *	@return		{Element}
		 */
		_addButton : function( what, onClick, className ) {

			const builder = Nino.admin.builder;
			const button = builder._fragment( 'add' );

			button.textContent = what === 'paste' ? Nino.content.getText('/_admin/builder/menu/paste') : '+ '+ Nino.content.getText( '/_admin/builder/tree/'+ what );
			button.disabled = builder._mutable() === false;

			if( className !== undefined )
				button.classList.add( className );

			button.addEventListener( 'click', function( ev ) {
				ev.stopPropagation();
				onClick( button );
			} );

			return button;
		},

		/**
		 *	One column of the preview, at the width and the visibility of the
		 *	viewport, in the ground it paints. Its head has the width for a title;
		 *	what it says of the column is a picture behind it, the loop it runs - a
		 *	button, which opens the block Loop -, an alignment that is not the default
		 *	- the icon of the choice, a button that opens the Layout tab - and that it
		 *	is hidden: where the view is one viewport, in that one, else in which
		 *	ones. A column with a loop has the components of its cell in it, as one
		 *	without has its own; one that holds components is a frame of its own in it
		 *	(see _previewCard())
		 *
		 *	@param		{Object}		col						An entry of _preview()
		 *
		 *	@return		{Element}
		 */
		_previewCol : function( col ) {

			const builder = Nino.admin.builder;
			const frame = builder._fragment( 'col' );
			const body = builder._one( frame, '.builder-col-body' );
			const status = builder._one( frame, '.builder-head-status' );
			const all = builder._viewport === 'g';

			frame.dataset.path = col.path.join('.');
			frame.style.setProperty( '--builder-w', String( col.width ) );
			frame.classList.toggle( 'is-hidden', col.hidden );
			builder._one( frame, '.builder-col-name' ).textContent = col.width+ '%';
			builder._pick( frame, col.path );

			// A column that paints a ground is tinted as the section is
			if( col.color !== '' )
				frame.classList.add( 'builder-color-'+ col.color );

			if( col.background === true )
				status.appendChild( builder._statusItem( 'background', 'image', col.backgroundField === ''
					? Nino.content.getText('/_admin/builder/preview/column-background')
					: Nino.adminUi.format( Nino.content.getText('/_admin/builder/preview/column-background-field'), col.backgroundField ), '' ) );

			// The picture of a field is the element's own, and there is none to draw: a placeholder that names the field stands behind what the column holds, as the picture does on the page
			if( col.backgroundField !== '' ) {

				const behind = builder._placeholder( { path : col.path, label : col.backgroundField, image : 'image', line : '', picture : '', draws : '' }, 'none' );

				behind.classList.add('builder-col-bg');
				behind.removeAttribute('data-path');
				// A layer behind what the column holds, in the frame and not in the flow of its body
				frame.insertBefore( behind, frame.firstChild );
			}

			// The title's link covers the whole of the tile
			if( col.linked === true )
				status.appendChild( builder._statusItem( 'linked', 'link', Nino.content.getText('/_admin/builder/preview/linked'), '' ) );

			if( col.stack !== null ) {

				// What the head says of the loop is in the title as well: in a narrow column the text is cut off, the title is not
				const says = col.stack.label+ ' · '+ col.stack.type;
				const loop = builder._statusItem( 'loop', 'loop', Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/loop'), says ), says, function() {
					builder._select( col.stack.path, false );
					builder._openSettings( col.stack.path );
				} );

				// The type of the loop is red when the project has none like it, and says so here
				loop.dataset.path = col.stack.path.join('.');
				status.appendChild( loop );
			}

			// An alignment that is not the default is the icon of the choice, as the dialog has it, in every view - it is no property of a viewport
			[ { key : 'stackAlign', group : 'stack-align', words : 'align-stack' }, { key : 'text', group : 'text', words : 'align-text' } ].forEach( function( how ) {

				const icon = builder.ALIGN_ICONS[how.key][col[how.key]];

				if( col[how.key] === '' || icon === undefined )
					return;

				status.appendChild( builder._statusItem( 'align', icon, Nino.adminUi.format( Nino.content.getText( '/_admin/builder/preview/'+ how.words ), builder._optionLabel( how.group, col[how.key] ) ), '', function() {
					builder._select( col.path, false );
					builder._openSettings( col.path, builder._blockOf( 'column', how.key ) );
				} ) );
			} );

			if( all === true ? col.hiddenIn.length > 0 : col.hidden === true )
				status.appendChild( builder._statusItem( 'hidden', 'hidden', all === true
					? Nino.adminUi.format( Nino.content.getText('/_admin/builder/preview/hidden-in'), col.hiddenIn.map( function( viewport ) { return Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) } ).join(', ') )
					: Nino.content.getText('/_admin/builder/preview/hidden'), '' ) );

			builder._one( frame, '.builder-pcol-head' ).appendChild( builder._tools( col.path, Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/tools-col'), col.path[1] + 1, builder._doc.model.blocks[col.path[0]].id ) ) );

			if( col.table !== null || col.rows !== null ) {

				// The table or the rows the loop makes: one picture of them, which opens the form of the loop
				const drawing = col.table !== null ? builder._tablePlaceholder( col.table ) : builder._rowsPlaceholder( col.rows );

				drawing.dataset.path = col.stack.path.join('.');
				drawing.addEventListener( 'click', function() {
					builder._select( col.stack.path, false );
					builder._openSettings( col.stack.path );
				} );
				body.appendChild( drawing );

				return frame;
			}

			col.components.forEach( function( component ) { body.appendChild( component.holds === true ? builder._previewCard( component ) : builder._placeholder( component, 'tools' ) ) } );
			body.appendChild( builder._addsRow( col.path ) );

			return frame;
		},

		/**
		 *	The table a loop makes, in the preview: the titles of its columns for a head - none where no column has one - and two rows of what its cells show, a field by its
		 *	name and a line of text as it is written
		 *
		 *	@param		{Array}			columns						[ { title, value } ]
		 *
		 *	@return		{Element}
		 */
		_tablePlaceholder : function( columns ) {

			const builder = Nino.admin.builder;
			const wrap = builder._el( 'div', 'builder-table-ph' );
			const table = builder._el( 'table', 'builder-table-ph-table' );
			const body = builder._el( 'tbody' );

			if( columns.some( function( column ) { return column.title !== '' } ) === true ) {

				const head = builder._el( 'thead' );
				const line = builder._el( 'tr' );

				columns.forEach( function( column ) { line.appendChild( builder._el( 'th', column.red === true ? 'is-red' : '', column.title ) ) } );
				head.appendChild( line );
				table.appendChild( head );
			}

			[ 0, 1 ].forEach( function() {

				const line = builder._el( 'tr' );

				columns.forEach( function( column ) { line.appendChild( builder._el( 'td', column.red === true ? 'is-red' : '', column.value ) ) } );
				body.appendChild( line );
			} );

			table.appendChild( body );
			wrap.appendChild( table );

			return wrap;
		},

		/**
		 *	The rows a list makes, in the preview: three bullets and the line of each
		 *
		 *	@param		{Array}			rows							The lines
		 *
		 *	@return		{Element}
		 */
		_rowsPlaceholder : function( rows ) {

			const builder = Nino.admin.builder;
			const list = builder._el( 'div', 'builder-rows-ph' );

			rows.forEach( function( line ) { list.appendChild( builder._el( 'div', 'builder-rows-ph-row', '\u2022  '+ line ) ) } );

			return list;
		},

		/**
		 *	A component that holds components, in the preview: a frame of its own in the body of
		 *	its column, lighter than the frame of a column. Its head has the label of the component,
		 *	that it has a picture behind it where it has, and its tools; its body has its children
		 *	as placeholders and a button that adds one. It is tinted in the ground it paints, as a
		 *	column is
		 *
		 *	@param		{Object}		card							An entry of _preview() that holds components
		 *
		 *	@return		{Element}
		 */
		_previewCard : function( card ) {

			const builder = Nino.admin.builder;
			const frame = builder._fragment( 'card' );
			const body = builder._one( frame, '.builder-card-body' );

			frame.dataset.path = card.path.join('.');
			builder._one( frame, '.builder-card-name' ).textContent = card.label;
			builder._pick( frame, card.path );

			if( card.color !== '' )
				frame.classList.add( 'builder-color-'+ card.color );

			if( card.background === true )
				builder._one( frame, '.builder-head-status' ).appendChild( builder._statusItem( 'background', 'image', Nino.content.getText('/_admin/builder/preview/card-background'), '' ) );

			builder._one( frame, '.builder-card-head' ).appendChild( builder._tools( card.path, card.label ) );

			card.children.forEach( function( child ) { body.appendChild( builder._placeholder( child, 'tools' ) ) } );
			body.appendChild( builder._addsRow( card.path ) );

			return frame;
		},

		/**
		 *	The button at the end of a column: a component, which goes in the column
		 *	and - where it runs a loop - in the cell of the loop; at the end of a
		 *	component that holds components it is a child
		 *
		 *	@param		{Array}			path							The column, or the component that holds components
		 *
		 *	@return		{Element}
		 */
		_addsRow : function( path ) {

			const builder = Nino.admin.builder;
			const row = builder._el( 'div', 'builder-adds' );

			row.appendChild( builder._addButton( 'component', function( button ) { builder._pickComponent( path, button ) } ) );

			return row;
		},

		/**
		 *	A placeholder: the image the registry names for a component and its label -
		 *	or, where the component has a text, that stands in the place of the label, and
		 *	where its slot has a picture, the picture in the place of the image. What it
		 *	is then is told by its title, the label. A component that says what it draws
		 *	has it under the label, as a line of its own: the tag and the class. The
		 *	stylesheet grades the label by what the component draws and never by its name:
		 *	the image of the registry is data-image, and the tag the component draws - the
		 *	first word of its draws, in lower case, where that is a tag name - data-tag
		 *
		 *	@param		{Object}		component			{ path, label, image, line, picture, draws }
		 *	@param		{string}		how						tools: a frame of its own, with its tools; none: a frame that has
		 *															its tools elsewhere
		 *
		 *	@return		{Element}
		 */
		_placeholder : function( component, how ) {

			const builder = Nino.admin.builder;
			const ph = builder._fragment( 'ph' );
			const line = component.line ?? '';
			const picture = component.picture ?? '';
			const draws = component.draws ?? '';
			const second = builder._one( ph, '.builder-ph-draws' );

			// The first word of what it draws is the tag, where it is one: ' .nino-card' (a tag left empty) and a word of something else say none
			const tag = draws.trim().split( /\s+/ )[0].toLowerCase();

			builder._one( ph, '.builder-ph-label' ).textContent = line === '' ? component.label : line;
			builder._one( ph, 'use' ).setAttribute( 'href', '#builder-ph-'+ component.image );
			ph.dataset.path = component.path.join('.');
			ph.dataset.image = component.image;

			if( /^[a-z][a-z0-9]*$/.test( tag ) === true )
				ph.dataset.tag = tag;

			second.textContent = draws;
			second.hidden = draws === '';

			if( line !== '' || picture !== '' )
				ph.title = component.label;

			if( line !== '' )
				ph.classList.add('has-line');

			if( draws !== '' )
				ph.classList.add('has-draws');

			if( picture !== '' ) {
				builder._one( ph, '.builder-ph-picture' ).src = picture;
				ph.classList.add('has-picture');
			}

			builder._pick( ph, component.path );

			// A component with an address to go to is a link: a small icon says so, where the preview cannot draw an anchor
			if( component.linked === true ) {
				ph.classList.add('is-linked');
				ph.appendChild( builder._statusItem( 'linked', 'link', Nino.content.getText('/_admin/builder/preview/linked'), '' ) );
			}

			if( how === 'tools' )
				ph.appendChild( builder._tools( component.path, component.label ) );

			return ph;
		},

		/**
		 *	One thing said of a frame, in the middle of its head: an icon with a title
		 *	where the icon says it all, an icon and a text where there is more to say.
		 *	With an action it is a button
		 *
		 *	@param		{string}		kind							background, warning, hidden, loop or align: a class of its own
		 *	@param		{string}		icon
		 *	@param		{string}		title
		 *	@param		{string}		text							'' for the icon alone
		 *	@param		{Function}	[action]
		 *
		 *	@return		{Element}
		 */
		_statusItem : function( kind, icon, title, text, action ) {

			const builder = Nino.admin.builder;
			const item = builder._el( action === undefined ? 'span' : 'button', 'builder-status-item is-'+ kind );

			item.title = title;
			item.appendChild( builder._icon( icon ) );

			if( text !== '' )
				item.appendChild( builder._el( 'span', 'builder-status-text', text ) );
			else {

				// An icon alone is a picture - but not where it is a button, which keeps its role, and has the title for its name
				if( action === undefined )
					item.setAttribute( 'role', 'img' );

				item.setAttribute( 'aria-label', title );
			}

			if( action !== undefined ) {
				item.type = 'button';
				item.addEventListener( 'click', function( ev ) {
					ev.stopPropagation();
					action();
				} );
			}

			return item;
		},

		// ----------------------------------------------------------------- Changes

		/**
		 *	One step up or down in the level a node stands in
		 *
		 *	@param		{Array}			path
		 *	@param		{number}		delta							-1 or 1
		 *
		 *	@return		void
		 */
		_stepNode : function( path, delta ) {

			const builder = Nino.admin.builder;

			if( builder._mutable() === false )
				return;

			const to = builder._step( builder._doc.model, path, delta );

			if( to === null )
				return;

			builder._sel = to;
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_duplicateAt : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			if( builder._mutable() === false )
				return;

			const to = builder._duplicate( doc.model, builder._registry, doc.file, path, builder._valuesOf );

			if( to === null )
				return;

			// The keys and slots of a copy of a section are new ones, under the id it has now
			if( path.length === 1 && doc.model.blocks[to[0]].kind === 'section' )
				builder._fresh[doc.model.blocks[to[0]].id] = true;

			builder._sel = to;
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	Copy a section, a column or a component: the clip is what the next paste puts in. The document is not changed, and so is not unsaved. The
		 *	clip carries the text each key under the section's prefix has in each language - the stored one with what was typed for it and is not saved
		 *	yet over it - so that the words travel when the page is left, or the key is renamed
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Object|null}							The clip
		 */
		_copyAt : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const place = builder._place( doc.model, path );

			if( builder._mutable() === false || place === null )
				return null;

			const kind = builder._kind( doc.model, path );
			const section = doc.model.blocks[path[0]];
			const clip = { kind : kind === 'col' ? 'column' : kind, file : doc.file, section : kind === 'html' ? '' : builder._keyId( section ), node : builder._clone( place.list[place.index] ), values : {} };
			const prefix = builder._keyUri( doc.file, clip.section, '' );
			const visit = function( component ) {

				const source = component.source;

				if( typeof source === 'string' && clip.section !== '' && source.indexOf( prefix ) === 0 && builder._sourceKind( builder._registry, component.name ) !== 'image' ) {

					const held = Object.assign( {}, builder._valuesOf( source ), component.values );

					if( Object.keys( held ).length > 0 )
						clip.values[source] = held;
				}

				( component.components || [] ).forEach( visit );
			};

			if( clip.kind === 'section' )
				clip.node.cols.forEach( function( col ) { col.components.forEach( visit ) } );
			else if( clip.kind === 'column' )
				clip.node.components.forEach( visit );
			else if( clip.kind === 'component' )
				visit( clip.node );

			builder._clip = clip;

			try {
				wn.localStorage.setItem( builder.CLIP_KEY, JSON.stringify( clip ) );
			} catch(e) {

				// No storage to be had: the clip lives in memory. A storage that reads and does not write must not hand the older clip back when the
				// editor is opened again, so what it holds is taken away
				try {
					wn.localStorage.removeItem( builder.CLIP_KEY );
				} catch(e2) {
					// None to take it from
				}
			}

			// The paste tools say what is in the clip now
			builder._redraw();

			return clip;
		},

		/**
		 *	Paste the clip beside or into the node at the path (see _paste()) and select the copy, with the focus on the tool that pastes - the one
		 *	that was pressed went with the preview, and the person may want the same again. A section is a new one: its keys and slots are made
		 *
		 *	@param		{Array}			path							[] for the end of the page
		 *
		 *	@return		void
		 */
		_pasteAt : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			if( builder._mutable() === false || builder._pasteable( doc.model, builder._registry, path, builder._clip ) !== '' )
				return;

			const to = builder._paste( doc.model, builder._registry, doc.file, path, builder._clip );

			if( to === null )
				return;

			if( builder._clip.kind === 'section' )
				builder._fresh[doc.model.blocks[to[0]].id] = true;

			builder._sel = to;
			builder._changed();
			builder._paintSelection( true );

			const tool = builder._toolAt( to, 'paste' );

			if( tool !== null )
				tool.focus();
		},

		/**
		 *	The keys of the editor: the one listener of its root is there while a document is open and goes with it. The root is focusable, so that a
		 *	click on a frame - which has no focus of its own - leaves the focus in the editor, where its keys are heard
		 *
		 *	@param		{boolean}		on
		 *
		 *	@return		void
		 */
		_watchKeys : function( on ) {

			const builder = Nino.admin.builder;
			const root = dc.getElementById('builder-editor');

			root.removeEventListener( 'keydown', builder._keyDown );

			if( on === true )
				root.addEventListener( 'keydown', builder._keyDown );
		},

		/**
		 *	Ctrl+C and Ctrl+V - Cmd on a Mac - copy and paste the frame whose tool has the focus, else the selected one, as the tools do. The
		 *	browser keeps the key wherever it has its own
		 *	use for it: in a field, with text selected, in a dialog, in an editor that is for reading, and where no frame is selected. A paste that
		 *	the place refuses does nothing - the title of the tool says why
		 *
		 *	@param		{KeyboardEvent}	ev
		 *
		 *	@return		void
		 */
		_keyDown : function( ev ) {

			const builder = Nino.admin.builder;
			// The letter of the key where it is a Latin one, else the letter the key has on a Latin layout: a Cyrillic or Greek layout has its own letters on those keys
			const key = typeof ev.key === 'string' && /^[a-z]$/i.test( ev.key ) === true ? ev.key.toLowerCase() : ( ev.code === 'KeyC' ? 'c' : ( ev.code === 'KeyV' ? 'v' : '' ) );

			if( ( ev.ctrlKey !== true && ev.metaKey !== true ) || ev.altKey === true || ev.shiftKey === true || ( key !== 'c' && key !== 'v' ) || ev.defaultPrevented === true )
				return;

			if( builder._doc === null || builder._mutable() === false || builder._dialogOptions !== null || dc.getElementById('builder-picker').open === true )
				return;

			const active = dc.activeElement;

			if( active !== null && active !== undefined && active.closest('input, textarea, select, [contenteditable]') !== null )
				return;

			const text = typeof wn.getSelection === 'function' ? wn.getSelection() : null;

			if( text !== null && text !== undefined && text.toString() !== '' )
				return;

			// The frame whose tool has the focus is the one that is meant - its toolbar is what is in view - and the selection otherwise
			const tool = active === null || active === undefined ? null : active.closest('[data-path] [data-tool]');
			const frame = tool === null ? null : tool.closest('[data-path]');
			const path = frame === null ? builder._sel : frame.dataset.path.split('.').map( Number );

			if( path.length === 0 )
				return;

			ev.preventDefault();

			if( builder._same( path, builder._sel ) === false )
				builder._select( path, false );

			if( key === 'c' )
				builder._copyAt( path );
			else
				builder._pasteAt( path );
		},

		/**
		 *	A delete the tool asks for: at once where the frame holds nothing, else
		 *	after the question
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_deleteAt : function( path ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;
			const kind = builder._kind( model, path );

			if( builder._mutable() === false )
				return;

			if( builder._children( model, path ) === 0 )
				return builder._delete( path );

			Nino.adminUi.choiceDialog( {
				title		: Nino.content.getText( '/_admin/builder/tree/'+ kind ),
				message	: Nino.content.getText('/_admin/builder/confirm/delete-node'),
				choices	: [
					{ value : 'delete', label : Nino.content.getText('/_admin/common/label/delete'), kind : 'danger' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				],
				onChoose	: function( choice ) {
					if( choice === 'delete' )
						builder._delete( path );
				},
			} );
		},

		/**
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_delete : function( path ) {

			const builder = Nino.admin.builder;

			if( builder._remove( builder._doc.model, path ) === false )
				return;

			builder._sel = path.slice( 0, -1 );
			builder._changed();
		},

		/**
		 *	A new section below the last one, with the animation of the template
		 *
		 *	@param		{string}		[kind]						content, the default, or tiles
		 *
		 *	@return		void
		 */
		_addSection : function( kind ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;

			if( builder._mutable() === false )
				return;

			const section = builder._newSection( model, kind );

			model.blocks.push( section );
			builder._fresh[section.id] = true;
			builder._sel = [ model.blocks.length - 1 ];
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	A new column at the end of the row of a section, the whole width (see _addedCol())
		 *
		 *	@param		{Array}			path					The section
		 *
		 *	@return		void
		 */
		_addCol : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];

			if( builder._mutable() === false )
				return;

			builder._addedCol( section );
			builder._sel = [ path[0], section.cols.length - 1 ];
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	Choose the component a column - or a component that holds components - gets: a lightbox with every component of the
		 *	registry, in groups, each with its icon and its label (see _pickChoices()). One that cannot go where the list is
		 *	stands there greyed, and says why. A click on one that can goes in the list and the dialog goes on to that component's
		 *	settings: the person who adds a component wants to set it. The close of the dialog, or Escape, ends it with the
		 *	component in, and the focus on the tool that opens the settings of that component (see _returnFocus())
		 *
		 *	@param		{Array}			path					The column, or the component that holds components
		 *
		 *	@return		void
		 */
		_pickComponent : function( path ) {

			const builder = Nino.admin.builder;
			const groups = builder._pickChoices( builder._doc.model, path, builder._registry );

			builder._dialog( {
				title	: Nino.content.getText('/_admin/builder/component/insert'),
				wide	: true,
				build	: function( body ) {

					const box = builder._el( 'div', 'builder-lightbox' );

					groups.forEach( function( group ) {

						const part = builder._el( 'section', 'builder-lightbox-group' );

						part.dataset.group = group.id;

						part.appendChild( builder._el( 'h3', 'builder-lightbox-title', group.label ) );

						group.rows.forEach( function( row ) {

							const list = builder._el( 'div', 'builder-lightbox-row' );

							if( row.label !== '' )
								part.appendChild( builder._el( 'h4', 'builder-lightbox-sub', row.label ) );

							row.items.forEach( function( item ) { list.appendChild( builder._pickItem( path, item ) ) } );
							part.appendChild( list );
						} );

						box.appendChild( part );
					} );

					body.appendChild( box );
				},
			} );
		},

		/**
		 *	One component of the lightbox: a button with its icon - the registry's, or the puzzle of the sprite - and its label. One
		 *	that cannot be added here is greyed, with the reason as its title and, for whoever does not see the grey, in a span of its
		 *	own that only a screen reader reads
		 *
		 *	@param		{Array}			path					Where it would go
		 *	@param		{Object}		item					As _pickChoices() has it: { name, label, icon, reason }
		 *
		 *	@return		{Element}
		 */
		_pickItem : function( path, item ) {

			const builder = Nino.admin.builder;
			const button = builder._el( 'button', 'builder-pick-item' );
			// A component that names no parts - or an empty list of them - wears the puzzle of the sprite
			const icon = Array.isArray( item.icon ) === true && item.icon.length > 0 ? builder._shape( item.icon ) : builder._icon( 'puzzle' );

			icon.classList.add('builder-pick-icon');
			button.type = 'button';
			// The component by its name in the registry, which its label - in the language of the workbench - is no way to find it by
			button.dataset.name = item.name;
			button.appendChild( icon );
			button.appendChild( builder._el( 'span', 'builder-pick-label', item.label ) );

			if( item.reason !== '' ) {
				button.disabled = true;
				button.title = item.reason;
				button.appendChild( builder._el( 'span', 'nino-admin-sr-only', item.reason ) );
			} else
				button.addEventListener( 'click', function() { builder._insert( path, item.name ) } );

			return button;
		},

		/**
		 *	Add a component and open its settings in the dialog the choice was made in, with the focus on the first control
		 *
		 *	@param		{Array}			path					The column, or the component that holds components
		 *	@param		{string}		name					The component
		 *
		 *	@return		void
		 */
		_insert : function( path, name ) {

			const builder = Nino.admin.builder;
			const before = builder._get( builder._doc.model, path ).components.length;

			builder._addComponent( path, name );

			// Where nothing was added - a document that may not change - there are no settings to open
			if( builder._get( builder._doc.model, path ).components.length === before )
				return;

			builder._openSettings( builder._sel );

			// The first control of the block that is open
			const open = Array.from( dc.querySelectorAll('#builder-dialog-content .builder-block-body') ).find( function( candidate ) { return candidate.hidden === false } );
			const first = open === undefined ? null : open.querySelector('input:not(:disabled), select:not(:disabled), textarea:not(:disabled), button:not(:disabled)');

			if( first !== null )
				first.focus();
		},

		/**
		 *	A component at the end of a column - or of the loop it has -, or a child at the end
		 *	of a component that holds components
		 *
		 *	@param		{Array}			path					The column, or the component that holds components
		 *	@param		{string}		name					The component
		 *
		 *	@return		void
		 */
		_addComponent : function( path, name ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			// The components of the column, or the children of the component that holds components
			const list = ( builder._get( doc.model, path ) || {} ).components;

			if( builder._mutable() === false || Array.isArray( list ) === false )
				return;

			list.push( builder._newComponent( doc.model, builder._registry, doc.file, path, name, builder._keys ) );
			builder._sel = path.concat( [ list.length - 1 ] );
			builder._changed();
			builder._paintSelection( true );
		},

		// -------------------------------------------------------------- Dialogs

		/**
		 *	Open the dialog the template has, with a title, a body that is built here - the blocks of the form of an element, where it is one - and the actions it
		 *	ends with. A form of a node writes into the model as it is changed, so the actions are only the way out; the dialog that asks (a new template, an
		 *	HTML+ block) has an action that decides
		 *
		 *	@param		{Object}		options
		 *	@param		{string}		options.title
		 *	@param		{Array}			[options.rename]		The path of a section: its name stands in the title, where it is changed
		 *	@param		{number}		[options.blame]			The index of the section the form is of: what a refused save said of it goes over the form
		 *	@param		{Function}	[options.build]			Called with the body
		 *	@param		{Array}			[options.blocks]		[ { id, controls, held, build( body ), says() } ] - the form of an element, one block for each, drawn as an accordion (see _blocks())
		 *	@param		{string}		[options.kind]			The kind of element the blocks are of: the dialog opens on the block that was open last in a dialog of that kind
		 *	@param		{string}		[options.block]			The block to open, whichever was open last
		 *	@param		{Array}			[options.actions]		[ { label, kind, onClick( problem ) } ] - false keeps the dialog open
		 *	@param		{Function}	[options.onClose]
		 *	@param		{boolean}		[options.wide]
		 *	@param		{Array}			[options.returnTo]	The path of the node the form is of: when the dialog closes and the control that opened it is gone, the focus goes to the settings tool of that node (see _returnFocus())
		 *
		 *	@return		void
		 */
		_dialog : function( options ) {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-dialog');
			const content = dc.getElementById('builder-dialog-content');
			const actions = dc.getElementById('builder-dialog-actions');
			const problems = dc.getElementById('builder-dialog-problems');
			const heading = dc.getElementById('builder-dialog-heading');
			const name = dc.getElementById('builder-dialog-name');
			// A dialog that takes the place of another - the settings of a component that was added in the lightbox - is opened from the control the first one was
			const opener = builder._dialogOptions === null ? dc.activeElement : builder._dialogOpener;

			builder._closeDialog( true );
			builder._dialogOptions = options;
			builder._dialogOpener = opener;

			dialog.classList.toggle( 'is-wide', options.wide === true );
			dc.getElementById('builder-dialog-kind').textContent = options.title;
			content.innerHTML = '';
			actions.innerHTML = '';
			problems.textContent = '';
			problems.hidden = true;

			const problem = function( text ) {
				problems.textContent = text;
				problems.hidden = text === '';
			};

			// The name of a section is part of the title, and changed there
			heading.querySelectorAll('.builder-rename').forEach( function( button ) { button.remove() } );
			name.hidden = options.rename === undefined;

			if( options.rename !== undefined ) {
				name.textContent = builder._doc.model.blocks[options.rename[0]].id;
				heading.appendChild( builder._renamer( name, options.rename, problem, function( id ) { builder._renamedIn( id ) } ) );
			}

			if( Array.isArray( options.blocks ) === true && options.blocks.length > 0 ) {

				if( options.blame !== undefined )
					builder._blamed( content, options.blame );

				builder._blocks( content, options );
			} else
				options.build( content );

			( options.actions || [] ).forEach( function( action ) {

				const button = builder._el( 'button', 'nino-admin-btn-'+ ( action.kind || 'secondary' ), action.label );

				button.type = 'button';
				button.addEventListener( 'click', function() {
					problem( '' );
					if( action.onClick( problem ) !== false )
						builder._closeDialog();
				} );
				actions.appendChild( button );
			} );

			const close = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText( ( options.actions || [] ).length > 0 ? '/_admin/common/label/cancel' : '/_admin/builder/label/close' ) );

			close.type = 'button';
			close.addEventListener( 'click', builder._closeDialog );
			actions.appendChild( close );

			builder._dialogProblem = problem;

			if( typeof dialog.showModal === 'function' )
				dialog.showModal();
			else
				dialog.setAttribute( 'open', '' );
		},

		/**
		 *	The blocks of the form of an element, as an accordion: a head for each - its name, and muted beside it what the block says of the element where that
		 *	is not the default (see _saySummaries()) - over a body that holds its controls. One is open and the others are closed; a click on a head opens that
		 *	block and closes the one that was open, or closes the one it is the head of. The block that opens is the one asked for, else the one that was open
		 *	last in a dialog of the same kind of element - kept while the page is open, and nowhere else -, else the first. The bodies are all built at once and
		 *	the closed ones hidden, as the panes of a tab list were
		 *
		 *	@param		{Element}		content
		 *	@param		{Object}		options					As _dialog() has them: blocks, kind, block
		 *
		 *	@return		void
		 */
		_blocks : function( content, options ) {

			const builder = Nino.admin.builder;
			const state = { kind : options.kind, ids : [], parts : {}, open : null };
			const host = builder._el( 'div', 'builder-blocks' );
			const has = function( id ) { return options.blocks.some( function( block ) { return block.id === id } ) };
			const start = [ options.block, builder._lastBlock[options.kind] ].find( function( id ) { return id !== undefined && has( id ) === true } ) ?? options.blocks[0].id;

			options.blocks.forEach( function( block ) {

				const part = builder._el( 'section', 'builder-block' );
				const title = builder._el( 'h3', 'builder-block-title' );
				const head = builder._el( 'button', 'builder-block-head' );
				const says = builder._el( 'span', 'builder-block-says' );
				const body = builder._el( 'div', 'builder-block-body' );

				part.id = 'builder-block-'+ block.id;
				head.id = 'builder-head-'+ block.id;
				body.id = 'builder-body-'+ block.id;
				head.type = 'button';
				head.setAttribute( 'aria-controls', body.id );
				body.setAttribute( 'role', 'region' );
				body.setAttribute( 'aria-labelledby', head.id );
				head.appendChild( builder._icon('down') );
				head.appendChild( builder._el( 'span', 'builder-block-name', Nino.content.getText( '/_admin/builder/block/'+ block.id+ '/label' ) ) );
				head.appendChild( says );
				head.addEventListener( 'click', function() { builder._openBlock( state.open === block.id ? null : block.id ) } );
				title.appendChild( head );
				part.appendChild( title );
				part.appendChild( body );
				host.appendChild( part );
				state.ids.push( block.id );
				state.parts[block.id] = { part : part, head : head, says : says, body : body, spec : block };
				block.build( body );
			} );

			content.appendChild( host );
			builder._dialogBlocks = state;
			builder._openBlock( start );
			builder._saySummaries();
		},

		/**
		 *	Open one block of the dialog and close the others - or close them all, for null. What was opened is what a dialog of the same kind of element opens on
		 *	next time
		 *
		 *	@param		{string|null}	id
		 *
		 *	@return		void
		 */
		_openBlock : function( id ) {

			const builder = Nino.admin.builder;
			const state = builder._dialogBlocks;

			if( state === null )
				return;

			state.open = id;

			state.ids.forEach( function( other ) {

				const part = state.parts[other];

				part.part.classList.toggle( 'is-open', other === id );
				part.head.setAttribute( 'aria-expanded', other === id ? 'true' : 'false' );
				part.body.hidden = other !== id;
			} );

			if( id !== null && state.kind !== undefined )
				builder._lastBlock[state.kind] = id;
		},

		/**
		 *	Draw blocks of the dialog again - a choice in one changes what another holds. The control that has the focus is gone with the block, and the focus
		 *	goes to the one that stands in its place
		 *
		 *	@param		{...string}		ids						The blocks; the ones the dialog has none of are let go
		 *
		 *	@return		void
		 */
		_refreshBlock : function( ...ids ) {

			const builder = Nino.admin.builder;
			const state = builder._dialogBlocks;

			if( state === null )
				return;

			ids.forEach( function( id ) {

				const part = state.parts[id];

				if( part === undefined )
					return;

				const controls = function() { return Array.from( part.body.querySelectorAll('input, select, textarea, button') ) };
				const had = dc.activeElement;
				const at = had === null ? -1 : controls().indexOf( had );

				part.body.innerHTML = '';
				part.spec.build( part.body );

				if( at === -1 )
					return;

				// The control that had the focus is where it was - or, where it sleeps now (the arrow that was switched off in a section that is no cover), the nearest awake one
				// before it, else after it, else the head of the block: a focus on a control that cannot have it falls to the page. A question mark is no place for it - it
				// would open the hint of another field
				const now = controls();
				const awake = function( control ) { return control !== undefined && control.disabled === false && control.classList.contains('builder-help-btn') === false };
				const before = now.slice( 0, at ).reverse().find( awake );
				const after = now.slice( at + 1 ).find( awake );
				const target = awake( now[at] ) ? now[at] : ( before ?? after ?? part.head );

				target.focus();
			} );

			builder._saySummaries();
		},

		/**
		 *	Draw the blocks of the dialog that hold some of the controls named again - a choice in one control changes what another shows, and the table says where that stands.
		 *	With no name, every block of the dialog
		 *
		 *	@param		{...string}		names						The controls, by the names the table lists them under
		 *
		 *	@return		void
		 */
		_refreshControls : function( ...names ) {

			const builder = Nino.admin.builder;
			const state = builder._dialogBlocks;

			if( state === null )
				return;

			builder._refreshBlock( ...state.ids.filter( function( id ) {
				return names.length === 0 || state.parts[id].spec.controls.some( function( name ) { return names.indexOf( name ) !== -1 } );
			} ) );
		},

		/**
		 *	What the head of each block says: the values of its controls that are not the default, as the block's own words say them, joined by a dot. A block
		 *	whose controls are all at the default says nothing
		 *
		 *	@return		void
		 */
		_saySummaries : function() {

			const state = Nino.admin.builder._dialogBlocks;

			if( state === null )
				return;

			state.ids.forEach( function( id ) {

				const said = state.parts[id].spec.says();

				state.parts[id].says.textContent = said;
				state.parts[id].says.hidden = said === '';
			} );
		},

		// The kind of element each dialog was of and the block that was open in it last, by kind: kept while the page is open
		_lastBlock : {},
		// The blocks of the dialog that is open: { kind, ids, parts : id => { part, head, says, body, spec }, open }; null where it has none
		_dialogBlocks : null,

		/**
		 *	Tabs over panes, as a tab list is made: the buttons of the strip are tabs
		 *	that control their panes, and the panes are tab panels that are named by
		 *	their tabs. The panes are built at once, the one that is chosen is shown
		 *
		 *	@param		{Element}		strip
		 *	@param		{Element}		content
		 *	@param		{Array}			tabs							[ { id, label, build( pane ) } ]
		 *	@param		{string}		[start]						The tab to start on, the first by default
		 *	@param		{string}		prefix						Starts the ids of the elements, to tell the dialogs apart
		 *
		 *	@return		{Object}									id => pane
		 */
		_tabs : function( strip, content, tabs, start, prefix ) {

			const builder = Nino.admin.builder;
			const buttons = {};
			const panes = {};

			tabs.forEach( function( tab ) {

				const button = builder._el( 'button', 'builder-tab', tab.label );
				const pane = builder._el( 'div', 'builder-tabpane' );

				button.type = 'button';
				button.id = prefix+ '-tab-'+ tab.id;
				button.setAttribute( 'role', 'tab' );
				button.setAttribute( 'aria-controls', prefix+ '-pane-'+ tab.id );
				pane.id = prefix+ '-pane-'+ tab.id;
				pane.setAttribute( 'role', 'tabpanel' );
				pane.setAttribute( 'aria-labelledby', button.id );
				buttons[tab.id] = button;
				panes[tab.id] = pane;
				strip.appendChild( button );
				content.appendChild( pane );
				tab.build( pane );
			} );

			const show = function( id ) {
				Object.keys( panes ).forEach( function( key ) { panes[key].hidden = key !== id } );
			};
			const first = start !== undefined && panes[start] !== undefined ? start : tabs[0].id;

			Nino.adminUi.buttonRow( buttons, first, show, 'aria-selected' );
			show( first );

			return panes;
		},

		// The options of the dialog that is open, the way it says what is wrong, and the control that had the focus when it opened
		_dialogOptions : null,
		_dialogProblem : null,
		_dialogOpener : null,

		/**
		 *	Close the dialog, and tell what it was made for. The dialog of a source, which
		 *	stands above it, goes first. A close the script asks for makes the dialog fire a
		 *	close event of its own a task later, which has to be let go by: a dialog may
		 *	have been opened since. The focus goes back where it was, and what another panel
		 *	changed while it was open is looked at now that it is gone - unless the close is
		 *	the first step of another dialog's opening
		 *
		 *	@param		{boolean}		[replaced]				True where another dialog follows at once
		 *
		 *	@return		void
		 */
		_closeDialog : function( replaced ) {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-dialog');
			const options = builder._dialogOptions;
			const opener = builder._dialogOpener;

			builder._dialogOptions = null;
			builder._dialogOpener = null;
			builder._dialogBlocks = null;
			builder._closePicker();

			if( dialog !== null && dialog.open === true && typeof dialog.close === 'function' ) {
				builder._closing++;
				dialog.close();
			}

			if( options !== null && typeof options.onClose === 'function' )
				options.onClose();

			if( replaced === true )
				return;

			builder._returnFocus( options, opener );
			builder._reloadMoved();
		},

		// How many closes the script asked for whose event has not arrived yet
		_closing : 0,

		/**
		 *	The dialog was closed by the person - Escape, or the way the browser has -
		 *	and what it was asked to do then
		 *
		 *	@return		void
		 */
		_dialogClosed : function() {

			const builder = Nino.admin.builder;

			if( builder._closing > 0 ) {
				builder._closing--;
				return;
			}

			const options = builder._dialogOptions;
			const opener = builder._dialogOpener;

			builder._dialogOptions = null;
			builder._dialogOpener = null;
			builder._dialogBlocks = null;

			if( options !== null && typeof options.onClose === 'function' )
				options.onClose();

			builder._returnFocus( options, opener );
			builder._reloadMoved();
		},

		/**
		 *	Where the focus goes when a dialog is closed. The browser gives it back to the control that opened the dialog -
		 *	unless that control is gone: a change in the form draws the preview again, and the tool that opened the form
		 *	goes with it; the button that opened the lightbox is gone once a component was added in it. The focus is then on
		 *	no control, and goes to the settings tool of the node the form was of, drawn again where it was. A dialog that is
		 *	of no node, or that was opened with the focus on no control, leaves the focus as the browser has it
		 *
		 *	@param		{Object|null}		options				The dialog that was closed
		 *	@param		{Element|null}	opener				The control that had the focus when it was opened
		 *
		 *	@return		void
		 */
		_returnFocus : function( options, opener ) {

			if( options === null || Array.isArray( options.returnTo ) === false || opener === null || opener === dc.body || opener.isConnected !== false )
				return;

			const tool = Nino.admin.builder._toolAt( options.returnTo, 'settings' );

			if( tool !== null )
				tool.focus();
		},

		/**
		 *	A tool of a frame of the preview, where it is drawn now
		 *
		 *	@param		{Array}			path							The frame
		 *	@param		{string}		name							settings, html, up, down, duplicate, copy, paste or delete
		 *
		 *	@return		{Element|null}						null where the frame has none that is shown and can be used
		 */
		_toolAt : function( path, name ) {

			const tool = dc.querySelector( '#builder-preview [data-path="'+ path.join('.')+ '"] [data-tool="'+ name+ '"]' );

			return tool !== null && tool.hidden === false && tool.disabled === false ? tool : null;
		},

		/**
		 *	The preview drawn again where what another panel changed asks for it, with the focus where it was. A tool of a
		 *	frame that has the focus goes with the drawing, and the focus with it, to the page: it is given to the tool that
		 *	stands in its place, found by the frame it is of and its name. Anywhere else the focus is not the preview's to move
		 *
		 *	@return		void
		 */
		_redraw : function() {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-preview');
			const active = dc.activeElement;
			const tool = active !== null && active !== undefined && active.dataset !== undefined && active.dataset.tool !== undefined && mount.contains( active ) === true ? active : null;
			const frame = tool === null ? null : tool.closest('[data-path]');
			const at = frame === null ? null : { path : frame.dataset.path.split('.').map( Number ), name : tool.dataset.tool };

			builder._renderPreview();

			const again = at === null ? null : builder._toolAt( at.path, at.name );

			if( again !== null )
				again.focus();
		},

		// ------------------------------------------------ Icons, groups and tables

		/**
		 *	An icon of the sprite in the template. It is decoration: the word for it
		 *	is the title and the label of what it is on, or stands beside it
		 *
		 *	@param		{string}		name							The part of its id after builder-icon-
		 *
		 *	@return		{Element}
		 */
		_icon : function( name ) {

			const builder = Nino.admin.builder;
			const icon = builder._fragment('icon');

			builder._one( icon, 'use' ).setAttribute( 'href', '#builder-icon-'+ name );

			return icon;
		},

		/**
		 *	The icon a component is shown by, from the parts the registry gives it: an svg of
		 *	24 by 24 that is decoration, with an element for each part, in the order of the
		 *	parts. It is built and never written: the icon of a feature's component is data
		 *	that feature brings, and the script puts nothing of it on the page but the elements
		 *	it makes of it, one by one, with the attributes it sets, one by one - the tag of a
		 *	part is one of the seven an icon is made of and an attribute is one that tag takes,
		 *	which is the rule the kernel holds an icon to before the registry has it, and the
		 *	script holds to it again. A part it does not know is left out, and so is an
		 *	attribute. The stroke and the size are the stylesheet's, as they are of the icons
		 *	of the sprite
		 *
		 *	@param		{Array}			parts							[ [ tag, { attribute : value } ] ]
		 *
		 *	@return		{Element}
		 */
		_shape : function( parts ) {

			const builder = Nino.admin.builder;
			const icon = dc.createElementNS( builder.SVG, 'svg' );

			icon.setAttribute( 'class', 'builder-icon' );
			icon.setAttribute( 'viewBox', '0 0 24 24' );
			icon.setAttribute( 'aria-hidden', 'true' );
			icon.setAttribute( 'focusable', 'false' );

			parts.forEach( function( part ) {

				const tag = Array.isArray( part ) === true ? part[0] : '';
				const attributes = Array.isArray( part ) === true && typeof part[1] === 'object' ? part[1] : null;

				if( typeof tag !== 'string' || Object.prototype.hasOwnProperty.call( builder.SHAPE_TAGS, tag ) === false || attributes === null )
					return;

				const element = dc.createElementNS( builder.SVG, tag );

				Object.keys( attributes ).forEach( function( name ) {
					if( builder.SHAPE_TAGS[tag].indexOf( name ) !== -1 )
						element.setAttribute( name, attributes[name] );
				} );

				icon.appendChild( element );
			} );

			return icon;
		},

		/**
		 *	A button that is an icon and nothing else. The word it stands for is its title,
		 *	for the pointer, and its label, for whoever does not see the icon
		 *
		 *	@param		{string}		name							The icon
		 *	@param		{string}		word
		 *	@param		{Function}	onClick
		 *
		 *	@return		{Element}
		 */
		_iconButton : function( name, word, onClick ) {

			const builder = Nino.admin.builder;
			const button = builder._el( 'button', 'builder-icon-btn' );

			button.type = 'button';
			button.title = word;
			button.setAttribute( 'aria-label', word );
			button.appendChild( builder._icon( name ) );
			button.addEventListener( 'click', onClick );

			return button;
		},

		/**
		 *	A group of a form: what belongs together under a heading, which can be folded
		 *	away where it is rarely wanted
		 *
		 *	@param		{Element}		parent
		 *	@param		{string}		title
		 *	@param		{boolean}		[folded]					A group that is shut until it is opened
		 *
		 *	@return		{Element}									Where the fields of the group go
		 */
		_group : function( parent, title, folded ) {

			const builder = Nino.admin.builder;
			const group = builder._fragment( folded === true ? 'fold' : 'group' );

			builder._one( group, '.builder-group-title' ).textContent = title;
			parent.appendChild( group );

			return builder._one( group, '.builder-group-body' );
		},

		/**
		 *	The group of a block that a control belongs to, by the name of the group (group/<name>): the one that was drawn last in the body where it is that group, else a new
		 *	one under its heading. A group is there once a control has asked for it, so a control that draws nothing leaves no heading
		 *
		 *	@param		{Element}		body
		 *	@param		{string}		name
		 *
		 *	@return		{Element}									Where the fields of the group go
		 */
		_groupIn : function( body, name ) {

			const builder = Nino.admin.builder;
			const last = body.children[body.children.length - 1];

			if( last !== undefined && last.dataset.group === name )
				return builder._one( last, '.builder-group-body' );

			const inner = builder._group( body, Nino.content.getText( '/_admin/builder/group/'+ name ) );

			body.children[body.children.length - 1].dataset.group = name;

			return inner;
		},

		/**
		 *	A table of a form, with its heads
		 *
		 *	@param		{Array}			heads							The words over its columns
		 *
		 *	@return		{Object}									{ table, body }
		 */
		_table : function( heads ) {

			const builder = Nino.admin.builder;
			const table = builder._fragment('table');
			const row = builder._one( table, 'thead tr' );

			heads.forEach( function( head ) {

				const cell = builder._el( 'th', '', head );

				cell.setAttribute( 'scope', 'col' );
				row.appendChild( cell );
			} );

			return { table : table, body : builder._one( table, 'tbody' ) };
		},

		/**
		 *	The head of a row: a word, and an icon before it where the row has one
		 *
		 *	@param		{string}		word
		 *	@param		{string}		[icon]
		 *
		 *	@return		{Element}
		 */
		_rowHead : function( word, icon ) {

			const builder = Nino.admin.builder;
			const head = builder._el( 'th', 'builder-row-head' );

			head.setAttribute( 'scope', 'row' );

			if( icon !== undefined )
				head.appendChild( builder._icon( icon ) );

			head.appendChild( builder._el( 'span', '', word ) );

			return head;
		},

		/**
		 *	A row of a table: its head, then a cell for each control
		 *
		 *	@param		{Element}		body
		 *	@param		{Element}		head
		 *	@param		{Array}			controls
		 *
		 *	@return		void
		 */
		_tableRow : function( body, head, controls ) {

			const builder = Nino.admin.builder;
			const row = builder._el('tr');

			row.appendChild( head );

			controls.forEach( function( control ) {

				const cell = builder._el('td');

				cell.appendChild( control );
				row.appendChild( cell );
			} );

			body.appendChild( row );
		},

		/**
		 *	The select in a cell of a table. It has no label beside it, so its
		 *	label says whose it is
		 *
		 *	@param		{string}		label							What a screen reader calls it
		 *	@param		{Array}			options						[ { value, label } ]
		 *	@param		{string}		value
		 *	@param		{Function}	onChange					Called with the value, as text
		 *
		 *	@return		{Element}
		 */
		_tableSelect : function( label, options, value, onChange ) {

			const builder = Nino.admin.builder;
			const select = builder._el( 'select', 'nino-admin-input' );

			select.setAttribute( 'aria-label', label );

			options.forEach( function( option ) {

				const el = builder._el( 'option', '', option.label );

				el.value = option.value;
				select.appendChild( el );
			} );

			select.value = String( value );
			select.addEventListener( 'change', function() { onChange( select.value ) } );

			return select;
		},

		/**
		 *	The viewports a table of a form has a row for: the one the preview shows, or
		 *	all three where it shows all of them at once
		 *
		 *	@return		{Array}
		 */
		_views : function() {

			const builder = Nino.admin.builder;

			return builder._viewport === 'g' ? builder.VIEWPORTS : [ builder._viewport ];
		},

		/**
		 *	A table with a row for each viewport that is shown (see _views()): the device,
		 *	with its icon and its word, and the controls the caller makes for it. Where it
		 *	has one row, a link under it has the preview show all of them, and the table
		 *	all three rows
		 *
		 *	@param		{Element}		pane
		 *	@param		{Array}			heads							The words over the columns after the device
		 *	@param		{Function}	controls					Called with the viewport: the controls of its row
		 *
		 *	@return		void
		 */
		_viewportTable : function( pane, heads, controls ) {

			const builder = Nino.admin.builder;
			const table = builder._table( [ Nino.content.getText('/_admin/builder/col/head-device') ].concat( heads ) );

			builder._views().forEach( function( viewport ) {
				builder._tableRow( table.body, builder._rowHead( Nino.content.getText( '/_admin/builder/viewport/'+ viewport ), builder.DEVICES[viewport] ), controls( viewport ) );
			} );

			pane.appendChild( table.table );

			if( builder._viewport !== 'g' )
				pane.appendChild( builder._allViewports() );
		},

		/**
		 *	The link under a table of one row: the preview shows all the viewports, and
		 *	the blocks of the dialog it is in that have a table of viewports are drawn
		 *	again with all of them - the block that is open is not the only one
		 *
		 *	@return		{Element}
		 */
		_allViewports : function() {

			const builder = Nino.admin.builder;
			const link = builder._el( 'button', 'builder-link-btn', Nino.content.getText('/_admin/builder/viewport/all') );

			link.type = 'button';
			link.addEventListener( 'click', function() {
				builder._setView('g');
				builder._refreshControls( 'width', 'stack', 'reverse' );
			} );

			return link;
		},

		// ---------------------------------------------------------------- Fields

		/**
		 *	The key of the words of a value, option/<group>-<value>: the value as a word of a key - the underscore a value starts with (the
		 *	window _blank opens in) is none, any other is a hyphen - and default for the empty one
		 *
		 *	@param		{string}		group
		 *	@param		{string}		value
		 *
		 *	@return		{string}
		 */
		_wordKey : function( group, value ) {
			return '/_admin/builder/option/'+ group+ '-'+ ( value === '' ? 'default' : String( value ).replace( /^_+/, '' ).replace( /_+/g, '-' ) );
		},

		/**
		 *	The words of a value of a setting: the panel's own where it has them,
		 *	the value itself - a modifier of Nino.css is a name - where it has not
		 *
		 *	@param		{string}		group
		 *	@param		{string}		value
		 *
		 *	@return		{string}
		 */
		_optionLabel : function( group, value ) {

			const own = Nino.content.getText( Nino.admin.builder._wordKey( group, value ) );

			if( own !== '' )
				return own;

			return value === '' ? Nino.content.getText('/_admin/builder/label/none') : value;
		},

		/**
		 *	The words of a value of an attribute a component declares, for a choice of a few drawn as buttons: the panel's own where it has
		 *	them (option/<group>-<value>), else the value itself, humanised - hyphens and underscores are spaces, the first letter is a capital -
		 *	and, for the empty one, none
		 *
		 *	@param		{string}		group							The group of the words (see ATTRIBUTE_WORDS) - where an attribute has none, attribute-<its name>, so that the name of a component's attribute is never a group the panel has for something else
		 *	@param		{string}		value
		 *
		 *	@return		{string}
		 */
		_wordOf : function( group, value ) {

			const own = Nino.content.getText( Nino.admin.builder._wordKey( group, value ) );

			if( own !== '' )
				return own;

			if( value === '' )
				return Nino.content.getText('/_admin/builder/label/none');

			const words = String( value ).replace( /[-_]+/g, ' ' ).trim();

			return words.charAt(0).toUpperCase()+ words.slice(1);
		},

		/**
		 *	@param		{string}		group
		 *	@param		{Array}			values
		 *
		 *	@return		{Array}										[ { value, label } ]
		 */
		_options : function( group, values ) {

			const builder = Nino.admin.builder;

			return values.map( function( value ) { return { value : value, label : builder._optionLabel( group, value ) } } );
		},

		/**
		 *	The spacing of an element as the box it is: the four sides inside as a select each around a small rectangle, the two outside above and below it, where the
		 *	element has an outside. A select holds the steps 0 to 6 and none, which is the page's own and drawn as a dash. The box reads and writes the settings it is given -
		 *	the keys of the sides, the Writer makes the classes of them - and nothing else
		 *
		 *	@param		{Object}		holder						Where the settings are kept: the settings of a section, a column, a component
		 *	@param		{Object}		options						{ name, hint, outside, inside, after }: the words the box is called by - '' for none -, the sentence a "?" says of it, the keys of the sides
		 *																	outside - above, below, or null where it has no outside - and inside - above, below, left, right -, and what is called with the key of a side after it was written
		 *
		 *	@return		{Element}
		 */
		_boxField : function( holder, options ) {

			const builder = Nino.admin.builder;
			const box = builder._el( 'div', 'builder-box' );
			const head = builder._el( 'div', 'builder-box-head' );
			const frame = builder._el( 'div', 'builder-box-frame' );
			const label = options.name === '' ? Nino.content.getText('/_admin/builder/block/spacing/label') : options.name;
			const side = function( key, place, words ) {

				const select = builder._el( 'select', 'nino-admin-input builder-step builder-step-'+ place );

				select.dataset.key = key;
				select.setAttribute( 'aria-label', Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/spacing-cell'), Nino.content.getText( '/_admin/builder/section/'+ words ), Nino.content.getText( '/_admin/builder/spacing/'+ ( place === 'outer' ? 'outside' : 'inside' ) ) ) );

				builder.SPACES.forEach( function( value ) {

					const option = builder._el( 'option', '', value === '' ? '–' : value );

					option.value = value;
					select.appendChild( option );
				} );

				select.value = String( holder[key] ?? '' );
				select.addEventListener( 'change', function() {

					holder[key] = select.value;

					if( typeof options.after === 'function' )
						options.after( key );

					builder._changed();
				} );

				return select;
			};

			box.setAttribute( 'role', 'group' );
			box.setAttribute( 'aria-label', label );
			head.appendChild( builder._el( 'span', 'builder-box-name', options.name ) );

			if( options.hint !== '' )
				head.appendChild( builder._help( options.hint, label ) );

			box.appendChild( head );

			if( options.outside !== null )
				box.appendChild( side( options.outside[0], 'outer', 'above' ) );

			frame.appendChild( side( options.inside[0], 'inner', 'above' ) );
			frame.appendChild( side( options.inside[2], 'inner', 'left' ) );
			frame.appendChild( builder._el( 'span', 'builder-box-core' ) );
			frame.appendChild( side( options.inside[3], 'inner', 'right' ) );
			frame.appendChild( side( options.inside[1], 'inner', 'below' ) );
			box.appendChild( frame );

			if( options.outside !== null )
				box.appendChild( side( options.outside[1], 'outer', 'below' ) );

			return box;
		},

		/**
		 *	What a box says of itself in the head of its block: where a side is set, outside above and below, then inside above, below, left and right, a dash for the sides that are
		 *	not set - a box with no outside says the inside alone, and a box with nothing set says nothing. A box that has a name of its own has it in front
		 *
		 *	@param		{Object}		holder
		 *	@param		{Array|null}	outside						The keys above and below, null for a box that has no outside
		 *	@param		{Array}			inside						The keys above, below, left and right
		 *	@param		{string}		[name]
		 *
		 *	@return		{string}									'' where no side is set
		 */
		_boxSaid : function( holder, outside, inside, name ) {

			const set = function( key ) { return holder[key] !== undefined && holder[key] !== '' };
			const steps = function( keys ) { return keys.map( function( key ) { return set( key ) === true ? holder[key] : '–' } ).join('/') };

			if( ( outside === null ? inside : outside.concat( inside ) ).some( set ) === false )
				return '';

			const parts = outside === null ? [] : [ Nino.content.getText('/_admin/builder/spacing/outside')+ ' '+ steps( outside ) ];

			parts.push( Nino.content.getText('/_admin/builder/spacing/inside')+ ' '+ steps( inside ) );

			return ( name === undefined || name === '' ? '' : name+ ' ' )+ parts.join(' · ');
		},

		/**
		 *	How far in from the left and the right the picture or the ground of a row, a column or a card starts: two selects, left and right, of the steps up to the third - none
		 *	being the page's own - under one name
		 *
		 *	@param		{Object}		holder
		 *	@param		{Array}			keys							The settings, left then right
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *
		 *	@return		{Element}									The group; setDisabled( boolean ) puts it to sleep
		 */
		_offsetField : function( holder, keys, label, hint ) {

			const builder = Nino.admin.builder;
			const field = builder._el( 'div', 'builder-offset' );
			const group = builder._el( 'div', 'builder-offset-steps' );
			const selects = [];

			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', label );

			keys.forEach( function( key, at ) {

				const select = builder._el( 'select', 'nino-admin-input builder-step' );

				select.dataset.key = key;
				select.setAttribute( 'aria-label', Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/spacing-cell'), label, Nino.content.getText( '/_admin/builder/section/'+ ( at === 0 ? 'left' : 'right' ) ) ) );

				builder.OFFSETS.forEach( function( value ) {

					const option = builder._el( 'option', '', value === '' ? '–' : value );

					option.value = value;
					select.appendChild( option );
				} );

				select.value = String( holder[key] ?? '' );
				select.addEventListener( 'change', function() {
					holder[key] = select.value;
					builder._changed();
				} );
				selects.push( select );
				group.appendChild( builder._el( 'span', 'builder-offset-side', Nino.content.getText( '/_admin/builder/section/'+ ( at === 0 ? 'left' : 'right' ) ) ) );
				group.appendChild( select );
			} );

			field.appendChild( builder._el( 'span', 'builder-offset-label', label ) );
			field.appendChild( group );

			const row = builder._hinted( field, hint, label );

			row.setDisabled = function( disabled ) { selects.forEach( function( select ) { select.disabled = disabled === true } ) };

			return row;
		},

		/**
		 *	What a holder says of how far in from its sides its ground starts: both steps, a dash where one is not set - nothing where neither is
		 *
		 *	@param		{Object}		holder
		 *	@param		{Array}			keys
		 *	@param		{string}		label
		 *
		 *	@return		{string}
		 */
		_offsetSaid : function( holder, keys, label ) {

			if( keys.every( function( key ) { return holder[key] === undefined || holder[key] === '' } ) === true )
				return '';

			return label+ ' '+ keys.map( function( key ) { return holder[key] === undefined || holder[key] === '' ? '–' : holder[key] } ).join('/');
		},

		/**
		 *	The control element of a field marked with the setting it writes: the select, the input or the group of buttons that is the field, or is in it - what a test, a script
		 *	or a reader of the page finds the field of a setting by
		 *
		 *	@param		{Element}		field
		 *	@param		{string}		key
		 *
		 *	@return		{Element}									The field
		 */
		_keyed : function( field, key ) {

			const selector = 'select, textarea, input[type="text"], input[type="number"], input[type="checkbox"], .builder-segment';
			const control = field.matches( selector ) === true ? field : field.querySelector( selector );

			if( control !== null )
				control.dataset.key = key;

			return field;
		},

		/**
		 *	The blocks an element has, drawn from the table (BLOCKS): for each block of the kind the controls it lists, in its order, that the element has - and a block none of whose
		 *	controls the element has is not one of them. A control that is listed under several of its settings is drawn once in the dialog, where the first of them stands. What the
		 *	head of a block says is what these controls say, in the same order
		 *
		 *	@param		{string}		kind							section, column or component
		 *	@param		{Object}		controls					The controls of the element by name: { keys, build( body ), says() } each (see _sectionControls())
		 *
		 *	@return		{Array}										[ { id, controls, held, build, says } ] for the dialog: the names the table lists for the block, the controls drawn in it
		 */
		_blocksOf : function( kind, controls ) {

			const builder = Nino.admin.builder;
			const drawn = [];

			return builder.BLOCKS[kind].map( function( block ) {

				const held = block.controls.filter( function( name ) { return Object.prototype.hasOwnProperty.call( controls, name ) } ).map( function( name ) { return controls[name] } ).filter( function( control ) {

					if( drawn.indexOf( control ) !== -1 )
						return false;

					drawn.push( control );

					return true;
				} );

				return {
					id			: block.id,
					controls	: block.controls,
					held		: held,
					build		: function( body ) { held.forEach( function( control ) { control.build( body ) } ) },
					says		: function() { return builder._said( held.map( function( control ) { return control.says() } ) ) },
				};
			} ).filter( function( block ) { return block.held.length > 0 } );
		},

		/**
		 *	The block of the table that lists a control: the one a status of a frame opens the dialog on, whichever block the table has put the control in
		 *
		 *	@param		{string}		kind							section, column or component
		 *	@param		{string}		control						The name the table lists the control under
		 *
		 *	@return		{string|undefined}						The id of the block, undefined where the table lists the control in none
		 */
		_blockOf : function( kind, control ) {

			const block = Nino.admin.builder.BLOCKS[kind].find( function( candidate ) { return candidate.controls.indexOf( control ) !== -1 } );

			return block === undefined ? undefined : block.id;
		},

		/**
		 *	What a block says in its head: the words that are not empty, joined by a dot, each once
		 *
		 *	@param		{Array}			words
		 *
		 *	@return		{string}
		 */
		_said : function( words ) {
			return words.filter( function( word, at ) { return word !== '' && words.indexOf( word ) === at } ).join(' · ');
		},

		/**
		 *	A select of the design system, whose change is the caller's
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{Array}			options					[ { value, label } ]
		 *	@param		{string}		value
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_selectField : function( label, hint, options, value, onChange ) {

			const field = Nino.adminUi.selectField( { key : '', label : label, hint : '', options : options, value : value, onChange : onChange } );

			return Nino.admin.builder._hinted( field, hint, label );
		},

		/**
		 *	The sentence a field has to say, as a "?" that carries it (see _help()) - none under the field. A field with nothing to say is the field itself;
		 *	one with a sentence stands in a row with its button, which is not inside the label of the field: a label holds no button, and the name of the
		 *	control is the label's words alone
		 *
		 *	@param		{Element}		field
		 *	@param		{string}		hint
		 *	@param		{string}		[name]						What the field is called, for the button
		 *
		 *	@return		{Element}									The field, or its row
		 */
		_hinted : function( field, hint, name ) {

			const builder = Nino.admin.builder;

			if( hint === '' || hint === undefined || hint === null )
				return field;

			const row = builder._el( 'div', 'builder-row' );

			row.appendChild( field );
			row.appendChild( builder._help( hint, name ) );

			return row;
		},

		/**
		 *	The mark of a sentence a control has to say: a button "?" that has the sentence as a tooltip - on hover and on focus, and by a touch -, and as its
		 *	description (aria-describedby), so that a screen reader reads it with the button. Escape closes the tooltip and nothing else, and a tooltip the
		 *	pointer is over stays while it is. The tooltip is drawn where the button is on the window and kept inside it: the region of a dialog scrolls
		 *	and would cut it. The pattern is the Builder's own (.builder-help); every panel of the workbench has its hints under the fields, and one for all of
		 *	them is for the kernel to make
		 *
		 *	@param		{string}		text							The sentence
		 *	@param		{string}		[name]						What the button is for, said to whoever does not see it: the name of the field
		 *
		 *	@return		{Element}									The wrap of the button and its tooltip
		 */
		_help : function( text, name ) {

			const builder = Nino.admin.builder;
			const wrap = builder._el( 'span', 'builder-help' );
			const button = builder._el( 'button', 'builder-help-btn', '?' );
			const tip = builder._el( 'span', 'builder-help-tip', text );
			const id = 'builder-tip-'+ ( ++builder._helps );
			const hide = function() { tip.hidden = true };
			const show = function() {

				tip.hidden = false;

				if( typeof button.getBoundingClientRect !== 'function' )
					return;

				const at = button.getBoundingClientRect();
				const room = 8;
				const left = Math.max( room, Math.min( at.left, wn.innerWidth - tip.offsetWidth - room ) );
				const below = at.bottom + 6;

				tip.style.left = left+ 'px';
				tip.style.top = ( below + tip.offsetHeight + room > wn.innerHeight ? Math.max( room, at.top - tip.offsetHeight - 6 ) : below )+ 'px';
			};

			button.type = 'button';
			button.setAttribute( 'aria-label', typeof name === 'string' && name !== '' ? Nino.adminUi.format( Nino.content.getText('/_admin/builder/label/help-of'), name ) : Nino.content.getText('/_admin/builder/label/help') );
			button.setAttribute( 'aria-describedby', id );
			tip.id = id;
			tip.setAttribute( 'role', 'tooltip' );
			tip.hidden = true;

			wrap.addEventListener( 'mouseenter', show );
			wrap.addEventListener( 'mouseleave', function() { if( dc.activeElement !== button ) hide() } );
			button.addEventListener( 'focus', show );
			button.addEventListener( 'blur', hide );
			button.addEventListener( 'click', function() { if( tip.hidden === true ) show(); else hide() } );
			wrap.addEventListener( 'keydown', function( ev ) {

				if( ev.key !== 'Escape' || tip.hidden === true )
					return;

				// The tooltip is what Escape closes: the dialog stays
				ev.preventDefault();
				ev.stopPropagation();
				hide();
			} );

			wrap.appendChild( button );
			wrap.appendChild( tip );
			// What describes a group that has no button of its own to carry it
			wrap.tipId = id;

			return wrap;
		},

		// How many tooltips were made: the number in the id that joins a button to its sentence
		_helps : 0,

		/**
		 *	A switch of the design system
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{boolean}		checked
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_switchField : function( label, hint, checked, onChange ) {

			const field = Nino.adminUi.switchField( { key : '', checked : checked, label : label, hint : '' } );
			const input = field.querySelector('input');

			input.addEventListener( 'change', function() { onChange( input.checked ) } );

			return Nino.admin.builder._hinted( field, hint, label );
		},

		/**
		 *	A plain checkbox with its label beside it, for a flag that is better drawn small than as a switch: the attribute of a component says so
		 *	(its input)
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{boolean}		checked
		 *	@param		{Function}	onChange					Called with whether it is checked
		 *
		 *	@return		{Element}
		 */
		_checkField : function( label, hint, checked, onChange ) {

			const builder = Nino.admin.builder;
			const field = builder._el( 'div', 'builder-check-field' );
			const item = builder._el( 'label', 'builder-check' );
			const box = builder._el('input');

			box.type = 'checkbox';
			box.checked = checked === true;
			box.addEventListener( 'change', function() { onChange( box.checked ) } );
			item.appendChild( box );
			item.appendChild( builder._el( 'span', '', label ) );
			field.appendChild( item );

			return builder._hinted( field, hint, label );
		},

		/**
		 *	A whole number in bounds; what is emptied is null
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{number|null}	value
		 *	@param		{number}		min
		 *	@param		{number}		max
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_numberField : function( label, hint, value, min, max, onChange ) {

			const field = Nino.adminUi.numberField( { key : '', value : value === null ? '' : value, min : min, max : max, label : label, hint : '' } );
			const input = field.querySelector('input');

			input.addEventListener( 'input', function() {
				const number = parseInt( input.value, 10 );
				onChange( isNaN( number ) === true ? null : number );
			} );

			return Nino.admin.builder._hinted( field, hint, label );
		},

		/**
		 *	A line of text, or several lines
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{string}		value
		 *	@param		{Function}	onChange					Called on every input
		 *	@param		{Object}		[more]						{ multiline, readonly, onCommit, list, placeholder } - onCommit on the change of the value, list an id of a <datalist>, placeholder the words an empty field shows
		 *
		 *	@return		{Element}
		 */
		_textField : function( label, hint, value, onChange, more ) {

			const builder = Nino.admin.builder;
			const options = more || {};
			const field = builder._el( 'label', 'nino-admin-field' );
			const control = builder._el( options.multiline === true ? 'textarea' : 'input', 'nino-admin-input' );

			field.appendChild( builder._el( 'span', '', label ) );

			if( options.multiline !== true )
				control.type = 'text';

			control.value = value === null || value === undefined ? '' : String( value );
			control.autocomplete = 'off';
			control.spellcheck = false;
			control.readOnly = options.readonly === true;

			if( typeof options.list === 'string' )
				control.setAttribute( 'list', options.list );

			if( typeof options.placeholder === 'string' && options.placeholder !== '' )
				control.placeholder = options.placeholder;

			control.addEventListener( 'input', function() { onChange( control.value ) } );

			if( typeof options.onCommit === 'function' )
				control.addEventListener( 'change', function() { options.onCommit( control.value, control ) } );

			field.appendChild( control );

			const row = builder._hinted( field, hint, label );

			row.control = control;

			return row;
		},

		/**
		 *	A choice of a few, as buttons that are one field: a group of radio buttons,
		 *	which a keyboard and a screen reader read as the field it is. Each is a word
		 *	or, where the option has an icon, the icon with the word for its title and its
		 *	label
		 *
		 *	@param		{string}		label
		 *	@param		{Array}			options						[ { value, label, icon } ]
		 *	@param		{string}		value
		 *	@param		{Function}	onChange					Called with the value of the one that was chosen
		 *	@param		{string}		[hint]
		 *
		 *	@return		{Element}									The field; setDisabled( boolean ) greys it and has it take no choice
		 */
		_segmentField : function( label, options, value, onChange, hint ) {

			const builder = Nino.admin.builder;
			const field = builder._el( 'div', 'builder-segment-field' );
			const group = builder._el( 'div', 'builder-segment' );
			const name = 'builder-segment-'+ ( ++builder._segments );
			const inputs = [];

			group.setAttribute( 'role', 'radiogroup' );
			group.setAttribute( 'aria-label', label );

			options.forEach( function( option ) {

				const item = builder._el( 'label', 'builder-segment-option' );
				const input = builder._el('input');

				input.type = 'radio';
				input.name = name;
				input.value = option.value;
				input.checked = option.value === value;
				input.addEventListener( 'change', function() {
					if( input.checked === true )
						onChange( option.value );
				} );
				item.appendChild( input );

				if( option.icon === undefined )
					item.appendChild( builder._el( 'span', 'builder-segment-face', option.label ) );
				else {
					item.title = option.label;
					input.setAttribute( 'aria-label', option.label );
					item.appendChild( builder._icon( option.icon ) );
				}

				inputs.push( input );
				group.appendChild( item );
			} );

			field.appendChild( builder._el( 'span', 'builder-segment-label', label ) );
			field.appendChild( group );

			const row = builder._hinted( field, hint, label );

			row.setDisabled = function( disabled ) {
				group.classList.toggle( 'is-disabled', disabled === true );
				inputs.forEach( function( input ) { input.disabled = disabled === true } );
			};

			return row;
		},

		/**
		 *	The icons of the options of an attribute that is drawn as the icons of an alignment: the set of words they are - where a text stands,
		 *	where the content stands in a box, where a stack places what is in it, the three the kernel has icons for (see ALIGN_ICONS) - found
		 *	by the words, and never by the name of the attribute
		 *
		 *	@param		{Array}			options						The values of the attribute
		 *
		 *	@return		{Object|null}							word => icon, null where the words are none of the three sets
		 */
		_alignIcons : function( options ) {

			const builder = Nino.admin.builder;
			const words = ( options || [] ).filter( function( option ) { return option !== '' } );
			const within = function( set ) { return words.length > 0 && words.every( function( word ) { return set.indexOf( word ) !== -1 } ) };

			// The word centre is in two of the sets: a choice of nothing else is a text's
			if( within( [ 'left', 'center', 'right' ] ) === true )
				return builder.ALIGN_ICONS.text;

			if( within( [ 'top', 'middle', 'bottom', 'last-bottom' ] ) === true )
				return builder.ALIGN_ICONS.vertical;

			return within( [ 'start', 'center', 'end' ] ) === true ? builder.ALIGN_ICONS.stackAlign : null;
		},

		/**
		 *	The values of an alignment as the options of a segment: the icon of each
		 *	where it has one (see ALIGN_ICONS), its word for the rest
		 *
		 *	@param		{string}		setting						text, rowAlign or stackAlign
		 *	@param		{string}		group							What _optionLabel() has the words of
		 *	@param		{Array}			values
		 *
		 *	@return		{Array}
		 */
		_alignOptions : function( setting, group, values ) {

			const builder = Nino.admin.builder;

			return values.map( function( value ) { return { value : value, label : builder._optionLabel( group, value ), icon : builder.ALIGN_ICONS[setting][value] } } );
		},

		/**
		 *	The colour of a section: a select with words, and beside it a patch of the
		 *	colour chosen. A select cannot paint a colour in each of its options, so the
		 *	patch follows the choice. A column has a select of its own, and so has a component
		 *	that paints a ground (see _attributeField()): none of its own,
		 *	which leaves it as the section it is in, counter and the colours - and its
		 *	patch is the ground it ends up with: the section's, or the counter of that
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{string}		value
		 *	@param		{Function}	onChange
		 *	@param		{string}		[around]					For a column: the colour of the section it is in
		 *
		 *	@return		{Element}
		 */
		_colorField : function( label, hint, value, onChange, around ) {

			const builder = Nino.admin.builder;
			const column = around !== undefined;
			const row = builder._el( 'div', 'builder-with-swatch' );
			const swatch = builder._el( 'span', 'builder-swatch' );
			const paint = function( color ) {

				const shown = column === false ? color : ( color === '' ? around : builder._paints( color, around ) );
				const ground = builder._ground( shown );

				swatch.dataset.color = shown === '' ? 'plain' : shown;

				if( ground === '' )
					swatch.style.removeProperty('--builder-swatch');
				else
					swatch.style.setProperty( '--builder-swatch', ground );
			};

			swatch.setAttribute( 'aria-hidden', 'true' );
			paint( value );

			row.appendChild( builder._selectField( label, '', column === true ? builder._columnColors() : builder._options( 'color', builder.COLORS ), value, function( color ) {
				paint( color );
				onChange( color );
			} ) );
			row.appendChild( swatch );

			return builder._hinted( row, hint, label );
		},

		/**
		 *	The words of a ground: counter has its own, the colours of a section the ones the select of a section has
		 *
		 *	@param		{string}		value							counter, or a colour of a section but ''
		 *
		 *	@return		{string}
		 */
		_groundLabel : function( value ) {
			return value === 'counter' ? Nino.content.getText('/_admin/builder/color/counter') : Nino.admin.builder._optionLabel( 'color', value );
		},

		/**
		 *	What a column may paint as the options of a select: none of its own, which is the section it is in, counter,
		 *	and the colours of a section
		 *
		 *	@return		{Array}										[ { value, label } ]
		 */
		_columnColors : function() {

			const builder = Nino.admin.builder;

			return builder.COL_COLORS.map( function( value ) {
				return { value : value, label : value === '' ? Nino.content.getText('/_admin/builder/color/surroundings') : builder._groundLabel( value ) };
			} );
		},

		/**
		 *	The ground a colour paints, read at the time out of the custom properties of
		 *	Nino.css that the preview has. The workbench does not load Nino.css, so in the
		 *	panel the preview has none of them, and the patch is the grey the frames of the
		 *	preview are painted in (see admin.css): a preview that carries the site's
		 *	properties shows the site's colours, any other a neutral one
		 *
		 *	@param		{string}		color							A value of COLORS
		 *
		 *	@return		{string}									'' where it is not known
		 */
		_ground : function( color ) {

			const preview = dc.getElementById('builder-preview');
			const property = Nino.admin.builder.GROUNDS[color];

			if( typeof wn.getComputedStyle !== 'function' || preview === null || property === undefined )
				return '';

			return wn.getComputedStyle( preview ).getPropertyValue( property ).trim();
		},

		/**
		 *	The focus of the picture behind a section: which of nine places of it stays
		 *	when the picture is cropped. A switch says there is none, and nine radio
		 *	buttons in a grid, one for each place, say where it is
		 *
		 *	@param		{number|null}	focus							1 to 9, null for none
		 *	@param		{boolean}		enabled						Whether there is a picture to have one
		 *	@param		{Function}	onChange					Called with 1 to 9, or null
		 *	@param		{string}		[label]						What the grid is called to whoever does not see it: the focus
		 *
		 *	@return		{Element}
		 */
		_focusField : function( focus, enabled, onChange, label ) {

			const builder = Nino.admin.builder;
			const field = builder._el( 'div', 'builder-focus' );
			const grid = builder._el( 'div', 'builder-focus-grid' );
			const name = 'builder-segment-'+ ( ++builder._segments );
			const inputs = [];
			let current = focus;

			// The grid is there to choose a place in, so it is off where there is no picture or no focus
			const sync = function() {

				grid.classList.toggle( 'is-disabled', enabled === false || current === null );

				inputs.forEach( function( input, at ) {
					input.disabled = enabled === false || current === null;
					input.checked = current === at + 1;
				} );
			};

			const none = builder._switchField( Nino.content.getText('/_admin/builder/source/no-focus'), Nino.content.getText('/_admin/builder/hint/focus'), focus === null, function( off ) {
				current = off === true ? null : 5;
				onChange( current );
				sync();
			} );

			grid.setAttribute( 'role', 'radiogroup' );
			grid.setAttribute( 'aria-label', label ?? Nino.content.getText('/_admin/builder/source/focus') );

			builder.FOCUS.forEach( function( place, at ) {

				const item = builder._el( 'label', 'builder-focus-place' );
				const input = builder._el('input');
				const word = Nino.content.getText( '/_admin/builder/focus/'+ place );

				input.type = 'radio';
				input.name = name;
				input.value = String( at + 1 );
				input.setAttribute( 'aria-label', word );
				input.addEventListener( 'change', function() {
					current = at + 1;
					onChange( current );
				} );
				item.title = word;
				item.appendChild( input );
				item.appendChild( builder._el( 'span', 'builder-focus-dot' ) );
				inputs.push( input );
				grid.appendChild( item );
			} );

			builder._one( none, 'input' ).disabled = enabled === false;
			field.appendChild( none );
			field.appendChild( grid );
			sync();

			return field;
		},

		/**
		 *	The frame above or below a page: one of the project's, or none
		 *
		 *	@param		{string}		label
		 *	@param		{Array}			frames
		 *	@param		{string}		value
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_frameField : function( label, frames, value, onChange ) {

			const options = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ].concat( frames.map( function( frame ) { return { value : frame, label : frame } } ) );

			return Nino.admin.builder._selectField( label, '', options, value, onChange );
		},

		/**
		 *	One field of a setting of a node, from what the form says of it: the
		 *	control by the setting's type, its words and the place it writes to
		 *
		 *	@param		{Object}		target						The object the value is kept in
		 *	@param		{Object}		field							{ key, type, label, hint, group, values, items, min, max, around } - the type is
		 *															bool, int, string, color (around being the colour of the section, for the colour
		 *															of a column), segment (its items being the options of the segment) or else a
		 *															select over the values
		 *	@param		{Function}	[after]						Called with the value after it was written
		 *
		 *	@return		{Element}
		 */
		_setting : function( target, field, after ) {

			const builder = Nino.admin.builder;
			const label = Nino.content.getText( field.label );
			const hint = field.hint === undefined ? '' : Nino.content.getText( field.hint );
			const write = function( value ) {

				target[field.key] = value;

				if( typeof after === 'function' )
					after( value );

				builder._changed();
			};

			const keyed = function( control ) { return builder._keyed( control, field.key ) };

			if( field.type === 'bool' )
				return keyed( builder._switchField( label, hint, target[field.key] === true, write ) );

			if( field.type === 'int' )
				return keyed( builder._numberField( label, hint, target[field.key], field.min, field.max, write ) );

			if( field.type === 'string' )
				return keyed( builder._textField( label, hint, target[field.key], write ) );

			if( field.type === 'color' )
				return keyed( builder._colorField( label, hint, String( target[field.key] ), write, field.around ) );

			if( field.type === 'segment' )
				return keyed( builder._segmentField( label, field.items, String( target[field.key] ), write, hint ) );

			return keyed( builder._selectField( label, hint, builder._options( field.group, field.values ), String( target[field.key] ), write ) );
		},

		// ---------------------------------------------------------- The radius of a surface

		/**
		 *	The kind of surface a component is, as the registry says it (surface): card, image, or none for a component that has no surface of its own
		 *	with a radius. The panel knows no component by name: what is a surface, and of which kind, is the registry's word
		 *
		 *	@param		{Object}		schema						One entry of the registry's components
		 *
		 *	@return		{string}									The kind, '' where there is none
		 */
		_surfaceOf : function( schema ) {
			return typeof schema.surface === 'string' ? schema.surface : '';
		},

		/**
		 *	The strength the page gives a kind of surface where an element says nothing: the registry's radii (the markers --radius-<kind>-default of the
		 *	page's style sheets, read by the server; the kernel's own where no Design sets them, all of them standard)
		 *
		 *	@param		{string}		kind							cover, row, column, tile, card or image
		 *
		 *	@return		{string}									standard or alternative
		 */
		_radiusDefault : function( kind ) {
			return ( Nino.admin.builder._registry.radii || {} )[kind] === 'alternative' ? 'alternative' : 'standard';
		},

		/**
		 *	The strength in force on an element: the one it says - the setting, which is the class nino-radius or nino-radius-alt - or, where it says none,
		 *	the default of its kind
		 *
		 *	@param		{string}		setting						'', standard or alternative
		 *	@param		{string}		kind
		 *
		 *	@return		{string}									standard or alternative
		 */
		_radiusIn : function( setting, kind ) {
			return setting === 'standard' || setting === 'alternative' ? setting : Nino.admin.builder._radiusDefault( kind );
		},

		/**
		 *	The toggle "alternative radius" of a surface - the picture of a cover that keeps the room of the page around it, what the row of a section paints,
		 *	what a column paints, a card, a picture - the same switch in all of them. The page has two strengths of radius, the standard and the alternative, and a
		 *	default of its own for each kind of surface; the toggle is on where the strength in force is the alternative one. That is the strength the element
		 *	says with its class, or - where it has none - the default of its kind (registry.radii). Switching it writes the class that deviates from the default of
		 *	the kind, nino-radius-alt where the default is the standard strength and nino-radius where it is the alternative one, and removes the class where the
		 *	strength asked for is the default itself: so an element the editor never touched, or one that was switched back, follows a later change of the default in
		 *	the Design, and an element the editor set keeps what was set. The toggle sleeps where the class would mean nothing - where the element paints no ground
		 *	and no picture - and keeps what it holds: the setting waits in the model, as the setting of any form that sleeps
		 *
		 *	@param		{Object}		holder						Where the setting is kept: the settings of a section, a column, a component
		 *	@param		{string}		key								radius, or rowRadius for the row of a section
		 *	@param		{string}		kind							cover, row, column, tile, card or image: the kind of surface, which has its default
		 *	@param		{Function}	awake							Whether the element paints a surface now
		 *
		 *	@return		{Element}									The switch, with syncRadius(): draws its state and its sleep again, for the form that changes what the element paints
		 */
		_radiusToggle : function( holder, key, kind, awake ) {

			const builder = Nino.admin.builder;
			const field = builder._switchField( Nino.content.getText('/_admin/builder/common/radius-alt'), Nino.content.getText('/_admin/builder/hint/radius-alt'), builder._radiusIn( holder[key], kind ) === 'alternative', function( on ) {

				const asked = on === true ? 'alternative' : 'standard';

				holder[key] = asked === builder._radiusDefault( kind ) ? '' : asked;
				builder._changed();
			} );
			const box = builder._one( field, 'input' );

			field.syncRadius = function() {
				box.checked = builder._radiusIn( holder[key], kind ) === 'alternative';
				box.disabled = awake() === false;
			};
			field.syncRadius();

			return builder._keyed( field, key );
		},

		// ---------------------------------------------------------- The hover of a picture

		/**
		 *	The toggle "hover effect" of a picture - the frame of [image] and the picture of a card alike, any component whose source is an image. The effect is the page's: the Design
		 *	chooses its kind and its strength for the whole site (Motion, Page), and a picture only takes part in it, which it says with the class nino-hover. The toggle is on where the
		 *	picture has the class; switching it writes the class and takes it away, and the classes the editor wrote stay as they were. Nothing here depends on the rest of the form: a picture
		 *	may take part with any ratio, any focus, any link
		 *
		 *	@param		{Object}		holder						The component
		 *
		 *	@return		{Element}									The switch
		 */
		_hoverToggle : function( holder ) {

			const builder = Nino.admin.builder;

			return builder._keyed( builder._switchField( Nino.content.getText('/_admin/builder/common/hover'), Nino.content.getText('/_admin/builder/hint/hover'), holder.hover === 'on', function( on ) {

				holder.hover = on === true ? 'on' : '';
				builder._changed();
			} ), 'hover' );
		},

		// ---------------------------------------------------------- The settings

		/**
		 *	Open the form of what a path names
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		[block]						The block of the form to open, for a form that has blocks: the one that was open last by default
		 *
		 *	@return		void
		 */
		_openSettings : function( path, block ) {

			const builder = Nino.admin.builder;
			const kind = builder._kind( builder._doc.model, path );

			// Under 38 rem the editor is for reading: a form would write
			if( builder._mutable() === false )
				return;

			if( kind === 'template' )
				return builder._templateDialog();

			if( kind === 'section' )
				return builder._sectionDialog( path, block );

			if( kind === 'col' )
				return builder._colDialog( path, block );

			if( kind === 'stack' )
				return builder._colDialog( path.slice( 0, 2 ), builder._blockOf( 'column', 'stack' ) );

			if( kind === 'html' )
				return builder._editBlock( path );

			builder._componentDialog( path, block );
		},

		/**
		 *	What a refused save said of the block a form belongs to, over the form
		 *
		 *	@param		{Element}		pane
		 *	@param		{number}		index					The block's index
		 *
		 *	@return		void
		 */
		_blamed : function( pane, index ) {

			const builder = Nino.admin.builder;
			const sentences = builder._blame( builder._doc.model, builder._problems ).blocks[index] || [];

			sentences.slice().reverse().forEach( function( sentence ) { pane.insertBefore( builder._el( 'p', 'nino-admin-error', sentence ), pane.firstChild ) } );
		},

		/**
		 *	The form of the template, in groups: its name, with its file and the slug the
		 *	text keys of the page are made of, which are text; the frames above and below
		 *	it; and its animation - whether it animates its sections, and, folded away, what
		 *	kind of animation that is. Turning the switch of the animation asks, as the
		 *	dialog closes, whether the sections that follow the template follow the switch
		 *
		 *	@return		void
		 */
		_templateDialog : function() {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const registry = builder._registry;
			const before = doc.model.animate === true;
			const followers = builder._likeSections( doc.model );

			builder._dialog( {
				title	: Nino.content.getText('/_admin/builder/tree/template'),
				build	: function( body ) {

					const general = builder._group( body, Nino.content.getText('/_admin/builder/group/general') );

					general.appendChild( builder._textField( Nino.content.getText('/_admin/builder/label/name'), Nino.content.getText('/_admin/builder/hint/name'), doc.model.name, function( value ) { doc.model.name = value; builder._changed() } ) );
					general.appendChild( builder._textField( Nino.content.getText('/_admin/builder/label/file'), Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/file'), doc.file.replace( /^page-/, '' ), doc.file ), doc.file+ '.tpl', function() {}, { readonly : true } ) );

					const frames = builder._group( body, Nino.content.getText('/_admin/builder/group/frames') );

					frames.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/header'), registry.headers, doc.model.header, function( value ) { doc.model.header = value; builder._changed() } ) );
					frames.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/footer'), registry.footers, doc.model.footer, function( value ) { doc.model.footer = value; builder._changed() } ) );

					const animation = builder._group( body, Nino.content.getText('/_admin/builder/group/animation') );

					animation.appendChild( builder._switchField( Nino.content.getText('/_admin/builder/template/animate'), Nino.content.getText('/_admin/builder/hint/animate'), doc.model.animate === true, function( checked ) { doc.model.animate = checked; builder._changed() } ) );
					builder._animationFields( builder._group( animation, Nino.content.getText('/_admin/builder/group/kind'), true ), doc.model, { rest : false, hint : '/_admin/builder/hint/vpa-template', speeds : 'wrap-speed' } );
				},
				onClose	: function() { builder._askFollow( followers, before ) },
			} );
		},

		/**
		 *	The sections that follow the template are asked whether they follow it where its
		 *	switch was turned: on, the ones without an animation are given the bare one; off,
		 *	the ones with just that lose it. Yes is the way the question is put, and the
		 *	sections with an animation of their own are none of its business
		 *
		 *	@param		{Array}				followers				The sections that followed the template, before the switch was turned
		 *	@param		{boolean}			before					Whether the template animated its sections
		 *
		 *	@return		void
		 */
		_askFollow : function( followers, before ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			if( doc === null || followers.length === 0 || ( doc.model.animate === true ) === before )
				return;

			Nino.adminUi.choiceDialog( {
				title		: Nino.content.getText('/_admin/builder/state/vpa-follow'),
				message	: Nino.adminUi.format( Nino.content.getText( doc.model.animate === true ? '/_admin/builder/confirm/vpa-on' : '/_admin/builder/confirm/vpa-off' ), followers.length ),
				choices	: [
					{ value : 'follow', label : Nino.content.getText('/_admin/builder/confirm/vpa-yes'), kind : 'primary' },
					{ value : 'stay', label : Nino.content.getText('/_admin/builder/confirm/vpa-no'), kind : 'secondary' },
				],
				onChoose	: function( choice ) {

					if( choice !== 'follow' )
						return;

					builder._followSections( doc.model, followers );
					builder._changed();
				},
			} );
		},

		/**
		 *	The form of a section, in the blocks the table has for it (BLOCKS.section): its surface - the line and the ground, the background inside and the radius, and then, in a
		 *	group of its own, the background image: the picture with its focus, the cover and what stands inside it, the parallax, the dimming, the offset and the arrow - its
		 *	spacing - the whitespace of the page and the box of its sides, with the box of its row under it -, its width and its alignment - how wide the row is and where it stands,
		 *	where the text and the columns are put and where the row stands in a cover -, its loop, which makes a section of every element, and its properties: the animation, the
		 *	classes of its own and the id. The name of the section stands in the title as well, where it is changed too. Each field writes into the model at once
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		[block]						The block to open: the one that was open last in a dialog of a section, by default
		 *
		 *	@return		void
		 */
		_sectionDialog : function( path, block ) {

			const builder = Nino.admin.builder;

			builder._dialog( {
				// The title is the kind of the section: Content or Tiles
				title		: Nino.content.getText( builder._doc.model.blocks[path[0]].settings.tiles === true ? '/_admin/builder/tree/tiles' : '/_admin/builder/tree/content' ),
				rename	: path,
				blame		: path[0],
				returnTo	: path,
				kind		: 'section',
				block		: block,
				blocks	: builder._blocksOf( 'section', builder._sectionControls( path ) ),
			} );
		},

		/**
		 *	The controls of the form of a section, by the name the table lists them under (see BLOCKS): { keys, build( body ), says() } each - the settings it writes, what it draws into
		 *	the body of the block it stands in, and what the head of that block says of it where it is not the default. A control that writes several settings is the same entry under
		 *	each of them. The controls reach into one another where a choice of one changes what another shows at once - the colour wakes the background inside, a line its brand
		 *	colour - by the name, and where it changes what others show the blocks are drawn again
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Object}									name of the control => its entry (see _blocksOf())
		 */
		_sectionControls : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const settings = section.settings;
			const word = function( key ) { return Nino.content.getText( '/_admin/builder/'+ key ) };
			const setting = function( field, after ) { return builder._setting( settings, field, after ) };
			const has = function() { return section.background !== null && section.background !== undefined };
			// Which of the three the animation is: like the template, off or its own - kept while the form is open - and the controls of the animation it has then
			const animation = { mode : builder._animationMode( builder._doc.model.animate === true, settings ) };
			const animations = builder._animationControls( settings, {} );
			// The fields that a choice of another control changes at once, by the name of the control: the one that was drawn last
			const fields = {};
			// A choice that other controls show: the blocks are drawn again
			const again = function() { builder._refreshControls() };
			// Where the picture behind the section and what depends on it stand: one group under its heading, made once per body however many controls build into it
			const picture = function( body ) { return builder._groupIn( body, 'background-image' ) };
			// There is nothing to put inside where the section has neither a colour nor a picture, and a cover or a parallax is the section's own picture
			const possible = function() { return settings.cover === null && settings.parallax !== true && ( settings.color !== '' || has() === true ) };
			// What is on with none of that is no more than the file can say, and it goes off - and with it the corners are the cover's again, not the row's
			const settle = function() {

				if( possible() === false && settings.cover === null && settings.parallax !== true && settings.inner === true )
					settings.inner = false;
			};
			// The switch of the background inside shows what the colour made of it, and the blocks are drawn again where that changed what they show
			const syncInner = function() {

				const was = settings.inner;

				settle();

				if( fields.inner !== undefined ) {
					builder._one( fields.inner, 'input' ).checked = settings.inner === true;
					builder._one( fields.inner, 'input' ).disabled = possible() === false && settings.inner !== true;
				}

				if( was !== settings.inner )
					again();
			};
			const radiusSaid = function( radius ) { return radius === '' ? '' : word( radius === 'alternative' ? 'common/radius-alt' : 'common/radius-standard' ) };
			// What an alignment says of itself in the head, by the words of its group
			const alignSaid = function( key, group ) { return settings[key] === '' ? '' : builder._optionLabel( group, settings[key] ) };
			// A field of the animation that the section has where its animation is its own: like the template and off leave nothing to choose
			const animated = function( key ) {
				return {
					keys	: [ key ],
					build	: function( body ) {
						if( animation.mode === 'own' )
							animations[key].build( builder._groupIn( body, 'animation' ) );
					},
					says	: function() { return '' },
				};
			};
			const sides = {
				keys	: builder.SIDES,
				build	: function( body ) { body.appendChild( builder._boxField( settings, { name : word('tree/section'), hint : word('hint/spacing'), outside : [ 'mt', 'mb' ], inside : [ 'pt', 'pb', 'pl', 'pr' ] } ) ) },
				says	: function() { return builder._boxSaid( settings, [ 'mt', 'mb' ], [ 'pt', 'pb', 'pl', 'pr' ] ) },
			};
			const rowSides = {
				keys	: builder.ROW_SIDES,
				build	: function( body ) {

					// The row of the section is the frame its columns stand in: the room inside it, which a background inside paints. The width of the row to the edge is the same two sides
					body.appendChild( builder._boxField( settings, { name : word('spacing/row'), hint : word('hint/spacing-row'), outside : null, inside : builder.ROW_SIDES, after : function( key ) {
						if( key === 'rowPl' || key === 'rowPr' )
							builder._refreshControls( 'width' );
					} } ) );
				},
				says	: function() { return builder._boxSaid( settings, null, builder.ROW_SIDES, word('spacing/row') ) },
			};
			const offset = {
				keys	: [ 'rowBgPl', 'rowBgPr' ],
				build	: function( body ) {

					// How far in from the sides the picture inside the row starts: there is one only with the background inside
					const inset = builder._offsetField( settings, [ 'rowBgPl', 'rowBgPr' ], word('section/offset'), word('hint/offset') );

					inset.setDisabled( settings.inner !== true || has() === false );
					picture( body ).appendChild( inset );
				},
				says	: function() { return builder._offsetSaid( settings, [ 'rowBgPl', 'rowBgPr' ], word('section/offset') ) },
			};

			settle();

			const controls = {
				color : {
					keys	: [ 'color' ],
					build	: function( body ) { body.appendChild( setting( { key : 'color', type : 'color', label : '/_admin/builder/section/color' }, syncInner ) ) },
					says	: function() { return settings.color === '' ? '' : builder._optionLabel( 'color', settings.color ) },
				},
				border : {
					keys	: [ 'border' ],
					build	: function( body ) {

						// The brand colour is the colour of the line, and a line of no strength draws nothing: the switch waits for a strength. The model keeps what it holds
						const waits = function( strength ) {
							if( fields.borderPrimary !== undefined )
								builder._one( fields.borderPrimary, 'input' ).disabled = strength === '';
						};

						body.appendChild( setting( { key : 'border', type : 'select', label : '/_admin/builder/section/border', group : 'border', values : builder.BORDERS }, waits ) );
					},
					says	: function() { return settings.border === '' ? '' : word('section/border')+ ' '+ builder._optionLabel( 'border', settings.border ).toLowerCase() },
				},
				borderPrimary : {
					keys	: [ 'borderPrimary' ],
					build	: function( body ) {

						fields.borderPrimary = setting( { key : 'borderPrimary', type : 'bool', label : '/_admin/builder/section/border-primary' } );
						builder._one( fields.borderPrimary, 'input' ).disabled = settings.border === '';
						body.appendChild( fields.borderPrimary );
					},
					says	: function() { return settings.border !== '' && settings.borderPrimary === true ? word('section/border-primary') : '' },
				},
				background : {
					keys	: [ 'background' ],
					build	: function( body ) { picture( body ).appendChild( builder._backgroundField( path, again ) ) },
					says	: function() { return has() === true ? word('source/background-image') : '' },
				},
				cover : {
					keys	: [ 'cover' ],
					build	: function( body ) {

						const looping = builder._looping( section );
						// A cover is a switch and its height in percent of the window's beside it. A section that makes sections of elements has a call for a tag, which has no attribute for a height
						const height = builder._numberField( word('section/cover-height'), '', settings.cover, 1, 100, function( percent ) {

							// The cover is never lost by a field on its way to a number: an emptied one, or one out of bounds, leaves the height as it is
							if( percent === null || percent < 1 || percent > 100 )
								return;

							settings.cover = percent;
							builder._changed();
						} );
						const cover = builder._keyed( builder._switchField( word('section/cover'), word('hint/cover'), settings.cover !== null, function( on ) {

							// A cover is 90 high to begin with, what Nino.ui.js makes of one that names no height
							settings.cover = on === true ? ( settings.cover ?? 90 ) : null;

							// A cover is the section's own picture, and the background cannot stand inside. With the cover - and with no parallax to be one - goes the room around it, and a
							// scrim over no picture: a section that is neither has the scrim of its own, for a picture it has
							if( on === true )
								settings.inner = false;
							else if( settings.parallax !== true ) {
								settings.padded = false;

								if( has() === false )
									settings.dim = false;
							}

							builder._changed();
							again();
						} ), 'cover' );

						// Once the typing is over the field shows the height in force: 150 on its way to nothing stays in the model as 15, and the field says so
						builder._one( height, 'input' ).addEventListener( 'change', function() {

							const input = builder._one( height, 'input' );
							const percent = parseInt( input.value, 10 );

							if( isNaN( percent ) === true || percent < 1 || percent > 100 )
								input.value = settings.cover === null ? '' : settings.cover;
						} );
						builder._one( height, 'input' ).disabled = settings.cover === null || looping === true;
						builder._keyed( height, 'cover' );
						picture( body ).appendChild( cover );
						picture( body ).appendChild( height );
					},
					says	: function() { return settings.cover === null ? '' : word('section/cover')+ ' '+ settings.cover+ ' %' },
				},
				parallax : {
					keys	: [ 'parallax' ],
					build	: function( body ) {

						const parallax = builder._keyed( builder._switchField( word('section/parallax'), '', settings.parallax === true, function( on ) {

							settings.parallax = on;

							// A parallax is the section's own picture, and the background cannot stand inside. Without it - and with no cover to be one - goes the room around it, and the
							// scrim over no picture
							if( on === true )
								settings.inner = false;
							else if( settings.cover === null ) {
								settings.padded = false;

								if( has() === false )
									settings.dim = false;
							}

							builder._changed();
							again();
						} ), 'parallax' );

						// A switch that is on can be switched off whatever the picture: a parallax a template wrote without one, or that lost its picture, must not be stuck
						builder._one( parallax, 'input' ).disabled = has() === false && settings.parallax !== true;
						picture( body ).appendChild( parallax );
					},
					says	: function() { return settings.parallax === true ? word('section/parallax') : '' },
				},
				dim : {
					keys	: [ 'dim' ],
					build	: function( body ) {

						// The scrim is for a picture, of any section: the cover's, the parallax's, and the plain section's own
						const dim = setting( { key : 'dim', type : 'bool', label : '/_admin/builder/section/dim', hint : '/_admin/builder/hint/dim-text' } );

						builder._one( dim, 'input' ).disabled = has() === false && settings.dim !== true;

						// Text on a picture reads better with the picture dimmed: the question mark of the switch is there for as long as that is advice - a picture, text on it, the
						// dimming off - and no more than that
						const advise = function() { builder._one( dim, '.builder-help' ).hidden = has() === false || settings.dim === true || builder._carriesText( section, builder._registry ) === false };

						builder._one( dim, 'input' ).addEventListener( 'change', advise );
						advise();
						picture( body ).appendChild( dim );
					},
					says	: function() { return settings.dim === true ? word('section/dim') : '' },
				},
				inner : {
					keys	: [ 'inner' ],
					build	: function( body ) {

						// The row paints once the background stands inside, and the radius that follows is the row's
						fields.inner = setting( { key : 'inner', type : 'bool', label : '/_admin/builder/section/inner-background', hint : '/_admin/builder/hint/inner-background' }, again );
						builder._one( fields.inner, 'input' ).disabled = possible() === false && settings.inner !== true;
						body.appendChild( fields.inner );
					},
					says	: function() { return settings.inner === true ? word('section/inner-background') : '' },
				},
				padded : {
					keys	: [ 'padded' ],
					build	: function( body ) {

						// The room of the page around a cover or a parallax: there is none to keep around a section that is neither. A switch that is on can be switched off
						const padded = setting( { key : 'padded', type : 'bool', label : '/_admin/builder/section/padded', hint : '/_admin/builder/hint/padded' }, again );

						builder._one( padded, 'input' ).disabled = settings.cover === null && settings.parallax !== true && settings.padded !== true;
						picture( body ).appendChild( padded );
					},
					says	: function() { return settings.padded === true ? word('section/padded') : '' },
				},
				// One toggle for the corners, whichever of the two is drawn: the row's where the background stands inside it - what the row paints has the radius of the row -, else of
				// what a cover paints with the room of the page around it. It sleeps where there is nothing to round
				radius : {
					keys	: [ 'radius' ],
					build	: function( body ) {
						if( settings.inner !== true )
							body.appendChild( builder._radiusToggle( settings, 'radius', 'cover', function() { return settings.padded === true && ( settings.cover !== null || settings.parallax === true ) } ) );
					},
					says	: function() { return radiusSaid( settings.radius ) },
				},
				rowRadius : {
					keys	: [ 'rowRadius' ],
					build	: function( body ) {
						if( settings.inner === true )
							body.appendChild( builder._radiusToggle( settings, 'rowRadius', 'row', function() { return true } ) );
					},
					says	: function() { return radiusSaid( settings.rowRadius ) },
				},
				rowBgPl : offset,
				rowBgPr : offset,
				arrow : {
					keys	: [ 'arrow' ],
					build	: function( body ) {

						// The arrow at the foot of a cover: a section that is none has no use for it, and one that has it keeps it
						const arrow = builder._switchField( word('section/arrow'), word('hint/arrow'), settings.arrow === true, function( on ) {
							settings.arrow = on;
							builder._changed();
							// With the cover off the switch is only there to be turned off: once it is, it sleeps
							again();
						} );

						builder._keyed( arrow, 'arrow' );
						builder._one( arrow, 'input' ).disabled = settings.cover === null && settings.arrow !== true;
						picture( body ).appendChild( arrow );
					},
					says	: function() { return settings.arrow === true ? word('section/arrow') : '' },
				},
				rowGap : {
					keys	: [ 'rowGap' ],
					build	: function( body ) { body.appendChild( setting( { key : 'rowGap', type : 'select', label : '/_admin/builder/section/row-gap', hint : '/_admin/builder/hint/row-gap', group : 'space', values : builder.SPACES } ) ) },
					says	: function() { return settings.rowGap === '' ? '' : word('spacing/gap')+ ' '+ settings.rowGap },
				},
				whitespace : {
					keys	: [ 'whitespace' ],
					build	: function( body ) { body.appendChild( setting( { key : 'whitespace', type : 'select', label : '/_admin/builder/section/whitespace', hint : '/_admin/builder/hint/whitespace', group : 'whitespace', values : builder.WHITESPACE } ) ) },
					says	: function() { return settings.whitespace === '' ? '' : word('section/whitespace')+ ' '+ builder._optionLabel( 'whitespace', settings.whitespace ).toLowerCase() },
				},
				mt : sides,
				mb : sides,
				pt : sides,
				pb : sides,
				pl : sides,
				pr : sides,
				rowPt : rowSides,
				rowPb : rowSides,
				rowPl : rowSides,
				rowPr : rowSides,
				width : {
					keys	: [ 'row', 'rowPl', 'rowPr' ],
					build	: function( body ) {

						body.appendChild( builder._keyed( builder._segmentField( word('section/width'), builder.WIDTHS.map( function( value ) {
							return { value : value, label : builder._optionLabel( 'width', value ), icon : 'width-'+ value };
						} ), builder._widthOf( settings ), function( value ) {
							builder._widthSet( settings, value );
							builder._changed();
							// The maximum width and the place of the row are for a row as wide as its content, and the box of the row shows the sides that to the edge sets
							builder._refreshControls( 'width', 'row', 'rowPlace', 'rowPt' );
						}, word('hint/width') ), 'row' ) );
					},
					says	: function() {

						const width = builder._widthOf( settings );

						return width === 'content' ? '' : builder._optionLabel( 'width', width );
					},
				},
				row : {
					keys	: [ 'row' ],
					build	: function( body ) {

						// The width the row may have where it has one: it is for a row as wide as its content
						const own = setting( { key : 'row', type : 'select', label : '/_admin/builder/section/row', group : 'row', values : builder.ROWS } );

						builder._one( own, 'select' ).disabled = builder._widthOf( settings ) !== 'content';
						body.appendChild( own );
					},
					says	: function() { return settings.row !== '' && builder._widthOf( settings ) === 'content' ? builder._optionLabel( 'row', settings.row ) : '' },
				},
				rowPlace : {
					keys	: [ 'rowPlace' ],
					build	: function( body ) {

						// Where a row narrower than the page stands: a row as wide as the page has no place to stand in
						const place = setting( { key : 'rowPlace', type : 'segment', label : '/_admin/builder/section/row-place', hint : '/_admin/builder/hint/row-place', items : builder._options( 'row-place', builder.ROW_PLACES ) } );

						place.setDisabled( builder._widthOf( settings ) !== 'content' );
						body.appendChild( place );
					},
					says	: function() { return settings.rowPlace !== '' && builder._widthOf( settings ) === 'content' ? builder._optionLabel( 'row-place', settings.rowPlace ) : '' },
				},
				text : {
					keys	: [ 'text' ],
					build	: function( body ) { body.appendChild( setting( { key : 'text', type : 'segment', label : '/_admin/builder/section/text', hint : '/_admin/builder/hint/text', items : builder._alignOptions( 'text', 'text', builder.TEXTS ) } ) ) },
					says	: function() { return alignSaid( 'text', 'text' ) },
				},
				rowAlign : {
					keys	: [ 'rowAlign' ],
					build	: function( body ) { body.appendChild( setting( { key : 'rowAlign', type : 'segment', label : '/_admin/builder/section/row-align', hint : '/_admin/builder/hint/row-align', items : builder._alignOptions( 'rowAlign', 'row-align', builder.ROW_ALIGN ) } ) ) },
					says	: function() { return alignSaid( 'rowAlign', 'row-align' ) },
				},
				reverse : {
					keys	: [ 'reverse' ],
					build	: function( body ) { builder._reverseSwitches( body, settings, word('hint/reverse') ) },
					says	: function() {

						const turned = builder.VIEWPORTS.filter( function( viewport ) { return settings.reverse[viewport] === true } );

						return turned.length === 0 ? '' : Nino.adminUi.format( word('section/reverse-said'), turned.map( function( viewport ) { return word( 'viewport/'+ viewport ) } ).join(', ') );
					},
				},
				contentPos : {
					keys	: [ 'contentPos' ],
					build	: function( body ) {

						// Where the row of a cover or a parallax stands in it: elsewhere the row stands in the flow of the page
						const content = setting( { key : 'contentPos', type : 'select', label : '/_admin/builder/section/content-pos', hint : '/_admin/builder/hint/content-pos', group : 'content-pos', values : builder.CONTENT_POS } );

						builder._one( content, 'select' ).disabled = settings.cover === null && settings.parallax !== true;
						body.appendChild( content );
					},
					says	: function() { return alignSaid( 'contentPos', 'content-pos' ) },
				},
				stack : {
					keys	: [ 'stack' ],
					build	: function( body ) {

						const loop = builder._el( 'div', 'builder-loop' );

						builder._stackForm( loop, path );
						body.appendChild( loop );
					},
					says	: function() { return builder._looping( section ) === true ? word('stack/sections') : '' },
				},
				vpa : {
					keys	: [ 'vpa' ],
					build	: function( body ) {

						const group = builder._groupIn( body, 'animation' );

						group.appendChild( builder._animationModeField( section, animation ) );

						if( animation.mode === 'own' )
							animations.vpa.build( group );
					},
					says	: function() {

						const mode = builder._animationMode( builder._doc.model.animate === true, settings );

						if( mode === 'off' )
							return word('section/vpa')+ ' '+ builder._optionLabel( 'anim', 'off' ).toLowerCase();

						return mode === 'own' && settings.vpa !== null ? word('section/vpa')+ ' '+ builder._optionLabel( 'effect', builder._effectSplit( settings.vpa ).effect ).toLowerCase() : '';
					},
				},
				vpaSpeed : animated( 'vpaSpeed' ),
				vpaMode : animated( 'vpaMode' ),
				vpaDelay : animated( 'vpaDelay' ),
				vpaDuration : animated( 'vpaDuration' ),
				custom : {
					keys	: [ 'custom' ],
					build	: function( body ) { builder._groupIn( body, 'custom' ).appendChild( setting( { key : 'custom', type : 'string', label : '/_admin/builder/section/custom', hint : '/_admin/builder/hint/custom' } ) ) },
					says	: function() { return settings.custom === '' ? '' : word('group/custom') },
				},
				rowCustom : {
					keys	: [ 'rowCustom' ],
					build	: function( body ) { builder._groupIn( body, 'custom' ).appendChild( setting( { key : 'rowCustom', type : 'string', label : '/_admin/builder/section/row-custom' } ) ) },
					says	: function() { return settings.rowCustom === '' ? '' : word('group/custom') },
				},
				// The id is what the pencil in the title changes: the same rename, with the same question where keys and slots move with it. The field has the id the section has -
				// after a name that will not do, after a question that is answered no, and once the section is renamed
				id : {
					keys	: [ 'id' ],
					build	: function( body ) {

						const field = builder._keyed( builder._textField( word('section/id'), '', section.id, function() {}, { onCommit : function( value, control ) {

							if( value.trim() === section.id )
								builder._dialogProblem( '' );
							else
								builder._renameTo( path, value, builder._dialogProblem, builder._renamedIn );

							control.value = section.id;
						} } ), 'id' );

						// Escape puts the id the section has back and stays in the field, as the pencil's field does: the first Escape reverts what was typed and is not the one that
						// closes the dialog, the next one closes it with nothing left to commit (the field commits on change, which a dialog closing fires as well)
						field.control.addEventListener( 'keydown', function( ev ) {

							if( ev.key !== 'Escape' || field.control.value === section.id )
								return;

							field.control.value = section.id;
							builder._dialogProblem( '' );
							ev.preventDefault();
							ev.stopPropagation();
						} );

						body.appendChild( field );
					},
					says	: function() { return '' },
				},
				// The kind of the section, content or tiles: a switch of the whole form, which is opened again for the kind it has become (see _setKind())
				kind : {
					keys	: [ 'tiles' ],
					build	: function( body ) {
						body.appendChild( builder._keyed( builder._segmentField( word('section/kind'), builder._options( 'kind', builder.KINDS ), settings.tiles === true ? 'tiles' : 'content', function( value ) {

							if( ( value === 'tiles' ) === ( settings.tiles === true ) )
								return;

							builder._setKind( section, value === 'tiles' );
							builder._changed();
							builder._sectionDialog( path, builder._blockOf( 'section', 'kind' ) );
						}, word('hint/kind') ), 'tiles' ) );
					},
					says	: function() { return settings.tiles === true ? word('tree/tiles') : '' },
				},
			};

			if( settings.tiles !== true )
				return controls;

			// A section of tiles has the controls it has of the content kind - the ground between its tiles, its lines, the picture behind the whole section with its dimming, its air, its loop, its animation and its own classes, and the order of its tiles, turned round for each viewport as for any row - and three of its
			// own: the height of its tiles, the gap between them and how the text of the section runs, under the names the table lists in the block Tiles. It has no width, no row, no cover,
			// no parallax and no picture of its row: its row is the whole width of the section, the picture of the section shows in the gaps between the tiles and its tiles hold their own
			const kept = [ 'border', 'borderPrimary', 'background', 'dim', 'whitespace', 'mt', 'mb', 'pt', 'pb', 'pl', 'pr', 'stack', 'reverse', 'kind', 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration', 'custom', 'rowCustom', 'id' ];
			const tiles = {};

			kept.forEach( function( name ) { tiles[name] = controls[name] } );

			// The colour of the section is what shows between the tiles
			tiles.color = {
				keys	: [ 'color' ],
				build	: function( body ) { body.appendChild( setting( { key : 'color', type : 'color', label : '/_admin/builder/section/color-tiles' } ) ) },
				says	: controls.color.says,
			};
			tiles.ratio = {
				keys	: [ 'ratio' ],
				build	: function( body ) { body.appendChild( setting( { key : 'ratio', type : 'select', label : '/_admin/builder/section/ratio', hint : '/_admin/builder/hint/ratio', group : 'ratio', values : builder.RATIOS } ) ) },
				says	: function() { return settings.ratio === '' ? '' : builder._optionLabel( 'ratio', settings.ratio ) },
			};
			tiles.tileGap = {
				keys	: [ 'rowGap' ],
				build	: function( body ) { body.appendChild( setting( { key : 'rowGap', type : 'select', label : '/_admin/builder/section/tile-gap', hint : '/_admin/builder/hint/tile-gap', group : 'space', values : builder.SPACES } ) ) },
				says	: controls.rowGap.says,
			};
			tiles.tileText = controls.text;

			return tiles;
		},

		/**
		 *	A section turned into the other kind, as the switch of the form says it. To tiles it takes the ratio of a new one and gives up what is the content kind's: the width, the place
		 *	and the padding of its row, the ground and the picture of its row, the cover, the parallax, the dimming of a cover or a parallax and the room of a padded cover, the place of the content, the radius of a padded cover,
		 *	the arrow and the offsets of the picture of each column. The ground and the lines of the section, the picture behind it with its dimming, the gap, the whitespace, its spacing, its text alignment, its animation and its classes stay. To content it
		 *	loses the ratio and the link over the whole of each of its tiles. Its columns stay as they are
		 *
		 *	@param		{Object}		section
		 *	@param		{boolean}		tiles							The kind it becomes
		 *
		 *	@return		void
		 */
		_setKind : function( section, tiles ) {

			const builder = Nino.admin.builder;
			const settings = section.settings;

			settings.tiles = tiles;

			if( tiles === true ) {

				settings.ratio = builder.TILES_RATIO;

				[ 'row', 'rowPlace', 'rowAlign', 'rowPl', 'rowPr', 'rowPt', 'rowPb', 'rowRadius', 'rowBgPl', 'rowBgPr', 'contentPos', 'radius' ].forEach( function( key ) { settings[key] = '' } );
				[ 'parallax', 'padded', 'inner', 'arrow' ].forEach( function( key ) { settings[key] = false } );
				settings.cover = null;

				// The scrim of a cover or of a parallax goes with them; the one over the picture behind the section stays with the picture
				if( section.background === null || section.background === undefined )
					settings.dim = false;

				// A tile has no offsets of its picture
				section.cols.forEach( function( col ) { col.bgPl = ''; col.bgPr = '' } );

				return;
			}

			settings.ratio = '';
			section.cols.forEach( function( col ) { col.linked = false } );
		},

		/**
		 *	How wide the row of a section is, as the form says it: the width of the content, the width of the page - the row has no maximum width - or to the edge of the page, which
		 *	is that with no padding at the left and the right of the row
		 *
		 *	@param		{Object}		settings					Of a section
		 *
		 *	@return		{string}									A value of WIDTHS
		 */
		_widthOf : function( settings ) {

			if( settings.row !== 'full' )
				return 'content';

			return settings.rowPl === '0' && settings.rowPr === '0' ? 'edge' : 'full';
		},

		/**
		 *	The row of a section made as wide as the form asks: content width leaves the row the width it has - the grid's own where it had none at all -, full width takes the
		 *	maximum width away, and to the edge does that and has no padding at the sides. What the edge put there goes with it: another width leaves the sides as they were before
		 *
		 *	@param		{Object}		settings					Of a section
		 *	@param		{string}		width							A value of WIDTHS
		 *
		 *	@return		void
		 */
		_widthSet : function( settings, width ) {

			const was = Nino.admin.builder._widthOf( settings );

			settings.row = width === 'content' ? ( settings.row === 'full' ? '' : settings.row ) : 'full';

			if( width === 'edge' ) {
				settings.rowPl = '0';
				settings.rowPr = '0';
			} else if( was === 'edge' ) {
				settings.rowPl = '';
				settings.rowPr = '';
			}
		},


		/**
		 *	The animation of a section: like the template - what the template gives its
		 *	sections -, off, or its own, which opens the fields. Where the template does not
		 *	animate its sections, none is like the template and there is no off
		 *
		 *	@param		{Object}		section
		 *	@param		{Object}		state							{ mode }, kept by the dialog over a redraw of the block
		 *
		 *	@return		{Element}
		 */
		_animationModeField : function( section, state ) {

			const builder = Nino.admin.builder;
			const animate = builder._doc.model.animate === true;
			const options = ( animate === true ? [ 'like', 'off', 'own' ] : [ 'like', 'own' ] ).map( function( value ) {
				return { value : value, label : Nino.content.getText( '/_admin/builder/option/anim-'+ ( value === 'like' && animate === false ? 'like-off' : value ) ) };
			} );

			return builder._selectField( Nino.content.getText('/_admin/builder/section/vpa'), Nino.content.getText( animate === true ? '/_admin/builder/hint/vpa-section' : '/_admin/builder/hint/vpa-section-off' ), options, state.mode, function( value ) {

				state.mode = value;
				builder._animationSet( animate, section.settings, value );
				builder._changed();
				builder._refreshControls( 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration' );
			} );
		},

		/**
		 *	The controls of the animation of a section or a column, or of the kind of animation of the template: the effect and its strength, how fast and how often, when it starts
		 *	and how long it takes, a control each - { keys, build( pane ) }, by the setting it writes. The effect and the strength are two fields and one word of the model
		 *	(zoom-soft); the strength is none to choose with the plain effect, or with none. vpa is null for none, '' for the plain effect, else that word. With none, the speed
		 *	and how often are none to choose either: nothing is written for an animation that is not there
		 *
		 *	@param		{Object}		target						The settings of a section or a column - or the model of the template
		 *	@param		{Object}		options						{ none: whether none is among the effects - a column's -, rest: false for no repeat, delay and duration - the template's -,
		 *															times: false for no delay and duration - a column's, which the file keeps as classes and has no attribute for -, hint: the key of
		 *															the words under the effect, speeds: the group of the words of the speeds }
		 *
		 *	@return		{Object}									vpa, vpaSpeed and, where the options leave them, vpaMode, vpaDelay and vpaDuration => { keys, build( pane ) }
		 */
		_animationControls : function( target, options ) {

			const builder = Nino.admin.builder;
			const state = { strength : builder._effectSplit( target.vpa ).strength || 'medium' };
			// What the effect leaves to choose, as the fields that were drawn last have it: the strength with an effect that has one, the speed and the mode with any
			const fields = {};
			const sync = function() {

				if( fields.strength !== undefined )
					fields.strength.setDisabled( ( target.vpa ?? '' ) === '' );

				[ 'vpaSpeed', 'vpaMode' ].forEach( function( key ) {
					if( fields[key] !== undefined )
						fields[key].querySelector('select').disabled = target.vpa === null;
				} );
			};
			const controls = {
				vpa : {
					keys	: [ 'vpa' ],
					build	: function( pane ) {

						const effects = builder.EFFECTS.map( function( effect ) { return { value : effect, label : builder._optionLabel( 'effect', effect ) } } );

						if( options.none === true )
							effects.unshift( { value : 'none', label : builder._optionLabel( 'vpa', 'none' ) } );

						fields.strength = builder._segmentField( Nino.content.getText('/_admin/builder/section/vpa-strength'), builder.STRENGTHS.map( function( value ) {
							return { value : value, label : builder._optionLabel( 'strength', value ) };
						} ), state.strength, function( value ) {
							state.strength = value;
							target.vpa = builder._effectJoin( builder._effectSplit( target.vpa ).effect, value );
							builder._changed();
						} );

						pane.appendChild( builder._keyed( builder._selectField( Nino.content.getText('/_admin/builder/section/vpa-effect'), options.hint === undefined ? '' : Nino.content.getText( options.hint ), effects, target.vpa === null ? 'none' : builder._effectSplit( target.vpa ).effect, function( effect ) {

							target.vpa = effect === 'none' ? null : builder._effectJoin( effect, state.strength );
							sync();
							builder._changed();
						} ), 'vpa' ) );
						pane.appendChild( builder._keyed( fields.strength, 'vpa' ) );
						sync();
					},
				},
				vpaSpeed : {
					keys	: [ 'vpaSpeed' ],
					build	: function( pane ) {

						fields.vpaSpeed = builder._setting( target, { key : 'vpaSpeed', type : 'select', label : '/_admin/builder/section/vpa-speed', group : options.speeds ?? 'speed', values : builder.SPEEDS } );
						pane.appendChild( fields.vpaSpeed );
						sync();
					},
				},
			};

			if( options.rest === false )
				return controls;

			controls.vpaMode = {
				keys	: [ 'vpaMode' ],
				build	: function( pane ) {

					// A mode a hand-written file carries is kept in the list, so that reading it changes nothing
					fields.vpaMode = builder._setting( target, { key : 'vpaMode', type : 'select', label : '/_admin/builder/section/vpa-mode', group : 'mode',
						values : builder.ANIMATION_MODES.concat( builder.ANIMATION_MODES.indexOf( target.vpaMode ) === -1 ? [ target.vpaMode ] : [] ) } );
					pane.appendChild( fields.vpaMode );
					sync();
				},
			};

			if( options.times !== false )
				[ [ 'vpaDelay', '/_admin/builder/section/vpa-delay' ], [ 'vpaDuration', '/_admin/builder/section/vpa-duration' ] ].forEach( function( time ) {
					controls[time[0]] = {
						keys	: [ time[0] ],
						build	: function( pane ) { pane.appendChild( builder._setting( target, { key : time[0], type : 'string', label : time[1], hint : '/_admin/builder/hint/time' } ) ) },
					};
				} );

			return controls;
		},

		/**
		 *	The animation of the template, or of anything else that has the fields in one place: the controls of the animation (see _animationControls()) one after the other, in
		 *	the order of their settings
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		target						The model of the template
		 *	@param		{Object}		options						As _animationControls() has them
		 *
		 *	@return		void
		 */
		_animationFields : function( pane, target, options ) {

			const controls = Nino.admin.builder._animationControls( target, options );

			[ 'vpa', 'vpaSpeed', 'vpaMode', 'vpaDelay', 'vpaDuration' ].forEach( function( key ) {
				if( controls[key] !== undefined )
					controls[key].build( pane );
			} );
		},

		/**
		 *	The pencil beside the name of a section: it turns the name into a field
		 *
		 *	@param		{Element}		text							What shows the name
		 *	@param		{Array}			path							The section
		 *	@param		{Function}	say								Where a name that will not do is said
		 *	@param		{Function}	[renamed]					Called with the new name once it is the section's
		 *
		 *	@return		{Element}									The button
		 */
		_renamer : function( text, path, say, renamed ) {

			const builder = Nino.admin.builder;
			const button = builder._iconButton( 'pencil', Nino.content.getText('/_admin/builder/menu/rename'), function( ev ) {
				ev.stopPropagation();
				builder._editName( text, button, path, say, renamed );
			} );

			button.classList.add('builder-rename');

			return button;
		},

		/**
		 *	The name of a section as a field in the place it stands. Enter and leaving
		 *	the field take what was typed, if it will do; Escape leaves the name as it was.
		 *	Enter and Escape give the focus back to the pencil - to the one of the preview
		 *	that is drawn again for the new name, where the pencil it was given to is gone
		 *
		 *	@param		{Element}		text							What shows the name; it is hidden while the field is there
		 *	@param		{Element}		button						The pencil, hidden as well
		 *	@param		{Array}			path							The section
		 *	@param		{Function}	say								Where a name that will not do is said
		 *	@param		{Function}	[renamed]					Called with the new name once it is the section's
		 *
		 *	@return		void
		 */
		_editName : function( text, button, path, say, renamed ) {

			const builder = Nino.admin.builder;
			const field = builder._el( 'input', 'nino-admin-input builder-name-input' );
			let finished = false;

			// However it ends the field goes and the name and the pencil are back; a name is taken once
			const end = function( take, focus ) {

				if( finished === true )
					return;

				finished = true;
				field.remove();
				text.hidden = false;
				button.hidden = false;

				if( focus === true )
					button.focus();

				if( take === true )
					builder._renameTo( path, field.value, say, function( id ) {

						if( focus === true && button.isConnected === false ) {

							const again = dc.querySelector( '#builder-preview [data-path="'+ path.join('.')+ '"] .builder-rename' );

							if( again !== null )
								again.focus();
						}

						if( typeof renamed === 'function' )
							renamed( id );
					} );
			};

			field.type = 'text';
			field.value = builder._doc.model.blocks[path[0]].id;
			field.autocomplete = 'off';
			field.spellcheck = false;
			field.setAttribute( 'aria-label', Nino.content.getText('/_admin/builder/section/id') );

			field.addEventListener( 'keydown', function( ev ) {

				if( ev.key === 'Enter' )
					end( true, true );
				else if( ev.key === 'Escape' )
					end( false, true );
				else
					return;

				// The Escape that leaves the field is not the one that closes the dialog it may be in
				ev.preventDefault();
				ev.stopPropagation();
			} );
			field.addEventListener( 'blur', function() { end( true, false ) } );

			// A click in the field is not one on the frame it is in, and neither is the mousedown of its double click: the frame keeps
			// that one from selecting a word, and a word of the name is what a double click in the field is for
			[ 'mousedown', 'click', 'dblclick' ].forEach( function( type ) { field.addEventListener( type, function( ev ) { ev.stopPropagation() } ) } );

			text.hidden = true;
			button.hidden = true;
			text.parentNode.insertBefore( field, text );
			field.focus();
			field.select();
		},

		/**
		 *	A section gets the name that was typed for it, if it will do: a slug no
		 *	other section has. One that has keys or slots under its name asks first,
		 *	since they move with it
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		value
		 *	@param		{Function}	say								Told why not - and told '' once the name will do
		 *	@param		{Function}	[renamed]
		 *
		 *	@return		void
		 */
		_renameTo : function( path, value, say, renamed ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const section = doc.model.blocks[path[0]];
			const id = value.trim();
			const taken = doc.model.blocks.some( function( block, at ) { return at !== path[0] && block.kind === 'section' && block.id === id } );

			if( id === section.id )
				return;

			if( builder.SEGMENT.test( id ) === false || taken === true ) {
				say( Nino.content.getText( taken === true ? '/_admin/builder/error/id-taken' : '/_admin/builder/error/id-slug' ) );
				return;
			}

			say( '' );

			const rename = function() {

				builder._rename( path, id );

				if( typeof renamed === 'function' )
					renamed( id );
			};

			if( builder._ownKeys( section ) === false )
				return rename();

			Nino.adminUi.choiceDialog( {
				title		: Nino.content.getText('/_admin/builder/state/rename'),
				message	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/confirm/rename'), section.id, id ),
				choices	: [
					{ value : 'rename', label : Nino.content.getText('/_admin/common/label/rename'), kind : 'primary' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				],
				onChoose	: function( choice ) {
					if( choice === 'rename' )
						rename();
				},
			} );
		},

		/**
		 *	Said in the line of the bar: what is wrong with a name typed in the head of
		 *	a frame, which has no place of its own for it
		 *
		 *	@param		{string}		text							'' for nothing to say
		 *
		 *	@return		void
		 */
		_barSays : function( text ) {

			if( text !== '' )
				Nino.admin.builder._status.fail( text );
		},

		/**
		 *	Whether a section has keys or slots under its own id
		 *
		 *	@param		{Object}		section
		 *
		 *	@return		{boolean}
		 */
		_ownKeys : function( section ) {

			const builder = Nino.admin.builder;

			return Object.keys( builder._names( section, builder._doc.file, [] ) ).length > 0;
		},

		/**
		 *	A section gets another id. Where it has been saved the server moves its
		 *	keys and slots (renamedFrom says from where); where it was made here
		 *	its sources are written new, since nothing is moved that was never made -
		 *	but for a slot that is there already, a picture was stored for it: it stays
		 *	what the source names, whatever the section is called
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		id
		 *
		 *	@return		void
		 */
		_rename : function( path, id ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const section = doc.model.blocks[path[0]];
			const old = section.id;

			if( builder._fresh[old] === true ) {

				const from = builder._keyUri( doc.file, old, '' );
				const to = builder._keyUri( doc.file, id, '' );
				const follow = function( holder, key ) {
					if( typeof holder[key] === 'string' && holder[key].indexOf( from ) === 0 && builder._slotOf( holder[key] ) === null )
						holder[key] = to+ holder[key].slice( from.length );
				};
				// The source of a component, the pictures its attributes name, and the same in its children
				const move = function( node ) {

					follow( node, 'source' );
					builder._pictures( builder._declared( ( builder._registry.components || {} )[node.name] || {} ) ).forEach( function( attribute ) { follow( node.attributes, attribute ) } );
					( node.components || [] ).forEach( move );
				};

				if( section.background )
					follow( section.background, 'slot' );

				section.cols.forEach( function( col ) {

					if( col.background )
						follow( col.background, 'slot' );

					col.components.forEach( move );
				} );

				delete builder._fresh[old];
				builder._fresh[id] = true;
			} else if( section.renamedFrom === undefined )
				section.renamedFrom = old;
			else if( section.renamedFrom === id )
				delete section.renamedFrom;

			section.id = id;
			builder._changed();
		},

		/**
		 *	The section of the dialog that is open has its new name: the title says it, and so do the controls of the form that show it - the id, and the id of a loop that makes sections
		 *
		 *	@param		{string}		id
		 *
		 *	@return		void
		 */
		_renamedIn : function( id ) {

			dc.getElementById('builder-dialog-name').textContent = id;
			Nino.admin.builder._refreshControls( 'id', 'stack' );
		},

		// ---------------------------------------------------- Columns and loops

		/**
		 *	The form of a column, in the blocks the table has for it (BLOCKS.column): its surface - the colour it paints and the radius of what it paints and then, in a group of its
		 *	own, the background image: the picture behind it, whether it is dimmed and how far in from the sides it starts -, its spacing - the gap between its components and the box of
		 *	its sides -, its width and its alignment - how wide it is and whether it is hidden in each viewport, where its components and its text are put -, its loop and its
		 *	properties: its animation and the classes of its own
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		[block]						The block to open: the one that was open last in a dialog of a column, by default
		 *
		 *	@return		void
		 */
		_colDialog : function( path, block ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];

			builder._dialog( {
				// A column of a section of tiles is a tile
				title		: Nino.adminUi.format( Nino.content.getText( section.settings.tiles === true ? '/_admin/builder/tile/title' : '/_admin/builder/col/title' ), section.id ),
				blame		: path[0],
				returnTo	: path,
				kind		: 'column',
				block		: block,
				blocks	: builder._blocksOf( 'column', builder._colControls( path ) ),
			} );
		},

		/**
		 *	The controls of the form of a column, by the name the table lists them under (see BLOCKS and _sectionControls())
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Object}									name of the control => its entry (see _blocksOf())
		 */
		_colControls : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const col = section.cols[path[1]];
			// A column of a section of tiles is a tile: it has the link over the whole of it, the radius of its own kind and no offsets of its picture, and its words are placed in it
			const tile = section.settings.tiles === true;
			const word = function( key ) { return Nino.content.getText( '/_admin/builder/'+ key ) };
			const setting = function( field, after ) { return builder._setting( col, field, after ) };
			const has = function() { return col.background !== null && col.background !== undefined };
			// The animation of a column is its own, and has no delay and no duration: the file keeps it as classes
			const animations = builder._animationControls( col, { none : true, times : false } );
			// The fields that a choice of another control changes at once, by the name of the control: the one that was drawn last
			const fields = {};
			// A choice that other controls show: the blocks are drawn again
			const again = function() { builder._refreshControls() };
			// Where the picture behind the column and what depends on it stand: one group under its heading (see _sectionControls())
			const picture = function( body ) { return builder._groupIn( body, 'background-image' ) };
			const animated = function( key ) {
				return {
					keys	: [ key ],
					build	: function( body ) { animations[key].build( builder._groupIn( body, 'animation' ) ) },
					says	: function() { return '' },
				};
			};
			const sides = {
				keys	: builder.SIDES,
				build	: function( body ) { body.appendChild( builder._boxField( col, { name : '', hint : word('hint/spacing'), outside : [ 'mt', 'mb' ], inside : [ 'pt', 'pb', 'pl', 'pr' ] } ) ) },
				says	: function() { return builder._boxSaid( col, [ 'mt', 'mb' ], [ 'pt', 'pb', 'pl', 'pr' ] ) },
			};
			const offset = {
				keys	: [ 'bgPl', 'bgPr' ],
				build	: function( body ) {

					// How far in from the sides the picture starts: it is read where the column has one
					const inset = builder._offsetField( col, [ 'bgPl', 'bgPr' ], word('section/offset'), word('hint/offset') );

					inset.setDisabled( has() === false );
					picture( body ).appendChild( inset );
				},
				says	: function() { return builder._offsetSaid( col, [ 'bgPl', 'bgPr' ], word('section/offset') ) },
			};
			// What a column says in its head of the width it has in each viewport and of where it is hidden
			const widthSaid = function() {

				const words = [];
				const widths = builder.VIEWPORTS.map( function( viewport ) { return Number( col.width[viewport] ) } );
				const hidden = builder.VIEWPORTS.filter( function( viewport ) { return col.hidden[viewport] === true } );

				if( widths.some( function( width ) { return width !== 100 } ) === true )
					words.push( widths.join('/')+ ' %' );

				if( hidden.length > 0 )
					words.push( Nino.adminUi.format( word('preview/hidden-in'), hidden.map( function( viewport ) { return word( 'viewport/'+ viewport ) } ).join(', ') ) );

				return builder._said( words );
			};
			const width = {
				keys	: [ 'width', 'hidden' ],
				build	: function( body ) { builder._widthTable( body, col ) },
				says	: widthSaid,
			};

			const controls = {
				color : {
					keys	: [ 'color' ],
					build	: function( body ) {
						body.appendChild( setting( { key : 'color', type : 'color', label : '/_admin/builder/section/color', around : section.settings.color }, function() {
							if( fields.radius !== undefined )
								fields.radius.syncRadius();
						} ) );
					},
					says	: function() { return col.color === '' ? '' : builder._groundLabel( col.color ) },
				},
				background : {
					keys	: [ 'background' ],
					build	: function( body ) { picture( body ).appendChild( builder._backgroundField( path, again ) ) },
					says	: function() { return has() === true ? word('source/background-image') : '' },
				},
				dim : {
					keys	: [ 'dim' ],
					build	: function( body ) {

						const dim = setting( { key : 'dim', type : 'bool', label : '/_admin/builder/section/dim' } );

						// There is a picture to darken, or there is none
						builder._one( dim, 'input' ).disabled = has() === false;
						picture( body ).appendChild( dim );
					},
					says	: function() { return col.dim === true && has() === true ? word('section/dim') : '' },
				},
				bgPl : offset,
				bgPr : offset,
				radius : {
					keys	: [ 'radius' ],
					build	: function( body ) {

						// A column that paints - a colour, a picture - has a radius, which is the column's to take; one that paints nothing has none, and the toggle sleeps
						fields.radius = builder._radiusToggle( col, 'radius', tile === true ? 'tile' : 'column', function() { return ( col.color !== '' && col.color !== undefined ) || has() } );
						body.appendChild( fields.radius );
					},
					says	: function() { return col.radius === '' ? '' : word( col.radius === 'alternative' ? 'common/radius-alt' : 'common/radius-standard' ) },
				},
				stackGap : {
					keys	: [ 'stackGap' ],
					build	: function( body ) { body.appendChild( setting( { key : 'stackGap', type : 'select', label : '/_admin/builder/col/stack-gap', group : 'space', values : builder.SPACES } ) ) },
					says	: function() { return col.stackGap === '' ? '' : word('spacing/gap')+ ' '+ col.stackGap },
				},
				mt : sides,
				mb : sides,
				pt : sides,
				pb : sides,
				pl : sides,
				pr : sides,
				width : width,
				hidden : width,
				stackAlign : {
					keys	: [ 'stackAlign' ],
					build	: function( body ) { body.appendChild( setting( { key : 'stackAlign', type : 'segment', label : tile === true ? '/_admin/builder/tile/stack-align' : '/_admin/builder/col/stack-align', items : builder._alignOptions( 'stackAlign', 'stack-align', builder.STACK_ALIGN ) } ) ) },
					says	: function() { return col.stackAlign === '' ? '' : builder._optionLabel( 'stack-align', col.stackAlign ) },
				},
				text : {
					keys	: [ 'text' ],
					build	: function( body ) { body.appendChild( setting( { key : 'text', type : 'segment', label : '/_admin/builder/section/text', items : builder._alignOptions( 'text', 'text', builder.TEXTS ) } ) ) },
					says	: function() { return col.text === '' ? '' : builder._optionLabel( 'text', col.text ) },
				},
				stack : {
					keys	: [ 'stack' ],
					build	: function( body ) {

						const loop = builder._el( 'div', 'builder-loop' );

						builder._stackForm( loop, path );
						body.appendChild( loop );
					},
					says	: function() { return col.stack === null || col.stack === undefined ? '' : builder._stackLabel( builder._registry, col.stack.name ) },
				},
				vpa : {
					keys	: [ 'vpa' ],
					build	: function( body ) { animations.vpa.build( builder._groupIn( body, 'animation' ) ) },
					says	: function() { return col.vpa === null || col.vpa === undefined ? '' : word('section/vpa')+ ' '+ builder._optionLabel( 'effect', builder._effectSplit( col.vpa ).effect ).toLowerCase() },
				},
				vpaSpeed : animated( 'vpaSpeed' ),
				vpaMode : animated( 'vpaMode' ),
				custom : {
					keys	: [ 'custom' ],
					build	: function( body ) { builder._groupIn( body, 'custom' ).appendChild( setting( { key : 'custom', type : 'string', label : '/_admin/builder/col/custom', hint : '/_admin/builder/hint/custom' } ) ) },
					says	: function() { return col.custom === '' ? '' : word('group/custom') },
				},
			};

			if( tile === false )
				return controls;

			// The title's link covers the whole of a tile - a switch of its own, which a column of a content section has no use for - and the picture of a tile lies over the whole of it
			controls.linked = {
				keys	: [ 'linked' ],
				build	: function( body ) { body.appendChild( setting( { key : 'linked', type : 'bool', label : '/_admin/builder/col/linked', hint : '/_admin/builder/hint/linked' } ) ) },
				says	: function() { return col.linked === true ? word('col/linked') : '' },
			};
			delete controls.bgPl;
			delete controls.bgPr;

			return controls;
		},


		/**
		 *	The viewports of a column as a table, a row for each that is shown (see
		 *	_viewportTable()): the width the column has there and whether it is hidden there
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		col
		 *
		 *	@return		void
		 */
		_widthTable : function( pane, col ) {

			const builder = Nino.admin.builder;

			builder._viewportTable( pane, [ Nino.content.getText('/_admin/builder/col/head-width'), Nino.content.getText('/_admin/builder/col/head-hidden') ], function( viewport ) {

				const device = Nino.content.getText( '/_admin/builder/viewport/'+ viewport );

				return [
					builder._keyed( builder._tableSelect( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/width'), device ), builder._widths(), col.width[viewport], function( value ) {
						builder._setWidth( col, viewport, value );
						builder._changed();
					} ), 'width' ),
					builder._keyed( builder._hiddenBox( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/hidden'), device ), col.hidden[viewport] === true, function( checked ) {
						builder._setHidden( col, viewport, checked );
						builder._changed();
					} ), 'hidden' ),
				];
			} );
		},

		/**
		 *	The switches that turn the columns of a row round, as the hidden box of a column is drawn: a table with a row for each viewport that is shown (see
		 *	_viewportTable()) - the one of the view, or all three in Global, each row with its icon and its word, and the link to all of them under the table of one row.
		 *	A switch writes its viewport in the map of the section (settings.reverse); it is marked with the setting and with the viewport, and the hint stands over the table
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		settings					Of the section
		 *	@param		{string}		hint
		 *
		 *	@return		void
		 */
		_reverseSwitches : function( pane, settings, hint ) {

			const builder = Nino.admin.builder;
			const label = Nino.content.getText('/_admin/builder/section/reverse');

			settings.reverse = Object.assign( { s : false, m : false, l : false }, settings.reverse );
			pane.appendChild( builder._hinted( builder._el( 'span', 'builder-note', label ), hint, label ) );

			builder._viewportTable( pane, [ label ], function( viewport ) {

				const box = builder._hiddenBox( Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/reverse-in'), Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) ), settings.reverse[viewport] === true, function( checked ) {
					settings.reverse[viewport] = checked;
					builder._changed();
				} );

				box.dataset.viewport = viewport;

				return [ builder._keyed( box, 'reverse' ) ];
			} );
		},

		/**
		 *	The widths of a column or of a cell of a loop, as the options of a select
		 *
		 *	@return		{Array}										[ { value, label } ]
		 */
		_widths : function() {
			return Nino.admin.builder.COL_WIDTHS.map( function( width ) { return { value : width, label : width } } );
		},

		/**
		 *	The box that hides a column in a viewport, in a cell of the viewport table
		 *
		 *	@param		{string}		label							What a screen reader calls it
		 *	@param		{boolean}		checked
		 *	@param		{Function}	onChange					Called with whether it is checked
		 *
		 *	@return		{Element}
		 */
		_hiddenBox : function( label, checked, onChange ) {

			const box = Nino.admin.builder._el( 'input' );

			box.type = 'checkbox';
			box.checked = checked === true;
			box.setAttribute( 'aria-label', label );
			box.addEventListener( 'change', function() { onChange( box.checked ) } );

			return box;
		},

		/**
		 *	The loop of a column: Static, or one of the registry, and the form of the one
		 *	that is chosen, in groups - the data it runs over, the order, the grid of its
		 *	cells, and what the loop brings of its own. The components of the column stay
		 *	where they are when it changes; what their sources mean there is checked, and a
		 *	source that means nothing is red until it is changed. The loop of a section is
		 *	the same without the grid - Static or the loop that makes a section of every
		 *	element, whose cell is the section: its id is the section's, shown and not
		 *	changed here. A loop in a loop is none: a section that makes sections has no
		 *	column that runs a loop, and one with such a column cannot start to
		 *
		 *	@param		{Element}		pane
		 *	@param		{Array}			path					The column - or the section
		 *
		 *	@return		void
		 */
		_stackForm : function( pane, path ) {

			const builder = Nino.admin.builder;
			const registry = builder._registry;
			const section = builder._doc.model.blocks[path[0]];
			const whole = path.length === 1;
			const col = whole === true ? section : section.cols[path[1]];
			const own = builder._sectionLoop( registry );
			const names = Object.keys( registry.stacks || {} ).filter( function( name ) { return ( name === own ) === whole } );
			const options = [ { value : '', label : Nino.content.getText('/_admin/builder/stack/static') } ].concat( names.map( function( name ) {
				return { value : name, label : whole === true ? Nino.content.getText('/_admin/builder/stack/sections') : builder._stackLabel( registry, name ) };
			} ) );
			// The one that would be the loop in a loop is held back, and says why
			const held = whole === true ? ( builder._looping( section ) === false && section.cols.some( function( other ) { return other.stack !== null && other.stack !== undefined } ) === true ) : builder._looping( section ) === true;
			const kind = builder._selectField( Nino.content.getText('/_admin/builder/stack/kind'), '', options, col.stack === null || col.stack === undefined ? '' : col.stack.name, function( name ) {

				col.stack = name === '' ? null : builder._stackFor( registry, name, col.stack === null || col.stack === undefined ? null : col.stack );

				if( whole === false )
					builder._cellFor( registry, path );

				// A call makes no cover of another height
				if( whole === true && col.stack !== null && section.settings.cover !== null )
					section.settings.cover = 90;

				builder._changed();
				builder._refreshControls();
			} );

			kind.querySelector('select').disabled = held === true;
			pane.appendChild( kind );

			if( held === true )
				pane.appendChild( Nino.adminUi.notice( Nino.content.getText( whole === true ? '/_admin/builder/stack/in-column' : '/_admin/builder/stack/in-section' ) ) );

			if( col.stack === null || col.stack === undefined )
				return;

			const stack = col.stack;
			const schema = registry.stacks[stack.name] || {};
			const grid = whole === false && schema.grid !== false;
			const type = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === stack.source } );
			const typeOptions = ( registry.types || [] ).map( function( candidate ) { return { value : candidate.uri, label : candidate.title+ ' ('+ candidate.uri+ ')' } } );
			const number = function( key, label, hint ) {
				return builder._numberField( Nino.content.getText( label ), hint === undefined ? '' : Nino.content.getText( hint ), parseInt( stack.attributes[key], 10 ) || 0, 0, 100000, function( value ) {
					stack.attributes[key] = String( value === null ? 0 : value );
					builder._changed();
				} );
			};
			// The id of a loop that makes sections is the id of the section, which is changed in the title: the sections are numbered after it
			const id = whole === true
				? builder._textField( Nino.content.getText('/_admin/builder/stack/id'), Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/sections-id'), section.id, section.id ), section.id, function() {}, { readonly : true } )
				: builder._textField( Nino.content.getText('/_admin/builder/stack/id'), Nino.content.getText('/_admin/builder/hint/stack-id'), stack.attributes.id, function( value ) {
					stack.attributes.id = value;
					builder._changed();
				} );

			if( type === undefined )
				typeOptions.unshift( { value : stack.source, label : stack.source === '' ? Nino.content.getText('/_admin/builder/label/none') : stack.source } );

			// What the loop runs over; a loop without a grid has its id here, since it has no grid to put it in
			const data = builder._group( pane, Nino.content.getText('/_admin/builder/group/data') );

			data.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/type'), Nino.content.getText('/_admin/builder/stack/type-hint'), typeOptions, stack.source, function( value ) {
				stack.source = value;
				builder._changed();
				builder._refreshControls( 'stack' );
			} ) );

			// A type the project does not have is a thing to say where it is, and to point to the panel that makes it
			if( type === undefined )
				data.appendChild( Nino.adminUi.notice( Nino.content.getText('/_admin/builder/stack/type-missing'), { href : '#types', label : Nino.content.getText('/_admin/builder/stack/types-link') } ) );

			data.appendChild( builder._textField( Nino.content.getText('/_admin/builder/stack/query'), Nino.content.getText('/_admin/builder/hint/query'), stack.attributes.query, function( value ) {
				stack.attributes.query = value;
				builder._changed();
			} ) );

			if( grid === false )
				data.appendChild( id );

			// In which order, and how many
			const order = builder._group( pane, Nino.content.getText('/_admin/builder/group/order') );

			builder._sortField( order, stack, type === undefined ? {} : type.fields );
			order.appendChild( number( 'limit', '/_admin/builder/stack/limit', '/_admin/builder/hint/limit' ) );
			order.appendChild( number( 'offset', '/_admin/builder/stack/offset' ) );

			// The cells: how wide in each viewport, how far apart, as high as the highest
			if( grid === true ) {

				const cells = builder._group( pane, Nino.content.getText('/_admin/builder/group/grid') );

				builder._cellsTable( cells, stack );

				cells.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/gap'), '', builder._options( 'space', builder.SPACES.slice( 1 ) ), String( stack.attributes.gap ), function( value ) {
					stack.attributes.gap = value;
					builder._changed();
				} ) );
				cells.appendChild( builder._switchField( Nino.content.getText('/_admin/builder/stack/autoheight'), '', stack.attributes.autoheight === '1', function( checked ) {
					stack.attributes.autoheight = checked === true ? '1' : '0';
					builder._changed();
				} ) );

				cells.appendChild( id );
			}

			const declared = Object.assign( {}, builder._declared( schema ) );
			const cell = builder._cellKind( registry, stack );
			const fields = type === undefined ? {} : type.fields;

			// The style of a table and of a list is drawn with the words of the panel
			if( ( cell === 'columns' || cell === 'row' ) && declared.style !== undefined ) {

				const options = ( declared.style.options || [] ).map( function( word ) {
					return { value : word, label : Nino.content.getText( '/_admin/builder/stack/style-'+ ( word === '' ? 'plain' : word ) ) };
				} );

				if( options.some( function( option ) { return option.value === String( stack.attributes.style ) } ) === false )
					options.push( { value : String( stack.attributes.style ), label : String( stack.attributes.style ) } );

				builder._group( pane, Nino.content.getText('/_admin/builder/group/style') ).appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/style'), '', options, String( stack.attributes.style ), function( value ) {
					stack.attributes.style = value;
					builder._changed();
				} ) );

				delete declared.style;
			}

			if( Object.keys( declared ).length > 0 )
				builder._attributeFields( builder._group( pane, Nino.content.getText('/_admin/builder/group/own') ), declared, stack.attributes, {
					fields	: fields,
					section	: section,
					around	: whole === true ? ( section.settings.color || '' ) : builder._surface( section, col ),
					redraw	: function() { builder._refreshControls( 'stack' ) },
				} );

			// What a cell of the loop is made of, where the loop has a form for it: the columns of a table, the row of a list
			if( cell === 'columns' )
				builder._columnsEditor( builder._group( pane, Nino.content.getText('/_admin/builder/group/columns') ), path, fields );
			else if( cell === 'row' )
				builder._rowEditor( builder._group( pane, Nino.content.getText('/_admin/builder/group/row') ), path, fields );
		},

		/**
		 *	What a loop is the cell of, as its schema says it ('cell'): section for the loop of sections, columns for the loop whose content is the columns of a table, row for the loop
		 *	whose cell may be one row of a field or of a line of text. The Builder asks the schema and has no name for any of them
		 *
		 *	@param		{Object}		registry
		 *	@param		{Object}		stack						{ name, source, attributes }
		 *
		 *	@return		{string}									'' for a loop whose cell is the components of its column
		 */
		_cellKind : function( registry, stack ) {
			return ( ( registry.stacks || {} )[stack.name] || {} ).cell || '';
		},

		/**
		 *	Whether a component is a column of a table by its schema and not by its name: it belongs in no place of a page (section, card, hero) but only in a place a loop holds (LOOP_PLACES)
		 *
		 *	@param		{Object}		schema						The component's entry of the registry
		 *
		 *	@return		{boolean}
		 */
		_columnar : function( schema ) {

			const places = Array.isArray( schema.in ) === true ? schema.in : [];

			return places.length > 0 && places.every( function( place ) { return Nino.admin.builder.LOOP_PLACES.indexOf( place ) !== -1 } );
		},

		/**
		 *	The fields of a type that a cell can show, by name
		 *
		 *	@param		{Object}		fields						name => the field of the type
		 *	@param		{boolean}		pictures					Whether an image field is one of them
		 *
		 *	@return		{Array}										[ { value, label } ]
		 */
		_valueChoices : function( fields, pictures ) {

			const builder = Nino.admin.builder;

			return Object.keys( fields ).filter( function( name ) {
				return builder.SHOWN.indexOf( fields[name].type ) !== -1 || ( pictures === true && fields[name].type === 'image' );
			} ).map( function( name ) { return { value : name, label : name } } );
		},

		/**
		 *	A column that was changed to what its loop holds: a table has columns and nothing else, any other loop has none; a table has one, since it needs at least one,
		 *	and a list starts as a row of a field - or a line, where its type has no field to show. The components that are not the cell of the loop are dropped
		 *
		 *	@param		{Object}		registry
		 *	@param		{Array}			path							The column
		 *
		 *	@return		void
		 */
		_cellFor : function( registry, path ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;
			const col = model.blocks[path[0]].cols[path[1]];
			const kind = col.stack === null || col.stack === undefined ? '' : builder._cellKind( registry, col.stack );
			const columnar = function( component ) { return builder._columnar( ( registry.components || {} )[component.name] || {} ) };
			const type = col.stack === null || col.stack === undefined ? undefined : ( registry.types || [] ).find( function( candidate ) { return candidate.uri === col.stack.source } );
			const first = type === undefined ? [] : builder._valueChoices( type.fields || {}, kind === 'columns' );

			col.components = col.components.filter( function( component ) { return columnar( component ) === ( kind === 'columns' ) } );

			if( kind === 'columns' && col.components.length === 0 ) {

				const node = builder._newComponent( model, registry, builder._doc.file, path, builder._columnName( registry ), builder._keys );

				node.attributes.field = first.length > 0 ? first[0].value : '';
				col.components.push( node );
			}

			if( kind === 'row' && col.components.length === 0 )
				builder._rowSet( registry, path, first.length > 0 ? first[0].value : builder.FREE, '' );
		},

		/**
		 *	The name of the component that is a column of a table, as the registry has it
		 *
		 *	@param		{Object}		registry
		 *
		 *	@return		{string}
		 */
		_columnName : function( registry ) {

			const builder = Nino.admin.builder;

			return Object.keys( registry.components || {} ).find( function( name ) { return builder._columnar( registry.components[name] ) } ) || '';
		},

		/**
		 *	Whether a line of free text can be carried by a call: no line break and no double bracket that opens no fill, [[name]] - a quote, an ampersand and a single bracket are written as entities
		 *
		 *	@param		{string}		text
		 *
		 *	@return		{boolean}
		 */
		_lineValid : function( text ) {
			return /\r|\n|\[\[/.test( String( text ).replace( /\[\[\/?[A-Za-z0-9_.\/-]+\]\]/g, '' ) ) === false;
		},

		/**
		 *	The select of what a cell shows: a field of the type by its name, and a line of free text. Where the field is none the type has, it is there as it stands, so that it is
		 *	seen and changed
		 *
		 *	@param		{Object}		fields
		 *	@param		{string}		current						A field, the empty word, or FREE
		 *	@param		{boolean}		pictures					Whether an image field may be shown
		 *	@param		{string}		label
		 *	@param		{Function}	onChange					( value )
		 *
		 *	@return		{Element}
		 */
		_valueSelect : function( fields, current, pictures, label, onChange ) {

			const builder = Nino.admin.builder;
			const items = builder._valueChoices( fields, pictures );

			if( current !== '' && current !== builder.FREE && items.some( function( item ) { return item.value === current } ) === false )
				items.push( { value : current, label : current } );

			items.push( { value : builder.FREE, label : Nino.content.getText('/_admin/builder/column/free') } );

			return builder._selectField( label, Nino.content.getText('/_admin/builder/hint/column-value'), items, current, onChange );
		},

		/**
		 *	The input of a line of free text, with the fields it can take named in its hint, and the sentence that says the line cannot be carried while it holds a quote or a bracket
		 *
		 *	@param		{Object}		fields
		 *	@param		{string}		text
		 *	@param		{Function}	onChange					( text )
		 *
		 *	@return		{Element}
		 */
		_lineField : function( fields, text, onChange ) {

			const builder = Nino.admin.builder;
			const wrap = builder._el( 'div', 'builder-line-field' );
			const shown = builder._valueChoices( fields, false ).map( function( item ) { return item.value } );
			const names = shown.slice( 0, 12 ).map( function( name ) { return '[['+ name+ ']]' } ).join(', ');
			const warn = builder._el( 'p', 'nino-admin-hint builder-line-warning', Nino.content.getText('/_admin/builder/hint/line-invalid') );
			const unknown = builder._el( 'p', 'nino-admin-hint builder-line-warning builder-line-unknown' );
			const check = function( value ) {

				warn.hidden = builder._lineValid( value );

				const lost = builder._lineFills( value ).filter( function( fill ) { return fill.charAt(0) !== '/' && [ '.id', '.uri', '.slug' ].indexOf( fill ) === -1 && shown.indexOf( fill ) === -1 } );

				unknown.hidden = lost.length === 0;
				unknown.textContent = Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/line-unknown'), lost.map( function( fill ) { return '[['+ fill+ ']]' } ).join(', ') );
			};
			const field = builder._textField( Nino.content.getText('/_admin/builder/column/text'), Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/column-text'), names ), text, function( value ) {

				check( value );
				onChange( value );
			}, { placeholder : '[[price]],- €' } );

			check( text );
			wrap.appendChild( field );
			wrap.appendChild( warn );
			wrap.appendChild( unknown );

			return wrap;
		},

		/**
		 *	The names of the fills of a line of free text, in order
		 *
		 *	@param		{string}		text
		 *
		 *	@return		{Array}
		 */
		_lineFills : function( text ) {
			return ( String( text ).match( /\[\[\/?[A-Za-z0-9_.\/-]+\]\]/g ) || [] ).map( function( fill ) { return fill.slice( 2, -2 ) } );
		},

		/**
		 *	The editor of the columns of a table: a row for each column with the title (the text key of the head of the column, or none) and what its cells show - a field of the
		 *	type, or a line of free text with fields in it -, the buttons that move a column up and down and take it away, and the button that adds one. A table needs one
		 *
		 *	@param		{Element}		pane
		 *	@param		{Array}			path							The column that runs the table
		 *	@param		{Object}		fields						The fields of the type the table runs over
		 *
		 *	@return		void
		 */
		_columnsEditor : function( pane, path, fields ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const section = doc.model.blocks[path[0]];
			const col = section.cols[path[1]];
			const registry = builder._registry;
			const list = builder._el( 'div', 'builder-columns' );
			const draw = function() {

				builder._columnEditors.splice( 0 ).forEach( function( made ) { made.destroy() } );
				list.innerHTML = '';

				col.components.forEach( function( node, k ) { list.appendChild( builder._columnRow( col, node, k, section, fields, path.concat( [ k ] ), draw ) ) } );

				if( col.components.length === 0 )
					list.appendChild( Nino.adminUi.notice( Nino.content.getText('/_admin/builder/stack/columns-none') ) );

				const add = builder._el( 'button', 'nino-admin-btn-secondary builder-column-add', Nino.content.getText('/_admin/builder/stack/column-add') );

				add.type = 'button';
				add.addEventListener( 'click', function() {

					const node = builder._newComponent( doc.model, registry, doc.file, path, builder._columnName( registry ), builder._keys );
					const first = builder._valueChoices( fields, true );

					node.attributes.field = first.length > 0 ? first[0].value : '';
					col.components.push( node );
					builder._changed();
					draw();
				} );
				list.appendChild( add );
			};

			pane.appendChild( list );
			draw();
		},

		// The editors of the text under a title key that the form of the columns made: drawn away with the form
		_columnEditors : [],

		/**
		 *	One column of a table in the editor of the columns
		 *
		 *	@param		{Object}		col								The column of the page, whose components the columns are
		 *	@param		{Object}		node							The column of the table
		 *	@param		{number}		k									Its place among them
		 *	@param		{Object}		section
		 *	@param		{Object}		fields
		 *	@param		{Array}			path							The node
		 *	@param		{Function}	redraw
		 *
		 *	@return		{Element}
		 */
		_columnRow : function( col, node, k, section, fields, path, redraw ) {

			const builder = Nino.admin.builder;
			const registry = builder._registry;
			const row = builder._el( 'div', 'builder-column' );
			const head = builder._el( 'div', 'builder-column-head' );
			const move = function( to ) {

				col.components.splice( to, 0, col.components.splice( k, 1 )[0] );
				builder._changed();
				redraw();
			};
			const up = builder._iconButton( 'up', Nino.content.getText('/_admin/builder/column/up'), function() { move( k - 1 ) } );
			const down = builder._iconButton( 'down', Nino.content.getText('/_admin/builder/column/down'), function() { move( k + 1 ) } );
			const remove = builder._iconButton( 'trash', Nino.content.getText('/_admin/builder/column/remove'), function() {

				col.components.splice( k, 1 );
				builder._changed();
				redraw();
			} );

			head.appendChild( builder._el( 'span', 'builder-label', Nino.adminUi.format( Nino.content.getText('/_admin/builder/column/number'), k + 1 ) ) );
			up.disabled = k === 0;
			down.disabled = k === col.components.length - 1;
			head.appendChild( up );
			head.appendChild( down );
			head.appendChild( remove );
			row.appendChild( head );

			// The title: the text key of the head of the column, made here as a title is, or none
			const extra = builder._el( 'div', 'builder-content-extra' );
			const fill = function() {

				extra.innerHTML = '';

				const text = builder._textOf( node, builder._locale, builder._keys, registry );

				if( text !== null )
					extra.appendChild( builder._valueField( node, text, builder._columnEditors ) );

				extra.hidden = extra.children.length === 0;
			};

			row.appendChild( builder._sourceRow( {
				kind		: 'text',
				fixed		: false,
				create	: true,
				fields	: null,
				value		: node.source,
				text		: null,
				section	: section,
				name		: node.name,
				seed		: Nino.content.getText('/_admin/builder/column/seed'),
				caption	: '',
				label		: Nino.content.getText('/_admin/builder/column/title'),
				red			: builder._red( builder._doc.model, registry ).find( function( entry ) { return builder._same( entry.path, path ) } ),
				onPick	: function( source, create ) {

					if( source !== node.source )
						delete node.values;

					node.source = source;

					if( create === null )
						delete node.create;
					else {
						node.create = create;
						builder._placeholders( registry, node );
					}

					builder._changed();
					fill();
				},
				onFixed	: function() {},
			} ) );
			row.appendChild( extra );
			fill();

			// What the cells show
			const free = typeof node.text === 'string';

			row.appendChild( builder._valueSelect( fields, free === true ? builder.FREE : String( node.attributes.field ?? '' ), true, Nino.content.getText('/_admin/builder/column/value'), function( value ) {

				if( value === builder.FREE ) {
					node.text = node.text ?? '';
					node.attributes.field = '';
				} else {
					node.text = null;
					node.attributes.field = value;
				}

				builder._changed();
				redraw();
			} ) );

			if( free === true )
				row.appendChild( builder._lineField( fields, node.text, function( value ) {
					node.text = value;
					builder._changed();
				} ) );

			return row;
		},

		/**
		 *	The row of a list, as the cell of the list holds it: exactly one component, the field of the element ([text <field>]) or a line of free text ([line]), with
		 *	the attributes the schema starts them with and nothing else. A list whose cell is more than that - or other - is not a row
		 *
		 *	@param		{Object}		registry
		 *	@param		{Object}		col								The column that runs the list
		 *
		 *	@return		{Object|null}							{ field } or { text }, null where the cell is the components of the column
		 */
		_rowOf : function( registry, col ) {

			const builder = Nino.admin.builder;

			if( col.components.length !== 1 )
				return null;

			const node = col.components[0];
			const defaults = ( ( registry.components || {} )[node.name] || {} ).defaults || {};
			const attributes = node.attributes || {};
			const plain = Object.keys( defaults ).every( function( key ) { return String( attributes[key] ?? '' ) === String( defaults[key] ) } ) && Object.keys( attributes ).length === Object.keys( defaults ).length
				&& builder.SIDES.every( function( side ) { return ( node[side] ?? '' ) === '' } ) && ( node.radius ?? '' ) === '' && ( node.hover ?? '' ) === '' && node.create === undefined;

			if( plain === false )
				return null;

			if( node.name === builder.ROW_FIELD && node.text === null && node.source !== '' && node.source.charAt(0) !== '/' )
				return { field : node.source };

			if( node.name === builder.ROW_LINE && node.source === '' && typeof node.text === 'string' )
				return { text : node.text };

			return null;
		},

		/**
		 *	The row of a list made anew: the field of the element, or a line of free text where the value is FREE
		 *
		 *	@param		{Object}		registry
		 *	@param		{Array}			path							The column that runs the list
		 *	@param		{string}		value							A field, or FREE
		 *	@param		{string}		text							The line, for FREE
		 *
		 *	@return		void
		 */
		_rowSet : function( registry, path, value, text ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;
			const col = model.blocks[path[0]].cols[path[1]];
			const free = value === builder.FREE;
			const node = builder._newComponent( model, registry, builder._doc.file, path, free === true ? builder.ROW_LINE : builder.ROW_FIELD, builder._keys );

			if( free === true )
				node.text = text;
			else
				node.source = value;

			col.components = [ node ];
		},

		/**
		 *	The row of a list in the form of the loop: what the row shows - a field of the type or a line of free text - for a list whose cell is a row, and a sentence that
		 *	says the cell is made of components of its own for a list whose cell is more
		 *
		 *	@param		{Element}		pane
		 *	@param		{Array}			path							The column that runs the list
		 *	@param		{Object}		fields
		 *
		 *	@return		void
		 */
		_rowEditor : function( pane, path, fields ) {

			const builder = Nino.admin.builder;
			const registry = builder._registry;
			const col = builder._doc.model.blocks[path[0]].cols[path[1]];
			const row = builder._rowOf( registry, col );

			if( row === null ) {
				pane.appendChild( Nino.adminUi.notice( Nino.content.getText('/_admin/builder/stack/row-cells') ) );
				return;
			}

			const free = row.text !== undefined;

			pane.appendChild( builder._valueSelect( fields, free === true ? builder.FREE : row.field, false, Nino.content.getText('/_admin/builder/column/value'), function( value ) {

				builder._rowSet( registry, path, value, '' );
				builder._changed();
				builder._refreshControls( 'stack' );
			} ) );

			if( free === true )
				pane.appendChild( builder._lineField( fields, row.text, function( value ) {
					col.components[0].text = value;
					builder._changed();
				} ) );
		},

		/**
		 *	The cells of a loop as a table, a row for each viewport that is shown (see
		 *	_viewportTable()): how wide a cell is there. A loop that names fewer widths
		 *	than there are viewports takes the last for the others
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		stack
		 *
		 *	@return		void
		 */
		_cellsTable : function( pane, stack ) {

			const builder = Nino.admin.builder;
			const given = String( stack.attributes.cols || '100 50 33' ).split( /\s+/ );
			const cells = builder.VIEWPORTS.map( function( viewport, at ) { return given[at] ?? given[given.length - 1] } );

			builder._viewportTable( pane, [ Nino.content.getText('/_admin/builder/stack/head-cells') ], function( viewport ) {

				const at = builder.VIEWPORTS.indexOf( viewport );

				return [ builder._tableSelect( Nino.adminUi.format( Nino.content.getText('/_admin/builder/stack/cells'), Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) ), builder._widths(), cells[at], function( value ) {
					cells[at] = value;
					stack.attributes.cols = cells.join(' ');
					builder._changed();
				} ) ];
			} );
		},

		/**
		 *	The order of a loop: a field of the type to sort by, and two toggles, an
		 *	arrow up for ascending and an arrow down for descending - and, for a list
		 *	of several fields, the text itself
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		stack
		 *	@param		{Object}		fields						The fields of the loop's type
		 *
		 *	@return		void
		 */
		_sortField : function( pane, stack, fields ) {

			const builder = Nino.admin.builder;
			const state = builder._sortState( stack );
			const label = Nino.content.getText('/_admin/builder/stack/sort');
			const hint = Nino.content.getText('/_admin/builder/hint/sort');

			if( state.several === true ) {
				pane.appendChild( builder._textField( label, hint, state.field, function( value ) { stack.attributes.sort = value; builder._changed() } ) );
				return;
			}

			const names = Object.keys( fields );
			const choices = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ].concat( names.map( function( name ) { return { value : name, label : name } } ) );

			if( state.field !== '' && names.indexOf( state.field ) === -1 )
				choices.push( { value : state.field, label : state.field } );

			pane.appendChild( builder._selectField( label, hint, choices, state.field, function( value ) {
				state.field = value;
				builder._sortSet( stack, state.field, state.descending );
				builder._changed();
			} ) );

			pane.appendChild( builder._sortToggles( state.descending, function( descending ) {
				state.descending = descending;
				builder._sortSet( stack, state.field, state.descending );
				builder._changed();
			} ) );
		},

		/**
		 *	The two arrows of an order, of which one is on: a choice of two icons, up for
		 *	ascending and down for descending
		 *
		 *	@param		{boolean}		descending
		 *	@param		{Function}	onChange					Called with whether the order is descending
		 *
		 *	@return		{Element}
		 */
		_sortToggles : function( descending, onChange ) {

			return Nino.admin.builder._segmentField( Nino.content.getText('/_admin/builder/stack/direction'), [
				{ value : 'asc', label : Nino.content.getText('/_admin/builder/stack/asc'), icon : 'arrow-up' },
				{ value : 'desc', label : Nino.content.getText('/_admin/builder/stack/desc'), icon : 'arrow-down' },
			], descending === true ? 'desc' : 'asc', function( value ) { onChange( value === 'desc' ) } );
		},

		// --------------------------------------------------------- Components

		/**
		 *	The fields of the type the loop of a column runs over - its own, or the loop of the section whose cell the column is
		 *
		 *	@param		{Object}		section
		 *	@param		{Object}		col
		 *
		 *	@return		{Object|null}							null for a column that runs no loop, no fields for a type the project does not have
		 */
		_typeFields : function( section, col ) {

			const loop = Nino.admin.builder._loopOf( section, col );

			if( loop === null )
				return null;

			return ( ( Nino.admin.builder._registry.types || [] ).find( function( candidate ) { return candidate.uri === loop.source } ) || { fields : {} } ).fields;
		},

		/**
		 *	The attributes of a schema as a map - php sends an empty one as a list
		 *
		 *	@param		{Object}		schema
		 *
		 *	@return		{Object}
		 */
		_declared : function( schema ) {
			return Array.isArray( schema.attributes ) === true || schema.attributes === undefined ? {} : schema.attributes;
		},

		/**
		 *	The form of a component, in the blocks the table has for it (BLOCKS.component) and its schema fills: its content - the source of its text or its picture and, under it,
		 *	the text of the key in the language the editor works in or the picture of the slot with its alt text; for [html] the editor of the blocks profile instead -, its
		 *	link - the address and the window it opens in, and whether all of it is the link, with the address and the window of its title under that switch -, its surface - the attributes the schema puts on the tab of the background, and the
		 *	toggle of its radius and the one of its hover, which a picture has -, its spacing - the box of its sides - and its properties: the variants and the other attributes, in the order of the schema, and the classes of its
		 *	own. A component that holds components has no content: its children are edited where they stand, each in the form every component has. A block the component has
		 *	nothing for is not drawn
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		[block]						The block to open: the one that was open last in a dialog of that component, by default
		 *
		 *	@return		void
		 */
		_componentDialog : function( path, block ) {

			const builder = Nino.admin.builder;
			const component = builder._get( builder._doc.model, path );
			const schema = ( builder._registry.components || {} )[component.name] || {};
			const container = builder._holds( builder._registry, component.name ) !== '';
			// The editor of an [html] component and those of the texts of a key, which the dialog lets go of when it closes
			const state = { editors : [], editor : null, typed : false };

			builder._dialog( {
				title		: container === true ? ( schema.label || component.name ) : Nino.adminUi.format( Nino.content.getText('/_admin/builder/component/title'), schema.label || component.name ),
				blame		: path[0],
				returnTo	: path,
				kind		: 'component:'+ component.name,
				block		: block,
				blocks	: builder._blocksOf( 'component', builder._componentControls( path, state ) ),
				onClose	: function() {

					state.editors.splice( 0 ).forEach( function( made ) { made.destroy() } );

					// The content stays as it was where nothing was typed: the editor shows it in its own normal form, which is not the file's
					if( state.editor !== null ) {

						if( state.typed === true ) {
							component.content = state.editor.getValue();
							builder._changed();
						}

						state.editor.destroy();
					}
				},
			} );
		},

		/**
		 *	The group of controls an attribute of a schema belongs to, which the table places in a block (see BLOCKS): the background for what the schema puts on the tab of the
		 *	background, the link for an address and for the attributes that are a link (LINK_KEYS), the variants, and the attributes for every other
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry
		 *
		 *	@return		{string}									background, link, variants or attributes
		 */
		_groupOf : function( name, declared ) {

			if( declared.tab === 'background' )
				return 'background';

			if( declared.type === 'href' || Nino.admin.builder.LINK_KEYS.indexOf( name ) !== -1 )
				return 'link';

			return declared.type === 'variant' ? 'variants' : 'attributes';
		},

		/**
		 *	The controls of the form of a component, by the name the table lists them under (see BLOCKS and _sectionControls()): the source, one control for each group of the
		 *	attributes of its schema - the link, the background, the variants and the other attributes, each a field to the attribute in the order of the schema -, the offsets of the
		 *	picture behind a card, the radius of a surface and the hover of a picture, the box of the six sides and the classes of the component. A control the component has nothing for is not one of them
		 *
		 *	@param		{Array}			path
		 *	@param		{Object}		state							{ editors, editor, typed }, kept by the dialog for what is made in the content and is let go of when it closes
		 *
		 *	@return		{Object}									name of the control => its entry (see _blocksOf())
		 */
		_componentControls : function( path, state ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const component = builder._get( doc.model, path );
			const schema = ( builder._registry.components || {} )[component.name] || {};
			const kind = builder._sourceKind( builder._registry, component.name );
			const surface = builder._surfaceOf( schema );
			const section = doc.model.blocks[path[0]];
			const col = section.cols[path[1]];
			const fields = builder._typeFields( section, col );
			const declared = builder._declared( schema );
			const word = function( key ) { return Nino.content.getText( '/_admin/builder/'+ key ) };
			// What the attributes of the schema are to each other, told by the whole schema: the focus and the dimming of a picture follow it wherever they stand
			const roles = builder._roles( declared );
			// The attributes by the group they stand in, in the order of the schema
			const groups = { link : {}, background : {}, variants : {}, attributes : {} };

			Object.keys( declared ).forEach( function( name ) { groups[builder._groupOf( name, declared[name] )][name] = declared[name] } );

			// An address and the window it opens in are what a component that is a link has besides the attributes it declares, which the schema does not declare for it
			groups.link = builder._linkFields( schema );

			const context = function( name ) {
				return { fields : fields, section : section, around : builder._surface( section, col ), roles : roles, node : component, path : path, redraw : function() { builder._refreshControls( name ) } };
			};
			const said = function( from ) {
				return builder._said( Object.keys( from ).map( function( name ) { return builder._attributeSaid( name, from[name], component.attributes[name], schema ) } ) );
			};
			// The control of a group of attributes: a field to each
			const attributes = function( group ) {
				return {
					keys	: Object.keys( groups[group] ),
					build	: function( body ) {

						const here = context( group );

						// Whether all of a card is the link: the switch turned draws the block again, and on it shows where the link goes (see _stretchedLink())
						if( group === 'link' && groups.link.linked !== undefined ) {

							here.after = function( changed ) {
								if( changed === 'linked' )
									builder._refreshControls( group );
							};
							builder._attributeFields( body, groups[group], component.attributes, here );
							builder._stretchedLink( body, path, here );

							return;
						}

						builder._attributeFields( body, groups[group], component.attributes, here );
					},
					says	: function() { return said( groups[group] ) },
				};
			};
			const controls = {};
			const sides = {
				keys	: builder.SIDES,
				build	: function( body ) { body.appendChild( builder._boxField( component, { name : '', hint : word('hint/spacing'), outside : [ 'mt', 'mb' ], inside : [ 'pt', 'pb', 'pl', 'pr' ] } ) ) },
				says	: function() { return builder._boxSaid( component, [ 'mt', 'mb' ], [ 'pt', 'pb', 'pl', 'pr' ] ) },
			};

			if( kind !== 'none' && builder._holds( builder._registry, component.name ) === '' )
				controls.source = {
					keys	: [ 'source' ],
					build	: function( body ) { builder._componentContent( body, path, state ) },
					says	: function() { return builder._contentSaid( component, kind ) },
				};

			Object.keys( groups ).forEach( function( group ) {
				if( Object.keys( groups[group] ).length > 0 )
					controls[group] = attributes( group );
			} );

			// A card paints a ground, and has how far in from its sides it starts
			if( component.bgPl !== undefined )
				controls.bgPl = controls.bgPr = {
					keys	: [ 'bgPl', 'bgPr' ],
					build	: function( body ) { body.appendChild( builder._offsetField( component, [ 'bgPl', 'bgPr' ], word('section/offset'), word('hint/offset') ) ) },
					says	: function() { return builder._offsetSaid( component, [ 'bgPl', 'bgPr' ], word('section/offset') ) },
				};

			// A component that is a surface has a radius of its own, which is no attribute of its call
			if( surface !== '' )
				controls.radius = {
					keys	: [ 'radius' ],
					build	: function( body ) { body.appendChild( builder._radiusToggle( component, 'radius', surface, function() { return true } ) ) },
					says	: function() { return component.radius === '' ? '' : word( component.radius === 'alternative' ? 'common/radius-alt' : 'common/radius-standard' ) },
				};

			// A picture - by what the schema says its source is, so the frame of [image] and the picture of a card alike - takes part in the hover effect of the page by a switch
			if( kind === 'image' )
				controls.hover = {
					keys	: [ 'hover' ],
					build	: function( body ) { body.appendChild( builder._hoverToggle( component ) ) },
					says	: function() { return component.hover === 'on' ? word('common/hover') : '' },
				};

			builder.SIDES.forEach( function( side ) { controls[side] = sides } );

			controls['class'] = {
				keys	: [ 'class' ],
				build	: function( body ) {
					body.appendChild( builder._keyed( builder._textField( word('component/custom'), word('hint/custom'), component.attributes['class'], function( value ) {
						component.attributes['class'] = value;
						builder._changed();
					} ), 'class' ) );
				},
				says	: function() { return String( component.attributes['class'] ?? '' ) === '' ? '' : word('group/custom') },
			};

			return controls;
		},

		/**
		 *	The attributes of a component that are its link, by name: the ones its schema declares in that group (see _groupOf()) and - for a component the registry marks as a
		 *	link - the address and the window it opens in, which the schema does not declare for it and which every link has
		 *
		 *	@param		{Object}		schema						The component's entry of the registry
		 *
		 *	@return		{Object}									name => the entry of the schema, or its stand-in for the two a link has
		 */
		_linkFields : function( schema ) {

			const builder = Nino.admin.builder;
			const declared = builder._declared( schema );
			const found = {};

			Object.keys( declared ).forEach( function( name ) {
				if( builder._groupOf( name, declared[name] ) === 'link' )
					found[name] = declared[name];
			} );

			if( schema.link !== true )
				return found;

			return Object.assign( {
				href		: { type : 'href', label : Nino.content.getText('/_admin/builder/link/href'), hint : Nino.content.getText('/_admin/builder/hint/link-href') },
				target	: { type : 'select', options : builder.LINK_TARGETS, label : Nino.content.getText('/_admin/builder/link/target') },
			}, found );
		},

		/**
		 *	Where the link of a card goes, under the switch that makes all of the card the link: the link of the last child whose component says in the registry that its link may
		 *	be the card's ('stretched'; the Builder knows none by name), wherever it stands among the children - with more than one both cover the card, and the later one lies on top
		 *	and takes the clicks. A child whose schema says it is a link ('link') has the address and the window as fields of its attributes, the same fields its own form has; one
		 *	that draws a link of its own has none, and a line says whose link the card takes. More than one such child has a second line, that only one should be the card's.
		 *	A card that holds none says so in a line that names the components of the registry that say it and may stand in a card, by their labels. Nothing while the switch is off,
		 *	and nothing where no component of the registry says stretched: the block is the switch alone. The card keeps no address of its own: the link is that child's, as the kernel
		 *	draws it
		 *
		 *	@param		{Element}		body							The block of the link
		 *	@param		{Array}			path							The card
		 *	@param		{Object}		context						As the card's own fields have it (see _attributeFields())
		 *
		 *	@return		void
		 */
		_stretchedLink : function( body, path, context ) {

			const builder = Nino.admin.builder;
			const components = builder._registry.components || {};
			const card = builder._get( builder._doc.model, path );

			if( String( card.attributes.linked ?? '' ) !== '1' )
				return;

			const children = card.components ?? [];
			const line = function( key, label ) {
				const text = Nino.content.getText( '/_admin/builder/link/'+ key );
				body.appendChild( builder._el( 'p', 'nino-admin-hint', label === undefined ? text : Nino.adminUi.format( text, label ) ) );
			};
			const labelOf = function( name ) { return ( components[name] || {} ).label || name };
			// The children whose component says that its link may be the card's, by their place among the children, in the order they stand in
			const candidates = [];

			children.forEach( function( child, at ) {
				if( ( components[child.name] || {} ).stretched === true )
					candidates.push( at );
			} );

			// None: the line names what to add - the components that say it and may stand in a card (no place named is every place)
			if( candidates.length === 0 ) {

				const offered = Object.keys( components ).filter( function( name ) {
					return components[name].stretched === true && ( Array.isArray( components[name].in ) === false || components[name].in.indexOf('card') !== -1 );
				} );

				if( offered.length > 0 )
					line( 'no-stretched', offered.map( labelOf ).join(', ') );

				return;
			}

			const at = candidates[candidates.length - 1];
			const child = children[at];

			// A child that is a link has the address and the window the kernel wraps around what it draws; one that draws its own link has no field for it here
			if( components[child.name].link === true )
				builder._attributeFields( body, builder._linkFields( components[child.name] ), child.attributes, {
					fields	: context.fields,
					section	: context.section,
					around	: context.around,
					node		: child,
					path		: path.concat( [ at ] ),
					redraw	: context.redraw,
				} );
			else
				line( 'stretched', labelOf( child.name ) );

			if( candidates.length > 1 )
				line( 'two-stretched' );
		},

		/**
		 *	What the head of the content of a component says: the first words of its text, or the name of its picture's slot - nothing where it has none
		 *
		 *	@param		{Object}		component
		 *	@param		{string}		kind							What the component is by its schema (see _sourceKind())
		 *
		 *	@return		{string}
		 */
		_contentSaid : function( component, kind ) {

			const builder = Nino.admin.builder;
			const last = function( source ) { return String( source ?? '' ).split('/').pop() };

			if( kind === 'content' )
				return builder._firstLine( component.content ?? '', builder.SAID_MAX );

			if( kind !== 'text' )
				return last( component.source );

			const info = builder._textOf( component, builder._locale, builder._keys, builder._registry );

			if( info !== null )
				return builder._firstLine( info.value, builder.SAID_MAX );

			return typeof component.text === 'string' ? builder._firstLine( component.text, builder.SAID_MAX ) : last( component.source );
		},

		/**
		 *	What the head of a block says of an attribute whose value is not what its schema gives it: a switch by its name, a choice by the word of its value, a number by its name and
		 *	itself, a picture by its name, any other value cut short
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry
		 *	@param		{*}					value							What the component has
		 *	@param		{Object}		schema						The component's entry of the registry, whose defaults are the ones of the call
		 *
		 *	@return		{string}									'' where the value is the default
		 */
		_attributeSaid : function( name, declared, value, schema ) {

			const builder = Nino.admin.builder;
			const own = String( ( schema.defaults || {} )[name] ?? declared.default ?? '' );
			const now = value === undefined || value === null ? '' : String( value );
			const label = declared.label || name;

			if( now === own )
				return '';

			if( declared.type === 'bool' )
				return now === '1' ? label : '';

			if( declared.type === 'image' )
				return now === '' ? '' : label;

			if( declared.type === 'int' )
				return label+ ' '+ now;

			if( declared.type === 'select' && builder._isFocus( declared ) === true )
				return now === '' ? '' : label+ ' '+ Nino.content.getText( '/_admin/builder/focus/'+ builder.FOCUS[parseInt( now, 10 ) - 1] );

			if( declared.type === 'select' && builder._isGround( declared ) === true )
				return builder._groundLabel( now );

			if( declared.type === 'select' || declared.type === 'variant' ) {

				// The words of the option, else - for a value the method does not answer - the entry the dialog shows for it, else the value made into words
				const stale = builder._notOffered( declared, now );

				return builder._optionWords( declared, now ) || ( stale.length > 0 ? stale[0].label : builder._wordOf( declared.type === 'variant' ? 'variant' : ( Object.prototype.hasOwnProperty.call( builder.ATTRIBUTE_WORDS, name ) === true ? builder.ATTRIBUTE_WORDS[name] : 'attribute-'+ name ), now ) );
			}

			return builder._firstLine( now, builder.SAID_MAX );
		},

		/**
		 *	The content of a component in its block: the source of its text or its picture and, under it, the text of the key in the language the editor works in or the picture of
		 *	the slot with its alt text; for a component whose content is HTML the editor of the blocks profile. What an editor made here is kept in the state of the dialog, which
		 *	lets go of it when it closes
		 *
		 *	@param		{Element}		body
		 *	@param		{Array}			path							The component
		 *	@param		{Object}		state							{ editors, editor, typed }
		 *
		 *	@return		void
		 */
		_componentContent : function( body, path, state ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const component = builder._get( doc.model, path );
			const schema = ( builder._registry.components || {} )[component.name] || {};
			const kind = builder._sourceKind( builder._registry, component.name );
			const section = doc.model.blocks[path[0]];
			const col = section.cols[path[1]];
			const fields = builder._typeFields( section, col );

			if( kind === 'content' ) {

				const mount = builder._el( 'div', 'builder-content' );

				body.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/component/content') ) );
				body.appendChild( mount );

				if( typeof Nino.admin.htmlEditor === 'object' ) {
					state.editor = Nino.admin.htmlEditor.create( mount, component.content ?? '', builder.CONTENT_MAX, 8, 'blocks' );
					mount.addEventListener( 'input', function() { state.typed = true; component.content = state.editor.getValue(); builder._changed() } );
				}

				return;
			}

			// What is under the source: the text of its key, or the picture of its slot - and nothing where it has none
			const extra = builder._el( 'div', 'builder-content-extra' );
			const fill = function() {

				state.editors.splice( 0 ).forEach( function( made ) { made.destroy() } );
				extra.innerHTML = '';

				const text = kind === 'text' ? builder._textOf( component, builder._locale, builder._keys, builder._registry ) : null;

				if( text !== null )
					extra.appendChild( builder._valueField( component, text, state.editors ) );

				if( kind === 'image' && ( component.source.charAt(0) === '/' || ( component.source === '' && builder._loopOf( section, col ) === null ) ) )
					extra.appendChild( builder._pictureField( {
						owner		: {
							get : function() { return { uri : component.source, create : component.create } },
							put : function( uri, create ) {
								component.source = uri;
								component.text = null;

								if( create === undefined )
									delete component.create;
								else
									component.create = create;
							},
						},
						section	: section,
						name		: component.name,
						seed		: schema.label || component.name,
						redraw	: fill,
					} ) );

				if( extra.children.length === 0 )
					extra.remove();
				else
					body.appendChild( extra );
			};

			// A line of free text takes the fields of the element, where there is one, written between double brackets
			if( schema.fields === true && fields !== null )
				body.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/column-text'), builder._valueChoices( fields, false ).slice( 0, 12 ).map( function( item ) { return '[['+ item.value+ ']]' } ).join(', ') ) ) );

			body.appendChild( builder._sourceRow( {
				kind		: kind,
				fixed		: kind !== 'image',
				create	: true,
				fields	: fields,
				value		: component.source,
				text		: component.text,
				section	: section,
				name		: component.name,
				seed		: schema.label || component.name,
				caption	: Nino.content.getText('/_admin/builder/source/current'),
				red			: builder._red( doc.model, builder._registry ).find( function( entry ) { return builder._same( entry.path, path ) } ),
				onPick	: function( source, create ) {

					// The texts typed for the key the component had are that key's, not the new one's
					if( source !== component.source )
						delete component.values;

					component.source = source;
					component.text = null;

					if( create === null )
						delete component.create;
					else {
						component.create = create;
						builder._placeholders( builder._registry, component );
					}

					builder._changed();
					fill();
				},
				onFixed	: function( text ) {
					component.text = text;
					component.source = '';
					delete component.create;
					delete component.values;
					builder._changed();
					fill();
				},
			} ) );

			fill();
		},


		/**
		 *	The attributes of a component or a stack, a field each in the order of the schema, in one column. A field is marked with the attribute it writes
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		declared					name => the schema's entry
		 *	@param		{Object}		attributes				Where the values are kept
		 *	@param		{Object}		context						{ fields, section, node, path, around, redraw, roles }: the fields of the stack's type, where there is a stack, and the section
		 *																the attributes are in; the component they belong to and its path, where they are a component's; the ground the
		 *																column paints (see _surface()), for the grounds an attribute names; what draws the form again, for a
		 *																picture that is changed - its focus and its dimming follow it; and what the attributes are to a picture (see _roles()), where
		 *																the form shows only some of the attributes of the schema - by default the roles of the attributes it is given; and
		 *																after( name, value ), called once a field has written its value, for a form that draws something else for the choice
		 *
		 *	@return		void
		 */
		_attributeFields : function( pane, declared, attributes, context ) {

			const builder = Nino.admin.builder;
			const here = Object.assign( { roles : builder._roles( declared ) }, context );

			Object.keys( declared ).forEach( function( name ) { pane.appendChild( builder._keyed( builder._attributeField( name, declared[name], attributes, here ), name ) ) } );
		},

		/**
		 *	Whether a value is a source: a key of the project or of the system, a page of the
		 *	site with a sub-path, or a field of the type a loop runs over - and not an address
		 *	typed in
		 *
		 *	@param		{string}		value
		 *	@param		{Object|null}	fields						The fields of the loop's type, null where there is no loop
		 *
		 *	@return		{boolean}
		 */
		_isSource : function( value, fields ) {

			const builder = Nino.admin.builder;

			return builder.KEY.test( value ) === true || value.indexOf('/_nino/') === 0 || builder.ADDRESS.test( value ) === true || value === '.id' || value === '.uri' || value === '.slug'
				|| ( fields !== null && fields !== undefined && Object.prototype.hasOwnProperty.call( fields, value ) === true );
		},

		/**
		 *	The words the schema gives one option of an attribute: the label that a method of the feature answered for it
		 *	(optionLabels, value => label, in the language of the workbench), '' where the schema has none - the list of
		 *	options is then written in the manifest, and its values are what the field shows, as before. The Builder knows
		 *	no attribute by name for it: whatever the schema labels is shown by its label
		 *
		 *	@param		{Object}		declared					The schema's entry of the attribute
		 *	@param		{string}		option						One of its options
		 *
		 *	@return		{string}
		 */
		_optionWords : function( declared, option ) {

			const labels = declared.optionLabels;

			if( labels === undefined || labels === null || typeof labels !== 'object' || Object.prototype.hasOwnProperty.call( labels, option ) === false )
				return '';

			return typeof labels[option] === 'string' ? labels[option] : '';
		},

		/**
		 *	The value a call holds that is not among the options a method of the feature answered - a form that was deleted since, say - as the one more
		 *	entry of the choice, marked as not offered, so that it stays what the call has until the editor picks another. A list that is written in the
		 *	manifest has no such value (the Reader refuses it), and the empty value is the first of the options or none
		 *
		 *	@param		{Object}		declared					The schema's entry of the attribute
		 *	@param		{string}		value							What the call has
		 *
		 *	@return		{Array}										[ { value, label } ] - none, or the one entry
		 */
		_notOffered : function( declared, value ) {

			if( value === '' || declared.optionLabels === undefined || declared.optionLabels === null || ( declared.options || [] ).indexOf( value ) !== -1 )
				return [];

			return [ { value : value, label : Nino.adminUi.format( Nino.content.getText('/_admin/builder/label/not-offered'), value ) } ];
		},

		/**
		 *	One attribute of a component or a stack, by the type its schema gives
		 *	it. Every value is a string in the model, as a call writes it. A key, an image
		 *	slot or a link is a source, and is changed in the dialog of the sources.
		 *	What a select is, is known by its options and never by the name of the component
		 *	it is in: a select over the grounds a column may paint is the colour select of a
		 *	column, with the patch of the colour beside it; a select over none and the nine
		 *	places is the grid of the focus of a picture, which has no place to give while the
		 *	picture before it is not there; and a switch right after a picture is its dimming,
		 *	which waits for the picture as well. The Builder has words for the values of a few
		 *	attributes by their names (see ATTRIBUTE_WORDS); a select of any other has its
		 *	values as they are. The picture of a component has the upload that a column's
		 *	background has (see _pictureAttribute())
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry: type, label, hint, options, optionLabels, min, max
		 *	@param		{Object}		attributes				Where the value is kept
		 *	@param		{Object}		context						As _attributeFields() has it, with the roles of its attributes (see _roles())
		 *
		 *	@return		{Element}
		 */
		_attributeField : function( name, declared, attributes, context ) {

			const builder = Nino.admin.builder;
			const label = declared.label || name;
			const hint = declared.hint || '';
			const write = function( value ) {

				attributes[name] = value;
				builder._changed();

				// A form that has to draw something else for the choice says so
				if( typeof context.after === 'function' )
					context.after( name, value );
			};
			const value = attributes[name] === undefined || attributes[name] === null ? '' : String( attributes[name] );
			const role = ( context.roles ?? {} )[name];
			// A focus or a dimming is of a picture: the one before it in the schema, or - for a focus that has none - the component's own
			const pictured = role === undefined || role.picture === '' || String( attributes[role.picture] ?? '' ) !== '';

			// A variant is a select of words whose value is a class: a row of buttons, unless the schema says another way to draw it
			if( declared.type === 'select' || declared.type === 'variant' ) {

				if( declared.type === 'select' && builder._isGround( declared ) === true )
					return builder._colorField( label, hint, value, write, context.around ?? '' );

				if( declared.type === 'select' && builder._isFocus( declared ) === true )
					return builder._focusField( /^[1-9]$/.test( value ) === true ? parseInt( value, 10 ) : null, pictured, function( focus ) { write( focus === null ? '' : String( focus ) ) }, label );

				const group = declared.type === 'variant' ? 'variant' : ( Object.prototype.hasOwnProperty.call( builder.ATTRIBUTE_WORDS, name ) === true ? builder.ATTRIBUTE_WORDS[name] : '' );
				const input = declared.input ?? ( declared.type === 'variant' ? 'segment' : 'select' );

				// The words of the buttons: the panel's where it has them, the value humanised where it has not - an alignment drawn as icons has the icons, and the words for a title
				if( input === 'segment' || input === 'icons' ) {

					const icons = input === 'icons' ? builder._alignIcons( declared.options ) : null;

					return builder._segmentField( label, ( declared.options || [] ).map( function( option ) {
						return { value : option, label : builder._optionWords( declared, option ) || builder._wordOf( group === '' ? 'attribute-'+ name : group, option ), icon : icons === null ? undefined : icons[option] };
					} ).concat( builder._notOffered( declared, value ) ), value, write, hint );
				}

				return builder._selectField( label, hint, ( declared.options || [] ).map( function( option ) {
					return { value : option, label : builder._optionWords( declared, option ) || ( group !== '' ? builder._optionLabel( group, option ) : ( option === '' ? Nino.content.getText('/_admin/builder/label/none') : option ) ) };
				} ).concat( builder._notOffered( declared, value ) ), value, write );
			}

			if( declared.type === 'bool' ) {

				const field = declared.input === 'checkbox'
					? builder._checkField( label, hint, value === '1', function( checked ) { write( checked === true ? '1' : '0' ) } )
					: builder._switchField( label, hint, value === '1', function( checked ) { write( checked === true ? '1' : '0' ) } );

				// There is a picture to dim, or there is none
				if( role !== undefined && role.role === 'dim' )
					builder._one( field, 'input' ).disabled = pictured === false;

				return field;
			}

			if( declared.type === 'int' )
				return builder._numberField( label, hint, value === '' ? null : parseInt( value, 10 ), declared.min, declared.max, function( number ) { write( number === null ? '' : String( number ) ) } );

			if( declared.type === 'lines' )
				return builder._textField( label, hint, value, write, { multiline : true } );

			if( declared.type === 'image' && context.node !== undefined )
				return builder._pictureAttribute( name, declared, attributes, context );

			if( declared.type === 'key' || declared.type === 'href' || declared.type === 'image' ) {

				const fields = context.fields ?? null;
				// An address typed in is a fixed value, and shown as one
				const fixed = declared.type === 'href' && value !== '' && builder._isSource( value, fields ) === false;

				return builder._sourceRow( {
					kind		: declared.type === 'image' ? 'image' : ( declared.type === 'href' ? 'href' : 'text' ),
					fixed		: declared.type === 'href',
					create	: declared.type === 'href' && builder._keysRead === true,
					fields	: fields,
					value		: fixed === true ? '' : value,
					text		: fixed === true ? value : null,
					section	: context.section,
					name		: name,
					seed		: label,
					caption	: '',
					label		: label,
					onPick	: function( source ) { write( source ) },
					onFixed	: write,
				} );
			}

			return builder._textField( label, hint, value, write );
		},

		/**
		 *	An attribute of the type image of a component: the picture behind a card. Its source is a slot or - in a loop - an
		 *	image field of the element, as the source of a picture is; and it has what the picture behind a column has: the upload
		 *	button that stores a picture at once and makes the slot, and beside it the button that takes the picture away again - alone
		 *	in a row of its own where the picture is a field of the element, which is not stored here. The mark of a slot that is
		 *	made when the document is saved is kept on the component, under the name of the attribute (slots). It has no alt text: the
		 *	picture behind a content is decoration. Its focus and its dimming follow it in the schema, and the form is drawn again
		 *	when the picture changes. A picture that is gone leaves nothing to dim and no place to focus on, so the dimming is off and
		 *	the focus is none with it
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry
		 *	@param		{Object}		attributes				Where the value is kept
		 *	@param		{Object}		context						As _attributeField() has it, with the node whose attribute it is
		 *
		 *	@return		{Element}
		 */
		_pictureAttribute : function( name, declared, attributes, context ) {

			const builder = Nino.admin.builder;
			const node = context.node;
			const schema = ( builder._registry.components || {} )[node.name] || {};
			const label = declared.label || name;
			const value = String( attributes[name] ?? '' );
			const wrap = builder._el( 'div', 'builder-background' );
			// The button that takes the picture away: the same as the one of a background
			const remove = {
				label		: Nino.content.getText('/_admin/builder/source/remove-background'),
				disabled	: value === '',
				onClick	: function() {

					put( '', undefined );
					builder._changed();
					again();
				},
			};
			// What the picture is called where nothing else names it: the label of a slot it makes, and the name of it
			const words = ( schema.label || node.name )+ ' - '+ label;
			const slug = node.name+ '-'+ name;
			const again = function() {
				if( typeof context.redraw === 'function' )
					context.redraw();
			};
			// The picture and the mark of the slot that is made with the document go together
			const put = function( uri, create ) {

				const slots = node.slots ?? {};

				attributes[name] = uri;

				if( create === undefined || create === null )
					delete slots[name];
				else
					slots[name] = create;

				if( Object.keys( slots ).length === 0 )
					delete node.slots;
				else
					node.slots = slots;

				if( uri !== '' )
					return;

				Object.keys( context.roles ?? {} ).forEach( function( other ) {
					if( context.roles[other].role === 'dim' && context.roles[other].picture === name )
						attributes[other] = '0';
					if( context.roles[other].role === 'focus' && context.roles[other].picture === name )
						attributes[other] = '';
				} );
			};

			wrap.appendChild( builder._sourceRow( {
				kind		: 'image',
				fixed		: false,
				create	: true,
				fields	: context.fields ?? null,
				value		: value,
				text		: null,
				section	: context.section,
				name		: slug,
				seed		: words,
				caption	: Nino.content.getText('/_admin/builder/source/current-image'),
				label		: label,
				red			: builder._red( builder._doc.model, builder._registry ).find( function( entry ) { return builder._same( entry.path, context.path ?? [] ) && entry.source === value } ),
				onPick	: function( source, create ) {

					put( source, create );
					builder._changed();
					again();
				},
			} ) );

			// A field of the element is the picture of the element, which is not stored here
			if( value === '' || value.charAt( 0 ) === '/' )
				wrap.appendChild( builder._pictureField( {
					owner		: {
						get : function() { return { uri : String( attributes[name] ?? '' ), create : ( node.slots ?? {} )[name] } },
						put : put,
					},
					section	: context.section,
					name		: slug,
					seed		: context.section.id+ ': '+ words,
					alt			: false,
					redraw	: again,
					remove	: remove,
				} ) );
			else {

				const buttons = builder._el( 'div', 'builder-picture-buttons' );
				const away = builder._el( 'button', 'nino-admin-btn-secondary builder-picture-remove', remove.label );

				away.type = 'button';
				away.addEventListener( 'click', remove.onClick );
				buttons.appendChild( away );
				wrap.appendChild( buttons );
			}

			return wrap;
		},

		// ----------------------------------------------------------- The source

		/**
		 *	The source of a text or a picture in a form: what it is now, as text, and an icon
		 *	button beside it, which opens the dialog where it is changed (see _sourceDialog(),
		 *	whose options these are, besides the ones of the row). What the dialog chooses
		 *	is written by the caller's onPick and onFixed, and the row says it
		 *
		 *	@param		{Object}		options						Those of _sourceDialog(), and
		 *	@param		{string}		options.caption		What the row says before the value; '' for nothing
		 *	@param		{string}		[options.label]		The name of the field, over the row
		 *	@param		{Object}		[options.red]			What is wrong with the source where it stands
		 *
		 *	@return		{Element}
		 */
		_sourceRow : function( options ) {

			const builder = Nino.admin.builder;
			const row = builder._fragment('source');
			const red = builder._one( row, '.builder-source-red' );
			const name = builder._one( row, '.builder-source-name' );
			const caption = builder._one( row, '.builder-source-caption' );
			const current = builder._one( row, '.builder-source-current' );
			const id = 'builder-source-'+ ( ++builder._sourceRows );
			const show = function() {
				current.textContent = builder._sourceShown( options );
				red.hidden = options.red === undefined;
				red.textContent = options.red === undefined ? '' : Nino.content.getText( '/_admin/builder/tree/red-'+ options.red.why );
			};

			name.hidden = options.label === undefined;
			name.textContent = options.label ?? '';
			caption.hidden = options.caption === '';
			caption.textContent = options.caption;

			const choose = builder._iconButton( 'source', Nino.content.getText('/_admin/builder/source/title'), function() {

				builder._sourceDialog( Object.assign( {}, options, {
					onPick	: function( source, create ) {
						options.value = source;
						options.text = null;
						options.red = undefined;
						options.onPick( source, create );
						show();
					},
					onFixed	: function( text ) {
						options.text = text;
						options.value = '';
						options.red = undefined;
						options.onFixed( text );
						show();
					},
				} ) );
			} );

			// A form with a few of these has a button "Source" for each: the one of a field is named by the field - "Link, Source" - by the name that is over the row, and its own
			if( options.label !== undefined ) {
				name.id = id+ '-name';
				choose.id = id+ '-button';
				choose.setAttribute( 'aria-labelledby', name.id+ ' '+ choose.id );
			}

			builder._one( row, '.builder-source-line' ).appendChild( choose );

			show();

			return row;
		},

		// How many rows of a source were made: the number in the ids that join a button to the name of its field
		_sourceRows : 0,

		/**
		 *	A source as it is said: its key, slot or field; the fixed value in quotes;
		 *	none where it is neither
		 *
		 *	@param		{Object}		options						{ value, text }
		 *
		 *	@return		{string}
		 */
		_sourceShown : function( options ) {

			if( typeof options.text === 'string' && options.value === '' )
				return '“'+ options.text+ '”';

			return options.value === '' ? Nino.content.getText('/_admin/builder/label/none') : options.value;
		},

		/**
		 *	The key that holds the address of a page of the site - one of the pages the list of keys told of: the fill of its uri, which is the address in the language the
		 *	page is read in
		 *
		 *	@param		{Object}		page							{ uri, httpUri, template, category, templateName }
		 *
		 *	@return		{string}
		 */
		_pageKey : function( page ) {
			return '/_nino/webpage'+ page.uri+ '/uri';
		},

		/**
		 *	The two parts of the address of a link to a page of the site: the key that holds the address of the page, and the sub-path after it, which is empty where the link
		 *	leads to the page alone. A source that is no such address has the whole of it for its key
		 *
		 *	@param		{string}		source
		 *
		 *	@return		{Object}									{ key, postfix }
		 */
		_pageParts : function( source ) {

			const found = Nino.admin.builder.ADDRESS.exec( source );

			return found === null ? { key : source, postfix : '' } : { key : found[1], postfix : found[2] };
		},

		/**
		 *	The address of a link to a page of the site, as the template holds it: the key that holds the address of the page - and, where the link goes on from the page, the
		 *	directory the site is served from, the fill of that key and the sub-path after it, which the kernel puts in before it reads the call
		 *
		 *	@param		{string}		key
		 *	@param		{string}		postfix						As _postfixOf() answers it
		 *
		 *	@return		{string}
		 */
		_pageAddress : function( key, postfix ) {
			// The fill of the directory is put together here: the kernel puts the fills into a script it serves, and one written out would be gone from it
			return postfix === '' ? key : '[['+ '/nino/dir'+ ']][['+ key+ ']]'+ postfix;
		},

		/**
		 *	The sub-path of a link as it is written, from what was typed: nothing for nothing, else with the slash in front that an address needs where it has none - a query
		 *	or an anchor is the sub-path as it is. Null for one that holds a space, a quote, a backslash or a bracket that is no fill, and for a fill the loop does not have:
		 *	one of the element of a loop is its .slug, its .uri, its .id or a field of the type it was offered (see _fillsOf()), and where there is no loop none is
		 *
		 *	@param		{string}				typed
		 *	@param		{Object|null}		fields					The fields of the type the loop runs over, null where there is no loop
		 *
		 *	@return		{string|null}
		 */
		_postfixOf : function( typed, fields ) {

			const builder = Nino.admin.builder;
			const text = typed.trim();
			const said = text === '' || [ '/', '?', '#' ].indexOf( text.charAt(0) ) !== -1 ? text : '/'+ text;
			const offered = fields === null || fields === undefined ? [] : builder._fillsOf( fields );

			if( builder.SUBPATH.test( said ) === false )
				return null;

			return ( said.match( /\[\[[^\[\]]*\]\]/g ) ?? [] ).every( function( fill ) { return offered.indexOf( fill ) !== -1 } ) === true ? said : null;
		},

		/**
		 *	The fills of the element of a loop that an address may hold, as the dialog of a link lists them: the slug of the element - the last segment of its uri, the name a link to
		 *	a page of the element needs - its uri and its number in the loop, then the fields of the type that are a line of text or a number - not the ones that hold markup
		 *
		 *	@param		{Object}		fields						The fields of the type the loop runs over
		 *
		 *	@return		{Array}										[ '[[.slug]]', ... ]
		 */
		_fillsOf : function( fields ) {

			return [ '.slug', '.uri', '.id' ].concat( Object.keys( fields ).filter( function( name ) {
				return [ 'string', 'integer', 'double' ].indexOf( fields[name].type ) !== -1 && fields[name].html !== true && /^[A-Za-z0-9_-]+$/.test( name ) === true;
			} ) ).map( function( name ) { return '[['+ name+ ']]' } );
		},

		/**
		 *	The page of the site a source is the address of, null for a source that is the address of none - the page alone or with a sub-path
		 *
		 *	@param		{string}		source
		 *
		 *	@return		{Object|null}
		 */
		_pageOf : function( source ) {

			const builder = Nino.admin.builder;
			const key = builder._pageParts( source ).key;

			return builder._pages.find( function( page ) { return builder._pageKey( page ) === key } ) ?? null;
		},

		/**
		 *	A page as it is said where one is chosen: its address and the name of the template it shows
		 *
		 *	@param		{Object}		page
		 *
		 *	@return		{string}
		 */
		_pageSaid : function( page ) {

			const name = typeof page.templateName === 'string' && page.templateName !== '' ? page.templateName : ( typeof page.template === 'string' ? page.template : '' );
			const address = typeof page.httpUri === 'string' && page.httpUri !== '' ? page.httpUri : page.uri;

			return name === '' ? address : address+ ' - '+ name;
		},

		/**
		 *	The dialog of a source, over the dialog it was opened from: where the value
		 *	of a text or a picture is chosen - from the ones there are, made new, or typed
		 *	in. It answers by calling onPick or onFixed with the choice and closing; Escape
		 *	closes it with no answer. Its options are a plain object, and all it needs to
		 *	know of the form that opened it: the interface a dialog of the workbench can take
		 *	over, for every panel that chooses a key or a slot
		 *
		 *	@param		{Object}		options
		 *	@param		{string}		options.kind				text, image or href: what the source is - a key of a text, a slot of a picture, a link
		 *	@param		{boolean}		options.fixed				Whether a value typed in may be the source (Static)
		 *	@param		{boolean}		options.create			Whether a key or a slot may be made new here (Create new)
		 *	@param		{Object|null}	options.fields		The fields of the type a loop runs over, null where there is no loop
		 *	@param		{string}		options.value				The source as it is
		 *	@param		{string|null}	options.text			The fixed value as it is
		 *	@param		{Object}		options.section			The section whose keys a new one is named among
		 *	@param		{string}		options.name				What a new key or slot is called: the kind of the component, background for the picture of a section
		 *	@param		{string}		options.seed				The label of a new slot - a new key has no text to start with
		 *	@param		{Function}	options.onPick			Called with ( source, create ): create is null, or what the key (nothing but the mark) or the slot is made with
		 *	@param		{Function}	[options.onFixed]		Called with ( text )
		 *
		 *	@return		void
		 */
		_sourceDialog : function( options ) {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-picker');
			const strip = dc.getElementById('builder-picker-tabs');
			const content = dc.getElementById('builder-picker-content');
			const done = builder._closePicker;
			const tabs = [ { id : 'pick', label : Nino.content.getText('/_admin/builder/source/tab-pick'), build : function( pane ) { builder._pickPane( pane, options, done ) } } ];
			const pages = options.kind === 'href' && builder._pages.length > 0;

			// A link may lead to a page of the site, which is chosen by its address and the template it shows and not by the key that holds the address
			if( pages === true )
				tabs.push( { id : 'internal', label : Nino.content.getText('/_admin/builder/source/tab-internal'), build : function( pane ) { builder._internalPane( pane, options, done ) } } );

			if( options.create === true )
				tabs.push( { id : 'new', label : Nino.content.getText('/_admin/builder/source/tab-new'), build : function( pane ) {

					// The key of a link holds the address, which is typed with it: it is made at once, as the slot of an upload is, and not with the document
					if( options.kind === 'href' ) {

						builder._newName( pane, options, 'key', function( key, size, text, answer ) {

							builder._panelCall( 'keys/create', { key : key, global : true, format : 'plain', value : text }, function( status, response ) {

								if( status !== 200 )
									return answer( builder._errorText( status, response, '/_admin/builder/error/key' ) );

								// The keys of the project have it now, and the list of the dialog the next time it is open
								builder._keysAsked = false;
								builder._loadKeys();
								options.onPick( key, null );
								done();
							} );

							return true;
						}, Nino.content.getText('/_admin/builder/source/address') );

						return;
					}

					const what = options.kind === 'image' ? 'slot' : 'key';

					builder._newName( pane, options, what, function( uri, size ) {
						options.onPick( uri, what === 'slot' ? { label : options.seed, width : size.width, height : size.height } : {} );
						done();
					} );
				} } );

			if( options.fixed === true )
				tabs.push( { id : 'fixed', label : Nino.content.getText('/_admin/builder/source/tab-fixed'), build : function( pane ) { builder._fixedPane( pane, options, done ) } } );

			strip.innerHTML = '';
			content.innerHTML = '';
			builder._tabs( strip, content, tabs, typeof options.text === 'string' ? 'fixed' : ( pages === true && builder._pageOf( options.value ) !== null ? 'internal' : 'pick' ), 'builder-picker' );

			if( typeof dialog.showModal === 'function' )
				dialog.showModal();
			else
				dialog.setAttribute( 'open', '' );
		},

		/**
		 *	Close the dialog of a source, if it is open
		 *
		 *	@return		void
		 */
		_closePicker : function() {

			const dialog = dc.getElementById('builder-picker');

			if( dialog !== null && dialog.open === true && typeof dialog.close === 'function' )
				dialog.close();
		},

		/**
		 *	A list to choose from, with a box that narrows it
		 *
		 *	@param		{Element}		mount
		 *	@param		{Array}			items						[ { value, label, sub } ]
		 *	@param		{string}		current					The value that is chosen now
		 *	@param		{string}		placeholder
		 *	@param		{Function}	onPick					( value )
		 *	@param		{string}		[query]					What the box starts with
		 *
		 *	@return		void
		 */
		_picker : function( mount, items, current, placeholder, onPick, query ) {

			const builder = Nino.admin.builder;
			const search = builder._el( 'input', 'nino-admin-input' );
			const list = builder._el( 'div', 'builder-picker-list' );
			const draw = function() {

				const needle = search.value.trim().toLowerCase();
				const hits = items.filter( function( item ) { return needle === '' || ( item.value+ ' '+ item.label+ ' '+ ( item.sub ?? '' ) ).toLowerCase().indexOf( needle ) !== -1 } );

				list.innerHTML = '';

				hits.slice( 0, 40 ).forEach( function( item ) {

					const button = builder._el( 'button', item.value === current ? 'builder-pick-item is-current' : 'builder-pick-item' );
					const name = builder._el( 'span', 'builder-pick-name', item.label );

					button.type = 'button';

					// The picture a slot has, small: it is the project's own, from the Images panel's url
					if( typeof item.image === 'string' && item.image !== '' ) {

						const thumb = builder._el( 'img', 'builder-pick-thumb' );

						thumb.src = item.image;
						thumb.alt = '';
						thumb.loading = 'lazy';
						button.appendChild( thumb );
					}

					button.appendChild( name );

					if( item.sub !== undefined && item.sub !== '' )
						button.appendChild( builder._el( 'span', 'builder-pick-sub', item.sub ) );

					button.addEventListener( 'click', function() { current = item.value; onPick( item.value ); draw() } );
					list.appendChild( button );
				} );

				if( hits.length === 0 )
					list.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/builder/source/no-hits') ) );
				else if( hits.length > 40 )
					list.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.adminUi.format( Nino.content.getText('/_admin/builder/source/more'), hits.length - 40 ) ) );
			};

			search.type = 'search';
			search.placeholder = placeholder;
			search.setAttribute( 'aria-label', placeholder );
			search.value = query ?? '';
			search.addEventListener( 'input', draw );

			mount.appendChild( search );
			mount.appendChild( list );
			draw();
		},

		/**
		 *	The first tab of the dialog of a source: what it is now, in a loop the fields of
		 *	the element that may stand for it, and the keys of the project or the slots of
		 *	its pictures, found by what they are called or say - with those of the section
		 *	first. A choice is the answer: it closes the dialog
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options						See _sourceDialog()
		 *	@param		{Function}	done							Closes the dialog
		 *
		 *	@return		void
		 */
		_pickPane : function( pane, options, done ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const image = options.kind === 'image';
			const own = builder._keyUri( doc.file, options.section.id, '' );
			const pick = function( source ) {
				options.onPick( source, null );
				done();
			};
			const now = builder._el( 'p', 'builder-source-now' );

			now.appendChild( builder._el( 'span', '', Nino.content.getText('/_admin/builder/source/current') ) );
			now.appendChild( builder._el( 'code', 'builder-source-current', builder._sourceShown( options ) ) );
			pane.appendChild( now );

			if( options.fields !== null && options.fields !== undefined ) {

				const types = image === true ? [ 'image' ] : ( options.kind === 'text' ? [ 'string', 'integer', 'double', 'date', 'datetime' ] : [ 'string' ] );
				const names = Object.keys( options.fields ).filter( function( name ) { return types.indexOf( options.fields[name].type ) !== -1 } );
				const items = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ]
					.concat( names.map( function( name ) { return { value : name, label : name } } ) )
					.concat( image === true ? [] : [ { value : '.id', label : '.id' }, { value : '.uri', label : '.uri' }, { value : '.slug', label : '.slug' } ].filter( function( item ) { return options.kind === 'href' || item.value !== '.uri' } ) );

				pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/field'), Nino.content.getText('/_admin/builder/hint/field'), items, options.value.charAt(0) === '/' || options.value.charAt(0) === '[' ? '' : options.value, pick ) );
			}

			if( image === true ) {

				pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/source/slot') ) );

				builder._picker( pane, ( builder._registry.slots || [] ).map( function( slot ) {
					return { value : slot.uri, label : slot.uri, sub : slot.label+ ( slot.hasImage === true ? '' : ' – '+ Nino.content.getText('/_admin/builder/source/empty-slot') ), image : slot.url ?? '' };
				} ), options.value, Nino.content.getText('/_admin/builder/source/search-slot'), pick, own );

				return;
			}

			const keys = ( builder._keys || [] ).map( function( entry ) {
				const value = entry.global === true ? entry.values['*'] : Object.values( entry.values )[0];
				return { value : entry.key, label : entry.key, sub : typeof value === 'string' ? value.replace( /<[^>]*>/g, '' ).slice( 0, 60 ) : '' };
			} );

			// The addresses of the pages of the site stand beside the texts a link may be, in front of them: a link leads to a page more often than to a text
			const pages = options.kind === 'href' ? builder._pages.map( function( page ) {
				return { value : builder._pageKey( page ), label : builder._pageKey( page ), sub : builder._pageSaid( page ) };
			} ) : [];

			pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/source/key') ) );

			if( keys.length > 0 || pages.length > 0 )
				builder._picker( pane, pages.concat( keys ), options.value, Nino.content.getText('/_admin/builder/source/search'), pick, pages.length === 0 && keys.some( function( key ) { return key.value.indexOf( own ) === 0 } ) === true ? own : '' );
			else
				pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/source/typed'), builder._keysAnswered === true ? Nino.content.getText('/_admin/builder/hint/typed') : '', options.value.charAt(0) === '/' ? options.value : '', function() {}, {
					onCommit : function( value ) { if( builder.KEY.test( value.trim() ) === true || value.trim().indexOf('/_nino/') === 0 ) pick( value.trim() ) },
				} ) );
		},

		/**
		 *	The tab of a link that leads to a page of the site: a choice among the pages by their address and the template they show, and the sub-path after the address of the
		 *	page - the fields of the element in a loop, which are listed under the field and put in by a click. The source is the key that holds the address of the page, so the
		 *	link follows the page into the language the visitor reads, and with a sub-path the fill of that key and the sub-path after it. A page chosen with no sub-path typed is
		 *	the answer, and closes the dialog; with one the button, or Enter in the field, is the answer
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options						See _sourceDialog()
		 *	@param		{Function}	done							Closes the dialog
		 *
		 *	@return		void
		 */
		_internalPane : function( pane, options, done ) {

			const builder = Nino.admin.builder;
			const words = function( key ) { return Nino.content.getText( '/_admin/builder/'+ key ) };
			const now = builder._pageOf( options.value );
			const items = [ { value : '', label : words('label/none') } ].concat( builder._pages.map( function( page ) {
				return { value : builder._pageKey( page ), label : builder._pageSaid( page ) };
			} ) );
			const use = builder._el( 'button', 'nino-admin-btn-secondary', words('source/use') );
			const message = builder._el( 'p', 'nino-admin-error builder-postfix-message' );
			let page = now === null ? '' : builder._pageKey( now );
			let typed = now === null ? '' : builder._pageParts( options.value ).postfix;
			const check = function() {

				const bad = builder._postfixOf( typed, options.fields ) === null;

				message.textContent = bad === true ? words('source/postfix-invalid') : '';
				field.control.setAttribute( 'aria-invalid', bad === true ? 'true' : 'false' );
				use.disabled = page === '' || bad === true;
			};
			const take = function() {

				const postfix = builder._postfixOf( typed, options.fields );

				if( page === '' || postfix === null )
					return;

				options.onPick( builder._pageAddress( page, postfix ), null );
				done();
			};

			use.type = 'button';
			use.addEventListener( 'click', take );
			// What is wrong with the sub-path is said where a screen reader is told of it, as the refusal of a new key is
			message.id = 'builder-postfix-message-'+ ( ++builder._sourceRows );
			message.setAttribute( 'aria-live', 'polite' );

			pane.appendChild( builder._selectField( words('source/page'), words('hint/page'), items, page, function( value ) {

				page = value;

				// None is no choice; a page alone is the answer, and a page with a sub-path waits for the button
				if( value !== '' && typed.trim() === '' )
					take();
				else
					check();
			} ) );

			const field = builder._textField( words('source/postfix'), words('hint/postfix'), typed, function( value ) {
				typed = value;
				check();
			}, { placeholder : '/' } );

			// The dialog closes on the key, and the focus goes back to the button that opened it: the key press is not to go on to it
			field.control.addEventListener( 'keydown', function( ev ) {

				if( ev.key !== 'Enter' )
					return;

				ev.preventDefault();
				take();
			} );

			field.control.setAttribute( 'aria-describedby', message.id );
			pane.appendChild( field );
			pane.appendChild( message );

			// In a loop the fields of the element stand for what each cell leads to; a click puts one in where the cursor is
			if( options.fields !== null && options.fields !== undefined ) {

				const list = builder._el( 'p', 'nino-admin-hint builder-postfix-fields' );

				list.appendChild( builder._el( 'span', '', words('source/postfix-fields') ) );

				builder._fillsOf( options.fields ).forEach( function( fill ) {

					const token = builder._el( 'button', 'builder-postfix-token', fill );

					token.type = 'button';
					token.addEventListener( 'click', function() {

						const control = field.control;
						const empty = control.value.trim() === '';
						const value = empty === true ? '/' : control.value;
						const from = typeof control.selectionStart === 'number' && empty === false ? control.selectionStart : value.length;
						const to = typeof control.selectionEnd === 'number' && empty === false ? control.selectionEnd : value.length;

						control.value = value.slice( 0, from )+ fill+ value.slice( to );
						typed = control.value;
						control.focus();

						// The caret goes behind the fill that was put in, where the next character is typed
						if( typeof control.setSelectionRange === 'function' )
							control.setSelectionRange( from + fill.length, from + fill.length );

						check();
					} );
					list.appendChild( token );
				} );

				pane.appendChild( list );
			}

			pane.appendChild( use );
			check();
		},

		/**
		 *	The tab of a source that is typed in: a value that stands in the template, which
		 *	the editors cannot change. Using it is the answer
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options						See _sourceDialog()
		 *	@param		{Function}	done							Closes the dialog
		 *
		 *	@return		void
		 */
		_fixedPane : function( pane, options, done ) {

			const builder = Nino.admin.builder;
			const use = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText('/_admin/builder/source/use') );
			const field = builder._textField( Nino.content.getText('/_admin/builder/source/fixed'), Nino.content.getText('/_admin/builder/hint/fixed'), options.text ?? '', function() {} );

			use.type = 'button';
			use.addEventListener( 'click', function() {
				options.onFixed( field.control.value );
				done();
			} );
			// The dialog closes on the key, and the focus goes back to the button that opened it: the key press is not to go on to it
			field.control.addEventListener( 'keydown', function( ev ) {

				if( ev.key !== 'Enter' )
					return;

				ev.preventDefault();
				use.click();
			} );

			pane.appendChild( field );
			pane.appendChild( use );
		},

		/**
		 *	The part of the source field that makes a new key or slot: a name, which
		 *	starts as the kind of the component and is numbered where the section has
		 *	it, the key or slot it makes of it and whether that is one it can have
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options
		 *	@param		{string}		what							key or slot
		 *	@param		{Function}	use								( uri, { width, height }, address, answer ) - one that has to ask the server first answers true and tells what went wrong, or nothing, to
		 *																	answer() when it has been told
		 *	@param		{string}		[address]					The label of a field for the text the new key is made with: the key of a link holds its address
		 *
		 *	@return		void
		 */
		_newName : function( pane, options, what, use, address ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const box = builder._fragment( 'newkey' );
			const size = Object.assign( {}, builder.SLOT_SIZE );
			const name = builder._one( box, '.builder-new-name' );
			const uri = builder._one( box, '.builder-new-uri' );
			const message = builder._one( box, '.builder-new-message' );
			const go = builder._one( box, '.builder-new-use' );
			const typed = address === undefined ? null : builder._textField( address, '', '', function() { check() } );
			let asking = false;
			const check = function() {

				let problem = builder.SEGMENT.test( options.section.id ) === false ? 'grammar' : builder._nameProblem( options.section, doc.file, name.value.trim(), builder._keys );

				// A slot's segments each start with a letter: the section id and the name are told apart, the one to rename is the one that starts with a digit
				if( problem === '' && what === 'slot' )
					problem = /^[a-z]/.test( options.section.id ) === false ? 'slot' : ( /^[a-z]/.test( name.value.trim() ) === false ? 'slot-name' : '' );

				// A key that is made with a text is not made without
				if( problem === '' && typed !== null && typed.control.value.trim() === '' )
					problem = 'address';

				uri.textContent = builder._keyUri( doc.file, options.section.id, name.value.trim() );
				message.textContent = problem === '' ? '' : Nino.content.getText( '/_admin/builder/source/name-'+ problem );
				go.disabled = problem !== '' || asking === true;
			};

			builder._one( box, '.builder-new-title' ).textContent = Nino.content.getText( what === 'key' ? '/_admin/builder/source/new-key' : '/_admin/builder/source/new-slot' );
			builder._one( box, '.builder-new-name-label' ).textContent = Nino.content.getText('/_admin/builder/source/name');
			go.textContent = Nino.content.getText('/_admin/builder/source/use');
			name.value = builder._keyName( options.section, doc.file, options.name, builder._keys );
			name.addEventListener( 'input', check );

			const sizes = builder._one( box, '.builder-new-size' );

			sizes.hidden = what !== 'slot';

			if( what === 'slot' )
				[ 'width', 'height' ].forEach( function( dimension ) {
					const input = builder._one( box, '.builder-new-'+ dimension );
					input.value = String( size[dimension] );
					input.addEventListener( 'input', function() { size[dimension] = Math.max( 1, parseInt( input.value, 10 ) || 1 ) } );
					builder._one( box, '.builder-new-'+ dimension+ '-label' ).textContent = Nino.content.getText( '/_admin/common/label/'+ dimension );
				} );

			go.addEventListener( 'click', function() {

				asking = true;

				// What is made at once is told when it is: the form is as it was until then, and says what went wrong
				const waits = use( uri.textContent, size, typed === null ? '' : typed.control.value.trim(), function( problem ) {
					asking = false;
					check();
					message.textContent = problem ?? '';
				} ) === true;

				if( waits === true ) {

					if( asking === true )
						check();

					return;
				}

				asking = false;
				name.value = builder._keyName( options.section, doc.file, options.name, builder._keys );
				check();
			} );

			if( typed !== null )
				box.insertBefore( typed, message );

			check();
			pane.appendChild( box );
		},

		/**
		 *	Whether a section has text on it: a component in one of its columns, or in a card in one, that says something - a title,
		 *	a text, a button: what the registry gives a text to - or a block of HTML that is not empty
		 *
		 *	@param		{Object}		section
		 *	@param		{Object}		registry
		 *
		 *	@return		{boolean}
		 */
		_carriesText : function( section, registry ) {

			const builder = Nino.admin.builder;
			const says = function( component ) {

				const kind = builder._sourceKind( registry, component.name );

				if( kind === 'text' )
					return true;

				if( kind === 'content' && builder._holds( registry, component.name ) === '' )
					return String( component.content ?? '' ).trim() !== '';

				return ( component.components || [] ).some( says );
			};

			return ( section.cols || [] ).some( function( col ) { return ( col.components || [] ).some( says ) } );
		},

		/**
		 *	The picture behind a section or a column: its slot, the picture it has with the buttons that store one and take it away again - side by side, in the row of the
		 *	question mark that says what the upload takes - and the alt text in the language the editor works in, and its focus. The slot is the source of the row, which is changed
		 *	in the dialog of the sources. A column that loses its picture loses the dimming with it, a section the parallax and the dimming. What else the picture decides - the
		 *	switches of a section, the offsets of a column - is the block's: it is told when the picture changes. The picture of a column that is a cell of a loop of sections is
		 *	chosen among the image fields of the loop's type instead (see below), with the same focus
		 *
		 *	@param		{Array}			path							A section, or a column
		 *	@param		{Function}	redraw						Draws the blocks that follow the picture again
		 *
		 *	@return		{Element}
		 */
		_backgroundField : function( path, redraw ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const column = path.length === 2;
			const holder = column === true ? section.cols[path[1]] : section;
			const has = holder.background !== null && holder.background !== undefined;
			const wrap = builder._el( 'div', 'builder-background' );
			// What it is called where nothing else names it: the background of the section, or of a column of it
			const words = column === true ? Nino.adminUi.format( Nino.content.getText('/_admin/builder/source/column-background'), path[1] + 1 ) : Nino.content.getText('/_admin/builder/source/background');

			wrap.dataset.key = 'background';

			// A column with a loop of its own has the picture outside it: one picture for every cell, which the form says
			if( column === true && holder.stack !== null && holder.stack !== undefined ) {

				const said = Nino.content.getText('/_admin/builder/source/background-image');
				const own = builder._hinted( builder._el( 'span', 'builder-label', said ), Nino.content.getText('/_admin/builder/hint/background-own-loop'), said );

				own.classList.add('builder-background-own-loop');
				wrap.appendChild( own );
			}

			// A column that is a cell of a loop of sections takes an image field of the element for its picture, instead of a slot: the control offers the image fields of the
			// type, and the focus. (A column with a loop of its own has its picture outside that loop, where there is no element; a column of a loop of sections has none.) A column
			// that has a slot already keeps the controls of the slot
			const cell = column === true && section.stack !== null && section.stack !== undefined ? builder._typeFields( section, holder ) : null;

			if( cell !== null && ( has === false || ( holder.background.field ?? '' ) !== '' ) ) {

				const current = has === true ? holder.background.field : '';
				const names = Object.keys( cell ).filter( function( name ) { return cell[name].type === 'image' } );
				// A field the type has not any more stays in the list, so that the form says what the column holds, and is marked red as the preview marks it
				const offered = current === '' || names.indexOf( current ) !== -1 ? names : names.concat( [ current ] );
				const red = builder._red( builder._doc.model, builder._registry ).find( function( entry ) { return builder._same( entry.path, path ) } );
				const items = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ].concat( offered.map( function( name ) { return { value : name, label : name } } ) );

				wrap.classList.add('builder-background-field');
				wrap.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/background-field'), Nino.content.getText('/_admin/builder/hint/background-field'), items, current, function( value ) {

					if( value === '' ) {

						holder.background = null;
						holder.dim = false;
					}
					else
						holder.background = { slot : '', field : value, focus : has === true ? holder.background.focus : null };

					builder._changed();
					// The focus is there for a picture, and so is the dimming: the blocks are drawn again
					redraw();
				} ) );

				if( red !== undefined ) {

					const note = builder._el( 'p', 'nino-admin-error builder-source-red', Nino.content.getText( '/_admin/builder/tree/red-'+ red.why ) );

					wrap.appendChild( note );
				}

				wrap.appendChild( builder._focusField( has === true ? holder.background.focus : null, has, function( focus ) {
					holder.background.focus = focus;
					builder._changed();
				} ) );

				return wrap;
			}

			wrap.appendChild( builder._sourceRow( {
				kind		: 'image',
				fixed		: false,
				create	: true,
				fields	: null,
				// Inside the group Background image the row is the picture: the heading says the rest
				label		: Nino.content.getText('/_admin/builder/source/picture'),
				// A field of the element where there is no loop for it to be the element's is shown, red, until a slot takes its place
				value		: has === true ? ( holder.background.slot || ( holder.background.field ?? '' ) ) : '',
				text		: null,
				section	: section,
				name		: column === true ? 'column-background' : 'background',
				seed		: words,
				red			: column === true ? builder._red( builder._doc.model, builder._registry ).find( function( entry ) { return builder._same( entry.path, path ) } ) : undefined,
				caption	: Nino.content.getText('/_admin/builder/source/current-image'),
				onPick	: function( source, create ) {

					// A slot where a field was: the field is gone, with its red mark
					holder.background = { slot : source, focus : holder.background ? holder.background.focus : null };

					if( create !== null )
						holder.background.create = create;

					builder._changed();
					// The focus and the button that takes the picture away are there for a picture
					redraw();
				},
			} ) );

			wrap.appendChild( builder._pictureField( {
				owner		: {
					get : function() { return holder.background ? { uri : holder.background.slot, create : holder.background.create } : { uri : '', create : undefined } },
					put : function( uri, create ) {

						holder.background = holder.background ?? { slot : uri, focus : null };
						holder.background.slot = uri;
						delete holder.background.field;

						if( create === undefined )
							delete holder.background.create;
						else
							holder.background.create = create;
					},
				},
				section	: section,
				name		: column === true ? 'column-background' : 'background',
				// A slot made by an upload has no form to name it: it is the background of this section, or of this column of it
				seed		: section.id+ ': '+ words,
				redraw	: redraw,
				remove	: {
					label		: Nino.content.getText('/_admin/builder/source/remove-background'),
					disabled	: has === false,
					onClick	: function() {

						holder.background = null;

						// There is nothing left to darken, and no picture to move slower than the page - and no picture to stand inside, where the section has no colour either
						if( column === true )
							holder.dim = false;
						else {
							section.settings.parallax = false;
							section.settings.dim = false;

							if( section.settings.color === '' )
								section.settings.inner = false;
						}

						builder._changed();
						redraw();
					},
				},
			} ) );

			wrap.appendChild( builder._focusField( has === true ? holder.background.focus : null, has, function( focus ) {
				holder.background.focus = focus;
				builder._changed();
			} ) );

			return wrap;
		},

		// ------------------------------------------ Languages, texts and pictures

		/**
		 *	The language the editor starts in: the native one of the site, else the first
		 *	it has
		 *
		 *	@param		{Object}		registry
		 *
		 *	@return		{string}									'' where the registry names none
		 */
		_nativeLocale : function( registry ) {

			const locales = ( registry.locales || [] ).map( function( entry ) { return entry.locale } );

			return locales.indexOf( registry.native ) === -1 ? ( locales[0] ?? '' ) : registry.native;
		},

		/**
		 *	What a language is called: the name the project gives it, else its code
		 *
		 *	@param		{string}		locale
		 *
		 *	@return		{string}
		 */
		_localeName : function( locale ) {

			const found = ( Nino.admin.builder._registry.locales || [] ).find( function( entry ) { return entry.locale === locale } );

			return found === undefined || found.name === '' ? locale : found.name;
		},

		/**
		 *	The choice of the language in the bar over the preview: an option for each
		 *	language of the site, the one the editor works in chosen. A site that has one
		 *	language has nothing to choose, and the select is out of sight
		 *
		 *	@return		void
		 */
		_drawLocales : function() {

			const builder = Nino.admin.builder;
			const select = dc.getElementById('builder-locale');
			const locales = builder._registry.locales || [];

			select.innerHTML = '';

			locales.forEach( function( entry ) {

				const option = builder._el( 'option', '', builder._localeName( entry.locale ) );

				option.value = entry.locale;
				select.appendChild( option );
			} );

			select.value = builder._locale;
			select.hidden = locales.length < 2;
		},

		/**
		 *	The editor works in another language: the preview shows its texts, and the
		 *	dialogs that are opened next show and write them
		 *
		 *	@param		{string}		locale
		 *
		 *	@return		void
		 */
		_setLocale : function( locale ) {

			const builder = Nino.admin.builder;

			builder._locale = locale;
			builder._renderPreview();
		},

		/**
		 *	The first line of a text as a placeholder says it: a break, or the end of a
		 *	paragraph or an item, ends the line, the tags are gone and the entities read,
		 *	white space is one space, and a line longer than max is cut and ends in an
		 *	ellipsis. It is text, to be set as text: nothing in it is markup
		 *
		 *	@param		{*}					value							What a key holds: plain text, or the html of the other formats
		 *	@param		{number}		max
		 *
		 *	@return		{string}									'' where the text has no line
		 */
		_firstLine : function( value, max ) {

			const entities = { amp : '&', lt : '<', gt : '>', quot : '"', apos : "'", nbsp : ' ' };
			const line = String( value ?? '' )
				.replace( /<\s*(?:br|\/p|\/li|\/div|\/h[1-6]|\/blockquote)\b[^>]*>/gi, '\n' )
				.replace( /<[^>]*>/g, '' )
				.replace( /&(#x?[0-9a-f]+|[a-z]+);/gi, function( all, name ) {

					if( name.charAt(0) === '#' ) {
						const code = name.charAt(1).toLowerCase() === 'x' ? parseInt( name.slice( 2 ), 16 ) : parseInt( name.slice( 1 ), 10 );
						return code > 0 && code < 0x110000 ? String.fromCodePoint( code ) : all;
					}

					return entities[name.toLowerCase()] ?? all;
				} )
				.split( /\r\n|\r|\n/ )
				.map( function( part ) { return part.replace( /\s+/g, ' ' ).trim() } )
				.find( function( part ) { return part !== '' } ) ?? '';
			const characters = Array.from( line );

			return characters.length > max ? characters.slice( 0, max ).join('').trimEnd()+ '…' : line;
		},

		/**
		 *	The key a source names, in the list of the keys of the project
		 *
		 *	@param		{Array|null}	keys							As text/keys answers them
		 *	@param		{string}		source
		 *
		 *	@return		{Object|null}
		 */
		_keyEntry : function( keys, source ) {
			return ( keys || [] ).find( function( entry ) { return entry.key === source } ) ?? null;
		},

		/**
		 *	The format a text key is made in when the document is saved: what its
		 *	component asks for - the format attribute, where the schema has one -, else plain
		 *
		 *	@param		{Object}		registry
		 *	@param		{Object}		component
		 *
		 *	@return		{string}									One of FORMATS
		 */
		_createFormat : function( registry, component ) {

			const builder = Nino.admin.builder;
			const declared = builder._declared( ( registry.components || {} )[component.name] || {} );
			const asked = Object.prototype.hasOwnProperty.call( declared, 'format' ) === true ? String( component.attributes.format ?? '' ) : '';

			return builder.FORMATS.indexOf( asked ) === -1 ? 'plain' : asked;
		},

		/**
		 *	The text of a component in a language, where it has one to show: the one typed
		 *	for it and not saved yet, else the one its key holds - and a key the save is going to
		 *	make holds none: it is made with what is typed for it. A component whose source is no
		 *	text key has none to show: a fixed text, the field of a loop, a picture, a key nobody knows
		 *
		 *	@param		{Object}		component
		 *	@param		{string}		locale
		 *	@param		{Array|null}	keys							The keys of the project, as text/keys answers them
		 *	@param		{Object}		registry
		 *
		 *	@return		{Object|null}							{ value, stored, staged, format, global, maxlength }: what is shown, what the key holds
		 *															('' for a key that is made at save), whether what is shown was typed, the format of the
		 *															key, whether it is the same in every language, its limit (0 for none known)
		 */
		_textOf : function( component, locale, keys, registry ) {

			const builder = Nino.admin.builder;

			// The title of a column is a key whatever line the column shows: its text is no fixed text of the title
			if( builder._sourceKind( registry, component.name ) !== 'text' || ( typeof component.text === 'string' && builder._columnar( ( registry.components || {} )[component.name] || {} ) === false ) || String( component.source ?? '' ).charAt(0) !== '/' )
				return null;

			const entry = builder._keyEntry( keys, component.source );
			const typed = component.values ?? {};
			let info;

			if( entry !== null ) {

				const held = entry.global === true ? entry.values['*'] : entry.values[locale];

				info = {
					stored		: typeof held === 'string' ? held : '',
					format		: builder.FORMATS.indexOf( entry.format ) === -1 ? ( entry.html === true ? 'inline' : 'plain' ) : entry.format,
					global		: entry.global === true,
					maxlength	: entry.maxlength ?? 0,
				};
			} else if( component.create !== null && typeof component.create === 'object' )
				info = { stored : '', format : builder._createFormat( registry, component ), global : false, maxlength : 0 };
			else
				return null;

			// A key every language shares has one text, whichever language it was typed in
			const given = info.global === true ? Object.values( typed )[0] : typed[locale];

			info.staged = typeof given === 'string';
			info.value = info.staged === true ? given : info.stored;

			return info;
		},

		/**
		 *	The text the page shows for a component in a language, which is what the preview says of it. A language that has no text of its own for the key - none, or one of nothing but
		 *	white space, which the site counts as none - is shown the text the site fills it with, in the order the site fills it: the text every language shares, else the native language's,
		 *	else the others' in the order the site lists them. A text typed and not saved counts for the language it was typed in as the one the key holds does. The field under the source
		 *	(see _valueField()) does not fall back: it is the text of the language, empty where it has none, and says what the component is for in its place
		 *
		 *	Two places where what the preview shows is not what the site shows, both with keys that only a file by hand makes: a key that has a text every language shares and texts of languages
		 *	beside it - the Text panel keeps one or the other - where the site gives a language that has none of its own the text of another, the native one first, ahead of the shared text, and
		 *	the preview the shared text first; and a language that has a text file and is not one of the site's, which the site asks last and the preview not at all
		 *
		 *	@param		{Object}		component
		 *	@param		{Object}		info							As _textOf() answers it for the language shown
		 *	@param		{Array|null}	keys							The keys of the project, as text/keys answers them
		 *	@param		{Object}		registry
		 *
		 *	@return		{string}									'' where no language has a text: the preview then shows the label of the component
		 */
		_shownText : function( component, info, keys, registry ) {

			// A key every language shares has the one text, and a language that has one has no use for another
			if( info.value.trim() !== '' || info.global === true )
				return info.value;

			const builder = Nino.admin.builder;
			const entry = builder._keyEntry( keys, component.source );
			const typed = component.values ?? {};
			const held = entry === null ? {} : entry.values;
			const textIn = function( code ) {

				const given = typed[code];
				const stored = held[code];

				return typeof given === 'string' ? given : ( typeof stored === 'string' ? stored : '' );
			};
			const chain = [ '*', builder._nativeLocale( registry ) ].concat( ( registry.locales || [] ).map( function( language ) { return language.locale } ) );
			const found = chain.find( function( code ) { return textIn( code ).trim() !== '' } );

			return found === undefined ? '' : textIn( found );
		},

		/**
		 *	Keep a text typed for the key of a component in its model, under the language
		 *	- and only where it is not what the key holds: typed back to that, it is none
		 *
		 *	@param		{Object}		component
		 *	@param		{Object}		info							As _textOf() answers
		 *	@param		{string}		locale
		 *	@param		{string}		text
		 *
		 *	@return		void
		 */
		_stageValue : function( component, info, locale, text ) {

			const values = component.values ?? {};

			// The one text of a key every language shares is kept once
			if( info.global === true )
				Object.keys( values ).forEach( function( key ) { delete values[key] } );

			if( text === info.stored )
				delete values[locale];
			else
				values[locale] = text;

			if( Object.keys( values ).length === 0 )
				delete component.values;
			else
				component.values = values;
		},

		/**
		 *	The text of the key of a component in the language the editor works in, as a field
		 *	under its source: a line of text for a key of the plain format - a few lines where
		 *	it holds some -, the editor of the workbench in the profile of its format for the
		 *	others. What is typed goes into the model, under the language; the key is written
		 *	when the document is saved. Where nothing is typed the field shows the label of the
		 *	component, in the language it is typed in, as its placeholder: it says what the field is
		 *	for and is no text - a page shows the text of the native language for an empty one
		 *
		 *	@param		{Object}		component
		 *	@param		{Object}		info							As _textOf() answers
		 *	@param		{Array}			editors						Where an editor made here is added, for the dialog to let go of it
		 *
		 *	@return		{Element}
		 */
		_valueField : function( component, info, editors ) {

			const builder = Nino.admin.builder;
			const locale = builder._locale;
			const wrap = builder._el( 'div', 'builder-value' );
			const placeholder = builder._labelIn( builder._registry, component.name, locale );
			const label = info.global === true ? Nino.content.getText('/_admin/builder/value/label-global') : Nino.adminUi.format( Nino.content.getText('/_admin/builder/value/label'), builder._localeName( locale ) );
			const keep = function( text ) {
				builder._stageValue( component, info, locale, text );
				builder._changed();
			};

			if( info.format === 'plain' ) {

				const field = builder._textField( label, Nino.content.getText('/_admin/builder/value/hint'), info.value, keep, { multiline : info.value.indexOf('\n') !== -1, placeholder : placeholder } );

				// A text is for reading: the spelling is checked, which a name or a class is not
				field.control.spellcheck = true;

				if( info.maxlength > 0 )
					field.control.maxLength = info.maxlength;

				wrap.appendChild( field );
			} else {

				const mount = builder._el( 'div', 'builder-content' );
				const head = builder._el( 'div', 'builder-label-row' );
				const name = builder._el( 'span', 'builder-label', label );
				const help = builder._help( Nino.content.getText('/_admin/builder/value/hint'), label );

				// An editor is no input that its label wraps: the group is named by the label and described by the sentence behind the mark, as the Text panel does it
				name.id = 'builder-value-label';
				wrap.setAttribute( 'role', 'group' );
				wrap.setAttribute( 'aria-labelledby', name.id );
				wrap.setAttribute( 'aria-describedby', help.tipId );
				head.appendChild( name );
				head.appendChild( help );
				wrap.appendChild( head );
				wrap.appendChild( mount );

				if( typeof Nino.admin.htmlEditor === 'object' ) {

					const editor = Nino.admin.htmlEditor.create( mount, info.value, info.maxlength > 0 ? info.maxlength : builder.CONTENT_MAX, 0, info.format );
					const box = mount.querySelector('.nino-admin-richtext-content');
					// An editor that holds no text says what it is for as an input does - the stylesheet draws the words while the box is blank
					const blank = function() {
						if( box !== null )
							box.classList.toggle( 'is-blank', editor.getValue() === '' );
					};
					// What the editor shows is its own normal form of the text, which is not the key's: the text is kept where that changes, not where it is shown
					let last = editor.getValue();
					const sync = function() {

						blank();

						const now = editor.getValue();

						if( now === last )
							return;

						last = now;
						keep( now );
					};

					if( box !== null )
						box.dataset.placeholder = placeholder;

					blank();

					// A key press and a paste are input; a button of the toolbar is a click, a shortcut is a key up
					[ 'input', 'click', 'keyup', 'focusout' ].forEach( function( type ) { mount.addEventListener( type, sync ) } );
					editors.push( editor );
				}
			}

			return wrap;
		},

		/**
		 *	The slot of the project a uri names
		 *
		 *	@param		{string}		uri
		 *
		 *	@return		{Object|null}
		 */
		_slotOf : function( uri ) {
			return ( Nino.admin.builder._registry.slots || [] ).find( function( slot ) { return slot.uri === uri } ) ?? null;
		},

		/**
		 *	The alt texts of a slot as a map - php sends an empty one as a list
		 *
		 *	@param		{Object|null}	slot
		 *
		 *	@return		{Object}									language => text
		 */
		_altOf : function( slot ) {
			return slot === null || slot.alt === undefined || slot.alt === null || Array.isArray( slot.alt ) === true ? {} : slot.alt;
		},

		/**
		 *	A slot for a source that names none: in the section, named as a new one is and
		 *	free in the project, made with the label and the size a new one is given
		 *
		 *	@param		{Object}		section
		 *	@param		{string}		name							The kind of the component, background for the picture of a section
		 *	@param		{string}		seed							The label the slot is made with
		 *
		 *	@return		{Object|null}							{ uri, create } - null where the id of the section or the name is no part of a slot: it starts with a digit
		 */
		_newSource : function( section, name, seed ) {

			const builder = Nino.admin.builder;
			const file = builder._doc.file;
			const names = builder._names( section, file, builder._keys );
			const word = builder._free( builder._segment( name ) || 'item', function( candidate ) {
				return names[candidate] === true || builder._slotOf( builder._keyUri( file, section.id, candidate ) ) !== null;
			} );

			if( /^[a-z]/.test( section.id ) === false || /^[a-z]/.test( word ) === false )
				return null;

			return { uri : builder._keyUri( file, section.id, word ), create : Object.assign( { label : seed }, builder.SLOT_SIZE ) };
		},

		// What the picture of a slot last said of itself, for the form that is drawn again to say it: { text, kind } - kind is '', 'warning' or 'error'
		_pictureNote : null,

		/**
		 *	The picture of a slot in a form: the picture it has at the left, and at its right the
		 *	buttons - the one that stores a picture, at once and not when the document is saved,
		 *	and where the caller has one the one that takes the picture away, the two alike, and
		 *	the question mark that says what the upload is and what the server takes of it - and
		 *	under the picture the alt text in the language the editor works in. The upload is the
		 *	button of the row: it opens the file field, which is hidden from the eye and not from
		 *	the keyboard. The first picture makes the slot where there is none yet; an alt text
		 *	goes to the slot at once where it is there, and into the mark of one that is made when
		 *	the document is saved, where it is not
		 *
		 *	@param		{Object}		options
		 *	@param		{Object}		options.owner			{ get(), put( uri, create ) }: the source in the model - { uri, create } - and
		 *															the slot it is changed to, create undefined for none to make
		 *	@param		{Object}		options.section		Whose keys a new slot is named among
		 *	@param		{string}		options.name			What a new slot is called: the kind of the component, background
		 *	@param		{string}		options.seed			The label a new slot is made with
		 *	@param		{Function}	options.redraw		Draws the form again, once the model has changed under it
		 *	@param		{boolean}		[options.alt]		false for a picture that has no alt text to give - the one behind a content, which is decoration
		 *	@param		{Object}		[options.remove]	{ label, disabled, onClick }: the button that takes the picture away, beside the upload; none for a picture that is not taken away here
		 *
		 *	@return		{Element}
		 */
		_pictureField : function( options ) {

			const builder = Nino.admin.builder;
			const locale = builder._locale;
			const source = options.owner.get();
			const slot = builder._slotOf( source.uri );
			const wrap = builder._el( 'div', 'builder-picture' );
			const view = builder._el( 'div', 'builder-picture-view' );
			const thumb = builder._el( 'img', 'builder-picture-thumb' );
			const controls = builder._el( 'div', 'builder-picture-controls' );
			const file = builder._el('input');
			const message = builder._el( 'p', 'nino-admin-hint builder-picture-message' );
			const note = builder._pictureNote;
			// kind: '' for a plain note, 'warning' for one that is no failure - the picture was stored -, 'error'
			const say = function( text, kind ) {
				message.textContent = text;
				message.classList.toggle( 'is-warning', kind === 'warning' );
				message.classList.toggle( 'is-error', kind === 'error' );
				message.hidden = text === '';
			};

			builder._pictureNote = null;

			thumb.alt = '';
			thumb.hidden = slot === null || typeof slot.url !== 'string';

			if( thumb.hidden === false )
				thumb.src = slot.url;

			// What the upload is and what the server takes of it is said behind the mark by its name
			const limits = Nino.adminUi.uploadHint();
			const buttons = builder._el( 'div', 'builder-picture-buttons' );
			const upload = Nino.content.getText('/_admin/builder/picture/upload');
			const choose = builder._el( 'button', 'nino-admin-btn-secondary builder-picture-upload', upload );

			// The file field is there for the keyboard and the screen reader, hidden from the eye (a field that is not displayed has neither): the button opens it
			file.type = 'file';
			file.accept = 'image/*';
			file.className = 'nino-admin-sr-only';
			file.setAttribute( 'aria-label', upload );
			file.addEventListener( 'change', function() {
				if( file.files.length > 0 )
					builder._upload( options, file.files[0], file, say );
			} );

			choose.type = 'button';
			choose.addEventListener( 'click', function() { file.click() } );
			buttons.appendChild( choose );
			// The field follows the button it belongs to, so that the keyboard passes Upload, the field - which shows its ring on the button - and then Remove and the question mark
			buttons.appendChild( file );

			if( options.remove !== undefined ) {

				const remove = builder._el( 'button', 'nino-admin-btn-secondary builder-picture-remove', options.remove.label );

				remove.type = 'button';
				remove.disabled = options.remove.disabled === true;
				remove.addEventListener( 'click', options.remove.onClick );
				buttons.appendChild( remove );
			}

			buttons.appendChild( builder._help( Nino.content.getText( slot === null ? '/_admin/builder/picture/hint-made' : '/_admin/builder/picture/hint' )+ ( limits === null ? '' : ' '+ limits.textContent ), upload ) );
			controls.appendChild( buttons );
			controls.appendChild( message );
			view.appendChild( thumb );
			view.appendChild( controls );
			wrap.appendChild( view );

			say( note === null ? '' : note.text, note === null ? '' : note.kind );

			if( options.alt === false )
				return wrap;

			// Where the slot is there the alt text is its own; where it is made later it is in the mark that makes it
			const mark = source.create ?? {};
			const alt = builder._textField(
				Nino.adminUi.format( Nino.content.getText('/_admin/builder/picture/alt'), builder._localeName( locale ) ),
				Nino.content.getText( source.uri === '' ? '/_admin/builder/picture/alt-hint-none' : ( slot === null ? '/_admin/builder/picture/alt-hint-made' : '/_admin/builder/picture/alt-hint' ) ),
				slot === null ? ( mark.alt || {} )[locale] ?? '' : builder._altOf( slot )[locale] ?? '',
				function( text ) {

					const now = options.owner.get();

					if( now.uri === '' || builder._slotOf( now.uri ) !== null )
						return;

					const made = now.create ?? Object.assign( { label : options.seed }, builder.SLOT_SIZE );
					const texts = Object.assign( {}, made.alt || {} );

					if( text === '' )
						delete texts[locale];
					else
						texts[locale] = text;

					if( Object.keys( texts ).length === 0 )
						delete made.alt;
					else
						made.alt = texts;

					options.owner.put( now.uri, made );
					builder._changed();
				},
				{ onCommit : function( text ) {

					const held = builder._slotOf( options.owner.get().uri );

					if( held === null || text === ( builder._altOf( held )[locale] ?? '' ) )
						return;

					const sent = {};

					sent[locale] = text;

					builder._panelCall( 'images/alt', { uri : held.uri, alt : sent }, function( status, response ) {

						if( status !== 200 || response === null )
							return say( builder._errorText( status, response, '/_admin/builder/error/alt' ), 'error' );

						held.alt = response.alt;
						say( Nino.content.getText('/_admin/builder/picture/alt-saved'), '' );
					} );
				} }
			);

			alt.control.disabled = source.uri === '';
			wrap.appendChild( alt );

			return wrap;
		},

		/**
		 *	Store the picture a person chose for a slot: the slot is made first where it is not
		 *	there - from the mark of its source, or as a new one where the source names none -,
		 *	then the Images panel's own upload, then the alt texts that were typed before the
		 *	slot was there. The source is the slot from the moment it is, and nothing is left to
		 *	make it. What came of it is said in the form, which is drawn again
		 *
		 *	@param		{Object}		options						As _pictureField() has them
		 *	@param		{File}			picture
		 *	@param		{Element}		field							The file input, off while the picture is on its way
		 *	@param		{Function}	say								( text, error ): where the form says it
		 *
		 *	@return		void
		 */
		_upload : function( options, picture, field, say ) {

			const builder = Nino.admin.builder;
			const opened = builder._dialogOptions;
			const source = options.owner.get();
			let uri = source.uri;
			let mark = source.create;
			let alt = {};
			const done = function( text, kind ) {

				field.disabled = false;

				// The form is drawn again if the dialog it is in is the one that is open; the model has changed either way
				if( builder._dialogOptions !== opened )
					return;

				builder._pictureNote = { text : text, kind : kind ?? '' };
				options.redraw();
			};

			if( uri === '' ) {

				const made = builder._newSource( options.section, options.name, options.seed );

				if( made === null )
					return say( Nino.content.getText('/_admin/builder/source/name-slot'), 'error' );

				uri = made.uri;
				mark = made.create;
			}

			field.disabled = true;
			say( Nino.content.getText('/_admin/builder/picture/busy'), '' );

			// What was typed before the slot was there goes to it once it is
			const finish = function( text, kind ) {

				if( Object.keys( alt ).length === 0 )
					return done( text, kind );

				builder._panelCall( 'images/alt', { uri : uri, alt : alt }, function( status, response ) {

					if( status !== 200 || response === null )
						return done( builder._errorText( status, response, '/_admin/builder/error/alt' ), 'error' );

					builder._touch('slots');
					builder._slotOf( uri ).alt = response.alt;
					done( text, kind );
				} );
			};
			const send = function() {

				builder._panelCall( 'images/upload', { uri : uri }, function( status, response ) {

					if( status !== 200 || response === null )
						return finish( builder._errorText( status, response, '/_admin/builder/error/upload' ), 'error' );

					const held = builder._slotOf( uri );

					builder._touch('slots');

					// The name a picture is stored under stays the same when it is replaced, so the browser is told that this is another
					held.hasImage = true;
					held.url = response.url+ ( response.url.indexOf('?') === -1 ? '?' : '&' )+ 't='+ Date.now();
					builder._renderPreview();

					finish( response.belowTarget === true && response.source ? Nino.adminUi.format( Nino.content.getText('/_admin/builder/picture/small'), response.source.width+ ' × '+ response.source.height+ ' px' )
						: Nino.content.getText('/_admin/builder/picture/stored'), response.belowTarget === true ? 'warning' : '' );
				}, { file : picture } );
			};

			Nino.adminUi.checkImage( picture, function( rejection ) {

				if( rejection !== null )
					return done( Nino.adminUi.api.errorText( 400, rejection ), 'error' );

				if( builder._slotOf( uri ) !== null )
					return send();

				const size = mark ?? Object.assign( { label : options.seed }, builder.SLOT_SIZE );

				builder._panelCall( 'slots/create', { uri : uri, label : size.label, width : size.width, height : size.height }, function( status, response ) {

					// A slot that is there already (409) was made by another request in the meantime, and is the one to use
					if( status !== 200 && status !== 409 )
						return done( builder._errorText( status, response, '/_admin/builder/error/make-slot' ), 'error' );

					builder._touch('slots');
					builder._registry.slots.push( { uri : uri, label : size.label, width : size.width, height : size.height, hasImage : false, url : null, alt : {} } );
					alt = Object.assign( {}, size.alt || {} );
					options.owner.put( uri, undefined );
					builder._changed();
					send();
				} );
			} );
		},

		// --------------------------------------------------------------- HTML+

		/**
		 *	The HTML+ editor: a block the builder does not read, or a section edited
		 *	as the markup the builder would write for it - everything is allowed
		 *	and nothing is made clean. Applying it has the server read it again: a
		 *	block that reads as a section is a section from then on, and one that
		 *	does not stays a block of html, with the reason where it had none yet
		 *
		 *	@param		{Array|null}	path					The block, null for a new one
		 *
		 *	@return		void
		 */
		_editBlock : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			if( builder._mutable() === false )
				return;

			const kind = path === null ? 'html' : builder._kind( doc.model, path );
			const block = path === null ? null : doc.model.blocks[path[0]];
			let area = null;

			const open = function( source ) {

				builder._dialog( {
					title		: Nino.content.getText( path === null ? '/_admin/builder/html/title-new' : '/_admin/builder/html/title' ),
					wide		: true,
					returnTo	: path,
					build		: function( body ) {

						const form = builder._fragment( 'html' );

						area = builder._one( form, '.builder-html-source' );
						area.value = source;
						area.addEventListener( 'keydown', function( ev ) {

							if( ev.key !== 'Tab' )
								return;

							ev.preventDefault();

							const start = area.selectionStart;

							area.value = area.value.slice( 0, start )+ '\t'+ area.value.slice( area.selectionEnd );
							area.selectionStart = area.selectionEnd = start + 1;
						} );

						builder._one( form, '.builder-html-note' ).textContent = Nino.content.getText( kind === 'section' ? '/_admin/builder/html/note-section' : '/_admin/builder/html/note' );

						const reason = builder._one( form, '.builder-html-reason' );

						reason.hidden = block === null || block.reason === null || block.reason === undefined;
						reason.textContent = reason.hidden === true ? '' : builder._reasonText( block.reason );

						body.appendChild( form );
						area.focus();
					},
					actions		: [ { label : Nino.content.getText('/_admin/builder/html/apply'), kind : 'primary', onClick : function( problem ) {
						builder._applyBlock( path, area.value, kind, problem );
						return false;
					} } ],
				} );
			};

			// A new block starts as the fragment of the template: what is no section stays a block of html
			if( path === null )
				return open( builder._fragment('skeleton').outerHTML );

			if( kind === 'html' )
				return open( block.source );

			// A section as the markup the builder writes for it: the server says
			builder._apiCall( 'source', { model : doc.model }, function( status, response ) {

				const part = status === 200 && response !== null ? response.parts.find( function( candidate ) { return candidate.block === path[0] } ) : undefined;

				if( part === undefined )
					return builder._status.error( status, response, '/_admin/builder/error/source' );

				open( part.source );
			} );
		},

		/**
		 *	Apply the source of a block: the model with the block edited goes to the
		 *	server, which reads it again, and what comes back is the model
		 *
		 *	@param		{Array|null}	path
		 *	@param		{string}		source
		 *	@param		{string}		kind					What the block was: html, section
		 *	@param		{Function}	problem				Says what is wrong in the dialog
		 *
		 *	@return		void
		 */
		_applyBlock : function( path, source, kind, problem ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const model = builder._clone( doc.model );
			const old = path === null ? null : model.blocks[path[0]];

			// A section that was edited as html has no reason yet: the one the server finds comes back
			const reason = old !== null && kind === 'html' ? old.reason ?? null : ( kind === 'section' ? { line : 0, code : '', detail : '', text : '' } : null );
			const block = { kind : 'html', source : source, reason : reason, edited : true };

			if( path === null )
				model.blocks.push( block );
			else
				model.blocks[path[0]] = block;

			builder._apiCall( 'source', { model : model }, function( status, response ) {

				if( status !== 200 || response === null ) {
					const outcome = builder._outcome( status, response );
					problem( builder._errorText( status, response, '/_admin/builder/error/source' )+ ( outcome.problems.length > 0 ? ' '+ outcome.problems.join(' ') : '' ) );
					return;
				}

				doc.model = builder._normalise( response.model );

				if( old !== null && old.kind === 'section' )
					builder._carry( old, doc.model.blocks[path[0]] );

				const known = JSON.parse( doc.saved ).blocks.filter( function( other ) { return other.kind === 'section' } ).map( function( other ) { return other.id } );

				doc.model.blocks.forEach( function( other ) {
					if( other.kind === 'section' && known.indexOf( other.id ) === -1 )
						builder._fresh[other.id] = true;
				} );

				// The preview is drawn again first: the dialog hands the focus back to the tool that opened it, which the old preview took with it
				builder._sel = [ path === null ? doc.model.blocks.length - 1 : path[0] ];
				builder._changed();
				builder._closeDialog();
			} );
		},

		/**
		 *	What a section that was read again has lost: the instructions to make the
		 *	keys and slots that are new - those of the pictures an attribute names
		 *	among them -, the texts typed for its keys, and where the section was
		 *	renamed from. The markup carries none of them, so they are put back on
		 *	what stands where it stood - the same source, the same id - in the children
		 *	of a component that holds components as well
		 *
		 *	@param		{Object}		old								The section as it was
		 *	@param		{Object}		[again]						The block the server read at its place
		 *
		 *	@return		void
		 */
		_carry : function( old, again ) {

			if( again === undefined || again.kind !== 'section' )
				return;

			const builder = Nino.admin.builder;
			const made = {};
			const typed = {};
			const pictured = {};
			const pictures = function( component ) { return builder._pictures( builder._declared( ( builder._registry.components || {} )[component.name] || {} ) ) };
			// What a component carries, and what its children do
			const gather = function( component ) {

				if( component.create !== undefined && made[component.source] === undefined )
					made[component.source] = component.create;

				if( component.values !== undefined && typed[component.source] === undefined )
					typed[component.source] = component.values;

				Object.keys( component.slots || {} ).forEach( function( attribute ) {
					if( pictured[String( component.attributes[attribute] )] === undefined )
						pictured[String( component.attributes[attribute] )] = component.slots[attribute];
				} );

				( component.components || [] ).forEach( gather );
			};
			const restore = function( component ) {

				if( made[component.source] !== undefined && component.create === undefined )
					component.create = builder._clone( made[component.source] );

				if( typed[component.source] !== undefined && component.values === undefined )
					component.values = builder._clone( typed[component.source] );

				pictures( component ).forEach( function( attribute ) {

					const uri = String( component.attributes[attribute] );

					if( pictured[uri] === undefined || ( component.slots || {} )[attribute] !== undefined )
						return;

					component.slots = component.slots || {};
					component.slots[attribute] = builder._clone( pictured[uri] );
				} );

				( component.components || [] ).forEach( restore );
			};

			old.cols.forEach( function( col ) {

				if( col.background && col.background.create !== undefined )
					pictured[col.background.slot] = col.background.create;

				col.components.forEach( gather );
			} );

			again.cols.forEach( function( col ) {

				if( col.background && col.background.create === undefined && pictured[col.background.slot] !== undefined )
					col.background.create = builder._clone( pictured[col.background.slot] );

				col.components.forEach( restore );
			} );

			if( old.background && old.background.create !== undefined && again.background && again.background.slot === old.background.slot )
				again.background.create = builder._clone( old.background.create );

			if( old.renamedFrom !== undefined && again.id === old.id )
				again.renamedFrom = old.renamedFrom;
		},

		/**
		 *	The source of the whole file as it would be saved: read only, and the
		 *	blocks of html - the ones the builder keeps as they are - marked
		 *
		 *	@return		void
		 */
		_showSource : function() {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			builder._apiCall( 'source', { model : doc.model }, function( status, response ) {

				if( status !== 200 || response === null )
					return builder._status.error( status, response, '/_admin/builder/error/source' );

				builder._dialog( {
					title		: doc.file+ '.tpl',
					wide		: true,
					build		: function( body ) {

						const view = builder._fragment( 'view' );
						const code = builder._one( view, '.builder-view-code' );

						response.parts.forEach( function( part, at ) {

							const block = part.block === null ? undefined : doc.model.blocks[part.block];
							const span = builder._el( 'span', 'builder-part builder-part-'+ part.kind+ ( block !== undefined && block.reason !== null && block.reason !== undefined ? ' is-failed' : '' ), part.source );

							code.appendChild( span );
							code.appendChild( dc.createTextNode( at === response.parts.length - 1 ? '\n' : '\n\n' ) );
						} );

						builder._one( view, '.builder-view-note' ).textContent = Nino.content.getText('/_admin/builder/source/view-note');
						body.appendChild( view );
					},
				} );
			} );
		},

		// ---------------------------------------------------------------- Saving

		/**
		 *	Save the document: the model with the hash of the file it was loaded
		 *	from. What comes back is the model as the server read it again, and it
		 *	replaces this one. A file that changed since is a 409 and the question
		 *	whether to take its version or to write this one anyway; what the server
		 *	refuses as not valid it says at the blocks it names
		 *
		 *	Every way this ends reports to done( ok ), if there is one (see
		 *	Nino.admin.dirty.guard())
		 *
		 *	@param		{Function}	[done]
		 *	@param		{boolean}		[force]				Write although the file is not the one the hash is of
		 *
		 *	@return		void
		 */
		_save : function( done, force ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const report = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			if( doc === null || builder._saving === true )
				return report( false );

			const red = builder._red( doc.model, builder._registry );

			if( red.length > 0 ) {
				builder._select( red[0].path );
				builder._status.fail( Nino.content.getText('/_admin/builder/error/red') );
				return report( false );
			}

			builder._saving = true;
			builder._status.saving();
			dc.getElementById('builder-save').disabled = true;

			builder._apiCall( 'save', builder._saveRequest( doc, force === true ), function( status, response ) {

				builder._saving = false;

				const outcome = builder._outcome( status, response );

				if( outcome.kind === 'saved' ) {

					// The file, and what the save wrote besides: the other panels that show them look again when they are shown
					const wrote = builder._writes( doc.model, builder._registry );

					builder._touch('templates');

					if( wrote.texts === true )
						builder._touch('texts');

					if( wrote.slots === true )
						builder._touch('slots');

					doc.model = builder._normalise( response.model );
					doc.hash = response.hash;
					doc.saved = JSON.stringify( doc.model );
					builder._fresh = {};
					builder._problems = [];

					if( builder._get( doc.model, builder._sel ) === null )
						builder._sel = [];

					builder._repaint();
					builder._status.saved();
					builder._refreshStores();
					dc.getElementById('builder-save').disabled = false;

					if( typeof Nino.admin.dirty === 'object' )
						Nino.admin.dirty.refresh();

					return report( true );
				}

				dc.getElementById('builder-save').disabled = false;

				if( outcome.kind === 'conflict' ) {
					builder._status.fail( Nino.content.getText('/_admin/builder/error/conflict') );
					builder._askConflict();
					return report( false );
				}

				if( outcome.kind === 'invalid' ) {
					builder._problems = outcome.problems;
					builder._repaint();
				}

				// A save that stopped on the way - a panel that could not make or write - may have made the slots its marks name, and it keeps them: they are asked
				// for again, so that an alt text typed next goes to the slot and not into a mark that is no longer read
				if( outcome.kind === 'error' )
					builder._refreshSlots();

				builder._status.fail( builder._errorText( status, response, '/_admin/common/error/save' ) );
				report( false );
			} );
		},

		/**
		 *	The keys of the project and the slots of the registry, asked for again: a save
		 *	made some, wrote the texts of others and the alt texts of the slots it made, and
		 *	the dialogs and the preview show what they hold. The preview is drawn again once
		 *	each is there
		 *
		 *	@return		void
		 */
		_refreshStores : function() {

			const builder = Nino.admin.builder;

			builder._keysAsked = false;
			builder._loadKeys();
			builder._refreshSlots();
		},

		/**
		 *	The slots of the registry, asked for again: those a save made are there, the
		 *	pictures and alt texts stored since are theirs. The preview is drawn again with them
		 *
		 *	@return		void
		 */
		_refreshSlots : function() {

			const builder = Nino.admin.builder;
			const stamp = builder._stamped( [ 'slots' ] );

			builder._apiCall( 'registry', {}, function( status, response ) {

				if( status !== 200 || response === null || builder._registry === null || builder._doc === null )
					return;

				Object.assign( builder._seen, stamp );
				builder._registry.slots = response.slots;
				builder._redraw();
			} );
		},

		/**
		 *	What the registry says of the project, asked for again: the element types with their fields, the slots and the frames a page may
		 *	name - which another panel may have changed. The components and the stacks are the kernel's and stay. One request; the preview is
		 *	drawn again with the answer
		 *
		 *	@return		void
		 */
		_refreshRegistry : function() {

			const builder = Nino.admin.builder;
			const stamp = builder._stamped( [ 'templates', 'elements', 'slots' ] );

			builder._apiCall( 'registry', {}, function( status, response ) {

				if( status !== 200 || response === null || builder._registry === null || builder._doc === null )
					return;

				Object.assign( builder._seen, stamp );

				[ 'types', 'slots', 'headers', 'footers' ].forEach( function( key ) {
					if( response[key] !== undefined )
						builder._registry[key] = response[key];
				} );

				builder._redraw();
			} );
		},

		/**
		 *	The file changed under the document: take its version, write this one
		 *	anyway, or leave things as they are
		 *
		 *	@return		void
		 */
		_askConflict : function() {

			const builder = Nino.admin.builder;

			Nino.adminUi.choiceDialog( {
				title		: Nino.content.getText('/_admin/builder/state/conflict'),
				message	: Nino.content.getText('/_admin/builder/error/conflict'),
				choices	: [
					{ value : 'reload', label : Nino.content.getText('/_admin/builder/label/reload'), kind : 'primary' },
					{ value : 'force', label : Nino.content.getText('/_admin/builder/label/force'), kind : 'danger' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				],
				onChoose	: function( choice ) {

					const request = builder._doc === null ? null : builder._conflictRequest( builder._doc, choice );

					if( request === null )
						return;

					if( request.action === 'builder/load' )
						builder._openEditor( builder._doc.file );
					else
						builder._save( undefined, true );
				},
			} );
		},

		// ----------------------------------------------------- The shell's asking

		/**
		 *	Whether the document holds what nobody saved - what the shell asks
		 *
		 *	@return		{boolean}
		 */
		isDirty : function() {
			return Nino.admin.builder._unsaved( Nino.admin.builder._doc );
		},

		/**
		 *	The input is let go of: the model is what was saved, as the shell is
		 *	about to leave or draw the panel again
		 *
		 *	@return		void
		 */
		discard : function() {

			const builder = Nino.admin.builder;

			if( builder._doc === null )
				return;

			builder._doc.model = JSON.parse( builder._doc.saved );
			builder._fresh = {};
			builder._problems = [];
		},

	};

	/*	The shell asks Save, Discard or Cancel before anything throws the document
		away - a back link, a log out, a change of language - and the browser asks
		when the page is closed	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.register( 'builder', {
			isDirty	: Nino.admin.builder.isDirty,
			save		: function( done ) { Nino.admin.builder._save( done ) },
			discard	: Nino.admin.builder.discard,
			bar			: function() { return dc.getElementById('builder-bar') },
		} );

	Nino.events.bindCallback( 'ready', Nino.admin.builder.init );

})(window, document, document.documentElement, document.body);
