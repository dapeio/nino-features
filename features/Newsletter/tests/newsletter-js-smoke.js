/**
 *	Nino
 *	newsletter-js-smoke.js	What the Newsletter panel's script does with the list
 *													the server answers: the count of confirmed addresses
 *													with the pending ones named beside it, the BCC line of
 *													the confirmed addresses only, the status of every row
 *													in words, a filter that sets the rows of the table
 *													that is there, an export that writes the rows the
 *													filter shows, a delete that draws the list again on the
 *													filter it was on, and a copy of the BCC line through
 *													the kernel's own Nino.clipboard().
 *
 *													No jsdom, no dependency: a dom stand-in with just
 *													enough for the panel to draw itself, so this runs with
 *													nothing but node. The kernel's _nino/Nino.js runs in
 *													it as it is - from NINO_ROOT, or three levels up when
 *													the feature sits in a checkout - and the workbench
 *													(Nino.adminUi, Nino.admin, Nino.content) is a stand-in
 *													laid over it, kept to what docs/features.md says of
 *													it. newsletter-smoke.php runs this through node when
 *													node is on the path, so bin/check.sh and CI cover it;
 *													it also runs on its own.
 *
 *	Usage: node features/Newsletter/tests/newsletter-js-smoke.js
 *	       NINO_ROOT=../nino node features/Newsletter/tests/newsletter-js-smoke.js
 */
'use strict';
/* global require, __dirname, process, setImmediate, Buffer */
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

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
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
 *	One element, with just enough of the interface admin.js and the kernel's
 *	Nino.clipboard() reach for
 */
function element( tag ) {

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		parent			: null,
		className		: '',
		id					: '',
		textContent	: '',
		value				: '',
		style				: {},
		classes			: [],
		classList		: { add : function( name ) { el.classes.includes( name ) || el.classes.push( name ) }, remove : function( name ) { el.classes = el.classes.filter( function( c ) { return c !== name } ) } },
		listeners		: {},
		setAttribute: function() {},
		appendChild	: function( child ) { child.parent = this; this.children.push( child ); return child },
		remove			: function() { if( el.parent !== null ) el.parent.children = el.parent.children.filter( function( c ) { return c !== el } ) },
		select			: function() { selected.push( el ) },
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
	'/_admin/newsletter/confirm/delete' 		: '%s — really remove this address?',
	'/_admin/newsletter/label/filename' 		: 'newsletter.csv',
};

const tables = [];
const fields = [];
const exported = [];
const requests = [];
const timers = [];
const selected = [];
const confirms = [];
const answers = {};
let confirmed = true;
let copyApi = null;
let copyCommand = true;

const body = element('body');
body.scrollLeft = 0;
body.scrollTop = 0;
const list = element('div');
list.id = 'newsletter-list';
const page = element('div');
page.appendChild( list );

/** The element of that id anywhere under the page, as getElementById() finds it */
function find( node, id ) {

	if( node.id === id )
		return node;

	for( const child of node.children ) {
		const hit = find( child, id );
		if( hit !== null )
			return hit;
	}

	return null;
}

/*	The page the kernel's Nino.js loads into - the same as its own test builds
	(tests/nino-core-js-smoke.js): what it asks of the browser at load, and
	the clipboard and the copy command, which this test lets answer as it
	wants. The timers are kept, not run, so the two seconds the line "Copied!"
	stays are not waited for	*/
const sandbox = {
	console : console,
	document : {
		body : body,
		documentElement : { clientHeight : 640, clientWidth : 1024, scrollLeft : 0, scrollTop : 0, style : {}, classList : { add : function() {}, remove : function() {} } },
		cookie : '',
		readyState : 'complete',
		getElementById : function( id ) { return find( page, id ) },
		querySelector : function() { return null },
		querySelectorAll : function() { return [] },
		createElement : function( tag ) { return element( tag ) },
		addEventListener : function() {},
		execCommand : function() { return copyCommand },
	},
	location : { pathname : '/', origin : 'https://example.com', href : 'https://example.com/' },
	XMLHttpRequest : function() {},
	FormData : function() {},
	TextEncoder : TextEncoder,
	btoa : function( binary ) { return Buffer.from( binary, 'latin1' ).toString('base64') },
	atob : function( b64 ) { return Buffer.from( b64, 'base64' ).toString('latin1') },
	setTimeout : function( fn, delay ) { timers.push( { fn : fn, delay : delay } ); return timers.length },
	clearTimeout : function() {},
	Event : function( type ) { this.type = type },
	dispatchEvent : function() {},
	addEventListener : function() {},
	requestAnimationFrame : function() { return 0 },
	matchMedia : function() { return { matches : false, addEventListener : function() {} } },
	confirm : function( text ) { confirms.push( text ); return confirmed },
};
sandbox.window = sandbox;
sandbox.self = sandbox;
Object.defineProperty( sandbox, 'navigator', { get : function() { return { userAgent : 'node', language : 'en-US', clipboard : copyApi } } } );

const context = vm.createContext( sandbox );
vm.runInContext( fs.readFileSync( path.join( root, '_nino/Nino.js' ), 'utf8' ), context, { filename : 'Nino.js' } );

/*	The workbench is a stand-in, kept to what the manual says of the members the
	script calls: api.call() keeps its requests and answers from the table above;
	panel() is the lifecycle the manual describes - the screen is asked for when
	the panel is shown, drawn by render() from the answer, and a failure is said
	in the mount with the panel's key; reload() asks again	*/
const nino = sandbox.Nino;

nino.admin = { exportCsv : function( name, rows ) { exported.push( { name : name, rows : rows } ) } };
nino.adminUi = {
	emptyState	: function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
	table				: function( options ) {
		const table = { options : options, rows : options.rows, setRows : function( next ) { this.rows = next } };
		tables.push( table );
		return table;
	},
	selectField	: function( options ) { const field = element('label'); field.options = options; fields.push( field ); return field },
	el					: function( tag, className, text ) { const el = element( tag ); if( className ) el.className = className; if( text !== undefined ) el.textContent = text; return el },
	hint				: function( text ) { const p = element('p'); p.className = 'nino-admin-hint'; p.textContent = text; return p },
	button			: function( text, onClick, options ) {
		const button = element('button');
		button.className = ( options && options.className ) || '';
		button.id = ( options && options.id ) || '';
		button.textContent = text;
		button.addEventListener( 'click', onClick );
		return button;
	},
	// The placeholders %s %d %n, filled in order
	format			: function( text, ...params ) { let at = 0; return String( text ).replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } ) },
	api					: { call : function( action, payload, callback ) {
		requests.push( { action : action, payload : payload } );
		const answer = answers[action]( payload );
		callback( answer[0], answer[1] );
	} },
	showError		: function( container, status, response, key ) {
		container.innerHTML = '';
		const p = element('p');
		p.className = 'nino-admin-error';
		p.textContent = 'told '+ status+ ' '+ ( response.code || response.error )+ ' ['+ key+ ']';
		container.appendChild( p );
		return p;
	},
	panel				: function( spec ) {
		const self = {
			uri			: spec.uri,
			ready		: false,
			loading	: false,
			data		: null,
			mount		: function() { return sandbox.document.getElementById( spec.mount ) },
			call		: function( action, payload, done ) { nino.adminUi.api.call( spec.uri+ '/'+ action, payload, done ) },
			fail		: function( container, status, response, key ) { return nino.adminUi.showError( container, status, response, key || '/_admin/'+ spec.uri+ '/error/load' ) },
			init		: function() {
				if( self.loading === true || self.mount() === null )
					return;
				self.loading = true;
				self.call( spec.action || 'list', spec.payload || {}, function( status, response ) {
					self.loading = false;
					if( status === 200 ) {
						self.data = response;
						self.ready = true;
						spec.render( response, self );
						return;
					}
					self.fail( self.mount(), status, response );
					self.ready = false;
				} );
			},
			reload			: function() { self.init() },
			showCurrent	: function() {
				if( self.ready === false || spec.show === 'always' )
					self.init();
			},
		};
		return self;
	},
};
// The key itself, except where the test has a word for it
nino.content = { getText : function( key ) { return Object.prototype.hasOwnProperty.call( texts, key ) ? texts[key] : key } };

vm.runInContext( source, context, { filename : 'admin.js' } );

const panel = nino.admin.newsletter;

const entries = [
	{ email : 'newest@example.com', status : 'pending', date : '2026-09-03 10:00:00', ip : '203.0.113.1' },
	{ email : 'anna@example.com', status : 'subscribed', date : '2026-09-02 10:00:00', ip : '203.0.113.2' },
	{ email : 'old@example.com', date : '2025-01-02 10:00:00', ip : '203.0.113.3' },
	{ email : 'bert@example.com', status : 'pending', date : '2026-09-01 10:00:00', ip : '203.0.113.4' },
];
const emails = function( rows ) { return rows.map( function( row ) { return row.email } ).join(',') };
const reply = function( rows, unsubscribeUrl ) {
	return [ 200, { entries : rows, counts : { subscribed : rows.filter( function( e ) { return e.status !== 'pending' } ).length, pending : rows.filter( function( e ) { return e.status === 'pending' } ).length }, unsubscribeUrl : unsubscribeUrl ?? '' } ];
};
/** What the panel drew since the list was emptied */
const drawnIds = function() { return descendants( list ) };
const byId = function( id ) { return drawnIds().filter( function( el ) { return el.id === id } )[0] };
/** Let what is queued run: the clipboard answers through promises */
const settle = function() { return new Promise( function( resolve ) { setImmediate( resolve ) } ) };

// --- The pure helpers ---------------------------------------------------------

check( 'the panel script boots and offers the two helpers', typeof panel._rows === 'function' && typeof panel._bccLine === 'function' );
check( 'the rows of "all" are every entry', emails( panel._rows( entries, 'all' ) ) === 'newest@example.com,anna@example.com,old@example.com,bert@example.com' );
check( '...and so are the rows of anything that is not a status', emails( panel._rows( entries, '' ) ) === emails( entries ) );
check( 'the rows of "pending" are the unconfirmed ones', emails( panel._rows( entries, 'pending' ) ) === 'newest@example.com,bert@example.com' );
check( 'the rows of "subscribed" are the confirmed ones - an entry with no status is one of them', emails( panel._rows( entries, 'subscribed' ) ) === 'anna@example.com,old@example.com' );
check( 'the BCC line holds the confirmed addresses only, comma-separated', panel._bccLine( entries ) === 'anna@example.com, old@example.com' );
check( '...and is empty where nothing is confirmed', panel._bccLine( [ entries[0], entries[3] ] ) === '' );

// --- The screen --------------------------------------------------------------------

check( 'nothing is asked before the panel is shown', requests.length === 0 );
answers['newsletter/list'] = function() { return reply( entries, 'https://example.org/.newsletter/unsubscribe' ) };
panel.showCurrent();
check( 'showing the panel asks the server for the list', requests.map( function( r ) { return r.action } ).join() === 'newsletter/list' && JSON.stringify( requests[0].payload ) === '{}' );

check( 'the summary counts the confirmed addresses and names the pending ones beside them', byId('newsletter-summary').textContent === '2 subscribers · 2 pending' );
check( 'the BCC field holds the confirmed addresses only', byId('newsletter-bcc-field').value === 'anna@example.com, old@example.com' );
check( 'under it, the page where a subscriber asks for a link, in the hint', byId('newsletter-bcc-hint').textContent === 'Put it into every BCC mail: https://example.org/.newsletter/unsubscribe'
	&& byId('newsletter-bcc-hint').className === 'nino-admin-hint' );

const table = tables[0];
check( 'the table is given every entry to begin with', table.options.rows === entries && table.rows === entries );
const statusColumn = table.options.columns.filter( function( column ) { return column.key === 'status' } )[0];
check( 'it has a status column, sorted and searched on the slug', statusColumn !== undefined && statusColumn.type === 'string' );
check( '...whose cell says it in words', statusColumn.render( 'pending' ) === 'Pending' && statusColumn.render( 'subscribed' ) === 'Confirmed' );
const deleteColumn = table.options.columns[table.options.columns.length - 1];
const deleteButton = deleteColumn.render( 'anna@example.com' );
check( 'the last column is a delete button, the workbench\'s small danger button', deleteButton.tagName === 'BUTTON' && deleteButton.className === 'nino-admin-btn-danger nino-admin-btn--small'
	&& deleteButton.textContent === '/_admin/newsletter/label/delete' );

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

// --- A delete draws the list again on the filter that was on screen ------------------

filter.onChange( 'pending' );
requests.length = 0;
tables.length = 0;
fields.length = 0;
confirms.length = 0;
const remaining = entries.filter( function( e ) { return e.email !== 'bert@example.com' } );
answers['newsletter/delete'] = function() { return [ 200, { ok : true } ] };
answers['newsletter/list'] = function() { return reply( remaining ) };
deleteColumn.render( 'bert@example.com' ).click();
check( 'a delete asks first, naming the address in the question', confirms.length === 1 && confirms[0] === 'bert@example.com — really remove this address?' );
check( '...then posts the address, and asks for the list again', requests.map( function( r ) { return r.action } ).join() === 'newsletter/delete,newsletter/list' && requests[0].payload.email === 'bert@example.com' );
check( '...and the list that comes back is on the filter that was on screen, with the address gone',
	fields[0].options.value === 'pending' && emails( tables[0].options.rows ) === 'newest@example.com' && byId('newsletter-summary').textContent === '2 subscribers · 1 pending' );

confirmed = false;
requests.length = 0;
deleteColumn.render( 'anna@example.com' ).click();
confirmed = true;
check( 'a delete that is not confirmed asks the server nothing', requests.length === 0 );

answers['newsletter/delete'] = function() { return [ 404, { error : 'unknown email', code : 'newsletter_unknown' } ] };
deleteColumn.render( 'gone@example.com' ).click();
check( 'a delete the server refuses is said in the mount with the panel\'s own sentence for it, the list not asked for again', requests.map( function( r ) { return r.action } ).join() === 'newsletter/delete'
	&& list.children.length === 1 && list.children[0].className === 'nino-admin-error' && list.children[0].textContent === 'told 404 newsletter_unknown [/_admin/newsletter/error/load]' );

// --- Showing the panel again ---------------------------------------------------------------

answers['newsletter/list'] = function() { return reply( entries ) };
tables.length = 0;
fields.length = 0;
requests.length = 0;
panel.showCurrent();
check( 'a showing asks for the list again, whatever was shown before', requests.map( function( r ) { return r.action } ).join() === 'newsletter/list' );
check( '...and draws it on the status filter that was on screen', fields[0].options.value === 'pending' && emails( tables[0].options.rows ) === 'newest@example.com,bert@example.com' );

answers['newsletter/list'] = function() { return [ 503, { error : 'Busy' } ] };
panel.showCurrent();
check( 'a list that fails to load is said as the workbench says it, with the panel\'s own sentence for what it was doing',
	list.children.length === 1 && list.children[0].className === 'nino-admin-error' && list.children[0].textContent === 'told 503 Busy [/_admin/newsletter/error/load]' );

// --- The edges -----------------------------------------------------------------------

list.innerHTML = '';
panel._renderList( { entries : [ entries[1] ], counts : { subscribed : 1, pending : 0 }, unsubscribeUrl : '' } );
check( 'one confirmed address is "1 subscriber", with nothing said about pending', byId('newsletter-summary').textContent === '1 subscriber' );
check( 'with no address to ask at there is no hint line', byId('newsletter-bcc-hint') === undefined );

list.innerHTML = '';
panel._renderList( { entries : [], counts : { subscribed : 0, pending : 0 }, unsubscribeUrl : 'https://example.org/.newsletter/unsubscribe' } );
check( 'an empty list says so instead of drawing a table', list.children.length === 1 && list.children[0].className === 'nino-admin-empty' );

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

// --- Copying the BCC line, through the kernel's Nino.clipboard() -----------------------

/**
 *	The BCC card, freshly drawn, with its copy button and the line next to it
 *
 *	@return		{Object}									{ copied, press( api, command ) } - press() presses the button with the clipboard the way the test
 *																		wants it (the api answers, refuses or is not there; the copy command answers or
 *																		refuses), waits for the promises and answers what the line next to the button says
 */
function card() {

	list.innerHTML = '';
	panel._renderList( { entries : entries, counts : { subscribed : 2, pending : 2 }, unsubscribeUrl : '' } );

	const copied = byId('newsletter-bcc-copied');
	const button = descendants( list ).filter( function( el ) { return el.textContent === '/_admin/newsletter/label/copy' } )[0];

	return {
		copied : copied,
		press : async function( api, command ) {
			copyApi = api;
			copyCommand = command;
			selected.length = 0;
			button.click();
			await settle();
			return [ copied.textContent, copied.classes.includes( 'nino-admin-error' ), copied.classes.includes( 'admin-hidden' ) === false ];
		},
	};
}

async function main() {

	const written = [];
	const works = { writeText : function( text ) { written.push( text ); return Promise.resolve() } };
	const refuses = { writeText : function() { return Promise.reject( new Error( 'denied' ) ) } };

	const first = card();
	let copy = await first.press( works, false );
	check( 'a copy says "Copied!" in the line next to the button, and shows it', copy[0] === '/_admin/newsletter/label/copied' && copy[1] === false && copy[2] === true );
	check( '...what was copied is the BCC line, selected first', written.join() === 'anna@example.com, old@example.com' && selected.length === 1 );
	check( '...and the line is hidden again after two seconds', timers.some( function( timer ) { return timer.delay === 2000 } ) === true );
	timers.filter( function( timer ) { return timer.delay === 2000 } ).forEach( function( timer ) { timer.fn() } );
	check( '...when its time is up', first.copied.classes.includes( 'admin-hidden' ) === true );

	const second = card();
	copy = await second.press( refuses, false );
	check( 'a copy that fails says so in the workbench\'s error colour, and the line stays', copy[0] === '/_admin/newsletter/error/copy' && copy[1] === true && copy[2] === true );
	copy = await second.press( works, false );
	check( 'the next one that works is not marked so any more', copy[0] === '/_admin/newsletter/label/copied' && copy[1] === false && copy[2] === true );

	/*	The kernel tries the older way after the api refuses, and where there is no
		api: a copy that the browser's own command takes is a copy. The panel
		used to go to the error line at the first refusal	*/
	copy = await card().press( refuses, true );
	check( 'a clipboard api that refuses is not yet a failure: the older way runs, and a copy it makes is "Copied!"', copy[0] === '/_admin/newsletter/label/copied' && copy[1] === false );
	copy = await card().press( null, true );
	check( '...and so is a browser without the api', copy[0] === '/_admin/newsletter/label/copied' && copy[1] === false );
	copy = await card().press( null, false );
	check( '...while a browser that has neither says it failed', copy[0] === '/_admin/newsletter/error/copy' && copy[1] === true );
	check( 'the textarea the older way makes for the moment is gone again', body.children.length === 0 );

	console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
	process.exit( failures === 0 ? 0 : 1 );
}

main();
