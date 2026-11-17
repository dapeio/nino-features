/**
 *	Nino - Modeswitch
 *	modeswitch.js	Three states, one control: read the site light, read it dark,
 *							or read it the way the system asks. The first two set the token
 *							--brand-scheme on the root element, which is what the whole
 *							palette turns on (every colour of the page is a light-dark()
 *							pair, and color-scheme is set from the token); the third
 *							removes what was set, so that the brand's own value stands again.
 *
 *							The control is there only where the brand leaves the mode to
 *							the visitor, that is where the token, as the style sheets give
 *							it, is "light dark". A brand that says light or dark has
 *							decided, and the switch stays hidden - the component is drawn
 *							hidden and this script is what shows it.
 *
 *							The choice lives in localStorage and nowhere else - no
 *							cookie, nothing sent to the server, nothing to declare. A
 *							browser that refuses storage (private window, storage off)
 *							throws on both read and write; every access here is wrapped,
 *							and the switch then works for the length of the visit rather
 *							than not at all.
 *
 *							The choice is put on the page the moment this script runs and,
 *							before the page is painted, by assets/mode-early.js, which the
 *							output phase puts into the head (Modeswitch::callbackOutput())
 *							- the same decision, made earlier. It needs Nino.js, which every
 *							site loads, for one thing: the first painting of the buttons
 *							waits for Nino.events to say the page is ready.
 *
 *	@package		Dape/Nino
 *	@author			David Perchermeier <mail@dape.io>
 *	@link				https://github.com/dapeio/nino
 */

( function( wn, dc ) {

	'use strict';

	// What the reader's choice is kept under, the three it can be and the token it sets. A
	// value that is none of them is treated as no choice at all - a stored
	// string is something another script, another version or a person with a
	// console can have put there
	const KEY 	= 'nino-mode';
	const MODES = [ 'light', 'system', 'dark' ];
	const TOKEN = '--brand-scheme';

	/**
	 *	The stored choice, or 'system' where there is none
	 *
	 *	@return		{string}
	 */
	const stored = function() {
		try {
			const value = wn.localStorage.getItem( KEY );
			return MODES.indexOf( value ) === -1 ? 'system' : value;
		}
		catch( e ) {
			// Storage refused. Not an error to report: the switch still works,
			// it just does not outlive the page
			return 'system';
		}
	};

	/**
	 *	Whether the brand leaves the mode to the visitor: the token as the style sheets give it,
	 *	without what this script set on the root element
	 *
	 *	@return		{boolean}
	 */
	const open = function() {

		const style = dc.documentElement.style;
		const set = style.getPropertyValue( TOKEN );

		style.removeProperty( TOKEN );
		const brand = wn.getComputedStyle( dc.documentElement ).getPropertyValue( TOKEN ).replace( /\s+/g, ' ' ).trim();

		if( set !== '' )
			style.setProperty( TOKEN, set );

		return brand === 'light dark' || brand === 'dark light';
	};

	/**
	 *	Put a choice on the page. 'system' removes what was set rather than
	 *	writing a third value
	 *
	 *	@param		{string}	mode
	 *
	 *	@return		void
	 */
	const apply = function( mode ) {
		if( mode === 'system' )
			dc.documentElement.style.removeProperty( TOKEN );
		else if( open() === true )
			dc.documentElement.style.setProperty( TOKEN, mode );
	};

	/**
	 *	Mark the button that is on, on every switch the page carries - a site
	 *	with one in the header and one in the footer has two, and they are the
	 *	same switch. A switch is shown here and nowhere else, and only where
	 *	the brand leaves the mode open
	 *
	 *	@param		{string}	mode
	 *
	 *	@return		void
	 */
	const paint = function( mode ) {

		const show = open();
		const switches = dc.querySelectorAll( '.mode-switch' );
		for( let i = 0; i < switches.length; i++ )
			switches[i].hidden = show === false;

		const buttons = dc.querySelectorAll( '.mode-switch-button' );
		for( let i = 0; i < buttons.length; i++ )
			buttons[i].setAttribute( 'aria-pressed', buttons[i].getAttribute( 'data-mode' ) === mode ? 'true' : 'false' );
	};

	/**
	 *	Choose one: on the page, in storage, and on every switch
	 *
	 *	@param		{string}	mode
	 *
	 *	@return		void
	 */
	const choose = function( mode ) {

		if( MODES.indexOf( mode ) === -1 )
			return;

		apply( mode );
		paint( mode );

		try { wn.localStorage.setItem( KEY, mode ); } catch( e ) { /* see stored() */ }
	};

	/*	Applied here, at parse time, as well as by the early script of the head: the bundle
		this sits in is the last thing in <body>, and a page whose head the output phase
		could not reach still gets the choice	*/
	apply( stored() );

	/*	One listener on the document rather than one per button, so a switch
		that arrives later - a lightbox, a fragment swapped in - needs nothing
		to wire it up. closest() so a click on the icon inside a button is a
		click on the button	*/
	dc.addEventListener( 'click', function( event ) {

		const target = event.target;

		// A click can land on a text node, and an event can be dispatched with
		// no target at all - neither has closest(), and neither is a button
		if( target === null || typeof target === 'undefined' || typeof target.closest !== 'function' )
			return;

		const button = target.closest( '.mode-switch-button' );

		if( button === null )
			return;

		choose( button.getAttribute( 'data-mode' ) );
	} );

	/*	A switch is rendered hidden and pressed on nothing, and paint() - the only thing that
		ever shows one - has already run by the time a later one arrives, so the document is
		watched for one being added. A browser without MutationObserver keeps the switches the
		page loaded with	*/
	if( typeof wn.MutationObserver === 'function' )
		new wn.MutationObserver( function( records ) {

			for( let r = 0; r < records.length; r++ ) {

				const added = records[r].addedNodes;

				for( let i = 0; i < added.length; i++ ) {

					// A text node has no matches(), and nothing that is not an
					// element is a switch or carries one
					if( added[i].nodeType !== 1 )
						continue;

					if( added[i].matches( '.mode-switch' ) === false && added[i].querySelector( '.mode-switch' ) === null )
						continue;

					// Every switch on the page is the same switch, so one pass
					// over all of them answers however many arrived at once
					paint( stored() );
					return;
				}
			}
		} ).observe( dc.documentElement, { childList : true, subtree : true } );

	// ...and the buttons that are on the page now
	Nino.events.bindCallback( 'ready', function() { paint( stored() ) } );

} )( window, document );
