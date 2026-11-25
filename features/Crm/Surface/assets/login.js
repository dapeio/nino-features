/**
 *	Crm		The login of the surface in the browser: the page at /crm and the pages of the apps below /crm show it where nobody is signed in. It posts to the kernel's own
 *				login (POST /.nino/auth/login) the way Nino.auth.login does: the mail and the password as HTTP Basic credentials in the
 *				Authorization header - the kernel reads them nowhere else, a field of a form named user or pw is never looked at - and the
 *				token of the page in X-CSRF-Token. Where the kernel says yes it reads the page again (the route is in the data block), with the hash of the link the person came
 *				by, so that a deep link survives the login. The kernel answers 200 for a login, 401 for a pair that does not fit and 403
 *				for a token that is no longer the session's.
 *
 *				It reads what it needs from the JSON block of the page (base, route, csrf, texts) and writes sentences with textContent only.
 */

( function( wn, dc ) {

	'use strict';

	wn.Nino = wn.Nino || {};

	if( Object.prototype.hasOwnProperty.call( wn.Nino, 'crm' ) === false )
		Object.defineProperty( wn.Nino, 'crm', { value : {}, enumerable : true } );

	/**
	 *	The credentials as the kernel reads them: Basic, the pair encoded as UTF-8 first (btoa() alone would send the latin-1 byte of an
	 *	umlaut and throw on a character above U+00FF), as Nino.js does
	 *
	 *	@param		{string}	user
	 *	@param		{string}	pw
	 *
	 *	@return		{string}									The value of the Authorization header
	 */
	const basic = function( user, pw ) {

		let binary = '';

		new wn.TextEncoder().encode( user+ ':'+ pw ).forEach( function( byte ) { binary += String.fromCharCode( byte ) } );

		return 'Basic '+ wn.btoa( binary );
	};

	/**
	 *	Wire the form of the page
	 *
	 *	@param		{Object}	[block]					The element with the JSON; the page's own by default
	 *
	 *	@return		{void}
	 */
	const boot = function( block ) {

		const data = JSON.parse( ( block || dc.getElementById( 'crm-data' ) ).textContent );
		const form = dc.getElementById( 'login' );
		const user = dc.getElementById( 'login-user' );
		const pw = dc.getElementById( 'login-pw' );
		const message = dc.getElementById( 'login-msg' );
		const submit = dc.getElementById( 'login-submit' );

		const say = function( key, ...params ) {
			let text = String( data.texts[key] ?? '' );
			params.forEach( function( param ) { text = text.replace( '%s', String( param ) ) } );
			message.textContent = text;
		};

		// A refusal is said at the field it is about: the field is marked and described by the sentence, and the sentence is read out
		const refuse = function( field ) {
			field.setAttribute( 'aria-invalid', 'true' );
			field.setAttribute( 'aria-describedby', 'login-msg' );
		};

		[ user, pw ].forEach( function( field ) {
			field.addEventListener( 'input', function() {
				field.removeAttribute( 'aria-invalid' );
				field.removeAttribute( 'aria-describedby' );
				message.textContent = '';
			} );
		} );

		form.addEventListener( 'submit', function( event ) {

			event.preventDefault();

			if( submit.disabled === true )
				return;

			message.textContent = '';
			[ user, pw ].forEach( function( field ) { field.removeAttribute( 'aria-invalid' ); field.removeAttribute( 'aria-describedby' ) } );
			submit.disabled = true;

			wn.fetch( data.base+ '/.nino/auth/login', { method : 'POST', credentials : 'same-origin', headers : { 'X-CSRF-Token' : data.csrf, 'Authorization' : basic( user.value, pw.value ) } } ).then( function( response ) {

				submit.disabled = false;

				if( response.status === 200 ) {

					// The page is at its address already: to that address with the same hash the browser only moves to the hash and reads nothing again
					if( wn.location.hash !== '' )
						wn.location.reload();
					else
						wn.location.replace( data.base+ data.route );

					return;
				}

				if( response.status === 401 ) {
					say( 'login.wrong' );
					pw.value = '';
					refuse( pw );
					pw.focus();
				}
				else if( response.status === 403 )
					say( 'login.stale' );
				else
					say( 'login.status', response.status );
			}, function() {
				submit.disabled = false;
				say( 'login.offline' );
			} );
		} );
	};

	Object.defineProperty( wn.Nino.crm, 'login', { value : Object.freeze( { boot : boot } ), enumerable : true } );

	if( dc.readyState === 'loading' )
		dc.addEventListener( 'DOMContentLoaded', function() { boot() } );
	else if( dc.readyState === 'interactive' || dc.readyState === 'complete' )
		boot();

} )( window, document );
