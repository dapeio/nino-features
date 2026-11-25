/**
 *	Nino features
 *	crm-js-smoke.js		What the script of the panel does over the real shell of the workbench (adminPage() of the kernel's harness: the real router, the real registry of
 *										unsaved input, dialogs that open the way a browser's do) and the real Nino.adminUi, one pin for each thing:
 *
 *										A. the status: a row for each line the server answers, in its order, named by the word of the panel or - for the line of a package - by the label the
 *										   package gave; the state as a badge with a sign; the sentence of the state with the values it brought; a label, a detail and an address that are text
 *										   and never markup; no key of a word stands raw
 *										B. the way to the surface: a link where the server says it is there, a sentence and no dead link where it is not, and no address that is no address
 *										C. a fix: a button only at a line that has one and is not as it should be, one press sends one request with the key of the line, the answer is the screen,
 *										   the toast says it once and the focus is on the line - a failure is said over the table and the button is there again
 *										D. the data of removed packages: the table is there only where there is data, a button only for a package that is removed, the question asks for the name to
 *										   be typed, the answer sends the key and what was typed, the focus goes where the dialog was opened - and a refusal is said over the table
 *										E. the address: #crm and #crm/crm are the screen, an address that says more or names a tab the panel has not is written over by the one the screen has, and
 *										   another panel's address is left alone
 *										F. the data and the actions are apart from the screen
 *										G. every word the script reads is in both languages, for every line and every state the core answers
 *
 *										The helpers - check(), done(), findAll(), element(), load() and adminPage() - are those of tests/harness.js of the checkout, three levels up or
 *										the one NINO_ROOT names. crm-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Crm/tests/crm-js-smoke.js
 *	       NINO_ROOT=../nino node features/Crm/tests/crm-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, adminPage, element, findAll, fire } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const own = function( locale ) { return Object.assign( {}, fills('_admin/text/'+ locale +'.php'), fills( path.relative( root, path.join( featureDir, 'text/'+ locale +'.php' ) ) ) ) };
const words = own('en_US');
const german = own('de_DE');
const W = function( key ) { return words['/_admin/crm/'+ key] };

// What the server answers: the lines of the core in the states they have, and one line of a package that names itself
const LINES = [
	{ key : 'database', hint : false, fixable : true, state : 'missing' },
	{ key : 'writable', hint : false, fixable : false, state : 'ok' },
	{ key : 'schema', hint : false, fixable : true, state : 'behind', version : 1, stored : 0, lacks : [ 'crm-probe' ] },
	{ key : 'probe-line', hint : false, fixable : true, state : 'ok', label : '<img src=x onerror=alert(1)>', detail : 'A line <b>of</b> a package' },
	{ key : 'addresses', hint : true, fixable : false, state : 'default', crm : '', app : '' },
	{ key : 'mail', hint : false, fixable : false, state : 'failed', waiting : 1, failed : 2 },
	{ key : 'mail-head', hint : false, fixable : false, state : 'partial', lacks : [ 'crm-head-sign' ] },
];
const SCREEN = { lines : LINES, whole : false, open : { address : '/crm' }, orphans : [] };
const ORPHANS = [
	{ key : 'crm-gone', package : 'crm-gone', version : 1, tables : 3 },
	{ key : 'crm-also', package : 'crm-also', version : 2, tables : 1 },
];

const clone = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
const hasClass = function( node, name ) { return String( node.className ).split(/\s+/).indexOf( name ) !== -1 };
const byClass = function( root, name ) { return findAll( root, function( node ) { return hasClass( node, name ) } ) };
const tag = function( root, name ) { return findAll( root, function( node ) { return node.tagName === name.toUpperCase() } ) };
const text = function( node ) { return findAll( node, function() { return true } ).map( function( one ) { return one.textContent } ).join('\n') };
const buttons = function( root ) { return tag( root, 'button' ) };
const named = function( root, label ) { return buttons( root ).filter( function( node ) { return node.attributes['aria-label'] === label || node.textContent === label } ) };
const click = function( node ) { fire( node, 'click', { target : node } ) };

/** The panel in a page of the workbench of its own: the requests are answered by the test, the toasts are kept for it to read */
function world( hash ) {

	const h = adminPage( { crm : [ [ 'crm', 'crm' ] ], members : [ [ 'members', 'members' ] ] } );
	const mount = element('div');
	const requests = [];
	const toasts = [];

	mount.id = 'crm-list';
	h.page.appendChild( mount );
	h.show('crm');
	h.router.start();

	if( hash !== undefined )
		h.box.location.hash = hash;
	else
		h.router.set( 'crm', [] );

	h.Nino.content.getText = function( key ) { return words[key] ?? '' };
	h.Nino.http = { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } };
	h.ui.say = function( message ) { toasts.push( message ) };
	h.doc.querySelectorAll = ( function( base ) { return function( selector ) { return selector === 'dialog[open]' ? h.dialogs.filter( function( one ) { return one.open === true } ) : base( selector ) } } )( h.doc.querySelectorAll );
	h.doc.querySelector = function( selector ) { return h.doc.querySelectorAll( selector )[0] ?? null };
	h.box.KeyboardEvent = function( type, init ) { return Object.assign( { type : type }, init ) };

	load( relative, h.context );

	const w = {
		h : h, mount : mount, requests : requests, toasts : toasts, panel : h.admin.crm,
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		open : function( screen ) {
			w.panel.showCurrent();
			w.answer( 200, clone( screen ?? SCREEN ) );
		},
		rows : function() { return findAll( mount, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) },
		row : function( key ) { return w.rows().filter( function( node ) { return node.dataset.row === key } )[0] },
		dialog : function() { return h.dialogs.filter( function( one ) { return one.open === true } ).pop() ?? null },
		focused : function() { return h.doc.activeElement },
	};

	return w;
}

const block = function( name, run ) {

	try {
		run();
	}
	catch( error ) {
		check( 'the block "'+ name +'" ran to its end: it stopped with '+ ( error && error.name ? error.name : 'an error' ), false );
	}
};

( function() {

	// --- A. the status ----------------------------------------------------------------------------------------------------------

	block( 'A', function() {
		const w = world();

		check( 'A. the script attaches under its nav uri with the data and the actions apart from the screen', typeof w.panel === 'object' && typeof w.panel.showCurrent === 'function' && typeof w.panel.model === 'object' && typeof w.panel.actions === 'object' );
		w.panel.showCurrent();
		check( 'A. showing the panel asks the server for the status, once, and nothing else', w.requests.length === 1 && w.requests[0].action === 'crm/status' && JSON.stringify( w.requests[0].payload ) === '{}' );
		w.answer( 200, clone( SCREEN ) );

		const rows = w.rows();

		check( 'A. a row for each line, by its key, in the order the server sent them, drawn by the standard directory of the workbench', rows.map( function( row ) { return row.dataset.row } ).join() === LINES.map( function( line ) { return line.key } ).join() && byClass( w.mount, 'nino-admin-directory' ).length === 1 );
		check( 'A. the columns are the part - its name -, its state and its detail, which says the sentence and gives way to the buttons; the part is the name of the card of a narrow screen and the detail its second line', findAll( w.mount, function( node ) { return node.tagName === 'TH' && node.dataset.key !== undefined } ).map( function( th ) { return th.dataset.key } ).join() === 'label,status,note' );
		check( 'A. a line of the core is named by the word of the panel and a line of a package by the label it gave, as text', text( w.row('database') ).indexOf( W('line/database') ) !== -1 && text( w.row('mail-head') ).indexOf( W('line/mail-head') ) !== -1 && text( w.row('probe-line') ).indexOf('<img src=x onerror=alert(1)>') !== -1
			&& findAll( w.mount, function( node ) { return node.tagName === 'IMG' || node.tagName === 'B' } ).length === 0 );
		check( 'A. the state is a badge with a sign - a line that is as it should be has a check, a fault a dot - and the look follows: ok, warn for what is to do, error for what is wrong, muted for what the owner may leave',
			byClass( w.row('writable'), 'nino-admin-badge' )[0].textContent === '✓ '+ W('state/ok') && byClass( w.row('writable'), 'nino-admin-badge' )[0].dataset.state === 'ok' && byClass( w.row('database'), 'nino-admin-badge' )[0].dataset.state === 'warn'
			&& byClass( w.row('mail'), 'nino-admin-badge' )[0].dataset.state === 'error' && byClass( w.row('mail'), 'nino-admin-badge' )[0].textContent === '● '+ W('state/failed') && byClass( w.row('addresses'), 'nino-admin-badge' )[0].dataset.state === 'muted'
			&& byClass( w.row('addresses'), 'nino-admin-badge' )[0].textContent === '○ '+ W('state/default') );
		check( 'A. the sentence of a state has the values the state brought, in the order it names them - the mails that failed and the ones that wait, the version the file has and the one that is expected, the parts that are behind - and a package says its own detail, as text',
			text( w.row('mail') ).indexOf( W('detail/mail-failed').replace( '%s', '2' ).replace( '%s', '1' ) ) !== -1 && text( w.row('schema') ).indexOf( W('detail/schema-behind').replace( '%s', '0' ).replace( '%s', '1' ).replace( '%s', 'crm-probe' ) ) !== -1
			&& text( w.row('mail-head') ).indexOf( W('detail/mail-head-partial').replace( '%s', 'crm-head-sign' ) ) !== -1 && text( w.row('probe-line') ).indexOf('A line <b>of</b> a package') !== -1 );
		check( 'A. no key of a word stands raw anywhere on the screen', text( w.mount ).indexOf('/_admin/') === -1 && text( w.mount ).indexOf('%s') === -1 );
		check( 'A. there is no table of data of removed packages where there is none: one directory, and no section title for it', byClass( w.mount, 'nino-admin-directory' ).length === 1 && text( w.mount ).indexOf( W('section/orphans') ) === -1 );

		// The text system answers a key it does not know with the key itself - a string that starts with a slash: that is no word
		const keyed = world();

		keyed.h.Nino.content.getText = function( key ) { return words[key] ?? key };

		const unknown = keyed.panel.model.rowOf( { key : 'x-line', hint : false, fixable : false, state : 'brand-new', label : '' } );

		check( 'A. a line and a state the panel has no word for are shown by their keys and not by a raw key of a word: the key of the line, the state as it came, and no sentence', unknown.label === 'x-line' && unknown.status.text === '● brand-new' && unknown.note === '' && unknown.status.state === 'warn' );
	} );

	// --- B. the way to the surface ---------------------------------------------------------------------------------------------------

	block( 'B', function() {
		const w = world();

		w.open();
		check( 'B. the way to the surface is a link with the address the server gave and the word of the panel, in a section of its own, and no sentence that says there is none', findAll( w.mount, function( node ) { return node.id === 'crm-open' } )[0].href === '/crm' && text( w.mount ).indexOf( W('open/none') ) === -1 );

		const ready = world();

		ready.open( Object.assign( clone( SCREEN ), { open : { address : 'https://crm.example.org/' } } ) );

		const link = findAll( ready.mount, function( node ) { return node.id === 'crm-open' } )[0];

		check( 'B. an address of https is a link, in the form of the workbench', link !== undefined && link.tagName === 'A' && link.href === 'https://crm.example.org/' && link.textContent === W('label/open') && hasClass( link, 'nino-admin-linkbutton' ) );

		const same = world();

		same.open( Object.assign( clone( SCREEN ), { open : { address : '/crm' } } ) );
		check( 'B. an address on the same host is a path', findAll( same.mount, function( node ) { return node.id === 'crm-open' } )[0].href === '/crm' );

		const hostile = [ 'javascript:alert(1)', 'data:text/html,x', '//evil.test/', 'http://crm.example.org/', ' https://x.org', '', 'vbscript:x', '/\\evil.test', '\nhttps://x.org/' ].map( function( address ) {

			const page = world();

			page.open( Object.assign( clone( SCREEN ), { open : { address : address } } ) );

			return findAll( page.mount, function( node ) { return node.id === 'crm-open' } ).length + tag( page.mount, 'a' ).length;
		} );

		check( 'B. an address that is no path of this site and no https address - a script, data, a host with no scheme, one that is not safe to follow - is no link, and the sentence says there is nothing to open', hostile.every( function( count ) { return count === 0 } ) );
	} );

	// --- C. a fix ---------------------------------------------------------------------------------------------------------------

	block( 'C', function() {
		const w = world();

		w.open();

		const mend = function( key ) { return buttons( w.row( key ) ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend ') === 0 } ) };

		check( 'C. a button at the line that has a fix and is not as it should be - the database that is missing, the schema that is behind - small, secondary, named by its line', mend('database').length === 1 && mend('schema').length === 1 && hasClass( mend('database')[0], 'nino-admin-btn-secondary' ) && hasClass( mend('database')[0], 'nino-admin-btn--small' ) );
		check( 'C. none where the line is as it should be though it has a fix, none where there is no fix, none at a line that is only a hint', mend('probe-line').length === 0 && mend('writable').length === 0 && mend('mail').length === 0 && mend('addresses').length === 0 && buttons( w.mount ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend') === 0 } ).length === 2 );

		click( mend('database')[0] );
		check( 'C. a press sends one request for one line, with its key and nothing more - not every line', w.requests.length === 1 && w.requests[0].action === 'crm/setup' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { key : 'database' } ) && mend('database')[0].disabled === true );
		click( mend('database')[0] );
		check( 'C. while the answer is out a second press sends nothing', w.requests.length === 1 );

		const better = clone( SCREEN );

		better.lines[0].state = 'ok';
		better.lines[2].state = 'ok';
		better.whole = true;
		w.answer( 200, better );

		check( 'C. the answer is the screen: the lines are drawn again from it, the buttons are gone where the line is as it should be, and the toast says it was mended once', w.toasts.length === 1 && w.toasts[0] === W('msg/mended') && byClass( w.row('database'), 'nino-admin-badge' )[0].dataset.state === 'ok' && buttons( w.mount ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend') === 0 } ).length === 0 );
		check( 'C. the focus is on the line that was mended, which is drawn again', w.focused() !== null && w.row('database').contains !== undefined && ( w.focused() === w.row('database') || findAll( w.row('database'), function( node ) { return node === w.focused() } ).length === 1 ) );

		const failing = world();

		failing.open();
		click( buttons( failing.row('schema') ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend ') === 0 } )[0] );
		failing.answer( 500, { error : 'x', code : 'crm_setup_step', params : [ 'schema' ] } );

		const hinted = world();

		hinted.open( Object.assign( clone( SCREEN ), { lines : [ { key : 'addresses', hint : true, fixable : true, state : 'invalid', crm : '', app : '' } ] } ) );
		check( 'C. a line that is only a hint and has a fix and is not as it should be has no button either: the owner may leave it as it is', buttons( hinted.mount ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend ') === 0 } ).length === 0 && hinted.rows().length === 1 );

		const notice = byClass( failing.mount, 'nino-admin-notice' );

		check( 'C. a failure is said over the table with the sentence of its code and the line in it - no toast - and the button is there again', failing.toasts.length === 0 && notice.length === 1 && notice[0].textContent.indexOf('schema') !== -1 && buttons( failing.row('schema') ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Mend') === 0 && b.disabled === false } ).length === 1 );
	} );

	// --- D. the data of removed packages -----------------------------------------------------------------------------------------

	block( 'D', function() {
		const w = world();

		w.open( Object.assign( clone( SCREEN ), { orphans : clone( ORPHANS ) } ) );

		const orphanRows = function() { return w.rows().filter( function( row ) { return row.dataset.row === 'crm-gone' || row.dataset.row === 'crm-also' } ) };
		const drop = function( row ) { return buttons( row ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Delete the data of') === 0 } ) };

		check( 'D. where packages left data there is a second table, under a title of its own, a row for each package with its state, the number of its tables and its version', byClass( w.mount, 'nino-admin-directory' ).length === 2 && text( w.mount ).indexOf( W('section/orphans') ) !== -1 && orphanRows().length === 2
			&& byClass( w.row('crm-gone'), 'nino-admin-badge' )[0].textContent === '● '+ W('orphan/removed') && byClass( w.row('crm-also'), 'nino-admin-badge' )[0].textContent === '● '+ W('orphan/removed') && text( w.row('crm-gone') ).indexOf('3') !== -1 );
		check( 'D. the button that deletes the data is at each package that is gone, one at each, and is red', drop( w.row('crm-gone') ).length === 1 && drop( w.row('crm-also') ).length === 1 && String( drop( w.row('crm-gone') )[0].className ).indexOf('danger') !== -1 );

		const opener = drop( w.row('crm-gone') )[0];

		click( opener );

		const dialog = w.dialog();

		check( 'D. the button asks, and sends nothing before the answer: a dialog with the title of the action, the name of the package and the number of its tables in the question, a field for the name to be typed, and the answer that deletes is shut until it is', dialog !== null && w.requests.length === 0 && text( dialog ).indexOf('crm-gone') !== -1 && tag( dialog, 'input' ).length === 1
			&& named( dialog, W('drop/yes') )[0].disabled === true );

		const typed = tag( dialog, 'input' )[0];

		typed.value = 'crm-off';
		fire( typed, 'input', { target : typed } );
		check( 'D. another name typed leaves the answer shut', named( dialog, W('drop/yes') )[0].disabled === true );
		typed.value = 'crm-gone';
		fire( typed, 'input', { target : typed } );
		check( 'D. the name typed opens it', named( dialog, W('drop/yes') )[0].disabled === false );
		click( named( dialog, W('drop/yes') )[0] );
		check( 'D. the answer sends one request with the key of the package and what was typed - the server checks it again', w.requests.length === 1 && w.requests[0].action === 'crm/drop-data' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { key : 'crm-gone', typed : 'crm-gone' } ) );

		const left = clone( SCREEN );

		left.orphans = [ ORPHANS[1] ];
		w.answer( 200, Object.assign( left, { dropped : 3 } ) );
		check( 'D. the answer is the screen without that package, the toast says it was deleted once, and the focus goes to the next row of the data', w.toasts.length === 1 && w.toasts[0] === W('msg/dropped') && w.rows().filter( function( row ) { return row.dataset.row === 'crm-gone' } ).length === 0 && w.focused() !== null && findAll( w.row('crm-also'), function( node ) { return node === w.focused() } ).concat( w.focused() === w.row('crm-also') ? [ 1 ] : [] ).length === 1 );

		const last = world();

		last.open( Object.assign( clone( SCREEN ), { orphans : [ clone( ORPHANS[0] ) ] } ) );
		click( drop( last.row('crm-gone') )[0] );

		const field = tag( last.dialog(), 'input' )[0];

		field.value = 'crm-gone';
		fire( field, 'input', { target : field } );
		click( named( last.dialog(), W('drop/yes') )[0] );
		last.answer( 200, Object.assign( clone( SCREEN ), { dropped : 3 } ) );
		check( 'D. with the last package gone the table is gone, and the focus is on the lines of the status', byClass( last.mount, 'nino-admin-directory' ).length === 1 && last.focused() !== null && findAll( last.mount, function( node ) { return node === last.focused() } ).length === 1 );

		const asked = world();
		const questions = [];

		asked.h.ui.confirm = function( level, options ) { questions.push( [ level, options ] ); options.onChoose( true, 'something else' ) };
		asked.open( Object.assign( clone( SCREEN ), { orphans : [ clone( ORPHANS[0] ) ] } ) );
		click( buttons( asked.row('crm-gone') ).filter( function( b ) { return String( b.attributes['aria-label'] ).indexOf('Delete the data of') === 0 } )[0] );
		check( 'D. the question is of the level destructive, its answer that goes on is red, the name of the package is the word to be typed - and what was typed is what the server is sent, not the name: the server decides whether it was the name',
			questions.length === 1 && questions[0][0] === 'destructive' && questions[0][1].kind === 'danger' && questions[0][1].typed === 'crm-gone' && questions[0][1].title === W('drop/title') && asked.requests.length === 1 && JSON.stringify( asked.requests[0].payload ) === JSON.stringify( { key : 'crm-gone', typed : 'something else' } ) );

		const cancelled = world();

		cancelled.open( Object.assign( clone( SCREEN ), { orphans : [ clone( ORPHANS[0] ) ] } ) );

		const button = drop( cancelled.row('crm-gone') )[0];

		click( button );
		click( named( cancelled.dialog(), words['/_admin/common/label/cancel'] )[0] );
		check( 'D. cancelling sends nothing, closes the dialog and gives the focus back to the button that opened it', cancelled.requests.length === 0 && cancelled.dialog() === null && cancelled.focused() === button );

		const refused = world();

		refused.open( Object.assign( clone( SCREEN ), { orphans : clone( ORPHANS ) } ) );
		click( drop( refused.row('crm-gone') )[0] );

		const refusedField = tag( refused.dialog(), 'input' )[0];

		refusedField.value = 'crm-gone';
		fire( refusedField, 'input', { target : refusedField } );
		click( named( refused.dialog(), W('drop/yes') )[0] );
		refused.answer( 409, { error : 'x', code : 'crm_drop_refused', params : [ 'crm-gone' ] } );
		check( 'D. a refusal of the server is said over the table of the data with its sentence and the name of the package, with no toast, and the row is still there', refused.toasts.length === 0 && byClass( refused.mount, 'nino-admin-notice' ).length === 1 && byClass( refused.mount, 'nino-admin-notice' )[0].textContent.indexOf('crm-gone') !== -1 && refused.row('crm-gone') !== undefined );
	} );

	// --- E. the address ---------------------------------------------------------------------------------------------------------

	block( 'E', function() {
		const cases = [ [ '#crm/crm', '#crm/crm' ], [ '#crm', '#crm' ], [ '#crm/crm/zzz', '#crm' ], [ '#crm/crm/a/b', '#crm' ], [ '#crm/other', '#crm' ], [ '#crm/crm/%zz', '#crm/crm/%zz' ], [ '#crm/crm/@new', '#crm' ], [ '#crm//x', '#crm' ], [ '#crm/crm/', '#crm/crm/' ], [ '', '' ] ];
		const results = cases.map( function( pair ) {

			const w = world( pair[0] );

			w.panel.showCurrent();

			return w.h.hash();
		} );

		check( 'E. #crm and #crm/crm are the screen as it is, and so is what the router reads as no more than that - a slash at the end, a stray percent sign -; an address that says more - a part, a word - or names a tab the panel has not is written over by the one the screen has, #crm', JSON.stringify( results ) === JSON.stringify( cases.map( function( pair ) { return pair[1] } ) ) );

		const other = world( '#members/members/x' );

		other.h.show('members');
		other.panel.showCurrent();
		check( 'E. an address of another panel is left alone: the panel writes only over its own', other.h.hash() === '#members/members/x' );

		const again = world();

		again.open();
		again.h.box.location.hash = '#crm/crm/zzz';
		again.panel.showCurrent();
		check( 'E. showing the panel again asks for the status again, and writes the address over once more where it was changed', again.requests.length === 1 && again.requests[0].action === 'crm/status' && again.h.hash() === '#crm' );
	} );

	// --- F. the data and the actions are apart from the screen -----------------------------------------------------------------

	block( 'F', function() {
		const w = world();
		const model = w.panel.model;

		check( 'F. the badge of a state is data: a sign, a word and a look, with no page', model.badge('ok').state === 'ok' && model.badge('ok').text === '✓ '+ W('state/ok') && model.badge('foreign').state === 'error' && model.badge('default').state === 'muted' && model.badge('behind').state === 'warn' );
		check( 'F. the row of an orphan keeps what the server said and adds the badge', model.orphanOf( ORPHANS[0] ).status.state === 'warn' && model.orphanOf( ORPHANS[0] ).package === 'crm-gone' && model.orphanOf( ORPHANS[1] ).status.text === '● '+ W('orphan/removed') );
		check( 'F. a state the database is in while another process holds it is a warning, a package whose schema could not be raised is a fault, and the line of a package says its package and the version it asked for', model.badge('busy').state === 'warn' && model.badge('unraised').state === 'error'
			&& model.detail( { key : 'schema-crm-x', package : 'crm-x', version : 3, state : 'unraised' } ) === W('detail/package-unraised').replace( '%s', 'crm-x' ).replace( '%s', '3' ) );
		check( 'F. the actions are the two requests of the server, each by its action name, and nothing else', Object.keys( w.panel.actions ).sort().join() === 'drop,mend' );
	} );

	// --- G. every word the script reads is in both languages -------------------------------------------------------------------

	block( 'G', function() {
		const source = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
		const read = new Set();

		source.replace( /\bw\(\s*'([a-z0-9/-]+)'\s*\)/g, function( all, key ) { read.add( '/_admin/crm/'+ key ); return all } );
		source.replace( /'(\/_admin\/[a-z0-9/_-]+)'/g, function( all, key ) { read.add( key ); return all } );

		const states = { database : [ 'ok', 'missing', 'foreign', 'damaged' ], writable : [ 'ok', 'unwritable' ], schema : [ 'ok', 'none', 'behind', 'ahead', 'foreign', 'damaged' ], addresses : [ 'default', 'ok', 'invalid' ], mail : [ 'ok', 'failed', 'stuck' ], 'mail-head' : [ 'ok', 'partial', 'none' ] };

		Object.keys( states ).forEach( function( line ) {
			read.add( '/_admin/crm/line/'+ line );
			states[line].forEach( function( state ) { read.add( '/_admin/crm/state/'+ state ); read.add( '/_admin/crm/detail/'+ line +'-'+ state ) } );
		} );

		[ 'state/error' ].forEach( function( key ) { read.add( '/_admin/crm/'+ key ) } );
		[ 'crm_setup_unknown', 'crm_setup_none', 'crm_setup_step', 'crm_drop_key', 'crm_drop_typed', 'crm_drop_unknown', 'crm_drop_failed', 'crm_drop_refused', 'crm_database' ].forEach( function( code ) { read.add( '/_admin/error/'+ code ) } );

		const wanted = Array.from( read ).filter( function( key ) { return key.indexOf('/_admin/common/') !== 0 && key !== '/_admin/crm/' } ).filter( function( key ) { return key.indexOf('//') === -1 } );
		const missing = wanted.filter( function( key ) { return words[key] === undefined || words[key] === '' || german[key] === undefined || german[key] === '' } );

		if( missing.length > 0 )
			console.log( '  missing: '+ missing.join() );

		check( 'G. every word the script reads - the lines of the core, each state of each, the codes of the failures a person can cause - is in both languages of the panel', missing.length === 0 && wanted.length > 60 );
		check( 'G. the two languages have the same keys and the same number of places in every sentence', Object.keys( words ).filter( function( key ) { return key.indexOf('/_admin/crm/') === 0 || key.indexOf('/_admin/error/crm_') === 0 } ).every( function( key ) { return german[key] !== undefined && ( words[key].match( /%s/g ) ?? [] ).length === ( german[key].match( /%s/g ) ?? [] ).length } )
			&& Object.keys( german ).filter( function( key ) { return key.indexOf('/_admin/crm/') === 0 || key.indexOf('/_admin/error/crm_') === 0 } ).every( function( key ) { return words[key] !== undefined } ) );
		check( 'G. the script writes no sentence of its own, no address by hand, no button, dialog or browser question, and posts nothing by itself', /createElement\(\s*.button|window\.confirm|\balert\(|(?<![\w.])confirm\(|\bprompt\(|'#'\s*\+|location\.hash\s*=|XMLHttpRequest|fetch\(/.test( source ) === false && /Nino\.adminUi\.api\.call\(/.test( source ) === true );
	} );

	done();
} )();
