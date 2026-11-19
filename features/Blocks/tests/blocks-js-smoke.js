/**
 *	Nino features
 *	blocks-js-smoke.js	What the script of the Blocks draws, over the real Nino.adminUi of the workbench and the script of the Builder, whose editor it works with - the
 *											page in the frame is stood in for, as the frame of a browser is not here:
 *
 *											A. the list: a row for each block of the tab, the tab of the theme only where there is a theme in use, the empty state, what a row can do
 *											   in the project and in the theme, a click that opens the block
 *											B. the forms: a new block with a name in each language, a category and what it begins with; a copy, a new name, the removal - what each
 *											   asks of the server and where it says what the server refused
 *											C. the sheet of a block: opened through the editor of the Builder with the adapter of this panel, the way back to the list on its tab, Save
 *											   as in the bar - and the block of a theme only looked at: nothing of it changes, nothing is saved
 *
 *											The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js of the checkout, three
 *											levels up or the one NINO_ROOT names. blocks-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Blocks/tests/blocks-js-smoke.js
 *	       NINO_ROOT=../nino node features/Blocks/tests/blocks-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, documentOf } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const builderDir = path.join( featureDir, '../Builder' );
const scriptPath = path.join( featureDir, 'assets/admin.js' );
const builderPath = path.join( builderDir, 'assets/admin.js' );
const relative = path.relative( root, scriptPath );
const builderRelative = path.relative( root, builderPath );

const words = {
	en : Object.assign( {}, fills( path.relative( root, path.join( builderDir, 'text/en_US.php' ) ) ), fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ) ),
	de : Object.assign( {}, fills( path.relative( root, path.join( builderDir, 'text/de_DE.php' ) ) ), fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

/** The words of the panel by their keys: a test asks for the fill and never writes the sentence */
const W = function( key, ...params ) { return String( words.en['/_admin/blocks/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

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

	const blocksList = element('div');
	const blocksSheet = element('div');

	blocksList.id = 'blocks-list';
	blocksSheet.id = 'blocks-sheet';
	blocksSheet.className = 'admin-hidden';
	page.appendChild( blocksList );
	page.appendChild( blocksSheet );
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
			workLocale : { current : 'de_DE', native : 'de_DE', followers : {}, follow : function( name, fn ) { this.followers[name] = fn }, set : function( locale ) { const done = this.current; this.current = locale; Object.keys( this.followers ).forEach( function( name ) { this.followers[name]( locale ) }, this ); return done } },
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

	load( builderRelative, context );
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

	w.title.textContent = 'Blocks';
	w.panel = w.Nino.admin.blocks;
	w.builder = w.Nino.admin.builder;

	return w;
};

const LIST = function( extra ) {

	return Object.assign( {
		tab : 'project', theme : { name : 'Nino' },
		blocks : [
			{ key : 'karten', name : { de_DE : 'Karten', en_US : 'Cards' }, category : 'content', group : 'content', variants : 2, rank : 900 },
			{ key : 'held', name : 'Held', category : 'intro', group : 'intro', variants : 0, rank : 900 },
		],
		library : [ { key : 'hero-full', name : { en_US : 'Hero' }, origin : { en_US : 'Basis' }, own : false }, { key : 'karten', name : { en_US : 'Cards' }, origin : { en_US : 'Project' }, own : true } ],
		categories : [ 'intro', 'content', 'trust', 'offer', 'action', 'contact' ],
		contentLocale : 'de_DE', native : 'de_DE', locales : LOCALES, dir : '', shell : true, copy : { de_DE : ' Kopie', en_US : ' copy', fr_FR : ' copie' }, fresh : { de_DE : 'Neuer Block', en_US : 'New block', fr_FR : 'Nouveau bloc' },
	}, extra ?? {} );
};

/** The panel shown at an address, the list asked for and answered */
const shown = function( hash, list, options ) {

	const w = opened( options );

	w.state.hash = hash;
	w.panel.showCurrent();

	const asked = w.requests.shift();

	asked.callback( { status : 200, responseJSON : list ?? LIST(), getAllResponseHeaders : function() { return 'x' } } );

	return { w : w, asked : asked };
};

const rowsOfList = function( w ) { return findAll( w.page.querySelector('#blocks-list') ?? w.page, function( el ) { return el.tagName === 'TR' && el.dataset.row !== undefined } ) };
const labelsOf = function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' } ).map( function( el ) { return el.attributes['aria-label'] ?? el.title ?? '' } ) };
const tabsOf = function( w ) { return findAll( w.page, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).map( textOf ) };
const request = function( w, action ) { return w.requests.filter( function( entry ) { return entry.action === action } )[0] };
const answerOf = function( entry, status, body ) { entry.callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return 'x' } } ) };
const dialogOf = function( w ) { return w.dialogs[w.dialogs.length - 1] };
const pressed = function( dialog, label ) { fire( findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === label } )[0], 'click' ) };
const typed = function( dialog, values ) {
	findAll( dialog, function( el ) { return el.tagName === 'INPUT' && String( el.className ).indexOf('nino-admin-input') !== -1 } ).forEach( function( input, at ) { input.value = values[at]; fire( input, 'input' ) } );
};

// --- A. the list --------------------------------------------------------------------------------------------------------

{
	const { w, asked } = shown('#blocks');
	const rows = rowsOfList( w );

	check( 'A. the panel asks for the list of the tab the address names, in the language of the workbench, and draws a row for each block: its preview, its name with its key quietly under it, its category in words, its number of variants',
		asked.action === 'blocks/list' && asked.payload.tab === 'project' && asked.payload.locale === 'de_DE' && rows.length === 2 && textOf( rows[0] ).indexOf('Cards') !== -1 && textOf( rows[0] ).indexOf('karten') !== -1 && textOf( rows[0] ).indexOf( W('cat/content') ) !== -1 && textOf( rows[0] ).indexOf('2') !== -1
		&& rows[0].querySelector('.blocks-tile') !== null );
	check( 'A. the tabs are the project, the theme in use with its name, and the frames - the third; the theme tab is not there without a theme in use', ( function() {
		const none = shown( '#blocks', LIST( { theme : null } ) ).w;

		return tabsOf( w ).join() === W('tab/project')+ ','+ W( 'tab/theme', 'Nino' )+ ','+ W('tab/frames') && tabsOf( none ).join() === W('tab/project')+ ','+ W('tab/frames');
	} )() );
	check( 'A. a block of the project has three things to do in its row - duplicate, rename, delete - and each is named for the block; one of the theme has one: copy it as a block of the project', ( function() {
		const theme = shown( '#blocks/theme', LIST( { tab : 'theme' } ) ).w;
		const mine = labelsOf( rowsOfList( w )[0] );
		const theirs = labelsOf( rowsOfList( theme )[0] );

		return mine.length === 3 && mine[0].indexOf('Cards') !== -1 && mine[2].indexOf('Cards') !== -1 && theirs.length === 1 && theirs[0] === W( 'row/copy', 'Cards' );
	} )() );
	check( 'A. a list with no block says so - the project and the theme each in its words - and has the button of a new block', ( function() {
		const empty = shown( '#blocks', LIST( { blocks : [] } ) ).w;
		const none = shown( '#blocks/theme', LIST( { blocks : [], tab : 'theme' } ) ).w;

		return textOf( empty.page ).indexOf( W('msg/none-project') ) !== -1 && textOf( none.page ).indexOf( W('msg/none-theme') ) !== -1 && findAll( empty.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } ).length === 1;
	} )() );
	check( 'A. a click on a row opens the block on the tab it is on, and a click on the other tab goes to the list of that tab', ( function() {
		const x = shown('#blocks');

		fire( rowsOfList( x.w )[0], 'click' );

		const opened = x.w.state.hash === '#blocks/karten' && request( x.w, 'blocks/open' ) !== undefined && request( x.w, 'blocks/open' ).payload.key === 'karten';
		const theme = shown('#blocks');

		fire( findAll( theme.w.page, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } )[1], 'click' );

		return opened === true && theme.w.state.hash === '#blocks/theme';
	} )() );
}

// --- B. the forms ---------------------------------------------------------------------------------------------------------

{
	const x = shown('#blocks');
	const w = x.w;

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );

	const dialog = dialogOf( w );
	const selects = findAll( dialog, function( el ) { return el.tagName === 'SELECT' } );
	const inputs = findAll( dialog, function( el ) { return el.tagName === 'INPUT' && String( el.className ).indexOf('nino-admin-input') !== -1 } );

	check( 'B. a new block asks for a name in each language of the site, for a category - those of the kernel but the frames - and for what it begins with: nothing, or the copy of a block of the library with the name of its source',
		inputs.length === 2 && selects.length === 2 && findAll( selects[0], function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.value } ).join() === 'intro,content,trust,offer,action,contact'
		&& findAll( selects[1], function( el ) { return el.tagName === 'OPTION' } ).map( textOf ).join('|') === W('new/empty')+ '|'+ W( 'new/copy-of', 'Hero' )+ ' · Basis|'+ W( 'new/copy-of', 'Cards' )+ ' · '+ W('tab/project') );

	typed( dialog, [ 'Neu', 'New' ] );
	pressed( dialog, W('new/submit') );

	const sent = request( w, 'blocks/create' );

	check( 'B. a new block is asked of the server with its names by language, its category and what it begins with - and where the server says the key is taken, it is said at the name and the dialog stays', sent !== undefined && sent.payload.name.de_DE === 'Neu' && sent.payload.name.en_US === 'New' && sent.payload.category === 'content' && sent.payload.from === ''
		&& ( function() {
			w.requests.length = 0;
			answerOf( sent, 409, { error : 'taken', code : 'blocks_taken', params : [ 'neu' ], field : 'name' } );

			return findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-field-error') !== -1 } ).length === 1 && w.state.hash === '#blocks';
		} )() );
}

{
	const w = shown('#blocks').w;
	const row = rowsOfList( w )[0];

	fire( findAll( row, function( el ) { return el.tagName === 'BUTTON' } )[1], 'click' );

	const rename = dialogOf( w );

	pressed( rename, W('rename/submit') );

	const sent = request( w, 'blocks/rename' );

	check( 'B. a block is renamed with the names it has in each language in the fields - and the request names the key and the names', sent !== undefined && sent.payload.key === 'karten' && sent.payload.name.de_DE === 'Karten' && sent.payload.name.en_US === 'Cards' );

	const second = shown('#blocks').w;

	fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );
	typed( dialogOf( second ), [ 'Zwei', 'Two' ] );
	pressed( dialogOf( second ), W('dup/submit') );

	const copied = request( second, 'blocks/duplicate' );

	check( 'B. a block is duplicated under a new name in each language: the request names the block and the names', copied !== undefined && copied.payload.key === 'karten' && copied.payload.name.de_DE === 'Zwei' && copied.payload.name.en_US === 'Two' );

	const third = shown('#blocks').w;

	fire( findAll( rowsOfList( third )[0], function( el ) { return el.tagName === 'BUTTON' } )[2], 'click' );

	const question = dialogOf( third );

	check( 'B. a block is taken away after a question that says the pages that have it keep their own copy - and the answer yes asks the server to remove that key', ( function() {
		const said = textOf( question );

		pressed( question, W('delete/title') );
		fire( question, 'close' );

		const removal = request( third, 'blocks/remove' );

		return said.indexOf( W( 'delete/message', 'Cards' ) ) !== -1 && removal !== undefined && removal.payload.key === 'karten';
	} )() );

	const theme = shown( '#blocks/theme', LIST( { tab : 'theme' } ) ).w;

	fire( findAll( rowsOfList( theme )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );
	typed( dialogOf( theme ), [ 'Eigen', 'Own' ] );
	pressed( dialogOf( theme ), W('dup/submit-theme') );

	const made = request( theme, 'blocks/duplicate' );

	check( 'B. a block of the theme is copied as a block of the project by the same request as a duplicate - and it is the project that is shown afterwards', ( function() {
		answerOf( made, 200, { key : 'eigen' } );

		return made !== undefined && made.payload.key === 'karten' && theme.state.hash === '#blocks';
	} )() );
}

// --- C. the sheet of a block ------------------------------------------------------------------------------------------------

const FRAME_HEAD = { n : 'template', s : '/templates/html-header', a : {}, c : null };
const FRAME_FOOT = { n : 'template', s : '/templates/html-footer', a : {}, c : null };

const SHEET = function( extra ) {

	return pageAnswer( Object.assign( {
		key : 'karten', editable : true, stamp : 'b1', name : { de_DE : 'Karten', en_US : 'Cards' }, fresh : {},
		tree : [ FRAME_HEAD, { t : '\n' }, block( 'karten', columns ), { t : '\n' }, FRAME_FOOT, { t : '\n' } ], level : 2, texts : {}, address : '/home', contentLocale : 'de_DE',
	}, extra ?? {} ) );
};

const sheetOf = function( hash, extra ) {

	const x = shown( hash );
	const w = x.w;

	w.requests.length = 0;
	w.panel.follow();

	answerOf( request( w, 'blocks/open' ), 200, SHEET( extra ) );

	return w;
};

{
	const w = sheetOf('#blocks/karten');
	const sheet = w.builder.editor.state();
	const back = findAll( w.page.querySelector('#blocks-sheet') ?? w.page, function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0];
	const saveas = sheet.view === null ? undefined : sheet.view.buttons.saveas;

	check( 'C. a block is opened as a sheet of the editor of the Builder with the adapter of this panel: the tree view of the block is drawn, the requests of the draft carry the key of the block and go to this panel, and the way back is the list on the tab the block is on',
		sheet.sheet === 'block' && sheet.adapter.panel === 'blocks' && sheet.mode === 'advanced' && ( back.attributes.href ?? back.href ) === '#blocks' && textOf( back ).indexOf( W('title') ) !== -1 && w.requests.filter( function( entry ) { return /^blocks\//.test( entry.action ) === true } ).every( function( entry ) { return entry.payload.key === 'karten' } )
		&& w.requests.filter( function( entry ) { return /^builder\//.test( entry.action ) === true } ).length === 0 && sheet.adapter.readOnly === false );
	check( 'C. the lightbox of the root of a block has no way to keep it as a block of the project - the block is one - in the advanced part where the page has it', ( function() {
		w.builder.editor.properties( sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } ), 'root', element('button'), function() {} );

		const box = dialogOf( w );
		const advanced = findAll( box, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && textOf( el ).indexOf( String( words.en['/_admin/builder/box/advanced'] ) ) !== -1 } )[0];

		fire( advanced, 'click' );

		const none = findAll( box, function( el ) { return el.dataset !== undefined && el.dataset.key === 'block:own' } ).length === 0 && textOf( box ).indexOf( String( words.en['/_admin/builder/own/open'] ) ) === -1;

		fire( box, 'close' );

		return none;
	} )() );
	check( 'C. the bar has Undo, Redo, Save as and Save, and Save as asks for the names of a new block and sends the sheet - the tree, the words and the pictures - with them; where the server says yes the editor goes to the new block', ( function() {
		fire( saveas, 'click' );

		const dialog = dialogOf( w );

		typed( dialog, [ 'Neu', 'New' ] );
		pressed( dialog, W('saveas/submit') );

		const sent = request( w, 'blocks/saveas' );

		answerOf( sent, 200, { key : 'neu' } );

		return saveas !== undefined && sent !== undefined && sent.payload.name.de_DE === 'Neu' && Array.isArray( sent.payload.tree ) === true && typeof sent.payload.texts === 'object' && typeof sent.payload.pictures === 'object' && sent.payload.key === 'karten' && w.state.hash === '#blocks/neu';
	} )() );
}

{
	const choice = { type : 'choice', label : 'Ground', hint : '', default : 'base', options : [ 'base', 'dark' ], optionLabels : null, responsive : false };
	const w = sheetOf( '#blocks/theme/karten', { editable : false, schemas : Object.assign( {}, pageAnswer().schemas, { 'section-wrap' : schema( 'Section', 'panel-top', 'none', { attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' }, ground : choice } } ) } ) } );
	const sheet = w.builder.editor.state();
	const model = w.builder.model;
	const before = JSON.stringify( sheet.top );
	const notice = findAll( w.page.querySelector('#blocks-sheet') ?? w.page, function( el ) { return String( el.className ).indexOf('nino-admin-notice') !== -1 } );

	// the properties of a component are looked at, and nothing in them is changed
	w.builder.editor.properties( sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } ), 'root', element('button'), function() {} );

	const box = dialogOf( w );
	const look = findAll( box, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && textOf( el ).indexOf( String( words.en['/_admin/builder/box/look'] ) ) !== -1 } )[0];

	fire( look, 'click' );

	const controls = findAll( box, function( el ) { return /^(INPUT|SELECT|TEXTAREA)$/.test( el.tagName ) || ( el.tagName === 'BUTTON' && el.attributes['role'] === 'radio' ) } );

	model.setAttr( 'root', 'ground', 'dark' );
	model.move( 2, 1 );

	check( 'C. a block of the theme is a sheet that is only looked at: it says so in a quiet badge beside the state of the bar - with the reason as its tooltip - and in no box above the tree, the way back is the theme tab, Save is not there and "Save to own blocks" is, no change is taken - a setting, a move - and the lightbox of a component has no control that is on',
		sheet.adapter.readOnly === true && notice.length === 0 && ( function() {

			const badge = findAll( w.page, function( el ) { return String( el.className ).indexOf('builder-state-badge') !== -1 } )[0];

			return badge !== undefined && badge.dataset.state === 'muted' && badge.title === W('readonly/reason') && textOf( badge ).indexOf( W('readonly/badge') ) !== -1 && String( badge.className ).indexOf('nino-admin-badge') !== -1 && textOf( sheet.view.buttons.saveas ).trim() === W('saveas/button-theme');
		} )() && sheet.view.bar.save.hidden === true && sheet.view.buttons.saveas !== undefined && JSON.stringify( sheet.top ) === before && sheet.undo.length === 0
		&& controls.length > 0 && controls.every( function( control ) { return control.disabled === true } ) && ( function( link ) { return link.attributes.href ?? link.href } )( findAll( w.page, function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0] ) === '#blocks/theme' );
}

// --- D. the fixes of the first round --------------------------------------------------------------------------------------------

// The dialogs begin with a name: a new block with "New block" in each language, a copy with the name and the word of a copy in each language, a rename with the name; only the working
// language is required, and the others stand in an accordion of their own, closed
{
	const w = shown('#blocks').w;
	const inputsOf = function( dialog ) { return findAll( dialog, function( el ) { return el.tagName === 'INPUT' && String( el.className ).indexOf('nino-admin-input') !== -1 } ) };

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );

	const fresh = dialogOf( w );
	const more = findAll( fresh, function( el ) { return String( el.className ).indexOf('nino-admin-accordion') !== -1 && el.tagName === 'DIV' && el.className === 'nino-admin-accordion' } );

	check( 'D. a new block begins with a name in the working language - the word of the list answers - and the language that is not the working one is empty and stands in an accordion that is closed',
		inputsOf( fresh ).map( function( input ) { return input.value } ).join('|') === 'Neuer Block|' && more.length === 1 && textOf( more[0] ).indexOf( W('new/more-languages') ) !== -1 && ( function() {
			const head = findAll( more[0], function( el ) { return el.tagName === 'BUTTON' } )[0];
			const closed = head.attributes['aria-expanded'] === 'false';

			fire( head, 'click' );

			return closed === true && head.attributes['aria-expanded'] === 'true' && textOf( more[0] ).indexOf( W('new/more-note') ) !== -1 && findAll( more[0], function( el ) { return el.tagName === 'INPUT' } ).length === 1;
		} )() );

	// a name in the working language alone is sent: the other language is empty and the server gives it the name of the working language
	pressed( fresh, W('new/submit') );

	const alone = request( w, 'blocks/create' );

	check( 'D. the working language is the one that is required: a name in it alone is sent, with the other language empty', alone !== undefined && alone.payload.name.de_DE === 'Neuer Block' && alone.payload.name.en_US === '' );

	fire( fresh, 'close' );
	w.requests.length = 0;
	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );

	const none = dialogOf( w );

	inputsOf( none )[0].value = '  ';
	pressed( none, W('new/submit') );

	check( 'D. without a name in the working language nothing is sent and the field says so', request( w, 'blocks/create' ) === undefined && textOf( none ).indexOf( W('new/name-missing') ) !== -1 );

	fire( none, 'close' );

	const second = shown('#blocks').w;

	fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );

	check( 'D. a copy begins with the name of the block and the word of a copy in each language, a rename with the name of the block in each language',
		inputsOf( dialogOf( second ) ).map( function( input ) { return input.value } ).join('|') === 'Karten Kopie|Cards copy' && ( function() {
			fire( dialogOf( second ), 'close' );
			fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[1], 'click' );

			return inputsOf( dialogOf( second ) ).map( function( input ) { return input.value } ).join('|') === 'Karten|Cards';
		} )() );
}

// A block that was just made is opened with its first column chosen, and a block with no text is opened with the texts as a map
{
	const x = shown('#blocks');
	const w = x.w;

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );
	pressed( dialogOf( w ), W('new/submit') );
	answerOf( request( w, 'blocks/create' ), 200, { key : 'karten' } );
	w.requests.length = 0;
	w.panel.follow();
	answerOf( request( w, 'blocks/open' ), 200, SHEET( { texts : [] } ) );

	const sheet = w.builder.editor.state();

	check( 'D. a block that was just made is opened with its first column - the Spalte of its first row - as the chosen entry of the tree, so that the plus is at hand; the one that is opened from the list is chosen at its root',
		sheet.marked === '3.0' && sheet.tree.get().selected === '3.0' && sheet.tree.el.querySelector('[data-id="3.0"]').attributes['aria-selected'] === 'true' && sheet.sheet === 'block' );

	check( 'D. an answer with no text - the empty list of JSON - is the texts as an empty map: a text that is typed is a key of it', Array.isArray( sheet.texts ) === false && typeof sheet.texts === 'object' && ( function() { sheet.texts['/template/page-home/karten/title'] = { de_DE : 'x' }; return JSON.stringify( sheet.texts ).indexOf('"x"') !== -1 } )() );

	// a component put into a column that has none opens the column and is the chosen entry
	const model = w.builder.model;
	const column = '3.0';

	model.addComponent( column, { name : 'section-title', node : { n : 'section-title', s : '', a : {}, c : null } }, '\n\t' );

	const chosen = sheet.tree.get().selected;
	const holder = sheet.tree.el.querySelector('[data-id="'+ column+ '"]');

	check( 'D. a component that is put into a column with nothing in it opens the column and is the chosen entry - not a column folded over it',
		chosen.indexOf( column+ '.' ) === 0 && holder.attributes['aria-expanded'] === 'true' && sheet.tree.el.querySelector('[data-id="'+ chosen+ '"]') !== null );
}

// What a sheet that is only looked at does: nothing - a key, a button of the tree, a call of the model - and it says nothing that is not so
{
	const w = sheetOf( '#blocks/theme/karten', { editable : false } );
	const sheet = w.builder.editor.state();
	const model = w.builder.model;
	const before = JSON.stringify( sheet.top );
	const rows = findAll( sheet.tree.el, function( el ) { return el.tagName === 'LI' && el.dataset.id !== undefined } );
	const buttons = findAll( sheet.tree.el, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-label'] !== undefined } ).map( function( el ) { return el.attributes['aria-label'] } );
	const title = String( words.en['/_admin/builder/msg/component-removed'] ).split('%s').pop();

	check( 'D. the rows of the tree of a block that is only looked at have the one button that looks - edit - and no plus, no arrow and no cross', buttons.length > 0 && buttons.every( function( label ) { return /edit/i.test( label ) === true } ) );

	const asked = w.dialogs.length;

	rows.forEach( function( row ) { fire( sheet.tree.el, 'keydown', { key : 'Delete', target : row } ) } );

	const answers = [ model.removeComponent( '1' ), model.moveComponent( '1', 1 ), model.addComponent( '3.0', { name : 'section-title', node : { n : 'section-title', s : '', a : {}, c : null } }, '\n' ) ];
	const line = textOf( sheet.view.bar.el );

	check( 'D. in a block that is only looked at Delete asks nothing, and removing, moving and adding a component do nothing - the draft is as it was, no step to undo - and the bar does not say that a component is gone',
		w.dialogs.length === asked && answers.every( function( answer ) { return answer === false || answer === undefined } ) && JSON.stringify( sheet.top ) === before && sheet.undo.length === 0 && ( title === '' || line.indexOf( title ) === -1 ) );
}

// The working language that is changed while a block is open is the business of this panel: the Builder, whose editor it is, opens no page for it - there is none
{
	const w = sheetOf('#blocks/karten');

	w.requests.length = 0;
	w.Nino.admin.workLocale.set('en_US');

	const asked = w.requests.map( function( entry ) { return entry.action } );

	check( 'D. a change of the working language while a block is open asks for no page of the Builder - no builder/open for the key of a block - and asks this panel for the block in the new language',
		asked.filter( function( action ) { return /^builder\//.test( action ) === true } ).length === 0 && asked.filter( function( action ) { return /^blocks\//.test( action ) === true } ).length > 0 );
}

// The tabs stand in the head of the pane, beside its name - where the workbench keeps the tabs of a panel - and not over the list; the first is "Own blocks"
{
	const x = shown('#blocks');
	const head = x.w.page.querySelector('.admin-panel-head');
	const inHead = head === null ? [] : findAll( head, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).map( textOf );
	const inList = findAll( x.w.page.querySelector('#blocks-list'), function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).length;

	check( 'D. the tabs of the list stand in the head of the pane - "Own blocks", the theme and the frames - and none stands in the list itself', inHead.join() === W('tab/project')+ ','+ W( 'tab/theme', 'Nino' )+ ','+ W('tab/frames') && inList === 0 && W('tab/project') === 'Own blocks' && String( words.de['/_admin/blocks/tab/project'] ) === 'Eigene Blocks' );
}

// "Save as" in a block of one's own keeps its name and says what it does behind a (?); in a block of the theme it is "Save to own blocks" - and neither has a paragraph over its field
{
	const dialogOfSave = function( hash ) {

		const w = sheetOf( hash, hash === '#blocks/theme/karten' ? { editable : false } : undefined );

		fire( w.builder.editor.state().view.buttons.saveas, 'click' );

		const dialog = dialogOf( w );
		const message = findAll( dialog, function( el ) { return el.tagName === 'P' } ).filter( function( el ) { return String( el.className ).indexOf('nino-admin-field') === -1 } );

		return { title : textOf( dialog.querySelector('.nino-admin-dialog-title') ), submit : findAll( dialog, function( el ) { return el.tagName === 'BUTTON' } ).map( function( el ) { return textOf( el ).trim() } ), paragraphs : message.map( textOf ).join(' '), help : findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-help-button') !== -1 } ).length };
	};
	const mine = dialogOfSave('#blocks/karten');
	const theirs = dialogOfSave('#blocks/theme/karten');

	check( 'D. "Save as" of an own block is called so and has its sentence behind a (?) at the name; of a block of the theme it is "Save to own blocks" - the title and the button - and with no (?) of its own; neither dialog has a paragraph above its field, and the name is the one field',
		mine.title === W('saveas/title') && mine.submit.indexOf( W('saveas/submit') ) !== -1 && mine.help === 1 && mine.paragraphs.indexOf( W('saveas/help') ) === -1 && theirs.title === W('saveas/title-theme') && theirs.submit.indexOf( W('saveas/submit-theme') ) !== -1 && theirs.help === 0 && theirs.paragraphs === '' );
}

// The title of the editor is the NAME of the block, with its key quietly behind it - and not the kind of the root and the key
{
	const w = sheetOf('#blocks/karten');
	const context = findAll( w.page, function( el ) { return String( el.className ).indexOf('nino-admin-context') !== -1 } ).map( textOf ).join(' ');

	check( 'D. the title of the editor of a block is the name of the block in the language of the workbench - with its key quietly behind it - and not "Section" and a key', context.indexOf('Cards') !== -1 && context.indexOf('karten') !== -1 && context.indexOf( 'Section ·' ) === -1 );
}

// The dialog of a source says "block" where the sheet is a block: the lightbox names the text and the picture of the block, and the dialog offers to choose and to make - nothing else
{
	const w = sheetOf('#blocks/karten', { texts : { '/template/page-home/karten/title' : { de_DE : 'Karten', en_US : 'Cards' } }, sources : [ { key : '/template/page-home/karten/title', kind : 'text', name : 'title' } ] } );
	const sheet = w.builder.editor.state();
	const root = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );

	w.builder.editor.properties( root, '1', element('button'), function() {} );

	const box = dialogOf( w );

	fire( findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === String( words.en['/_admin/builder/source/change'] ) } )[0], 'click' );

	const choose = dialogOf( w );
	const tabs = findAll( choose, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).map( function( el ) { return textOf( el ).trim() } );

	check( 'D. the dialog of a source on a block says where the text comes from as the block\'s - "of this block" - and not as the page\'s',
		textOf( choose ).indexOf( String( words.en['/_admin/builder/source/is-key-block'] ).split('%s')[1].split(':')[0] ) !== -1 && textOf( choose ).indexOf( 'of this page' ) === -1 );

	check( 'D. the dialog of a source on a block has the two tabs a block can carry - choose a value and new - and no fixed text, no page and no address', tabs.join('|') === [ words.en['/_admin/builder/source/tab-pick'], words.en['/_admin/builder/source/tab-new'] ].join('|') );
}


done();
