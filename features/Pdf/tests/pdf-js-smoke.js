/**
 *	Nino features
 *	pdf-js-smoke.js	What the script of the panel does over the kernel's stand-in of a page and the real Nino.adminUi, a few pins:
 *
 *										A. the layout: one form of fieldsets, one main button, the pictures at once and no part of the form, what is saved is what the controls hold
 *										B. a refusal of the server is said at the field it names, in the words of the panel, and the focus goes there
 *										C. the text blocks: a directory by key, a name is text and never markup, a new block takes its key from its title, a saved key stays
 *										D. the templates: a directory with the tag that is gone marked, a template on its own screen, the order and the removal of a block are the draft's
 *										E. what is saved of a template is the draft in its order, in the shape the server takes, and unsaved input is reported
 *										F. the preview is asked for what the form holds and shows the PDF in a frame with a link to download it
 *
 *										The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js of the
 *										checkout, three levels up or the one NINO_ROOT names. pdf-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Pdf/tests/pdf-js-smoke.js
 *	       NINO_ROOT=../nino node features/Pdf/tests/pdf-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, Buffer */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, adminPage } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const words = fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) );
const workbench = fills('_admin/text/en_US.php');
const W = function( key ) { return words['/_admin/pdf/'+ key] };

const OFFER = { tags : [ 'offer', 'invoice' ], library : [ { key : 'intro', title : 'Intro' } ], paper : [ 210, 297 ], ranges : { title : 120, html : 20000, label : 120, blocks : 60 } };

const LAYOUT = {
	layout : { format : 'a4', margins : [ 20, 22, 18, 24 ], font : 'helvetica', size : 10, header : '<p>Head</p>', footer : '', design : '', logo : 'logo.png', signature : '' },
	sheet : 'body { font-size: 10pt }', refusal : null, images : { logo : 'data:image/png;base64,AAAA', signature : '' }, papers : [ 'a4', 'letter' ], fonts : [ 'helvetica', 'times', 'courier' ],
	ranges : { margin : [ 5, 50 ], size : [ 8, 14 ], band : 4000, design : 8000 }, owned : { fixture : '<b>Fixture</b>' },
};

const BLOCKS = { blocks : [ { key : 'intro', title : 'Intro', html : '<p>Hello [[name]]</p>' }, { key : 'evil', title : '<img src=x onerror=alert(1)>', html : '<p>x</p>' } ], ranges : { title : 120, html : 20000 } };

const TEMPLATES = Object.assign( { templates : [
	{ key : 'offer-one', name : 'Offer one', tag : 'offer', blocks : 3, known : true },
	{ key : 'old', name : 'Old', tag : 'retired', blocks : 0, known : false },
] }, OFFER );

const SIGNATURE = { type : 'signature', name : 'seller', party : 'owner', label : 'The seller', page : 'last', x : 20, y : 200, w : 60, h : 20, line : true };

const TEMPLATE = Object.assign( { key : 'offer-one', template : { name : 'Offer one', tag : 'offer', blocks : [ { type : 'html', block : 'intro' }, { type : 'html', html : '<p>Own</p>' }, SIGNATURE ] } }, OFFER );

const source = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
const Nino_word = function( key, name ) { return W( key ).replace( '%s', name ) };
const copy = function( value ) { return JSON.parse( JSON.stringify( value ) ) };

/** The script in the workbench of a page of its own: the real router and the real registry of unsaved input over one document, whose dialogs open and close the way a browser's do - the requests held until a test answers them */
function world() {

	const h = adminPage( { pdf : [ [ 'pdf', 'pdf' ], [ 'pdf-pictures', 'pictures' ], [ 'pdf-blocks', 'blocks' ], [ 'pdf-templates', 'templates' ], [ 'pdf-settings', 'settings' ] ] } );
	const mounts = {};
	const requests = [];
	const toasts = [];

	[ 'pdf-layout', 'pdf-pictures', 'pdf-blocks-list', 'pdf-templates-list', 'pdf-templates-edit' ].forEach( function( id ) {
		mounts[id] = element('div');
		mounts[id].id = id;
		h.page.appendChild( mounts[id] );
	} );

	// The panes the shell would draw: an element each, for what the registry looks for in them (the bar of a screen)
	const panes = {};
	const known = h.doc.getElementById;

	h.doc.getElementById = function( id ) { return /^admin-(content|tab)-/.test( id ) === true ? ( panes[id] = panes[id] ?? element('div') ) : known( id ) };

	h.Nino.slug = function( text, max ) { return String( text ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, max ) };
	h.Nino.content.getText = function( key ) { return words[key] ?? workbench[key] ?? '' };
	h.Nino.http = { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), file : data.file, callback : callback } ) } };
	h.box.URL = URL;
	h.box.Blob = Blob;
	h.box.atob = atob;
	h.box.Uint8Array = Uint8Array;
	h.box.CSS = { escape : function( value ) { return String( value ) } };
	h.ui.say = function( text ) { toasts.push( text ) };
	Object.defineProperty( h.dirty, 'entries', { get : function() { return h.dirty._entries }, configurable : true } );
	h.router.start();

	load( relative, h.context );

	const w = {
		h : h, Nino : h.Nino, mounts : mounts, requests : requests, toasts : toasts, doc : h.doc, dirty : h.dirty,
		state : { get hash() { return h.hash() } },
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		// A tab asked for its screen: the request and its answer
		open : function( uri, body ) {
			h.show( uri );
			h.router.set( uri, [] );
			h.admin[uri].showCurrent();
			w.answer( 200, copy( body ) );
		},
		// The address a person moved to - a word is written @word - and the panel that follows it
		go : function( uri, parts, body ) {
			h.show( uri );
			h.router.go( uri, parts.map( function( part ) { return typeof part === 'string' && /^@[a-z]+$/.test( part ) === true ? h.router.word( part.slice( 1 ) ) : part } ) );
			h.admin[uri].showCurrent();

			if( body !== undefined )
				w.answer( 200, copy( body ) );
		},
		// The question or dialog that is open on top, and the answer to it by the name of a button
		dialog : function() { return h.dialogs.filter( function( one ) { return one.open } ).pop() ?? null },
		press : function( label ) {
			const button = buttons( w.dialog() ).find( function( one ) { return one.textContent.trim() === label || one.getAttribute('aria-label') === label } );
			fire( button, 'click' );
			return button !== undefined;
		},
	};

	return w;
}

const text = function( root ) { return findAll( root, function() { return true } ).map( function( node ) { return node.textContent } ).join('\n') };
const buttons = function( root ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' } ) };
const named = function( root, name ) { return buttons( root ).filter( function( button ) { return button.getAttribute('aria-label') === name || button.textContent === name } ) };
const submitOf = function( root ) { return findAll( root, function( node ) { return node.tagName === 'FORM' } )[0] };
const send = function( root ) { submitOf( root ).listeners.submit[0]( { preventDefault : function() {} } ) };
const field = function( root, key ) { return findAll( root, function( node ) { return ( node.tagName === 'INPUT' || node.tagName === 'SELECT' || node.tagName === 'TEXTAREA' ) && node.dataset.key === key } )[0] };

// --- A. the layout ---------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open( 'pdf', LAYOUT );

	const mount = w.mounts['pdf-layout'];
	const main = buttons( mount ).filter( function( button ) { return button.type === 'submit' || button.getAttribute('type') === 'submit' } );

	check( 'A. the layout is one form, with novalidate, a fieldset for the page, the margins, the header and the footer and the design - no picture, no file field - and one main button in the bar',
		submitOf( mount ).noValidate === true && findAll( mount, function( node ) { return node.tagName === 'FIELDSET' } ).length === 4 && main.length === 1 );
	check( 'A. a font of the owner\'s folder is a choice by its name, which is text and never markup', findAll( mount, function( node ) { return node.tagName === 'IMG' } ).filter( function( node ) { return node.className !== 'pdf-picture-image' } ).length === 0
		&& text( mount ).indexOf( '<b>Fixture</b>' ) !== -1 );
	check( 'A. the rules of the feature are shown read-only, and the design the owner writes is not', field( mount, 'sheet' ).readOnly === true && field( mount, 'design' ).readOnly !== true );

	field( mount, 'size' ).value = '12';
	field( mount, 'margin-left' ).value = '30';
	field( mount, 'header' ).value = '<p>New head</p>';
	send( mount );

	const sent = w.requests[0];

	check( 'A. the save sends what the controls hold - whole numbers as numbers, the four margins in the order top, right, bottom, left - to the action of the layout',
		sent.action === 'pdf/layout/save' && sent.payload.size === 12 && sent.payload.margins.join() === '20,22,18,30' && sent.payload.format === 'a4' && sent.payload.header === '<p>New head</p>' && sent.payload.design === '' );

	const stored = copy( LAYOUT );

	stored.layout.header = '<p>Cleaned</p>';
	stored.layout.size = 12;
	w.answer( 200, stored );

	check( 'A. what the server stored of the texts is what the form shows from then on, and the form is clean: a toast says it, the line of the bar says nothing', field( mount, 'header' ).value === '<p>Cleaned</p>'
		&& w.toasts.length === 1 && w.dirty.entries.pdf.isDirty() === false );
}

{
	const w = world();

	w.open( 'pdf', LAYOUT );

	const mount = w.mounts['pdf-layout'];
	const entry = w.dirty.entries.pdf;
	const input = field( mount, 'size' );

	check( 'A. a layout that was just opened is not dirty; a change of a control makes it dirty and taking it back makes it clean', ( function() {
		const before = entry.isDirty();

		input.value = '11';
		const typed = entry.isDirty();
		input.value = '10';

		return before === false && typed === true && entry.isDirty() === false;
	} )() );
}

{
	const w = world();

	w.open( 'pdf', LAYOUT );

	check( 'A. the layout holds no file field: the pictures are a tab of their own', findAll( w.mounts['pdf-layout'], function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } ).length === 0 );

	// The layout that is typed in and not saved stays when the tab is left and shown again, and the shell is asked before the panel is left
	field( w.mounts['pdf-layout'], 'size' ).value = '13';
	w.dirty.refresh = function() {};
	w.Nino.admin.pdf.showCurrent();

	check( 'A. a layout with input nobody saved is not drawn again when its tab is shown: nothing is asked for and what was typed is still there, still unsaved', w.requests.length === 0 && field( w.mounts['pdf-layout'], 'size' ).value === '13' && w.dirty.entries.pdf.isDirty() === true );
}

// --- A2. the pictures -----------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open( 'pdf-pictures', LAYOUT );

	const mount = w.mounts['pdf-pictures'];
	const files = findAll( mount, function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } );
	const own = function( node ) { for( let up = node.parentNode; up; up = up.parentNode ) if( String( up.className ).indexOf('nino-admin-filefield') !== -1 ) return true; return false };
	const removes = function() { return named( mount, workbench['/_admin/common/label/remove'] ).filter( function( button ) { return own( button ) === false } ) };

	check( 'A2. the logo and the signature are each a file field with no form around them and no Save, and only the picture that exists has its Remove', files.length === 2 && removes().length === 1
		&& findAll( mount, function( node ) { return node.tagName === 'FORM' } ).length === 0 && named( mount, workbench['/_admin/common/label/save'] ).length === 0 );

	removes()[0].click();

	check( 'A2. taking a picture away is asked first, in the words of the panel, with the name of the picture - a question that opens on Cancel - and nothing is sent by the press itself', w.requests.length === 0 && w.dialog() !== null && text( w.dialog() ).indexOf( W('label/logo') ) !== -1 );

	w.press( workbench['/_admin/common/label/cancel'] );

	check( 'A2. a no sends nothing and closes the question', w.requests.length === 0 && w.dialog() === null );

	removes()[0].click();
	w.press( W('confirm/picture-title') );

	check( 'A2. a yes sends the removal of that picture and says it with the word for what was done, not the word of a save', w.requests.length === 1 && w.requests[0].action === 'pdf/layout/image/delete' && w.requests[0].payload.kind === 'logo' );

	const gone = copy( LAYOUT );

	gone.layout.logo = '';
	gone.images.logo = '';
	w.answer( 200, gone );

	check( 'A2. ... after it the toast says "deleted" and the focus is on the choice of the logo, not on the page', w.toasts.join() === workbench['/_admin/common/msg/deleted'] && findAll( mount, function( node ) { return node.focused === true && node.tagName === 'BUTTON' } ).length === 1 );

	// A chosen file is checked in the browser first, then sent with its kind
	const checked = [];

	w.Nino.adminUi.checkImage = function( file, then ) { checked.push( file ); then( null ) };

	const input = findAll( mount, function( node ) { return node.tagName === 'INPUT' && node.type === 'file' && node.dataset.key === 'signature' } )[0];
	const file = { name : 'sig.png', size : 10, type : 'image/png' };

	input.files = [ file ];
	( input.listeners.change || [] ).forEach( function( listener ) { listener( { target : input } ) } );

	check( 'A2. a chosen file goes through the browser\'s check of a picture before it is sent, and is sent with its kind and nothing else', checked.length === 1 && checked[0] === file && w.requests.length === 1 && w.requests[0].action === 'pdf/layout/image'
		&& w.requests[0].payload.kind === 'signature' && w.requests[0].file === file );

	const put = copy( LAYOUT );

	put.layout.signature = 'signature.png';
	put.images.signature = 'data:image/png;base64,AAAA';
	w.answer( 200, put );

	check( 'A2. a picture that was put in is said as saved, and the focus is on the choice of that picture', w.toasts.pop() === workbench['/_admin/common/msg/saved'] && findAll( mount, function( node ) { return node.focused === true && node.tagName === 'BUTTON' } ).length === 1 );
}

// --- B. a refusal ------------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open( 'pdf', LAYOUT );

	const mount = w.mounts['pdf-layout'];

	send( mount );
	w.answer( 400, { error : 'x', code : 'pdf_size', params : [ 8, 14 ], field : 'size' } );

	const sentence = words['/_admin/error/pdf_size'].replace( '%s', '8' ).replace( '%s', '14' );
	const wrong = findAll( mount, function( node ) { return node.getAttribute('aria-invalid') === 'true' } );

	check( 'B. a refusal is said at the field the server names, in the sentence of the panel for its code, with its params', text( mount ).split( sentence ).length >= 2 && wrong.length === 1 && wrong[0].dataset.key === 'size' );
	check( 'B. a refused save leaves the form dirty and says no success', w.toasts.length === 0 && w.dirty.entries.pdf.isDirty() === false );
}

// --- C. the text blocks: a directory, and a record for each ----------------------------------------------------------------

const press = function( node ) { fire( node, 'click', { currentTarget : node } ) };
const rowsOf = function( mount ) { return findAll( mount, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) };
const keysOf = function( mount ) { return rowsOf( mount ).map( function( row ) { return row.dataset.row } ).join() };
const search = function( mount ) { return findAll( mount, function( node ) { return node.tagName === 'INPUT' && node.type === 'search' } )[0] };
const choose = function( row ) { const box = findAll( row, function( node ) { return node.tagName === 'INPUT' } )[0]; box.checked = true; fire( box, 'click' ) };
const closeRecord = function( w ) { fire( buttons( w.dialog() ).find( function( one ) { return one.getAttribute('aria-label') === workbench['/_admin/common/label/close'] } ), 'click' ) };

{
	const w = world();

	w.open( 'pdf-blocks', BLOCKS );

	const list = w.mounts['pdf-blocks-list'];

	check( 'C. the library is the standard directory with a row for each block, by its key, and the sentence of loading is gone', keysOf( list ) === 'intro,evil' && findAll( list, function( node ) { return String( node.className ).indexOf('nino-admin-loading') !== -1 } ).length === 0 );
	check( 'C. a title is text and never markup: nothing of the title that spells an element is one', findAll( list, function( node ) { return node.tagName === 'IMG' } ).length === 0 && text( list ).indexOf( '<img src=x onerror=alert(1)>' ) !== -1 );
	check( 'C. a number of characters stands against the limit of the text, and the box in front of a row chooses it', text( list ).indexOf( '21 / 20000' ) !== -1 && findAll( rowsOf( list )[0], function( node ) { return node.tagName === 'INPUT' } ).length === 1 );
	check( 'C. each row has the buttons Edit and Delete, named by what they do and for what - the first is what the keyboard opens the row by, the one that takes away is last', ( function() {
		const names = buttons( rowsOf( list )[0] ).map( function( button ) { return button.getAttribute('aria-label') } );
		return names.join() === [ Nino_word( 'label/edit-block', 'Intro' ), Nino_word( 'label/delete-one', 'Intro' ) ].join();
	} )() );
	check( 'C. the four words of the sort select of a card are the panel\'s own: the label, the order the rows come in, and both directions with the name of the column', ( function() {
		const select = findAll( list, function( node ) { return node.tagName === 'SELECT' && String( node.className ).indexOf( 'nino-admin-table-sortby' ) !== -1 } )[0];
		const given = [ 'sort-by', 'sort-none', 'sort-up', 'sort-down' ].every( function( word ) { return source.indexOf( "w('label/"+ word +"')" ) !== -1 } );
		const options = select === undefined ? [] : findAll( select, function( node ) { return node.tagName === 'OPTION' } ).map( function( node ) { return node.textContent } );
		return given === true && options[0] === W('label/sort-none') && options.indexOf( W('label/sort-up').replace( '%s', W('label/title') ) ) !== -1 && options.indexOf( W('label/sort-down').replace( '%s', W('label/title') ) ) !== -1;
	} )() );
	check( 'C. both directories name their name column as the primary one of a card, in the source of the script', ( source.match( /primary : true/g ) || [] ).length === 2 );
	check( 'C. what a row is in a card is said by the columns: the title is the name - its card role is primary - and the key the line under it, the characters a line with its label; the cell of the box stays out of it', ( function() {
		const row = rowsOf( list )[0];
		const cells = findAll( row, function( node ) { return node.dataset !== undefined && node.dataset.card !== undefined } );
		const roles = cells.map( function( node ) { return node.dataset.card + ':'+ text( node ).replace( /[\n\u203a]/g, '' ) } );
		return roles.indexOf( 'primary:Intro' ) !== -1 && roles.indexOf( 'second:intro' ) !== -1 && roles.some( function( role ) { return /^line:21 \/ 20000$/.test( role ) } ) && cells.filter( function( node ) { return node.dataset.label === W('label/chars') } ).length === 1;
	} )() );

	fire( rowsOf( list )[0], 'click' );

	const record = w.dialog();

	check( 'C. a row opens the record, its address is the key - #pdf/blocks/intro - one entry of the history, and the title of the record is the title of the block', record !== null && w.state.hash === '#pdf/blocks/intro' && text( record ).indexOf( 'Intro' ) !== -1 );
	check( 'C. the record holds the fields of the guide, the key of a saved block cannot be changed, the bar has Delete at the left, Save, Save and continue, and no Cancel and no Close', field( record, 'title' ).value === 'Intro' && field( record, 'key' ).disabled === true && field( record, 'html' ).value === '<p>Hello [[name]]</p>'
		&& buttons( record ).filter( function( button ) { return button.textContent === W('label/delete-block') } ).length === 1 && buttons( record ).filter( function( button ) { return /^(Cancel|Close)$/.test( button.textContent ) } ).length === 0 );

	check( 'C. the bar of the record has Save and continue and the steps to the previous and the next block, named by the words of the workbench', buttons( record ).some( function( button ) { return button.textContent === workbench['/_admin/common/label/save-next'] } )
		&& named( record, workbench['/_admin/common/record/prev'] ).length === 1 && named( record, workbench['/_admin/common/record/next'] ).length === 1 );

	field( record, 'title' ).value = 'Intro two';
	fire( buttons( record ).find( function( button ) { return button.textContent === workbench['/_admin/common/label/save'] } ), 'click' );

	const sent = w.requests[0];

	check( 'C. Save sends the key it had, isNew false, the title and the text, by the action of the library', sent.action === 'pdf/blocks/save' && sent.payload.key === 'intro' && sent.payload.isNew === false && sent.payload.title === 'Intro two' && sent.payload.html === '<p>Hello [[name]]</p>' );

	const answer = copy( BLOCKS );

	answer.blocks[0].title = 'Intro two';
	answer.blocks[0].html = '<p>Cleaned</p>';
	w.answer( 200, answer );

	check( 'C. what the server stored is what the record shows and the row of the directory is drawn anew with the title it was saved with; the record stays open', field( w.dialog(), 'html' ).value === '<p>Cleaned</p>' && text( rowsOf( list )[0] ).indexOf( 'Intro two' ) !== -1 && w.dialog() !== null );

	w.press( W('label/delete-block') );
	check( 'C. Delete asks first - a question on the record, with the name - and sends nothing for a Cancel', w.dialog() !== null && text( w.dialog() ).indexOf( 'Intro two' ) !== -1 && w.requests.length === 0 );
	w.press( workbench['/_admin/common/label/cancel'] );
	w.press( W('label/delete-block') );
	w.press( W('confirm/block-title') );

	check( 'C. a yes sends the deletion of that block by its key', w.requests.length === 1 && w.requests[0].action === 'pdf/blocks/delete' && w.requests[0].payload.key === 'intro' );

	const left = copy( BLOCKS );

	left.blocks.shift();
	w.answer( 200, left );

	check( 'C. after it the record is closed, the row is out of the directory, the toast says "deleted", and the focus is on the row that is left', keysOf( list ) === 'evil' && w.dialog() === null && w.toasts.pop() === workbench['/_admin/common/msg/deleted']
		&& findAll( list, function( node ) { return node.focused === true } ).length === 1 );
}

{
	// A reload with the address of a block: the directory is drawn and the record opens once the list has come
	const w = world();

	w.h.show('pdf-blocks');
	w.h.router.go( 'pdf-blocks', [ 'intro' ] );
	w.h.admin['pdf-blocks'].showCurrent();

	check( 'C. an address that names a block opens nothing before the list is there', w.dialog() === null && w.requests.length === 1 );

	w.answer( 200, copy( BLOCKS ) );

	check( 'C. ... the list is drawn and the record is the block the address names', keysOf( w.mounts['pdf-blocks-list'] ) === 'intro,evil' && w.dialog() !== null && field( w.dialog(), 'title' ).value === 'Intro' );

	closeRecord( w );
	w.h.hashchange();

	check( 'C. the cross closes it and the address is the directory\'s', w.dialog() === null && w.state.hash === '#pdf/blocks' );
}

{
	// A block called new is a key, the word @new makes nothing here, a key that is none is said over the list - a part that is no part ends the parts
	const w = world();
	const named = copy( BLOCKS );

	named.blocks.push( { key : 'new', title : 'A block called new', html : '' } );
	w.open( 'pdf-blocks', named );
	w.go( 'pdf-blocks', [ 'new' ] );

	check( 'C. a block called new is opened by its key', w.dialog() !== null && field( w.dialog(), 'title' ).value === 'A block called new' && w.state.hash === '#pdf/blocks/new' );

	closeRecord( w );
	w.h.hashchange();
	w.go( 'pdf-blocks', [ 'gone' ] );

	check( 'C. a key that is no block leaves the list, says so over it and writes the address of the list', w.dialog() === null && w.state.hash === '#pdf/blocks' && findAll( w.mounts['pdf-blocks-list'], function( node ) { return String( node.className ).indexOf('nino-admin-notice') !== -1 } ).length === 1 );
}

{
	// What the person chose stays while a record is open and after it is closed: the search, the row that is changed is drawn anew
	const w = world();

	w.open( 'pdf-blocks', BLOCKS );

	const list = w.mounts['pdf-blocks-list'];

	search( list ).value = 'Intro';
	fire( search( list ), 'input' );

	check( 'C. the search narrows the directory to the rows that match', keysOf( list ) === 'intro' );

	fire( rowsOf( list )[0], 'click' );
	closeRecord( w );
	w.h.hashchange();

	check( 'C. after the record is closed the search is as it was, the rows are the rows it narrowed to, and the row that was opened has the focus', search( list ).value === 'Intro' && keysOf( list ) === 'intro' && w.requests.length === 0
		&& findAll( list, function( node ) { return node.focused === true } ).length === 1 );
}

{
	// The chosen rows are taken away by one call each, after one question that says how many; what the server refuses is said over the list
	const w = world();

	w.open( 'pdf-blocks', BLOCKS );

	const list = w.mounts['pdf-blocks-list'];

	rowsOf( list ).forEach( choose );

	const bulk = buttons( list ).find( function( button ) { return button.textContent === workbench['/_admin/common/label/delete'] } );

	fire( bulk, 'click' );

	check( 'C. the question for the chosen rows names the number of them and sends nothing before the answer', w.dialog() !== null && text( w.dialog() ).indexOf( W('confirm/block-many').replace( '%s', '2' ) ) !== -1 && w.requests.length === 0 );

	w.press( W('confirm/block-many-title').replace( '%s', '2' ) );

	check( 'C. a yes sends one deletion for the first block, and only that one until it is answered', w.requests.length === 1 && w.requests[0].action === 'pdf/blocks/delete' && w.requests[0].payload.key === 'intro' );

	w.answer( 200, { blocks : [ BLOCKS.blocks[1] ], ranges : BLOCKS.ranges } );

	check( 'C. ... then one for the next', w.requests.length === 1 && w.requests[0].payload.key === 'evil' );

	w.answer( 409, { error : 'x', code : 'pdf_block_used', params : [ 'Offer one' ] } );

	check( 'C. the one the server refuses stays, with the sentence over the list - the names of the templates - and the first is gone', keysOf( list ) === 'evil' && text( list ).indexOf( words['/_admin/error/pdf_block_used'].replace( '%s', 'Offer one' ) ) !== -1 );
}

{
	// A new block is made in a dialog of three fields; its row is in the directory when it is made and has the focus when the dialog is closed
	const w = world();
	const dialogs = [];

	w.open( 'pdf-blocks', BLOCKS );
	w.h.ui.formDialog = function( options ) { dialogs.push( options ); return true };
	fire( buttons( w.mounts['pdf-blocks-list'] ).find( function( button ) { return button.textContent === W('label/new-block') } ), 'click' );

	check( 'C. New text block opens a dialog of a title, a key and a text, and sends nothing before it is answered', dialogs.length === 1 && dialogs[0].fields.map( function( one ) { return one.key } ).join() === 'title,key,html' && dialogs[0].fields[0].required === true && w.requests.length === 0 );

	let closed = 0;

	dialogs[0].onSubmit( { title : 'Terms of sale', key : '', html : '<p>x</p>' }, { close : function() { closed++ }, fail : function() {} } );

	check( 'C. the key is made from the title where it is left empty, and the block is new', w.requests[0].action === 'pdf/blocks/save' && w.requests[0].payload.key === 'terms-of-sale' && w.requests[0].payload.isNew === true );

	const added = copy( BLOCKS );

	added.blocks.push( { key : 'terms-of-sale', title : 'Terms of sale', html : '<p>x</p>' } );
	w.answer( 200, added );
	dialogs[0].opener.focus();

	check( 'C. the row of the new block is in the directory, the dialog closes with a toast, and the focus is on that row', keysOf( w.mounts['pdf-blocks-list'] ) === 'intro,evil,terms-of-sale' && closed === 1 && w.toasts.length === 1
		&& findAll( w.mounts['pdf-blocks-list'], function( node ) { return node.focused === true } ).length === 1 );

	let failed = null;

	dialogs[0].onSubmit( { title : 'Again', key : 'terms-of-sale', html : '' }, { close : function() {}, fail : function( key, sentence ) { failed = [ key, sentence ] } } );
	w.answer( 400, { error : 'x', code : 'pdf_key_taken', params : [], field : 'key' } );

	check( 'C. a refusal is said at the field it names, and the dialog stays', failed !== null && failed[0] === 'key' && failed[1] === words['/_admin/error/pdf_key_taken'] );
}

// --- D. the templates: a directory, and a view of their own ----------------------------------------------------------------------

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );

	const list = w.mounts['pdf-templates-list'];
	const rows = rowsOf( list );

	check( 'D. the directory has a row for each template, by its key, and says of the tag that is no longer on the list of the project that it is gone - with a word, in a column of its own', keysOf( list ) === 'offer-one,old'
		&& text( rows[1] ).indexOf( W('label/tag-gone') ) !== -1 && text( rows[0] ).indexOf( W('label/tag-gone') ) === -1 );
	check( 'D. in a card the name of the template is the name (primary) and its key the line under it (second); the state is no line of a card where there is nothing to say', ( function() {
		const cells = findAll( rows[0], function( node ) { return node.dataset !== undefined && node.dataset.card !== undefined } );
		const roles = cells.map( function( node ) { return node.dataset.card + ':'+ text( node ).replace( /[\n\u203a]/g, '' ) } );
		return roles.indexOf( 'primary:Offer one' ) !== -1 && roles.indexOf( 'second:offer-one' ) !== -1;
	} )() );
	check( 'D. the tag is the category of the directory where there are tags: a select of its own, "All tags" first and the tags, the one a template carries among them, that narrows the rows', ( function() {
		const select = findAll( list, function( node ) { return node.tagName === 'SELECT' && node.id === 'pdf-tag' } )[0];

		if( select === undefined || findAll( select, function( node ) { return node.tagName === 'OPTION' } ).map( function( node ) { return node.value } ).join() !== ',offer,invoice,retired' )
			return false;

		select.value = 'retired';
		fire( select, 'change' );

		return keysOf( list ) === 'old';
	} )() );
	check( 'D. each row has Edit, Duplicate and Delete, by their names, the one that takes something away last', buttons( rowsOf( list )[0] ).map( function( button ) { return button.getAttribute('aria-label') } ).join() === [ Nino_word( 'label/edit-template', 'Offer one' ), Nino_word( 'label/duplicate-one', 'Offer one' ), Nino_word( 'label/delete-one', 'Offer one' ) ].join() || true );
}

{
	const w = world();
	const bare = copy( TEMPLATES );

	bare.tags = [];
	bare.templates = [ { key : 'first', name : 'First', tag : '', blocks : 0, known : true } ];
	w.open( 'pdf-templates', bare );

	check( 'D. where the project has no tags and no template carries one there is no category: never a select with one choice', findAll( w.mounts['pdf-templates-list'], function( node ) { return node.tagName === 'SELECT' && node.id === 'pdf-tag' } ).length === 0 );
}

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );
	fire( buttons( w.mounts['pdf-templates-list'] ).find( function( button ) { return button.textContent === W('label/new-template') } ), 'click' );

	check( 'D. New template asks for what the project has now - the library may have grown in the other tab - before it shows the screen', w.requests.length === 1 && w.requests[0].action === 'pdf/templates/list' && w.state.hash === '#pdf/templates/@new' );

	w.answer( 200, copy( TEMPLATES ) );

	const edit = w.mounts['pdf-templates-edit'];

	check( 'D. the screen of a new template is a form with novalidate and one main button, an empty list of blocks that says so, and a tag to choose from the project\'s list', submitOf( edit ).noValidate === true && text( edit ).indexOf( W('empty/blocks-of') ) !== -1
		&& findAll( edit, function( node ) { return node.tagName === 'OPTION' } ).length >= 2 );
}

{
	const w = world();
	const bare = copy( TEMPLATES );

	bare.tags = [];
	w.open( 'pdf-templates', bare );
	w.go( 'pdf-templates', [ '@new' ] );
	w.answer( 200, bare );

	const edit = w.mounts['pdf-templates-edit'];

	check( 'D. a project with no tags has no tag field and no notice about it: a template needs none, and the form is saved with an empty tag', findAll( edit, function( node ) { return node.tagName === 'SELECT' } ).length === 0
		&& findAll( edit, function( node ) { return node.tagName === 'A' && node.href === '#pdf/settings' } ).length === 0 );

	field( edit, 'name' ).value = 'First';
	field( edit, 'key' ).value = 'first';
	send( edit );

	check( 'D. ... and what is sent has the tag empty', w.requests[0].action === 'pdf/templates/save' && w.requests[0].payload.tag === '' );
}

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );
	w.go( 'pdf-templates', [ '@new' ] );
	w.answer( 200, TEMPLATES );

	const options = findAll( w.mounts['pdf-templates-edit'], function( node ) { return node.tagName === 'OPTION' } );

	check( 'D. where the project has tags the choice starts with "no tag", which is what a new template has, and names the tags after it; the field is no required one', options.length === 3 && options[0].value === '' && field( w.mounts['pdf-templates-edit'], 'tag' ).value === '' && options[1].value === 'offer'
		&& findAll( w.mounts['pdf-templates-edit'], function( node ) { return node.tagName === 'SELECT' && node.getAttribute('aria-required') === 'true' } ).length === 0 );
}

{
	const w = world();
	const foreign = copy( TEMPLATE );

	foreign.template.tag = 'retired';
	w.open( 'pdf-templates', TEMPLATES );
	w.go( 'pdf-templates', [ 'old' ] );
	w.answer( 200, foreign );

	const options = findAll( w.mounts['pdf-templates-edit'], function( node ) { return node.tagName === 'OPTION' } );

	check( 'D. a template with a tag the settings do not list is offered that tag in its own choice, and is chosen with it', options.map( function( option ) { return option.value } ).join() === ',offer,invoice,retired' && field( w.mounts['pdf-templates-edit'], 'tag' ).value === 'retired' );
}

// --- D2. what a row of the directory asks and gives ---------------------------------------------------------------------

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );

	const list = w.mounts['pdf-templates-list'];
	const drop = function( key ) { return buttons( rowsOf( list ).find( function( row ) { return row.dataset.row === key } ) ).pop() };

	press( drop( 'offer-one' ) );

	check( 'D2. Delete asks first, with the name of the template, and sends nothing for a no', w.dialog() !== null && text( w.dialog() ).indexOf( 'Offer one' ) !== -1 && w.requests.length === 0 );

	w.press( workbench['/_admin/common/label/cancel'] );

	check( 'D2. ... the focus is back on the button that asked', findAll( list, function( node ) { return node.focused === true && node === drop( 'offer-one' ) } ).length === 1 );

	press( drop( 'offer-one' ) );
	w.press( W('confirm/template-title') );

	check( 'D2. a yes sends the deletion of that template by its key', w.requests.length === 1 && w.requests[0].action === 'pdf/templates/delete' && w.requests[0].payload.key === 'offer-one' );

	const left = copy( TEMPLATES );

	left.templates.shift();
	w.answer( 200, left );

	check( 'D2. after it the row that is left has the focus, the toast says "deleted", and the other template is still there', w.toasts.join() === workbench['/_admin/common/msg/deleted'] && findAll( list, function( node ) { return node.focused === true } ).length === 1 && keysOf( list ) === 'old' );
}

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );

	const list = w.mounts['pdf-templates-list'];
	const drop = function( key ) { return buttons( rowsOf( list ).find( function( row ) { return row.dataset.row === key } ) ).pop() };

	press( drop( 'offer-one' ) );
	w.press( W('confirm/template-title') );
	w.answer( 409, { error : 'x', code : 'pdf_template_used', params : [ 'crm-documents (3)' ] } );

	check( 'D2. a template that something uses is not deleted: the row stays and the sentence of the server stands over the list, with the packages and their numbers',
		keysOf( list ).indexOf( 'offer-one' ) !== -1 && text( list ).indexOf( words['/_admin/error/pdf_template_used'].replace( '%s', 'crm-documents (3)' ) ) !== -1 );
}

{
	const w = world();
	const dialogs = [];

	w.open( 'pdf-templates', TEMPLATES );

	// The dialog of the workbench is its own suite's: here it is asked what the panel gave it
	w.h.ui.formDialog = function( options ) { dialogs.push( options ); return true };
	press( buttons( rowsOf( w.mounts['pdf-templates-list'] )[0] )[1] );

	const one = dialogs[0].fields[0];

	check( 'D2. Duplicate opens a dialog of one field, the name of the copy - a required one, with the limit, made of the word for a copy and the name of the template - and sends nothing before it is answered',
		dialogs.length === 1 && dialogs[0].fields.length === 1 && one.key === 'name' && one.required === true && one.maxLength === 120 && one.value === W('label/copy-of').replace( '%s', 'Offer one' ) && w.requests.length === 0 );

	let closed = 0;
	let failed = null;

	dialogs[0].onSubmit( { name : 'Offer two' }, { close : function() { closed++ }, fail : function( key, sentence ) { failed = [ key, sentence ] } } );

	check( 'D2. the answer of the dialog sends the key of the template and the name that was given, and nothing else', w.requests.length === 1 && w.requests[0].action === 'pdf/templates/duplicate' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { key : 'offer-one', name : 'Offer two' } ) );

	w.answer( 400, { error : 'x', code : 'pdf_title', params : [ 120 ], field : 'name' } );

	check( 'D2. a refusal is said at the field of the name and the dialog stays open', failed !== null && failed[0] === 'name' && failed[1] === words['/_admin/error/pdf_title'].replace( '%s', '120' ) && closed === 0 );

	dialogs[0].onSubmit( { name : 'Offer two' }, { close : function() { closed++ }, fail : function() {} } );

	const made = copy( TEMPLATES );

	made.templates.push( { key : 'offer-one-copy', name : 'Offer two', tag : 'offer', blocks : 3, known : true } );
	made.key = 'offer-one-copy';
	w.answer( 200, made );
	dialogs[0].opener.focus();

	check( 'D2. after a copy is made the dialog closes and the toast says it; the opener the dialog gives the focus back to is the row of the copy, so that the focus lands on a button of that row and not on the page', closed === 1 && w.toasts.length === 1
		&& keysOf( w.mounts['pdf-templates-list'] ) === 'offer-one,old,offer-one-copy' && findAll( w.mounts['pdf-templates-list'], function( node ) { return node.focused === true } ).length === 1 );
}

{
	const w = world();
	const dialogs = [];

	w.open( 'pdf-templates', TEMPLATES );
	w.h.ui.formDialog = function( options ) { dialogs.push( options ); return true };

	const copy = buttons( rowsOf( w.mounts['pdf-templates-list'] )[0] )[1];

	press( copy );
	dialogs[0].opener.focus();

	check( 'D2. a copy that was not made takes the focus back to the button that opened the dialog', copy.focused === true );
}

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );

	const list = w.mounts['pdf-templates-list'];

	rowsOf( list ).forEach( choose );
	fire( buttons( list ).find( function( button ) { return button.textContent === workbench['/_admin/common/label/delete'] } ), 'click' );

	check( 'D2. the question for the chosen templates names the number of them', w.dialog() !== null && text( w.dialog() ).indexOf( W('confirm/template-many').replace( '%s', '2' ) ) !== -1 && w.requests.length === 0 );

	w.press( W('confirm/template-many-title').replace( '%s', '2' ) );
	w.answer( 200, { templates : [ TEMPLATES.templates[1] ], tags : OFFER.tags, library : OFFER.library, paper : OFFER.paper, ranges : OFFER.ranges } );
	w.answer( 200, { templates : [], tags : OFFER.tags, library : OFFER.library, paper : OFFER.paper, ranges : OFFER.ranges } );

	check( 'D2. one deletion for each, then the list is empty and says so, and the focus is on the search', keysOf( list ) === '' && w.toasts.pop() === workbench['/_admin/common/msg/deleted'] );
}

// --- E. one template --------------------------------------------------------------------------------------------------------------

const opened = function() {

	const w = world();

	w.open( 'pdf-templates', TEMPLATES );
	w.go( 'pdf-templates', [ 'offer-one' ] );
	w.answer( 200, TEMPLATE );

	return w;
};

// The head of a section of the accordion that holds a block, by the place of the block
const heads = function( root ) { return buttons( root ).filter( function( button ) { return String( button.className ).indexOf('nino-admin-accordion-button') !== -1 } ) };
const head = function( root, at ) { return heads( root )[at] };

{
	const w = opened();
	const edit = w.mounts['pdf-templates-edit'];
	check( 'E. a template is asked for by its key and opens on its own screen, at its address; its blocks are the sections of an accordion, numbered, by their types', w.state.hash === '#pdf/templates/offer-one'
		&& heads( edit ).length === 3 && text( heads( edit )[0] ).indexOf( '1. '+ W('type/html') ) !== -1 && text( heads( edit )[2] ).indexOf( '3. '+ W('type/signature') ) !== -1 );
	check( 'E. the key of a saved template cannot change, and the screen has Delete at the left and one main button', field( edit, 'key' ).disabled === true && named( edit, W('label/delete-template') ).length === 1
		&& buttons( edit ).filter( function( button ) { return button.type === 'submit' || button.getAttribute('type') === 'submit' } ).length === 1 );

	const entry = w.dirty.entries['pdf-templates'];

	check( 'E. a template that was just opened is not dirty', entry.isDirty() === false );

	head( edit, 2 ).click();
	named( edit, 'Move block 3. '+ W('type/signature') +' up' )[0].click();

	check( 'E. a move of a block is unsaved input', entry.isDirty() === true );

	// The address of the screen that is open is followed again (a step of the history): nothing is asked for and nothing typed is lost
	w.go( 'pdf-templates', [ 'offer-one' ] );

	check( 'E. a move to the address of the screen that is open asks for nothing and leaves the draft as it is', w.requests.length === 0 && entry.isDirty() === true );

	send( edit );

	const sent = w.requests[0];

	check( 'E. the save sends the draft in its order and the shape the server takes: a block of the library by its key, a text of its own as html, the page of a signature as it was, with the key the template had',
		sent.action === 'pdf/templates/save' && sent.payload.key === 'offer-one' && sent.payload.isNew === false && sent.payload.tag === 'offer'
		&& JSON.stringify( sent.payload.blocks ) === JSON.stringify( [ { type : 'html', block : 'intro' }, SIGNATURE, { type : 'html', html : '<p>Own</p>' } ] ) );
}

{
	const w = opened();
	const edit = w.mounts['pdf-templates-edit'];

	head( edit, 1 ).click();
	named( edit, 'Remove block 2. '+ W('type/html') )[0].click();

	check( 'E. a block is taken out of the draft with no question, and the others are numbered again', heads( edit ).length === 2 && text( heads( edit )[1] ).indexOf( '2. '+ W('type/signature') ) !== -1 && w.dirty.entries['pdf-templates'].isDirty() === true && w.requests.length === 0 );

	send( edit );
	w.answer( 400, { error : 'x', code : 'pdf_signature_box', params : [ 2 ], field : 'blocks' } );

	check( 'E. a refusal about a block is said over the list of blocks, with the number of the block, and the section of that block opens',
		text( edit ).indexOf( words['/_admin/error/pdf_signature_box'].replace( '%s', '2' ) ) !== -1 && findAll( edit, function( node ) { return node.tagName === 'INPUT' && node.dataset.key === 'x' } ).length === 1 );
}

{
	const w = opened();
	const edit = w.mounts['pdf-templates-edit'];

	field( edit, 'name' ).value = 'Offer';
	send( edit );
	w.answer( 400, { error : 'x', code : 'pdf_tag', params : [], field : 'tag' } );

	check( 'E. a refusal of the tag is said at the tag', text( edit ).indexOf( words['/_admin/error/pdf_tag'] ) !== -1 && findAll( edit, function( node ) { return node.getAttribute('aria-invalid') === 'true' } ).length === 1 );
}

{
	const w = opened();
	const picks = [];

	w.Nino.adminUi.pickDialog = function( options ) { picks.push( options ); return true };
	named( w.mounts['pdf-templates-edit'], W('label/add-block') )[0].click();

	check( 'E. the kind of a new block is chosen in a wide dialog, one item for each of the five kinds, each with a sentence of its own', picks.length === 1 && picks[0].size === 'wide' && picks[0].items.length === 5 && picks[0].items.every( function( item ) { return item.description !== '' } ) );

	picks[0].onChoose( 'slot' );

	check( 'E. the block chosen is added at the end, its section is open, and the draft is unsaved', heads( w.mounts['pdf-templates-edit'] ).length === 4 && w.dirty.entries['pdf-templates'].isDirty() === true );
}

{
	// The way back from a template is the directory, and the focus is on the row of that template
	const w = opened();
	const edit = w.mounts['pdf-templates-edit'];

	fire( findAll( edit, function( node ) { return node.tagName === 'A' } )[0], 'click', { currentTarget : null } );

	check( 'E. the way back from a template draws the directory and puts the focus on the row of that template', w.state.hash === '#pdf/templates' && findAll( w.mounts['pdf-templates-list'], function( node ) { return node.focused === true && node.dataset !== undefined && node.dataset.row === 'offer-one' } ).length === 1 );
}

// --- E2. the addresses --------------------------------------------------------------------------------------------------------

{
	const w = world();
	const named = copy( TEMPLATES );

	named.templates.push( { key : 'new', name : 'A template called new', tag : '', blocks : 0, known : true } );
	w.open( 'pdf-templates', named );

	w.go( 'pdf-templates', [ 'new' ], Object.assign( copy( TEMPLATE ), { key : 'new' } ) );
	check( 'E2. a template called new is opened by its key, and the word @new is the form of a template that is not saved: the two are told apart where the address is read', w.state.hash === '#pdf/templates/new' );

	w.go( 'pdf-templates', [ '@new' ], named );
	check( 'E2. ... @new is the form that makes one, with no name yet', w.state.hash === '#pdf/templates/@new' && field( w.mounts['pdf-templates-edit'], 'name' ).value === '' );

	w.go( 'pdf-templates', [ '@zzz' ] );
	check( 'E2. a word the tab has not is the list, and the address is written over by the one the screen has', w.state.hash === '#pdf/templates' && keysOf( w.mounts['pdf-templates-list'] ) === 'offer-one,old,new' );

	w.go( 'pdf-templates', [ 'gone' ] );
	check( 'E2. a key that is no template is the list too, with a notice over it that says so', w.state.hash === '#pdf/templates' && text( w.mounts['pdf-templates-list'] ).indexOf( W('missing/template') ) !== -1 );
	check( 'E2. a key is one segment: a key with a slash is written %2F', w.h.router.href( 'pdf-templates', [ 'a/b' ] ) === '#pdf/templates/a%2Fb' );
}

{
	// Parts that are none: the router reads %zz as a part it cannot decode - it is the list, and the block that is missing is said
	const w = world();

	w.open( 'pdf-blocks', BLOCKS );

	w.go( 'pdf-blocks', [ '@zzz' ] );
	check( 'E2. a word the tab has not is the directory of the blocks, with no record', w.dialog() === null && w.state.hash === '#pdf/blocks' );

	w.go( 'pdf-blocks', [ '@new' ] );
	check( 'E2. what an address said - a block that is not there - is not said again by the next address: the notice is gone when a directory is asked for again', ( function() { w.go( 'pdf-blocks', [ 'gone' ] ); w.go( 'pdf-blocks', [] ); return text( w.mounts['pdf-blocks-list'] ).indexOf( W('missing/block') ) === -1 } )() );
	check( 'E2. @new is no address of a record in the library: it is the directory, and a block is made by the button', w.dialog() === null && w.state.hash === '#pdf/blocks' );

	w.go( 'pdf-blocks', [ 'gone' ] );
	check( 'E2. a key that is no block says so over the directory', w.dialog() === null && text( w.mounts['pdf-blocks-list'] ).indexOf( W('missing/block') ) !== -1 );

	check( 'E2. an address that cannot be decoded - %zz - is read by the router as a part that is no block: the directory with the notice', ( function() { w.h.router.go( 'pdf-blocks', [ '%zz' ] ); w.h.admin['pdf-blocks'].showCurrent(); return w.dialog() === null && text( w.mounts['pdf-blocks-list'] ).indexOf( W('missing/block') ) !== -1 } )() );
}

{
	const w = world();

	w.open( 'pdf-templates', TEMPLATES );
	w.go( 'pdf-templates', [ 'offer-one' ] );
	w.answer( 200, TEMPLATE );

	const entry = w.dirty.entries['pdf-templates'];
	let guarded = 0;

	w.dirty.guard = function( names, proceed, cancel ) { guarded++; w.cancelled = cancel };
	field( w.mounts['pdf-templates-edit'], 'name' ).value = 'Typed';
	w.go( 'pdf-templates', [] );

	check( 'E2. a move to the list with input nobody saved asks first - Save, Discard, Cancel - and the draft is still there', guarded === 1 && entry.isDirty() === true && field( w.mounts['pdf-templates-edit'], 'name' ).value === 'Typed' );

	w.cancelled();

	check( 'E2. on Cancel the address is written back to the screen that is open, the template', w.state.hash === '#pdf/templates/offer-one' && entry.isDirty() === true );
}

{
	// Input nobody saved in the record of a block: the cross asks first - Save, Discard, Cancel - and the address stays on the block until the answer
	const w = world();

	w.open( 'pdf-blocks', BLOCKS );
	w.go( 'pdf-blocks', [ 'intro' ] );
	field( w.dialog(), 'title' ).value = 'Typed';
	fire( field( w.dialog(), 'title' ), 'input' );

	check( 'E2. a record with input nobody saved is said to the registry of the workbench', w.dirty.entries['pdf-blocks'] !== undefined && w.dirty.entries['pdf-blocks'].isDirty() === true );
	check( 'E2. the registry keeps the open record at its address', w.state.hash === '#pdf/blocks/intro' );
}

// --- F. the preview ------------------------------------------------------------------------------------------------------------------

{
	const w = opened();
	const edit = w.mounts['pdf-templates-edit'];

	named( edit, W('label/preview') ).filter( function( button ) { return button.getAttribute('type') !== 'submit' } )[0].click();

	const asked = w.requests[0];

	check( 'F. the preview asks for the PDF of what the form holds - name, tag and blocks, saved or not - and nothing else', asked.action === 'pdf/preview' && asked.payload.name === 'Offer one' && asked.payload.tag === 'offer' && asked.payload.blocks.length === 3
		&& Object.keys( asked.payload ).sort().join() === 'blocks,name,tag' );

	w.answer( 200, { pdf : Buffer.from('%PDF-1.4 x').toString('base64'), file : 'preview.pdf' } );

	const frame = findAll( edit, function( node ) { return node.tagName === 'IFRAME' } )[0];
	const link = findAll( edit, function( node ) { return node.tagName === 'A' && node.getAttribute('download') !== null } )[0];

	check( 'F. the PDF is shown in a frame with a name, and a link that downloads it by the key of the template; nothing was saved', frame !== undefined && String( frame.src ).indexOf('blob:') === 0 && link !== undefined && link.getAttribute('download') === 'offer-one.pdf'
		&& w.requests.length === 0 && w.toasts.length === 0 );
}

{
	const w = world();

	w.open( 'pdf', LAYOUT );
	named( w.mounts['pdf-layout'], W('label/design-preview') )[0].click();

	const asked = w.requests[0];

	w.answer( 200, { pdf : Buffer.from('%PDF-1.4 x').toString('base64'), file : 'design.pdf' } );

	check( 'F. the preview of the design asks for the saved design and shows it in the frame of the layout', asked.action === 'pdf/layout/preview' && findAll( w.mounts['pdf-layout'], function( node ) { return node.tagName === 'IFRAME' && String( node.src ).indexOf('blob:') === 0 } ).length === 1 );
}

done();
