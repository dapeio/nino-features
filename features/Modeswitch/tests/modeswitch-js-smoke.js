/**
 *	Nino
 *	modeswitch-js-smoke.js	What modeswitch.js does over a dom stand-in: which
 *													of the three it starts on, what each of them
 *													writes on the root element, that the middle one
 *													writes nothing at all, that the choice survives a
 *													reload and that a browser refusing storage gets a
 *													switch that works for the visit instead of one
 *													that throws.
 *
 *													No jsdom, no dependency: the same element stand-in
 *													the other feature tests build, so this runs with
 *													nothing but node.
 *
 *	Usage: node features/Modeswitch/tests/modeswitch-js-smoke.js
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

/**
 *	One element, with just enough of the interface modeswitch.js reaches for
 */
function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		classes			: {},
		hidden			: true,
		children		: [],
		getAttribute	: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute	: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
	};

	el.classList = {
		toggle : function( name, on ) { if( on === true ) el.classes[name] = true; else delete el.classes[name] },
	};

	// A click lands on whatever was clicked; closest() walks back up to the
	// button, which is exactly what the delegated listener relies on
	el.closest = function( selector ) {
		if( selector === '.nino-modeswitch-btn' )
			return el.attributes['data-mode'] !== undefined ? el : ( el.parent && el.parent.closest ? el.parent.closest( selector ) : null );
		return null;
	};

	return el;
}

/**
 *	A page with one switch on it, the script loaded over it, and the handful
 *	of hooks a test needs back
 *
 *	@param		{Object}	options		{ stored, storageThrows, readyState }
 */
function page( options ) {

	options = options || {};

	const store = {};
	if( typeof options.stored === 'string' )
		store['nino-mode'] = options.stored;

	const buttons = [ 'light', 'system', 'dark' ].map( function( mode ) { return element( { 'data-mode' : mode, 'aria-pressed' : 'false' } ) } );
	const group = element( {} );
	group.children = buttons;
	buttons.forEach( function( b ) { b.parent = group } );

	const root = element( {} );
	const listeners = {};

	const dc = {
		documentElement	: root,
		readyState			: options.readyState || 'complete',
		querySelectorAll	: function( selector ) {
			if( selector === '.nino-modeswitch' ) return [ group ];
			if( selector === '.nino-modeswitch-btn' ) return buttons;
			return [];
		},
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const storage = {
		getItem : function( key ) {
			if( options.storageThrows === true ) throw new Error('denied');
			return Object.prototype.hasOwnProperty.call( store, key ) ? store[key] : null;
		},
		setItem : function( key, value ) {
			if( options.storageThrows === true ) throw new Error('denied');
			store[key] = String( value );
		},
	};

	const sandbox = { console : console, document : dc, localStorage : storage };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'modeswitch.js' } );

	return {
		root : root, group : group, buttons : buttons, store : store, listeners : listeners,
		mode : function() { return root.getAttribute('data-nino-mode') },
		pressed : function() {
			return buttons.filter( function( b ) { return b.getAttribute('aria-pressed') === 'true' } )
				.map( function( b ) { return b.getAttribute('data-mode') } );
		},
		click : function( mode ) {
			const target = buttons.filter( function( b ) { return b.getAttribute('data-mode') === mode } )[0];
			( listeners['click'] || [] ).forEach( function( fn ) { fn( { target : target } ) } );
		},
	};
}

// --- Where it starts ---------------------------------------------------------

const fresh = page( {} );

check( 'a reader who has never chosen is left on the system setting', fresh.mode() === null );
check( '...and the middle button is the one that reads as on', fresh.pressed().join() === 'system' );
check( 'the switch is unhidden once the script has it', fresh.group.hidden === false );

const dark = page( { stored : 'dark' } );

check( 'a stored choice is on the page before anything is clicked', dark.mode() === 'dark' );
check( '...and painted on the button that holds it', dark.pressed().join() === 'dark' );

const nonsense = page( { stored : 'sepia' } );

check( 'a stored value that is none of the three is no choice at all', nonsense.mode() === null && nonsense.pressed().join() === 'system' );

// --- Choosing ----------------------------------------------------------------

const p = page( {} );

p.click('dark');
check( 'choosing dark writes the attribute', p.mode() === 'dark' );
check( '...remembers it', p.store['nino-mode'] === 'dark' );
check( '...and moves the mark to that button alone', p.pressed().join() === 'dark' );

p.click('light');
check( 'choosing light writes the other one', p.mode() === 'light' && p.store['nino-mode'] === 'light' );

/*	The middle position removes the attribute rather than writing a third
	value: the stylesheet's rule for a reader who chose nothing is a
	prefers-color-scheme query, and that only applies while nothing is written.
	So "system" keeps answering the system setting even when it changes with
	the page open	*/
p.click('system');
check( 'choosing system removes the attribute rather than writing a third value', p.mode() === null );
check( '...and is remembered as a choice all the same', p.store['nino-mode'] === 'system' );
check( '...and still marks its own button', p.pressed().join() === 'system' );

// A reload is a fresh page over the same storage
const again = page( { stored : p.store['nino-mode'] } );
check( 'the choice survives the next page', again.pressed().join() === 'system' );

// --- A browser that refuses storage ------------------------------------------

const refused = page( { storageThrows : true } );

check( 'storage refused is not an error - the switch still starts', refused.mode() === null && refused.pressed().join() === 'system' );

refused.click('dark');
check( '...and still switches, for the length of the visit', refused.mode() === 'dark' && refused.pressed().join() === 'dark' );

// --- Everything else ---------------------------------------------------------

/*	The listener sits on the document, so it sees every click on the page.
	Anything that is not a button - the page itself, a text node, an event
	with no target at all - has to fall through rather than throw	*/
const ignored = page( {} );

( ignored.listeners['click'] || [] ).forEach( function( fn ) {
	fn( { target : ignored.group } );
	fn( { target : null } );
	fn( { target : undefined } );
	fn( { target : {} } );
} );

check( 'a click that is not on a button changes nothing and throws nothing', ignored.mode() === null
	&& ignored.pressed().join() === 'system' );

const early = page( { readyState : 'loading' } );
check( 'the stored choice is applied at parse time, before the dom is ready', early.mode() === null );

const earlyDark = page( { stored : 'dark', readyState : 'loading' } );
check( '...which is what keeps a dark reader from seeing one light paint', earlyDark.mode() === 'dark' );

check( 'one delegated listener, not one per button', ( earlyDark.listeners['click'] || [] ).length === 1 );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
