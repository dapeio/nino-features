/**
 *	Nino - Typewriter
 *	typewriter.js	Types the lines of every .typewriter on the page one after
 *							the other: fade one in, write it out character by character
 *							with the cursor riding at the writing head, hold it, fade it
 *							out, then the next one - stopping on the last line, or
 *							looping where the container asks for it. No build step; it
 *							needs Nino.js, which every site loads: the kernel bundles it
 *							into the project's own /.cache/script.js behind it, from the
 *							manifest's assets.
 *
 *							The container is drawn by the component typewriter: its lines
 *							are the .typewriter-line children, and the speed, the hold,
 *							the loop and the label of the pause button are data-typewriter-*
 *							attributes on it. This file is a static asset, never rendered
 *							through the fill engine (docs/development.md, "Assets Are Not
 *							Templates"), so the element is the only place it can read them.
 *
 *							The markup this leaves is three spans per line: what is
 *							typed, what is not typed yet - laid out, only invisible, so
 *							nothing moves while a line grows - and the line's own text
 *							once for a screen reader. The cursor is one element for the
 *							whole typewriter, moved between the two halves of whichever
 *							line is being written. Every line is written as text, never
 *							as markup. The part of the group styles all of it, and
 *							matches none of it before this file runs: without JavaScript,
 *							and for a visitor who asked for reduced motion, the lines
 *							stand below one another as the markup has them.
 *
 *							Every typewriter gets a button after it that pauses the typing
 *							and takes it up again (WCAG 2.2.2). The button is the
 *							container's next sibling, never a child of it: a heading used
 *							as a typewriter would take the button into its own name. It
 *							is taken away again once nothing moves any more, which is the
 *							end of the last line of a typewriter that does not loop.
 *
 *							Browser baseline is its stylesheet's: IntersectionObserver
 *							and Array.from here.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.typewriter';

	// The class of the lines the component draws
	var LINE = 'typewriter-line';

	// What the container does not say: ms per typed character, ms a finished line stays
	var SPEED_DEFAULT = 45;
	var HOLD_DEFAULT = 1600;

	// ms of the fade in and out of a line, and ms between a line and the next one; the fade is the one the part
	// gives the lines (typewriter-is-running > .typewriter-line)
	var FADE = 400;
	var PAUSE = 300;

	// The class of the pause button, whose look is the standard part of the group
	var BUTTON = 'typewriter-toggle';

	/**
	 *	What a container says about itself, read off its data-typewriter-* attributes: the milliseconds per character
	 *	and the milliseconds a finished line stays (a number, else the default), whether it loops (only 1, true, yes and
	 *	on say so; 0, false, no and off say not; any other word is no answer and leaves it at no), and the label of
	 *	the pause button
	 *
	 *	@param		{Element}		el
	 *
	 *	@return		{Object}
	 */
	function options( el ) {

		var number = function( name, fallback, least ) {

			var value = parseFloat( el.getAttribute( 'data-typewriter-' + name ) );

			return isFinite( value ) === true && value >= least ? value : fallback;
		};

		var loop = ( el.getAttribute( 'data-typewriter-loop' ) || '' ).trim().toLowerCase();

		return {
			speed	: number( 'speed', SPEED_DEFAULT, 1 ),
			hold	: number( 'hold', HOLD_DEFAULT, 0 ),
			loop	: [ '1', 'true', 'yes', 'on' ].indexOf( loop ) !== -1,
			label	: ( el.getAttribute( 'data-typewriter-label' ) || '' ).trim()
		};
	}

	/**
	 *	The label the pause button carries, or '' where it gets none: no attribute, an empty one, or one that still
	 *	holds a text fill nobody resolved - a button that says "[[/feature/typewriter/pause/label]]" is worse than none
	 *
	 *	@param		{string}	text					What data-typewriter-label holds
	 *
	 *	@return		{string}
	 */
	function toggleLabel( text ) {
		return text.indexOf( '[[' ) === -1 ? text : '';
	}

	/**
	 *	Whether the visitor asked for less motion
	 *
	 *	@return		{boolean}
	 */
	function reducedMotion() {
		return typeof window.matchMedia === 'function' && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches === true;
	}

	/**
	 *	Call back once the element has been in the viewport; at once where the browser has no IntersectionObserver
	 *
	 *	@param		{Element}		el
	 *	@param		{Function}	callback
	 *
	 *	@return		void
	 */
	function whenVisible( el, callback ) {

		if( typeof window.IntersectionObserver !== 'function' ) {
			callback();
			return;
		}

		var observer = new window.IntersectionObserver( function( entries ) {

			for( var i = 0; i < entries.length; i++ )
				if( entries[i].isIntersecting === true ) {
					observer.disconnect();
					callback();
					return;
				}
		} );

		observer.observe( el );
	}

	/**
	 *	The lines of a container: its children the component drew
	 *
	 *	@param		{Element}		el
	 *
	 *	@return		{Array}
	 */
	function linesOf( el ) {

		var found = [];

		for( var i = 0; i < el.children.length; i++ )
			if( el.children[i].classList.contains( LINE ) === true )
				found.push( el.children[i] );

		return found;
	}

	/**
	 *	Prepare one .typewriter and run its lines
	 *
	 *	@param		{Element}	el						Typewriter container
	 *
	 *	@return		void
	 */
	function run( el ) {

		var opt = options( el );
		var lines = linesOf( el );

		if( lines.length === 0 )
			return;

		/*	Every step of the animation waits for the next one through later(),
			which remembers the one that is pending. A pause cancels the timer
			and keeps the step; taking it up again arms the step with its full
			delay, rather than working out how much of it was left - the clock
			is the browser's and a typewriter does not need it to the
			millisecond	*/
		var pending = null;
		var timer = null;
		var paused = false;
		var toggle = null;

		function fire() {
			var step = pending;
			pending = null;
			timer = null;
			step.callback();
		}

		function later( callback, delay ) {
			pending = { callback : callback, delay : delay };

			if( paused === false )
				timer = window.setTimeout( fire, delay );
		}

		function pause() {
			paused = true;

			if( timer !== null )
				window.clearTimeout( timer );

			timer = null;
		}

		function resume() {
			paused = false;

			if( pending !== null && timer === null )
				timer = window.setTimeout( fire, pending.delay );
		}

		/**
		 *	Draw the pause button after the container, once. createElement()
		 *	and textContent only: the label is the project's own text
		 *
		 *	@return		void
		 */
		function button() {

			var label = toggleLabel( opt.label );

			if( label === '' || el.parentNode === null )
				return;

			toggle = document.createElement( 'button' );
			toggle.type = 'button';
			toggle.className = BUTTON;
			toggle.setAttribute( 'aria-pressed', 'false' );
			toggle.textContent = label;

			toggle.addEventListener( 'click', function() {

				var on = toggle.getAttribute( 'aria-pressed' ) !== 'true';

				toggle.setAttribute( 'aria-pressed', on === true ? 'true' : 'false' );

				if( on === true ) {
					el.classList.add( 'typewriter-is-paused' );
					pause();
				}
				else {
					el.classList.remove( 'typewriter-is-paused' );
					resume();
				}
			} );

			el.parentNode.insertBefore( toggle, el.nextSibling );
		}

		var cursor = document.createElement( 'span' );
		cursor.className = 'typewriter-cursor';
		cursor.setAttribute( 'aria-hidden', 'true' );

		var sequence = [];
		var index = 0;
		var typed = 0;

		/*	Every line is split in three, and all three stay in it: the part
			already typed, the part still to come - laid out, only invisible -
			and beside them the line's own text once, the only one assistive
			technology is left to read. A cursor and a half-written word are
			decoration; the line is content. textContent throughout: a line
			is text and never becomes markup	*/
		for( var i = 0; i < lines.length; i++ ) {

			// What the browser would have rendered, not what the file happens to
			// be indented with: an untrimmed source would start every line by
			// typing its own newline and tabs
			var source = lines[i].textContent.replace( /\s+/g, ' ' ).trim();
			var reader = document.createElement( 'span' );
			var written = document.createElement( 'span' );
			var rest = document.createElement( 'span' );

			reader.className = 'typewriter-reader';
			reader.textContent = source;
			written.className = 'typewriter-written';
			written.setAttribute( 'aria-hidden', 'true' );
			rest.className = 'typewriter-rest';
			rest.setAttribute( 'aria-hidden', 'true' );
			rest.textContent = source;

			lines[i].textContent = '';
			lines[i].appendChild( reader );
			lines[i].appendChild( written );
			lines[i].appendChild( rest );

			// Array.from(), not the string: slicing a string by index cuts an
			// emoji in half and types the half
			sequence.push( { line : lines[i], text : written, rest : rest, chars : Array.from( source ) } );
		}

		el.classList.add( 'typewriter-is-running' );

		/**
		 *	Split the current line at the character count typed so far - the
		 *	cursor between the two halves is the writing head
		 *
		 *	@return		void
		 */
		function write() {
			sequence[index].text.textContent = sequence[index].chars.slice( 0, typed ).join( '' );
			sequence[index].rest.textContent = sequence[index].chars.slice( typed ).join( '' );
		}

		/**
		 *	Move the cursor into the line at `index` and fade it in, empty
		 *
		 *	@return		void
		 */
		function show() {
			typed = 0;
			write();
			sequence[index].line.insertBefore( cursor, sequence[index].rest );
			sequence[index].line.classList.add( 'typewriter-is-active' );
			later( type, FADE );
		}

		/**
		 *	Write the next character of the current line, and hold the line
		 *	once it is complete
		 *
		 *	@return		void
		 */
		function type() {

			if( typed < sequence[index].chars.length ) {
				typed++;
				write();
				later( type, opt.speed );
				return;
			}

			later( leave, opt.hold );
		}

		/**
		 *	Take the finished line away and queue the next one. The last line
		 *	of a typewriter that does not loop stays where it is, without its
		 *	cursor and without its pause button: an animation that has ended
		 *	should not go on blinking, or offer to stop what has stopped
		 *
		 *	@return		void
		 */
		function leave() {

			if( opt.loop === false && index === sequence.length - 1 ) {
				if( cursor.parentNode !== null )
					cursor.parentNode.removeChild( cursor );

				// Removed rather than hidden: a button that has the keyboard
				// focus hands it to the container first, or it would fall
				// back to the page's top
				if( toggle !== null && toggle.parentNode !== null ) {
					if( document.activeElement === toggle ) {
						el.setAttribute( 'tabindex', '-1' );
						el.focus();
					}

					toggle.parentNode.removeChild( toggle );
				}

				return;
			}

			sequence[index].line.classList.remove( 'typewriter-is-active' );
			index = ( index + 1 ) % sequence.length;

			later( show, FADE + PAUSE );
		}

		button();

		whenVisible( el, function() {
			later( show, 0 );
		} );
	}

	/**
	 *	Run every .typewriter the page has
	 *
	 *	@return		void
	 */
	function init() {

		// A visitor who asked for less motion gets none of this: nothing is
		// rewritten, no class is written, and not one rule of the part
		// matches - the lines stay below one another
		if( reducedMotion() === true )
			return;

		var typewriters = document.querySelectorAll( SELECTOR );
		for( var i = 0; i < typewriters.length; i++ )
			run( typewriters[i] );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
