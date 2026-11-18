/**
 *	Nino features
 *	Builder						The panel of the workbench for the pages: the list of them, and for a page the page itself - drawn by the website as a draft, in
 *										a frame - with a bar on each block that moves, copies or removes it, and the texts of the page written where they stand. Everything
 *										it shows is the answer of the API (see Modules\Builder\Admin): the page as it is saved from open, the draft from preview. A change
 *										is a change of the working state - the tree of the template and the words of its namespace - and nothing is written before Save.
 *
 *										The address is #builder (the list), #builder/<page> (a page: its template without page-), #builder/@new (the view that makes one; with a page behind it,
 *										#builder/@new/<page>, the view that makes a copy of that page) and #builder/@header or #builder/@footer
 *										(a frame of the site). The extended view of a block is #builder/<page>/<block> - the tree of its components - and the frames are the same
 *										views at #builder/<page>/@header and #builder/<page>/@footer. They are for the account that may design. The properties of a component have no address
 *										of their own: they are the lightbox that the pencil at the component, the row of the tree and the row of the outline open.
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
 *										neighbours, taken out - the undo brings it back - and has its properties, by the schema of its component, in the lightbox.
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
	const LAYOUT		= { mobile : 390, tablet : 768, desktop : null };
	const DESKTOP		= 1280;

	// The step that writes the markup in a call of html
	const DEVELOP		= 3;

	// Below this width of the preview a person who has chosen no width is shown the page as a phone shows it
	const NARROW		= 520;

	// The words after an id that is a copy of another: hero-2, and the longest id there is
	const MAX_ID		= 64;

	// The longest word an address is made a name of a template of - what the server takes - and the address of the view that makes a page: no word of a page can start with @, as the frames' neither
	const MAX_SLUG	= 60;
	const NEW				= '@new';

	// How long a text typed in the page may be, in characters: the server keeps 20000 bytes of what a format makes of it
	const MAX_TEXT	= 5000;

	// How long the words in the name of a block are, in characters
	const NAMED			= 24;

	// How strong the other blocks are drawn where the setting fades them
	const FADE			= .8;

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
		sheet			: 'page',
		adapter		: null,
		ready			: false,
		saving		: false,
		dropped		: null,
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
		origin		: null,
		originNext	: null,
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
		variants		: [],
		variantBusy	: false,
		variantWant	: null,
		variantFocus	: false,
		pickers		: [],
		typeTitles	: {},
		pendingVariants	: [],
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
		dim					: 'frame',
		gl					: null,
		found				: null,
		host				: null,
		hot					: '',
		focus				: '',
		overBar			: '',
		selected		: '',
		paintBars		: null,
		scroll			: null,
		box					: null,
		penBack			: '',
		penFocus		: null,
		penHost			: null,
		fields			: {},
		fieldInfo		: {},
		uses			: {},
		shared			: {},
	};

	/**
	 *	What the view that makes a page holds: whether it is the one on the screen, the bar of its form and the button of the list that opened it
	 */
	const N = {
		open		: false,
		bar			: null,
		button	: null,
		taken		: '',
		choosing	: false,
		source	: '',
	};

	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/builder/'+ key ), ...params );
	};

	// The word of a source that is the page's - or the block's, on the sheet of a block
	const ownWord = function( page, block ) {
		return S.adapter.sources === 'block' ? block : page;
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

		if( S.adapter.readOnly === true )
			return;

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
		redrawBox();

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

		// The focus is put on the copy before the page is drawn: the page is drawn by the change, and the button that had the focus is gone with it
		S.refocus = { at : at + 2, action : 'copy', from : from ?? 'frame' };

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

	// The choice of variants a block of the page has, as the server told it with the page that is drawn: the catalogue block it is, the variant it has - null where it
	// is its own - and the names. None where the account does not design, in a frame, or where the place is no block - the one rule for every place the choice is shown
	const variantOfBlock = function( at ) {

		if( S.level < 2 || S.frame === true || S.top[at] === undefined || kindOf( S.top[at] ) !== 'block' )
			return undefined;

		return S.variants.find( function( entry ) { return entry.at === at } );
	};

	// A control that chooses a variant: it cannot be used while a choice is asked of the server - the block of the draft is that block until the answer comes
	const pickerLock = function( set, keep ) {

		// The one of the lightbox is kept when the bars are drawn again, and is replaced by the next of its kind
		if( keep === true ) {
			set.keep = true;
			S.pickers = S.pickers.filter( function( other ) { return other.keep !== true } );
		}

		set( S.variantBusy );
		S.pickers.push( set );
	};

	// A variant is chosen: the server says what the block is with it - which block it is and which variants it has are its to tell - and the block of the draft is that, in one
	// step to undo. The answer is for the block that was asked about: where the draft has another block at the place - it was moved, taken out or brought back while the
	// answer came - it is dropped and nothing is written. The place of the choice keeps the focus when the page is drawn
	const chooseVariant = function( at, name, from ) {

		if( S.variantBusy === true || name === '' || variantOfBlock( at ) === undefined )
			return;

		const sent = JSON.stringify( S.top[at] );

		S.variantBusy = true;
		S.pickers.forEach( function( set ) { set( true ) } );

		api( 'variant', { tree : S.top, at : String( at ), name : name, texts : S.texts, slots : S.copies, pictures : picturesNow(), inserted : insertedNow() }, function( status, response ) {

			S.variantBusy = false;
			S.pickers.forEach( function( set ) { set( false ) } );

			if( status !== 200 ) {
				S.variantFocus = false;
				S.view.bar.line.error( status, response );
				paint();
				return;
			}

			if( S.top[at] === undefined || JSON.stringify( S.top[at] ) !== sent ) {
				S.variantFocus = false;
				S.view.bar.line.fail( word('msg/variant-stale') );
				return;
			}

			S.variantFocus = from === 'box';
			S.refocus = from === 'box' ? null : { at : at, action : 'variant', from : from ?? 'frame' };
			change( function() { S.top[at] = fixAttrs( [ response.node ] )[0] } );
		} );
	};

	// A strip of steps chooses with every arrow key: what it says is asked of the server once it has rested, so that going past a variant is no request and no step to undo
	const queueVariant = function( at, name, from ) {

		const id = ( S.variantWant === null ? 0 : S.variantWant.id ) + 1;

		S.variantWant = { id : id, at : at, name : name };
		wn.setTimeout( function() {

			if( S.variantWant !== null && S.variantWant.id === id ) {
				S.variantWant = null;
				chooseVariant( at, name, from );
			}
		}, VARIANT_REST );
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

	// The four things done to a block: the key and whether its word is written out beside its icon where there is room for it
	const ACTIONS = [ [ 'up', false ], [ 'down', false ], [ 'copy', false ], [ 'remove', true ] ];

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

	// --- the outline: the blocks of the page as the tree of the workbench, and as the strip it folds to ------------

	// The key a block has in every view that shows it - the outline, its strip, the page in the frame -: its id, and where it has none the place it stands at. The
	// calls of the two frames have a key of their own
	const keyAt = function( at ) {

		const node = S.top[at];

		if( node === undefined )
			return '';

		if( kindOf( node ) === 'block' )
			return idOf( node ) !== '' ? idOf( node ) : '@'+ at;

		if( node.n !== 'template' )
			return '@'+ at;

		// The first call of a frame on the top level is the head, the last the foot, and what stands between is a template of its own: no second head
		const calls = [];

		S.top.forEach( function( other, index ) {
			if( other.n === 'template' )
				calls.push( index );
		} );

		if( calls.length === 1 )
			return /footer/i.test( String( node.s ?? '' ) ) === true ? '@footer' : '@header';

		return at === calls[0] ? '@header' : ( at === calls[calls.length - 1] ? '@footer' : '@'+ at );
	};

	// The index in the top level that a key names, -1 for none
	const atOf = function( key ) {

		for( let at = 0; at < S.top.length; at++ )
			if( keyAt( at ) === key )
				return at;

		return -1;
	};

	// The calls inside a call, at any depth
	const innerCalls = function( node, into ) {

		( node.c ?? [] ).forEach( function( inner ) {

			if( inner.n !== undefined )
				into.push( inner );

			if( Array.isArray( inner.c ) === true )
				innerCalls( inner, into );
		} );

		return into;
	};

	// Whether a call is a loop: a collection or any component the register says repeats its content
	const isLoopCall = function( call ) {
		return call.n.indexOf('collection') === 0 || ( S.schemas[call.n] !== undefined && S.schemas[call.n].loop === true );
	};

	/**
	 *	The icon of a block, from what makes it what it is - the first of these that the block has:
	 *
	 *		a loop or a collection in it			repeat
	 *		a picture (the call or the attribute)	image
	 *		a row of two columns or more			columns-3
	 *		a table									table
	 *		a form									rows-2
	 *		a button									mouse-pointer-click
	 *		free html								code
	 *		a text									text
	 *		a title and nothing else				heading
	 *		nothing of these						square (the icon its component names is the one of the head of the page: no block has it)
	 *
	 *	The call of a frame - the head and the foot - has the icon of the component. Free markup has the one of code
	 */
	const iconOf = function( node ) {

		if( node.n === undefined )
			return 'code';

		if( node.n === 'header-wrap' || node.n === 'footer-wrap' )
			return node.n === 'header-wrap' ? 'panel-top' : 'panel-bottom';

		const inner = innerCalls( node, [] );
		const has = function( test ) { return inner.some( test ) };
		const columns = [ node ].concat( inner ).some( function( call ) {
			return call.n === 'section-row' && ( call.c ?? [] ).filter( function( child ) { return child.n === 'section-col' } ).length >= 2;
		} );

		if( has( isLoopCall ) === true )
			return 'repeat';

		if( has( function( call ) { return call.n === 'image' } ) === true || [ node ].concat( inner ).some( function( call ) { return typeof ( call.a ?? {} ).image === 'string' } ) === true )
			return 'image';

		if( columns === true )
			return 'columns-3';

		if( has( function( call ) { return call.n.indexOf('table') !== -1 } ) === true )
			return 'table';

		if( has( function( call ) { return call.n.indexOf('form') === 0 } ) === true )
			return 'rows-2';

		if( has( function( call ) { return call.n.indexOf('button') === 0 } ) === true )
			return 'mouse-pointer-click';

		if( has( function( call ) { return call.n === 'html' } ) === true )
			return 'code';

		if( has( function( call ) { return call.n === 'text' } ) === true )
			return 'text';

		if( has( function( call ) { return call.n === 'section-title' || call.n === 'section-subtitle' } ) === true )
			return 'heading';

		return 'square';
	};

	// The icons of the four things done to a block, and of the place to put one in and of the extended view
	const ACTION_ICONS = { insert : 'plus', up : 'arrow-up', down : 'arrow-down', copy : 'copy', remove : 'trash-2', advanced : 'sliders-horizontal' };

	// What a row of the outline is: its key, the word it shows - the id of a block, the name of a frame -, the full name for a hint, the icon and its type
	const rowOf = function( item ) {

		const node = item.node;
		const key = keyAt( item.at );

		if( item.kind === 'frame' && ( key === '@header' || key === '@footer' ) )
			return { key : key, label : word( key === '@footer' ? 'word/frame-footer' : 'word/frame-header' ), detail : word('hint/frame-short'), name : '', icon : key === '@footer' ? 'panel-bottom' : 'panel-top', locked : false };

		// A template that is put in the middle of the page has its name and an id of its own
		if( item.kind === 'frame' )
			return { key : key, label : String( node.s ?? '' ).split('/').pop(), detail : '', name : String( node.s ?? '' ), icon : 'file-text', locked : false };

		if( item.kind === 'free' )
			return { key : key, label : word('word/free'), detail : '', name : '', icon : 'code', locked : true };

		return { key : key, label : idOf( node ) !== '' ? idOf( node ) : kindName( node ), detail : '', name : titleOf( node, true ), icon : iconOf( node ), locked : lockedInside( node ) === true };
	};

	// The entries of the outline tree: a row for each block, the calls of the frames as quiet rows over and under them, the locked pieces as one row. What can be done
	// to a row stands at its end - put a block in behind it, up, down, duplicate, delete, the extended view - and is drawn by the tree under the pointer, with the
	// focus in the row and on the row that is chosen
	const outlineItems = function() {

		const rows = items();
		const spots = points();

		return rows.map( function( item, index ) {

			const row = rowOf( item );
			const actions = [];
			const spot = spots.find( function( candidate ) { return candidate.b === index + 1 } );
			const title = item.kind === 'block' ? titleOf( item.node ) : row.label;

			if( spot !== undefined )
				actions.push( { key : 'insert', icon : ACTION_ICONS.insert, name : word( 'label/insert-after', spot.name ) } );

			if( item.kind === 'block' && S.frame === false && free( item.node ) === true )
				[ 'up', 'down', 'copy', 'remove' ].forEach( function( action ) {
					actions.push( { key : action, icon : ACTION_ICONS[action], name : word( 'label/'+ action )+ ': '+ title, disabled : possible( item.at, action ) === false, danger : action === 'remove' } );
				} );

			if( item.kind === 'block' && advancedParts( item.node ) !== null )
				actions.push( { key : 'advanced', icon : ACTION_ICONS.advanced, name : word('label/advanced')+ ': '+ title } );

			const held = item.kind === 'block' && S.frame === false ? variantOfBlock( item.at ) : undefined;
			const named = held === undefined || held.current === null ? undefined : held.items.find( function( entry ) { return entry[0] === held.current } );

			return { id : row.key, label : row.label, detail : named === undefined ? row.detail : localized( named[1] ), icon : row.icon, locked : row.locked, actions : actions };
		} );
	};

	// The full name of a block is the hint of its row: the tree has the id in it, and a long id its own
	const titled = function() {

		items().forEach( function( item ) {

			const row = rowOf( item );
			const entry = S.gl.tree.el.querySelector( 'li[data-id="'+ row.key.replace( /["\\]/g, '\\$&' )+ '"]' );

			if( entry === null || row.name === '' )
				return;

			entry.querySelector('.nino-admin-tree-row').title = row.name;
			entry.querySelector('.nino-admin-tree-label').title = row.name;
		} );
	};

	// The place that is above the first row, where a page has no call of a head: the only place the rows have no button for
	const firstSpot = function() {
		return points().find( function( spot ) { return spot.b === 0 } );
	};

	// A press on a button of a row: the block it is on is chosen, and what the button says is done to it
	const rowAction = function( key, id, opener ) {

		const at = atOf( id );

		if( at === -1 )
			return;

		if( key === 'insert' ) {
			const index = items().findIndex( function( item ) { return keyAt( item.at ) === id } );
			const spot = points().find( function( candidate ) { return candidate.b === index + 1 } );

			if( spot !== undefined )
				openInsert( spot, opener, 'outline' );

			return;
		}

		if( key === 'advanced' ) {
			openExtended( at );
			return;
		}

		selectBlock( id, false );
		act( at, key, opener, 'outline' );
	};

	// Where the page in the frame is rolled to, for the way back from the extended view
	const rememberScroll = function() {

		try {
			S.scroll = S.view.split.frame.contentWindow.scrollY;
		} catch( error ) {
			S.scroll = null;
		}
	};

	// The extended view of a block: the block is the chosen one where the way back leads
	const openExtended = function( at ) {

		const parts = advancedParts( S.top[at] );

		if( parts === null )
			return;

		S.selected = keyAt( at );
		rememberScroll();
		S.adapter.go( parts, { focus : 'back', origin : S.frame === true ? [ addressOf( S.template ) ] : null } );
	};

	// Roll the page in the frame to what a key names: the block, the head or the foot of the page
	const rollTo = function( key ) {

		if( S.view === null || S.view.split.frame === null )
			return;

		let win = null;
		let doc = null;

		try {
			win = S.view.split.frame.contentWindow;
			doc = S.view.split.frame.contentDocument;
		} catch( error ) {
			return;
		}

		if( win === null || win === undefined || doc === null || doc === undefined || doc.body === null )
			return;

		if( key === '@header' )
			win.scrollTo( { top : 0 } );
		else if( key === '@footer' )
			win.scrollTo( { top : doc.documentElement.scrollHeight } );
		else {

			const block = S.found === null ? undefined : S.found.blocks.find( function( candidate ) { return candidate.key === key } );
			const box = block === undefined ? null : boxOf( block.elements, win );

			if( box !== null )
				win.scrollTo( { top : Math.max( 0, box.top - 24 ) } );
		}
	};

	// Choose a block: its row is the chosen one, its bar stays on the page and, where asked, the page rolls to it
	const selectBlock = function( key, roll ) {

		S.selected = key;

		if( S.gl !== null ) {
			S.gl.tree.select( key === '' ? null : key );
			paintRail();
		}

		if( S.paintBars !== null )
			S.paintBars();

		if( roll === true )
			rollTo( key );
	};

	// What the other views are pointing at, as the outline shows it: the rows of the keys and their icons in the strip are marked, nothing is moved
	const lightOutline = function( keys ) {

		const list = keys === null ? [] : ( Array.isArray( keys ) === true ? keys : [ keys ] );

		if( S.gl === null )
			return;

		S.gl.tree.highlight( list.length > 0 ? list : null );
		S.gl.lit = list;
		paintRail();
	};

	// The strip the outline folds to: one button for each row, with the icon of its type; a press chooses the block and rolls the page to it
	const paintRail = function() {

		const gl = S.gl;

		if( gl === null )
			return;

		gl.rail.innerHTML = '';

		items().forEach( function( item ) {

			const row = rowOf( item );
			const name = item.kind === 'block' ? titleOf( item.node ) : row.label;
			const button = Nino.adminUi.iconButton( row.icon, name, function() { selectBlock( row.key, true ) }, { className : 'builder-rail-button' } );

			button.dataset.key = row.key;
			button.setAttribute( 'aria-pressed', String( row.key === S.selected ) );
			button.classList.toggle( 'is-lit', gl.lit.indexOf( row.key ) !== -1 );
			button.classList.toggle( 'is-quiet', item.kind === 'frame' );

			// What the strip is over is told to the other views, as the rows of the tree are: the pointer and the focus both
			button.addEventListener( 'pointerenter', function() { S.view.board.outline.set( row.key ) } );
			button.addEventListener( 'pointerleave', function() { S.view.board.outline.set( null ) } );
			button.addEventListener( 'focus', function() { S.view.board.outline.set( row.key ) } );
			button.addEventListener( 'blur', function() { S.view.board.outline.set( null ) } );
			gl.rail.appendChild( button );
		} );
	};

	// The outline is drawn again after every change: the tree keeps what is chosen and the focus where the ids stay, and puts the focus on the row that is new
	const paintOutline = function() {

		const gl = S.gl;

		if( gl === null )
			return;

		if( S.selected !== '' && atOf( S.selected ) === -1 )
			S.selected = '';

		gl.tree.update( outlineItems() );
		gl.tree.select( S.selected === '' ? null : S.selected );
		titled();
		paintRail();
		gl.first.hidden = firstSpot() === undefined;

		const wanted = S.refocus !== null && S.refocus.from === 'outline' ? S.refocus : null;

		if( wanted === null )
			return;

		S.refocus = null;

		// A move or a removal leaves the focus where the tree keeps it; a copy and the way back from the extended view name the row and the button
		if( wanted.action === 'copy' || wanted.action === 'advanced' )
			gl.tree.focus( keyAt( wanted.at ), wanted.action === 'copy' ? 'copy' : 'advanced' );

		// The strip stands in the place of a column that is folded: its button takes the focus the row cannot have
		if( gl.tree.el.contains( dc.activeElement ) === false && wanted.action === 'advanced' ) {

			const button = gl.rail.querySelector( 'button[data-key="'+ keyAt( wanted.at ).replace( /["\\]/g, '\\$&' )+ '"]' );

			if( button !== null )
				button.focus();
		}
	};

	// --- the frame: bars over the blocks, the texts that are written where they stand ------------------------------

	// The colours and measures of the workbench the page in the frame is drawn with: read from its tokens where the page is marked, so that the bars are the ones of the
	// workbench in the scheme it is in
	const toneOf = function() {

		const style = wn.getComputedStyle( dc.documentElement );
		const read = function( name ) { return style.getPropertyValue( name ).trim() };

		return {
			blue : read('--editor-blue'), soft : read('--editor-blue-soft'), ground : read('--editor-bg-elevated'), ink : read('--editor-text'), line : read('--editor-border'),
			red : read('--editor-red-ink'), redsoft : read('--editor-red-soft'), shadow : read('--editor-shadow-sm'), radius : read('--editor-radius-sm'),
		};
	};

	// What the page in the frame is given: the texts that are written where they stand have a frame of the accent colour, and where the setting fades the other blocks
	// they are drawn at four fifths of their strength - without a transition for a person who has asked for none
	const frameCss = function( tone ) {

		return '[data-nino-text],[data-nino-image]{cursor:text;outline:2px dashed transparent;outline-offset:2px}'
			+ '[data-nino-image]{cursor:pointer}'
			+ '[data-nino-text]:hover,[data-nino-text]:focus,[data-nino-image]:hover,[data-nino-image]:focus{outline-color:'+ tone.blue+ '}'
			+ '[data-nino-text][contenteditable]{outline:2px solid '+ tone.blue+ ';background:'+ tone.soft+ '}'
			+ '[data-nino-placeholder]:not([contenteditable]){opacity:.6;font-style:italic}'
			+ '[data-nino-locked]{cursor:not-allowed}'
			+ '[data-nino-carrier]:focus-visible{outline:2px solid '+ tone.blue+ ';outline-offset:2px}'
			+ '[data-nino-block]{transition:opacity .12s}'
			+ '[data-nino-dim]{opacity:'+ FADE+ '}'
			+ '@media (prefers-reduced-motion:reduce){[data-nino-block]{transition:none}}';
	};

	// The bars stand in the shadow of one element at the end of the page: nothing of the website's style reaches them and they reach nothing of its. A bar, the frame
	// of its block and the places to put a block in are shown only for the block that is on - the pointer or the focus is in it, or it is the chosen one
	const BAR_CSS = ':host{all:initial;position:absolute;top:0;left:0;width:0;height:0;z-index:2147483000}'
		+ '.bar{position:absolute;width:max-content;display:flex;flex-wrap:wrap;gap:2px;align-items:center;box-sizing:border-box;max-width:calc(100vw - 16px);padding:2px;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-ground);color:var(--b-ink);font:600 12px/1.2 system-ui,sans-serif;box-shadow:var(--b-shadow);opacity:0;visibility:hidden;transition:opacity .12s,visibility 0s .12s}'
		+ '.bar[data-on],.bar:focus-within,button.insert[data-on],button.insert:focus-visible{opacity:1;visibility:visible;transition-delay:0s}'
		+ '.name{flex:1 1 auto;min-width:0;padding:0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
		+ 'button{box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;gap:4px;min-width:24px;min-height:24px;margin:0;padding:0 4px;border:1px solid transparent;border-radius:var(--b-radius);background:transparent;color:inherit;font:600 12px/1 system-ui,sans-serif;cursor:pointer}'
		+ 'button:hover{background:var(--b-soft)}button:focus-visible{outline:2px solid var(--b-blue);outline-offset:1px}button:disabled{opacity:.5;cursor:default}'
		+ 'button.danger{color:var(--b-red)}button.danger:hover{background:var(--b-redsoft)}'
		+ 'span.variant{position:relative;display:inline-flex}'
		+ 'button.variant-trigger{max-width:170px;border-color:var(--b-line);background:var(--b-ground)}'
		+ 'button.variant-trigger[aria-expanded=true]{background:var(--b-soft)}'
		+ '.variant-menu{display:none;position:absolute;top:calc(100% + 2px);right:0;z-index:1;flex-direction:column;gap:1px;min-width:100%;width:max-content;max-width:calc(100vw - 32px);padding:2px;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-ground);box-shadow:var(--b-shadow)}'
		+ '.variant-menu[data-open]{display:flex}'
		+ '.variant-menu button{justify-content:flex-start;width:100%;white-space:nowrap}'
		+ '.variant-menu button[aria-checked=true]{background:var(--b-soft);font-weight:600}'
		+ 'svg{flex:none;width:14px;height:14px}'
		+ '.frame{position:absolute;display:none;pointer-events:none;box-sizing:border-box;border:2px solid var(--b-blue);border-radius:3px}'
		+ '.frame[data-on]{display:block}'
		+ 'button.insert{position:absolute;width:max-content;max-width:calc(100vw - 16px);border:1px solid var(--b-line);background:var(--b-ground);color:var(--b-ink);box-shadow:var(--b-shadow);white-space:nowrap;opacity:0;visibility:hidden;transition:opacity .12s,visibility 0s .12s}'
		+ 'button.insert{padding:0 10px;border-style:dashed;border-color:var(--b-blue);border-radius:14px}'
		+ 'button.insert:hover{background:var(--b-soft)}'
		+ '@media (prefers-reduced-motion:reduce){.bar,button.insert{transition:none}}';

	// The pencil stands in a shadow of its own, in the window of the page: it is moved in the page to stand next to the component the keyboard is on, so that the Tab key reaches it
	// next to the component, and that place may lie in a box of the page that is positioned
	const PEN_CSS = ':host{all:initial;position:fixed;top:0;left:0;width:0;height:0;z-index:2147483000}'
		+ 'button.pen{position:fixed;box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;min-width:24px;min-height:24px;margin:0;padding:0;border:1px solid var(--b-line);border-radius:var(--b-radius);background:var(--b-ground);color:var(--b-blue);box-shadow:var(--b-shadow);cursor:pointer;opacity:0;visibility:hidden;transition:opacity .12s,visibility 0s .12s}'
		+ 'button.pen[data-on],button.pen:focus-visible{opacity:1;visibility:visible;transition-delay:0s}'
		+ 'button.pen:hover{background:var(--b-soft)}button.pen:focus-visible{outline:2px solid var(--b-blue);outline-offset:1px}'
		+ 'button.pen.loop{visibility:visible;pointer-events:none}button.pen.loop:focus-visible{opacity:1;pointer-events:auto}'
		+ 'svg{flex:none;width:14px;height:14px}'
		+ '@media (prefers-reduced-motion:reduce){button.pen{transition:none}}';

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

					found.all.push( { path : where, elements : elements, holder : closed.start.parentNode } );

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
	 *	Tab and Escape end it (Enter in a text of lines or blocks is a line) and what was typed stays - the step of Undo takes it back
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

			// Escape leaves the text as a click beside it does: what was typed stays, and the step of Undo takes it back
			if( event.key === 'Escape' ) {
				event.preventDefault();
				endEdit( true );
				target.focus();
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

	// Which block is on: the one the pointer is in, else the one the focus is in - a text, a picture, a bar -, and where neither is in a block the chosen one stays on.
	// `current` is what the pointer or the focus is in: the block the other blocks are measured against
	const activeOf = function() {

		const current = S.hot !== '' ? S.hot : S.focus;

		return { current : current, active : current !== '' ? current : S.selected };
	};

	/**
	 *	What a block shows, by the setting "dim": its bar and the places for a block beside it are drawn for the block that is on; the setting "frame" (the default) draws a
	 *	frame of the accent colour round that block and leaves the others as they are; the setting "fade" leaves it without a frame and draws the other blocks at four fifths
	 *	of their strength while the pointer or the focus is in one
	 *
	 *	@param		{string}		mode					'frame' or 'fade'
	 *	@param		{string}		key
	 *	@param		{Object}		now						activeOf()
	 *
	 *	@return		{Object}									{ bar, frame, dim }
	 */
	const lookOf = function( mode, key, now ) {
		return { bar : key === now.active, frame : key === now.active && mode !== 'fade', dim : mode === 'fade' && now.current !== '' && key !== now.current };
	};

	/**
	 *	What the account may open at a component of the page, and how it is named: its properties in the lightbox - the one form of them in the Builder. The pencil on a
	 *	component is drawn from this and opens what open() does; an account that may only write the content is given a pencil where the lightbox has the source to read or
	 *	a content to write, and none where it has nothing to show it
	 *
	 *	@param		{string}		path					The position of the component in the tree of the page, 'a.b.c'
	 *	@param		{Object}		node					The call
	 *
	 *	@return		{Object|null}							{ name, source, open( opener ) } or null where there is nothing to open
	 */
	const penOf = function( path, node ) {

		if( node.n === undefined || S.schemas[node.n] === undefined || /^\d+\.\d+/.test( path ) === false )
			return null;

		const parts = path.split('.');
		const at = Number( parts[0] );
		const block = S.top[at];

		if( block === undefined || kindOf( block ) !== 'block' || idOf( block ) === '' )
			return null;

		const rel = parts.slice( 1 ).join('.');
		const had = S.box;

		S.box = { block : idOf( block ) };

		const has = hasProperties( rel );

		S.box = had;

		if( has === false )
			return null;

		const schema = S.schemas[node.n];
		const own = Nino.content.getText( '/_admin/builder/kind/'+ node.n );

		return {
			name	: word( 'label/edit-component', own !== '' ? own : localized( schema.label ) ),
			source	: schema.source !== 'none' || schema.loop === true,
			open	: function( opener ) { openProperties( at, rel, opener, function() { penBack( path ) } ) },
		};
	};

	// The pencil gets the focus back where the page is drawn again after the lightbox: the page in the frame is a new one by then, or about to be
	const penBack = function( path ) {

		S.penBack = path;

		if( S.stale === false && S.penFocus !== null ) {
			S.penBack = '';
			S.penFocus( path );
		}
	};

	// The component the pencil is for when the pointer comes over something: what the pointer is over - and the pencil is no part of the page: the pointer on it, or on the
	// way to it inside the component, leaves the component as it is
	const pointerAim = function( before, overOwn, pick ) {
		return overOwn === true ? before : pick();
	};

	// Where the pencil stands for the box of a component, in the coordinates of the window - the box and the window in those of the page: inside, in the top corner on the right,
	// where no word of the component is under it; else flush against the box, at its right edge, above its right corner, or at its left edge. A loop has no box of its own, and its
	// pencil touches the top of the repetitions. `texts` are the boxes of the words in the component, `side` says where it stands
	const penSpot = function( box, size, view, loop, texts ) {

		const right = box.left + box.width;
		const bottom = box.top + box.height;
		const first = Math.min( Math.max( box.top + 2, view.top + 2 ), Math.max( box.top, bottom - size ) );
		const inside = { side : 'inside', top : first, left : Math.max( box.left, Math.min( right, view.left + view.width ) - size - 2 ) };
		const sides = [
			{ side : 'right', top : Math.min( Math.max( box.top, view.top + 2 ), Math.max( box.top, bottom - size ) ), left : right },
			{ side : 'above', top : box.top - size, left : inside.left },
			{ side : 'left', top : Math.min( Math.max( box.top, view.top + 2 ), Math.max( box.top, bottom - size ) ), left : box.left - size },
		];
		const covers = function( spot ) {

			// Inside a box the pencil keeps two pixels from a word; flush against it, it only must not touch one
			const margin = spot.side === 'inside' ? 2 : 0;

			return ( texts ?? [] ).some( function( text ) { return spot.left < text.left + text.width + margin && spot.left + size > text.left - margin && spot.top < text.top + text.height + margin && spot.top + size > text.top - margin } );
		};
		const fits = function( spot ) { return spot.left >= view.left && spot.left + size <= view.left + view.width && spot.top >= view.top };
		const found = ( loop === true ? [ sides[1] ] : [ inside ].concat( sides ) ).find( function( spot ) { return fits( spot ) === true && covers( spot ) === false } ) ?? inside;

		return { side : found.side, top : found.top - view.top, left : found.left - view.left };
	};

	// The place round a pencil that is not inside its component, which still counts for the component: the strip along the edge it stands at, from the top of the box to its
	// foot, and the pencil itself - so that the way from the component to the pencil is never lost. In the coordinates of the window
	const penZone = function( spot, box, size, view ) {

		const top = box.top - view.top;
		const bottom = top + box.height;

		if( spot.side === 'right' )
			return { left : spot.left, right : spot.left + size + 6, top : Math.min( top, spot.top ), bottom : Math.max( bottom, spot.top + size ) };

		if( spot.side === 'left' )
			return { left : spot.left - 6, right : spot.left + size, top : Math.min( top, spot.top ), bottom : Math.max( bottom, spot.top + size ) };

		if( spot.side === 'above' )
			return { left : spot.left, right : spot.left + size, top : spot.top - 6, bottom : top };

		return null;
	};

	const inZone = function( zone, x, y ) {
		return zone !== null && x >= zone.left && x <= zone.right && y >= zone.top && y <= zone.bottom;
	};

	// The element of a component that the Tab key stops at: the text that is written where it stands, the picture that is exchanged, else its first element - which is given
	// a stop of its own where it has none. A loop has no stop: it is reached by its row in the tree
	const carrierOf = function( found, entry ) {

		if( entry.loop === true || entry.elements.length === 0 )
			return null;

		const text = found.texts.find( function( candidate ) { return candidate.path === entry.path && candidate.element.hasAttribute('data-nino-text') === true } );

		if( text !== undefined )
			return text.element;

		const image = entry.elements.find( function( element ) { return element.hasAttribute('data-nino-image') === true } );

		return image ?? entry.elements[0];
	};

	// The element the one pencil stands behind for the Tab key, null where it stands inside the carrier: after a text or a picture, which hold nothing else - and after the link or
	// the button that holds the carrier, as a pencil in a link would be a part of it, taken by its click and read with it
	const seatAfter = function( carrier ) {

		const holding = carrier.closest === undefined ? null : carrier.closest('a, button');

		if( holding !== null )
			return holding;

		return carrier.hasAttribute('data-nino-text') === true || carrier.hasAttribute('data-nino-image') === true ? carrier : null;
	};

	// Where the one pencil stands in the page: after the element of seatAfter, else as the first thing in a component that holds others - so that the components inside come after it
	const seatOf = function( carrier ) {
		return seatAfter( carrier ) !== null ? 'after' : 'inside';
	};

	// Whether the carrier is a stop of the Tab key already: a text that is written and a picture that is exchanged are
	const isStop = function( carrier ) {
		return carrier.hasAttribute('data-nino-text') === true || carrier.hasAttribute('data-nino-image') === true;
	};

	// The elements that are given a stop of the Tab key: of every component that has a pencil, the one it stands next to - those that are stops already (a text, a picture) are none
	const stopsOf = function( found, entries, hasPen ) {

		const out = [];

		entries.forEach( function( entry ) {

			const carrier = carrierOf( found, entry );

			if( carrier !== null && hasPen( entry ) === true && isStop( carrier ) === false && out.indexOf( carrier ) === -1 )
				out.push( carrier );
		} );

		return out;
	};

	// The loops that have a stop of the Tab key of their own, with a pencil: a loop that has repetitions has the pencil in front of the first, one that has none - an empty
	// source - at the end of the element that holds it
	const loopStopsOf = function( entries, hasPen ) {
		return entries.filter( function( entry ) { return entry.loop === true && ( entry.elements.length > 0 || entry.holder !== undefined && entry.holder !== null ) && hasPen( entry ) === true } );
	};

	// The elements a loop is drawn at: its repetitions, or the element that holds it where it shows none
	const shownOf = function( entry ) {
		return entry.elements.length > 0 ? entry.elements : ( entry.holder === undefined || entry.holder === null ? [] : [ entry.holder ] );
	};

	// The Tab key stops once in a block: at the one component of it that was on last, and the arrows go from one component of the block to the next. The member that an arrow
	// key at a member of a list of members of a block leads to, -1 where it leads nowhere
	const roveTo = function( count, at, key ) {

		if( count === 0 )
			return -1;

		const to = { ArrowRight : at + 1, ArrowDown : at + 1, ArrowLeft : at - 1, ArrowUp : at - 1, Home : 0, End : count - 1 }[key];

		return to === undefined || to < 0 || to >= count ? -1 : to;
	};

	/**
	 *	Hang a bar on each block of the frame: its name and the things that can be done to it, in an element that has a shadow of its own. The bar, the frame of the block
	 *	and the places to put a block in next to it are drawn for the block that is on, and the pencil for the component under the pointer
	 *
	 */
	const bars = function( frame, blocks, behind ) {

		const doc = frame.contentDocument;
		const win = frame.contentWindow;
		const spots = points();
		const tone = toneOf();
		const host = doc.createElement('nino-builder-bars');
		const shadow = host.attachShadow( { mode : 'open' } );
		const style = doc.createElement('style');
		const placed = [];
		const pics = [];
		const fade = S.dim === 'fade';

		// On a page that is laid out as wide as a tablet the words of the bar are written out, on a phone they are the icons, with their names
		const wide = win.innerWidth >= 768;

		style.textContent = BAR_CSS;
		shadow.appendChild( style );
		S.pickers = S.pickers.filter( function( set ) { return set.keep === true } );
		Object.keys( tone ).forEach( function( name ) { host.style.setProperty( '--b-'+ name, tone[name] ) } );
		host.setAttribute( 'role', 'region' );
		host.setAttribute( 'aria-label', word('label/bars') );
		S.host = host;

		const glyph = function( name ) {
			return Nino.adminUi.icon( name, { size : 14 } );
		};

		// A button of the bar: an icon, and where there is room the word of the thing it does
		const tool = function( icon, text, name, className ) {

			const button = doc.createElement('button');

			button.type = 'button';
			button.className = className ?? '';
			button.title = name;
			button.setAttribute( 'aria-label', name );
			button.appendChild( glyph( icon ) );

			if( text !== '' )
				button.appendChild( doc.createTextNode( text ) );

			return button;
		};

		// The button that puts a block in at a place: the words are written out where there is room for them and where a phone has to have them
		const pill = function( text, name, press, icon ) {

			const button = tool( icon ?? ACTION_ICONS.insert, text, name, 'insert' );

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
			bar.dataset.key = block.key;
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

					const name = word( 'label/'+ action[0] )+ ': '+ title;
					const button = tool( ACTION_ICONS[action[0]], wide === true && action[1] === true ? word( 'label/'+ action[0]+ '-short' ) : '', name, action[0] === 'remove' ? 'danger' : '' );

					button.dataset.action = action[0];
					button.dataset.at = String( block.at );
					button.disabled = possible( block.at, action[0] ) === false;

					// A click and a key press both end here; the dialog of a removal is the panel's, so it is asked from outside the frame
					button.addEventListener( 'click', function() { selectBlock( block.key, false ); act( block.at, action[0], S.view.split.frame, 'frame' ) } );
					bar.appendChild( button );
				} );
			else if( S.frame === false ) {

				const lock = doc.createElement('span');

				lock.className = 'name';
				lock.textContent = word('word/locked');
				bar.appendChild( lock );
			}

			const held = variantOfBlock( block.at );

			// The choice of a variant: a button with the name of the one that holds and a menu of the others - a choice is made when it is confirmed, never by an arrow key
			if( held !== undefined ) {

				const box = doc.createElement('span');
				const trigger = doc.createElement('button');
				const menu = doc.createElement('div');
				const here = held.items.find( function( item ) { return item[0] === held.current } );
				const shown = here === undefined ? word('variant/own') : localized( here[1] );
				const entries = [];

				box.className = 'variant';
				trigger.type = 'button';
				trigger.className = 'variant-trigger';
				trigger.dataset.action = 'variant';
				trigger.dataset.at = String( block.at );
				trigger.title = word('label/variant')+ ': '+ shown;
				trigger.setAttribute( 'aria-label', word('label/variant')+ ': '+ title+ ': '+ shown );
				trigger.setAttribute( 'aria-haspopup', 'menu' );
				trigger.setAttribute( 'aria-expanded', 'false' );
				trigger.appendChild( doc.createTextNode( shown ) );
				trigger.appendChild( glyph( 'chevron-down' ) );
				menu.className = 'variant-menu';
				menu.setAttribute( 'role', 'menu' );
				menu.setAttribute( 'aria-label', word('label/variant')+ ': '+ title );

				const close = function( back ) {

					menu.removeAttribute('data-open');
					trigger.setAttribute( 'aria-expanded', 'false' );

					if( back === true )
						trigger.focus();
				};
				const open = function() {

					menu.setAttribute( 'data-open', '' );
					trigger.setAttribute( 'aria-expanded', 'true' );
					( entries.find( function( entry ) { return entry.getAttribute('aria-checked') === 'true' } ) ?? entries[0] ).focus();
				};

				held.items.forEach( function( item, at ) {

					const entry = doc.createElement('button');

					entry.type = 'button';
					entry.setAttribute( 'role', 'menuitemradio' );
					entry.setAttribute( 'aria-checked', item[0] === held.current ? 'true' : 'false' );
					entry.dataset.variant = item[0];
					entry.textContent = localized( item[1] );
					entry.tabIndex = -1;
					entry.addEventListener( 'click', function() {

						close( true );

						if( item[0] !== held.current ) {
							selectBlock( block.key, false );
							chooseVariant( block.at, item[0] );
						}
					} );
					entry.addEventListener( 'keydown', function( event ) {

						const to = { ArrowDown : at + 1, ArrowUp : at - 1, Home : 0, End : entries.length - 1 }[event.key];

						if( event.key === 'Escape' || event.key === 'Tab' ) {
							event.preventDefault();
							event.stopPropagation();
							close( event.key === 'Escape' );
						}
						else if( to !== undefined ) {
							event.preventDefault();
							event.stopPropagation();
							entries[( to + entries.length ) % entries.length].focus();
						}
					} );
					entries.push( entry );
					menu.appendChild( entry );
				} );

				trigger.addEventListener( 'click', function() { menu.hasAttribute('data-open') === true ? close( false ) : open() } );
				trigger.addEventListener( 'keydown', function( event ) {

					if( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
						event.preventDefault();
						open();
					}
				} );
				box.addEventListener( 'focusout', function( event ) {

					if( event.relatedTarget === null || box.contains( event.relatedTarget ) === false )
						close( false );
				} );
				pickerLock( function( off ) { trigger.disabled = off } );
				box.appendChild( trigger );
				box.appendChild( menu );
				bar.appendChild( box );
			}

			const parts = advancedParts( node );

			if( parts !== null ) {

				const name = word('label/advanced')+ ': '+ title;
				const more = tool( ACTION_ICONS.advanced, wide === true ? word('label/advanced') : '', name, '' );

				more.dataset.action = 'advanced';
				more.dataset.at = String( block.at );
				more.addEventListener( 'click', function() { openExtended( block.at ) } );
				bar.appendChild( more );
			}

			const first = spots.find( function( spot ) { return spot.block === block.at && spot.carry === 'before' } );
			const last = spots.find( function( spot ) { return spot.block === block.at && spot.carry === 'after' } );
			const pills = { before : first === undefined ? null : pillOf( first ), after : last === undefined ? null : pillOf( last ) };

			[ frameBox, pills.before, bar, pills.after ].forEach( function( piece ) {

				if( piece === null )
					return;

				piece.dataset.key = block.key;
				shadow.appendChild( piece );
			} );

			// Where the setting fades the other blocks they change their strength with a transition
			if( fade === true )
				block.elements.forEach( function( element ) { element.setAttribute( 'data-nino-block', '' ) } );

			placed.push( { block : block, bar : bar, box : frameBox, pills : pills } );
		} );

		// A page of free markup only has its places in the frame too: the first under the header of the site, the others above its footer. They are always there, as no
		// block is on
		const loose = spots.filter( function( spot ) { return spot.block === null } ).map( function( spot ) {

			const button = pillOf( spot );

			button.setAttribute( 'data-on', '' );
			shadow.appendChild( button );

			return button;
		} );

		// The picture a call reads as an attribute: a button in the foot corner of the call
		behind.forEach( function( item ) {

			const button = pill( word('picture/replace-title'), word( 'picture/replace-of', item.target.label ), function( opener ) {

				if( S.stale === false && S.editing === null )
					openImage( item.target, opener );
			}, 'image' );

			button.dataset.image = item.target.key;
			button.setAttribute( 'data-on', '' );
			shadow.appendChild( button );
			pics.push( { button : button, element : item.element } );
		} );

		// The pencil: one button, put at the corner of the component the pointer or the focus is on
		const loopPens = [];
		const pen = tool( 'pencil', '', word('hint/write'), 'pen' );
		const penHost = doc.createElement('nino-builder-pen');
		const penShadow = penHost.attachShadow( { mode : 'open' } );
		const penStyle = doc.createElement('style');
		let aimed = null;

		penStyle.textContent = PEN_CSS;
		penShadow.appendChild( penStyle );
		Object.keys( tone ).forEach( function( name ) { penHost.style.setProperty( '--b-'+ name, tone[name] ) } );
		penHost.setAttribute( 'aria-hidden', 'false' );
		S.penHost = penHost;
		pen.addEventListener( 'click', function() {

			if( aimed !== null )
				aimed.pen.open( pen );
		} );
		penShadow.appendChild( pen );

		// A thing of the page that is in one of the two shadows: the bars or the pencil
		const ownOf = function( node ) { return shadow.contains( node ) === true || penShadow.contains( node ) === true || loopPens.some( function( held ) { return held.shadow.contains( node ) === true } ) };

		// The page is laid out at a width and scaled into the pane where the person chose a width the pane has not: a bar is scaled back, so that it is as large
		// as the workbench's own controls
		const scaleBack = function() {

			const scale = win.innerWidth > 0 ? frame.getBoundingClientRect().width / win.innerWidth : 1;

			return scale > 0 && scale < 1 ? 1 / scale : 1;
		};

		// What each block shows: its bar, its frame and its strength, by what is on and the setting
		const paintBars = function() {

			const now = activeOf();

			placed.forEach( function( item ) {

				const look = lookOf( S.dim, item.block.key, now );

				item.bar.toggleAttribute( 'data-on', look.bar );
				item.box.toggleAttribute( 'data-on', look.frame );

				[ item.pills.before, item.pills.after ].forEach( function( pill ) {
					if( pill !== null )
						pill.toggleAttribute( 'data-on', look.bar );
				} );

				item.block.elements.forEach( function( element ) {
					element.toggleAttribute( 'data-nino-dim', look.dim );
				} );
			} );
		};

		// The boxes of the words that stand in some elements, in the coordinates of the page: what the pencil does not stand on
		const wordsOf = function( elements, win ) {

			const out = [];

			elements.forEach( function( element ) {

				const walker = doc.createTreeWalker( element, 4 );
				let node = walker.nextNode();

				while( node !== null && out.length < 400 ) {

					if( node.data.trim() !== '' ) {

						const range = doc.createRange();

						range.selectNodeContents( node );
						Array.from( range.getClientRects() ).forEach( function( rect ) {
							if( rect.width > 0 && rect.height > 0 )
								out.push( { top : rect.top + win.scrollY, left : rect.left + win.scrollX, width : rect.width, height : rect.height } );
						} );
					}

					node = walker.nextNode();
				}
			} );

			return out;
		};

		// The component the pencil was put next to in the page last: where the component is another one, it is put next to that - never inside a link or a button, which it
		// would be taken by
		let anchored = '';

		const placePen = function() {

			const box = aimed === null || S.editing !== null ? null : boxOf( aimed.entry.elements, win );

			if( box === null ) {
				pen.removeAttribute('data-on');
				return;
			}

			if( anchored !== aimed.entry.path && penShadow.activeElement !== pen ) {
				anchored = aimed.entry.path;
				anchorPen();
			}

			const back = scaleBack();
			const size = 26 * back;
			const view = { top : win.scrollY, left : win.scrollX, width : win.innerWidth };

			pen.title = aimed.pen.name;
			pen.setAttribute( 'aria-label', aimed.pen.name );
			pen.dataset.key = aimed.key;
			pen.style.transformOrigin = '0 0';
			pen.style.transform = back === 1 ? '' : 'scale('+ back+ ')';

			// Inside the box of the component where no word is under it, else flush against the box; the zone round it counts for the component
			const spot = penSpot( box, size, view, aimed.entry.loop === true, wordsOf( aimed.entry.elements, win ) );

			aimed.zone = penZone( spot, box, size, view );

			pen.style.top = spot.top+ 'px';
			pen.style.left = spot.left+ 'px';
			pen.setAttribute( 'data-on', '' );
		};

		// What the focus is on, told to the other views - where the view is still the one the page was drawn for
		const tell = function( key ) {

			if( S.view !== null && S.view.board !== null )
				S.view.board.page.set( key );
		};

		// What the pencil can be at: every call of the page that has elements, and for each loop - which has none of its own - the elements of its repetitions
		const aims = function() {

			const list = S.found.all.filter( function( entry ) { return entry.elements.length > 0 } );
			const loops = {};
			const empty = [];

			list.forEach( function( entry ) {

				const parts = entry.path.split('.');

				for( let depth = 2; depth < parts.length; depth++ ) {

					const path = parts.slice( 0, depth ).join('.');
					const call = nodeAt( path );

					if( call !== null && call.n !== undefined && isLoopCall( call ) === true )
						loops[path] = ( loops[path] ?? [] ).concat( entry.elements );
				}
			} );

			// A loop that shows nothing has none to be seen from: the element that holds it is where its pencil stands
			S.found.all.forEach( function( entry ) {

				const call = entry.elements.length === 0 && entry.path.split('.').length > 1 && entry.holder !== null && entry.holder !== undefined && entry.holder !== doc.body ? nodeAt( entry.path ) : null;

				if( call !== null && call.n !== undefined && isLoopCall( call ) === true && loops[entry.path] === undefined )
					empty.push( { path : entry.path, elements : [], holder : entry.holder, loop : true } );
			} );

			return list.map( function( entry ) { return { path : entry.path, elements : entry.elements, loop : false } } )
				.concat( Object.keys( loops ).map( function( path ) { return { path : path, elements : loops[path], loop : true } } ) )
				.concat( empty );
		};

		// The component the pencil is for: the innermost one that holds the element and has something to open for the account - a card of a loop and the field in it have theirs,
		// and the loop its own at the edge of its repetitions. The pencil is no part of the page: the pointer on it does not change the component
		const pickAim = function( target ) {

			let found = null;

			if( S.stale === false && S.editing === null && target !== null )
				aims().filter( function( entry ) {
					return entry.elements.some( function( element ) { return element.contains( target ) } ) === true;
				} ).sort( function( one, other ) { return other.path.length - one.path.length || ( one.loop === true ? 1 : 0 ) - ( other.loop === true ? 1 : 0 ) } ).some( function( entry ) {

					const node = nodeAt( entry.path );
					const open = node === null ? null : penOf( entry.path, node );

					if( open === null )
						return false;

					found = { entry : entry, pen : open, key : keyAt( Number( entry.path.split('.')[0] ) ) };

					return true;
				} );

			return found;
		};

		const aim = function( target ) {

			held = false;
			aimed = pickAim( target );
			placePen();
		};

		// What the pointer is over - and, where it is in the zone round the pencil of the component it was on, still that component: the pencil stands outside of its box, where it
		// would cover a word, and the way to it must not give it up
		let held = false;
		const pickPointed = function( event ) {

			if( aimed !== null && aimed.zone !== undefined && inZone( aimed.zone, event.clientX, event.clientY ) === true ) {
				held = true;
				return aimed;
			}

			held = false;

			return pickAim( event.target );
		};

		// The pencil stands in the page next to its component for the Tab key: after a text or a picture, which hold nothing else, and as the first thing in a component that
		// holds others - so that the one pencil is the next stop after the component, and the components inside come after it
		const anchorPen = function() {

			if( aimed === null )
				return;

			const carrier = carrierOf( S.found, aimed.entry );

			anchored = aimed.entry.path;

			// A loop has no element of its own: the pencil rests at the end of the page, where no link holds it
			if( carrier === null ) {

				if( penHost.parentNode !== doc.body )
					doc.body.appendChild( penHost );

				return;
			}

			const behind = seatAfter( carrier );

			if( behind !== null )
				behind.after( penHost );
			else
				carrier.insertBefore( penHost, carrier.firstChild );
		};

		// The pencil of a component, with the focus on it: where the lightbox of the component was closed
		S.penFocus = function( path ) {

			const entry = aims().find( function( candidate ) { return candidate.path === path } );
			const node = entry === undefined ? null : nodeAt( entry.path );
			const open = node === null ? null : penOf( entry.path, node );

			if( open === null )
				return;

			const own = loopPens.find( function( held ) { return held.entry.path === path } );

			if( own !== undefined ) {
				own.button.focus();
				return;
			}

			aimed = { entry : entry, pen : open, key : keyAt( Number( entry.path.split('.')[0] ) ) };
			anchorPen();
			placePen();
			pen.focus();
		};

		// The page is laid out at a width and scaled into the pane where the person chose a width the pane has not: a bar is scaled back, so that it is as large
		// as the workbench's own controls. It stands in the top corner on the right, inside the block, where a block has the room; one that is low - a header - has it
		// below its edge, where it covers the top of the next, and never the logo or the menu
		const place = function() {

			const back = scaleBack();

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

				// A block that is higher than the screen keeps its bar at the top of what is in sight, and never below its own foot
				const seen = Math.min( Math.max( box.top + 8 * back, win.scrollY + 8 * back ), box.top + box.height - height - 2 * back );

				item.bar.style.top = ( low === true ? box.top + box.height + 2 * back : seen )+ 'px';
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
					const high = pill.offsetHeight * back;
					const upper = Math.min( Math.max( box.top + 4 * back, win.scrollY + 4 * back ), box.top + box.height - high - 4 * back );
					const lower = Math.max( box.top + 4 * back, Math.min( box.top + box.height - high - 4 * back, win.scrollY + win.innerHeight - high - 4 * back ) );

					pill.style.top = ( which === 0 ? upper : ( low === true ? box.top + box.height + 2 * back : lower ) )+ 'px';
				} );
			} );

			loopPens.forEach( function( held ) {

				const box = boxOf( shownOf( held.entry ), win );

				if( box === null )
					return;

				const size = 26 * back;
				const spot = penSpot( box, size, { top : win.scrollY, left : win.scrollX, width : win.innerWidth }, true, [] );

				held.button.style.transformOrigin = '0 0';
				held.button.style.transform = back === 1 ? '' : 'scale('+ back+ ')';
				held.button.style.top = spot.top+ 'px';
				held.button.style.left = spot.left+ 'px';
			} );

			placePen();
		};

		// What stands over the page is told which block the pointer is on while it is on a bar, a place or the pencil - they stand in the shadow, where the page
		// takes the pointer for the element that holds it
		S.overBar = '';
		shadow.addEventListener( 'pointerover', function( event ) {

			const target = event.target;
			const own = target.closest === undefined ? null : target.closest('[data-key]');

			S.overBar = own === null ? '' : own.dataset.key;
		} );

		doc.addEventListener( 'pointerover', function( event ) {

			const origin = typeof event.composedPath === 'function' ? event.composedPath()[0] : event.target;

			if( ownOf( origin ) === false )
				S.overBar = '';

			aimed = pointerAim( aimed, ownOf( origin ), function() { return pickPointed( event ) } );
			placePen();
		} );
		doc.documentElement.addEventListener( 'pointerleave', function( event ) {

			// A finger that is lifted leaves the page too: the pencil it has tapped stays until another place is tapped
			if( event.pointerType !== undefined && event.pointerType !== 'mouse' )
				return;

			S.overBar = '';

			if( aimed !== null && penShadow.activeElement !== pen ) {
				aimed = null;
				placePen();
			}
		} );

		// The keyboard is on the block the focus is in - a text, a picture, or a bar - and the pencil is at the component of the text
		doc.addEventListener( 'focusin', function( event ) {

			const origin = typeof event.composedPath === 'function' ? event.composedPath()[0] : event.target;
			const inside = ownOf( origin ) === true;
			const own = inside === true && origin.closest !== undefined ? origin.closest('[data-key]') : null;

			S.focus = own !== null ? own.dataset.key : ( keyInPage( event.target ) ?? '' );

			// What moved last counts: the focus holds until the pointer moves again
			S.hot = '';
			paintBars();
			tell( S.focus === '' ? null : S.focus );

			if( inside === false ) {
				aim( event.target );
				anchorPen();
			}
		} );
		doc.addEventListener( 'pointermove', function( event ) {

			const origin = typeof event.composedPath === 'function' ? event.composedPath()[0] : event.target;
			const key = ownOf( origin ) === true ? S.overBar : ( keyInPage( event.target ) ?? '' );

			if( key !== S.hot ) {
				S.hot = key;
				paintBars();
			}

			// The pointer has left the zone round the pencil inside one element of the page: what it is over decides again
			if( held === true && ownOf( origin ) === false && aimed !== null && inZone( aimed.zone ?? null, event.clientX, event.clientY ) === false ) {
				aimed = pickPointed( event );
				placePen();
			}
		} );
		doc.addEventListener( 'focusout', function( event ) {

			S.focus = '';
			paintBars();
			tell( null );

			if( event.relatedTarget === null || ( event.relatedTarget !== host && event.relatedTarget !== penHost && loopPens.some( function( held ) { return held.host === event.relatedTarget } ) === false && ownOf( event.relatedTarget ) === false ) )
				aim( null );
		} );

		penShadow.addEventListener( 'pointerover', function() { S.overBar = pen.dataset.key ?? '' } );
		doc.body.appendChild( host );
		doc.body.appendChild( penHost );

		// The Tab key stops once in a block, at the component that was on last; the arrow keys go from one component of the block to the next, Enter and Space open what the
		// component has - the lightbox, or the cursor in a text that is written - and the pencil stands for the mouse and the finger, and after the component for Tab once the
		// arrows were used. Where the focus came by Tab the pencil is no stop: the next Tab goes on to the next block
		const roving = function( page, seat, pencil, loops ) {

			const members = [];
			const current = {};
			let arrowed = false;

			aims().filter( function( entry ) { return entry.loop === false } ).forEach( function( entry ) {

				const node = nodeAt( entry.path );
				const open = node === null ? null : penOf( entry.path, node );
				const carrier = carrierOf( S.found, entry );

				if( carrier === null || ( open === null && isStop( carrier ) === false ) || members.some( function( member ) { return member.element === carrier } ) === true )
					return;

				members.push( {
					at : Number( entry.path.split('.')[0] ), element : carrier, order : carrier,
					owns : function( target ) { return target === carrier },
					focus : function() { carrier.focus() },
					mark : function( on ) { carrier.tabIndex = on === true ? 0 : -1 },
					open : open,
				} );
			} );

			loops.forEach( function( held ) {
				members.push( {
					at : Number( held.entry.path.split('.')[0] ), element : held.button, order : held.host,
					owns : function( target ) { return target === held.host },
					focus : function() { held.button.focus() },
					mark : function( on ) { held.button.tabIndex = on === true ? 0 : -1 },
					open : null,
				} );
			} );

			members.sort( function( one, other ) { return ( one.order.compareDocumentPosition( other.order ) & 4 ) !== 0 ? -1 : 1 } );

			const groups = {};

			members.forEach( function( member ) {

				( groups[member.at] = groups[member.at] ?? [] ).push( member );
				member.mark( false );
			} );

			Object.keys( groups ).forEach( function( at ) {
				current[at] = groups[at][0];
				groups[at][0].mark( true );
			} );

			// What a screen reader is told of a stop: it is in a group named for its block, and one sentence - the same for every stop - says what the keys do
			const keys = doc.createElement('span');

			keys.id = 'nino-builder-keys';
			keys.hidden = true;
			keys.textContent = word('hint/keys');
			doc.body.appendChild( keys );

			S.found.blocks.forEach( function( block ) {

				const node = S.top[block.at];

				if( node === undefined || kindOf( node ) !== 'block' )
					return;

				block.elements.forEach( function( element ) {

					if( element.hasAttribute('role') === false ) {
						element.setAttribute( 'role', 'group' );
						element.setAttribute( 'aria-label', word( 'label/block-group', titleOf( node ) ) );
					}
				} );
			} );

			members.forEach( function( member ) {

				if( member.element.nodeType === 1 && member.owns( member.element ) === true && member.element.tagName !== 'BUTTON' )
					member.element.setAttribute( 'aria-describedby', keys.id );
			} );

			const memberOf = function( target ) { return members.find( function( member ) { return member.owns( target ) } ) };

			page.addEventListener( 'focusin', function( event ) {

				const member = memberOf( event.target );

				if( member !== undefined && current[member.at] !== member ) {
					current[member.at].mark( false );
					current[member.at] = member;
					member.mark( true );
				}

				// The pencil of a component is a stop after it where the arrows brought the focus there
				if( member !== undefined )
					pencil.tabIndex = arrowed === true ? 0 : -1;

				arrowed = false;
			} );

			page.addEventListener( 'keydown', function( event ) {

				const member = memberOf( event.target );

				if( member === undefined || S.editing !== null || S.stale === true || event.defaultPrevented === true || event.altKey === true || event.ctrlKey === true || event.metaKey === true )
					return;

				if( event.key === 'Enter' || event.key === ' ' ) {

					if( member.open === null && member.element.hasAttribute('data-nino-text') === false )
						return;

					if( member.element.hasAttribute('data-nino-image') === true || ( event.key === 'Enter' && member.element.hasAttribute('data-nino-text') === true ) )
						return;

					event.preventDefault();

					const text = S.found.texts.find( function( candidate ) { return candidate.element === member.element } );
					const entry = text === undefined ? null : entryOf( text );

					if( member.element.hasAttribute('data-nino-text') === true && entry !== null )
						beginEdit( text, entry );
					else if( member.open !== null )
						member.open.open( member.element );

					return;
				}

				const group = groups[member.at];
				const to = roveTo( group.length, group.indexOf( member ), event.key );

				if( to === -1 )
					return;

				event.preventDefault();
				arrowed = true;
				group[to].focus();
			} );
		};

		// Every component that has a pencil is a stop of the Tab key, in the order of the page
		stopsOf( S.found, aims(), function( entry ) { const node = nodeAt( entry.path ); return node !== null && penOf( entry.path, node ) !== null } ).forEach( function( carrier ) {
			carrier.tabIndex = -1;
			carrier.setAttribute( 'data-nino-carrier', '' );
		} );

		// A loop has no element of its own to stop at: its pencil is a stop of its own, in front of the first repetition, which shows where the loop stands when the keyboard is on it
		loopStopsOf( aims(), function( entry ) { const node = nodeAt( entry.path ); return node !== null && penOf( entry.path, node ) !== null } ).forEach( function( entry ) {

			const open = penOf( entry.path, nodeAt( entry.path ) );

			const loopHost = doc.createElement('nino-builder-pen');
			const loopShadow = loopHost.attachShadow( { mode : 'open' } );
			const loopStyle = doc.createElement('style');
			const button = tool( 'pencil', '', open.name, 'pen loop' );

			loopStyle.textContent = PEN_CSS;
			loopShadow.appendChild( loopStyle );
			Object.keys( tone ).forEach( function( name ) { loopHost.style.setProperty( '--b-'+ name, tone[name] ) } );
			button.dataset.key = keyAt( Number( entry.path.split('.')[0] ) );
			button.addEventListener( 'click', function() { open.open( button ) } );
			button.addEventListener( 'focus', function() {
				shownOf( entry )[0].scrollIntoView( { block : 'nearest' } );
				place();
			} );
			button.tabIndex = -1;
			loopShadow.appendChild( button );

			if( entry.elements.length > 0 )
				( entry.elements[0].closest('a, button') ?? entry.elements[0] ).before( loopHost );
			else
				entry.holder.appendChild( loopHost );

			loopPens.push( { entry : entry, button : button, shadow : loopShadow, host : loopHost } );
		} );

		roving( doc, penHost, pen, loopPens );
		S.paintBars = paintBars;
		place();
		paintBars();
		S.place = place;

		if( typeof win.ResizeObserver === 'function' )
			new win.ResizeObserver( place ).observe( doc.body );

		win.addEventListener( 'resize', place );
		win.addEventListener( 'load', place );

		// The bars keep to what is in sight: they follow the scrolling, once for the frames between
		let waiting = false;

		win.addEventListener( 'scroll', function() {

			if( waiting === true )
				return;

			waiting = true;
			win.requestAnimationFrame( function() {
				waiting = false;
				place();
			} );
		} );

		if( S.refocus !== null && S.refocus.from === 'frame' ) {

			const wanted = S.refocus;

			const target = shadow.querySelector( '[data-at="'+ wanted.at+ '"][data-action="'+ wanted.action+ '"]:not(:disabled)' ) ?? shadow.querySelector( 'button[data-at="'+ wanted.at+ '"]:not(:disabled)' );

			// The way back from the extended view waits for the drawing that has the block: the page that is loaded first need not be that one
			S.refocus = target === null && wanted.action === 'advanced' ? wanted : null;

			if( target !== null ) {
				selectBlock( keyAt( wanted.at ), false );
				target.focus();
			}
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

	// What the page in the frame shows is what the loops are counted from: the tree of the extended view and the lightbox that is open are told again, whichever view drew it
	const pageDrawn = function( found ) {

		S.found = found;

		if( S.mode === 'advanced' && S.tree !== null )
			S.tree.update( itemsNow() );

		if( S.box !== null && S.box.recount !== null )
			S.box.recount();

		if( S.box !== null && S.box.variantField !== undefined && S.box.variantField !== null )
			refreshVariant();
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
		S.variants = S.pendingVariants;
		S.stale = false;
		S.editing = null;

		if( S.mode === 'advanced' ) {
			instrumentAdvanced( frame, doc );
			return;
		}

		const found = traced( doc );
		const style = doc.createElement('style');

		// What the page shows is the one it is drawn for: the blocks have the keys of the views that show them, and nothing is on until the pointer or the focus comes
		found.blocks.forEach( function( block ) { block.key = keyAt( block.at ) } );
		pageDrawn( found );

		S.hot = '';
		S.focus = '';
		S.overBar = '';
		style.textContent = frameCss( toneOf() );
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

		// A block that is clicked or tapped is the chosen one: where no pointer rests over a block - a touch screen - its bar is the one that stays
		doc.addEventListener( 'click', function( event ) {

			const key = keyInPage( event.target );

			if( key !== null && key !== S.selected )
				selectBlock( key, false );
		} );
		doc.addEventListener( 'submit', function( event ) { event.preventDefault() }, true );
		doc.addEventListener( 'keydown', function( event ) { keys( event, found.blocks, doc ) } );

		bars( frame, found.blocks, behind );

		if( S.penBack !== '' && S.penFocus !== null ) {

			const path = S.penBack;

			S.penBack = '';
			S.penFocus( path );
		}

		// The way back from the extended view shows the page where it was left
		if( S.scroll !== null ) {
			frame.contentWindow.scrollTo( { top : S.scroll } );
			S.scroll = null;
		}
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

		paintBox();

		if( S.mode === 'advanced' ) {
			paintAdvanced();
			return;
		}

		paintBar( view );
		view.buttons.undo.disabled = S.undo.length === 0;
		view.buttons.redo.disabled = S.redo.length === 0;
		paintOutline();
	};

	// --- the dialog "Insert block" ----------------------------------------------------------------------------------

	// Where a block comes from, as a chip says it: the label the kernel gives the origin
	const originWord = function( origin ) {
		return localized( origin.label );
	};

	// The frame of a tile, once there is an address for it: the page with the block in it, laid out as a desktop lays it out and scaled into the box
	const tileFrame = function( box, held, dir ) {

		const frame = el( 'iframe', 'builder-tile-frame' );

		// A picture, not a page: no script of the site runs in it, and nothing in it reaches the workbench - the frame has no right at all
		frame.setAttribute( 'sandbox', '' );
		frame.setAttribute( 'loading', 'lazy' );
		frame.setAttribute( 'tabindex', '-1' );
		frame.setAttribute( 'aria-hidden', 'true' );
		frame.setAttribute( 'src', ( dir ?? S.dir )+ held.address+ ( held.address.indexOf('?') === -1 ? '?' : '&' )+ held.query );
		box.appendChild( frame );
		Nino.adminUi.scaleFrame( frame, box, TILE.width, TILE.height );
	};

	/**
	 *	The picture of a tile is asked for when its box is on screen, and once for as long as the draft holds. The server keeps the newest TILES drafts of the session and
	 *	drops the others, so what is held here is the newest as well
	 *
	 */
	const tileAsk = function( box, adapter ) {

		// A panel that shows tiles outside of a sheet brings its adapter: the request goes through it, the tokens are kept apart from the page panel's, and its dir is the one the picture is asked at
		const via = adapter ?? S.adapter;
		const locale = adapter === undefined ? S.locale : adapter.base().locale;
		const key = ( adapter === undefined ? '' : adapter.panel+ '|' )+ box.dataset.block+ '|'+ locale;
		const held = S.tiles[key];
		const dir = adapter === undefined ? S.dir : adapter.dir ?? S.dir;

		if( box.querySelector('iframe') !== null )
			return;

		if( held !== undefined && Date.now() < held.until ) {
			tileFrame( box, held, dir );
			return;
		}

		via.call( 'tile', Object.assign( via.base(), { key : box.dataset.block, token : held === undefined ? '' : held.token } ), function( status, response ) {

			if( status !== 200 )
				return;

			delete S.tiles[key];
			S.tiles[key] = { token : response.token, query : response.query, address : response.address, until : Date.now() + response.ttl * 1000 - GRACE };

			Object.keys( S.tiles ).slice( 0, Math.max( 0, Object.keys( S.tiles ).length - TILES ) ).forEach( function( old ) { delete S.tiles[old] } );

			if( box.isConnected !== false && box.querySelector('iframe') === null )
				tileFrame( box, S.tiles[key], dir );
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

		if( entry.variants > 0 ) {

			const count = el( 'span', 'nino-admin-hint builder-tile-variants', entry.variants === 1 ? word('variant/one') : word( 'variant/many', entry.variants ) );

			count.id = 'builder-tile-'+ entry.key+ '-variants';
			described.push( count.id );
			text.appendChild( count );
		}

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
			size			: 'wide',
			className	: 'builder-insert-dialog',
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
			yes			: word('confirm/drop-title'),
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

	/**
	 *	The sheet the editor works on: a page of the site - which is what the panel of the Builder opens - or a block, a draft whose top level is that one block and that has no page,
	 *	no outline and no frames. Everything that takes the draft to a place - where the requests go and what they carry, where "back" leads, the address and the name of the
	 *	panel, which level of the screen is shown, whether there is a last state - is the adapter's, so that another panel can work on a sheet of its own with the same
	 *	code. The adapter of a page is what the panel has always done
	 *
	 *	@return		{Object}									{ panel, level, call( action, payload, done, extra ), base(), go( parts, options ), href( parts ), set( parts ), show(), back( advanced ), fail( level, status, response ), restore } - and, for a sheet that is
	 *														not a page: readOnly (nothing of it is changed), sources: 'block' (the dialog of a source offers what a block can carry), uploads: false (a picture
	 *														is no file here), buttons ([ { key, label, name, onPress } ] more of the bar), reload() (what the conflict of a save does to load the sheet again), code (what stands quietly behind the name in the title: the key of the block)
	 */
	const pageAdapter = {
		panel		: 'builder',
		level		: 'builder-page',
		restore	: true,
		call		: function( action, payload, done, extra ) { Nino.admin.builder.call( action, payload, done, extra ) },
		base		: function() { return { template : S.template, locale : S.locale } },
		go			: function( parts, options ) { Nino.admin.builder.go( parts, options ) },
		href		: function( parts ) { return Nino.admin.router.href( 'builder', parts ) },
		set			: function( parts ) { Nino.admin.router.set( 'builder', parts ) },
		show		: function() { Nino.adminUi.showLevel( 'builder-page', { focus : false } ) },
		fail		: function( level, status, response ) { Nino.admin.builder.fail( level, status, response ) },

		// Where "back" goes from the view, the name it says and whether it keeps the draft: the extended view of a page is one draft with the page; that of a frame
		// goes to where it was opened from - the list, a page or the view of the frame itself, the address of which is S.origin - and, where it is not known (an
		// address that was typed or reloaded), to the page the address shows the frame on. The page that is left is asked about first (go() guards it)
		back		: function( advanced ) {

			if( advanced === false )
				return { parts : [], name : word('label/pages'), keep : false, block : false };

			if( S.frame === false )
				return { parts : [ S.adv.slug ], name : nameOfPage( S.template ), keep : true, block : true };

			const parts = S.origin ?? [ S.page !== '' ? S.page : addressOf( S.template ) ];

			return { parts : parts, name : parts.length === 0 ? word('label/pages') : nameOfPage( templateOf( parts[0] ) ), keep : false, block : parts[0] === addressOf( S.template ) };
		},
	};

	S.adapter = pageAdapter;

	const api = function( action, payload, done, extra ) {
		S.adapter.call( action, Object.assign( S.adapter.base(), payload ), done, extra );
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
		S.shared = response.shared ?? S.shared;
		S.uses = response.uses ?? S.uses;
		S.level = response.level ?? S.level;
		S.schemas = response.schemas ?? S.schemas;
		S.findings = response.findings ?? {};
		S.pendingVariants = response.variants ?? [];
		S.typeTitles = response.titles ?? S.typeTitles;
		S.links = response.links ?? S.links;
		learn( response.schemas ?? {} );
		// An answer with no texts is an empty list in JSON: the texts are a map, and a text that is typed is a key of it
		S.texts = Array.isArray( response.texts ) === true || response.texts === undefined || response.texts === null ? {} : response.texts;
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
		api( 'preview', { tree : S.top, texts : S.texts, slots : S.copies, pictures : picturesNow(), inserted : insertedNow(), token : token, look : null, page : S.page }, function( status, response ) {

			if( status === 200 ) {
				S.pending = response.sources;
				S.address = response.address;
				S.findings = response.findings ?? {};
				S.pendingVariants = response.variants ?? [];

				if( S.mode === 'advanced' && S.tree !== null )
					S.tree.update( itemsNow() );
			}

			done( status, response );
		} );
	};

	const save = function( done ) {

		const forced = S.force;
		const pressed = S.view !== null && dc.activeElement === S.view.bar.save;

		S.force = false;
		S.saving = true;
		endEdit( true );

		api( 'save', { tree : S.top, texts : changedTexts(), slots : S.copies, pictures : picturesNow(), inserted : insertedNow(), base : S.stamp, force : forced }, function( status, response ) {

			S.saving = false;

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
					onReload		: function() { typeof S.adapter.reload === 'function' ? S.adapter.reload() : openPage( S.target, S.origin, function() {} ) },
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
			title		: word( frame === '' ? 'confirm/restore-title' : 'confirm/restore-title-frame', frame ),
			message	: word( frame === '' ? 'confirm/restore-message' : 'confirm/restore-message-frame', frame ),
			yes			: word( frame === '' ? 'confirm/restore-title' : 'confirm/restore-title-frame', frame ),
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
					onReload		: function() { openPage( S.target, S.origin, function() {} ) },
					onCancel		: function() {},
				} );
				return;
			}

			S.view.bar.line.error( status, response );
		} );
	};

	// The working language moved, whoever moved it: unsaved input is asked about first (Save writes it in the language it was typed in), then the page is opened again
	// in the new one at the place it was left. A page that Discard closed on the way is opened again
	const followLanguage = function( locale ) {

		// A sheet of a block is the panel's that brought it: that panel follows the language, and there is no page here to open again
		if( S.sheet === 'block' )
			return;

		if( S.ready === false ) {

			const left = S.dropped;

			S.dropped = null;

			if( Nino.admin.builder.ready === true )
				Nino.admin.builder.reload( left === null ? undefined : function() { Nino.admin.builder.go( left ) } );

			return;
		}

		if( locale === S.locale )
			return;

		Nino.admin.dirty.guard( [ 'builder' ], function() {
			Nino.admin.builder.reload( function() { openPage( targetOf( here().parts ) ?? S.target, S.origin, function() {} ) } );
		}, function() { Nino.admin.workLocale.set( S.locale ) } );
	};

	// Discard of the choice of a language: the shell closes the page, its address is kept for the moment it does
	const dropDraft = function() {

		S.dropped = S.at;
		wn.setTimeout( function() { S.dropped = null }, 0 );
		discard();
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

	// Where "back" goes from the view: the adapter of the sheet says
	const backOf = function( advanced ) {
		return S.adapter.back( advanced );
	};

	// What the page in the frame stands for, for the switchboard between the views: the elements of a block by its key, and the key of the block an element is part of
	const elementsOfKey = function( key ) {

		const block = S.found === null ? undefined : S.found.blocks.find( function( candidate ) { return candidate.key === key } );

		return block === undefined ? [] : block.elements;
	};

	// The extended view: the elements of the page that stand for a component of the block - every repetition of it where it stands in a loop - and the component an element is
	// part of, the innermost one, both by the place in the block
	const elementsOfRel = function( rel ) {

		if( S.found === null || rootAt() === -1 )
			return [];

		const abs = absOf( rel );

		return S.found.all.filter( function( entry ) { return entry.path === abs } ).reduce( function( all, entry ) { return all.concat( entry.elements ) }, [] );
	};

	const relInPage = function( element ) {

		const root = String( rootAt() );
		let best = null;

		if( S.found === null || rootAt() === -1 )
			return null;

		S.found.all.forEach( function( entry ) {

			const inside = entry.path === root || entry.path.indexOf( root+ '.' ) === 0;

			if( inside === true && entry.elements.some( function( held ) { return held.contains( element ) } ) === true && ( best === null || entry.path.length > best.path.length ) )
				best = entry;
		} );

		return best === null ? null : ( best.path === root ? 'root' : best.path.slice( root.length + 1 ) );
	};

	const keyInPage = function( element ) {

		// A bar, a place or the pencil stands in the shadow of one element, which the page takes for the element that holds the pointer: it is the block of that bar
		if( ( S.host !== null && element === S.host ) || ( S.penHost !== null && element === S.penHost ) )
			return S.overBar === '' ? null : S.overBar;

		const block = S.found === null ? undefined : S.found.blocks.find( function( candidate ) {
			return candidate.elements.some( function( inside ) { return inside.contains( element ) } );
		} );

		return block === undefined ? null : block.key;
	};

	// The way back from a view: the focus goes to where the person came from - the block that was opened, whose row in the outline is the chosen one and has the focus on its
	// button of the extended view, in an outline that is as open as it was -, or the title of the page a frame was opened from
	const leaveExtended = function( advanced, way ) {

		if( advanced === true && way.block === true ) {
			S.selected = keyAt( rootAt() );
			S.refocus = { at : rootAt(), action : 'advanced', from : 'outline' };
		}

		S.adapter.go( way.parts, advanced === true && way.block === false ? { focus : S.frame === true ? 'frames' : 'page' } : undefined );
	};

	// The frames of the site are two pages of their own, which a page does not hold: one icon beside its name asks which of them
	const openFrames = function( opener ) {

		Nino.adminUi.pickDialog( {
			title		: word('label/frames-edit'),
			items		: [
				{ value : '@header', label : word('word/frame-header'), description : word('hint/frame-short') },
				{ value : '@footer', label : word('word/frame-footer'), description : word('hint/frame-short') },
			],
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			size		: 'narrow',
			opener	: opener,
			onChoose	: function( part ) { S.adapter.go( [ addressOf( S.template ), part ], { focus : 'back' } ) },
		} );
	};

	// The view that is on the screen is left: what it asked of the page in the frame and of the other views stops
	const leaveView = function() {

		if( S.view === null )
			return;

		S.view.preview.stop();

		if( S.view.board !== null )
			S.view.board.leave();
	};

	const build = function( level ) {

		level.innerHTML = '';

		// The extended view goes back to the page it is of - or the frame -, the page to the list
		const advanced = S.mode === 'advanced';
		const layouts = layoutsOf( advanced );
		const way = backOf( advanced );
		const home = way.parts;
		const settings = el( 'div', 'builder-column' );
		const buttons = {};
		const gl = { tree : null, rail : el( 'div', 'builder-rail' ), lit : [], first : null };

		const tools = Nino.adminUi.previewTools( {
			width		: S.width,
			onWidth	: function( name ) {
				S.width = name;
				S.chosen = true;
				S.view.preview.width( name );
			},
			// The draft is drawn again, and not the last one that was sent: what was typed in a text is no change that sends a draft, and the page that is loaded again
			// would not show it
			onReload	: function() {
				S.stale = true;
				S.view.preview.flush();
			},
		} );

		// The frames of the site are written for every page: from the design on, and not in the view of a frame
		const icons = advanced === false && S.frame === false && S.level >= 2 ? [ { icon : 'panels-top-left', name : word('label/frames-edit'), onClick : function( event ) { openFrames( event.currentTarget ) }, id : 'builder-frames' } ] : [];

		const context = Nino.adminUi.contextBar( {
			panel	: Nino.adminUi.panelName( level ),
			title	: S.sheet === 'block' ? S.name : ( advanced === true ? blockTitle() : S.name ),
			code	: S.sheet === 'block' ? ( S.adapter.code ?? '' ) : ( advanced === true ? '' : S.template+ '.tpl' ),
			back	: {
				to			: way.name,
				href		: S.adapter.href( home ),
				onClick	: function() { leaveExtended( advanced, way ) },
			},
			icons	: icons,
			tools	: tools.el,
			within	: level,
		} );

		const back = context.querySelector('.nino-admin-back-link');

		back.id = 'builder-back-list';
		Nino.adminUi.localeBadge( S.locale, context );

		if( way.keep === true )
			back.setAttribute( 'data-keep-draft', '' );

		const bar = Nino.adminUi.saveBar( {
			id			: 'builder-bar',
			buttons	: [
				{ key : 'undo', label : word('label/undo'), name : word('label/undo'), onPress : function() { undo(); settle2( 'undo', 'redo' ) } },
				{ key : 'redo', label : word('label/redo'), name : word('label/redo'), onPress : function() { redo(); settle2( 'redo', 'undo' ) } },
			].concat( S.adapter.buttons ?? [] ),
			onSave		: save,
			onRestore	: askRestore,
		} );

		bar.buttons.undo.insertBefore( Nino.adminUi.icon('undo-2'), bar.buttons.undo.firstChild );
		bar.buttons.redo.insertBefore( Nino.adminUi.icon('redo-2'), bar.buttons.redo.firstChild );
		buttons.undo = bar.buttons.undo;
		buttons.redo = bar.buttons.redo;

		( S.adapter.buttons ?? [] ).forEach( function( extra ) { buttons[extra.key] = bar.buttons[extra.key] } );

		// A sheet that is only looked at has nothing to save
		if( S.adapter.readOnly === true )
			bar.save.hidden = true;

		// The outline: the blocks of the page as a tree - the place above the first one where the page has no head, and below it the rows -, in the column at the left of the surface
		if( advanced === false ) {

			const body = el( 'div', 'builder-outline' );

			gl.first = Nino.adminUi.button( word('label/insert-short'), function() {

				const spot = firstSpot();

				if( spot !== undefined )
					openInsert( spot, gl.first, 'outline' );
			}, { className : 'nino-admin-btn--small builder-insert', name : word('label/insert-short') } );
			gl.first.hidden = true;
			gl.tree = Nino.adminUi.tree( {
				id			: 'builder-outline',
				label		: word('label/outline'),
				items		: [],
				onSelect	: function( id ) { selectBlock( id, true ) },
				onAction	: function( key, id ) { rowAction( key, id, dc.activeElement !== null && gl.tree.el.contains( dc.activeElement ) === true ? dc.activeElement : gl.tree.el ) },
				onHover		: function( id ) { S.view.board.outline.set( id ) },
			} );

			// Alt and an arrow moves the block of the row the focus is in, as in the page
			gl.tree.el.addEventListener( 'keydown', function( event ) {

				const row = event.target.closest === undefined ? null : event.target.closest('li[data-id]');

				if( event.altKey === true && ( event.key === 'ArrowUp' || event.key === 'ArrowDown' ) && row !== null && atOf( row.dataset.id ) !== -1 ) {
					event.preventDefault();
					move( atOf( row.dataset.id ), event.key === 'ArrowUp' ? -1 : 1, 'outline' );
				}
			} );
			body.appendChild( gl.first );
			body.appendChild( gl.tree.el );
			gl.body = body;
		}

		const split = Nino.adminUi.split( {
			id			: advanced === true ? 'builder-split-extended' : 'builder-split',
			fill		: true,
			settings	: advanced === true ? settings : undefined,
			aside		: advanced === true ? undefined : { content : gl.body, label : word('label/outline'), rail : gl.rail },
			preview		: { src : 'about:blank', title : word('label/preview-title'), width : layouts[S.width] ?? undefined },
			onLoad		: instrument,
			onView		: function( view ) {

				if( view === 'preview' && S.chosen === false && split.frame.parentNode.getBoundingClientRect().width < NARROW ) {
					S.width = 'mobile';
					tools.setWidth( 'mobile' );
					S.view.preview.width( 'mobile' );
				}
			},
		} );

		const preview = Nino.adminUi.previewFrame( {
			split		: split,
			send		: send,
			widths	: layouts,
			mark		: 'loose',
			address	: function( query ) { return S.dir+ S.address+ ( S.address.indexOf('?') === -1 ? '?' : '&' )+ query },
			onError	: function( status, response ) { bar.line.error( status, response ) },
		} );

		if( typeof wn.ResizeObserver === 'function' )
			new wn.ResizeObserver( function() { if( S.place !== null ) S.place() } ).observe( split.frame.parentNode );

		// What the pointer or the focus is on, in every view that shows the blocks: the outline, its strip and the frame of the page are joined by one switchboard
		let board = null;

		if( advanced === false ) {

			const sync = Nino.adminUi.hoverSync();
			const inOutline = sync.join( function( key ) { lightOutline( key ) } );
			const marks = preview.marks( {
				elements	: elementsOfKey,
				keyAt			: keyInPage,
				onHover		: function( key ) {
					S.hot = key === null ? '' : key;

					if( S.paintBars !== null )
						S.paintBars();

					inPage.set( key );
				},
			} );
			const inPage = sync.join( function( key ) { marks.highlight( key ) } );

			board = {
				outline	: inOutline,
				page		: inPage,
				leave		: function() {
					marks.stop();
					inOutline.leave();
					inPage.leave();
				},
			};
		}
		else {

			// The tree, the page in the frame and the line of the path show the same component: what the pointer or the focus is over in one is marked in the others - the page
			// shows the chosen component where nothing is pointed at, and every repetition of a pattern of a loop where the pattern is
			const sync = Nino.adminUi.hoverSync();
			let pointed = null;
			const inTree = sync.join( function( key ) {
				if( S.tree !== null )
					S.tree.highlight( key );
			} );
			const marks = preview.marks( {
				elements	: elementsOfRel,
				keyAt			: relInPage,
				onHover		: function( key ) { inPage.set( key ) },
			} );
			const inPage = sync.join( function( key ) {
				pointed = key;
				marks.highlight( key === null ? ( S.marked === '' ? null : S.marked ) : key );
			} );

			board = {
				tree		: inTree,
				page		: inPage,
				show		: function( scroll ) { marks.highlight( pointed === null && S.marked !== '' ? S.marked : pointed, { scroll : scroll === true } ) },
				refresh	: function() { marks.refresh() },
				leave		: function() {
					marks.stop();
					inTree.leave();
					inPage.leave();
				},
			};
		}

		S.found = null;
		S.paintBars = null;
		S.penFocus = null;
		S.penHost = null;
		S.host = null;
		S.hot = '';
		S.focus = '';
		S.gl = advanced === true ? null : gl;
		S.view = { level : level, split : split, preview : preview, bar : bar, buttons : buttons, title : context.querySelector('.nino-admin-contextbar-title'), kind : S.mode, board : board, tools : tools };

		if( advanced === true )
			split.words( { settings : word('adv/tree'), preview : word('label/preview') } );

		level.appendChild( context );
		level.appendChild( split.el );
		level.appendChild( bar.el );

		if( typeof Nino.admin.dirty === 'object' ) Nino.admin.dirty.register( S.adapter.panel, bar.entry( { discard : dropDraft, saving : function() { return S.saving === true } } ) );

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
	 *	Open a sheet of the kind block: a draft whose top level is one block, with no page, no outline and no frames. The panel that works on it brings its adapter - where
	 *	the requests go, where "back" leads, the name of the panel - and the draft in the form of the answer of an opened page: tree, texts, sources, schemas, known,
	 *	images, slots, stamp, level, locale. The tree view of that block is drawn into the level the adapter names, with the line of its path, the marks that join the views, the
	 *	frame with the page of the block, the lightbox of its components and the steps of Undo
	 *
	 *	@param		{Object}		adapter				As pageAdapter, with template (the name its words are filed under), name and, where the address has a first part, slug
	 *	@param		{Object}		draft					The answer of an opened page, for one block
	 *
	 *	@return		{boolean}									Whether the sheet is open: false where the draft has no block
	 */
	const openSheet = function( adapter, draft ) {

		leaveView();
		release();

		const page = dc.getElementById('builder-page');

		if( page !== null && adapter.level !== 'builder-page' )
			page.innerHTML = '';

		S.sheet = 'block';
		S.adapter = adapter;
		S.ready = false;
		S.built = false;
		S.template = adapter.template;
		S.page = '';
		S.origin = null;
		S.named = {};
		S.selected = '';
		S.frame = false;
		S.staged = [];
		S.dir = draft.dir ?? '';
		S.dim = draft.dim ?? 'frame';
		S.address = draft.address ?? '/';
		S.locale = draft.contentLocale ?? draft.locale ?? S.locale;
		S.fresh = draft.fresh ?? {};
		S.locales = draft.locales ?? [];
		S.native = draft.native ?? '';
		S.catalog = null;
		S.tiles = {};
		S.undo = [];
		S.redo = [];
		S.name = adapter.name ?? '';
		take( draft );

		const block = S.top.find( function( node ) { return kindOf( node ) === 'block' } );

		if( block === undefined || idOf( block ) === '' )
			return false;

		S.adv = { slug : adapter.slug ?? '', block : idOf( block ) };
		S.mode = 'advanced';
		S.tree = null;
		S.mark = null;
		S.ready = true;
		S.stale = true;
		S.target = null;
		build( dc.getElementById( adapter.level ) );
		S.built = true;
		adapter.show();
		S.at = partsOf();
		adapter.set( S.at );

		return true;
	};

	// The sheet is left: the editor is the page panel's again, with nothing open
	const closeSheet = function() {

		if( S.sheet === 'page' )
			return;

		leaveView();
		release();

		// What the sheet drew is no more: a view of the page that is drawn next has the ids of the bar and the split to itself
		const drawn = dc.getElementById( S.adapter.level );

		if( drawn !== null )
			drawn.innerHTML = '';

		S.sheet = 'page';
		S.adapter = pageAdapter;
		S.ready = false;
		S.built = false;
		S.view = null;
		S.adv = null;
		S.mode = 'basic';
		S.template = '';
		S.top = [];
		S.undo = [];
		S.redo = [];
		S.found = null;
		S.tree = null;
		S.box = null;
	};

	/**
	 *	Open a template: the page as the server has it, drawn on the screen, its draft asked for
	 *
	 *	@param		{Array|null}	origin				The address the extended view of a frame goes back to; null where that is the page the address shows it on
	 *	@param		{Function}	done					Called with whether it was opened
	 */
	const openPage = function( target, origin, done ) {

		closeSheet();

		const template = target.template;

		// The block that is chosen is a block of the page that is open
		if( template !== S.template )
			S.selected = '';

		leaveView();

		S.ready = false;
		S.built = false;
		S.template = template;
		S.page = target.page;
		S.origin = origin;
		S.named = {};

		Nino.admin.builder.call( 'open', { template : template, page : target.page, locale : Nino.admin.workLocale.current }, function( status, response ) {

			const level = dc.getElementById('builder-page');

			if( status !== 200 ) {
				Nino.admin.builder.fail( level, status, response );
				Nino.adminUi.showLevel( 'builder-page', { focus : false } );
				done( false );
				return;
			}

			// The language moved while the page was on its way: it is asked for again in the language of the workbench
			if( response.contentLocale !== Nino.admin.workLocale.current ) {
				openPage( target, origin, done );
				return;
			}

			S.frame = response.frame === true;
			S.staged = [];
			S.dir = response.dir;
			S.dim = response.dim;
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
	 *	What an address names: the template, the page a frame is shown on, and for the extended view the block
	 *
	 *	@param		{Array}			parts				[ page, block ]
	 *
	 */
	const targetOf = function( parts ) {

		const template = templateOf( parts[0] ?? '' );

		if( template === '' )
			return null;

		const head = { mode : 'basic', template : template, page : '', slug : addressOf( template ), block : '' };

		if( parts.length < 2 || /^@(?:header|footer)$/.test( parts[0] ) === true )
			return head;

		const frame = /^@(?:header|footer)$/.test( parts[1] ) === true;

		if( frame === false && /^[a-z][a-z0-9-]*$/.test( parts[1] ) === false )
			return head;

		return {
			mode		: 'advanced',
			template	: frame === true ? templateOf( parts[1] ) : template,
			page		: frame === true ? parts[0].replace( /^page-/, '' ) : '',
			slug		: parts[0],
			block		: parts[1],
		};
	};

	/**
	 *	Show what an address names: the page as it is written on, or the extended view of one of its blocks, and in it the tree or one component. A way into the
	 *	extended view that the account may not take - it is for the design - or a block that is not there lands on the page, with a sentence
	 *
	 *	@param		{Object}		target				As targetOf() answers
	 */
	const show = function( target ) {

		const level = dc.getElementById( S.adapter.level );
		const was = S.adv;
		let mode = target.mode;
		let because = '';

		S.adv = mode === 'advanced' ? { slug : target.slug, block : target.block } : null;

		// Another block by the address is another view: its name, its marks and its tree are its own
		const another = was !== null && S.adv !== null && ( was.block !== S.adv.block || was.slug !== S.adv.slug );

		if( another === true )
			S.marked = '';

		if( mode === 'advanced' && S.level < 2 )
			because = word('msg/no-advanced');
		else if( mode === 'advanced' && rootAt() === -1 )
			because = word('msg/no-block');

		if( because !== '' ) {
			mode = 'basic';
			S.adv = null;
		}

		S.mode = mode;
		S.tree = null;
		S.mark = null;

		if( S.built === false || S.view === null || S.view.kind !== mode || another === true ) {

			leaveView();

			build( level );
			S.built = true;
		}
		else if( mode === 'advanced' ) {
			drawLeft();
			paint();
		}

		S.adapter.show();

		S.at = mode === 'basic' ? [ addressOf( S.template ) ] : partsOf();
		S.adapter.set( S.at );

		if( because !== '' )
			told( because );

		// The focus goes where the move says: into the view that was opened on its title, or back to the icon or the title it was opened from
		const wanted = S.focusNext;

		S.focusNext = '';

		if( wanted === 'back' && mode === 'advanced' ) {

			if( S.view.title.hasAttribute('tabindex') === false )
				S.view.title.setAttribute( 'tabindex', '-1' );

			S.view.title.focus();
		}
		else if( wanted === 'frames' && mode === 'basic' && dc.getElementById('builder-frames') !== null )
			dc.getElementById('builder-frames').focus();
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

		const block = S.box !== null ? S.box.block : ( S.adv !== null ? S.adv.block : null );

		if( block === null )
			return -1;

		return S.top.findIndex( function( node ) {
			return kindOf( node ) === 'block' && ( S.frame === true || idOf( node ) === block );
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

	const actionsOf = function( rel, container, editable ) {

		const list = [];
		const title = titleOfRel( rel );

		// A sheet that is only looked at has the properties of a component to read and nothing to do with it
		if( S.adapter.readOnly === true )
			return editable === true ? [ { key : 'edit', icon : 'pencil', name : word( 'label/edit-component', title ) } ] : [];

		if( container === true )
			list.push( { key : 'add', icon : 'plus', name : word( 'label/add-in', title ) } );

		if( rel !== 'root' ) {
			list.push( { key : 'up', icon : 'arrow-up', name : word( 'label/up-comp', title ), disabled : siblingAt( rel, -1 ) === -1 } );
			list.push( { key : 'down', icon : 'arrow-down', name : word( 'label/down-comp', title ), disabled : siblingAt( rel, 1 ) === -1 } );
		}

		// What the register knows has its properties in the lightbox: the pieces that are locked have none
		if( editable === true )
			list.push( { key : 'edit', icon : 'pencil', name : word( 'label/edit-component', title ) } );

		if( rel !== 'root' )
			list.push( { key : 'remove', icon : 'trash-2', name : word( 'label/remove-comp', title ), danger : true } );

		return list;
	};

	// The note at the right of an entry: the width of a column - the one every screen has - and the variant where it is not the standard one
	const metaOf = function( node, schema ) {

		const parts = [];
		const width = schema.attributes.size !== undefined && schema.attributes.size.responsive === true ? widthsOf( ( node.a ?? {} ).size )[''] : '';
		const variant = typeof ( node.a ?? {} ).variant === 'string' ? node.a.variant : '';

		if( width !== '' )
			parts.push( width+ ' %' );

		if( variant !== '' && variant !== schema.standard )
			parts.push( variantLabel( schema, variant ) );

		return parts.join(' · ');
	};

	// What a loop shows, in a note: the title of the type of its elements and how they are sorted
	const loopNote = function( node, schema, rel ) {

		const type = typeof node.s === 'string' && node.s.charAt(0) === '/' ? typeTitle( node.s ) : '';
		const sort = schema.attributes.sort !== undefined && typeof ( node.a ?? {} ).sort === 'string' && node.a.sort !== '' ? word( 'sort/by', sortSentence( typeof node.s === 'string' ? node.s : '', node.a.sort ) ) : '';

		return [ type, sort, loopCount( node, rel ) ].filter( function( part ) { return part !== '' } ).join(' · ');
	};

	// --- the sort of a loop, in words -------------------------------------------------------------------------------------

	// The most steps a sort has: the first orders, and where two are equal the next decides
	// How long a strip of variants rests before its choice is asked for, in milliseconds
	const VARIANT_REST = 450;

	const SORT_STEPS = 3;

	// What a field is for the words of its direction: a date is newest and oldest, a number smallest and largest, anything else a text from A to Z
	const sortKind = function( type ) {
		return type === 'date' || type === 'datetime' ? 'date' : ( type === 'integer' || type === 'double' ? 'number' : 'text' );
	};

	// The sort attribute as steps - `-date,title` is the field date reversed and then title - and whether the list can show it: a field the type does not have, the word
	// random, an empty step or more steps than the list takes are shown as they are written
	const sortSteps = function( value, names ) {

		const steps = [];
		let exact = true;

		String( value ?? '' ).split(',').forEach( function( part ) {

			const word = part.trim();

			if( word === '' )
				return;

			const reverse = word.charAt(0) === '-';
			const field = reverse === true ? word.slice( 1 ) : word;

			if( names.indexOf( field ) === -1 )
				exact = false;

			steps.push( { field : field, reverse : reverse } );
		} );

		return { steps : steps, exact : exact === true && steps.length <= SORT_STEPS && String( value ?? '' ).trim().split(',').every( function( part ) { return part.trim() !== '' } ) };
	};

	const sortWritten = function( steps ) {
		return steps.map( function( step ) { return ( step.reverse === true ? '-' : '' )+ step.field } ).join(',');
	};

	// The name of a field the way the project gives it: the label of the model, the word for the three the elements all have, else the name
	const fieldWord = function( type, name ) {

		const info = S.fieldInfo[type];
		const label = info === undefined ? undefined : info.labels[name];

		if( typeof label === 'string' && label !== '' )
			return label;

		return [ 'id', 'uri', 'slug' ].indexOf( name ) !== -1 ? word( 'source/field-'+ name ) : name;
	};

	// The direction of a step in words that fit the field: "A to Z" for a text, "newest first" for a date, "smallest first" for a number
	const directionWord = function( type, name, reverse ) {

		const info = S.fieldInfo[type];

		return word( 'sort/'+ ( reverse === true ? 'desc' : 'asc' )+ '-'+ sortKind( info === undefined ? '' : info.types[name] ) );
	};

	// The sort of a loop in a sentence: where it is the order the elements were made in, and else its steps - a step that runs against the grain is told how
	const sortSentence = function( type, value ) {

		if( String( value ?? '' ).trim() === '' )
			return word('sort/none');

		const info = S.fieldInfo[type];
		const read = sortSteps( value, info === undefined ? [] : Object.keys( info.types ) );

		if( read.exact !== true )
			return String( value );

		return read.steps.map( function( step ) { return fieldWord( type, step.field )+ ( step.reverse === true || sortKind( info.types[step.field] ) === 'date' ? ' ('+ directionWord( type, step.field, step.reverse )+ ')' : '' ) } ).join(', ');
	};

	// How many elements a loop shows, counted from the page in the frame: the first component inside it is drawn once for each element. None where there is no page to count in
	const loopCount = function( node, rel ) {

		const count = loopElements( node, rel );

		return count === null || count === 0 ? '' : ( count === 1 ? word('adv/element') : word( 'adv/elements', count ) );
	};

	// How often the first component inside a loop is on the page, null where there is nothing to count in
	const drawnOf = function( node, rel ) {

		if( S.found === null )
			return null;

		const list = node.c ?? [];

		for( let at = 0; at < list.length; at++ )
			if( isPiece( list[at] ) === true && list[at].n !== undefined && S.schemas[list[at].n] !== undefined ) {

				const abs = absOf( childOf( rel, at ) );

				return S.found.all.filter( function( entry ) { return entry.path === abs } ).length;
			}

		return null;
	};

	// The number of elements a loop shows: one repetition of it. A table draws its first component once more in its head, which is no element; a loop inside another loop is
	// drawn once for each element of the outer one, and the number told is of one of them
	const loopElements = function( node, rel ) {

		let count = drawnOf( node, rel );

		if( count === null )
			return null;

		const schema = S.schemas[node.n];

		if( schema !== undefined && schema.preview === 'table' && count > 0 )
			count--;

		for( let up = parentOf( rel ), seen = rel; seen !== 'root' && up !== undefined; seen = up, up = parentOf( up ) ) {

			const outer = relNode( up );

			if( outer !== null && outer.n !== undefined && S.schemas[outer.n] !== undefined && S.schemas[outer.n].loop === true ) {

				const times = drawnOf( outer, up );

				if( times !== null && times > 1 )
					count = Math.round( count / times );

				break;
			}
		}

		return count;
	};

	// The finding of a loop whose sort the list cannot show - a field the type has not, the word random, too many steps: the row of the tree says it as the lightbox does
	const unsortable = function( node, schema ) {

		if( schema === undefined || schema.loop !== true || S.level < 2 || typeof node.s !== 'string' || S.fieldInfo[node.s] === undefined )
			return undefined;

		const given = typeof ( node.a ?? {} ).sort === 'string' ? node.a.sort : '';

		return given.trim() === '' || sortSteps( given, Object.keys( S.fieldInfo[node.s].types ) ).exact === true ? undefined : word( 'sort/unknown', given );
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

		const entry = {
			id			: rel,
			kind		: titleOfRel( rel ),
			icon		: schema === undefined ? 'code' : schema.icon,
			children	: children,
			open		: true,
			locked	: schema === undefined,
			finding	: hintOf( absOf( rel ) ) ?? unsortable( node, schema ),
			actions	: actionsOf( rel, schema !== undefined && schema.container === true, schema !== undefined ),
		};

		if( schema === undefined )
			return entry;

		entry.name = ownText( node, rel, 40 );
		entry.meta = metaOf( node, schema );

		// A value that comes from a field of the element - its first word, or an attribute, begins with a dot - is the field, and no text of its own; the word before the field
		// of an attribute is the attribute's
		const fieldsRead = [];

		if( typeof node.s === 'string' && node.s.charAt(0) === '.' ) {
			entry.source = node.s;
			fieldsRead.push( node.s );
		}
		else
			Object.keys( schema.attributes ).forEach( function( name ) {

				const given = ( node.a ?? {} )[name];

				if( [ 'text', 'link', 'image' ].indexOf( schema.attributes[name].type ) !== -1 && typeof given === 'string' && /^\.[A-Za-z][A-Za-z0-9_-]*$/.test( given ) === true && fieldsRead.length === 0 ) {
					entry.source = { field : given, label : localized( schema.attributes[name].label ) };
					fieldsRead.push( given );
				}
			} );

		// A field that the type of the loop does not have is the commonest mistake in a loop: the row says so
		const type = fieldsRead.length === 0 ? null : loopOf( rel );

		if( type !== null && type !== '' && Array.isArray( S.fields[type] ) === true ) {

			const missing = fieldsRead.find( function( written ) { return [ 'id', 'uri', 'slug', 'lang' ].indexOf( written.slice( 1 ) ) === -1 && S.fields[type].indexOf( written.slice( 1 ) ) === -1 } );

			if( missing !== undefined )
				entry.finding = { text : word( 'finding/nofield', missing.slice( 1 ), typeTitle( type ) ), state : 'warn' };
		}

		// What is inside a loop is drawn for each element: the type is told in the tag where the loop stands in another
		if( schema.loop === true )
			entry.loop = { detail : loopNote( node, schema, rel ), type : typeof node.s === 'string' && node.s.charAt(0) === '/' ? typeTitle( node.s ) : '' };

		return entry;
	};

	const itemsNow = function() {

		const node = rootAt() === -1 ? undefined : S.top[rootAt()];

		return node === undefined ? [] : [ itemOf( node, 'root' ) ];
	};

	// The parts of an address of the extended view: the page and the block
	const partsOf = function() {
		return [ S.adv.slug, S.adv.block ];
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

		if( S.adapter.readOnly === true )
			return false;

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

		if( S.adapter.readOnly === true )
			return false;

		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/unit-title'),
			message	: word('confirm/unit-message'),
			yes			: word('confirm/unit-title'),
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

		if( S.adapter.readOnly === true )
			return false;

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

		if( S.adapter.readOnly === true )
			return false;

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

		// What was put in is the chosen entry, and what is above it is opened by that: a column that had nothing in it has the new component in view
		if( S.tree !== null )
			S.tree.select( made );

		told( word( 'msg/component-added', localized( schema.label ) ) );
	};

	// The place for a component: asked of the server, which says what the register offers there - by group, each as the call it would be - and the dialog lists it
	const openOffer = function( rel, opener ) {

		if( S.adapter.readOnly === true )
			return false;

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
				size			: 'form',
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
			return key === '' ? word( 'source/is-address', written ) : word( key.indexOf( '/template/'+ S.template+ '/' ) === 0 ?																																																																																																																									ownWord( 'source/is-slot', 'source/is-slot-block' ) : 'source/is-slot-other', ( S.pictures[key] ?? S.images[key] ?? {} ).label ?? key.slice( key.lastIndexOf('/') + 1 ) );

		if( key === '' )
			return word( 'source/is-fixed', shortened( written, 60 ) );

		return word( key.indexOf( '/template/'+ S.template+ '/' ) === 0 ?																																																																				ownWord( 'source/is-key', 'source/is-key-block' ) : 'source/is-key-other', key.slice( key.lastIndexOf('/') + 1 ), shortened( textOf( key ) || ( S.shared[key] ?? '' ), 60 ) || '–' );
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
		redrawBox();
	};

	/**
	 *	The dialog Source: where the content of a call comes from. Tabs - the arrows walk them - for the text of a key that is there, a new one, a fixed one; for an
	 *	address also a page of the site and an address; for a picture a slot that is there or a new one. A source that is chosen closes the dialog and is put into the call
	 */
	const openSource = function( rel, spec, opener ) {

		const node = relNode( rel );
		const scope = scopeOf( rel );
		const loop = loopOf( rel );
		const tabs = ( spec.kind === 'link' ? [ 'pick', 'new', 'page', 'address' ] : ( spec.kind === 'image' ? [ 'pick', 'new' ] : [ 'pick', 'new', 'fixed' ] ) ).filter( function( tab ) { return S.adapter.sources !== 'block' || [ 'pick', 'new' ].indexOf( tab ) !== -1 } );
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
						out.push( entryOf( label !== '' ? label : ( text !== '' ? text : name ), root === '' ? word(																																																																																																			ownWord( 'source/page', 'source/page-block' ) ) : word(																																																																																																																																																																																																																																																													ownWord( 'source/page-of', 'source/page-of-block' ), root.charAt(0).toUpperCase()+ root.slice( 1 ) ), '', function() { finish( { form : 'key', written : writtenFor( key ) } ) } ) );
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
				field.appendChild( el( 'span', '', word( spec.kind === 'link' ? 'source/address-text' : 'source/text-in', Nino.adminUi.localeName( S.locale ) ) ) );

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
			size			: 'form',
			nested		: true,
			opener		: opener,
			choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function() {

				if( choice !== null )
					applySource( rel, spec, choice );
			},
		} );

		buttons.pick.focus();
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
			size			: 'form',
			opener		: opener,
			choices		: [ { value : 'close', label : Nino.adminUi.text('/_admin/common/label/close'), kind : 'primary' } ],
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

		// A picture of a block is the placeholder of the kernel: there is no file to give it
		if( S.adapter.uploads === false )
			return box;

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
		let typed = false;
		let made = '';

		name.querySelector('input').addEventListener( 'input', function() {
			if( typed === false )
				key.querySelector('input').value = slugOf( name.querySelector('input').value );
		} );
		key.querySelector('input').addEventListener( 'input', function() { typed = true } );

		[ name, about, category, key ].forEach( function( part ) { body.appendChild( part ) } );

		Nino.adminUi.formDialog( {
			title			: word('own/title'),
			message		: word('own/note'),
			body			: body,
			submit		: word('own/title'),
			cancel		: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener		: opener,
			onSubmit	: function( values, ctl ) {

				// What is wrong is said at the field it is about, which gets the focus; the dialog stays
				const refuse = function( label, text ) {

					const control = label.querySelector( label === category ? 'select' : 'input' );

					Nino.adminUi.fieldError( label, control, 'builder-own-problem', text );
					control.focus();
					ctl.idle();
				};

				if( name.querySelector('input').value.trim() === '' )
					return refuse( name, word('own/need-name') );

				if( NAME.test( key.querySelector('input').value ) === false )
					return refuse( key, word('own/bad-key') );

				api( 'blocksave', { tree : S.top, at : String( rootAt() ), key : key.querySelector('input').value, name : name.querySelector('input').value.trim(), description : about.querySelector('input').value.trim(), category : category.querySelector('select').value, texts : S.texts, pictures : picturesNow() }, function( status, response ) {

					if( status !== 200 ) {
						refuse( ( response ?? {} ).field === 'key' ? key : ( ( response ?? {} ).field === 'category' ? category : name ), Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/own' ) );
						return;
					}

					S.catalog = null;
					made = name.querySelector('input').value.trim();
					body.closest('dialog').addEventListener( 'close', function() { told( word( 'own/saved', made ) ) } );
					ctl.close();
				} );
			},
		} );
	};

	// --- the view "New page" ----------------------------------------------------------------------------------------

	/**
	 *	A field of a short value that every language of the site has - the name of the page, its address -: the name of the field with its (?), and a row for each language,
	 *	the language as a quiet badge in front of its input. The rows are a group, named by the name of the field
	 *
	 *	@param		{Object}		options				{ key, label, help, example, maxLength, locales }
	 *
	 *	@return		{Object}									{ el, inputs, rows } - the inputs and the rows (the labels) by language
	 */
	const perLanguage = function( options ) {

		const field = el( 'div', 'nino-admin-field' );
		const name = el( 'span', 'nino-admin-field-name', options.label );
		const group = el( 'div', 'builder-new-rows' );
		const inputs = {};
		const rows = {};

		options.locales.forEach( function( locale ) {

			const row = el( 'label', 'builder-new-row' );
			const input = el( 'input', 'nino-admin-input' );

			input.type = 'text';
			input.dataset.key = options.key+ '-'+ locale;
			input.setAttribute( 'aria-required', 'true' );

			if( options.maxLength !== undefined )
				input.maxLength = options.maxLength;

			if( options.example !== undefined )
				input.placeholder = options.example;

			row.appendChild( el( 'span', 'builder-new-language', Nino.adminUi.localeName( locale, options.locales ) ) );
			row.appendChild( input );
			group.appendChild( row );
			inputs[locale] = input;
			rows[locale] = row;
		} );

		field.appendChild( name );
		field.appendChild( group );
		Nino.adminUi.sectionHelp( name, group, options.help );

		// The star follows the words once the (?) has taken the name: the help is "Help for Name", not "Help for Name*"
		const star = el( 'span', 'nino-admin-required', '*' );

		star.setAttribute( 'aria-hidden', 'true' );
		name.querySelector('.nino-admin-field-text').appendChild( star );

		return { el : field, inputs : inputs, rows : rows };
	};

	// What a menu is called: the name it has in the words of the panel, else its identifier
	const menuName = function( nav ) {
		return Nino.content.getText( '/_admin/builder/page/menu-'+ nav ) !== '' ? word( 'page/menu-'+ nav ) : nav;
	};

	/**
	 *	The menus a page can be in: the name of the field with its (?) and a checkbox for each menu, a checklist of the workbench
	 *
	 *	@param		{Array}			navs					The keys of the menus
	 *
	 *	@return		{Object}									{ el, boxes } - the boxes by menu
	 */
	const menusField = function( navs ) {

		const field = el( 'div', 'nino-admin-field' );
		const name = el( 'span', 'nino-admin-field-name', word('page/menus') );
		const list = el( 'div', 'nino-admin-checklist' );
		const boxes = {};

		navs.forEach( function( nav ) {

			const label = el( 'label', '' );
			const box = el( 'input', '' );

			box.type = 'checkbox';
			box.value = nav;
			box.dataset.key = 'menu-'+ nav;
			boxes[nav] = box;
			label.appendChild( box );
			label.appendChild( el( 'span', '', menuName( nav ) ) );

			// The identifier stands quietly behind a name that the menu has
			if( menuName( nav ) !== nav )
				label.appendChild( el( 'small', 'nino-admin-checklist-state', nav ) );

			list.appendChild( label );
		} );

		field.appendChild( name );
		field.appendChild( list );
		Nino.adminUi.sectionHelp( name, list, word('page/menus-help') );

		return { el : field, boxes : boxes };
	};

	// What the Builder wrote of the pages - a page made, copied or taken away - is told to the shell, which lets the panels that show the same things ask again and
	// the window beside the workbench draw the website anew; where the writer knows the address of the page, it says it
	const announce = function( uri ) {
		[ 'templates', 'texts', 'slots', 'routes', 'navigations' ].forEach( function( topic ) { Nino.admin.touch( topic, uri ) } );
	};

	// What stands in the place of the choice of the block in the view that makes a copy: the page it is made from, which is no choice
	const copiedFrom = function( origin ) {

		const field = el( 'div', 'nino-admin-field' );

		field.appendChild( el( 'span', 'nino-admin-field-name', word('page/copy-source') ) );
		field.appendChild( el( 'p', 'builder-new-origin', word( 'page/copy-of', origin.label ) ) );

		return field;
	};

	/**
	 *	Draw the view that makes a page, at #builder/@new: the way back to the list, a name and an address for each language of the site - the address is proposed from the
	 *	name until it is written by hand -, the menus the page is in and the block it starts with, and the bar with the one button that makes it. What the server refuses
	 *	is said at the field it is about; the page that is made is opened as it stands. The view of a copy (#builder/@new/<page>) is the same form, filled with what the server
	 *	proposes, with the page it is made from where the block would be chosen
	 *
	 *	@param		{Element}		level					The element the view is drawn in
	 *	@param		{Object}		data					The answer of the list: the languages and the menus
	 *	@param		{Object|null}	origin				The answer of copyof for the view that makes the copy of a page: its names and addresses and menus, which the form starts with, and the page itself in
	 *																		place of the block to start with; null for a new page
	 *
	 *	@return		{Element|boolean}					Where the focus goes when the person opened the view: the name of the panel, the heading of the view
	 */
	const drawNew = function( level, data, origin ) {

		const locales = data.locales.filter( function( entry ) { return entry.active === true } ).map( function( entry ) { return entry.locale } );
		const native = locales.indexOf( data.native ) !== -1 ? data.native : locales[0];
		const form = el( 'form', 'builder-new-form' );
		const group = el( 'fieldset', '' );
		const names = perLanguage( { key : 'name', label : word('page/name'), help : word('page/name-help'), maxLength : 120, locales : locales } );
		const addresses = perLanguage( { key : 'address', label : word('page/address'), help : word( origin === null || origin === undefined ? 'page/address-hint' : 'page/copy-address-hint' ), example : word('page/address-example'), locales : locales } );
		const menus = menusField( data.navs );
		const copy = origin !== null && origin !== undefined;
		const start = copy === true ? copiedFrom( origin ) : Nino.adminUi.selectField( { key : 'block', label : word('page/start'), help : word('page/start-help'), options : [ { value : '', label : word('page/start-none') } ], value : '' } );
		const chosen = copy === true ? null : start.querySelector('select');
		const message = el( 'p', '' );
		const primary = Nino.adminUi.button( word( copy === true ? 'page/copy-title' : 'page/title' ), null, { type : 'submit' } );
		const bar = Nino.adminUi.actionBar( { status : { line : message, fresh : true }, primary : primary } );
		const touched = {};
		let sending = false;

		level.innerHTML = '';
		form.id = 'builder-new-form';
		form.noValidate = true;
		const legend = el( 'legend', '', word( copy === true ? 'page/copy-open' : 'page/open' ) );

		group.appendChild( legend );
		group.appendChild( names.el );
		group.appendChild( addresses.el );

		if( data.navs.length > 0 )
			group.appendChild( menus.el );

		group.appendChild( start );
		Nino.adminUi.sectionHelp( legend, group, word( copy === true ? 'page/copy-note' : 'page/note' ) );
		form.appendChild( group );
		form.appendChild( bar.el );
		bar.line.bind( form );

		level.appendChild( Nino.adminUi.contextBar( {
			back : { to : word('label/pages'), href : Nino.admin.router.href( 'builder', [] ), onClick : function() { Nino.admin.builder.go( [] ) } },
		} ) );
		level.appendChild( form );

		// The copy starts from the names and the addresses of the page it is made from - the addresses are the ones the server found free, and stay until a hand changes them
		if( copy === true ) {

			locales.forEach( function( locale ) {
				names.inputs[locale].value = origin.names[locale] ?? '';
				addresses.inputs[locale].value = origin.addresses[locale] ?? '';
				touched[locale] = true;
			} );

			Object.keys( menus.boxes ).forEach( function( nav ) { menus.boxes[nav].checked = origin.navs.indexOf( nav ) !== -1 } );
		}

		// The address of a language is proposed from its name until a hand has written it
		locales.forEach( function( locale ) {

			addresses.inputs[locale].addEventListener( 'input', function() { touched[locale] = addresses.inputs[locale].value !== '' } );
			names.inputs[locale].addEventListener( 'input', function() {

				if( touched[locale] === true )
					return;

				const slug = slugOf( names.inputs[locale].value, MAX_SLUG );

				addresses.inputs[locale].value = slug === '' ? '' : '/'+ slug;
			} );
		} );

		if( copy === false )
			Nino.admin.builder.call( 'blocks', {}, function( status, response ) {

				if( status !== 200 )
					return;

				response.blocks.filter( function( entry ) { return entry.off === null } ).forEach( function( entry ) {
					chosen.appendChild( Object.assign( el( 'option', '', localized( entry.name ) ), { value : entry.key } ) );
				} );
			} );

		// What is wrong is said at the field it is about, in the line of the bar and in the field, which has the focus
		const refuse = function( wrapper, input, key, text ) {

			Nino.adminUi.fieldError( wrapper, input, 'builder-new-error-'+ key, text );
			bar.line.fail( word('error/create') );
			input.focus();
		};

		const check = function() {

			const missing = Nino.content.getText('/_admin/common/error/missing');

			for( const kind of [ [ names, 'name', 'page/name' ], [ addresses, 'address', 'page/address' ] ] )
				for( const locale of locales )
					if( kind[0].inputs[locale].value.trim() === '' ) {
						refuse( kind[0].rows[locale], kind[0].inputs[locale], kind[1]+ '-'+ locale, Nino.adminUi.format( missing, word( kind[2] )+ ' ('+ Nino.adminUi.localeName( locale, locales )+ ')' ) );
						return false;
					}

			return true;
		};

		const submit = function( done ) {

			Nino.adminUi.clearFieldErrors( form );

			if( sending === true || check() === false ) {
				done( false );
				return;
			}

			const sent = { names : {}, addresses : {}, navs : Object.keys( menus.boxes ).filter( function( nav ) { return menus.boxes[nav].checked } ) };

			if( copy === true )
				sent.from = origin.template;
			else
				sent.block = chosen.value;

			locales.forEach( function( locale ) {
				sent.names[locale] = names.inputs[locale].value;
				sent.addresses[locale] = addresses.inputs[locale].value;
			} );

			sending = true;
			primary.disabled = true;
			bar.line.saving();

			Nino.admin.builder.call( copy === true ? 'duplicate' : 'create', sent, function( status, response ) {

				if( status !== 200 ) {

					sending = false;
					primary.disabled = false;

					const named = ( response ?? {} ).field;
					const target = named === 'uri' || named === 'template' ? 'address-'+ native : ( typeof named === 'string' ? named.replace( /^httpUri-/, 'address-' ) : '' );
					const where = target.indexOf('name-') === 0 ? [ names, target.slice( 5 ) ] : ( target.indexOf('address-') === 0 ? [ addresses, target.slice( 8 ) ] : null );
					const text = Nino.adminUi.api.errorText( status, response, '/_admin/builder/error/create' );

					if( where !== null && where[0].inputs[where[1]] !== undefined ) {
						Nino.adminUi.fieldError( where[0].rows[where[1]], where[0].inputs[where[1]], 'builder-new-error-'+ target, text );
						where[0].inputs[where[1]].focus();
						bar.line.fail( word('error/create') );
					}
					else if( target === 'block' && chosen !== null ) {
						Nino.adminUi.fieldError( start, chosen, 'builder-new-error-block', text );
						chosen.focus();
						bar.line.fail( word('error/create') );
					}
					else
						bar.line.fail( text );

					done( false );
					return;
				}

				// The form holds what is made: nothing is lost by leaving it, and the page that was made is opened - unless the answer of a question has taken the person elsewhere
				N.taken = valuesOf();
				announce();
				Nino.adminUi.say( word( 'page/made', names.inputs[ locales.indexOf( Nino.admin.workLocale.current ) !== -1 ? Nino.admin.workLocale.current : native ].value.trim() ) );
				Nino.admin.builder.init( function() {

					if( N.open === true )
						Nino.admin.builder.go( [ response.slug ], { focus : 'page' } );
				} );
				done( true );
			} );
		};

		form.addEventListener( 'submit', function( event ) {
			event.preventDefault();
			submit( function() {} );
		} );

		// What the form holds, to know whether it holds more than was taken for saved
		const valuesOf = function() {
			return JSON.stringify( [ locales.map( function( locale ) { return [ names.inputs[locale].value, addresses.inputs[locale].value ] } ), Object.keys( menus.boxes ).map( function( nav ) { return menus.boxes[nav].checked } ), chosen === null ? '' : chosen.value ] );
		};

		N.bar = bar;
		N.taken = valuesOf();

		// The form is no view of the language of the workbench - it shows them all - so the choice of another asks nothing of it and loses nothing (see choosing())
		if( typeof Nino.admin.dirty === 'object' ) Nino.admin.dirty.register( 'builder', {
			bar			: function() { return bar.el },
			save		: submit,
			saving	: function() { return sending === true },
			discard	: function() { N.taken = valuesOf() },
			isDirty	: function() { return N.choosing === false && N.open === true && valuesOf() !== N.taken },
		} );

		return nameOfPanel();
	};

	// The heading of the list is the name of the panel: the first heading in it is the title of the group of the frames, which is no place for the focus
	const nameOfPanel = function() {

		const pane = dc.getElementById('builder-list').closest('[data-panel]');
		const title = pane === null ? null : pane.querySelector('.admin-panel-title');

		return title === null ? true : title;
	};

	// Whether an address is the one of the view that makes a page: #builder/@new - or, with the word of a page behind it, the one that makes the copy of that page
	const isNew = function( parts ) {
		return parts[0] === NEW && ( parts.length === 1 || ( parts.length === 2 && templateOf( parts[1] ) !== '' && parts[1][0] !== '@' ) );
	};

	// The address of the view that is open: that of a copy names the page it is made from
	const newParts = function() {
		return N.source === '' ? [ NEW ] : [ NEW, N.source ];
	};

	// The page a view that makes a copy is about: the word behind #builder/@new, '' for the view that makes a new page
	const sourceOfNew = function( parts ) {
		return parts.length === 2 ? parts[1] : '';
	};

	// The view is shown for the account that may make a page, by the address #builder/@new - or #builder/@new/<page> for the copy of a page, which asks the server for
	// what the form starts from: any other account is taken to the list
	const showNew = function() {

		leaveView();

		S.ready = false;

		if( S.list === null || S.list.create !== true ) {
			N.open = false;
			N.source = '';
			Nino.admin.builder.go( [] );
			return;
		}

		const source = sourceOfNew( here().parts );

		N.open = true;
		N.source = source;
		Nino.admin.router.set( 'builder', newParts() );

		if( source === '' ) {
			Nino.adminUi.showLevel( 'builder-new', { focus : drawNew( dc.getElementById('builder-new'), S.list, null ) } );
			return;
		}

		Nino.admin.builder.call( 'copyof', { template : templateOf( source ), locale : Nino.admin.workLocale.current }, function( status, response ) {

			// The person has gone elsewhere while the answer was on its way
			if( N.open !== true || N.source !== source )
				return;

			if( status !== 200 ) {

				N.open = false;
				N.source = '';
				Nino.admin.builder.go( [] );
				listFail( status, response, '/_admin/builder/error/copy' );
				return;
			}

			Nino.adminUi.showLevel( 'builder-new', { focus : drawNew( dc.getElementById('builder-new'), S.list, response ) } );
		} );
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

	// The sentence that explains what an attribute does: it stands behind the (?) at the name of the field, and is none where the schema has no hint
	const helpOf = function( declared ) {

		const hint = localized( declared.hint );

		return hint === '' ? undefined : hint;
	};

	// The (?) of a field that is made by hand - a strip of steps, the three rows of a width -: after its name, and tied to what it explains
	const withHelp = function( field, caption, control, declared, label ) {

		const hint = helpOf( declared );

		if( hint !== undefined )
			caption.appendChild( Nino.adminUi.fieldHelp( hint, { field : control, name : label } ) );

		return field;
	};

	// One attribute as the control its type says: a choice as a pick or a strip of steps, a responsive choice as three rows, a bool as a switch, a number, a line. What an
	// attribute reads - a text, an address, a picture - is a source and has its row in the content; the id is not changed and stands in the advanced part
	const attributeField = function( rel, name, declared ) {

		const node = relNode( rel );
		const given = ( node.a ?? {} )[name];
		const label = localized( declared.label );
		const standard = declared.default;
		const key = rel+ ':'+ name;
		const help = helpOf( declared );

		if( declared.type === 'choice' ) {

			// A choice between off and on is a switch
			if( declared.responsive !== true && declared.options.length === 2 && declared.options.indexOf('off') !== -1 && declared.options.indexOf('on') !== -1 ) {

				const field = Nino.adminUi.switchField( { key : key, checked : ( given === undefined ? standard : String( given ) ) === 'on', label : label, help : help } );

				field.querySelector('input').addEventListener( 'change', function( event ) {

					const now = event.target.checked === true ? 'on' : 'off';

					setAttr( rel, name, now === standard ? null : now );
				} );

				return field;
			}

			// A width is a share of the row: the numbers say so
			const percent = name === 'size' && declared.responsive === true && ( declared.optionLabels === null || declared.optionLabels === undefined ) && declared.options.every( function( option ) { return /^\d+$/.test( option ) } );
			const options = declared.options.map( function( option ) { return { value : option, label : percent === true ? option+ ' %' : attrWord( declared, option ) } } );

			if( declared.responsive === true ) {

				const widths = widthsOf( given ?? standard );
				const group = el( 'div', 'nino-admin-field builder-widths-set' );
				const caption = el( 'span', 'builder-field-label', label );
				const rows = el( 'div', 'builder-widths-rows' );

				caption.id = 'builder-widths-'+ rel.replace( /\./g, '-' )+ '-'+ name;
				group.setAttribute( 'role', 'group' );
				group.setAttribute( 'aria-labelledby', caption.id );
				group.appendChild( caption );
				group.appendChild( rows );

				[ [ '', 'rows/all' ], [ 'm', 'rows/tablet' ], [ 'l', 'rows/desktop' ] ].forEach( function( row ) {

					const choices = row[0] === '' ? options : [ { value : '', label : row[0] === 'm' ? word('rows/below-tablet') : word('rows/below-desktop') } ].concat( options );

					rows.appendChild( Nino.adminUi.selectField( {
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

				return withHelp( group, caption, group, declared, label );
			}

			const current = given === undefined ? standard : String( given );

			// A value that is none of the options stays where it is, and says so
			const stray = given !== undefined && declared.options.indexOf( String( given ) ) === -1;

			if( stray === true )
				options.push( { value : String( given ), label : word( 'field/outside', String( given ) ) } );

			const wide = stray === true || options.length > 6 || options.some( function( option ) { return option.label.length > 8 } );
			const set = function( value ) { setAttr( rel, name, value === standard ? null : value ) };

			if( wide === true )
				return Nino.adminUi.selectField( { key : key, label : label, options : options, value : current, onChange : set, help : help } );

			const box = el( 'div', 'nino-admin-field builder-steps-field' );
			const caption = el( 'span', 'builder-field-label', label );
			const steps = Nino.adminUi.steps( { labelledby : 'builder-steps-'+ rel.replace( /\./g, '-' )+ '-'+ name, steps : options, value : current, standard : standard, onChange : set } );

			// The strip is drawn again with the page of the settings: the focus finds its way back to it by this
			box.dataset.stepKey = key;
			S.strips[key] = steps;
			caption.id = 'builder-steps-'+ rel.replace( /\./g, '-' )+ '-'+ name;
			box.appendChild( caption );
			box.appendChild( steps.el );

			return withHelp( box, caption, steps.el, declared, label );
		}

		if( declared.type === 'bool' ) {

			const on = given === undefined ? standard === true : ( given === true || ON.indexOf( String( given ).toLowerCase() ) !== -1 );
			const field = Nino.adminUi.switchField( { key : key, checked : on, label : label, help : help } );

			field.querySelector('input').addEventListener( 'change', function( event ) {

				const now = event.target.checked === true;

				setAttr( rel, name, now === ( standard === true ) ? null : ( now === true ? true : 'off' ) );
			} );

			return field;
		}

		if( declared.type === 'int' ) {

			const field = Nino.adminUi.numberField( { key : key, label : label, value : given === undefined ? standard : Number( given ), min : declared.min ?? undefined, max : declared.max ?? undefined, help : help } );

			let told = null;

			field.querySelector('input').addEventListener( 'change', function( event ) {

				const value = String( event.target.value ).trim();
				const least = typeof declared.min === 'number' ? declared.min : null;
				const most = typeof declared.max === 'number' ? declared.max : null;

				if( told !== null ) {
					told.remove();
					told = null;
					Nino.adminUi.clearFieldErrors( event.target );
				}

				// A value the setting does not take is told at the field and stays out of the draft - the page keeps showing what the draft draws
				if( value !== '' && ( /^-?\d{1,9}$/.test( value ) === false || ( least !== null && Number( value ) < least ) || ( most !== null && Number( value ) > most ) ) ) {

					const text = least !== null && most !== null ? word( 'field/range-both', least, most ) : ( least !== null ? word( 'field/range-min', least ) : ( most !== null ? word( 'field/range-max', most ) : word('field/whole') ) );

					told = Nino.adminUi.fieldError( field, event.target, 'builder-number-error-'+ key.replace( /[^a-z0-9]/gi, '-' ), text );
					return;
				}

				setAttr( rel, name, value === '' || value === String( standard ) ? null : value );
			} );

			return field;
		}

		const line = Nino.adminUi.textField( { key : key, label : label, value : given === undefined ? '' : String( given ), help : help } );

		line.querySelector('input').addEventListener( 'change', function( event ) {
			setAttr( rel, name, event.target.value === '' ? null : event.target.value );
		} );

		return line;
	};

	// The variants of the block, as a choice in the lightbox of its root: a strip of steps for two to five - where an arrow key is a choice that is asked of the server once it has
	// rested - and a pick for more. One that is none of them is said to be its own and is no step that can be chosen. The field is drawn again with every page that is drawn
	const blockVariantField = function( at ) {

		const held = variantOfBlock( at );
		const label = word('variant/of-block');
		const help = { hint : word('variant/help') };
		let field;

		if( held.items.length >= 2 && held.items.length <= 5 ) {

			const caption = el( 'span', 'builder-field-label', label );
			const steps = Nino.adminUi.steps( { labelledby : 'builder-block-variant', steps : held.items.map( function( item ) { return { value : item[0], label : localized( item[1] ) } } ), value : held.current ?? '', onChange : function( value ) { queueVariant( at, value, 'box' ) } } );

			field = el( 'div', 'nino-admin-field builder-steps-field' );
			caption.id = 'builder-block-variant';
			field.appendChild( caption );
			field.appendChild( steps.el );

			if( held.current === null )
				field.appendChild( el( 'p', 'nino-admin-hint', word('variant/own-note') ) );

			withHelp( field, caption, steps.el, help, label );
			pickerLock( function( off ) { steps.disable( off ) }, true );
			field.focusControl = function() { steps.focus() };
		}
		else {

			const options = ( held.current === null ? [ { value : '', label : word('variant/own') } ] : [] ).concat( held.items.map( function( item ) { return { value : item[0], label : localized( item[1] ) } } ) );

			field = Nino.adminUi.selectField( { key : 'root:block-variant', label : label, options : options, value : held.current ?? '', help : help.hint, onChange : function( value ) {

				if( value !== '' )
					chooseVariant( at, value, 'box' );
			} } );

			const select = field.querySelector('select');

			Array.from( field.querySelectorAll('option') ).forEach( function( option ) {

				if( option.value === '' )
					option.disabled = true;
			} );
			pickerLock( function( off ) { select.disabled = off }, true );
			field.focusControl = function() { select.focus() };
		}

		field.dataset.blockVariant = '';

		if( S.box !== null )
			S.box.variantField = field;

		return field;
	};

	// The field of the lightbox is the choice as the page that was drawn last has it: after a change by hand it says "own" and the way back is a step that can be chosen
	const refreshVariant = function() {

		const old = S.box.variantField;

		if( old === null || old === undefined || old.isConnected === false )
			return;

		const held = S.box.rel === 'root' ? variantOfBlock( rootAt() ) : undefined;
		const fresh = held === undefined ? null : blockVariantField( rootAt() );

		if( fresh === null ) {
			old.remove();
			S.box.variantField = null;
			return;
		}

		old.replaceWith( fresh );

		if( S.variantFocus === true ) {
			S.variantFocus = false;
			fresh.focusControl();
		}
	};

	// The variant: the schema's and the ones the theme adds - a word of a variant that the theme does not have stays where it is, with the sentence of the register
	const variantField = function( rel, node, schema, beside ) {

		const given = typeof ( node.a ?? {} ).variant === 'string' ? node.a.variant : '';
		const labelOf = function( variant ) { return variantLabel( schema, variant ) };
		const options = [ { value : '', label : word( 'variant/standard', labelOf( schema.standard ) ) } ].concat( schema.variants.map( function( variant ) { return { value : variant, label : labelOf( variant ) } } ) );

		if( given !== '' && schema.variants.indexOf( given ) === -1 )
			options.push( { value : given, label : word( 'variant/missing', given ) } );

		return Nino.adminUi.selectField( { key : rel+ ':variant', label : schema.attributes.variant !== undefined ? localized( schema.attributes.variant.label ) : word( beside === true ? 'field/variant-component' : 'field/variant' ), options : options, value : given, onChange : function( value ) { setAttr( rel, 'variant', value === '' ? null : value ) } } );
	};

	// --- the properties of a component: the lightbox ----------------------------------------------------------------------------------

	// The title the project gives a type of elements, else what the loop names
	const typeTitle = function( given ) {
		return ( S.types.find( function( entry ) { return entry.type === given } ) ?? { title : S.typeTitles[given] ?? ( given.indexOf('/_') === 0 ? word('type/example') : given ) } ).title;
	};

	// The types of elements of the project are asked for once, where a loop shows one by its title; what comes is told to the caller
	const askTypes = function( then ) {

		if( S.types.length > 0 || S.typesAsked === true )
			return;

		S.typesAsked = true;
		api( 'keys', { kind : 'types' }, function( status, response ) {

			if( status === 200 && response.types.length > 0 ) {
				S.types = response.types;
				then();
			}
		} );
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
				size			: 'form',
				nested		: true,
				opener		: opener,
				choices		: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ],
				onChoose	: function() {

					if( picked !== '' )
						change( function() { relNode( rel ).s = picked } );

					redrawBox();

					// The fields of the new type are what the sort is made of: they are asked for, and the lightbox is drawn again with them
					if( picked !== '' && S.level >= 2 )
						askFields( redrawBox );
				},
			} );

			const first = body.querySelector('button');

			if( first !== null )
				first.focus();
		} );
	};

	// Where a call reads its words from, at each place it does: its first word and each attribute that is a text, an address or a picture - and a loop reads the elements of a
	// type. `where` says where a value comes from: the field of an element, a text of another place than the page, or the page's own (a fixed text and a value that is not set too)
	const readsOf = function( rel ) {

		const node = relNode( rel );
		const schema = node === null || node === undefined || node.n === undefined ? undefined : S.schemas[node.n];
		const out = [];

		if( schema === undefined )
			return out;

		const place = function( kind, written ) {

			if( written.charAt(0) === '.' )
				return 'field';

			const key = kind === 'link' ? '' : keyOf( rel, written );

			return key !== '' && key.indexOf( '/template/'+ S.template+ '/' ) !== 0 ? 'shared' : 'own';
		};

		if( schema.loop === true && schema.source === 'none' )
			out.push( { form : 'type', kind : 'type', attr : '', label : word('loop/type'), written : typeof node.s === 'string' ? node.s : '', where : 'collection' } );

		const source = schema.source === 'none' ? null : sourceOf( rel );

		if( source !== null )
			out.push( { form : source.kind === 'fixed' ? 'fixed' : 'first', kind : source.kind === 'fixed' ? 'text' : source.kind, attr : '', label : word('field/content'), written : source.written, where : source.kind === 'fixed' ? 'own' : place( source.kind, source.written ), source : source } );

		// What stands under the source is told by what it is - the text, the picture, the target - and not by "content", which is the next section's name
		if( source !== null && out[out.length - 1].where !== 'own' )
			out[out.length - 1].label = word( 'source/label-'+ out[out.length - 1].kind );

		Object.keys( schema.attributes ).forEach( function( name ) {

			const declared = schema.attributes[name];
			const given = ( node.a ?? {} )[name];
			const written = typeof given === 'string' ? given : '';

			if( declared.type === 'text' || declared.type === 'link' || declared.type === 'image' )
				out.push( { form : 'attribute', kind : declared.type, attr : name, label : localized( declared.label ), written : written, where : place( declared.type, written ) } );
		} );

		return out;
	};

	// What the content of a component is made of, for the account: a text that is written here, a picture that is exchanged here, and - for the one that may design - the sentence
	// of every other value with the button that changes its source. An account that may write the content only is given the texts of the page and its pictures, the alt of a
	// picture among them - what the server lets it write
	const contentPlan = function( rel, reads ) {

		const own = '/template/'+ S.template+ '/';
		const plan = [];

		reads.forEach( function( read ) {

			if( read.where !== 'own' )
				return;

			const key = read.form === 'first' ? read.source.key : ( read.form === 'fixed' ? '' : keyOf( rel, read.written ) );
			const mine = key !== '' && key.indexOf( own ) === 0;

			if( read.kind === 'text' && read.form === 'fixed' && S.level >= 2 )
				plan.push( { how : 'text', read : read, key : '' } );
			else if( read.kind === 'text' && mine === true && ( S.level >= 2 || read.form === 'first' || read.attr === 'alt' ) && ( read.form !== 'first' || read.source.editable === true ) )
				plan.push( { how : 'text', read : read, key : key } );
			else if( read.kind === 'image' && mine === true && S.frame === false && ( S.images[key] !== undefined || S.pictures[key] !== undefined ) )
				plan.push( { how : 'picture', read : read, key : key } );
			else if( S.level >= 2 )
				plan.push( { how : 'sentence', read : read, key : key } );
		} );

		return plan;
	};

	// Whether the lightbox of a component has anything to show the account: the sources it reads from elsewhere and the content it may write, and for the one that may design the
	// look and the advanced part as well
	const hasProperties = function( rel ) {

		if( S.level >= 2 )
			return true;

		const reads = readsOf( rel );

		return reads.some( function( read ) { return read.where !== 'own' } ) === true || contentPlan( rel, reads ).length > 0;
	};

	// The sentence of a value in words
	const sentenceOf = function( rel, read ) {

		if( read.form === 'type' )
			return read.written === '' ? word('type/none') : word( 'type/is', typeTitle( read.written ) );

		if( read.form === 'fixed' )
			return word( 'source/is-fixed', shortened( ( relNode( rel ).a ?? {} ).text ?? '', 60 ) );

		return sayOf( { kind : read.kind, attr : read.attr }, read.written, rel );
	};

	// The button that changes where a value comes from, for the one that may design
	const changeButton = function( rel, read ) {

		const spec = { kind : read.kind, attr : read.attr, label : read.label };
		const button = Nino.adminUi.button( read.form === 'type' ? word('type/change') : word('source/change'), function() {

			if( read.form === 'type' )
				openType( rel, button );
			else
				openSource( rel, spec, button );
		}, { className : 'nino-admin-btn--small', name : word( 'source/change-of', read.label ) } );

		button.dataset.key = rel+ ':source:'+ read.attr;

		return button;
	};

	// A value that is told and not written: its name at the left, what it is in a sentence and - for the one that may design - the button that changes it
	const sentenceRow = function( rel, read ) {

		const row = el( 'div', 'nino-admin-field builder-box-row' );
		const value = el( 'div', 'builder-box-value' );

		const name = el( 'span', '', read.label );

		row.appendChild( name );
		value.appendChild( el( 'p', 'builder-box-sentence', sentenceOf( rel, read ) ) );

		// A text of another place is not changed here: where it is changed stands behind the (?), and where more pages read it that is said
		if( read.where === 'shared' && read.kind === 'text' ) {

			const key = keyOf( rel, read.written );

			name.appendChild( Nino.adminUi.fieldHelp( word('word/not-here'), { field : value, name : read.label } ) );

			if( S.uses[key] !== undefined )
				value.appendChild( el( 'p', 'builder-box-sentence', word( 'source/used', S.uses[key] ) ) );
		}

		if( S.level >= 2 )
			value.appendChild( changeButton( rel, read ) );

		row.appendChild( value );

		return row;
	};

	// The text a call reads and this page writes: one field, in the language of the view - a fixed text for every language -, whose change is one step of the undo
	const textRow = function( rel, step ) {

		const read = step.read;
		const fixed = read.form === 'fixed';
		const first = read.form === 'first';
		const format = first === true ? read.source.format : 'plain';
		const rich = first === true && read.kind === 'text' && format !== 'plain' && format !== 'inline';
		const now = function() { return fixed === true ? String( ( relNode( rel ).a ?? {} ).text ?? '' ) : textOf( step.key ) };
		const value = now();
		const options = { key : rel+ ':'+ ( read.attr === '' ? 'content' : read.attr ), label : read.label, value : value };

		// A text the language of the view has none of is shown as the page shows it - the text of the next language - and says so: the field begins empty
		if( fixed === false && value.trim() === '' && fallbackText( step.key ) !== '' ) {
			options.placeholder = fallbackText( step.key );
			options.note = word( 'hint/missing', Nino.adminUi.localeName( S.locale ) );
		}

		if( rich === true )
			options.rows = 5;

		const field = rich === true ? Nino.adminUi.textareaField( options ) : Nino.adminUi.textField( options );

		field.querySelector('span').appendChild( el( 'span', 'nino-admin-badge builder-field-chip', fixed === true ? word('field/all-languages') : Nino.adminUi.localeName( S.locale ) ) );
		field.querySelector( rich === true ? 'textarea' : 'input' ).addEventListener( 'change', function( event ) {
			settle( { kind : fixed === true ? 'fixed' : 'text', key : step.key, format : format }, absOf( rel ), event.target.value, now(), true );
		} );

		return field;
	};

	// The picture a call reads and this page owns: the picture as it is and the button that uploads another
	const pictureRow = function( rel, step ) {

		const row = el( 'div', 'nino-admin-field builder-box-row' );
		const value = el( 'div', 'builder-box-value' );

		row.appendChild( el( 'span', '', step.read.label ) );
		value.appendChild( pictureBox( step.key, step.read.label, redrawBox ) );
		row.appendChild( value );

		return row;
	};

	// The button that makes a value come from another source, under a text or a picture of the page: for the one that may design
	const moreRow = function( rel, read ) {

		const row = el( 'div', 'builder-box-change' );

		row.appendChild( changeButton( rel, read ) );

		return row;
	};

	const sourceRows = function( rel, reads ) {

		const rows = [];

		reads.filter( function( read ) { return read.where !== 'own' } ).forEach( function( read ) {

			if( read.form === 'type' )
				loopRows( rel, read ).forEach( function( row ) { rows.push( row ) } );
			else
				rows.push( sentenceRow( rel, read ) );
		} );

		return rows;
	};

	// The names of the attributes that make a call a loop: what it shows is its source, and stands in the source of the lightbox and nowhere else
	const LOOP_ATTRIBUTES = [ 'sort', 'limit', 'offset', 'query' ];

	// The rows of the source of a loop: its type, its sort in words, how many it shows at most, from which on, the query - and, quietly, how many elements it shows now. The one
	// who may only write the content reads the type and the sort
	const loopRows = function( rel, read ) {

		const node = relNode( rel );
		const schema = S.schemas[node.n];
		const type = typeof node.s === 'string' ? node.s : '';
		const rows = [ sentenceRow( rel, read ) ];

		if( schema.attributes.sort !== undefined )
			rows.push( sortRow( rel, node, schema, type ) );

		if( S.level >= 2 )
			[ 'limit', 'offset', 'query' ].forEach( function( name ) {
				if( schema.attributes[name] !== undefined )
					rows.push( attributeField( rel, name, schema.attributes[name] ) );
			} );

		const count = loopElements( node, rel );

		if( count !== null ) {

			const row = el( 'div', 'nino-admin-field builder-box-row' );
			const value = el( 'div', 'builder-box-value' );

			row.appendChild( el( 'span', '', word('loop/shows') ) );
			const shown = el( 'p', 'builder-box-sentence', count === 1 ? word('adv/element') : word( 'adv/elements', count ) );

			shown.dataset.count = 'loop';
			value.appendChild( shown );
			row.appendChild( value );
			rows.push( row );
		}

		return rows;
	};

	// The sort of a loop as a list of steps - the field and the direction in words that fit it, each step to be taken away, and a step to be added - for the one who designs;
	// a value the list cannot show stays as it is written, with the finding and the way to begin a list of one's own
	const sortRow = function( rel, node, schema, type ) {

		const row = el( 'div', 'nino-admin-field builder-box-row' );
		const value = el( 'div', 'builder-box-value' );
		const label = localized( schema.attributes.sort.label );
		const name = el( 'span', '', label );
		const given = typeof ( node.a ?? {} ).sort === 'string' ? node.a.sort : '';
		const info = S.fieldInfo[type];

		row.appendChild( name );
		name.appendChild( Nino.adminUi.fieldHelp( word('sort/help'), { field : value, name : label } ) );
		row.appendChild( value );

		// Without the fields of the type - the step below the design does not have them, or they are on their way - the sort is the sentence of what is written
		if( S.level < 2 || info === undefined ) {
			value.appendChild( el( 'p', 'builder-box-sentence', sortSentence( type, given ) ) );
			return row;
		}

		const names = Object.keys( info.types );
		const read = sortSteps( given, names );
		const write = function( steps ) { box.pending = false; setAttr( rel, 'sort', steps.length === 0 ? null : sortWritten( steps ) ); redrawBox() };
		const box = S.box !== null && S.box.rel === rel ? S.box : { pending : false };

		if( read.exact === false ) {

			value.appendChild( el( 'p', 'builder-box-sentence', given ) );
			value.appendChild( Nino.adminUi.notice( word( 'sort/unknown', given ) ) );

			const reset = Nino.adminUi.button( word('sort/reset'), function() { write( [] ) }, { className : 'nino-admin-btn--small' } );

			reset.dataset.key = rel+ ':sort:reset';
			value.appendChild( reset );

			return row;
		}

		if( read.steps.length === 0 )
			value.appendChild( el( 'p', 'builder-box-sentence', word('sort/none') ) );

		read.steps.forEach( function( step, at ) {

			const line = el( 'div', 'builder-sort-step' );
			const fields = names.map( function( field ) { return { value : field, label : fieldWord( type, field ) } } );
			const kind = sortKind( info.types[step.field] );
			const directions = [ { value : 'asc', label : word( 'sort/asc-'+ kind ) }, { value : 'desc', label : word( 'sort/desc-'+ kind ) } ];
			const change = function( field, reverse ) {

				const next = read.steps.map( function( held ) { return { field : held.field, reverse : held.reverse } } );

				next[at] = { field : field, reverse : reverse };
				write( next );
			};

			line.appendChild( Nino.adminUi.selectField( { key : rel+ ':sort:'+ at+ ':field', label : word( 'sort/field', at + 1 ), options : fields, value : step.field, onChange : function( field ) { change( field, false ) } } ) );
			line.appendChild( Nino.adminUi.selectField( { key : rel+ ':sort:'+ at+ ':direction', label : word( 'sort/direction', at + 1 ), options : directions, value : step.reverse === true ? 'desc' : 'asc', onChange : function( direction ) { change( step.field, direction === 'desc' ) } } ) );
			line.appendChild( Nino.adminUi.iconButton( 'trash-2', word( 'sort/remove', at + 1 ), function() { write( read.steps.filter( function( held, index ) { return index !== at } ) ) }, { danger : true } ) );
			value.appendChild( line );
		} );

		if( read.steps.length < SORT_STEPS ) {

			const free = names.filter( function( field ) { return read.steps.every( function( step ) { return step.field !== field } ) } ).sort( function( one, other ) { return ( [ 'id', 'uri', 'slug' ].indexOf( one ) === -1 ? 0 : 1 ) - ( [ 'id', 'uri', 'slug' ].indexOf( other ) === -1 ? 0 : 1 ) } );

			// A step that is added is a row with no field yet: the page is written when a field is chosen, and nothing before
			if( box.pending === true && free.length > 0 ) {

				const line = el( 'div', 'builder-sort-step' );
				const options = [ { value : '', label : word('sort/choose') } ].concat( free.map( function( field ) { return { value : field, label : fieldWord( type, field ) } } ) );

				line.appendChild( Nino.adminUi.selectField( { key : rel+ ':sort:new:field', label : word( 'sort/field', read.steps.length + 1 ), options : options, value : '', onChange : function( field ) {
					if( field !== '' )
						write( read.steps.concat( [ { field : field, reverse : false } ] ) );
				} } ) );
				line.appendChild( Nino.adminUi.iconButton( 'trash-2', word( 'sort/remove', read.steps.length + 1 ), function() { box.pending = false; redrawBox() }, { danger : true } ) );
				value.appendChild( line );
			}

			const add = Nino.adminUi.button( word('sort/add'), function() { box.pending = true; redrawBox() }, { className : 'nino-admin-btn--small' } );

			add.dataset.key = rel+ ':sort:add';
			add.disabled = free.length === 0 || box.pending === true;
			value.appendChild( add );
		}

		return row;
	};

	const contentRows = function( rel, plan ) {

		const rows = [];

		plan.forEach( function( step ) {

			if( step.how === 'sentence' )
				rows.push( sentenceRow( rel, step.read ) );
			else {
				rows.push( step.how === 'text' ? textRow( rel, step ) : pictureRow( rel, step ) );

				if( S.level >= 2 )
					rows.push( moreRow( rel, step.read ) );
			}
		} );

		return rows;
	};

	// How it looks and where it stands: the variant and the settings of the schema - not what a call reads, which is its content, and not its id
	const lookRows = function( rel ) {

		const node = relNode( rel );
		const schema = S.schemas[node.n];
		const rows = [];

		// The variant of the block: the first thing of the look of a block that has some - the same choice as the bar of the block has
		if( rel === 'root' && S.frame === false && variantOfBlock( rootAt() ) !== undefined )
			rows.push( blockVariantField( rootAt() ) );

		if( schema.variants.length > 0 )
			rows.push( variantField( rel, node, schema, rows.length > 0 ) );

		Object.keys( schema.attributes ).forEach( function( name ) {

			if( [ 'text', 'link', 'image', 'id' ].indexOf( schema.attributes[name].type ) === -1 && ( schema.loop !== true || LOOP_ATTRIBUTES.indexOf( name ) === -1 ) )
				rows.push( attributeField( rel, name, schema.attributes[name] ) );
		} );

		return rows;
	};

	// The markup a call of html holds, for the account that may develop - the component cleans it where it draws it; with calls inside it is shown and not changed
	const htmlRow = function( rel, node ) {

		const calls = ( node.c ?? [] ).some( function( inner ) { return inner.t === undefined } );
		const field = Nino.adminUi.textareaField( { key : rel+ ':content', label : word('field/markup'), value : ( node.c ?? [] ).map( function( inner ) { return inner.t ?? '' } ).join(''), rows : 10, help : word( calls === true ? 'field/html-calls' : 'field/html-note' ) } );
		const control = field.querySelector('textarea');

		field.querySelector('span').appendChild( el( 'span', 'nino-admin-badge builder-field-chip', word('field/all-languages') ) );

		if( calls === true ) {
			control.readOnly = true;
			control.setAttribute( 'aria-readonly', 'true' );
		}
		else
			control.addEventListener( 'change', function() {
				change( function() { relNode( rel ).c = control.value === '' ? [] : [ { t : control.value } ] } );
			} );

		return field;
	};

	// What stands under "Advanced": the id the texts and pictures of the block stand under, the markup of a call of html, the block as a block of the project and the notation
	// A lightbox of a sheet that is only looked at: every control of it is off, the heads of its sections and the (?) behind a name are not
	const freeze = function( holder ) {

		holder.querySelectorAll('input, select, textarea, button').forEach( function( control ) {

			if( control.getAttribute('aria-expanded') === null && String( control.className ).indexOf('nino-admin-help-button') === -1 )
				control.disabled = true;
		} );
	};

	const advancedRows = function( rel, box ) {

		const node = relNode( rel );
		const schema = S.schemas[node.n];
		const rows = [];
		const id = Object.keys( schema.attributes ).find( function( name ) { return schema.attributes[name].type === 'id' } );

		if( id !== undefined ) {

			const row = el( 'div', 'nino-admin-field builder-box-row' );
			const label = localized( schema.attributes[id].label );
			const name = el( 'span', '', label );
			const value = el( 'code', 'builder-field-value', ( node.a ?? {} )[id] === undefined ? '-' : String( node.a[id] ) );

			name.appendChild( Nino.adminUi.fieldHelp( word('field/read-id'), { field : value, name : label } ) );
			row.appendChild( name );
			row.appendChild( value );
			rows.push( row );
		}

		if( node.n === 'html' && S.level >= DEVELOP )
			rows.push( htmlRow( rel, node ) );

		// A block of the page is kept as a block of the project, for the pages that are made after it: the lightbox gives way to the dialog that asks for its name
		if( rel === 'root' && S.frame === false && S.sheet === 'page' ) {

			const row = el( 'div', 'builder-box-change' );
			const own = Nino.adminUi.button( word('own/open'), function() {
				box.then = 'own';
				box.close();
			}, { className : 'nino-admin-btn--small' } );

			own.dataset.key = 'block:own';
			row.appendChild( own );
			rows.push( row );
		}

		const code = el( 'pre', 'builder-code', S.notation[ rel === 'root' ? 'block' : 'node' ] ?? '' );
		const note = el( 'div', 'nino-admin-field builder-box-row' );

		code.tabIndex = 0;
		note.appendChild( el( 'span', '', word( rel === 'root' ? 'field/notation-block' : 'field/notation' ) ) );
		note.appendChild( code );
		rows.push( note );
		box.code = code;

		return rows;
	};

	// The notation as the template has it, which the server writes of the draft: asked for with every change that is made while the lightbox is open
	const askNotation = function( box ) {

		const turn = ++box.asked;

		api( 'notation', { tree : S.top, look : { block : absOf('root'), node : box.rel === 'root' ? '' : absOf( box.rel ) } }, function( status, response ) {

			if( status === 200 && turn === box.asked && S.box === box && box.code !== null ) {
				S.notation = response.notation ?? {};
				box.code.textContent = S.notation[ box.rel === 'root' ? 'block' : 'node' ] ?? '';
			}
		} );
	};

	// The component of a lightbox is gone - an undo took it away: the focus goes to the work surface, to the row of the tree that is chosen or else to the name of the view
	const focusWork = function() {

		if( S.tree !== null && S.tree.focus( S.marked === '' ? 'root' : S.marked ) === true )
			return;

		if( S.view !== null ) {
			S.view.title.setAttribute( 'tabindex', '-1' );
			S.view.title.focus();
		}
	};

	// The lightbox is drawn again where something it shows was changed from outside - a source that was chosen, a picture, an undo
	const redrawBox = function() {

		if( S.box !== null )
			S.box.draw();
	};

	// What every change leaves in the lightbox that is open: the notation
	const paintBox = function() {

		if( S.box !== null && S.box.code !== null && S.level >= 2 )
			askNotation( S.box );
	};

	/**
	 *	The properties of one component of a page - the one form of them in the whole Builder, whoever opens it: the pencil at the component in the page, its row in the tree of the
	 *	extended view, the row of a block in the outline. A modal dialog of the workbench in the size of a form, its sections in a fixed order - the source where the content comes
	 *	from one, the content, how it looks and where it stands, the advanced part - and only those that have something to show the account: the one that may only write the content
	 *	is given the source, to read, and the content. A value is in the draft the moment it is changed - one step of the undo each -, and Close is all there is to press
	 *
	 *	@param		{number}		at						The index of its block in the top level of the tree
	 *	@param		{string}		rel						'root' for the block itself, else its place in the block
	 *	@param		{Element}		opener				What opened it
	 *	@param		{Function}	[back]				Called when it is closed: gives the focus to what opened it where that is not the element itself
	 *
	 *	@return		{boolean}									Whether it was opened
	 */
	const openProperties = function( at, rel, opener, back ) {

		const root = S.top[at];

		if( S.box !== null || root === undefined || kindOf( root ) !== 'block' || idOf( root ) === '' )
			return false;

		const box = { block : idOf( root ), rel : rel, open : '', code : null, asked : 0, then : '', draw : null, close : null, gone : false, recount : null };

		S.box = box;

		// The notation of the component that was open before is not this one's: it stands empty until the answer comes
		S.notation = {};

		const node = relNode( rel );

		if( node === null || node === undefined || node.n === undefined || S.schemas[node.n] === undefined || hasProperties( rel ) === false ) {
			S.box = null;
			return false;
		}

		const holder = el( 'div', 'builder-box' );
		const body = function( rows ) {

			const part = el( 'div', 'nino-admin-dialog-fields builder-box-body' );

			rows.forEach( function( row ) { part.appendChild( row ) } );

			// A sheet that is only looked at has every control of its rows off, whenever a section makes them
			if( S.adapter.readOnly === true )
				freeze( part );

			return part;
		};
		let accordion = null;

		box.draw = function() {

			const now = relNode( rel );

			if( now === null || now === undefined || S.schemas[now.n] === undefined ) {
				box.gone = true;
				box.close();
				return;
			}

			const inside = holder.contains( dc.activeElement ) === true;
			const held = dc.activeElement !== null && dc.activeElement.dataset !== undefined ? dc.activeElement.dataset.key : undefined;
			const reads = readsOf( rel );
			const plan = contentPlan( rel, reads );
			const sections = [];

			box.code = null;

			if( reads.some( function( read ) { return read.where !== 'own' } ) === true )
				sections.push( { key : 'source', label : word('box/source'), content : function() { return body( sourceRows( rel, reads ) ) } } );

			if( plan.length > 0 )
				sections.push( { key : 'content', label : word('box/content'), summary : ownText( now, rel, 40 ), content : function() { return body( contentRows( rel, plan ) ) } } );

			if( S.level >= 2 && lookRows( rel ).length > 0 )
				sections.push( { key : 'look', label : word('box/look'), summary : variantSummary( now ), content : function() { return body( lookRows( rel ) ) } } );

			if( S.level >= 2 )
				sections.push( { key : 'advanced', label : word('box/advanced'), content : function() { return body( advancedRows( rel, box ) ) } } );

			if( sections.some( function( section ) { return section.key === box.open } ) === false )
				box.open = sections[0].key;

			S.strips = {};
			holder.innerHTML = '';

			const finding = S.level >= 2 ? hintOf( absOf( rel ) ) : undefined;

			if( finding !== undefined )
				holder.appendChild( Nino.adminUi.notice( finding.text ) );

			accordion = Nino.adminUi.accordion( {
				id			: 'builder-box-acc',
				level		: 3,
				open		: box.open,
				sections	: sections,
				onToggle	: function( open ) {

					box.open = open[0] ?? box.open;

					if( box.open === 'advanced' )
						askNotation( box );

				},
			} );
			holder.appendChild( accordion.el );

			if( box.open === 'advanced' )
				askNotation( box );

			// The focus stays in the dialog: on the field it was on, else on the head of the section that is open
			const again = held === undefined ? null : holder.querySelector( '[data-key="'+ held+ '"]' );

			if( again !== null )
				again.focus();
			else if( inside === true && accordion.head( box.open ) !== null )
				accordion.head( box.open ).focus();
		};

		// The number of elements a loop shows is the page's: it is told again when the page in the frame was drawn again, and nothing else of the lightbox is
		box.recount = function() {

			const shown = holder.querySelector('[data-count="loop"]');
			const count = shown === null || relNode( rel ) === null ? null : loopElements( relNode( rel ), rel );

			if( count !== null )
				shown.textContent = count === 1 ? word('adv/element') : word( 'adv/elements', count );
		};

		box.draw();

		// The types of elements are the design's to read: a loop is told by the path of its type to the step below
		if( S.level >= 2 ) {
			askTypes( redrawBox );
			askFields( redrawBox );
		}

		const first = accordion.body( box.open ).querySelector('input, textarea, select, button');
		const name = titleOfRel( rel );

		Nino.adminUi.choiceDialog( {
			title			: word( 'label/edit-component', name ),
			message		: word( 'label/edit-component', name ),
			content		: holder,
			size			: 'form',
			className	: 'builder-lightbox',
			opener		: opener,
			focus			: first ?? undefined,
			choices		: [ { value : 'close', label : Nino.adminUi.text('/_admin/common/label/close'), kind : 'secondary' } ],
			onOpen		: function( handle ) { box.close = function() { handle.close('close') } },
			onChoose	: function() {

				S.box = null;

				if( box.gone === true )
					focusWork();
				else if( typeof back === 'function' )
					back();

				if( box.then === 'own' )
					openBlockSave( opener );
			},
		} );

		return true;
	};

	// --- the extended view: the tree of a block ---

	const variantSummary = function( node ) {

		const count = Object.keys( node.a ?? {} ).filter( function( name ) { return name !== 'id' } ).length;

		return count === 0 ? word('adv/defaults') : word( 'adv/given', count );
	};

	const blockTitle = function() {

		if( S.frame === true )
			return word( 'word/'+ S.template );

		const node = S.top[rootAt()];

		return node === undefined ? '' : titleOf( node );
	};

	// A row of the tree is the chosen one, and the page in the frame shows it
	const chooseRow = function( id, roll ) {

		S.marked = id;

		if( S.tree !== null )
			S.tree.select( id );

		if( S.mark !== null )
			S.mark( roll === true );

	};

	// The types of the loops of the block, and what fields each has: asked for once, and the rows that read a field are drawn again with the answer
	const loopTypes = function( node, into ) {

		if( node.n !== undefined && S.schemas[node.n] !== undefined && S.schemas[node.n].loop === true && typeof node.s === 'string' && /^\/[a-z_][a-z0-9_-]{0,63}$/.test( node.s ) === true )
			into.add( node.s );

		( node.c ?? [] ).forEach( function( inner ) { loopTypes( inner, into ) } );

		return into;
	};

	const askFields = function( then ) {

		if( rootAt() === -1 )
			return;

		loopTypes( S.top[rootAt()], new Set() ).forEach( function( type ) {

			if( S.fields[type] !== undefined )
				return;

			S.fields[type] = null;
			api( 'keys', { kind : 'fields', type : type }, function( status, response ) {

				if( status === 200 ) {
					S.fields[type] = Object.keys( response.fields );
					S.fieldInfo[type] = { types : response.fields, labels : response.labels ?? {} };

					if( typeof then === 'function' )
						then();
					else if( S.tree !== null )
						S.tree.update( itemsNow() );
				}
				else
					delete S.fields[type];
			} );
		} );
	};

	// A component is taken out of the block after the question, where the key for Delete asks it: the undo brings it back
	const askRemoveComponent = function( rel, opener ) {

		if( S.adapter.readOnly === true )
			return;

		if( rel === 'root' || relNode( rel ) === null )
			return;

		if( lockedNode( relNode( rel ) ) === true ) {
			askRemoveUnit( rel, opener );
			return;
		}

		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/component-title'),
			message	: word( 'confirm/component-message', titleOfRel( rel ) ),
			yes			: word('confirm/component-title'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose : function( ok ) {
				if( ok === true )
					removeComponent( rel );
			},
		} );
	};

	const treeLevel = function() {

		const holder = el( 'div', 'builder-adv-tree' );

		S.tree = Nino.adminUi.tree( {
			id				: 'builder-tree',
			label			: word('adv/tree-label'),
			items			: itemsNow(),
			path			: true,
			selected	: S.marked === '' ? 'root' : S.marked,
			onSelect	: function( id ) { chooseRow( id, true ) },
			onAction	: function( key, id ) {

				const opener = S.tree.el.querySelector( '[data-key="'+ key+ '"]' );
				const held = dc.activeElement ?? opener;

				if( key === 'add' )
					openOffer( id, held );
				else if( key === 'edit' )
					openProperties( rootAt(), id, held, function() { S.tree.focus( id, 'edit' ) } );
				else if( key === 'remove' && lockedNode( relNode( id ) ) === true )
					askRemoveUnit( id, held );
				else if( key === 'remove' )
					removeComponent( id );
				else
					moveComponent( id, key === 'up' ? -1 : 1 );
			},
			// The entry that has the stop is the one the preview marks; the tree is not drawn again here - a press on a button of another row moves the stop first
			// The stop of the tree moves by a hand or by the keyboard - the pointer over a row only marks it, in onHover -: the page rolls to the component
			onFocus		: function( id ) {

				S.marked = id;

				if( S.mark !== null )
					S.mark( true );
			},
			onHover		: function( id ) { S.view.board.tree.set( id ) },
		} );

		// A loop is told by the title of its type: the types come and the entries are drawn again
		askTypes( function() { if( S.tree !== null ) S.tree.update( itemsNow() ) } );
		askFields();

		// Enter on a row is the way into its properties and Delete asks to take it out; a button of the row has its own keys
		S.tree.el.addEventListener( 'keydown', function( event ) {

			const row = event.target.closest === undefined ? null : event.target.closest('li[data-id]');

			if( row === null || event.target !== row || relNode( row.dataset.id ) === null )
				return;

			if( event.key === 'Enter' && S.schemas[ relNode( row.dataset.id ).n ] !== undefined )
				openProperties( rootAt(), row.dataset.id, row, function() { S.tree.focus( row.dataset.id ) } );
			else if( event.key === 'Delete' )
				askRemoveComponent( row.dataset.id, row );
		} );

		S.tree.path.classList.add('builder-adv-path');
		holder.appendChild( S.tree.path );
		holder.appendChild( S.tree.el );

		return holder;
	};

	// The left column of the extended view: the tree of the block
	const drawLeft = function() {

		const view = S.view;

		S.tree = null;
		view.split.settings.innerHTML = '';
		view.split.words( { settings : word('adv/tree'), preview : word('label/preview') } );

		// The block itself is the one that is marked, until the focus is on another entry
		if( S.marked === '' || S.marked === '@block' || relNode( S.marked ) === null )
			S.marked = 'root';

		view.split.settings.appendChild( treeLevel() );
	};

	// What a change does to the left column of the extended view
	const paintAdvanced = function() {

		const view = S.view;

		paintBar( view );
		view.buttons.undo.disabled = S.undo.length === 0;
		view.buttons.redo.disabled = S.redo.length === 0;

		if( rootAt() === -1 ) {
			S.adapter.go( [ S.frame === true ? addressOf( S.template ) : S.adv.slug ] );
			return;
		}

		const advFocus = S.advFocus;

		S.advFocus = '';

		if( S.tree !== null ) {

			S.tree.update( itemsNow() );

			if( advFocus !== '' )
				S.tree.focus( advFocus );
		}

		if( S.mark !== null )
			S.mark( false );
	};

	// The page in the frame of the extended view: the components of the block are marked by the switchboard of the views, and a click on one of them - on any repetition of a
	// pattern of a loop - chooses the one row of the pattern. Nothing is written in the frame here
	const instrumentAdvanced = function( frame, doc ) {

		const style = doc.createElement('style');

		pageDrawn( traced( doc ) );

		style.textContent = '[data-nino-pick]{cursor:pointer}';
		doc.head.appendChild( style );

		S.mark = function( scroll ) {
			if( S.view !== null && S.view.board !== null )
				S.view.board.show( scroll );
		};

		doc.querySelectorAll('a, button, input, select, textarea, summary, [tabindex]').forEach( function( element ) { element.tabIndex = -1 } );
		doc.addEventListener( 'submit', function( event ) { event.preventDefault() }, true );
		doc.addEventListener( 'keydown', function( event ) { keys( event, [], doc ) } );
		doc.addEventListener( 'click', function( event ) {

			if( event.target.closest && event.target.closest('a') !== null )
				event.preventDefault();

			const rel = relInPage( event.target );

			if( rel === null || S.stale === true )
				return;

			const node = relNode( rel );

			if( node !== null && S.schemas[node.n] !== undefined )
				chooseRow( rel, false );
		}, true );

		S.mark( true );
	};

	// --- the list ---------------------------------------------------------------------------------------------------

	// When a page was written, as the workbench writes a date: day and minute, the German way round in German and the year first in any other language
	const dateOf = function( seconds ) {

		const at = new Date( seconds * 1000 );
		const two = function( number ) { return String( number ).padStart( 2, '0' ) };
		const german = String( dc.documentElement.lang ).slice( 0, 2 ) === 'de';
		const day = german === true ? two( at.getDate() )+ '.'+ two( at.getMonth() + 1 )+ '.'+ at.getFullYear() : at.getFullYear()+ '-'+ two( at.getMonth() + 1 )+ '-'+ two( at.getDate() );

		return day+ ', '+ two( at.getHours() )+ ':'+ two( at.getMinutes() );
	};

	// The sort key of a date: the year first, whatever the language writes
	const isoOf = function( seconds ) {

		const at = new Date( seconds * 1000 );
		const two = function( number ) { return String( number ).padStart( 2, '0' ) };

		return at.getFullYear()+ '-'+ two( at.getMonth() + 1 )+ '-'+ two( at.getDate() )+ 'T'+ two( at.getHours() )+ ':'+ two( at.getMinutes() );
	};

	// The button that makes a page: the one main button of the bar of the list
	const makeButton = function( id ) {
		return Nino.adminUi.button( word('page/open'), function() { Nino.admin.builder.go( [ NEW ] ) }, { kind : 'primary', id : id } );
	};

	// What a row of the table says of a page: the name with its template quietly under it, and a sign where a language lacks its name or its address
	const nameCell = function( value, row ) {

		const cell = el( 'span', 'builder-cell-name' );
		const first = el( 'span', 'builder-cell-first' );

		first.appendChild( el( 'strong', '', value ) );

		if( row.complete === false ) {

			const sign = el( 'span', 'builder-cell-warn' );

			sign.title = word('list/incomplete');
			sign.appendChild( Nino.adminUi.icon( 'triangle-alert', { label : word('list/incomplete') } ) );
			first.appendChild( sign );
		}

		cell.appendChild( first );
		cell.appendChild( el( 'small', 'builder-cell-file', row.template+ '.tpl' ) );

		return cell;
	};

	// The most readers, and pages that link, a question before a page is deleted lists: the others are counted
	const LISTED = 5;

	/**
	 *	A list of names as a sentence, at most LISTED of them, and how many more there are for the rest. What is not a name is none
	 *
	 *	@param		{Array}			names
	 *
	 *	@return		{string}
	 */
	const listed = function( each ) {

		const shown = each.slice( 0, LISTED ).join(', ');

		return each.length > LISTED ? shown+ ' '+ word( 'confirm/more', each.length - LISTED ) : shown;
	};

	// Who reads or links: each once, a template by the name of its page where the list has it
	const whoIs = function( names ) {

		const named = {};

		( S.list === null ? [] : S.list.pages ).forEach( function( page ) { named[page.template+ '.tpl'] = page.name } );

		return Array.from( new Set( names.filter( function( name ) { return typeof name === 'string' && name !== '' } ).map( function( name ) { return named[name] ?? name } ) ) );
	};

	/**
	 *	The question before a page is deleted, from the answer of the server (uses): the page with its address, and where others read words or pictures of it or link to it,
	 *	who they are - the readers and the pages that link, each at most LISTED, the rest counted. A leftover that has no address is named by its template
	 *
	 *	@param		{Object}		asked					The answer of uses
	 *
	 *	@return		{string}
	 */
	const deleteMessage = function( asked ) {

		const lines = [ asked.remnant === true ? word( 'confirm/delete-leftover', asked.name ) : word( 'confirm/delete-page', asked.name, asked.path ) ];

		// The number and the names are the same thing: the pages and files that read what stays, and the pages that link
		const readers = whoIs( [].concat( ...asked.kept.map( function( entry ) { return entry.by } ) ) );
		const linking = whoIs( asked.linked.map( function( entry ) { return entry.by } ) );

		if( readers.length > 0 )
			lines.push( word( 'confirm/delete-kept', readers.length, listed( readers ) ) );

		if( linking.length > 0 )
			lines.push( word( 'confirm/delete-linked', listed( linking ) ) );

		return lines.join(' ');
	};

	// What an action of the list said was wrong is taken away when the next one starts
	const clearFail = function() {

		const old = dc.getElementById('builder-list').querySelector('.builder-list-error');

		if( old !== null )
			old.remove();
	};

	// What went wrong with an action of the list is said above the table, where the person is looking - and nowhere else - until the next action or a new list
	const listFail = function( status, response, fallback ) {

		const mount = dc.getElementById('builder-list');

		clearFail();

		const message = el( 'p', 'nino-admin-error builder-list-error', Nino.adminUi.api.errorText( status, response, fallback ) );

		message.setAttribute( 'role', 'alert' );
		mount.insertBefore( message, mount.firstChild );
	};

	/**
	 *	Delete a page of the list: the server is asked what the deletion would keep and leave a link to - and refuses, in the kernel's words, a page that may not go -,
	 *	then one question names all of it, and only a yes takes the page away
	 *
	 *	@param		{Object}		row						The row of the table
	 *	@param		{Element}		opener				The button that was pressed: the focus returns there
	 */
	const askDelete = function( row, opener ) {

		clearFail();

		Nino.admin.builder.call( 'uses', { template : row.template, locale : Nino.admin.workLocale.current }, function( status, asked ) {

			if( status !== 200 ) {
				listFail( status, asked, '/_admin/builder/error/remove' );
				return;
			}

			Nino.adminUi.confirm( 'destructive', {
				title		: word('confirm/delete-page-title'),
				message	: deleteMessage( asked ),
				yes			: word('confirm/delete-page-title'),
				no			: Nino.adminUi.text('/_admin/common/label/cancel'),
				kind		: 'danger',
				opener	: opener,
				onChoose : function( ok ) {

					if( ok === true )
						deletePage( asked );
				},
			} );
		} );
	};

	// The page is taken away: said as a toast, the other panels and the window beside the workbench told, the table drawn again without it
	const deletePage = function( asked ) {

		Nino.admin.builder.call( 'remove', { template : asked.template }, function( status, response ) {

			if( status !== 200 ) {
				listFail( status, response, '/_admin/builder/error/remove' );
				return;
			}

			announce( asked.path === '' ? undefined : asked.path );
			Nino.adminUi.say( word( 'msg/page-deleted', asked.name ) );

			// The row that had the focus is gone: it goes to the search over the table, or - where no page is left - to the button that makes one
			Nino.admin.builder.reload( function() {

				const mount = dc.getElementById('builder-list');
				const next = mount.querySelector('.nino-admin-table-search') ?? N.button;

				if( next !== null && next !== undefined )
					next.focus();
			} );
		} );
	};

	// The buttons at the end of a row of the table, for the account that may make a page: the page is copied - the view of a copy is its address - or deleted, which the two
	// pages the kernel never takes away say they cannot be
	const rowButtons = function( row ) {

		// A page that the website serves itself is no page to copy, and the two it never takes away are no pages to delete
		const copy = Nino.adminUi.iconButton( 'copy', word( 'list/duplicate', row.name ), function() { Nino.admin.builder.go( [ NEW, addressOf( row.template ) ], { origin : [] } ) }, { small : true, disabled : row.locked === 'runtime' } );
		const drop = Nino.adminUi.iconButton( 'trash-2', word( 'list/delete', row.name ), function() { askDelete( row, drop ) }, { small : true, danger : true, disabled : row.locked !== '' } );

		if( row.locked === '' )
			return [ copy, drop ];

		// The reason stands as the title of the shut buttons and behind a (?) at them, which a keyboard and a touch screen reach
		const reason = word( 'list/locked-'+ row.locked );

		drop.title = reason;

		if( row.locked === 'runtime' )
			copy.title = reason;

		return [ copy, Nino.adminUi.fieldHelp( reason, { name : row.name } ), drop ];
	};

	const render = function( data ) {

		closeSheet();

		S.list = data;
		S.level = data.level ?? S.level;

		const mount = dc.getElementById('builder-list');
		const holder = el( 'div', '' );
		const menus = data.navs.length > 0;
		const dated = data.pages.some( function( page ) { return page.saved > 0 } );

		mount.innerHTML = '';
		mount.appendChild( holder );

		// A page is made by the one who may design and may write routes: the list says so, and the action asks again
		if( data.pages.length === 0 )
			holder.appendChild( Nino.adminUi.emptyState( word('msg/none') ) );
		else {

			const columns = [
				{ key : 'name', label : word('page/name'), type : 'string', render : nameCell },
				{ key : 'path', label : word('page/address'), type : 'string' },
				{ key : 'blocks', label : word('list/blocks'), type : 'integer' },
			];

			if( menus === true )
				columns.push( { key : 'menus', label : word('list/menus'), type : 'string' } );

			if( dated === true )
				columns.push( { key : 'saved', label : word('list/saved'), type : 'date', render : function( value, row ) { return row.saved === '' ? '' : dateOf( row.stamp ) } } );

			Nino.adminUi.table( {
				mount			: holder,
				rowKey		: 'template',
				columns		: columns,
				rows			: data.pages.map( function( page ) {
					return { template : page.template, name : page.name, path : page.path, blocks : page.blocks, menus : page.navs.map( menuName ).join(', '), saved : page.saved > 0 ? isoOf( page.saved ) : '', stamp : page.saved, complete : page.complete, locked : page.locked };
				} ),
				actions		: data.create === true ? rowButtons : undefined,
				labels		: { search : word('list/search'), empty : word('msg/none'), noMatch : word('list/no-match') },
				search		: true,
				// The file of the template is a name of the page as well - it is what a person who has seen the files looks for -, and the date is the one the row shows
				searchText	: function( row ) { return row.template+ '.tpl '+ ( row.stamp > 0 ? dateOf( row.stamp ) : '' ) },
				onRowClick	: function( row ) { Nino.admin.builder.go( [ addressOf( row.template ) ], { origin : [] } ) },
			} );
		}

		if( data.frames.length > 0 )
			mount.appendChild( Nino.adminUi.list( data.frames.map( function( frame ) {

				const parts = data.pages.length > 0 ? [ addressOf( data.pages[0].template ), addressOf( frame.template ) ] : [ addressOf( frame.template ) ];

				return {
					key		: frame.key,
					title	: word( 'word/frame-'+ frame.key ),
					detail	: word('hint/frame-short'),
					href	: Nino.admin.router.href( 'builder', parts ),
					onOpen	: function() { Nino.admin.builder.go( parts, { origin : [] } ) },
				};
			} ), { title : word('label/frames'), level : 2 } ) );

		if( data.create === true ) {

			N.button = makeButton('builder-new-page');
			mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : N.button } ).el );
		}
	};

	// --- the panel --------------------------------------------------------------------------------------------------

	Nino.admin.builder = Nino.adminUi.panel( {
		uri		: 'builder',
		mount	: 'builder-list',
		render	: render,
		payload	: function() { return { locale : Nino.admin.workLocale.current } },
		show	: 'always',
		onError	: function() { Nino.adminUi.showLevel( 'builder-list', { focus : false } ) },
		dirty	: {
			isDirty	: isDirty,
			save		: function( done ) { S.view.bar.press( done ) },
			bar			: function() { return S.view === null ? null : S.view.bar.el },
			discard	: dropDraft,
			saving	: function() { return S.saving === true },
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
			slugOf : slugOf, loopOf : loopOf, keyOf : keyOf, applySource : applySource, fallbackText : fallbackText, picturesNow : picturesNow, pictureTargets : pictureTargets, layoutsOf : layoutsOf, freshText : freshText, backOf : backOf, paintBar : paintBar, hintWord : hintWord, api : api, save : save,
			build : build, keyAt : keyAt, atOf : atOf, rowOf : rowOf, outlineItems : outlineItems, openExtended : openExtended, leaveExtended : leaveExtended, penOf : penOf, iconOf : iconOf, activeOf : activeOf, lookOf : lookOf,
			selectBlock : selectBlock, variantOfBlock : variantOfBlock, chooseVariant : chooseVariant, blockVariantField : blockVariantField, tileOf : tileOf, typeTitle : typeTitle, loopElements : loopElements, pageDrawn : pageDrawn, seatAfter : seatAfter, roveTo : roveTo, shownOf : shownOf, rowAction : rowAction, redrawBox : redrawBox, focusWork : focusWork, sortSteps : sortSteps, sortWritten : sortWritten, sortSentence : sortSentence, directionWord : directionWord, sortKind : sortKind, penSpot : penSpot, penZone : penZone, inZone : inZone, pointerAim : pointerAim, carrierOf : carrierOf, seatOf : seatOf, stopsOf : stopsOf, loopStopsOf : loopStopsOf, openProperties : openProperties, readsOf : readsOf, frameCss : frameCss, paintOutline : paintOutline, elementsOfKey : elementsOfKey, keyInPage : keyInPage,
		},

		/**
		 *	The editor, for a panel that works on a sheet of its own - the draft of one block of the library - with the code the page is edited with. Nothing of it is copied: the
		 *	functions are the ones the panel of the pages runs, on the sheet the adapter brings (see openSheet())
		 *
		 *		open( adapter, draft )				the sheet of a block is open and its tree view is drawn into adapter.level; false for a draft with no block
		 *		close()												the sheet is left, the panel of the pages has none
		 *		view( level )									the tree view with the line of the path, the marks of the views, the frame and the bar, drawn again into a level
		 *		properties( at, rel, opener, back )	the lightbox of a component - its source, its content, its look - of the block at an index of the top level
		 *		source( rel, spec, opener )		the dialog that chooses where a value comes from, of the component at a place of the block (spec is what the lightbox reads: { form, kind, attr, written, where })
		 *		change( make, reload )				a step of the draft that Undo takes back; make() changes it
		 *		undo(), redo()								one step back, one step forward
		 *		save( done )									the draft is sent as the adapter's 'save' and the answer is taken
		 *		call( action, payload, done, extra )	a request of the sheet, through the adapter, with the base of its payload
		 *		tile( box, [adapter] )				the picture of a block - a tile - asked for and drawn into box (box.dataset.block is its key); with an adapter, outside of any sheet: its call() asks, its dir is where the picture is drawn
		 *		tileFrame( box, held, [dir] )	the frame of a tile that is asked for already ({ address, query })
		 *		select( rel )									the entry of the tree that is chosen - 'root' or the places of a component, as the tree has them, 0.2.1 -, which opens what is above it
		 *		state()												the model of the sheet, for the tests of the panel
		 */
		editor : {
			open : openSheet, close : closeSheet, view : build, properties : openProperties, source : openSource, change : change, undo : undo, redo : redo, save : save,
			call : api, tile : tileAsk, tileFrame : tileFrame, state : function() { return S }, select : function( rel ) { if( S.sheet === 'block' && S.tree !== null && relNode( rel ) !== null ) chooseRow( rel, false ) },
		},

		go : function( parts, options ) {

			closeSheet();

			const target = targetOf( parts );
			const leaves = ( N.open === true && ( isNew( parts ) === false || sourceOfNew( parts ) !== N.source ) ) || ( S.ready === true && ( parts.length === 0 || target === null || target.template !== S.template || target.page !== S.page ) );

			S.focusNext = ( options ?? {} ).focus ?? '';
			S.focusFrom = ( options ?? {} ).from ?? '';

			Nino.admin.dirty.guard( leaves === true ? [ 'builder' ] : [], function() {
				S.originNext = ( options ?? {} ).origin ?? null;
				Nino.admin.router.go( 'builder', parts );
				Nino.admin.builder.follow();
			} );
		},

		follow : function() {

			// What an action of the list said was wrong belongs to the address it was said at
			clearFail();

			const at = here();
			const parts = at.panel === 'builder' ? at.parts : [];

			if( isNew( parts ) === true ) {
				showNew();
				return;
			}

			// The view that makes a page is left for the list with the focus on the button that opened it
			const from = N.open;

			N.open = false;
			N.source = '';

			if( parts.length === 0 ) {

				leaveView();

				S.ready = false;
				Nino.admin.router.set( 'builder', [] );

				Nino.adminUi.showLevel( 'builder-list', { focus : from === true && N.button !== null && N.button.isConnected === true ? N.button : nameOfPanel() } );

				return;
			}

			const target = targetOf( parts );
			const origin = S.originNext;

			S.originNext = null;

			if( target === null ) {
				Nino.admin.builder.go( [] );
				return;
			}

			// The page that is open is shown again - at another level of the extended view where the address says so -, and any other is opened
			if( target.template === S.template && target.page === S.page && S.ready === true ) {
				show( target );
				return;
			}

			openPage( target, origin, function() {} );
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
			const leaving = ( N.open === true && ( isNew( at.parts ) === false || sourceOfNew( at.parts ) !== N.source ) ) || ( S.ready === true && ( at.parts.length === 0 || there === null || there.template !== S.template || there.page !== S.page ) );

			Nino.admin.router.leave( [ 'builder' ], leaving, proceed, function() {
				Nino.admin.router.set( 'builder', N.open === true ? newParts() : ( S.ready === true ? S.at : [] ) );
			} );
		},
	} );

	Nino.admin.workLocale.follow( 'builder', followLanguage );

	// The choice of a language in the workbench asks every view that follows it about its unsaved input, by the name of the panel: the form that makes a page does not
	// follow it, and is let out of that question for as long as the choice is made - the handler of the shell runs inside this event
	dc.addEventListener( 'change', function( event ) {

		if( event.target !== null && typeof event.target === 'object' && event.target.dataset !== undefined && event.target.dataset.worklocale !== undefined ) {
			N.choosing = true;
			wn.setTimeout( function() { N.choosing = false }, 0 );
		}
	}, true );

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
