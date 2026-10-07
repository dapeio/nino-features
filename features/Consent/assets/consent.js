/**
 *	Nino - Consent
 *	consent.js		The [consent] banner's behaviour and the consent gate every
 *								page gets, banner or not: read the stored choice, show
 *								the banner when there is none, activate every
 *								<script type="text/plain" data-consent="category"> the
 *								choice allows, toggle [data-consent-show]/[data-consent-
 *								hide], and tell the rest of the page through the
 *								"nino:consent" event. No dependencies, no build step -
 *								bundled into the project's own /.cache/script.js the same
 *								way the kernel bundles Nino.js/Nino.ui.js (see
 *								Consent::init()), so this runs on every page the site's
 *								own frame already loads that bundle on, whether or not
 *								that page renders [consent] itself.
 *
 *								The cookie name/lifetime a project chose under the
 *								feature's settings reach this file through the banner's
 *								own data-consent-cookie/data-consent-days attributes
 *								(see Consent::doConsentShortcode()) - this file is a
 *								static asset, never rendered through the fill engine
 *								(docs/development.md, "Assets Are Not Templates"), so it
 *								cannot read config.php directly. A page with no banner
 *								falls back to the setting's own default cookie name.
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
'use strict';

( function() {

	var DEFAULT_COOKIE_NAME = 'nino_consent';
	var DEFAULT_DAYS = 180;
	var CATEGORIES = [ 'necessary', 'statistics', 'marketing', 'external' ];

	// The [consent-settings] button that last reopened the banner, so the
	// focus goes back to it once a choice is made
	var opener = null;

	/**
	 *	The banner, or null on a page that does not render [consent]
	 *
	 *	@return		{?Element}
	 */
	function banner() {
		return document.querySelector( '.nino-consent' );
	}

	/**
	 *	The cookie name to read/write - the banner's own data-consent-cookie
	 *	when it is on the page, else the setting's own default
	 *
	 *	@return		{string}
	 */
	function cookieName() {
		var el = banner();
		var name = el === null ? '' : ( el.getAttribute( 'data-consent-cookie' ) || '' );
		return name === '' ? DEFAULT_COOKIE_NAME : name;
	}

	/**
	 *	How many days a choice this page writes is kept - the banner's own
	 *	data-consent-days when it is on the page, else the setting's default
	 *
	 *	@return		{number}
	 */
	function cookieDays() {
		var el = banner();
		var raw = el === null ? '' : ( el.getAttribute( 'data-consent-days' ) || '' );
		var days = parseInt( raw, 10 );
		return ( isNaN( days ) === false && days > 0 ) ? days : DEFAULT_DAYS;
	}

	/**
	 *	@param		{string}		name
	 *
	 *	@return		{?string}								The raw cookie value, or null when it is not set
	 */
	function readCookie( name ) {
		var escaped = name.replace( /([.$?*|{}()[\]\\/+^])/g, '\\$1' );
		var match = document.cookie.match( new RegExp( '(?:^|; )' + escaped + '=([^;]*)' ) );
		return match === null ? null : decodeURIComponent( match[1] );
	}

	/**
	 *	Write the compact consent cookie - SameSite=Lax, Secure on https,
	 *	path=/, never a third-party or server-set cookie
	 *
	 *	@param		{string}		name
	 *	@param		{string}		value
	 *	@param		{number}		days
	 *
	 *	@return		void
	 */
	function writeCookie( name, value, days ) {
		var expires = new Date( Date.now() + days * 864e5 ).toUTCString();
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = name + '=' + encodeURIComponent( value ) + '; expires=' + expires + '; path=/; SameSite=Lax' + secure;
	}

	/**
	 *	A category list cut to what this site offers - what the banner has a
	 *	checkbox for. A category the settings switched off is never allowed
	 *	(\Nino\Modules\Consent::allowed() refuses it whatever the cookie says),
	 *	and the browser must not release its placeholders either. On a page with
	 *	no banner there is nothing to read the offer from, and the list is kept
	 *	as it is
	 *
	 *	@param		{Array}			list
	 *
	 *	@return		{Array}
	 */
	function onlyOffered( list ) {

		if( banner() === null )
			return list;

		var offered = offeredCategories();

		return list.filter( function( category ) {
			return category === 'necessary' || offered.indexOf( category ) !== -1;
		} );
	}

	/**
	 *	The stored cookie value as a clean, deduped category list -
	 *	"necessary" is always in it, an unknown token is dropped, and so is a
	 *	category the site does not offer (see onlyOffered())
	 *
	 *	@param		{?string}		raw
	 *
	 *	@return		{?Array}								null when there was no cookie to parse
	 */
	function parseAllowed( raw ) {

		if( raw === null || raw === '' )
			return null;

		// The banner Nino's base install shipped up to 1.3.x wrote 'accepted'
		// or 'declined' into a cookie of the same name (for 365 days): a choice
		// already made, kept - every category the site offers, or the necessary
		// one alone
		if( raw === 'accepted' )
			return onlyOffered( CATEGORIES.slice() );
		if( raw === 'declined' )
			return [ 'necessary' ];

		var list = raw.split( ',' ).map( function( entry ) {
			return entry.trim();
		} ).filter( function( entry ) {
			return CATEGORIES.indexOf( entry ) !== -1;
		} );

		if( list.indexOf( 'necessary' ) === -1 )
			list.unshift( 'necessary' );

		return onlyOffered( list );
	}

	/**
	 *	Activate one placeholder script - a real <script>, cloned from the
	 *	placeholder's own attributes (data-src becomes src, everything else
	 *	but "type" is copied as is) and its inline content when there is no
	 *	data-src. Marked so a later call never activates it twice
	 *
	 *	@param		{Element}		placeholder
	 *
	 *	@return		void
	 */
	function activateScript( placeholder ) {

		if( placeholder.dataset.consentActivated === 'true' )
			return;

		placeholder.dataset.consentActivated = 'true';

		var script = document.createElement( 'script' );
		var attributes = placeholder.attributes;
		var src = placeholder.getAttribute( 'data-src' );

		for( var i = 0; i < attributes.length; i++ )
			if( attributes[i].name !== 'type' )
				script.setAttribute( attributes[i].name, attributes[i].value );

		if( src !== null && src !== '' )
			script.src = src;
		else
			script.textContent = placeholder.textContent;

		placeholder.parentNode.insertBefore( script, placeholder.nextSibling );
	}

	/**
	 *	Set the banner's checkboxes to a choice, so reopening it shows what is
	 *	stored and "Save selection" keeps it rather than quietly revoking it.
	 *	"necessary" is disabled and stays as it is
	 *
	 *	@param		{Array}			allowed
	 *
	 *	@return		void
	 */
	function syncBoxes( allowed ) {

		var el = banner();
		if( el === null )
			return;

		var boxes = el.querySelectorAll( '[data-consent-category]' );
		for( var i = 0; i < boxes.length; i++ )
			if( boxes[i].disabled !== true )
				boxes[i].checked = allowed.indexOf( boxes[i].getAttribute( 'data-consent-category' ) ) !== -1;
	}

	/**
	 *	Apply one allowed-categories list to the whole page: the
	 *	documentElement dataset, the banner's checkboxes, every matching
	 *	placeholder script, the data-consent-show/hide toggles, and the
	 *	"nino:consent" event
	 *
	 *	@param		{Array}			allowed
	 *
	 *	@return		void
	 */
	function applyConsent( allowed ) {

		document.documentElement.dataset.consent = allowed.join( ',' );

		syncBoxes( allowed );

		var placeholders = document.querySelectorAll( 'script[type="text/plain"][data-consent]' );
		for( var i = 0; i < placeholders.length; i++ )
			if( allowed.indexOf( placeholders[i].getAttribute( 'data-consent' ) ) !== -1 )
				activateScript( placeholders[i] );

		for( var c = 0; c < allowed.length; c++ ) {

			var toShow = document.querySelectorAll( '[data-consent-show="' + allowed[c] + '"]' );
			for( var s = 0; s < toShow.length; s++ )
				toShow[s].hidden = false;

			var toHide = document.querySelectorAll( '[data-consent-hide="' + allowed[c] + '"]' );
			for( var h = 0; h < toHide.length; h++ )
				toHide[h].hidden = true;
		}

		document.dispatchEvent( new CustomEvent( 'nino:consent', { detail: { allowed: allowed.slice() } } ) );
	}

	/**
	 *	Every category currently offered by the banner's own checkboxes -
	 *	what "accept all" means on this page, since a category the settings
	 *	did not switch on has no checkbox to begin with
	 *
	 *	@return		{Array}
	 */
	function offeredCategories() {

		var el = banner();
		if( el === null )
			return CATEGORIES.slice();

		var boxes = el.querySelectorAll( '[data-consent-category]' );
		var list = [];
		for( var i = 0; i < boxes.length; i++ )
			list.push( boxes[i].getAttribute( 'data-consent-category' ) );

		return list;
	}

	/**
	 *	Store a choice, apply it and hide the banner - the only place that
	 *	writes the cookie; PHP never does (see \Nino\Modules\Consent::allowed())
	 *
	 *	@param		{Array}			allowed
	 *
	 *	@return		void
	 */
	function setConsent( allowed ) {

		writeCookie( cookieName(), allowed.join( ',' ), cookieDays() );
		applyConsent( allowed );

		var el = banner();
		if( el !== null )
			el.hidden = true;

		// Back to the button that opened it. Nothing keeps the focus inside the
		// banner meanwhile - it is a notice, not a modal
		if( opener !== null && opener.parentNode !== null && typeof opener.focus === 'function' )
			opener.focus();

		opener = null;
	}

	/**
	 *	Wire the banner's own buttons and every [consent-settings] button on
	 *	the page - a no-op where the banner is absent
	 *
	 *	@return		void
	 */
	function bind() {

		var el = banner();

		if( el !== null ) {

			var acceptAll = el.querySelector( '[data-consent-action="accept-all"]' );
			var necessaryOnly = el.querySelector( '[data-consent-action="necessary-only"]' );
			var save = el.querySelector( '[data-consent-action="save"]' );

			if( acceptAll !== null )
				acceptAll.addEventListener( 'click', function() {
					setConsent( offeredCategories() );
				} );

			if( necessaryOnly !== null )
				necessaryOnly.addEventListener( 'click', function() {
					setConsent( [ 'necessary' ] );
				} );

			if( save !== null )
				save.addEventListener( 'click', function() {

					var checked = [ 'necessary' ];
					var boxes = el.querySelectorAll( '[data-consent-category]:checked' );

					for( var i = 0; i < boxes.length; i++ ) {
						var category = boxes[i].getAttribute( 'data-consent-category' );
						if( checked.indexOf( category ) === -1 )
							checked.push( category );
					}

					setConsent( checked );
				} );
		}

		// A reopen button on a page with no banner has nothing to open: it is
		// hidden rather than left to do nothing
		var openButtons = document.querySelectorAll( '.nino-consent-open' );
		for( var b = 0; b < openButtons.length; b++ ) {

			if( el === null ) {
				openButtons[b].hidden = true;
				continue;
			}

			openButtons[b].addEventListener( 'click', function() {
				var current = banner();
				if( current === null )
					return;

				opener = this;
				current.hidden = false;
				current.focus();
			} );
		}
	}

	function init() {

		// This feature supersedes the plain accept/decline banner Nino's base
		// install shipped up to 1.3.x (.nino-cookie-banner in the project's
		// html-footer.tpl). A project set up then keeps the block - an install
		// unit is applied once and never rewritten - so where one is still in the
		// page, it goes: a visitor never sees two, nor an unstyled leftover
		Array.prototype.forEach.call( document.querySelectorAll( '.nino-cookie-banner' ), function( el ) {
			if( el.parentNode !== null )
				el.parentNode.removeChild( el );
		} );

		bind();

		var stored = parseAllowed( readCookie( cookieName() ) );

		if( stored === null ) {

			var el = banner();
			if( el !== null )
				el.hidden = false;

			applyConsent( [ 'necessary' ] );
			return;
		}

		applyConsent( stored );
	}

	if( document.readyState === 'loading' )
		document.addEventListener( 'DOMContentLoaded', init );
	else
		init();

} )();
