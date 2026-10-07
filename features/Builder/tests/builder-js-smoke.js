/**
 *	Nino
 *	builder-js-smoke.js		What the panel's admin.js does over a stand-in for the page
 *												- no jsdom, no dependency, nothing but node.
 *
 *												The model half is driven as it is: moving a node inside its
 *												level and refusing it across levels, cut and paste, a copy
 *												that takes new ids and new keys, delete; the names of a new
 *												key and what the grammar says of one; the red sources of a
 *												stack that changed; the preview of each viewport; what a
 *												save's answer means and what the two answers to a conflict
 *												send; the unsaved state. All of it over the example page of
 *												the concept (fixtures/page-home.json, which builder-smoke.php
 *												holds to what the Reader makes of page-home.tpl) and the
 *												registry of the kernel's own components (registry.json).
 *
 *												The half that draws is run over an element that accepts
 *												anything: the dialogs, the tree and the preview are built
 *												without a page, the field a form is made of is caught where
 *												it is made - a select, a switch, a number, a line of text -
 *												and its change is called the way the page would call it, so
 *												what a form writes into the model is measured, and so is
 *												what a request carries.
 *
 *	Usage: node features/Builder/tests/builder-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let checks = 0;
let failures = 0;

function check( label, condition ) {
	checks++;
	if( condition === true ) {
		console.log( '  ok  - '+ label );
		return;
	}
	failures++;
	console.log( 'FAIL  - '+ label );
}

const source = fs.readFileSync( path.join( __dirname, '../assets/admin.js' ), 'utf8' );
const fixture = function( name ) { return JSON.parse( fs.readFileSync( path.join( __dirname, 'fixtures', name ), 'utf8' ) ) };

// The words of the panel, as the English file has them
const words = {};
fs.readFileSync( path.join( __dirname, '../text/en_US.php' ), 'utf8' ).split( '\n' ).forEach( function( line ) {
	const found = /^\s*'\[\[(\/[^\]]+)\]\]'\s*=>\s*'(.*)',\s*$/.exec( line );
	if( found !== null )
		words[found[1]] = found[2].replace( /\\'/g, "'" );
} );

/**
 *	An element that accepts whatever the panel does to it: every property is
 *	another such element, a call answers one, an assignment is kept, and the
 *	listeners are kept to be called
 */
function loose() {

	const store = { listeners : {} };
	const cache = {};

	return new Proxy( function() {}, {
		get : function( target, key ) {

			if( key === Symbol.toPrimitive )
				return function() { return '' };

			if( key === 'then' || key === 'nodeType' )
				return undefined;

			if( key === 'addEventListener' )
				return function( type, fn ) { ( store.listeners[type] = store.listeners[type] || [] ).push( fn ) };

			if( key === 'dispatch' )
				return function( type, ev ) { ( store.listeners[type] || [] ).forEach( function( fn ) { fn( ev || { target : {}, preventDefault : function() {}, stopPropagation : function() {} } ) } ) };

			if( key === 'forEach' || key === 'filter' || key === 'map' || key === 'some' || key === 'find' || key === 'indexOf' )
				return function() { return key === 'indexOf' ? -1 : ( key === 'find' ? undefined : [] ) };

			if( Object.prototype.hasOwnProperty.call( store, key ) )
				return store[key];

			return cache[key] = cache[key] || loose();
		},
		set : function( target, key, value ) { store[key] = value; return true },
		apply : function() { return loose() },
	} );
}

let fields = [];
let dialogs = [];
let requests = [];
let questions = [];
let answers = [];
let changes = 0;

const sandbox = {
	document : {
		getElementById : function() { return loose() },
		createElement : function() { return loose() },
		createTextNode : function() { return loose() },
		querySelector : function() { return null },
		querySelectorAll : function() { return [] },
		addEventListener : function() {},
		documentElement : {},
		body : {},
	},
	console : console,
};

sandbox.window = sandbox;
sandbox.Nino = {
	dir : '',
	events : { bindCallback : function() {} },
	content : { getText : function( key ) { return Object.prototype.hasOwnProperty.call( words, key ) ? words[key] : '' } },
	admin : {
		router : { current : function() { return { panel : 'builder', parts : [] } }, set : function() {}, go : function() {}, leave : function( names, leaving, proceed ) { proceed() } },
		dirty : { entry : null, register : function( name, entry ) { this.entry = entry }, refresh : function() {} },
		sessionLocale : { current : 'en_US' },
	},
	adminUi : {
		format : function( text, ...params ) {
			let at = 0;
			return String( text ?? '' ).replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } );
		},
		api : { call : function( action, payload, callback ) { requests.push( { action : action, payload : payload, callback : callback } ) }, errorText : function( status, response, key ) { return '('+ status+ ') '+ key } },
		selectField : function() { return loose() },
		switchField : function() { return loose() },
		numberField : function() { return loose() },
		buttonRow : function() { return function() {} },
		emptyState : function() { return loose() },
		listActions : function() { return loose() },
		notice : function() { return loose() },
		table : function() { return { setRows : function() {} } },
		status : function() {
			const calls = [];
			const status = { state : 'idle', calls : calls };
			[ 'idle', 'saving', 'saved', 'dirty', 'fail', 'error' ].forEach( function( name ) {
				status[name] = function() { status.state = name === 'fail' ? 'error' : name; calls.push( name ) };
			} );
			return status;
		},
		choiceDialog : function( options ) { questions.push( options ); return true },
	},
};

vm.createContext( sandbox );
vm.runInContext( source, sandbox, { filename : 'admin.js' } );

const builder = sandbox.Nino.admin.builder;
const registryFixture = fixture('registry.json');

/**
 *	The registry as the panel gets it: the components and the stacks of the kernel
 *	and a project with one element type, two image slots and the frames
 */
const registry = function() {
	return Object.assign( JSON.parse( JSON.stringify( registryFixture ) ), {
		types		: [
			{ uri : '/services', title : 'Services', fields : { title : { type : 'string' }, summary : { type : 'string', blocks : true }, image : { type : 'image' }, price : { type : 'double' } } },
			{ uri : '/team', title : 'Team', fields : { name : { type : 'string' }, photo : { type : 'image' } } },
		],
		slots		: [ { uri : '/template/page-home/hero/background', label : 'Background', width : 1600, height : 900, hasImage : true, url : '/uploads/hero.1600x900.jpg' }, { uri : '/project/logo/header/image', label : 'Logo', width : 300, height : 100, hasImage : false, url : null } ],
		headers	: [ 'html-header', 'html-header-slim' ],
		footers	: [ 'html-footer' ],
		fieldTypes : [ 'string', 'integer', 'double', 'boolean', 'array', 'date', 'datetime', 'image', 'element' ],
	} );
};

const page = function() { return builder._normalise( fixture('page-home.json') ) };
const known = function() { return [ { key : '/template/page-home/hero/title', global : false, values : { en_US : 'Welcome' } }, { key : '/template/page-home/hero/subtitle', global : true, values : { '*' : 'We build' } } ] };

console.log( 'The model' );

let model = page();

check( 'the model php sends has its empty maps as lists, and they are maps here', Array.isArray( fixture('page-home.json').blocks[0].cols[0].hidden ) === true
	&& JSON.stringify( model.blocks[0].cols[0].hidden ) === '{}' && model.blocks[0].cols[0].components[0].attributes.level === '1' );
check( 'a path names its node: the template, a section, a column, a component, a stack', builder._get( model, [] ) === model && builder._get( model, [ 0 ] ).id === 'hero' && builder._get( model, [ 1, 1 ] ).stack.name === 'stack'
	&& builder._get( model, [ 0, 0, 2 ] ).name === 'button' && builder._get( model, [ 1, 1, 'x' ] ).source === '/services' && builder._get( model, [ 1, 0, 'x' ] ) === null && builder._get( model, [ 9 ] ) === null && builder._get( model, [ 2, 0 ] ) === null );
check( '...and the other way: the node gives its path', builder._same( builder._locate( model, model.blocks[3] ), [ 3 ] ) && builder._same( builder._locate( model, model.blocks[1].cols[1].components[2] ), [ 1, 1, 2 ] )
	&& builder._same( builder._locate( model, model.blocks[1].cols[1].stack ), [ 1, 1, 'x' ] ) && builder._locate( model, {} ) === null );
check( 'what a node is: template, section, a block of html, column, stack, component', [ [], [ 0 ], [ 2 ], [ 0, 0 ], [ 1, 1, 'x' ], [ 0, 0, 1 ] ].map( function( at ) { return builder._kind( model, at ) } ).join() === 'template,section,html,col,stack,component' );

console.log( '\nMoving, cutting, copying, deleting' );

check( 'a section moves to the place of another and the others close up', builder._move( model, [ 0 ], [ 1 ] ) === true && model.blocks.map( function( block ) { return block.id || 'html' } ).join() === 'services,hero,html,contact' );
check( '...and back', builder._move( model, [ 1 ], [ 0 ] ) === true && model.blocks[0].id === 'hero' );
check( 'a column moves inside its section, a component inside its column', builder._move( model, [ 1, 0 ], [ 1, 1 ] ) === true && model.blocks[1].cols[0].stack !== null
	&& builder._move( model, [ 0, 0, 0 ], [ 0, 0, 2 ] ) === true && model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'subtitle,button,title' );
model = page();
check( 'nothing moves across levels: a component into another column, a column into another section, a section into a column, a stack at all',
	builder._move( model, [ 0, 0, 0 ], [ 1, 0, 0 ] ) === false && builder._move( model, [ 0, 0 ], [ 1, 0 ] ) === false && builder._move( model, [ 0 ], [ 0, 0 ] ) === false
	&& builder._move( model, [ 1, 1, 'x' ], [ 1, 1, 0 ] ) === false && builder._move( model, [], [ 0 ] ) === false );
check( '...nor to its own place, nor to one that is not there', builder._move( model, [ 0 ], [ 0 ] ) === false && builder._move( model, [ 0 ], [ 9 ] ) === false && JSON.stringify( model ) === JSON.stringify( page() ) );
check( 'a step up from the first place and down from the last are none, and a step is a move', builder._step( model, [ 0 ], -1 ) === null && builder._step( model, [ 3 ], 1 ) === null && builder._same( builder._step( model, [ 3 ], -1 ), [ 2 ] ) && model.blocks[2].id === 'contact' );
model = page();

const clip = { node : model.blocks[0].cols[0].components[1] };
check( 'what is cut goes down at the end of another column, and leaves its old place', builder._same( builder._paste( model, clip, [ 3, 0 ] ), [ 3, 0, 2 ] ) && model.blocks[0].cols[0].components.length === 2 && model.blocks[3].cols[0].components[2] === clip.node );
check( '...or after a component of the same column, the place it was in taken into account', builder._same( builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 0, 0, 1 ] ), [ 0, 0, 1 ] ) && model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'button,title' );
check( '...a stack counts as its column', builder._same( builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 1, 1, 'x' ] ), [ 1, 1, 4 ] ) );
check( 'a section goes after another, or first where the template is the target', builder._same( builder._paste( model, { node : model.blocks[3] }, [] ), [ 0 ] ) && model.blocks.map( function( block ) { return block.id || 'html' } ).join() === 'contact,hero,services,html'
	&& builder._same( builder._paste( model, { node : model.blocks[0] }, [ 1 ] ), [ 1 ] ) && model.blocks[0].id === 'hero' );
check( 'a column goes at the end of a section, or after a column - and not into a block of html', builder._same( builder._paste( model, { node : model.blocks[1].cols[0] }, [ 0 ] ), [ 0, 1 ] ) && model.blocks[0].cols.length === 2
	&& builder._paste( model, { node : model.blocks[0].cols[0] }, [ 3 ] ) === null && model.blocks[0].cols.length === 2 );
check( 'what cannot go down there is refused and nothing moves: a component on a section, on a block of html; what was deleted since; a stack',
	builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 0 ] ) === null && builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 3 ] ) === null
	&& builder._paste( model, { node : {} }, [ 0, 0 ] ) === null && builder._paste( model, null, [ 0, 0 ] ) === null && builder._paste( model, { node : model.blocks[2].cols[1].stack }, [ 0, 0 ] ) === null );
const held = JSON.stringify( model );
check( '...and a dry run only says whether it would', builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 0, 1 ], true ) !== null
	&& builder._paste( model, { node : model.blocks[0].cols[0].components[0] }, [ 0 ], true ) === null && JSON.stringify( model ) === held );

model = page();

check( 'a copy of a section takes an id of its own, and keys and slots of its own where the original has them: new ones, with the text the key has now',
	( function() {
		const at = builder._duplicate( model, registry(), 'page-home', [ 0 ], function( key ) { return key === '/template/page-home/hero/title' ? 'Welcome' : '' } );
		const copy = model.blocks[1];
		return builder._same( at, [ 1 ] ) && copy.id === 'hero-2' && model.blocks[0].id === 'hero' && model.blocks.length === 5
			&& copy.cols[0].components[0].source === '/template/page-home/hero-2/title' && copy.cols[0].components[0].create.value === 'Welcome'
			&& copy.cols[0].components[1].source === '/template/page-home/hero-2/subtitle' && copy.cols[0].components[1].create.value === 'Subtitle'
			&& copy.background.slot === '/template/page-home/hero-2/background' && copy.background.create.label === 'Background' && copy.background.focus === 5
			&& copy.cols[0].components[2].source === '/_nino/webpage/contact/name' && copy.cols[0].components[2].create === undefined
			&& model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' && model.blocks[0].cols[0].components[0].create === undefined && model.blocks[0].background.create === undefined;
	} )() );
check( '...a second copy takes the next id', builder._same( builder._duplicate( model, registry(), 'page-home', [ 0 ], function() { return '' } ), [ 1 ] ) && model.blocks[1].id === 'hero-3' );
model = page();
check( 'a copy of a component in its section has a name beside the original (title-2), and one that is not the section\'s own keeps its source', ( function() {
	const first = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return 'Welcome' } );
	const second = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return 'Welcome' } );
	const third = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 4 ], function() { return '' } );
	const components = model.blocks[0].cols[0].components;
	return builder._same( first, [ 0, 0, 1 ] ) && components[1].source === '/template/page-home/hero/title-3' && components[1].create.value === 'Welcome' && builder._same( second, [ 0, 0, 1 ] )
		&& components[2].source === '/template/page-home/hero/title-2' && components[0].source === '/template/page-home/hero/title' && components[0].create === undefined && builder._same( third, [ 0, 0, 5 ] ) && components[5].source === '/_nino/webpage/contact/name' && components[5].create === undefined;
} )() );
model = page();
check( 'a copy of a column takes new keys beside the original for every component, and the copy shares nothing with it', ( function() {
	builder._duplicate( model, registry(), 'page-home', [ 0, 0 ], function() { return '' } );
	const copy = model.blocks[0].cols[1];
	copy.components[0].attributes.level = '3';
	return copy.components[0].source === '/template/page-home/hero/title-2' && copy.components[1].source === '/template/page-home/hero/subtitle-2' && model.blocks[0].cols[0].components[0].attributes.level === '1' && model.blocks[0].cols.length === 2;
} )() );
check( 'a block of html is copied as it is, a stack cannot be copied', ( function() {
	const html = builder._duplicate( model, registry(), 'page-home', [ 2 ], function() { return '' } );
	return builder._same( html, [ 3 ] ) && model.blocks[3].kind === 'html' && model.blocks[3].source === model.blocks[2].source && model.blocks[3] !== model.blocks[2] && builder._duplicate( model, registry(), 'page-home', [ 1, 1, 'x' ], function() { return '' } ) === null;
} )() );

model = page();
check( 'delete takes a section, a column or a component out, and a stack is not deleted this way', builder._remove( model, [ 1, 1, 0 ] ) === true && model.blocks[1].cols[1].components.length === 3 && builder._remove( model, [ 1, 1, 'x' ] ) === false
	&& builder._remove( model, [ 2 ] ) === true && model.blocks.length === 3 && builder._remove( model, [ 0, 0 ] ) === true && model.blocks[0].cols.length === 0 && builder._remove( model, [ 9 ] ) === false && builder._remove( model, [] ) === false );

console.log( '\nNames and keys' );

model = page();
const hero = model.blocks[0];
check( 'a word is a segment: lower case, a hyphen for the rest', builder._segment( 'Hello World!' ) === 'hello-world' && builder._segment( '  --Title_2 ' ) === 'title-2' && builder._segment( '###' ) === '' );
check( 'a new key is named by the kind of the component, and numbered where the section has the name (title, title-2)', builder._keyName( hero, 'page-home', 'title' ) === 'title-2' && builder._keyName( hero, 'page-home', 'button' ) === 'button'
	&& builder._keyName( hero, 'page-home', 'background' ) === 'background-2' && builder._keyName( model.blocks[3], 'page-home', 'title' ) === 'title-2' && builder._keyName( hero, 'page-home', '###' ) === 'item' );
check( '...and where the project has the key without the file knowing it', builder._keyName( hero, 'page-home', 'button', [ { key : '/template/page-home/hero/button' }, { key : '/template/page-home/hero/button-2' } ] ) === 'button-3' && builder._keyName( hero, 'page-home', 'text', [ { key : '/template/page-home/other/text' } ] ) === 'text' );
check( 'the key of a name is /template/<file>/<section>/<name>, and the grammar holds it', builder._keyUri( 'page-home', 'hero', 'cta' ) === '/template/page-home/hero/cta' && builder.KEY.test( '/template/page-home/hero/cta' ) === true
	&& builder.KEY.test( '/template/page-home/hero/Cta' ) === false && builder.KEY.test( '/template/page-home/hero' ) === false && builder.KEY.test( '/_nino/webpage/contact/name' ) === false && builder.KEY.test( '/template/a/b/c/d' ) === false );
check( 'a name that will not do says why: the grammar, or a name the section has', builder._nameProblem( hero, 'page-home', 'cta' ) === '' && builder._nameProblem( hero, 'page-home', 'Bad Name' ) === 'grammar' && builder._nameProblem( hero, 'page-home', '' ) === 'grammar'
	&& builder._nameProblem( hero, 'page-home', 'title' ) === 'taken' && builder._nameProblem( hero, 'page-home', 'cta', [ { key : '/template/page-home/hero/cta' } ] ) === 'taken' && builder._nameProblem( hero, 'page-home', 'a--b' ) === 'grammar' );

const reg = registry();
check( 'a new section has a free id, one column over the whole width, nothing in it', ( function() {
	const first = builder._newSection( model );
	model.blocks.push( first );
	const second = builder._newSection( model );
	return first.id === 'section' && second.id === 'section-2' && first.cols.length === 1 && JSON.stringify( first.cols[0].width ) === '{"s":100,"m":100,"l":100}' && first.cols[0].components.length === 0 && first.settings.vpa === null && first.background === null;
} )() );
check( 'a new component has the attributes of its schema, and in a static column a key of its own that is made at save', ( function() {
	const title = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'title', [] );
	const image = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'image', [] );
	const html = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'html', [] );
	const spacer = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'spacer', [] );
	return title.source === '/template/page-home/hero/title-2' && title.create.value === 'Title' && title.attributes.level === '2' && title.text === null
		&& image.source === '/template/page-home/hero/image' && image.create.label === 'Image' && image.create.width === 1600 && html.content === '' && html.source === '' && html.create === undefined && spacer.source === '' && spacer.create === undefined;
} )() );
check( '...and in a stack none: its source is a field, chosen in the form', builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).source === '' && builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).create === undefined );

console.log( '\nThe red source' );

model = page();
check( 'the example page has no source that means nothing where it stands', builder._red( model, reg ).length === 0 );
model.blocks[1].cols[1].stack = null;
check( 'a stack changed to static: every field of the element is red in that column - and nothing else', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return [ entry.path.join('.'), entry.source, entry.why ] } ) )
	=== JSON.stringify( [ [ '1.1.0', 'image', 'static' ], [ '1.1.1', 'title', 'static' ], [ '1.1.2', 'summary', 'static' ], [ '1.1.3', '.uri', 'static' ] ] ) );
model.blocks[1].cols[1].stack = { name : 'stack', source : '/services', attributes : { cols : '100 50 50' } };
check( 'and the stack back: nothing is red', builder._red( model, reg ).length === 0 );
model.blocks[1].cols[1].stack.source = '/team';
check( 'a stack of a type that has not the fields: the fields it has not are red, the ones it has (.uri, a key) are not', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return entry.source+ ':'+ entry.why } ) ) === JSON.stringify( [ 'image:field', 'title:field', 'summary:field' ] ) );
model.blocks[1].cols[1].stack.source = '/nowhere';
check( 'a stack of a type the Elements panel does not know is red itself, and its components are not judged by it', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return entry.path.join('.')+ ':'+ entry.why } ) ) === JSON.stringify( [ '1.1.x:type' ] ) );
model.blocks[1].cols[1].stack.source = '/services';
model.blocks[1].cols[1].components[1].source = '/template/page-home/services/title';
model.blocks[1].cols[1].components[2].source = '';
check( 'a key stands anywhere, and no source is none', builder._red( model, reg ).length === 0 );
model.blocks[0].cols[0].components[0].source = 'title';
check( 'a field name in a column that has no stack is red, and nothing else on the page is', builder._red( model, reg ).length === 1 && builder._red( model, reg )[0].why === 'static' && builder._same( builder._red( model, reg )[0].path, [ 0, 0, 0 ] ) );

console.log( '\nThe preview' );

model = page();
const plan = function( viewport ) { return builder._preview( model, reg, viewport ) };
check( 'a section is a frame with its colour and whether a picture stands behind it, a block of html a frame of its own', plan('l').map( function( block ) { return block.kind+ ':'+ block.id+ ':'+ block.color+ ':'+ block.background } ).join()
	=== 'section:hero:black:true,section:services::false,html:::false,section:contact:primary:false' );
check( 'the columns have the width of the viewport', plan('l')[0].cols[0].width === 66 && plan('m')[0].cols[0].width === 100 && plan('s')[0].cols[0].width === 100 && plan('l')[1].cols.map( function( col ) { return col.width } ).join() === '50,50' );
model.blocks[1].cols[0].width = { s : 100, m : 50, l : 25 };
model.blocks[1].cols[0].hidden = { s : true, m : true };
check( 'and so has their visibility: a hidden column is hidden in its viewports, drawn all the same', plan('s')[1].cols[0].hidden === true && plan('m')[1].cols[0].hidden === true && plan('l')[1].cols[0].hidden === false
	&& plan('s')[1].cols[0].width === 100 && plan('m')[1].cols[0].width === 50 && plan('l')[1].cols[0].width === 25 && plan('s')[1].cols[0].components.length === 2 );
check( 'a viewport changes widths and visibility and nothing else', ( function() {
	const strip = function( viewport ) {
		return JSON.stringify( plan( viewport ).map( function( block ) { return [ block.path, block.kind, block.id, block.color, block.cols.map( function( col ) { return [ col.path, col.components.map( function( component ) { return [ component.path, component.name, component.image ] } ) ] } ) ] } ) );
	};
	return strip('s') === strip('m') && strip('m') === strip('l');
} )() );
check( 'a component is a placeholder with the image the registry names - the kernel\'s five, and block for the rest', plan('l')[0].cols[0].components.map( function( component ) { return component.image+ ':'+ component.label } ).join() === 'title:Title,title:Subtitle,button:Button' );
model.blocks[0].cols[0].components.push( { name : 'unknown-one', source : '', text : null, attributes : {} } );
model.blocks[0].cols[0].components.push( { name : 'spacer', source : '', text : null, attributes : {} } );
check( '...a component the registry does not know and one that names no image are blocks, labelled with what they are', plan('l')[0].cols[0].components.slice( 3 ).map( function( component ) { return component.image+ ':'+ component.label } ).join() === 'block:unknown-one,block:Spacer' );
check( 'a stack is cells in the widths of its own for the viewport, the first of its column\'s components as the stack\'s path', ( function() {
	const stack = function( viewport ) { return plan( viewport )[1].cols[1].stack };
	return stack('s').cell === 100 && stack('m').cell === 50 && stack('l').cell === 50 && stack('l').label === 'Stack' && stack('l').source === '/services' && stack('l').image === 'cells' && builder._same( stack('l').path, [ 1, 1, 'x' ] )
		&& plan('l')[1].cols[0].stack === null;
} )() );
model.blocks[1].cols[1].stack.attributes.cols = '100';
check( 'a stack that names fewer widths than viewports takes the last one for the others', plan('l')[1].cols[1].stack.cell === 100 );
model.blocks[1].cols[1].stack.name = 'slider';
check( 'a stack without a grid is said to be one', plan('l')[1].cols[1].stack.grid === false );

console.log( '\nUnsaved changes and saving' );

model = page();
let doc = { file : 'page-home', hash : 'h1', model : model, saved : JSON.stringify( model ), usedBy : [] };
check( 'a document as loaded holds nothing unsaved, and none at all holds none', builder._unsaved( doc ) === false && builder._unsaved( null ) === false );
model.blocks[0].id = 'start';
check( 'a change makes it unsaved...', builder._unsaved( doc ) === true );
model.blocks[0].id = 'hero';
check( '...and the change taken back makes it saved again', builder._unsaved( doc ) === false );
model.blocks[0].cols[0].hidden.s = true;
check( 'a column made hidden is a change, and made visible again is none', builder._unsaved( doc ) === true && ( delete model.blocks[0].cols[0].hidden.s ) && builder._unsaved( doc ) === false );
check( 'the request of a save is the file, the model and the hash, and force only where it is asked', JSON.stringify( Object.keys( builder._saveRequest( doc, false ) ) ) === '["file","model","hash"]' && builder._saveRequest( doc, true ).force === true && builder._saveRequest( doc, true ).hash === 'h1' );
check( 'what an answer means: saved, a conflict, a model that is not valid with its problems, the rest an error', builder._outcome( 200, { model : {}, hash : 'x' } ).kind === 'saved' && builder._outcome( 409, { code : 'builder_conflict' } ).kind === 'conflict'
	&& builder._outcome( 400, { code : 'builder_invalid', params : [ 'a', 'b' ] } ).problems.join() === 'a,b' && builder._outcome( 400, { code : 'builder_value', params : [ 'c' ] } ).kind === 'invalid'
	&& builder._outcome( 409, { code : 'builder_key_exists', params : [ 'k' ] } ).kind === 'error' && builder._outcome( 409, { code : 'builder_key_exists', params : [ 'k' ] } ).problems.join() === 'k'
	&& builder._outcome( 500, null ).kind === 'error' && builder._outcome( 200, null ).kind === 'error' && builder._outcome( 403, { code : 'builder_permission' } ).kind === 'error' );
check( 'a conflict is answered by Reload (the file as it is, the changes dropped), by Save anyway (the same model, forced), or by Cancel (nothing)', JSON.stringify( builder._conflictRequest( doc, 'reload' ) ) === JSON.stringify( { action : 'builder/load', payload : { file : 'page-home' } } )
	&& builder._conflictRequest( doc, 'force' ).action === 'builder/save' && builder._conflictRequest( doc, 'force' ).payload.force === true && builder._conflictRequest( doc, 'force' ).payload.model === model && builder._conflictRequest( doc, 'cancel' ) === null );
check( 'a refused save names its blocks by the id of the section or the number of the block', ( function() {
	const blame = builder._blame( model, [ 'the section "services": the source "x" means nothing', 'block 3 is neither a section nor a block of html', 'the name is one line', 'the section "gone" is not valid', 'block 9 is no block' ] );
	return blame.blocks[1].length === 1 && blame.blocks[2].length === 1 && blame.general.length === 3 && blame.blocks[0] === undefined;
} )() );
check( 'a reason is said in the words of the panel, with its line and its detail', builder._reasonText( { line : 3, code : 'second-row', detail : '', text : 'x' } ) === 'Line 3: The section has more than one row'
	&& builder._reasonText( { line : 7, code : 'section-attribute', detail : 'onclick', text : 'x' } ) === 'Line 7: The section has the attribute onclick, which the Builder does not keep'
	&& builder._reasonText( { line : 0, code : 'new-code', detail : '', text : 'its own sentence' } ) === 'its own sentence' );
check( 'an error is said in the panel\'s own sentence for the code the server gave, else the workbench\'s', builder._errorText( 409, { code : 'builder_conflict' }, '/x' ) === words['/_admin/builder/error/conflict']
	&& builder._errorText( 409, { code : 'builder_key_exists' }, '/x' ) === words['/_admin/builder/error/key-exists'] && builder._errorText( 500, { code : 'builder_nothing_like_it' }, '/x' ) === '(500) /x' && builder._errorText( 500, null, '/x' ) === '(500) /x' );

console.log( '\nThe panel at work' );

const registered = sandbox.Nino.admin.dirty.entry;
check( 'the panel registers with the shell\'s unsaved input: a question of whether, a save, a discard, the bar', registered !== null && typeof registered.isDirty === 'function' && typeof registered.save === 'function' && typeof registered.discard === 'function' && typeof registered.bar === 'function' );

// What stands where the page would draw: the document, as _openEditor() makes it
const open = function( withModel ) {

	const loaded = withModel === undefined ? page() : withModel;

	builder._registry = registry();
	builder._doc = { file : 'page-home', hash : 'h1', model : loaded, saved : JSON.stringify( loaded ), usedBy : [ { route : 'GET://', uri : '/home' }, { route : 'GET://about', uri : '/about' } ] };
	builder._sel = [];
	builder._clip = null;
	builder._fresh = {};
	builder._problems = [];
	builder._folded = {};
	builder._saving = false;
	builder._keys = known();
	builder._status = sandbox.Nino.adminUi.status();
	requests = [];
	questions = [];
	changes = 0;
	fields = [];
	dialogs = [];

	return builder._doc;
};

// The places a form is made in are caught: what it makes is called as the page would call it
const keep = {};
[ '_dialog', '_refreshTab', '_closeDialog', '_dialogProblem', '_changed' ].forEach( function( name ) { keep[name] = builder[name] } );
[ '_selectField', '_switchField', '_numberField', '_textField' ].forEach( function( name ) {
	keep[name] = builder[name];
	builder[name] = function() {
		fields.push( { kind : name.slice( 1, -5 ), label : arguments[0], hint : arguments[1], args : Array.prototype.slice.call( arguments ) } );
		return loose();
	};
} );
builder._dialog = function( options ) {
	dialogs.push( options );
	if( Array.isArray( options.tabs ) === true )
		options.tabs.forEach( function( tab ) { tab.build( loose() ) } );
	else
		options.build( loose() );
};
builder._refreshTab = function( id ) { dialogs[dialogs.length - 1].tabs.find( function( tab ) { return tab.id === id } ).build( loose() ) };
builder._closeDialog = function() {};
const said = [];
builder._dialogProblem = function( text ) { said.push( text ) };

const afterwards = keep._changed;
builder._changed = function() { changes++; builder._problems = []; afterwards.call( builder ) };

const last = function() { return dialogs[dialogs.length - 1] };
const field = function( label ) { return fields.filter( function( entry ) { return entry.label === label } ).pop() };
const choose = function( label, value ) {
	const entry = field( label );
	entry.args[ entry.kind === 'select' ? 4 : ( entry.kind === 'switch' ? 3 : ( entry.kind === 'number' ? 5 : 3 ) ) ]( value );
};

doc = open();
builder._renderEditor();
check( 'the editor draws itself from the model: the tree, the preview, the bar, without a page to complain', builder._doc.model.blocks.length === 4 && builder._status.state === 'idle' && builder._unsaved( builder._doc ) === false );
check( 'a section and a column are drawn with settings of their own, the template with a name and two frames', ( function() {
	fields = [];
	builder._openSettings( [] );
	return dialogs.length === 1 && fields.map( function( entry ) { return entry.label } ).join() === 'Name,Header,Footer' && fields[1].args[2].map( function( option ) { return option.value } ).join() === ',html-header,html-header-slim';
} )() );

fields = [];
builder._openSettings( [ 0 ] );
check( 'the form of a section is in tabs, and writes into the model as it is changed', last().tabs.map( function( tab ) { return tab.id } ).join() === 'general,background,spacing,animation,custom' && ( function() {
	choose( 'Colour', 'dark' );
	choose( 'Margin above', '3' );
	choose( 'Animation', 'zoom-soft' );
	choose( 'Dim the picture', false );
	choose( 'Height of the cover', null );
	const settings = builder._doc.model.blocks[0].settings;
	return settings.color === 'dark' && settings.mt === '3' && settings.vpa === 'zoom-soft' && settings.dim === false && settings.cover === null && changes === 5 && builder._unsaved( builder._doc ) === true;
} )() );
choose( 'Animation', 'none' );
check( '...the animation is none, the plain one or an effect: null, an empty string, its name', builder._doc.model.blocks[0].settings.vpa === null && ( choose( 'Animation', 'plain' ), builder._doc.model.blocks[0].settings.vpa === '' ) );
check( '...and the line over the bar says there is something to save', builder._status.state === 'dirty' );

fields = [];
builder._openSettings( [ 0, 0 ] );
check( 'the form of a column: a width and a visibility for each viewport, alignment, the stack, the animation, the classes', last().tabs.map( function( tab ) { return tab.id } ).join() === 'layout,stack,animation,custom' && ( function() {
	choose( 'Width, Phone', '50' );
	choose( 'Width, Desktop', '25' );
	choose( 'Hidden, Tablet', true );
	choose( 'Hidden, Phone', true );
	choose( 'Hidden, Phone', false );
	const col = builder._doc.model.blocks[0].cols[0];
	return col.width.s === 50 && col.width.l === 25 && col.width.m === 100 && JSON.stringify( col.hidden ) === '{"m":true}';
} )() );

fields = [];
builder._openSettings( [ 1, 1, 'x' ] );
check( 'the stack of a column opens the form of the column on its own tab, and has the form of a stack: its type, its loop, its grid, its id', last().tab === 'stack' && field( 'Type' ) !== undefined && field( 'Sort by' ) !== undefined && field( 'Limit' ) !== undefined
	&& field( 'Query' ) !== undefined && field( 'Cell width, Phone' ) !== undefined && field( 'Gap between the cells' ) !== undefined && field( 'Cells of equal height' ) !== undefined && field( 'Id of the stack' ) !== undefined );
check( '...the type is one of the Elements panel\'s, with the title it has there', field( 'Type' ).args[2].map( function( option ) { return option.value+ '='+ option.label } ).join() === '/services=Services (/services),/team=Team (/team)' );
choose( 'Sort by', 'summary' );
choose( 'Direction', 'desc' );
choose( 'Limit', 12 );
choose( 'Cell width, Tablet', '33' );
choose( 'Cells of equal height', false );
choose( 'Gap between the cells', '4' );
check( 'what the form writes is what the call carries: a field and a direction as the loop reads them, the numbers as text, the cells as three widths', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.attributes.sort === '-summary' && stack.attributes.limit === '12' && stack.attributes.cols === '100 33 50' && stack.attributes.autoheight === '0' && stack.attributes.gap === '4';
} )() );
choose( 'Type', '/team' );
check( 'a stack of another type leaves the components where they are - what their sources mean there is red until changed', builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 3 );
choose( 'Stack', '' );
check( 'Static drops the stack and keeps the components, and every field of the element is red', builder._doc.model.blocks[1].cols[1].stack === null && builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 4 );
choose( 'Stack', 'slider' );
check( 'a registered stack comes with its own attributes and the loop it has, over a type of the project', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.name === 'slider' && stack.source === '/services' && stack.attributes.width === '75%' && stack.attributes.limit === '0' && builder._red( builder._doc.model, builder._registry ).length === 0;
} )() );
choose( 'Stack', 'stack' );
check( '...and going on to another stack keeps what both have', ( function() {
	builder._doc.model.blocks[1].cols[1].stack.attributes.limit = '5';
	choose( 'Stack', 'list' );
	return builder._doc.model.blocks[1].cols[1].stack.name === 'list' && builder._doc.model.blocks[1].cols[1].stack.attributes.limit === '5' && builder._doc.model.blocks[1].cols[1].stack.source === '/services';
} )() );

doc = open();
fields = [];
builder._openSettings( [ 0, 0, 0 ] );
check( 'the form of a component has its source, then the attributes of its schema, then the classes of its own', fields.map( function( entry ) { return entry.label } ).join() === 'Fixed value,Level,Style,Custom classes' );

// The source field is looked at where the component form makes it
const sources = [];
const realSource = builder._sourceField;
builder._sourceField = function( options ) { sources.push( options ); return loose() };
builder._openSettings( [ 0, 0, 0 ] );
check( 'a component of the text kind is given a source field of the text kind, over the section it stands in', sources[0].kind === 'text' && sources[0].value === '/template/page-home/hero/title' && sources[0].section.id === 'hero' && sources[0].fields === null && sources[0].text === null && sources[0].label === 'Title' && sources[0].name === 'title' );
sources[0].onPick( '/template/page-home/hero/title-2', { value : 'Welcome' } );
check( 'picking a source sets it, with what the key is made with where it is new', builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title-2' && builder._doc.model.blocks[0].cols[0].components[0].create.value === 'Welcome' );
sources[0].onPick( '/template/page-home/hero/title', null );
check( '...and an old one takes the instruction to make it away again', builder._doc.model.blocks[0].cols[0].components[0].create === undefined );
sources[0].onFixed( 'Mehr' );
check( 'a fixed value is the text of the call and no source', builder._doc.model.blocks[0].cols[0].components[0].text === 'Mehr' && builder._doc.model.blocks[0].cols[0].components[0].source === '' );
sources[0].onPick( '/template/page-home/hero/title', null );
check( '...a source after it is a source again, and no fixed value', builder._doc.model.blocks[0].cols[0].components[0].text === null && builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' );
sources.length = 0;
builder._openSettings( [ 1, 1, 0 ] );
check( 'in a stack the source field has the fields of the type, and the image component a field of the image kind', sources[0].kind === 'image' && Object.keys( sources[0].fields ).join() === 'title,summary,image,price' && sources[0].value === 'image' );
sources.length = 0;
builder._openSettings( [ 1, 1, 3 ] );
check( '...the button\'s source is of the text kind, with the value and the fixed text it has', sources[0].kind === 'text' && sources[0].value === '.uri' && sources[0].text === 'Mehr' );
builder._sourceField = realSource;

doc = open();
fields = [];
const htmlBefore = dialogs.length;
builder._openSettings( [ 2 ] );
check( 'a block of html has no form: it has the editor, which is a dialog of its own, titled as such', dialogs.length === htmlBefore + 1 && last().title === words['/_admin/builder/html/title'] && fields.length === 0 );

// What the editor of the block shows is caught where it is made
const areas = [];
const realOne = builder._one;
builder._one = function( root, selector ) {
	const found = realOne.call( builder, root, selector );
	if( selector === '.builder-html-source' )
		areas.push( found );
	return found;
};
doc = open();
builder._openSettings( [ 2 ] );
builder._one = realOne;
check( '...with the source of the block in it, and applying it sends that source to be read again', areas.length === 1 && areas[0].value === builder._doc.model.blocks[2].source && ( last().actions[0].onClick( function() {} ), requests.length === 1
	&& requests[0].action === 'builder/source' && requests[0].payload.model.blocks[2].source === builder._doc.model.blocks[2].source && requests[0].payload.model.blocks[2].edited === true ) );

const realFragment = builder._fragment;
builder._fragment = function( name ) { return name === 'skeleton' ? { outerHTML : '<div>\n</div>' } : realFragment.call( builder, name ) };
builder._one = function( root, selector ) {
	const found = realOne.call( builder, root, selector );
	if( selector === '.builder-html-source' )
		areas.push( found );
	return found;
};
doc = open();
areas.length = 0;
builder._editBlock( null );
builder._one = realOne;
builder._fragment = realFragment;
check( 'a new block of html starts as the skeleton of the template, which is no section, and is titled as a new one', last().title === words['/_admin/builder/html/title-new'] && areas.length === 1 && areas[0].value === '<div>\n</div>' );
last().actions[0].onClick( function() {} );
check( '...and is applied as a block of html without a reason, to be read again', requests.length === 1 && requests[0].payload.model.blocks[4].kind === 'html' && requests[0].payload.model.blocks[4].reason === null && requests[0].payload.model.blocks[4].edited === true );

console.log( '\nThe editor of an [html] component' );

doc = open();
const content = { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>Hello <strong>world</strong></p>' };
builder._doc.model.blocks[0].cols[0].components.push( content );
const made = [];
sandbox.Nino.admin.htmlEditor = { create : function( mount, value, maxlength, rows, format ) {
	const editor = { value : value, mount : mount, maxlength : maxlength, format : format, getValue : function() { return editor.value }, destroy : function() {} };
	made.push( editor );
	return editor;
} };
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
check( 'the content of an [html] component is edited in the workbench\'s HTML editor, in the blocks format, with a limit above none (an editor of none trims every edit to nothing)', made.length === 1
	&& made[0].value === '<p>Hello <strong>world</strong></p>' && made[0].format === 'blocks' && made[0].maxlength > 0 && made[0].maxlength === builder.CONTENT_MAX );
made[0].value = '<p>Hello again</p>';
made[0].mount.dispatch( 'input' );
check( '...and what is typed goes into the component, which is a change', content.content === '<p>Hello again</p>' && changes === 1 && builder._unsaved( builder._doc ) === true );
const dialogOfContent = last();
dialogOfContent.onClose();
check( '...and closed after typing it stays what was typed', content.content === '<p>Hello again</p>' );

doc = open();
const plain = { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<h2>Heading</h2>' };
builder._doc.model.blocks[0].cols[0].components.push( plain );
builder._doc.saved = JSON.stringify( builder._doc.model );
made.length = 0;
const changesBefore = changes;
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
made[0].value = '<p>Heading</p>';
last().onClose();
check( 'a dialog of an [html] component opened and closed without typing leaves the content byte for byte (the editor\'s normal form of <h2>Heading</h2> is another) and the document is not unsaved', plain.content === '<h2>Heading</h2>' && changes === changesBefore && builder._unsaved( builder._doc ) === false );
made.length = 0;
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
made[0].value = '<h2>Heading!</h2>';
made[0].mount.dispatch( 'input' );
last().onClose();
check( '...and one with typing writes the value, and that is a change', plain.content === '<h2>Heading!</h2>' && changes > changesBefore && builder._unsaved( builder._doc ) === true );
delete sandbox.Nino.admin.htmlEditor;

console.log( '\nThe link to the real page' );

doc = open();
const anchor = { hidden : true, href : '' };
builder._pageLink( anchor );
check( 'the link goes to the address the first route serves the page at (GET:// is /), not to the uri of the route\'s webpage (/home, which is none)', anchor.hidden === false && anchor.href === '/' );
builder._sel = [ 1, 0 ];
builder._pageLink( anchor );
check( '...with the id of the section the selection is in, as an anchor', anchor.href === '/#services' );
builder._doc.usedBy = [ { route : 'GET://legal/imprint', uri : '/legal/imprint' }, { route : 'POST://form', uri : '/form' } ];
builder._pageLink( anchor );
check( '...a page of a path is its path, and a route that is no GET is no page', anchor.href === '/legal/imprint#services' && builder._addresses( builder._doc.usedBy ).join() === '/legal/imprint' );
builder._doc.usedBy = [ { route : 'POST://form', uri : '/form' } ];
builder._pageLink( anchor );
check( '...and where no route answers a GET there is no link', anchor.hidden === true && builder._addresses( [ { route : 'GET://', uri : '/home' }, { route : 'GET://', uri : '/start' }, { route : 'GET://x', uri : '' } ] ).join() === '/,/x' );

console.log( '\nThe changes the tree makes' );

doc = open();
const clipped = builder._doc.model.blocks[0].cols[0].components[2];
builder._clip = { node : clipped };
builder._pasteAt( [ 3, 0 ] );
check( 'a paste puts the node down, selects it and forgets the clipboard', builder._doc.model.blocks[3].cols[0].components.indexOf( clipped ) === 2 && builder._clip === null && builder._same( builder._sel, [ 3, 0, 2 ] ) && changes === 1 );
check( '...a menu says whether there is something to paste, and where', ( function() {
	builder._clip = { node : builder._doc.model.blocks[0].cols[0].components[0] };
	return builder._canPaste( [ 3, 0 ] ) === true && builder._canPaste( [ 3 ] ) === false && builder._canPaste( [ 2 ] ) === false && ( builder._clip = null, builder._canPaste( [ 3, 0 ] ) === false );
} )() );
builder._duplicateAt( [ 0 ] );
check( 'a copy of a section is a new section: its keys are new, so it may be renamed without moving anything', builder._doc.model.blocks[1].id === 'hero-2' && builder._fresh['hero-2'] === true && builder._fresh['hero'] !== true );
builder._sel = [ 0 ];
builder._delete( [ 1 ] );
check( 'a delete selects the parent and drops what was cut with the node', builder._doc.model.blocks.length === 4 && builder._same( builder._sel, [] ) );
builder._clip = { node : builder._doc.model.blocks[0].cols[0] };
builder._delete( [ 0, 0 ] );
check( '...the clipboard of a column that was deleted is empty', builder._clip === null );
builder._addSection();
builder._addCol( [ 0 ] );
builder._addComponent( [ 0, 0 ], 'button' );
check( 'a new section is at the end and fresh, a column joins its section, a component its column - with its key', builder._doc.model.blocks[4].id === 'section' && builder._fresh.section === true && builder._doc.model.blocks[0].cols.length === 1
	&& builder._doc.model.blocks[0].cols[0].components.length === 1 && builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/button' && builder._doc.model.blocks[0].cols[0].components[0].create.value === 'Button' );
doc = open();
builder._rename( [ 0 ], 'start' );
check( 'a section that was saved and is renamed says from where: the server moves its keys, and the sources stay as they are', builder._doc.model.blocks[0].id === 'start' && builder._doc.model.blocks[0].renamedFrom === 'hero'
	&& builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' );
builder._rename( [ 0 ], 'intro' );
check( '...a second rename keeps the first name, and the way back to it is no rename at all', builder._doc.model.blocks[0].renamedFrom === 'hero' && ( builder._rename( [ 0 ], 'hero' ), builder._doc.model.blocks[0].renamedFrom === undefined ) );
builder._addSection();
builder._addComponent( [ 4, 0 ], 'title' );
builder._rename( [ 4 ], 'faq' );
check( 'a section made here and renamed is written new: nothing was ever saved under its old name', builder._doc.model.blocks[4].id === 'faq' && builder._doc.model.blocks[4].renamedFrom === undefined && builder._doc.model.blocks[4].cols[0].components[0].source === '/template/page-home/faq/title'
	&& builder._doc.model.blocks[4].cols[0].components[0].create.value === 'Title' && builder._fresh.faq === true && builder._fresh.section === undefined );

console.log( '\nThe form of the id' );

doc = open();
fields = [];
builder._openSettings( [ 1 ] );
const idField = fields.filter( function( entry ) { return entry.label === 'Id' } ).pop();
let control = { value : 'x' };
idField.args[4].onCommit( 'Not A Slug', control );
check( 'an id that is no slug is put back, and says so', control.value === 'services' && builder._doc.model.blocks[1].id === 'services' && said.pop() === words['/_admin/builder/error/id-slug'] );
control = { value : 'x' };
idField.args[4].onCommit( 'contact', control );
check( '...so is an id another section has', control.value === 'services' && builder._doc.model.blocks[1].id === 'services' && said.pop() === words['/_admin/builder/error/id-taken'] );
questions = [];
idField.args[4].onCommit( 'offer', control );
check( 'a new id for a section that has keys is asked about first, and moved only on the answer', questions.length === 1 && builder._doc.model.blocks[1].id === 'services' );
questions[0].onChoose( 'cancel' );
check( '...Cancel puts the field back', control.value === 'services' && builder._doc.model.blocks[1].id === 'services' );
questions[0].onChoose( 'rename' );
check( '...Rename moves it', builder._doc.model.blocks[1].id === 'offer' && builder._doc.model.blocks[1].renamedFrom === 'services' );

console.log( '\nSaving the document' );

doc = open();
builder._doc.model.blocks[0].cols[0].components[0].source = 'title';
builder._save( function( ok ) { answers.push( ok ) } );
check( 'a source that means nothing is not saved: no request goes, the first red one is selected, and the line says why', requests.length === 0 && answers[answers.length - 1] === false && builder._status.state === 'error' && builder._same( builder._sel, [ 0, 0, 0 ] ) );

doc = open();
builder._doc.model.name = 'Start';
builder._save( function( ok ) { answers.push( ok ) } );
check( 'a save sends the file, the model and the hash, and nothing else while it runs: a second one is none', requests.length === 1 && requests[0].action === 'builder/save' && requests[0].payload.file === 'page-home' && requests[0].payload.hash === 'h1' && requests[0].payload.model.name === 'Start'
	&& requests[0].payload.force === undefined && builder._saving === true && ( builder._save(), requests.length === 1 ) );
const saved = JSON.parse( JSON.stringify( builder._doc.model ) );
saved.blocks[0].id = 'hero';
requests[0].callback( 200, { model : saved, hash : 'h2' } );
check( 'the answer replaces the model and the hash, and the document holds nothing unsaved', builder._doc.hash === 'h2' && builder._doc.model.name === 'Start' && builder._unsaved( builder._doc ) === false && builder._saving === false && builder._status.state === 'saved' && answers[answers.length - 1] === true && builder._fresh.x === undefined );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 409, { code : 'builder_conflict', error : 'x' } );
check( 'a conflict asks: reload, save anyway or cancel', questions.length === 1 && questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'reload,force,cancel' && builder._saving === false && builder._unsaved( builder._doc ) === true );
questions[0].onChoose( 'force' );
check( '...Save anyway sends the same model again with force, and the old hash', requests.length === 2 && requests[1].action === 'builder/save' && requests[1].payload.force === true && requests[1].payload.hash === 'h1' && requests[1].payload.model.name === 'Start' );
questions[0].onChoose( 'reload' );
check( '...Reload loads the file again - the model, the registry, the list - and nothing is sent that writes', requests.slice( 2 ).map( function( request ) { return request.action } ).sort().join() === 'builder/list,builder/load,builder/registry' );
const before = requests.length;
questions[0].onChoose( 'cancel' );
check( '...Cancel does nothing', requests.length === before && builder._unsaved( builder._doc ) === true );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 400, { code : 'builder_invalid', error : 'x', params : [ 'the section "services": the source "x" means nothing here', 'the name is bad' ] } );
check( 'a model the server refuses stays unsaved, with its problems at the blocks they name', builder._problems.length === 2 && builder._blame( builder._doc.model, builder._problems ).blocks[1].length === 1 && builder._unsaved( builder._doc ) === true && builder._status.state === 'error' );
builder._changed();
check( '...the next change lets them go', builder._problems.length === 0 );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 403, { code : 'builder_permission', error : 'x', params : [ '/_admin/keys/manage' ] } );
check( 'a refusal for a permission is an error and nothing is lost', builder._unsaved( builder._doc ) === true && builder._saving === false && builder._status.state === 'error' );

check( 'the shell is told what is unsaved, and can drop it', ( function() {
	doc = open();
	const clean = registered.isDirty() === false;
	builder._doc.model.name = 'Start';
	const dirty = registered.isDirty() === true;
	registered.discard();
	return clean === true && dirty === true && registered.isDirty() === false && builder._doc.model.name === 'Home';
} )() );
check( '...and a save it asks for reports how it went', ( function() {
	doc = open();
	builder._doc.model.name = 'Start';
	let said = null;
	registered.save( function( ok ) { said = ok } );
	requests[0].callback( 200, { model : builder._doc.model, hash : 'h3' } );
	return said === true;
} )() );

console.log( '\nThe blocks of HTML+' );

doc = open();
let bar = null;
builder._applyBlock( [ 2 ], '<section id="map"></section>', 'html', function( text ) { bar = text } );
check( 'a block is applied by sending the model with the block edited, and asking the server to read it again', requests.length === 1 && requests[0].action === 'builder/source' && requests[0].payload.model.blocks[2].edited === true && requests[0].payload.model.blocks[2].kind === 'html'
	&& requests[0].payload.model.blocks[2].source === '<section id="map"></section>' && requests[0].payload.model.blocks[2].reason === null && builder._doc.model.blocks[2].edited === undefined );
const again = JSON.parse( JSON.stringify( requests[0].payload.model ) );
again.blocks[2] = { kind : 'section', id : 'map', settings : builder._doc.model.blocks[0].settings, background : null, cols : [ builder._newCol() ] };
requests[0].callback( 200, { model : again, parts : [], source : '' } );
check( '...what comes back is the model: a block that reads as a section is one, and is new to the page', builder._doc.model.blocks[2].kind === 'section' && builder._fresh.map === true && builder._same( builder._sel, [ 2 ] ) );

doc = open();
builder._applyBlock( [ 0 ], '<div></div>', 'section', function( text ) { bar = text } );
check( 'a section edited as html is a block that has no reason yet, for the server to find one', requests[0].payload.model.blocks[0].kind === 'html' && requests[0].payload.model.blocks[0].reason.code === '' );
requests[0].callback( 400, { code : 'builder_invalid', params : [ 'block 1 is neither a section nor a block of html' ] } );
check( '...and what the server refuses it for is said in the dialog', typeof bar === 'string' && bar.indexOf( 'block 1' ) !== -1 && builder._doc.model.blocks[0].kind === 'section' );

doc = open();
builder._doc.model.blocks[0].renamedFrom = 'intro';
builder._doc.model.blocks[0].cols[0].components[0].create = { value : 'Welcome' };
builder._doc.model.blocks[0].cols[0].components[1].create = { value : 'We build' };
builder._doc.model.blocks[0].background.create = { label : 'Hero', width : 1600, height : 900 };
builder._applyBlock( [ 0 ], '<section id="hero"></section>', 'section', function( text ) { bar = text } );
const reread = JSON.parse( JSON.stringify( builder._doc.model ) );
delete reread.blocks[0].renamedFrom;
reread.blocks[0].cols[0].components.forEach( function( component ) { delete component.create } );
delete reread.blocks[0].background.create;
requests[0].callback( 200, { model : reread, parts : [], source : '' } );
check( 'a section applied as html keeps what the markup cannot carry: the keys and slots it is to make, and where it was renamed from', ( function() {
	const section = builder._doc.model.blocks[0];
	return ( section.cols[0].components[0].create || {} ).value === 'Welcome' && ( section.cols[0].components[1].create || {} ).value === 'We build' && ( section.background.create || {} ).width === 1600 && section.renamedFrom === 'intro'
		&& section.cols[0].components[2].create === undefined && builder._fresh.hero === undefined;
} )() );

doc = open();
builder._doc.model.blocks[0].renamedFrom = 'intro';
builder._doc.model.blocks[0].cols[0].components[0].create = { value : 'Welcome' };
builder._applyBlock( [ 0 ], '<section id="top"></section>', 'section', function( text ) { bar = text } );
const moved = JSON.parse( JSON.stringify( builder._doc.model ) );
moved.blocks[0].id = 'top';
delete moved.blocks[0].renamedFrom;
moved.blocks[0].cols[0].components[0].source = '/template/page-home/top/title';
delete moved.blocks[0].cols[0].components[0].create;
requests[0].callback( 200, { model : moved, parts : [], source : '' } );
check( '...but not for a section whose id or sources are not the ones it had: nothing is made of them that was not asked for', builder._doc.model.blocks[0].renamedFrom === undefined && builder._doc.model.blocks[0].cols[0].components[0].create === undefined );

console.log( '\nEvery form and menu is drawn' );

// The real fields, the real dialog: over an element that takes anything, every form of every node is made, and the menu of every row
builder._selectField = keep._selectField;
builder._switchField = keep._switchField;
builder._numberField = keep._numberField;
builder._textField = keep._textField;
builder._dialog = keep._dialog;
builder._refreshTab = keep._refreshTab;
builder._closeDialog = keep._closeDialog;
builder._dialogProblem = keep._dialogProblem;
builder._changed = keep._changed;

const drawn = function( what, run ) {
	try {
		run();
		return true;
	} catch( error ) {
		console.log( '      '+ what+ ': '+ error.message );
		return false;
	}
};

doc = open();
builder._renderEditor();
builder._doc.model.blocks[1].cols[1].stack.attributes.sort = 'a,b';
builder._doc.model.blocks[1].cols[1].components.push( { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>x</p>' } );
builder._doc.model.blocks[1].cols[1].components.push( { name : 'spacer', source : '', text : null, attributes : { size : '2' } } );
builder._doc.model.blocks[3].cols.push( builder._newCol() );
builder._doc.model.blocks[3].cols[1].components.push( { name : 'image', source : '/template/page-home/contact/image', text : null, attributes : {}, create : { label : 'Image', width : 1600, height : 900 } } );

const paths = [];
builder._doc.model.blocks.forEach( function( block, b ) {
	paths.push( [ b ] );
	( block.cols || [] ).forEach( function( col, c ) {
		paths.push( [ b, c ] );
		if( col.stack !== null )
			paths.push( [ b, c, 'x' ] );
		col.components.forEach( function( component, k ) { paths.push( [ b, c, k ] ) } );
	} );
} );
paths.push( [] );

check( 'the form of every node can be drawn: '+ paths.length+ ' of them - the template, the sections, the blocks, the columns, the stacks, the components', paths.filter( function( at ) {
	return drawn( 'settings '+ at.join('.'), function() { builder._openSettings( at ) } ) === false;
} ).length === 0 );
check( 'the menu of every row can be drawn, and what it offers where a column is cut', paths.filter( function( at ) {
	return at.length > 0 && at[at.length - 1] !== 'x' && drawn( 'menu '+ at.join('.'), function() { builder._openMenu( at, loose() ); builder._clip = { node : builder._doc.model.blocks[0].cols[0] } } ) === false;
} ).length === 0 );
check( 'the picker of components, the tree folded and unfolded, and every viewport can be drawn', drawn( 'picker', function() {
	builder._pickComponent( [ 0, 0 ], loose() );
	builder._pickComponent( [ 1, 1 ], loose() );
	builder._folded = { '0': true, '1.1': true };
	builder._renderTree();
	[ 's', 'm', 'l' ].forEach( function( viewport ) { builder._viewport = viewport; builder._renderPreview() } );
} ) );
check( 'the list, with templates and with none, and the dialog that makes a new one', drawn( 'list', function() {
	builder._files = [ { file : 'page-home', name : '', header : '', footer : '', sections : 1, foreign : 0, readable : false, editable : false, usedBy : [] } ];
	builder._renderList();
	builder._rowActions( { file : 'page-home', editable : true } );
	builder._newTemplate();
	builder._confirmDelete( 'page-home' );
} ) );
check( 'the source field of every kind: a key in a section, a field in a stack, a picture, the background', drawn( 'source field', function() {
	const section = builder._doc.model.blocks[1];
	const base = { section : section, fields : null, value : '', text : null, label : 'Title', onPick : function() {}, onFixed : function() {} };
	[ 'text', 'href', 'image' ].forEach( function( kind ) {
		builder._sourceField( Object.assign( {}, base, { kind : kind } ) );
		builder._sourceField( Object.assign( {}, base, { kind : kind, fields : registry().types[0].fields, value : 'title', text : 'x', red : { why : 'field' } } ) );
	} );
	builder._keys = [];
	builder._sourceField( Object.assign( {}, base, { kind : 'text' } ) );
	builder._backgroundField( [ 0 ] );
} ) );
check( 'the HTML+ editor and the source view', drawn( 'html', function() {
	builder._editBlock( [ 2 ] );
	builder._editBlock( null );
	builder._editBlock( [ 0 ] );
	requests[requests.length - 1].callback( 200, { parts : [ { kind : 'section', block : 0, source : '<section></section>' } ] } );
	builder._showSource();
	requests[requests.length - 1].callback( 200, { parts : [ { kind : 'head', block : null, source : 'a' }, { kind : 'html', block : 2, source : 'b' }, { kind : 'foot', block : null, source : 'c' } ] } );
} ) );
check( 'the errors of the panel, and an editor that is opened and fails to load', drawn( 'load', function() {
	builder._showError( loose(), 500, { code : 'offline' } );
	requests = [];
	builder._openEditor( 'page-home' );
	requests.forEach( function( request ) { request.callback( 404, { code : 'builder_missing' } ) } );
	builder._openEditor( 'page-home' );
	requests.slice( 3 ).forEach( function( request ) {
		if( request.action === 'builder/load' )
			request.callback( 200, { model : page(), hash : 'h9', source : '' } );
		else if( request.action === 'builder/registry' )
			request.callback( 200, registry() );
		else
			request.callback( 200, { templates : [ { file : 'page-home', usedBy : [ { route : 'GET://', uri : '/home' } ] } ] } );
	} );
} ) && builder._doc !== null && builder._doc.hash === 'h9' && builder._doc.usedBy.length === 1 );

check( 'a new key is named by the kind of the component and a new slot of the picture behind a section background, whatever the label says in the language of the workbench (Titel is title)', ( function() {
	const names = [];
	const realName = builder._keyName;
	const section = builder._doc.model.blocks[1];
	const base = { kind : 'text', name : 'title', section : section, fields : null, value : '', text : null, label : 'Titel', onPick : function() {}, onFixed : function() {} };
	builder._keyName = function( at, file, name, known ) { names.push( name ); return realName.call( builder, at, file, name, known ) };
	builder._sourceField( base );
	builder._sourceField( Object.assign( {}, base, { kind : 'image', name : 'button', label : 'Schaltfläche' } ) );
	builder._backgroundField( [ 1 ] );
	builder._keyName = realName;
	return names.join() === 'title,button,background';
} )() );
check( 'a slot is no slot where a segment of it starts with a digit: a section id of 2col says so before the server does', ( function() {
	const section = { id : '2col', cols : [] };
	return builder.SLOT.test( builder._keyUri( 'page-home', section.id, 'background' ) ) === false && builder.KEY.test( builder._keyUri( 'page-home', section.id, 'title' ) ) === true;
} )() );
( function() {
	const section = { id : 'intro', cols : [] };
	const shown = function( id, typed, what ) {
		const names = {};
		const real = builder._one;
		builder._one = function( root, selector ) { const found = real.call( builder, root, selector ); names[selector] = found; return found };
		builder._newName( loose(), { section : { id : id, cols : [] }, name : 'title' }, what, function() {} );
		builder._one = real;
		names['.builder-new-name'].value = typed;
		names['.builder-new-name'].dispatch( 'input' );
		return { message : names['.builder-new-message'].textContent, disabled : names['.builder-new-use'].disabled };
	};
	const digitSection = shown( '2col', 'background', 'slot' );
	const digitName = shown( 'intro', '2x', 'slot' );
	const keyDigitName = shown( 'intro', '2x', 'key' );
	check( 'a new slot in a section of 2col blames the section, and the Use button is off', digitSection.message === words['/_admin/builder/source/name-slot'] && digitSection.disabled === true );
	check( '...a slot name of 2x in the section intro blames the name, not the section, and the Use button is off', digitName.message === words['/_admin/builder/source/name-slot-name'] && digitName.message !== words['/_admin/builder/source/name-slot'] && digitName.disabled === true );
	check( '...a key name of 2x is a name the grammar of a key allows', keyDigitName.message === '' && keyDigitName.disabled === false && section.id === 'intro' );
} )();
check( 'the slots of the project come with the picture they have, for the thumbnail: the url, and none where there is no picture', ( function() {
	const pickers = [];
	const realPicker = builder._picker;
	builder._picker = function( mount, items ) { pickers.push( items ) };
	builder._sourceField( { kind : 'image', name : 'image', section : builder._doc.model.blocks[1], fields : null, value : '', text : null, label : 'Image', onPick : function() {} } );
	builder._picker = realPicker;
	const items = pickers[0] || [];
	return items.length === 2 && items[0].image === '/uploads/hero.1600x900.jpg' && items[1].image === '';
} )() );
check( 'the stylesheet keeps what the script hides hidden (a button with display set is shown all the same) and paints the sections in grey levels', ( function() {
	const css = fs.readFileSync( path.join( __dirname, '../assets/admin.css' ), 'utf8' );
	const colours = css.split( '.builder-frame.builder-color-' ).slice( 1 ).map( function( rule ) { return rule.slice( 0, rule.indexOf( '}' ) ) } );
	return css.indexOf( '.builder-icon-btn[hidden] {\n\tdisplay: none;' ) !== -1 && colours.length === 6 && colours.every( function( rule ) { return /var\(--editor-(blue|orange)/.test( rule ) === false } );
} )() );

console.log( '\nThe refusal to delete' );

( function() {
	const lines = [];
	const realEl = builder._el;
	const realLoad = builder._loadList;
	builder._el = function( tag, cls, text ) { if( tag === 'p' ) lines.push( text ); return realEl.apply( builder, arguments ) };
	questions.length = 0;
	requests.length = 0;
	builder._confirmDelete( 'page-home' );
	questions[questions.length - 1].onChoose( 'delete' );
	requests[requests.length - 1].callback( 409, { params : [ 'GET://', 'GET://contact', 'POST://form' ] } );
	builder._el = realEl;
	builder._loadList = realLoad;
	check( 'the refusal names the addresses the list shows (the routes\' keys through _addresses), not uris', lines.length === 1 && /\/, \/contact$/.test( lines[0] ) && lines[0].indexOf( 'GET:' ) === -1 );
} )();

console.log( '\nThe screens' );

check( 'the list and the editor are drawn without a page to complain', ( function() {
	builder._files = [ { file : 'page-home', name : 'Home', header : 'html-header', footer : '', sections : 3, foreign : 1, readable : true, editable : true, usedBy : [ { route : 'GET://', uri : '/home' } ] } ];
	builder._renderList();
	builder._files = [];
	builder._renderList();
	return true;
} )() );
check( 'the address of a template is checked before a request is made of it', builder.FILE.test( 'page-home' ) === true && builder.FILE.test( 'page-' ) === false && builder.FILE.test( '../x' ) === false && builder.FILE.test( 'page-Home' ) === false );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
