/**
 *	Nino features
 *	builder-js-smoke.js	What the script of the panel does with a page that is not drawn: its model - the blocks of a tree, the move that never goes
 *											past a locked piece, the copy with the ids of the rule and its words, the steps of the undo - and what it says:
 *
 *											A. the words: every key the script asks for is a fill of both interface languages, and none is written into it
 *											B. the model: a move, a copy, a removal, a text, and twenty mixed steps there and back to the tree they began with; a block that is put
 *											   in - its ids by the rule, its words in the languages of the site, the places where it can be put in, its undo
 *
 *											The script runs in a context of its own with the few names of the workbench it touches while it is loaded; the screen it draws
 *											is looked at in a browser. builder-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Builder/tests/builder-js-smoke.js
 *	       NINO_ROOT=../nino node features/Builder/tests/builder-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const script = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
/**
 *	Every question of a script of the feature is asked with a title: each call of confirm(), choiceDialog() and formDialog() in the files names one, and
 *	the one of confirm() and choiceDialog() is a word of the feature or of the workbench - a call, or a literal - and no variable that could be empty
 */
const dialogsWithoutTitle = function( sources ) {

	const bad = [];

	sources.forEach( function( file ) {

		const text = fs.readFileSync( file, 'utf8' );
		const calls = /Nino\.adminUi\.(confirm|choiceDialog|formDialog)\(/g;
		let found;

		while( ( found = calls.exec( text ) ) !== null ) {

			const open = text.indexOf( '{', found.index );
			let depth = 0;
			let top = '';
			let quote = '';

			for( let at = open; at < text.length; at++ ) {

				const char = text[at];

				if( quote !== '' ) {
					if( char === '\\' )
						at++;
					else if( char === quote )
						quote = '';
					continue;
				}

				if( char === "'" || char === '"' || char === '`' )
					quote = char;
				else if( char === '{' )
					depth++;
				else if( char === '}' && --depth === 0 )
					break;

				if( depth === 1 )
					top += char;
			}

			const title = /(?:^|[{,\s])title\s*:\s*(\S[^\n]*)/.exec( top );
			const word = found[1] === 'formDialog' ? /./ : /^(?:word\(|Nino\.adminUi\.text\(|Nino\.content\.getText\(|'|")/;

			if( title === null || word.test( title[1] ) === false )
				bad.push( path.basename( file )+ ':'+ text.slice( 0, found.index ).split('\n').length );
		}
	} );

	return bad;
};

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};

// --- A. the words ---------------------------------------------------------------------------------------------

{
	const asked = new Set();
	let match;
	const direct = /word\(\s*'([a-z-]+\/[a-z0-9-]+)'/g;
	const named = /'\/_admin\/builder\/([a-z-]+\/[a-z0-9-]+)'/g;

	while( ( match = direct.exec( script ) ) !== null )
		asked.add( match[1] );

	while( ( match = named.exec( script ) ) !== null )
		asked.add( match[1] );

	// A word chosen between two - word( picture ? 'source/a' : 'source/b' ) - is read as the literal it is
	const either = /'((?:own|page|picture|source|insert|confirm|hint|msg|field)\/[a-z0-9-]+)'/g;

	while( ( match = either.exec( script ) ) !== null )
		asked.add( match[1] );

	// The keys the script puts together: the four things done to a block, the three widths, the two frames
	[ 'up', 'down', 'copy', 'remove' ].forEach( function( key ) { asked.add( 'label/'+ key ) } );
	[ 'remove' ].forEach( function( key ) { asked.add( 'label/'+ key+ '-short' ) } );
	[ 'section-wrap', 'header-wrap', 'footer-wrap' ].forEach( function( key ) { asked.add( 'kind/'+ key ) } );
	[ 'mobile', 'tablet', 'desktop', 'frame-header', 'frame-footer' ].forEach( function( key ) { asked.add( 'word/'+ key ) } );
	// The places of the dialog: the categories and the two ways a place is named
	[ 'all', 'intro', 'content', 'dynamic', 'trust', 'offer', 'action', 'contact' ].forEach( function( key ) { asked.add( 'insert/cat-'+ key ) } );
	[ 'before', 'after' ].forEach( function( key ) { asked.add( 'label/insert-'+ key ) } );
	// The words of the extended view that are put together: what the register has against a place and why a component is grey, the three rows of a width, the
	// two variants that have a word, what a field says of the id and the picture - and the three steps of the roles tab, which the kernel reads
	[ 'unknown', 'variant', 'outside', 'foreign', 'place', 'once', 'self', 'depth', 'duplicate', 'nofield' ].forEach( function( key ) { asked.add( 'finding/'+ key ) } );
	[ 'once', 'depth', 'self', 'place' ].forEach( function( key ) { asked.add( 'offer/why-'+ key ) } );
	[ 'section', 'card', 'general', 'features', 'groups', 'loops' ].forEach( function( key ) { asked.add( 'offer/sec-'+ key ) } );
	[ 'all', 'tablet', 'desktop' ].forEach( function( key ) { asked.add( 'rows/'+ key ) } );
	[ 'shows-on', 'shows-off' ].forEach( function( key ) { asked.add( 'rows/'+ key ) } );
	asked.add('variant/regular');
	// The id of a section in its bar: what is wrong with a new one
	[ 'form', 'long', 'taken', 'why-frame', 'why-block', 'why-content', 'why-locked' ].forEach( function( key ) { asked.add( 'ident/'+ key ) } );
	asked.add('field/read-id');
	// What the panel says of itself in PHP: the pane's tabs (the pages, the header and the footer) and the name of the feature at the right
	[ 'tab/pages', 'tab/frames', 'scope/name' ].forEach( function( key ) { asked.add( key ) } );
	[ 'pick', 'new', 'fixed', 'page', 'address' ].forEach( function( key ) { asked.add( 'source/tab-'+ key ) } );
	// Where a text is from, the technical fields of an element and the menus of the starter site
	[ 'company', 'website', 'mail', 'project', 'common', 'page', 'feature', 'module', 'place' ].forEach( function( key ) { asked.add( 'source/area-'+ key ) } );
	[ 'id', 'uri', 'slug' ].forEach( function( key ) { asked.add( 'source/field-'+ key ) } );
	[ 'text', 'image', 'link' ].forEach( function( key ) { asked.add( 'source/label-'+ key ) } );
	[ 'asc', 'desc' ].forEach( function( way ) { [ 'text', 'date', 'number' ].forEach( function( kind ) { asked.add( 'sort/'+ way+ '-'+ kind ) } ) } );
	[ 'main', 'footer', 'legal' ].forEach( function( key ) { asked.add( 'page/menu-'+ key ) } );
	// The lengths a sample of a place in a block of the library has
	[ 'short', 'line', 'paragraph', 'long', 'own' ].forEach( function( key ) { asked.add( 'place/len-'+ key ) } );
	[ '1', 'n' ].forEach( function( key ) { asked.add( 'place/words-'+ key ) } );
	// The own picture of a place: what it says of it and the three things done to it
	[ 'none', 'has', 'chosen', 'taken', 'upload', 'replace', 'remove' ].forEach( function( key ) { asked.add( 'place/own-'+ key ) } );
	// A page that has lost the call of its head or of its foot, and the way to put it back
	[ 'header', 'footer' ].forEach( function( key ) { [ 'missing-', 'restore-', 'restored-' ].forEach( function( kind ) { asked.add( 'frame/'+ kind+ key ) } ) } );
	// The two pages whose button to delete is shut, each with its reason, and the word the server puts behind the name of a copy in the language of each name
	[ 'home', 'not-found', 'runtime' ].forEach( function( key ) { asked.add( 'list/locked-'+ key ) } );
	asked.add('new/copy');
	[ 'header', 'footer' ].forEach( function( key ) { asked.add( 'frame/'+ key ) } );
	[ 'content', 'design', 'content-edit', 'design-edit' ].forEach( function( key ) { asked.add( 'scope/'+ key ) } );
	// The failure of a load is the one the shell's panel() asks for by the name of the panel
	asked.add('error/load');
	Array.from( asked ).filter( function( key ) { return key.slice( -1 ) === '-' } ).forEach( function( key ) { asked.delete( key ) } );

	const missing = Array.from( asked ).filter( function( key ) { return words.en['/_admin/builder/'+ key] === undefined || words.de['/_admin/builder/'+ key] === undefined } );

	check( 'A. every word the script asks for - '+ asked.size+ ' of them - is a fill of the panel in both interface languages', missing.length === 0 && asked.size > 20 );
	check( 'A. every question the panel asks has a title, a word of the panel or of the workbench', dialogsWithoutTitle( [ path.join( featureDir, 'assets/admin.js' ) ] ).length === 0, dialogsWithoutTitle( [ path.join( featureDir, 'assets/admin.js' ) ] ).join() );
	check( 'A. a dialog of the panel takes its width from the size the workbench gives it: no call names a class of a dialog, and the stylesheet gives no dialog a width', /nino-admin-dialog--/.test( script ) === false && /dialog[^{}]*\{[^}]*\bwidth\s*:/.test( fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' ) ) === false );
	check( 'A. no fill of the panel is left without a reader, the same fills stand in both languages and a placeholder is the same in both', Object.keys( words.en ).filter( function( key ) {
		return key.indexOf('/_admin/builder/') === 0 && key.split('/').length === 5;
	} ).every( function( key ) {
		return asked.has( key.slice( '/_admin/builder/'.length ) ) === true && words.de[key] !== undefined && ( words.en[key].match( /%[sdn]/g ) ?? [] ).join() === ( words.de[key].match( /%[sdn]/g ) ?? [] ).join();
	} ) );
	check( 'A. no sentence is written into the script as markup: a text is set with word() and a name with textContent', /innerHTML\s*=\s*[^'\s]/.test( script.replace( /\.innerHTML\s*=\s*(?:'';|editing\.was;)/g, '' ).replace( /parent\.innerHTML = '';/g, '' ) ) === false && /insertAdjacentHTML|document\.write|outerHTML\s*=/.test( script ) === false );
}

// --- B. the model ---------------------------------------------------------------------------------------------

const context = {
	window : null,
	document : { addEventListener : function() {}, documentElement : { lang : 'en-US' }, getElementById : function() { return { innerHTML : '', appendChild : function() {}, querySelector : function() { return null } } } },
};

// What the stand-in keeps of the list and of the way between the views: the elements made, where the router went and the calls to the server
const made = [];
const went = { parts : [], calls : [] };

// What the stand-in of the workbench was asked by the language: the views that follow it, the questions of the unsaved input and the answer to give, the languages that were set
const followers = [];
const questions = [];
const chosen = [];
const written = [];
let answer = 'save';

context.window = { setTimeout : function() {}, Nino : { admin : { router : { href : function( panel, parts ) { return '#'+ panel+ '/'+ parts.join('/') }, go : function( panel, parts ) { went.parts = parts }, set : function() {}, current : function() { return { panel : 'builder', parts : went.parts } } }, dirty : { guard : function( keys, run, cancel ) { questions.push( keys.join() ); answer === 'cancel' ? cancel() : run() } }, workLocale : { current : 'de_DE', follow : function( name, handler ) { followers.push( { name : name, handler : handler } ) }, set : function( locale ) { chosen.push( locale ) } }, sessionLocale : { set current( locale ) { written.push( locale ) } } }, adminUi : { panel : function( spec ) { return spec }, format : function( text ) { return text }, scaleFrame : function() {}, iconButton : function() { return { dataset : {}, setAttribute : function() {}, classList : { toggle : function() {} }, addEventListener : function() {} } }, hint : function() { return {} }, emptyState : function() { return {} }, button : function() { return { dataset : {}, addEventListener : function() {}, classList : { add : function() {} }, setAttribute : function() {}, appendChild : function() {}, disabled : false } }, el : function( tag, className, text ) { const attributes = {}; const listeners = {}; const element = { tag : tag, className : className, text : text, attributes : attributes, listeners : listeners, dataset : {}, addEventListener : function( type, run ) { listeners[type] = run }, classList : { add : function() {} }, setAttribute : function( name, value ) { attributes[name] = value }, appendChild : function() {} }; made.push( element ); return element } }, content : { getText : function() { return '' } } }, document : context.document };
context.Nino = context.window.Nino;
vm.createContext( context );
vm.runInContext( script, context );

const builder = context.window.Nino.admin.builder.model;
const panel = context.window.Nino.admin.builder;
const S = builder.state;

const section = function( id, title ) {
	return { n : 'section-wrap', s : '', a : { id : id }, c : [ { t : '\n\t' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n\t' }, { n : 'section-col', s : '', a : { id : id+ '-1' }, c : [ { t : '\n' }, { n : 'text', s : '~text', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' } ] };
};

const fresh = function() {

	S.template = 'page-home';
	S.known = { 'section-wrap' : { label : 'Section', id : 'id', source : 'none' }, 'section-col' : { label : 'Column', id : 'id', source : 'none' }, 'section-title' : { label : 'Title', id : '', source : 'text' }, text : { label : 'Text', id : '', source : 'text' } };
	S.top = [ { t : '<!-- note -->\n' }, { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' }, section( 'hero' ), { t : '\n' }, section( 'start' ), { t : '\n' }, { n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' } ];
	S.texts = { '/template/page-home/hero/title' : { de_DE : 'Hallo', en_US : 'Hello' }, '/template/page-home/hero-1/text' : { de_DE : 'Eins', en_US : 'One' }, '/template/page-home/start/title' : { de_DE : 'Start', en_US : 'Start' } };
	S.slots = [ '/template/page-home/hero/background' ];
	S.copies = {};
	S.undo = [];
	S.redo = [];
	S.frame = false;
	S.level = 2;
	S.locale = 'de_DE';
	S.ready = true;
	S.pictures = {};
	S.saved = JSON.stringify( [ S.top, S.texts, S.pictures ] );
};

fresh();

const blocks = function() {
	return S.top.map( function( node, at ) { return builder.kindOf( node ) === 'block' ? at : -1 } ).filter( function( at ) { return at !== -1 } );
};
const idsOf = function() {
	return blocks().map( function( at ) { return S.top[at].a.id } );
};

const [ first, second ] = blocks();

check( 'B. a note, the call of the head and the call of the foot are locked pieces and the other nodes of the top level are blocks and gaps', S.top.map( function( node ) { return builder.kindOf( node ) } ).join() === 'free,call,gap,block,gap,block,gap,call,gap' );
check( 'B. a block moves to the place of the next block, the blanks stay where they are, and not past a locked piece: the first has no way up and the last none down',
	builder.neighbour( first, -1 ) === -1 && builder.neighbour( second, 1 ) === -1 && builder.neighbour( first, 1 ) === second && builder.move( first, 1 ) === true && idsOf().join() === 'start,hero' && S.top.map( function( node ) { return builder.kindOf( node ) } ).join() === 'free,call,gap,block,gap,block,gap,call,gap' );
check( 'B. a move is one step of the undo and one of the redo, and the tree is the one it was after both', ( function() {
	const moved = JSON.stringify( S.top );
	builder.undo();
	const undone = idsOf().join() === 'hero,start';
	builder.redo();
	return undone === true && JSON.stringify( S.top ) === moved && S.undo.length === 1;
} )() );

fresh();

check( 'B. a copy has the first free id of its name and what is inside begins with it - hero-2 and hero-2-1 -, stands behind the block and has its words in every language', ( function() {
	const [ at ] = blocks();
	const ok = builder.duplicate( at );
	return ok === true && idsOf().join() === 'hero,hero-2,start' && S.top[at + 2].c[3].a.id === 'hero-2-1' && S.texts['/template/page-home/hero-2/title'].en_US === 'Hello' && S.texts['/template/page-home/hero-2-1/text'].de_DE === 'Eins';
} )() );
check( 'B. a second copy takes the next id, and the id of a copy is no base for one: hero-2 copied is hero-3', ( function() {
	builder.duplicate( blocks()[1] );
	return idsOf().join() === 'hero,hero-2,hero-3,start';
} )() );
check( 'B. a block is taken out with the blanks in front of it, or behind it where it is the first, and the nodes that stay are still a tree with no two texts in a row', ( function() {
	fresh();
	const [ at ] = blocks();
	builder.remove( at );
	return idsOf().join() === 'start' && S.top.filter( function( node, index ) { return node.t !== undefined && S.top[index + 1] !== undefined && S.top[index + 1].t !== undefined } ).length === 0;
} )() );
check( 'B. a locked piece is moved, copied and removed by nobody', ( function() {
	fresh();
	const before = JSON.stringify( S.top );
	return builder.remove( 1 ) === false && builder.duplicate( 1 ) === false && builder.move( 1, 1 ) === false && JSON.stringify( S.top ) === before;
} )() );
check( 'B. twenty mixed steps are undone to the tree they began with, to the byte, and done again to the tree they ended with', ( function() {
	fresh();
	const begin = JSON.stringify( [ S.top, S.texts ] );
	const sequence = [ function() { builder.duplicate( blocks()[0] ) }, function() { builder.move( blocks()[0], 1 ) }, function() { builder.duplicate( blocks()[2] ) }, function() { builder.remove( blocks()[1] ) } ];
	let count = 0;

	while( S.undo.length < 20 && count++ < 200 )
		sequence[count % sequence.length]();

	const end = JSON.stringify( [ S.top, S.texts ] );
	const steps = S.undo.length;

	while( S.undo.length > 0 )
		builder.undo();

	const back = JSON.stringify( [ S.top, S.texts ] );

	while( S.redo.length > 0 )
		builder.redo();

	return steps === 20 && back === begin && JSON.stringify( [ S.top, S.texts ] ) === end && builder.isDirty() === true;
} )() );
check( 'B. a fresh page is not changed, a change is, and the undo goes back no further than 200 steps', ( function() {
	fresh();
	const clean = builder.isDirty() === false;

	for( let count = 0; count < 260; count++ )
		builder.duplicate( blocks()[0] );

	return clean === true && S.undo.length <= 200;
} )() );
check( 'B. the address of a page is its template without page-, a frame has the name of its side but is no address of this panel, and a name that is none is no template', builder.addressOf( 'page-home' ) === 'home' && builder.addressOf( 'frame-footer' ) === '@footer' && builder.templateOf( '@header' ) === '' && builder.templateOf( 'home' ) === 'page-home' && builder.templateOf( '../x' ) === '' );

check( 'B. a block is named by its kind and the first text it shows - in the language of the view -, the call of a frame is a line of its own, and the locked pieces that stand one after the other are one row', ( function() {
	fresh();
	S.top.splice( 7, 0, { t : '<article>x</article>' }, { n : 'legal', s : '', a : {}, c : null }, { t : '\n' } );
	const rows = builder.items();
	return builder.titleOf( S.top[3] ) === 'Section \u00B7 Hallo' && builder.titleOf( S.top[5] ) === 'Section \u00B7 Start' && rows.map( function( row ) { return row.kind } ).join() === 'frame,block,block,free,frame';
} )() );
check( 'B. a block that holds free markup is locked like a piece of the top level: it is not moved, copied or taken out, and no block is moved past it', ( function() {
	fresh();
	S.top[3].c.splice( 1, 0, { t : '<form>x</form>' } );
	const before = JSON.stringify( S.top );
	return builder.free( S.top[3] ) === false && builder.move( 3, 1 ) === false && builder.duplicate( 3 ) === false && builder.remove( 3 ) === false && builder.move( 5, -1 ) === false && JSON.stringify( S.top ) === before && builder.possible( 3, 'up' ) === false && builder.possible( 5, 'copy' ) === true;
} )() );
check( 'B. a copy of a block with a picture asks for the slot of its own, made from the original\'s, and the undo takes the ask back', ( function() {
	fresh();
	S.texts['/template/page-home/hero/title'] = { de_DE : 'Hallo' };
	S.slots = [ '/template/page-home/hero/background' ];
	builder.duplicate( 3 );
	const asked = JSON.stringify( S.copies );
	builder.undo();
	return asked === JSON.stringify( { '/template/page-home/hero-2/background' : '/template/page-home/hero/background' } ) && Object.keys( S.copies ).length === 0;
} )() );
check( 'B. a text that is only clicked is no change and no step of the undo, one that is written is one step, and a text that was typed back to what it was is none', ( function() {
	fresh();
	const entry = { kind : 'text', key : '/template/page-home/hero/title', format : 'inline' };
	const clicked = builder.settle( entry, '', 'Hallo', 'Hallo' );
	const steps = S.undo.length;
	const written = builder.settle( entry, '', 'Servus', 'Hallo' );

	return clicked === false && steps === 0 && written === true && S.undo.length === 1 && S.texts['/template/page-home/hero/title'].de_DE === 'Servus' && builder.isDirty() === true;
} )() );

// --- the dialog: a block that is put in --------------------------------------------------------------------

const heroBlock = function() {
	return { key : 'hero-full', id : 'hero', known : { 'section-wrap' : S.known['section-wrap'], 'section-col' : S.known['section-col'], 'section-title' : S.known['section-title'] },
		tree : [ section( 'hero', 'T' ) ],
		texts : { de_DE : { title : 'Titel', text : 'Text', '1-text' : 'Innen' }, en_US : { title : 'Title', text : 'Text', '1-text' : 'Inside' } },
		slots : [ { name : 'title', scope : '', word : 'title', kind : 'text' }, { name : '1-text', scope : '1', word : 'text', kind : 'text' }, { name : 'background', scope : '', word : 'background', kind : 'image' } ],
		images : { background : { placeholder : '/nino/placeholder/landscape', width : 1920, height : 1080, label : { de_DE : 'Hintergrundbild', en_US : 'Background picture' } } } };
};

check( 'B. a block is put in with its own id where the page has none, with the next number where it has, and what is inside begins with the id of the root - hero-2 and hero-2-1', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'start' ] );
	S.locales = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true } ];
	S.native = 'de_DE';
	const place = builder.points().find( function( spot ) { return spot.carry === 'after' && S.top[spot.block].a.id === 'start' } );
	const ok = builder.insert( place, heroBlock() );
	return ok === true && idsOf().join() === 'hero,start,hero-2' && S.top.find( function( node ) { return node.a !== undefined && node.a.id === 'hero-2' } ).c[3].a.id === 'hero-2-1' && S.inserted['hero-2'] === 'hero-full';
} )() );
check( 'B. its words are under the keys of the new ids, each language with its own, and the picture is no word', ( function() {
	return S.texts['/template/page-home/hero-2/title'].de_DE === 'Titel' && S.texts['/template/page-home/hero-2-1/text'].en_US === 'Inside' && S.texts['/template/page-home/hero-2/background'] === undefined;
} )() );
check( 'B. the languages of the site get what the block has: the native one the English text where the block has none for it, any other none', ( function() {
	fresh();
	S.locales = [ { locale : 'de_DE', active : true }, { locale : 'fr_FR', active : true }, { locale : 'en_US', active : false } ];
	S.native = 'de_DE';
	const block = heroBlock();
	delete block.texts.de_DE;
	builder.insert( builder.points()[0], block );
	const words = S.texts['/template/page-home/hero-2/title'] ?? S.texts['/template/page-home/hero-3/title'] ?? S.texts['/template/page-home/hero-4/title'];
	return Object.keys( words ).join() === 'de_DE' && words.de_DE === 'Title';
} )() );
check( 'B. a block is put in at each seam between the calls of the frames - behind the first, up to the last -, the button of a seam is carried by the block beside it, and a frame has none', ( function() {
	fresh();
	const spots = builder.points().map( function( spot ) { return spot.b+ ':'+ spot.carry+ ':'+ ( spot.block === null ? '' : S.top[spot.block].a.id ) } ).join();
	S.frame = true;
	const none = builder.points().length === 0;
	S.frame = false;
	return spots === '1:before:hero,2:after:hero,3:after:start' && none === true;
} )() );
check( 'B. a page that has no block has a place - at its top, between its pieces and at its end, anywhere where it has no call of a frame - and a block put in there stands where the place is', ( function() {
	fresh();
	S.top = [ { t : '<p>Impressum</p>' } ];
	S.oldIds = new Set();
	S.locales = [ { locale : 'de_DE', active : true } ];
	S.native = 'de_DE';
	const spots = builder.points();
	const ok = builder.insert( spots[spots.length - 1], heroBlock() );
	return spots.length === 2 && ok === true && S.top.length === 2 && S.top[1].a.id === 'hero-2' && S.refocus.at === 1;
} )() );
check( 'B. the focus is set where the new block will be before the page is drawn: the page is drawn by the change, and the button that had the focus is gone with it', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'start' ] );
	S.locales = [ { locale : 'de_DE', active : true } ];
	S.native = 'de_DE';
	let seen = null;
	S.gl = { tree : { update : function() { seen = JSON.stringify( S.refocus ) }, select : function() {}, focus : function() {}, highlight : function() {}, el : { contains : function() { return false }, querySelector : function() { return null } } }, rail : { set innerHTML( value ) {}, appendChild : function() {} }, first : {}, lit : [] };
	S.view = { bar : { set : function() {} }, buttons : { undo : {}, redo : {} }, preview : { schedule : function() {} } };
	builder.insert( builder.points()[1], heroBlock(), 'outline' );
	S.view = null;
	S.gl = null;
	return seen !== null && JSON.parse( seen ).from === 'outline' && S.top[JSON.parse( seen ).at].a.id === 'hero-2';
} )() );
check( 'B. the picture of a block that is put in is known to the panel with its label and size before the page is saved - the server is told it, so that another file can be chosen for it - and the undo takes it back', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'start' ] );
	S.locales = [ { locale : 'de_DE', active : true } ];
	S.native = 'de_DE';
	builder.insert( builder.points()[1], heroBlock() );
	const key = '/template/page-home/hero-2/background';
	const told = JSON.stringify( builder.picturesNow()[key] );
	builder.undo();
	return told === JSON.stringify( { label : 'Background picture', width : 1920, height : 1080 } ) && S.pictures[key] === undefined;
} )() );
check( 'B. a block that is put in is called what the dialog called it until it has words to be called by, and its copy is called so too', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'start' ] );
	S.locales = [ { locale : 'de_DE', active : true } ];
	S.native = 'de_DE';
	S.named = {};
	const block = heroBlock();
	block.texts.de_DE.title = 'Eine klare Überschrift für Deine Seite';
	builder.insert( builder.points()[1], block, 'frame', { de_DE : 'Hero mit Band', en_US : 'Hero with band' } );
	const at = S.top.findIndex( function( node ) { return node.a !== undefined && node.a.id === 'hero-2' } );
	builder.duplicate( at );
	const copy = S.top.findIndex( function( node ) { return node.a !== undefined && node.a.id === 'hero-3' } );
	return builder.titleOf( S.top[at] ) === 'Section \u00B7 Hero with band' && builder.titleOf( S.top[copy] ) === 'Section \u00B7 Hero with band' && builder.titleOf( S.top[3] ) === 'Section \u00B7 Hallo';
} )() );
check( 'B. the extended view lays the desktop out at 1280 and scales it into its narrow pane, the page does not: its desktop is as wide as the pane', ( function() {
	const advanced = builder.layoutsOf( true );
	const basic = builder.layoutsOf( false );
	return advanced.desktop === 1280 && advanced.tablet === 768 && advanced.mobile === 390 && basic.desktop === null && basic.tablet === 768;
} )() );
check( 'B. the build of a page is the design step\'s: for the content step there is no place to put a block in, and a block is not moved, copied or taken out - whatever presses the way it is done', ( function() {
	fresh();
	const was = JSON.stringify( S.top );
	const before = builder.points().length;
	S.level = 1;
	const none = builder.points().length === 0 && builder.builds() === false;
	const [ one ] = blocks();
	builder.move( one, 1, 'frame' );
	builder.duplicate( one, 'frame' );
	builder.remove( one, 'frame' );
	const same = JSON.stringify( S.top ) === was && S.undo.length === 0;
	S.level = 2;
	return before > 0 && none === true && same === true && builder.builds() === true && builder.points().length === before;
} )() );
check( 'B. a page that has no call of its foot says so quietly in the outline - "No footer", no word of a fault - for the one who designs, with the way to put it back at its end, once; the one who changes the content is told nothing and has no way',
	( function() {
		fresh();
		const wasFoot = S.top.findIndex( function( node ) { return node.n === 'template' && node.s === '/templates/html-footer' } );
		S.top.splice( wasFoot, 2 );
		S.sheet = 'page';

		const says = function() { return builder.outlineItems().filter( function( row ) { return row.id === '@missing-footer' } ).length };
		const quiet = builder.missingFrames().join() === 'footer' && says() === 1 && builder.outlineItems()[builder.outlineItems().length - 1].id === '@missing-footer';
		S.level = 1;
		const none = builder.missingFrames().length === 0 && says() === 0;
		builder.restoreFrame( 'footer' );
		const refused = S.top.some( function( node ) { return node.s === '/templates/html-footer' } ) === false;
		S.level = 2;
		builder.restoreFrame( 'footer' );
		builder.restoreFrame( 'footer' );
		const back = S.top.filter( function( node ) { return node.s === '/templates/html-footer' } ).length === 1 && S.top[S.top.length - 2].s === '/templates/html-footer';

		const whole = quiet === true && none === true && refused === true && back === true && builder.missingFrames().length === 0 && S.undo.length === 1;

		const said = ( function() { S.top.splice( S.top.findIndex( function( node ) { return node.s === '/templates/html-footer' } ), 2 ); return builder.outlineItems().filter( function( row ) { return row.id === '@missing-footer' } )[0] } )();
		const quietly = said !== undefined && said.actions.length === 1 && [ 'en', 'de' ].every( function( lang ) { return [ 'missing-header', 'missing-footer' ].every( function( key ) { return words[lang]['/_admin/builder/frame/'+ key] !== undefined && /missing|fehlt|error|wrong|fehler/i.test( words[lang]['/_admin/builder/frame/'+ key] ) === false } ) } ) && /^(?:No|Kein)/.test( words.en['/_admin/builder/frame/missing-footer'] ) === true;

		return whole === true && quietly === true;
	} )() );
check( 'B. what the call of html holds is the account\'s that may develop: for the others the block that holds text in it is locked, for that account it is a block like any other - and the words of a new text are the ones of the language of the page', ( function() {
	fresh();
	const html = { n : 'html', s : '', a : {}, c : [ { t : '<p>x</p>' } ] };
	const block = { n : 'section-wrap', s : '', a : { id : 'raw' }, c : [ { t : '\n' }, html, { t : '\n' } ] };
	S.known.html = { label : 'HTML', id : '', source : 'none' };
	S.level = 2;
	const closed = builder.free( block );
	S.level = 3;
	const open = builder.free( block );
	S.level = 1;
	S.fresh = { de_DE : 'Neuer Text', en_US : 'New text' };
	S.locale = 'en_US';
	return closed === false && open === true && builder.freshText() === 'New text';
} )() );
context.window.Nino.admin.builder.call = function( name, data ) { went.calls.push( [ name, data ] ) };

const listed = { pages : [ { template : 'page-home', name : 'Home', path : '/', locales : [] }, { template : 'page-contact', name : 'Contact', path : '/contact', locales : [] } ], frames : [ { template : 'frame-header', key : 'header' } ], level : 2 };

check( 'B. the page goes back to the list, keeping nothing of an extended view: the way back of a page is the list and it has no block in it', ( function() {
	fresh();
	S.list = listed;
	S.template = 'page-contact';
	const way = builder.backOf( false );
	return way.parts.length === 0 && way.keep === false && way.block === false && typeof builder.openExtended === 'undefined';
} )() );
check( 'B. a variant that a hint names is told by the label the register gives it, and a variant that has none has no word - never its key', ( function() {
	const labelled = builder.hintWord( { variant : 'band', standard : 'soft', labels : { variant : null, standard : { en_US : 'Soft surface' } } }, 'standard' );
	const bare = builder.hintWord( { variant : 'band', standard : 'soft', labels : { variant : null, standard : null } }, 'variant' );
	return labelled === 'Soft surface' && bare === '';
} )() );
check( 'B. the bar offers the last state where there is one, and has it off with its reason where the account may not put it back', ( function() {
	fresh();
	const made = function( last, unback ) {
		const restore = { disabled : false, title : null };
		S.last = last;
		S.unback = unback;
		const bar = { set : function() {}, restore : restore, el : { insertBefore : function( node ) { return node } } };
		builder.paintBar( { bar : bar } );
		return restore;
	};
	const may = made( 1, '' );
	const may2 = made( 1, 'builder_restore_develop' );
	const none = made( 0, 'builder_restore_develop' );
	S.last = 0;
	S.unback = '';
	return may.disabled === false && may2.disabled === true && may2.title !== null && may.title === null && none.disabled === false;
} )() );
check( 'B. what the bar said of a change is gone where the undo takes the whole of it back, and stays while something is left', ( function() {
	fresh();
	const lines = [];
	S.view = { bar : { set : function() {}, line : { idle : function() { lines.push( 'idle' ) } }, restore : {} }, buttons : { undo : {}, redo : {} }, preview : { schedule : function() {} }, split : { settings : { contains : function() { return false }, appendChild : function() {}, querySelectorAll : function() { return [] }, set innerHTML( value ) {} } } };
	builder.duplicate( 3 );
	builder.duplicate( 3 );
	builder.undo();
	const half = lines.length;
	builder.undo();
	S.view = null;
	return half === 0 && lines.length === 1;
} )() );
check( 'B. a name is the kind and the first words where they are short, the kind and the id where they are not, and the title of it is the whole of them', ( function() {
	fresh();
	S.texts['/template/page-home/hero/title'] = { de_DE : 'Eine klare Überschrift für Deine Seite' };
	return builder.titleOf( S.top[3] ) === 'Section \u00B7 hero' && builder.titleOf( S.top[3], true ) === 'Section \u00B7 Eine klare Überschrift für Deine Seite';
} )() );
check( 'B. the picture of a tile is a frame that has no right at all: a script of the site does not run in it', ( function() {
	let frame = null;
	builder.tileFrame( { appendChild : function( made ) { frame = made } }, { address : '/', query : 'nino-preview=0123' } );
	return frame !== null && frame.attributes.sandbox === '';
} )() );
check( 'B. a block that was put in is one step of the undo, its copy is a block that was put in too, and what the server is told is the blocks that are still on the page', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'start' ] );
	S.locales = [ { locale : 'de_DE', active : true } ];
	S.native = 'de_DE';
	const begin = JSON.stringify( [ S.top, S.texts ] );
	builder.insert( builder.points()[1], heroBlock() );
	const copied = builder.duplicate( S.top.findIndex( function( node ) { return node.a !== undefined && node.a.id === 'hero-2' } ) );
	const told = Object.keys( builder.insertedNow() ).sort().join();
	builder.undo();
	const half = Object.keys( builder.insertedNow() ).join();
	builder.undo();
	return copied === true && told === 'hero-2,hero-3' && half === 'hero-2' && JSON.stringify( [ S.top, S.texts ] ) === begin && Object.keys( builder.insertedNow() ).length === 0;
} )() );
check( 'B. the dialog asks for as many pictures as the server keeps, and the script does not have a bigger number than the class', ( function() {
	const php = fs.readFileSync( path.join( featureDir, 'Library/Library.php' ), 'utf8' );
	return Number( ( /const int TILES = (\d+);/.exec( php ) ?? [] )[1] ) === Number( ( /const TILES\s*= (\d+);/.exec( script ) ?? [] )[1] );
} )() );

// --- C. the extended view ---------------------------------------------------------------------------------------

const schemas = {
	'section-wrap' : { icon : 'panel-top', label : 'Section', group : 'section', container : true, source : 'none', format : '', id : 'id', variants : [], standard : 'regular', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' }, ground : { type : 'choice', label : 'Ground', hint : '', default : 'base', options : [ 'base', 'alt' ], optionLabels : null, responsive : false } } },
	'section-col' : { icon : 'columns-3', label : 'Column', group : 'section', container : true, source : 'none', format : '', id : 'id', variants : [ 'card' ], standard : 'regular', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' }, size : { type : 'choice', label : 'Width', hint : '', default : '100', options : [ '25', '33', '50', '100' ], optionLabels : null, responsive : true }, link : { type : 'bool', label : 'Link', hint : '', default : false } } },
	'section-title' : { icon : 'heading', label : 'Title', group : 'section', container : false, source : 'text', format : 'inline', id : '', variants : [], standard : 'regular', attributes : {} },
	'text' : { icon : 'text', label : 'Text', group : 'text', container : false, source : 'text', format : 'blocks', id : '', variants : [], standard : 'regular', attributes : {} },
	'spacer' : { icon : 'ellipsis', label : 'Spacer', group : 'text', container : false, source : 'none', format : '', id : '', variants : [], standard : 'regular', attributes : {} },
};

const extended = function() {

	fresh();
	S.schemas = schemas;
	S.known = { 'section-wrap' : { label : 'Section', id : 'id', source : 'none' }, 'section-col' : { label : 'Column', id : 'id', source : 'none' }, 'section-title' : { label : 'Title', id : '', source : 'text' }, text : { label : 'Text', id : '', source : 'text' }, spacer : { label : 'Spacer', id : '', source : 'none' } };
	S.level = 2;
	S.findings = {};
	S.mode = 'advanced';
	S.adv = { slug : 'home', block : 'hero' };
	S.locale = 'de_DE';
	S.top = [ { t : '<!-- note -->\n' }, { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'hero' }, c : [ { t : '\n\t' }, { n : 'section-col', s : '', a : { id : 'hero-1', size : '100' }, c : [ { t : '\n\t\t' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n\t\t' }, { n : 'text', s : '~text', a : {}, c : null }, { t : '\n\t\t<p>free</p>\n\t' } ] }, { t : '\n' } ] }, { t : '\n' } ];
	S.pictures = {};
	S.saved = JSON.stringify( [ S.top, S.texts, S.pictures ] );
};

check( 'C. an address names the page, and for the old address of the extended view - #builder/<page>/<block> - the block that is marked where the page opens: there is no extended view of a page, a frame is no address of this panel, and what is none is no page', ( function() {
	const at = function( parts ) { return builder.targetOf( parts ) };
	const old = at( [ 'home', 'hero' ] );
	const more = at( [ 'home', 'hero', '1.1', 'look' ] );
	const header = at( [ 'home', '@header' ] );

	return old.mode === 'basic' && old.template === 'page-home' && old.block === 'hero' && more.mode === 'basic' && more.block === 'hero' && header.block === '@header' && header.template === 'page-home'
		&& at( [ '@header' ] ) === null && at( [ 'contact', '@header', '1' ] ).template === 'page-contact' && at( [ 'home', 'Not A Block' ] ).block === '' && at( [ 'no_page' ] ) === null;
} )() );

check( 'C. the tree of a block is its components with their kind and first words, a locked entry for free markup, and the id of an entry is its place in the block', ( function() {
	extended();
	S.texts['/template/page-home/hero-1/title'] = { de_DE : 'Hallo Welt' };

	const root = builder.itemsNow()[0];
	const column = root.children[0];

	return root.id === 'root' && root.kind === 'Section' && column.id === '1' && column.children.map( function( item ) { return item.id } ).join() === '1.1,1.3,1.4'
		&& column.children[0].name === 'Hallo Welt' && column.children[2].locked === true && column.children[0].locked === false
		&& root.actions.map( function( action ) { return action.key } ).join() === 'add,edit' && column.actions.map( function( action ) { return action.key } ).join() === 'add,up,down,edit,remove'
		&& column.children[2].actions.map( function( action ) { return action.key } ).join() === 'up,down,remove';
} )() );

check( 'C. an entry of the tree carries the icon of its component, the kind and the content apart, the width and the variant as its note, a lock for what is locked and the finding as its hint', ( function() {
	extended();
	S.texts['/template/page-home/hero-1/title'] = { de_DE : 'Hallo Welt' };
	S.top[3].c[1].a.variant = 'card';
	S.top[3].c[1].a.size = '50 m:100';
	S.findings = { '3.1.1': [ [ 'outside', 'size' ] ] };

	const root = builder.itemsNow()[0];
	const column = root.children[0];
	const title = column.children[0];
	const free = column.children[2];

	return root.icon === 'panel-top' && title.icon === 'heading' && title.kind === 'Title' && title.name === 'Hallo Welt' && title.label === undefined && title.detail === undefined
		&& column.meta === '50 % · card' && root.meta === '' && free.locked === true && free.icon === 'code' && free.name === undefined
		&& title.finding !== undefined && title.finding.state === 'warn' && column.finding === undefined && root.children[0].actions.every( function( action ) { return typeof action.icon === 'string' && action.label === undefined } );
} )() );

check( 'C. a component that repeats its children for each element is a loop with its type and sort, and a value that comes from a field of the element is the field as its source', ( function() {
	extended();
	S.schemas = Object.assign( {}, schemas, { 'collection-list' : { icon : 'list', label : 'List', group : 'collection', container : true, loop : true, source : 'none', format : '', id : '', variants : [], standard : 'regular', attributes : { sort : { type : 'string', label : 'Sort', hint : '', default : '', options : null, optionLabels : null, responsive : false }, limit : { type : 'int', label : 'At most', hint : '', default : 0 }, offset : { type : 'int', label : 'Skip', hint : '', default : 0 }, query : { type : 'string', label : 'Query', hint : '', default : '' } } } } );
	S.known['collection-list'] = { label : 'List', id : '', source : 'none' };
	S.types = [ { type : '/services', title : 'Services' } ];
	S.top[3].c[1].c = [ { t : '\n\t\t' }, { n : 'collection-list', s : '/services', a : { sort : '-date' }, c : [ { t : '\n' }, { n : 'section-title', s : '.title', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' } ];

	const loop = builder.itemsNow()[0].children[0].children[0];
	const inner = loop.children[0];

	return typeof loop.loop === 'object' && loop.loop.type === 'Services' && loop.loop.detail.indexOf('Services') === 0 && loop.icon === 'list'
		&& inner.loop === undefined && inner.source === '.title' && inner.name === '' && builder.itemsNow()[0].loop === undefined && builder.itemsNow()[0].source === undefined;
} )() );

check( 'C. the stylesheet of the feature draws no tree of its own: no rule names a class of the workbench tree', fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' ).indexOf( 'nino-admin-tree' ) === -1 );

check( 'C. the tree of the extended view is the tree of the workbench and has no line of the path over it - the owner took it away - and nothing of it is left: no option, no class, no rule', /Nino\.adminUi\.tree\(\s*\{[^}]*\bpath\s*:\s*true\b/.test( script ) === false && /S\.tree\.path/.test( script ) === false && /builder-adv-path/.test( script ) === false && /builder-adv-path/.test( fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' ) ) === false );

check( 'C. a component is moved among its neighbours - over free markup too, never off the end - and taken out with the blanks that lay it out, so that no two texts stand side by side, and the undo gives back the tree to the byte', ( function() {
	extended();
	const over = builder.moveComponent( '1.3', 1 ) && S.top[3].c[1].c.some( function( node ) { return node.t !== undefined && node.t.indexOf('<p>free</p>') !== -1 } );
	extended();
	const begin = JSON.stringify( S.top );
	const up = builder.moveComponent( '1.3', -1 );
	const kinds = S.top[3].c[1].c.map( function( node ) { return node.n ?? 'blank' } ).join();
	const edge = builder.moveComponent( '1.1', -1 ) || builder.moveComponent( 'root', 1 ) || builder.moveComponent( '1.9', 1 );
	builder.undo();
	const back = JSON.stringify( S.top ) === begin;
	const out = builder.removeComponent( '1.1' );
	const list = S.top[3].c[1].c;
	const clean = list.map( function( node ) { return node.n ?? node.t } ).join() === '\n\t\t,text,\n\t\t<p>free</p>\n\t';
	builder.undo();

	return over === true && up === true && kinds === 'blank,text,blank,section-title,blank' && edge === false && back === true && out === true && clean === true && JSON.stringify( S.top ) === begin;
} )() );

check( 'C. a component that is put in stands behind the last piece the container holds - never inside a unit of locked pieces - with the blanks the server says, and reads a text of its own under the nearest id', ( function() {
	extended();
	builder.addComponent( '1', { name : 'section-title', node : { n : 'section-title', s : '', a : {}, c : null } }, '\n\t\t' );
	let list = S.top[3].c[1].c;
	const added = list.find( function( node ) { return node.s === '~title-2' } );
	const afterFree = list.indexOf( added ) > list.findIndex( function( node ) { return node.t !== undefined && node.t.indexOf('<p>free</p>') !== -1 } );
	const closed = list[list.length - 1].t !== undefined && list[list.length - 1].n === undefined;

	builder.addComponent( '1', { name : 'spacer', node : { n : 'spacer', s : '', a : {}, c : null } }, '\n\t\t' );
	list = S.top[3].c[1].c;

	return added !== undefined && afterFree === true && closed === true && typeof S.texts['/template/page-home/hero-1/title-2'].de_DE === 'string'
		&& list.some( function( node ) { return node.n === 'spacer' && node.s === '' } ) === true && list.filter( function( node ) { return node.n !== undefined } ).length === 4;
} )() );

check( 'C. the locked pieces that follow one another are one entry of the tree, one unit: it is moved past a call and taken out whole, and nothing moves into it', ( function() {
	extended();
	S.top[3].c[1].c = [ { t : '\n\t\t' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n\t\t<b>a</b>' }, { n : 'csrf', s : '', a : {}, c : null }, { t : '\n\t\t<i>b</i>\n\t' } ];
	const items = builder.itemsNow()[0].children[0].children;
	const one = items.length === 2 && items[1].locked === true;
	const moved = builder.moveComponent( '1.2', -1 );
	const kinds = S.top[3].c[1].c.map( function( node ) { return node.n ?? 'text' } ).join();
	const gone = builder.removeComponent( '1.1' );
	const left = S.top[3].c[1].c.map( function( node ) { return node.n ?? 'text' } ).join();

	return one === true && moved === true && kinds === 'text,csrf,text,section-title' && gone === true && left === 'section-title';
} )() );

check( 'C. an attribute is set where it is not the default and taken away where it is, a responsive width is the parts of "100 m:33", and a bool is a bare word', ( function() {
	extended();
	builder.setAttr( '1', 'size', builder.widthsWritten( Object.assign( builder.widthsOf( '100' ), { m : '33' } ), '100' ) );
	const size = S.top[3].c[1].a.size;
	builder.setAttr( '1', 'link', true );
	const link = S.top[3].c[1].a.link;
	builder.setAttr( '1', 'link', null );
	const parts = builder.widthsOf( '50 l:25' );

	return size === '100 m:33' && link === true && S.top[3].c[1].a.link === undefined && parts[''] === '50' && parts.l === '25' && parts.m === '' && builder.widthsWritten( builder.widthsOf( '' ), '100' ) === '100'
		&& builder.widthsWritten( { '' : '', m : '', l : '50' }, '100' ) === '100 l:50';
} )() );

check( 'C. a text is read from the place the component says: the key under the nearest id, a fixed text for all languages, and a key of the project that is not written here', ( function() {
	extended();
	S.top[3].c[1].c[3].a = { text : 'Fest' };
	S.top[3].c[1].c[1].s = '/project/company/general/name';
	const at = function( rel ) { return builder.sourceOf( rel ) };

	return at( '1.3' ).kind === 'fixed' && at( '1.3' ).editable === true && at( '1.1' ).key === '/project/company/general/name' && at( '1.1' ).editable === false
		&& at( '1' ) === null && Object.keys( builder.model === undefined ? {} : {} ).length === 0;
} )() );

check( 'C. what the register has against a place is a sentence at the entry, and the way into a block that is gone is the page', ( function() {
	extended();
	S.findings = { '3.1.1': [ [ 'outside', 'size' ] ] };
	const hint = builder.hintOf( '3.1.1' );
	S.top.splice( 3, 1 );

	return hint !== undefined && hint.state === 'warn' && builder.hintOf( '3.1.9' ) === undefined && builder.rootAt() === -1 && builder.itemsNow().length === 0;
} )() );

// --- D. the source of a content ---------------------------------------------------------------------------------

check( 'D. a name in a template is words of lower case letters and digits joined by hyphens: the umlauts as German writes them, no mark, no hyphen at an end, and no longer than it is told', builder.slugOf( 'Über uns & Söhne!' ) === 'ueber-uns-soehne' && builder.slugOf( '  --Café  ' ) === 'cafe' && builder.slugOf( '' ) === '' && builder.slugOf( 'abcdefghij', 5 ) === 'abcde' && builder.slugOf( 'ab cdefghij', 3 ) === 'ab' );

check( 'D. a source is put into a call, each a step of the undo: a new text is a key of the scope with the words of the view - and no fixed text is left -, a fixed text takes the place of the first word, a key of another place is written as it is, a link is a page or an address, a new picture is a slot', ( function() {
	extended();
	const begin = JSON.stringify( [ S.top, S.texts, S.pictures ] );
	const title = function() { return S.top[3].c[1].c[1] };
	builder.applySource( '1.1', { kind : 'text', attr : '' }, { form : 'new', name : 'claim', text : 'Hi' } );
	const made = title().s === '~claim' && S.texts['/template/page-home/hero-1/claim'].de_DE === 'Hi' && title().a.text === undefined;
	builder.applySource( '1.1', { kind : 'text', attr : '' }, { form : 'fixed', text : 'Fest' } );
	const fixed = title().s === '' && title().a.text === 'Fest' && builder.sourceOf( '1.1' ).kind === 'fixed';
	builder.applySource( '1.1', { kind : 'text', attr : '' }, { form : 'key', written : '/project/company/general/name' } );
	const other = title().s === '/project/company/general/name' && title().a.text === undefined && builder.sourceOf( '1.1' ).editable === false;
	builder.applySource( '1', { kind : 'link', attr : 'href' }, { form : 'page', written : 'page:/contact' } );
	const page = S.top[3].c[1].a.href === 'page:/contact';
	builder.applySource( '1', { kind : 'link', attr : 'href' }, { form : 'key', written : '' } );
	const none = S.top[3].c[1].a.href === undefined;
	builder.applySource( '1.1', { kind : 'image', attr : '' }, { form : 'new', name : 'photo', label : 'Photo', width : 800, height : 600 } );
	const picture = S.pictures['/template/page-home/hero-1/photo'].width === 800 && builder.isDirty() === true && Object.keys( builder.picturesNow() ).join() === '/template/page-home/hero-1/photo';
	const steps = S.undo.length;

	for( let count = 0; count < steps; count++ )
		builder.undo();

	return made === true && fixed === true && other === true && page === true && none === true && picture === true && steps === 6 && JSON.stringify( [ S.top, S.texts, S.pictures ] ) === begin && builder.isDirty() === false;
} )() );

check( 'D. a field of the element is a source in a loop only: the loop is the nearest call above that holds calls and names a type of elements as its first word, and a call that is not inside one has none', ( function() {
	extended();
	S.schemas = Object.assign( {}, schemas, { collection : Object.assign( {}, schemas['section-col'], { loop : true } ) } );
	S.top[3].c[1].c = [ { t : '\n' }, { n : 'collection', s : '/services', a : {}, c : [ { t : '\n' }, { n : 'section-title', s : '.title', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' } ];

	const inside = builder.loopOf( '1.1.1' ) === '/services' && builder.loopOf( '1.1' ) === null && builder.loopOf( 'root' ) === null && builder.loopOf( '1' ) === null;

	// A loop that names no type yet is a loop - the dialog says to choose first - and no field is offered
	S.top[3].c[1].c[1].s = '';
	const typeless = builder.loopOf( '1.1.1' ) === '';
	S.top[3].c[1].c[1].s = '/services';

	// A call that names a type of elements and holds no loop is no loop: it is one only where the register says it holds calls the way a loop does
	S.top[3].c[1].c[1].n = 'section-col';
	S.top[3].c[1].c[1].a = { id : 'hero-2' };

	return inside === true && typeless === true && builder.loopOf( '1.1.1' ) === null;
} )() );

check( 'D. the key of a written source is the key of the scope for ~name and the key as it is for one with a slash - and none for a field of the element', ( function() {
	extended();
	return builder.keyOf( '1.1', '~claim' ) === '/template/page-home/hero-1/claim' && builder.keyOf( '1.1', '/project/company/general/name' ) === '/project/company/general/name' && builder.keyOf( '1.1', '.title' ) === '' && builder.keyOf( 'root', '~x' ) === '/template/page-home/hero/x';
} )() );

check( 'D. a text the language of the view has none of is shown as the page shows it - the text of the native language first, then of the next that has one - and a text nobody has is none', ( function() {
	extended();
	S.native = 'de_DE';
	S.locales = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true }, { locale : 'fr_FR', active : true } ];
	S.locale = 'fr_FR';
	S.texts['/template/page-home/hero/x'] = { de_DE : '', en_US : '<p>English</p>' };
	S.texts['/template/page-home/hero/y'] = { de_DE : 'Hallo', en_US : 'Hello' };

	return builder.fallbackText( '/template/page-home/hero/x' ) === 'English' && builder.fallbackText( '/template/page-home/hero/y' ) === 'Hallo' && builder.fallbackText( '/template/page-home/hero/none' ) === '';
} )() );

check( 'D. a picture of the page that may be exchanged where it stands is a call that is a picture or a call that reads one as an attribute, and a slot of the page - not a slot of another place, not a picture of a frame - and its alt is the key of the page it reads', ( function() {
	extended();
	S.template = 'page-home';
	S.sources = [ { path : '3.1', kind : 'image', attribute : '', key : '/template/page-home/hero/picture' }, { path : '3.2', kind : 'image', attribute : '', key : '/logo' }, { path : '3.3', kind : 'image', attribute : 'image', key : '/template/page-home/hero/background' }, { path : '3.4', kind : 'text', attribute : '', key : '/template/page-home/hero/title' } ];
	S.top[3].c[1] = { n : 'image', s : '~picture', a : { alt : '~alt' }, c : null };
	S.images = {};
	const found = builder.pictureTargets();
	S.frame = true;
	const frame = builder.pictureTargets().length;
	S.frame = false;

	return found.length === 2 && found[0].path === '3.1' && found[0].attribute === false && found[0].alt === '/template/page-home/hero/alt' && found[1].path === '3.3' && found[1].attribute === true && found[1].key === '/template/page-home/hero/background' && frame === 0;
} )() );

check( 'D. the pictures the server is told of are the slots with their size and the file that was made for them, and never the address of the picture', ( function() {
	extended();
	S.pictures = { '/template/page-home/hero/photo' : { label : 'Photo', width : 800, height : 600, file : 'template/page-home/hero/photo.s0123456789ab.800x600.webp', url : '/public/images/x.webp' }, '/template/page-home/hero/other' : { label : 'Other', width : 10, height : 10 } };
	const told = builder.picturesNow();

	return told['/template/page-home/hero/photo'].url === undefined && told['/template/page-home/hero/photo'].file.indexOf('.s0123456789ab.') !== -1 && Object.keys( told['/template/page-home/hero/other'] ).join() === 'label,width,height';
} )() );

// --- E. the language of the workbench ----------------------------------------------------------------------------

const calls = [];
const language = function() { return followers.find( function( follower ) { return follower.name === 'builder' } ) };

panel.call = function( action, payload, done ) { calls.push( { action : action, payload : payload, done : done } ) };
panel.reload = function( then ) { if( typeof then === 'function' ) then() };
panel.ready = true;

check( 'E. the panel follows the language of the workbench under the name of its entry of unsaved input - the one the question before a change asks about', language() !== undefined && panel.uri === 'builder' );

check( 'E. a call about a page names the language the page is shown in', ( function() {
	fresh();
	S.locale = 'en_US';
	builder.api( 'preview', {}, function() {} );
	return calls[0].payload.locale === 'en_US' && calls[0].payload.template === 'page-home';
} )() );

check( 'E. a change of the language from outside, with input that is not saved, asks first - in the entry of the panel - and the page is opened again in the new language', ( function() {
	fresh();
	S.target = { mode : 'basic', template : 'page-home', page : '', slug : 'home' };
	S.origin = null;
	S.texts['/template/page-home/hero/title'].de_DE = 'Geändert';
	questions.length = 0;
	calls.length = 0;
	answer = 'save';
	context.window.Nino.admin.workLocale.current = 'en_US';
	language().handler( 'en_US' );

	return questions.join() === 'builder' && calls.length === 1 && calls[0].action === 'open' && calls[0].payload.locale === 'en_US';
} )() );

check( 'E. Cancel keeps the language of the page and the draft, asks the workbench to take its language back, and opens nothing', ( function() {
	fresh();
	S.texts['/template/page-home/hero/title'].de_DE = 'Geändert';
	const draft = JSON.stringify( S.texts );
	calls.length = 0;
	chosen.length = 0;
	answer = 'cancel';
	language().handler( 'en_US' );
	answer = 'save';

	return S.locale === 'de_DE' && JSON.stringify( S.texts ) === draft && calls.length === 0 && chosen.join() === 'de_DE';
} )() );

check( 'E. the language of the page is written to the session by nobody: the workbench has one, and a change is made with workLocale', ( function() {
	fresh();
	S.target = { mode : 'basic', template : 'page-home', page : '', slug : 'home' };
	language().handler( 'en_US' );
	language().handler( 'de_DE' );

	return written.length === 0;
} )() );

check( 'E. the language is the workbench\'s alone: the view has no select of its own for it, and the context bar says it by the badge of the workbench', /Nino\.adminUi\.localeBadge\(\s*S\.locale,\s*context\s*\)/.test( script ) === true );

check( 'E. a page that was on its way when the language moved is asked for again in the language of the workbench, and not kept in the other', ( function() {
	fresh();
	S.target = { mode : 'basic', template : 'page-home', page : '', slug : 'home' };
	calls.length = 0;
	context.window.Nino.admin.workLocale.current = 'en_US';
	language().handler( 'en_US' );
	calls[0].done( 200, { contentLocale : 'de_DE' } );

	return calls.length === 2 && calls[1].action === 'open' && calls[1].payload.locale === 'en_US';
} )() );

check( 'E. the entry of unsaved input says it is saving from the request to the answer, which is what the workbench waits for before it changes the language', ( function() {
	fresh();
	S.view = { bar : { line : { error : function() {} } } };
	calls.length = 0;
	const before = panel.dirty.saving();
	builder.save( function() {} );
	const during = panel.dirty.saving();
	calls[0].done( 500, {} );
	S.view = null;

	return before === false && during === true && panel.dirty.saving() === false;
} )() );

check( 'E. Discard of the choice of a language, which the shell answers by closing the page, opens the page again in the new language', ( function() {
	fresh();
	S.at = [ 'home' ];
	const went = [];
	panel.go = function( parts ) { went.push( parts ) };
	panel.dirty.discard();
	S.ready = false;
	language().handler( 'en_US' );

	return went.length === 1 && went[0].join() === 'home';
} )() );

// --- the id of a section, changed in its bar -------------------------------------------------------------------------------------

fresh();
S.oldIds = new Set( [ 'hero', 'hero-1', 'start' ] );
S.inserted = {};
S.named = {};
S.selected = 'hero';

check( 'P6. the id of a section is changed in one step of the undo: the block is made again under the new id by the rule of a copy - what is inside begins with it - with the words of each of its ids under the new ones, and the chosen block stays chosen', ( function() {
	const [ at ] = blocks();
	const before = JSON.stringify( [ S.top, S.texts ] );
	const undone = S.undo.length;
	const result = builder.renameBlock( at, 'intro' );

	const made = result === '' && idsOf().join() === 'intro,start' && S.top[at].c[3].a.id === 'intro-1' && S.texts['/template/page-home/intro/title'].en_US === 'Hello' && S.texts['/template/page-home/intro-1/text'].de_DE === 'Eins'
		&& S.copies['/template/page-home/intro/background'] === '/template/page-home/hero/background' && S.undo.length === undone + 1 && S.selected === builder.keyAt( at );

	builder.undo();

	return made === true && JSON.stringify( [ S.top, S.texts ] ) === before && S.undo.length === undone;
} )() );

check( 'F287. the jump marks of the page that point at the id that goes - href="#hero" in a component, a text that is just "#hero", also for an id inside the section - point at the new id in the same step of the undo, and are counted; links that only look alike - another id, an address with the id behind it - stay', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'hero-1', 'start' ] );
	S.inserted = {};
	S.named = {};

	const [ at, other ] = blocks();

	S.top[other].c.push( { n : 'button', a : { href : '#hero', title : '#hero-10' }, c : [] }, { n : 'button', a : { href : 'page:/x#hero' }, c : [] }, { n : 'button', a : { href : '#hero-1' }, c : [] }, { n : 'button', a : { href : 'xhero' }, c : [] } );
	const tail = function() { return S.top[other].c.slice( -4 ).map( function( node ) { return node.a.href } ).join() };
	S.texts['/template/page-home/start/link'] = { de_DE : '#hero', en_US : '#hero' };
	S.texts['/template/page-home/start/note'] = { de_DE : 'see #hero' };

	const before = JSON.stringify( [ S.top, S.texts ] );
	const undone = S.undo.length;
	const result = builder.renameBlock( at, 'intro' );
	const links = tail();

	const made = result === '' && S.relinked === 3 && links === '#intro,page:/x#hero,#intro-1,xhero' && S.top[other].c.slice( -4 )[0].a.title === '#hero-10' && S.texts['/template/page-home/start/link'].de_DE === '#intro' && S.texts['/template/page-home/start/link'].en_US === '#intro' && S.texts['/template/page-home/start/note'].de_DE === 'see #hero' && S.undo.length === undone + 1;

	builder.undo();

	return made === true && JSON.stringify( [ S.top, S.texts ] ) === before;
} )() );

check( 'F287. the change of an id says what it moved on the page, and asks the server which other pages still link to the old id - the ids of the section and of what is inside it, only for a page - and names them, three at most and the rest counted, in the line of the bar',
	/api\( 'anchorlinks', \{ ids : ids \}/.test( script ) === true && /S\.sheet !== 'page' \|\| S\.frame === true/.test( script ) === true && /moved === 1 \? word\( 'ident\/relinked-one', '#'\+ now \) : word\( 'ident\/relinked', String\( moved \), '#'\+ now \)/.test( script ) === true && /others\.pages\.slice\( 0, 3 \)/.test( script ) === true && /word\( 'ident\/others-more', String\( others\.pages\.length - 3 \) \)/.test( script ) === true
	&& /if\( lines\.length > 0 && S\.view !== null \)\s*S\.view\.bar\.line\.idle\( lines\.join\(' '\) \)/.test( script ) === true && /announceLinks\( id, value, wasIds, S\.relinked \)/.test( script ) === true );

check( 'P6. an id that is no id, is too long or is another one of the page - or one a text or a picture place of the page already has for a scope - is refused with its reason, and nothing is changed and no step is made', ( function() {
	fresh();
	S.oldIds = new Set( [ 'hero', 'hero-1', 'start' ] );
	S.inserted = {};
	S.named = {};
	S.texts['/template/page-home/taken/title'] = { de_DE : 'x' };

	const [ at ] = blocks();
	const before = JSON.stringify( [ S.top, S.texts, S.copies ] );
	const answers = [ 'Intro', '1intro', '-intro', 'in--tro', 'in tro', '', 'a'.repeat( 70 ), 'start', 'taken' ].map( function( id ) { return builder.renameBlock( at, id ) } );

	// an id of another block that the saved page does not have any more is taken as well
	S.oldIds = new Set();
	const unsaved = builder.identProblem( at, 'start' );

	return answers.join() === 'form,form,form,form,form,form,long,taken,taken' && unsaved === 'taken' && JSON.stringify( [ S.top, S.texts, S.copies ] ) === before && S.undo.length === 0 && builder.identProblem( at, 'fresh-id' ) === '';
} )() );

check( 'P6. the id stays where it is the page\'s or the block\'s own and says why: not for the one who writes the content, not in a frame of the site, not in the editor of a block, not for a block with a locked piece', ( function() {
	fresh();
	const [ at ] = blocks();
	const open = builder.identWhy( S.top[at] ) === '';

	S.level = 1;
	const content = builder.identWhy( S.top[at] ) !== '' && builder.renameBlock( at, 'intro' ) === 'locked';
	S.level = 2;
	S.frame = true;
	const frame = builder.identWhy( S.top[at] ) !== '' && builder.renameBlock( at, 'intro' ) === 'locked';
	S.frame = false;
	S.sheet = 'block';
	const sheet = builder.identWhy( S.top[at] ) !== '' && builder.renameBlock( at, 'intro' ) === 'locked';
	S.sheet = 'page';

	return open === true && content === true && frame === true && sheet === true && idsOf().join() === 'hero,start';
} )() );

check( 'P6. the bar of a section shows the id and not the word Section: the id is a button that a press turns into a field in the same place - Enter and leaving take it, Escape leaves it as it was - and what is wrong with it is said at the field', ( function() {
	const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

	return /bar\.appendChild\( identOf\( node, block \) \)/.test( script ) === true && /label\.textContent = title/.test( script ) === false && /event\.key === 'Enter'[\s\S]{0,80}take\( false \)/.test( script ) === true && /event\.key === 'Escape'[\s\S]{0,120}end\(\);/.test( script ) === true
		&& /input\.addEventListener\( 'blur', function\(\) \{ take\( true \) \} \)/.test( script ) === true && /note\.textContent = word\( 'ident\/'\+ problem \)/.test( script ) === true && /setAttribute\( 'aria-invalid', 'true' \)/.test( script ) === true
		&& /button\.setAttribute\( 'aria-disabled', 'true' \)/.test( script ) === true && /S\.refocus = \{ at : block\.at, action : 'ident'/.test( script ) === true;
} )() );


// --- the frames of a page --------------------------------------------------------------------------------------------------

fresh();
S.sheet = 'page';
S.oldIds = new Set( [ 'hero', 'start' ] );

const frameCalls = function() {
	return S.top.filter( function( node ) { return node.n === 'template' || node.n === 'frame' } ).map( function( node ) { return node.n+ ' '+ node.s } );
};

check( 'F286. a restore that brings back a state which names a frame the site has not says so at once: the line after the restore carries the word of the frame, and the plain sentence is for a restore with none missing',
	/response\.missingFrames\.length > 0 \? word\( 'msg\/restored-frame', response\.missingFrames\.join\(', '\) \) : word\('msg\/restored'\)/.test( script ) === true );

check( 'F285. a call of the frame of a page says its side and what it chooses: the call of the scaffold is the standard frame, [frame /templates/frame-header] too, a word is the word, none is none - and what is no call of a frame is none',
	builder.sideOfCall( { n : 'template', s : '/templates/html-header' } ) === 'header' && builder.sideOfCall( { n : 'frame', s : '/templates/frame-footer-klein' } ) === 'footer' && builder.sideOfCall( { n : 'template', s : '/templates/other' } ) === null && builder.sideOfCall( { n : 'frame', s : '/templates/page-x' } ) === null
	&& builder.choiceOfCall( { n : 'template', s : '/templates/html-header' } ) === 'default' && builder.choiceOfCall( { n : 'frame', s : '/templates/frame-header' } ) === 'default' && builder.choiceOfCall( { n : 'frame', s : '/templates/frame-header-landing' } ) === 'landing'
	&& builder.choiceOfCall( { n : 'frame', s : '/templates/frame-footer-none' } ) === 'none' && builder.frameWord( 'landing' ) === 'landing' );

{
	S.frameNames = { header : { landing : 'Landing' }, footer : {} };

	check( 'F286. a frame of the site that has a name is called by it - in the choice of a page and in the outline, with the word quietly beside it - and by its word where it has none; the standard frames and none are called by the words of the panel, and the name of the other side is not borrowed',
		builder.frameWord( 'landing', 'header' ) === 'Landing' && builder.frameWord( 'landing', 'footer' ) === 'landing' && builder.frameWord( 'zwei', 'header' ) === 'zwei' && builder.frameWord( 'default', 'header' ) !== 'Landing' && builder.frameWord( 'none', 'header' ) !== 'Landing');

	S.frameNames = null;
}


check( 'F285. the choice of both frames is written in ONE step of the undo, and the undo brings back both calls: the page that called the scaffold calls its frames - the header with the word, the footer with none - and the rest of the page is not touched',
	( function() {
		const before = JSON.stringify( S.top );
		const steps = S.undo.length;
		const ok = builder.setFrames( { header : 'landing', footer : 'none' } );
		const now = frameCalls();
		const rest = JSON.stringify( S.top.filter( function( node ) { return node.n !== 'frame' && node.n !== 'template' } ) ) === JSON.stringify( JSON.parse( before ).filter( function( node ) { return node.n !== 'frame' && node.n !== 'template' } ) );

		builder.undo();

		return ok === true && now.join() === 'frame /templates/frame-header-landing,frame /templates/frame-footer-none' && rest === true && S.undo.length === steps && JSON.stringify( S.top ) === before;
	} )() );

check( 'F285. the first change of a page that calls the scaffold itself writes both calls as the call of the frame - the side that was not changed with the standard frame - and a choice that changes nothing writes none',
	( function() {
		fresh();
		S.sheet = 'page';
		const none = builder.setFrames( { header : 'default', footer : 'default' } );
		const unchanged = JSON.stringify( S.top );
		const some = builder.setFrames( { header : 'landing' } );

		return none === false && some === true && frameCalls().join() === 'frame /templates/frame-header-landing,frame /templates/frame-footer' && unchanged.indexOf( 'html-header' ) !== -1;
	} )() );

check( 'F285. a choice that is the choice is no change and makes no step; a word that is not a word - a path, a capital, a space, a second hyphen - is no choice; the one who may not design and a frame of the site change nothing',
	( function() {
		fresh();
		S.sheet = 'page';
		builder.setFrames( { header : 'landing' } );
		const steps = S.undo.length;
		const before = JSON.stringify( S.top );
		const same = builder.setFrames( { header : 'landing' } );
		const bad = [ '../x', 'Landing', 'a b', 'a--b', '', '/templates/x' ].map( function( word ) { return builder.setFrames( { header : word } ) } );

		S.level = 1;
		const content = builder.setFrames( { footer : 'none' } );
		S.level = 2;
		S.frame = true;
		const frame = builder.setFrames( { footer : 'none' } );
		S.frame = false;

		return same === false && bad.every( function( one ) { return one === false } ) && content === false && frame === false && S.undo.length === steps && JSON.stringify( S.top ) === before;
	} )() );

check( 'F285. the lightbox "Frames of this page" is the workbench\'s dialog with one choice of tiles for the header and one for the footer - the block of the workbench for tiles, or the same tiles from one place of the feature where it has none -, the chosen tile marked, one main answer that sets both; beside the name of the page, for the one who designs, with an icon and the name as its words',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

		return /Nino\.adminUi\.iconButton\( 'panels-top-left', word\('frames\/page'\)/.test( script ) === true && /S\.sheet === 'page' && S\.frame === false && advanced === false && builds\(\) === true/.test( script ) === true
			&& /api\( 'pageframes', \{\}/.test( script ) === true && /api\( 'pageframetile', \{ side : group\.key, word : item\.value \}/.test( script ) === true && /typeof Nino\.adminUi\.tileChoice === 'function' \? Nino\.adminUi\.tileChoice\( options \) : legacyTiles\( options \)/.test( script ) === true
			&& /const tiles = frameTileChoice\( groups \);/.test( script ) === true && /if\( value === 'set' \)\s*setFrames\( frameValues\( tiles, groups, have \) \)/.test( script ) === true && /content\s*: root,/.test( script ) === true
			&& /api\( 'scaffold', \{ side : side \}/.test( script ) === true && /Nino\.adminUi\.confirm\( 'destructive', \{[\s\S]{0,260}nested\s*: true/.test( script ) === true;
	} )() );

check( 'F285. an arrow key in a group of tiles leads to the tile it points at, in all four directions: Left and Right along the tiles, Up and Down to the line over or under at the middle that is nearest, Home and End to the ends, and nowhere past them',
	( function() {
		const tile = function( left, top ) { return { getBoundingClientRect : function() { return { left : left, top : top, width : 100, height : 60 } } } };
		// Three in a line and two under them: the second line is shifted to the right by a third of a tile
		const tiles = [ tile( 0, 0 ), tile( 110, 0 ), tile( 220, 0 ), tile( 30, 70 ), tile( 140, 70 ) ];
		const go = function( at, key ) { return builder.tileTo( tiles, at, key ) };

		return go( 0, 'ArrowRight' ) === 1 && go( 1, 'ArrowLeft' ) === 0 && go( 4, 'ArrowRight' ) === -1 && go( 0, 'ArrowLeft' ) === -1 && go( 2, 'Home' ) === 0 && go( 0, 'End' ) === 4
			&& go( 0, 'ArrowDown' ) === 3 && go( 1, 'ArrowDown' ) === 4 && go( 2, 'ArrowDown' ) === 4 && go( 3, 'ArrowUp' ) === 0 && go( 4, 'ArrowUp' ) === 1 && go( 0, 'ArrowUp' ) === -1 && go( 4, 'ArrowDown' ) === -1
			&& builder.tileTo( [ {  getBoundingClientRect : function() { return {} } }, { getBoundingClientRect : function() { return {} } } ], 0, 'ArrowDown' ) === 1;
	} )() );

check( 'F285. a frame that the page names and the site does not have marks no tile - the first one is not marked in its place - and a frame that is there marks its own',
	builder.markedFrame( [ 'default', 'landing', 'none' ], 'gone' ) === null && builder.markedFrame( [ 'default', 'landing', 'none' ], 'landing' ) === 'landing' && builder.markedFrame( [ 'default', 'none' ], 'default' ) === 'default' );

check( 'F290. the button that turns the scaffold stands in the group of its side, and the picture of each tile is drawn into the face the block gives, under the key of the frame - side and word - that the tile of a frame is kept by',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

		return /root\.querySelector\( '\.nino-admin-pick-group\[data-group="'\+ side\+ '"\]' \)/.test( script ) === true && /if\( host !== null \)\s*host\.appendChild\( turn \)/.test( script ) === true && /face\.dataset\.block = group\.key\+ ':'\+ item\.value;/.test( script ) === true && /const face = tiles\.faces\[group\.key\]\[item\.value\];/.test( script ) === true;
	} )() );

check( 'F292. the dialog of a source on the sheet of the block of a frame calls the text that is there "Text of the website" and says that it is the website\'s - the same on every page, changed in the panel Texts - and the sheet of any other block keeps the words it had',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

		return /word\( tab === 'pick' && spec\.kind === 'text' && S\.adapter\.frameBlock === true \? 'source\/tab-site' : 'source\/tab-'\+ tab \)/.test( script ) === true && /word\( S\.adapter\.frameBlock === true \? 'source\/elsewhere-block' : 'source\/elsewhere-note' \)/.test( script ) === true;
	} )() );

check( 'F292. a text of a block of a frame has a button that chooses the text of the website - the dialog of a source with the keys of the store - and names the key it reads, with what it says: the text is shown at the component and never written into the block',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

		return /if\( S\.adapter\.frameBlock === true && S\.adapter\.readOnly !== true \) \{\s*const site = Nino\.adminUi\.button\( word\('place\/site'\), function\(\) \{ openSource\( rel, \{ kind : 'text', attr : read\.attr, label : read\.label \}, site \) \}/.test( script ) === true
			&& /if\( S\.adapter\.frameBlock === true && key\.indexOf\( '\/template\/'\+ S\.template\+ '\/' \) !== 0 \)\s*return word\( 'source\/is-key-site', key,/.test( script ) === true && /finish\( \{ form : 'key', written : entry\.key, text : entry\.text \} \)/.test( script ) === true
			&& /S\.shared\[choice\.written\] = choice\.text;/.test( script ) === true;
	} )() );

check( 'F292. the bar says after a save which languages were given the text of the working language, and for which parts - once, as a line - and the dialog of a source marks a text that the website does not otherwise offer openly, for the block of a frame only',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );

		return /if\( Array\.isArray\( response\.filled \) === true && response\.filled\.length > 0 \)\s*S\.view\.bar\.line\.idle\( filledSentence\( response\.filled \) \);/.test( script ) === true
			&& /S\.adapter\.frameBlock === true && entry\.withheld === true \? word\('source\/withheld'\) : ''/.test( script ) === true && /word\( 'msg\/filled', filled\.map/.test( script ) === true;
	} )() );

check( 'F285. the choice does not touch the call of the scaffold that has an attribute: it is not written as the call of a frame when the other side is changed',
	( function() {
		fresh();
		S.sheet = 'page';
		S.top = [ { n : 'template', s : '/templates/html-header', a : { class : 'x' }, c : null }, { t : '\n' }, { n : 'template', s : '/templates/html-footer', a : {}, c : null } ];
		const ok = builder.setFrames( { footer : 'none' } );

		return ok === true && JSON.stringify( S.top[0] ) === JSON.stringify( { n : 'template', s : '/templates/html-header', a : { class : 'x' }, c : null } ) && frameCalls().join() === 'template /templates/html-header,frame /templates/frame-footer-none';
	} )() );

check( 'F285. the scaffold that was turned draws the lightbox anew from what the site says, and the answer names where the old text is kept; the notes of a side that cannot be chosen and of a frame that is missing are the notes of the groups - in a class of the feature only for the tiles of a workbench without the block -, and the (?) of the lightbox holds its explanation',
	( function() {
		const script = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.js' ), 'utf8' );
		const css = require('fs').readFileSync( require('path').join( __dirname, '../assets/admin.css' ), 'utf8' );

		return /reload = true;\s*if\( opened !== null \)\s*opened\.close\( 'cancel' \)/.test( script ) === true && /else if\( reload === true \)\s*openPageFrames\( opener \)/.test( script ) === true && /word\( 'frames\/scaffold-done', answer\.kept \)/.test( script ) === true
			&& /const to = tileTo\( tiles, at, event\.key \);\s*if\( to !== -1 \)/.test( script ) === true && /info\.locked === true/.test( script ) === true && /info\.missing === true/.test( script ) === true && /tools\s*: Nino\.adminUi\.fieldHelp\( word\('frames\/page-note'\)/.test( script ) === true
			&& /\.builder-framechoice-note \{/.test( css ) === true && /nino-admin-pick-set \.nino-admin-hint/.test( css ) === false;
	} )() );

done();
