/**
 *	Nino features
 *	crm-surface-js-smoke.js		What the script of the surface does (the five files of Surface/assets: crm-format.js, crm-kit.js, crm-points.js, crm-screens.js, crm-boot.js) over the stand-in of the kernel's tests/harness.js: the real
 *								files, the real words (Surface/text), the answers of the endpoint as the server of the core gives them.
 *
 *								A. the files: each is a script of its own that passes, none builds markup from data, sets a style or names a package; the names of the kit; what the files share while they load is gone after the start
 *								B. Nino.crm.format alone: money, rates, days and times in both languages, loaded with nothing else
 *								C. the screens of the core from fixtures: the overview, the clients (list, card, new), the projects (list, card, new), the values, the settings, the setup, the app of the tasks - their data on the screen
 *								D. the registration points through a probe package: a menu entry, a page, a tile and a block of the overview, a need, a tab and a head of the cards, a mark and a column of a list, a section and a tab of the settings,
 *								   a step of the setup, a source of hits, a key, a kind of the logbook, a line of the test phase, a part of a form, a record kind, an app, the state of a package, the kit - and none of them without it
 *								E. the keys, the router and the safety of what a person typed
 *
 *								The helpers - check(), done(), fills(), load(), findAll(), element(), fire() and documentOf() - are those of tests/harness.js of the checkout, three levels up or the one NINO_ROOT names.
 *
 *	Usage: node features/Crm/tests/crm-surface-js-smoke.js
 *	       NINO_ROOT=../nino node features/Crm/tests/crm-surface-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, setImmediate */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, load, findAll, fire, element, documentOf, matches } = require( path.join( root, 'tests/harness.js' ) );

// A promise that is rejected and that no test waits for is a failed check with its own line - not the end of the run
process.on( 'unhandledRejection', function( error ) {
	check( 'no promise of the script is left rejected: '+ String( error && error.message )+ ( process.env.CRM_TRACE ? ' '+ String( error && error.stack ).split( '\n' ).slice( 0, 4 ).join( ' | ' ) : '' ), false );
	process.exitCode = 1;
} );

const featureDir = path.join( __dirname, '..' );
const OWN = path.relative( root, path.join( featureDir, 'Surface' ) )+ '/';
const SCRIPTS = [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ];

/**
 *	@param		{string}	locale
 *
 *	@return		{Object}									The flat words of the surface in a language, as the page gets them
 */
function wordsOf( locale ) {

	const map = {};
	const re = /^\t'([a-z0-9._-]+)' => '((?:[^'\\]|\\.)*)',$/gm;
	const text = fs.readFileSync( path.join( featureDir, 'Surface/text/'+ locale+ '.php' ), 'utf8' );
	let found;

	while( ( found = re.exec( text ) ) )
		map[found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return map;
}

const texts = wordsOf( 'de_DE' );
const textsEn = wordsOf( 'en_US' );

/**
 *	@param		{string}	locale
 *
 *	@return		{Object}									The sentences of the refusals of the endpoint that the server of the surface puts among the words of the page: error.<code>, from the words of the feature
 */
function serverWordsOf( locale ) {

	const map = {};
	const re = /^\t'\[\[\/_admin\/error\/([a-z0-9_]+)\]\]' => '((?:[^'\\]|\\.)*)',$/gm;
	const text = fs.readFileSync( path.join( featureDir, 'text/'+ locale+ '.php' ), 'utf8' );
	let found;

	while( ( found = re.exec( text ) ) )
		map['error.'+ found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return map;
}

const serverWords = { de_DE : serverWordsOf( 'de_DE' ), en_US : serverWordsOf( 'en_US' ) };

/**
 *	@param		{string}	name
 *
 *	@return		{string}									The source of a script of the surface
 */
function scriptOf( name ) {
	return fs.readFileSync( path.join( featureDir, 'Surface/assets/'+ name ), 'utf8' );
}

const pageScript = SCRIPTS.map( scriptOf ).join( '\n' );

// --- the world: the page, the browser and the endpoint as stand-ins ------------------------------------------------

function world( options ) {

	const opts = Object.assign( { hash : '', answers : {}, perms : undefined, pdfAvailable : undefined, blocked : false }, options || {} );
	const calls = [];
	const timers = [];
	const hashListeners = [];
	const documentListeners = {};
	const windowListeners = {};
	const scrolled = [];
	const scrolls = [];
	const dialogs = [];
	const opened = [];
	const downloads = [];
	const storage = {};
	const local = opts.store === undefined ? new Map() : opts.store;
	const reloads = [];
	const tabs = [];
	const queries = [];
	const mediaListeners = [];
	let narrow = opts.phone === true;

	const mount = element('div');
	mount.id = 'root';

	// The rail is empty: the script draws it from the entries of the core, of the features and of the page
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
	block.textContent = JSON.stringify( { base : '', csrf : 'token-1', today : opts.today ?? '2026-11-24', locale : opts.locale ?? 'de_DE', user : { name : 'crm@example.com' }, app : opts.app, perm : '/_admin/crm/manage', perms : opts.perms === undefined ? { crm : true, elements : true, text : true } : opts.perms, device : opts.device, menu : opts.menu, texts : Object.assign( {}, ( opts.locale ?? 'de_DE' ) === 'de_DE' ? texts : textsEn, serverWords[opts.locale ?? 'de_DE'] ), links : { workbench : '/_admin/', site : '/', users : '/_admin/#users' }, packages : opts.packages ?? {} } );
	mount.appendChild( block );

	const page = documentOf( mount );
	const make = page.createElement;

	// The page as it loads: still parsing, so the script waits for DOMContentLoaded (the world fires it after the scripts of the packages have run, unless a test starts it itself)
	page.readyState = 'loading';

	page.createElement = function( tag ) {
		const el = make( tag );

		// A form submits as the browser's requestSubmit() does: its listeners of submit hear it
		if( tag === 'form' )
			el.requestSubmit = function() { fire( el, 'submit', { target : el, preventDefault : function() {} } ) };

		// A link that is clicked is a download the page asked for: its address is kept, and nothing is loaded
		if( tag === 'a' ) {
			const press = el.click;
			el.click = function() { downloads.push( el.getAttribute( 'href' ) ); return press === undefined ? undefined : press.call( el ) };
		}

		// A node that is appended where it already stands elsewhere moves, as in a browser
		const attach = el.appendChild;

		el.appendChild = function( child ) {
			const old = child.parentNode === null || child.parentNode === undefined ? -1 : child.parentNode.children.indexOf( child );

			if( old !== -1 )
				child.parentNode.children.splice( old, 1 );

			// A browser takes the focus from a field that is taken out of the page
			const holds = function( node, target ) { return node === target || ( node.children || [] ).some( function( kid ) { return holds( kid, target ) } ) };

			if( old !== -1 && page.activeElement !== null && page.activeElement !== undefined && holds( child, page.activeElement ) ) {
				page.activeElement.focused = false;

				if( typeof page.activeElement.selectionStart === 'number' ) {
					page.activeElement.selectionStart = 0;
					page.activeElement.selectionEnd = 0;
				}
				page.activeElement = null;
			}

			return attach( child );
		};

		// Where a test asks for a layout the nodes have one: a box of 90 in steps of 100 in a row of 300, as the pills of a strip need it
		if( opts.geometry === true ) {
			Object.defineProperty( el, 'offsetLeft', { get : function() { return el.parentNode === null || el.parentNode === undefined ? 0 : el.parentNode.children.indexOf( el ) * 100 } } );
			el.offsetWidth = 90;
			el.clientWidth = 300;
			el.scrollLeft = 0;
		}

		// A node that is scrolled to says so; the browser's own box of a node is none here
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
		reload : function() { reloads.push( 1 ) },
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
		removeEventListener : function( name, fn ) { windowListeners[name] = ( windowListeners[name] || [] ).filter( function( one ) { return one !== fn } ) },
		// The width the script asks about: a phone where the test says so, and the change of it that a test makes
		matchMedia : function( query ) { queries.push( query ); return { media : query, get matches() { return query === '(display-mode: standalone)' ? opts.standalone === true : narrow }, addEventListener : function( name, fn ) { if( name === 'change' ) mediaListeners.push( fn ) } } },
		scrollY : 0,
		scrollTo : function( x, y ) { scrolls.push( [ x, y ] ) },
		setTimeout : function( fn, ms ) { timers.push( { fn : fn, ms : ms } ); return timers.length },
		clearTimeout : function( id ) { if( id > 0 && timers[id - 1] ) timers[id - 1].cleared = true },
		sessionStorage : { getItem : function( key ) { return storage[key] ?? null }, setItem : function( key, value ) { storage[key] = value }, removeItem : function( key ) { delete storage[key] } },
		// The storage of the device: a map the test can hold on to, or one that throws as a browser with the storage shut does
		localStorage : { getItem : function( key ) { if( opts.brokenStore === true ) throw new Error('shut'); return local.has( key ) ? local.get( key ) : null }, setItem : function( key, value ) { if( opts.brokenStore === true ) throw new Error('shut'); local.set( key, String( value ) ) } },
		open : function( url, target, features ) { opened.push( [ url, target, features ] ); if( opts.blocked === true ) return null; const tab = { opener : 'window' }; tabs.push( tab ); return tab; },
		// A picture is posted as a form: the action, the data and the file as its fields
		navigator : opts.clipboard === undefined && opts.iosStandalone !== true ? undefined : Object.assign( opts.clipboard === undefined ? {} : { clipboard : opts.clipboard }, opts.iosStandalone === true ? { standalone : true } : {} ),
		FormData : function FormData() {
			const fields = [];
			this.fields = fields;
			this.append = function( name, value ) { fields.push( [ name, value ] ) };
			this.get = function( name ) { const one = fields.find( function( field ) { return field[0] === name } ); return one === undefined ? null : one[1] };
		},
		fetch : function( url, init ) {

			const form = init.body instanceof win.FormData;
			const request = form ? { action : init.body.get( 'action' ), data : JSON.parse( init.body.get( 'data' ) ), file : init.body.get( 'file' ) ?? init.body.get( 'chunk' ), part : init.body.get( 'chunk' ) === null ? 'file' : 'chunk' } : ( url.endsWith( '/crm/api' ) === true ? JSON.parse( init.body ) : { action : url, data : JSON.parse( init.body ) } );
			const answer = typeof opts.answers[request.action] === 'function' ? opts.answers[request.action]( request.data, request.file ) : [ 404, { error : 'no answer in the test for '+ request.action, code : 'action' } ];

			calls.push( { url : url, init : init, action : request.action, data : request.data, file : request.file, part : request.part, form : form } );

			// An answer may be a promise of one, which the test keeps back until it lets it go
			return Promise.resolve( answer ).then( function( given ) {

				if( given === 'offline' )
					throw new Error('offline');

				return { status : given[0], json : function() { return Promise.resolve( given[1] ) } };
			} );
		},
	};

	const errors = [];
	const quiet = { error : function( error ) { errors.push( error ) }, warn : function( error ) { errors.push( error ) }, log : console.log };
	const context = vm.createContext( { window : win, document : page, console : quiet } );

	// A test that pins the day gives the moment it takes: the page draws that moment, not the one the clock shows when it draws, so that the test cannot fall on either side of midnight
	if( typeof opts.today === 'number' )
		vm.runInContext( 'Date = class extends Date { constructor( ...given ) { given.length === 0 ? super( '+ opts.today+ ' ) : super( ...given ) } }', context );

	SCRIPTS.forEach( function( name ) { load( OWN+ 'assets/'+ name, context ) } );

	// A test that plays a script of a package gives it here, to run after the core and before the page starts
	if( typeof opts.package === 'function' )
		opts.package( win, context );

	const app = win.Nino.crm.app;

	const made = {
		errors : errors, app : app, win : win, page : page, calls : calls, tabs : tabs, main : main, toast : toast, dialogRoot : dialogRoot, nav : nav, timers : timers, dialogs : dialogs, opened : opened, downloads : downloads, reloads : reloads, storage : storage, answers : opts.answers,
		location : location, documentListeners : documentListeners, windowListeners : windowListeners, scrolled : scrolled, scrolls : scrolls,
		queries : queries, local : local,
		send : function( name, event ) { ( windowListeners[name] || [] ).forEach( function( fn ) { fn( event ) } ) },
		setPhone : function( on ) { narrow = on; mediaListeners.forEach( function( fn ) { fn() } ) },
		loaded : function() { ( documentListeners.DOMContentLoaded || [] ).slice().forEach( function( fn ) { fn() } ) },
		last : function( action ) { return calls.filter( function( call ) { return call.action === action } ).pop() },
		all : function( action ) { return calls.filter( function( call ) { return call.action === action } ) },
		sent : function() { return calls.map( function( call ) { return call.action } ) },
	};

	// A test that plays a script of a feature gives it here, to run before the page starts
	if( typeof opts.before === 'function' )
		opts.before( made );

	if( opts.start !== false )
		made.loaded();

	return made;
}

/**
 *	Let the promises of the script settle
 *
 *	@return		{Promise}
 */
async function settle() {
	for( let n = 0; n < 8; n++ )
		await new Promise( function( resolve ) { setImmediate( resolve ) } );
}

/**
 *	@param		{Object}	node
 *
 *	@return		{string}									The text of a node and of everything under it
 */
function textOf( node ) {

	// What is not there says nothing: the check that asked for it fails on its own line
	if( node === undefined || node === null )
		return '';

	return String( node.textContent ?? '' )+ ( node.children || [] ).map( textOf ).join('');
}

/**
 *	@param		{Object}	root
 *	@param		{string}	selector				A tag, #id, .class or [data-x="y"], alone or one after the other
 *
 *	@return		{Object[]}
 */
function all( root, selector ) {

	const parts = selector.trim().split( /\s+/ );
	const last = parts.pop();

	// A screen that was not drawn has no node to look in: nothing is found, and the check that asked fails on its own line
	if( root === undefined || root === null )
		return [];

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

/**
 *	A click: the listeners of the element and of the ones above it, as far as it is not stopped, then the document's
 *
 *	@param		{Object}	w
 *	@param		{Object}	el
 *
 *	@return		{void}
 */
function click( w, el ) {

	let stopped = false;
	let prevented = false;
	const event = { type : 'click', target : el, preventDefault : function() { prevented = true }, stopPropagation : function() { stopped = true } };

	for( let node = el; node !== null && stopped === false; node = node.parentNode )
		fire( node, 'click', event );

	( w.documentListeners.click || [] ).forEach( function( fn ) { fn( event ) } );

	return prevented;
}

/**
 *	A person types into a field: the value, then input; the change and the blur follow where the field is left
 *
 *	@param		{Object}	el
 *	@param		{string}	value
 *	@param		{boolean}	[leave]					false to type without leaving
 *
 *	@return		{void}
 */
function type( el, value, leave ) {
	el.value = value;
	fire( el, 'input', { target : el } );

	if( leave !== false ) {
		fire( el, 'change', { target : el } );
		fire( el, 'blur', { target : el } );
	}
}

/**
 *	@param		{Object}	w
 *	@param		{string}	key
 *	@param		{Object}	[target]
 *
 *	@return		{boolean}									Whether the key was kept from its default
 */
function press( w, key, target, mods ) {
	let prevented = false;
	( w.documentListeners.keydown || [] ).forEach( function( fn ) { fn( Object.assign( { type : 'keydown', key : key, target : target || null, preventDefault : function() { prevented = true } }, mods || {} ) ) } );
	return prevented;
}


/**
 *	@param		{Object}	node
 *	@param		{string}	value
 *
 *	@return		{void}
 */
function selectValue( node, value ) {
	node.value = value;
	fire( node, 'change', { target : node } );
}

const inMainOne = function( w, selector ) { return all( w.main, selector )[0] };

// --- fixtures: what the server of the core answers --------------------------------------------------------------------

const CLIENTS = [
	{ id : 1, number : 'K-0001', title : 'Nordlicht Records GmbH', company : 'Nordlicht Records GmbH', name : 'Lena Brandt', street : 'Hafenstraße 12', zip : '20457', city : 'Hamburg', country : 'DE', email : 'lena@nordlicht.example', phone : '+49 40 123456', vat_id : 'DE123456789', buyer_reference : '', business : true, active : true, notes : 'Prefers mail', created : '2026-01-05T10:00:00Z', updated : '2026-01-05T10:00:00Z' },
	{ id : 2, number : 'K-0002', title : 'Tom Weiss', company : '', name : 'Tom Weiss', street : '', zip : '', city : 'Berlin', country : 'AT', email : 'tom@example.org', phone : '', vat_id : '', buyer_reference : '', business : false, active : false, notes : '', created : '2026-02-01T10:00:00Z', updated : '2026-02-01T10:00:00Z' },
	{ id : 3, number : 'K-0003', title : '', company : '', name : '', street : '', zip : '', city : '', country : 'DE', email : '', phone : '', vat_id : '', buyer_reference : '', business : false, active : true, notes : '', created : '2026-03-01T10:00:00Z', updated : '2026-03-01T10:00:00Z' },
];

const PHASES = [ { key : 'recording', title : 'Aufnahme', order : 10 }, { key : 'mix', title : '', order : 20 } ];

const PROJECTS = [
	{ id : '12', title : 'Winter EP', client : 'K-0001', description : 'Four songs\nand a cover', started : '2026-02-02', done : false, phase : 'mix', favorite : false, client_id : 1, client_title : 'Nordlicht Records GmbH', client_active : true },
	{ id : '9', title : 'Old single', client : 'K-0001', description : '', started : '2025-05-01', done : true, phase : '', favorite : false, client_id : 1, client_title : 'Nordlicht Records GmbH', client_active : true },
	{ id : '14', title : '', client : 'K-0002', description : '', started : '', done : false, phase : 'recording', favorite : false, client_id : 2, client_title : 'Tom Weiss', client_active : false },
];

const TASKS = [ { id : 1, text : 'Mix the second song', done : false, done_at : '', created_at : '2026-03-02T09:00:00Z', source : '', ref : '' }, { id : 2, text : 'Send the cover', done : true, done_at : '2026-03-03T09:00:00Z', created_at : '2026-03-01T09:00:00Z', source : '', ref : '' } ];

const NOTIFICATIONS = { switches : {}, owner : 'owner@example.com', sender : 'noreply@example.com', cap : 0 };

const STATUS_LINES = [
	{ key : 'database', hint : false, fixable : true, state : 'missing' },
	{ key : 'writable', hint : false, fixable : false, state : 'ok' },
	{ key : 'schema', hint : false, fixable : true, state : 'behind', version : 1, stored : 0, lacks : [ 'crm-probe' ] },
	{ key : 'addresses', hint : true, fixable : false, state : 'default', crm : '', app : '' },
	{ key : 'mail', hint : false, fixable : true, state : 'ok' },
];

/**
 *	The answers of the endpoint for the screens of the core: action => ( data ) => [ status, body ]
 *
 *	@param		{Object}	[over]
 *
 *	@return		{Object}
 */
function server( over ) {

	const ok = function( body ) { return [ 200, body ] };

	return Object.assign( {
		'crm/overview' : function() { return ok( { summary : { clients : 2 }, needs : [], packages : {} } ) },
		'crm/status' : function() { return ok( { lines : STATUS_LINES.map( function( line ) { return Object.assign( {}, line, { state : 'ok', fixable : false } ) } ), whole : true, open : { address : '/crm' }, orphans : [] } ) },
		'crm-clients/list' : function() { return ok( { clients : CLIENTS, erasing : [] } ) },
		'crm-clients/get' : function( data ) { const one = CLIENTS.find( function( client ) { return String( client.id ) === data.id } ); return one === undefined ? [ 404, { error : 'x', code : 'crm_clients_unknown' } ] : ok( { client : one } ) },
		'crm-clients/save' : function( data ) { return ok( { client : Object.assign( {}, CLIENTS[0], data, { id : data.id === undefined ? 9 : Number( data.id ), number : data.id === undefined ? 'K-0009' : 'K-0001' } ), packages : {} } ) },
		'crm-clients/active' : function( data ) { return ok( { client : Object.assign( {}, CLIENTS[1], { active : data.active } ), packages : {} } ) },
		'crm-clients/delete-preview' : function() { return ok( { preview : { found : true, number : 'K-0001', deletable : true, reasons : [], counts : { projects : 2, shares : 0, received : 1, records : 0, logbook : 3, tasks : 4, weird : 5 }, shared : [], features : [], permitted : true } } ) },
		'crm-projects/list' : function( data ) { return ok( { missing : false, native : 'de_DE', phases : PHASES, projects : PROJECTS.filter( function( one ) { return data.client === undefined || String( one.client_id ) === data.client } ), shared : data.client === undefined ? undefined : [ { id : '14', title : 'Shared EP', owner_id : 2, owner : 'K-0002', owner_title : 'Tom Weiss', since : '2026-03-05', source : '' } ] } ) },
		'crm-projects/get' : function( data ) {
			const project = PROJECTS.find( function( one ) { return one.id === data.id } );
			return project === undefined ? [ 404, { error : 'x', code : 'crm_projects_unknown' } ] : ok( { native : 'de_DE', phases : PHASES, project : project, waiting : null, client : { id : project.client_id, number : project.client, title : project.client_title, active : project.client_active }, tasks : 1, shares : [] } );
		},
		'crm-projects/phase' : function() { return ok( { id : '12', phase : 'mix', favorite : false } ) },
		'crm-tasks/list' : function() { return ok( { tasks : TASKS } ) },
		'crm-tasks/open' : function() { return ok( { projects : [ { id : '12', label : 'P-0012', title : 'Winter EP', client_title : 'Nordlicht', open : 1 } ], others : [] } ) },
		'crm-logbook/list' : function() { return ok( { entries : [ { id : 3, at : '2026-03-02T09:00:00Z', source : 'crm', kind : 'client-added', text : '', by : 'owner@example.com', data : {}, project : '', project_label : '', project_known : false }, { id : 2, at : '2026-03-01T09:00:00Z', source : 'crm', kind : 'note', text : 'Called back', by : 'system', data : {}, project : '12', project_label : 'P-0012 Winter EP', project_known : true }, { id : 1, at : '2026-02-01T09:00:00Z', source : 'crm', kind : 'project-unshared', text : '', by : 'owner@example.com', data : { label : 'P-0012', client : 'K-0002', owner : true }, project : '', project_label : '', project_known : false }, { id : 0, at : '2026-01-01T09:00:00Z', source : 'docs', kind : 'document-sent', text : '', by : 'system', data : {}, project : '', project_label : '', project_known : false } ], more : false } ) },
		'crm/settings-get' : function() { return ok( { settings : { start_client : '1', start_project : '1', seller_name : '', brand_word : 'Studio', brand_domain : '', brand_color : '', brand_ink : '', crm_host : '', app_host : '' }, addresses : [ { key : 'app', address : 'https://example.org/_admin' }, { key : 'crm', address : 'https://example.org/crm' } ], next : { client : 'K-0004', project : 'P-0015' }, company : {}, packages : {}, wipe : { steps : [ 'probe', 'strange' ] } } ) },
		'crm/notifications' : function() { return ok( NOTIFICATIONS ) },
		'text/keys' : function() { return ok( { keys : [ { key : '/project/company/general/name', global : false, writable : true, values : { de_DE : 'Studio Nord', en_US : 'Studio North' } } ], locales : [ { locale : 'de_DE' }, { locale : 'en_US' } ], native : 'de_DE' } ) },
		'elements/types' : function() { return ok( { types : [ { type : 'crm-phases', title : 'Phasen', descr : '', model : { title : { type : 'string', locale : true, required : true, maxlength : 60, label : 'Titel' }, order : { type : 'integer', label : 'Reihenfolge' } }, rights : {}, autoincrement : false, nextUri : '', defaults : [], hidden : [] } ], missing : [], native : 'de_DE', locales : [ 'en_US', 'de_DE' ], selectedLocale : 'de_DE' } ) },
		'elements/list' : function() { return ok( { columns : [ 'title', 'order' ], elements : [ { uri : 'recording', label : 'Aufnahme', values : { title : 'Aufnahme', order : 10 } } ] } ) },
	}, over || {} );
}

// --- the pins ------------------------------------------------------------------------------------------------------

/**
 *	@param		{Object}	w						A world
 *	@param		{string}	selector
 *
 *	@return		{Object[]}
 */
function inMain( w, selector ) {
	return all( w.main, selector );
}

const hasText = function( node, text ) { return textOf( node ).indexOf( text ) !== -1 };
const values = function( w, selector ) { return inMain( w, selector ).map( function( node ) { return node.value } ) };

( async function() {

	try {

	// === A. the files
	const sources = {};
	SCRIPTS.forEach( function( name ) { sources[name] = scriptOf( name ) } );

	SCRIPTS.forEach( function( name ) {

		let compiled = true;

		try {
			new vm.Script( sources[name], { filename : name } );
		}
		catch( error ) {
			compiled = false;
		}

		check( name+ ' is a valid script of its own', compiled === true );
	} );

	check( 'no file builds markup from data: no innerHTML, outerHTML, insertAdjacentHTML, document.write, eval or Function in it', /innerHTML|outerHTML|insertAdjacentHTML|document\.write|\beval\(|new Function/.test( pageScript ) === false );
	check( 'no file sets an inline style: the look is in the stylesheet (no style attribute, no .style. assignment)', /setAttribute\( *'style'|\.style\.|cssText/.test( pageScript ) === false );
	check( 'the files post only to the endpoint of the page (one fetch) and carry no address of a host', ( pageScript.match( /wn\.fetch\(/g ) || [] ).length === 1 && /https?:\/\//.test( pageScript ) === false );
	check( 'no file of the core names a package: no feature key, no panel of a package, no word of one in its code', /crm-(documents|portal|files|review|dates|events|workshops|sign|pdf|members|termine)\b|\b(Members|Pdf|CrmDocuments|CrmPortal|CrmFiles|Money)\b|\/crm\/(dateien|files|export|uploads)/.test( pageScript ) === false );
	check( 'no file of the core carries a German word in its code or its comments: no umlaut, no word of the screens of the first version', ( pageScript.match( /[äöüßÄÖÜ]|\b(beleg|kunde|kunden|projekt|projekte|termin|termine|datei|dateien|einstellungen|aufgaben|logbuch|uebersicht|einrichtung|freigaben)\b/i ) === null ) );

	// what the files share while they load is gone when the page has started
	const first = world( { answers : server(), hash : '#overview' } );
	await settle();

	check( 'what the files share while they load (Nino.crm.core) is gone after the start, and the handle of the page is there', first.win.Nino.crm.core === undefined && typeof first.win.Nino.crm.app.route === 'function' && first.win.Nino.crm.app.boot === undefined );

	const kitNames = Object.keys( first.win.Nino.crm.kit.make( 'probe' ) ).sort();

	// What the scripts of the packages call under Nino.crm is declared in the description of the class Crm (Crm.php), and held here: each name is there, is a function and is written in that description
	const declaredPoints = ['kit.make', 'state.own', 'keys.add', 'menu.add', 'pages.add', 'overview.addTile', 'wipe.addCount', 'cards.addTab', 'cards.addFact'];
	const declaredKit = ['append', 'ask', 'badge', 'card', 'cardList', 'clear', 'clientField', 'dmy', 'el', 'formList', 'formOf', 'format.locale', 'has', 'kv', 'link', 'menu', 'note', 'submitter', 't', 'toast', 'unsaver', 'valuesOfForm', 'api', 'call', 'crumb', 'search', 'sortHead', 'keepSearch', 'refuseAt', 'failed', 'errorText', 'saver', 'reveal', 'loading', 'loadFailed', 'data', 'dialog', 'head'];
	const declaredText = fs.readFileSync( path.join( featureDir, 'Crm.php' ), 'utf8' );
	const missing = declaredPoints.filter( function( name ) {

		const found = name.split( '.' ).reduce( function( object, part ) { return object === undefined || object === null ? undefined : object[part] }, first.win.Nino.crm );

		return typeof found !== 'function' || declaredText.includes( 'Nino.crm.'+ name+ '(' ) === false;
	} ).concat( declaredKit.filter( function( name ) {

		const found = name.split( '.' ).reduce( function( object, part ) { return object === undefined || object === null ? undefined : object[part] }, first.win.Nino.crm.kit.make( 'probe' ) );

		return typeof found !== 'function' || declaredText.includes( 'kit.'+ name+ '(' ) === false;
	} ) );

	check( 'every name under Nino.crm that the scripts of the packages call is declared in the description of the class and is a function - '+ declaredPoints.length+ ' registration points and the kit of a package with '+ declaredKit.length+ ' members: a name that goes, or that the description does not write, is a failure of this line', missing.length === 0 && declaredPoints.length === 9 && declaredKit.length === 38 );
	check( 'the kit a package gets at load time names what the packages after the core need and is frozen: no main, no alive, no title, no form', kitNames.length > 60 && [ 'main', 'alive', 'title', 'form', 'mine' ].every( function( name ) { return kitNames.includes( name ) === false } ) === true && Object.isFrozen( first.win.Nino.crm.kit.make( 'probe' ) ) === true );
	check( 'the kit has the names of the first group a package draws with (el, t, api, toast, link, note, kv, head, dmy, stamp, clear, append, dialog, ask, projectLabel, projectText, badge, card, today, errorText, clientField)', [ 'el', 't', 'api', 'toast', 'link', 'note', 'kv', 'head', 'dmy', 'stamp', 'clear', 'append', 'dialog', 'ask', 'projectLabel', 'projectText', 'badge', 'card', 'today', 'errorText', 'clientField' ].every( function( name ) { return kitNames.includes( name ) } ) );
	check( 'the kit has the names of the second group (hm, format, step, failed, loadFailed, loading, reveal, route, wordOf, extra, extraText, search, menu, saver, unsaver, projectNo, clientTitle, pickerClients, cardList, sortHead, keepRoles, phone, pagerOf, keepSearch, refusedText, cardTabs, cardBody, headParts, headItems, headChoice, headHasAction, headLines, PHONE, PAGE_SIZE, settingsOrdered, sitePage, quietField, quietText, saveFrom, showLink)', [ 'hm', 'format', 'step', 'failed', 'loadFailed', 'loading', 'reveal', 'route', 'wordOf', 'extra', 'extraText', 'search', 'menu', 'saver', 'unsaver', 'projectNo', 'clientTitle', 'pickerClients', 'cardList', 'sortHead', 'keepRoles', 'phone', 'pagerOf', 'keepSearch', 'refusedText', 'cardTabs', 'cardBody', 'headParts', 'headItems', 'headChoice', 'headHasAction', 'headLines', 'PHONE', 'PAGE_SIZE', 'settingsOrdered', 'sitePage', 'quietField', 'quietText', 'saveFrom', 'showLink' ].every( function( name ) { return kitNames.includes( name ) } ) );

	// every word the script asks for is in both languages, with the same number of places
	const asked = new Set();
	let found;
	const pattern = /(?:\bt|\bhas|\bword|\bcountWord|\bwordOf)\( *'([a-z][a-z0-9-]*(?:\.[a-z0-9._-]*)?)'/g;

	while( ( found = pattern.exec( pageScript ) ) )
		asked.add( found[1] );

	const lackingDe = [ ...asked ].filter( function( key ) { return /[.-]$/.test( key ) === false && texts[key] === undefined && /^(text\.error-|error\.crm_)/.test( key ) === false && key !== 'button.th' } );
	const lackingEn = [ ...asked ].filter( function( key ) { return /[.-]$/.test( key ) === false } ).filter( function( key ) { return textsEn[key] === undefined && /^(text\.error-|error\.crm_)/.test( key ) === false && key !== 'button.th' } );

	// every class the script sets has a rule in the style sheet, except the ones that are hooks only (a test, a package or a script finds the node by it)
	{
		const css = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm.css' ), 'utf8' );
		const set = new Set();

		for( const one of pageScript.matchAll( /\bclass\s*:\s*'([^']*)'/g ) )
			one[1].split( ' ' ).filter( function( name ) { return /^[a-z][a-z0-9-]*$/.test( name ) && name.endsWith( '-' ) === false } ).forEach( function( name ) { set.add( name ) } );

		const bare = [ ...set ].filter( function( name ) { return new RegExp( '\\.'+ name.replace( /-/g, '\\-' )+ '(?![\\w-])' ).test( css ) === false } ).sort();
		const hooks = [ 'logbook-by', 'logbook-project', 'picker-options', 'secret', 'share', 'share-end', 'share-keep', 'share-source', 'shared-row', 'task-copy', 'task-date', 'task-delete', 'task-down', 'task-up', 'tasks-block' ];

		check( 'every class the script sets has a rule in the style sheet or is one of the hooks that have none'+ ( bare.join() === hooks.join() ? '' : ' - '+ bare.join( ' ' ) ), set.size > 100 && bare.join() === hooks.join() );
	}

	// the badges the script draws have their colors, and the card of a list on the phone has a place for every cell a package adds, in the three ways narrow can say
	{
		const css = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm.css' ), 'utf8' );
		const kinds = [ ...pageScript.matchAll( /\bbadge\( *'([a-z-]+)'/g ) ].map( function( one ) { return one[1] } );
		const cards = css.slice( css.indexOf( '.tbl.cards td[data-col^="x-"]' ) - 200, css.indexOf( '.tbl.cards td[data-col^="x-"]' ) + 500 );

		check( 'the select of the phase in the head of a project takes what is left of the line, so that the title of a phase never widens the page', /\.phase select \{ min-width: 0; max-width: 100%/.test( css ) && /\.phase \{ max-width: 100%; min-width: 0 \}/.test( css ) );
		check( 'every kind of badge the script draws has a color in the style sheet', kinds.length >= 4 && kinds.every( function( kind ) { return new RegExp( '\\.b-'+ kind+ '\\s*[,{]' ).test( css ) } ) );
		check( 'a cell of a package in the card of a list has a line of its own, and says with narrow whether it keeps the word of its column, goes small to the second line or stays out', /td\[data-col\^="x-"\] \{ grid-column: 1 \/ -1 \}/.test( cards ) && /td\[data-narrow="second"\]/.test( cards ) && /td\[data-narrow="off"\] \{ display: none \}/.test( cards ) && /@media \(max-width: 759px\)/.test( css.slice( 0, css.indexOf( '.tbl.cards td[data-col^="x-"]' ) ) ) );
	}

	// a button that holds nothing but a sign has a name for a screen reader: every menu of the script says what it is
	{
		const menus = [ ...pageScript.matchAll( /\bmenu\( \{([^}]*)\}/g ) ].map( function( one ) { return one[1] } ).filter( function( spec ) { return spec.includes( 'label' ) } );

		check( 'every menu the script draws names its button for a screen reader (aria), and there are menus to look at', menus.length >= 5 && menus.every( function( spec ) { return /\baria\s*:/.test( spec ) } ) );
	}

	check( 'every word the script asks for with a key of its own is a word of the German surface'+ ( lackingDe.length === 0 ? '' : ' - '+ lackingDe.join( ', ' ) ), lackingDe.length === 0 );
	check( 'every word the script asks for with a key of its own is a word of the English surface'+ ( lackingEn.length === 0 ? '' : ' - '+ lackingEn.join( ', ' ) ), lackingEn.length === 0 );
	check( 'the two languages have the same keys, no empty word, the same number of places in each', Object.keys( texts ).length === Object.keys( textsEn ).length && Object.keys( texts ).every( function( key ) {
		return textsEn[key] !== undefined && texts[key].trim() !== '' && textsEn[key].trim() !== '' && texts[key].split( '%s' ).length === textsEn[key].split( '%s' ).length;
	} ) === true );

	// every word of the files is asked for by the script, a template or the server of the surface, and every word a template or the server asks for is there: no word of a package is left in the files of the core
	{
		const readAll = function( dir, pattern, into ) {
			fs.readdirSync( dir, { withFileTypes : true } ).forEach( function( entry ) {
				const file = path.join( dir, entry.name );

				if( entry.isDirectory() === true && entry.name !== 'text' && entry.name !== 'assets' )
					readAll( file, pattern, into );
				else if( entry.isFile() === true && pattern.test( entry.name ) === true )
					into.push( fs.readFileSync( file, 'utf8' ) );
			} );

			return into;
		};
		const templates = readAll( path.join( featureDir, 'templates' ), /^surface-.*\.tpl$/, [] ).join( '\n' );
		const server = readAll( path.join( featureDir, 'Surface' ), /\.php$/, [] ).join( '\n' );
		const stems = [];
		const literals = new Set();
		let at;
		const quoted = /'([a-z-][a-z0-9.-]*)'/g;

		while( ( at = quoted.exec( pageScript+ '\n'+ server ) ) )
			if( /[.-]$/.test( at[1] ) === true )
				stems.push( at[1] );
			else
				literals.add( at[1] );

		// The vetoes of the core are words of the surface (veto.<name>) that the class of the projects asks for by the name: each word has its call and each call its word
		const projects = fs.readFileSync( path.join( featureDir, 'Projects', 'Projects.php' ), 'utf8' );
		const vetoCalls = [ ...projects.matchAll( /self::veto\( \$appData, '([a-z-]+)'/g ) ].map( function( one ) { return 'veto.'+ one[1] } );
		const vetoWords = Object.keys( texts ).filter( function( key ) { return key.startsWith( 'veto.' ) } );
		stems.push( 'veto.' );
		const kinds = [ ...server.matchAll( /self::notice\( [^;]*?, '([a-z]+)' \)/g ) ].map( function( one ) { return one[1] } );
		const inTemplates = [ ...templates.matchAll( /\[\[t\.([a-z0-9.-]+)\]\]/g ) ].map( function( one ) { return one[1] } );
		const viaServer = kinds.flatMap( function( kind ) { return [ 'title', 'text', 'link' ].map( function( part ) { return kind+ '.'+ part } ) } );
		const left = Object.keys( texts ).filter( function( key ) {
			return literals.has( key ) === false && inTemplates.includes( key ) === false && viaServer.includes( key ) === false && stems.some( function( stem ) { return stem.startsWith( '-' ) === true ? key.includes( stem ) : key.startsWith( stem ) } ) === false;
		} );
		const missing = inTemplates.concat( viaServer ).filter( function( key ) { return texts[key] === undefined || textsEn[key] === undefined } );

		check( 'every word of the files is asked for by the script (by its key or by the start of a key it builds), by a template or by the server'+ ( left.length === 0 ? '' : ' - '+ left.join( ', ' ) ), left.length === 0 && new Set( kinds ).size === 2 && inTemplates.length > 10 );
		check( 'every sentence of a veto the core gives is a word of the surface in both languages, and none is written in the code: the words are veto.<name>, one for each call of the class of the projects ('+ vetoWords.length+ ' of them)', vetoWords.length === 9 && [ ...new Set( vetoCalls ) ].sort().join() === vetoWords.sort().join() && vetoWords.every( function( key ) { return textsEn[key] !== undefined } ) === true
			&& [ ...projects.matchAll( /trigger_error\( ([^;]*?) \);/g ) ].filter( function( one ) { return one[1].includes( 'E_USER_' ) === false } ).every( function( one ) { return one[1].startsWith( 'self::veto( ' ) } ) === true );
		check( 'every word a template or the server of the surface asks for is in both languages'+ ( missing.length === 0 ? '' : ' - '+ missing.join( ', ' ) ), missing.length === 0 );
	}

	// === B. the formatter alone
	{
		const lone = vm.createContext( { window : {}, document : { documentElement : { lang : 'de-DE' } } } );
		lone.window.document = lone.document;
		vm.runInContext( sources['crm-format.js'], lone, { filename : 'crm-format.js' } );

		const format = lone.window.Nino.crm.format;
		const NB = ' ';

		check( 'Nino.crm.format loads alone, with nothing of the rest, and takes the language of the document', typeof format.money === 'function' && format.locale() === 'de_DE' && Object.isFrozen( format ) === true && lone.window.Nino.crm.core === undefined );
		check( 'money in German: the point between the groups, the comma before the cents, the currency behind a no-break space, the number alone for none', format.money( 123456, '€' ) === '1.234,56'+ NB+ '€' && format.money( -5, '' ) === '-0,05' && format.money( 100000000 ) === '1.000.000,00' );
		check( 'a rate is written without a trailing zero, a day as d.m.Y, a time of midnight as the day alone', format.rate( 1900 ) === '19 %' && format.rate( 550 ) === '5,5 %' && format.day( '2026-03-04' ) === '04.03.2026' && format.day( '2026-03-04T10:00:00Z' ) === '04.03.2026' && format.day( 'x' ) === '' && format.stamp( '2026-03-04T00:00:00Z' ) === '04.03.2026' );
		check( 'a price is read as the language writes it and as a plain number; 1.500 is neither a thousand nor one and a half; nothing past the highest price; no other text', format.parseMoney( '1.234,56' ) === 123456 && format.parseMoney( '1234.56' ) === 123456 && format.parseMoney( '12,5' ) === 1250 && format.parseMoney( '-3' ) === -300 && format.parseMoney( '1.500' ) === null && format.parseMoney( '10000000,01' ) === null && format.parseMoney( 'abc' ) === null && format.parseMoney( '1,2,3' ) === null );

		format.locale( 'en_US' );

		check( 'in English the comma separates the groups and the point the cents, a rate has its point, a day is written month first', format.money( 123456, '€' ) === '1,234.56'+ NB+ '€' && format.rate( 550 ) === '5.5 %' && format.day( '2026-03-04' ) === '03/04/2026' && format.parseMoney( '1,234.56' ) === 123456 && format.parseMoney( '1.234,56' ) === null );
	}

	// === C. the screens of the core
	{
		const today = new Date();
		const w = world( { hash : '#overview', answers : server(), today : today.getTime() } );
		await settle();

		check( 'the overview: the head with the day in the language of the page, the tile of the clients with their number, the card of what needs the owner says nothing waits, the card of the device', hasText( w.main, today.toLocaleDateString( 'de-DE', { weekday : 'long', day : 'numeric', month : 'long', year : 'numeric' } ) ) && hasText( inMain( w, '.tile' )[0], '2' ) && hasText( inMain( w, '.tile' )[0], texts['tile.clients-many'].replace( '%s', '2' ) ) && hasText( w.main, texts['needs.none'] ) && hasText( w.main, texts['device.title'] ) );
		check( 'the rail: the entries of the core in their order with the count of the clients, the one of the overview marked, and the overview asked for once', textOf( w.nav ) === [ texts['nav.overview'], texts['nav.clients']+ ' 2', texts['nav.projects'], texts['nav.settings'], texts['nav.values'] ].join( '' ) && all( w.nav, 'a' ).filter( function( anchor ) { return String( anchor.className ).split( ' ' ).includes( 'on' ) } ).map( textOf ).join() === texts['nav.overview'] && w.sent().filter( function( name ) { return name === 'crm/overview' } ).length === 1 );

		const every = [];

		for( const hash of [ '#overview', '#clients', '#client-1', '#client-1-notes', '#client-1-logbook', '#client-new', '#projects', '#project-12', '#project-12-shares', '#projects-new', '#values', '#settings', '#settings/mails', '#settings/system', '#setup' ] ) {

			const one = world( { hash : hash, answers : server() } );
			await settle();
			every.push( [ hash, one.errors.length, textOf( one.main ).trim() === '' ] );
		}

		check( 'every screen of the core draws something and says nothing to the console'+ ( every.filter( function( row ) { return row[1] > 0 || row[2] } ).length === 0 ? '' : ' - '+ JSON.stringify( every.filter( function( row ) { return row[1] > 0 || row[2] } ) ) ), every.every( function( row ) { return row[1] === 0 && row[2] === false } ) );
	}

	{
		const w = world( { hash : '#clients', answers : server() } );
		await settle();

		const rows = inMain( w, 'tbody tr' );

		check( 'the rail counts the active clients on every screen, not only on the overview', hasText( w.nav, texts['nav.clients']+ ' 2' ) );
		check( 'the list of the clients: a row each in the order of the numbers with company, name, city and state, a client with no data says so, a country that is not Germany is named, the link to a new client', rows.length === 3 && hasText( rows[0], 'Nordlicht Records GmbH' ) && hasText( rows[1], '(AT)' ) && hasText( rows[2], texts['clients.bare'] ) && inMain( w, '#new-client' )[0].getAttribute( 'href' ) === '#client-new' );
		check( 'the list asks the server for the clients once, and for the count of the rail', w.sent().filter( function( name ) { return name === 'crm-clients/list' } ).length === 1 && w.sent().every( function( name ) { return [ 'crm-clients/list', 'crm/overview', 'crm/status' ].includes( name ) } ) );

		const search = inMain( w, '#q' )[0];
		type( search, 'berlin' );

		check( 'the search narrows the list to the clients that hold the text', inMain( w, 'tbody tr' ).length === 1 && hasText( inMain( w, 'tbody tr' )[0], 'Tom Weiss' ) );

		w.location.hash = '#client-1';
		w.send( 'hashchange', {} );
		await settle();
		check( 'a row opens the card: #client-<id> draws it', hasText( w.main, 'Hafenstraße 12' ) );
	}

	{
		const w = world( { hash : '#client-1', answers : server() } );
		await settle();

		const tabs = inMain( w, '[role="tab"]' ).map( function( tab ) { return tab.dataset.tab } );

		check( 'the card of a client: the master data as facts, the mail and the phone as links', hasText( w.main, 'Hafenstraße 12' ) && inMain( w, '#client-mail' )[0].getAttribute( 'href' ) === 'mailto:lena@nordlicht.example' && inMain( w, '#client-tel' )[0].getAttribute( 'href' ) === 'tel:+4940123456' );
		check( 'the card has the tabs of the core in their order, and no tab of a package', tabs.join() === 'projects,notes,logbook' );
		check( 'the tab of the projects lists the projects of the client, the way to a new one, and the projects of other clients released to it', hasText( w.main, 'Winter EP' ) && hasText( w.main, 'Old single' ) && hasText( w.main, texts['projects.shared-title'] ) && hasText( w.main, 'Shared EP' ) && inMain( w, '#client-project-new' )[0].getAttribute( 'href' ) === '#projects-new-k1' );
		check( 'the card asks for the client and for its projects and for nothing of a package', w.sent().filter( function( name ) { return /^crm-/.test( name ) } ).sort().join() === 'crm-clients/get,crm-projects/list' );

		// the notes
		click( w, inMain( w, '[data-tab="notes"]' )[0] );
		await settle();
		const notes = inMain( w, '#notes' )[0];
		type( notes, 'New note' );
		click( w, inMain( w, '#notes-save' )[0] );
		await settle();

		const saved = w.last( 'crm-clients/save' );

		check( 'saving the notes posts every field of the client with the new note and nothing else of a package', saved !== undefined && saved.data.notes === 'New note' && saved.data.id === '1' && saved.data.company === 'Nordlicht Records GmbH' && saved.data.business === true && saved.data.packages === undefined );

		// the master data: a save keeps what the form does not show
		click( w, inMain( w, '.menu button' ).find( function( button ) { return button.textContent === texts['card.edit'] } ) );
		type( inMain( w, '#f-city' )[0], 'Lübeck' );
		fire( inMain( w, '#master' )[0], 'submit', { preventDefault : function() {} } );
		await settle();

		const master = w.all( 'crm-clients/save' ).pop();

		check( 'a save of the master data posts every field of the client, the notes it does not show among them, the change, and nothing of a package', master.data.city === 'Lübeck' && master.data.notes === 'New note' && master.data.id === '1' && master.data.packages === undefined && master.data.company === 'Nordlicht Records GmbH' && master.data.business === true );

		// the logbook
		click( w, inMain( w, '[data-tab="logbook"]' )[0] );
		await settle();
		const entries = inMain( w, '.logbook-row' );

		check( 'the tab of the logbook draws the entries: the word of a kind of the core, the line of the owner as it was written, who wrote it, the project as a link', entries.length === 4 && hasText( entries[0], texts['logbook.kind-client-added'] ) && hasText( entries[1], 'Called back' ) && hasText( entries[1], texts['logbook.by-system'] ) && inMain( w, '.logbook-project' )[0].getAttribute( 'href' ) === '#project-12' );
		check( 'an entry of a kind nobody here knows shows the key of its kind as it is and the source that wrote it beside it', hasText( entries[3], texts['logbook.unknown'].replace( '%s', 'document-sent' ).replace( '%s', 'docs' ) ) );
		check( 'a release that ended because the client became the owner says so, with the project and the client it is about', hasText( entries[2], texts['logbook.kind-project-unshared-owner'].replace( '%s', 'P-0012' ).replace( '%s', 'K-0002' ) ) );
		type( inMain( w, '#logbook-new-text' )[0], 'A line' );
		fire( inMain( w, '#logbook-new' )[0], 'submit', { preventDefault : function() {} } );
		await settle();
		check( 'the line of the owner is posted to the logbook of the client', w.last( 'crm-logbook/add' ) !== undefined && w.last( 'crm-logbook/add' ).data.text === 'A line' && w.last( 'crm-logbook/add' ).data.client === '1' );

		// the menu: deactivate, delete
		const menuItems = inMain( w, '.menu button' ).map( function( button ) { return button.textContent } );

		check( 'the menu of the head: edit, deactivate and delete, in this order', menuItems.slice( 1, 4 ).join( '|' ) === [ texts['card.edit'], texts['card.deactivate'], texts['client-delete.menu'] ].join( '|' ) );

		// the menu with the keyboard: the arrow opens it with the focus on an item, the arrows move among the items, Escape closes it and the focus is back on the button
		{
			const root = inMain( w, '.menu' )[0];
			const trigger = root.children[0];
			const items = Array.from( root.querySelectorAll( '[role="menuitem"]' ) );
			const holder = function() { return items.find( function( item ) { return item.focused === true } ) };
			const key = function( name ) { fire( root, 'keydown', { key : name, altKey : false, target : holder() ?? trigger, preventDefault : function() {} } ) };

			key( 'ArrowDown' );
			const opened = String( root.className ).split( ' ' ).includes( 'open' ) && holder() === items[0];
			key( 'ArrowDown' );
			const second = holder() === items[1];
			key( 'End' );
			const last = holder() === items[2];
			key( 'ArrowDown' );
			const wrapped = holder() === items[0];
			key( 'ArrowUp' );
			const back = holder() === items[2];
			key( 'Escape' );

			check( 'the menu of the head by the keyboard: the arrow opens it on its first item, the arrows and End move among the items and wrap, Escape closes it and gives the focus back to its button', opened && second && last && wrapped && back && String( root.className ).split( ' ' ).includes( 'open' ) === false && trigger.focused === true && holder() === undefined );
		}

		click( w, inMain( w, '.menu button' ).find( function( button ) { return button.textContent === texts['client-delete.menu'] } ) );
		await settle();

		const box = w.dialogs[0];

		check( 'deleting a client asks the server what hangs on it, and the dialog lists the client and the counts above nought in the order of the server, with the word the surface has for each', w.last( 'crm-clients/delete-preview' ) !== undefined && box !== undefined && hasText( box, texts['client-delete.line-base'] ) && hasText( box, texts['client-delete.line-projects'].replace( '%s', '2' ) ) && hasText( box, texts['client-delete.line-logbook'].replace( '%s', '3' ) ) && hasText( box, texts['client-delete.line-tasks'].replace( '%s', '4' ) ) && hasText( box, texts['client-delete.line-received'].replace( '%s', '1' ) ) );
		check( 'a count the server names that the surface has no word for is a line with its key and its number, and one that is nought is not drawn: the dialog says what the server takes', hasText( box, texts['client-delete.line-other'].replace( '%s', 'weird' ).replace( '%s', '5' ) ) === true && hasText( box, texts['client-delete.line-shares'].split( '%s' )[0] ) === false );
		// the word of the dialog: the button waits for it exactly, and nothing is posted before
		{
			const yesOf = function( dialog ) { return all( dialog, 'button' ).pop() };
			const field = all( box, 'input' )[0];
			const posts = function() { return w.all( 'crm-clients/delete' ).length };
			const off = yesOf( box ).disabled === true;

			click( w, yesOf( box ) );
			type( field, 'K-000' );
			const wrong = yesOf( box ).disabled === true;
			click( w, yesOf( box ) );
			type( field, 'k-0001' );
			const small = yesOf( box ).disabled === true;
			type( field, 'K-0001 ' );
			const spaced = yesOf( box ).disabled === true;
			click( w, yesOf( box ) );
			await settle();
			const early = posts();
			type( field, 'K-0001' );
			const exact = yesOf( box ).disabled === false;

			check( 'the button that deletes a client is off until the number is typed exactly (not a part, not in small letters, not with a blank), and a press while it is off posts nothing', off === true && wrong === true && small === true && spaced === true && early === 0 && exact === true );
		}
	}

	{
		const w = world( { hash : '#client-new', answers : server() } );
		await settle();

		type( inMain( w, '#f-company' )[0], 'Fresh Ltd' );
		type( inMain( w, '#f-email' )[0], 'fresh@example.com' );
		fire( inMain( w, '#new-client-form' )[0], 'submit', { preventDefault : function() {} } );
		await settle();

		const post = w.last( 'crm-clients/save' );

		check( 'a new client: the fields of the master data are posted - the core\'s own and nothing else - and the card of the client opens', post !== undefined && post.data.company === 'Fresh Ltd' && post.data.email === 'fresh@example.com' && post.data.business === false && Object.keys( post.data ).every( function( name ) { return [ 'company', 'name', 'street', 'zip', 'city', 'country', 'email', 'phone', 'vat_id', 'buyer_reference', 'business' ].includes( name ) } ) && w.location.hash === '#client-9' );
	}

	{
		const w = world( { hash : '#projects', answers : server() } );
		await settle();

		const rows = inMain( w, 'tbody tr' );

		check( 'the list of the projects: the running ones with their number, client, start, state and phase, the finished one only where the switch is on, a project with no title by its number', rows.length === 2 && hasText( rows[0], 'P-0012' ) && hasText( rows[0], 'Nordlicht Records GmbH' ) && hasText( rows[0], '02.02.2026' ) && hasText( rows[0], 'Mix' ) && hasText( rows[1], 'P-0014' ) );

		click( w, inMain( w, '#p-all' )[0] );
		inMain( w, '#p-all' )[0].checked = true;
		fire( inMain( w, '#p-all' )[0], 'change', { target : inMain( w, '#p-all' )[0] } );
		await settle();
		check( 'the switch shows the finished projects too', inMain( w, 'tbody tr' ).length === 3 );
	}

	{
		const w = world( { hash : '#project-12', answers : server() } );
		await settle();

		check( 'the card of a project: the head with the number, the client as a link, the start, the phase select, the facts, the tabs tasks, releases and logbook', hasText( w.main, 'P-0012' ) && inMain( w, '[role="tab"]' ).map( function( tab ) { return tab.dataset.tab } ).join() === 'tasks,shares,logbook' && hasText( w.main, 'Four songs' ) && inMain( w, '#project-phase' ).length === 1 );
		check( 'the tasks: the open one first, the done one with its day, the field for a new one', values( w, '.task-text' ).join() === 'Mix the second song,Send the cover' && hasText( w.main, texts['tasks.done-on'].replace( '%s', '03.03.2026' ) ) && inMain( w, '#task-new, .task-new' ).length >= 0 );

		selectValue( inMainOne( w, '#project-phase' ), 'recording' );
		await settle();
		check( 'a phase chosen is posted at once with the id of the project', w.last( 'crm-projects/phase' ) !== undefined && w.last( 'crm-projects/phase' ).data.phase === 'recording' && w.last( 'crm-projects/phase' ).data.id === '12' );
	}

	{
		const w = world( { hash : '#projects-new-k1', answers : server( { 'elements/save' : function( data ) { return [ 200, { element : { '.uri' : 'crm-projects/15' } } ] } } ) } );
		await settle();

		type( inMain( w, '#f-title' )[0], 'Spring EP' );
		fire( inMain( w, '#project-form' )[0], 'submit', { preventDefault : function() {} } );
		await settle();

		const post = w.last( 'elements/save' );

		check( 'a new project posts the element of the type crm-projects through the panel\'s action, with the number of its client and no id of its own, and the card opens', post !== undefined && post.data.type === 'crm-projects' && post.data.isNew === true && post.data.uri === '' && post.data.fields.title === 'Spring EP' && post.data.fields.client === 'K-0001' && post.data.packages === undefined && w.location.hash === '#project-15' );
	}

	{
		const w = world( { hash : '#values', answers : server( { 'elements/types' : function() { return [ 200, { types : [ { type : 'crm-phases', title : 'Phasen', model : {}, rights : {}, autoincrement : false } ] } ] } } ) } );
		await settle();
		check( 'the values: the types the server lets the screen edit are the tabs, in the words of the surface', hasText( w.main, 'Phasen' ) );

		const phases = world( { hash : '#values', answers : server( { 'elements/types' : function() { return [ 200, { types : [ { type : 'crm-phases', title : 'Phasen', hint : 'The hint of the type', model : { title : { type : 'string', locale : true, label : 'Label of the title' }, order : { type : 'integer', label : 'Label of the order' } }, rights : {}, autoincrement : false } ] } ] }, 'elements/list' : function() { return [ 200, { columns : [ 'title', 'order' ], elements : [] } ] } } ) } );
		await settle();
		check( 'the head of a column of a type is the label its model has in the language, the key where it has none, and the sentence over the list is the hint the server sends for the type', hasText( phases.main, 'Label of the order' ) && inMain( phases, '#values-hint' ).length === 1 && inMain( phases, '#values-hint' )[0].textContent === 'The hint of the type');
	}

	{
		const w = world( { hash : '#settings', answers : server() } );
		await settle();

		const tabNames = inMain( w, '.settings-tabs a' ).map( function( tab ) { return tab.dataset.tab } );

		check( 'the settings: the tabs general, mails and system with the sections of the core, nothing of a package', tabNames.join() === 'general,mails,system' && [ 'company-holder', 'brand', 'crm-host', 'app-host', 'numbers' ].every( function( id ) { return inMain( w, '#'+ id ).length === 1 } ) );
		check( 'the block of the mails has no switch and so no button where no package enters one, and says where the mails go', inMain( w, '#mailnotes-save' ).length === 0 && hasText( w.main, 'owner@example.com' ) );

		type( inMain( w, '#f-start_client' )[0], '10' );
		fire( inMain( w, '#settings-form' )[0], 'submit', { preventDefault : function() {} } );
		await settle();
		check( 'the numbers are saved through crm/settings-save with the start numbers and the name of the owner', w.last( 'crm/settings-save' ) !== undefined && w.last( 'crm/settings-save' ).data.start_client === '10' && w.last( 'crm/settings-save' ).data.start_project === '1' && w.last( 'crm/settings-save' ).data.seller_name === '' );
	}

	{
		const broken = server( { 'crm/status' : function() { return [ 200, { lines : STATUS_LINES, whole : false, open : { address : '/crm' }, orphans : [] } ] }, 'crm/setup' : function( data ) { return [ 200, { lines : STATUS_LINES.map( function( line ) { return Object.assign( {}, line, { state : 'ok', fixable : false } ) } ), whole : true, open : { address : '/crm' }, orphans : [] } ] } } );
		const w = world( { hash : '#overview', answers : broken } );
		await settle();

		check( 'a status that is not whole starts the page on the setup', w.location.hash === '#setup' );

		const rows = inMain( w, '#setup-rows li' );

		check( 'the setup: a row for each line the server names, in its order, with the label, the sentence of its state with its values, and a button only at the lines that have a fix and are not as they should be', rows.length === 5 && rows[0].dataset.key === 'database' && hasText( rows[0], texts['setup.detail-database-missing'] ) && hasText( rows[2], texts['setup.detail-schema-behind'].replace( '%s', '0' ).replace( '%s', '1' ).replace( '%s', 'crm-probe' ) ) && inMain( w, '[data-fix]' ).map( function( button ) { return button.dataset.fix } ).join() === 'database,schema' );

		click( w, inMain( w, '[data-fix="database"]' )[0] );
		await settle();

		check( 'a press mends one line by its key and draws what the server answers, with the way on to the overview', w.last( 'crm/setup' ).data.key === 'database' && inMain( w, '[data-fix]' ).length === 0 && inMain( w, '#setup-done' ).length === 1 );
	}

	{
		const lines = [ { key : 'writable', hint : false, fixable : false, state : 'ok' }, { key : 'strays', hint : false, fixable : true, state : 'leftover' } ];
		const broken = server( { 'crm/status' : function() { return [ 200, { lines : lines, whole : false, open : { address : '/crm' }, orphans : [] } ] }, 'crm/setup' : function() { return [ 200, { lines : lines.map( function( line ) { return Object.assign( {}, line, { state : 'ok', fixable : false } ) } ), whole : true, open : { address : '/crm' }, orphans : [] } ] } } );
		const w = world( { hash : '#setup', answers : broken } );
		await settle();
		const row = inMain( w, '#setup-rows li' ).find( function( one ) { return one.dataset.key === 'strays' } );

		check( 'the line of the leftovers of deleted projects has its label, the sentence of its state and the word of the state, and a button that mends it by its key', row !== undefined && hasText( row, texts['setup.line-strays'] ) && hasText( row, texts['setup.detail-strays-leftover'] ) && hasText( row, texts['setup.state-leftover'] ) && all( row, '[data-fix="strays"]' ).length === 1 );
		click( w, all( row, '[data-fix="strays"]' )[0] );
		await settle();
		check( 'a press posts the key of the line, and a line that is fine has no button', w.last( 'crm/setup' ).data.key === 'strays' && inMain( w, '[data-fix]' ).length === 0 );
	}

	{
		const w = world( { hash : '', app : 'todo', answers : server() } );
		await settle();
		check( 'the app of the tasks: no rail keys, the head, a tab for the project that has open tasks and its list of tasks', inMain( w, '.todo-tabs' ).length === 1 && values( w, '.task-text' ).includes( 'Mix the second song' ) && w.sent().includes( 'crm-tasks/open' ) && w.sent().includes( 'crm-tasks/list' ) );
	}

	// === D. the registration points, through a probe package - and the core without it
	{
		const bare = world( { hash : '#overview', answers : server() } );
		await settle();

		const rail = textOf( bare.nav );
		const bareClient = world( { hash : '#client-1', answers : server() } );
		await settle();
		const bareSettings = world( { hash : '#settings', answers : server() } );
		await settle();

		check( 'without a package nothing is dead: the rail has the entries of the core only, each one with a page; no tab of a card is empty; no tile of the overview without a content', rail.indexOf( 'Probe' ) === -1 && bare.win.Nino.crm.menu.entries().every( function( entry ) { return entry.page !== null && bare.win.Nino.crm.pages.list().some( function( page ) { return page.key === entry.page } ) } ) && inMain( bareClient, '[role="tab"]' ).length === 3 && inMain( bare, '.tile' ).every( function( tile ) { return textOf( tile ).trim() !== '' } ) );
		check( 'without a package the settings show no tab and no section of a package, and no tab of the strip has no section', inMain( bareSettings, '.settings-tabs a' ).every( function( tab ) { return inMain( bareSettings, '#panel-'+ tab.dataset.tab )[0].children.length > 0 } ) );
	}

	const pageInner = {};
	const probe = function( win, context ) {

		const crm = win.Nino.crm;
		const kit = crm.kit.make( 'probe' );
		const node = function( text, id ) { return kit.el( 'p', { id : id, text : text } ) };

		crm.settings.addTab( { key : 'probe', word : 'Probe tab', rank : 500 } );
		crm.settings.addSection( { key : 'probe-section', tab : 'probe', word : 'Probe section', build : function( inner ) { return inner.card( 'Probe card', node( 'probe settings', 'probe-settings' ) ) } } );
		crm.pages.add( { key : 'probe', hash : 'probe', title : 'Probe page', draw : function( inner, where ) { pageInner.object = inner; return node( 'probe page '+ where.rest, 'probe-page' ) } } );
		crm.menu.add( { key : 'probe', word : 'Probe entry', page : 'probe', rank : 700, count : function() { return 4 } } );
		crm.overview.addTile( { key : 'probe', rank : 5, draw : function( inner, overview ) { return node( 'probe tile '+ overview.probe, 'probe-tile' ) } } );
		crm.overview.addBlock( { key : 'probe', rank : 40, draw : function() { return inner( 'probe block' ) } } );
		crm.overview.addNeed( { key : 'probe-need', draw : function( inner, need ) { return kit.el( 'li', { class : 'probe-need', text : 'need '+ need.id } ) } } );
		crm.overview.addData( { key : 'probe', load : function() { return Promise.resolve( 'loaded' ) } } );
		crm.cards.addTab( 'client', { key : 'probe', word : 'Probe tab', rank : 700, count : function() { return 7 }, draw : function( inner, record ) { return node( 'client '+ record.client.number, 'probe-client-tab' ) } } );
		crm.cards.addTab( 'project', { key : 'probe', word : 'Probe tab', rank : 700, draw : function( inner, record ) { return node( 'project '+ record.project.id, 'probe-project-tab' ) } } );
		crm.cards.addHead( 'client', { key : 'probe-mark', slot : 'marks', draw : function() { return kit.el( 'span', { id : 'probe-mark', text : 'probe mark' } ) } } );
		crm.cards.addHead( 'client', { key : 'probe-note', slot : 'notes', draw : function() { return node( 'probe note', 'probe-note' ) } } );
		crm.cards.addHead( 'client', { key : 'probe-action', slot : 'actions', draw : function() { return kit.el( 'span', { id : 'probe-action', text : 'probe action' } ) } } );
		crm.cards.addHead( 'client', { key : 'probe-menu', slot : 'menu', draw : function() { return [ { label : 'Probe item', run : function() {} } ] } } );
		crm.cards.addHead( 'project', { key : 'probe-action', slot : 'actions', draw : function() { return kit.el( 'span', { id : 'probe-project-action', text : 'probe project action' } ) } } );
		crm.cards.addFact( 'client', { key : 'probe-fact', word : 'Probe fact', draw : function() { return 'fact value' } } );
		crm.cards.addFact( 'project', { key : 'probe-fact', word : 'Probe fact', draw : function() { return 'project fact' } } );
		crm.cards.define( 'probe-card', { screen : 'probe', slots : [ 'notes' ] } );
		crm.lists.addMark( 'client', { key : 'probe', draw : function() { return kit.el( 'span', { class : 'probe-mark', text : '*' } ) } } );
		crm.lists.addMark( 'project', { key : 'probe', draw : function() { return kit.el( 'span', { class : 'probe-mark', text : '+' } ) } } );
		[ 'client', 'project', 'client-project' ].forEach( function( list ) {
			crm.lists.addColumn( list, { key : 'probe', head : 'Probe column', rank : 500, load : function() { win.probeLoads = ( win.probeLoads || 0 ) + 1; return Promise.resolve() }, cell : function( inner, row ) { return 'cell '+ row.id } } );
		} );
		crm.search.add( { key : 'probe', word : 'Probe hits', load : function() { return Promise.resolve( [ { n : 'Zebra' } ] ) }, hits : function( inner, rows, holds ) { return rows.filter( function( row ) { return holds( [ row.n ] ) } ).map( function( row ) { return { label : row.n, hint : 'probe', href : '#probe' } } ) } } );
		crm.setup.addStep( { key : 'probe', word : 'Probe step', rank : 500, done : function() { return false }, draw : function() { return kit.el( 'span', { text : 'probe step open' } ) } } );
		crm.keys.add( { key : 'z', href : '#probe', word : 'Go to the probe' } );
		crm.logbook.addKind( { kind : 'probe-done', source : 'probe', sentence : function( inner, entry ) { return 'Probe '+ entry.data.label }, link : function() { return { href : '#probe', text : 'probe link' } } } );
		crm.wipe.addCount( { key : 'probe', rank : 60, goes : 'Probe data', count : 'probe rows: %s' } );
		crm.forms.extend( 'client-new', { key : 'probe', draw : function( inner, form ) { return kit.el( 'div', { class : 'card pad', id : 'probe-form' }, kit.formList( [ { name : 'probe_field', label : 'Probe field', control : kit.el( 'input', { id : 'f-probe', type : 'text' } ) } ], form ) ) }, values : function() { return { field : 'typed' } }, done : function( inner, answer ) { win.probeDone = answer.body.client.number } } );
		crm.forms.extend( 'project-new', { key : 'probe', draw : function() { return node( 'probe project form', 'probe-project-form' ) }, values : function() { return { field : 'project' } } } );
		crm.forms.extend( 'client-master', { key : 'probe', draw : function() { return node( 'probe master form', 'probe-master-form' ) } } );
		crm.records.addKind( { kind : 'probe-record', title : function() { return 'Probe record' }, body : function() { return [ [ 'Probe fact', 'x' ] ] } } );
		crm.sites.define( 'probe' );
		crm.sites.add( { key : 'probe', address : function( inner, thing ) { return '/probe/'+ thing.id } } );
		crm.extras.add( { key : 'mails-note', word : 'Probe mails note' } );
		crm.extras.add( { key : 'probe:own-place', word : function( inner, ...params ) { return ' own '+ params.join( '+' ) } } );
		crm.kit.add( 'probeHelper', function() { return 'helped' } );
		crm.apps.add( { key : 'probeapp', route : '/crm/probeapp', home : 'probe.home-', draw : function( inner ) { inner.main.appendChild( node( 'probe app', 'probe-app' ) ) } } );
		crm.state.own( 'probe', { count : 1 } ).count = 5;
		win.probeKit = kit;
		win.probe = crm.kit.publish( 'probe', { x : 1 } );

		function inner( text ) { return kit.card( 'Probe block', node( text, 'probe-block' ) ) }
	};

	{
		const answers = server( {
			'crm/overview' : function() { return [ 200, { summary : { clients : 2 }, needs : [ { kind : 'probe-need', package : 'probe', id : 7 }, { kind : 'unknown-need', package : 'other', id : 8 } ], packages : { probe : { n : 1 } } } ] },
			'crm-logbook/list' : function() { return [ 200, { entries : [ { id : 9, at : '2026-03-02T09:00:00Z', source : 'probe', kind : 'probe-done', text : '', by : 'system', data : { label : 'X-1' }, project : '', project_label : '', project_known : false } ], more : false } ] },
			'crm/wipe' : function() { return [ 200, { clients : 2, projects : 1, probe : 5, records : 0, logbook : 3, shares : 0 } ] },
		} );
		const w = world( { hash : '#overview', answers : answers, package : probe, packages : { probe : { perm : true, note : 'from the server' } } } );
		await settle();

		check( 'a menu entry, a page and its count: the rail has the entry of the package by its rank, with its count, and the page draws with the address after the hash', textOf( w.nav ).indexOf( 'Probe entry 4' ) !== -1 && w.win.Nino.crm.menu.entries().map( function( entry ) { return entry.key } ).join() === 'overview,clients,projects,probe,settings,values' );
		check( 'a tile and a block of the overview, what it loaded for the package, and the line of a need the package named - the need nobody drew is left out and not counted as a dead line', inMain( w, '#probe-tile' )[0].textContent === 'probe tile loaded' && inMain( w, '#probe-block' ).length === 1 && inMain( w, '.probe-need' ).length === 1 && hasText( inMain( w, '.probe-need' )[0], 'need 7' ) && hasText( w.main, texts['needs.none'] ) === false );
		check( 'the tile of the package stands before the one of the core by its rank', inMain( w, '#probe-tile' )[0].parentNode.children[0] === inMain( w, '#probe-tile' )[0] && inMain( w, '#probe-tile' )[0].parentNode.children.length === 2 );

		w.location.hash = '#probe-77';
		w.send( 'hashchange', {} );
		await settle();
		check( 'the page of the package draws the rest of its address and marks its entry in the rail', inMain( w, '#probe-page' )[0].textContent === 'probe page 77' && all( w.nav, 'a' ).filter( function( anchor ) { return hasText( anchor, 'Probe entry' ) && String( anchor.className ).split( ' ' ).includes( 'on' ) } ).length === 1 );

		// What the core hands over, read at run time and held against the description of the class as a set against a set: the kit of a package, and the object a page is given (the kit of the page and what belongs to the page).
		// A helper that a package adds to the kit is the package's, not the core's
		const given = pageInner.object;
		const made = first.win.Nino.crm.kit.make( 'probe' );
		const own = function( object ) { return Object.keys( object ).filter( function( name ) { return name !== 'probeHelper' } ).sort() };
		const words = function( heading, prefix ) {
			const at = declaredText.indexOf( heading );
			return at === -1 ? [ '(no such section)' ] : Array.from( declaredText.slice( at, declaredText.indexOf( ' - ', at ) === -1 || prefix === 'kit.' ? declaredText.indexOf( '. The object', at ) : declaredText.indexOf( ' - ', at ) ).matchAll( new RegExp( '\\b'+ prefix.replace( '.', '\\.' )+ '([A-Za-z_]+)', 'g' ) ) ).map( function( found ) { return found[1] } ).sort();
		};
		const declaredKitSet = words( 'THE MEMBERS OF THE KIT', 'kit.' );
		const declaredInnerSet = words( 'THE MEMBERS OF THE OBJECT OF A PAGE', 'inner.' );
		const same = function( one, two ) { return one.length === two.length && one.every( function( name, at ) { return name === two[at] } ) };

		check( 'the members of the kit ('+ declaredKitSet.length+ ') and of the object a page is given ('+ declaredInnerSet.length+ ') are the set the description of the class declares, name for name, read from what the core hands over - not counted by hand: a member that is added or goes without the description saying so is a failure of this line', given !== undefined && same( own( made ), declaredKitSet ) === true && same( own( given ), declaredInnerSet ) === true && typeof given.main === 'object' && typeof given.alive === 'function' && typeof given.arm === 'function' );

		const search = world( { hash : '#overview', answers : answers, package : probe } );
		await settle();
		type( inMain( search, '#q' )[0], 'zeb' );
		await settle();
		check( 'a source of hits is asked once, groups its hits under its word and lists them next to the ones of the core', hasText( inMain( search, '#search-results' )[0], 'Zebra' ) && hasText( inMain( search, '#search-results' )[0], 'Probe hits' ) && inMain( search, '#search-results [role="option"]' )[0].getAttribute( 'href' ) === '#probe' );
		type( inMain( search, '#q' )[0], 'nord' );
		await settle();
		check( 'the hits of the clients and of the projects are the core\'s own sources, named by their words', hasText( inMain( search, '#search-results' )[0], 'Nordlicht Records GmbH' ) && hasText( inMain( search, '#search-results' )[0], texts['search.group-client'] ) );

		const card = world( { hash : '#client-1', answers : answers, package : probe } );
		await settle();
		check( 'the card of a client: a tab with its number, a mark, a note, an action, an item of the menu and a fact of the package; the tab draws the record', inMain( card, '[data-tab="probe"]' ).length === 1 && textOf( inMain( card, '[data-tab="probe"]' )[0] ).indexOf( '7' ) !== -1 && inMain( card, '#probe-mark' ).length === 1 && inMain( card, '#probe-note' ).length === 1 && inMain( card, '#probe-action' ).length === 1 && hasText( card.main, 'Probe item' ) && hasText( card.main, 'Probe fact' ) && hasText( card.main, 'fact value' ) );
		click( card, inMain( card, '[data-tab="probe"]' )[0] );
		await settle();
		check( '...and the tab of the package draws what it is given: the record of the client', inMain( card, '#probe-client-tab' )[0].textContent === 'client K-0001' );

		const projectCard = world( { hash : '#project-12-probe', answers : answers, package : probe } );
		await settle();
		check( 'the card of a project: the tab of the package opens by its address, an action in the head and a fact', inMain( projectCard, '#probe-project-tab' )[0].textContent === 'project 12' && inMain( projectCard, '#probe-project-action' ).length === 1 && hasText( projectCard.main, 'project fact' ) );

		const clients = world( { hash : '#clients', answers : answers, package : probe } );
		await settle();
		check( 'the list of the clients: a mark after the company of each row and a column of the package with its head, read once for all the rows', inMain( clients, '.probe-mark' ).length === 3 && hasText( inMain( clients, 'thead' )[0], 'Probe column' ) && hasText( inMain( clients, 'tbody tr' )[0], 'cell 1' ) && clients.win.probeLoads === 1 );
		const projects = world( { hash : '#projects', answers : answers, package : probe } );
		await settle();
		check( 'the list of the projects and the table of the projects of a client have their column, too', hasText( inMain( projects, 'thead' )[0], 'Probe column' ) && hasText( inMain( projects, 'tbody tr' )[0], 'cell 12' ) && inMain( projects, '.probe-mark' ).length === 2 );

		const settings = world( { hash : '#settings/probe', answers : answers, package : probe } );
		await settle();
		check( 'a tab and a section of the settings: the tab stands after the ones of the core and opens by its address, the section is drawn into it', inMain( settings, '.settings-tabs a' ).map( function( tab ) { return tab.dataset.tab } ).join() === 'general,mails,probe,system' && inMain( settings, '#probe-settings' ).length === 1 );

		const mails = world( { hash : '#settings', answers : answers, package : probe } );
		await settle();
		check( 'a part of a sentence the package adds is said in the block of the mails', hasText( inMain( mails, '#mailnotes' )[0], 'Probe mails note' ) );

		const broken = server( { 'crm/status' : function() { return [ 200, { lines : STATUS_LINES, whole : false, open : { address : '/crm' }, orphans : [] } ] } } );
		const setup = world( { hash : '#setup', answers : broken, package : probe } );
		await settle();
		check( 'a step of the setup is a row of the list after the others, with its sentence, and keeps the list from being whole', inMain( setup, '#setup-rows li' ).some( function( row ) { return row.dataset.key === 'probe' && row.dataset.state === 'todo' && hasText( row, 'probe step open' ) } ) );

		press( w, 'g' );
		press( w, 'z' );
		await settle();
		check( 'a key of the package: g and its letter go to its address, and the list of the keys names it', w.location.hash === '#probe' && w.win.Nino.crm.app.model.shortcuts().some( function( pair ) { return pair[0] === 'g z' && pair[1] === 'Go to the probe' } ) );
		check( 'the keys of the core are words of the language: u, k and p in German, and the three of them are on the list', [ 'g u', 'g k', 'g p' ].every( function( keys ) { return w.win.Nino.crm.app.model.shortcuts().some( function( pair ) { return pair[0] === keys } ) } ) );
		check( 'a key that is taken is refused, so is one the page binds itself, so is a letter that is none', [ { key : 'k', href : '#x', word : 'x' }, { key : 'g', href : '#x', word : 'x' }, { key : 'zz', href : '#x', word : 'x' }, { key : 'y', href : 'https://elsewhere.example', word : 'x' } ].every( function( spec ) {
			try { w.win.Nino.crm.keys.add( spec ); return false } catch( error ) { return true }
		} ) === true );

		check( 'an extra a package names with its own key in front is kept for the place that asks for it and gets what the place gives; a bare name that is none of the six of the core, a name with no package, with two colons or with capitals is refused, and a name that is taken is refused', w.win.Nino.crm.extras.list().some( function( one ) { return one.key === 'probe:own-place' } ) && w.win.probeKit.extraText( 'probe:own-place', 1, 2 ) === ' own 1+2' && w.win.probeKit.extraText( 'probe:other-place' ) === '' && [ 'own-place', ':own-place', 'probe:', 'a:b:c', 'Probe:own', 'probe:Own', 'mails-nope' ].every( function( key ) {
			try { w.win.Nino.crm.extras.add( { key : key, word : 'x' } ); return false } catch( error ) { return error.name === 'TypeError' }
		} ) === true && ( function() { try { w.win.Nino.crm.extras.add( { key : 'probe:own-place', word : 'twice' } ); return false } catch( error ) { return true } }() ) === true && w.win.Nino.crm.extras.keys().length === 6 );

		const log = world( { hash : '#client-1-logbook', answers : answers, package : probe } );
		await settle();
		check( 'a kind of the logbook a package writes: the sentence is the package\'s, and so is the link of the entry', hasText( inMain( log, '.logbook-row' )[0], 'Probe X-1' ) && inMain( log, '.logbook-link' )[0].getAttribute( 'href' ) === '#probe' );

		const wipe = world( { hash : '#settings/system', answers : answers, package : probe } );
		await settle();
		click( wipe, inMain( wipe, '#wipe-open' )[0] );
		await settle();
		const lines = all( wipe.dialogs[0], 'li' ).map( textOf );
		check( 'the dialog of the test phase lists what goes by rank - the line of the package among them - and what stays', lines.indexOf( 'Probe data' ) > lines.indexOf( texts['wipe.goes-projects'] ) && lines.indexOf( texts['wipe.goes-numbers'] ) > lines.indexOf( 'Probe data' ) && lines.indexOf( texts['wipe.stays-settings'] ) !== -1 );
		check( 'a step the server runs for a package that no line speaks of is a plain line with its key after the lines of the script, and the step a line speaks of is not named twice', lines.filter( function( line ) { return line === texts['wipe.line-other'].replace( '%s', 'strange' ) } ).length === 1 && lines.indexOf( texts['wipe.line-other'].replace( '%s', 'strange' ) ) > lines.indexOf( texts['wipe.goes-numbers'] )
			&& lines.some( function( line ) { return line === texts['wipe.line-other'].replace( '%s', 'probe' ) } ) === false );
		const input = all( wipe.dialogs[0], 'input' )[0];
		{
			const yes = all( wipe.dialogs[0], 'button' ).pop();
			const off = yes.disabled === true;

			click( wipe, yes );
			type( input, texts['wipe.word'].toLowerCase() );
			const small = yes.disabled === true;
			type( input, texts['wipe.word'].slice( 0, -1 ) );
			const part = yes.disabled === true;
			click( wipe, yes );
			await settle();
			const early = wipe.all( 'crm/wipe' ).length;
			type( input, texts['wipe.word'] );

			check( 'the button of the test phase is off until the word is typed exactly (not a part, not in small letters), a press while it is off posts nothing, and the exact word turns it on', off === true && small === true && part === true && early === 0 && yes.disabled === false );
		}
		type( input, texts['wipe.word'] );
		click( wipe, all( wipe.dialogs[0], 'button' ).pop() );
		await settle();
		check( 'the toast of the test phase counts what went in the order the server names it, with the sentence each line gave; every count the server names has the sentence a line gave', wipe.last( 'crm/wipe' ).data.confirm === texts['wipe.word'] && textOf( wipe.toast ) === texts['wipe.done'].replace( '%s', [ texts['wipe.count-clients'].replace( '%s', '2' ), texts['wipe.count-projects'].replace( '%s', '1' ), 'probe rows: 5', texts['wipe.count-records'].replace( '%s', '0' ), texts['wipe.count-logbook'].replace( '%s', '3' ), texts['wipe.count-shares'].replace( '%s', '0' ) ].join( ', ' ) ) );

		// A run that was begun holds what a package that is removed - or that is on and has no such step - left over: the dialog asks whether to give it up, names the PACKAGE and sends forget only with its key typed
		for( const code of [ 'crm_wipe_held_gone', 'crm_wipe_held_step' ] ) {

			const sent = [];
			const held = server( { 'crm/wipe' : function( data ) { sent.push( data ); return data.forget === undefined ? [ 409, { error : 'x', code : code, params : [ 'crm-probe-two', 'twog' ] } ] : [ 200, { clients : 0, projects : 0, records : 0, logbook : 0, shares : 0 } ] } } );
			const gone = world( { hash : '#settings/system', answers : held, package : probe } );
			await settle();
			click( gone, inMain( gone, '#wipe-open' )[0] );
			await settle();
			type( all( gone.dialogs[0], 'input' )[0], texts['wipe.word'] );
			click( gone, all( gone.dialogs[0], 'button' ).pop() );
			await settle();
			const ask = gone.dialogs[gone.dialogs.length - 1];
			const said = ask === undefined ? '' : textOf( ask );
			const typed = ask === undefined ? null : all( ask, 'input' )[0];
			const yes = ask === undefined ? null : all( ask, 'button' ).pop();
			const early = sent.length;

			if( yes !== null ) {
				click( gone, yes );
				await settle();
			}

			const noKey = sent.length === early && yes !== null && yes.disabled === true;

			if( typed !== null ) {
				type( typed, 'twog' );
				click( gone, yes );
				await settle();
			}

			const stepOnly = sent.length === early;

			if( typed !== null ) {
				type( typed, 'crm-probe-two' );
				click( gone, yes );
				await settle();
			}

			check( 'a run that holds what a package left over - '+ code+ ' - is asked about in a dialog of its own that names the package and not the step, in the language of the page, with no key left raw: the button is off until the key of the PACKAGE is typed, the step is not its key, and then the post carries the word again and forget with the step and the package, as typed',
				said.includes( 'crm-probe-two' ) === true && said.includes( 'twog' ) === false && said.includes( 'crm_wipe' ) === false && noKey === true && stepOnly === true && sent.length === early + 1 && JSON.stringify( sent[sent.length - 1].forget ) === JSON.stringify( [ { step : 'twog', package : 'crm-probe-two' } ] ) && sent[sent.length - 1].confirm === texts['wipe.word'] && sent[0].forget === undefined );
		}

		{
			const off = world( { hash : '#settings/system', answers : server( { 'crm/wipe' : function() { return [ 409, { error : 'x', code : 'crm_wipe_held_off', params : [ 'crm-probe-two', 'twog' ] } ] } } ), package : probe } );
			await settle();
			click( off, inMain( off, '#wipe-open' )[0] );
			await settle();
			type( all( off.dialogs[0], 'input' )[0], texts['wipe.word'] );
			click( off, all( off.dialogs[0], 'button' ).pop() );
			await settle();
			const offText = textOf( off.dialogs[0] );

			check( 'a package that is only switched off is not given up: the dialog of the test phase stays, says the package - not the step - and that it has to be switched on, and there is no second dialog', off.dialogs.length === 1 && offText.includes( 'crm-probe-two' ) === true && offText.includes( 'twog' ) === false && offText.includes( 'crm_wipe' ) === false && offText.length > 40 );
		}

		// A run that is not finished: the list of the clients says so above the rows, with the button that opens the dialog of the test phase, and no banner where the server says nothing of it
		{
			const rows = server( { 'crm-clients/list' : function() { return [ 200, { clients : CLIENTS, erasing : [], wiping : true } ] }, 'crm/wipe' : function() { return [ 200, { clients : 0, projects : 0, records : 0, logbook : 0, shares : 0 } ] } } );
			const open = world( { hash : '#clients', answers : rows, package : probe } );
			await settle();
			const banner = inMain( open, '.wipe-pending' )[0];
			const quiet = world( { hash : '#clients', answers : answers, package : probe } );
			await settle();

			const label = textOf( inMain( open, '#wipe-finish' )[0] );

			click( open, inMain( open, '#wipe-finish' )[0] );
			await settle();
			const asked = open.dialogs.length === 1 && open.sent().includes( 'crm/settings-get' ) === true;

			if( asked === true ) {
				type( all( open.dialogs[0], 'input' )[0], texts['wipe.word'] );
				click( open, all( open.dialogs[0], 'button' ).pop() );
				await settle();
			}

			check( 'a run of the test phase that is not finished is shown above the list of the clients with its sentence and the button that finishes it, the button opens the dialog of the test phase and the word posts the wipe, and a list the server says nothing of has no banner', banner !== undefined && textOf( banner ).includes( texts['wipe.pending'] ) === true && label === texts['wipe.finish']
				&& inMain( quiet, '.wipe-pending' ).length === 0 && asked === true && open.last( 'crm/wipe' ).data.confirm === texts['wipe.word'] );
		}

		// A row that cannot be read: the dialog of the test phase stays, a second dialog throws the rest away with the word typed again, and the post carries discard
		{
			const sent = [];
			const broken = server( { 'crm/wipe' : function( data ) { sent.push( data ); return data.discard === true ? [ 200, { clients : 0, projects : 0, records : 0, logbook : 0, shares : 0 } ] : [ 409, { error : 'x', code : 'crm_wipe_journal' } ] } } );
			const dead = world( { hash : '#settings/system', answers : broken, package : probe } );
			await settle();
			click( dead, inMain( dead, '#wipe-open' )[0] );
			await settle();
			type( all( dead.dialogs[0], 'input' )[0], texts['wipe.word'] );
			click( dead, all( dead.dialogs[0], 'button' ).pop() );
			await settle();
			const second = dead.dialogs[dead.dialogs.length - 1];
			const text = second === undefined ? '' : textOf( second );
			const yes = second === undefined ? null : all( second, 'button' ).pop();
			const off = yes !== null && yes.disabled === true;
			const early = sent.length;

			if( second !== undefined ) {
				type( all( second, 'input' )[0], texts['wipe.word'] );
				click( dead, yes );
				await settle();
			}

			check( 'a row of the run that cannot be read has a way out at the surface: a dialog of its own says what is lost and what may stay, its button is off until the word of the test phase is typed, and the post then carries discard', dead.dialogs.length === 2 && text.includes( texts['wipe.discard-title'] ) === true && text.includes( 'crm_wipe' ) === false && off === true && sent.length === early + 1 && sent[sent.length - 1].discard === true && sent[sent.length - 1].confirm === texts['wipe.word'] && sent[0].discard === undefined );
		}

		// While a run is open the button of the new client is off and says why in words that stand beside it
		{
			const open = world( { hash : '#clients', answers : server( { 'crm-clients/list' : function() { return [ 200, { clients : CLIENTS, erasing : [], wiping : true } ] } } ), package : probe } );
			await settle();
			const button = inMain( open, '#new-client' )[0];
			const why = inMain( open, '#new-client-why' )[0];

			check( 'while a run of the test phase is open the button of the new client is off and the reason stands beside it, not in a hint alone', button !== undefined && button.tagName === 'BUTTON' && button.disabled === true && why !== undefined && textOf( why ) === texts['clients.new-wiping'] && button.getAttribute( 'aria-describedby' ) === 'new-client-why' );
		}

		const form = world( { hash : '#client-new', answers : answers, package : probe } );
		await settle();
		type( inMain( form, '#f-company' )[0], 'Fresh Ltd' );
		fire( inMain( form, '#new-client-form' )[0], 'submit', { preventDefault : function() {} } );
		await settle();
		check( 'a part of the form of a new client is drawn in it, what it posts travels under packages.<key>, and the package is told how it went', inMain( form, '#probe-form' ).length === 0 && form.last( 'crm-clients/save' ).data.packages !== undefined && form.last( 'crm-clients/save' ).data.packages.probe.field === 'typed' && form.last( 'crm-clients/save' ).data.probe_field === undefined && form.win.probeDone === 'K-0009' );
		const projectForm = world( { hash : '#projects-new-k1', answers : server( { 'elements/save' : function() { return [ 200, { element : { '.uri' : 'crm-projects/20' } } ] } } ), package : probe } );
		await settle();
		type( inMain( projectForm, '#f-title' )[0], 'Probe project' );
		fire( inMain( projectForm, '#project-form' )[0], 'submit', { preventDefault : function() {} } );
		await settle();
		check( 'the part of the form of a new project posts under packages, next to the fields of the element', projectForm.last( 'elements/save' ).data.packages.probe.field === 'project' && inMain( projectForm, '#probe-project-form' ).length === 0 );
		const master = world( { hash : '#client-1', answers : answers, package : probe } );
		await settle();
		click( master, inMain( master, '.menu button' ).find( function( button ) { return button.textContent === texts['card.edit'] } ) );
		check( 'the part of the form of the master data is drawn when the card is edited', inMain( master, '#probe-master-form' ).length === 1 );

		const app = world( { hash : '', app : 'probeapp', answers : answers, package : probe } );
		await settle();
		check( 'an app of a package draws its own screen with no rail, and the page names the key of the app in the data block', inMain( app, '#probe-app' ).length === 1 && app.win.Nino.crm.apps.list().map( function( one ) { return one.key } ).join() === 'todo,probeapp' );

		check( 'the state of a package is the same object whenever it asks, and the page starts it again at its start', w.win.Nino.crm.state.own( 'probe', { count : 1 } ).count === 1 );
		check( 'the kit a package got at load time draws with what the page has: a helper added is there for what is drawn after, what a package published is frozen and under its key, the data the server put under the key is read', w.win.Nino.crm.kit.make( 'again' ).probeHelper() === 'helped' && Object.isFrozen( w.win.probe ) === true && w.win.Nino.crm.probe.x === 1 && w.win.probeKit.data( 'probe' ).note === 'from the server' && w.win.probeKit.data( 'none' ) === null );
		check( 'a name the core has, or another package has, cannot be published; the same helper twice is refused', ( function() { try { w.win.Nino.crm.kit.publish( 'kit', {} ); return false } catch( error ) { try { w.win.Nino.crm.kit.add( 'el', function() {} ); return false } catch( again ) { return true } } }() ) );
		check( 'a record of a kind a package entered shows in a dialog with the title the package gave and the facts it added', ( function() { w.win.Nino.crm.kit.make( 'x' ).showRecord( { kind : 'probe-record', at : '2026-03-02T09:00:00Z', ip : '1.2.3.4', agent : 'a', sha256 : 'abc', shown : 'text' } ); return true }() ) && hasText( w.dialogs[w.dialogs.length - 1], 'Probe record' ) && hasText( w.dialogs[w.dialogs.length - 1], 'Probe fact' ) );
		check( 'a page of the customer\'s side a package defined is known by its address and for no other kind', w.win.Nino.crm.sites.has( 'probe' ) === true && w.win.Nino.crm.kit.make( 'x' ).sitePage( 'probe', { id : 3 } ) === '/probe/3' && w.win.Nino.crm.kit.make( 'x' ).sitePage( 'other', {} ) === null );
	}

	// === E. the router, the language, the session and what a person typed
	{
		const answers = server();
		const old = world( { hash : '#kunden', answers : answers } );
		await settle();

		check( 'an address of the first version is not understood: the overview is drawn, nothing leads from #kunden to the clients', hasText( old.main, texts['overview.title'] ) && old.win.Nino.crm.app.parse( '#kunde-1' ).screen === 'overview' && old.win.Nino.crm.app.parse( '#projekte' ).screen === 'overview' && old.win.Nino.crm.app.parse( '#einstellungen' ).screen === 'overview' );
		check( 'the addresses of the core: #clients, #client-<id>-<tab>, #client-new, #projects-new-k<id>, #project-<id>-<tab>, #values/<type>/e/<key>, #settings/<tab>/<block>, #setup', [ [ '#clients', 'clients' ], [ '#client-12-logbook', 'client' ], [ '#client-new', 'clientnew' ], [ '#projects-new-k4', 'projectnew' ], [ '#project-9-shares', 'project' ], [ '#values/crm-phases/e/mix', 'values' ], [ '#settings/general/brand', 'settings' ], [ '#setup', 'setup' ], [ '#overview', 'overview' ] ].every( function( pair ) { return old.win.Nino.crm.app.parse( pair[0] ).screen === pair[1] } ) );

		const english = world( { hash : '#client-1', locale : 'en_US', answers : answers } );
		await settle();

		check( 'in English the page says its words in English and writes the day and the money as the language does', hasText( english.main, textsEn['card.master'] ) && hasText( english.main, '01/05/2026' ) && english.win.Nino.crm.format.locale() === 'en_US' && hasText( english.main, textsEn['card.since'] ) );

		const german = world( { hash : '#client-1', answers : answers } );
		await settle();
		check( 'in German the day is written d.m.Y and the format of the page is German', hasText( german.main, '05.01.2026' ) && german.win.Nino.crm.format.locale() === 'de_DE' );

		// the session that ended, the token that is stale
		const ended = world( { hash : '#clients', answers : server( { 'crm-clients/list' : function() { return [ 401, { error : 'x', code : 'session' } ] } } ) } );
		await settle();
		check( 'a session that ended says so in a dialog with the way back to this page and the hash', ended.dialogs.length === 1 && hasText( ended.dialogs[0], texts['session.title'] ) && all( ended.dialogs[0], 'a' )[0].getAttribute( 'href' ) === '/crm#clients' );

		const stale = world( { hash : '#clients', answers : server( { 'crm-clients/list' : function() { return [ 403, { error : 'x', code : 'csrf' } ] } } ) } );
		await settle();
		const once = stale.reloads.length;
		stale.app.route();
		await settle();
		check( 'a stale token reads the page again, once: the second refusal is the person\'s to read', once === 1 && stale.reloads.length === 1 && stale.storage['crm-reloaded'] === '1' );

		// what a person typed is text, in every view that shows it
		const bad = '<img src=x onerror=alert(1)>';
		const evil = Object.assign( {}, CLIENTS[0], { company : bad, name : bad, title : bad, city : bad, email : 'javascript:alert(1)', phone : 'javascript:alert(2)', notes : bad } );
		const hostile = server( {
			'crm-clients/list' : function() { return [ 200, { clients : [ evil ], erasing : [] } ] },
			'crm-clients/get' : function() { return [ 200, { client : evil } ] },
			'crm-projects/list' : function() { return [ 200, { missing : false, native : 'de_DE', phases : PHASES, projects : [ Object.assign( {}, PROJECTS[0], { title : bad, client_title : bad } ) ], shared : [] } ] },
			'crm-projects/get' : function() { return [ 200, { native : 'de_DE', phases : PHASES, project : Object.assign( {}, PROJECTS[0], { title : bad, description : bad } ), waiting : null, client : { id : 1, number : 'K-0001', title : bad, active : true }, tasks : 1, shares : [] } ] },
			'crm-tasks/list' : function() { return [ 200, { tasks : [ Object.assign( {}, TASKS[0], { text : bad } ) ] } ] },
			'crm-logbook/list' : function() { return [ 200, { entries : [ { id : 1, at : '2026-03-02T09:00:00Z', source : 'crm', kind : 'note', text : bad, by : bad, data : {}, project : '12', project_label : bad, project_known : true } ], more : false } ] },
		} );
		const markup = function( w ) { return findAll( w.main, function( node ) { return [ 'IMG', 'SCRIPT', 'IFRAME', 'OBJECT', 'EMBED' ].includes( String( node.tagName ) ) } ).length };
		let injected = 0;
		let shown = 0;

		for( const hash of [ '#clients', '#client-1', '#client-1-notes', '#client-1-logbook', '#client-1-projects', '#projects', '#project-12', '#project-12-logbook', '#overview', '#settings' ] ) {

			const w = world( { hash : hash, answers : hostile } );
			await settle();
			injected += markup( w );
			shown += hasText( w.main, bad ) || values( w, 'textarea' ).includes( bad ) ? 1 : 0;

			// links that are made from what was typed: only an address of the plain shape, and a hash of this page
			findAll( w.main, function( node ) { return String( node.tagName ) === 'A' } ).forEach( function( anchor ) {
				if( /^(javascript|data|vbscript):/i.test( String( anchor.getAttribute( 'href' ) ) ) === true )
					injected++;
			} );
		}

		check( 'a company, a name, a city, a note, a title, a task and a line of the logbook with markup in them is text in every view: no element is made from it', injected === 0 && shown >= 8 );
		const plain = world( { hash : '#client-1', answers : hostile } );
		await settle();
		check( 'an address and a phone number that are no address are shown as text and are no link', inMain( plain, '#client-mail' ).length === 0 && inMain( plain, '#client-tel' ).length === 0 && hasText( plain.main, 'javascript:alert(1)' ) && hasText( plain.main, 'javascript:alert(2)' ) );

		// what a package gives is guarded
		const guard = world( { hash : '#client-1', answers : server(), package : function( win ) {
			const crm = win.Nino.crm;
			crm.cards.addTab( 'client', { key : 'throws', word : 'Throws', draw : function() { throw new Error( 'broken' ) } } );
			crm.cards.addTab( 'client', { key : 'empty', word : 'Empty', draw : function() { return win.document.createElement( 'div' ) } } );
			crm.cards.addHead( 'client', { key : 'bad-mark', slot : 'marks', draw : function() { return 'text is no node' } } );
			crm.lists.addColumn( 'client', { key : 'throws', head : 'Throws', cell : function() { throw new Error( 'broken' ) } } );
		} } );
		await settle();
		const failing = inMain( guard, '[data-tab="throws"]' )[0];
		click( guard, failing );
		await settle();
		check( 'code of a package that throws leaves a sentence in its place and takes nothing else down', hasText( guard.main, texts['extension.tab-failed'] ) && hasText( guard.main, texts['extension.head-failed'] ) && hasText( guard.main, 'Hafenstraße 12' ) && guard.errors.length >= 2 );
		click( guard, inMain( guard, '[data-tab="empty"]' )[0] );
		await settle();
		check( 'a tab that draws nothing says so, and the sentence does not take the card down', hasText( inMain( guard, '#tab' )[0], texts['extension.tab-failed'] ) && hasText( inMain( guard, '#tab' )[0], texts['extension.key'].replace( '%s', 'empty' ) ) && hasText( guard.main, 'Hafenstraße 12' ) );

		// a package that registers late has the screen drawn again
		const late = world( { hash : '#client-1', answers : server() } );
		await settle();
		late.win.Nino.crm.cards.addTab( 'client', { key : 'late', word : 'Late tab', draw : function() { return late.win.document.createElement( 'span' ) } } );
		await settle();
		check( 'a tab registered after the page is up is on the card', inMain( late, '[data-tab="late"]' ).length === 1 );

		// registrations that are none
		const crm = late.win.Nino.crm;
		const refused = function( run ) { try { run(); return false } catch( error ) { return true } };

		check( 'what is no entry is refused: an address of another host, one that starts with two slashes, a script address, a key twice, an app with no route below /crm, a form that is none, a part that is no function', [
			function() { crm.menu.add( { key : 'x1', word : 'X', href : 'https://elsewhere.example' } ) },
			function() { crm.menu.add( { key : 'x2', word : 'X', href : '//elsewhere.example' } ) },
			function() { crm.menu.add( { key : 'x3', word : 'X', href : 'javascript:alert(1)' } ) },
			function() { crm.menu.add( { key : 'overview', word : 'X', page : 'overview' } ) },
			function() { crm.apps.add( { key : 'x4', route : '/elsewhere', draw : function() {} } ) },
			function() { crm.forms.extend( 'no-form', { key : 'x5', draw : function() {} } ) },
			function() { crm.search.add( { key : 'x6', load : 'no', hits : function() {} } ) },
			function() { crm.wipe.addCount( { key : 'x7', goes : '' } ) },
			function() { crm.cards.addTab( 'nobody', { key : 'x8', word : 'X', draw : function() {} } ) },
			function() { crm.logbook.addKind( { kind : 'x9', sentence : 'no' } ) },
			function() { crm.overview.addData( { key : 'summary', load : function() {} } ) },
			function() { crm.state.own( 'x10', [] ) },
			function() { crm.kit.make( 'Bad Key' ) },
		].every( refused ) === true );

		// the names under Nino.crm are the core's: set once, frozen, never assigned again, and the page does not hand its state to anybody
		const names = Object.keys( crm ).filter( function( name ) { return name !== 'probe' } );
		const assigns = function( target, name ) { try { target[name] = function() {}; return target[name] !== undefined && String( target[name] ) === String( function() {} ) } catch( error ) { return false } };
		check( 'Nino.crm and every namespace under it are frozen: a name cannot be assigned again, a function of one cannot be put in another\'s place, a name cannot be added and none can be deleted', Object.isFrozen( crm ) === true && names.length >= 20
			&& names.every( function( name ) { return typeof crm[name] !== 'object' || Object.isFrozen( crm[name] ) === true } ) === true && assigns( crm, 'menu' ) === false && assigns( crm.cards, 'addTab' ) === false && assigns( crm.kit, 'publish' ) === false && assigns( crm, 'added' ) === false
			&& refused( function() { delete crm.menu } ) === true && refused( function() { late.win.Nino.crm = {} } ) === true );
		check( 'a name cannot be published after the page has started, and the handle of the page holds no state', refused( function() { crm.kit.publish( 'tardy', {} ) } ) === true && crm.app.state === undefined && Object.keys( crm.app ).every( function( key ) { return [ 'route', 'parse', 'el', 't', 'model' ].includes( key ) } ) === true
			&& Object.isFrozen( crm.app.model ) === true );
		const guarded = crm.kit.make( 'guarded' );
		const before = late.win.Nino.crm.app.t( 'nav.clients' );
		guarded.words( { 'nav.clients' : 'Taken over', 'constructor' : 'x', 'toString' : 'y', '__proto__' : 'z', 'guarded.fresh' : 'Fresh' } );
		check( 'a package cannot overwrite a word of the core with words(): a word the page has stays, a word it has not is added, a key such as constructor is a word like another and toString or __proto__ is no word of a package', late.win.Nino.crm.app.t( 'nav.clients' ) === before && before !== 'Taken over'
			&& late.win.Nino.crm.app.t( 'guarded.fresh' ) === 'Fresh' && guarded.has( 'constructor' ) === true && guarded.has( 'toString' ) === false && guarded.has( 'hasOwnProperty' ) === false && guarded.has( '__proto__' ) === false );
		{
			const made = guarded.menu( { label : 'More', aria : 'More', items : [ { label : 'Script', href : 'javascript:alert(1)' }, { label : 'Host', href : 'https://elsewhere.example/x' }, { label : 'Slashes', href : '//elsewhere.example' }, { label : 'Number', href : 7 }, { label : 'Good', href : '/crm#projects' } ] } );
			const anchors = all( made, 'a' );

			check( 'an item of a menu of a package that names an address that is none of this site is no link (a script, another host, two slashes, a number); one that names a page of this site is', anchors.length === 1 && anchors[0].getAttribute( 'href' ) === '/crm#projects' && all( made, '[role="menuitem"]' ).length === 5 );
		}
		check( 'the data of a package is found by its own key: constructor, toString and __proto__ are no data', [ 'constructor', 'toString', '__proto__', 'hasOwnProperty', 'valueOf' ].every( function( key ) { return guarded.data( key ) === null } ) === true );

		// the link to the content, and what was typed and not stored
		{
			const guard = world( { hash : '#client-1-notes', answers : server() } );
			await settle();
			const area = inMain( guard, '#notes' )[0];
			const dialogsNow = function() { return guard.dialogs.length };
			const skip = guard.page.createElement( 'a' );

			skip.className = 'skip';
			skip.setAttribute( 'href', '#main' );
			guard.main.parentNode.appendChild( skip );
			type( area, 'Typed, not stored', false );
			const prevented = click( guard, skip );
			check( 'the link to the content keeps the address and the screen, takes the focus to the content and asks nothing, and what was typed stays', prevented === true && guard.location.hash === '#client-1-notes' && guard.main.focused === true && dialogsNow() === 0 && inMain( guard, '#notes' )[0] === area && area.value === 'Typed, not stored' );

			const link = all( guard.nav, 'a' ).find( function( anchor ) { return anchor.getAttribute( 'href' ) === '#projects' } );

			check( 'a link of the rail away from notes that were typed and not stored asks first, and no is the screen as it was', ( function() { click( guard, link ); return dialogsNow() === 1 && guard.location.hash === '#client-1-notes' } )() === true );
			click( guard, all( guard.dialogs[0], 'button' )[0] );
			await settle();
			check( 'no keeps the notes and the address', guard.location.hash === '#client-1-notes' && inMain( guard, '#notes' )[0].value === 'Typed, not stored' );

			// the address typed, or the back button: the address is put back and the question is asked
			guard.location.hash = '#projects';
			await settle();
			check( 'an address that changes under notes that were not stored (the back button, a typed address) is put back and the question is asked', guard.location.hash === '#client-1-notes' && guard.dialogs.length === 2 && inMain( guard, '#notes' )[0].value === 'Typed, not stored' );
			click( guard, all( guard.dialogs[1], 'button' )[0] );
			await settle();
			press( guard, 'g' );
			press( guard, 'p' );
			await settle();
			check( 'a key of the page away from them asks as well', guard.dialogs.length === 3 && guard.location.hash === '#client-1-notes' );
			click( guard, all( guard.dialogs[2], 'button' ).pop() );
			await settle();
			check( 'yes lets go: the screen of the address is drawn and nothing is posted for the notes', guard.location.hash === '#projects' && hasText( guard.main, texts['projects.title'] ) && guard.all( 'crm-clients/save' ).length === 0 );
		}

		// leaving the page with what was typed: the reload and the close ask as the form does, and the sign-out asks before the session ends
		{
			const door = world( { hash : '#client-1-notes', answers : server( { 'crm/signout' : function() { return [ 200, { ok : true } ] } } ) } );
			await settle();
			const unload = function() {

				const event = { prevented : false, returnValue : undefined, preventDefault : function() { this.prevented = true } };

				door.send( 'beforeunload', event );

				return event.prevented === true && event.returnValue === '';
			};
			const out = door.page.getElementById( 'signout' );

			check( 'nothing typed: the reload and the close ask nothing', unload() === false );
			type( inMain( door, '#notes' )[0], 'Typed, not stored', false );
			check( 'notes that were typed and not stored: the reload and the close ask, as a form of the page does', unload() === true );
			click( door, out );
			check( 'the sign-out asks first, and the session is not ended while the question stands', door.dialogs.length === 1 && door.all( 'crm/signout' ).length === 0 && door.reloads.length === 0 );
			click( door, all( door.dialogs[0], 'button' )[0] );
			await settle();
			check( 'no leaves it as it was: no sign-out was sent, the notes are there and the reload still asks', door.all( 'crm/signout' ).length === 0 && inMain( door, '#notes' )[0].value === 'Typed, not stored' && unload() === true );
			click( door, out );
			click( door, all( door.dialogs[1], 'button' ).pop() );
			await settle();
			check( 'yes ends the session: one sign-out, then the page is made again, and the reload that follows asks nothing', door.all( 'crm/signout' ).length === 1 && door.reloads.length === 1 && unload() === false );

			const calm = world( { hash : '#projects', answers : server( { 'crm/signout' : function() { return [ 200, { ok : true } ] } } ) } );
			await settle();
			click( calm, calm.page.getElementById( 'signout' ) );
			await settle();
			check( 'with nothing typed the sign-out asks nothing', calm.dialogs.length === 0 && calm.all( 'crm/signout' ).length === 1 && calm.reloads.length === 1 );
		}

		// a session that is taken up again has a new token: the answer of the sign-out of the other devices carries it, and the next action is posted with it
		{
			const dev = world( { hash : '#overview', device : { remembered : true, count : 2 }, answers : server( { 'crm/devices-forget' : function() { return [ 200, { remembered : true, count : 0, csrf : 'token-2' } ] } } ) } );
			await settle();
			const token = function( action ) { return dev.last( action ).init.headers['X-CSRF-Token'] };
			const before = token( 'crm/overview' );

			click( dev, inMain( dev, '[data-device="forget"]' )[0] );
			click( dev, all( dev.dialogs[0], 'button' ).pop() );
			await settle();
			dev.location.hash = '#clients';
			await settle();
			check( 'every device of the account signed out: the token the answer carries is the one the next action is posted with, and nothing of it stays in what the card shows', before === 'token-1' && dev.all( 'crm/devices-forget' ).length === 1 && token( 'crm-clients/list' ) === 'token-2' && dev.reloads.length === 0 );
		}

		// the deletion of a project: the rows that stay, a veto and a busy database are said
		{
			let reply = [ 200, { ok : true } ];
			const answers = server( { 'elements/delete' : function() { return reply } } );
			const attempt = async function() {

				const w = world( { hash : '#project-12', answers : answers } );
				await settle();
				click( w, inMain( w, '.menu button' ).find( function( button ) { return button.textContent === texts['projects.delete'] } ) );
				await settle();
				click( w, all( w.dialogs[0], 'button' ).pop() );
				await settle();

				return w;
			};
			const plain = await attempt();
			reply = [ 200, { ok : true, leftover : true } ];
			const rest = await attempt();
			reply = [ 409, { error : 'Held by <b>the invoice</b> [csrf]', code : 'crm_elements_vetoed' } ];
			const vetoed = await attempt();
			reply = [ 503, { error : 'busy', code : 'crm_busy' } ];
			const busy = await attempt();

			check( 'a project that is deleted says so, and where the rows that hung on it stay for later it says that: no error', textOf( plain.toast ) === texts['projects.deleted'] && textOf( rest.toast ) === texts['projects.deleted-leftover'] && plain.location.hash === '#projects' && rest.location.hash === '#projects' );
			check( 'a veto is said with the sentence of the one who holds the element, as text and as it was written, and the screen stays; a busy database says that and stays', textOf( vetoed.toast ) === serverWords.de_DE['error.crm_elements_vetoed']+ ' Held by <b>the invoice</b> [csrf]' && all( vetoed.toast, 'b' ).length === 0 && vetoed.location.hash === '#project-12'
				&& textOf( busy.toast ) === serverWords.de_DE['error.crm_busy'] && busy.location.hash === '#project-12' );
		}

		// the steps of a removed package that hold a deletion up
		{
			const gone = server( { 'crm-clients/delete' : function( data ) {
				return data.skip === undefined ? [ 409, { error : 'x', code : 'crm_package_gone', params : [ 'gone' ], skippable : [ 'gone' ] } ] : [ 200, { number : 'K-0001', skipped : data.skip } ];
			} } );
			const w = world( { hash : '#client-1', answers : gone } );
			await settle();
			click( w, inMain( w, '.menu button' ).find( function( button ) { return button.textContent === texts['client-delete.menu'] } ) );
			await settle();
			const box = w.dialogs[0];
			const yes = function() { return all( box, 'button' ).pop() };

			type( all( box, 'input' )[0], 'K-0001' );
			click( w, yes() );
			await settle();
			const skip = all( box, '[data-skip="gone"]' )[0];

			check( 'a deletion that a removed package holds up names the steps the server says may be given up, one button for each, and posts none of them yet', skip !== undefined && skip.textContent === texts['client-delete.skip-button'].replace( '%s', 'gone' ) && w.all( 'crm-clients/delete' ).length === 1 && w.all( 'crm-clients/delete' )[0].data.skip === undefined );
			click( w, skip );
			await settle();
			check( 'giving a step up is asked first with the key of the step named, and no leaves it as it was', w.dialogs.length === 2 && hasText( w.dialogs[1], texts['client-delete.skip-title'].replace( '%s', 'gone' ) ) === true );
			click( w, all( w.dialogs[1], 'button' )[0] );
			await settle();
			check( 'no: nothing is given up and the button is still there', all( box, '[data-skipped]' ).length === 0 && all( box, '[data-skip="gone"]' ).length === 1 );
			click( w, skip );
			await settle();
			click( w, all( w.dialogs[w.dialogs.length - 1], 'button' ).pop() );
			await settle();
			check( 'yes: the dialog says which step will be given up, and the button of it is gone', all( box, '[data-skipped="gone"]' ).length === 1 && all( box, '[data-skip="gone"]' ).length === 0 );
			click( w, yes() );
			await settle();
			check( 'the next press posts the list of the steps given up, and the sentence of the end names them', w.all( 'crm-clients/delete' ).length === 2 && w.all( 'crm-clients/delete' )[1].data.skip.join() === 'gone' && textOf( w.toast ) === texts['client-delete.done-skipped'].replace( '%s', 'K-0001' ).replace( '%s', 'gone' ) );

			// a package that is only off, or whose data are there: nothing may be given up
			const off = server( { 'crm-clients/delete' : function() { return [ 409, { error : 'x', code : 'crm_package_off', params : [ 'x' ], skippable : [] } ] } } );
			const o = world( { hash : '#client-1', answers : off } );
			await settle();
			click( o, inMain( o, '.menu button' ).find( function( button ) { return button.textContent === texts['client-delete.menu'] } ) );
			await settle();
			type( all( o.dialogs[0], 'input' )[0], 'K-0001' );
			click( o, all( o.dialogs[0], 'button' ).pop() );
			await settle();
			check( 'where the server says no step may be given up there is no button for it, and the sentence of the refusal stands in the dialog', all( o.dialogs[0], '[data-skip]' ).length === 0 && hasText( o.dialogs[0], 'crm_package_off' ) === false );

			// the button is for the answer that says the package is removed and nothing else: a package that is not removed, or whose data are there, offers no step even if a list came with the answer
			for( const code of [ 'crm_package_off', 'crm_package_data' ] ) {

				const other = world( { hash : '#client-1', answers : server( { 'crm-clients/delete' : function() { return [ 409, { error : 'x', code : code, params : [ 'x' ], skippable : [ 'x' ] } ] } } ) } );
				await settle();
				click( other, inMain( other, '.menu button' ).find( function( button ) { return button.textContent === texts['client-delete.menu'] } ) );
				await settle();
				type( all( other.dialogs[0], 'input' )[0], 'K-0001' );
				click( other, all( other.dialogs[0], 'button' ).pop() );
				await settle();
				check( 'the answer '+ code+ ' offers no step to give up, whatever list comes with it', all( other.dialogs[0], '[data-skip]' ).length === 0 );
			}
		}

		// a half block that stands alone in its row takes the row
		{
			const home = world( { hash : '#overview', answers : server() } );
			await settle();
			const rows = inMain( home, '.cols' );

			check( 'a block of half the width that is alone in its row is a row of one column: no empty half beside it', rows.length >= 1 && rows.every( function( row ) { return row.children.length !== 1 || String( row.className ).split( ' ' ).includes( 'one' ) === true } ) === true && rows.some( function( row ) { return row.children.length === 1 } ) === true );
		}

		// the way of the head names the screen as the head does
		{
			const fresh = world( { hash : '#client-new', answers : server() } );
			const project = world( { hash : '#projects-new', answers : server() } );
			await settle();

			check( 'the way above a new client and a new project ends in the name of the screen as it is written, with its capital', textOf( inMain( fresh, '.crumb' )[0] ).endsWith( ' › '+ texts['newclient.title'] ) === true && textOf( inMain( project, '.crumb' )[0] ).endsWith( ' › '+ texts['projects.new-title'] ) === true );
		}

		// nothing typed: nothing asked
		{
			const calm = world( { hash : '#client-1-notes', answers : server() } );
			await settle();
			calm.location.hash = '#projects';
			await settle();
			check( 'where nothing was typed the way out is free: no question, the screen of the address', calm.dialogs.length === 0 && calm.location.hash === '#projects' );
		}

		// the keys
		const keyed = world( { hash : '#overview', answers : server() } );
		await settle();
		press( keyed, 'g' );
		press( keyed, 'k' );
		await settle();
		check( 'g and the letter of a screen go to it: g k leads to the clients in German', keyed.location.hash === '#clients' );
		press( keyed, '?', null );
		check( '? shows the list of the keys with the ones of the core and the keys of the page, in a dialog', keyed.dialogs.length === 1 && hasText( keyed.dialogs[0], 'g k' ) && hasText( keyed.dialogs[0], texts['keys.slash'] ) && hasText( keyed.dialogs[0], texts['keys.save-chord'] ) );
		const typing = world( { hash : '#clients', answers : server() } );
		await settle();
		press( typing, 'g', inMain( typing, '#q' )[0] );
		press( typing, 'u', inMain( typing, '#q' )[0] );
		check( 'a letter typed into a field is the field\'s: no key of the page works there', typing.location.hash === '#clients' );
		press( typing, '/', null );
		check( 'the slash goes to the search field where nothing is being typed', inMain( typing, '#q' )[0].focused === true );

		// the app of the tasks has no keys, no rail, no overview
		const todo = world( { hash : '', app : 'todo', answers : server() } );
		await settle();
		press( todo, 'g' );
		press( todo, 'k' );
		check( 'an app has no keys of the page: g k leads nowhere', todo.location.hash === '' );
	// F. the login: a refusal is said at the field it is about
	{
		const mount = element('div');
		const field = function( id, tag ) { const node = element( tag || 'input' ); node.id = id; mount.appendChild( node ); return node };
		const form = field( 'login', 'form' );
		const user = field( 'login-user' );
		const pw = field( 'login-pw' );
		const msg = field( 'login-msg', 'p' );
		field( 'login-submit', 'button' );
		const block = field( 'crm-data', 'script' );
		const replaced = [];
		let status = 401;

		block.textContent = JSON.stringify( { base : '', route : '/crm', csrf : 't', texts : { 'login.wrong' : 'Wrong pair', 'login.stale' : 'Stale', 'login.status' : 'Status %s', 'login.offline' : 'Offline' } } );

		const page = documentOf( mount );
		page.readyState = 'loading';
		const listeners = [];
		page.addEventListener = function( name, fn ) { listeners.push( fn ) };
		const win = { Nino : {}, TextEncoder : TextEncoder, btoa : btoa, fetch : function() { return Promise.resolve( { status : status } ) }, location : { hash : '', replace : function( to ) { replaced.push( to ) }, reload : function() { replaced.push( 'reload' ) } } };
		const context = vm.createContext( { window : win, document : page, console : console } );

		load( OWN+ 'assets/login.js', context );
		listeners.forEach( function( fn ) { fn() } );

		type( user, 'owner@example.com', false );
		type( pw, 'wrong', false );
		fire( form, 'submit', { preventDefault : function() {} } );
		await settle();

		check( 'a pair the kernel refuses is said in the sentence under the password field', msg.textContent === 'Wrong pair' );
		check( 'the field is invalid and described by the sentence, and holds nothing', pw.getAttribute( 'aria-invalid' ) === 'true' && pw.getAttribute( 'aria-describedby' ) === 'login-msg' && pw.value === '' && pw.focused === true && replaced.length === 0 );
		type( pw, 'x', false );
		check( 'typing again takes the mark and the sentence away', pw.getAttribute( 'aria-invalid' ) === null && pw.getAttribute( 'aria-describedby' ) === null && msg.textContent === '' );

		status = 200;
		fire( form, 'submit', { preventDefault : function() {} } );
		await settle();
		check( 'a pair the kernel takes goes to the page of the route', replaced.join() === '/crm' );
		check( 'the names under Nino.crm the login sets are frozen and set once', Object.isFrozen( win.Nino.crm.login ) === true && ( function() { try { win.Nino.crm.login = {}; return false } catch( error ) { return true } }() ) === true );
	}

	}

	}
	catch( error ) {
		check( 'the run goes through to its end: '+ String( error && error.message ), false );
	}

	done();
} )();
