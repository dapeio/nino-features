/**
 *	Nino
 *	hello-js-smoke.js		What the panel's admin.js does over a dom stand-in:
 *											that it asks the workbench's own request helper where
 *											there is one and posts from the project's own directory
 *											where there is not, that a failure reads the way the
 *											workbench says it where it has the words and "(status)
 *											message" where it has not, that the save line is the
 *											workbench's status line where there is one, and that
 *											the shell's registry of unsaved input is told what is
 *											on screen, can press Save, and is not drawn over.
 *
 *											No jsdom, no dependency: a small element stand-in with
 *											just enough of Nino.adminUi for the panel to draw
 *											itself, so this runs with nothing but node.
 *
 *	Usage: node features/Hello/tests/hello-js-smoke.js
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
		type						: '',
		value						: '',
		placeholder			: '',
		maxLength				: 0,
		disabled				: false,
		listeners				: {},
		_text						: '',
		focused					: false,
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { this.children.push( child ); return child },
		addEventListener: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		fire						: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { target : el } ) } ) },
		focus						: function() { this.focused = true },
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
	'/_admin/hello/error/long'	: 'Too long.',
	'/_admin/hello/error/save'	: 'It could not be saved.',
	'/_admin/hello/msg/saved'		: 'Saved.',
	'/_admin/common/msg/saving'	: 'Saving ...',
};

/**
 *	The panel, with the answers a test gives it
 *
 *	@param		{Object}	options		{ name } - what hello/list answers with; send: the post
 *															of a Nino before 1.3.2; api, status, dirty: stand in for
 *															Nino.adminUi.api, Nino.adminUi.status and the shell's
 *															Nino.admin.dirty registry (a newer workbench); answer: what
 *															hello/save answers, { status, body }
 */
function panel( options ) {

	options = options || {};

	const wrap = element('div');
	wrap.id = 'hello-form';

	const posted = [];
	const dc = {
		createElement		: function( tag ) { return element( tag ) },
		getElementById	: function( id ) { return id === 'hello-form' ? wrap : descendants( wrap ).filter( function( el ) { return el.id === id } )[0] || null },
	};

	const Nino = {
		content	: { getText : function( key ) { return TEXT[key] !== undefined ? TEXT[key] : key } },
		events	: { bindCallback : function() {} },
		adminUi	: {
			actionBar : function( bar ) { const el = element('div'); el.appendChild( bar ); return el },
		},
		http		: { sendRequest : function( uri, method, callback, data ) {
			posted.push( [ uri, method, data ] );
			if( data.action === 'hello/list' )
				return callback( { status : 200, responseJSON : { name : options.name || '', fallback : 'World', greeting : 'Hello' } } );
			const answer = options.answer || { status : 200, body : { name : JSON.parse( data.data ).name } };
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

	Nino.admin.hello.init();

	return {
		panel		: Nino.admin.hello,
		wrap		: wrap,
		posted	: posted,
		input		: function() { return dc.getElementById('hello-name') },
		save		: function() { return dc.getElementById('hello-save') },
		msg			: function() { return dc.getElementById('hello-msg') },
	};
}


// --- Asking the workbench ------------------------------------------------------------

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const bare = panel( { name : 'Ada' } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	bare.posted.length === 1 && bare.posted[0][0] === '[[/nino/dir]]/_admin/' && bare.posted[0][1] === 'POST'
	&& bare.posted[0][2].action === 'hello/list' && bare.posted[0][2].data === '{}' );
check( '...and draws what it answered', bare.input().value === 'Ada' && bare.input().placeholder === 'World' );

const routed = [];
const viaApi = panel( { api : { call : function( action, payload, callback ) {
	routed.push( [ action, payload ] );
	callback( 200, { name : 'Grace', fallback : 'World', greeting : 'Hello' } );
} } } );
check( 'with it the panel asks the helper for \'hello/<action>\' and posts nothing by hand',
	routed.length === 1 && routed[0][0] === 'hello/list' && viaApi.posted.length === 0 && viaApi.input().value === 'Grace' );

check( 'a failure says "(status) message" as it always did where the shell has no errorText()',
	bare.panel._errorText( 503, { error : 'Busy' }, '/_admin/hello/error/save' ) === '(503) Busy'
	&& bare.panel._errorText( 503, null, '/_admin/hello/error/save' ) === '(503) It could not be saved.' );
const withText = panel( { api : { call : function( action, payload, callback ) { callback( 200, { name : '', fallback : 'World', greeting : 'Hello' } ) }, errorText : function( status, response, key ) { return 'told '+ status+ ' ['+ key+ ']' } } } );
check( '...and what errorText() makes of it where it has one', withText.panel._errorText( 503, { error : 'Busy' }, '/_admin/hello/error/save' ) === 'told 503 [/_admin/hello/error/save]' );


// --- Saving, with the sentences of the panel's own -------------------------------------

const saving = panel( { name : 'Ada' } );
saving.input().value = 'Grace';
saving.save().fire('click');
check( 'a save posts the name, and the screen says it is saved in the panel\'s own sentence',
	saving.posted.length === 2 && saving.posted[1][2].action === 'hello/save' && saving.posted[1][2].data === '{"name":"Grace"}'
	&& saving.msg().textContent === 'Saved.' && saving.input().value === 'Grace' );

const refused = panel( { name : 'Ada', answer : { status : 422, body : { error : 'No such name' } } } );
refused.save().fire('click');
check( 'a refused save reads "(422) No such name" as an error, and gives the button back',
	refused.msg().textContent === '(422) No such name' && refused.msg().className === 'nino-admin-error' && refused.save().disabled === false );

const long = panel( { name : 'Ada' } );
long.input().value = 'x'.repeat( 61 );
long.save().fire('click');
check( 'a name that is too long is refused on the screen, before anything is sent',
	long.posted.length === 1 && long.msg().textContent === 'Too long.' && long.msg().className === 'nino-admin-error' && long.input().focused === true );


// --- Saying what happened, with the workbench's status line ------------------------------

// Nino.adminUi.status() in miniature: what it was told, and what it follows
const lines = [];
const statusStub = function( el ) {
	const line = { el : el, told : [], bound : null, isDirty : null,
		saving : function() { line.told.push('saving') },
		saved : function() { line.told.push('saved') },
		fail : function( text ) { line.told.push( 'fail '+ text ) },
		error : function( status, response, key ) { line.told.push( [ 'error', status, response.error, key ].join(' ') ) },
		bind : function( form, isDirty ) { line.bound = form; line.isDirty = isDirty },
	};
	lines.push( line );
	return line;
};

const lined = panel( { name : 'Ada', status : statusStub } );
const line = lines[lines.length - 1];
check( 'where the shell has a status line the panel\'s message is one, following the field',
	line.el === lined.msg() && line.bound !== null && typeof line.isDirty === 'function' );
check( '...and the field typed back to what is saved is not a change', line.isDirty() === false && ( lined.input().value = 'Grace' ) && line.isDirty() === true );
lined.save().fire('click');
// The screen is drawn again from what the save answered, so "saved" is told to the line of the new screen
check( '...told when the save starts, and - on the screen drawn from the answer - when it is done, with no sentence of the panel\'s own',
	line.told.join() === 'saving' && lines[lines.length - 1] !== line && lines[lines.length - 1].told.join() === 'saved' && lined.msg().textContent === '' );

const failed = panel( { name : 'Ada', status : statusStub, answer : { status : 500, body : { error : 'Down' } } } );
failed.save().fire('click');
check( '...and a failed one is told as an error with the status, the body and the panel\'s own sentence for it',
	lines[lines.length - 1].told.join() === 'saving,error 500 Down /_admin/hello/error/save' );

const tooLong = panel( { name : 'Ada', status : statusStub } );
tooLong.input().value = 'x'.repeat( 61 );
tooLong.save().fire('click');
check( '...and a refusal before anything is sent is told as the error it is', lines[lines.length - 1].told.join() === 'fail Too long.' );


// --- Unsaved input ---------------------------------------------------------------------

// The shell's registry in miniature: what the panel watches, what it was asked
// to take for saved, and whether the shell thinks it holds input
const registry = { watched : {}, snapshots : [], dirty : false };
registry.watchForm = function( name, getter, save ) { registry.watched[name] = { getter : getter, save : save } };
registry.snapshot = function( name ) { registry.snapshots.push( name ) };
registry.isDirty = function( names ) { return registry.dirty && names[0] === 'hello' };

const guarded = panel( { name : 'Ada', dirty : registry } );
const watched = registry.watched['hello'] || null;
check( 'where the shell has the registry the panel watches its screen under its own uri', watched !== null && watched.getter() === guarded.wrap && typeof watched.save === 'function' );
check( '...and what is on screen is taken for saved when it is drawn', registry.snapshots.length === 1 && registry.snapshots[0] === 'hello' );

// Another panel and back again
const mark = element('span');
guarded.wrap.appendChild( mark );
registry.dirty = true;
guarded.panel.showCurrent();
check( 'coming back to the panel does not draw it over a name nobody has saved', guarded.wrap.children.indexOf( mark ) !== -1 );
registry.dirty = false;
guarded.panel.showCurrent();
check( '...and a screen that holds nothing is drawn again as before', guarded.wrap.children.indexOf( mark ) === -1 && registry.snapshots.length === 2 );

guarded.input().value = 'Grace';
let told = [];
watched.save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save posts the name and says it was saved, once, with the screen taken for saved afterwards',
	told.length === 1 && told[0] === true && guarded.posted.filter( function( p ) { return p[2].action === 'hello/save' } ).length === 1
	&& registry.snapshots.length === 3 );

panel( { name : 'Ada', dirty : registry, answer : { status : 500, body : { error : 'Down' } } } );
told = [];
registry.watched['hello'].save( function( ok ) { told.push( ok ) } );
check( '...and says it was not when the server refused it', told.length === 1 && told[0] === false );

const tooMuch = panel( { name : 'Ada', dirty : registry } );
tooMuch.input().value = 'x'.repeat( 61 );
told = [];
registry.watched['hello'].save( function( ok ) { told.push( ok ) } );
check( '...or when the name is too long to send', told.length === 1 && told[0] === false && tooMuch.posted.length === 1 );

const without = panel( { name : 'Ada' } );
const markPlain = element('span');
without.wrap.appendChild( markPlain );
without.panel.showCurrent();
check( 'a workbench without the registry draws the screen again as it always did', without.wrap.children.indexOf( markPlain ) === -1 );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
