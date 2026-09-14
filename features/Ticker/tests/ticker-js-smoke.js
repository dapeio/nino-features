/**
 *	Nino
 *	ticker-js-smoke.js	What ticker.js does over a dom stand-in: how many copies
 *											it makes for a given row and box, that it stops making
 *											them, that the copies are hidden from a screen reader,
 *											what it sets the animation's distance and duration to,
 *											and that a row with nothing in it is left alone.
 *
 *											No jsdom, no dependency: the same element stand-in the
 *											other feature tests build, so this runs with nothing
 *											but node.
 *
 *	Usage: node features/Ticker/tests/ticker-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/ticker.js' ), 'utf8' );

function element( attributes, width ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		children		: [],
		properties	: {},
		width				: width || 0,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { this.children.push( child ); return child },
		cloneNode				: function() { return element( Object.assign( {}, this.attributes ), this.width ) },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) {
			const name = selector.replace( /^\./, '' );
			return this.children.filter( function( c ) { return c.matchesClass( name ) } )[0] || null;
		},
	};

	el.classList = { add : function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() } };
	el.style = { setProperty : function( name, value ) { el.properties[name] = value } };

	// The track's own width is the sum of its children's
	Object.defineProperty( el, 'scrollWidth', {
		get : function() { return el.children.reduce( function( sum, c ) { return sum + c.width }, 0 ) },
	} );

	Object.defineProperty( el, 'clientWidth', { get : function() { return el.width } } );

	return el;
}

/**
 *	One row as a project writes it, the script loaded over it
 *
 *	@param		{Object}	options		{ box, items, speed, direction, readyState, withTrack }
 */
function page( options ) {

	options = options || {};

	const row = element( { 'class' : 'nino-ticker' }, options.box !== undefined ? options.box : 1000 );

	if( options.speed !== undefined ) row.attributes['data-ticker-speed'] = String( options.speed );
	if( options.direction !== undefined ) row.attributes['data-ticker-direction'] = options.direction;

	const track = element( { 'class' : 'nino-ticker-track' }, 0 );

	for( const width of ( options.items !== undefined ? options.items : [ 200, 200, 200 ] ) )
		track.appendChild( element( { 'class' : 'nino-ticker-item' }, width ) );

	if( options.withTrack !== false )
		row.appendChild( track );

	const listeners = {};

	const dc = {
		readyState			: options.readyState || 'complete',
		querySelectorAll	: function( selector ) { return selector === '.nino-ticker' ? [ row ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const sandbox = { console : console, document : dc };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'ticker.js' } );

	return {
		row : row, track : track, listeners : listeners,
		running : function() { return row.matchesClass( 'nino-is-running' ) },
		items : function() { return track.children.length },
		copies : function() { return track.children.filter( function( c ) { return c.getAttribute('aria-hidden') === 'true' } ).length },
		distance : function() { return track.properties['--nino-ticker-distance'] },
		duration : function() { return track.properties['--nino-ticker-duration'] },
	};
}


// --- Copying the row -----------------------------------------------------------

// Three items of 200 = 600 wide, in a box of 1000: the run has to cover
// 1000 + 600, so two more copies of it (1800) are needed
const plain = page( {} );

check( 'the row is copied until it covers the box plus one length of itself', plain.items() === 9 );
check( '...and every copy is hidden from a screen reader - the row is read once', plain.copies() === 6 );
check( 'the row says it is running, which is what the stylesheet starts the animation on', plain.running() === true );

const wide = page( { box : 200, items : [ 300, 300 ] } );
check( 'a row already wider than its box is copied once, not not at all', wide.items() === 4 );

const tiny = page( { box : 4000, items : [ 10 ] } );
check( 'one narrow item in a very wide box stops at the ceiling rather than making a thousand elements', tiny.items() <= 41 );


// --- The animation -------------------------------------------------------------

check( 'the distance is one original length, so the copy ends where the original stood', plain.distance() === '600px' );
check( '...and the duration follows from the speed the row was given', plain.duration() === '15s' );

const quick = page( { speed : 200 } );
check( 'a row with its own speed runs at it', quick.duration() === '3s' );

const nonsense = page( { speed : -5 } );
check( '...and a speed that is not one falls back rather than standing still or running backwards', nonsense.duration() === '15s' );


// --- A row that is not one -----------------------------------------------------

const bare = page( { withTrack : false } );
check( 'a row with no track in it is left exactly as it was', bare.running() === false );

const empty = page( { items : [] } );
check( '...and so is one with nothing to run', empty.running() === false && empty.items() === 0 );

const flat = page( { items : [ 0, 0 ] } );
check( 'a row whose items have no width yet is left alone rather than divided by zero',
	flat.running() === false && flat.distance() === undefined );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.running() === false && ( late.listeners['DOMContentLoaded'] || [] ).length === 1 );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and takes the rows over when it arrives', late.running() === true && late.items() === 9 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
