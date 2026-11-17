/**
 *	Nino features
 *	admin.js		The panel of the Mailer feature: a test mail as a form of one field, whose answer is a
 *							toast or, where it failed, the line of the bar, and the last failures as a dense
 *							list (see Modules\Mailer\Admin beside this file). Which server the mail goes
 *							through stands at the field as its note; the password and the user name are
 *							never part of an answer, so never part of the screen.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// The words of the three kinds of encryption, one literal key each
	const ENCRYPTIONS = { starttls : '/_admin/mailer/encryption/starttls', tls : '/_admin/mailer/encryption/tls', none : '/_admin/mailer/encryption/none' };

	// Where the mail goes through: the host with its port and encryption, or that nothing is set yet
	const stateText = function( data ) {
		return data.host
			? Nino.adminUi.format( t('/_admin/mailer/label/status'), data.host, String( data.port ), t( ENCRYPTIONS[data.encryption] ?? ENCRYPTIONS.starttls ) )
			: t('/_admin/mailer/label/unconfigured');
	};

	// The last failures, newest first, as a dense list: the date and the reason the server gave, as text. Where there are none, an empty state
	const failures = function( list ) {

		const box = el( 'section', 'mailer-errors' );

		box.id = 'mailer-errors';
		box.appendChild( Nino.adminUi.sectionTitle( t('/_admin/mailer/label/errors') ) );

		if( list.length === 0 ) {
			box.appendChild( Nino.adminUi.emptyState( t('/_admin/mailer/hint/errors-empty') ) );
			return box;
		}

		const items = el( 'ul', 'nino-admin-list nino-admin-list-dense' );

		list.forEach( function( error ) {

			const row = el( 'li', 'mailer-error' );

			row.appendChild( el( 'strong', '', error.date ) );
			row.appendChild( dc.createTextNode( ' ' ) );
			row.appendChild( el( 'span', '', error.reason ) );
			items.appendChild( row );
		} );

		box.appendChild( items );

		return box;
	};

	Nino.admin.mailer = Nino.adminUi.panel( {
		uri		: 'mailer',
		mount	: 'mailer-form',
		action	: 'status',
		show	: 'always',
		render	: function( data, panel ) {

			const mount = panel.mount();
			const form = el( 'form' );
			const set = el( 'fieldset' );
			const grid = el( 'div', 'nino-admin-fieldgrid' );
			const field = Nino.adminUi.textField( {
				key			: 'to',
				type		: 'email',
				id			: 'mailer-to',
				label		: t('/_admin/mailer/label/to'),
				help		: t('/_admin/mailer/help/to'),
				note		: stateText( data ),
				value		: data.testTo,
				required	: true,
				maxLength	: 254,
			} );
			const input = field.querySelector('input');
			const send = Nino.adminUi.button( t('/_admin/mailer/label/send'), null, { kind : 'primary', type : 'submit', id : 'mailer-send' } );
			// The line of the bar says a failure and stays until something changes; the badge of the bar has nothing to say here
			const bar = Nino.adminUi.actionBar( { status : { fresh : true }, primary : send } );
			const errors = el( 'div' );

			form.noValidate = true;
			input.name = 'to';
			const legend = el( 'legend', '', t('/_admin/mailer/label/test') );

			set.appendChild( legend );
			grid.appendChild( field );
			set.appendChild( grid );
			form.appendChild( set );
			// What the screen is for is behind the (?) of its section, not a paragraph over it
			Nino.adminUi.sectionHelp( legend, set, t('/_admin/mailer/hint/intro') );
			form.appendChild( bar.el );

			errors.appendChild( failures( data.errors || [] ) );

			form.addEventListener( 'submit', function( event ) {

				event.preventDefault();
				Nino.adminUi.clearFieldErrors( form );

				if( input.value.trim() === '' ) {
					Nino.adminUi.fieldError( field, input, 'mailer-to-error', Nino.adminUi.format( t('/_admin/common/error/missing'), t('/_admin/mailer/label/to') ) );
					input.focus();
					return;
				}

				send.disabled = true;
				bar.line.idle( t('/_admin/mailer/msg/sending') );

				panel.call( 'test', { to : input.value }, function( status, response ) {

					send.disabled = false;

					// A failure was recorded on the server: the list is asked again, with the ones before it
					panel.call( 'status', {}, function( code, answer ) {

						if( code !== 200 || answer === null )
							return;

						errors.innerHTML = '';
						errors.appendChild( failures( answer.errors || [] ) );
					} );

					// The server's own sentence is the reason the mail server gave
					if( status !== 200 ) {

						if( ( response ?? {} ).field === 'to' ) {
							bar.line.idle('');
							Nino.adminUi.fieldError( field, input, 'mailer-to-error', Nino.adminUi.api.errorText( status, response, '/_admin/mailer/error/send' ) );
							input.focus();
							return;
						}

						// The sentence of the server's own line, without the number of the status in front of it
						bar.line.fail( Nino.adminUi.format( t('/_admin/mailer/error/send-reason'), String( ( response ?? {} ).error ?? '' ) ) );
						return;
					}

					bar.line.idle('');
					Nino.adminUi.say( t('/_admin/mailer/msg/sent') );
				} );
			} );

			mount.innerHTML = '';
			mount.appendChild( form );
			mount.appendChild( errors );
		},
	} );

} )( window, document );
