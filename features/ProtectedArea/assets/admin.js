/**
 *	Nino features
 *	admin.js		The panel of the Protected area feature: one form - the protected paths as lines and a new
 *							password, typed twice - saved with the bar, and beside Save the one action "Sign everybody out",
 *							asked about first (see Modules\ProtectedArea\Admin beside this file). The password is never part of
 *							an answer, so never part of the screen: the form says whether one is set and nothing more.
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };

	// The parts of the screen that save() and the shell's registry of unsaved input read: made with each drawing
	let form = null;
	let area = null;
	let first = null;
	let second = null;
	// The labels the three controls stand in, which an error is drawn under
	let wraps = null;
	let bar = null;
	// The paths as the server holds them, as the lines of the field
	let saved = '';

	// What is not saved: the field is not what the server holds, or a password is typed
	const changed = function() {
		return area !== null && ( area.value !== saved || first.value !== '' || second.value !== '' );
	};

	// The control a refused value belongs to, by the name the server gave it
	const fieldOf = function( name ) {
		return name === 'paths' ? area : ( name === 'pw' ? first : null );
	};

	// Saving model A of the guide: the form with Save in the bar, the answer as the badge and a toast
	const save = function( panel, done ) {

		const min = panel.data.minLength;
		const pw = first.value;

		Nino.adminUi.clearFieldErrors( form );

		// The server holds the length again; this only spares the round trip. Counted in characters, as the server counts them
		if( pw !== '' && Array.from( pw ).length < min ) {
			Nino.adminUi.fieldError( wraps.first, first, 'protected-pw-error', Nino.adminUi.format( t('/_admin/error/protected_short'), min ) );
			bar.line.fail( t('/_admin/protected/error/fields') );
			first.focus();
			return done( false );
		}

		if( pw !== second.value ) {
			Nino.adminUi.fieldError( wraps.second, second, 'protected-pw2-error', t('/_admin/protected/error/mismatch') );
			bar.line.fail( t('/_admin/protected/error/fields') );
			second.focus();
			return done( false );
		}

		bar.line.saving();

		panel.call( 'save', { paths : area.value, pw : pw }, function( status, response ) {

			if( status !== 200 || response === null ) {

				const field = fieldOf( ( response ?? {} ).field );

				// A refused value is said under its field and in the line of the bar, and the focus goes to it
				if( field !== null && field !== undefined )
					Nino.adminUi.fieldError( field === area ? wraps.area : wraps.first, field, field.id+ '-error', Nino.adminUi.api.errorText( status, response, '/_admin/protected/error/save' ) );

				bar.line.error( status, response, '/_admin/protected/error/save' );

				return done( false );
			}

			// The form is drawn again from the answer: the keyboard keeps its place in it
			const held = document.activeElement !== null && document.activeElement !== undefined ? document.activeElement.id : '';

			panel.data = response;
			draw( panel );

			const back = held !== '' ? document.getElementById( held ) : null;
			( back !== null ? back : document.getElementById('protected-save') ).focus();

			// The badge says saved and the toast says so; a new password says what else it did
			bar.line.saved();

			if( pw !== '' )
				Nino.adminUi.say( t('/_admin/protected/msg/pwsaved') );

			done( true );
		} );
	};

	// Sign everybody out: a question of the level 'big' - it reaches every visitor who unlocked - and the answer as a toast
	const signOut = function( panel, button ) {

		Nino.adminUi.confirm( 'big', {
			title		: t('/_admin/protected/label/signoutall'),
			message	: t('/_admin/protected/confirm/signout'),
			yes			: t('/_admin/protected/label/signoutall'),
			no			: t('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: button,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				button.disabled = true;

				panel.call( 'signout', {}, function( status, response ) {

					button.disabled = false;

					if( status !== 200 || response === null )
						return bar.line.error( status, response, '/_admin/protected/error/save' );

					Nino.adminUi.say( t('/_admin/protected/msg/signedout') );
				} );
			},
		} );
	};

	// The screen, from the answer of protected/state
	const draw = function( panel ) {

		const data = panel.data;
		const mount = panel.mount();

		const paths = Nino.adminUi.textareaField( {
			label			: t('/_admin/protected/label/paths'),
			help			: t('/_admin/protected/help/paths'),
			placeholder	: '/intern',
			id				: 'protected-paths',
			value			: data.paths.join('\n'),
			rows			: 5,
		} );
		const newPw = Nino.adminUi.textField( {
			type				: 'password',
			label				: t('/_admin/protected/label/newpw'),
			help				: Nino.adminUi.format( t('/_admin/protected/help/newpw'), data.minLength ),
			note				: t( data.hasPassword === true ? '/_admin/protected/msg/haspw' : '/_admin/protected/msg/nopw' ),
			id					: 'protected-pw',
			autocomplete: 'new-password',
			maxLength		: data.maxLength,
		} );
		const newPw2 = Nino.adminUi.textField( {
			type				: 'password',
			label				: t('/_admin/protected/label/newpw2'),
			id					: 'protected-pw2',
			autocomplete: 'new-password',
			maxLength		: data.maxLength,
		} );

		area = paths.querySelector('textarea');
		area.name = 'paths';
		first = newPw.querySelector('input');
		first.name = 'pw';
		second = newPw2.querySelector('input');
		saved = area.value;
		wraps = { area : paths, first : newPw, second : newPw2 };

		// The one shape of a form: a fieldset with its legend, the grid in it that puts the label at the left of the field
		const set = Nino.adminUi.el( 'fieldset' );
		const grid = Nino.adminUi.el( 'div', 'nino-admin-fieldgrid' );
		const legend = Nino.adminUi.el( 'legend', '', t('/_admin/protected/label/area') );

		set.appendChild( legend );
		grid.appendChild( paths );
		grid.appendChild( newPw );
		grid.appendChild( newPw2 );
		set.appendChild( grid );

		// What the screen is for is behind the (?) of its section, not a paragraph over it
		Nino.adminUi.sectionHelp( legend, set, t('/_admin/protected/hint/intro') );

		const out = Nino.adminUi.button( t('/_admin/protected/label/signoutall'), function() { signOut( panel, out ) }, { kind : 'danger', id : 'protected-signout' } );

		bar = Nino.adminUi.actionBar( { danger : out, status : true, primary : Nino.adminUi.button( t('/_admin/common/label/save'), null, { type : 'submit', id : 'protected-save' } ) } );

		form = Nino.adminUi.el( 'form' );
		form.noValidate = true;
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); save( panel, function() {} ) } );
		form.appendChild( set );
		form.appendChild( bar.el );
		bar.line.bind( form );

		mount.innerHTML = '';
		mount.appendChild( form );

		if( typeof Nino.admin.dirty === 'object' )
			Nino.admin.dirty.refresh();
	};

	Nino.admin.protected = Nino.adminUi.panel( {
		uri		: 'protected',
		mount	: 'protected-form',
		action	: 'state',
		show	: 'keep',
		dirty	: {
			isDirty	: changed,
			save		: function( done ) { save( Nino.admin.protected, done ) },
			discard	: function() { area.value = saved; first.value = ''; second.value = '' },
		},
		render	: function( data, panel ) { draw( panel ) },
	} );

} )(window);
