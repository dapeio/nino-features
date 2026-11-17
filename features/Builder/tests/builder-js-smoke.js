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

	// The keys the script puts together: the four things done to a block, the three widths, the two frames
	[ 'up', 'down', 'copy', 'remove' ].forEach( function( key ) { asked.add( 'label/'+ key ) } );
	[ 'copy', 'remove' ].forEach( function( key ) { asked.add( 'label/'+ key+ '-short' ) } );
	[ 'section-wrap', 'header-wrap', 'footer-wrap' ].forEach( function( key ) { asked.add( 'kind/'+ key ) } );
	[ 'mobile', 'tablet', 'desktop', 'frame-header', 'frame-footer' ].forEach( function( key ) { asked.add( 'word/'+ key ) } );
	// The places of the dialog: the categories, the origins and the two ways a place is named
	[ 'all', 'intro', 'content', 'trust', 'offer', 'action', 'contact' ].forEach( function( key ) { asked.add( 'insert/cat-'+ key ) } );
	[ 'basis', 'project' ].forEach( function( key ) { asked.add( 'insert/origin-'+ key ) } );
	[ 'before', 'after' ].forEach( function( key ) { asked.add( 'label/insert-'+ key ) } );
	// The failure of a load is the one the shell's panel() asks for by the name of the panel
	asked.add('error/load');
	Array.from( asked ).filter( function( key ) { return key.slice( -1 ) === '-' } ).forEach( function( key ) { asked.delete( key ) } );

	const missing = Array.from( asked ).filter( function( key ) { return words.en['/_admin/builder/'+ key] === undefined || words.de['/_admin/builder/'+ key] === undefined } );

	check( 'A. every word the script asks for - '+ asked.size+ ' of them - is a fill of the panel in both interface languages', missing.length === 0 && asked.size > 20 );
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
	document : { addEventListener : function() {}, documentElement : { lang : 'en-US' } },
};

context.window = { Nino : { admin : {}, adminUi : { panel : function( spec ) { return spec }, format : function( text ) { return text }, scaleFrame : function() {}, button : function() { return { dataset : {}, addEventListener : function() {}, classList : { add : function() {} }, setAttribute : function() {}, appendChild : function() {}, disabled : false } }, el : function( tag, className ) { const attributes = {}; return { tag : tag, className : className, attributes : attributes, dataset : {}, addEventListener : function() {}, classList : { add : function() {} }, setAttribute : function( name, value ) { attributes[name] = value }, appendChild : function() {} } } }, content : { getText : function() { return '' } } }, document : context.document };
context.Nino = context.window.Nino;
vm.createContext( context );
vm.runInContext( script, context );

const builder = context.window.Nino.admin.builder.model;
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
	S.locale = 'de_DE';
	S.ready = true;
	S.saved = JSON.stringify( [ S.top, S.texts ] );
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
check( 'B. the address of a page is its template without page-, a frame has the name of its side, and a name that is none is no template', builder.addressOf( 'page-home' ) === 'home' && builder.addressOf( 'frame-footer' ) === '@footer' && builder.templateOf( '@header' ) === 'frame-header' && builder.templateOf( 'home' ) === 'page-home' && builder.templateOf( '../x' ) === '' );

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
		slots : [ { name : 'title', scope : '', word : 'title', kind : 'text' }, { name : '1-text', scope : '1', word : 'text', kind : 'text' }, { name : 'background', scope : '', word : 'background', kind : 'image' } ] };
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
	S.view = { bar : { set : function() {} }, buttons : { undo : {}, redo : {} }, split : { settings : { contains : function() { return false }, appendChild : function() {}, querySelectorAll : function() { return [] }, set innerHTML( value ) { seen = JSON.stringify( S.refocus ) } } }, preview : { schedule : function() {} } };
	builder.insert( builder.points()[1], heroBlock(), 'outline' );
	S.view = null;
	return seen !== null && JSON.parse( seen ).from === 'outline' && S.top[JSON.parse( seen ).at].a.id === 'hero-2';
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

done();
