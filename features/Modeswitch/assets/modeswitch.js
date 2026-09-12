/**
 *	Nino - Modeswitch
 *	modeswitch.js	Three states, one control: read the site light, read it dark,
 *							or read it the way the system asks. The first two write
 *							data-nino-mode on the root element, which is what every Nino
 *							project's assets/theme.css already answers to; the third
 *							removes the attribute, because the stylesheet's rule for a
 *							reader who chose nothing is a prefers-color-scheme query that
 *							only applies while nothing is written.
 *
 *							That is also why "system" keeps working when the reader
 *							changes their system setting with the page open: there is no
 *							stored state to go stale, only an absence the browser
 *							re-answers on its own.
 *
 *							The choice lives in localStorage and nowhere else - no
 *							cookie, nothing sent to the server, nothing to declare. A
 *							browser that refuses storage (private window, storage off)
 *							throws on both read and write; every access here is wrapped,
 *							and the switch then works for the length of the visit rather
 *							than not at all.
 *
 *							No dependencies, not even on Nino.js: bundled into the
 *							project's own /.cache/script.js the same way the kernel
 *							bundles Nino.js/Nino.ui.js (see Modeswitch::init()), so a
 *							project that dropped the kernel's scripts still gets a
 *							working switch.
 *
 *	@package		Dape/Nino
 *	@author			David Perchermeier <mail@dape.io>
 *	@link				https://github.com/dapeio/nino
 */

( function( wn, dc ) {

	'use strict';

	// What the reader's choice is kept under, and the three it can be. A
	// value that is none of them is treated as no choice at all - a stored
	// string is something another script, another version or a person with a
	// console can have put there
	const KEY 	= 'nino-mode';
	const MODES = [ 'light', 'system', 'dark' ];
	const ATTR 	= 'data-nino-mode';

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
	 *	Put a choice on the page. 'system' removes the attribute rather than
	 *	writing a third value - see this file's docblock
	 *
	 *	@param		{string}	mode
	 *
	 *	@return		void
	 */
	const apply = function( mode ) {
		if( mode === 'system' )
			dc.documentElement.removeAttribute( ATTR );
		else
			dc.documentElement.setAttribute( ATTR, mode );
	};

	/**
	 *	Mark the button that is on, on every switch the page carries - a site
	 *	with one in the header and one in the footer has two, and they are the
	 *	same switch
	 *
	 *	@param		{string}	mode
	 *
	 *	@return		void
	 */
	const paint = function( mode ) {

		// ...and unhidden here rather than in a step of its own: the switch is
		// rendered hidden so a reader without JavaScript is not left with three
		// buttons that do nothing, and the moment it can be painted is the
		// moment it is worth showing
		const switches = dc.querySelectorAll( '.nino-modeswitch' );
		for( let i = 0; i < switches.length; i++ )
			switches[i].hidden = false;

		const buttons = dc.querySelectorAll( '.nino-modeswitch-btn' );
		for( let i = 0; i < buttons.length; i++ ) {
			const on = buttons[i].getAttribute('data-mode') === mode;
			buttons[i].classList.toggle( 'nino-is-active', on );
			buttons[i].setAttribute( 'aria-pressed', on === true ? 'true' : 'false' );
		}
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

	/*	Applied here, at parse time, rather than on ready: the bundle this
		sits in is the last thing in <body>, and every statement earlier than
		DOMContentLoaded is one repaint less for a reader whose choice differs
		from their system setting. A project that wants none at all moves
		[assets /.cache/script.js] into its html-header.tpl - see the README	*/
	apply( stored() );

	/*	One listener on the document rather than one per button, so a switch
		that arrives later - a lightbox, a fragment swapped in - needs nothing
		to wire it up. closest() so a click on the icon inside a button is a
		click on the button	*/
	const onClick = function( event ) {

		const target = event.target;

		// A click can land on a text node, and an event can be dispatched with
		// no target at all - neither has closest(), and neither is a button
		if( target === null || typeof target === 'undefined' || typeof target.closest !== 'function' )
			return;

		const button = target.closest('.nino-modeswitch-btn');

		if( button === null )
			return;

		choose( button.getAttribute('data-mode') );
	};

	dc.addEventListener( 'click', onClick );

	// ...and the buttons that are on the page now. They are rendered pressed
	// on nothing, because only the browser knows which one is
	if( dc.readyState === 'loading' )
		dc.addEventListener( 'DOMContentLoaded', function() { paint( stored() ) } );
	else
		paint( stored() );

} )( window, document );
