/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Vcard						The module's /_admin panel, "vCard": every detail of the
 *													card on one screen - picture, name, line, text, the ways to
 *													reach, links, times - saved at once. Fields that stand in
 *													more than one language of the site have one input each.
 *													See Modules\Vcard\Admin beside this file.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The screen as it is drawn now: the answer, the controls by the name the server knows them by, the rows of links
	let ui = null;
	// What the form held when it was drawn or saved, as the text read() makes of it
	let saved = '';

	const text = function( key ) {
		return Nino.content.getText( key );
	};

	const el = function( tag, className, content ) {
		return Nino.adminUi.el( tag, className, content );
	};

	// The name of a language as the project says it, the code where it has none
	const languageName = function( locale ) {
		return ( ui.data.names || {} )[locale] || locale;
	};

	/**
	 *	A fieldset with its legend: one group of the screen, in the order it is filled in
	 *
	 *	@param		{string}		key					The fill of the legend
	 *
	 *	@return		{Element}
	 */
	const group = function( key ) {

		const set = el( 'fieldset', 'vcard-group' );
		set.appendChild( el( 'legend', '', text( key ) ) );

		return set;
	};

	/**
	 *	A line of text: the workbench's labelled input, named for the error of the server
	 *	and carrying what the project knows as its placeholder
	 *
	 *	@param		{string}		name				The posted key
	 *	@param		{string}		label				The fill of the label
	 *	@param		{string}		value
	 *	@param		{Object}		[options]		{ hint, placeholder, maxLength, type, autocomplete }
	 *
	 *	@return		{Element}									The label
	 */
	const line = function( name, label, value, options ) {

		options = options || {};

		const field = Nino.adminUi.textField( {
			label			: text( label ),
			value			: value,
			hint			: options.hint ? text( options.hint ) : undefined,
			placeholder	: options.placeholder || '',
			maxLength		: options.maxLength,
			type			: options.type || 'text',
			autocomplete	: options.autocomplete || 'off',
		} );

		const input = field.querySelector('input');
		input.dataset.field = name;
		if( options.inputMode )
			input.inputMode = options.inputMode;
		ui.controls[name] = input;

		return field;
	};

	/**
	 *	A text of several lines, as a labelled textarea
	 *
	 *	@param		{string}		name
	 *	@param		{string}		label
	 *	@param		{string}		value
	 *	@param		{Object}		[options]		{ hint, placeholder, maxLength, rows }
	 *
	 *	@return		{Element}
	 */
	const area = function( name, label, value, options ) {

		options = options || {};

		const field = el( 'label', 'nino-admin-field' );
		const area = el( 'textarea', 'nino-admin-input' );

		field.appendChild( el( 'span', '', text( label ) ) );

		area.value = value;
		area.rows = options.rows || 3;
		area.dataset.field = name;
		area.placeholder = options.placeholder || '';
		if( options.maxLength )
			area.maxLength = options.maxLength;
		field.appendChild( area );

		if( options.hint )
			field.appendChild( el( 'small', 'nino-admin-hint', text( options.hint ) ) );

		ui.controls[name] = area;

		return field;
	};

	/**
	 *	A detail that stands in every language of the site: a group with the label once
	 *	and one input for each language, the language's code in front where there is more than one
	 *
	 *	@param		{string}		name				line, text or hours
	 *	@param		{string}		label
	 *	@param		{Object}		options			{ hint, multiline, maxLength, placeholders : { locale : text } }
	 *
	 *	@return		{Element}
	 */
	const languages = function( name, label, options ) {

		const wrap = el( 'div', 'vcard-languages' );
		const title = el( 'span', 'nino-admin-field-name', text( label ) );

		title.id = 'vcard-label-'+ name;
		wrap.setAttribute( 'role', 'group' );
		wrap.setAttribute( 'aria-labelledby', title.id );
		wrap.appendChild( title );

		ui.data.locales.forEach( function( locale ) {

			const own = ui.data.data.texts[locale] || {};
			const field = el( 'label', 'vcard-language' );
			const control = options.multiline === true ? el( 'textarea', 'nino-admin-input' ) : el( 'input', 'nino-admin-input' );

			if( ui.data.locales.length > 1 )
				field.appendChild( el( 'span', 'vcard-language-code', languageName( locale ) ) );

			control.value = own[name] || '';
			control.maxLength = options.maxLength;
			control.dataset.field = 'texts.'+ locale+ '.'+ name;
			control.placeholder = ( options.placeholders || {} )[locale] || '';
			control.setAttribute( 'aria-label', text( label )+ ' ('+ languageName( locale )+ ')' );

			if( options.multiline === true )
				control.rows = 4;
			else
				control.type = 'text';

			field.appendChild( control );
			wrap.appendChild( field );
			ui.controls['texts.'+ locale+ '.'+ name] = control;
		} );

		if( options.hint )
			wrap.appendChild( el( 'small', 'nino-admin-hint', text( options.hint ) ) );

		return wrap;
	};

	const select = function( name, label, value, choices, hint ) {

		const field = Nino.adminUi.selectField( {
			key		: name,
			label	: text( label ),
			value	: value,
			options	: choices.map( function( choice ) { return { value : choice, label : text( '/_admin/vcard/'+ name+ '/'+ choice ) } } ),
			note	: undefined,
		} );

		field.querySelector('select').dataset.field = name;
		ui.controls[name] = field.querySelector('select');

		if( hint )
			field.appendChild( el( 'small', 'nino-admin-hint', text( hint ) ) );

		return field;
	};

	const toggle = function( name, label, hint, checked ) {

		const field = Nino.adminUi.switchField( { key : name, checked : checked, label : text( label ), hint : hint ? text( hint ) : undefined } );

		ui.controls[name] = field.querySelector('input');

		return field;
	};

	/**
	 *	Where the page is, and the way to look at it, and - where the card is not on / - the button that gives it /.
	 *	What it says follows the routes as they are: it says only what is the case
	 *
	 *	@return		{Element}
	 */
	const where = function() {

		const page = ui.data.page;
		const box = el( 'div', 'vcard-where' );
		const states = {
			start		: text('/_admin/vcard/status/start'),
			page		: Nino.adminUi.format( text('/_admin/vcard/status/page'), page.path ),
			detached	: Nino.adminUi.format( text('/_admin/vcard/status/detached'), page.path ),
			none		: text('/_admin/vcard/status/none'),
		};

		box.appendChild( Nino.adminUi.notice( states[page.state] ) );

		if( page.takenUri !== '' )
			box.appendChild( Nino.adminUi.hint( Nino.adminUi.format( text('/_admin/vcard/status/taken'), page.takenUri ) ) );

		if( page.state === 'start' || page.state === 'page' ) {
			const open = el( 'a', 'nino-admin-btn--small vcard-open', text('/_admin/vcard/label/open') );
			open.href = page.path;
			open.target = '_blank';
			open.rel = 'noopener';
			box.appendChild( open );
		}

		if( page.start !== null ) {

			const start = Nino.adminUi.button( text('/_admin/vcard/label/start'), function() { takeStart( start ) }, { className : 'nino-admin-btn--small vcard-start', disabled : page.start.blocked } );

			box.appendChild( start );

			if( page.start.blocked === true )
				box.appendChild( Nino.adminUi.hint( text( page.start.reason === 'template' ? '/_admin/vcard/hint/start-template' : '/_admin/vcard/hint/start-blocked' ), 'error' ) );
		}

		if( page.state === 'start' )
			box.appendChild( Nino.adminUi.hint( text('/_admin/vcard/hint/off') ) );

		return box;
	};

	/**
	 *	Ask, and give / to the card. The question says where the page that holds / goes; the screen of the
	 *	form is left as it is, so that what has been typed stays - only the part that says where the page is
	 *	is drawn again
	 *
	 *	@param		{Element}		opener			The button, which the focus goes back to
	 *
	 *	@return		void
	 */
	const takeStart = function( opener ) {

		const plan = ui.data.page.start;

		Nino.adminUi.confirm( 'big', {
			message	: [ plan.moves === true ? Nino.adminUi.format( text('/_admin/vcard/confirm/start-moves'), plan.name, plan.to ) : text('/_admin/vcard/confirm/start-free'), plan.gone.length > 0 ? Nino.adminUi.format( text('/_admin/vcard/confirm/start-gone'), plan.gone.join(', ') ) : '', text('/_admin/vcard/confirm/start-ask') ].filter( function( part ) { return part !== '' } ).join(' '),
			yes			: text('/_admin/vcard/label/start'),
			no			: text('/_admin/vcard/label/cancel'),
			opener		: opener,
			onChoose	: function( yes ) {

				if( yes === false )
					return;

				Nino.admin.vcard.call( 'start', {}, function( status, response ) {

					if( status !== 200 || response === null ) {
						ui.line.error( status, response, '/_admin/vcard/error/save' );
						return;
					}

					ui.data.page = response.page;

					const fresh = where();
					ui.where.replaceWith( fresh );
					ui.where = fresh;

					const said = response.moved.to !== '' ? Nino.adminUi.format( text('/_admin/vcard/msg/moved'), response.moved.name, response.moved.to ) : text('/_admin/vcard/msg/started');
					ui.live.textContent = said;
					ui.line.idle( said );
					( fresh.querySelector('.vcard-open') ?? fresh ).focus();
				} );
			},
		} );
	};

	/**
	 *	The picture: which one the card shows, and the photo itself
	 *
	 *	@return		{Element}
	 */
	const picture = function() {

		const set = group('/_admin/vcard/heading/picture');

		set.appendChild( select( 'picture', '/_admin/vcard/label/picture', ui.data.data.picture, [ 'auto', 'photo', 'logo', 'none' ] ) );

		ui.photo = el( 'div', 'vcard-photo' );
		set.appendChild( ui.photo );
		drawPhoto();

		return set;
	};

	const drawPhoto = function() {

		const box = ui.photo;
		const photo = ui.data.photo;

		box.innerHTML = '';
		ui.alt = {};

		const row = el( 'div', 'vcard-photo-row' );

		if( photo.url !== null ) {
			const img = el( 'img', 'vcard-photo-img' );
			img.src = photo.url;
			img.alt = '';
			img.width = 96;
			img.height = 96;
			row.appendChild( img );
		}
		else
			row.appendChild( el( 'p', 'nino-admin-hint', text('/_admin/vcard/hint/photo-none') ) );

		const actions = el( 'div', 'nino-admin-actions' );
		const file = el( 'input' );
		file.type = 'file';
		file.accept = 'image/*';
		file.hidden = true;
		file.addEventListener( 'change', function() {
			if( file.files.length > 0 )
				upload( file.files[0] );
			file.value = '';
		} );

		actions.appendChild( file );
		actions.appendChild( Nino.adminUi.button( text( photo.url === null ? '/_admin/vcard/label/photo-choose' : '/_admin/vcard/label/photo-replace' ), function() { file.click() }, { className : 'nino-admin-btn--small' } ) );

		if( photo.url !== null )
			actions.appendChild( Nino.adminUi.button( text('/_admin/vcard/label/photo-remove'), function( event ) { ask( event.currentTarget ) }, { className : 'nino-admin-btn--small nino-admin-btn-danger' } ) );

		row.appendChild( actions );
		box.appendChild( row );
		box.appendChild( el( 'small', 'nino-admin-hint', Nino.adminUi.format( text('/_admin/vcard/hint/photo'), photo.size, photo.size ) ) );

		if( photo.url !== null ) {

			const alt = el( 'div', 'vcard-languages' );
			const title = el( 'span', 'nino-admin-field-name', text('/_admin/vcard/label/photo-alt') );

			title.id = 'vcard-label-alt';
			alt.setAttribute( 'role', 'group' );
			alt.setAttribute( 'aria-labelledby', title.id );
			alt.appendChild( title );

			ui.data.locales.forEach( function( locale ) {

				const field = el( 'label', 'vcard-language' );
				const input = el( 'input', 'nino-admin-input' );

				if( ui.data.locales.length > 1 )
					field.appendChild( el( 'span', 'vcard-language-code', languageName( locale ) ) );

				input.type = 'text';
				input.value = photo.alt[locale] || '';
				input.maxLength = ui.data.limits.alt;
				input.dataset.field = 'alt.'+ locale;
				input.setAttribute( 'aria-label', text('/_admin/vcard/label/photo-alt')+ ' ('+ languageName( locale )+ ')' );
				field.appendChild( input );
				alt.appendChild( field );
				ui.alt[locale] = input;
			} );

			alt.appendChild( el( 'small', 'nino-admin-hint', text('/_admin/vcard/hint/photo-alt') ) );
			box.appendChild( alt );
		}

		if( ui.data.logo === false )
			box.appendChild( Nino.adminUi.notice( text('/_admin/vcard/hint/logo-none'), { href : '#images', label : text('/_admin/vcard/label/images') } ) );
	};

	/**
	 *	Send a chosen photo. It is saved on its own, as the Images panel saves one: a photo
	 *	is a file on the server, not a value of the form, so the rest of the form is left as it is
	 *
	 *	@param		{File}			file
	 *
	 *	@return		void
	 */
	const upload = function( file ) {

		Nino.adminUi.checkImage( file, function( problem ) {

			if( problem !== null ) {
				ui.line.fail( Nino.adminUi.api.errorText( 400, problem, '/_admin/vcard/error/upload' ) );
				return;
			}

			ui.line.idle( text('/_admin/vcard/msg/uploading') );

			Nino.admin.vcard.call( 'upload', {}, function( status, response ) {

				if( status !== 200 || response === null ) {
					ui.line.error( status, response, '/_admin/vcard/error/upload' );
					return;
				}

				ui.data.photo = response.photo;
				ui.data.logo = response.logo;
				drawPhoto();
				ui.line.idle( text('/_admin/vcard/msg/uploaded') );
				ui.live.textContent = text('/_admin/vcard/msg/uploaded');
				refresh();
			}, { file : file } );
		} );
	};

	const ask = function( opener ) {

		Nino.adminUi.confirm( 'destructive', {
			message	: text('/_admin/vcard/confirm/photo-remove'),
			yes			: text('/_admin/vcard/label/photo-remove'),
			no			: text('/_admin/vcard/label/cancel'),
			opener		: opener,
			onChoose	: function( yes ) {
				if( yes === true )
					remove();
			},
		} );
	};

	const remove = function() {

		Nino.admin.vcard.call( 'remove', {}, function( status, response ) {

			if( status !== 200 || response === null ) {
				ui.line.error( status, response, '/_admin/vcard/error/upload' );
				return;
			}

			ui.data.photo = response.photo;
			drawPhoto();
			ui.live.textContent = text('/_admin/vcard/msg/removed');
			ui.line.idle( text('/_admin/vcard/msg/removed') );
			refresh();
		} );
	};

	/**
	 *	The links: a row each - the address, a name in every language - that can be moved
	 *	and removed, and one button to add
	 *
	 *	@return		{Element}
	 */
	const links = function() {

		const set = group('/_admin/vcard/heading/links');

		set.appendChild( el( 'p', 'nino-admin-hint', Nino.adminUi.format( text('/_admin/vcard/hint/links'), ui.data.limits.links ) ) );

		ui.list = el( 'ul', 'vcard-links' );
		ui.rows = [];
		ui.empty = el( 'p', 'nino-admin-hint', text('/_admin/vcard/hint/links-none') );

		set.appendChild( ui.empty );
		set.appendChild( ui.list );

		ui.data.data.links.forEach( function( link ) { addRow( link ) } );

		ui.add = Nino.adminUi.button( text('/_admin/vcard/label/link-add'), function() {
			const row = addRow( { id : '', url : '', labels : {} } );
			row.url.focus();
			refresh();
		}, { className : 'nino-admin-btn--small' } );

		set.appendChild( ui.add );
		arrange();

		return set;
	};

	const addRow = function( link ) {

		const item = el( 'li', 'vcard-link' );
		const row = { id : link.id || Math.random().toString( 16 ).slice( 2, 10 ), item : item, url : null, labels : {} };
		const fields = el( 'div', 'vcard-link-fields' );

		const address = Nino.adminUi.textField( { label : text('/_admin/vcard/label/link-url'), value : link.url, maxLength : ui.data.limits.url, type : 'text', autocomplete : 'off' } );
		row.url = address.querySelector('input');
		row.url.inputMode = 'url';
		fields.appendChild( address );

		const names = el( 'div', 'vcard-languages' );
		ui.data.locales.forEach( function( locale ) {

			const field = el( 'label', 'vcard-language' );
			const input = el( 'input', 'nino-admin-input' );

			if( ui.data.locales.length > 1 )
				field.appendChild( el( 'span', 'vcard-language-code', languageName( locale ) ) );

			input.type = 'text';
			input.value = ( link.labels || {} )[locale] || '';
			input.maxLength = ui.data.limits.label;
			input.setAttribute( 'aria-label', text('/_admin/vcard/label/link-label')+ ' ('+ languageName( locale )+ ')' );
			input.placeholder = text('/_admin/vcard/label/link-label');
			field.appendChild( input );
			names.appendChild( field );
			row.labels[locale] = input;
		} );
		fields.appendChild( names );

		const move = el( 'div', 'nino-admin-move' );
		row.up = Nino.adminUi.button( '↑', function() { shift( row, -1 ) }, { name : text('/_admin/vcard/label/link-up') } );
		row.down = Nino.adminUi.button( '↓', function() { shift( row, 1 ) }, { name : text('/_admin/vcard/label/link-down') } );
		row.drop = Nino.adminUi.button( '×', function() { drop( row ) }, { name : text('/_admin/vcard/label/link-remove'), className : 'nino-admin-btn-danger' } );
		move.appendChild( row.up );
		move.appendChild( row.down );
		move.appendChild( row.drop );

		item.appendChild( fields );
		item.appendChild( move );
		ui.list.appendChild( item );
		ui.rows.push( row );
		arrange();

		return row;
	};

	const shift = function( row, by ) {

		const at = ui.rows.indexOf( row );
		const to = at+ by;

		if( to < 0 || to >= ui.rows.length )
			return;

		ui.rows.splice( at, 1 );
		ui.rows.splice( to, 0, row );
		ui.rows.forEach( function( each ) { each.item.remove() } );
		ui.rows.forEach( function( each ) { ui.list.appendChild( each.item ) } );
		arrange();
		( by < 0 && to === 0 ? row.down : ( by > 0 && to === ui.rows.length - 1 ? row.up : ( by < 0 ? row.up : row.down ) ) ).focus();
		refresh();
	};

	const drop = function( row ) {

		const at = ui.rows.indexOf( row );

		ui.rows.splice( at, 1 );
		row.item.remove();
		arrange();
		ui.live.textContent = text('/_admin/vcard/msg/link-removed');

		const next = ui.rows[ Math.min( at, ui.rows.length - 1 ) ];
		( next ? next.url : ui.add ).focus();
		refresh();
	};

	// The ends of the list cannot move further, the add button stops at the limit, an empty list says so
	const arrange = function() {

		ui.rows.forEach( function( row, index ) {
			row.up.disabled = index === 0;
			row.down.disabled = index === ui.rows.length - 1;

			// Where the server names the field of a refused link, it is by place in the list
			row.url.dataset.field = 'links.'+ index+ '.url';
			Object.keys( row.labels ).forEach( function( locale ) { row.labels[locale].dataset.field = 'links.'+ index+ '.labels.'+ locale } );
		} );

		ui.empty.hidden = ui.rows.length > 0;

		if( ui.add )
			ui.add.disabled = ui.rows.length >= ui.data.limits.links;
	};

	/**
	 *	What the form holds, as the server takes it
	 *
	 *	@param		{boolean}		[full]			Whether a cleared description of the photo is sent too, which it needs to be removed
	 *
	 *	@return		{Object}
	 */
	const read = function( full ) {

		const value = function( name ) { return ui.controls[name].value };
		const out = {
			picture	: value('picture'),
			kind		: value('kind'),
			name		: value('name'),
			phone		: value('phone'),
			email		: value('email'),
			website		: value('website'),
			address		: value('address'),
			map			: ui.controls.map.checked,
			save		: ui.controls.save.checked,
			texts		: {},
			links		: [],
			alt			: {},
		};

		ui.data.locales.forEach( function( locale ) {
			out.texts[locale] = { line : value('texts.'+ locale+ '.line'), text : value('texts.'+ locale+ '.text'), hours : value('texts.'+ locale+ '.hours') };

			if( ui.alt[locale] && ( full === true || ui.alt[locale].value !== '' ) )
				out.alt[locale] = ui.alt[locale].value;
		} );

		ui.rows.forEach( function( row ) {
			const labels = {};
			ui.data.locales.forEach( function( locale ) { labels[locale] = row.labels[locale].value } );
			out.links.push( { id : row.id, url : row.url.value, labels : labels } );
		} );

		return out;
	};

	const refresh = function() {
		if( typeof Nino.admin.dirty === 'object' )
			Nino.admin.dirty.refresh();
	};

	/**
	 *	The sentence of a refusal at the field the server named, which the field points at with aria-describedby
	 *
	 *	@param		{number}		status
	 *	@param		{Object}		response
	 *
	 *	@return		void
	 */
	const refused = function( status, response ) {

		const field = response !== null && typeof response.field === 'string' && /^[A-Za-z0-9_.-]+$/.test( response.field ) ? ui.form.querySelector( '[data-field="'+ response.field+ '"]' ) : null;

		if( field !== null )
			Nino.adminUi.fieldError( field.closest('label') ?? field.parentNode, field, 'vcard-field-error', Nino.adminUi.api.errorText( status, response, '/_admin/vcard/error/save' ) );
	};

	const save = function( done ) {

		ui.save.disabled = true;
		ui.line.saving();
		Nino.adminUi.clearFieldErrors( ui.form );

		Nino.admin.vcard.call( 'save', read( true ), function( status, response ) {

			ui.save.disabled = false;

			if( status !== 200 || response === null ) {
				ui.line.error( status, response, '/_admin/vcard/error/save' );
				refused( status, response );
				done( false );
				return;
			}

			const panel = ui.panel;
			panel.data = response;
			draw( response, panel );
			ui.line.saved();
			ui.save.focus();
			refresh();
			done( true );
		} );
	};

	const draw = function( data, panel ) {

		const mount = panel.mount();

		ui = { data : data, panel : panel, controls : {}, rows : [], alt : {}, photo : null, where : null, form : null, list : null, empty : null, add : null, line : null, save : null, live : null };
		mount.innerHTML = '';

		const form = el( 'form', 'vcard-form' );
		ui.form = form;
		const stored = data.data;
		const inherited = data.inherited;

		form.noValidate = true;
		form.addEventListener( 'submit', function( event ) {
			event.preventDefault();
			save( function() {} );
		} );

		form.appendChild( el( 'p', 'nino-admin-hint', text('/_admin/vcard/hint/intro') ) );
		ui.where = where();
		form.appendChild( ui.where );
		form.appendChild( picture() );

		const words = group('/_admin/vcard/heading/name');
		words.appendChild( line( 'name', '/_admin/vcard/label/name', stored.name, { hint : '/_admin/vcard/hint/name', placeholder : inherited.name, maxLength : data.limits.name, autocomplete : 'name' } ) );
		words.appendChild( select( 'kind', '/_admin/vcard/label/kind', stored.kind, [ 'person', 'organization' ], '/_admin/vcard/hint/kind' ) );
		words.appendChild( languages( 'line', '/_admin/vcard/label/line', { hint : '/_admin/vcard/hint/line', maxLength : data.limits.line } ) );
		words.appendChild( languages( 'text', '/_admin/vcard/label/text', { hint : '/_admin/vcard/hint/text', maxLength : data.limits.text, multiline : true, placeholders : inherited.text } ) );
		form.appendChild( words );

		const contact = group('/_admin/vcard/heading/contact');
		contact.appendChild( el( 'p', 'nino-admin-hint', text('/_admin/vcard/hint/contact') ) );
		contact.appendChild( line( 'phone', '/_admin/vcard/label/phone', stored.phone, { placeholder : inherited.phone, maxLength : data.limits.phone, inputMode : 'tel', autocomplete : 'tel' } ) );
		contact.appendChild( line( 'email', '/_admin/vcard/label/email', stored.email, { placeholder : inherited.email, maxLength : data.limits.email, type : 'email', autocomplete : 'email' } ) );
		contact.appendChild( line( 'website', '/_admin/vcard/label/website', stored.website, { placeholder : inherited.website, maxLength : data.limits.url, type : 'url', autocomplete : 'url' } ) );
		contact.appendChild( area( 'address', '/_admin/vcard/label/address', stored.address, { hint : '/_admin/vcard/hint/address', placeholder : inherited.address, maxLength : data.limits.address, rows : 4 } ) );
		contact.appendChild( toggle( 'map', '/_admin/vcard/label/map', '/_admin/vcard/hint/map', stored.map ) );
		form.appendChild( contact );

		form.appendChild( links() );

		const times = group('/_admin/vcard/heading/times');
		times.appendChild( languages( 'hours', '/_admin/vcard/label/hours', { hint : '/_admin/vcard/hint/hours', maxLength : data.limits.hours } ) );
		form.appendChild( times );

		const file = group('/_admin/vcard/heading/file');
		file.appendChild( toggle( 'save', '/_admin/vcard/label/save-button', '/_admin/vcard/hint/save-button', stored.save ) );
		form.appendChild( file );

		const legal = group('/_admin/vcard/heading/legal');
		legal.appendChild( el( 'p', 'nino-admin-hint', text( data.legal.active === true ? '/_admin/vcard/hint/legal' : '/_admin/vcard/hint/legal-none' ) ) );
		if( data.legal.active === true ) {
			const row = el( 'div', 'nino-admin-actions' );
			[ [ '#elements', '/_admin/vcard/label/elements' ], [ data.legal.imprint, '/_admin/vcard/label/legal-imprint' ], [ data.legal.privacy, '/_admin/vcard/label/legal-privacy' ] ].forEach( function( entry, index ) {
				const link = el( 'a', 'nino-admin-btn--small', text( entry[1] ) );
				link.href = entry[0];
				if( index > 0 ) {
					link.target = '_blank';
					link.rel = 'noopener';
				}
				row.appendChild( link );
			} );
			legal.appendChild( row );
		}
		form.appendChild( legal );

		// Said to a screen reader when a row goes or a photo is saved: nothing else on the screen changes where the eye is
		ui.live = el( 'p', 'nino-admin-sr-only' );
		ui.live.setAttribute( 'role', 'status' );
		form.appendChild( ui.live );

		const message = el( 'span' );
		const bar = Nino.adminUi.actionBar( el( 'div' ) );

		ui.line = Nino.adminUi.status( message );
		ui.save = Nino.adminUi.button( text('/_admin/vcard/label/save'), null, { type : 'submit', className : 'nino-admin-btn-primary' } );
		ui.line.bind( form, function() { return dirty() } );

		bar.appendChild( ui.line.element );
		bar.appendChild( ui.save );
		form.appendChild( bar );

		mount.appendChild( form );
		saved = JSON.stringify( read( false ) );
	};

	const dirty = function() {
		return ui !== null && ui.line !== null && JSON.stringify( read( false ) ) !== saved;
	};

	Nino.admin.vcard = Nino.adminUi.panel( {
		uri		: 'vcard',
		mount	: 'vcard-list',
		dirty	: { isDirty : dirty, save : save, discard : function() { if( ui !== null ) draw( ui.panel.data, ui.panel ) } },
		render	: draw,
	} );

} )(window,document);
