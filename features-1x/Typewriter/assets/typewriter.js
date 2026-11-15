/**
 *	Nino - Typewriter
 *	typewriter.js	Types the lines of every .nino-typewriter on the page one
 *							after the other: fade one in, write it out character by
 *							character with the cursor riding at the writing head, hold
 *							it, take it away again - fading, or erasing it backwards -
 *							then the next one, stopping on the last line or looping
 *							where the container asks for it. No build step; it needs
 *							Nino.js and Nino.ui.js, which every site loads: the
 *							kernel bundles it into the project's own
 *							/.cache/script.js behind them, from the manifest's
 *							assets.
 *
 *							Every timing is a data attribute on the container itself
 *							(see OPTIONS below and the feature's manual) - this
 *							file is a static asset, never rendered through the fill
 *							engine (docs/development.md, "Assets Are Not Templates"),
 *							so the element is the only place a project can say how
 *							fast its own typewriter writes.
 *
 *							The markup this leaves is three spans per line: what is
 *							typed, what is not typed yet - laid out, only invisible,
 *							so nothing moves while a line grows - and the line's own
 *							text once for a screen reader, in the kernel's
 *							.nino-sr-only. The cursor is one element for the whole
 *							typewriter, moved between the two halves of whichever line
 *							is being written. typewriter.css styles all of it, and
 *							matches none of it before this file runs: without
 *							JavaScript, and for a visitor who asked for reduced motion,
 *							the container stays what the markup says it is - paragraphs
 *							below one another.
 *
 *							A container that carries data-typewriter-toggle gets a
 *							button after it that pauses the typing and takes it up
 *							again (WCAG 2.2.2). The button is the container's next
 *							sibling, never a child of it: a heading used as a
 *							typewriter would take the button into its own name. It is
 *							taken away again once nothing moves any more, which is
 *							the end of the last line of a typewriter that does not
 *							loop.
 *
 *							Browser baseline is its stylesheet's: :has() there,
 *							IntersectionObserver and Array.from here.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
( function() {

	'use strict';

	// Every one of these is overridable on the container, the key dashed and
	// prefixed: data-typewriter-speed, data-typewriter-backspace-speed, ... The
	// rules Nino.ui.options() reads them by, each with its default as the
	// fallback. A flag says yes with 1, true, yes or on and no with 0, false, no
	// or off; any other word is no answer and leaves it at its default
	var OPTIONS = {
		lines				: { type : 'selector', fallback : 'p' },									// selector of the lines inside the container
		start				: { type : 'enum', values : [ 'view', 'load' ], fallback : 'view' },		// 'view' starts in the viewport, 'load' right away
		'start-delay'		: { type : 'int', fallback : 0 },											// ms before the first line, once
		speed				: { type : 'int', fallback : 45 },											// ms per typed character
		hold				: { type : 'int', fallback : 1600 },										// ms the finished line stays
		exit				: { type : 'enum', values : [ 'fade', 'backspace' ], fallback : 'fade' },	// 'fade' fades the line out, 'backspace' erases it
		fade				: { type : 'int', fallback : 400 },											// ms of the fade in and out, 0 switches it off
		'backspace-speed'	: { type : 'int', fallback : 25 },											// ms per erased character
		pause				: { type : 'int', fallback : 300 },											// ms between one line and the next
		loop				: { type : 'flag', fallback : false },										// true starts over after the last line instead of stopping
		// An empty cursor is a value of its own: a typewriter without a cursor
		// character is a typewriter without a cursor
		cursor				: { type : 'text', fallback : '|', empty : true },							// the cursor character, '' leaves it out
		toggle				: { type : 'text', fallback : '' }											// the label of the pause button, '' for none
	};

	// The two classes the toggle takes; the kernel's own button classes carry
	// its look, so what this file adds is the pressed state (typewriter.css)
	var BUTTON = 'nino-btn nino-btn--outline nino-btn--small nino-typewriter-toggle';

	/**
	 *	The label a container's toggle button carries, or '' where it gets
	 *	none: no attribute, an empty one, or one that still holds a text fill
	 *	nobody resolved - a button that says "[[/feature/typewriter/pause/label]]" is worse
	 *	than none
	 *
	 *	@param		{string}	text					What data-typewriter-toggle holds, as Nino.ui.options() read it
	 *
	 *	@return		{string}
	 */
	function toggleLabel( text ) {

		var label = text.trim();

		return label.indexOf( '[[' ) === -1 ? label : '';
	}

	/**
	 *	Prepare one .nino-typewriter and run its lines
	 *
	 *	@param		{Element}	el						Typewriter container
	 *
	 *	@return		void
	 */
	function run( el ) {

		var opt = Nino.ui.options( el, 'typewriter', OPTIONS );
		var lines = opt.lines;

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

			var label = toggleLabel( opt.toggle );

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
					el.classList.add( 'nino-is-paused' );
					pause();
				}
				else {
					el.classList.remove( 'nino-is-paused' );
					resume();
				}
			} );

			el.parentNode.insertBefore( toggle, el.nextSibling );
		}

		var cursor = document.createElement( 'span' );
		cursor.className = 'nino-typewriter-cursor';
		cursor.setAttribute( 'aria-hidden', 'true' );
		cursor.textContent = opt.cursor;

		var sequence = [];
		var index = 0;
		var typed = 0;

		/*	Every line is split in three, and all three stay in it: the part
			already typed, the part still to come - laid out, only invisible -
			and beside them the line's own text once, the only one assistive
			technology is left to read. A cursor and a half-written word are
			decoration; the line is content. textContent throughout, the lines
			are the project's own text	*/
		for( var i = 0; i < lines.length; i++ ) {

			// What the browser would have rendered, not what the file happens to
			// be indented with: an untrimmed source would start every line by
			// typing its own newline and tabs
			var source = lines[i].textContent.replace( /\s+/g, ' ' ).trim();
			var reader = document.createElement( 'span' );
			var written = document.createElement( 'span' );
			var rest = document.createElement( 'span' );

			reader.className = 'nino-sr-only';
			reader.textContent = source;
			written.className = 'nino-typewriter-text';
			written.setAttribute( 'aria-hidden', 'true' );
			rest.className = 'nino-typewriter-rest';
			rest.setAttribute( 'aria-hidden', 'true' );
			rest.textContent = source;

			lines[i].textContent = '';
			lines[i].appendChild( reader );
			lines[i].appendChild( written );
			lines[i].appendChild( rest );
			lines[i].classList.add( 'nino-typewriter-line' );
			lines[i].style.transitionDuration = ( opt.exit === 'fade' ? opt.fade : 0 ) + 'ms';

			// Array.from(), not the string: slicing a string by index cuts an
			// emoji in half and types the half
			sequence.push( { line : lines[i], text : written, rest : rest, chars : Array.from( source ) } );
		}

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
			sequence[index].line.classList.add( 'nino-is-active' );
			later( type, opt.exit === 'fade' ? opt.fade : 0 );
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
		 *	Take the finished line away - fading it out, or erasing it one
		 *	character at a time - and queue the next one. The last line of a
		 *	typewriter that does not loop stays where it is, without its
		 *	cursor and without its pause button: an animation that has ended
		 *	should not go on blinking, or offer to stop what has stopped
		 *
		 *	@return		void
		 */
		function leave() {

			if( opt.loop === false && index === sequence.length - 1 ) {
				if( cursor.parentNode !== null )
					cursor.parentNode.removeChild( cursor );

				// Removed rather than hidden: .nino-btn's own display rule
				// beats the [hidden] attribute. A button that has the keyboard
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

			if( opt.exit === 'backspace' && typed > 0 ) {
				typed--;
				write();
				later( leave, opt['backspace-speed'] );
				return;
			}

			sequence[index].line.classList.remove( 'nino-is-active' );
			index = ( index + 1 ) % sequence.length;

			later( show, ( opt.exit === 'fade' ? opt.fade : 0 ) + opt.pause );
		}

		/**
		 *	Start this typewriter, after its start delay
		 *
		 *	@return		void
		 */
		function begin() {
			later( show, opt['start-delay'] );
		}

		button();

		if( opt.start === 'load' )
			begin();
		else
			Nino.ui.whenVisible( el, begin );
	}

	/**
	 *	Run every .nino-typewriter the page has
	 *
	 *	@return		void
	 */
	function init() {

		// A visitor who asked for less motion gets none of this: nothing is
		// rewritten, no line class is written, and not one rule in
		// typewriter.css matches - the container stays the paragraphs it is
		if( Nino.ui.reducedMotion() === true )
			return;

		var typewriters = document.querySelectorAll( '.nino-typewriter' );
		for( var i = 0; i < typewriters.length; i++ )
			run( typewriters[i] );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
