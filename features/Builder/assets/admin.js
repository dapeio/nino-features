/**
 *	Nino features
 *	Builder						The panel of the workbench for the pages: the list of them, and for a page the page itself - drawn by the website as a draft, in
 *										a frame - with a bar on each block that moves, copies or removes it, and the texts of the page written where they stand. Everything
 *										it shows is the answer of the API (see Modules\Builder\Admin): the page as it is saved from open, the draft from preview. A change
 *										is a change of the working state - the tree of the template and the words of its namespace - and nothing is written before Save.
 *
 *										The address is #builder (the list), #builder/<page> (a page: its template without page-) and #builder/@header or #builder/@footer
 *										(a frame of the site).
 *
 *										The tree is the one \Nino\Markup reads, kept canonical (a text that follows a text is one node), so that the position of a node in it
 *										is the position the trace of the drawing names. A block is a node on the top level that the register has; free markup and a call
 *										that has no schema are locked, stand where they are and are never copied. Moving a block swaps it with the next block - never past
 *										a locked piece - and the blanks between the nodes stay where they are.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// How many steps the undo goes back, the widths of the preview and the width the page is laid out at for each of them - the desktop is as wide as the pane,
	// which is the page at its own size and not a larger one made smaller
	const MAX_STEPS	= 200;
	const WIDTHS		= [ 'mobile', 'tablet', 'desktop' ];
	const LAYOUT		= { mobile : 390, tablet : 768, desktop : null };

	// Below this width of the preview a person who has chosen no width is shown the page as a phone shows it
	const NARROW		= 520;

	// The words after an id that is a copy of another: hero-2, and the longest id there is
	const MAX_ID		= 64;

	// How long a text typed in the page may be, in characters: the server keeps 20000 bytes of what a format makes of it
	const MAX_TEXT	= 5000;

	// The blanks the server calls a gap
	const BLANKS		= /^[ \t\r\n]*$/;

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
		view			: null,
		width			: 'desktop',
		chosen		: false,
		editing		: null,
		stale			: false,
		force			: false,
		refocus		: null,
		keys			: false,
		place			: null,
		savedTexts	: {},
		copies			: {},
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
	 *	@param		{Array}			nodes
	 *
	 *	@return		{Array}
	 */
	const normalize = function( nodes ) {

		const out = [];

		nodes.forEach( function( node ) {

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
	 *	@param		{Object}		node
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

		return ( node.c ?? [] ).some( function( inner ) {

			if( inner.f !== undefined || ( inner.t !== undefined && BLANKS.test( inner.t ) === false ) )
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
	 *	@param		{number}		at
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

	// The id of a copy: the id of its root with the number after it that is the first free one - a number is no part of the base, hero-2 is hero again
	const freeId = function( id, taken ) {

		const base = id.replace( /-\d+$/, '' );
		const keys = Object.keys( S.texts ).concat( S.slots );

		for( let count = 2; count < 10000; count++ ) {

			const candidate = base+ '-'+ count;
			const prefix = '/template/'+ S.template+ '/'+ candidate+ '/';

			if( candidate.length <= MAX_ID && taken.has( candidate ) === false && keys.some( function( key ) { return key.indexOf( prefix ) === 0 } ) === false )
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
	 *	@param		{Object}		block
	 *	@param		{string}		root					Its id
	 *	@param		{string}		now						The id of the copy
	 *	@param		{Object}		map						(filled) old id => new id
	 *
	 *	@return		{Object}
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

	const snap = function() {
		return { top : clone( S.top ), texts : clone( S.texts ), copies : clone( S.copies ) };
	};

	const stateOf = function() {
		return JSON.stringify( [ S.top, S.texts ] );
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
	 *
	 *	@return		{void}
	 */
	const change = function( make, reload ) {

		const before = snap();

		make();
		S.top = normalize( S.top );

		if( stateOf() === JSON.stringify( [ before.top, before.texts ] ) )
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
		changed( true );
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
	 *	@param		{number}		at
	 *	@param		{number}		direction
	 *
	 *	@return		{boolean}
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
	 *	@param		{number}		at
	 *
	 *	@return		{boolean}
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
	 *	@param		{number}		at
	 *
	 *	@return		{boolean}
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
	const shortened = function( text ) {

		const plain = String( text ).replace( /<[^>]*>/g, ' ' ).replace( /&[a-z#0-9]+;/gi, ' ' ).replace( /\s+/g, ' ' ).trim();

		return plain.length <= 36 ? plain : plain.slice( 0, 36 ).replace( /\s+\S*$/, '' )+ '…';
	};

	/**
	 *	The first text a block shows: the value of its first source that is a text, or a fixed text - in the language of the view - where the page has one
	 *
	 *	@param		{Array}			nodes
	 *	@param		{string}		scope					The id of the nearest call above that has one
	 *
	 *	@return		{string}
	 */
	const firstText = function( nodes, scope ) {

		for( const node of nodes ) {

			if( node.n === undefined )
				continue;

			const known = S.known[node.n];
			const id = idOf( node ) === '' ? scope : idOf( node );

			if( known !== undefined && known.source === 'text' ) {

				const written = typeof node.s === 'string' ? node.s : '';
				const value = typeof ( node.a ?? {} ).text === 'string' ? node.a.text : ( written.charAt(0) === '~' && id !== '' ? textOf( '/template/'+ S.template+ '/'+ id+ '/'+ written.slice( 1 ) ) : '' );

				if( shortened( value ) !== '' )
					return shortened( value );
			}

			const inner = Array.isArray( node.c ) === true ? firstText( node.c, id ) : '';

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
	const titleOf = function( node ) {

		if( kindOf( node ) !== 'block' )
			return node.n === 'template' ? word('word/frame') : ( kindOf( node ) === 'call' ? word( 'word/call', node.n ) : word('word/free') );

		// A header and a footer are what they are called: the first words in them are a button or a link
		const text = node.n === 'header-wrap' || node.n === 'footer-wrap' ? '' : firstText( [ node ], '' );
		const label = text !== '' ? text : ( node.n === 'header-wrap' || node.n === 'footer-wrap' ? '' : idOf( node ) );

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
	 *	@param		{number}		at
	 *	@param		{Element}		opener
	 *	@param		{string}		from					Where the action was started: outline or frame
	 *
	 *	@return		{void}
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

	// The outline in the column of the split view: every block with its bar, the frame and the free markup as the lines they are
	const outline = function() {

		const list = el( 'ol', 'builder-outline' );

		items().forEach( function( item ) {

			const row = el( 'li', 'builder-row builder-row--'+ item.kind );
			const label = el( 'span', 'builder-row-name', titleOf( item.node ) );

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

			list.appendChild( row );
		} );

		return list;
	};

	// --- the frame: bars over the blocks, the texts that are written where they stand ------------------------------

	const FRAME_CSS = '[data-nino-text]{cursor:text;outline:2px dashed transparent;outline-offset:2px}'
		+ '[data-nino-text]:hover,[data-nino-text]:focus{outline-color:#2f6fed}'
		+ '[data-nino-text][contenteditable]{outline:2px solid #2f6fed;background:rgba(47,111,237,.08)}'
		+ '[data-nino-placeholder]:not([contenteditable]){opacity:.6;font-style:italic}'
		+ '[data-nino-locked]{cursor:not-allowed}';

	// The bars stand in the shadow of one element at the end of the page: nothing of the website's style reaches them and they reach nothing of its
	const BAR_CSS = ':host{all:initial;position:absolute;top:0;left:0;width:0;height:0;z-index:2147483000}'
		+ '.bar{position:absolute;width:max-content;display:flex;flex-wrap:wrap;gap:4px;align-items:center;box-sizing:border-box;max-width:calc(100vw - 16px);padding:4px 6px;border-radius:6px;background:#1b2330;color:#fff;font:600 13px/1.2 system-ui,sans-serif;box-shadow:0 1px 4px rgba(0,0,0,.4)}'
		+ '.name{flex:1 1 auto;min-width:0;padding:0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
		+ 'button{box-sizing:border-box;min-width:28px;min-height:28px;margin:0;padding:0 6px;border:1px solid #6b7d99;border-radius:4px;background:#2c3a4f;color:#fff;font:600 14px/1 system-ui,sans-serif;cursor:pointer}'
		+ 'button:hover{background:#3d4f6b}button:focus-visible{outline:3px solid #ffd54a;outline-offset:1px}button:disabled{opacity:.55;cursor:default}'
		+ '.frame{position:absolute;pointer-events:none;border:1px dashed rgba(47,111,237,.7);box-sizing:border-box}';

	/**
	 *	The blocks and the texts of the page in the frame, from the comments the trace of the drawing writes: a block is the elements between the comment
	 *	that opens a call of the top level of this template and the one that ends it, a text is the element that holds a value between the comments of its source
	 *
	 *	@param		{Document}	doc
	 *
	 *	@return		{Object}									{ blocks : [ { at, elements } ], texts : [ { element, path, written } ] }
	 */
	const traced = function( doc ) {

		const found = { blocks : [], texts : [] };
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

				if( closed !== undefined && closed.path.indexOf( prefix ) === 0 && /^\d+$/.test( closed.path.slice( prefix.length ) ) === true ) {

					const elements = [];

					for( let sibling = closed.start.nextSibling; sibling !== null && sibling !== comment; sibling = sibling.nextSibling )
						if( sibling.nodeType === 1 )
							elements.push( sibling );

					found.blocks.push( { at : Number( closed.path.slice( prefix.length ) ), elements : elements } );
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
	 *	Settle a text that was written: a value that is the one the element showed when the writing began is no change - a click is no step of the undo - and
	 *	any other is the text of the key in the language of the view, or the fixed text of the call
	 *
	 *	@param		{Object}		entry					The source of the page
	 *	@param		{string}		path					Where the call is in the tree
	 *	@param		{string}		value
	 *	@param		{string}		initial
	 *
	 *	@return		{boolean}									Whether it was a change
	 */
	const settle = function( entry, path, value, initial ) {

		if( value === initial )
			return false;

		if( entry.kind === 'fixed' ) {

			const node = nodeAt( path );

			if( node === null || node.a === undefined || node.a.text === value )
				return false;

			change( function() { nodeAt( path ).a.text = value }, false );

			return true;
		}

		// A text that is emptied is drawn as the website would draw it, which the page in the frame is asked for; any other stands there as it was typed
		change( function() {
			S.texts[entry.key] = Object.assign( {}, S.texts[entry.key] ?? {}, { [S.locale] : value } );
		}, value.trim() === '' );

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
	 *	Write a text where it stands: the element of the frame is made editable - as plain text where the key is a plain one, else as it is - and what is
	 *	typed is the new text of the key in the language of the view. Enter, Tab and Escape end it (Enter in a text of lines or of blocks is a line),
	 *	and Escape takes it back. A text that the language of the view has none of shows the one of another language: the writing begins with nothing
	 *
	 *	@param		{Object}		text					{ element, path, written }
	 *	@param		{Object}		entry					The source of the page this text is
	 *
	 *	@return		{void}
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
	 *	@param		{HTMLIFrameElement}		frame
	 *	@param		{Array}					blocks
	 *
	 *	@return		{void}
	 */
	const bars = function( frame, blocks ) {

		const doc = frame.contentDocument;
		const win = frame.contentWindow;
		const host = doc.createElement('nino-builder-bars');
		const shadow = host.attachShadow( { mode : 'open' } );
		const style = doc.createElement('style');
		const placed = [];

		// On a page that is laid out as wide as a tablet the words of the bar are written out, on a phone they are the glyphs, with their names
		const wide = win.innerWidth >= 768;

		style.textContent = BAR_CSS;
		shadow.appendChild( style );
		host.setAttribute( 'role', 'region' );
		host.setAttribute( 'aria-label', word('label/bars') );

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

			shadow.appendChild( frameBox );
			shadow.appendChild( bar );
			placed.push( { block : block, bar : bar, box : frameBox } );
		} );

		// The page is laid out at a width and scaled into the pane where the person chose a width the pane has not: a bar is scaled back, so that it is as large
		// as the workbench's own controls. It stands in the top corner on the right, inside the block, where a block has the room; one that is low - a header - has it
		// below its edge, where it covers the top of the next, and never the logo or the menu
		const place = function() {

			const scale = win.innerWidth > 0 ? frame.getBoundingClientRect().width / win.innerWidth : 1;
			const back = scale > 0 && scale < 1 ? 1 / scale : 1;

			placed.forEach( function( item ) {

				const box = boxOf( item.block.elements, win );

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

			S.refocus = null;

			const target = shadow.querySelector( 'button[data-at="'+ wanted.at+ '"][data-action="'+ wanted.action+ '"]:not(:disabled)' ) ?? shadow.querySelector( 'button[data-at="'+ wanted.at+ '"]:not(:disabled)' );

			if( target !== null )
				target.focus();
		}
	};

	/**
	 *	What a page that was loaded in the frame becomes: its texts can be written and its blocks have their bars. Only a page that is the draft of the last
	 *	answer is touched - the page as it was before the first answer, or one the person navigated to, has no trace and is left as it is
	 *
	 *	@param		{HTMLIFrameElement}		frame
	 *
	 *	@return		{void}
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

		// The page is a preview: nothing in it goes anywhere or is sent, and the keys of the page are the texts and the bars first
		doc.querySelectorAll('a, button, input, select, textarea, summary, [tabindex]').forEach( function( element ) {

			if( element.hasAttribute('data-nino-text') === false )
				element.tabIndex = -1;
		} );
		doc.addEventListener( 'click', function( event ) {

			if( event.target.closest && event.target.closest('a') !== null )
				event.preventDefault();
		}, true );
		doc.addEventListener( 'submit', function( event ) { event.preventDefault() }, true );
		doc.addEventListener( 'keydown', function( event ) { keys( event, found.blocks, doc ) } );

		bars( frame, found.blocks );
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

		view.bar.set( { dirty : isDirty(), restorable : S.last > 0 } );
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

	const api = function( action, payload, done ) {
		Nino.admin.builder.call( action, Object.assign( { template : S.template }, payload ), done );
	};

	// What the server answers after a save or a restore, and an open: the page as it stands now
	const take = function( response ) {

		S.top = normalize( response.tree );
		S.texts = response.texts;
		S.known = response.known;
		S.stamp = response.stamp;
		S.last = response.last ?? 0;
		S.sources = response.sources;
		S.pending = response.sources;
		S.saved = stateOf();
		S.savedTexts = clone( S.texts );
		S.copies = {};

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
		api( 'preview', { tree : S.top, texts : S.texts, slots : S.copies, token : token }, function( status, response ) {

			if( status === 200 ) {
				S.pending = response.sources;
				S.address = response.address;
			}

			done( status, response );
		} );
	};

	const save = function( done ) {

		const forced = S.force;

		S.force = false;
		endEdit( true );

		api( 'save', { tree : S.top, texts : changedTexts(), slots : S.copies, base : S.stamp, force : forced }, function( status, response ) {

			if( status === 200 ) {

				take( response );
				S.view.bar.line.saved();
				S.view.preview.schedule();
				done( true );
				paint();
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
					onReload		: function() { openPage( S.template, function() {} ) },
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

		Nino.adminUi.confirm( 'destructive', {
			title		: word('confirm/restore-title'),
			message	: word('confirm/restore-message'),
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
					onReload		: function() { openPage( S.template, function() {} ) },
					onCancel		: function() {},
				} );
				return;
			}

			S.view.bar.line.error( status, response );
		} );
	};

	// The name of a language in that language - Deutsch, English - and its code behind it; the code alone where the browser knows no name
	const languageName = function( locale ) {

		const code = locale.split('_')[0];
		let name = '';

		try {
			name = new Intl.DisplayNames( [ code ], { type : 'language' } ).of( code ) ?? '';
		} catch( error ) {
			name = '';
		}

		return name === '' || name === code ? locale : name.charAt( 0 ).toUpperCase()+ name.slice( 1 )+ ' ('+ locale+ ')';
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
	const tools = function() {

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

		const outlineToggle = Nino.adminUi.button( word('label/outline'), function() {

			const root = S.view.split.el;
			const open = root.dataset.outline !== 'open';

			root.dataset.outline = open === true ? 'open' : 'closed';
			outlineToggle.setAttribute( 'aria-expanded', String( open ) );
		}, { className : 'nino-admin-btn--small builder-outline-toggle', id : 'builder-outline-toggle' } );

		outlineToggle.setAttribute( 'aria-expanded', 'false' );
		box.appendChild( outlineToggle );

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

	const build = function( level ) {

		level.innerHTML = '';

		const back = el( 'a', 'nino-admin-back-link', word('label/back-list') );

		back.href = Nino.admin.router.href( 'builder', [] );
		back.id = 'builder-back-list';
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.builder.go( [] );
		} );

		const head = el( 'div', 'builder-head' );
		const title = el( 'h3', 'builder-name', S.name );
		const toolbox = tools();
		const settings = el( 'div', 'builder-column' );
		const buttons = {};

		const bar = Nino.adminUi.saveBar( {
			id			: 'builder-bar',
			buttons	: [
				{ key : 'undo', label : '\u21B6 '+ word('label/undo'), name : word('label/undo'), onPress : undo },
				{ key : 'redo', label : '\u21B7 '+ word('label/redo'), name : word('label/redo'), onPress : redo },
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
			preview		: { src : 'about:blank', title : word('label/preview-title'), width : LAYOUT[S.width] ?? undefined },
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
			widths	: LAYOUT,
			address	: function( query ) { return S.dir+ S.address+ ( S.address.indexOf('?') === -1 ? '?' : '&' )+ query },
			onError	: function( status, response ) { bar.line.error( status, response ) },
		} );

		if( typeof wn.ResizeObserver === 'function' )
			new wn.ResizeObserver( function() { if( S.place !== null ) S.place() } ).observe( split.frame.parentNode );

		split.el.dataset.outline = 'closed';
		S.view = { level : level, split : split, preview : preview, bar : bar, buttons : buttons, title : title };

		split.words( { settings : word('label/blocks'), preview : word('label/preview') } );
		head.appendChild( title );

		// The frames of the site stand as a slim line over and under the page, as they do in the page: they hold for every page and are written in a page of their own
		const frameRow = function( part, label ) {

			const row = el( 'div', 'builder-frame-row' );
			const link = el( 'a', 'builder-frame-link', word('label/edit') );

			link.href = Nino.admin.router.href( 'builder', [ part ] );
			link.setAttribute( 'aria-label', word('label/edit')+ ': '+ label );
			link.addEventListener( 'click', function( event ) {
				event.preventDefault();
				Nino.admin.builder.go( [ part ] );
			} );
			row.appendChild( el( 'span', 'builder-frame-name', label ) );
			row.appendChild( link );

			return row;
		};

		if( S.frame === true )
			head.appendChild( el( 'p', 'nino-admin-hint', word('hint/frame') ) );

		level.appendChild( Nino.adminUi.contextBar( back ) );
		level.appendChild( head );

		if( S.frame === false )
			level.appendChild( frameRow( '@header', word('word/frame-row-header') ) );

		level.appendChild( split.el );

		if( S.frame === false )
			level.appendChild( frameRow( '@footer', word('word/frame-row-footer') ) );

		level.appendChild( bar.el );

		Nino.admin.dirty.register( 'builder', bar.entry( { discard : function() { discard() } } ) );
		paint();
		preview.flush();
		preview.width( S.width );
	};

	// The work is dropped: the page as it is saved
	const discard = function() {

		if( S.ready === false )
			return;

		const was = JSON.parse( S.saved );

		S.top = was[0];
		S.texts = was[1];
		S.undo = [];
		S.redo = [];
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
	 *	@param		{string}		template
	 *	@param		{Function}	done					Called with whether it was opened
	 *
	 *	@return		{void}
	 */
	const openPage = function( template, done ) {

		if( S.view !== null )
			S.view.preview.stop();

		S.ready = false;
		S.template = template;

		Nino.admin.builder.call( 'open', { template : template }, function( status, response ) {

			const level = dc.getElementById('builder-page');

			if( status !== 200 ) {
				Nino.admin.builder.fail( level, status, response );
				Nino.adminUi.showLevel( 'builder-page', { focus : false } );
				done( false );
				return;
			}

			S.frame = response.frame === true;
			S.dir = response.dir;
			S.address = response.address;
			S.locale = response.contentLocale;
			S.locales = response.locales;
			S.undo = [];
			S.redo = [];
			S.name = nameOfPage( template );
			take( response );
			S.ready = true;
			S.stale = true;
			build( level );
			Nino.adminUi.showLevel( 'builder-page', { focus : false } );
			done( true );
		} );
	};

	const nameOfPage = function( template ) {

		const page = S.list.pages.find( function( candidate ) { return candidate.template === template } );

		if( page !== undefined )
			return page.name;

		return template === 'frame-header' ? word('word/frame-header') : ( template === 'frame-footer' ? word('word/frame-footer') : template );
	};

	// --- the list ---------------------------------------------------------------------------------------------------

	const rowOf = function( template, name, hint, chips ) {

		const row = el( 'li', 'builder-page-row' );
		const link = el( 'a', 'builder-page-link' );

		link.href = Nino.admin.router.href( 'builder', [ addressOf( template ) ] );
		link.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.builder.go( [ addressOf( template ) ] );
		} );
		link.appendChild( el( 'strong', 'builder-page-name', name ) );
		link.appendChild( el( 'span', 'nino-admin-hint', hint ) );
		chips.forEach( function( chip ) { link.appendChild( el( 'span', 'nino-admin-badge', chip ) ) } );
		row.appendChild( link );

		return row;
	};

	const render = function( data ) {

		S.list = data;

		const mount = dc.getElementById('builder-list');
		const pages = el( 'ul', 'builder-pages' );
		const frames = el( 'ul', 'builder-pages' );

		mount.innerHTML = '';

		data.pages.forEach( function( page ) { pages.appendChild( rowOf( page.template, page.name, page.path, page.locales ) ) } );
		data.frames.forEach( function( frame ) { frames.appendChild( rowOf( frame.template, word( 'word/frame-'+ frame.key ), word('hint/frame-short'), [] ) ) } );

		mount.appendChild( el( 'h3', 'builder-heading', word('label/pages') ) );

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
			items : items, titleOf : titleOf, settle : settle, free : free, possible : possible,
		},

		go : function( parts ) {

			const leaves = S.ready === true && ( parts.length === 0 || templateOf( parts[0] ) !== S.template );

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

			const template = templateOf( parts[0] );

			if( template === '' ) {
				Nino.admin.builder.go( [] );
				return;
			}

			if( template === S.template && S.ready === true ) {
				Nino.adminUi.showLevel( 'builder-page', { focus : false } );
				return;
			}

			openPage( template, function() {} );
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

			const leaving = S.ready === true && ( at.parts.length === 0 || templateOf( at.parts[0] ) !== S.template );

			Nino.admin.router.leave( [ 'builder' ], leaving, proceed, function() {
				Nino.admin.router.set( 'builder', S.ready === true ? [ addressOf( S.template ) ] : [] );
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
