/**
 *	Nino
 *	typewriter-js-smoke.js	Behaviour test for typewriter.js, the half of this
 *							feature that PHP never touches: the line sequence it
 *							plays - once by default, in a loop where the container
 *							asks - the pause button a container can ask for, every
 *							data attribute that times it, the markup it leaves for
 *							assistive technology, and the markup it leaves alone.
 *							DOM-light - the file is evaluated in a vm context
 *							against stand-ins for the handful of DOM APIs it
 *							uses, with a clock that only moves when this test
 *							says so.
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
let focused = null;

function element( attributes ) {
	const el = {
		attributes : attributes || {},
		children : [],
		own : '',
		tagName : '',
		type : '',
		listeners : {},
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

			// Nothing to go before is the end of the list, which is where
			// appendChild() has put it already
			if( before === null || before === undefined )
				return child;

			this.children.pop();
			this.children.splice( this.children.indexOf( before ), 0, child );
			return child;
		},
		addEventListener : function( type, callback ) {
			( this.listeners[type] = this.listeners[type] || [] ).push( callback );
		},
		click : function() {
			( this.listeners['click'] || [] ).forEach( function( callback ) { callback() } );
		},
		removeChild : function( child ) {
			this.children = this.children.filter( function( node ) { return node !== child } );
			child.parentNode = null;

			// A focused element that leaves the page takes the focus with it
			if( focused === child )
				focused = null;

			return child;
		},
		focus : function() { focused = this },
		querySelectorAll : function() { return [] },
	};

	// The sibling after this element in its parent, null where it is the last
	Object.defineProperty( el, 'nextSibling', {
		get : function() {
			if( this.parentNode === null )
				return null;
			return this.parentNode.children[ this.parentNode.children.indexOf( this ) + 1 ] ?? null;
		},
	} );

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

	focused = null;

	const
		lines = ( spec.lines || [] ).map( function( text ) {
			const line = element();
			line.textContent = text;
			return line;
		} ),
		wrap = element( spec.attributes || {} ),
		// What the container stands in: the pause button goes after it, as its
		// sibling, so it has to have a parent to stand beside
		host = element();

	host.appendChild( wrap );

	wrap.querySelectorAll = function( selector ) { return selector === ( spec.selector || 'p' ) ? lines : [] };

	/*	A container the page carries in front of that one, with a lines
		selector the browser refuses. querySelectorAll() answers a selector it
		cannot read with a SyntaxError rather than with no elements - that is
		what is being stood in for here, for the one shape this test uses -
		and one of those must not cost the page the typewriters after it	*/
	const
		refusedLine = element(),
		refused = spec.refuses === undefined ? null : element( { 'data-typewriter-lines' : spec.refuses } );

	refusedLine.textContent = 'Xy';

	if( refused !== null )
		refused.querySelectorAll = function( selector ) {
			if( /[:,[(]$/.test( selector ) === true )
				throw new SyntaxError( '\''+ selector+ '\' is not a valid selector' );
			return selector === 'p' ? [ refusedLine ] : [];
		};

	const
		timers = [],
		observers = [],
		document = {
			get activeElement() { return focused },
			readyState : 'complete',
			addEventListener : function() {},
			querySelectorAll : function( selector ) { return selector === '.nino-typewriter' ? ( refused === null ? [ wrap ] : [ refused, wrap ] ) : [] },
			createElement : function( tag ) {
				const created = element();
				created.tagName = tag;
				return created;
			},
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
		// A cleared timer is one that has been run: the clock skips it
		clearTimeout : function( id ) {
			if( timers[id - 1] !== undefined )
				timers[id - 1].done = true;
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
		host : host,
		// The pause button, where there is one: the container's next sibling
		toggle : function() { return wrap.nextSibling },
		lines : lines,
		refusedLine : refusedLine,
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

/*	The default is one pass: after the last line the typewriter stops, with that
	line complete and its cursor gone, so nothing blinks on forever. The earlier
	lines stay faded out - their text is still there for a screen reader, in the
	reader spans	*/
fade.advance( 45 + 1600 );
check( 'after the last line it stops, with that line complete and still showing',
	second.classList.contains('nino-is-active') === true && childByClass( second, 'nino-typewriter-text' ).textContent === 'Cd' );
check( '...its cursor gone, so the blinking ends', childByClass( second, 'nino-typewriter-cursor' ) === null );
check( '...and the line before it faded out for good, its text still there for a screen reader',
	first.classList.contains('nino-is-active') === false && childByClass( first, 'nino-typewriter-reader' ).textContent === 'Ab' );

fade.advance( 100000 );
check( 'a typewriter that has stopped stays stopped', second.classList.contains('nino-is-active') === true
	&& first.classList.contains('nino-is-active') === false && childByClass( second, 'nino-typewriter-text' ).textContent === 'Cd' );

const single = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load' } } );
single.advance( 400 + 45 + 45 + 1600 );
check( 'a typewriter of one line types it once and leaves it standing',
	single.lines[0].classList.contains('nino-is-active') === true && childByClass( single.lines[0], 'nino-typewriter-text' ).textContent === 'Ab'
	&& childByClass( single.lines[0], 'nino-typewriter-cursor' ) === null );

/*	What an existing page does to get the old behaviour back: any non-empty
	value that is not one of the four that switch a flag off	*/
[ '1', 'true', 'on', 'yes', 'YES' ].forEach( function( value ) {

	const looped = world( { lines : [ 'Ab', 'Cd' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : value } } );

	// 0 + 400 + 90 + 1600 to leave the first line, 400 + 300 to open the second,
	// 400 + 90 + 1600 to leave it, and 400 + 300 to come round again
	looped.advance( 400 + 90 + 1600 + 400 + 300 + 400 + 90 + 1600 + 400 + 300 );
	check( 'data-typewriter-loop="'+ value+ '" starts over after the last line, from nothing',
		looped.lines[0].classList.contains('nino-is-active') === true && looped.lines[1].classList.contains('nino-is-active') === false
		&& childByClass( looped.lines[0], 'nino-typewriter-text' ).textContent === ''
		&& childByClass( looped.lines[0], 'nino-typewriter-cursor' ) !== null );
} );

// Spaces around the value are not part of it, as in data-ticker-loop
const blankLoop = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : '  ' } } );
blankLoop.advance( 400 + 45 + 45 + 1600 );
check( 'a data-typewriter-loop of nothing but blanks is the default, which is one pass', childByClass( blankLoop.lines[0], 'nino-typewriter-cursor' ) === null );

const spacedOff = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : ' 0 ' } } );
spacedOff.advance( 400 + 45 + 45 + 1600 );
check( 'a data-typewriter-loop of " 0 " switches the loop off like "0"', childByClass( spacedOff.lines[0], 'nino-typewriter-cursor' ) === null );

const emptyLoop = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : '' } } );
emptyLoop.advance( 400 + 45 + 45 + 1600 );
check( 'an empty data-typewriter-loop is the default, which is one pass', childByClass( emptyLoop.lines[0], 'nino-typewriter-cursor' ) === null );

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


// --- The pause button ------------------------------------------------------

console.log( 'data-typewriter-toggle' );

const plain = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load' } } );
check( 'a container that does not ask for one gets no button', plain.host.children.length === 1 && plain.toggle() === null );

const asked = world( { lines : [ 'Ab', 'Cd' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : '1', 'data-typewriter-toggle' : 'Pause animation' } } );
const button = asked.toggle();

check( 'a container with a label gets exactly one button, as its next sibling and not inside it',
	button !== null && asked.host.children.length === 2 && asked.host.children[0] === asked.wrap
	&& asked.wrap.children.length === 0 && button.tagName === 'button' );
check( '...a real button that submits nothing, wearing the kernel\'s button classes and its own',
	button.type === 'button' && button.className === 'nino-btn nino-btn--outline nino-btn--small nino-typewriter-toggle' );
check( '...labelled with the attribute, and not pressed', button.textContent === 'Pause animation' && button.getAttribute('aria-pressed') === 'false' );

asked.advance( 400 );
check( 'the typing runs before anybody presses it', childByClass( asked.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

button.click();
check( 'a press marks the button pressed and the container paused',
	button.getAttribute('aria-pressed') === 'true' && asked.wrap.classList.contains('nino-is-paused') === true );

asked.advance( 100000 );
check( 'and nothing is typed or changed for as long as it stays so',
	childByClass( asked.lines[0], 'nino-typewriter-text' ).textContent === 'A' && asked.lines[0].classList.contains('nino-is-active') === true
	&& asked.lines[1].classList.contains('nino-is-active') === false );

button.click();
check( 'a second press takes the container out of its pause', button.getAttribute('aria-pressed') === 'false'
	&& asked.wrap.classList.contains('nino-is-paused') === false );

asked.advance( 44 );
check( '...and the step that was waiting is armed again with its full delay', childByClass( asked.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

asked.advance( 1 );
check( '...so the next character lands one step after the press', childByClass( asked.lines[0], 'nino-typewriter-text' ).textContent === 'Ab' );

// A pause is the visitor's, and it can be asked for before anything has
// started - the button is there from the first paint
const early = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-toggle' : 'Pause' } } );
early.toggle().click();
early.scrollIntoView();
early.advance( 100000 );
check( 'a pause before the container has started holds it back until it is taken up again',
	early.lines[0].classList.contains('nino-is-active') === false );

early.toggle().click();
early.advance( 0 );
check( '...and then it starts', early.lines[0].classList.contains('nino-is-active') === true );

// Where nothing moves any more, there is nothing left to pause
const once = world( { lines : [ 'Ab', 'Cd' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-toggle' : 'Pause' } } );
once.advance( 400 + 90 + 1600 + 400 + 300 + 400 + 90 );
check( 'the button stays while the last line is still being written', once.toggle() !== null );

once.advance( 1600 );
check( 'and is removed - not hidden - after the last line of a single pass', once.toggle() === null && once.host.children.length === 1 );

// The keyboard focus does not fall back to the page's top with the button
const focusing = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-toggle' : 'Pause' } } );
focusing.toggle().focus();
focusing.advance( 400 + 45 + 45 + 1600 );
check( 'a button that has the focus when it is removed hands it to the container',
	focusing.toggle() === null && focused === focusing.wrap && focusing.wrap.getAttribute('tabindex') === '-1' );

const unfocused = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-toggle' : 'Pause' } } );
unfocused.advance( 400 + 45 + 45 + 1600 );
check( '...and one that has not leaves the container as it was', unfocused.toggle() === null && focused === null
	&& unfocused.wrap.getAttribute('tabindex') === null );

const endless = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-loop' : '1', 'data-typewriter-toggle' : 'Pause' } } );
endless.advance( 100000 );
check( 'one that loops keeps its button, since it never stops moving', endless.toggle() !== null );

[ [ 'an empty label', '' ], [ 'a label that is still a fill nobody resolved', '[[/feature/typewriter/pause/label]]' ], [ 'a label of nothing but blanks', '   ' ] ].forEach( function( bad ) {
	const none = world( { lines : [ 'Ab' ], attributes : { 'data-typewriter-start' : 'load', 'data-typewriter-toggle' : bad[1] } } );
	check( bad[0]+ ' draws no button', none.host.children.length === 1 && none.toggle() === null );
} );

const reduced = world( { lines : [ 'Ab' ], reducedMotion : true, attributes : { 'data-typewriter-toggle' : 'Pause' } } );
check( 'a visitor who asked for less motion gets no button, and the markup is left alone',
	reduced.host.children.length === 1 && reduced.lines[0].children.length === 0 && reduced.lines[0].textContent === 'Ab' );

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

/*	The old file let the SyntaxError out of run() and out of the loop in
	init(), which here would end this suite instead of failing a check - so
	the page is driven inside a try and the error becomes the answer	*/
const refusedSelector = ( function() {
	try {
		const page = world( { lines : [ 'Ab' ], refuses : 'p:' } );
		page.scrollIntoView();
		page.advance( 400 );
		return {
			after	: childByClass( page.lines[0], 'nino-typewriter-text' ).textContent,
			own		: childByClass( page.refusedLine, 'nino-typewriter-text' ).textContent,
		};
	}
	catch( error ) {
		return { after : error.name, own : error.name };
	}
} )();

check( 'a lines selector the browser refuses costs that one container, not every typewriter after it on the page', refusedSelector.after === 'A' );
check( 'and that container keeps the default selector, the way every other unreadable attribute keeps its default', refusedSelector.own === 'X' );

const observerless = world( { lines : [ 'Ab' ], withoutObserver : true } );
observerless.advance( 400 );
check( 'a browser without IntersectionObserver has nothing to wait for and simply starts', childByClass( observerless.lines[0], 'nino-typewriter-text' ).textContent === 'A' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exitCode = failures === 0 ? 0 : 1;
