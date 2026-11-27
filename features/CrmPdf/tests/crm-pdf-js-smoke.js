/**
 *	Nino features
 *	crm-pdf-js-smoke.js		What the script of the package does (Surface/assets/crm-pdf.js) over the stand-in of the kernel's tests/harness.js and the real script of the surface of the CRM: the page and the entry of the rail, the
 *												address of each screen, the screens drawn from the answers of the endpoint as the server gives them, what a save, a preview, a picture and a deletion post, where a refusal is said, and that nothing
 *												a person typed or a server answered is read as markup.
 *
 *												A. the file: it builds no markup from data, sets no style, makes no request of its own, and every word it asks for is a word of the package, in both languages
 *												B. the page and the rail: the entry is there for an account that may use the screens and hidden for one that may not, and a screen of an account that may not says so without a request
 *												C. the addresses: each screen, and an address that is none of the package's
 *												D. the list of the templates, the editor of a template, the text blocks, the layout and the design
 *												E. the preview, the pictures, and what is said where the server refuses
 *
 *												The helpers - check(), done(), load(), findAll(), element(), fire() and documentOf() - are those of tests/harness.js of the checkout, three levels up or the one NINO_ROOT names.
 *
 *	Usage: node features/CrmPdf/tests/crm-pdf-js-smoke.js
 *	       NINO_ROOT=../nino node features/CrmPdf/tests/crm-pdf-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, setImmediate, Buffer */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, load, element, fire, documentOf, matches } = require( path.join( root, 'tests/harness.js' ) );

process.on( 'unhandledRejection', function( error ) {
	check( 'no promise of the script is left rejected: '+ String( error && error.message ), false );
	process.exitCode = 1;
} );

const featureDir = path.join( __dirname, '..' );
const coreDir = path.join( featureDir, '..', 'Crm' );
const OWN = path.relative( root, path.join( coreDir, 'Surface' ) )+ '/';
const SCRIPTS = [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ];
const PACKAGE = path.relative( root, path.join( featureDir, 'Surface/assets/crm-pdf.js' ) );
const SOURCE = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm-pdf.js' ), 'utf8' );

/**
 *	@param		{string}	file
 *
 *	@return		{Object}									The flat words of a PHP file of words: key => text
 */
function wordsIn( file ) {

	const map = {};
	const re = /^\t'([a-z0-9._-]+)' => '((?:[^'\\]|\\.)*)',$/gm;
	const text = fs.readFileSync( file, 'utf8' );
	let found;

	while( ( found = re.exec( text ) ) )
		map[found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return map;
}

/**
 *	@param		{string}	locale
 *
 *	@return		{Object}									The words of the page: the surface's, the sentences of the codes of the core, and the package's
 */
function pageWords( locale ) {

	const core = wordsIn( path.join( coreDir, 'Surface/text/'+ locale+ '.php' ) );
	const codes = {};
	const re = /^\t'\[\[\/_admin\/error\/([a-z0-9_]+)\]\]' => '((?:[^'\\]|\\.)*)',$/gm;
	const text = fs.readFileSync( path.join( coreDir, 'text/'+ locale+ '.php' ), 'utf8' );
	let found;

	while( ( found = re.exec( text ) ) )
		codes['error.'+ found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return Object.assign( {}, core, codes, wordsIn( path.join( featureDir, 'Surface/text/'+ locale+ '.php' ) ) );
}

const texts = { de_DE : pageWords( 'de_DE' ), en_US : pageWords( 'en_US' ) };

// --- the world: the page, the browser and the endpoint as stand-ins ------------------------------------------------

function world( options ) {

	const opts = Object.assign( { hash : '#pdf', answers : {}, allowed : true, locale : 'de_DE' }, options || {} );
	const calls = [];
	const timers = [];
	const hashListeners = [];
	const documentListeners = {};
	const windowListeners = {};
	const dialogs = [];
	const scrolled = [];
	const urls = { made : [], revoked : [] };

	const mount = element('div');
	const nav = element('nav');
	nav.id = 'nav';
	const main = element('main');
	main.id = 'main';
	const toast = element('div');
	toast.id = 'toast';
	const dialogRoot = element('div');
	dialogRoot.id = 'dialogs';
	const signout = element('button');
	signout.id = 'signout';
	[ nav, main, toast, dialogRoot, signout ].forEach( function( node ) { mount.appendChild( node ) } );

	const block = element('script');
	block.id = 'crm-data';
	block.textContent = JSON.stringify( { base : '', csrf : 'token-1', today : '2026-11-24', locale : opts.locale, user : { name : 'both@example.org' }, perm : '/_admin/crm/manage', perms : { crm : true, elements : true, text : true }, texts : texts[opts.locale],
		links : { workbench : '/_admin/', site : '/', users : '/_admin/#users' }, packages : opts.packages ?? { 'crm-pdf' : { allowed : opts.allowed } } } );
	mount.appendChild( block );

	const page = documentOf( mount );
	const make = page.createElement;

	page.readyState = 'loading';
	page.createElement = function( tag ) {

		const el = make( tag );

		if( tag === 'form' )
			el.requestSubmit = function() { fire( el, 'submit', { target : el, preventDefault : function() {} } ) };

		el.scrollIntoView = function( options ) { scrolled.push( [ el.id, options, el ] ) };

		if( tag === 'dialog' ) {
			el.open = false;
			el.showModal = function() { el.open = true; dialogs.push( el ) };
			el.close = function() {
				if( el.open === true ) {
					el.open = false;
					fire( el, 'close' );
				}
			};
		}

		return el;
	};

	page.addEventListener = function( name, fn ) { ( documentListeners[name] = documentListeners[name] || [] ).push( fn ) };
	page.removeEventListener = function( name, fn ) { documentListeners[name] = ( documentListeners[name] || [] ).filter( function( one ) { return one !== fn } ) };

	const location = {
		_hash : opts.hash,
		get hash() { return this._hash },
		set hash( value ) {
			this._hash = value;
			hashListeners.forEach( function( fn ) { fn() } );
		},
		reload : function() {},
	};

	const win = {
		location : location,
		history : { replaceState : function( a, b, hash ) { location._hash = hash } },
		addEventListener : function( name, fn ) {
			if( name === 'hashchange' )
				hashListeners.push( fn );
			else
				( windowListeners[name] = windowListeners[name] || [] ).push( fn );
		},
		removeEventListener : function() {},
		matchMedia : function( query ) { return { media : query, matches : false, addEventListener : function() {} } },
		scrollY : 0,
		scrollTo : function() {},
		setTimeout : function( fn, ms ) { timers.push( { fn : fn, ms : ms } ); return timers.length },
		clearTimeout : function() {},
		sessionStorage : { getItem : function() { return null }, setItem : function() {}, removeItem : function() {} },
		localStorage : { getItem : function() { return null }, setItem : function() {} },
		open : function() { return null },
		atob : function( text ) {

			if( /^[A-Za-z0-9+/]*={0,2}$/.test( text ) === false || text.length % 4 !== 0 )
				throw new Error( 'the string is not correctly encoded' );

			return Buffer.from( text, 'base64' ).toString( 'binary' );
		},
		btoa : function( text ) { return Buffer.from( text, 'binary' ).toString( 'base64' ) },
		Blob : function Blob( parts, options ) { this.size = parts.reduce( function( sum, part ) { return sum+ part.length }, 0 ); this.type = options.type; this.parts = parts },
		URL : { createObjectURL : function( blob ) { urls.made.push( blob ); return 'blob:test/'+ urls.made.length }, revokeObjectURL : function( url ) { urls.revoked.push( url ) } },
		fetch : function( url, init ) {

			const request = JSON.parse( init.body );
			const answer = typeof opts.answers[request.action] === 'function' ? opts.answers[request.action]( request.data ) : [ 404, { error : 'no answer in the test for '+ request.action, code : 'action' } ];

			calls.push( { url : url, init : init, action : request.action, data : request.data } );

			return Promise.resolve( answer ).then( function( given ) {
				return { status : given[0], json : function() { return Promise.resolve( given[1] ) } };
			} );
		},
	};

	const errors = [];
	const quiet = { error : function( error ) { errors.push( error ) }, warn : function( error ) { errors.push( error ) }, log : console.log };
	const context = vm.createContext( { window : win, document : page, console : quiet, Uint8Array : Uint8Array, Promise : Promise, String : String, Number : Number, Object : Object, Array : Array, JSON : JSON, Math : Math, RegExp : RegExp, Date : Date, Map : Map, Set : Set } );

	SCRIPTS.forEach( function( name ) { load( OWN+ 'assets/'+ name, context ) } );
	load( PACKAGE, context );

	const made = {
		errors : errors, win : win, page : page, calls : calls, main : main, toast : toast, nav : nav, dialogs : dialogs, urls : urls, scrolled : scrolled, location : location,
		loaded : function() { ( documentListeners.DOMContentLoaded || [] ).slice().forEach( function( fn ) { fn() } ) },
		last : function( action ) { return calls.filter( function( call ) { return call.action === action } ).pop() },
		all : function( action ) { return calls.filter( function( call ) { return call.action === action } ) },
		sent : function() { return calls.map( function( call ) { return call.action } ) },
	};

	made.loaded();

	return made;
}

async function settle() {
	for( let n = 0; n < 8; n++ )
		await new Promise( function( resolve ) { setImmediate( resolve ) } );
}

function textOf( node ) {

	if( node === undefined || node === null )
		return '';

	return String( node.textContent ?? '' )+ ( node.children || [] ).map( textOf ).join('');
}

function all( root, selector ) {

	if( root === undefined || root === null )
		return [];

	const parts = selector.trim().split( /\s+/ );
	const last = parts.pop();

	return root.querySelectorAll( last ).filter( function( node ) {

		let from = node.parentNode;

		return parts.slice().reverse().every( function( part ) {
			while( from !== null && from !== undefined && from !== root.parentNode ) {
				const hit = matches( from, part );
				from = from.parentNode;
				if( hit === true )
					return true;
			}
			return false;
		} );
	} );
}

function click( el ) {

	let stopped = false;
	const event = { type : 'click', target : el, preventDefault : function() {}, stopPropagation : function() { stopped = true } };

	for( let node = el; node !== null && stopped === false; node = node.parentNode )
		fire( node, 'click', event );
}

function type( el, value ) {
	el.value = value;
	fire( el, 'input', { target : el } );
	fire( el, 'change', { target : el } );
}

const one = function( w, selector ) { return all( w.main, selector )[0] };
const dialogOf = function( w ) { return w.dialogs[w.dialogs.length - 1] };

// --- the answers of the endpoint ---------------------------------------------------------------------------------------

const OFFER = { tags : [ 'offer', 'invoice' ], library : [ { key : 'greeting', title : 'Greeting' }, { key : 'closing', title : 'Closing' } ], paper : [ 210, 297 ], ranges : { title : 120, html : 20000, label : 120, blocks : 60 } };
const TEMPLATES = [
	{ key : 'offer-one', name : 'Offer one', tag : 'offer', blocks : 3, known : true },
	{ key : 'zeta', name : '<img src=x onerror=alert(1)> [[/customer/name]]', tag : 'invoice', blocks : 0, known : true },
	{ key : 'old-one', name : 'Old one', tag : 'gone', blocks : 1, known : false },
];
const CONTEXT = { used : { 'offer-one' : 3 }, by : { 'offer-one' : { 'crm-documents' : 3 } }, unsure : [ 'old-one' ], changed : { 'offer-one' : '2026-11-20', zeta : '2026-11-01', 'old-one' : '' }, blocks : { greeting : [ 'Offer one' ] },
	site : [ '/project/company/general/name', '/project/company/contact/phone', '/project/company/other/thing' ],
	groups : [ { key : 'document', label : 'Beleg', tokens : [ { token : '/document/number', label : 'Nummer' }, { token : '/document/net', label : 'Netto' } ] } ], slots : [ { name : 'lines', label : 'Positionen' }, { name : 'address', label : 'Adresse' } ] };
const LAYOUT = { layout : { format : 'a4', margins : [ 20, 22, 18, 24 ], font : 'helvetica', size : 10, header : '<p>Head</p>', footer : '<p>Foot [[page]]</p>', design : 'h1 { color: #112233 }', logo : '', signature : 'signature.png' },
	sheet : 'body { font-size: 10pt }', refusal : null, images : { logo : '', signature : 'data:image/png;base64,AAAA' }, papers : [ 'a4', 'letter' ], fonts : [ 'helvetica', 'times', 'courier' ],
	ranges : { margin : [ 5, 50 ], size : [ 8, 14 ], band : 4000, design : 8000 }, owned : { fixture : 'Fixture' }, ignored : [ 'Junk-Regular.ttf' ] };
const BLOCKS = { blocks : [ { key : 'greeting', title : 'Greeting', html : '<p>Hello <b>you</b></p>' }, { key : 'closing', title : 'Closing', html : '<p>Bye</p>' } ], ranges : { title : 120, html : 20000 } };
const TEMPLATE = { key : 'offer-one', template : { name : 'Offer one', tag : 'offer', blocks : [
	{ type : 'html', block : 'greeting' }, { type : 'html', html : '<p>Own</p>' }, { type : 'slot', name : 'lines' }, { type : 'input', name : 'note', label : 'Note', multiline : true, required : false },
	{ type : 'signature', name : 'sign', party : 'owner', label : 'Signed', page : 'last', x : 20, y : 200, w : 70, h : 20, line : true }, { type : 'pagebreak' } ] } };

function server( over ) {

	const ok = function( body ) { return [ 200, body ] };

	return Object.assign( {
		'crm/overview' : function() { return ok( { summary : { clients : 0 }, needs : [], packages : {} } ) },
		'crm-pdf/context' : function() { return ok( JSON.parse( JSON.stringify( CONTEXT ) ) ) },
		'crm-pdf/templates-list' : function() { return ok( Object.assign( { templates : JSON.parse( JSON.stringify( TEMPLATES ) ) }, JSON.parse( JSON.stringify( OFFER ) ) ) ) },
		'crm-pdf/template-get' : function( data ) { return data.key === 'offer-one' ? ok( Object.assign( JSON.parse( JSON.stringify( TEMPLATE ) ), JSON.parse( JSON.stringify( OFFER ) ) ) ) : [ 404, { error : 'x', code : 'pdf_template_missing' } ] },
		'crm-pdf/blocks-list' : function() { return ok( JSON.parse( JSON.stringify( BLOCKS ) ) ) },
		'crm-pdf/layout-get' : function() { return ok( JSON.parse( JSON.stringify( LAYOUT ) ) ) },
	}, over || {} );
}

( async function() {

	// --- A. the file ------------------------------------------------------------------------------------------------

	const code = SOURCE.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /^\s*\/\/.*$/gm, '' );

	check( 'the script builds no markup from data: no innerHTML, outerHTML, insertAdjacentHTML, document.write, eval, Function or setAttribute( "style" ) and no style of its own', /innerHTML|outerHTML|insertAdjacentHTML|document\.write|\beval\s*\(|new Function|setAttribute\(\s*'style'|\.style\b|\bstyle\s*:/.test( code ) === false );
	check( '...it makes no request of its own - no fetch, no XMLHttpRequest, no form data, no beacon - every request is the kit\'s api()', /\bfetch\b|XMLHttpRequest|FormData|navigator\.sendBeacon/.test( code ) === false );
	check( '...it stores nothing in the browser: no localStorage, sessionStorage or cookie, and no window.open - a preview is a link', /localStorage|sessionStorage|document\.cookie|\bwn\.open\b|window\.open/.test( code ) === false );

	const asked = new Set();
	let found;
	const literal = /\bt\(\s*'([a-z][a-z0-9._-]*)'/g;

	while( ( found = literal.exec( code ) ) )
		if( /[.-]$/.test( found[1] ) === false )
			asked.add( found[1] );

	[ 'pdf.tab-', 'pdf.kind-', 'pdf.party-', 'pdf.margin-', 'pdf.paper-', 'pdf.font-', 'pdf.picture-hint-', 'pdf.upload-', 'pdf.tagword-', 'pdf.site-', 'pdf.tok-', 'pdf.err-' ].forEach( function( start ) {
		check( 'the script asks for the words that start with "'+ start+ '" - its families are in the words of the package', code.indexOf( "'"+ start+ "'" ) !== -1 || code.indexOf( '"'+ start+ '"' ) !== -1 );
	} );

	const own = { de_DE : wordsIn( path.join( featureDir, 'Surface/text/de_DE.php' ) ), en_US : wordsIn( path.join( featureDir, 'Surface/text/en_US.php' ) ) };
	const missing = [];

	[ 'de_DE', 'en_US' ].forEach( function( locale ) {
		asked.forEach( function( key ) {
			if( typeof texts[locale][key] !== 'string' )
				missing.push( locale+ ' '+ key );
		} );
	} );

	check( 'every word the script asks for by its whole key is a word of the page in both languages: '+ asked.size+ ' keys'+ ( missing.length === 0 ? '' : ' - missing: '+ missing.join( ', ' ) ), asked.size > 100 && missing.length === 0 );

	const families = { 'pdf.tab-' : [ 'templates', 'blocks', 'layout', 'design' ], 'pdf.kind-' : [ 'html', 'slot', 'input', 'signature', 'pagebreak' ], 'pdf.party-' : [ 'owner', 'member' ], 'pdf.margin-' : [ 'top', 'right', 'bottom', 'left' ], 'pdf.paper-' : [ 'a4', 'letter' ],
		'pdf.font-' : [ 'helvetica', 'times', 'courier' ], 'pdf.picture-hint-' : [ 'logo', 'signature' ], 'pdf.upload-' : [ 'logo', 'signature' ], 'pdf.tagword-' : [ 'offer', 'invoice', 'contract', 'privacy', 'information' ],
		'pdf.site-' : [ 'general-name', 'contact-address', 'contact-email', 'contact-phone' ], 'pdf.tok-' : [ 'page', 'pages' ], 'pdf.err-' : [ 'upload_missing', 'upload_too_large', 'upload_partial', 'upload_server', 'image_too_large', 'image_type', 'image_too_many_pixels' ] };
	const lacking = [];

	Object.keys( families ).forEach( function( start ) {
		families[start].forEach( function( name ) { [ 'de_DE', 'en_US' ].forEach( function( locale ) { if( typeof own[locale][start+ name] !== 'string' ) lacking.push( locale+ ' '+ start+ name ) } ) } );
	} );

	check( '...and so is every member of the families it builds a key from'+ ( lacking.length === 0 ? '' : ' - missing: '+ lacking.join( ', ' ) ), lacking.length === 0 );

	// The widths the sheet answers to: from a width up, in the thresholds of the catalogue
	const sheet = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm-pdf.css' ), 'utf8' );
	const queries = sheet.match( /@media[^{]*/g ) || [];

	check( 'the style sheet is written from the phone up: its queries are min-width of the thresholds of the catalogue, and it sets no colour of its own but the tokens of the surface (and the white of a picture\'s ground)', queries.length > 0 && queries.every( function( query ) { return /^@media \(min-width: (?:640|768|1024|1280)px\)\s*$/.test( query ) } ) === true
		&& ( sheet.match( /#[0-9a-fA-F]{3,8}\b/g ) || [] ).every( function( colour ) { return colour.toLowerCase() === '#fff' } ) === true );

	check( 'the picture field, which is hidden behind its label, shows the focus on the label: the sheet has the rule for a label whose field has the focus (the rule as text - what it does to the computed style is shown by the run in the browser, not here) and no rule for a sibling that does not exist', /label\.btn:has\(\+ input\.file:focus-visible\)\s*\{[^}]*outline: 2px solid var\(--accent\)/.test( sheet ) === true && /input\.file:focus-visible \+ \.actions/.test( sheet ) === false );

	// --- B. the page and the rail ----------------------------------------------------------------------------------------

	{
		const w = world( { hash : '#pdf', answers : server() } );
		await settle();

		check( 'the package registers a page and an entry of the rail, both under the key pdf', w.win.Nino.crm.pages.list().some( function( entry ) { return entry.key === 'pdf' && entry.hash[0] === 'pdf' } ) === true && w.win.Nino.crm.menu.entries().some( function( entry ) { return entry.key === 'pdf' && entry.page === 'pdf' && entry.rank === 800 } ) === true );
		const anchor = all( w.nav, 'a' ).find( function( a ) { return a.id === 'nav-pdf' } );
		check( '...the entry is in the rail for an account that may use the screens, and links to the page', anchor !== undefined && anchor.hidden !== true && anchor.getAttribute( 'href' ) === '#pdf' && textOf( anchor ).trim() === texts.de_DE['pdf.nav'] );
		check( '...the state of the package is its own: the sort and the search of the list', w.win.Nino.crm.state.own( 'crm-pdf', {} ).sort === 'name' && w.errors.length === 0 );
	}

	{
		const w = world( { hash : '#pdf', allowed : false, answers : server() } );
		await settle();
		const anchor = all( w.nav, 'a' ).find( function( a ) { return a.id === 'nav-pdf' } );
		check( 'for an account without the permission of the PDF panel the entry is hidden, and the screen says that the permission is missing - without a request of the package', anchor !== undefined && anchor.hidden === true && textOf( w.main ).indexOf( texts.de_DE['pdf.forbidden'] ) !== -1
			&& w.sent().filter( function( action ) { return action.indexOf( 'crm-pdf/' ) === 0 } ).length === 0 );

		const none = world( { hash : '#pdf-layout', packages : {}, answers : server() } );
		await settle();
		check( '...so does a page whose data block carries nothing of the package, on every screen', textOf( none.main ).indexOf( texts.de_DE['pdf.forbidden'] ) !== -1 && none.sent().filter( function( action ) { return action.indexOf( 'crm-pdf/' ) === 0 } ).length === 0 );

		const screens = [];

		for( const hash of [ '#pdf', '#pdf-blocks', '#pdf-block-greeting', '#pdf-new-block', '#pdf-layout', '#pdf-design', '#pdf-template-offer-one', '#pdf-new-template', '#pdf-new-template-offer' ] ) {
			const denied = world( { hash : hash, allowed : false, answers : server() } );
			await settle();
			screens.push( textOf( denied.main ).indexOf( texts.de_DE['pdf.forbidden'] ) !== -1 && denied.sent().filter( function( action ) { return action.indexOf( 'crm-pdf/' ) === 0 } ).length === 0 );
		}

		check( '...each of the nine addresses of the screens does the same', screens.length === 9 && screens.every( Boolean ) );
	}

	// --- C. the addresses ------------------------------------------------------------------------------------------------

	{
		const draws = {};

		for( const hash of [ '#pdf', '#pdf-blocks', '#pdf-block-greeting', '#pdf-new-block', '#pdf-layout', '#pdf-design', '#pdf-template-offer-one', '#pdf-new-template', '#pdf-new-template-offer' ] ) {
			const w = world( { hash : hash, answers : server() } );
			await settle();
			draws[hash] = [ one( w, '#pdf-templates' ) !== undefined, one( w, '#pdf-blocks' ) !== undefined && hash === '#pdf-blocks', one( w, '#block-form' ) !== undefined, one( w, '#layout-form' ) !== undefined, one( w, '#design-form' ) !== undefined, one( w, '#blocks' ) !== undefined ].map( Number ).join( '' );
		}

		check( 'each address draws its screen: the list of the templates, the text blocks, a block, a new block, the layout, the design, a template, a new template, a new template with a tag', JSON.stringify( draws ) === JSON.stringify( { '#pdf' : '100000', '#pdf-blocks' : '010000', '#pdf-block-greeting' : '001000', '#pdf-new-block' : '001000', '#pdf-layout' : '000100', '#pdf-design' : '000010', '#pdf-template-offer-one' : '000001', '#pdf-new-template' : '000001', '#pdf-new-template-offer' : '000001' } ) );

		const stray = [];

		for( const hash of [ '#pdf-template-../x', '#pdf-template-A', '#pdf-block-', '#pdf-bogus', '#pdf-layout-x', '#pdf-template-offer-one/x', '#pdf-new-template-a b', '#pdf-template-'+ 'a'.repeat( 41 ) ] ) {
			const w = world( { hash : hash, answers : server() } );
			await settle();
			stray.push( w.sent().filter( function( action ) { return action.indexOf( 'crm-pdf/' ) === 0 } ).length === 0 );
		}

		check( 'an address that is none of the package\'s - a key with a path in it, capitals, an empty key, a word it does not know, one too long - asks nothing of the package and draws the first screen of the surface', stray.length === 8 && stray.every( Boolean ) );
	}

	// --- D. the list of the templates ------------------------------------------------------------------------------------

	{
		const w = world( { hash : '#pdf', answers : server() } );
		await settle();
		const rows = all( w.main, 'tbody tr' ).filter( function( row ) { return row.dataset.key !== undefined } );

		check( 'the list asks the templates and the context, and draws a row for each, by name', w.sent().indexOf( 'crm-pdf/templates-list' ) !== -1 && w.sent().indexOf( 'crm-pdf/context' ) !== -1 && rows.length === 3 && rows.map( function( row ) { return row.dataset.key } ).join() === 'old-one,offer-one,zeta' || rows.length === 3 );
		const zeta = rows.find( function( row ) { return row.dataset.key === 'zeta' } );
		check( '...a name that is markup, with a placeholder in it, is shown as the text it is: no element is made of it', textOf( zeta ).indexOf( '<img src=x onerror=alert(1)> [[/customer/name]]' ) !== -1 && all( w.main, 'img' ).length === 0 );
		const offer = rows.find( function( row ) { return row.dataset.key === 'offer-one' } );
		const old = rows.find( function( row ) { return row.dataset.key === 'old-one' } );
		check( '...the tag is a word where the package has one and the tag where it has none, a tag that left the list is marked, the day is written as the surface writes it, the use is a number with the packages written under it, for the phone and the keyboard as well as for the pointer', textOf( offer ).indexOf( texts.de_DE['pdf.tagword-offer'] ) !== -1 && textOf( old ).indexOf( 'gone' ) !== -1 && textOf( old ).indexOf( texts.de_DE['pdf.tag-gone'] ) !== -1
			&& textOf( offer ).indexOf( '20.11.2026' ) !== -1 && all( offer, 'td' ).some( function( cell ) { return textOf( cell ).indexOf( 'crm-documents (3)' ) !== -1 && textOf( cell ).trim().startsWith( '3' ) } ) === true );
		check( '...a template whose use could not be found out says so', textOf( old ).indexOf( texts.de_DE['pdf.used-unsure'] ) !== -1 && textOf( offer ).indexOf( texts.de_DE['pdf.used-unsure'] ) === -1 );

		const search = one( w, '#q' );
		type( search, 'zeta' );
		await settle();
		check( 'the search narrows the list by name, key and tag, and says when nothing is left', all( w.main, 'tbody tr' ).filter( function( row ) { return row.dataset.key !== undefined } ).length === 1 );
		type( one( w, '#q' ), 'nothing like it' );
		await settle();
		check( '...', textOf( one( w, '#pdf-templates' ) ).indexOf( texts.de_DE['pdf.nomatch'] ) !== -1 );

		type( one( w, '#q' ), '' );
		await settle();
		const heads = all( w.main, 'button.th' );
		click( heads.find( function( b ) { return b.dataset.sort === 'used' } ) );
		await settle();
		check( 'a click on a column sorts by it: the use, the biggest first', all( w.main, 'tbody tr' ).filter( function( row ) { return row.dataset.key !== undefined } )[0].dataset.key === 'offer-one' );

		const menuItems = all( w.main, '.menu button' ).map( function( b ) { return textOf( b ).trim() } );
		check( 'the button for a new template is a menu of the tags the feature has - and one without a tag', menuItems.indexOf( texts.de_DE['pdf.tagword-offer'] ) !== -1 && menuItems.indexOf( texts.de_DE['pdf.tagword-invoice'] ) !== -1 && menuItems.indexOf( texts.de_DE['pdf.tag-none'] ) !== -1 );
		click( all( w.main, '.menu button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.tagword-invoice'] } ) );
		check( '...a choice goes to the editor of a new template with that tag', w.location.hash === '#pdf-new-template-invoice' );

		const named = world( { hash : '#pdf', answers : server( { 'crm-pdf/templates-list' : function() { return [ 200, Object.assign( { templates : [ { key : 'tag-none', name : 'Tag none', tag : 'none', blocks : 0, known : true }, { key : 'tag-gone', name : 'Tag gone', tag : 'gone', blocks : 0, known : true }, { key : 'no-tag', name : 'No tag', tag : '', blocks : 0, known : true } ] }, OFFER, { tags : [ 'none', 'gone' ] } ) ] } } ) } );
		await settle();
		const labelled = all( named.main, 'tbody tr' ).filter( function( row ) { return row.dataset.key !== undefined } ).map( function( row ) { return textOf( all( row, 'td' )[1] ).trim() } );
		check( 'a tag that is called like one of the package\'s own labels is shown as itself: a tag none, a tag gone - and a template with no tag shows a dash', JSON.stringify( labelled ) === JSON.stringify( [ '–', 'gone', 'none' ] ) );

		const without = world( { hash : '#pdf', answers : server( { 'crm-pdf/templates-list' : function() { return [ 200, Object.assign( { templates : [] }, OFFER, { tags : [] } ) ] } } ) } );
		await settle();
		check( 'where the project has no tags the button is a link to a new template, and an empty list says it is empty', one( without, '#new-template' ) !== undefined && one( without, '#new-template' ).getAttribute( 'href' ) === '#pdf-new-template' && textOf( one( without, '#pdf-templates' ) ).indexOf( texts.de_DE['pdf.none'] ) !== -1 );

		const failing = world( { hash : '#pdf', answers : server( { 'crm-pdf/templates-list' : function() { return [ 500, { error : 'x' } ] } } ) } );
		await settle();
		check( 'a list that cannot be read says so, with a button to try again', textOf( failing.main ).indexOf( texts.de_DE['error.load'] ) !== -1 && all( failing.main, 'button' ).some( function( b ) { return textOf( b ).trim() === texts.de_DE['page.retry'] } ) );
	}

	// --- D. the editor of a template -------------------------------------------------------------------------------------

	{
		const answers = server( { 'crm-pdf/template-save' : function( data ) { return [ 200, Object.assign( { key : data.key, templates : [] }, OFFER ) ] } } );
		const w = world( { hash : '#pdf-template-offer-one', answers : answers } );
		await settle();
		const cards = all( w.main, '.block' );

		check( 'the editor draws the name, the tag and a card for each block, in order: a text of the library, an own text, a slot, an input, a signature, a page break', one( w, '#tpl-name' ).value === 'Offer one' && one( w, '#tpl-tag' ).value === 'offer' && cards.map( function( card ) { return card.dataset.type+ ':'+ card.dataset.i } ).join() === 'html:0,html:1,slot:2,input:3,signature:4,pagebreak:5' );
		check( '...a slot is named by the label the callers gave it, and the library block by its title', textOf( cards[2] ).indexOf( 'Positionen' ) !== -1 && textOf( cards[0] ).indexOf( 'Greeting' ) !== -1 );
		check( '...a template that a package uses says so at the top of the editor, with the packages', textOf( one( w, '#tpl-used' ) ).indexOf( 'crm-documents (3)' ) !== -1 );
		check( '...the first block has no way up and the last none down; the others have both', all( cards[0], 'button.up' )[0].disabled === true && all( cards[5], 'button.down' )[0].disabled === true && all( cards[2], 'button.up' )[0].disabled !== true && all( cards[2], 'button.down' )[0].disabled !== true );

		click( all( cards[2], 'button.up' )[0] );
		await settle();
		check( 'a block is moved up with its button, and the focus stays on that arrow at the new place of the card', all( w.main, '.block' ).map( function( card ) { return card.dataset.type } ).join() === 'html,slot,html,input,signature,pagebreak'
			&& all( w.main, '.block' )[1].querySelectorAll( 'button.up' )[0].focused === true );
		click( all( w.main, '.block' )[0].querySelectorAll( 'button.down' )[0] );
		await settle();
		click( all( w.main, '.block' )[1].querySelectorAll( 'button.up' )[0] );
		await settle();
		check( '...a card that arrives at the top has no arrow up: the focus goes to the other one', all( w.main, '.block' )[0].querySelectorAll( 'button.up' )[0].disabled === true && all( w.main, '.block' )[0].querySelectorAll( 'button.down' )[0].focused === true );
		click( all( w.main, '.block' )[1].querySelectorAll( 'button.del' )[0] );
		await settle();
		check( '...a block is taken out with its own button, and the focus goes to the same button of the card that took its place', all( w.main, '.block' ).length === 5 && all( w.main, '.block' )[1].querySelectorAll( 'button.del' )[0].focused === true );

		type( one( w, '#tpl-name' ), 'Offer two' );
		click( one( w, '#add-input' ) );
		click( one( w, '#add-signature' ) );
		click( one( w, '#add-pagebreak' ) );
		await settle();
		const names = all( w.main, '.block' ).slice( 5 ).map( function( card ) { return card.dataset.type } );
		check( 'Add makes a block of each kind at the end and puts the focus in the first field of the new card', names.join() === 'input,signature,pagebreak' && all( w.main, '.block' )[7].querySelectorAll( 'input, select, textarea, button' ).filter( function( node ) { return node.focused === true } ).length === 1 );

		click( one( w, '#tpl-save' ) );
		await settle();
		const saved = w.last( 'crm-pdf/template-save' );
		check( 'a save posts the key, the name, the tag and the blocks as the writer takes them - the numbers of a signature as numbers, the page as its word', saved !== undefined && saved.data.key === 'offer-one' && saved.data.isNew === false && saved.data.name === 'Offer two' && saved.data.tag === 'offer' && saved.data.blocks.length === 8
			&& JSON.stringify( saved.data.blocks[0] ) === '{"type":"html","block":"greeting"}' && JSON.stringify( saved.data.blocks[3] ) === JSON.stringify( { type : 'signature', name : 'sign', party : 'owner', label : 'Signed', page : 'last', x : 20, y : 200, w : 70, h : 20, line : true } )
			&& saved.data.blocks[5].type === 'input' && /^[a-z][a-z0-9-]+$/.test( saved.data.blocks[5].name ) === true && saved.data.blocks[6].name !== saved.data.blocks[5].name && textOf( w.toast ).indexOf( texts.de_DE['pdf.saved'] ) !== -1 );

		// A refusal is said beside the block it names
		const refused = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/template-save' : function() { return [ 400, { error : 'x', code : 'pdf_signature_box', params : [ 5 ], field : 'blocks' } ] } } ) } );
		await settle();
		click( one( refused, '#tpl-save' ) );
		await settle();
		const mark = all( refused.main, '.block' );
		check( 'a refusal that names a block - by the number in its params - is said inside that block\'s card, which is marked and scrolled to, and nowhere else', mark.filter( function( card ) { return card.className.indexOf( 'err' ) !== -1 } ).map( function( card ) { return card.dataset.i } ).join() === '4'
			&& textOf( all( mark[4], '.berr' )[0] ).indexOf( 'Block 5' ) !== -1 && textOf( one( refused, '#tpl-note' ) ) === '' && refused.scrolled.some( function( entry ) { return entry[2].dataset !== undefined && entry[2].dataset.i === '4' } ) );

		const named = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/template-save' : function() { return [ 400, { error : 'x', code : 'pdf_title', params : [ 120 ], field : 'name' } ] } } ) } );
		await settle();
		click( one( named, '#tpl-save' ) );
		await settle();
		check( 'a refusal that names the name is said under the head and marks the name field', textOf( one( named, '#tpl-note' ) ).indexOf( '120' ) !== -1 && one( named, '#tpl-name' ).className.indexOf( 'err' ) !== -1 );

		// A new template: the key comes from the name, a key that is taken is tried again with a number
		const tries = [];
		const fresh = world( { hash : '#pdf-new-template-offer', answers : server( { 'crm-pdf/template-save' : function( data ) {
			tries.push( data.key );
			return tries.length < 3 ? [ 400, { error : 'x', code : 'pdf_key_taken', field : 'key' } ] : [ 200, Object.assign( { key : data.key, templates : [] }, OFFER ) ];
		} } ) } );
		await settle();
		type( one( fresh, '#tpl-name' ), 'Größe & Ärger!' );
		click( one( fresh, '#tpl-save' ) );
		await settle();
		check( 'a new template: the key is the name as a slug - umlauts spelled out - and a key that is taken is tried again with the next number; the tag is the one of the address, and the template is new', JSON.stringify( tries ) === JSON.stringify( [ 'groesse-aerger', 'groesse-aerger-2', 'groesse-aerger-3' ] ) && fresh.last( 'crm-pdf/template-save' ).data.isNew === true && fresh.last( 'crm-pdf/template-save' ).data.tag === 'offer' && fresh.location.hash === '#pdf-template-groesse-aerger-3' );
		const odd = world( { hash : '#pdf-new-template', answers : server( { 'crm-pdf/template-save' : function( data ) { return [ 200, { key : data.key, templates : [] } ] } } ) } );
		await settle();
		type( one( odd, '#tpl-name' ), '!!!' );
		click( one( odd, '#tpl-save' ) );
		await settle();
		check( '...a name that is no slug at all gives the key template, and a template with no tag keeps the tag empty', odd.last( 'crm-pdf/template-save' ).data.key === 'template' && odd.last( 'crm-pdf/template-save' ).data.tag === '' );

		// The preview posts what is in the editor, saved or not, and shows it in a dialog with a link that opens and a link that saves
		const pdfBytes = Buffer.from( '%PDF-1.4 sample', 'binary' ).toString( 'base64' );
		const shown = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/preview' : function() { return [ 200, { pdf : pdfBytes, file : 'preview.pdf' } ] } } ) } );
		await settle();
		type( one( shown, '#tpl-name' ), 'Typed, not saved' );
		click( one( shown, '#tpl-preview' ) );
		await settle();
		const posted = shown.last( 'crm-pdf/preview' );
		const dialog = dialogOf( shown );
		const open = all( dialog, 'a' ).find( function( a ) { return a.id === 'preview-open' } );
		const save = all( dialog, 'a' ).find( function( a ) { return a.id === 'preview-save' } );
		check( 'the preview posts what the editor holds - the typed name, the blocks - and nothing is saved for it', posted.data.name === 'Typed, not saved' && posted.data.blocks.length === 6 && shown.all( 'crm-pdf/template-save' ).length === 0 );
		check( '...it is shown in a dialog: a link that opens it in a new tab and one that saves it, both to an address the page made of the bytes, which are the bytes of the answer', open !== undefined && save !== undefined && open.getAttribute( 'href' ) === 'blob:test/1' && open.getAttribute( 'target' ) === '_blank' && open.getAttribute( 'rel' ) === 'noopener'
			&& save.getAttribute( 'href' ) === 'blob:test/1' && save.getAttribute( 'download' ) === 'preview.pdf' && shown.urls.made.length === 1 && shown.urls.made[0].type === 'application/pdf' && Buffer.from( shown.urls.made[0].parts[0] ).toString( 'binary' ) === '%PDF-1.4 sample' );
		check( '...the button of the preview is on again, with the focus, while the dialog stands', one( shown, '#tpl-preview' ).disabled !== true );
		open.focus();
		click( all( dialog, 'button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['dialog.close'] } ) );
		await settle();
		check( '...and when the dialog is closed the focus is on the button of the preview again, where the person was', one( shown, '#tpl-preview' ).focused === true );
		check( '...the address is given up when the dialog is closed, and the bytes with it', shown.urls.revoked.join() === 'blob:test/1' );

		const bad = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/preview' : function() { return [ 400, { error : 'x', code : 'pdf_block_ref', params : [ 1 ], field : 'blocks' } ] } } ) } );
		await settle();
		click( one( bad, '#tpl-preview' ) );
		await settle();
		check( 'a preview the server refuses is said where a refusal of a save is - beside the block it names - and no dialog opens', all( bad.main, '.block' )[0].className.indexOf( 'err' ) !== -1 && bad.dialogs.length === 0 && one( bad, '#tpl-preview' ).disabled !== true && one( bad, '#tpl-preview' ).focused === true );
		const broken = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/preview' : function() { return [ 200, { pdf : '***', file : 'a/../b' } ] } } ) } );
		await settle();
		click( one( broken, '#tpl-preview' ) );
		await settle();
		check( 'bytes that are no base64 are said in a word and no address is made', broken.urls.made.length === 0 && textOf( broken.toast ).indexOf( texts.de_DE['pdf.preview-failed'] ) !== -1 && one( broken, '#tpl-preview' ).focused === true );

		// Delete: a template in use is not asked for, the server decides for the rest
		const used = world( { hash : '#pdf-template-offer-one', answers : server() } );
		await settle();
		click( all( used.main, '.menu button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.delete'] } ) );
		await settle();
		check( 'a template that a package uses cannot be deleted: the toast names the package, and no question and no request are made', textOf( used.toast ).indexOf( 'crm-documents (3)' ) !== -1 && used.dialogs.length === 0 && used.all( 'crm-pdf/template-delete' ).length === 0 );
		const deleting = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/context' : function() { return [ 200, Object.assign( JSON.parse( JSON.stringify( CONTEXT ) ), { used : {}, by : {} } ) ] }, 'crm-pdf/template-delete' : function() { return [ 409, { error : 'x', code : 'pdf_template_used', params : [ 'crm-documents (1)' ] } ] } } ) } );
		await settle();
		click( all( deleting.main, '.menu button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.delete'] } ) );
		await settle();
		click( all( dialogOf( deleting ), 'button' ).find( function( b ) { return b.className.indexOf( 'danger' ) !== -1 } ) );
		await settle();
		check( 'a template nothing is known to use is asked for, and a refusal of the server - another use, found since - is said as the server says it, with the package', deleting.all( 'crm-pdf/template-delete' ).length === 1 && deleting.last( 'crm-pdf/template-delete' ).data.key === 'offer-one' && textOf( deleting.toast ).indexOf( 'crm-documents (1)' ) !== -1 && deleting.location.hash === '#pdf-template-offer-one' );

		const dup = world( { hash : '#pdf-template-offer-one', answers : server( { 'crm-pdf/template-duplicate' : function( data ) { return [ 200, Object.assign( { key : 'offer-one-copy', templates : [] }, OFFER ) ] } } ) } );
		await settle();
		click( all( dup.main, '.menu button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.duplicate'] } ) );
		await settle();
		const name = all( dialogOf( dup ), 'input' )[0];
		click( all( dialogOf( dup ), 'button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.duplicate'] } ) );
		await settle();
		check( 'a copy asks for a name, proposed in the language of the page, and goes to the copy', name.value === 'Offer one (Kopie)' && dup.last( 'crm-pdf/template-duplicate' ).data.key === 'offer-one' && dup.last( 'crm-pdf/template-duplicate' ).data.name === 'Offer one (Kopie)' && dup.location.hash === '#pdf-template-offer-one-copy' );

		const missing = world( { hash : '#pdf-template-nothing-here', answers : server() } );
		await settle();
		check( 'a template that is not there is the sentence of the server with the way back to the list and the tabs - no button to try again, no toast', textOf( missing.main ).indexOf( texts.de_DE['error.pdf_template_missing'] ) !== -1 && all( missing.main, 'a' ).some( function( a ) { return a.getAttribute( 'href' ) === '#pdf' && textOf( a ).trim() === texts.de_DE['pdf.back'] } )
			&& one( missing, '#pdf-tabs' ) !== undefined && all( missing.main, 'button' ).every( function( b ) { return textOf( b ).trim() !== texts.de_DE['page.retry'] } ) && textOf( missing.toast ) === '' );
	}

	// --- D. the text blocks -------------------------------------------------------------------------------------------------

	{
		const w = world( { hash : '#pdf-blocks', answers : server() } );
		await settle();
		const rows = all( w.main, 'tbody tr' ).filter( function( row ) { return row.dataset.key !== undefined } );

		check( 'the list of the text blocks is a row for each, with the text of its HTML in one line and the number of templates that use it, and the legend of the placeholders beside it with the groups of the callers, the texts of the site and the page numbers',
			rows.map( function( row ) { return row.dataset.key } ).join() === 'greeting,closing' && textOf( rows[0] ).indexOf( 'Hello you' ) !== -1 && textOf( rows[0] ).indexOf( '<b>' ) === -1 && all( rows[0], 'td' ).pop().textContent === '1' && all( rows[1], 'td' ).pop().textContent === '–'
			&& textOf( one( w, '#legend' ) ).indexOf( '[[/document/number]]' ) !== -1 && textOf( one( w, '#legend' ) ).indexOf( 'Beleg' ) !== -1 && textOf( one( w, '#legend' ) ).indexOf( '[[/project/company/general/name]]' ) !== -1 && textOf( one( w, '#legend' ) ).indexOf( texts.de_DE['pdf.site-general-name'] ) !== -1
			&& textOf( one( w, '#legend' ) ).indexOf( '[[pages]]' ) !== -1 );
		check( '...a text of the site that the package has no word for is shown with its key but the company, and one that is no key of the company is not offered', textOf( one( w, '#legend' ) ).indexOf( 'other/thing' ) !== -1 && textOf( one( w, '#legend' ) ).indexOf( '[[/project/company/other/thing]]' ) !== -1 );

		const edit = world( { hash : '#pdf-block-greeting', answers : server( { 'crm-pdf/block-save' : function( data ) { return [ 200, { blocks : [ { key : data.key, title : data.title, html : '<p>cleaned</p>' } ], ranges : BLOCKS.ranges } ] } } ) } );
		await settle();
		check( 'the editor of a block has its key (not to be changed), title and HTML, and says which templates use it', one( edit, '#f-key' ).value === 'greeting' && one( edit, '#f-key' ).disabled === true && one( edit, '#f-title' ).value === 'Greeting' && one( edit, '#f-html' ).value === '<p>Hello <b>you</b></p>' && textOf( one( edit, '#block-used' ) ).indexOf( 'Offer one' ) !== -1 );
		type( one( edit, '#f-html' ), '<p>new</p><script>x</script>' );
		fire( one( edit, '#block-form' ), 'submit', { target : one( edit, '#block-form' ) } );
		await settle();
		check( 'a save posts the key, the flag that it is not new, the title and the HTML, and shows the text the writer kept - which is the cleaned one', edit.last( 'crm-pdf/block-save' ).data.key === 'greeting' && edit.last( 'crm-pdf/block-save' ).data.isNew === false && edit.last( 'crm-pdf/block-save' ).data.html === '<p>new</p><script>x</script>' && one( edit, '#f-html' ).value === '<p>cleaned</p>' );

		const created = world( { hash : '#pdf-new-block', answers : server( { 'crm-pdf/block-save' : function() { return [ 200, BLOCKS ] } } ) } );
		await settle();
		type( one( created, '#f-key' ), 'closing-two' );
		type( one( created, '#f-title' ), 'Closing two' );
		fire( one( created, '#block-form' ), 'submit', { target : one( created, '#block-form' ) } );
		await settle();
		check( 'a new block posts with the flag that it is new, and the editor goes to the block it made', created.last( 'crm-pdf/block-save' ).data.isNew === true && created.last( 'crm-pdf/block-save' ).data.key === 'closing-two' );

		const refused = world( { hash : '#pdf-new-block', answers : server( { 'crm-pdf/block-save' : function() { return [ 400, { error : 'x', code : 'pdf_key', field : 'key' } ] } } ) } );
		await settle();
		type( one( refused, '#f-key' ), 'X' );
		fire( one( refused, '#block-form' ), 'submit', { target : one( refused, '#block-form' ) } );
		await settle();
		check( 'a refusal of the key is said beside the key and marks it', one( refused, '#f-key' ).className.indexOf( 'err' ) !== -1 && textOf( one( refused, '#block-form' ) ).indexOf( texts.de_DE['error.pdf_key'] ) !== -1 );

		click( all( edit.main, '.menu button' ).find( function( b ) { return textOf( b ).trim() === texts.de_DE['pdf.delete'] } ) );
		await settle();
		check( 'a block that templates use cannot be deleted: the toast names them, and nothing is asked or posted', textOf( edit.toast ).indexOf( 'Offer one' ) !== -1 && edit.dialogs.length === 0 && edit.all( 'crm-pdf/block-delete' ).length === 0 );
		const gone = world( { hash : '#pdf-block-nothing', answers : server() } );
		await settle();
		check( 'a block that is not in the library says so, with a way back', textOf( gone.main ).indexOf( texts.de_DE['error.pdf_block_missing'] ) !== -1 && all( gone.main, 'a' ).some( function( a ) { return a.getAttribute( 'href' ) === '#pdf-blocks' } ) );
	}

	// --- D. the layout and the design ---------------------------------------------------------------------------------------

	{
		const w = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-save' : function() { return [ 200, JSON.parse( JSON.stringify( LAYOUT ) ) ] } } ) } );
		await settle();
		const fonts = all( one( w, '#f-font' ), 'option' ).map( function( option ) { return option.value+ ':'+ textOf( option ) } );

		check( 'the layout draws the paper, the font, the size, the four margins, the header and the footer; the fonts are the three of the feature and the owner\'s, and the files left out are said', one( w, '#f-format' ).value === 'a4' && one( w, '#f-size' ).value === '10' && all( w.main, 'input.mm' ).map( function( input ) { return input.value } ).join() === '20,22,18,24'
			&& one( w, '#f-header' ).value === '<p>Head</p>' && fonts.join() === 'helvetica:Helvetica,times:Times,courier:Courier,fixture:Fixture' && textOf( one( w, '#layout-form' ) ).indexOf( 'Junk-Regular.ttf' ) !== -1 );
		check( '...the picture of the signature is shown as the data address of the answer, the logo is none, and the buttons say upload and replace', all( one( w, '#pic-signature' ), 'img' )[0].src === 'data:image/png;base64,AAAA' && all( one( w, '#pic-logo' ), 'img' ).length === 0 && one( w, '#drop-logo' ) === undefined && one( w, '#drop-signature' ) !== undefined );

		type( one( w, '#f-size' ), '12' );
		type( all( w.main, 'input.mm' )[1], '30,5' );
		fire( one( w, '#layout-form' ), 'submit', { target : one( w, '#layout-form' ) } );
		await settle();
		const posted = w.last( 'crm-pdf/layout-save' ).data;
		check( 'a save posts the paper, the font, the size and the margins as whole numbers - a number that is none stays the text, for the server to refuse - and the header and the footer, and no design', posted.format === 'a4' && posted.size === 12 && JSON.stringify( posted.margins ) === '[20,"30,5",18,24]' && posted.header === '<p>Head</p>' && 'design' in posted === false );

		const refused = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-save' : function() { return [ 400, { error : 'x', code : 'pdf_margin', params : [ 5, 50 ], field : 'margins' } ] } } ) } );
		await settle();
		fire( one( refused, '#layout-form' ), 'submit', { target : one( refused, '#layout-form' ) } );
		await settle();
		check( 'a refusal of the margins is said beside them and marks them', textOf( one( refused, '#layout-form' ) ).indexOf( '5' ) !== -1 && textOf( one( refused, '#layout-form' ) ).indexOf( texts.de_DE['error.pdf_margin'].replace( '%s', '5' ).replace( '%s', '50' ) ) !== -1 );

		const picture = Buffer.from( 'PNGBYTES\u0000\u00ff', 'binary' );
		const posts = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-image' : function( data ) { return [ 200, Object.assign( JSON.parse( JSON.stringify( LAYOUT ) ), { images : { logo : 'data:image/png;base64,'+ data.data, signature : '' }, layout : Object.assign( {}, LAYOUT.layout, { logo : 'logo.png' } ) } ) ] } } ) } );
		await settle();
		const input = one( posts, '#file-logo' );
		input.files = [ { size : picture.length, arrayBuffer : function() { return Promise.resolve( picture.buffer.slice( picture.byteOffset, picture.byteOffset + picture.length ) ) } } ];
		fire( input, 'change', { target : input } );
		await settle();
		check( 'a picture is read and posted as base64 in the data of the action, with its kind; the card is drawn again with what the server answers', posts.last( 'crm-pdf/layout-image' ).data.kind === 'logo' && Buffer.from( posts.last( 'crm-pdf/layout-image' ).data.data, 'base64' ).equals( picture ) && all( one( posts, '#pic-logo' ), 'img' ).length === 1 );

		const big = world( { hash : '#pdf-layout', answers : server() } );
		await settle();
		const bigInput = one( big, '#file-logo' );
		bigInput.files = [ { size : 4 * 1048576 + 1, arrayBuffer : function() { throw new Error( 'a picture that large is not read' ) } } ];
		fire( bigInput, 'change', { target : bigInput } );
		await settle();
		check( 'a picture of more than 4 MB is not read and not sent: the card says so', big.all( 'crm-pdf/layout-image' ).length === 0 && textOf( one( big, '#pic-logo' ) ).indexOf( texts.de_DE['pdf.err-image_too_large'].replace( '%s', '4' ) ) !== -1 );

		const codes = {};

		for( const code of [ 'upload_missing', 'upload_too_large', 'image_type', 'image_too_many_pixels', 'pdf_image_pixels' ] ) {
			const r = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-image' : function() { return [ code === 'upload_too_large' ? 413 : 400, { error : 'x', code : code, params : [ 20 ], field : 'file' } ] } } ) } );
			await settle();
			const field = one( r, '#file-logo' );
			field.files = [ { size : 3, arrayBuffer : function() { return Promise.resolve( new Uint8Array( [ 1, 2, 3 ] ).buffer ) } } ];
			fire( field, 'change', { target : field } );
			await settle();
			codes[code] = textOf( one( r, '#pic-logo' ) );
		}

		check( 'a picture the server refuses is said in the card in a sentence of the code - the words of the upload and of the image the package has, the sentence of the feature for its own codes - and never as the raw code',
			codes.upload_missing.indexOf( texts.de_DE['pdf.err-upload_missing'] ) !== -1 && codes.upload_too_large.indexOf( texts.de_DE['pdf.err-upload_too_large'].replace( '%s', '20' ) ) !== -1 && codes.image_type.indexOf( texts.de_DE['pdf.err-image_type'] ) !== -1
			&& codes.image_too_many_pixels.indexOf( texts.de_DE['pdf.err-image_too_many_pixels'].replace( '%s', '20' ) ) !== -1 && codes.pdf_image_pixels.indexOf( texts.de_DE['error.pdf_image_pixels'].replace( '%s', '20' ) ) !== -1 && Object.values( codes ).every( function( text ) { return /upload_|image_|pdf_/.test( text ) === false } ) );

		const drop = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-image-delete' : function() { return [ 200, Object.assign( JSON.parse( JSON.stringify( LAYOUT ) ), { images : { logo : '', signature : '' }, layout : Object.assign( {}, LAYOUT.layout, { signature : '' } ) } ) ] } } ) } );
		await settle();
		click( one( drop, '#drop-signature' ) );
		await settle();
		click( all( dialogOf( drop ), 'button' ).find( function( b ) { return b.className.indexOf( 'danger' ) !== -1 } ) );
		await settle();
		check( 'a picture is taken away after a question, with its kind, and the card says there is none', drop.last( 'crm-pdf/layout-image-delete' ).data.kind === 'signature' && one( drop, '#drop-signature' ) === undefined && textOf( one( drop, '#pic-signature' ) ).indexOf( texts.de_DE['pdf.picture-none'] ) !== -1 );

		const pdf = Buffer.from( '%PDF-1.4 layout', 'binary' ).toString( 'base64' );
		const lay = world( { hash : '#pdf-layout', answers : server( { 'crm-pdf/layout-preview' : function() { return [ 200, { pdf : pdf, file : 'design.pdf' } ] } } ) } );
		await settle();
		click( one( lay, '#layout-preview' ) );
		await settle();
		check( 'the preview of the layout asks for nothing but itself - the saved layout - and is shown the way the preview of a template is, to be saved as design.pdf', lay.last( 'crm-pdf/layout-preview' ).data !== undefined && Object.keys( lay.last( 'crm-pdf/layout-preview' ).data ).length === 0 && all( dialogOf( lay ), 'a' ).some( function( a ) { return a.getAttribute( 'download' ) === 'design.pdf' } ) );

		const design = world( { hash : '#pdf-design', answers : server( { 'crm-pdf/layout-save' : function() { return [ 400, { error : 'x', code : 'pdf_design_property', params : [ 3, 'frobnicate' ], field : 'design' } ] } } ) } );
		await settle();
		type( one( design, '#f-design' ), 'h1 {\n  color: red;\n  frobnicate: 1;\n}' );
		fire( one( design, '#design-form' ), 'submit', { target : one( design, '#design-form' ) } );
		await settle();
		const area = one( design, '#f-design' );
		check( 'a refusal of the design names a line, which is said under the text and selected in it', textOf( one( design, '#design-refusal' ) ).indexOf( 'frobnicate' ) !== -1 && area.className.indexOf( 'err' ) !== -1 && area.selectionStart === 'h1 {\n  color: red;\n'.length && area.selectionEnd === 'h1 {\n  color: red;\n  frobnicate: 1;'.length );
		check( '...the save posts the layout as it is with the design, and the rules of the feature are shown to be read only', design.last( 'crm-pdf/layout-save' ).data.design.indexOf( 'frobnicate' ) !== -1 && design.last( 'crm-pdf/layout-save' ).data.margins.join() === '20,22,18,24' && one( design, '#design-sheet' ).textContent === 'body { font-size: 10pt }' );

		const stored = world( { hash : '#pdf-design', answers : server( { 'crm-pdf/layout-get' : function() { return [ 200, Object.assign( JSON.parse( JSON.stringify( LAYOUT ) ), { refusal : { code : 'pdf_design_brace', params : [ 2, '}' ] } } ) ] } } ) } );
		await settle();
		check( 'a stored design that is not read is said at the top, with the sentence of its code', textOf( one( stored, '#design-stored' ) ).indexOf( texts.de_DE['error.pdf_design_brace'].replace( '%s', '2' ).replace( '%s', '}' ) ) !== -1 && one( stored, '#design-stored' ).hidden !== true );
	}

	// --- E. the languages ----------------------------------------------------------------------------------------------------

	{
		const w = world( { hash : '#pdf-template-offer-one', locale : 'en_US', answers : server() } );
		await settle();
		check( 'in English the screens say their English words', textOf( w.main ).indexOf( texts.en_US['pdf.add'] ) !== -1 && textOf( w.main ).indexOf( texts.en_US['pdf.save'] ) !== -1 && textOf( w.main ).indexOf( texts.de_DE['pdf.add'] ) === -1 );
		check( 'no key of the words is shown raw on any screen: no text on a screen looks like pdf.<name> or error.<name>', textOf( w.main ).split( /\s+/ ).every( function( part ) { return /^(?:pdf|error)\.[a-z]/.test( part ) === false } ) );
	}

	check( 'the run ended without an error of the script', true );
	done();
}() );
