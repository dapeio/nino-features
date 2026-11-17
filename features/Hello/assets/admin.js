( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };

	let form = null;
	let input = null;
	let bar = null;
	let saved = '';

	// Saving model A of the guide: a form, Save in the bar, the answer as the badge and a toast
	const save = function( done ) {

		Nino.adminUi.clearFieldErrors( form );
		bar.line.saving();

		Nino.admin.hello.call( 'save', { name : input.value }, function( status, response ) {

			if( status !== 200 ) {
				bar.line.error( status, response, '/_admin/hello/error/save' );
				input.focus();
				done( false );
				return;
			}

			saved = input.value = response.name;
			bar.line.saved();
			Nino.adminUi.say( t('/_admin/common/msg/saved') );
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
			done( true );
		} );
	};

	Nino.admin.hello = Nino.adminUi.panel( {
		uri    : 'hello',
		mount  : 'hello-list',
		dirty  : { isDirty : function() { return input !== null && input.value !== saved }, save : save, discard : function() { input.value = saved } },
		render : function( data, panel ) {

			const mount = panel.mount();
			const field = Nino.adminUi.textField( { label : t('/_admin/hello/label/name'), help : t('/_admin/hello/hint/name'), value : data.name, maxLength : data.longest, id : 'hello-name' } );

			// The one shape of a form: a fieldset with its legend, the grid in it that puts the label at the left of the field
			const set = Nino.adminUi.el( 'fieldset' );
			const grid = Nino.adminUi.el( 'div', 'nino-admin-fieldgrid' );

			set.appendChild( Nino.adminUi.el( 'legend', '', t('/_admin/hello/label/greeting') ) );
			set.appendChild( grid );

			form = Nino.adminUi.el( 'form' );
			form.noValidate = true;
			form.addEventListener( 'submit', function( event ) { event.preventDefault(); save( function() {} ) } );

			input = field.querySelector('input');
			input.name = 'name';
			saved = input.value;

			bar = Nino.adminUi.actionBar( { status : true, primary : Nino.adminUi.button( t('/_admin/common/label/save'), null, { type : 'submit' } ) } );

			grid.appendChild( field );
			form.appendChild( set );
			form.appendChild( bar.el );
			bar.line.bind( form );

			mount.innerHTML = '';
			mount.appendChild( form );
		},
	} );

} )(window);
