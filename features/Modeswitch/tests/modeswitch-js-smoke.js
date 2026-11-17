/**
 *	Nino
 *	modeswitch-js-smoke.js		What the two scripts of the feature do over a dom stand-in:
 *														the choice is on the page when the script runs, before the
 *														page is ready (and by the early script before it is painted),
 *														it is set only where the brand leaves the mode to the visitor,
 *														the switch is shown only there and says which button is on,
 *														a click sets the token, stores the choice and says so on
 *														every switch, the system removes what was set, a browser that
 *														refuses storage costs the visit's choice and nothing else, and
 *														a switch that arrives later is painted.
 *
 *														No jsdom, no dependency: a little element stand-in and the
 *														kernel's own Nino.js, which starts the script (Nino.events).
 *														NINO_ROOT names the checkout, else it is three levels up,
 *														where a project keeps it.
 *
 *	Usage: node features/Modeswitch/tests/modeswitch-js-smoke.js
 *	       NINO_ROOT=../nino node features/Modeswitch/tests/modeswitch-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let checks = 0;
let failures = 0;

function check( label, condition ) {
	checks++;
	if( condition === true ) {
		console.log( '  ok  - '+ label );
		return;
	}
	failures++;
	console.log( 'FAIL  - '+ label );
}

const source = fs.readFileSync( path.join( __dirname, '../assets/modeswitch.js' ), 'utf8' );
const early = fs.readFileSync( path.join( __dirname, '../assets/mode-early.js' ), 'utf8' );
const checkout = process.env.NINO_ROOT || path.join( __dirname, '../../..' );
const kernel = fs.readFileSync( path.join( checkout, '_nino/Nino.js' ), 'utf8' );

function element( tag, attributes ) {

	const el = {
		tagName				: tag,
		nodeType			: 1,
		attributes		: Object.assign( {}, attributes || {} ),
		classes				: ( ( attributes || {} )['class'] || '' ).split( /\s+/ ),
		hidden				: Object.prototype.hasOwnProperty.call( attributes || {}, 'hidden' ),
		children			: [],
		parentNode		: null,
		getAttribute	: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute	: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild		: function( child ) { child.parentNode = this; this.children.push( child ); return child },
		matches				: function( selector ) { return selector.charAt( 0 ) === '.' && this.classes.indexOf( selector.slice( 1 ) ) !== -1 },
		closest				: function( selector ) { let node = this; while( node !== null ) { if( node.nodeType === 1 && node.matches( selector ) ) return node; node = node.parentNode } return null },
		querySelector	: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) { return all( this ).filter( function( e ) { return e.matches( selector ) } ) },
	};

	return el;
}

function all( root ) {
	let found = [];
	root.children.forEach( function( child ) { found.push( child ); found = found.concat( all( child ) ) } );
	return found;
}

function modeSwitch() {
	const el = element( 'div', { 'class' : 'mode-switch', 'hidden' : '' } );
	[ 'light', 'system', 'dark' ].forEach( function( mode ) { el.appendChild( element( 'button', { 'class' : 'mode-switch-button', 'data-mode' : mode, 'aria-pressed' : 'false' } ) ) } );
	return el;
}

/**
 *	A page with the scripts loaded over it
 *
 *	@param		{Object}	options		{ brand: the token as the style sheets give it, stored: what localStorage holds, refuse: storage throws, only: run the early script alone }
 */
function page( elements, options ) {

	const root = element( 'html', {} );
	const body = element( 'body', {} );
	root.appendChild( body );
	elements.forEach( function( e ) { body.appendChild( e ) } );

	// The style of the root element: a property set on it wins over the style sheets, as it does in a browser
	const set = {};
	root.style = {
		getPropertyValue	: function( name ) { return Object.prototype.hasOwnProperty.call( set, name ) ? set[name] : '' },
		setProperty				: function( name, value ) { set[name] = value },
		removeProperty		: function( name ) { delete set[name] },
	};

	const store = options.stored === undefined ? {} : { 'nino-mode' : options.stored };
	const listeners = {};
	const timeouts = [];
	let observer = null;

	const dc = {
		documentElement	: root,
		body						: body,
		readyState			: 'complete',
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		removeEventListener	: function() {},
		querySelector			: function( selector ) { return body.querySelector( selector ) },
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
	};

	const sandbox = {
		console : console, document : dc, location : { protocol : 'https:', hash : '' },
		navigator : { userAgent : 'node' },
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		requestAnimationFrame : function() {},
		setTimeout : function( fn ) { timeouts.push( fn ); return timeouts.length },
		getComputedStyle : function() { return { getPropertyValue : function( name ) { return name === '--brand-scheme' ? ( set[name] !== undefined ? set[name] : options.brand ) : '' } } },
		localStorage : {
			getItem		: function( key ) { if( options.refuse === true ) throw new Error( 'refused' ); return Object.prototype.hasOwnProperty.call( store, key ) ? store[key] : null },
			setItem		: function( key, value ) { if( options.refuse === true ) throw new Error( 'refused' ); store[key] = value },
		},
		MutationObserver : function( callback ) { observer = callback; this.observe = function() {} },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( kernel, context, { filename : 'Nino.js' } );
	vm.runInContext( options.only === true ? early : source, context, { filename : options.only === true ? 'mode-early.js' : 'modeswitch.js' } );

	const started = function() {
		while( timeouts.length > 0 )
			timeouts.shift()();
	};

	return {
		root : root, body : body, store : store, started : started,
		token : function() { return root.style.getPropertyValue( '--brand-scheme' ) },
		click : function( button ) { ( listeners['click'] || [] ).forEach( function( fn ) { fn( { target : button } ) } ) },
		pressed : function( el ) { return el.querySelectorAll( '.mode-switch-button' ).map( function( b ) { return b.getAttribute( 'aria-pressed' ) === 'true' ? b.getAttribute( 'data-mode' ) : '' } ).join( '' ) },
		arrive : function( el ) { body.appendChild( el ); observer( [ { addedNodes : [ el ] } ] ) },
	};
}

// --- The choice is on the page, where the brand leaves the mode open --------------

const early1 = page( [], { brand : 'light dark', stored : 'dark', only : true } );
check( 'the early script sets the token of a stored dark choice, with nothing but the stored value and the brand to go on', early1.token() === 'dark' );
check( 'it leaves the mode alone for the system, for no choice and for a value that is none of the three', [ 'system', undefined, 'sepia', '' ].every( function( stored ) { return page( [], { brand : 'light dark', stored : stored, only : true } ).token() === '' } ) );
check( 'it leaves a brand that decided alone, and a browser that refuses storage is the system', page( [], { brand : 'light', stored : 'dark', only : true } ).token() === '' && page( [], { brand : 'dark', stored : 'light', only : true } ).token() === ''
	&& page( [], { brand : 'light dark', stored : 'dark', refuse : true, only : true } ).token() === '' );

const control = modeSwitch();
const onLoad = page( [ control ], { brand : 'light dark', stored : 'dark' } );
check( 'the script of the bundle puts the choice on the page when it runs, before the page is ready', onLoad.token() === 'dark' && control.hidden === true );
onLoad.started();
check( 'once the page is ready the switch is shown and the button of the choice is the one that is on', control.hidden === false && onLoad.pressed( control ) === 'dark' );

check( 'the script of the bundle takes only light, dark and the system from the store: any other string sets no token, whatever the brand', [ 'evil', 'dark;x', ' dark', '', 'DARK', '--x: 1' ].every( function( stored ) {
	const odd = modeSwitch();
	const run = page( [ odd ], { brand : 'light dark', stored : stored } );
	run.started();
	return run.token() === '' && run.pressed( odd ) === 'system';
} ) );

// --- A brand that decided has no switch -------------------------------------------

const fixed = modeSwitch();
const decided = page( [ fixed ], { brand : 'light', stored : 'dark' } );
decided.started();
check( 'where the brand says light, the switch stays hidden and the stored choice sets nothing', fixed.hidden === true && decided.token() === '' );
decided.click( fixed.querySelector( '.mode-switch-button' ) );
check( 'a click that nothing should have received (the switch is hidden) sets nothing there either', decided.token() === '' && fixed.hidden === true );

// --- A click ----------------------------------------------------------------------

const header = modeSwitch();
const footer = modeSwitch();
const live = page( [ header, footer ], { brand : 'light dark' } );
live.started();
check( 'with no choice made the system is the one that is on, on every switch', live.pressed( header ) === 'system' && live.pressed( footer ) === 'system' && live.token() === '' );
live.click( header.querySelectorAll( '.mode-switch-button' )[2] );
check( 'a click on dark sets the token, stores the choice and says so on both switches', live.token() === 'dark' && live.store['nino-mode'] === 'dark' && live.pressed( header ) === 'dark' && live.pressed( footer ) === 'dark' );
live.click( footer.querySelectorAll( '.mode-switch-button' )[1] );
check( 'a click on the system removes what was set, and the choice is the system again', live.token() === '' && live.store['nino-mode'] === 'system' && live.pressed( header ) === 'system' );
live.click( header );
check( 'a click on anything that is no button of a switch does nothing', live.store['nino-mode'] === 'system' && live.token() === '' );

// --- Storage that is refused ------------------------------------------------------

const closed = modeSwitch();
const refused = page( [ closed ], { brand : 'light dark', refuse : true } );
refused.started();
refused.click( closed.querySelectorAll( '.mode-switch-button' )[0] );
check( 'a browser that refuses storage still switches for the length of the visit', refused.token() === 'light' && refused.pressed( closed ) === 'light' );

// --- A switch that arrives later --------------------------------------------------

const later = page( [], { brand : 'light dark', stored : 'light' } );
later.started();
const arrived = modeSwitch();
later.arrive( arrived );
check( 'a switch that is added to the page afterwards is shown and painted at once', arrived.hidden === false && later.pressed( arrived ) === 'light' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
