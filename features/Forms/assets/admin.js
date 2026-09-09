/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Forms						The panel of the Forms feature: the forms a project has
 *													defined, and one form's fields on a screen of its
 *													own - two levels in two panes, stepped through with
 *													the workbench's own back link. The submissions are
 *													the kernel's own Submissions panel, which reads the
 *													same definitions and needs nothing from here.
 *
 *													Admin/Admin.php beside it does the reading and
 *													writing and hands every word over already in the
 *													session language, so this file only lays out what
 *													it gets.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.forms = {

		_ready		: false,
		_forms		: [],
		// The field types Forms::TYPES declares - the panel offers exactly
		// what the endpoint accepts, so the two cannot drift apart
		_types		: [],
		// The field names a form may not take (\Nino\Form::RESERVED), so the
		// editor can say so before a save is refused for it
		_reserved	: [],
		// True while the project has defined no forms of its own: the list is
		// showing the contact form the kernel falls back to
		_default	: false,
		// False while the kernel module that owns POST /.form is switched
		// off - the one state in which a form drawn here posts into nothing
		_endpoint	: true,
		// How long submissions are kept and whether they are kept at all -
		// the kernel's own two keys, edited on the list screen
		_retention: 3,
		_store		: true,
		// The form being edited - a working copy, so leaving the screen
		// without saving changes nothing. null while the list is on screen
		_editing	: null,

		/**
		 *	Load the forms and draw whichever level is current
		 *
		 *	@param		{Function}	[then]			Run once the list is back
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('forms-list');
			if( wrap === null )
				return;

			Nino.admin.forms._apiCall( 'list', {}, function( status, response ) {
				if( status !== 200 || response === null )
					return Nino.admin.forms._showError( wrap, status, response );

				Nino.admin.forms._forms			= response.forms || [];
				Nino.admin.forms._types			= response.types || [];
				Nino.admin.forms._reserved	= response.reserved || [];
				Nino.admin.forms._default		= response.default === true;
				Nino.admin.forms._endpoint	= response.endpoint !== false;
				Nino.admin.forms._retention	= response.retention || 3;
				Nino.admin.forms._store			= response.store !== false;
				Nino.admin.forms._renderList();
				Nino.admin.forms._ready = true;

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	Re-show whichever level is on - the shell calls this when the
		 *	panel is opened again
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.forms._ready === false )
				return Nino.admin.forms.init();

			if( Nino.admin.forms._editing !== null )
				return Nino.admin.forms._showForm( Nino.admin.forms._editing );

			Nino.admin.forms._showList();
		},

		/**
		 *	Call a forms/* action
		 *
		 *	@param		{string}		endpoint			Action name (eg. "list", becomes "forms/list")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'forms/'+ endpoint, data : JSON.stringify( payload ) } );
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
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/load') );
			container.appendChild( p );
		},

		/**
		 *	Which of the two panes is on screen
		 *
		 *	@param		{string}	level				'list' or 'form'
		 *
		 *	@return		void
		 */
		_level : function( level ) {
			[ 'list', 'form' ].forEach( function( name ) {
				dc.getElementById('forms-'+ name ).classList.toggle( 'admin-hidden', name !== level );
			} );
		},

		/**
		 *	The list: why nothing works while the kernel's contact form is
		 *	on, one card per form, and the action that adds one
		 *
		 *	@return		void
		 */
		_renderList : function() {

			const wrap = dc.getElementById('forms-list');
			wrap.innerHTML = '';

			Nino.admin.forms._editing = null;

			// A form that draws fine and posts to a 404 is the one failure this
			// feature could produce silently, so it is said here and in red
			if( Nino.admin.forms._endpoint === false ) {
				const off = dc.createElement('p');
				off.className = 'nino-admin-error';
				off.textContent = Nino.content.getText('/_admin/forms/hint/endpoint');
				wrap.appendChild( off );
			}

			if( Nino.admin.forms._default === true ) {
				const hint = dc.createElement('p');
				hint.className = 'nino-admin-hint';
				hint.textContent = Nino.content.getText('/_admin/forms/hint/default');
				wrap.appendChild( hint );
			}

			if( Nino.admin.forms._forms.length === 0 )
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/forms/hint/empty') ) );
			else
				Nino.admin.forms._forms.forEach( function( form ) { wrap.appendChild( Nino.admin.forms._renderCard( form ) ) } );

			const add = dc.createElement('button');
			add.type = 'button';
			add.className = 'nino-admin-btn-primary';
			add.textContent = Nino.content.getText('/_admin/forms/label/new');
			add.addEventListener( 'click', function() { Nino.admin.forms._showForm( Nino.admin.forms._blank() ) } );

			wrap.appendChild( Nino.adminUi.listActions( [ add ] ) );
			wrap.appendChild( Nino.admin.forms._renderSettings() );

			Nino.admin.forms._level('list');
		},

		/**
		 *	The two things about the submissions a project decides, under the
		 *	list: how long they are kept, and whether they are kept at all.
		 *	Both are the kernel's own config keys - it is the kernel that
		 *	writes the records - so a project that switches this feature off
		 *	keeps whatever was chosen here
		 *
		 *	@return		{Element}							<form>
		 */
		_renderSettings : function() {

			const el = dc.createElement('form');
			el.className = 'forms-settings';

			const legend = dc.createElement('h3');
			legend.textContent = Nino.content.getText('/_admin/forms/label/submissions-settings');
			el.appendChild( legend );

			// The label carries the unit itself: the shared one only knows the
			// units the workbench declares, and months is this panel's word
			el.appendChild( Nino.adminUi.numberField( {
				key : 'retention',
				label : Nino.content.getText('/_admin/forms/label/retention'),
				hint : Nino.content.getText('/_admin/forms/hint/retention'),
				value : Nino.admin.forms._retention,
				min : 1,
				max : 60,
			} ) );

			el.appendChild( Nino.adminUi.switchField( {
				key : 'store',
				label : Nino.content.getText('/_admin/forms/label/store'),
				hint : Nino.content.getText('/_admin/forms/hint/store'),
				checked : Nino.admin.forms._store,
			} ) );

			const actions = dc.createElement('div');
			actions.className = 'nino-admin-actionbar';

			const save = dc.createElement('button');
			save.type = 'submit';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/common/label/save');
			actions.appendChild( save );

			const msg = dc.createElement('p');
			msg.id = 'forms-settings-msg';
			actions.appendChild( msg );

			el.appendChild( actions );

			// The shared fields carry their name as data-key and are read back
			// through it, the way every generated field in the workbench is -
			// they hand back the label, not the control
			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				msg.textContent = Nino.content.getText('/_admin/common/msg/saving');

				const values = {};
				Array.prototype.slice.call( el.querySelectorAll('[data-key]') ).forEach( function( field ) {
					values[ field.dataset.key ] = field.type === 'checkbox' ? field.checked : field.value;
				} );

				Nino.admin.forms._apiCall( 'settings', {
					retention : parseInt( values.retention, 10 ),
					store : values.store === true,
				}, function( status, response ) {
					if( status !== 200 || response === null ) {
						msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
						return;
					}
					msg.textContent = Nino.content.getText('/_admin/common/msg/saved');
					Nino.admin.forms._retention	= response.retention;
					Nino.admin.forms._store			= response.store;
				} );
			} );

			return el;
		},

		/**
		 *	One form: its name, the shortcode that renders it, how many
		 *	fields it has, and the three things that can be done with it
		 *
		 *	@param		{Object}	form
		 *
		 *	@return		{Element}							<section>
		 */
		_renderCard : function( form ) {

			const card = dc.createElement('section');
			card.className = 'nino-admin-card';
			card.dataset.form = form.key;

			const title = dc.createElement('h3');
			title.textContent = form.name;
			card.appendChild( title );

			const shortcode = dc.createElement('p');
			shortcode.className = 'nino-admin-hint';
			shortcode.textContent = Nino.content.getText('/_admin/forms/hint/shortcode').replace( '%s', Nino.admin.forms._shortcode( form ) );
			card.appendChild( shortcode );

			const fields = dc.createElement('p');
			fields.className = 'nino-admin-hint';
			// What it collects, and what has come in through it. The
			// submissions themselves are the Submissions panel's - this is the
			// number that says which form is actually being used
			fields.textContent = [
				form.fields.map( function( field ) { return field.name } ).join( ', ' ),
				Nino.content.getText('/_admin/forms/label/entries').replace( '%s', form.entries ),
			].filter( Boolean ).join( ' \u00b7 ' );
			card.appendChild( fields );

			const actions = dc.createElement('div');
			actions.className = 'forms-actions';

			const edit = dc.createElement('button');
			edit.type = 'button';
			edit.className = 'nino-admin-btn-primary';
			edit.textContent = Nino.content.getText('/_admin/forms/label/edit');
			edit.addEventListener( 'click', function() { Nino.admin.forms._showForm( form ) } );
			actions.appendChild( edit );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/forms/label/delete');
			remove.addEventListener( 'click', function() { Nino.admin.forms._delete( form ) } );
			actions.appendChild( remove );

			card.appendChild( actions );

			return card;
		},

		/**
		 *	The shortcode that renders one form - without a key for the
		 *	first one defined, which is what a submission carrying no form
		 *	field belongs to
		 *
		 *	@param		{Object}	form
		 *
		 *	@return		{string}
		 */
		_shortcode : function( form ) {
			return '[form key="'+ form.key+ '"]';
		},

		/**
		 *	A new, empty form - one required text field, so it is a form
		 *	that could be saved as it stands
		 *
		 *	@return		{Object}
		 */
		_blank : function() {
			return {
				key : '', name : '', to : '', subject : '', confirm : false,
				ownerTemplate : '/templates/mail-owner',
				userTemplate : '/templates/mail-user',
				fields : [ { name : 'name', label : '', type : 'text', required : true, options : [] } ],
				entries : 0,
			};
		},

		/**
		 *	Delete one form, after a confirm prompt - its submissions stay,
		 *	which is what the prompt says
		 *
		 *	@param		{Object}	form
		 *
		 *	@return		void
		 */
		_delete : function( form ) {

			if( wn.confirm( Nino.content.getText('/_admin/forms/confirm/delete') ) === false )
				return;

			Nino.admin.forms._apiCall( 'delete', { key : form.key }, function( status, response ) {
				if( status !== 200 )
					return Nino.admin.forms._showError( dc.getElementById('forms-list'), status, response );

				Nino.admin.forms.init();
			} );
		},

		/**
		 *	Open the form editor on a working copy, so leaving it without
		 *	saving changes nothing that was loaded
		 *
		 *	@param		{Object}	form
		 *
		 *	@return		void
		 */
		_showForm : function( form ) {

			Nino.admin.forms._editing = JSON.parse( JSON.stringify( form ) );
			Nino.admin.forms._renderForm();
		},

		/**
		 *	The form editor: what the form is, where its mail goes, and its
		 *	fields - one row each, added and removed here rather than one
		 *	request at a time (the whole definition is saved as one)
		 *
		 *	@return		void
		 */
		_renderForm : function() {

			const wrap = dc.getElementById('forms-form');
			const form = Nino.admin.forms._editing;
			wrap.innerHTML = '';

			const backLink = dc.createElement('a');
			backLink.href = '#';
			backLink.className = 'nino-admin-back-link';
			backLink.textContent = Nino.content.getText('/_admin/common/label/back');
			backLink.addEventListener( 'click', function( ev ) { ev.preventDefault(); Nino.admin.forms._renderList() } );
			wrap.appendChild( Nino.admin.formToolbar( backLink ) );

			// The key it is replacing, so a rename stays one entry rather
			// than becoming a second form beside the old one
			const was = form.key;

			const el = dc.createElement('form');

			const about = dc.createElement('fieldset');
			const legend = dc.createElement('legend');
			legend.textContent = Nino.content.getText('/_admin/forms/label/form');
			about.appendChild( legend );

			// Every control carries what it is, so _collect() can read the whole
			// form back before a redraw - the editor is drawn again whenever a
			// field is added, removed or retyped, and without this everything
			// typed into these boxes would be redrawn from the copy it was
			// loaded with, ie. silently thrown away
			Nino.admin.forms._input( about, '/_admin/forms/label/name', 'text', form.name, '', 'name' );
			Nino.admin.forms._input( about, '/_admin/forms/label/key', 'text', form.key, '', 'key' ).required = true;
			Nino.admin.forms._input( about, '/_admin/forms/label/to', 'email', form.to, '/_admin/forms/hint/to', 'to' );
			Nino.admin.forms._input( about, '/_admin/forms/label/subject', 'text', form.subject, '/_admin/forms/hint/subject', 'subject' );

			const confirm = Nino.adminUi.switchField( {
				key 		: 'confirm',
				checked : form.confirm === true,
				label 	: Nino.content.getText('/_admin/forms/label/confirm'),
			} );
			confirm.dataset.about = 'confirm';
			about.appendChild( confirm );

			Nino.admin.forms._input( about, '/_admin/forms/label/ownertpl', 'text', form.ownerTemplate, '', 'ownertpl' );
			Nino.admin.forms._input( about, '/_admin/forms/label/usertpl', 'text', form.userTemplate, '/_admin/forms/hint/templates', 'usertpl' );

			el.appendChild( about );

			const fields = dc.createElement('fieldset');
			const fieldsLegend = dc.createElement('legend');
			fieldsLegend.textContent = Nino.content.getText('/_admin/forms/label/fields');
			fields.appendChild( fieldsLegend );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			// The names something else already owns (\Nino\Form::RESERVED), said
			// here rather than only in the refusal a save would come back with
			hint.textContent = Nino.content.getText('/_admin/forms/hint/fields').replace( '%s', Nino.admin.forms._reserved.join( ', ' ) );
			fields.appendChild( hint );

			const rows = dc.createElement('div');
			rows.id = 'forms-field-rows';
			fields.appendChild( rows );

			form.fields.forEach( function( field, index ) { rows.appendChild( Nino.admin.forms._renderFieldRow( field, index ) ) } );

			const add = dc.createElement('button');
			add.type = 'button';
			add.className = 'nino-admin-btn-secondary';
			add.textContent = Nino.content.getText('/_admin/forms/label/addfield');
			add.addEventListener( 'click', function() {
				Nino.admin.forms._collect();
				Nino.admin.forms._editing.fields.push( { name : '', label : '', type : 'text', required : false, options : [] } );
				Nino.admin.forms._renderForm();
			} );
			fields.appendChild( add );

			el.appendChild( fields );

			const actions = dc.createElement('div');
			actions.className = 'nino-admin-actionbar';

			const save = dc.createElement('button');
			save.type = 'submit';
			save.textContent = Nino.content.getText('/_admin/common/label/save');
			actions.appendChild( save );

			const msg = dc.createElement('p');
			msg.setAttribute( 'aria-live', 'polite' );
			actions.appendChild( msg );

			el.appendChild( actions );

			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				Nino.admin.forms._collect();
				Nino.admin.forms._save( was, Nino.admin.forms._editing, save, msg );
			} );

			wrap.appendChild( el );

			Nino.admin.forms._level('form');
		},

		/**
		 *	One labelled input in a fieldset, appended and handed back so
		 *	the submit handler can read it
		 *
		 *	@param		{Element}	parent
		 *	@param		{string}	label				Fill key
		 *	@param		{string}	type				Input type
		 *	@param		{string}	value
		 *	@param		{string}	hint				Fill key, '' for none
		 *	@param		{string}	role				What it is, for _collect()
		 *
		 *	@return		{Element}							The <input>
		 */
		_input : function( parent, label, type, value, hint, role ) {

			const wrap = dc.createElement('label');
			wrap.className = 'nino-admin-field';

			const span = dc.createElement('span');
			span.textContent = Nino.content.getText( label );
			wrap.appendChild( span );

			const input = dc.createElement('input');
			input.type = type;
			input.value = value === null || value === undefined ? '' : String( value );
			input.autocomplete = 'off';
			if( role !== undefined )
				input.dataset.about = role;
			wrap.appendChild( input );

			if( hint !== '' ) {
				const small = dc.createElement('small');
				small.className = 'nino-admin-hint';
				small.textContent = Nino.content.getText( hint );
				wrap.appendChild( small );
			}

			parent.appendChild( wrap );

			return input;
		},

		/**
		 *	One field of the form being edited: what it is called, what a
		 *	visitor reads, what shape it takes, whether it has to be filled -
		 *	and, for a select, its options
		 *
		 *	@param		{Object}	field
		 *	@param		{number}	index
		 *
		 *	@return		{Element}							<div class="forms-field">
		 */
		_renderFieldRow : function( field, index ) {

			const row = dc.createElement('div');
			row.className = 'forms-field';
			row.dataset.index = String( index );

			Nino.admin.forms._input( row, '/_admin/forms/label/fieldname', 'text', field.name, '' ).dataset.role = 'name';
			Nino.admin.forms._input( row, '/_admin/forms/label/fieldlabel', 'text', field.label, '' ).dataset.role = 'label';

			const typeWrap = dc.createElement('label');
			typeWrap.className = 'nino-admin-field';
			const typeSpan = dc.createElement('span');
			typeSpan.textContent = Nino.content.getText('/_admin/forms/label/fieldtype');
			typeWrap.appendChild( typeSpan );

			const type = dc.createElement('select');
			type.dataset.role = 'type';
			Nino.admin.forms._types.forEach( function( name ) {
				const option = dc.createElement('option');
				option.value = name;
				// The type names are the manifest's own words, not sentences -
				// they stay as they are in every interface language
				option.textContent = name;
				if( name === field.type )
					option.selected = true;
				type.appendChild( option );
			} );
			type.value = field.type;
			type.addEventListener( 'change', function() {
				Nino.admin.forms._collect();
				Nino.admin.forms._renderForm();
			} );
			typeWrap.appendChild( type );
			row.appendChild( typeWrap );

			const required = Nino.adminUi.switchField( {
				key 		: 'required',
				checked : field.required === true,
				label 	: Nino.content.getText('/_admin/forms/label/required'),
			} );
			required.dataset.role = 'required';
			row.appendChild( required );

			// Only a select has options, and only then is the box for them
			// anything but noise
			if( field.type === 'select' ) {
				const optionsWrap = dc.createElement('label');
				optionsWrap.className = 'nino-admin-field nino-admin-field-wide';
				const optionsSpan = dc.createElement('span');
				optionsSpan.textContent = Nino.content.getText('/_admin/forms/label/options');
				optionsWrap.appendChild( optionsSpan );
				const options = dc.createElement('textarea');
				options.rows = 3;
				options.dataset.role = 'options';
				options.value = ( field.options || [] ).join('\n');
				optionsWrap.appendChild( options );
				row.appendChild( optionsWrap );
			}

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/forms/label/removefield');
			remove.addEventListener( 'click', function() {
				Nino.admin.forms._collect();
				Nino.admin.forms._editing.fields.splice( index, 1 );
				Nino.admin.forms._renderForm();
			} );
			row.appendChild( remove );

			return row;
		},

		/**
		 *	Read the whole editor back into the working copy - what the form
		 *	is as well as its field rows. Called before every redraw and
		 *	before saving: the editor is drawn again whenever a field is
		 *	added, removed or retyped, and it draws from the working copy, so
		 *	anything not read back first would be quietly redrawn as it was
		 *
		 *	@return		void
		 */
		_collect : function() {

			const wrap = dc.getElementById('forms-form');
			const form = Nino.admin.forms._editing;

			if( wrap === null || form === null )
				return;

			const about = function( role ) { return wrap.querySelector('[data-about="'+ role+ '"]') };

			if( about('key') !== null ) {
				form.name						= about('name').value.trim();
				form.key						= about('key').value.trim().toLowerCase();
				form.to							= about('to').value.trim();
				form.subject				= about('subject').value.trim();
				form.confirm				= about('confirm').querySelector('[data-key]').checked === true;
				form.ownerTemplate	= about('ownertpl').value.trim();
				form.userTemplate		= about('usertpl').value.trim();
			}

			const rows = dc.getElementById('forms-field-rows');

			if( rows === null )
				return;

			const fields = [];

			Array.prototype.slice.call( rows.children ).forEach( function( row ) {

				const required	= row.querySelector('[data-role="required"] [data-key]');
				const options		= row.querySelector('[data-role="options"]');

				fields.push( {
					name 			: row.querySelector('[data-role="name"]').value.trim(),
					label 		: row.querySelector('[data-role="label"]').value.trim(),
					type 			: row.querySelector('[data-role="type"]').value,
					required 	: required !== null && required.checked === true,
					options 	: options === null ? [] : options.value.split('\n').map( function( line ) { return line.trim() } ).filter( Boolean ),
				} );
			} );

			form.fields = fields;
		},

		/**
		 *	Save the form being edited, then go back to the list it came
		 *	from - which is where the new name, key and counts are
		 *
		 *	@param		{string}	was					The key before this edit, '' for a new form
		 *	@param		{Object}	posted
		 *	@param		{Element}	save
		 *	@param		{Element}	msg
		 *
		 *	@return		void
		 */
		_save : function( was, posted, save, msg ) {

			save.disabled = true;
			msg.classList.remove('nino-admin-error');
			msg.textContent = Nino.content.getText('/_admin/common/msg/saving');

			Nino.admin.forms._apiCall( 'save', { key : was, form : posted }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null ) {
					msg.classList.add('nino-admin-error');
					msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
					return;
				}

				Nino.admin.forms.init();
			} );
		},

	};

	Nino.events.bindCallback( 'ready', Nino.admin.forms.init );

})(window, document, document.documentElement, document.body);
