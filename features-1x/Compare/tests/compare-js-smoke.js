/**
 *	Nino
 *	compare-js-smoke.js		What compare.js does over a dom stand-in: that it takes
 *												the control out of hiding and says so with the class
 *												the stylesheet switches layout on, that it writes the
 *												control's value into the one custom property the
 *												clipping reads, that a value outside the control's
 *												own range is brought back into it, and that a pair
 *												with no control in it is left exactly as it was.
 *
 *												No jsdom, no dependency: the same element stand-in the
 *												other feature tests build, so this runs with nothing
 *												but node - and the kernel's own Nino.js, which the
 *												script starts through (Nino.events): NINO_ROOT names
 *												the checkout, else it is three levels up, where a
 *												project keeps it.
 *
 *	Usage: node features/Compare/tests/compare-js-smoke.js
 *	       NINO_ROOT=../nino node features/Compare/tests/compare-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/compare.js' ), 'utf8' );
const checkout = process.env.NINO_ROOT || path.join( __dirname, '../../..' );
const kernel = fs.readFileSync( path.join( checkout, '_nino/Nino.js' ), 'utf8' );

/**
 *	One element, with just enough of the interface compare.js reaches for
 */
function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		classes			: {},
		properties	: {},
		hidden			: true,
		children		: [],
		listeners		: {},
		value				: ( attributes || {} ).value,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		querySelector		: function( selector ) {
			return this.children.filter( function( c ) { return c.matchesClass( selector.replace( /^\./, '' ) ) } )[0] || null;
		},
		matchesClass		: function( name ) {
			return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1;
		},
	};

	el.classList = { add : function( name ) { el.classes[name] = true } };
	el.style = { setProperty : function( name, value ) { el.properties[name] = value } };

	return el;
}

/**
 *	One pair as the shortcode writes it, the script loaded over it, and the
 *	handful of hooks a test needs back
 *
 *	@param		{Object}	options		{ value, withRange, readyState, hold }
 */
function page( options ) {

	options = options || {};

	const pair = element( { 'class' : 'nino-compare nino-compare--4-3' } );

	const range = element( {
		'class' : 'nino-compare-range',
		'value'	: options.value !== undefined ? options.value : '50',
	} );

	if( options.withRange !== false )
		pair.children.push( range );

	const listeners = {};
	const timeouts = [];

	const dc = {
		readyState			: options.readyState || 'complete',
		querySelector		: function() { return null },
		querySelectorAll	: function( selector ) { return selector === '.nino-compare' ? [ pair ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	// What Nino.js needs to load and to start its callbacks: a navigator, the
	// events of the window and a timer it asks for a page that is ready already
	const sandbox = {
		console : console, document : dc,
		navigator : { userAgent : 'node' },
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		setTimeout : function( fn ) { timeouts.push( fn ); return timeouts.length },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( kernel, context, { filename : 'Nino.js' } );
	vm.runInContext( source, context, { filename : 'compare.js' } );

	// A page that is ready when the scripts run starts the callbacks a moment
	// later, from the timer Nino.js asked for; one still loading starts them
	// when the document says so. What a callback throws is thrown again from a
	// timer, and that one is run here too - a script that fails fails the run
	const start = function() {
		while( timeouts.length > 0 )
			timeouts.shift()();
	};

	if( dc.readyState !== 'loading' && options.hold !== true )
		start();

	return {
		pair : pair, range : range, listeners : listeners, start : start,
		position : function() { return pair.properties['--nino-compare-position'] },
		move : function( value ) {
			range.value = value;
			( range.listeners['input'] || [] ).forEach( function( fn ) { fn( {} ) } );
		},
	};
}


// --- Taking a pair over --------------------------------------------------------

const middle = page( {} );

check( 'the control comes out of hiding, because now there is something behind it', middle.range.hidden === false );
check( '...and the pair says so with the class the stylesheet switches layout on', middle.pair.classes['nino-is-ready'] === true );
check( 'the starting position is written where the clipping reads it', middle.position() === '50%' );


// --- Following the control -----------------------------------------------------

middle.move( '20' );
check( 'moving the control moves the divider', middle.position() === '20%' );

middle.move( '0' );
check( '...all the way to one end', middle.position() === '0%' );

middle.move( '100' );
check( '...and to the other', middle.position() === '100%' );


// --- A value that is not one ---------------------------------------------------

const started = page( { value : '35' } );
check( 'a starting position written into the markup is taken as it stands', started.position() === '35%' );

started.move( '140' );
check( 'a value past the end is brought back to it rather than written through', started.position() === '100%' );

started.move( '-20' );
check( '...and one before the start likewise', started.position() === '0%' );

started.move( 'links' );
check( 'and one that is not a number changes nothing at all', started.position() === '0%' );


// --- A pair that is not one ----------------------------------------------------

const empty = page( { withRange : false } );

check( 'a pair with no control in it is left exactly as it was - two pictures under one another',
	empty.pair.classes['nino-is-ready'] === undefined && empty.position() === undefined );


// --- Starting with the kernel --------------------------------------------------

const held = page( { hold : true } );

check( 'the control comes out of hiding when Nino.js calls its ready callbacks, and not when the script is loaded',
	held.range.hidden === true && held.pair.classes['nino-is-ready'] === undefined );

held.start();
check( '...and then it does, and the pair says so', held.range.hidden === false && held.pair.classes['nino-is-ready'] === true );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.range.hidden === true );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
// What a callback throws is thrown again from a timer, and that one is run here too
late.start();
check( '...and picks the pairs up when it arrives', late.range.hidden === false && late.position() === '50%' );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
