/**
 *	Nino
 *	newsletter-js-smoke.js	What the Newsletter panel's script draws from the list
 *													the server answers: the count of confirmed addresses
 *													with the pending ones named beside it, the BCC line of
 *													the confirmed addresses only, the status of every row
 *													in words, a filter that sets the rows of the table
 *													that is there, and an export that writes the rows the
 *													filter shows.
 *
 *													No jsdom, no dependency: a dom stand-in with just
 *													enough of Nino.adminUi for the panel to draw itself,
 *													so this runs with nothing but node. newsletter-smoke.php
 *													runs it through node when node is on the path, so
 *													bin/check.sh and CI cover it; it also runs on its own.
 *
 *	Usage: node features/Newsletter/tests/newsletter-js-smoke.js
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
		classList		: { add : function() {}, remove : function() {} },
		listeners		: {},
		setAttribute: function() {},
		appendChild	: function( child ) { this.children.push( child ); return child },
		select			: function() {},
		addEventListener : function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		click				: function() { ( this.listeners['click'] || [] ).forEach( function( fn ) { fn() } ) },
	};

	// Assigning '' empties the element, the way it does in a browser
	let html = '';
	Object.defineProperty( el, 'innerHTML', { get : function() { return html }, set : function( value ) { html = value; el.children = [] } } );

	return el;
}

const texts = {
	'/_admin/newsletter/label/subscriber' 	: 'subscriber',
	'/_admin/newsletter/label/subscribers' 	: 'subscribers',
	'/_admin/newsletter/label/pending' 			: 'pending',
	'/_admin/newsletter/status/subscribed' 	: 'Confirmed',
	'/_admin/newsletter/status/pending' 		: 'Pending',
	'/_admin/newsletter/hint/unsubscribe' 	: 'Put it into every BCC mail: %s',
	'/_admin/newsletter/label/filename' 		: 'newsletter.csv',
};

const tables = [];
const fields = [];
const exported = [];
const list = element('div');
list.id = 'newsletter-list';

/*	The namespace the panel script extends - a local stand-in, not the global,
	so the file lints like a browser script with the rest of them	*/
const nino = {
	admin		: { exportCsv : function( name, rows ) { exported.push( { name : name, rows : rows } ) } },
	adminUi	: {
		emptyState	: function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
		table				: function( options ) {
			const table = { options : options, rows : options.rows, setRows : function( next ) { this.rows = next } };
			tables.push( table );
			return table;
		},
		selectField	: function( options ) { const field = element('label'); field.options = options; fields.push( field ); return field },
	},
	content	: { getText : function( key ) { return Object.prototype.hasOwnProperty.call( texts, key ) ? texts[key] : key } },
	events	: { bindCallback : function() {} },
	http		: { sendRequest : function() { throw new Error( 'drawing the list asks the server for nothing' ) } },
};
const sandbox = {
	console : console, setTimeout : setTimeout, Nino : nino,
	document : { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return id === 'newsletter-list' ? list : null }, execCommand : function() { return true } },
};
sandbox.window = sandbox;
sandbox.window.navigator = {};
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const panel = nino.admin.newsletter;

const entries = [
	{ email : 'newest@example.com', status : 'pending', date : '2026-09-03 10:00:00', ip : '203.0.113.1' },
	{ email : 'anna@example.com', status : 'subscribed', date : '2026-09-02 10:00:00', ip : '203.0.113.2' },
	{ email : 'old@example.com', date : '2025-01-02 10:00:00', ip : '203.0.113.3' },
	{ email : 'bert@example.com', status : 'pending', date : '2026-09-01 10:00:00', ip : '203.0.113.4' },
];
const emails = function( rows ) { return rows.map( function( row ) { return row.email } ).join(',') };

// --- The pure helpers ---------------------------------------------------------

check( 'the panel script boots and offers the two helpers', typeof panel._rows === 'function' && typeof panel._bccLine === 'function' );
check( 'the rows of "all" are every entry', emails( panel._rows( entries, 'all' ) ) === 'newest@example.com,anna@example.com,old@example.com,bert@example.com' );
check( '...and so are the rows of anything that is not a status', emails( panel._rows( entries, '' ) ) === emails( entries ) );
check( 'the rows of "pending" are the unconfirmed ones', emails( panel._rows( entries, 'pending' ) ) === 'newest@example.com,bert@example.com' );
check( 'the rows of "subscribed" are the confirmed ones - an entry with no status is one of them', emails( panel._rows( entries, 'subscribed' ) ) === 'anna@example.com,old@example.com' );
check( 'the BCC line holds the confirmed addresses only, comma-separated', panel._bccLine( entries ) === 'anna@example.com, old@example.com' );
check( '...and is empty where nothing is confirmed', panel._bccLine( [ entries[0], entries[3] ] ) === '' );

// --- The screen --------------------------------------------------------------------

panel._renderList( { entries : entries, counts : { subscribed : 2, pending : 2 }, unsubscribeUrl : 'https://example.org/.newsletter/unsubscribe' } );
const drawn = descendants( list );
const byId = function( id ) { return drawn.filter( function( el ) { return el.id === id } )[0] };

check( 'the summary counts the confirmed addresses and names the pending ones beside them', byId('newsletter-summary').textContent === '2 subscribers · 2 pending' );
check( 'the BCC field holds the confirmed addresses only', byId('newsletter-bcc-field').value === 'anna@example.com, old@example.com' );
check( 'under it, the page where a subscriber asks for a link, in the hint', byId('newsletter-bcc-hint').textContent === 'Put it into every BCC mail: https://example.org/.newsletter/unsubscribe' );

const table = tables[0];
check( 'the table is given every entry to begin with', table.options.rows === entries && table.rows === entries );
const statusColumn = table.options.columns.filter( function( column ) { return column.key === 'status' } )[0];
check( 'it has a status column, sorted and searched on the slug', statusColumn !== undefined && statusColumn.type === 'string' );
check( '...whose cell says it in words', statusColumn.render( 'pending' ) === 'Pending' && statusColumn.render( 'subscribed' ) === 'Confirmed' );

const filter = fields[0].options;
check( 'the filter offers all, confirmed and pending, and starts on all', filter.value === 'all' && filter.options.map( function( o ) { return o.value } ).join(',') === 'all,subscribed,pending' );
check( '...in words', filter.options.map( function( o ) { return o.label } ).join(',') === '/_admin/newsletter/filter/all,Confirmed,Pending' );

byId('newsletter-export').click();
check( 'the export writes every row while the filter says all', exported.length === 1 && exported[0].name === 'newsletter.csv' && emails( exported[0].rows ) === emails( entries ) );

filter.onChange( 'pending' );
check( 'choosing pending sets the rows of the table that is there, instead of drawing another', tables.length === 1 && emails( table.rows ) === 'newest@example.com,bert@example.com' );
byId('newsletter-export').click();
check( 'the export follows the filter', emails( exported[1].rows ) === 'newest@example.com,bert@example.com' );
check( '...while the BCC line keeps to the confirmed addresses whatever the filter shows', byId('newsletter-bcc-field').value === 'anna@example.com, old@example.com' );

filter.onChange( 'subscribed' );
byId('newsletter-export').click();
check( 'choosing confirmed shows and exports the confirmed ones', emails( table.rows ) === 'anna@example.com,old@example.com' && emails( exported[2].rows ) === 'anna@example.com,old@example.com' );

filter.onChange( 'all' );
check( 'and all brings every row back', emails( table.rows ) === emails( entries ) );

// --- The edges -----------------------------------------------------------------------

list.innerHTML = '';
panel._renderList( { entries : [ entries[1] ], counts : { subscribed : 1, pending : 0 }, unsubscribeUrl : '' } );
const single = descendants( list );
const singleById = function( id ) { return single.filter( function( el ) { return el.id === id } )[0] };
check( 'one confirmed address is "1 subscriber", with nothing said about pending', singleById('newsletter-summary').textContent === '1 subscriber' );
check( 'with no address to ask at there is no hint line', singleById('newsletter-bcc-hint') === undefined );

list.innerHTML = '';
panel._renderList( { entries : [], counts : { subscribed : 0, pending : 0 }, unsubscribeUrl : 'https://example.org/.newsletter/unsubscribe' } );
check( 'an empty list says so instead of drawing a table', list.children.length === 1 && list.children[0].className === 'nino-admin-empty' );

// A delete draws the list again on the filter that was on screen
list.innerHTML = '';
tables.length = 0;
fields.length = 0;
panel._renderList( { entries : entries, counts : { subscribed : 2, pending : 2 }, unsubscribeUrl : '' }, 'pending' );
check( 'a list drawn on a chosen filter starts with that filter selected and its rows', fields[0].options.value === 'pending' && emails( tables[0].options.rows ) === 'newest@example.com,bert@example.com' );
list.innerHTML = '';
tables.length = 0;
fields.length = 0;
panel._renderList( { entries : entries, counts : { subscribed : 2, pending : 2 }, unsubscribeUrl : '' }, 'nonsense' );
check( '...and anything that is not a status starts on all', fields[0].options.value === 'all' && tables[0].options.rows === entries );

// --- Asking the workbench ------------------------------------------------------------

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const wasPosted = [];
const keepSend = nino.http.sendRequest;
nino.http.sendRequest = function( uri, method, callback, data ) { wasPosted.push( [ uri, method, data ] ); callback( { status : 200, responseJSON : { ok : true } } ) };
let gotAnswer = null;
nino.admin.newsletter._apiCall( 'list', { a : 1 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	wasPosted.length === 1 && wasPosted[0][0] === '[[/nino/dir]]/_admin/' && wasPosted[0][1] === 'POST'
	&& wasPosted[0][2].action === 'newsletter/list' && wasPosted[0][2].data === '{"a":1}' && JSON.stringify( gotAnswer ) === '[200,{"ok":true}]' );

const wasRouted = [];
nino.adminUi.api = { call : function( action, payload, callback ) { wasRouted.push( [ action, payload ] ); callback( 200, { via : 'api' } ) } };
gotAnswer = null;
nino.admin.newsletter._apiCall( 'list', { b : 2 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'with it the action \'newsletter/<action>\' and the payload go to the helper, and nothing is posted by hand',
	wasRouted.length === 1 && wasRouted[0][0] === 'newsletter/list' && wasRouted[0][1].b === 2 && wasPosted.length === 1 && gotAnswer[1].via === 'api' );

check( 'a failed request says "(status) message" as it always did where the shell has no errorText()',
	nino.admin.newsletter._errorText( 503, { error : 'Busy' }, '/_admin/newsletter/error/load' ) === '(503) Busy'
	&& nino.admin.newsletter._errorText( 503, null, '/_admin/newsletter/error/load' ) === '(503) /_admin/newsletter/error/load' );
nino.adminUi.api.errorText = function( status, response, key ) { return 'told '+ status+ ' '+ response.error+ ' ['+ key+ ']' };
check( '...and what errorText() makes of it where it has one', nino.admin.newsletter._errorText( 503, { error : 'Busy' }, '/_admin/newsletter/error/load' ) === 'told 503 Busy [/_admin/newsletter/error/load]' );
delete nino.adminUi.api;
nino.http.sendRequest = keepSend;

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
