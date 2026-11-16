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

// The words of the panel and of the API, by their keys: a test of what the panel says asks for the fill and never writes the sentence
const W = function( key ) { return words.en['/_admin/themes/'+ key] };
const E = function( code ) { return words.en['/_admin/error/'+ code] };
const Wp = function( key, ...params ) { return String( key.indexOf('api/') === 0 ? E( key.slice( 4 ) ) : W( key ) ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };
const CANCEL = workbench['/_admin/common/label/cancel'];

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
	[ 'word/type', 'word/measures', 'word/motion', 'word/more', 'word/mobile', 'word/tablet', 'word/desktop', 'state/saved', 'state/applied', 'msg/knob-one', 'msg/knobs', 'variant/regular', 'error/load', 'confirm/brand-title', 'label/take-back', 'label/take-back-standard', 'msg/adjust-darker', 'msg/adjust-lighter', 'msg/palette-ok', 'msg/palette-bad', 'msg/replaces-last', 'msg/unreadable', 'msg/foreign', 'confirm/save-title', 'word/reaches', 'word/falls-short', 'word/colours', 'word/mode', 'word/logo', 'label/color-1', 'label/color-2', 'label/tone', 'label/mode', 'label/font-title', 'label/font-text', 'label/font-button' ]
		.concat( [ 'warm', 'neutral', 'cool' ].map( function( tone ) { return 'word/tone-'+ tone } ), [ 'light', 'dark', 'auto' ].map( function( mode ) { return 'word/mode-'+ mode } ), [ 'sans', 'serif', 'mono' ].map( function( font ) { return 'word/font-system-'+ font } ), [ 'base', 'alt', 'tint', 'ink', 'quiet', 'line', 'accent', 'on', 'picture', 'on-primary' ].map( function( role ) { return 'role/'+ role } ) )
		.forEach( function( key ) { asked.add( key ) } );

	// A key that ends in a hyphen is the beginning of one the script puts together: those are named below
	Array.from( asked ).filter( function( key ) { return key.slice( -1 ) === '-' } ).forEach( function( key ) { asked.delete( key ) } );

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

const GROUP_LABEL = { section : { de_DE : 'Abschnitt', en_US : 'Section' }, header : { de_DE : 'Header', en_US : 'Header' }, footer : { de_DE : 'Footer', en_US : 'Footer' }, brand : { de_DE : 'Marke', en_US : 'Brand' }, settings : { de_DE : 'Einstellungen', en_US : 'Settings' }, css : { de_DE : 'CSS', en_US : 'Css' }, groups : { de_DE : 'Gruppen', en_US : 'Groups' } };
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

const BRAND_KEYS = [ 'color-1', 'color-2', 'tone', 'mode', 'font-title', 'font-text', 'font-button' ];
const BRAND_DEFAULTS = { 'color-1' : '#1c5db8', 'color-2' : '#17233a', tone : 'neutral', mode : 'auto', 'font-title' : 'system-sans', 'font-text' : 'system-sans', 'font-button' : 'system-sans' };
const FONTS = [ { key : 'open-sans', name : 'Open Sans', system : false }, { key : 'system-mono', name : 'system-mono', system : true }, { key : 'system-sans', name : 'system-sans', system : true }, { key : 'system-serif', name : 'system-serif', system : true } ];

/** A brand that names nothing: every value follows the theme */
function brandNone() {

	const brand = {};

	BRAND_KEYS.forEach( function( key ) { brand[key] = { own : false } } );

	return brand;
}

/** What a theme suggests for the brand: Craft a warm tone and a font, Nino a neutral tone, the standard part nothing */
function suggestion( theme ) {
	return theme === 'craft' ? { tone : 'warm', 'font-title' : 'open-sans' } : ( theme === 'nino' ? { tone : 'neutral' } : [] );
}

/** The palette as the API answers it, the pair of the quiet text on the tint under what a text needs */
function paletteOf( source, weak, adjusted ) {

	const light = { base : '#ffffff', alt : '#f4f4f4', tint : '#e8eefc', ink : '#1b1b1b', quiet : '#555555', line : '#cccccc', accent : '#1c5db8', on : '#ffffff' };
	const dark = { base : '#16181d', alt : '#1d2027', tint : '#1d2a4a', ink : '#ececec', quiet : '#a9afbb', line : '#3a3f4b', accent : '#8ab4f8', on : '#101114' };
	const contrast = {};

	[ [ 'light', [ 'base', 'alt', 'tint' ] ], [ 'dark', [ 'base', 'alt', 'tint', 'picture' ] ] ].forEach( function( set ) {
		set[1].forEach( function( ground ) {
			[ 'ink', 'quiet', 'accent' ].forEach( function( role ) { contrast[set[0]+ ' '+ role+ '/'+ ground] = role === 'ink' ? 12.5 : 6.25 } );
		} );
		contrast[set[0]+ ' button'] = set[0] === 'dark' ? 4.5 : 5.5;
	} );

	contrast.primary = 6.34;

	if( weak === true )
		contrast['light quiet/tint'] = 4.2;

	if( adjusted === true ) {
		light.accent = '#7f6904';
		dark.accent = '#ffd400';
	}

	return { source : source, veil : '#404040', colors : { light : light, dark : dark, 'color-1' : adjusted === true ? '#ffd400' : '#1c5db8', 'color-2' : '#17233a', 'on-color-1' : '#ffffff', 'on-color-2' : '#ffffff' }, contrast : contrast };
}

/** What open and preview say of a mix: the group Section, whose part comes from nino or craft, a group Header with the standard part, and the page */
function describe( mix, rest, brand ) {

	brand = brand ?? brandNone();

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
			css : plain( 'css', 'css-wrap', [] ),
			groups : plain( 'groups', 'groups-wrap', [] ),
		},
		brand : {
			stored : brand,
			effective : Object.fromEntries( BRAND_KEYS.map( function( key ) { return [ key, brand[key].own === true ? brand[key].value : ( suggestion( theme === '' ? '' : ( ( part.page ?? {} ).from ?? 'nino' ).split('@')[0] )[key] ?? BRAND_DEFAULTS[key] ) ] } ) ),
			named : [],
			suggested : suggestion( ( ( part.page ?? {} ).from ?? 'standard' ).split('@')[0] ),
			defaults : BRAND_DEFAULTS,
			unavailable : rest === 'gone' ? [ 'font-title' ] : [],
			fonts : FONTS.map( function( font ) { return font.key } ),
		},
		palette : paletteOf( BRAND_KEYS.some( function( key ) { return brand[key].own === true && ( key === 'color-1' || key === 'color-2' ) } ) || rest === 'adjust' ? 'brand' : 'reset', rest === 'weak', rest === 'adjust' ),
		notes : [],
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
		groups : [ { key : 'brand', label : GROUP_LABEL.brand, feature : 'basis', components : [] }, { key : 'footer', label : GROUP_LABEL.footer, feature : 'basis', components : [] }, { key : 'section', label : GROUP_LABEL.section, feature : 'basis', components : [] }, { key : 'settings', label : GROUP_LABEL.settings, feature : 'basis', components : [] }, { key : 'css', label : GROUP_LABEL.css, feature : 'basis', components : [] }, { key : 'groups', label : GROUP_LABEL.groups, feature : 'basis', components : [] }, { key : 'header', label : GROUP_LABEL.header, feature : 'basis', components : [] } ],
		fonts : FONTS, limits : { css : 65536, name : 60 }, state : 'state-1', pages : ( pages ?? [ { path : '/', title : 'Start' }, { path : '/contact', title : '' } ] ).map( function( page ) { return typeof page === 'string' ? { path : page, title : '' } : page } ),
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

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder };

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

				if( request.action === 'themes/list' ) {
					const listed = listAnswer( w.active, serving.listExtra, options.pages );

					listed.themes = listed.themes.filter( function( theme ) { return w.deleted.indexOf( theme.key ) === -1 } );
					listed.themes.forEach( function( theme ) { if( theme.kind === 'own' ) theme.stamp = w.stamp } );
					listed.last = w.last;
					listed.state = w.stateStamp;
					listed.canRestore = w.canRestore;
					w.answer( 200, listed );
				}
				else if( request.action === 'themes/open' )
					w.answer( 200, Object.assign( { theme : { key : payload.theme, kind : payload.theme.indexOf('own:') === 0 ? 'own' : 'theme', name : payload.theme.indexOf('own:') === 0 ? 'Lindner' : { de_DE : { rest : 'Ruhe', craft : 'Craft' }[payload.theme] ?? 'Nino', en_US : { rest : 'Rest', craft : 'Craft' }[payload.theme] ?? 'Nino' } }, mix : mixOf( payload.theme ), stamp : payload.theme.indexOf('own:') === 0 ? w.stamp : '', brandStamp : w.brandStamp, state : w.stateStamp, canRestore : w.canRestore, last : w.last, active : w.activeState() }, describe( mixOf( payload.theme ), w.rest === 'gone' || w.rest === 'adjust' ? w.rest : false, w.brandStored ) ) );
				else if( request.action === 'themes/preview' )
					w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900 }, describe( payload.mix, w.rest, payload.brand ) ) );
				else if( request.action === 'themes/apply' && payload.theme.indexOf('own:') === 0 && payload.base !== w.stamp )
					w.answer( 409, { error : 'the theme was changed since it was opened', code : 'themes_changed', params : [ 'Lindner' ], field : 'theme' } );
				else if( request.action === 'themes/save' && payload.base === w.stamp && payload.mix.name === 'Taken' )
					w.answer( 409, { error : 'an own theme of that name is there', code : 'themes_name_taken', params : [ 'Taken Before' ], field : 'name' } );
				else if( request.action === 'themes/save' && payload.base !== w.stamp )
					w.answer( 409, { error : 'the theme was changed since it was opened', code : 'themes_changed', params : [ 'Lindner' ], field : 'theme' } );
				else if( ( request.action === 'themes/save' || request.action === 'themes/save-as' || request.action === 'themes/brand' ) && payload.brand !== undefined && payload.brandBase !== w.brandStamp )
					w.answer( 409, { error : 'the brand was changed since it was opened', code : 'themes_brand_changed', params : [], field : 'brand' } );
				else if( request.action === 'themes/save' || request.action === 'themes/save-as' || request.action === 'themes/brand' ) {

					if( request.action !== 'themes/brand' )
						w.stamp = 'stamp-'+ ( Number( w.stamp.slice( 6 ) ) + 1 );

					if( payload.brand !== undefined ) {
						w.brandStored = payload.brand;
						w.brandStamp = 'brand-'+ ( Number( w.brandStamp.slice( 6 ) ) + 1 );
					}

					w.bump();
					w.answer( 200, { saved : true, theme : request.action === 'themes/save-as' ? 'own:'+ payload.mix.name.toLowerCase() : payload.theme, applied : false, stamp : w.stamp, brandStamp : w.brandStamp, state : w.stateStamp, canRestore : w.canRestore, active : w.activeState() } );
				}
				else if( request.action === 'themes/restore' && payload.base !== w.stateStamp )
					w.answer( 409, { error : 'the themes were changed since they were opened', code : 'themes_state', params : [], field : '' } );
				else if( request.action === 'themes/restore' ) {
					w.restored++;
					w.bump();
					w.answer( 200, { restored : true, active : w.activeState(), foreign : false, canRestore : true, state : w.stateStamp, brandStamp : w.brandStamp } );
				}
				else if( request.action === 'themes/delete' && payload.base !== w.stamp )
					w.answer( 409, { error : 'the theme was changed since it was opened', code : 'themes_changed', params : [ 'Lindner' ], field : 'theme' } );
				else if( request.action === 'themes/delete' ) {
					w.deleted.push( payload.theme );
					w.bump();
					w.answer( 200, { deleted : true, active : w.activeState(), canRestore : true, state : w.stateStamp } );
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
		brandStamp : 'brand-1',
		stateStamp : 'state-1',
		canRestore : false,
		last : null,
		brandStored : brandNone(),
		deleted : [],
		restored : 0,
		bump : function() { w.stateStamp = 'state-'+ ( Number( w.stateStamp.slice( 6 ) ) + 1 ); w.canRestore = true },
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
		text : function( node ) { return node === undefined || node === null ? '' : node.textContent + node.children.map( w.text ).join('') },
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

	check( 'C. the groups as a page is read: the brand of the project, the page, the header, the others by name in the language of the interface, the footer last - and none that is called as a section of the panel is', rows.map( function( row ) { return row.dataset.group } ).join() === 'brand,page,header,section,footer', rows.map( function( row ) { return row.dataset.group } ).join() );
	check( 'C. each row is a link with the address of its group, and says where its part comes from - the brand, that it is the project\'s', rows.map( function( row ) { return row.href } ).join() === '#themes/nino/brand,#themes/nino/page,#themes/nino/header,#themes/nino/section,#themes/nino/footer' && w.byClass( rows[0], 'themes-source' )[0].textContent === W('word/project') && w.byClass( rows[1], 'themes-source' )[0].textContent === 'Nino' && w.byClass( rows[2], 'themes-source' )[0].textContent === 'Standard' );
	check( 'C. the first level has three sections - the groups, the settings, the own css - and one of them is open: the groups', w.theme.querySelectorAll('[aria-expanded]').length === 3 && w.theme.querySelectorAll('[aria-expanded="true"]').length === 1 && w.theme.querySelectorAll('[aria-expanded]')[0].attributes['aria-expanded'] === 'true' );
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


	check( 'D. the way back leads to the groups, writes the address of the theme and puts the focus on the row of the group that was left', w.state.hash === '#themes/nino' && w.byClass( w.theme, 'themes-groups' ).length === 1 && w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0].focused === true, w.state.hash );
	fire( w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0], 'click' );
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

	check( 'F. ...and so does its row in the list of the groups', w.text( w.byClass( w.theme, 'themes-groups' )[0].children[3] ).indexOf('basis ^9.0') !== -1 );
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

	fire( findAll( second, function( node ) { return node.tagName === 'BUTTON' && node.textContent === CANCEL } )[0], 'click' );

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

	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === CANCEL } )[0], 'click' );

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
		fire( findAll( e.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === CANCEL } )[0], 'click' );
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

	fire( findAll( question, function( node ) { return node.textContent === CANCEL } )[0], 'click' );

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

	check( 'K. ...and a step out of them puts it on the row of the group that was left', w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0].focused === true );

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

	[ [ 'settings', '#themes/nino/settings' ], [ 'css', '#themes/nino/css' ], [ 'groups', '#themes/nino' ] ].forEach( function( named ) {
		w.state.hash = '#themes/nino/'+ named[0];
		w.panel.showCurrent();
		w.serve();

		const open = w.theme.querySelectorAll('[aria-expanded="true"]');

		check( 'M. '+ named[0]+ ' is no group: the address shows the first level of the theme with that section open, and the address is the one that section has', open.length === 1 && open[0].id === 'themes-groups-'+ [ 'groups', 'settings', 'css' ].indexOf( named[0] )+ '-head' && w.state.hash === named[1], w.state.hash );
	} );

	w.state.hash = '#themes/nino/brand';
	w.panel.showCurrent();
	w.serve();

	check( 'M. brand has a level of its own: the brand of the project, no group of the list - four sections, the first open, and the address names it', w.byClass( w.theme, 'themes-groups' ).length === 0 && w.theme.querySelectorAll('[aria-expanded]').length === 4 && w.state.hash === '#themes/nino/brand/colours', w.state.hash );

	w.state.hash = '#themes/nino';
	w.panel.showCurrent();
	w.serve();

	const rows = w.byClass( w.theme, 'themes-groups' )[0].children.map( function( item ) { return item.children[0].dataset.group } );

	check( 'M. a group of the register that is called as one of them - the fixture has brand, settings, css and groups - is not in the list of the groups as a group: the one row that is called brand is the brand of the project', rows.join() === 'brand,page,header,section,footer' );
	check( 'M. the accordion of the first level has an address, the theme, and the section of the brand has one of its own', seen.length > 0 && seen.filter( function( options ) { return options.id === 'themes-groups' } ).every( function( options ) { return options.address.panel === 'themes' && options.address.parts.join() === 'nino' } ) && seen.some( function( options ) { return options.id === 'themes-acc' && options.address.parts.join() === 'nino,brand' } ) );
}

// --- O. the brand ------------------------------------------------------------------------------------------------------------

/** One node of the screen by its id */
function byId( root, id ) {
	return findAll( root, function( node ) { return node.id === id } )[0];
}

/** What is typed into a field: the value, and the event the field listens to */
function type( node, value ) {
	node.value = value;
	fire( node, 'input' );
}

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino' );

	const row = w.byClass( w.theme, 'themes-groups' )[0].children[0].children[0];

	check( 'O. the brand has a row before the page in the groups: a link to its own level, which says that it is the project\'s, and the page theme\'s brand is no group', row.dataset.group === 'brand' && row.href === '#themes/nino/brand' && w.byClass( row, 'themes-source' )[0].textContent === W('word/project') && w.text( row ).indexOf('Brand') !== -1 );

	fire( row, 'click' );

	check( 'O. a click on the row goes to the level of the brand: the address names the first section, the way back is there, the name of the level says that it belongs to the project, and the focus is on the way back', w.state.hash === '#themes/nino/brand/colours' && w.byClass( w.theme, 'themes-back' ).length === 1 && w.text( w.byClass( w.theme, 'themes-title' )[0] ).indexOf('Project') !== -1 && w.byClass( w.theme, 'themes-back' )[0].focused === true, w.state.hash );
	check( 'O. the level has four sections - colours, type, mode, logo - one is open, each head says what is in it: from the theme, where the project has set nothing', w.theme.querySelectorAll('[aria-expanded]').length === 4 && w.theme.querySelectorAll('[aria-expanded="true"]').length === 1 && w.byClass( w.theme, 'nino-admin-accordion-summary' ).map( function( node ) { return node.textContent } ).join() === 'from the theme,from the theme,from the theme,/logo · /logo/dark' );
	check( 'O. the switch of the split view says Settings: it is a level of settings', w.byClass( w.theme, 'nino-admin-split-switch' )[0].children[0].textContent === W('label/settings') );

	const color1 = byId( w.theme, 'themes-brand-color-1' );
	const color2 = byId( w.theme, 'themes-brand-color-2' );

	check( 'O. the colours are the workbench\'s own field, with the code and the contrast of the text that is written on it, and a value that the project has not set is the theme\'s: the default of Nino.css, said so, and no way back to offer', color1.value === '#1c5db8' && color2.value === '#17233a' && w.byClass( w.theme, 'nino-admin-color-contrast' ).length === 2 && w.text( w.byClass( w.theme, 'nino-admin-color-contrast' )[0] ).indexOf('enough') !== -1
		&& byId( w.theme, 'themes-brand-back-color-1' ).hidden === true && w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/from-standard') ) !== -1 && w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf( Wp( 'chip/from-theme', 'Nino' ) ) !== -1, w.text( w.byClass( w.theme, 'themes-origin' )[0] ) );

	// the first colour typed is the project's own
	type( color1, '#9a4b1d' );

	check( 'O. M4: a colour is an own value from the first time it is set - the chip says so, the way back is offered with the value it goes back to, the section is marked changed and says how many own values it holds, and the draft is changed', w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/own-value') ) !== -1 && byId( w.theme, 'themes-brand-back-color-1' ).hidden === false && byId( w.theme, 'themes-brand-back-color-1' ).textContent === Wp( 'label/take-back-standard', '#1c5db8' )
		&& w.text( byId( w.theme, 'themes-acc-0-head' ) ).indexOf( W('msg/own-one') ) !== -1 && w.text( byId( w.theme, 'themes-acc-0-head' ) ).indexOf('changed') !== -1 && w.state_() === '● Changed' && w.dirty.entries.themes.isDirty() === true );

	// a code that is no colour is none: nothing is changed by it
	type( color2, 'red' );

	check( 'O. a code that is no colour says so at the field and changes nothing', w.text( byId( w.theme, 'themes-acc-0-body' ) ).indexOf( workbench['/_admin/common/color/invalid'] ) !== -1 && w.text( byId( w.theme, 'themes-acc-0-head' ) ).indexOf( W('msg/own-one') ) !== -1 );

	check( 'O. the draft asks for the preview when the hand has rested - with the brand, whose first colour is the project\'s own', w.timers.length > 0 && ( w.tick(), w.requests.length === 1 && w.requests[0]?.action === 'themes/preview' && w.requests[0]?.payload?.brand?.['color-1']?.own === true && w.requests[0]?.payload?.brand?.['color-1']?.value === '#9a4b1d' && w.requests[0]?.payload?.brand?.['color-2']?.own === false ) );

	w.serve();

	// the tone and the way back
	const tone = w.step( 'themes-brand-tone', 0 );

	fire( tone, 'click' );

	check( 'O. a tone is chosen with the steps: it is an own value as well, and the chip says it', w.step( 'themes-brand-tone', 0 ).attributes['aria-checked'] === 'true' && w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf( W('chip/own-value') ) !== -1 && byId( w.theme, 'themes-brand-back-tone' ).textContent === Wp( 'label/take-back', W('word/tone-neutral') ) );

	fire( byId( w.theme, 'themes-brand-back-tone' ), 'click' );

	check( 'O. M3: the way back takes the value back: nothing is the project\'s own, the steps show the theme\'s tone, the chip says so, and the focus is on the steps', w.step( 'themes-brand-tone', 1 ).attributes['aria-checked'] === 'true' && byId( w.theme, 'themes-brand-back-tone' ).hidden === true && w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf( Wp( 'chip/from-theme', 'Nino' ) ) !== -1 && w.step( 'themes-brand-tone', 1 ).focused === true );
	w.tick();

	check( 'O. ...and the draft that goes out says it: the tone is not own, the colour still is', w.requests[0]?.payload?.brand?.tone?.own === false && w.requests[0]?.payload?.brand?.['color-1']?.own === true );
	w.serve();

	type( color2, '#17233b' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Reset' ), 'click' );

	check( 'O. Reset drops a brand that was changed with the rest of the draft: the field shows the saved value, nothing is the project\'s own, the state says so', byId( w.theme, 'themes-brand-color-2' ).value === '#17233a' && byId( w.theme, 'themes-brand-back-color-2' ).hidden === true && w.state_() === '✓ Saved and active' && w.dirty.entries.themes.isDirty() === false );
	w.serve();

	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	fire( byId( w.theme, 'themes-brand-back-color-1' ), 'click' );

	check( 'O. M3: the first colour goes back too, the field shows the colour of the theme and the focus stands on it, and with nothing own the draft is the saved state again', byId( w.theme, 'themes-brand-color-1' ).value === '#1c5db8' && w.byClass( w.theme, 'themes-state' )[0].dataset.state !== 'warn' && w.dirty.entries.themes.isDirty() === false && byId( w.theme, 'themes-brand-color-1' ).focused === true );
	w.tick();
	w.serve();
}

{
	// the palette: what the draft says of its colours, and the verdict of each pair
	const w = world( { active : 'nino', pages : [ '/' ] } );

	w.rest = 'weak';
	w.at( '#themes/nino/brand/colours' );

	const pairs = w.byClass( w.theme, 'themes-pair' );
	const weak = pairs.filter( function( pair ) { return pair.dataset.state === 'error' } );

	check( 'O. the palette is every pair a part reads for the light and the dark mode - 24 with the colour on the first - each with its swatch, its name, the number and the word', pairs.length === 24 && w.byClass( w.theme, 'themes-roles' ).length === 2 && w.byClass( w.theme, 'themes-role' ).length === 16 && w.byClass( w.theme, 'themes-palette-mode' ).map( function( node ) { return node.textContent } ).join() === [ 'light', 'dark', 'light', 'dark' ].map( function( mode ) { return W( 'word/mode-'+ mode ) } ).join() && w.byClass( w.theme, 'themes-allpairs' )[0].open === true );
	check( 'O. the number is the one the API gave, cut to two decimals and written as the language writes it, a pair on the very limit reaches, and the pair under 4.5 : 1 says that it falls short in a word and in a mark, which the colour does not say alone', weak.length === 1 && w.text( weak[0] ).indexOf( Wp( 'msg/ratio', 4.2 ) ) !== -1 && w.text( weak[0] ).indexOf( W('word/falls-short') ) !== -1 && w.text( weak[0] ).indexOf('✕') !== -1 && w.text( pairs[0] ).indexOf( Wp( 'msg/ratio', 12.5 ) ) !== -1 && w.text( pairs[0] ).indexOf( W('word/reaches') ) !== -1 && w.text( pairs[0] ).indexOf('✓') !== -1 );
	check( 'O. the name of a pair is the text on its ground, in the words of the panel, and the dark mode has the picture under a section as a ground', w.text( pairs[0] ).indexOf( Wp( 'msg/pair', W('role/ink'), W('role/base') ) ) !== -1 && w.text( w.byClass( w.theme, 'themes-pairs' )[1] ).indexOf( Wp( 'msg/pair', W('role/accent'), W('role/picture') ) ) !== -1 && w.text( w.byClass( w.theme, 'themes-pairs' )[0] ).indexOf( W('role/picture') ) === -1 );
	check( 'O. a swatch shows the colours of its pair - the text on its ground - and is no information for a screen reader', pairs[0].children[0].style.color === '#1b1b1b' && pairs[0].children[0].style.backgroundColor === '#ffffff' && pairs[0].children[0].attributes['aria-hidden'] === 'true'
		&& w.byClass( w.theme, 'themes-pair-swatch' ).filter( function( node ) { return node.style.backgroundColor === '#404040' } ).length === 3 );
	check( 'O. the smallest contrast is told in a line of its own, with the word', w.text( w.byClass( w.theme, 'themes-least' )[0] ) === Wp( 'msg/palette-bad', 1, 4.2 ), w.text( w.byClass( w.theme, 'themes-least' )[0] ) );
	check( 'O. a brand that names no colour has the colours of the reset, and the section says so, where the palette is', w.byClass( w.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent.indexOf( W('msg/palette-reset') ) !== -1 } ).length === 1 );

	// a draft with an own colour is a palette of the brand: no notice
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();

	check( 'O. the palette is made again with every answer of the preview: with a colour of the project\'s own the notice that the reset stands is gone, and the contrast line of the field is told the colour that is written on it', w.byClass( w.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent.indexOf( W('msg/palette-reset') ) !== -1 } ).length === 0 && w.byClass( w.theme, 'themes-pair' ).length === 24 );
}

{
	// where every pair holds the list of them is closed, and one sentence says so
	const w = world( { active : 'nino', pages : [ '/' ] } );

	w.at( '#themes/nino/brand/colours' );

	check( 'O. where every pair holds its contrast the list of them starts closed and one sentence says that they hold, with the smallest number; the eight colours of each mode are seen', w.byClass( w.theme, 'themes-allpairs' )[0].open === false && w.text( w.byClass( w.theme, 'themes-least' )[0] ) === Wp( 'msg/palette-ok', 4.5 ) && w.byClass( w.theme, 'themes-role' ).length === 16 && w.text( w.byTag( w.byClass( w.theme, 'themes-allpairs' )[0], 'summary' )[0] ) === Wp( 'label/all-pairs', 24 ) );
}

{
	// type, mode, logo
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/brand/type' );

	const title = byId( w.theme, 'themes-brand-font-title' );
	const options = w.byTag( title, 'option' ).map( function( node ) { return node.value+ ':'+ node.textContent } );

	check( 'O. the fonts are the ones the list names - the system\'s in the words of the panel, a folder of public/fonts/ by its name - and the value is the one Nino.css has, since the theme suggests none: the system font, as the standard value', options.join() === 'open-sans:Open Sans,system-mono:System monospace,system-sans:System sans-serif,system-serif:System serif' && title.value === 'system-sans' && w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/from-standard') ) !== -1, options.join() );
	check( 'O. each of the three has a name for a screen reader: the field is a label with the select inside', [ 'font-title', 'font-text', 'font-button' ].every( function( key ) { return byId( w.theme, 'themes-brand-'+ key ).parentNode.tagName === 'LABEL' } ) );

	title.value = 'open-sans';
	fire( title, 'change' );

	check( 'O. a font that is chosen is an own value: the chip, the way back with the font it goes back to, and the head', w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/own-value') ) !== -1 && byId( w.theme, 'themes-brand-back-font-title' ).textContent === Wp( 'label/take-back-standard', W('word/font-system-sans') ) && w.text( byId( w.theme, 'themes-acc-1-head' ) ).indexOf( W('msg/own-one') ) !== -1 );

	fire( byId( w.theme, 'themes-brand-back-font-title' ), 'click' );

	check( 'O. ...and back, the select shows the theme\'s font again and the focus is on it', title.value === 'system-sans' && title.focused === true && w.dirty.entries.themes.isDirty() === false );

	fire( byId( w.theme, 'themes-acc-2-head' ), 'click' );

	check( 'O. the mode is three steps - light, dark, automatic - the one that stands is the default of Nino.css, automatic', w.text( w.step( 'themes-brand-mode', 0 ) ) === 'Light' && w.text( w.step( 'themes-brand-mode', 1 ) ) === 'Dark' && w.text( w.step( 'themes-brand-mode', 2 ) ).trim().indexOf('Automatic') === 0 && w.step( 'themes-brand-mode', 2 ).attributes['aria-checked'] === 'true' && w.state.hash === '#themes/nino/brand/mode' );

	fire( w.step( 'themes-brand-mode', 1 ), 'click' );
	w.tick();

	check( 'O. a mode that is chosen goes out with the draft as an own value', w.requests[0]?.payload?.brand?.mode?.own === true && w.requests[0]?.payload?.brand?.mode?.value === 'dark' );
	w.serve();

	fire( byId( w.theme, 'themes-acc-3-head' ), 'click' );

	const logo = byId( w.theme, 'themes-logo-open' );

	check( 'O. the logo is no field of the panel: a link to the images of the workbench, where the two places /logo and /logo/dark are, and a sentence that says that a theme brings no logo', logo.tagName === 'A' && logo.href === '#images/logo' && w.text( byId( w.theme, 'themes-acc-3-body' ) ).indexOf('/logo/dark') !== -1 && w.text( byId( w.theme, 'themes-acc-3-body' ) ).indexOf( W('hint/logo') ) !== -1 && w.byClass( byId( w.theme, 'themes-acc-3-body' ), 'themes-brand-field' ).length === 0 );
}

{
	// the brand of the theme the page comes from: Craft suggests a warm tone and a font, and a project that has set a font keeps it
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );

	check( 'O. M1: the values that the project has not set are those of the theme the page comes from: the tone Craft suggests, and the chip names Craft', w.step( 'themes-brand-tone', 0 ).attributes['aria-checked'] === 'false' && w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf( Wp( 'chip/from-theme', 'Nino' ) ) !== -1 || w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf('From the theme') !== -1 );

	w.brandStored = Object.assign( brandNone(), { 'font-title' : { own : true, value : 'system-serif' } } );
	w.at( '#themes/nino/brand/type' );

	check( 'O. M2: an own font stays what the theme suggests, and the way back says the value it goes back to', byId( w.theme, 'themes-brand-font-title' ).value === 'system-serif' && w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/own-value') ) !== -1 && byId( w.theme, 'themes-brand-back-font-title' ).hidden === false && byId( w.theme, 'themes-brand-font-text' ).value === 'system-sans' );
	check( 'O. ...and what was saved is not a change: the draft is the saved state', w.dirty.entries.themes.isDirty() === false );
}

// --- P. what is saved of the brand, and what is asked first --------------------------------------------------------------------

/** The levels a question was asked at: confirm() of the workbench, taken over to say which of its three kinds each action is */
function levels( w ) {

	const seen = [];
	const real = w.ui.confirm;

	w.ui.confirm = function( level, options ) { seen.push( level ); return real.call( w.ui, level, options ) };

	return seen;
}

/** The button of the dialog that is open that has the label */
function pressed( w, label ) {

	const dialog = w.dialogs[w.dialogs.length - 1];

	fire( findAll( dialog, function( node ) { return node.tagName === 'BUTTON' && node.textContent.trim() === label } )[0], 'click' );
}

{
	// the brand of a theme of a package, alone: its own action, asked first as a thing that reaches the website
	const w = world( { active : 'nino' } );
	const seen = levels( w );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();

	check( 'P. a brand that is changed in a theme of a package is saved as the brand and not as a theme: the button says Save, and not that an own theme is made', w.text( w.button( w.bar(), 'Save' ) ) === 'Save' && w.button( w.bar(), 'Save as own theme' ) === undefined && w.button( w.bar(), 'Save' ).disabled === false );

	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'P. it is asked first, whichever theme is open and whether or not it is the website\'s - the brand holds for the whole website - as a big thing, and the question says that it is the brand', seen.join() === 'big' && w.requests.length === 0 && w.text( w.dialogs[0] ).indexOf( W('confirm/brand') ) !== -1 );

	pressed( w, 'Cancel' );

	check( 'P. No saves nothing and the draft stays', w.requests.length === 0 && w.state_() === '● Changed' );

	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );

	const sent = w.requests[0];

	check( 'P. Yes sends the brand alone - the action brand - with the theme that is open, the brand as the draft has it, the stamp of the brand it was seen in, and no force', sent?.action === 'themes/brand' && sent.payload?.theme === 'craft' && sent.payload?.brand?.['color-1']?.value === '#9a4b1d' && sent.payload?.brandBase === 'brand-1' && sent.payload?.force === false && sent.payload?.mix === undefined );

	w.serve();

	check( 'P. what was saved is what the screen is: the state is Saved, the stamp of the brand is the new one, and the draft is the brand that is stored', w.state_() === '✓ Saved' && w.dirty.entries.themes.isDirty() === false && w.brandStamp === 'brand-2' && w.actions.slice( w.actions.indexOf('themes/brand') ).join() === 'themes/brand,themes/list,themes/open,themes/preview' );

	type( byId( w.theme, 'themes-brand-color-1' ), '#1f6f5b' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );

	check( 'P. the next save is made on the stamp the last gave', w.requests[0]?.payload?.brandBase === 'brand-2' );
	w.serve();
}

{
	// the brand and the mix of a theme of a package: one step, as a theme of its own
	const w = world( { active : 'nino' } );
	const seen = levels( w );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();
	w.state.hash = '#themes/craft/brand/colours';
	w.panel.showCurrent();
	w.serve();
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();

	check( 'P. the mix and the brand changed in a theme of a package: the button makes an own theme, and the row of the brand in the groups says that it is changed', w.text( w.button( w.bar(), 'Save as own theme' ) ) !== '' );

	fire( w.button( w.bar(), 'Save as own theme' ), 'click' );
	pressed( w, 'Save' );

	check( 'P. the brand is asked about first, since it reaches the website - and then the name', seen.join() === 'big' && w.dialogs.length === 2 && w.dialogs[1].open === true );

	const dialog = w.dialogs[w.dialogs.length - 1];

	findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	pressed( w, 'Save' );

	check( 'P. M5: save as sends the mix and the brand side by side - the brand is no part of the mix - and the stamp of the brand', w.requests[0]?.action === 'themes/save-as' && w.requests[0]?.payload?.brand?.['color-1']?.value === '#9a4b1d' && w.requests[0]?.payload?.brandBase === 'brand-1' && w.requests[0]?.payload?.mix?.brand === undefined && JSON.stringify( w.requests[0]?.payload?.mix ).indexOf('color-1') === -1 );
	w.serve();
}

{
	// an own theme that is not the website's: its mix is saved without a question, and the brand with the mix is asked about
	const w = world( { active : 'nino' } );
	const seen = levels( w );

	w.at( '#themes/own.lindner/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'P. the mix of an own theme that is not the website\'s is saved at once, and the brand is not sent where it is not changed', seen.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.brand === undefined && w.requests[0]?.payload?.brandBase === undefined );
	w.serve();

	w.state.hash = '#themes/own.lindner/brand/colours';
	w.panel.showCurrent();
	w.serve();
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );

	check( 'P. an own theme with a changed brand: save, with the mix, the brand and the two stamps - the one of the theme and the one of the brand - and the asking comes first', w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.brand?.['color-1']?.value === '#9a4b1d' && w.requests[0]?.payload?.brandBase === 'brand-1' && w.requests[0]?.payload?.base === w.stamp && w.requests[0]?.payload?.mix?.name === 'Lindner' );
	w.serve();
}

{
	// the brand that another tab saved
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	w.brandStamp = 'brand-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'P. a brand that another tab saved since it was opened is said in the question of the conflict, with the sentence of the server, and Cancel keeps the draft', question.open === true && w.text( question ).indexOf( E('themes_brand_changed') ) !== -1 && ( fire( findAll( question, function( node ) { return node.textContent === CANCEL } )[0], 'click' ), w.state_() === '● Changed' && w.requests.length === 0 ) );

	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.serve();
	pressed( w, 'Overwrite' );

	check( 'P. Overwrite sends the save once more at once, with the stamp the other tab left - the theme was asked for before the question, for what the other tab set - and no force', w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' && w.requests[0]?.payload?.brandBase === 'brand-5' && w.requests[0]?.payload?.force === false );

	w.serve();

	check( 'P. ...and the save is made once more with that stamp, and only that one: the stamp of the theme is the one the person has - what the refusal was not about is not taken over', w.sent.filter( function( request ) { return request.action === 'themes/brand' } ).slice( -1 )[0]?.payload?.brandBase === 'brand-5' && w.sent.filter( function( request ) { return request.action === 'themes/brand' } ).slice( -1 )[0]?.payload?.force === false && w.brandStored['color-1'].value === '#9a4b1d' && w.state_() === '✓ Saved' );
}

{
	// the two stamps of a save are taken over one at a time: what a refusal was about, and nothing else
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	w.stamp = 'stamp-9';
	w.brandStamp = 'brand-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.serve();
	pressed( w, 'Overwrite' );
	w.serve();
	pressed( w, 'Overwrite' );
	w.serve();

	const saves = w.sent.filter( function( request ) { return request.action === 'themes/save' } );

	check( 'P. a theme and a brand that both were saved by another tab are two questions, one after the other: the first save was made on the stamps it had, the second with the stamp of the theme only, the third with both - each of them the one the refusal was about', saves.length === 3 && saves[0]?.payload?.base === 'stamp-1' && saves[0]?.payload?.brandBase === 'brand-1' && saves[1]?.payload?.base === 'stamp-9' && saves[1]?.payload?.brandBase === 'brand-1' && saves[2]?.payload?.base === 'stamp-9' && saves[2]?.payload?.brandBase === 'brand-5' && w.state_() === '✓ Saved' );
}

{
	// a save as that the brand of another tab refuses: Overwrite is the answer to the stamp, and no force goes with it
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.state.hash = '#themes/craft/brand/colours';
	w.panel.showCurrent();
	w.serve();
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	w.brandStamp = 'brand-5';
	fire( w.button( w.bar(), 'Save as own theme' ), 'click' );
	pressed( w, 'Save' );
	findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	pressed( w, 'Save' );
	w.serve();
	pressed( w, 'Overwrite' );
	w.serve();

	const asks = w.sent.filter( function( request ) { return request.action === 'themes/save-as' } );

	check( 'P. a save as that the brand of another tab refuses is asked once more with the stamp that stands and with no force: Overwrite is the answer to a stamp', asks.length === 2 && asks[0]?.payload?.brandBase === 'brand-1' && asks[1]?.payload?.brandBase === 'brand-5' && asks[1]?.payload?.force === false && w.state_() === '✓ Saved' );
}

{
	// the stylesheet of the project is somebody else's: asked, as for every change that writes it
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.answer( 409, { error : 'the stylesheet of the project is not the one this feature wrote', code : 'themes_foreign', params : [], field : '' } );

	check( 'P. a stylesheet that is not the feature\'s is explained and Replace is offered, for the brand as it is for the theme', w.dialogs.length === 2 && w.text( w.dialogs[1] ).indexOf( E('themes_foreign') ) !== -1 && w.requests.length === 0 );

	pressed( w, 'Replace' );

	check( 'P. ...Replace asks again with force, the stamp of the brand the one it was', w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' && w.requests[0]?.payload?.force === true && w.requests[0]?.payload?.brandBase === 'brand-1' );
	w.serve();
}

{
	// production: the question is the workbench's, and the mode may leave it out
	const w = world( { active : 'nino' } );
	const asked = [];

	w.Nino.admin.mode = { policy : function( level ) { asked.push( level ); return { ask : level === 'destructive', note : '' } } };
	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'P. a mode that does not ask before a big thing is not asked: the brand is saved at once, and the level that was asked of the policy is big', asked.join() === 'big' && w.dialogs.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' );
	w.serve();
}

// --- Q. the own css ------------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/css' );

	const text = byId( w.theme, 'themes-css' );

	check( 'Q. the own css is a section of the first level with an address of its own, and it is open: a field of text, named by the label that holds it, in a face of fixed width, off spell checking, wrapping nothing', w.state.hash === '#themes/own.lindner/css' && text.tagName === 'TEXTAREA' && text.parentNode.tagName === 'LABEL' && w.text( text.parentNode ).indexOf( W('label/css') ) !== -1 && text.className.indexOf('themes-code') !== -1 && text.spellcheck === false && text.attributes.wrap === 'off' );
	check( 'Q. one sentence at the field says that the text goes into the website as it is, unchecked, after the parts of the theme and before the stylesheet of the project - and the field points at it', w.text( byId( w.theme, 'themes-css-hint' ) ) === Wp( 'hint/css', 64 ) && text.attributes['aria-describedby'] === 'themes-css-hint' );
	check( 'Q. a theme with no css says so in the head of the section, and nothing is changed', w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('none') !== -1 && w.dirty.entries.themes.isDirty() === false );

	type( text, 'a { color: red }\nb { color: blue }' );

	check( 'Q. what is typed is the css of the draft: the head says how many lines and that it is changed, the bar says changed, and the field is the one that was typed in', w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('2 lines') !== -1 && w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('changed') !== -1 && w.state_() === '● Changed' && w.dirty.entries.themes.isDirty() === true );
	check( 'Q. the draft follows when the hand has rested - for longer than it does for a knob, which is how a text is typed - as one request, with the css as it was typed', w.timers.filter( function( timer ) { return timer.ms === 400 } ).length === 1 && w.timers.filter( function( timer ) { return timer.ms === 150 } ).length === 0 && ( w.tick(), w.requests.length === 1 && w.requests[0]?.action === 'themes/preview' && w.requests[0]?.payload?.mix?.css === 'a { color: red }\nb { color: blue }' ) );
	w.serve();

	type( text, '  \n\n ' );

	check( 'Q. space and line breaks and nothing else is no css: it is no change of the saved state, which says nothing, and the head says none', w.dirty.entries.themes.isDirty() === false && w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('none') !== -1 );

	type( text, 'a { }' );
	type( text, '' );

	check( 'Q. what is emptied is the saved state again, and the head says none', w.dirty.entries.themes.isDirty() === false && w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('none') !== -1 );

	type( text, 'a { }\r\n\r\n' );

	check( 'Q. the line ending that a person\'s system writes is the one of the server\'s: a carriage return is no line', w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('1 line') !== -1 && w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('lines') === -1 );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'Q. saved, the css goes with the mix as it was typed - it is the theme\'s, so it is saved with the theme and with no other request - and the server trims it', w.requests.length === 1 && w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.mix?.css === 'a { }\r\n\r\n' );
	w.serve();
}

{
	// what the server would refuse is said at the field, and is not sent
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/css' );

	const text = byId( w.theme, 'themes-css' );

	type( text, 'a { content: "ä" }'.repeat( 1 ) );
	w.tick();
	w.serve();

	check( 'Q. a text with characters of more than one byte is counted as the server counts it', byId( w.theme, 'themes-css-problem' ) === undefined && w.requests.length === 0 );

	type( text, '/* '+ 'x'.repeat( 65536 ) );

	check( 'Q. a text of more than 65536 bytes is said at the field, in its own sentence, which the field points at, and the field is marked', w.text( byId( w.theme, 'themes-css-problem' ) ) === Wp( 'error/css-long', 64 ) && text.attributes['aria-invalid'] === 'true' && text.attributes['aria-describedby'] === 'themes-css-problem' );
	check( 'Q. ...Save cannot be pressed, and the draft is not asked for with it - nothing is sent with the hand at rest - and the one that was made before stays on the screen', w.button( w.bar(), 'Save' ).disabled === true && w.timers.filter( function( timer ) { return timer.fn !== null } ).length === 0 );

	type( text, 'a { }' );

	check( 'Q. the sentence goes with the text that is too long', byId( w.theme, 'themes-css-problem' ) === undefined && text.attributes['aria-invalid'] === undefined && text.attributes['aria-describedby'] === 'themes-css-hint' && w.button( w.bar(), 'Save' ).disabled === false );
	w.timers.length = 0;

	type( text, 'a { }\0' );

	check( 'Q. a null byte is said as well, and is not sent', w.text( byId( w.theme, 'themes-css-problem' ) ).indexOf( W('error/css-null') ) !== -1 && w.button( w.bar(), 'Save' ).disabled === true );

	// two bytes for a character: the limit is bytes
	type( text, 'ä'.repeat( 32768 ) );

	check( 'Q. the limit is bytes and not characters: 32768 letters of two bytes are 65536 and are taken, one more is not', byId( w.theme, 'themes-css-problem' ) === undefined && ( type( text, 'ä'.repeat( 32769 ) ), byId( w.theme, 'themes-css-problem' ) !== undefined ) );
}

{
	// the preview with the hostile text: it is sent as it is, and it is text
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/css' );

	const hostile = '</style><script>window.x = 1</script><style>a { color: red';

	type( byId( w.theme, 'themes-css' ), hostile );
	w.tick();

	check( 'Q. a text with a closing tag of a style, a script and a brace that is never closed is the text that goes to the preview, untouched - and nothing of it is a node of the panel', w.requests[0]?.payload?.mix?.css === hostile && w.byTag( w.theme, 'script' ).length === 0 && w.byTag( w.theme, 'style' ).length === 0 && byId( w.theme, 'themes-css-problem' ) === undefined );
	w.serve();
	check( 'Q. ...and the field holds what was typed, and the panel is whole: the groups and the settings are there', byId( w.theme, 'themes-css' ).value === hostile && w.theme.querySelectorAll('[aria-expanded]').length === 3 );
}

// --- R. the settings of a theme: its name, what it is made of, the last state, deleting ----------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );

	const name = byId( w.theme, 'themes-theme-name' );

	check( 'R. the settings are a section of the first level with an address of their own, and open: the name of the theme in a field, what the theme is made of in a sentence, and the three things that can be done with it as a whole', w.state.hash === '#themes/own.lindner/settings' && name.value === 'Lindner' && w.text( byId( w.theme, 'themes-groups-1-body' ) ).indexOf( W('label/mix') ) !== -1 && byId( w.theme, 'themes-save-as' ) !== undefined && byId( w.theme, 'themes-restore' ) !== undefined && byId( w.theme, 'themes-delete' ) !== undefined );
	check( 'R. what the theme is made of is the sentence the dialog of the name has: where each group takes its part from', w.text( byId( w.theme, 'themes-groups-1-body' ) ).indexOf( [ Wp( 'msg/made-of', W('word/page'), 'Nino' ), Wp( 'msg/made-of', 'Section', 'Craft' ) ].join(', ')+ ', '+ Wp( 'msg/mix-rest', W('word/standard') ) ) !== -1, w.text( byId( w.theme, 'themes-groups-1-body' ) ) );
	check( 'R. the name of an own theme is a field that can be written in, one line of at most the longest name the API takes; a name has a sentence that says what it is; the name stands in the head of the section', name.readOnly === false && name.maxLength === 60 && w.text( byId( w.theme, 'themes-name-hint' ) ).indexOf( W('hint/name-own') ) !== -1 && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Lindner') !== -1 && name.attributes['aria-describedby'] === 'themes-name-hint' );

	type( name, 'Lindner Two' );

	check( 'R. a new name is a change of the draft - the head of the section says it, so does the bar - and the title over the split view is the saved name until it is saved', w.state_() === '● Changed' && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Lindner Two') !== -1 && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('changed') !== -1 && w.text( w.byClass( w.theme, 'themes-name' )[0] ) === 'Lindner' );

	type( name, '   ' );

	check( 'R. a name that says nothing is said at the field, which is marked, and is not taken: the draft keeps the last name that was one', w.text( byId( w.theme, 'themes-name-error' ) ) === W('error/name-empty') && name.attributes['aria-invalid'] === 'true' && name.attributes['aria-describedby'] === 'themes-name-error' && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Lindner Two') !== -1 );

	type( name, 'Lindner Three' );

	check( 'R. ...and the sentence goes with the next letter', byId( w.theme, 'themes-name-error' ) === undefined && name.attributes['aria-invalid'] === undefined && name.attributes['aria-describedby'] === 'themes-name-hint' );

	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'R. a rename is a save: save, the key of the theme, the new name in the mix and the stamp it was seen in - the key does not change', w.requests.length === 1 && w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.theme === 'own:lindner' && w.requests[0]?.payload?.mix?.name === 'Lindner Three' && w.requests[0]?.payload?.base === 'stamp-1' );
	w.serve();

	check( 'R. ...and what is saved is read again: the title and the card of the theme are the new name only if the server says so - the stand-in does not, so the name that comes back is the one the server keeps', w.state_() === '✓ Saved' && w.dirty.entries.themes.isDirty() === false );
}

{
	// a theme of a package has a name that is read, and no delete
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/settings' );

	const name = byId( w.theme, 'themes-theme-name' );

	check( 'R. the name of a theme of a package is read and not written, and the sentence says that it is never overwritten; it has no button to delete it, and says that it is not deleted here', name.value === 'Craft' && name.readOnly === true && w.text( byId( w.theme, 'themes-name-hint' ) ) === Wp( 'hint/name-package', '1.0.0' ) && byId( w.theme, 'themes-delete' ) === undefined && w.text( byId( w.theme, 'themes-delete-hint' ) ) === W('hint/delete-package') );
	check( 'R. the first level is the settings of a theme of a package as well, with the name in the head of the section, and no change in it', w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Craft') !== -1 && w.dirty.entries.themes.isDirty() === false );

	// Save as own theme in the settings: the dialog, and the name goes with the mix
	const seen = levels( w );

	fire( byId( w.theme, 'themes-save-as' ), 'click' );

	const dialog = w.dialogs[w.dialogs.length - 1];

	check( 'R. "Save as own theme" in the settings asks the name in the dialog of the bar, with a suggestion made of the name of the theme - it needs no change of the draft, and the website is not asked about, because it does not show what is saved', dialog.open === true && findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0].value === 'Craft 2' && seen.length === 0 && w.requests.length === 0 );

	pressed( w, 'Cancel' );

	check( 'R. Cancel sends nothing and the focus is back on the button', w.requests.length === 0 && byId( w.theme, 'themes-save-as' ).focused === true );

	fire( byId( w.theme, 'themes-save-as' ), 'click' );
	pressed( w, 'Save' );

	check( 'R. the name is sent with the mix of the draft - which is the theme as it stands - to save-as, and no brand, since none is changed', w.requests[0]?.action === 'themes/save-as' && w.requests[0]?.payload?.mix?.name === 'Craft 2' && w.requests[0]?.payload?.brand === undefined );
	w.serve();
	check( 'R. what is saved is opened: the address is the own theme', w.state.hash.indexOf('#themes/own.craft 2') === 0 || w.state.hash.indexOf('#themes/own.craft%202') === 0 );
}

{
	// the last state: when it can be used
	const none = world( { active : 'nino' } );

	none.at( '#themes/own.lindner/settings' );

	check( 'R. the last state is not to be used where there is none: the button cannot be pressed and the sentence says what it is waiting for', byId( none.theme, 'themes-restore' ).disabled === true && none.text( byId( none.theme, 'themes-restore-hint' ) ).indexOf( W('hint/restore-none') ) !== -1 && byId( none.theme, 'themes-restore' ).attributes['aria-describedby'] === 'themes-restore-hint' );
}

{
	// what it says, what it asks
	const w = world( { active : 'nino' } );
	const seen = levels( w );

	w.canRestore = true;
	w.at( '#themes/own.lindner/settings' );

	check( 'R. Nr. 7: with a last state it is - and says what it covers: theme, brand, own themes and stylesheet, and that a second restore is the state as it is now', byId( w.theme, 'themes-restore' ).disabled === false && w.text( byId( w.theme, 'themes-restore-hint' ) ) === Wp( 'hint/restore', W('msg/last-unknown') ) );

	type( byId( w.theme, 'themes-theme-name' ), 'Lindner x' );

	check( 'R. Nr. 7: not with a change that is not saved - the button cannot be pressed and the sentence says to save or reset first', byId( w.theme, 'themes-restore' ).disabled === true && w.text( byId( w.theme, 'themes-restore-hint' ) ).indexOf( W('hint/restore-dirty') ) !== -1 );

	fire( w.button( w.bar(), 'Reset' ), 'click' );
	w.serve();

	check( 'R. ...and it can be as soon as the change is gone', byId( w.theme, 'themes-restore' ).disabled === false && w.text( byId( w.theme, 'themes-restore-hint' ) ) === Wp( 'hint/restore', W('msg/last-unknown') ) );

	fire( byId( w.theme, 'themes-restore' ), 'click' );

	check( 'R. it is asked first, as a big thing, in a question that says what it covers and that the state as it is now becomes the last state', seen.join() === 'big' && w.dialogs.length === 1 && w.text( w.dialogs[0] ).indexOf( Wp( 'confirm/restore', W('msg/last-unknown') ) ) !== -1 );

	pressed( w, 'Cancel' );

	check( 'R. No does nothing and the focus is on the button', w.requests.length === 0 && byId( w.theme, 'themes-restore' ).focused === true );

	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );

	check( 'R. Yes asks the API with the state the panel saw it in, and no force', w.requests.length === 1 && w.requests[0]?.action === 'themes/restore' && w.requests[0]?.payload?.base === 'state-1' && w.requests[0]?.payload?.force === false );
	w.serve();

	check( 'R. what was restored is asked for again and shown: the list, the theme, its draft - and the line says that the last state is back; the focus is on the button that was pressed', w.actions.slice( w.actions.indexOf('themes/restore') ).join() === 'themes/restore,themes/list,themes/open,themes/preview' && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf( W('msg/restored') ) !== -1 && byId( w.theme, 'themes-restore' ).focused === true && w.state_() === '✓ Saved' );

	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );

	check( 'R. twice is now: the stamp of the state is the one the first restore left, and the second is asked with it', w.requests[0]?.payload?.base === 'state-2' );
	w.serve();
}

{
	// a state that another tab changed
	const w = world( { active : 'nino' } );

	w.canRestore = true;
	w.at( '#themes/own.lindner/settings' );
	w.stateStamp = 'state-7';
	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'R. a restore on a state that another tab changed is said in the question of the conflict - three answers, the sentence of the server - and nothing is swapped', question.open === true && w.text( question ).indexOf( E('themes_state') ) !== -1 && w.restored === 0 );

	pressed( w, 'Cancel' );

	check( 'R. Cancel leaves it, and the focus is on the button', w.requests.length === 0 && byId( w.theme, 'themes-restore' ).focused === true );

	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );
	w.serve();
	pressed( w, 'Overwrite' );
	w.serve();

	check( 'R. Overwrite asks the theme as it is now, to have the stamp of the state, and the restore is asked again with that stamp and with nothing else taken over', w.restored === 1 && w.sent.filter( function( request ) { return request.action === 'themes/restore' } ).slice( -1 )[0]?.payload?.base === 'state-7' && w.sent.filter( function( request ) { return request.action === 'themes/restore' } ).slice( -1 )[0]?.payload?.force === false );
}

{
	// a theme that is gone from the state that is restored
	const w = world( { active : 'nino' } );

	w.canRestore = true;
	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );
	w.deleted.push( 'own:lindner' );
	w.serve();

	check( 'R. a theme that is not in the state that comes back is not shown: the list is, and says so', w.state.hash === '#themes' && w.list.className.indexOf('admin-hidden') === -1 && w.text( byId( w.list, 'themes-say' ) ).indexOf( Wp( 'msg/restored-gone', 'Lindner' ) ) !== -1 && w.theme.children.length === 0 );
}

{
	// delete
	const w = world( { active : 'nino' } );
	const seen = levels( w );

	w.at( '#themes/own.lindner/settings' );

	const remove = byId( w.theme, 'themes-delete' );

	check( 'R. an own theme that is not the website\'s has a button to delete it, and the sentence says that the website does not change', remove.disabled === false && w.text( byId( w.theme, 'themes-delete-hint' ) ).indexOf( W('hint/delete') ) !== -1 && remove.attributes['aria-describedby'] === 'themes-delete-hint' );

	fire( remove, 'click' );

	check( 'R. it is asked first as a thing that cannot be taken back - destructive, with the name - and the question has the red answer', seen.join() === 'destructive' && w.text( w.dialogs[0] ).indexOf( Wp( 'confirm/delete-title', 'Lindner' ) ) !== -1 && findAll( w.dialogs[0], function( node ) { return node.className.indexOf('nino-admin-btn-danger') !== -1 } ).length === 1 );

	pressed( w, 'Cancel' );

	check( 'R. No does nothing and the focus is on the button', w.requests.length === 0 && byId( w.theme, 'themes-delete' ).focused === true );

	fire( byId( w.theme, 'themes-delete' ), 'click' );
	pressed( w, 'Delete theme' );

	check( 'R. Yes asks the API with the key, the stamp the theme was seen in and no force', w.requests.length === 1 && w.requests[0]?.action === 'themes/delete' && w.requests[0]?.payload?.theme === 'own:lindner' && w.requests[0]?.payload?.base === 'stamp-1' && w.requests[0]?.payload?.force === false );
	w.serve();

	check( 'R. what is deleted is gone: the list is shown, the address is the list, the line says which theme was deleted, and the card is not there', w.state.hash === '#themes' && w.text( byId( w.list, 'themes-say' ) ) === Wp( 'msg/deleted', 'Lindner' ) && w.byTag( w.list, 'article' ).filter( function( card ) { return card.dataset.theme === 'own:lindner' } ).length === 0 && w.dirty.entries.themes.isDirty() === false );
}

{
	// the theme of the website is not deleted - what the API says
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/settings' );

	check( 'R. the theme that the website shows has a button to delete that cannot be pressed, and the sentence says to apply another one first', byId( w.theme, 'themes-delete' ).disabled === true && w.text( byId( w.theme, 'themes-delete-hint' ) ).indexOf( W('hint/delete-active') ) !== -1 );
}

{
	// delete: a theme that was changed by another tab, a stylesheet that is somebody else's
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );
	w.stamp = 'stamp-9';
	fire( byId( w.theme, 'themes-delete' ), 'click' );
	pressed( w, 'Delete theme' );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'R. a theme that another tab changed since it was opened is said in the question of the conflict before it is deleted, and the theme is there', w.text( question ).indexOf( Wp( 'api/themes_changed', 'Lindner' ) ) !== -1 && w.deleted.length === 0 );

	pressed( w, 'Overwrite' );
	w.serve();

	check( 'R. Overwrite takes the stamp that stands now and deletes it with that', w.deleted.join() === 'own:lindner' && w.sent.filter( function( request ) { return request.action === 'themes/delete' } ).slice( -1 )[0]?.payload?.base === 'stamp-9' && w.sent.filter( function( request ) { return request.action === 'themes/delete' } ).slice( -1 )[0]?.payload?.force === false );

	const y = world( { active : 'nino' } );

	y.at( '#themes/own.lindner/settings' );
	fire( byId( y.theme, 'themes-delete' ), 'click' );
	pressed( y, 'Delete theme' );
	y.answer( 409, { error : 'the stylesheet of the project is not the one this feature wrote', code : 'themes_foreign', params : [], field : '' } );
	pressed( y, 'Replace' );

	check( 'R. a stylesheet that is not the feature\'s is explained, and Replace deletes with force', y.requests.length === 1 && y.requests[0]?.action === 'themes/delete' && y.requests[0]?.payload?.force === true && y.requests[0]?.payload?.base === 'stamp-1' );
}

{
	// the questions are the workbench's: levels, and a mode that asks for none of them
	const w = world( { active : 'nino' } );
	const asked = [];

	w.Nino.admin.mode = { policy : function( level ) { asked.push( level ); return { ask : false, note : '' } } };
	w.canRestore = true;
	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-restore' ), 'click' );
	w.serve();
	fire( byId( w.theme, 'themes-delete' ), 'click' );
	w.serve();

	check( 'R. production: a restore asks the policy of the mode at the level big and a delete at destructive, and where the mode asks for neither both are done at once', asked.join() === 'big,destructive' && w.dialogs.length === 0 && w.restored === 1 && w.sent.filter( function( request ) { return request.action === 'themes/delete' } ).length === 1 );
}

// --- S. the second round: what the brand says where no theme is shown, a colour that is not one yet, a font that is gone, and the rest --------------------

/** The text of a node, or nothing where there is none: a check that asks for a node that is not there is red and does not stop the others */
function said( w, node ) {
	return node === undefined || node === null ? '' : w.text( node );
}

{
	// B1: where no theme is on the website the brand reaches nobody, and the panel does not say that visitors see it
	const w = world( { active : '' } );
	const seen = levels( w );

	w.at( '#themes/nino/brand/colours' );

	check( 'S. no theme on the website: the level of the brand says that the brand is saved and holds once a theme is applied', said( w, w.byClass( w.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent === W('msg/brand-later') } )[0] ) === W('msg/brand-later') );

	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );

	check( 'S. ...and it is saved with no question that is untrue: nothing reaches a visitor, so the save is asked of the API at once', seen.length === 0 && w.dialogs.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' );
	w.serve();

	const on = world( { active : 'nino' } );

	on.at( '#themes/nino/brand/colours' );

	check( 'S. with a theme on the website the level says nothing of the kind', on.byClass( on.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent === W('msg/brand-later') } ).length === 0 );

	type( byId( on.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	on.tick();
	on.serve();
	fire( on.button( on.bar(), 'Save' ), 'click' );

	check( 'S. ...and the question is asked, with the sentence of the brand, in which no word says that visitors see it: that is the workbench\'s to say, by the mode', said( on, on.dialogs[0] ).indexOf( W('confirm/brand') ) !== -1 && W('confirm/brand').indexOf( W('confirm/save-title') ) === -1 );
}

{
	// B2: a colour that is on its way to one
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/brand/colours' );

	const colour = byId( w.theme, 'themes-brand-color-1' );

	type( colour, '#123' );
	w.tick();

	check( 'S. a code that is a colour on the way - #123 - is followed by the preview, and the draft holds it for now', w.requests.length === 1 && w.requests[0]?.payload?.brand?.['color-1']?.value === '#112233' && w.dirty.entries.themes.isDirty() === true );
	w.serve();

	fire( w.step( 'themes-brand-tone', 0 ), 'click' );
	type( colour, '#1234' );

	check( 'S. a code that is no colour at the end puts the draft back to the last colour that was left in the field - the theme\'s - and the colour is no own value', byId( w.theme, 'themes-brand-back-color-1' ).hidden === true && ( w.tick(), w.requests.slice( -1 )[0]?.payload?.brand?.['color-1']?.own === false ) );
	check( 'S. ...and the bar says why it cannot be saved, whichever section is open; Save cannot be pressed, and a save by the keyboard is told no and sends nothing', said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === Wp( 'error/colour-invalid', W('label/color-1') ) && w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'error' && w.button( w.bar(), 'Save' ).disabled === true && ( w.sent.length = 0, w.dirty.entries.themes.save( function( ok ) { w.kept = ok } ), w.kept === false && w.requests.filter( function( request ) { return request.action !== 'themes/preview' } ).length === 0 ) );

	type( colour, '#abc' );
	fire( colour, 'change' );
	type( colour, '#abcd' );

	check( 'S. a colour that was left in the field is the one the draft goes back to: #abc was left, so #abcd is #aabbcc again and not the colour of the theme', ( w.tick(), w.serve(), w.sent.filter( function( request ) { return request.action === 'themes/preview' } ).slice( -1 )[0]?.payload?.brand?.['color-1']?.value === '#aabbcc' ) && w.dirty.entries.themes.isDirty() === true && w.button( w.bar(), 'Save' ).disabled === true );

	type( colour, '#abcdef' );

	check( 'S. a colour at last: the reason goes from the bar, and the draft can be saved', said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === '' && w.button( w.bar(), 'Save' ).disabled === false );

	// the way back clears what is wrong with the field
	type( colour, '#12' );
	fire( byId( w.theme, 'themes-brand-back-color-1' ) ?? element('button'), 'click' );
	type( colour, '#12' );
	fire( w.button( w.bar(), 'Reset' ), 'click' );

	check( 'S. a reset leaves no colour that is wrong behind: the field shows the saved colour, and nothing keeps the draft from being saved', byId( w.theme, 'themes-brand-color-1' ).value === '#1c5db8' && said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf( Wp( 'error/colour-invalid', W('label/color-1') ) ) === -1 );
}

{
	// the css that cannot be saved is in the bar, whichever section is open
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/css' );
	type( byId( w.theme, 'themes-css' ), '/* '+ 'x'.repeat( 65536 ) );

	check( 'S. a css that is too long is said in the bar as well as at the field, where the bar is what is looked at', said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === Wp( 'error/css-long', 64 ) && w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'error' );

	type( byId( w.theme, 'themes-css' ), 'a { }' );

	check( 'S. ...and goes with it', w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state !== 'error' && said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) !== Wp( 'error/css-long', 64 ) );
}

{
	// B3: a font whose folder is gone
	const w = world( { active : 'nino' } );

	w.rest = 'gone';
	w.brandStored = Object.assign( brandNone(), { 'font-title' : { own : true, value : 'folder-gone' } } );
	w.at( '#themes/nino/brand/type' );

	const select = byId( w.theme, 'themes-brand-font-title' );
	const options = w.byTag( select, 'option' ).filter( function( node ) { return node.value === 'folder-gone' } );

	check( 'S. a font whose folder is gone stays the value of the field, and the option says that it is not available', select.value === 'folder-gone' && options.length === 1 && options[0].textContent === Wp( 'msg/font-gone', 'folder-gone' ) );
	check( 'S. ...and a sentence at the field says what the page does meanwhile, which the select points at; a font that is there has none', said( w, byId( w.theme, 'themes-brand-font-title-gone' ) ) === Wp( 'msg/font-gone-note', 'folder-gone' ) && select.attributes['aria-describedby'] === 'themes-brand-font-title-gone' && byId( w.theme, 'themes-brand-font-text-gone' ) === undefined );
	check( 'S. the draft is no change: the value is the one that is stored, and what is stored is sent back as it is', w.dirty.entries.themes.isDirty() === false && w.requests.length === 0 );
}

{
	// 6c: a colour that is changed for the text and the buttons that are drawn with it
	const w = world( { active : 'nino' } );

	w.rest = 'adjust';
	w.at( '#themes/nino/brand/colours' );

	const adjusted = w.byClass( w.theme, 'themes-adjusted' )[0];

	check( 'S. a colour that is made darker for text and buttons says so, at the colour, with the mode and the colour it is made - and only for the mode where it is', said( w, adjusted ) === Wp( 'msg/adjust-darker', W('word/mode-light'), '#7f6904' ) && adjusted.hidden === false );

	const plain = world( { active : 'nino' } );

	plain.at( '#themes/nino/brand/colours' );

	check( 'S. a colour that is used as it is says nothing', plain.byClass( plain.theme, 'themes-adjusted' )[0].hidden === true && said( plain, plain.byClass( plain.theme, 'themes-adjusted' )[0] ) === '' );
}

{
	// 6f: the last state says what comes back
	const w = world( { active : 'nino' } );

	w.canRestore = true;
	w.last = { at : 1794829846, theme : 'own:lindner', name : 'Lindner' };
	w.at( '#themes/own.lindner/settings' );

	const hint = said( w, byId( w.theme, 'themes-restore-hint' ) );

	check( 'S. the last state is said by its time and the theme the website showed in it: the hint names the theme and the year of the day it was saved', hint.indexOf( 'Lindner' ) !== -1 && hint.indexOf( String( new Date( 1794829846 * 1000 ).getFullYear() ) ) !== -1 );

	fire( byId( w.theme, 'themes-restore' ), 'click' );

	check( 'S. ...and so does the question of the restore', said( w, w.dialogs[0] ).indexOf( 'Lindner' ) !== -1 );

	pressed( w, CANCEL );
	fire( byId( w.theme, 'themes-delete' ), 'click' );

	check( 'S. a delete says in its question that it replaces the last state that is kept, and so does an apply, and a save of the theme of the website', said( w, w.dialogs[1] ).indexOf( W('msg/replaces-last').split('%s')[0] ) !== -1 );

	pressed( w, CANCEL );
	fire( w.button( w.bar(), 'Apply to the website' ), 'click' );

	check( 'S. ...an apply too', said( w, w.dialogs[2] ).indexOf( W('msg/replaces-last').split('%s')[0] ) !== -1 );

	const plain = world( { active : 'nino' } );

	plain.canRestore = true;
	plain.at( '#themes/own.lindner/settings' );
	fire( byId( plain.theme, 'themes-delete' ), 'click' );

	check( 'S. a last state that was saved before its time was kept is replaced all the same, and the sentence says it without the time', said( plain, plain.dialogs[0] ).indexOf( W('msg/replaces-last-plain') ) !== -1 );

	const none = world( { active : 'nino' } );

	none.at( '#themes/own.lindner/settings' );
	fire( byId( none.theme, 'themes-delete' ), 'click' );

	check( 'S. where there is no last state kept nothing is said of replacing it', said( none, none.dialogs[0] ).indexOf( W('msg/replaces-last').split('%s')[0] ) === -1 );
}

{
	// 6g: a name that a card has is said at the field, and the same name again is no change
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );

	const name = byId( w.theme, 'themes-theme-name' );

	type( name, 'Lindner x' );
	type( name, 'Lindner' );

	check( 'S. the same name typed again is no change: the draft is the saved state, the bar does not say changed and the head of the section has no mark', w.dirty.entries.themes.isDirty() === false && w.byClass( w.theme, 'themes-state' )[0].dataset.state !== 'warn' && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf( W('state/changed-mark') ) === -1 );

	type( name, 'Taken' );
	fire( w.button( w.bar(), 'Save' ), 'click' );
	w.serve();

	check( 'S. a name that another card has is said at the name field, with the name of that card, and not only in the bar', said( w, byId( w.theme, 'themes-name-error' ) ) === Wp( 'api/themes_name_taken', 'Taken Before' ) && name.attributes['aria-describedby'] === 'themes-name-error' );

	type( name, 'Taken 2' );

	check( 'S. ...and the sentence goes with the next letter', byId( w.theme, 'themes-name-error' ) === undefined );
}

{
	// the merge of the brand of two tabs
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();

	// the other tab saved the second colour, and a first one of its own
	w.brandStored = Object.assign( brandNone(), { 'color-1' : { own : true, value : '#000001' }, 'color-2' : { own : true, value : '#000002' } } );
	w.brandStamp = 'brand-5';
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.serve();

	const question = said( w, w.dialogs[w.dialogs.length - 1] );

	check( 'S. the question says which values the two tabs changed - the first colour, and not the second, which only the other tab changed - and what Overwrite does', question.indexOf( Wp( 'msg/brand-both', W('label/color-1') ) ) !== -1 && question.indexOf( W('label/color-2') ) === -1 && question.indexOf( W('msg/brand-merge') ) !== -1 );

	pressed( w, 'Overwrite' );

	const sent = w.requests[0]?.payload?.brand;

	check( 'S. Overwrite merges by value: the first colour is the one of this tab, the second is the one the other tab saved - what this tab did not touch is not written over - and nothing is sent with force', sent['color-1'].value === '#9a4b1d' && sent['color-2'].value === '#000002' && w.requests[0]?.payload?.brandBase === 'brand-5' && w.requests[0]?.payload?.force === false );
	w.serve();

	const quiet = world( { active : 'nino' } );

	quiet.at( '#themes/craft/brand/colours' );
	type( byId( quiet.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	quiet.tick();
	quiet.serve();
	quiet.brandStored = Object.assign( brandNone(), { 'color-2' : { own : true, value : '#000002' } } );
	quiet.brandStamp = 'brand-5';
	fire( quiet.button( quiet.bar(), 'Save' ), 'click' );
	pressed( quiet, 'Save' );
	quiet.serve();

	check( 'S. where the other tab touched other values only, the question names none as changed by both', said( quiet, quiet.dialogs[quiet.dialogs.length - 1] ).indexOf( W('msg/brand-both').split('%s')[0] ) === -1 );
}

{
	// 5.2: a stylesheet that cannot be read, or cannot be replaced
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), 'Save' ), 'click' );
	pressed( w, 'Save' );
	w.answer( 409, { error : 'the stylesheet cannot be read', code : 'themes_unreadable', params : [], field : '' } );

	check( 'S. a stylesheet that cannot be read is asked about as one that is somebody else\'s, in the sentence that says that what it holds is not kept as the last state', said( w, w.dialogs[1] ).indexOf( E('themes_unreadable') ) !== -1 && w.requests.length === 0 );

	pressed( w, 'Replace' );

	check( 'S. ...and Replace sends the force', w.requests.length === 1 && w.requests[0]?.payload?.force === true );

	w.answer( 409, { error : 'the stylesheet cannot be replaced', code : 'themes_locked', params : [], field : '' } );

	check( 'S. a stylesheet that cannot be replaced is said in the bar, and is no success: the state stays changed and there is no question', w.dialogs.length === 2 && w.state_() === '● Changed' && said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === E('themes_locked') );
}

{
	// 7: the way back from the images, the groups hint, the stamp of an apply
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/brand/logo' );
	fire( byId( w.theme, 'themes-logo-open' ), 'click' );
	w.state.hash = '#themes/nino/brand/logo';
	w.panel.showCurrent();

	check( 'S. the way back from the images ends on the link that went there', byId( w.theme, 'themes-logo-open' ).focused === true );

	const groups = world( { active : 'nino' } );

	groups.at( '#themes/nino' );

	check( 'S. the sentence that explains the groups is under the groups, and not under the settings or the own css', said( groups, groups.theme ).indexOf( W('hint/groups') ) !== -1 );

	groups.state.hash = '#themes/nino/settings';
	groups.panel.showCurrent();
	groups.serve();

	check( 'S. ...it goes with the section: the settings are open and the sentence is not on the screen', groups.byClass( groups.theme, 'themes-groups' ).length === 0 && groups.text( groups.theme ).indexOf( W('hint/groups') ) === -1 );

	const apply = world( { active : 'nino' } );
	const seen = levels( apply );

	apply.at( '#themes/own.lindner/section/section-wrap' );
	fire( apply.button( apply.bar(), 'Apply to the website' ), 'click' );
	pressed( apply, 'Apply to the website' );

	check( 'S. an own theme is applied as the stamp the panel has of it says: the apply goes with the stamp, as a save does', apply.requests[0]?.action === 'themes/apply' && apply.requests[0]?.payload?.base === 'stamp-1' && seen.join() === 'big' );

	apply.stamp = 'stamp-9';
	apply.serve();

	const card = world( { active : 'nino' } );

	card.at( '#themes' );
	fire( findAll( card.list, function( b ) { return b.dataset.apply === 'own:lindner' } )[0], 'click' );
	pressed( card, 'Apply to the website' );

	check( 'S. a card of the list applies the stamp of the theme as the list has it', card.requests[0]?.payload?.base === 'stamp-1' );

	card.stamp = 'stamp-9';
	card.serve();

	check( 'S. ...and where another tab saved the theme meanwhile the list says so and shows the theme as it is now, and the website keeps what it shows', said( card, byId( card.list, 'themes-say' ) ) === Wp( 'api/themes_changed', 'Lindner' ) && card.active === 'nino' && card.dialogs.length === 1 );
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
