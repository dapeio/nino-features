/**
 *	Nino
 *	search-js-smoke.js	What the panel's admin.js does over a dom stand-in, on the workbench's own helpers: the
 *											Nino.js and the Nino.admin.js of the checkout it runs against are the real ones, and
 *											the stand-in is only what a browser and the shell are - a document that is loading
 *											until the test says the page is ready, the words a page carries, the wire, and the
 *											shell's registry of unsaved input. A handful of pins: the strip of three tabs, a
 *											row that says its state, the order of ticking being the priority and four being
 *											the most, the save that builds, the working language the probe asks in.
 *
 *											No jsdom: the element stand-in of the checkout's tests/harness.js. The checkout is
 *											the one NINO_ROOT names, else the one three levels up.
 *
 *	Usage: node features/Search/tests/search-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '..', '..', '..' ) );
const { check, done, element, documentOf, findAll, fire, fills, load, shellRouter } = require( path.join( root, 'tests', 'harness.js' ) );

const source = fs.readFileSync( path.join( __dirname, '..', 'assets', 'admin.js' ), 'utf8' );

// The words as the workbench has them in English: the shell's own and this panel's, from their files
const WORDS = Object.assign( fills( '_admin/text/en_US.php' ), fills( path.relative( root, path.join( __dirname, '..', 'text', 'en_US.php' ) ) ) );

/** The text of an element and of everything below it - a text node is a child here */
function textOf( el ) {
	return el.textContent + el.children.map( textOf ).join('');
}

/** Every descendant of $root with a class */
function withClass( root, name ) {
	return findAll( root, function( el ) { return String( el.className ).split(/\s+/).indexOf( name ) !== -1 } );
}

/** Every descendant of $root with a tag */
function withTag( root, tag ) {
	return findAll( root, function( el ) { return el.tagName === tag } );
}

const WEIGHTS = { 0 : 1, 1 : 0.7, 2 : 0.45, 3 : 0.25 };

/** A row as indexState() answers it */
function typeRow( options ) {
	return Object.assign( { type : '/products', title : 'Products', model : [ '.uri', 'title', 'text', 'tags', 'note', 'code' ], labels : { title : 'Headline' }, fields : { 0 : 'title' }, configured : true, issues : [],
		indexed : true, built : '2026-09-12T10:00:00+00:00', elements : 3, indexedElements : 3, stale : false }, options || {} );
}

/**
 *	The panel, drawn into the two elements the shell hands it
 *
 *	@param		{Object}	options		{ types, probe, at, dirty } - types: what search/list answers, probe: what search/probe answers,
 *																at: the address the router stands at (a hash, #search/search/@types), dirty: the shell's registry
 */
function panel( options ) {

	options = options || {};

	const body = element('body');
	const list = element('div');
	const type = element('div');

	list.id = 'search-list';
	type.id = 'search-type';
	[ list, type ].forEach( function( pane ) { body.appendChild( pane ) } );

	const calls = [];

	const answer = function( action ) {

		if( action === 'search/list' )
			return [ 200, { types : options.types || [ typeRow() ], weights : WEIGHTS, indexable : [ 'string', 'array' ] } ];
		if( action === 'search/save' )
			return [ 200, options.save || { type : '/products', fields : { 0 : 'title' }, removed : false } ];
		if( action === 'search/createindex' )
			return [ 200, options.created || { created : 1, elements : 3, failed : [], skipped : {}, issues : {} } ];
		if( action === 'search/probe' )
			return [ 200, options.probe || { query : 'x', hits : [ { uri : '/products/a', type : '/products', label : 'Alpha <b>', score : 1.5, coverage : 1, matched : [ 'title' ] } ], limit : 25 } ];

		return [ 200, {} ];
	};

	const heard = {};
	const timers = [];
	const page = documentOf( body );

	page.readyState = 'loading';
	page.addEventListener = function( name, fn ) { ( heard[name] = heard[name] || [] ).push( fn ) };
	page.createElement = function( tag ) { return Object.assign( element( tag ), { nodeType : 1 } ) };

	const sandbox = {
		console : console, Object : Object, document : page, navigator : { userAgent : 'node' }, NinoJstext : WORDS,
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		requestAnimationFrame : function() {},
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	load( '_nino/Nino.js', context );

	const Nino = sandbox.Nino;

	Nino.http.sendRequest = function( uri, method, callback, data ) {
		calls.push( { uri : uri, method : method, action : data.action, data : JSON.parse( data.data ) } );
		const answered = answer( data.action );
		callback( { status : answered[0], responseJSON : answered[1] } );
	};

	// The shell: the registry of unsaved input, the address of the workbench and the working language - what the panel reads of it
	// The router of the workbench itself, over the panes this panel has: what the panel writes is read back by it
	const codec = shellRouter( { search : [ [ 'search', 'search' ], [ 'search-settings', 'settings' ] ] } );
	const router = codec.stub( {
		hash : options.at || '#search',
		current : function() { return codec.read( router.hash ) },
		isActive : function() { return true },
		go : function( name, parts ) { router.hash = codec.check( name, parts ) },
		set : function( name, parts ) { router.hash = codec.check( name, parts ) },
	} );
	const followers = [];

	Nino.admin = {
		router : router,
		workLocale : { current : 'en_US', native : 'de_DE', follow : function( name, handler ) { followers.push( { name : name, handler : handler } ) } },
		dirty : options.dirty || { register : function() {}, isDirty : function() { return false }, guard : function( names, proceed ) { proceed() } },
	};

	load( '_admin/assets/Nino.admin.js', context );
	vm.runInContext( source, context, { filename : 'admin.js' } );

	// What the panel says as a toast
	const said = [];
	const say = Nino.adminUi.say;

	Nino.adminUi.say = function( text, options ) { said.push( text ); return say( text, options ) };

	Nino.admin.search.showCurrent();

	const found = function( rootEl, predicate ) { return findAll( rootEl, predicate )[0] || null };

	return {
		codec : codec, said : said, list : list, type : type, calls : calls, panel : Nino.admin.search, router : router, followers : followers, workLocale : Nino.admin.workLocale,
		hidden : function( el ) { return el.classList.contains('admin-hidden') },
		tabs : function() { return withClass( list, 'nino-admin-tab' ) },
		asked : function( action ) { return calls.filter( function( call ) { return call.action === action } ) },
		button : function( rootEl, words ) { return found( rootEl, function( el ) { return el.tagName === 'BUTTON' && el.textContent === words } ) },
		// The tab a row is looked for in: the index and the types both list every type
		pane : function( id ) { return found( list, function( el ) { return el.id === id } ) },
		row : function( uri, pane ) { return found( pane || list, function( el ) { return el.tagName === 'TR' && el.dataset.row === uri } ) },
		boxes : function() { return withTag( type, 'INPUT' ).filter( function( el ) { return el.type === 'checkbox' } ) },
		// A box ticked the way a person ticks it: the state first, then the event its listener hears
		tick : function( box, on ) { box.checked = on; fire( box, 'change' ) },
	};
}

/** The panel with the fields of /products open, the way the Edit button opens them */
function editing( options ) {
	const open = panel( options );
	fire( open.button( open.row( '/products', open.pane('search-types') ), WORDS['/_admin/search/label/edit'] ), 'click' );
	return open;
}

/** The shell's registry of unsaved input in miniature: what it was told, and what it was asked before it let an exit through */
function registry() {
	const stand = { entries : {}, asked : [], answer : 'proceed' };
	stand.register = function( name, entry ) { stand.entries[name] = entry };
	stand.guard = function( names, proceed ) { stand.asked.push( names ); if( stand.answer === 'proceed' ) proceed() };
	stand.isDirty = function( names ) { return names.some( function( name ) { return stand.entries[name] !== undefined && stand.entries[name].isDirty() === true } ) };
	return stand;
}


// --- The list: the strip of tabs and the state of a type -----------------------------------------------------------------

const open = panel( { types : [ typeRow(), typeRow( { type : '/notes', title : 'Notes', fields : {}, configured : false, indexed : false, built : '', elements : 2, indexedElements : 0 } ), typeRow( { type : '/old', title : 'Old', stale : true } ) ] } );

check( 'the panel asks for the list once, when it is shown, through the workbench\'s own request',
	open.calls.length === 1 && open.calls[0].action === 'search/list' && open.calls[0].uri === '/_admin/' && open.calls[0].method === 'POST' );

const tabs = open.tabs();
check( 'the list is a strip of three tabs in their order, Index, Types and Try it, with the Index on show and the pane of each hidden but one',
	tabs.map( function( tab ) { return tab.textContent } ).join('|') === [ WORDS['/_admin/search/tab/index'], WORDS['/_admin/search/tab/types'], WORDS['/_admin/search/tab/probe'] ].join('|')
	&& tabs[0].getAttribute('aria-selected') === 'true' && withClass( open.list, 'nino-admin-tabs' )[0].getAttribute('role') === 'tablist'
	&& findAll( open.list, function( el ) { return el.getAttribute('role') === 'tabpanel' && el.hidden === false } ).length === 1 );

const state = function( uri ) { return withClass( open.row( uri, open.pane('search-index') ), 'nino-admin-badge' )[0].dataset.state };
check( 'a row says the state of its index as the workbench\'s badge: current is ok, an index nobody configured is muted, one that is out of date is a warning',
	state('/products') === 'ok' && state('/notes') === 'muted' && state('/old') === 'warn' );
check( 'Rebuild stands only at the row where there is something to rebuild, as a small button; every other index is rebuilt with the one of the bar',
	open.button( open.row( '/old', open.pane('search-index') ), WORDS['/_admin/search/label/rebuild'] ) !== null && open.button( open.row( '/old', open.pane('search-index') ), WORDS['/_admin/search/label/rebuild'] ).classList.contains('nino-admin-btn--small') === true
	&& open.button( open.row( '/products', open.pane('search-index') ), WORDS['/_admin/search/label/rebuild'] ) === null );

fire( open.button( open.list, WORDS['/_admin/search/label/rebuild-all'] ), 'click' );
check( 'Rebuild all asks for every index, and the list is asked for again after it', open.asked('search/createindex').length === 1 && JSON.stringify( open.asked('search/createindex')[0].data ) === '{}' && open.asked('search/list').length === 2 );


// --- The fields of one type: the order of ticking is the priority ----------------------------------------------------------

const types = panel( {} );
fire( types.tabs()[1], 'click' );
check( 'the Types tab sets the address of the tab', types.router.hash === '#search/search/@types' );
fire( types.button( types.row( '/products', types.pane('search-types') ), WORDS['/_admin/search/label/edit'] ), 'click' );
check( 'Edit opens the fields of the type as a level of its own, at an address of its own, with the way back to the tab and the name of the type',
	types.router.hash === '#search/search/@types/products' && types.hidden( types.type ) === false && types.hidden( types.list ) === true
	&& withClass( types.type, 'nino-admin-back-link' )[0].textContent === 'Back to '+ WORDS['/_admin/search/tab/types'] && withTag( types.type, 'H3' )[0].textContent === 'Products' );

const view = editing( {} );
const boxes = view.boxes();
check( 'the fields of the model are a checklist, the address of the element first and the field that is indexed ticked',
	boxes.map( function( box ) { return box.value } ).join(',') === '.uri,title,text,tags,note,code' && boxes[1].checked === true && boxes.filter( function( box ) { return box.checked } ).length === 1 );
view.tick( boxes[5], true );
view.tick( boxes[2], true );
check( 'a field that is ticked goes behind the ones that are there, whatever its place in the list: the order of ticking is the priority, and each row says its place and its weight',
	withClass( boxes[1].parentNode, 'nino-admin-checklist-state' )[0].textContent === 'Priority 1, counts 1.00' && withClass( boxes[5].parentNode, 'nino-admin-checklist-state' )[0].textContent === 'Priority 2, counts 0.70'
	&& withClass( boxes[2].parentNode, 'nino-admin-checklist-state' )[0].textContent === 'Priority 3, counts 0.45' );
view.tick( boxes[3], true );
check( 'four are the most: the weights are four, and the boxes that are not ticked wait until one is let go', boxes.filter( function( box ) { return box.checked === false && box.disabled === true } ).length === 2 );
view.tick( boxes[5], false );
check( '...and a field let go leaves the ones behind it their order and frees the boxes', boxes.filter( function( box ) { return box.disabled === true } ).length === 0
	&& withClass( boxes[2].parentNode, 'nino-admin-checklist-state' )[0].textContent === 'Priority 2, counts 0.70' && withClass( boxes[5].parentNode, 'nino-admin-checklist-state' )[0].textContent === '' );

const bar = withClass( view.type, 'nino-admin-actionbar' )[0];
fire( view.button( bar, WORDS['/_admin/common/label/save'] ), 'click' );
const form = withTag( view.type, 'FORM' )[0];
fire( form, 'submit', { type : 'submit', preventDefault : function() {} } );
check( 'saving posts the fields as places, in the order they were ticked, and builds nothing', view.asked('search/save').length >= 1 && JSON.stringify( view.asked('search/save')[0].data ) === '{"type":"/products","fields":{"0":"title","1":"text","2":"tags"}}'
	&& view.asked('search/createindex').length === 0 );
fire( view.button( bar, WORDS['/_admin/search/label/saveandbuild'] ), 'click' );
check( 'Save and build saves and then builds the index of that type', view.asked('search/createindex').length === 1 && JSON.stringify( view.asked('search/createindex')[0].data ) === '{"type":"/products"}'
	&& view.calls.map( function( call ) { return call.action } ).lastIndexOf('search/createindex') > view.calls.map( function( call ) { return call.action } ).lastIndexOf('search/save') - 1 );

// What a field is called: the word of the model's label, else the vocabulary, else the key; the key stays behind it where it is not the word
check( 'a field is worded by the label of its model, and the key stays quiet behind it; the address of the element has a word of its own',
	withTag( view.type, 'LABEL' ).some( function( el ) { return textOf( el ).indexOf( 'Headline (title)' ) !== -1 } ) === true && withTag( view.type, 'LABEL' ).some( function( el ) { return textOf( el ).indexOf( WORDS['/_admin/search/label/uri'] ) !== -1 } ) === true
	&& withTag( view.type, 'LABEL' ).some( function( el ) { return textOf( el ).trim().indexOf( 'Note (note)' ) === 0 } ) === true );

// A problem of the configuration is worded in the interface language from the code the engine says, never an English sentence of the engine
const broken = panel( { types : [ typeRow( { type : '/gone', fields : {}, configured : true, indexed : false, issues : [ [ 'notype', '/gone' ], [ 'priority', '7' ] ] } ) ] } );
check( 'the issues of a type are worded by the panel from their codes and what belongs in them', textOf( broken.pane('search-types') ).indexOf( '/gone: '+ WORDS['/_admin/search/issue/notype'].replace( '%s', '/gone' ) ) !== -1
	&& textOf( broken.pane('search-types') ).indexOf( WORDS['/_admin/search/issue/priority'].replace( '%s', '7' ) ) !== -1 );

// After a save the view stays as it is and the focus where it was: the form is the same one, and the button the person pressed has it
const saveButton = view.button( bar, WORDS['/_admin/common/label/save'] );
const formBefore = withTag( view.type, 'FORM' )[0];
saveButton.focus();
fire( form, 'submit', { type : 'submit', preventDefault : function() {} } );
check( 'a save does not build the form again: it is the same form after it, and the focus is still on the button that was pressed', withTag( view.type, 'FORM' )[0] === formBefore && formBefore.isConnected === true && saveButton.focused === true );

// A save that is not a build says the index is to be rebuilt only where the index is out of date after it
const saved = function( stale ) {
	const page = editing( { types : [ typeRow( { stale : stale } ) ] } );
	page.tick( page.boxes()[3], true );
	fire( withTag( page.type, 'FORM' )[0], 'submit', { type : 'submit', preventDefault : function() {} } );
	return page.said.indexOf( WORDS['/_admin/search/msg/rebuild'] ) !== -1;
};
check( 'the word that the index is to be rebuilt is said after a save where the index is out of date, and not where it is current', saved( true ) === true && saved( false ) === false );

// The shell is told what is unsaved, and asks before the way back loses it
const shell = registry();
const guarded = editing( { dirty : shell } );
check( 'the shell\'s registry is told of the panel, and the fields are clean until one is ticked', shell.entries.search !== undefined && shell.isDirty( [ 'search' ] ) === false );
guarded.tick( guarded.boxes()[3], true );
check( 'a field ticked is unsaved input, and a field let go again is none', shell.isDirty( [ 'search' ] ) === true && ( guarded.tick( guarded.boxes()[3], false ), shell.isDirty( [ 'search' ] ) === false ) );
guarded.tick( guarded.boxes()[3], true );
fire( withClass( guarded.type, 'nino-admin-back-link' )[0], 'click' );
check( 'the way back asks the registry first, and the view is left where it answers to proceed', shell.asked.length === 1 && guarded.router.hash === '#search/search/@types' );


// --- The probe: the working language of the workbench -----------------------------------------------------------------------

const probing = panel( {} );
fire( probing.tabs()[2], 'click' );
const query = withTag( probing.list, 'INPUT' ).filter( function( el ) { return el.id === 'search-probe-query' } )[0];
query.value = 'alpha';
const probeForm = withTag( probing.list, 'FORM' ).filter( function( el ) { return withTag( el, 'INPUT' ).indexOf( query ) !== -1 } )[0];
fire( probeForm, 'submit', { type : 'submit', preventDefault : function() {} } );
check( 'the probe posts what was typed, the types that are ticked and the working language of the workbench, and no language of its own',
	JSON.stringify( probing.asked('search/probe')[0].data ) === '{"query":"alpha","types":["/products"],"locale":"en_US"}' && withClass( probing.list, 'nino-admin-locale-select' ).length === 0 );
check( 'what it found is drawn as text in the table, and a change of the working language puts it away: it was found in the other one',
	textOf( probing.list ).indexOf( 'Alpha <b>' ) !== -1 && withTag( probing.list, 'B' ).length === 0 && probing.followers.length === 1 && ( probing.followers[0].handler( 'de_DE' ), textOf( probing.list ).indexOf( 'Alpha <b>' ) === -1 ) );

// --- The address: a word or a key ------------------------------------------------------------------------------------------

// What the router reads of an address is what the panel shows: the word @types is the tab, a type that is called types is a type, and every address the panel wrote was read back
{
	const typed = panel( { at : '#search/search/@types/products' } );
	check( 'an address with the word @types and the name of a type opens the fields of that type', typed.hidden( typed.type ) === false && typed.router.hash === '#search/search/@types/products' );

	const key = panel( { at : '#search/search/types/products' } );
	check( 'the same without the at sign is no word: a key opens nothing of this panel, and the panel starts as the index', key.hidden( key.type ) === true && key.router.hash === '#search' );

	const probe = panel( { at : '#search/search/@probe' } );
	check( 'the word @probe is the tab of the probe', probe.router.hash === '#search/search/@probe' && probe.hidden( probe.list ) === false );

	const none = panel( { at : '#search/search/@types/%40products' } );
	check( 'a type whose name is a word, written as a key, is no type that is there: the tab of the types stands', none.hidden( none.type ) === true && none.router.hash === '#search/search/@types' );

	check( 'every address the panel wrote was read back by the router as the same screen and the same parts', [ typed, key, probe, none, types, guarded ].every( page => page.codec.mismatches.length === 0 ) && types.codec.written.some( written => typeof written.parts[0] === 'object' ) );
}

done();
