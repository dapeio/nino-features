/**
 *	Nino - vCard
 *	vcard.js		The switch of the colour scheme that the card offers its visitor, in the
 *							bundle only while the owner has it on. The card says which scheme it wears in
 *							data-vcard-mode (light or dark; no attribute is auto, the device decides), and
 *							the visitor's choice replaces it: light and dark write the attribute, auto takes
 *							it away. The choice is kept in the browser alone, under the key the Modeswitch
 *							of the catalogue keeps its own under, with the same three words: no cookie,
 *							nothing sent to the server. A browser that refuses storage gets a switch that
 *							works for the visit.
 *
 *	@package		Dape/Nino
 *	@author			David Perchermeier <mail@dape.io>
 *	@link				https://github.com/dapeio/nino-features
 */

( function( wn, dc ) {

	'use strict';

	const KEY = 'nino-mode';
	const ATTR = 'data-vcard-mode';
	const SCHEMES = [ 'light', 'auto', 'dark' ];

	const card = dc.querySelector('.nino-vcard[data-vcard-design]');
	const group = card === null ? null : card.querySelector('.nino-vcard-scheme');

	if( group === null )
		return;

	// What the owner fixed, as one of the three: the start of a visitor who has chosen nothing
	const owner = SCHEMES.indexOf( card.getAttribute( ATTR ) ) === -1 ? 'auto' : card.getAttribute( ATTR );

	/**
	 *	The stored choice, or '' where there is none. 'system' is the word the Modeswitch stores for auto
	 *
	 *	@return		{string}
	 */
	const stored = function() {
		try {
			const value = wn.localStorage.getItem( KEY ) === 'system' ? 'auto' : wn.localStorage.getItem( KEY );
			return SCHEMES.indexOf( value ) === -1 ? '' : value;
		}
		catch( e ) {
			return '';
		}
	};

	/**
	 *	Put a scheme on the card and mark the button that is on
	 *
	 *	@param		{string}		scheme
	 *
	 *	@return		void
	 */
	const apply = function( scheme ) {

		if( scheme === 'auto' )
			card.removeAttribute( ATTR );
		else
			card.setAttribute( ATTR, scheme );

		group.querySelectorAll('[data-vcard-scheme]').forEach( function( button ) {
			const on = button.getAttribute('data-vcard-scheme') === scheme;
			button.classList.toggle( 'is-active', on );
			button.setAttribute( 'aria-pressed', on === true ? 'true' : 'false' );
		} );
	};

	group.addEventListener( 'click', function( event ) {

		const button = event.target.closest('[data-vcard-scheme]');

		if( button === null )
			return;

		const scheme = button.getAttribute('data-vcard-scheme');

		apply( scheme );

		try {
			wn.localStorage.setItem( KEY, scheme === 'auto' ? 'system' : scheme );
		}
		catch( e ) {
			// Storage refused: the choice holds for this visit
		}
	} );

	apply( stored() === '' ? owner : stored() );
	group.hidden = false;
} )( window, document );
