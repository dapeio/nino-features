/**
 *	Nino
 *	ticker-js-smoke.js	What ticker.js does over a dom stand-in: how many copies
 *											it makes for a given row and box, that it stops making
 *											them, that the copies are hidden from a screen reader,
 *											what it sets the animation's distance and duration to,
 *											that a row with nothing in it is left alone - and
 *											that a row runs one pass by default and loops where it
 *											asks, waits to be seen, is done when its animation
 *											ends and stays done, and that the pause button it can
 *											ask for is drawn once, after the row, and removed with
 *											the pass.
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
		if( name === '--nino-ticker-distance' ) el.measured++;
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
 *	One row as a project writes it, the script loaded over it
 *
 *	@param		{Object}	options		{ box, items, speed, direction, readyState, withTrack,
 *																loop, toggle, reducedMotion, observer }
 *																observer: false stands in for a browser without
 *																IntersectionObserver
 */
function page( options ) {

	options = options || {};

	const row = element( { 'class' : 'nino-ticker' }, options.box !== undefined ? options.box : 1000 );

	if( options.speed !== undefined ) row.attributes['data-ticker-speed'] = String( options.speed );
	if( options.direction !== undefined ) row.attributes['data-ticker-direction'] = options.direction;
	if( options.loop !== undefined ) row.attributes['data-ticker-loop'] = options.loop;
	if( options.toggle !== undefined ) row.attributes['data-ticker-toggle'] = options.toggle;

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

	// What the row stands in, which is where the pause button goes: beside it
	const host = element();
	host.appendChild( row );

	const observers = [];

	const listeners = {};

	focused = null;

	const dc = {
		get activeElement()	{ return focused },
		readyState			: options.readyState || 'complete',
		querySelectorAll	: function( selector ) { return selector === '.nino-ticker' ? [ row ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		createElement			: function( tag ) { const created = element(); created.tagName = tag; return created },
	};

	const timers = [];

	const sandbox = { console : console, document : dc,
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		setTimeout				: function( fn ) { timers.push( fn ); return timers.length },
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

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'ticker.js' } );

	return {
		row : row, track : track, host : host, listeners : listeners, observers : observers,
		running : function() { return row.matchesClass( 'nino-is-running' ) },
		// The row has come into view
		intersect : function() { observers.forEach( function( observer ) { observer.callback( [ { isIntersecting : true } ] ) } ) },
		// The track's animation has run its course; a child's would not count
		animationEnd : function( target ) { track.fire( 'animationend', { target : target || track } ) },
		toggle : function() { return row.nextSibling },
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


// --- One pass, or round and round ----------------------------------------------

/*	The default is one cycle. The reading of data-ticker-loop is
	typewriter.js's own for data-typewriter-loop: missing or empty is the
	default, '0', 'false', 'off' and 'no' say it too, anything else asks for the
	loop - and what the script writes is a class, so the stylesheet never
	repeats the list	*/
check( 'a row without data-ticker-loop is not looping', plain.row.matchesClass('nino-is-looping') === false );
check( '...and nor are the four values that say so, nor an empty one',
	[ '0', 'false', 'OFF', 'no', '', '  ' ].every( function( value ) { return page( { loop : value } ).row.matchesClass('nino-is-looping') === false } ) );
check( '...while any other value asks for the endless loop',
	[ '1', 'true', 'on', 'yes', 'loop' ].every( function( value ) { return page( { loop : value } ).row.matchesClass('nino-is-looping') === true } ) );

// Measured is not started: the row is copied and its animation set up, and
// then held until somebody could see it - a footer row would otherwise be
// through its single pass before anybody scrolled to it
check( 'a row is measured at once, and waits to be seen',
	plain.running() === true && plain.row.matchesClass('nino-is-waiting') === true && plain.observers.length === 1
	&& plain.observers[0].observed[0] === plain.row );

plain.intersect();
check( '...and starts when it has been in the viewport, which is the only time it is looked for',
	plain.row.matchesClass('nino-is-waiting') === false && plain.observers[0].disconnected === true );

const unobserved = page( { observer : false } );
check( 'a browser without IntersectionObserver has nothing to wait for and runs at once',
	unobserved.running() === true && unobserved.row.matchesClass('nino-is-waiting') === false );

// A single pass ends on its own animation, and stays ended: a resize sets the
// duration again, which would start a finished animation over
const pass = page( {} );
pass.intersect();
pass.animationEnd( pass.track.children[0] );
check( 'an animation end that comes from one of the items is not the row\'s', pass.row.matchesClass('nino-is-done') === false );

pass.animationEnd();
check( 'the end of the track\'s own animation marks the row done', pass.row.matchesClass('nino-is-done') === true );

const doneMeasured = pass.measurements();
const doneItems = pass.items();
pass.resize( 3000 );
pass.settle();
check( 'and a finished row is never measured, copied or started again - not by a resize either',
	pass.measurements() === doneMeasured && pass.items() === doneItems );

const endlessRow = page( { loop : '1' } );
check( 'a looping row has no end to wait for', ( endlessRow.track.listeners['animationend'] || [] ).length === 0 );


// --- The pause button ----------------------------------------------------------

const quiet = page( {} );
check( 'a row that does not ask for one gets no button', quiet.toggle() === null && quiet.host.children.length === 1 );

const asked = page( { toggle : 'Pause animation' } );
const button = asked.toggle();

check( 'a row with a label gets one button, as its next sibling - not inside it, where the track is cloned',
	button !== null && asked.host.children.length === 2 && asked.host.children[0] === asked.row
	&& asked.row.children.length === 1 && asked.row.children[0] === asked.track
	&& asked.track.children.every( function( child ) { return child.tagName !== 'button' } ) );
check( '...a real button wearing the kernel\'s classes and its own, labelled and not pressed',
	button.tagName === 'button' && button.type === 'button'
	&& button.className === 'nino-btn nino-btn--outline nino-btn--small nino-ticker-toggle'
	&& button.textContent === 'Pause animation' && button.getAttribute('aria-pressed') === 'false' );

button.click();
check( 'a press marks the button pressed and the row paused, which the stylesheet holds the animation for',
	button.getAttribute('aria-pressed') === 'true' && asked.row.matchesClass('nino-is-paused') === true );
button.click();
check( 'a second press takes it up again', button.getAttribute('aria-pressed') === 'false' && asked.row.matchesClass('nino-is-paused') === false );

asked.resize( 3000 );
asked.settle();
asked.resize( 400 );
asked.settle();
check( 'measuring the row again - a resize - does not draw a second button', asked.host.children.length === 2 && asked.toggle() === button );

asked.animationEnd();
check( 'the button is removed - not hidden - with the end of the pass, and the pause with it',
	asked.toggle() === null && asked.host.children.length === 1 && asked.row.matchesClass('nino-is-paused') === false );

// The keyboard focus does not fall back to the page's top with the button
const focusing = page( { toggle : 'Pause animation' } );
focusing.resize( 3000 );
focusing.settle();
focusing.toggle().focus();
focusing.animationEnd();
check( 'a button that has the focus when the pass ends hands it to the row',
	focusing.toggle() === null && focused === focusing.row && focusing.row.getAttribute('tabindex') === '-1' );

const unfocused = page( { toggle : 'Pause animation' } );
unfocused.resize( 3000 );
unfocused.settle();
unfocused.animationEnd();
check( '...and one that has not leaves the row as it was', unfocused.toggle() === null && focused === null
	&& unfocused.row.getAttribute('tabindex') === null );

const loopingButton = page( { loop : '1', toggle : 'Pause animation' } );
loopingButton.resize( 3000 );
loopingButton.settle();
check( 'a row that loops keeps its button, since it never stops moving', loopingButton.toggle() !== null && loopingButton.host.children.length === 2 );

[ [ 'an empty label', '' ], [ 'a label of nothing but blanks', '   ' ], [ 'a label that is still a fill nobody resolved', '[[/ticker/toggle]]' ] ].forEach( function( bad ) {
	const none = page( { toggle : bad[1] } );
	check( bad[0]+ ' draws no button', none.toggle() === null && none.host.children.length === 1 );
} );

const reduced = page( { toggle : 'Pause animation', reducedMotion : true } );
check( 'a visitor who asked for less motion gets no button - nothing moves for them', reduced.toggle() === null && reduced.host.children.length === 1 );

const unmeasured = page( { toggle : 'Pause animation', items : [ 0, 0 ] } );
check( 'and a row that has not been measured has nothing to pause yet, so it gets no button either',
	unmeasured.running() === false && unmeasured.toggle() === null );


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
