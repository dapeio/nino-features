/**
 *	Nino features
 *	newsletter-js-smoke.js	What the script of the panel does over the kernel's stand-in of a page and the real Nino.adminUi, a few pins:
 *
 *													A. the list: a row for each address, an address is text and never markup, the status is a badge with a sign, no row is a target of its own
 *													B. the filter sets the rows of the table, and the export writes what is on show: four columns, never a credential
 *													C. a delete asks first, sends the address, draws the list again and leaves the focus on the next row
 *													D. the BCC line holds the confirmed addresses only, and says how many in the singular and in the plural
 *													E. nothing to list is said in a box and has no bar
 *
 *													The helpers - check(), done(), findAll(), element(), documentOf() and load() - are those of tests/harness.js of the
 *													checkout, three levels up or the one NINO_ROOT names. newsletter-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Newsletter/tests/newsletter-js-smoke.js
 *	       NINO_ROOT=../nino node features/Newsletter/tests/newsletter-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, setImmediate */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, documentOf } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const words = Object.assign( {}, fills('_admin/text/en_US.php'), fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ) );
const W = function( key ) { return words['/_admin/newsletter/'+ key] };

// What the server answers: the newest first. The token is already left out by the server - a rule of the PHP suite - and one is put in here all the same, as an answer of a server that forgot it: the export must not carry it
const LIST = {
	entries : [
		{ email : 'new@example.com', status : 'pending', date : '2026-03-03 10:00:00', ip : '203.0.113.9', token : 'secret-token-1' },
		{ email : '<img src=x onerror=alert(1)>@example.com', status : 'subscribed', date : '2026-02-02 10:00:00', ip : '203.0.113.8' },
		{ email : '=cmd@example.com', status : 'subscribed', date : '2026-01-01 10:00:00', ip : '203.0.113.7' },
	],
	counts : { subscribed : 2, pending : 1 },
	unsubscribeUrl : 'https://example.org/.newsletter/unsubscribe',
};

/** The script in a page of its own: the workbench's ui, with the questions, the toasts, the clipboard and the export kept for the test to read */
function world( list ) {

	const page = element('div');
	const mount = element('div');
	const requests = [];
	const toasts = [];
	const questions = [];
	const exported = [];
	const copied = [];
	const state = { clipboard : true };

	mount.id = 'newsletter-list';
	page.appendChild( mount );

	const doc = documentOf( page );

	doc.documentElement = { lang : 'en-US' };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) { return element( tag ) };

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return words[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } },
		admin : { dirty : { register : function() {}, isDirty : function() { return false }, refresh : function() {} }, workLocale : { current : 'en_US', native : 'en_US' } },
		clipboard : function( text ) { copied.push( text ); return Promise.resolve( state.clipboard ) },
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, Promise : Promise, CSS : { escape : function( value ) { return String( value ) } } };

	sandbox.window = { Nino : Nino, location : { hash : '', href : 'http://workbench.test/_admin/' }, setTimeout : function() { return 0 }, clearTimeout : function() {} };

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	Nino.adminUi.say = function( text ) { toasts.push( text ) };
	Nino.adminUi.confirm = function( level, options ) { questions.push( { level : level, options : options } ) };
	Nino.admin.exportCsv = function( name, rows ) { exported.push( { name : name, rows : rows } ) };
	load( relative, context );

	const w = {
		list : mount, requests : requests, toasts : toasts, questions : questions, exported : exported, copied : copied, state : state, doc : doc, panel : Nino.admin.newsletter,
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		open : function() {
			w.panel.showCurrent();
			w.answer( 200, JSON.parse( JSON.stringify( list ?? LIST ) ) );
		},
	};

	return w;
}

const text = function( root ) { return findAll( root, function() { return true } ).map( function( node ) { return node.textContent } ).join('\n') };
const buttons = function( root ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' } ) };
const named = function( root, name ) { return buttons( root ).filter( function( button ) { return button.textContent === name } ) };
const rowsOf = function( root ) { return findAll( root, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) };
const settle = function() { return new Promise( function( resolve ) { setImmediate( resolve ) } ) };

( async function() {

	// --- A. the list ------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();

		const rows = rowsOf( w.list );

		check( 'A. a row for each address, by the address', rows.map( function( row ) { return row.dataset.row } ).join() === LIST.entries.map( function( entry ) { return entry.email } ).join() );
		check( 'A. an address is text and never markup: nothing of it that spells an element is one, and it is no link - a link in a cell is a target too small for the guide', findAll( w.list, function( node ) { return node.tagName === 'IMG' || node.tagName === 'A' } ).length === 0
			&& text( w.list ).indexOf( '<img src=x onerror=alert(1)>@example.com' ) !== -1 );
		check( 'A. the status is a badge with a word and a sign, and the state it carries is the colour: warn for a pending one, ok for a confirmed one', ( function() {
			const badges = findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-badge') !== -1 } );
			return badges.length === 3 && badges[0].textContent === '● '+ W('status/pending') && badges[0].dataset.state === 'warn' && badges[1].textContent === '✓ '+ W('status/subscribed') && badges[1].dataset.state === 'ok';
		} )() );
		check( 'A. a row is no target that opens something: no row listens for a click, and the delete of each row is a button of its own', rows.every( function( row ) { return ( row.listeners.click ?? [] ).length === 0 } ) && named( w.list, words['/_admin/common/label/delete'] ).length === 3 );
		check( 'A. the list has its search, the bar of the list with the two buttons, and the page a subscriber asks for a link at', findAll( w.list, function( node ) { return node.tagName === 'INPUT' && node.type === 'search' } ).length >= 1
			&& named( w.list, W('label/copy-bcc') ).length === 1 && named( w.list, W('label/export') ).length === 1 && text( w.list ).indexOf( LIST.unsubscribeUrl ) !== -1 );
	}

	// --- B. the filter and the export ----------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		named( w.list, W('label/export') )[0].listeners.click[0]();

		const all = w.exported[0];

		check( 'B. the export writes the rows of the list in the file the panel names, four columns each, the status as its word of the file and no token or any other key', all.name === W('label/filename') && all.rows.length === 3
			&& all.rows.every( function( row ) { return Object.keys( row ).join() === 'email,status,date,ip' } ) && all.rows[0].status === 'pending' && JSON.stringify( all.rows ).indexOf('token') === -1 );

		const chips = findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-chip') !== -1 } );

		check( 'B. the status is narrowed by two chips in the bar of the filters, with how many there are of each', chips.length === 2 && chips[0].textContent === W('status/subscribed')+ ' (2)' && chips[1].textContent === W('status/pending')+ ' (1)'
			&& findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-filterbar') !== -1 } ).length === 1 );

		chips[0].listeners.click[0]();
		named( w.list, W('label/export') )[0].listeners.click[0]();

		check( 'B. a filter sets the rows of the table that is there, and the export writes what is on show', rowsOf( w.list ).length === 2 && w.exported[1].rows.map( function( row ) { return row.email } ).join() === LIST.entries.slice( 1 ).map( function( entry ) { return entry.email } ).join() );
	}

	// --- C. a delete -------------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		named( rowsOf( w.list )[0], words['/_admin/common/label/delete'] )[0].listeners.click[0]();

		check( 'C. a delete asks first, at the level of what is lost for good, and says the address and what it takes: no request is made before the answer', w.questions.length === 1 && w.questions[0].level === 'destructive'
			&& w.questions[0].options.message.indexOf('new@example.com') !== -1 && w.questions[0].options.yes === W('confirm/delete-title') && w.requests.length === 0 );

		w.questions[0].options.onChoose( false );

		check( 'C. a no sends nothing', w.requests.length === 0 );

		w.questions[0].options.onChoose( true );

		const sent = w.requests[0];

		check( 'C. a yes sends the address and nothing else', sent.action === 'newsletter/delete' && JSON.stringify( sent.payload ) === JSON.stringify( { email : 'new@example.com' } ) );

		w.answer( 200, {} );

		check( 'C. the answer draws the list again', w.requests.length === 1 && w.requests[0].action === 'newsletter/list' );

		const rest = JSON.parse( JSON.stringify( LIST ) );

		rest.entries.shift();
		rest.counts = { subscribed : 2, pending : 0 };
		w.answer( 200, rest );

		check( 'C. the focus stays in the list - on the row that came in the place of the one that went - and the toast says it', rowsOf( w.list ).length === 2 && [ rowsOf( w.list )[0] ].concat( findAll( rowsOf( w.list )[0], function() { return true } ) ).filter( function( node ) { return node.focused === true } ).length === 1 && w.toasts.join() === words['/_admin/common/msg/deleted'] );
	}

	{
		const w = world();

		w.open();
		named( rowsOf( w.list )[0], words['/_admin/common/label/delete'] )[0].listeners.click[0]();
		w.questions[0].options.onChoose( true );
		w.answer( 404, { error : 'unknown email', code : 'newsletter_unknown' } );

		check( 'C. a delete the server refuses is said in place of the list, and no toast says it is done', w.toasts.length === 0 && text( w.list ).length > 0 && rowsOf( w.list ).length === 0 );
	}

	// --- C2. a delete on a later page

	{
		const many = JSON.parse( JSON.stringify( LIST ) );

		many.entries = [];

		for( let n = 0; n < 60; n++ )
			many.entries.push( { email : 'p'+ String( n ).padStart( 2, '0' )+ '@example.com', status : 'subscribed', date : '2026-01-01 10:00:00', ip : '203.0.113.1' } );

		many.counts = { subscribed : 60, pending : 0 };

		const w = world( many );

		w.open();

		const next = buttons( w.list ).filter( function( button ) { return button.getAttribute('aria-label') === words['/_admin/common/label/nextpage'] } )[0];

		next.listeners.click[0]();
		check( 'C. the table of more than 50 rows has a second page, and the row of it can be deleted', rowsOf( w.list ).length === 10 );

		named( rowsOf( w.list )[3], words['/_admin/common/label/delete'] )[0].listeners.click[0]();
		w.questions[0].options.onChoose( true );
		w.answer( 200, {} );

		const rest = JSON.parse( JSON.stringify( many ) );

		rest.entries.splice( 53, 1 );
		rest.counts = { subscribed : 59, pending : 0 };
		w.answer( 200, rest );

		check( 'C. the table draws its first page again, which has not the row that stood there: the focus is in the list - on its search or the list itself - and never on the page', [ w.list ].concat( findAll( w.list, function() { return true } ) ).filter( function( node ) { return node.focused === true } ).length === 1 );
	}

	// --- D. the BCC line --------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		named( w.list, W('label/copy-bcc') )[0].listeners.click[0]();
		await settle();

		check( 'D. the BCC line is the confirmed addresses, comma and space between, and a pending one is not in it; with two it says so in the plural',
			w.copied[0] === '<img src=x onerror=alert(1)>@example.com, =cmd@example.com' && w.toasts[0] === W('msg/bcc-many').replace( '%s', '2' ) );

		w.state.clipboard = false;
		named( w.list, W('label/copy-bcc') )[0].listeners.click[0]();
		await settle();

		check( 'D. a copy the browser refuses is said where the list stands, as an error of the workbench and not as a hint or a toast', w.toasts.length === 1 && text( w.list ).indexOf( words['/_admin/error/newsletter_copy'] ) !== -1
			&& findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-error') !== -1 && node.textContent === words['/_admin/error/newsletter_copy'] } ).length === 1 && findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-hint') !== -1 } ).length === 0 );

		const one = JSON.parse( JSON.stringify( LIST ) );

		one.entries = one.entries.slice( 0, 2 );
		one.counts = { subscribed : 1, pending : 1 };

		const single = world( one );

		single.open();
		named( single.list, W('label/copy-bcc') )[0].listeners.click[0]();
		await settle();

		check( 'D. with one address it says so in the singular', single.toasts[0] === W('msg/bcc-one') );

		const pending = JSON.parse( JSON.stringify( LIST ) );

		pending.entries = pending.entries.slice( 0, 1 );
		pending.counts = { subscribed : 0, pending : 1 };

		const waiting = world( pending );

		waiting.open();

		check( 'D. with no confirmed address there is no line to copy', named( waiting.list, W('label/copy-bcc') ).length === 0 && named( waiting.list, W('label/export') ).length === 1 );
	}

	// --- E. nothing to list -------------------------------------------------------------------------------------------------

	{
		const w = world( { entries : [], counts : { subscribed : 0, pending : 0 }, unsubscribeUrl : '' } );

		w.open();

		check( 'E. no signup is said in a box, with no table and no bar of the list', text( w.list ).indexOf( W('empty') ) !== -1 && rowsOf( w.list ).length === 0 && buttons( w.list ).length === 0 );
	}

	done();

} )();
