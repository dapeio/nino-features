( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	let input = null;
	let line = null;
	let saved = '';

	const save = function( done ) {

		line.saving();

		Nino.admin.hello.call( 'save', { name : input.value }, function( status, response ) {

			if( status !== 200 ) {
				line.error( status, response, '/_admin/hello/error/save' );
				done( false );
				return;
			}

			saved = input.value = response.name;
			line.saved();
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
			done( true );
		} );
	};

	Nino.admin.hello = Nino.adminUi.panel( {
		uri		: 'hello',
		mount	: 'hello-list',
		dirty	: { isDirty : function() { return input !== null && input.value !== saved }, save : save, discard : function() { input.value = saved } },
		render	: function( data, panel ) {

			const mount = panel.mount();
			const field = Nino.adminUi.textField( { label : Nino.content.getText('/_admin/hello/label/name'), value : data.name, maxLength : data.longest } );
			const bar = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );

			input = field.querySelector('input');
			input.name = 'name';
			saved = input.value;

			line = Nino.adminUi.status( Nino.adminUi.el( 'span' ) );
			line.bind( mount );

			bar.appendChild( line.element );
			bar.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/hello/label/save'), function() { save( function() {} ) }, { className : 'nino-admin-btn-primary' } ) );

			mount.innerHTML = '';
			mount.appendChild( field );
			mount.appendChild( bar );
		},
	} );

} )(window);
