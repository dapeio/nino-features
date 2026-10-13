/**
 *	Nino - Reading Progress
 *	progress.js		Keeps a bar in step with how far through a text the reader is.
 *					No build step; it needs Nino.js, which every site loads: the
 *					kernel bundles it into the project's own /.cache/script.js
 *					behind it, from the manifest's assets.
 *
 *					What is measured is a choice: the whole page counts the footer
 *					as part of the article, so "finished" arrives after the last
 *					paragraph rather than at it. data-progress-of="#article" names
 *					the one element that is the text, and then the bar is full when
 *					the text is.
 *
 *					The width is a custom property the stylesheet uses, so what
 *					happens on every frame of a scroll is a paint rather than a
 *					layout read followed by a style write. Scrolling costs one
 *					animation frame at most, not one call per event: Nino.js
 *					throttles the scroll and the resize that paint() hangs off.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-progress';

	var bars = [];

	/**
	 *	How far through one bar's subject the page has been scrolled, 0 to 1
	 *
	 *	@param		{Element}		bar
	 *
	 *	@return		{number}
	 */
	function share( bar ) {

		var of = bar.getAttribute( 'data-progress-of' ) || '';
		var subject = null;

		if( of !== '' ) {
			try { subject = document.querySelector( of ) }
			catch( e ) { subject = null }
		}

		var seen = window.innerHeight || 0;

		if( subject !== null ) {

			var box = subject.getBoundingClientRect();

			/*	The text is finished when its last line has been read, not when its
				bottom edge reaches the top of the window - so what is travelled is
				the element's height less one screenful, and an element shorter than
				the screen is finished the moment it is on it	*/
			var travel = box.height - seen;

			if( travel <= 0 )
				return box.top <= 0 ? 1 : 0;

			return Math.max( 0, Math.min( 1, -box.top / travel ) );
		}

		var doc = document.documentElement;
		var total = ( doc.scrollHeight || 0 ) - seen;

		if( total <= 0 )
			return 0;

		return Math.max( 0, Math.min( 1, ( window.pageYOffset || doc.scrollTop || 0 ) / total ) );
	}

	/**
	 *	Every bar, once
	 *
	 *	@return		void
	 */
	function paint() {

		for( var i = 0; i < bars.length; i++ ) {

			var value = share( bars[i] );

			bars[i].style.setProperty( '--nino-progress', ( value * 100 ) + '%' );

			// Only where the bar has a name. A progressbar with no name is
			// announced as a number nobody asked for, which is worse than a
			// decoration a screen reader never mentions
			if( bars[i].getAttribute( 'role' ) === 'progressbar' )
				bars[i].setAttribute( 'aria-valuenow', String( Math.round( value * 100 ) ) );
		}
	}

	/**
	 *	Every bar on the page
	 *
	 *	@return		void
	 */
	function init() {

		var found = document.querySelectorAll( SELECTOR );
		var body = document.body;

		for( var i = 0; i < found.length; i++ ) {

			/*	Into the body, and before anything is measured. A fixed element is
				pinned to the window only while none of its ancestors has a transform,
				a filter or a perspective - one that has becomes the box it is fixed
				to, and the bar scrolls away with that box instead of staying at the
				top. An animated section keeps its translateY(0) after it has been
				seen, the row of a cover is centred with translateY(-50%), and the
				Builder places the bar in a section: so it is moved out of there, to
				where nothing stands between it and the window	*/
			if( body && found[i].parentNode !== body )
				body.appendChild( found[i] );

			var label = found[i].getAttribute( 'data-progress-label' ) || '';

			/*	Named, so it is a control a screen reader can say something useful
				about - or hidden from one entirely. The one thing it must not be is
				an unnamed number that is announced every time it changes	*/
			if( label !== '' ) {
				found[i].setAttribute( 'role', 'progressbar' );
				found[i].setAttribute( 'aria-label', label );
				found[i].setAttribute( 'aria-valuemin', '0' );
				found[i].setAttribute( 'aria-valuemax', '100' );
			}
			else
				found[i].setAttribute( 'aria-hidden', 'true' );

			found[i].classList.add( 'nino-is-ready' );
			bars.push( found[i] );
		}

		if( bars.length === 0 )
			return;

		paint();
		Nino.events.bindCallback( 'scroll', paint );
		Nino.events.bindCallback( 'resize', paint );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
