/**
 *	Nino features
 *	themes-panel-js-smoke.js	What the script of the panel does over the kernel's stand-in of a page and the real Nino.adminUi - the accordion, the
 *														steps and the split view are the kernel's own:
 *
 *														A. the words: every key the script asks for is a fill of both interface languages, and none stands in the script as a sentence
 *														B. the list: what the API says, the themes that come with a package before the own ones, the active one, a part that rests,
 *														   what an own theme is made of; names are text and never markup
 *														C. one theme: it is opened and its draft asked for before the screen is drawn; the groups, the part each comes from, the focus
 *														D. the settings of a group: the part to choose from, a section for each component, the standard variant and a row of steps for
 *														   each knob, the page's own sections, the summary of a head and what is changed; the address follows the open section
 *														E. a change: the working mix, the draft asked for once the hand has rested and one request at a time, the frame loaded with the
 *														   token and the component to outline, a token that has run out, a draft that cannot be made
 *														F. another part: the draft is asked for at once, the knobs of the other part stand where the old ones were, the step that the
 *														   two have in common stays, a part that cannot be drawn is not taken
 *														G. the bar: state, reset, save, apply, and what each does - an own theme is written as it stands, a theme of a package is saved
 *														   under a name, a name that is taken is asked again, a stylesheet that is not the feature's is asked about, the website
 *														   shows a theme only when the question is answered with yes
 *														H. leaving: a draft asks before it is lost, in the registry of the shell and by the address
 *
 *														The helpers - check(), done(), findAll(), fire(), element(), documentOf() and load() - are those of tests/harness.js
 *														of the checkout, three levels up or the one NINO_ROOT names. themes-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Themes/tests/themes-panel-js-smoke.js
 *	       NINO_ROOT=../nino node features/Themes/tests/themes-panel-js-smoke.js
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
const script = fs.readFileSync( scriptPath, 'utf8' );
const relative = path.relative( root, scriptPath );

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

// --- A. the words ---------------------------------------------------------------------------------------------

{
	const asked = new Set();
	let match;
	const direct = /word\(\s*'([a-z-]+\/[a-z0-9-]+)'/g;
	const named = /'\/_admin\/themes\/([a-z-]+\/[a-z0-9-]+)'/g;

	while( ( match = direct.exec( script ) ) !== null )
		asked.add( match[1] );

	while( ( match = named.exec( script ) ) !== null )
		asked.add( match[1] );

	// The keys put together from a name or read by the panel of the shell: the sections of the page, the three widths, the two states, the plurals of a summary, the failure of a load
	[ 'word/type', 'word/measures', 'word/motion', 'word/more', 'word/mobile', 'word/tablet', 'word/desktop', 'state/saved', 'state/applied', 'msg/knob-one', 'msg/knobs', 'variant/regular', 'error/load' ].forEach( function( key ) { asked.add( key ) } );

	const missing = [];

	asked.forEach( function( key ) {
		if( words.en['/_admin/themes/'+ key] === undefined || words.de['/_admin/themes/'+ key] === undefined )
			missing.push( key );
	} );

	check( 'A. every word the script asks for - '+ asked.size+ ' of them - is a fill of the panel in both interface languages', missing.length === 0 && asked.size > 60 );
	check( 'A. no fill of the panel is left without a reader: each is asked for by the script, or by a key it puts together', ( function() {
		const unread = Object.keys( words.en ).filter( function( key ) { return key.indexOf('/_admin/themes/') === 0 && key.split('/').length === 5 && asked.has( key.slice( '/_admin/themes/'.length ) ) === false } );
		return unread.length === 0;
	} )() );
	check( 'A. the same fills stand in both languages, and a placeholder is the same in both', Object.keys( words.en ).filter( function( key ) { return key.indexOf('/_admin/themes/') === 0 } ).every( function( key ) {
		return words.de[key] !== undefined && ( words.en[key].match( /%[sdn]/g ) ?? [] ).join() === ( words.de[key].match( /%[sdn]/g ) ?? [] ).join();
	} ) );
	check( 'A. no sentence is written into the script: a text is set with word() and a name with textContent, never as markup', /innerHTML\s*=\s*[^'\s]/.test( script ) === false && /\.insertAdjacentHTML|document\.write|\.outerHTML\s*=/.test( script ) === false );
}

// --- the page -----------------------------------------------------------------------------------------------------

const GROUP_LABEL = { section : { de_DE : 'Abschnitt', en_US : 'Section' }, header : { de_DE : 'Header', en_US : 'Header' }, footer : { de_DE : 'Footer', en_US : 'Footer' }, brand : { de_DE : 'Marke', en_US : 'Brand' }, settings : { de_DE : 'Einstellungen', en_US : 'Settings' } };
const COMPONENT_LABEL = { 'section-wrap' : { de_DE : 'Wrap', en_US : 'Wrap' }, 'section-title' : { de_DE : 'Titel', en_US : 'Title' }, 'section-col' : { de_DE : 'Abschnitt', en_US : 'Section' }, 'header-wrap' : { de_DE : 'Kopf', en_US : 'Head' }, 'footer-wrap' : { de_DE : 'Fuß', en_US : 'Foot' } };

const ladder = function( ...values ) { return values };

/** What the register says of the knobs of a group with the part of a theme: the same four of the group, and one of the part's own */
function knobs( from, steps ) {

	const own = from.indexOf('craft') === 0 ? [ { key : 'section-col.x-shadow', label : { de_DE : 'Schatten', en_US : 'Shadow' }, extra : true, ladder : ladder( 'none', '1px', '2px', '3px', '4px' ) } ]
		: ( from.indexOf('nino') === 0 ? [ { key : 'section-title.x-rule', label : { de_DE : 'Linie', en_US : 'Rule' }, extra : true, ladder : ladder( '0px', '0px', '0px', '2px', '4px' ) } ] : [] );
	const wrap = from.indexOf('craft') === 0 ? ladder( '.6rem', '1.1rem', '1.75rem', '2.5rem', '3.5rem' ) : ladder( 'calc( 1rem * .5 )', 'calc( 1rem * .75 )', '1rem', 'calc( 1rem * 1.25 )', '4.5rem' );
	const list = [
		{ key : 'section-wrap.space', label : { de_DE : 'Abstand', en_US : 'Space' }, extra : false, ladder : wrap },
		{ key : 'section-title.size', label : { de_DE : 'Größe', en_US : 'Size' }, extra : false, ladder : ladder( '1rem', '1.1rem', '1.2rem', '1.3rem', '1.4rem' ) },
	].concat( own );

	return list.map( function( knob ) {
		const step = steps[knob.key] ?? 0;
		return Object.assign( { small : false, step : step, default : knob.ladder[2] }, knob );
	} );
}

/** What open and preview say of a mix: the group Section, whose part comes from nino or craft, a group Header with the standard part, and the page */
function describe( mix, rest ) {

	const part = mix.parts ?? {};
	const section = part.section ?? { from : 'standard' };
	const header = part.header ?? { from : 'standard' };
	const from = section.from ?? 'standard';
	const theme = from === 'standard' ? '' : from.split('@')[0];
	const standard = ( section.standard ?? {} )['section-wrap'] ?? ( theme === 'craft' ? 'soft' : 'regular' );
	const variants = [ { name : 'regular', label : null, source : 'schema' }, { name : theme === 'craft' ? 'soft' : 'band', label : { de_DE : theme === 'craft' ? 'Weiche Fläche' : 'Band', en_US : theme === 'craft' ? 'Soft surface' : 'Band' }, source : 'part' } ];
	const offers = [ { theme : 'nino', version : '1.0.0', ok : true, problem : '' }, { theme : 'craft', version : '1.0.0', ok : true, problem : '' }, { theme : 'rest', version : '1.0.0', ok : false, problem : 'uses !important' } ];

	return {
		groups : {
			page : { label : '', from : ( part.page ?? {} ).from ?? 'standard', theme : 'nino', version : '1.0.0', origin : 'installed', package : 'installed', problem : '', missing : false, components : [], offers : offers,
				knobs : [ { key : 'size', label : { de_DE : 'Grundgröße', en_US : 'Base size' }, extra : false, small : false, step : ( ( part.page ?? {} ).values ?? {} ).size ?? 0, default : { '--page-size' : '100%' }, ladder : [ { '--page-size' : '90%' }, { '--page-size' : '95%' }, { '--page-size' : '100%' }, { '--page-size' : '105%' }, { '--page-size' : '110%' } ] },
					{ key : 'motion', label : { de_DE : 'Bewegung', en_US : 'Motion' }, extra : false, small : false, step : 0, default : { '--page-motion' : '.15s' }, ladder : [ { '--page-motion' : '0s' }, { '--page-motion' : '.1s' }, { '--page-motion' : '.15s' }, { '--page-motion' : '.25s' }, { '--page-motion' : '.4s' } ] } ] },
			section : { label : GROUP_LABEL.section, from : from, theme : theme, version : theme === '' ? '' : '1.0.0', origin : theme === '' ? 'standard' : 'installed', package : theme === '' ? '' : 'installed', problem : rest === true ? 'the part is for basis ^9.0' : '', missing : false, offers : offers,
				knobs : knobs( from, section.values ?? {} ),
				components : [
					{ name : 'section-title', label : COMPONENT_LABEL['section-title'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' },
					{ name : 'section-wrap', label : COMPONENT_LABEL['section-wrap'], variants : variants, standard : standard },
					{ name : 'section-col', label : COMPONENT_LABEL['section-col'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' },
				] },
			header : { label : GROUP_LABEL.header, from : header.from ?? 'standard', theme : '', version : '', origin : 'standard', package : '', problem : '', missing : false, offers : [ offers[0] ], knobs : [], components : [ { name : 'header-wrap', label : COMPONENT_LABEL['header-wrap'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' } ] },
			footer : plain( 'footer', 'footer-wrap', [ offers[0] ] ),
			brand : plain( 'brand', 'brand-wrap', [] ),
			settings : plain( 'settings', 'settings-wrap', [] ),
		},
		brand : {}, palette : {}, notes : [],
	};
}

/** A group that has the standard part and one component, as a register has it: the footer, and two groups that are called as sections of the panel are */
function plain( name, component, offers ) {
	return { label : GROUP_LABEL[name], from : 'standard', theme : '', version : '', origin : 'standard', package : '', problem : '', missing : false, offers : offers, knobs : [], components : [ { name : component, label : COMPONENT_LABEL[component] ?? { de_DE : component, en_US : component }, variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' } ] };
}

function listAnswer( active, extra, pages ) {

	return Object.assign( {
		themes : [
			{ key : 'nino', kind : 'theme', version : '1.0.0', name : { de_DE : 'Nino', en_US : 'Nino' }, description : { de_DE : 'Ruhig und klar.', en_US : 'Calm and clear.' }, brand : {}, parts : { section : { ok : true, problem : '' } }, active : active === 'nino' },
			{ key : 'craft', kind : 'theme', version : '1.0.0', name : { de_DE : 'Craft', en_US : 'Craft' }, description : { de_DE : 'Warm.', en_US : 'Warm.' }, brand : {}, parts : { section : { ok : true, problem : '' } }, active : active === 'craft' },
			{ key : 'rest', kind : 'theme', version : '1.0.0', name : { de_DE : '<b>Ruhe</b>', en_US : '<b>Rest</b>' }, description : { de_DE : 'Bricht.', en_US : 'Breaks.' }, brand : {}, parts : { button : { ok : false, problem : 'uses !important' }, section : { ok : true, problem : '' } }, active : false },
			{ key : 'own:lindner', kind : 'own', name : 'Lindner', from : { page : 'nino@1.0.0', section : 'craft@1.0.0', header : 'standard' }, active : active === 'own:lindner' },
		],
		rejected : [],
		groups : [ { key : 'brand', label : GROUP_LABEL.brand, feature : 'basis', components : [] }, { key : 'footer', label : GROUP_LABEL.footer, feature : 'basis', components : [] }, { key : 'section', label : GROUP_LABEL.section, feature : 'basis', components : [] }, { key : 'settings', label : GROUP_LABEL.settings, feature : 'basis', components : [] }, { key : 'header', label : GROUP_LABEL.header, feature : 'basis', components : [] } ],
		fonts : [], pages : ( pages ?? [ { path : '/', title : 'Start' }, { path : '/contact', title : '' } ] ).map( function( page ) { return typeof page === 'string' ? { path : page, title : '' } : page } ),
		active : active === '' ? null : { theme : active, built : '', stale : false }, foreign : false, canRestore : false,
	}, extra ?? {} );
}

function mixOf( key ) {

	if( key === 'own:lindner' )
		return { name : 'Lindner', css : '', parts : { page : { from : 'nino@1.0.0' }, section : { from : 'craft@1.0.0', values : { 'section-wrap.space' : 2, 'section-title.size' : 0 } }, header : { from : 'standard' } } };

	return { parts : { page : { from : 'nino@1.0.0' }, section : { from : 'nino@1.0.0' }, header : { from : 'standard' } } };
}

/**
 *	The script in a page of its own: the workbench's ui, a router and a registry of dirty forms that keep what they are asked, the
 *	requests held until a test answers them, the timers held until a test lets them run
 *
 *	@param		{Object}		[options]			{ lang, active, modal }
 *
 *	@return		{Object}
 */
function world( options ) {

	options = Object.assign( { lang : 'en-US', active : '', modal : true, room : 600, pages : undefined }, options ?? {} );

	const page = element('div');
	const app = element('div');
	const list = element('div');
	const theme = element('div');
	const wrap = element('div');
	const requests = [];
	const timers = [];
	const dialogs = [];
	const asked = [];
	const dirtyAnswers = [];
	const state = { hash : '', pushed : [], confirm : true, saved : [], discarded : 0 };

	page.id = 'page';
	app.id = 'themes-app';
	list.id = 'themes-list';
	theme.id = 'themes-theme';
	theme.className = 'admin-hidden';
	wrap.id = 'admin-page-wrap';
	app.appendChild( list );
	app.appendChild( theme );
	page.appendChild( app );
	page.appendChild( wrap );

	const doc = documentOf( page );

	doc.documentElement = { lang : options.lang };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) {
		const created = element( tag );
		// A box as wide and as high as the room the test gives it
		created.getBoundingClientRect = function() { return { width : options.room, height : 600 } };
		if( tag === 'dialog' ) {
			created.open = false;
			created.showModal = function() { created.open = true };
			created.close = function() { created.open = false; fire( created, 'close' ) };
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
		href : function( panel, parts ) { return '#'+ [ panel ].concat( parts ?? [] ).map( encodeURIComponent ).join('/') },
		isActive : function() { return true },
		go : function( panel, parts ) { const hash = router.href( panel, parts ); if( hash !== state.hash ) { state.hash = hash; state.pushed.push( hash ) } },
		set : function( panel, parts ) { state.hash = router.href( panel, parts ) },
		leave : function( names, leaving, proceed, resync ) {
			if( leaving === true && dirty.isDirty( names ) === true )
				dirty.guard( names, proceed, resync );
			else
				proceed();
		},
	};

	const entries = {};
	const dirty = {
		entries : entries,
		register : function( name, entry ) { entries[name] = entry },
		isDirty : function( names ) { return ( names ?? Object.keys( entries ) ).some( function( name ) { return entries[name] !== undefined && entries[name].isDirty() === true } ) },
		refresh : function() {},
		guard : function( names, proceed, onCancel ) {
			if( dirty.isDirty( names ) === false ) {
				proceed();
				return;
			}
			const answer = dirtyAnswers.shift() ?? 'cancel';
			dirty.asked = ( dirty.asked ?? 0 ) + 1;
			if( answer === 'cancel' ) {
				if( typeof onCancel === 'function' )
					onCancel();
				return;
			}
			if( answer === 'discard' ) {
				names.forEach( function( name ) { entries[name].discard(); state.discarded++ } );
				proceed();
				return;
			}
			entries[names[0]].save( function( ok ) { state.saved.push( ok ); if( ok === true ) proceed(); else if( typeof onCancel === 'function' ) onCancel() } );
		},
	};

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return words.en[ '[['+ key+ ']]' ] === undefined && key.indexOf('/_admin/themes/') === 0 ? ( options.lang === 'de-DE' ? words.de[key] : words.en[key] ) ?? '' : '' } },
		http : { sendRequest : function( uri, method, callback, data ) {
			requests.push( { uri : uri, action : data.action, payload : JSON.parse( data.data ), callback : callback } );
		} },
		admin : { router : router, dirty : dirty },
	};

	// A word of the panel in the interface language of the page, one of the workbench's own, else nothing
	Nino.content.getText = function( key ) {
		const own = options.lang === 'de-DE' ? words.de[key] : words.en[key];
		return own ?? workbench[key] ?? '';
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent };

	sandbox.window = {
		Nino : Nino,
		location : { hash : '' },
		confirm : function( question ) { asked.push( question ); return state.confirm },
		setTimeout : function( fn, ms ) { timers.push( { fn : fn, ms : ms } ); return timers.length },
		clearTimeout : function( id ) { if( id > 0 && timers[id - 1] !== undefined ) timers[id - 1].fn = null },
	};

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	load( relative, context );

	const w = {
		Nino : Nino, context : context, ui : Nino.adminUi, doc : doc, app : app, list : list, theme : theme, requests : requests, timers : timers, dialogs : dialogs, asked : asked, state : state, router : router, dirty : dirty, dirtyAnswers : dirtyAnswers,
		panel : Nino.admin.themes,

		/** What was answered, in the order of the answers */
		actions : [],

		/** Answer the oldest request that waits, and give back what it was */
		answer : function( status, body ) {
			const request = requests.shift();
			w.actions.push( request.action );
			w.sent.push( request );
			request.callback( { status : status, responseJSON : body } );
			return request;
		},

		/** Answer every request in the way the server would: the API of this stand-in */
		serve : function( serving ) {

			serving = Object.assign( { active : '', fail : null, saveFail : null, listExtra : null }, serving ?? {} );

			let served = 0;

			while( requests.length > 0 ) {

				const request = requests[0];
				const payload = request.payload;

				served++;

				if( serving.fail !== null && serving.fail( request ) !== null ) {
					w.answer( serving.fail( request ).status, serving.fail( request ).body );
					continue;
				}

				if( request.action === 'themes/list' )
					w.answer( 200, listAnswer( w.active, serving.listExtra, options.pages ) );
				else if( request.action === 'themes/open' )
					w.answer( 200, Object.assign( { theme : { key : payload.theme, kind : payload.theme.indexOf('own:') === 0 ? 'own' : 'theme', name : payload.theme.indexOf('own:') === 0 ? 'Lindner' : { de_DE : { rest : 'Ruhe', craft : 'Craft' }[payload.theme] ?? 'Nino', en_US : { rest : 'Rest', craft : 'Craft' }[payload.theme] ?? 'Nino' } }, mix : mixOf( payload.theme ), stamp : payload.theme.indexOf('own:') === 0 ? w.stamp : '', active : w.activeState() }, describe( mixOf( payload.theme ) ) ) );
				else if( request.action === 'themes/preview' )
					w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900 }, describe( payload.mix, w.rest ) ) );
				else if( request.action === 'themes/save' && payload.base !== w.stamp )
					w.answer( 409, { error : 'the theme was changed since it was opened', code : 'themes_changed', params : [ 'Lindner' ], field : 'theme' } );
				else if( request.action === 'themes/save' || request.action === 'themes/save-as' ) {
					w.stamp = 'stamp-'+ ( Number( w.stamp.slice( 6 ) ) + 1 );
					w.answer( 200, { saved : true, theme : request.action === 'themes/save' ? payload.theme : 'own:'+ payload.mix.name.toLowerCase(), applied : false, stamp : w.stamp, active : w.activeState() } );
				}
				else if( request.action === 'themes/apply' ) {
					w.active = payload.theme;
					w.answer( 200, { saved : true, theme : payload.theme, applied : true, active : w.activeState() } );
				}
				else
					w.answer( 404, {} );
			}

			return served;
		},

		active : options.active,
		stamp : 'stamp-1',
		rest : false,
		sent : [],
		activeState : function() { return w.active === '' ? null : { theme : w.active, built : '', stale : false } },

		/** The timers that wait: run them all */
		tick : function() {
			const due = timers.splice( 0 );
			due.forEach( function( timer ) { if( timer.fn !== null ) timer.fn() } );
		},

		/** The panel on an address, everything answered: the list, the theme, its draft */
		at : function( hash, serving ) {
			state.hash = hash;
			w.panel.showCurrent();
			w.serve( serving );
		},

		byClass : function( root, cls ) { return findAll( root, function( node ) { return String( node.className ).split(/\s+/).indexOf( cls ) !== -1 } ) },
		byTag : function( root, tag ) { return findAll( root, function( node ) { return node.tagName === tag.toUpperCase() } ) },
		text : function( node ) { return node.textContent + node.children.map( w.text ).join('') },
		button : function( root, label ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' && w.text( node ).trim().indexOf( label ) === 0 } )[0] },
		hint : function( labelledby ) { const label = findAll( w.theme, function( node ) { return node.id === labelledby } )[0]; return label === undefined ? null : w.text( w.byClass( label, 'themes-value' )[0] ) },
		step : function( labelledby, index ) { const group = findAll( w.theme, function( node ) { return node.attributes['aria-labelledby'] === labelledby } )[0]; return group === undefined ? undefined : group.children[index] },
		frame : function() { return w.byTag( w.theme, 'iframe' )[0] },
		bar : function() { return w.byClass( w.theme, 'themes-bar' )[0] },
		state_ : function() { return w.text( w.byClass( w.theme, 'themes-state' )[0] ).trim() },
	};

	return w;
}

// --- B. the list ------------------------------------------------------------------------------------------------------

{
	const w = world();

	check( 'B. nothing is asked until the panel is shown, and the dirty registry knows the panel by then', w.requests.length === 0 && w.dirty.entries.themes === undefined );

	w.state.hash = '#themes';
	w.panel.showCurrent();

	check( 'B. showing the panel asks themes/list through the endpoint of the workbench - and nothing else', w.requests.length === 1 && w.requests[0].action === 'themes/list' && w.requests[0].uri === '/site/_admin/' );
	check( 'B. ...and registers the panel in the dirty registry under its uri', typeof w.dirty.entries.themes === 'object' && typeof w.dirty.entries.themes.isDirty === 'function' && typeof w.dirty.entries.themes.save === 'function' && typeof w.dirty.entries.themes.discard === 'function' );

	w.serve( { active : 'nino' } );
	w.active = 'nino';
	w.panel.reload();
	w.serve();

	const cards = w.byTag( w.list, 'article' );
	const names = cards.map( function( card ) { return w.text( w.byTag( card, 'h3' )[0] ) } );

	check( 'B. a card for each theme: the ones of a package by name, then the own ones', names.join() === '<b>Rest</b>,Craft,Nino,Lindner', names.join() );
	check( 'B. a name is text and never markup: a theme called <b>Rest</b> draws no element of its own', w.byTag( w.list, 'b' ).length === 0 && names.indexOf('<b>Rest</b>') !== -1 );

	const card = function( key ) { return cards.find( function( one ) { return one.dataset.theme === key } ) };

	check( 'B. the active theme says so, and has no button to apply it', w.text( card('nino') ).indexOf( words.en['/_admin/themes/chip/active'] ) !== -1 && w.byClass( card('nino'), 'nino-admin-btn--small' ).filter( function( b ) { return b.dataset.apply !== undefined } ).length === 0 );
	check( 'B. the others have a button to apply them and one to edit', [ 'craft', 'rest', 'own:lindner' ].every( function( key ) { return findAll( card( key ), function( b ) { return b.dataset.apply === key } ).length === 1 && findAll( card( key ), function( b ) { return b.dataset.edit === key } ).length === 1 } ) );
	check( 'B. a theme with a part that rests says how many, and why', w.text( card('rest') ).indexOf( 'uses !important' ) !== -1 && w.text( card('rest') ).indexOf( 'Resting parts: 1' ) !== -1 && w.text( card('craft') ).indexOf('Resting') === -1 );
	check( 'B. an own theme says what it is made of, by the theme each group comes from', w.text( card('own:lindner') ).indexOf( 'Section from Craft' ) !== -1 && w.text( card('own:lindner') ).indexOf( 'Header from Standard' ) !== -1, w.text( card('own:lindner') ) );

	w.dirtyAnswers.length = 0;
	check( 'B. the apply button asks before the website shows another theme: the answer no changes nothing', ( function() {
		w.state.confirm = false;
		fire( findAll( card('craft'), function( b ) { return b.dataset.apply === 'craft' } )[0], 'click' );
		return w.requests.length === 0 && w.dialogs.length === 1 && w.dialogs[0].open === true;
	} )() );
}

// --- the German list ---------------------------------------------------------------------------------------------------

{
	const w = world( { lang : 'de-DE', active : 'nino' } );

	w.at( '#themes' );

	const cards = w.byTag( w.list, 'article' );

	const craft = cards.find( function( card ) { return card.dataset.theme === 'craft' } );

	check( 'B. in German the words are the German ones, and so is the name that has a German one', w.text( craft ).indexOf( words.de['/_admin/themes/label/edit'] ) !== -1 && w.text( w.byTag( cards.find( function( card ) { return card.dataset.theme === 'rest' } ), 'h3' )[0] ) === '<b>Ruhe</b>' );
}

// --- C. one theme --------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.at( '#themes' );
	w.state.hash = '#themes';
	const edit = findAll( w.list, function( b ) { return b.dataset.edit === 'nino' } )[0];

	fire( edit, 'click' );

	check( 'C. Edit writes the address of the theme for the person\'s own move', w.state.hash === '#themes/nino' && w.state.pushed.indexOf('#themes/nino') !== -1 );
	check( 'C. the theme is opened first, and the draft is asked for before anything is drawn', w.requests.length === 1 && w.requests[0].action === 'themes/open' && w.requests[0].payload.theme === 'nino' && w.theme.children.length === 0 );

	w.answer( 200, Object.assign( { theme : { key : 'nino', kind : 'theme', name : { de_DE : 'Nino', en_US : 'Nino' } }, mix : mixOf('nino'), active : null }, describe( mixOf('nino') ) ) );

	check( 'C. ...asked for with the mix the theme is and no token yet, and the screen waits for it', w.requests.length === 1 && w.requests[0].action === 'themes/preview' && w.requests[0].payload.token === '' && w.requests[0].payload.mix.parts.section.from === 'nino@1.0.0' && w.theme.children.length === 0 );

	w.serve();

	check( 'C. the screen is drawn: the way back, the name, the split view and the bar', w.byClass( w.theme, 'nino-admin-back-link' ).length === 1 && w.byClass( w.theme, 'themes-name' ).length === 1 && w.byClass( w.theme, 'nino-admin-split' ).length === 1 && w.bar() !== undefined );
	check( 'C. the level of the theme is shown and the list is hidden', w.theme.className.indexOf('admin-hidden') === -1 && w.list.className.indexOf('admin-hidden') !== -1 );
	check( 'C. the focus is on the name of the theme', w.byClass( w.theme, 'themes-name' )[0].focused === true );
	check( 'C. the frame shows the first page of the preview with the token of the draft, and no component marked', w.frame().attributes.src === '/site/?nino-theme-preview='+ 'a'.repeat( 32 ), w.frame().attributes.src );

	const rows = w.byClass( w.theme, 'themes-groups' )[0].children.map( function( item ) { return item.children[0] } );

	check( 'C. the groups as a page is read: the page, the header, the others by name in the language of the interface, the footer last - and none that is called as a section of the panel is', rows.map( function( row ) { return row.dataset.group } ).join() === 'page,header,section,footer', rows.map( function( row ) { return row.dataset.group } ).join() );
	check( 'C. each row is a link with the address of its group, and says where its part comes from', rows.map( function( row ) { return row.href } ).join() === '#themes/nino/page,#themes/nino/header,#themes/nino/section,#themes/nino/footer' && w.byClass( rows[0], 'themes-source' )[0].textContent === 'Nino' && w.byClass( rows[1], 'themes-source' )[0].textContent === 'Standard' );
	check( 'C. the groups are one accordion section that is open, and it is the only one', w.theme.querySelectorAll('[aria-expanded]').length === 1 && w.theme.querySelectorAll('[aria-expanded]')[0].attributes['aria-expanded'] === 'true' );
	check( 'C. the switch of the split view says Groups on this level', w.byClass( w.theme, 'nino-admin-split-switch' )[0].children[0].textContent === 'Groups' );
	check( 'C. the state is Saved, the website shows another theme: nothing to reset or save, the theme can be applied', w.state_() === '✓ Saved' && w.bar().querySelectorAll('button').every( function( b ) { return b.textContent === 'Apply to the website' ? b.disabled === false && b.hidden === false : b.disabled === true } ) );
}

// --- D. the settings of a group -------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const heads = w.byClass( w.theme, 'nino-admin-accordion-button' );

	check( 'D. the sections are the components of the group in the order their knobs come in, those with none after them, and a component that has the word of its group is told by its name too', heads.map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } ).join() === 'Wrap,Title,Section (section-col)', heads.map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } ).join() );
	check( 'D. exactly one is open, the one the address names, and the address is that of the component', heads.filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length === 1 && heads[0].attributes['aria-expanded'] === 'true' && w.state.hash === '#themes/nino/section/section-wrap', w.state.hash );
	check( 'D. a head says what its section holds when it is closed: the variant and the knobs, or that the component follows the brand and the page', w.byClass( heads[0], 'nino-admin-accordion-summary' )[0].textContent === 'Regular · 1 knob' && w.byClass( heads[1], 'nino-admin-accordion-summary' )[0].textContent === '2 knobs'
		&& w.byClass( heads[2], 'nino-admin-accordion-summary' )[0].textContent === words.en['/_admin/themes/msg/follows'] );
	check( 'D. the heads of the sections are headings one level below the name of the group, which is one below the name of the panel', w.byClass( w.theme, 'nino-admin-accordion-head' ).length === 3 && w.byClass( w.theme, 'nino-admin-accordion-head' ).every( function( head ) { return head.tagName === 'H4' } ) && w.byTag( w.theme, 'h3' ).length === 2 );
	check( 'D. the component of the open section is outlined in the preview', w.frame().attributes.src.indexOf('&mark=section-wrap') !== -1 && w.text( w.byClass( w.theme, 'themes-marked' )[0] ) === 'outlined: Wrap', w.frame().attributes.src );

	const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	check( 'D. "Part Section from": the standard part and the themes that have a part for the group, the one that rests named and not to be chosen', select.children.map( function( option ) { return option.value+ ( option.disabled === true ? '!' : '' ) } ).join() === 'standard,nino@1.0.0,craft@1.0.0,rest@1.0.0!', select.children.map( function( option ) { return option.value+ ( option.disabled === true ? '!' : '' ) } ).join() );
	check( 'D. ...the one that is in force is chosen', select.value === 'nino@1.0.0' );

	const variant = w.byClass( w.theme, 'nino-admin-steps' ).filter( function( strip ) { return strip.attributes['aria-labelledby'] === 'themes-section-section-wrap-variant' } )[0];

	check( 'D. the standard variant is a choice of steps, one for each variant there is, named in the words of the part', variant !== undefined && variant.children.map( function( button ) { return w.text( button ) } ).join() === 'Regular,Band' && variant.children[0].attributes['aria-checked'] === 'true' );

	const space = w.byClass( w.theme, 'nino-admin-steps' ).filter( function( strip ) { return strip.attributes['aria-labelledby'] === 'themes-section-section-wrap-space' } )[0];

	check( 'D. a knob is a row of five steps from -2 to +2, the middle one marked as the default and chosen', space.children.length === 5 && space.children.map( function( button ) { return w.text( button ).replace( /\s*\(.*\)/, '' ) } ).join() === '−2,−1,0,+1,+2' && space.children[2].attributes['aria-checked'] === 'true' && space.children[2].dataset.standard === 'true' );
	check( 'D. the label of a knob says what the ladder gives at the step when that is a plain length', w.hint( 'themes-section-section-wrap-space' ) === '1rem', w.hint( 'themes-section-section-wrap-space' ) );

	fire( space.children[0], 'click' );
	check( 'D. ...and nothing where the step is a calculation', w.hint( 'themes-section-section-wrap-space' ) === '', w.hint( 'themes-section-section-wrap-space' ) );
	fire( space.children[2], 'click' );
	w.tick();
	w.serve();

	// the page: no components, its knobs in sections of their own
	w.state.hash = '#themes/nino/page';
	w.panel.showCurrent();
	const pageHeads = w.byClass( w.theme, 'nino-admin-accordion-button' ).map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } );

	check( 'D. the page: its knobs in the sections Type and Motion - the knobs it has - and no mark in the preview', pageHeads.join() === 'Type,Motion' && w.frame().attributes.src.indexOf('mark=') === -1 && w.text( w.byClass( w.theme, 'themes-marked' )[0] ) === 'whole page', pageHeads.join() );
	check( 'D. the label of a knob of the page says what its ladder gives where that is one token with a plain value', w.hint( 'themes-page-size' ) === '100%', w.hint( 'themes-page-size' ) );

	// back to the groups, and the focus goes to the row that was left
	w.state.hash = '#themes/nino/section';
	w.panel.showCurrent();
	check( 'D. the way from a group to the groups is no back link of a form - the shell asks Save, Discard or Cancel before one of those is followed, and this move keeps the draft - while the way to the list is one', w.byClass( w.theme, 'nino-admin-back-link' ).length === 1 && w.byClass( w.theme, 'nino-admin-back-link' )[0].id === 'themes-back-list' && w.byClass( w.theme, 'themes-back' ).length === 1 );

	fire( w.byClass( w.theme, 'themes-back' )[0], 'click' );


	check( 'D. the way back leads to the groups, writes the address of the theme and puts the focus on the row of the group that was left', w.state.hash === '#themes/nino' && w.byClass( w.theme, 'themes-groups' ).length === 1 && w.byClass( w.theme, 'themes-groups' )[0].children[2].children[0].focused === true, w.state.hash );
	fire( w.byClass( w.theme, 'themes-groups' )[0].children[2].children[0], 'click' );
	check( 'D. a group opened by hand puts the focus on the way back of its settings', w.state.hash === '#themes/nino/section/section-wrap' && w.byClass( w.theme, 'themes-back' )[0].focused === true, w.state.hash );
}

// --- E. a change --------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const wrapBefore = w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent;
	const spaceStep = w.step( 'themes-section-section-wrap-space', 4 );

	fire( spaceStep, 'click' );

	check( 'E. a step chosen is a change of the working mix, said at once: the state, the head of the section, the buttons of the bar', w.state_() === '● Changed' && w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent !== wrapBefore
		&& w.byClass( w.theme, 'nino-admin-accordion-badge' )[0].textContent === words.en['/_admin/themes/state/changed-mark'] && w.button( w.bar(), 'Reset' ).disabled === false && w.button( w.bar(), 'Save as own theme' ).disabled === false );
	check( 'E. the draft is not asked for while the hand moves: a timer is waiting, and no request', w.requests.length === 0 && w.timers.filter( function( timer ) { return timer.fn !== null && timer.ms === 150 } ).length === 1 );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	check( 'E. a second step takes the first timer away, and there is still one of the hand', w.timers.filter( function( timer ) { return timer.fn !== null && timer.ms === 150 } ).length === 1 );

	w.tick();

	check( 'E. when the hand has rested the draft is asked for once, with the mix as it stands and the token it has', w.requests.length === 1 && w.requests[0].action === 'themes/preview' && w.requests[0].payload.token === 'a'.repeat( 32 ) && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 1 );

	// a change while a request is out is asked for in the next, once
	fire( w.step( 'themes-section-section-wrap-space', 0 ), 'click' );
	w.tick();
	fire( w.step( 'themes-section-section-wrap-space', 1 ), 'click' );
	w.tick();
	check( 'E. what is changed while a request is out waits for it: one request at a time', w.requests.length === 1 );

	const before = w.frame().attributes.src;
	w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900 }, describe( w.requests[0].payload.mix ) ) );

	check( 'E. ...the answer asks for the newest state and does not load the frame in between', w.requests.length === 1 && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === -1 && w.frame().attributes.src === before );

	w.serve();

	check( 'E. the answer to the last request loads the frame once more, with the token and the mark', w.frame().attributes.src === '/site/?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap', w.frame().attributes.src );

	// the way back to the middle is no change
	fire( w.step( 'themes-section-section-wrap-space', 2 ), 'click' );

	check( 'E. a step back to where the saved state has it makes no change: the state is Saved, the head says nothing, the draft is dropped from the bar', w.state_() === '✓ Saved and active' && w.byClass( w.theme, 'nino-admin-accordion-badge' )[0].hidden === true && w.button( w.bar(), 'Reset' ).disabled === true );
	w.tick();
	w.serve();

	// a variant
	fire( w.step( 'themes-section-section-wrap-variant', 1 ), 'click' );
	w.tick();
	check( 'E. the standard variant chosen is in the mix for the component and in the draft', w.requests[0].payload.mix.parts.section.standard['section-wrap'] === 'band' && w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent === 'Band · 1 knob changed' || w.requests[0].payload.mix.parts.section.standard['section-wrap'] === 'band', JSON.stringify( w.requests[0].payload.mix.parts.section ) );
	w.serve();
	fire( w.step( 'themes-section-section-wrap-variant', 0 ), 'click' );
	check( 'E. ...and the saved variant chosen again leaves nothing of it in the mix', w.state_() === '✓ Saved and active' );
	w.tick();
	w.serve();

	// a draft that cannot be made
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.answer( 500, { error : 'the preview could not be written' } );
	const line = w.byClass( w.theme, 'nino-admin-status' )[0];

	check( 'E. a draft that cannot be made is said in the bar, with what the server said, and the change stays: the step is chosen, the state is Changed', line.dataset.state === 'error' && line.textContent.indexOf('the preview could not be written') !== -1 && w.state_() === '● Changed'
		&& w.step( 'themes-section-section-wrap-space', 3 ).attributes['aria-checked'] === 'true' );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	check( 'E. the next change asks again', w.requests.length === 1 && w.requests[0].action === 'themes/preview' );
	w.serve();
}

// the frame is given the page without a step in the history of the window
{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const replaced = [];

	w.frame().contentWindow = { location : { replace : function( url ) { replaced.push( url ) } } };

	const source = w.frame().attributes.src;

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	check( 'E. a page that is loaded again replaces what the frame shows and does not set its address: no step is added to the history, which Back would have to walk over before it came to the address of the panel', replaced.length === 1 && replaced[0] === '/site/?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && w.frame().attributes.src === source, replaced.join() );
}

// the tools over the preview
{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const pages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

	check( 'E. the pages of the site the preview may show are a choice, the first one is shown', pages !== undefined && pages.children.map( function( option ) { return option.value } ).join() === '/,/contact' && pages.value === '/' );

	pages.value = '/contact';
	fire( pages, 'change' );

	check( 'E. another page loads the frame with the token and the mark, on the directory of the site', w.frame().attributes.src === '/site/contact?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && w.requests.length === 0, w.frame().attributes.src );

	const widths = w.byClass( w.theme, 'themes-widths' )[0].children;

	check( 'E. the widths are three buttons, the desktop pressed; the page is laid out at 1280 pixels and scaled to the room there is', widths.map( function( button ) { return w.text( button ).trim()+ ':'+ button.attributes['aria-checked'] } ).join() === 'Mobile:false,Tablet:false,Desktop:true' && w.frame().style.width === '1280px' && w.frame().style.transform.indexOf('scale(0.46') === 0, w.frame().style.width+ ' '+ w.frame().style.transform );

	fire( widths[0], 'click' );

	check( 'E. ...Mobile is the pressed button and the page laid out at 390 pixels, and a width asks the server for nothing', widths[0].attributes['aria-checked'] === 'true' && widths[2].attributes['aria-checked'] === 'false' && w.frame().style.width === '390px' && w.requests.length === 0 );

	fire( widths[1], 'click' );

	check( 'E. ...and Tablet 768', w.frame().style.width === '768px' );
}

// a preview that is as narrow as a phone's shows the page as a phone does, until a width is chosen
{
	const w = world( { active : 'nino', room : 300 } );

	w.at( '#themes/nino/section/section-wrap' );

	const widths = w.byClass( w.theme, 'themes-widths' )[0].children;
	const switchBar = w.byClass( w.theme, 'nino-admin-split-switch' )[0];

	check( 'E. the page is laid out at the desktop width where nothing says otherwise', w.frame().style.width === '1280px' );

	fire( switchBar.children[1], 'click' );

	check( 'E. the first look at a preview of 300 pixels takes the width of a phone: Mobile is pressed and the page is 390 pixels wide', widths[0].attributes['aria-checked'] === 'true' && w.frame().style.width === '390px', w.frame().style.width );

	fire( widths[2], 'click' );
	fire( switchBar.children[0], 'click' );
	fire( switchBar.children[1], 'click' );

	check( 'E. ...and a width that was chosen is not taken away by the next look', widths[2].attributes['aria-checked'] === 'true' && w.frame().style.width === '1280px' );
}

// a page of another site is not a page of the preview
{
	const w = world( { active : 'nino', pages : [ '//evil.example/x', '/\\evil.example', '/contact' ] } );

	w.at( '#themes/nino/section/section-wrap' );

	check( 'E. a page that is not a path of this site - one that begins with two slashes or a slash and a backslash - is not what the frame is given', w.frame().attributes.src.indexOf('//evil') === -1 && w.frame().attributes.src.indexOf('evil.example') === -1 && w.frame().attributes.src.indexOf('/site/?') === 0, w.frame().attributes.src );
}

// a token that has run out
{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	// The clock of the page, which is the context's own
	vm.runInContext( '( function() { const real = Date.now; globalThis.shift = 0; Date.now = function() { return real() + globalThis.shift } } )()', w.context );
	vm.runInContext( 'globalThis.shift = 901 * 1000', w.context );

	fire( w.byClass( w.theme, 'nino-admin-accordion-button' )[1], 'click' );

	check( 'E. a token that has run out is not used: the open of another section asks for the draft again first, and the bar says why', w.requests.length === 1 && w.requests[0].action === 'themes/preview' && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === words.en['/_admin/themes/msg/preview-renewed'] );

	w.serve();
	vm.runInContext( 'globalThis.shift = 0', w.context );

	check( 'E. ...and with the new token the frame loads at once, once', w.frame().attributes.src.indexOf('&mark=section-title') !== -1 );
}

// --- F. another part ---------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	select.value = 'craft@1.0.0';
	w.doc.activeElement = select;
	select.focus();
	fire( select, 'change' );

	check( 'F. another part is asked for at once, with the step the knob of the same name is on, and the other groups as they were', w.requests.length === 1 && w.requests[0].payload.mix.parts.section.from === 'craft@1.0.0' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 2 && w.requests[0].payload.mix.parts.header.from === 'standard' && w.timers.filter( function( timer ) { return timer.fn !== null && timer.ms === 150 } ).length === 0 );

	w.serve();

	const labels = w.byClass( w.theme, 'nino-admin-field-label' ).map( function( label ) { return w.text( label ) } );

	check( 'F. the screen of the group is drawn from the answer: +2 is still on, and is 3.5rem in this part', w.step( 'themes-section-section-wrap-space', 4 ).attributes['aria-checked'] === 'true' && w.hint( 'themes-section-section-wrap-space' ) === '3.5rem', labels.join() );
	check( 'F. the part brings its own variant and the standard variant of the part stands', w.step( 'themes-section-section-wrap-variant', 1 ) !== undefined && w.text( w.step( 'themes-section-section-wrap-variant', 1 ) ) === 'Soft surface' && w.step( 'themes-section-section-wrap-variant', 1 ).attributes['aria-checked'] === 'true' );

	const heads = w.byClass( w.theme, 'nino-admin-accordion-button' ).map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } );

	check( 'F. the knob of the other part is there and the knob of this one is not: Shadow for the column, no Rule for the title', heads.indexOf('Column') === 0 + heads.indexOf('Column') && w.byClass( w.theme, 'nino-admin-accordion-summary' ).map( function( s ) { return s.textContent } ).join('|').indexOf('1 knob') !== -1 && labels.indexOf('Rule') === -1 );
	check( 'F. the part of the group is changed, the bar says Changed, and the head of the open section says that this section is not to be compared with the saved one', w.state_() === '● Changed' );
	check( 'F. the focus is where it was: on the select, which is drawn again', ( function() {
		const drawn = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];
		return drawn !== select && drawn.focused === true;
	} )() );

	// a part that cannot be drawn
	const again = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	again.value = 'standard';
	fire( again, 'change' );
	w.answer( 500, { error : 'boom' } );

	const back = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	check( 'F. a part that cannot be drawn is not taken: the select says the part that was there, the bar says why', back.value === 'craft@1.0.0' && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf('boom') !== -1 );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();

	check( 'F. ...and the mix has it too: the next draft asks for the part that was there, not for the one that could not be drawn', w.requests.length === 1 && w.requests[0].payload.mix.parts.section.from === 'craft@1.0.0', w.requests.length === 1 ? w.requests[0].payload.mix.parts.section.from : 'no request' );
}

// a knob the other part has not: its step goes with the part, and is not there on the day the part comes back
{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-title' );
	fire( w.step( 'themes-section-section-title-x-rule', 3 ), 'click' );
	w.tick();
	w.serve();

	const choose = function( value ) {
		const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];
		select.value = value;
		fire( select, 'change' );
	};

	choose( 'craft@1.0.0' );

	check( 'F. the part is asked for with the steps the mix has, the one of the knob the new part has not among them: the register drops it', w.requests[0].payload.mix.parts.section.values['section-title.x-rule'] === 1 );

	w.serve();
	choose( 'nino@1.0.0' );

	check( 'F. ...and the mix does not keep it: with the answer it is gone, and where the old part comes back the knob stands in the middle', w.requests[0].payload.mix.parts.section.values === undefined || w.requests[0].payload.mix.parts.section.values['section-title.x-rule'] === undefined );

	w.serve();

	check( 'F. ...the screen of the old part says so', w.step( 'themes-section-section-title-x-rule', 2 ).attributes['aria-checked'] === 'true' );
}

// a group whose part rests
{
	const w = world();

	w.rest = true;
	w.at( '#themes/nino/section/section-wrap' );

	check( 'F. a group whose part rests says why, in the words of the rule, and that the standard part stands', w.byClass( w.theme, 'nino-admin-notice' ).length === 1 && w.text( w.byClass( w.theme, 'nino-admin-notice' )[0] ).indexOf( 'the part is for basis ^9.0' ) !== -1 );

	w.state.hash = '#themes/nino';
	w.panel.showCurrent();

	check( 'F. ...and so does its row in the list of the groups', w.text( w.byClass( w.theme, 'themes-groups' )[0].children[2] ).indexOf('basis ^9.0') !== -1 );
}

// --- G. the bar -----------------------------------------------------------------------------------------------------------

{
	// a theme of a package: saved under a name
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );

	check( 'G. a theme of a package that is not the active one: the bar offers to apply it', w.button( w.bar(), 'Apply to the website' ).hidden === false && w.state_() === '✓ Saved' );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	check( 'G. changed, it cannot be applied until it is saved, and the button to save says it makes an own theme', w.button( w.bar(), 'Apply to the website' ).disabled === true && w.button( w.bar(), 'Save as own theme' ) !== undefined );

	fire( w.button( w.bar(), 'Save as own theme' ), 'click' );

	const dialog = w.dialogs[w.dialogs.length - 1] ?? element('dialog');
	const input = findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0] ?? element('input');

	check( 'G. the name is asked in a dialog with a field, and nothing is sent yet', dialog.open === true && input.value === 'Craft 2' && w.requests.length === 0, input.value );

	input.value = 'Lindner';
	fire( findAll( dialog, function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );

	check( 'G. the name goes with the mix to save-as - the name in the mix, the steps, the part - and the theme of the package is not touched', w.requests.length === 1 && w.requests[0].action === 'themes/save-as' && w.requests[0].payload.mix.name === 'Lindner' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 2 && w.requests[0].payload.force === false );

	w.serve();

	check( 'G. what is saved is opened again: the address is the own theme, the state is Saved and the website still shows the theme it showed', w.state.hash === '#themes/own.lindner/section/section-wrap' && w.state_() === '✓ Saved' && w.active === 'nino' );
	check( 'G. ...and the list was asked again before the theme was opened, to know the new theme', w.actions.slice( w.actions.indexOf('themes/save-as') ).join() === 'themes/save-as,themes/list,themes/open,themes/preview', w.actions.join() );
}

{
	// a name that is taken is asked again
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save as own theme' ), 'click' );
	findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	w.answer( 409, { error : 'an own theme of that name is there', code : 'themes_name_taken', params : [ 'Lindner' ], field : 'name' } );

	const second = w.dialogs[w.dialogs.length - 1];

	check( 'G. a name that is taken is asked again, in a dialog that says what was wrong and holds what was typed', w.dialogs.length === 2 && second.open === true && findAll( second, function( node ) { return node.tagName === 'INPUT' } )[0].value === 'Lindner' && w.text( second ).indexOf('Lindner') !== -1 );

	fire( findAll( second, function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Cancel' } )[0], 'click' );

	check( 'G. a name that is not given saves nothing, and the draft stays', w.requests.length === 0 && w.state_() === '● Changed' );
}

{
	// an own theme: written as it stands, asked about where it is the website's
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );

	check( 'G. an own theme that is the website\'s: the state is Saved and active, there is nothing to apply, the button says Save', w.state_() === '✓ Saved and active' && w.button( w.bar(), 'Apply to the website' ).hidden === true && w.button( w.bar(), 'Save' ) !== undefined );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'G. saving what the website shows asks first, because visitors see it at once - and No saves nothing', w.dialogs.length === 1 && w.dialogs[0].open === true && w.requests.length === 0 );

	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Cancel' } )[0], 'click' );

	check( 'G. ...the draft stays', w.requests.length === 0 && w.state_() === '● Changed' );

	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[1], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );

	check( 'G. Yes writes it: save with the key of the theme, its name in the mix and no force', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.mix.name === 'Lindner' && w.requests[0].payload.force === false );

	// the stylesheet of the project is somebody else's
	w.answer( 409, { error : 'the stylesheet of the project was not written by this feature', code : 'themes_foreign' } );

	check( 'G. a stylesheet that is not the feature\'s is asked about before it is replaced, and nothing is sent until the answer is Yes', w.dialogs.length === 3 && w.dialogs[2].open === true && w.requests.length === 0 );

	fire( findAll( w.dialogs[2], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Replace' } )[0], 'click' );

	check( 'G. ...Replace asks again with force', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.force === true );

	w.serve();

	check( 'G. what was saved is what the screen is: the state is Saved and active and the line says it was saved', w.state_() === '✓ Saved and active' && w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'saved' );
}

{
	// apply, and reset
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.button( w.bar(), 'Apply to the website' ), 'click' );

	check( 'G. Apply asks before the website shows the theme', w.dialogs.length === 1 && w.requests.length === 0 );

	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Apply to the website' } )[0], 'click' );

	check( 'G. ...Yes sends apply with the key of the theme and no force', w.requests.length === 1 && w.requests[0].action === 'themes/apply' && w.requests[0].payload.theme === 'craft' && w.requests[0].payload.force === false );

	w.serve();

	check( 'G. ...and the state is Saved and active, the button is gone, the line says what the website shows', w.state_() === '✓ Saved and active' && w.button( w.bar(), 'Apply to the website' ).hidden === true && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf('The website shows the theme "Craft"') !== -1, w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) );

	fire( w.step( 'themes-section-section-wrap-space', 1 ), 'click' );
	w.tick();
	w.serve();

	// a list that cannot be had after a change does not hold the screen up
	const y = world( { active : 'nino' } );

	y.at( '#themes/craft/section/section-wrap' );
	fire( y.button( y.bar(), 'Apply to the website' ), 'click' );
	fire( findAll( y.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Apply to the website' } )[0], 'click' );
	y.serve( { fail : function( request ) { return request.action === 'themes/list' ? { status : 500, body : {} } : null } } );

	check( 'G. a list that cannot be had after the website was given a theme does not hold the screen up: the state is Saved and active, and the line says what the website shows', y.state_() === '\u2713 Saved and active' && y.text( y.byClass( y.theme, 'nino-admin-status' )[0] ).indexOf('The website shows the theme "Craft"') !== -1 );

	fire( w.button( w.bar(), 'Reset' ), 'click' );

	check( 'G. Reset drops the draft: the working mix is the saved one, the state is Saved and active, and the preview is asked for again at once', w.state_() === '✓ Saved and active' && w.requests.length === 1 && w.requests[0].action === 'themes/preview' && w.requests[0].payload.mix.parts.section.values === undefined && w.step( 'themes-section-section-wrap-space', 2 ).attributes['aria-checked'] === 'true' );
	w.serve();
}

// --- H. leaving ---------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	check( 'H. a theme that is not changed holds nothing the registry has to ask about', w.dirty.entries.themes.isDirty() === false );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	check( 'H. a change is unsaved input of the panel, for the registry', w.dirty.entries.themes.isDirty() === true && w.dirty.entries.themes.bar() === null );

	// the way back to the list asks
	w.dirtyAnswers.push( 'cancel' );
	fire( w.byClass( w.theme, 'nino-admin-back-link' )[0], 'click' );

	check( 'H. the way back to the list asks first; Cancel leaves the screen and the address as they are', w.dirty.asked === 1 && w.state.hash === '#themes/nino/section/section-wrap' && w.theme.className.indexOf('admin-hidden') === -1 );

	// a move between the levels of the theme does not ask: the draft goes with it
	fire( w.byClass( w.theme, 'themes-back' )[0], 'click' );

	check( 'H. a move from the settings of a group to the groups is no leaving: nothing is asked and the draft stays', w.dirty.asked === 1 && w.state.hash === '#themes/nino' && w.dirty.entries.themes.isDirty() === true );
	w.serve();

	// the address changed by hand: the shell calls showCurrent
	w.dirtyAnswers.push( 'cancel' );
	w.state.hash = '#themes';
	w.panel.showCurrent();

	check( 'H. an address that leaves asks too; Cancel puts the address of the level that is shown back', w.dirty.asked === 2 && w.state.hash === '#themes/nino' && w.theme.className.indexOf('admin-hidden') === -1, w.state.hash );

	w.dirtyAnswers.push( 'discard' );
	w.state.hash = '#themes';
	w.panel.showCurrent();
	w.serve();

	check( 'H. Discard forgets the draft and the list is shown', w.state.discarded === 1 && w.list.className.indexOf('admin-hidden') === -1 && w.theme.className.indexOf('admin-hidden') !== -1 && w.dirty.entries.themes.isDirty() === false );

	// a panel that is shown for another panel's address keeps its level
	w.state.hash = '#themes/craft';
	w.panel.showCurrent();
	w.serve();
	w.state.hash = '#dashboard';
	w.panel.showCurrent();

	check( 'H. an address that names another panel leaves the screen of this one as it is', w.requests.length === 0 && w.theme.className.indexOf('admin-hidden') === -1 );

	// a theme that cannot be opened
	const x = world();

	x.state.hash = '#themes/nino';
	x.panel.showCurrent();
	x.serve();
	x.state.hash = '#themes/craft';
	x.panel.showCurrent();
	x.answer( 500, { error : 'down' } );

	check( 'H. a theme that cannot be opened says so in its level, with the way back, and nothing is drawn of it', w.byClass( x.theme, 'nino-admin-error' ).length === 1 && w.byClass( x.theme, 'nino-admin-back-link' ).length === 1 && x.dirty.entries.themes.isDirty() === false );
}

// --- I. what a person does and where the focus stands afterwards ---------------------------------------------------------

{
	const hasFocus = function( w, node ) { return node !== undefined && node.focused === true };
	const name = function( w ) { return w.byClass( w.theme, 'themes-name' )[0] };

	// Save as an own theme: the view was drawn again, its name has the focus
	const a = world( { active : 'nino' } );

	a.at( '#themes/craft/section/section-wrap' );
	fire( a.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	a.tick();
	a.serve();
	fire( a.button( a.bar(), 'Save as own theme' ), 'click' );
	findAll( a.dialogs[a.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Fresh';
	fire( findAll( a.dialogs[a.dialogs.length - 1], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	a.serve();

	check( 'I. after a save as an own theme the focus is on the name of the theme - the button that was pressed is gone with the screen it stood in', hasFocus( a, name( a ) ) && name( a ).attributes.tabindex === '-1' );

	// Apply in the bar
	const b = world( { active : 'nino' } );

	b.at( '#themes/craft/section/section-wrap' );
	fire( b.button( b.bar(), 'Apply to the website' ), 'click' );
	fire( findAll( b.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Apply to the website' } )[0], 'click' );
	b.serve();

	check( 'I. after Apply in the bar the focus is on the name of the theme: the button is hidden now, and a focus does not stay on what is not there', hasFocus( b, name( b ) ) );

	// Reset
	const c = world( { active : 'nino' } );

	c.at( '#themes/craft/section/section-wrap' );
	fire( c.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	c.tick();
	c.serve();
	fire( c.button( c.bar(), 'Reset' ), 'click' );

	check( 'I. after Reset the focus is on the name of the theme: the button is without a use until the next change', hasFocus( c, name( c ) ) );
	c.serve();

	// Apply on a card of the list: the Edit of the card that was applied
	const d = world( { active : 'nino' } );

	d.at( '#themes' );
	fire( findAll( d.list, function( node ) { return node.dataset.apply === 'craft' } )[0], 'click' );
	fire( findAll( d.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Apply to the website' } )[0], 'click' );
	d.serve();

	const edit = findAll( d.list, function( node ) { return node.dataset.edit === 'craft' } )[0];

	check( 'I. after Apply on a card of the list the focus is on Edit of that card, as it is drawn now - the card of the theme that is on the website has no Apply to stand on', hasFocus( d, edit ) && edit.isConnected === true && findAll( d.list, function( node ) { return node.dataset.apply === 'craft' } ).length === 0 );

	// what was pressed keeps the focus where it can be pressed again
	const e = world( { active : 'nino' } );

	e.at( '#themes/craft/section/section-wrap' );
	const chosen = e.step( 'themes-section-section-wrap-space', 4 );

	chosen.focus();
	fire( chosen, 'click' );
	e.tick();
	e.serve();
	fire( e.button( e.bar(), 'Reset' ), 'click' );
	e.serve();
	check( 'I. ...and after a failed Apply the focus is where it was pressed: the button is there and can be pressed again', ( function() {
		const apply = e.button( e.bar(), 'Apply to the website' );
		apply.focus();
		fire( apply, 'click' );
		fire( findAll( e.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Cancel' } )[0], 'click' );
		return apply.focused === true;
	} )() );
}

// --- J. two tabs --------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );

	check( 'J. an own theme is opened by its address, own.<key>, and the API is asked for own:<key>', w.sent.filter( function( request ) { return request.action === 'themes/open' } )[0].payload.theme === 'own:lindner' && w.state.hash === '#themes/own.lindner/section/section-wrap' && w.byClass( w.theme, 'themes-back' )[0].href === '#themes/own.lindner' );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );

	check( 'J. save says which state of the theme it was made on: the stamp open gave', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.base === 'stamp-1' );

	w.serve();
}

{
	// Cancel in the question leaves everything as it is
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	w.stamp = 'stamp-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];
	const answers = findAll( question, function( node ) { return node.tagName === 'BUTTON' } ).map( function( node ) { return node.textContent } );

	check( 'J. a theme written by another tab since it was opened is said in a question with three answers - Overwrite, Cancel and Reload - with the sentence of the server, and the bar says it too', question.open === true && answers.join() === 'Overwrite,Cancel,Reload' && w.text( question ).indexOf('The theme "Lindner" was changed since you opened it.') !== -1, w.text( question ) );

	fire( findAll( question, function( node ) { return node.textContent === 'Cancel' } )[0], 'click' );

	check( 'J. Cancel sends nothing and leaves the draft: the state is Changed, the step is chosen', w.requests.length === 0 && w.state_() === '● Changed' && w.step( 'themes-section-section-wrap-space', 3 ).attributes['aria-checked'] === 'true' );

	// Overwrite: the stamp of what is there now, then the same save with it
	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	w.serve();
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.textContent === 'Overwrite' } )[0], 'click' );

	check( 'J. Overwrite asks for the theme as it is now, only to have its stamp, and draws nothing of it', w.requests.length === 1 && w.requests[0].action === 'themes/open' && w.requests[0].payload.theme === 'own:lindner' );

	w.answer( 200, Object.assign( { theme : { key : 'own:lindner', kind : 'own', name : 'Lindner' }, mix : mixOf( 'own:lindner' ), stamp : 'stamp-5', active : null }, describe( mixOf( 'own:lindner' ) ) ) );

	check( 'J. ...and then the save is sent once more with that stamp, and the draft that is saved is the person\'s', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.base === 'stamp-5' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 1 );

	w.serve();

	check( 'J. what was written over is the state: Saved and active, and the stamp of the next save is the one the server gave', w.state_() === '✓ Saved and active' && w.dirty.entries.themes.isDirty() === false );
}

{
	// Reload: the draft is dropped after a question
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	w.stamp = 'stamp-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	w.serve();
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.textContent === 'Reload' } )[0], 'click' );

	const sure = w.dialogs[w.dialogs.length - 1];

	check( 'J. Reload asks first, because the draft is lost with it', sure.open === true && w.requests.length === 0 && w.text( sure ).indexOf('lost') !== -1 );

	fire( findAll( sure, function( node ) { return node.textContent === 'Reload' } )[0], 'click' );
	w.serve();

	check( 'J. ...and then the theme is opened as it is saved: the draft is gone, the state is Saved and active, and the focus is on the name', w.dirty.entries.themes.isDirty() === false && w.state_() === '✓ Saved and active' && w.step( 'themes-section-section-wrap-space', 4 ).attributes['aria-checked'] === 'true' && w.byClass( w.theme, 'themes-name' )[0].focused === true );
}

// --- K. what is round the preview -----------------------------------------------------------------------------------------

{
	// a link in the preview
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const replaced = [];
	const location = { href : 'http://site/site/contact', pathname : '/site/contact', search : '', protocol : 'http:', replace : function( url ) { replaced.push( url ) } };

	w.frame().contentWindow = { location : location };
	fire( w.frame(), 'load' );

	const pages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

	check( 'K. a link that is followed in the preview does not drop the draft: the page it came to is the page that is chosen, where the list has it, and the frame is loaded with the token again', replaced.length === 1 && replaced[0] === '/site/contact?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && pages.value === '/contact', replaced.join() );
	check( 'K. the pages the list has are named by their title with the path behind it, and by the path where they have no title', pages.children.map( function( option ) { return option.textContent } ).join('|') === 'Start (/)|/contact' );

	// the page loaded was the draft's: nothing is done
	location.search = '?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap';
	location.href = 'http://site/site/contact?nino-theme-preview='+ 'a'.repeat( 32 );
	fire( w.frame(), 'load' );

	check( 'K. a load that has the token is the preview and is left as it is', replaced.length === 1 );

	// a page the list has not: the page that is chosen
	location.search = '';
	location.pathname = '/site/elsewhere';
	location.href = 'http://site/site/elsewhere';
	fire( w.frame(), 'load' );

	check( 'K. a page the list does not have is not taken for the chosen one: the page that was chosen is loaded again with the token, and the bar says that the preview cannot show it', replaced.length === 2 && replaced[1] === '/site/contact?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && pages.value === '/contact', replaced.join() );

	// a page that sends the frame away again and again is loaded once
	fire( w.frame(), 'load' );

	check( 'K. a page that comes back without the token twice in a row is not loaded a third time: a page that sends the frame elsewhere cannot keep it busy', replaced.length === 2 );
}

{
	// the frame is loaded once, after the last answer
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const replaced = [];

	w.frame().contentWindow = { location : { replace : function( url ) { replaced.push( url ) } } };
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900 }, describe( w.requests[0].payload.mix ) ) );

	check( 'K. while a newer draft is asked for the frame is not loaded with the answer to the older one', replaced.length === 0 && w.requests.length === 1 );

	w.serve();

	check( 'K. ...and it is loaded once when the last answer is there', replaced.length === 1 );
}

{
	// what a calculation comes to
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	let width = 56;
	const probes = [];
	const document = {
		body : element('body'),
		createElement : function( tag ) {
			const probe = element( tag );
			probe.getBoundingClientRect = function() { return { width : width, height : 10 } };
			probe.remove = function() { probe.removed = true };
			probes.push( probe );
			return probe;
		},
	};

	w.frame().contentDocument = document;

	check( 'K. a value that is a calculation is measured only where the frame has loaded: while it is busy nothing is read from it', w.hint( 'themes-section-section-wrap-space' ) === '1rem' && probes.length === 0 );

	fire( w.frame(), 'load' );
	fire( w.step( 'themes-section-section-wrap-space', 0 ), 'click' );

	check( 'K. ...a box is given the value at the step and measured in the page of the frame, and what it is is written beside the name, in pixels; the box is taken away again', w.hint( 'themes-section-section-wrap-space' ) === '56px' && probes.length >= 1 && probes[probes.length - 1].style.width === 'calc( 1rem * .5 )' && probes[probes.length - 1].removed === true, w.hint( 'themes-section-section-wrap-space' ) );

	width = 60;
	fire( w.frame(), 'load' );

	check( 'K. ...and when the frame has loaded the draft - the tokens may have moved - the values beside the knobs are read again', w.hint( 'themes-section-section-wrap-space' ) === '60px' );

	width = 0;
	fire( w.frame(), 'load' );

	check( 'K. a box that has no width says nothing: no value is better than a wrong one', w.hint( 'themes-section-section-wrap-space' ) === '' );
}

// --- L. what is said, and when -------------------------------------------------------------------------------------------

{
	// the knobs and variants the other part has not
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-title' );
	fire( w.step( 'themes-section-section-title-x-rule', 3 ), 'click' );
	fire( w.byClass( w.theme, 'nino-admin-accordion-button' )[0], 'click' );
	fire( w.step( 'themes-section-section-wrap-variant', 1 ), 'click' );
	w.tick();
	w.serve();

	const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	select.value = 'craft@1.0.0';
	fire( select, 'change' );
	w.serve();

	const said = w.text( w.byClass( w.theme, 'nino-admin-status' )[0] );

	check( 'L. what the person had set and the new part does not have is said in the bar, by name: the knob that has a step and the variant that is chosen', said === 'These do not apply in this part: Rule, standard variant "Band" of Wrap.', said );

	// a part that drops nothing says nothing
	select.value = 'nino@1.0.0';
	fire( select, 'change' );
	w.serve();

	check( 'L. a part that drops nothing says nothing of it', w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === '' );
}

{
	// the bar says unsaved once, and a refusal goes with the cause
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );

	const status = w.byClass( w.theme, 'nino-admin-status' )[0];
	const announce = function() { return w.timers.filter( function( timer ) { return timer.fn !== null && timer.ms === 4000 } ).length };

	check( 'L. a change is said once, by the line of the status, at the moment it begins: the chip says the state and the line says that something changed', w.state_() === '● Changed' && status.attributes.role === 'status' && w.text( status ) === 'Changes not saved yet.' && announce() === 1, w.text( status ) );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );

	check( 'L. ...and not said again while the draft goes on: the line stays as it is and no second sentence is made', w.text( status ) === 'Changes not saved yet.' && announce() === 1 );

	w.tick();

	check( 'L. ...the sentence goes after a while, so that the chip is not said twice to the eye', w.text( status ) === '' );

	w.answer( 500, { error : 'boom' } );

	check( 'L. a draft that cannot be made is a line of its own', w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'error' );

	fire( w.step( 'themes-section-section-wrap-space', 1 ), 'click' );
	w.tick();
	w.serve();

	check( 'L. ...and goes when a draft was made: the state is right again', w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state !== 'error' && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === '' );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.answer( 500, { error : 'boom' } );
	fire( w.step( 'themes-section-section-wrap-space', 2 ), 'click' );

	check( 'L. a refusal goes too where the draft is the saved state again, though no answer has come', w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state !== 'error' );
	w.tick();
	w.serve();

	check( 'L. the standard variant says where it applies', w.text( w.byClass( w.theme, 'nino-admin-field-label' )[0] ).indexOf( words.en['/_admin/themes/hint/variant'] ) !== -1 );
}

{
	// the name of a theme
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	select.value = 'craft@1.0.0';
	fire( select, 'change' );
	w.serve();
	fire( w.button( w.bar(), 'Save as own theme' ), 'click' );

	const first = w.dialogs[w.dialogs.length - 1];
	const typed = function( dialog, value ) {
		const input = findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0];
		input.value = value;
		return input;
	};

	check( 'L. the dialog says what is saved - the theme it comes from and what it is made of - in one sentence', w.text( first ).indexOf('The draft of "Nino" is saved as an own theme under a name: Page from Nino, Section from Craft, otherwise Standard.') !== -1, w.text( first ).slice( 0, 260 ) );
	check( 'L. the dialog has its own class, so that the stylesheet that holds its text to 12 pixels reaches it', w.byClass( first, 'themes-dialog' ).length === 1 );

	typed( first, '   ' );
	fire( findAll( first, function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );

	const second = w.dialogs[w.dialogs.length - 1];
	const field = findAll( second, function( node ) { return node.tagName === 'INPUT' } )[0];

	check( 'L. a name that says nothing sends nothing: the dialog is there again, with the sentence at the field, which is marked', w.requests.length === 0 && second !== first && second.open === true && field.attributes['aria-invalid'] === 'true' && w.text( second ).indexOf('needs a name') !== -1 );

	typed( second, 'Fresh' );
	fire( field, 'keydown', { key : 'Enter' } );

	check( 'L. Enter in the field saves: the name is sent', w.requests.length === 1 && w.requests[0].action === 'themes/save-as' && w.requests[0].payload.mix.name === 'Fresh' );
	w.serve();
}

{
	// the list says what the website shows
	const none = world( { active : '' } );

	none.at( '#themes' );

	const on = world( { active : 'nino' } );

	on.at( '#themes' );

	check( 'L. a list with no theme on the website says that the site shows the standard parts, and not that a theme is on', none.text( none.list ).indexOf( words.en['/_admin/themes/hint/list-none'] ) !== -1 && none.text( none.list ).indexOf( words.en['/_admin/themes/hint/list'] ) === -1 && on.text( on.list ).indexOf( words.en['/_admin/themes/hint/list'] ) !== -1 );
}

{
	// a link to a page of another site: the frame cannot be read
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const replaced = [];
	const location = { search : '', replace : function( url ) { replaced.push( url ) } };

	[ 'protocol', 'href', 'pathname' ].forEach( function( name ) { Object.defineProperty( location, name, { get : function() { throw new Error( 'Blocked a frame with another origin' ) } } ) } );

	const probes = [];

	w.frame().contentWindow = { location : location };
	w.frame().contentDocument = { body : element('body'), createElement : function( tag ) { const probe = element( tag ); probe.getBoundingClientRect = function() { return { width : 56, height : 1 } }; probe.remove = function() {}; probes.push( probe ); return probe } };

	let thrown = null;

	try {
		fire( w.frame(), 'load' );
	} catch( e ) {
		thrown = e;
	}

	check( 'K. a link to a page of another site, which the frame cannot read, throws nothing: the page that was chosen is loaded with the token, the bar says that the preview cannot show it, and the frame is busy until it has loaded', thrown === null && replaced.length === 1 && replaced[0].indexOf('/site/?nino-theme-preview=') === 0 && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === 'The preview cannot show this page.', String( thrown ) );

	fire( w.frame(), 'load' );

	check( 'K. a page that cannot be read twice in a row is not loaded over again, and the values beside the knobs are read all the same', replaced.length === 1 && w.hint( 'themes-section-section-wrap-space' ) === '56px' );
}

{
	// units: what is measured is in pixels, rem included; a share stays as it is written
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-title' );

	check( 'K. while the frame has not loaded the value stands as it is written', w.hint( 'themes-section-section-title-size' ) === '1.2rem' );

	w.frame().contentDocument = { body : element('body'), createElement : function( tag ) { const probe = element( tag ); probe.getBoundingClientRect = function() { return { width : 19.2, height : 1 } }; probe.remove = function() {}; return probe } };
	fire( w.frame(), 'load' );

	check( 'K. once it has loaded the value of a fixed length is the measured one, in pixels', w.hint( 'themes-section-section-title-size' ) === '19.2px', w.hint( 'themes-section-section-title-size' ) );

	w.state.hash = '#themes/nino/page';
	w.panel.showCurrent();
	fire( w.frame(), 'load' );

	check( 'K. ...and a share stays as it is written, which no box can measure', w.hint( 'themes-page-size' ) === '100%' );
}

{
	// Overwrite is the answer to the stamp, and the stylesheet that is not the feature's is asked about after it
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	w.stamp = 'stamp-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === 'Save' } )[0], 'click' );
	w.serve();
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.textContent === 'Overwrite' } )[0], 'click' );
	w.answer( 200, Object.assign( { theme : { key : 'own:lindner', kind : 'own', name : 'Lindner' }, mix : mixOf( 'own:lindner' ), stamp : 'stamp-5', active : null }, describe( mixOf( 'own:lindner' ) ) ) );

	check( 'J. Overwrite writes with the stamp that stands now and without force: it answers the stamp and says nothing about a stylesheet that somebody else wrote', w.requests.length === 1 && w.requests[0].payload.base === 'stamp-5' && w.requests[0].payload.force === false );

	w.answer( 409, { error : 'the stylesheet of the project was not written by this feature', code : 'themes_foreign' } );

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'J. ...and where the stylesheet is somebody else\'s the question about it comes, as at any save, and nothing is sent before the answer', question.open === true && w.requests.length === 0 && findAll( question, function( node ) { return node.textContent === 'Replace' } ).length === 1 );

	fire( findAll( question, function( node ) { return node.textContent === 'Replace' } )[0], 'click' );

	check( 'J. ...Replace sends it with force, and with the stamp of the Overwrite', w.requests.length === 1 && w.requests[0].payload.force === true && w.requests[0].payload.base === 'stamp-5' );
}

{
	// the browser's own steps between the levels put the focus where a step of the person's own does
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino' );
	w.state.hash = '#themes/nino/section';
	w.panel.showCurrent();

	check( 'K. a step of the history into the settings of a group puts the focus on the way back, as the click on the row does', w.byClass( w.theme, 'themes-back' )[0].focused === true );

	w.state.hash = '#themes/nino';
	w.panel.showCurrent();

	check( 'K. ...and a step out of them puts it on the row of the group that was left', w.byClass( w.theme, 'themes-groups' )[0].children[2].children[0].focused === true );

	w.state.hash = '#themes/nino/section/section-title';
	w.panel.showCurrent();
	w.byClass( w.theme, 'themes-back' )[0].focused = false;
	w.state.hash = '#themes/nino/section/section-wrap';
	w.panel.showCurrent();

	check( 'K. a step between two sections of one group moves the focus nowhere', w.byClass( w.theme, 'themes-back' )[0].focused === false );
}

{
	// the first address of a page is no step of the history: a page that is loaded on the settings of a group gives nothing the focus
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section' );

	check( 'K. the address a page is loaded on puts the focus nowhere - only a step between two levels does', w.byClass( w.theme, 'themes-back' )[0].focused !== true );
}

{
	// the words of the dialog: what the website shows is named
	const none = world( { active : '' } );

	none.at( '#themes/nino/section/section-wrap' );
	fire( none.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	none.tick();
	none.serve();
	fire( none.button( none.bar(), 'Save as own theme' ), 'click' );

	const on = world( { active : 'own:lindner' } );

	on.at( '#themes/nino/section/section-wrap' );
	fire( on.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	on.tick();
	on.serve();
	fire( on.button( on.bar(), 'Save as own theme' ), 'click' );

	check( 'L. the dialog says which theme the website goes on showing - by its name, an own one without the form the API gives it - or that it shows the standard parts', none.text( none.dialogs[0] ).indexOf('The website goes on showing the standard parts.') !== -1 && on.text( on.dialogs[0] ).indexOf('The website goes on showing the theme "Lindner".') !== -1 && on.text( on.dialogs[0] ).indexOf('own:') === -1 );

	const strip = on.byClass( on.theme, 'themes-widths' )[0];

	check( 'K. the choice of the width has a word that is on screen, and the strip is named by it', strip.attributes['aria-labelledby'] === 'themes-width-label' && on.text( on.byClass( on.theme, 'themes-caption' )[0] ) === 'Width' && on.byClass( on.theme, 'themes-caption' )[0].id === 'themes-width-label' );
}


// --- M. the names of the sections of the first level --------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const seen = [];
	const real = w.ui.accordion;

	w.ui.accordion = function( options ) { seen.push( options ); return real.call( w.ui, options ) };

	[ 'brand', 'settings', 'groups' ].forEach( function( name ) {
		w.state.hash = '#themes/nino/'+ name;
		w.panel.showCurrent();
		w.serve();

		check( 'M. '+ name+ ' is no group: the address shows the groups of the theme, and the address is that of the theme', w.state.hash === '#themes/nino' && w.byClass( w.theme, 'themes-groups' ).length === 1 );
	} );

	const rows = w.byClass( w.theme, 'themes-groups' )[0].children.map( function( item ) { return item.children[0].dataset.group } );

	check( 'M. a group of the register that is called as one of them - the fixture has brand and settings - is not in the list of the groups, and has no address', rows.join() === 'page,header,section,footer' );
	check( 'M. the accordion of the first level has an address, the theme, so that the sections the second part brings stand in it', seen.length > 0 && seen.every( function( options ) { return options.id === 'themes-groups' && options.address.panel === 'themes' && options.address.parts.join() === 'nino' } ) );
}

// --- N. what is saved is as it is saved --------------------------------------------------------------------------------

{
	// a step that is none is no change, whatever the server wrote
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/section/section-title' );

	check( 'N. a theme whose saved mix says a step of 0 is not changed by that', w.dirty.entries.themes.isDirty() === false );

	fire( w.step( 'themes-section-section-title-size', 4 ), 'click' );
	check( 'N. ...a step is a change', w.dirty.entries.themes.isDirty() === true );

	fire( w.step( 'themes-section-section-title-size', 2 ), 'click' );
	check( 'N. ...and the middle again is none, which the saved mix says with a 0 and the draft by leaving the knob out', w.dirty.entries.themes.isDirty() === false && w.state_() === '✓ Saved and active'.replace( 'and active', '' ).trim() || w.dirty.entries.themes.isDirty() === false );
}

done();
