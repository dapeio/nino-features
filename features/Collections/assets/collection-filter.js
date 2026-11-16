/*	collection-filter.js - the one script of the feature Collections. A box with data-collection-filter holds rows that
	are marked with data-collection-value; the script puts a row of buttons above them, one for each value and one for
	all, and hides the rows of the other values. No script, no buttons: every row stays where it is.	*/
( function() {

	'use strict';

	const SELECTOR = '[data-collection-filter]';

	/**
	 *	Make one box a filter
	 *
	 *	@param		{HTMLElement}		box
	 */
	function setup( box ) {

		// The rows of this box, and not the ones of a filter inside it
		const rows = Array.prototype.filter.call( box.querySelectorAll( '[data-collection-value]' ), function( row ) {
			return row.closest( SELECTOR ) === box;
		} );
		const values = [];

		rows.forEach( function( row ) {

			const value = row.getAttribute( 'data-collection-value' );

			if( value !== '' && values.indexOf( value ) === -1 )
				values.push( value );
		} );

		// One value is no choice
		if( values.length < 2 )
			return;

		const nav = document.createElement( 'div' );
		const status = document.createElement( 'p' );
		const buttons = [];

		nav.className = 'collection-filter-nav';
		nav.setAttribute( 'role', 'group' );
		nav.setAttribute( 'aria-label', box.getAttribute( 'data-label' ) );
		status.className = 'collection-filter-status';
		status.setAttribute( 'role', 'status' );

		/**
		 *	Show the rows of one value, and say how many that are
		 *
		 *	@param		{string}		value			'' for all of them
		 *	@param		{boolean}		announce	Whether the status says it: the first drawing says nothing
		 */
		function show( value, announce ) {

			let shown = 0;

			rows.forEach( function( row ) {

				const hit = value === '' || row.getAttribute( 'data-collection-value' ) === value;

				row.hidden = hit === false;
				shown += hit === true ? 1 : 0;
			} );

			buttons.forEach( function( button ) {
				button.setAttribute( 'aria-pressed', button.getAttribute( 'data-value' ) === value ? 'true' : 'false' );
			} );

			status.textContent = announce === true ? box.getAttribute( 'data-shown' ).replace( '{shown}', String( shown ) ).replace( '{total}', String( rows.length ) ) : '';
		}

		[ '' ].concat( values ).forEach( function( value ) {

			const button = document.createElement( 'button' );

			button.type = 'button';
			button.className = 'collection-filter-button';
			button.setAttribute( 'data-value', value );
			button.textContent = value === '' ? box.getAttribute( 'data-all' ) : value;
			button.addEventListener( 'click', function() {
				show( value, true );
			} );

			buttons.push( button );
			nav.appendChild( button );
		} );

		box.insertBefore( status, box.firstChild );
		box.insertBefore( nav, box.firstChild );

		show( '', false );
	}

	function start() {
		Array.prototype.forEach.call( document.querySelectorAll( SELECTOR ), setup );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', start );
	else
		start();

} )();
