/**
 *	Nino
 *	search-js-smoke.js	What the panel's admin.js does over a dom stand-in, on
 *											the workbench's own helpers: the Nino.js and the
 *											Nino.admin.js of the checkout it runs against are the
 *											real ones, and the stand-in is only what a browser and
 *											the shell are - a document that is loading until the
 *											test says the page is ready, the words a page carries,
 *											the wire, and the shell's registry of unsaved input.
 *											That the list is asked for once - when the shell
 *											shows the panel, and not again when the page is ready -
 *											and drawn with no heading of its own, that a row says its
 *											state as the workbench's badge and carries the small
 *											buttons, that a rebuild says what it did on the
 *											list's own line and names what it could not build,
 *											that the type editor draws its four slots as selects
 *											over the model, saves them, builds the index and
 *											says a refusal in the words of its code, that the
 *											shell's registry of unsaved input is told what is on
 *											screen, can press Save, and is asked before the
 *											editor's back link leaves it, and that the probe
 *											sends what was typed, draws the hits in the order
 *											they came and remembers its query.
 *
 *											No jsdom: the element stand-in of the checkout's
 *											tests/harness.js, which is there from Nino 1.8 on,
 *											as the feature is. The checkout is the one NINO_ROOT
 *											names, else the one three levels up.
 *
 *	Usage: node features/Search/tests/search-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '..', '..', '..' ) );
const { check, done, element, documentOf, findAll, fire, fills, load } = require( path.join( root, 'tests', 'harness.js' ) );

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

/** A select, changed the way a person changes it: its own listener and the form's hear it */
function choose( select, value ) {
	select.value = value;
	select.dispatchEvent( { type : 'change', bubbles : true } );
}

const WEIGHTS = { 0 : 1, 1 : 0.7, 2 : 0.45, 3 : 0.25 };

/** A row as indexState() answers it */
function typeRow( options ) {
	return Object.assign( { type : '/products', title : 'Products', model : [ '.uri', 'title', 'text' ], fields : { 1 : 'title' }, configured : true, issues : [],
		indexed : true, built : '2026-09-12T10:00:00+00:00', elements : 3, indexedElements : 3, stale : false }, options || {} );
}

const ROW = typeRow();

/**
 *	The panel, drawn into the three elements the shell hands it
 *
 *	@param		{Object}	options		{ types, locales, save, created, probe, fail, hold, dirty, start } -
 *																types: what search/list answers,
 *																save, created, probe: what those actions answer,
 *																fail: { action: { status, body } } answers that action with a failure,
 *																hold: the action whose answer is kept until release() gives it,
 *																dirty: stands in for the shell's Nino.admin.dirty registry,
 *																start: 'show' (the shell shows the panel - the default) or 'none' - a page that
 *																is still loading, whose ready() the test calls
 */
function panel( options ) {

	options = options || {};

	const body = element('body');
	const list = element('div');
	const type = element('div');
	const probe = element('div');

	list.id = 'search-list';
	type.id = 'search-type';
	probe.id = 'search-probe';
	[ list, type, probe ].forEach( function( pane ) { body.appendChild( pane ) } );

	const calls = [];
	const held = [];

	/** What the server answers to one action: [ status, body ] */
	function answer( action ) {

		if( options.fail && options.fail[action] )
			return [ options.fail[action].status, options.fail[action].body ];
		if( action === 'search/list' )
			return [ 200, { types : options.types || [ ROW ], weights : WEIGHTS, indexable : [ 'string', 'array' ], locales : options.locales || [ 'de_DE', 'en_US' ] } ];
		if( action === 'search/save' )
			return [ 200, options.save || { type : '/products', fields : { 1 : 'title' }, removed : false } ];
		if( action === 'search/createindex' )
			return [ 200, options.created || { created : 1, elements : 3, failed : [], skipped : {}, issues : {} } ];
		if( action === 'search/probe' )
			return [ 200, options.probe || { query : 'x', hits : [], limit : 25 } ];

		return [ 200, {} ];
	}

	/*	The page Nino.js loads into. It is still loading, so what is bound to the
		ready event waits for DOMContentLoaded, and the timers Nino.js asks for are
		kept for the test to run: a callback that throws is thrown again from one
		of them, and a smoke that never ran them would not see it. The words are
		what the [jstext] shortcode puts on a page, which the real
		Nino.content.getText() reads	*/
	const heard = {};
	const timers = [];

	const page = documentOf( body );
	page.readyState = 'loading';
	page.addEventListener = function( name, fn ) { ( heard[name] = heard[name] || [] ).push( fn ) };

	/*	table() takes a node that a cell hands back in as one when it is an Object
		with a nodeType: the elements here are made in this realm and carry no
		type, and the script runs in a context of its own. A node is given its
		type, and the context this realm's Object	*/
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

	// The wire is the page's: the real api of Nino.admin.js posts through it
	Nino.http.sendRequest = function( uri, method, callback, data ) {

		calls.push( { uri : uri, method : method, action : data.action, data : data.data } );

		const answered = answer( data.action );
		const give = function() { callback( { status : answered[0], responseJSON : answered[1] } ) };

		if( options.hold === data.action )
			return held.push( give );

		give();
	};

	// The shell's registry of unsaved input, with nothing unsaved in it and an exit that is never refused
	Nino.admin = { dirty : options.dirty || { register : function() {}, isDirty : function() { return false }, guard : function( names, proceed ) { proceed() } } };

	load( '_admin/assets/Nino.admin.js', context );
	vm.runInContext( source, context, { filename : 'admin.js' } );

	/*	The page is ready: the document fires DOMContentLoaded, and the timers
		Nino.js asked for run - the callbacks of the ready event are Nino.js's
		to call, and so is the error one of them throws again	*/
	const ready = function() {
		( heard['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn( { type : 'DOMContentLoaded' } ) } );
		while( timers.length > 0 )
			timers.shift()();
	};

	// The shell shows the panel; the first showing is what asks for the list
	if( options.start !== 'none' )
		Nino.admin.search.showCurrent();

	const found = function( root, predicate ) { return findAll( root, predicate )[0] || null };

	return {
		ready		: ready,
		list		: list,
		type		: type,
		probe		: probe,
		calls		: calls,
		panel		: Nino.admin.search,
		release	: function() { held.splice( 0 ).forEach( function( give ) { give() } ) },
		byId		: function( id ) { return found( body, function( el ) { return el.id === id } ) },
		hidden	: function( pane ) { return pane.classList.contains('admin-hidden') },
		// The row of one type on the list
		row			: function( uri ) { return found( list, function( el ) { return el.tagName === 'TR' && el.getAttribute('data-type') === uri } ) },
		// The button of a row or a bar, by its words
		button	: function( root, words ) { return found( root, function( el ) { return el.tagName === 'BUTTON' && el.textContent === words } ) },
		// The editor's pieces
		slots		: function() { return withTag( type, 'SELECT' ) },
		slot		: function( priority ) { return found( type, function( el ) { return el.tagName === 'SELECT' && el.getAttribute('data-key') === 'slot-'+ priority } ) },
		save		: function() { return found( type, function( el ) { return el.id === 'search-save' } ) },
		msg			: function() { return found( type, function( el ) { return el.id === 'search-type-msg' } ) },
		asked		: function( action ) { return calls.filter( function( call ) { return call.action === action } ) },
	};
}

/** The panel with its editor open on a row's type */
function editing( options ) {
	const open = panel( options );
	fire( open.button( open.row( ( options && options.edit ) || '/products' ), WORDS['/_admin/search/label/edit'] ), 'click' );
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


// --- The list --------------------------------------------------------------------

const open = panel( { types : [ ROW, typeRow( { type : '/notes', title : 'Notes', fields : {}, configured : false, indexed : false, built : '', elements : 2, indexedElements : 0 } ) ] } );

check( 'the panel asks for the list once when it is first shown, through the workbench\'s own request: the project\'s _admin, a post, the action and a json payload',
	open.calls.length === 1 && open.calls[0].action === 'search/list' && open.calls[0].uri === '/_admin/' && open.calls[0].method === 'POST' && open.calls[0].data === '{}' );

/*	A page opened on the panel's address has the shell show the panel, and the
	page being ready is a second moment the page has: a panel that bound itself
	to it asked for its list twice. This one has no such bootstrap - Nino.js
	says when the page is ready, and nothing is bound to it	*/
open.ready();

check( 'the page being ready after that asks for nothing more', open.calls.length === 1 );

const loading = panel( { start : 'none' } );

loading.ready();

check( 'a page that is ready before the shell has shown the panel asks for nothing either: the panel starts when it is shown', loading.calls.length === 0 );

loading.panel.showCurrent();

check( '...and that showing asks for the list, once', loading.calls.length === 1 && loading.calls[0].action === 'search/list' );

open.panel.showCurrent();
check( 'a second showing asks for nothing and draws the list again from the answer', open.calls.length === 1 && withTag( open.list, 'TR' ).length === 3 );

check( 'only the list is on screen, the editor and the probe are put away',
	open.hidden( open.list ) === false && open.hidden( open.type ) === true && open.hidden( open.probe ) === true );

check( 'the list draws no heading of its own - the head over the pane names the panel - and the first thing under that head is its hint',
	[ 'H1', 'H2', 'H3', 'H4', 'H5', 'H6' ].every( function( tag ) { return withTag( open.list, tag ).length === 0 } ) === true
	&& open.list.children[0].tagName === 'P' && open.list.children[0].className === 'nino-admin-hint' && open.list.children[0].textContent === WORDS['/_admin/search/hint/intro'] );

const products = open.row('/products');
const cells = products.children;
check( 'a row names the type and its uri, then its fields as chips in the order of the slots with the slot as their title, then its counts',
	textOf( cells[0] ) === 'Products /products' && withClass( cells[1], 'search-chip' ).length === 1 && withClass( cells[1], 'search-chip' )[0].textContent === 'title'
	&& withClass( cells[1], 'search-chip' )[0].title === 'Priority 1' && cells[2].textContent === '3' );
check( 'the state is the workbench\'s badge, in the state of its word, with the day it was built beside it',
	withClass( cells[3], 'nino-admin-badge' ).length === 1 && withClass( cells[3], 'nino-admin-badge' )[0].dataset.state === 'ok'
	&& withClass( cells[3], 'nino-admin-badge' )[0].textContent === WORDS['/_admin/search/state/current'] && textOf( cells[3] ).indexOf( 'built 2026-09-12' ) !== -1 );
check( 'a type nobody configured says so, quietly, and has no fields to show',
	withClass( open.row('/notes').children[3], 'nino-admin-badge' )[0].dataset.state === 'muted' && open.row('/notes').children[1].textContent === '—' );

const states = panel( { types: [
	typeRow( { type : '/broken', fields : {}, issues : [ 'there is no element type "/broken"' ], indexed : false } ),
	typeRow( { type : '/missing', indexed : false, built : '' } ),
	typeRow( { type : '/stale', stale : true, indexedElements : 2 } ),
] } );
const level = function( uri ) { return withClass( states.row( uri ), 'nino-admin-badge' )[0].dataset.state };
check( 'a type that cannot be indexed is an error, one not built yet and one out of date are warnings',
	level('/broken') === 'error' && level('/missing') === 'warn' && level('/stale') === 'warn' );
check( '...a type whose configuration holds a name that does not resolve says why in the error colour, under its fields',
	withClass( states.row('/broken').children[1], 'nino-admin-error' ).length === 1 && withClass( states.row('/broken').children[1], 'nino-admin-error' )[0].textContent === 'there is no element type "/broken"' );
check( '...and a type that holds fewer elements in its index than it has says how many of how many', states.row('/stale').children[2].textContent === '2 / 3' );

const edit = open.button( products, WORDS['/_admin/search/label/edit'] );
check( 'the buttons of a row are the workbench\'s small ones, in the workbench\'s row of actions: Edit for every type, Rebuild only where there is something to build',
	edit.classList.contains('nino-admin-btn--small') === true && withClass( products, 'nino-admin-actions' ).length === 1
	&& open.button( products, WORDS['/_admin/search/label/rebuild'] ) === null
	&& states.button( states.row('/missing'), WORDS['/_admin/search/label/rebuild'] ).classList.contains('nino-admin-btn--small') === true
	&& states.button( states.row('/stale'), WORDS['/_admin/search/label/rebuild'] ) !== null && states.button( states.row('/broken'), WORDS['/_admin/search/label/rebuild'] ) === null );

const bar = withClass( open.list, 'nino-admin-actionbar' )[0];
check( 'under the list there is a bar with Rebuild all, the primary action, and the way into the probe, and a line for what they did',
	open.byId('search-createindex').classList.contains('nino-admin-btn-primary') === true && open.byId('search-createindex').parentNode === bar && open.byId('search-openprobe').parentNode === bar
	&& open.byId('search-list-msg').classList.contains('nino-admin-status') === true && open.byId('search-list-msg').getAttribute('role') === 'status' );

const none = panel( { types : [] } );
check( 'a project with no element types says so, and draws no table and no bar',
	withClass( none.list, 'nino-admin-empty' )[0].textContent === WORDS['/_admin/search/hint/empty'] && withTag( none.list, 'TABLE' ).length === 0 && none.byId('search-createindex') === null );

const failedLoad = panel( { fail : { 'search/list' : { status : 500, body : null } } } );
check( 'a list that could not be read is said in the first mount, with its status and the workbench\'s sentence for it',
	withClass( failedLoad.list, 'nino-admin-error' )[0].textContent === '(500) '+ WORDS['/_admin/common/error/load'] && failedLoad.panel.ready === false );


// --- Rebuilding ------------------------------------------------------------------

const rebuilding = panel( { hold : 'search/createindex', created : { created : 2, elements : 5, failed : [], skipped : { '/notes' : [ 'the model of "/notes" has no field "titel"', 'no field is named' ] }, issues : {} } } );
fire( rebuilding.byId('search-createindex'), 'click' );
check( 'Rebuild all asks for every index, says it is on its way on the list\'s line and cannot be pressed twice',
	rebuilding.asked('search/createindex').length === 1 && rebuilding.asked('search/createindex')[0].data === '{}'
	&& rebuilding.byId('search-list-msg').textContent === WORDS['/_admin/search/msg/creating'] && rebuilding.byId('search-createindex').disabled === true );
rebuilding.release();
check( '...and when it is built the list is asked for again, and the line says how many indexes of how many elements, in the plural',
	rebuilding.asked('search/list').length === 2 && rebuilding.byId('search-list-msg').textContent === 'Created 2 search indexes for 5 elements.'
	&& rebuilding.byId('search-list-msg').dataset.state === 'idle' && rebuilding.byId('search-createindex').disabled === false );
check( '...a type it could not index is named under the line with its reasons, in the error colour',
	withClass( rebuilding.byId('search-list-notes'), 'nino-admin-error' ).length === 1
	&& withClass( rebuilding.byId('search-list-notes'), 'nino-admin-error' )[0].textContent === '/notes: the model of "/notes" has no field "titel"; no field is named' );

const single = panel( { types : [ typeRow( { type : '/stale', stale : true } ) ] } );
fire( single.button( single.row('/stale'), WORDS['/_admin/search/label/rebuild'] ), 'click' );
check( 'Rebuild on a row asks for that type alone, and one index is said in the singular',
	single.asked('search/createindex')[0].data === '{"type":"/stale"}' && single.byId('search-list-msg').textContent === 'Created 1 search index for 3 elements.' );

const empty = panel( { created : { created : 0, elements : 0, failed : [], skipped : {}, issues : {} } } );
fire( empty.byId('search-createindex'), 'click' );
check( 'a rebuild that built nothing says that no type is configured for indexing', empty.byId('search-list-msg').textContent === WORDS['/_admin/search/msg/none'] );

const refusedBuild = panel( { fail : { 'search/createindex' : { status : 500, body : { error : 'could not create search indexes for /products' } } } } );
fire( refusedBuild.byId('search-createindex'), 'click' );
check( 'a rebuild the server could not do says what it says, with the status in front, on the line, and gives the button back - the list is not asked for again',
	refusedBuild.byId('search-list-msg').textContent === '(500) could not create search indexes for /products' && refusedBuild.byId('search-list-msg').dataset.state === 'error'
	&& refusedBuild.byId('search-createindex').disabled === false && refusedBuild.asked('search/list').length === 1 );


// --- The editor ------------------------------------------------------------------

const editor = editing();

check( 'Edit puts the editor on screen and the list away, and asks for nothing', editor.hidden( editor.type ) === false && editor.hidden( editor.list ) === true && editor.calls.length === 1 );
check( 'the editor starts with the way back, and names the type with what it edits',
	withClass( editor.type, 'nino-admin-contextbar' ).length === 1 && withTag( editor.type, 'A' )[0].textContent === WORDS['/_admin/search/label/back']
	&& withTag( editor.type, 'H2' )[0].textContent === 'Products — Indexed fields' );
check( 'two hints say how the slots weigh and what the fields are, and a type that is current has no third',
	withClass( editor.type, 'nino-admin-hint' ).map( function( el ) { return el.textContent } ).join('|') === WORDS['/_admin/search/hint/slots']+ '|'+ WORDS['/_admin/search/hint/indexable'] );

check( 'there is one select per priority the engine weighs, in the order of the priorities, each a field of the workbench\'s',
	editor.slots().length === 4 && editor.slots().map( function( select ) { return select.getAttribute('data-key') } ).join() === 'slot-0,slot-1,slot-2,slot-3'
	&& withClass( editor.byId('search-slots'), 'nino-admin-field' ).length === 4 );
check( '...named by their priority and their weight with two places, whatever json made of the number',
	textOf( editor.slot(0).parentNode ).indexOf('Priority 0 — weighs 1.00') === 0 && textOf( editor.slot(1).parentNode ).indexOf('Priority 1 — weighs 0.70') === 0
	&& textOf( editor.slot(3).parentNode ).indexOf('Priority 3 — weighs 0.25') === 0 );
check( '...over the type\'s own model, an empty choice first, with the field it is configured with picked',
	editor.slot(1).children.map( function( option ) { return option.value } ).join() === ',.uri,title,text' && editor.slot(1).value === 'title' && editor.slot(0).value === ''
	&& editor.slot(0).children[0].textContent === WORDS['/_admin/search/label/nofield'] );

choose( editor.slot(0), 'text' );
choose( editor.slot(1), '' );
check( 'choosing changes the working copy and nothing else: a field is set, an empty choice takes the slot out',
	JSON.stringify( editor.panel._editing.fields ) === '{"0":"text"}' && JSON.stringify( editor.panel._types[0].fields ) === '{"1":"title"}' );

const stale = editing( { types : [ typeRow( { stale : true } ) ] } );
check( 'an index out of date says so above the slots, as a refusal',
	withClass( stale.type, 'nino-admin-error' ).length === 1 && withClass( stale.type, 'nino-admin-error' )[0].textContent === WORDS['/_admin/search/hint/stale'] );

const unfit = editing( { types : [ typeRow( { fields : { 2 : 'image' } } ) ] } );
check( 'a configured field the model does not offer shows the empty choice rather than a blank', unfit.slot(2).value === '' );


// --- Saving the slots ------------------------------------------------------------

const saving = editing( { hold : 'search/createindex' } );
choose( saving.slot(0), 'text' );
fire( saving.save(), 'click' );

check( 'Save sends the type and the slots as they are now, says it is saving on the editor\'s line and cannot be pressed twice',
	saving.asked('search/save').length === 1 && saving.asked('search/save')[0].data === '{"type":"/products","fields":{"0":"text","1":"title"}}'
	&& saving.msg().dataset.state === 'saving' && saving.msg().textContent === WORDS['/_admin/common/msg/saving'] && saving.save().disabled === true );
fire( saving.save(), 'click' );
check( '...a second press sends nothing', saving.asked('search/save').length === 1 );
check( '...and builds the index out of them once they are stored', saving.asked('search/createindex').length === 1 && saving.asked('search/createindex')[0].data === '{"type":"/products"}' );
saving.release();
check( '...then the list is asked for again, is on screen, and says what was built on its own line',
	saving.asked('search/list').length === 2 && saving.hidden( saving.list ) === false && saving.hidden( saving.type ) === true
	&& saving.byId('search-list-msg').textContent === 'Created 1 search index for 3 elements.' );

const removed = editing( { save : { type : '/products', fields : {}, removed : true } } );
fire( removed.save(), 'click' );
check( 'a type left without slots is stored and builds nothing; the list says it is no longer indexed',
	removed.asked('search/createindex').length === 0 && removed.byId('search-list-msg').textContent === WORDS['/_admin/search/msg/removed'] );

const stored = editing( { save : { type : '/products', fields : {}, removed : false } } );
fire( stored.save(), 'click' );
check( '...and where nothing was removed the list says it was saved', stored.byId('search-list-msg').textContent === WORDS['/_admin/search/msg/saved'] );

const unbuilt = editing( { fail : { 'search/createindex' : { status : 500, body : { error : 'boom' } } } } );
fire( unbuilt.save(), 'click' );
check( 'slots that are stored but could not be built say they were saved, and the list is back',
	unbuilt.byId('search-list-msg').textContent === WORDS['/_admin/search/msg/saved'] && unbuilt.hidden( unbuilt.list ) === false && unbuilt.hidden( unbuilt.type ) === true );


// --- A refusal, in the words of its code -----------------------------------------

function refusal( body, status ) {
	const refused = editing( { fail : { 'search/save' : { status : status || 400, body : body } } } );
	choose( refused.slot(2), 'text' );
	fire( refused.save(), 'click' );
	return refused;
}

const field = refusal( { error : 'the model of "/products" has no field "titel"', code : 'search_field', params : [ '/products', 'titel' ] } );
check( 'a slot refused for a field the model has not says the sentence of its code, in the interface language, on the editor\'s line, as an error',
	field.msg().textContent === 'The model of "/products" has no field "titel".' && field.msg().dataset.state === 'error' && field.msg().getAttribute('role') === 'alert' );
check( '...and leaves the editor open with the button given back, and the index unbuilt',
	field.hidden( field.type ) === false && field.save().disabled === false && field.asked('search/createindex').length === 0 );

const priority = refusal( { error : 'priority "7" is not 0, 1, 2 or 3', code : 'search_priority', params : [ 7 ] } );
check( 'a priority nobody has says its sentence, with the number in it', priority.msg().textContent === 'There is no priority 7 - the four slots are 0, 1, 2 and 3.' );

const text = refusal( { error : 'the field "active" carries no text to search', code : 'search_text', params : [ 'active' ] } );
check( 'a field with no text in it says that, with its name', text.msg().textContent === 'The field "active" carries no text to search.' );

const notype = refusal( { error : 'there is no element type "/products"', code : 'search_notype', params : [ '/products' ] }, 404 );
check( 'a type that is gone says so, with its name, and a code for the type itself and for the list of fields have their words too',
	notype.msg().textContent === 'There is no element type "/products".' && WORDS['/_admin/error/search_type'] === 'That is not the name of an element type.'
	&& WORDS['/_admin/error/search_fields'] === 'The priorities were not sent as field names.' );

const broken = refusal( { error : 'could not write /nino/elements/index to config.php' }, 500 );
check( 'a write that failed has no code and is said as the server said it, with the status in front',
	broken.msg().textContent === '(500) could not write /nino/elements/index to config.php' );

const silent = refusal( null, 502 );
check( 'an answer with nothing in it is said in the panel\'s own sentence for what it was doing', silent.msg().textContent === '(502) '+ WORDS['/_admin/search/error/save'] );


// --- Unsaved input ---------------------------------------------------------------

const shell = registry();
const guarded = panel( { dirty : shell } );
const entry = shell.entries['search'] || null;

check( 'the panel registers under its own uri when it first asks, with the spec\'s three members', entry !== null && typeof entry.isDirty === 'function' && typeof entry.save === 'function' && typeof entry.discard === 'function' );
check( '...with the list on screen there is nothing unsaved', entry.isDirty() === false );

fire( guarded.button( guarded.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
check( '...and the slots as they were opened are what is saved', entry.isDirty() === false );

choose( guarded.slot(0), 'text' );
check( '...but a slot that is changed is not, and the editor\'s line says so', entry.isDirty() === true && guarded.msg().dataset.state === 'dirty' && guarded.msg().textContent === WORDS['/_admin/common/msg/dirty'] );

choose( guarded.slot(0), '' );
check( '...and put back as it was it is saved again, and the line goes quiet', entry.isDirty() === false && guarded.msg().dataset.state === 'idle' );

choose( guarded.slot(0), 'text' );
withTag( guarded.type, 'A' )[0].listeners['click'].forEach( function( listener ) { listener( { preventDefault : function() {} } ) } );
check( 'the back link asks the shell first, naming this panel, and goes back when told to',
	shell.asked.length === 1 && shell.asked[0][0] === 'search' && guarded.panel._editing === null && guarded.hidden( guarded.list ) === false );

shell.answer = 'wait';
fire( guarded.button( guarded.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
withTag( guarded.type, 'A' )[0].listeners['click'].forEach( function( listener ) { listener( { preventDefault : function() {} } ) } );
check( '...and stays where it is while the shell is still asking', shell.asked.length === 2 && guarded.panel._editing !== null && guarded.hidden( guarded.type ) === false );
shell.answer = 'proceed';

const told = [];
const asked = panel( { dirty : shell, hold : 'search/createindex' } );
fire( asked.button( asked.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
choose( asked.slot(0), 'text' );
shell.entries['search'].save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save stores the slots, and says nothing while the index is still being built from them',
	told.length === 0 && asked.asked('search/save').length === 1 && JSON.parse( asked.asked('search/save')[0].data ).fields[0] === 'text' );
asked.release();
check( '...and says it was saved - once - when the index is built, so a log out that follows loses neither',
	told.length === 1 && told[0] === true && asked.asked('search/createindex').length === 1 );

const none2 = [];
const emptied = panel( { dirty : shell, save : { type : '/products', fields : {}, removed : true } } );
fire( emptied.button( emptied.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
shell.entries['search'].save( function( ok ) { none2.push( ok ) } );
check( '...a type left without slots builds nothing, so the answer to the save is the shell\'s at once',
	none2.length === 1 && none2[0] === true && emptied.asked('search/createindex').length === 0 );

const refused = panel( { dirty : shell, fail : { 'search/save' : { status : 400, body : { error : 'x', code : 'search_type' } } } } );
fire( refused.button( refused.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
choose( refused.slot(0), 'text' );
const answers = [];
shell.entries['search'].save( function( ok ) { answers.push( ok ) } );
check( 'a Save the shell asked for that the server refuses says it did not save, with the reason on screen',
	answers.length === 1 && answers[0] === false && refused.msg().textContent === WORDS['/_admin/error/search_type'] );
refused.panel.showCurrent();
check( '...and the shell showing the panel again keeps the reason, and the slots that were not saved',
	refused.msg().textContent === WORDS['/_admin/error/search_type'] && refused.panel._editing !== null && refused.slot(0).value === 'text' );

answers.length = 0;
refused.panel._editing = null;
shell.entries['search'].save( function( ok ) { answers.push( ok ) } );
check( 'a Save with no editor open says it did not', answers.length === 1 && answers[0] === false );

const clean = panel( { dirty: shell } );
fire( clean.button( clean.row('/products'), WORDS['/_admin/search/label/edit'] ), 'click' );
clean.panel.showCurrent();
check( 'a showing of the panel while the editor holds nothing unsaved draws the editor again, from its working copy',
	clean.hidden( clean.type ) === false && clean.slots().length === 4 && clean.calls.length === 1 );

shell.entries['search'].discard();
choose( clean.slot(0), 'text' );
check( 'the shell\'s Discard takes the slots as they are for saved', shell.entries['search'].isDirty() === true );
shell.entries['search'].discard();
check( '...and there is nothing unsaved left', shell.entries['search'].isDirty() === false );


// --- The probe -------------------------------------------------------------------

const asking = panel( { probe : { query : 'orbit', limit : 2, hits : [
	{ uri : '/articles/orbit-summary', type : '/articles', label : 'Remote station', score : 1.75, coverage : 1, matched : [ 'summary' ] },
	{ uri : '/articles/orbit-title', type : '/articles', label : 'Orbit', score : 1.2, coverage : 0.5, matched : [ 'title', 'author' ] },
] } } );
fire( asking.byId('search-openprobe'), 'click' );

check( 'Try it puts the probe on screen and the list away, with the way back, a heading and its hint',
	asking.hidden( asking.probe ) === false && asking.hidden( asking.list ) === true && withClass( asking.probe, 'nino-admin-contextbar' ).length === 1
	&& withTag( asking.probe, 'H2' )[0].textContent === WORDS['/_admin/search/label/probe'] && withClass( asking.probe, 'nino-admin-hint' )[0].textContent === WORDS['/_admin/search/hint/probe'] );

const query = asking.byId('search-probe-query');
check( 'the query is the workbench\'s text field of the search kind, named, with the id the stylesheet and the labels know',
	query.type === 'search' && query.parentNode.tagName === 'LABEL' && query.parentNode.className === 'nino-admin-field' && textOf( query.parentNode ).indexOf( WORDS['/_admin/search/label/query'] ) === 0 );
check( '...and one checkbox per type that is indexed, all of them checked at first',
	withTag( asking.byId('search-probe-types'), 'INPUT' ).length === 1 && withTag( asking.byId('search-probe-types'), 'INPUT' )[0].checked === true );

const locale = asking.byId('search-probe-locale');
check( 'with more than one language the probe asks in the one picked from a labelled select of the workbench\'s, in its form',
	locale !== null && locale.tagName === 'SELECT' && locale.children.map( function( option ) { return option.value } ).join() === 'de_DE,en_US'
	&& locale.parentNode.tagName === 'LABEL' && textOf( locale.parentNode ).indexOf( WORDS['/_admin/search/label/locale'] ) === 0 );

query.value = 'orbit';
choose( locale, 'en_US' );
fire( asking.byId('search-probe-run'), 'click' );
check( 'Search sends what was typed, the types that are checked and the language picked',
	asking.asked('search/probe').length === 1 && asking.asked('search/probe')[0].data === '{"query":"orbit","types":["/products"],"locale":"en_US"}' );

const hits = withTag( asking.byId('search-probe-hits'), 'TR' );
check( 'the hits come as a table in the order they were answered, numbered, labelled, with the score, the share of the query they cover and where they matched',
	hits.length === 3 && hits[1].children.map( function( td ) { return td.textContent } ).join('|') === '1|Remote station|1.75|100%|summary'
	&& hits[2].children.map( function( td ) { return td.textContent } ).join('|') === '2|Orbit|1.2|50%|title, author' && hits[1].getAttribute('data-uri') === '/articles/orbit-summary' );
check( '...and when the answer holds as many hits as the limit, a hint says that these are the best of them',
	withClass( asking.byId('search-probe-results'), 'nino-admin-hint' )[0].textContent === 'The best 2 hits.' );

fire( query, 'keydown', { key : 'Enter' } );
check( 'Enter in the query runs it too', asking.asked('search/probe').length === 2 );

asking.byId('search-probe-types').children[0].children[0].checked = false;
fire( asking.byId('search-probe-types').children[0].children[0], 'change' );
fire( asking.byId('search-probe-run'), 'click' );
check( 'a type that is unchecked is not asked for', JSON.parse( asking.asked('search/probe')[2].data ).types.length === 0 );

withTag( asking.probe, 'A' )[0].listeners['click'].forEach( function( listener ) { listener( { preventDefault : function() {} } ) } );
check( 'the way back leaves the probe for the list', asking.hidden( asking.list ) === false && asking.hidden( asking.probe ) === true );
fire( asking.byId('search-openprobe'), 'click' );
check( 'the probe remembers its query, its language and its hits for the next time it is opened',
	asking.byId('search-probe-query').value === 'orbit' && asking.byId('search-probe-locale').value === 'en_US' && withTag( asking.byId('search-probe-hits'), 'TR' ).length === 3 );

const nothing = panel( { probe : { query : 'zzz', hits : [], limit : 25 }, locales : [ 'de_DE' ] } );
fire( nothing.byId('search-openprobe'), 'click' );
fire( nothing.byId('search-probe-run'), 'click' );
check( 'a search that found nothing says so', withClass( nothing.byId('search-probe-results'), 'nino-admin-empty' )[0].textContent === WORDS['/_admin/search/hint/probe-empty'] );
check( '...and with one language there is no select to pick it from, and the probe asks for the empty one', nothing.byId('search-probe-locale') === null
	&& JSON.parse( nothing.asked('search/probe')[0].data ).locale === '' );

const unsearchable = panel( { types : [ typeRow( { fields : {}, configured : false } ) ] } );
fire( unsearchable.byId('search-openprobe'), 'click' );
check( 'a project with no type that is indexed has nothing to ask: the probe says so and draws no form',
	withClass( unsearchable.probe, 'nino-admin-empty' )[0].textContent === WORDS['/_admin/search/msg/none'] && unsearchable.byId('search-probe-query') === null );

const failing = panel( { fail : { 'search/probe' : { status : 500, body : { error : 'boom' } } } } );
fire( failing.byId('search-openprobe'), 'click' );
fire( failing.byId('search-probe-run'), 'click' );
check( 'a probe that failed is said where its hits would have been, as the workbench says it', withClass( failing.byId('search-probe-results'), 'nino-admin-error' )[0].textContent === '(500) boom' );

done();
