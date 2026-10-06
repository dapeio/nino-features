/**
 *	Nino
 *	protected-js-smoke.js		What the Protected area panel's script draws and
 *													posts, over a dom stand-in: the page list with its
 *													states (ticked, covered by a wider path, a title or
 *													the path), what a save sends - and that an empty
 *													choice is asked about first - the two password
 *													entries checked before anything is sent, the new
 *													password posted as "pw" and never written back into
 *													the screen, and the sign-out asked about first.
 *
 *													No jsdom, no dependency: an element stand-in of the
 *													kind the other feature tests build, so this runs with
 *													nothing but node. protected-smoke.php runs it through
 *													node when node is on the path, so bin/check.sh and CI
 *													cover it; it also runs on its own.
 *
 *	Usage: node features/ProtectedArea/tests/protected-js-smoke.js
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

/**
 *	One element, with just enough of the interface admin.js reaches for
 */
function element( tag ) {
	const el = {
		tagName				: String( tag ).toUpperCase(),
		children			: [],
		className			: '',
		textContent		: '',
		id						: '',
		value					: '',
		checked				: false,
		disabled			: false,
		attrs					: {},
		listeners			: {},
		appendChild		: function( child ) { this.children.push( child ); return child },
		setAttribute	: function( name, value ) { this.attrs[ name ] = value },
		addEventListener : function( type, fn ) { ( this.listeners[ type ] = this.listeners[ type ] || [] ).push( fn ) },
	};
	Object.defineProperty( el, 'innerHTML', { get : function() { return '' }, set : function() { this.children.length = 0 } } );
	el.classList = {
		toggle : function( name, force ) {
			const names = el.className.split(' ').filter( Boolean );
			const at = names.indexOf( name );
			if( force === true && at === -1 )
				names.push( name );
			if( force === false && at !== -1 )
				names.splice( at, 1 );
			el.className = names.join(' ');
		},
	};
	return el;
}

/** Every element below (and including) a root that the predicate accepts */
function all( root, predicate ) {
	const found = predicate( root ) === true ? [ root ] : [];
	root.children.forEach( function( child ) { all( child, predicate ).forEach( function( el ) { found.push( el ) } ) } );
	return found;
}

/** Fire a listener the way the browser would */
function fire( el, type ) {
	( el.listeners[ type ] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {} } ) } );
}

const mount = element('div');
mount.id = 'protected-form';

// What the server was asked, and what the next answer is
const asked = [];
let answer = function() { return [ 200, {} ] };
let confirmed = [];
let confirmAnswer = true;

const nino = {
	admin		: {},
	adminUi	: {
		emptyState	: function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
		actionBar		: function( bar ) { bar.className = ( bar.className+ ' nino-admin-actionbar' ).trim(); return bar },
	},
	// The key with the placeholder it would carry, so what is filled in shows
	content	: { getText : function( key ) { return key+ ' %s' } },
	events	: { bindCallback : function() {} },
	http		: { sendRequest : function( url, method, callback, payload ) {
		asked.push( { action : payload.action, data : JSON.parse( payload.data ) } );
		const result = answer( payload.action );
		callback( { status : result[0], responseJSON : result[1] } );
	} },
};

function find( id ) {
	return all( mount, function( el ) { return el.id === id } )[0] || null;
}

const sandbox = {
	console		: console,
	confirm		: function( text ) { confirmed.push( text ); return confirmAnswer },
	document	: { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return id === 'protected-form' ? mount : find( id ) } },
	Nino			: nino,
};
sandbox.window = sandbox;
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const panel = nino.admin.protected;
check( 'the panel script boots under the namespace its nav uri names', typeof panel === 'object' && typeof panel.init === 'function' && typeof panel.showCurrent === 'function' );

// --- The pure parts ------------------------------------------------------------

check( '_rowText: a title is the name and the paths stand beside it', panel._rowText( { uri : '/about', title : 'About', paths : [ '/about', '/ueber-uns' ] } ).name === 'About'
	&& panel._rowText( { uri : '/about', title : 'About', paths : [ '/about', '/ueber-uns' ] } ).state === '/about, /ueber-uns' );
check( '_rowText: without a title the first path is the name and only the other variants stand beside it', panel._rowText( { uri : '/blog', title : '', paths : [ '/blog' ] } ).name === '/blog'
	&& panel._rowText( { uri : '/blog', title : '', paths : [ '/blog' ] } ).state === ''
	&& panel._rowText( { uri : '/a', title : '', paths : [ '/a', '/b' ] } ).state === '/b' );
check( '_rowText: a page covered by a wider path says so', panel._rowText( { uri : '/n', title : 'N', paths : [ '/intern/n' ], covered : true, selected : false } ).state.indexOf('/_admin/protected/label/covered') !== -1 );

const pages = [
	{ uri : '/about', title : 'About', paths : [ '/about', '/ueber-uns' ], selected : false, covered : false },
	{ uri : '/intern', title : 'Intern', paths : [ '/intern' ], selected : true, covered : false },
	{ uri : '/intern/notes', title : '', paths : [ '/intern/notes' ], selected : false, covered : true },
	{ uri : '/blog', title : '', paths : [ '/blog' ], selected : false, covered : false },
];

check( '_collect: every address of every ticked page, in list order', JSON.stringify( panel._collect( pages, { '/about' : true, '/intern' : true, '/intern/notes' : false, '/blog' : false } ) ) === '["/about","/ueber-uns","/intern"]' );
check( '_collect: nothing ticked is an empty list', JSON.stringify( panel._collect( pages, {} ) ) === '[]' );

check( '_pwProblem: shorter than the minimum, two different entries, and a good one',
	panel._pwProblem( '1234567', '1234567', 8 ) === 'short' && panel._pwProblem( '12345678', '12345679', 8 ) === 'mismatch' && panel._pwProblem( '12345678', '12345678', 8 ) === '' );

// --- The screen -------------------------------------------------------------------

function state( overrides ) {
	return Object.assign( { hasPassword : true, pages : pages, extra : [ '/preview/client-a' ], minLength : 8, maxLength : 200 }, overrides || {} );
}

answer = function( action ) { return action === 'protected/state' ? [ 200, state() ] : [ 200, {} ] };
panel.showCurrent();

check( 'opening the panel asks for the state, once', asked.length === 1 && asked[0].action === 'protected/state' );
panel.showCurrent();
check( '...and opening it again keeps what is on screen instead of asking again', asked.length === 1 );

const sections = mount.children.filter( function( el ) { return el.tagName === 'FIELDSET' } );
check( 'the screen is the three sections - password, pages, sign-out - each a fieldset with a legend', sections.length === 3
	&& sections.every( function( box ) { return box.children[0].tagName === 'LEGEND' } ) );

const rows = all( mount, function( el ) { return el.tagName === 'LABEL' && el.className === '' } );
check( 'a row for every page of the list', rows.length === 4 );

const boxes = rows.map( function( row ) { return row.children[0] } );
check( 'the protected page is ticked and can be unticked', boxes[1].checked === true && boxes[1].disabled === false );
check( 'a page covered by a wider path shows ticked and cannot be changed', boxes[2].checked === true && boxes[2].disabled === true );
check( 'the others are open and can be ticked', boxes[0].checked === false && boxes[0].disabled === false && boxes[3].checked === false );
check( 'a title is the row\'s name, its paths sit beside it', rows[0].children[1].textContent === 'About' && rows[0].children[2].textContent === '/about, /ueber-uns' );
check( 'without a title the row is named by its path', rows[3].children[1].textContent === '/blog' );

const extra = all( mount, function( el ) { return el.className === 'nino-admin-hint' && el.textContent.indexOf('/preview/client-a') !== -1 } );
check( 'what only the Features panel can name is said, as text', extra.length === 1 && extra[0].textContent.indexOf('/_admin/protected/label/extra') === 0 );

// --- Saving the pages ---------------------------------------------------------------

fire( boxes[0], 'change' );
boxes[0].checked = true;
fire( boxes[0], 'change' );
asked.length = 0;
answer = function( action ) {
	return action === 'protected/pages'
		? [ 200, state( { pages : pages.map( function( page ) { return Object.assign( {}, page, { selected : page.uri === '/about' || page.uri === '/intern' } ) } ) } ) ]
		: [ 200, {} ];
};
fire( find('protected-savepages'), 'click' );
check( 'saving posts every address of every ticked page', asked.length === 1 && asked[0].action === 'protected/pages'
	&& JSON.stringify( asked[0].data.paths ) === '["/about","/ueber-uns","/intern"]' );
check( '...a covered page is not posted', asked[0].data.paths.indexOf('/intern/notes') === -1 );
check( '...with nothing to confirm while something is chosen', confirmed.length === 0 );
check( '...and the screen is drawn from the answer, with the note', find('protected-pages-msg').textContent.indexOf('/_admin/protected/msg/pagessaved') === 0 );

// An empty choice
const rowsNow = all( mount, function( el ) { return el.tagName === 'LABEL' && el.className === '' } );
rowsNow[0].children[0].checked = false;
fire( rowsNow[0].children[0], 'change' );
rowsNow[1].children[0].checked = false;
fire( rowsNow[1].children[0], 'change' );

asked.length = 0;
confirmAnswer = false;
fire( find('protected-savepages'), 'click' );
check( 'an empty choice, where something was protected, is asked about first', confirmed.length === 1 && confirmed[0].indexOf('/_admin/protected/confirm/none') === 0 );
check( '...and nothing is sent when that is declined', asked.length === 0 );

confirmAnswer = true;
fire( find('protected-savepages'), 'click' );
check( '...and an empty list is sent when it is accepted', asked.length === 1 && asked[0].action === 'protected/pages' && JSON.stringify( asked[0].data.paths ) === '[]' );

// Nothing protected, nothing chosen: nothing to ask
answer = function() { return [ 200, state( { pages : pages.map( function( page ) { return Object.assign( {}, page, { selected : false, covered : false } ) } ), extra : [] } ) ] };
panel._ready = false;
panel.showCurrent();
confirmed = [];
asked.length = 0;
fire( find('protected-savepages'), 'click' );
check( 'an empty choice where nothing was protected is not worth a question', confirmed.length === 0 && asked.length === 1 );
check( 'with nothing set by hand there is no note about it', all( mount, function( el ) { return el.textContent.indexOf('/_admin/protected/label/extra') !== -1 } ).length === 0 );

// A failed save says why, and keeps the screen
answer = function() { return [ 400, { error : 'unknown page' } ] };
fire( find('protected-savepages'), 'click' );
check( 'a refused save is said on the line under the list, as an error', find('protected-pages-msg').textContent === '(400) unknown page'
	&& find('protected-pages-msg').className.indexOf('nino-admin-error') !== -1 );

// --- The password ---------------------------------------------------------------------

const first = find('protected-newpw');
const second = find('protected-newpw2');
check( 'two password fields, which a password manager reads as a new password, bound to the length the server named',
	first.type === 'password' && second.type === 'password' && first.autocomplete === 'new-password' && first.minLength === 8 && first.maxLength === 200 );

function submit() { all( mount, function( el ) { return el.tagName === 'FORM' } ).forEach( function( f ) { fire( f, 'submit' ) } ) }

asked.length = 0;
first.value = 'short';
second.value = 'short';
submit();
check( 'a password that is too short is refused before anything is sent', asked.length === 0 && find('protected-pw-msg').textContent.indexOf('/_admin/protected/error/short') === 0 );

first.value = 'long-enough-1';
second.value = 'long-enough-2';
submit();
check( 'two entries that differ are refused before anything is sent', asked.length === 0 && find('protected-pw-msg').textContent.indexOf('/_admin/protected/error/mismatch') === 0 );

second.value = 'long-enough-1';
answer = function() { return [ 200, state( { hasPassword : true, extra : [] } ) ] };
submit();
check( 'two equal entries are sent once, as "pw"', asked.length === 1 && asked[0].action === 'protected/password' && JSON.stringify( asked[0].data ) === '{"pw":"long-enough-1"}' );
check( '...the screen is drawn again, with the fields empty and the note', find('protected-newpw').value === '' && find('protected-newpw2').value === ''
	&& find('protected-pw-msg').textContent.indexOf('/_admin/protected/msg/pwsaved') === 0 );
check( '...and the password is nowhere in what is on screen', all( mount, function( el ) { return el.textContent.indexOf('long-enough-1') !== -1 || el.value === 'long-enough-1' } ).length === 0 );

answer = function() { return [ 403, { error : 'not allowed' } ] };
find('protected-newpw').value = 'long-enough-3';
find('protected-newpw2').value = 'long-enough-3';
submit();
check( 'a refused password is said as an error', find('protected-pw-msg').textContent === '(403) not allowed' );

// --- Signing out --------------------------------------------------------------------------

asked.length = 0;
confirmed = [];
confirmAnswer = false;
fire( find('protected-signout'), 'click' );
check( 'signing everybody out is asked about first, and declined means nothing is sent', confirmed.length === 1 && confirmed[0].indexOf('/_admin/protected/confirm/signout') === 0 && asked.length === 0 );

confirmAnswer = true;
answer = function() { return [ 200, { signedOut : true } ] };
fire( find('protected-signout'), 'click' );
check( 'accepted, it posts the action and says it is done', asked.length === 1 && asked[0].action === 'protected/signout'
	&& find('protected-signout-msg').textContent.indexOf('/_admin/protected/msg/signedout') === 0 );

// --- The edges ---------------------------------------------------------------------------------

answer = function() { return [ 200, state( { pages : [], extra : [] } ) ] };
panel._ready = false;
panel.showCurrent();
const empty = all( mount, function( el ) { return el.className === 'nino-admin-empty' } );
check( 'a site with no pages says so instead of drawing an empty list', empty.length === 1 && empty[0].textContent.indexOf('/_admin/protected/empty/pages') === 0
	&& find('protected-savepages') === null );

answer = function() { return [ 403, { error : 'not allowed' } ] };
panel._ready = false;
panel.showCurrent();
check( 'a refused state is shown where the screen would be', mount.children.length === 1 && mount.children[0].className === 'nino-admin-error' && mount.children[0].textContent === '(403) not allowed' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
