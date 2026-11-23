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
const { check, done, fills, load, element, findAll, fire, documentOf, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

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
		schemas : { 'section-wrap' : schema( 'Section', 'panel-top', 'none', { side : 'page' } ), 'section-title' : schema( 'Title', 'heading', 'text' ), collection : schema( 'Collection', 'database', 'none', { loop : true } ), 'section-row' : schema( 'Row', 'rows-2', 'none' ), 'section-col' : schema( 'Column', 'columns-3', 'none' ), html : schema( 'HTML', 'code', 'none', { markup : true, container : false } ) },
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

	// The router of the workbench itself, over the panes of the panel: what the panel writes is read back by it
	const codec = shellRouter( { builder : [ [ 'builder', 'builder' ], [ 'builder-frames', 'frames' ], [ 'builder-settings', 'settings' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		go : function( panel, parts ) { const hash = codec.check( panel, parts ); if( hash !== state.hash ) { state.hash = hash; state.pushed.push( hash ) } },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		leave : function( names, leaving, proceed, resync ) { leaving === true && dirty.isDirty( names ) === true ? dirty.guard( names, proceed, resync ) : proceed() },
	} );

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
		Nino : Nino, served : options.page, view : view, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, form : form, requests : requests, dialogs : dialogs, said : said, answers : answers, state : state, codec : codec, dirty : dirty,
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
	w.open('#builder/builder/home');

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
	check( 'A. the page has no icon for the frames and no extended view: the frames are written in the editor of the blocks, and a frame is no view of this panel for anybody', ( function() {
		const names = function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' && ( el.id === 'builder-frames' || el.dataset.key === 'advanced' ) } ).length };
		const poor = opened( { page : pageAnswer( { level : 1, schemas : undefined } ) } );

		return names( bar ) === 0 && names( poor.view ) === 0 && /openExtended|openFrames|(?<!#)builder-frames(?!-list|')/.test( script ) === false && /Nino\.admin\.builder\.go\( *\[[^\]]*@header/.test( script ) === false;
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
	check( 'B. the root of a header and of a footer is told by the side its schema says - the icon of the top and of the bottom of the page, and no words of the block beside its kind - and a component with the name of a root and no side is none',
		( function() {
			const S = model.state;
			const was = S.schemas;
			S.schemas = Object.assign( {}, was, { 'probe-head' : schema( 'Head', 'x', 'none', { side : 'header' } ), 'probe-foot' : schema( 'Foot', 'x', 'none', { side : 'footer' } ), 'header-wrap' : schema( 'Header', 'x', 'none' ) } );
			const answer = [ model.iconOf( { n : 'probe-head', s : '', a : { id : 'h' }, c : [] } ), model.iconOf( { n : 'probe-foot', s : '', a : { id : 'f' }, c : [] } ), model.iconOf( { n : 'header-wrap', s : '', a : { id : 'h' }, c : [] } ) ];
			S.schemas = was;
			return answer[0] === 'panel-top' && answer[1] === 'panel-bottom' && answer[2] !== 'panel-top' && answer[2] !== 'panel-bottom';
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
	check( 'B. what can be done to a block is at the end of its row - put one in behind it, up, down, duplicate, delete in red -, what cannot be done is shut, and the rows of the frames can have a block put in behind them, their texts opened and - for the one who designs - the frame itself edited in the tab of the frames', ( function() {
		const keys = function( id ) { return buttonsOf( rowOf( w, id ) ).map( function( button ) { return button.dataset.key } ).join() };
		const shut = function( id ) { return buttonsOf( rowOf( w, id ) ).filter( function( button ) { return button.disabled === true } ).map( function( button ) { return button.dataset.key } ).join() };
		const remove = buttonsOf( rowOf( w, 'cards' ) ).find( function( button ) { return button.dataset.key === 'remove' } );
		return keys( 'hero' ) === 'insert,up,down,copy,remove' && shut( 'hero' ) === 'up' && shut( 'team' ) === 'down' && shut( 'cards' ) === '' && keys( '@header' ) === 'insert,texts,frame-edit' && keys( '@footer' ) === 'texts,frame-edit'
			&& remove.classList.contains('nino-admin-tree-action--danger') === true && remove.attributes['aria-label'] === W('label/remove')+ ': Section \u00B7 Karten';
	} )() );
	check( 'B. the outline folds to a strip with the icon of each row, and a press on one chooses the block', ( function() {
		const strip = findAll( w.view, function( el ) { return el.tagName === 'BUTTON' && el.className.indexOf('builder-rail-button') !== -1 } );
		fire( strip[2], 'click' );
		return strip.length === 5 && strip.map( function( button ) { return button.dataset.key } ).join() === '@header,hero,cards,team,@footer' && model.state.selected === 'cards';
	} )() );
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
	w.open('#builder/builder/home');

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
	check( 'F290. the page of the Builder asks for no frame round what is under the pointer: its blocks have their own marks', marks.hover === undefined );
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
	check( 'D. a field with a source or a loop is opened at its source by its pencil, and a section is not', ( function() {
		const text = model.penOf( '2.1', S.top[2].c[1] );
		const wrap = model.penOf( '4.1', { n : 'section-wrap', s : '', a : { id : 'x' }, c : [] } );
		const loop = model.penOf( '4.1', { n : 'collection', s : '/team', a : {}, c : [] } );

		return text.source === true && loop.source === true && wrap.source === false;
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
	const bodies = function( dialog ) { return findAll( dialog, function( el ) { return String( el.className ).split(' ').indexOf('builder-box-body') !== -1 } ).length };
	const names = function( dialog ) { return heads( dialog ).map( function( head ) { return textOf( head ).replace( /\s+/g, ' ' ).trim() } ) };
	const open = function( x, rel ) {

		const back = { called : 0 };
		const opener = element('button');

		x.model.state.box = null;
		x.model.openProperties( 2, rel, opener, function() { back.called++ } ) === true || ( back.refused = true );

		return { dialog : x.w.dialogs[x.w.dialogs.length - 1], back : back, opener : opener, close : function() { const dialog = x.w.dialogs[x.w.dialogs.length - 1]; fire( dialog, 'close' ) } };
	};

	check( 'E. the lightbox is one dialog of the workbench in the size of a form with the title "<kind> edit", and its sections stand in the fixed order - the source first, and open, where the content comes from one, then the content, how it looks, the advanced part - each only where it has something to show (the title of its own has no source, the column has neither source nor content, and none of them has an advanced part: the id of a component below the section is no row; the column has the look alone, open, with no head)', ( function() {
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
		const columnBodies = bodies( column.dialog );
		const form = column.dialog.className.indexOf('nino-admin-dialog--form') !== -1;
		const title = textOf( column.dialog.querySelector('.nino-admin-dialog-title') );

		column.close();

		return fieldHeads.length === 3 && [ 'source', 'content', 'look' ].every( function( key, at ) { return fieldHeads[at].indexOf( W( 'box/'+ key ) ) === 0 } ) && fieldOpen === 'true,false,false'
			&& ownHeads.length === 2 && [ 'content', 'look' ].every( function( key, at ) { return ownHeads[at].indexOf( W( 'box/'+ key ) ) === 0 } )
			&& columnHeads.length === 0 && columnBodies === 1 && form === true && title === W( 'label/edit-component', 'Column' );
	} )() );

	check( 'F294 T5. the advanced part is where an anchor is read - the id of a section of the page - and nowhere else: a component below it has no "Id" row and no part; the section has the id as text, in the one flat list of its dialog, and a button to rename it - and a section with no id and nothing else says there is nothing to set',
		( function() {
			const w = opened( { page : pageAnswer( { tree : tree, level : 3, schemas : Object.assign( {}, pageAnswer().schemas, attributes, { 'section-wrap' : schema( 'Section', 'rows', 'none', { side : 'page', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' } } } ) } ) } ) } );
			const x = { w : w, S : w.panel.model.state, model : w.panel.model };

			x.S.hot = '';

			const below = open( x, '3.1' );
			const belowText = textOf( below.dialog );
			const belowHeads = names( below.dialog ).join('|');
			const belowId = findAll( below.dialog, function( el ) { return String( el.className ).indexOf('builder-id-text') !== -1 } ).length;
			below.close();

			const column = open( x, '5' );
			const columnHeads = names( column.dialog ).join('|');
			column.close();

			const section = open( x, 'root' );
			const sectionHeads = names( section.dialog );
			const texts = findAll( section.dialog, function( el ) { return el.tagName === 'CODE' && String( el.className ).indexOf('builder-id-text') !== -1 } );
			const rename = findAll( section.dialog, function( el ) { return el.dataset !== undefined && el.dataset.key === 'root:id-rename' } ).length;
			const inputs = findAll( section.dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key !== undefined && /:id$/.test( el.dataset.key ) } ).length;

			section.close();

			const poor = open( world2( 3 ), 'root' );
			const none = textOf( poor.dialog ).indexOf( W('box/none') ) !== -1 && heads( poor.dialog ).length === 0;

			poor.close();

			return belowHeads.indexOf( W('box/advanced') ) === -1 && belowText.indexOf( W('field/read-id') ) === -1 && belowId === 0 && columnHeads.indexOf( W('box/advanced') ) === -1
				&& sectionHeads.length === 0 && texts.length === 1 && textOf( texts[0] ) === 'team' && rename === 1 && inputs === 0 && none === true;
		} )() );

	check( 'E. a value that is changed in the lightbox is in the draft at once and is one step of the undo, the lightbox has nothing to apply - Close is its only button - and the undo takes the changes back one by one', ( function() {
		const x = world2( 3 );
		const box = open( x, '5' );
		const select = findAll( box.dialog, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === '5:size' } )[0];
		const variant = findAll( box.dialog, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === '5:variant' } )[0];
		const buttons = findAll( box.dialog.querySelector('.nino-admin-dialog-actions'),																																																																																			function( el ) { return el.tagName === 'BUTTON' && /nino-admin-btn-(?:primary|secondary|danger)/.test( el.className ) } );
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

	check( 'E. the account that may only write the content is given the source, to read, or the content - one section each, open, with no head - and no look, no advanced part: the lightbox of a column has nothing to show it and is not opened', ( function() {
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

		return fieldHeads.length === 0 && bodies( field.dialog ) === 1 && noSetting === true && ownHeads.length === 0 && bodies( own.dialog ) === 1 && refused === true;
	} )() );
}



// --- the fixes of the owner's feedback: the work surface and the lightbox ---------------------------------------------------------------

{
	/** A column and a row with settings of every kind: one with a value for each width alone, two of them, the strips that are pictures, a strip that is none, and a word */
	const attrs = {
		'section-col' : schema( 'Column', 'columns-3', 'none', { variants : [], standard : 'regular', attributes : {
			id : { type : 'id', label : 'Id', hint : '', default : '' },
			size : { type : 'choice', label : 'Width', hint : '', default : '100', options : [ '25', '50', '100' ], optionLabels : null, responsive : true },
			align : { type : 'choice', label : 'Align', hint : '', default : 'start', options : [ 'start', 'center', 'end' ], optionLabels : { start : 'Start', center : 'Centre', end : 'End' }, responsive : true },
			hide : { type : 'choice', label : 'Hide', hint : '', default : 'off', options : [ 'off', 'on' ], optionLabels : { off : 'Off', on : 'On' }, responsive : true },
		} } ),
		'section-row' : schema( 'Row', 'rows-2', 'none', { variants : [], standard : 'regular', attributes : {
			id : { type : 'id', label : 'Id', hint : '', default : '' },
			reverse : { type : 'choice', label : 'Reverse', hint : '', default : 'off', options : [ 'off', 'on' ], optionLabels : { off : 'Off', on : 'On' }, responsive : true },
			halign : { type : 'choice', label : 'Across', hint : '', default : 'start', options : [ 'start', 'center', 'end', 'between' ], optionLabels : { start : 'Start', center : 'Centre', end : 'End', between : 'Between' }, responsive : false },
			tone : { type : 'choice', label : 'Tone', hint : '', default : 'a', options : [ 'a', 'b', 'c' ], optionLabels : { a : 'A', b : 'B', c : 'C' }, responsive : false },
		} } ),
		'section-title' : schema( 'Title', 'heading', 'text', { attributes : { href : { type : 'link', label : 'Target', hint : '', default : '' } } } ),
		'button' : schema( 'Button', 'mouse-pointer', 'text', { attributes : { href : { type : 'link', label : 'Link', hint : '', default : '' }, target : { type : 'choice', label : 'Open in', hint : '', default : 'self', options : [ 'self', 'blank' ], optionLabels : { self : 'Same window', blank : 'New window' }, responsive : false }, tone : { type : 'choice', label : 'Tone', hint : '', default : 'primary', options : [ 'primary', 'quiet' ], optionLabels : { primary : 'Main', quiet : 'Quiet' }, responsive : false } } } ),
	};
	const page = [
		{ n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'team' }, c : [ { t : '\n' }, { n : 'section-title', s : '~title', a : {}, c : null }, { t : '\n' }, { n : 'section-row', s : '', a : {}, c : [ { t : '\n' }, { n : 'section-col', s : '', a : { id : 'one' }, c : [] }, { t : '\n' } ] }, { t : '\n' } ] }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'more' }, c : [ { t : '\n' }, { n : 'button', s : '~button', a : { href : 'page:/home' }, c : null }, { t : '\n' } ] }, { t : '\n' },
		{ n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' },
	];
	const make = function( level ) {

		const w = opened( { page : pageAnswer( { tree : page, level : level, schemas : Object.assign( {}, pageAnswer().schemas, attrs ), texts : { '/template/page-home/team/title' : { de_DE : 'Team', en_US : 'Team' } } } ) } );
		const S = w.panel.model.state;

		S.hot = '';

		return { w : w, S : S, model : w.panel.model };
	};
	const lightbox = function( x, rel ) {

		x.model.state.box = null;
		x.model.openProperties( 2, rel, element('button'), function() {} );

		return { dialog : x.w.dialogs[x.w.dialogs.length - 1], close : function() { fire( x.w.dialogs[x.w.dialogs.length - 1], 'close' ) } };
	};
	const withClass = function( node, name ) { return findAll( node, function( el ) { return String( el.className ).split(' ').indexOf( name ) !== -1 } ) };
	const picture = function( node ) { return findAll( node, function( el ) { return String( el.tagName ).toLowerCase() === 'svg' } ).length };

	check( 'I. 3: the outline is folded when a page is opened for the first time - the strip of the icons of the types is there and the body is not - and what a person chooses stays', ( function() {
		const x = make( 3 );
		const aside = x.S.view.split.aside;

		return aside !== null && aside.collapsed() === true && /aside\s*:[^}]*collapsed\s*:\s*true/.test( script ) === true;
	} )() );

	check( 'I. 6: the page keeps the lightbox for the properties of a component: its adapter does not ask for the column, the properties open in a dialog of the workbench, and no column of properties is drawn in the page - the sheet of a block is the one that says where they open', ( function() {
		const x = make( 3 );
		const before = x.w.dialogs.length;
		const row = lightbox( x, '3' );
		const made = x.w.dialogs.length === before + 1 && row.dialog !== undefined && String( row.dialog.className ).indexOf('builder-lightbox') !== -1;
		const none = findAll( x.w.view, function( el ) { return String( el.className ).split(' ').indexOf('builder-props') !== -1 } ).length === 0 && x.S.box.panel === false;

		row.close();

		return made === true && none === true && x.S.adapter.props === undefined;
	} )() );

	check( 'I. 7: the width of the preview is chosen by the devices - the phone, the tablet, the desktop - each a picture with its word for the tooltip and for a screen reader', ( function() {
		const x = make( 3 );
		const steps = findAll( x.w.view, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('nino-admin-steps-step') !== -1 && el.attributes.role === 'radio' } );
		const widths = steps.filter( function( step ) { return [ 'Mobile', 'Tablet', 'Desktop' ].indexOf( textOf( step ).trim() ) !== -1 } );

		return widths.length === 3 && widths.every( function( step ) { return picture( step ) === 1 && step.title === textOf( step ).trim() && withClass( step, 'nino-admin-sr-only' ).length === 1 } );
	} )() );

	check( 'I. 12: a strip whose values the builder has a picture for - the alignments, the direction - is a strip of pictures, each named by its word; a value or an attribute it has none for stays a word; the table is ONE place, by the name of the attribute and the value', ( function() {
		const x = make( 3 );
		const row = lightbox( x, '3' );
		const strip = function( key ) { return findAll( row.dialog, function( el ) { return el.dataset !== undefined && el.dataset.stepKey === key } )[0] };
		const across = strip( '3:halign' );
		const tone = strip( '3:tone' );
		const model = x.model;

		row.close();

		const named = across === undefined ? [] : withClass( across, 'nino-admin-sr-only' ).map( function( word ) { return textOf( word ).trim() } ).filter( function( word ) { return word.indexOf('(') !== 0 } );

		return across !== undefined && picture( across ) === 4 && named.join() === 'Start,Centre,End,Between'
			&& tone !== undefined && picture( tone ) === 0 && textOf( tone ).indexOf('A') !== -1
			&& model.glyphOf( 'halign', 'between' ) === 'align-horizontal-space-between' && model.glyphOf( 'align', 'end' ) === 'align-right' && model.glyphOf( 'reverse', 'on' ) === 'arrow-left' && model.glyphOf( 'hide', 'on' ) === 'eye-off'
			&& model.glyphOf( 'size', '50' ) === null && model.glyphOf( 'halign', 'sideways' ) === null && model.glyphOf( 'nothing', 'start' ) === null;
	} )() );

	check( 'I. 13: a setting with a value for each width that is alone in its section has the three devices as the heads of three columns and a choice under each; each choice writes its part of the value and is one step of the undo', ( function() {
		const x = make( 3 );
		const row = lightbox( x, '3' );
		const heads = withClass( row.dialog, 'builder-device' );
		const selects = findAll( row.dialog, function( el ) { return el.tagName === 'INPUT' && /^3:reverse:/.test( el.dataset.key ?? '' ) } );
		const steps = x.S.undo.length;

		selects[1].checked = true;
		fire( selects[1], 'change' );

		const written = x.S.top[2].c[3].a.reverse === 'off m:on' && x.S.undo.length === steps + 1;

		x.model.undo();

		const back = x.S.top[2].c[3].a.reverse === undefined && x.S.undo.length === steps;

		row.close();

		return heads.length === 3 && picture( row.dialog ) >= 3 && selects.map( function( el ) { return el.dataset.key } ).join() === '3:reverse:,3:reverse:m,3:reverse:l' && withClass( row.dialog, 'builder-widths-group' ).length === 0 && written === true && back === true;
	} )() );

	check( 'I. 13: several settings with a value for each width in one section have ONE choice of the width - the devices - and one choice for each setting for that width; the dot at a device says that something is written for it, the choice of the width is the width of the preview, and a change is one step of the undo', ( function() {
		const x = make( 3 );

		x.S.width = 'tablet';

		const col = lightbox( x, '3.1' );
		const picker = withClass( col.dialog, 'builder-width-pick' );
		const radios = findAll( picker[0], function( el ) { return el.tagName === 'BUTTON' && el.attributes.role === 'radio' } );
		const keys = function() { return findAll( col.dialog, function( el ) { return ( el.tagName === 'SELECT' && /^3\.1:(?:size|align):/.test( el.dataset.key ?? '' ) ) || /^3\.1:(?:size|align):/.test( el.dataset.stepKey ?? '' ) } ).map( function( el ) { return el.dataset.key ?? el.dataset.stepKey } ).join() };
		const atTablet = keys();
		const steps = x.S.undo.length;
		const sized = findAll( col.dialog, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === '3.1:size:m' } )[0];

		sized.value = '50';
		fire( sized, 'change' );

		const dots = function() { return withClass( col.dialog, 'builder-device-dot' ).map( function( dot ) { return dot.hidden === true ? '0' : '1' } ).join('') };
		const written = x.S.top[2].c[3].c[1].a.size === '100 m:50' && x.S.undo.length === steps + 1;
		const shown = dots();

		fire( radios[2], 'click' );

		const atDesktop = keys();
		const coupled = x.S.width === 'desktop';

		x.model.undo();
		col.close();

		return picker.length === 1 && radios.length === 3 && picture( picker[0] ) === 3 && atTablet === '3.1:size:m,3.1:align:m' && written === true && shown === '010' && atDesktop === '3.1:size:l,3.1:align:l' && coupled === true && x.S.top[2].c[3].c[1].a.size === undefined;
	} )() );

	check( 'I. 14: in a column the alignment is a strip of pictures for each width - with a first step that gives the width the value of the one below - and hiding is ONE row with three small switches, one under each device: a width that writes nothing is quiet (dimmed) and its row has no sentence - what it inherits is its name and tooltip -, a width that writes a value is drawn full, its device has a dot, and a small icon button gives it back to the one below',
		( function() {
		const x = make( 3 );

		x.S.width = 'tablet';

		const col = lightbox( x, '3.1' );
		const strip = findAll( col.dialog, function( el ) { return el.dataset !== undefined && el.dataset.stepKey === '3.1:align:m' } )[0];
		const hide = function() { return findAll( col.dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === '3.1:hide:m' } )[0] };
		const rows = withClass( col.dialog, 'builder-switch-row' );
		const switches = findAll( rows[0], function( el ) { return el.tagName === 'INPUT' && /^3\.1:hide:/.test( el.dataset.key ?? '' ) } );
		const quiet = rows.length === 1 && switches.length === 3 && withClass( rows[0], 'builder-device' ).length === 3 && withClass( col.dialog, 'builder-width-inherited' ).length === 2 && withClass( rows[0], 'nino-admin-hint' ).length === 0 && findAll( rows[0], function( el ) { return el.tagName === 'SMALL' } ).length === 0 && withClass( rows[0], 'builder-device-dot' ).length === 0;
		const none = findAll( col.dialog, function( el ) { return el.tagName === 'SELECT' && /^3\.1:(?:align|hide):/.test( el.dataset.key ?? '' ) } ).length === 0;

		hide().checked = true;
		fire( hide(), 'change' );

		const written = x.S.top[2].c[3].c[1].a.hide === 'off m:on' && withClass( col.dialog, 'builder-width-inherited' ).length === 1 && withClass( withClass( col.dialog, 'builder-switch-row' )[0], 'builder-device-dot' ).length === 1 && findAll( col.dialog, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('builder-width-back') !== -1 && el.attributes['aria-label'] === W('rows/below-tablet') } ).length === 1;
		const back = findAll( col.dialog, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('builder-width-back') !== -1 } )[0];

		fire( back, 'click' );

		const gone = x.S.top[2].c[3].c[1].a.hide === undefined;

		col.close();

		return strip !== undefined && picture( strip ) === 4 && quiet === true && none === true && written === true && gone === true;
	} )() );

	check( 'I. 15: where a call opens its link stands with the link - under it, in the section Content, where the link is - and not among the looks of the call: the button has "Open in" under its link and the weight under the looks',
		( function() {
			const x = make( 3 );

			x.model.state.box = null;
			x.model.openProperties( 4, '1', element('button'), function() {} );

			const dialog = x.w.dialogs[x.w.dialogs.length - 1];
			const has = function( key ) { return findAll( dialog, function( el ) { return el.dataset !== undefined && ( el.dataset.key === key || el.dataset.stepKey === key ) } ).length > 0 };
			const atLink = has( '1:target' ) && has( '1:href:source' ) !== undefined;
			const open0 = x.model.state.box.open;
			const inSource = open0 === 'content' && has( '1:target' ) && has( '1:tone' ) === false;

			x.model.state.box.open = 'look';
			x.model.state.box.draw();

			const inLook = has( '1:tone' ) && has( '1:target' ) === false;

			fire( dialog, 'close' );

			return atLink === true && inSource === true && inLook === true;
		} )() );

	check( 'I. 9: "Change source" stands where the value stands - in its column, under it, at the left - at every value alike: the text, the link; one place for every row', ( function() {
		const x = make( 3 );
		const own = lightbox( x, '1' );
		const buttons = findAll( own.dialog, function( el ) { return el.tagName === 'BUTTON' && /:source:/.test( el.dataset.key ?? '' ) } );
		const places = buttons.map( function( button ) {

			const held = String( button.parentNode.className );
			const above = String( button.parentNode.parentNode.className );

			return ( held.indexOf('builder-box-value') !== -1 && above.indexOf('nino-admin-field') !== -1 ) || ( held.indexOf('builder-box-change') !== -1 && above.indexOf('nino-admin-field') !== -1 );
		} );

		own.close();

		return buttons.length === 2 && places.every( function( place ) { return place === true } ) && /\.builder-box-change\s*\{[^}]*justify-content:\s*flex-start/.test( sheet ) === true;
	} )() );

	check( 'I. 10: the dialog of the source of a link has the tabs "Internal page", "Address", "Choose a value", "New" in that order, and the one that shows what the link has now is open - the first where it has nothing', ( function() {
		const x = make( 3 );
		const tabs = function( written ) {

			x.S.top[2].c[1].a = written === '' ? {} : { href : written };
			x.model.state.box = null;
			x.model.state.box = { block : 'team' };
			x.w.panel.editor.source( '1', { kind : 'link', attr : 'href', label : 'Target' }, element('button') );

			const dialog = x.w.dialogs[x.w.dialogs.length - 1];
			const buttons = findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.attributes.role === 'tab' } );
			const out = { order : buttons.map( function( button ) { return textOf( button ).trim() } ).join('|'), open : buttons.filter( function( button ) { return button.attributes['aria-selected'] === 'true' } ).map( textOf ).join() };

			fire( dialog, 'close' );

			return out;
		};
		const none = tabs( '' );
		const address = tabs( 'https://example.org' );
		const key = tabs( '~title' );

		return none.order === 'Internal page|Address|Choose a value|New' && none.open === 'Internal page' && address.open === 'Address' && key.open === 'Choose a value';
	} )() );

	check( 'F326 c. the dialog of the source lists each text with its words, where it is from and - quietly, in monospace, on a line of its own - the key it is saved under; the same key twice is ONE row, two keys with the same words are two rows each with its own key; no row says "Page Page ..." or "Another place"; the search finds a key as it finds words', ( function() {
		const x = make( 3 );

		x.model.state.box = { block : 'team' };
		x.w.panel.editor.source( '1', { kind : 'text', attr : '', label : 'Title' }, element('button') );

		const dialog = x.w.dialogs[x.w.dialogs.length - 1];
		const asked = x.w.requests.filter( function( request ) { return request.action === 'builder/keys' } );
		const entries = [
			{ key : '/_nino/webpage/404/name', name : 'name', text : 'Not found', own : false, withheld : true, area : 'page', of : 'Not found' },
			{ key : '/_nino/webpage/404/title', name : 'title', text : 'Not found', own : false, withheld : true, area : 'page', of : 'Not found' },
			{ key : '/_nino/webpage/404/title', name : 'title', text : 'Not found', own : false, withheld : true, area : 'page', of : 'Not found' },
			{ key : '/_nino/locale/de_DE/name', name : 'name', text : 'Deutsch', own : false, withheld : true },
		];

		asked[0].callback( { status : 200, responseJSON : { entries : entries, more : false }, getAllResponseHeaders : function() { return 'x' } } );

		const rows = findAll( dialog, function( el ) { return el.tagName === 'LI' && String( el.className ).indexOf('builder-source-item') !== -1 } );
		const uris = rows.map( function( row ) { return findAll( row, function( el ) { return String( el.className ).indexOf('builder-source-uri') !== -1 } ).map( textOf ).join() } );
		const text = rows.map( textOf ).join(' | ');
		const draft = uris.indexOf('/template/page-home/team/title') !== -1;

		const typedIn = function( query ) {
			const input = findAll( dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'source-search' } )[0];
			const before = x.w.requests.length;

			input.value = query;
			fire( input, 'input' );
			x.w.timers.splice( 0 ).forEach( function( fn ) { fn() } );

			const question = x.w.requests.slice( before ).filter( function( request ) { return request.action === 'builder/keys' } )[0];

			question.callback( { status : 200, responseJSON : { entries : [], more : false }, getAllResponseHeaders : function() { return 'x' } } );

			return findAll( dialog, function( el ) { return el.tagName === 'LI' && String( el.className ).indexOf('builder-source-item') !== -1 } ).map( textOf ).join(' | ');
		};
		const byKey = typedIn('page-home/team');
		const none = typedIn('no-such-thing');

		fire( dialog, 'close' );

		return rows.length === 4 && draft === true && uris.filter( function( uri ) { return uri === '/_nino/webpage/404/title' } ).length === 1 && uris.indexOf('/_nino/webpage/404/name') !== -1 && uris.indexOf('/_nino/locale/de_DE/name') !== -1
			&& /Page · Not found/.test( text ) === true && /Page Not found/.test( text ) === false && /Another place/.test( text ) === false && /Module · locale/.test( text ) === true
			&& byKey.indexOf('/template/page-home/team/title') !== -1 && byKey.indexOf('/_nino/') === -1 && none.indexOf('/template/') === -1
			&& /\.builder-source-uri \{[^}]*flex: 0 0 100%;/.test( sheet ) === true && /\.builder-source-code \{[^}]*font-family: var\(--nino-admin-mono\);/.test( sheet ) === true;
	} )() );

	check( 'I. 11: the notation of a component is no row of "Advanced" but behind the button <> in the foot of the lightbox - for the account that designs and not for the one that writes the content; Escape closes the small window of it and not the lightbox', ( function() {
		const x = make( 3 );
		x.model.state.sheet = 'block';
		const box = lightbox( x, '1' );
		const button = findAll( box.dialog, function( el ) { return el.tagName === 'BUTTON' && el.dataset.key === 'notation' } )[0];
		const inside = withClass( box.dialog, 'builder-code' ).every( function( code ) { return String( code.parentNode.className ).indexOf('builder-notation-pop') !== -1 } );
		const pop = withClass( box.dialog, 'builder-notation-pop' )[0];
		const closed = pop.hidden === true;

		fire( button, 'click' );

		const shown = pop.hidden === false && button.attributes['aria-expanded'] === 'true';

		fire( box.dialog, 'keydown', { key : 'Escape' } );

		const only = pop.hidden === true && x.S.box !== null;

		box.close();

		const content = make( 1 );
		const plain = lightbox( content, '1' );
		const none = findAll( plain.dialog, function( el ) { return el.dataset !== undefined && el.dataset.key === 'notation' } ).length === 0 && withClass( plain.dialog, 'builder-code' ).length === 0;

		plain.close();

		return button !== undefined && inside === true && closed === true && shown === true && only === true && none === true;
	} )() );

	check( 'I. 2: the pencil is always INSIDE the frame of its component, in its top corner on the right - never outside it, never at another corner - and the frame is the border box of the component and nothing more: for twelve kinds of components, at a wide and a narrow window, scrolled and not', ( function() {
		const model = opened().panel.model;
		const kinds = [ { w : 400, h : 60 }, { w : 160, h : 48 }, { w : 600, h : 100 }, { w : 300, h : 200 }, { w : 320, h : 180 }, { w : 380, h : 40 }, { w : 1200, h : 300 }, { w : 1200, h : 120 }, { w : 1100, h : 500 }, { w : 120, h : 20 }, { w : 20, h : 20 }, { w : 3000, h : 40 } ];
		const bad = [];

		[ 1280, 390 ].forEach( function( width ) {

			[ 0, 150 ].forEach( function( scrolled ) {

				const view = { top : scrolled, left : 0, width : width };

				kinds.forEach( function( kind, at ) {

					const box = { top : 100 + at * 3, left : 16, width : kind.w, height : kind.h };
					const spot = model.penSpot( box, 26, view );
					const pen = { left : spot.left + view.left, top : spot.top + view.top, right : spot.left + view.left + 26, bottom : spot.top + view.top + 26 };
					const right = Math.min( box.left + box.width, view.left + view.width );
					const frame = model.markBox( box, view );
					const small = box.width < 30 || box.height < 30;

					// at the top right, inside: the right edge of the pencil is two pixels in from the right edge of the box - or of the window - and its top inside the box; a box that is smaller than
					// the pencil has it at the corner and covered by it. In sight: never above the top of the window
					const atRight = pen.right === right - ( box.width >= 26 + 2 * model.PEN_INSET ? model.PEN_INSET : 0 );
					const inside = small === true || ( pen.left >= box.left && pen.top >= box.top && pen.bottom <= box.top + box.height );
					const seen = pen.top >= view.top || box.top + box.height - view.top < 30;

					if( atRight === false || inside === false || seen === false || frame.width !== box.width || frame.height !== box.height || frame.left !== box.left - view.left || frame.top !== box.top - view.top )
						bad.push( width+ '/'+ scrolled+ '/'+ at );
				} );
			} );
		} );

		return bad.length === 0 && /const placePen = function/.test( script ) === true && /penSpot\( box, size, view \)/.test( script ) === true && /penSide|sides\[0\]|side : 'right'/.test( script ) === false;
	} )() );

	check( 'I. 4: the place for a block between two blocks is a soft, larger button of glass with a plus and no word - no dashed line round it, 36 pixels with a 20 pixel plus - and its name and tooltip are the words', ( function() {
		return /const button = pill\( '', word\(/.test( script ) === true && /button\.insert\{--glass:36px;width:auto\}/.test( script ) === true && /button\.insert svg\{width:20px;height:20px\}/.test( script ) === true;
	} )() );

	check( 'I. 4b. a seam has its middle on the edge - the middle of the gap between two sections, the top of the first, the foot of the last - the same every time it is asked, held in only by the top and the foot of the page, and in the middle of the page', ( function() {
		const model = opened().panel.model;
		const size = { width : 44, height : 32 };
		const page = { width : 1000, height : 3000 };
		const between = model.seamAt( { top : 100, height : 400 }, { top : 500, height : 300 }, size, page );
		const gap = model.seamAt( { top : 100, height : 400 }, { top : 520, height : 300 }, size, page );
		const first = model.seamAt( null, { top : 120, height : 300 }, size, page );
		const last = model.seamAt( { top : 2800, height : 200 }, null, size, page );
		const top = model.seamAt( null, { top : 0, height : 300 }, size, page );

		return between.top + size.height / 2 === 500 && gap.top + size.height / 2 === 510 && first.top + size.height / 2 === 120 && last.top === 2968 && top.top === 0 && between.left === 478
			&& JSON.stringify( model.seamAt( { top : 100, height : 400 }, { top : 500, height : 300 }, size, page ) ) === JSON.stringify( between );
	} )() );

	check( 'I. 1, 2 and 8: the bar asks the workbench for no name of the panel above the way back (panelLabel false), what the other views point at is drawn by the workbench - the style of this panel has no rule for it - and the origin of a block and the language of a field are quiet words and no labels with a ground of their own', ( function() {
		return /panelLabel\s*:\s*false/.test( script ) === true && /nino-admin-contextbar-panel/.test( sheet ) === false && /is-lit\s*\{/.test( sheet ) === false && /\.is-lit\b/.test( sheet.replace( /\.builder-rail-button:not\(\.is-lit\)/g, '' ) ) === false
			&& /builder-tile-origin/.test( script ) === true && /builder-field-lang/.test( script ) === true && /nino-admin-badge builder-field-chip/.test( script ) === false && /builder-tile-chips/.test( script ) === false;
	} )() );
}


// --- the way back to the list -----------------------------------------------------------------------------------------

{
	const w = world();
	const regions = function() { return JSON.stringify( [ w.list.children.map( function( child ) { return child.className.split(' ')[0] } ) ] ) };

	w.title.textContent = 'Builder';
	w.open('#builder');

	const first = regions();

	w.open('#builder/builder/home');

	const during = w.view.children.length > 0;

	w.open('#builder');

	check( 'H. the way back from a page to the list leaves the list with the regions it had when it was opened the first time, and the bar of the page that is left is one that asks for no name of the panel - the head of the pane, its name and its tabs, is there with the list, as the workbench lets a hidden level be',
		during === true && regions() === first && /panelLabel\s*:\s*false/.test( script ) === true && /\[ 'builder-page', 'builder-new' \]\.forEach/.test( script ) === false );
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
		// The tree view is the view of a sheet of a block - the editor of the blocks brings one - and no view of a page: the sheet is opened with an adapter of its own
		w.open('#builder/builder/home');
		w.serve();

		const adapter = {
			panel : 'themelab', level : 'builder-page', template : 'page-home', name : 'Team', slug : 'home', dir : 'ltr', restore : false, sources : 'block', uploads : false,
			call : function( action, payload, done, extra ) { w.panel.call( action, payload, done, extra ) },
			base : function() { return { template : 'page-home', locale : 'de_DE' } },
			go : function() {}, href : function( parts ) { return w.Nino.admin.router.href( 'builder', parts ) },
			set : function() { w.Nino.admin.router.set( 'builder', [ 'home', 'team' ] ) },
			show : function() {}, fail : function() {},
			back : function() { return { parts : [ 'home' ], name : 'Home', keep : true, block : true } },
		};

		w.panel.editor.open( adapter, JSON.parse( JSON.stringify( w.served ) ) );
		w.serve();
	};

	advanced();

	const model = w.panel.model;
	const S = model.state;
	const tree = function() { return w.view.querySelector('.nino-admin-tree') };
	const row = function( id ) { return rowsOf( w ).find( function( el ) { return el.dataset.id === id } ) };
	const lit = function() { return rowsOf( w ).filter( function( el ) { return el.querySelector('.nino-admin-tree-row').classList.contains('is-lit') } ).map( function( el ) { return el.dataset.id } ).join() };

	check( 'F. the tree is the tree of the workbench with no line of the path over it, and the extended view has no column of settings: the tree stands alone', ( function() {
		const split = w.view.querySelector('.nino-admin-split');

		return S.tree !== null && S.tree.path === null && split.querySelector('.builder-adv-path') === null && split.querySelector('.nino-admin-tree-path') === null && split.querySelector('.nino-admin-split-settings') !== null && split.querySelector('.nino-admin-accordion') === null && tree() !== null;
	} )() );
	check( 'F. the column of the tree and the outline of a page are one pane: the same classes - the ground and the measure - on both, so that one rule dresses them in the scheme that is on', ( function() {
		const settings = w.view.querySelector('.nino-admin-split-settings');

		return settings !== null && String( settings.className ).indexOf('builder-tree-pane') !== -1 && String( settings.className ).indexOf('builder-tree-pane--column') !== -1
			&& /\.builder-tree-pane\s*\{[^}]*background:\s*var\(--editor-bg-elevated\)/.test( sheet ) === true && /\.builder-tree-pane--column\s*\{[^}]*padding:\s*var\(--nino-admin-space-3\)/.test( sheet ) === true && /builder-outline builder-tree-pane/.test( script ) === true;
	} )() );
	check( 'F. a double click on a row opens the lightbox of its component - as Enter does - and on a button of the row or on the arrow that opens it nothing: one click chooses as ever', ( function() {
		const rows = rowsOf( w );
		const row = rows.find( function( el ) { return el.dataset.id === '1' } ) ?? rows[1];
		const asked = w.dialogs.length;

		S.box = null;
		fire( row, 'click' );

		const chosen = w.dialogs.length === asked;
		const button = buttonsOf( row )[0];

		if( button !== undefined )
			fire( S.tree.el, 'dblclick', { target : button } );

		const none = w.dialogs.length === asked;
		const toggle = findAll( row, function( el ) { return String( el.className ).indexOf('nino-admin-tree-toggle') !== -1 } )[0];

		if( toggle !== undefined )
			fire( S.tree.el, 'dblclick', { target : toggle } );

		const arrow = w.dialogs.length === asked;

		fire( S.tree.el, 'dblclick', { target : row } );

		const opened = w.dialogs.length === asked + 1 && S.box !== null;

		fire( w.dialogs[w.dialogs.length - 1], 'close' );

		return chosen === true && none === true && arrow === true && opened === true;
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
	check( 'F290. the page of an editor of the blocks asks for the quiet frame of the workbench round the innermost component under the pointer - hover : true - and what it reports with onHover is lit in the tree, the same component; the page of the Builder asks for none, and a workbench that does not know the option is handed it all the same: nothing is drawn and nothing fails', ( function() {
		marks.onHover( null );
		marks.onHover('1.1');

		const lit1 = lit() === '1.1';

		marks.onHover( null );

		return marks.hover === true && lit1 === true && lit() !== '1.1' && typeof marks.onHover === 'function';
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
	check( 'F. a component that is put into a container with nothing in it is the chosen entry and the container is opened over it - a container with no child is no folded one with the new component hidden in it', ( function() {
		const block = S.top[model.rootAt()];

		block.c[1].c = [ { t : '\n' } ];
		S.tree.update( model.itemsNow() );

		const was = ( row( '1' ) ?? { attributes : {} } ).attributes['aria-expanded'];

		model.addComponent( '1', { name : 'section-title', node : { n : 'section-title', s : '', a : {}, c : null } }, '\n' );

		const out = was === undefined && S.tree.get().selected === '1.1' && row( '1' ).attributes['aria-expanded'] === 'true' && row( '1.1' ) !== undefined;

		return out;
	} )() );
}

// --- G. the pencil: reached by the pointer and by the keyboard --------------------------------------------------------

{
	const w = opened();
	const model = w.panel.model;
	const view = { top : 0, left : 0, width : 1000 };

	check( 'G. the pencil is inside the box at its top corner on the right for every component - a loop and a row as well - and goes with the box: a box that is scrolled up has it at the top of the window and never below its foot, a box that reaches beyond the window has it at the edge of the window', ( function() {
		const boxes = [ { top : 100, left : 50, width : 300, height : 80 }, { top : -200, left : 50, width : 300, height : 500 }, { top : 100, left : 900, width : 400, height : 80 }, { top : -200, left : 50, width : 300, height : 100 } ];
		const spots = boxes.map( function( box ) { return model.penSpot( box, 26, view ) } );

		return spots[0].left === 350 - 26 - 2 && spots[0].top === 102 && spots[1].top === 0 && spots[1].left === 350 - 26 - 2 && spots[2].left === 1000 - 26 - 2 && spots[3].top === -128 && model.PEN_INSET === 2;
	} )() );
	check( 'G. what the pointer is over and what is chosen are two things: the pencil is for the one the pointer is over - the innermost - and for the chosen one where the pointer is over none; each has its own mark, the chosen one\'s drawn through and the other\'s quiet, and where they are one component the chosen one\'s is the only mark', ( function() {
		const one = { entry : { path : '0.1.0' } };
		const other = { entry : { path : '0.1' } };
		const a = model.aimFor( one, other );
		const b = model.aimFor( null, other );
		const c = model.aimFor( other, other );
		const d = model.aimFor( null, null );

		return a.pen === one && a.marks.hovered === one && a.marks.chosen === other && b.pen === other && b.marks.hovered === null && b.marks.chosen === other && c.pen === other && c.marks.hovered === null && c.marks.chosen === other && d.pen === null
			&& /\.mark\.chosen\{border-width:2px;border-style:solid;opacity:1\}/.test( script ) === true && /\.mark\.hovered\{border-width:1px;border-style:solid;opacity:\.6\}/.test( script ) === true
			&& /hovered = null;\s*placePen\(\);\s*\}\s*\} \);/.test( script ) === true && /const hover = function\( target \)/.test( script ) === true && /pointerAim|aimed\.zone|penZone|inZone/.test( script ) === false;
	} )() );
	check( 'G. a loop is a stop of the Tab key of its own - it has no element to stand next to - with a pencil in front of its first repetition, and only where it has repetitions and a pencil for the account', ( function() {
		const element = { id : 'a' };
		const entries = [ { path : '1.5', elements : [ element ], loop : true }, { path : '1.7', elements : [], loop : true }, { path : '1.9', elements : [ element ], loop : false }, { path : '1.11', elements : [ element ], loop : true } ];
		const stops = model.loopStopsOf( entries, function( entry ) { return entry.path !== '1.11' } );

		return stops.length === 1 && stops[0].path === '1.5';
	} )() );
	check( 'G. what the pointer is over decides the component of the pencil at once - the innermost one, whatever is chosen - except over the pencil or the bar itself, which are no part of the page: the pointer on them leaves the component as it is', ( function() {
		return /if\( ownOf\( origin \) === false \)\s*hover\( event\.target \);/.test( script ) === true && /const hover = function\( target \) \{\s*hovered = pickAim\( target \);\s*placePen\(\);/.test( script ) === true
			&& /const aim = function\( target \) \{\s*chosen = pickAim\( target \);/.test( script ) === true;
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
	check( 'G. a pencil never stands in a link or a button - it stands after it - an empty loop has a stop with a pencil of its own at the element that holds it, and the arrow keys lead from one component of a block to the next and stop at its ends', ( function() {
		const has = function( names, holding ) { return { hasAttribute : function( name ) { return names.indexOf( name ) !== -1 }, closest : function() { return holding ?? null } } };
		const link = has( [] );
		const inLink = has( [], link );
		const textInLink = has( [ 'data-nino-text' ], link );
		const plain = has( [] );
		const entries = [ { path : '1.1', elements : [ inLink ], loop : false }, { path : '1.2', elements : [ plain ], loop : false } ];
		const stops = model.stopsOf( { texts : [] }, entries, function() { return true } );
		const hold = {};
		const empty = model.loopStopsOf( [ { path : '1.7', elements : [], holder : hold, loop : true }, { path : '1.9', elements : [], loop : true } ], function() { return true } );
		const walk = [ 'ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End', 'x' ].map( function( key ) { return model.roveTo( 3, 1, key ) } );

		return model.seatAfter( inLink ) === link && model.seatAfter( textInLink ) === link && model.seatAfter( plain ) === null && model.seatOf( inLink ) === 'after'
			&& stops.length === 2 && empty.length === 1 && empty[0].path === '1.7' && model.shownOf( empty[0] )[0] === hold
			&& walk.join() === '2,2,0,0,0,2,-1' && model.roveTo( 3, 2, 'ArrowRight' ) === -1 && model.roveTo( 3, 0, 'ArrowLeft' ) === -1 && model.roveTo( 0, 0, 'Home' ) === -1;
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
		html : schema( 'HTML', 'code', 'none', { markup : true, container : false } ),
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



// --- I. the source of a loop and its sort in words --------------------------------------------------------------------

{
	const text = { type : 'string', label : 'x', hint : '', default : '', options : null, optionLabels : null, responsive : false };
	const loopSchema = schema( 'List', 'list', 'none', { loop : true, attributes : { sort : Object.assign( {}, text, { label : 'Sort' } ), limit : { type : 'int', label : 'At most', hint : '', default : 0, min : 0, max : 1000 }, offset : { type : 'int', label : 'Skip', hint : '', default : 0 }, query : Object.assign( {}, text, { label : 'Query' } ) } } );
	const schemas = {
		'section-title' : schema( 'Title', 'heading', 'text' ),
		'collection-list' : loopSchema,
		'collection-filter' : schema( 'Filter', 'sliders-horizontal', 'none', { loop : false, container : true, attributes : { by : Object.assign( {}, text, { label : 'Field' } ) } } ),
	};
	const tree = ( sort ) => [
		{ n : 'template', s : '/templates/html-header', a : {}, c : null }, { t : '\n' },
		{ n : 'section-wrap', s : '', a : { id : 'team' }, c : [ { t : '\n' }, { n : 'collection-filter', s : '', a : { by : '.title' }, c : [ { t : '\n' }, { n : 'collection-list', s : '/team', a : { sort : sort }, c : [ { t : '\n' }, { n : 'section-title', s : '.title', a : {}, c : null }, { t : '\n' } ] }, { t : '\n' } ] }, { t : '\n' } ] }, { t : '\n' },
		{ n : 'template', s : '/templates/html-footer', a : {}, c : null }, { t : '\n' },
	];
	const ofLoop = function( level, sort ) {

		const w = opened( { page : pageAnswer( { tree : tree( sort ), level : level, schemas : Object.assign( {}, pageAnswer().schemas, schemas ) } ) } );
		const S = w.panel.model.state;

		S.fieldInfo['/team'] = { types : { title : 'string', date : 'date', price : 'double' }, labels : { title : 'Name' } };
		S.types = [ { type : '/team', title : 'Team' } ];

		return { w : w, S : S, model : w.panel.model };
	};
	const open = function( x ) {

		x.S.box = null;
		x.model.openProperties( 2, '1.1', element('button'), function() {} );

		const dialog = x.w.dialogs[x.w.dialogs.length - 1];
		const heads = findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && el.attributes['aria-controls'] !== undefined && el.className.indexOf('nino-admin-help-button') === -1 } );
		const keys = function() { return findAll( dialog, function( el ) { return el.dataset !== undefined && el.dataset.key !== undefined } ).map( function( el ) { return el.dataset.key } ) };
		const field = function( key ) { return findAll( dialog, function( el ) { return el.dataset !== undefined && el.dataset.key === key } )[0] };

		return { dialog : dialog, heads : heads, keys : keys, field : field, close : function() { fire( dialog, 'close' ) } };
	};

	check( 'I. the source of a loop is the one section of its lightbox - open, with no head of an accordion - and holds its type, its sort, how many at most, from which on and the query, and what it shows now; none of them is in the look or in the advanced part', ( function() {
		const x = ofLoop( 3, '-date,title' );
		const box = open( x );
		const heads = box.heads.length;
		const inSource = box.keys();
		const names = [ '1.1:limit', '1.1:offset', '1.1:query', '1.1:sort:0:field', '1.1:sort:1:field', '1.1:sort:add', '1.1:source:' ];

		const everywhere = box.keys();
		const once = names.every( function( key ) { return inSource.indexOf( key ) !== -1 && everywhere.filter( function( other ) { return other === key } ).length === 1 } );

		box.close();

		return heads === 0 && once === true;
	} )() );
	check( 'I. the sort is a list of steps that is the attribute in both directions: a step is a field and a direction, a change writes the attribute in its form, a step is added and taken away, at most three - and a value the list cannot show stays as it is written until a new one is made', ( function() {
		const x = ofLoop( 3, '-date,title' );
		const node = function() { return x.S.top[2].c[1].c[1] };
		const box = open( x );
		const direction = box.field('1.1:sort:0:direction');
		const shown = direction.value === 'desc' && box.field('1.1:sort:0:field').value === 'date' && box.field('1.1:sort:1:direction').value === 'asc';

		direction.value = 'asc';
		fire( direction, 'change' );
		const written = node().a.sort === 'date,title';

		// A step that is added is a row with no field: nothing is written until a field is chosen
		fire( box.field('1.1:sort:add'), 'click' );
		const pending = node().a.sort === 'date,title' && box.field('1.1:sort:new:field') !== undefined && box.field('1.1:sort:new:field').value === '' && box.field('1.1:sort:add').disabled === true;
		const chosen = box.field('1.1:sort:new:field');

		chosen.value = 'price';
		fire( chosen, 'change' );
		const added = pending === true && node().a.sort === 'date,title,price' && box.field('1.1:sort:add') === undefined && box.field('1.1:sort:new:field') === undefined;
		const against = box.field('1.1:sort:1:direction');

		against.value = 'desc';
		fire( against, 'change' );
		const reversed = node().a.sort === 'date,-title,price';

		box.field('1.1:sort:1:direction').value = 'asc';
		fire( box.field('1.1:sort:1:direction'), 'change' );
		fire( findAll( box.dialog, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-label'] === W( 'sort/remove', 1 ) } )[0], 'click' );
		const removed = node().a.sort === 'title,price';
		box.close();

		const strange = ofLoop( 3, '-nothing,title' );
		const other = open( strange );
		const kept = strange.S.top[2].c[1].c[1].a.sort === '-nothing,title' && other.field('1.1:sort:0:field') === undefined && other.field('1.1:sort:reset') !== undefined;

		fire( other.field('1.1:sort:reset'), 'click' );
		const reset = strange.S.top[2].c[1].c[1].a.sort === undefined;
		other.close();

		return shown === true && written === true && added === true && reversed === true && removed === true && kept === true && reset === true;
	} )() );
	check( 'I. the words of a direction follow the type of the field - text, date, number - and the loop in the tree is told by its type, its sort in those words and the number of its elements', ( function() {
		const x = ofLoop( 3, '-date,title' );
		const words = [ 'title', 'date', 'price' ].map( function( name ) { return [ x.model.directionWord( '/team', name, false ), x.model.directionWord( '/team', name, true ) ].join('|') } );
		const kinds = [ 'title', 'date', 'price' ].map( function( name ) { return W( 'sort/asc-'+ x.model.sortKind( x.S.fieldInfo['/team'].types[name] ) )+ '|'+ W( 'sort/desc-'+ x.model.sortKind( x.S.fieldInfo['/team'].types[name] ) ) } );
		x.S.adv = { slug : 'home', block : 'team' };
		const loop = x.model.itemsNow()[0].children[0].children[0];

		return new Set( words ).size === 3 && words.join() === kinds.join() && loop.loop.detail.indexOf( W( 'sort/by', '' ).trim() ) !== -1 && loop.loop.detail.indexOf('Name') !== -1 && loop.loop.detail.indexOf( W( 'sort/desc-date' ) ) !== -1;
	} )() );
	check( 'I. a loop is counted as the repetition of one: a table draws its first component once more in its head, which is no element, and a loop in a loop is drawn for each element of the outer one - the number is of one', ( function() {
		const x = ofLoop( 3, '' );
		const list = function() { return x.S.top[2].c[1].c[1] };

		x.S.adv = { slug : 'home', block : 'team' };

		const times = function( rel, count ) { return Array.from( { length : count }, function() { return { path : x.model.absOf( rel ), elements : [] } } ) };

		x.S.found = { blocks : [], all : times( '1.1.1', 4 ) };
		const plain = x.model.loopElements( list(), '1.1' );
		x.S.schemas['collection-list'].preview = 'table';
		const table = x.model.loopElements( list(), '1.1' );
		x.S.schemas['collection-list'].preview = '';
		x.S.schemas['collection-filter'].loop = true;
		x.S.found = { blocks : [], all : times( '1.1', 3 ).concat( times( '1.1.1', 12 ) ) };
		const nested = x.model.loopElements( list(), '1.1' );

		x.S.schemas['collection-filter'].loop = false;

		return plain === 4 && table === 3 && nested === 4;
	} )() );
	check( 'I. the number a loop shows follows every page that is drawn - in the lightbox that is open and in the tree - whichever view drew the page - the draw of the basic view and the one of the extended view both call it', ( function() {
		const x = ofLoop( 3, '' );
		let told = 0;
		let updated = 0;

		x.S.box = { recount : function() { told++ } };
		x.S.tree = { update : function() { updated++ } };
		x.S.mode = 'basic';
		x.model.pageDrawn( { blocks : [], all : [] } );
		x.S.mode = 'advanced';
		x.model.pageDrawn( { blocks : [], all : [] } );

		return told === 2 && updated === 1 && ( script.match( /pageDrawn\(/g ) ?? [] ).length === 2;
	} )() );
	check( 'I. a number the setting does not take is told at its field and stays out of the draft - a minus where the least is nought, a word, a value above the most - and a good one is written and the sentence is gone', ( function() {
		const x = ofLoop( 3, '' );
		const node = function() { return x.S.top[2].c[1].c[1] };
		const box = open( x );
		const input = findAll( box.dialog, function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '1.1:limit' } )[0];
		const errors = function() { return findAll( box.dialog, function( el ) { return el.className === 'nino-admin-field-error' } ).length };
		const tried = [ '-5', 'abc', '2.5', '5000' ].map( function( value ) { input.value = value; fire( input, 'change' ); return [ node().a.limit, errors() ] } );

		input.value = '7';
		fire( input, 'change' );
		const good = node().a.limit === '7' && errors() === 0;

		box.close();

		return tried.every( function( pair ) { return pair[0] === undefined && pair[1] === 1 } ) && good === true;
	} )() );
	check( 'I. a sort the list cannot show is a finding at the row of the loop in the tree as it is in the lightbox, one it can show is none - and a field the type gives no label is told by its plain name, with no mark round it, one with a label by the label', ( function() {
		const strange = ofLoop( 3, '-nothing,title' );
		const fine = ofLoop( 3, '-date,title' );

		strange.S.adv = { slug : 'home', block : 'team' };
		fine.S.adv = { slug : 'home', block : 'team' };

		const row = function( x ) { return x.model.itemsNow()[0].children[0].children[0] };
		const sentence = fine.model.sortSentence( '/team', 'title,-price' );

		return row( strange ).finding !== undefined && row( fine ).finding === undefined && sentence.indexOf('Name') !== -1 && sentence.indexOf('price') !== -1 && sentence.indexOf('`') === -1;
	} )() );
	check( 'I. a filter holds the loop and is none: it has its type icon and neither the loop icon nor the pattern, the list in it has both - and the account that may only write the content reads the type and the sort and has no field to change either', ( function() {
		const x = ofLoop( 3, '' );

		x.S.adv = { slug : 'home', block : 'team' };

		const filter = x.model.itemsNow()[0].children[0];
		const inner = filter.children[0];
		const poor = ofLoop( 1, '-date' );
		const box = open( poor );
		const readOnly = [ '1.1:sort:0:field', '1.1:sort:add', '1.1:limit', '1.1:query', '1.1:source:' ].every( function( key ) { return box.field( key ) === undefined } );

		box.close();

		return filter.loop === undefined && filter.icon === 'sliders-horizontal' && typeof inner.loop === 'object' && inner.icon === 'list' && readOnly === true && box.heads.length === 0;
	} )() );
}

// --- J. the variants of a block -----------------------------------------------------------------------------------------

{
	const labelled = function( de, en ) { return { de_DE : de, en_US : en } };
	const items = [ [ 'image-left', labelled( 'Bild links', 'Picture left' ) ], [ 'image-right', labelled( 'Bild rechts', 'Picture right' ) ], [ 'plain', labelled( 'Schlicht', 'Plain' ) ] ];
	const ofVariants = function( level, current ) {

		const w = opened( { page : pageAnswer( { level : level } ) } );
		const S = w.panel.model.state;

		S.variants = [ { at : 2, key : 'split-image-text', current : current, items : items } ];

		return { w : w, S : S, model : w.panel.model };
	};

	check( 'J. the choice of a variant is there for a block that the server told has variants and for the account that designs - and for no other block, no account that only writes the content, and not in a frame', ( function() {
		const design = ofVariants( 2, 'image-left' );
		const develop = ofVariants( 3, 'image-left' );
		const content = ofVariants( 1, 'image-left' );
		const framed = ofVariants( 3, 'image-left' );

		framed.S.frame = true;
		design.S.variants.push( { at : 1, key : 'x', current : null, items : items } );

		return design.model.variantOfBlock( 2 ) !== undefined && develop.model.variantOfBlock( 2 ) !== undefined && design.model.variantOfBlock( 4 ) === undefined && design.model.variantOfBlock( 1 ) === undefined && content.model.variantOfBlock( 2 ) === undefined && framed.model.variantOfBlock( 2 ) === undefined;
	} )() );
	const radios = function( field ) { return findAll( field, function( el ) { return el.attributes !== undefined && el.attributes['role'] === 'radio' } ) };
	const checked = function( field ) { return radios( field ).filter( function( el ) { return el.attributes['aria-checked'] === 'true' } ).map( textOf ) };
	const rest = function( x ) { while( x.w.timers.length > 0 ) x.w.timers.shift()() };

	check( 'J. the variant that holds is the one that is shown, in the words of the block - in the lightbox of its root as in the outline - and one that holds none is shown as "own", no step that is chosen and nothing that can be chosen as it', ( function() {
		const x = ofVariants( 3, 'image-right' );
		const field = x.model.blockVariantField( 2 );
		x.S.adv = null;

		const outline = x.model.outlineItems();
		const row = outline.find( function( entry ) { return entry.id === x.model.keyAt( 2 ) } );
		const foreign = ofVariants( 3, null );
		const strange = foreign.model.blockVariantField( 2 );
		const alone = foreign.model.outlineItems().find( function( entry ) { return entry.id === foreign.model.keyAt( 2 ) } );
		const many = ofVariants( 3, null );

		many.S.variants[0].items = items.concat( [ [ 'a', labelled( 'A', 'A' ) ], [ 'b', labelled( 'B', 'B' ) ], [ 'c', labelled( 'C', 'C' ) ] ] );

		const pick = findAll( many.model.blockVariantField( 2 ), function( el ) { return el.tagName === 'OPTION' } );

		return radios( field ).length === 3 && checked( field ).join() === 'Picture right' && row !== undefined && /Picture right|Bild rechts/.test( row.detail ) === true
			&& radios( strange ).length === 3 && checked( strange ).length === 0 && textOf( strange ).indexOf( W('variant/own-note') ) !== -1 && radios( strange ).every( function( el ) { return textOf( el ) !== W('variant/own') } ) && alone.detail === ''
			&& pick.length === 7 && pick[0].value === '' && pick[0].disabled === true && textOf( pick[0] ) === W('variant/own') && pick.slice( 1 ).every( function( option ) { return option.value !== '' } );
	} )() );
	check( 'J. a variant that is chosen is asked of the server by the place and the name only, and the answer is the block in the draft in ONE step that undoes - a refusal changes nothing and says so, and "own" is no name that is asked for', ( function() {
		const x = ofVariants( 3, 'image-left' );
		const before = JSON.stringify( x.S.top );
		const steps = x.S.undo.length;

		x.w.requests.length = 0;
		x.model.chooseVariant( 2, '' );
		const none = x.w.requests.length === 0;
		x.model.chooseVariant( 2, 'image-right' );

		const asked = x.w.requests.filter( function( request ) { return request.action === 'builder/variant' } );
		const keys = asked.length === 1 ? Object.keys( asked[0].payload ).sort().join() : '';
		const node = JSON.parse( JSON.stringify( x.S.top[2] ) );

		node.a.reverse = 'on';
		x.w.reply( 200, { node : node, variant : 'image-right' } );

		const done = x.S.undo.length === steps + 1 && x.S.top[2].a.reverse === 'on';

		x.model.undo();

		const back = JSON.stringify( x.S.top ) === before;

		x.w.requests.length = 0;
		x.model.chooseVariant( 2, 'image-right' );
		x.w.reply( 403, { error : 'x', code : 'builder_variant' } );

		return none === true && asked.length === 1 && /^at,inserted,locale,name,pictures,slots,template,texts,tree$/.test( keys ) && asked[0].payload.at === '2' && asked[0].payload.name === 'image-right' && done === true && back === true && x.S.undo.length === steps && JSON.stringify( x.S.top ) === before;
	} )() );
	check( 'J. the answer to a choice is for the block that was asked about: a block that was moved, taken out or brought back while it came gets nothing written - the answer is dropped and said so - and while a choice is asked for no other can be made', ( function() {
		const x = ofVariants( 3, 'image-left' );
		const before = JSON.stringify( x.S.top );
		const steps = x.S.undo.length;

		x.w.requests.length = 0;
		x.model.chooseVariant( 2, 'image-right' );
		x.model.chooseVariant( 2, 'image-left' );

		const once = x.w.requests.filter( function( request ) { return request.action === 'builder/variant' } ).length === 1;
		const locked = x.S.variantBusy === true;
		const moved = x.S.top[2];

		// The block is changed by hand while the answer comes: a block of the draft that is not the one that was sent
		x.S.top[2] = JSON.parse( JSON.stringify( x.S.top[4] ) );

		const node = JSON.parse( JSON.stringify( moved ) );

		node.a.reverse = 'on';
		x.w.reply( 200, { node : node, variant : 'image-right' } );

		const dropped = x.S.top[2].a.reverse === undefined && x.S.undo.length === steps && x.S.variantBusy === false;

		x.S.top[2] = moved;
		x.w.requests.length = 0;
		x.model.chooseVariant( 2, 'image-right' );

		return once === true && locked === true && dropped === true && x.w.requests.filter( function( request ) { return request.action === 'builder/variant' } ).length === 1 && x.S.top.length === JSON.parse( before ).length;
	} )() );
	check( 'J. the strip of the lightbox chooses with every arrow key and asks the server once it has rested - going past a variant is no request and no step - and the field is the choice of the page that was drawn last: a change by hand makes it "own", and the way back is a step', ( function() {
		const x = ofVariants( 3, 'image-left' );

		x.w.requests.length = 0;
		x.S.box = null;
		x.model.openProperties( 2, 'root', element('button'), function() {} );

		const box = x.w.dialogs[x.w.dialogs.length - 1];
		const field = findAll( box, function( el ) { return el.dataset !== undefined && el.dataset.blockVariant !== undefined } )[0];
		const buttons = radios( field );

		const waiting = x.w.timers.length;

		fire( buttons[1], 'click' );
		fire( buttons[2], 'click' );

		const quiet = x.w.requests.filter( function( request ) { return request.action === 'builder/variant' } ).length === 0 && x.w.timers.length === waiting + 2;

		rest( x );

		const asked = x.w.requests.filter( function( request ) { return request.action === 'builder/variant' } );
		const last = asked.length === 1 && asked[0].payload.name === 'plain';

		x.w.requests.length = 0;
		x.S.variantBusy = false;
		x.S.variants = [ { at : 2, key : 'split-image-text', current : null, items : items } ];
		x.model.pageDrawn( { blocks : [], all : [] } );

		const now = findAll( box, function( el ) { return el.dataset !== undefined && el.dataset.blockVariant !== undefined } )[0];

		return quiet === true && last === true && now !== undefined && now !== field && checked( now ).length === 0 && textOf( now ).indexOf( W('variant/own-note') ) !== -1;
	} )() );
	check( 'J. Escape leaves a text that is written as a click beside it does: what was typed stays, the step of Undo takes it back - the script ends the writing with what stands there and takes none back', ( function() {
		const keys = script.slice( script.indexOf( "editing.keys = function" ), script.indexOf( "editing.blur = function" ) );

		return /event\.key === 'Escape'[\s\S]{0,260}endEdit\( true \)/.test( keys ) === true && keys.indexOf( 'endEdit( false )' ) === -1;
	} )() );
	check( 'J. the tile of the dialog says how many variants the block has - none where it has none - in the same tile, once', ( function() {
		const w = opened();
		const tile = function( count ) { return w.panel.model.tileOf( { key : 'k', name : 'K', description : 'K', origin : { label : 'x' }, own : false, variants : count, hint : null, off : null }, function() {}, function() {} ) };

		return textOf( tile( 3 ) ).indexOf( W( 'variant/many', 3 ) ) !== -1 && textOf( tile( 1 ) ).indexOf( W('variant/one') ) !== -1 && textOf( tile( 0 ) ).indexOf( 'variants' ) === -1 && findAll( tile( 3 ), function( el ) { return String( el.className ).indexOf('builder-tile-variants') !== -1 } ).length === 1;
	} )() );
	check( 'J. a loop is told by the title of its type where the page has one - also for the account that only writes the content, which is not told the types - and by the path where there is none', ( function() {
		const x = ofVariants( 1, null );

		x.S.types = [];
		x.S.typeTitles = { '/team' : 'Our team' };

		return x.model.typeTitle( '/team' ) === 'Our team' && x.model.typeTitle( '/shop' ) === '/shop';
	} )() );
}


// --- K. a sheet of the kind block, for a second panel -------------------------------------------------------------------

{
	const sheet = function() {

		const w = opened();
		const S = w.panel.model.state;
		const level = element('div');
		const sent = [];
		const went = [];
		const set = [];
		let shown = 0;

		level.id = 'dummy-level';
		w.page.appendChild( level );

		const adapter = {
			panel : 'dummy', level : 'dummy-level', template : 'block-draft', name : 'Draft', slug : '', restore : false,
			call : function( action, payload, done ) { sent.push( { action : action, payload : payload, done : done } ) },
			base : function() { return { block : 'x', locale : S.locale } },
			go : function( parts ) { went.push( parts ) },
			href : function( parts ) { return '#dummy/'+ parts.join('/') },
			set : function( parts ) { set.push( parts ) },
			show : function() { shown++ },
			fail : function() {},
			back : function() { return { parts : [ 'list' ], name : 'Blocks', keep : false, block : false } },
		};
		const draft = pageAnswer( { tree : [ block( 'split', columns ) ], level : 3 } );

		w.requests.length = 0;

		return { w : w, S : S, adapter : adapter, draft : draft, sent : sent, went : went, set : set, shown : function() { return shown }, level : level, editor : w.panel.editor };
	};

	check( 'K. a panel with an adapter of its own works on a sheet of the kind block with the code of the pages: the tree view is drawn, the requests of the draft go to the adapter with its own payload and none to an action of the pages, and the way back is the adapter\'s', ( function() {
		const x = sheet();
		const opened = x.editor.open( x.adapter, x.draft );
		const back = findAll( x.level, function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0];
		const preview = x.sent.filter( function( entry ) { return entry.action === 'preview' } );

		const out = opened === true && x.S.sheet === 'block' && x.S.mode === 'advanced' && x.shown() === 1 && x.set.length === 1 && x.w.requests.length === 0
			&& preview.length === 1 && preview[0].payload.block === 'x' && preview[0].payload.template === undefined && back !== undefined && ( back.attributes.href ?? back.href ) === '#dummy/list' && textOf( back ).indexOf('Blocks') !== -1;

		x.editor.close();

		return out === true && x.S.sheet === 'page';
	} )() );
	check( 'K. a change in the lightbox of the sheet and Undo and Redo are the draft of the sheet - one step each - and the draft is told to the registry of the panel that owns the adapter; saving it is a request of the adapter, never an action of the pages', ( function() {
		const x = sheet();

		x.editor.open( x.adapter, x.draft );

		const at = x.S.top.findIndex( function( node ) { return node.n !== undefined } );
		const before = JSON.stringify( x.S.top );

		x.editor.properties( at, 'root', element('button'), function() {} );
		x.editor.change( function() { x.S.top[at].a.ground = 'dark' } );

		const changed = x.S.undo.length === 1 && x.S.top[at].a.ground === 'dark' && x.w.dirty.isDirty( [ 'dummy' ] ) === true;

		x.editor.undo();

		const undone = JSON.stringify( x.S.top ) === before && x.S.redo.length === 1;

		x.editor.redo();

		const redone = x.S.top[at].a.ground === 'dark';

		x.sent.length = 0;
		x.editor.save( function() {} );

		const saved = x.sent.filter( function( entry ) { return entry.action === 'save' } );

		x.editor.close();

		return changed === true && undone === true && redone === true && saved.length === 1 && saved[0].payload.block === 'x' && saved[0].payload.template === undefined && x.w.requests.length === 0;
	} )() );
	check( 'K. a tile is asked for and drawn through the adapter as well, and the page panel has no sheet left when the sheet is closed: the next page opens as a page', ( function() {
		const x = sheet();

		x.editor.open( x.adapter, x.draft );
		x.sent.length = 0;

		const box = element('div');

		box.dataset.block = 'hero-full';
		x.editor.tile( box );

		const asked = x.sent.filter( function( entry ) { return entry.action === 'tile' } );

		x.editor.close();

		return asked.length === 1 && asked[0].payload.key === 'hero-full' && asked[0].payload.block === 'x' && x.w.requests.length === 0 && x.S.adapter !== x.adapter && x.S.ready === false;
	} )() );
}


// --- K. the texts of the header and of the footer are those of the frame the page chooses ------------------------------------------------------------------------------

{
	const texts = function( head, foot ) {

		const x = world( { page : pageAnswer( { tree : [ head, { t : '\n' }, block( 'hero' ), { t : '\n' }, foot, { t : '\n' } ] } ) } );
		const asked = {};

		x.title.textContent = 'Builder';
		x.open('#builder/builder/home');

		[ '@header', '@footer' ].forEach( function( id ) {

			x.requests.length = 0;
			const button = buttonsOf( rowOf( x, id ) ).find( function( candidate ) { return candidate.dataset.key === 'texts' } );

			if( button !== undefined )
				fire( button, 'click' );

			asked[id] = x.requests.filter( function( request ) { return request.action === 'builder/frametexts' } ).map( function( request ) { return request.payload.template } ).join();
		} );

		return asked;
	};
	const standard = texts( { n : 'template', s : '/templates/html-header', a : {}, c : null }, { n : 'template', s : '/templates/html-footer', a : {}, c : null } );
	const named = texts( { n : 'frame', s : '/templates/frame-header-landing', a : {}, c : null }, { n : 'frame', s : '/templates/frame-footer-klein', a : {}, c : null } );
	const none = texts( { n : 'frame', s : '/templates/frame-header-none', a : {}, c : null }, { n : 'template', s : '/templates/html-footer', a : {}, c : null } );

	check( 'K. the texts of the head and of the foot of a page are asked for the frames the page chooses: the standard ones for the calls of the scaffold, the named ones for a page that chooses them, and none for a side the page leaves empty',
		standard['@header'] === 'frame-header' && standard['@footer'] === 'frame-footer' && named['@header'] === 'frame-header-landing' && named['@footer'] === 'frame-footer-klein' && none['@header'] === '' && none['@footer'] === 'frame-footer' );
}


// --- L. a frame with the call of html: what the step that may not write markup sees of it is what it sees of a page ----------------------------------------------------------------------------

{
	// A header with the call of html in its actions and a free text beside the button: the same tree is a frame - the sheet of a frame is the sheet of a block whose root is the header - and a page
	const tree = [ { n : 'header-wrap', s : '', a : { id : 'header' }, c : [ { t : '\n' }, { n : 'header-actions', s : '', a : {}, c : [ { t : '\n' }, { n : 'html', s : '', a : {}, c : [ { t : '<p>x</p>' } ] }, { t : '\n' }, { n : 'button', s : '~b', a : {}, c : null }, { t : '\n' }, { t : 'Free text' } ] }, { t : '\n' } ] } ];
	const schemas = { 'header-wrap' : schema( 'Header', 'panel-top', 'none', { side : 'header' } ), 'header-actions' : schema( 'Actions', 'mouse-pointer-click', 'none' ), html : schema( 'HTML', 'code', 'none', { markup : true, container : false } ), button : schema( 'Button', 'rectangle-horizontal', 'text' ) };
	const known = { 'header-wrap' : { label : 'Header', icon : 'panel-top', id : 'id', source : 'none' }, 'header-actions' : { label : 'Actions', icon : 'mouse-pointer-click', id : '', source : 'none' }, html : { label : 'HTML', icon : 'code', id : '', source : 'none' }, button : { label : 'Button', icon : 'rectangle-horizontal', id : '', source : 'text' } };

	const sheet_ = function( asFrame, level ) {

		const w = opened();
		const level_ = element('div');
		const adapter = {
			panel : 'dummy', level : 'frame-level', frame : asFrame, template : asFrame === true ? 'frame-header' : 'page-home', name : 'Header', slug : '', restore : true, sources : 'block', props : 'panel',
			call : function() {}, base : function() { return { template : asFrame === true ? 'frame-header' : 'page-home', locale : 'de_DE' } }, go : function() {}, href : function() { return '#dummy' }, set : function() {}, show : function() {}, fail : function() {},
			back : function() { return { parts : [], name : 'Frames', keep : false, block : false } },
		};

		level_.id = 'frame-level';
		w.page.appendChild( level_ );
		w.requests.length = 0;
		w.panel.editor.open( adapter, pageAnswer( { template : adapter.template, frame : asFrame, tree : JSON.parse( JSON.stringify( tree ) ), known : known, schemas : Object.assign( {}, pageAnswer().schemas, schemas ), level : level, texts : {} } ) );

		return { w : w, S : w.panel.model.state, rows : function() { return findAll( level_, function( el ) { return el.tagName === 'LI' && el.dataset.id !== undefined } ) } };
	};

	// the buttons of the row itself, not of the rows inside it
	const buttonsOf = function( row ) { return findAll( row.querySelector('.nino-admin-tree-row') ?? row, function( el ) { return el.tagName === 'BUTTON' && el.dataset.key !== undefined } ).map( function( button ) { return button.dataset.key } ) };
	const rowsOf = function( x ) { const out = {}; x.rows().forEach( function( row ) { out[row.dataset.id] = textOf( row ).replace( /\s+/g, ' ' ).trim() + ' | ' + buttonsOf( row ).join() } ); return out };
	const frameDesign = sheet_( true, 2 );
	const pageDesign = sheet_( false, 2 );
	const frameRows = rowsOf( frameDesign );
	const pageRows = rowsOf( pageDesign );
	const inner = function( rows ) { const out = {}; Object.keys( rows ).filter( function( id ) { return id.indexOf('1.') === 0 } ).forEach( function( id ) { out[id] = rows[id] } ); return out };
	const html = frameDesign.rows().find( function( row ) { return /^HTML/.test( textOf( row ) ) } );
	const develop = sheet_( true, 3 );
	const developHtml = develop.rows().find( function( row ) { return /^HTML/.test( textOf( row ) ) } );

	check( 'L. a frame offers the step that designs what a page offers it: the rows inside the header, with the buttons of each row - the call of html and the free text included - are those of the same tree on a page, row for row',
		Object.keys( inner( frameRows ) ).length >= 4 && JSON.stringify( inner( frameRows ) ) === JSON.stringify( inner( pageRows ) ) && frameDesign.S.frame === true && pageDesign.S.frame === false && frameDesign.S.level === 2 );
	check( 'L. what is offered is what the server takes of a page, and of a frame the same: the call of html has the button that takes it away and no button that copies it, and the free text has the buttons of a locked piece',
		html !== undefined && buttonsOf( html ).indexOf('remove') !== -1 && buttonsOf( html ).indexOf('copy') === -1 && buttonsOf( html ).length > 0 );
	check( 'L. for the step that holds the right of Themelab too the call of html is a component like any other: it has the buttons of one, and what it holds is a row of its own',
		developHtml !== undefined && buttonsOf( developHtml ).length > 0 && develop.S.level === 3 && develop.rows().some( function( row ) { return row.dataset.id === '1.1.0' } ) === true );
}


{
	// The three choices of a setting with a value for each width stand one under the other, each with its device in front, where the box is too narrow for them side by side
	const narrow = /@container \(max-width: 40rem\) \{[\s\S]*?\.builder-widths-grid \{\s*grid-template-columns: minmax\(0, 1fr\);[\s\S]*?\.builder-widths-column \{\s*grid-template-columns: auto minmax\(0, 1fr\);/.test( sheet );

	check( 'the choices of the three widths stand one under the other, the device in front of each, where their box is narrower than 40rem - and the box is the container that is asked', narrow === true && /\.builder-lightbox \.builder-widths-set \{\s*container-type: inline-size;/.test( sheet ) );
}

done();
