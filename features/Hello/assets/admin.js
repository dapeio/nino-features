/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Hello					The feature's /_admin panel, "Hello World": one field
 *												and one button, written to be copied. Ships with the
 *												feature and is loaded exactly while the panel is.
 *
 *												The contract with the workbench is two things. The
 *												object attaches to Nino.admin.<panel name> - the name
 *												Admin::nav() gave - and answers showCurrent() when its
 *												tab is selected. Everything else is this file's own
 *												business.
 *
 *												Three rules hold everywhere in a panel:
 *
 *													- Never a string on screen that is not a text fill.
 *														Nino.content.getText() reads text/<locale>.php
 *														beside this file, so the panel speaks whatever
 *														the workbench is set to.
 *													- Never innerHTML with anything a person typed.
 *														createElement and textContent, always.
 *													- The screen validates to be kind; the server
 *														validates to be right. Both, never one.
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.hello = {

		// Whether the first load has happened, and what it answered. A panel
		// keeps its own state: switching away and back never reloads
		_ready	: false,
		_data		: null,
		// The status line of the screen on show - drawn again with the screen
		_line		: null,

		/**
		 *	Load what the screen shows, then draw
		 *
		 *	@param		{Function}	[then]			Run once the screen is back
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('hello-form');

			// The pane is only in the document while this panel is installed
			// and the account may see it - so this is a real check, not a
			// formality
			if( wrap === null )
				return;

			Nino.admin.hello._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.hello._showError( wrap, status, response );

				Nino.admin.hello._data	= response;
				Nino.admin.hello._ready	= true;
				Nino.admin.hello._render();

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	What the workbench calls when this panel's tab is selected. First
		 *	time: load. After that: redraw what is already here
		 *
		 *	@return		void
		 */
		showCurrent : function() {
			if( Nino.admin.hello._ready === false )
				return Nino.admin.hello.init();

			// A screen drawn again over a name nobody has saved would throw
			// it away - the page keeps what is typed while another panel is
			// open
			if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty.isDirty( [ 'hello' ] ) === true )
				return;

			Nino.admin.hello._render();
		},

		/**
		 *	One action of this panel. Every panel's call looks like this: one
		 *	POST to /_admin/ with the action name and the payload as json
		 *
		 *	The workbench's own request helper does the posting where this Nino
		 *	has one: it knows the project's directory and what to do when the
		 *	page has outlived its session. The post below is what every panel
		 *	did before it - its base is the literal the asset bundle fills in,
		 *	because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint		The half after 'hello/'
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		( status, parsed json or null )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'hello/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'hello/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the server's code in the workbench's
		 *	language, then its own message, then this panel's sentence - where
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

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.hello._errorText( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	The line that says whether the screen is saved. Where this Nino has
		 *	Nino.adminUi.status() it is that: "saving", "saved at 09:41", the
		 *	"unsaved changes" a keystroke brings, or why it failed - in the
		 *	workbench's own words. Before it, the same calls write this
		 *	panel's sentences into the paragraph
		 *
		 *	@param		{Element}		msg
		 *	@param		{Element}		form				What is typed into it turns "saved" into "unsaved changes"
		 *	@param		{Function}	isDirty			Whether what is typed differs from what is saved
		 *
		 *	@return		{Object}						{ saving(), saved(), fail( text ), error( status, response, key ) }
		 */
		_status : function( msg, form, isDirty ) {

			if( Nino.adminUi && typeof Nino.adminUi.status === 'function' ) {
				const line = Nino.adminUi.status( msg );
				line.bind( form, isDirty );
				return line;
			}

			return {
				saving : function() { msg.className = ''; msg.textContent = Nino.content.getText('/_admin/common/msg/saving') },
				saved	 : function() { msg.className = ''; msg.textContent = Nino.content.getText('/_admin/hello/msg/saved') },
				fail	 : function( text ) { msg.className = 'nino-admin-error'; msg.textContent = text },
				error	 : function( status, response, key ) { this.fail( Nino.admin.hello._errorText( status, response, key ) ) },
			};
		},

		/**
		 *	The screen
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = dc.getElementById('hello-form');
			const data = Nino.admin.hello._data;

			if( wrap === null || data === null )
				return;

			wrap.innerHTML = '';

			/*	No heading: the workbench opens every pane with a head of its
				own - the label nav() gave, room beside it for a strip of tabs
				and at its end for buttons - and a panel reaches it through
				Nino.adminUi.panelHead() when it has something to put there.
				So a screen starts with what it is about, and a heading of its
				own would only say the panel's name a second time. A form one
				level down, the kind a list opens, is where a heading belongs	*/
			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/hello/hint');
			wrap.appendChild( hint );

			/*	The field, in the workbench's own shape: the field *is* a
				<label>, its name is a <span> inside it, and the control carries
				.nino-admin-input. That is what Nino.adminUi.selectField() builds,
				and matching it is what makes a panel look like the rest of the
				workbench without copying a single colour - the label needs no
				"for" because the control is inside it.

				The stored value goes on .value, never into markup: it came out
				of a form and is a string somebody typed	*/
			const field = dc.createElement('label');
			field.className = 'nino-admin-field hello-field';

			const name = dc.createElement('span');
			name.textContent = Nino.content.getText('/_admin/hello/label/name');
			field.appendChild( name );

			const input = dc.createElement('input');
			input.type = 'text';
			input.id = 'hello-name';
			input.className = 'nino-admin-input';
			// Kind, not right: the same limit is checked again on the server,
			// which is the check that counts
			input.maxLength = 60;
			input.value = String( data.name || '' );
			input.placeholder = String( data.fallback || '' );
			field.appendChild( input );

			wrap.appendChild( field );

			/*	What the two halves add up to, shown rather than described: the
				greeting is a setting of the Features panel and the name is this
				screen's, and a person changing one wants to see the other	*/
			const preview = dc.createElement('p');
			preview.className = 'hello-preview';
			preview.id = 'hello-preview';
			wrap.appendChild( preview );

			const paint = function() {
				const name = input.value.trim() === '' ? String( data.fallback || '' ) : input.value.trim();
				preview.textContent = String( data.greeting || '' )+ ', '+ name + '!';
			};

			input.addEventListener( 'input', paint );
			paint();

			// The message the save writes into, and the button that writes it
			const msg = dc.createElement('p');
			msg.id = 'hello-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const save = dc.createElement('button');
			save.type = 'button';
			save.id = 'hello-save';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/hello/label/save');
			// Typing the name back to what is saved is not a change: the line
			// is told, and so is the shell, which asks before a log out or a
			// language change would lose it (see the end of this file). It
			// follows the field, which is drawn again with the screen - the
			// wrap outlives every draw and would collect a listener per draw
			const line = Nino.admin.hello._status( msg, field, function() { return input.value !== String( data.name || '' ) } );
			Nino.admin.hello._line = line;
			save.addEventListener( 'click', function() { Nino.admin.hello._save( input, save, line ) } );

			const actions = dc.createElement('div');
			actions.appendChild( save );
			actions.appendChild( msg );

			// actionBar() is the workbench's own footer for a screen's buttons,
			// so every panel's sit in the same place
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );

			// What is on screen now is what is saved
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.snapshot('hello');
		},

		/**
		 *	Save, and say what happened
		 *
		 *	@param		{HTMLElement}	input
		 *	@param		{HTMLElement}	btn
		 *	@param		{Object}			line				_status()'s answer
		 *	@param		{Function}		[done]			Told whether it was saved - the shell's Save asks
		 *
		 *	@return		void
		 */
		_save : function( input, btn, line, done ) {

			const name = input.value.trim();
			const finish = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			// A second click while one is on its way
			if( btn.disabled === true )
				return finish( false );

			// Kind, not right: the server checks the same thing, and that is
			// the check that counts
			if( name.length > 60 ) {
				line.fail( Nino.content.getText('/_admin/hello/error/long') );
				input.focus();
				return finish( false );
			}

			btn.disabled = true;
			line.saving();

			Nino.admin.hello._apiCall( 'save', { name : name }, function( status, response ) {

				btn.disabled = false;

				if( status !== 200 || response === null ) {
					line.error( status, response, '/_admin/hello/error/save' );
					return finish( false );
				}

				// Redrawn from what the save answered, not from what was typed:
				// the server is what decided, including the fallback it applied
				Nino.admin.hello._data.name = response.name;
				Nino.admin.hello._render();
				Nino.admin.hello._line.saved();

				finish( true );
			} );
		},
	};

	/*	The shell asks Save, Discard or Cancel before a log out or a language
		change would lose a name nobody has saved, and a reload gets the
		browser's own question. A panel registers where this Nino has the
		registry and does without where it has not - panel scripts run on
		older workbenches too	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.watchForm( 'hello', function() { return dc.getElementById('hello-form') }, function( done ) {

			const input = dc.getElementById('hello-name');
			const save = dc.getElementById('hello-save');

			if( input === null || save === null )
				return done( false );

			Nino.admin.hello._save( input, save, Nino.admin.hello._line, done );
		} );

})(window, document, document.documentElement, document.body);
