/**
 *	Nino
 *	typewriter-js-smoke.js	What typewriter.js does over a dom stand-in, one pin
 *													each: that a line is typed character by character
 *													from its own text and stays for the length of its
 *													hold, that the line is written as text and never as
 *													markup, that a screen reader gets the line once and
 *													the typing is hidden from it, that the last line
 *													stays and the button goes with it where the
 *													typewriter does not loop and that it starts over
 *													where it does, that the pause button stops the
 *													typing and takes it up again, that the typing waits
 *													to be seen, and that a visitor who asked for less
 *													motion gets the lines as the page wrote them.
 *
 *													No jsdom, no dependency: a small element stand-in, so
 *													this runs with nothing but node - and the kernel's
 *													own Nino.js, which starts the script (Nino.events):
 *													from the checkout three levels up, or the one
 *													NINO_ROOT names.
 *
 *	Usage: node features/Typewriter/tests/typewriter-js-smoke.js
 *	       NINO_ROOT=../nino node features/Typewriter/tests/typewriter-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/typewriter.js' ), 'utf8' );
const kernel = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const nino = new vm.Script( fs.readFileSync( path.join( kernel, '_nino/Nino.js' ), 'utf8' ), { filename : 'Nino.js' } );

// What the script wrote as markup: any assignment to one of these is a finding
const markup = [];

function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		children		: [],
		parent			: null,
		listeners		: {},
		tagName			: '',
		type				: '',
		own					: '',
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		removeChild			: function( child ) { this.children = this.children.filter( function( c ) { return c !== child } ); child.parent = null; return child },
		insertBefore		: function( child, before ) {
			if( child.parent !== null )
				child.parent.removeChild( child );
			child.parent = this;
			if( before === null || before === undefined )
				this.children.push( child );
			else
				this.children.splice( this.children.indexOf( before ), 0, child );
			return child;
		},
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		click						: function() { ( this.listeners['click'] || [] ).forEach( function( fn ) { fn( {} ) } ) },
		focus						: function() { focused = this },
	};

	el.classList = {
		add			: function( name ) { if( el.classList.contains( name ) === false ) el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() },
		remove	: function( name ) { el.attributes['class'] = ( el.attributes['class'] || '' ).split( /\s+/ ).filter( function( n ) { return n !== name } ).join( ' ' ) },
		contains: function( name ) { return ( el.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
	};

	Object.defineProperty( el, 'parentNode', { get : function() { return el.parent } } );
	Object.defineProperty( el, 'nextSibling', { get : function() { return el.parent === null ? null : ( el.parent.children[ el.parent.children.indexOf( el ) + 1 ] || null ) } } );
	Object.defineProperty( el, 'className', { get : function() { return el.attributes['class'] || '' }, set : function( value ) { el.attributes['class'] = String( value ) } } );
	Object.defineProperty( el, 'textContent', {
		get : function() { return el.children.length > 0 ? el.children.map( function( c ) { return c.textContent } ).join( '' ) : el.own },
		set : function( value ) { el.children.forEach( function( c ) { c.parent = null } ); el.children = []; el.own = String( value ) },
	} );

	for( const name of [ 'innerHTML', 'outerHTML' ] )
		Object.defineProperty( el, name, { get : function() { return '' }, set : function( value ) { markup.push( name + ': ' + value ) } } );

	el.insertAdjacentHTML = function( where, value ) { markup.push( 'insertAdjacentHTML: ' + value ) };

	return el;
}

let focused = null;

/**
 *	One typewriter as the component draws it, the kernel's Nino.js and then the
 *	script loaded over it
 *
 *	@param		{Object}	options		{ lines, speed, hold, loop, label, reducedMotion, observer }
 */
function page( options ) {

	options = options || {};
	focused = null;
	markup.length = 0;

	const typewriter = element( { 'class' : 'typewriter', 'data-typewriter-loop' : options.loop === true ? '1' : '0', 'data-typewriter-label' : options.label !== undefined ? options.label : 'Pause animation' } );

	if( options.speed !== undefined ) typewriter.attributes['data-typewriter-speed'] = String( options.speed );
	if( options.hold !== undefined ) typewriter.attributes['data-typewriter-hold'] = String( options.hold );

	for( const text of ( options.lines !== undefined ? options.lines : [ 'ab', 'cd' ] ) ) {
		const line = typewriter.appendChild( element( { 'class' : 'typewriter-line' } ) );
		line.own = text;
	}

	const host = element();
	host.appendChild( typewriter );

	const listeners = {};
	const observers = [];
	const timers = [];

	const dc = {
		get activeElement() { return focused },
		readyState			: 'complete',
		documentElement	: { clientHeight : 800, clientWidth : 1000, scrollTop : 0, scrollLeft : 0 },
		body						: { scrollTop : 0, scrollLeft : 0, classList : { add : function() {}, remove : function() {} } },
		querySelector			: function() { return null },
		querySelectorAll	: function( selector ) { return selector === '.typewriter' ? [ typewriter ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		createElement			: function( tag ) { const created = element(); created.tagName = tag; return created },
	};

	const sandbox = { console : console, document : dc,
		navigator					: { userAgent : 'node' },
		location					: { hash : '' },
		Event							: function( type ) { this.type = type },
		dispatchEvent			: function() {},
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		requestAnimationFrame	: function() { return 1 },
		setTimeout				: function( fn, delay ) { timers.push( { fn : fn, delay : delay } ); return timers.length },
		clearTimeout			: function( id ) { timers[id - 1] = null },
		matchMedia				: function( query ) { return { media : query, matches : options.reducedMotion === true } },
		IntersectionObserver	: function( callback ) {
			this.callback = callback;
			this.observe = function() {};
			this.disconnect = function() {};
			observers.push( this );
		},
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );

	nino.runInContext( context );
	vm.runInContext( source, context, { filename : 'typewriter.js' } );

	// Run what was asked of setTimeout() once, in order, and say after how long it was asked for; Nino.js's own
	// ready callbacks are among them, so the first call settles those and returns
	const next = function() {
		const timer = timers.filter( function( t ) { return t !== null } )[0];
		if( timer === undefined ) return null;
		timers[timers.indexOf( timer )] = null;
		timer.fn();
		return timer.delay;
	};

	// Every timer that is armed, ready callbacks first
	const pending = function() { return timers.filter( function( t ) { return t !== null } ).length };

	// Until the typing waits on a timer of its own: the ready callbacks of Nino.js
	next();

	const lines = function() { return typewriter.children.filter( function( c ) { return c.classList.contains( 'typewriter-line' ) } ) };

	return {
		typewriter : typewriter, host : host, observers : observers, next : next, pending : pending, lines : lines,
		intersect : function() { observers.forEach( function( observer ) { observer.callback( [ { isIntersecting : true } ] ) } ) },
		// What the script wrote into a line, by the class of the part: reader, written, rest
		part : function( line, name ) { return lines()[line].children.filter( function( c ) { return c.classList.contains( 'typewriter-' + name ) } )[0] },
		typed : function( line ) { return lines()[line].children.filter( function( c ) { return c.classList.contains( 'typewriter-written' ) } )[0].textContent },
		rest : function( line ) { return lines()[line].children.filter( function( c ) { return c.classList.contains( 'typewriter-rest' ) } )[0].textContent },
		// Run until the timer that is armed next is the one with this delay, and return what ran
		until : function( predicate, limit ) {
			for( let i = 0; i < ( limit || 500 ); i++ ) {
				if( predicate() === true ) return true;
				if( next() === null ) return predicate();
			}
			return false;
		},
		toggle : function() { return typewriter.nextSibling },
		cursor : function() { return typewriter.children.reduce( function( found, line ) { return found || line.children.filter( function( c ) { return c.classList.contains( 'typewriter-cursor' ) } )[0] || null }, null ) },
	};
}


// --- Typing a line -------------------------------------------------------------

const typing = page( { lines : [ 'abc', 'xy' ], speed : 10, hold : 77 } );
check( 'the typing waits until the typewriter has been in view, and the lines are taken over at once', typing.pending() === 0 && typing.typewriter.classList.contains( 'typewriter-is-running' ) === true && typing.rest( 0 ) === 'abc' && typing.typed( 0 ) === '' );

typing.intersect();
typing.until( function() { return typing.typed( 0 ) === 'ab' } );
check( 'a line is written character by character, the rest of it waits in its place, and the active line is the one being typed',
	typing.typed( 0 ) === 'ab' && typing.rest( 0 ) === 'c' && typing.lines()[0].classList.contains( 'typewriter-is-active' ) === true && typing.lines()[1].classList.contains( 'typewriter-is-active' ) === false
	&& typing.cursor() !== null && typing.cursor().parent === typing.lines()[0] );

typing.until( function() { return typing.typed( 0 ) === 'abc' } );
typing.next();
const held = typing.next();
check( 'a finished line stands for the hold the typewriter names, then the next line becomes the active one', held === 77 && typing.until( function() { return typing.lines()[1].classList.contains( 'typewriter-is-active' ) } ) === true && typing.lines()[0].classList.contains( 'typewriter-is-active' ) === false );

typing.until( function() { return typing.typed( 1 ) === 'xy' } );
typing.until( function() { return typing.cursor() === null } );
check( 'the last line of a typewriter that does not loop stays, and the cursor and the pause button are gone',
	typing.typed( 1 ) === 'xy' && typing.lines()[1].classList.contains( 'typewriter-is-active' ) === true && typing.cursor() === null && typing.toggle() === null && typing.pending() === 0 );


// --- A line is text, a screen reader hears it once --------------------------------

const evil = '<img src=x onerror=alert(1)> & [csrf]';
const safe = page( { lines : [ evil ] } );
safe.intersect();
safe.until( function() { return safe.typed( 0 ) === evil } );
check( 'a line that spells markup is typed as the text it is, and the script writes no markup at all',
	safe.typed( 0 ) === evil && markup.length === 0 && safe.part( 0, 'reader' ).textContent === evil );
check( 'the line stands once for a screen reader and the typed and untyped halves are hidden from it',
	safe.part( 0, 'reader' ).getAttribute( 'aria-hidden' ) === null
	&& safe.part( 0, 'written' ).getAttribute( 'aria-hidden' ) === 'true' && safe.part( 0, 'rest' ).getAttribute( 'aria-hidden' ) === 'true' && safe.cursor().getAttribute( 'aria-hidden' ) === 'true' );

const indented = page( { lines : [ '\n\t\t  one   two\n\t' ] } );
check( 'a line is typed as the browser would show it, not as the file is indented', indented.rest( 0 ) === 'one two' );


// --- Looping, and the button ---------------------------------------------------

const looping = page( { lines : [ 'a', 'b' ], loop : true, speed : 1, hold : 1 } );
looping.intersect();
looping.until( function() { return looping.typed( 1 ) === 'b' } );
looping.until( function() { return looping.typed( 0 ) === 'a' && looping.lines()[0].classList.contains( 'typewriter-is-active' ) && looping.lines()[1].classList.contains( 'typewriter-is-active' ) === false } );
check( 'a typewriter that loops starts over after its last line and keeps its button', looping.typed( 0 ) === 'a' && looping.cursor() !== null && looping.toggle() !== null && looping.toggle().tagName === 'button' );

check( 'an empty label, one of blanks and one that is still a fill nobody resolved draw no button',
	[ '', '   ', '[[/feature/typewriter/pause/label]]' ].every( function( label ) { const none = page( { label : label } ); return none.toggle() === null && none.host.children.length === 1 } ) );

const button = page( { lines : [ 'abcd' ], speed : 5 } );
button.intersect();
button.until( function() { return button.typed( 0 ) === 'a' } );
const pressed = button.toggle();
pressed.click();
const armed = button.pending();
button.next();
check( 'the pause button is the typewriter\'s next sibling, named as the label says, and a press stops the typing', pressed.parent === button.host && pressed.textContent === 'Pause animation'
	&& pressed.getAttribute( 'aria-pressed' ) === 'true' && button.typewriter.classList.contains( 'typewriter-is-paused' ) === true && armed === 0 && button.typed( 0 ) === 'a' );
pressed.click();
button.until( function() { return button.typed( 0 ) === 'abcd' } );
check( 'a second press takes the typing up where it stopped', pressed.getAttribute( 'aria-pressed' ) === 'false' && button.typewriter.classList.contains( 'typewriter-is-paused' ) === false && button.typed( 0 ) === 'abcd' );


// --- What a container says -----------------------------------------------------

const nonsense = page( { lines : [ 'ab' ], speed : 'fast', hold : '-5' } );
nonsense.intersect();
nonsense.next();
check( 'a speed and a hold that are no numbers leave the defaults', nonsense.next() === 400 && nonsense.next() === 45 );


// --- Less motion, and a typewriter with nothing in it --------------------------

const reduced = page( { lines : [ 'one', 'two' ], reducedMotion : true } );
check( 'a visitor who asked for less motion gets the lines as the page wrote them - nothing rewritten, no class, no button, nothing waiting',
	reduced.lines().length === 2 && reduced.lines().every( function( line ) { return line.children.length === 0 } ) && reduced.lines()[0].textContent === 'one'
	&& reduced.typewriter.classList.contains( 'typewriter-is-running' ) === false && reduced.toggle() === null && reduced.observers.length === 0 && reduced.pending() === 0 );
check( 'a typewriter with no line is left alone', page( { lines : [] } ).toggle() === null && page( { lines : [] } ).observers.length === 0 );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
