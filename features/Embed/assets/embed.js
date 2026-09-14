/**
 *	Nino - External Embeds
 *	embed.js			Creates the iframe an [embed] stands for, at the moment it is
 *							released, and never before. No dependencies, no build step -
 *							bundled into the project's own /.cache/script.js the same way
 *							the kernel bundles Nino.js/Nino.ui.js (see Embed::init()).
 *
 *							Why this exists at all: a hidden iframe is still fetched. An
 *							iframe inside a container with the hidden attribute, with
 *							display:none or with visibility:hidden loads exactly like a
 *							visible one, so a page that writes the frame and hides it has
 *							already given the visitor's address to the provider. What the
 *							shortcode writes therefore carries the address in
 *							data-embed-src, and the iframe below is built from it - so
 *							there is no request to suppress in the first place.
 *
 *							Two things release one, and both are the visitor's: pressing
 *							the surface, and having allowed the consent category the
 *							site named. The second is read off <html data-consent>, which
 *							the Consent feature writes, and kept current through the
 *							"nino:consent" event it fires - so the two features meet over
 *							markup and neither imports the other. A site without Consent
 *							has no such attribute, every embed waits for a press, and
 *							nothing here has to know that either.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var SELECTOR = '.nino-embed[data-embed-src]';

	/**
	 *	What a frame is allowed to do. Deliberately short: a video needs to
	 *	play fullscreen and a map needs to ask where you are, and neither
	 *	needs anything else. allow-scripts and allow-same-origin together
	 *	would let a frame reach out of its own sandbox, so this does not
	 *	sandbox at all rather than sandbox in a way that reads as protection
	 *	and is not - what keeps a provider out of this page is that its frame
	 *	is a different origin, which it is either way
	 */
	var ALLOW = 'accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture';

	/**
	 *	The consent categories the visitor has allowed, as the Consent
	 *	feature last wrote them onto <html>
	 *
	 *	@return		{Array}
	 */
	function allowed() {

		var raw = document.documentElement.getAttribute( 'data-consent' );

		return ( typeof raw === 'string' && raw !== '' ) ? raw.split( ',' ) : [];
	}

	/**
	 *	Whether one embed's category is among them. An embed with no category
	 *	- the site switched that off, or never had Consent - is never
	 *	released this way and always waits for the press
	 *
	 *	@param		{Element}		embed
	 *	@param		{Array}			categories
	 *
	 *	@return		{boolean}
	 */
	function consented( embed, categories ) {

		var category = embed.getAttribute( 'data-embed-consent' );

		return typeof category === 'string' && category !== '' && categories.indexOf( category ) !== -1;
	}

	/**
	 *	Put the frame in, and take the surface away. The button is removed
	 *	rather than hidden: a hidden button is still in the tab order, and
	 *	this one has nothing left to do
	 *
	 *	@param		{Element}		embed
	 *
	 *	@return		void
	 */
	function load( embed ) {

		if( embed.dataset.embedLoaded === 'true' )
			return;

		var src = embed.getAttribute( 'data-embed-src' );

		if( typeof src !== 'string' || src.slice( 0, 8 ) !== 'https://' )
			return;

		embed.dataset.embedLoaded = 'true';

		var frame = document.createElement( 'iframe' );
		frame.setAttribute( 'src', src );
		frame.setAttribute( 'title', embed.getAttribute( 'data-embed-title' ) || '' );
		// allow carries fullscreen, and a browser says so out loud if
		// allowfullscreen is set beside it: "Allow attribute will take
		// precedence over 'allowfullscreen'"
		frame.setAttribute( 'allow', ALLOW );
		// The provider learns which page this is from anyway, through the
		// referrer the browser sends. It does not have to learn which path
		frame.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
		frame.setAttribute( 'loading', 'eager' );

		var button = embed.querySelector( '.nino-embed-open' );
		if( button !== null )
			button.parentNode.removeChild( button );

		embed.appendChild( frame );
		embed.classList.add( 'nino-is-loaded' );

		/*	A page may want to know - a tracker that should only run once a
			video is there, a layout that has to measure again. The name is the
			one Consent uses for its own announcement, with the host and the
			element this happened to	*/
		document.dispatchEvent( new CustomEvent( 'nino:embed', {
			detail : { host : embed.getAttribute( 'data-embed-host' ) || '', embed : embed },
		} ) );
	}

	/**
	 *	One press releases the embed pressed, and - where the site asked for
	 *	it - every other embed of the same provider on this page. Nothing is
	 *	stored: it lasts as long as the page is open, because a decision kept
	 *	past that would be a decision to declare
	 *
	 *	@param		{Element}		embed
	 *
	 *	@return		void
	 */
	function press( embed ) {

		load( embed );

		if( embed.hasAttribute( 'data-embed-remember' ) === false )
			return;

		var host = embed.getAttribute( 'data-embed-host' );

		if( typeof host !== 'string' || host === '' )
			return;

		var others = document.querySelectorAll( SELECTOR + '[data-embed-remember][data-embed-host="' + host.replace( /"/g, '' ) + '"]' );

		for( var i = 0; i < others.length; i++ )
			load( others[i] );
	}

	/**
	 *	Release every embed the current consent allows - on load, and again
	 *	whenever the visitor changes their mind
	 *
	 *	@return		void
	 */
	function applyConsent() {

		var categories = allowed();

		if( categories.length === 0 )
			return;

		var embeds = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < embeds.length; i++ )
			if( consented( embeds[i], categories ) === true )
				load( embeds[i] );
	}

	/**
	 *	Show the surfaces and wire them up. They are written hidden, the way
	 *	[mode-switch] is: where this file never runs, a surface would be a
	 *	thing to press that cannot do anything, and the <noscript> link
	 *	beside it is what is left instead
	 *
	 *	@return		void
	 */
	function init() {

		var embeds = document.querySelectorAll( SELECTOR );

		for( var i = 0; i < embeds.length; i++ ) {

			var button = embeds[i].querySelector( '.nino-embed-open' );

			if( button === null )
				continue;

			button.hidden = false;
			button.addEventListener( 'click', ( function( embed ) {
				return function() { press( embed ) };
			} )( embeds[i] ) );
		}

		applyConsent();
	}

	document.addEventListener( 'nino:consent', applyConsent );

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
