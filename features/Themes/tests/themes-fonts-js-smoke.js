/**
 *	Nino features
 *	themes-fonts-js-smoke.js	What the script of the tab of the fonts does (assets/admin-fonts.js), over the kernel's stand-in of a page, the real
 *														Nino.adminUi and the real script of the panel of the themes beside it:
 *
 *														A. the tab: the name the workbench asks it by, what showing it asks, and that the panel of the themes has no strip
 *														   of its own for it
 *														B. the fonts: one request and never the repository's, the installed families, where the brand uses them, what a
 *														   name is drawn as
 *														C. the repository: a state that cannot be asked, the button that loads the list, the list, the search and the pages
 *														D. an install: one request, what the brand is offered then, where the focus goes, a refusal
 *														E. a delete and the licence
 *														F. the settings: the tab holds no field of them and leads to their form
 *
 *														The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js
 *														of the checkout, three levels up or the one NINO_ROOT names. themes-fonts-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Themes/tests/themes-fonts-js-smoke.js
 *	       NINO_ROOT=../nino node features/Themes/tests/themes-fonts-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, fire, documentOf, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const panelScript = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const viewsScript = path.relative( root, path.join( featureDir, 'assets/admin-fonts.js' ) );
const script = fs.readFileSync( path.join( featureDir, 'assets/admin-fonts.js' ), 'utf8' );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

// The words of the views and of the API, by their keys: a test of what a view says asks for the fill and never writes the sentence
const W = function( key, ...params ) { return String( words.en['/_admin/themes/fonts/'+ key] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };
const E = function( code, ...params ) { return String( words.en['/_admin/error/'+ code] ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };

const FONTS = [ { key : 'system-sans', name : 'system-sans', system : true }, { key : 'system-serif', name : 'system-serif', system : true }, { key : 'system-mono', name : 'system-mono', system : true } ];

/** An installed family as the API says it */
function installed( key, name, change ) {
	return Object.assign( { key : key, name : name, faces : [ [ 400, 'normal', '400.woff2' ], [ 700, 'normal', '700.woff2' ], [ 400, 'italic', '400-italic.woff2' ] ], size : 250000, license : 'OFL-1.1', version : '1.2', category : 'sans', source : 'https://github.com/example/'+ key, made : true, licensed : true, used : [] }, change ?? {} );
}

/** A family of the list as the API says it */
function family( slug, name, change ) {
	return Object.assign( { slug : slug, family : name, category : 'sans', version : '1.0', license : 'OFL-1.1', source : 'https://github.com/example/'+ slug, weights : [ 400, 700 ], styles : [ 'normal' ], size : 180000, problem : '', installed : null, foreign : false }, change ?? {} );
}

/** What themes/fonts answers */
function view( change ) {
	return Object.assign( { installed : [], repository : { state : 'ready', host : 'fonts.example.test', ownKey : true, settings : true }, available : null, fonts : FONTS.slice() }, change ?? {} );
}

/**
 *	The two scripts in a page of their own: the workbench's ui, a router that keeps the address, the requests held until a test answers them
 *
 *	@param		{Object}		[options]			{ lang, themes (false: the tab of the themes was never shown) }
 *
 *	@return		{Object}
 */
function world( options ) {

	options = Object.assign( { lang : 'en-US', themes : true }, options ?? {} );

	const page = element('div');
	const app = element('div');
	const list = element('div');
	const theme = element('div');
	const wrap = element('div');
	const mount = element('div');
	const requests = [];
	const dialogs = [];
	const state = { hash : '' };

	page.id = 'page';
	app.id = 'themes-app';
	list.id = 'themes-list';
	theme.id = 'themes-theme';
	theme.className = 'admin-hidden';
	wrap.id = 'admin-page-wrap';
	app.appendChild( list );
	app.appendChild( theme );
	mount.id = 'themes-fonts-list';
	page.appendChild( app );
	page.appendChild( mount );
	page.appendChild( wrap );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) {
		const created = element( tag );
		created.getBoundingClientRect = function() { return { width : 600, height : 600 } };
		if( tag === 'dialog' ) {
			created.open = false;
			created.showModal = function() { created.open = true };
			created.close = function() { created.open = false; fire( created, 'close' ) };
			dialogs.push( created );
		}
		return created;
	};

	// The router of the workbench itself, over the panes of the panel: what the tab writes is read back by it
	const codec = shellRouter( { themes : [ [ 'themes', 'themes' ], [ 'themes-fonts', 'fonts' ], [ 'themes-css', 'css' ], [ 'themes-settings', 'settings' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		isActive : function() { return true },
		go : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
	} );

	const dirty = { register : function() {}, isDirty : function() { return false }, refresh : function() {}, guard : function( names, proceed ) { proceed() } };

	const Nino = {
		dir : '/site',
		slug : function( text ) { return String( text ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ) },
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return ( options.lang === 'de-DE' ? words.de[key] : words.en[key] ) ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) {
			requests.push( { uri : uri, action : data.action, payload : JSON.parse( data.data ), callback : callback } );
		} },
		admin : { router : router, dirty : dirty },
	};

	function Watcher() {
		this.observe = function() {};
		this.unobserve = function() {};
		this.disconnect = function() {};
	}

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder, Date : Date };

	sandbox.window = {
		Nino : Nino,
		IntersectionObserver : Watcher,
		location : { hash : '', href : 'http://workbench.test/_admin/' },
		confirm : function() { return true },
		setTimeout : function() { return 1 },
		clearTimeout : function() {},
	};

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	load( panelScript, context );
	load( viewsScript, context );

	const w = {
		Nino : Nino, doc : doc, app : app, list : list, mount : mount, requests : requests, dialogs : dialogs, state : state,
		panel : Nino.admin.themes,
		tab : Nino.admin['themes-fonts'],
		sent : [],

		/** Answer the oldest request that waits, and give back what it was */
		answer : function( status, body ) {
			const request = requests.shift();
			w.sent.push( request );
			request.callback( { status : status, responseJSON : body } );
			return request;
		},

		/** The tab is shown, as the workbench shows it: the list of the themes first, where the test has it shown before - its fonts are those of the brand */
		show : function() {

			if( options.themes === true && w.panel.data === null ) {
				state.hash = '#themes';
				w.panel.showCurrent();
				w.answer( 200, { themes : [], rejected : [], groups : [], fonts : FONTS.slice(), limits : { css : 1000, name : 60 }, pages : [], active : null, foreign : false, unreadable : false, canRestore : false, last : null, state : '', brandStamp : 'x' } );
			}

			state.hash = '#themes/fonts';
			w.tab.showCurrent();
		},

		box : function() { return mount },
		byClass : function( from, cls ) { return findAll( from, function( node ) { return String( node.className ).split(/\s+/).indexOf( cls ) !== -1 } ) },
		byTag : function( from, tag ) { return findAll( from, function( node ) { return node.tagName === tag.toUpperCase() } ) },
		text : function( node ) { return node === undefined || node === null ? '' : node.textContent + node.children.map( w.text ).join('') },
		button : function( from, label ) { return findAll( from, function( node ) { return node.tagName === 'BUTTON' && w.text( node ).trim() === label } )[0] },
		said : function() { return w.text( doc.getElementById('themes-fonts-say') ) },

		/** Press an answer of the question that is open */
		choose : function( label ) {
			const dialog = dialogs.filter( function( one ) { return one.open === true } ).pop();
			fire( w.button( dialog, label ), 'click' );
		},
	};

	return w;
}

// --- A. the tab ---------------------------------------------------------------------------------------------------------------

{
	const w = world();

	check( 'A. the tab is in the namespace of the workbench under its uri, themes-fonts, with the function the shell calls when it is shown - and loading the scripts asks nothing', typeof w.tab === 'object' && typeof w.tab.showCurrent === 'function' && w.requests.length === 0 );
	check( 'A. the panel of the themes has no views of its own and draws no strip: the three tabs of the pane are the registry\'s, in the head, and the two are not drawn twice - what the tab takes from the panel is where the focus lands and a way to draw an open theme once more', w.panel.views === undefined && typeof w.panel.land === 'function' && typeof w.panel.redraw === 'function' );

	w.show();
	check( 'A. showing the tab asks themes/fonts through the endpoint of the workbench - one request, with no payload, and not the list of the repository', w.requests.length === 1 && w.requests[0].action === 'themes/fonts' && w.requests[0].uri === '/site/_admin/' && JSON.stringify( w.requests[0].payload ) === '{}'
		&& w.doc.getElementById('themes-tabs') === null );
	w.answer( 200, view() );

	w.tab.showCurrent();
	check( 'A. showing it once more asks once more: where the brand uses a family may have changed on the tab of the themes', w.requests.length === 1 && w.requests[0].action === 'themes/fonts' );

	w.tab.showCurrent();

	const first = w.requests.shift();

	w.answer( 200, view( { installed : [ installed( 'inter', 'Inter' ) ] } ) );
	first.callback( { status : 200, responseJSON : view() } );
	check( 'A. of two answers for the screen the one of the later request is drawn, whichever comes last', w.byClass( w.box(), 'themes-font' ).length === 1 );

	const alone = world( { themes : false } );

	alone.show();
	alone.answer( 200, view( { fonts : FONTS.concat( [ { key : 'inter', name : 'Inter', system : false } ] ) } ) );
	check( 'A. a tab that is shown before the themes ever were draws all the same: there is no list of the brand to put the fonts into yet, and the themes ask for theirs when they are shown', alone.byTag( alone.box(), 'section' ).length === 2 && alone.panel.data === null );
}

// --- B. the fonts ---------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view() );

	const titles = w.byTag( w.box(), 'h3' ).map( function( heading ) { return [ W('installed/title'), W('available/title') ].find( function( title ) { return w.text( heading ).indexOf( title ) === 0 } ) } );

	check( 'B. the tab has two halves, Installed and Available, each a section named by its heading - and no title of its own: the head of the pane names the screen', w.byTag( w.box(), 'h2' ).length === 0 && titles.join('|') === [ W('installed/title'), W('available/title') ].join('|')
		&& w.byTag( w.box(), 'section' ).every( function( section ) { return w.doc.getElementById( section.attributes['aria-labelledby'] ) !== null } ) );
	check( 'B. with no family installed the first half says so, and how one gets there', w.text( w.byClass( w.box(), 'nino-admin-empty' )[0] ) === W('installed/empty') );
	check( 'B. the list of the repository is not asked for by showing the view: the half says that nothing is loaded, and the only request was themes/fonts', w.requests.length === 0 && w.sent.map( function( one ) { return one.action } ).join() === 'themes/list,themes/fonts' && w.text( w.box() ).indexOf( W('available/unloaded') ) !== -1 );

	const failing = world();

	failing.show();
	failing.answer( 403, { error : 'not allowed' } );
	check( 'B. a view the server refuses says so in the place of the halves and draws none', failing.byTag( failing.box(), 'section' ).length === 0 && failing.byClass( failing.box(), 'nino-admin-error' ).length > 0 );

	const full = world();

	full.show();
	full.answer( 200, view( { installed : [
		installed( 'open-sans', 'Open Sans', { used : [ 'font-text', 'font-button' ] } ),
		installed( 'inter-var', 'Inter <b>Var</b>', { faces : [ [ '100 900', 'normal', '100-900.woff2' ] ], used : [], size : 900 } ),
		installed( 'by-hand', 'By Hand', { made : false, version : '', license : '', category : '', source : '', licensed : false } ),
	] } ) );

	const cards = full.byClass( full.box(), 'themes-font' );
	const card = function( key ) { return cards.find( function( one ) { return one.dataset.font === key } ) };

	check( 'B. a card for each installed family, by name', cards.map( function( one ) { return one.dataset.font } ).join() === 'by-hand,inter-var,open-sans' );
	check( 'B. a name is text and never markup: a family called Inter <b>Var</b> draws no element of its own', full.byTag( full.box(), 'b' ).length === 0 && full.text( full.byTag( card('inter-var'), 'h4' )[0] ) === 'Inter <b>Var</b>' );
	check( 'B. a card says the faces of the family, the axis of a weight as a range, and its size', full.text( card('open-sans') ).indexOf( W( 'installed/faces', '400, 700, 400 '+ W('installed/italic') ) ) !== -1 && full.text( card('inter-var') ).indexOf( W( 'installed/variable', '100–900' ) ) !== -1
		&& full.text( card('open-sans') ).indexOf( '244 KB' ) !== -1 && full.text( card('inter-var') ).indexOf( '1 KB' ) !== -1 );
	check( 'B. ...its version, its category and its licence, and for a folder that was put there by hand that it is one', full.text( card('open-sans') ).indexOf( W( 'installed/version', '1.2' ) ) !== -1 && full.text( card('open-sans') ).indexOf( 'OFL-1.1' ) !== -1 && full.text( card('open-sans') ).indexOf( W('category/sans') ) !== -1
		&& full.text( card('by-hand') ).indexOf( W('installed/own') ) !== -1 );

	const usedLine = full.doc.getElementById('themes-font-used-open-sans');
	const blocked = full.button( card('open-sans'), W('installed/remove') );

	const reason = full.byClass( card('open-sans'), 'nino-admin-help-text' )[0];

	check( 'B. a family the brand uses says where - the text and the buttons - its delete cannot be pressed, and the reason is the sentence behind the (?) beside that button, which describes it',
		full.text( usedLine ) === W( 'installed/used', W('role/font-text')+ ', '+ W('role/font-button') ) && blocked.disabled === true
		&& reason !== undefined && full.text( reason ) === W('installed/used-note') && blocked.attributes['aria-describedby'] === reason.id && blocked.parentNode === reason.parentNode.parentNode );
	check( 'B. a family the brand does not use says so and can be deleted', full.text( full.doc.getElementById('themes-font-used-inter-var') ) === W('installed/unused') && full.button( card('inter-var'), W('installed/remove') ).disabled === false );

	const source = full.byTag( card('open-sans'), 'a' )[0];

	check( 'B. the source of a family is a link that opens apart from the workbench and tells the other side nothing; a family without one has no link', source.href === 'https://github.com/example/open-sans' && source.target === '_blank' && source.rel === 'noopener noreferrer' && full.byTag( card('by-hand'), 'a' ).length === 0 );
	check( 'B. a folder that was put there by hand has no button that deletes it - nothing brings back what is in it - and its card says so', full.button( card('by-hand'), W('installed/remove') ) === undefined && full.text( card('by-hand') ).indexOf( W('installed/own-note') ) !== -1 && full.button( card('inter-var'), W('installed/remove') ) !== undefined );
	check( 'B. a family without a licence text has a button for it that cannot be pressed and says why', full.button( card('by-hand'), W('installed/license-show') ).disabled === true && full.button( card('by-hand'), W('installed/license-show') ).title === W('installed/license-none') );
}

// --- B2. the two halves and what explains them -----------------------------------------------------------------------------------

{
	// What the project has and what it can get are the two halves of one grid; every explanation is the sentence behind a (?) at the title or the name it belongs to, and
	// what stays in view is a case: an error, a count, a state
	const explaining = function( w, sentences ) {

		// Every element of the tab whose text is one of the sentences is the sentence behind a (?), and no paragraph, hint or notice says it open
		return sentences.every( function( sentence ) {
			const found = findAll( w.box(), function( node ) { return node.tagName !== undefined && w.text( node ) === sentence } );
			return found.length > 0 && found.every( function( node ) { return [ 'nino-admin-help', 'nino-admin-help-text' ].indexOf( String( node.className ) ) !== -1 } );
		} );
	};
	const w = world();

	w.show();
	w.answer( 200, view( { installed : [
		installed( 'open-sans', 'Open Sans', { used : [ 'font-text' ] } ),
		installed( 'by-hand', 'By Hand', { made : false, version : '', license : '', category : '', source : '', licensed : false } ),
	] } ) );

	const columns = w.byClass( w.box(), 'themes-fonts-cols' );
	const halves = columns.length === 1 ? columns[0].children : [];
	const head = function( half ) { return w.byTag( half, 'h3' )[0] };
	const sentenceOf = function( node ) { return w.text( w.byClass( node, 'nino-admin-help-text' )[0] ) };

	check( 'F349 a. the tab is one grid of two halves, what the project has and what it can get, in that order: the installed families, then the repository - each a section whose title is named by its words alone',
		columns.length === 1 && halves.length === 2 && halves.every( function( half ) { return half.tagName === 'SECTION' } ) && w.text( head( halves[0] ) ).indexOf( W('installed/title') ) === 0 && w.text( head( halves[1] ) ).indexOf( W('available/title') ) === 0
		&& w.byClass( w.box(), 'themes-fonts-half' ).length === 2 && halves.every( function( half ) { return half.attributes['aria-labelledby'] === w.byClass( head( half ), 'nino-admin-field-text' )[0].id } ) );
	check( 'F349 b. each half explains itself behind a (?) at its title: the installed ones what they are, the repository which host the button asks and that the list is fetched on the press - and the button that loads is described by that sentence',
		sentenceOf( head( halves[0] ) ) === W('installed/help') && sentenceOf( head( halves[1] ) ) === W( 'available/from-own', 'fonts.example.test' )+ ' '+ W('available/press')
		&& w.doc.getElementById('themes-fonts-load').attributes['aria-describedby'] === w.byClass( head( halves[1] ), 'nino-admin-help-text' )[0].id && w.byClass( head( halves[0] ), 'nino-admin-help-button' ).length === 1 && w.byClass( head( halves[1] ), 'nino-admin-help-button' ).length === 1 );

	const hand = w.byClass( w.box(), 'themes-font' ).find( function( one ) { return one.dataset.font === 'by-hand' } );
	const made = w.byClass( w.box(), 'themes-font' ).find( function( one ) { return one.dataset.font === 'open-sans' } );

	check( 'F349 c. no explanation stands open as a paragraph: the sentence of a folder that was put there by hand is behind a (?) at its name - a card that was installed has none there - and the sentence of a button that is off is behind a (?) at the button',
		explaining( w, [ W('installed/own-note'), W('installed/used-note'), W('installed/help'), W( 'available/from-own', 'fonts.example.test' )+ ' '+ W('available/press') ] ) === true && w.byClass( w.byClass( hand, 'themes-font-title' )[0], 'nino-admin-help-text' ).length === 1 && w.byClass( w.byClass( hand, 'themes-font-title' )[0], 'nino-admin-help-text' ).every( function( node ) { return w.text( node ) === W('installed/own-note') } )
		&& w.byClass( w.byClass( made, 'themes-font-title' )[0], 'nino-admin-help-text' ).length === 0 );
	check( 'F349 d. what is a state stays in view: the line of the use, the faces and the size, and where nothing is loaded the word of it beside the button', w.text( w.doc.getElementById('themes-font-used-open-sans') ) === W( 'installed/used', W('role/font-text') ) && w.text( made ).indexOf( W( 'installed/faces', '400, 700, 400 '+ W('installed/italic') ) ) !== -1
		&& w.text( w.byClass( w.box(), 'themes-fonts-bar' )[0] ).indexOf( W('available/unloaded') ) !== -1 );

	// The repository that is loaded: the count and the rows stay, and no sentence of the list is open
	fire( w.doc.getElementById('themes-fonts-load'), 'click' );
	w.answer( 200, Object.assign( { loaded : 2 }, view( { available : { fetched : 1794816000, stale : false, families : [ family( 'inter', 'Inter', { foreign : true } ), family( 'lora', 'Lora' ) ] } } ) ) );

	check( 'F349 e. a loaded list keeps the explanation behind the (?) at the title, shows its count and the error of a row, and says no sentence of the repository open',
		explaining( w, [ W( 'available/from-own', 'fonts.example.test' )+ ' '+ W('available/press'), W('installed/help') ] ) === true && sentenceOf( head( w.byClass( w.box(), 'themes-fonts-half' )[1] ) ) === W( 'available/from-own', 'fonts.example.test' )+ ' '+ W('available/press')
		&& w.text( w.box() ).indexOf( W( 'available/count', 2 ) ) !== -1 && w.text( w.box() ).indexOf( W('available/foreign') ) !== -1 && w.text( w.byClass( w.box(), 'themes-fonts-bar' )[0] ).indexOf( W('available/unloaded') ) === -1 );

	const empty = world();

	empty.show();
	empty.answer( 200, view() );
	check( 'F349 f. with no family installed the first half is the empty box and its title keeps the (?): the empty sentence is only the case and the next step, and nothing of the explanation is in it',
		empty.text( empty.byClass( empty.box(), 'nino-admin-empty' )[0] ) === W('installed/empty') && W('installed/empty').indexOf( W('installed/help') ) === -1 && sentenceOf( head( empty.byClass( empty.box(), 'themes-fonts-half' )[0] ) ) === W('installed/help') );
}

// --- C. the repository ------------------------------------------------------------------------------------------------------

{
	[ 'off', 'internal', 'unsupported', 'badkey' ].forEach( function( state ) {

		const w = world();

		w.show();
		w.answer( 200, view( { repository : { state : state, host : '', ownKey : false, settings : true } } ) );

		const notice = w.doc.getElementById('themes-fonts-off');
		const link = w.byTag( w.box(), 'a' )[0];

		check( 'C. a repository that is '+ state+ ' has no button that loads and no list: the half says why, in its own sentence, and leads to the settings of the workbench', notice !== null && w.text( notice ) === W( 'available/'+ state ) && W( 'available/'+ state ) !== 'undefined'
			&& w.doc.getElementById('themes-fonts-load') === null && link !== undefined && link.href === '#themes/settings' && w.text( link ) === W('available/settings') );
	} );

	const denied = world();

	denied.show();
	denied.answer( 200, view( { repository : { state : 'off', host : '', ownKey : false, settings : false } } ) );
	check( 'C. an account that may not open the settings is not led there', denied.byTag( denied.box(), 'a' ).length === 0 && denied.doc.getElementById('themes-fonts-off') !== null );

	const w = world();

	w.show();
	w.answer( 200, view() );

	const loadButton = w.doc.getElementById('themes-fonts-load');

	check( 'C. a repository that can be asked has the button that loads the list, by its name', loadButton !== null && w.text( loadButton ) === W('available/load') && loadButton.disabled === false );
	check( 'C. ...and says which host the button asks and whose key checks the list - the address itself is the server\'s', w.text( w.box() ).indexOf( W( 'available/from-own', 'fonts.example.test' ) ) !== -1 && W( 'available/from', 'x' ) !== W( 'available/from-own', 'x' ) );

	fire( loadButton, 'click' );
	check( 'C. pressing it asks themes/fonts-load - one request, no address in it: the server knows where the list is - and the button waits', w.requests.length === 1 && w.requests[0].action === 'themes/fonts-load' && JSON.stringify( w.requests[0].payload ) === '{}' && loadButton.disabled === true && w.said() === W('msg/loading') );

	w.answer( 502, { error : 'the font list signature does not verify', code : 'themes_fonts_signature', params : [], field : '' } );
	check( 'C. a list that is refused is said in the sentence of its failure, as a failure, the button can be pressed once more and has the focus', w.said() === E('themes_fonts_signature') && w.doc.getElementById('themes-fonts-say').className.indexOf('nino-admin-error') !== -1 && loadButton.disabled === false && loadButton.focused === true );

	const families = [ family( 'ibm-plex', 'Plex' ), family( 'open-sans', 'Open Sans', { installed : '1.0' } ), family( 'fraunces', 'Fraunces', { category : 'serif', weights : [ '100 900' ], installed : '0.9', version : '2.0' } ), family( 'noto', 'Noto <i>Sans</i> 2.0', { problem : 'family' } ),
		family( 'system-sans', 'System Sans', { problem : 'system' } ), family( 'inter', 'Inter', { foreign : true } ) ];

	for( let number = 1; number <= 26; number++ )
		families.push( family( 'family-'+ String( number ).padStart( 2, '0' ), 'Family '+ String( number ).padStart( 2, '0' ), { category : [ 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ][number % 6] } ) );

	fire( loadButton, 'click' );
	w.answer( 200, Object.assign( { loaded : families.length }, view( { available : { fetched : 1794816000, stale : false, families : families }, installed : [ installed( 'open-sans', 'Open Sans', { version : '1.0' } ), installed( 'fraunces', 'Fraunces', { version : '0.9' } ) ] } ) ) );

	const rowsOf = function() { return w.byClass( w.box(), 'themes-fonts-row' ) };
	const row = function( slug ) { return rowsOf().find( function( one ) { return one.dataset.slug === slug } ) };
	const search = w.doc.getElementById('themes-fonts-search');

	check( 'C. a list that is loaded is said, with the number of its families, and the focus goes to the search', w.said() === W( 'msg/loaded', 32 ) && search !== null && search.focused === true && w.text( w.doc.getElementById('themes-fonts-load') ) === W('available/reload') );
	check( 'C. the list shows twelve families of a page, by name, and says how many there are and which page it is', rowsOf().length === 12 && rowsOf()[0].dataset.slug === 'family-01' && w.text( w.box() ).indexOf( W( 'available/count', 32 ) ) !== -1 && w.text( w.box() ).indexOf( W( 'page/of', 1, 3 ) ) !== -1 );

	fire( w.button( w.box(), W('page/next') ), 'click' );
	fire( w.button( w.box(), W('page/next') ), 'click' );
	check( 'C. the last page holds the rest, its button for the next cannot be pressed and the focus is on the one for the page before', rowsOf().length === 8 && w.button( w.box(), W('page/next') ).disabled === true && w.button( w.box(), W('page/previous') ).focused === true && w.text( w.box() ).indexOf( W( 'page/of', 3, 3 ) ) !== -1 );

	search.value = 'SANS';
	fire( search, 'input' );
	check( 'C. the search finds by the name and by the slug, whatever the case, from the first page on - and asks nobody', rowsOf().map( function( one ) { return one.dataset.slug } ).join() === 'noto,open-sans,system-sans' && w.requests.length === 0 && w.text( w.box() ).indexOf( W( 'available/count', 3 ) ) !== -1 );
	check( 'C. a name of the list is text and never markup', w.byTag( w.box(), 'i' ).length === 0 && w.text( row('noto') ).indexOf( 'Noto <i>Sans</i> 2.0' ) !== -1 );
	check( 'C. a family the brand cannot name and one whose slug is a stack of the system say why and have no button', w.text( row('noto') ).indexOf( W('available/problem-family') ) !== -1 && w.byTag( row('noto'), 'button' ).length === 0 && w.text( row('system-sans') ).indexOf( W('available/problem-system') ) !== -1 && w.byTag( row('system-sans'), 'button' ).length === 0 );
	check( 'C. a family that is installed in the version of the list says so and has no button', w.text( row('open-sans') ).indexOf( W('available/installed') ) !== -1 && w.byTag( row('open-sans'), 'button' ).length === 0 );

	search.value = 'ibm';
	fire( search, 'input' );
	check( 'C. a family is found by its slug where its name does not say it: ibm finds Plex', rowsOf().map( function( one ) { return one.dataset.slug } ).join() === 'ibm-plex' );

	search.value = 'zzz';
	fire( search, 'input' );
	check( 'C. a search that finds nothing says so', rowsOf().length === 0 && w.text( w.box() ).indexOf( W('available/nomatch') ) !== -1 );

	search.value = '';
	fire( search, 'input' );

	const select = w.byTag( w.box(), 'select' )[0];

	select.value = 'serif';
	fire( select, 'change' );
	check( 'C. the category narrows the list: the serifs alone', rowsOf().every( function( one ) { return w.text( one ).indexOf( W('category/serif') ) !== -1 } ) && rowsOf().length === 6 && row('fraunces') !== undefined );
	check( 'C. a family that is installed in another version offers the update to the version of the list, and says the axis of its weight as a range', w.button( row('fraunces'), W( 'available/update', '2.0' ) ) !== undefined && w.text( row('fraunces') ).indexOf( W( 'installed/variable', '100–900' ) ) !== -1 );

	select.value = 'all';
	fire( select, 'change' );
	search.value = 'inter';
	fire( search, 'input' );
	check( 'C. a family whose folder is somebody else\'s says so and has no button', w.text( row('inter') ).indexOf( W('available/foreign') ) !== -1 && w.byTag( row('inter'), 'button' ).length === 0 );

	const old = world();

	old.show();
	old.answer( 200, view( { available : { fetched : 1794816000, stale : true, families : [ family( 'inter', 'Inter' ) ] } } ) );
	check( 'C. a list that is older than a day says so above its families, and the button that installs from it cannot be pressed until it is loaded once more', old.byClass( old.box(), 'nino-admin-notice' ).some( function( notice ) { return old.text( notice ) === W('available/stale') } )
		&& old.button( old.box(), W('available/install') ).disabled === true && old.doc.getElementById('themes-fonts-load').disabled === false );
}

// --- D. an install ------------------------------------------------------------------------------------------------------------

{
	const w = world();
	const list = [ family( 'inter', 'Inter' ), family( 'fraunces', 'Fraunces', { installed : '0.9', version : '2.0' } ) ];
	const available = function( change ) { return { fetched : 1794816000, stale : false, families : list.map( function( one ) { return Object.assign( {}, one, change[one.slug] ?? {} ) } ) } };

	w.show();
	w.answer( 200, view( { available : available( {} ), installed : [ installed( 'fraunces', 'Fraunces', { version : '0.9', used : [ 'font-title' ] } ) ], fonts : FONTS.concat( [ { key : 'fraunces', name : 'Fraunces', system : false } ] ) } ) );

	const row = function( slug ) { return w.byClass( w.box(), 'themes-fonts-row' ).find( function( one ) { return one.dataset.slug === slug } ) };
	const button = w.button( row('inter'), W('available/install') );

	check( 'D. the button that installs a family says which one to a screen reader', button.attributes['aria-label'] === W('available/install')+ ': Inter' );

	fire( button, 'click' );
	check( 'D. pressing it asks themes/fonts-install with the slug and nothing else - no address, no digest: those are the server\'s - and the button waits', w.requests.length === 1 && w.requests[0].action === 'themes/fonts-install' && JSON.stringify( w.requests[0].payload ) === '{"slug":"inter"}' && button.disabled === true && w.said() === W( 'msg/installing', 'Inter' ) );

	w.answer( 502, { error : 'the archive does not match what the list promised', code : 'themes_fonts_mismatch', params : [], field : '' } );
	check( 'D. an install that is refused is said in the sentence of its failure, the button can be pressed once more and has the focus, and the list of the brand is as it was', w.said() === E('themes_fonts_mismatch') && button.disabled === false && button.focused === true && w.panel.data.fonts.length === 4 );

	fire( button, 'click' );
	w.answer( 502, { error : 'the archive is refused: it holds "index.php", which the list does not name', code : 'themes_fonts_archive', params : [], field : '' } );
	check( 'D. a refusal of the archive is said in the language of the interface, whole: the reason of the server, which is English, is not put into the sentence', w.said() === E('themes_fonts_archive') && w.said().indexOf('index.php') === -1 && E('themes_fonts_archive').indexOf('%') === -1 );

	const brandList = w.panel.data.fonts;
	const drawn = { count : 0, list : '' };
	const redraw = w.panel.redraw;

	check( 'D. with no theme open there is nothing to draw again, and asking for it does nothing', redraw() === undefined && w.requests.length === 0 );
	w.panel.redraw = function() { drawn.count++; drawn.list = w.panel.data.fonts.map( function( font ) { return font.key } ).join(); redraw() };

	fire( button, 'click' );
	w.answer( 200, Object.assign( { result : { slug : 'inter', updated : false } }, view( { available : available( { inter : { installed : '1.0' } } ), installed : [ installed( 'fraunces', 'Fraunces', { version : '0.9', used : [ 'font-title' ] } ), installed( 'inter', 'Inter', { version : '1.0' } ) ],
		fonts : FONTS.concat( [ { key : 'fraunces', name : 'Fraunces', system : false }, { key : 'inter', name : 'Inter', system : false } ] ) } ) ) );

	check( 'D. a family that is installed is said, has its card among the installed, and the focus is on the name of that card', w.said() === W( 'msg/installed', 'Inter' ) && w.byClass( w.box(), 'themes-font' ).map( function( one ) { return one.dataset.font } ).join() === 'fraunces,inter' && w.doc.getElementById('themes-font-title-inter').focused === true );
	check( 'D. the brand is offered the family at once: the list of fonts the panel holds - the one the brand picks from - is the one of the answer, in place', w.panel.data.fonts === brandList && brandList.map( function( font ) { return font.key } ).join() === 'system-sans,system-serif,system-mono,fraunces,inter' );
	check( 'D. ...and a theme that is open on the other tab is drawn again from that list - once, after the list has the family', drawn.count === 1 && drawn.list === 'system-sans,system-serif,system-mono,fraunces,inter' );
	check( 'D. the row of the list says that it is installed and has no button any more', w.text( row('inter') ).indexOf( W('available/installed') ) !== -1 && w.byTag( row('inter'), 'button' ).length === 0 );

	const update = w.button( row('fraunces'), W( 'available/update', '2.0' ) );

	fire( update, 'click' );
	w.answer( 200, Object.assign( { result : { slug : 'fraunces', updated : true } }, view( { available : available( { inter : { installed : '1.0' }, fraunces : { installed : '2.0' } } ), installed : [ installed( 'fraunces', 'Fraunces', { version : '2.0', used : [ 'font-title' ] } ), installed( 'inter', 'Inter', { version : '1.0' } ) ],
		fonts : FONTS.concat( [ { key : 'fraunces', name : 'Fraunces', system : false }, { key : 'inter', name : 'Inter', system : false } ] ) } ) ) );
	check( 'D. an answer that leaves the fonts as they were - an update - draws nothing again', drawn.count === 1 );
	check( 'D. an update of a family the brand uses says that the stylesheet of the website has the faces as they were until it is built once more', w.said() === W( 'msg/updated-used', 'Fraunces' ) && W( 'msg/updated-used', 'x' ) !== W( 'msg/updated', 'x' ) );
}

// --- E. a delete and the licence ----------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view( { installed : [ installed( 'inter', 'Inter' ), installed( 'open-sans', 'Open Sans' ) ], fonts : FONTS.concat( [ { key : 'inter', name : 'Inter', system : false }, { key : 'open-sans', name : 'Open Sans', system : false } ] ) } ) );

	const card = function( key ) { return w.byClass( w.box(), 'themes-font' ).find( function( one ) { return one.dataset.font === key } ) };
	const remove = w.button( card('inter'), W('installed/remove') );

	fire( remove, 'click' );

	const dialog = w.dialogs.filter( function( one ) { return one.open === true } ).pop();

	check( 'E. a delete asks first, by the name of the family, and asks the server nothing before the answer', dialog !== undefined && w.text( dialog ).indexOf( W( 'installed/remove-title', 'Inter' ) ) !== -1 && w.text( dialog ).indexOf( W( 'installed/remove-message', 'Inter' ) ) !== -1 && w.requests.length === 0 );

	w.choose( workbench['/_admin/common/label/cancel'] );
	check( 'E. the answer no deletes nothing: no request, the card stays', w.requests.length === 0 && card('inter') !== undefined );

	fire( remove, 'click' );
	w.choose( W('installed/remove') );
	check( 'E. the answer yes asks themes/fonts-remove with the slug', w.requests.length === 1 && w.requests[0].action === 'themes/fonts-remove' && JSON.stringify( w.requests[0].payload ) === '{"slug":"inter"}' );

	w.answer( 409, { error : 'the brand uses the font "inter"', code : 'themes_fonts_used', params : [], field : 'slug' } );
	check( 'E. a delete the server refuses - the brand took the family in another tab - is said in its sentence, and the card stays', w.said() === E('themes_fonts_used') && card('inter') !== undefined );

	fire( w.button( card('inter'), W('installed/remove') ), 'click' );
	w.choose( W('installed/remove') );

	const brandList = w.panel.data.fonts;

	w.answer( 200, Object.assign( { result : { slug : 'inter' } }, view( { installed : [ installed( 'open-sans', 'Open Sans' ) ], fonts : FONTS.concat( [ { key : 'open-sans', name : 'Open Sans', system : false } ] ) } ) ) );
	check( 'E. a family that is deleted is said, its card is gone, the focus is on the heading of the installed - and the brand is not offered it any more', w.said() === W( 'msg/removed', 'Inter' ) && card('inter') === undefined && card('open-sans') !== undefined
		&& w.doc.getElementById('themes-fonts-installed-title').focused === true && w.panel.data.fonts === brandList && brandList.map( function( font ) { return font.key } ).join() === 'system-sans,system-serif,system-mono,open-sans' );

	const show = w.button( card('open-sans'), W('installed/license-show') );

	check( 'E. the button of the licence says that it opens something and what', show.attributes['aria-expanded'] === 'false' && w.doc.getElementById( show.attributes['aria-controls'] ) !== null );

	fire( show, 'click' );
	check( 'E. the licence is asked for when it is shown: themes/fonts-license with the slug', w.requests.length === 1 && w.requests[0].action === 'themes/fonts-license' && JSON.stringify( w.requests[0].payload ) === '{"slug":"open-sans"}' );

	w.answer( 200, { slug : 'open-sans', text : 'SIL Open Font License <script>alert(1)</script>' } );

	const pre = w.byTag( card('open-sans'), 'pre' )[0];

	check( 'E. it is drawn as the text it is, in a box a keyboard reaches to scroll it, named by the family - and never as markup', pre !== undefined && w.text( pre ) === 'SIL Open Font License <script>alert(1)</script>' && w.byTag( w.box(), 'script' ).length === 0 && pre.tabIndex === 0 && pre.attributes.role === 'region'
		&& pre.attributes['aria-label'].indexOf('Open Sans') !== -1 && show.attributes['aria-expanded'] === 'true' && w.text( show ) === W('installed/license-hide') );

	fire( show, 'click' );
	check( 'E. the button hides it', w.byTag( card('open-sans'), 'pre' ).length === 0 && show.attributes['aria-expanded'] === 'false' && w.text( show ) === W('installed/license-show') );

	fire( show, 'click' );
	check( 'E. ...and shows it once more without asking: the text was kept', w.requests.length === 0 && w.byTag( card('open-sans'), 'pre' ).length === 1 );

	const other = world();

	other.show();
	other.answer( 200, view( { installed : [ installed( 'inter', 'Inter' ) ] } ) );

	const otherShow = other.button( other.box(), W('installed/license-show') );

	fire( otherShow, 'click' );
	other.answer( 404, { error : 'there is no licence of that font', code : 'themes_fonts_unknown', params : [], field : 'slug' } );
	check( 'E. a licence that cannot be read is said, and nothing is shown', other.said() === E('themes_fonts_unknown') && other.byTag( other.box(), 'pre' ).length === 0 );
}

// --- F. the settings ----------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.show();
	w.answer( 200, view( { repository : { state : 'badkey', host : 'fonts.example.test', ownKey : true, settings : true }, installed : [ installed( 'inter', 'Inter' ) ] } ) );

	const link = w.byTag( w.box(), 'a' ).find( function( one ) { return one.href === '#themes/settings' } );

	check( 'F. the settings stand in one place, the tab the workbench draws from the manifest: this tab leads there by its address and holds no field of them - no input, no text area, no form',
		link !== undefined && w.byTag( w.box(), 'input' ).length === 0 && w.byTag( w.box(), 'textarea' ).length === 0 && w.byTag( w.box(), 'form' ).length === 0 && /fontsUrl|fontsKey|features\/settings/.test( script ) === false );
	check( 'F. the script makes no request of its own: every one goes to the API of the workbench, to an action of the fonts', /\bfetch\s*\(|XMLHttpRequest|sendBeacon/.test( script ) === false && ( script.match( /Nino\.adminUi\.api\.call\(/g ) ?? [] ).length === 1 && ( script.match( /\bcall\( '(fonts[a-z-]*)'/g ) ?? [] ).length === 5 );
}

done();
