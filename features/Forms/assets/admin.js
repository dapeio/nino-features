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
 *													writing. A refusal comes back as a code, which the
 *													workbench says in the session language, and - for a
 *													form that was not saved - with the words of what is
 *													wrong with each field and control; this file looks
 *													those up in the same language and lays out what it
 *													gets.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The two panes of the panel, one on show at a time
	const levels = Nino.adminUi.levels( 'forms', [ 'list', 'form' ] );

	Nino.admin.forms = Nino.adminUi.panel( {
		uri			: 'forms',
		mount		: 'forms-list',
		boot		: 'ready',
		error		: '/_admin/common/error/load',
		// What the shell asks before the editor is left with input nobody has saved
		dirty		: {
			isDirty	: function() { return Nino.admin.forms._editing !== null && Nino.admin.forms._fingerprint() !== Nino.admin.forms._saved },
			save		: function( done ) { Nino.admin.forms._submit( done ) },
			discard	: function() { Nino.admin.forms._saved = Nino.admin.forms._fingerprint() },
		},
		render	: function( data ) {
			Nino.admin.forms._forms			= data.forms || [];
			Nino.admin.forms._types			= data.types || [];
			Nino.admin.forms._templates	= data.templates || [];
			Nino.admin.forms._reserved	= data.reserved || [];
			Nino.admin.forms._default		= data.default === true;
			Nino.admin.forms._endpoint	= data.endpoint !== false;
			Nino.admin.forms._retention	= data.retention || 3;
			Nino.admin.forms._store			= data.store !== false;
			Nino.admin.forms._renderList();
		},
	} );

	Object.assign( Nino.admin.forms, {

		_forms		: [],
		// The field types \Nino\Form::TYPES declares - the panel offers exactly
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
		// What the editor held when it was drawn, as _fingerprint() reads it:
		// what unsaved input is told from
		_saved		: '',

		/**
		 *	Re-show whichever level is on - the shell calls this when the
		 *	panel is opened again
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.forms.ready === false )
				return Nino.admin.forms.init();

			if( Nino.admin.forms._editing !== null ) {

				// The editor reads its fields back from the copy, and what was
				// typed since is only in the boxes - drawn again, it would
				// be gone. The shell keeps count of that, and it stays as it is
				if( Nino.admin.dirty.isDirty( [ 'forms' ] ) === true )
					return;

				return Nino.admin.forms._showForm( Nino.admin.forms._editing, Nino.admin.forms._was );
			}

			Nino.admin.forms._renderList();
		},

		/**
		 *	The list: a warning while the endpoint's kernel module is off, a
		 *	hint while the kernel's fallback contact form is what is shown, one
		 *	card per form, and the action that adds one
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
			if( Nino.admin.forms._endpoint === false )
				wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/forms/hint/endpoint'), 'error' ) );

			if( Nino.admin.forms._default === true )
				wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/forms/hint/default') ) );

			// Never empty: \Nino\Form::forms() answers the contact form the
			// kernel falls back to while a project has defined none
			Nino.admin.forms._forms.forEach( function( form ) { wrap.appendChild( Nino.admin.forms._renderCard( form ) ) } );

			const add = Nino.adminUi.button( Nino.content.getText('/_admin/forms/label/new'), function() { Nino.admin.forms._showForm( Nino.admin.forms._blank(), '' ) }, { className : 'nino-admin-btn-primary' } );

			wrap.appendChild( Nino.adminUi.listActions( [ add ] ) );
			wrap.appendChild( Nino.admin.forms._renderSettings() );

			levels.show('list');
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

			const el = Nino.adminUi.el( 'form', 'nino-admin-card forms-settings' );

			el.appendChild( Nino.adminUi.el( 'h3', '', Nino.content.getText('/_admin/forms/label/submissions-settings') ) );

			el.appendChild( Nino.adminUi.numberField( {
				key : 'retention',
				label : Nino.content.getText('/_admin/forms/label/retention'),
				hint : Nino.content.getText('/_admin/forms/hint/retention'),
				value : Nino.admin.forms._retention,
				min : 1,
				max : 60,
				unit : 'months',
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
			const actions = Nino.adminUi.el( 'div', 'nino-admin-actions' );

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/common/label/save'), null, { type : 'submit', className : 'nino-admin-btn-primary' } );
			actions.appendChild( save );

			const msg = Nino.adminUi.el( 'p', 'nino-admin-hint' );
			msg.id = 'forms-settings-msg';
			actions.appendChild( msg );

			el.appendChild( actions );

			const line = Nino.adminUi.status( msg );
			line.bind( el );

			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				save.disabled = true;
				line.saving();

				// The shared fields carry their name as data-key and are read back
				// through it, the way every generated field in the workbench is -
				// they hand back the label, not the control
				const values = {};
				Array.prototype.slice.call( el.querySelectorAll('[data-key]') ).forEach( function( field ) {
					values[ field.dataset.key ] = field.type === 'checkbox' ? field.checked : field.value;
				} );

				Nino.admin.forms.call( 'settings', {
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

			const card = Nino.adminUi.el( 'section', 'nino-admin-card' );
			card.dataset.form = form.key;

			card.appendChild( Nino.adminUi.el( 'h3', '', form.name ) );
			card.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/forms/hint/shortcode'), Nino.admin.forms._shortcode( form ) ) ) );

			// What it collects, and what has come in through it. The
			// submissions themselves are the Submissions panel's - this is the
			// number that says which form is actually being used
			card.appendChild( Nino.adminUi.hint( [
				form.fields.map( function( field ) { return field.name } ).join( ', ' ),
				Nino.adminUi.format( Nino.content.getText('/_admin/forms/label/entries'), form.entries ),
			].filter( Boolean ).join( ' · ' ) ) );

			const actions = Nino.adminUi.el( 'div', 'nino-admin-actions' );
			actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/forms/label/edit'), function() { Nino.admin.forms._showForm( form, form.key ) }, { className : 'nino-admin-btn-primary' } ) );
			actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/forms/label/delete'), function() { Nino.admin.forms._delete( form ) }, { className : 'nino-admin-btn-danger' } ) );
			card.appendChild( actions );

			return card;
		},

		/**
		 *	The shortcode that renders one form, always with its key: a bare
		 *	[form] draws whichever form is first, and the list can be reordered
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

			Nino.admin.forms.call( 'delete', { key : form.key }, function( status, response ) {
				if( status !== 200 )
					return Nino.admin.forms.fail( dc.getElementById('forms-list'), status, response );

				Nino.admin.forms.reload();
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
			Nino.admin.forms._saved = Nino.admin.forms._fingerprint();
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

			const backLink = Nino.adminUi.el( 'a', 'nino-admin-back-link', Nino.content.getText('/_admin/common/label/back') );
			backLink.href = '#';
			backLink.addEventListener( 'click', function( ev ) { ev.preventDefault(); Nino.admin.forms._renderList() } );
			wrap.appendChild( Nino.admin.formToolbar( backLink ) );

			// The key it is replacing, so a rename stays one entry rather
			// than becoming a second form beside the old one - the one the
			// editor was opened with, not what is typed into the box by now
			const was = Nino.admin.forms._was;

			const el = Nino.adminUi.el( 'form' );

			// What a refused save says, inside the form: the status line sits in
			// the action bar, which a phone does not show
			const summary = Nino.adminUi.el( 'p', 'nino-admin-error' );
			summary.id = 'forms-summary';
			summary.hidden = true;
			el.appendChild( summary );

			const about = Nino.adminUi.el( 'fieldset' );
			about.appendChild( Nino.adminUi.el( 'legend', '', Nino.content.getText('/_admin/forms/label/form') ) );

			// Every control carries what it is, so _collect() can read the whole
			// form back before a redraw - the editor is drawn again whenever a
			// field is added, removed or retyped, and without this everything
			// typed into these boxes would be redrawn from the copy it was
			// loaded with, ie. silently thrown away
			Nino.admin.forms._input( about, '/_admin/forms/label/name', 'text', form.name, '', 'name' );
			Nino.admin.forms._input( about, '/_admin/forms/label/key', 'text', form.key, '', 'key', true );
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

			const fields = Nino.adminUi.el( 'fieldset' );
			fields.appendChild( Nino.adminUi.el( 'legend', '', Nino.content.getText('/_admin/forms/label/fields') ) );

			// The names something else already owns (\Nino\Form::RESERVED), said
			// here rather than only in the refusal a save would come back with
			fields.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/forms/hint/fields'), Nino.admin.forms._reserved.join( ', ' ) ) ) );

			const rows = Nino.adminUi.el( 'div' );
			rows.id = 'forms-field-rows';
			fields.appendChild( rows );

			form.fields.forEach( function( field, index ) { rows.appendChild( Nino.admin.forms._renderFieldRow( field, index ) ) } );

			fields.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/forms/label/addfield'), function() {
				Nino.admin.forms._collect();
				const taken = Nino.admin.forms._editing.fields.map( function( field ) { return field.name } );
				// A new field takes its name from its label, which is empty yet
				Nino.admin.forms._editing.fields.push( {
					name : Nino.admin.forms._deriveName( '', taken, Nino.admin.forms._reserved ),
					label : '', type : 'text', required : false, options : [], auto : true,
				} );
				Nino.admin.forms._renderForm();
			}, { className : 'nino-admin-btn-secondary' } ) );

			el.appendChild( fields );

			const actions = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/common/label/save'), null, { type : 'submit' } );
			actions.appendChild( save );

			const msg = Nino.adminUi.el( 'p' );
			actions.appendChild( msg );

			el.appendChild( actions );

			const line = Nino.adminUi.status( msg );
			line.bind( el );

			Nino.admin.forms._editor = { was : was, save : save, line : line };

			el.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				Nino.admin.forms._submit();
			} );

			wrap.appendChild( el );

			levels.show('form');
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
		 *	@param		{string}	[role]			What it is, for _collect()
		 *	@param		{boolean}	[required]	Marked the way the workbench marks a required field
		 *
		 *	@return		{Element}							The <input>
		 */
		_input : function( parent, label, type, value, hint, role, required ) {

			const field = Nino.adminUi.textField( {
				label				: Nino.content.getText( label ),
				type				: type,
				value				: value === null || value === undefined ? '' : String( value ),
				hint				: hint === '' ? '' : Nino.content.getText( hint ),
				required		: required === true,
				autocomplete: 'off',
			} );

			const input = field.querySelector('input');

			if( role !== undefined )
				input.dataset.about = role;

			parent.appendChild( field );

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

			// The placeholder that carries every field into a mail is a token of
			// its own, so it is put in here and not written into the fill
			if( hint !== '' )
				field.appendChild( Nino.adminUi.el( 'small', 'nino-admin-hint', Nino.adminUi.format( Nino.content.getText( hint ), '[[fields]]' ) ) );

			parent.appendChild( field );

			return select;
		},

		/**
		 *	The name a field takes from its label: the slug Nino.slug() makes of
		 *	it - lower case, ascii, words joined by a hyphen, the shape of a form
		 *	key; the German umlauts and the sharp s are spelled out (ae, oe, ue,
		 *	ss) and so are the other letters its table has, the ones it has not are
		 *	a separator. A label written as a fill key gives the last part of the
		 *	key. The result starts with a letter, is at most 64 characters, is
		 *	never one of the names the form keeps for itself and never one another
		 *	field has
		 *
		 *	@param		{string}				label
		 *	@param		{Array<string>}	taken				The names the other fields have
		 *	@param		{Array<string>}	reserved			\Nino\Form::RESERVED
		 *
		 *	@return		{string}
		 */
		_deriveName : function( label, taken, reserved ) {

			const key = /^\[\[\/(?:[^\[\]\/]+\/)*([^\[\]\/]+)\]\]$/.exec( String( label ).trim() );

			let name = Nino.slug( key === null ? String( label ) : key[1], 64 );

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

			const row = Nino.adminUi.el( 'div', 'forms-field' );
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

			const type = Nino.adminUi.selectField( {
				key			: 'type',
				label		: Nino.content.getText('/_admin/forms/label/fieldtype'),
				// The kernel's list of types, in the words of the workbench's
				// language - the type itself, which is what is stored, stays the value
				options	: Nino.admin.forms._types.map( function( kind ) { return { value : kind, label : Nino.content.getText( '/_admin/forms/type/'+ kind ) || kind } } ),
				value		: field.type,
				onChange: function() {
					Nino.admin.forms._collect();
					Nino.admin.forms._renderForm();
				},
			} );
			type.querySelector('select').dataset.role = 'type';
			row.appendChild( type );

			const required = Nino.adminUi.switchField( {
				key 		: 'required',
				checked : field.required === true,
				label 	: Nino.content.getText('/_admin/forms/label/required'),
			} );
			required.dataset.role = 'required';
			row.appendChild( required );

			// Only a select and a group of radio buttons have options, and only
			// then is the list for them anything but noise
			if( field.type === 'select' || field.type === 'radio' ) {

				/*	The workbench's list of texts keeps its rows itself and tells onChange
					what it holds, without the rows that are empty, each time that changes.
					The working copy is written by the field's place in it: _collect() puts
					new objects into the copy, and a write to the one this function was
					given would go nowhere	*/
				const options = Nino.adminUi.stringList( {
					key 			: 'options',
					label 		: Nino.content.getText('/_admin/forms/label/options'),
					value 		: field.options || [],
					text 			: {
						add 		: Nino.content.getText('/_admin/forms/options/add'),
						remove 	: Nino.content.getText('/_admin/forms/options/remove'),
						up 			: Nino.content.getText('/_admin/forms/options/up'),
						down 		: Nino.content.getText('/_admin/forms/options/down'),
						empty 	: Nino.content.getText('/_admin/forms/options/empty'),
						item 		: Nino.content.getText('/_admin/forms/options/item'),
					},
					onChange 	: function( list ) { Nino.admin.forms._editing.fields[index].options = list },
				} );

				// The whole width of the row; the role is how _collect() and a refusal find it
				options.classList.add('nino-admin-field-wide');
				options.dataset.role = 'options';
				row.appendChild( options );
			}

			const move = Nino.adminUi.el( 'div', 'nino-admin-move' );

			[ [ 'up', -1, '/_admin/common/label/moveup', '↑' ], [ 'down', 1, '/_admin/common/label/movedown', '↓' ] ].forEach( function( step ) {
				const button = Nino.adminUi.button( step[3], function() { Nino.admin.forms._move( index, step[1] ) }, {
					name		: Nino.content.getText( step[2] ),
					// The first field has nothing above it, the last nothing below
					disabled: index + step[1] < 0 || index + step[1] >= Nino.admin.forms._editing.fields.length,
				} );
				button.dataset.role = step[0];
				move.appendChild( button );
			} );

			row.appendChild( move );

			row.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/forms/label/removefield'), function() {
				Nino.admin.forms._collect();
				Nino.admin.forms._editing.fields.splice( index, 1 );
				Nino.admin.forms._renderForm();
			}, { className : 'nino-admin-btn-danger' } ) );

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
		 *	under the fields and the summary above them, and what points to a
		 *	sentence - the invalid controls, and the element that was given the
		 *	focus in place of a list (see _mark())
		 *
		 *	@return		void
		 */
		_unmark : function() {

			const wrap = dc.getElementById('forms-form');

			if( wrap === null )
				return;

			const sentences = [];

			wrap.querySelectorAll('.nino-admin-field-error').forEach( function( el ) {
				sentences.push( el.id );
				el.remove();
			} );
			wrap.querySelectorAll('[aria-invalid]').forEach( function( el ) {
				el.removeAttribute('aria-invalid');
				el.removeAttribute('aria-describedby');
			} );

			// The element that took the focus for a list points to the sentence as
			// well, and is not invalid itself: the list is
			wrap.querySelectorAll('[aria-describedby]').forEach( function( el ) {
				if( sentences.indexOf( el.getAttribute('aria-describedby') ) !== -1 )
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
		 *	would only repair with a code for the whole form and, in its params,
		 *	a word for every field (fields, by its place in the list, and
		 *	controls, which of its controls) and for every control of the form
		 *	itself (about): each word is looked up as the sentence it stands for,
		 *	written under its control, which is marked invalid and linked to it,
		 *	and the first one is focused. The sentence for the whole form and what
		 *	has no control of its own - a form without a field - go into the
		 *	summary at the top of the editor, since the status line is not shown
		 *	on a phone. Any other failure - a refused permission, a server error,
		 *	no answer - is said there as well. The options of a select or of radio
		 *	buttons are one control, a list: it is marked as a whole, the sentence
		 *	follows it and the focus goes to its first text, or to its button while
		 *	it has no row. That element points to the sentence as well: the wrapper
		 *	takes no focus and has no role, so nothing is read of it where the
		 *	focus is
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

			const summary = dc.getElementById('forms-summary');
			const headline = Nino.adminUi.api.errorText( status, response, '/_admin/common/error/save' );

			if( status !== 400 || response === null || typeof response !== 'object' ) {

				if( summary !== null ) {
					summary.textContent = headline;
					summary.hidden = false;
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
				const error = Nino.adminUi.el( 'p', 'nino-admin-field-error', text );
				error.id = id;
				// After the label, not in it: the sentence describes the control, and
				// inside the label it would be part of the control's name as well. The
				// list of options has no label around it, it is its own wrapper
				( control.dataset.role === 'options' ? control : control.parentNode ).after( error );

				control.setAttribute( 'aria-invalid', 'true' );
				control.setAttribute( 'aria-describedby', id );
				marked.push( control );
			};

			// The words of forms_fields and forms_duplicate, in the order of their
			// params: fields, controls, about. Any other code carries none
			const params = Array.isArray( response.params ) === true ? response.params : [];
			const fields = params[0] || {};
			const controls = params[1] || {};
			const about = params[2] || {};

			const say = function( word ) { return Nino.content.getText( '/_admin/forms/problem/'+ word ) };

			[ 'key', 'to', 'ownertpl', 'usertpl' ].forEach( function( role ) {
				if( typeof about[role] === 'string' )
					mark( wrap.querySelector('[data-about="'+ role+ '"]'), say( about[role] ) );
			} );

			Object.keys( about ).forEach( function( role ) {
				if( [ 'key', 'to', 'ownertpl', 'usertpl' ].indexOf( role ) === -1 && typeof about[role] === 'string' )
					rest.push( say( about[role] ) );
			} );

			const rows = dc.getElementById('forms-field-rows');

			Object.keys( fields ).sort( function( a, b ) { return a - b } ).forEach( function( index ) {

				const row = rows === null ? null : rows.children[ parseInt( index, 10 ) ];

				if( row === undefined || row === null )
					return rest.push( say( fields[index] ) );

				mark( row.querySelector('[data-role="'+ ( controls[index] || 'name' ) +'"]'), say( fields[index] ) );
			} );

			if( summary !== null ) {
				summary.textContent = [ headline ].concat( rest ).join(' ');
				summary.hidden = false;
			}

			if( marked.length > 0 ) {

				// A wrapper takes no focus: the first text of the list does, or its button
				// where it has no row yet. The wrapper has no role either, so a screen reader
				// at that element does not hear what the wrapper is described by: the element
				// is described by the sentence as well
				const first = marked[0].dataset.role === 'options' ? marked[0].querySelector('.nino-admin-stringlist-input, .nino-admin-stringlist-add') : marked[0];
				first.setAttribute( 'aria-describedby', marked[0].getAttribute('aria-describedby') );
				first.focus();
			}
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

			const before = form.fields;
			const fields = [];

			Array.prototype.slice.call( rows.children ).forEach( function( row, index ) {

				const required	= row.querySelector('[data-role="required"] [data-key]');
				const type			= row.querySelector('[data-role="type"]').value;

				// The options are not read from the list: it told the working copy what it
				// holds as it changed (see _renderFieldRow()). Only a field that is drawn
				// with a list and is still a select or a group of radio buttons has any;
				// one that was retyped to another type leaves them out
				const listed = row.querySelector('[data-role="options"]') !== null && ( type === 'select' || type === 'radio' );

				fields.push( {
					name 			: row.querySelector('[data-role="name"]').value.trim(),
					label 		: row.querySelector('[data-role="label"]').value.trim(),
					type 			: type,
					required 	: required !== null && required.checked === true,
					options 	: listed === true ? ( before[index].options || [] ) : [],
					auto			: row.dataset.auto === 'true',
				} );
			} );

			form.fields = fields;
		},

		/**
		 *	A form as it is sent: a copy, without what the working copy
		 *	remembers about how a name came about
		 *
		 *	@param		{Object}	form
		 *
		 *	@return		{Object}
		 */
		_posted : function( form ) {

			const copy = JSON.parse( JSON.stringify( form ) );
			copy.fields.forEach( function( field ) { delete field.auto } );

			return copy;
		},

		/**
		 *	The editor as one string, to tell unsaved input from what was saved:
		 *	the form as a save would send it, read from the boxes. They are read
		 *	back into the working copy first, as every redraw does, so that asking
		 *	changes nothing a redraw would not
		 *
		 *	@return		{string}									'' while there is no editor
		 */
		_fingerprint : function() {

			if( Nino.admin.forms._editing === null )
				return '';

			Nino.admin.forms._collect();

			return JSON.stringify( Nino.admin.forms._posted( Nino.admin.forms._editing ) );
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
		 *	@param		{Object}		line				status()'s answer
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

			Nino.admin.forms.call( 'save', { key : was, form : Nino.admin.forms._posted( posted ) }, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null ) {
					line.error( status, response, '/_admin/common/error/save' );
					Nino.admin.forms._mark( status, response );
					return finish( false );
				}

				// Saved is saved: what asked for it may go on while the list
				// is read again
				finish( true );
				Nino.admin.forms.reload();
			} );
		},

	} );

})(window, document, document.documentElement, document.body);
