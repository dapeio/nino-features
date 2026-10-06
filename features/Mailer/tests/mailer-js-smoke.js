/**
 *	Nino
 *	mailer-js-smoke.js	What the Mailer panel's script does with the status the
 *											server answers: the status line with the port a send
 *											really uses, the address field filled with the one the
 *											server offers - and never over what somebody typed - and
 *											the last errors as a list of plain text, or the line that
 *											says there are none.
 *
 *											No jsdom, no dependency: a dom stand-in with just enough
 *											for the panel to draw itself, so this runs with nothing
 *											but node. mailer-smoke.php runs it through node when node
 *											is on the path, so bin/check.sh and CI cover it; it also
 *											runs on its own.
 *
 *	Usage: node features/Mailer/tests/mailer-js-smoke.js
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

/**
 *	One element, with just enough of the interface admin.js reaches for
 */
function element( tag ) {

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		className		: '',
		id					: '',
		textContent	: '',
		value				: '',
		listeners		: {},
		setAttribute: function() {},
		appendChild	: function( child ) { this.children.push( child ); return child },
		addEventListener : function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
	};

	let html = '';
	Object.defineProperty( el, 'innerHTML', { get : function() { return html }, set : function( value ) { html = value; el.children = [] } } );

	return el;
}

const texts = {
	'/_admin/mailer/label/status' 			: 'Sending through %host:%port (%encryption).',
	'/_admin/mailer/label/unconfigured' : 'Not configured yet',
	'/_admin/mailer/label/errors' 			: 'Last errors',
	'/_admin/mailer/hint/errors-empty' 	: 'No failed sends recorded.',
};

const wrap = element('div');
wrap.id = 'mailer-form';
const requests = [];
let answer = { host : 'smtp.example.com', port : 465, encryption : 'tls', testTo : 'me@example.org', errors : [] };

/*	The namespace the panel script extends - a local stand-in, not the global,
	so the file lints like a browser script with the rest of them	*/
const nino = {
	admin		: {},
	adminUi	: { emptyState : function( message ) { const empty = element('p'); empty.className = 'nino-admin-empty'; empty.textContent = message; return empty } },
	content	: { getText : function( key ) { return Object.prototype.hasOwnProperty.call( texts, key ) ? texts[key] : key } },
	events	: { bindCallback : function() {} },
	http		: { sendRequest : function( url, method, callback, data ) {
		requests.push( data.action );
		callback( { status : 200, responseJSON : answer } );
	} },
};
const sandbox = { console : console, Nino : nino, document : { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return id === 'mailer-form' ? wrap : null } } };
sandbox.window = sandbox;
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const mailer = nino.admin.mailer;
const find = function( id ) { return descendants( wrap ).filter( function( el ) { return el.id === id } )[0] };

// --- A fresh screen -------------------------------------------------------------

mailer.init();
check( 'the screen asks the server for its status once', requests.join(',') === 'mailer/status' );
check( 'the status line names the port a send really uses', find('mailer-status').textContent === 'Sending through smtp.example.com:465 (tls).' );
check( 'the address field holds the one the server offered', find('mailer-to').value === 'me@example.org' );
check( 'no errors is the empty state component',  descendants( find('mailer-errors') ).some( function( el ) { return el.className === 'nino-admin-empty' } ) );
check( 'no errors says so', find('mailer-errors').children.map( function( el ) { return el.textContent } ).join('|') === 'Last errors|No failed sends recorded.' );

// --- Errors, as text ---------------------------------------------------------------

answer = { host : 'smtp.example.com', port : 587, encryption : 'starttls', testTo : 'other@example.org', errors : [
	{ date : '2026-10-02 10:00:00', reason : 'could not connect to smtp.example.com:587' },
	{ date : '2026-10-01 09:00:00', reason : '<img src=x onerror=alert(1)>' },
] };
find('mailer-to').value = 'typed@example.org';
mailer._loadStatus( find('mailer-status'), find('mailer-to'), find('mailer-errors') );
const items = descendants( find('mailer-errors') ).filter( function( el ) { return el.tagName === 'LI' } );
check( 'a failure is listed with its date, newest first', items.length === 2 && items[0].textContent === '2026-10-02 10:00:00 - could not connect to smtp.example.com:587' );
check( 'the list is a dense list of rows', descendants( find('mailer-errors') ).some( function( el ) { return el.tagName === 'UL' && el.className === 'nino-admin-list nino-admin-list-dense' } ) );
check( 'a reason is text, never markup', items[1].textContent === '2026-10-01 09:00:00 - <img src=x onerror=alert(1)>' && items[1].children.length === 0 );
check( 'the list replaces what was there instead of adding to it', descendants( find('mailer-errors') ).filter( function( el ) { return el.className === 'nino-admin-eyebrow' } ).length === 1 );
check( 'an address somebody typed is not written over', find('mailer-to').value === 'typed@example.org' );
check( 'the status line follows the new answer', find('mailer-status').textContent === 'Sending through smtp.example.com:587 (starttls).' );

// --- Nothing configured -------------------------------------------------------------

answer = { host : '', port : 587, encryption : 'starttls', testTo : '', errors : [] };
mailer._loadStatus( find('mailer-status'), find('mailer-to'), find('mailer-errors') );
check( 'with no host the status line says it is not configured', find('mailer-status').textContent === 'Not configured yet' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
