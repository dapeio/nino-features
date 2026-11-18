/**
 *	Nino
 *	ticker-js-smoke.js	What ticker.js does over a dom stand-in, one pin each:
 *											how many copies it makes for a given row and box, that
 *											the copies are hidden from assistive technology and
 *											cannot take the focus, what it sets the animation's
 *											distance and duration to, that a row with nothing in
 *											it is left alone, that a row runs one pass by default
 *											and loops where it asks, waits to be seen, is done when
 *											its animation ends, that the pause button it can ask
 *											for is drawn once, after the row, works and is removed
 *											with the pass - and that a visitor who asked for less
 *											motion gets the row as the page wrote it.
 *
 *											No jsdom, no dependency: the same element stand-in the
 *											other feature tests build, so this runs with nothing
 *											but node - and the kernel's own Nino.js, which the
 *											script is written against and which runs here as it is
 *											on disk: from the checkout three levels up, or the one
 *											NINO_ROOT names.
 *
 *	Usage: node features/Ticker/tests/ticker-js-smoke.js
 *	       NINO_ROOT=../nino node features/Ticker/tests/ticker-js-smoke.js
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

// The script of the kernel that ticker.js is written against, read from the
// checkout this runs against: three levels up when the feature sits in a project's
// features/, else the one NINO_ROOT names
const kernel = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const nino = new vm.Script( fs.readFileSync( path.join( kernel, '_nino/Nino.js' ), 'utf8' ), { filename : 'Nino.js' } );

// The track is a flex row with a gap between its items (see ticker.css), and
// the gap is the whole point of the distance the animation runs: a row of n
// items is n widths plus n-1 gaps wide, while the first copy stands one gap
// further along than that. A stand-in without it cannot tell the two apart
const GAP = 32;

// The element that has the keyboard focus, as document.activeElement says
let focused = null;

function element( attributes, width ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		children		: [],
		parent			: null,
		listeners		: {},
		tagName			: '',
		type				: '',
		textContent	: '',
		properties	: {},
		width				: width || 0,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		removeChild			: function( child ) {
			this.children = this.children.filter( function( c ) { return c !== child } );
			child.parent = null;
			// A focused element that leaves the page takes the focus with it
			if( focused === child )
				focused = null;
			return child;
		},
		focus						: function() { focused = this },
		// A node goes before another, or last where there is none to go before
		insertBefore		: function( child, before ) {
			child.parent = this;
			if( before === null || before === undefined )
				this.children.push( child );
			else
				this.children.splice( this.children.indexOf( before ), 0, child );
			return child;
		},
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		// What the browser does when an event reaches the element
		fire						: function( type, event ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( event || {} ) } ) },
		click						: function() { this.fire( 'click' ) },
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

	el.classList = {
		add			: function( name ) { if( el.classList.contains( name ) === false ) el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() },
		remove	: function( name ) { el.attributes['class'] = ( el.attributes['class'] || '' ).split( /\s+/ ).filter( function( n ) { return n !== name } ).join(' ') },
		contains: function( name ) { return el.matchesClass( name ) },
	};
	el.properties = {};
	el.measured = 0;
	// Every write of the distance is one measurement of this row, which is
	// how a row that was left running is told from one that was built again
	el.style = { setProperty : function( name, value ) {
		el.properties[name] = value;
		if( name === '--ticker-distance' ) el.measured++;
	} };

	Object.defineProperty( el, 'parentNode', { get : function() { return el.parent } } );

	Object.defineProperty( el, 'nextSibling', {
		get : function() {
			return el.parent === null ? null : ( el.parent.children[ el.parent.children.indexOf( el ) + 1 ] || null );
		},
	} );

	Object.defineProperty( el, 'className', {
		get : function() { return el.attributes['class'] || '' },
		set : function( value ) { el.attributes['class'] = String( value ) },
	} );

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
 *	One row as a project writes it, the kernel's two scripts and then the script
 *	loaded over it. A document that is there already is ready when this returns;
 *	one that is not is ready when its DOMContentLoaded listeners are called
 *
 *	@param		{Object}	options		{ box, items, speed, readyState, withTrack,
 *																loop, toggle, reducedMotion, observer }
 *																observer: false stands in for a browser without
 *																IntersectionObserver
 */
function page( options ) {

	options = options || {};

	const row = element( { 'class' : 'ticker' }, options.box !== undefined ? options.box : 1000 );

	if( options.speed !== undefined ) row.attributes['data-ticker-speed'] = String( options.speed );
	if( options.loop !== undefined ) row.attributes['data-ticker-loop'] = options.loop;
	// A label with the switch on, as the template writes them: the label is always there, the switch says whether it is used
	row.attributes['data-ticker-label'] = options.toggle !== undefined ? options.toggle : 'Pause animation';
	if( options.toggle !== undefined ) row.attributes['data-ticker-toggle'] = '1';

	const track = element( { 'class' : 'ticker-track' }, 0 );

	for( const width of ( options.items !== undefined ? options.items : [ 200, 200, 200 ] ) ) {
		const item = track.appendChild( element( { 'class' : 'ticker-item' }, width ) );
		// A logo is usually a link, and a link is usually named - which is
		// what a copy may carry neither of
		item.appendChild( element( { 'href' : '/partner', 'id' : 'logo-' + track.children.length } ) );
	}

	if( options.withTrack !== false )
		row.appendChild( track );

	// What the row stands in, which is where the pause button goes: beside it
	const host = element();
	host.appendChild( row );

	const observers = [];

	const listeners = {};

	focused = null;

	const dc = {
		get activeElement()	{ return focused },
		readyState			: options.readyState || 'complete',
		documentElement	: { clientHeight : 800, clientWidth : 1000, scrollTop : 0, scrollLeft : 0 },
		body						: { scrollTop : 0, scrollLeft : 0, classList : { add : function() {}, remove : function() {} } },
		querySelector			: function() { return null },
		querySelectorAll	: function( selector ) { return selector === '.ticker' ? [ row ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		createElement			: function( tag ) { const created = element(); created.tagName = tag; return created },
	};

	// What was asked of setTimeout() and has not run: the kernel's ready callbacks
	// first, then what the script asks for in turn
	const timers = [];

	const sandbox = { console : console, document : dc,
		navigator					: { userAgent : 'node' },
		location					: { hash : '' },
		Event							: function( type ) { this.type = type },
		dispatchEvent			: function( event ) { ( listeners[event.type] || [] ).forEach( function( fn ) { fn( event ) } ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		requestAnimationFrame	: function() { return 1 },
		setTimeout				: function( fn, delay ) { timers.push( { fn : fn, delay : delay } ); return timers.length },
		clearTimeout			: function( id ) { timers[id - 1] = null },
		matchMedia				: function( query ) { return { media : query, matches : options.reducedMotion === true } },
		IntersectionObserver	: function( callback ) {
			const observer = this;
			observer.callback = callback;
			observer.observed = [];
			observer.disconnected = false;
			observer.observe = function( el ) { observer.observed.push( el ) };
			observer.disconnect = function() { observer.disconnected = true };
			observers.push( observer );
		},
	};
	sandbox.window = sandbox;

	if( options.observer === false )
		delete sandbox.IntersectionObserver;

	const context = vm.createContext( sandbox );

	nino.runInContext( context );
	vm.runInContext( source, context, { filename : 'ticker.js' } );

	// Run what was asked of setTimeout(), and what that asked for in turn - a timer
	// that was cleared is null
	const settle = function() {
		while( timers.length > 0 )
			timers.splice( 0 ).forEach( function( timer ) { if( timer !== null ) timer.fn() } );
	};

	// A document that is there already: Nino.js calls its ready callbacks back a
	// millisecond after it was loaded. One of them throwing is thrown again from a
	// timer, which settle() runs too - so the error ends the run instead of passing unseen
	if( dc.readyState !== 'loading' )
		settle();

	return {
		row : row, track : track, host : host, listeners : listeners, observers : observers,
		running : function() { return row.matchesClass( 'ticker-is-running' ) },
		// The row has come into view
		intersect : function() { observers.forEach( function( observer ) { observer.callback( [ { isIntersecting : true } ] ) } ) },
		// The track's animation has run its course; a child's would not count
		animationEnd : function( target ) { track.fire( 'animationend', { target : target || track } ) },
		toggle : function() { return row.nextSibling },
		items : function() { return track.children.length },
		copies : function() { return track.children.filter( function( c ) { return c.getAttribute('aria-hidden') === 'true' } ).length },
		distance : function() { return track.properties['--ticker-distance'] },
		duration : function() { return track.properties['--ticker-duration'] },
		copiedIds : function() { return track.children.slice( 3 ).map( function( c ) { return c.querySelectorAll( '[id]' ).length } ) },
		copiedTabstops : function() { return track.children.slice( 3 ).map( function( c ) { return ( c.children[0] || {} ).attributes['tabindex'] } ) },
		measurements : function() { return track.measured },
		// A window is resized; the rows are not measured again until it has
		// come to rest, which is what settle() is
		resize : function( box ) {
			if( box !== undefined ) row.width = box;
			( listeners['resize'] || [] ).forEach( function( fn ) { fn() } );
		},
		settle : settle,
	};
}


// --- Copying the row -----------------------------------------------------------

// Three items of 200 with a gap between them = 664 wide, in a box of 1000:
// the run has to cover 1000 + 664, so two more copies of it are needed
const plain = page( {} );

check( 'the row is copied until it covers the box plus one length of itself, and says it is running', plain.items() === 9 && plain.running() === true );
check( 'every copy is hidden from assistive technology, cannot take the focus and has no id of the original, and the originals are none of that',
	plain.copies() === 6 && plain.copiedIds().every( function( n ) { return n === 0 } ) && plain.copiedTabstops().every( function( t ) { return t === '-1' } )
	&& plain.track.children.slice( 3 ).every( function( c ) { return c.getAttribute('inert') === '' } )
	&& plain.track.children.slice( 0, 3 ).every( function( c ) { return c.getAttribute('inert') === null && c.getAttribute('aria-hidden') === null } ) );
check( 'one narrow item in a very wide box stops at the ceiling rather than making a thousand elements', page( { box : 4000, items : [ 10 ] } ).items() <= 41 );


// --- The animation -------------------------------------------------------------

// The distance is where the first copy stands: the track's own width leaves out the gap between the last original and the first copy
check( 'the distance is where the first copy stands, and the duration follows from it and the speed of the row; a speed that is none is the default',
	plain.distance() === '696px' && plain.duration() === '17.4s' && page( { speed : 200 } ).duration() === '3.48s' && page( { speed : -5 } ).duration() === '17.4s' );

const grown = page( {} );
grown.resize( 3000 );
grown.settle();
const covered = grown.items() === 18 && grown.distance() === '696px';
grown.resize( 200 );
grown.settle();
check( 'a box that changed width is covered again, from the row the page wrote and not from the copies of the box before', covered === true && grown.items() === 6 && grown.copies() === 3 );

const dragged = page( {} );
dragged.resize( 1400 );
dragged.resize( 1800 );
const whileMoving = dragged.measurements();
dragged.settle();
dragged.resize();
dragged.settle();
check( 'a window being dragged measures nothing while it moves, once when it comes to rest, and a resize that left the width does not measure at all',
	whileMoving === 1 && dragged.measurements() === 2 );


// --- One pass, or round and round ----------------------------------------------

check( 'a row loops where data-ticker-loop says yes and not where it says no, says nothing or says a word that is neither',
	[ '1', 'true', 'on', 'yes', ' YES ' ].every( function( value ) { return page( { loop : value } ).row.matchesClass('ticker-is-looping') === true } )
	&& [ '0', 'false', 'OFF', 'no', '', 'loop', 'ja' ].every( function( value ) { return page( { loop : value } ).row.matchesClass('ticker-is-looping') === false } )
	&& plain.row.matchesClass('ticker-is-looping') === false );

check( 'a row is measured at once and waits to be seen',
	plain.running() === true && plain.row.matchesClass('ticker-is-waiting') === true && plain.observers[0].observed[0] === plain.row );
plain.intersect();
check( 'the viewport is looked at once: the wait is over and the observer let go, and a browser without IntersectionObserver has nothing to wait for',
	plain.row.matchesClass('ticker-is-waiting') === false && plain.observers[0].disconnected === true && page( { observer : false } ).row.matchesClass('ticker-is-waiting') === false );

const pass = page( {} );
pass.animationEnd( pass.track.children[0] );
const foreign = pass.row.matchesClass('ticker-is-done');
pass.animationEnd();
const doneMeasured = pass.measurements();
pass.resize( 3000 );
pass.settle();
check( 'the end of the track\'s own animation marks the row done, one of an item does not, and a finished row is never measured, copied or started again',
	foreign === false && pass.row.matchesClass('ticker-is-done') === true && pass.measurements() === doneMeasured && pass.items() === 9
	&& ( page( { loop : '1' } ).track.listeners['animationend'] || [] ).length === 0 );


// --- The pause button ----------------------------------------------------------

const asked = page( { toggle : 'Pause animation' } );
const button = asked.toggle();
check( 'a row that does not ask for one gets no button; one that does gets one real button as its next sibling, not inside the track that is cloned, labelled and not pressed',
	page( {} ).toggle() === null && button !== null && asked.host.children.length === 2 && asked.track.children.every( function( child ) { return child.tagName !== 'button' } )
	&& button.tagName === 'button' && button.type === 'button' && button.className === 'ticker-toggle' && button.textContent === 'Pause animation' && button.getAttribute('aria-pressed') === 'false' );

button.click();
const pressed = button.getAttribute('aria-pressed') === 'true' && asked.row.matchesClass('ticker-is-paused') === true;
button.click();
asked.resize( 3000 );
asked.settle();
check( 'a press pauses the row and marks the button pressed, a second press takes it up again, and measuring again draws no second button',
	pressed === true && button.getAttribute('aria-pressed') === 'false' && asked.row.matchesClass('ticker-is-paused') === false && asked.toggle() === button && asked.host.children.length === 2 );

asked.animationEnd();
check( 'the button is removed, not hidden, with the end of the pass, and the pause with it', asked.toggle() === null && asked.host.children.length === 1 && asked.row.matchesClass('ticker-is-paused') === false );

const focusing = page( { toggle : 'Pause animation' } );
focusing.toggle().focus();
focusing.animationEnd();
const handedOver = focused === focusing.row && focusing.row.getAttribute('tabindex') === '-1';
const unfocused = page( { toggle : 'Pause animation' } );
unfocused.animationEnd();
check( 'a button that has the focus when the pass ends hands it to the row, and one that has not leaves the row as it was', handedOver === true && unfocused.row.getAttribute('tabindex') === null );

const looping = page( { loop : '1', toggle : 'Pause animation' } );
check( 'a row that loops keeps its button, since it never stops moving', looping.toggle() !== null && looping.host.children.length === 2 );
check( 'an empty label, one of blanks and one that is still a fill nobody resolved draw no button',
	[ '', '   ', '[[/feature/ticker/pause/label]]' ].every( function( label ) { const none = page( { toggle : label } ); return none.toggle() === null && none.host.children.length === 1 } ) );


// --- Less motion ---------------------------------------------------------------

const reduced = page( { toggle : 'Pause animation', reducedMotion : true } );
check( 'a visitor who asked for less motion gets the row as the page wrote it - no copies, nothing running, no button',
	reduced.items() === 3 && reduced.copies() === 0 && reduced.running() === false && reduced.toggle() === null && reduced.host.children.length === 1 );


// --- A row that is not one -----------------------------------------------------

check( 'a row with no track, one with nothing to run and one whose items have no width yet are left as they were',
	page( { withTrack : false } ).running() === false && page( { items : [] } ).running() === false && page( { items : [ 0, 0 ] } ).running() === false
	&& page( { items : [ 0, 0 ] } ).distance() === undefined );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );
const waited = late.running() === false && ( late.listeners['DOMContentLoaded'] || [] ).length === 1;
( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( 'a script that ran before the dom was there waits for it, and takes the rows over when it arrives', waited === true && late.running() === true && late.items() === 9 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
