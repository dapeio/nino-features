/**
 *	Nino - Typewriter
 *	typewriter.js	Types the lines of every .nino-typewriter on the page one
 *							after the other: fade one in, write it out character by
 *							character with the cursor riding at the writing head, hold
 *							it, take it away again - fading, or erasing it backwards -
 *							then the next one, looping or stopping on the last line.
 *							No dependencies, no build step - not even on Nino.js:
 *							bundled into the project's own /.cache/script.js the same
 *							way the kernel bundles Nino.js/Nino.ui.js (see
 *							Typewriter::init()), and a project that dropped the
 *							kernel's own scripts still gets a working typewriter.
 *
 *							Every timing is a data attribute on the container itself
 *							(see DEFAULTS below and this feature's README.md) - this
 *							file is a static asset, never rendered through the fill
 *							engine (docs/development.md, "Assets Are Not Templates"),
 *							so the element is the only place a project can say how
 *							fast its own typewriter writes.
 *
 *							The markup this leaves is three spans per line: what is
 *							typed, what is not typed yet - laid out, only invisible,
 *							so nothing moves while a line grows - and the line's own
 *							text once for a screen reader. The cursor is one element
 *							for the whole typewriter, moved between the two halves of
 *							whichever line is being written. typewriter.css styles
 *							all of it, and matches none of it before this file runs:
 *							without JavaScript, and for a visitor who asked for
 *							reduced motion, the container stays what the markup says
 *							it is - paragraphs below one another.
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
	// prefixed: data-typewriter-speed, data-typewriter-backspace-speed, ...
	var DEFAULTS = {
		lines					: 'p',			// selector of the lines inside the container
		start					: 'view',		// 'view' starts in the viewport, 'load' right away
		startDelay		: 0,				// ms before the first line, once
		speed					: 45,				// ms per typed character
		hold					: 1600,			// ms the finished line stays
		exit					: 'fade',		// 'fade' fades the line out, 'backspace' erases it
		fade					: 400,			// ms of the fade in and out, 0 switches it off
		backspaceSpeed	: 25,			// ms per erased character
		pause					: 300,			// ms between one line and the next
		loop					: true,			// false stops on the last line instead
		cursor				: '|'				// the cursor character, '' leaves it out
	};

	var OFF = [ '0', 'false', 'off', 'no' ];

	/**
	 *	One data-typewriter-* attribute, '' and a missing attribute alike
	 *	falling back to the default
	 *
	 *	@param		{Element}	el						Typewriter container
	 *	@param		{string}	name					Attribute without its data-typewriter- prefix
	 *	@param		{string}	fallback			Default to keep when it is missing or empty
	 *
	 *	@return		{string}
	 */
	function text( el, name, fallback ) {
		var value = el.getAttribute( 'data-typewriter-' + name );
		return ( value === null || value === '' ) ? fallback : value;
	}

	/**
	 *	The same as a count of milliseconds. An unreadable attribute makes
	 *	parseInt() answer NaN, and a NaN would time the whole animation with
	 *	it - a typo in one attribute must cost that one attribute, no more
	 *
	 *	@param		{Element}	el						Typewriter container
	 *	@param		{string}	name					Attribute without its data-typewriter- prefix
	 *	@param		{number}	fallback			Default to keep when it is not a positive number
	 *
	 *	@return		{number}								Milliseconds
	 */
	function number( el, name, fallback ) {
		var value = parseInt( text( el, name, '' ), 10 );
		return ( isNaN( value ) === true || value < 0 ) ? fallback : value;
	}

	/**
	 *	The same as a switch. '0', 'false', 'off' and 'no' turn it off, any
	 *	other value on - a default that is on has to stay switchable
	 *
	 *	@param		{Element}	el						Typewriter container
	 *	@param		{string}	name					Attribute without its data-typewriter- prefix
	 *	@param		{boolean}	fallback			Default to keep when it is missing or empty
	 *
	 *	@return		{boolean}
	 */
	function flag( el, name, fallback ) {
		var value = text( el, name, '' );
		return value === '' ? fallback : OFF.indexOf( value.toLowerCase() ) === -1;
	}

	/**
	 *	One container's settings, read off its data attributes - everything
	 *	it does not carry itself stays at its DEFAULTS value, so a plain
	 *	<div class="nino-typewriter"> already runs
	 *
	 *	@param		{Element}	el						Typewriter container
	 *
	 *	@return		{Object}								The resolved settings of this one container
	 */
	function options( el ) {

		// The one attribute an empty value says something with: a typewriter
		// without a cursor character is a typewriter without a cursor
		var cursor = el.getAttribute( 'data-typewriter-cursor' );

		return {
			lines					: text( el, 'lines', DEFAULTS.lines ),
			start					: text( el, 'start', DEFAULTS.start ) === 'load' ? 'load' : 'view',
			startDelay		: number( el, 'start-delay', DEFAULTS.startDelay ),
			speed					: number( el, 'speed', DEFAULTS.speed ),
			hold					: number( el, 'hold', DEFAULTS.hold ),
			exit					: text( el, 'exit', DEFAULTS.exit ) === 'backspace' ? 'backspace' : 'fade',
			fade					: number( el, 'fade', DEFAULTS.fade ),
			backspaceSpeed	: number( el, 'backspace-speed', DEFAULTS.backspaceSpeed ),
			pause					: number( el, 'pause', DEFAULTS.pause ),
			loop					: flag( el, 'loop', DEFAULTS.loop ),
			cursor				: cursor === null ? DEFAULTS.cursor : cursor
		};
	}

	/**
	 *	Whether the visitor asked their system for reduced motion. A browser
	 *	without matchMedia() answers the same as one whose visitor never set
	 *	the preference: no
	 *
	 *	@return		{boolean}
	 */
	function prefersReducedMotion() {
		return typeof window.matchMedia === 'function'
			&& window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches === true;
	}

	/**
	 *	Call back once the element has been in the viewport, and only then -
	 *	a typewriter nobody has scrolled to yet would otherwise be through
	 *	its lines before it is read. Without IntersectionObserver there is
	 *	nothing to wait for and it starts right away
	 *
	 *	@param		{Element}		el					Typewriter container
	 *	@param		{Function}	callback		Run once, when it is visible
	 *
	 *	@return		void
	 */
	function whenVisible( el, callback ) {

		if( typeof window.IntersectionObserver !== 'function' ) {
			callback();
			return;
		}

		var observer = new window.IntersectionObserver( function( entries ) {
			for( var i = 0; i < entries.length; i++ ) {
				if( entries[i].isIntersecting !== true )
					continue;
				observer.disconnect();
				callback();
				return;
			}
		} );

		observer.observe( el );
	}

	/**
	 *	Prepare one .nino-typewriter and run its lines
	 *
	 *	@param		{Element}	el						Typewriter container
	 *
	 *	@return		void
	 */
	function run( el ) {

		var opt = options( el );
		var lines = el.querySelectorAll( opt.lines );

		if( lines.length === 0 )
			return;

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

			reader.className = 'nino-typewriter-reader';
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
			window.setTimeout( type, opt.exit === 'fade' ? opt.fade : 0 );
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
				window.setTimeout( type, opt.speed );
				return;
			}

			window.setTimeout( leave, opt.hold );
		}

		/**
		 *	Take the finished line away - fading it out, or erasing it one
		 *	character at a time - and queue the next one. The last line of a
		 *	typewriter that does not loop stays where it is, without its
		 *	cursor: an animation that has ended should not go on blinking
		 *
		 *	@return		void
		 */
		function leave() {

			if( opt.loop === false && index === sequence.length - 1 ) {
				if( cursor.parentNode !== null )
					cursor.parentNode.removeChild( cursor );
				return;
			}

			if( opt.exit === 'backspace' && typed > 0 ) {
				typed--;
				write();
				window.setTimeout( leave, opt.backspaceSpeed );
				return;
			}

			sequence[index].line.classList.remove( 'nino-is-active' );
			index = ( index + 1 ) % sequence.length;

			window.setTimeout( show, ( opt.exit === 'fade' ? opt.fade : 0 ) + opt.pause );
		}

		/**
		 *	Start this typewriter, after its start delay
		 *
		 *	@return		void
		 */
		function begin() {
			window.setTimeout( show, opt.startDelay );
		}

		if( opt.start === 'load' )
			begin();
		else
			whenVisible( el, begin );
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
		if( prefersReducedMotion() === true )
			return;

		var typewriters = document.querySelectorAll( '.nino-typewriter' );
		for( var i = 0; i < typewriters.length; i++ )
			run( typewriters[i] );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
