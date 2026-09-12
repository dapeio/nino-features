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
			Nino.admin.hello._render();
		},

		/**
		 *	One action of this panel. Every panel's call looks like this: one
		 *	POST to /_admin/ with the action name and the payload as json
		 *
		 *	@param		{string}		endpoint		The half after 'hello/'
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		( status, parsed json or null )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'hello/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/load') );
			container.appendChild( p );
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

			const heading = dc.createElement('h2');
			heading.textContent = Nino.content.getText('/_admin/hello/title');
			wrap.appendChild( heading );

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
			save.addEventListener( 'click', function() { Nino.admin.hello._save( input, save, msg ) } );

			const actions = dc.createElement('div');
			actions.appendChild( save );

			// actionBar() is the workbench's own footer for a screen's buttons,
			// so every panel's sit in the same place
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );
			wrap.appendChild( msg );
		},

		/**
		 *	Save, and say what happened
		 *
		 *	@param		{HTMLElement}	input
		 *	@param		{HTMLElement}	btn
		 *	@param		{HTMLElement}	msg
		 *
		 *	@return		void
		 */
		_save : function( input, btn, msg ) {

			const name = input.value.trim();

			msg.className = '';
			msg.textContent = '';

			// Kind, not right: the server checks the same thing, and that is
			// the check that counts
			if( name.length > 60 ) {
				msg.className = 'nino-admin-error';
				msg.textContent = Nino.content.getText('/_admin/hello/error/long');
				input.focus();
				return;
			}

			btn.disabled = true;

			Nino.admin.hello._apiCall( 'save', { name : name }, function( status, response ) {

				btn.disabled = false;

				if( status !== 200 || response === null ) {
					msg.className = 'nino-admin-error';
					msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/hello/error/save') );
					return;
				}

				// Redrawn from what the save answered, not from what was typed:
				// the server is what decided, including the fallback it applied
				Nino.admin.hello._data.name = response.name;
				Nino.admin.hello._render();

				const back = dc.getElementById('hello-msg');
				if( back !== null )
					back.textContent = Nino.content.getText('/_admin/hello/msg/saved');
			} );
		},
	};

})(window, document, document.documentElement, document.body);
