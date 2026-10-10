/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Builder					The panel of the Builder feature: the page templates of
 *													the project in a list, and one of them in an editor that
 *													is its preview - a static drawing of its sections,
 *													columns and components, from the model alone. Every
 *													frame of it has a head of three parts - its title, what
 *													is said of it, the tools that change it (settings, up,
 *													down, duplicate, delete; and for a section to edit it as
 *													HTML+) -, every level ends in a button that adds to it,
 *													and the settings of a frame open in a dialog that tells
 *													its order in tabs and groups. The source of a text or a
 *													picture is changed in a dialog of its own, over that
 *													one, and the name of a section where it stands.
 *
 *													The server reads and writes the file (see Document,
 *													Reader and Writer): this script knows the model only,
 *													as Reader describes it, changes it in place and sends it
 *													back. The preview is drawn from that model and never from
 *													content, so nothing it shows can fail on a text or a
 *													picture. The pure half of the script - the operations on
 *													the model, the red sources, the preview model, what a
 *													save's answer means - touches no element and is what
 *													builder-js-smoke.js drives; the rest draws what the
 *													template (templates/panel.tpl) holds the fragments of.
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

		// The document on screen: { file, hash, model, saved, usedBy }. saved is
		// the model as it was last loaded or saved, as json, which is what the
		// unsaved state is measured against. null while the list is on screen
		_doc			: null,

		// What is selected, as a path into the model: [] the template, [b] a
		// section, [b, c] a column, [b, c, k] a component, [b, c, 'x'] the loop
		_sel			: [],
		// The viewport the preview shows - s, m or l, or g for all of them at once - and
		// the frame the pointer is over: the innermost one, whose tools are seen
		_viewport	: 'l',
		_hovered	: null,
		// The sections the editor made since the document was loaded, by id: their
		// keys and slots are new, so a rename moves them in the model and no
		// request has to move them
		_fresh		: {},
		// The text keys of the project, for the source field: null until they were
		// asked for, [] where this account may not read them
		_keys			: null,
		_keysAsked: false,
		// Whether the answer is in: before it, no key is there and none was refused
		_keysAnswered: false,
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
		BORDERS		: [ '', '1', '2', '3', 'primary' ],
		ROWS			: [ '', 'narrow', 'wide' ],
		// Where a row puts its columns, up and down: at the top, which is none, in the middle,
		// at the bottom. nino-grid-center puts them in the middle across, and is for a file
		// written by hand: the form leaves it as it is and has no icon for it
		ROW_ALIGN	: [ '', 'middle', 'bottom' ],
		IMAGES		: [ '', 'cover', 'parallax' ],
		IMAGE_POS	: [ '', 'top', 'center', 'bottom' ],
		TEXTS			: [ '', 'left', 'center', 'right' ],
		SPACES		: [ '', '0', '1', '2', '3', '4', '5', '6' ],
		// The effects of Nino.css, each of them in three strengths, which the model keeps with
		// the effect as one word (zoom-soft). The plain one, which fades in and rises, has neither
		EFFECTS		: [ '', 'zoom', 'zoom-out', 'slide-left', 'slide-right', 'flip', 'blur' ],
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
		},
		// The custom property of Nino.css that holds the ground a colour paints
		GROUNDS		: { '' : '--color-section-default-bg', alt : '--color-section-alt-bg', tint : '--color-section-tint-bg', dark : '--color-section-dark-bg', black : '--color-section-black-bg', primary : '--color-primary', 'brand-alt' : '--color-brand-alt' },
		// The types of an attribute that fit half a line, next to another
		SHORT			: [ 'select', 'bool', 'int', 'string' ],

		// How many visible characters the editor of an [html] component keeps: the
		// editor trims to it after every edit, so it is a limit and never none
		CONTENT_MAX	: 100000,

		// What a text key and a section id are made of: lower-case words of letters
		// and digits joined by hyphens - the grammar \Nino\Text::isGrammarKey() holds
		// a key to, which the server decides again
		SEGMENT		: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
		KEY				: /^\/(?:template|project|feature|module)(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){3}$/,
		FILE			: /^page-[a-z0-9]+(?:-[a-z0-9]+)*$/,

		// The preview images the registry may name, and the one for the rest
		PREVIEWS	: [ 'title', 'text', 'image', 'button', 'block', 'cells' ],

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

				( block.cols || [] ).forEach( function( col ) {

					col.hidden = map( col.hidden );
					col.width = map( col.width );
					col.components = Array.isArray( col.components ) === true ? col.components : [];

					if( col.stack !== null && typeof col.stack === 'object' )
						col.stack.attributes = map( col.stack.attributes );

					col.components.forEach( function( component ) { component.attributes = map( component.attributes ) } );
				} );
			} );

			return model;
		},

		/**
		 *	The node a path names
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path							[], [b], [b, c], [b, c, k] or [b, c, 'x']
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
				return col.stack === null || col.stack === undefined ? null : col.stack;

			return col.components[path[2]] ?? null;
		},

		/**
		 *	The list a node stands in and its place there - the one a node can be
		 *	moved in. The template and a stack stand in none
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
		 *	What a node is: template, section, html, col, stack or component
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
		 *	nothing is moved, and neither does a node go to another parent
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
		 *	set in the Loop tab of its column
		 *
		 *	@param		{Object}		model
		 *	@param		{Array}			path
		 *
		 *	@return		{Object|null}							settings, html, up, down, duplicate and delete: null for a tool the
		 *															frame does not have, false for one it has and cannot use now;
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
				delete		: place !== null && ( kind !== 'col' || place.list.length > 1 ),
			};
		},

		/**
		 *	How much a frame holds that is somebody's work: the components of a
		 *	column, and its loop, everything in a section. A frame that holds
		 *	none is deleted without asking
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

			return 0;
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

			( section.cols || [] ).forEach( function( col ) {
				col.components.forEach( function( component ) { note( component.source ) } );
			} );

			( known || [] ).forEach( function( entry ) { note( entry.key ) } );

			return names;
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
		 *	A section as the editor makes a new one: a name that is free, the
		 *	animation the template gives, one column over the whole width, nothing
		 *	in it. The template gives the bare nino-vpa where it animates its
		 *	sections and none where it does not
		 *
		 *	@param		{Object}		model
		 *
		 *	@return		{Object}
		 */
		_newSection : function( model ) {

			const builder = Nino.admin.builder;
			const id = builder._free( 'section', function( name ) { return model.blocks.some( function( block ) { return block.id === name } ) } );

			return {
				kind			: 'section',
				id				: id,
				settings	: { row : '', rowAlign : '', rowCustom : '', fullwidth : false, fullheight : false, color : '', border : '', image : '', dim : false, imagePos : '', cover : null, mt : '', mb : '', pt : '', pb : '', text : '', vpa : model.animate === true ? '' : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '', custom : '' },
				background : null,
				cols			: [ builder._newCol() ],
			};
		},

		/**
		 *	A column as the editor makes a new one: the whole width everywhere
		 *
		 *	@return		{Object}
		 */
		_newCol : function() {
			return { width : { s : 100, m : 100, l : 100 }, hidden : {}, text : '', stackAlign : '', stackGap : '', vpa : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '', custom : '', stack : null, components : [] };
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
		 *	An effect and its strength as the one word the model keeps them as
		 *
		 *	@param		{string}		effect						'' for the plain one, else zoom, zoom-out, slide-left, slide-right, flip or blur
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
		 *	has, and in a static column a key of its own where it reads a text -
		 *	made when the document is saved, with the component's label as its text
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		file
		 *	@param		{Array}			at								The path of the column
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

			if( kind === 'content' )
				component.content = '';

			if( section.cols[at[1]].stack === null && ( kind === 'text' || kind === 'image' ) && builder.SEGMENT.test( section.id ) === true ) {

				const slug = builder._keyName( section, file, name, known );

				component.source = builder._keyUri( file, section.id, slug );
				component.create = kind === 'image' ? { label : schema.label || name, width : 1600, height : 900 } : { value : schema.label || name };
			}

			return component;
		},

		/**
		 *	A copy of a node for a place beside it: the copy has an id of its own
		 *	where it is a section, and the keys and slots a section owns are new
		 *	ones in the copy - made at save, with the text the key has now
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		file
		 *	@param		{Array}			path							A section, a column or a component
		 *	@param		{Function}	valueOf						The text of a key as it is now, '' where it is not known
		 *
		 *	@return		{Array|null}							The path of the copy
		 */
		_duplicate : function( model, registry, file, path, valueOf ) {

			const builder = Nino.admin.builder;
			const place = builder._place( model, path );

			if( place === null )
				return null;

			const copy = builder._clone( place.list[place.index] );
			const section = model.blocks[path[0]];

			if( path.length === 1 && copy.kind === 'section' ) {

				const id = builder._free( copy.id, function( name ) { return model.blocks.some( function( block ) { return block.id === name } ) } );
				const from = builder._keyUri( file, copy.id, '' );

				copy.id = id;
				delete copy.renamedFrom;
				builder._rekey( copy, registry, from, builder._keyUri( file, id, '' ), valueOf, false );
			} else if( path.length >= 2 ) {

				const prefix = builder._keyUri( file, section.id, '' );

				builder._rekey( path.length === 2 ? copy : { cols : [ { components : [ copy ] } ] }, registry, prefix, prefix, valueOf, true, section, file );
			}

			place.list.splice( place.index + 1, 0, copy );

			return path.slice( 0, -1 ).concat( [ path[path.length - 1] + 1 ] );
		},

		/**
		 *	The keys and slots of a copy, made new: every source that stands under
		 *	a prefix is moved under another, or - in a copy that stays in its
		 *	section - given a free name beside the original
		 *
		 *	@param		{Object}		node							A section, or a column (or a stand-in with its cols)
		 *	@param		{Object}		registry
		 *	@param		{string}		from							The prefix the sources stand under
		 *	@param		{string}		to								The prefix they go to
		 *	@param		{Function}	valueOf
		 *	@param		{boolean}		within						Whether the copy stays in the section, so the last segment changes
		 *	@param		{Object}		[section]					Its section, for the names that are taken
		 *	@param		{string}		[file]
		 *
		 *	@return		void
		 */
		_rekey : function( node, registry, from, to, valueOf, within, section, file ) {

			const builder = Nino.admin.builder;
			const columns = node.kind === 'section' ? node.cols : ( node.cols || [ node ] );
			const taken = within === true ? builder._names( section, file, [] ) : {};
			const remake = function( source, kind, label ) {

				if( typeof source !== 'string' || source.indexOf( from ) !== 0 )
					return { source : source };

				const last = source.slice( from.length );
				const name = within === true ? builder._free( last.replace( /-\d+$/, '' ) || 'item', function( candidate ) { return taken[candidate] === true } ) : last;

				taken[name] = true;

				return {
					source	: to+ name,
					create	: kind === 'image' ? { label : label, width : 1600, height : 900 } : { value : valueOf( source ) || label },
				};
			};

			if( node.background ) {
				const made = remake( node.background.slot, 'image', Nino.content.getText('/_admin/builder/source/background') );
				node.background.slot = made.source;
				if( made.create !== undefined )
					node.background.create = made.create;
			}

			columns.forEach( function( col ) {

				( col.components || [] ).forEach( function( component ) {

					const kind = builder._sourceKind( registry, component.name );
					const made = remake( component.source, kind, ( ( registry.components || {} )[component.name] || {} ).label || component.name );

					component.source = made.source;

					if( made.create !== undefined )
						component.create = made.create;
				} );
			} );
		},

		// ------------------------------------------------------- Red sources

		/**
		 *	What a source means where it stands, and the ones that mean nothing: a
		 *	field in a static column (it has no element), a field the stack's type
		 *	does not have, a stack that loops a type the Elements panel does not
		 *	know. The editor marks them red and does not save with one - the
		 *	server refuses them as well, and says it a request later
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

				block.cols.forEach( function( col, c ) {

					let fields = null;

					if( col.stack !== null && col.stack !== undefined ) {

						const type = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === col.stack.source } );

						if( type === undefined )
							red.push( { path : [ b, c, 'x' ], source : col.stack.source, why : 'type' } );
						else
							fields = type.fields || {};
					}

					col.components.forEach( function( component, k ) {

						const kind = builder._sourceKind( registry, component.name );
						const source = component.source || '';

						if( source === '' || kind === 'none' || kind === 'content' || source.charAt( 0 ) === '/' || kind === 'href' )
							return;

						if( col.stack === null || col.stack === undefined )
							red.push( { path : [ b, c, k ], source : source, why : 'static' } );
						else if( fields !== null && source !== '.id' && source !== '.uri' && Object.prototype.hasOwnProperty.call( fields, source ) === false )
							red.push( { path : [ b, c, k ], source : source, why : 'field' } );
					} );
				} );
			} );

			return red;
		},

		// --------------------------------------------------------- The preview

		/**
		 *	What the preview draws, from the model alone: the sections in order, each
		 *	with its colour and whether it has a picture behind it, its columns at the
		 *	width and the visibility of one viewport, in a column its components as
		 *	placeholders with the image the registry names, and for a column that runs a
		 *	loop what the loop is, and the type it runs over by the title the Elements
		 *	panel gives it - it is said in the head of the column, whose components are
		 *	the cell of each element. A viewport changes nothing but
		 *	widths and visibility; all of them at once (g) is drawn at the widths of
		 *	the widest, with no column greyed - which viewports hide a column is
		 *	what it says in its head
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		viewport					s, m, l or g
		 *
		 *	@return		{Array}										One entry per block
		 */
		_preview : function( model, registry, viewport ) {

			const builder = Nino.admin.builder;
			const shown = viewport === 'g' ? 'l' : viewport;
			const place = function( name, path ) {

				const schema = ( registry.components || {} )[name] || {};

				return { path : path, name : name, label : schema.label || name, image : builder.PREVIEWS.indexOf( schema.preview ) === -1 ? 'block' : schema.preview };
			};

			return model.blocks.map( function( block, b ) {

				if( block.kind !== 'section' )
					return { path : [ b ], kind : 'html', id : '', color : '', background : false, reason : block.reason || null, cols : [] };

				return {
					path				: [ b ],
					kind				: 'section',
					id					: block.id,
					color				: block.settings.color || '',
					background	: block.background !== null && block.background !== undefined,
					reason			: null,
					cols				: block.cols.map( function( col, c ) {

						const stack = col.stack === null || col.stack === undefined ? null : col.stack;
						const type = stack === null ? undefined : ( registry.types || [] ).find( function( candidate ) { return candidate.uri === stack.source } );

						return {
							path			: [ b, c ],
							width			: parseInt( col.width[shown], 10 ) || 100,
							hidden		: viewport !== 'g' && col.hidden[viewport] === true,
							hiddenIn	: builder.VIEWPORTS.filter( function( at ) { return col.hidden[at] === true } ),
							stack			: stack === null ? null : { path : [ b, c, 'x' ], label : builder._stackLabel( registry, stack.name ), type : type === undefined || type.title === '' ? stack.source : type.title },
							components : col.components.map( function( component, k ) { return place( component.name, [ b, c, k ] ) } ),
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
		 *	for its code, with the detail where the sentence has a place for it
		 *
		 *	@param		{Object}		reason						{ line, code, detail, text }
		 *
		 *	@return		{string}
		 */
		_reasonText : function( reason ) {

			const words = Nino.content.getText( '/_admin/builder/reason/'+ reason.code );
			const text = words === '' ? ( reason.text || '' ) : Nino.adminUi.format( words, reason.detail );

			return reason.line > 0 ? Nino.adminUi.format( Nino.content.getText('/_admin/builder/state/line'), reason.line, text ) : text;
		},

		// ----------------------------------------------------------- Requests

		/**
		 *	Call an action. The workbench's own request helper posts where this
		 *	Nino has one - it knows the project's directory and what to do when the
		 *	page has outlived its session; the post below is what a panel did
		 *	before it, with the base the asset bundle fills in
		 *
		 *	@param		{string}		action				builder/<name> - or only the name - or the action of another panel
		 *	@param		{Object}		payload				Sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( action, payload, callback ) {

			const name = action.indexOf('/') === -1 ? 'builder/'+ action : action;

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( name, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : name, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the panel's own sentence for the code the
		 *	server gave (builder_conflict is /_admin/builder/error/conflict), else
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

				if( own !== '' )
					return own;
			}

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( body.error ?? '' ) || Nino.content.getText( key ) );
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

			builder._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null ) {
					// Asked for again the next time the panel is shown
					builder._started = builder._ready;
					return builder._showError( dc.getElementById('builder-templates'), status, response );
				}

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
					{ key : 'actions', label : '', type : 'string', render : function( value, row ) { return builder._rowActions( row ) } },
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
		 *	Wire what the template draws once: the buttons of the views, the bar, the
		 *	back link, the buttons below the preview, the frame the pointer is over,
		 *	the close of the dialogs and the menu's way out
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

			builder._status = Nino.adminUi.status( dc.getElementById('builder-status') );

			dc.getElementById('builder-save').addEventListener( 'click', function() { builder._save() } );
			dc.getElementById('builder-source').addEventListener( 'click', builder._showSource );
			dc.getElementById('builder-add-section').addEventListener( 'click', function() { builder._addSection() } );
			dc.getElementById('builder-add-html').addEventListener( 'click', function() { builder._editBlock( null ) } );
			dc.getElementById('builder-template-settings').addEventListener( 'click', function() { builder._select( [] ); builder._openSettings( [] ) } );

			// Where the pointer is over a frame its tools are seen - the innermost frame's
			const preview = dc.getElementById('builder-preview');
			preview.addEventListener( 'mouseover', function( ev ) { builder._hover( ev.target.closest('.builder-pick') ) } );
			preview.addEventListener( 'mouseleave', function() { builder._hover( null ) } );

			dc.getElementById('builder-dialog-close').addEventListener( 'click', builder._closeDialog );
			dc.getElementById('builder-dialog').addEventListener( 'close', builder._dialogClosed );
			dc.getElementById('builder-picker-close').addEventListener( 'click', builder._closePicker );

			// The menu goes where a click lands outside it, and on Escape
			dc.addEventListener( 'click', function( ev ) {
				const menu = dc.getElementById('builder-menu');
				if( menu.hidden === false && menu.contains( ev.target ) === false )
					menu.hidden = true;
			} );
			dc.getElementById('builder-menu').addEventListener( 'keydown', function( ev ) {
				if( ev.key === 'Escape' )
					dc.getElementById('builder-menu').hidden = true;
			} );
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
					builder._showScreen( 'list' );
					Nino.admin.router.set( 'builder', [] );
					builder._listError( failure.status, failure.response );
					return;
				}

				const model = builder._normalise( answers.load.model );
				const entry = ( answers.list.templates || [] ).find( function( candidate ) { return candidate.file === file } );

				builder._registry = answers.registry;
				builder._doc = { file : file, hash : answers.load.hash, model : model, saved : JSON.stringify( model ), usedBy : entry === undefined ? [] : entry.usedBy };
				builder._sel = [];
				builder._fresh = {};
				builder._problems = [];
				builder._saving = false;

				builder._renderEditor();
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
		 *	The text keys of the project, once, for the source field and for a copy
		 *	that has to know the text of the key it takes over. An account that may
		 *	not read them gets none and types a key instead
		 *
		 *	@return		void
		 */
		_loadKeys : function() {

			const builder = Nino.admin.builder;

			if( builder._keysAsked === true )
				return;

			builder._keysAsked = true;
			builder._keys = [];

			builder._apiCall( 'text/keys', {}, function( status, response ) {

				builder._keysAnswered = true;

				if( status === 200 && response !== null && Array.isArray( response.keys ) === true )
					builder._keys = response.keys;
			} );
		},

		/**
		 *	The text a key has now, in the language of the workbench where it has one
		 *
		 *	@param		{string}		key
		 *
		 *	@return		{string}									'' where it is not known
		 */
		_valueOf : function( key ) {

			const entry = ( Nino.admin.builder._keys || [] ).find( function( candidate ) { return candidate.key === key } );

			if( entry === undefined )
				return '';

			const locale = typeof Nino.admin.sessionLocale === 'object' && Nino.admin.sessionLocale.current !== null ? Nino.admin.sessionLocale.current : '';
			const value = entry.global === true ? entry.values['*'] : ( entry.values[locale] ?? Object.values( entry.values )[0] );

			return typeof value === 'string' ? value : '';
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
		 *
		 *	@return		void
		 */
		_select : function( path ) {

			const builder = Nino.admin.builder;

			builder._sel = path;
			builder._paintSelection( true );
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
			const plan = builder._preview( model, builder._registry, builder._viewport );

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

				// The title: a section has its name, which is changed where it stands
				name.textContent = block.kind === 'section' ? block.id : 'HTML+';

				if( block.kind === 'section' )
					name.parentNode.appendChild( builder._renamer( name, block.path, builder._barSays ) );

				// What is said of the frame
				if( block.background === true )
					status.appendChild( builder._statusItem( 'background', 'image', Nino.content.getText('/_admin/builder/preview/background'), '' ) );

				if( block.reason !== null )
					status.appendChild( builder._statusItem( 'warning', 'warning', builder._reasonText( block.reason ), '' ) );

				builder._pick( frame, block.path );
				head.appendChild( builder._tools( block.path ) );

				if( block.kind === 'html' )
					body.appendChild( builder._placeholder( { path : block.path, label : 'HTML+', image : 'block' }, 'none' ) );

				block.cols.forEach( function( col ) { body.appendChild( builder._previewCol( col ) ) } );

				// The end of the row: a column more
				if( block.kind === 'section' )
					body.appendChild( builder._addButton( 'col', function() { builder._addCol( block.path ) }, 'builder-add-col' ) );

				mount.appendChild( frame );
			} );

			builder._markRed( mount, builder._red( model, builder._registry ) );
			builder._paintSelection( false );
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

			el.addEventListener( 'click', function( ev ) {
				if( ev.target.closest('.builder-pick') === el )
					builder._select( path );
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
		 *	The tools of a frame: settings, up, down, duplicate, delete - and for a
		 *	section to edit it as HTML+. A tool the frame cannot use now is off
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Element}
		 */
		_tools : function( path ) {

			const builder = Nino.admin.builder;
			const tools = builder._fragment( 'tools' );
			const offered = builder._toolbar( builder._doc.model, path );
			const run = {
				settings	: function() { builder._select( path ); builder._openSettings( path ) },
				html			: function() { builder._editBlock( path ) },
				up				: function() { builder._stepNode( path, -1 ) },
				down			: function() { builder._stepNode( path, 1 ) },
				duplicate	: function() { builder._duplicateAt( path ) },
				delete		: function() { builder._deleteAt( path ) },
			};

			Object.keys( run ).forEach( function( name ) {

				const button = builder._one( tools, '[data-tool="'+ name+ '"]' );

				button.hidden = offered[name] === null;
				button.disabled = offered[name] === false;
				button.addEventListener( 'click', function( ev ) {
					ev.stopPropagation();
					run[name]();
				} );
			} );

			return tools;
		},

		/**
		 *	The button at the end of a level that adds to it
		 *
		 *	@param		{string}		what							section, col, component, stack or html-block - the part of its words under tree/
		 *	@param		{Function}	onClick						Called with the button
		 *	@param		{string}		[className]
		 *
		 *	@return		{Element}
		 */
		_addButton : function( what, onClick, className ) {

			const builder = Nino.admin.builder;
			const button = builder._fragment( 'add' );

			button.textContent = '+ '+ Nino.content.getText( '/_admin/builder/tree/'+ what );
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
		 *	viewport. Its head has the width for a title; what it says of the column
		 *	is the loop it runs - a button, which opens the Loop tab - and that it
		 *	is hidden: where the view is one viewport, in that one, else in which
		 *	ones. A column with a loop has the components of its cell in it, as one
		 *	without has its own
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

			if( col.stack !== null ) {

				// What the head says of the loop is in the title as well: in a narrow column the text is cut off, the title is not
				const says = col.stack.label+ ' · '+ col.stack.type;
				const loop = builder._statusItem( 'loop', 'loop', Nino.adminUi.format( Nino.content.getText('/_admin/builder/menu/loop'), says ), says, function() {
					builder._select( col.stack.path );
					builder._openSettings( col.stack.path );
				} );

				// The type of the loop is red when the project has none like it, and says so here
				loop.dataset.path = col.stack.path.join('.');
				status.appendChild( loop );
			}

			if( all === true ? col.hiddenIn.length > 0 : col.hidden === true )
				status.appendChild( builder._statusItem( 'hidden', 'hidden', all === true
					? Nino.adminUi.format( Nino.content.getText('/_admin/builder/preview/hidden-in'), col.hiddenIn.map( function( viewport ) { return Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) } ).join(', ') )
					: Nino.content.getText('/_admin/builder/preview/hidden'), '' ) );

			builder._one( frame, '.builder-pcol-head' ).appendChild( builder._tools( col.path ) );

			col.components.forEach( function( component ) { body.appendChild( builder._placeholder( component, 'tools' ) ) } );
			body.appendChild( builder._addsRow( col.path ) );

			return frame;
		},

		/**
		 *	The button at the end of a column: a component, which goes in the column
		 *	and - where it runs a loop - in the cell of the loop
		 *
		 *	@param		{Array}			path							The column
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
		 *	A placeholder: the image the registry names for a component, and its label
		 *
		 *	@param		{Object}		component			{ path, label, image }
		 *	@param		{string}		how						tools: a frame of its own, with its tools; none: a frame that has
		 *															its tools elsewhere
		 *
		 *	@return		{Element}
		 */
		_placeholder : function( component, how ) {

			const builder = Nino.admin.builder;
			const ph = builder._fragment( 'ph' );

			builder._one( ph, '.builder-ph-label' ).textContent = component.label;
			builder._one( ph, 'use' ).setAttribute( 'href', '#builder-ph-'+ component.image );
			ph.dataset.path = component.path.join('.');
			builder._pick( ph, component.path );

			if( how === 'tools' )
				ph.appendChild( builder._tools( component.path ) );

			return ph;
		},

		/**
		 *	One thing said of a frame, in the middle of its head: an icon with a title
		 *	where the icon says it all, an icon and a text where there is more to say.
		 *	With an action it is a button
		 *
		 *	@param		{string}		kind							background, warning, hidden or loop: a class of its own
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

		// ------------------------------------------------------ Menu and changes

		/**
		 *	Show a list of choices under an element, in the one menu the template has
		 *
		 *	@param		{Array}			items					[ { label, run, disabled } ]
		 *	@param		{Element}		anchor
		 *
		 *	@return		void
		 */
		_showMenu : function( items, anchor ) {

			const builder = Nino.admin.builder;
			const menu = dc.getElementById('builder-menu');
			const pane = menu.parentNode;
			const at = anchor.getBoundingClientRect();
			const box = pane.getBoundingClientRect();

			menu.innerHTML = '';

			items.forEach( function( item ) {

				const button = builder._el( 'button', 'builder-menu-item', item.label );

				button.type = 'button';
				button.setAttribute( 'role', 'menuitem' );
				button.disabled = item.disabled === true;
				button.addEventListener( 'click', function() {
					menu.hidden = true;
					item.run();
				} );
				menu.appendChild( button );
			} );

			menu.style.top = ( at.bottom - box.top + pane.scrollTop )+ 'px';
			menu.style.left = Math.max( 0, Math.min( at.left - box.left, box.width - 200 ) )+ 'px';
			menu.hidden = false;

			const first = menu.querySelector('button:not(:disabled)');

			if( first !== null )
				first.focus();
		},

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

			const to = builder._duplicate( doc.model, builder._registry, doc.file, path, builder._valueOf );

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
		 *	@return		void
		 */
		_addSection : function() {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;

			if( builder._mutable() === false )
				return;

			const section = builder._newSection( model );

			model.blocks.push( section );
			builder._fresh[section.id] = true;
			builder._sel = [ model.blocks.length - 1 ];
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	A new column at the end of the row of a section
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

			section.cols.push( builder._newCol() );
			builder._sel = [ path[0], section.cols.length - 1 ];
			builder._changed();
			builder._paintSelection( true );
		},

		/**
		 *	Choose the component a column gets: the components of the registry, the
		 *	ones a loop takes where the column has one
		 *
		 *	@param		{Array}			path					The column
		 *	@param		{Element}		anchor
		 *
		 *	@return		void
		 */
		_pickComponent : function( path, anchor ) {

			const builder = Nino.admin.builder;
			const registry = builder._registry;
			const stacked = builder._doc.model.blocks[path[0]].cols[path[1]].stack !== null;

			const items = Object.keys( registry.components || {} ).filter( function( name ) {
				return stacked === false || registry.components[name].loop !== false;
			} ).map( function( name ) {
				return { label : registry.components[name].label || name, run : function() { builder._addComponent( path, name ) } };
			} ).sort( function( a, b ) { return a.label.localeCompare( b.label ) } );

			builder._showMenu( items, anchor );
		},

		/**
		 *	A component at the end of a column - or of the loop it has
		 *
		 *	@param		{Array}			path					The column
		 *	@param		{string}		name					The component
		 *
		 *	@return		void
		 */
		_addComponent : function( path, name ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const col = doc.model.blocks[path[0]].cols[path[1]];

			if( builder._mutable() === false )
				return;

			col.components.push( builder._newComponent( doc.model, builder._registry, doc.file, path, name, builder._keys ) );
			builder._sel = [ path[0], path[1], col.components.length - 1 ];
			builder._changed();
			builder._paintSelection( true );
		},

		// -------------------------------------------------------------- Dialogs

		/**
		 *	Open the dialog the template has, with a title, a body that is built
		 *	here - in tabs, where it has them - and the actions it ends with. A
		 *	form of a node writes into the model as it is changed, so the actions
		 *	are only the way out; the dialog that asks (a new template, an HTML+
		 *	block) has an action that decides
		 *
		 *	@param		{Object}		options
		 *	@param		{string}		options.title
		 *	@param		{Array}			[options.rename]		The path of a section: its name stands in the title, where it is changed
		 *	@param		{number}		[options.blame]			The index of the block the form is of: what a refused save said of it goes over the first tab
		 *	@param		{Function}	[options.build]			Called with the body
		 *	@param		{Array}			[options.tabs]			[ { id, label, build( pane ) } ]
		 *	@param		{string}		[options.tab]				The tab to start on
		 *	@param		{Array}			[options.actions]		[ { label, kind, onClick( problem ) } ] - false keeps the dialog open
		 *	@param		{Function}	[options.onClose]
		 *	@param		{boolean}		[options.wide]
		 *
		 *	@return		void
		 */
		_dialog : function( options ) {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-dialog');
			const content = dc.getElementById('builder-dialog-content');
			const strip = dc.getElementById('builder-dialog-tabs');
			const actions = dc.getElementById('builder-dialog-actions');
			const problems = dc.getElementById('builder-dialog-problems');
			const heading = dc.getElementById('builder-dialog-heading');
			const name = dc.getElementById('builder-dialog-name');

			builder._closeDialog();
			builder._dialogOptions = options;

			dialog.classList.toggle( 'is-wide', options.wide === true );
			dc.getElementById('builder-dialog-kind').textContent = options.title;
			content.innerHTML = '';
			strip.innerHTML = '';
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
				heading.appendChild( builder._renamer( name, options.rename, problem, function( id ) { name.textContent = id } ) );
			}

			if( Array.isArray( options.tabs ) === true && options.tabs.length > 0 ) {
				builder._dialogPanes = builder._tabs( strip, content, options.tabs, options.tab, 'builder' );
				strip.hidden = false;

				if( options.blame !== undefined )
					builder._blamed( builder._dialogPanes[options.tabs[0].id], options.blame );
			} else {
				strip.hidden = true;
				builder._dialogPanes = {};
				options.build( content );
			}

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

		// The options of the dialog that is open, its panes by the id of their tab, and the way it says what is wrong
		_dialogOptions : null,
		_dialogPanes : {},
		_dialogProblem : null,

		/**
		 *	Draw the pane of a tab again - a choice in it changes what it holds
		 *
		 *	@param		{string}		id
		 *
		 *	@return		void
		 */
		_refreshTab : function( id ) {

			const builder = Nino.admin.builder;
			const pane = builder._dialogPanes[id];
			const tab = builder._dialogOptions.tabs.find( function( candidate ) { return candidate.id === id } );

			pane.innerHTML = '';
			tab.build( pane );
		},

		/**
		 *	Close the dialog, and tell what it was made for. The dialog of a source, which
		 *	stands above it, goes first. A close the script asks for makes the dialog fire a
		 *	close event of its own a task later, which has to be let go by: a dialog may
		 *	have been opened since
		 *
		 *	@return		void
		 */
		_closeDialog : function() {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-dialog');
			const options = builder._dialogOptions;

			builder._dialogOptions = null;
			builder._closePicker();

			if( dialog !== null && dialog.open === true && typeof dialog.close === 'function' ) {
				builder._closing++;
				dialog.close();
			}

			if( options !== null && typeof options.onClose === 'function' )
				options.onClose();
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

			builder._dialogOptions = null;

			if( options !== null && typeof options.onClose === 'function' )
				options.onClose();
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
		 *	the tabs of the dialog it is in are drawn again with all of them - the tab
		 *	that is shown is not the only one with a table of viewports
		 *
		 *	@return		{Element}
		 */
		_allViewports : function() {

			const builder = Nino.admin.builder;
			const link = builder._el( 'button', 'builder-link-btn', Nino.content.getText('/_admin/builder/viewport/all') );

			link.type = 'button';
			link.addEventListener( 'click', function() {
				builder._setView('g');
				Object.keys( builder._dialogPanes ).forEach( function( id ) { builder._refreshTab( id ) } );
			} );

			return link;
		},

		/**
		 *	The spacing of a section: a table, with above and below for its rows and
		 *	the margin and the padding for its columns
		 *
		 *	@param		{Object}		settings					Of a section
		 *
		 *	@return		{Element}
		 */
		_spacingTable : function( settings ) {

			const builder = Nino.admin.builder;
			const table = builder._table( [ Nino.content.getText('/_admin/builder/section/spacing'), Nino.content.getText('/_admin/builder/section/outer'), Nino.content.getText('/_admin/builder/section/inner') ] );
			const options = builder._options( 'space', builder.SPACES );

			[ [ 'above', 'mt', 'pt' ], [ 'below', 'mb', 'pb' ] ].forEach( function( row ) {

				const side = Nino.content.getText( '/_admin/builder/section/'+ row[0] );

				builder._tableRow( table.body, builder._rowHead( side ), [ [ row[1], 'outer' ], [ row[2], 'inner' ] ].map( function( cell ) {
					return builder._tableSelect( Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/spacing-cell'), side, Nino.content.getText( '/_admin/builder/section/'+ cell[1] ) ), options, settings[cell[0]], function( value ) {
						settings[cell[0]] = value;
						builder._changed();
					} );
				} ) );
			} );

			return table.table;
		},

		// ---------------------------------------------------------------- Fields

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

			const own = Nino.content.getText( '/_admin/builder/option/'+ group+ '-'+ ( value === '' ? 'default' : value ) );

			if( own !== '' )
				return own;

			return value === '' ? Nino.content.getText('/_admin/builder/label/none') : value;
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

			const field = Nino.adminUi.selectField( { key : '', label : label, hint : hint, options : options, value : value, onChange : onChange } );

			return Nino.admin.builder._hinted( field, hint );
		},

		/**
		 *	The hint of a field under it, where it has one
		 *
		 *	@param		{Element}		field
		 *	@param		{string}		hint
		 *
		 *	@return		{Element}
		 */
		_hinted : function( field, hint ) {

			if( hint !== '' && hint !== undefined && field.querySelector('.nino-admin-hint') === null )
				field.appendChild( Nino.admin.builder._el( 'small', 'nino-admin-hint', hint ) );

			return field;
		},

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

			const field = Nino.adminUi.switchField( { key : '', checked : checked, label : label, hint : hint } );
			const input = field.querySelector('input');

			input.addEventListener( 'change', function() { onChange( input.checked ) } );

			return field;
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

			const field = Nino.adminUi.numberField( { key : '', value : value === null ? '' : value, min : min, max : max, label : label, hint : hint } );
			const input = field.querySelector('input');

			input.addEventListener( 'input', function() {
				const number = parseInt( input.value, 10 );
				onChange( isNaN( number ) === true ? null : number );
			} );

			return field;
		},

		/**
		 *	A line of text, or several lines
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{string}		value
		 *	@param		{Function}	onChange					Called on every input
		 *	@param		{Object}		[more]						{ multiline, readonly, onCommit, list } - onCommit on the change of the value, list an id of a <datalist>
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

			control.addEventListener( 'input', function() { onChange( control.value ) } );

			if( typeof options.onCommit === 'function' )
				control.addEventListener( 'change', function() { options.onCommit( control.value, control ) } );

			field.appendChild( control );
			field.control = control;

			return builder._hinted( field, hint );
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
			builder._hinted( field, hint );

			field.setDisabled = function( disabled ) {
				group.classList.toggle( 'is-disabled', disabled === true );
				inputs.forEach( function( input ) { input.disabled = disabled === true } );
			};

			return field;
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
		 *	patch follows the choice
		 *
		 *	@param		{string}		label
		 *	@param		{string}		hint
		 *	@param		{string}		value
		 *	@param		{Function}	onChange
		 *
		 *	@return		{Element}
		 */
		_colorField : function( label, hint, value, onChange ) {

			const builder = Nino.admin.builder;
			const row = builder._el( 'div', 'builder-with-swatch' );
			const swatch = builder._el( 'span', 'builder-swatch' );
			const paint = function( color ) {

				const ground = builder._ground( color );

				swatch.dataset.color = color === '' ? 'plain' : color;

				if( ground === '' )
					swatch.style.removeProperty('--builder-swatch');
				else
					swatch.style.setProperty( '--builder-swatch', ground );
			};

			swatch.setAttribute( 'aria-hidden', 'true' );
			paint( value );

			row.appendChild( builder._selectField( label, hint, builder._options( 'color', builder.COLORS ), value, function( color ) {
				paint( color );
				onChange( color );
			} ) );
			row.appendChild( swatch );

			return row;
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
		 *
		 *	@return		{Element}
		 */
		_focusField : function( focus, enabled, onChange ) {

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
			grid.setAttribute( 'aria-label', Nino.content.getText('/_admin/builder/source/focus') );

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
		 *	@param		{Object}		field							{ key, type, label, hint, group, values, items, min, max } - the type is
		 *															bool, int, string, color, segment (its items being the options of the
		 *															segment) or else a select over the values
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

			if( field.type === 'bool' )
				return builder._switchField( label, hint, target[field.key] === true, write );

			if( field.type === 'int' )
				return builder._numberField( label, hint, target[field.key], field.min, field.max, write );

			if( field.type === 'string' )
				return builder._textField( label, hint, target[field.key], write );

			if( field.type === 'color' )
				return builder._colorField( label, hint, String( target[field.key] ), write );

			if( field.type === 'segment' )
				return builder._segmentField( label, field.items, String( target[field.key] ), write, hint );

			return builder._selectField( label, hint, builder._options( field.group, field.values ), String( target[field.key] ), write );
		},

		// ---------------------------------------------------------- The settings

		/**
		 *	Open the form of what a path names
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_openSettings : function( path ) {

			const builder = Nino.admin.builder;
			const kind = builder._kind( builder._doc.model, path );

			// Under 38 rem the editor is for reading: a form would write
			if( builder._mutable() === false )
				return;

			if( kind === 'template' )
				return builder._templateDialog();

			if( kind === 'section' )
				return builder._sectionDialog( path );

			if( kind === 'col' )
				return builder._colDialog( path, 'layout' );

			if( kind === 'stack' )
				return builder._colDialog( path.slice( 0, 2 ), 'loop' );

			if( kind === 'html' )
				return builder._editBlock( path );

			builder._componentDialog( path );
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

					builder._group( body, Nino.content.getText('/_admin/builder/group/frames') ).appendChild( builder._line( [
						builder._frameField( Nino.content.getText('/_admin/builder/label/header'), registry.headers, doc.model.header, function( value ) { doc.model.header = value; builder._changed() } ),
						builder._frameField( Nino.content.getText('/_admin/builder/label/footer'), registry.footers, doc.model.footer, function( value ) { doc.model.footer = value; builder._changed() } ),
					] ) );

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
		 *	The form of a section, in four tabs: how it is laid out (the width of its row, whether
		 *	it is full width and full height, where its columns and its text are put, the spacing),
		 *	its background (the colour, the border, the picture), its animation and the classes
		 *	of its own. The name of the section is part of the title, where it is changed. Each
		 *	field writes into the model at once
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_sectionDialog : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const setting = function( field ) { return builder._setting( section.settings, field ) };
			// Which of the three the animation is: like the template, off or its own - kept while the form is open
			const animation = { mode : builder._animationMode( builder._doc.model.animate === true, section.settings ) };

			builder._dialog( {
				title	: Nino.content.getText('/_admin/builder/tree/section'),
				rename	: path,
				blame	: path[0],
				tabs	: [
					{ id : 'layout', label : Nino.content.getText('/_admin/builder/tab/layout'), build : function( pane ) {
						pane.appendChild( setting( { key : 'row', type : 'select', label : '/_admin/builder/section/row', hint : '/_admin/builder/hint/row', group : 'row', values : builder.ROWS } ) );
						pane.appendChild( builder._line( [
							setting( { key : 'fullwidth', type : 'bool', label : '/_admin/builder/section/fullwidth', hint : '/_admin/builder/hint/fullwidth' } ),
							setting( { key : 'fullheight', type : 'bool', label : '/_admin/builder/section/fullheight', hint : '/_admin/builder/hint/fullheight' } ),
						] ) );
						pane.appendChild( builder._line( [
							setting( { key : 'rowAlign', type : 'segment', label : '/_admin/builder/section/row-align', items : builder._alignOptions( 'rowAlign', 'row-align', builder.ROW_ALIGN ) } ),
							setting( { key : 'text', type : 'segment', label : '/_admin/builder/section/text', items : builder._alignOptions( 'text', 'text', builder.TEXTS ) } ),
						] ) );
						pane.appendChild( builder._spacingTable( section.settings ) );
					} },
					{ id : 'background', label : Nino.content.getText('/_admin/builder/tab/background'), build : function( pane ) {
						pane.appendChild( builder._line( [
							setting( { key : 'color', type : 'color', label : '/_admin/builder/section/color' } ),
							setting( { key : 'border', type : 'select', label : '/_admin/builder/section/border', group : 'border', values : builder.BORDERS } ),
						] ) );
						pane.appendChild( builder._line( [
							setting( { key : 'image', type : 'select', label : '/_admin/builder/section/image', hint : '/_admin/builder/hint/image', group : 'image', values : builder.IMAGES } ),
							setting( { key : 'dim', type : 'bool', label : '/_admin/builder/section/dim' } ),
						] ) );
						pane.appendChild( builder._backgroundField( path ) );
					} },
					{ id : 'animation', label : Nino.content.getText('/_admin/builder/tab/animation'), build : function( pane ) { builder._sectionAnimation( pane, section, animation ) } },
					{ id : 'custom', label : Nino.content.getText('/_admin/builder/tab/custom'), build : function( pane ) {
						pane.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.content.getText('/_admin/builder/hint/custom') ) );
						pane.appendChild( builder._line( [
							setting( { key : 'custom', type : 'string', label : '/_admin/builder/section/custom' } ),
							setting( { key : 'rowCustom', type : 'string', label : '/_admin/builder/section/row-custom' } ),
						] ) );
					} },
				],
			} );
		},

		/**
		 *	The animation of a section: like the template - what the template gives its
		 *	sections -, off, or its own, which opens the fields. Where the template does not
		 *	animate its sections, none is like the template and there is no off
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		section
		 *	@param		{Object}		state							{ mode }, kept by the dialog over a redraw of the tab
		 *
		 *	@return		void
		 */
		_sectionAnimation : function( pane, section, state ) {

			const builder = Nino.admin.builder;
			const animate = builder._doc.model.animate === true;
			const options = ( animate === true ? [ 'like', 'off', 'own' ] : [ 'like', 'own' ] ).map( function( value ) {
				return { value : value, label : Nino.content.getText( '/_admin/builder/option/anim-'+ ( value === 'like' && animate === false ? 'like-off' : value ) ) };
			} );

			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/section/vpa'), Nino.content.getText( animate === true ? '/_admin/builder/hint/vpa-section' : '/_admin/builder/hint/vpa-section-off' ), options, state.mode, function( value ) {

				state.mode = value;
				builder._animationSet( animate, section.settings, value );
				builder._changed();
				builder._refreshTab('animation');
			} ) );

			if( state.mode === 'own' )
				builder._animationFields( pane, section.settings, {} );
		},

		/**
		 *	The animation of a section or a column, or the kind of animation of the
		 *	template: the effect and its strength on a line, how fast and how often on the
		 *	next, when it starts and how long it takes on the last. The effect and the
		 *	strength are two fields and one word of the model (zoom-soft); the strength is
		 *	none to choose with the plain effect, or with none. vpa is null for none, '' for
		 *	the plain effect, else that word. With none, the speed and how often are none to
		 *	choose either: nothing is written for an animation that is not there
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		target						The settings of a section or a column - or the model of the template
		 *	@param		{Object}		options						{ none: whether none is among the effects - a column's -, rest: false for no
		 *															repeat, delay and duration - the template's -, times: false for no delay and
		 *															duration - a column's, which the file keeps as classes and has no attribute
		 *															for -, hint: the key of the words under the effect, speeds: the group of the
		 *															words of the speeds }
		 *
		 *	@return		void
		 */
		_animationFields : function( pane, target, options ) {

			const builder = Nino.admin.builder;
			const state = { strength : builder._effectSplit( target.vpa ).strength || 'medium' };
			const effects = builder.EFFECTS.map( function( effect ) { return { value : effect, label : builder._optionLabel( 'effect', effect ) } } );
			const strength = builder._segmentField( Nino.content.getText('/_admin/builder/section/vpa-strength'), builder.STRENGTHS.map( function( value ) {
				return { value : value, label : builder._optionLabel( 'strength', value ) };
			} ), state.strength, function( value ) {
				state.strength = value;
				target.vpa = builder._effectJoin( builder._effectSplit( target.vpa ).effect, value );
				builder._changed();
			} );
			const speed = builder._setting( target, { key : 'vpaSpeed', type : 'select', label : '/_admin/builder/section/vpa-speed', group : options.speeds ?? 'speed', values : builder.SPEEDS } );

			// A mode a hand-written file carries is kept in the list, so that reading it changes nothing
			const mode = options.rest === false ? null : builder._setting( target, { key : 'vpaMode', type : 'select', label : '/_admin/builder/section/vpa-mode', group : 'mode',
				values : builder.ANIMATION_MODES.concat( builder.ANIMATION_MODES.indexOf( target.vpaMode ) === -1 ? [ target.vpaMode ] : [] ) } );

			// What the effect leaves to choose: the strength with an effect that has one, the speed and the mode with any
			const sync = function() {

				strength.setDisabled( ( target.vpa ?? '' ) === '' );

				[ speed, mode ].forEach( function( field ) {
					if( field !== null )
						field.querySelector('select').disabled = target.vpa === null;
				} );
			};

			if( options.none === true )
				effects.unshift( { value : 'none', label : builder._optionLabel( 'vpa', 'none' ) } );

			pane.appendChild( builder._line( [
				builder._selectField( Nino.content.getText('/_admin/builder/section/vpa-effect'), Nino.content.getText( options.hint ?? '/_admin/builder/hint/vpa' ), effects, target.vpa === null ? 'none' : builder._effectSplit( target.vpa ).effect, function( effect ) {

					target.vpa = effect === 'none' ? null : builder._effectJoin( effect, state.strength );
					sync();
					builder._changed();
				} ),
				strength,
			] ) );

			if( mode === null )
				pane.appendChild( speed );
			else {

				pane.appendChild( builder._line( [ speed, mode ] ) );

				if( options.times !== false )
					pane.appendChild( builder._line( [
						builder._setting( target, { key : 'vpaDelay', type : 'string', label : '/_admin/builder/section/vpa-delay', hint : '/_admin/builder/hint/time' } ),
						builder._setting( target, { key : 'vpaDuration', type : 'string', label : '/_admin/builder/section/vpa-duration', hint : '/_admin/builder/hint/time' } ),
					] ) );
			}

			sync();
		},

		/**
		 *	Fields that belong together in one row, the width shared among them - and
		 *	a caption in front of them where there is one
		 *
		 *	@param		{Array}			fields						Elements
		 *	@param		{string}		[caption]
		 *
		 *	@return		{Element}
		 */
		_line : function( fields, caption ) {

			const builder = Nino.admin.builder;
			const line = builder._el( 'div', caption === undefined ? 'builder-line' : 'builder-line has-caption' );

			if( caption !== undefined )
				line.appendChild( builder._el( 'span', 'builder-line-caption', caption ) );

			fields.forEach( function( field ) { line.appendChild( field ) } );

			return line;
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

			// A click in the field is not one on the frame it is in
			[ 'click', 'dblclick' ].forEach( function( type ) { field.addEventListener( type, function( ev ) { ev.stopPropagation() } ) } );

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
		 *	its sources are written new, since nothing is moved that was never made
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
				const move = function( node ) {
					if( typeof node.source === 'string' && node.source.indexOf( from ) === 0 )
						node.source = to+ node.source.slice( from.length );
				};

				if( section.background && section.background.slot.indexOf( from ) === 0 )
					section.background.slot = to+ section.background.slot.slice( from.length );

				section.cols.forEach( function( col ) { col.components.forEach( move ) } );

				delete builder._fresh[old];
				builder._fresh[id] = true;
			} else if( section.renamedFrom === undefined )
				section.renamedFrom = old;
			else if( section.renamedFrom === id )
				delete section.renamedFrom;

			section.id = id;
			builder._changed();
		},

		// ---------------------------------------------------- Columns and loops

		/**
		 *	The form of a column, in four tabs: its layout (its width and visibility in the
		 *	viewport or in each of them, where its text and its components are put), the loop it
		 *	runs, its animation, and the classes of its own
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		tab						The tab to start on: layout or loop
		 *
		 *	@return		void
		 */
		_colDialog : function( path, tab ) {

			const builder = Nino.admin.builder;
			const col = builder._doc.model.blocks[path[0]].cols[path[1]];

			builder._dialog( {
				title	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/title'), builder._doc.model.blocks[path[0]].id ),
				blame	: path[0],
				tab		: tab,
				tabs	: [
					{ id : 'layout', label : Nino.content.getText('/_admin/builder/tab/layout'), build : function( pane ) {

						builder._widthTable( pane, col );

						pane.appendChild( builder._setting( col, { key : 'text', type : 'segment', label : '/_admin/builder/section/text', items : builder._alignOptions( 'text', 'text', builder.TEXTS ) } ) );
						pane.appendChild( builder._line( [
							builder._setting( col, { key : 'stackAlign', type : 'segment', label : '/_admin/builder/col/stack-align', hint : '/_admin/builder/hint/stack-align', items : builder._alignOptions( 'stackAlign', 'stack-align', builder.STACK_ALIGN ) } ),
							builder._setting( col, { key : 'stackGap', type : 'select', label : '/_admin/builder/col/stack-gap', group : 'space', values : builder.SPACES } ),
						] ) );
					} },
					{ id : 'loop', label : Nino.content.getText('/_admin/builder/tab/loop'), build : function( pane ) { builder._stackForm( pane, path ) } },
					{ id : 'animation', label : Nino.content.getText('/_admin/builder/tab/animation'), build : function( pane ) { builder._animationFields( pane, col, { none : true, times : false, hint : '/_admin/builder/hint/vpa-col' } ) } },
					{ id : 'custom', label : Nino.content.getText('/_admin/builder/tab/custom'), build : function( pane ) {
						pane.appendChild( builder._setting( col, { key : 'custom', type : 'string', label : '/_admin/builder/col/custom', hint : '/_admin/builder/hint/custom' } ) );
					} },
				],
			} );
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
					builder._tableSelect( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/width'), device ), builder._widths(), col.width[viewport], function( value ) {
						builder._setWidth( col, viewport, value );
						builder._changed();
					} ),
					builder._hiddenBox( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/hidden'), device ), col.hidden[viewport] === true, function( checked ) {
						builder._setHidden( col, viewport, checked );
						builder._changed();
					} ),
				];
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
		 *	source that means nothing is red until it is changed
		 *
		 *	@param		{Element}		pane
		 *	@param		{Array}			path					The column
		 *
		 *	@return		void
		 */
		_stackForm : function( pane, path ) {

			const builder = Nino.admin.builder;
			const registry = builder._registry;
			const col = builder._doc.model.blocks[path[0]].cols[path[1]];
			const options = [ { value : '', label : Nino.content.getText('/_admin/builder/stack/static') } ].concat( Object.keys( registry.stacks || {} ).map( function( name ) { return { value : name, label : builder._stackLabel( registry, name ) } } ) );

			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/kind'), Nino.content.getText('/_admin/builder/hint/stack-kind'), options, col.stack === null || col.stack === undefined ? '' : col.stack.name, function( name ) {

				col.stack = name === '' ? null : builder._stackFor( registry, name, col.stack === null || col.stack === undefined ? null : col.stack );

				builder._changed();
				builder._refreshTab('loop');
			} ) );

			if( col.stack === null || col.stack === undefined )
				return;

			const stack = col.stack;
			const schema = registry.stacks[stack.name] || {};
			const grid = schema.grid !== false;
			const type = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === stack.source } );
			const typeOptions = ( registry.types || [] ).map( function( candidate ) { return { value : candidate.uri, label : candidate.title+ ' ('+ candidate.uri+ ')' } } );
			const number = function( key, label, hint ) {
				return builder._numberField( Nino.content.getText( label ), hint === undefined ? '' : Nino.content.getText( hint ), parseInt( stack.attributes[key], 10 ) || 0, 0, 100000, function( value ) {
					stack.attributes[key] = String( value === null ? 0 : value );
					builder._changed();
				} );
			};
			const id = builder._textField( Nino.content.getText('/_admin/builder/stack/id'), Nino.content.getText('/_admin/builder/hint/stack-id'), stack.attributes.id, function( value ) {
				stack.attributes.id = value;
				builder._changed();
			} );

			if( type === undefined )
				typeOptions.unshift( { value : stack.source, label : stack.source === '' ? Nino.content.getText('/_admin/builder/label/none') : stack.source } );

			// What the loop runs over; a loop without a grid has its id here, since it has no grid to put it in
			const data = builder._group( pane, Nino.content.getText('/_admin/builder/group/data') );

			data.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/type'), '', typeOptions, stack.source, function( value ) {
				stack.source = value;
				builder._changed();
				builder._refreshTab('loop');
			} ) );

			data.appendChild( Nino.adminUi.notice( Nino.content.getText( type === undefined ? '/_admin/builder/stack/type-missing' : '/_admin/builder/stack/type-hint' ), { href : '#types', label : Nino.content.getText('/_admin/builder/stack/types-link') } ) );

			data.appendChild( builder._textField( Nino.content.getText('/_admin/builder/stack/query'), Nino.content.getText('/_admin/builder/hint/query'), stack.attributes.query, function( value ) {
				stack.attributes.query = value;
				builder._changed();
			} ) );

			if( grid === false )
				data.appendChild( id );

			// In which order, and how many
			const order = builder._group( pane, Nino.content.getText('/_admin/builder/group/order') );

			builder._sortField( order, stack, type === undefined ? {} : type.fields );
			order.appendChild( builder._line( [ number( 'limit', '/_admin/builder/stack/limit', '/_admin/builder/hint/limit' ), number( 'offset', '/_admin/builder/stack/offset' ) ] ) );

			// The cells: how wide in each viewport, how far apart, as high as the highest
			if( grid === true ) {

				const cells = builder._group( pane, Nino.content.getText('/_admin/builder/group/grid') );

				builder._cellsTable( cells, stack );

				cells.appendChild( builder._line( [
					builder._selectField( Nino.content.getText('/_admin/builder/stack/gap'), '', builder._options( 'space', builder.SPACES.slice( 1 ) ), String( stack.attributes.gap ), function( value ) {
						stack.attributes.gap = value;
						builder._changed();
					} ),
					builder._switchField( Nino.content.getText('/_admin/builder/stack/autoheight'), Nino.content.getText('/_admin/builder/hint/autoheight'), stack.attributes.autoheight === '1', function( checked ) {
						stack.attributes.autoheight = checked === true ? '1' : '0';
						builder._changed();
					} ),
				] ) );

				cells.appendChild( id );
			}

			const declared = builder._declared( schema );

			if( Object.keys( declared ).length > 0 )
				builder._attributeFields( builder._group( pane, Nino.content.getText('/_admin/builder/group/own') ), declared, stack.attributes, { fields : type === undefined ? {} : type.fields, section : builder._doc.model.blocks[path[0]] } );
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
			const line = builder._el( 'div', 'builder-sort' );

			if( state.field !== '' && names.indexOf( state.field ) === -1 )
				choices.push( { value : state.field, label : state.field } );

			line.appendChild( builder._selectField( label, '', choices, state.field, function( value ) {
				state.field = value;
				builder._sortSet( stack, state.field, state.descending );
				builder._changed();
			} ) );

			line.appendChild( builder._sortToggles( state.descending, function( descending ) {
				state.descending = descending;
				builder._sortSet( stack, state.field, state.descending );
				builder._changed();
			} ) );

			pane.appendChild( line );
			pane.appendChild( builder._el( 'small', 'nino-admin-hint', hint ) );
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
		 *	The form of a component, in tabs: its content - the source of its text or its
		 *	picture, for [html] the editor of the blocks profile instead -, its properties,
		 *	which are the attributes its schema declares, in the order of the schema, and the
		 *	classes of its own. A component without a source, or without attributes, has no
		 *	tab for what it does not have
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_componentDialog : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const component = builder._get( doc.model, path );
			const schema = ( builder._registry.components || {} )[component.name] || {};
			const kind = builder._sourceKind( builder._registry, component.name );
			const section = doc.model.blocks[path[0]];
			const col = section.cols[path[1]];
			const fields = col.stack === null || col.stack === undefined ? null : ( ( builder._registry.types || [] ).find( function( candidate ) { return candidate.uri === col.stack.source } ) || { fields : {} } ).fields;
			const declared = builder._declared( schema );
			const tabs = [];
			let editor = null;
			let typed = false;

			if( kind !== 'none' )
				tabs.push( { id : 'content', label : Nino.content.getText('/_admin/builder/tab/content'), build : function( pane ) {

					if( kind === 'content' ) {

						const mount = builder._el( 'div', 'builder-content' );

						pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/component/content') ) );
						pane.appendChild( mount );

						if( typeof Nino.admin.htmlEditor === 'object' ) {
							editor = Nino.admin.htmlEditor.create( mount, component.content ?? '', builder.CONTENT_MAX, 8, 'blocks' );
							mount.addEventListener( 'input', function() { typed = true; component.content = editor.getValue(); builder._changed() } );
						}

						return;
					}

					pane.appendChild( builder._sourceRow( {
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
							component.source = source;
							component.text = null;

							if( create === null )
								delete component.create;
							else
								component.create = create;

							builder._changed();
						},
						onFixed	: function( text ) {
							component.text = text;
							component.source = '';
							delete component.create;
							builder._changed();
						},
					} ) );
				} } );

			if( Object.keys( declared ).length > 0 )
				tabs.push( { id : 'properties', label : Nino.content.getText('/_admin/builder/tab/properties'), build : function( pane ) {
					builder._attributeFields( pane, declared, component.attributes, { fields : fields, section : section } );
				} } );

			tabs.push( { id : 'custom', label : Nino.content.getText('/_admin/builder/tab/custom'), build : function( pane ) {
				pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/component/custom'), Nino.content.getText('/_admin/builder/hint/custom'), component.attributes['class'], function( value ) {
					component.attributes['class'] = value;
					builder._changed();
				} ) );
			} } );

			builder._dialog( {
				title	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/component/title'), schema.label || component.name ),
				blame	: path[0],
				wide	: kind === 'content',
				tabs	: tabs,
				onClose	: function() {
					// The content stays as it was where nothing was typed: the editor shows it in its own normal form, which is not the file's
					if( editor !== null ) {

						if( typed === true ) {
							component.content = editor.getValue();
							builder._changed();
						}

						editor.destroy();
					}
				},
			} );
		},

		/**
		 *	The attributes of a schema as the lines of a form: in the order of the schema,
		 *	two to a line where both of them are short, a select, a switch, a number or a
		 *	line of text; every other one has a line to itself
		 *
		 *	@param		{Object}		declared					name => the schema's entry
		 *
		 *	@return		{Array}										[ [ name, name ], [ name ] ]
		 */
		_attributeLines : function( declared ) {

			const builder = Nino.admin.builder;
			const lines = [];
			let open = null;

			Object.keys( declared ).forEach( function( name ) {

				if( builder.SHORT.indexOf( declared[name].type ) === -1 ) {
					open = null;
					lines.push( [ name ] );
				} else if( open !== null ) {
					open.push( name );
					open = null;
				} else {
					open = [ name ];
					lines.push( open );
				}
			} );

			return lines;
		},

		/**
		 *	The attributes of a component or a stack, a field each, as _attributeLines() has
		 *	them in lines
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		declared					name => the schema's entry
		 *	@param		{Object}		attributes				Where the values are kept
		 *	@param		{Object}		context						{ fields, section }: the fields of the stack's type, where there is a stack, and the section the attributes are in
		 *
		 *	@return		void
		 */
		_attributeFields : function( pane, declared, attributes, context ) {

			const builder = Nino.admin.builder;

			builder._attributeLines( declared ).forEach( function( names ) {

				const fields = names.map( function( name ) { return builder._attributeField( name, declared[name], attributes, context ) } );

				pane.appendChild( fields.length === 1 ? fields[0] : builder._line( fields ) );
			} );
		},

		/**
		 *	Whether a value is a source: a key of the project or of the system, or a field
		 *	of the type a loop runs over - and not an address typed in
		 *
		 *	@param		{string}		value
		 *	@param		{Object|null}	fields						The fields of the loop's type, null where there is no loop
		 *
		 *	@return		{boolean}
		 */
		_isSource : function( value, fields ) {

			const builder = Nino.admin.builder;

			return builder.KEY.test( value ) === true || value.indexOf('/_nino/') === 0 || value === '.id' || value === '.uri'
				|| ( fields !== null && fields !== undefined && Object.prototype.hasOwnProperty.call( fields, value ) === true );
		},

		/**
		 *	One attribute of a component or a stack, by the type its schema gives
		 *	it. Every value is a string in the model, as a call writes it. A key, an image
		 *	slot or a link is a source, and is changed in the dialog of the sources
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry: type, label, hint, options, min, max
		 *	@param		{Object}		attributes				Where the value is kept
		 *	@param		{Object}		context						{ fields, section }, as _attributeFields() has them
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
			};
			const value = attributes[name] === undefined || attributes[name] === null ? '' : String( attributes[name] );

			if( declared.type === 'select' )
				return builder._selectField( label, hint, ( declared.options || [] ).map( function( option ) { return { value : option, label : option === '' ? Nino.content.getText('/_admin/builder/label/none') : option } } ), value, write );

			if( declared.type === 'bool' )
				return builder._switchField( label, hint, value === '1', function( checked ) { write( checked === true ? '1' : '0' ) } );

			if( declared.type === 'int' )
				return builder._numberField( label, hint, value === '' ? null : parseInt( value, 10 ), declared.min, declared.max, function( number ) { write( number === null ? '' : String( number ) ) } );

			if( declared.type === 'lines' )
				return builder._textField( label, hint, value, write, { multiline : true } );

			if( declared.type === 'key' || declared.type === 'href' || declared.type === 'image' ) {

				const fields = context.fields ?? null;
				// An address typed in is a fixed value, and shown as one
				const fixed = declared.type === 'href' && value !== '' && builder._isSource( value, fields ) === false;

				return builder._sourceRow( {
					kind		: declared.type === 'image' ? 'image' : ( declared.type === 'href' ? 'href' : 'text' ),
					fixed		: declared.type === 'href',
					create	: false,
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
			const show = function() {
				current.textContent = builder._sourceShown( options );
				red.hidden = options.red === undefined;
				red.textContent = options.red === undefined ? '' : Nino.content.getText( '/_admin/builder/tree/red-'+ options.red.why );
			};

			name.hidden = options.label === undefined;
			name.textContent = options.label ?? '';
			caption.hidden = options.caption === '';
			caption.textContent = options.caption;

			builder._one( row, '.builder-source-line' ).appendChild( builder._iconButton( 'source', Nino.content.getText('/_admin/builder/source/title'), function() {

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
			} ) );

			show();

			return row;
		},

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
		 *	@param		{string}		options.seed				The text a new key starts with, the label of a new slot
		 *	@param		{Function}	options.onPick			Called with ( source, create ): create is null, or what the key or the slot is made with
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

			if( options.create === true )
				tabs.push( { id : 'new', label : Nino.content.getText('/_admin/builder/source/tab-new'), build : function( pane ) {

					const what = options.kind === 'image' ? 'slot' : 'key';

					builder._newName( pane, options, what, function( uri, size ) {
						options.onPick( uri, what === 'slot' ? { label : options.seed, width : size.width, height : size.height } : { value : options.seed } );
						done();
					} );
				} } );

			if( options.fixed === true )
				tabs.push( { id : 'fixed', label : Nino.content.getText('/_admin/builder/source/tab-fixed'), build : function( pane ) { builder._fixedPane( pane, options, done ) } } );

			strip.innerHTML = '';
			content.innerHTML = '';
			builder._tabs( strip, content, tabs, typeof options.text === 'string' ? 'fixed' : 'pick', 'builder-picker' );

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

				const names = Object.keys( options.fields ).filter( function( name ) { return options.fields[name].type === ( image === true ? 'image' : 'string' ) } );
				const items = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ]
					.concat( names.map( function( name ) { return { value : name, label : name } } ) )
					.concat( image === true ? [] : [ { value : '.id', label : '.id' }, { value : '.uri', label : '.uri' } ].filter( function( item ) { return options.kind === 'href' || item.value === '.id' } ) );

				pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/field'), Nino.content.getText( image === true ? '/_admin/builder/hint/field-image' : '/_admin/builder/hint/field' ), items, options.value.charAt(0) === '/' ? '' : options.value, pick ) );
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

			pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/source/key') ) );

			if( keys.length > 0 )
				builder._picker( pane, keys, options.value, Nino.content.getText('/_admin/builder/source/search'), pick, keys.some( function( key ) { return key.value.indexOf( own ) === 0 } ) === true ? own : '' );
			else
				pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/source/typed'), builder._keysAnswered === true ? Nino.content.getText('/_admin/builder/hint/typed') : '', options.value.charAt(0) === '/' ? options.value : '', function() {}, {
					onCommit : function( value ) { if( builder.KEY.test( value.trim() ) === true || value.trim().indexOf('/_nino/') === 0 ) pick( value.trim() ) },
				} ) );
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
		 *	@param		{Function}	use								( uri, { width, height } )
		 *
		 *	@return		void
		 */
		_newName : function( pane, options, what, use ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const box = builder._fragment( 'newkey' );
			const size = { width : 1600, height : 900 };
			const name = builder._one( box, '.builder-new-name' );
			const uri = builder._one( box, '.builder-new-uri' );
			const message = builder._one( box, '.builder-new-message' );
			const go = builder._one( box, '.builder-new-use' );
			const check = function() {

				let problem = builder.SEGMENT.test( options.section.id ) === false ? 'grammar' : builder._nameProblem( options.section, doc.file, name.value.trim(), builder._keys );

				// A slot's segments each start with a letter: the section id and the name are told apart, the one to rename is the one that starts with a digit
				if( problem === '' && what === 'slot' )
					problem = /^[a-z]/.test( options.section.id ) === false ? 'slot' : ( /^[a-z]/.test( name.value.trim() ) === false ? 'slot-name' : '' );

				uri.textContent = builder._keyUri( doc.file, options.section.id, name.value.trim() );
				message.textContent = problem === '' ? '' : Nino.content.getText( '/_admin/builder/source/name-'+ problem );
				go.disabled = problem !== '';
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
				use( uri.textContent, size );
				name.value = builder._keyName( options.section, doc.file, options.name, builder._keys );
				check();
			} );

			check();
			pane.appendChild( box );
		},

		/**
		 *	The picture behind a section: its slot, where it stands and how high a cover
		 *	is, its focus, and the way to take it away again. The picture is the source of
		 *	the row, which is changed in the dialog of the sources
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Element}
		 */
		_backgroundField : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const has = section.background !== null && section.background !== undefined;
			const wrap = builder._el( 'div', 'builder-background' );
			const remove = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText('/_admin/builder/source/remove-background') );

			wrap.appendChild( builder._sourceRow( {
				kind		: 'image',
				fixed		: false,
				create	: true,
				fields	: null,
				value		: has === true ? section.background.slot : '',
				text		: null,
				section	: section,
				name		: 'background',
				seed		: Nino.content.getText('/_admin/builder/source/background'),
				caption	: Nino.content.getText('/_admin/builder/source/current-image'),
				onPick	: function( source, create ) {

					section.background = { slot : source, focus : section.background ? section.background.focus : null };

					if( create !== null )
						section.background.create = create;

					builder._changed();
					// The focus and the button that takes the picture away are there for a picture
					builder._refreshTab('background');
				},
			} ) );

			wrap.appendChild( builder._line( [
				builder._setting( section.settings, { key : 'imagePos', type : 'select', label : '/_admin/builder/section/image-pos', group : 'image-pos', values : builder.IMAGE_POS } ),
				builder._setting( section.settings, { key : 'cover', type : 'int', label : '/_admin/builder/section/cover', hint : '/_admin/builder/hint/cover', min : 0, max : 100 } ),
			] ) );

			wrap.appendChild( builder._focusField( has === true ? section.background.focus : null, has, function( focus ) {
				section.background.focus = focus;
				builder._changed();
			} ) );

			remove.type = 'button';
			remove.disabled = has === false;
			remove.addEventListener( 'click', function() {
				section.background = null;
				builder._changed();
				builder._refreshTab('background');
			} );
			wrap.appendChild( remove );

			return wrap;
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

				builder._sel = [ path === null ? doc.model.blocks.length - 1 : path[0] ];
				builder._closeDialog();
				builder._changed();
			} );
		},

		/**
		 *	What a section that was read again has lost: the instructions to make the
		 *	keys and slots that are new, and where the section was renamed from. The
		 *	markup carries neither, so they are put back on what stands where it
		 *	stood - the same source, the same id
		 *
		 *	@param		{Object}		old								The section as it was
		 *	@param		{Object}		[again]						The block the server read at its place
		 *
		 *	@return		void
		 */
		_carry : function( old, again ) {

			if( again === undefined || again.kind !== 'section' )
				return;

			const made = {};

			old.cols.forEach( function( col ) {
				col.components.forEach( function( component ) {
					if( component.create !== undefined && made[component.source] === undefined )
						made[component.source] = component.create;
				} );
			} );

			again.cols.forEach( function( col ) {
				col.components.forEach( function( component ) {
					if( made[component.source] !== undefined && component.create === undefined )
						component.create = Nino.admin.builder._clone( made[component.source] );
				} );
			} );

			if( old.background && old.background.create !== undefined && again.background && again.background.slot === old.background.slot )
				again.background.create = Nino.admin.builder._clone( old.background.create );

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

					doc.model = builder._normalise( response.model );
					doc.hash = response.hash;
					doc.saved = JSON.stringify( doc.model );
					builder._fresh = {};
					builder._problems = [];

					if( builder._get( doc.model, builder._sel ) === null )
						builder._sel = [];

					builder._repaint();
					builder._status.saved();
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

				builder._status.fail( builder._errorText( status, response, '/_admin/common/error/save' ) );
				report( false );
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
		when the page is closed. A shell without the registry is simply not asking	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.register( 'builder', {
			isDirty	: Nino.admin.builder.isDirty,
			save		: function( done ) { Nino.admin.builder._save( done ) },
			discard	: Nino.admin.builder.discard,
			bar			: function() { return dc.getElementById('builder-bar') },
		} );

	Nino.events.bindCallback( 'ready', Nino.admin.builder.init );

})(window, document, document.documentElement, document.body);
