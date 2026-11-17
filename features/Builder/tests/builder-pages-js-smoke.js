/**
 *	Nino features
 *	builder-pages-js-smoke.js	What the script of the panel draws of the list of the pages and of the view that makes one, over the real Nino.adminUi of the
 *														workbench - the table, the list, the bars, the fields and the help are the kernel's own:
 *
 *														A. the list: a table of the workbench with its search over the whole width - which reads the address and the file of the
 *														   template -, the columns, the sign of a page that lacks something, the row that opens, the frames as a short list, the one
 *														   main button in the bar of the list, the box of a list that has no page
 *														B. the view #builder/@new: the way back that names where it leads, a row for each language with the language as a badge, the
 *														   fields with their help behind the (?), the menus as boxes, the block to start with, the one main button; no dialog
 *														C. making a page: what is wrong is said at the field and in the line, nothing is sent for it, what the server refuses is said at
 *														   the field it names, the page that is made is opened and the toast says so; unsaved input is asked about, and the address of
 *														   the view is no page's
 *
 *														The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js
 *														of the checkout, three levels up or the one NINO_ROOT names. builder-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Builder/tests/builder-pages-js-smoke.js
 *	       NINO_ROOT=../nino node features/Builder/tests/builder-pages-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, documentOf } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const scriptPath = path.join( featureDir, 'assets/admin.js' );
const relative = path.relative( root, scriptPath );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

/** The words of the panel and of the workbench by their keys: a test asks for the fill and never writes the sentence */
const W = function( key, ...params ) { return String( words.en['/_admin/builder/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

/** What an element and everything in it says */
const textOf = function( node ) { return String( node.textContent ?? '' )+ ( node.children ?? [] ).map( textOf ).join('') };

const LOCALES = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true }, { locale : 'fr_FR', active : false } ];

/** The answer of the list: three pages, one of which lacks a language, and the two frames */
const answer = function( extra ) {

	return Object.assign( {
		pages : [
			{ template : 'page-home', name : 'Home', path : '/', locales : [ 'de_DE', 'en_US' ], navs : [ 'main' ], complete : true, blocks : 3, saved : 1763356920 },
			{ template : 'page-contact', name : 'Contact', path : '/contact', locales : [ 'de_DE', 'en_US' ], navs : [], complete : false, blocks : 1, saved : 1763300000 },
			{ template : 'page-about', name : 'About us', path : '/about', locales : [ 'de_DE', 'en_US' ], navs : [ 'main', 'footer' ], complete : true, blocks : 0, saved : 1763200000 },
		],
		frames : [ { key : 'header', template : 'frame-header' }, { key : 'footer', template : 'frame-footer' } ],
		create : true,
		navs : [ 'main', 'footer' ],
		native : 'de_DE',
		locales : LOCALES,
		level : 3,
		contentLocale : 'de_DE',
		dir : '',
	}, extra ?? {} );
};

/**
 *	The script in a page of its own: the real workbench ui, a router that remembers its address and a registry of unsaved input that answers as the
 *	shell does, the requests held until a test answers them
 *
 *	@param		{Object}		[options]			{ lang, list }
 *
 *	@return		{Object}
 */
function world( options ) {

	options = Object.assign( { lang : 'en-US', list : answer() }, options ?? {} );

	const page = element('div');
	const wrap = element('div');
	const app = element('div');
	const list = element('div');
	const form = element('div');
	const view = element('div');
	const requests = [];
	const timers = [];
	const dialogs = [];
	const said = [];
	const answers = [];
	const state = { hash : '#builder', pushed : [], discarded : 0, saved : [] };

	page.id = 'page';
	wrap.id = 'admin-page-wrap';
	app.id = 'builder-app';
	list.id = 'builder-list';
	form.id = 'builder-new';
	form.className = 'admin-hidden';
	view.id = 'builder-page';
	view.className = 'admin-hidden';
	app.appendChild( list );
	app.appendChild( form );
	app.appendChild( view );
	page.appendChild( app );
	page.appendChild( wrap );

	// The pane the panel stands in, with the name of the panel that its heading is
	const title = element('h2');

	page.setAttribute( 'data-panel', 'builder' );
	title.className = 'admin-panel-title';
	page.appendChild( title );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.listeners = {};
	doc.addEventListener = function( name, fn ) { ( doc.listeners[name] = doc.listeners[name] ?? [] ).push( fn ) };
	doc.createElement = function( tag ) {
		const created = element( tag );
		if( tag === 'dialog' ) {
			created.showModal = function() {};
			created.close = function() {};
			dialogs.push( created );
		}
		return created;
	};

	const parse = function() {
		const raw = state.hash.replace( /^#/, '' );
		const parts = raw === '' ? [] : raw.split('/').map( decodeURIComponent );
		return { panel : parts[0] ?? '', parts : parts.slice( 1 ) };
	};

	const router = {
		current : parse,
		href : function( panel, parts ) { return '#'+ [ panel ].concat( parts ?? [] ).map( function( part ) { return encodeURIComponent( part ).replace( /%40/g, '@' ) } ).join('/') },
		go : function( panel, parts ) { const hash = router.href( panel, parts ); if( hash !== state.hash ) { state.hash = hash; state.pushed.push( hash ) } },
		set : function( panel, parts ) { state.hash = router.href( panel, parts ) },
		leave : function( names, leaving, proceed, resync ) { leaving === true && dirty.isDirty( names ) === true ? dirty.guard( names, proceed, resync ) : proceed() },
	};

	// The registry of the shell as far as the panel uses it: a watched form is dirty when it differs from what was last taken, a question is answered from a queue
	const entries = {};
	const forms = {};
	const baseline = {};
	const serialize = function( node ) { return JSON.stringify( findAll( node, function( el ) { return /^(INPUT|SELECT|TEXTAREA)$/.test( el.tagName ) } ).map( function( el ) { return el.type === 'checkbox' ? el.checked : el.value } ) ) };
	const shown = function( node ) { for( let at = node; at !== null; at = at.parentNode ) if( String( at.className ).split(/\s+/).indexOf('admin-hidden') !== -1 ) return false; return true };
	const dirty = {
		asked : 0,
		register : function( name, entry ) { entries[name] = entry },
		isDirty : function( names ) { return ( names ?? Object.keys( entries ) ).some( function( name ) { return entries[name] !== undefined && entries[name].isDirty() === true } ) },
		snapshot : function( name ) { const node = forms[name]( ); baseline[name] = node === null ? undefined : serialize( node ) },
		watchForm : function( name, getter, save, bar ) {
			forms[name] = getter;
			entries[name] = { bar : bar, save : save, discard : function() { state.discarded++; dirty.snapshot( name ) }, isDirty : function() { const node = getter(); return node !== null && baseline[name] !== undefined && shown( node ) === true && serialize( node ) !== baseline[name] } };
		},
		guard : function( names, proceed, onCancel ) {

			if( dirty.isDirty( names ) === false ) {
				proceed();
				return;
			}

			const reply = answers.shift() ?? 'cancel';

			dirty.asked++;

			if( reply === 'cancel' ) {
				if( typeof onCancel === 'function' )
					onCancel();
				return;
			}

			if( reply === 'discard' ) {
				names.forEach( function( name ) { entries[name].discard() } );
				proceed();
				return;
			}

			entries[names[0]].save( function( ok ) { state.saved.push( ok ); if( ok === true ) proceed(); else if( typeof onCancel === 'function' ) onCancel() } );
		},
	};

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return ( options.lang === 'de-DE' ? words.de[key] : words.en[key] ) ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } },
		admin : {
			router : router,
			dirty : dirty,
			workLocale : { current : 'de_DE', native : 'de_DE', follow : function() {}, set : function() {} },
		},
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder };

	sandbox.window = {
		Nino : Nino,
		location : { hash : '', href : 'http://workbench.test/_admin/' },
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
		clearTimeout : function() {},
	};

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );

	// The names of the languages, which the shell hands over once
	Nino.adminUi.locales = { native : 'de_DE', names : { de_DE : 'Deutsch', en_US : 'English (US)', fr_FR : 'Français' } };
	Nino.adminUi.say = function( text ) { said.push( text ) };

	load( relative, context );

	const w = {
		Nino : Nino, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, form : form, requests : requests, dialogs : dialogs, said : said, answers : answers, state : state, dirty : dirty,
		panel : Nino.admin.builder,

		/** Answer the oldest request that waits, and say which it was */
		reply : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return 'x' } } );
			return request;
		},

		/** Answer every request the panel has asked for in the way the server would */
		serve : function( serving ) {

			serving = Object.assign( { create : null }, serving ?? {} );

			while( requests.length > 0 ) {

				const request = requests[0];

				if( request.action === 'builder/list' )
					w.reply( 200, options.list );
				else if( request.action === 'builder/blocks' )
					w.reply( 200, { blocks : [ { key : 'hero-full', name : 'Hero', off : null }, { key : 'old', name : 'Old', off : 'gone' } ] } );
				else if( request.action === 'builder/create' && serving.create !== null )
					w.reply( serving.create.status, serving.create.body );
				else if( request.action === 'builder/create' )
					w.reply( 200, { template : 'page-team', slug : 'team' } );
				else
					w.reply( 404, { error : 'not served: '+ request.action } );
			}
		},

		/** The panel shown at an address, as the shell shows it: the list is asked for, the view follows the address */
		open : function( hash ) {
			state.hash = hash;
			w.panel.showCurrent();
			w.serve();
		},

		table : function() { return list.querySelector('.nino-admin-table') },
		rows : function() { return findAll( list, function( el ) { return el.tagName === 'TR' && el.dataset.row !== undefined } ) },
		search : function( text ) { const input = list.querySelector('.nino-admin-table-search'); input.value = text; fire( input, 'input' ) },
		shown : function( node ) { return shown( node ) },
		inputOf : function( key ) { return form.querySelector('[data-key="'+ key+ '"]') },
		type : function( key, text ) { const input = w.inputOf( key ); input.value = text; fire( input, 'input' ) },
		submit : function() { fire( w.form.querySelector('form'), 'submit' ) },
	};

	return w;
}

// --- A. the list ------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open('#builder');

	const table = w.table();
	const heads = findAll( table, function( el ) { return el.tagName === 'TH' } );
	const bar = w.list.querySelector('.nino-admin-list-actions');

	check( 'A. the list of the pages is the table of the workbench in a frame that scrolls, with a search over the whole width above it', table !== null && w.list.querySelector('.nino-admin-table-scroll') !== null && w.list.querySelector('.nino-admin-table-toolbar--wide') !== null && w.list.querySelector('.nino-admin-table-search') !== null );
	check( 'A. a row for each page, in the order of the routes, with the name, the address, the number of blocks, the menus and the day it was written - and a head of words for each, none for the buttons', w.rows().map( function( row ) { return row.dataset.row } ).join() === 'page-home,page-contact,page-about'
		&& heads.filter( function( head ) { return head.dataset.key !== undefined } ).map( function( head ) { return head.dataset.key } ).join() === 'name,path,blocks,menus,saved' && textOf( w.rows()[0] ).indexOf('Home') !== -1 && textOf( w.rows()[0] ).indexOf('/') !== -1 && textOf( w.rows()[0] ).indexOf( W('page/menu-main') ) !== -1 && textOf( w.rows()[2] ).indexOf( W('page/menu-footer') ) !== -1 );
	check( 'A. the file of the template stands quietly under the name, and the sign that a language lacks something only at the page that lacks it, named for a screen reader', ( function() {
		const signs = w.rows().map( function( row ) { return findAll( row, function( el ) { return el.tagName === 'SVG' && el.attributes.role === 'img' } ).length } );
		return textOf( w.rows()[1] ).indexOf('page-contact.tpl') !== -1 && signs.join() === '0,1,0' && findAll( w.rows()[1], function( el ) { return el.attributes['aria-label'] === W('list/incomplete') } ).length === 1;
	} )() );

	w.search('/contact');
	const byAddress = w.rows().map( function( row ) { return row.dataset.row } ).join();
	w.search('page-about');
	const byFile = w.rows().map( function( row ) { return row.dataset.row } ).join();
	w.search('nothing of the kind');
	const none = w.rows().length === 0 && textOf( w.list ).indexOf( W('list/no-match') ) !== -1;
	w.search('');

	check( 'A. the search runs in the browser over the address and over the file of the template, and a search that finds nothing says so', byAddress === 'page-contact' && byFile === 'page-about' && none === true && w.requests.length === 0 );
	check( 'A. the search is named for a screen reader, with the words of the list', w.list.querySelector('.nino-admin-table-search').attributes['aria-label'] === W('list/search') );

	check( 'A. a row opens its page and says where it came from: the list', ( function() {
		fire( w.rows()[1], 'click' );
		return w.state.hash === '#builder/contact';
	} )() );

	w.open('#builder');

	check( 'A. a row has no button of its own: the page is opened by the row, and the table says so with its sign', w.rows().every( function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' } ).length === 0 && findAll( row, function( el ) { return el.className === 'nino-admin-row-chev' } ).length === 1 } ) );
	check( 'A. the frames are a short list under the table, each row a link to the frame that says it holds for every page', ( function() {
		const group = w.list.querySelector('.nino-admin-list-group');
		const links = findAll( group, function( el ) { return el.tagName === 'A' } );
		return group !== null && links.length === 2 && links[0].href === '#builder/home/@header' && textOf( group ).indexOf( W('hint/frame-short') ) !== -1 && textOf( group ).indexOf( W('label/frames') ) !== -1;
	} )() );
	check( 'A. the bar of the list has one main button - New page - and nothing else, and the button of the old place is gone', ( function() {
		const buttons = findAll( bar ?? element('div'), function( el ) { return el.tagName === 'BUTTON' } );
		const rest = findAll( w.list, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-btn') !== -1 && bar.children.indexOf( el ) === -1 } );
		return bar !== null && buttons.length === 1 && buttons[0].classList.contains('nino-admin-btn-primary') && textOf( buttons[0] ) === W('page/open') && rest.length === 0 && buttons[0].classList.contains('nino-admin-btn--small') === false;
	} )() );
	check( 'A. the main button leads to #builder/@new, and the list has no buttons of a page that cannot be had: no delete, no duplicate', ( function() {
		fire( bar.querySelector('button'), 'click' );
		const toNew = w.state.hash === '#builder/@new';
		return toNew === true && findAll( w.list, function( el ) { return el.className.indexOf('nino-admin-row-actions') !== -1 } ).length === 0;
	} )() );

	const poor = world( { list : answer( { create : false } ) } );

	poor.open('#builder');

	check( 'A. the account that may not make a page has the list without the bar', poor.list.querySelector('.nino-admin-list-actions') === null && poor.rows().length === 3 );

	const empty = world( { list : answer( { pages : [], frames : [] } ) } );

	empty.open('#builder');

	check( 'A. a site with no page has the box of the workbench with its one sentence and no button, no table - the one main button is the one of the bar', empty.table() === null && empty.list.querySelector('.nino-admin-empty') !== null && textOf( empty.list.querySelector('.nino-admin-empty') ).indexOf( W('msg/none') ) !== -1 && findAll( empty.list, function( el ) { return el.className.indexOf('nino-admin-btn-primary') !== -1 } ).length === 1 && findAll( empty.list.querySelector('.nino-admin-empty'), function( el ) { return el.tagName === 'BUTTON' } ).length === 0 );

	const plain = world( { list : answer( { navs : [] } ) } );

	plain.open('#builder');

	check( 'A. a site without menus has no column of them', findAll( plain.table(), function( el ) { return el.tagName === 'TH' && el.dataset.key === 'menus' } ).length === 0 );
}

// --- B. the view that makes a page ------------------------------------------------------------------------------

{
	const w = world();

	w.open('#builder/@new');

	const level = w.form;
	const form = level.querySelector('form');
	const bar = form.querySelector('.nino-admin-actionbar');

	check( 'B. the address #builder/@new shows the view that makes a page - and the list is behind it', w.shown( level ) === true && w.shown( w.list ) === false && w.state.hash === '#builder/@new' );
	check( 'B. the way back is the one back link of the context bar, says where it leads, and goes to the list', ( function() {
		const links = findAll( level, function( el ) { return el.className.indexOf('nino-admin-back-link') !== -1 } );
		fire( links[0], 'click' );
		const back = w.state.hash === '#builder';
		w.open('#builder/@new');
		return links.length === 1 && level.querySelector('.nino-admin-contextbar') !== null && textOf( links[0] ) === workbench['/_admin/common/label/back-to'].replace( '%s', W('label/pages') ) && links[0].href === '#builder' && back === true;
	} )() );

	check( 'B. the form checks when it is sent, not in the browser: it does not validate', form.noValidate === true && findAll( form, function( el ) { return el.attributes['aria-required'] === 'true' } ).length === 4 );
	check( 'B. the name and the address have a row for each language the website serves - the one it does not serve has none - with the language as a badge in front, the name of the workbench for it', ( function() {
		const badges = findAll( form, function( el ) { return el.className.indexOf('builder-new-language') !== -1 } ).map( textOf );
		return badges.join() === 'Deutsch,English (US),Deutsch,English (US)' && w.inputOf('name-de_DE') !== null && w.inputOf('address-en_US') !== null && w.inputOf('name-fr_FR') === null;
	} )() );
	check( 'B. every field has its help behind a (?) at its name and no text under the field: the section, the name, the address, the menus and the block', ( function() {
		const helps = findAll( form, function( el ) { return el.className === 'nino-admin-help' } );
		const under = findAll( form, function( el ) { return el.className.indexOf('nino-admin-hint') !== -1 } );
		return helps.length === 5 && under.length === 0 && findAll( form, function( el ) { return el.className.indexOf('nino-admin-help-button') !== -1 && /^Help for /.test( String( el.attributes['aria-label'] ?? el.title ?? '' ) ) } ).length === 5;
	} )() );
	check( 'B. the menus are boxes, one for each menu of the site, the block is a choice that begins with none and offers the blocks that are offered', ( function() {
		const boxes = findAll( form, function( el ) { return el.type === 'checkbox' } );
		const select = form.querySelector('select');
		return boxes.map( function( box ) { return box.value } ).join() === 'main,footer' && form.querySelector('.nino-admin-checklist') !== null && select.children.map( function( option ) { return option.value } ).join() === ',hero-full';
	} )() );
	check( 'B. the bar has the one main button, Create page, at the right, and the shell finds it as the bar of the form', ( function() {
		const buttons = findAll( bar, function( el ) { return el.tagName === 'BUTTON' } );
		return bar !== null && buttons.length === 1 && buttons[0].classList.contains('nino-admin-btn-primary') && buttons[0].type === 'submit' && textOf( buttons[0] ) === W('page/title') && w.dirty.isDirty( [ 'builder' ] ) === false;
	} )() );
	check( 'B. the address is proposed from the name of each language until it is written by hand', ( function() {
		w.type( 'name-de_DE', 'Über uns' );
		w.type( 'name-en_US', 'About us' );
		const proposed = w.inputOf('address-de_DE').value+ ' '+ w.inputOf('address-en_US').value;
		w.type( 'address-en_US', '/about' );
		w.type( 'name-en_US', 'About us now' );
		return proposed === '/ueber-uns /about-us' && w.inputOf('address-en_US').value === '/about';
	} )() );
	check( 'B. no dialog is made for a page: the list, the button and the view draw none', w.dialogs.length === 0 );
}

// --- C. making a page -------------------------------------------------------------------------------------------

{
	const fill = function( w ) {
		w.type( 'name-de_DE', 'Team' );
		w.type( 'name-en_US', 'Team' );
		w.type( 'address-de_DE', '/team' );
		w.type( 'address-en_US', '/team-en' );
	};
	const errorsOf = function( w ) { return findAll( w.form, function( el ) { return el.className === 'nino-admin-field-error' } ) };

	{
		const w = world();

		w.open('#builder/@new');
		w.type( 'name-de_DE', 'Team' );
		w.submit();

		const errors = errorsOf( w );

		check( 'C. a language that has no name is said at its field - the sentence names the field and the language -, in the line of the bar, with the focus on the field, and nothing is sent', errors.length === 1 && textOf( errors[0] ) === workbench['/_admin/common/error/missing'].replace( '%s', W('page/name')+ ' (English (US))' )
			&& w.inputOf('name-en_US').attributes['aria-invalid'] === 'true' && w.inputOf('name-en_US').focused === true && w.requests.length === 0
			&& w.form.querySelector('.nino-admin-status').dataset.state === 'error' );

		w.type( 'name-en_US', 'Team' );
		w.type( 'address-de_DE', '' );
		w.submit();

		check( 'C. the address that is missing is said at the field of its language, once the names are there', errorsOf( w ).length === 1 && w.inputOf('address-de_DE').attributes['aria-invalid'] === 'true' && w.requests.length === 0 );
	}

	{
		const w = world( { list : answer( { pages : answer().pages.concat( [ { template : 'page-new', name : 'News', path : '/new', locales : [ 'de_DE', 'en_US' ], navs : [], complete : true, blocks : 1, saved : 1763200000 } ] ) } ) } );

		w.open('#builder');
		fire( w.rows()[3], 'click' );

		check( 'C. a page whose address is new is a page: it opens from the list as the page, and the view that makes one has an address no page can have', w.state.hash === '#builder/new' && w.requests[0].action === 'builder/open' && w.requests[0].payload.template === 'page-new' && w.shown( w.form ) === false );
	}

	{
		const w = world();

		w.open('#builder/@new');
		fill( w );
		w.form.querySelector('[data-key="menu-footer"]').checked = true;
		w.form.querySelector('select').value = 'hero-full';
		w.submit();

		const sent = w.requests[0];

		check( 'C. what is sent is what is written: the name and the address of each language, the menus that are ticked and the block', sent.action === 'builder/create' && JSON.stringify( sent.payload ) === JSON.stringify( { names : { de_DE : 'Team', en_US : 'Team' }, addresses : { de_DE : '/team', en_US : '/team-en' }, navs : [ 'footer' ], block : 'hero-full' } ) );

		w.reply( 409, { error : 'x', code : 'builder_page_http_uri_taken', params : [ '/team-en' ], field : 'httpUri-en_US' } );

		const errors = errorsOf( w );

		check( 'C. what the server refuses is said at the field it names - in its row, with the sentence of the server - and in the line, the focus is there, and the view stays', errors.length === 1 && errors[0].parentNode === w.inputOf('address-en_US').parentNode.parentNode && w.inputOf('address-en_US').attributes['aria-invalid'] === 'true'
			&& w.inputOf('address-en_US').focused === true && w.shown( w.form ) === true && w.said.length === 0 && findAll( w.form, function( el ) { return el.tagName === 'BUTTON' && el.type === 'submit' } )[0].disabled === false );
	}

	{
		const w = world();

		w.open('#builder/@new');
		fill( w );
		w.submit();
		w.reply( 200, { template : 'page-team', slug : 'team' } );

		const toast = w.said.slice();

		w.serve();

		check( 'C. the page that was made is said as a toast with its name, the list is asked for again and the page is opened - and the view of the list is not shown on the way', toast.join() === W('page/made', 'Team') && w.state.hash === '#builder/team' && w.requests.every( function( request ) { return request.action !== 'builder/create' } ) );
	}

	{
		const w = world();

		w.open('#builder/@new');
		fill( w );

		check( 'C. what is typed is unsaved input of the panel: the shell is asked about it, and a leaving that is turned down keeps the view as it was', ( function() {
			const dirtyNow = w.dirty.isDirty( [ 'builder' ] ) === true;
			w.answers.push( 'cancel' );
			fire( findAll( w.form, function( el ) { return el.className.indexOf('nino-admin-back-link') !== -1 } )[0], 'click' );
			return dirtyNow === true && w.dirty.asked === 1 && w.shown( w.form ) === true && w.inputOf('name-de_DE').value === 'Team';
		} )() );
		check( 'C. Discard leaves for the list, and a view that holds nothing is left without a question', ( function() {
			w.answers.push( 'discard' );
			fire( findAll( w.form, function( el ) { return el.className.indexOf('nino-admin-back-link') !== -1 } )[0], 'click' );
			const left = w.state.hash === '#builder' && w.shown( w.list ) === true;
			w.state.hash = '#builder/@new';
			w.panel.follow();
			const asked = w.dirty.asked;
			fire( findAll( w.form, function( el ) { return el.className.indexOf('nino-admin-back-link') !== -1 } )[0], 'click' );
			return left === true && w.dirty.asked === asked && w.state.hash === '#builder';
		} )() );
		check( 'C. the address of the view in a step of the history, with input that is not saved, asks as the back link does', ( function() {
			w.state.hash = '#builder/@new';
			w.panel.follow();
			w.type( 'name-de_DE', 'Anders' );
			w.state.hash = '#builder';
			w.answers.push( 'cancel' );
			w.panel.showCurrent();
			return w.dirty.asked === 3 && w.state.hash === '#builder/@new' && w.shown( w.form ) === true;
		} )() );
	}

	{
		const w = world( { list : answer( { create : false } ) } );

		w.open('#builder/@new');

		check( 'C. the account that may not make a page is taken to the list by the address of the view', w.state.hash === '#builder' && w.shown( w.list ) === true && w.shown( w.form ) === false );
	}
}

// --- D. what the view does with the hand ------------------------------------------------------------------------

{
	const w = world( { list : answer( { navs : [ 'main', 'odd' ] } ) } );

	w.open('#builder/@new');

	check( 'D. the view opens with the focus on the name of the panel, its heading - not in a field', w.title.focused === true && w.inputOf('name-de_DE').focused !== true );
	check( 'D. the language is a quiet word in front of the field, no badge with a frame, and the rows are one grid of two columns', ( function() {
		const tags = findAll( w.form, function( el ) { return el.className.indexOf('builder-new-language') !== -1 } );
		return tags.length === 4 && tags.every( function( tag ) { return tag.className.indexOf('nino-admin-badge') === -1 && tag.dataset.state === undefined } );
	} )() );
	check( 'D. a menu is called by its name with its identifier quietly behind it, and a menu that has no name by its identifier alone', ( function() {
		const labels = findAll( w.form.querySelector('.nino-admin-checklist'), function( el ) { return el.tagName === 'LABEL' } );
		return textOf( labels[0] ) === W('page/menu-main')+ 'main' && labels[0].children.some( function( el ) { return el.className === 'nino-admin-checklist-state' } ) && textOf( labels[1] ) === 'odd' && labels[1].children.every( function( el ) { return el.className !== 'nino-admin-checklist-state' } );
	} )() );

	w.type( 'name-de_DE', 'Eins' );
	w.type( 'address-de_DE', '/hand' );
	w.type( 'name-de_DE', 'Eins zwei' );
	const kept = w.inputOf('address-de_DE').value;
	w.type( 'address-de_DE', '' );
	w.type( 'name-de_DE', 'Drei vier' );

	check( 'D. an address that is written by hand is kept, and one that is emptied is proposed again from the name', kept === '/hand' && w.inputOf('address-de_DE').value === '/drei-vier' );

	check( 'D. the choice of a language in the workbench asks nothing of the form, which shows them all - for as long as the choice is made - and the input is asked about at any other time', ( function() {
		const typed = w.dirty.isDirty( [ 'builder' ] ) === true;
		w.doc.listeners.change.forEach( function( run ) { run( { target : { dataset : { worklocale : '' } } } ) } );
		const during = w.dirty.isDirty( [ 'builder' ] );
		w.timers.forEach( function( run ) { run() } );

		return typed === true && during === false && w.dirty.isDirty( [ 'builder' ] ) === true;
	} )() );

	check( 'D. a change of another select is no choice of the language', ( function() {
		w.doc.listeners.change.forEach( function( run ) { run( { target : { dataset : {} } } ) } );
		return w.dirty.isDirty( [ 'builder' ] ) === true;
	} )() );
}

{
	const w = world();

	w.open('#builder/@new');
	w.type( 'name-de_DE', 'Team' );
	w.type( 'name-en_US', 'Team EN' );
	w.Nino.admin.workLocale.current = 'en_US';
	w.submit();
	w.submit();

	const button = findAll( w.form, function( el ) { return el.tagName === 'BUTTON' && el.type === 'submit' } )[0];
	const once = w.requests.filter( function( request ) { return request.action === 'builder/create' } ).length === 1;

	w.reply( 200, { template : 'page-team', slug : 'team' } );
	w.submit();

	check( 'D. a page is sent once however often the button is pressed - and the button stays shut after the page was made, until the page is open: a second press sends nothing', once === true && button.disabled === true && w.requests.every( function( request ) { return request.action !== 'builder/create' } ) );
	check( 'D. the toast names the page in the language of the workbench in which it is edited', w.said.join() === W('page/made', 'Team EN') );
}

{
	const w = world();

	w.open('#builder');

	const cell = w.rows()[0].children[4];

	w.search( textOf( cell ) );

	check( 'D. the search finds the date the row shows', w.rows().length >= 1 && w.rows().every( function( row ) { return textOf( row.children[4] ) === textOf( cell ) } ) );
}

done();
