/**
 *	Nino
 *	builder-js-smoke.js		What the panel's admin.js does over a stand-in for the page
 *												- no jsdom, no dependency, nothing but node.
 *
 *												The model half is driven as it is: moving a node inside its
 *												level and refusing it across levels, a copy that takes new
 *												ids and new keys, delete; what the tools of a frame offer,
 *												what a delete asks about and what the buttons at the end of
 *												a level add; the animation of the template - the classes of
 *												a new section, a section that is like the template, the
 *												sections that follow it; the order of a loop, the widths and
 *												the hidden viewports of a column; the names of a new key and
 *												what the grammar says of one; the red sources of a loop that
 *												changed; the preview of each viewport; what a save's answer
 *												means and what the two answers to a conflict send; the
 *												unsaved state. All of it over the example page of the concept
 *												(fixtures/page-home.json, which builder-smoke.php holds to
 *												what the Reader makes of page-home.tpl) and the registry of
 *												the kernel's own components (registry.json).
 *
 *												The half that draws is run over an element that accepts
 *												anything: the dialogs, the list and the preview are built
 *												without a page, the field a form is made of is caught where
 *												it is made - a select, a switch, a number, a line of text, a
 *												width, a box, the arrows of an order - and its change is
 *												called the way the page would call it, so what a form writes
 *												into the model is measured, and so is what a request carries.
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
check( 'what a node is: template, section, a block of html, column, stack, component', [ [], [ 0 ], [ 2 ], [ 0, 0 ], [ 1, 1, 'x' ], [ 0, 0, 1 ] ].map( function( at ) { return builder._kind( model, at ) } ).join() === 'template,section,html,col,stack,component' );

console.log( '\nMoving, copying, deleting' );

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
check( 'a step moves a node within its level, a section among sections, a column among columns, a component among the components of its column - and the others close up', ( function() {
	const names = function() { return model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() };
	const down = builder._step( model, [ 0, 0, 0 ], 1 );
	const moved = names();
	const section = builder._step( model, [ 1 ], -1 );
	return builder._same( down, [ 0, 0, 1 ] ) && moved === 'subtitle,title,button' && builder._same( section, [ 0 ] ) && model.blocks[0].id === 'services' && model.blocks[1].id === 'hero'
		&& builder._step( model, [ 1, 0 ], 1 ) === null && builder._step( model, [ 0, 0 ], -1 ) === null;
} )() );

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
check( 'delete takes a section, a column or a component out', builder._remove( model, [ 1, 1, 0 ] ) === true && model.blocks[1].cols[1].components.length === 3
	&& builder._remove( model, [ 2 ] ) === true && model.blocks.length === 3 && builder._remove( model, [ 0, 0 ] ) === true && model.blocks[0].cols.length === 0 && builder._remove( model, [ 9 ] ) === false && builder._remove( model, [] ) === false );
model = page();
check( '...and a loop, with the components that are its cell - a column that has none is not a loop to delete', builder._remove( model, [ 1, 1, 'x' ] ) === true && model.blocks[1].cols[1].stack === null && model.blocks[1].cols[1].components.length === 0
	&& model.blocks[1].cols.length === 2 && builder._remove( model, [ 1, 0, 'x' ] ) === false && builder._remove( model, [ 0, 5, 'x' ] ) === false );

console.log( '\nThe tools of a frame' );

model = page();
const toolbar = function( at ) { return JSON.stringify( builder._toolbar( model, at ) ) };
check( 'a section has settings, HTML+, up, down, duplicate and delete - the first cannot go up, the last cannot go down', toolbar( [ 1 ] ) === '{"settings":true,"html":true,"up":true,"down":true,"duplicate":true,"delete":true}'
	&& builder._toolbar( model, [ 0 ] ).up === false && builder._toolbar( model, [ 0 ] ).down === true && builder._toolbar( model, [ 3 ] ).down === false && builder._toolbar( model, [ 3 ] ).up === true );
check( '...a block of html has no tool of its own to edit it as HTML+: its settings are the editor', builder._toolbar( model, [ 2 ] ).html === null && builder._toolbar( model, [ 2 ] ).settings === true && builder._toolbar( model, [ 2 ] ).duplicate === true );
check( '...a column and a component move inside their level: the first of two cannot go up, the last cannot go down', builder._toolbar( model, [ 1, 0 ] ).up === false && builder._toolbar( model, [ 1, 0 ] ).down === true && builder._toolbar( model, [ 1, 1 ] ).down === false
	&& builder._toolbar( model, [ 1, 1, 0 ] ).up === false && builder._toolbar( model, [ 1, 1, 3 ] ).down === false && builder._toolbar( model, [ 1, 1, 3 ] ).up === true && builder._toolbar( model, [ 1, 1, 0 ] ).html === null );
check( '...the only column of a section is not deleted: a section has one at least', builder._toolbar( model, [ 0, 0 ] ).delete === false && builder._toolbar( model, [ 1, 0 ] ).delete === true && builder._toolbar( model, [ 0, 0, 0 ] ).delete === true );
check( '...a loop has settings and delete, and is neither moved nor copied - it is the one of its column', toolbar( [ 1, 1, 'x' ] ) === '{"settings":true,"html":null,"up":null,"down":null,"duplicate":null,"delete":true}' );
check( '...the template has no tools, and a path the model has nothing at has none to use', builder._toolbar( model, [] ) === null && builder._toolbar( model, [ 9, 0, 0 ] ).up === false && builder._toolbar( model, [ 9, 0, 0 ] ).delete === false );

check( 'what a frame holds that a delete would take with it: the components of a column and of a loop, the loop itself, all of a section', builder._children( model, [ 0 ] ) === 3 && builder._children( model, [ 1 ] ) === 7 && builder._children( model, [ 0, 0 ] ) === 3
	&& builder._children( model, [ 1, 1 ] ) === 5 && builder._children( model, [ 1, 1, 'x' ] ) === 4 && builder._children( model, [ 0, 0, 0 ] ) === 0 && builder._children( model, [ 2 ] ) === 0 && builder._children( model, [ 9 ] ) === 0 );
check( '...a section with nothing in it, as a new one is, holds nothing', builder._children( { blocks : [ builder._newSection( model ) ] }, [ 0 ] ) === 0 );

check( 'a column ends in a button for a component and one for a loop - and where it has a loop, in the loop: a component only', builder._columnAdds( model, [ 0, 0 ] ).join() === 'component,stack' && builder._columnAdds( model, [ 1, 0 ] ).join() === 'component,stack'
	&& builder._columnAdds( model, [ 1, 1 ] ).join() === 'component' && builder._columnAdds( model, [ 9, 9 ] ).length === 0 );

console.log( '\nA new loop, the animation of the template, the order of a loop, the widths of a column' );

check( 'a new loop is the kernel\'s own, over the first type of the project, with the attributes it has', ( function() {
	const stack = builder._newStack( registry() );
	return stack.name === 'stack' && stack.source === '/services' && stack.attributes.cols === '100 50 33' && stack.attributes.gap === '2' && stack.attributes.id === '';
} )() );
check( 'the plain stack of the kernel is called the loop, the others by the label they registered', builder._stackLabel( registry(), 'stack' ) === 'Loop' && builder._stackLabel( registry(), 'slider' ) === 'Slider' && builder._stackLabel( registry(), 'nothing' ) === 'nothing' );
check( '...the first loop the registry has where it has not that one, and none where it has none; over no type where the project has none', ( function() {
	const some = registry();
	delete some.stacks.stack;
	const none = registry();
	none.stacks = {};
	const typeless = registry();
	typeless.types = [];
	return builder._newStack( some ).name === 'slider' && builder._newStack( none ) === null && builder._newStack( typeless ).source === '';
} )() );

check( 'the animation of the template is read from its classes, and written in the order the Writer writes them', JSON.stringify( builder._vpaParse( 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium' ) ) === '{"vpa":"zoom-soft","vpaSpeed":"medium","vpaMode":""}'
	&& JSON.stringify( builder._vpaParse( 'nino-vpa--repeat nino-vpa--speed-slow nino-vpa--blur-hard' ) ) === '{"vpa":"blur-hard","vpaSpeed":"slow","vpaMode":"repeat"}' && JSON.stringify( builder._vpaParse( 'nino-vpa' ) ) === '{"vpa":"","vpaSpeed":"","vpaMode":""}'
	&& JSON.stringify( builder._vpaParse( null ) ) === '{"vpa":null,"vpaSpeed":"","vpaMode":""}' && builder._vpaClasses( { vpa : 'zoom-soft', vpaSpeed : 'medium', vpaMode : 'repeat' } ) === 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium nino-vpa--repeat'
	&& builder._vpaClasses( { vpa : '', vpaSpeed : '', vpaMode : '' } ) === 'nino-vpa' && builder._vpaClasses( { vpa : null, vpaSpeed : 'fast', vpaMode : '' } ) === null );
check( '...whatever a template says reads back as itself', [ 'nino-vpa', 'nino-vpa nino-vpa--zoom-soft', 'nino-vpa nino-vpa--blur-hard nino-vpa--speed-fast nino-vpa--repeat', 'nino-vpa nino-vpa--speed-slow' ].every( function( classes ) { return builder._vpaClasses( builder._vpaParse( classes ) ) === classes } ) );

model = page();
model.vpa = 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium';
check( 'a new section is made with the classes of the template: they are its animation', ( function() {
	const section = builder._newSection( model );
	return section.settings.vpa === 'zoom-soft' && section.settings.vpaSpeed === 'medium' && section.settings.vpaMode === '' && section.settings.vpaDelay === '' && builder._vpaLike( model.vpa, section.settings ) === true;
} )() );
model.vpa = null;
check( '...and with none where the template has none', builder._newSection( model ).settings.vpa === null );

model = page();
check( 'a section is like the template where it carries exactly its classes, in whatever order: no more, no less, no delay and no duration of its own', ( function() {
	const like = { vpa : 'zoom-soft', vpaSpeed : 'medium', vpaMode : '', vpaDelay : '', vpaDuration : '' };
	const classes = 'nino-vpa--speed-medium nino-vpa nino-vpa--zoom-soft';
	return builder._vpaLike( classes, like ) === true && builder._vpaLike( classes, Object.assign( {}, like, { vpaSpeed : 'slow' } ) ) === false && builder._vpaLike( classes, Object.assign( {}, like, { vpaMode : 'repeat' } ) ) === false
		&& builder._vpaLike( classes, Object.assign( {}, like, { vpaDelay : '200ms' } ) ) === false && builder._vpaLike( classes, Object.assign( {}, like, { vpaDuration : '1s' } ) ) === false && builder._vpaLike( classes, { vpa : null, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '' } ) === false;
} )() );
check( '...no animation is like a template that has none, and like nothing else', builder._vpaLike( null, builder._newSection( model ).settings ) === true && builder._vpaLike( 'nino-vpa', builder._newSection( model ).settings ) === false
	&& builder._vpaLike( null, { vpa : '', vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '' } ) === false );
check( '...a section is made like the template - its delay and its duration go - or like none', ( function() {
	const settings = { vpa : 'blur-hard', vpaSpeed : 'fast', vpaMode : 'repeat', vpaDelay : '1s', vpaDuration : '2s' };
	builder._vpaApply( settings, 'nino-vpa nino-vpa--zoom-soft' );
	const like = JSON.stringify( settings );
	builder._vpaApply( settings, null );
	return like === '{"vpa":"zoom-soft","vpaSpeed":"","vpaMode":"","vpaDelay":"","vpaDuration":""}' && JSON.stringify( settings ) === '{"vpa":null,"vpaSpeed":"","vpaMode":"","vpaDelay":"","vpaDuration":""}';
} )() );
check( 'the sections that are like the template are found, and follow where it changes - the others do not', ( function() {
	model = page();
	model.vpa = 'nino-vpa nino-vpa--zoom-soft';
	builder._vpaApply( model.blocks[0].settings, model.vpa );
	builder._vpaApply( model.blocks[3].settings, model.vpa );
	model.blocks[3].settings.vpaDelay = '1s';
	const before = builder._likeSections( model, model.vpa );
	model.vpa = 'nino-vpa nino-vpa--blur-soft nino-vpa--speed-slow';
	builder._followVpa( model, before, model.vpa );
	return before.join() === '0' && model.blocks[0].settings.vpa === 'blur-soft' && model.blocks[0].settings.vpaSpeed === 'slow' && model.blocks[1].settings.vpa === '' && model.blocks[3].settings.vpa === 'zoom-soft' && model.blocks[3].settings.vpaDelay === '1s'
		&& builder._likeSections( model, model.vpa ).join() === '0';
} )() );

check( 'the order of a loop is a field and a direction, written as the loop reads them: a minus for descending, none for no field - and a list of fields is left as it is', ( function() {
	const stack = { attributes : { sort : 'title' } };
	const was = JSON.stringify( builder._sortState( stack ) );
	builder._sortSet( stack, 'summary', true );
	const down = stack.attributes.sort;
	builder._sortSet( stack, 'summary', false );
	const up = stack.attributes.sort;
	builder._sortSet( stack, '', true );
	return was === '{"field":"title","descending":false,"several":false}' && down === '-summary' && up === 'summary' && stack.attributes.sort === ''
		&& JSON.stringify( builder._sortState( { attributes : { sort : '-date' } } ) ) === '{"field":"date","descending":true,"several":false}' && builder._sortState( { attributes : { sort : 'a,-b' } } ).several === true
		&& builder._sortState( { attributes : {} } ).field === '';
} )() );

check( 'the width of a column in a viewport is a number, and hidden is an entry for the viewports that hide it only', ( function() {
	const col = builder._newCol();
	builder._setWidth( col, 's', '50' );
	builder._setWidth( col, 'l', 25 );
	builder._setHidden( col, 'm', true );
	builder._setHidden( col, 's', true );
	builder._setHidden( col, 's', false );
	return JSON.stringify( col.width ) === '{"s":50,"m":100,"l":25}' && JSON.stringify( col.hidden ) === '{"m":true}';
} )() );

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
	return stack('s').cell === 100 && stack('m').cell === 50 && stack('l').cell === 50 && stack('l').label === 'Loop' && stack('l').source === '/services' && stack('l').image === 'cells' && builder._same( stack('l').path, [ 1, 1, 'x' ] )
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
	builder._fresh = {};
	builder._problems = [];
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
// The controls of the viewport table and the arrows of an order are caught the same way
[ [ '_widthSelect', 'width' ], [ '_hiddenBox', 'hidden' ], [ '_sortToggles', 'toggles' ] ].forEach( function( entry ) {
	keep[entry[0]] = builder[entry[0]];
	builder[entry[0]] = function() {
		fields.push( { kind : entry[1], label : entry[1] === 'toggles' ? 'Direction' : arguments[0], hint : '', args : Array.prototype.slice.call( arguments ) } );
		return loose();
	};
} );
// ...and so is every line of fields, with how many it holds
let lines = [];
keep._line = builder._line;
builder._line = function( items, caption ) { lines.push( { count : items.length, caption : caption } ); return keep._line.apply( builder, arguments ) };
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
const field = function( label, nth ) {
	const found = fields.filter( function( entry ) { return entry.label === label } );
	return nth === undefined ? found.pop() : found[nth];
};
// Where the change of a field is called, by what the field is
const changeAt = { select : 4, switch : 3, number : 5, text : 3, width : 2, hidden : 2, toggles : 1 };
const choose = function( label, value, nth ) {
	const entry = field( label, nth );
	entry.args[changeAt[entry.kind]]( value );
};

doc = open();
builder._renderEditor();
check( 'the editor draws itself from the model: the preview, the bar, without a page to complain', builder._doc.model.blocks.length === 4 && builder._status.state === 'idle' && builder._unsaved( builder._doc ) === false );
check( 'the template has a name, two frames and the animation a new section is given: an effect, how fast and how often - and no delay, which a section has of its own', ( function() {
	fields = [];
	lines = [];
	builder._openSettings( [] );
	return dialogs.length === 1 && fields.map( function( entry ) { return entry.label } ).join() === 'Name,Header,Footer,Effect,Speed,Runs' && fields[1].args[2].map( function( option ) { return option.value } ).join() === ',html-header,html-header-slim'
		&& fields[5].args[2].map( function( option ) { return option.value } ).join() === ',repeat' && fields[5].args[2].map( function( option ) { return option.label } ).join() === 'Once,Repeat'
		&& lines.map( function( line ) { return line.count } ).join() === '2,2';
} )() );
check( '...what is chosen is the classes of its head line: none until an effect is chosen, then the effect, the speed and the mode in the order they are written', ( function() {
	const model = builder._doc.model;
	const was = model.vpa;
	choose( 'Effect', 'zoom-soft' );
	const effect = model.vpa;
	choose( 'Speed', 'slow' );
	choose( 'Runs', 'repeat' );
	const all = model.vpa;
	choose( 'Effect', 'plain' );
	const plain = model.vpa;
	choose( 'Effect', 'none' );
	return was === null && effect === 'nino-vpa nino-vpa--zoom-soft' && all === 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-slow nino-vpa--repeat' && plain === 'nino-vpa nino-vpa--speed-slow nino-vpa--repeat' && model.vpa === null;
} )() );
doc = open();
builder._doc.model.vpa = 'nino-vpa nino-vpa--zoom-soft';
builder._vpaApply( builder._doc.model.blocks[0].settings, builder._doc.model.vpa );
builder._vpaApply( builder._doc.model.blocks[1].settings, builder._doc.model.vpa );
builder._doc.model.blocks[3].settings.vpa = 'blur-hard';
fields = [];
builder._openSettings( [] );
const templateDialog = last();
templateDialog.onClose();
check( 'a template dialog closed without a change of the animation asks nothing', questions.length === 0 );
choose( 'Effect', 'blur-soft' );
templateDialog.onClose();
check( 'a change of it asks whether the sections that are like the template follow it - naming how many; yes is the first of the answers', questions.length === 1 && questions[0].message === 'The animation of the template changed. Should the sections that are like the template follow it? Sections affected: 2.'
	&& questions[0].choices.map( function( choice ) { return choice.value+ ':'+ choice.kind } ).join() === 'follow:primary,stay:secondary' && builder._doc.model.blocks[0].settings.vpa === 'zoom-soft' );
questions[0].onChoose( 'stay' );
check( '...No leaves them as they are - and they are not like the template any more', builder._doc.model.blocks[0].settings.vpa === 'zoom-soft' && builder._doc.model.blocks[1].settings.vpa === 'zoom-soft' && builder._vpaLike( builder._doc.model.vpa, builder._doc.model.blocks[0].settings ) === false );
questions[0].onChoose( 'follow' );
check( '...Yes has them follow it, and not the section that has an animation of its own', builder._doc.model.blocks[0].settings.vpa === 'blur-soft' && builder._doc.model.blocks[1].settings.vpa === 'blur-soft' && builder._doc.model.blocks[3].settings.vpa === 'blur-hard'
	&& builder._doc.model.blocks[2].kind === 'html' && builder._unsaved( builder._doc ) === true );
doc = open();
builder._doc.model.vpa = 'nino-vpa nino-vpa--flip-hard';
fields = [];
builder._openSettings( [] );
questions = [];
choose( 'Effect', 'zoom-soft' );
last().onClose();
check( 'a template that no section follows asks nothing, and changes', questions.length === 0 && builder._doc.model.vpa === 'nino-vpa nino-vpa--zoom-soft' );
doc = open();
builder._doc.model.vpa = 'nino-vpa nino-vpa--blur-hard nino-vpa--visible';
fields = [];
builder._openSettings( [] );
check( '...a template that was written by hand with a mode the form has not still shows it', fields[5].args[2].map( function( option ) { return option.value } ).join() === ',repeat,visible' && fields[5].args[3] === 'visible' && fields[3].args[3] === 'blur-hard' );
doc = open();

fields = [];
lines = [];
builder._openSettings( [ 0 ] );
check( 'the spacing of a section is two lines, above and below, of two fields each - outside and inside - and the other settings stand together two by two', lines.filter( function( line ) { return line.caption !== undefined } ).map( function( line ) { return line.caption+ ':'+ line.count } ).join() === 'Above:2,Below:2'
	&& lines.length === 9 && lines.every( function( line ) { return line.count === 2 } ) && fields.filter( function( entry ) { return entry.label === 'Outside' } ).length === 2 && fields.filter( function( entry ) { return entry.label === 'Inside' } ).length === 2 );
check( 'the form of a section is in tabs, and writes into the model as it is changed', last().tabs.map( function( tab ) { return tab.id } ).join() === 'general,background,spacing,animation,custom' && ( function() {
	choose( 'Colour', 'dark' );
	choose( 'Outside', '3', 0 );
	choose( 'Inside', '2', 1 );
	choose( 'Effect', 'zoom-soft' );
	choose( 'Dim the picture', false );
	choose( 'Height of the cover', null );
	const settings = builder._doc.model.blocks[0].settings;
	return settings.color === 'dark' && settings.mt === '3' && settings.mb === '' && settings.pt === '' && settings.pb === '2' && settings.vpa === 'zoom-soft' && settings.dim === false && settings.cover === null && changes === 6 && builder._unsaved( builder._doc ) === true;
} )() );
choose( 'Effect', 'none' );
check( '...the animation is none, the plain one or an effect: null, an empty string, its name', builder._doc.model.blocks[0].settings.vpa === null && ( choose( 'Effect', 'plain' ), builder._doc.model.blocks[0].settings.vpa === '' ) );
check( '...and the line over the bar says there is something to save', builder._status.state === 'dirty' );

doc = open();
builder._doc.model.vpa = 'nino-vpa nino-vpa--blur-soft';
fields = [];
builder._openSettings( [ 0 ] );
const mode = function() { return fields.filter( function( entry ) { return entry.label === 'Animation' && entry.kind === 'select' && entry.args[2][0].value === 'like' } ).pop() };
check( 'the animation of a section is like the template, off or its own: a section that is neither shows its own fields, after the choice, the effect it has', mode().args[2].map( function( option ) { return option.value+ '='+ option.label } ).join() === 'like=Like the template,off=Off,own=Own'
	&& mode().args[3] === 'own' && field( 'Effect' ) !== undefined && field( 'Delay' ) !== undefined );
const picked = mode();
fields = [];
picked.args[4]( 'like' );
check( '...like the template is the classes of the template, and no delay or duration of its own, and the fields of its own are gone', ( function() {
	const settings = builder._doc.model.blocks[0].settings;
	return settings.vpa === 'blur-soft' && settings.vpaSpeed === '' && settings.vpaDelay === '' && builder._vpaLike( builder._doc.model.vpa, settings ) === true && field( 'Delay' ) === undefined && fields.filter( function( entry ) { return entry.label === 'Animation' } ).length === 1 && mode().args[3] === 'like';
} )() );
mode().args[4]( 'off' );
check( '...off is none', builder._doc.model.blocks[0].settings.vpa === null && mode().args[3] === 'off' );
mode().args[4]( 'own' );
check( '...own starts as the plain animation, with the fields to change it - and what is changed there is the section\'s own, which is no longer like the template', ( function() {
	const settings = builder._doc.model.blocks[0].settings;
	const started = settings.vpa;
	choose( 'Speed', 'fast', 0 );
	return started === '' && field( 'Delay' ) !== undefined && settings.vpaSpeed === 'fast' && builder._vpaLike( builder._doc.model.vpa, settings ) === false && mode().args[3] === 'own';
} )() );
check( '...and the mode of it is once or repeat - the states the page itself sets, visible and visible-once, are no way for an animation to run', field( 'Runs' ).args[2].map( function( option ) { return option.value } ).join() === ',repeat' );
fields = [];
builder._openSettings( [ 0 ] );
check( '...and a section that has the classes of the template shows like the template when the form is opened', ( function() {
	builder._vpaApply( builder._doc.model.blocks[0].settings, builder._doc.model.vpa );
	fields = [];
	builder._openSettings( [ 0 ] );
	return mode().args[3] === 'like';
} )() );
doc = open();

fields = [];
builder._openSettings( [ 0, 0 ] );
check( 'the form of a column: a width and a visibility for each viewport as a table of three rows, alignment, the loop, the animation, the classes', last().tabs.map( function( tab ) { return tab.id } ).join() === 'layout,stack,animation,custom'
	&& fields.filter( function( entry ) { return entry.kind === 'width' } ).map( function( entry ) { return entry.label } ).join() === 'Width, Phone,Width, Tablet,Width, Desktop'
	&& fields.filter( function( entry ) { return entry.kind === 'hidden' } ).map( function( entry ) { return entry.label } ).join() === 'Hidden, Phone,Hidden, Tablet,Hidden, Desktop' );
check( '...the animation of the column runs once or repeats, like the others', field( 'Runs' ).args[2].map( function( option ) { return option.value } ).join() === ',repeat' );
check( '...what the table writes is the width of the viewport, and the viewports that hide the column', ( function() {
	choose( 'Width, Phone', '50' );
	choose( 'Width, Desktop', '25' );
	choose( 'Hidden, Tablet', true );
	choose( 'Hidden, Phone', true );
	choose( 'Hidden, Phone', false );
	const col = builder._doc.model.blocks[0].cols[0];
	return col.width.s === 50 && col.width.l === 25 && col.width.m === 100 && JSON.stringify( col.hidden ) === '{"m":true}';
} )() );

fields = [];
lines = [];
builder._openSettings( [ 1, 1, 'x' ] );
check( 'the loop of a column opens the form of the column on its own tab, and has the form of a loop: its type, its order, its limit, its grid, its id', last().tab === 'stack' && field( 'Type' ) !== undefined && field( 'Sort by' ) !== undefined && field( 'Direction' ) !== undefined && field( 'Limit' ) !== undefined && field( 'Offset' ) !== undefined
	&& field( 'Query' ) !== undefined && field( 'Cell width, Phone' ) !== undefined && field( 'Gap between the cells' ) !== undefined && field( 'Cells of equal height' ) !== undefined && field( 'Id of the loop' ) !== undefined );
check( '...limit and offset share a line, the three cells and the gap another, the id and the equal height a third - and the animation of the column has speed and mode on a line, delay and duration on the next', lines.map( function( line ) { return line.count } ).join() === '2,2,4,2,2,2' );
check( '...the type is one of the Elements panel\'s, with the title it has there', field( 'Type' ).args[2].map( function( option ) { return option.value+ '='+ option.label } ).join() === '/services=Services (/services),/team=Team (/team)' );
check( '...the order is a field and two arrows, of which the one for ascending is on where the loop says title', field( 'Sort by' ).args[3] === 'title' && field( 'Direction' ).args[0] === false );
choose( 'Sort by', 'summary' );
choose( 'Direction', true );
choose( 'Limit', 12 );
choose( 'Offset', 3 );
choose( 'Cell width, Tablet', '33' );
choose( 'Cells of equal height', false );
choose( 'Gap between the cells', '4' );
check( 'what the form writes is what the call carries: a field and a direction as the loop reads them, the numbers as text, the cells as three widths', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.attributes.sort === '-summary' && stack.attributes.limit === '12' && stack.attributes.offset === '3' && stack.attributes.cols === '100 33 50' && stack.attributes.autoheight === '0' && stack.attributes.gap === '4';
} )() );
choose( 'Direction', false );
check( '...the other arrow is ascending, and the field stays', builder._doc.model.blocks[1].cols[1].stack.attributes.sort === 'summary' );
choose( 'Sort by', 'price' );
check( '...and the direction stays where a field is chosen after it', builder._doc.model.blocks[1].cols[1].stack.attributes.sort === 'price' );
choose( 'Direction', true );
choose( 'Type', '/team' );
check( 'a loop of another type leaves the components where they are - what their sources mean there is red until changed', builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 3 );
choose( 'Loop', '' );
check( 'Static drops the loop and keeps the components, and every field of the element is red', builder._doc.model.blocks[1].cols[1].stack === null && builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 4 );
choose( 'Loop', 'slider' );
check( 'a registered loop comes with its own attributes and the loop it has, over a type of the project', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.name === 'slider' && stack.source === '/services' && stack.attributes.width === '75%' && stack.attributes.limit === '0' && builder._red( builder._doc.model, builder._registry ).length === 0;
} )() );
choose( 'Loop', 'stack' );
check( '...and going on to another loop keeps what both have', ( function() {
	builder._doc.model.blocks[1].cols[1].stack.attributes.limit = '5';
	choose( 'Loop', 'list' );
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

console.log( '\nThe list and the copy of a template' );

doc = open();
check( 'a template is copied from the list: the dialog asks for the name of the new one, with the name of the old one in it, and sends the old file and the new name', ( function() {
	builder._files = [ { file : 'page-home', name : 'Home', editable : true } ];
	fields = [];
	builder._duplicateTemplate( 'page-home' );
	const dialog = last();
	const asked = field( 'Name' );
	choose( 'Name', 'My copy' );
	requests = [];
	dialog.actions[0].onClick( function( text ) { said.push( text ) } );
	return dialog.title === 'Duplicate' && asked.args[2] === 'Copy of Home' && dialog.actions.length === 1 && dialog.actions[0].label === 'Duplicate'
		&& requests.length === 1 && requests[0].action === 'builder/duplicate' && JSON.stringify( requests[0].payload ) === '{"file":"page-home","name":"My copy"}';
} )() );
check( '...keys and image slots that are in the way are named in the dialog after the sentence of the code, in the panel\'s own words: the server answers the bare uris, the keys and then the slots', ( function() {
	const before = said.length;
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ [ '/template/page-home-copy/hero/title', '/template/page-home-copy/hero/subtitle' ], [ '/template/page-home-copy/hero/background' ] ] } );
	return said.slice( before ).join() === words['/_admin/builder/error/key-exists']
		+ ' '+ words['/_admin/builder/error/keys-in-the-way'].replace( '%s', '/template/page-home-copy/hero/title, /template/page-home-copy/hero/subtitle' )
		+ ' '+ words['/_admin/builder/error/slots-in-the-way'].replace( '%s', '/template/page-home-copy/hero/background' );
} )() );
check( '...a list with nothing in it says nothing of its kind, and no sentence of the server\'s is shown: an answer in sentences names nothing', ( function() {
	const before = said.length;
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ [], [ '/template/page-home-copy/hero/background' ] ] } );
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ 'the key "/template/page-home-copy/hero/title" is there already' ] } );
	const lines = said.slice( before );
	return lines.length === 2 && lines[0] === words['/_admin/builder/error/key-exists']+ ' '+ words['/_admin/builder/error/slots-in-the-way'].replace( '%s', '/template/page-home-copy/hero/background' )
		&& lines[1] === words['/_admin/builder/error/key-exists'];
} )() );
check( '...a key or a slot that a panel refused - another request made it in the meantime - is named by its uri after the sentence of its code, and params of another status are not shown', ( function() {
	const before = said.length;
	requests[0].callback( 409, { code : 'builder_slot', params : [ '/template/page-home-copy/hero/background' ] } );
	requests[0].callback( 409, { code : 'builder_key', params : [ '/template/page-home-copy/hero/title' ] } );
	requests[0].callback( 403, { code : 'builder_permission', params : [ '/_admin/keys/manage' ] } );
	const lines = said.slice( before );
	return lines.length === 3 && lines[0] === words['/_admin/builder/error/slot']+ ' /template/page-home-copy/hero/background' && lines[1] === words['/_admin/builder/error/key']+ ' /template/page-home-copy/hero/title'
		&& lines[2] === words['/_admin/builder/error/permission'];
} )() );
check( '...a name that makes a file that is there is said in the dialog, which stays open - and the copy is opened where it was made', ( function() {
	const before = said.length;
	requests[0].callback( 409, { code : 'builder_exists' } );
	const refused = said.slice( before ).join() === words['/_admin/builder/error/exists'];
	requests = [];
	const route = [];
	const realGo = sandbox.Nino.admin.router.go;
	sandbox.Nino.admin.router.go = function( panel, parts ) { route.push( panel+ ':'+ parts.join() ) };
	builder._visit( 'page-home-copy' );
	sandbox.Nino.admin.router.go = realGo;
	return refused === true && route.join() === 'builder:page-home-copy' && requests.map( function( request ) { return request.action } ).sort().join() === 'builder/list,builder/load,builder/registry' && requests.find( function( request ) { return request.action === 'builder/load' } ).payload.file === 'page-home-copy';
} )() );
check( '...and a template the project has no file for is copied from its file name', ( function() {
	builder._files = [];
	fields = [];
	builder._duplicateTemplate( 'page-none' );
	return field( 'Name' ).args[2] === 'Copy of page-none';
} )() );

check( 'the list has a name, a file, the frames, the sections and the routes, and the buttons - no count of blocks of HTML+, no column for whether the file is read completely - sorted as the server sorts it', ( function() {
	let given = null;
	const realTable = sandbox.Nino.adminUi.table;
	sandbox.Nino.adminUi.table = function( options ) { given = options; return { setRows : function() {} } };
	builder._files = [
		{ file : 'page-a', name : 'A', header : 'html-header', footer : '', sections : 2, foreign : 1, readable : true, reason : null, editable : true, usedBy : [ { route : 'GET://a', uri : '/a' } ] },
		{ file : 'page-b', name : '', header : '', footer : '', sections : 0, foreign : 1, readable : false, reason : { line : 3, code : 'second-row', detail : '', text : '' }, editable : true, usedBy : [] },
	];
	builder._renderList();
	sandbox.Nino.adminUi.table = realTable;
	return given.columns.map( function( column ) { return column.key } ).join() === 'name,file,header,footer,sections,usedBy,actions' && given.rows.map( function( row ) { return row.file } ).join() === 'page-a,page-b'
		&& given.rows.map( function( row ) { return row.name } ).join() === 'A,page-b' && given.rows.map( function( row ) { return row.header+ '|'+ row.footer } ).join() === 'html-header|none,none|none';
} )() );
check( '...a template that is not read completely has a mark by its name, with the reason of the first block it failed at for a tooltip - one that is read has none', ( function() {
	let given = null;
	const realTable = sandbox.Nino.adminUi.table;
	sandbox.Nino.adminUi.table = function( options ) { given = options; return { setRows : function() {} } };
	builder._renderList();
	sandbox.Nino.adminUi.table = realTable;
	const warnings = given.rows.map( function( row ) { return row.warning } );
	const marks = {};
	const realOne2 = builder._one;
	builder._one = function( root, selector ) { const found = realOne2.call( builder, root, selector ); marks[selector] = found; return found };
	builder._nameCell( given.rows[1] );
	const warned = { hidden : marks['.builder-name-warning'].hidden, title : marks['.builder-name-warning'].title, text : marks['.builder-name-text'].textContent };
	builder._nameCell( given.rows[0] );
	builder._one = realOne2;
	return warnings.join( '|' ) === '|Line 3: The section has more than one row' && warned.hidden === false && warned.title === 'Line 3: The section has more than one row' && warned.text === 'page-b' && marks['.builder-name-warning'].hidden === true;
} )() );
check( '...and the three buttons of a row: open, duplicate, delete - the first two off for a file that is no page template of the grammar', ( function() {
	const made = [];
	const realEl = builder._el;
	builder._el = function( tag, className, text ) { const el = realEl.apply( builder, arguments ); if( tag === 'button' ) made.push( { text : text, el : el } ); return el };
	builder._rowActions( { file : 'page-a', editable : false } );
	builder._el = realEl;
	return made.map( function( button ) { return button.text } ).join() === 'Open,Duplicate,Delete' && made[0].el.disabled === true && made[1].el.disabled === true && made[2].el.disabled !== true;
} )() );

console.log( '\nThe tools of the frames, and the buttons that add' );

doc = open();
const toolButtons = {};
const oneBefore = builder._one;
builder._one = function( root, selector ) { const found = oneBefore.call( builder, root, selector ); toolButtons[selector] = found; return found };
// The tools of one frame, as they are when it is drawn - drawing the preview again, as a change does, makes others
const toolsOf = function( path ) {
	const found = {};
	builder._tools( path );
	[ 'settings', 'html', 'up', 'down', 'duplicate', 'delete' ].forEach( function( name ) { found[name] = toolButtons['[data-tool="'+ name+ '"]'] } );
	return found;
};
const component = toolsOf( [ 0, 0, 0 ] );
check( 'the tools of a frame are the buttons of its toolbar: the first component cannot go up, has no tool for HTML+, and has the others', component.up.disabled === true && component.down.disabled === false && component.html.hidden === true && component.settings.hidden === false
	&& component.duplicate.disabled === false && component.delete.disabled === false );
component.down.dispatch( 'click' );
check( '...and pressed they work on the frame: down moves the title behind the subtitle', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name } ).join() === 'subtitle,title,button' && changes === 1 );
component.duplicate.dispatch( 'click' );
check( '...duplicate copies what stands first now, behind it', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name+ ':'+ node.source.split('/').pop() } ).join() === 'subtitle:subtitle,subtitle:subtitle-2,title:title,button:name' && changes === 2 );
component.delete.dispatch( 'click' );
check( '...delete takes it away at once, as it holds nothing', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name+ ':'+ node.source.split('/').pop() } ).join() === 'subtitle:subtitle-2,title:title,button:name' && changes === 3 && questions.length === 0 );
const section = toolsOf( [ 0 ] );
const loop = toolsOf( [ 1, 1, 'x' ] );
check( 'the tools of a section have one for HTML+ more, and a loop has settings and delete only', section.html.hidden === false && section.up.disabled === true && loop.up.hidden === true && loop.down.hidden === true && loop.duplicate.hidden === true && loop.settings.hidden === false && loop.delete.disabled === false );
requests = [];
section.html.dispatch( 'click' );
check( '...the one for HTML+ asks the server for the markup of the section', requests.length === 1 && requests[0].action === 'builder/source' );
builder._one = oneBefore;

doc = open();
builder._duplicateAt( [ 0 ] );
check( 'a copy of a section from its tools is a new section: its keys are new, so it may be renamed without moving anything, and it is the selection', builder._doc.model.blocks[1].id === 'hero-2' && builder._fresh['hero-2'] === true && builder._fresh['hero'] !== true
	&& builder._same( builder._sel, [ 1 ] ) && changes === 1 && builder._doc.model.blocks[2].id === 'services' );
check( '...a copy of a component stands behind it, with a key beside the old one, and a copy of a column behind it', ( function() {
	builder._duplicateAt( [ 0, 0, 0 ] );
	const first = builder._doc.model.blocks[0].cols[0].components;
	builder._duplicateAt( [ 0, 0 ] );
	return first[1].name === 'title' && first[1].source === '/template/page-home/hero/title-2' && first[1].create.value === 'Welcome' && builder._doc.model.blocks[0].cols.length === 2 && builder._same( builder._sel, [ 0, 1 ] );
} )() );
check( '...and a loop is not copied: it has no tool for it', ( function() {
	const before = JSON.stringify( builder._doc.model );
	builder._duplicateAt( [ 2, 1, 'x' ] );
	return JSON.stringify( builder._doc.model ) === before;
} )() );

doc = open();
changes = 0;
builder._stepNode( [ 0, 0, 0 ], 1 );
check( 'down moves a component behind the next, and it stays the selection', builder._doc.model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'subtitle,title,button' && builder._same( builder._sel, [ 0, 0, 1 ] ) && changes === 1 );
builder._stepNode( [ 0, 0, 1 ], -1 );
builder._stepNode( [ 0 ], -1 );
check( '...up moves it back, and from the first place there is no step: the document is not changed by it', builder._doc.model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'title,subtitle,button' && changes === 2 && builder._unsaved( builder._doc ) === false );
builder._stepNode( [ 1 ], -1 );
check( '...a section moves among the sections', builder._doc.model.blocks.map( function( block ) { return block.id || 'html' } ).join() === 'services,hero,html,contact' && builder._same( builder._sel, [ 0 ] ) );
builder._stepNode( [ 0, 0 ], 1 );
builder._stepNode( [ 0, 1 ], -1 );
check( '...and a column among the columns of its section', changes === 5 && builder._doc.model.blocks[0].cols.length === 2 && builder._doc.model.blocks[0].cols[0].stack === null && builder._doc.model.blocks[0].cols[1].stack !== null );

doc = open();
changes = 0;
questions = [];
builder._deleteAt( [ 0, 0, 2 ] );
check( 'a component is deleted at once, and the parent is the selection', builder._doc.model.blocks[0].cols[0].components.length === 2 && questions.length === 0 && builder._same( builder._sel, [ 0, 0 ] ) && changes === 1 );
builder._addSection();
builder._deleteAt( [ 4 ] );
check( '...so is a section that holds nothing', builder._doc.model.blocks.length === 4 && questions.length === 0 );
builder._deleteAt( [ 0 ] );
check( 'a section with something in it asks first, and nothing is deleted until the answer says so', questions.length === 1 && builder._doc.model.blocks.length === 4 && questions[0].title === 'Section' && questions[0].message === words['/_admin/builder/confirm/delete-node']
	&& questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'delete,cancel' );
questions[0].onChoose( 'cancel' );
check( '...Cancel leaves it', builder._doc.model.blocks.length === 4 && builder._doc.model.blocks[0].id === 'hero' );
questions[0].onChoose( 'delete' );
check( '...Delete takes it, with everything in it', builder._doc.model.blocks.length === 3 && builder._doc.model.blocks[0].id === 'services' && builder._same( builder._sel, [] ) );
questions = [];
builder._deleteAt( [ 0, 1 ] );
check( 'a column with a loop asks too, and the loop itself, which takes its components with it', questions.length === 1 && questions[0].title === 'Column' && ( questions[0].onChoose( 'cancel' ), builder._doc.model.blocks[0].cols.length === 2 ) );
questions = [];
builder._deleteAt( [ 0, 1, 'x' ] );
questions[0].onChoose( 'delete' );
check( '...a loop that is deleted is gone with its components, and the column is empty and stays', questions[0].title === 'Loop' && builder._doc.model.blocks[0].cols[1].stack === null && builder._doc.model.blocks[0].cols[1].components.length === 0 && builder._same( builder._sel, [ 0, 1 ] ) );
questions = [];
builder._deleteAt( [ 0, 1 ] );
check( '...an empty column is deleted without a question', questions.length === 0 && builder._doc.model.blocks[0].cols.length === 1 );

doc = open();
builder._doc.model.vpa = 'nino-vpa nino-vpa--zoom-soft';
builder._addSection();
builder._addCol( [ 0 ] );
builder._addComponent( [ 0, 0 ], 'button' );
check( 'a new section is below the last, fresh, with the animation of the template; a column ends the row of its section; a component ends its column - with its key', builder._doc.model.blocks[4].id === 'section' && builder._fresh.section === true && builder._doc.model.blocks[4].settings.vpa === 'zoom-soft'
	&& builder._doc.model.blocks[0].cols.length === 2 && JSON.stringify( builder._doc.model.blocks[0].cols[1].width ) === '{"s":100,"m":100,"l":100}'
	&& builder._doc.model.blocks[0].cols[0].components.length === 4 && builder._doc.model.blocks[0].cols[0].components[3].source === '/template/page-home/hero/button' && builder._doc.model.blocks[0].cols[0].components[3].create.value === 'Button' && builder._same( builder._sel, [ 0, 0, 3 ] ) );
builder._addCol( [ 1 ] );
builder._addComponent( [ 1, 1 ], 'title' );
check( '...at the end of the level they are meant for: the column of the services is the third, the component stands last in the loop', builder._doc.model.blocks[1].cols.length === 3 && builder._doc.model.blocks[1].cols[1].components.length === 5 && builder._doc.model.blocks[1].cols[1].components[4].name === 'title'
	&& builder._doc.model.blocks[1].cols[1].components[4].source === '' && builder._doc.model.blocks[1].cols[1].components[4].create === undefined && builder._doc.model.blocks.length === 5 );
builder._addLoop( [ 0, 1 ] );
check( 'a loop in a column that has none is the loop the registry gives, over the first type, and the column keeps its components: it is selected', builder._doc.model.blocks[0].cols[1].stack.name === 'stack' && builder._doc.model.blocks[0].cols[1].stack.source === '/services'
	&& builder._same( builder._sel, [ 0, 1, 'x' ] ) && builder._doc.model.blocks[0].cols[1].stack.attributes.limit === '0' );
check( '...and a column that has one gets no second', ( function() {
	const before = JSON.stringify( builder._doc.model );
	builder._addLoop( [ 1, 1 ] );
	return JSON.stringify( builder._doc.model ) === before;
} )() );
check( '...a column that was a column of components and became a loop has red sources only where they mean nothing: the keys of its components do', builder._red( builder._doc.model, builder._registry ).length === 0 );
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
[ '_widthSelect', '_hiddenBox', '_sortToggles', '_line' ].forEach( function( name ) { builder[name] = keep[name] } );

check( 'the two arrows of an order are ascending and descending: the one that is on is the order, the one that is pressed is what the loop is told', ( function() {
	const rows = [];
	const buttonRow = sandbox.Nino.adminUi.buttonRow;
	sandbox.Nino.adminUi.buttonRow = function( buttons, current, onChange ) { rows.push( { buttons : buttons, current : current, onChange : onChange } ) };
	const told = [];
	builder._sortToggles( true, function( descending ) { told.push( descending ) } );
	builder._sortToggles( false, function() {} );
	rows[0].onChange( 'desc' );
	rows[0].onChange( 'asc' );
	sandbox.Nino.adminUi.buttonRow = buttonRow;
	return rows[0].current === 'desc' && rows[1].current === 'asc' && Object.keys( rows[0].buttons ).join() === 'asc,desc' && told.join() === 'true,false';
} )() );

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
check( 'the tools of every frame can be drawn, and the frame the pointer is over is the one that has them seen', paths.filter( function( at ) {
	return at.length > 0 && drawn( 'tools '+ at.join('.'), function() { builder._tools( at ) } ) === false;
} ).length === 0 && drawn( 'hover', function() {
	const first = { classList : { add : function() {}, remove : function() {} } };
	builder._hover( first );
	builder._hover( first );
	builder._hover( null );
} ) && builder._hovered === null );
check( 'the picker of components, the preview of every viewport and the buttons at the end of a column can be drawn', drawn( 'picker', function() {
	builder._pickComponent( [ 0, 0 ], loose() );
	builder._pickComponent( [ 1, 1 ], loose() );
	[ 's', 'm', 'l' ].forEach( function( viewport ) { builder._viewport = viewport; builder._renderPreview() } );
	builder._addsRow( [ 0, 0 ] );
	builder._addsRow( [ 1, 1 ] );
} ) );
check( 'the list, with templates and with none, the name with its mark, and the dialogs that make a new template and a copy of one', drawn( 'list', function() {
	builder._files = [ { file : 'page-home', name : '', header : '', footer : '', sections : 1, foreign : 1, readable : false, reason : { line : 3, code : 'second-row', detail : '', text : '' }, editable : false, usedBy : [] } ];
	builder._renderList();
	builder._rowActions( { file : 'page-home', editable : true } );
	builder._nameCell( { name : 'Home', warning : '' } );
	builder._nameCell( { name : 'Home', warning : 'Line 3: The section has more than one row' } );
	builder._newTemplate();
	builder._duplicateTemplate( 'page-home' );
	builder._duplicateTemplate( 'page-none' );
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
check( 'the stylesheet keeps what the script hides hidden (a button with display set is shown all the same), paints the sections in grey levels, shows the tools of the frame the pointer is over and of the selected one, and gives the dialog 44rem', ( function() {
	const css = fs.readFileSync( path.join( __dirname, '../assets/admin.css' ), 'utf8' );
	const colours = css.split( '.builder-frame.builder-color-' ).slice( 1 ).map( function( rule ) { return rule.slice( 0, rule.indexOf( '}' ) ) } );
	return css.indexOf( '.builder-icon-btn[hidden] {\n\tdisplay: none;' ) !== -1 && colours.length === 6 && colours.every( function( rule ) { return /var\(--editor-(blue|orange)/.test( rule ) === false } )
		&& css.indexOf( '.builder-pick.is-hover > header > .builder-tools' ) !== -1 && css.indexOf( '.builder-pick.is-selected > .builder-tools' ) !== -1 && css.indexOf( 'width: min(44rem, calc(100vw - 2rem));' ) !== -1;
} )() );
check( 'the template holds a fragment for everything the script clones, and none for a tree', ( function() {
	const template = fs.readFileSync( path.join( __dirname, '../templates/panel.tpl' ), 'utf8' );
	const used = [];
	source.replace( /_fragment\( '([a-z]+)' \)/g, function( all, name ) { used.push( name ) } );
	return used.length > 0 && used.every( function( name ) { return template.indexOf( 'id="builder-tpl-'+ name+ '"' ) !== -1 } ) && template.indexOf( 'builder-tpl-row' ) === -1 && template.indexOf( 'builder-tree' ) === -1 && source.indexOf( 'builder-tree' ) === -1;
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
	builder._files = [ { file : 'page-home', name : 'Home', header : 'html-header', footer : '', sections : 3, foreign : 1, readable : true, reason : null, editable : true, usedBy : [ { route : 'GET://', uri : '/home' } ] } ];
	builder._renderList();
	builder._files = [];
	builder._renderList();
	return true;
} )() );
check( 'the address of a template is checked before a request is made of it', builder.FILE.test( 'page-home' ) === true && builder.FILE.test( 'page-' ) === false && builder.FILE.test( '../x' ) === false && builder.FILE.test( 'page-Home' ) === false );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
