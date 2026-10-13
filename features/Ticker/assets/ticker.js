/**
 *	Nino - Ticker
 *	ticker.js		Makes a row run - once, or round and round without a seam. No
 *					build step; it needs Nino.js and Nino.ui.js, which every site
 *					loads: the kernel bundles it into the project's own
 *					/.cache/script.js behind them, from the manifest's assets.
 *
 *					The seam is the whole problem. A row that simply scrolls runs
 *					out and jumps back, and the jump is what everybody sees. So the
 *					row's own children are copied until they are wider than the box
 *					plus one length of the row, and the whole of it is moved by
 *					exactly one original width - at which point the copy is standing
 *					where the original stood and the animation can start again with
 *					nothing moving.
 *
 *					The copies are aria-hidden: to a screen reader the row is read
 *					once, which is how many times it is there.
 *
 *					The movement is a CSS animation rather than a timer. A browser
 *					runs it off the main thread, and a tab in the background stops
 *					paying for it - neither of which is true of a script that moves
 *					something every frame.
 *
 *					By default a row runs exactly one cycle - one original width
 *					plus one gap, at its own speed - and stops on a frame identical
 *					to its first, because the copy stands where the original stood.
 *					It waits until it has been scrolled into view, or a footer row
 *					would be through its pass before anybody saw it. data-ticker-loop
 *					asks for the endless loop instead (WCAG 2.2.2: anything that
 *					moves for more than five seconds needs a way to pause it - a
 *					loop always, one pass when it is that long; see
 *					data-ticker-toggle).
 *
 *					data-ticker-toggle on a row puts a pause button after it, as its
 *					next sibling - never inside the track, which is cloned. A press
 *					pauses the animation (touch and keyboard included, unlike
 *					:hover and :focus-within) and the button is taken away again
 *					once a single pass has ended.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-ticker';

	// Pixels per second, where the row does not say. Slow enough to read a
	// word on the way past
	var SPEED_DEFAULT = 40;

	// How many copies are allowed at most. A row of one narrow logo in a wide
	// box would otherwise be copied until the page is a thousand elements long
	var COPIES_MAX = 40;

	// How long the window has to stand still before the rows are measured
	// again. A window dragged across the screen reports every step of the
	// drag, and every one of them would cost a measurement of every row
	var SETTLE = 150;

	// What a row says about itself, as Nino.ui.options() reads it off its
	// data-ticker-* attributes: its speed, whether it loops - only 1, true,
	// yes and on say so, like every flag of the kernel - and the label of the
	// pause button it asks for
	var OPTIONS = {
		speed	: { type : 'number', fallback : SPEED_DEFAULT },
		loop	: { type : 'flag', fallback : false },
		toggle	: { type : 'text', fallback : '' }
	};

	// The kernel's own button classes carry the look of the pause button;
	// ticker.css adds the pressed state
	var BUTTON = 'nino-btn nino-btn--outline nino-btn--small nino-ticker-toggle';

	// The rows this file is running, each with the box width its copies were
	// made for
	var rows = [];

	var settling = null;

	/**
	 *	The label a row's toggle button carries, or '' where it gets none: no
	 *	attribute, an empty one, or one that still holds a text fill nobody
	 *	resolved - a button that says "[[/feature/ticker/pause/label]]" is worse than none
	 *
	 *	@param		{string}		text			What data-ticker-toggle holds, as Nino.ui.options() read it
	 *
	 *	@return		{string}
	 */
	function toggleLabel( text ) {

		var label = text.trim();

		return label.indexOf( '[[' ) === -1 ? label : '';
	}

	/**
	 *	Draw the pause button after a row, once. Not inside it: the track is
	 *	cloned, and a button in a clone is a second one. createElement() and
	 *	textContent only - the label is the project's own text. Nothing is
	 *	drawn under reduced motion, where nothing moves, and nothing before
	 *	the row runs
	 *
	 *	@param		{Object}		entry			One row
	 *
	 *	@return		void
	 */
	function button( entry ) {

		var row = entry.row;

		if( entry.toggle !== null || entry.done === true || entry.label === '' || row.parentNode === null || Nino.ui.reducedMotion() === true )
			return;

		entry.toggle = document.createElement( 'button' );
		entry.toggle.type = 'button';
		entry.toggle.className = BUTTON;
		entry.toggle.setAttribute( 'aria-pressed', 'false' );
		entry.toggle.textContent = entry.label;

		entry.toggle.addEventListener( 'click', function() {

			var on = entry.toggle.getAttribute( 'aria-pressed' ) !== 'true';

			entry.toggle.setAttribute( 'aria-pressed', on === true ? 'true' : 'false' );

			if( on === true )
				row.classList.add( 'nino-is-paused' );
			else
				row.classList.remove( 'nino-is-paused' );
		} );

		row.parentNode.insertBefore( entry.toggle, row.nextSibling );
	}

	/**
	 *	A single pass is over: the row stands on a frame identical to its first.
	 *	It is marked done, which the stylesheet turns into no animation at all -
	 *	so a later resize, which sets the duration again, cannot start it over -
	 *	and the pause button, which has nothing left to pause, is removed
	 *	rather than hidden: .nino-btn's own display rule beats [hidden]
	 *
	 *	@param		{Object}		entry			One row
	 *
	 *	@return		void
	 */
	function finish( entry ) {

		entry.done = true;
		entry.row.classList.add( 'nino-is-done' );
		entry.row.classList.remove( 'nino-is-paused' );

		if( entry.toggle !== null && entry.toggle.parentNode !== null ) {

			// A button that has the keyboard focus hands it to the row first,
			// or it would fall back to the page's top
			if( document.activeElement === entry.toggle ) {
				entry.row.setAttribute( 'tabindex', '-1' );
				entry.row.focus();
			}

			entry.toggle.parentNode.removeChild( entry.toggle );
		}

		entry.toggle = null;
	}

	/**
	 *	Take this file's own copies back out, so what is measured is the row
	 *	the project wrote rather than that row plus the copies made for a box
	 *	of some other width
	 *
	 *	@param		{Element}		track
	 *
	 *	@return		void
	 */
	function strip( track ) {

		for( var i = track.children.length - 1; i >= 0; i-- )
			if( track.children[i].getAttribute( 'data-ticker-copy' ) !== null )
				track.removeChild( track.children[i] );
	}

	/**
	 *	Copy the run of children until it is wider than the box plus one length
	 *	of the row, and set the animation going over exactly one original width
	 *
	 *	@param		{Object}		entry			One row, and the box width its copies were made for
	 *
	 *	@return		void
	 */
	function run( entry ) {

		var row = entry.row;
		var track = row.querySelector( '.nino-ticker-track' );

		// A finished pass stays finished: measuring it again would copy a
		// row nothing is moving
		if( track === null || entry.done === true )
			return;

		strip( track );

		if( track.children.length === 0 )
			return;

		var originals = [];
		var content = 0;

		for( var i = 0; i < track.children.length; i++ ) {
			originals.push( track.children[i] );
			content += track.children[i].offsetWidth;
		}

		/*	Nothing to show yet - a picture that has not loaded has no width,
			and a row of them would run across nothing. The items' own widths,
			not the track's: a flex row with a gap is that gap wide even when
			everything in it is empty	*/
		if( content <= 0 )
			return;

		var one = track.scrollWidth;

		/*	The box plus one length of the row, not the row twice over: what has
			to be covered is the distance the track travels plus the box it
			travels across, or the end of the copies comes into view before the
			reset	*/
		var needed = row.clientWidth + one;
		var width = one;
		var copies = 0;

		while( width < needed && copies < COPIES_MAX ) {

			for( var c = 0; c < originals.length; c++ ) {
				var copy = originals[c].cloneNode( true );
				copy.setAttribute( 'aria-hidden', 'true' );
				copy.setAttribute( 'data-ticker-copy', '' );
				disarm( copy );
				track.appendChild( copy );
			}

			width += one;
			copies++;
		}

		/*	The distance is where the first copy stands, measured rather than
			computed: the track's own width leaves out the gap between the last
			original and the first copy, so a row with a gap - which is what
			this track has - ended its cycle one gap short of where the copy
			stands, and jumped by that gap every time round. The very thing the
			copies are here to prevent	*/
		var distance = ( track.children[ originals.length ] !== undefined )
			? track.children[ originals.length ].offsetLeft - originals[0].offsetLeft
			: one;

		if( distance <= 0 )
			return;

		track.style.setProperty( '--nino-ticker-distance', distance + 'px' );
		track.style.setProperty( '--nino-ticker-duration', ( distance / entry.speed ) + 's' );

		entry.across = row.clientWidth;

		row.classList.add( 'nino-is-running' );

		// Once, and only now that the row is measured: before that there is
		// nothing for the button to pause. A single pass is over when the
		// track's animation ends
		if( entry.listening === false && entry.loop === false ) {
			entry.listening = true;
			track.addEventListener( 'animationend', function( event ) {
				if( event.target === track )
					finish( entry );
			} );
		}

		button( entry );
	}

	/**
	 *	Every row whose box is not the width its copies were made for. A
	 *	resize says nothing about the width on its own - a phone's address bar
	 *	sliding away is one - and copying a row again for a box that did not
	 *	change is work with nothing to show for it
	 *
	 *	@return		void
	 */
	function remeasure() {

		settling = null;

		for( var i = 0; i < rows.length; i++ )
			if( rows[i].done === false && rows[i].across !== rows[i].row.clientWidth )
				run( rows[i] );
	}

	/**
	 *	One measurement once the window has come to rest, rather than one per
	 *	event of a window being dragged across the screen
	 *
	 *	@return		void
	 */
	function schedule() {

		if( settling !== null )
			window.clearTimeout( settling );

		settling = window.setTimeout( remeasure, SETTLE );
	}

	/**
	 *	Take a copy out of everything but the picture: an id is the original's
	 *	and may only exist once, and a link or a button in a copy is a stop on
	 *	the way through the page that reads the same as the one before it.
	 *	aria-hidden takes a copy out of the screen reader's list but leaves it
	 *	in the tab order, which is the one thing it cannot do by itself
	 *
	 *	@param		{Element}		copy
	 *
	 *	@return		void
	 */
	function disarm( copy ) {

		var withId = copy.querySelectorAll( '[id]' );

		for( var i = 0; i < withId.length; i++ )
			withId[i].removeAttribute( 'id' );

		if( copy.id )
			copy.removeAttribute( 'id' );

		var focusable = copy.querySelectorAll( 'a[href], button, input, select, textarea, [tabindex]' );

		for( var f = 0; f < focusable.length; f++ )
			focusable[f].setAttribute( 'tabindex', '-1' );
	}

	/**
	 *	Every row on the page
	 *
	 *	@return		void
	 */
	function init() {

		var found = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < found.length; i++ ) {

			var options = Nino.ui.options( found[i], 'ticker', OPTIONS );

			// The width it was built for, which nothing has been built for yet
			var entry = { row : found[i], across : -1, speed : options.speed, loop : options.loop, label : toggleLabel( options.toggle ), done : false, toggle : null, listening : false };

			rows.push( entry );

			if( entry.loop === true )
				found[i].classList.add( 'nino-is-looping' );

			/*	Held until it has been seen. The class is on before the row is
				measured, so the animation the measurement starts begins paused
				rather than running for the length of the time it takes to
				scroll there	*/
			if( typeof window.IntersectionObserver === 'function' )
				found[i].classList.add( 'nino-is-waiting' );

			run( entry );

			Nino.ui.whenVisible( found[i], ( function( row ) {
				return function() { row.classList.remove( 'nino-is-waiting' ) };
			} )( found[i] ) );
		}

		if( rows.length === 0 )
			return;

		/*	The copies cover the box as it was when they were made. A window
			that gets wider afterwards is a box the row no longer reaches
			across, and what the visitor sees for the rest of every cycle is
			the stretch of nothing behind the last copy	*/
		window.addEventListener( 'resize', schedule, { passive : true } );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
