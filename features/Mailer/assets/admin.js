/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Mailer					The module's /_admin panel, "Mailer": a status line
 *													(host/port/encryption, never the password) and an
 *													address field with a "Send test mail" button, going
 *													through the same \Nino\Mail::send() and the same
 *													per-ip cap every other mail on the site does. See
 *													Modules\Mailer\Admin beside this file.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.mailer = {

		/**
		 *	Render the pane and load the current status
		 *
		 *	@return		void
		 */
		init : function() {

			const wrap = dc.getElementById('mailer-form');
			if( wrap === null )
				return;

			wrap.innerHTML = '';

			const heading = dc.createElement('h2');
			heading.textContent = Nino.content.getText('/_admin/mailer/label/title');
			wrap.appendChild( heading );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/mailer/hint/intro');
			wrap.appendChild( hint );

			const status = dc.createElement('p');
			status.id = 'mailer-status';
			status.textContent = Nino.content.getText('/_admin/mailer/msg/loading');
			wrap.appendChild( status );

			const form = dc.createElement('form');

			const label = dc.createElement('label');
			label.htmlFor = 'mailer-to';
			label.textContent = Nino.content.getText('/_admin/mailer/label/to');

			const input = dc.createElement('input');
			input.id = 'mailer-to';
			input.type = 'email';
			input.name = 'to';
			input.required = true;

			const msg = dc.createElement('p');
			msg.id = 'mailer-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const send = dc.createElement('button');
			send.type = 'submit';
			send.id = 'mailer-send';
			send.className = 'nino-admin-btn-primary';
			send.textContent = Nino.content.getText('/_admin/mailer/label/send');

			const actions = dc.createElement('div');
			actions.className = 'nino-admin-actionbar';
			actions.appendChild( send );

			form.appendChild( label );
			form.appendChild( input );
			form.appendChild( actions );
			form.appendChild( msg );

			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				Nino.admin.mailer._sendTest( input, send, msg );
			} );

			wrap.appendChild( form );

			Nino.admin.mailer._loadStatus( status );
		},

		/**
		 *	Nothing to restore when the tab is switched to
		 *
		 *	@return		void
		 */
		showCurrent : function() {
		},

		/**
		 *	Call a mailer/* admin action
		 *
		 *	@param		{string}		endpoint			Action name (eg. "test", becomes "mailer/test")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'mailer/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	Load host/port/encryption and render the status line
		 *
		 *	@param		{Element}	status
		 *
		 *	@return		void
		 */
		_loadStatus : function( status ) {

			Nino.admin.mailer._apiCall( 'status', {}, function( httpStatus, response ) {

				if( httpStatus !== 200 || response === null ) {
					status.textContent = '('+ httpStatus+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/mailer/error/load') );
					return;
				}

				status.textContent = response.host
					? Nino.content.getText('/_admin/mailer/label/status')
						.replace( '%host', response.host )
						.replace( '%port', String( response.port ) )
						.replace( '%encryption', response.encryption )
					: Nino.content.getText('/_admin/mailer/label/unconfigured');
			} );
		},

		/**
		 *	Send the test mail and report the outcome
		 *
		 *	@param		{Element}	input
		 *	@param		{Element}	send
		 *	@param		{Element}	msg
		 *
		 *	@return		void
		 */
		_sendTest : function( input, send, msg ) {

			send.disabled = true;
			msg.textContent = Nino.content.getText('/_admin/mailer/msg/sending');

			Nino.admin.mailer._apiCall( 'test', { to : input.value }, function( status, response ) {

				send.disabled = false;

				if( status !== 200 ) {
					msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/mailer/error/send') );
					return;
				}

				msg.textContent = Nino.content.getText('/_admin/mailer/msg/sent');
			} );
		},
	};

	Nino.events.bindCallback( 'ready', Nino.admin.mailer.init );

})(window, document, document.documentElement, document.body);
