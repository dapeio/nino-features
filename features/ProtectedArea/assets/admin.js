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

	Nino.admin.protected = {

		_ready	: false,
		// What protected/state answered: hasPassword, pages, extra, minLength, maxLength
		_state	: null,
		// The pages ticked on screen, by internal uri - a working copy: leaving
		// without saving changes nothing
		_chosen	: {},

		/**
		 *	Load the state and draw the screen
		 *
		 *	@return		void
		 */
		init : function() {

			const wrap = dc.getElementById('protected-form');
			if( wrap === null )
				return;

			Nino.admin.protected._apiCall( 'state', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.protected._showError( wrap, status, response );

				Nino.admin.protected._take( response );
				Nino.admin.protected._ready = true;
				Nino.admin.protected._render();
			} );
		},

		/**
		 *	The shell calls this when the panel is opened. What is on screen
		 *	stays - a password half typed is not something to throw away by
		 *	looking at another panel
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.protected._ready === false )
				Nino.admin.protected.init();
		},

		/**
		 *	Call a protected/* admin action
		 *
		 *	@param		{string}		endpoint			Action name (eg. "pages", becomes "protected/pages")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'protected/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	Show a failed request's status/error in a container
		 *
		 *	@param		{Element}		container
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		void
		 */
		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.protected._failure( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	"(status) what the server said", or the fill when it said nothing
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		fallback			Fill key
		 *
		 *	@return		{string}
		 */
		_failure : function( status, response, fallback ) {
			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( fallback ) );
		},

		/**
		 *	A text fill with its one placeholder filled in. A function as the
		 *	replacement, so a "$&" in a path is text and not a pattern
		 *
		 *	@param		{string}	key
		 *	@param		{*}				value
		 *
		 *	@return		{string}
		 */
		_say : function( key, value ) {
			return Nino.content.getText( key ).replace( /%[sd]/, function() { return String( value ) } );
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

			if( pw.length < min )
				return 'short';

			return pw === again ? '' : 'mismatch';
		},

		/**
		 *	A paragraph that says what happened, politely, to a screen reader as
		 *	well
		 *
		 *	@param		{string}	id
		 *
		 *	@return		{Element}
		 */
		_message : function( id ) {
			const msg = dc.createElement('p');
			msg.id = id;
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'aria-live', 'polite' );
			return msg;
		},

		/**
		 *	Write into a message line, as a failure or as the plain note it is
		 *
		 *	@param		{string}	id
		 *	@param		{string}	text
		 *	@param		{boolean}	failed
		 *
		 *	@return		void
		 */
		_report : function( id, text, failed ) {

			const msg = dc.getElementById( id );
			if( msg === null )
				return;

			msg.classList.toggle( 'nino-admin-error', failed === true );
			msg.textContent = text;
		},

		/**
		 *	One fieldset of the screen: the legend, and its parts after it
		 *
		 *	@param		{string}		legendKey		Fill key
		 *
		 *	@return		{Element}
		 */
		_section : function( legendKey ) {

			const box = dc.createElement('fieldset');
			const legend = dc.createElement('legend');
			legend.textContent = Nino.content.getText( legendKey );
			box.appendChild( legend );

			return box;
		},

		/**
		 *	One note under a legend
		 *
		 *	@param		{string}	text
		 *
		 *	@return		{Element}
		 */
		_hint : function( text ) {
			const p = dc.createElement('p');
			p.className = 'nino-admin-hint';
			p.textContent = text;
			return p;
		},

		/**
		 *	The screen: the password, the pages, signing out
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = dc.getElementById('protected-form');
			const state = Nino.admin.protected._state;

			if( wrap === null || state === null )
				return;

			wrap.innerHTML = '';

			// No heading of its own: the head the shell renders over the pane
			// names the panel, and a second name under it would only say it again
			wrap.appendChild( Nino.admin.protected._hint( Nino.content.getText('/_admin/protected/hint/intro') ) );
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

			const status = Nino.admin.protected._hint( Nino.content.getText( state.hasPassword === true ? '/_admin/protected/msg/haspw' : '/_admin/protected/msg/nopw' ) );
			box.appendChild( status );
			box.appendChild( Nino.admin.protected._hint( Nino.admin.protected._say('/_admin/protected/hint/password', state.minLength ) ) );

			const form = dc.createElement('form');

			const fields = [];

			[ [ 'newpw', '/_admin/protected/label/newpw' ], [ 'newpw2', '/_admin/protected/label/newpw2' ] ].forEach( function( def ) {

				const field = dc.createElement('label');
				field.className = 'nino-admin-field';

				const name = dc.createElement('span');
				name.textContent = Nino.content.getText( def[1] );
				field.appendChild( name );

				const input = dc.createElement('input');
				input.type = 'password';
				input.id = 'protected-'+ def[0];
				input.className = 'nino-admin-input';
				input.autocomplete = 'new-password';
				input.required = true;
				input.minLength = state.minLength;
				input.maxLength = state.maxLength;
				field.appendChild( input );

				fields.push( input );
				form.appendChild( field );
			} );

			const save = dc.createElement('button');
			save.type = 'submit';
			save.id = 'protected-setpw';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/protected/label/setpw');

			const actions = dc.createElement('div');
			actions.appendChild( save );
			form.appendChild( Nino.adminUi.actionBar( actions ) );
			form.appendChild( Nino.admin.protected._message('protected-pw-msg') );

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

			const problem = Nino.admin.protected._pwProblem( first.value, second.value, Nino.admin.protected._state.minLength );

			if( problem === 'short' )
				return Nino.admin.protected._report( 'protected-pw-msg', Nino.admin.protected._say('/_admin/protected/error/short', Nino.admin.protected._state.minLength ), true );

			if( problem === 'mismatch' )
				return Nino.admin.protected._report( 'protected-pw-msg', Nino.content.getText('/_admin/protected/error/mismatch'), true );

			save.disabled = true;
			Nino.admin.protected._report( 'protected-pw-msg', Nino.content.getText('/_admin/common/msg/saving'), false );

			Nino.admin.protected._apiCall( 'password', { pw : first.value }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null )
					return Nino.admin.protected._report( 'protected-pw-msg', Nino.admin.protected._failure( status, response, '/_admin/common/error/save' ), true );

				// A new password changes nothing about the pages: what was ticked
				// and not yet saved is still ticked
				const ticked = Nino.admin.protected._chosen;
				Nino.admin.protected._take( response );
				Nino.admin.protected._chosen = ticked;
				Nino.admin.protected._render();
				Nino.admin.protected._report( 'protected-pw-msg', Nino.content.getText('/_admin/protected/msg/pwsaved'), false );
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
			box.appendChild( Nino.admin.protected._hint( Nino.content.getText('/_admin/protected/hint/pages') ) );

			if( ( state.pages || [] ).length === 0 ) {
				box.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/protected/empty/pages') ) );
				return box;
			}

			const list = dc.createElement('div');
			list.className = 'nino-admin-checklist';

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

				const name = dc.createElement('span');
				name.textContent = row.name;
				label.appendChild( name );

				const paths = dc.createElement('small');
				paths.className = 'nino-admin-checklist-state';
				paths.textContent = row.state;
				label.appendChild( paths );

				list.appendChild( label );
			} );

			box.appendChild( list );

			// What the list cannot name, set by hand in the Features panel: said,
			// so the screen does not seem to protect less than it does, and kept
			if( ( state.extra || [] ).length > 0 )
				box.appendChild( Nino.admin.protected._hint( Nino.admin.protected._say('/_admin/protected/label/extra', state.extra.join(', ') ) ) );

			const save = dc.createElement('button');
			save.type = 'button';
			save.id = 'protected-savepages';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/protected/label/savepages');
			save.addEventListener( 'click', function() { Nino.admin.protected._savePages( save ) } );

			const actions = dc.createElement('div');
			actions.appendChild( save );
			box.appendChild( Nino.adminUi.actionBar( actions ) );
			box.appendChild( Nino.admin.protected._message('protected-pages-msg') );

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

			const state = Nino.admin.protected._state;
			const paths = Nino.admin.protected._collect( state.pages, Nino.admin.protected._chosen );

			const wasProtected = state.pages.some( function( page ) { return page.selected === true } );

			if( paths.length === 0 && wasProtected === true && wn.confirm( Nino.content.getText('/_admin/protected/confirm/none') ) === false )
				return;

			save.disabled = true;
			Nino.admin.protected._report( 'protected-pages-msg', Nino.content.getText('/_admin/common/msg/saving'), false );

			Nino.admin.protected._apiCall( 'pages', { paths : paths }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null )
					return Nino.admin.protected._report( 'protected-pages-msg', Nino.admin.protected._failure( status, response, '/_admin/common/error/save' ), true );

				Nino.admin.protected._take( response );
				Nino.admin.protected._render();
				Nino.admin.protected._report( 'protected-pages-msg', Nino.content.getText('/_admin/protected/msg/pagessaved'), false );
			} );
		},

		/**
		 *	Sign everybody out: one button, asked about first
		 *
		 *	@return		{Element}
		 */
		_renderSignOut : function() {

			const box = Nino.admin.protected._section('/_admin/protected/label/signout');
			box.appendChild( Nino.admin.protected._hint( Nino.content.getText('/_admin/protected/hint/signout') ) );

			const out = dc.createElement('button');
			out.type = 'button';
			out.id = 'protected-signout';
			out.className = 'nino-admin-btn nino-admin-btn-danger';
			out.textContent = Nino.content.getText('/_admin/protected/label/signoutall');
			out.addEventListener( 'click', function() {

				if( wn.confirm( Nino.content.getText('/_admin/protected/confirm/signout') ) === false )
					return;

				out.disabled = true;

				Nino.admin.protected._apiCall( 'signout', {}, function( status, response ) {

					out.disabled = false;

					if( status !== 200 || response === null )
						return Nino.admin.protected._report( 'protected-signout-msg', Nino.admin.protected._failure( status, response, '/_admin/common/error/save' ), true );

					Nino.admin.protected._report( 'protected-signout-msg', Nino.content.getText('/_admin/protected/msg/signedout'), false );
				} );
			} );

			const actions = dc.createElement('div');
			actions.appendChild( out );
			box.appendChild( Nino.adminUi.actionBar( actions ) );
			box.appendChild( Nino.admin.protected._message('protected-signout-msg') );

			return box;
		},
	};

} )(window, document, document.documentElement, document.body);
