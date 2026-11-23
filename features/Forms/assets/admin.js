/**
 *	Nino features
 *	admin.js		The panel of the Forms feature: the forms a project has defined in a table, and
 *							one form on a screen of its own with the tabs Fields, Delivery and Protection,
 *							saved as a whole (model A of docs/admin-ui.md). The properties of a field are a
 *							dialog of four fields, the options of a choice a dialog of one. Built from the
 *							blocks of the workbench: panel(), table(), tabs(), list(), formDialog(),
 *							the question dialog, contextBar() and actionBar().
 *
 *							The addresses are #forms (the directory), #forms/forms/@new/<tab> (a new form) and
 *							#forms/forms/<key>/<tab> (a form, its tab: the words @fields, @delivery or @protection).
 *							A form may be called new: its key is a key, and @new is the word that makes one.
 *
 *							Admin/Admin.php beside this file does the reading and writing. A refusal comes
 *							back as a code, which the workbench says in the session language, and - for a
 *							form that was not saved - with the words of what is wrong with each field and
 *							control; this file looks those up and lays them out at the field.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( key ) { return Nino.content.getText( '/_admin/forms/'+ key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// What the last answer of the server holds, and what the screen keeps of its own: the form that is open (a working copy), its tab and what it was saved as
	const S = { forms : [], types : [], reserved : [], templates : [], locales : [], retention : 3, store : true, isDefault : false, endpoint : true, guards : {}, view : null, query : '' };

	const TABS = [ 'fields', 'delivery', 'protection' ];
	const NAME = /^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/;
	const KEY = /^[a-z][a-z0-9-]*$/;

	const copy = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
	const work = function() { return ( Nino.admin.workLocale || {} ).current || S.locales[0] || '' };

	// The row of the list of fields that stands for the field at an index: a list item with its place in data-row
	const rowOf = function( mount, index ) {
		return Array.prototype.find.call( mount.querySelectorAll('li'), function( li ) { return li.dataset.row === String( index ) } ) ?? null;
	};

	// The label of a field as the person reads it: the text of the working language, else of the first language that has one, else the name
	const wording = function( field ) {

		const texts = field.texts || {};
		const text = texts[ work() ] || S.locales.map( function( code ) { return texts[code] } ).find( function( value ) { return typeof value === 'string' && value !== '' } );

		return text || field.name;
	};

	// A name for a field from its label: letters and digits, words joined by a hyphen, a letter first, not taken and not one the engine keeps for itself
	const deriveName = function( text, taken ) {

		let stem = String( text ).normalize('NFD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );

		if( stem === '' || /^[0-9]/.test( stem ) === true )
			stem = 'field'+ ( stem === '' ? '' : '-'+ stem );

		stem = stem.slice( 0, 56 ).replace( /-+$/, '' );

		let name = stem;

		for( let n = 2; taken.indexOf( name ) !== -1 || S.reserved.indexOf( name ) !== -1; n++ )
			name = stem+ '-'+ n;

		return name;
	};

	const blank = function() {
		return {
			key : '', name : '', to : '', subject : '', subjectTexts : {}, confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user',
			fields : [], entries : 0,
		};
	};

	// --- the directory ------------------------------------------------------------------------------------------------------------

	const focusAround = function( mount, keys, gone ) {

		const at = keys.indexOf( gone );
		const around = keys.filter( function( key ) { return key !== gone } );
		const key = around[ Math.min( at, around.length - 1 ) ];
		const target = key === undefined ? mount.querySelector('.nino-admin-empty') : mount.querySelector( '[data-row="'+ CSS.escape( key ) +'"] button, [data-row="'+ CSS.escape( key ) +'"]' );

		if( target === null )
			return;

		if( target.hasAttribute('tabindex') === false && target.tagName !== 'BUTTON' )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	const removeForm = function( form, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: Nino.adminUi.format( w('confirm/delete'), form.name ),
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.forms.call( 'delete', { key : form.key }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( dc.getElementById('forms-list'), status, response, '/_admin/forms/error/save' );

					const keys = S.forms.map( function( row ) { return row.key } );
					const fromList = S.view === null;

					S.forms = S.forms.filter( function( row ) { return row.key !== form.key } );
					S.view = null;

					if( fromList === true ) {
						drawList();
						focusAround( dc.getElementById('forms-list'), keys, form.key );
					}
					else {
						Nino.admin.router.go( 'forms', [] );
						follow( {} );
					}

					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// How long submissions stay and whether they stay at all: the kernel's own two keys, a dialog of two fields
	const openSubmissions = function( opener ) {

		const body = el( 'div', 'nino-admin-dialog-fields' );
		const retention = Nino.adminUi.numberField( { key : 'retention', label : w('label/retention'), help : w('hint/retention'), value : S.retention, min : 1, max : 60, unit : 'months', required : true } );
		const store = Nino.adminUi.switchField( { key : 'store', label : w('label/store'), help : w('hint/store'), checked : S.store === true } );

		body.appendChild( retention );
		body.appendChild( store );

		Nino.adminUi.formDialog( {
			title		: w('dialog/submissions'),
			body		: body,
			submit	: t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit : function( values, ctl ) {

				const input = retention.querySelector('input');
				const months = parseInt( input.value, 10 );
				const refuse = function( text ) {
					Nino.adminUi.clearFieldErrors( body );
					Nino.adminUi.fieldError( retention, input, 'forms-retention-error', text );
					input.focus();
					ctl.idle();
				};

				if( Number.isInteger( months ) === false || months < 1 || months > 60 )
					return refuse( Nino.adminUi.api.errorText( 400, { code : 'forms_retention' }, '/_admin/forms/error/save' ) );

				Nino.admin.forms.call( 'settings', { retention : months, store : store.querySelector('input').checked === true }, function( status, response ) {

					if( status !== 200 || response === null )
						return refuse( Nino.adminUi.api.errorText( status, response, '/_admin/forms/error/save' ) );

					S.retention = response.retention;
					S.store = response.store === true;
					ctl.close();
					Nino.adminUi.say( t('/_admin/common/msg/saved') );
				} );
			},
		} );
	};

	const drawList = function() {

		const mount = dc.getElementById('forms-list');

		if( mount === null )
			return;

		mount.innerHTML = '';

		// A form that draws fine and posts to a 404 is the one failure this feature could produce silently, so it is said here
		if( S.endpoint === false )
			mount.appendChild( Nino.adminUi.notice( w('hint/endpoint') ) );

		if( S.isDefault === true )
			mount.appendChild( Nino.adminUi.notice( w('hint/default') ) );

		const holder = el('div');

		mount.appendChild( holder );

		Nino.adminUi.table( {
			mount		: holder,
			rowKey	: 'key',
			search	: true,
			query		: S.query,
			onQuery	: function( value ) { S.query = value },
			rows		: S.forms,
			labels	: { search : w('label/search'), empty : w('empty/forms'), noMatch : w('empty/nomatch') },
			columns	: [
				{ key : 'name', label : w('label/name'), type : 'string', render : function( value, row ) {
					const cell = el( 'span', 'forms-name', value );
					cell.appendChild( el( 'br' ) );
					cell.appendChild( el( 'code', '', row.key ) );
					return cell;
				} },
				{ key : 'fieldCount', label : w('label/fields'), type : 'integer' },
				{ key : 'entries', label : w('label/entries'), type : 'integer' },
			],
			actions	: function( row ) {

				const edit = Nino.adminUi.button( w('label/edit'), function() { go( row.key, 'fields' ) }, { small : true } );
				const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { removeForm( row, drop ) }, { small : true, kind : 'danger' } );

				// The form the kernel falls back to is no definition yet: there is nothing to delete
				return S.isDefault === true ? [ edit ] : [ edit, drop ];
			},
			onRowClick : function( row ) { go( row.key, 'fields' ) },
		} );

		const make = Nino.adminUi.button( w('label/new'), function() { go( '', 'fields' ) }, { kind : 'primary' } );
		const kept = Nino.adminUi.button( w('label/submissions'), function() { openSubmissions( kept ) }, { kind : 'secondary' } );

		mount.appendChild( Nino.adminUi.actionBar( { list : true, secondary : [ kept ], primary : make } ).el );
	};

	// --- one field, in a dialog of four fields -------------------------------------------------------------------------------------

	const typeOptions = function() {
		return S.types.map( function( type ) { return { value : type, label : w('type/'+ type) } } );
	};

	/**
	 *	The properties of one field: its label in every language, its name, its type and whether it is required. A new field is made in
	 *	the dialog, an old one edited; the working copy of the form changes and nothing is sent - the form is saved as a whole
	 *
	 *	@param		{Object}		field				{ name, label, type, required, options, texts }
	 *	@param		{number}		index				The place of the field in the form, -1 for a new one
	 *	@param		{Element}		[opener]		What had the focus
	 */
	const openField = function( field, index, opener ) {

		const view = S.view;
		const creating = index === -1;

		Nino.adminUi.formDialog( {
			title		: w( creating === true ? 'dialog/field-add' : 'dialog/field-edit' ),
			submit	: creating === true ? w('dialog/field-add') : t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			fields	: [
				{ key : 'label', type : 'locales', label : w('label/fieldlabel'), help : w('hint/fieldlabel'), locales : S.locales, values : field.texts || {}, required : true, maxLength : 200 },
				{ key : 'name', label : w('label/fieldname'), help : Nino.adminUi.format( w('hint/fieldname'), S.reserved.join( ', ' ) ), value : creating === true ? '' : field.name, maxLength : 64 },
				{ key : 'type', type : 'select', label : w('label/fieldtype'), help : w('hint/fieldtype'), options : typeOptions(), value : field.type },
				{ key : 'required', type : 'check', label : w('label/required'), value : field.required === true },
			],
			onSubmit : function( values, ctl ) {

				const taken = view.draft.fields.filter( function( other, at ) { return at !== index } ).map( function( other ) { return other.name } );
				const spoken = Object.keys( values.label ).map( function( code ) { return values.label[code] } ).find( function( text ) { return text.trim() !== '' } ) || '';
				const name = values.name.trim() === '' ? deriveName( values.label[ work() ] || spoken, taken ) : values.name.trim();

				if( NAME.test( name ) === false )
					return ctl.fail( 'name', w('problem/name') );

				if( S.reserved.indexOf( name ) !== -1 )
					return ctl.fail( 'name', w('problem/reserved') );

				if( taken.indexOf( name ) !== -1 )
					return ctl.fail( 'name', w('problem/duplicate') );

				const next = {
					name : name, type : values.type, required : values.required === true, texts : values.label,
					label : field.label, options : [ 'select', 'radio' ].indexOf( values.type ) === -1 ? [] : ( field.options || [] ),
				};

				// A label the person changed is written as words of the panel's own, whatever it was: the server decides by comparing with what the label said
				if( creating === true )
					view.draft.fields.push( next );
				else
					view.draft.fields[ index ] = next;

				ctl.close();
				drawFields();
				view.changed();
			},
		} );
	};

	// The options of a select or of a group of radio buttons: one value of the field to a line
	const openOptions = function( field, opener ) {

		const view = S.view;
		const body = el( 'div', 'nino-admin-dialog-fields' );
		const area = Nino.adminUi.textareaField( { key : 'options', label : w('label/options'), help : w('hint/options'), rows : 8, value : ( field.options || [] ).join( '\n' ), maxLength : 4000 } );
		const input = area.querySelector('textarea');

		body.appendChild( area );

		return Nino.adminUi.formDialog( {
			title		: w('dialog/options'),
			body		: body,
			submit	: t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit : function( values, ctl ) {

				const options = input.value.split( '\n' ).map( function( line ) { return line.trim() } ).filter( function( line ) { return line !== '' } );

				if( field.type === 'radio' && options.length === 0 ) {
					Nino.adminUi.clearFieldErrors( body );
					Nino.adminUi.fieldError( area, input, 'forms-options-error', w('problem/options') );
					input.focus();
					return ctl.idle();
				}

				field.options = options;
				ctl.close();
				drawFields();
				view.changed();
			},
		} );
	};

	// The fields of the open form as an ordered list: a row opens the dialog, the buttons of the cluster move and remove it
	const drawFields = function() {

		const view = S.view;
		const mount = view.fieldsMount;

		mount.innerHTML = '';

		if( view.draft.fields.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( w('empty/fields') ) );
			return;
		}

		const rows = view.draft.fields.map( function( field, index ) {

			const choice = field.type === 'select' || field.type === 'radio';
			const options = choice === true ? Nino.adminUi.button( w('label/options'), function() { openOptions( field, options ) }, { small : true } ) : null;

			return {
				key		: String( index ),
				title	: wording( field ),
				detail	: field.name+ ' · '+ w('type/'+ field.type)+ ( choice === true ? ' · '+ ( ( field.options || [] ).length === 1 ? w('label/option-one') : Nino.adminUi.format( w('label/option-count'), ( field.options || [] ).length ) ) : '' ),
				badge	: field.required === true ? { text : w('label/required'), state : 'muted' } : null,
				onOpen	: function() { openField( field, index, rowOf( mount, index ).querySelector('button') ) },
				actions	: choice === true ? [ options ] : [],
			};
		} );

		mount.appendChild( Nino.adminUi.list( rows, { label : w('label/fields') } ) );

		const move = function( from, to ) {

			const fields = view.draft.fields;

			if( to < 0 || to >= fields.length )
				return;

			fields.splice( to, 0, fields.splice( from, 1 )[0] );
			drawFields();
			view.changed();

			// The focus goes with the row that was moved: to the same button of its new place, else to the one that is left
			const again = view.moves[ to ][ from < to ? 1 : 0 ];

			( again.disabled === true ? view.moves[ to ][ 0 ].disabled === true ? view.moves[ to ][ 1 ] : view.moves[ to ][ 0 ] : again ).focus();
		};

		const remove = function( index ) {

			view.draft.fields.splice( index, 1 );
			drawFields();
			view.changed();

			const row = rowOf( mount, Math.min( index, view.draft.fields.length - 1 ) );
			const next = row !== null ? row.querySelector('button') : ( mount.querySelector('.nino-admin-empty') || view.addField );

			if( next.hasAttribute('tabindex') === false && next.tagName !== 'BUTTON' )
				next.setAttribute( 'tabindex', '-1' );

			next.focus();
		};

		view.moves = [];

		Array.prototype.forEach.call( mount.querySelectorAll('li'), function( li, index ) {

			const cluster = el( 'span', 'nino-admin-move' );
			const name = wording( view.draft.fields[ index ] );
			const up = Nino.adminUi.iconButton( 'arrow-up', Nino.adminUi.format( w('label/move-up'), name ), function() { move( index, index - 1 ) }, { small : true, disabled : index === 0 } );
			const down = Nino.adminUi.iconButton( 'arrow-down', Nino.adminUi.format( w('label/move-down'), name ), function() { move( index, index + 1 ) }, { small : true, disabled : index === view.draft.fields.length - 1 } );
			const drop = Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('label/field-remove'), name ), function() { remove( index ) }, { small : true, danger : true } );

			[ up, down, drop ].forEach( function( button ) { cluster.appendChild( button ) } );
			// A child of the row itself, as the kernel's rule for an ordered list has it: inside the row it would lie under the overlay that opens it
			li.appendChild( cluster );
			view.moves.push( [ up, down, drop ] );
		} );
	};

	// --- one form, on a screen of its own ----------------------------------------------------------------------------------------

	// What the form holds right now, read back from the controls: the draft is changed by the dialogs, the controls by typing
	const collect = function() {

		const view = S.view;
		const draft = view.draft;
		const read = function( field ) { return field.querySelector('input, select').value };

		return {
			key : read( view.controls.key ).trim(), name : read( view.controls.name ).trim(), to : read( view.controls.to ).trim(),
			subject : draft.subject, subjectTexts : view.controls.subject.value(), confirm : view.controls.confirm.querySelector('input').checked === true,
			ownerTemplate : read( view.controls.ownerTemplate ), userTemplate : read( view.controls.userTemplate ),
			fields : draft.fields,
		};
	};

	const fingerprint = function() { return S.view === null ? '' : JSON.stringify( collect() ) };

	const templateField = function( key, label, value, help ) {

		const paths = S.templates.indexOf( value ) === -1 && value !== '' ? [ value ].concat( S.templates ) : S.templates;

		return Nino.adminUi.selectField( { key : key, label : label, help : help, value : value, options : paths.map( function( path ) { return { value : path, label : path } } ) } );
	};

	// The tab Protection says what guards a form and where it is set: it edits nothing
	const protectionTab = function() {

		const g = S.guards;
		const pane = el('div');
		const on = function( yes ) { return { text : w( yes === true ? 'state/on' : 'state/off' ), state : yes === true ? 'ok' : 'muted' } };

		const title = Nino.adminUi.sectionTitle( w('tab/protection') );

		pane.appendChild( title );
		Nino.adminUi.sectionHelp( title, pane, w('hint/protection') );

		pane.appendChild( Nino.adminUi.list( [
			{ key : 'csrf', title : w('guard/csrf'), detail : w('guard/csrf-detail'), badge : on( true ) },
			{ key : 'honeypot', title : w('guard/honeypot'), detail : w('guard/honeypot-detail'), badge : on( true ) },
			{ key : 'minSeconds', title : w('guard/minseconds'), detail : Nino.adminUi.format( w('guard/minseconds-detail'), g.minSeconds ), badge : on( g.minSeconds > 0 ) },
			{ key : 'rateLimit', title : w('guard/ratelimit'), detail : Nino.adminUi.format( w('guard/ratelimit-detail'), g.rateLimit ), badge : on( g.rateLimit > 0 ) },
			{ key : 'blocklist', title : w('guard/blocklist'), detail : ( g.blocked === 1 ? w('guard/blocklist-one') : Nino.adminUi.format( w('guard/blocklist-detail'), g.blocked ) ), badge : on( g.blocked > 0 ) },
		], { label : w('tab/protection') } ) );

		// What is set where, and the way there: a link, since the settings are another tab of the panel
		const settings = el( 'a', 'nino-admin-linkbutton', t('/_admin/common/split/settings') );

		settings.href = Nino.admin.router.href( 'forms-settings', [] );

		pane.appendChild( Nino.adminUi.hint( w('hint/settings') ) );
		pane.appendChild( settings );

		return pane;
	};

	// What a refusal of the server is about: the controls of the form that carry a word of the answer
	const refusal = function( response ) {

		const view = S.view;
		let first = null;

		if( response === null || response.code !== 'forms_fields' || Array.isArray( response.params ) === false )
			return false;

		const fields = response.params[0] || {};
		const about = response.params[2] || {};
		let serial = 0;

		const say = function( target, input, word ) {

			Nino.adminUi.fieldError( target, input, 'forms-error-'+ ( ++serial ), w( 'problem/'+ word ) );
			first = first || input;
		};

		// A control of the form itself: the key, the recipient, the subject, a mail template
		const byAbout = { key : view.controls.key, to : view.controls.to, ownertpl : view.controls.ownerTemplate, usertpl : view.controls.userTemplate, keytaken : view.controls.key };

		Object.keys( about ).forEach( function( control ) {

			if( control === 'subject' )
				return say( view.controls.subject.el, view.controls.subject.controls[ work() ] || view.controls.subject.el, about[control] );

			const field = byAbout[ control === 'key' ? 'key' : control ];

			if( field !== undefined )
				say( field, field.querySelector('input, select'), about[control] );
		} );

		// A field of the list: the row that has the word, said in the sentence over the list as well, since the row opens a dialog
		const wrong = Object.keys( fields );

		if( wrong.length > 0 ) {

			const rows = wrong.map( function( index ) { return wording( view.draft.fields[ index ] ) || view.draft.fields[ index ].name } );

			view.fieldsError = el( 'p', 'nino-admin-field-error', Nino.adminUi.format( w('hint/field-refused'), rows.join( ', ' ) ) );
			view.fieldsError.setAttribute( 'role', 'alert' );
			view.fieldsMount.parentNode.insertBefore( view.fieldsError, view.fieldsMount );

			wrong.forEach( function( index ) {
				const row = rowOf( view.fieldsMount, index );
				if( row !== null )
					row.appendChild( el( 'small', 'nino-admin-field-error', w( 'problem/'+ fields[index] ) ) );
			} );

			const target = rowOf( view.fieldsMount, wrong[0] );

			first = first || ( target !== null ? target.querySelector('button') : view.addField );
		}

		if( first !== null ) {
			tabs.select( wrong.length > 0 || about.key !== undefined ? 'fields' : ( about.subject !== undefined || about.to !== undefined || about.ownertpl !== undefined || about.usertpl !== undefined ? 'delivery' : view.tab ) );
			view.tab = tabs.current();
			first.focus();
		}

		return true;
	};

	let tabs = null;

	const save = function( done ) {

		const view = S.view;
		const form = view.form;
		const now = collect();

		Nino.adminUi.clearFieldErrors( form );

		if( view.fieldsError !== null && view.fieldsError.parentNode !== null )
			view.fieldsError.parentNode.removeChild( view.fieldsError );

		Array.prototype.forEach.call( view.fieldsMount.querySelectorAll('small.nino-admin-field-error'), function( note ) { note.parentNode.removeChild( note ) } );

		const problem = function( control, text, tab ) {

			view.bar.line.error( 400, { code : 'forms_fields' }, '/_admin/forms/error/save' );
			tabs.select( tab );
			view.tab = tab;
			Nino.adminUi.fieldError( control, control.querySelector('input, select'), 'forms-error-0', text );
			control.querySelector('input, select').focus();
			done( false );
		};

		if( now.name === '' )
			return problem( view.controls.name, Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/name') ), 'fields' );

		// A form with no field is no form: said over the list, where the button that adds one is
		if( now.fields.length === 0 ) {

			view.fieldsError = el( 'p', 'nino-admin-field-error', w('problem/fields') );
			view.fieldsError.setAttribute( 'role', 'alert' );
			view.fieldsMount.parentNode.insertBefore( view.fieldsError, view.fieldsMount );
			view.bar.line.error( 400, { code : 'forms_fields' }, '/_admin/forms/error/save' );
			tabs.select( 'fields' );
			view.tab = 'fields';
			view.addField.focus();
			done( false );
			return;
		}

		// A form with no key of its own takes it from its name; one that is typed is held to the engine's rule
		if( now.key === '' )
			now.key = deriveFormKey( now.name );

		if( KEY.test( now.key ) === false )
			return problem( view.controls.key, w('problem/key'), 'fields' );

		view.bar.line.saving();

		const body = {
			key : view.was,
			form : {
				key : now.key, name : now.name, to : now.to, subject : now.subject, confirm : now.confirm, ownerTemplate : now.ownerTemplate, userTemplate : now.userTemplate,
				fields : now.fields.map( function( field ) { return { name : field.name, label : field.label, type : field.type, required : field.required === true, options : field.options || [] } } ),
			},
			texts : { subject : now.subjectTexts, fields : now.fields.map( function( field ) { return field.texts || {} } ) },
		};

		Nino.admin.forms.call( 'save', body, function( status, response ) {

			if( status !== 200 || response === null ) {

				view.bar.line.error( status, response, '/_admin/forms/error/save' );

				// The words of the refusal at the field; a duplicate key is the key's
				if( refusal( response ) === false && response !== null && response.code === 'forms_duplicate' )
					refusal( { code : 'forms_fields', params : [ {}, {}, { key : 'keytaken' } ] } );

				done( false );
				return;
			}

			// What the server wrote is what the form is now: its words come back with the next list, the key it has is its address
			Nino.admin.forms.reload( function() {

				const saved = S.forms.find( function( row ) { return row.key === response.form.key } );

				if( saved !== undefined ) {

					const tab = view.tab;

					// The screen is drawn again from what the server holds, the focus stays on the button that was pressed
					showForm( saved, saved.key, tab, { focus : false } );
					S.view.bar.line.saved();
					Nino.admin.router.set( 'forms', partsOf( saved.key, tab ) );
				}

				if( typeof Nino.admin.dirty === 'object' )
					Nino.admin.dirty.refresh();

				done( true );
			} );
		} );
	};

	// The key of a new form from its name: letters and digits, words joined by a hyphen, a letter first, one nobody has
	const deriveFormKey = function( name ) {

		let stem = String( name ).normalize('NFD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );

		stem = stem === '' || /^[0-9]/.test( stem ) === true ? 'form'+ ( stem === '' ? '' : '-'+ stem ) : stem;
		stem = stem.slice( 0, 56 ).replace( /-+$/, '' );

		let key = stem;

		for( let n = 2; S.forms.some( function( form ) { return form.key === key } ); n++ )
			key = stem+ '-'+ n;

		return key;
	};

	/**
	 *	Open the form on its own screen, on a working copy: leaving it unsaved changes nothing that was loaded
	 *
	 *	@param		{Object}		form				The form as the server read it
	 *	@param		{string}		was					The key it is saved under, '' for a new form
	 *	@param		{string}		tab					fields, delivery or protection
	 *	@param		{Object}		[focus]			{ focus : false } where the form was not opened by the person
	 */
	const showForm = function( form, was, tab, focus ) {

		const mount = dc.getElementById('forms-form');
		const draft = copy( form );
		const view = { draft : draft, was : was, tab : tab, controls : {}, form : null, bar : null, fieldsMount : null, fieldsError : null, saved : '' };

		S.view = view;
		mount.innerHTML = '';

		view.changed = function() { if( typeof Nino.admin.dirty === 'object' ) Nino.admin.dirty.refresh() };

		const back = { to : mount, href : Nino.admin.router.href( 'forms', [] ), onClick : function() { go( null ) } };

		mount.appendChild( Nino.adminUi.contextBar( { back : back, title : was === '' ? w('label/new') : form.name } ) );

		const el1 = el( 'form' );
		el1.noValidate = true;
		view.form = el1;

		// Fields: what the form is, then its fields
		const fieldsPane = el('div');
		const about = el('fieldset');
		const grid = el( 'div', 'nino-admin-fieldgrid' );
		const legend = el( 'legend', '', w('label/form') );

		about.appendChild( legend );
		about.appendChild( grid );
		fieldsPane.appendChild( about );

		view.controls.name = Nino.adminUi.textField( { key : 'name', label : w('label/name'), help : w('hint/name'), value : draft.name, required : true, maxLength : 100, id : 'forms-name' } );
		view.controls.key = Nino.adminUi.textField( { key : 'key', label : w('label/key'), help : w('hint/key'), value : draft.key, maxLength : 64, id : 'forms-key', placeholder : w('placeholder/key') } );
		[ view.controls.name, view.controls.key ].forEach( function( field ) { grid.appendChild( field ) } );

		const listTitle = Nino.adminUi.sectionTitle( w('label/fields') );

		fieldsPane.appendChild( listTitle );
		Nino.adminUi.sectionHelp( listTitle, fieldsPane, Nino.adminUi.format( w('hint/fields'), S.reserved.join( ', ' ) ) );

		view.fieldsMount = el('div');
		fieldsPane.appendChild( view.fieldsMount );

		view.addField = Nino.adminUi.button( w('label/addfield'), function() {
			openField( { name : '', label : '', type : 'text', required : false, options : [], texts : {} }, -1, view.addField );
		}, { kind : 'secondary' } );
		fieldsPane.appendChild( view.addField );

		// Delivery: where the mail goes and what it is called
		const deliveryPane = el('div');
		const sent = el('fieldset');
		const sentGrid = el( 'div', 'nino-admin-fieldgrid' );

		sent.appendChild( el( 'legend', '', w('tab/delivery') ) );
		sent.appendChild( sentGrid );
		deliveryPane.appendChild( sent );

		view.controls.to = Nino.adminUi.textField( { key : 'to', label : w('label/to'), help : w('hint/to'), type : 'email', value : draft.to, maxLength : 254, id : 'forms-to', autocomplete : 'off' } );
		view.controls.subject = Nino.adminUi.localeRows( { key : 'subject', label : w('label/subject'), help : w('hint/subject'), locales : S.locales, values : draft.subjectTexts || {}, maxLength : 200 } );
		view.controls.confirm = Nino.adminUi.switchField( { key : 'confirm', checked : draft.confirm === true, label : w('label/confirm'), help : w('hint/confirm') } );
		view.controls.ownerTemplate = templateField( 'ownerTemplate', w('label/ownertpl'), draft.ownerTemplate, Nino.adminUi.format( w('hint/ownertpl'), '[[fields]]' ) );
		view.controls.userTemplate = templateField( 'userTemplate', w('label/usertpl'), draft.userTemplate, Nino.adminUi.format( w('hint/usertpl'), '[[fields]]' ) );

		[ view.controls.to, view.controls.subject.el, view.controls.confirm, view.controls.ownerTemplate, view.controls.userTemplate ].forEach( function( field ) { sentGrid.appendChild( field ) } );

		const items = [
			{ key : 'fields', label : w('tab/fields'), panel : fieldsPane },
			{ key : 'delivery', label : w('tab/delivery'), panel : deliveryPane },
			{ key : 'protection', label : w('tab/protection'), panel : protectionTab() },
		];

		tabs = Nino.adminUi.tabs( items, {
			active		: tab,
			id				: 'forms-tabs',
			label			: w('label/title'),
			onSelect	: function( key ) { view.tab = key; Nino.admin.router.set( 'forms', partsOf( view.was, key ) ) },
		} );

		tabs.current = function() { return view.tab };

		el1.appendChild( tabs.el );
		items.forEach( function( item ) { el1.appendChild( item.panel ) } );

		// The form the kernel falls back to is no definition yet: there is nothing to delete before the first save
		const drop = was === '' || S.isDefault === true ? undefined : Nino.adminUi.button( w('label/delete-form'), function() { removeForm( form, drop ) }, { kind : 'danger' } );

		view.bar = Nino.adminUi.actionBar( {
			danger	: drop,
			status	: true,
			primary	: Nino.adminUi.button( was === '' ? w('label/create') : t('/_admin/common/label/save'), null, { type : 'submit', kind : 'primary' } ),
		} );

		el1.appendChild( view.bar.el );
		view.bar.line.bind( el1 );

		el1.addEventListener( 'submit', function( event ) { event.preventDefault(); save( function() {} ) } );
		mount.appendChild( el1 );

		drawFields();
		view.saved = fingerprint();

		Nino.adminUi.showLevel( 'forms-form', focus === undefined ? { focus : view.controls.name.querySelector('input') } : focus );
	};

	// --- the address --------------------------------------------------------------------------------------------------------------------

	// The parts of an address: the form - the word @new for one that is not saved yet, else its key - and its tab, a word; none for the directory
	const partsOf = function( was, tab ) {
		return was === null ? [] : [ was === '' ? Nino.admin.router.word('new') : was, Nino.admin.router.word( tab ) ];
	};

	// Move to a form and its tab: '' for the form that is not saved yet, null for the directory
	const go = function( was, tab ) {

		const proceed = function() {
			Nino.admin.router.go( 'forms', partsOf( was, tab ) );
			follow( {} );
		};

		if( S.view !== null && S.view.was !== was && typeof Nino.admin.dirty === 'object' )
			return Nino.admin.dirty.guard( [ 'forms' ], proceed, function() {} );

		proceed();
	};

	// What an address names of the panel: the form - '' for @new, null where it names none - and the tab it asks for
	const targetOf = function( parts ) {

		const router = Nino.admin.router;
		const tab = TABS.find( function( name ) { return router.isWord( parts, 1, name ) } ) ?? 'fields';

		if( router.isWord( parts, 0, 'new' ) === true )
			return { was : '', tab : tab };

		// Any other word is none of this panel's; a key is the form of that name
		return { was : parts.length === 0 || router.wordOf( parts, 0 ) !== '' ? null : parts[0], tab : tab };
	};

	// Show what the address names: the directory, a new form or the form of a key at its tab
	const follow = function( focus ) {

		const at = Nino.admin.router.current();
		const target = targetOf( at.panel === 'forms' ? at.parts : [] );
		const tab = target.tab;
		const form = target.was === null || target.was === '' ? undefined : S.forms.find( function( row ) { return row.key === target.was } );

		// The word @new is the form that is not saved yet, at any of its tabs: a form that is called new is a form of that key
		if( target.was === '' ) {

			if( S.view === null || S.view.was !== '' )
				showForm( blank(), '', tab, focus.focus === false ? { focus : false } : undefined );
			else if( S.view.tab !== tab && tabs !== null ) {
				tabs.select( tab );
				S.view.tab = tab;
			}

			Nino.admin.router.set( 'forms', partsOf( '', S.view.tab ) );
			return;
		}

		if( form !== undefined ) {

			if( S.view === null || S.view.was !== form.key )
				showForm( form, form.key, tab, focus.focus === false ? { focus : false } : undefined );
			else if( S.view.tab !== tab && tabs !== null ) {
				tabs.select( tab );
				S.view.tab = tab;
			}

			Nino.admin.router.set( 'forms', partsOf( form.key, S.view.tab ) );
			return;
		}

		S.view = null;
		drawList();
		Nino.adminUi.showLevel( 'forms-list', { focus : false } );
		Nino.admin.router.set( 'forms', [] );
	};

	const addressOf = function() { return S.view === null ? [] : partsOf( S.view.was, S.view.tab ) };

	Nino.admin.forms = Nino.adminUi.panel( {
		uri			: 'forms',
		mount		: 'forms-list',
		show		: 'always',
		error		: '/_admin/common/error/load',
		// What the shell asks before the editor is left with input nobody has saved
		dirty		: {
			isDirty	: function() { return S.view !== null && fingerprint() !== S.view.saved },
			save		: function( done ) { save( done ) },
			discard	: function() { if( S.view !== null ) S.view.saved = fingerprint() },
		},
		render	: function( data ) {

			S.forms = ( data.forms || [] ).map( function( form ) { return Object.assign( form, { fieldCount : form.fields.length } ) } );
			S.types = data.types || [];
			S.reserved = data.reserved || [];
			S.templates = data.templates || [];
			S.locales = data.locales || [];
			S.isDefault = data.default === true;
			S.endpoint = data.endpoint !== false;
			S.retention = data.retention || 3;
			S.store = data.store !== false;
			S.guards = data.guards || {};

			// A list that was asked for again while a form is open (after a save) only brings the data: what is on screen stays
			if( S.view === null )
				follow( { focus : false } );
		},
	} );

	Object.assign( Nino.admin.forms, {

		/**
		 *	Show what the address names, once the data is there; a move to another panel leaves what is on screen as it is
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			const at = Nino.admin.router.current();

			if( Nino.admin.forms.ready === true && at.panel !== 'forms' ) {
				Nino.admin.router.set( 'forms', addressOf() );
				return;
			}

			const proceed = function() { S.view = null; follow( { focus : false } ) };

			if( Nino.admin.forms.ready === false ) {
				Nino.admin.forms.init();
				return;
			}

			const leaving = S.view !== null && targetOf( at.panel === 'forms' ? at.parts : [] ).was !== S.view.was;

			Nino.admin.router.leave( [ 'forms' ], leaving, proceed, function() {
				Nino.admin.router.set( 'forms', addressOf() );
			} );

			// A move inside the form that is open (its tab): nothing to ask
			if( leaving === false )
				follow( { focus : false } );
		},
	} );

} )(window,document);
