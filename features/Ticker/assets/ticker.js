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
		track.style.setProperty( '--nino-ticker-duration', ( distance / speed( row ) ) + 's' );

		row.classList.add( 'nino-is-running' );
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

		var rows = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < rows.length; i++ )
			run( rows[i] );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
