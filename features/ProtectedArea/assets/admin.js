/**
 *	Nino										A compact filesystembased php framework
 *	Modules\ProtectedArea		The feature's /_admin panel, "Protected area": a form
 *													for a new password, the site's pages as a list to
 *													tick the protected ones off and one button that signs
 *													everybody out - see Modules\ProtectedArea\Admin beside
 *													this file. Ships with the feature and is loaded exactly
 *													while it is active. Draws with the workbench's own
 *													classes and nothing of its own; every string is a fill
 *													and every server-sent value goes in as text.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The state is drawn from the answer to protected/state; showCurrent() below
	// asks for it again on every showing
	Nino.admin.protected = Nino.adminUi.panel( {
		uri		: 'protected',
		mount	: 'protected-form',
		action	: 'state',
		error	: '/_admin/common/error/load',
		render	: function( data ) {
			Nino.admin.protected._take( data );
			Nino.admin.protected._render();
		},
	} );

	Object.assign( Nino.admin.protected, {

		// What protected/state answered: hasPassword, pages, extra, minLength, maxLength
		_state	: null,
		// The pages ticked on screen, by internal uri - a working copy: leaving
		// without saving changes nothing
		_chosen	: {},
		// The live lines of the three sections - password, pages, signout - made
		// with the screen and drawn again with it
		_lines	: {},

		/**
		 *	The shell calls this when the panel is opened. The state is asked for
		 *	again every time: a page that was made, renamed or protected
		 *	elsewhere since the panel was last looked at must be in the list.
		 *	What is on screen stays where it holds something not yet saved - a
		 *	password half typed, a page ticked - since looking at another panel
		 *	is no reason to throw that away; and it stays when the answer is
		 *	none, with nothing said, since what is there is still true as far
		 *	as it goes
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.protected.ready === false )
				return Nino.admin.protected.init();

			Nino.admin.protected.call( 'state', {}, function( status, response ) {

				if( status !== 200 || response === null || Nino.admin.protected._unsaved() === true )
					return;

				Nino.admin.protected._take( response );
				Nino.admin.protected._render();
			} );
		},

		/**
		 *	Whether the screen holds something that is not saved: a new
		 *	password typed into either field, or a page ticked or unticked
		 *	against what the server last said
		 *
		 *	@return		{boolean}
		 */
		_unsaved : function() {

			const typed = [ 'protected-newpw', 'protected-newpw2' ].some( function( id ) {
				const input = dc.getElementById( id );
				return input !== null && input.value !== '';
			} );

			if( typed === true )
				return true;

			const state = Nino.admin.protected._state;
			const chosen = Nino.admin.protected._chosen;

			return state !== null && ( state.pages || [] ).some( function( page ) { return ( chosen[ page.uri ] === true ) !== ( page.selected === true ) } );
		},

		/**
		 *	Adopt a state from the server, and tick what it says is protected
		 *
		 *	@param		{Object}	state
		 *
		 *	@return		void
		 */
		_take : function( state ) {

			Nino.admin.protected._state = state;
			Nino.admin.protected._chosen = {};

			( state.pages || [] ).forEach( function( page ) {
				Nino.admin.protected._chosen[ page.uri ] = page.selected === true;
			} );
		},

		/**
		 *	What a row of the list says: the page's title, or its first path
		 *	where no text has one, and beside it the paths - every language
		 *	variant of the page - and whether a wider path already covers it
		 *
		 *	@param		{Object}	page			One entry of the state's pages
		 *
		 *	@return		{{name: string, state: string}}
		 */
		_rowText : function( page ) {

			const paths = page.paths || [];
			const title = String( page.title || '' );
			const parts = ( title === '' ? paths.slice( 1 ) : paths ).slice();

			if( page.covered === true && page.selected !== true )
				parts.push( Nino.content.getText('/_admin/protected/label/covered') );

			return { name : title === '' ? String( paths[0] || page.uri ) : title, state : parts.join(', ') };
		},

		/**
		 *	The paths to post: every address of every ticked page. A page covered
		 *	by a wider path is not ticked and not posted - the server keeps
		 *	what it does not list, and what it lists it takes from this
		 *
		 *	@param		{Array}		pages			The state's pages
		 *	@param		{Object}	chosen		uri => ticked
		 *
		 *	@return		{Array}
		 */
		_collect : function( pages, chosen ) {

			const paths = [];

			( pages || [] ).forEach( function( page ) {
				if( chosen[ page.uri ] === true )
					( page.paths || [] ).forEach( function( path ) { paths.push( path ) } );
			} );

			return paths;
		},

		/**
		 *	What is wrong with a new password, before the server is asked - the
		 *	server holds the length again, which is the check that counts
		 *
		 *	@param		{string}	pw
		 *	@param		{string}	again
		 *	@param		{number}	min
		 *
		 *	@return		{string}					'', 'short' or 'mismatch'
		 */
		_pwProblem : function( pw, again, min ) {

			// Counted in characters, as the server counts them (mb_strlen), not in
			// UTF-16 units: a character outside the BMP is one here as well
			if( Array.from( pw ).length < min )
				return 'short';

			return pw === again ? '' : 'mismatch';
		},

		/**
		 *	The live line of a section: where it says that the section is saving,
		 *	saved or refused, with its own words for what was saved. Its element
		 *	is the one the section puts in its row, see _actions()
		 *
		 *	@param		{string}	id					Of the line's element
		 *	@param		{string}	saved				The fill key of its words for "saved", if the section has some of its own
		 *
		 *	@return		{Object}							See Nino.adminUi.status()
		 */
		_line : function( id, saved ) {

			const el = Nino.adminUi.el( 'span' );
			el.id = id;

			return Nino.adminUi.status( el, { saved : Nino.content.getText( saved ) } );
		},

		/**
		 *	The row of a section where its button stands, and its live line after
		 *	it. Not the workbench's fixed action bar: that one is the screen's, and
		 *	this screen has three sections with a button each - three of them would
		 *	lie over each other at its foot, and only the last could be clicked
		 *
		 *	@param		{Element}		button
		 *	@param		{Object}		line				The section's live line, see _line()
		 *
		 *	@return		{Element}
		 */
		_actions : function( button, line ) {

			const row = Nino.adminUi.el( 'div', 'nino-admin-actions' );
			row.appendChild( button );
			row.appendChild( line.element );

			return row;
		},

		/**
		 *	One fieldset of the screen: the legend, and its parts after it
		 *
		 *	@param		{string}		legendKey		Fill key
		 *
		 *	@return		{Element}
		 */
		_section : function( legendKey ) {

			const box = Nino.adminUi.el( 'fieldset' );
			box.appendChild( Nino.adminUi.el( 'legend', '', Nino.content.getText( legendKey ) ) );

			return box;
		},

		/**
		 *	The screen: the password, the pages, signing out
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = Nino.admin.protected.mount();
			const state = Nino.admin.protected._state;

			if( wrap === null || state === null )
				return;

			wrap.innerHTML = '';

			// No heading of its own: the head the shell renders over the pane
			// names the panel, and a second name under it would only say it again
			wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/protected/hint/intro') ) );
			wrap.appendChild( Nino.admin.protected._renderPassword( state ) );
			wrap.appendChild( Nino.admin.protected._renderPages( state ) );
			wrap.appendChild( Nino.admin.protected._renderSignOut() );
		},

		/**
		 *	The password: whether there is one, two fields for a new one, and
		 *	the note that a new one signs everybody out. A form, so Enter sends
		 *	it and a password manager knows what it is looking at
		 *
		 *	@param		{Object}	state
		 *
		 *	@return		{Element}
		 */
		_renderPassword : function( state ) {

			const box = Nino.admin.protected._section('/_admin/protected/label/password');

			box.appendChild( Nino.adminUi.hint( Nino.content.getText( state.hasPassword === true ? '/_admin/protected/msg/haspw' : '/_admin/protected/msg/nopw' ) ) );
			box.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/protected/hint/password'), state.minLength ) ) );

			const form = Nino.adminUi.el( 'form' );

			const fields = [ [ 'newpw', '/_admin/protected/label/newpw' ], [ 'newpw2', '/_admin/protected/label/newpw2' ] ].map( function( def ) {

				const field = Nino.adminUi.textField( {
					type				: 'password',
					id					: 'protected-'+ def[0],
					autocomplete: 'new-password',
					required		: true,
					minLength		: state.minLength,
					maxLength		: state.maxLength,
					label				: Nino.content.getText( def[1] ),
				} );

				form.appendChild( field );

				return field.querySelector('input');
			} );

			// The first is the one that is sent, as "pw": the field the server names
			// when it refuses it
			fields[0].name = 'pw';

			const line = Nino.admin.protected._line( 'protected-pw-msg', '/_admin/protected/msg/pwsaved' );
			Nino.admin.protected._lines.password = line;

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/protected/label/setpw'), null, { type : 'submit', id : 'protected-setpw', className : 'nino-admin-btn-primary' } );

			form.appendChild( Nino.admin.protected._actions( save, line ) );

			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				Nino.admin.protected._savePassword( fields[0], fields[1], save );
			} );

			box.appendChild( form );

			return box;
		},

		/**
		 *	Set the new password and say what happened. The answer is the state
		 *	again, with a password now, and the screen is drawn from it - the
		 *	two fields empty, which is the point
		 *
		 *	@param		{Element}		first
		 *	@param		{Element}		second
		 *	@param		{Element}		save
		 *
		 *	@return		void
		 */
		_savePassword : function( first, second, save ) {

			const line = Nino.admin.protected._lines.password;
			const problem = Nino.admin.protected._pwProblem( first.value, second.value, Nino.admin.protected._state.minLength );

			if( problem === 'short' )
				return line.fail( Nino.adminUi.format( Nino.content.getText('/_admin/error/protected_short'), Nino.admin.protected._state.minLength ) );

			if( problem === 'mismatch' )
				return line.fail( Nino.content.getText('/_admin/protected/error/mismatch') );

			save.disabled = true;
			line.saving();

			Nino.admin.protected.call( 'password', { pw : first.value }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null )
					return line.error( status, response, '/_admin/common/error/save' );

				// A new password changes nothing about the pages: what was ticked
				// and not yet saved is still ticked
				const ticked = Nino.admin.protected._chosen;
				Nino.admin.protected._take( response );
				Nino.admin.protected._chosen = ticked;
				Nino.admin.protected._render();
				Nino.admin.protected._lines.password.saved();
			} );
		},

		/**
		 *	The pages: one row each, ticked where the server says it is protected
		 *
		 *	@param		{Object}	state
		 *
		 *	@return		{Element}
		 */
		_renderPages : function( state ) {

			const box = Nino.admin.protected._section('/_admin/protected/label/pages');
			box.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/protected/hint/pages') ) );

			if( ( state.pages || [] ).length === 0 ) {
				box.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/protected/empty/pages') ) );
				return box;
			}

			const list = Nino.adminUi.el( 'div', 'nino-admin-checklist' );

			state.pages.forEach( function( page ) {

				const row = Nino.admin.protected._rowText( page );
				const covered = page.covered === true && page.selected !== true;

				const label = dc.createElement('label');

				const input = dc.createElement('input');
				input.type = 'checkbox';
				input.checked = covered === true || Nino.admin.protected._chosen[ page.uri ] === true;
				// Covered by a wider path: protected already, and nothing to tick
				input.disabled = covered;
				input.addEventListener( 'change', function() {
					Nino.admin.protected._chosen[ page.uri ] = input.checked;
				} );
				label.appendChild( input );

				label.appendChild( Nino.adminUi.el( 'span', '', row.name ) );
				label.appendChild( Nino.adminUi.el( 'small', 'nino-admin-checklist-state', row.state ) );

				list.appendChild( label );
			} );

			box.appendChild( list );

			// What the list cannot name, set by hand in the Features panel: said,
			// so the screen does not seem to protect less than it does, and kept
			if( ( state.extra || [] ).length > 0 )
				box.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/protected/label/extra'), state.extra.join(', ') ) ) );

			const line = Nino.admin.protected._line( 'protected-pages-msg', '/_admin/protected/msg/pagessaved' );
			Nino.admin.protected._lines.pages = line;

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/protected/label/savepages'), function() { Nino.admin.protected._savePages( save ) }, { id : 'protected-savepages', className : 'nino-admin-btn-primary' } );

			box.appendChild( Nino.admin.protected._actions( save, line ) );

			return box;
		},

		/**
		 *	Save the choice. Nothing chosen is accepted, but only after asking
		 *	- it switches the protection of every page in the list off
		 *
		 *	@param		{Element}		save
		 *
		 *	@return		void
		 */
		_savePages : function( save ) {

			const line = Nino.admin.protected._lines.pages;
			const state = Nino.admin.protected._state;
			const paths = Nino.admin.protected._collect( state.pages, Nino.admin.protected._chosen );

			const wasProtected = state.pages.some( function( page ) { return page.selected === true } );

			if( paths.length === 0 && wasProtected === true && wn.confirm( Nino.content.getText('/_admin/protected/confirm/none') ) === false )
				return;

			save.disabled = true;
			line.saving();

			Nino.admin.protected.call( 'pages', { paths : paths }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null )
					return line.error( status, response, '/_admin/common/error/save' );

				Nino.admin.protected._take( response );
				Nino.admin.protected._render();
				Nino.admin.protected._lines.pages.saved();
			} );
		},

		/**
		 *	Sign everybody out: one button, asked about first
		 *
		 *	@return		{Element}
		 */
		_renderSignOut : function() {

			const box = Nino.admin.protected._section('/_admin/protected/label/signout');
			box.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/protected/hint/signout') ) );

			const line = Nino.admin.protected._line( 'protected-signout-msg', '/_admin/protected/msg/signedout' );
			Nino.admin.protected._lines.signout = line;

			const out = Nino.adminUi.button( Nino.content.getText('/_admin/protected/label/signoutall'), function() {

				if( wn.confirm( Nino.content.getText('/_admin/protected/confirm/signout') ) === false )
					return;

				out.disabled = true;

				Nino.admin.protected.call( 'signout', {}, function( status, response ) {

					out.disabled = false;

					if( status !== 200 || response === null )
						return line.error( status, response, '/_admin/common/error/save' );

					line.saved();
				} );
			}, { id : 'protected-signout', className : 'nino-admin-btn nino-admin-btn-danger' } );

			box.appendChild( Nino.admin.protected._actions( out, line ) );

			return box;
		},
	} );

} )(window, document, document.documentElement, document.body);
