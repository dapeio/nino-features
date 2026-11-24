/**
 *	Nino features
 *	themes-css-js-smoke.js	What the script of the tab of the own css does (assets/admin-css.js), over the kernel's stand-in of a page and the real
 *													Nino.adminUi:
 *
 *														A. the tab: the name the workbench asks it by, what showing it asks, and that every request is one of the three actions
 *														B. the screen: the field holds the text, the counter says its size, the bar knows when the text is not saved
 *														C. a draft: what is typed is sent to the preview, the faults it answers keep the text from being saved, the limit too
 *														D. a save: on the stamp the text was opened with, what the bar says after it, a refusal, and the question of a second session
 *
 *													The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js
 *													of the checkout, three levels up or the one NINO_ROOT names. themes-css-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Themes/tests/themes-css-js-smoke.js
 *	       NINO_ROOT=../nino node features/Themes/tests/themes-css-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, documentOf, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const viewsScript = path.relative( root, path.join( featureDir, 'assets/admin-css.js' ) );
const script = fs.readFileSync( path.join( featureDir, 'assets/admin-css.js' ), 'utf8' );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

// The words of the view and of the API, by their keys: a test of what a view says asks for the fill and never writes the sentence
const W = function( key, ...params ) { return String( words.en['/_admin/themes/css/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

const STAMP = 'a'.repeat( 64 );

/** What themes/css answers */
function view( change ) {
	return Object.assign( { css : 'a { color: red; }\n', stamp : STAMP, bytes : 19, max : 262144, problem : '', exists : true, pages : [ { path : '/', title : 'Home' } ] }, change ?? {} );
}

/**
 *	The two scripts in a page of their own: the workbench's ui, a router that keeps the address, the requests held until a test answers them
 *
 *	@param		{Object}		[options]			{ lang, themes (false: the tab of the themes was never shown) }
 *
 *	@return		{Object}
 */
function world( options ) {

	options = Object.assign( { lang : 'en-US', themes : true }, options ?? {} );

	const page = element('div');
	const app = element('div');
	const list = element('div');
	const theme = element('div');
	const wrap = element('div');
	const mount = element('div');
	const requests = [];
	const dialogs = [];
	const toasts = [];
	const state = { hash : '' };

	page.id = 'page';
	app.id = 'themes-app';
	list.id = 'themes-list';
	theme.id = 'themes-theme';
	theme.className = 'admin-hidden';
	wrap.id = 'admin-page-wrap';
	app.appendChild( list );
	app.appendChild( theme );
	mount.id = 'themes-css-list';
	page.appendChild( app );
	page.appendChild( mount );
	page.appendChild( wrap );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) {
		const created = element( tag );
		created.getBoundingClientRect = function() { return { width : 600, height : 600 } };
		if( tag === 'dialog' ) {
			created.open = false;
			created.showModal = function() { created.open = true };
			created.close = function() { created.open = false; fire( created, 'close' ) };
			dialogs.push( created );
		}
		return created;
	};

	// The router of the workbench itself, over the panes of the panel: what the tab writes is read back by it
	const codec = shellRouter( { themes : [ [ 'themes', 'themes' ], [ 'themes-fonts', 'fonts' ], [ 'themes-css', 'css' ], [ 'themes-settings', 'settings' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		isActive : function() { return true },
		go : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
	} );

	const entries = {};
	const dirty = { register : function( name, entry ) { entries[name] = entry }, isDirty : function() { return false }, refresh : function() {}, guard : function( names, proceed ) { proceed() } };

	const Nino = {
		dir : '/site',
		slug : function( text ) { return String( text ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ) },
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return ( options.lang === 'de-DE' ? words.de[key] : words.en[key] ) ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) {
			requests.push( { uri : uri, action : data.action, payload : JSON.parse( data.data ), callback : callback } );
		} },
		admin : { router : router, dirty : dirty },
	};

	function Watcher() {
		this.observe = function() {};
		this.unobserve = function() {};
		this.disconnect = function() {};
	}

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder, Date : Date };

	sandbox.window = {
		Nino : Nino,
		IntersectionObserver : Watcher,
		location : { hash : '', href : 'http://workbench.test/_admin/' },
		confirm : function() { return true },
		setTimeout : function() { return 1 },
		clearTimeout : function() {},
	};

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	Nino.adminUi.say = function( text ) { toasts.push( text ) };
	load( viewsScript, context );

	const w = {
		entries : entries, Nino : Nino, doc : doc, app : app, list : list, mount : mount, requests : requests, dialogs : dialogs, toasts : toasts, state : state,
		tab : Nino.admin['themes-css'],
		sent : [],

		/** Answer the oldest request that waits, and give back what it was */
		answer : function( status, body ) {
			const request = requests.shift();
			w.sent.push( request );
			request.callback( { status : status, responseJSON : body } );
			return request;
		},

		/** The tab is shown, as the workbench shows it */
		show : function() {
			state.hash = '#themes/css';
			w.tab.showCurrent();
		},

		box : function() { return mount },
		byClass : function( from, cls ) { return findAll( from, function( node ) { return String( node.className ).split(/\s+/).indexOf( cls ) !== -1 } ) },
		byTag : function( from, tag ) { return findAll( from, function( node ) { return node.tagName === tag.toUpperCase() } ) },
		text : function( node ) { return node === undefined || node === null ? '' : node.textContent + node.children.map( w.text ).join('') },
		button : function( from, label ) { return findAll( from, function( node ) { return node.tagName === 'BUTTON' && w.text( node ).trim() === label } )[0] },

		/** Press an answer of the question that is open */
		choose : function( label ) {
			const dialog = dialogs.filter( function( one ) { return one.open === true } ).pop();
			fire( w.button( dialog, label ), 'click' );
		},
	};

	return w;
}


// --- A. the tab ---------------------------------------------------------------------------------------------------------------

const type = function( w, text ) {
	const area = w.byTag( w.box(), 'textarea' )[0];
	area.value = text;
	fire( area, 'input' );
};
const saveButton = function( w ) { return w.doc.getElementById('themes-css-bar-save') };
const answerPreview = function( w, faults ) { return w.answer( 200, { token : '0'.repeat( 32 ), query : 'nino-preview='+ '0'.repeat( 32 ), ttl : 900, faults : faults ?? [] } ) };

{
	const w = world();

	check( 'A. the tab is in the namespace of the workbench under its uri, themes-css, with the function the shell calls when it is shown - and loading the script asks nothing', typeof w.tab === 'object' && typeof w.tab.showCurrent === 'function' && w.requests.length === 0 );

	w.show();
	check( 'A. showing the tab asks themes/css through the endpoint of the workbench - one request, with no payload', w.requests.length === 1 && w.requests[0].action === 'themes/css' && Object.keys( w.requests[0].payload ).length === 0 );
	w.answer( 200, view() );

	const first = w.requests.shift();

	w.tab.showCurrent();
	w.answer( 200, view( { css : 'b { }' } ) );
	first.callback( { status : 200, responseJSON : view() } );
	w.requests.length = 0;
	check( 'A. showing the tab once more asks once more, and of two answers for the screen the one of the later request is drawn', w.byTag( w.box(), 'textarea' ).length === 1 );

	const failing = world();

	failing.show();
	failing.answer( 403, { error : 'not allowed' } );
	check( 'A. an answer that is none shows the error in place of the screen: no field, no bar', failing.byTag( failing.box(), 'textarea' ).length === 0 && failing.doc.getElementById('themes-css-bar') === null );
}

// --- B. the screen --------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view() );

	const area = w.byTag( w.box(), 'textarea' )[0];
	const entry = w.entries['themes-css'];

	check( 'B. the field holds the text of the file in the face of code, with its name and its help behind the (?); the counter says its size against the limit; the bar has Save, off while nothing is changed',
		area.value === 'a { color: red; }\n' && String( area.className ).indexOf('nino-admin-code') !== -1 && w.text( w.byClass( w.box(), 'themes-css-size' )[0] ) === W( 'size/bytes', '18', '256' ) && saveButton( w ).disabled === true
		&& w.text( w.byClass( w.box(), 'themes-css-field' )[0] ).indexOf( W('field/label') ) !== -1 && w.byClass( w.box(), 'nino-admin-help' ).length + w.byClass( w.box(), 'nino-admin-field-help' ).length > 0 );

	check( 'B. the screen is registered with the shell for unsaved input under its uri; it holds none until the text is typed in, and then it does - and typing the text back is none again',
		typeof entry === 'object' && entry.isDirty() === false && ( type( w, 'a { color: blue; }' ), entry.isDirty() === true ) && saveButton( w ).disabled === false && ( type( w, 'a { color: red; }\n' ), entry.isDirty() === false ) );

	check( 'B. the first preview is asked for at once with the text and no token, and the page it answers is loaded with its query in the frame of the preview',
		w.requests.length === 1 && w.requests[0].action === 'themes/css-preview' && w.requests[0].payload.css === 'a { color: red; }\n' && w.requests[0].payload.token === '' );
	answerPreview( w );
	const frame = w.byTag( w.box(), 'iframe' )[0];
	check( 'B. the frame shows the page of the site with the query of the draft', frame !== undefined && String( frame.src || frame.attributes.src ).indexOf( '/site/?nino-preview='+ '0'.repeat( 32 ) ) === 0 );
}

// --- B2. a tab that is left and shown again ----------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view() );
	answerPreview( w );
	type( w, 'typed { color: red; }' );
	w.tab.showCurrent();

	check( 'B. a tab that is shown again while its text is typed and not saved keeps the text and asks nothing: the pane holds its tabs in memory and moves between them without a question',
		w.requests.length === 0 && w.byTag( w.box(), 'textarea' ).length === 1 && w.byTag( w.box(), 'textarea' )[0].value === 'typed { color: red; }' && w.entries['themes-css'].isDirty() === true );

	w.entries['themes-css'].discard();
	w.tab.showCurrent();

	check( 'B. once the text was given up - the shell asked and the answer was Discard - the tab is drawn anew from the file', w.requests.length === 1 && w.requests[0].action === 'themes/css' );
}

// --- C. a draft -----------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view() );
	answerPreview( w, [ 'open-block' ] );
	type( w, 'a { color: red;' );

	check( 'C. a text whose braces are not closed - what the preview says - keeps Save off though the text is changed, and the bar says why with the sentence of the fault',
		saveButton( w ).disabled === true && w.text( w.doc.getElementById('themes-css-bar') ).indexOf( W('fault/open-block') ) !== -1 );

	const long = world();

	long.show();
	long.answer( 200, view() );
	answerPreview( long );
	type( long, '/*'+ 'a'.repeat( 262143 ) );
	check( 'C. a text over the limit keeps Save off as well, and the counter says how much it is', saveButton( long ).disabled === true && long.text( long.byClass( long.box(), 'themes-css-size' )[0] ).indexOf('256') !== -1 );
	type( long, '/*'+ 'a'.repeat( 262140 )+ '*/' );
	check( 'C. a text of exactly the limit does not', saveButton( long ).disabled === false );
}

{
	const w = world();

	w.show();
	w.answer( 200, view() );
	answerPreview( w );
	type( w, '/* '+ 'a'.repeat( 1534 )+ ' */' );

	check( 'C. a text of a kilobyte or more is counted in kilobytes, with one decimal below ten, and one of less in bytes - never as 0.0', w.text( w.byClass( w.box(), 'themes-css-size' )[0] ) === W( 'size/note', '1.5', '256' ) && W( 'size/bytes', '18', '256' ).indexOf('0.0') === -1 );

	type( w, 'a { }\n<!-- old -->' );

	check( 'C. a text that holds what could end a style element or a comment of html - a file that was there already may - keeps Save off, and the bar says which mark it is',
		saveButton( w ).disabled === true && w.text( w.doc.getElementById('themes-css-bar') ).indexOf( String( words.en['/_admin/error/themes_css_markup'] ).replace( '%s', '<!--' ) ) !== -1 );

	type( w, 'a { }' );
	check( 'C. and the text without it can be saved', saveButton( w ).disabled === false );

	const shown = world();

	shown.show();
	shown.answer( 200, view( { css : '<!-- old -->\na { }\n', stamp : STAMP } ) );
	answerPreview( shown );
	check( 'C. a file that breaks the rule is shown as it is and cannot be saved until the text keeps it: the field holds it, Save is off though nothing was typed - and typing a fix turns Save on',
		shown.byTag( shown.box(), 'textarea' )[0].value === '<!-- old -->\na { }\n' && saveButton( shown ).disabled === true && ( type( shown, 'a { }\n' ), saveButton( shown ).disabled === false ) );
}

{
	const w = world();

	w.show();
	w.answer( 200, view( { css : '', problem : 'themes_css_big', bytes : 300000 } ) );

	check( 'C. a file that cannot be shown - longer than the limit, a NUL byte, no utf-8 - is said with the rule it breaks: no field, no bar and no preview, nothing to write over it, and no request but the one that read it',
		w.byTag( w.box(), 'textarea' ).length === 0 && w.doc.getElementById('themes-css-bar') === null && w.requests.length === 0 && w.text( w.box() ).indexOf( W( 'why/big' ) ) !== -1 );
}

// --- D. a save ------------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view() );
	answerPreview( w );
	type( w, 'b { color: blue; }' );
	fire( saveButton( w ), 'click' );

	check( 'D. Save sends the text with the stamp it was opened with - and no force', w.requests.length === 1 && w.requests[0].action === 'themes/css-save' && w.requests[0].payload.css === 'b { color: blue; }' && w.requests[0].payload.base === STAMP && w.requests[0].payload.force === false );

	w.answer( 200, { saved : true, stamp : 'b'.repeat( 64 ), bytes : 18 } );
	check( 'D. a save that went through leaves nothing unsaved and Save off', w.entries['themes-css'].isDirty() === false && saveButton( w ).disabled === true );
	check( 'D. a saved text says so once: the bar of the tab says it, and the tab adds nothing', w.toasts.length === 1 && w.toasts[0] === workbench['/_admin/common/msg/saved'] && workbench['/_admin/common/msg/saved'] !== '' );

	type( w, 'c { color: green; }' );
	fire( saveButton( w ), 'click' );
	check( 'D. the next save is made on the stamp the last one answered', w.requests[0].payload.base === 'b'.repeat( 64 ) );

	w.answer( 409, { error : 'the css was changed since it was opened', code : 'themes_css_changed', field : 'css' } );
	const open = w.dialogs.filter( function( one ) { return one.open === true } );

	check( 'D. a stamp that is not the file\'s any more is the question of the workbench for a second session - Reload, Cancel, Overwrite - and the text stays as it is typed while it is open',
		open.length === 1 && w.button( open[0], workbench['/_admin/common/conflict/reload'] ) !== undefined && w.button( open[0], workbench['/_admin/common/conflict/overwrite'] ) !== undefined && w.byTag( w.box(), 'textarea' )[0].value === 'c { color: green; }' && w.entries['themes-css'].isDirty() === true );

	w.choose( workbench['/_admin/common/conflict/overwrite'] );
	check( 'D. Overwrite sends the same text once more with force', w.requests.length === 1 && w.requests[0].action === 'themes/css-save' && w.requests[0].payload.force === true && w.requests[0].payload.css === 'c { color: green; }' );
	w.answer( 200, { saved : true, stamp : 'c'.repeat( 64 ), bytes : 19 } );

	type( w, 'd { color: gray; }' );
	fire( saveButton( w ), 'click' );
	w.answer( 409, { error : 'the css was changed since it was opened', code : 'themes_css_changed', field : 'css' } );
	w.choose( workbench['/_admin/common/conflict/reload'] );

	check( 'D. Reload asks the file again and draws what is there: the text of the other session, nothing unsaved', w.requests.length >= 1 && w.requests[0].action === 'themes/css' );
	w.answer( 200, view( { css : 'e { color: black; }', stamp : 'd'.repeat( 64 ) } ) );
	check( 'D. and the field holds it', w.byTag( w.box(), 'textarea' )[0].value === 'e { color: black; }' && w.entries['themes-css'].isDirty() === false );

	w.requests.length = 0;
	type( w, 'f { color: pink; }' );
	fire( saveButton( w ), 'click' );
	w.answer( 400, { error : 'the braces of the css are not closed', code : 'themes_css_faults', field : 'css' } );

	check( 'D. a refusal of the server stays on the line of the bar and the text stays: Save is on again and the text is unsaved', w.entries['themes-css'].isDirty() === true && saveButton( w ).disabled === false && w.text( w.doc.getElementById('themes-css-bar') ).indexOf( String( words.en['/_admin/error/themes_css_faults'] ) ) !== -1 );
}

// --- E. the words ---------------------------------------------------------------------------------------------------------------

{
	const asked = new Set();
	const pattern = /\bword\(\s*'([a-z0-9\/-]+)'/g;
	let found;

	while( ( found = pattern.exec( script ) ) !== null )
		if( found[1].slice( -1 ) !== '/' )
			asked.add( found[1] );

	check( 'E. every word the script asks for - '+ asked.size+ ' of them - is a fill of the feature in both interface languages, with the same places for what is put in', asked.size > 4 && [ ...asked ].every( function( key ) {
		const en = words.en['/_admin/themes/css/'+ key];
		const de = words.de['/_admin/themes/css/'+ key];

		return typeof en === 'string' && en !== '' && typeof de === 'string' && de !== '' && ( en.match( /%[sdn]/g ) ?? [] ).join() === ( de.match( /%[sdn]/g ) ?? [] ).join();
	} ) );
	check( 'E. every sentence the tab can get from the API - each code of the own css - is a fill of both languages, and no sentence is written into the script: a text is set with word(), never as markup',
		[ 'big', 'nul', 'utf8', 'markup', 'faults', 'missing', 'locked', 'busy', 'changed', 'write' ].every( function( code ) { return words.en['/_admin/error/themes_css_'+ code] !== undefined && words.de['/_admin/error/themes_css_'+ code] !== undefined } )
		&& [ 'big', 'nul', 'utf8' ].every( function( why ) { return words.en['/_admin/themes/css/why/'+ why] !== undefined && words.de['/_admin/themes/css/why/'+ why] !== undefined } )
		&& [ 'open-comment', 'open-string', 'open-url', 'open-block', 'stray-close' ].every( function( fault ) { return words.en['/_admin/themes/css/fault/'+ fault] !== undefined && words.de['/_admin/themes/css/fault/'+ fault] !== undefined } )
		&& /innerHTML\s*=\s*[^'\s]/.test( script ) === false && /\.insertAdjacentHTML|document\.write|\.outerHTML\s*=|\beval\s*\(|new\s+Function/.test( script ) === false && /\.style\b|setAttribute\(\s*['"]style/.test( script ) === false );
}

done();
