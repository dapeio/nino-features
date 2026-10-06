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
		// The mail templates a form can name (/templates/mail-*, without the
		// header and the footer), which the editor offers as a list
		_templates: [],
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
		// The key the editor was opened with, '' for a new form: what a save
		// replaces, however the key is edited meanwhile
		_was		: '',
		// What the editor on show saves with: the key it is replacing, its Save
		// button and its status line. null while there is no editor
		_editor		: null,

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
				Nino.admin.forms._templates	= response.templates || [];
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

			if( Nino.admin.forms._editing !== null ) {

				// The editor reads its fields back from the copy, and what was
				// typed since is only in the boxes - drawn again, it would
				// be gone. Where this Nino keeps count of that, it stays as it is
				if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty.isDirty( [ 'forms' ] ) === true )
					return;

				return Nino.admin.forms._showForm( Nino.admin.forms._editing, Nino.admin.forms._was );
			}

			Nino.admin.forms._renderList();
		},

		/**
		 *	Call a forms/* action. The workbench's own request helper posts
		 *	where this Nino has one - it knows the project's directory and what
		 *	to do when the page has outlived its session; the post below is
		 *	what every panel did before it, with the base the asset bundle
		 *	fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint			Action name (eg. "list", becomes "forms/list")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'forms/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'forms/'+ endpoint, data : JSON.stringify( payload ) } );
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
			p.textContent = Nino.admin.forms._errorText( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	The line that says whether a form is saved. Where this Nino has
		 *	Nino.adminUi.status() it is that: "saving", "saved at 09:41", the
		 *	"unsaved changes" a keystroke brings, or why it failed with the
		 *	field the server named marked. Before it, the same calls write
		 *	this panel's sentences into the paragraph
		 *
		 *	@param		{Element}		msg
		 *	@param		{Element}		form
		 *
		 *	@return		{Object}						{ saving(), saved(), error( status, response, key ) }
		 */
		_status : function( msg, form ) {

			if( Nino.adminUi && typeof Nino.adminUi.status === 'function' ) {
				const line = Nino.adminUi.status( msg );
				line.bind( form );
				return line;
			}

			return {
				saving : function() { msg.classList.remove('nino-admin-error'); msg.textContent = Nino.content.getText('/_admin/common/msg/saving') },
				saved	 : function() { msg.textContent = Nino.content.getText('/_admin/common/msg/saved') },
				error	 : function( status, response, key ) { msg.classList.add('nino-admin-error'); msg.textContent = Nino.admin.forms._errorText( status, response, key ) },
			};
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
			Nino.admin.forms._was = '';
			Nino.admin.forms._editor	= null;

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
			add.addEventListener( 'click', function() { Nino.admin.forms._showForm( Nino.admin.forms._blank(), '' ) } );

			wrap.appendChild( Nino.adminUi.listActions( [ add ] ) );
			wrap.appendChild( Nino.admin.forms._renderSettings() );

			Nino.admin.forms._level('list');
		},

		/**
		 *	The two things about the submissions a project decides, under the
		 *	list, as a card of their own with its own Save: how long they are
		 *	kept, and whether they are kept at all.
		 *	Both are the kernel's own config keys - it is the kernel that
		 *	writes the records - so a project that switches this feature off
		 *	keeps whatever was chosen here
		 *
		 *	@return		{Element}							<form>
		 */
		_renderSettings : function() {

			const el = dc.createElement('form');
			el.className = 'nino-admin-card forms-settings';

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

			// A row of the card, not the workbench's fixed action bar: that one
			// belongs to the screen, and a second one lay over the first - and
			// over New form with it
			const actions = dc.createElement('div');
			actions.className = 'forms-actions';

			const save = dc.createElement('button');
			save.type = 'submit';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/common/label/save');
			actions.appendChild( save );

			const msg = dc.createElement('p');
			msg.id = 'forms-settings-msg';
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'role', 'status' );
			actions.appendChild( msg );

			el.appendChild( actions );

			// The shared fields carry their name as data-key and are read back
			// through it, the way every generated field in the workbench is -
			// they hand back the label, not the control
			const line = Nino.admin.forms._status( msg, el );

			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				save.disabled = true;
				line.saving();

				const values = {};
				Array.prototype.slice.call( el.querySelectorAll('[data-key]') ).forEach( function( field ) {
					values[ field.dataset.key ] = field.type === 'checkbox' ? field.checked : field.value;
				} );

				Nino.admin.forms._apiCall( 'settings', {
					retention : parseInt( values.retention, 10 ),
					store : values.store === true,
				}, function( status, response ) {
					save.disabled = false;
					if( status !== 200 || response === null ) {
						line.error( status, response, '/_admin/common/error/save' );
						return;
					}
					line.saved();
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
			edit.addEventListener( 'click', function() { Nino.admin.forms._showForm( form, form.key ) } );
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
		 *	@param		{string}	was					The key it is saved under, '' for a new form
		 *
		 *	@return		void
		 */
		_showForm : function( form, was ) {

			Nino.admin.forms._editing = JSON.parse( JSON.stringify( form ) );
			Nino.admin.forms._was = was;
			Nino.admin.forms._renderForm();

			// What is on screen now is what is saved
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.snapshot('forms');
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
			// than becoming a second form beside the old one - the one the
			// editor was opened with, not what is typed into the box by now
			const was = Nino.admin.forms._was;

			const el = dc.createElement('form');

			// What a refused save says, inside the form: the status line sits in
			// the action bar, which a phone does not show
			const summary = dc.createElement('p');
			summary.id = 'forms-summary';
			summary.className = 'nino-admin-error';
			summary.hidden = true;
			el.appendChild( summary );

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

			Nino.admin.forms._templateField( about, 'ownertpl', '/_admin/forms/label/ownertpl', form.ownerTemplate, '' );
			Nino.admin.forms._templateField( about, 'usertpl', '/_admin/forms/label/usertpl', form.userTemplate, '/_admin/forms/hint/templates' );

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
				const taken = Nino.admin.forms._editing.fields.map( function( field ) { return field.name } );
				// A new field takes its name from its label, which is empty yet
				Nino.admin.forms._editing.fields.push( {
					name : Nino.admin.forms._deriveName( '', taken, Nino.admin.forms._reserved ),
					label : '', type : 'text', required : false, options : [], auto : true,
				} );
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

			const line = Nino.admin.forms._status( msg, el );

			Nino.admin.forms._editor = { was : was, save : save, line : line };

			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				Nino.admin.forms._submit();
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
			input.className = 'nino-admin-input';
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
		 *	One mail template picked from the ones the project has, as a
		 *	labelled list. What the form names now is always in it, whether the
		 *	project has such a file or not: a list that left it out would show
		 *	the first template in its place, and the next save would quietly
		 *	change the form
		 *
		 *	@param		{Element}	parent
		 *	@param		{string}	role				What it is, for _collect() and for a refusal
		 *	@param		{string}	label				Fill key
		 *	@param		{string}	value				The template path the form names
		 *	@param		{string}	hint				Fill key, '' for none
		 *
		 *	@return		{Element}							The <select>
		 */
		_templateField : function( parent, role, label, value, hint ) {

			const paths = Nino.admin.forms._templates.slice();
			if( value !== '' && paths.indexOf( value ) === -1 )
				paths.unshift( value );

			const field = Nino.adminUi.selectField( {
				key			: role,
				label		: Nino.content.getText( label ),
				options	: paths.map( function( path ) { return { value : path, label : path } } ),
				value		: value,
			} );

			const select = field.querySelector('select');
			select.dataset.about = role;

			if( hint !== '' ) {
				const small = dc.createElement('small');
				small.className = 'nino-admin-hint';
				// The placeholder that carries every field into a mail is a token of
				// its own, so it is put in here and not written into the fill
				small.textContent = Nino.content.getText( hint ).replace( '%s', '[[fields]]' );
				field.appendChild( small );
			}

			parent.appendChild( field );

			return select;
		},

		/**
		 *	The name a field takes from its label: lower case, ascii, words
		 *	joined by a hyphen - the shape of a form key. A label written as a
		 *	fill key gives the last part of the key. The German umlauts and the
		 *	sharp s are spelled out (ae, oe, ue, ss) before any other accent is
		 *	dropped, so a name stays readable in the export. The result starts
		 *	with a letter, is at most 64 characters, is never one of the names
		 *	the form keeps for itself and never one another field has
		 *
		 *	@param		{string}				label
		 *	@param		{Array<string>}	taken				The names the other fields have
		 *	@param		{Array<string>}	reserved			\Nino\Form::RESERVED
		 *
		 *	@return		{string}
		 */
		_deriveName : function( label, taken, reserved ) {

			const spelled = { 'ä' : 'ae', 'ö' : 'oe', 'ü' : 'ue', 'ß' : 'ss', 'Ä' : 'Ae', 'Ö' : 'Oe', 'Ü' : 'Ue', 'ẞ' : 'SS' };

			const key = /^\[\[\/(?:[^\[\]\/]+\/)*([^\[\]\/]+)\]\]$/.exec( String( label ).trim() );
			const text = key === null ? String( label ) : key[1];

			let name = text
				.replace( /[äöüßÄÖÜẞ]/g, function( character ) { return spelled[character] } )
				.normalize('NFD')
				.replace( /[\u0300-\u036f]/g, '' )
				.toLowerCase()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-+|-+$/g, '' );

			if( name === '' )
				name = 'field';
			else if( /^[a-z]/.test( name ) === false )
				name = 'field-'+ name;

			// A suffix is part of the 64, so the name is cut to leave room for it
			const cut = function( base, suffix ) { return base.slice( 0, 64 - suffix.length ).replace( /-+$/, '' )+ suffix };

			name = cut( name, '' );

			if( reserved.indexOf( name ) !== -1 )
				name = cut( name, '-field' );

			const base = name;
			for( let number = 2; taken.indexOf( name ) !== -1; number++ )
				name = cut( base, '-'+ number );

			return name;
		},

		/**
		 *	One field of the form being edited: what it is called, what a
		 *	visitor reads, what shape it takes, whether it has to be filled -
		 *	and, for a select or a group of radio buttons, its options. And the
		 *	pair of buttons that moves it up or down the list
		 *
		 *	A field that was added here takes its name from its label until the
		 *	name is typed into by hand (row.dataset.auto, which _collect() reads
		 *	back into the working copy so a redraw keeps it). A field that was
		 *	loaded never does: a name that is saved is never rewritten
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

			if( field.auto === true )
				row.dataset.auto = 'true';

			const name = Nino.admin.forms._input( row, '/_admin/forms/label/fieldname', 'text', field.name, '' );
			name.dataset.role = 'name';
			// The first thing typed into the name is the person's own, and the
			// label stops following it
			name.addEventListener( 'input', function() { delete row.dataset.auto } );

			const label = Nino.admin.forms._input( row, '/_admin/forms/label/fieldlabel', 'text', field.label, '' );
			label.dataset.role = 'label';
			label.addEventListener( 'input', function() {

				if( row.dataset.auto !== 'true' )
					return;

				const taken = [];
				Array.prototype.slice.call( dc.getElementById('forms-field-rows').children ).forEach( function( other ) {
					if( other !== row )
						taken.push( other.querySelector('[data-role="name"]').value.trim() );
				} );

				name.value = Nino.admin.forms._deriveName( label.value, taken, Nino.admin.forms._reserved );
			} );

			const typeWrap = dc.createElement('label');
			typeWrap.className = 'nino-admin-field';
			const typeSpan = dc.createElement('span');
			typeSpan.textContent = Nino.content.getText('/_admin/forms/label/fieldtype');
			typeWrap.appendChild( typeSpan );

			const type = dc.createElement('select');
			type.className = 'nino-admin-input';
			type.dataset.role = 'type';
			Nino.admin.forms._types.forEach( function( kind ) {
				const option = dc.createElement('option');
				option.value = kind;
				// The kernel's list of types, in the words of the workbench's
				// language - the type itself, which is what is stored, stays the value
				option.textContent = Nino.content.getText( '/_admin/forms/type/'+ kind ) || kind;
				if( kind === field.type )
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

			// Only a select and a group of radio buttons have options, and only
			// then is the box for them anything but noise
			if( field.type === 'select' || field.type === 'radio' ) {
				const optionsWrap = dc.createElement('label');
				optionsWrap.className = 'nino-admin-field nino-admin-field-wide';
				const optionsSpan = dc.createElement('span');
				optionsSpan.textContent = Nino.content.getText('/_admin/forms/label/options');
				optionsWrap.appendChild( optionsSpan );
				const options = dc.createElement('textarea');
				options.className = 'nino-admin-input';
				options.rows = 3;
				options.dataset.role = 'options';
				options.value = ( field.options || [] ).join('\n');
				optionsWrap.appendChild( options );
				row.appendChild( optionsWrap );
			}

			const move = dc.createElement('div');
			move.className = 'forms-field-move';

			[ [ 'up', -1, '/_admin/common/label/moveup', '\u2191' ], [ 'down', 1, '/_admin/common/label/movedown', '\u2193' ] ].forEach( function( step ) {
				const button = dc.createElement('button');
				button.type = 'button';
				button.dataset.role = step[0];
				button.title = Nino.content.getText( step[2] );
				button.setAttribute( 'aria-label', Nino.content.getText( step[2] ) );
				button.textContent = step[3];
				// The first field has nothing above it, the last nothing below
				button.disabled = index + step[1] < 0 || index + step[1] >= Nino.admin.forms._editing.fields.length;
				button.addEventListener( 'click', function() { Nino.admin.forms._move( index, step[1] ) } );
				move.appendChild( button );
			} );

			row.appendChild( move );

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
		 *	Move one field up or down the list: what is typed is read back
		 *	first, the two fields trade places in the working copy and the
		 *	editor is drawn again. The focus goes to the button that was
		 *	pressed on the field in its new place - or to the other one where
		 *	that button is switched off because the field is at the end now
		 *
		 *	@param		{number}	index
		 *	@param		{number}	direction			-1 up, 1 down
		 *
		 *	@return		void
		 */
		_move : function( index, direction ) {

			Nino.admin.forms._collect();

			const fields = Nino.admin.forms._editing.fields;
			const to = index + direction;

			if( to < 0 || to >= fields.length )
				return;

			const moved = fields[index];
			fields[index] = fields[to];
			fields[to] = moved;

			Nino.admin.forms._renderForm();

			const row = dc.getElementById('forms-field-rows').children[to];
			let button = row.querySelector( '[data-role="'+ ( direction < 0 ? 'up' : 'down' ) +'"]' );

			if( button.disabled === true )
				button = row.querySelector( '[data-role="'+ ( direction < 0 ? 'down' : 'up' ) +'"]' );

			button.focus();
		},

		/**
		 *	Take the marks of a refused save off the editor: the sentences
		 *	under the fields and the summary above them
		 *
		 *	@return		void
		 */
		_unmark : function() {

			const wrap = dc.getElementById('forms-form');

			if( wrap === null )
				return;

			wrap.querySelectorAll('.nino-admin-field-error').forEach( function( el ) { el.remove() } );
			wrap.querySelectorAll('[aria-invalid]').forEach( function( el ) {
				el.removeAttribute('aria-invalid');
				el.removeAttribute('aria-describedby');
			} );

			const summary = dc.getElementById('forms-summary');
			if( summary !== null ) {
				summary.hidden = true;
				summary.textContent = '';
			}
		},

		/**
		 *	Show where a refused save went wrong. The server answers a form it
		 *	would only repair with a sentence for the whole form and, beside it,
		 *	one for every field (fields, by its place in the list, and controls,
		 *	which of its controls) and for every control of the form itself
		 *	(about): each is written under its control, which is marked invalid
		 *	and linked to it, and the first one is focused. The sentence for the
		 *	whole form and what has no control of its own - a form without a
		 *	field - go into the summary at the top of the editor, since the
		 *	status line is not shown on a phone. Any other failure - a refused
		 *	permission, a server error, no answer - is said there as well
		 *
		 *	@param		{number}	status
		 *	@param		{*}				response
		 *
		 *	@return		void
		 */
		_mark : function( status, response ) {

			const wrap = dc.getElementById('forms-form');

			if( wrap === null )
				return;

			if( status !== 400 || response === null || typeof response !== 'object' ) {

				const failed = dc.getElementById('forms-summary');

				if( failed !== null ) {
					failed.textContent = Nino.admin.forms._errorText( status, response, '/_admin/common/error/save' );
					failed.hidden = false;
				}

				return;
			}

			const marked = [];
			const rest = [];

			const mark = function( control, text ) {

				if( control === null ) {
					rest.push( text );
					return;
				}

				const id = 'forms-error-'+ marked.length;
				const error = dc.createElement('p');
				error.id = id;
				error.className = 'nino-admin-field-error';
				error.textContent = text;
				// After the label, not in it: the sentence describes the control, and
				// inside the label it would be part of the control's name as well
				control.parentNode.after( error );

				control.setAttribute( 'aria-invalid', 'true' );
				control.setAttribute( 'aria-describedby', id );
				marked.push( control );
			};

			const about = response.about || {};

			[ 'key', 'to', 'ownertpl', 'usertpl' ].forEach( function( role ) {
				if( typeof about[role] === 'string' )
					mark( wrap.querySelector('[data-about="'+ role+ '"]'), about[role] );
			} );

			Object.keys( about ).forEach( function( role ) {
				if( [ 'key', 'to', 'ownertpl', 'usertpl' ].indexOf( role ) === -1 && typeof about[role] === 'string' )
					rest.push( about[role] );
			} );

			const rows = dc.getElementById('forms-field-rows');
			const fields = response.fields || {};
			const controls = response.controls || {};

			Object.keys( fields ).sort( function( a, b ) { return a - b } ).forEach( function( index ) {

				const row = rows === null ? null : rows.children[ parseInt( index, 10 ) ];

				if( row === undefined || row === null )
					return rest.push( fields[index] );

				mark( row.querySelector('[data-role="'+ ( controls[index] || 'name' ) +'"]'), fields[index] );
			} );

			const summary = dc.getElementById('forms-summary');

			if( summary !== null && typeof response.error === 'string' ) {
				summary.textContent = [ response.error ].concat( rest ).join(' ');
				summary.hidden = false;
			}

			if( marked.length > 0 )
				marked[0].focus();
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
				form.ownerTemplate	= about('ownertpl').value;
				form.userTemplate		= about('usertpl').value;
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
					auto			: row.dataset.auto === 'true',
				} );
			} );

			form.fields = fields;
		},

		/**
		 *	Save the editor on show - one way for the button and for the shell,
		 *	which saves from its question before a back link or a log out
		 *
		 *	@param		{Function}	[done]			Told whether it was saved
		 *
		 *	@return		void
		 */
		_submit : function( done ) {

			const editor = Nino.admin.forms._editor;

			if( editor === null || Nino.admin.forms._editing === null ) {
				if( typeof done === 'function' )
					done( false );
				return;
			}

			Nino.admin.forms._collect();
			Nino.admin.forms._save( editor.was, Nino.admin.forms._editing, editor.save, editor.line, done );
		},

		/**
		 *	Save the form being edited, then go back to the list it came
		 *	from - which is where the new name, key and counts are
		 *
		 *	@param		{string}		was					The key before this edit, '' for a new form
		 *	@param		{Object}		posted
		 *	@param		{Element}		save
		 *	@param		{Object}		line				_status()'s answer
		 *	@param		{Function}	[done]			Told whether it was saved - the shell's Save asks
		 *
		 *	@return		void
		 */
		_save : function( was, posted, save, line, done ) {

			const finish = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			// A second submit while one is on its way
			if( save.disabled === true )
				return finish( false );

			save.disabled = true;
			line.saving();
			Nino.admin.forms._unmark();

			// What the working copy remembers about how a name came about is
			// not part of the form
			const form = JSON.parse( JSON.stringify( posted ) );
			form.fields.forEach( function( field ) { delete field.auto } );

			Nino.admin.forms._apiCall( 'save', { key : was, form : form }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null ) {
					line.error( status, response, '/_admin/common/error/save' );
					Nino.admin.forms._mark( status, response );
					return finish( false );
				}

				// Saved is saved: what asked for it may go on while the list
				// is read again
				finish( true );
				Nino.admin.forms.init();
			} );
		},

	};

	/*	The shell asks Save, Discard or Cancel before the form's back link, a
		log out or a language change would lose what is typed into it. A panel
		registers where this Nino has the registry and does without where it
		has not - panel scripts run on older workbenches too	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.watchForm( 'forms', function() { return dc.getElementById('forms-form') }, function( done ) {
			Nino.admin.forms._submit( done );
		} );

	Nino.events.bindCallback( 'ready', Nino.admin.forms.init );

})(window, document, document.documentElement, document.body);
