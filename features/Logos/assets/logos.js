/**
 *	Nino - Logos
 *	logos.js		Draws the pause button of a moving band, and nothing else. No
 *					build step; it needs Nino.js, which every site loads: the kernel
 *					bundles it into the project's own /.cache/script.js behind it,
 *					from the manifest's assets.
 *
 *					Anything that moves for more than five seconds needs a way to
 *					pause it that does not depend on a pointer or a keyboard focus
 *					(WCAG 2.2.2): the hover and the focus of the look Band are a
 *					courtesy, the button is the control. The markup always carries
 *					the button, hidden, because the look is chosen after the page
 *					is written - so this file asks the browser whether the track
 *					of a row runs an animation, which only the look Band gives it
 *					and which reduced motion takes away, and shows the button only
 *					then. A press toggles the class nino-logos-strip--paused, which
 *					the stylesheet of the band turns into a paused animation.
 *
 *					The word of the button is a text fill the install unit brings,
 *					written into the data attribute data-logos-pause. A row whose word is still the unresolved fill
 *					gets no button: one that says "[[/feature/logos/action/pause]]"
 *					is worse than none. The word never changes: the state of the button
 *					is aria-pressed, as the pause button of the Ticker has it, and a
 *					word that changed with it would say the same thing twice. The
 *					button is in the markup, and textContent is the only write of a
 *					word.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-logos-strip';
	var PAUSED = 'nino-logos-strip--paused';

	/**
	 *	A word of a button, or '' where it has none: no attribute, an empty one, or
	 *	one that still holds a text fill nobody resolved
	 *
	 *	@param		{Element}		strip			The row
	 *	@param		{string}		name			The data attribute that holds it
	 *
	 *	@return		{string}
	 */
	function word( strip, name ) {

		var text = ( strip.getAttribute( name ) || '' ).trim();

		return text.indexOf( '[[' ) === -1 ? text : '';
	}

	/**
	 *	Show the button of one row if its track runs, and give it its press
	 *
	 *	@param		{Element}		strip			The row
	 *
	 *	@return		void
	 */
	function init( strip ) {

		var button = strip.querySelector( '.nino-logos-strip-pause' );
		var track = strip.querySelector( '.nino-logos-strip-list' );
		var pause = word( strip, 'data-logos-pause' );

		if( button === null || track === null || pause === '' )
			return;

		if( window.getComputedStyle( track ).animationName === 'none' )
			return;

		button.textContent = pause;
		button.setAttribute( 'aria-pressed', 'false' );
		button.hidden = false;

		button.addEventListener( 'click', function() {

			var on = button.getAttribute( 'aria-pressed' ) !== 'true';

			button.setAttribute( 'aria-pressed', on === true ? 'true' : 'false' );

			if( on === true )
				strip.classList.add( PAUSED );
			else
				strip.classList.remove( PAUSED );
		} );
	}

	Nino.events.bindCallback( 'ready', function() {

		var found = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < found.length; i++ )
			init( found[i] );
	} );

} )();
