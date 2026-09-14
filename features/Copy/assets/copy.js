/**
 *	Nino - Copy to Clipboard
 *	copy.js			Puts what a [copy] holds into the clipboard, and says whether it
 *					worked. No dependencies, no build step - bundled into the
 *					project's own /.cache/script.js the same way the kernel bundles
 *					Nino.js/Nino.ui.js (see Copy::init()).
 *
 *					The three words are handed to this file on the button itself -
 *					data-copy-do, -done and -failed, resolved by the fill engine
 *					before the page was sent. A static asset cannot read a text
 *					fill, so what is here chooses between words rather than knowing
 *					any.
 *
 *					Two ways of copying, because one of them is not everywhere: the
 *					clipboard API where the browser has it and the page is allowed
 *					to use it - it is secure-context only, so a site served over
 *					http has none - and a selection plus execCommand where it is
 *					not. Where neither works the button says so and leaves the text
 *					selected, which is the thing the reader was going to do anyway.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-copy';

	// How long the button says it worked before it is a copy button again
	var SAID_FOR = 1600;

	/**
	 *	What one [copy] puts into the clipboard: what it was told to, or what
	 *	it shows - a number grouped so it can be read is copied without the
	 *	grouping, and everything else is copied as it stands
	 *
	 *	@param		{Element}		box
	 *	@param		{Element}		button
	 *
	 *	@return		{string}
	 */
	function value( box, button ) {

		var given = button.getAttribute( 'data-copy-value' );

		if( typeof given === 'string' && given !== '' )
			return given;

		var text = box.querySelector( '.nino-copy-text' );

		return text === null ? '' : text.textContent;
	}

	/**
	 *	Say one of the three words on the button, and go back to the first
	 *	one after a moment
	 *
	 *	@param		{Element}		button
	 *	@param		{string}		which		'done' or 'failed'
	 *
	 *	@return		void
	 */
	function say( button, which ) {

		var word = button.querySelector( '.nino-copy-word' );
		var said = button.getAttribute( 'data-copy-' + which );
		var back = button.getAttribute( 'data-copy-do' );

		if( word === null || typeof said !== 'string' )
			return;

		word.textContent = said;
		button.classList.add( which === 'done' ? 'nino-is-done' : 'nino-is-failed' );

		if( button.dataset.copyTimer !== undefined )
			clearTimeout( parseInt( button.dataset.copyTimer, 10 ) );

		button.dataset.copyTimer = String( setTimeout( function() {
			word.textContent = back;
			button.classList.remove( 'nino-is-done' );
			button.classList.remove( 'nino-is-failed' );
			delete button.dataset.copyTimer;
		}, SAID_FOR ) );
	}

	/**
	 *	The way that works without the clipboard API: put the text into a
	 *	field nobody can see, select it, and let the browser's own copy
	 *	command take it
	 *
	 *	@param		{string}		text
	 *
	 *	@return		{boolean}
	 */
	function legacy( text ) {

		var field = document.createElement( 'textarea' );

		field.value = text;
		field.setAttribute( 'readonly', '' );
		field.setAttribute( 'aria-hidden', 'true' );
		field.style.position = 'fixed';
		field.style.top = '-1000px';
		field.style.opacity = '0';

		document.body.appendChild( field );

		var worked = false;

		try {
			field.select();
			worked = document.execCommand( 'copy' ) === true;
		}
		catch( e ) {
			worked = false;
		}

		document.body.removeChild( field );

		return worked;
	}

	/**
	 *	One press
	 *
	 *	@param		{Element}		box
	 *	@param		{Element}		button
	 *
	 *	@return		void
	 */
	function press( box, button ) {

		var text = value( box, button );

		if( text === '' )
			return;

		/*	The clipboard API is secure-context only, so a site served over http
			does not have one at all - and where it is there it may still be
			refused. Either way the older way is tried before anything is said	*/
		if( navigator.clipboard !== undefined && typeof navigator.clipboard.writeText === 'function' ) {

			navigator.clipboard.writeText( text ).then(
				function() { say( button, 'done' ) },
				function() { say( button, legacy( text ) === true ? 'done' : 'failed' ) }
			);

			return;
		}

		say( button, legacy( text ) === true ? 'done' : 'failed' );
	}

	/**
	 *	Every [copy] on the page. The buttons are written hidden, the way
	 *	[mode-switch]'s switch is: where this file never runs, what is left is
	 *	the text, selectable, which is what it was before
	 *
	 *	@return		void
	 */
	function init() {

		var boxes = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < boxes.length; i++ ) {

			var button = boxes[i].querySelector( '.nino-copy-btn' );

			if( button === null )
				continue;

			/*	What the button copies, for a reader who cannot see what it stands
				beside: "Copy" is what it does, "IBAN" is what it copies, and one
				without the other is half a name	*/
			var named = button.getAttribute( 'data-copy-named' );
			var word	= button.getAttribute( 'data-copy-do' ) || '';

			if( typeof named === 'string' && named !== '' )
				button.setAttribute( 'aria-label', word + ': ' + named );

			button.hidden = false;
			button.addEventListener( 'click', ( function( box, btn ) {
				return function() { press( box, btn ) };
			} )( boxes[i], button ) );
		}
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
