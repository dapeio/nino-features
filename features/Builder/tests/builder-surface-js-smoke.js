/**
 *	Nino features
 *	builder-surface-js-smoke.js	What the script of the panel draws of a page that is worked on, over the real Nino.adminUi of the workbench - the context bar, the
 *															split view with its outline, the tree, the tools of the preview and the bar are the kernel's own; the page in the frame and
 *															the marks over it are stood in for, as the frame of a browser is not here:
 *
 *															A. the surface: a context bar with the name of the page, its file and the tools, a split view that fills the height with the
 *															   outline at its left and no column of settings, the bar at the foot - and nothing of the panel's own over the page
 *															B. the outline: a row for each block with the icon of its type and its id, the frames as rows, what can be done to a row,
 *															   the extended view and the way back to the same view with the same block chosen
 *															C. what the views show of one another: the pointer or the focus over a row marks the block in the page and the other way
 *															   round, one block at a time, with the keys of the blocks
 *															D. the page in the frame: the bar of a block is there for the block that is on - pointer, focus or chosen - and nobody else,
 *															   the two ways of showing which block is on, the pencil at a component and who may use it
 *
 *															The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js
 *															of the checkout, three levels up or the one NINO_ROOT names. builder-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Builder/tests/builder-surface-js-smoke.js
 *	       NINO_ROOT=../nino node features/Builder/tests/builder-surface-js-smoke.js
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
const script = fs.readFileSync( scriptPath, 'utf8' );
const sheet = fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

/** The words of the panel by their keys: a test asks for the fill and never writes the sentence */
const W = function( key, ...params ) { return String( words.en['/_admin/builder/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

/** What an element and everything in it says */
const textOf = function( node ) { return String( node.textContent ?? '' )+ ( node.children ?? [] ).map( textOf ).join('') };

const LOCALES = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true } ];

/** The answer of the list: the pages and the two frames */
const answer = function( extra ) {

	return Object.assign( {
		pages : [ { template : 'page-home', name : 'Home', path : '/', locales : [ 'de_DE', 'en_US' ], navs : [], complete : true, blocks : 3, saved : 1763356920 } ],
		frames : [ { key : 'header', template : 'frame-header' }, { key : 'footer', template : 'frame-footer' } ],
		create : true, navs : [], native : 'de_DE', locales : LOCALES, level : 3, contentLocale : 'de_DE', dir : '',
	}, extra ?? {} );
};

/** The schema of a component as the server tells it */
const schema = function( label, icon, source, extra ) {
	return Object.assign( { label : label, group : '', icon : icon, container : source === 'none', loop : false, source : source, format : source === 'text' ? 'inline' : '', id : source === 'none' ? 'id' : '', attributes : {}, variants : [], variantLabels : {}, standard : '' }, extra ?? {} );
};

/** A block: a section with a title, three of them between the calls of the head and the foot of the site */
const block = function( id, extra ) {
	return { n : 'section-wrap', s : '', a : { id : id }, c : [ { t : '\n\t' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n' } ].concat( extra ?? [] ) };
};

/** A row of two columns, and a collection: what makes a block a block of columns, and a block of a loop */
const columns = [ { n : 'section-row', s : '', a : {}, c : [ { n : 'section-col', s : '', a : { id : 'c-1' }, c : [] }, { n : 'section-col', s : '', a : { id : 'c-2' }, c : [] } ] } ];
const loop = [ { n : 'collection', s : '/team', a : {}, c : [] } ];

/** The answer of a page that is opened: three blocks, a title in each, a collection in the third, and the schemas of an account that may design */
const pageAnswer = function( extra ) {

	return Object.assign( {
		template : 'page-home', frame : false,
		tree : [ { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' }, block( 'hero' ), { t : '\n' }, block( 'cards', columns ), { t : '\n' }, block( 'team', loop ), { t : '\n' }, { n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' } ],
		sources : [], slots : [], images : {}, stamp : 'a', address : '/', last : 0, unback : '', fresh : { de_DE : 'Neu', en_US : 'New' },
		texts : { '/template/page-home/hero/title' : { de_DE : 'Hallo', en_US : 'Hello' }, '/template/page-home/cards/title' : { de_DE : 'Karten', en_US : 'Cards' } },
		known : { 'section-wrap' : { label : 'Section', icon : 'panel-top', id : 'id', source : 'none' }, 'section-title' : { label : 'Title', icon : 'heading', id : '', source : 'text' }, 'section-row' : { label : 'Row', icon : 'rows-2', id : '', source : 'none' }, 'section-col' : { label : 'Column', icon : 'columns-3', id : 'id', source : 'none' }, collection : { label : 'Collection', icon : 'database', id : '', source : 'none' } },
		schemas : { 'section-wrap' : schema( 'Section', 'panel-top', 'none' ), 'section-title' : schema( 'Title', 'heading', 'text' ), collection : schema( 'Collection', 'database', 'none', { loop : true } ), 'section-row' : schema( 'Row', 'rows-2', 'none' ), 'section-col' : schema( 'Column', 'columns-3', 'none' ) },
		findings : {}, links : [], contentLocale : 'de_DE', native : 'de_DE', locales : LOCALES, dir : '', level : 3, dim : 'frame',
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

	options = Object.assign( { lang : 'en-US', list : answer(), page : pageAnswer() }, options ?? {} );

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

	const head = element('div');

	head.className = 'admin-panel-head';
	head.appendChild( title );
	page.appendChild( head );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.listeners = {};
	doc.addEventListener = function( name, fn ) { ( doc.listeners[name] = doc.listeners[name] ?? [] ).push( fn ) };
	doc.createElement = function( tag ) {
		const created = element( tag );
		created.scrollIntoView = function() {};
		created.getBoundingClientRect = function() { return { top : 0, left : 0, width : 800, height : 600, right : 800, bottom : 600 } };
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
		Nino : Nino, view : view, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, form : form, requests : requests, dialogs : dialogs, said : said, answers : answers, state : state, dirty : dirty,
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
				else if( request.action === 'builder/open' )
					w.reply( 200, options.page );
				else if( request.action === 'builder/preview' )
					w.reply( 200, { token : 'draft', query : 'nino-preview=draft', ttl : 600, sources : [], address : '/' } );
				else if( request.action === 'builder/notation' )
					w.reply( 200, { notation : { block : '', node : '' } } );
				else if( request.action === 'builder/types' )
					w.reply( 200, { types : [] } );
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


/** A page that is open: the name of the panel is the heading of its pane, as the shell has it */
const opened = function( options ) {

	const w = world( options );

	w.title.textContent = 'Builder';
	w.open('#builder/home');

	return w;
};

const bars = function( w ) { return w.view.children.map( function( child ) { return child.className.split(' ')[0] } ) };
const rowsOf = function( w ) { return findAll( w.view, function( el ) { return el.tagName === 'LI' && el.dataset.id !== undefined } ) };
const rowOf = function( w, id ) { return rowsOf( w ).find( function( el ) { return el.dataset.id === id } ) };
const buttonsOf = function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' && el.dataset.key !== undefined } ) };


// --- A. the surface ---------------------------------------------------------------------------------------------

{
	const w = opened();
	const bar = w.view.querySelector('.nino-admin-contextbar');
	const split = w.view.querySelector('.nino-admin-split');

	check( 'A. a page that is worked on is three blocks of the workbench - the context bar, the split view that fills the height, the bar at the foot - and nothing of the panel\'s own over or under them',
		bars( w ).join() === 'nino-admin-contextbar,nino-admin-split,nino-admin-actionbar' && split.classList.contains('nino-admin-split--fill') === true );
	check( 'A. the context bar has the name of the panel small over the way back, the name of the page with its file quietly behind it, and the tools of the preview', bar.dataset.panel === 'Builder' && bar.querySelector('.nino-admin-back-link') !== null && textOf( bar.querySelector('.nino-admin-back-link') ) === 'Back to '+ W('label/pages')
		&& textOf( bar.querySelector('.nino-admin-contextbar-title') ) === 'Home' && textOf( bar.querySelector('.nino-admin-contextbar-code') ) === 'page-home.tpl' && bar.querySelector('.nino-admin-previewtools') !== null && bar.querySelector('.nino-admin-previewtools select') === null );
	check( 'A. the split view has the outline at its left and no column of settings: two columns, the preview takes the rest', split.querySelector('.nino-admin-split-aside') !== null && split.querySelector('.nino-admin-split-settings') === null && split.dataset.bare === 'true' && split.querySelector('iframe') !== null );
	check( 'A. the title, the tools and the way back are the context bar\'s and the preview tools\' of the workbench, and the frame of the page has no height of its own', /Nino\.adminUi\.contextBar\(/.test( script ) === true && /Nino\.adminUi\.previewTools\(/.test( script ) === true && /clamp\(\s*28rem/.test( sheet ) === false );
	check( 'A. the icon beside the name opens the choice between the two frames of the site for whoever may design, and is not there for the one who may only write the content, nor in a frame', ( function() {
		const names = function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-label'] === W('label/frames-edit') } ).length };
		const poor = opened( { page : pageAnswer( { level : 1, schemas : undefined } ) } );
		const frame = world( { page : pageAnswer( { template : 'frame-header', frame : true, tree : [ { n : 'header-wrap', s : '', a : { id : 'header' }, c : [] } ], known : { 'header-wrap' : { label : 'Header', icon : 'panel-top', id : 'id', source : 'none' } } } ) } );

		frame.title.textContent = 'Builder';
		frame.open('#builder/@header');

		return names( bar ) === 1 && names( poor.view ) === 0 && poor.view.querySelector('.nino-admin-contextbar') !== null && frame.panel.model.state.frame === true && names( frame.view ) === 0 && frame.view.querySelector('.nino-admin-contextbar') !== null;
	} )() );
}

// --- B. the outline ---------------------------------------------------------------------------------------------

{
	const w = opened();
	const model = w.panel.model;
	const S = model.state;

	check( 'B. a row for each block in the order of the page, the calls of the head and the foot of the site as the first and the last, each with the icon of its type and the id of the block', ( function() {
		const items = model.outlineItems();
		return rowsOf( w ).map( function( row ) { return row.dataset.id } ).join() === '@header,hero,cards,team,@footer'
			&& items.map( function( item ) { return item.icon } ).join() === 'panel-top,heading,columns-3,repeat,panel-bottom' && items.map( function( item ) { return item.label } ).join() === W('word/frame-header')+ ',hero,cards,team,'+ W('word/frame-footer');
	} )() );
	check( 'B. blocks of different kinds have different icons - a loop, columns, a text, a picture, a table, free markup - and none has the icon of the head of the page', ( function() {
		const of = function( calls ) { return model.iconOf( block( 'x', calls ) ) };
		const call = function( n, a ) { return [ { n : n, s : '', a : a ?? {}, c : [] } ] };
		const icons = [ of( [] ), of( loop ), of( columns ), of( call( 'text' ) ), of( call( 'image' ) ), of( call( 'table-simple' ) ), of( call( 'html' ) ), of( call( 'button' ) ) ];

		return new Set( icons ).size === icons.length && icons.indexOf('panel-top') === -1 && model.iconOf( { n : 'section-wrap', s : '', a : { id : 'h', image : '~background' }, c : [] } ) === 'image';
	} )() );
	check( 'B. a template put in the middle of the page is no second head: the first call of a frame is the head, the last the foot, the one between has a row and an id of its own', ( function() {
		const saved = S.top;
		S.top = [ { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' }, { n : 'template', s : '/templates/partial-note', a : {}, c : null }, { t : '\n' }, { n : 'template', s : '/templates/html-footer', a : {}, c : null } ];
		const keys = [ 0, 2, 4 ].map( function( at ) { return model.keyAt( at ) } ).join();
		S.top = saved;
		return keys === '@header,@2,@footer';
	} )() );
	check( 'B. the word of the kind is not in a row - the id stands for the block, and the whole name is its hint', ( function() {
		const row = rowOf( w, 'hero' );
		return textOf( row.querySelector('.nino-admin-tree-label') ) === 'hero' && textOf( row ).indexOf( W('kind/section-wrap') ) === -1 && row.querySelector('.nino-admin-tree-row').title === 'Section · Hallo';
	} )() );
	check( 'B. what can be done to a block is at the end of its row - put one in behind it, up, down, duplicate, delete in red, the extended view -, what cannot be done is shut, and the rows of the frames can only have a block put in behind them', ( function() {
		const keys = function( id ) { return buttonsOf( rowOf( w, id ) ).map( function( button ) { return button.dataset.key } ).join() };
		const shut = function( id ) { return buttonsOf( rowOf( w, id ) ).filter( function( button ) { return button.disabled === true } ).map( function( button ) { return button.dataset.key } ).join() };
		const remove = buttonsOf( rowOf( w, 'cards' ) ).find( function( button ) { return button.dataset.key === 'remove' } );
		return keys( 'hero' ) === 'insert,up,down,copy,remove,advanced' && shut( 'hero' ) === 'up' && shut( 'team' ) === 'down' && shut( 'cards' ) === '' && keys( '@header' ) === 'insert' && keys( '@footer' ) === ''
			&& remove.classList.contains('nino-admin-tree-action--danger') === true && remove.attributes['aria-label'] === W('label/remove')+ ': Section \u00B7 Karten';
	} )() );
	check( 'B. the outline folds to a strip with the icon of each row, and a press on one chooses the block', ( function() {
		const strip = findAll( w.view, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('builder-rail-button') !== -1 } );
		fire( strip[2], 'click' );
		return strip.length === 5 && strip.map( function( button ) { return button.dataset.key } ).join() === '@header,hero,cards,team,@footer' && model.state.selected === 'cards';
	} )() );
}

{
	// The extended view and the way back: the block is the chosen one and the outline is as open as it was left
	const back = function( folded ) {

		const w = opened();
		const model = w.panel.model;
		const toggle = w.view.querySelector('.nino-admin-aside-toggle');

		if( folded === true )
			fire( toggle, 'click' );

		const before = w.view.querySelector('.nino-admin-split').dataset.aside;

		fire( buttonsOf( rowOf( w, 'cards' ) ).find( function( button ) { return button.dataset.key === 'advanced' } ), 'click' );
		w.serve();

		const there = w.state.hash;
		const split = w.view.querySelector('.nino-admin-split');
		const extended = split.querySelector('.nino-admin-split-aside') === null && split.querySelector('.nino-admin-split-settings') !== null;
		const link = w.view.querySelector('.nino-admin-back-link');

		model.state.selected = '';
		fire( link, 'click' );
		w.serve();

		const again = w.view.querySelector('.nino-admin-split');
		const chosen = findAll( w.view, function( el ) { return el.tagName === 'LI' && el.attributes['aria-selected'] === 'true' } ).map( function( row ) { return row.dataset.id } ).join();
		const pressed = findAll( w.view, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('builder-rail-button') !== -1 && el.attributes['aria-pressed'] === 'true' } ).map( function( button ) { return button.dataset.key } ).join();

		return there === '#builder/home/cards' && extended === true && textOf( link ) === 'Back to Home' && w.state.hash === '#builder/home' && again.dataset.aside === before && again.querySelector('.nino-admin-split-aside') !== null && chosen === 'cards' && pressed === 'cards' && model.state.selected === 'cards';
	};

	check( 'B. the extended view of a block is a surface of its own without the outline, its way back leads into the view it came from - the outline as open as it was left, the same block chosen - whether the outline was open or folded', back( false ) === true && back( true ) === true );
}

// --- C. what the views show of one another ----------------------------------------------------------------------

{
	const w = world();
	const seen = [];
	let marks = null;

	w.title.textContent = 'Builder';
	w.ui.frameMarks = function( options ) {
		marks = options;
		return { highlight : function( keys ) { seen.push( keys ) }, refresh : function() {}, stop : function() {} };
	};
	w.open('#builder/home');

	const model = w.panel.model;
	const over = function( id ) { rowOf( w, id ).dispatchEvent( { type : 'pointerover', bubbles : true } ) };
	const lit = function() { return rowsOf( w ).filter( function( row ) { return row.querySelector('.nino-admin-tree-row').classList.contains('is-lit') } ).map( function( row ) { return row.dataset.id } ).join() };
	const strip = function() { return findAll( w.view, function( el ) { return el.tagName === 'BUTTON' && el.classList.contains('is-lit') } ).map( function( button ) { return button.dataset.key } ).join() };

	check( 'C. a row of the outline that the pointer is over marks the same block in the page, and none when the pointer has gone', ( function() {
		over( 'team' );
		const first = seen[seen.length - 1];
		w.view.querySelector('.nino-admin-tree').dispatchEvent( { type : 'pointerleave', bubbles : false } );
		return first === 'team' && seen[seen.length - 1] === null;
	} )() );
	check( 'C. the block the pointer or the focus is over in the page marks its row in the outline - and its icon in the strip - and the pointer gone, none', ( function() {
		marks.onHover( 'cards' );
		const on = lit()+ '|'+ strip();
		marks.onHover( null );
		return on === 'cards|cards' && lit() === '' && strip() === '';
	} )() );
	check( 'C. the page tells the outline what it is over by the keys of the blocks - the id of each -, and the elements of a key are the elements of that block', ( function() {
		const inner = { id : 'inner' };
		const one = { contains : function( node ) { return node === one || node === inner } };
		const other = { contains : function( node ) { return node === other } };

		model.state.found = { blocks : [ { at : 2, key : 'hero', elements : [ one ] }, { at : 4, key : 'cards', elements : [ other ] } ], all : [] };

		return marks.elements('hero')[0] === one && marks.elements('cards')[0] === other && marks.elements('none').length === 0 && marks.keyAt( inner ) === 'hero' && marks.keyAt( other ) === 'cards' && marks.keyAt( {} ) === null;
	} )() );
}

// --- D. the page in the frame -----------------------------------------------------------------------------------

{
	const w = opened();
	const model = w.panel.model;
	const S = model.state;
	const on = function( key, hot, focus, selected ) {
		S.hot = hot;
		S.focus = focus;
		S.selected = selected;
		return model.lookOf( 'frame', key, model.activeOf() ).bar;
	};

	check( 'D. the bar of a block is there for the block the pointer is in, else the one the focus is in, else the chosen one - and for no other', on( 'hero', '', '', '' ) === false && on( 'hero', 'hero', '', '' ) === true && on( 'hero', '', 'hero', '' ) === true
		&& on( 'hero', '', '', 'hero' ) === true && on( 'cards', 'hero', '', '' ) === false && on( 'hero', 'cards', '', 'hero' ) === false && on( 'cards', 'cards', 'hero', 'hero' ) === true );
	check( 'D. the bar, the frame of a block and the places beside it are hidden unless the block is on, and the transition is none for a person who asks for none', /\.bar\{[^}]*visibility:hidden/.test( script ) === true && /\.frame\{[^}]*display:none/.test( script ) === true
		&& /\.bar\[data-on\]/.test( script ) === true && /prefers-reduced-motion:reduce/.test( script ) === true );
	check( 'D. the setting "frame" - the default - draws a frame of two pixels in the accent colour round the block that is on and leaves the others as they are; the setting "fade" draws the others at four fifths and no frame', ( function() {
		S.hot = 'cards';
		S.selected = '';
		const now = model.activeOf();
		const frame = [ 'hero', 'cards' ].map( function( key ) { return model.lookOf( 'frame', key, now ) } );
		const fade = [ 'hero', 'cards' ].map( function( key ) { return model.lookOf( 'fade', key, now ) } );
		const css = model.frameCss( { blue : 'blue', soft : 'soft' } );

		return JSON.stringify( frame ) === JSON.stringify( [ { bar : false, frame : false, dim : false }, { bar : true, frame : true, dim : false } ] )
			&& JSON.stringify( fade ) === JSON.stringify( [ { bar : false, frame : false, dim : true }, { bar : true, frame : false, dim : false } ] )
			&& /\[data-nino-dim\]\{opacity:0?\.8\}/.test( css ) === true && /\.frame\{[^}]*border:2px solid var\(--b-blue\)/.test( script ) === true && /\[data-nino-block\]\{transition:none\}/.test( css ) === true;
	} )() );
	check( 'D. the setting the server tells is the one the page shows: "fade" where it says so, "frame" otherwise', opened( { page : pageAnswer( { dim : 'fade' } ) } ).panel.model.state.dim === 'fade' && S.dim === 'frame' );
	check( 'D. the colours of the bars and of the frame are those of the workbench, read from its tokens - no colour is written into the script', /--editor-blue/.test( script ) === true && /--editor-red-ink/.test( script ) === true && /#[0-9a-fA-F]{3,8}\b/.test( script.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /^\s*\/\/.*$/gm, '' ).replace( /&#\d+;/g, '' ) ) === false );
	check( 'D. the button that loads the preview again draws the draft that is worked on, with the text that was written and not yet saved, and not the page that was last loaded', ( function() {
		const reload = findAll( w.view, function( el ) { return el.className.indexOf('nino-admin-previewtools-reload') !== -1 } )[0];
		const entry = { kind : 'text', key : '/template/page-home/hero/title', format : 'inline' };

		w.serve();
		model.settle( entry, '', 'Hallo X', 'Hallo' );
		w.requests.length = 0;
		fire( reload, 'click' );

		const asked = w.requests.filter( function( request ) { return request.action === 'builder/preview' } );

		return asked.length === 1 && asked[0].payload.texts['/template/page-home/hero/title'].de_DE === 'Hallo X' && S.stale === true;
	} )() );
	check( 'D. the way into the extended view remembers where the page in the frame was rolled to, and a field with a source or a loop is opened at its source', ( function() {
		S.view.split.frame.contentWindow = { scrollY : 412 };
		model.openExtended( 2 );
		const rolled = S.scroll;
		const text = model.penOf( '2.1', S.top[2].c[1] );
		const wrap = model.penOf( '4.1', { n : 'section-wrap', s : '', a : { id : 'x' }, c : [] } );
		const loop = model.penOf( '4.1', { n : 'collection', s : '/team', a : {}, c : [] } );

		return rolled === 412 && text.source === true && loop.source === true && wrap.source === false;
	} )() );
	check( 'D. the pencil at a component opens the lightbox of that component and no other view - the address stays where it is - for whoever may design, and for the one who may only write the content where the lightbox has a source to show or a text to write, and nowhere else', ( function() {
		const text = model.penOf( '2.1', S.top[2].c[1] );
		const wrap = model.penOf( '4.3', S.top[4].c[3] );
		const opener = element('button');
		const before = w.state.hash;
		const count = w.dialogs.length;

		text.open( opener );

		const opened = w.dialogs.length === count + 1 && S.box !== null && w.state.hash === before;

		S.box = null;
		S.level = 1;
		const own = model.penOf( '2.1', S.top[2].c[1] );
		const loopWrap = model.penOf( '4.3', S.top[4].c[3] );
		S.level = 3;

		return text.name === W( 'label/edit-component', 'Title' ) && wrap !== null && wrap.source === false && opened === true && own !== null && loopWrap === null;
	} )() );
}


// --- E. the lightbox -----------------------------------------------------------------------------------------------

{
	/** The page of the lightbox: a column with a width and a variant, a title that reads a field of the elements inside a collection, and a title of its own */
	const attributes = {
		'section-col' : schema( 'Column', 'columns-3', 'none', { variants : [ 'card' ], standard : 'regular', attributes : {
			id : { type : 'id', label : 'Id', hint : '', default : '' },
			size : { type : 'choice', label : 'Width', hint : 'How wide', default : '100', options : [ '25', '50', '100' ], optionLabels : { '25' : 'A quarter of the row', '50' : 'Half of the row', '100' : 'The whole row' }, responsive : false },
		} } ),
		'section-title' : schema( 'Title', 'heading', 'text', { attributes : { href : { type : 'link', label : 'Target', hint : '', default : '' }, tag : { type : 'choice', label : 'Tag', hint : 'Which heading', default : 'h2', options : [ 'h1', 'h2', 'h3' ], optionLabels : { h1 : 'Heading of the page', h2 : 'Heading of a part', h3 : 'Heading of a section' }, responsive : false } } } ),
		collection : schema( 'Collection', 'database', 'none', { loop : true } ),
	};
	const tree = [
		{ n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'team' }, c : [ { t : '\n' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n' }, { n : 'collection', s : '/team', a : {}, c : [ { t : '\n' }, { n : 'section-title', s : '.title', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' }, { n : 'section-col', s : '', a : { id : 'team-1', size : '50' }, c : [] }, { t : '\n' } ] }, { t : '\n' },
		{ n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' },
	];
	const world2 = function( level ) {

		const w = opened( { page : pageAnswer( { tree : tree, level : level, schemas : Object.assign( {}, pageAnswer().schemas, attributes ), texts : { '/template/page-home/team/title' : { de_DE : 'Team', en_US : 'Team' } } } ) } );
		const S = w.panel.model.state;

		S.hot = '';
		return { w : w, S : S, model : w.panel.model };
	};
	const heads = function( dialog ) { return findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && el.attributes['aria-controls'] !== undefined && el.className.indexOf('nino-admin-help-button') === -1 } ) };
	const names = function( dialog ) { return heads( dialog ).map( function( head ) { return textOf( head ).replace( /\s+/g, ' ' ).trim() } ) };
	const open = function( x, rel ) {

		const back = { called : 0 };
		const opener = element('button');

		x.model.state.box = null;
		x.model.openProperties( 2, rel, opener, function() { back.called++ } ) === true || ( back.refused = true );

		return { dialog : x.w.dialogs[x.w.dialogs.length - 1], back : back, opener : opener, close : function() { const dialog = x.w.dialogs[x.w.dialogs.length - 1]; fire( dialog, 'close' ) } };
	};

	check( 'E. the lightbox is one dialog of the workbench in the size of a form with the title "<kind> edit", and its sections stand in the fixed order - the source first, and open, where the content comes from one, then the content, how it looks, the advanced part - each only where it has something to show (the title of its own has no source, the column has neither source nor content)', ( function() {
		const x = world2( 3 );
		const field = open( x, '3.1' );
		const fieldHeads = names( field.dialog );
		const fieldOpen = heads( field.dialog ).map( function( head ) { return head.attributes['aria-expanded'] } ).join();
		field.close();
		const own = open( x, '1' );
		const ownHeads = names( own.dialog );
		own.close();
		const column = open( x, '5' );
		const columnHeads = names( column.dialog );
		const form = column.dialog.className.indexOf('nino-admin-dialog--form') !== -1;
		const title = textOf( column.dialog.querySelector('.nino-admin-dialog-title') );

		column.close();

		return fieldHeads.length === 4 && [ 'source', 'content', 'look', 'advanced' ].every( function( key, at ) { return fieldHeads[at].indexOf( W( 'box/'+ key ) ) === 0 } ) && fieldOpen === 'true,false,false,false'
			&& ownHeads.length === 3 && [ 'content', 'look', 'advanced' ].every( function( key, at ) { return ownHeads[at].indexOf( W( 'box/'+ key ) ) === 0 } )
			&& columnHeads.length === 2 && columnHeads[0].indexOf( W('box/look') ) === 0 && columnHeads[1].indexOf( W('box/advanced') ) === 0 && form === true && title === W( 'label/edit-component', 'Column' );
	} )() );

	check( 'E. a value that is changed in the lightbox is in the draft at once and is one step of the undo, the lightbox has nothing to apply - Close is its only button - and the undo takes the changes back one by one', ( function() {
		const x = world2( 3 );
		const box = open( x, '5' );
		const select = findAll( box.dialog, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === '5:size' } )[0];
		const variant = findAll( box.dialog, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === '5:variant' } )[0];
		const buttons = findAll( box.dialog.querySelector('.nino-admin-dialog-actions'), function( el ) { return el.tagName === 'BUTTON' } );
		const column = function() { return x.S.top[2].c[5].a };
		const steps = x.S.undo.length;

		select.value = '25';
		fire( select, 'change' );
		const sized = column().size === '25' && x.S.undo.length === steps + 1;
		variant.value = 'card';
		fire( variant, 'change' );
		const varied = column().variant === 'card' && x.S.undo.length === steps + 2;
		const open2 = x.w.dialogs[x.w.dialogs.length - 1] === box.dialog && x.S.box !== null;

		x.model.undo();
		const one = column().variant === undefined && column().size === '25';
		x.model.undo();
		const two = column().size === '50';

		box.close();

		return sized === true && varied === true && open2 === true && one === true && two === true && buttons.length === 1 && textOf( buttons[0] ) === workbench['/_admin/common/label/close'];
	} )() );

	check( 'E. the lightbox gives the focus back to what opened it when it is closed, and holds nothing of the component afterwards', ( function() {
		const x = world2( 3 );
		const box = open( x, '1' );

		box.close();

		return box.back.called === 1 && x.S.box === null;
	} )() );

	check( 'E. the account that may only write the content is given the source, to read, and the content - and no look, no advanced part: the lightbox of a column has nothing to show it and is not opened', ( function() {
		const x = world2( 1 );
		const field = open( x, '3.1' );
		const fieldHeads = names( field.dialog );
		const noSetting = findAll( field.dialog, function( el ) { return el.tagName === 'SELECT' || ( el.tagName === 'BUTTON' && /:source:/.test( el.dataset.key ?? '' ) ) } ).length === 0;
		field.close();
		const own = open( x, '1' );
		const ownHeads = names( own.dialog );
		own.close();
		const column = open( x, '5' );
		const refused = column.back.refused === true;
		x.S.box = null;

		return fieldHeads.length === 1 && fieldHeads[0].indexOf( W('box/source') ) === 0 && noSetting === true && ownHeads.length === 1 && ownHeads[0].indexOf( W('box/content') ) === 0 && refused === true;
	} )() );
}



// --- F. the tree of the extended view -------------------------------------------------------------------------------

{
	const marksSeen = [];
	let marks = null;
	const w = world( { page : pageAnswer( { schemas : Object.assign( {}, pageAnswer().schemas, { button : schema( 'Button', 'mouse-pointer-click', 'text', { attributes : { href : { type : 'link', label : 'Target', hint : '', default : '' } } } ) } ),
		tree : [ { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' }, { n : 'section-wrap', s : '', a : { id : 'team' }, c : [ { t : '\n' }, { n : 'collection', s : '/team', a : {}, c : [ { t : '\n' }, { n : 'section-title', s : '.title', a : {}, c : null }, { t : '\n' }, { n : 'button', s : '~label', a : { href : '.url' }, c : null }, { t : '\n' }, { n : 'section-title', s : '.teaser', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' } ] }, { t : '\n' } ] } ) } );
	const advanced = function() {

		w.title.textContent = 'Builder';
		w.ui.frameMarks = function( options ) {
			marks = options;
			return { highlight : function( keys, extra ) { marksSeen.push( [ keys, extra ] ) }, refresh : function() {}, stop : function() {} };
		};
		w.open('#builder/home/team');
		w.serve();
	};

	advanced();

	const model = w.panel.model;
	const S = model.state;
	const tree = function() { return w.view.querySelector('.nino-admin-tree') };
	const row = function( id ) { return rowsOf( w ).find( function( el ) { return el.dataset.id === id } ) };
	const lit = function() { return rowsOf( w ).filter( function( el ) { return el.querySelector('.nino-admin-tree-row').classList.contains('is-lit') } ).map( function( el ) { return el.dataset.id } ).join() };

	check( 'F. the tree is the tree of the workbench with the line of the path over it - the kinds from the block to the chosen row - and the extended view has no column of settings: the tree stands alone', ( function() {
		const split = w.view.querySelector('.nino-admin-split');

		return S.tree !== null && S.tree.path !== null && S.tree.path.classList.contains('builder-adv-path') === true && split.querySelector('.nino-admin-split-settings') !== null && split.querySelector('.nino-admin-accordion') === null && tree() !== null;
	} )() );
	check( 'F. a loop is the icon of a loop with its type and sort, its children stand once as the pattern, and a value that comes from a field - of the first word or of an attribute - is a tag with the whole name of the field', ( function() {
		const items = model.itemsNow()[0].children[0];
		const kids = items.children;

		return typeof items.loop === 'object' && kids.length === 3 && kids[0].source === '.title' && typeof kids[1].source === 'object' && kids[1].source.field === '.url' && kids[1].source.label === 'Target' && kids[2].source === '.teaser';
	} )() );
	check( 'F. a field that the type of the loop has not is a finding at its row, and the fields the type has are not', ( function() {
		S.fields['/team'] = [ 'title', 'url' ];
		const kids = model.itemsNow()[0].children[0].children;

		return kids[0].finding === undefined && kids[1].finding === undefined && kids[2].finding !== undefined && kids[2].finding.state === 'warn' && kids[2].finding.text.indexOf('teaser') !== -1;
	} )() );
	check( 'F. the note of a loop ends with the number of its elements, counted from the page in the frame, and has none where there is no page to count in', ( function() {
		const abs = model.absOf('1.1');
		const note = function() { return model.itemsNow()[0].children[0].loop.detail };

		S.found = null;
		const none = note();
		S.found = { blocks : [], all : [ { path : abs, elements : [] }, { path : abs, elements : [] }, { path : abs, elements : [] } ] };
		const three = note();
		S.found = { blocks : [], all : [ { path : abs, elements : [] } ] };
		const one = note();

		return none.indexOf( W('adv/elements', 3) ) === -1 && three.slice( -W('adv/elements', 3).length ) === W('adv/elements', 3) && one.slice( -W('adv/element').length ) === W('adv/element');
	} )() );
	check( 'F. a row marks the same component in the page, and the page marks its row: every repetition of a pattern of a loop is marked, a click on any of them is the one row, and the chosen component stays marked where nothing is pointed at', ( function() {
		const inner = { id : 'inner' };
		const one = { id : 'a', contains : function( node ) { return node === one || node === inner } };
		const two = { id : 'b', contains : function( node ) { return node === two } };
		const abs = model.absOf('1.1');

		S.found = { blocks : [], all : [ { path : abs, elements : [ one ] }, { path : abs, elements : [ two ] }, { path : model.absOf('1'), elements : [ one, two ] } ] };
		S.marked = 'root';
		marksSeen.length = 0;
		row('1.1').dispatchEvent( { type : 'pointerover', bubbles : true } );
		const toPage = marksSeen.length > 0 && marksSeen[marksSeen.length - 1][0] === '1.1';
		const elements = marks.elements('1.1');
		const key = marks.keyAt( inner );
		tree().dispatchEvent( { type : 'pointerleave', bubbles : false } );
		const backToChosen = marksSeen[marksSeen.length - 1][0] === 'root';
		marks.onHover('1.1');

		return toPage === true && elements.length === 2 && elements[0] === one && elements[1] === two && key === '1.1' && backToChosen === true && lit() === '1.1' && marks.keyAt( {} ) === null;
	} )() );
	check( 'F. Enter on a row opens the lightbox of its component, and Delete asks before the component is taken out - nothing is taken out by the key alone', ( function() {
		const count = w.dialogs.length;
		const before = JSON.stringify( S.top );

		row('1.1').dispatchEvent( { type : 'keydown', key : 'Enter', bubbles : true, target : row('1.1') } );
		const opened = w.dialogs.length === count + 1 && S.box !== null && S.box.rel === '1.1';
		fire( w.dialogs[w.dialogs.length - 1], 'close' );
		row('1.1').dispatchEvent( { type : 'keydown', key : 'Delete', bubbles : true, target : row('1.1') } );
		const asked = w.dialogs.length === count + 2 && JSON.stringify( S.top ) === before;

		return opened === true && asked === true;
	} )() );
}



// --- G. the pencil: reached by the pointer and by the keyboard --------------------------------------------------------

{
	const w = opened();
	const model = w.panel.model;
	const view = { top : 0, left : 0, width : 1000 };
	const inside = function( spot, box ) { return spot.top >= box.top && spot.top + 26 <= box.top + box.height && spot.left >= box.left && spot.left + 26 <= box.left + box.width };

	check( 'G. the pencil stands INSIDE the box of its component - in its top corner on the right, also where the component begins above the window or reaches past its right edge - so that the way from the component to it never leaves it; a loop has no box, its pencil touches the top of the repetitions', ( function() {
		const boxes = [ { top : 100, left : 50, width : 300, height : 80 }, { top : -200, left : 50, width : 300, height : 500 }, { top : 100, left : 900, width : 400, height : 80 } ];
		const spots = boxes.map( function( box ) { return model.penSpot( box, 26, view, false ) } );
		const placed = spots.every( function( spot, at ) { return inside( { top : spot.top + view.top, left : spot.left + view.left }, boxes[at] ) } );
		const loop = model.penSpot( { top : 300, left : 50, width : 300, height : 400 }, 26, view, true );

		return placed === true && spots[0].left + 26 + 2 === 350 && spots[1].top >= 2 && spots[2].left + 26 <= 1000 && loop.top + 26 === 300 && loop.left + 26 + 2 === 350;
	} )() );
	check( 'G. what the pointer is over decides the component of the pencil - except over the pencil itself, which is no part of the page: the pointer on it leaves the component as it is', ( function() {
		const before = { entry : 'a' };
		let asked = 0;
		const pick = function() { asked++; return { entry : 'b' }; };

		return model.pointerAim( before, true, pick ) === before && asked === 0 && model.pointerAim( before, false, pick ).entry === 'b' && asked === 1;
	} )() );
	check( 'G. every component that has a pencil is a stop of the Tab key and its pencil is the next one after it: a text and a picture are stops already, a column is given one - a loop has none - and the pencil stands after a text and as the first thing inside a column', ( function() {
		const has = function( names ) { const el = { attrs : {}, hasAttribute : function( name ) { return names.indexOf( name ) !== -1 } }; return el };
		const text = has( [ 'data-nino-text' ] );
		const picture = has( [ 'data-nino-image' ] );
		const column = has( [] );
		const entries = [ { path : '1.1', elements : [ column ], loop : false }, { path : '1.1.1', elements : [ text ], loop : false }, { path : '1.1.3', elements : [ picture ], loop : false }, { path : '1.5', elements : [ column ], loop : true } ];
		const found = { texts : [ { path : '1.1.1', element : text } ] };
		const stops = model.stopsOf( found, entries, function() { return true } );

		return stops.length === 1 && stops[0] === column && model.carrierOf( found, entries[1] ) === text && model.carrierOf( found, entries[2] ) === picture && model.carrierOf( found, entries[3] ) === null
			&& model.seatOf( text ) === 'after' && model.seatOf( picture ) === 'after' && model.seatOf( column ) === 'inside' && model.stopsOf( found, entries, function() { return false } ).length === 0;
	} )() );
}

// --- H. the lightbox: what each step is shown, and what it keeps -------------------------------------------------------

{
	const html = { n : 'html', s : '', a : {}, c : [ { t : '<b>x</b>' } ] };
	const schemas = {
		'section-title' : schema( 'Title', 'heading', 'text', { attributes : {
			tip : { type : 'text', label : 'Tip', hint : '', default : '' },
			href : { type : 'link', label : 'Target', hint : '', default : '' },
			alt : { type : 'text', label : 'Alt', hint : '', default : '' },
		} } ),
		html : schema( 'HTML', 'code', 'none' ),
	};
	const tree = [
		{ n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'x' }, c : [ { t : '\n' }, { n : 'section-title', s : '~title', a : { tip : '~tip', href : '~target', alt : '~alt' }, c : null }, { t : '\n' }, { n : 'section-title', s : '~fixed', a : { text : 'Fixed' }, c : null }, { t : '\n' }, html, { t : '\n' } ] }, { t : '\n' },
		{ n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' },
	];
	const texts = { '/template/page-home/x/title' : { de_DE : 'T' }, '/template/page-home/x/tip' : { de_DE : 'Tip' }, '/template/page-home/x/target' : { de_DE : '/a' }, '/template/page-home/x/alt' : { de_DE : 'Alt' } };
	const ofLevel = function( level ) {

		const w = opened( { page : pageAnswer( { tree : tree, level : level, texts : texts, schemas : Object.assign( {}, pageAnswer().schemas, schemas ) } ) } );

		return { w : w, model : w.panel.model, S : w.panel.model.state };
	};
	const show = function( x, rel, one ) {

		x.S.box = null;
		x.model.openProperties( 2, rel, element('button'), function() {} );

		const dialog = x.w.dialogs[x.w.dialogs.length - 1];
		const heads = findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && el.attributes['aria-controls'] !== undefined && el.className.indexOf('nino-admin-help-button') === -1 } );

		// Every section is opened, so that what each holds is there to be looked at
		if( one !== true )
			heads.forEach( function( head ) { fire( head, 'click' ) } );

		return { dialog : dialog, keys : findAll( dialog, function( el ) { return el.dataset !== undefined && el.dataset.key !== undefined } ).map( function( el ) { return el.dataset.key } ), controls : findAll( dialog, function( el ) { return /^(INPUT|TEXTAREA|SELECT)$/.test( el.tagName ) && el.type !== 'file' } ).length, close : function() { fire( dialog, 'close' ) } };
	};

	check( 'H. the account that may only write the content is given the words of the page and the alt of a picture as fields, and no other: no button that changes a source, no field for a fixed text, for the text of a tip or of a target, and no markup', ( function() {
		const x = ofLevel( 1 );
		const title = show( x, '1' );
		title.close();
		const fixed = x.model.penOf( '2.3', x.S.top[2].c[3] );
		const markup = x.model.penOf( '2.5', x.S.top[2].c[5] );

		return title.keys.some( function( key ) { return /:source:/.test( key ) } ) === false && title.keys.indexOf('1:content') !== -1 && title.keys.indexOf('1:alt') !== -1 && title.keys.indexOf('1:tip') === -1 && title.keys.indexOf('1:href') === -1
			&& title.controls === 2 && fixed === null && markup === null;
	} )() );
	check( 'H. the account that may design gets the tip, the target and the fixed text as fields and the buttons that change a source, and the markup of a call of html only from the step of the development', ( function() {
		const two = ofLevel( 2 );
		const title = show( two, '1' );
		title.close();
		const fixed = show( two, '3' );
		fixed.close();
		const noMarkup = show( two, '5' );
		noMarkup.close();
		const three = ofLevel( 3 );
		const markup = show( three, '5' );
		markup.close();

		return title.keys.some( function( key ) { return /:source:/.test( key ) } ) === true && title.keys.indexOf('1:tip') !== -1 && fixed.keys.indexOf('3:content') !== -1
			&& noMarkup.keys.indexOf('5:content') === -1 && markup.keys.indexOf('5:content') !== -1;
	} )() );
	check( 'H. what the lightbox shows is drawn again by an undo and a redo - the value that was taken back is the one in the field - and the pencil is given back the focus when the lightbox is closed', ( function() {
		const x = ofLevel( 3 );
		const box = show( x, '1', true );
		const field = function() { return findAll( box.dialog, function( el ) { return el.dataset !== undefined && el.dataset.key === '1:content' } )[0] };
		const before = field().value;

		field().value = 'Changed';
		fire( field(), 'change' );
		x.model.undo();
		const back = field().value === before;
		x.model.redo();
		const again = field().value === 'Changed';
		const opener = element('button');
		let focused = '';

		box.close();
		x.S.penBack = '';
		x.S.box = null;
		const pen = x.model.penOf( '2.1', x.S.top[2].c[1] );

		pen.open( opener );
		fire( x.w.dialogs[x.w.dialogs.length - 1], 'close' );
		focused = x.S.penBack;

		return back === true && again === true && focused === '2.1';
	} )() );

	check( 'H. a text of another place is told with its words and where it is changed - the words as the server sends them, the sentence behind the (?) - and the loop is told by the number of its elements, which the page in the frame shows and nothing else tells', ( function() {
		const shared = [ { n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' }, { n : 'section-wrap', s : '', a : { id : 'x' }, c : [ { t : '\n' }, { n : 'section-title', s : '/project/company/general/name', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' }, { n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' } ];
		const x = { w : null };
		const w = opened( { page : pageAnswer( { tree : shared, level : 1, shared : { '/project/company/general/name' : 'Beispiel GmbH' }, schemas : Object.assign( {}, pageAnswer().schemas, schemas ) } ) } );
		const model = w.panel.model;

		model.state.box = null;
		model.openProperties( 2, '1', element('button'), function() {} );

		const dialog = w.dialogs[w.dialogs.length - 1];
		const said = textOf( dialog );
		const helps = findAll( dialog, function( el ) { return el.className.indexOf('nino-admin-help-text') !== -1 } ).map( textOf ).join();

		fire( dialog, 'close' );

		return x.w === null && said.indexOf('Beispiel GmbH') !== -1 && helps.indexOf( W('word/not-here') ) !== -1;
	} )() );
	check( 'H. a lightbox whose component is taken away closes, and the focus goes to the work surface - never to the body of the page - and the notation of the component before is not shown in the next', ( function() {
		const x = ofLevel( 3 );
		const box = show( x, '5' );

		x.S.notation = { node : '[old]' };
		x.model.removeComponent('5');
		x.model.redrawBox();
		fire( box.dialog, 'close' );

		const landed = x.S.view.title.focused === true;
		const next = show( x, '1' );
		const notation = findAll( next.dialog, function( el ) { return el.tagName === 'PRE' } )[0];

		next.close();

		return x.S.box === null && landed === true && ( notation === undefined || textOf( notation ) === '' );
	} )() );
}


done();
