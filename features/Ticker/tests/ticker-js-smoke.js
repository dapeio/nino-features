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

// The track is a flex row with a gap between its items (see ticker.css), and
// the gap is the whole point of the distance the animation runs: a row of n
// items is n widths plus n-1 gaps wide, while the first copy stands one gap
// further along than that. A stand-in without it cannot tell the two apart
const GAP = 32;

function element( attributes, width ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		children		: [],
		parent			: null,
		properties	: {},
		width				: width || 0,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		removeChild			: function( child ) { this.children = this.children.filter( function( c ) { return c !== child } ); child.parent = null; return child },
		cloneNode				: function() {
			const copy = element( Object.assign( {}, this.attributes ), this.width );
			for( const child of this.children )
				copy.appendChild( child.cloneNode() );
			return copy;
		},
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) {
			const name = selector.replace( /^\./, '' );
			return this.children.filter( function( c ) { return c.matchesClass( name ) } )[0] || null;
		},
		// Enough of a selector to answer what the script asks: '[id]', and the
		// list of things a visitor can tab to
		querySelectorAll	: function( selector ) {
			const wantsId = selector === '[id]';
			const found = [];
			for( const child of this.children ) {
				if( wantsId === true ? child.attributes['id'] !== undefined : child.attributes['href'] !== undefined )
					found.push( child );
				Array.prototype.push.apply( found, child.querySelectorAll( selector ) );
			}
			return found;
		},
	};

	el.classList = { add : function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() } };
	el.properties = {};
	el.measured = 0;
	// Every write of the distance is one measurement of this row, which is
	// how a row that was left running is told from one that was built again
	el.style = { setProperty : function( name, value ) {
		el.properties[name] = value;
		if( name === '--nino-ticker-distance' ) el.measured++;
	} };

	Object.defineProperty( el, 'id', {
		get : function() { return el.attributes['id'] || '' },
	} );

	// The track's own width is the sum of its children's, plus the gap
	// between each pair of them
	Object.defineProperty( el, 'scrollWidth', {
		get : function() {
			const sum = el.children.reduce( function( total, c ) { return total + c.width }, 0 );
			return el.children.length > 1 ? sum + ( el.children.length - 1 ) * GAP : sum;
		},
	} );

	Object.defineProperty( el, 'clientWidth', { get : function() { return el.width } } );
	Object.defineProperty( el, 'offsetWidth', { get : function() { return el.width } } );

	// Where this element sits in its row: everything before it, each width
	// followed by one gap
	Object.defineProperty( el, 'offsetLeft', {
		get : function() {
			if( el.parent === null )
				return 0;
			let left = 0;
			for( const sibling of el.parent.children ) {
				if( sibling === el )
					break;
				left += sibling.width + GAP;
			}
			return left;
		},
	} );

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

	for( const width of ( options.items !== undefined ? options.items : [ 200, 200, 200 ] ) ) {
		const item = track.appendChild( element( { 'class' : 'nino-ticker-item' }, width ) );
		// A logo is usually a link, and a link is usually named - which is
		// what a copy may carry neither of
		if( options.linked !== false )
			item.appendChild( element( { 'href' : '/partner', 'id' : 'logo-' + track.children.length } ) );
	}

	if( options.withTrack !== false )
		row.appendChild( track );

	const listeners = {};

	const dc = {
		readyState			: options.readyState || 'complete',
		querySelectorAll	: function( selector ) { return selector === '.nino-ticker' ? [ row ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const timers = [];

	const sandbox = { console : console, document : dc,
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		setTimeout				: function( fn ) { timers.push( fn ); return timers.length },
		clearTimeout			: function( id ) { timers[id - 1] = null },
	};
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'ticker.js' } );

	return {
		row : row, track : track, listeners : listeners,
		running : function() { return row.matchesClass( 'nino-is-running' ) },
		items : function() { return track.children.length },
		copies : function() { return track.children.filter( function( c ) { return c.getAttribute('aria-hidden') === 'true' } ).length },
		distance : function() { return track.properties['--nino-ticker-distance'] },
		duration : function() { return track.properties['--nino-ticker-duration'] },
		copiedIds : function() { return track.children.slice( 3 ).map( function( c ) { return c.querySelectorAll( '[id]' ).length } ) },
		copiedTabstops : function() { return track.children.slice( 3 ).map( function( c ) { return ( c.children[0] || {} ).attributes['tabindex'] } ) },
		measurements : function() { return track.measured },
		// A window is resized; the rows are not measured again until it has
		// come to rest, which is what settle() is
		resize : function( box ) {
			if( box !== undefined ) row.width = box;
			( listeners['resize'] || [] ).forEach( function( fn ) { fn() } );
		},
		settle : function() { timers.splice( 0 ).forEach( function( fn ) { if( fn !== null ) fn() } ) },
	};
}


// --- Copying the row -----------------------------------------------------------

// Three items of 200 with a gap between them = 664 wide, in a box of 1000:
// the run has to cover 1000 + 664, so two more copies of it are needed
const plain = page( {} );

check( 'the row is copied until it covers the box plus one length of itself', plain.items() === 9 );
check( '...and every copy is hidden from a screen reader - the row is read once', plain.copies() === 6 );
check( 'the row says it is running, which is what the stylesheet starts the animation on', plain.running() === true );

// A copy is the same picture and nothing else: an id belongs to the original
// and may only exist once, and a link in a copy is a stop on the way through
// the page that reads exactly like the one before it. aria-hidden takes a
// copy out of the screen reader's list but leaves it in the tab order
check( 'no copy carries an id of the original', plain.copiedIds().every( function( n ) { return n === 0 } ) );
check( '...and no link inside one is a stop on the way through the page', plain.copiedTabstops().every( function( t ) { return t === '-1' } ) );

const wide = page( { box : 200, items : [ 300, 300 ] } );
check( 'a row already wider than its box is copied once, not not at all', wide.items() === 4 );

const tiny = page( { box : 4000, items : [ 10 ] } );
check( 'one narrow item in a very wide box stops at the ceiling rather than making a thousand elements', tiny.items() <= 41 );


// --- The animation -------------------------------------------------------------

// Three items of 200 and three gaps of 32: where the first copy stands, not
// how wide the originals are. The track's own width leaves out the gap
// between the last original and the first copy, so running that far ended
// one gap short of the copy and the row jumped by it every time round
check( 'the distance is where the first copy stands, so the copy ends where the original stood', plain.distance() === '696px' );
check( '...and the duration follows from that distance and the speed the row was given', plain.duration() === '17.4s' );

const quick = page( { speed : 200 } );
check( 'a row with its own speed runs at it', quick.duration() === '3.48s' );

const nonsense = page( { speed : -5 } );
check( '...and a speed that is not one falls back rather than standing still or running backwards', nonsense.duration() === '17.4s' );


// --- A box that changed width --------------------------------------------------

// The copies cover the box they were made for and no more. Three items of 200
// with a gap = 664 wide: in a box of 3000 the run has to cover 3000 + 664, so
// five copies of it are needed where the box of 1000 took two
const grown = page( {} );

grown.resize( 3000 );
grown.settle();
check( 'a box that got wider is covered again, rather than running the row out into a stretch of nothing',
	grown.items() === 18 && grown.distance() === '696px' );

// 200 + 664 is covered by one copy, and the copies made for the wider box are
// taken back out rather than copied on top of
grown.resize( 200 );
grown.settle();
check( '...and one that got narrower is copied again too, from the row the project wrote', grown.items() === 6 && grown.copies() === 3 );

grown.resize();
grown.settle();
check( 'a resize that left the box the width it was measures nothing - a phone\'s address bar sliding away is one of those',
	grown.measurements() === 3 && grown.items() === 6 );

const dragged = page( {} );

dragged.resize( 1400 );
dragged.resize( 1800 );
dragged.resize( 2200 );
check( 'a window being dragged across the screen measures nothing while it moves', dragged.measurements() === 1 );

dragged.settle();
check( '...and once, not once per step, when it comes to rest', dragged.measurements() === 2 && dragged.items() === 15 );


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
