/**
 *	Nino features
 *	themelab-js-smoke.js	What the script of Themelab draws, over the real Nino.adminUi of the workbench and the script of the Builder, whose editor it works with - the
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
 *											levels up or the one NINO_ROOT names. themelab-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Themelab/tests/themelab-js-smoke.js
 *	       NINO_ROOT=../nino node features/Themelab/tests/themelab-js-smoke.js
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
const W = function( key, ...params ) { return String( words.en['/_admin/themelab/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

/** What an element and everything in it says */
const textOf = function( node ) { return String( node.textContent ?? '' )+ ( node.children ?? [] ).map( textOf ).join('') };

const LOCALES = [ { locale : 'de_DE', active : true }, { locale : 'en_US', active : true } ];

/** The answer of the list: the pages and the two frames */
const answer = function( extra ) {

	return Object.assign( {
		pages : [ { template : 'page-home', name : 'Home', path : '/', locales : [ 'de_DE', 'en_US' ], navs : [], complete : true, blocks : 3, saved : 1763356920 } ],
		frames : [ { key : 'header', side : 'header', template : 'frame-header', default : true, names : {}, name : '', uses : 0 }, { key : 'footer', side : 'footer', template : 'frame-footer', default : true, names : {}, name : '', uses : 0 } ],
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
		schemas : { 'section-wrap' : schema( 'Section', 'panel-top', 'none', { side : 'page' } ), 'section-title' : schema( 'Title', 'heading', 'text' ), collection : schema( 'Collection', 'database', 'none', { loop : true } ), 'section-row' : schema( 'Row', 'rows-2', 'none' ), 'section-col' : schema( 'Column', 'columns-3', 'none' ) },
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
	const posted = [];
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

	const themelabList = element('div');
	const themelabSheet = element('div');

	themelabList.id = 'themelab-list';
	themelabSheet.id = 'themelab-sheet';
	themelabSheet.className = 'admin-hidden';
	page.appendChild( themelabList );
	page.appendChild( themelabSheet );
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
		if( tag === 'form' )
			created.submit = function() { posted.push( created ) };

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

	const observers = [];

	sandbox.window = {
		Nino : Nino,
		location : { hash : '', href : 'http://workbench.test/_admin/' },
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
		clearTimeout : function() {},
	};

	// A browser that tells when a box is on screen: the test says when, by seeing( box )
	if( options.observer === true )
		sandbox.window.IntersectionObserver = function( callback ) {
			this.callback = callback;
			this.nodes = [];
			this.gone = false;
			this.observe = function( node ) { this.nodes.push( node ) };
			this.disconnect = function() { this.gone = true };
			observers.push( this );
		};

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );

	// The names of the languages, which the shell hands over once
	Nino.adminUi.locales = { native : 'de_DE', names : { de_DE : 'Deutsch', en_US : 'English (US)', fr_FR : 'Français' } };
	Nino.adminUi.say = function( text ) { said.push( text ) };

	load( builderRelative, context );
	load( relative, context );

	const w = {
		Nino : Nino, observers : observers, view : view, title : title, timers : timers, ui : Nino.adminUi, doc : doc, page : page, list : list, form : form, requests : requests, posted : posted, dialogs : dialogs, said : said, answers : answers, state : state, dirty : dirty,
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
	w.panel = w.Nino.admin.themelab;
	w.builder = w.Nino.admin.builder;

	return w;
};

const LIST = function( extra ) {

	return Object.assign( {
		tab : 'blocks', theme : { key : 'nino', name : 'Nino', version : '1.2.0', origin : 'package' }, sources : [ { key : 'nino', label : { en_US : 'Nino', de_DE : 'Nino' }, theme : true } ], counts : { blocks : 2, frames : 2 },
		blocks : [
			{ key : 'karten', name : { de_DE : 'Karten', en_US : 'Cards' }, description : { de_DE : 'Vier Karten', en_US : 'Four cards' }, category : 'content', group : 'content', variants : 2, variantNames : [ { de_DE : 'Standard', en_US : 'Standard' }, { de_DE : 'Dunkel', en_US : 'Dark' } ], rank : 900, origin : 'project', own : true, openable : true },
			{ key : 'held', name : 'Held', category : 'intro', group : 'intro', variants : 0, rank : 900, origin : 'project', own : true, openable : true },
		],
		library : [ { key : 'hero-full', name : { en_US : 'Hero' }, origin : { en_US : 'Basis' }, own : false }, { key : 'karten', name : { en_US : 'Cards' }, origin : { en_US : 'Project' }, own : true } ],
		categories : [ 'intro', 'content', 'trust', 'offer', 'action', 'contact' ],
		contentLocale : 'de_DE', native : 'de_DE', locales : LOCALES, dir : '', shell : true, site : 'example.test', package : { bytes : 2097152, version : '1.0.0', name : { de_DE : 'Blocks von %s', en_US : 'Blocks of %s' } }, copy : { de_DE : ' Kopie', en_US : ' copy', fr_FR : ' copie' }, fresh : { de_DE : 'Neuer Block', en_US : 'New block', fr_FR : 'Nouveau bloc' },
	}, extra ?? {} );
};

/** The list as the server answers it where the blocks are the theme's: the same rows, as those of the theme in use - the choice of the source is what shows them */
const THEME = function( extra ) {

	const own = LIST();

	return LIST( Object.assign( { blocks : own.blocks.map( function( block ) { return Object.assign( {}, block, { origin : 'nino', own : false, openable : true } ) } ) }, extra ?? {} ) );
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

const rowsOfList = function( w ) { return findAll( w.page.querySelector('#themelab-list') ?? w.page, function( el ) { return el.tagName === 'TR' && el.dataset.row !== undefined } ) };
const labelsOf = function( node ) { return findAll( node, function( el ) { return el.tagName === 'BUTTON' } ).map( function( el ) { return el.attributes['aria-label'] ?? el.title ?? '' } ) };
const tabsOf = function( w ) { return findAll( w.page, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).map( textOf ) };
const request = function( w, action ) { return w.requests.filter( function( entry ) { return entry.action === action } )[0] };
const answerOf = function( entry, status, body ) { entry.callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return 'x' } } ) };
const boxOf = function( w ) { return findAll( w.page, function( el ) { return el.tagName === 'SECTION' && /(^| )builder-props( |$)/.test( String( el.className ) ) } )[0] };
const leaveBox = function( w ) { const open = w.builder.editor.state().box; if( open !== null && open.panel === true ) open.leave( true ) };
const dialogOf = function( w ) { return w.dialogs[w.dialogs.length - 1] };
const pressed = function( dialog, label ) { fire( findAll( dialog, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === label } )[0], 'click' ) };
const typed = function( dialog, values ) {
	findAll( dialog, function( el ) { return el.tagName === 'INPUT' && String( el.className ).indexOf('nino-admin-input') !== -1 } ).forEach( function( input, at ) { input.value = values[at]; fire( input, 'input' ) } );
};

// --- A. the list --------------------------------------------------------------------------------------------------------

{
	const { w, asked } = shown('#themelab');
	const rows = rowsOfList( w );

	check( 'A. the panel asks for the list of the tab the address names, in the language of the workbench, and draws a row for each block: its preview, its name with its key quietly under it, its category in words, its number of variants',
		asked.action === 'themelab/list' && asked.payload.tab === 'blocks' && asked.payload.locale === 'de_DE' && rows.length === 2 && textOf( rows[0] ).indexOf('Cards') !== -1 && textOf( rows[0] ).indexOf('karten') !== -1 && textOf( rows[0] ).indexOf( W('cat/content') ) !== -1 && textOf( rows[0] ).indexOf('2') !== -1
		&& rows[0].querySelector('.themelab-tile') !== null );
	const choiceOf = function( x ) { return findAll( x.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' } )[0] };
	const optionsOf = function( select ) { return findAll( select, function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.value+ '='+ textOf( el ) } ).join() };

	check( 'A. the tabs are the blocks and the theme - the frames of the site are no tab of this panel; where the blocks come from is a choice beside the search - all, the own ones, and each source by its name - and the choice is the one of the own blocks to begin with', ( function() {
		const none = shown( '#themelab', LIST( { theme : null, sources : [] } ) ).w;

		return tabsOf( w ).join() === W('tab/blocks')+ ','+ W('tab/info') && words.en['/_admin/themelab/tab/frames'] === undefined && words.de['/_admin/themelab/tab/frames'] === undefined && tabsOf( none ).join() === tabsOf( w ).join()
			&& optionsOf( choiceOf( w ) ) === 'all='+ W('source/all')+ ',own='+ W('tab/project')+ ',nino='+ W( 'tab/theme', 'Nino' ) && choiceOf( w ).value === 'own' && optionsOf( choiceOf( none ) ) === 'all='+ W('source/all')+ ',own='+ W('tab/project');
	} )() );
	check( 'A. the third tab is the theme in use and only reads: its name, version and origin, how many blocks and frames the project has, and what a theme package will hold - with a (?) that says export and import follow, and no button, no field, no bar of actions', ( function() {
		const x = shown( '#themelab/@theme', LIST( { tab : 'info' } ) );
		const page = textOf( x.w.page );
		const view = findAll( x.w.page, function( el ) { return String( el.className ).split(' ').indexOf('themelab-info') !== -1 } )[0];
		const writing = view === undefined ? [ 'none' ] : findAll( view, function( el ) { return /^(INPUT|SELECT|TEXTAREA)$/.test( el.tagName ) || ( el.tagName === 'BUTTON' && String( el.className ).indexOf('nino-admin-help') === -1 && el.attributes['aria-expanded'] === undefined ) } );

		return x.asked.payload.tab === 'info' && page.indexOf('1.2.0') !== -1 && page.indexOf( W('info/origin-package') ) !== -1 && page.indexOf( W('info/package-design') ) !== -1 && writing.length === 0 && page.indexOf( W('new/button') ) === -1;
	} )() );
	check( 'A. a block of the project has three things to do in its row - duplicate, rename, delete - and each is named for the block; one of the theme has one: copy it as a block of the project', ( function() {
		const theme = shown( '#themelab/@blocks/nino', THEME() ).w;
		const mine = labelsOf( rowsOfList( w )[0] );
		const theirs = labelsOf( rowsOfList( theme )[0] );

		return mine.length === 3 && mine[0].indexOf('Cards') !== -1 && mine[2].indexOf('Cards') !== -1 && theirs.length === 1 && theirs[0] === W( 'row/copy', 'Cards' );
	} )() );
	check( 'F294 T1. the choice of the source stands at the left of the search - before it in the toolbar - and filters at once with the search: all shows the own rows with their actions and those of the theme with the way to open and copy and the source named in the row, one source only its own rows; the choice is in the address and is kept - none of the tools of the own blocks is there for the rows of a theme', ( function() {
		const mixed = function() { return LIST( { blocks : LIST().blocks.concat( THEME().blocks.map( function( block ) { return Object.assign( {}, block, { key : 't-'+ block.key } ) } ) ) } ) };
		const all = shown( '#themelab/@blocks/all', mixed() ).w;
		const bar = findAll( all.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' || el.tagName === 'INPUT' && /nino-admin-(table-)?search|search/.test( String( el.className )+ String( el.attributes['aria-label'] ?? '' )+ String( el.attributes.placeholder ?? '' ) ) } );
		const rowsAll = rowsOfList( all );
		const ownTools = rowsAll.filter( function( row ) { return labelsOf( row ).length === 3 } ).length;
		const themeTools = rowsAll.filter( function( row ) { return labelsOf( row ).length === 1 } ).length;
		const origin = rowsAll.filter( function( row ) { return findAll( row, function( el ) { return String( el.className ).indexOf('builder-cell-origin') !== -1 } ).length === 1 } ).length;
		const own = shown( '#themelab', mixed() ).w;
		const ownBar = findAll( own.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).indexOf( W('new/button') ) !== -1 } ).length;
		const select = choiceOf( own );

		select.value = 'nino';
		fire( select, 'change' );

		const redrawn = own.state.hash === '#themelab/@blocks/nino';
		const theme = shown( '#themelab/@blocks/nino', mixed() ).w;
		const selects = findAll( theme.page, function( el ) { return el.tagName === 'INPUT' && el.attributes.type === 'checkbox' } ).length;
		const toolbar = findAll( theme.page, function( el ) { return el.tagName === 'BUTTON' && ( textOf( el ).indexOf( W('new/button') ) !== -1 || textOf( el ).indexOf( W('export/button') ) !== -1 ) } ).length;

		return bar.length === 2 && bar[0].tagName === 'SELECT' && rowsAll.length === 4 && ownTools === 2 && themeTools === 2 && origin === 4 && ownBar > 0 && redrawn === true && selects === 0 && toolbar === 0 && choiceOf( shown( '#themelab', mixed() ).w ) !== undefined;
	} )() );
	check( 'A. a list with no block says so - the project and the theme each in its words - and has the button of a new block', ( function() {
		const empty = shown( '#themelab', LIST( { blocks : [] } ) ).w;
		const none = shown( '#themelab/@blocks/nino', THEME( { blocks : [] } ) ).w;

		return textOf( empty.page ).indexOf( W('msg/none-project') ) !== -1 && textOf( none.page ).indexOf( W('msg/none-theme') ) !== -1 && findAll( empty.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } ).length === 1;
	} )() );
	check( 'A. the own blocks that the kernel does not read are said in ONE notice at the top of the lists that have the own blocks - each way of a refusal once, with the keys of its blocks, in the words of the panel - and in the list of a theme it is none',
		( function() {
			const refused = [ { key : 'alt-kopf', why : 'category', category : 'frame', side : '' }, { key : 'quer-kopf', why : 'side', category : 'footer', side : 'header' }, { key : 'kaputt', why : 'other', category : '', side : '' }, { key : 'alt-fuss', why : 'category', category : 'frame', side : '' } ];
			const page = shown( '#themelab', LIST( { refused : refused } ) ).w.page;
			const notices = findAll( page, function( el ) { return String( el.className ).indexOf('nino-admin-notice') !== -1 } );
			const theme = textOf( shown( '#themelab/@blocks/nino', THEME( { refused : refused } ) ).w.page );

			return notices.length === 1 && textOf( notices[0] ) === W( 'msg/refused', [ W( 'msg/refused-category', [ W( 'msg/refused-item', 'alt-kopf', 'frame' ), W( 'msg/refused-item', 'alt-fuss', 'frame' ) ].join( ', ' ) ), W( 'msg/refused-side', W( 'msg/refused-item-side', 'quer-kopf', 'footer', W('cat/header') ) ), W( 'msg/refused-other', W( 'msg/refused-key', 'kaputt' ) ) ].join( ' ' ) ) && theme.indexOf( 'alt-kopf' ) === -1;
		} )() );
	check( 'A. a click on a row opens the block, and the choice of another source is the address of the list of that source', ( function() {
		const x = shown('#themelab');

		fire( rowsOfList( x.w )[0], 'click' );

		const opened = x.w.state.hash === '#themelab/karten' && request( x.w, 'themelab/open' ) !== undefined && request( x.w, 'themelab/open' ).payload.key === 'karten';
		const theme = shown('#themelab');

		const select = findAll( theme.w.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' } )[0];

		select.value = 'nino';
		fire( select, 'change' );

		return opened === true && theme.w.state.hash === '#themelab/@blocks/nino';
	} )() );
}

// --- B. the forms ---------------------------------------------------------------------------------------------------------

{
	const x = shown('#themelab');
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

	const sent = request( w, 'themelab/create' );

	check( 'B. a new block is asked of the server with its names by language, its category and what it begins with - and where the server says the key is taken, it is said at the name and the dialog stays', sent !== undefined && sent.payload.name.de_DE === 'Neu' && sent.payload.name.en_US === 'New' && sent.payload.category === 'content' && sent.payload.from === ''
		&& ( function() {
			w.requests.length = 0;
			answerOf( sent, 409, { error : 'taken', code : 'themelab_taken', params : [ 'neu' ], field : 'name' } );

			return findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-field-error') !== -1 } ).length === 1 && w.state.hash === '#themelab';
		} )() );
}

{
	const w = shown('#themelab').w;
	const row = rowsOfList( w )[0];

	fire( findAll( row, function( el ) { return el.tagName === 'BUTTON' } )[1], 'click' );

	const rename = dialogOf( w );

	pressed( rename, W('details/submit') );

	const sent = request( w, 'themelab/rename' );

	check( 'B. a block is renamed with the names it has in each language in the fields - and the request names the key and the names', sent !== undefined && sent.payload.key === 'karten' && sent.payload.name.de_DE === 'Karten' && sent.payload.name.en_US === 'Cards' && sent.payload.description.de_DE === 'Vier Karten' && sent.payload.category === 'content' && String( sent.payload.rank ) === '900' );

	const second = shown('#themelab').w;

	fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );
	typed( dialogOf( second ), [ 'Zwei', 'Two' ] );
	pressed( dialogOf( second ), W('dup/submit') );

	const copied = request( second, 'themelab/duplicate' );

	check( 'B. a block is duplicated under a new name in each language: the request names the block and the names', copied !== undefined && copied.payload.key === 'karten' && copied.payload.name.de_DE === 'Zwei' && copied.payload.name.en_US === 'Two' );

	const third = shown('#themelab').w;

	fire( findAll( rowsOfList( third )[0], function( el ) { return el.tagName === 'BUTTON' } )[2], 'click' );

	const question = dialogOf( third );

	check( 'B. a block is taken away after a question that says the pages that have it keep their own copy - and the answer yes asks the server to remove that key', ( function() {
		const said = textOf( question );

		pressed( question, W('delete/title') );
		fire( question, 'close' );

		const removal = request( third, 'themelab/remove' );

		return said.indexOf( W( 'delete/message', 'Cards' ) ) !== -1 && removal !== undefined && removal.payload.key === 'karten';
	} )() );

	const theme = shown( '#themelab/@blocks/nino', THEME() ).w;

	fire( findAll( rowsOfList( theme )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );
	typed( dialogOf( theme ), [ 'Eigen', 'Own' ] );
	pressed( dialogOf( theme ), W('dup/submit-theme') );

	const made = request( theme, 'themelab/duplicate' );

	check( 'B. a block of the theme is copied as a block of the project by the same request as a duplicate - and it is the project that is shown afterwards', ( function() {
		answerOf( made, 200, { key : 'eigen' } );

		return made !== undefined && made.payload.key === 'karten' && theme.state.hash === '#themelab';
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

	answerOf( request( w, 'themelab/open' ), 200, SHEET( extra ) );

	return w;
};

{
	const w = sheetOf('#themelab/karten');
	const sheet = w.builder.editor.state();
	const back = findAll( w.page.querySelector('#themelab-sheet') ?? w.page, function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0];
	const saveas = sheet.view === null ? undefined : sheet.view.buttons.saveas;

	check( 'C. a block is opened as a sheet of the editor of the Builder with the adapter of this panel: the tree view of the block is drawn, the requests of the draft carry the key of the block and go to this panel, and the way back is the list on the tab the block is on',
		sheet.sheet === 'block' && sheet.adapter.panel === 'themelab' && sheet.mode === 'advanced' && ( back.attributes.href ?? back.href ) === '#themelab' && textOf( back ).indexOf( W('title') ) !== -1 && w.requests.filter( function( entry ) { return /^themelab\//.test( entry.action ) === true } ).every( function( entry ) { return entry.payload.key === 'karten' } )
		&& w.requests.filter( function( entry ) { return /^builder\//.test( entry.action ) === true } ).length === 0 && sheet.adapter.readOnly === false );
	check( 'C. the lightbox of the root of a block has no way to keep it as a block of the project - the block is one - in the advanced part where the page has it', ( function() {
		w.builder.editor.properties( sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } ), 'root', element('button'), function() {} );

		const box = boxOf( w );
		const advanced = findAll( box, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined && textOf( el ).indexOf( String( words.en['/_admin/builder/box/advanced'] ) ) !== -1 } )[0];

		fire( advanced, 'click' );

		const none = findAll( box, function( el ) { return el.dataset !== undefined && el.dataset.key === 'block:own' } ).length === 0 && textOf( box ).indexOf( String( words.en['/_admin/builder/own/open'] ) ) === -1;

		leaveBox( w );

		return none;
	} )() );
	check( 'C. the bar has Undo, Redo, Save as and Save, and Save as asks for the names of a new block and sends the sheet - the tree, the words and the pictures - with them; where the server says yes the editor goes to the new block', ( function() {
		fire( saveas, 'click' );

		const dialog = dialogOf( w );

		typed( dialog, [ 'Neu', 'New' ] );
		pressed( dialog, W('saveas/submit') );

		const sent = request( w, 'themelab/saveas' );

		answerOf( sent, 200, { key : 'neu' } );

		return saveas !== undefined && sent !== undefined && sent.payload.name.de_DE === 'Neu' && Array.isArray( sent.payload.tree ) === true && typeof sent.payload.texts === 'object' && typeof sent.payload.pictures === 'object' && sent.payload.key === 'karten' && w.state.hash === '#themelab/neu';
	} )() );
}

{
	const choice = { type : 'choice', label : 'Ground', hint : '', default : 'base', options : [ 'base', 'dark' ], optionLabels : null, responsive : false };
	const w = sheetOf( '#themelab/@theme-blocks/karten', { editable : false, schemas : Object.assign( {}, pageAnswer().schemas, { 'section-wrap' : schema( 'Section', 'panel-top', 'none', { side : 'page', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' }, ground : choice } } ) } ) } );
	const sheet = w.builder.editor.state();
	const model = w.builder.model;
	const before = JSON.stringify( sheet.top );
	const notice = findAll( w.page.querySelector('#themelab-sheet') ?? w.page, function( el ) { return String( el.className ).indexOf('nino-admin-notice') !== -1 } );

	// the properties of a component are looked at, and nothing in them is changed
	w.builder.editor.properties( sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } ), 'root', element('button'), function() {} );

	const box = boxOf( w );

	const controls = findAll( box, function( el ) { return /^(INPUT|SELECT|TEXTAREA)$/.test( el.tagName ) || ( el.tagName === 'BUTTON' && el.attributes['role'] === 'radio' ) } );

	model.setAttr( 'root', 'ground', 'dark' );
	model.move( 2, 1 );

	check( 'C. a block of the theme is a sheet that is only looked at: it says so in a quiet badge beside the state of the bar - with the reason as its tooltip - and in no box above the tree, the way back is the theme tab, Save is not there and "Save to own blocks" is, no change is taken - a setting, a move - and the lightbox of a component has no control that is on',
		sheet.adapter.readOnly === true && notice.length === 0 && ( function() {

			const badge = findAll( w.page, function( el ) { return String( el.className ).indexOf('builder-state-badge') !== -1 } )[0];

			return badge !== undefined && badge.dataset.state === 'muted' && badge.title === W('readonly/reason') && textOf( badge ).indexOf( W('readonly/badge') ) !== -1 && String( badge.className ).indexOf('nino-admin-badge') !== -1 && textOf( sheet.view.buttons.saveas ).trim() === W('saveas/button-theme');
		} )() && sheet.view.bar.save.hidden === true && sheet.view.buttons.saveas !== undefined && JSON.stringify( sheet.top ) === before && sheet.undo.length === 0
		&& controls.length > 0 && controls.every( function( control ) { return control.disabled === true } ) && ( function( link ) { return link.attributes.href ?? link.href } )( findAll( w.page, function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0] ) === '#themelab/@theme-blocks' );
}

// --- C2. the properties of a component in the column beside the preview ---------------------------------------------------------

{
	const w = sheetOf('#themelab/karten');
	const sheet = w.builder.editor.state();
	const rootAt = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );
	const model = w.builder.model;
	const keysOf = function( node ) { return findAll( node, function( el ) { return el.dataset !== undefined && el.dataset.key !== undefined && el.dataset.key !== '' } ).map( function( el ) { return el.dataset.key } ).sort().join() };
	const shownBefore = w.dialogs.length;
	const opened = w.builder.editor.properties( rootAt, '1', element('button'), function() {} );
	const panel = boxOf( w );
	const heading = panel === undefined ? undefined : findAll( panel, function( el ) { return el.tagName === 'H2' } )[0];

	check( 'C2. the properties of a component in the block editor open in the column beside the preview and not in a dialog: no dialog is made, the column is a named region with the heading "<kind> edit", and the focus is on that heading (the panel is no dialog: docs/admin-ui.md - after a change of the view the focus goes to the heading)',
		opened === true && w.dialogs.length === shownBefore && panel !== undefined && panel.attributes['aria-labelledby'] === heading.id && heading.id !== '' && heading.tabIndex === -1 && heading.focused === true
		&& textOf( heading ) === String( words.en['/_admin/builder/label/edit-component'] ).replace( '%s', 'Title' ) && panel.attributes.role === undefined && panel.tagName === 'SECTION' );

	const panelKeys = keysOf( panel );
	const rowsIn = function( rel, lightbox ) {

		leaveBox( w );
		sheet.adapter.props = lightbox === true ? undefined : 'panel';
		w.builder.editor.properties( rootAt, rel, element('button'), function() {} );

		const holder = lightbox === true ? dialogOf( w ) : boxOf( w );
		const keys = keysOf( holder )+ '#'+ ( lightbox === true ? Math.max( findAll( holder, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('nino-admin-accordion-button') !== -1 } ).length, findAll( holder, function( el ) { return String( el.className ).indexOf('builder-box-body') !== -1 } ).length > 0 ? 1 : 0 ) : findAll( holder, function( el ) { return el.dataset !== undefined && el.dataset.section !== undefined } ).length );

		if( lightbox === true )
			fire( dialogOf( w ), 'close' );

		sheet.adapter.props = 'panel';

		return keys;
	};

	// the rows are the same where the sheet has no column: the lightbox of the page is the other container of the same code
	const sameRows = [ 'root', '1' ].map( function( rel ) { return [ rowsIn( rel, false ), rowsIn( rel, true ) ] } );

	check( 'C2. the rows of a component are one code in two containers: the column and the lightbox hold the same sections and rows - for the block itself and for a component in it -, each key once, and none of them is drawn twice',
		panelKeys !== '' && sameRows.every( function( pair ) { return pair[0] !== '' && pair[0] === pair[1] && pair[0].split('#')[0].split(',').length === new Set( pair[0].split('#')[0].split(',') ).size } ) && sameRows[0][0] !== sameRows[1][0] );

	leaveBox( w );

	// the way back is a change of what the column shows: with work that is not saved it asks nothing, drops nothing and leaves the steps of the undo as they are
	w.builder.editor.properties( rootAt, '1', element('button'), function() {} );
	model.setAttr( 'root', 'ground', 'dark' );

	const steps = sheet.undo.length;
	const draft = JSON.stringify( sheet.top );
	const asked = w.dialogs.length;
	const back = findAll( boxOf( w ), function( el ) { return String( el.className ).indexOf('nino-admin-back-link') !== -1 } )[0];

	sheet.tree.select( 'root' );
	fire( back, 'click' );

	const row = sheet.tree.el.querySelector('[data-id="1"]');

	check( 'C2. "Back to the overview" is a button and no link - the workbench reads a link as a way out of the editor and asks about unsaved work -: with a change in the draft it opens no dialog, keeps the draft and every step of the undo, and the column is gone, the outline is there',
		back.tagName === 'BUTTON' && back.type === 'button'
		&& boxOf( w ) === undefined && sheet.box === null && w.dialogs.length === asked && sheet.undo.length === steps && steps > 0 && JSON.stringify( sheet.top ) === draft && String( sheet.view.split.settings.className ).indexOf('builder-props-open') === -1 );
	check( 'C2. the way back chooses the row of the component that was open - the chosen one by the tree itself, apart from the focus - and gives it the focus and the mark in the preview',
		sheet.marked === '1' && sheet.tree.get().selected === '1' && row !== null && row.focused === true && String( row.attributes['aria-selected'] ) === 'true' );

	w.builder.editor.properties( rootAt, '1', element('button'), function() {} );
	sheet.tree.select( 'root' );
	fire( sheet.view.level, 'keydown', { key : 'Escape' } );

	check( 'C2. Escape is the way back as well, from wherever in the view the focus is, with the same: no dialog, the draft and the undo as they were, the row of the component chosen and focused',
		boxOf( w ) === undefined && sheet.box === null && w.dialogs.length === asked && sheet.undo.length === steps && JSON.stringify( sheet.top ) === draft && sheet.marked === '1' && sheet.tree.get().selected === '1' && sheet.tree.el.querySelector('[data-id="1"]').focused === true );

	w.builder.editor.properties( rootAt, '1', element('button'), function() {} );
	const kept = w.dialogs.length;

	w.builder.editor.pick( '3' );

	check( 'C2. a press on another component in the preview gives the column that component at once - no dialog, no way back through the overview - and that component is the chosen and the marked one',
		sheet.box !== null && sheet.box.rel === '3' && boxOf( w ) !== undefined && findAll( boxOf( w ), function( el ) { return el.tagName === 'H2' } ).length === 1 && sheet.marked === '3' && w.dialogs.length === kept );

	leaveBox( w );

	// the component that is open is taken away by a step of the undo or the redo: the column is gone, the overview is there
	const count = JSON.stringify( sheet.top[rootAt] );

	model.removeComponent( '3' );
	model.undo();
	w.builder.editor.properties( rootAt, '3', element('button'), function() {} );
	model.redo();

	check( 'C2. a step of the undo or the redo that takes the component away that is open closes the column: the overview, with the focus on the work - and no dialog is left',
		JSON.stringify( sheet.top[rootAt] ) !== count && boxOf( w ) === undefined && sheet.box === null && String( sheet.view.split.settings.className ).indexOf('builder-props-open') === -1 );
}

check( 'C2. the panel holds the place of the column before it draws the sheet anew for a language that moved: the choice of the language asks the editor to hold the open component, and only then loads the block again',
	/dirty\.guard\( \[ 'themelab' \], function\(\) \{\s*editor\(\)\.hold\(\);\s*Nino\.admin\.themelab\.reload\(/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === true );

// A narrow window shows the column or the preview: with the properties open a strip of the preview stands over the column, a press on it goes to the preview, and it is gone with the column
{
	const w = sheetOf('#themelab/karten');
	const sheet = w.builder.editor.state();
	const rootAt = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );
	const peeks = function() { return findAll( sheet.view.split.preview, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('builder-props-peek') !== -1 } ) };

	w.builder.editor.properties( rootAt, '1', element('button'), function() {} );

	const there = peeks();
	const live = String( sheet.view.split.el.className ).indexOf('builder-props-live') !== -1;

	sheet.view.split.show('settings');
	fire( there[0], 'click' );

	const went = sheet.view.split.view() === 'preview';

	leaveBox( w );

	check( 'C2. with the properties open the preview has a strip over the column of a narrow window - a button named for it, marked live on the view - and a press on it is the way to the preview; leaving the column takes the strip and the mark away',
		there.length === 1 && there[0].attributes['aria-label'] === String( words.en['/_admin/builder/props/peek'] ) && live === true && went === true && peeks().length === 0 && String( sheet.view.split.el.className ).indexOf('builder-props-live') === -1 );
}

// The working language moves while the properties are open in the column: the block is drawn anew in the new language and the same component is open again - held by the panel
{
	const w = sheetOf('#themelab/karten');
	const rootAt = w.builder.editor.state().top.findIndex( function( node ) { return node.n === 'section-wrap' } );

	w.builder.editor.properties( rootAt, '1', element('button'), function() {} );
	w.builder.editor.hold();
	w.builder.editor.close();

	check( 'C2. the sheet that is closed for the new language has no column open - and the one that was held is open again when the block comes in the new language: the same component, the column and no dialog',
		boxOf( w ) === undefined && ( function() {

			w.requests.length = 0;
			w.panel.follow();
			answerOf( request( w, 'themelab/open' ), 200, SHEET() );

			const state = w.builder.editor.state();

			return state.box !== null && state.box.panel === true && state.box.rel === '1' && boxOf( w ) !== undefined;
		} )() );

	check( 'C2. what the panel holds is used once and only for the block it was held for: the same block opened again later has no column open',
		( function() {

			w.builder.editor.close();
			w.requests.length = 0;
			w.panel.follow();
			answerOf( request( w, 'themelab/open' ), 200, SHEET() );

			return w.builder.editor.state().box === null && boxOf( w ) === undefined;
		} )() );
}

// --- D. the fixes of the first round --------------------------------------------------------------------------------------------

// The dialogs begin with a name: a new block with "New block" in each language, a copy with the name and the word of a copy in each language, a rename with the name; only the working
// language is required, and the others stand in an accordion of their own, closed
{
	const w = shown('#themelab').w;
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

	const alone = request( w, 'themelab/create' );

	check( 'D. the working language is the one that is required: a name in it alone is sent, with the other language empty', alone !== undefined && alone.payload.name.de_DE === 'Neuer Block' && alone.payload.name.en_US === '' );

	fire( fresh, 'close' );
	w.requests.length = 0;
	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );

	const none = dialogOf( w );

	inputsOf( none )[0].value = '  ';
	pressed( none, W('new/submit') );

	check( 'D. without a name in the working language nothing is sent and the field says so', request( w, 'themelab/create' ) === undefined && textOf( none ).indexOf( W('new/name-missing') ) !== -1 );

	fire( none, 'close' );

	const second = shown('#themelab').w;

	fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[0], 'click' );

	check( 'D. a copy begins with the name of the block and the word of a copy in each language, the details of a block begin with its name in each language',
		inputsOf( dialogOf( second ) ).map( function( input ) { return input.value } ).join('|') === 'Karten Kopie|Cards copy' && ( function() {
			fire( dialogOf( second ), 'close' );
			fire( findAll( rowsOfList( second )[0], function( el ) { return el.tagName === 'BUTTON' } )[1], 'click' );

			return inputsOf( dialogOf( second ) ).map( function( input ) { return input.value } ).slice( 0, 2 ).join('|') === 'Karten|Cards';
		} )() );
}

// A block that was just made is opened with its first column chosen, and a block with no text is opened with the texts as a map
{
	const x = shown('#themelab');
	const w = x.w;

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );
	pressed( dialogOf( w ), W('new/submit') );
	answerOf( request( w, 'themelab/create' ), 200, { key : 'karten' } );
	w.requests.length = 0;
	w.panel.follow();
	answerOf( request( w, 'themelab/open' ), 200, SHEET( { texts : [] } ) );

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

	// the dialog of the components lists what the place does not take as well, grey and with its reason - it does not leave it out
	fire( dialogOf( w ), 'close' );
	model.openOffer( column, element('button') );

	const offerAsk = request( w, 'themelab/offer' );

	answerOf( offerAsk, 200, { offers : [
		{ name : 'section-title', label : { de_DE : 'Titel', en_US : 'Title' }, icon : 'heading', group : 'section', groupLabel : null, section : 'section', feature : null, featureLabel : null, ok : true, why : '', node : { n : 'section-title', s : '', a : [], c : null } },
		{ name : 'section-col', label : { de_DE : 'Spalte', en_US : 'Column' }, icon : 'columns-3', group : 'section', groupLabel : null, section : 'section', feature : null, featureLabel : null, ok : false, why : 'place', node : { n : 'section-col', s : '', a : [], c : [] } },
	], blank : '\n\t', schemas : {}, container : 'section-col' } );

	const tiles = findAll( dialogOf( w ), function( el ) { return el.tagName === 'BUTTON' && el.dataset !== undefined && el.dataset.name !== undefined } );
	const grey = tiles.find( function( el ) { return el.dataset.name === 'section-col' } );

	check( 'D. the dialog of the components lists what the place does not take as well - a tile that is grey, with the reason as its title - and a tile that can be used is not grey',
		tiles.length === 2 && grey !== undefined && grey.disabled === true && String( grey.title ).indexOf( String( words.en['/_admin/builder/offer/why-place'] ) ) === 0 && tiles.find( function( el ) { return el.dataset.name === 'section-title' } ).disabled !== true );

	fire( dialogOf( w ), 'close' );

	// a loop that is put in is complete at once: the placeholder type and a pattern of its own - and the headings of the columns of a table are words of the block
	[ 'collection-list', 'collection-table', 'collection' ].forEach( function( name ) { sheet.schemas[name] = Object.assign( schema( name, 'table', 'none', { container : true } ), { loop : true } ) } );
	[ 'collection-list', 'collection-table', 'collection', 'collection-col', 'text', 'section-col', 'section-title' ].forEach( function( name ) { sheet.known[name] = sheet.known[name] ?? { label : name, id : '', source : 'none' } } );
	[ 'collection-col' ].forEach( function( name ) { sheet.schemas[name] = sheet.schemas[name] ?? schema( name, 'columns-2', 'text', {} ) } );
	model.addComponent( column, { name : 'collection-table', node : { n : 'collection-table', s : '', a : {}, c : [] } }, '\n\t' );
	model.addComponent( column, { name : 'collection-list', node : { n : 'collection-list', s : '', a : {}, c : [] } }, '\n\t' );
	model.addComponent( column, { name : 'collection', node : { n : 'collection', s : '', a : {}, c : [] } }, '\n\t' );

	const holds = function( name ) { const found = []; const walk = function( nodes ) { ( Array.isArray( nodes ) === true ? nodes : [] ).forEach( function( node ) { if( node.n === name && node.s === '/_placeholder' ) found.push( node ); walk( node.c ) } ) }; walk( sheet.top ); return found[0] };
	const headingKey = function( name ) { return Object.keys( sheet.texts ).filter( function( key ) { return key.slice( -name.length - 1 ) === '/'+ name } )[0] };
	const table = holds( 'collection-table' );
	const list = holds( 'collection-list' );
	const cards = holds( 'collection' );

	check( 'D. a loop that is put into a block is complete at once - the placeholder type of the kernel and a pattern for its elements: a table of two columns with headings that are words of the block, a list with the title of an element, a collection with a card of a title and a text',
		table.s === '/_placeholder' && table.c.map( function( node ) { return node.s } ).join() === '.title,.text' && table.c[0].a.title === '~heading-title' && sheet.texts[headingKey( 'heading-title' )].de_DE === 'Titel' && sheet.texts[headingKey( 'heading-text' )].en_US === 'Text'
		&& list.s === '/_placeholder' && list.c[0].s === '.title' && list.a.sort === 'title' && cards.s === '/_placeholder' && cards.c[0].n === 'section-col' && cards.c[0].c.map( function( node ) { return node.s } ).join() === '.title,.text' );

	// the place of a loop: how many elements the block shows as examples - one to twelve - and the field each component of an element reads
	sheet.schemas['collection-list'].attributes = { sort : { type : 'text', label : 'Sort', hint : '', default : '' }, limit : { type : 'int', label : 'At most', hint : '', default : 0, min : 0, max : 99 }, offset : { type : 'int', label : 'From', hint : '', default : 0 }, query : { type : 'text', label : 'Query', hint : '', default : '' } };

	const relOf = function( target, nodes, prefix ) {

		for( let at = 0; at < nodes.length; at++ ) {

			if( nodes[at] === target )
				return prefix+ at;

			const deeper = Array.isArray( nodes[at].c ) === true ? relOf( target, nodes[at].c, prefix+ at+ '.' ) : null;

			if( deeper !== null )
				return deeper;
		}

		return null;
	};
	const rootAt = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );
	const loopRel = relOf( list, sheet.top[rootAt].c, '' );

	model.state.box = null;
	w.builder.editor.properties( rootAt, loopRel, element('button'), function() {} );

	const examples = findAll( boxOf( w ), function( el ) { return el.tagName === 'SELECT' && el.dataset !== undefined && el.dataset.key === loopRel+ ':examples' } )[0];

	examples.value = '5';
	fire( examples, 'change' );

	check( 'D. the place of a loop in a block says how many elements the block shows as examples - a choice of one to twelve that writes the attribute for "at most" - and has no source, no sort and no query',
		examples !== undefined && findAll( examples, function( el ) { return el.tagName === 'OPTION' } ).length === 12 && list.a.limit === '5' && textOf( boxOf( w ) ).indexOf( String( words.en['/_admin/builder/box/source'] ) ) === -1 );

	leaveBox( w );

	check( 'D. a component that is put into a column with nothing in it opens the column and is the chosen entry - not a column folded over it',
		chosen.indexOf( column+ '.' ) === 0 && holder.attributes['aria-expanded'] === 'true' && sheet.tree.el.querySelector('[data-id="'+ chosen+ '"]') !== null );
}

// What a sheet that is only looked at does: nothing - a key, a button of the tree, a call of the model - and it says nothing that is not so
{
	const w = sheetOf( '#themelab/@theme-blocks/karten', { editable : false } );
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
	const w = sheetOf('#themelab/karten');

	w.requests.length = 0;
	w.Nino.admin.workLocale.set('en_US');

	const asked = w.requests.map( function( entry ) { return entry.action } );

	check( 'D. a change of the working language while a block is open asks for no page of the Builder - no builder/open for the key of a block - and asks this panel for the block in the new language',
		asked.filter( function( action ) { return /^builder\//.test( action ) === true } ).length === 0 && asked.filter( function( action ) { return /^themelab\//.test( action ) === true } ).length > 0 );
}

// The tabs stand in the head of the pane, beside its name - where the workbench keeps the tabs of a panel - and not over the list; the first is "Own blocks"
{
	const x = shown('#themelab');
	const head = x.w.page.querySelector('.admin-panel-head');
	const inHead = head === null ? [] : findAll( head, function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).map( textOf );
	const inList = findAll( x.w.page.querySelector('#themelab-list'), function( el ) { return el.attributes !== undefined && el.attributes.role === 'tab' } ).length;

	check( 'D. the tabs of the list stand in the head of the pane - the blocks and the theme - and none stands in the list itself; the first of the switch of the blocks is "Own blocks"', inHead.join() === W('tab/blocks')+ ','+ W('tab/info') && inList === 0 && W('tab/project') === 'Own blocks' && String( words.de['/_admin/themelab/tab/project'] ) === 'Eigene Blocks' );
}

// "Save as" in a block of one's own keeps its name and says what it does behind a (?); in a block of the theme it is "Save to own blocks" - and neither has a paragraph over its field
{
	const dialogOfSave = function( hash ) {

		const w = sheetOf( hash, hash === '#themelab/@theme-blocks/karten' ? { editable : false } : undefined );

		fire( w.builder.editor.state().view.buttons.saveas, 'click' );

		const dialog = dialogOf( w );
		const message = findAll( dialog, function( el ) { return el.tagName === 'P' } ).filter( function( el ) { return String( el.className ).indexOf('nino-admin-field') === -1 } );

		return { title : textOf( dialog.querySelector('.nino-admin-dialog-title') ), submit : findAll( dialog, function( el ) { return el.tagName === 'BUTTON' } ).map( function( el ) { return textOf( el ).trim() } ), paragraphs : message.map( textOf ).join(' '), help : findAll( dialog, function( el ) { return String( el.className ).indexOf('nino-admin-help-button') !== -1 } ).length };
	};
	const mine = dialogOfSave('#themelab/karten');
	const theirs = dialogOfSave('#themelab/@theme-blocks/karten');

	check( 'D. "Save as" of an own block is called so and has its sentence behind a (?) at the name; of a block of the theme it is "Save to own blocks" - the title and the button - and with no (?) of its own; neither dialog has a paragraph above its field, and the name is the one field',
		mine.title === W('saveas/title') && mine.submit.indexOf( W('saveas/submit') ) !== -1 && mine.help === 1 && mine.paragraphs.indexOf( W('saveas/help') ) === -1 && theirs.title === W('saveas/title-theme') && theirs.submit.indexOf( W('saveas/submit-theme') ) !== -1 && theirs.help === 0 && theirs.paragraphs === '' );
}

// The title of the editor is the NAME of the block, with its key quietly behind it - and not the kind of the root and the key
{
	const w = sheetOf('#themelab/karten');
	const context = findAll( w.page, function( el ) { return String( el.className ).indexOf('nino-admin-context') !== -1 } ).map( textOf ).join(' ');

	check( 'D. the title of the editor of a block is the name of the block in the language of the workbench - with its key quietly behind it - and not "Section" and a key', context.indexOf('Cards') !== -1 && context.indexOf('karten') !== -1 && context.indexOf( 'Section ·' ) === -1 );
}

// The dialog of a source says "block" where the sheet is a block: the lightbox names the text and the picture of the block, and the dialog offers to choose and to make - nothing else
{
	const w = sheetOf('#themelab/karten', { texts : { '/template/page-home/karten/title' : { de_DE : 'Karten', en_US : 'Cards' } }, sources : [ { key : '/template/page-home/karten/title', kind : 'text', name : 'title' } ] } );
	const sheet = w.builder.editor.state();
	const root = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );

	w.builder.editor.properties( root, '1', element('button'), function() {} );

	const box = boxOf( w );

	fire( findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === String( words.en['/_admin/builder/source/change'] ) } )[0], 'click' );

	const inputOf = function( key, tag ) { return findAll( box, function( el ) { return el.tagName === ( tag ?? 'INPUT' ) && el.dataset !== undefined && el.dataset.key === key } )[0] };
	const place = inputOf( '1:place:first' );
	const sourceWord = String( words.en['/_admin/builder/box/source'] );
	const model = w.builder.model;

	check( 'D. the lightbox of a component in a block has a place - its id and, quietly, where it becomes the page\'s - and no source, no content to write and no way to change the source',
		place !== undefined && place.value === 'title' && textOf( box ).indexOf( sourceWord ) === -1 && textOf( box ).indexOf( String( words.en['/_admin/builder/box/content'] ) ) === -1
		&& findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === String( words.en['/_admin/builder/source/change'] ) } ).length === 0
		&& textOf( box ).indexOf( String( words.en['/_admin/builder/place/becomes'] ).replace( '%s', 'title' ) ) !== -1 );

	const select = inputOf( '1:length:title', 'SELECT' );
	const node = function() { return sheet.top[root].c[1] };
	const sample = model.loremOf( 'title', 8, { title : false, button : false, rich : false } );

	check( 'D. the sample of a place is Lorem ipsum of the length that is chosen, the same words in every language and the same every time - a title has no full stop, a button no more than two words',
		select !== undefined && sample === model.loremOf( 'title', 8, { title : false, button : false, rich : false } ) && sample.split( ' ' ).length === 8 && sample.slice( -1 ) === '.'
		&& model.loremOf( 'title', 8, { title : true, button : false, rich : false } ).slice( -1 ) !== '.' && model.loremOf( 'x', 80, { title : false, button : true, rich : false } ).split( ' ' ).length === 2
		&& model.loremOf( 'a', 30, { title : false, button : false, rich : false } ) !== model.loremOf( 'b', 30, { title : false, button : false, rich : false } ) );

	// a button holds one word or two, and its lengths say so
	const buttonLengths = model.lengthsOf( { title : false, button : true, rich : false } );

	check( 'D. the lengths of a button are one word and two words - named so, "1 word" and "2 words" - and the sample of each has that many words; the other places have the four lengths, and a button the first time has two',
		buttonLengths.map( function( entry ) { return entry[0]+ ':'+ entry[1] } ).join() === 'short:1,line:2' && model.loremOf( 'b', 1, { title : false, button : true, rich : false } ).split( ' ' ).length === 1 && model.loremOf( 'b', 2, { title : false, button : true, rich : false } ).split( ' ' ).length === 2
		&& model.lengthsOf( { title : false, button : false, rich : false } ).length === 4 && String( words.en['/_admin/builder/place/words-1'] ) === '1 word' && /%d/.test( String( words.en['/_admin/builder/place/words-n'] ) ) === true );

	select.value = 'line';
	fire( select, 'change' );

	check( 'D. a length that is chosen writes its sample into the words of the place in each language of the site, as one step of the undo',
		[ 'de_DE', 'en_US' ].every( function( locale ) { return sheet.texts['/template/page-home/karten/title'][locale] === model.loremOf( 'title', 8, model.kindOfPlace( node(), 'plain' ) ) } ) && sheet.undo.length > 0 );

	const again = findAll( boxOf( w ), function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '1:place:first' } )[0];

	again.value = 'headline';
	fire( again, 'change' );

	check( 'D. the id of a place is changed with its words: the call reads the new id and the words stand under it, the old id has none - and an id that is taken or is no id is refused at the field',
		node().s === '~headline' && sheet.texts['/template/page-home/karten/headline'] !== undefined && sheet.texts['/template/page-home/karten/title'] === undefined );

	const third = findAll( boxOf( w ), function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '1:place:first' } )[0];

	third.value = 'Bad Name';
	fire( third, 'change' );

	check( 'D. an id with a capital or a space, or longer than forty letters, is no id of a place: it is refused at the field with the sentence of the source dialog, and the place is as it was',
		node().s === '~headline' && third.value === 'headline' && textOf( boxOf( w ) ).indexOf( String( words.en['/_admin/builder/source/bad-name'] ) ) !== -1 );

	// a second place of the block - its words are the block's, and its id is taken
	sheet.texts['/template/page-home/karten/other'] = { de_DE : 'Other', en_US : 'Other' };

	const fourth = findAll( boxOf( w ), function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '1:place:first' } )[0];

	fourth.value = 'other';
	fire( fourth, 'change' );

	check( 'D. an id that another place of the block has is refused at the field with the sentence "taken" - in the words of a block - and the place keeps its id and its words',
		node().s === '~headline' && fourth.value === 'headline' && textOf( boxOf( w ) ).indexOf( String( words.en['/_admin/builder/source/taken-block'] ) ) !== -1 && /block/i.test( String( words.en['/_admin/builder/source/taken-block'] ) ) === true && /section|page/i.test( String( words.en['/_admin/builder/source/taken-block'] ) ) === false && sheet.texts['/template/page-home/karten/headline'] !== undefined );

	delete sheet.texts['/template/page-home/karten/other'];

	// a picture place: its id, the size of its picture, what the page shows until one is chosen - and no source
	const pic = sheetOf( '#themelab/karten', {
		tree : [ FRAME_HEAD, { t : '\n' }, block( 'karten', [ { n : 'image', s : '~bild', a : {}, c : null }, { t : '\n' } ] ), { t : '\n' }, FRAME_FOOT, { t : '\n' } ],
		schemas : Object.assign( {}, pageAnswer().schemas, { image : schema( 'Image', 'image', 'image', {} ) } ),
		details : { names : { de_DE : 'Karten', en_US : 'Cards' }, descriptions : { de_DE : 'D', en_US : 'D' }, category : 'content', rank : 900, images : { bild : { placeholder : '/nino/placeholder/mark', width : 800, height : 600, label : { de_DE : 'B', en_US : 'P' }, used : true } }, placeholders : [ '/nino/placeholder/mark', '/nino/placeholder/landscape' ] },
		images : { '/template/page-home/karten/bild' : { label : 'Bild', width : 800, height : 600, filename : null } },
		sources : [ { key : '/template/page-home/karten/bild', kind : 'image', name : 'bild' } ],
	} );
	const picSheet = pic.builder.editor.state();
	const picRoot = picSheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );

	pic.builder.editor.properties( picRoot, '3', element('button'), function() {} );

	const picBox = boxOf( pic );
	const wide = findAll( picBox, function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '3:width:bild' } )[0];

	wide.value = '1200';
	fire( wide, 'change' );

	check( 'D. a picture place has its id and its format in the lightbox of its component - the size of the picture and what the page shows until one is chosen - and the size is written to the details of the block, in the limits of the kernel',
		wide !== undefined && picSheet.extra.details.images.bild.width === 1200 && findAll( picBox, function( el ) { return el.dataset !== undefined && el.dataset.key === '3:placeholder:bild' } ).length === 1
		&& findAll( picBox, function( el ) { return el.tagName === 'INPUT' && el.dataset !== undefined && el.dataset.key === '3:place:first' } )[0].value === 'bild' && textOf( picBox ).indexOf( String( words.en['/_admin/builder/box/source'] ) ) === -1 );

	wide.value = '99999';
	fire( wide, 'change' );

	check( 'D. a size beyond what the kernel takes is cut to its limit: 4096', picSheet.extra.details.images.bild.width === 4096 );

	// a picture place that was just put in has its format at once - before anything is saved - and the place for an own picture
	leaveBox( pic );
	pic.builder.model.addComponent( 'root', { name : 'image', node : { n : 'image', s : '', a : {}, c : null } }, '\n\t' );

	const fresh = picSheet.top[picRoot].c.map( function( node, at ) { return node.n === 'image' && node.s !== '~bild' ? String( at ) : null } ).filter( function( at ) { return at !== null } )[0];

	pic.builder.editor.properties( picRoot, fresh, element('button'), function() {} );

	const freshBox = boxOf( pic );
	const hasKey = function( key ) { return findAll( freshBox, function( el ) { return el.dataset !== undefined && el.dataset.key === key } ).length > 0 };
	const placed = picSheet.top[picRoot].c[Number( fresh )].s.slice( 1 );
	const made = picSheet.pictures['/template/page-home/karten/'+ placed];

	check( 'D. a picture place that has just been put in has its format before anything is saved - the size, 800 by 600, what the page shows until a picture is chosen - and the way to give it an own picture',
		placed === 'bild-2' && made !== undefined && made.width === 800 && made.height === 600 && made.placeholder === '/nino/placeholder/landscape' && hasKey( fresh+ ':width:'+ placed ) && hasKey( fresh+ ':placeholder:'+ placed ) && hasKey( 'own:'+ placed ) );

	const chooser = findAll( freshBox, function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];

	chooser.files = [ { name : 'a.png' } ];
	fire( chooser, 'change' );
	answerOf( request( pic, 'themelab/picture' ), 200, { file : 'a'.repeat( 32 )+ '.png', type : 'png' } );

	check( 'D. an own picture that is given to a place is a change of the draft - it stands in what the save sends, as the name of the staged file - and a place that has none is told so',
		picSheet.extra.ownChosen[placed] === 'a'.repeat( 32 )+ '.png' && pic.builder.model.isDirty() === true && request( pic, 'themelab/picture' ).payload.name === placed && request( pic, 'themelab/picture' ).payload.key !== undefined );

	// the picture that was chosen is drawn in the page where the place is - from the file that was put aside, asked of the panel for this block - and the page the server drew is not touched
	const picture = { attributes : { srcset : 'x 2x' }, setAttribute : function( name, value ) { this.attributes[name] = value }, removeAttribute : function( name ) { delete this.attributes[name] }, matches : function() { return true } };

	picSheet.found = { all : [ { path : String( picRoot )+ '.'+ fresh, elements : [ picture ] } ], blocks : [] };
	picSheet.sources = [ { kind : 'image', key : '/template/page-home/karten/'+ placed, path : String( picRoot )+ '.'+ fresh, attribute : 'picture' } ];
	pic.requests.length = 0;
	pic.builder.editor.paintOwn();
	pic.builder.editor.paintOwn();

	const asked = pic.requests.filter( function( entry ) { return entry.action === 'themelab/picture-show' } );

	answerOf( asked[0], 200, { url : 'data:image/png;base64,AAAA' } );

	check( 'D. the own picture that was chosen for a place is shown by the page where the place is, from the file that was put aside for this block: the panel asks for it once - by its name and the key of the block - and the image of the place has it as its source, with no srcset; the picture the server drew for the draft is only the placeholder',
		asked.length === 1 && asked[0].payload.file === 'a'.repeat( 32 )+ '.png' && asked[0].payload.key !== undefined && picture.attributes.src === 'data:image/png;base64,AAAA' && picture.attributes.srcset === undefined );

	// a picture that was put aside and is not wanted - another one came for the place, the draft is given up - is let go of at the server
	pic.requests.length = 0;
	chooser.files = [ { name : 'b.png' } ];
	fire( chooser, 'change' );
	answerOf( request( pic, 'themelab/picture' ), 200, { file : 'b'.repeat( 32 )+ '.png', type : 'png' } );

	const replaced = request( pic, 'themelab/picture-drop' );

	pic.requests.length = 0;
	pic.builder.model.discard();

	const given = request( pic, 'themelab/picture-drop' );

	check( 'D. a picture that was put aside is let go of when another comes for the place and when the draft is given up: the server is asked to drop the file that is not wanted, and the draft has none chosen after',
		replaced !== undefined && replaced.payload.files.join() === 'a'.repeat( 32 )+ '.png' && given !== undefined && given.payload.files.join() === 'b'.repeat( 32 )+ '.png' && Object.keys( picSheet.extra.ownChosen ).length === 0 );
}


// --- the variants and the details of a block ------------------------------------------------------------------------------

{
	const attribute = function( label, name, fallback, options, look ) { return { type : 'choice', label : label, hint : '', default : fallback, options : options, optionLabels : null, responsive : false, min : null, max : null, class : look } };
	const VARIED = function( extra ) {

		const schemas = Object.assign( {}, pageAnswer().schemas, {
			'section-wrap' : schema( 'Section', 'panel-top', 'none', { side : 'page', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '', options : null, optionLabels : null, responsive : false, min : null, max : null, class : false }, ground : attribute( 'Ground', 'ground', 'base', [ 'base', 'tint' ], true ) } } ),
			'section-row' : schema( 'Row', 'rows-2', 'none', { variants : [ 'regular', 'card' ], standard : 'regular', attributes : { reverse : attribute( 'Reverse', 'reverse', 'off', [ 'off', 'on' ], true ), gap : attribute( 'Gap', 'gap', '2', [ '1', '2', '3' ], false ) } } ),
		} );

		return Object.assign( {
			schemas : schemas,
			alternatives : [ { name : 'standard', label : { de_DE : 'Standard', en_US : 'Standard' }, set : {} }, { name : 'right', label : { de_DE : 'Rechts', en_US : 'Right' }, set : { '3' : { reverse : 'on' } } } ],
			details : { names : { de_DE : 'Karten', en_US : 'Cards' }, descriptions : { de_DE : 'D', en_US : 'D' }, category : 'content', rank : 900, images : { picture : { placeholder : '/nino/placeholder/mark', width : 800, height : 600, label : { de_DE : 'B', en_US : 'P' }, used : true }, spare : { placeholder : '/nino/placeholder/mark', width : 800, height : 600, label : { de_DE : 'S', en_US : 'S' }, used : false } }, locales : [ 'de_DE', 'en_US' ], placeholders : [ '/nino/placeholder/landscape', '/nino/placeholder/mark' ] },
		}, extra ?? {} );
	};
	const w = sheetOf( '#themelab/karten', VARIED() );
	const sheet = w.builder.editor.state();
	const model = w.builder.model;
	const root = sheet.top.findIndex( function( node ) { return node.n === 'section-wrap' } );
	const row = function() { return sheet.top[root].c[3] };
	const canon = function() { return sheet.adapter.canon( sheet.top, sheet.extra ) };
	const chooseVariant = function( label ) {

		fire( sheet.view.bar.buttons.variant, 'click' );

		const box = dialogOf( w );

		fire( findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim().indexOf( label ) === 0 } )[0], 'click' );
		fire( box, 'close' );
	};

	check( 'E. the sets of the variants stand on the calls they are about - the one that sets the reverse of the row has it in v, and the value the template has in o - and the first variant, the template, is the one that is edited: the row has the reverse of the template',
		JSON.stringify( sheet.extra.vars.map( function( variant ) { return variant.name } ) ) === '["standard","right"]' && sheet.extra.edited === 'standard' && JSON.stringify( row().v ) === '{"right":{"reverse":"on"}}' && row().o.reverse === null && row().a.reverse === undefined
		&& canon().variants[1].set['3'].reverse === 'on' && canon().tree.length === sheet.top.length );

	// The root of the block is the one the schemas the server sent say is of the side page - a root of another name has its variants as well
	const other = JSON.parse( JSON.stringify( SHEET( VARIED() ) ).split( 'section-wrap' ).join( 'probe-top' ) );
	const otherSheet = sheetOf( '#themelab/karten', other ).builder.editor.state();
	const otherCanon = otherSheet.adapter.canon( otherSheet.top, otherSheet.extra );

	check( 'E. the sets of the variants stand on the calls of a block whose root has a name of its own: the root is the call whose schema says the page, never a name',
		otherSheet.top.some( function( node ) { return node.n === 'probe-top' } ) === true && otherSheet.top.some( function( node ) { return node.n === 'section-wrap' } ) === false && otherCanon.variants[1].set['3'] !== undefined && otherCanon.variants[1].set['3'].reverse === 'on' );

	check( 'E. the question before a variant is deleted is asked by the workbench\'s confirm() of the dialog that manages the variants - nested, in red, for no visitor - and no question of the panel\'s own is made for it',
		/Nino\.adminUi\.confirm\( 'destructive', \{\s*title\s*: word\('variants\/delete-title'\),[\s\S]*?kind\s*: 'danger',\s*visitors\s*: false,[\s\S]*?nested\s*: true,/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === true && /Nino\.adminUi\.choiceDialog\( \{[^}]*variants\/delete-title/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === false );

	chooseVariant( 'Rechts' );

	check( 'E. another variant is looked at by the menu of the bar: the attributes of the calls are its own, the bar says which it is, and that is no change of the draft - nothing is unsaved and nothing is on the undo',
		sheet.extra.edited === 'right' && row().a.reverse === 'on' && model.isDirty() === false && sheet.undo.length === 0 && textOf( sheet.view.bar.buttons.variant ).indexOf( W( 'variants/button', 'Rechts' ) ) !== -1 );

	// the working language moves - and a conflict answered with "Reload" is the same - and the block is drawn anew: the variant that was edited is the one that is edited then, so that a
	// change goes where the person meant it and not into the template
	check( 'C2. a block that is drawn anew for the language keeps the variant that is edited - the bar says it, the attributes are its own - and a sheet that was not held for it begins with the first',
		( function() {

			w.builder.editor.hold();
			w.builder.editor.close();
			w.requests.length = 0;
			w.panel.follow();
			answerOf( request( w, 'themelab/open' ), 200, SHEET( VARIED() ) );

			const kept = sheet.extra.edited === 'right' && row().a.reverse === 'on' && textOf( sheet.view.bar.buttons.variant ).indexOf( W( 'variants/button', 'Rechts' ) ) !== -1;

			w.builder.editor.close();
			w.requests.length = 0;
			w.panel.follow();
			answerOf( request( w, 'themelab/open' ), 200, SHEET( VARIED() ) );

			return kept === true && sheet.extra.edited === 'standard';
		} )() );

	check( 'C2. "Reload" in the conflict of a save holds the place as the language does: the variant that is edited and the component that is open are asked to be held before the sheet is loaded anew',
		/onReload\s*: function\(\) \{[\s\S]*?holdProperties\(\);\s*S\.adapter\.reload\(\);/.test( fs.readFileSync( builderPath, 'utf8' ) ) === true );

	chooseVariant( 'Rechts' );

	const inContext = function( node ) { for( let up = node; up !== null && up !== undefined; up = up.parentNode ) if( String( up.className ?? '' ).split(' ').indexOf('nino-admin-contextbar') !== -1 ) return true; return false };

	check( 'E. the buttons of the block - its details and its variant - stand in the bar of the context beside its name and not in the foot; the name of the variant button is the words it shows, so that what a person reads is what she says',
		sheet.adapter.buttons.map( function( one ) { return one.key } ).join() === 'saveas' && sheet.adapter.context.map( function( one ) { return one.key } ).join() === 'details,variant' && inContext( sheet.view.bar.buttons.details ) === true && inContext( sheet.view.bar.buttons.variant ) === true
		&& sheet.view.bar.buttons.variant.attributes['aria-label'] === W( 'variants/button', 'Rechts' ) && textOf( sheet.view.bar.buttons.variant ).trim() === W( 'variants/button', 'Rechts' ) );

	model.setAttr( 'root', 'ground', 'tint' );

	check( 'E. a look that is changed in a variant other than the first is the set of that variant at the place of the call - the template keeps what it had - and is a change of the draft, one step of the undo',
		sheet.top[root].v.right.ground === 'tint' && sheet.top[root].o.ground === null && canon().tree[root].a.ground === undefined && canon().variants[1].set.root.ground === 'tint' && model.isDirty() === true && sheet.undo.length === 1 );

	model.setAttr( '3', 'gap', '3' );

	check( 'E. what is no look - the gap of the row here, which the register does not write as a class - is the template\'s whatever variant is edited, and no set has it',
		row().a.gap === '3' && Object.hasOwn( row().v.right, 'gap' ) === false && canon().tree[root].c[3].a.gap === '3' );

	model.setAttr( 'root', 'ground', null );

	check( 'E. a look that is put back to what the template has leaves no set: the value is the template\'s again and the set of the variant at that place is gone',
		Object.keys( sheet.top[root].v ?? {} ).length === 0 && canon().variants[1].set.root === undefined && sheet.top[root].a.ground === undefined );

	model.undo();
	model.undo();

	const half = sheet.top[root].v.right.ground === 'tint' && row().a.gap === undefined && sheet.extra.edited === 'right' && model.isDirty() === true;

	model.undo();

	check( 'E. the undo takes the steps back in the variant that they were made in: the look of the set is back, and with the last step gone the draft is as it was opened',
		half === true && Object.keys( sheet.top[root].v ?? {} ).length === 0 && model.isDirty() === false );

	chooseVariant( 'Standard' );
	model.setAttr( 'root', 'ground', 'tint' );

	check( 'E. in the first variant a look is the template\'s: the variants that do not set it have it too, and the one that sets it keeps its own',
		canon().tree[root].a.ground === 'tint' && canon().variants[1].set.root === undefined && JSON.stringify( row().v ) === '{"right":{"reverse":"on"}}' );

	model.undo();

	// a call that is taken out takes its set with it, and the places that come after move: the sets are carried by the calls
	model.removeComponent( '1' );

	check( 'E. the sets move with the calls: when a call before the row is taken out the row has another place and its set is at that place - and a set of a call that was taken out is gone',
		canon().variants[1].set['1'].reverse === 'on' && canon().variants[1].set['3'] === undefined );

	model.undo();

	chooseVariant( 'Rechts' );

	const sent = { tree : sheet.top.slice(), texts : {} };

	sheet.adapter.out( 'preview', sent );

	const saving = { tree : sheet.top.slice(), texts : {} };

	sheet.adapter.out( 'save', saving );

	check( 'E. the draft that is drawn has no sets on its calls and the attributes of the variant that is edited, the draft that is saved is the template with the variants and the details beside it - the server is told what it builds block.json from',
		JSON.stringify( sent.tree ).indexOf( '"v"' ) === -1 && JSON.stringify( sent.tree ).indexOf( '"o"' ) === -1 && sent.variants === undefined && saving.variants.length === 2 && saving.variants[1].set['3'].reverse === 'on' && saving.details.rank === 900 && saving.tree[root].c[3].a.reverse === undefined );

	// the answer of the save: the variant that was edited is the one that stays
	sheet.adapter.took( { alternatives : saving.variants, details : sheet.extra.details }, { edited : 'right' } );

	check( 'E. after the save the variant that was edited is still the one that is edited - the answer of the server brings the variants as they are saved and the tree shows the one that was looked at, not the standard',
		sheet.extra.edited === 'right' && row().a.reverse === 'on' && sheet.extra.vars.length === 2 );

	// a new variant begins as the one that is edited
	chooseVariant( 'Rechts' );
	chooseVariant( W('variants/new') );
	typed( dialogOf( w ), [ 'Gross', 'Big' ] );
	pressed( dialogOf( w ), W('variants/new-submit') );
	fire( dialogOf( w ), 'close' );

	check( 'E. a new variant is made from a name and begins as a copy of the one that is edited: its key is made from the name, it is the one that is edited, it has the sets the other had - and it is a change of the draft',
		sheet.extra.vars.length === 3 && sheet.extra.vars[2].name === 'gross' && sheet.extra.edited === 'gross' && JSON.stringify( row().v.gross ) === '{"reverse":"on"}' && model.isDirty() === true && row().a.reverse === 'on' );

	// a draft that is given up is the draft that was saved - whatever it keeps beside the tree: the variants that were made are gone and the draft is no more changed, which is what lets
	// a person leave a sheet whose save was refused
	( function() {
		const keep = model.snap();
		const had = JSON.stringify( [ keep.extra, keep.top ] );
		model.discard();
		const gone = sheet.extra.vars.length === 2 && sheet.extra.vars.every( function( variant ) { return variant.name !== 'gross' } ) === true;
		check( 'E. a draft that is given up is the one that was saved: the variant that was made is gone, the draft is no more changed - so that the way out of a sheet whose save was refused is open - and the tree is a tree',
			gone === true && model.isDirty() === false && Array.isArray( sheet.top ) === true && had !== JSON.stringify( [ model.snap().extra, model.snap().top ] ) );
		sheet.top = keep.top;
		sheet.extra = keep.extra;
	} )();

	// the variants are one overview: a row each, chosen to edit, with the buttons of the row; the first is the standard and has no way to move or to go - and "Manage variants" is no more
	fire( sheet.view.bar.buttons.variant, 'click' );

	const manage = dialogOf( w );
	const rowsOfOverview = findAll( manage, function( el ) { return el.tagName === 'LI' && String( el.className ).indexOf('builder-var-row') !== -1 } );
	const buttonsOf = function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' && String( el.className ).indexOf('builder-var-pick') === -1 } ).map( function( el ) { return String( el.attributes['aria-label'] ?? '' )+ ( el.disabled === true ? '!' : '' ) } ) };

	check( 'E. the variants are one overview of rows - the standard first, then the others by their names - each chosen by a press on it, and each of the project\'s with a button to rename, up, down and delete, named for the variant; there is no "Manage variants" - the first has no way to move or to go: its up, down and delete are off',
		rowsOfOverview.map( function( row ) { return row.dataset.variant } ).join('|') === 'standard|right|gross' && buttonsOf( rowsOfOverview[0] ).length === 4 && buttonsOf( rowsOfOverview[0] ).slice( 1 ).every( function( name ) { return name.slice( -1 ) === '!' } ) === true
		&& buttonsOf( rowsOfOverview[2] ).every( function( name ) { return name.slice( -1 ) !== '!' || name.indexOf( 'down' ) !== -1 || name.indexOf( 'Down' ) !== -1 || name.indexOf( 'unten' ) !== -1 } ) === true && textOf( manage ).indexOf( 'Manage' ) === -1 && textOf( manage ).indexOf( 'verwalten' ) === -1 );

	// the first variant is the standard and keeps its place, and it has a name that can be changed like any other: in the dialog of the name of a new variant
	const before = model.snap();
	const undoWas = sheet.undo.slice();

	fire( findAll( rowsOfOverview[0], function( el ) { return el.tagName === 'BUTTON' && String( el.attributes['aria-label'] ?? '' ).indexOf( 'Rename' ) !== -1 } )[0], 'click' );
	fire( manage, 'close' );

	const renaming = dialogOf( w );

	typed( renaming, [ 'Normal', 'Normal' ] );
	pressed( renaming, W('variants/edit-submit') );
	fire( renaming, 'close' );

	while( w.timers.length > 0 )
		w.timers.shift()();

	check( 'E. the standard variant is named in the dialog of the name - the same as a new variant has -, in each language, and it stays the first and keeps its set; the overview is open again after it',
		sheet.extra.vars[0].name === 'standard' && sheet.extra.vars[0].label.en_US === 'Normal' && sheet.extra.vars[0].label.de_DE === 'Normal' && sheet.extra.vars.length === 3 && sheet.extra.edited !== undefined && textOf( dialogOf( w ) ).indexOf( W('variants/menu-title') ) !== -1 );

	// the name goes back in place - and the step of the undo goes with it
	Object.assign( sheet.extra.vars[0].label, before.extra.vars[0].label );
	sheet.undo.length = undoWas.length;
	sheet.redo.length = 0;

	const manage2 = dialogOf( w );
	const trashed = findAll( manage2, function( el ) { return el.tagName === 'LI' && el.dataset.variant === 'gross' } )[0];

	const trash = findAll( trashed, function( el ) { return el.tagName === 'BUTTON' && String( el.attributes['aria-label'] ?? '' ).indexOf( 'Delete' ) !== -1 } )[0];

	fire( trash, 'click' );
	pressed( dialogOf( w ), W('variants/delete-yes') );
	fire( dialogOf( w ), 'close' );

	check( 'E. a variant is taken away after a question that says what happens to the pages that have it: its set is gone from every call, the first variant is the one that is edited then, and it is a change of the draft',
		sheet.extra.vars.length === 2 && row().v.gross === undefined && sheet.extra.edited === 'standard' && row().a.reverse === undefined );

	// every row of the lightbox says where it writes: a look the set of this variant, the rest every variant - and what it says is where the change goes
	// the management of the variants is still open under the question that was answered: it is closed, as a person does with Close
	fire( w.dialogs.filter( function( one ) { return textOf( one ).indexOf( W('variants/menu-title') ) === 0 } ).pop(), 'close' );

	chooseVariant( 'Rechts' );

	const opener = sheet.view.bar.buttons.variant;
	const shownBox = w.builder.editor.properties( root, '3', opener );
	const boxDialog = boxOf( w );
	const bodies = findAll( findAll( boxDialog, function( el ) { return el.dataset !== undefined && el.dataset.section === 'look' } )[0], function( el ) { return String( el.className ).indexOf('builder-box-body') !== -1 } );
	const lookBody = bodies[0];
	const countOf = function( node, name ) { return findAll( node, function( el ) { return String( el.className ).split(' ').indexOf( name ) !== -1 } ).length };
	const fieldRows = lookBody.children.filter( function( child ) { return String( child.className ).indexOf('builder-var-note') === -1 } );
	const marks = fieldRows.map( function( one ) { return countOf( one, 'builder-var-dot' ) } );
	const sentences = [ 'builder-var-here', 'builder-var-all' ].map( function( name ) { return countOf( lookBody, name ) } );

	check( 'E. in a variant the rows of the look that write its set - the reverse here - have a dot before their names, a ring where nothing is set, and the rows that write the template - the variant of the component, the gap - have nothing; no sentence stands under a row, and one note at the head of the section says which variant is edited, with a (?)',
		shownBox === true && fieldRows.length === 3 && marks.join('|') === '0|1|0' && sentences.join() === '0,0' && countOf( lookBody, 'builder-var-note' ) === 1 && textOf( lookBody.children[0] ).indexOf( W( 'variants/button', 'Rechts' ) ) !== -1
		&& findAll( lookBody.children[0], function( el ) { return el.tagName === 'BUTTON' && String( el.attributes['aria-label'] ?? '' ).indexOf( W('variants/note-name') ) !== -1 } ).length === 1 );

	const reversed = fieldRows[1];

	check( 'E. the dot has words for a screen reader: where the set holds a value it says that it applies only to this block variant, where it holds none that it can be set',
		textOf( reversed ).indexOf( W('variants/dot-set') ) !== -1 && countOf( reversed, 'is-set' ) === 1 );

	const dots = fieldRows.map( function( one ) { return countOf( one, 'is-set' ) } );
	const resets = fieldRows.map( function( one ) { return findAll( one, function( el ) { return el.tagName === 'BUTTON' && String( el.attributes['aria-label'] ?? '' ) === W('variants/reset') } ).length } );
	const steps = sheet.undo.length;

	fire( findAll( fieldRows[1], function( el ) { return el.tagName === 'BUTTON' && String( el.attributes['aria-label'] ?? '' ) === W('variants/reset') } )[0], 'click' );

	check( 'E. a row that has a value in the set of the variant has a filled dot and the way back to the template - "Reset to template", a small button with its name - a row that has none has neither; the way back takes the value out of the set, as one step of the undo',
		dots.join() === '0,1,0' && resets.join() === '0,1,0' && ( row().v ?? {} ).right === undefined && sheet.undo.length === steps + 1 && row().a.reverse === undefined );

	model.undo();

	// ... and what a row says is where the change goes
	model.setAttr( '3', 'variant', 'card' );
	model.setAttr( '3', 'gap', '1' );
	model.setAttr( '3', 'reverse', 'off' );

	check( 'E. what a row says is where its change goes: the variant of the component and the gap are the template\'s - for every variant - the look is the set of the variant that is edited',
		row().a.variant === 'card' && row().o.variant === undefined && row().a.gap === '1' && row().o.gap === undefined && Object.keys( ( row().v ?? {} ).right ?? {} ).join() === ''
		&& canon().tree[root].c[3].a.variant === 'card' && canon().tree[root].c[3].a.gap === '1' );

	leaveBox( w );

	// a step back that lands in the variant its change was made in says which variant is edited
	chooseVariant( 'Standard' );
	model.undo();

	check( 'E. a step back that goes to another variant - the change it takes back was made there - says in the bar which variant is edited; choosing a variant says nothing',
		sheet.extra.edited === 'right' && sheet.view.bar.line.element.textContent === W( 'variants/edited', 'Rechts' ) );
}

{
	const w = sheetOf( '#themelab/@theme-blocks/karten', { editable : false, alternatives : [ { name : 'standard', label : { en_US : 'Standard' }, set : {} }, { name : 'right', label : { en_US : 'Right' }, set : {} } ] } );
	const sheet = w.builder.editor.state();
	const buttons = Object.keys( sheet.view.bar.buttons );

	check( 'E. a block of the theme is looked at: its variants can be chosen to look at and there is no way to make one or to manage them, and it has no details to change',
		buttons.indexOf( 'variant' ) !== -1 && buttons.indexOf( 'details' ) === -1 && sheet.adapter.readOnly === true );
}

{
	const w = sheetOf( '#themelab/karten', { details : { names : { de_DE : 'Karten', en_US : 'Cards' }, descriptions : { de_DE : 'D', en_US : 'D' }, category : 'content', rank : 900, images : { picture : { placeholder : '/nino/placeholder/mark', width : 800, height : 600, label : { de_DE : 'B', en_US : 'P' }, used : true }, spare : { placeholder : '/nino/placeholder/mark', width : 800, height : 600, label : { de_DE : 'S', en_US : 'S' }, used : false } }, locales : [ 'de_DE', 'en_US' ], placeholders : [ '/nino/placeholder/landscape', '/nino/placeholder/mark' ] }, alternatives : [ { name : 'standard', label : { de_DE : 'Standard', en_US : 'Standard' }, set : {} } ] } );
	const sheet = w.builder.editor.state();
	const model = w.builder.model;

	fire( sheet.view.bar.buttons.details, 'click' );

	const box = dialogOf( w );
	const heads = findAll( box, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined } ).map( function( el ) { return textOf( el ).trim() } );

	check( 'E. the details of a block are a lightbox with an accordion: the name and the description in each language, the choice - category and rank - and the places of the pictures',
		heads.length === 3 && heads[0].indexOf( W('details/name') ) === 0 && heads[1].indexOf( W('details/order') ) === 0 && heads[2].indexOf( W('details/images') ) === 0 );

	const fields = findAll( box, function( el ) { return el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' } );
	const named = fields.filter( function( el ) { return el.tagName === 'INPUT' } );
	const described = fields.filter( function( el ) { return el.tagName === 'TEXTAREA' } );

	named[1].value = 'Boxes';
	fire( named[1], 'input' );
	described[0].value = 'Eine Beschreibung';
	fire( described[0], 'input' );

	check( 'E. the name and the description are written for the language they are typed in - the other languages keep theirs - and each is a step of the undo',
		sheet.extra.details.names.en_US === 'Boxes' && sheet.extra.details.names.de_DE === 'Karten' && sheet.extra.details.descriptions.de_DE === 'Eine Beschreibung' && sheet.extra.details.descriptions.en_US === 'D' && sheet.undo.length === 2 && model.isDirty() === true );

	model.undo();
	model.undo();

	check( 'E. the undo takes the names back as they were', sheet.extra.details.names.en_US === 'Cards' && sheet.extra.details.descriptions.de_DE === 'D' && model.isDirty() === false );

	const orderHead = findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim().indexOf( W('details/order') ) === 0 } )[0];

	fire( orderHead, 'click' );

	const rank = findAll( box, function( el ) { return el.tagName === 'INPUT' && el.type === 'number' && el.value === '900' } )[0];

	rank.value = '5000';
	fire( rank, 'change' );

	const kind = findAll( box, function( el ) { return el.tagName === 'SELECT' } )[0];

	kind.value = 'trust';
	fire( kind, 'change' );

	check( 'E. the rank is a number from 0 to 999 - what is typed beyond is the end of it - and the category is one of the words of the kernel; both are steps of the undo and part of what is saved',
		sheet.extra.details.rank === 999 && sheet.extra.details.category === 'trust' && sheet.undo.length === 2 );

	model.undo();
	model.undo();

	const open = findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim().indexOf( W('details/images') ) === 0 } )[0];

	fire( open, 'click' );

	const unused = textOf( box ).indexOf( W('details/unused') ) !== -1;
	const drop = findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === W( 'details/image-delete', 'spare' ) } )[0];

	const keepable = findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === W( 'details/image-delete', 'picture' ) } ).length;

	fire( drop, 'click' );

	check( 'E. a place of a picture that no call reads is marked unused and can be taken away - and that is a change of the draft, one step of the undo; the one that is read has no such button',
		unused === true && drop !== undefined && keepable === 0 && sheet.extra.details.images.spare === undefined && sheet.extra.details.images.picture !== undefined && model.isDirty() === true && sheet.undo.length === 1 );

	model.undo();

	check( 'E. the undo brings a detail back: the place of the picture is there again, and the draft is as it was opened', sheet.extra.details.images.spare !== undefined && model.isDirty() === false );

	// a place is unused when the draft does not read it - the picture that was read is taken out - and not only when the block was opened so: it is marked at once, and the button says that it goes with the save
	fire( box, 'close' );

	const rootId = String( sheet.top.find( function( node ) { return node.n === 'section-wrap' } ).a.id );

	sheet.sources = [ { kind : 'image', key : '/template/blocks-x/'+ rootId+ '/spare', path : '1', written : '~spare', name : 'image' } ];
	sheet.adapter.painted();

	const gone = sheet.view.bar.buttons.details.dataset.notice;

	fire( sheet.view.bar.buttons.details, 'click' );

	const again = dialogOf( w );

	fire( findAll( again, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim().indexOf( W('details/images') ) === 0 } )[0], 'click' );

	const dropLost = findAll( again, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === W( 'details/image-delete', 'picture' ) } ).length;
	const dropKept = findAll( again, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === W( 'details/image-delete', 'spare' ) } ).length;

	check( 'E. a place of a picture that the draft does not read is marked unused at once and said to go with the save - the kernel holds no place that is not read - the button of the details carries a quiet dot and the words, the places that are read have neither',
		gone === 'picture' && dropLost === 1 && dropKept === 0 && textOf( again ).indexOf( W('details/unused') ) !== -1 && sheet.view.bar.buttons.details.title === W( 'details/falls-away', 'picture' ) );
}


// --- F. the export and the import ---------------------------------------------------------------------------------------

{
	const { w } = shown('#themelab');
	const rowBoxes = function( page ) { return findAll( page, function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' && el.attributes['aria-label'] !== undefined && /^(?:Choose|.*wählen)/.test( String( el.attributes['aria-label'] ) ) } ) };
	const boxes = rowBoxes( w.page );
	const button = function( id ) { return findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && el.id === id } )[0] };
	const importBar = button('themelab-import');
	const on = shown( '#themelab/@blocks/nino', THEME() ).w;
	const barButton = function( label ) { return findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === label } ).filter( function( el ) { return String( el.className ).indexOf('nino-admin-btn') !== -1 && el.id === '' } )[0] };

	check( 'F. the list of the own blocks is chosen by the workbench\'s table: a box at each row, named for its block, and a quiet button of the import beside "New block" - no button of the export of its own; the theme\'s list has no box and no import',
		boxes.length === 2 && boxes[0].attributes['aria-label'] === W( 'list/pick', 'Cards' ) && button('themelab-export') === undefined && importBar !== undefined && textOf( importBar ).trim() === W('import/button')
		&& rowBoxes( on.page ).length === 0 && findAll( on.page, function( el ) { return el.id === 'themelab-import' } ).length === 0 );

	boxes[0].checked = true;
	fire( boxes[0], 'click' );
	boxes[1].checked = true;
	fire( boxes[1], 'click' );

	const exportBar = barButton( W('export/button') );
	const deleteBar = barButton( W('delete/many') );

	check( 'F. with blocks chosen the bar of the table has the two actions for them - the export and the deletion, the deletion in red - and the theme\'s list has no bar',
		exportBar !== undefined && deleteBar !== undefined && String( deleteBar.className ).indexOf('danger') !== -1 && String( exportBar.className ).indexOf('danger') === -1
		&& findAll( on.page, function( el ) { return String( el.className ).indexOf('nino-admin-table-selection') !== -1 } ).length === 0 );

	// several blocks: ONE package, asked of the server with exactly the keys that are chosen, in the order of the rows
	fire( exportBar, 'click' );

	const several = dialogOf( w );

	pressed( several, W('export/submit') );

	const askedAll = request( w, 'themelab/export' );

	check( 'F. the export of the chosen blocks is one package: the request has exactly the keys of the chosen rows, in the order of the rows, and the dialog names a package of its own - not the name of one block',
		askedAll !== undefined && askedAll.payload.keys.join() === 'karten,held' && askedAll.payload.name !== 'Karten' );

	fire( several, 'close' );
	w.requests.length = 0;
	boxes[1].checked = false;
	fire( boxes[1], 'click' );

	// one block: its name and the key that comes of it
	fire( exportBar, 'click' );

	const box = dialogOf( w );
	const inputs = findAll( box, function( el ) { return el.tagName === 'INPUT' && String( el.className ).indexOf('nino-admin-input') !== -1 } );

	check( 'F. the dialog of the export asks for the name of the package - that of the one block, or "Blocks of <site>" for several -, a key made of it, the version 1.0.0 and a description that may stay empty',
		inputs.length === 4 && inputs[0].value === 'Karten' && inputs[1].value === 'karten' && inputs[2].value === '1.0.0' && inputs[3].value === '' );

	pressed( box, W('export/submit') );
	const asked = request( w, 'themelab/export' );

	check( 'F. the export is asked before the file: the keys, the key, the version and the name of the package, "check" - so that a refusal is said in the dialog',
		asked !== undefined && asked.payload.check === true && asked.payload.keys.join() === 'karten' && asked.payload.key === 'karten' && asked.payload.version === '1.0.0' && asked.payload.name === 'Karten' && w.posted.length === 0 );

	answerOf( asked, 400, { error : 'x', code : 'themelab_export_version', field : 'version' } );

	check( 'F. a refusal of the export is said at its field and the dialog stays; no file is asked for', w.posted.length === 0 && textOf( box ).indexOf( W('export/version') ) !== -1 && box.open !== false );

	w.requests.length = 0;
	pressed( box, W('export/submit') );
	answerOf( request( w, 'themelab/export' ), 200, { file : 'karten-1.0.0.tar.gz', size : 100, blocks : 1 } );

	const form = w.posted[0];
	const fields = form === undefined ? {} : Object.fromEntries( findAll( form, function( el ) { return el.tagName === 'INPUT' } ).map( function( el ) { return [ el.name, el.value ] } ) );

	check( 'F. where it can be made the file is asked for by a form that posts into a frame nobody sees - the action, the data without "check", and the token of the page - and the dialog says that the package is being downloaded',
		form !== undefined && form.method === 'post' && form.target === 'themelab-download' && fields.action === 'themelab/export' && JSON.parse( fields.data ).keys.join() === 'karten' && JSON.parse( fields.data ).check === undefined && w.said.indexOf( W( 'export/done', 'karten-1.0.0.tar.gz' ) ) !== -1 );
}

{
	const { w } = shown('#themelab');
	const importBar = findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && el.id === 'themelab-import' } )[0];

	fire( importBar, 'click' );

	const first = dialogOf( w );

	pressed( first, W('import/check') );

	check( 'F. the import asks for a package: with none chosen it says so at the field and asks nothing of the server', textOf( first ).indexOf( W('import/no-file') ) !== -1 && request( w, 'themelab/import-check' ) === undefined );

	const input = findAll( first, function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];

	input.files = [ { name : 'blocks.tar.gz', size : 100 } ];
	fire( input, 'change' );
	pressed( first, W('import/check') );

	const checking = request( w, 'themelab/import-check' );
	const preview = { id : 'a'.repeat( 32 ), seconds : 900, pack : { key : 'p', version : '1.0.0', name : 'Paket' }, blocks : [
		{ key : 'aa', name : 'Aa', category : 'content', variants : [ 'Standard', 'Dunkel' ], images : 1, state : 'free', origin : '', replace : false },
		{ key : 'bb', name : 'Bb', category : 'intro', variants : [], images : 0, state : 'taken', origin : 'project', replace : true },
		{ key : 'cc', name : 'Cc', category : 'trust', variants : [], images : 0, state : 'taken', origin : 'basis', replace : false },
	] };

	check( 'F. the file that was chosen is sent with the request of the check, and nothing else of it', checking !== undefined && checking.payload.locale === 'de_DE' );

	answerOf( checking, 200, preview );
	fire( first, 'close' );

	const shownBox = dialogOf( w );
	const ticks = findAll( shownBox, function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' } );
	const selects = findAll( shownBox, function( el ) { return el.tagName === 'SELECT' } );
	const optionsOf = function( select ) { return findAll( select, function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.value } ).join() };

	check( 'F. what the package holds is shown before anything is written: each block a row with a box that is ticked, its name and key, its category, its variants and picture places; where the key is taken the choice to skip - the default - or to take a copy with a key of its own, and to replace only where the block that has the key is an own one',
		textOf( shownBox ).indexOf('Paket') !== -1 && ticks.length === 3 && ticks.every( function( tick ) { return tick.checked === true } ) && selects.length === 2 && optionsOf( selects[0] ) === 'skip,copy,replace' && optionsOf( selects[1] ) === 'skip,copy'
		&& textOf( shownBox ).split( W('import/exists') ).length === 3 && textOf( shownBox ).indexOf( W('import/taken') ) === -1 && textOf( shownBox ).indexOf( 'Static content' ) !== -1 && textOf( shownBox ).indexOf( W('import/col-variants')+ ': 2' ) !== -1 );

	selects[0].value = 'replace';
	fire( selects[0], 'change' );

	const warned = findAll( shownBox, function( el ) { return String( el.className ).indexOf('themelab-import-warn') !== -1 } ).filter( function( el ) { return el.hidden !== true } ).length;
	const red = findAll( shownBox, function( el ) { return String( el.className ).split(' ').indexOf('is-replace') !== -1 } ).length;

	check( 'F. the choice to replace is marked red and says what it does: it overwrites the files of the block', warned === 1 && red === 1 );

	ticks.forEach( function( tick ) { tick.checked = false; fire( tick, 'change' ) } );
	pressed( shownBox, W('import/submit') );

	check( 'F. a package with no block ticked is not imported: the dialog says so and asks nothing', request( w, 'themelab/import' ) === undefined && w.said.indexOf( W('import/none') ) !== -1 );

	ticks[0].checked = true;
	fire( ticks[0], 'change' );
	ticks[1].checked = true;
	fire( ticks[1], 'change' );
	pressed( shownBox, W('import/submit') );
	fire( shownBox, 'close' );

	const importing = request( w, 'themelab/import' );

	check( 'F. what is sent is the key of the preview and the choices - which blocks and what is done where a key is taken - and nothing of the package itself',
		importing !== undefined && importing.payload.id === 'a'.repeat( 32 ) && JSON.stringify( importing.payload.items ) === JSON.stringify( { aa : { take : true, how : 'skip' }, bb : { take : true, how : 'replace' }, cc : { take : false, how : 'skip' } } ) );

	answerOf( importing, 200, { results : [ { key : 'aa', name : 'Aa', result : 'written', as : 'aa' }, { key : 'bb', name : 'Bb', result : 'failed', as : '', message : W('import/failed-invalid') }, { key : 'cc', name : 'Cc', result : 'left', as : '' } ] } );

	const result = dialogOf( w );

	check( 'F. the result says for each block what became of it, and where one failed which one and why - and that what was written before stays',
		textOf( result ).indexOf( W( 'import/result-written', 'Aa' ) ) !== -1 && textOf( result ).indexOf( W( 'import/result-failed', 'Bb', W('import/failed-invalid') ) ) !== -1 && textOf( result ).indexOf( W( 'import/result-left', 'Cc' ) ) !== -1 && textOf( result ).indexOf( W( 'import/stopped', 'Bb' ) ) !== -1 );
}


{
	const { w } = shown('#themelab');

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && el.id === 'themelab-import' } )[0], 'click' );

	const first = dialogOf( w );
	const input = findAll( first, function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];

	input.files = [ { name : 'blocks.tar.gz', size : 100 } ];
	fire( input, 'change' );
	pressed( first, W('import/check') );
	answerOf( request( w, 'themelab/import-check' ), 200, { id : 'b'.repeat( 32 ), seconds : 900, pack : { key : 'p', version : '1.0.0', name : 'Paket' }, blocks : [ { key : 'aa', name : 'Aa', category : 'content', variants : [], images : 0, state : 'free', origin : '', replace : false } ] } );
	fire( first, 'close' );

	const preview = dialogOf( w );

	pressed( preview, 'Cancel' );
	fire( preview, 'close' );

	const dropped = request( w, 'themelab/import-drop' );

	check( 'F. a preview that is left - Cancel or Escape - lets go of the bytes that are held for it: the key of the preview is sent, and the title of the dialog is the one of the import',
		dropped !== undefined && dropped.payload.id === 'b'.repeat( 32 ) && request( w, 'themelab/import' ) === undefined && textOf( preview ).indexOf( W('import/title') ) !== -1 );

}


// --- G. several own blocks taken away together -------------------------------------------------------------------------------

{
	const { w } = shown('#themelab');
	const boxes = findAll( w.page, function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' && /Cards|Held/.test( String( el.attributes['aria-label'] ) ) } );

	boxes.forEach( function( box ) { box.checked = true; fire( box, 'click' ) } );

	const bar = findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).trim() === W('delete/many') && el.id === '' } )[0];

	w.requests.length = 0;
	fire( bar, 'click' );

	const question = dialogOf( w );
	const counted = textOf( question ).indexOf( W( 'delete/many-title', 2 ) ) !== -1 && w.requests.length === 0;

	pressed( question, W('delete/many') );
	fire( question, 'close' );

	const first = w.requests.filter( function( entry ) { return entry.action === 'themelab/remove' } );

	check( 'G. several blocks are taken away after ONE question that says how many, and nothing is asked of the server before the answer: then each key is a request of its own - the right, the token and the checks of one block - the first before the second',
		counted === true && first.length === 1 && first[0].payload.key === 'karten' );

	answerOf( first[0], 200, { key : 'karten' } );

	const second = w.requests.filter( function( entry ) { return entry.action === 'themelab/remove' } )[1];

	answerOf( second, 403, { error : 'x', code : 'themelab_forbidden' } );

	check( 'G. a block that the server refuses does not stop the others: each key was asked, in the order of the rows, whatever the answer to the one before',
		second !== undefined && second.payload.key === 'held' && w.requests.filter( function( entry ) { return entry.action === 'themelab/remove' } ).length === 2 );
}

// --- H. a frame of the site as a block of the project -----------------------------------------------------------------------------

/** What was asked of the panel, in the order it was asked */
const asked = function( w, action ) { return w.requests.filter( function( entry ) { return entry.action === action } ) };

// The block of a frame stands in the list of the own blocks as the block of a frame: its category is the frame's, its picture is the tile of the blocks - asked as the picture of any
// block is, by its key - and a press on the row opens the editor, as for any own block; the details, which the pencil opens, do not ask for a category
{
	const list = LIST( { counts : { blocks : 2, frames : 2, frameBlocks : 1 }, blocks : LIST().blocks.concat( [ { key : 'mein-kopf', name : { de_DE : 'Mein Kopf', en_US : 'My head' }, description : { de_DE : 'Kopf', en_US : 'Head' }, category : 'header', group : 'header', variants : 0, rank : 0, frame : true, template : 'frame-header', origin : 'project', own : true, openable : true } ] ) } );
	const { w } = shown( '#themelab', list );
	const head = rowsOfList( w ).find( function( row ) { return row.dataset.row === 'mein-kopf' } );
	const tiles = asked( w, 'themelab/frame-tile' ).concat( asked( w, 'themelab/tile' ) ).map( function( entry ) { return entry.action+ ':'+ entry.payload.key+ ':'+ ( entry.payload.template ?? '' ) } );

	check( 'H. the block of a frame is a row of the list with the category of the frames and no line that sends the person to the frames of the site - it is edited like any block; its picture is asked for as the picture of any block is - by its key and no template, and no action of the frames - like the picture of the blocks of a page',
		head !== undefined && textOf( head ).indexOf( W('cat/header') ) !== -1 && findAll( head, function( el ) { return String( el.className ).indexOf('builder-cell-note') !== -1 } ).length === 0 && tiles.indexOf( 'themelab/tile:mein-kopf:' ) !== -1 && tiles.indexOf( 'themelab/tile:karten:' ) !== -1 && tiles.filter( function( entry ) { return entry.indexOf('themelab/frame-tile') === 0 } ).length === 0 );

	w.requests.length = 0;
	fire( head, 'click' );

	check( 'H. a press on the row of the block of a frame opens it in the editor of the blocks - as the row of any own block does - and not its details: the address is the block\'s',
		w.state.hash === '#themelab/mein-kopf' && request( w, 'themelab/open' ) !== undefined && request( w, 'themelab/open' ).payload.key === 'mein-kopf' && w.dialogs.length === 0 );

	const again = shown( '#themelab', list ).w;
	const pencil = findAll( again.page, function( el ) { return el.tagName === 'BUTTON' && ( el.attributes['aria-label'] ?? el.attributes.title ?? '' ) === W( 'row/details', 'My head' ) } )[0];

	again.requests.length = 0;
	fire( pencil, 'click' );
	const opened = dialogOf( again );

	check( 'H. the pencil of the row of the block of a frame opens its details - a name, a description and a rank, and no category to choose',
		opened !== undefined && textOf( opened ).indexOf( W( 'details/title', 'My head' ) ) !== -1 && findAll( opened, function( el ) { return el.tagName === 'SELECT' } ).length === 0 );

	pressed( opened, W('details/submit') );
	const sent = request( again, 'themelab/rename' );

	check( 'H. what the details of a block of a frame send has the category of its side', sent !== undefined && sent.payload.category === 'header' && sent.payload.key === 'mein-kopf' );

	fire( opened, 'close' );
}

// The tab of the theme counts the own blocks of the frames besides the blocks and the frames
{
	const { w } = shown( '#themelab/info', LIST( { tab : 'info', counts : { blocks : 2, frames : 2, frameBlocks : 3 } } ) );

	check( 'H. the tab of the theme counts the own blocks of the frames', textOf( w.page ).indexOf( W('info/frame-blocks') ) !== -1 && /3/.test( textOf( w.page ).slice( textOf( w.page ).indexOf( W('info/frame-blocks') ) ) ) );
}

// The way to the frames of the site: a link to the tab of the pages for the one who holds that right and designs - the list says so - on the tab of the theme and under the list of the blocks of a
// header or of a footer, and for nobody else
{
	const link = function( now ) { return findAll( now.page, function( el ) { return el.tagName === 'A' && el.dataset.key === 'frames-at' } ) };
	const info = shown( '#themelab/info', LIST( { tab : 'info', framesAt : true } ) ).w;
	const plain = shown( '#themelab/info', LIST( { tab : 'info', framesAt : false } ) ).w;
	const headed = LIST( { framesAt : true, blocks : LIST().blocks.concat( [ { key : 'mein-kopf', name : { de_DE : 'Mein Kopf', en_US : 'My head' }, description : { de_DE : 'Kopf', en_US : 'Head' }, category : 'header', group : 'header', variants : 0, rank : 0, frame : true, template : 'frame-header', origin : 'project', own : true, openable : true } ] ) } );
	const blocks = shown( '#themelab', headed ).w;

	check( 'H. the way to the frames of the site is a link to the tab Header/footer of the pages - #builder-frames - on the tab of the theme for the account the list says may go there, and none for another',
		link( info ).length === 1 && link( info )[0].href === '#builder-frames' && textOf( link( info )[0] ) === W('frames-at/link') && textOf( info.page ).indexOf( W('frames-at/note') ) !== -1 && link( plain ).length === 0 && textOf( plain.page ).indexOf( W('frames-at/note') ) === -1 );

	const category = findAll( blocks.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-category' } )[0];

	category.value = 'header';
	fire( category, 'change' );

	check( 'H. under the list of the blocks of a header or of a footer the same link stands - and under no other list of blocks', link( blocks ).length === 1 && link( shown( '#themelab', LIST( { framesAt : true, blocks : headed.blocks } ) ).w ).length === 0 );
}

// The fixed addresses have the @ that no key of a block has: a block whose key is "frames", "theme" or "theme-blocks" opens its editor by its address, the tabs go by their @ address, and
// the address of before, without the @, is the block where the project has one of that key and else the tab
{
	[ 'frames', 'theme', 'theme-blocks' ].forEach( function( key ) {

		const own = shown( '#themelab/'+ key, LIST( { ownKeys : [ 'karten', key ] } ) ).w;
		const opened = request( own, 'themelab/open' );

		check( 'K. the block "'+ key+ '" of the project opens its editor by #themelab/'+ key, opened !== undefined && opened.payload.key === key && own.state.hash === '#themelab/'+ key );
	} );

	const tabs = [ [ '@frames', 'blocks', '' ], [ '@frames/header', 'blocks', '' ], [ '@theme', 'info', '@theme' ], [ '@theme-blocks', 'blocks', '@blocks/nino' ] ].map( function( pair ) {

		const w = shown( '#themelab/'+ pair[0], LIST( { tab : pair[1], frames : [], ownKeys : [ 'karten', 'frames', 'theme', 'theme-blocks' ] } ) ).w;

		return request( w, 'themelab/open' ) === undefined && w.state.hash === '#themelab'+ ( pair[2] === '' ? '' : '/'+ pair[2] );
	} );

	check( 'K. the theme is at #themelab/@theme, the theme\'s blocks at #themelab/@blocks/nino - the address #themelab/@theme-blocks of before is written so - and the address of the tab of the frames, which this panel has no more, is the list of the blocks: #themelab/@frames and #themelab/@frames/header are written #themelab - whatever blocks the project has: no editor opens', tabs.join() === 'true,true,true,true' );

	const old = [ [ 'frames', 'blocks', '' ], [ 'theme', 'info', '@theme' ], [ 'theme-blocks', 'blocks', '@blocks/nino' ] ].map( function( triple ) {

		const w = shown( '#themelab/'+ triple[0], LIST( { tab : triple[1], frames : [], ownKeys : [ 'karten' ] } ) ).w;

		return request( w, 'themelab/open' ) === undefined && w.state.hash === '#themelab'+ ( triple[2] === '' ? '' : '/'+ triple[2] );
	} );

	check( 'K. the addresses of before, without the @, open the tab where no block of that key is: and are written with the @', old.join() === 'true,true,true' );
}

// The address names a block by its key and a tab or a source by a part with the @, which no key has: a block called "blocks" opens as the block, and a theme called "theme" is a source of
// the list and not the theme in use - that is what the address of before, #themelab/@theme-blocks, stands for
{
	const blocks = shown( '#themelab/blocks', LIST( { ownKeys : [ 'karten', 'blocks' ] } ) ).w;
	const opened = request( blocks, 'themelab/open' );

	check( 'K2. the block "blocks" of the project opens its editor by #themelab/blocks - the part of the tab of the blocks is @blocks, which no key has',
		opened !== undefined && opened.payload.key === 'blocks' && blocks.state.hash === '#themelab/blocks' );

	const sources = [ { key : 'theme', label : { en_US : 'Theme', de_DE : 'Theme' }, theme : false }, { key : 'nino', label : { en_US : 'Nino', de_DE : 'Nino' }, theme : true } ];
	const rows = THEME().blocks.map( function( block, at ) { return Object.assign( {}, block, { origin : at === 0 ? 'theme' : 'nino' } ) } );
	const named = shown( '#themelab/@blocks/theme', LIST( { sources : sources, blocks : rows } ) ).w;
	const select = findAll( named.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' } )[0];
	const old = shown( '#themelab/@theme-blocks', LIST( { sources : sources, blocks : rows } ) ).w;
	const oldSelect = findAll( old.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' } )[0];

	check( 'K2. a theme whose key is "theme" is the source #themelab/@blocks/theme - its blocks - and the address of before, #themelab/@theme-blocks, is the theme in use, written as its own source',
		select.value === 'theme' && rowsOfList( named ).length === 1 && named.state.hash === '#themelab/@blocks/theme' && oldSelect.value === 'nino' && old.state.hash === '#themelab/@blocks/nino' );

	check( 'K2. the resolution of an address is fixed by structure: every fixed part has the @ that no key of a block has - the key of a block is of the form [a-z][a-z0-9-]* - and the theme in use has a name of its own, not the key "theme"',
		/const FIXED = \{ '@theme-blocks' : 'theme', '@frames' : 'frames', '@theme' : 'info', '@blocks' : 'blocks' \};/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === true && /const IN_USE = '@in-use';/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === true
		&& /\^\[a-z\]\[a-z0-9-\]\{0,63\}\$/.test( fs.readFileSync( scriptPath, 'utf8' ) ) === true );
}

// A block that is made with the key of a fixed address - "frames" - opens as the block: the key is a key of the project from the moment the server has made it
{
	const x = shown( '#themelab', LIST( { ownKeys : [ 'karten', 'held' ] } ) );
	const w = x.w;

	fire( findAll( w.page, function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === W('new/button') } )[0], 'click' );

	const dialog = dialogOf( w );

	typed( dialog, [ 'Frames', 'Frames' ] );
	pressed( dialog, W('new/submit') );
	answerOf( request( w, 'themelab/create' ), 200, { key : 'frames' } );
	fire( dialog, 'close' );

	check( 'K. a new block whose key is "frames" is opened as that block, at #themelab/frames - not read as the tab of the frames', w.state.hash === '#themelab/frames' && request( w, 'themelab/open' ) !== undefined && request( w, 'themelab/open' ).payload.key === 'frames' );
}

// The block of a header in the editor of the blocks: the root is the header, there are no variants and no category, the details say what the block reads of the site, and the dialog of a source says
// which text is the block's own and which the website's
{
	const head = { n : 'header-wrap', s : '', a : { id : 'header' }, c : [ { t : '\n\t' }, { n : 'button', s : '~button', a : {}, c : null }, { t : '\n\t' }, { n : 'button', s : '/project/company/general/name', a : {}, c : null }, { t : '\n' } ] };
	const w = sheetOf( '#themelab/mein-kopf', {
		key : 'mein-kopf', side : 'header', stamp : 'f1', filled : [ { locale : 'en_US', parts : [ '[button]' ] } ], name : { de_DE : 'Mein Kopf', en_US : 'My head' },
		tree : [ FRAME_HEAD, { t : '\n' }, head, { t : '\n' }, FRAME_FOOT, { t : '\n' } ],
		texts : { '/template/page-home/header/button' : { de_DE : 'Hallo', en_US : 'Hello' } },
		known : { 'header-wrap' : { label : 'Header', icon : 'panel-top', id : 'id', source : 'none' }, button : { label : 'Button', icon : 'mouse-pointer-click', id : '', source : 'text' } },
		schemas : Object.assign( {}, pageAnswer().schemas, { 'header-wrap' : schema( 'Header', 'panel-top', 'none', { side : 'header' } ), button : schema( 'Button', 'mouse-pointer-click', 'text' ) } ),
		details : { side : 'header', reads : [ '/project/company/general/name' ], names : { de_DE : 'Mein Kopf', en_US : 'My head' }, descriptions : { de_DE : 'K', en_US : 'H' }, category : 'header', rank : 0, images : {}, locales : [ 'de_DE', 'en_US' ], placeholders : [] },
	} );
	const sheet = w.builder.editor.state();

	check( 'M. the block of a header is opened as a sheet of the editor of the blocks - its address is the block\'s - and has no variant: the bar has the details and no button of a variant',
		w.state.hash === '#themelab/mein-kopf' && sheet.sheet === 'block' && sheet.adapter.panel === 'themelab' && sheet.adapter.frameBlock === true && sheet.view.bar.buttons.details !== undefined && sheet.view.bar.buttons.variant === undefined );

	check( 'M. the quiet place the picture of the block of a frame has next to it says it is the page - and a block of a page keeps its sentence', sheet.adapter.base().hint === W('preview/page') && W('preview/page') !== W('preview/place') );

	fire( sheet.view.bar.buttons.details, 'click' );

	const box = dialogOf( w );
	const heads = findAll( box, function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-expanded'] !== undefined } ).map( function( el ) { return textOf( el ).trim() } );

	check( 'M. the details of the block of a frame are the name, the choice - the rank only, the category is its side\'s - and what the block reads of the website: no places of pictures',
		heads.length === 3 && heads[0].indexOf( W('details/name') ) === 0 && heads[1].indexOf( W('details/order') ) === 0 && heads[2].indexOf( W('details/reads') ) === 0 && heads[2].indexOf( '1' ) !== -1 && findAll( box, function( el ) { return el.tagName === 'SELECT' } ).length === 0 );

	findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).indexOf( W('details/order') ) === 0 } )[0].dispatchEvent( { type : 'click', bubbles : true } );

	check( 'M. the details say which languages were given the text of the working language at the last save, and for which parts - the name of the language and the part',
		textOf( box ).indexOf( '[button]' ) !== -1 && textOf( box ).indexOf( 'English' ) !== -1 && sheet.extra.filled.length === 1 );

	check( 'M. the choice of the block of a frame has the rank and no category to choose: it is of the category of its side', findAll( box, function( el ) { return el.tagName === 'SELECT' } ).length === 0 && findAll( box, function( el ) { return el.tagName === 'INPUT' && el.type === 'number' } ).length === 1 );

	findAll( box, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).indexOf( W('details/reads') ) === 0 } )[0].dispatchEvent( { type : 'click', bubbles : true } );

	check( 'M. the block says which texts of the website it reads, as the draft has them now - the key of each call whose source is one - and none of its own',
		textOf( box ).indexOf( '/project/company/general/name' ) !== -1 && textOf( box ).indexOf( W('details/reads-note') ) !== -1 );

	fire( box, 'close' );

	// what the draft reads changes with the draft: a call that reads the text of the website no more is no read
	const none = sheet.top.find( function( node ) { return node.n === 'header-wrap' } );

	none.c[3].s = '~name';
	fire( sheet.view.bar.buttons.details, 'click' );

	check( 'M. a block whose calls read no text of the website says so', textOf( dialogOf( w ) ).indexOf( W('details/reads') ) !== -1 && ( function() { const again = dialogOf( w ); findAll( again, function( el ) { return el.tagName === 'BUTTON' && textOf( el ).indexOf( W('details/reads') ) === 0 } )[0].dispatchEvent( { type : 'click', bubbles : true } ); return textOf( again ).indexOf( W('details/reads-none') ) !== -1; } )() );
}

// The properties of the parts of a header in the editor, as the kernel declares them: a header is built like a section, of a row and its columns. The wrap has what a section has - the pin of
// the header ("Stays at the top"), the ground, the picture and its veil, the height - and the row and the column have their own settings, as they have in a section; the menu button of the
// navigation is in the sheet of its look. Text alignment and spacing are no setting of the wrap, so they are no row of it: the sheet shows what has an effect
{
	const choice = { type : 'choice', label : 'Menu button', hint : '', default : 'auto', options : [ 'auto', 'always' ], labels : { auto : 'Automatic', always : 'Always' } };
	const pick = function( label, fallback, options, extra ) { return Object.assign( { type : 'choice', label : label, hint : '', default : fallback, options : options, optionLabels : null, responsive : false, min : null, max : null, class : true }, extra ?? {} ) };
	const flag = function( label ) { return { type : 'bool', label : label, hint : '', default : false, options : null, optionLabels : null, responsive : false, min : null, max : null, class : true } };
	const w = sheetOf( '#themelab/mein-kopf', {
		key : 'mein-kopf', side : 'header', stamp : 'f1', name : { de_DE : 'Mein Kopf', en_US : 'My head' },
		tree : [ FRAME_HEAD, { t : '\n' }, { n : 'header-wrap', s : '', a : { id : 'header' }, c : [ { t : '\n\t' }, { n : 'section-row', s : '', a : {}, c : [ { t : '\n\t\t' }, { n : 'section-col', s : '', a : { size : 'fill' }, c : [ { t : '\n\t\t\t' }, { n : 'header-nav', s : '', a : {}, c : [] }, { t : '\n\t\t' } ] }, { t : '\n\t' } ] }, { t : '\n' } ] }, { t : '\n' }, FRAME_FOOT, { t : '\n' } ],
		known : { 'header-wrap' : { label : 'Header', icon : 'panel-top', id : 'id', source : 'none' }, 'section-row' : { label : 'Row', icon : 'rows-2', id : '', source : 'none' }, 'section-col' : { label : 'Column', icon : 'columns-3', id : '', source : 'none' }, 'header-nav' : { label : 'Menu', icon : 'menu', id : '', source : 'none' } },
		schemas : Object.assign( {}, pageAnswer().schemas, {
			'header-wrap' : schema( 'Header', 'panel-top', 'none', { side : 'header', attributes : { id : { type : 'id', label : 'Id', hint : '', default : '' }, sticky : { type : 'bool', label : 'Stays at the top', hint : '', default : false },
				ground : pick( 'Ground', 'base', [ 'base', 'alt', 'tint', 'primary', 'dark' ] ), image : { type : 'image', label : 'Picture', hint : '', default : '', class : false }, picture : pick( 'Show picture', 'on', [ 'on', 'off' ] ), dim : flag( 'Dim the picture' ), height : pick( 'Height', 'auto', [ 'auto', '60', '80', '100' ] ) } } ),
			'section-row' : schema( 'Row', 'rows-2', 'none', { attributes : { width : pick( 'Width', 'regular', [ 'narrow', 'regular', 'wide', 'full' ] ), gap : pick( 'Gap', '2', [ '0', '1', '2', '3', '4' ] ), valign : pick( 'Vertical alignment', 'start', [ 'start', 'center', 'end' ] ), halign : pick( 'Horizontal alignment', 'start', [ 'start', 'center', 'end', 'between' ] ), reverse : pick( 'Reverse', 'off', [ 'off', 'on' ], { responsive : true } ) } } ),
			'section-col' : schema( 'Column', 'columns-3', 'none', { attributes : { size : pick( 'Width', '100', [ '25', '50', '100', 'auto', 'fill' ], { responsive : true } ), align : pick( 'Alignment', 'start', [ 'start', 'center', 'end' ], { responsive : true } ), hide : pick( 'Hide', 'off', [ 'off', 'on' ], { responsive : true } ), ground : pick( 'Ground', 'none', [ 'none', 'base', 'alt', 'tint', 'primary', 'dark' ] ), pad : pick( 'Padding', 'auto', [ 'auto', '0', '1', '2', '3', '4' ] ) } } ),
			'header-nav' : schema( 'Menu', 'menu', 'none', { attributes : { collapse : choice } } ),
		} ),
		details : { side : 'header', reads : [], names : { de_DE : 'Mein Kopf', en_US : 'My head' }, descriptions : { de_DE : 'K', en_US : 'H' }, category : 'header', rank : 0, images : {}, locales : [ 'de_DE', 'en_US' ], placeholders : [] },
	} );
	const sheet = w.builder.editor.state();
	const keysOf = function( node ) { return findAll( node, function( el ) { return el.dataset !== undefined && ( el.dataset.key !== undefined || el.dataset.stepKey !== undefined ) } ).map( function( el ) { return el.dataset.key ?? el.dataset.stepKey } ).sort() };
	const keysAt = function( rel ) {
		leaveBox( w );
		sheet.adapter.props = 'panel';
		w.builder.editor.properties( sheet.top.findIndex( function( node ) { return node.n === 'header-wrap' } ), rel, element('button'), function() {} );

		return keysOf( boxOf( w ) ).join();
	};
	const head = keysAt('root');
	const row = keysAt('1');
	const column = keysAt('1.1');
	const nav = keysAt('1.1.1');
	const named = function( keys ) { return keys.split(',').map( function( key ) { return key.replace( /^[^:]*:/, '' ).replace( /:.*$/, '' ) } ) };
	const has = function( keys, names ) { return names.every( function( name ) { return named( keys ).indexOf( name ) !== -1 } ) };
	const lacks = function( keys, names ) { return names.every( function( name ) { return named( keys ).indexOf( name ) === -1 } ) };

	check( 'F326 b. the sheet of the header of a frame has the settings the header declares - it stays at the top, the ground, the picture with its veil and the height - and no text alignment and no spacing: those are no settings of a header',
		has( head, [ 'sticky', 'ground', 'picture', 'dim', 'height' ] ) === true && lacks( head, [ 'align', 'space', 'pad', 'gap', 'size', 'width', 'reverse' ] ) === true );

	check( 'F326 b. the row of a header has the settings of a row - width, gap, both alignments and the reversed order - and the column of it those of a column - width with the sizes auto and fill, alignment, hiding, the ground and the padding - and neither has the settings of the header',
		has( row, [ 'width', 'gap', 'valign', 'halign', 'reverse' ] ) === true && lacks( row, [ 'sticky', 'ground', 'height', 'dim' ] ) === true
		&& has( column, [ 'size', 'align', 'hide', 'ground', 'pad' ] ) === true && lacks( column, [ 'sticky', 'height', 'dim', 'gap', 'valign' ] ) === true );

	check( 'F326 b. the sheet of the menu of a header has the setting the menu declares: the menu button is a row of its look, as it is in the sheet of a part of a page',
		/collapse/.test( nav ) === true );
}

{
	const css = fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );
	const script = fs.readFileSync( scriptPath, 'utf8' );
	const variantWords = function( code ) { return [ ...fs.readFileSync( path.join( featureDir, 'text/'+ code+ '.php' ), 'utf8' ).matchAll( /\[\[\/_admin\/themelab\/(variants\/[a-z-]+)\]\]'\s*=> '([^']*)'/g ) ] };

	check( 'F294 T3. the overview of the variants is a list of rows - not a table, which folds from four actions - with the buttons always in view, a row with the name to choose it and the buttons to rename, move and delete; the word "Vorlage" / "template" is in no word of the variants, and "Manage variants" and the dialog of it are no more',
		/\.builder-var-row \{\s*display: flex;/.test( css ) === true && /\.builder-var-actions \{\s*flex: 0 0 auto;\s*display: flex;/.test( css ) === true && /\.builder-var-pick \{[^}]*min-height: 2rem;/.test( css ) === true
		&& script.indexOf('askManage') === -1 && script.indexOf('variants/manage') === -1 && [ 'de_DE', 'en_US' ].every( function( code ) { return variantWords( code ).length > 20 && variantWords( code ).every( function( pair ) { return /Vorlage|template|Manage|verwalten/i.test( pair[2] ) === false && /manage/.test( pair[1] ) === false } ) } ) );
}

{
	const css = fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );
	const rule = function( selector ) { const found = new RegExp( selector.replace( /[.[\]()]/g, '\\$&' )+ ' \\{([^}]*)\\}' ).exec( css ); return found === null ? '' : found[1] };

	check( 'F299 T2. a row of the variants has no box-shadow of an input and keeps its parts on one middle line: the row and the button that chooses the variant have align-items: center, the button has no shadow, and its focus is drawn by an outline',
		/align-items: center;/.test( rule('.builder-var-row') ) === true && /align-items: center;/.test( rule('.builder-var-pick') ) === true && /box-shadow: none;/.test( rule('.builder-var-pick') ) === true
		&& /outline: 2px solid/.test( rule('.builder-var-pick:focus-visible') ) === true && /baseline/.test( rule('.builder-var-pick') ) === false );
}

{
	const lists = function( w ) { return w.requests.filter( function( entry ) { return entry.action === 'themelab/list' } ).length };
	const tilesAsked = function( w ) { return w.requests.filter( function( entry ) { return entry.action === 'themelab/tile' } ).length };
	const look = function( watch ) { if( watch.gone === true ) return; watch.callback( [ { isIntersecting : true, target : watch.nodes[0] } ] ) };

	// the list is asked for once at the start: the panel asks, and the first address it follows is that list - the second asks again, whatever the time between
	const x = shown( '#themelab', LIST() );

	check( 'F317 a. the list is asked for and drawn ONCE at the start - the first address that is followed is the list the panel asked for -, and the second asks again: Back from the editor right after the start loads the list anew',
		lists( x.w ) === 0 && ( x.w.panel.follow(), lists( x.w ) === 1 ) );

	// a first address that names a block uses the mark up: the list of the next address is asked for
	const block = shown( '#themelab/karten', LIST( { ownKeys : [ 'karten' ] } ) ).w;

	block.requests.length = 0;
	block.state.hash = '#themelab';
	block.panel.follow();

	check( 'F317 a. the mark of the start is used up by the first address, whatever it names: the block that was opened first leaves the list to be asked for', lists( block ) === 1 );

	// the pictures: asked when the box is on screen, not before, and once
	const w = shown( '#themelab', LIST( { blocks : LIST().blocks.concat( [ Object.assign( {}, LIST().blocks[0], { key : 'zweites', name : { de_DE : 'Zweites', en_US : 'Second' } } ) ] ) } ), { observer : true } ).w;
	const before = tilesAsked( w );
	const rows = rowsOfList( w ).length;
	const first = w.observers[0];
	const watchers = w.observers.length;

	look( first );

	const after = tilesAsked( w );

	look( first );

	check( 'F317 b. the picture of a block is asked for when its box is on screen and not before - a box of a row that is not seen asks nothing -, and once for a box: the observer of a box that was seen is let go, and a second look asks nothing',
		before === 0 && rows > 1 && watchers >= rows && after === 1 && tilesAsked( w ) === 1 && first.gone === true && w.observers.filter( function( one ) { return one.gone === false } ).length === watchers - 1 );

	const plain = shown( '#themelab', LIST() ).w;

	check( 'F317 b. where the browser has no observer the picture of every row is asked for at once', tilesAsked( plain ) >= rowsOfList( plain ).length && tilesAsked( plain ) > 1 );
}

// A page that is open in the pane of the pages has its view in the level builder-page. The sheet of a block of this panel is drawn with the same editor and takes that level for itself: the pane of the
// pages shows its list when it is shown next, and no empty page
{
	const x = shown( '#themelab/karten' );
	const w = x.w;
	const levels = function( id ) { return findAll( w.page, function( el ) { return el.id === id } )[0] };
	const pageLevel = levels('builder-page');
	const listLevel = levels('builder-list');

	w.requests.length = 0;
	w.panel.follow();
	pageLevel.classList.remove('admin-hidden');
	pageLevel.appendChild( element('div') );
	listLevel.classList.add('admin-hidden');
	answerOf( request( w, 'themelab/open' ), 200, SHEET() );

	check( 'F326 a. the sheet of a block that is opened while a page is open in the pane of the pages takes the level of the page for itself and gives the pane its list: nothing is left of the page, the list is the level that shows',
		pageLevel.children.length === 0 && pageLevel.classList.contains('admin-hidden') === true && listLevel.classList.contains('admin-hidden') === false );
}

// The editor is the pane of the pages' as well: a page opened there while a sheet of this panel is open takes the sheet - the level of the sheet is emptied - and the list that was unloaded for the
// sheet is drawn by nobody else. The panel that is shown next asks for its list again and shows it, and the level of the sheet that is gone is not the one that shows
{
	const x = shown('#themelab/karten');
	const w = x.w;
	const levels = function( id ) { return findAll( w.page, function( el ) { return el.id === id } )[0] };
	const list = levels('themelab-list');
	const sheet = levels('themelab-sheet');

	w.requests.length = 0;
	w.panel.follow();
	answerOf( request( w, 'themelab/open' ), 200, SHEET() );

	const open = w.builder.editor.state().sheet === 'block' && sheet.children.length > 0 && list.children.length === 0;

	// The pane of the pages opens a page: that is a closeSheet() of the editor, and the address is the one of the pages
	w.builder.editor.close();
	w.state.hash = '#builder/home';
	w.requests.length = 0;
	w.panel.showCurrent();

	const asked = request( w, 'themelab/list' );

	if( asked !== undefined )
		answerOf( asked, 200, LIST() );

	check( 'F344 a. the panel that comes back after a page of the pane of the pages took its sheet asks for its list and draws it: the list level has its rows and shows, the level of the sheet that is gone is hidden and empty',
		open === true && asked !== undefined && rowsOfList( w ).length === 2 && list.classList.contains('admin-hidden') === false && sheet.classList.contains('admin-hidden') === true && sheet.children.length === 0 );

	// the block that was lost is forgotten: shown once more, the panel asks for nothing
	w.requests.length = 0;
	w.state.hash = '#builder/home';
	w.panel.showCurrent();

	check( 'F344 a. the block the editor no longer holds is forgotten once the list is drawn: the panel shown once more asks for nothing and has no block open', request( w, 'themelab/list' ) === undefined && list.classList.contains('admin-hidden') === false );
}

{
	// The choice of where the blocks come from is drawn by the bar of the workbench where that has a select of its own, and by a select field put in front of the search where it has not: the options
	// are one list for both, and the way it is asked for is the same - both ways are tried with a bar that is made to be the one or the other
	const way = function( bar, list ) {

		const w = opened();
		const calls = [];
		const tables = [];
		const original = w.ui.filterBar;
		const table = w.ui.table;

		w.ui.filterBar = function( options ) {

			calls.push( options );

			const made = original.call( this, Object.assign( {}, options, { filter : undefined } ) );

			// A bar of the workbench with a select of its own answers with the select; one that has none answers with its field of the search alone
			if( bar === 'with' )
				made.select = element('select');
			else
				delete made.select;

			return made;
		};
		w.ui.table = function( options ) { tables.push( options ); return table.call( this, options ) };

		w.state.hash = '#themelab';
		w.panel.showCurrent();
		w.requests.shift().callback( { status : 200, responseJSON : list ?? LIST(), getAllResponseHeaders : function() { return 'x' } } );

		const fields = findAll( w.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-from' } );
		const asked = tables.map( function( options ) { return options.filter } ).filter( function( filter ) { return filter !== undefined && filter !== null } )[0];
		const barred = calls.map( function( options ) { return options.filter } ).filter( function( filter ) { return filter !== undefined && filter !== null && filter.options.length > 0 } )[0];
		const names = function( filter ) { return filter === undefined ? '' : filter.options.map( function( option ) { return option.value } ).join()+ '|'+ filter.value+ '|'+ filter.label };

		return { w : w, fields : fields, asked : asked, barred : barred, names : names };
	};
	const withBar = way('with');
	const withoutBar = way('without');
	const choices = 'all,own,nino|own|'+ W('source/label');

	check( 'F333 a. where the bar of the workbench has a select of its own the table is given the choice - the options, the one that is chosen and its name for a screen reader - and no select field is put in front of the search',
		withBar.names( withBar.asked ) === choices && withBar.fields.length === 0 && typeof withBar.asked.onChange === 'function' );

	check( 'F333 a. ...and where it has none the table is given no choice and the select field with the same options, the same one chosen and the same name stands in front of the search, once',
		withoutBar.asked === undefined && withoutBar.fields.length === 1 && findAll( withoutBar.fields[0], function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.value } ).join() === 'all,own,nino' && withoutBar.fields[0].value === 'own'
		&& withoutBar.fields[0].parentNode.classList.contains('themelab-from') === true );

	const noRows = LIST( { blocks : [] } );
	const emptyWith = way( 'with', noRows );
	const emptyWithout = way( 'without', noRows );

	check( 'F333 a. a list with no block has the choice alone: in the bar where the workbench has a select, in a select field where it has none - with the options of the list that has blocks',
		emptyWith.names( emptyWith.barred ) === choices && emptyWith.fields.length === 0 && emptyWithout.fields.length === 1 && emptyWithout.barred === undefined );

	withBar.asked.onChange( 'all' );
	withoutBar.fields[0].value = 'all';
	fire( withoutBar.fields[0], 'change' );

	check( 'F333 a. a choice made in the bar and one made in the field are the same act: the address of the list is the same, and the choice is the one the list is drawn for',
		withBar.w.state.hash === withoutBar.w.state.hash && withBar.w.requests.length === withoutBar.w.requests.length );
}

{
	// F344: the list of the blocks has the category beside the search: one select with "all" first and the categories the blocks of the source have, narrowing the rows together with the search and the
	// choice of the source, kept over the drawing of the list anew
	const x = shown('#themelab');
	const w = x.w;
	const bar = function( now ) { return findAll( now.page, function( el ) { return String( el.className ).split(/\s+/).indexOf('nino-admin-table-toolbar') !== -1 } )[0] };
	const category = function( now ) { return findAll( now.page, function( el ) { return el.tagName === 'SELECT' && el.dataset.key === 'themelab-category' } )[0] };
	const search = function( now ) { return findAll( now.page, function( el ) { return String( el.className ).split(/\s+/).indexOf('nino-admin-table-search') !== -1 } )[0] };
	const keys = function( now ) { return rowsOfList( now ).map( function( row ) { return row.dataset.row } ).join() };
	const options = function( select ) { return findAll( select, function( el ) { return el.tagName === 'OPTION' } ).map( function( el ) { return el.value } ).join() };
	const choose = function( now, value ) { category( now ).value = value; fire( category( now ), 'change' ) };
	const kids = bar( w ).children;

	check( 'F344 e. the list has a select for the category after the search in the bar over it: "all" is the first and the one that is chosen, then the categories its blocks have in the order the server lists them; it is named for a screen reader as the column is',
		category( w ) !== undefined && kids.indexOf( search( w ) ) !== -1 && kids.indexOf( category( w ) ) > kids.indexOf( search( w ) ) && options( category( w ) ) === 'all,intro,content' && category( w ).value === 'all'
		&& category( w ).attributes['aria-label'] === W('list/category') && keys( w ) === 'karten,held' );

	choose( w, 'intro' );

	check( 'F344 e. a category narrows the rows to the blocks of it, the choice of the source and the search go with it: the search of the table is kept over the drawing of the list, and a search that finds nothing in the category has the sentence of the list',
		keys( w ) === 'held' && category( w ).value === 'intro' );

	search( w ).value = 'karten';
	fire( search( w ), 'input' );

	check( 'F344 e. ...a search that finds nothing in the category shows the sentence of the list and no row', keys( w ) === '' && textOf( w.page ).indexOf( W('list/no-match') ) !== -1 );

	search( w ).value = '';
	fire( search( w ), 'input' );
	w.panel.reload();
	answerOf( w.requests.filter( function( entry ) { return entry.action === 'themelab/list' } ).pop(), 200, LIST() );

	check( 'F344 e. ...the choice stays when the list is asked for and drawn anew', category( w ).value === 'intro' && keys( w ) === 'held' );

	choose( w, 'all' );

	const one = shown( '#themelab', LIST( { blocks : LIST().blocks.map( function( block ) { return Object.assign( {}, block, { category : 'content', group : 'content' } ) } ) } ) ).w;
	const theirs = shown( '#themelab/@blocks/nino', THEME() ).w;

	// a category that the list now drawn has no block of is no choice: it is "all", and the list is not empty for it
	const stale = shown('#themelab').w;

	choose( stale, 'intro' );
	stale.panel.reload();
	answerOf( stale.requests.filter( function( entry ) { return entry.action === 'themelab/list' } ).pop(), 200, LIST( { blocks : LIST().blocks.map( function( block ) { return Object.assign( {}, block, { category : 'content', group : 'content' } ) } ) } ) );

	check( 'F344 e. "all" shows every block again; a category the list drawn next has no block of is dropped, and the list shows all its blocks; a source whose blocks have one category has no select for it - there is nothing to choose; the blocks of a source have the select as the own ones do, with the categories they have',
		keys( w ) === 'karten,held' && keys( stale ) === 'karten,held' && category( stale ) === undefined && category( one ) === undefined && category( theirs ) !== undefined && options( category( theirs ) ) === 'all,intro,content' );
}

{
	// F344: the picture of a block in a row of the list is 11 rem wide - what the stylesheet declares for it outside of the rules of a narrow screen
	const sheet = fs.readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' ).replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/g, '' );
	const width = [];

	sheet.replace( /([^{}]+)\{([^{}]*)\}/g, function( all, selectors, body ) {

		if( selectors.split(',').map( function( one ) { return one.trim().replace( /\s+/g, ' ' ) } ).indexOf('#themelab-app .themelab-tile') !== -1 )
			body.split(';').forEach( function( line ) { if( line.slice( 0, Math.max( line.indexOf(':'), 0 ) ).trim() === 'width' ) width.push( line.slice( line.indexOf(':') + 1 ).trim() ) } );

		return all;
	} );

	check( 'F344 e. the picture of a block in a row of the list is 11 rem wide', width.pop() === '11rem' );
}

{
	// The word for the head and the foot of a page: in German "Kopfbereich", "Fußbereich" and "Kopf-/Fußbereich", in English "header" and "footer" and "header/footer" - the old words are in no text of the
	// panel and in no line of its manual - a technical name, as frame-header or [frame ...], is no word of it. The exceptions are named by their text, each with its reason
	const readWords = function( file, locale ) {

		const text = fs.readFileSync( path.join( featureDir, file ), 'utf8' );
		const pairs = [ ...text.matchAll( /^\t'\[\[([^\]]+)\]\]'\s*=> '((?:[^'\\]|\\.)*)',\s*$/gm ) ].map( function( found ) { return [ found[1], found[2] ] } );

		return pairs;
	};
	const manual = function( locale ) {

		return [ ...fs.readFileSync( path.join( featureDir, 'feature.php' ), 'utf8' ).matchAll( new RegExp( "'"+ locale+ "' => '((?:[^'\\\\]|\\\\.)*)'", 'g' ) ) ].map( function( found ) { return [ 'feature.php', found[1] ] } );
	};
	const OLD = { de_DE : /Rahmen|\bHeader|\bFooter|Kopfzeile|Fußzeile/, en_US : /\b[Ff]rames?\b/ };
	const EXCEPTIONS = [];
	const offending = function( locale ) {

		return readWords( 'text/'+ locale+ '.php', locale ).concat( manual( locale ) ).filter( function( pair ) { return OLD[locale].test( pair[1].replace( /\b(?:frame|header|footer)-[a-z.-]+|\[frame\b[^\]]*\]/g, '' ) ) === true && EXCEPTIONS.indexOf( pair[1] ) === -1 } ).map( function( pair ) { return pair[0] } );
	};
	const keysOf = function( locale ) { return readWords( 'text/'+ locale+ '.php', locale ).map( function( pair ) { return pair[0] } ).sort().join() };

	check( 'F296. the words of Themelab say "Kopfbereich", "Fußbereich" and "Kopf-/Fußbereich" in German and "header", "footer" and "header/footer" in English: no text of the panel and no line of its manual has "Rahmen", "Header" or "Footer" in German or "frame" in English for the head and the foot of a page, but the exceptions that are named here - and both languages have the same keys',
		offending('de_DE').length === 0 && offending('en_US').length === 0 && keysOf('de_DE') === keysOf('en_US') && /Kopf-\/Fußbereich/.test( fs.readFileSync( path.join( featureDir, 'text/de_DE.php' ), 'utf8' ) ) === true );
}

done();
