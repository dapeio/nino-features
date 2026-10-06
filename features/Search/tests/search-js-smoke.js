/**
 *	Nino
 *	search-js-smoke.js	What the panel's admin.js does over a dom stand-in:
 *											that it asks the workbench's own request helper where
 *											there is one and posts from the project's own directory
 *											where there is not, that a failure reads the way the
 *											workbench says it where it has the words and "(status)
 *											message" where it has not, that the type editor's
 *											message is the workbench's status line where there is
 *											one, and that the shell's registry of unsaved input is
 *											told what is on screen, can press Save, and is asked
 *											before the editor's back link leaves it.
 *
 *											No jsdom, no dependency: a small element stand-in with
 *											just enough of Nino.adminUi for the editor to draw
 *											itself, so this runs with nothing but node.
 *
 *	Usage: node features/Search/tests/search-js-smoke.js
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

/** Every descendant of $root, depth first */
function descendants( root ) {
	let all = [];
	for( const child of root.children ) {
		all.push( child );
		all = all.concat( descendants( child ) );
	}
	return all;
}

/** One element, with just enough of the interface the panel reaches for */
function element( tag ) {

	const el = {
		tagName					: tag.toUpperCase(),
		attributes			: {},
		children				: [],
		dataset					: {},
		id							: '',
		href						: '',
		type						: '',
		value						: '',
		disabled				: false,
		selected				: false,
		className				: '',
		listeners				: {},
		_text						: '',
		classList				: { toggle : function() {}, add : function() {}, remove : function() {} },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { this.children.push( child ); return child },
		addEventListener: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		fire						: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {}, target : el } ) } ) },
	};

	Object.defineProperty( el, 'textContent', {
		get : function() { return el._text + el.children.map( function( c ) { return c.textContent } ).join('') },
		set : function( value ) { el._text = String( value ); el.children = [] },
	} );

	Object.defineProperty( el, 'innerHTML', {
		get : function() { return '' },
		set : function() { el.children = []; el._text = '' },
	} );

	return el;
}

const TEXT = {
	'/_admin/search/error/save'		: 'The slots could not be saved.',
	'/_admin/search/msg/saved'		: 'Saved.',
	'/_admin/search/msg/created'	: '%d index entries from %d elements.',
	'/_admin/search/label/slot'		: 'Slot %s',
	'/_admin/search/label/weight'	: 'weight %s',
};

const ROW = { type : '/products', title : 'Products', model : [ 'title', 'text' ], fields : { 1 : 'title' }, configured : true, indexed : true, stale : false, issues : [] };

/**
 *	The panel with its editor open on one type
 *
 *	@param		{Object}	options		{ send } stands in for the post of a Nino before 1.3.2;
 *															api, status, dirty for Nino.adminUi.api, Nino.adminUi.status
 *															and the shell's Nino.admin.dirty registry (a newer
 *															workbench); answer: what search/save answers, { status, body };
 *															hold: the action that stays unanswered until release()
 */
function panel( options ) {

	options = options || {};

	const panes = { 'search-list' : element('div'), 'search-type' : element('div'), 'search-probe' : element('div') };
	const posted = [];
	const held = [];

	const dc = {
		createElement		: function( tag ) { return element( tag ) },
		createTextNode	: function( text ) { const el = element('span'); el.textContent = text; return el },
		getElementById	: function( id ) {
			if( panes[id] !== undefined )
				return panes[id];
			return descendants( panes['search-type'] ).filter( function( el ) { return el.id === id } )[0] || null;
		},
	};

	const Nino = {
		content	: { getText : function( key ) { return TEXT[key] !== undefined ? TEXT[key] : key } },
		events	: { bindCallback : function() {} },
		adminUi	: {
			contextBar	: function( back ) { const el = element('div'); el.appendChild( back ); return el },
			actionBar		: function( bar ) { const el = element('div'); el.appendChild( bar ); return el },
			emptyState	: function( text ) { const el = element('p'); el.textContent = text; return el },
		},
		http		: { sendRequest : function( uri, method, callback, data ) {
			posted.push( [ uri, method, data ] );
			const answer = data.action === 'search/save' && options.answer ? options.answer : { status : 200, body : { fields : { 1 : 'title' }, created : 0 } };
			// An action held back until release() answers it, to see what the
			// panel reports before and after
			if( options.hold === data.action )
				return held.push( function() { callback( { status : answer.status, responseJSON : answer.body } ) } );
			callback( { status : answer.status, responseJSON : answer.body } );
		} },
	};

	if( options.api )
		Nino.adminUi.api = options.api;
	if( options.status )
		Nino.adminUi.status = options.status;
	Nino.admin = {};
	if( options.dirty )
		Nino.admin.dirty = options.dirty;

	const sandbox = { console : console, document : dc, Nino : Nino, window : { Nino : Nino } };
	sandbox.window.window = sandbox.window;
	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	const search = Nino.admin.search;
	search._weights = { 1 : 1, 2 : 0.7 };
	search._types = [ ROW ];
	search._ready = true;
	search._editType( ROW );

	return {
		panel		: search,
		type		: panes['search-type'],
		posted	: posted,
		release	: function() { held.splice( 0 ).forEach( function( answer ) { answer() } ) },
		back		: function() { return descendants( panes['search-type'] ).filter( function( el ) { return el.tagName === 'A' } )[0] },
		save		: function() { return dc.getElementById('search-save') },
		msg			: function() { return dc.getElementById('search-type-msg') },
		slots		: function() { return dc.getElementById('search-slots') },
		slot		: function( priority ) { return dc.getElementById('search-slot-'+ priority) },
	};
}


// --- Asking the workbench ------------------------------------------------------------

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const bare = panel();
bare.panel._apiCall( 'list', { a : 1 }, function() {} );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	bare.posted.length === 1 && bare.posted[0][0] === '[[/nino/dir]]/_admin/' && bare.posted[0][1] === 'POST'
	&& bare.posted[0][2].action === 'search/list' && bare.posted[0][2].data === '{"a":1}' );

const routed = [];
const viaApi = panel( { api : { call : function( action, payload, callback ) { routed.push( [ action, payload ] ); callback( 200, { via : 'api' } ) } } } );
let answered = null;
viaApi.panel._apiCall( 'list', { b : 2 }, function( status, response ) { answered = [ status, response ] } );
check( 'with it the action \'search/<action>\' and the payload go to the helper, and nothing is posted by hand',
	routed.length === 1 && routed[0][0] === 'search/list' && routed[0][1].b === 2 && viaApi.posted.length === 0 && answered[1].via === 'api' );

check( 'a failure says "(status) message" as it always did where the shell has no errorText()',
	bare.panel._errorText( 503, { error : 'Busy' }, '/_admin/search/error/save' ) === '(503) Busy'
	&& bare.panel._errorText( 503, null, '/_admin/search/error/save' ) === '(503) The slots could not be saved.' );
const withText = panel( { api : { call : function() {}, errorText : function( status, response, key ) { return 'told '+ status+ ' ['+ key+ ']' } } } );
check( '...and what errorText() makes of it where it has one', withText.panel._errorText( 503, { error : 'Busy' }, '/_admin/search/error/save' ) === 'told 503 [/_admin/search/error/save]' );


// --- Saving the slots, with the sentences of the panel's own ---------------------------

const refused = panel( { answer : { status : 422, body : { error : 'Not a field of the type' } } } );
refused.save().fire('click');
check( 'a refused save reads "(422) ..." as an error on the editor\'s message line, and gives the button back',
	refused.msg().textContent === '(422) Not a field of the type' && refused.msg().className === 'nino-admin-error' && refused.save().disabled === false );


// --- The workbench's status line ---------------------------------------------------------

// Nino.adminUi.status() in miniature: what it was told, and what it follows
const lines = [];
const statusStub = function( el ) {
	const line = { el : el, told : [], bound : null,
		saving : function() { line.told.push('saving') },
		error : function( status, response, key ) { line.told.push( [ 'error', status, response.error, key ].join(' ') ) },
		bind : function( form ) { line.bound = form },
	};
	lines.push( line );
	return line;
};

const lined = panel( { status : statusStub, answer : { status : 500, body : { error : 'Down' } } } );
const line = lines[lines.length - 1];
check( 'where the shell has a status line the editor\'s message is one, following the slots - drawn anew with every screen, where the editor\'s wrap outlives them', line.el === lined.msg() && line.bound === lined.slots() && line.bound !== lined.type );
lined.save().fire('click');
check( '...told when the save starts, and a failure with the status, the body and the panel\'s own sentence for it',
	line.told.join() === 'saving,error 500 Down /_admin/search/error/save' && lined.msg().textContent === '' );


// --- Unsaved input -----------------------------------------------------------------------

// The shell's registry in miniature: what the panel watches, what it was asked
// to take for saved, and what it was asked before it let an exit through
const registry = { watched : {}, snapshots : [], asked : [], answer : 'proceed' };
registry.watchForm = function( name, getter, save ) { registry.watched[name] = { getter : getter, save : save } };
registry.snapshot = function( name ) { registry.snapshots.push( name ) };
registry.guard = function( names, proceed ) { registry.asked.push( names ); if( registry.answer === 'proceed' ) proceed() };
registry.isDirty = function( names ) { return registry.watched[names[0]] !== undefined && registry.unsaved === true };
registry.unsaved = false;

const guarded = panel( { dirty : registry } );
const watched = registry.watched['search'] || null;
check( 'where the shell has the registry the panel watches its editor under its own uri', watched !== null && watched.getter() === guarded.type && typeof watched.save === 'function' );
check( '...and what is on screen is taken for saved when the editor opens', registry.snapshots.length === 1 && registry.snapshots[0] === 'search' );

guarded.back().fire('click');
check( 'the editor\'s back link asks the shell first, naming this panel, and goes back when told to', registry.asked.length === 1 && registry.asked[0][0] === 'search' && guarded.panel._editing === null );

const kept = panel( { dirty : registry } );
registry.answer = 'wait';
kept.back().fire('click');
check( '...and stays where it is while the shell is still asking', registry.asked.length === 2 && kept.panel._editing !== null );
registry.answer = 'proceed';

let told = [];
const saved = panel( { dirty : registry, hold : 'search/createindex' } );
saved.slot( 1 ).value = 'text';
saved.slot( 1 ).fire('change');
registry.watched['search'].save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save posts the slots, and says nothing while the index is still being built from them',
	told.length === 0 && saved.posted.filter( function( p ) { return p[2].action === 'search/save' } ).length === 1
	&& JSON.parse( saved.posted.filter( function( p ) { return p[2].action === 'search/save' } )[0][2].data ).fields[1] === 'text' );
saved.release();
check( '...and says it was saved - once - when the index is built, so a log out that follows loses neither',
	told.length === 1 && told[0] === true && saved.posted.filter( function( p ) { return p[2].action === 'search/createindex' } ).length === 1 );

told = [];
const emptied = panel( { dirty : registry, answer : { status : 200, body : { removed : true, fields : {} } } } );
registry.watched['search'].save( function( ok ) { told.push( ok ) } );
check( '...a type left without slots builds nothing, so the answer to the save is the shell\'s', told.length === 1 && told[0] === true
	&& emptied.posted.filter( function( p ) { return p[2].action === 'search/createindex' } ).length === 0 );

const turnedDown = panel( { dirty : registry, answer : { status : 500, body : { error : 'Down' } } } );
told = [];
registry.watched['search'].save( function( ok ) { told.push( ok ) } );
check( '...and says it was not when the server refused them', told.length === 1 && told[0] === false );

told = [];
turnedDown.panel._editing = null;
registry.watched['search'].save( function( ok ) { told.push( ok ) } );
check( '...as it does when there is no editor to save', told.length === 1 && told[0] === false );

// A Save the shell asked for that fails brings the panel on screen again
// (showCurrent()): the reason it gave has to still be there
const failing = panel( { dirty : registry, answer : { status : 500, body : { error : 'Down' } } } );
registry.unsaved = true;
told = [];
registry.watched['search'].save( function( ok ) { told.push( ok ) } );
check( 'a Save the shell asked for that the server refuses says it did not save, with the reason on screen',
	told.length === 1 && told[0] === false && failing.msg().textContent !== '' );
const reason = failing.msg().textContent;
failing.panel.showCurrent();
check( '...and the shell showing the panel again keeps the reason, and the slots that were not saved',
	failing.msg() !== null && failing.msg().textContent === reason && failing.panel._editing !== null );
registry.unsaved = false;

const plain = panel();
plain.back().fire('click');
check( 'on a workbench without the registry the back link leaves the editor at once', plain.panel._editing === null );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
