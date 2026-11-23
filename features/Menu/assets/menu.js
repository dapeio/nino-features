/**
 *	Nino - Menu
 *	menu.js		The panels of the component menu, as the pattern "disclosure navigation" of the
 *						WAI-ARIA authoring practices has them: an entry with a panel is its link and a button
 *						beside it, and the button says in aria-expanded whether the panel is open. A press or
 *						Enter or Space on the button turns the panel; Escape closes it and hands the focus
 *						to the button (an accordion in the overlay of the header takes the key: the next Escape
 *						is the header's, which closes the menu); the focus leaving the entry (Tab) or a press outside closes it; a
 *						second panel closes the first, so one panel is open at a time. A mouse that rests on
 *						an entry opens the panel after a short delay and leaves it a moment after the pointer
 *						has gone - the hover adds to the button and is never the only way.
 *
 *						The stylesheet decides the shape and this reads it from the style: a panel that is
 *						positioned is a dropdown that hangs from its entry from 768px, one that is not is an
 *						accordion under it. A dropdown is moved sideways, with --menu-shift, where it would
 *						cross an edge of the window.
 *
 *						The script writes no markup and reads none from data: it sets aria-expanded and one
 *						custom property. Without it the stylesheet leaves every panel open and the buttons
 *						hidden. "Less motion" is the stylesheet's: nothing here moves anything.
 *
 *						It needs Nino.js, which every site loads (Nino.events.bindCallback() starts it).
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	// How long the pointer rests on an entry before its panel opens, and how long it may be away from the entry and the panel before the panel closes
	const OPEN_DELAY = 120;
	const CLOSE_DELAY = 300;

	// The room a dropdown keeps to the edge of the window, in px
	const EDGE = 8;

	// The entry whose panel is open, or null
	let current = null;

	// What waits for its time: { item, timer } - the entry that is about to open and the one that is about to close
	let opening = null;
	let closing = null;

	// The shape the open panel had when it opened - true for a dropdown - for the resize that changes it
	let shape = true;

	// The entry whose panel the hover opened and no click has held yet: the first click holds it open, the next one closes it
	let hovered = null;

	/**
	 *	Whether the panel of an entry is a dropdown: the stylesheet decides, and this reads what it decided
	 *
	 *	@param		{Element}		item
	 *
	 *	@return		{boolean}
	 */
	function dropdown( item ) {

		const panel = item.querySelector( '.menu-panel' );

		return panel !== null && window.getComputedStyle( panel ).position === 'absolute';
	}

	/**
	 *	Whether something stands in the top layer that has the Escape before a panel: a modal dialog, a popover that closes with it - a manual one is a toast and does
	 *	not. The script of the header of the kernel asks the same and leaves that Escape to it as well, so that each key has one taker
	 *
	 *	@return		{boolean}
	 */
	function layer() {

		try {
			return document.querySelector( ':modal, :popover-open:not([popover=manual])' ) !== null;
		}
		catch( error ) {

			try {
				return document.querySelector( ':modal' ) !== null;
			}
			catch( other ) {
				return false;
			}
		}
	}

	/**
	 *	Whether a node is the entry or inside it
	 *
	 *	@param		{Element}		item
	 *	@param		{?Node}			node
	 *
	 *	@return		{boolean}
	 */
	function inside( item, node ) {

		for( let walk = node; walk !== null && walk !== undefined; walk = walk.parentNode )
			if( walk === item )
				return true;

		return false;
	}

	/**
	 *	Forget what waits: an entry that was about to open and one that was about to close
	 *
	 *	@param		{?Element}	[item]			Only what waits for this entry; everything without one
	 *
	 *	@return		void
	 */
	function stop( item ) {

		if( opening !== null && ( item === undefined || opening.item === item ) ) {
			clearTimeout( opening.timer );
			opening = null;
		}

		if( closing !== null && ( item === undefined || closing.item === item ) ) {
			clearTimeout( closing.timer );
			closing = null;
		}
	}

	/**
	 *	Keep a dropdown inside the window: the panel hangs from its entry, and where it would cross the
	 *	left or the right edge it is moved by the difference
	 *
	 *	@param		{Element}		item
	 *
	 *	@return		void
	 */
	function place( item ) {

		const panel = item.querySelector( '.menu-panel' );

		if( panel === null )
			return;

		panel.style.removeProperty( '--menu-shift' );

		if( dropdown( item ) === false )
			return;

		const box = panel.getBoundingClientRect();
		const width = document.documentElement.clientWidth;
		let shift = 0;

		if( box.right > width - EDGE )
			shift = width - EDGE - box.right;

		if( box.left + shift < EDGE )
			shift = EDGE - box.left;

		if( shift !== 0 )
			panel.style.setProperty( '--menu-shift', shift+ 'px' );
	}

	/**
	 *	Open or close the panel of an entry: the button says which in aria-expanded, and the
	 *	entry that was open is closed when another opens
	 *
	 *	@param		{Element}		item
	 *	@param		{boolean}		open
	 *
	 *	@return		void
	 */
	function set( item, open ) {

		const toggle = item.querySelector( '.menu-toggle' );

		if( toggle === null )
			return;

		if( open === true && current !== null && current !== item )
			set( current, false );

		toggle.setAttribute( 'aria-expanded', open === true ? 'true' : 'false' );

		if( open === false && hovered === item )
			hovered = null;

		if( open === true ) {
			current = item;
			shape = dropdown( item );
			place( item );
		}
		else if( current === item )
			current = null;
	}

	/**
	 *	The pointer has come to an entry: its panel opens after the delay
	 *
	 *	@param		{Element}		item
	 *	@param		{Event}			event
	 *
	 *	@return		void
	 */
	function enter( item, event ) {

		if( event.pointerType !== 'mouse' || dropdown( item ) === false )
			return;

		stop( item );

		if( current === item )
			return;

		if( opening !== null )
			stop( opening.item );

		opening = { item : item, timer : setTimeout( function() {
			opening = null;
			set( item, true );
			hovered = item;
		}, OPEN_DELAY ) };
	}

	/**
	 *	The pointer has left an entry and its panel: the panel closes after the delay, and one
	 *	that has not opened yet does not
	 *
	 *	@param		{Element}		item
	 *	@param		{Event}			event
	 *
	 *	@return		void
	 */
	function leave( item, event ) {

		if( event.pointerType !== 'mouse' || dropdown( item ) === false )
			return;

		stop( item );

		// An entry that has the focus - it was pressed or tabbed to - stays open until the focus leaves it
		if( current === item && inside( item, document.activeElement ) === true )
			return;

		closing = { item : item, timer : setTimeout( function() {
			closing = null;
			set( item, false );
		}, CLOSE_DELAY ) };
	}

	/**
	 *	The button beside an entry is pressed - by a click, Enter or Space: it turns the panel at once
	 *
	 *	@param		{Element}		item
	 *
	 *	@return		void
	 */
	function press( item ) {

		stop();

		// A press on the button of a panel the mouse has opened by resting on it holds the panel open: the person aimed at the button and meant to open it
		if( current === item && hovered === item ) {
			hovered = null;
			return;
		}

		set( item, current !== item );
	}

	/**
	 *	Take one entry with a panel over
	 *
	 *	@param		{Element}		item
	 *
	 *	@return		void
	 */
	function bind( item ) {

		const toggle = item.querySelector( '.menu-toggle' );

		if( toggle === null || item.querySelector( '.menu-panel' ) === null )
			return;

		item.addEventListener( 'pointerenter', function( event ) { enter( item, event ) } );
		item.addEventListener( 'pointerleave', function( event ) { leave( item, event ) } );
		toggle.addEventListener( 'click', function() { press( item ) } );
	}

	/**
	 *	Take the menus over
	 *
	 *	@return		void
	 */
	function init() {

		const menus = document.querySelectorAll( '.menu' );
		let bound = 0;

		for( let i = 0; i < menus.length; i++ ) {

			// The panels fold only now: a script that did not get here leaves every link reachable
			menus[i].classList.add( 'menu--ready' );

			const items = menus[i].querySelectorAll( '.menu-item--panel' );

			for( let n = 0; n < items.length; n++ ) {
				bind( items[n] );
				bound++;
			}
		}

		if( bound === 0 )
			return;

		// A press outside the open dropdown closes it - an accordion stays, or the list would move under the pointer before the click lands (capturing: a press on something that stops the event is heard as well)
		document.addEventListener( 'pointerdown', function( event ) {

			if( current !== null && dropdown( current ) === true && inside( current, event.target ) === false )
				set( current, false );
		}, true );

		// The focus that leaves the entry - the Tab key walks on - closes it
		document.addEventListener( 'focusin', function( event ) {

			if( current !== null && dropdown( current ) === true && inside( current, event.target ) === false )
				set( current, false );
		} );

		// Escape closes the panel - unless a dialog or a popover is on top of it - and, where the focus was in it, hands the focus to its button. It is heard before the script of the header hears it (capturing), and an accordion that was
		// shown takes the key - preventDefault -, which that script leaves alone: the panel is the first to go, the overlay the next to. A panel nobody sees (the menu is shut) or reaches (it lies behind an overlay, inert) takes nothing
		document.addEventListener( 'keydown', function( event ) {

			if( event.key !== 'Escape' || current === null || layer() === true )
				return;

			const item = current;
			const held = inside( item, document.activeElement );
			const taken = dropdown( item ) === false && item.closest( '[inert]' ) === null && item.querySelector( '.menu-panel' ).getClientRects().length > 0;

			stop();
			set( item, false );

			if( held === true )
				item.querySelector( '.menu-toggle' ).focus();

			if( taken === true )
				event.preventDefault();
		}, true );

		// What was open in the one shape is closed in the other, and a dropdown that stays is kept inside the window
		window.addEventListener( 'resize', function() {

			if( current === null )
				return;

			if( dropdown( current ) !== shape ) {
				stop();
				set( current, false );
				return;
			}

			place( current );
		} );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
