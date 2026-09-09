/**
 *	Nino
 *	typewriter-js-smoke.js	Behaviour test for typewriter.js, the half of this
 *							feature that PHP never touches: the line sequence it
 *							plays, every data attribute that times it, the markup
 *							it leaves for assistive technology, and the markup it
 *							leaves alone. DOM-light - the file is evaluated in a
 *							vm context against stand-ins for the handful of DOM
 *							APIs it uses, with a clock that only moves when this
 *							test says so.
 *
 *							typewriter-smoke.php runs this through node when node
 *							is on the path, so bin/check.sh and CI cover it; it
 *							also runs on its own.
 *
 *	Usage: node features/Typewriter/tests/typewriter-js-smoke.js
 */

'use strict';

// Nino's eslint config hands the node globals to the tests directory at the
// root of a checkout, which a feature's own tests directory is not - and
// `npx eslint features` in that checkout is what CI runs over this file. So
// it names the three node globals it uses itself
/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const source = fs.readFileSync( path.join( __dirname, '../assets/typewriter.js' ), 'utf8' );

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

function classList( initial ) {
	const values = new Set( initial || [] );
	return {
		add : function( value ) { values.add( value ) },
		remove : function( value ) { values.delete( value ) },
		contains : function( value ) { return values.has( value ) },
	};
}

/**
 *	The smallest element the behaviour actually uses: attributes, a class
 *	list, a style object, children, and a textContent that reads and writes
 *	them the way the DOM does - the typewriter empties a line and appends
 *	spans to it, so a plain string property would not measure anything.
 */
function element( attributes ) {
	const el = {
		attributes : attributes || {},
		children : [],
		own : '',
		className : '',
		style : {},
		parentNode : null,
		classList : classList(),
		getAttribute : function( name ) { return this.attributes[name] ?? null },
		setAttribute : function( name, value ) { this.attributes[name] = String( value ) },
		appendChild : function( child ) {
			if( child.parentNode !== null )
				child.parentNode.removeChild( child );
			child.parentNode = this;
			this.children.push( child );
			return child;
		},
		insertBefore : function( child, before ) {
			this.appendChild( child );
			this.children.pop();
			this.children.splice( this.children.indexOf( before ), 0, child );
			return child;
		},
		removeChild : function( child ) {
			this.children = this.children.filter( function( node ) { return node !== child } );
			child.parentNode = null;
			return child;
		},
		querySelectorAll : function() { return [] },
	};

	Object.defineProperty( el, 'textContent', {
		get : function() {
			return this.own + this.children.map( function( child ) { return child.textContent } ).join('');
		},
		set : function( value ) {
			this.children.forEach( function( child ) { child.parentNode = null } );
			this.children = [];
			this.own = String( value );
		},
	} );

	return el;
}

function childByClass( el, className ) {
	return el.children.find( function( child ) { return child.className === className } ) ?? null;
}

/**
 *	Whether the cursor stands between what is written and what is not - the
 *	writing head, and the reason the line never rewraps around it.
 */
function cursorAtHead( line ) {
	const at = line.children.findIndex( function( child ) { return child.className === 'nino-typewriter-cursor' } );
	return at > 0
		&& line.children[at - 1].className === 'nino-typewriter-text'
		&& ( line.children[at + 1] ?? {} ).className === 'nino-typewriter-rest';
}

/**
 *	One page with one .nino-typewriter on it, a clock that only moves when
 *	the test says so, and typewriter.js evaluated against both. The file
 *	starts itself on a document that is already loaded, so it has run by the
 *	time this returns - waiting, on a container that starts in view, for
 *	scrollIntoView() below.
 */
function world( spec ) {

	const
		lines = ( spec.lines || [] ).map( function( text ) {
			const line = element();
			line.textContent = text;
			return line;
		} ),
		wrap = element( spec.attributes || {} );

	wrap.querySelectorAll = function( selector ) { return selector === ( spec.selector || 'p' ) ? lines : [] };

	const
		timers = [],
		observers = [],
		document = {
			readyState : 'complete',
			addEventListener : function() {},
			querySelectorAll : function( selector ) { return selector === '.nino-typewriter' ? [ wrap ] : [] },
			createElement : function() { return element() },
		};

	let now = 0;

	const sandbox = {
		console : console,
		document : document,
		matchMedia : function( query ) {
			return { media : query, matches : spec.reducedMotion === true };
		},
		setTimeout : function( callback, delay ) {
			timers.push( { at : now + ( Number( delay ) || 0 ), callback : callback, done : false } );
			return timers.length;
		},
		IntersectionObserver : function( callback ) {
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

	if( spec.withoutObserver === true )
		delete sandbox.IntersectionObserver;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'typewriter.js' } );

	/**
	 *	Run every callback due within the next `ms`, in the order they are
	 *	due - including the ones scheduled while running them.
	 */
	function advance( ms ) {
		const until = now + ms;
		for( let guard = 0; guard < 10000; guard++ ) {
			const due = timers
				.filter( function( timer ) { return timer.done === false && timer.at <= until } )
				.sort( function( a, b ) { return a.at - b.at } )[0];
			if( due === undefined )
				break;
			due.done = true;
			now = due.at;
			due.callback();
		}
		now = until;
	}

	return {
		wrap : wrap,
		lines : lines,
		observers : observers,
		advance : advance,
		scrollIntoView : function() {
			observers.forEach( function( observer ) { observer.callback( [ { isIntersecting : true } ] ) } );
		},
	};
}


// --- The defaults: viewport start, fade, loop ------------------------------

console.log( 'Default sequence' );

const fade = world( { lines : [ 'Ab', 'Cd' ] } );

const
	first = fade.lines[0],
	second = fade.lines[1];

check( 'each line is marked as one, so the stacking in typewriter.css takes hold', first.classList.contains('nino-typewriter-line') === true && second.classList.contains('nino-typewriter-line') === true );
check( 'the complete line stays readable for assistive technology', childByClass( first, 'nino-typewriter-reader' ).textContent === 'Ab' && childByClass( second, 'nino-typewriter-reader' ).textContent === 'Cd' );
check( 'the typed text goes into a span of its own, hidden from it', childByClass( first, 'nino-typewriter-text' ).attributes['aria-hidden'] === 'true' );
check( 'the line is laid out at the size it will have, from the start', childByClass( first, 'nino-typewriter-rest' ).textContent === 'Ab' && childByClass( first, 'nino-typewriter-rest' ).attributes['aria-hidden'] === 'true' );
check( 'the fade duration reaches the element as the transition it is', first.style.transitionDuration === '400ms' );

fade.advance( 10000 );
check( 'nothing types before the container is in view, however long the page waits', childByClass( first, 'nino-typewriter-text' ).textContent === '' && first.classList.contains('nino-is-active') === false );

fade.scrollIntoView();
fade.advance( 0 );
check( 'scrolling it into view opens the first line, still empty', first.classList.contains('nino-is-active') === true && childByClass( first, 'nino-typewriter-text' ).textContent === '' );
check( 'and the observer is done watching it', fade.observers[0].disconnected === true );
check( 'the cursor rides between what is written and what is not', cursorAtHead( first ) === true );
check( 'and carries the default character', childByClass( first, 'nino-typewriter-cursor' ).textContent === '|' );

fade.advance( 400 );
check( 'typing starts after the fade, one character at a time', childByClass( first, 'nino-typewriter-text' ).textContent === 'A' );
check( 'and every character it writes is one the line stops reserving', childByClass( first, 'nino-typewriter-rest' ).textContent === 'b' );

fade.advance( 45 );
check( 'the next character follows one speed step later', childByClass( first, 'nino-typewriter-text' ).textContent === 'Ab' );

fade.advance( 45 + 1600 - 1 );
check( 'the finished line holds', first.classList.contains('nino-is-active') === true );

fade.advance( 1 );
check( 'then fades out', first.classList.contains('nino-is-active') === false );
check( 'the second line waits for the fade and the pause', second.classList.contains('nino-is-active') === false );

fade.advance( 400 + 300 );
check( 'the second line opens with the cursor moved into it', second.classList.contains('nino-is-active') === true && cursorAtHead( second ) === true );
check( 'and the cursor is gone from the first', childByClass( first, 'nino-typewriter-cursor' ) === null );

fade.advance( 400 + 45 + 45 );
check( 'the second line types the same way', childByClass( second, 'nino-typewriter-text' ).textContent === 'Cd' );

fade.advance( 45 + 1600 + 400 + 300 );
check( 'after the last line it starts over', first.classList.contains('nino-is-active') === true && second.classList.contains('nino-is-active') === false );
check( 'and rewrites it from nothing', childByClass( first, 'nino-typewriter-text' ).textContent === '' );

console.log('');


// --- Everything the data attributes change ---------------------------------

console.log( 'data-typewriter-*' );

const typed = world( {
	lines : [ 'Ho', 'Ja' ],
	selector : 'li',
	attributes : {
		'data-typewriter-lines'						: 'li',
		'data-typewriter-start'						: 'load',
		'data-typewriter-start-delay'			: '10',
		'data-typewriter-speed'						: '5',
		'data-typewriter-hold'						: '20',
		'data-typewriter-exit'						: 'backspace',
		'data-typewriter-fade'						: '999',
		'data-typewriter-backspace-speed'	: '3',
		'data-typewriter-pause'						: '7',
		'data-typewriter-loop'						: '0',
		'data-typewriter-cursor'					: '_',
	},
} );

const
	one = typed.lines[0],
	two = typed.lines[1];

check( 'data-typewriter-lines picks the elements to type', one.classList.contains('nino-typewriter-line') === true );
check( 'data-typewriter-start=load asks no observer whether it is visible', typed.observers.length === 0 );
check( 'an erasing typewriter does not fade, whatever data-typewriter-fade says', one.style.transitionDuration === '0ms' );

typed.advance( 9 );
check( 'it waits for the start delay instead', one.classList.contains('nino-is-active') === false );

typed.advance( 1 );
check( 'and starts on its own once that is over', one.classList.contains('nino-is-active') === true );
check( 'data-typewriter-cursor replaces the cursor character', childByClass( one, 'nino-typewriter-cursor' ).textContent === '_' );

typed.advance( 5 );
check( 'data-typewriter-speed times the typing', childByClass( one, 'nino-typewriter-text' ).textContent === 'Ho' );

typed.advance( 5 + 20 );
check( 'data-typewriter-hold times the standstill the finished line takes, then the first character goes', childByClass( one, 'nino-typewriter-text' ).textContent === 'H' );

typed.advance( 3 );
check( 'data-typewriter-backspace-speed times the erasing', childByClass( one, 'nino-typewriter-text' ).textContent === '' );
check( 'and what is erased goes back to reserving its place', childByClass( one, 'nino-typewriter-rest' ).textContent === 'Ho' );

typed.advance( 3 + 7 );
check( 'data-typewriter-pause times the gap to the next line', two.classList.contains('nino-is-active') === true && one.classList.contains('nino-is-active') === false );

typed.advance( 5 + 5 + 5 + 20 );
check( 'data-typewriter-loop=0 stops on the last line, complete', two.classList.contains('nino-is-active') === true && childByClass( two, 'nino-typewriter-text' ).textContent === 'Ja' );
check( 'and takes the cursor away rather than blinking on forever', childByClass( two, 'nino-typewriter-cursor' ) === null );

typed.advance( 10000 );
check( 'a stopped typewriter stays stopped', childByClass( two, 'nino-typewriter-text' ).textContent === 'Ja' && one.classList.contains('nino-is-active') === false );

console.log('');


// --- What an unreadable value does -----------------------------------------

console.log( 'Values it refuses' );

const refused = world( {
	lines : [ 'Ab' ],
	attributes : {
		'data-typewriter-start'	: 'load',
		'data-typewriter-speed'	: 'fast',
		'data-typewriter-hold'		: '-5',
		'data-typewriter-exit'		: 'wobble',
		'data-typewriter-loop'		: 'false',
	},
} );

check( 'an exit mode nobody implements falls back to the fade - which is the one that gives the line a transition', refused.lines[0].style.transitionDuration === '400ms' );

refused.advance( 400 );
check( 'an unreadable duration keeps the default instead of timing the animation with NaN', childByClass( refused.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

refused.advance( 44 );
check( 'and keeps it exactly - 45 ms per character, not "fast"', childByClass( refused.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

refused.advance( 1 );
check( 'so the second character lands one default step in', childByClass( refused.lines[0], 'nino-typewriter-text' ).textContent === 'Ab' );

refused.advance( 45 + 1600 - 1 );
check( 'a negative hold keeps the default too - one millisecond before it is over, the line is still standing', childByClass( refused.lines[0], 'nino-typewriter-cursor' ) !== null );

refused.advance( 1 );
check( 'data-typewriter-loop=false switches the loop off as well: the one line stays, its cursor goes', refused.lines[0].classList.contains('nino-is-active') === true
	&& childByClass( refused.lines[0], 'nino-typewriter-cursor' ) === null && childByClass( refused.lines[0], 'nino-typewriter-text' ).textContent === 'Ab' );

const cursorless = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-cursor' : '' } } );
cursorless.advance( 400 );
check( 'an empty data-typewriter-cursor means no cursor, not the default one', childByClass( cursorless.lines[0], 'nino-typewriter-cursor' ).textContent === '' );

console.log('');


// --- The text it types -----------------------------------------------------

console.log( 'Text' );

const shaped = world( {
	lines : [ '\n\t\t\tHi   there\n\t\t', '\u{1F680}!' ],
	attributes : {
		'data-typewriter-start'	: 'load',
		'data-typewriter-speed'	: '1',
		'data-typewriter-fade'		: '0',
		'data-typewriter-hold'		: '1',
		'data-typewriter-pause'	: '0',
	},
} );

check( 'the line is read as the browser would render it, not as the template indents it', childByClass( shaped.lines[0], 'nino-typewriter-reader' ).textContent === 'Hi there' );

shaped.advance( 0 );
check( 'so typing starts on the first character of the text, not on its indentation', childByClass( shaped.lines[0], 'nino-typewriter-text' ).textContent === 'H' );

shaped.advance( 7 );
check( 'and ends on its last', childByClass( shaped.lines[0], 'nino-typewriter-text' ).textContent === 'Hi there' );

shaped.advance( 2 );
check( 'a character outside the basic plane is typed whole, never as half a pair', childByClass( shaped.lines[1], 'nino-typewriter-text' ).textContent === '\u{1F680}' );

console.log('');


// --- What it leaves alone --------------------------------------------------

console.log( 'What it leaves alone' );

const still = world( { lines : [ 'Ab', 'Cd' ], reducedMotion : true } );
still.scrollIntoView();
still.advance( 10000 );

check( 'a visitor who asked for less motion gets no typewriter at all', still.lines[0].classList.contains('nino-typewriter-line') === false );
check( 'and the lines keep their text, all of them, unwrapped', still.lines[0].textContent === 'Ab' && still.lines[1].textContent === 'Cd' && still.lines[0].children.length === 0 );

const empty = world( { lines : [] } );
empty.scrollIntoView();
empty.advance( 10000 );
check( 'a container whose selector matches nothing does nothing', empty.wrap.children.length === 0 );

const observerless = world( { lines : [ 'Ab' ], withoutObserver : true } );
observerless.advance( 400 );
check( 'a browser without IntersectionObserver has nothing to wait for and simply starts', childByClass( observerless.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exitCode = failures === 0 ? 0 : 1;
