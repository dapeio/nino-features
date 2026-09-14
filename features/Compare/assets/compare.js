/**
 *	Nino - Before/After
 *	compare.js		Turns the two captioned pictures the shortcode wrote into the
 *					two stacked under one divider, and keeps the divider where the
 *					range control says it is. No dependencies, no build step -
 *					bundled into the project's own /.cache/script.js the same way
 *					the kernel bundles Nino.js/Nino.ui.js (see Compare::init()).
 *
 *					What this file does not do is drag anything. The divider is an
 *					<input type="range">: the browser already drags it with a
 *					mouse, with a finger and with the arrow keys, announces it, and
 *					gives it a value. All that is left is to read that value into
 *					a custom property - the clipping is compare.css's, so a drag
 *					is a paint rather than a call into JavaScript on every frame.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-compare';

	/**
	 *	Write one pair's divider position onto the element the stylesheet
	 *	reads it from
	 *
	 *	@param		{Element}		pair
	 *	@param		{string}		value		0 to 100
	 *
	 *	@return		void
	 */
	function position( pair, value ) {

		var number = parseInt( value, 10 );

		if( isNaN( number ) === true )
			return;

		number = Math.max( 0, Math.min( 100, number ) );

		pair.style.setProperty( '--nino-compare-position', number + '%' );
	}

	/**
	 *	Take one pair over: show its control, follow it, and say so with the
	 *	class the stylesheet switches layout on
	 *
	 *	@param		{Element}		pair
	 *
	 *	@return		void
	 */
	function ready( pair ) {

		var range = pair.querySelector( '.nino-compare-range' );

		if( range === null )
			return;

		range.hidden = false;
		position( pair, range.value );

		range.addEventListener( 'input', function() { position( pair, range.value ) } );

		/*	The second picture is aria-hidden and its caption is inside the
			clipped box: to a screen reader the pair is one picture with a
			description, which is what it is. The control names itself	*/
		pair.classList.add( 'nino-is-ready' );
	}

	/**
	 *	Every pair on the page
	 *
	 *	@return		void
	 */
	function init() {

		var pairs = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < pairs.length; i++ )
			ready( pairs[i] );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
