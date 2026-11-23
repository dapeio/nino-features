/**
 *	Nino features
 *	forms-js-smoke.js	What the script of the panel does over the kernel's stand-in of a page and the real Nino.adminUi, a few pins:
 *
 *											A. the directory: a row for each form, a name is text and never markup, New is an address
 *											B. a form: the three tabs, one main button, a row for each field with the cluster that moves and removes it
 *											C. an order and a removal are the draft's, and what is saved is the draft in that order, with the words of each language
 *											D. unsaved input: the form is dirty as soon as a control or a row changed, and not before
 *											E. a refusal of the server is said at the row and at the control it is about, on the tab that has it
 *
 *											The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js of the
 *											checkout, three levels up or the one NINO_ROOT names. forms-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Forms/tests/forms-js-smoke.js
 *	       NINO_ROOT=../nino node features/Forms/tests/forms-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, documentOf, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const words = fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) );
const workbench = fills('_admin/text/en_US.php');
const W = function( key ) { return words['/_admin/forms/'+ key] };

const FORMS = [
	{ key : 'contact', name : 'Contact', to : '', subject : '', subjectTexts : { de_DE : '', en_US : '' }, confirm : true, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 2,
		fields : [
			{ name : 'name', label : 'Name', type : 'text', required : true, options : [], texts : { de_DE : 'Name', en_US : 'Name' } },
			{ name : 'plan', label : 'Plan', type : 'select', required : false, options : [ 'S', 'L' ], texts : { de_DE : 'Plan', en_US : 'Plan' } },
			{ name : 'message', label : 'Message', type : 'textarea', required : true, options : [], texts : { de_DE : 'Nachricht', en_US : 'Message' } },
		] },
	{ key : 'evil', name : '<img src=x onerror=alert(1)>', to : '', subject : '', subjectTexts : { de_DE : '', en_US : '' }, confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0,
		fields : [ { name : 'x', label : '<b>x</b>', type : 'text', required : false, options : [], texts : { de_DE : '<b>x</b>', en_US : '<b>x</b>' } } ] },
];

const LIST = {
	forms : FORMS, types : [ 'text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'date' ], reserved : [ 'form', 'location', '_csrf', '_t', 'id', 'date', 'ip' ],
	templates : [ '/templates/mail-owner', '/templates/mail-user' ], locales : [ 'de_DE', 'en_US' ], default : false, retention : 3, store : true, endpoint : true, guards : { minSeconds : 3, rateLimit : 10, blocked : 0 },
};

/** The script in a page of its own: the workbench's ui, a router and a registry of dirty forms that keep what they are asked, the requests held until a test answers them */
function world( extra ) {

	const page = element('div');
	const list = element('div');
	const form = element('div');
	const requests = [];
	const toasts = [];
	const state = { hash : '' };

	list.id = 'forms-list';
	form.id = 'forms-form';
	page.appendChild( list );
	page.appendChild( form );

	const doc = documentOf( page );

	doc.documentElement = { lang : 'en-US' };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) { return element( tag ) };

	const entries = {};
	const dirty = {
		entries : entries,
		register : function( name, entry ) { entries[name] = entry },
		isDirty : function( names ) { return ( names ?? Object.keys( entries ) ).some( function( name ) { return entries[name] !== undefined && entries[name].isDirty() === true } ) },
		refresh : function() {},
		guard : function( names, proceed ) { proceed() },
	};

	// The router of the workbench itself, over the panes this panel has: what the panel writes is read back by it
	const codec = shellRouter( { forms : [ [ 'forms', 'forms' ], [ 'forms-settings', 'settings' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		isActive : function() { return true },
		go : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
	} );

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return words[key] ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } },
		admin : { router : router, dirty : dirty, workLocale : { current : 'en_US', native : 'en_US' } },
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, CSS : { escape : function( value ) { return String( value ) } } };

	sandbox.window = { Nino : Nino, location : { hash : '', href : 'http://workbench.test/_admin/' }, setTimeout : function() { return 0 }, clearTimeout : function() {} };

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	Nino.adminUi.say = function( text ) { toasts.push( text ) };
	load( relative, context );

	const w = {
		Nino : Nino, list : list, form : form, requests : requests, toasts : toasts, state : state, dirty : dirty, panel : Nino.admin.forms,
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		open : function() {
			w.panel.showCurrent();
			w.answer( 200, Object.assign( JSON.parse( JSON.stringify( LIST ) ), extra ?? {} ) );
		},
		go : function( parts ) {
			state.hash = router.href( 'forms', parts );
			w.panel.showCurrent();
		},
		at : function( hash ) {
			state.hash = hash;
			w.panel.showCurrent();
		},
		codec : codec,
	};

	return w;
}

const text = function( root ) { return findAll( root, function() { return true } ).map( function( node ) { return node.textContent } ).join('\n') };
// The rows of the list of fields: a list item that stands for a place in the form, not a row of the tab that says what protects it
const rowsOf = function( root ) { return findAll( root, function( node ) { return node.tagName === 'LI' && /^[0-9]+$/.test( String( node.dataset.row ) ) } ) };
const Nino_text = function( word ) { return workbench['/_admin/common/label/'+ word] };
const buttons = function( root ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' } ) };
const named = function( root, name ) { return buttons( root ).filter( function( button ) { return button.getAttribute('aria-label') === name || button.textContent === name } ) };

// --- A. the directory -----------------------------------------------------------------------------------------------

{
	const w = world();

	w.open();

	const rows = findAll( w.list, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } );

	check( 'A. the directory has a row for each form, by its key', rows.map( function( row ) { return row.dataset.row } ).join() === 'contact,evil' );
	check( 'A. a name is text and never markup: nothing of the name that spells an element is one', findAll( w.list, function( node ) { return node.tagName === 'IMG' } ).length === 0 && text( w.list ).indexOf( '<img src=x onerror=alert(1)>' ) !== -1 );

	named( w.list, W('label/new') )[0].click();

	check( 'A. New form is an address of its own and shows a form with no field yet, which is said', w.state.hash === '#forms/forms/@new/@fields' && rowsOf( w.form ).length === 0 && text( w.form ).indexOf( W('empty/fields') ) !== -1 );
}

{
	const w = world();

	w.open();
	w.go( [ w.codec.word('new'), w.codec.word('delivery') ] );

	check( 'A. the address of a new form keeps its tab: opened again at #forms/forms/@new/@delivery it is the new form at that tab, not the directory', w.state.hash === '#forms/forms/@new/@delivery' && findAll( w.form, function( node ) { return node.tagName === 'FORM' } ).length === 1 );

	const kernelForm = world( { default : true } );

	kernelForm.open();

	check( 'A. the form the kernel falls back to has no Delete in the directory, where it could only be refused', named( kernelForm.list, W('label/edit') ).length === 2 && named( kernelForm.list, Nino_text( 'delete' ) ).length === 0 );
}

// --- B. a form ------------------------------------------------------------------------------------------------------

const opened = function() {

	const w = world();

	w.open();
	w.go( [ 'contact', w.codec.word('fields') ] );

	return w;
};

{
	const w = opened();
	const tabs = findAll( w.form, function( node ) { return node.getAttribute('role') === 'tab' } );
	const rows = rowsOf( w.form );
	const main = buttons( w.form ).filter( function( button ) { return button.getAttribute('type') === 'submit' || button.type === 'submit' } );

	check( 'B. a form has the tabs Fields, Delivery and Protection, and its address says the tab', tabs.map( function( tab ) { return tab.textContent } ).join() === [ W('tab/fields'), W('tab/delivery'), W('tab/protection') ].join() && w.state.hash === '#forms/forms/contact/@fields' );
	check( 'B. one main button, in the bar; the form is one that the script of the kernel does not check for us: novalidate', main.length === 1 && findAll( w.form, function( node ) { return node.tagName === 'FORM' } )[0].noValidate === true );
	check( 'B. the cluster is a child of the row of the list and no part of the part that opens the field: a press on it would else land on the overlay that opens the dialog', rows.every( function( row ) {
		const cluster = findAll( row, function( node ) { return node.className === 'nino-admin-move' } )[0];
		return cluster.parentNode === row && findAll( row, function( node ) { return String( node.className ).indexOf('nino-admin-row-open') !== -1 && node.contains( cluster ) } ).length === 0;
	} ) );
	check( 'B. a field with one option says "1 option", with two "2 options"', text( rows[1] ).indexOf( W('label/option-count').replace( '%s', '2' ) ) !== -1 && text( rows[1] ).indexOf( W('label/option-one') ) === -1 && ( function() {
		const forms = JSON.parse( JSON.stringify( FORMS ) );
		const again = world( { forms : forms } );

		forms[0].fields[1].options = [ 'S' ];
		again.open();
		again.go( [ 'contact', again.codec.word('fields') ] );

		const row = text( rowsOf( again.form )[1] );

		return row.indexOf( W('label/option-one') ) !== -1 && row.indexOf( W('label/option-count').replace( '%s', '1' ) ) === -1;
	} )() );
	check( 'B. a row for each field with the cluster that moves it up and down and removes it; the first cannot go up and the last cannot go down', rows.length === 3
		&& findAll( rows[0], function( node ) { return node.className === 'nino-admin-move' } ).length === 1 && buttons( rows[0] ).filter( function( b ) { return b.disabled === true } ).length === 1 && buttons( rows[2] ).filter( function( b ) { return b.disabled === true } ).length === 1
		&& named( rows[1], W('label/options') ).length === 1 && named( rows[0], W('label/options') ).length === 0 );
}

{
	const w = opened();

	check( 'B. the way to the settings is a link, not a button', findAll( w.form, function( node ) { return node.tagName === 'A' && node.href === '#forms/settings' } ).length === 1 && buttons( w.form ).filter( function( button ) { return button.textContent === workbench['/_admin/features/tab/settings'] || button.textContent === 'Settings' } ).length === 0 );
}

// --- A2. the address says a word or a key: a form may be called new ------------------------------------------------------------

{
	const forms = JSON.parse( JSON.stringify( FORMS ) );
	const called = JSON.parse( JSON.stringify( FORMS[0] ) );

	called.key = 'new';
	called.name = 'The form called new';
	forms.push( called );

	const w = world( { forms : forms } );

	w.open();

	const draft = function() { return findAll( w.form, function( node ) { return node.tagName === 'INPUT' && node.dataset.key === 'name' } )[0].value };

	w.at( '#forms/forms/@new/@fields' );
	check( 'A2. the word @new is the form that makes one, whatever forms there are: it starts empty', draft() === '' && w.state.hash === '#forms/forms/@new/@fields' );

	w.at( '#forms/forms/new/@fields' );
	check( 'A2. the key new is the form of that key, a form that is there: it holds its name, and the address stays the key', draft() === 'The form called new' && w.state.hash === '#forms/forms/new/@fields' );

	w.at( '#forms/forms/%40new/@fields' );
	check( 'A2. the key @new, which no form has, is the directory - and the address says so', w.state.hash === '#forms' );

	w.at( '#forms/forms/new/protection' );
	check( 'A2. a tab that is no word is the first tab: protection without its at sign is no tab', w.state.hash === '#forms/forms/new/@fields' );

	check( 'A2. every address the panel wrote or drew was read back by the router as the same screen and the same parts', w.codec.written.length > 3 && w.codec.mismatches.length === 0
		&& w.codec.written.some( written => written.parts.length === 2 && typeof written.parts[0] === 'object' ) && w.codec.written.some( written => written.parts[0] === 'new' ) );
}

// --- C. an order, a removal, what is saved -----------------------------------------------------------------------

{
	const w = opened();

	named( w.form, 'Move Name down' )[0].click();
	named( w.form, 'Remove Message' )[0].click();

	const names = rowsOf( w.form ).map( function( row ) { return row.dataset.row } );

	check( 'C. a move and a removal change the rows and nothing else: two rows are left, in the new order', names.length === 2 && w.requests.length === 0 );

	findAll( w.form, function( node ) { return node.tagName === 'FORM' } )[0].listeners.submit[0]( { preventDefault : function() {} } );

	const sent = w.requests[0];

	check( 'C. the save sends the whole form in the order of the rows, once, with the key it had and the words of each language of every field',
		sent.action === 'forms/save' && sent.payload.key === 'contact' && sent.payload.form.fields.map( function( field ) { return field.name } ).join() === 'plan,name'
		&& sent.payload.texts.fields[1].de_DE === 'Name' && sent.payload.texts.fields[0].en_US === 'Plan' && sent.payload.form.fields[0].options.join() === 'S,L' );

	// The bar of the form says "saved" by itself, as a toast: the answer of the server is one sentence, not two
	w.answer( 200, { form : { key : 'contact' } } );
	w.answer( 200, JSON.parse( JSON.stringify( LIST ) ) );

	check( 'C. a saved form says so once: the bar of the form says it, and the panel adds nothing', w.requests.length === 0 && w.toasts.length === 1 );
}

// --- D. unsaved input ---------------------------------------------------------------------------------------------------

{
	const w = opened();
	const entry = w.dirty.entries.forms;

	check( 'D. a form that was just opened is not dirty; typing in a control makes it dirty and taking it back makes it clean again', ( function() {
		const before = entry.isDirty();
		const input = findAll( w.form, function( node ) { return node.tagName === 'INPUT' && node.dataset.key === 'name' } )[0];
		const was = input.value;

		input.value = was+ ' x';
		const typed = entry.isDirty();
		input.value = was;

		return before === false && typed === true && entry.isDirty() === false;
	} )() );

	named( w.form, 'Move Name down' )[0].click();

	check( 'D. a move of a row is unsaved input too', entry.isDirty() === true );
}

// --- E. a refusal ------------------------------------------------------------------------------------------------------------

{
	const w = opened();

	findAll( w.form, function( node ) { return node.tagName === 'FORM' } )[0].listeners.submit[0]( { preventDefault : function() {} } );
	w.answer( 400, { error : 'x', code : 'forms_fields', params : [ { 1 : 'options' }, { 1 : 'options' }, { to : 'to' } ] } );

	check( 'E. a refusal is said at the row of the field and at the control of the form it names, and the screen goes to the tab that has the first of them',
		text( w.form ).indexOf( words['/_admin/forms/problem/options'] ) !== -1 && text( w.form ).indexOf( words['/_admin/forms/problem/to'] ) !== -1 && w.toasts.length === 0 );
}

done();
