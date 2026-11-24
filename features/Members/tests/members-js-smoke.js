/**
 *	Nino features
 *	members-js-smoke.js	What the script of the panel does over the real shell of the workbench (adminPage() of the kernel's harness: the real router, the real registry
 *											of unsaved input, dialogs that open the way a browser's do) and the real Nino.adminUi, one pin for each thing:
 *
 *											A. the directory: a row for each account by its id, a name and an address that are text and never markup, the group, the state as a badge
 *											   with a sign, the last sign-in as a date; the group as the first select - only where there is a group to choose - and the state as the
 *											   second; the box to choose rows by; a row of three buttons - open, a link, delete - the link only for an account that is active
 *											B. the record: an account opens at #members/members/<id> with its name as the title and its address as the line under it; the address, the
 *											   name, the group and the tick of its state; Save posts the name, the group and the state - never the address - and draws the row anew; the
 *											   deletion asks on the record; a link and "Sign out everywhere" are in its bar where they have an effect
 *											C. a new member: a dialog of four fields at #members/members/@new, what it posts for each way an invitation goes out, the refusals of the
 *											   server said at their field, an invitation that failed said where the list stands, a link made without a mail shown once
 *											D. what is chosen: the deletion of the chosen rows says how many it takes and sends one delete for each; a failure stops it and is said
 *											E. the address: a reload opens the directory with the record, Back closes it, an address that names nothing is the directory, a dialog of a new
 *											   member that is open is the screen
 *											F. the data and the actions are apart from the screen
 *											G. every word the script reads is in both languages of the panel
 *
 *											The helpers - check(), done(), findAll(), element(), load() and adminPage() - are those of tests/harness.js of the checkout, three levels up or
 *											the one NINO_ROOT names. members-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Members/tests/members-js-smoke.js
 *	       NINO_ROOT=../nino node features/Members/tests/members-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, adminPage, element, findAll, fire } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const words = Object.assign( {}, fills('_admin/text/en_US.php'), fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ) );
const german = Object.assign( {}, fills('_admin/text/de_DE.php'), fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ) );
const W = function( key ) { return words['/_admin/members/'+ key] };

// What the server answers: every account with its key, sorted by name. The names and addresses hold markup, as a visitor could have typed them
const ROWS = [
	{ key : '0123456789ab', id : '0123456789ab', email : 'ada@example.org', name : 'Ada Lovelace', group : 'premium', active : true, created : '2026-01-01T10:00:00Z', login : '2026-11-02T10:15:00Z' },
	{ key : 'abcdef012345', id : 'abcdef012345', email : 'bare@example.org', name : '', group : '', active : true, created : '2026-01-02T10:00:00Z', login : null },
	{ key : 'fedcba987654', id : 'fedcba987654', email : 'eve@example.org', name : '<img src=x onerror=alert(1)>', group : 'staff', active : false, created : '2026-01-03T10:00:00Z', login : null },
];
const LIST = { rows : ROWS, groups : [ 'premium', 'staff' ] };

const clone = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
const hasClass = function( node, name ) { return String( node.className ).split(/\s+/).indexOf( name ) !== -1 };
const byClass = function( root, name ) { return findAll( root, function( node ) { return hasClass( node, name ) } ) };
const tag = function( root, name ) { return findAll( root, function( node ) { return node.tagName === name.toUpperCase() } ) };
const text = function( node ) { return findAll( node, function() { return true } ).map( function( one ) { return one.textContent } ).join('\n') };

/** The panel in a page of the workbench of its own: the requests are answered by the test, and the forms of the dialog of a new member are kept for it to read */
function world( list, hash, realForm ) {

	const h = adminPage( { members : [] } );
	const mount = element('div');
	const requests = [];
	const toasts = [];
	const forms = [];
	const copied = [];

	mount.id = 'members-list';
	h.page.appendChild( mount );
	h.show('members');
	h.router.start();

	if( hash !== undefined )
		h.box.location.hash = hash;
	else
		h.router.set( 'members', [] );

	h.Nino.content.getText = function( key ) { return words[key] ?? '' };
	h.Nino.http = { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } };
	h.Nino.clipboard = function( value ) { copied.push( value ); return Promise.resolve( true ) };
	h.ui.say = function( message ) { toasts.push( message ) };
	// The dialog of a new member is a form of the workbench whose fields the test reads: it is kept, and answered through a controller of its own
	if( realForm !== true )
		h.ui.formDialog = function( options ) { forms.push( options ); return true };

	// The options the script gave the directory, for the pins on what it says of its columns
	const made = { directory : null };
	const directory = h.ui.directory;

	h.ui.directory = function( options ) { made.directory = options; return directory.call( h.ui, options ) };
	// The page of the harness knows no [open] of a dialog: it is asked as the browser answers it
	const queryAll = h.doc.querySelectorAll;

	h.doc.querySelectorAll = function( selector ) { return selector === 'dialog[open]' ? h.dialogs.filter( function( one ) { return one.open === true } ) : queryAll( selector ) };
	h.doc.querySelector = function( selector ) { return h.doc.querySelectorAll( selector )[0] ?? null };
	h.box.KeyboardEvent = function( type, init ) { return Object.assign( { type : type }, init ) };

	load( relative, h.context );

	const w = {
		h : h, mount : mount, requests : requests, toasts : toasts, forms : forms, copied : copied, made : made, panel : h.admin.members,
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		open : function() {
			w.panel.showCurrent();
			w.answer( 200, clone( list ?? LIST ) );
		},
		rows : function() { return findAll( mount, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) },
		dialog : function() { return h.dialogs.filter( function( one ) { return one.open === true } ).pop() ?? null },
		// What a form of the workbench does with the answer of the server: its controller keeps what was said
		controller : function() {
			const said = { closed : 0, failed : [] };
			return { said : said, ctl : { close : function() { said.closed++ }, fail : function( key, message ) { said.failed.push( [ key, message ] ) }, idle : function() {} } };
		},
	};

	return w;
}

const buttons = function( root ) { return tag( root, 'button' ) };
const named = function( root, label ) { return buttons( root ).filter( function( node ) { return node.attributes['aria-label'] === label || node.textContent === label } ) };
const click = function( node ) { fire( node, 'click', { target : node } ) };
const barOf = function( dialog ) { return byClass( dialog, 'nino-admin-actionbar' )[0] };
const titleOf = function( dialog ) { return byClass( dialog, 'nino-admin-dialog-title' )[0].textContent };

( async function() {

	// --- A. the directory --------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.panel.showCurrent();
		check( 'A. the first showing reads the list, and says that the members are loading', w.requests.length === 1 && w.requests[0].action === 'members/list' && text( w.mount ).indexOf( W('loading') ) !== -1 );
		w.answer( 200, clone( LIST ) );

		const rows = w.rows();
		const data0 = w.panel.model;

		check( 'A. a row for each account, by its id, and no row drawn by hand: the standard directory of the workbench', rows.length === 3 && rows.map( function( row ) { return row.dataset.row } ).join() === '0123456789ab,abcdef012345,fedcba987654' && byClass( w.mount, 'nino-admin-directory' ).length + byClass( w.mount, 'nino-admin-table-scroll' ).length > 0 );
		check( 'A. the columns are the name - which fills the room and ends in an ellipsis -, the address, the group, the state and the last sign-in', findAll( w.mount, function( node ) { return node.tagName === 'TH' && node.dataset.key !== undefined } ).map( function( th ) { return th.dataset.key } ).join() === 'label,addr,group,status,seen'
			&& byClass( w.mount, 'nino-admin-table-cell-fill' ).length === 3 );
		check( 'A. every column says its part in the card of a narrow screen: the name is the name of the card, the address its second line, and the rest is "label: value" - no column left out, and the words of the sort select of the cards are the panel\'s, in both languages',
			w.made.directory.columns.filter( function( column ) { return column.primary === true } ).map( function( column ) { return column.key } ).join() === 'label' && w.made.directory.columns.filter( function( column ) { return column.narrow === 'second' } ).map( function( column ) { return column.key } ).join() === 'addr'
			&& w.made.directory.columns.filter( function( column ) { return column.narrow === false } ).length === 0 && [ 'sortBy', 'sortNone', 'sortUp', 'sortDown' ].every( function( key ) { return w.made.directory.labels[key] !== '' && w.made.directory.labels[key] !== undefined && german[ '/_admin/members/sort/'+ { sortBy : 'by', sortNone : 'none', sortUp : 'up', sortDown : 'down' }[key] ] !== undefined } ) );
		check( 'A. an account that has a name is a row with its address as its second line; one that has none is named by its address, which is said once', data0.rowOf( ROWS[0] ).addr === 'ada@example.org' && data0.rowOf( ROWS[1] ).label === 'bare@example.org' && data0.rowOf( ROWS[1] ).addr === '' );
		check( 'A. a name and an address are text and never markup: nothing of them that spells an element is one', findAll( w.mount, function( node ) { return node.tagName === 'IMG' } ).length === 0 && text( rows[2] ).indexOf('<img src=x onerror=alert(1)>') !== -1 );
		check( 'A. the state is a badge with a sign, the colour is not all that says it, and a sign-in is the date the workbench writes - nothing for an account that never signed in',
			byClass( rows[0], 'nino-admin-badge' )[0].textContent === '✓ '+ W('state/active') && byClass( rows[0], 'nino-admin-badge' )[0].dataset.state === 'ok' && byClass( rows[2], 'nino-admin-badge' )[0].textContent === '● '+ W('state/inactive') && byClass( rows[2], 'nino-admin-badge' )[0].dataset.state === 'muted'
			&& text( rows[0] ).indexOf('2026') !== -1 && text( rows[1] ).indexOf('2026') === -1 );
		check( 'A. a row has three buttons - open, a link, delete, the last in red - each named by the account; the link is not drawn for an account that is not active (it would do nothing)',
			buttons( rows[0] ).filter( function( b ) { return b.attributes['aria-label'] !== undefined && b.attributes['aria-label'].indexOf('Open') === 0 } ).length === 1 && buttons( rows[0] ).some( function( b ) { return b.attributes['aria-label'] === 'Send a link to Ada Lovelace' } ) === true
			&& buttons( rows[0] ).some( function( b ) { return b.attributes['aria-label'] === 'Delete Ada Lovelace' && hasClass( b, 'danger' ) || ( b.attributes['aria-label'] === 'Delete Ada Lovelace' && String( b.className ).indexOf('danger') !== -1 ) } ) === true
			&& buttons( rows[2] ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Send a link') === 0 } ).length === 0 );
		check( 'A. the group is the first select and the state the second, each with "all" first; the box of the head and of every row choose rows', tag( w.mount, 'select' ).filter( function( s ) { return s.id.indexOf('members-') === 0 } ).length === 2 && tag( w.mount, 'select' )[0].id === 'members-group' && tag( w.mount, 'select' )[1].id === 'members-state'
			&& tag( w.mount, 'select' )[0].children.map( function( option ) { return option.value } ).join() === ',premium,staff,-' && tag( w.mount, 'select' )[1].children.map( function( option ) { return option.value } ).join() === ',active,inactive'
			&& tag( w.mount, 'input' ).filter( function( input ) { return input.type === 'checkbox' } ).length === 4 );
		check( 'A. the bar under the directory has "New member" as its main button and "Sign everybody out"', named( w.mount, W('label/new') ).length === 1 && named( w.mount, W('label/signout') ).length === 1 );

		// narrowing
		const group = tag( w.mount, 'select' )[0];
		group.value = 'staff';
		fire( group, 'change' );
		check( 'A. the group narrows the rows that are there, in the browser: a request for nothing', w.rows().length === 1 && w.rows()[0].dataset.row === 'fedcba987654' && w.requests.length === 0 );
		group.value = '-';
		fire( group, 'change' );
		check( 'A. "No group" is the accounts that have none', w.rows().length === 1 && w.rows()[0].dataset.row === 'abcdef012345' );
		group.value = '';
		fire( group, 'change' );

		const state = tag( w.mount, 'select' )[1];
		state.value = 'active';
		fire( state, 'change' );
		check( 'A. the state narrows the rows too: the accounts that are active', w.rows().map( function( row ) { return row.dataset.row } ).join() === '0123456789ab,abcdef012345' );
		state.value = '';
		fire( state, 'change' );

		const search = tag( w.mount, 'input' ).find( function( input ) { return input.type === 'search' } );
		search.value = 'BARE@';
		fire( search, 'input' );
		check( 'A. the search reads the address, the name and the group, whatever the case', w.rows().length === 1 && w.rows()[0].dataset.row === 'abcdef012345' );
		search.value = 'STAFF';
		fire( search, 'input' );
		check( 'A. ...the group too: "staff" finds the account that is in it', w.rows().length === 1 && w.rows()[0].dataset.row === 'fedcba987654' );
	}

	{
		// A project without a group has no group select, and one with none to choose between is no select either
		const w = world();

		w.open();
		check( 'A. the group select has four options where some accounts have none', tag( w.mount, 'select' )[0].children.length === 4 );

		const bare = world( { rows : [ ROWS[1] ], groups : [] } );

		bare.open();
		check( 'A. a project without a group has no group select - a lone option is no choice - and the state select stays', tag( bare.mount, 'select' ).filter( function( s ) { return s.id.indexOf('members-') === 0 } ).length === 1 && tag( bare.mount, 'select' )[0].id === 'members-state' );

		const empty = world( { rows : [], groups : [] } );

		empty.open();
		check( 'A. no account at all is a sentence of the directory, and the button that makes the first stands in the bar', text( empty.mount ).indexOf( W('empty') ) !== -1 && named( empty.mount, W('label/new') ).length === 1 );
	}

	// --- B. the record -----------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		click( w.rows()[0] );

		let dialog = w.dialog();

		check( 'B. a click on a row opens its record at #members/members/<id>: the large dialog with the name as its title and the address under it', dialog !== null && w.h.hash() === '#members/members/0123456789ab' && hasClass( dialog, 'nino-admin-dialog--large' )
			&& titleOf( dialog ) === 'Ada Lovelace' && byClass( dialog, 'nino-admin-dialog-subtitle' )[0].textContent === 'ada@example.org' );
		check( 'B. the record holds the address as a line to read - it is not a field - and the name, the group and a tick that says whether the account is active', tag( dialog, 'input' ).filter( function( input ) { return input.type !== 'checkbox' } ).length === 2
			&& tag( dialog, 'input' ).filter( function( input ) { return input.type === 'checkbox' } ).length === 1 && tag( dialog, 'input' ).filter( function( input ) { return input.type === 'checkbox' } )[0].checked === true && byClass( dialog, 'nino-admin-readline' ).length === 1
			&& text( byClass( dialog, 'nino-admin-readline' )[0] ).indexOf('ada@example.org') !== -1 && tag( dialog, 'select' ).length === 0 );
		check( 'B. the tick has no help - the sentence that says what it does stays in view as its note - and the bar has the deletion at the left, a link and "Sign out everywhere" between, and no Cancel',
			byClass( dialog, 'nino-admin-help' ).filter( function( node ) { return false } ).length === 0 && text( dialog ).indexOf( W('hint/active') ) !== -1
			&& barOf( dialog ).children[0].textContent === W('confirm/delete-title') && named( barOf( dialog ), W('label/link') ).length === 1 && named( barOf( dialog ), W('label/signout-one') ).length === 1 && named( barOf( dialog ), words['/_admin/common/label/cancel'] ).length === 0
			&& named( barOf( dialog ), words['/_admin/common/label/save-next'] ).length === 1 );

		// save
		const inputs = tag( dialog, 'input' ).filter( function( input ) { return input.type !== 'checkbox' } );

		inputs[0].value = 'Ada L.';
		fire( inputs[0], 'input' );
		inputs[1].value = 'vip';
		fire( inputs[1], 'input' );

		const tick = tag( dialog, 'input' ).filter( function( input ) { return input.type === 'checkbox' } )[0];

		tick.checked = false;
		fire( tick, 'change' );
		click( buttons( barOf( dialog ) ).filter( function( b ) { return b.textContent === words['/_admin/common/label/save'] } )[0] );
		check( 'B. Save posts the id, the name, the group and the state - and never the address', w.requests.length === 1 && w.requests[0].action === 'members/save'
			&& JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab', name : 'Ada L.', group : 'vip', active : false } ) );
		w.answer( 200, { account : Object.assign( clone( ROWS[0] ), { name : 'Ada L.', group : 'vip', active : false } ) } );
		check( 'B. the record stays, saved, with the new name in its head, and the row behind it is drawn anew - fresh - with the state it has now; the list is not read again',
			w.dialog() === dialog && titleOf( dialog ) === 'Ada L.' && hasClass( w.rows()[0], 'is-fresh' ) && byClass( w.rows()[0], 'nino-admin-badge' )[0].textContent === '● '+ W('state/inactive') && w.requests.length === 0 );
		click( tag( dialog, 'button' ).find( function( b ) { return b.attributes['aria-label'] === words['/_admin/common/label/close'] } ) );
		click( w.rows()[0] );
		check( 'B. an account that is switched off has no link to send and nothing to sign out of: neither button is drawn when its record is opened', named( barOf( w.dialog() ), W('label/link') )[0].hidden === true && named( barOf( w.dialog() ), W('label/signout-one') )[0].hidden === true );
		dialog = w.dialog();

		// a refusal at its field
		inputs[0].value = 'x'.repeat( 121 );
		fire( inputs[0], 'input' );
		click( buttons( barOf( dialog ) ).filter( function( b ) { return b.textContent === words['/_admin/common/label/save'] } )[0] );
		w.answer( 400, { error : 'x', code : 'members_long', params : [ 120 ], field : 'name' } );
		check( 'B. a refusal of the server is said at the name, and the record stays open with what was typed', w.dialog() === dialog && byClass( dialog, 'nino-admin-field-error' ).length === 1 );
	}

	{
		// the deletion, on the record: the question stands on it
		const w = world();

		w.open();
		click( w.rows()[1] );

		const dialog = w.dialog();

		click( barOf( dialog ).children[0] );
		check( 'B. the deletion asks on the record, with the name of the account - or its address, where it has none - and sends nothing before the answer', w.h.dialogs.filter( function( one ) { return one.open === true } ).length === 2 && w.requests.length === 0
			&& text( w.dialog() ).indexOf('bare@example.org') !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'B. yes sends the one delete of the server, with the id', w.requests.length === 1 && w.requests[0].action === 'members/delete' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : 'abcdef012345' } ) );
		w.answer( 200, {} );
		check( 'B. the record is gone with the account, the row is out of the directory, and the list is not read again', w.dialog() === null && w.rows().length === 2 && w.requests.length === 0 );
	}

	{
		// a link from the record, and the sign-out of one account
		const w = world();

		w.open();
		click( w.rows()[0] );

		const dialog = w.dialog();

		click( named( barOf( dialog ), W('label/link') )[0] );
		check( 'B. a link asks how it goes - by mail, made without a mail - on the record, and sends nothing before the answer', w.h.dialogs.filter( function( one ) { return one.open === true } ).length === 2 && w.requests.length === 0
			&& named( w.dialog(), W('invite/mail') ).length === 1 && named( w.dialog(), W('invite/link-only') ).length === 1 );
		click( named( w.dialog(), W('invite/mail') )[0] );
		check( 'B. by mail it posts the id and notify', JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab', notify : true } ) && w.requests[0].action === 'members/link' );
		w.answer( 200, { sent : true } );
		check( 'B. ...and says it as a toast', w.toasts[0] === W('msg/sent') );

		click( named( barOf( dialog ), W('label/link') )[0] );
		click( named( w.dialog(), W('invite/link-only') )[0] );
		check( 'B. made without a mail it posts notify false', JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab', notify : false } ) );
		w.answer( 200, { sent : false, link : 'https://example.org/club/.members/login?t=ONCE' } );

		const shown = w.dialog();

		check( 'B. the link is shown once, in a read-only field with a button that copies it, and the toast says it was made without a mail', w.toasts[1] === W('msg/sent-quiet') && tag( shown, 'input' ).length === 1 && tag( shown, 'input' )[0].readOnly === true && tag( shown, 'input' )[0].value === 'https://example.org/club/.members/login?t=ONCE' );
		click( named( shown, W('label/copy') )[0] );
		check( 'B. ...the copy button copies exactly that link', w.copied.join() === 'https://example.org/club/.members/login?t=ONCE' );
		click( named( shown, words['/_admin/common/label/close'] )[0] );

		click( named( barOf( dialog ), W('label/signout-one') )[0] );
		check( 'B. "Sign out everywhere" asks, of the level big, with the name of the account, and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf('Ada Lovelace') !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'B. yes posts the id to the sign-out of the server - one account, not everybody', w.requests.length === 1 && w.requests[0].action === 'members/signout' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab' } ) );
		w.answer( 200, { signedOut : true } );
		check( 'B. ...and says so as a toast', w.toasts[w.toasts.length - 1] === W('msg/signedout-one') );
	}

	// --- C. a new member ---------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		click( named( w.mount, W('label/new') )[0] );

		const dialog = w.forms[0];

		check( 'C. "New member" opens a form of four fields - the address (required), the name, the group and the invitation - at #members/members/@new', w.forms.length === 1 && dialog.title === W('dialog/new') && dialog.fields.map( function( field ) { return field.key } ).join() === 'email,name,group,invite'
			&& dialog.fields[0].required === true && dialog.fields[3].type === 'select' && dialog.fields[3].options.map( function( option ) { return option.value } ).join() === 'mail,link,none' && dialog.fields[3].value === 'mail' && w.h.hash() === '#members/members/@new' );

		let made = w.controller();

		dialog.onSubmit( { email : 'new@example.org', name : 'New', group : 'vip', invite : 'mail' }, made.ctl );
		check( 'C. an invitation by mail posts invite true and notify true', JSON.stringify( w.requests[0].payload ) === JSON.stringify( { email : 'new@example.org', name : 'New', group : 'vip', invite : true, notify : true } ) && w.requests[0].action === 'members/add' );
		w.answer( 400, { error : 'x', code : 'members_taken', field : 'email' } );
		check( 'C. a refusal is said at the field it belongs to, and the form stays', made.said.failed.length === 1 && made.said.failed[0][0] === 'email' && made.said.closed === 0 );

		made = w.controller();
		dialog.onSubmit( { email : 'new@example.org', name : 'New', group : 'vip', invite : 'none' }, made.ctl );
		check( 'C. no invitation posts invite false and notify false', w.requests[0].payload.invite === false && w.requests[0].payload.notify === false );
		w.answer( 200, { account : { id : '111111111111', email : 'new@example.org', name : 'New', group : 'vip', active : true, created : '2026-11-23T10:00:00Z', login : null }, invited : false } );
		check( 'C. a new account closes the form, writes the list over the address of the form, is a row of the directory - with its group a new one of the select - and says so as a toast',
			made.said.closed === 1 && w.h.hash() === '#members' && w.rows().length === 4 && w.rows().some( function( row ) { return row.dataset.row === '111111111111' } ) && w.toasts[0] === W('msg/added') && w.requests.length === 0 );

		made = w.controller();
		click( named( w.mount, W('label/new') )[0] );
		w.forms[1].onSubmit( { email : 'quiet@example.org', name : '', group : '', invite : 'link' }, made.ctl );
		check( 'C. an invitation made without a mail posts notify false', w.requests[0].payload.invite === true && w.requests[0].payload.notify === false );
		w.answer( 200, { account : { id : '222222222222', email : 'quiet@example.org', name : '', group : '', active : true, created : '2026-11-23T10:00:00Z', login : null }, invited : true, link : 'https://example.org/club/.members/login?t=SECRET' } );
		check( 'C. ...and the link is shown once, in a dialog of the workbench with a read-only field, the toast saying it was made without a mail', w.toasts[1] === W('msg/invited-quiet') && w.dialog() !== null && tag( w.dialog(), 'input' )[0].value === 'https://example.org/club/.members/login?t=SECRET' );

		// the account is there where only the mail failed
		click( named( w.dialog(), words['/_admin/common/label/close'] )[0] );
		made = w.controller();
		click( named( w.mount, W('label/new') )[0] );
		w.forms[2].onSubmit( { email : 'late@example.org', name : '', group : '', invite : 'mail' }, made.ctl );
		w.answer( 500, { error : 'x', code : 'members_mail' } );
		check( 'C. an invitation that failed leaves the form, reads the list again and says the failure over the directory: the account is there', made.said.closed === 1 && w.requests.length === 1 && w.requests[0].action === 'members/list' );
		w.answer( 200, clone( LIST ) );
		check( 'C. ...in the words of the code', byClass( w.mount, 'nino-admin-notice' ).length === 1 && byClass( w.mount, 'nino-admin-notice' )[0].textContent.indexOf('mail') !== -1 );
	}

	// --- D. what is chosen -------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();

		// The box of a row, as the table draws it: the first input of the row
		const choose = function( row ) { const box = tag( row, 'input' )[0]; box.checked = true; fire( box, 'click' ) };
		const bar = function() { return byClass( w.mount, 'nino-admin-table-selection' )[0] };

		choose( w.rows()[1] );
		choose( w.rows()[2] );

		const bulk = tag( bar(), 'button' ).filter( function( b ) { return b.textContent === words['/_admin/common/label/delete'] } );

		check( 'D. with rows chosen a bar over the table offers the deletion, in red', hasClass( bar(), 'nino-admin-hidden' ) === false && bulk.length === 1 && String( bulk[0].className ).indexOf('danger') !== -1 );
		click( bulk[0] );
		check( 'D. the deletion of the chosen rows asks, says how many it takes, and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf( W('confirm/delete-many').replace( '%s', '2' ) ) !== -1 );
		check( 'D. the question and its answer say the plural - "Delete members", words of their own - and not the one of a single account', byClass( w.dialog(), 'nino-admin-dialog-title' )[0].textContent === W('confirm/delete-many-title') && W('confirm/delete-many-title') !== W('confirm/delete-title') && german['/_admin/members/confirm/delete-many-title'] !== german['/_admin/members/confirm/delete-title']
			&& buttons( w.dialog() ).filter( function( b ) { return b.textContent === W('confirm/delete-many-title') } ).length === 1 );
		click( buttons( w.dialog() )[1] );
		check( 'D. yes sends one delete for each, one after the other - the server checks the right and the token of each', w.requests.length === 1 && w.requests[0].action === 'members/delete' && w.requests[0].payload.id === 'abcdef012345' );
		w.answer( 200, {} );
		check( 'D. ...the second follows the answer of the first', w.requests.length === 1 && w.requests[0].payload.id === 'fedcba987654' );
		w.answer( 200, {} );
		check( 'D. the focus is on the row that took the place of the first that went - the one that is left - and on the search where none is', w.rows()[0].focused === true );
		check( 'D. both rows are gone, the choice is empty, the toast says how many were deleted and the list is not read again', w.rows().length === 1 && w.rows()[0].dataset.row === '0123456789ab' && hasClass( bar(), 'nino-admin-hidden' ) === true
			&& w.toasts[0] === W('msg/deleted-many').replace( '%s', '2' ) && w.requests.length === 0 );
	}

	{
		const w = world();

		w.open();

		[ 0, 1 ].forEach( function( at ) { const one = tag( w.rows()[at], 'input' )[0]; one.checked = true; fire( one, 'click' ) } );
		click( tag( byClass( w.mount, 'nino-admin-table-selection' )[0], 'button' ).filter( function( b ) { return b.textContent === words['/_admin/common/label/delete'] } )[0] );
		click( buttons( w.dialog() )[1] );
		w.answer( 500, { error : 'x' } );
		check( 'D. a deletion that fails stops the rest - the second is never sent -, leaves the rows, and says the failure over the directory', w.requests.length === 0 && w.rows().length === 3 && byClass( w.mount, 'nino-admin-notice' ).length === 1 && w.toasts.length === 0 );
	}

	{
		// a row's own deletion, and "Sign everybody out"
		const w = world();

		w.open();
		click( buttons( w.rows()[0] ).filter( function( b ) { return b.attributes['aria-label'] === 'Delete Ada Lovelace' } )[0] );
		check( 'D. the deletion of a row asks with the name and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf('Ada Lovelace') !== -1 );
		click( buttons( w.dialog() )[1] );
		w.answer( 200, {} );
		check( 'D. yes deletes it - one delete, the id - and the row is gone without the list read again, the toast says "Deleted."', w.rows().length === 2 && w.toasts[0] === words['/_admin/common/msg/deleted'] && w.requests.length === 0 );

		click( named( w.mount, W('label/signout') )[0] );
		check( 'D. "Sign everybody out" is a question of the level big, in red, and sends nothing before the answer', w.requests.length === 0 && w.dialog() !== null );
		click( buttons( w.dialog() )[1] );
		check( 'D. yes posts all true, and nothing else: everybody', w.requests.length === 1 && w.requests[0].action === 'members/signout' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { all : true } ) );
		w.answer( 200, { signedOut : true } );
		check( 'D. ...and the toast says so', w.toasts[1] === W('msg/signedout') );
	}

	{
		// the focus where nothing is left to take it
		const w = world( { rows : [ ROWS[0] ], groups : [ 'premium' ] } );

		w.open();

		const box = tag( w.rows()[0], 'input' )[0];

		box.checked = true;
		fire( box, 'click' );
		click( tag( byClass( w.mount, 'nino-admin-table-selection' )[0], 'button' ).filter( function( b ) { return b.textContent === words['/_admin/common/label/delete'] } )[0] );
		click( buttons( w.dialog() )[1] );
		w.answer( 200, {} );
		check( 'D. where no row is left the focus is on the search', w.rows().length === 0 && tag( w.mount, 'input' ).filter( function( input ) { return input.type === 'search' } )[0].focused === true );
	}

	{
		// the focus after a question that the record asked: on the button of the bar that asked it
		const w = world();

		w.open();
		click( w.rows()[0] );

		const bar = barOf( w.dialog() );
		const link = named( bar, W('label/link') )[0];

		click( link );
		click( named( w.dialog(), W('invite/mail') )[0] );
		w.answer( 200, { sent : true } );
		check( 'B. after the question of a link is answered the focus is on the button "Send link" of the record that asked it', w.dialog() !== null && link.focused === true );

		const out = named( barOf( w.dialog() ), W('label/signout-one') )[0];

		click( out );
		click( buttons( w.dialog() )[1] );
		w.answer( 200, { signedOut : true } );
		check( 'B. ...and after "Sign out everywhere" on the button that asked it', out.focused === true );
	}

	// --- E. the address ----------------------------------------------------------------------------------------------------------

	{
		// a reload with the address of an account: the directory with the record
		const w = world( null, '#members/members/fedcba987654' );

		w.open();
		check( 'E. a reload on #members/members/<id> opens the directory and, once the rows are there, the record of that account', w.dialog() !== null && titleOf( w.dialog() ) === '<img src=x onerror=alert(1)>' && w.rows().length === 3 );
		check( 'E. an account that is switched off has no link and no sign-out in its bar', named( barOf( w.dialog() ), W('label/link') )[0].hidden === true && named( barOf( w.dialog() ), W('label/signout-one') )[0].hidden === true );
	}

	{
		// Back closes it, and the directory is not read again
		const w = world();

		w.open();
		click( w.rows()[0] );
		check( 'E. a record that a person opened is one entry of the history', w.dialog() !== null && w.h.hash() === '#members/members/0123456789ab' );
		w.h.back();
		w.h.router.arrive();
		w.panel.showCurrent();
		check( 'E. Back closes the record, the address is the directory\'s and the rows are not read again', w.dialog() === null && w.h.hash() === '#members' && w.requests.length === 0 );
	}

	{
		const w = world( null, '#members/members/ffffffffffff' );

		w.open();
		check( 'E. an address that names an account nobody has - twelve hex characters - is the directory, with a notice, and the address is written over by the one of the directory', w.dialog() === null && byClass( w.mount, 'nino-admin-notice' ).length === 1 && w.h.hash() === '#members' );

		[ '#members/members/nothing', '#members/members/@foo', '#members/members/FFFFFFFFFFFF' ].forEach( function( where ) {

			const x = world( null, where );

			x.open();
			check( 'E. '+ where+ ' names no account that could be one: the directory, without a notice, and the address of the directory', x.dialog() === null && byClass( x.mount, 'nino-admin-notice' ).length === 0 && x.h.hash() === '#members' );
		} );
	}

	{
		// what the directory shows stays while a record is open
		const w = world();

		w.open();

		const search = tag( w.mount, 'input' ).find( function( input ) { return input.type === 'search' } );

		search.value = 'ada';
		fire( search, 'input' );
		click( w.rows()[0] );
		click( byClass( w.dialog(), 'nino-admin-dialog-close' )[0] ?? tag( w.dialog(), 'button' ).find( function( b ) { return b.attributes['aria-label'] === words['/_admin/common/label/close'] } ) );
		check( 'E. a record that is closed leaves the directory as it was: the search, and no request for the rows', w.dialog() === null && w.rows().length === 1 && search.value === 'ada' && w.requests.length === 0 );
	}

	{
		// the dialog of a new member
		const w = world( null, '#members/members/@new' );

		w.open();
		check( 'E. a reload on #members/members/@new opens the form of a new member', w.forms.length === 1 && w.forms[0].title === W('dialog/new') && w.dialog() === null );

		w.h.box.location.hash = '#members';
		w.panel.showCurrent();
		check( 'E. a step through the history that leaves the open form of a new member is taken back, and the form stays - where it cannot be taken back, the address is written to the form again', w.forms.length === 1 && w.h.hash() === '#members/members/@new' );
	}

	{
		// Back from the dialog of a new member: with nothing typed in it, it closes; with input, it asks, and stays
		const w = world( null, undefined, true );

		w.open();
		click( named( w.mount, W('label/new') )[0] );
		check( 'E. the real form of a new member is open at #members/members/@new', w.dialog() !== null && w.h.hash() === '#members/members/@new' );
		w.h.back();
		w.h.router.arrive();
		w.panel.showCurrent();
		check( 'E. Back with nothing typed in it closes the form, and the address is the directory\'s', w.dialog() === null && w.h.hash() === '#members' );

		click( named( w.mount, W('label/new') )[0] );

		const email = tag( w.dialog(), 'input' ).find( function( input ) { return input.type === 'email' } );

		email.value = 'typed@example.org';
		fire( email, 'input' );
		w.h.back();
		w.h.router.arrive();
		w.panel.showCurrent();
		check( 'E. Back with input in it asks what to do with it, on the form, and the step is taken back', w.h.dialogs.filter( function( one ) { return one.open === true } ).length === 2 && ( w.h.hash() === '#members/members/@new' || w.h.went.indexOf( 1 ) !== -1 ) );
	}

	{
		[ '#members/members/new', '#members/members/@zzz', '#members/members/%40new', '#members/members/@new/extra', '#members/zzz', '#members/members/a%25' ].forEach( function( where ) {

			const w = world( null, where );

			w.open();
			check( 'E. '+ where+ ' names no dialog of the panel: the directory stands, nothing is opened and the address is the one of the directory', w.forms.length === 0 && w.dialog() === null && w.h.hash() === '#members' );
		} );
	}

	// --- F. the data and the actions are apart from the screen ------------------------------------------------------------

	{
		const w = world();
		const data = w.panel.model;
		const actions = w.panel.actions;

		check( 'F. the data answer without a page: a name, a row, a payload for each of the three forms', data.label( ROWS[1] ) === 'bare@example.org' && data.rowOf( ROWS[2] ).status.state === 'muted' && data.rowOf( ROWS[0] ).seen === '2026-11-02T10:15:00' && data.rowOf( ROWS[1] ).seen === ''
			&& JSON.stringify( data.addPayload( { email : 'a@b.c', name : 'n', group : 'g', invite : 'link' } ) ) === JSON.stringify( { email : 'a@b.c', name : 'n', group : 'g', invite : true, notify : false } )
			&& JSON.stringify( data.savePayload( 'x', { name : 'n', group : 'g', active : true, email : 'no@no.no' } ) ) === JSON.stringify( { id : 'x', name : 'n', group : 'g', active : true } )
			&& JSON.stringify( data.linkPayload( 'x', 'mail' ) ) === JSON.stringify( { id : 'x', notify : true } ) );
		check( 'F. the group and the state a row passes are the same everywhere', data.inGroup( ROWS[1], '-' ) === true && data.inGroup( ROWS[0], '-' ) === false && data.inGroup( ROWS[0], 'premium' ) === true && data.inState( ROWS[2], 'inactive' ) === true && data.inState( ROWS[2], 'active' ) === false && data.inState( ROWS[2], '' ) === true );
		check( 'F. the actions are the six of the server, each ending in a callback', Object.keys( actions ).sort().join() === 'add,link,list,remove,save,signOut' );

		actions.remove( 'abcdef012345', function() {} );
		check( 'F. an action asks the server by the action of the panel and nothing else', w.requests.length === 1 && w.requests[0].action === 'members/delete' );
	}

	// --- G. the words ------------------------------------------------------------------------------------------------------------------

	{
		const script = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
		const used = [ ...script.matchAll( /\bw\('([a-z0-9/-]+)'\)/g ) ].map( function( found ) { return '/_admin/members/'+ found[1] } );
		const direct = [ ...script.matchAll( /'(\/_admin\/members\/[a-z0-9/-]+)'/g ) ].map( function( found ) { return found[1] } );
		const more = [ 'state/active', 'state/inactive', 'label/signout', 'label/signout-one', 'msg/signedout', 'msg/signedout-one' ].map( function( key ) { return '/_admin/members/'+ key } );
		const missing = used.concat( direct, more ).filter( function( key ) { return words[key] === undefined || german[key] === undefined } );

		check( 'G. every word the script reads is in both languages of the panel'+ ( missing.length === 0 ? '' : ' - '+ missing.join() ), used.length > 30 && missing.length === 0 );
		check( 'G. the two languages of the panel have the same keys', Object.keys( fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ) ).join() === Object.keys( fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ) ).join() );
		check( 'G. the script builds no button, dialog, bar, table or list of its own, writes no address by hand and asks the browser nothing', /createElement\(\s*['"](button|dialog|table)|window\.confirm|\balert\(|\bprompt\(|nino-admin-btn-|type\s*[:=]\s*['"]file|location\.hash|['"]#members/.test( script ) === false );
		check( 'G. the directory and the record are the kernel\'s: the script names them, and the form of the sign-in tick has no help', /Nino\.adminUi\.directory\(/.test( script ) === true && /Nino\.adminUi\.record\(/.test( script ) === true && /Nino\.adminUi\.table\(/.test( script ) === false && /checkField\(\s*\{[^}]*help\s*:/.test( script ) === false );
	}

	done();
} )();
