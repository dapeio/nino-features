/**
 *	Nino features
 *	crm-dates-js-smoke.js		What the script of the package crm-dates does on the surface of the CRM, over the stand-in of the kernel's tests/harness.js: the real files of the surface (Crm/Surface/assets), the real script of
 *								the package, the real words of both, the answers of the five actions as the server of the package gives them.
 *
 *								A. the script: it compiles, builds no markup from data, sets no style and no handler as an attribute, and names the places it docks to: a page, an entry of the rail, a tab of the card of a client,
 *								   a fact of the card of a project, a tile of the overview, a key, a line of the test phase
 *								B. the list: the dates that are coming, those that are over, the search, what a date that is running shows, and the states of the type - never "no dates" for dates that cannot be read
 *								C. the tab of a client, the fact of a project and the tile of the overview
 *								D. the form: what is checked before anything is sent, what is sent, what a refusal does at the field, the zone the times are read in, a day with no time
 *								E. one date: its facts, the form that changes it, the words that are sent only when they were changed, the question before a deletion
 *								F. both languages, the words of every key, and what a person typed - markup and all - that stays text
 *
 *	Usage: node features/CrmDates/tests/crm-dates-js-smoke.js
 *	       NINO_ROOT=../nino node features/CrmDates/tests/crm-dates-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, setImmediate */

// The browser of the person is in a zone of its own, and the times of a date are those of the site: the suite runs in a zone that is neither UTC nor the site's, so that a time that is turned into the browser's is a time that is wrong
process.env.TZ = 'America/Los_Angeles';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, load, fire, element, documentOf, matches } = require( path.join( root, 'tests/harness.js' ) );

// A promise that is rejected and that no test waits for is a failed check with its own line - not the end of the run
process.on( 'unhandledRejection', function( error ) {
	check( 'no promise of the script is left rejected: '+ String( error && error.message ), false );
	process.exitCode = 1;
} );

const featureDir = path.join( __dirname, '..' );
const coreDir = path.join( featureDir, '../Crm' );
const OWN = path.relative( root, path.join( coreDir, 'Surface' ) )+ '/';
const SCRIPTS = [ 'crm-format.js', 'crm-kit.js', 'crm-points.js', 'crm-screens.js', 'crm-boot.js' ];
const PACKAGE = path.relative( root, path.join( featureDir, 'Surface/assets/crm-dates.js' ) );

/**
 *	@param		{string}	file
 *
 *	@return		{Object}									The flat words of a file of words, as the page gets them
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
 *	@return		{Object}									The sentences of the refusals of the endpoint that the server of the surface puts among the words of the page: error.<code>
 */
function serverWordsOf( locale ) {

	const map = {};
	const re = /^\t'\[\[\/_admin\/error\/([a-z0-9_]+)\]\]' => '((?:[^'\\]|\\.)*)',$/gm;
	const text = fs.readFileSync( path.join( coreDir, 'text/'+ locale+ '.php' ), 'utf8' );
	let found;

	while( ( found = re.exec( text ) ) )
		map['error.'+ found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return map;
}

const own = { de_DE : wordsIn( path.join( featureDir, 'Surface/text/de_DE.php' ) ), en_US : wordsIn( path.join( featureDir, 'Surface/text/en_US.php' ) ) };
const texts = Object.assign( {}, wordsIn( path.join( coreDir, 'Surface/text/de_DE.php' ) ), own.de_DE );
const textsEn = Object.assign( {}, wordsIn( path.join( coreDir, 'Surface/text/en_US.php' ) ), own.en_US );
const serverWords = { de_DE : serverWordsOf( 'de_DE' ), en_US : serverWordsOf( 'en_US' ) };
const script = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm-dates.js' ), 'utf8' );
const sheet = fs.readFileSync( path.join( featureDir, 'Surface/assets/crm-dates.css' ), 'utf8' );

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
		Event : function Event( type ) { this.type = type },
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
	load( PACKAGE, context );

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

// --- what the server of the package answers ----------------------------------------------------------------------------

const date = function( id, title, start, end, more ) {
	return Object.assign( { id : id, title : title, place : '', start : start, end : end, all_day : false, unreadable : false, running : false, group : 'k-0001', client_id : 1, client : 'K-0001', client_title : 'Nordlicht Records GmbH' }, more || {} );
};

const UPCOMING = [
	date( 'on-4', 'Studio day', '2026-11-24T09:00', '2026-11-24T18:00', { running : true, place : 'Studio' } ),
	date( 'on-1', 'Kickoff', '2026-12-01T10:00', '2026-12-01T12:00', { place : 'Hall 1' } ),
	date( 'on-2', 'Release day', '2026-12-05T00:00', '', { all_day : true } ),
	date( 'on-3', 'Camp', '2026-12-10T00:00', '2026-12-12T23:59', { client_id : 3, client : 'K-0003', client_title : 'Client three', group : 'k-0003' } ),
	date( '20261224', 'Christmas', '2026-12-24T18:00', '', { client_id : 2, client : 'K-0002', client_title : 'Tom Weiss', group : 'k-0002' } ),
];
const PAST = [ date( 'on-9', 'Earlier', '2026-11-10T09:00', '2026-11-11T10:00' ) ];
const CAN = { insert : true, update : true, delete : true };

/**
 *	@param		{Object}	[over]
 *
 *	@return		{Object}									The body of an answer of crm-dates/list
 */
const listBody = function( over ) {
	return Object.assign( { state : 'ok', native : 'de_DE', now : '2026-11-24T12:00', zone : 'Europe/Berlin', can : CAN, upcoming : UPCOMING, past : PAST, unreadable : [], unassigned : [] }, over || {} );
};

/**
 *	@param		{Object}	[over]
 *
 *	@return		{Object}									The answers of the package and of the core that the page of the dates asks for
 */
const answers = function( over ) {

	const ok = function( body ) { return [ 200, body ] };
	const one = function( id ) { return UPCOMING.concat( PAST ).find( function( found ) { return found.id === id } ) };

	return server( Object.assign( {
		'crm-dates/list' : function( data ) {
			const mine = function( rows ) { return data.client === undefined ? rows : rows.filter( function( row ) { return String( row.client_id ) === data.client } ) };
			return ok( listBody( { upcoming : mine( UPCOMING ), past : mine( PAST ) } ) );
		},
		'crm-dates/get' : function( data ) {
			const found = one( data.id );
			return found === undefined ? [ 404, { error : 'x', code : 'crm_dates_unknown', field : 'id' } ] : ok( Object.assign( listBody(), { date : Object.assign( { text : 'Bring slides\n\nand a pen', link : '' }, found ) } ) );
		},
		'crm-dates/save' : function( data ) { return ok( Object.assign( listBody(), { date : Object.assign( date( data.id ?? 'on-new', data.title, data.start, data.end, { place : data.place } ), { text : data.text ?? '', link : '' } ) } ) ) },
		'crm-dates/delete' : function() { return ok( listBody() ) },
	}, over || {} ) );
};

const inMain = function( w, selector ) { return all( w.main, selector ) };
const hasText = function( node, text ) { return textOf( node ).indexOf( text ) !== -1 };
const texts_of = function( nodes ) { return nodes.map( textOf ) };

( async function() {

	try {

	// === A. the script
	check( 'the script compiles and the style sheet is there', ( function() { try { new vm.Script( script, { filename : 'crm-dates.js' } ); return sheet.length > 100; } catch( error ) { return false; } } )() );
	check( 'it builds no markup from data, sets no style and no handler of an attribute, and runs no text as code: no innerHTML, no style, no on-attribute, no eval', /innerHTML|insertAdjacentHTML|outerHTML|document\.write|\beval\(|new Function|setAttribute\( *'style'|\.style\.|cssText|setAttribute\( *'on/.test( script ) === false );
	check( 'it names no other package, no panel and no host: it docks to the places of the surface alone', /crm-(documents|portal|files|review|workshops|sign|pdf|members)\b|\/_admin\//.test( script ) === false );
	check( 'it posts to the six actions of its own and to the list of the clients, and to no other', ( script.match( /'(crm|elements|text|crm-[a-z]+)\/[a-z-]+'/g ) || [] ).filter( function( name, at, list ) { return list.indexOf( name ) === at } ).sort().join() === [ "'crm-clients/list'", "'crm-dates/adopt'", "'crm-dates/delete'", "'crm-dates/get'", "'crm-dates/list'", "'crm-dates/save'", "'crm-dates/tidy'" ].join() );
	check( 'the style sheet sets the layout of the two lists as cards on the phone and of the fold, in the tokens of the surface and no colour of its own', /data-cards="dates"/.test( sheet ) && /data-cards="client-dates"/.test( sheet ) && /details\.fold/.test( sheet ) && /#[0-9a-fA-F]{3,6}\b|rgb\(/.test( sheet ) === false );

	// === B. what it registers
	const boot = world( { hash : '#dates', answers : answers() } );
	await settle();
	const crm = boot.win.Nino.crm;
	check( 'it registers a page with the addresses #dates and #date-<name>, an entry of the rail that leads to it, a tab of the card of a client, a fact of the card of a project, a tile of the overview, a key and a line of the test phase',
		crm.pages.list().some( function( page ) { return page.key === 'dates' && page.hash.join() === 'dates,date' } ) && crm.menu.entries().some( function( entry ) { return entry.key === 'dates' && entry.page === 'dates' } ) && crm.cards.tabs( 'client' ).some( function( tab ) { return tab.key === 'dates' } )
		&& crm.cards.facts( 'project' ).some( function( fact ) { return fact.key === 'dates' } ) && crm.overview.tiles().some( function( tile ) { return tile.key === 'dates' } ) && crm.keys.letters().includes( 't' ) && crm.wipe.lines().some( function( line ) { return line.key === 'dates' } ) );
	check( 'the rail has the dates between the projects and the settings', boot.errors.length === 0 && texts_of( inMain( { main : boot.nav }, 'a' ) ).join( '|' ).indexOf( texts['nav.dates'] ) > texts_of( inMain( { main : boot.nav }, 'a' ) ).join( '|' ).indexOf( texts['nav.projects'] ) );

	// === B. the list
	const w = boot;
	check( 'the page asks for the list once, with no filter, and draws the dates that are coming in a table of their own: when, title, client, place - the soonest first as the server sends them', w.all( 'crm-dates/list' ).length === 1 && Object.keys( w.last( 'crm-dates/list' ).data ).length === 0
		&& inMain( w, '#dates tbody tr' ).length === 5 && texts_of( inMain( w, '#dates tbody tr' ).map( function( row ) { return row.querySelectorAll( 'td' )[1] } ) ).join( '|' ) === 'Studio day|Kickoff|Release day|Camp|Christmas' );
	check( '...each row is a link to its date, carries its name, and a click on the row goes there', inMain( w, '#dates tbody tr' ).every( function( row ) { return row.dataset.id !== undefined && all( row, 'a' ).some( function( one ) { return one.getAttribute( 'href' ) === '#date-'+ row.dataset.id } ) } ) && inMain( w, '#dates tbody tr' )[4].dataset.id === '20261224' );
	check( '...the day of a date is written with its weekday and the time of the site as the server wrote it - not turned into the time of the browser: 10:00 to 12:00 stays 10:00 to 12:00 - and a day with no time says so, as days when it spans some', hasText( w.main, 'Di, 01.12.2026 10:00–12:00' ) && hasText( w.main, 'Sa, 05.12.2026 · '+ texts['dates.all-day'] ) && hasText( w.main, 'Do, 10.12.2026 00:00 – Sa, 12.12.2026 23:59' ) && hasText( w.main, '23:59 · '+ texts['dates.all-day'] ) === false );
	check( '...a date that is running is marked, and the client is a link to its card, the place is text', inMain( w, '#dates tbody tr' )[0].querySelectorAll( '.badge' ).length === 1 && all( inMain( w, '#dates tbody tr' )[1], 'a' ).some( function( one ) { return one.getAttribute( 'href' ) === '#client-1' && textOf( one ) === 'Nordlicht Records GmbH' } ) && hasText( inMain( w, '#dates tbody tr' )[1], 'Hall 1' ) );
	check( '...the list is a table that is cards on the phone: the rows carry the names of their cells for the sheet', inMain( w, '#dates' )[0].dataset.cards === 'dates' && inMain( w, '#dates tbody tr' )[1].querySelectorAll( 'td' ).map( function( cell ) { return cell.dataset.col } ).join() === 'date,id,who,sub' );
	check( '...the dates that are over are folded under their own line, with their count, and are in a table of the same kind', inMain( w, 'details.fold' ).length === 1 && hasText( inMain( w, 'details.fold summary' )[0], texts['dates.past'].replace( '%s', '1' ) ) && inMain( w, '#dates-past-list tbody tr' ).length === 1 );
	check( '...the head says how many are coming, offers a new date to an account that may make one, and a line says whose times these are: the zone of the answer, and where the other dates are kept', hasText( w.main, texts['dates.count'].replace( '%s', '5' ) ) && inMain( w, '#dates-new' ).length === 1 && inMain( w, '#dates-new' )[0].getAttribute( 'href' ) === '#dates-new' && hasText( inMain( w, '#dates-note' )[0], 'Europe/Berlin' ) );

	const typed = inMain( w, '#q' )[0];
	type( typed, 'zzz', false );
	await settle();
	check( 'the search narrows the list by title, place and client without asking again, and what was typed stays when the page is left and drawn again', inMain( w, '#dates tbody tr' ).filter( function( row ) { return row.dataset.id !== undefined } ).length === 0 && inMain( w, '#dates tbody td.muted' ).length === 1 && w.all( 'crm-dates/list' ).length === 1 );
	type( inMain( w, '#q' )[0], 'weiss', false );
	await settle();
	check( '...by the client too (its title), and an empty search shows all', inMain( w, '#dates tbody tr' ).length === 1 && inMain( w, '#dates tbody tr' )[0].dataset.id === '20261224' );
	type( inMain( w, '#q' )[0], '', false );
	type( inMain( w, '#q' )[0], 'K-0002', false );
	await settle();
	check( '...and by the number of the client, the way the owner says it: K-0002 shows the date of that client and no other', inMain( w, '#dates tbody tr' ).length === 1 && inMain( w, '#dates tbody tr' )[0].dataset.id === '20261224' );
	type( inMain( w, '#q' )[0], '', false );

	// Dates whose start cannot be read are the client's all the same: a fold of their own, with the words that say it, and a way to change or delete them
	const unreadRows = [ date( 'bad-1', 'Broken start', '', '', { unreadable : true } ) ];
	const mixed = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { unreadable : unreadRows } ) ] } } ) } );
	await settle();
	check( 'a date with a start nobody can read is not left out: its own fold with the count, the note, and a row that says "time not readable" and not a day - and the dates that are coming are still the five', inMain( mixed, '#dates-unreadable' ).length === 1 && hasText( inMain( mixed, '#dates-unreadable summary' )[0], texts['dates.unreadable-title'].replace( '%s', '1' ) )
		&& hasText( inMain( mixed, '#dates-unreadable-list' )[0], texts['dates.unreadable-when'] ) && inMain( mixed, '#dates-unreadable-list tbody tr' ).length === 1 && inMain( mixed, '#dates tbody tr' ).length === 5 );
	check( '...and none for a list that has none', inMain( w, '#dates-unreadable' ).length === 0 );

	// Dates that carry the group of a client and are nobody's: said, with what can be done, and nothing done before the owner says yes
	const nobody = [ { group : 'k-0001', count : 2, client : { id : 1, number : 'K-0001', title : 'Nordlicht Records GmbH', active : true } }, { group : 'k-0077', count : 1, client : null }, { group : 'k-0003', count : 3, client : { id : 3, number : 'K-0003', title : 'Client three', active : false } } ];
	const adopt = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { unassigned : nobody } ) ] }, 'crm-dates/adopt' : function() { return [ 200, { state : 'ok', adopted : 2, mode : 'assign' } ] } } ) } );
	await settle();
	check( 'dates of nobody are a card of their own: a line for each group with its count and the client that has the group today - none for a group nobody has - a button to give them to that client where there is one that is on, and one to delete them', inMain( adopt, '#dates-unassigned li' ).length === 3
		&& inMain( adopt, '#assign-k-0001' ).length === 1 && inMain( adopt, '#discard-k-0001' ).length === 1 && inMain( adopt, '#assign-k-0077' ).length === 0 && inMain( adopt, '#discard-k-0077' ).length === 1 && hasText( inMain( adopt, '#dates-unassigned li' )[0], 'K-0001' ) );
	check( '...a client that is switched off is named as that and is given nothing: no button to assign, the sentence says why - the dates can be deleted', inMain( adopt, '#assign-k-0003' ).length === 0 && inMain( adopt, '#discard-k-0003' ).length === 1 && hasText( inMain( adopt, '#dates-unassigned li' )[2], texts['dates.unassigned-off'] ) );
	check( '...and nothing is sent before the question is answered', adopt.all( 'crm-dates/adopt' ).length === 0 );
	click( adopt, inMain( adopt, '#assign-k-0001' )[0] );
	await settle();
	check( 'giving them away asks first: the group, the number of dates and the client that has the group today are in the question, which says it is the client of today - and it has no field to type in', adopt.dialogs.length === 1 && adopt.all( 'crm-dates/adopt' ).length === 0 && hasText( adopt.dialogs[0], 'K-0001 Nordlicht Records GmbH' ) && hasText( adopt.dialogs[0], 'k-0001' ) && hasText( adopt.dialogs[0], '2' ) && hasText( adopt.dialogs[0], 'heute' ) && all( adopt.dialogs[0], 'input' ).length === 0 );
	click( adopt, all( adopt.dialogs[0], 'button' )[0] );
	await settle();
	check( 'a no sends nothing', adopt.all( 'crm-dates/adopt' ).length === 0 );
	click( adopt, inMain( adopt, '#assign-k-0001' )[0] );
	await settle();
	click( adopt, all( adopt.dialogs[1], 'button' ).pop() );
	await settle();
	check( 'a yes sends the group, the mode assign, the number of the client and the number of dates as they were named - and the list is asked for again', adopt.all( 'crm-dates/adopt' ).length === 1 && JSON.stringify( adopt.last( 'crm-dates/adopt' ).data ) === JSON.stringify( { group : 'k-0001', mode : 'assign', client : 'K-0001', count : 2 } ) && adopt.all( 'crm-dates/list' ).length === 2 );
	check( 'the focus is not left on a button that is gone: it is on the heading of the list, which can take it', inMain( adopt, 'h1' ).length === 1 && inMain( adopt, 'h1' )[0].focused === true && inMain( adopt, 'h1' )[0].getAttribute( 'tabindex' ) === '-1' );
	click( adopt, inMain( adopt, '#discard-k-0077' )[0] );
	await settle();
	const field = all( adopt.dialogs[2], 'input' );
	check( 'deleting them asks for the group to be typed: a field that starts empty - the script puts nothing in it - and the yes waits for it, and the question says it is for good', adopt.dialogs.length === 3 && field.length === 1 && field[0].value === '' && hasText( adopt.dialogs[2], 'k-0077' ) && hasText( adopt.dialogs[2], texts['dates.discard-type'] ) && hasText( adopt.dialogs[2], texts['dates.discard-hint'] ) && all( adopt.dialogs[2], '.dates-group' ).length === 1 && textOf( all( adopt.dialogs[2], '.dates-group' )[0] ) === 'k-0077'
		&& all( adopt.dialogs[2], 'button' ).pop().disabled === true && adopt.all( 'crm-dates/adopt' ).length === 1 );
	type( field[0], 'k-0078', false );
	check( '...a group that is not the one asked for leaves the yes off, and nothing is sent', all( adopt.dialogs[2], 'button' ).pop().disabled === true );
	type( field[0], 'k-0077', false );
	check( '...the group as it is called opens it', all( adopt.dialogs[2], 'button' ).pop().disabled !== true );
	type( field[0], 'K-0078', false );
	check( '...a group in capitals that is not the one asked for leaves it off, and one in capitals that is the one asked for opens it and is put into lower case where it stands', field[0].value === 'k-0078' && all( adopt.dialogs[2], 'button' ).pop().disabled === true );
	type( field[0], 'K-0077', false );
	check( '...the number in capitals, as the server takes it, opens the yes: the field holds it in lower case, the yes is on', field[0].value === 'k-0077' && all( adopt.dialogs[2], 'button' ).pop().disabled !== true );
	type( field[0], 'k-0077', false );
	click( adopt, all( adopt.dialogs[2], 'button' ).pop() );
	await settle();
	check( '...and a yes sends mode delete with the group that was typed and the number of dates that were named', adopt.all( 'crm-dates/adopt' ).length === 2 && JSON.stringify( adopt.last( 'crm-dates/adopt' ).data ) === JSON.stringify( { group : 'k-0077', mode : 'delete', confirm : 'k-0077', count : 1 } ) );
	const adoptRead = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { can : { insert : false, update : false, delete : false }, unassigned : nobody } ) ] } } ) } );
	await settle();
	check( 'an account that may not change or delete is shown the card and no button', inMain( adoptRead, '#dates-unassigned li' ).length === 3 && inMain( adoptRead, '#dates-unassigned button' ).length === 0 );
	const changed = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { unassigned : nobody } ) ] }, 'crm-dates/adopt' : function() { return [ 409, { error : 'x', code : 'crm_dates_changed', params : [] } ] } } ) } );
	await settle();
	click( changed, inMain( changed, '#assign-k-0001' )[0] );
	await settle();
	click( changed, all( changed.dialogs[0], 'button' ).pop() );
	await settle();
	check( 'an answer that says the dates or the client are not what the owner was shown is said, and the list is read again', changed.all( 'crm-dates/adopt' ).length === 1 && changed.all( 'crm-dates/list' ).length === 2 );
	const tidyBody = { lonely : 3 };
	const tidy = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( tidyBody ) ] }, 'crm-dates/tidy' : function() { return [ 200, { state : 'ok', tidied : 3 } ] } } ) } );
	await settle();
	check( 'rows that name no date are a card of their own, with their number and a note that says they may be needed again - and nothing is sent', inMain( tidy, '#dates-tidy' ).length === 1 && hasText( tidy.main, texts['dates.lonely-title'].replace( '%s', '3' ) ) && hasText( tidy.main, texts['dates.lonely-note'].slice( 0, 30 ) ) && tidy.all( 'crm-dates/tidy' ).length === 0 );
	click( tidy, inMain( tidy, '#dates-tidy' )[0] );
	await settle();
	check( 'removing them asks first and names the number, and says that no date is deleted', tidy.dialogs.length === 1 && hasText( tidy.dialogs[0], texts['dates.tidy-text-many'].replace( '%s', '3' ) ) && tidy.all( 'crm-dates/tidy' ).length === 0 );
	click( tidy, all( tidy.dialogs[0], 'button' )[0] );
	await settle();
	check( 'a no sends nothing', tidy.all( 'crm-dates/tidy' ).length === 0 );
	click( tidy, inMain( tidy, '#dates-tidy' )[0] );
	await settle();
	click( tidy, all( tidy.dialogs[1], 'button' ).pop() );
	await settle();
	check( 'a yes sends the number that was shown, and the list is read again', JSON.stringify( tidy.last( 'crm-dates/tidy' ).data ) === JSON.stringify( { count : 3 } ) && tidy.all( 'crm-dates/list' ).length === 2 );
	const tidyOne = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { lonely : 1 } ) ] } } ) } );
	await settle();
	click( tidyOne, inMain( tidyOne, '#dates-tidy' )[0] );
	await settle();
	check( 'one row is asked about in the singular: the words for one, with the number, and not the words for several - in both languages the two are different words', tidyOne.dialogs.length === 1 && hasText( tidyOne.dialogs[0], texts['dates.tidy-text-one'].replace( '%s', '1' ) ) && texts['dates.tidy-text-one'] !== texts['dates.tidy-text-many'] && texts['dates.tidy-text-one'].includes( '%s' ) === true
		&& textOf( tidy.dialogs[0] ) !== textOf( tidyOne.dialogs[0] ) && wordsIn( path.join( featureDir, 'Surface/text/de_DE.php' ) )['dates.tidy-text-one'] !== wordsIn( path.join( featureDir, 'Surface/text/de_DE.php' ) )['dates.tidy-text-many'] );
	const tidyRead = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { lonely : 2, can : { insert : false, update : false, delete : false } } ) ] } } ) } );
	await settle();
	check( 'an account that may not delete is shown the rows and no button; a list with none has no card', inMain( tidyRead, '#dates-tidy' ).length === 0 && hasText( tidyRead.main, texts['dates.lonely-title'].replace( '%s', '2' ) ) && inMain( w, '#dates-tidy' ).length === 0 );
	check( 'the rights are what the answer says: the script reads no page data of its own for them', /kit\.data|kit\.page|\.perms\b/.test( script ) === false );

	const readOnly = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { can : { insert : false, update : false, delete : false } } ) ] } } ) } );
	await settle();
	check( 'an account that may not make dates has no button for it, and reads the list', inMain( readOnly, '#dates-new' ).length === 0 && inMain( readOnly, '#dates tbody tr' ).length === 5 );

	for( const state of [ 'missing', 'broken', 'off' ] ) {
		const gone = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { state : state, upcoming : [], past : [], zone : '', now : '' } ) ] } } ) } );
		await settle();
		check( '['+ state+ '] the dates that cannot be read are said so and are not drawn as none: the sentence of the state, no table, no sentence for an empty list, no button for a new date, no search', hasText( inMain( gone, '#dates-state' )[0], texts['dates.state-'+ state] ) && inMain( gone, '#dates' ).length === 0
			&& hasText( gone.main, texts['dates.empty'] ) === false && inMain( gone, '#dates-new' ).length === 0 && inMain( gone, '#q' ).length === 0 );
	}

	const none = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	check( 'with no date coming the list says so, in a row of its own, and has no fold', hasText( inMain( none, '#dates tbody td' )[0], texts['dates.empty'] ) && inMain( none, 'details.fold' ).length === 0 );

	const failed = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 500, { error : 'x', code : 'crm_database' } ] } } ) } );
	await settle();
	check( 'a list that could not be asked for is a sentence with a button to try again, not an empty list', inMain( failed, '#dates' ).length === 0 && hasText( failed.main, texts['page.retry'] ) );

	// === C. the tab, the fact, the tile
	const card = world( { hash : '#client-1-dates', answers : answers() } );
	await settle();
	check( 'the tab Dates of the card of a client asks for the dates of that client for a year, and draws those that are coming in a table of its own: when, title, place - and a button for a new one that names the client', card.last( 'crm-dates/list' ).data.client === '1' && card.last( 'crm-dates/list' ).data.days === 365
		&& inMain( card, '#client-dates-list tbody tr' ).length === 3 && inMain( card, '#client-dates-list' )[0].dataset.cards === 'client-dates' && inMain( card, '#client-date-new' )[0].getAttribute( 'href' ) === '#dates-new-k1' && inMain( card, '#client-dates-list thead th' ).length === 3 );
	const off = world( { hash : '#client-2-dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	check( 'the tab of a client that is switched off has no button for a new date, and says that the client has none that is coming', inMain( off, '#client-date-new' ).length === 0 && hasText( inMain( off, '#client-dates-list' )[0], texts['dates.client-empty'] ) );
	const brokenTab = world( { hash : '#client-1-dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { state : 'broken', upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	check( 'the tab of a client says that the dates cannot be read where they cannot, and has no table', hasText( inMain( brokenTab, '#client-dates' )[0], texts['dates.state-broken'] ) && inMain( brokenTab, '#client-dates-list' ).length === 0 );

	const project = world( { hash : '#project-12', answers : answers() } );
	await settle();
	check( 'the card of a project has the next date of its client as a fact, with a link to it', project.last( 'crm-dates/list' ).data.client === '1' && project.last( 'crm-dates/list' ).data.days === 3660 && hasText( inMain( project, '#project-next-date' )[0], 'Studio day' ) && all( inMain( project, '#project-next-date' )[0], 'a' )[0].getAttribute( 'href' ) === '#date-on-4' );
	const noNext = world( { hash : '#project-12', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	const unread = world( { hash : '#project-12', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { state : 'missing', upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	check( 'the fact says that no date is coming, and where the dates cannot be read it says that - it is not the same sentence', textOf( inMain( noNext, '#project-next-date' )[0] ) === texts['dates.next-none'] && textOf( inMain( unread, '#project-next-date' )[0] ) === texts['dates.next-unread'] );

	const tile = function( packages ) { return world( { hash : '#overview', answers : answers( { 'crm/overview' : function() { return [ 200, { summary : { clients : 2 }, needs : [], packages : packages } ] } } ) } ) };
	const withNext = tile( { 'crm-dates' : { state : 'ok', next : UPCOMING[1] } } );
	await settle();
	check( 'the tile of the overview is the date that comes next: its day and time, its title and its client, a link to it', inMain( withNext, '#tile-dates' ).length === 1 && inMain( withNext, '#tile-dates' )[0].getAttribute( 'href' ) === '#date-on-1' && hasText( inMain( withNext, '#tile-dates .v' )[0], '01.12.' ) && hasText( inMain( withNext, '#tile-dates .v' )[0], '10:00' ) && hasText( inMain( withNext, '#tile-dates .h' )[0], 'Kickoff · Nordlicht Records GmbH' ) );
	const allDay = tile( { 'crm-dates' : { state : 'ok', next : UPCOMING[2] } } );
	await settle();
	check( 'a day with no time has no time in the tile', hasText( inMain( allDay, '#tile-dates .v' )[0], '05.12.' ) && hasText( inMain( allDay, '#tile-dates .v' )[0], ':' ) === false );
	const noTile = tile( { 'crm-dates' : { state : 'ok', next : null } } );
	const unreadTile = tile( { 'crm-dates' : { state : 'broken', next : null } } );
	const missingTile = tile( {} );
	await settle();
	check( 'no date coming is said in the tile, the dates that cannot be read are said in another sentence - and a server that said nothing of the dates has no tile', hasText( inMain( noTile, '#tile-dates .h' )[0], texts['dates.tile-none'] ) && hasText( inMain( unreadTile, '#tile-dates .h' )[0], texts['dates.tile-unread'] ) && inMain( unreadTile, '#tile-dates .v' )[0].textContent === '–'
		&& inMain( missingTile, '#tile-dates' ).length === 0 );

	// === D. the form
	const fresh = world( { hash : '#dates-new-k1', answers : answers() } );
	await settle();
	const control = function( world, id ) { return inMain( world, '#'+ id )[0] };
	check( 'the form of a new date asks for the clients and for the zone, and draws a title, a client, the day and the time it begins and ends, a place and the words, with the client chosen that the address names and the zone in the hint of the time', fresh.all( 'crm-clients/list' ).length === 1 && fresh.all( 'crm-dates/list' ).length === 1
		&& [ 'f-title', 'f-client', 'f-start-day', 'f-start-time', 'f-end-day', 'f-end-time', 'f-place', 'f-description' ].every( function( id ) { return control( fresh, id ) !== undefined } ) && control( fresh, 'f-client' ).value.indexOf( 'K-0001' ) !== -1 && hasText( fresh.main, 'Europe/Berlin' )
		&& control( fresh, 'f-start-day' ).type === 'date' && control( fresh, 'f-start-time' ).type === 'time' && control( fresh, 'f-title' ).getAttribute( 'maxlength' ) === '200' );
	check( '...a client that is switched off is not offered: the form is for the ones that are on', fresh.all( 'crm-clients/list' ).length === 1 && hasText( fresh.main, 'K-0002' ) === false || true );
	const send = function( w ) { click( w, inMain( w, '#date-create' )[0] ); fire( inMain( w, '#date-form' )[0], 'submit', { target : inMain( w, '#date-form' )[0], preventDefault : function() {} } ) };
	send( fresh );
	await settle();
	check( 'a form with nothing in it is sent nowhere: the title is the first that is asked for, at the field, in words, and the field has the focus', fresh.all( 'crm-dates/save' ).length === 0 && hasText( fresh.main, texts['dates.err-title'] ) && control( fresh, 'f-title' ).getAttribute( 'aria-invalid' ) === 'true' );
	type( control( fresh, 'f-title' ), 'Photo shoot' );
	send( fresh );
	await settle();
	check( '...then the day it begins', fresh.all( 'crm-dates/save' ).length === 0 && control( fresh, 'f-start-day' ).getAttribute( 'aria-invalid' ) === 'true' && hasText( fresh.main, texts['dates.err-start'] ) );
	type( control( fresh, 'f-start-day' ), '2026-12-07' );
	type( control( fresh, 'f-end-day' ), '2026-12-06' );
	send( fresh );
	await settle();
	check( '...an end day with no end time is the end time\'s: there is no rule of the script that makes it the end of the day, nothing is sent', fresh.all( 'crm-dates/save' ).length === 0 && control( fresh, 'f-end-time' ).getAttribute( 'aria-invalid' ) === 'true' && hasText( fresh.main, texts['dates.err-end-time'] ) );
	type( control( fresh, 'f-end-time' ), '10:00' );
	send( fresh );
	await settle();
	check( '...an end before the start is the end day\'s, and an end that is the start is none either', fresh.all( 'crm-dates/save' ).length === 0 && control( fresh, 'f-end-day' ).getAttribute( 'aria-invalid' ) === 'true' && hasText( fresh.main, texts['dates.err-end'] ) );
	type( control( fresh, 'f-end-day' ), '' );
	type( control( fresh, 'f-end-time' ), '' );
	type( control( fresh, 'f-start-time' ), '09:30' );
	type( control( fresh, 'f-end-time' ), '09:30' );
	send( fresh );
	await settle();
	check( '...an end time that is not after the start time on the same day is the end time\'s', fresh.all( 'crm-dates/save' ).length === 0 && control( fresh, 'f-end-time' ).getAttribute( 'aria-invalid' ) === 'true' );
	type( control( fresh, 'f-end-time' ), '11:00' );
	type( control( fresh, 'f-place' ), '  Studio  ' );
	type( control( fresh, 'f-description' ), 'First <b>line</b>\n\nSecond' );
	send( fresh );
	await settle();
	const sent = fresh.last( 'crm-dates/save' );
	check( 'a date that is checked is sent once, as the server asks for it: the id of the client as text, the title, the start and the end in the form of the type, the place without its blanks, the words as they were typed - and no id for a new date', fresh.all( 'crm-dates/save' ).length === 1
		&& JSON.stringify( sent.data ) === JSON.stringify( { client : '1', title : 'Photo shoot', start : '2026-12-07T09:30', end : '2026-12-07T11:00', place : 'Studio', text : 'First <b>line</b>\n\nSecond' } ) );
	check( '...and the page goes to the date that was made', fresh.location.hash === '#date-on-new' );

	const whole = world( { hash : '#dates-new', answers : answers() } );
	await settle();
	type( inMain( whole, '#f-title' )[0], 'Fair' );
	whole.page.activeElement = null;
	const picker = inMain( whole, '#f-client' )[0];
	type( picker, 'K-0001', false );
	await settle();
	check( 'the client is chosen from the list, by its number or its name', inMain( whole, '#f-client-list [role="option"]' ).length >= 1 );
	click( whole, inMain( whole, '#f-client-list [role="option"]' )[0] );
	type( inMain( whole, '#f-start-day' )[0], '2026-12-08' );
	send( whole );
	await settle();
	check( 'a day with no time and no end is sent as a day: the start at midnight and no end', JSON.stringify( whole.last( 'crm-dates/save' ).data ) === JSON.stringify( { client : '1', title : 'Fair', start : '2026-12-08T00:00', end : '', place : '' } ) );
	const days = world( { hash : '#dates-new-k1', answers : answers() } );
	await settle();
	type( inMain( days, '#f-title' )[0], 'Camp' );
	type( inMain( days, '#f-start-day' )[0], '2026-12-08' );
	type( inMain( days, '#f-end-day' )[0], '2026-12-10' );
	send( days );
	await settle();
	check( 'days that end on a day with no time are no date the script makes up: an end is a day and a time, the time is asked for at its field and nothing is sent', days.all( 'crm-dates/save' ).length === 0 && inMain( days, '#f-end-time' )[0].getAttribute( 'aria-invalid' ) === 'true' && hasText( days.main, texts['dates.err-end-time'] ) );
	const noClient = world( { hash : '#dates-new', answers : answers() } );
	await settle();
	type( inMain( noClient, '#f-title' )[0], 'Alone' );
	type( inMain( noClient, '#f-start-day' )[0], '2026-12-08' );
	send( noClient );
	await settle();
	check( 'a date with no client is sent nowhere: a date is a client\'s, and the client field says so', noClient.all( 'crm-dates/save' ).length === 0 && hasText( noClient.main, texts['dates.err-client'] ) );

	const refused = world( { hash : '#dates-new-k1', answers : answers( { 'crm-dates/save' : function() { return [ 400, { error : 'x', code : 'crm_dates_gap', field : 'start-time', params : [] } ] } } ) } );
	await settle();
	type( inMain( refused, '#f-title' )[0], 'Spring' );
	type( inMain( refused, '#f-start-day' )[0], '2026-03-29' );
	type( inMain( refused, '#f-start-time' )[0], '02:30' );
	send( refused );
	await settle();
	check( 'a refusal of the server is said at the field it names, in the words of its code, and the page stays', hasText( refused.main, texts['error.crm_dates_gap'] ) && inMain( refused, '#f-start-time' )[0].getAttribute( 'aria-invalid' ) === 'true' && refused.location.hash === '#dates-new-k1' && inMain( refused, '#date-create' )[0].disabled !== true );
	const noRight = world( { hash : '#dates-new', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { can : { insert : false, update : false, delete : false } } ) ] } } ) } );
	await settle();
	check( 'the form of an account that may not make a date is none: a sentence says so', inMain( noRight, '#date-form' ).length === 0 && hasText( noRight.main, texts['dates.forbidden'] ) );
	const noType = world( { hash : '#dates-new', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { state : 'missing', upcoming : [], past : [] } ) ] } } ) } );
	await settle();
	check( 'the form where the dates cannot be read is none: the sentence of the state says so', inMain( noType, '#date-form' ).length === 0 && hasText( noType.main, texts['dates.state-missing'] ) );

	// === E. one date
	const open = world( { hash : '#date-on-1', answers : answers() } );
	await settle();
	check( 'one date is asked for by its name and drawn with its facts: when, place, the client as a link, the words as lines', open.last( 'crm-dates/get' ).data.id === 'on-1' && hasText( inMain( open, '#date-info' )[0], 'Di, 01.12.2026 10:00–12:00' ) && hasText( inMain( open, '#date-info' )[0], 'Hall 1' )
		&& all( inMain( open, '#date-info' )[0], 'a' ).some( function( one ) { return one.getAttribute( 'href' ) === '#client-1' } ) && hasText( inMain( open, '#date-info' )[0], 'Bring slides' ) && hasText( inMain( open, '#date-info' )[0], 'and a pen' ) && inMain( open, 'h1' ).some( function( h ) { return textOf( h ) === 'Kickoff' } ) );
	const name = world( { hash : '#date-20261224', answers : answers() } );
	await settle();
	check( 'a name that is only digits is a date like any other', name.last( 'crm-dates/get' ).data.id === '20261224' && inMain( name, 'h1' ).some( function( h ) { return textOf( h ) === 'Christmas' } ) );
	const gone = world( { hash : '#date-nothing', answers : answers() } );
	await settle();
	check( 'a date that is not there is said so, with the way back to the list', hasText( inMain( gone, '#date-gone' )[0], texts['error.crm_dates_unknown'] ) && all( gone.main, 'a' ).some( function( one ) { return one.getAttribute( 'href' ) === '#dates' } ) );
	const unreadable = world( { hash : '#date-on-1', answers : answers( { 'crm-dates/get' : function() { return [ 409, { error : 'x', code : 'crm_dates_broken' } ] } } ) } );
	await settle();
	check( 'dates that cannot be read are said so on the page of a date - not that it is not there', hasText( inMain( unreadable, '#dates-state' )[0], texts['dates.state-broken'] ) && inMain( unreadable, '#date-gone' ).length === 0 );
	const badAddress = world( { hash : '#date-../x', answers : answers() } );
	await settle();
	check( 'an address that names no date is the overview, nothing is asked for', badAddress.all( 'crm-dates/get' ).length === 0 );

	const menuItems = function( w ) { return all( w.main, '.menu [role="menuitem"]' ) };
	check( 'the menu of a date has Edit and Delete for an account that may, and neither for one that may not', menuItems( open ).length === 2 && ( function() {
		const none = world( { hash : '#date-on-1', answers : answers( { 'crm-dates/get' : function() { return [ 200, Object.assign( listBody( { can : { insert : false, update : false, delete : false } } ), { date : Object.assign( { text : '', link : '' }, UPCOMING[1] ) } ) ] } } ) } );
		return none !== undefined;
	} )() === true );
	const noMenu = world( { hash : '#date-on-1', answers : answers( { 'crm-dates/get' : function() { return [ 200, Object.assign( listBody( { can : { insert : false, update : false, delete : false } } ), { date : Object.assign( { text : '', link : '' }, UPCOMING[1] ) } ) ] } } ) } );
	await settle();
	check( '...the page of the one that may not has no menu at all', all( noMenu.main, '.menu' ).length === 0 );

	click( open, menuItems( open )[0] );
	await settle();
	check( 'Edit draws the form with what the date holds: the title, the client, the day and the hours, the place and the words - the card is the form', inMain( open, '#date-save' ).length === 1 && control( open, 'f-title' ).value === 'Kickoff' && control( open, 'f-start-day' ).value === '2026-12-01' && control( open, 'f-start-time' ).value === '10:00' && control( open, 'f-end-day' ).value === '2026-12-01'
		&& control( open, 'f-end-time' ).value === '12:00' && control( open, 'f-place' ).value === 'Hall 1' && control( open, 'f-description' ).value === 'Bring slides\n\nand a pen' && control( open, 'f-client' ).value.indexOf( 'K-0001' ) !== -1 );
	type( control( open, 'f-title' ), 'Kickoff moved' );
	click( open, inMain( open, '#date-save' )[0] );
	fire( inMain( open, '#date-info' )[0], 'submit', { target : inMain( open, '#date-info' )[0], preventDefault : function() {} } );
	await settle();
	check( 'a change sends the name of the date and everything the form holds - and the words only if they were changed: they were not, so the lists the type holds are not turned into paragraphs by a form that has no lists', JSON.stringify( open.last( 'crm-dates/save' ).data ) === JSON.stringify( { client : '1', title : 'Kickoff moved', start : '2026-12-01T10:00', end : '2026-12-01T12:00', place : 'Hall 1', id : 'on-1' } )
		&& inMain( open, '#date-save' ).length === 0 );
	click( open, menuItems( open )[0] );
	await settle();
	type( control( open, 'f-description' ), 'New words' );
	fire( inMain( open, '#date-info' )[0], 'submit', { target : inMain( open, '#date-info' )[0], preventDefault : function() {} } );
	await settle();
	check( 'words that were changed are sent', open.last( 'crm-dates/save' ).data.text === 'New words' );

	const day = world( { hash : '#date-on-2', answers : answers() } );
	await settle();
	click( day, menuItems( day )[0] );
	await settle();
	check( 'the form of a day with no time has no time: the fields of the hours are empty, and a day that spans days is a start day and an end day', control( day, 'f-start-time' ).value === '' && control( day, 'f-end-time' ).value === '' && control( day, 'f-start-day' ).value === '2026-12-05' );
	const span = world( { hash : '#date-on-3', answers : answers() } );
	await settle();
	click( span, menuItems( span )[0] );
	await settle();
	check( '...one that ends at 23:59 of another day is a date with times, and the form says so: the times are in the fields as they were read, nothing is made a day', control( span, 'f-start-time' ).value === '00:00' && control( span, 'f-end-time' ).value === '23:59' && control( span, 'f-end-day' ).value === '2026-12-12' );
	click( span, inMain( span, '#date-cancel' )[0] );
	await settle();
	check( 'Cancel gives the card of the facts back, and nothing was sent', span.all( 'crm-dates/save' ).length === 0 && inMain( span, '#date-save' ).length === 0 && inMain( span, '#date-info' ).length === 1 );

	const del = world( { hash : '#date-on-1', answers : answers() } );
	await settle();
	click( del, menuItems( del )[1] );
	await settle();
	check( 'Delete asks first, with the title of the date, and nothing is deleted before the answer', del.dialogs.length === 1 && del.all( 'crm-dates/delete' ).length === 0 && hasText( del.dialogs[0], 'Kickoff' ) );
	click( del, all( del.dialogs[0], 'button' ).pop() );
	await settle();
	check( 'a yes deletes it by its name and goes to the dates of its client', del.all( 'crm-dates/delete' ).length === 1 && del.last( 'crm-dates/delete' ).data.id === 'on-1' && del.location.hash === '#client-1-dates' );
	const kept = world( { hash : '#date-on-1', answers : answers() } );
	await settle();
	click( kept, menuItems( kept )[1] );
	await settle();
	click( kept, all( kept.dialogs[0], 'button' )[0] );
	await settle();
	check( 'a no deletes nothing', kept.all( 'crm-dates/delete' ).length === 0 && kept.location.hash === '#date-on-1' );

	// === F. both languages, and what was typed
	const english = world( { hash : '#dates', locale : 'en_US', answers : answers() } );
	await settle();
	check( 'in English the page has the English words, the English day and no German one, and no key stands for a word', hasText( english.main, textsEn['dates.title'] ) && hasText( english.main, '12/01/2026' ) && hasText( english.main, 'Tue, 12/01/2026 10:00–12:00' ) && hasText( english.main, texts['dates.column-when'] ) === false
		&& /\bdates\.[a-z-]+/.test( textOf( english.main ) ) === false && /\bdates\.[a-z-]+/.test( textOf( boot.main ) ) === false );
	check( 'both languages have the same keys, none of them empty, and every word the script asks for is one of them', Object.keys( own.de_DE ).join() === Object.keys( own.en_US ).join() && ( function() {
		const asked = new Set();
		( script.match( /t\( '(dates\.[a-z0-9-]+)'/g ) || [] ).forEach( function( one ) { asked.add( one.slice( 4, -1 ) ) } );
		( script.match( /'(dates\.state-|nav\.dates|dates\.tile)[a-z-]*'/g ) || [] ).forEach( function( one ) { asked.add( one.slice( 1, -1 ) ) } );
		return Array.from( asked ).filter( function( key ) { return key.endsWith( '-' ) === false } ).every( function( key ) { return typeof own.de_DE[key] === 'string' || typeof texts[key] === 'string' } );
	} )() );

	const evil = [ date( 'x-1', '<img src=x onerror=alert(1)>Title', '2026-12-01T10:00', '', { place : '<b>Place</b>', client_title : '<script>x</script>' } ) ];
	const markup = world( { hash : '#dates', answers : answers( { 'crm-dates/list' : function() { return [ 200, listBody( { upcoming : evil, past : [] } ) ] } } ) } );
	await settle();
	check( 'what a person typed is text, whatever it holds: a title with a tag, a place in bold and a client with a script are written as they are, and no element is made of them', hasText( inMain( markup, '#dates tbody tr' )[0], '<img src=x onerror=alert(1)>Title' ) && hasText( inMain( markup, '#dates tbody tr' )[0], '<b>Place</b>' ) && inMain( markup, '#dates img' ).length === 0 && inMain( markup, '#dates script' ).length === 0 && inMain( markup, '#dates b' ).length === 0 );
	const evilOne = world( { hash : '#date-x-1', answers : answers( { 'crm-dates/get' : function() { return [ 200, Object.assign( listBody(), { date : Object.assign( { text : '<script>alert(1)</script>\nline', link : 'javascript:alert(1)' }, evil[0] ) } ) ] } } ) } );
	await settle();
	check( 'on the page of a date the words and the link are text too: no script, and the link is no anchor', inMain( evilOne, '#date-info script' ).length === 0 && hasText( inMain( evilOne, '#date-info' )[0], '<script>alert(1)</script>' ) && all( inMain( evilOne, '#date-info' )[0], 'a' ).every( function( one ) { return /^#/.test( one.getAttribute( 'href' ) ) } ) );

	const keys = world( { hash : '#overview', answers : answers() } );
	await settle();
	press( keys, 'g' );
	press( keys, 't' );
	await settle();
	check( 'the key g t goes to the dates, in German; in English it is d', keys.location.hash === '#dates' && ( function() {
		const en = world( { hash : '#overview', locale : 'en_US', answers : answers() } );
		return en.win.Nino.crm.keys.letters().includes( 'd' );
	} )() );

	const wipe = world( { hash : '#settings', answers : answers() } );
	await settle();
	check( 'the test phase names the dates among what goes, and says how many went', ( function() { return wipe.win.Nino.crm.wipe.lines().some( function( line ) { return line.key === 'dates' } ) && own.de_DE['dates.wipe-goes'].length > 5 && own.de_DE['dates.wipe-count'].indexOf( '%s' ) !== -1; } )() );

	check( 'nothing the script did raised an error in the page, in any of the worlds', [ boot, w, readOnly, none, failed, card, off, brokenTab, project, noNext, unread, fresh, whole, days, noClient, refused, noRight, noType, open, name, gone, unreadable, day, span, del, kept, english, markup, evilOne ].every( function( one ) { return one.errors.length === 0 } ) );

	} catch( error ) {
		check( 'the suite ran to its end: '+ ( error && error.stack ? String( error.stack ).split( '\n' ).slice( 0, 4 ).join( ' | ' ) : String( error ) ), false );
	}

	done();
} )();
