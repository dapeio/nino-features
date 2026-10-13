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

	// The screen is drawn from the answer to mailer/status, which the panel
	// asks for when the page is ready: the address field has its value from
	// it, and nothing is on screen before it arrives
	Nino.admin.mailer = Nino.adminUi.panel( {
		uri		: 'mailer',
		mount	: 'mailer-form',
		action	: 'status',
		boot	: 'ready',
		render	: function( data, panel ) {

			const wrap = panel.mount();
			wrap.innerHTML = '';

			// No heading of its own: the head the shell renders over the pane
			// names the panel (Nino.adminUi.panelHead()), and a second name a
			// line under it would only say the same thing again
			wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/mailer/hint/intro') ) );

			const status = Nino.adminUi.el( 'p' );
			status.id = 'mailer-status';
			wrap.appendChild( status );

			const field = Nino.adminUi.textField( { type : 'email', required : true, id : 'mailer-to', value : data.testTo, label : Nino.content.getText('/_admin/mailer/label/to') } );
			const input = field.querySelector('input');
			input.name = 'to';

			const message = Nino.adminUi.el( 'span' );
			message.id = 'mailer-msg';

			const line = Nino.adminUi.status( message, { saving : Nino.content.getText('/_admin/mailer/msg/sending'), saved : Nino.content.getText('/_admin/mailer/msg/sent') } );
			const send = Nino.adminUi.button( Nino.content.getText('/_admin/mailer/label/send'), null, { type : 'submit', id : 'mailer-send', className : 'nino-admin-btn-primary' } );

			const bar = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );
			bar.appendChild( line.element );
			bar.appendChild( send );

			// Under the form, where a failed test mail is looked up afterwards
			const errors = Nino.adminUi.el( 'div' );
			errors.id = 'mailer-errors';

			const form = Nino.adminUi.el( 'form' );
			form.appendChild( field );
			form.appendChild( bar );

			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				Nino.admin.mailer._sendTest( input, send, line, status, errors );
			} );

			wrap.appendChild( form );
			wrap.appendChild( errors );

			Nino.admin.mailer._show( data, status, errors );
		},
	} );

	Object.assign( Nino.admin.mailer, {

		/**
		 *	Write what the server answered: the status line - host, port and
		 *	encryption, or that nothing is configured - and the last errors
		 *
		 *	@param		{Object}	data				The answer to mailer/status
		 *	@param		{Element}	status
		 *	@param		{Element}	errors			Where the last failures are listed
		 *
		 *	@return		void
		 */
		_show : function( data, status, errors ) {

			status.textContent = data.host
				? Nino.content.getText('/_admin/mailer/label/status')
					.replace( '%host', data.host )
					.replace( '%port', String( data.port ) )
					.replace( '%encryption', data.encryption )
				: Nino.content.getText('/_admin/mailer/label/unconfigured');

			Nino.admin.mailer._renderErrors( errors, data.errors || [] );
		},

		/**
		 *	Ask for the status again and write it over the screen's - the form
		 *	itself is left as it is, so that what somebody typed stays
		 *
		 *	@param		{Element}	status
		 *	@param		{Element}	errors
		 *
		 *	@return		void
		 */
		_refresh : function( status, errors ) {

			Nino.admin.mailer.call( 'status', {}, function( httpStatus, response ) {

				if( httpStatus !== 200 || response === null ) {
					status.textContent = Nino.adminUi.api.errorText( httpStatus, response, '/_admin/mailer/error/load' );
					return;
				}

				Nino.admin.mailer._show( response, status, errors );
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

			errors.appendChild( Nino.adminUi.el( 'p', 'nino-admin-eyebrow', Nino.content.getText('/_admin/mailer/label/errors') ) );

			if( list.length === 0 ) {
				errors.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/mailer/hint/errors-empty') ) );
				return;
			}

			const items = Nino.adminUi.el( 'ul', 'nino-admin-list nino-admin-list-dense' );

			list.forEach( function( error ) {
				items.appendChild( Nino.adminUi.el( 'li', '', error.date+ ' - '+ error.reason ) );
			} );

			errors.appendChild( items );
		},

		/**
		 *	Send the test mail and report the outcome
		 *
		 *	@param		{Element}	input
		 *	@param		{Element}	send
		 *	@param		{Object}	line				The live line, see Nino.adminUi.status()
		 *	@param		{Element}	status			The status line, loaded again with the errors once the test is done
		 *	@param		{Element}	errors
		 *
		 *	@return		void
		 */
		_sendTest : function( input, send, line, status, errors ) {

			send.disabled = true;
			line.saving();

			Nino.admin.mailer.call( 'test', { to : input.value }, function( httpStatus, response ) {

				send.disabled = false;

				// A failure was recorded on the server: list it, with the ones
				// before it
				Nino.admin.mailer._refresh( status, errors );

				// The server's own message is the reason the mail server gave
				if( httpStatus !== 200 ) {
					line.error( httpStatus, response, '/_admin/mailer/error/send' );
					return;
				}

				line.saved();
			} );
		},
	} );

})(window, document, document.documentElement, document.body);
