/**
 *	Nino features
 *	builder-js-smoke.js	What the script of the panel does with a page that is not drawn: its model - the blocks of a tree, the move that never goes
 *											past a locked piece, the copy with the ids of the rule and its words, the steps of the undo - and what it says:
 *
 *											A. the words: every key the script asks for is a fill of both interface languages, and none is written into it
 *											B. the model: a move, a copy, a removal, a text, and twenty mixed steps there and back to the tree they began with
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

context.window = { Nino : { admin : {}, adminUi : { panel : function( spec ) { return spec }, format : function( text ) { return text } }, content : { getText : function() { return '' } } }, document : context.document };
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

done();
