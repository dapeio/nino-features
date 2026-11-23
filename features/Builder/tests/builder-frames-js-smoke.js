/**
 *	Nino features
 *	builder-frames-js-smoke.js	What the script of the tab "Header/footer" of the pane of the pages draws and asks for, over the real Nino.adminUi of the workbench and the real
 *															script of the panel - the table, the dialogs, the questions and the dialog of the blocks are the kernel's and the panel's own; the editor of the sheets
 *															is stood in for, to see what the tab hands it:
 *
 *															A. the list: a table of the workbench with the choice of the side, the search and the actions of a row - the standard frames have no rename and no delete -,
 *															   the word of the side in the main button, the box of a site that has none, a refusal said above the table
 *															B. the choice of a block: the dialog of the blocks of the panel of the pages, the question before anything is written - for the standard frame without its
 *															   name said twice -, the frame that was saved meanwhile is not forced, the last choice taken back
 *															C. a new frame: the form, the side in the source, the block that is chosen after it, what the server refuses at its field, the editor on the new frame
 *															D. renamed and taken away: the name in each language, the question, the pages that choose a frame said by number and by name and no button that deletes
 *															E. kept as a block: only where the account holds the right that writes one; the key follows the name, the question first, the languages without a text
 *															F. the editor and the address: #builder-frames/<frame>, every request of the sheet the action of a frame, the way back to the list, the unsaved work, the
 *															   address that is none, the way into the editor from the list and back, and the language of the workbench
 *
 *	Usage: node features/Builder/tests/builder-frames-js-smoke.js
 *	       NINO_ROOT=../nino node features/Builder/tests/builder-frames-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, documentOf } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');
// The words of the panel of the blocks too: its sentences for what it refuses are on the page when the tab asks it to keep a frame as a block
const themelab = fills( path.relative( root, path.join( featureDir, '../Themelab/text/en_US.php' ) ) );

/** The words of the panel by their keys: a test asks for the fill and never writes the sentence */
const W = function( key, ...params ) { return String( words.en['/_admin/builder/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

/** What an element and everything in it says */
const textOf = function( node ) { return node === undefined || node === null ? '' : String( node.textContent ?? '' )+ ( node.children ?? [] ).map( textOf ).join('') };

const LOCALES = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true } ];

const FRAMES = [
	{ key : 'header', side : 'header', template : 'frame-header', default : true, names : {}, name : '', uses : 3, pages : [ 'Home', 'Contact', 'About' ], stamp : 'aa', last : 0, undo : false },
	{ key : 'header-landing', side : 'header', template : 'frame-header-landing', default : false, names : { de_DE : 'Landung', en_US : 'Landing' }, name : 'Landing', uses : 1, pages : [ 'Home' ], stamp : 'cc', last : 0, undo : false },
	{ key : 'header-shop', side : 'header', template : 'frame-header-shop', default : false, names : {}, name : '', uses : 0, pages : [], stamp : 'dd', last : 0, undo : false },
	{ key : 'footer', side : 'footer', template : 'frame-footer', default : true, names : {}, name : '', uses : 0, pages : [], stamp : 'bb', last : 1763356920, undo : true },
];

/** The answer of the list of the frames */
const answer = function( extra ) {

	return Object.assign( { frames : FRAMES, bridge : true, develop : false, native : 'de_DE', locales : LOCALES, level : 2, contentLocale : 'de_DE', dir : '', dim : 'frame' }, extra ?? {} );
};

/** A block the dialog offers */
const entry = function( key, source, kind, label, extra ) {
	return Object.assign( { key : key, name : key, description : '', category : 'header', origin : { kind : kind, label : label }, own : kind === 'project', source : source, applied : false, off : null }, extra ?? {} );
};

/**
 *	The tab in a page of its own: the real workbench ui, the real script of the panel and the script of the tab, a router that remembers its address and a registry of unsaved
 *	input that answers as the shell does, the requests held until a test answers them. The editor of the sheets is a stand-in that remembers what it was given
 *
 *	@param		{Object}		[options]			{ lang, list, hash }
 *
 *	@return		{Object}
 */
function world( options ) {

	options = Object.assign( { lang : 'en-US', list : answer(), hash : '#builder-frames' }, options ?? {} );

	const page = element('div');
	const app = element('div');
	const list = element('div');
	const sheet = element('div');
	const pages = element('div');
	const requests = [];
	const timers = [];
	const dialogs = [];
	const said = [];
	const answers = [];
	const questions = [];
	const state = { hash : options.hash, pushed : [], opened : [], closed : 0, saved : [], sheet : false, held : 0 };

	page.id = 'page';
	app.id = 'builder-frames-app';
	list.id = 'builder-frames-list';
	sheet.id = 'builder-frames-sheet';
	sheet.className = 'admin-hidden';
	pages.id = 'builder-list';
	app.appendChild( list );
	app.appendChild( sheet );
	page.appendChild( app );
	page.appendChild( pages );

	const title = element('h2');

	page.setAttribute( 'data-panel', 'builder-frames' );
	title.className = 'admin-panel-title';
	page.appendChild( title );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.listeners = {};
	// What the page and the dialogs that stand in the body are made of, as a query of the document finds it
	doc.querySelectorAll = function( selector ) { return [].concat( page.querySelectorAll( selector ), doc.body.querySelectorAll( selector ) ) };
	doc.addEventListener = function( name, fn ) { ( doc.listeners[name] = doc.listeners[name] ?? [] ).push( fn ) };
	doc.removeEventListener = function() {};
	doc.createElement = function( tag ) {
		const created = element( tag );
		created.getBoundingClientRect = function() { return { top : 0, left : 0, width : 300, height : 200, right : 300, bottom : 200 } };
		if( tag === 'dialog' ) {
			created.showModal = function() { doc.body.appendChild( created ) };
			// As a browser does: a dialog that is closed says so, once, and leaves the body of the document
			created.close = function() { if( created.shut === true ) return; created.shut = true; if( created.parentNode !== null && created.parentNode !== undefined ) created.parentNode.removeChild( created ); fire( created, 'close' ) };
			dialogs.push( created );
		}
		return created;
	};

	const parse = function() {
		const raw = state.hash.replace( /^#/, '' );
		const parts = raw === '' ? [] : raw.split('/').map( decodeURIComponent );
		return { panel : parts[0] ?? '', parts : parts.slice( 1 ) };
	};

	const entries = {};
	const dirty = {
		asked : 0,
		register : function( name, entryOf ) { entries[name] = entryOf },
		isDirty : function( names ) { return ( names ?? Object.keys( entries ) ).some( function( name ) { return entries[name] !== undefined && entries[name].isDirty() === true } ) },
		guard : function( names, proceed, onCancel ) {

			if( dirty.isDirty( names ) === false ) {
				proceed();
				return;
			}

			const reply = answers.shift() ?? 'cancel';

			dirty.asked++;

			if( reply === 'discard' ) {
				proceed();
				return;
			}

			if( typeof onCancel === 'function' )
				onCancel();
		},
	};

	const router = {
		current : parse,
		href : function( panel, parts ) { return '#'+ [ panel ].concat( parts ?? [] ).map( function( part ) { return encodeURIComponent( part ).replace( /%40/g, '@' ) } ).join('/') },
		go : function( panel, parts ) { const hash = router.href( panel, parts ); if( hash !== state.hash ) { state.hash = hash; state.pushed.push( hash ) } },
		set : function( panel, parts ) { state.hash = router.href( panel, parts ) },
		leave : function( names, leaving, proceed, resync ) { leaving === true && dirty.isDirty( names ) === true ? dirty.guard( names, proceed, resync ) : proceed() },
	};

	const locale = { current : 'de_DE', native : 'de_DE', followed : {}, follow : function( name, fn ) { locale.followed[name] = fn }, set : function( code ) { locale.current = code } };

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return ( options.lang === 'de-DE' ? words.de[key] : words.en[key] ) ?? themelab[key] ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } },
		admin : { touch : function() {}, router : router, dirty : dirty, workLocale : locale },
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder };

	sandbox.window = {
		Nino : Nino,
		location : { hash : '', href : 'http://workbench.test/_admin/', replace : function( hash ) { state.hash = hash } },
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
		clearTimeout : function() {},
		addEventListener : function() {},
		removeEventListener : function() {},
	};

	// A pane as narrow as a phone: the media query of under 40 rem matches, and the change of it is kept for the test to tell
	if( options.narrow !== undefined )
		sandbox.window.matchMedia = function() { return { matches : options.narrow.matches === true, addEventListener : function( name, fn ) { options.narrow.change = fn } } };

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );

	Nino.adminUi.locales = { native : 'de_DE', names : { de_DE : 'Deutsch', en_US : 'English (US)' } };
	Nino.adminUi.say = function( text ) { said.push( text ) };
	Nino.adminUi.confirm = function( level, optionsOf ) { questions.push( { level : level, options : optionsOf } ); return true };

	load( path.relative( root, path.join( featureDir, 'assets/admin.js' ) ), context );

	// The editor of the sheets is the panel of the pages' own: what the tab hands it is what is looked at, so it is stood in for - it remembers the adapter and the draft, and says it is open
	const editor = Nino.admin.builder.editor;
	const open = { adapter : null, draft : null };

	editor.open = function( adapter, draft ) { open.adapter = adapter; open.draft = draft; state.opened.push( adapter.template ); state.sheet = true; return true };
	editor.close = function() { if( state.sheet === true ) state.closed++; state.sheet = false; open.adapter = null };
	editor.state = function() { return { sheet : state.sheet === true ? 'block' : 'page', adapter : open.adapter ?? { panel : 'builder' }, ready : state.sheet, locale : 'de_DE', view : { bar : { buttons : { frameblock : null, frameadopt : null }, line : { idle : function() {}, fail : function() {}, error : function() {} } } } } };
	editor.hold = function() { state.held++ };
	editor.call = function( action, payload, done ) { requests.push( { action : 'editor:'+ action, payload : payload, callback : function( reply ) { done( reply.status, reply.responseJSON ) } } ) };
	editor.replace = function() { return true };

	Nino.admin.themelab = { call : function( action, payload, done ) { requests.push( { action : 'themelab/'+ action, payload : payload, callback : function( reply ) { done( reply.status, reply.responseJSON ) } } ) } };

	load( path.relative( root, path.join( featureDir, 'assets/admin-frames.js' ) ), context );

	const w = {
		Nino : Nino, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, sheet : sheet, requests : requests, dialogs : dialogs, said : said, questions : questions, answers : answers, state : state, dirty : dirty, open : open, locale : locale, editor : editor,
		tab : Nino.admin['builder-frames'],

		/** Answer the oldest request that waits, and say which it was */
		reply : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return 'x' } } );
			return request;
		},

		/** The requests of an action, as they were made */
		asked : function( action ) { return requests.filter( function( request ) { return request.action === action } ) },

		/** Answer every request the tab has asked for in the way the server would */
		serve : function( serving ) {

			serving = Object.assign( {}, serving ?? {} );

			while( requests.length > 0 ) {

				const request = requests[0];

				if( request.action === 'builder/frame-list' )
					w.reply( 200, options.list );
				else if( serving[request.action] !== undefined )
					w.reply( serving[request.action].status, serving[request.action].body );
				else if( request.action === 'builder/frame-open' )
					w.reply( 200, { template : request.payload.template, frame : true, side : request.payload.template.indexOf('footer') !== -1 ? 'footer' : 'header', tree : [], contentLocale : 'de_DE', dir : '', stamp : 'aa', level : 2 } );
				else if( [ 'builder/frame-create', 'builder/frame-rename', 'builder/frame-remove', 'builder/framechoose', 'builder/framerestore' ].indexOf( request.action ) !== -1 )
					w.reply( 200, { key : 'header-new', template : 'frame-header-new', side : 'header' } );
				else if( request.action === 'themelab/adopt' )
					w.reply( 200, { key : 'block-x', gaps : [] } );
				else
					w.reply( 404, { error : 'not served: '+ request.action } );
			}
		},

		/** The tab shown at an address, as the shell shows it */
		show : function( hash ) {
			state.hash = hash;
			w.tab.showCurrent();
			w.serve();
		},

		table : function() { return list.querySelector('.nino-admin-table') },
		rows : function() { return findAll( list, function( el ) { return el.tagName === 'TR' && el.dataset.row !== undefined } ) },
		row : function( key ) { return findAll( list, function( el ) { return el.tagName === 'TR' && el.dataset.row === key } )[0] },
		buttons : function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' } ) },
		named : function( node, text ) { return w.buttons( node ).filter( function( el ) { return textOf( el ) === text || el.attributes['aria-label'] === text } )[0] },
		side : function( value ) { const select = list.querySelector('#builder-frames-side') ?? list.querySelector('select'); select.value = value; fire( select, 'change' ); },
		search : function( text ) { const input = list.querySelector('.nino-admin-table-search'); input.value = text; fire( input, 'input' ) },
		dialog : function() { return dialogs[dialogs.length - 1] },
	};

	return w;
}

// --- A. the list ------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames');

	const table = w.table();
	const heads = findAll( table, function( el ) { return el.tagName === 'TH' } );
	const keys = function() { return w.rows().map( function( row ) { return row.dataset.row } ).join() };
	const before = w.requests.length;

	check( 'A. the list of the frames is the table of the workbench in a frame that scrolls, with a search over the whole width above it, the choice of the side beside it and the word of the tab in its own mount',
		table !== null && w.list.querySelector('.nino-admin-table-scroll') !== null && w.list.querySelector('.nino-admin-table-search') !== null && w.list.querySelector('#builder-frames-side') !== null && w.asked('builder/frame-list').length === 0 );
	check( 'A. a row for each frame - the standard header, the named ones, the standard footer - with a head of words for the name, the side and the pages, and none for the buttons',
		keys() === 'header,header-landing,header-shop,footer' && heads.filter( function( head ) { return head.dataset.key !== undefined } ).map( function( head ) { return head.dataset.key } ).join() === 'name,side,uses' );
	check( 'A. a frame is named by the word of its side - the standard ones - or by its name, and by its word where it has none; the second line says the word, and the pages say how many choose the frame',
		textOf( w.row('header') ).indexOf( W('frame/header') ) !== -1 && textOf( w.row('header') ).indexOf( W('frame/standard') ) !== -1 && textOf( w.row('header') ).indexOf( W( 'frame/uses-many', '3' ) ) !== -1
		&& textOf( w.row('header-landing') ).indexOf('Landing') !== -1 && textOf( w.row('header-landing') ).indexOf( W( 'frame/word-of', 'landing' ) ) !== -1 && textOf( w.row('header-landing') ).indexOf( W('frame/uses-one') ) !== -1
		&& textOf( w.row('header-shop') ).indexOf('shop') !== -1 && textOf( w.row('header-shop') ).indexOf( W('frame/uses-none') ) !== -1 && textOf( w.row('footer') ).indexOf( W('frame/footer') ) !== -1 );

	w.side('footer');

	const footers = keys();

	w.side('header');

	const headers = keys();

	w.side('all');

	check( 'A. the choice of the side filters at once, with no request: the footers, the headers, all of them - and the table is drawn again', footers === 'footer' && headers === 'header,header-landing,header-shop' && keys() === 'header,header-landing,header-shop,footer' && w.requests.length === before );

	w.search('shop');
	const byWord = keys();
	w.search('frame-footer');
	const byFile = keys();
	w.search('nothing of the kind');
	const none = w.rows().length === 0 && textOf( w.list ).indexOf( W('frame/no-match') ) !== -1;
	w.search('');

	check( 'A. the search reads the word and the file of the template, and says that it finds nothing', byWord === 'header-shop' && byFile === 'footer' && none === true );

	const names = function( row ) { return w.buttons( row ).map( function( el ) { return el.attributes['aria-label'] ?? textOf( el ) } ) };
	const menuOf = function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-haspopup'] === 'menu' } )[0] };

	check( 'A. the standard header has the pencil, the choice and the keeping as a block - and no rename, no delete; a named frame has the pencil and the choice in its row and the others behind "more", the delete the last of them',
		names( w.row('header') ).join() === [ W('frame/edit-header'), W('frame/choose-header'), W('frame/adopt-header') ].join()
		&& names( w.row('header-landing') ).slice( 0, 2 ).join() === [ W( 'frame/edit-of-header', 'Landing' ), W( 'frame/choose-of-header', 'Landing' ) ].join() && menuOf( w.row('header-landing') ) !== undefined && menuOf( w.row('header') ) === undefined );

	check( 'A. the row has no "›" at its end - the list is a table, not a list of rows that open - and a click on the row opens nothing: the pencil does',
		findAll( w.list, function( el ) { return String( el.className ).indexOf('nino-admin-row-chev') !== -1 } ).length === 0 && ( function() { const before = w.state.hash; fire( w.row('header-shop'), 'click' ); return w.state.hash === before && w.state.opened.length === 0 } )() );
	check( 'A. the second line of a named frame says "Word: landing" - the word after a colon - and the standard ones say "Standard"',
		textOf( w.row('header-landing') ).indexOf('Word: landing') !== -1 && textOf( w.row('header') ).indexOf('Word:') === -1 );

	// a pane as narrow as a phone: the pencil is the only button that stands in a row, everything else is behind "More actions" - the name and the heads have the room
	const phone = world( { narrow : { matches : true } } );

	phone.show('#builder-frames');

	const actionsOf = function( x, key ) { return findAll( x.row( key ), function( el ) { return String( el.className ).split(' ').indexOf('nino-admin-row-actions') !== -1 } )[0] };
	const stands = function( x, key ) { return ( actionsOf( x, key ).children ?? [] ).filter( function( el ) { return el.tagName === 'BUTTON' } ).map( function( el ) { return el.attributes['aria-label'] ?? textOf( el ) } ) };
	const more = function( x, key ) { return x.buttons( x.row( key ) ).filter( function( el ) { return el.attributes['aria-haspopup'] === 'menu' } )[0] };
	const itemsOf = function( x, key ) { const trigger = more( x, key ); if( trigger === undefined ) return []; fire( trigger, 'click' ); return findAll( x.doc.body, function( el ) { return el.attributes.role === 'menuitem' } ).concat( findAll( x.row( key ), function( el ) { return el.attributes.role === 'menuitem' } ) ).map( function( el ) { return textOf( el ).trim() } ) };

	check( 'A. on a narrow pane a row has the pencil and one menu: the name is read whole, the heads of the columns stand where they stood, and every other action of the row is in the menu - the choice, the way back, the keeping as a block, rename, delete',
		stands( phone, 'header-landing' ).join() === W( 'frame/edit-of-header', 'Landing' ) && more( phone, 'header-landing' ) !== undefined && stands( phone, 'footer' ).join() === W('frame/edit-footer') && more( phone, 'footer' ) !== undefined
		&& heads.length === findAll( phone.table(), function( el ) { return el.tagName === 'TH' } ).length && textOf( phone.row('header-landing') ).indexOf('Landing') !== -1 );
	check( 'A. what is behind "More actions" of a narrow row is what the wide row has beside the pencil: the same actions, the delete the last and in red - and a press on an item does what the button did',
		( function() {
			const names = itemsOf( phone, 'header-landing' );
			return names.length === 4 && names[0] === W( 'frame/choose-of-header', 'Landing' ) && names[names.length - 1] === W( 'frame/remove-of-header', 'Landing' ) && names.every( function( name ) { return name.indexOf( W('frame/header') ) === 0 && name.indexOf('\u201eLanding\u201c') !== -1 || name.indexOf( W( 'frame/header' ).toLowerCase() ) !== -1 && name.indexOf('\u201cLanding\u201d') !== -1 } );
		} )() );

	// the pane turned wide again: the row is the wide one
	const turn = { matches : true };
	const turning = world( { narrow : turn } );

	turning.show('#builder-frames');
	turn.matches = false;
	turn.change();

	check( 'A. a pane that gets wide - a window resized, a phone turned - draws the list again with the wide row: the pencil and the choice stand in it',
		turn.change !== undefined && stands( turning, 'header-landing' ).length === 2 );

	const main = w.named( w.list, W('frame/new') );

	check( 'A. the main button of the bar says "New header/footer" where both sides are shown, and "New header" or "New footer" where one is chosen', main !== undefined && ( function() {
		w.side('header');
		const header = w.named( w.list, W('frame/new-header') ) !== undefined;
		w.side('footer');
		const footer = w.named( w.list, W('frame/new-footer') ) !== undefined;
		w.side('all');
		return header === true && footer === true;
	} )() );

	const bare = world( { list : answer( { frames : [] } ) } );

	bare.show('#builder-frames');

	check( 'A. a site with no frame has the box that says so, and no table', textOf( bare.list ).indexOf( W('frame/none-listed') ) !== -1 || bare.table() !== null );

	const refused = world();

	refused.tab.showCurrent();
	refused.reply( 403, { error : 'no', code : 'builder_level' } );

	check( 'A. a list that cannot be loaded says why above the place of the table, and draws none', textOf( refused.list ).length > 0 && refused.table() === null );
}

// --- B. the choice of a block -----------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames');

	fire( w.named( w.row('header'), W('frame/choose-header') ), 'click' );

	const asked = w.asked('builder/framechoices')[0];

	asked.callback( { status : 200, responseJSON : { blocks : [
		entry( 'soft', 'second', 'theme', 'Craft' ), entry( 'alike', 'other', 'theme', 'Craft' ), entry( 'basic', 'basis', 'basis', 'Basis' ),
		entry( 'applied', 'nino', 'theme', 'Nino', { applied : true } ), entry( 'mine', 'project', 'project', 'Project' ),
	], categories : [] }, getAllResponseHeaders : function() { return 'x' } } );

	const dialog = w.dialog();
	const items = findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } );
	const heads = findAll( dialog, function( el ) { return el.className === 'nino-admin-pick-group-title' } ).map( textOf );
	const tiles = w.asked('builder/frametile');

	check( 'B. the dialog of the choice has the blocks of the kind of the frame from every source under the name of their source - the own ones, the theme in use, then the others by name, two themes of one name told apart by their key - and asks the server by the template of the frame and the language; nothing is written by asking',
		asked.payload.template === 'frame-header' && asked.payload.locale === 'de_DE' && heads.join('|') === [ W('frame/own'), W( 'frame/theme', 'Nino' ), 'Basis', W( 'frame/theme-key', 'Craft', 'other' ), W( 'frame/theme-key', 'Craft', 'second' ) ].join('|')
		&& items.map( textOf ).join() === 'mine,applied,basic,alike,soft' && w.asked('builder/framechoose').length === 0
		&& textOf( dialog ).indexOf( W('frame/choose-title-header') ) !== -1 && textOf( dialog ).indexOf( W('frame/choose-title-footer') ) === -1 );

	tiles.forEach( function( request ) { request.callback( { status : 200, responseJSON : { token : 't', query : 'nino-preview=tok-'+ request.payload.key, ttl : 600, address : '/home' }, getAllResponseHeaders : function() { return 'x' } } ) } );

	const faces = findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-pick-image') !== -1 } );
	const framed = faces.map( function( face ) { const frame = findAll( face, function( el ) { return el.tagName === 'IFRAME' } )[0]; return frame === undefined ? '' : String( frame.attributes.src ?? frame.src ).split('tok-')[1] } );

	check( 'B. the picture of each item is the tile that the dialog of the blocks draws, asked for once by the template of the frame and the key of the block, and it stands in the face of that item',
		tiles.length === 5 && tiles.every( function( request ) { return request.payload.template === 'frame-header' } ) && framed.join() === 'mine,applied,basic,alike,soft' );

	fire( items[4], 'click' );

	const question = w.questions[w.questions.length - 1];

	check( 'B. the whole frame goes by the choice, so it is asked first - destructive - and the question of the standard frame says the side and the block, not the name of the frame - which is the side - a second time',
		question !== undefined && question.level === 'destructive' && question.options.message === W( 'frame/confirm-message-header-standard', 'soft' ) && question.options.message.indexOf( W('frame/header') ) === question.options.message.lastIndexOf( W('frame/header') ) );

	check( 'B. only the answer yes asks the server to write: the template, the key, the stamp of the frame the list saw and the language, and no tree, no words, no right',
		w.asked('builder/framechoose').length === 0 && ( question.options.onChoose( true ), w.asked('builder/framechoose').length === 1 )
		&& Object.keys( w.asked('builder/framechoose')[0].payload ).sort().join() === 'base,key,locale,template' && w.asked('builder/framechoose')[0].payload.base === 'aa' && w.asked('builder/framechoose')[0].payload.template === 'frame-header' );

	// a named frame has its name in the question
	const named = world();

	named.show('#builder-frames');
	fire( named.named( named.row('header-landing'), W( 'frame/choose-of-header', 'Landing' ) ), 'click' );
	named.asked('builder/framechoices')[0].callback( { status : 200, responseJSON : { blocks : [ entry( 'mine', 'project', 'project', 'Project' ) ], categories : [] }, getAllResponseHeaders : function() { return 'x' } } );
	fire( findAll( named.dialog(), function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } )[0], 'click' );

	check( 'B. the question of a named frame names it: its name and the block',
		named.questions[named.questions.length - 1].options.message === W( 'frame/confirm-message-header', 'Landing', 'mine' ) );

	// a frame that holds parts the account may not write says so in the question: they go with the frame and "Undo" brings them back - one sentence, and only there
	const lockedList = answer( { frames : FRAMES.map( function( frame ) { return Object.assign( {}, frame, { locked : frame.key === 'header-shop' } ) } ) } );
	const shut = world( { list : lockedList } );

	shut.show('#builder-frames');

	const askedFor = function( x, key, label ) {

		fire( x.named( x.row( key ), W( 'frame/choose-of-header', label ) ), 'click' );
		x.asked('builder/framechoices')[x.asked('builder/framechoices').length - 1].callback( { status : 200, responseJSON : { blocks : [ entry( 'mine', 'project', 'project', 'Project' ) ], categories : [] }, getAllResponseHeaders : function() { return 'x' } } );
		fire( findAll( x.dialog(), function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } )[0], 'click' );

		return x.questions[x.questions.length - 1].options.message;
	};
	const shopQuestion = askedFor( shut, 'header-shop', 'shop' );
	const plainQuestion = askedFor( shut, 'header-landing', 'Landing' );

	check( 'B. the question of a frame that holds parts the account may not write says in one sentence that they go and that "Undo" brings them back; the question of a frame without them does not',
		shopQuestion === W( 'frame/confirm-message-header', 'shop', 'mine' ) + ' '+ W('frame/confirm-locked') && plainQuestion === W( 'frame/confirm-message-header', 'Landing', 'mine' ) && W('frame/confirm-locked').indexOf('. ') === -1 );

	// the frame was saved elsewhere meanwhile: the server says so with its own code - the list says it in words and is drawn again, and nothing is forced
	const chose = w.asked('builder/framechoose')[0];

	w.requests.length = 0;
	chose.callback( { status : 409, responseJSON : { error : 'x', code : 'builder_frame_changed' }, getAllResponseHeaders : function() { return 'x' } } );

	check( 'B. a frame that was changed since the list was drawn is not forced: the answer 409 with the code of the frame says so in words, the list is asked for again, and no request is made with force',
		w.said.indexOf( W('frame/changed-header') ) !== -1 && w.asked('builder/frame-list').length === 1 && w.requests.every( function( request ) { return request.payload.force === undefined } ) );

	// a frame that is made of a block is no replacement: the same dialog applies the choice at once, with no question
	{
		const chosen = [];
		const before = w.questions.length;

		w.panel = w.Nino.admin.builder;
		w.panel.chooseFrame( { name : 'Header', opener : null, dir : '', confirm : false, load : function( done ) { done( 200, { blocks : [ entry( 'one', 'project', 'project', 'Project' ) ], categories : [] } ) }, tile : function() {}, fail : function() {}, none : function() {}, apply : function( key ) { chosen.push( key ) } } );

		const pick = w.dialog();

		fire( findAll( pick, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } )[0], 'click' );

		check( 'B. the dialog of the blocks of a frame, asked not to confirm - for a frame that is made of a block, which replaces none - applies the block at once and asks no question', chosen.join() === 'one' && w.questions.length === before );
	}

	// the last choice is taken back by the button of the row that has one, with the stamp the list saw
	const undo = world();

	undo.show('#builder-frames');
	fire( undo.named( undo.row('footer'), W('frame/undo-footer') ), 'click' );

	const back = undo.asked('builder/framerestore')[0];

	check( 'B. the way back is a button of the frame that has a last state to go back to and of no other, and it asks the server for the template and the stamp the list saw',
		undo.named( undo.row('header'), W('frame/undo-header') ) === undefined && back !== undefined && back.payload.template === 'frame-footer' && back.payload.base === 'bb' );

	undo.serve();

	check( 'B. the frame is taken back: the toast says it in the word of the side and the list is asked for again', undo.said.indexOf( W( 'frame/undone-footer', W('frame/footer') ) ) !== -1 );
}

// --- C. a new frame ---------------------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames');
	fire( w.named( w.list, W('frame/new') ), 'click' );

	const dialog = w.dialog();
	const input = function( key, code ) { return findAll( dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === key && ( code === undefined || el.dataset.locale === code ) } )[0] };
	const type = function( element, text ) { element.value = text; fire( element, 'input' ) };
	const select = findAll( dialog, function( el ) { return el.tagName === 'SELECT' } )[0];
	const options = findAll( select, function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.textContent } );

	check( 'C. the form asks for a name in each language, a word and what the frame is made of - the standard one, another one or a block, each with its side in the word of the side and none with the word for both',
		textOf( dialog ).indexOf( W('frame/new-title') ) !== -1 && input( 'name', 'de_DE' ) !== undefined && input( 'name', 'en_US' ) !== undefined && input( 'word' ) !== undefined
		&& options.join('|') === [ W('frame/header')+ ': '+ W('frame/source-standard'), W('frame/header')+ ': '+ W('frame/source-frame')+ ' - Landing', W('frame/header')+ ': '+ W('frame/source-frame')+ ' - shop', W('frame/header')+ ': '+ W('frame/source-block'), W('frame/footer')+ ': '+ W('frame/source-standard'), W('frame/footer')+ ': '+ W('frame/source-block') ].join('|') );

	type( input( 'name', 'de_DE' ), 'Fuß der Landung' );

	check( 'C. the word follows the name in the working language as it is typed - small letters, digits, single hyphens - until it is written by hand', input( 'word' ).value === 'fuss-der-landung' );

	select.value = 'footer|frame-footer';
	fire( dialog.querySelector('form'), 'submit' );

	const made = w.asked('builder/frame-create');

	check( 'C. the request names the side of the source, the word, the names in each language and the frame it is made of - and no block, no tree, no right',
		made.length === 1 && made[0].payload.side === 'footer' && made[0].payload.word === 'fuss-der-landung' && made[0].payload.from === 'frame-footer' && made[0].payload.block === undefined && made[0].payload.name.de_DE === 'Fuß der Landung' && made[0].payload.locale === 'de_DE' );

	w.reply( 200, { key : 'footer-fuss-der-landung', template : 'frame-footer-fuss-der-landung', side : 'footer' } );

	check( 'C. the frame that was made is told in the word of its side and is open in the editor by its address', w.said.indexOf( W( 'frame/created-footer', 'Fuß der Landung' ) ) !== -1 && w.state.hash === '#builder-frames/footer-fuss-der-landung' );

	// a refusal is said at its field and the dialog stays
	const again = world();

	again.show('#builder-frames');
	fire( again.named( again.list, W('frame/new') ), 'click' );

	const form = again.dialog();
	const typed = function( key, text ) { const element = findAll( form, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === key && ( key !== 'name' || el.dataset.locale === 'de_DE' ) } )[0]; element.value = text; fire( element, 'input' ) };

	typed( 'name', 'Landung' );
	typed( 'word', 'shop' );
	fire( form.querySelector('form'), 'submit' );
	again.reply( 409, { error : 'x', code : 'builder_frames_taken', params : [ 'shop' ], field : 'word' } );

	check( 'C. a word that is taken is refused by the server and said at the field of the word, in the words of the server - and nothing is opened', textOf( form ).indexOf( 'shop' ) !== -1 && again.state.opened.length === 0 && again.state.hash === '#builder-frames' );

	// the form stays open after the refusal with everything as it was typed: the name, the word and what the frame is made of - and the refusal is said at the field of the word
	const fieldOf = function( x, dialog, key ) { return findAll( dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === key && ( key !== 'name' || el.dataset.locale === 'de_DE' ) } )[0] };
	const sourceOf = function( dialog ) { return findAll( dialog, function( el ) { return el.tagName === 'SELECT' } )[0] };
	const kept = again.dialog();

	check( 'C. the form is open after a refusal, with the name, the word and the source as they were typed, and the refusal is said at the field of the word - nothing was closed, nothing was made',
		kept === form && form.shut !== true && form.parentNode !== null && fieldOf( again, form, 'name' ).value === 'Landung' && fieldOf( again, form, 'word' ).value === 'shop' && sourceOf( form ).value === 'header|frame-header'
		&& findAll( form, function( el ) { return String( el.className ).indexOf('nino-admin-field-error') !== -1 || el.attributes['aria-invalid'] === 'true' } ).length > 0 );

	// made of a block: the form is closed for the dialog of the blocks, which applies at once; the block is the source and the frame has no other
	const block = world();

	block.show('#builder-frames');
	fire( block.named( block.list, W('frame/new') ), 'click' );

	const bform = block.dialog();
	const bsel = findAll( bform, function( el ) { return el.tagName === 'SELECT' } )[0];
	const bname = findAll( bform, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0];

	bname.value = 'Kopf aus Block';
	fire( bname, 'input' );
	bsel.value = 'header|@block';
	fire( bform.querySelector('form'), 'submit' );

	const choices = block.asked('builder/framechoices')[0];

	check( 'C. "made of a block" asks the blocks that the panel has for the side that was chosen, and makes nothing yet', choices !== undefined && choices.payload.template === 'frame-header' && block.asked('builder/frame-create').length === 0 );

	choices.callback( { status : 200, responseJSON : { blocks : [ entry( 'one', 'project', 'project', 'Project' ) ], categories : [] }, getAllResponseHeaders : function() { return 'x' } } );

	const before = block.questions.length;
	const pick = block.dialog();

	fire( findAll( pick, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } )[0], 'click' );

	const fromBlock = block.asked('builder/frame-create')[0];

	check( 'C. the block that is chosen is the source of the frame: the request has the block and the side, the word made of the name, and no frame - and no question came first',
		fromBlock !== undefined && fromBlock.payload.block === 'one' && fromBlock.payload.from === undefined && fromBlock.payload.side === 'header' && fromBlock.payload.word === 'kopf-aus-block' && block.questions.length === before );

	// a refusal after the block was chosen opens the form again with what was typed: the name, the word, the block as the source - and says it at the field of the word
	fromBlock.callback( { status : 409, responseJSON : { error : 'x', code : 'builder_frames_taken', params : [ 'kopf-aus-block' ], field : 'word' }, getAllResponseHeaders : function() { return 'x' } } );

	const reopened = block.dialog();

	check( 'C. the form is open again after a refusal of a frame made of a block, with the name, the word and the block as the source as they were typed, and the refusal is said at the field of the word',
		reopened !== bform && reopened.shut !== true && reopened.parentNode !== null && findAll( reopened, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0].value === 'Kopf aus Block'
		&& findAll( reopened, function( el ) { return el.tagName === 'SELECT' } )[0].value === 'header|@block' && findAll( reopened, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'word' } )[0] !== undefined
		&& findAll( reopened, function( el ) { return String( el.className ).indexOf('nino-admin-field-error') !== -1 || el.attributes['aria-invalid'] === 'true' } ).length > 0 );

	// where one side is chosen the form offers the sources of that side only
	const footer = world();

	footer.show('#builder-frames');
	footer.side('footer');
	fire( footer.named( footer.list, W('frame/new-footer') ), 'click' );

	check( 'C. where the side is chosen in the list the form offers the sources of that side only',
		findAll( findAll( footer.dialog(), function( el ) { return el.tagName === 'SELECT' } )[0], function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.textContent } ).every( function( label ) { return label.indexOf( W('frame/footer') ) === 0 } ) );
}

// --- D. renamed and taken away ----------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames');

	// the buttons behind "more" are pressed through the menu of the row
	const press = function( world, key, text ) {

		const menu = findAll( world.row( key ), function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-haspopup'] === 'menu' } )[0];

		fire( menu, 'click' );

		const item = findAll( world.row( key ), function( el ) { return el.attributes.role === 'menuitem' && textOf( el ) === text } )[0];

		fire( item, 'click' );
	};

	press( w, 'header-landing', W( 'frame/rename-of-header', 'Landing' ) );

	const dialog = w.dialog();
	const field = findAll( dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0];

	check( 'D. the dialog of "Rename" says the side in its title and begins with the name the frame has in the working language, in each language', textOf( dialog ).indexOf( W('frame/rename-title-header') ) !== -1 && field !== undefined && field.value === 'Landung' );

	field.value = 'Start';
	fire( field, 'input' );
	fire( dialog.querySelector('form'), 'submit' );

	const renamed = w.asked('builder/frame-rename');

	check( 'D. renaming asks for the template of the frame and the new names, and nothing else - no word, no tree', renamed.length === 1 && renamed[0].payload.template === 'frame-header-landing' && renamed[0].payload.name.de_DE === 'Start' && renamed[0].payload.word === undefined && Object.keys( renamed[0].payload ).sort().join() === 'locale,name,template' );

	w.reply( 200, { template : 'frame-header-landing' } );

	// a refusal of the rename: the dialog stays open with the name as it was typed, and the refusal is said at the field
	const refusing = world();

	refusing.show('#builder-frames');
	press( refusing, 'header-landing', W( 'frame/rename-of-header', 'Landing' ) );

	const askedDialog = refusing.dialog();
	const typedName = findAll( askedDialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0];

	typedName.value = 'Shop';
	fire( typedName, 'input' );
	fire( askedDialog.querySelector('form'), 'submit' );
	refusing.reply( 409, { error : 'x', code : 'builder_frames_name_taken', params : [ 'Shop' ], field : 'name' } );

	check( 'D. the dialog of "Rename" is open after a refusal, with the name as it was typed, and the refusal is said in it - the list is not asked for again and nothing is closed',
		refusing.dialog() === askedDialog && askedDialog.shut !== true && askedDialog.parentNode !== null && findAll( askedDialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0].value === 'Shop'
		&& refusing.asked('builder/frame-list').length === 0 && textOf( askedDialog ).indexOf('Shop') !== -1 && refusing.said.indexOf( W( 'frame/renamed-header', 'Shop' ) ) === -1 );

	check( 'D. the toast says the new name in the word of the side and the list is asked for again', w.said.indexOf( W( 'frame/renamed-header', 'Start' ) ) !== -1 && w.asked('builder/frame-list').length === 1 );

	// the standard frames have neither
	check( 'D. the standard frames have no rename and no delete: nothing to press, nothing to ask the server to take away', w.named( w.row('header'), W( 'frame/rename-of-header', W('frame/header') ) ) === undefined && w.named( w.row('footer'), W( 'frame/remove-of-footer', W('frame/footer') ) ) === undefined );

	const removing = world();

	removing.show('#builder-frames');

	// a frame that pages choose is not asked about: the dialog names the number and the pages, says what to do and has no button that deletes - the one button is "Understood"
	press( removing, 'header-landing', W( 'frame/remove-of-header', 'Landing' ) );

	const used = removing.dialog();

	check( 'D. a frame that pages choose is not asked about: the dialog says how many pages choose it and which, and has no button that deletes - the one button is "Understood" - and nothing is asked of the server',
		textOf( used ).indexOf( W( 'frame/remove-used-header', 'Landing', '1', 'Home' ) ) !== -1 && findAll( used, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('frame/remove') } ).length === 0 && findAll( used, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('frame/remove-ok') } ).length === 1
		&& removing.asked('builder/frame-remove').length === 0 );

	const more = world( { list : answer( { frames : FRAMES.map( function( frame ) { return frame.key === 'header-landing' ? Object.assign( {}, frame, { uses : 5, pages : [ 'A', 'B', 'C' ] } ) : frame } ) } ) } );

	more.show('#builder-frames');
	press( more, 'header-landing', W( 'frame/remove-of-header', 'Landing' ) );

	check( 'D. where more pages choose it than the list names, the rest is counted: three by name and "and 2 more"', textOf( more.dialog() ).indexOf( 'A, B, C '+ W( 'frame/remove-more', '2' ) ) !== -1 && textOf( more.dialog() ).indexOf( '5' ) !== -1 );

	// a frame no page chooses is asked about, and the answer yes asks the server to take it away by its template
	press( removing, 'header-shop', W( 'frame/remove-of-header', 'shop' ) );

	const question = removing.questions[removing.questions.length - 1];

	check( 'D. "Delete" asks first, and says that the frame goes with its texts - destructive - and a turned down question asks the server for nothing',
		question.level === 'destructive' && question.options.message === W( 'frame/remove-message-header', 'shop' ) && removing.asked('builder/frame-remove').length === 0 );

	question.options.onChoose( true );

	const taken = removing.asked('builder/frame-remove');

	check( 'D. the answer yes asks the server to take the frame away by its template, and the toast says it', taken.length === 1 && taken[0].payload.template === 'frame-header-shop' && ( removing.serve(), removing.said.indexOf( W( 'frame/removed-header', 'shop' ) ) !== -1 ) );

	// the server refuses a frame that a page chose meanwhile: said above the table, in the words of the server
	const refused = world();

	refused.show('#builder-frames');
	press( refused, 'header-shop', W( 'frame/remove-of-header', 'shop' ) );
	refused.questions[refused.questions.length - 1].options.onChoose( true );
	refused.reply( 409, { error : 'x', code : 'builder_frames_used', params : [ 2, 'Home (/home), Contact (/contact)' ], field : 'template' } );

	check( 'D. a frame that a page chose meanwhile is refused by the server and the refusal is said above the table', findAll( refused.list, function( el ) { return String( el.className ).indexOf('builder-list-error') !== -1 } ).length === 1 );
}

// --- E. kept as a block ----------------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames');

	const adopt = w.named( w.row('header'), W('frame/adopt-header') );

	check( 'E. a frame is kept as a block where the account holds the right that writes one - the list says so - with a button for it in the row', adopt !== undefined );

	const plain = world( { list : answer( { bridge : false } ) } );

	plain.show('#builder-frames');

	check( 'E. for the account that holds no such right the row has no button for it - not shut, not there: neither the word of the button nor its name stands anywhere in the list, not in a menu either',
		textOf( plain.list ).indexOf( W('frame/adopt') ) === -1 && findAll( plain.list, function( el ) { return [ W('frame/adopt-header'), W('frame/adopt-footer'), W( 'frame/adopt-of-header', 'Landing' ) ].indexOf( el.attributes['aria-label'] ) !== -1 } ).length === 0 && w.named( w.row('header'), W('frame/adopt-header') ) !== undefined );

	fire( adopt, 'click' );

	const dialog = w.dialog();
	const inputs = findAll( dialog, function( el ) { return el.tagName === 'INPUT' } );
	const nameDe = inputs.filter( function( el ) { return el.dataset.key === 'name' && el.dataset.locale === 'de_DE' } )[0];
	const keyed = inputs.filter( function( el ) { return el.dataset.key === 'key' } )[0];

	check( 'E. the dialog names the frame in its title, says what is kept in the word of its side - and where the block is to be found - begins with the name of the frame of the site in the working language and the key made of it',
		textOf( dialog ).indexOf( W('frame/adopt-header') ) !== -1 && textOf( dialog ).indexOf( W('frame/adopt-message-header') ) !== -1 && nameDe.value === W( 'frame/adopt-default-name', W('frame/header') ) && keyed.value === 'kopfbereich-der-website'.replace( 'kopfbereich-der-website', keyed.value ) );

	nameDe.value = 'Mein Kopf';
	fire( nameDe, 'input' );

	check( 'E. the field of the key shows the key that is written: it follows the name in the working language as it is typed', keyed.value === 'mein-kopf' );

	keyed.value = 'eigener-kopf';
	fire( keyed, 'input' );
	nameDe.value = 'Anderer Kopf';
	fire( nameDe, 'input' );

	check( 'E. a key that was typed by hand stays as it was typed', keyed.value === 'eigener-kopf' );

	fire( dialog.querySelector('form'), 'submit' );

	const asked = w.asked('themelab/adopt');

	check( 'E. the first request is a question - check, nothing is written - for the frame, with the names, the description and the key that stands in the field, and no tree and no text of the frame - and it goes to the action of Themelab that writes an own block',
		asked.length === 1 && asked[0].payload.check === true && asked[0].payload.template === 'frame-header' && asked[0].payload.key === 'eigener-kopf' && asked[0].payload.name.de_DE === 'Anderer Kopf' && asked[0].payload.tree === undefined && asked[0].payload.texts === undefined );

	w.reply( 200, { gaps : [] } );

	const written = w.asked('themelab/adopt');

	check( 'E. where no language lacks a text the frame is written at once, with the same key and no check, no fill', written.length === 1 && written[0].payload.check === undefined && written[0].payload.key === 'eigener-kopf' && written[0].payload.fill === undefined );

	w.reply( 200, { key : 'eigener-kopf' } );

	check( 'E. the toast says the block that was made', w.said.indexOf( W( 'frame/adopt-done', 'Anderer Kopf' ) ) !== -1 );

	// a key that is taken is refused at the field and the dialog stays
	const taken = world();

	taken.show('#builder-frames');
	fire( taken.named( taken.row('footer'), W('frame/adopt-footer') ), 'click' );
	fire( taken.dialog().querySelector('form'), 'submit' );
	taken.reply( 409, { error : 'x', code : 'themelab_taken', params : [ 'fusszeile-der-website' ], field : 'key' } );

	check( 'E. a key that is taken is refused by the server and the dialog names it, at the field, and stays open', textOf( taken.dialog() ).indexOf( 'fusszeile-der-website' ) !== -1 && taken.asked('themelab/adopt').length === 0 );

	// a language that lacks a text is said before anything is written
	const gaps = world();

	gaps.show('#builder-frames');
	fire( gaps.named( gaps.row('header'), W('frame/adopt-header') ), 'click' );
	fire( gaps.dialog().querySelector('form'), 'submit' );
	gaps.reply( 200, { gaps : [ { locale : 'en_US', parts : [ '[button' ] } ] } );

	const gap = gaps.dialog();

	check( 'E. a language that lacks a text is said before anything is written: the dialog names the language and the part and offers to fill from the working language, and to cancel - and writes nothing yet',
		textOf( gap ).indexOf( W('frame/adopt-gaps-title') ) !== -1 && textOf( gap ).indexOf('[button') !== -1 && findAll( gap, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W( 'frame/adopt-gaps-fill', 'Deutsch' ) } ).length === 1 && gaps.asked('themelab/adopt').length === 0 );

	fire( findAll( gap, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W( 'frame/adopt-gaps-fill', 'Deutsch' ) } )[0], 'click' );

	const filled = gaps.asked('themelab/adopt')[0];

	check( 'E. "Fill from the working language" writes, naming that language - and no other - for the languages that lack a text', filled !== undefined && filled.payload.fill === 'de_DE' && filled.payload.check === undefined );
}

// --- F. the editor and the address ------------------------------------------------------------------------------

{
	const w = world();

	w.show('#builder-frames/header-landing');

	const opened = w.state.opened.slice();
	const adapter = w.open.adapter;

	check( 'F. the address #builder-frames/header-landing opens that frame: the server is asked for its template, the list is asked for too - the account may keep it as a block is the list\'s to say - and the editor of the sheets has the sheet with the adapter of the frame',
		opened.join() === 'frame-header-landing' && w.state.hash === '#builder-frames/header-landing' && adapter !== null && adapter.frame === true && adapter.template === 'frame-header-landing' && adapter.panel === 'builder-frames' && adapter.level === 'builder-frames-sheet' );

	// every request of the sheet is the action of a frame: builder/frame-<action>
	const log = [];

	w.Nino.admin.builder.call = function( action, payload ) { log.push( [ action, payload.template ] ) };
	[ 'open', 'preview', 'save', 'restore', 'blocks', 'block', 'tile', 'keys', 'variant', 'offer', 'notation', 'upload', 'unstage' ].forEach( function( action ) { adapter.call( action, { template : 'frame-header-landing' }, function() {} ) } );

	check( 'F. every request of the sheet is the action of a frame - builder/frame-<action> - of the panel of the pages, and names the frame; the base of a request is its template and the language the sheet is worked in',
		log.length === 13 && log.every( function( entry ) { return /^frame-[a-z]+$/.test( entry[0] ) === true && entry[1] === 'frame-header-landing' } ) && adapter.base().template === 'frame-header-landing' && adapter.base().locale === 'de_DE' && adapter.restore === true && adapter.props === 'panel' && adapter.sources === 'block' );

	check( 'F. the way back is the list of the tab - "Back to Header/footer", at the address #builder-frames - and the address of the sheet is the frame\'s own',
		adapter.back().name === W('tab/frames') && adapter.back().parts.length === 0 && adapter.href( [] ) === '#builder-frames' && adapter.href( [ 'header-landing' ] ) === '#builder-frames/header-landing' );

	check( 'F. the sheet has the button that replaces the frame by a block in the word of its side, and - where the account holds the right that writes an own block - the button that keeps it as one',
		adapter.buttons.map( function( button ) { return button.key } ).join() === 'frameblock,frameadopt' && adapter.buttons[0].label === W('frame/insert-header') && adapter.buttons[1].label === W('frame/adopt') && adapter.buttons[0].name === W( 'frame/insert-of-header', 'Landing' ) && adapter.buttons[1].name === W( 'frame/adopt-of-header', 'Landing' ) );

	// the footer says its own word
	const foot = world();

	foot.show('#builder-frames/footer');

	check( 'F. the standard footer has the words of the footer: the button says "Replace footer" and the sheet is named by the word of the side', foot.open.adapter.buttons[0].label === W('frame/insert-footer') && foot.open.adapter.name === W('frame/footer') );

	const plain = world( { list : answer( { bridge : false } ) } );

	plain.show('#builder-frames/header');

	check( 'F. an account that holds no right that writes an own block has no button that keeps the frame as one', plain.open.adapter.buttons.map( function( button ) { return button.key } ).join() === 'frameblock' );

	// the side of a frame is the server's word: the answer of the open says it, the key of the address does not
	const odd = world();

	odd.state.hash = '#builder-frames/header-landing';
	odd.tab.showCurrent();
	odd.serve( { 'builder/frame-open' : { status : 200, body : { template : 'frame-header-landing', frame : true, side : 'footer', tree : [], contentLocale : 'de_DE', dir : '', stamp : 'aa', level : 2 } } } );

	check( 'F. the side of the sheet is the side the server gives the frame: the buttons say the word of that side', odd.open.adapter.side === 'footer' && odd.open.adapter.buttons[0].label === W('frame/insert-footer') );

	// an address that names no frame is the list
	const bad = world();

	bad.show('#builder-frames/Header');
	const upper = bad.state.hash;
	const none = world();
	none.show('#builder-frames/header/extra');

	check( 'F. an address that names no frame - a capital, more than one part - is the list: the list is asked for, no sheet is opened, and the address is the list\'s',
		upper === '#builder-frames' && none.state.hash === '#builder-frames' && bad.state.opened.length === 0 && none.state.opened.length === 0 && bad.table() !== null );

	// the way in from the list, and the way out
	const go = world();

	go.show('#builder-frames');
	fire( go.buttons( go.row('header-shop') )[0], 'click' );
	go.serve();

	const reached = go.state.hash;
	const openedNow = go.state.opened.join();

	check( 'F. the pencil of a row opens the frame in the editor: the address is the frame\'s, the sheet is the sheet of that frame, and the list is given up - nothing of it stays in its mount', reached === '#builder-frames/header-shop' && openedNow === 'frame-header-shop' && go.list.children.length === 0 );

	go.state.hash = '#builder-frames';
	go.tab.showCurrent();
	go.serve();

	check( 'F. the way back leads to the list: the sheet is closed and the list is drawn again from the server', go.state.closed >= 1 && go.table() !== null && go.state.hash === '#builder-frames' );

	// unsaved work is asked about before another address is shown
	const keep = world();

	keep.show('#builder-frames/header');
	keep.dirty.register( 'builder-frames', { isDirty : function() { return true } } );
	keep.state.hash = '#builder-frames';
	keep.tab.showCurrent();

	check( 'F. unsaved work in the sheet is asked about before the list is shown, and the answer cancel keeps the sheet and its address',
		keep.dirty.asked === 1 && keep.state.hash === '#builder-frames/header' && keep.state.opened.length === 1 );

	// the way back of the sheet itself is asked about unsaved work too: the question, and the answer discard goes to the list
	const leave = world();

	leave.show('#builder-frames/header');
	leave.dirty.register( 'builder-frames', { isDirty : function() { return true } } );
	// the way back of the sheet - where there is a sheet to go back from: a script that lost it says so in the pin and not by a crash
	const goBack = function( x ) { if( x.open.adapter !== null ) x.open.adapter.go( [] ) };

	goBack( leave );

	const stayed = leave.state.hash === '#builder-frames/header' && leave.dirty.asked === 1 && leave.open.adapter !== null;

	leave.answers.push( 'discard' );
	goBack( leave );

	check( 'F. the way back from the sheet asks about unsaved work before it leads anywhere: cancel keeps the sheet and its address, discard goes to the list',
		stayed === true && leave.dirty.asked === 2 && leave.state.hash === '#builder-frames' );

	// the language of the workbench follows: the sheet is held and asked for again, or the choice is taken back
	const lang = world();

	lang.show('#builder-frames/header');
	lang.state.opened.length = 0;
	lang.locale.followed['builder-frames']();
	lang.serve();

	check( 'F. a language chosen in the workbench makes the sheet be asked for again, and the properties that were open are held for the new sheet', lang.state.held === 1 && lang.state.opened.join() === 'frame-header' );

	// another panel is shown: the tab asks nothing
	const away = world();

	away.show('#builder-frames');
	away.requests.length = 0;
	away.state.hash = '#themelab';
	away.tab.showCurrent();

	check( 'F. the tab asks for nothing while another panel is the one that is shown', away.requests.length === 0 );
}

done();
