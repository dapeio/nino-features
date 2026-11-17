/**
 *	Nino features
 *	Builder						The panel of the workbench for the pages: the list of them, and for a page the page itself - drawn by the website as a draft, in
 *										a frame - with a bar on each block that moves, copies or removes it, and the texts of the page written where they stand. Everything
 *										it shows is the answer of the API (see Modules\Builder\Admin): the page as it is saved from open, the draft from preview. A change
 *										is a change of the working state - the tree of the template and the words of its namespace - and nothing is written before Save.
 *
 *										The address is #builder (the list), #builder/<page> (a page: its template without page-) and #builder/@header or #builder/@footer
 *										(a frame of the site). The extended view of a block is #builder/<page>/<block> - the tree of its components, and
 *										#builder/<page>/<block>/@block its settings - and of one component #builder/<page>/<block>/<path>/<section>, the path being the
 *										position of the component in the block; the frames are the same views at #builder/<page>/@header and #builder/<page>/@footer. They
 *										are for the account that may design.
 *
 *										A block is put in with "Insert block": between the blocks of the page, at its end and in the outline. The dialog lists what the
 *										server offers, by category, each with a small picture drawn by the website in the active theme; the block that is chosen is asked
 *										for as a tree with its texts in every language, its ids are made free by the rule (hero, hero-2 - what is inside begins with the
 *										new id of the root), and it stands in the working state like any other change until Save.
 *
 *										The tree is the one \Nino\Markup reads, kept canonical (a text that follows a text is one node), so that the position of a node
 *										in it is the position the trace of the drawing names. A block is a node on the top level that the register has; free markup and a
 *										call that has no schema are locked and stay where they are. Moving a block swaps it with the next block - never past a locked
 *										piece. The extended view works on the same tree: a component is put in where the register says it may stand, moved among its
 *										neighbours, taken out - the undo brings it back - and has its settings by the schema of its component.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// How many steps the undo goes back, the widths of the preview and the width the page is laid out at for each: in the page the desktop is as wide as the pane,
	// beside the tree of the extended view the pane is narrow and the desktop is laid out at DESKTOP and scaled into it, as the preview of the themes does
	const MAX_STEPS	= 200;
	const WIDTHS		= [ 'mobile', 'tablet', 'desktop' ];
	const LAYOUT		= { mobile : 390, tablet : 768, desktop : null };
	const DESKTOP		= 1280;

	// The step that writes the markup in a call of html
	const DEVELOP		= 3;

	// Below this width of the preview a person who has chosen no width is shown the page as a phone shows it
	const NARROW		= 520;

	// The words after an id that is a copy of another: hero-2, and the longest id there is
	const MAX_ID		= 64;

	// How long a text typed in the page may be, in characters: the server keeps 20000 bytes of what a format makes of it
	const MAX_TEXT	= 5000;

	// How long the words in the name of a block are, in characters
	const NAMED			= 24;

	// The blanks the server calls a gap
	const BLANKS		= /^[ \t\r\n]*$/;

	// The page a tile of the insert dialog is drawn at, in pixels, and how long before its time is out a draft is asked for again, in milliseconds
	const TILE			= { width : 960, height : 600 };
	const TILES			= 12;
	const GRACE			= 30000;

	/**
	 *	What the panel holds: the list, the page that is open with its tree and words as they are saved and as they are being worked on, the steps
	 *	of the undo and what the frame shows
	 */
	const S = {
		list			: null,
		ready			: false,
		template	: '',
		frame			: false,
		top				: [],
		texts			: {},
		saved			: '',
		stamp			: '',
		known			: {},
		slots			: [],
		sources		: [],
		pending		: null,
		address		: '/',
		dir				: '',
		locale		: '',
		locales		: [],
		undo			: [],
		redo			: [],
		last			: 0,
		unback		: '',
		view			: null,
		width			: 'desktop',
		chosen		: false,
		editing		: null,
		stale			: false,
		force			: false,
		refocus		: null,
		imageBack	: '',
		keys			: false,
		place			: null,
		savedTexts	: {},
		copies			: {},
		inserted		: {},
		named				: {},
		fresh				: {},
		oldIds			: new Set(),
		native			: '',
		catalog			: null,
		tiles				: {},
		category		: '',
		looking			: null,
		level				: 1,
		page				: '',
		target			: null,
		at					: [],
		adv					: null,
		schemas			: {},
		findings		: {},
		links				: [],
		notation		: {},
		marked			: '',
		mark				: null,
		tree				: null,
		focusNext		: '',
		advFocus		: '',
		strips			: {},
		built				: false,
		mode				: 'basic',
		focusFrom		: '',
		look				: null,
		pictures		: {},
		images			: {},
		staged			: [],
		types				: [],
		typesAsked	: false,
	};

	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/builder/'+ key ), ...params );
	};

	const localized = function( value ) {

		if( typeof value === 'string' )
			return value;

		if( value === null || typeof value !== 'object' )
			return '';

		const tag		= String( ( dc.documentElement && dc.documentElement.lang ) || '' ).replace( '-', '_' );
		const names	= Object.keys( value );
		const near	= names.find( function( name ) { return tag !== '' && name.slice( 0, 2 ) === tag.slice( 0, 2 ) } );

		return String( value[tag] ?? ( near !== undefined ? value[near] : undefined ) ?? value.en_US ?? value[names[0]] ?? '' );
	};

	const clone = function( value ) {
		return JSON.parse( JSON.stringify( value ) );
	};

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	const here = function() {
		return Nino.admin.router.current();
	};

	// --- the model: the tree, the blocks and the words ---------------------------------------------------------

	/**
	 *	The nodes of a level with the texts that follow a text made one, and the empty ones left out: the form the server reads back from what it writes
	 *
	 *
	 */
	const normalize = function( nodes ) {

		const out = [];

		nodes.forEach( function( node ) {

			if( Array.isArray( node.c ) === true )
				node.c = normalize( node.c );

			if( node.t !== undefined && node.n === undefined && node.f === undefined ) {

				if( node.t === '' )
					return;

				const last = out[out.length - 1];

				if( last !== undefined && last.t !== undefined && last.n === undefined && last.f === undefined ) {
					out[out.length - 1] = { t : last.t+ node.t };
					return;
				}
			}

			out.push( node );
		} );

		return out;
	};

	/**
	 *	What a node of the top level is: a gap, free markup, a call without a schema (the register tells which names it has), or a block
	 *
	 *
	 *	@return		{string}									'gap', 'free', 'call' or 'block'
	 */
	const kindOf = function( node ) {

		if( node.n !== undefined )
			return S.known[node.n] !== undefined ? 'block' : 'call';

		return node.t !== undefined && node.f === undefined && BLANKS.test( node.t ) === true ? 'gap' : 'free';
	};

	// Whether a block holds free markup or a call that has no schema: such a block stays where it is, as it is - it is locked like a piece of the top level
	const lockedInside = function( node ) {

		// The text a call of html holds is its content for the account that may develop, no free markup
		const own = node.n === 'html' && S.level >= DEVELOP;

		return ( node.c ?? [] ).some( function( inner ) {

			if( inner.f !== undefined || ( inner.t !== undefined && BLANKS.test( inner.t ) === false && own === false ) )
				return true;

			return inner.n !== undefined && ( S.known[inner.n] === undefined || lockedInside( inner ) === true );
		} );
	};

	// Whether a node of the top level is a block that a person may move, copy and take out
	const free = function( node ) {
		return node !== undefined && kindOf( node ) === 'block' && lockedInside( node ) === false;
	};

	// The id a block has - the attribute its component has for it - or ''
	const idOf = function( node ) {

		const name = S.known[node.n] !== undefined ? S.known[node.n].id : '';

		return name !== '' && node.a !== undefined && typeof node.a[name] === 'string' ? node.a[name] : '';
	};

	// Every id the tree names, in a set
	const idsOf = function( nodes, into ) {

		nodes.forEach( function( node ) {

			if( node.n === undefined )
				return;

			const id = idOf( node );

			if( id !== '' )
				into.add( id );

			if( Array.isArray( node.c ) === true )
				idsOf( node.c, into );
		} );

		return into;
	};

	/**
	 *	The index of the block a move goes to: the next block that way, with nothing but blanks between - a locked piece is no way past
	 *
	 *	@param		{number}		direction			-1 or 1
	 *
	 *	@return		{number}									-1 for none
	 */
	const neighbour = function( at, direction ) {

		for( let next = at + direction; next >= 0 && next < S.top.length; next += direction ) {

			const kind = kindOf( S.top[next] );

			if( kind === 'block' )
				return free( S.top[next] ) === true ? next : -1;

			if( kind !== 'gap' )
				return -1;
		}

		return -1;
	};

	// Whether an id is free: it is no id of the page - the one that is saved and the one that is worked on - and no key or slot of the page has it for a scope
	const isFree = function( candidate, taken ) {

		const prefix = '/template/'+ S.template+ '/'+ candidate+ '/';

		return candidate.length <= MAX_ID && taken.has( candidate ) === false && S.oldIds.has( candidate ) === false
			&& Object.keys( S.texts ).concat( S.slots, Object.keys( S.copies ) ).some( function( key ) { return key.indexOf( prefix ) === 0 } ) === false;
	};

	// The id of a copy: the id of its root with the number after it that is the first free one - a number is no part of the base, hero-2 is hero again. A block
	// that is put in has its own id first, where that is free
	const freeId = function( id, taken, own ) {

		const base = id.replace( /-\d+$/, '' );

		if( own === true && isFree( id, taken ) === true )
			return id;

		for( let count = 2; count < 10000; count++ ) {

			const candidate = base+ '-'+ count;

			if( isFree( candidate, taken ) === true )
				return candidate;
		}

		return '';
	};

	// The id a copy has for an id of the original (the rule: it begins with the id of the new root and a hyphen)
	const innerId = function( id, root, now ) {
		return id.indexOf( root+ '-' ) === 0 ? now+ id.slice( root.length ) : now+ '-'+ id;
	};

	/**
	 *	A copy of a block with its ids made new, and which id is which
	 *
	 *	@param		{string}		root					Its id
	 *	@param		{string}		now						The id of the copy
	 *	@param		{Object}		map						(filled) old id => new id
	 *
	 */
	const copied = function( block, root, now, map ) {

		const copy = clone( block );
		const walk = function( node ) {

			if( node.n === undefined )
				return;

			const name = S.known[node.n] !== undefined ? S.known[node.n].id : '';

			if( name !== '' && node.a !== undefined && typeof node.a[name] === 'string' && node.a[name] !== '' ) {
				const was = node.a[name];
				node.a[name] = was === root ? now : innerId( was, root, now );
				map[was] = node.a[name];
			}

			if( Array.isArray( node.c ) === true )
				node.c.forEach( walk );
		};

		walk( copy );

		return copy;
	};

	// The attributes of a node that has none come as an empty list from the server and are an empty map here, so that an attribute can be set on them
	const fixAttrs = function( nodes ) {

		nodes.forEach( function( node ) {

			if( node.n === undefined )
				return;

			if( Array.isArray( node.a ) === true || node.a === undefined || node.a === null )
				node.a = {};

			if( Array.isArray( node.c ) === true )
				fixAttrs( node.c );
		} );

		return nodes;
	};

	// What the screen knows of a component by name - its label, the attribute that is its id and what its source is - from the schemas the server sent
	const learn = function( schemas ) {

		Object.keys( schemas ).forEach( function( name ) {
			S.known[name] = { label : schemas[name].label, id : schemas[name].id, source : schemas[name].source };
		} );
	};

	const snap = function() {
		return { top : clone( S.top ), texts : clone( S.texts ), copies : clone( S.copies ), inserted : clone( S.inserted ), pictures : clone( S.pictures ) };
	};

	const stateOf = function() {
		return JSON.stringify( [ S.top, S.texts, S.pictures ] );
	};

	const isDirty = function() {
		return S.ready === true && stateOf() !== S.saved;
	};

	/**
	 *	Make a change: the state before it is kept for the undo and what the frame shows is asked for again, unless the change is a text that is
	 *	typed in the frame, which stands there as it was typed
	 *
	 *	@param		{Function}	make					Changes S.top and S.texts
	 *	@param		{boolean}	[reload]				false for a typed text
	 */
	const change = function( make, reload ) {

		const before = snap();

		make();
		S.top = normalize( S.top );

		if( stateOf() === JSON.stringify( [ before.top, before.texts, before.pictures ] ) )
			return;

		S.undo.push( before );

		if( S.undo.length > MAX_STEPS )
			S.undo.shift();

		S.redo = [];
		changed( reload !== false );
	};

	const changed = function( reload ) {

		if( reload === true && S.view !== null ) {
			S.stale = true;
			S.view.preview.schedule();
		}

		paint();
	};

	const step = function( from, to ) {

		if( from.length === 0 )
			return;

		to.push( snap() );

		const now = from.pop();

		S.top = now.top;
		S.texts = now.texts;
		S.copies = now.copies;
		S.inserted = now.inserted;
		S.pictures = now.pictures;
		changed( true );

		// What the bar said of a change is not true of a page that is back to what is saved
		if( S.view !== null && isDirty() === false )
			S.view.bar.line.idle();
	};

	const undo = function() {
		step( S.undo, S.redo );
	};

	const redo = function() {
		step( S.redo, S.undo );
	};

	/**
	 *	Move the block at an index to the place of the next block that way
	 *
	 *
	 */
	const move = function( at, direction, from ) {

		const to = S.top[at] === undefined || free( S.top[at] ) === false ? -1 : neighbour( at, direction );

		if( to === -1 || S.frame === true )
			return false;

		change( function() {
			const held = S.top[at];
			S.top[at] = S.top[to];
			S.top[to] = held;
		} );

		S.refocus = { at : to, action : direction < 0 ? 'up' : 'down', from : from ?? 'frame' };

		return true;
	};

	/**
	 *	Put a copy of a block behind it: its ids made new by the rule (the new root is the first free id of its name, what is inside begins with it), and the
	 *	words of every id the block has put under the new ids, in every language
	 *
	 *
	 */
	const duplicate = function( at, from ) {

		const block = S.top[at];

		if( free( block ) === false || S.frame === true )
			return false;

		const root = idOf( block );
		let copy = null;
		let map = {};

		if( root === '' )
			copy = clone( block );
		else {

			const now = freeId( root, idsOf( S.top, new Set() ) );

			if( now === '' )
				return false;

			copy = copied( block, root, now, map );

			if( idsOf( [ copy ], new Set() ).size !== Object.keys( map ).length )
				return false;
		}

		change( function() {

			S.top.splice( at + 1, 0, { t : '\n' }, copy );

			// A copy of a block that is put in and not saved is that block as well: the server holds it to the block it was made from
			if( root !== '' && S.inserted[root] !== undefined )
				S.inserted[ idOf( copy ) ] = S.inserted[root];

			if( root !== '' && S.named[root] !== undefined )
				S.named[ idOf( copy ) ] = S.named[root];

			Object.keys( S.texts ).forEach( function( key ) {

				const parts = key.split('/');

				if( parts.length === 5 && parts[2] === S.template && map[parts[3]] !== undefined )
					S.texts[ '/template/'+ S.template+ '/'+ map[parts[3]]+ '/'+ parts[4] ] = clone( S.texts[key] );
			} );

			// The pictures of the copy are made from those of the original when the page is saved: the same file, a slot of their own
			S.slots.forEach( function( uri ) {

				const parts = uri.split('/');

				if( parts.length === 5 && parts[2] === S.template && map[parts[3]] !== undefined )
					S.copies[ '/template/'+ S.template+ '/'+ map[parts[3]]+ '/'+ parts[4] ] = uri;
			} );
		} );

		S.refocus = { at : at + 2, action : 'copy', from : from ?? 'frame' };

		return true;
	};

	/**
	 *	Take a block out, with the blanks that stood in front of it - or, for the first, behind it
	 *
	 *
	 */
	const remove = function( at, from ) {

		if( free( S.top[at] ) === false || S.frame === true )
			return false;

		S.refocus = { at : at, action : 'remove', from : from ?? 'frame' };

		change( function() {

			if( at > 0 && kindOf( S.top[at - 1] ) === 'gap' )
				S.top.splice( at - 1, 2 );
			else if( at + 1 < S.top.length && kindOf( S.top[at + 1] ) === 'gap' )
				S.top.splice( at, 2 );
			else
				S.top.splice( at, 1 );
		} );

		return true;
	};

	/**
	 *	The places of the page a block can be put in: each seam of the top level between the calls of the frames and, where the page has none, each seam there is.
	 *	A place knows the row before and behind it, the block that carries its button and the name of its neighbour. A frame has none
	 *
	 *	@return		{Array}										[ { b, after, before, block, carry : 'after'|'before'|'', name } ]
	 */
	const points = function() {

		const list = [];

		if( S.frame === true )
			return list;

		const rows = items();
		const calls = rows.map( function( row, at ) { return row.kind === 'frame' ? at : -1 } ).filter( function( at ) { return at !== -1 } );

		for( let b = 0; b <= rows.length; b++ ) {

			if( calls.length > 0 && ( b <= calls[0] || ( calls.length > 1 && b > calls[calls.length - 1] ) ) )
				continue;

			const above = rows[b - 1];
			const below = rows[b];

			list.push( {
				b			: b,
				after	: above === undefined ? null : above.last,
				before	: below === undefined ? null : below.at,
				block	: above !== undefined && above.kind === 'block' ? above.at : ( below !== undefined && below.kind === 'block' ? below.at : null ),
				carry	: above !== undefined && above.kind === 'block' ? 'after' : ( below !== undefined && below.kind === 'block' ? 'before' : '' ),
				name	: above !== undefined ? titleOf( above.node ) : ( below !== undefined ? titleOf( below.node ) : S.name ),
			} );
		}

		return list;
	};

	// The text of a block in the languages of the site, as the block has it: each language its own, the native one the English where the block has none for it, any
	// other language none - the page then shows the text of the native language
	const wordsOf = function( block, name ) {

		const out = {};

		S.locales.filter( function( entry ) { return entry.active === true } ).forEach( function( entry ) {

			const own = ( block.texts[entry.locale] ?? {} )[name];
			const text = typeof own === 'string' ? own : ( entry.locale === S.native ? ( block.texts.en_US ?? {} )[name] : undefined );

			if( typeof text === 'string' )
				out[entry.locale] = text;
		} );

		return out;
	};

	/**
	 *	Put a block in the page: its tree with free ids (its own id or the first number behind it; what is inside begins with it) and its texts under the keys
	 *	of the new ids, each language with its own
	 *
	 *	@param		{Object}		place					One of points()
	 *	@param		{Object}		block					The answer of the server
	 *	@param		{string}		[from]				outline or frame
	 *	@param		{string|Object}	[name]		What the dialog calls the block
	 */
	const insert = function( place, block, from, name ) {

		if( S.frame === true )
			return false;

		Object.assign( S.known, block.known );
		Object.assign( S.schemas, block.schemas ?? {} );

		const now = freeId( block.id, idsOf( S.top, new Set() ), true );

		if( now === '' )
			return false;

		const map = {};
		const copy = copied( block.tree[0], block.id, now, map );

		if( idsOf( [ copy ], new Set() ).size !== Object.keys( map ).length )
			return false;

		// Behind the row before the place, else in front of the row behind it, else at the end
		const at = place.after !== null ? place.after + 1 : ( place.before !== null ? place.before : S.top.length );

		change( function() {

			if( place.after !== null )
				S.top.splice( at, 0, { t : '\n' }, copy );
			else
				S.top.splice( at, 0, copy, { t : '\n' } );

			// The focus is put on the new block before the page is drawn - the button that had it is gone with the drawing -, where the blanks that were joined left it
			S.refocus = { at : normalize( S.top ).indexOf( copy ), action : 'copy', from : from ?? 'frame' };

			block.slots.forEach( function( slot ) {

				const was = slot.scope === '' ? block.id : block.id+ '-'+ slot.scope;
				const id = was === block.id ? now : innerId( was, block.id, now );
				const key = '/template/'+ S.template+ '/'+ id+ '/'+ slot.word;

				if( slot.kind === 'image' ) {

					// The slot is made when the page is saved; until then the panel knows its label and size, so that a file can be chosen
					const spec = ( block.images ?? {} )[slot.name];

					if( spec !== undefined )
						S.pictures[key] = { label : localized( spec.label ) || slot.word, width : spec.width, height : spec.height };

					return;
				}

				const words = wordsOf( block, slot.name );

				if( Object.keys( words ).length > 0 )
					S.texts[key] = words;
			} );

			S.inserted[now] = block.key;

			// The block is called what the dialog called it until it has words to be called by
			if( name !== undefined )
				S.named[now] = name;
		} );

		return true;
	};

	// The blocks that were put in and are still on the top level of the page: what the server is told, so that it holds each to the block it is
	const insertedNow = function() {

		const out = {};

		S.top.forEach( function( node ) {

			const id = node.n === undefined ? '' : idOf( node );

			if( id !== '' && S.inserted[id] !== undefined )
				out[id] = S.inserted[id];
		} );

		return out;
	};

	// The node a position names, 'a.b.c' - the indices from the top level down - or null
	const nodeAt = function( path ) {

		let list = S.top;
		let node = null;

		for( const part of String( path ).split('.') ) {

			node = list[ Number( part ) ];

			if( node === undefined )
				return null;

			list = Array.isArray( node.c ) === true ? node.c : [];
		}

		return node;
	};

	const textOf = function( key ) {
		return S.texts[key] !== undefined && typeof S.texts[key][S.locale] === 'string' ? S.texts[key][S.locale] : '';
	};

	// --- the blocks of the page: their names and the outline ------------------------------------------------------

	// The words of a text for a title: no markup, one line, cut at a word where it is long
	const shortened = function( text, most ) {

		const plain = String( text ).replace( /<[^>]*>/g, ' ' ).replace( /&[a-z#0-9]+;/gi, ' ' ).replace( /\s+/g, ' ' ).trim();

		return plain.length <= most ? plain : plain.slice( 0, most ).replace( /\s+\S*$/, '' )+ '…';
	};

	/**
	 *	The first text a block shows: the value of its first source that is a text, or a fixed text - in the language of the view - where the page has one
	 *
	 *	@param		{string}		scope					The id of the nearest call above that has one
	 *
	 */
	const firstText = function( nodes, scope, most ) {

		for( const node of nodes ) {

			if( node.n === undefined )
				continue;

			const known = S.known[node.n];
			const id = idOf( node ) === '' ? scope : idOf( node );

			if( known !== undefined && known.source === 'text' ) {

				const written = typeof node.s === 'string' ? node.s : '';
				const value = typeof ( node.a ?? {} ).text === 'string' ? node.a.text : ( written.charAt(0) === '~' && id !== '' ? textOf( '/template/'+ S.template+ '/'+ id+ '/'+ written.slice( 1 ) ) : '' );

				if( shortened( value, most ) !== '' )
					return shortened( value, most );
			}

			const inner = Array.isArray( node.c ) === true ? firstText( node.c, id, most ) : '';

			if( inner !== '' )
				return inner;
		}

		return '';
	};

	// The kind of a block in a word: the one this panel has for the component - a section is a Sektion -, else the label the register gives it
	const kindName = function( node ) {

		const own = Nino.content.getText( '/_admin/builder/kind/'+ node.n );

		return own !== '' ? own : localized( S.known[node.n].label );
	};

	// The name of a block: its kind and, behind it, the first text it shows or - where it shows none - its id
	const titleOf = function( node, whole ) {

		if( kindOf( node ) !== 'block' )
			return node.n === 'template' ? word('word/frame') : ( kindOf( node ) === 'call' ? word( 'word/call', node.n ) : word('word/free') );

		// A header and a footer are what they are called: the first words in them are a button or a link
		// A name is the kind and the first words: where they are longer than a name is, the kind and the id - the whole of the words is the title of the name
		const text = node.n === 'header-wrap' || node.n === 'footer-wrap' ? '' : firstText( [ node ], '', 120 );
		const named = S.named[idOf( node )];
		const label = text !== '' && ( whole === true || text.length <= NAMED ) ? text : ( node.n === 'header-wrap' || node.n === 'footer-wrap' ? '' : ( named !== undefined ? localized( named ) : idOf( node ) ) );

		return label === '' ? kindName( node ) : kindName( node )+ ' · '+ label;
	};

	/**
	 *	The rows of the outline: a block each, the call of a frame as a line of its own, and the locked pieces that stand one after the other as one row
	 *	of free markup - the imprint of a site is one row, and not the seven nodes it is made of
	 *
	 *	@return		{Array}										[ { at, kind : 'block'|'frame'|'free', node, last } ]
	 */
	const items = function() {

		const list = [];

		S.top.forEach( function( node, at ) {

			const kind = kindOf( node );

			// A note the template begins with is no piece of the page to look at
			if( kind === 'gap' || ( node.t !== undefined && /^\s*(?:<!--[\s\S]*?-->\s*)+$/.test( node.t ) === true ) )
				return;

			const row = kind === 'block' ? 'block' : ( node.n === 'template' ? 'frame' : 'free' );
			const before = list[list.length - 1];

			if( row === 'free' && before !== undefined && before.kind === 'free' && S.top.slice( before.last + 1, at ).every( function( between ) { return kindOf( between ) === 'gap' } ) === true ) {
				before.last = at;
				return;
			}

			list.push( { at : at, kind : row, node : node, last : at } );
		} );

		return list;
	};

	/**
	 *	Ask whether a block may be taken out: the question is asked, and answered with yes only by a person who read it
	 *
	 *	@param		{string}		from					Where the action was started: outline or frame
	 */
	const askRemove = function( at, opener, from ) {

		// Nothing a visitor sees changes before Save, so the question is the one of a thing that is taken away - and not the one of a live site
		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/remove-title'),
			message	: word( 'confirm/remove-message', titleOf( S.top[at] ) ),
			yes			: word('label/remove'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose : function( ok ) {
				if( ok === true )
					remove( at, from );
			},
		} );
	};

	const act = function( at, action, opener, from ) {

		if( action === 'up' )
			move( at, -1, from );
		else if( action === 'down' )
			move( at, 1, from );
		else if( action === 'copy' )
			duplicate( at, from );
		else if( action === 'remove' )
			askRemove( at, opener, from );
	};

	// The four things done to a block: the key, the glyph that stands for it and whether its word is written out where there is room for it
	const ACTIONS = [ [ 'up', '↑', false ], [ 'down', '↓', false ], [ 'copy', '⧉', true ], [ 'remove', '✕', true ] ];

	// Whether an action can be done to the block at an index now
	const possible = function( at, action ) {

		if( S.frame === true || free( S.top[at] ) === false )
			return false;

		return action === 'up' ? neighbour( at, -1 ) !== -1 : ( action === 'down' ? neighbour( at, 1 ) !== -1 : true );
	};

	// The address of the extended view of a block - a block of a page by its id, the block of a frame by the frame -, null where it has none: a frame is
	// shown on the first page of the site
	const advancedParts = function( node ) {

		if( S.level < 2 )
			return null;

		if( S.frame === true ) {

			const page = S.list.pages[0];

			return page === undefined ? null : [ addressOf( page.template ), addressOf( S.template ) ];
		}

		return idOf( node ) === '' ? null : [ addressOf( S.template ), idOf( node ) ];
	};

	// The place to put a block in as a row of the outline: one button, named for the block it stands by
	const insertRow = function( place ) {

		const row = el( 'li', 'builder-insert-row' );
		const button = Nino.adminUi.button( word('label/insert-short'), function() { openInsert( place, button, 'outline' ) }, { className : 'nino-admin-btn--small builder-insert', name : word( place.after === null && place.before !== null ? 'label/insert-before' : 'label/insert-after', place.name ) } );

		button.dataset.insert = String( place.b );
		row.appendChild( button );

		return row;
	};

	// The outline in the column of the split view: every block with its bar, the frame and the free markup as the lines they are, and between the blocks the places
	// where a block is put in
	const outline = function() {

		const list = el( 'ol', 'builder-outline' );
		const spots = points();
		const rows = items();
		const spotAt = function( b ) { return spots.find( function( spot ) { return spot.b === b } ) };

		rows.forEach( function( item, b ) {

			const before = spotAt( b );

			if( before !== undefined )
				list.appendChild( insertRow( before ) );

			const row = el( 'li', 'builder-row builder-row--'+ item.kind );
			const label = el( 'span', 'builder-row-name', titleOf( item.node ) );

			label.title = titleOf( item.node, true );

			row.dataset.at = String( item.at );
			row.appendChild( label );

			if( item.kind === 'free' || ( item.kind === 'block' && lockedInside( item.node ) === true ) )
				row.appendChild( el( 'span', 'nino-admin-badge builder-lock', word('word/locked') ) );

			if( item.kind === 'block' && S.frame === false && free( item.node ) === true ) {

				const bar = el( 'span', 'builder-row-actions' );

				bar.setAttribute( 'role', 'group' );
				bar.setAttribute( 'aria-label', word( 'label/block-bar', titleOf( item.node ) ) );

				ACTIONS.forEach( function( action ) {

					const name = word( 'label/'+ action[0] )+ ': '+ titleOf( item.node );
					const button = Nino.adminUi.button( action[2] === true ? action[1]+ ' '+ word( 'label/'+ action[0]+ '-short' ) : action[1], function() { act( item.at, action[0], button, 'outline' ) }, { className : 'nino-admin-btn--small builder-act', name : name } );

					button.dataset.action = action[0];
					button.disabled = possible( item.at, action[0] ) === false;
					bar.appendChild( button );
				} );

				bar.addEventListener( 'keydown', function( event ) {

					if( event.altKey === true && ( event.key === 'ArrowUp' || event.key === 'ArrowDown' ) ) {
						event.preventDefault();
						move( item.at, event.key === 'ArrowUp' ? -1 : 1, 'outline' );
					}
				} );

				row.appendChild( bar );
			}

			if( item.kind === 'block' && advancedParts( item.node ) !== null ) {

				const parts = advancedParts( item.node );
				const open = Nino.adminUi.button( word('label/advanced'), function() { Nino.admin.builder.go( parts, { focus : 'back' } ) }, { className : 'nino-admin-btn--small builder-act', name : word('label/advanced')+ ': '+ titleOf( item.node ) } );

				open.dataset.action = 'advanced';
				row.appendChild( open );
			}

			list.appendChild( row );
		} );

		const end = spotAt( rows.length );

		if( end !== undefined )
			list.appendChild( insertRow( end ) );

		return list;
	};

	// --- the frame: bars over the blocks, the texts that are written where they stand ------------------------------

	const FRAME_CSS = '[data-nino-text],[data-nino-image]{cursor:text;outline:2px dashed transparent;outline-offset:2px}'
		+ '[data-nino-image]{cursor:pointer}'
		+ '[data-nino-text]:hover,[data-nino-text]:focus,[data-nino-image]:hover,[data-nino-image]:focus{outline-color:#2f6fed}'
		+ '[data-nino-text][contenteditable]{outline:2px solid #2f6fed;background:rgba(47,111,237,.08)}'
		+ '[data-nino-placeholder]:not([contenteditable]){opacity:.6;font-style:italic}'
		+ '[data-nino-locked]{cursor:not-allowed}';

	// The bars stand in the shadow of one element at the end of the page: nothing of the website's style reaches them and they reach nothing of its
	const BAR_CSS = ':host{all:initial;position:absolute;top:0;left:0;width:0;height:0;z-index:2147483000}'
		+ '.bar{position:absolute;width:max-content;display:flex;flex-wrap:wrap;gap:4px;align-items:center;box-sizing:border-box;max-width:calc(100vw - 16px);padding:4px 6px;border-radius:6px;background:#1b2330;color:#fff;font:600 13px/1.2 system-ui,sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.4)}'
		+ '.name{flex:1 1 auto;min-width:0;padding:0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
		+ 'button{box-sizing:border-box;min-width:28px;min-height:28px;margin:0;padding:0 6px;border:1px solid #6b7d99;border-radius:4px;background:#2c3a4f;color:#fff;font:600 14px/1 system-ui,sans-serif;cursor:pointer}'
		+ 'button:hover{background:#3d4f6b}button:focus-visible{outline:3px solid #ffd54a;outline-offset:1px}button:disabled{opacity:.55;cursor:default}'
		+ '.frame{position:absolute;pointer-events:none;border:1px dashed rgba(47,111,237,.7);box-sizing:border-box}'
		+ 'button.insert{position:absolute;width:max-content;max-width:calc(100vw - 16px);padding:0 12px;border:1px dashed #b7c4da;border-radius:14px;background:#1b2330;box-shadow:0 1px 4px rgba(0,0,0,.4);white-space:nowrap}'
		+ 'button.insert:hover{background:#3d4f6b}';

	/**
	 *	The blocks and the texts of the page in the frame, from the comments the trace of the drawing writes: a block is the elements between the comment
	 *	that opens a call of the top level of this template and the one that ends it, a text is the element that holds a value between the comments of its source
	 *
	 *	@return		{Object}									{ blocks : [ { at, elements } ], texts : [ { element, path, written } ], all : [ { path, elements } ] }
	 */
	const traced = function( doc ) {

		const found = { blocks : [], texts : [], all : [] };
		const prefix = S.template+ ':';
		const walker = doc.createTreeWalker( doc.body, 128 );
		const open = [];
		const values = [];
		let comment = walker.nextNode();

		while( comment !== null ) {

			const data = comment.data;

			if( data.indexOf('nino:n ') === 0 ) {

				const path = data.slice( 7 );

				open.push( { path : path, start : comment } );
			}
			else if( data === '/nino:n' ) {

				const closed = open.pop();

				if( closed !== undefined && closed.path.indexOf( prefix ) === 0 && /^\d+(?:\.\d+)*$/.test( closed.path.slice( prefix.length ) ) === true ) {

					const elements = [];
					const where = closed.path.slice( prefix.length );

					for( let sibling = closed.start.nextSibling; sibling !== null && sibling !== comment; sibling = sibling.nextSibling )
						if( sibling.nodeType === 1 )
							elements.push( sibling );

					found.all.push( { path : where, elements : elements } );

					if( /^\d+$/.test( where ) === true )
						found.blocks.push( { at : Number( where ), elements : elements } );
				}
			}
			else if( data.indexOf('nino:s ') === 0 ) {
				values.push( { start : comment, written : data.slice( 7 ), path : open.length > 0 ? open[open.length - 1].path : '' } );
			}
			else if( data === '/nino:s' ) {

				const value = values.pop();

				if( value !== undefined && value.path.indexOf( prefix ) === 0 && value.start.parentNode !== null && value.start.parentNode.nodeType === 1 && value.start.parentNode !== doc.body )
					found.texts.push( { element : value.start.parentNode, path : value.path.slice( prefix.length ), written : value.written } );
			}

			comment = walker.nextNode();
		}

		return found;
	};

	// Whether a text is one this panel writes: the page has a place for it in its namespace (or a fixed text), and it is the one that is open
	const entryOf = function( text ) {

		return S.sources.find( function( entry ) { return entry.path === text.path && entry.written === text.written && entry.kind !== 'image' && entry.kind !== 'link' } ) ?? null;
	};

	// The words of a text that is written: the trace comments the drawing puts round a value are no part of it
	const stripped = function( html ) {
		return String( html ).replace( /<!--[\s\S]*?-->/g, '' );
	};

	/**
	 *	Settle a text that was written: the value the element showed at the start is no change, any other is the text of the key in the language of the view
	 *
	 *	@param		{Object}		entry					The source of the page
	 *	@param		{string}		path					Where the call is in the tree
	 *	@param		{boolean}		[reload]			Whether the page in the frame is drawn again: it is where it does not show what was typed
	 *
	 *	@return		{boolean}									Whether it was a change
	 */
	const settle = function( entry, path, value, initial, reload ) {

		if( value === initial )
			return false;

		if( entry.kind === 'fixed' ) {

			const node = nodeAt( path );

			if( node === null || node.a === undefined || node.a.text === value )
				return false;

			change( function() { nodeAt( path ).a.text = value }, reload === true );

			return true;
		}

		// A text that is emptied is drawn as the website would draw it, which the page in the frame is asked for; any other stands there as it was typed
		change( function() {
			S.texts[entry.key] = Object.assign( {}, S.texts[entry.key] ?? {}, { [S.locale] : value } );
		}, reload === true || value.trim() === '' );

		return true;
	};

	// What the element holds as a text: its markup where the key's format has some, else its words; a fixed text is one line
	const valueOf = function( target, entry ) {

		const rich = entry.kind === 'text' && entry.format !== 'plain';
		let value = rich === true ? stripped( target.innerHTML ) : target.textContent;

		if( entry.kind === 'fixed' )
			value = value.replace( /\s+/g, ' ' ).trim();
		else if( rich === true )
			value = value.replace( /(<br\s*\/?>)+$/i, '' );

		return value;
	};

	const endEdit = function( keep ) {

		const editing = S.editing;

		if( editing === null )
			return;

		S.editing = null;

		const target = editing.element;

		target.removeAttribute('contenteditable');
		target.removeEventListener( 'keydown', editing.keys );
		target.removeEventListener( 'blur', editing.blur );
		target.removeEventListener( 'paste', editing.paste );
		target.removeEventListener( 'beforeinput', editing.limit );

		const value = keep === false ? editing.initial : valueOf( target, editing.entry );

		// What stood there is back where the text is not changed - the words of another language, shown for a text that has none, among them
		if( settle( editing.entry, editing.path, value, editing.initial ) === false )
			target.innerHTML = editing.was;
		else
			target.removeAttribute('data-nino-placeholder');
	};

	/**
	 *	Write a text where it stands: the element of the frame is made editable and what is typed is the new text of the key in the language of the view. Enter,
	 *	Tab and Escape end it (Enter in a text of lines or blocks is a line), and Escape takes it back
	 *
	 *	@param		{Object}		text					{ element, path, written }
	 *	@param		{Object}		entry					The source of the page this text is
	 */
	const beginEdit = function( text, entry ) {

		endEdit( true );

		const target = text.element;
		const rich = entry.kind === 'text' && entry.format !== 'plain';
		const lines = rich === true && ( entry.format === 'lines' || entry.format === 'blocks' );
		const empty = entry.kind === 'text' && textOf( entry.key ).trim() === '';
		const editing = { element : target, entry : entry, path : text.path, was : target.innerHTML, initial : empty === true ? '' : valueOf( target, entry ), keys : null, blur : null, paste : null, limit : null };

		if( empty === true )
			target.textContent = '';

		editing.keys = function( event ) {

			if( event.key === 'Escape' ) {
				event.preventDefault();
				endEdit( false );
				return;
			}

			if( event.key === 'Tab' || ( event.key === 'Enter' && ( lines === false || event.ctrlKey === true ) ) ) {

				if( event.key === 'Enter' )
					event.preventDefault();

				endEdit( true );
				return;
			}

			if( event.key === 'Enter' && entry.format === 'lines' ) {
				event.preventDefault();
				target.ownerDocument.execCommand( 'insertLineBreak' );
			}

			// What is typed in a text is no shortcut of the page
			event.stopPropagation();
		};

		editing.blur = function() { endEdit( true ) };

		// A text has its length: what would go beyond it is not typed, and the line says why
		editing.limit = function( event ) {

			if( event.inputType !== undefined && event.inputType.indexOf('insert') === 0 && target.textContent.length + String( event.data ?? '' ).length > MAX_TEXT ) {
				event.preventDefault();

				if( S.view !== null )
					S.view.bar.line.fail( word( 'msg/too-long', MAX_TEXT ) );
			}
		};

		// Pasted text is text, and no longer than a text may be: the markup of what was copied is not the person's
		editing.paste = function( event ) {

			event.preventDefault();

			const given = String( ( event.clipboardData ?? wn.clipboardData ).getData('text/plain') );
			const room = Math.max( 0, MAX_TEXT - target.textContent.length );

			if( given.length > room && S.view !== null )
				S.view.bar.line.fail( word( 'msg/too-long', MAX_TEXT ) );

			target.ownerDocument.execCommand( 'insertText', false, given.slice( 0, room ) );
		};

		target.addEventListener( 'keydown', editing.keys );
		target.addEventListener( 'blur', editing.blur );
		target.addEventListener( 'paste', editing.paste );
		target.addEventListener( 'beforeinput', editing.limit );
		target.setAttribute( 'contenteditable', rich === true ? 'true' : 'plaintext-only' );

		// A browser that does not know plaintext-only takes the text as it is, which the key's own format cleans at the server
		if( rich === false && target.contentEditable !== 'plaintext-only' )
			target.setAttribute( 'contenteditable', 'true' );

		S.editing = editing;
		target.focus();

		const range = target.ownerDocument.createRange();
		const selection = target.ownerDocument.defaultView.getSelection();

		range.selectNodeContents( target );
		range.collapse( false );
		selection.removeAllRanges();
		selection.addRange( range );
	};

	// The union of the boxes of the elements of a block, in the coordinates of the page
	const boxOf = function( elements, win ) {

		let top = Infinity;
		let left = Infinity;
		let right = 0;
		let bottom = 0;

		elements.forEach( function( element ) {

			const box = element.getBoundingClientRect();

			top = Math.min( top, box.top );
			left = Math.min( left, box.left );
			right = Math.max( right, box.right );
			bottom = Math.max( bottom, box.bottom );
		} );

		return top === Infinity ? null : { top : top + win.scrollY, left : left + win.scrollX, width : right - left, height : bottom - top };
	};

	/**
	 *	Hang a bar on each block of the frame: its name and the four things that can be done to it, in an element that has a shadow of its own
	 *
	 */
	const bars = function( frame, blocks, behind ) {

		const doc = frame.contentDocument;
		const win = frame.contentWindow;
		const spots = points();
		const host = doc.createElement('nino-builder-bars');
		const shadow = host.attachShadow( { mode : 'open' } );
		const style = doc.createElement('style');
		const placed = [];
		const pics = [];

		// On a page that is laid out as wide as a tablet the words of the bar are written out, on a phone they are the glyphs, with their names
		const wide = win.innerWidth >= 768;

		style.textContent = BAR_CSS;
		shadow.appendChild( style );
		host.setAttribute( 'role', 'region' );
		host.setAttribute( 'aria-label', word('label/bars') );

		// The button that puts a block in at a place: the words are written out where there is room for them and where a phone has to have them
		const pill = function( text, name, press ) {

			const button = doc.createElement('button');

			button.type = 'button';
			button.className = 'insert';
			button.textContent = text;
			button.title = name;
			button.setAttribute( 'aria-label', name );
			button.addEventListener( 'click', function() { press( button ) } );

			return button;
		};
		const pillOf = function( spot ) {

			const button = pill( word('label/insert-short'), word( spot.after === null && spot.before !== null ? 'label/insert-before' : 'label/insert-after', spot.name ), function( opener ) { openInsert( spot, opener, 'frame' ) } );

			button.dataset.insert = String( spot.b );

			return button;
		};

		blocks.forEach( function( block ) {

			const node = S.top[block.at];

			if( node === undefined || kindOf( node ) !== 'block' )
				return;

			const title = titleOf( node );
			const bar = doc.createElement('div');
			const label = doc.createElement('span');
			const frameBox = doc.createElement('div');

			bar.className = 'bar';
			bar.setAttribute( 'role', 'group' );
			bar.setAttribute( 'aria-label', word( 'label/block-bar', title ) );
			label.className = 'name';
			label.textContent = title;
			label.title = titleOf( node, true );
			frameBox.className = 'frame';

			if( wide === true )
				bar.appendChild( label );

			if( S.frame === false && free( node ) === true )
				ACTIONS.forEach( function( action ) {

					const button = doc.createElement('button');
					const name = word( 'label/'+ action[0] )+ ': '+ title;

					button.type = 'button';
					button.textContent = wide === true && action[2] === true ? action[1]+ ' '+ word( 'label/'+ action[0]+ '-short' ) : action[1];
					button.title = name;
					button.setAttribute( 'aria-label', name );
					button.dataset.action = action[0];
					button.dataset.at = String( block.at );
					button.disabled = possible( block.at, action[0] ) === false;

					// A click and a key press both end here; the dialog of a removal is the panel's, so it is asked from outside the frame
					button.addEventListener( 'click', function() { act( block.at, action[0], S.view.split.frame, 'frame' ) } );
					bar.appendChild( button );
				} );
			else if( S.frame === false ) {

				const lock = doc.createElement('span');

				lock.className = 'name';
				lock.textContent = word('word/locked');
				bar.appendChild( lock );
			}

			const parts = advancedParts( node );

			if( parts !== null ) {

				const more = doc.createElement('button');
				const name = word('label/advanced')+ ': '+ title;

				more.type = 'button';
				more.textContent = word('label/advanced');
				more.title = name;
				more.setAttribute( 'aria-label', name );
				more.dataset.action = 'advanced';
				more.dataset.at = String( block.at );
				more.addEventListener( 'click', function() { Nino.admin.builder.go( parts, { focus : 'back' } ) } );
				bar.appendChild( more );
			}

			const first = spots.find( function( spot ) { return spot.block === block.at && spot.carry === 'before' } );
			const last = spots.find( function( spot ) { return spot.block === block.at && spot.carry === 'after' } );
			const pills = { before : first === undefined ? null : pillOf( first ), after : last === undefined ? null : pillOf( last ) };

			shadow.appendChild( frameBox );

			if( pills.before !== null )
				shadow.appendChild( pills.before );

			shadow.appendChild( bar );

			if( pills.after !== null )
				shadow.appendChild( pills.after );

			placed.push( { block : block, bar : bar, box : frameBox, pills : pills } );
		} );

		// A page of free markup only has its places in the frame too: the first under the header of the site, the others above its footer
		const loose = spots.filter( function( spot ) { return spot.block === null } ).map( function( spot ) {

			const button = pillOf( spot );

			shadow.appendChild( button );

			return button;
		} );

		// The picture a call reads as an attribute: a button in the foot corner of the call
		behind.forEach( function( item ) {

			const button = pill( word('picture/replace-title'), word( 'picture/replace-of', item.target.label ), function( opener ) {

				if( S.stale === false && S.editing === null )
					openImage( item.target, opener );
			} );

			button.dataset.image = item.target.key;
			shadow.appendChild( button );
			pics.push( { button : button, element : item.element } );
		} );

		// The page is laid out at a width and scaled into the pane where the person chose a width the pane has not: a bar is scaled back, so that it is as large
		// as the workbench's own controls. It stands in the top corner on the right, inside the block, where a block has the room; one that is low - a header - has it
		// below its edge, where it covers the top of the next, and never the logo or the menu
		const place = function() {

			const scale = win.innerWidth > 0 ? frame.getBoundingClientRect().width / win.innerWidth : 1;
			const back = scale > 0 && scale < 1 ? 1 / scale : 1;

			pics.forEach( function( pic ) {

				const box = boxOf( [ pic.element ], win );

				pic.button.style.display = box === null ? 'none' : '';

				if( box === null )
					return;

				pic.button.style.transformOrigin = '0 0';
				pic.button.style.transform = back === 1 ? '' : 'scale('+ back+ ')';
				pic.button.style.top = ( box.top + box.height - pic.button.offsetHeight * back - 8 * back )+ 'px';
				pic.button.style.left = Math.max( box.left, box.left + box.width - pic.button.offsetWidth * back - 8 * back )+ 'px';
			} );

			loose.forEach( function( button, which ) {

				const header = doc.querySelector('header');
				const footer = doc.querySelector('footer');

				button.style.transformOrigin = '0 0';
				button.style.transform = back === 1 ? '' : 'scale('+ back+ ')';
				button.style.left = ( 8 * back )+ 'px';

				if( which === 0 )
					button.style.top = ( ( header === null ? 0 : header.getBoundingClientRect().bottom + win.scrollY ) + 4 * back )+ 'px';
				else
					button.style.top = ( ( footer === null ? doc.documentElement.scrollHeight : footer.getBoundingClientRect().top + win.scrollY ) - button.offsetHeight * back - 4 * back )+ 'px';
			} );

			placed.forEach( function( item ) {

				const box = boxOf( item.block.elements, win );

				const hidden = box === null ? 'none' : '';

				[ item.pills.before, item.pills.after ].forEach( function( pill ) {
					if( pill !== null )
						pill.style.display = hidden;
				} );

				if( box === null ) {
					item.bar.style.display = 'none';
					item.box.style.display = 'none';
					return;
				}

				item.bar.style.display = '';
				item.box.style.display = '';
				item.box.style.top = box.top+ 'px';
				item.box.style.left = box.left+ 'px';
				item.box.style.width = box.width+ 'px';
				item.box.style.height = box.height+ 'px';
				item.bar.style.transformOrigin = '0 0';
				item.bar.style.transform = back === 1 ? '' : 'scale('+ back+ ')';
				item.bar.style.maxWidth = Math.max( 120, ( box.width - 16 * back ) / back )+ 'px';

				const width = item.bar.offsetWidth * back;
				const height = item.bar.offsetHeight * back;
				const low = box.height < 120 * back;

				item.bar.style.top = ( low === true ? box.top + box.height + 2 * back : box.top + 8 * back )+ 'px';
				item.bar.style.left = Math.max( box.left, box.left + box.width - width - 8 * back )+ 'px';
				item.height = height;

				// The places for a block stand on the left, where the bar is not: the one before the first block of a run inside the top of it, the one behind a block
				// at its foot - or, where the block is low, below it like its bar
				[ item.pills.before, item.pills.after ].forEach( function( pill, which ) {

					if( pill === null )
						return;

					pill.style.transformOrigin = '0 0';
					pill.style.transform = back === 1 ? '' : 'scale('+ back+ ')';
					pill.style.left = ( box.left + 8 * back )+ 'px';
					pill.style.top = ( which === 0 ? box.top + 4 * back : ( low === true ? box.top + box.height + 2 * back : box.top + box.height - pill.offsetHeight * back - 4 * back ) )+ 'px';
				} );
			} );
		};

		doc.body.appendChild( host );
		place();
		S.place = place;

		if( typeof win.ResizeObserver === 'function' )
			new win.ResizeObserver( place ).observe( doc.body );

		win.addEventListener( 'resize', place );
		win.addEventListener( 'load', place );

		if( S.refocus !== null && S.refocus.from === 'frame' ) {

			const wanted = S.refocus;

			const target = shadow.querySelector( 'button[data-at="'+ wanted.at+ '"][data-action="'+ wanted.action+ '"]:not(:disabled)' ) ?? shadow.querySelector( 'button[data-at="'+ wanted.at+ '"]:not(:disabled)' );

			// The way back from the extended view waits for the drawing that has the block: the page that is loaded first need not be that one
			S.refocus = target === null && wanted.action === 'advanced' ? wanted : null;

			if( target !== null )
				target.focus();
		}

		imageFocus( doc );
	};

	// After the dialog of a picture the focus stands on the picture or its button, once the page is drawn again
	const imageFocus = function( doc ) {

		const back = S.imageBack;
		const host = doc.querySelector('nino-builder-bars');

		if( back === '' || host === null )
			return;

		S.imageBack = '';
		( host.shadowRoot.querySelector( 'button[data-image="'+ back+ '"]' ) ?? Array.from( doc.querySelectorAll('[data-nino-image]') ).find( function( element ) { return element.getAttribute('data-nino-image') === back } ) )?.focus();
	};

	/**
	 *	What a page that was loaded in the frame becomes: its texts can be written and its blocks have their bars. Only a page that is the draft of the last
	 *	answer is touched - the page as it was before the first answer, or one the person navigated to, has no trace and is left as it is
	 *
	 */
	const instrument = function( frame ) {

		let doc = null;

		try {
			doc = frame.contentDocument;
		} catch( error ) {
			return;
		}

		if( doc === null || doc.body === null || S.pending === null || S.ready === false )
			return;

		S.sources = S.pending;
		S.stale = false;
		S.editing = null;

		if( S.mode === 'advanced' ) {
			instrumentAdvanced( frame, doc );
			return;
		}

		const found = traced( doc );
		const style = doc.createElement('style');

		style.textContent = FRAME_CSS;
		doc.head.appendChild( style );

		found.texts.forEach( function( text ) {

			const entry = entryOf( text );

			if( entry === null || entry.editable !== true ) {
				text.element.setAttribute( 'data-nino-locked', '' );
				text.element.title = word('word/not-here');
				return;
			}

			text.element.setAttribute( 'data-nino-text', '' );

			// A text the language of the view has none of shows the text of another language: it is told from one that is written
			if( entry.kind === 'text' && textOf( entry.key ).trim() === '' )
				text.element.setAttribute( 'data-nino-placeholder', '' );

			text.element.title = word('hint/write');
			text.element.tabIndex = 0;
			text.element.addEventListener( 'click', function() {

				if( S.stale === false && S.editing === null )
					beginEdit( text, entry );
			} );
			text.element.addEventListener( 'keydown', function( event ) {

				if( event.key === 'Enter' && S.editing === null && S.stale === false ) {
					event.preventDefault();
					beginEdit( text, entry );
				}
			} );
		} );

		// A picture of a slot of the page is changed where it stands, by every step that may change the content: a click, Enter or Space opens its dialog. A picture a call reads
		// as an attribute - the background of a section - has a button of its own in the corner of that call
		const behind = [];

		pictureTargets().forEach( function( target ) {

			const where = found.all.find( function( entry ) { return entry.path === target.path } );
			const element = where === undefined ? undefined : where.elements[0];

			if( element === undefined )
				return;

			if( target.attribute === true ) {
				behind.push( { target : target, element : element } );
				return;
			}

			element.setAttribute( 'data-nino-image', target.key );
			element.setAttribute( 'role', 'button' );
			element.setAttribute( 'aria-label', word( 'picture/replace-of', target.label ) );
			element.title = word('picture/replace-title');
			element.tabIndex = 0;
			element.addEventListener( 'click', function() {

				if( S.stale === false && S.editing === null )
					openImage( target, element );
			} );
			element.addEventListener( 'keydown', function( event ) {

				if( ( event.key === 'Enter' || event.key === ' ' ) && S.editing === null && S.stale === false ) {
					event.preventDefault();
					openImage( target, element );
				}
			} );
		} );

		// The page is a preview: nothing in it goes anywhere or is sent, and the keys of the page are the texts and the bars first
		doc.querySelectorAll('a, button, input, select, textarea, summary, [tabindex]').forEach( function( element ) {

			if( element.hasAttribute('data-nino-text') === false && element.hasAttribute('data-nino-image') === false )
				element.tabIndex = -1;
		} );
		doc.addEventListener( 'click', function( event ) {

			if( event.target.closest && event.target.closest('a') !== null )
				event.preventDefault();
		}, true );
		doc.addEventListener( 'submit', function( event ) { event.preventDefault() }, true );
		doc.addEventListener( 'keydown', function( event ) { keys( event, found.blocks, doc ) } );

		bars( frame, found.blocks, behind );
	};

	// The keys of the page in the frame: the undo and the save as in the workbench, and Alt and an arrow moves the block the focus is in
	const keys = function( event, blocks, doc ) {

		if( S.editing !== null )
			return;

		const mod = event.ctrlKey === true || event.metaKey === true;
		const key = String( event.key ).toLowerCase();

		if( mod === true && key === 'z' ) {
			event.preventDefault();
			event.shiftKey === true ? redo() : undo();
		}
		else if( mod === true && key === 'y' ) {
			event.preventDefault();
			redo();
		}
		else if( mod === true && key === 's' ) {
			event.preventDefault();
			S.view.bar.press();
		}
		else if( event.altKey === true && ( event.key === 'ArrowUp' || event.key === 'ArrowDown' ) ) {

			const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
			const origin = path.length > 0 ? path[0] : event.target;
			let at = origin.dataset !== undefined && origin.dataset.at !== undefined ? Number( origin.dataset.at ) : -1;

			if( at === -1 )
				blocks.forEach( function( block ) {
					if( block.elements.some( function( element ) { return element.contains( origin ) } ) === true )
						at = block.at;
				} );

			if( at !== -1 ) {
				event.preventDefault();
				move( at, event.key === 'ArrowUp' ? -1 : 1 );
			}
		}

		return doc;
	};

	// --- the screen of a page ---------------------------------------------------------------------------------------

	const paint = function() {

		const view = S.view;

		if( view === null )
			return;

		if( S.mode === 'advanced' ) {
			paintAdvanced();
			return;
		}

		paintBar( view );
		view.buttons.undo.disabled = S.undo.length === 0;
		view.buttons.redo.disabled = S.redo.length === 0;
		fill( view.split.settings, outline() );
	};

	// The outline is drawn again after every change; the focus stays where the action was done - on the button of the row that is the block now, or on the
	// row that took its place where it was taken out - and a focus that is elsewhere in it is put back where it was
	const fill = function( parent, piece ) {

		const held = dc.activeElement;
		const inside = parent.contains( held ) === true ? ( held.dataset.action ?? '' ) : '';
		const row = inside !== '' ? held.closest('li') : null;
		const at = row !== null ? row.dataset.at : '';
		const wanted = S.refocus !== null && S.refocus.from === 'outline' ? S.refocus : null;

		parent.innerHTML = '';
		parent.appendChild( piece );

		if( wanted !== null ) {

			S.refocus = null;

			const rows = Array.from( parent.querySelectorAll('li[data-at]') ).filter( function( candidate ) { return candidate.querySelector('button:not(:disabled)') !== null } );
			const target = rows.find( function( candidate ) { return Number( candidate.dataset.at ) === wanted.at } ) ?? rows.find( function( candidate ) { return Number( candidate.dataset.at ) >= wanted.at } ) ?? rows[rows.length - 1];

			if( target !== undefined )
				( target.querySelector( 'button[data-action="'+ wanted.action+ '"]:not(:disabled)' ) ?? target.querySelector('button:not(:disabled)') ).focus();
		}
		else if( inside !== '' ) {

			const again = parent.querySelector( 'li[data-at="'+ at+ '"] button[data-action="'+ inside+ '"]:not(:disabled)' );

			if( again !== null )
				again.focus();
		}
	};

	// --- the dialog "Insert block" ----------------------------------------------------------------------------------

	// Where a block comes from, as a chip says it: the label the kernel gives the origin
	const originWord = function( origin ) {
		return localized( origin.label );
	};

	// The frame of a tile, once there is an address for it: the page with the block in it, laid out as a desktop lays it out and scaled into the box
	const tileFrame = function( box, held ) {

		const frame = el( 'iframe', 'builder-tile-frame' );

		// A picture, not a page: no script of the site runs in it, and nothing in it reaches the workbench - the frame has no right at all
		frame.setAttribute( 'sandbox', '' );
		frame.setAttribute( 'loading', 'lazy' );
		frame.setAttribute( 'tabindex', '-1' );
		frame.setAttribute( 'aria-hidden', 'true' );
		frame.setAttribute( 'src', S.dir+ held.address+ ( held.address.indexOf('?') === -1 ? '?' : '&' )+ held.query );
		box.appendChild( frame );
		Nino.adminUi.scaleFrame( frame, box, TILE.width, TILE.height );
	};

	/**
	 *	The picture of a tile is asked for when its box is on screen, and once for as long as the draft holds. The server keeps the newest TILES drafts of the session and
	 *	drops the others, so what is held here is the newest as well
	 *
	 */
	const tileAsk = function( box ) {

		const key = box.dataset.block+ '|'+ S.locale;
		const held = S.tiles[key];

		if( box.querySelector('iframe') !== null )
			return;

		if( held !== undefined && Date.now() < held.until ) {
			tileFrame( box, held );
			return;
		}

		api( 'tile', { key : box.dataset.block, token : held === undefined ? '' : held.token }, function( status, response ) {

			if( status !== 200 )
				return;

			delete S.tiles[key];
			S.tiles[key] = { token : response.token, query : response.query, address : response.address, until : Date.now() + response.ttl * 1000 - GRACE };

			Object.keys( S.tiles ).slice( 0, Math.max( 0, Object.keys( S.tiles ).length - TILES ) ).forEach( function( old ) { delete S.tiles[old] } );

			if( box.isConnected !== false && box.querySelector('iframe') === null )
				tileFrame( box, S.tiles[key] );
		} );
	};

	// A variant of a hint, as the register labels it, else as the panel does - '' where it has no word: a key is none for a person
	const hintWord = function( hint, which ) {
		return labelOfVariant( { [hint[which]] : ( hint.labels ?? {} )[which] }, hint[which] );
	};

	/**
	 *	A block of the dialog: the button that puts it in, with its picture, its name, what it is for, where it comes from and - where the theme does not have a
	 *	variant it names - the one it appears as. A block that is left out because a feature is off is no button: it says which one
	 *
	 */
	const tileOf = function( entry, pick, drop ) {

		const item = el( 'li', 'builder-tile'+ ( entry.off === null ? '' : ' builder-tile--off' ) );
		const thumb = el( 'span', 'builder-tile-thumb' );
		const text = el( 'span', 'builder-tile-text' );
		const name = el( 'strong', 'builder-tile-name', localized( entry.name ) );
		const about = el( 'span', 'nino-admin-hint builder-tile-about', localized( entry.description ) );
		const chips = el( 'span', 'builder-tile-chips' );
		const note = entry.off !== null ? word( 'insert/off', entry.off.map( localized ).join(', ') ) : ( entry.hint === null ? '' : ( hintWord( entry.hint, 'variant' ) === '' || hintWord( entry.hint, 'standard' ) === '' ? word('insert/variant-plain') : word( 'insert/variant', hintWord( entry.hint, 'variant' ), hintWord( entry.hint, 'standard' ) ) ) );
		const described = [];

		thumb.setAttribute( 'aria-hidden', 'true' );
		chips.appendChild( el( 'span', 'nino-admin-badge', originWord( entry.origin ) ) );
		about.id = 'builder-tile-'+ entry.key+ '-about';

		if( localized( entry.description ) !== localized( entry.name ) )
			described.push( about.id );
		text.appendChild( name );

		if( localized( entry.description ) !== localized( entry.name ) )
			text.appendChild( about );
		text.appendChild( chips );

		if( note !== '' ) {

			const line = el( 'span', 'nino-admin-hint builder-tile-note', note );

			line.id = 'builder-tile-'+ entry.key+ '-note';
			described.push( line.id );
			text.appendChild( line );
		}

		if( entry.off !== null ) {
			item.appendChild( thumb );
			item.appendChild( text );
			return item;
		}

		const button = el( 'button', 'builder-tile-pick' );

		thumb.dataset.block = entry.key;
		button.type = 'button';
		button.dataset.block = entry.key;
		button.setAttribute( 'aria-label', word( 'insert/pick', localized( entry.name ) ) );
		button.setAttribute( 'aria-describedby', described.join(' ') );
		button.addEventListener( 'click', function() { pick( entry ) } );
		button.appendChild( thumb );
		button.appendChild( text );
		item.appendChild( button );

		// A block of the project is taken away from here, after the question
		if( entry.own === true && S.level >= 2 )
			item.appendChild( Nino.adminUi.button( word('insert/drop'), function() { drop( entry ) }, { className : 'nino-admin-btn--small builder-tile-drop', name : word( 'insert/drop-of', localized( entry.name ) ) } ) );

		return item;
	};

	/**
	 *	The dialog: the categories as tabs - the arrows walk them - and the blocks of the one that is chosen, each as a tile. A block that is chosen closes the dialog,
	 *	is asked for and put in; Cancel and Escape give the focus back to what opened it
	 */
	const insertDialog = function( catalog, place, opener, from ) {

		const body = el( 'div', 'builder-insert-body' );
		const strip = el( 'div', 'nino-admin-tabs nino-admin-tabs--bar builder-cats' );
		const panel = el( 'div', 'nino-admin-tabpanel builder-tiles' );
		const by = {};
		const buttons = {};
		let picked = null;
		let looking = null;

		catalog.categories.forEach( function( category ) { by[category] = [] } );
		catalog.blocks.forEach( function( entry ) {
			if( by[entry.category] !== undefined )
				by[entry.category].push( entry );
		} );

		const cats = [ 'all' ].concat( catalog.categories.filter( function( category ) { return by[category].length > 0 } ) );

		by.all = catalog.blocks.filter( function( entry ) { return by[entry.category] !== undefined } );

		const close = function( entry ) {

			picked = entry;

			const dialog = body.closest('dialog');

			if( dialog !== null )
				dialog.close();
		};

		const draw = function( category ) {

			S.category = category;

			if( looking !== null )
				looking.disconnect();

			panel.innerHTML = '';
			panel.setAttribute( 'aria-labelledby', 'builder-cat-'+ category );

			const list = el( 'ul', 'builder-tilelist' );

			by[category].forEach( function( entry ) { list.appendChild( tileOf( entry, close, function( own ) { close( { drop : own } ) } ) ) } );
			panel.appendChild( list );

			const boxes = Array.from( list.querySelectorAll('.builder-tile-thumb[data-block]') );

			looking = typeof wn.IntersectionObserver === 'function' ? new wn.IntersectionObserver( function( seen ) {

				seen.forEach( function( entry ) {

					if( entry.isIntersecting === true ) {
						looking.unobserve( entry.target );
						tileAsk( entry.target );
					}
				} );
			}, { rootMargin : '120px' } ) : null;

			boxes.forEach( function( box ) {
				if( looking === null )
					tileAsk( box );
				else
					looking.observe( box );
			} );
		};

		body.appendChild( el( 'p', 'nino-admin-hint builder-insert-note', word('insert/note') ) );

		if( by.all.length === 0 )
			body.appendChild( Nino.adminUi.emptyState( word('insert/empty') ) );
		else {

			strip.setAttribute( 'role', 'tablist' );
			strip.setAttribute( 'aria-label', word('insert/categories') );
			panel.id = 'builder-cat-panel';
			panel.setAttribute( 'role', 'tabpanel' );

			cats.forEach( function( category ) {

				const tab = el( 'button', 'nino-admin-tab builder-cat', word( 'insert/cat-'+ category ) );

				tab.type = 'button';
				tab.id = 'builder-cat-'+ category;
				tab.setAttribute( 'role', 'tab' );
				tab.setAttribute( 'aria-controls', 'builder-cat-panel' );
				buttons[category] = tab;
				strip.appendChild( tab );
			} );

			const active = cats.indexOf( S.category ) !== -1 ? S.category : cats[0];

			Nino.adminUi.buttonRow( buttons, active, draw, 'aria-selected' );
			body.appendChild( strip );
			body.appendChild( panel );
			draw( active );
		}

		Nino.adminUi.choiceDialog( {
			title			: word('insert/title'),
			message		: word('insert/note'),
			content		: body,
			className	: 'nino-admin-dialog--manual builder-insert-dialog',
			opener		: opener,
			choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function() {

				if( looking !== null )
					looking.disconnect();

				if( picked !== null && picked.drop !== undefined )
					askDrop( picked.drop, place, opener, from );
				else if( picked !== null )
					put( picked, place, from );
			},
		} );

		// The dialog has its own scrolling content, where the browser puts the focus: the arrows are the tabs' - the active one has it
		if( buttons[S.category] !== undefined )
			buttons[S.category].focus();
	};

	// A block of the project is taken away after a question - a page that has it keeps its copy - and the dialog opens again without it
	const askDrop = function( entry, place, opener, from ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/drop-title'),
			message	: word( 'confirm/drop-message', localized( entry.name ) ),
			yes			: word('insert/drop'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose : function( ok ) {

				if( ok === false ) {
					openInsert( place, opener, from );
					return;
				}

				api( 'blockdrop', { key : entry.key }, function( status, response ) {

					if( status !== 200 )
						S.view.bar.line.error( status, response );
					else {
						S.catalog.blocks = S.catalog.blocks.filter( function( block ) { return block.key !== entry.key } );
						S.view.bar.line.idle( word( 'msg/dropped', localized( entry.name ) ) );
					}

					openInsert( place, opener, from );
				} );
			},
		} );
	};

	// The block that was chosen is asked for - its tree, its texts in every language - and put in; what the server says of it is what stands
	const put = function( entry, place, from ) {

		api( 'block', { key : entry.key }, function( status, response ) {

			if( status !== 200 ) {
				S.view.bar.line.error( status, response );
				return;
			}

			if( insert( place, response, from, entry.name ) === true )
				S.view.bar.line.idle( word( 'msg/inserted', localized( entry.name ) ) );
			else
				S.view.bar.line.fail( word('error/insert') );
		} );
	};

	/**
	 *	Open the dialog at a place: the blocks are asked for once for a page that is open, and kept
	 *
	 *	@param		{Object}		place					One of points()
	 *	@param		{Element}		opener				What has the focus back when the dialog is closed
	 *	@param		{string}		from					Where the action was started: outline or frame
	 */
	const openInsert = function( place, opener, from ) {

		if( S.frame === true || S.view === null )
			return;

		if( S.catalog !== null ) {
			insertDialog( S.catalog, place, opener, from );
			return;
		}

		api( 'blocks', {}, function( status, response ) {

			if( status !== 200 ) {
				S.view.bar.line.fail( Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/blocks' ) );
				return;
			}

			S.catalog = response;
			insertDialog( response, place, opener, from );
		} );
	};

	const api = function( action, payload, done, extra ) {
		Nino.admin.builder.call( action, Object.assign( { template : S.template }, payload ), done, extra );
	};

	// The pictures a person chose, as the server takes them: the slot a source reads and the file that was made for it
	const picturesNow = function() {

		const out = {};

		Object.keys( S.pictures ).forEach( function( uri ) {
			out[uri] = { label : S.pictures[uri].label, width : S.pictures[uri].width, height : S.pictures[uri].height };

			if( typeof S.pictures[uri].file === 'string' )
				out[uri].file = S.pictures[uri].file;
		} );

		return out;
	};

	// The files that were uploaded and are given up - the draft is thrown away, the page is saved and some were not the ones given -: the server takes away the ones no slot reads
	const release = function() {

		const files = S.staged;

		S.staged = [];

		if( files.length > 0 && S.template !== '' )
			api( 'unstage', { files : files }, function() {} );
	};

	// What the server answers after a save or a restore, and an open: the page as it stands now
	const take = function( response ) {

		S.top = normalize( fixAttrs( response.tree ) );
		S.level = response.level ?? S.level;
		S.schemas = response.schemas ?? S.schemas;
		S.findings = response.findings ?? {};
		S.links = response.links ?? S.links;
		learn( response.schemas ?? {} );
		S.texts = response.texts;
		S.known = Object.assign( {}, S.known, response.known );
		S.stamp = response.stamp;
		S.last = response.last ?? 0;
		S.unback = response.unback ?? '';
		S.sources = response.sources;
		S.pending = response.sources;
		S.pictures = {};
		S.saved = stateOf();
		S.savedTexts = clone( S.texts );
		S.copies = {};
		S.inserted = {};
		S.images = response.images ?? S.images;
		S.oldIds = idsOf( S.top, new Set() );

		if( Array.isArray( response.slots ) === true )
			S.slots = response.slots;
	};

	// The words that are not the ones the server holds: a key that is new, a language whose text was changed - the rest stays as it was written
	const changedTexts = function() {

		const out = {};

		Object.keys( S.texts ).forEach( function( key ) {
			Object.keys( S.texts[key] ).forEach( function( locale ) {

				if( S.savedTexts[key] === undefined || S.savedTexts[key][locale] !== S.texts[key][locale] ) {
					out[key] = out[key] ?? {};
					out[key][locale] = S.texts[key][locale];
				}
			} );
		} );

		return out;
	};

	const send = function( token, done ) {
		api( 'preview', { tree : S.top, texts : S.texts, slots : S.copies, pictures : picturesNow(), inserted : insertedNow(), token : token, look : S.mode === 'advanced' ? S.look : null, page : S.page }, function( status, response ) {

			if( status === 200 ) {
				S.pending = response.sources;
				S.address = response.address;

				if( S.mode === 'advanced' ) {

					S.findings = response.findings ?? {};
					S.notation = response.notation ?? {};
					refreshNotation();

					if( S.adv.view === 'tree' && S.tree !== null )
						S.tree.update( itemsNow() );
				}
			}

			done( status, response );
		} );
	};

	const save = function( done ) {

		const forced = S.force;
		const pressed = S.view !== null && dc.activeElement === S.view.bar.save;

		S.force = false;
		endEdit( true );

		api( 'save', { tree : S.top, texts : changedTexts(), slots : S.copies, pictures : picturesNow(), inserted : insertedNow(), base : S.stamp, force : forced }, function( status, response ) {

			if( status === 200 ) {

				release();
				take( response );
				S.view.bar.line.saved();
				S.view.preview.schedule();
				done( true );
				paint();

				// The pressed button is off now and takes the focus with it: it goes to the state of the bar
				if( pressed === true && ( dc.activeElement === null || dc.activeElement === dc.body ) ) {
					S.view.bar.state.tabIndex = -1;
					S.view.bar.state.focus();
				}

				return;
			}

			if( status === 409 && ( response ?? {} ).code === 'builder_changed' ) {

				done( false );
				Nino.adminUi.conflict( {
					message			: Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/save' ),
					opener			: S.view.bar.save,
					onOverwrite : function() {
						S.force = true;
						S.view.bar.press();
					},
					onReload		: function() { openPage( S.target, function() {} ) },
					onCancel		: function() { S.view.bar.line.dirty() },
				} );
				return;
			}

			S.view.bar.line.error( status, response );
			done( false );
		} );
	};

	// The last state takes the place of this one, and this one becomes the last: the question is asked, as it is where the Themes do the same
	const askRestore = function( opener ) {

		const frame = S.frame === true ? nameOfPage( S.template ) : '';

		Nino.adminUi.confirm( 'destructive', {
			title		: frame === '' ? word('confirm/restore-title') : word( 'confirm/restore-title-frame', frame ),
			message	: frame === '' ? word('confirm/restore-message') : word( 'confirm/restore-message-frame', frame ),
			yes			: word('label/restore'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose : function( ok ) {
				if( ok === true )
					restore( opener );
			},
		} );
	};

	const restore = function( opener ) {

		api( 'restore', { base : S.stamp, force : S.force }, function( status, response ) {

			S.force = false;

			if( status === 200 ) {

				S.undo = [];
				S.redo = [];
				take( response );
				S.view.bar.line.idle( word('msg/restored') );
				S.view.preview.flush();
				paint();
				return;
			}

			if( status === 409 && ( response ?? {} ).code === 'builder_changed' ) {

				Nino.adminUi.conflict( {
					message			: Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/save' ),
					opener			: opener,
					onOverwrite : function() {
						S.force = true;
						restore( opener );
					},
					onReload		: function() { openPage( S.target, function() {} ) },
					onCancel		: function() {},
				} );
				return;
			}

			S.view.bar.line.error( status, response );
		} );
	};

	// The name of a language in that language - Deutsch, English - with its code behind it, or alone where plain
	const languageName = function( locale, plain ) {

		const code = locale.split('_')[0];
		let name = '';

		try {
			name = new Intl.DisplayNames( [ code ], { type : 'language' } ).of( code ) ?? '';
		} catch( error ) {
			name = '';
		}

		return name === '' || name === code ? locale : name.charAt( 0 ).toUpperCase()+ name.slice( 1 )+ ( plain === true ? '' : ' ('+ locale+ ')' );
	};

	// The language the page is written in: the working language of the workbench, which is changed there and is the one the other panels write in
	const switchLanguage = function( locale, select ) {

		endEdit( true );

		Nino.adminUi.api.call( 'admin/locale', { locale : locale }, function( status, response ) {

			if( status !== 200 ) {
				select.value = S.locale;
				S.view.bar.line.error( status, response );
				return;
			}

			S.locale = locale;
			Nino.admin.sessionLocale.current = locale;
			S.stale = true;
			paint();
			S.view.preview.flush();
		} );
	};

	// The widths of the preview, the language of the page and the outline, over the frame
	const tools = function( basic ) {

		const box = el( 'div', 'builder-tools' );
		const caption = el( 'span', 'nino-admin-hint builder-caption', word('word/width') );
		const widths = Nino.adminUi.steps( {
			labelledby	: 'builder-width-label',
			steps				: WIDTHS.map( function( name ) { return { value : name, label : word( 'word/'+ name ) } } ),
			value				: S.width,
			onChange		: function( name ) {
				S.width = name;
				S.chosen = true;
				S.view.preview.width( name );
			},
		} );

		caption.id = 'builder-width-label';
		widths.el.classList.add('builder-widths');

		if( basic === true ) {

			const outlineToggle = Nino.adminUi.button( word('label/outline'), function() {

				const root = S.view.split.el;
				const open = root.dataset.outline !== 'open';

				root.dataset.outline = open === true ? 'open' : 'closed';
				outlineToggle.setAttribute( 'aria-expanded', String( open ) );
			}, { className : 'nino-admin-btn--small builder-outline-toggle', id : 'builder-outline-toggle' } );

			outlineToggle.setAttribute( 'aria-expanded', 'false' );
			box.appendChild( outlineToggle );
		}

		const choices = S.locales.filter( function( entry ) { return entry.active === true || entry.locale === S.locale } );

		if( choices.length > 1 ) {

			const select = el( 'select', 'nino-admin-input builder-language' );

			select.id = 'builder-language';
			select.setAttribute( 'aria-label', word('label/language') );
			choices.forEach( function( entry ) {

				const option = el( 'option', '', languageName( entry.locale ) );

				option.value = entry.locale;
				select.appendChild( option );
			} );
			select.value = S.locale;
			select.addEventListener( 'change', function() { switchLanguage( select.value, select ) } );
			box.appendChild( select );
		}
		else
			box.appendChild( el( 'span', 'nino-admin-badge builder-language', languageName( S.locale ) ) );

		const sized = el( 'div', 'builder-widthbox' );

		sized.appendChild( caption );
		sized.appendChild( widths.el );
		box.appendChild( sized );

		return { el : box, widths : widths };
	};

	// A button of the bar that has done its work and has nothing left to do is no place for the focus: it goes to the one that undoes it, or to Save
	const settle2 = function( pressed, other ) {

		const buttons = S.view.buttons;
		const to = buttons[pressed].disabled === false ? buttons[pressed] : ( buttons[other].disabled === false ? buttons[other] : S.view.bar.save );

		to.focus();
	};

	// The width the page is laid out at, for each width of the preview
	const layoutsOf = function( advanced ) {
		return advanced === true ? Object.assign( {}, LAYOUT, { desktop : DESKTOP } ) : LAYOUT;
	};

	// Where "back" goes from the view, and whether it keeps the draft: the extended view of a page is one draft with the page; that of a frame goes to the page it was opened from
	const backOf = function( advanced ) {

		if( advanced === false )
			return { parts : [], keep : false };

		return S.frame === true ? { parts : [ S.page !== '' ? S.page : addressOf( S.template ) ], keep : false } : { parts : [ S.adv.slug ], keep : true };
	};

	const build = function( level ) {

		level.innerHTML = '';

		// The extended view goes back to the page it is of - or the frame -, the page to the list
		const advanced = S.mode === 'advanced';
		const layouts = layoutsOf( advanced );
		const home = backOf( advanced ).parts;
		const back = el( 'a', 'nino-admin-back-link', advanced === true ? word('label/back-page') : word('label/back-list') );

		back.href = Nino.admin.router.href( 'builder', home );
		back.id = 'builder-back-list';

		if( backOf( advanced ).keep === true )
			back.setAttribute( 'data-keep-draft', '' );

		back.addEventListener( 'click', function( event ) {

			event.preventDefault();

			// The focus goes back to where the person came from: the block that was opened, or the title of the page a frame was opened from
			if( advanced === true && S.frame === false )
				S.refocus = { at : rootAt(), action : 'advanced', from : 'frame' };

			Nino.admin.builder.go( home, advanced === true && S.frame === true ? { focus : 'page' } : undefined );
		} );

		const head = el( 'div', 'builder-head' );
		const title = el( 'h3', 'builder-name', advanced === true ? blockTitle() : S.name );
		const toolbox = tools( advanced === false );
		const settings = el( 'div', 'builder-column' );
		const buttons = {};

		const bar = Nino.adminUi.saveBar( {
			id			: 'builder-bar',
			buttons	: [
				{ key : 'undo', label : '\u21B6 '+ word('label/undo'), name : word('label/undo'), onPress : function() { undo(); settle2( 'undo', 'redo' ) } },
				{ key : 'redo', label : '\u21B7 '+ word('label/redo'), name : word('label/redo'), onPress : function() { redo(); settle2( 'redo', 'undo' ) } },
			],
			onSave		: save,
			onRestore	: askRestore,
		} );

		buttons.undo = bar.buttons.undo;
		buttons.redo = bar.buttons.redo;

		const split = Nino.adminUi.split( {
			id			: 'builder-split',
			settings	: settings,
			tools		: toolbox.el,
			preview		: { src : 'about:blank', title : word('label/preview-title'), width : layouts[S.width] ?? undefined },
			onLoad		: instrument,
			onView		: function( view ) {

				if( view === 'preview' && S.chosen === false && split.frame.parentNode.getBoundingClientRect().width < NARROW ) {
					S.width = 'mobile';
					toolbox.widths.set( 'mobile' );
					S.view.preview.width( 'mobile' );
				}
			},
		} );

		const preview = Nino.adminUi.previewFrame( {
			split		: split,
			send		: send,
			widths	: layouts,
			address	: function( query ) { return S.dir+ S.address+ ( S.address.indexOf('?') === -1 ? '?' : '&' )+ query },
			onError	: function( status, response ) { bar.line.error( status, response ) },
		} );

		if( typeof wn.ResizeObserver === 'function' )
			new wn.ResizeObserver( function() { if( S.place !== null ) S.place() } ).observe( split.frame.parentNode );

		// The outline of the page stands beside it only where the person opens it; the tree of a block is the column of the extended view
		split.el.dataset.outline = advanced === true ? 'open' : 'closed';
		S.view = { level : level, split : split, preview : preview, bar : bar, buttons : buttons, title : title, kind : S.mode };

		split.words( { settings : word('label/blocks'), preview : word('label/preview') } );
		head.appendChild( title );

		// The frames of the site stand as a slim line over and under the page, as they do in the page: they hold for every page and are written in a page of their own
		const frameRow = function( part, label ) {

			const row = el( 'div', 'builder-frame-row' );
			const link = el( 'a', 'builder-frame-link', word('label/edit') );

			// The frame opens in the extended view, on this page: its blocks are not what is written on the page
			const parts = [ addressOf( S.template ), part ];

			link.href = Nino.admin.router.href( 'builder', parts );
			link.setAttribute( 'aria-label', word('label/edit')+ ': '+ label );
			link.addEventListener( 'click', function( event ) {
				event.preventDefault();
				Nino.admin.builder.go( parts, { focus : 'back' } );
			} );
			row.appendChild( el( 'span', 'builder-frame-name', label ) );
			row.appendChild( link );

			return row;
		};

		if( S.frame === true && advanced === false )
			head.appendChild( el( 'p', 'nino-admin-hint', word('hint/frame') ) );

		// The frames are written for every page: from the design on
		const rows = S.frame === false && advanced === false && S.level >= 2;

		level.appendChild( Nino.adminUi.contextBar( back ) );
		level.appendChild( head );

		if( rows === true )
			level.appendChild( frameRow( '@header', word('word/frame-row-header') ) );

		level.appendChild( split.el );

		if( rows === true )
			level.appendChild( frameRow( '@footer', word('word/frame-row-footer') ) );

		level.appendChild( bar.el );

		Nino.admin.dirty.register( 'builder', bar.entry( { discard : function() { discard() } } ) );

		if( advanced === true )
			drawLeft();

		paint();
		preview.flush();
		preview.width( S.width );
	};

	// Restore is on the bar where the page has a last state, and off - with the reason - where this account may not put it back
	const paintBar = function( view ) {

		view.bar.set( { dirty : isDirty(), restorable : S.last > 0 } );

		// The reason stands beside the button, where it is read, and not in its title alone
		const off = S.last > 0 && S.unback !== '';

		if( off === true ) {
			view.bar.restore.disabled = true;
			view.bar.restore.title = word('msg/no-restore');
		}

		if( off === true || view.note !== undefined ) {
			view.note = view.note ?? view.bar.el.insertBefore( el( 'span', 'nino-admin-hint builder-restore-note' ), view.bar.restore );
			view.note.textContent = off === true ? word('msg/no-restore') : '';
		}
	};

	// The work is dropped: the page as it is saved
	const discard = function() {

		if( S.ready === false )
			return;

		const was = JSON.parse( S.saved );

		S.top = was[0];
		S.texts = was[1];
		S.copies = {};
		S.inserted = {};
		S.pictures = {};
		S.undo = [];
		S.redo = [];
		release();
		paint();
	};

	const templateOf = function( part ) {

		if( part === '@header' )
			return 'frame-header';

		if( part === '@footer' )
			return 'frame-footer';

		return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test( part ) === true ? 'page-'+ part : '';
	};

	const addressOf = function( template ) {

		if( template === 'frame-header' )
			return '@header';

		return template === 'frame-footer' ? '@footer' : template.replace( /^page-/, '' );
	};

	/**
	 *	Open a template: the page as the server has it, drawn on the screen, its draft asked for
	 *
	 *	@param		{Function}	done					Called with whether it was opened
	 */
	const openPage = function( target, done ) {

		const template = target.template;

		if( S.view !== null )
			S.view.preview.stop();

		S.ready = false;
		S.built = false;
		S.template = template;
		S.page = target.page;
		S.named = {};

		Nino.admin.builder.call( 'open', { template : template, page : target.page }, function( status, response ) {

			const level = dc.getElementById('builder-page');

			if( status !== 200 ) {
				Nino.admin.builder.fail( level, status, response );
				Nino.adminUi.showLevel( 'builder-page', { focus : false } );
				done( false );
				return;
			}

			S.frame = response.frame === true;
			S.staged = [];
			S.dir = response.dir;
			S.address = response.address;
			S.locale = response.contentLocale;
			S.fresh = response.fresh ?? {};
			S.locales = response.locales;
			S.native = response.native;
			S.catalog = null;
			S.tiles = {};
			S.undo = [];
			S.redo = [];
			S.name = nameOfPage( template );
			take( response );
			S.ready = true;
			S.stale = true;
			S.target = target;
			show( target );
			done( true );
		} );
	};

	/**
	 *	What an address names: the template, the page a frame is shown on, and for the extended view the block, the place in it and the section
	 *
	 *	@param		{Array}			parts				[ page, block, place, section ]
	 *
	 */
	const targetOf = function( parts ) {

		const template = templateOf( parts[0] ?? '' );

		if( template === '' )
			return null;

		const head = { mode : 'basic', template : template, page : '', slug : addressOf( template ), block : '', path : '', section : '' };

		if( parts.length < 2 || /^@(?:header|footer)$/.test( parts[0] ) === true )
			return head;

		const frame = /^@(?:header|footer)$/.test( parts[1] ) === true;
		const place = parts[2] ?? '';

		if( frame === false && /^[a-z][a-z0-9-]*$/.test( parts[1] ) === false )
			return head;

		return {
			mode		: 'advanced',
			template	: frame === true ? templateOf( parts[1] ) : template,
			page		: frame === true ? parts[0].replace( /^page-/, '' ) : '',
			slug		: parts[0],
			block		: parts[1],
			path		: place === '@block' || /^root$|^\d+(?:\.\d+)*$/.test( place ) === true ? place : '',
			section	: place === '@block' ? '' : ( parts[3] ?? '' ),
		};
	};

	/**
	 *	Show what an address names: the page as it is written on, or the extended view of one of its blocks, and in it the tree or one component. A way into the
	 *	extended view that the account may not take - it is for the design - or a block that is not there lands on the page, with a sentence
	 *
	 *	@param		{Object}		target				As targetOf() answers
	 */
	const show = function( target ) {

		const level = dc.getElementById('builder-page');
		const was = S.adv === null ? null : S.adv.path;
		let mode = target.mode;
		let because = '';

		S.adv = mode === 'advanced' ? { slug : target.slug, block : target.block, path : target.path, section : target.section, view : '', accordion : null } : null;

		if( mode === 'advanced' && S.level < 2 )
			because = word('msg/no-advanced');
		else if( mode === 'advanced' && rootAt() === -1 )
			because = word('msg/no-block');

		if( because !== '' ) {
			mode = 'basic';
			S.adv = null;
		}

		// A step of the browser's history between the levels puts the focus where the person's own step does
		if( S.focusNext === '' && was !== null && S.adv !== null ) {

			const inside = function( path ) { return path !== '' && path !== '@block' };

			if( inside( S.adv.path ) === true && inside( was ) === false )
				S.focusNext = 'back';
			else if( inside( S.adv.path ) === false && inside( was ) === true ) {
				S.focusNext = 'tree';
				S.focusFrom = was;
			}
		}

		S.mode = mode;
		S.tree = null;
		S.mark = null;

		if( S.built === false || S.view === null || S.view.kind !== mode ) {

			if( S.view !== null )
				S.view.preview.stop();

			build( level );
			S.built = true;
		}
		else if( mode === 'advanced' ) {
			drawLeft();
			paint();
		}

		Nino.adminUi.showLevel( 'builder-page', { focus : false } );

		S.at = mode === 'basic' ? [ addressOf( S.template ) ] : ( S.adv.path === '' || S.adv.path === '@block' ? partsOf( '', S.adv.path === '' ? 'tree' : 'block' ) : partsOf( S.adv.path, S.adv.accordion === null ? S.adv.section : ( S.adv.accordion.get()[0] ?? '' ) ) );
		Nino.admin.router.set( 'builder', S.at );

		if( because !== '' )
			told( because );

		// The focus goes where the move says: into a component on its first line, out of it on the entry it was left from
		const wanted = S.focusNext;

		S.focusNext = '';

		if( wanted === 'back' && mode === 'advanced' ) {

			const link = dc.getElementById('builder-back-tree');
			const into = link !== null ? link : S.view.title;

			if( into.hasAttribute('tabindex') === false && into.tagName !== 'A' )
				into.setAttribute( 'tabindex', '-1' );

			into.focus();
		}
		else if( wanted === 'tree' && S.tree !== null )
			S.tree.focus( S.focusFrom );
		else if( wanted === 'page' && mode === 'basic' ) {

			S.view.title.setAttribute( 'tabindex', '-1' );
			S.view.title.focus();
		}

		S.focusFrom = '';
	};

	const nameOfPage = function( template ) {

		const page = S.list.pages.find( function( candidate ) { return candidate.template === template } );

		if( page !== undefined )
			return page.name;

		return template === 'frame-header' ? word('word/frame-header') : ( template === 'frame-footer' ? word('word/frame-footer') : template );
	};

	// --- the extended view: the tree of a block and the settings of a component ----------------------------------------------------

	// The index in the top level of the tree of the block the view is about: the block with its id - a frame has one block, its first - or -1 where it is gone
	const rootAt = function() {

		if( S.adv === null )
			return -1;

		return S.top.findIndex( function( node ) {
			return kindOf( node ) === 'block' && ( S.frame === true || idOf( node ) === S.adv.block );
		} );
	};

	// A position in the block - 'root' for the block itself, else the indices in each level, the blanks counted - as the position in the tree of the page
	const absOf = function( rel ) {
		return String( rootAt() )+ ( rel === 'root' ? '' : '.'+ rel );
	};

	const relNode = function( rel ) {
		return rootAt() === -1 ? null : nodeAt( absOf( rel ) );
	};

	const parentOf = function( rel ) {
		return rel.indexOf('.') === -1 ? 'root' : rel.slice( 0, rel.lastIndexOf('.') );
	};

	const indexOfRel = function( rel ) {
		return Number( rel.slice( rel.lastIndexOf('.') + 1 ) );
	};

	const childOf = function( rel, at ) {
		return rel === 'root' ? String( at ) : rel+ '.'+ at;
	};

	// Whether a node of a level is a piece of it - a call, a fill or words - and not the blanks that lay it out
	const isPiece = function( node ) {
		return node.n !== undefined || node.f !== undefined || ( node.t !== undefined && BLANKS.test( node.t ) === false );
	};

	// The id a component reads its words under: its own, or the nearest one above it
	const scopeOf = function( rel ) {

		let node = S.top[rootAt()];
		let scope = idOf( node );

		if( rel === 'root' )
			return scope;

		for( const part of rel.split('.') ) {

			node = ( node.c ?? [] )[ Number( part ) ];

			if( node === undefined )
				return scope;

			if( node.n !== undefined && idOf( node ) !== '' )
				scope = idOf( node );
		}

		return scope;
	};

	// Where a component reads its first word, as the server tells of the page: the kind of the source, its key, the format and whether it is written here
	const sourceOf = function( rel ) {

		const node = relNode( rel );
		const schema = node === null ? undefined : S.schemas[node.n];

		if( schema === undefined || schema.source === 'none' )
			return null;

		const given = node.a ?? {};
		const format = typeof given.format === 'string' && given.format !== '' ? given.format : ( schema.format !== '' ? schema.format : 'plain' );
		const written = typeof node.s === 'string' ? node.s : '';

		if( schema.source === 'text' && typeof given.text === 'string' )
			return { kind : 'fixed', key : '', written : 'text', format : format, editable : true };

		const scope = scopeOf( rel );
		const key = written.charAt(0) === '~' && written !== '~' && scope !== '' ? '/template/'+ S.template+ '/'+ scope+ '/'+ written.slice( 1 ) : ( written.charAt(0) === '/' && schema.source !== 'link' ? written : '' );

		return { kind : schema.source, key : key, written : written, format : format, editable : schema.source === 'text' && key.indexOf( '/template/'+ S.template+ '/' ) === 0 };
	};

	// The words of a component for a title: its kind and, where it has a text of its own, the beginning of it
	const ownText = function( node, rel, most ) {

		const source = node.n !== undefined && S.schemas[node.n] !== undefined && S.schemas[node.n].source === 'text' ? sourceOf( rel ) : null;

		if( source === null )
			return '';

		return shortened( source.kind === 'fixed' ? ( node.a ?? {} ).text : ( source.key === '' ? '' : textOf( source.key ) ), most );
	};

	const titleOfRel = function( rel ) {

		const node = relNode( rel );

		if( node === null )
			return '';

		return node.n === undefined ? word('word/free') : ( S.schemas[node.n] === undefined ? word( 'word/call', node.n ) : kindName( node ) );
	};

	// The next piece of the level of a component, before it or behind it: its index there, -1 for none
	// Whether a node of a level is a locked piece: free markup, a fill or a call that has no schema - the pieces that follow one another are one unit
	const lockedNode = function( node ) {
		return isPiece( node ) === true && ( node.n === undefined || S.schemas[node.n] === undefined );
	};

	// The nodes a component or a unit takes in its level: the node itself, or the locked pieces that follow one another with nothing but blanks between
	const runAt = function( list, at ) {

		if( lockedNode( list[at] ) === false )
			return [ at, at ];

		let from = at;
		let to = at;

		for( let before = at - 1; before >= 0; before-- ) {

			if( isPiece( list[before] ) === false )
				continue;

			if( lockedNode( list[before] ) === false )
				break;

			from = before;
		}

		for( let after = at + 1; after < list.length; after++ ) {

			if( isPiece( list[after] ) === false )
				continue;

			if( lockedNode( list[after] ) === false )
				break;

			to = after;
		}

		return [ from, to ];
	};

	// The next piece of the level of a component, before it or behind it - past the whole unit where it is one -: its index there, -1 for none
	const siblingAt = function( rel, direction ) {

		const parent = relNode( parentOf( rel ) );
		const list = parent === null || Array.isArray( parent.c ) === false ? [] : parent.c;
		const run = runAt( list, indexOfRel( rel ) );

		for( let next = direction < 0 ? run[0] - 1 : run[1] + 1; next >= 0 && next < list.length; next += direction )
			if( isPiece( list[next] ) === true )
				return next;

		return -1;
	};

	// What the register has against a place of the tree, as one sentence
	const hintOf = function( abs ) {

		const found = S.findings[abs];

		if( found === undefined )
			return undefined;

		// The words are the ones the schema gives: the label of the attribute and of the component, and of the variant - never the key
		const node = nodeAt( abs ) ?? {};
		const schema = node.n === undefined ? undefined : S.schemas[node.n];
		const attribute = function( name ) { return schema !== undefined && schema.attributes[name] !== undefined ? localized( schema.attributes[name].label ) : name };
		const component = function( name ) {

			const own = Nino.content.getText( '/_admin/builder/kind/'+ name );

			return own !== '' ? own : ( S.schemas[name] !== undefined ? localized( S.schemas[name].label ) : name );
		};
		const say = function( entry ) {

			if( entry[0] === 'outside' || entry[0] === 'foreign' )
				return word( 'finding/'+ entry[0], attribute( entry[1] ) );

			if( entry[0] === 'variant' )
				return schema === undefined || labelOfVariant( schema.variantLabels, entry[1] ) === '' ? word('finding/variant-plain') : word( 'finding/variant', variantLabel( schema, entry[1] ) );

			if( entry[0] === 'unknown' )
				return entry[2] === undefined ? word( 'finding/nofeature', component( entry[1] ) ) : word( 'finding/unknown', component( entry[1] ), localized( entry[2] ) );

			return word( 'finding/'+ entry[0], component( entry[1] ) );
		};

		return { text : found.map( say ).join(' '), state : 'warn' };
	};

	// The word of a variant: the label the register gives it, else the word of this panel for the standard one, else ''
	const labelOfVariant = function( labels, variant ) {

		if( labels !== undefined && labels !== null && labels[variant] !== undefined && labels[variant] !== null )
			return localized( labels[variant] );

		return Nino.content.getText( '/_admin/builder/variant/'+ variant );
	};

	const variantLabel = function( schema, variant ) {
		return labelOfVariant( schema.variantLabels, variant ) || variant;
	};

	const actionsOf = function( rel, container ) {

		const list = [];
		const title = titleOfRel( rel );

		if( container === true )
			list.push( { key : 'add', label : '+', name : word( 'label/add-in', title ) } );

		if( rel !== 'root' ) {
			list.push( { key : 'up', label : '↑', name : word( 'label/up-comp', title ), disabled : siblingAt( rel, -1 ) === -1 } );
			list.push( { key : 'down', label : '↓', name : word( 'label/down-comp', title ), disabled : siblingAt( rel, 1 ) === -1 } );
			list.push( { key : 'remove', label : '×', name : word( 'label/remove-comp', title ), danger : true } );
		}

		return list;
	};

	// The entry of the tree for a component or for a piece that is locked, and for everything inside it
	const itemOf = function( node, rel ) {

		const schema = node.n === undefined ? undefined : S.schemas[node.n];
		const children = [];

		if( schema !== undefined ) {

			const list = node.c ?? [];

			for( let at = 0; at < list.length; at++ ) {

				if( isPiece( list[at] ) === false )
					continue;

				// A unit of locked pieces is one entry
				children.push( itemOf( list[at], childOf( rel, at ) ) );
				at = runAt( list, at )[1];
			}
		}

		return {
			id			: rel,
			label		: titleOfRel( rel ),
			detail	: schema === undefined ? '' : ownText( node, rel, 40 ),
			children	: children,
			open		: true,
			locked	: schema === undefined,
			hint		: hintOf( absOf( rel ) ),
			actions	: actionsOf( rel, schema !== undefined && schema.container === true ),
		};
	};

	const itemsNow = function() {

		const node = rootAt() === -1 ? undefined : S.top[rootAt()];

		return node === undefined ? [] : [ itemOf( node, 'root' ) ];
	};

	const countCalls = function( node ) {

		return 1 + ( node.c ?? [] ).reduce( function( sum, inner ) { return sum + ( inner.n === undefined ? 0 : countCalls( inner ) ) }, 0 );
	};

	// The parts of an address of the extended view: the page, the block, and where in it
	const partsOf = function( rel, section ) {

		const head = [ S.adv.slug, S.adv.block ];

		if( rel === '' )
			return head.concat( section === 'block' ? [ '@block' ] : [] );

		return head.concat( [ rel ], section === '' ? [] : [ section ] );
	};

	const hrefOf = function( parts ) {
		return Nino.admin.router.href( 'builder', parts );
	};

	// A line for the bar of the page, where there is one
	const told = function( text ) {

		if( S.view !== null )
			S.view.bar.line.idle( text );
	};

	// The place of a node is where the blanks that lay it out stand: the node goes, with the blanks in front of it where there are, else the ones behind it
	const takeOut = function( list, from, to ) {

		const blank = function( node ) { return node.t !== undefined && node.n === undefined && BLANKS.test( node.t ) === true };

		if( from > 0 && blank( list[from - 1] ) === true )
			list.splice( from - 1, to - from + 2 );
		else if( to + 1 < list.length && blank( list[to + 1] ) === true )
			list.splice( from, to - from + 2 );
		else
			list.splice( from, to - from + 1 );
	};

	const moveComponent = function( rel, direction ) {

		const here = relNode( rel );

		if( rel === 'root' || here === null || here === undefined )
			return false;

		const to = siblingAt( rel, direction );
		const list = relNode( parentOf( rel ) ).c;
		const mine = runAt( list, indexOfRel( rel ) );

		if( to === -1 )
			return false;

		const other = runAt( list, to );
		const first = direction < 0 ? other : mine;
		const second = direction < 0 ? mine : other;
		let at = first[0];

		// The two runs change places with the blanks between them where they were: a unit is moved whole, and a call is moved past it whole
		change( function() {
			list.splice( first[0], second[1] - first[0] + 1, ...list.slice( second[0], second[1] + 1 ), ...list.slice( first[1] + 1, second[0] ), ...list.slice( first[0], first[1] + 1 ) );
		} );

		if( direction > 0 )
			at = first[0] + ( second[1] - second[0] + 1 ) + ( second[0] - first[1] - 1 );

		S.advFocus = childOf( parentOf( rel ), at );
		S.marked = S.advFocus;

		return true;
	};

	// A unit of locked pieces is taken out only after the question, as a block is: what is in it is nobody's to write again here
	const askRemoveUnit = function( rel, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/unit-title'),
			message	: word('confirm/unit-message'),
			yes			: word('label/remove-short'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose : function( ok ) {
				if( ok === true )
					removeComponent( rel );
			},
		} );
	};

	const removeComponent = function( rel ) {

		if( rel === 'root' || relNode( rel ) === null )
			return false;

		const list = relNode( parentOf( rel ) ).c;
		const title = titleOfRel( rel );
		const run = runAt( list, indexOfRel( rel ) );

		// Taking a component out asks nothing: the undo brings it back; a unit of locked pieces goes whole
		change( function() { takeOut( list, run[0], run[1] ) } );
		S.advFocus = parentOf( rel );
		told( word( 'msg/component-removed', title ) );

		return true;
	};

	// The name of a new source in the scope: the last word of the component's name, then the first number behind it that nothing in the scope reads or has words for
	const freeName = function( base, scope ) {

		const used = new Set();
		const prefix = '/template/'+ S.template+ '/'+ scope+ '/';
		const walk = function( nodes ) {

			nodes.forEach( function( node ) {

				if( node.n === undefined )
					return;

				if( typeof node.s === 'string' && node.s.charAt(0) === '~' )
					used.add( node.s.slice( 1 ) );

				if( Array.isArray( node.c ) === true )
					walk( node.c );
			} );
		};

		walk( S.top );
		Object.keys( S.texts ).concat( S.slots, Object.keys( S.copies ) ).forEach( function( key ) {
			if( key.indexOf( prefix ) === 0 )
				used.add( key.slice( prefix.length ) );
		} );

		for( let count = 1; count < 10000; count++ ) {

			const candidate = count === 1 ? base : base+ '-'+ count;

			if( used.has( candidate ) === false )
				return candidate;
		}

		return base;
	};

	/**
	 *	Put a component into a container, behind the last call it holds, laid out the way the page is: the blanks come from the server, which writes a template as the
	 *	kernel does. A component with a text reads it from a key of its own under the nearest id - where there is none, the text is fixed - and has a first text
	 */
	const addComponent = function( rel, offer, blank ) {

		const container = relNode( rel );
		const fresh = clone( offer.node );
		const schema = S.schemas[offer.name];
		const scope = scopeOf( rel );
		const close = blank.length > 1 && blank.slice( -1 ) === '\t' ? blank.slice( 0, -1 ) : '\n';
		let made = '';

		fresh.a = {};

		change( function() {

			if( schema.source !== 'none' && schema.source !== 'link' && scope !== '' ) {

				const name = freeName( offer.name.replace( /^.*-/, '' ), scope );

				fresh.s = '~'+ name;

				if( schema.source === 'text' )
					S.texts[ '/template/'+ S.template+ '/'+ scope+ '/'+ name ] = { [S.locale] : freshText() };
			}
			else if( schema.source === 'text' )
				fresh.a.text = word('new/text');

			if( Array.isArray( container.c ) === false )
				container.c = [];

			// Behind the last piece the container holds - a unit of locked pieces is never cut by what is put in
			let last = -1;

			container.c.forEach( function( inner, at ) {
				if( isPiece( inner ) === true )
					last = at;
			} );

			if( last === -1 )
				container.c = [ { t : blank }, fresh, { t : close } ];
			else {

				container.c.splice( last + 1, 0, { t : blank }, fresh );

				if( container.c[container.c.length - 1] === fresh )
					container.c.push( { t : close } );
			}

			made = childOf( rel, container.c.indexOf( fresh ) );
		} );

		S.marked = made;
		S.advFocus = made;
		told( word( 'msg/component-added', localized( schema.label ) ) );
	};

	// The place for a component: asked of the server, which says what the register offers there - by group, each as the call it would be - and the dialog lists it
	const openOffer = function( rel, opener ) {

		api( 'offer', { tree : S.top, at : absOf( rel ) }, function( status, response ) {

			if( status !== 200 ) {
				S.view.bar.line.error( status, response );
				return;
			}

			Object.assign( S.schemas, response.schemas );
			learn( response.schemas );

			const body = el( 'div', 'builder-offer' );
			const groups = [];
			let picked = null;

			response.offers.forEach( function( offer ) {

				let group = groups.find( function( entry ) { return entry.key === offer.group } );

				if( group === undefined ) {
					group = { key : offer.group, label : offer.groupLabel === null || offer.groupLabel === undefined ? offer.group : localized( offer.groupLabel ), offers : [] };
					groups.push( group );
				}

				group.offers.push( offer );
			} );

			if( groups.length === 0 )
				body.appendChild( Nino.adminUi.emptyState( word('offer/none') ) );

			groups.forEach( function( group ) {

				const list = el( 'ul', 'builder-offer-list' );

				body.appendChild( el( 'h4', 'builder-offer-group', group.label ) );

				group.offers.forEach( function( offer ) {

					const item = el( 'li', 'builder-offer-item' );
					const button = el( 'button', 'nino-admin-btn builder-offer-pick', localized( offer.label ) );

					button.type = 'button';
					button.dataset.name = offer.name;

					if( offer.ok === true )
						button.addEventListener( 'click', function() {

							picked = offer;
							button.closest('dialog').close();
						} );
					else {
						button.disabled = true;
						item.appendChild( button );
						item.appendChild( el( 'span', 'nino-admin-hint', word( 'offer/why-'+ offer.why ) ) );
						list.appendChild( item );
						return;
					}

					item.appendChild( button );
					list.appendChild( item );
				} );

				body.appendChild( list );
			} );

			Nino.adminUi.choiceDialog( {
				title			: word( 'offer/title', titleOfRel( rel ) ),
				message		: word('offer/note'),
				content		: body,
				className	: 'nino-admin-dialog--manual builder-offer-dialog',
				opener		: opener,
				choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
				onChoose	: function() {

					if( picked !== null )
						addComponent( rel, picked, response.blank );
				},
			} );

			const first = body.querySelector('button:not(:disabled)');

			if( first !== null )
				first.focus();
		} );
	};

	// --- the source of a content: the dialog, the pictures, the block of the project ---------------------------------------------------

	// Where a text of the project is from, in words: the part of the project it is of, or the page
	const areaOf = function( entry ) {

		if( entry.area === undefined )
			return word('source/area-other');

		if( [ 'company', 'website', 'mail', 'project' ].indexOf( entry.area ) !== -1 )
			return word( 'source/shared', word( 'source/area-'+ entry.area ) );

		return entry.area === 'page' || entry.area === 'feature' ? word( 'source/area-'+ entry.area, entry.of ) : word( 'source/area-'+ ( entry.area === 'common' ? 'common' : 'other' ) );
	};

	// A name that is written in a template: lower case words, joined by hyphens - the umlauts as the German writes them out
	const slugOf = function( text, most ) {

		const plain = String( text ).toLowerCase().replace( /ä/g, 'ae' ).replace( /ö/g, 'oe' ).replace( /ü/g, 'ue' ).replace( /ß/g, 'ss' ).normalize('NFD').replace( /[̀-ͯ]/g, '' );

		return plain.replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, most ?? 40 ).replace( /-+$/, '' );
	};

	const NAME = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
	const ADDRESS = /^(?:https?:\/\/[^\s]+|mailto:[^\s]+|tel:[^\s]+|#[^\s]*|\/(?!\/)[^\s]*)$/i;

	// The type of elements of the loop a component stands in: the nearest call above it that holds calls and names a type of elements as its first word - '' where the
	// loop names none yet, null where the component stands in no loop
	const loopOf = function( rel ) {

		if( rel === 'root' )
			return null;

		let node = S.top[rootAt()];
		let type = null;

		const look = function( candidate ) {

			if( S.schemas[candidate.n] !== undefined && S.schemas[candidate.n].loop === true )
				type = typeof candidate.s === 'string' && /^\/[a-z_][a-z0-9_-]{0,63}$/.test( candidate.s ) === true ? candidate.s : '';
		};

		look( node );

		for( const part of rel.split('.').slice( 0, -1 ) ) {

			node = ( node.c ?? [] )[ Number( part ) ];

			if( node === undefined )
				break;

			look( node );
		}

		return type;
	};

	// The key a written source of a component names, for a text or a picture: the key of the page for ~name, the key as it is written for one that begins with a slash
	const keyOf = function( rel, written ) {

		if( written.charAt(0) === '~' )
			return scopeOf( rel ) === '' ? '' : '/template/'+ S.template+ '/'+ scopeOf( rel )+ '/'+ written.slice( 1 );

		return written.charAt(0) === '/' ? written : '';
	};

	// The names in use in the scope of a component - a source of the page, a word of the page, a slot of the page - so that a new name is one nobody has
	const namesIn = function( scope ) {

		const prefix = '/template/'+ S.template+ '/'+ scope+ '/';
		const used = new Set();

		Object.keys( S.texts ).concat( S.slots, Object.keys( S.copies ), Object.keys( S.pictures ), Object.keys( S.images ) ).forEach( function( key ) {
			if( key.indexOf( prefix ) === 0 )
				used.add( key.slice( prefix.length ) );
		} );

		return used;
	};

	// The words of a new text, in the language the page is written in - which is not always the one of the workbench
	const freshText = function() {
		return typeof S.fresh[S.locale] === 'string' ? S.fresh[S.locale] : word('new/text');
	};

	// The text the page shows where the language of the view has none: the next language that has one, the native language first - what the website does
	const fallbackText = function( key ) {

		const order = [ S.native ].concat( S.locales.map( function( entry ) { return entry.locale } ) ).filter( function( locale ) { return locale !== S.locale } );
		const found = order.find( function( locale ) { return typeof ( S.texts[key] ?? {} )[locale] === 'string' && S.texts[key][locale].trim() !== '' } );

		return found === undefined ? '' : shortened( S.texts[key][found], 200 );
	};

	/**
	 *	What a written source says, in a few words: the text it reads, the page it leads to, the field it takes
	 *
	 *	@param		{Object}		spec					{ kind, attr }
	 *
	 */
	const sayOf = function( spec, written, rel ) {

		if( written === '' )
			return word('source/empty');

		if( written.charAt(0) === '.' && /^\.[A-Za-z][A-Za-z0-9_-]*$/.test( written ) === true )
			return word( 'source/is-field', written.slice( 1 ) );

		if( spec.kind === 'link' && written.indexOf('page:') === 0 ) {

			const page = S.links.find( function( entry ) { return 'page:'+ entry.uri === written } );

			return word( 'source/is-page', page === undefined ? written.slice( 5 ) : page.name );
		}

		if( spec.kind === 'link' && written.charAt(0) !== '~' && ( written.charAt(0) !== '/' || /\s/.test( written ) === true || ( S.texts[written] === undefined && written.split('/').length !== 5 ) ) )
			return word( 'source/is-address', written );

		const key = keyOf( rel, written );

		if( spec.kind === 'image' )
			return key === '' ? word( 'source/is-address', written ) : word( key.indexOf( '/template/'+ S.template+ '/' ) === 0 ? 'source/is-slot' : 'source/is-slot-other', ( S.pictures[key] ?? S.images[key] ?? {} ).label ?? key.slice( key.lastIndexOf('/') + 1 ) );

		if( key === '' )
			return word( 'source/is-fixed', shortened( written, 60 ) );

		return word( key.indexOf( '/template/'+ S.template+ '/' ) === 0 ? 'source/is-key' : 'source/is-key-other', key.slice( key.lastIndexOf('/') + 1 ), shortened( textOf( key ), 60 ) || '–' );
	};

	// Put a source into a call: the first word of it or one of its attributes. A new text is a key of the scope of the call with the words of the view, a new picture a slot of
	// it, a fixed text the text of the call - and a source that is written takes the place of the fixed text
	const applySource = function( rel, spec, choice ) {

		const node = relNode( rel );
		const scope = scopeOf( rel );
		const base = '/template/'+ S.template+ '/'+ scope+ '/';

		change( function() {

			const attrs = ensureAttrs( node );
			let written = choice.written ?? '';

			if( choice.form === 'new' ) {

				written = '~'+ choice.name;

				if( spec.kind === 'image' )
					S.pictures[ base+ choice.name ] = { label : choice.label, width : choice.width, height : choice.height };
				else
					S.texts[ base+ choice.name ] = { [S.locale] : choice.text };
			}

			if( choice.form === 'fixed' ) {

				if( spec.attr === '' ) {
					attrs.text = choice.text;
					node.s = '';
				}
				else
					attrs[spec.attr] = choice.text;

				return;
			}

			if( spec.attr === '' ) {
				node.s = written;
				delete attrs.text;
			}
			else if( written === '' )
				delete attrs[spec.attr];
			else
				attrs[spec.attr] = written;
		} );

		told( word('msg/source-set') );
	};

	/**
	 *	The dialog Source: where the content of a call comes from. Tabs - the arrows walk them - for the text of a key that is there, a new one, a fixed one; for an
	 *	address also a page of the site and an address; for a picture a slot that is there or a new one. A source that is chosen closes the dialog and is put into the call
	 */
	const openSource = function( rel, spec, opener ) {

		const node = relNode( rel );
		const scope = scopeOf( rel );
		const loop = loopOf( rel );
		const tabs = spec.kind === 'link' ? [ 'pick', 'new', 'page', 'address' ] : ( spec.kind === 'image' ? [ 'pick', 'new' ] : [ 'pick', 'new', 'fixed' ] );
		const given = spec.attr === '' ? ( typeof node.s === 'string' ? node.s : '' ) : ( typeof ( node.a ?? {} )[spec.attr] === 'string' ? node.a[spec.attr] : '' );
		const fixed = spec.attr === '' && spec.kind === 'text' && typeof ( node.a ?? {} ).text === 'string' ? node.a.text : ( spec.attr !== '' && spec.kind === 'text' && given !== '' && given.match( /^[~./]\S*$/ ) === null ? given : '' );
		const body = el( 'div', 'builder-source-body' );
		const strip = el( 'div', 'nino-admin-tabs nino-admin-tabs--bar builder-source-tabs' );
		const panel = el( 'div', 'nino-admin-tabpanel builder-source-panel' );
		const buttons = {};
		const base = '/template/'+ S.template+ '/'+ scope+ '/';
		let choice = null;

		const finish = function( made ) {

			choice = made;

			const dialog = body.closest('dialog');

			if( dialog !== null )
				dialog.close();
		};

		const error = function( box, text ) {

			box.textContent = text;
			box.hidden = text === '';
		};

		const oops = function() {

			const box = el( 'p', 'nino-admin-hint builder-source-error', '' );

			box.setAttribute( 'role', 'alert' );
			box.hidden = true;

			return box;
		};

		const entryOf = function( name, text, chip, onPick ) {

			const item = el( 'li', 'builder-source-item' );
			const button = el( 'button', 'builder-source-pick' );

			button.type = 'button';
			button.appendChild( el( 'strong', '', name ) );

			if( text !== '' )
				button.appendChild( el( 'span', 'nino-admin-hint', text ) );

			if( chip !== '' )
				button.appendChild( el( 'span', 'nino-admin-badge', chip ) );

			button.addEventListener( 'click', onPick );
			item.appendChild( button );

			return item;
		};

		// What a key of this page is called where it is the scope's own: ~name; any other is written as it is
		const writtenFor = function( key ) {
			return key.indexOf( base ) === 0 ? '~'+ key.slice( base.length ) : key;
		};

		const pick = function() {

			const box = el( 'div', 'builder-source-picker' );
			const search = Nino.adminUi.textField( { key : 'source-search', label : word('source/search'), value : '', placeholder : word( spec.kind === 'image' ? 'source/search-hint-image' : 'source/search-hint' ) } );
			const list = el( 'ul', 'builder-source-list' );
			const note = el( 'p', 'nino-admin-hint', '' );
			const fields = el( 'div', 'builder-source-fields' );
			let waiting = null;
			let asked = 0;

			const show = function( found, more ) {

				list.innerHTML = '';

				found.forEach( function( entry ) { list.appendChild( entry ) } );
				note.textContent = found.length === 0 ? word('source/none-found') : ( more === true ? word('source/more') : '' );
			};

			const ask = function() {

				const query = search.querySelector('input').value.trim();
				const mine = query.toLowerCase();
				const turn = ++asked;

				api( 'keys', { kind : spec.kind === 'image' ? 'image' : 'text', query : query }, function( status, response ) {

					if( turn !== asked || status !== 200 )
						return;

					const out = [];
					const draft = spec.kind === 'image' ? Object.assign( {}, S.images, S.pictures ) : S.texts;
					const seen = new Set();

					// What is on the page - saved or not - first, as it is now; the rest as the server found it. A text is shown first, and where it is from quietly behind it
					Object.keys( draft ).filter( function( key ) { return key.indexOf( '/template/'+ S.template+ '/' ) === 0 } ).sort().forEach( function( key ) {

						const name = key.slice( key.lastIndexOf('/') + 1 );
						const text = spec.kind === 'image' ? '' : shortened( textOf( key ) !== '' ? textOf( key ) : fallbackText( key ), 80 );
						const label = spec.kind === 'image' ? String( draft[key].label ?? '' ) : '';
						const block = S.top.find( function( node ) { return node.n !== undefined && idsOf( [ node ], new Set() ).has( key.split('/')[3] ) } );
						const root = block === undefined ? '' : idOf( block );

						if( mine !== '' && ( name+ ' '+ text+ ' '+ label ).toLowerCase().indexOf( mine ) === -1 )
							return;

						seen.add( key );
						out.push( entryOf( label !== '' ? label : ( text !== '' ? text : name ), root === '' ? word('source/page') : word( 'source/page-of', root.charAt(0).toUpperCase()+ root.slice( 1 ) ), '', function() { finish( { form : 'key', written : writtenFor( key ) } ) } ) );
					} );

					response.entries.forEach( function( entry ) {

						if( seen.has( entry.key ) === true || entry.key.indexOf( '/template/'+ S.template+ '/' ) === 0 )
							return;

						out.push( entryOf( entry.text !== '' ? entry.text : entry.name, areaOf( entry ), '', function() { finish( { form : 'key', written : entry.key } ) } ) );
					} );

					show( out, response.more );
				} );
			};

			search.querySelector('input').addEventListener( 'input', function() {
				wn.clearTimeout( waiting );
				waiting = wn.setTimeout( ask, 200 );
			} );

			box.appendChild( search );
			box.appendChild( list );
			box.appendChild( note );

			if( spec.kind !== 'image' )
				box.appendChild( el( 'p', 'nino-admin-hint', word('source/elsewhere-note') ) );

			// The field of the element: where there is a loop, the fields of the type of its elements - else the sentence that says why there is none
			fields.appendChild( el( 'h4', 'builder-offer-group', word('source/field-title') ) );

			if( loop === null || loop === '' )
				fields.appendChild( el( 'p', 'nino-admin-hint', word( loop === null ? 'source/no-loop' : 'source/loop-first' ) ) );
			else {

				const holder = el( 'ul', 'builder-source-list' );

				fields.appendChild( holder );
				api( 'keys', { kind : 'fields', type : loop }, function( status, response ) {

					if( status !== 200 )
						return;

					// The fields of the model by the words the model gives them, the ones every element has - its number, its address, its short name - quietly at the end
					const technical = [ 'id', 'uri', 'slug' ];
					const names = Object.keys( response.fields ).filter( function( name ) {
						return spec.kind === 'image' ? response.fields[name] === 'image' : [ 'string', 'integer', 'double', 'boolean', 'date', 'datetime' ].indexOf( response.fields[name] ) !== -1;
					} );

					names.filter( function( name ) { return technical.indexOf( name ) === -1 } ).concat( names.filter( function( name ) { return technical.indexOf( name ) !== -1 } ) ).forEach( function( name ) {
						holder.appendChild( entryOf( technical.indexOf( name ) !== -1 ? word( 'source/field-'+ name ) : ( response.labels[name] ?? name ), '', '', function() { finish( { form : 'field', written : '.'+ name } ) } ) );
					} );

					if( holder.children.length === 0 )
						fields.appendChild( el( 'p', 'nino-admin-hint', word('source/no-field') ) );
				} );
			}

			box.appendChild( fields );
			ask();

			return box;
		};

		const fresh = function() {

			const box = el( 'div', 'builder-source-new' );
			const problem = oops();

			if( scope === '' ) {
				box.appendChild( el( 'p', 'nino-admin-hint', word('source/no-scope') ) );
				return box;
			}

			const first = node.n.replace( /^.*-/, '' );
			const called = Nino.adminUi.textField( { key : 'source-name', label : word('source/name'), value : spec.attr === '' ? first : spec.attr, hint : word('source/name-hint') } );
			const used = namesIn( scope );
			const input = called.querySelector('input');
			let name = input.value;

			for( let count = 2; used.has( name ) === true && count < 1000; count++ )
				name = ( spec.attr === '' ? first : spec.attr )+ '-'+ count;

			input.value = name;
			box.appendChild( called );

			let text = null;
			let label = null;
			let width = null;
			let height = null;

			if( spec.kind === 'image' ) {

				label = Nino.adminUi.textField( { key : 'source-label', label : word('source/label'), value : S.schemas[node.n] === undefined ? '' : localized( S.schemas[node.n].label ) } );
				width = Nino.adminUi.numberField( { key : 'source-width', label : word('source/width'), value : 1200, min : 16, max : 4096 } );
				height = Nino.adminUi.numberField( { key : 'source-height', label : word('source/height'), value : 800, min : 16, max : 4096 } );
				box.appendChild( label );
				box.appendChild( width );
				box.appendChild( height );
			}
			else {

				const rich = spec.kind === 'text' && sourceOf( rel ) !== null && spec.attr === '' && sourceOf( rel ).format !== 'plain' && sourceOf( rel ).format !== 'inline';
				const field = el( 'label', 'nino-admin-field' );

				text = el( rich === true ? 'textarea' : 'input', 'nino-admin-input' );
				field.appendChild( el( 'span', '', word( spec.kind === 'link' ? 'source/address-text' : 'source/text-in', languageName( S.locale ) ) ) );

				if( rich === true )
					text.rows = 4;
				else
					text.type = 'text';

				text.value = spec.kind === 'link' ? '' : freshText();
				field.appendChild( text );
				box.appendChild( field );
			}

			box.appendChild( problem );
			box.appendChild( Nino.adminUi.button( word('source/apply'), function() {

				const now = input.value.trim();

				if( NAME.test( now ) === false || now.length > 40 )
					return error( problem, word('source/bad-name') );

				if( namesIn( scope ).has( now ) === true )
					return error( problem, word('source/taken') );

				if( spec.kind === 'image' )
					return finish( { form : 'new', name : now, label : label.querySelector('input').value.trim() === '' ? now : label.querySelector('input').value.trim(), width : Number( width.querySelector('input').value ), height : Number( height.querySelector('input').value ) } );

				if( text.value.trim() === '' || ( spec.kind === 'link' && ADDRESS.test( text.value.trim() ) === false ) )
					return error( problem, word( spec.kind === 'link' ? 'source/bad-address' : 'source/empty-text' ) );

				finish( { form : 'new', name : now, text : text.value } );
			}, { className : 'nino-admin-btn-primary' } ) );

			return box;
		};

		const plain = function( which ) {

			const box = el( 'div', 'builder-source-plain' );
			const problem = oops();
			let field = null;

			if( which === 'page' ) {

				const current = S.links.some( function( entry ) { return 'page:'+ entry.uri === given } ) === true ? given : '';

				field = Nino.adminUi.selectField( { key : 'source-page', label : word('source/page-label'), options : [ { value : '', label : word('source/page-choose') } ].concat( S.links.map( function( entry ) { return { value : 'page:'+ entry.uri, label : entry.name } } ) ), value : current } );
			}
			else
				field = Nino.adminUi.textField( { key : 'source-plain', label : word( which === 'address' ? 'source/address-label' : 'source/fixed-label' ), value : which === 'address' ? ( given !== '' && given.match( /^[~.]/ ) === null && given.indexOf('page:') !== 0 ? given : '' ) : fixed, hint : word( which === 'address' ? 'source/address-hint' : 'source/fixed-hint' ) } );

			box.appendChild( field );
			box.appendChild( problem );
			box.appendChild( Nino.adminUi.button( word('source/apply'), function() {

				const control = field.querySelector( which === 'page' ? 'select' : 'input' );
				const value = which === 'fixed' ? control.value : control.value.trim();

				if( which === 'page' && value === '' )
					return error( problem, word('source/page-choose') );

				if( which === 'address' && ADDRESS.test( value ) === false )
					return error( problem, word('source/bad-address') );

				if( which === 'fixed' && value.trim() === '' )
					return error( problem, word('source/empty-text') );

				finish( which === 'fixed' ? { form : 'fixed', text : value } : { form : which, written : value } );
			}, { className : 'nino-admin-btn-primary' } ) );

			return box;
		};

		const draw = function( tab ) {

			panel.innerHTML = '';
			panel.setAttribute( 'aria-labelledby', 'builder-source-tab-'+ tab );
			panel.appendChild( tab === 'pick' ? pick() : ( tab === 'new' ? fresh() : plain( tab ) ) );
		};

		body.appendChild( el( 'p', 'nino-admin-hint builder-source-now', word( 'source/now', fixed !== '' ? word( 'source/is-fixed', shortened( fixed, 60 ) ) : sayOf( spec, given, rel ) ) ) );
		strip.setAttribute( 'role', 'tablist' );
		strip.setAttribute( 'aria-label', word('source/tabs') );
		panel.id = 'builder-source-panel';
		panel.setAttribute( 'role', 'tabpanel' );

		tabs.forEach( function( tab ) {

			const button = el( 'button', 'nino-admin-tab', word( 'source/tab-'+ tab ) );

			button.type = 'button';
			button.id = 'builder-source-tab-'+ tab;
			button.setAttribute( 'role', 'tab' );
			button.setAttribute( 'aria-controls', 'builder-source-panel' );
			buttons[tab] = button;
			strip.appendChild( button );
		} );

		Nino.adminUi.buttonRow( buttons, 'pick', draw, 'aria-selected' );
		body.appendChild( strip );
		body.appendChild( panel );
		draw( 'pick' );

		Nino.adminUi.choiceDialog( {
			title			: word( 'source/title', spec.label ),
			message		: word('source/note'),
			content		: body,
			className	: 'nino-admin-dialog--manual builder-source-dialog',
			opener		: opener,
			choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function() {

				if( choice !== null )
					applySource( rel, spec, choice );
			},
		} );

		buttons.pick.focus();
	};

	// The place of a source in the settings of a call: what it is in a sentence and the button that changes it
	const sourceField = function( rel, spec, summary ) {

		const field = el( 'div', 'nino-admin-field builder-source-field' );
		const line = el( 'p', 'nino-admin-hint builder-source', summary );
		const button = Nino.adminUi.button( word('source/change'), function() { openSource( rel, spec, button ) }, { className : 'nino-admin-btn--small', name : word( 'source/change-of', spec.label ) } );

		button.dataset.key = rel+ ':source:'+ spec.attr;
		field.appendChild( el( 'span', 'builder-field-label', spec.label ) );
		field.appendChild( line );
		field.appendChild( button );

		return field;
	};

	// The id above a place of the top level of the page: where a ~name of a call there is read
	const scopeAt = function( path ) {

		let list = S.top;
		let scope = '';

		for( const part of path.split('.') ) {

			const node = list[ Number( part ) ];

			if( node === undefined )
				break;

			if( node.n !== undefined && idOf( node ) !== '' )
				scope = idOf( node );

			list = Array.isArray( node.c ) === true ? node.c : [];
		}

		return scope;
	};

	// The pictures of the page that a person who changes the content may exchange: a call that is a picture and reads a slot of the page
	const pictureTargets = function() {

		const out = [];

		S.sources.forEach( function( entry ) {

			if( entry.kind !== 'image' || entry.key.indexOf( '/template/'+ S.template+ '/' ) !== 0 || S.frame === true )
				return;

			const node = nodeAt( entry.path );
			const alt = node !== null && typeof ( node.a ?? {} ).alt === 'string' && node.a.alt.charAt(0) === '~' ? '/template/'+ S.template+ '/'+ scopeAt( entry.path )+ '/'+ node.a.alt.slice( 1 ) : '';

			out.push( { path : entry.path, key : entry.key, attribute : entry.attribute !== '', alt : alt, label : ( S.images[entry.key] ?? S.pictures[entry.key] ?? {} ).label ?? entry.key.slice( entry.key.lastIndexOf('/') + 1 ) } );
		} );

		return out;
	};

	/**
	 *	The dialog that exchanges a picture of the page where it stands: the picture and the button that uploads another - made at once, given by Save - and the alt text of
	 *	the picture, where it is a text of the page
	 *
	 *	@param		{Object}		target				One of pictureTargets()
	 */
	const openImage = function( target, opener ) {

		const body = el( 'div', 'builder-own-body' );
		const holder = el( 'div', 'builder-picture-holder' );
		const draw = function() {

			holder.innerHTML = '';
			holder.appendChild( pictureBox( target.key, target.label, draw ) );
		};

		draw();
		body.appendChild( holder );

		if( target.alt !== '' ) {

			const alt = Nino.adminUi.textField( { key : 'picture-alt', label : word('picture/alt'), value : textOf( target.alt ), maxLength : 250, hint : word('picture/alt-hint') } );

			alt.querySelector('input').addEventListener( 'change', function( event ) {

				const value = event.target.value;

				change( function() { S.texts[target.alt] = Object.assign( {}, S.texts[target.alt] ?? {}, { [S.locale] : value } ) } );
			} );
			body.appendChild( alt );
		}

		Nino.adminUi.choiceDialog( {
			title			: word( 'picture/replace-of', target.label ),
			message		: word('picture/replace-note'),
			content		: body,
			className	: 'builder-own-dialog',
			opener		: opener,
			choices		: [ { value : 'close', label : word('picture/done'), kind : 'primary' } ],
			onChoose	: function() {

				S.imageBack = target.key;

				if( S.stale === false && S.view !== null )
					imageFocus( S.view.split.frame.contentDocument );
			},
		} );

		( holder.querySelector('button') ?? holder ).focus();
	};

	/**
	 *	The picture of a slot, as it is now - saved or chosen - with the button that uploads another: the file is made at once, and the slot is given it by Save. A slot that is not
	 *	the page's is shown and not changed here
	 *
	 *	@param		{string}		key						The slot
	 *
	 */
	const pictureBox = function( key, label, again ) {

		const box = el( 'div', 'builder-picture' );
		const info = Object.assign( {}, S.images[key], S.pictures[key] );
		const own = key !== '' && key.indexOf( '/template/'+ S.template+ '/' ) === 0;
		const line = el( 'p', 'nino-admin-hint builder-picture-line', '' );

		if( typeof info.url === 'string' && info.url !== '' ) {

			const image = el( 'img', 'builder-picture-image' );

			image.src = info.url;
			image.alt = '';
			box.appendChild( image );
		}
		else
			box.appendChild( el( 'p', 'nino-admin-hint', word( S.pictures[key] !== undefined && S.pictures[key].file === undefined ? 'picture/placeholder' : 'picture/none' ) ) );

		line.setAttribute( 'role', 'status' );

		if( own === false || info.width === undefined ) {
			box.appendChild( el( 'p', 'nino-admin-hint', word( key === '' ? 'picture/no-slot' : 'picture/other' ) ) );
			return box;
		}

		const input = el( 'input', 'builder-picture-file' );
		const button = Nino.adminUi.button( word( typeof info.url === 'string' && info.url !== '' ? 'picture/replace' : 'picture/upload' ), function() { input.click() }, { className : 'nino-admin-btn--small', name : word( 'picture/upload-of', label ) } );

		input.type = 'file';
		input.accept = 'image/*';
		input.hidden = true;
		input.tabIndex = -1;
		button.dataset.key = 'picture:'+ key;
		input.addEventListener( 'change', function() {

			if( input.files.length === 0 )
				return;

			line.textContent = word('picture/sending');

			api( 'upload', { uri : key, width : info.width, height : info.height }, function( status, response ) {

				if( status !== 200 ) {
					line.textContent = Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/upload' );
					return;
				}

				S.staged.push( response.file );
				change( function() {
					S.pictures[key] = Object.assign( {}, S.pictures[key] ?? { label : info.label, width : info.width, height : info.height }, { file : response.file, url : response.url } );
				} );
				told( word( response.belowTarget === true ? 'picture/small' : 'picture/chosen' ) );

				if( typeof again === 'function' )
					again();

				const back = dc.querySelector( '[data-key="picture:'+ key+ '"]' );

				if( back !== null )
					back.focus();
			}, { file : input.files[0] } );
		} );

		box.appendChild( button );
		box.appendChild( input );
		box.appendChild( line );

		if( typeof S.pictures[key] === 'object' && typeof S.pictures[key].file === 'string' )
			box.appendChild( el( 'p', 'nino-admin-hint', word('picture/unsaved') ) );

		return box;
	};

	/**
	 *	The dialog that makes a block of the project of the block of the page that is open: a name, a category and the folder it is kept in. It is made only where the
	 *	kernel takes it as a block; where it does not, the dialog says what is in the way and stays
	 *
	 */
	const openBlockSave = function( opener ) {

		const body = el( 'div', 'builder-own-body' );
		const name = Nino.adminUi.textField( { key : 'own-name', label : word('own/name'), required : true, maxLength : 80, value : '' } );
		const about = Nino.adminUi.textField( { key : 'own-about', label : word('own/about'), maxLength : 240, value : '', hint : word('own/about-hint') } );
		const kinds = [ 'intro', 'content', 'trust', 'offer', 'action', 'contact' ];
		const category = Nino.adminUi.selectField( { key : 'own-category', label : word('own/category'), options : kinds.map( function( kind ) { return { value : kind, label : word( 'insert/cat-'+ kind ) } } ), value : 'content' } );
		const key = Nino.adminUi.textField( { key : 'own-key', label : word('own/key'), maxLength : 40, value : '', hint : word('own/key-hint') } );
		const problem = el( 'p', 'nino-admin-hint builder-source-error', '' );
		let typed = false;
		let made = '';

		problem.setAttribute( 'role', 'alert' );
		problem.hidden = true;
		name.querySelector('input').addEventListener( 'input', function() {
			if( typed === false )
				key.querySelector('input').value = slugOf( name.querySelector('input').value );
		} );
		key.querySelector('input').addEventListener( 'input', function() { typed = true } );

		body.appendChild( el( 'p', 'nino-admin-hint', word('own/note') ) );
		[ name, about, category, key, problem ].forEach( function( part ) { body.appendChild( part ) } );

		const save = Nino.adminUi.button( word('own/save'), function() {

			if( name.querySelector('input').value.trim() === '' || NAME.test( key.querySelector('input').value ) === false ) {
				problem.textContent = word( name.querySelector('input').value.trim() === '' ? 'own/need-name' : 'own/bad-key' );
				problem.hidden = false;
				return;
			}

			save.disabled = true;
			api( 'blocksave', { tree : S.top, at : String( rootAt() ), key : key.querySelector('input').value, name : name.querySelector('input').value.trim(), description : about.querySelector('input').value.trim(), category : category.querySelector('select').value, texts : S.texts, pictures : picturesNow() }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 ) {

					const field = ( response ?? {} ).field === 'key' ? key : ( ( response ?? {} ).field === 'category' ? category : name );

					problem.textContent = Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/own' );
					problem.hidden = false;
					field.querySelector( field === category ? 'select' : 'input' ).focus();
					return;
				}

				S.catalog = null;
				made = name.querySelector('input').value.trim();
				body.closest('dialog').close();
			} );
		}, { className : 'nino-admin-btn-primary' } );

		body.appendChild( save );

		Nino.adminUi.choiceDialog( {
			title			: word('own/title'),
			message		: word('own/note'),
			content		: body,
			className	: 'builder-own-dialog',
			opener		: opener,
			choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function() {

				if( made !== '' )
					told( word( 'own/saved', made ) );
			},
		} );

		name.querySelector('input').focus();
	};

	/**
	 *	The dialog that makes a page: a name and an address for each language of the site - the address is proposed from the name until it is written by hand -, the menus
	 *	the page is in and the block it starts with. What the kernel refuses is said at the field it is about; the page is opened as it stands when it is made
	 *
	 */
	const openNewPage = function( opener ) {

		const data = S.list;
		const locales = data.locales.filter( function( entry ) { return entry.active === true } ).map( function( entry ) { return entry.locale } );
		const body = el( 'div', 'builder-new-body' );
		const fields = {};
		const problems = {};
		const general = el( 'p', 'nino-admin-hint builder-source-error', '' );
		const navs = {};
		let made = null;

		general.setAttribute( 'role', 'alert' );
		general.hidden = true;

		const complain = function( name, text ) {

			const box = problems[name];

			if( box === undefined ) {
				general.textContent = text;
				general.hidden = false;
				return;
			}

			box.textContent = text;
			box.hidden = false;
			fields[name].setAttribute( 'aria-invalid', 'true' );
		};

		body.appendChild( el( 'p', 'nino-admin-hint', word('page/note') ) );
		locales.forEach( function( locale ) {

			const group = el( 'div', 'builder-new-group' );
			const address = Nino.adminUi.textField( { key : 'page-address-'+ locale, label : word('page/address'), required : true, value : '', hint : word('page/address-hint') } );
			const name = Nino.adminUi.textField( { key : 'page-name-'+ locale, label : word('page/name'), required : true, value : '', maxLength : 120 } );
			let touched = false;

			group.setAttribute( 'role', 'group' );
			group.setAttribute( 'aria-label', languageName( locale ) );
			group.appendChild( el( 'h4', 'builder-offer-group', languageName( locale ) ) );
			group.appendChild( name );
			group.appendChild( address );
			fields['name-'+ locale] = name.querySelector('input');
			fields['httpUri-'+ locale] = address.querySelector('input');

			[ 'name-'+ locale, 'httpUri-'+ locale ].forEach( function( at ) {

				const box = el( 'p', 'nino-admin-hint builder-source-error', '' );

				box.id = 'builder-page-problem-'+ at;
				box.hidden = true;
				problems[at] = box;
				fields[at].setAttribute( 'aria-describedby', box.id );
				fields[at].parentNode.insertAdjacentElement( 'afterend', box );
			} );

			address.querySelector('input').addEventListener( 'input', function() { touched = true } );
			name.querySelector('input').addEventListener( 'input', function() {

				if( touched === false ) {

					const slug = slugOf( name.querySelector('input').value, 60 );

					address.querySelector('input').value = slug === '' ? '' : '/'+ slug;
				}
			} );
			body.appendChild( group );
		} );

		if( data.navs.length > 0 ) {

			const menus = el( 'div', 'builder-new-group' );

			menus.setAttribute( 'role', 'group' );
			menus.setAttribute( 'aria-label', word('page/menus') );
			menus.appendChild( el( 'h4', 'builder-offer-group', word('page/menus') ) );
			data.navs.forEach( function( nav ) {

				const label = el( 'label', 'builder-new-check' );
				const box = el( 'input', '' );

				box.type = 'checkbox';
				box.value = nav;
				navs[nav] = box;
				label.appendChild( box );
				label.appendChild( dc.createTextNode( ' '+ ( Nino.content.getText( '/_admin/builder/page/menu-'+ nav ) !== '' ? word( 'page/menu-'+ nav ) : nav ) ) );
				menus.appendChild( label );
			} );
			body.appendChild( menus );
		}

		const chosen = el( 'select', 'nino-admin-input' );
		const startField = el( 'label', 'nino-admin-field' );

		chosen.id = 'builder-page-block';
		startField.appendChild( el( 'span', '', word('page/start') ) );
		startField.appendChild( chosen );
		chosen.appendChild( Object.assign( el( 'option', '', word('page/start-none') ), { value : '' } ) );
		body.appendChild( startField );
		body.appendChild( general );

		Nino.admin.builder.call( 'blocks', {}, function( status, response ) {

			if( status !== 200 )
				return;

			response.blocks.filter( function( entry ) { return entry.off === null } ).forEach( function( entry ) {
				chosen.appendChild( Object.assign( el( 'option', '', localized( entry.name ) ), { value : entry.key } ) );
			} );
		} );

		const make = Nino.adminUi.button( word('page/make'), function() {

			const names = {};
			const addresses = {};

			Object.keys( problems ).forEach( function( at ) {
				problems[at].hidden = true;
				fields[at].removeAttribute('aria-invalid');
			} );
			general.hidden = true;
			locales.forEach( function( locale ) {
				names[locale] = fields['name-'+ locale].value;
				addresses[locale] = fields['httpUri-'+ locale].value;
			} );

			make.disabled = true;
			Nino.admin.builder.call( 'create', { names : names, addresses : addresses, navs : Object.keys( navs ).filter( function( nav ) { return navs[nav].checked } ), block : chosen.value }, function( status, response ) {

				make.disabled = false;

				if( status !== 200 ) {

					const field = ( response ?? {} ).field === 'uri' || ( response ?? {} ).field === 'template' ? 'httpUri-'+ locales[0] : ( response ?? {} ).field;

					complain( field, Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/create' ) );

					if( problems[field] !== undefined )
						fields[field].focus();

					return;
				}

				made = response;
				body.closest('dialog').close();
			} );
		}, { className : 'nino-admin-btn-primary' } );

		body.appendChild( make );

		Nino.adminUi.choiceDialog( {
			title			: word('page/title'),
			message		: word('page/note'),
			content		: body,
			className	: 'builder-new-dialog',
			opener		: opener,
			choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function() {

				if( made !== null )
					Nino.admin.builder.init( function() { Nino.admin.builder.go( [ made.slug ], { focus : 'page' } ) } );
			},
		} );

		if( locales.length > 0 )
			fields['name-'+ locales[0]].focus();
	};


	// --- the settings of a component, by the schema ---

	const ON = [ '1', 'true', 'yes', 'on' ];

	const ensureAttrs = function( node ) {

		if( node.a === undefined || node.a === null || Array.isArray( node.a ) === true )
			node.a = {};

		return node.a;
	};

	// Set an attribute of the component at a place - or take it away where the value is none or is the default the register draws without it
	const setAttr = function( rel, name, value ) {

		change( function() {

			const attrs = ensureAttrs( relNode( rel ) );

			if( value === null )
				delete attrs[name];
			else
				attrs[name] = value;
		} );
	};

	const attrWord = function( declared, option ) {

		const labels = declared.optionLabels;

		return labels !== null && labels !== undefined && labels[option] !== undefined ? localized( labels[option] ) : String( option );
	};

	// The three widths of a responsive choice - everywhere, from a tablet, from a desktop - as the parts of the value "100 m:33"
	const widthsOf = function( value ) {

		const out = { '' : '', m : '', l : '' };

		String( value ?? '' ).split( /\s+/ ).filter( function( part ) { return part !== '' } ).forEach( function( part ) {

			const colon = part.indexOf(':');

			if( colon === -1 )
				out[''] = part;
			else if( out[ part.slice( 0, colon ) ] !== undefined )
				out[ part.slice( 0, colon ) ] = part.slice( colon + 1 );
		} );

		return out;
	};

	const widthsWritten = function( widths, standard ) {

		const parts = [ widths[''] !== '' ? widths[''] : standard ];

		[ 'm', 'l' ].forEach( function( at ) {
			if( widths[at] !== '' )
				parts.push( at+ ':'+ widths[at] );
		} );

		return parts.join(' ');
	};

	const fieldNote = function( field, declared ) {

		const hint = localized( declared.hint );

		if( hint !== '' )
			field.appendChild( el( 'span', 'nino-admin-hint builder-field-hint', hint ) );

		return field;
	};

	// One attribute as the control its type says: a choice as a pick or a strip of steps, a responsive choice as three rows, a bool as a switch, a number, a line, a link
	const attributeField = function( rel, name, declared ) {

		const node = relNode( rel );
		const given = ( node.a ?? {} )[name];
		const label = localized( declared.label );
		const standard = declared.default;
		const key = rel+ ':'+ name;

		// What an attribute reads - a text, an address, a picture - is a source: its sentence and the dialog that changes it
		if( declared.type === 'text' || declared.type === 'link' || declared.type === 'image' ) {

			const spec = { kind : declared.type, attr : name, label : label };
			const written = typeof given === 'string' ? given : '';
			const field = sourceField( rel, spec, sayOf( spec, written, rel ) );

			if( declared.type === 'image' )
				field.appendChild( pictureBox( keyOf( rel, written ), label ) );

			return fieldNote( field, declared );
		}

		if( declared.type === 'choice' ) {

			// A choice between off and on is a switch
			if( declared.responsive !== true && declared.options.length === 2 && declared.options.indexOf('off') !== -1 && declared.options.indexOf('on') !== -1 ) {

				const field = Nino.adminUi.switchField( { key : key, checked : ( given === undefined ? standard : String( given ) ) === 'on', label : label } );

				field.querySelector('input').addEventListener( 'change', function( event ) {

					const now = event.target.checked === true ? 'on' : 'off';

					setAttr( rel, name, now === standard ? null : now );
				} );

				return fieldNote( field, declared );
			}

			// A width is a share of the row: the numbers say so
			const percent = name === 'size' && declared.responsive === true && ( declared.optionLabels === null || declared.optionLabels === undefined ) && declared.options.every( function( option ) { return /^\d+$/.test( option ) } );
			const options = declared.options.map( function( option ) { return { value : option, label : percent === true ? option+ ' %' : attrWord( declared, option ) } } );

			if( declared.responsive === true ) {

				const widths = widthsOf( given ?? standard );
				const group = el( 'div', 'builder-widths-set' );
				const caption = el( 'span', 'builder-field-label', label );

				caption.id = 'builder-widths-'+ rel.replace( /\./g, '-' )+ '-'+ name;
				group.setAttribute( 'role', 'group' );
				group.setAttribute( 'aria-labelledby', caption.id );
				group.appendChild( caption );

				[ [ '', 'rows/all' ], [ 'm', 'rows/tablet' ], [ 'l', 'rows/desktop' ] ].forEach( function( row ) {

					const choices = row[0] === '' ? options : [ { value : '', label : row[0] === 'm' ? word('rows/below-tablet') : word('rows/below-desktop') } ].concat( options );

					group.appendChild( Nino.adminUi.selectField( {
						key			: key+ ':'+ row[0],
						label		: word( row[1] ),
						options	: choices,
						value		: widths[ row[0] ],
						className	: 'nino-admin-field builder-width-row',
						onChange	: function( value ) {

							const next = widthsOf( given ?? standard );

							next[ row[0] ] = value;

							const text = widthsWritten( next, standard );

							setAttr( rel, name, text === standard ? null : text );
						},
					} ) );
				} );

				return fieldNote( group, declared );
			}

			const current = given === undefined ? standard : String( given );

			// A value that is none of the options stays where it is, and says so
			const stray = given !== undefined && declared.options.indexOf( String( given ) ) === -1;

			if( stray === true )
				options.push( { value : String( given ), label : word( 'field/outside', String( given ) ) } );

			const wide = stray === true || options.length > 6 || options.some( function( option ) { return option.label.length > 8 } );
			const set = function( value ) { setAttr( rel, name, value === standard ? null : value ) };

			if( wide === true )
				return fieldNote( Nino.adminUi.selectField( { key : key, label : label, options : options, value : current, onChange : set } ), declared );

			const box = el( 'div', 'nino-admin-field builder-steps-field' );
			const caption = el( 'span', 'builder-field-label', label );
			const steps = Nino.adminUi.steps( { labelledby : 'builder-steps-'+ rel.replace( /\./g, '-' )+ '-'+ name, steps : options, value : current, standard : standard, onChange : set } );

			// The strip is drawn again with the page of the settings: the focus finds its way back to it by this
			box.dataset.stepKey = key;
			S.strips[key] = steps;
			caption.id = 'builder-steps-'+ rel.replace( /\./g, '-' )+ '-'+ name;
			box.appendChild( caption );
			box.appendChild( steps.el );

			return fieldNote( box, declared );
		}

		if( declared.type === 'bool' ) {

			const on = given === undefined ? standard === true : ( given === true || ON.indexOf( String( given ).toLowerCase() ) !== -1 );
			const field = Nino.adminUi.switchField( { key : key, checked : on, label : label } );

			field.querySelector('input').addEventListener( 'change', function( event ) {

				const now = event.target.checked === true;

				setAttr( rel, name, now === ( standard === true ) ? null : ( now === true ? true : 'off' ) );
			} );

			return fieldNote( field, declared );
		}

		if( declared.type === 'int' ) {

			const field = Nino.adminUi.numberField( { key : key, label : label, value : given === undefined ? standard : Number( given ), min : declared.min ?? undefined, max : declared.max ?? undefined } );

			field.querySelector('input').addEventListener( 'change', function( event ) {

				const value = String( event.target.value ).trim();

				setAttr( rel, name, value === '' || value === String( standard ) ? null : value );
			} );

			return fieldNote( field, declared );
		}

		if( declared.type === 'id' ) {

			const field = el( 'div', 'nino-admin-field' );

			field.appendChild( el( 'span', 'builder-field-label', label ) );
			field.appendChild( el( 'code', 'builder-field-value', given === undefined ? '-' : String( given ) ) );
			field.appendChild( el( 'span', 'nino-admin-hint builder-field-hint', word( 'field/read-'+ declared.type ) ) );

			return field;
		}

		const line = Nino.adminUi.textField( { key : key, label : label, value : given === undefined ? '' : String( given ) } );

		line.querySelector('input').addEventListener( 'change', function( event ) {
			setAttr( rel, name, event.target.value === '' ? null : event.target.value );
		} );

		return fieldNote( line, declared );
	};

	// The variant: the schema's and the ones the theme adds - a word of a variant that the theme does not have stays where it is, with the sentence of the register
	const variantField = function( rel, node, schema ) {

		const given = typeof ( node.a ?? {} ).variant === 'string' ? node.a.variant : '';
		const labelOf = function( variant ) { return variantLabel( schema, variant ) };
		const options = [ { value : '', label : word( 'variant/standard', labelOf( schema.standard ) ) } ].concat( schema.variants.map( function( variant ) { return { value : variant, label : labelOf( variant ) } } ) );

		if( given !== '' && schema.variants.indexOf( given ) === -1 )
			options.push( { value : given, label : word( 'variant/missing', given ) } );

		return Nino.adminUi.selectField( { key : rel+ ':variant', label : word('field/variant'), options : options, value : given, onChange : function( value ) { setAttr( rel, 'variant', value === '' ? null : value ) } } );
	};

	// The words of a component: a text written in the language of the view - a fixed one for all - and in a sentence where it is read
	// The markup a call of html holds, for the account that may develop - the component cleans it where it draws it; with calls inside it is shown and not changed
	const htmlBody = function( rel, node ) {

		const body = el( 'div', 'builder-adv-content' );
		const field = el( 'label', 'nino-admin-field' );
		const control = el( 'textarea', 'nino-admin-input' );
		const calls = ( node.c ?? [] ).some( function( inner ) { return inner.t === undefined } );

		field.appendChild( el( 'span', 'builder-field-label', word('field/content') ) );
		field.appendChild( el( 'span', 'nino-admin-badge builder-field-chip', word('field/all-languages') ) );
		control.value = ( node.c ?? [] ).map( function( inner ) { return inner.t ?? '' } ).join('');
		control.rows = 10;
		control.dataset.key = rel+ ':content';

		if( calls === true ) {
			control.readOnly = true;
			control.setAttribute( 'aria-readonly', 'true' );
		}
		else
			control.addEventListener( 'change', function() {
				change( function() { relNode( rel ).c = control.value === '' ? [] : [ { t : control.value } ] } );
			} );

		field.appendChild( control );
		body.appendChild( field );
		body.appendChild( el( 'p', 'nino-admin-hint builder-source', word( calls === true ? 'field/html-calls' : 'field/html-note' ) ) );

		return body;
	};

	const contentBody = function( rel ) {

		const node = relNode( rel );
		const source = sourceOf( rel );
		const body = el( 'div', 'builder-adv-content' );

		if( node.n === 'html' && S.level >= DEVELOP )
			return htmlBody( rel, node );

		// A loop shows the elements of a type: the type is its source
		if( source === null && S.schemas[node.n].loop === true )
			return typeBody( rel, node, body );

		if( source === null )
			return body;

		if( source.kind === 'text' || source.kind === 'fixed' ) {

			const rich = source.kind === 'text' && source.format !== 'plain' && source.format !== 'inline';
			const value = source.kind === 'fixed' ? node.a.text : textOf( source.key );
			const field = el( 'label', 'nino-admin-field' );
			const control = el( rich === true ? 'textarea' : 'input', 'nino-admin-input' );

			field.appendChild( el( 'span', 'builder-field-label', word('field/content') ) );
			field.appendChild( el( 'span', 'nino-admin-badge builder-field-chip', source.kind === 'fixed' ? word('field/all-languages') : languageName( S.locale ) ) );
			control.value = value;
			control.dataset.key = rel+ ':content';

			// A text the language of the view has none of is shown as the page shows it - the text of the next language - and says so: the field begins empty
			if( source.kind === 'text' && source.key !== '' && value.trim() === '' && fallbackText( source.key ) !== '' ) {

				control.placeholder = fallbackText( source.key );
				field.appendChild( el( 'span', 'nino-admin-hint builder-field-hint', word( 'hint/missing', languageName( S.locale ) ) ) );
			}

			if( rich === true )
				control.rows = 5;
			else
				control.type = 'text';

			if( source.editable === false ) {
				control.readOnly = true;
				control.setAttribute( 'aria-readonly', 'true' );
			}
			else
				control.addEventListener( 'change', function() {
					settle( { kind : source.kind, key : source.key, format : source.format }, absOf( rel ), control.value, value, true );
				} );

			field.appendChild( control );
			body.appendChild( field );
		}

		// The source in one sentence; changing it is for the dialog of the source
		const sentence = source.written.charAt(0) === '.' ? sayOf( { kind : source.kind }, source.written, rel ) : source.kind === 'fixed' ? word('source/fixed')
			: ( source.kind === 'image' ? word('source/image')
			: ( source.kind === 'link' ? word('source/link')
			: ( source.editable === true ? word( S.frame === true ? 'source/own-frame' : 'source/own' ) : ( source.key === '' ? word('source/none') : word('source/other') ) ) ) );
		const line = el( 'p', 'nino-admin-hint builder-source', sentence );

		body.appendChild( line );

		const spec = { kind : source.kind === 'fixed' ? 'text' : source.kind, attr : '', label : word('field/content') };
		const other = Nino.adminUi.button( word('source/change'), function() { openSource( rel, spec, other ) }, { className : 'nino-admin-btn--small', name : word( 'source/change-of', spec.label ) } );

		other.dataset.key = rel+ ':source:';
		body.appendChild( other );

		if( source.kind === 'image' && source.written.charAt(0) !== '.' )
			body.appendChild( pictureBox( source.key, spec.label ) );

		return body;
	};

	// What a loop shows: the type of elements it names as its first word, in the words the project gives the type, and the dialog that chooses another
	const typeBody = function( rel, node, body ) {

		const given = typeof node.s === 'string' ? node.s : '';
		const line = el( 'p', 'nino-admin-hint builder-source', '' );
		const say = function() { line.textContent = given === '' ? word('type/none') : word( 'type/is', ( S.types.find( function( entry ) { return entry.type === given } ) ?? { title : given.indexOf('/_') === 0 ? word('type/example') : given } ).title ) };
		const choose = Nino.adminUi.button( word('type/change'), function() { openType( rel, choose ) }, { className : 'nino-admin-btn--small' } );

		choose.dataset.key = rel+ ':source:';
		say();
		body.appendChild( line );
		body.appendChild( choose );

		if( S.types.length === 0 && S.typesAsked === false ) {

			S.typesAsked = true;
			api( 'keys', { kind : 'types' }, function( status, response ) {

				if( status === 200 && response.types.length > 0 ) {
					S.types = response.types;
					say();
				}
			} );
		}

		return body;
	};

	// The types of elements of the project, to choose what a loop shows
	const openType = function( rel, opener ) {

		api( 'keys', { kind : 'types' }, function( status, response ) {

			if( status !== 200 ) {
				S.view.bar.line.error( status, response );
				return;
			}

			S.types = response.types;

			const body = el( 'div', 'builder-offer' );
			const list = el( 'ul', 'builder-offer-list' );
			let picked = '';

			if( response.types.length === 0 )
				body.appendChild( Nino.adminUi.emptyState( word('type/empty') ) );

			response.types.forEach( function( entry ) {

				const item = el( 'li', 'builder-offer-item' );
				const button = el( 'button', 'nino-admin-btn builder-offer-pick', entry.title );

				button.type = 'button';
				button.addEventListener( 'click', function() {
					picked = entry.type;
					button.closest('dialog').close();
				} );
				item.appendChild( button );
				list.appendChild( item );
			} );
			body.appendChild( list );

			Nino.adminUi.choiceDialog( {
				title			: word('type/title'),
				message		: word('type/note'),
				content		: body,
				className	: 'nino-admin-dialog--manual builder-offer-dialog',
				opener		: opener,
				choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
				onChoose	: function() {

					if( picked !== '' )
						change( function() { relNode( rel ).s = picked } );
				},
			} );

			const first = body.querySelector('button');

			if( first !== null )
				first.focus();
		} );
	};

	const lookBody = function( rel ) {

		const node = relNode( rel );
		const schema = S.schemas[node.n];
		const body = el( 'div', 'builder-adv-look' );
		const names = Object.keys( schema.attributes );

		if( schema.variants.length > 0 )
			body.appendChild( variantField( rel, node, schema ) );

		names.forEach( function( name ) {
			body.appendChild( attributeField( rel, name, schema.attributes[name] ) );
		} );

		if( names.length === 0 && schema.variants.length === 0 )
			body.appendChild( el( 'p', 'nino-admin-hint', word('field/none') ) );

		return body;
	};

	// --- the two levels of the left column ---

	const blockTitle = function() {

		if( S.frame === true )
			return word( 'word/'+ S.template );

		const node = S.top[rootAt()];

		return node === undefined ? '' : titleOf( node );
	};

	// The notation as the template has it, which the server writes of the draft: the whole block, or the first line of a component
	const notationBox = function( id, which ) {

		const box = el( 'div', 'builder-notation' );
		const code = el( 'pre', 'builder-code', S.notation[which] ?? '' );

		code.id = id;
		code.tabIndex = 0;
		box.appendChild( el( 'span', 'builder-field-label', which === 'block' ? word('field/notation-block') : word('field/notation') ) );
		box.appendChild( code );

		return box;
	};

	const refreshNotation = function() {

		[ [ 'builder-notation-block', 'block' ], [ 'builder-notation-node', 'node' ] ].forEach( function( pair ) {

			const code = dc.getElementById( pair[0] );

			if( code !== null )
				code.textContent = S.notation[ pair[1] ] ?? '';
		} );
	};

	const treeLevel = function( section ) {

		const holder = el( 'div', 'builder-adv-level' );
		const rootNode = S.top[rootAt()];
		const treeBox = el( 'div', 'builder-adv-tree' );
		const settings = el( 'div', 'builder-adv-block' );
		const idLine = el( 'p', 'builder-adv-id' );

		S.tree = Nino.adminUi.tree( {
			id				: 'builder-tree',
			label			: word('adv/tree-label'),
			items			: itemsNow(),
			selected	: S.marked === '' ? 'root' : S.marked,
			onSelect	: function( id ) { Nino.admin.builder.go( partsOf( id, sectionOf( id ) ), { focus : 'back' } ) },
			onAction	: function( key, id ) {

				const opener = S.tree.el.querySelector( '[data-key="'+ key+ '"]' );

				if( key === 'add' )
					openOffer( id, dc.activeElement ?? opener );
				else if( key === 'remove' && lockedNode( relNode( id ) ) === true )
					askRemoveUnit( id, dc.activeElement ?? opener );
				else if( key === 'remove' )
					removeComponent( id );
				else
					moveComponent( id, key === 'up' ? -1 : 1 );
			},
			// The entry that has the stop is the one the preview marks; the tree is not drawn again here - a press on a button of another row moves the stop first
			onFocus		: function( id ) {

				S.marked = id;

				if( S.mark !== null )
					S.mark( true );
			},
		} );
		treeBox.appendChild( S.tree.el );

		idLine.appendChild( el( 'span', 'builder-field-label', word('field/id') ) );
		idLine.appendChild( el( 'code', 'builder-field-value', S.frame === true ? ( idOf( rootNode ) === '' ? '-' : idOf( rootNode ) ) : S.adv.block ) );
		settings.appendChild( idLine );
		settings.appendChild( el( 'p', 'nino-admin-hint', word( 'adv/block-sentence', countCalls( rootNode ) ) ) );

		// A block of the page is kept as a block of the project, for the pages that are made after it
		if( S.frame === false && S.level >= 2 ) {

			const own = Nino.adminUi.button( word('own/open'), function() { openBlockSave( own ) }, { className : 'nino-admin-btn--small' } );

			own.dataset.key = 'block:own';
			settings.appendChild( own );
		}

		settings.appendChild( notationBox( 'builder-notation-block', 'block' ) );

		const accordion = Nino.adminUi.accordion( {
			id			: 'builder-acc',
			level		: 3,
			open		: section === 'block' ? 'block' : 'tree',
			sections	: [
				{ key : 'tree', label : word('adv/tree'), summary : word( 'adv/count', countCalls( rootNode ) ), content : treeBox },
				{ key : 'block', label : word('adv/settings'), summary : blockTitle(), content : settings },
			],
			onToggle	: function( open ) {

				S.adv.section = open[0] === 'block' ? 'block' : '';
				S.adv.path = S.adv.section === 'block' ? '@block' : '';
				S.at = partsOf( '', S.adv.section === '' ? 'tree' : 'block' );
				Nino.admin.router.go( 'builder', S.at );
			},
		} );

		holder.appendChild( accordion.el );
		S.adv.accordion = accordion;

		return holder;
	};

	const componentLevel = function( rel, section ) {

		const node = relNode( rel );
		const schema = S.schemas[node.n];
		const holder = el( 'div', 'builder-adv-level' );
		const back = el( 'a', 'nino-admin-back-link', word('label/back-tree') );
		const keys = [ 'look' ];

		back.id = 'builder-back-tree';
		back.href = hrefOf( partsOf( '', 'tree' ) );
		back.setAttribute( 'data-keep-draft', '' );
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.builder.go( partsOf( '', 'tree' ), { focus : 'tree', from : rel } );
		} );
		holder.appendChild( back );
		holder.appendChild( el( 'h3', 'builder-name builder-adv-title', titleOfRel( rel ) ) );

		const finding = hintOf( absOf( rel ) );

		if( finding !== undefined )
			holder.appendChild( Nino.adminUi.notice( finding.text ) );

		if( schema.source !== 'none' || schema.loop === true || ( node.n === 'html' && S.level >= DEVELOP ) )
			keys.unshift( 'content' );

		const open = keys.indexOf( section ) === -1 ? keys[0] : section;
		const accordion = Nino.adminUi.accordion( {
			id			: 'builder-acc',
			level		: 4,
			open		: open,
			sections	: keys.map( function( key ) {

				return key === 'content'
					? { key : key, label : word('adv/content'), summary : ownText( node, rel, 40 ), content : function() { return contentBody( rel ) } }
					: { key : key, label : word('adv/settings'), summary : variantSummary( node ), content : function() { return lookBody( rel ) } };
			} ),
			onToggle	: function( now ) {

				S.adv.section = now[0] ?? open;
				S.at = partsOf( rel, S.adv.section );
				Nino.admin.router.go( 'builder', S.at );
			},
		} );

		holder.appendChild( accordion.el );
		holder.appendChild( notationBox( 'builder-notation-node', 'node' ) );
		S.adv.accordion = accordion;

		return holder;
	};

	// The section of a component that opens first: its content where it has one, else its settings
	const sectionOf = function( rel ) {

		const node = relNode( rel );

		return rel === 'root' || node === null || ( S.schemas[node.n].source === 'none' && ( node.n !== 'html' || S.level < DEVELOP ) ) ? 'look' : 'content';
	};

	const variantSummary = function( node ) {

		const count = Object.keys( node.a ?? {} ).filter( function( name ) { return name !== 'id' } ).length;

		return count === 0 ? word('adv/defaults') : word( 'adv/given', count );
	};

	// The left column of the extended view: the level the address names. The focus goes where the move says: into a component on its way back, out of it on the entry
	const drawLeft = function( ask ) {

		const view = S.view;
		const rel = S.adv.path;
		const held = dc.activeElement !== null && dc.activeElement.dataset !== undefined ? dc.activeElement.dataset.key : undefined;
		const strip = dc.activeElement !== null && dc.activeElement.closest !== undefined && dc.activeElement.closest('[data-step-key]') !== null ? dc.activeElement.closest('[data-step-key]').dataset.stepKey : undefined;
		const level = rel === '' || rel === '@block' || relNode( rel ) === null || S.schemas[ relNode( rel ).n ] === undefined ? 'tree' : 'component';

		S.tree = null;
		S.strips = {};
		view.split.settings.innerHTML = '';
		view.split.words( { settings : word( level === 'tree' ? 'adv/tree' : 'adv/settings' ), preview : word('label/preview') } );

		if( level === 'tree' ) {

			// The block itself is the one that is marked, until the focus is on another entry
			if( S.marked === '' || S.marked === '@block' || relNode( S.marked ) === null )
				S.marked = 'root';

			S.adv.view = 'tree';
			view.split.settings.appendChild( treeLevel( rel === '@block' ? 'block' : '' ) );
			S.look = { block : absOf( 'root' ), node : '' };
		}
		else {
			S.adv.view = 'component';
			S.marked = rel;
			view.split.settings.appendChild( componentLevel( rel, S.adv.section ) );
			S.look = { block : absOf( 'root' ), node : absOf( rel ) };
		}

		if( held !== undefined ) {

			const again = view.split.settings.querySelector( '[data-key="'+ held+ '"]' );

			if( again !== null )
				again.focus();
		}
		else if( strip !== undefined && S.strips[strip] !== undefined )
			S.strips[strip].focus();

		// What is written of the block and of the component is what the server writes of the draft: it is asked for when the level changes, and the answer of
		// each draft brings it again
		if( ask !== false )
			api( 'notation', { tree : S.top, look : S.look }, function( status, response ) {

				if( status === 200 && S.mode === 'advanced' ) {
					S.notation = response.notation;
					refreshNotation();
				}
			} );
	};

	// What a change does to the left column of the extended view
	const paintAdvanced = function() {

		const view = S.view;

		paintBar( view );
		view.buttons.undo.disabled = S.undo.length === 0;
		view.buttons.redo.disabled = S.redo.length === 0;

		if( rootAt() === -1 ) {
			Nino.admin.builder.go( [ S.frame === true ? addressOf( S.template ) : S.adv.slug ] );
			return;
		}

		const advFocus = S.advFocus;

		S.advFocus = '';

		if( S.adv.view === 'tree' && S.tree !== null ) {

			S.tree.update( itemsNow() );

			if( advFocus !== '' )
				S.tree.focus( advFocus );

			if( S.adv.accordion !== null )
				S.adv.accordion.update( 'tree', { summary : word( 'adv/count', countCalls( S.top[rootAt()] ) ) } );

			refreshNotation();
		}
		else
			drawLeft( false );

		if( S.mark !== null )
			S.mark( false );
	};

	// The page in the frame of the extended view: the call that is chosen has a box round it, which follows the page where it is laid out again, and a click on a
	// component of the block chooses it. Nothing is written in the frame here
	const instrumentAdvanced = function( frame, doc ) {

		const found = traced( doc );
		const style = doc.createElement('style');
		const host = doc.createElement('nino-builder-mark');
		const shadow = host.attachShadow( { mode : 'open' } );
		const box = doc.createElement('div');
		const css = doc.createElement('style');
		const win = frame.contentWindow;

		style.textContent = '[data-nino-pick]{cursor:pointer}';
		doc.head.appendChild( style );
		css.textContent = ':host{all:initial;position:absolute;top:0;left:0;width:0;height:0;z-index:2147483000}.mark{position:absolute;pointer-events:none;box-sizing:border-box;border:2px solid #2f6fed;background:rgba(47,111,237,.1);border-radius:3px}';
		shadow.appendChild( css );
		box.className = 'mark';
		shadow.appendChild( box );
		host.setAttribute( 'aria-hidden', 'true' );
		doc.body.appendChild( host );

		S.mark = function( scroll ) {

			const wanted = rootAt() === -1 || S.marked === '' ? null : found.all.find( function( entry ) { return entry.path === absOf( S.marked ) } );
			const place = wanted === undefined || wanted === null ? null : boxOf( wanted.elements, win );

			box.style.display = place === null ? 'none' : '';

			if( place === null )
				return;

			box.style.top = place.top+ 'px';
			box.style.left = place.left+ 'px';
			box.style.width = place.width+ 'px';
			box.style.height = place.height+ 'px';

			if( scroll === true )
				win.scrollTo( { top : Math.max( 0, place.top - win.innerHeight / 3 ) } );
		};

		// The box follows the component where the page is laid out again: a width of the preview, a picture that arrives
		if( typeof win.ResizeObserver === 'function' )
			new win.ResizeObserver( function() { if( S.mark !== null ) S.mark( false ) } ).observe( doc.body );

		win.addEventListener( 'resize', function() { if( S.mark !== null ) S.mark( false ) } );
		doc.querySelectorAll('a, button, input, select, textarea, summary, [tabindex]').forEach( function( element ) { element.tabIndex = -1 } );
		doc.addEventListener( 'submit', function( event ) { event.preventDefault() }, true );
		doc.addEventListener( 'keydown', function( event ) { keys( event, [], doc ) } );
		doc.addEventListener( 'click', function( event ) {

			if( event.target.closest && event.target.closest('a') !== null )
				event.preventDefault();

			const root = rootAt();
			let best = null;

			found.all.forEach( function( entry ) {

				const inside = entry.path === String( root ) || entry.path.indexOf( root+ '.' ) === 0;

				if( inside === true && entry.elements.some( function( element ) { return element.contains( event.target ) } ) === true && ( best === null || entry.path.length > best.path.length ) )
					best = entry;
			} );

			if( best === null || S.stale === true )
				return;

			const rel = best.path === String( root ) ? 'root' : best.path.slice( String( root ).length + 1 );
			const node = relNode( rel );

			if( node !== null && S.schemas[node.n] !== undefined )
				Nino.admin.builder.go( partsOf( rel, sectionOf( rel ) ), { focus : 'back' } );
		}, true );

		S.mark( true );
	};

	// --- the list ---------------------------------------------------------------------------------------------------

	const rowOf = function( template, name, hint, chips, parts ) {

		const row = el( 'li', 'builder-page-row' );
		const link = el( 'a', 'builder-page-link' );
		const at = parts ?? [ addressOf( template ) ];

		link.href = Nino.admin.router.href( 'builder', at );
		link.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.builder.go( at );
		} );
		link.appendChild( el( 'strong', 'builder-page-name', name ) );
		link.appendChild( el( 'span', 'nino-admin-hint', hint ) );
		chips.forEach( function( chip ) { link.appendChild( el( 'span', 'nino-admin-badge', chip ) ) } );
		row.appendChild( link );

		return row;
	};

	const render = function( data ) {

		S.list = data;
		S.level = data.level ?? S.level;

		const mount = dc.getElementById('builder-list');
		const pages = el( 'ul', 'builder-pages' );
		const frames = el( 'ul', 'builder-pages' );

		mount.innerHTML = '';

		data.pages.forEach( function( page ) { pages.appendChild( rowOf( page.template, page.name, page.path, page.locales.map( function( locale ) { return languageName( locale, true ) } ) ) ) } );
		data.frames.forEach( function( frame ) { frames.appendChild( rowOf( frame.template, word( 'word/frame-'+ frame.key ), word('hint/frame-short'), [], data.pages.length > 0 ? [ addressOf( data.pages[0].template ), addressOf( frame.template ) ] : undefined ) ) } );

		mount.appendChild( el( 'h3', 'builder-heading', word('label/pages') ) );

		// A page is made by the one who may design and may write routes: the list says so, and the action asks again
		if( data.create === true ) {

			const make = Nino.adminUi.button( word('page/open'), function() { openNewPage( make ) }, { className : 'nino-admin-btn--small', id : 'builder-new-page' } );

			mount.appendChild( make );
		}

		if( data.pages.length === 0 )
			mount.appendChild( Nino.adminUi.emptyState( word('msg/none') ) );
		else
			mount.appendChild( pages );

		if( data.frames.length > 0 ) {
			mount.appendChild( el( 'h3', 'builder-heading', word('label/frames') ) );
			mount.appendChild( frames );
		}

		mount.appendChild( Nino.adminUi.hint( word('hint/list') ) );
	};

	// --- the panel --------------------------------------------------------------------------------------------------

	Nino.admin.builder = Nino.adminUi.panel( {
		uri		: 'builder',
		mount	: 'builder-list',
		render	: render,
		show	: 'always',
		onError	: function() { Nino.adminUi.showLevel( 'builder-list', { focus : false } ) },
		dirty	: {
			isDirty	: isDirty,
			save		: function( done ) { S.view.bar.press( done ) },
			bar			: function() { return S.view === null ? null : S.view.bar.el },
			discard	: function() { discard() },
		},
	} );

	Object.assign( Nino.admin.builder, {

		// The model, for what drives it without a page
		model : {
			state : S, normalize : normalize, kindOf : kindOf, neighbour : neighbour, move : move, duplicate : duplicate, remove : remove,
			undo : undo, redo : redo, isDirty : isDirty, nodeAt : nodeAt, templateOf : templateOf, addressOf : addressOf,
			items : items, titleOf : titleOf, settle : settle, free : free, possible : possible, points : points, insert : insert, insertedNow : insertedNow, tileFrame : tileFrame,
			targetOf : targetOf, rootAt : rootAt, absOf : absOf, itemsNow : itemsNow, siblingAt : siblingAt, moveComponent : moveComponent, removeComponent : removeComponent,
			addComponent : addComponent, sourceOf : sourceOf, setAttr : setAttr, widthsOf : widthsOf, widthsWritten : widthsWritten, fixAttrs : fixAttrs, hintOf : hintOf, partsOf : partsOf,
			slugOf : slugOf, loopOf : loopOf, keyOf : keyOf, applySource : applySource, fallbackText : fallbackText, picturesNow : picturesNow, pictureTargets : pictureTargets, layoutsOf : layoutsOf, freshText : freshText, backOf : backOf, paintBar : paintBar, hintWord : hintWord,
		},

		go : function( parts, options ) {

			const target = targetOf( parts );
			const leaves = S.ready === true && ( parts.length === 0 || target === null || target.template !== S.template || target.page !== S.page );

			S.focusNext = ( options ?? {} ).focus ?? '';
			S.focusFrom = ( options ?? {} ).from ?? '';

			Nino.admin.dirty.guard( leaves === true ? [ 'builder' ] : [], function() {
				Nino.admin.router.go( 'builder', parts );
				Nino.admin.builder.follow();
			} );
		},

		follow : function() {

			const at = here();
			const parts = at.panel === 'builder' ? at.parts : [];

			if( parts.length === 0 ) {

				if( S.view !== null )
					S.view.preview.stop();

				S.ready = false;
				Nino.admin.router.set( 'builder', [] );
				Nino.adminUi.showLevel( 'builder-list', { focus : true } );
				return;
			}

			const target = targetOf( parts );

			if( target === null ) {
				Nino.admin.builder.go( [] );
				return;
			}

			// The page that is open is shown again - at another level of the extended view where the address says so -, and any other is opened
			if( target.template === S.template && target.page === S.page && S.ready === true ) {
				show( target );
				return;
			}

			openPage( target, function() {} );
		},

		showCurrent : function() {

			const at = here();

			if( Nino.admin.builder.ready === true && at.panel !== 'builder' )
				return;

			const proceed = function() { Nino.admin.builder.follow() };

			if( Nino.admin.builder.ready === false ) {
				Nino.admin.builder.init( proceed );
				return;
			}

			const there = targetOf( at.parts );
			const leaving = S.ready === true && ( at.parts.length === 0 || there === null || there.template !== S.template || there.page !== S.page );

			Nino.admin.router.leave( [ 'builder' ], leaving, proceed, function() {
				Nino.admin.router.set( 'builder', S.ready === true ? S.at : [] );
			} );
		},
	} );

	// The keys of the workbench over a page that is open: the undo and the redo, where no field takes the keys for itself
	if( S.keys === false ) {

		S.keys = true;

		dc.addEventListener( 'keydown', function( event ) {

			if( S.ready === false || here().panel !== 'builder' || event.ctrlKey === false && event.metaKey === false )
				return;

			const held = dc.activeElement;

			if( held !== null && held !== dc.body && ( held.isContentEditable === true || /^(INPUT|TEXTAREA|SELECT)$/.test( held.tagName ) === true ) )
				return;

			const key = String( event.key ).toLowerCase();

			if( key === 'z' || key === 'y' ) {
				event.preventDefault();
				key === 'y' || event.shiftKey === true ? redo() : undo();
			}
		} );
	}

} )(window,document);
