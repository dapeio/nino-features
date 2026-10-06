/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Mailer					The module's /_admin panel, "Mailer": a status line
 *													(host/port/encryption, never the password), an
 *													address field - the signed-in account's own address
 *													to begin with - with a "Send test mail" button, going
 *													through the same \Nino\Mail::send() and the same
 *													per-ip cap every other mail on the site does, and the
 *													last failures the transport recorded. See
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

			// No heading of its own: the head the shell renders over the pane
			// names the panel (Nino.adminUi.panelHead()), and a second name a
			// line under it would only say the same thing again
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

			// Under the form, where a failed test mail is looked up afterwards
			const errors = dc.createElement('div');
			errors.id = 'mailer-errors';

			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				Nino.admin.mailer._sendTest( input, send, msg, status, errors );
			} );

			wrap.appendChild( form );
			wrap.appendChild( errors );

			Nino.admin.mailer._loadStatus( status, input, errors );
		},

		/**
		 *	Nothing to restore when the tab is switched to
		 *
		 *	@return		void
		 */
		showCurrent : function() {
		},

		/**
		 *	Call a mailer/* admin action. The workbench's own request helper
		 *	posts where this Nino has one - it knows the project's directory
		 *	and what to do when the page has outlived its session; the post
		 *	below is what every panel did before it, with the base the asset
		 *	bundle fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint			Action name (eg. "test", becomes "mailer/test")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'mailer/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'mailer/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the server's code in the workbench's
		 *	language, then its own message - which for a test mail is the
		 *	reason the mail server gave - then this panel's sentence, where
		 *	this Nino has errorText(). Before it, "(status) message"
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key					Fill key of the panel's own sentence
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( key ) );
		},

		/**
		 *	Load host/port/encryption, render the status line, offer the
		 *	address the test mail goes to and list the last errors
		 *
		 *	@param		{Element}	status
		 *	@param		{Element}	input				The test mail's address field - filled only while it is empty
		 *	@param		{Element}	errors			Where the last failures are listed
		 *
		 *	@return		void
		 */
		_loadStatus : function( status, input, errors ) {

			Nino.admin.mailer._apiCall( 'status', {}, function( httpStatus, response ) {

				if( httpStatus !== 200 || response === null ) {
					status.textContent = Nino.admin.mailer._errorText( httpStatus, response, '/_admin/mailer/error/load' );
					return;
				}

				status.textContent = response.host
					? Nino.content.getText('/_admin/mailer/label/status')
						.replace( '%host', response.host )
						.replace( '%port', String( response.port ) )
						.replace( '%encryption', response.encryption )
					: Nino.content.getText('/_admin/mailer/label/unconfigured');

				// Never over what somebody has typed already
				if( input.value === '' && response.testTo )
					input.value = response.testTo;

				Nino.admin.mailer._renderErrors( errors, response.errors || [] );
			} );
		},

		/**
		 *	List the last failures - date and reason, newest first, as text -
		 *	or say there are none
		 *
		 *	@param		{Element}	errors
		 *	@param		{Array}		list				[ { date, reason }, ... ]
		 *
		 *	@return		void
		 */
		_renderErrors : function( errors, list ) {

			errors.innerHTML = '';

			const label = dc.createElement('p');
			label.className = 'nino-admin-eyebrow';
			label.textContent = Nino.content.getText('/_admin/mailer/label/errors');
			errors.appendChild( label );

			if( list.length === 0 ) {
				errors.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/mailer/hint/errors-empty') ) );
				return;
			}

			const items = dc.createElement('ul');
			items.className = 'nino-admin-list nino-admin-list-dense';

			list.forEach( function( error ) {
				const item = dc.createElement('li');
				item.textContent = error.date+ ' - '+ error.reason;
				items.appendChild( item );
			} );

			errors.appendChild( items );
		},

		/**
		 *	Send the test mail and report the outcome
		 *
		 *	@param		{Element}	input
		 *	@param		{Element}	send
		 *	@param		{Element}	msg
		 *	@param		{Element}	statusLine	The status line, loaded again with the errors once the test is done
		 *	@param		{Element}	errors
		 *
		 *	@return		void
		 */
		_sendTest : function( input, send, msg, statusLine, errors ) {

			send.disabled = true;
			msg.textContent = Nino.content.getText('/_admin/mailer/msg/sending');

			Nino.admin.mailer._apiCall( 'test', { to : input.value }, function( status, response ) {

				send.disabled = false;

				// A failure was recorded on the server: list it, with the ones
				// before it
				Nino.admin.mailer._loadStatus( statusLine, input, errors );

				if( status !== 200 ) {
					msg.textContent = Nino.admin.mailer._errorText( status, response, '/_admin/mailer/error/send' );
					return;
				}

				msg.textContent = Nino.content.getText('/_admin/mailer/msg/sent');
			} );
		},
	};

	Nino.events.bindCallback( 'ready', Nino.admin.mailer.init );

})(window, document, document.documentElement, document.body);
