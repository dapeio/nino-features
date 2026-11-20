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
 *														E. the two buttons of a row: only for the account that may make a page, the button to delete shut for the two pages the kernel
 *														   never takes away, the server asked first and one question after it that names who reads and who links - at most five of
 *														   each -, Cancel asks nothing more, a refusal is said above the table
 *														F. the copy of a page: the row leads to the view of the copy, which starts from what the server says of the page, has the page
 *														   in place of the block, and sends what is written with the page it is made from
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

/** What the server says of a page that would be taken away: the page, and who reads its words and who links to it */
const asked = function( extra ) {
	return Object.assign( { template : 'page-contact', name : 'Contact', path : '/contact', remnant : false, kept : [], linked : [] }, extra );
};

/** What the server says of the page a copy is made from: a name, a free address and the menus of it for each language */
const ORIGIN = { template : 'page-about', label : 'About us', names : { de_DE : 'Über uns (Kopie)', en_US : 'About us (Copy)' }, addresses : { de_DE : '/ueber-2', en_US : '/about-2' }, navs : [ 'footer' ] };

const LOCALES = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true }, { locale : 'fr_FR', active : false } ];

/** The answer of the list: three pages, one of which lacks a language, and the two frames */
const answer = function( extra ) {

	return Object.assign( {
		pages : [
			{ template : 'page-home', name : 'Home', path : '/', locales : [ 'de_DE', 'en_US' ], navs : [ 'main' ], complete : true, locked : 'home', blocks : 3, saved : 1763356920 },
			{ template : 'page-contact', name : 'Contact', path : '/contact', locales : [ 'de_DE', 'en_US' ], navs : [], complete : false, locked : '', blocks : 1, saved : 1763300000 },
			{ template : 'page-about', name : 'About us', path : '/about', locales : [ 'de_DE', 'en_US' ], navs : [ 'main', 'footer' ], complete : true, locked : '', blocks : 0, saved : 1763200000 },
		],
		create : true,
		frames : [],
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
	const touched = [];
	const questions = [];
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
		created.getBoundingClientRect = function() { return { top : 0, left : 0, width : 300, height : 200, right : 300, bottom : 200 } };
		if( tag === 'dialog' ) {
			// As a browser does: a modal dialog stands in the body of the document while it is open
			created.showModal = function() { doc.body.appendChild( created ) };
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
			touch : function( topic, uri ) { touched.push( [ topic, uri ] ) },
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
	// The question of the workbench is kept as it was asked: what it says, how it ends and what comes of each answer is the panel's
	Nino.adminUi.confirm = function( level, options ) { questions.push( { level : level, options : options } ); return true };

	load( relative, context );

	const w = {
		Nino : Nino, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, form : form, requests : requests, dialogs : dialogs, said : said, touched : touched, questions : questions, answers : answers, state : state, dirty : dirty,
		panel : Nino.admin.builder,

		/** Answer the oldest request that waits, and say which it was */
		reply : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return 'x' } } );
			return request;
		},

		/** Answer every request the panel has asked for in the way the server would */
		serve : function( serving ) {

			serving = Object.assign( { create : null, uses : null, remove : null, copyof : null, duplicate : null }, serving ?? {} );

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
				else if( request.action === 'builder/duplicate' )
					w.reply( serving.duplicate?.status ?? 200, serving.duplicate?.body ?? { template : 'page-contact-2', slug : 'contact-2', copied : { texts : 1, slots : 0, files : 0 } } );
				else if( request.action === 'builder/copyof' )
					w.reply( serving.copyof?.status ?? 200, serving.copyof?.body ?? ORIGIN );
				else if( request.action === 'builder/uses' )
					w.reply( serving.uses?.status ?? 200, serving.uses?.body ?? asked( {} ) );
				else if( request.action === 'builder/remove' )
					w.reply( serving.remove?.status ?? 200, serving.remove?.body ?? { template : 'page-contact', texts : 3, files : 0, kept : 0, linked : 0 } );
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

	check( 'A. the page is opened by the row, and the table says so with its sign', w.rows().every( function( row ) { return findAll( row, function( el ) { return el.className === 'nino-admin-row-chev' } ).length === 1 } ) );
	check( 'A. the list of the pages has no list of the frames: they are written in the editor of the blocks', ( function() {
		return findAll( w.list, function( el ) { return el.tagName === 'A' && /@(?:header|footer)/.test( String( el.href ) ) } ).length === 0 && textOf( w.list ).indexOf( W('frames/default') ) === -1;
	} )() );
	check( 'A. the bar of the list has one main button - New page - and nothing else, and the button of the old place is gone', ( function() {
		const buttons = findAll( bar ?? element('div'), function( el ) { return el.tagName === 'BUTTON' } );
		const rest = findAll( w.list, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-btn') !== -1 && bar.children.indexOf( el ) === -1 && el.className.indexOf('nino-admin-btn-icon') === -1 } );
		return bar !== null && buttons.length === 1 && buttons[0].classList.contains('nino-admin-btn-primary') && textOf( buttons[0] ) === W('page/open') && rest.length === 0 && buttons[0].classList.contains('nino-admin-btn--small') === false;
	} )() );
	check( 'A. the main button leads to #builder/@new', ( function() {
		fire( bar.querySelector('button'), 'click' );
		return w.state.hash === '#builder/@new';
	} )() );

	const poor = world( { list : answer( { create : false } ) } );

	poor.open('#builder');

	check( 'A. the account that may not make a page has the list without the bar and without the buttons of a row', poor.list.querySelector('.nino-admin-list-actions') === null && poor.rows().length === 3 && findAll( poor.list, function( el ) { return el.className.indexOf('nino-admin-row-actions') !== -1 } ).length === 0 );

	const empty = world( { list : answer( { pages : [] } ) } );

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

// --- E. the buttons of a row ------------------------------------------------------------------------------------

{
	const buttonsOf = function( w, at ) { return findAll( w.rows()[at], function( el ) { return el.tagName === 'BUTTON' } ) };

	{
		const w = world();

		w.open('#builder');

		const own = [ 0, 1, 2 ].map( function( at ) { return buttonsOf( w, at ) } );

		const last = function( buttons ) { return buttons[buttons.length - 1] };
		const helps = function( at ) { return findAll( w.rows()[at], function( el ) { return el.className === 'nino-admin-help' } ) };

		check( 'E. each row has the small button to copy first and the one that deletes last, in the danger kind, in the one group the workbench makes of them - and nothing between them but the help of a shut button',
			own.every( function( buttons, at ) { return last( buttons ).classList.contains('nino-admin-btn-danger') && buttons[0].classList.contains('nino-admin-btn-danger') === false && buttons.length === 2 + helps( at ).length } ) );
		check( 'E. the button to delete is shut for the page the kernel never takes away, with its reason as the title and behind a (?) that a keyboard reaches - and for no other page', last( own[0] ).disabled === true && String( last( own[0] ).title ).length > 0 && helps( 0 ).length === 1 && String( textOf( helps( 0 )[0] ) ).length > 0
			&& last( own[1] ).disabled === false && last( own[2] ).disabled === false && helps( 1 ).length === 0 && helps( 2 ).length === 0 && own[0][0].disabled === false );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );

		check( 'E. the server is asked first what the page would keep and who links to it, with the template and the language of the view - the row does not open, nothing is deleted and no question is asked yet', w.requests.length === 1 && w.requests[0].action === 'builder/uses' && w.requests[0].payload.template === 'page-contact'
			&& typeof w.requests[0].payload.locale === 'string' && w.state.hash === '#builder' && w.questions.length === 0 );

		w.serve();

		const question = w.questions[0];

		check( 'E. one question follows the answer: a destructive one with a title, whose way on is the danger one and which names the page and its address - and nothing else where nothing is kept and nothing links', w.questions.length === 1 && question.level === 'destructive' && question.options.title.length > 0 && question.options.kind === 'danger'
			&& question.options.message === W( 'confirm/delete-page', 'Contact', '/contact' ) && w.requests.length === 0 );

		question.options.onChoose( false );

		check( 'E. Cancel asks nothing more: no deletion is sent, the table is as it was, nothing is said', w.requests.length === 0 && w.said.length === 0 && w.rows().length === 3 && w.touched.length === 0 );

		question.options.onChoose( true );

		check( 'E. Yes sends the deletion of that page - and only that - once', w.requests.length === 1 && w.requests[0].action === 'builder/remove' && w.requests[0].payload.template === 'page-contact' );

		w.serve();

		check( 'E. what came of it is said as a toast with the name, the shell is told what was written - the routes and the templates among it, with the address of the page -, and the list is asked for again', w.said.join() === W( 'msg/page-deleted', 'Contact' ) && w.touched.some( function( told ) { return told[0] === 'routes' && told[1] === '/contact' } ) && w.touched.some( function( told ) { return told[0] === 'templates' } ) );
	}

	{
		const readers = [ 1, 2, 3, 4, 5, 6, 7 ].map( function( number ) { return { key : '/template/page-contact/hero/title', by : [ 'reader-'+ number+ '.tpl' ] } } );
		const links = [ 1, 2, 3 ].map( function( number ) { return { by : 'linker-'+ number+ '.tpl', key : '' } } );
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		w.serve( { uses : { status : 200, body : asked( { kept : readers, linked : links } ) } } );

		const message = w.questions[0].options.message;

		check( 'E. what is kept is counted and its readers are listed - at most five, the rest counted - and who links is listed the same way: each name once, in the order the server gave', ( message.match( /reader-\d\.tpl/g ) ?? [] ).join() === 'reader-1.tpl,reader-2.tpl,reader-3.tpl,reader-4.tpl,reader-5.tpl'
			&& ( message.match( /linker-\d\.tpl/g ) ?? [] ).join() === 'linker-1.tpl,linker-2.tpl,linker-3.tpl' && message.indexOf( W( 'confirm/delete-kept', 7, '' ).split(' (')[0] ) !== -1 && message.indexOf( W( 'confirm/more', 2 ) ) !== -1 && message.indexOf( W( 'confirm/more', 1 ) ) === -1 );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		// the same page reads twice - the file of its template and a text of its - and a page of the list is named by its name, where the list has it
		w.serve( { uses : { status : 200, body : asked( { kept : [ { key : 'a', by : [ 'page-about.tpl' ] }, { key : 'b', by : [ 'page-about.tpl', 'file-of-nobody.tpl' ] }, { key : 'c', by : [ 'page-about.tpl' ] } ] } ) } } );

		const message = w.questions[0].options.message;

		check( 'E. who reads is told once and by the name of the page where the list has it - the number is the number of the names listed, not of the texts that stay', message.indexOf('page-about.tpl') === -1 && message.indexOf('About us') !== -1 && message.indexOf('file-of-nobody.tpl') !== -1 && message.indexOf( W( 'confirm/delete-kept', 2, 'About us, file-of-nobody.tpl' ) ) !== -1 );
	}

	{
		const w = world( { list : answer( { pages : answer().pages.concat( [ { template : 'page-legal-imprint', name : 'Imprint', path : '/imprint', locales : [ 'de_DE', 'en_US' ], navs : [], complete : true, locked : 'runtime', blocks : 0, saved : 0 } ] ) } ) } );

		w.open('#builder');

		const own = findAll( w.rows()[3], function( el ) { return el.tagName === 'BUTTON' } );

		check( 'E. a page that the website serves itself cannot be copied either: both buttons are shut, each with the reason as its title, and the reason stands behind the one (?)', own.filter( function( button ) { return button.disabled === true && String( button.title ).length > 0 } ).length === 2 && findAll( w.rows()[3], function( el ) { return el.className === 'nino-admin-help' } ).length === 1 );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		w.serve( { uses : { status : 409, body : { error : 'x', code : 'routes_remove_shared', params : [], field : 'template' } } } );
		w.state.hash = '#builder/contact';
		w.panel.follow();
		w.serve();
		w.state.hash = '#builder';
		w.panel.follow();

		check( 'E. what an action of the list said was wrong is gone when the address changes', w.list.querySelector('.builder-list-error') === null );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		w.serve( { uses : { status : 200, body : asked( { template : 'page-gone', name : 'page-gone', path : '', remnant : true } ) } } );

		check( 'E. a leftover with no address is asked about in the words of a leftover, not of a page at an address', w.questions[0].options.message === W( 'confirm/delete-leftover', 'page-gone' ) );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		w.serve( { uses : { status : 409, body : { error : 'x', code : 'routes_remove_shared', params : [ 'page-contact', 'a, b' ], field : 'template' } } } );

		const line = w.list.querySelector('.builder-list-error');

		check( 'E. a refusal of the server is said above the table, as an alert, and no question is asked', line !== null && line.attributes.role === 'alert' && textOf( line ).length > 0 && w.questions.length === 0 && w.rows().length === 3 && w.list.firstChild === line );

		fire( buttonsOf( w, 2 )[1], 'click' );

		check( 'E. the next action takes the old sentence away', w.list.querySelector('.builder-list-error') === null );
	}

	{
		const w = world();

		w.open('#builder');
		fire( buttonsOf( w, 1 )[1], 'click' );
		w.serve();
		w.questions[0].options.onChoose( true );
		w.serve( { remove : { status : 409, body : { error : 'x', code : 'routes_remove_included', params : [ 'page-contact', 'page-about.tpl' ], field : 'template' } } } );

		check( 'E. a deletion that the server refuses when it is asked is said in the same place, and says nothing of success', w.list.querySelector('.builder-list-error') !== null && w.said.length === 0 && w.touched.length === 0 );
	}

	{
		const w = world( { list : answer( { create : false } ) } );

		w.open('#builder');

		check( 'E. an account that may not make a page has no button in any row', w.rows().every( function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' } ).length === 0 } ) );
	}
}

// --- F. the copy of a page --------------------------------------------------------------------------------------

{
	const copyOf = function( w, at ) { fire( findAll( w.rows()[at], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' ) };

	{
		const w = world();

		w.open('#builder');
		copyOf( w, 2 );

		check( 'F. the button of a row leads to the view of the copy, whose address names the page - and the panel asks the server what the form starts from', w.state.hash === '#builder/@new/about' && w.requests.length === 1 && w.requests[0].action === 'builder/copyof' && w.requests[0].payload.template === 'page-about' );

		w.serve();

		const form = w.form.querySelector('form');

		check( 'F. the form starts from what the server said: the name and the address of each language, the menus of the page ticked and no other - and the page is the one the address names', w.shown( w.form ) === true && w.inputOf('name-de_DE').value === ORIGIN.names.de_DE && w.inputOf('name-en_US').value === ORIGIN.names.en_US
			&& w.inputOf('address-de_DE').value === ORIGIN.addresses.de_DE && w.inputOf('address-en_US').value === ORIGIN.addresses.en_US && w.form.querySelector('[data-key="menu-footer"]').checked === true && w.form.querySelector('[data-key="menu-main"]').checked === false && w.state.hash === '#builder/@new/about' );
		check( 'F. the choice of the block to start with is not there - the page it is made from stands in its place - and no list of blocks is asked for', form.querySelector('select') === null && textOf( form ).indexOf( W( 'page/copy-of', 'About us' ) ) !== -1 && w.requests.length === 0 );
		check( 'F. what the server proposed is no input that is not saved: the view is left without a question, and a name that is changed is', ( function() {
			const clean = w.dirty.isDirty( [ 'builder' ] ) === false;
			w.type( 'name-de_DE', 'Anders' );
			return clean === true && w.dirty.isDirty( [ 'builder' ] ) === true;
		} )() );
		check( 'F. an address that the server proposed is kept when the name is written: it is not made over from the name', w.inputOf('address-de_DE').value === ORIGIN.addresses.de_DE );

		w.submit();

		const sent = w.requests[0];

		check( 'F. what is sent is the page it is made from and what is written - the names, the addresses and the menus - and no block', sent.action === 'builder/duplicate' && JSON.stringify( sent.payload ) === JSON.stringify( { names : { de_DE : 'Anders', en_US : ORIGIN.names.en_US }, addresses : ORIGIN.addresses, navs : [ 'footer' ], from : 'page-about' } ) );

		w.reply( 200, { template : 'page-about-2', slug : 'about-2', copied : { texts : 3, slots : 0, files : 0 } } );
		w.serve();

		check( 'F. the copy is opened as a page that was made: the toast, the shell told what was written and the address of the copy', w.said.length === 1 && w.state.hash === '#builder/about-2' && w.touched.length > 0 );
	}

	{
		const w = world();

		w.open('#builder/@new/about');
		w.serve();
		w.submit();
		w.reply( 409, { error : 'x', code : 'routes_http_uri_taken', params : [ '/about-2' ], field : 'httpUri-en_US' } );

		const errors = findAll( w.form, function( el ) { return el.className === 'nino-admin-field-error' } );

		check( 'F. what the server refuses is said at the field it names, as for a new page, and the view stays', errors.length === 1 && w.inputOf('address-en_US').attributes['aria-invalid'] === 'true' && w.shown( w.form ) === true && w.said.length === 0 );
	}

	{
		const w = world();

		w.open('#builder');
		w.state.hash = '#builder/@new/nothing';
		w.panel.showCurrent();
		w.serve( { copyof : { status : 404, body : { error : 'x', code : 'builder_unknown', params : [ 'page-nothing' ], field : 'template' } } } );

		check( 'F. a page the server does not know is no view: the list stands, with the refusal said above the table', w.state.hash === '#builder' && w.shown( w.list ) === true && w.shown( w.form ) === false && w.list.querySelector('.builder-list-error') !== null );
	}

	{
		const w = world();

		w.open('#builder/@new/about');
		w.serve();
		w.type( 'name-de_DE', 'Anders' );
		w.answers.push( 'cancel' );
		w.state.hash = '#builder/@new';
		w.panel.showCurrent();

		check( 'F. the view of another copy, or of a new page, is another view: what was typed is asked about first, and a turned-down question keeps the form and its address', w.dirty.asked === 1 && w.state.hash === '#builder/@new/about' && w.inputOf('name-de_DE').value === 'Anders' );
	}

	{
		const w = world( { list : answer( { create : false } ) } );

		w.open('#builder/@new/about');

		check( 'F. the account that may not make a page is taken to the list by the address of a copy as well, and nothing is asked', w.state.hash === '#builder' && w.shown( w.list ) === true && w.requests.length === 0 );
	}
}

// --- the frames in the list: a header and a footer that are given a block of their kind, without the editor of Themelab -----------------------------------------

{
	const frames = [ { key : 'header', template : 'frame-header', stamp : 'aa', last : 0, undo : false }, { key : 'footer', template : 'frame-footer', stamp : 'bb', last : 1763356920, undo : true } ];
	const w = world( { list : answer( { frames : frames, bridge : false } ) } );

	w.open('#builder');

	const buttons = function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-btn') !== -1 && el.className.indexOf('nino-admin-btn-icon') === -1 } ).map( textOf ) };
	const rows = findAll( w.list, function( el ) { return el.dataset !== undefined && ( el.dataset.row === 'header' || el.dataset.row === 'footer' ) } );

	check( 'G. the list of the pages has the two frames for the one who designs - a row each, "applies to all pages", with the choice of a block, the way back only where there is a last state, and nothing that names Themelab where Themelab is off',
		rows.length === 2 && buttons( rows[0] ).join() === W('frame/choose') && buttons( rows[1] ).join() === W('frame/choose')+ ','+ W('frame/undo') && textOf( rows[0] ).indexOf( W('frame/hint') ) !== -1 && buttons( w.list ).indexOf( W('frame/themelab') ) === -1 );

	const content = world( { list : answer( { frames : [] } ) } );

	content.open('#builder');

	const bridged = world( { list : answer( { frames : frames, bridge : true } ) } );

	bridged.open('#builder');

	check( 'G. the account that only writes the content gets no frames from the server and so no row of them; where Themelab is on and the account holds its right each row has "Edit in Themelab" as well',
		findAll( content.list, function( el ) { return el.dataset !== undefined && el.dataset.row === 'header' } ).length === 0 && buttons( bridged.list ).filter( function( text ) { return text === W('frame/themelab') } ).length === 2 );

	// the choice: the blocks of the frame are asked by its template, the dialog names each with where it comes from, the question is asked before anything is written - and the answer yes writes that block
	fire( findAll( rows[0], function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('frame/choose') } )[0], 'click' );

	const asked = w.requests.filter( function( entry ) { return entry.action === 'builder/framechoices' } )[0];

	const entry = function( key, source, kind, label, extra ) { return Object.assign( { key : key, name : key, description : '', category : 'frame', origin : { kind : kind, label : label }, own : kind === 'project', source : source, applied : false, off : null }, extra ?? {} ) };

	asked.callback( { status : 200, responseJSON : { blocks : [
		entry( 'soft', 'second', 'theme', 'Craft' ), entry( 'alike', 'other', 'theme', 'Craft' ), entry( 'basic', 'basis', 'basis', 'Basis' ),
		entry( 'applied', 'nino', 'theme', 'Nino', { applied : true } ), entry( 'mine', 'project', 'project', 'Project' ),
	], categories : [] }, getAllResponseHeaders : function() { return 'x' } } );

	const dialog = w.dialogs[w.dialogs.length - 1];
	const items = findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('nino-admin-pick-item') !== -1 } );
	const heads = findAll( dialog, function( el ) { return el.className === 'nino-admin-pick-group-title' } ).map( textOf );
	const tiles = w.requests.filter( function( entry ) { return entry.action === 'builder/frametile' } );

	check( 'G. the dialog of the choice has the blocks of the kind of the frame from every source under the name of their source - the own ones, the theme in use, then the others by name, two themes of one name told apart by their key - and asks the server by the template of the frame; nothing is written by asking',
		asked.payload.template === 'frame-header' && heads.join('|') === [ W('frame/own'), W( 'frame/theme', 'Nino' ), 'Basis', W( 'frame/theme-key', 'Craft', 'other' ), W( 'frame/theme-key', 'Craft', 'second' ) ].join('|')
		&& items.map( textOf ).join() === 'mine,applied,basic,alike,soft' && w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } ).length === 0 );

	// each item is a tile whose picture is the tile of the dialog of the blocks - asked for by the template of the frame and the key of the block - drawn in the face of the item, in the order of the items
	tiles.forEach( function( request ) { request.callback( { status : 200, responseJSON : { token : 't', query : 'nino-preview=tok-'+ request.payload.key, ttl : 600, address : '/home' }, getAllResponseHeaders : function() { return 'x' } } ) } );

	const faces = findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-pick-image') !== -1 } );
	const framed = faces.map( function( face ) { const frame = findAll( face, function( el ) { return el.tagName === 'IFRAME' } )[0]; return frame === undefined ? '' : String( frame.attributes.src ?? frame.src ).split('tok-')[1] } );

	check( 'G. the picture of each item is the tile that the dialog of the blocks draws, asked for once by the template of the frame and the key of the block, and it stands in the face of that item - the first face has the first item\'s block, the last the last\'s - with no other request than that',
		tiles.length === 5 && tiles.every( function( request ) { return request.payload.template === 'frame-header' } ) && framed.join() === 'mine,applied,basic,alike,soft' );

	fire( items[4], 'click' );
	fire( dialog, 'close' );

	const question = w.questions[w.questions.length - 1];

	check( 'G. the whole frame goes by the choice, so it is asked first - destructive, naming the frame and the block - and only the answer yes asks the server to write: the template, the key, the stamp of the frame the list saw and the language, and no tree, no words, no right',
		question !== undefined && question.level === 'destructive' && w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } ).length === 0 && ( question.options.onChoose( true ), w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } ).length === 1 )
		&& Object.keys( w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } )[0].payload ).sort().join() === 'base,key,locale,template' && w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } )[0].payload.base === 'aa' );

	// the frame was saved elsewhere meanwhile: the server says so with its own code - the list says it in words and is drawn again, and nothing is forced
	const chose = w.requests.filter( function( entry ) { return entry.action === 'builder/framechoose' } )[0];

	w.requests.length = 0;
	chose.callback( { status : 409, responseJSON : { error : 'x', code : 'builder_frame_changed' }, getAllResponseHeaders : function() { return 'x' } } );

	check( 'G. a frame that was changed since the list was drawn is not forced: the answer 409 with the code of the frame says so in words, the list is asked for again, and no request is made with force',
		w.said.indexOf( W('frame/changed') ) !== -1 && w.requests.filter( function( entry ) { return entry.action === 'builder/list' } ).length === 1 && w.requests.every( function( entry ) { return entry.payload.force === undefined } ) );
}

done();
