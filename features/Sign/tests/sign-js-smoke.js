/**
 *	Nino features
 *	sign-js-smoke.js	What the script of the panel does over the real shell of the workbench (adminPage() of the kernel's harness: the real router, the real registry
 *										of unsaved input, dialogs that open the way a browser's do) and the real Nino.adminUi, one pin for each thing:
 *
 *										A. the directory: a row for each request by its id - a string of digits too -, the title as text and never markup, the member as the second line
 *										   of a card, the state as a badge with a sign, the day it was asked; the state as the one select; the box to choose rows by; the buttons of
 *										   a row - open, remind where it waits, delete
 *										B. the record: a request opens at #sign/sign/<id> as a read-only entry: the member, the template, the times, and for a signed one the record
 *										   of the signing with the sentence in a box of its own, the two links to the PDFs, Remind, Ask again and Cancel only where they do something,
 *										   the deletion at the left of the bar
 *										C. the view that asks: #sign/sign/@new, a view of its own with its back link - the member, the template, as many free texts as the template has,
 *										   a title, whether the member is mailed - posts what it holds, says a refusal at its field, shows a link made without a mail once
 *										D. what is chosen: remind, cancel and delete of the chosen rows say how many they take, send one call for each - never "all" - and stop at the
 *										   first failure
 *										E. the address: a reload opens the directory with the record or the view, Back closes it, an address that names nothing is the directory
 *										F. the data and the actions are apart from the screen
 *										G. every word the script reads is in both languages
 *
 *										The helpers - check(), done(), fills(), load(), adminPage(), element(), findAll() and fire() - are those of tests/harness.js of the checkout,
 *										three levels up or the one NINO_ROOT names. sign-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Sign/tests/sign-js-smoke.js
 *	       NINO_ROOT=../nino node features/Sign/tests/sign-js-smoke.js
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
const W = function( key ) { return words['/_admin/sign/'+ key] };

const PDF = '/.sign/pdf?r=';
const request = function( id, over ) {
	return Object.assign( {
		key : id, id : id, title : 'Service contract', template : 'contract-one', templateName : 'Contract', account : 'aaaaaaaaaaaa', member : { name : 'Ada Lovelace', email : 'ada@example.org', label : 'Ada Lovelace' },
		status : 'requested', created : '2026-11-02T10:15:00Z', viewed : null, signed : null, declined : null, changed : null, expires : 1800000000, hashShown : 'a'.repeat( 64 ), hashSigned : '', method : '',
		record : { ip : '', agent : '', consent : '' }, files : { shown : true, signed : false }, urls : { shown : PDF+ id+ '&kind=shown', signed : PDF+ id+ '&kind=signed' },
	}, over );
};

// What the server answers: every request with its key, the newest first. An id of only digits is a number in a script that does not mind
const ROWS = [
	request( '0123456789ab', { title : 'Service contract' } ),
	request( '123456789012', { title : '<img src=x onerror=alert(1)>', status : 'signed', signed : '2026-11-03T08:00:00Z', method : 'drawn', hashSigned : 'b'.repeat( 64 ),
		record : { ip : '203.0.113.7', agent : 'Agent/1.0', consent : 'I agree.' }, files : { shown : true, signed : true }, member : { name : '', email : 'bare@example.org', label : 'bare@example.org' } } ),
	request( 'fedcba987654', { title : 'Privacy', status : 'changed', changed : '2026-11-04T08:00:00Z', member : { name : '', email : '', label : '' } } ),
	request( 'aaaaaaaaaa01', { title : 'Old', status : 'cancelled' } ),
];
const LIST = { rows : ROWS, days : 14 };
const OPTIONS = { accounts : [ { id : 'aaaaaaaaaaaa', name : 'Ada Lovelace', email : 'ada@example.org', group : 'staff' }, { id : 'bbbbbbbbbbbb', name : '', email : 'bob@example.org', group : '' } ],
	templates : [ { key : 'contract-one', name : 'Contract', tag : 'contract', inputs : [ { name : 'note', label : 'A note', multiline : true, required : true }, { name : 'extra', label : '', multiline : false, required : false } ] }, { key : 'plain', name : 'Plain', tag : '', inputs : [] } ],
	limits : { title : 120, input : 5000 } };

const clone = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
const hasClass = function( node, name ) { return String( node.className ).split(/\s+/).indexOf( name ) !== -1 };
const byClass = function( root, name ) { return findAll( root, function( node ) { return hasClass( node, name ) } ) };
const tag = function( root, name ) { return findAll( root, function( node ) { return node.tagName === name.toUpperCase() } ) };
const text = function( node ) { return findAll( node, function() { return true } ).map( function( one ) { return one.textContent } ).join('\n') };
const buttons = function( root ) { return tag( root, 'button' ) };
const named = function( root, label ) { return buttons( root ).filter( function( node ) { return node.attributes['aria-label'] === label || node.textContent === label } ) };
const click = function( node ) { fire( node, 'click', { target : node } ) };
const barOf = function( dialog ) { return byClass( dialog, 'nino-admin-actionbar' )[0] };
const titleOf = function( dialog ) { return byClass( dialog, 'nino-admin-dialog-title' )[0].textContent };

/** The panel in a page of the workbench of its own: the requests are answered by the test */
function world( list, hash, locale ) {

	const lang = locale === 'de_DE' ? german : words;
	const h = adminPage( { sign : [] } );
	const list_ = element('div');
	const form_ = element('div');
	const requests = [];
	const toasts = [];
	const copied = [];

	list_.id = 'sign-list';
	form_.id = 'sign-form';
	h.page.appendChild( list_ );
	h.page.appendChild( form_ );
	h.show('sign');
	h.router.start();

	if( hash !== undefined )
		h.box.location.hash = hash;
	else
		h.router.set( 'sign', [] );

	h.Nino.content.getText = function( key ) { return lang[key] ?? '' };
	h.Nino.http = { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } };
	h.Nino.clipboard = function( value ) { copied.push( value ); return Promise.resolve( true ) };
	h.ui.say = function( message ) { toasts.push( message ) };

	const made = { directory : null };
	const directory = h.ui.directory;

	h.ui.directory = function( options ) { made.directory = options; return directory.call( h.ui, options ) };

	const queryAll = h.doc.querySelectorAll;

	h.doc.querySelectorAll = function( selector ) { return selector === 'dialog[open]' ? h.dialogs.filter( function( one ) { return one.open === true } ) : queryAll( selector ) };
	h.doc.querySelector = function( selector ) { return h.doc.querySelectorAll( selector )[0] ?? null };
	h.box.KeyboardEvent = function( type, init ) { return Object.assign( { type : type }, init ) };

	load( relative, h.context );

	const w = {
		h : h, mount : list_, form : form_, requests : requests, toasts : toasts, copied : copied, made : made, panel : h.admin.sign,
		answer : function( status, body ) {
			const asked = requests.shift();
			asked.callback( { status : status, responseJSON : body } );
			return asked;
		},
		open : function( data ) {
			w.panel.showCurrent();
			w.answer( 200, clone( data ?? LIST ) );
		},
		rows : function() { return findAll( list_, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) },
		dialog : function() { return h.dialogs.filter( function( one ) { return one.open === true } ).pop() ?? null },
	};

	return w;
}

( async function() {

	// --- A. the directory --------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.panel.showCurrent();
		check( 'A. the first showing reads the list, and says that the requests are loading', w.requests.length === 1 && w.requests[0].action === 'sign/list' && text( w.mount ).indexOf( W('loading') ) !== -1 );
		w.answer( 200, clone( LIST ) );

		const rows = w.rows();

		check( 'A. a row for each request, by its id - a number-like id is its own string - drawn by the standard directory of the workbench', rows.length === 4 && rows.map( function( row ) { return row.dataset.row } ).join() === '0123456789ab,123456789012,fedcba987654,aaaaaaaaaa01'
			&& byClass( w.mount, 'nino-admin-table-scroll' ).length > 0 );
		check( 'A. the columns are the title - the name of the card, which fills the room -, the member as its second line, the state and the day it was asked', findAll( w.mount, function( node ) { return node.tagName === 'TH' && node.dataset.key !== undefined } ).map( function( th ) { return th.dataset.key } ).join() === 'title,who,status,asked'
			&& w.made.directory.columns.filter( function( column ) { return column.primary === true } ).map( function( column ) { return column.key } ).join() === 'title' && w.made.directory.columns.filter( function( column ) { return column.narrow === 'second' } ).map( function( column ) { return column.key } ).join() === 'who'
			&& [ 'sortBy', 'sortNone', 'sortUp', 'sortDown' ].every( function( key ) { return w.made.directory.labels[key] !== '' && w.made.directory.labels[key] !== undefined } ) );
		check( 'A. a title is text and never markup', findAll( w.mount, function( node ) { return node.tagName === 'IMG' } ).length === 0 && text( rows[1] ).indexOf('<img src=x onerror=alert(1)>') !== -1 );
		check( 'A. the state is a badge with a sign - the colour is not all that says it - and a member who is gone is named by the word for it', byClass( rows[1], 'nino-admin-badge' )[0].textContent === '✓ '+ W('state/signed') && byClass( rows[1], 'nino-admin-badge' )[0].dataset.state === 'ok'
			&& byClass( rows[0], 'nino-admin-badge' )[0].dataset.state === 'warn' && byClass( rows[3], 'nino-admin-badge' )[0].dataset.state === 'muted' && text( rows[2] ).indexOf( W('label/member-gone') ) !== -1 );
		check( 'A. a row that waits has three buttons - open, remind, delete in red; one that is over has two: remind would do nothing', buttons( rows[0] ).filter( function( b ) { return b.attributes['aria-label'] !== undefined } ).length === 3
			&& buttons( rows[1] ).filter( function( b ) { return b.attributes['aria-label'] !== undefined } ).length === 2 && buttons( rows[0] ).some( function( b ) { return b.attributes['aria-label'] === 'Remind of the request Service contract' } ) === true );
		check( 'A. the state is the one select, "all" first, and the box of the head and of every row choose rows', tag( w.mount, 'select' ).filter( function( s ) { return s.id.indexOf('sign-') === 0 } ).length === 1 && tag( w.mount, 'select' )[0].id === 'sign-state'
			&& tag( w.mount, 'select' )[0].children.map( function( option ) { return option.value } ).join() === ',waiting,requested,viewed,signed,declined,cancelled,expired,changed' && tag( w.mount, 'input' ).filter( function( input ) { return input.type === 'checkbox' } ).length === 5 );
		check( 'A. the bar under the directory has "Ask for a signature" as its main button', named( w.mount, W('label/new') ).length === 1 );

		const state = tag( w.mount, 'select' )[0];

		state.value = 'waiting';
		fire( state, 'change' );
		check( 'A. "waiting" narrows the rows in the browser - the ones that wait or ran out - and asks the server for nothing', w.rows().map( function( row ) { return row.dataset.row } ).join() === '0123456789ab' && w.requests.length === 0 );
		state.value = 'signed';
		fire( state, 'change' );
		check( 'A. a state narrows to that state', w.rows().map( function( row ) { return row.dataset.row } ).join() === '123456789012' );
		state.value = '';
		fire( state, 'change' );

		const search = tag( w.mount, 'input' ).find( function( input ) { return input.type === 'search' } );

		search.value = 'BARE@';
		fire( search, 'input' );
		check( 'A. the search reads the member\'s address and the template', w.rows().length === 1 && w.rows()[0].dataset.row === '123456789012' );
		search.value = 'contract-one';
		fire( search, 'input' );
		check( 'A. ...the key of the template too', w.rows().length === 4 );

		const empty = world();

		empty.open( { rows : [], days : 14 } );
		check( 'A. no request at all is a sentence of the directory, and the button that asks the first stands in the bar', text( empty.mount ).indexOf( W('empty') ) !== -1 && named( empty.mount, W('label/new') ).length === 1 );
	}

	// --- B. the record -----------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		click( w.rows()[1] );

		let dialog = w.dialog();

		check( 'B. a click on a row opens its record at #sign/sign/<id>: the large dialog, the title as its title and the id under it', dialog !== null && w.h.hash() === '#sign/sign/123456789012' && hasClass( dialog, 'nino-admin-dialog--large' )
			&& titleOf( dialog ) === '<img src=x onerror=alert(1)>' && byClass( dialog, 'nino-admin-dialog-subtitle' )[0].textContent === '123456789012' );
		check( 'B. a record is read and not edited: no field, and no Save, no Cancel in the bar', tag( dialog, 'input' ).length === 0 && named( barOf( dialog ), words['/_admin/common/label/save'] ).length === 0 && named( barOf( dialog ), words['/_admin/common/label/cancel'] ).length === 0 );
		check( 'B. a signed request shows the proof of the signing: the address, the browser, how it was signed, the sentence agreed to in a box of its own, and both hashes', text( dialog ).indexOf('203.0.113.7') !== -1 && text( dialog ).indexOf('Agent/1.0') !== -1
			&& text( dialog ).indexOf( W('method/drawn') ) !== -1 && byClass( dialog, 'nino-admin-messagebox' )[0].textContent === 'I agree.' && text( dialog ).indexOf( 'a'.repeat( 64 ) ) !== -1 && text( dialog ).indexOf( 'b'.repeat( 64 ) ) !== -1 );
		check( 'B. the two PDFs are links to the route of the feature, in a window of their own, and nothing else is a link', tag( dialog, 'a' ).map( function( a ) { return a.href } ).join() === PDF+ '123456789012&kind=shown,'+ PDF+ '123456789012&kind=signed'
			&& tag( dialog, 'a' ).every( function( a ) { return a.target === '_blank' && a.rel === 'noopener' } ) );
		check( 'B. a request that is over has no Remind and no Cancel and no Ask again - a button with no effect is not drawn - and the deletion stands at the left', [ W('dialog/remind'), W('dialog/cancel'), W('label/again') ].every( function( label ) { return named( barOf( dialog ), label ).every( function( b ) { return b.hidden === true } ) } )
			&& barOf( dialog ).children[0].textContent === W('confirm/delete-title') );
		click( tag( dialog, 'button' ).find( function( b ) { return b.attributes['aria-label'] === words['/_admin/common/label/close'] } ) );

		click( w.rows()[0] );
		dialog = w.dialog();
		check( 'B. a request that waits has Remind and Cancel, and no Ask again; its link is valid until a day', named( barOf( dialog ), W('dialog/remind') )[0].hidden !== true && named( barOf( dialog ), W('dialog/cancel') )[0].hidden !== true && named( barOf( dialog ), W('label/again') ).every( function( b ) { return b.hidden === true } )
			&& text( dialog ).indexOf( W('label/expires') ) !== -1 && tag( dialog, 'a' ).length === 1 );
		click( tag( dialog, 'button' ).find( function( b ) { return b.attributes['aria-label'] === words['/_admin/common/label/close'] } ) );

		click( w.rows()[2] );
		check( 'B. a request that changed has Ask again - and a member who is gone is said as that, with no address', named( barOf( w.dialog() ), W('label/again') )[0].hidden !== true && text( w.dialog() ).indexOf( W('label/member-gone') ) !== -1 );
	}

	{
		// remind from the record: the question says how it goes, and sends nothing before the answer
		const w = world();

		w.open();
		click( w.rows()[0] );

		const dialog = w.dialog();

		click( named( barOf( dialog ), W('dialog/remind') )[0] );
		check( 'B. Remind asks how it goes - by mail, or the link only - on the record, and sends nothing before the answer', w.requests.length === 0 && named( w.dialog(), W('label/send-mail') ).length === 1 && named( w.dialog(), W('label/link-only') ).length === 1 );
		click( named( w.dialog(), W('label/send-mail') )[0] );
		check( 'B. by mail it posts the id and notify true', w.requests[0].action === 'sign/remind' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab', notify : true } ) );
		w.answer( 200, { sent : true } );
		check( 'B. ...says it as a toast and reads the list again', w.toasts[0] === W('msg/remind') && w.requests.length === 1 && w.requests[0].action === 'sign/list' );
		w.answer( 200, clone( LIST ) );

		click( named( barOf( dialog ), W('dialog/remind') )[0] );
		click( named( w.dialog(), W('label/link-only') )[0] );
		check( 'B. made as a link only it posts notify false', w.requests[0].payload.notify === false );
		w.answer( 200, { sent : false, link : 'https://example.org/club/.sign?t=ONCE' } );

		const shown = w.dialog();

		check( 'B. the link is shown once, in a read-only field with a button that copies it', tag( shown, 'input' ).length === 1 && tag( shown, 'input' )[0].readOnly === true && tag( shown, 'input' )[0].value === 'https://example.org/club/.sign?t=ONCE' && w.toasts[1] === W('msg/remind-quiet') );
		click( named( shown, W('label/copy') )[0] );
		check( 'B. ...the copy button copies exactly that link', w.copied.join() === 'https://example.org/club/.sign?t=ONCE' );
	}

	{
		// cancel from the record, and the deletion
		const w = world();

		w.open();
		click( w.rows()[0] );

		const dialog = w.dialog();

		click( named( barOf( dialog ), W('dialog/cancel') )[0] );
		check( 'B. Cancel asks, with the name of the member, and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf('Ada Lovelace') !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'B. yes posts the one id to the cancel of the server', w.requests.length === 1 && w.requests[0].action === 'sign/cancel' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab' } ) );
		w.answer( 200, {} );
		check( 'B. the row says "cancelled" now without the list read again', byClass( w.rows()[0], 'nino-admin-badge' )[0].textContent === '● '+ W('state/cancelled') && w.requests.length === 0 );

		click( barOf( dialog ).children[0] );
		check( 'B. the deletion asks on the record, with the name of the member, and sends nothing before the answer', w.h.dialogs.filter( function( one ) { return one.open === true } ).length === 2 && w.requests.length === 0 && text( w.dialog() ).indexOf('Ada Lovelace') !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'B. yes sends the one delete, with the id', w.requests.length === 1 && w.requests[0].action === 'sign/delete' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab' } ) );
		w.answer( 200, {} );
		check( 'B. the record is gone with the request, the row is out of the directory, and the list is not read again', w.dialog() === null && w.rows().length === 3 && w.requests.length === 0 );
	}

	{
		// Ask again for a request that changed
		const w = world();

		w.open();
		click( w.rows()[2] );
		click( named( barOf( w.dialog() ), W('label/again') )[0] );
		click( named( w.dialog(), W('label/send-mail') )[0] );
		check( 'B. Ask again posts the id of the changed request and notify', w.requests[0].action === 'sign/again' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : 'fedcba987654', notify : true } ) );
		w.answer( 500, { error : 'x', code : 'sign_mail' } );
		check( 'B. a new request whose mail failed is there all the same: the list is read again, and the failure is said over the directory', w.requests.length === 1 && w.requests[0].action === 'sign/list' && byClass( w.mount, 'nino-admin-notice' ).length === 1 );
	}

	// --- C. the view that asks -------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();
		click( named( w.mount, W('label/new') )[0] );
		check( 'C. "Ask for a signature" reads what the form offers first and writes the address of the view that asks', w.requests.length === 1 && w.requests[0].action === 'sign/options' && w.h.hash() === '#sign/sign/@new' );
		w.answer( 200, clone( OPTIONS ) );

		const form = w.form;

		check( 'C. the view is a level of its own with one back link - "Back to ..." - and the form: the member and the template as selects, the title, the switch that says whether the member is mailed, and one main button',
			byClass( form, 'nino-admin-back-link' ).length + byClass( form, 'nino-admin-contextbar' ).length > 0 && tag( form, 'select' ).length === 2 && tag( form, 'form' ).length === 1 && named( form, W('label/ask') ).length === 1
			&& tag( form, 'input' ).filter( function( input ) { return input.type === 'checkbox' && input.checked === true } ).length === 1 && w.dialog() === null );

		const selects = tag( form, 'select' );

		selects[1].value = 'contract-one';
		fire( selects[1], 'change' );
		check( 'C. the template names its free texts and the view draws as many as it has - a long one as a text area, the required one with a star -, none for a template that asks for none',
			tag( form, 'textarea' ).length === 1 && tag( form, 'input' ).filter( function( input ) { return input.type === 'text' } ).length === 2 && byClass( form, 'nino-admin-required' ).length >= 1 );
		selects[1].value = 'plain';
		fire( selects[1], 'change' );
		check( 'C. ...and another template draws its own', tag( form, 'textarea' ).length === 0 );
		selects[1].value = 'contract-one';
		fire( selects[1], 'change' );

		fire( tag( form, 'form' )[0], 'submit', { preventDefault : function() {} } );
		check( 'C. nothing is sent while the member is not chosen: the field says it is missing and has the focus - the sentence stands at the field, with aria-invalid', w.requests.length === 0 && selects[0].attributes['aria-invalid'] === 'true' && selects[0].focused === true );
		selects[0].value = 'aaaaaaaaaaaa';
		fire( selects[0], 'change' );
		fire( tag( form, 'form' )[0], 'submit', { preventDefault : function() {} } );
		check( 'C. ...nor while a free text that is required is empty: it is named, and has the focus', w.requests.length === 0 && tag( form, 'textarea' )[0].attributes['aria-invalid'] === 'true' && tag( form, 'textarea' )[0].focused === true );
		tag( form, 'textarea' )[0].value = 'A note';
		fire( tag( form, 'textarea' )[0], 'input' );
		tag( form, 'input' ).filter( function( input ) { return input.type === 'text' } )[1].value = 'Title here';
		fire( tag( form, 'form' )[0], 'submit', { preventDefault : function() {} } );
		check( 'C. the post carries the member, the template, the free texts by their names, the title and whether the member is mailed - nothing else', w.requests.length === 1 && w.requests[0].action === 'sign/request'
			&& JSON.stringify( Object.keys( w.requests[0].payload ).sort() ) === JSON.stringify( [ 'account', 'inputs', 'notify', 'template', 'title' ] ) && w.requests[0].payload.account === 'aaaaaaaaaaaa' && w.requests[0].payload.template === 'contract-one'
			&& w.requests[0].payload.inputs.note === 'A note' && w.requests[0].payload.inputs.extra === '' && w.requests[0].payload.notify === true );
		w.answer( 400, { error : 'x', code : 'sign_account', field : 'account' } );
		check( 'C. a refusal is said at its field and the view stays, with what was typed', byClass( form, 'nino-admin-field-error' ).length >= 1 && tag( form, 'textarea' )[0].value === 'A note' && w.h.hash() === '#sign/sign/@new' );

		fire( tag( form, 'form' )[0], 'submit', { preventDefault : function() {} } );
		w.answer( 200, { id : 'cccccccccccc' } );
		check( 'C. a request that was made leaves the view for the directory - the address is the directory\'s - and says it as one toast', w.toasts.join() === W('msg/asked') && w.h.hash() === '#sign' );
		check( 'C. ...the list is asked for again', w.requests.length === 1 && w.requests[0].action === 'sign/list' );
	}

	{
		// The member is not mailed: the link is the answer, shown once
		const w = world();

		w.open();
		click( named( w.mount, W('label/new') )[0] );
		w.answer( 200, clone( OPTIONS ) );

		const form = w.form;
		const selects = tag( form, 'select' );

		selects[0].value = 'bbbbbbbbbbbb';
		fire( selects[0], 'change' );
		selects[1].value = 'plain';
		fire( selects[1], 'change' );

		const notify = tag( form, 'input' ).filter( function( input ) { return input.type === 'checkbox' } )[0];

		notify.checked = false;
		fire( notify, 'change' );
		fire( tag( form, 'form' )[0], 'submit', { preventDefault : function() {} } );
		check( 'C. with the switch off the post says notify false', w.requests[0].payload.notify === false );
		w.answer( 200, { id : 'dddddddddddd', link : 'https://example.org/club/.sign?t=SECRET' } );
		w.answer( 200, clone( LIST ) );
		check( 'C. ...and the link is shown once in a dialog with a read-only field, the toast saying the request was made without a mail', w.toasts[0] === W('msg/asked-quiet') && w.dialog() !== null && tag( w.dialog(), 'input' )[0].value === 'https://example.org/club/.sign?t=SECRET' );
	}

	{
		// Nothing to ask with: no member, or no template
		const w = world();

		w.open();
		click( named( w.mount, W('label/new') )[0] );
		w.answer( 200, { accounts : [], templates : OPTIONS.templates, limits : OPTIONS.limits } );
		check( 'C. without an active member the view is not opened: the notice says so over the directory and the address is the directory\'s', byClass( w.mount, 'nino-admin-notice' ).length === 1 && w.h.hash() === '#sign' && tag( w.form, 'form' ).length === 0 );
	}

	// --- D. what is chosen -------------------------------------------------------------------------------------------------------

	{
		const w = world();

		w.open();

		const choose = function( row ) { const box = tag( row, 'input' )[0]; box.checked = true; fire( box, 'click' ) };
		const bar = function() { return byClass( w.mount, 'nino-admin-table-selection' )[0] };

		choose( w.rows()[0] );
		choose( w.rows()[1] );
		check( 'D. with rows chosen a bar over the table offers Remind, Cancel and Delete - the last in red - and no action says "all"', tag( bar(), 'button' ).filter( function( b ) { return [ W('bulk/remind'), W('bulk/cancel'), words['/_admin/common/label/delete'] ].indexOf( b.textContent ) !== -1 } ).length === 3 );
		click( tag( bar(), 'button' ).filter( function( b ) { return b.textContent === W('bulk/remind') } )[0] );
		check( 'D. Remind says how many it takes - only the chosen rows that wait - and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf( W('confirm/remind-many').replace( '%s', '1' ) ) !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'D. yes sends one remind for the one that waits - with the mail - and the signed one is not asked', w.requests.length === 1 && w.requests[0].action === 'sign/remind' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : '0123456789ab', notify : true } ) );
		w.answer( 200, { sent : true } );
		check( 'D. ...the list is read again and the toast says how many were reminded', w.requests.length === 1 && w.requests[0].action === 'sign/list' );
		w.answer( 200, clone( LIST ) );
		check( 'D. ...the toast follows', w.toasts[0] === W('msg/remind-many').replace( '%s', '1' ) );
	}

	{
		const w = world();

		w.open();

		const choose = function( row ) { const box = tag( row, 'input' )[0]; box.checked = true; fire( box, 'click' ) };

		choose( w.rows()[1] );
		choose( w.rows()[3] );
		click( tag( byClass( w.mount, 'nino-admin-table-selection' )[0], 'button' ).filter( function( b ) { return b.textContent === words['/_admin/common/label/delete'] } )[0] );
		check( 'D. the deletion of the chosen rows says how many it takes and sends nothing before the answer', w.requests.length === 0 && text( w.dialog() ).indexOf( W('confirm/delete-many').replace( '%s', '2' ) ) !== -1 );
		click( buttons( w.dialog() )[1] );
		check( 'D. yes sends one delete for each, one after the other, each with its own id', w.requests.length === 1 && w.requests[0].action === 'sign/delete' && w.requests[0].payload.id === '123456789012' );
		w.answer( 200, {} );
		check( 'D. ...the second follows the answer of the first', w.requests.length === 1 && w.requests[0].payload.id === 'aaaaaaaaaa01' );
		w.answer( 200, {} );
		check( 'D. both rows are gone, the toast says how many, and the list is not read again', w.rows().length === 2 && w.toasts[0] === W('msg/delete-many').replace( '%s', '2' ) && w.requests.length === 0 );
	}

	{
		const w = world();

		w.open();

		[ 0, 3 ].forEach( function( at ) { const one = tag( w.rows()[at], 'input' )[0]; one.checked = true; fire( one, 'click' ) } );
		click( tag( byClass( w.mount, 'nino-admin-table-selection' )[0], 'button' ).filter( function( b ) { return b.textContent === words['/_admin/common/label/delete'] } )[0] );
		click( buttons( w.dialog() )[1] );
		w.answer( 500, { error : 'x' } );
		check( 'D. a deletion that fails stops the rest - the second is never sent -, leaves the rows, and says the failure over the directory', w.requests.length === 0 && w.rows().length === 4 && byClass( w.mount, 'nino-admin-notice' ).length === 1 );
	}

	// --- E. the address ----------------------------------------------------------------------------------------------------------

	{
		const w = world( null, '#sign/sign/123456789012' );

		w.open();
		check( 'E. a reload on #sign/sign/<id> opens the directory and, once the rows are there, the record - of an id of only digits too', w.dialog() !== null && titleOf( w.dialog() ) === '<img src=x onerror=alert(1)>' && w.rows().length === 4 );
	}

	{
		const w = world();

		w.open();
		click( w.rows()[0] );
		w.h.back();
		w.h.router.arrive();
		w.panel.showCurrent();
		check( 'E. Back closes the record, the address is the directory\'s and the rows are not read again', w.dialog() === null && w.h.hash() === '#sign' && w.requests.length === 0 );
	}

	{
		const w = world( null, '#sign/sign/ffffffffffff' );

		w.open();
		check( 'E. an address that names a request nobody has - twelve hex characters - is the directory with a notice, written over by the directory\'s', w.dialog() === null && byClass( w.mount, 'nino-admin-notice' ).length === 1 && w.h.hash() === '#sign' );

		[ '#sign/sign/nothing', '#sign/sign/@foo', '#sign/sign/FFFFFFFFFFFF', '#sign/sign/new', '#sign/sign/@new/extra', '#sign/zzz' ].forEach( function( where ) {

			const x = world( null, where );

			x.open();
			check( 'E. '+ where+ ' names nothing the panel has: the directory, no view that asks, and its own address', x.dialog() === null && x.requests.length === 0 && tag( x.form, 'form' ).length === 0 && x.h.hash() === '#sign' );
		} );
	}

	{
		// The view that asks, by its address, and Back from it
		const w = world( null, '#sign/sign/@new' );

		w.open();
		check( 'E. a reload on #sign/sign/@new opens the view that asks, once the list is there', w.requests.length === 1 && w.requests[0].action === 'sign/options' );
		w.answer( 200, clone( OPTIONS ) );
		check( 'E. ...the form is there', tag( w.form, 'form' ).length === 1 && w.h.hash() === '#sign/sign/@new' );
		w.h.back();
		w.h.router.arrive();
		w.panel.showCurrent();
		check( 'E. Back leaves it: the address is the directory\'s, and the form is gone from the address', w.h.hash() !== '#sign/sign/@new' );
	}

	// --- E2. the view that asks does not lose what was typed ----------------------------------------------------------------------

	{
		const ask = function() {

			const w = world();

			w.open();
			click( named( w.mount, W('label/new') )[0] );
			w.answer( 200, clone( OPTIONS ) );

			return w;
		};
		const field = function( w, id ) { return findAll( w.form, function( node ) { return node.id === id } )[0] };
		const stepBack = function( w ) {
			w.h.back();
			w.h.router.arrive();
			w.panel.showCurrent();
		};
		const choose = function( w, label ) { const open = w.dialog(); const button = open === null ? undefined : named( open, label )[0]; if( button !== undefined ) click( button ) };
		const discard = words['/_admin/common/label/discard'];
		const cancel = words['/_admin/common/label/cancel'];
		const form = function( w ) { return tag( w.form, 'form' ).length === 1 && hasClass( w.form, 'admin-hidden' ) === false };

		{
			const w = ask();

			check( 'E2. the view that asks is open at #sign/sign/@new, and untouched it counts as nothing to lose', form( w ) === true && w.h.hash() === '#sign/sign/@new' );
			stepBack( w );
			check( 'E2. Back from a form nobody touched leaves it without a question', w.dialog() === null && w.h.hash() !== '#sign/sign/@new' && form( w ) === false );
		}

		{
			// What counts: the title, the member, the template, the switch, a field of the template
			const touches = {
				title : function( w ) { field( w, 'sign-ask-title' ).value = 'Offer' },
				member : function( w ) { field( w, 'sign-ask-account' ).value = 'aaaaaaaaaaaa' },
				template : function( w ) { field( w, 'sign-ask-template' ).value = 'plain' },
				notify : function( w ) { tag( w.form, 'input' ).filter( function( input ) { return input.type === 'checkbox' } )[0].checked = false },
			};

			Object.keys( touches ).forEach( function( kind ) {

				const w = ask();

				touches[kind]( w );
				stepBack( w );
				check( 'E2. Back after only the '+ kind+ ' was changed asks first, and the view, its address and the input stay while the question is open', w.dialog() !== null && form( w ) === true );
				choose( w, cancel );
				check( 'E2. ...Cancel takes the step back (one entry forward) and keeps the view and the input - '+ kind, w.dialog() === null && form( w ) === true && w.h.went.join() === '1' );
				w.h.forward();
				w.h.router.arrive();
				w.panel.showCurrent();
				check( 'E2. ...the traversal arrives on the address of the view that stays, and no second question comes - '+ kind, w.dialog() === null && form( w ) === true && w.h.hash() === '#sign/sign/@new' && w.requests.length === 0 );
				stepBack( w );
				choose( w, discard );
				check( 'E2. ...Discard leaves it for the directory - '+ kind, w.dialog() === null && form( w ) === false && w.h.hash() !== '#sign/sign/@new' );
				check( 'E2. ...and puts every input back: the member, the template, the switch, the title - '+ kind, field( w, 'sign-ask-account' ).value === '' && field( w, 'sign-ask-template' ).value === '' && field( w, 'sign-ask-title' ).value === ''
					&& tag( w.form, 'input' ).filter( function( input ) { return input.type === 'checkbox' } )[0].checked === true );
			} );
		}

		{
			// A Save that fails (the form is not complete) keeps the view and writes no address of its own: the step is taken back through the history, and the next Back asks again
			const w = ask();

			field( w, 'sign-ask-title' ).value = 'Offer';
			stepBack( w );
			const entriesBefore = w.h.entries.join( '|' );
			const writes = [];
			const set = w.h.router.set;
			const go = w.h.router.go;

			w.h.router.set = function() { writes.push( 'set' ); return set.apply( this, arguments ) };
			w.h.router.go = function() { writes.push( 'go' ); return go.apply( this, arguments ) };
			choose( w, words['/_admin/common/label/save'] );
			check( 'E2. Save with a form that is not complete does not send it, and the step is taken back (one entry forward)', w.requests.length === 0 && w.h.went.join() === '1' && form( w ) === true );
			w.h.router.set = set;
			w.h.router.go = go;
			check( 'E2. ...and the panel writes no address of its own meanwhile - not even one the router would ignore while the step is taken back -: no entry of the history is written over', writes.length === 0 && w.h.entries.join( '|' ) === entriesBefore && w.h.hash() === '#sign' );
			w.h.forward();
			w.h.router.arrive();
			w.panel.showCurrent();
			check( 'E2. ...the traversal arrives on the view that stays, and the next Back asks again', w.h.hash() === '#sign/sign/@new' && w.dialog() === null );
			stepBack( w );
			check( 'E2. ...Back asks again after a Save that failed', w.dialog() !== null && form( w ) === true );
		}

		{
			// The back link of the view
			const w = ask();

			field( w, 'sign-ask-account' ).value = 'bbbbbbbbbbbb';
			const backLink = byClass( w.form, 'nino-admin-back-link' )[0];

			if( backLink !== undefined )
				click( backLink );
			check( 'E2. the back link of the view asks too, and Cancel keeps the view, its address and the member', w.dialog() !== null && form( w ) === true );
			choose( w, cancel );
			check( 'E2. ...kept', w.dialog() === null && form( w ) === true && w.h.hash() === '#sign/sign/@new' && field( w, 'sign-ask-account' ).value === 'bbbbbbbbbbbb' );
		}
	}

	// --- F. the data and the actions are apart from the screen ------------------------------------------------------------

	{
		const w = world();
		const data = w.panel.model;
		const actions = w.panel.actions;

		check( 'F. the data answer without a page: a row is a string key, a state, a badge', data.rowOf( ROWS[1] ).key === '123456789012' && data.rowOf( ROWS[1] ).state === 'signed' && data.rowOf( ROWS[0] ).waiting === true && data.rowOf( ROWS[1] ).waiting === false
			&& data.rowOf( ROWS[0] ).status.state === 'warn' && data.who( ROWS[2] ) === W('label/member-gone') );
		check( 'F. a key is a string whatever the server wrote: an id of only digits that came as a number is the string of it, and so is the key of the row', data.rowOf( Object.assign( clone( ROWS[0] ), { key : 123456789012, id : 123456789012 } ) ).key === '123456789012' );
		check( 'F. the state a row passes is the same everywhere: waiting is requested, viewed and expired', data.inState( data.rowOf( ROWS[0] ), 'waiting' ) === true && data.inState( data.rowOf( ROWS[1] ), 'waiting' ) === false && data.inState( data.rowOf( ROWS[1] ), 'signed' ) === true && data.inState( data.rowOf( ROWS[1] ), '' ) === true );
		check( 'F. a link is only a path of this site, and a time is the clock the server wrote', data.safe( '/.sign/pdf?r=x' ) === '/.sign/pdf?r=x' && data.safe( '//evil.example' ) === '' && data.safe( 'javascript:alert(1)' ) === '' && data.when( '2026-11-02T10:15:00Z' ) === '2026-11-02 10:15 UTC' );
		check( 'F. the actions are the seven of the server, each ending in a callback', Object.keys( actions ).sort().join() === 'again,cancel,list,options,remind,remove,request' );
		actions.remove( '0123456789ab', function() {} );
		check( 'F. an action asks the server by the action of the panel and nothing else', w.requests.length === 1 && w.requests[0].action === 'sign/delete' );
	}

	// --- G. the words ------------------------------------------------------------------------------------------------------------------

	{
		const script = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
		const used = [ ...script.matchAll( /\bw\('([a-z0-9/-]+)'\)/g ) ].map( function( found ) { return '/_admin/sign/'+ found[1] } );
		const direct = [ ...script.matchAll( /'(\/_admin\/sign\/[a-z0-9/-]+)'/g ) ].map( function( found ) { return found[1] } ).filter( function( key ) { return key.slice( -1 ) !== '/' } );
		const dynamic = [ 'requested', 'viewed', 'signed', 'declined', 'cancelled', 'expired', 'changed' ].map( function( state ) { return '/_admin/sign/state/'+ state } )
			.concat( [ 'created', 'viewed', 'signed', 'declined', 'changed', 'document-shown', 'document-signed' ].map( function( key ) { return '/_admin/sign/label/'+ key } ), [ 'drawn', 'typed' ].map( function( key ) { return '/_admin/sign/method/'+ key } ),
				[ 'remind', 'cancel', 'delete' ].map( function( key ) { return '/_admin/sign/bulk/'+ key } ), [ 'remind', 'cancel', 'delete' ].map( function( key ) { return '/_admin/sign/confirm/'+ key+ '-many' } ),
				[ 'remind', 'cancel', 'delete' ].map( function( key ) { return '/_admin/sign/msg/'+ key+ '-many' } ), [ 'remind', 'again' ].map( function( key ) { return '/_admin/sign/msg/'+ key } ), [ 'remind', 'again' ].map( function( key ) { return '/_admin/sign/msg/'+ key+ '-quiet' } ),
				[ 'remind', 'again', 'cancel', 'delete' ].map( function( key ) { return '/_admin/sign/error/'+ key } ) );
		const missing = used.concat( direct, dynamic ).filter( function( key ) { return words[key] === undefined || german[key] === undefined } );

		check( 'G. every word the script reads is in both languages of the panel'+ ( missing.length === 0 ? '' : ' - '+ missing.join() ), used.length > 30 && missing.length === 0 );
		check( 'G. the two languages of the panel have the same keys', Object.keys( fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ) ).join() === Object.keys( fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ) ).join() );
		check( 'G. the script builds no button, dialog, bar, table or list of its own, writes no address by hand and asks the browser nothing', /createElement\(\s*['"](button|dialog|table)|window\.confirm|\balert\(|\bprompt\(|nino-admin-btn-|location\.hash|['"]#sign/.test( script.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /^\s*\/\/.*$/gm, '' ) ) === false );
		check( 'G. the directory and the record are the kernel\'s: the script names them and builds no table of its own', /Nino\.adminUi\.directory\(/.test( script ) === true && /Nino\.adminUi\.record\(/.test( script ) === true && /Nino\.adminUi\.table\(/.test( script ) === false );

		const de = world( null, undefined, 'de_DE' );

		de.open();
		check( 'G. in German the directory says the German words', text( de.mount ).indexOf( german['/_admin/sign/label/search'] ) !== -1 || tag( de.mount, 'input' ).some( function( input ) { return input.type === 'search' } ) );
	}

	done();
} )();
