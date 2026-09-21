/**
 *	Nino - Countdown
 *	countdown.js	Counts the time left until the moment the markup carries, and
 *					puts the date back when it has passed. No dependencies, no
 *					build step - bundled into the project's own /.cache/script.js
 *					the same way the kernel bundles Nino.js/Nino.ui.js (see
 *					Countdown::init()).
 *
 *					The arithmetic is here rather than on the server because a
 *					page cached for an hour would otherwise be an hour wrong. The
 *					moment itself is the server's: an ISO-8601 string with the
 *					site's own offset in it, so a reader in another timezone
 *					counts down to the same instant rather than the same wall
 *					clock.
 *
 *					Both forms of every unit name are handed to this file on the
 *					part they belong to - data-countdown-one and -many, resolved
 *					by the fill engine before the page was sent. A static asset
 *					cannot read a text fill, so what it does is choose between
 *					two words rather than know any.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-countdown[data-countdown-to]';

	// Seconds in each part, largest first - the order they are drawn in, and
	// the order they have to be taken off the remainder in
	var SECONDS = { days : 86400, hours : 3600, minutes : 60, seconds : 1 };

	var timer = null;

	/**
	 *	The moment one countdown counts to, in milliseconds, or null where the
	 *	markup carries something that is not one
	 *
	 *	@param		{Element}		countdown
	 *
	 *	@return		{?number}
	 */
	function target( countdown ) {

		var raw = countdown.getAttribute( 'data-countdown-to' );

		if( typeof raw !== 'string' || raw === '' )
			return null;

		var at = Date.parse( raw );

		return isNaN( at ) === true ? null : at;
	}

	/**
	 *	Write one part's number and the form of its name that goes with it
	 *
	 *	@param		{Element}		part
	 *	@param		{number}		value
	 *
	 *	@return		void
	 */
	function paint( part, value ) {

		var number = part.querySelector( '.nino-countdown-value' );
		var name	 = part.querySelector( '.nino-countdown-name' );

		if( number !== null )
			number.textContent = String( value );

		if( name === null )
			return;

		var word = value === 1
			? part.getAttribute( 'data-countdown-one' )
			: part.getAttribute( 'data-countdown-many' );

		if( typeof word === 'string' && word !== '' )
			name.textContent = word;
	}

	/**
	 *	One countdown, one tick. The remainder is taken apart largest part
	 *	first, and only the parts this one was written with are taken off it -
	 *	so a countdown in days and hours says "3 days 4 hours", not "3 days 4
	 *	hours" with the minutes silently missing from the total
	 *
	 *	@param		{Element}		countdown
	 *	@param		{number}		now
	 *
	 *	@return		{boolean}					Whether it is still counting
	 */
	function tick( countdown, now ) {

		var at = target( countdown );

		if( at === null )
			return false;

		var left = Math.floor( ( at - now ) / 1000 );

		if( left <= 0 ) {
			finish( countdown );
			return false;
		}

		var parts = countdown.querySelectorAll( '.nino-countdown-part' );

		for( var i = 0; i < parts.length; i++ ) {

			var unit = parts[i].getAttribute( 'data-countdown-unit' );
			var size = SECONDS[unit];

			if( size === undefined )
				continue;

			/*	Each part takes its whole share of what is left, and the parts
				after it get the remainder - so a countdown written in days alone
				says "4 days" for four and a half, rather than four days and an
				hours part that is not on the screen	*/
			var value = Math.floor( left / size );

			paint( parts[i], value );
			left -= value * size;
		}

		return true;
	}

	/**
	 *	The moment has passed: the counter goes, and what the shortcode was
	 *	given for afterwards stands in its place. Where it was given nothing,
	 *	the date the markup already carried is put back - a countdown that has
	 *	run out is still a date
	 *
	 *	@param		{Element}		countdown
	 *
	 *	@return		void
	 */
	function finish( countdown ) {

		// The guard is the class, not data-countdown-done: that attribute is
		// where the sentence for afterwards is kept, and a flag written into
		// it would destroy the sentence one line before it is read
		if( countdown.classList.contains( 'nino-is-done' ) === true )
			return;

		countdown.classList.add( 'nino-is-done' );

		var parts = countdown.querySelector( '.nino-countdown-parts' );
		var date	= countdown.querySelector( '.nino-countdown-date' );
		var said	= countdown.getAttribute( 'data-countdown-done' );

		if( parts !== null )
			parts.hidden = true;

		if( typeof said === 'string' && said !== '' && date !== null ) {
			/*	The sentence is not a date, and datetime="..." is the
				machine-readable half of whatever the element says - so leaving it
				there tells a parser, a calendar or a screen reader that "Es ist so
				weit" IS that instant. The element keeps its class and its place
				and stops claiming a moment it no longer names	*/
			date.removeAttribute( 'datetime' );
			date.textContent = said;
			date.hidden = false;
		}
		else if( date !== null )
			date.hidden = false;
	}

	/**
	 *	Every countdown on the page, once a second. One timer for all of them
	 *	rather than one each: they all read the same clock
	 *
	 *	@return		{number}					How many are still counting
	 */
	function run() {

		var countdowns = document.querySelectorAll( SELECTOR );
		var now = Date.now();
		var counting = 0;

		for( var i = 0; i < countdowns.length; i++ )
			if( tick( countdowns[i], now ) === true )
				counting++;

		if( counting === 0 && timer !== null ) {
			clearInterval( timer );
			timer = null;
		}

		return counting;
	}

	/**
	 *	Take the counters over: the date steps aside, the parts come out of
	 *	hiding. They are written hidden the way [mode-switch]'s switch is -
	 *	where this file never runs, a row of em dashes is worse than the date
	 *
	 *	@return		void
	 */
	function init() {

		var countdowns = document.querySelectorAll( SELECTOR );
		var started = 0;

		for( var i = 0; i < countdowns.length; i++ ) {

			if( target( countdowns[i] ) === null )
				continue;

			var parts = countdowns[i].querySelector( '.nino-countdown-parts' );
			var date	= countdowns[i].querySelector( '.nino-countdown-date' );

			if( parts === null )
				continue;

			parts.hidden = false;

			if( date !== null )
				date.hidden = true;

			started++;
		}

		if( started === 0 )
			return;

		// The first tick before the clock, not after: a page opened on a
		// countdown that has already run out is done, and starting a timer for
		// it would be a second of ticking with nothing to count
		if( run() > 0 && timer === null )
			timer = setInterval( run, 1000 );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
