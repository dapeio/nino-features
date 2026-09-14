/**
 *	Nino - Ticker
 *	ticker.js		Makes a row run and start again without a seam. No dependencies,
 *					no build step - bundled into the project's own /.cache/script.js
 *					the same way the kernel bundles Nino.js/Nino.ui.js (see
 *					Ticker::init()).
 *
 *					The seam is the whole problem. A row that simply scrolls runs
 *					out and jumps back, and the jump is what everybody sees. So the
 *					row's own children are copied until they are wider than the box
 *					twice over, and the whole of it is moved by exactly one
 *					original width - at which point the copy is standing where the
 *					original stood and the animation can start again with nothing
 *					moving.
 *
 *					The copies are aria-hidden: to a screen reader the row is read
 *					once, which is how many times it is there.
 *
 *					The movement is a CSS animation rather than a timer. A browser
 *					runs it off the main thread, and a tab in the background stops
 *					paying for it - neither of which is true of a script that moves
 *					something every frame.
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

	/**
	 *	One row's own speed, in pixels per second
	 *
	 *	@param		{Element}		row
	 *
	 *	@return		{number}
	 */
	function speed( row ) {

		var given = parseFloat( row.getAttribute( 'data-ticker-speed' ) );

		return ( isNaN( given ) === true || given <= 0 ) ? SPEED_DEFAULT : given;
	}

	/**
	 *	Copy the run of children until it is wider than the box twice over, and
	 *	set the animation going over exactly one original width
	 *
	 *	@param		{Element}		row
	 *
	 *	@return		void
	 */
	function run( row ) {

		var track = row.querySelector( '.nino-ticker-track' );

		if( track === null || track.children.length === 0 )
			return;

		var originals = [];

		for( var i = 0; i < track.children.length; i++ )
			originals.push( track.children[i] );

		var one = track.scrollWidth;

		if( one <= 0 )
			return;

		/*	Twice the box, not twice the row: what has to be covered is the
			distance the track travels plus the box it travels across, or the end
			of the copies comes into view before the reset	*/
		var needed = row.clientWidth + one;
		var width = one;
		var copies = 0;

		while( width < needed && copies < COPIES_MAX ) {

			for( var c = 0; c < originals.length; c++ ) {
				var copy = originals[c].cloneNode( true );
				copy.setAttribute( 'aria-hidden', 'true' );
				track.appendChild( copy );
			}

			width += one;
			copies++;
		}

		// The distance is one original width, so the copy standing where the
		// original stood is what the animation ends on - and starting again
		// from zero moves nothing
		track.style.setProperty( '--nino-ticker-distance', one + 'px' );
		track.style.setProperty( '--nino-ticker-duration', ( one / speed( row ) ) + 's' );

		row.classList.add( 'nino-is-running' );
	}

	/**
	 *	Every row on the page
	 *
	 *	@return		void
	 */
	function init() {

		var rows = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < rows.length; i++ )
			run( rows[i] );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
