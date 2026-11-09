/**
 *	Nino - Menu
 *	menu.js		The panels of [menu /<key>]. The stylesheet decides the shape of a
 *						panel and this reads it from the style: a panel that is positioned
 *						(from 768px, in a bar) is a dropdown, one that is not is an accordion.
 *						A dropdown opens on the hover of
 *						its entry after a short delay and closes a moment after the pointer
 *						has left the entry and the panel, a press of the button beside the
 *						entry turns it, a link in it that has the focus holds it open, Escape
 *						closes it and hands the focus to its button, and a press or a focus
 *						outside closes it; one panel is open at a time on the page. Below
 *						768px the button turns the panel in the flow, under its entry, as an
 *						accordion inside the burger overlay of the kernel (Nino.ui.js owns
 *						the overlay). aria-expanded of the button says which is open.
 *
 *						Without this file the stylesheet shows a panel on :hover and
 *						:focus-within from 768px and draws every panel open below it; the
 *						class nino-menu-js, which this file puts on a list, takes that over.
 *						It needs Nino.js, which every site loads (Nino.events.bindCallback()
 *						starts it), and no build step: the manifest's assets bundle it into
 *						the project's own /.cache/script.js, behind the kernel's scripts.
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

	// The entry whose panel is open, or null
	let current = null;

	// What waits for its time: { item, timer } - the entry that is about to open and the one that is about to close
	let opening = null;
	let closing = null;

	// The shape the open panel had when it opened - true for a dropdown - for the resize that changes it
	let shape = true;

	/**
	 *	Whether the panel of an entry is a dropdown: the stylesheet decides the shape - a panel that is positioned hangs from the bar,
	 *	one that is not stands in the flow under its entry as an accordion, in the burger overlay of a phone or of a header that
	 *	keeps its burger, and in a header that is a rail - and this reads what it decided
	 *
	 *	@param		{Element}		item
	 *
	 *	@return		{boolean}
	 */
	function dropdown( item ) {

		const panel = item.querySelector( '.nino-menu-panel' );

		return panel !== null && window.getComputedStyle( panel ).position === 'absolute';
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
	 *	Whether a node is a link, or is inside one that is inside the panel
	 *
	 *	@param		{?Node}			node
	 *	@param		{Element}		panel
	 *
	 *	@return		{boolean}
	 */
	function linked( node, panel ) {

		for( let walk = node; walk !== null && walk !== undefined && walk !== panel; walk = walk.parentNode )
			if( walk.tagName === 'A' )
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
	 *	Open or close the panel of an entry: the button says which in aria-expanded, and the
	 *	entry that was open is closed when another opens
	 *
	 *	@param		{Element}		item
	 *	@param		{boolean}		open
	 *
	 *	@return		void
	 */
	function set( item, open ) {

		const toggle = item.querySelector( '.nino-menu-toggle' );

		if( toggle === null )
			return;

		if( open === true && current !== null && current !== item )
			set( current, false );

		toggle.setAttribute( 'aria-expanded', open === true ? 'true' : 'false' );

		if( open === true ) {
			current = item;
			shape = dropdown( item );
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

		closing = { item : item, timer : setTimeout( function() {
			closing = null;
			set( item, false );
		}, CLOSE_DELAY ) };
	}

	/**
	 *	The button beside an entry is pressed: it turns the panel, at once - below 768px as an accordion
	 *
	 *	@param		{Element}		item
	 *	@param		{Event}			event
	 *
	 *	@return		void
	 */
	function press( item, event ) {

		// The overlay of the burger closes on a click anywhere in it that is not its own button (Nino.ui.js)
		event.stopPropagation();
		stop();
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

		const toggle = item.querySelector( '.nino-menu-toggle' );
		const panel = item.querySelector( '.nino-menu-panel' );

		if( toggle === null || panel === null )
			return;

		item.addEventListener( 'pointerenter', function( event ) { enter( item, event ) } );
		item.addEventListener( 'pointerleave', function( event ) { leave( item, event ) } );
		toggle.addEventListener( 'click', function( event ) { press( item, event ) } );

		// A link of the panel that has the focus holds the panel open; the entry's own link and button do not open it, or the Tab key could not pass a panel
		item.addEventListener( 'focusin', function( event ) {

			if( dropdown( item ) === true && inside( panel, event.target ) === true ) {
				stop();
				set( item, true );
			}
		} );

		// The overlay closes on a click that is not on its button; a click on the text of a panel is not meant to
		panel.addEventListener( 'click', function( event ) {

			if( linked( event.target, panel ) === false )
				event.stopPropagation();
		} );
	}

	/**
	 *	Take the menus over
	 *
	 *	@return		void
	 */
	function init() {

		const lists = document.querySelectorAll( '.nino-menu' );

		for( let i = 0; i < lists.length; i++ ) {

			lists[i].classList.add( 'nino-menu-js' );

			const items = lists[i].querySelectorAll( '.nino-menu-item--panel' );

			for( let n = 0; n < items.length; n++ )
				bind( items[n] );
		}

		if( lists.length === 0 )
			return;

		// A press or the focus outside the open entry closes it (capturing: a click on the overlay's text does not reach the document)
		document.addEventListener( 'pointerdown', function( event ) {

			if( current !== null && dropdown( current ) === true && inside( current, event.target ) === false )
				set( current, false );
		}, true );

		document.addEventListener( 'focusin', function( event ) {

			if( current !== null && dropdown( current ) === true && inside( current, event.target ) === false )
				set( current, false );
		} );

		// Escape closes the panel and hands the focus to its button. An accordion leaves it to the overlay
		document.addEventListener( 'keydown', function( event ) {

			if( event.key !== 'Escape' || current === null || dropdown( current ) === false )
				return;

			const toggle = current.querySelector( '.nino-menu-toggle' );

			stop();
			set( current, false );
			toggle.focus();
		} );

		// What was open in the one shape is closed in the other
		window.addEventListener( 'resize', function() {

			if( current !== null && dropdown( current ) !== shape ) {
				stop();
				set( current, false );
			}
		} );
	}

	Nino.events.bindCallback( 'ready', init );

} )();
