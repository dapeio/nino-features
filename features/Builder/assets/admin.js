/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Builder					The panel of the Builder feature: the page templates of
 *													the project in a list, and one of them in an editor of
 *													two columns - the tree of its sections, columns and
 *													components on the left, a static preview of it on the
 *													right. What a node has as settings opens in a dialog,
 *													from the small button on its row in the tree and on its
 *													frame in the preview; the tree is the only place that
 *													moves anything, the preview selects.
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
		// section, [b, c] a column, [b, c, k] a component, [b, c, 'x'] the stack
		_sel			: [],
		// The viewport the preview shows - s, m or l - and the rows of the tree
		// that are folded, by the path they stand for
		_viewport	: 'l',
		_folded		: {},
		// What cut took out of the tree and has not put down: { node }
		_clip			: null,
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
		// The path the row being dragged stands for, and the sources the last tree was
		// drawn with that mean nothing where they stand
		_drag			: null,
		_redPaths	: [],

		// The values of Nino.css the forms offer, by the setting they are for (see
		// Reader). A '' is the setting off
		COLORS		: [ '', 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ],
		BORDERS		: [ '', '1', '2', '3', 'primary' ],
		ROWS			: [ '', 'narrow', 'wide' ],
		ROW_ALIGN	: [ '', 'center', 'middle', 'bottom' ],
		WIDTHS		: [ '', 'fullwidth', 'fullheight' ],
		IMAGES		: [ '', 'cover', 'parallax' ],
		IMAGE_POS	: [ '', 'top', 'center', 'bottom' ],
		TEXTS			: [ '', 'left', 'center', 'right' ],
		SPACES		: [ '', '0', '1', '2', '3', '4', '5', '6' ],
		EFFECTS		: [ 'blur-soft', 'blur-medium', 'blur-hard', 'flip-soft', 'flip-medium', 'flip-hard', 'slide-left-soft', 'slide-left-medium', 'slide-left-hard', 'slide-right-soft', 'slide-right-medium', 'slide-right-hard', 'zoom-soft', 'zoom-medium', 'zoom-hard', 'zoom-out-soft', 'zoom-out-medium', 'zoom-out-hard' ],
		SPEEDS		: [ '', 'fast', 'medium', 'slow' ],
		MODES			: [ '', 'repeat', 'visible', 'visible-once' ],
		COL_WIDTHS: [ '25', '33', '50', '66', '75', '100' ],
		STACK_ALIGN: [ '', 'start', 'center', 'end' ],
		FOCUS			: [ '', '1', '2', '3', '4', '5', '6', '7', '8', '9' ],
		VIEWPORTS	: [ 's', 'm', 'l' ],

		// How many visible characters the editor of an [html] component keeps: the
		// editor trims to it after every edit, so it is a limit and never none
		CONTENT_MAX	: 100000,

		// What a text key and a section id are made of: lower-case words of letters
		// and digits joined by hyphens - the grammar \Nino\Text::isGrammarKey() holds
		// a key to, which the server decides again
		SEGMENT		: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
		KEY				: /^\/(?:template|project|feature|module)(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){3}$/,
		SLOT			: /^\/[a-z][a-z0-9_-]*(?:\/[a-z][a-z0-9_-]*)*$/,
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
		 *	The path of a node of the model, found by the node itself
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		node
		 *
		 *	@return		{Array|null}
		 */
		_locate : function( model, node ) {

			for( let b = 0; b < model.blocks.length; b++ ) {

				const block = model.blocks[b];

				if( block === node )
					return [ b ];

				for( let c = 0; block.kind === 'section' && c < block.cols.length; c++ ) {

					const col = block.cols[c];

					if( col === node )
						return [ b, c ];

					if( col.stack === node && node !== null )
						return [ b, c, 'x' ];

					const k = col.components.indexOf( node );

					if( k !== -1 )
						return [ b, c, k ];
				}
			}

			return null;
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
		 *	nothing is moved - a component goes to another column by cut and
		 *	paste - and neither does it go to another parent
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
		 *	@param		{Array}			path							A section, a column or a component; a stack is dropped
		 *															by choosing Static in its column's form
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
		 *	Put a cut node down: a section after a section (or first, on the
		 *	template), a column at the end of a section or after a column, a
		 *	component at the end of a column or after a component. What was cut
		 *	leaves its place in the same step
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		clip							{ node }, what cut kept
		 *	@param		{Array}			target						The row it is put down on
		 *	@param		{boolean}		[dry]							Only ask whether it can be put down there
		 *
		 *	@return		{Array|null}							The path of the node now, null where it cannot be put down there
		 */
		_paste : function( model, clip, target, dry ) {

			const builder = Nino.admin.builder;
			const from = clip === null ? null : builder._locate( model, clip.node );

			if( from === null || from[from.length - 1] === 'x' )
				return null;

			let list = null;
			let index = 0;

			if( from.length === 1 ) {
				list = model.blocks;
				index = target.length === 0 ? 0 : target[0] + 1;
			} else if( from.length === 2 && target.length >= 1 && model.blocks[target[0]].kind === 'section' ) {
				list = model.blocks[target[0]].cols;
				index = target.length === 1 ? list.length : target[1] + 1;
			} else if( from.length === 3 && target.length >= 2 && model.blocks[target[0]].kind === 'section' ) {
				list = model.blocks[target[0]].cols[target[1]].components;
				index = target.length === 2 || target[2] === 'x' ? list.length : target[2] + 1;
			}

			if( list === null || dry === true )
				return list === null ? null : target;

			const place = builder._place( model, from );

			// What leaves the list in front of the place shifts the place by one
			if( place.list === list && place.index < index )
				index--;

			place.list.splice( place.index, 1 );
			list.splice( index, 0, clip.node );

			return builder._locate( model, clip.node );
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
		 *	A section as the editor makes a new one: a name that is free, one
		 *	column over the whole width, nothing in it
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
				settings	: { row : '', rowAlign : '', rowCustom : '', width : '', color : '', border : '', image : '', dim : false, imagePos : '', cover : null, mt : '', mb : '', pt : '', pb : '', text : '', vpa : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '', custom : '' },
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
		 *	placeholders with the image the registry names, and a stack as cells in
		 *	the stack's own widths for that viewport. A viewport changes nothing but
		 *	widths and visibility
		 *
		 *	@param		{Object}		model
		 *	@param		{Object}		registry
		 *	@param		{string}		viewport					s, m or l
		 *
		 *	@return		{Array}										One entry per block
		 */
		_preview : function( model, registry, viewport ) {

			const builder = Nino.admin.builder;
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
						const schema = stack === null ? {} : ( registry.stacks || {} )[stack.name] || {};
						const widths = stack === null ? [] : String( stack.attributes.cols || '100 50 33' ).split( /\s+/ );
						const at = builder.VIEWPORTS.indexOf( viewport );
						const cell = parseInt( widths[at] ?? widths[widths.length - 1] ?? '100', 10 ) || 100;

						return {
							path			: [ b, c ],
							width			: parseInt( col.width[viewport], 10 ) || 100,
							hidden		: col.hidden[viewport] === true,
							stack			: stack === null ? null : { path : [ b, c, 'x' ], label : schema.label || stack.name, source : stack.source, cell : cell, grid : schema.grid !== false, image : 'cells' },
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
			builder._clip = null;
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
					{ key : 'name', label : Nino.content.getText('/_admin/builder/label/name'), type : 'string' },
					{ key : 'file', label : Nino.content.getText('/_admin/builder/label/file'), type : 'string' },
					{ key : 'header', label : Nino.content.getText('/_admin/builder/label/header'), type : 'string' },
					{ key : 'footer', label : Nino.content.getText('/_admin/builder/label/footer'), type : 'string' },
					{ key : 'sections', label : Nino.content.getText('/_admin/builder/label/sections'), type : 'integer' },
					{ key : 'foreign', label : Nino.content.getText('/_admin/builder/label/foreign'), type : 'integer' },
					{ key : 'readable', label : Nino.content.getText('/_admin/builder/label/readable'), type : 'string', render : function( value ) {
						return value === 'yes' ? Nino.content.getText('/_admin/builder/label/yes') : Nino.content.getText('/_admin/builder/label/no');
					} },
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
						foreign	: entry.foreign,
						readable: entry.readable === true ? 'yes' : 'no',
						usedBy	: builder._addresses( entry.usedBy ).join(', '),
						// A cell the table does not take for empty: the buttons stand in it
						actions	: entry.file,
						editable: entry.editable === true,
					};
				} ),
				labels	: { search : Nino.content.getText('/_admin/builder/label/search'), empty : Nino.content.getText('/_admin/builder/empty/headline'), noMatch : Nino.content.getText('/_admin/builder/label/no-match') },
				onRowClick : function( row ) { if( row.editable === true ) builder._visit( row.file ) },
			} );

			mount.appendChild( Nino.adminUi.listActions( [ create ] ) );
		},

		/**
		 *	The buttons of one row: open, delete
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

			const remove = builder._el( 'button', 'nino-admin-btn-danger', Nino.content.getText('/_admin/builder/label/delete') );
			remove.type = 'button';
			remove.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._confirmDelete( row.file ) } );

			cell.appendChild( open );
			cell.appendChild( remove );

			return cell;
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
		 *	Wire what the template draws once: the viewport buttons, the bar, the
		 *	back link, the dialog's own close and the menu's way out
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
			builder.VIEWPORTS.forEach( function( viewport ) { buttons[viewport] = dc.getElementById( 'builder-viewport-'+ viewport ) } );
			Nino.adminUi.buttonRow( buttons, builder._viewport, function( viewport ) {
				builder._viewport = viewport;
				builder._renderPreview();
			} );

			builder._status = Nino.adminUi.status( dc.getElementById('builder-status') );

			dc.getElementById('builder-save').addEventListener( 'click', function() { builder._save() } );
			dc.getElementById('builder-source').addEventListener( 'click', builder._showSource );
			dc.getElementById('builder-add-section').addEventListener( 'click', function() { builder._addSection() } );
			dc.getElementById('builder-add-html').addEventListener( 'click', function() { builder._editBlock( null ) } );
			dc.getElementById('builder-template-settings').addEventListener( 'click', function() { builder._select( [] ); builder._openSettings( [] ) } );
			dc.getElementById('builder-dialog-close').addEventListener( 'click', builder._closeDialog );
			dc.getElementById('builder-dialog').addEventListener( 'close', builder._dialogClosed );

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
				builder._clip = null;
				builder._fresh = {};
				builder._problems = [];
				builder._folded = {};
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
		 *	The tree and the preview from the model, and the line in the bar
		 *
		 *	@return		void
		 */
		_repaint : function() {

			const builder = Nino.admin.builder;

			if( builder._doc === null )
				return;

			builder._renderTree();
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
		 *	What a refused save said, at the blocks it names: the rows and the frames
		 *	of those blocks carry the sentence, and the sentences no block owns are
		 *	listed over the workspace
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

		// -------------------------------------------------------------- The tree

		/**
		 *	What a row of the tree says: its kind, its words, whether it opens
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Object}									{ kind, label, sub, children, badge, warning }
		 */
		_describe : function( path ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;
			const node = builder._get( model, path );
			const kind = builder._kind( model, path );
			const registry = builder._registry;

			if( kind === 'template' )
				return { kind : kind, label : model.name === '' ? builder._doc.file : model.name, sub : builder._doc.file+ '.tpl', children : true, badge : '', warning : '' };

			if( kind === 'section' )
				return { kind : kind, label : node.id, sub : node.settings.color, children : true, badge : '', warning : '' };

			if( kind === 'html' ) {
				const id = /\bid="([^"]*)"/.exec( node.source );
				return { kind : kind, label : id === null ? '' : id[1], sub : '', children : false, badge : '', warning : node.reason === null || node.reason === undefined ? '' : builder._reasonText( node.reason ) };
			}

			if( kind === 'col' )
				return { kind : kind, label : Nino.adminUi.format( Nino.content.getText('/_admin/builder/tree/col-widths'), builder.VIEWPORTS.map( function( viewport ) { return node.width[viewport] } ).join('/') ), sub : '', children : true, badge : '', warning : '' };

			if( kind === 'stack' ) {
				const schema = ( registry.stacks || {} )[node.name] || {};
				return { kind : kind, label : schema.label || node.name, sub : node.source, children : false, badge : '', warning : '' };
			}

			const schema = ( registry.components || {} )[node.name] || {};
			const source = typeof node.text === 'string' && node.source === '' ? '“'+ node.text+ '”' : node.source.split('/').pop();

			return { kind : kind, label : schema.label || node.name, sub : source, children : false, badge : '', warning : '' };
		},

		/**
		 *	The tree, from the model
		 *
		 *	@return		void
		 */
		_renderTree : function() {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-tree');
			const model = builder._doc.model;
			const red = builder._red( model, builder._registry );

			builder._redPaths = red;
			mount.innerHTML = '';

			const walk = function( path, into ) {

				const row = builder._row( path );
				into.appendChild( row );

				const open = builder._folded[path.join('.')] !== true;
				const kind = builder._kind( model, path );

				if( open === false )
					return;

				const children = builder._el( 'div', 'builder-children' );
				children.setAttribute( 'role', 'group' );

				if( kind === 'template' )
					model.blocks.forEach( function( block, b ) { walk( [ b ], children ) } );
				else if( kind === 'section' )
					model.blocks[path[0]].cols.forEach( function( col, c ) { walk( [ path[0], c ], children ) } );
				else if( kind === 'col' ) {

					const col = model.blocks[path[0]].cols[path[1]];

					if( col.stack !== null && col.stack !== undefined ) {
						const stack = builder._row( [ path[0], path[1], 'x' ] );
						children.appendChild( stack );
						const inner = builder._el( 'div', 'builder-children' );
						col.components.forEach( function( component, k ) { inner.appendChild( builder._row( [ path[0], path[1], k ] ) ) } );
						inner.appendChild( builder._addRow( 'component', [ path[0], path[1] ] ) );
						children.appendChild( inner );
					} else {
						col.components.forEach( function( component, k ) { children.appendChild( builder._row( [ path[0], path[1], k ] ) ) } );
						children.appendChild( builder._addRow( 'component', [ path[0], path[1] ] ) );
					}
				}

				if( kind === 'section' )
					children.appendChild( builder._addRow( 'col', path ) );

				if( children.children.length > 0 )
					into.appendChild( children );
			};

			walk( [], mount );
			builder._paintSelection( false );
		},

		/**
		 *	One row of the tree
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Element}
		 */
		_row : function( path ) {

			const builder = Nino.admin.builder;
			const info = builder._describe( path );
			const row = builder._fragment( 'row' );
			const mutable = builder._mutable();
			const key = path.join('.');
			const red = ( builder._redPaths || [] ).find( function( entry ) { return builder._same( entry.path, path ) } );

			row.dataset.path = key;
			row.dataset.kind = info.kind;
			row.setAttribute( 'aria-level', String( path.length + 1 ) );
			row.classList.toggle( 'is-clipped', builder._clip !== null && builder._get( builder._doc.model, path ) === builder._clip.node );

			const toggle = builder._one( row, '.builder-row-toggle' );
			const folded = builder._folded[key] === true;

			toggle.hidden = info.children === false || info.kind === 'template';
			toggle.setAttribute( 'aria-expanded', folded === true ? 'false' : 'true' );
			toggle.addEventListener( 'click', function() { builder._folded[key] = folded === false; builder._renderTree() } );

			builder._one( row, '.builder-row-kind' ).textContent = Nino.content.getText( '/_admin/builder/tree/'+ info.kind );
			builder._one( row, '.builder-row-label' ).textContent = info.label;
			builder._one( row, '.builder-row-sub' ).textContent = info.sub;

			const badge = builder._one( row, '.builder-row-badge' );
			badge.textContent = info.badge;
			badge.hidden = info.badge === '';

			const warning = builder._one( row, '.builder-row-warning' );
			warning.hidden = info.warning === '';
			warning.title = info.warning;

			if( red !== undefined ) {
				row.classList.add('is-red');
				row.title = Nino.content.getText( '/_admin/builder/tree/red-'+ red.why );
			}

			const main = builder._one( row, '.builder-row-main' );
			main.addEventListener( 'click', function() { builder._select( path ) } );
			main.addEventListener( 'dblclick', function() { builder._openSettings( path ) } );
			main.addEventListener( 'keydown', function( ev ) {
				if( ev.altKey === true && ( ev.key === 'ArrowUp' || ev.key === 'ArrowDown' ) ) {
					ev.preventDefault();
					builder._stepRow( path, ev.key === 'ArrowUp' ? -1 : 1 );
				}
			} );

			const settings = builder._one( row, '.builder-row-settings' );
			settings.addEventListener( 'click', function() { builder._select( path ); builder._openSettings( path ) } );

			const menu = builder._one( row, '.builder-row-menu' );
			menu.hidden = info.kind === 'template' || info.kind === 'stack';
			menu.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._select( path ); builder._openMenu( path, menu ) } );

			// The handle drags a row inside its own level and its own parent
			const movable = mutable === true && info.kind !== 'template' && info.kind !== 'stack';
			builder._one( row, '.builder-handle' ).hidden = movable === false;
			row.draggable = movable;

			if( movable === true )
				builder._dragRow( row, path );

			return row;
		},

		/**
		 *	The drag and drop of one row
		 *
		 *	@param		{Element}		row
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_dragRow : function( row, path ) {

			const builder = Nino.admin.builder;
			const fits = function() {
				return builder._drag !== null && builder._drag.length === path.length && builder._same( builder._drag.slice( 0, -1 ), path.slice( 0, -1 ) ) === true && builder._same( builder._drag, path ) === false;
			};

			row.addEventListener( 'dragstart', function( ev ) {
				builder._drag = path;
				if( ev.dataTransfer ) {
					ev.dataTransfer.effectAllowed = 'move';
					ev.dataTransfer.setData( 'text/plain', path.join('.') );
				}
			} );
			row.addEventListener( 'dragover', function( ev ) {
				if( fits() === false )
					return;
				ev.preventDefault();
				row.classList.add('is-drop');
			} );
			row.addEventListener( 'dragleave', function() { row.classList.remove('is-drop') } );
			row.addEventListener( 'drop', function( ev ) {
				ev.preventDefault();
				row.classList.remove('is-drop');
				if( fits() === true && builder._move( builder._doc.model, builder._drag, path ) === true ) {
					builder._sel = path;
					builder._changed();
				}
				builder._drag = null;
			} );
			row.addEventListener( 'dragend', function() { builder._drag = null; row.classList.remove('is-drop') } );
		},

		/**
		 *	@param		{Array}			path
		 *	@param		{number}		delta
		 *
		 *	@return		void
		 */
		_stepRow : function( path, delta ) {

			const builder = Nino.admin.builder;

			if( builder._mutable() === false )
				return;

			const to = builder._step( builder._doc.model, path, delta );

			if( to === null )
				return;

			builder._sel = to;
			builder._changed();

			const row = dc.querySelector( '#builder-tree [data-path="'+ to.join('.')+ '"] .builder-row-main' );

			if( row !== null )
				row.focus();
		},

		/**
		 *	The row at the end of a list that adds to it
		 *
		 *	@param		{string}		what					section, col or component
		 *	@param		{Array}			path					The node the new one goes into
		 *
		 *	@return		{Element}
		 */
		_addRow : function( what, path ) {

			const builder = Nino.admin.builder;
			const row = builder._el( 'div', 'builder-add' );
			const button = builder._el( 'button', 'builder-add-button', '+ '+ Nino.content.getText( '/_admin/builder/tree/'+ what ) );

			button.type = 'button';
			button.disabled = builder._mutable() === false;
			button.addEventListener( 'click', function( ev ) {
				ev.stopPropagation();
				if( what === 'col' )
					builder._addCol( path );
				else
					builder._pickComponent( path, button );
			} );

			row.appendChild( button );

			return row;
		},

		/**
		 *	Select a node: the row and the frame of the preview
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
		 *	Mark the selected row and frame, and bring them into view
		 *
		 *	@param		{boolean}		scroll
		 *
		 *	@return		void
		 */
		_paintSelection : function( scroll ) {

			const builder = Nino.admin.builder;
			const key = builder._sel.join('.');

			dc.querySelectorAll('#builder-tree [data-path], #builder-preview [data-path]').forEach( function( el ) {

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
		 *	and never from content. Click selects, double click opens the form
		 *
		 *	@return		void
		 */
		_renderPreview : function() {

			const builder = Nino.admin.builder;
			const mount = dc.getElementById('builder-preview');
			const plan = builder._preview( builder._doc.model, builder._registry, builder._viewport );

			mount.innerHTML = '';
			mount.dataset.viewport = builder._viewport;

			plan.forEach( function( block ) {

				const frame = builder._fragment( 'frame' );
				const body = builder._one( frame, '.builder-frame-body' );

				frame.dataset.path = block.path.join('.');
				frame.classList.add( block.kind === 'section' ? 'builder-color-'+ ( block.color === '' ? 'plain' : block.color ) : 'is-html' );

				builder._one( frame, '.builder-frame-name' ).textContent = block.kind === 'section' ? block.id : 'HTML+';
				builder._one( frame, '.builder-frame-bg' ).hidden = block.background === false;
				builder._one( frame, '.builder-frame-warning' ).hidden = block.reason === null;
				builder._one( frame, '.builder-frame-warning' ).title = block.reason === null ? '' : builder._reasonText( block.reason );

				builder._pick( frame, block.path );
				builder._one( frame, '.builder-frame-settings' ).addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._select( block.path ); builder._openSettings( block.path ) } );

				if( block.kind === 'html' )
					body.appendChild( builder._placeholder( { path : block.path, label : 'HTML+', image : 'block' }, false ) );

				block.cols.forEach( function( col ) { body.appendChild( builder._previewCol( col ) ) } );

				mount.appendChild( frame );
			} );

			builder._paintSelection( false );
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
		 *	One column of the preview, at the width and the visibility of the
		 *	viewport
		 *
		 *	@param		{Object}		col						An entry of _preview()
		 *
		 *	@return		{Element}
		 */
		_previewCol : function( col ) {

			const builder = Nino.admin.builder;
			const frame = builder._fragment( 'col' );
			const body = builder._one( frame, '.builder-col-body' );

			frame.dataset.path = col.path.join('.');
			frame.style.setProperty( '--builder-w', String( col.width ) );
			frame.classList.toggle( 'is-hidden', col.hidden );
			builder._one( frame, '.builder-col-name' ).textContent = col.width+ '%';
			builder._one( frame, '.builder-col-hidden' ).hidden = col.hidden === false;
			builder._pick( frame, col.path );
			builder._one( frame, '.builder-col-settings' ).addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._select( col.path ); builder._openSettings( col.path ) } );

			if( col.stack !== null ) {

				const stack = builder._fragment( 'stack' );
				const cells = builder._one( stack, '.builder-cells' );
				const perRow = Math.max( 1, Math.min( 4, Math.floor( 100 / col.stack.cell ) ) );

				stack.dataset.path = col.stack.path.join('.');
				builder._one( stack, '.builder-stack-name' ).textContent = col.stack.label;
				builder._one( stack, '.builder-stack-source' ).textContent = col.stack.source;
				builder._pick( stack, col.stack.path );
				builder._one( stack, '.builder-stack-settings' ).addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._select( col.stack.path ); builder._openSettings( col.stack.path ) } );
				cells.style.setProperty( '--builder-cw', String( col.stack.cell ) );

				for( let n = 0; n < Math.min( 6, perRow * 2 ); n++ ) {
					const cell = builder._el( 'div', 'builder-cell' );
					col.components.forEach( function( component ) { cell.appendChild( builder._placeholder( component, false ) ) } );
					cells.appendChild( cell );
				}

				body.appendChild( stack );
				return frame;
			}

			col.components.forEach( function( component ) { body.appendChild( builder._placeholder( component, true ) ) } );

			return frame;
		},

		/**
		 *	A placeholder: the image the registry names for a component, and its label
		 *
		 *	@param		{Object}		component			{ path, label, image }
		 *	@param		{boolean}		withSettings	Whether it has a button of its own
		 *
		 *	@return		{Element}
		 */
		_placeholder : function( component, withSettings ) {

			const builder = Nino.admin.builder;
			const ph = builder._fragment( 'ph' );

			ph.dataset.path = component.path.join('.');
			builder._one( ph, '.builder-ph-label' ).textContent = component.label;
			builder._one( ph, 'use' ).setAttribute( 'href', '#builder-ph-'+ component.image );
			builder._pick( ph, component.path );

			const settings = builder._one( ph, '.builder-ph-settings' );
			settings.hidden = withSettings === false;
			settings.addEventListener( 'click', function( ev ) { ev.stopPropagation(); builder._select( component.path ); builder._openSettings( component.path ) } );

			return ph;
		},

		// ------------------------------------------------------ Menu and changes

		/**
		 *	The menu of a row, under its button
		 *
		 *	@param		{Array}			path
		 *	@param		{Element}		anchor
		 *
		 *	@return		void
		 */
		_openMenu : function( path, anchor ) {

			const builder = Nino.admin.builder;
			const model = builder._doc.model;
			const kind = builder._kind( model, path );
			const place = builder._place( model, path );
			const mutable = builder._mutable();
			const items = [];

			items.push( { label : Nino.content.getText( kind === 'html' ? '/_admin/builder/menu/edit-html' : '/_admin/builder/menu/settings' ), run : function() { builder._openSettings( path ) } } );

			if( kind === 'section' )
				items.push( { label : Nino.content.getText('/_admin/builder/menu/edit-html'), run : function() { builder._editBlock( path ) }, disabled : mutable === false } );

			items.push( { label : Nino.content.getText('/_admin/builder/menu/cut'), run : function() { builder._clip = { node : builder._get( model, path ) }; builder._renderTree() }, disabled : mutable === false } );
			items.push( { label : Nino.content.getText('/_admin/builder/menu/paste'), run : function() { builder._pasteAt( path ) }, disabled : mutable === false || builder._canPaste( path ) === false } );
			items.push( { label : Nino.content.getText('/_admin/builder/menu/duplicate'), run : function() { builder._duplicateAt( path ) }, disabled : mutable === false } );
			items.push( { label : Nino.content.getText('/_admin/common/label/moveup'), run : function() { builder._stepRow( path, -1 ) }, disabled : mutable === false || place === null || place.index === 0 } );
			items.push( { label : Nino.content.getText('/_admin/common/label/movedown'), run : function() { builder._stepRow( path, 1 ) }, disabled : mutable === false || place === null || place.index >= place.list.length - 1 } );
			items.push( { label : Nino.content.getText('/_admin/common/label/delete'), run : function() { builder._delete( path ) }, disabled : mutable === false, danger : true } );

			builder._showMenu( items, anchor );
		},

		/**
		 *	Show a list of choices under an element, in the one menu the template has
		 *
		 *	@param		{Array}			items					[ { label, run, disabled, danger } ]
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

				const button = builder._el( 'button', item.danger === true ? 'builder-menu-item is-danger' : 'builder-menu-item', item.label );

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
		 *	@param		{Array}			path
		 *
		 *	@return		{boolean}									Whether what was cut can be put down on the row
		 */
		_canPaste : function( path ) {

			const builder = Nino.admin.builder;

			return builder._clip !== null && builder._paste( builder._doc.model, builder._clip, path, true ) !== null;
		},

		/**
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_pasteAt : function( path ) {

			const builder = Nino.admin.builder;
			const to = builder._paste( builder._doc.model, builder._clip, path );

			if( to === null )
				return;

			builder._clip = null;
			builder._sel = to;
			builder._changed();
		},

		/**
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_duplicateAt : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const to = builder._duplicate( doc.model, builder._registry, doc.file, path, builder._valueOf );

			if( to === null )
				return;

			// The keys and slots of a copy of a section are new ones, under the id it has now
			if( path.length === 1 && doc.model.blocks[to[0]].kind === 'section' )
				builder._fresh[doc.model.blocks[to[0]].id] = true;

			builder._sel = to;
			builder._changed();
		},

		/**
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_delete : function( path ) {

			const builder = Nino.admin.builder;
			const node = builder._get( builder._doc.model, path );

			if( builder._remove( builder._doc.model, path ) === false )
				return;

			if( builder._clip !== null && builder._clip.node === node )
				builder._clip = null;

			builder._sel = path.slice( 0, -1 );
			builder._changed();
		},

		/**
		 *	A new section at the end of the page
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
		},

		/**
		 *	@param		{Array}			path					The section
		 *
		 *	@return		void
		 */
		_addCol : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];

			section.cols.push( builder._newCol() );
			builder._sel = [ path[0], section.cols.length - 1 ];
			builder._changed();
		},

		/**
		 *	Choose the component a column gets: the components of the registry, the
		 *	ones a stack takes where the column has one
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
		 *	@param		{Array}			path					The column
		 *	@param		{string}		name					The component
		 *
		 *	@return		void
		 */
		_addComponent : function( path, name ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const col = doc.model.blocks[path[0]].cols[path[1]];

			col.components.push( builder._newComponent( doc.model, builder._registry, doc.file, path, name, builder._keys ) );
			builder._sel = [ path[0], path[1], col.components.length - 1 ];
			builder._changed();
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

			builder._closeDialog();
			builder._dialogOptions = options;

			dialog.classList.toggle( 'is-wide', options.wide === true );
			dc.getElementById('builder-dialog-title').textContent = options.title;
			content.innerHTML = '';
			strip.innerHTML = '';
			actions.innerHTML = '';
			problems.textContent = '';
			problems.hidden = true;

			const problem = function( text ) {
				problems.textContent = text;
				problems.hidden = text === '';
			};

			if( Array.isArray( options.tabs ) === true && options.tabs.length > 0 ) {

				const buttons = {};
				const panes = {};

				options.tabs.forEach( function( tab ) {

					const button = builder._el( 'button', 'builder-tab', tab.label );
					const pane = builder._el( 'div', 'builder-tabpane' );

					button.type = 'button';
					button.setAttribute( 'role', 'tab' );
					buttons[tab.id] = button;
					panes[tab.id] = pane;
					strip.appendChild( button );
					content.appendChild( pane );
					tab.build( pane );
				} );

				const show = function( id ) {
					Object.keys( panes ).forEach( function( key ) { panes[key].hidden = key !== id } );
					builder._dialogTab = id;
				};

				const start = options.tab !== undefined && panes[options.tab] !== undefined ? options.tab : options.tabs[0].id;

				Nino.adminUi.buttonRow( buttons, start, show, 'aria-selected' );
				show( start );
				strip.hidden = false;
				builder._dialogPanes = panes;
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

		// The options of the dialog that is open, the tab it shows, and the way it says what is wrong
		_dialogOptions : null,
		_dialogTab : '',
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
		 *	Close the dialog, and tell what it was made for. A close the script asks
		 *	for makes the dialog fire a close event of its own a task later, which
		 *	has to be let go by: a dialog may have been opened since
		 *
		 *	@return		void
		 */
		_closeDialog : function() {

			const builder = Nino.admin.builder;
			const dialog = dc.getElementById('builder-dialog');
			const options = builder._dialogOptions;

			builder._dialogOptions = null;

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
		 *	@param		{Object}		[more]						{ multiline, onCommit, list } - onCommit on the change of the value, list an id of a <datalist>
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
		 *	@param		{Object}		field							{ key, type, label, hint, group, values, min, max }
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
				return builder._colDialog( path.slice( 0, 2 ), 'stack' );

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

			sentences.forEach( function( sentence ) { pane.appendChild( builder._el( 'p', 'nino-admin-error', sentence ) ) } );
		},

		/**
		 *	The form of the template: its name and its frames. The file and its slug
		 *	are text, not fields - the slug is the category of the keys
		 *
		 *	@return		void
		 */
		_templateDialog : function() {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const registry = builder._registry;

			builder._dialog( {
				title	: Nino.content.getText('/_admin/builder/tree/template'),
				build	: function( body ) {
					body.appendChild( builder._el( 'p', 'nino-admin-hint', Nino.adminUi.format( Nino.content.getText('/_admin/builder/hint/template'), doc.file+ '.tpl' ) ) );
					body.appendChild( builder._textField( Nino.content.getText('/_admin/builder/label/name'), Nino.content.getText('/_admin/builder/hint/name'), doc.model.name, function( value ) { doc.model.name = value; builder._changed() } ) );
					body.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/header'), registry.headers, doc.model.header, function( value ) { doc.model.header = value; builder._changed() } ) );
					body.appendChild( builder._frameField( Nino.content.getText('/_admin/builder/label/footer'), registry.footers, doc.model.footer, function( value ) { doc.model.footer = value; builder._changed() } ) );
				},
			} );
		},

		/**
		 *	The fields of a section's settings, by the tab they stand in: key, type,
		 *	the words of its label and hint, and the values it takes
		 *
		 *	@return		{Object}
		 */
		_sectionFields : function() {

			const builder = Nino.admin.builder;

			return {
				general		: [
					{ key : 'row', type : 'select', label : '/_admin/builder/section/row', hint : '/_admin/builder/hint/row', group : 'row', values : builder.ROWS },
					{ key : 'width', type : 'select', label : '/_admin/builder/section/width', group : 'width', values : builder.WIDTHS },
					{ key : 'color', type : 'select', label : '/_admin/builder/section/color', group : 'color', values : builder.COLORS },
					{ key : 'border', type : 'select', label : '/_admin/builder/section/border', group : 'border', values : builder.BORDERS },
				],
				background	: [
					{ key : 'image', type : 'select', label : '/_admin/builder/section/image', hint : '/_admin/builder/hint/image', group : 'image', values : builder.IMAGES },
					{ key : 'dim', type : 'bool', label : '/_admin/builder/section/dim' },
					{ key : 'imagePos', type : 'select', label : '/_admin/builder/section/image-pos', group : 'image-pos', values : builder.IMAGE_POS },
					{ key : 'cover', type : 'int', label : '/_admin/builder/section/cover', hint : '/_admin/builder/hint/cover', min : 0, max : 100 },
				],
				spacing		: [
					{ key : 'mt', type : 'select', label : '/_admin/builder/section/mt', group : 'space', values : builder.SPACES },
					{ key : 'mb', type : 'select', label : '/_admin/builder/section/mb', group : 'space', values : builder.SPACES },
					{ key : 'pt', type : 'select', label : '/_admin/builder/section/pt', group : 'space', values : builder.SPACES },
					{ key : 'pb', type : 'select', label : '/_admin/builder/section/pb', group : 'space', values : builder.SPACES },
					{ key : 'text', type : 'select', label : '/_admin/builder/section/text', group : 'text', values : builder.TEXTS },
					{ key : 'rowAlign', type : 'select', label : '/_admin/builder/section/row-align', group : 'row-align', values : builder.ROW_ALIGN },
				],
			};
		},

		/**
		 *	The form of a section, in tabs: its id, the row, the colour and the
		 *	picture behind it, the spacing, the animation, and the classes of its
		 *	own. Each field writes into the model at once
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		void
		 */
		_sectionDialog : function( path ) {

			const builder = Nino.admin.builder;
			const section = builder._doc.model.blocks[path[0]];
			const fields = builder._sectionFields();
			const group = function( name ) {
				return function( pane ) {
					builder._blamed( pane, path[0] );
					fields[name].forEach( function( field ) { pane.appendChild( builder._setting( section.settings, field ) ) } );
				};
			};

			builder._dialog( {
				title	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/title'), section.id ),
				tabs	: [
					{ id : 'general', label : Nino.content.getText('/_admin/builder/tab/general'), build : function( pane ) {
						builder._blamed( pane, path[0] );
						pane.appendChild( builder._idField( path ) );
						fields.general.forEach( function( field ) { pane.appendChild( builder._setting( section.settings, field ) ) } );
					} },
					{ id : 'background', label : Nino.content.getText('/_admin/builder/tab/background'), build : function( pane ) {
						fields.background.forEach( function( field ) { pane.appendChild( builder._setting( section.settings, field ) ) } );
						pane.appendChild( builder._backgroundField( path ) );
					} },
					{ id : 'spacing', label : Nino.content.getText('/_admin/builder/tab/spacing'), build : group( 'spacing' ) },
					{ id : 'animation', label : Nino.content.getText('/_admin/builder/tab/animation'), build : function( pane ) { builder._animationFields( pane, section.settings ) } },
					{ id : 'custom', label : Nino.content.getText('/_admin/builder/tab/custom'), build : function( pane ) {
						pane.appendChild( builder._setting( section.settings, { key : 'custom', type : 'string', label : '/_admin/builder/section/custom', hint : '/_admin/builder/hint/custom' } ) );
						pane.appendChild( builder._setting( section.settings, { key : 'rowCustom', type : 'string', label : '/_admin/builder/section/row-custom', hint : '/_admin/builder/hint/custom' } ) );
					} },
				],
			} );
		},

		/**
		 *	The animation of a section or a column: whether, which, how fast, when
		 *	it runs and with what delay and duration. vpa is null for none, ''
		 *	for the plain one, an effect's name for the rest
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		target						settings of a section, or a column
		 *
		 *	@return		void
		 */
		_animationFields : function( pane, target ) {

			const builder = Nino.admin.builder;
			const state = target.vpa === null ? 'none' : ( target.vpa === '' ? 'plain' : target.vpa );
			const options = [ { value : 'none', label : builder._optionLabel( 'vpa', 'none' ) }, { value : 'plain', label : builder._optionLabel( 'vpa', 'plain' ) } ].concat( builder.EFFECTS.map( function( effect ) { return { value : effect, label : effect } } ) );

			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/section/vpa'), Nino.content.getText('/_admin/builder/hint/vpa'), options, state, function( value ) {
				target.vpa = value === 'none' ? null : ( value === 'plain' ? '' : value );
				builder._changed();
			} ) );

			[
				{ key : 'vpaSpeed', type : 'select', label : '/_admin/builder/section/vpa-speed', group : 'speed', values : builder.SPEEDS },
				{ key : 'vpaMode', type : 'select', label : '/_admin/builder/section/vpa-mode', group : 'mode', values : builder.MODES },
				{ key : 'vpaDelay', type : 'string', label : '/_admin/builder/section/vpa-delay', hint : '/_admin/builder/hint/time' },
				{ key : 'vpaDuration', type : 'string', label : '/_admin/builder/section/vpa-duration', hint : '/_admin/builder/hint/time' },
			].forEach( function( field ) { pane.appendChild( builder._setting( target, field ) ) } );
		},

		/**
		 *	The id of a section: a slug no other section has. A new one is renamed
		 *	in the keys of the section by the server - or here, where the section
		 *	was made in this editor and nothing is saved under its old name - and
		 *	asks first where keys are moved
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Element}
		 */
		_idField : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const section = doc.model.blocks[path[0]];
			const field = builder._textField( Nino.content.getText('/_admin/builder/section/id'), Nino.content.getText('/_admin/builder/hint/id'), section.id, function() {}, {
				onCommit : function( value, control ) {

					const id = value.trim();
					const taken = doc.model.blocks.some( function( block, at ) { return at !== path[0] && block.kind === 'section' && block.id === id } );

					if( id === section.id )
						return;

					if( builder.SEGMENT.test( id ) === false || taken === true ) {
						control.value = section.id;
						builder._dialogProblem( Nino.content.getText( taken === true ? '/_admin/builder/error/id-taken' : '/_admin/builder/error/id-slug' ) );
						return;
					}

					builder._dialogProblem( '' );

					const rename = function() {
						builder._rename( path, id );
						dc.getElementById('builder-dialog-title').textContent = Nino.adminUi.format( Nino.content.getText('/_admin/builder/section/title'), id );
					};

					if( builder._ownKeys( section ) === false ) {
						rename();
						return;
					}

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
							else
								control.value = section.id;
						},
					} );
				},
			} );

			return field;
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

		// -------------------------------------------------- Columns and stacks

		/**
		 *	The form of a column, in tabs: its width and visibility in each viewport
		 *	and what its stack does with its components, the stack it holds, the
		 *	animation, the classes of its own
		 *
		 *	@param		{Array}			path
		 *	@param		{string}		tab						The tab to start on
		 *
		 *	@return		void
		 */
		_colDialog : function( path, tab ) {

			const builder = Nino.admin.builder;
			const col = builder._doc.model.blocks[path[0]].cols[path[1]];

			builder._dialog( {
				title	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/title'), builder._doc.model.blocks[path[0]].id ),
				tab		: tab,
				tabs	: [
					{ id : 'layout', label : Nino.content.getText('/_admin/builder/tab/layout'), build : function( pane ) {

						builder._blamed( pane, path[0] );

						builder.VIEWPORTS.forEach( function( viewport ) {
							pane.appendChild( builder._selectField( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/width'), Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) ), '', builder.COL_WIDTHS.map( function( width ) { return { value : width, label : width } } ), String( col.width[viewport] ), function( value ) {
								col.width[viewport] = parseInt( value, 10 );
								builder._changed();
							} ) );
						} );

						builder.VIEWPORTS.forEach( function( viewport ) {
							pane.appendChild( builder._switchField( Nino.adminUi.format( Nino.content.getText('/_admin/builder/col/hidden'), Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) ), '', col.hidden[viewport] === true, function( checked ) {
								if( checked === true )
									col.hidden[viewport] = true;
								else
									delete col.hidden[viewport];
								builder._changed();
							} ) );
						} );

						[
							{ key : 'stackAlign', type : 'select', label : '/_admin/builder/col/stack-align', hint : '/_admin/builder/hint/stack-align', group : 'stack-align', values : builder.STACK_ALIGN },
							{ key : 'stackGap', type : 'select', label : '/_admin/builder/col/stack-gap', group : 'space', values : builder.SPACES },
							{ key : 'text', type : 'select', label : '/_admin/builder/section/text', group : 'text', values : builder.TEXTS },
						].forEach( function( field ) { pane.appendChild( builder._setting( col, field ) ) } );
					} },
					{ id : 'stack', label : Nino.content.getText('/_admin/builder/tab/stack'), build : function( pane ) { builder._stackForm( pane, path ) } },
					{ id : 'animation', label : Nino.content.getText('/_admin/builder/tab/animation'), build : function( pane ) { builder._animationFields( pane, col ) } },
					{ id : 'custom', label : Nino.content.getText('/_admin/builder/tab/custom'), build : function( pane ) {
						pane.appendChild( builder._setting( col, { key : 'custom', type : 'string', label : '/_admin/builder/section/custom', hint : '/_admin/builder/hint/custom' } ) );
					} },
				],
			} );
		},

		/**
		 *	The stack of a column: Static, or one of the registry, and the form of the
		 *	one that is chosen. The components of the column stay where they are when
		 *	it changes; what their sources mean there is checked, and a source that
		 *	means nothing is red until it is changed
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
			const options = [ { value : '', label : Nino.content.getText('/_admin/builder/stack/static') } ].concat( Object.keys( registry.stacks || {} ).map( function( name ) { return { value : name, label : registry.stacks[name].label || name } } ) );

			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/kind'), Nino.content.getText('/_admin/builder/hint/stack-kind'), options, col.stack === null || col.stack === undefined ? '' : col.stack.name, function( name ) {

				if( name === '' ) {
					col.stack = null;
				} else {

					const defaults = builder._clone( ( registry.stacks[name] || {} ).defaults || {} );
					const old = col.stack === null || col.stack === undefined ? null : col.stack;
					const types = registry.types || [];

					Object.keys( defaults ).forEach( function( key ) {
						if( old !== null && Object.prototype.hasOwnProperty.call( old.attributes, key ) === true )
							defaults[key] = old.attributes[key];
					} );

					col.stack = { name : name, source : old !== null ? old.source : ( types.length > 0 ? types[0].uri : '' ), attributes : defaults };
				}

				builder._changed();
				builder._refreshTab( 'stack' );
			} ) );

			if( col.stack === null || col.stack === undefined )
				return;

			const stack = col.stack;
			const schema = registry.stacks[stack.name] || {};
			const type = ( registry.types || [] ).find( function( candidate ) { return candidate.uri === stack.source } );
			const typeOptions = ( registry.types || [] ).map( function( candidate ) { return { value : candidate.uri, label : candidate.title+ ' ('+ candidate.uri+ ')' } } );

			if( type === undefined )
				typeOptions.unshift( { value : stack.source, label : stack.source === '' ? Nino.content.getText('/_admin/builder/label/none') : stack.source } );

			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/type'), '', typeOptions, stack.source, function( value ) {
				stack.source = value;
				builder._changed();
				builder._refreshTab( 'stack' );
			} ) );

			pane.appendChild( Nino.adminUi.notice( Nino.content.getText( type === undefined ? '/_admin/builder/stack/type-missing' : '/_admin/builder/stack/type-hint' ), { href : '#types', label : Nino.content.getText('/_admin/builder/stack/types-link') } ) );

			builder._sortField( pane, stack, type === undefined ? {} : type.fields );

			[
				{ key : 'limit', label : '/_admin/builder/stack/limit', hint : '/_admin/builder/hint/limit' },
				{ key : 'offset', label : '/_admin/builder/stack/offset' },
			].forEach( function( field ) {
				pane.appendChild( builder._numberField( Nino.content.getText( field.label ), field.hint === undefined ? '' : Nino.content.getText( field.hint ), parseInt( stack.attributes[field.key], 10 ) || 0, 0, 100000, function( value ) {
					stack.attributes[field.key] = String( value === null ? 0 : value );
					builder._changed();
				} ) );
			} );

			pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/stack/query'), Nino.content.getText('/_admin/builder/hint/query'), stack.attributes.query, function( value ) {
				stack.attributes.query = value;
				builder._changed();
			} ) );

			if( schema.grid !== false ) {

				const cols = String( stack.attributes.cols || '100 50 33' ).split( /\s+/ );

				builder.VIEWPORTS.forEach( function( viewport, at ) {
					pane.appendChild( builder._selectField( Nino.adminUi.format( Nino.content.getText('/_admin/builder/stack/cells'), Nino.content.getText( '/_admin/builder/viewport/'+ viewport ) ), '', builder.COL_WIDTHS.map( function( width ) { return { value : width, label : width } } ), cols[at] ?? '100', function( value ) {
						cols[at] = value;
						stack.attributes.cols = cols.slice( 0, 3 ).join(' ');
						builder._changed();
					} ) );
				} );

				pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/gap'), '', builder._options( 'space', builder.SPACES.slice( 1 ) ), String( stack.attributes.gap ), function( value ) {
					stack.attributes.gap = value;
					builder._changed();
				} ) );

				pane.appendChild( builder._switchField( Nino.content.getText('/_admin/builder/stack/autoheight'), Nino.content.getText('/_admin/builder/hint/autoheight'), stack.attributes.autoheight === '1', function( checked ) {
					stack.attributes.autoheight = checked === true ? '1' : '0';
					builder._changed();
				} ) );
			}

			pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/stack/id'), Nino.content.getText('/_admin/builder/hint/stack-id'), stack.attributes.id, function( value ) {
				stack.attributes.id = value;
				builder._changed();
			} ) );

			Object.keys( builder._declared( schema ) ).forEach( function( name ) {
				pane.appendChild( builder._attributeField( name, builder._declared( schema )[name], stack.attributes, { fields : type === undefined ? {} : type.fields } ) );
			} );
		},

		/**
		 *	The order of a stack: a field and a direction, as the loop reads them
		 *	('title', '-date') - and, for a list of several fields, the text itself
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		stack
		 *	@param		{Object}		fields						The fields of the stack's type
		 *
		 *	@return		void
		 */
		_sortField : function( pane, stack, fields ) {

			const builder = Nino.admin.builder;
			const sort = String( stack.attributes.sort || '' );
			const label = Nino.content.getText('/_admin/builder/stack/sort');
			const hint = Nino.content.getText('/_admin/builder/hint/sort');

			if( sort.indexOf(',') !== -1 ) {
				pane.appendChild( builder._textField( label, hint, sort, function( value ) { stack.attributes.sort = value; builder._changed() } ) );
				return;
			}

			const down = sort.charAt(0) === '-';
			const field = down === true ? sort.slice( 1 ) : sort;
			const names = Object.keys( fields );
			const choices = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ].concat( names.map( function( name ) { return { value : name, label : name } } ) );

			if( field !== '' && names.indexOf( field ) === -1 )
				choices.push( { value : field, label : field } );

			const apply = function( name, descending ) {
				stack.attributes.sort = name === '' ? '' : ( descending === true ? '-' : '' )+ name;
				builder._changed();
			};

			let current = field;
			let descending = down;

			pane.appendChild( builder._selectField( label, hint, choices, field, function( value ) { current = value; apply( current, descending ) } ) );
			pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/stack/direction'), '', [ { value : 'asc', label : Nino.content.getText('/_admin/builder/stack/asc') }, { value : 'desc', label : Nino.content.getText('/_admin/builder/stack/desc') } ], down === true ? 'desc' : 'asc', function( value ) { descending = value === 'desc'; apply( current, descending ) } ) );
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
		 *	The form of a component: its source, the attributes its schema declares
		 *	and the classes of its own. [html] has its content in the editor of the
		 *	blocks profile instead of a source
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
			let editor = null;
			let typed = false;

			builder._dialog( {
				title	: Nino.adminUi.format( Nino.content.getText('/_admin/builder/component/title'), schema.label || component.name ),
				wide	: true,
				build	: function( body ) {

					builder._blamed( body, path[0] );

					if( kind === 'content' ) {

						const mount = builder._el( 'div', 'builder-content' );

						body.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/component/content') ) );
						body.appendChild( mount );

						if( typeof Nino.admin.htmlEditor === 'object' ) {
							editor = Nino.admin.htmlEditor.create( mount, component.content ?? '', builder.CONTENT_MAX, 8, 'blocks' );
							mount.addEventListener( 'input', function() { typed = true; component.content = editor.getValue(); builder._changed() } );
						}
					} else if( kind !== 'none' )
						body.appendChild( builder._sourceField( {
							kind		: kind,
							name		: component.name,
							section	: section,
							fields	: fields,
							value		: component.source,
							text		: component.text,
							label		: schema.label || component.name,
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

					const declared = builder._declared( schema );

					Object.keys( declared ).forEach( function( name ) {
						body.appendChild( builder._attributeField( name, declared[name], component.attributes, { fields : fields, component : component } ) );
					} );

					body.appendChild( builder._textField( Nino.content.getText('/_admin/builder/section/custom'), Nino.content.getText('/_admin/builder/hint/custom'), component.attributes['class'], function( value ) {
						component.attributes['class'] = value;
						builder._changed();
					} ) );
				},
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
		 *	One attribute of a component or a stack, by the type its schema gives
		 *	it. Every value is a string in the model, as a call writes it
		 *
		 *	@param		{string}		name
		 *	@param		{Object}		declared					The schema's entry: type, label, hint, options, min, max
		 *	@param		{Object}		attributes				Where the value is kept
		 *	@param		{Object}		context						{ fields } of the stack's type, where there is a stack
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

			const field = builder._textField( label, hint, value, write, { list : declared.type === 'string' ? undefined : 'builder-list-'+ name } );

			// A key, an image slot or a link: what the project has is offered, what is typed is taken
			if( declared.type === 'key' || declared.type === 'href' || declared.type === 'image' ) {

				const list = builder._el( 'datalist' );
				const known = declared.type === 'image'
					? ( builder._registry.slots || [] ).map( function( slot ) { return slot.uri } )
					: ( builder._keys || [] ).map( function( entry ) { return entry.key } );
				const own = context !== undefined && context.fields !== null && context.fields !== undefined ? Object.keys( context.fields ).concat( declared.type === 'href' ? [ '.uri' ] : [] ) : [];

				list.id = 'builder-list-'+ name;
				own.concat( known ).slice( 0, 300 ).forEach( function( item ) { const option = builder._el( 'option' ); option.value = item; list.appendChild( option ) } );
				field.appendChild( list );
			}

			return field;
		},

		// ----------------------------------------------------------- The source

		/**
		 *	The source of a component: where its text or its picture comes from. A
		 *	field with tabs, as many as the schema allows - a text key (Text), an
		 *	image slot (Image), a value that is the template's (Fixed) - and, in a
		 *	stack, the fields of the element beside the keys
		 *
		 *	@param		{Object}		options
		 *	@param		{string}		options.kind				text, image or href
		 *	@param		{Object}		options.section
		 *	@param		{Object|null}	options.fields		The fields of the stack's type, null in a static column
		 *	@param		{string}		options.value				The source as it is
		 *	@param		{string|null}	options.text			The fixed value
		 *	@param		{string}		options.name				What a new key or slot is called: the kind of the component, background for the picture behind a section
		 *	@param		{string}		options.label				The component's label, the text a new key starts with
		 *	@param		{Object}		[options.red]				What is wrong with the source where it stands
		 *	@param		{Function}	options.onPick				( source, create|null )
		 *	@param		{Function}	[options.onFixed]		( text )
		 *
		 *	@return		{Element}
		 */
		_sourceField : function( options ) {

			const builder = Nino.admin.builder;
			const wrap = builder._fragment( 'source' );
			const tabs = options.kind === 'image' ? [ 'image' ] : [ 'text', 'fixed' ];
			const buttons = {};
			const panes = {};
			const start = typeof options.text === 'string' && options.kind !== 'image' ? 'fixed' : tabs[0];

			tabs.forEach( function( tab ) {
				buttons[tab] = builder._one( wrap, '.builder-source-tab[data-tab="'+ tab+ '"]' );
				panes[tab] = builder._one( wrap, '.builder-source-pane[data-tab="'+ tab+ '"]' );
			} );

			[ 'text', 'image', 'fixed' ].forEach( function( tab ) {
				if( tabs.indexOf( tab ) === -1 ) {
					builder._one( wrap, '.builder-source-tab[data-tab="'+ tab+ '"]' ).remove();
					builder._one( wrap, '.builder-source-pane[data-tab="'+ tab+ '"]' ).remove();
				}
			} );

			builder._one( wrap, '.builder-source-label' ).textContent = Nino.content.getText('/_admin/builder/source/title');

			const current = builder._one( wrap, '.builder-source-current' );
			const show = function() {
				current.textContent = typeof options.text === 'string' && options.value === '' ? '“'+ options.text+ '”' : ( options.value === '' ? Nino.content.getText('/_admin/builder/label/none') : options.value );
				const red = builder._one( wrap, '.builder-source-red' );
				red.hidden = options.red === undefined;
				red.textContent = options.red === undefined ? '' : Nino.content.getText( '/_admin/builder/tree/red-'+ options.red.why );
			};
			const pick = function( source, create ) {
				options.value = source;
				options.text = null;
				options.red = undefined;
				options.onPick( source, create );
				show();
			};

			if( panes.text !== undefined )
				builder._textPane( panes.text, options, pick );

			if( panes.image !== undefined )
				builder._imagePane( panes.image, options, pick );

			if( panes.fixed !== undefined ) {

				const fixed = builder._textField( Nino.content.getText('/_admin/builder/source/fixed'), Nino.content.getText('/_admin/builder/hint/fixed'), options.text ?? '', function( value ) {
					options.text = value;
					options.value = '';
					options.red = undefined;
					options.onFixed( value );
					show();
				} );

				panes.fixed.appendChild( fixed );
			}

			Nino.adminUi.buttonRow( buttons, start, function( tab ) {
				Object.keys( panes ).forEach( function( key ) { panes[key].hidden = key !== tab } );
			}, 'aria-selected' );

			Object.keys( panes ).forEach( function( key ) { panes[key].hidden = key !== start } );
			show();

			return wrap;
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
		 *	The Text tab of the source: the fields of the element (in a stack), the
		 *	keys of the project, found by what they are called or say - with the
		 *	keys of the section first - and a new key of the section's own
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options
		 *	@param		{Function}	pick
		 *
		 *	@return		void
		 */
		_textPane : function( pane, options, pick ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const types = [ 'string' ];

			if( options.fields !== null && options.fields !== undefined ) {

				const names = Object.keys( options.fields ).filter( function( name ) { return types.indexOf( options.fields[name].type ) !== -1 } );
				const items = [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ]
					.concat( names.map( function( name ) { return { value : name, label : name } } ) )
					.concat( [ { value : '.id', label : '.id' }, { value : '.uri', label : '.uri' } ].filter( function( item ) { return options.kind === 'href' || item.value === '.id' } ) );

				pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/field'), Nino.content.getText('/_admin/builder/hint/field'), items, options.value.charAt(0) === '/' ? '' : options.value, function( value ) { pick( value, null ) } ) );
			}

			const keys = ( builder._keys || [] ).map( function( entry ) {
				const value = entry.global === true ? entry.values['*'] : Object.values( entry.values )[0];
				return { value : entry.key, label : entry.key, sub : typeof value === 'string' ? value.replace( /<[^>]*>/g, '' ).slice( 0, 60 ) : '' };
			} );

			pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/source/key') ) );

			const own = builder._keyUri( doc.file, options.section.id, '' );

			if( keys.length > 0 )
				builder._picker( pane, keys, options.value, Nino.content.getText('/_admin/builder/source/search'), function( key ) { pick( key, null ) }, keys.some( function( key ) { return key.value.indexOf( own ) === 0 } ) === true ? own : '' );
			else
				pane.appendChild( builder._textField( Nino.content.getText('/_admin/builder/source/typed'), builder._keysAnswered === true ? Nino.content.getText('/_admin/builder/hint/typed') : '', options.value.charAt(0) === '/' ? options.value : '', function() {}, {
					onCommit : function( value ) { if( builder.KEY.test( value.trim() ) === true || value.trim().indexOf('/_nino/') === 0 ) pick( value.trim(), null ) },
				} ) );

			builder._newName( pane, options, 'key', function( uri ) { pick( uri, { value : options.label } ) } );
		},

		/**
		 *	The Image tab of the source: the fields of the element that hold a picture
		 *	(in a stack), the image slots of the project, and a new slot of the
		 *	section's own with the size it is made in
		 *
		 *	@param		{Element}		pane
		 *	@param		{Object}		options
		 *	@param		{Function}	pick
		 *
		 *	@return		void
		 */
		_imagePane : function( pane, options, pick ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;

			if( options.fields !== null && options.fields !== undefined ) {

				const names = Object.keys( options.fields ).filter( function( name ) { return options.fields[name].type === 'image' } );

				pane.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/field'), Nino.content.getText('/_admin/builder/hint/field-image'), [ { value : '', label : Nino.content.getText('/_admin/builder/label/none') } ].concat( names.map( function( name ) { return { value : name, label : name } } ) ), options.value.charAt(0) === '/' ? '' : options.value, function( value ) { pick( value, null ) } ) );
			}

			pane.appendChild( builder._el( 'span', 'builder-label', Nino.content.getText('/_admin/builder/source/slot') ) );

			builder._picker( pane, ( builder._registry.slots || [] ).map( function( slot ) {
				return { value : slot.uri, label : slot.uri, sub : slot.label+ ( slot.hasImage === true ? '' : ' – '+ Nino.content.getText('/_admin/builder/source/empty-slot') ), image : slot.url ?? '' };
			} ), options.value, Nino.content.getText('/_admin/builder/source/search-slot'), function( uri ) { pick( uri, null ) }, builder._keyUri( doc.file, options.section.id, '' ) );

			builder._newName( pane, options, 'slot', function( uri, size ) { pick( uri, { label : options.label, width : size.width, height : size.height } ) } );
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
		 *	The picture behind a section: a slot, its focus, and the way to take it
		 *	away again
		 *
		 *	@param		{Array}			path
		 *
		 *	@return		{Element}
		 */
		_backgroundField : function( path ) {

			const builder = Nino.admin.builder;
			const doc = builder._doc;
			const section = doc.model.blocks[path[0]];
			const wrap = builder._el( 'div', 'builder-background' );

			wrap.appendChild( builder._sourceField( {
				kind		: 'image',
				name		: 'background',
				section	: section,
				fields	: null,
				value		: section.background ? section.background.slot : '',
				text		: null,
				label		: Nino.content.getText('/_admin/builder/source/background'),
				onPick	: function( source, create ) {

					if( source === '' ) {
						section.background = null;
					} else {
						section.background = { slot : source, focus : section.background ? section.background.focus : null };

						if( create !== null )
							section.background.create = create;
					}

					builder._changed();
				},
			} ) );

			wrap.appendChild( builder._selectField( Nino.content.getText('/_admin/builder/source/focus'), Nino.content.getText('/_admin/builder/hint/focus'), builder.FOCUS.map( function( value ) { return { value : value, label : value === '' ? Nino.content.getText('/_admin/builder/label/none') : value } } ), section.background && section.background.focus !== null ? String( section.background.focus ) : '', function( value ) {

				if( section.background === null )
					return;

				section.background.focus = value === '' ? null : parseInt( value, 10 );
				builder._changed();
			} ) );

			const remove = builder._el( 'button', 'nino-admin-btn-secondary', Nino.content.getText('/_admin/builder/source/remove-background') );

			remove.type = 'button';
			remove.addEventListener( 'click', function() {
				section.background = null;
				builder._changed();
				builder._refreshTab( 'background' );
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

				builder._clip = null;
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
					builder._clip = null;
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
			builder._clip = null;
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
