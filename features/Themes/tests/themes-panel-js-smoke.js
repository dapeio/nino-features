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

/**
 *	Every question of a script of the feature is asked with a title: each call of confirm(), choiceDialog() and formDialog() in the files names one, and
 *	the one of confirm() and choiceDialog() is a word of the feature or of the workbench - a call, or a literal - and no variable that could be empty
 */
const dialogsWithoutTitle = function( sources ) {

	const bad = [];

	sources.forEach( function( file ) {

		const text = fs.readFileSync( file, 'utf8' );
		const calls = /Nino\.adminUi\.(confirm|choiceDialog|formDialog)\(/g;
		let found;

		while( ( found = calls.exec( text ) ) !== null ) {

			const open = text.indexOf( '{', found.index );
			let depth = 0;
			let top = '';
			let quote = '';

			for( let at = open; at < text.length; at++ ) {

				const char = text[at];

				if( quote !== '' ) {
					if( char === '\\' )
						at++;
					else if( char === quote )
						quote = '';
					continue;
				}

				if( char === "'" || char === '"' || char === '`' )
					quote = char;
				else if( char === '{' )
					depth++;
				else if( char === '}' && --depth === 0 )
					break;

				if( depth === 1 )
					top += char;
			}

			const title = /(?:^|[{,\s])title\s*:\s*(\S[^\n]*)/.exec( top );
			const word = found[1] === 'formDialog' ? /./ : /^(?:word\(|Nino\.adminUi\.text\(|Nino\.content\.getText\(|'|")/;

			if( title === null || word.test( title[1] ) === false )
				bad.push( path.basename( file )+ ':'+ text.slice( 0, found.index ).split('\n').length );
		}
	} );

	return bad;
};

const words = {
	en : fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) ),
	de : fills( path.relative( root, path.join( featureDir, 'text/de_DE.php' ) ) ),
};
const workbench = fills('_admin/text/en_US.php');

// The slug of the kernel, which the workbench has from /_nino/Nino.js: the script of the site in a window of its own, and its function taken out
const kernelSlug = ( function() {

	const node = function() { return { classList : { add : function() {}, remove : function() {}, contains : function() { return false } }, addEventListener : function() {}, setAttribute : function() {}, getAttribute : function() { return null }, querySelectorAll : function() { return [] }, querySelector : function() { return null }, dataset : {}, style : {} } };
	const page = Object.assign( node(), { documentElement : node(), body : node(), head : node(), createElement : node, getElementById : function() { return null }, readyState : 'complete', cookie : '' } );
	const site = { document : page, console : console, addEventListener : function() {}, location : { href : 'http://site.test/', hash : '' }, navigator : {}, setTimeout : function() {}, matchMedia : function() { return { matches : false, addEventListener : function() {} } } };

	site.window = site;
	load( '_nino/Nino.js', vm.createContext( site ) );

	return site.Nino.slug;
} )();

// The words of the panel and of the API, by their keys: a test of what the panel says asks for the fill and never writes the sentence
const W = function( key ) { return words.en['/_admin/themes/'+ key] };
const E = function( code ) { return words.en['/_admin/error/'+ code] };
const Wp = function( key, ...params ) { return String( key.indexOf('api/') === 0 ? E( key.slice( 4 ) ) : W( key ) ).replace( /%[sdn]/g, function() { return String( params.shift() ) } ) };
// What the API says of a reason: the code and what goes into its sentence, beside the English sentence that is for the log
const CSS_WHY = { code : 'css', params : [ 'English detail of the css' ] };
const VERSION_WHY = { code : 'group-version', params : [ 'basis', '^9.0' ] };
const CANCEL = workbench['/_admin/common/label/cancel'];
const SAVED = '\u2713 '+ W('state/saved');
const APPLIED = '\u2713 '+ W('state/applied');
const CHANGED = '\u25CF '+ W('state/changed');

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
		.concat( [ 'warm', 'neutral', 'cool' ].map( function( tone ) { return 'word/tone-'+ tone } ), [ 'light', 'dark', 'auto' ].map( function( mode ) { return 'word/mode-'+ mode } ), [ 'sans', 'serif', 'mono' ].map( function( font ) { return 'word/font-system-'+ font } ), [ 'shipped', 'catalogue', 'uploaded', 'project' ].map( function( origin ) { return 'chip/'+ origin } ), [ 'base', 'alt', 'tint', 'ink', 'quiet', 'line', 'accent', 'on', 'picture', 'on-primary', 'on-second' ].map( function( role ) { return 'role/'+ role } ) )
		.forEach( function( key ) { asked.add( key ) } );

	// The reasons the API gives by code: each code of the reader has its words
	( /public const array WHY = \[([\s\S]*?)\n\t\t\];/.exec( fs.readFileSync( path.join( featureDir, 'Reader/Reader.php' ), 'utf8' ) )[1].match( /^\t\t\t'([a-z-]+)'/gm ) ?? [] )
		.forEach( function( line ) { asked.add( 'why/'+ line.trim().slice( 1, -1 ) ) } );

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
	check( 'A. the sentence of a refused mix that names its part has the place of the word of the group in both languages, as the one that names none has none', [ 'en', 'de' ].every( function( locale ) {
		return ( words[locale]['/_admin/error/themes_mix_part'].match( /%s/g ) ?? [] ).length === 1 && words[locale]['/_admin/error/themes_mix'].indexOf('%s') === -1;
	} ) );
	check( 'A. the same fills stand in both languages, and a placeholder is the same in both', Object.keys( words.en ).filter( function( key ) { return key.indexOf('/_admin/themes/') === 0 } ).every( function( key ) {
		return words.de[key] !== undefined && ( words.en[key].match( /%[sdn]/g ) ?? [] ).join() === ( words.de[key].match( /%[sdn]/g ) ?? [] ).join();
	} ) );
	check( 'A. every question the panel asks has a title, a word of the panel or of the workbench', dialogsWithoutTitle( [ scriptPath, path.join( featureDir, 'assets/admin-fonts.js' ) ] ).length === 0, dialogsWithoutTitle( [ scriptPath, path.join( featureDir, 'assets/admin-fonts.js' ) ] ).join() );
	check( 'A. no sentence is written into the script: a text is set with word() and a name with textContent, never as markup', /innerHTML\s*=\s*[^'\s]/.test( script ) === false && /\.insertAdjacentHTML|document\.write|\.outerHTML\s*=/.test( script ) === false );
}

// --- the page -----------------------------------------------------------------------------------------------------

const GROUP_LABEL = { fonts : { de_DE : 'Schriften', en_US : 'Fonts' }, section : { de_DE : 'Abschnitt', en_US : 'Section' }, header : { de_DE : 'Header', en_US : 'Header' }, footer : { de_DE : 'Footer', en_US : 'Footer' }, brand : { de_DE : 'Marke', en_US : 'Brand' }, settings : { de_DE : 'Einstellungen', en_US : 'Settings' }, css : { de_DE : 'CSS', en_US : 'Css' }, groups : { de_DE : 'Gruppen', en_US : 'Groups' } };
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
		return Object.assign( { small : false, step : step, delivered : from.indexOf('craft') === 0 && knob.key === 'section-wrap.space' ? -1 : 0, default : knob.ladder[2] }, knob );
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
	contrast.second = 7.8;

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
	const offers = [ { theme : 'nino', version : '1.0.0', ok : true, problem : '', why : null }, { theme : 'craft', version : '1.0.0', ok : true, problem : '', why : null, values : { 'section-wrap.space' : -1 } }, { theme : 'rest', version : '1.0.0', ok : false, problem : 'English detail of the css', why : CSS_WHY } ];

	return {
		groups : {
			page : { label : '', from : ( part.page ?? {} ).from ?? 'standard', theme : 'nino', version : '1.0.0', origin : 'installed', package : 'installed', problem : '', why : null, missing : false, components : [], offers : offers,
				knobs : [ { key : 'size', label : { de_DE : 'Grundgröße', en_US : 'Base size' }, extra : false, small : false, step : ( ( part.page ?? {} ).values ?? {} ).size ?? 0, default : { '--page-size' : '100%' }, ladder : [ { '--page-size' : '90%' }, { '--page-size' : '95%' }, { '--page-size' : '100%' }, { '--page-size' : '105%' }, { '--page-size' : '110%' } ] },
					{ key : 'motion', label : { de_DE : 'Bewegung', en_US : 'Motion' }, extra : false, small : false, step : 0, default : { '--page-motion' : '.15s' }, ladder : [ { '--page-motion' : '0s' }, { '--page-motion' : '.1s' }, { '--page-motion' : '.15s' }, { '--page-motion' : '.25s' }, { '--page-motion' : '.4s' } ] } ] },
			section : { label : GROUP_LABEL.section, from : from, theme : theme, version : theme === '' ? '' : '1.0.0', origin : theme === '' ? 'standard' : 'installed', package : theme === '' ? '' : 'installed', problem : rest === true ? 'English detail of the version' : '', why : rest === true ? VERSION_WHY : null, missing : false, offers : offers,
				knobs : knobs( from, section.values ?? {} ),
				components : [
					{ name : 'section-title', label : COMPONENT_LABEL['section-title'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' },
					{ name : 'section-wrap', label : COMPONENT_LABEL['section-wrap'], variants : variants, standard : standard },
					{ name : 'section-col', label : COMPONENT_LABEL['section-col'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' },
				] },
			header : { label : GROUP_LABEL.header, from : header.from ?? 'standard', theme : '', version : '', origin : 'standard', package : '', problem : '', why : null, missing : false, offers : [ offers[0] ], knobs : [], components : [ { name : 'header-wrap', label : COMPONENT_LABEL['header-wrap'], variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' } ] },
			footer : plain( 'footer', 'footer-wrap', [ offers[0] ] ),
			brand : plain( 'brand', 'brand-wrap', [] ),
			settings : plain( 'settings', 'settings-wrap', [] ),
			css : plain( 'css', 'css-wrap', [] ),
			groups : plain( 'groups', 'groups-wrap', [] ),
			fonts : plain( 'fonts', 'fonts-wrap', [] ),
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
	return { label : GROUP_LABEL[name], from : 'standard', theme : '', version : '', origin : 'standard', package : '', problem : '', why : null, missing : false, offers : offers, knobs : [], components : [ { name : component, label : COMPONENT_LABEL[component] ?? { de_DE : component, en_US : component }, variants : [ { name : 'regular', label : null, source : 'schema' } ], standard : 'regular' } ] };
}

function listAnswer( active, extra, pages, demos, copies ) {

	return Object.assign( {
		themes : [
			{ key : 'nino', kind : 'theme', origin : 'shipped', version : '1.0.0', name : { de_DE : 'Nino', en_US : 'Nino' }, description : { de_DE : 'Ruhig und klar.', en_US : 'Calm and clear.' }, brand : {}, parts : { section : { ok : true, problem : '', why : null } }, active : active === 'nino' },
			{ key : 'craft', kind : 'theme', origin : 'uploaded', frames : [ { key : 'craft-foot', name : { de_DE : 'Fuß <b>x</b>', en_US : 'Foot <b>x</b>' }, reads : [ { key : '/project/company/general/name', text : 'Nino <b>GmbH</b>', logo : false }, { key : '/logo', text : null, logo : true } ] } ], css : { lines : 4, why : null }, version : '1.0.0', name : { de_DE : 'Craft', en_US : 'Craft' }, description : { de_DE : 'Warm.', en_US : 'Warm.' }, brand : {}, parts : { section : { ok : true, problem : '', why : null } }, active : active === 'craft' },
			{ key : 'rest', kind : 'theme', origin : 'catalogue', css : { lines : 0, why : { code : 'theme-css', params : [ 'line 3 has an element selector' ] } }, version : '1.0.0', name : { de_DE : '<b>Ruhe</b>', en_US : '<b>Rest</b>' }, description : { de_DE : 'Bricht.', en_US : 'Breaks.' }, brand : {}, parts : { button : { ok : false, problem : 'English detail of the css', why : CSS_WHY }, section : { ok : true, problem : '', why : null } }, active : false },
			{ key : 'own:lindner', kind : 'own', name : 'Lindner', from : { page : 'nino@1.0.0', section : 'craft@1.0.0', header : 'standard' }, active : active === 'own:lindner' },
		].concat( ( copies ?? [] ).map( function( copy ) { return { key : copy.key, kind : 'own', name : copy.name, from : { page : 'nino@1.0.0', section : 'nino@1.0.0', header : 'standard' }, active : false } } ) ),
		rejected : [],
		groups : [ 'brand', 'footer', 'section', 'settings', 'css', 'groups', 'header' ].map( function( key ) { return { key : key, label : GROUP_LABEL[key], feature : 'basis', components : [], demo : ( demos ?? [] ).indexOf( key ) !== -1 } } ),
		fonts : FONTS, limits : { css : 65536, name : 60, key : 40 }, state : 'state-1', pages : ( pages ?? [ { path : '/', title : 'Start' }, { path : '/contact', title : '' } ] ).map( function( page ) { return typeof page === 'string' ? { path : page, title : '' } : page } ),
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

	options = Object.assign( { lang : 'en-US', active : '', modal : true, room : 600, pages : undefined, demos : [], chosen : '/' }, options ?? {} );

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
		slug : kernelSlug,
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

	// What the browser would tell of the boxes that come on screen: the boxes that are watched, and a test says which one came
	const watched = [];

	function Watcher( callback ) {
		this.callback = callback;
		this.observe = function( node ) { if( watched.some( function( one ) { return one.node === node } ) === false ) watched.push( { node : node, watcher : this } ) };
		this.unobserve = function( node ) { const at = watched.findIndex( function( one ) { return one.node === node } ); if( at !== -1 ) watched.splice( at, 1 ) };
		this.disconnect = function() { const mine = watched.filter( function( one ) { return one.watcher === this }, this ); mine.forEach( function( one ) { watched.splice( watched.indexOf( one ), 1 ) } ) };
	}

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, TextEncoder : TextEncoder };

	sandbox.window = {
		Nino : Nino,
		IntersectionObserver : Watcher,
		location : { hash : '', href : 'http://workbench.test/_admin/' },
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
					const listed = listAnswer( w.active, serving.listExtra, options.pages, options.demos, w.copies );

					listed.themes = listed.themes.filter( function( theme ) { return w.deleted.indexOf( theme.key ) === -1 } );
					listed.themes.forEach( function( theme ) { if( theme.kind === 'own' ) theme.stamp = w.stamp } );
					listed.last = w.last;
					listed.state = w.stateStamp;
					listed.canRestore = w.canRestore;
					w.answer( 200, listed );
				}
				else if( request.action === 'themes/open' )
					w.answer( 200, Object.assign( { theme : { key : payload.theme, kind : payload.theme.indexOf('own:') === 0 ? 'own' : 'theme', name : payload.theme.indexOf('own:') === 0 ? ( w.copies.find( function( copy ) { return copy.key === payload.theme } )?.name ?? 'Lindner' ) : { de_DE : { rest : 'Ruhe', craft : 'Craft' }[payload.theme] ?? 'Nino', en_US : { rest : 'Rest', craft : 'Craft' }[payload.theme] ?? 'Nino' } }, mix : mixOf( payload.theme ), stamp : payload.theme.indexOf('own:') === 0 ? w.stamp : '', brandStamp : w.brandStamp, state : w.stateStamp, canRestore : w.canRestore, last : w.last, active : w.activeState() }, describe( mixOf( payload.theme ), w.rest === 'gone' || w.rest === 'adjust' ? w.rest : false, w.brandStored ) ) );
				else if( request.action === 'themes/preview' )
					w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900 }, describe( payload.mix, w.rest, payload.brand ) ) );
				else if( request.action === 'themes/duplicate' && payload.theme.indexOf('own:') === 0 && payload.base !== w.stamp )
					w.answer( 409, { error : 'the theme was changed since it was opened', code : 'themes_changed', params : [ 'Lindner' ], field : 'theme' } );
				else if( request.action === 'themes/duplicate' && payload.name === 'Taken' )
					w.answer( 409, { error : 'an own theme of that name is there', code : 'themes_name_taken', params : [ 'Taken Before' ], field : 'name' } );
				else if( request.action === 'themes/duplicate' ) {
					const made = { key : 'own:'+ payload.name.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' ), name : payload.name };
					w.copies.push( made );
					w.bump();
					w.answer( 200, { saved : true, theme : made.key, applied : false, stamp : w.stamp, brandStamp : w.brandStamp, state : w.stateStamp, canRestore : w.canRestore, active : w.activeState() } );
				}
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
		copies : [],
		restored : 0,
		bump : function() { w.stateStamp = 'state-'+ ( Number( w.stateStamp.slice( 6 ) ) + 1 ); w.canRestore = true },
		rest : false,
		sent : [],
		activeState : function() { return w.active === '' ? null : { theme : w.active, built : '', stale : false } },

		/** The boxes that wait to come on screen, and the way a test brings one of them */
		watched : watched,
		onScreen : function( node ) {
			const one = watched.find( function( entry ) { return entry.node === node } );
			if( one !== undefined )
				one.watcher.callback( [ { target : node, isIntersecting : true } ] );
		},

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

			// The page a test looks at is chosen, as a person who wants the component outlined chooses it: "Automatic" is the demo, or the start page
			const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

			if( options.chosen !== '' && select !== undefined && select.value === '' ) {
				select.value = options.chosen;
				fire( select, 'change' );
			}
		},

		byClass : function( root, cls ) { return findAll( root, function( node ) { return String( node.className ).split(/\s+/).indexOf( cls ) !== -1 } ) },
		byTag : function( root, tag ) { return findAll( root, function( node ) { return node.tagName === tag.toUpperCase() } ) },
		text : function( node ) { return node === undefined || node === null ? '' : node.textContent + node.children.map( w.text ).join('') },
		button : function( root, label ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' && w.text( node ).trim().indexOf( label ) === 0 } )[0] },
		hint : function( labelledby ) { const label = findAll( w.theme, function( node ) { return node.id === labelledby } )[0]; return label === undefined ? null : w.text( w.byClass( label, 'themes-value' )[0] ) },
		step : function( labelledby, index ) { const group = findAll( w.theme, function( node ) { return node.attributes['aria-labelledby'] === labelledby } )[0]; return group === undefined ? undefined : group.children[index] },
		frame : function() { return w.byTag( w.theme, 'iframe' )[0] },
		bar : function() { return w.byClass( w.theme, 'nino-admin-savebar' )[0] },
		state_ : function() { return w.text( w.byClass( w.theme, 'nino-admin-savebar-state' )[0] ).trim() },
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
	check( 'B. ...and the list has nothing in the dirty registry: the entry is the bar of a screen that is open', w.dirty.entries.themes === undefined );

	w.serve( { active : 'nino' } );
	w.active = 'nino';
	w.panel.reload();
	w.serve();

	const cards = w.byTag( w.list, 'article' );
	const names = cards.map( function( card ) { return w.text( w.byClass( card, 'themes-card-name' )[0] ) } );

	check( 'B. a card for each theme: the one the website shows first, then the ones of a package by name, then the own ones', names.join() === 'Nino,<b>Rest</b>,Craft,Lindner', names.join() );
	check( 'B. a name is text and never markup: a theme called <b>Rest</b> draws no element of its own', w.byTag( w.list, 'b' ).length === 0 && names.indexOf('<b>Rest</b>') !== -1 );

	const card = function( key ) { return cards.find( function( one ) { return one.dataset.theme === key } ) };

	check( 'B. the active theme says so, and has no button to apply it', w.text( card('nino') ).indexOf( words.en['/_admin/themes/chip/active'] ) !== -1 && w.byClass( card('nino'), 'nino-admin-btn--small' ).filter( function( b ) { return b.dataset.apply !== undefined } ).length === 0 );
	check( 'B. the others have a button to apply them, and every card has the link that edits it', [ 'craft', 'rest', 'own:lindner' ].every( function( key ) { return findAll( card( key ), function( b ) { return b.dataset.apply === key } ).length === 1 && findAll( card( key ), function( b ) { return b.dataset.edit === key } ).length === 1 } ) );
	check( 'B. a theme with a part that rests says how many, and why', w.text( card('rest') ).indexOf( Wp( 'why/css' ) ) !== -1 && w.text( card('rest') ).indexOf( 'English detail' ) === -1 && w.text( card('rest') ).indexOf( 'Resting parts: 1' ) !== -1 && w.text( card('craft') ).indexOf('Resting') === -1 );
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

	check( 'B. in German the words are the German ones, and so is the name that has a German one', w.text( craft ).indexOf( words.de['/_admin/themes/card/apply'] ) !== -1 && w.text( w.byClass( cards.find( function( card ) { return card.dataset.theme === 'rest' } ), 'themes-card-name' )[0] ) === '<b>Ruhe</b>' );
}

// --- B2. a theme that was uploaded -----------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const cards = w.byTag( w.list, 'article' );
	const card = function( key ) { return cards.find( function( one ) { return one.dataset.theme === key } ) };
	const removeOf = function( key ) { return findAll( card( key ), function( b ) { return b.dataset.remove === key } ) };
	const upload = findAll( w.list, function( node ) { return node.id === 'themes-upload' } );

	check( 'B. the list has the button that chooses a theme package, and each card says where its theme comes from - the feature, the catalogue, an upload - in a chip',
		upload.length === 1 && w.text( upload[0] ) === W('label/upload') && w.text( card('nino') ).indexOf( W('chip/shipped') ) !== -1 && w.text( card('rest') ).indexOf( W('chip/catalogue') ) !== -1 && w.text( card('craft') ).indexOf( W('chip/uploaded') ) !== -1 );
	check( 'B. only the card of a theme that was uploaded has a button to remove it: the theme of the feature and the one of the catalogue are taken away where they were put, and an own theme has its Delete',
		removeOf('craft').length === 1 && removeOf('nino').length === 0 && removeOf('rest').length === 0 && removeOf('own:lindner').length === 0 );

	w.state.confirm = true;
	fire( removeOf('craft')[0], 'click' );

	const asked = w.dialogs.length === 1 && w.dialogs[0].open === true && w.requests.length === 0;

	pressed( w, CANCEL );

	const nothing = w.requests.length === 0;

	fire( removeOf('craft')[0], 'click' );
	pressed( w, W('label/remove') );

	const sent = w.requests.map( function( request ) { return request.action+ ' '+ JSON.stringify( request.payload ) } );

	check( 'B. the removal is asked first, as a thing that cannot be taken back: the answer no sends nothing, and yes sends the key of the theme and nothing else', asked === true && nothing === true && sent.join() === 'themes/remove {"theme":"craft"}', sent.join() );

	w.answer( 409, { error : 'the theme of the website is not removed', code : 'themes_upload_in_use_active', params : [ 'Craft' ], field : 'theme' } );

	check( 'B. a removal that the server refuses is said in the line of the list in the words of the panel for its code, and the card is still there',
		w.text( w.byClass( w.list, 'themes-say' )[0] ) === Wp( 'api/themes_upload_in_use_active', 'Craft' ) && w.byTag( w.list, 'article' ).some( function( one ) { return one.dataset.theme === 'craft' } ), w.text( w.byClass( w.list, 'themes-say' )[0] ) );
}

{
	// where a theme was removed, the focus is on the button that chooses a package
	const gone = world( { active : 'nino' } );

	gone.at( '#themes' );
	fire( findAll( gone.list, function( node ) { return node.dataset.remove === 'craft' } )[0], 'click' );
	pressed( gone, W('label/remove') );
	gone.answer( 200, { removed : true } );
	gone.serve();

	check( 'B. where a theme was removed the focus is on the button that chooses a package: the card that had the button is gone',
		findAll( gone.list, function( node ) { return node.id === 'themes-upload' } )[0].focused === true );
}

// --- B3. the dialogs of a theme package -------------------------------------------------------------------------------

/** The package as the preview of the server tells it: a theme that is installed in a lower version, two blocks - one comes in, one stays outside - and a licence that holds markup */
function previewOfTheme( change ) {

	return { id : 'a'.repeat( 32 ), seconds : 900, theme : Object.assign( {
		key : 'second', version : '1.3.0', name : 'Second <b>look</b>', description : 'Warm.', picture : '', parts : [ { group : 'section', ok : true, why : null } ],
		blocks : [ { key : 'in-one', name : 'One', category : 'content', frame : false, state : 'in', origin : '', theme : '', reads : [] }, { key : 'out-one', name : 'Two', category : 'content', frame : false, state : 'out', origin : 'Basis', theme : '', reads : [] } ],
		frames : 0, fonts : [], css : { lines : 0, text : '' }, licence : 'Free <b>text</b>\n', installed : { origin : 'uploaded', version : '1.2.0', replace : true }, refused : [], canInstall : true,
	}, change ?? {} ) };
}

/** The dialog that chooses the file, a file chosen and checked, and the answer of the server: the preview is the dialog that is open */
function previewDialog( w, preview ) {

	fire( findAll( w.list, function( node ) { return node.id === 'themes-upload' } )[0], 'click' );

	const input = findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } )[0];

	input.files = [ { name : 'second.tar.gz', size : 2048 } ];
	fire( input, 'change' );
	pressed( w, W('upload/check') );
	w.answer( 200, preview );

	return w.dialogs[w.dialogs.length - 1];
}

{
	// the preview: the words of the package are text, each block says what the kernel does with it, the licence is a field that is read and not changed
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const dialog = previewDialog( w, previewOfTheme() );
	const licence = findAll( dialog, function( node ) { return node.tagName === 'TEXTAREA' } )[0];
	const toggle = findAll( dialog, function( node ) { return node.id === 'themes-upload-licence-toggle' } )[0];

	check( 'B. the preview of a package says what the kernel does with each block - the one comes into the library, the other stays outside and names the origin that has its key - and a name is text and never markup',
		w.text( dialog ).indexOf( Wp( 'upload/block-in', 'One' ) ) !== -1 && w.text( dialog ).indexOf( Wp( 'upload/block-out', 'Two', 'Basis' ) ) !== -1 && w.byTag( dialog, 'b' ).length === 0 && w.text( dialog ).indexOf( 'Second <b>look</b>' ) !== -1 );
	check( 'B. the licence is a field that is read and not changed, whatever it holds, and it has the name of the button that shows it',
		licence !== undefined && licence.readOnly === true && licence.value === 'Free <b>text</b>\n' && toggle !== undefined && licence.getAttribute('aria-labelledby') === toggle.id && toggle.getAttribute('aria-expanded') === 'false' );

	check( 'B. the check sends the file and the language and nothing else: no key of the package, no choice',
		JSON.stringify( w.sent.filter( function( request ) { return request.action === 'themes/upload-check' } ).map( function( request ) { return request.payload } ) ) === '[{"locale":""}]' );

	pressed( w, CANCEL );

	check( 'B. a preview that is left - Cancel - lets go of the bytes that are held for it: upload-drop with the key of the preview, and nothing is written',
		w.requests.map( function( request ) { return request.action+ ' '+ request.payload.id } ).join() === 'themes/upload-drop '+ 'a'.repeat( 32 ) );
}

{
	// the box: a theme that was uploaded is replaced only with the tick, and the request says which it is
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	previewDialog( w, previewOfTheme() );
	pressed( w, W('upload/submit-replace') );

	const ask = w.text( w.dialogs[w.dialogs.length - 1] ).indexOf( W('upload/replace-ask') ) !== -1;
	const none = w.requests.length === 0;

	const box = findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' && node.type === 'checkbox' } )[0];

	box.checked = true;
	fire( box, 'change' );
	pressed( w, W('upload/submit-replace') );

	check( 'B. Replace without the tick sends nothing and says at the box what to do - and with the tick the request is upload with the key and replace as true, and nothing of the package',
		ask === true && none === true && w.requests.map( function( request ) { return request.action+ ' '+ JSON.stringify( request.payload ) } ).join() === 'themes/upload {"id":"'+ 'a'.repeat( 32 )+ '","replace":true,"locale":""}', w.requests.map( function( request ) { return request.action+ ' '+ JSON.stringify( request.payload ) } ).join() );
}

{
	// a package that is new: Install sends replace as false; a package that may not be put in has no button for it, and says why
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	previewDialog( w, previewOfTheme( { installed : null } ) );
	pressed( w, W('upload/submit') );

	const sent = w.requests.map( function( request ) { return request.action+ ' '+ request.payload.replace } ).join();
	const refusing = world( { active : 'nino' } );

	refusing.at( '#themes' );

	const dialog = previewDialog( refusing, previewOfTheme( { canInstall : false, refused : [ { code : 'key_reserved', params : [ 'second' ] } ], installed : null } ) );

	check( 'B. a new package is put in with replace as false; a package that may not be put in - its key is one that is reserved - has no button to install it and says why, in the sentence of the refusal',
		sent === 'themes/upload false' && findAll( dialog, function( node ) { return node.tagName === 'BUTTON' && node.textContent.trim() === W('upload/submit') } ).length === 0 && refusing.text( dialog ).indexOf( Wp( 'api/themes_upload_key_reserved', 'second' ) ) !== -1 );
}

{
	// the css a package brings, and the keys its frames read: text, never markup
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const css = '.a { color: red }\n/* </style><b>x</b> */\n.b { color: blue }';
	const dialog = previewDialog( w, previewOfTheme( { css : { lines : 3, text : css }, blocks : [ { key : 'foot', name : 'Foot', category : 'frame', frame : true, state : 'in', origin : '', theme : '', reads : [ { key : '/project/company/general/name', text : 'Nino <b>GmbH</b>', logo : false }, { key : '<b>x</b>', text : null, logo : false }, { key : '/logo', text : null, logo : true } ] } ], frames : 1 } ) );
	const toggle = findAll( dialog, function( node ) { return node.id === 'themes-upload-css-toggle' } )[0];
	const field = findAll( dialog, function( node ) { return node.tagName === 'TEXTAREA' && node.getAttribute('aria-labelledby') === 'themes-upload-css-toggle' } )[0];
	const keys = findAll( dialog, function( node ) { return node.tagName === 'CODE' } ).map( function( node ) { return node.textContent } );

	check( 'B. the preview says that the package brings css and how many lines, and shows it as text in a field that is read and not changed, behind a button, as the licence is',
		w.text( dialog ).indexOf( Wp( 'upload/css', 3 ) ) !== -1 && toggle !== undefined && field !== undefined && field.readOnly === true && field.value === css && toggle.getAttribute('aria-expanded') === 'false' );
	check( 'B. each frame of the package names the keys it reads, as a list of text - a key that is markup draws no element - with the sentence behind the (?) of what that means',
		keys.join() === '/project/company/general/name,<b>x</b>,/logo' && w.text( dialog ).indexOf( 'Nino <b>GmbH</b>' ) !== -1 && w.text( dialog ).indexOf( W('upload/reads-none') ) !== -1 && w.text( dialog ).indexOf( W('upload/reads-logo') ) !== -1 && w.byTag( dialog, 'b' ).length === 0 && w.text( dialog ).indexOf( Wp( 'upload/reads', 'Foot' ) ) !== -1 && w.text( dialog ).indexOf( W('upload/reads-help') ) !== -1 );
}

{
	// the card: the frames that read texts, quietly behind a button, as text; the css that was refused, with its reason
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const card = function( key ) { return w.byTag( w.list, 'article' ).find( function( one ) { return one.dataset.theme === key } ) };
	const toggle = findAll( card('craft'), function( node ) { return node.dataset.reads === 'craft' } )[0];
	const keys = findAll( card('craft'), function( node ) { return node.tagName === 'CODE' } ).map( function( node ) { return node.textContent } );

	check( 'B. the card of a theme with frames that read texts has a button that shows the keys as text - closed at first - and a theme without has none',
		toggle !== undefined && toggle.getAttribute('aria-expanded') === 'false' && keys.join() === '/project/company/general/name,/logo' && w.text( card('craft') ).indexOf( 'Nino <b>GmbH</b>' ) !== -1 && w.byTag( card('craft'), 'b' ).length === 0 && findAll( card('nino'), function( node ) { return node.dataset.reads !== undefined } ).length === 0 );
	check( 'B. a css of a theme that the rule refused is named on the card with the reason, in the words of the panel, and the card is there all the same',
		w.text( card('rest') ).indexOf( Wp( 'msg/card-css', Wp( 'why/theme-css', 'line 3 has an element selector' ) ) ) !== -1 );
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
	check( 'C. the state is Saved, the website shows another theme: nothing to reset or save, the theme can be applied', w.state_() === SAVED && w.bar().querySelectorAll('button').every( function( b ) { return b.textContent === W('label/apply') ? b.disabled === false && b.hidden === false : ( b.disabled === true || b.hidden === true ) } ) );
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
	check( 'D. the label of a knob says what the ladder gives at the step when that is a plain length - in pixels, as it is measured, so that a length in rem reads the same whether the preview is there or not', w.hint( 'themes-section-section-wrap-space' ) === '16px', w.hint( 'themes-section-section-wrap-space' ) );

	fire( space.children[0], 'click' );
	check( 'D. ...and nothing where the step is a calculation', w.hint( 'themes-section-section-wrap-space' ) === '', w.hint( 'themes-section-section-wrap-space' ) );
	fire( space.children[2], 'click' );
	w.tick();
	w.serve();

	// the page: no components, its knobs in sections of their own
	w.state.hash = '#themes/nino/page/type';
	w.panel.showCurrent();
	const pageHeads = w.byClass( w.theme, 'nino-admin-accordion-button' ).map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } );

	check( 'D. the page: its knobs in the sections Type and Motion - the knobs it has - and no mark in the preview', pageHeads.join() === 'Type,Motion' && w.frame().attributes.src.indexOf('mark=') === -1 && w.text( w.byClass( w.theme, 'themes-marked' )[0] ) === 'whole page', pageHeads.join() );
	check( 'D. the label of a knob of the page says what its ladder gives where that is one token with a plain value', w.hint( 'themes-page-size' ) === '100%', w.hint( 'themes-page-size' ) );

	// back to the groups, and the focus goes to the row that was left
	w.state.hash = '#themes/nino/section';
	w.panel.showCurrent();
	const backs = w.byClass( w.theme, 'nino-admin-back-link' );

	check( 'D. the way from a group to the groups is the workbench\'s back link that says it keeps the draft - the shell asks Save, Discard or Cancel before the other one, the way to the list, is followed', backs.length === 2 && backs[0].id === 'themes-back-list' && backs[0].attributes['data-keep-draft'] === undefined && backs[1].id === 'themes-back-groups' && backs[1].attributes['data-keep-draft'] === '' && w.byClass( w.theme, 'themes-back' ).length === 0 );

	fire( backs[1], 'click' );


	check( 'D. the way back leads to the groups, writes the address of the theme and puts the focus on the row of the group that was left', w.state.hash === '#themes/nino/groups' && w.byClass( w.theme, 'themes-groups' ).length === 1 && w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0].focused === true, w.state.hash );
	fire( w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0], 'click' );
	check( 'D. a group opened by hand puts the focus on the way back of its settings', w.state.hash === '#themes/nino/section/section-wrap' && w.byClass( w.theme, 'nino-admin-back-link' )[1].focused === true, w.state.hash );
}

// --- E. a change --------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const wrapBefore = w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent;
	const spaceStep = w.step( 'themes-section-section-wrap-space', 4 );

	fire( spaceStep, 'click' );

	check( 'E. a step chosen is a change of the working mix, said at once: the state, the head of the section, the buttons of the bar', w.state_() === CHANGED && w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent !== wrapBefore
		&& w.byClass( w.theme, 'nino-admin-accordion-badge' )[0].textContent === words.en['/_admin/themes/state/changed-mark'] && w.button( w.bar(), W('label/reset') ).disabled === false && w.button( w.bar(), W('label/save-as') ).disabled === false );
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

	check( 'E. a step back to where the saved state has it makes no change: the state is Saved, the head says nothing, the draft is dropped from the bar', w.state_() === APPLIED && w.byClass( w.theme, 'nino-admin-accordion-badge' )[0].hidden === true && w.button( w.bar(), W('label/reset') ).disabled === true );
	w.tick();
	w.serve();

	// a variant
	fire( w.step( 'themes-section-section-wrap-variant', 1 ), 'click' );
	w.tick();
	check( 'E. the standard variant chosen is in the mix for the component and in the draft', w.requests[0].payload.mix.parts.section.standard['section-wrap'] === 'band' && w.byClass( w.theme, 'nino-admin-accordion-summary' )[0].textContent === 'Band · 1 knob changed' || w.requests[0].payload.mix.parts.section.standard['section-wrap'] === 'band', JSON.stringify( w.requests[0].payload.mix.parts.section ) );
	w.serve();
	fire( w.step( 'themes-section-section-wrap-variant', 0 ), 'click' );
	check( 'E. ...and the saved variant chosen again leaves nothing of it in the mix', w.state_() === APPLIED );
	w.tick();
	w.serve();

	// a draft that cannot be made
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.answer( 500, { error : 'the preview could not be written' } );
	const line = w.byClass( w.theme, 'nino-admin-status' )[0];

	check( 'E. a draft that cannot be made is said in the bar, with what the server said, and the change stays: the step is chosen, the state is Changed', line.dataset.state === 'error' && line.textContent.indexOf('the preview could not be written') !== -1 && w.state_() === CHANGED
		&& w.step( 'themes-section-section-wrap-space', 3 ).attributes['aria-checked'] === 'true' );

	// a mix the server refused says which part was wrong, in the word of the panel for the group - and not the English sentence the log has
	fire( w.step( 'themes-section-section-wrap-space', 2 ), 'click' );
	w.tick();
	w.answer( 400, { error : 'the mix is refused: section.values: a knob is <component>.<name>, or a name for the page', code : 'themes_mix', params : [ 'section.values: a knob is <component>.<name>, or a name for the page', 'section' ], field : 'mix' } );

	check( 'E. a refused mix says which part was wrong, with the word of the group in the language of the panel, and not the sentence of the log', line.dataset.state === 'error' && line.textContent === Wp( 'api/themes_mix_part', GROUP_LABEL.section.en_US ) && line.textContent.indexOf('knob is') === -1 );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.answer( 400, { error : 'the mix is refused: mix: a mix is a name, a css and parts', code : 'themes_mix', params : [ 'mix: a mix is a name, a css and parts', '' ], field : 'mix' } );

	check( 'E. a mix that is wrong as a whole, or at a group the list does not have, has the sentence that names no part', line.textContent === Wp( 'api/themes_mix' ) );

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

	check( 'E. a page that is loaded again replaces what the frame shows and does not set its address: no step is added to the history, which Back would have to walk over before it came to the address of the panel', replaced.length === 1 && replaced[0] === 'http://workbench.test/site/?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && w.frame().attributes.src === source, replaced.join() );
}

// the tools over the preview
{
	const w = world( { active : 'nino', chosen : '' } );

	w.at( '#themes/nino/section/section-wrap' );

	const pages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

	check( 'E. the pages of the site the preview may show are a choice - "Automatic" first, and chosen - and the pages the list names', pages !== undefined && pages.children.map( function( option ) { return option.value } ).join() === ',/,/contact' && pages.value === '' && w.text( pages.children[0] ) === W('label/page-auto') );

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

	check( 'F. another part is asked for at once, at the steps it is delivered at - those it was set to are replaced - and the other groups as they were', w.requests.length === 1 && w.requests[0].payload.mix.parts.section.from === 'craft@1.0.0' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === -1 && w.requests[0].payload.mix.parts.header.from === 'standard' && w.timers.filter( function( timer ) { return timer.fn !== null && timer.ms === 150 } ).length === 0 );

	w.serve();

	const labels = w.byClass( w.theme, 'nino-admin-field-label' ).map( function( label ) { return w.text( label ) } );

	check( 'F. the screen of the group is drawn from the answer: the knob stands at the step the part is delivered at, -1, and the mark of the default is on that step and not on 0 - the middle of the scale is the middle', w.step( 'themes-section-section-wrap-space', 1 ).attributes['aria-checked'] === 'true' && w.step( 'themes-section-section-wrap-space', 1 ).dataset.standard === 'true' && w.step( 'themes-section-section-wrap-space', 2 ).dataset.standard === undefined && w.step( 'themes-section-section-wrap-space', 4 ).attributes['aria-checked'] !== 'true', labels.join() );
	check( 'F. the steps that were set for the part that was there are said to be replaced, where the bar says what happened - and the knob of the same name that was not set says nothing', w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf( Wp( 'msg/part-steps', 'Section' ) ) !== -1 );
	check( 'F. the part brings its own variant and the standard variant of the part stands', w.step( 'themes-section-section-wrap-variant', 1 ) !== undefined && w.text( w.step( 'themes-section-section-wrap-variant', 1 ) ) === 'Soft surface' && w.step( 'themes-section-section-wrap-variant', 1 ).attributes['aria-checked'] === 'true' );

	const heads = w.byClass( w.theme, 'nino-admin-accordion-button' ).map( function( head ) { return w.text( w.byClass( head, 'nino-admin-accordion-label' )[0] ) } );

	check( 'F. the knob of the other part is there and the knob of this one is not: Shadow for the column, no Rule for the title', heads.indexOf('Column') === 0 + heads.indexOf('Column') && w.byClass( w.theme, 'nino-admin-accordion-summary' ).map( function( s ) { return s.textContent } ).join('|').indexOf('1 knob') !== -1 && labels.indexOf('Rule') === -1 );
	check( 'F. the part of the group is changed, the bar says Changed, and the head of the open section says that this section is not to be compared with the saved one', w.state_() === CHANGED );
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

	check( 'F. the part is asked for at the steps it is delivered at: the step that was set for a knob the new part has not goes with the old part', w.requests[0].payload.mix.parts.section.values['section-title.x-rule'] === undefined && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === -1 );

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

	check( 'F. a group whose part rests says why, in the words of the rule, and that the standard part stands', w.byClass( w.theme, 'nino-admin-notice' ).length === 1 && w.text( w.byClass( w.theme, 'nino-admin-notice' )[0] ).indexOf( Wp( 'why/group-version', 'basis', '^9.0' ) ) !== -1 && w.text( w.byClass( w.theme, 'nino-admin-notice' )[0] ).indexOf( 'English detail' ) === -1 );

	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();

	check( 'F. ...and so does its row in the list of the groups', w.text( w.byClass( w.theme, 'themes-groups' )[0].children[3] ).indexOf('basis ^9.0') !== -1 );
}

// --- G. the bar -----------------------------------------------------------------------------------------------------------

{
	// a theme of a package: saved under a name
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );

	check( 'G. a theme of a package that is not the active one: the bar offers to apply it', w.button( w.bar(), W('label/apply') ).hidden === false && w.state_() === SAVED );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	check( 'G. changed, it cannot be applied until it is saved, and the button to save says it makes an own theme', w.button( w.bar(), W('label/apply') ).disabled === true && w.button( w.bar(), W('label/save-as') ) !== undefined );

	fire( w.button( w.bar(), W('label/save-as') ), 'click' );

	const dialog = w.dialogs[w.dialogs.length - 1] ?? element('dialog');
	const input = findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0] ?? element('input');

	check( 'G. the name is asked in a dialog with a field, and nothing is sent yet', dialog.open === true && input.value === 'Craft 2' && w.requests.length === 0, input.value );

	check( 'G. the dialog is the form dialog of the workbench - its size is a class of the kernel - and its answers stand as every dialog\'s do: Cancel, then the one that goes on, which is the primary one', dialog.className.indexOf('nino-admin-dialog--form') !== -1 && kindsOf( dialog ) === 'secondary,primary' && findAll( dialog, function( node ) { return node.tagName === 'BUTTON' } )[0].textContent === CANCEL );

	input.value = 'Lindner';
	fire( goOn( dialog ), 'click' );

	check( 'G. the name goes with the mix to save-as - the name in the mix, the steps, the part - and the theme of the package is not touched', w.requests.length === 1 && w.requests[0].action === 'themes/save-as' && w.requests[0].payload.mix.name === 'Lindner' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 2 && w.requests[0].payload.force === false );

	w.serve();

	check( 'G. what is saved is opened again: the address is the own theme, the state is Saved and the website still shows the theme it showed', w.state.hash === '#themes/own.lindner/section/section-wrap' && w.state_() === SAVED && w.active === 'nino' );
	check( 'G. ...and the list was asked again before the theme was opened, to know the new theme', w.actions.slice( w.actions.indexOf('themes/save-as') ).join() === 'themes/save-as,themes/list,themes/open,themes/preview', w.actions.join() );
}

{
	// a name that is taken is asked again
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save-as') ), 'click' );
	findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	fire( goOn( w.dialogs[w.dialogs.length - 1] ), 'click' );
	w.answer( 409, { error : 'an own theme of that name is there', code : 'themes_name_taken', params : [ 'Lindner' ], field : 'name' } );

	const second = w.dialogs[w.dialogs.length - 1];

	check( 'G. a name that is taken is asked again, in a dialog that says what was wrong and holds what was typed', w.dialogs.length === 2 && second.open === true && findAll( second, function( node ) { return node.tagName === 'INPUT' } )[0].value === 'Lindner' && w.text( second ).indexOf('Lindner') !== -1 );

	fire( findAll( second, function( node ) { return node.tagName === 'BUTTON' && node.textContent === CANCEL } )[0], 'click' );

	check( 'G. a name that is not given saves nothing, and the draft stays', w.requests.length === 0 && w.state_() === CHANGED );
}

{
	// an own theme: written as it stands, asked about where it is the website's
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );

	check( 'G. an own theme that is the website\'s: the state is Saved and active, there is nothing to apply, the button says Save', w.state_() === APPLIED && w.button( w.bar(), W('label/apply') ).hidden === true && w.button( w.bar(), W('label/save') ) !== undefined );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'G. saving what the website shows asks first, because visitors see it at once - and No saves nothing', w.dialogs.length === 1 && w.dialogs[0].open === true && w.requests.length === 0 );

	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === CANCEL } )[0], 'click' );

	check( 'G. ...the draft stays', w.requests.length === 0 && w.state_() === CHANGED );

	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[1] ), 'click' );

	check( 'G. Yes writes it: save with the key of the theme, its name in the mix and no force', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.mix.name === 'Lindner' && w.requests[0].payload.force === false );

	// the stylesheet of the project is somebody else's
	w.answer( 409, { error : 'the stylesheet of the project was not written by this feature', code : 'themes_foreign' } );

	check( 'G. a stylesheet that is not the feature\'s is asked about before it is replaced, and nothing is sent until the answer is Yes', w.dialogs.length === 3 && w.dialogs[2].open === true && w.requests.length === 0 );

	fire( goOn( w.dialogs[2] ), 'click' );

	check( 'G. ...Replace asks again with force', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.force === true );

	w.serve();

	check( 'G. what was saved is what the screen is: the state is Saved and active and the line says it was saved', w.state_() === APPLIED && w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'saved' );
}

{
	// apply, and reset
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/section/section-wrap' );
	fire( w.button( w.bar(), W('label/apply') ), 'click' );

	check( 'G. Apply asks before the website shows the theme', w.dialogs.length === 1 && w.requests.length === 0 );

	fire( findAll( w.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === W('label/apply') } )[0], 'click' );

	check( 'G. ...Yes sends apply with the key of the theme and no force', w.requests.length === 1 && w.requests[0].action === 'themes/apply' && w.requests[0].payload.theme === 'craft' && w.requests[0].payload.force === false );

	w.serve();

	check( 'G. ...and the state is Saved and active, the button is gone, the line says what the website shows', w.state_() === APPLIED && w.button( w.bar(), W('label/apply') ).hidden === true && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf('The website shows the theme "Craft"') !== -1, w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) );

	fire( w.step( 'themes-section-section-wrap-space', 1 ), 'click' );
	w.tick();
	w.serve();

	// a list that cannot be had after a change does not hold the screen up
	const y = world( { active : 'nino' } );

	y.at( '#themes/craft/section/section-wrap' );
	fire( y.button( y.bar(), W('label/apply') ), 'click' );
	fire( findAll( y.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === W('label/apply') } )[0], 'click' );
	y.serve( { fail : function( request ) { return request.action === 'themes/list' ? { status : 500, body : {} } : null } } );

	check( 'G. a list that cannot be had after the website was given a theme does not hold the screen up: the state is Saved and active, and the line says what the website shows', y.state_() === '\u2713 Saved and active' && y.text( y.byClass( y.theme, 'nino-admin-status' )[0] ).indexOf('The website shows the theme "Craft"') !== -1 );

	fire( w.button( w.bar(), W('label/reset') ), 'click' );

	check( 'G. Reset drops the draft: the working mix is the saved one, the state is Saved and active, and the preview is asked for again at once', w.state_() === APPLIED && w.requests.length === 1 && w.requests[0].action === 'themes/preview' && w.requests[0].payload.mix.parts.section.values === undefined && w.step( 'themes-section-section-wrap-space', 2 ).attributes['aria-checked'] === 'true' );
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

	check( 'H. a change is unsaved input of the panel, for the registry', w.dirty.entries.themes.isDirty() === true && w.dirty.entries.themes.bar() === w.bar() );

	// the way back to the list asks
	w.dirtyAnswers.push( 'cancel' );
	fire( w.byClass( w.theme, 'nino-admin-back-link' )[0], 'click' );

	check( 'H. the way back to the list asks first; Cancel leaves the screen and the address as they are', w.dirty.asked === 1 && w.state.hash === '#themes/nino/section/section-wrap' && w.theme.className.indexOf('admin-hidden') === -1 );

	// a move between the levels of the theme does not ask: the draft goes with it
	fire( w.byClass( w.theme, 'nino-admin-back-link' )[1], 'click' );

	check( 'H. a move from the settings of a group to the groups is no leaving: nothing is asked and the draft stays', w.dirty.asked === 1 && w.state.hash === '#themes/nino/groups' && w.dirty.entries.themes.isDirty() === true );
	w.serve();

	// the address changed by hand: the shell calls showCurrent
	w.dirtyAnswers.push( 'cancel' );
	w.state.hash = '#themes';
	w.panel.showCurrent();

	check( 'H. an address that leaves asks too; Cancel puts the address of the level that is shown back', w.dirty.asked === 2 && w.state.hash === '#themes/nino/groups' && w.theme.className.indexOf('admin-hidden') === -1, w.state.hash );

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

	// the tab is chosen once more: the shell shows the panel before it has written the address of it, and what is shown is what the address says
	check( 'H. ...and the address is the level that is shown, so that a reload comes back to it', w.state.hash === '#themes/craft' );

	// a theme that cannot be opened
	const x = world();

	x.state.hash = '#themes/nino/groups';
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
	fire( a.button( a.bar(), W('label/save-as') ), 'click' );
	findAll( a.dialogs[a.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Fresh';
	fire( goOn( a.dialogs[a.dialogs.length - 1] ), 'click' );
	a.serve();

	check( 'I. after a save as an own theme the focus is on the name of the theme - the button that was pressed is gone with the screen it stood in', hasFocus( a, name( a ) ) && name( a ).attributes.tabindex === '-1' );

	// Apply in the bar
	const b = world( { active : 'nino' } );

	b.at( '#themes/craft/section/section-wrap' );
	fire( b.button( b.bar(), W('label/apply') ), 'click' );
	fire( findAll( b.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === W('label/apply') } )[0], 'click' );
	b.serve();

	check( 'I. after Apply in the bar the focus is on the name of the theme: the button is hidden now, and a focus does not stay on what is not there', hasFocus( b, name( b ) ) );

	// Reset
	const c = world( { active : 'nino' } );

	c.at( '#themes/craft/section/section-wrap' );
	fire( c.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	c.tick();
	c.serve();
	fire( c.button( c.bar(), W('label/reset') ), 'click' );

	check( 'I. after Reset the focus is on the name of the theme: the button is without a use until the next change', hasFocus( c, name( c ) ) );
	c.serve();

	// Apply on a card of the list: the Edit of the card that was applied
	const d = world( { active : 'nino' } );

	d.at( '#themes' );
	fire( findAll( d.list, function( node ) { return node.dataset.apply === 'craft' } )[0], 'click' );
	fire( findAll( d.dialogs[0], function( node ) { return node.tagName === 'BUTTON' && node.textContent === W('label/apply') } )[0], 'click' );
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
	fire( e.button( e.bar(), W('label/reset') ), 'click' );
	e.serve();
	check( 'I. ...and after a failed Apply the focus is where it was pressed: the button is there and can be pressed again', ( function() {
		const apply = e.button( e.bar(), W('label/apply') );
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

	check( 'J. an own theme is opened by its address, own.<key>, and the API is asked for own:<key>', w.sent.filter( function( request ) { return request.action === 'themes/open' } )[0].payload.theme === 'own:lindner' && w.state.hash === '#themes/own.lindner/section/section-wrap' && w.byClass( w.theme, 'nino-admin-back-link' )[1].href === '#themes/own.lindner' );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[0] ), 'click' );

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
	w.doc.activeElement = w.byClass( w.theme, 'themes-name' )[0];
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[0] ), 'click' );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];
	const answers = findAll( question, function( node ) { return node.tagName === 'BUTTON' } );
	const kinds = answers.map( function( node ) { return /nino-admin-btn-(primary|danger|secondary)/.exec( node.className )[1] } );

	check( 'J. a theme written by another tab since it was opened is said in a question with three answers - the other way on, Cancel, and at the far right the one that overwrites, which is the dangerous one - with the sentence of the server, and the bar says it too', question.open === true && kinds.join() === 'secondary,secondary,danger' && answers[1].textContent === CANCEL && answers[2].textContent === workbench['/_admin/common/conflict/overwrite'] && w.text( question ).indexOf('The theme "Lindner" was changed since you opened it.') !== -1, w.text( question ) );

	fire( findAll( question, function( node ) { return node.textContent === CANCEL } )[0], 'click' );

	check( 'J. Cancel sends nothing and leaves the draft: the state is Changed, the step is chosen', w.requests.length === 0 && w.state_() === CHANGED && w.step( 'themes-section-section-wrap-space', 3 ).attributes['aria-checked'] === 'true' );
	check( 'J. ...and the focus is back on Save, which opened the question', w.button( w.bar(), W('label/save') ).focused === true );

	// Overwrite: the stamp of what is there now, then the same save with it
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[w.dialogs.length - 1] ), 'click' );
	w.serve();
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.textContent === workbench['/_admin/common/conflict/overwrite'] } )[0], 'click' );

	check( 'J. Overwrite asks for the theme as it is now, only to have its stamp, and draws nothing of it', w.requests.length === 1 && w.requests[0].action === 'themes/open' && w.requests[0].payload.theme === 'own:lindner' );

	w.answer( 200, Object.assign( { theme : { key : 'own:lindner', kind : 'own', name : 'Lindner' }, mix : mixOf( 'own:lindner' ), stamp : 'stamp-5', active : null }, describe( mixOf( 'own:lindner' ) ) ) );

	check( 'J. ...and then the save is sent once more with that stamp, and the draft that is saved is the person\'s', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.base === 'stamp-5' && w.requests[0].payload.mix.parts.section.values['section-wrap.space'] === 1 );

	w.serve();

	check( 'J. what was written over is the state: Saved and active, and the stamp of the next save is the one the server gave', w.state_() === APPLIED && w.dirty.entries.themes.isDirty() === false );
}

{
	// Reload: the draft is dropped after a question
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.serve();
	w.stamp = 'stamp-5';
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[0] ), 'click' );
	w.serve();
	const asked = w.dialogs.length;
	const conflict = w.dialogs[asked - 1];

	check( 'J. the question of the conflict is open and nothing is asked of the server before an answer', conflict.open === true && w.requests.length === 0 );

	fire( findAll( conflict, function( node ) { return node.textContent === workbench['/_admin/common/conflict/reload'] } )[0], 'click' );

	check( 'J. Reload is one answer: no second question follows it', w.dialogs.length === asked );

	w.serve();

	check( 'J. ...and then the theme is opened as it is saved: the draft is gone, the state is Saved and active, and the focus is on the name', w.dirty.entries.themes.isDirty() === false && w.state_() === APPLIED && w.step( 'themes-section-section-wrap-space', 4 ).attributes['aria-checked'] === 'true' && w.byClass( w.theme, 'themes-name' )[0].focused === true );
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

	check( 'K. a link that is followed in the preview does not drop the draft: the page it came to is the page that is chosen, where the list has it, and the frame is loaded with the token again', replaced.length === 1 && replaced[0] === 'http://workbench.test/site/contact?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && pages.value === '/contact', replaced.join() );
	check( 'K. the pages the list has are named by their title with the path behind it, and by the path where they have no title', pages.children.map( function( option ) { return option.textContent } ).join('|') === W('label/page-auto')+ '|Start (/)|/contact' );

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

	check( 'K. a page the list does not have is not taken for the chosen one: the page that was chosen is loaded again with the token, and the bar says that the preview cannot show it', replaced.length === 2 && replaced[1] === 'http://workbench.test/site/contact?nino-theme-preview='+ 'a'.repeat( 32 )+ '&mark=section-wrap' && pages.value === '/contact', replaced.join() );

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

	check( 'K. a value that is a calculation is measured only where the frame has loaded: while it is busy nothing is read from it', w.hint( 'themes-section-section-wrap-space' ) === '16px' && probes.length === 0 );

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

	check( 'L. what the person had set and the new part does not have is said in the bar, by name - the variant that is chosen - and so are the steps that were replaced by the ones the part is delivered at', said === 'These do not apply in this part: standard variant "Band" of Wrap. '+ Wp( 'msg/part-steps', 'Section' ), said );

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
	check( 'L. a change is said by the badge of the bar, and the line of the status is the one a screen reader hears', w.state_() === CHANGED && status.attributes.role === 'status' );

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();

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

	check( 'L. the standard variant says where it applies', w.text( w.byClass( w.theme, 'nino-admin-field-name' )[0] ).indexOf( words.en['/_admin/themes/hint/variant'] ) !== -1 );
}

{
	// the name of a theme
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const select = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.id === 'themes-part-section' } )[0];

	select.value = 'craft@1.0.0';
	fire( select, 'change' );
	w.serve();
	fire( w.button( w.bar(), W('label/save-as') ), 'click' );

	const first = w.dialogs[w.dialogs.length - 1];
	const typed = function( dialog, value ) {
		const input = findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0];
		input.value = value;
		return input;
	};

	check( 'L. the dialog says what is saved - the theme it comes from and what it is made of - in one sentence', w.text( first ).indexOf('The draft of "Nino" is saved as an own theme under a name: Page base from Nino, Section from Craft, otherwise Standard.') !== -1, w.text( first ).slice( 0, 260 ) );

	typed( first, '   ' );
	fire( goOn( first ), 'click' );

	const second = w.dialogs[w.dialogs.length - 1];
	const field = findAll( second, function( node ) { return node.tagName === 'INPUT' } )[0];

	check( 'L. a name that says nothing sends nothing: the dialog stays open, with the sentence at the field, which is marked', w.requests.length === 0 && second === first && second.open === true && field.attributes['aria-invalid'] === 'true' && w.text( second ).indexOf('needs a name') !== -1 );

	typed( second, 'Fresh' );
	// The dialog's own form takes the key, which the stand-in of the page does not carry up from the field
	fire( findAll( second, function( node ) { return node.tagName === 'FORM' } )[0], 'keydown', { key : 'Enter', target : field } );

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

	check( 'K. a link to a page of another site, which the frame cannot read, throws nothing: the page that was chosen is loaded with the token, the bar says that the preview cannot show it, and the frame is busy until it has loaded', thrown === null && replaced.length === 1 && replaced[0].indexOf('http://workbench.test/site/?nino-theme-preview=') === 0 && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === 'The preview cannot show this page.', String( thrown ) );

	fire( w.frame(), 'load' );

	check( 'K. a page that cannot be read twice in a row is not loaded over again, and the values beside the knobs are read all the same', replaced.length === 1 && w.hint( 'themes-section-section-wrap-space' ) === '56px' );
}

{
	// units: what is measured is in pixels, rem included; a share stays as it is written
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-title' );

	check( 'K. while the frame has not loaded a length in rem is the pixels of the root of the panel, and a share stays as it is written', w.hint( 'themes-section-section-title-size' ) === '19.2px' );

	w.frame().contentDocument = { body : element('body'), createElement : function( tag ) { const probe = element( tag ); probe.getBoundingClientRect = function() { return { width : 19.2, height : 1 } }; probe.remove = function() {}; return probe } };
	fire( w.frame(), 'load' );

	check( 'K. once it has loaded the value of a fixed length is the measured one, in pixels', w.hint( 'themes-section-section-title-size' ) === '19.2px', w.hint( 'themes-section-section-title-size' ) );

	w.state.hash = '#themes/nino/page/type';
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
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	fire( goOn( w.dialogs[0] ), 'click' );
	w.serve();
	fire( findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.textContent === workbench['/_admin/common/conflict/overwrite'] } )[0], 'click' );
	w.answer( 200, Object.assign( { theme : { key : 'own:lindner', kind : 'own', name : 'Lindner' }, mix : mixOf( 'own:lindner' ), stamp : 'stamp-5', active : null }, describe( mixOf( 'own:lindner' ) ) ) );

	check( 'J. Overwrite writes with the stamp that stands now and without force: it answers the stamp and says nothing about a stylesheet that somebody else wrote', w.requests.length === 1 && w.requests[0].payload.base === 'stamp-5' && w.requests[0].payload.force === false );

	w.answer( 409, { error : 'the stylesheet of the project was not written by this feature', code : 'themes_foreign' } );

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'J. ...and where the stylesheet is somebody else\'s the question about it comes, as at any save, and nothing is sent before the answer', question.open === true && w.requests.length === 0 && goOn( question ) !== undefined );

	fire( goOn( question ), 'click' );

	check( 'J. ...Replace sends it with force, and with the stamp of the Overwrite', w.requests.length === 1 && w.requests[0].payload.force === true && w.requests[0].payload.base === 'stamp-5' );
}

{
	// the browser's own steps between the levels put the focus where a step of the person's own does
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino' );
	w.state.hash = '#themes/nino/section';
	w.panel.showCurrent();

	check( 'K. a step of the history into the settings of a group puts the focus on the way back, as the click on the row does', w.byClass( w.theme, 'nino-admin-back-link' )[1].focused === true );

	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();

	check( 'K. ...and a step out of them puts it on the row of the group that was left', w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0].focused === true );

	w.state.hash = '#themes/nino/section/section-title';
	w.panel.showCurrent();
	w.byClass( w.theme, 'nino-admin-back-link' )[1].focused = false;
	w.state.hash = '#themes/nino/section/section-wrap';
	w.panel.showCurrent();

	check( 'K. a step between two sections of one group moves the focus nowhere', w.byClass( w.theme, 'nino-admin-back-link' )[1].focused === false );
}

{
	// the first address of a page is no step of the history: a page that is loaded on the settings of a group gives nothing the focus
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section' );

	check( 'K. the address a page is loaded on puts the focus nowhere - only a step between two levels does', w.byClass( w.theme, 'nino-admin-back-link' )[1].focused !== true );
}

{
	// the words of the dialog: what the website shows is named
	const none = world( { active : '' } );

	none.at( '#themes/nino/section/section-wrap' );
	fire( none.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	none.tick();
	none.serve();
	fire( none.button( none.bar(), W('label/save-as') ), 'click' );

	const on = world( { active : 'own:lindner' } );

	on.at( '#themes/nino/section/section-wrap' );
	fire( on.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	on.tick();
	on.serve();
	fire( on.button( on.bar(), W('label/save-as') ), 'click' );

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

	[ [ 'settings', '#themes/nino/settings' ], [ 'css', '#themes/nino/css' ], [ 'groups', '#themes/nino/groups' ] ].forEach( function( named ) {
		w.state.hash = '#themes/nino/'+ named[0];
		w.panel.showCurrent();
		w.serve();

		const open = w.theme.querySelectorAll('[aria-expanded="true"]');

		check( 'M. '+ named[0]+ ' is no group: the address shows the first level of the theme with that section open, and the address is the one that section has', open.length === 1 && open[0].id === 'themes-groups-'+ [ 'groups', 'settings', 'css' ].indexOf( named[0] )+ '-head' && w.state.hash === named[1], w.state.hash );
	} );

	w.state.hash = '#themes/nino/brand/colours';
	w.panel.showCurrent();
	w.serve();

	check( 'M. brand has a level of its own: the brand of the project, no group of the list - four sections, the one the address names open', w.byClass( w.theme, 'themes-groups' ).length === 0 && w.byClass( w.theme, 'nino-admin-accordion-button' ).length === 4 && w.byClass( w.theme, 'nino-admin-accordion-button' ).filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length === 1 && w.state.hash === '#themes/nino/brand/colours', w.state.hash );

	// a step of the history to an address that names no section: every section is closed, and the address stays what it is
	w.state.hash = '#themes/nino/brand';
	w.panel.showCurrent();
	w.serve();

	check( 'M. an address that names no section is a level with every section closed - what a click on the open head leaves behind - and stays the address', w.byClass( w.theme, 'nino-admin-accordion-button' ).length === 4 && w.byClass( w.theme, 'nino-admin-accordion-button' ).filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length === 0 && w.state.hash === '#themes/nino/brand', w.state.hash );

	w.state.hash = '#themes/nino/groups';
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

	check( 'O. a click on the row goes to the level of the brand: the address names the first section, the way back is there, the name of the level says that it belongs to the project, and the focus is on the way back', w.state.hash === '#themes/nino/brand/colours' && w.byClass( w.theme, 'nino-admin-back-link' ).length === 2 && w.text( w.byClass( w.theme, 'themes-title' )[0] ).indexOf('Project') !== -1 && w.byClass( w.theme, 'nino-admin-back-link' )[1].focused === true, w.state.hash );
	check( 'O. the level has four sections - colours, type, mode, logo - one is open, each head says what is in it: from the theme, where the project has set nothing', w.byClass( w.theme, 'nino-admin-accordion-button' ).length === 4 && w.byClass( w.theme, 'nino-admin-accordion-button' ).filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length === 1 && w.byClass( w.theme, 'nino-admin-accordion-summary' ).map( function( node ) { return node.textContent } ).join() === 'from the theme,from the theme,from the theme,/logo · /logo/dark' );
	check( 'O. the switch of the split view says Settings: it is a level of settings', w.byClass( w.theme, 'nino-admin-split-switch' )[0].children[0].textContent === W('label/settings') );

	const color1 = byId( w.theme, 'themes-brand-color-1' );
	const color2 = byId( w.theme, 'themes-brand-color-2' );

	check( 'O. the colours are the workbench\'s own field, with the code and the contrast of the text that is written on it, and a value that the project has not set is the theme\'s: the default of Nino.css, said so, and no way back to offer', color1.value === '#1c5db8' && color2.value === '#17233a' && w.byClass( w.theme, 'nino-admin-color-contrast' ).length === 2 && w.text( w.byClass( w.theme, 'nino-admin-color-contrast' )[0] ).indexOf('enough') !== -1
		&& byId( w.theme, 'themes-brand-back-color-1' ).hidden === true && w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/from-standard') ) !== -1 && w.text( w.byClass( w.theme, 'themes-origin' )[2] ).indexOf( Wp( 'chip/from-theme', 'Nino' ) ) !== -1, w.text( w.byClass( w.theme, 'themes-origin' )[0] ) );

	// the first colour typed is the project's own
	type( color1, '#9a4b1d' );

	check( 'O. M4: a colour is an own value from the first time it is set - the chip says so, the way back is offered with the value it goes back to, the section is marked changed and says how many own values it holds, and the draft is changed', w.text( w.byClass( w.theme, 'themes-origin' )[0] ).indexOf( W('chip/own-value') ) !== -1 && byId( w.theme, 'themes-brand-back-color-1' ).hidden === false && byId( w.theme, 'themes-brand-back-color-1' ).textContent === Wp( 'label/take-back-standard', '#1c5db8' )
		&& w.text( byId( w.theme, 'themes-acc-0-head' ) ).indexOf( W('msg/own-one') ) !== -1 && w.text( byId( w.theme, 'themes-acc-0-head' ) ).indexOf('changed') !== -1 && w.state_() === CHANGED && w.dirty.entries.themes.isDirty() === true );

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
	fire( w.button( w.bar(), W('label/reset') ), 'click' );

	check( 'O. Reset drops a brand that was changed with the rest of the draft: the field shows the saved value, nothing is the project\'s own, the state says so', byId( w.theme, 'themes-brand-color-2' ).value === '#17233a' && byId( w.theme, 'themes-brand-back-color-2' ).hidden === true && w.state_() === APPLIED && w.dirty.entries.themes.isDirty() === false );
	w.serve();

	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	fire( byId( w.theme, 'themes-brand-back-color-1' ), 'click' );

	check( 'O. M3: the first colour goes back too, the field shows the colour of the theme and the focus stands on it, and with nothing own the draft is the saved state again', byId( w.theme, 'themes-brand-color-1' ).value === '#1c5db8' && w.byClass( w.theme, 'nino-admin-savebar-state' )[0].dataset.state !== 'warn' && w.dirty.entries.themes.isDirty() === false && byId( w.theme, 'themes-brand-color-1' ).focused === true );
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

	check( 'O. the palette is every pair a part reads for the light and the dark mode - 25 with the colours on the first and on the second colour - each with its swatch, its name, the number and the word', pairs.length === 25 && w.byClass( w.theme, 'themes-roles' ).length === 2 && w.byClass( w.theme, 'themes-role' ).length === 16 && w.byClass( w.theme, 'themes-palette-mode' ).map( function( node ) { return node.textContent } ).join() === [ 'light', 'dark', 'light', 'dark' ].map( function( mode ) { return W( 'word/mode-'+ mode ) } ).join() && w.byClass( w.theme, 'themes-allpairs' )[0].open === true );
	check( 'O. the number is the one the API gave, cut to two decimals and written as the language writes it, a pair on the very limit reaches, and the pair under 4.5 : 1 says that it falls short in a word and in a mark, which the colour does not say alone', weak.length === 1 && w.text( weak[0] ).indexOf( Wp( 'msg/ratio', 4.2 ) ) !== -1 && w.text( weak[0] ).indexOf( W('word/falls-short') ) !== -1 && w.text( weak[0] ).indexOf('✕') !== -1 && w.text( pairs[0] ).indexOf( Wp( 'msg/ratio', 12.5 ) ) !== -1 && w.text( pairs[0] ).indexOf( W('word/reaches') ) !== -1 && w.text( pairs[0] ).indexOf('✓') !== -1 );
	check( 'O. the name of a pair is the text on its ground, in the words of the panel, and the dark mode has the picture under a section as a ground', w.text( pairs[0] ).indexOf( Wp( 'msg/pair', W('role/ink'), W('role/base') ) ) !== -1 && w.text( w.byClass( w.theme, 'themes-pairs' )[1] ).indexOf( Wp( 'msg/pair', W('role/accent'), W('role/picture') ) ) !== -1 && w.text( w.byClass( w.theme, 'themes-pairs' )[0] ).indexOf( W('role/picture') ) === -1 );
	check( 'O. a swatch shows the colours of its pair - the text on its ground - and is no information for a screen reader', pairs[0].children[0].style.color === '#1b1b1b' && pairs[0].children[0].style.backgroundColor === '#ffffff' && pairs[0].children[0].attributes['aria-hidden'] === 'true'
		&& w.byClass( w.theme, 'themes-pair-swatch' ).filter( function( node ) { return node.style.backgroundColor === '#404040' } ).length === 3 );
	check( 'O. the colour written on the second colour is a pair of its own, the last of the list, with the colours of the second colour and the number the API gave', pairs[24].children[0].style.color === '#ffffff' && pairs[24].children[0].style.backgroundColor === '#17233a' && w.text( pairs[24] ).indexOf( W('role/on-second') ) !== -1 && w.text( pairs[24] ).indexOf( Wp( 'msg/ratio', 7.8 ) ) !== -1 );
	check( 'O. the smallest contrast is told in a line of its own, with the word', w.text( w.byClass( w.theme, 'themes-least' )[0] ) === Wp( 'msg/palette-bad', 1, 4.2 ), w.text( w.byClass( w.theme, 'themes-least' )[0] ) );
	check( 'O. a brand that names no colour has the colours of the reset, and the section says so, where the palette is', w.byClass( w.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent.indexOf( W('msg/palette-reset') ) !== -1 } ).length === 1 );

	// a draft with an own colour is a palette of the brand: no notice
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();

	check( 'O. the palette is made again with every answer of the preview: with a colour of the project\'s own the notice that the reset stands is gone, and the contrast line of the field is told the colour that is written on it', w.byClass( w.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent.indexOf( W('msg/palette-reset') ) !== -1 } ).length === 0 && w.byClass( w.theme, 'themes-pair' ).length === 25 );
}

{
	// where every pair holds the list of them is closed, and one sentence says so
	const w = world( { active : 'nino', pages : [ '/' ] } );

	w.at( '#themes/nino/brand/colours' );

	check( 'O. where every pair holds its contrast the list of them starts closed and one sentence says that they hold, with the smallest number; the eight colours of each mode are seen', w.byClass( w.theme, 'themes-allpairs' )[0].open === false && w.text( w.byClass( w.theme, 'themes-least' )[0] ) === Wp( 'msg/palette-ok', 4.5 ) && w.byClass( w.theme, 'themes-role' ).length === 16 && w.text( w.byTag( w.byClass( w.theme, 'themes-allpairs' )[0], 'summary' )[0] ) === Wp( 'label/all-pairs', 25 ) );
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

/** The answer of a dialog that goes on: the last button, at the far right - whatever it is called */
function goOn( dialog ) {
	return findAll( dialog, function( node ) { return node.tagName === 'BUTTON' } ).pop();
}

/** The kinds of the answers of a dialog from the left: 'secondary', 'primary' or 'danger' */
function kindsOf( dialog ) {
	return findAll( dialog, function( node ) { return node.tagName === 'BUTTON' } ).map( function( node ) { return /nino-admin-btn-(primary|danger|secondary)/.exec( node.className )[1] } ).join();
}

/** The answer that goes on of the dialog that is open */
function pressedOn( w ) {
	fire( goOn( w.dialogs[w.dialogs.length - 1] ), 'click' );
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

	check( 'P. a brand that is changed in a theme of a package is saved as the brand and not as a theme: the button says Save, and not that an own theme is made', w.text( w.button( w.bar(), W('label/save') ) ) === W('label/save') && w.button( w.bar(), W('label/save-as') ) === undefined && w.button( w.bar(), W('label/save') ).disabled === false );

	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'P. it is asked first, whichever theme is open and whether or not it is the website\'s - the brand holds for the whole website - as a big thing, and the question says that it is the brand', seen.join() === 'big' && w.requests.length === 0 && w.text( w.dialogs[0] ).indexOf( W('confirm/brand') ) !== -1 );

	// A button that is off takes no focus, as in a browser: the question gives the focus back to Save while the save is under way
	const saveButton = w.button( w.bar(), W('label/save') );
	const focusOf = saveButton.focus;

	saveButton.focus = function() { if( saveButton.disabled !== true ) focusOf.call( saveButton ) };
	pressed( w, CANCEL );

	check( 'P. No saves nothing and the draft stays, and the focus is on Save, which was pressed - whatever the question gave it back to', w.requests.length === 0 && w.state_() === CHANGED && w.button( w.bar(), W('label/save') ).focused === true );

	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );

	const sent = w.requests[0];

	check( 'P. Yes sends the brand alone - the action brand - with the theme that is open, the brand as the draft has it, the stamp of the brand it was seen in, and no force', sent?.action === 'themes/brand' && sent.payload?.theme === 'craft' && sent.payload?.brand?.['color-1']?.value === '#9a4b1d' && sent.payload?.brandBase === 'brand-1' && sent.payload?.force === false && sent.payload?.mix === undefined );

	w.serve();

	check( 'P. what was saved is what the screen is: the state is Saved, the stamp of the brand is the new one, and the draft is the brand that is stored', w.state_() === SAVED && w.dirty.entries.themes.isDirty() === false && w.brandStamp === 'brand-2' && w.actions.slice( w.actions.indexOf('themes/brand') ).join() === 'themes/brand,themes/list,themes/open,themes/preview' );

	type( byId( w.theme, 'themes-brand-color-1' ), '#1f6f5b' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );

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

	check( 'P. the mix and the brand changed in a theme of a package: the button makes an own theme, and the row of the brand in the groups says that it is changed', w.text( w.button( w.bar(), W('label/save-as') ) ) !== '' );

	fire( w.button( w.bar(), W('label/save-as') ), 'click' );
	pressedOn( w );

	check( 'P. the brand is asked about first, since it reaches the website - and then the name', seen.join() === 'big' && w.dialogs.length === 2 && w.dialogs[1].open === true );

	const dialog = w.dialogs[w.dialogs.length - 1];

	findAll( dialog, function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	pressedOn( w );

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
	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'P. the mix of an own theme that is not the website\'s is saved at once, and the brand is not sent where it is not changed', seen.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.brand === undefined && w.requests[0]?.payload?.brandBase === undefined );
	w.serve();

	w.state.hash = '#themes/own.lindner/brand/colours';
	w.panel.showCurrent();
	w.serve();
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );

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
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.serve();

	const question = w.dialogs[w.dialogs.length - 1];

	check( 'P. a brand that another tab saved since it was opened is said in the question of the conflict, with the sentence of the server, and Cancel keeps the draft', question.open === true && w.text( question ).indexOf( E('themes_brand_changed') ) !== -1 && ( fire( findAll( question, function( node ) { return node.textContent === CANCEL } )[0], 'click' ), w.state_() === CHANGED && w.requests.length === 0 ) );

	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.serve();
	pressed( w, workbench['/_admin/common/conflict/overwrite'] );

	check( 'P. Overwrite sends the save once more at once, with the stamp the other tab left - the theme was asked for before the question, for what the other tab set - and no force', w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' && w.requests[0]?.payload?.brandBase === 'brand-5' && w.requests[0]?.payload?.force === false );

	w.serve();

	check( 'P. ...and the save is made once more with that stamp, and only that one: the stamp of the theme is the one the person has - what the refusal was not about is not taken over', w.sent.filter( function( request ) { return request.action === 'themes/brand' } ).slice( -1 )[0]?.payload?.brandBase === 'brand-5' && w.sent.filter( function( request ) { return request.action === 'themes/brand' } ).slice( -1 )[0]?.payload?.force === false && w.brandStored['color-1'].value === '#9a4b1d' && w.state_() === SAVED );
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
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.serve();
	pressed( w, workbench['/_admin/common/conflict/overwrite'] );
	w.serve();
	pressed( w, workbench['/_admin/common/conflict/overwrite'] );
	w.serve();

	const saves = w.sent.filter( function( request ) { return request.action === 'themes/save' } );

	check( 'P. a theme and a brand that both were saved by another tab are two questions, one after the other: the first save was made on the stamps it had, the second with the stamp of the theme only, the third with both - each of them the one the refusal was about', saves.length === 3 && saves[0]?.payload?.base === 'stamp-1' && saves[0]?.payload?.brandBase === 'brand-1' && saves[1]?.payload?.base === 'stamp-9' && saves[1]?.payload?.brandBase === 'brand-1' && saves[2]?.payload?.base === 'stamp-9' && saves[2]?.payload?.brandBase === 'brand-5' && w.state_() === SAVED );
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
	fire( w.button( w.bar(), W('label/save-as') ), 'click' );
	pressedOn( w );
	findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0].value = 'Lindner';
	pressedOn( w );
	w.serve();
	pressed( w, workbench['/_admin/common/conflict/overwrite'] );
	w.serve();

	const asks = w.sent.filter( function( request ) { return request.action === 'themes/save-as' } );

	check( 'P. a save as that the brand of another tab refuses is asked once more with the stamp that stands and with no force: Overwrite is the answer to a stamp', asks.length === 2 && asks[0]?.payload?.brandBase === 'brand-1' && asks[1]?.payload?.brandBase === 'brand-5' && asks[1]?.payload?.force === false && w.state_() === SAVED );
}

{
	// the stylesheet of the project is somebody else's: asked, as for every change that writes it
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/brand/colours' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	w.tick();
	w.serve();
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.answer( 409, { error : 'the stylesheet of the project is not the one this feature wrote', code : 'themes_foreign', params : [], field : '' } );

	check( 'P. a stylesheet that is not the feature\'s is explained and Replace is offered, for the brand as it is for the theme', w.dialogs.length === 2 && w.text( w.dialogs[1] ).indexOf( E('themes_foreign') ) !== -1 && w.requests.length === 0 );

	pressedOn( w );

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
	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'P. a mode that does not ask before a big thing is not asked: the brand is saved at once, and the level that was asked of the policy is big', asked.join() === 'big' && w.dialogs.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' );
	w.serve();
}

// --- Q. the own css ------------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/css' );

	const text = byId( w.theme, 'themes-css' );

	check( 'Q. the own css is a section of the first level with an address of its own, and it is open: a field of text, named by the label that holds it, in a face of fixed width, off spell checking, wrapping nothing', w.state.hash === '#themes/own.lindner/css' && text.tagName === 'TEXTAREA' && text.parentNode.tagName === 'LABEL' && w.text( text.parentNode ).indexOf( W('label/css') ) === -1 && text.className.indexOf('themes-code') !== -1 && text.spellcheck === false && text.attributes.wrap === 'off' );
	check( 'Q. one sentence at the field says that the text goes into the website as it is, unchecked, after the parts of the theme and before the stylesheet of the project - and the field points at it', w.text( byId( w.theme, 'themes-css-hint' ) ).indexOf( Wp( 'hint/css', 64 ) ) === 0 && text.attributes['aria-describedby'] === 'themes-css-hint' );
	check( 'Q. a theme with no css says so in the head of the section, and nothing is changed', w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('none') !== -1 && w.dirty.entries.themes.isDirty() === false );

	type( text, 'a { color: red }\nb { color: blue }' );

	check( 'Q. what is typed is the css of the draft: the head says how many lines and that it is changed, the bar says changed, and the field is the one that was typed in', w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('2 lines') !== -1 && w.text( byId( w.theme, 'themes-groups-2-head' ) ).indexOf('changed') !== -1 && w.state_() === CHANGED && w.dirty.entries.themes.isDirty() === true );
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
	fire( w.button( w.bar(), W('label/save') ), 'click' );

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
	check( 'Q. ...Save cannot be pressed, and the draft is not asked for with it - nothing is sent with the hand at rest - and the one that was made before stays on the screen', w.button( w.bar(), W('label/save') ).disabled === true && w.timers.filter( function( timer ) { return timer.fn !== null } ).length === 0 );

	type( text, 'a { }' );

	check( 'Q. the sentence goes with the text that is too long', byId( w.theme, 'themes-css-problem' ) === undefined && text.attributes['aria-invalid'] === undefined && text.attributes['aria-describedby'] === 'themes-css-hint' && w.button( w.bar(), W('label/save') ).disabled === false );
	w.timers.length = 0;

	type( text, 'a { }\0' );

	check( 'Q. a null byte is said as well, and is not sent', w.text( byId( w.theme, 'themes-css-problem' ) ).indexOf( W('error/css-null') ) !== -1 && w.button( w.bar(), W('label/save') ).disabled === true );

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
	check( 'Q. ...and the field holds what was typed, and the panel is whole: the groups and the settings are there', byId( w.theme, 'themes-css' ).value === hostile && w.byClass( w.theme, 'nino-admin-accordion-button' ).length === 3 );
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

	check( 'R. a new name is a change of the draft - the head of the section says it, so does the bar - and the title over the split view is the saved name until it is saved', w.state_() === CHANGED && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Lindner Two') !== -1 && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('changed') !== -1 && w.text( w.byClass( w.theme, 'themes-name' )[0] ) === 'Lindner' );

	type( name, '   ' );

	check( 'R. a name that says nothing is said at the field, which is marked, and is not taken: the draft keeps the last name that was one', w.text( byId( w.theme, 'themes-name-error' ) ) === W('error/name-empty') && name.attributes['aria-invalid'] === 'true' && name.attributes['aria-describedby'] === 'themes-name-error' && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf('Lindner Two') !== -1 );

	type( name, 'Lindner Three' );

	check( 'R. ...and the sentence goes with the next letter', byId( w.theme, 'themes-name-error' ) === undefined && name.attributes['aria-invalid'] === undefined && name.attributes['aria-describedby'] === 'themes-name-hint' );

	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'R. a rename is a save: save, the key of the theme, the new name in the mix and the stamp it was seen in - the key does not change', w.requests.length === 1 && w.requests[0]?.action === 'themes/save' && w.requests[0]?.payload?.theme === 'own:lindner' && w.requests[0]?.payload?.mix?.name === 'Lindner Three' && w.requests[0]?.payload?.base === 'stamp-1' );
	w.serve();

	check( 'R. ...and what is saved is read again: the title and the card of the theme are the new name only if the server says so - the stand-in does not, so the name that comes back is the one the server keeps', w.state_() === SAVED && w.dirty.entries.themes.isDirty() === false );
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

	pressed( w, CANCEL );

	check( 'R. Cancel sends nothing and the focus is back on the button', w.requests.length === 0 && byId( w.theme, 'themes-save-as' ).focused === true );

	fire( byId( w.theme, 'themes-save-as' ), 'click' );
	pressedOn( w );

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

	fire( w.button( w.bar(), W('label/reset') ), 'click' );
	w.serve();

	check( 'R. ...and it can be as soon as the change is gone', byId( w.theme, 'themes-restore' ).disabled === false && w.text( byId( w.theme, 'themes-restore-hint' ) ) === Wp( 'hint/restore', W('msg/last-unknown') ) );

	fire( byId( w.theme, 'themes-restore' ), 'click' );

	check( 'R. it is asked first, as a big thing, in a question that says what it covers and that the state as it is now becomes the last state', seen.join() === 'big' && w.dialogs.length === 1 && w.text( w.dialogs[0] ).indexOf( Wp( 'confirm/restore', W('msg/last-unknown') ) ) !== -1 );

	pressed( w, CANCEL );

	check( 'R. No does nothing and the focus is on the button', w.requests.length === 0 && byId( w.theme, 'themes-restore' ).focused === true );

	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );

	check( 'R. Yes asks the API with the state the panel saw it in, and no force', w.requests.length === 1 && w.requests[0]?.action === 'themes/restore' && w.requests[0]?.payload?.base === 'state-1' && w.requests[0]?.payload?.force === false );
	w.serve();

	check( 'R. what was restored is asked for again and shown: the list, the theme, its draft - and the line says that the last state is back; the focus is on the button that was pressed', w.actions.slice( w.actions.indexOf('themes/restore') ).join() === 'themes/restore,themes/list,themes/open,themes/preview' && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ).indexOf( W('msg/restored') ) !== -1 && byId( w.theme, 'themes-restore' ).focused === true && w.state_() === SAVED );

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

	pressed( w, CANCEL );

	check( 'R. Cancel leaves it, and the focus is on the button', w.requests.length === 0 && byId( w.theme, 'themes-restore' ).focused === true );

	fire( byId( w.theme, 'themes-restore' ), 'click' );
	pressed( w, 'Restore the last state' );
	w.serve();
	pressed( w, workbench['/_admin/common/conflict/overwrite'] );
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

	pressed( w, CANCEL );

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

	pressed( w, workbench['/_admin/common/conflict/overwrite'] );
	w.serve();

	check( 'R. Overwrite takes the stamp that stands now and deletes it with that', w.deleted.join() === 'own:lindner' && w.sent.filter( function( request ) { return request.action === 'themes/delete' } ).slice( -1 )[0]?.payload?.base === 'stamp-9' && w.sent.filter( function( request ) { return request.action === 'themes/delete' } ).slice( -1 )[0]?.payload?.force === false );

	const y = world( { active : 'nino' } );

	y.at( '#themes/own.lindner/settings' );
	fire( byId( y.theme, 'themes-delete' ), 'click' );
	pressed( y, 'Delete theme' );
	y.answer( 409, { error : 'the stylesheet of the project is not the one this feature wrote', code : 'themes_foreign', params : [], field : '' } );
	pressedOn( y );

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
	fire( w.button( w.bar(), W('label/save') ), 'click' );

	check( 'S. ...and it is saved with no question that is untrue: nothing reaches a visitor, so the save is asked of the API at once', seen.length === 0 && w.dialogs.length === 0 && w.requests.length === 1 && w.requests[0]?.action === 'themes/brand' );
	w.serve();

	const on = world( { active : 'nino' } );

	on.at( '#themes/nino/brand/colours' );

	check( 'S. with a theme on the website the level says nothing of the kind', on.byClass( on.theme, 'nino-admin-notice' ).filter( function( node ) { return node.textContent === W('msg/brand-later') } ).length === 0 );

	type( byId( on.theme, 'themes-brand-color-1' ), '#9a4b1d' );
	on.tick();
	on.serve();
	fire( on.button( on.bar(), W('label/save') ), 'click' );

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
	check( 'S. ...and the bar says why it cannot be saved, whichever section is open; Save cannot be pressed, and a save by the keyboard is told no and sends nothing', said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === Wp( 'error/colour-invalid', W('label/color-1') ) && w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state === 'error' && w.button( w.bar(), W('label/save') ).disabled === true && ( w.sent.length = 0, w.dirty.entries.themes.save( function( ok ) { w.kept = ok } ), w.kept === false && w.requests.filter( function( request ) { return request.action !== 'themes/preview' } ).length === 0 ) );

	type( colour, '#abc' );
	fire( colour, 'change' );
	type( colour, '#abcd' );

	check( 'S. a colour that was left in the field is the one the draft goes back to: #abc was left, so #abcd is #aabbcc again and not the colour of the theme', ( w.tick(), w.serve(), w.sent.filter( function( request ) { return request.action === 'themes/preview' } ).slice( -1 )[0]?.payload?.brand?.['color-1']?.value === '#aabbcc' ) && w.dirty.entries.themes.isDirty() === true && w.button( w.bar(), W('label/save') ).disabled === true );

	type( colour, '#abcdef' );

	check( 'S. a colour at last: the reason goes from the bar, and the draft can be saved', w.byClass( w.theme, 'nino-admin-status' )[0].dataset.state !== 'error' && w.button( w.bar(), W('label/save') ).disabled === false );

	// the way back clears what is wrong with the field
	type( colour, '#12' );
	fire( byId( w.theme, 'themes-brand-back-color-1' ) ?? element('button'), 'click' );
	type( colour, '#12' );
	fire( w.button( w.bar(), W('label/reset') ), 'click' );

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
	fire( w.button( w.bar(), W('label/apply') ), 'click' );

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

	check( 'S. the same name typed again is no change: the draft is the saved state, the bar does not say changed and the head of the section has no mark', w.dirty.entries.themes.isDirty() === false && w.byClass( w.theme, 'nino-admin-savebar-state' )[0].dataset.state !== 'warn' && w.text( byId( w.theme, 'themes-groups-1-head' ) ).indexOf( W('state/changed-mark') ) === -1 );

	type( name, 'Taken' );
	fire( w.button( w.bar(), W('label/save') ), 'click' );
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
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.serve();

	const question = said( w, w.dialogs[w.dialogs.length - 1] );

	check( 'S. the question says which values the two tabs changed - the first colour, and not the second, which only the other tab changed - and what Overwrite does', question.indexOf( Wp( 'msg/brand-both', W('label/color-1') ) ) !== -1 && question.indexOf( W('label/color-2') ) === -1 && question.indexOf( W('msg/brand-merge') ) !== -1 );

	pressed( w, workbench['/_admin/common/conflict/overwrite'] );

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
	fire( quiet.button( quiet.bar(), W('label/save') ), 'click' );
	pressedOn( quiet );
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
	fire( w.button( w.bar(), W('label/save') ), 'click' );
	pressedOn( w );
	w.answer( 409, { error : 'the stylesheet cannot be read', code : 'themes_unreadable', params : [], field : '' } );

	check( 'S. a stylesheet that cannot be read is asked about as one that is somebody else\'s, in the sentence that says that what it holds is not kept as the last state', said( w, w.dialogs[1] ).indexOf( E('themes_unreadable') ) !== -1 && w.requests.length === 0 );

	pressedOn( w );

	check( 'S. ...and Replace sends the force', w.requests.length === 1 && w.requests[0]?.payload?.force === true );

	w.answer( 409, { error : 'the stylesheet cannot be replaced', code : 'themes_locked', params : [], field : '' } );

	check( 'S. a stylesheet that cannot be replaced is said in the bar, and is no success: the state stays changed and there is no question', w.dialogs.length === 2 && w.state_() === CHANGED && said( w, w.byClass( w.theme, 'nino-admin-status' )[0] ) === E('themes_locked') );
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
	fire( apply.button( apply.bar(), W('label/apply') ), 'click' );
	pressed( apply, W('label/apply') );

	check( 'S. an own theme is applied as the stamp the panel has of it says: the apply goes with the stamp, as a save does', apply.requests[0]?.action === 'themes/apply' && apply.requests[0]?.payload?.base === 'stamp-1' && seen.join() === 'big' );

	apply.stamp = 'stamp-9';
	apply.serve();

	const card = world( { active : 'nino' } );

	card.at( '#themes' );
	fire( findAll( card.list, function( b ) { return b.dataset.apply === 'own:lindner' } )[0], 'click' );
	pressed( card, W('label/apply') );

	check( 'S. a card of the list applies the stamp of the theme as the list has it', card.requests[0]?.payload?.base === 'stamp-1' );

	card.stamp = 'stamp-9';
	card.serve();

	check( 'S. ...and where another tab saved the theme meanwhile the list says so - in words that do not say the theme was opened, which the person who applies from a card has not done - and shows the theme as it is now, and the website keeps what it shows', said( card, byId( card.list, 'themes-say' ) ) === Wp( 'msg/changed-list', 'Lindner' ) && said( card, byId( card.list, 'themes-say' ) ).indexOf( Wp( 'api/themes_changed', 'Lindner' ) ) === -1 && card.active === 'nino' && card.dialogs.length === 1 );
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
	check( 'N. ...and the middle again is none, which the saved mix says with a 0 and the draft by leaving the knob out', w.dirty.entries.themes.isDirty() === false && w.state_() === APPLIED.replace( 'and active', '' ).trim() || w.dirty.entries.themes.isDirty() === false );
}

// --- T. the tabs are the workbench's -----------------------------------------------------------------------------------------------

{
	// the pane has its tabs from the registry, in its head: the panel draws no strip of its own and keeps no address for one
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	check( 'T. the panel draws no strip of tabs and has no views to hang one in: the tabs of the pane are the registry\'s', w.doc.getElementById('themes-tabs') === null && w.panel.views === undefined && w.doc.getElementById('themes-view') === null );
	// a group of the register may be called fonts: the name is no section of the panel
	const named = world( { active : 'nino' } );

	named.state.hash = '#themes/nino/groups';
	named.panel.showCurrent();
	named.serve( { listExtra : { groups : [ 'brand', 'footer', 'section', 'header', 'fonts' ].map( function( key ) { return { key : key, label : GROUP_LABEL[key], feature : 'basis', components : [], demo : false } } ) } } );
	check( 'T. a group that is called fonts is a group like every other: it is a row of the groups, and the one called brand is named as kept', named.byClass( named.theme, 'themes-groups' )[0].children.map( function( item ) { return item.children[0].dataset.group } ).indexOf('fonts') !== -1
		&& named.byClass( named.theme, 'themes-reserved' ).length === 1 );

	w.state.hash = '#themes/fonts';
	w.panel.showCurrent();
	check( 'T. the address #themes/fonts names a theme of that key and nothing else: the panel asks to open it', w.requests.length === 1 && w.requests[0].action === 'themes/open' && w.requests[0].payload.theme === 'fonts' );
}

// --- U. a theme is copied -------------------------------------------------------------------------------------------------------

/** The dialog that is open: the field of the name in it */
function nameField( w ) {
	return findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'INPUT' } )[0];
}

{
	// from the card of a theme of a package: the name is asked, the copy is made, and the list says so
	const w = world( { active : 'nino' } );

	w.canRestore = true;
	w.at( '#themes' );

	const craft = w.byTag( w.list, 'article' ).find( function( card ) { return card.dataset.theme === 'craft' } );
	const button = findAll( craft, function( node ) { return node.dataset.duplicate === 'craft' } )[0];

	check( 'U. every card has a button to duplicate the theme, the one of a package and the own one, and the website\'s own theme too', w.byTag( w.list, 'article' ).every( function( card ) { return findAll( card, function( node ) { return node.dataset.duplicate === card.dataset.theme } ).length === 1 } ) );

	fire( button, 'click' );

	const dialog = w.dialogs[w.dialogs.length - 1];

	check( 'U. the button asks for a name in the dialog of the name, with the name of the theme and "(copy)" as the suggestion; nothing is sent yet', dialog.open === true && nameField( w ).value === Wp( 'msg/copy-name', 'Craft' ) && w.requests.length === 0 );
	check( 'U. ...the dialog says what is copied and that the brand is not, what the website goes on showing, and - where there is a last state - that it is replaced like every change does', w.text( dialog ).indexOf( Wp( 'msg/duplicate', 'Craft', Wp( 'msg/shown-theme', 'Nino' ) ) ) !== -1 && w.text( dialog ).indexOf( W('msg/replaces-last-plain') ) !== -1 );

	pressed( w, CANCEL );

	check( 'U. Cancel sends nothing, and the focus goes back to the button that was pressed', w.requests.length === 0 && button.focused === true );

	fire( button, 'click' );
	pressedOn( w );

	check( 'U. Save sends the copy: the theme, the name, no stamp for a theme of a package and no force - and nothing of a mix: the server copies what it holds', w.requests.length === 1 && w.requests[0].action === 'themes/duplicate' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { theme : 'craft', name : Wp( 'msg/copy-name', 'Craft' ), force : false } ), JSON.stringify( w.requests[0]?.payload ) );
	w.serve();

	check( 'U. the list is asked for again and shows the copy as a card of its own, the line says that it is made, and the focus is on the Edit button of the copy - the first place of what was made', w.actions.slice( -2 ).join() === 'themes/duplicate,themes/list' && w.byTag( w.list, 'article' ).some( function( card ) { return card.dataset.theme === 'own:craft-copy' } )
		&& w.text( byId( w.list, 'themes-say' ) ) === Wp( 'msg/duplicated', Wp( 'msg/copy-name', 'Craft' ) ) && findAll( w.list, function( node ) { return node.dataset.edit === 'own:craft-copy' } )[0].focused === true );
	check( 'U. a copy does not touch what the website shows: no apply, no theme opened', w.active === 'nino' && w.actions.indexOf('themes/apply') === -1 && w.actions.indexOf('themes/open') === -1 );
}

{
	// a name that is taken is asked again with the sentence at the field; a name that says nothing as well
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const craft = w.byTag( w.list, 'article' ).find( function( card ) { return card.dataset.theme === 'craft' } );

	fire( findAll( craft, function( node ) { return node.dataset.duplicate === 'craft' } )[0], 'click' );
	nameField( w ).value = 'Taken';
	pressedOn( w );
	w.serve();

	const again = w.dialogs[w.dialogs.length - 1];

	check( 'U. a name that is taken is asked again in the dialog, with the sentence of the server at the field, which is marked, and the name that was typed', again.open === true && again !== w.dialogs[0] && nameField( w ).value === 'Taken' && w.text( byId( again, 'themes-name-error' ) ) === Wp( 'api/themes_name_taken', 'Taken Before' ) && nameField( w ).attributes['aria-invalid'] === 'true' );

	nameField( w ).value = '   ';
	pressedOn( w );

	check( 'U. a name that says nothing sends nothing: the dialog is there again with the sentence at the field', w.requests.length === 0 && w.text( byId( w.dialogs[w.dialogs.length - 1], 'themes-name-error' ) ) === W('error/name-empty') );

	pressed( w, CANCEL );

	check( 'U. Cancel at the second asking sends nothing either, and the list is as it was', w.requests.length === 0 && w.byTag( w.list, 'article' ).length === 4 );
}

{
	// an own theme is copied as the one who asks saw it: with the stamp the card has; another tab that wrote it since is not copied unseen
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const own = function() { return w.byTag( w.list, 'article' ).find( function( card ) { return card.dataset.theme === 'own:lindner' } ) };

	fire( findAll( own(), function( node ) { return node.dataset.duplicate === 'own:lindner' } )[0], 'click' );

	check( 'U. the suggestion for an own theme is made of its name as the other one is', nameField( w ).value === Wp( 'msg/copy-name', 'Lindner' ) );

	pressedOn( w );

	check( 'U. an own theme is copied with the stamp of the card', w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.base === 'stamp-1', JSON.stringify( w.requests[0]?.payload ) );

	w.stamp = 'stamp-9';
	w.serve();

	check( 'U. where the theme was changed meanwhile nothing is made: the list says so in words of its own, shows the theme as it is now, and asks nothing - no dialog of a conflict, which is about a theme that is open', w.text( byId( w.list, 'themes-say' ) ) === Wp( 'msg/changed-list', 'Lindner' ) && w.dialogs.length === 1 && w.copies.length === 0 );
}

{
	// the name that is suggested is cut to the length a name may have
	const long = 'x'.repeat( 60 );
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { themes : [ { key : 'own:long', kind : 'own', name : long, from : {}, stamp : 'stamp-1', active : false } ] } } );

	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:long' } )[0], 'click' );

	check( 'U. the suggestion is no longer than a name may be, and still says that it is a copy', Array.from( nameField( w ).value ).length <= 60 && nameField( w ).value.indexOf( 'x' ) === 0 && nameField( w ).value !== long && nameField( w ).value.indexOf( Wp( 'msg/copy-name', '' ).trim().slice( -3 ) ) !== -1 );
}

{
	// from the settings of a theme: the saved theme is copied, and the screen goes on to the copy
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );

	const copy = byId( w.theme, 'themes-duplicate' );

	check( 'U. the settings have a button to duplicate the theme, with a sentence that says what is copied - the saved theme, its mix and its own css, not the brand - and nothing in the way of it where the draft is as it is saved', copy !== undefined && copy.disabled === false && w.text( byId( w.theme, 'themes-duplicate-hint' ) ) === W('hint/duplicate') && copy.attributes['aria-describedby'] === 'themes-duplicate-hint' );
}

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );
	type( byId( w.theme, 'themes-theme-name' ), 'Lindner Two' );

	const copy = byId( w.theme, 'themes-duplicate' );

	check( 'U. where the draft is not what is saved the button waits - it copies the saved theme, and the draft is what "Save as own theme" takes - and the sentence says what is to be done first', copy.disabled === true && w.text( byId( w.theme, 'themes-duplicate-hint' ) ) === W('hint/restore-dirty') );
}

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-duplicate' ), 'click' );

	check( 'U. the dialog of the settings is the one of the card: the suggestion, and what is copied and what the website goes on showing', nameField( w ).value === Wp( 'msg/copy-name', 'Lindner' ) && w.text( w.dialogs[w.dialogs.length - 1] ).indexOf( Wp( 'msg/duplicate', 'Lindner', Wp( 'msg/shown-theme', 'Nino' ) ) ) !== -1 );

	pressedOn( w );

	check( 'U. ...and it is sent with the stamp of the theme that is open', w.requests[0].action === 'themes/duplicate' && w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.base === 'stamp-1' && w.requests[0].payload.force === false );
	w.serve();

	check( 'U. the screen is the copy now - its address, in the same section - with the line that says that it is made, and the focus on its name; the theme that was copied is as it was', w.state.hash === '#themes/own.lindner-copy/settings' && w.text( w.byClass( w.theme, 'themes-name' )[0] ) === Wp( 'msg/copy-name', 'Lindner' ) && w.byClass( w.theme, 'themes-name' )[0].focused === true
		&& w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === Wp( 'msg/duplicated', Wp( 'msg/copy-name', 'Lindner' ) ) && w.deleted.length === 0, w.state.hash );
}

{
	// the other tab wrote the theme while the dialog was open: the conflict is asked as any other
	const w = world( { active : 'nino' } );

	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-duplicate' ), 'click' );
	pressedOn( w );
	w.stamp = 'stamp-9';
	w.serve();

	check( 'U. a theme that is open and was saved otherwise meanwhile is not asked about as an overwrite - a copy overwrites nothing - but as what it is: the copy would be of a theme the person has not seen; the choices are to copy it as it is saved now, to reload and to cancel', w.dialogs.length === 2 && w.text( w.dialogs[1] ).indexOf( W('label/copy-as-saved') ) !== -1 && w.text( w.dialogs[1] ).indexOf( workbench['/_admin/common/conflict/overwrite'] ) === -1 && w.text( w.dialogs[1] ).indexOf( W('label/reload') ) !== -1 );
	check( 'U. ...and the answers stand the way every dialog\'s do: the other way on, Cancel, and at the far right the one that goes on - which is not the one that destroys, so the focus may open on it', kindsOf( w.dialogs[1] ) === 'secondary,secondary,primary' && findAll( w.dialogs[1], function( node ) { return node.tagName === 'BUTTON' } )[1].textContent === CANCEL );

	pressed( w, W('label/copy-as-saved') );
	w.serve();

	check( 'U. ...copying it as it is saved sends the copy again with the stamp that stands now, and it is made', w.sent.filter( function( request ) { return request.action === 'themes/duplicate' } ).slice( -1 )[0].payload.base === 'stamp-9' && w.copies.length === 1 );
}

{
	// the other two answers of that question: Cancel, and Reload
	const w = world( { active : 'nino' } );
	const line = function() { return w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) };

	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-duplicate' ), 'click' );
	pressedOn( w );
	w.stamp = 'stamp-9';
	w.serve();

	check( 'U. while the question stands the line of the bar says that the copy is under way', line() === workbench['/_admin/common/msg/saving'] );

	pressed( w, CANCEL );

	check( 'U. Cancel makes no copy and asks nothing - and the line does not go on saying that something is saved', w.copies.length === 0 && w.requests.length === 0 && line() !== workbench['/_admin/common/msg/saving'] );

	fire( byId( w.theme, 'themes-duplicate' ), 'click' );
	pressedOn( w );
	w.serve();
	pressed( w, W('label/reload') );

	check( 'U. Reload makes no copy: the theme is asked for as it is saved', w.requests.length === 1 && w.requests[0].action === 'themes/open' && w.requests[0].payload.theme === 'own:lindner' && w.copies.length === 0 );

	w.serve();

	check( 'U. ...and is on the screen as it is saved, in the section it was in, with nothing under way', w.state.hash === '#themes/own.lindner/settings' && w.copies.length === 0 && line() !== workbench['/_admin/common/msg/saving'] && w.sent.filter( function( request ) { return request.action === 'themes/duplicate' } ).length === 2 );
}

{
	// the key a name makes is the server's: an umlaut is ae there, so "Mueller (copy)" is the copy of "Müller" that is taken
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { themes : [ { key : 'own:mueller', kind : 'own', name : 'Müller', from : {}, stamp : 'stamp-1', active : false }, { key : 'own:mueller-copy', kind : 'own', name : 'Mueller (copy)', from : {}, stamp : 'stamp-1', active : false } ] } } );
	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:mueller' } )[0], 'click' );

	check( 'U. a name with an umlaut makes the key the server makes of it: the first copy of Müller is taken by Mueller (copy), and the second is suggested', Wp( 'msg/copy-name', 'Mueller' ) === 'Mueller (copy)' && nameField( w ).value === Wp( 'msg/copy-name-n', 'Müller', 2 ), nameField( w ).value );
}

{
	// a name that is cut does not end on the space or the mark the cut fell on
	const cut = 'Lindner'+ ' –'.repeat( 30 )+ ' Sons';
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { themes : [ { key : 'own:cut', kind : 'own', name : cut, from : {}, stamp : 'stamp-1', active : false } ] } } );
	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:cut' } )[0], 'click' );

	check( 'U. a suggestion that is cut ends on the last word that is whole in it, not on the spaces and dashes the cut fell among: a name of 72 characters, Lindner and thirty dashes before its last word, is suggested as the copy of Lindner', Array.from( cut ).length === 72 && nameField( w ).value === Wp( 'msg/copy-name', 'Lindner' ), nameField( w ).value );
}

{
	// a stylesheet that is stale says why: for the feature or its components, or for a font whose files changed
	const notices = function( active ) {

		const w = world( { active : 'nino' } );

		w.at( '#themes' );
		w.panel.reload();
		w.serve( { listExtra : { active : active } } );

		return w.byClass( w.list, 'nino-admin-notice' ).map( function( node ) { return w.text( node ) } ).join('|');
	};

	check( 'X. the list says of a stale stylesheet what made it stale: a font whose files are others than the stylesheet names, or else the feature and its components - and nothing where it is not stale',
		notices( { theme : 'nino', built : '', stale : true, back : {}, fonts : true } ) === W('msg/stale-fonts') && notices( { theme : 'nino', built : '', stale : true, back : {}, fonts : false } ) === W('msg/stale')
		&& notices( { theme : 'nino', built : '', stale : false, back : {}, fonts : false } ) === '' && W('msg/stale-fonts') !== W('msg/stale') );
}

{
	// a list that is drawn again behind the screen of a theme
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/groups' );
	w.panel.reload();
	w.serve();

	check( 'Y. a list that is drawn again while a theme is on screen has its boxes and watches none of them: no picture is asked for behind a theme', w.byClass( w.list, 'themes-thumb' ).length === 4 && w.watched.length === 0 && w.requests.length === 0 );
}

{
	// the suggestion is always free: the second copy is the second, and a long name is cut so that its key is its own
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { themes : [
		{ key : 'own:lindner', kind : 'own', name : 'Lindner', from : {}, stamp : 'stamp-1', active : false },
		{ key : 'own:lindner-kopie', kind : 'own', name : Wp( 'msg/copy-name', 'Lindner' ), from : {}, stamp : 'stamp-1', active : false },
		{ key : 'own:long', kind : 'own', name : 'The very long name of a theme that is cut at forty', from : {}, stamp : 'stamp-1', active : false },
	] } } );

	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:lindner' } )[0], 'click' );

	check( 'U. a suggestion that a theme has as its name is not made: the copy of a copy is the second', nameField( w ).value === Wp( 'msg/copy-name-n', 'Lindner', 2 ) );
	pressed( w, CANCEL );
	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:long' } )[0], 'click' );

	const cut = nameField( w ).value;

	check( 'U. a name that is cut at the length of a key is cut shorter, so that the whole of the suggestion is in its key and the copy has a key of its own: the suggestion is not the original in the key, and says that it is a copy', cut !== 'The very long name of a theme that is cut at forty' && cut.indexOf( Wp( 'msg/copy-name', '' ).trim().slice( -5 ) ) !== -1 && cut.replace( /[^A-Za-z0-9]+/g, '-' ).length <= 40 && Array.from( cut ).length <= 60, cut );
}

{
	// a theme that was changed meanwhile, from the list: the focus is on the button of the same theme in the list that is drawn again
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	fire( findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:lindner' } )[0], 'click' );
	pressedOn( w );
	w.stamp = 'stamp-9';
	w.serve();

	const again = findAll( w.list, function( node ) { return node.dataset.duplicate === 'own:lindner' } )[0];

	check( 'U. where a theme was changed since the list was drawn the focus is on the Duplicate button of the same theme in the list as it is drawn now - the old button is gone, and the focus does not fall to the page', again !== undefined && again.focused === true );
}

// --- V. the page of the preview: "Automatic" -----------------------------------------------------------------------------------

{
	const w = world( { active : 'nino', demos : [ 'section', 'header' ], chosen : '' } );

	w.at( '#themes/nino/section/section-wrap' );

	const pages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];
	const token = 'nino-theme-preview='+ 'a'.repeat( 32 );

	check( 'V. the choice of the page has "Automatic" first and chosen, and the preview of a group that has a demo asks the website for it: the group in the address, with no mark - in the demo all is the component', pages.children[0].value === '' && pages.value === '' && w.frame().attributes.src === '/site/?'+ token+ '&nino-theme-demo=section', w.frame().attributes.src );

	// another group with a demo, one without, the groups
	fire( w.byClass( w.theme, 'nino-admin-back-link' )[1], 'click' );

	check( 'V. the level of the groups has no demo to show: the start page, with no component outlined', w.frame().attributes.src === '/site/?'+ token, w.frame().attributes.src );

	fire( w.byClass( w.theme, 'themes-groups' )[0].children[2].children[0], 'click' );

	check( 'V. another group that has a demo - the header - is shown by its own', w.state.hash.indexOf('#themes/nino/header') === 0 && w.frame().attributes.src.indexOf('&nino-theme-demo=header') !== -1 && w.frame().attributes.src.indexOf('nino-theme-demo=section') === -1, w.frame().attributes.src );

	w.state.hash = '#themes/nino/footer';
	w.panel.showCurrent();

	check( 'V. a group that has none - the footer - is shown by the start page, as the page of the whole is: the preview is not left empty', w.frame().attributes.src.indexOf('nino-theme-demo') === -1 && w.frame().attributes.src.indexOf('/site/?') === 0, w.frame().attributes.src );

	w.state.hash = '#themes/nino/page/type';
	w.panel.showCurrent();

	check( 'V. the whole page has no group of the register to take a demo from: the start page', w.frame().attributes.src.indexOf('nino-theme-demo') === -1 );

	// a page that is chosen is shown in place of the demo
	w.state.hash = '#themes/nino/section/section-wrap';
	w.panel.showCurrent();
	pages.value = '/contact';
	fire( pages, 'change' );

	check( 'V. a page that is chosen is shown in place of the demo, wherever the person goes in the theme', w.frame().attributes.src === '/site/contact?'+ token+ '&mark=section-wrap', w.frame().attributes.src );

	// it is remembered for the theme as long as the panel is open, and for no other theme
	w.state.hash = '#themes';
	w.panel.showCurrent();
	w.state.hash = '#themes/craft/section/section-wrap';
	w.panel.showCurrent();
	w.serve();

	const craftPages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

	check( 'V. another theme is shown by "Automatic" again - the choice is the theme\'s, not the panel\'s', craftPages.value === '' && w.frame().attributes.src.indexOf('nino-theme-demo=section') !== -1, w.frame().attributes.src );

	w.state.hash = '#themes';
	w.panel.showCurrent();
	w.state.hash = '#themes/nino/section/section-wrap';
	w.panel.showCurrent();
	w.serve();

	const ninoPages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];

	check( 'V. ...and the page that was chosen for a theme is the one it is shown by when it is opened again', ninoPages.value === '/contact' && w.frame().attributes.src.indexOf('/site/contact?') === 0 && w.frame().attributes.src.indexOf('nino-theme-demo') === -1, w.frame().attributes.src );

	// back to "Automatic"
	ninoPages.value = '';
	fire( ninoPages, 'change' );

	check( 'V. "Automatic" chosen again is the demo again', w.frame().attributes.src === '/site/?'+ token+ '&nino-theme-demo=section', w.frame().attributes.src );
}

{
	// a demo is asked for by the name of a group of the register and by nothing else: what the address says is encoded
	const w = world( { active : 'nino', demos : [ 'section' ], chosen : '' } );

	w.at( '#themes/nino/section/section-wrap' );

	check( 'V. the name of the group is the one thing the demo is asked by, and it is a name of the register: the query holds the group and no other value of the page', ( w.frame().attributes.src.match( /nino-theme-demo=([^&]*)/ ) ?? [] )[1] === 'section' );
}

{
	// where the site has no page at all the preview has no choice to make, and "Automatic" is the start page
	const w = world( { active : 'nino', pages : [] } );

	w.at( '#themes/nino/section/section-wrap' );

	check( 'V. a site whose list names no page has no choice of the page, and the preview is the root of the site', w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } ).length === 0 && w.frame().attributes.src.indexOf('/site/?') === 0 );
}

// --- W. the accordions close ------------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );
	const seen = [];
	const real = w.ui.accordion;

	w.ui.accordion = function( options ) { seen.push( options ); return real.call( w.ui, options ) };
	w.at( '#themes/nino/groups' );

	const heads = function() { return w.byClass( w.theme, 'nino-admin-accordion-button' ) };
	const open = function() { return heads().filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length };

	check( 'W. the first level is a collapsible accordion, and the groups are open where a person comes in', seen.length === 1 && seen[0].collapsible === true && open() === 1 && w.state.hash === '#themes/nino/groups' );

	fire( heads()[0], 'click' );

	check( 'W. a click on the open head closes it, and nothing is open: the heads below it can be reached - and the address is the level itself', open() === 0 && w.state.hash === '#themes/nino' && w.state.pushed.indexOf('#themes/nino') !== -1, w.state.hash );

	// Back and Forward: the level that has no section in its address is shown closed
	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();
	check( 'W. a step of the history to the address with the section opens it', open() === 1 && w.byClass( w.theme, 'themes-groups' ).length === 1 );
	w.state.hash = '#themes/nino';
	w.panel.showCurrent();
	check( 'W. ...and a step to the address of the level shows it closed, and the address stays the level\'s', open() === 0 && w.state.hash === '#themes/nino' );

	// a group: the same
	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();
	fire( w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0], 'click' );

	check( 'W. the settings of a group are a collapsible accordion, which a person who comes in by the row of the group finds with the first section open - the address names it, and so does the outline in the preview', seen[seen.length - 1].collapsible === true && open() === 1 && w.state.hash === '#themes/nino/section/section-wrap' && w.frame().attributes.src.indexOf('&mark=section-wrap') !== -1, w.state.hash );

	fire( heads()[0], 'click' );

	check( 'W. the open head closes: the address is the group\'s, nothing is outlined in the preview - the frame is loaded without a mark - and the line over it says that the whole page is shown', open() === 0 && w.state.hash === '#themes/nino/section' && w.frame().attributes.src.indexOf('mark=') === -1 && w.text( w.byClass( w.theme, 'themes-marked' )[0] ) === W('msg/whole-page'), w.state.hash+ ' '+ w.frame().attributes.src );

	fire( heads()[1], 'click' );

	check( 'W. another head opens the section, which is outlined', open() === 1 && w.state.hash === '#themes/nino/section/section-title' && w.frame().attributes.src.indexOf('&mark=section-title') !== -1 );

	// a step back to the closed group
	w.state.hash = '#themes/nino/section';
	w.panel.showCurrent();

	check( 'W. a step of the history to the closed group shows it closed and takes the outline from the preview', open() === 0 && w.frame().attributes.src.indexOf('mark=') === -1 );

	w.state.hash = '#themes/nino/section/section-title';
	w.panel.showCurrent();

	check( 'W. ...and a step to the section opens it and outlines it', open() === 1 && w.frame().attributes.src.indexOf('&mark=section-title') !== -1 );

	// the brand
	w.state.hash = '#themes/nino/brand/colours';
	w.panel.showCurrent();
	fire( heads()[0], 'click' );

	check( 'W. the brand is a collapsible accordion too: the open head closes, and the address is the brand\'s', seen[seen.length - 1].collapsible === true && open() === 0 && w.state.hash === '#themes/nino/brand' );

}

{
	// a level that is drawn again keeps what is open on it, and what is closed stays closed
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );
	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	fire( w.byClass( w.theme, 'nino-admin-accordion-button' )[0], 'click' );

	const open = function() { return w.byClass( w.theme, 'nino-admin-accordion-button' ).filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length };

	check( 'W. a change made in a section that is closed afterwards leaves the head that says so, and nothing open', open() === 0 && w.byClass( w.theme, 'nino-admin-accordion-badge' )[0].hidden === false );

	fire( w.button( w.bar(), W('label/reset') ), 'click' );
	w.serve();

	check( 'W. a level that is drawn again - Reset takes the draft back - is as it was: the section that was closed stays closed, and the badge of the change is gone', open() === 0 && w.byClass( w.theme, 'nino-admin-accordion-badge' ).every( function( badge ) { return badge.hidden === true } ) && w.state.hash === '#themes/nino/section' );

	// the way in opens the first section: a link, the address typed at the first load
	fire( w.byClass( w.theme, 'nino-admin-back-link' )[1], 'click' );
	fire( w.byClass( w.theme, 'themes-groups' )[0].children[3].children[0], 'click' );

	check( 'W. the way into a group is the way to its first section, whatever was closed there before', open() === 1 && w.state.hash === '#themes/nino/section/section-wrap' );
}

{
	// the address a page is loaded on is a way in: the group of the address is opened on the first section
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section' );

	check( 'W. an address that names no section, where the page is loaded on it, is a way in: the first section is open and the address says which', w.byClass( w.theme, 'nino-admin-accordion-button' ).filter( function( head ) { return head.attributes['aria-expanded'] === 'true' } ).length === 1 && w.state.hash === '#themes/nino/section/section-wrap', w.state.hash );
}

// --- X. what the second round left: the chip, the mode, a font that is back, what a dialog says ------------------------------------------

{
	// R3: a colour that is no colour is no change of the draft, and the state is not "saved" while it stands in a field
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/brand/colours' );

	check( 'X. the state of a theme as it is saved says so', w.state_() === APPLIED && w.button( w.bar(), W('label/reset') ).disabled === true );

	type( byId( w.theme, 'themes-brand-color-1' ), '#1234' );

	check( 'X. a colour that is no colour changes nothing of the draft - and the state is Changed all the same, and not "Saved and active": there is something in the field that is not saved, and a reset takes it away', w.dirty.entries.themes.isDirty() === true && w.state_() === CHANGED && w.byClass( w.theme, 'nino-admin-savebar-state' )[0].dataset.state === 'warn' && w.button( w.bar(), W('label/reset') ).disabled === false );

	fire( w.button( w.bar(), W('label/reset') ), 'click' );
	w.serve();

	check( 'X. ...and the reset puts the saved colour in the field and the state back to saved', byId( w.theme, 'themes-brand-color-1' ).value === '#1c5db8' && w.state_() === APPLIED );

	type( byId( w.theme, 'themes-brand-color-1' ), '#1234' );
	type( byId( w.theme, 'themes-brand-color-1' ), '#abc' );

	check( 'X. a colour that is one is a change, as it was', w.state_() === CHANGED && w.dirty.entries.themes.isDirty() === true );
}

{
	// R4: the sentence about a colour that is made darker names the modes the brand shows
	const fixed = function( mode ) {

		const w = world( { active : 'nino' } );

		w.rest = 'adjust';
		w.brandStored = Object.assign( brandNone(), { mode : { own : true, value : mode } } );
		w.at( '#themes/nino/brand/colours' );

		return w.byClass( w.theme, 'themes-adjusted' )[0];
	};

	const dark = fixed( 'dark' );
	const light = fixed( 'light' );
	const auto = fixed( 'auto' );

	check( 'X. a mode the brand never shows has no sentence: a brand that is dark only says nothing of what happens in the light mode', dark.hidden === true && dark.textContent === '' );
	check( 'X. ...the mode it shows is named, and where it shows both - the mode is automatic - each of them where the colour is made another', light.textContent === Wp( 'msg/adjust-darker', W('word/mode-light'), '#7f6904' ) && auto.textContent === Wp( 'msg/adjust-darker', W('word/mode-light'), '#7f6904' ) );
}

{
	// R5: a font that is back is not in the stylesheet before it is built again: the field says so, and the bar builds it
	const w = world( { active : 'nino' } );

	w.activeState = function() { return { theme : 'nino', built : '', stale : true, back : { 'font-title' : 'open-sans' } } };
	w.brandStored = Object.assign( brandNone(), { 'font-title' : { own : true, value : 'open-sans' } } );
	w.at( '#themes/nino/brand/type' );

	const select = byId( w.theme, 'themes-brand-font-title' );

	check( 'X. a font that was gone when the stylesheet was built and is there again has a sentence at its field, which the select points at - what is saved does not have it yet - and the fields of the other fonts have none', said( w, byId( w.theme, 'themes-brand-font-title-back' ) ) === Wp( 'msg/font-back', 'open-sans' ) && select.attributes['aria-describedby'] === 'themes-brand-font-title-back' && byId( w.theme, 'themes-brand-font-text-back' ) === undefined );

	const rebuild = w.button( w.bar(), W('label/rebuild') );

	check( 'X. the bar offers to build the stylesheet again where the theme is the one of the website and the stylesheet is not what the register and the fonts make now - the API says that it is stale', rebuild !== undefined && rebuild.hidden === false && rebuild.disabled === false );

	fire( rebuild, 'click' );
	pressed( w, W('label/apply') );

	check( 'X. for a theme of a package that is applying it once more - asked first, as every thing that reaches the website is - and nothing else is sent', w.requests.length === 1 && w.requests[0].action === 'themes/apply' && w.requests[0].payload.theme === 'nino' );

	w.activeState = function() { return { theme : 'nino', built : '', stale : false, back : {} } };
	w.serve();

	check( 'X. ...once it is built the button is gone, the line says so, and the field says nothing of the font', w.button( w.bar(), W('label/rebuild') ).hidden === true && w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === W('msg/rebuilt') );
}

{
	// R5 for an own theme: a save with nothing changed
	const w = world( { active : 'own:lindner' } );

	w.activeState = function() { return { theme : 'own:lindner', built : '', stale : true, back : {} } };
	w.at( '#themes/own.lindner/section/section-wrap' );

	check( 'X. a stylesheet that is stale for another reason - the feature or the register was changed - is offered the same: the bar has Rebuild, with nothing changed in the draft', w.dirty.entries.themes.isDirty() === false && w.button( w.bar(), W('label/rebuild') ).hidden === false );

	fire( w.button( w.bar(), W('label/rebuild') ), 'click' );
	pressedOn( w );

	check( 'X. for an own theme it is a save that changes nothing: the theme, its mix as it is saved - the name in it - and the stamp it was seen in, and no brand', w.requests.length === 1 && w.requests[0].action === 'themes/save' && w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.base === 'stamp-1' && w.requests[0].payload.mix.name === 'Lindner' && w.requests[0].payload.brand === undefined && w.requests[0].payload.force === false, JSON.stringify( w.requests[0]?.payload ) );

	w.activeState = function() { return { theme : 'own:lindner', built : '', stale : false, back : {} } };
	w.serve();

	check( 'X. ...and the screen is the theme as it is saved, with the line that says that the stylesheet is built', w.text( w.byClass( w.theme, 'nino-admin-status' )[0] ) === W('msg/rebuilt') && w.button( w.bar(), W('label/rebuild') ).hidden === true );
}

{
	// a theme that is not the website's has no Rebuild, and neither has a stylesheet that is as it should be
	const other = world( { active : 'craft' } );

	other.activeState = function() { return { theme : 'craft', built : '', stale : true, back : {} } };
	other.at( '#themes/nino/section/section-wrap' );

	const current = world( { active : 'nino' } );

	current.at( '#themes/nino/section/section-wrap' );

	check( 'X. the button is for the theme of the website only, and only where the stylesheet is stale', other.button( other.bar(), W('label/rebuild') ).hidden === true && current.button( current.bar(), W('label/rebuild') ).hidden === true );

	// the draft waits for a save: nothing is built from what is not saved
	current.activeState = function() { return { theme : 'nino', built : '', stale : true, back : {} } };
	current.panel.reload();
	current.serve();
	fire( current.step( 'themes-section-section-wrap-space', 3 ), 'click' );

	check( 'X. where the draft holds a change the button waits, as Apply does: it builds what is saved', current.button( current.bar(), W('label/rebuild') ).disabled === true );
}

{
	// R6: the dialog that saves a draft under a name says that it replaces the last state, as every question that writes does
	const w = world( { active : 'nino' } );

	w.at( '#themes/craft/settings' );
	fire( byId( w.theme, 'themes-save-as' ), 'click' );

	check( 'X. where there is no last state the dialog of "Save as own theme" has nothing to say of one', w.text( w.dialogs[w.dialogs.length - 1] ).indexOf( W('msg/replaces-last-plain') ) === -1 );

	const kept = world( { active : 'nino' } );

	kept.canRestore = true;
	kept.at( '#themes/craft/settings' );
	fire( byId( kept.theme, 'themes-save-as' ), 'click' );

	check( 'X. where there is one the dialog says that the state as it is now replaces it - the same sentence as the questions of Save, Apply and Delete', kept.text( kept.dialogs[kept.dialogs.length - 1] ).indexOf( W('msg/replaces-last-plain') ) !== -1 );
}

{
	// R6: the name of the theme in the sentence about the last state is put in quotes as it is in the other sentences
	const w = world( { active : 'nino' } );

	w.canRestore = true;
	w.last = { at : 1700000000, theme : 'craft', name : { de_DE : 'Craft', en_US : 'Craft' } };
	w.at( '#themes/own.lindner/settings' );
	fire( byId( w.theme, 'themes-restore' ), 'click' );

	check( 'X. the sentence about the last state names the theme as the others do: "the theme "Craft"" - the name stands in the quotes of the language', w.text( w.dialogs[w.dialogs.length - 1] ).indexOf( Wp( 'msg/shown-theme', 'Craft' ) ) !== -1 );

	const none = world( { active : 'nino' } );

	none.canRestore = true;
	none.last = { at : 1700000000, theme : '', name : '' };
	none.at( '#themes/own.lindner/settings' );
	fire( byId( none.theme, 'themes-restore' ), 'click' );

	check( 'X. ...and the standard parts, which are no theme, have none', none.text( none.dialogs[none.dialogs.length - 1] ).indexOf( W('word/standard-parts') ) !== -1 );
}

// --- Y. the thumbnails of the list ------------------------------------------------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const boxes = w.byTag( w.list, 'div' ).filter( function( node ) { return node.className.split(/\s+/).indexOf('themes-thumb') !== -1 } );
	const box = function( key ) { return boxes.find( function( one ) { return one.dataset.thumb === key } ) };

	check( 'Y. every card has a picture of its theme, and the picture is no control: hidden from a screen reader, with no frame in it and nothing asked of the server - no card is on screen yet', boxes.length === 4 && boxes.every( function( one ) { return one.attributes['aria-hidden'] === 'true' && w.byTag( one, 'iframe' ).length === 0 } ) && w.requests.length === 0 && w.watched.length === 4 );
	check( 'Y. the boxes are watched, and nothing else is: the card as a whole is not', w.watched.every( function( one ) { return boxes.indexOf( one.node ) !== -1 } ) );

	w.onScreen( box('craft') );

	check( 'Y. a card that comes on screen asks for its picture - one request for the theme, with no token, since none is held - and the others do not', w.requests.length === 1 && w.requests[0].action === 'themes/thumb' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { theme : 'craft', token : '' } ) && w.watched.length === 3 );

	w.answer( 200, { token : 'b'.repeat( 32 ), query : 'nino-theme-preview='+ 'b'.repeat( 32 ), expires : 4102444800, ttl : 900 } );

	const frame = w.byTag( box('craft'), 'iframe' )[0];

	check( 'Y. the answer gives the box a frame of the site\'s start page with the token of the thumbnail - not the one of the preview of a theme that is open - which takes no focus, is hidden from a screen reader and loads when it is seen', frame !== undefined && frame.attributes.src === '/site/?nino-theme-preview='+ 'b'.repeat( 32 ) && frame.attributes.tabindex === '-1' && frame.attributes['aria-hidden'] === 'true' && frame.attributes.loading === 'lazy' );
	check( 'Y. ...and the page is laid out as a desktop lays it out and scaled into the box, as the preview of a theme is, with the whole page in view', frame.style.width === '1280px' && frame.style.height === '800px' && frame.style.transform.indexOf('scale(') === 0 );

	// a refusal says nothing and draws nothing: the card is a card without a picture
	w.onScreen( box('nino') );
	w.answer( 500, { error : 'the preview could not be written' } );

	check( 'Y. a thumbnail that cannot be made leaves the card as it is: no frame, no sentence, no mark of an error in the list', w.byTag( box('nino'), 'iframe' ).length === 0 && w.text( byId( w.list, 'themes-say' ) ) === '' && w.byClass( w.list, 'nino-admin-notice' ).length === 0 );

	// the list is drawn again: what was asked is not asked again while the answer holds
	w.panel.reload();
	w.serve();

	const again = w.byTag( w.list, 'div' ).filter( function( node ) { return node.className.split(/\s+/).indexOf('themes-thumb') !== -1 && node.dataset.thumb === 'craft' } )[0];

	w.onScreen( again );

	check( 'Y. a list that is drawn again does not ask for a picture it has a token for - the token holds a quarter of an hour - and shows it at once, in the new card', w.requests.length === 0 && w.byTag( again, 'iframe' ).length === 1 && w.byTag( again, 'iframe' )[0].attributes.src === '/site/?nino-theme-preview='+ 'b'.repeat( 32 ) );
}

{
	// a token that has run out is asked for with the token it had, which the server takes over; an own theme that was saved since is another picture
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const box = function( key ) { return w.byTag( w.list, 'div' ).filter( function( node ) { return node.dataset.thumb === key } )[0] };

	w.onScreen( box('own:lindner') );
	w.answer( 200, { token : 'c'.repeat( 32 ), query : 'nino-theme-preview='+ 'c'.repeat( 32 ), expires : 4102444800, ttl : 1 } );
	w.panel.reload();
	w.serve();
	w.onScreen( box('own:lindner') );

	check( 'Y. a token that has run out is asked for again with the token it had - a thumbnail is a draft that is written again, and not one more folder - and the picture is the new answer', w.requests.length === 1 && w.requests[0].payload.token === 'c'.repeat( 32 ) );

	w.answer( 200, { token : 'c'.repeat( 32 ), query : 'nino-theme-preview='+ 'c'.repeat( 32 ), expires : 4102444800, ttl : 900 } );
	w.stamp = 'stamp-9';
	w.panel.reload();
	w.serve();
	w.onScreen( box('own:lindner') );

	check( 'Y. an own theme that was saved since the picture was made is another picture: it is asked for, and no token of the old one is offered', w.requests.length === 1 && w.requests[0].payload.theme === 'own:lindner' && w.requests[0].payload.token === '' );
}

{
	// the page is the start page of the site: the first of the list where the list has no home
	const w = world( { active : 'nino', pages : [ '/contact', '/imprint' ] } );

	w.at( '#themes' );
	w.onScreen( w.byTag( w.list, 'div' ).filter( function( node ) { return node.dataset.thumb === 'nino' } )[0] );
	w.answer( 200, { token : 'd'.repeat( 32 ), query : 'nino-theme-preview='+ 'd'.repeat( 32 ), expires : 4102444800, ttl : 900 } );

	check( 'Y. the picture is the start page - the home of the site, or the first page the list names where there is none - and never a page that was chosen for a preview', w.byTag( w.list, 'iframe' )[0].attributes.src === '/site/contact?nino-theme-preview='+ 'd'.repeat( 32 ) );

	// a list that is left leaves nothing to watch
	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();
	w.serve();

	check( 'Y. the pictures are the list\'s: the screen of a theme has none, and the preview of the theme asks for its own draft - one request, the preview - and no thumbnail', w.byTag( w.theme, 'iframe' ).length === 1 && w.sent.filter( function( request ) { return request.action === 'themes/thumb' } ).length === 1 );
}

// --- Z. the third round: cards of one height, unfinished css, a preview that is no page ----------------------------------------------

{
	// cards: what is said is on a fixed number of lines, the whole of it is in the title and in the text, and the buttons are at the foot
	const long = 'L'.repeat( 200 );
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { themes : [
		{ key : 'long', kind : 'theme', version : '1.0.0', name : { de_DE : long, en_US : long }, description : { de_DE : long+ ' '+ long, en_US : long+ ' '+ long }, brand : {}, parts : { a : { ok : false, problem : 'one', why : CSS_WHY }, b : { ok : false, problem : 'two', why : VERSION_WHY } }, active : false },
		{ key : 'own:short', kind : 'own', name : 'S', from : {}, stamp : 'x', active : false },
	] } } );

	const card = w.byTag( w.list, 'article' ).find( function( one ) { return one.dataset.theme === 'long' } );
	const title = w.byClass( card, 'themes-card-name' )[0];
	const clamped = w.byClass( card, 'themes-clamp' );

	check( 'Z. the name of a card is a heading whose title is the whole name, and the text of it is whole too - the cut is the stylesheet\'s, so that a screen reader has all of it', title.title === long && title.textContent === long );
	check( 'Z. what rests is laid on lines of a fixed number - a class of the stylesheet - with the whole text in the title, and the card has no description; a theme with two parts that rest says the first and puts both in the title, and the chip counts them', clamped.length === 1 && clamped.every( function( node ) { return node.title !== '' && node.title.indexOf( node.textContent ) !== -1 } ) && w.byClass( card, 'themes-rests' ).length === 1 && w.byClass( card, 'themes-rests' )[0].title.split('\n').length === 2 );
	check( 'Z. the buttons are the last thing in a card, in a block of their own, whatever the card says', card.children[card.children.length - 1].className.indexOf('nino-admin-actions') !== -1 );
}

{
	// a folder that is not read says why in the words of the panel: the sentence of the API is English, for the log
	const w = world( { active : 'nino' } );

	w.at( '#themes' );
	w.panel.reload();
	w.serve( { listExtra : { rejected : [ { folder : 'broken', reason : 'English sentence of the log', why : { code : 'version', params : [] } } ] } } );

	check( 'B. a theme folder that is not read is said with the words of the panel for its reason, and the English sentence is not shown', w.text( w.list ).indexOf( Wp( 'msg/rejected', 'broken', Wp( 'why/version' ) ) ) !== -1 && w.text( w.list ).indexOf( 'English sentence' ) === -1 );
}

{
	// css: a hint under the field for what is open, no refusal; the draft is sent and the hint follows the answer
	const w = world( { active : 'own:lindner' } );

	w.at( '#themes/own.lindner/css' );

	const text = byId( w.theme, 'themes-css' );
	const hint = byId( w.theme, 'themes-css-faults' );

	check( 'Z. the field of the own css has a line for what is open, empty and hidden while all is closed, which is read out when it comes - a status', hint !== undefined && hint.hidden === true && hint.attributes.role === 'status' );

	type( text, '.a { color: red' );
	w.tick();
	w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900, cssFaults : [ 'open-block', 'open-string' ] }, describe( w.requests[0].payload.mix ) ) );

	check( 'Z. an unfinished css is said quietly - every thing that is open, in words - and nothing stops: the css is sent, the field is not marked, and Save can be pressed', hint.hidden === false && hint.textContent === W('msg/css-open-block')+ ' '+ W('msg/css-open-string') && text.attributes['aria-invalid'] === undefined && w.button( w.bar(), W('label/save') ).disabled === false && w.requests.length === 0 );

	type( text, '.a { color: red }' );
	w.tick();
	w.answer( 200, Object.assign( { token : 'a'.repeat( 32 ), query : 'nino-theme-preview='+ 'a'.repeat( 32 ), expires : 4102444800, ttl : 900, cssFaults : [] }, describe( w.requests[0].payload.mix ) ) );

	check( 'Z. ...and the line goes when the css closes', hint.hidden === true && hint.textContent === '' );
}

{
	// an answer that is no draft leaves the picture as it is
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const before = w.frame().attributes.src;

	fire( w.step( 'themes-section-section-wrap-space', 3 ), 'click' );
	w.tick();
	w.requests[0].callback( { status : 200, responseJSON : false } );

	const line = w.byClass( w.theme, 'nino-admin-status' )[0];

	check( 'Z. a preview that answers with no draft - "false", no token - does not load the frame: the last picture stays, and the bar says in one sentence that the preview cannot be drawn for now', w.frame().attributes.src === before && line.textContent === W('msg/preview-unavailable') && line.dataset.state !== 'error' );

	fire( w.step( 'themes-section-section-wrap-space', 4 ), 'click' );
	w.tick();
	w.serve();

	check( 'Z. ...and the next change, answered as it should be, loads it and takes the sentence away', w.frame().attributes.src.indexOf('&mark=section-wrap') !== -1 && line.textContent !== W('msg/preview-unavailable') );

	fire( w.step( 'themes-section-section-wrap-space', 1 ), 'click' );
	w.tick();
	w.requests[0].callback( { status : 200, responseJSON : { token : 5 } } );

	check( 'Z. a token that is no text is no draft either', line.textContent === W('msg/preview-unavailable') );
}

// --- the review of the third round: what "Automatic" says, what is marked, the groups that are not shown, the pictures that go --------------------

{
	const w = world( { active : 'nino', demos : [ 'section' ], chosen : '' } );

	w.at( '#themes/nino/section/section-wrap' );

	const pages = w.byTag( w.theme, 'select' ).filter( function( one ) { return one.className.indexOf('themes-page') !== -1 } )[0];
	const marked = w.byClass( w.theme, 'themes-marked' )[0];

	check( 'V. where "Automatic" shows the demo of the group it says so as it is, and nothing is outlined: in the demo everything is the component - the line over the preview says nothing of an outline and the address has no mark', w.text( pages.children[0] ) === W('label/page-auto') && w.text( marked ) === '' && w.frame().attributes.src.indexOf('mark=') === -1 && w.frame().attributes.src.indexOf('nino-theme-demo=section') !== -1 );

	w.state.hash = '#themes/nino/footer';
	w.panel.showCurrent();

	check( 'V. where it shows the start page because the group has no demo the choice is still the short one and the line beside it says what is shown, and no component is outlined on a page that does not show it', w.text( pages.children[0] ) === W('label/page-auto') && w.text( marked ) === W('msg/auto-home') && w.frame().attributes.src.indexOf('mark=') === -1 );

	w.state.hash = '#themes/nino/section/section-wrap';
	w.panel.showCurrent();
	pages.value = '/contact';
	fire( pages, 'change' );

	check( 'V. a page that is chosen is the one place a component is outlined, and the line says which', w.frame().attributes.src.indexOf('&mark=section-wrap') !== -1 && w.text( marked ) === Wp( 'msg/marked', 'Wrap' ) );

	// the demo is asked of the start address, whatever the first page of the list is
	pages.value = '';
	fire( pages, 'change' );

	check( 'V. the demo is asked of the start address of the site and of no other page', w.frame().attributes.src.indexOf('/site/?nino-theme-preview=') === 0 );
}

{
	const w = world( { active : 'nino', pages : [ '/contact', '/imprint' ], demos : [ 'section' ], chosen : '' } );

	w.at( '#themes/nino/section/section-wrap' );

	check( 'V. ...also where the list has no home: the address of the demo is the root, and the start page of "Automatic" is the first page', w.frame().attributes.src.indexOf('/site/?nino-theme-preview=') === 0 );
}

{
	// a group of the register that is called as a section of the panel is named, and not left out
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/groups' );

	const notes = w.byClass( w.theme, 'themes-reserved' ).map( function( node ) { return node.textContent } );

	check( 'T. a group of the register whose name the panel keeps - the fixture has brand, settings, css and groups - is named in the groups with the reason, once each, and is no row of the list', notes.length === 4 && notes.indexOf( Wp( 'msg/group-reserved', 'Fonts', 'fonts' ) ) === -1 && w.byClass( w.theme, 'themes-groups' )[0].children.length === 5 );
}

{
	// the pictures are the list's
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const boxes = w.byTag( w.list, 'div' ).filter( function( node ) { return node.className.split(/\s+/).indexOf('themes-thumb') !== -1 } );

	w.onScreen( boxes[0] );
	w.answer( 200, { token : 'e'.repeat( 32 ), query : 'nino-theme-preview='+ 'e'.repeat( 32 ), expires : 4102444800, ttl : 900 } );

	check( 'Y. a picture is drawn once in a box: a box that comes on screen again has no second frame', ( function() { w.onScreen( boxes[0] ); return w.byTag( boxes[0], 'iframe' ).length === 1 } )() );

	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();
	w.serve();

	check( 'Y. where the list is left for a theme its pictures are taken away - no page stays loaded for a list that is not on screen - and the boxes are watched again', w.byTag( w.list, 'iframe' ).length === 0 && w.watched.length === 0 );

	const before = w.sent.filter( function( request ) { return request.action === 'themes/thumb' } ).length;

	w.state.hash = '#themes';
	w.panel.showCurrent();

	check( 'Y. ...the list that comes back watches its boxes again', w.watched.length === 4 );
	w.onScreen( boxes[0] );

	check( 'Y. ...and draws what is on screen again draws what is on screen again from the token it has, with no request', w.byTag( boxes[0], 'iframe' ).length === 1 && w.requests.length === 0 && w.sent.filter( function( request ) { return request.action === 'themes/thumb' } ).length === before );
}

{
	// a theme opened by its address asks for no picture
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/groups' );

	check( 'Y. a theme that is opened by its address draws no picture of the list: nothing is asked for before the list is on screen, and no box is watched', w.sent.filter( function( request ) { return request.action === 'themes/thumb' } ).length === 0 && w.byTag( w.list, 'iframe' ).length === 0 && w.watched.length === 0 );
}

{
	// two answers for one box - the list was left and came back while the first was out - are one frame
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const box = w.byTag( w.list, 'div' ).filter( function( node ) { return node.dataset.thumb === 'craft' } )[0];

	w.onScreen( box );
	w.state.hash = '#themes/nino/groups';
	w.panel.showCurrent();
	w.state.hash = '#themes';
	w.panel.showCurrent();
	w.onScreen( box );

	const asked = w.requests.filter( function( request ) { return request.action === 'themes/thumb' } );

	asked.forEach( function() {
		const at = w.requests.findIndex( function( request ) { return request.action === 'themes/thumb' } );
		const request = w.requests.splice( at, 1 )[0];
		request.callback( { status : 200, responseJSON : { token : 'f'.repeat( 32 ), query : 'nino-theme-preview='+ 'f'.repeat( 32 ), expires : 4102444800, ttl : 900 } } );
	} );

	check( 'Y. a box has one frame, however many answers come for it', asked.length === 2 && w.byTag( box, 'iframe' ).length === 1 );
}

// --- AA. the overview and the screen of a theme after the feedback of the owner ------------------------------------------------------

{
	const w = world( { active : 'nino' } );

	w.at( '#themes' );

	const cards = w.byTag( w.list, 'article' );
	const card = function( key ) { return cards.find( function( one ) { return one.dataset.theme === key } ) };
	const head = function( key ) { return w.byClass( card( key ), 'themes-card-head' )[0] };

	check( 'U1. a card has no description: the text a theme of a package brings is nowhere in the list, and a card of a package has no line of text of its own',
		cards.every( function( one ) { return w.text( one ).indexOf( 'Calm and clear.' ) === -1 && w.text( one ).indexOf( 'Breaks.' ) === -1 } ) && w.byClass( card('nino'), 'themes-clamp' ).length === 0 && w.byClass( card('craft'), 'themes-clamp' ).length === 0
		&& w.text( w.list ).indexOf( 'Calm and clear.' ) === -1 );

	const version = w.byClass( card('nino'), 'themes-card-version' )[0];

	check( 'U2. the version is a number behind the name in the title - quieter than the name, in the same heading - and no badge says it; an own theme has none',
		version !== undefined && version.textContent === '1.0.0' && version.parentNode === w.byTag( card('nino'), 'h3' )[0] && w.byClass( card('nino'), 'nino-admin-badge' ).every( function( badge ) { return w.text( badge ).indexOf('1.0.0') === -1 && w.text( badge ).indexOf('Version') === -1 } )
		&& w.byClass( card('own:lindner'), 'themes-card-version' ).length === 0 && w.byTag( card('nino'), 'h3' )[0].children[0].tagName === 'A' );

	const badges = w.byClass( head('nino'), 'themes-card-badges' )[0];

	check( 'U3. the badges stand in the head of the card at the right, in a block of their own beside the title and not in it; the name is cut by the stylesheet - its whole text is the title of the name - and all the badges of a card are in that block',
		badges !== undefined && head('nino').children[0].className.indexOf('themes-card-title') !== -1 && head('nino').children[head('nino').children.length - 1] === badges && w.byClass( card('nino'), 'nino-admin-badge' ).length === w.byClass( badges, 'nino-admin-badge' ).length && w.byClass( badges, 'nino-admin-badge' ).length >= 2
		&& w.byClass( w.byTag( card('nino'), 'h3' )[0], 'nino-admin-badge' ).length === 0 && w.byClass( card('craft'), 'themes-card-name' )[0].title === 'Craft'
		&& w.byClass( card('own:lindner'), 'themes-card-badges' )[0].children.length >= 1 );

	const apply = findAll( card('craft'), function( node ) { return node.dataset.apply === 'craft' } )[0];

	fire( apply, 'click' );

	check( 'U4. the button on the card says Apply and nothing more, and the question that follows has the whole sentence for its answer',
		w.text( apply ) === words.en['/_admin/themes/card/apply'] && words.en['/_admin/themes/card/apply'] !== words.en['/_admin/themes/label/apply'] && findAll( w.dialogs[w.dialogs.length - 1], function( node ) { return node.tagName === 'BUTTON' && node.textContent === words.en['/_admin/themes/label/apply'] } ).length === 1 );

	const link = w.byClass( card('craft'), 'themes-card-link' )[0];
	const nested = findAll( card('craft'), function( node ) { return node.tagName === 'A' } );
	const buttons = findAll( card('craft'), function( node ) { return node.tagName === 'BUTTON' } );

	check( 'U5. the whole card is one link named by the theme: one link in the card, the name inside it, no link or button inside another, the buttons of the card stand after it in the order of the document - so the Tab key takes the link first - and a press on the link opens the editor',
		nested.length === 1 && nested[0] === link && w.text( link ) === 'Craft' && link.href === '#themes/craft' && findAll( link, function( node ) { return node.tagName === 'A' || node.tagName === 'BUTTON' } ).length === 0 && buttons.length >= 2
		&& buttons.every( function( button ) { return findAll( card('craft'), function( node ) { return node === link || node === button } )[0] === link } ) && w.byClass( card('craft'), 'nino-admin-btn-primary' ).length === 0 && ( fire( link, 'click' ), w.state.hash === '#themes/craft' ) );

	const css = require('fs').readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );

	check( 'U5. the area of the link is the card: the stylesheet stretches it over the card, and what is pressed on its own - the buttons, the texts a frame reads - stands over it',
		/\.themes-card-link::after\s*\{[^}]*position:\s*absolute;[^}]*inset:\s*0/.test( css ) === true && /\.themes-card\s*\{[^}]*position:\s*relative/.test( css ) === true && /\.themes-card \.nino-admin-actions[\s\S]{0,160}z-index:\s*1/.test( css ) === true );

	check( 'U6. the theme the website shows is the card that says so - a mark on the card itself - and it is the first of the list; the others have none',
		card('nino').dataset.active === 'true' && cards[0] === card('nino') && cards.filter( function( one ) { return one.dataset.active === 'true' } ).length === 1
		&& /\.themes-card\[data-active="true"\]\s*\{[^}]*border:\s*2px solid var\(--editor-blue\)[^}]*background:/.test( css ) === true );
}

{
	// the theme of a package, and an own one: the name stands in the context bar
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino' );

	const bar = w.byClass( w.theme, 'nino-admin-contextbar' )[0];

	check( 'B1. the head of the theme is gone: the name stands in the context bar, after the way back, with its version quietly beside it - and the badge Active where the website shows the theme',
		w.byClass( w.theme, 'themes-head' ).length === 0 && bar !== undefined && bar.children[0].className.indexOf('nino-admin-back-link') !== -1 && w.byClass( bar, 'themes-name' ).length === 1 && w.byClass( bar, 'themes-name' )[0].className.indexOf('nino-admin-contextbar-title') !== -1
		&& w.text( w.byClass( bar, 'themes-name' )[0] ) === 'Nino' && w.byClass( bar, 'nino-admin-contextbar-code' )[0].textContent === '1.0.0' && w.byClass( bar, 'nino-admin-badge' ).map( w.text ).join() === words.en['/_admin/themes/chip/active'] );

	const own = world( { active : 'nino' } );

	own.at( '#themes/own.lindner' );

	const ownBar = own.byClass( own.theme, 'nino-admin-contextbar' )[0];

	check( 'B1. an own theme says so with a badge in the bar, has no version, and does not have the badge Active where another theme is on the website',
		own.byClass( own.theme, 'themes-head' ).length === 0 && own.byClass( ownBar, 'nino-admin-contextbar-code' ).length === 0 && own.byClass( ownBar, 'nino-admin-badge' ).map( own.text ).join() === words.en['/_admin/themes/chip/own'] );
}

{
	const en = world( { active : 'nino' } );
	const de = world( { lang : 'de-DE', active : 'nino' } );

	en.at( '#themes/nino/groups' );
	de.at( '#themes/nino/groups' );

	const rowOf = function( w ) { return w.byClass( w.theme, 'themes-groups' )[0].children.map( function( item ) { return item.children[0] } ).find( function( row ) { return row.dataset.group === 'page' } ) };

	check( 'B2. the part for the page is the page base in every place that names it: the row of the groups, the title of the group, the sentence of the dialog of the name - in German Seitenbasis, in English Page base',
		words.de['/_admin/themes/word/page'] === 'Seitenbasis' && words.en['/_admin/themes/word/page'] === 'Page base' && en.text( rowOf( en ) ).indexOf('Page base') !== -1 && de.text( rowOf( de ) ).indexOf('Seitenbasis') !== -1
		&& en.text( rowOf( en ) ).indexOf('Whole page') === -1 && Object.keys( words.de ).concat( Object.keys( words.en ) ).every( function( key ) { return String( words.de[key] ?? '' ).indexOf('Gesamte Seite') === -1 && String( words.en[key] ?? '' ).indexOf('Whole page') === -1 } ) );
}

{
	const w = world( { active : 'nino' } );

	w.at( '#themes/nino/section/section-wrap' );

	const back = w.byClass( w.theme, 'nino-admin-back-link' ).find( function( link ) { return link.id === 'themes-back-groups' } );
	const title = w.byClass( w.theme, 'themes-title' )[0];

	check( 'B3. the title of a group stands behind the way back in one row: the link and then the title, the two the children of one block',
		back !== undefined && title !== undefined && back.parentNode === title.parentNode && back.parentNode.className.indexOf('themes-subhead') !== -1 && back.parentNode.children[0] === back && back.parentNode.children[1] === title );

	w.state.hash = '#themes/nino/brand';
	w.panel.showCurrent();

	const brandBack = w.byClass( w.theme, 'nino-admin-back-link' ).find( function( link ) { return link.id === 'themes-back-groups' } );

	check( 'B3. and so does the title of the brand', brandBack !== undefined && brandBack.parentNode.className.indexOf('themes-subhead') !== -1 && brandBack.parentNode.children[1].className.indexOf('themes-title') !== -1 );
}

{
	const en = world( { active : 'nino' } );
	const heads = ( function( w ) { w.at( '#themes/nino/section/section-wrap' ); return w.byClass( w.theme, 'nino-admin-accordion-button' ) } )( en );

	fire( heads[2], 'click' );

	check( 'B4. a component that has no knobs in the theme says so in a few words - "Has no knobs in this theme", in German "Hat in diesem Theme keine Regler" - and not the sentence about the brand and the page',
		words.en['/_admin/themes/hint/follows'] === 'Has no knobs in this theme' && words.de['/_admin/themes/hint/follows'] === 'Hat in diesem Theme keine Regler' && en.text( en.theme ).indexOf('Has no knobs in this theme') !== -1 && en.text( en.theme ).indexOf('follows Brand') === -1 );
}

// --- AB. the second round on the overview: the height of the cards, the words of the badges, the lines of help ------------------------------

{
	const css = require('fs').readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );
	const grid = /#themes-app \.themes-cards \{([^}]*)\}/.exec( css )[1];

	check( 'N1. the cards are as high as what they say: the rows of the grid are as high as their cards - no fixed height of a row - the cards of a row fill it, and the buttons stand at the foot',
		/grid-auto-rows/.test( grid ) === false && /grid-template-rows/.test( grid ) === false && /align-items:\s*stretch/.test( grid ) === true && /\.themes-card \.nino-admin-actions \{[^}]*margin:\s*auto 0 0/.test( css ) === true && /grid-auto-rows:\s*1fr/.test( css ) === false );

	const en = world( { active : 'nino' } );
	const de = world( { lang : 'de-DE', active : 'nino' } );

	en.at( '#themes' );
	de.at( '#themes' );

	const badgesOf = function( w, key ) { return w.byClass( w.byTag( w.list, 'article' ).find( function( card ) { return card.dataset.theme === key } ), 'nino-admin-badge' ) };
	const shortWords = function( w, key ) { return badgesOf( w, key ).map( function( badge ) { return badge.textContent } ).join() };

	check( 'N2. the badges say their word short: Shipped, Catalogue, Uploaded, Own - Mitgeliefert, Katalog, Hochgeladen, Eigenes - and each has the whole of it as its title, so that the name has the room',
		shortWords( en, 'nino' ) === 'Active,Shipped' && shortWords( en, 'rest' ).indexOf('Catalogue') !== -1 && shortWords( en, 'craft' ) === 'Uploaded' && shortWords( en, 'own:lindner' ) === 'Own'
		&& shortWords( de, 'nino' ) === 'Aktiv,Mitgeliefert' && shortWords( de, 'rest' ).indexOf('Katalog') !== -1 && shortWords( de, 'craft' ) === 'Hochgeladen' && shortWords( de, 'own:lindner' ) === 'Eigenes'
		&& [ 'nino', 'rest', 'craft', 'own:lindner' ].every( function( key ) { return badgesOf( en, key ).every( function( badge ) { return typeof badge.title === 'string' && badge.title !== '' } ) } )
		&& badgesOf( en, 'nino' )[1].title === words.en['/_admin/themes/chip/title/shipped'] && badgesOf( en, 'nino' )[1].title !== badgesOf( en, 'nino' )[1].textContent && badgesOf( de, 'nino' )[1].title === 'Mit Themes geliefert' );

	const helps = function( w ) { return w.byClass( w.list, 'nino-admin-help-text' ).map( function( node ) { return node.textContent } ) };

	check( 'N3. the sentence under the list stands behind a (?) beside the search, and nowhere as an open line: with a theme on the website the sentence of the list, with none the other',
		helps( en ).indexOf( words.en['/_admin/themes/hint/list'] ) !== -1 && helps( en ).indexOf( words.en['/_admin/themes/hint/list-none'] ) === -1 && en.byClass( en.byClass( en.list, 'nino-admin-filterbar' )[0], 'nino-admin-help-button' ).length === 1 && en.byClass( en.list, 'nino-admin-hint' ).every( function( node ) { return node.textContent !== words.en['/_admin/themes/hint/list'] } ) );

	const none = world( { active : '' } );

	none.at( '#themes' );
	check( 'N3. ...and with no theme on the website it is the other sentence', helps( none ).indexOf( words.en['/_admin/themes/hint/list-none'] ) !== -1 && helps( none ).indexOf( words.en['/_admin/themes/hint/list'] ) === -1 );

	const group = world( { active : 'nino' } );

	group.at( '#themes/nino/section/section-wrap' );
	const heading = group.byClass( group.theme, 'themes-title' )[0];

	check( 'T9. the title of a group has no (?) and the screen of a group has no sentence of help of its own: the heading is the words of the group alone',
		group.byClass( heading, 'nino-admin-help-button' ).length === 0 && group.byClass( group.theme, 'nino-admin-help-text' ).length === 0 && words.en['/_admin/themes/hint/group'] === undefined && words.de['/_admin/themes/hint/group'] === undefined );

	group.state.hash = '#themes/nino/page/type';
	group.panel.showCurrent();
	check( 'T9. nor has the title of the page base', group.byClass( group.byClass( group.theme, 'themes-title' )[0], 'nino-admin-help-button' ).length === 0 && words.en['/_admin/themes/hint/page'] === undefined && words.de['/_admin/themes/hint/page'] === undefined );

	group.state.hash = '#themes/nino/brand';
	group.panel.showCurrent();
	check( 'N3. and so does the brand: its sentence behind the (?) at its title, none as an open line',
		group.byClass( group.byClass( group.theme, 'themes-title' )[0], 'nino-admin-help-button' ).length === 1 && group.byClass( group.theme, 'nino-admin-help-text' ).map( function( node ) { return node.textContent } ).join() === words.en['/_admin/themes/hint/brand']
		&& group.byClass( group.theme, 'nino-admin-hint' ).every( function( node ) { return node.textContent !== words.en['/_admin/themes/hint/brand'] } ) );
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

	check( 'F296. the words of Themes say "Kopfbereich", "Fußbereich" and "Kopf-/Fußbereich" in German and "header", "footer" and "header/footer" in English: no text of the panel and no line of its manual has "Rahmen", "Header" or "Footer" in German or "frame" in English for the head and the foot of a page, but the exceptions that are named here - and both languages have the same keys',
		offending('de_DE').length === 0 && offending('en_US').length === 0 && keysOf('de_DE') === keysOf('en_US') && /Kopf-\/Fußbereich/.test( fs.readFileSync( path.join( featureDir, 'text/de_DE.php' ), 'utf8' ) ) === true );
}

// --- AC. the second look of the owner at the overview: the name, the bar, delete, edit, the filter, the icons, the screens of a theme ---------------------------------

{
	const en = world( { active : 'nino' } );
	const de = world( { lang : 'de-DE', active : 'nino' } );

	en.at( '#themes' );
	de.at( '#themes' );

	const cardOf = function( w, key ) { return w.byTag( w.list, 'article' ).find( function( one ) { return one.dataset.theme === key } ) };
	const shown = function( w ) { return w.byTag( w.list, 'article' ).filter( function( one ) { return one.hidden !== true } ).map( function( one ) { return one.dataset.theme } ).join() };
	const classes = function( node ) { return String( node.className ).split(/\s+/) };

	check( 'T1. the panel is called Theme in the rail and over the pane, in both languages, and the first tab of the pane - the list - keeps the word Themes',
		words.en['/_admin/themes/nav'] === 'Theme' && words.de['/_admin/themes/nav'] === 'Theme' && words.en['/_admin/themes/tab/list/name'] === 'Themes' && words.de['/_admin/themes/tab/list/name'] === 'Themes' );

	const last = en.list.children[en.list.children.length - 1];

	check( 'T2. the button that chooses a package stands in the bar at the foot of the list - the bar of the workbench, with the main button - and not above the cards',
		classes( last ).indexOf('nino-admin-actionbar') !== -1 && en.byClass( last, 'nino-admin-btn-primary' ).length === 1 && en.byClass( last, 'nino-admin-btn-primary' )[0].id === 'themes-upload'
		&& en.byClass( en.list, 'themes-list-actions' ).length === 0 && findAll( en.list, function( node ) { return node.id === 'themes-upload' } ).length === 1 );

	const own = cardOf( en, 'own:lindner' );
	const deleteOf = function( root ) { return findAll( root, function( node ) { return node.dataset.delete !== undefined } ) };

	check( 'T3. an own theme has a button to delete it on its card - with an icon, named by the theme - and a theme of a package, a theme that was uploaded and the theme on the website have none',
		deleteOf( own ).length === 1 && deleteOf( own )[0].attributes['aria-label'] === W( 'card/delete-of' ).replace( '%s', 'Lindner' ) && deleteOf( en.list ).length === 1 );

	const onSite = world( { active : 'own:lindner' } );

	onSite.at( '#themes' );
	check( 'T3. the theme that the website shows is not deleted: it has no button, as the server would say no', deleteOf( onSite.list ).length === 0 );

	fire( deleteOf( own )[0], 'click' );

	const ask = en.dialogs[en.dialogs.length - 1];

	check( 'T3. the question names the theme; its answer, Delete, sends the delete of the editor - the same action - with the theme and the state it was read in',
		en.text( ask ).indexOf( W( 'confirm/delete-title' ).replace( '%s', 'Lindner' ) ) !== -1 );

	fire( findAll( ask, function( node ) { return node.tagName === 'BUTTON' && node.textContent === words.en['/_admin/themes/label/delete'] } )[0], 'click' );

	check( 'T3. ...one request: themes/delete of the own theme, on its stamp', en.requests.length === 1 && en.requests[0].action === 'themes/delete' && en.requests[0].payload.theme === 'own:lindner' && typeof en.requests[0].payload.base === 'string' );

	const pressed = en.byTag( en.list, 'article' ).map( function( card ) {
		const button = findAll( card, function( node ) { return node.tagName === 'BUTTON' && node.dataset.editbutton === card.dataset.theme } )[0];

		if( button === undefined )
			return false;

		en.state.hash = '#themes';
		fire( button, 'click' );

		return button.textContent === words.en['/_admin/themes/card/edit'] && en.state.hash === '#themes/'+ card.dataset.theme.replace( ':', '.' );
	} );

	en.state.hash = '#themes';
	check( 'T4. every card has a button to edit with the word Edit, beside the link of the card, and it leads to the address the link has', pressed.length === 4 && pressed.every( function( one ) { return one === true } ) && words.de['/_admin/themes/card/edit'] === 'Bearbeiten' );

	const select = en.byTag( en.list, 'select' ).find( function( one ) { return one.parentNode !== null && classes( one.parentNode ).indexOf('themes-from') !== -1 } );
	const bar = en.byClass( en.list, 'nino-admin-filterbar' )[0];

	check( 'T5. a choice of the origin stands over the list at the left of the search, in the bar of the filter, with the origins the site has - all, own, uploaded, catalogue, shipped - and none of the others; all is the one that is chosen',
		select !== undefined && select.children.map( function( option ) { return option.value } ).join() === 'all,own,uploaded,catalogue,shipped' && select.value === 'all' && bar.children[0] === select.parentNode
		&& select.children.map( function( option ) { return option.textContent } ).join() === 'All,Own themes,Uploaded themes,Catalogue themes,Shipped' && shown( en ) === 'nino,rest,craft,own:lindner' );

	const asked = en.requests.length;

	select.value = 'own';
	fire( select, 'change' );

	check( 'T5. the choice filters at once, with no request, and says so in the address', shown( en ) === 'own:lindner' && decodeURIComponent( en.state.hash ) === '#themes/@own' && en.requests.length === asked );

	const search = en.byTag( en.list, 'input' ).find( function( one ) { return one.id === 'themes-search' } );

	search.value = 'zzz';
	fire( search, 'input' );
	check( 'T5. a search that finds nothing hides the cards and shows a quiet sentence', shown( en ) === '' && en.byClass( en.list, 'themes-nothing' )[0].hidden !== true );

	search.value = 'lind';
	fire( search, 'input' );
	check( 'T5. the search is of the name and goes with the choice: what the choice leaves and the name has is shown, the sentence is not', shown( en ) === 'own:lindner' && en.byClass( en.list, 'themes-nothing' )[0].hidden === true );

	search.value = '';
	select.value = 'all';
	fire( select, 'change' );
	fire( search, 'input' );

	check( 'T5. all, with nothing to search, shows every card - the theme of the website first - and writes the address of the list as it was; the words of the origins are German in German',
		shown( en ) === 'nino,rest,craft,own:lindner' && en.state.hash === '#themes' && en.byTag( en.list, 'article' )[0].dataset.theme === 'nino' && words.de['/_admin/themes/list/from/own'] === 'Eigene Themes' && words.de['/_admin/themes/list/from/shipped'] === 'Mitgeliefert' );

	const actions = findAll( cardOf( en, 'craft' ), function( node ) { return classes( node ).indexOf('themes-card-actions') !== -1 } )[0];
	const kinds = actions.children.map( function( node ) { return node.tagName === 'BUTTON' ? ( node.dataset.editbutton !== undefined ? 'edit' : ( node.dataset.apply !== undefined ? 'apply' : 'button' ) ) : 'icons' } ).join();
	const icons = findAll( actions, function( node ) { return classes( node ).indexOf('themes-card-icons') !== -1 } )[0];

	check( 'T6. the buttons of a card are Edit and Apply with their words, then the icons - duplicate, and remove or delete - each with no word, the name of the theme in its name and in its title: edit, apply, duplicate, remove',
		kinds === 'edit,apply,icons' && icons.children.map( function( node ) { return node.dataset.duplicate !== undefined ? 'duplicate' : ( node.dataset.remove !== undefined ? 'remove' : ( node.dataset.delete !== undefined ? 'delete' : '?' ) ) } ).join() === 'duplicate,remove'
		&& icons.children.every( function( node ) { return node.textContent.trim() === '' && node.attributes['aria-label'] !== '' && node.title === node.attributes['aria-label'] && node.attributes['aria-label'].indexOf('Craft') !== -1 } ) );
}

{
	const lindner = world( { active : 'nino' } );

	lindner.at( '#themes/own.lindner/css', { listExtra : { limits : { css : 131072, name : 60, key : 40 } } } );

	const area = findAll( lindner.theme, function( node ) { return node.id === 'themes-css' } )[0];
	const hint = findAll( lindner.theme, function( node ) { return node.id === 'themes-css-hint' } )[0];

	check( 'T7. the css of a theme is called Theme CSS - Theme-CSS - the head of its section, and it is not named a second time over the field; the line under the field says how much it takes and that it stays with the theme, the number read from the limit',
		words.en['/_admin/themes/label/css'] === 'Theme CSS' && words.de['/_admin/themes/label/css'] === 'Theme-CSS' && lindner.byClass( lindner.theme, 'nino-admin-accordion-label' ).map( lindner.text ).indexOf('Theme CSS') !== -1
		&& area.parentNode.children.filter( function( node ) { return node.tagName === 'SPAN' } ).length === 0 && lindner.text( area.parentNode ).indexOf('Theme CSS') === -1
		&& classes_( hint ).indexOf('nino-admin-hint') !== -1 && lindner.text( hint ).indexOf( 'At most 128 KB, stays with the theme.' ) === 0 );

	function classes_( node ) { return String( node.className ).split(/\s+/) }

	const group = world( { active : 'nino' } );

	group.at( '#themes/nino/section/section-wrap' );

	const select = findAll( group.theme, function( node ) { return node.tagName === 'SELECT' && node.id === 'themes-part-section' } )[0];

	check( 'T8. the choice of the part says where it is used from - Use from, Verwenden aus - and no longer names the group in its label, which the title says',
		words.en['/_admin/themes/label/part-of'] === 'Use from' && words.de['/_admin/themes/label/part-of'] === 'Verwenden aus' && group.text( select.parentNode ).indexOf( 'Use from' ) === 0 && group.text( select.parentNode ).indexOf( 'Section' ) === -1 );

	const script = require('fs').readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
	const sheet = require('fs').readFileSync( path.join( featureDir, 'assets/admin.css' ), 'utf8' );

	check( 'T10. the names of fields are the workbench\'s class for them - nino-admin-field-name, which has the size, the weight and the colour of the other panels - and none is the class that the workbench has no rule for; the one rule of the sheet is for the same class',
		script.indexOf('nino-admin-field-label') === -1 && sheet.indexOf('nino-admin-field-label') === -1 && ( script.match( /nino-admin-field-name/g ) ?? [] ).length === 4 && /\.nino-admin-accordion-body \.nino-admin-field-name \{/.test( sheet ) === true );
}

done();
