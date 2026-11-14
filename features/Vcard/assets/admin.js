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

	// Ids of the help texts, so that a control can name the text that describes it
	let helps = 0;

	// What a click or a tap has pinned open, which a press elsewhere closes; and every text that is open, which Escape closes
	let pinned = null;
	const shown = new Set();

	/**
	 *	A "?" beside a heading or a label: a button that opens a short text under it. The text
	 *	shows while the pointer is over the button or over the text, while the button has the
	 *	focus and, after a click or a tap, until the next one or a press elsewhere. It stays
	 *	while any of that holds and Escape closes it, with the focus or without. The control it
	 *	belongs to names the text with aria-describedby, so a screen reader reads it with the
	 *	field whether it is shown or not; the button is named by what it explains
	 *
	 *	@param		{string}		owner				The words of what the "?" explains: a label, a heading
	 *	@param		{string}		key					The fill of the text
	 *	@param		{...*}			[values]		What a %s of the text says
	 *
	 *	@return		{Element}									<span class="vcard-help">, its button is .vcard-help-button and its text .vcard-help-text
	 */
	const help = function( owner, key, ...values ) {

		const wrap = el( 'span', 'vcard-help' );
		const button = el( 'button', 'vcard-help-button', '?' );
		const body = el( 'span', 'vcard-help-text', values.length > 0 ? Nino.adminUi.format( text( key ), ...values ) : text( key ) );
		let hover = false;
		let focus = false;
		let fixed = false;

		body.id = 'vcard-help-'+ ( ++helps );
		body.hidden = true;
		button.type = 'button';
		button.setAttribute( 'aria-label', text('/_admin/vcard/label/help')+ ': '+ owner );
		button.setAttribute( 'aria-describedby', body.id );
		button.setAttribute( 'aria-expanded', 'false' );

		const show = function( open ) {

			body.hidden = open === false;
			button.setAttribute( 'aria-expanded', open === true ? 'true' : 'false' );

			if( open === true )
				shown.add( release );
			else
				shown.delete( release );

			// Kept inside the screen: a text that would pass the right edge is moved back by what it passes
			if( open === true && typeof body.getBoundingClientRect === 'function' ) {
				body.style.marginInlineStart = '0px';
				const box = body.getBoundingClientRect();
				const over = box.right - ( dc.documentElement.clientWidth - 8 );
				body.style.marginInlineStart = over > 0 ? '-'+ Math.ceil( over )+ 'px' : '0px';
			}
		};

		const settle = function() { show( hover || focus || fixed ) };

		// Closed whatever held it open: Escape, a press elsewhere, a second click
		const release = function() {
			fixed = false;
			hover = false;
			focus = false;
			show( false );
			if( pinned === release )
				pinned = null;
		};

		// The pointer is over the "?" or over its text, which both stand in the span: moving from the one to the other keeps it open
		wrap.addEventListener( 'mouseenter', function() { hover = true; settle() } );
		wrap.addEventListener( 'mouseleave', function() { hover = false; settle() } );
		button.addEventListener( 'focus', function() { focus = true; settle() } );
		button.addEventListener( 'blur', function() { focus = false; settle() } );
		button.addEventListener( 'click', function() {

			if( fixed === true ) {
				release();
				return;
			}

			if( pinned !== null )
				pinned();

			fixed = true;
			pinned = release;
			settle();
		} );

		wrap.appendChild( button );
		wrap.appendChild( body );

		return wrap;
	};

	// A press elsewhere than on a help closes the one a click left open; Escape closes every text that is open, from anywhere
	dc.addEventListener( 'pointerdown', function( event ) {
		if( pinned !== null && event.target.closest && event.target.closest('.vcard-help') === null )
			pinned();
	} );
	dc.addEventListener( 'keydown', function( event ) {
		if( event.key === 'Escape' )
			Array.from( shown ).forEach( function( release ) { release() } );
	} );

	/**
	 *	The text of a "?" put at the end of the name of a control, and the control pointed at it
	 *
	 *	@param		{Element}		name				The element that holds the name: a span, a title
	 *	@param		{string}		label				The words of the name
	 *	@param		{Element}		control			The input, select or textarea
	 *	@param		{string}		key					The fill of the text
	 *	@param		{...*}			[values]
	 *
	 *	@return		void
	 */
	const helped = function( name, label, control, key, ...values ) {

		const wrap = help( label, key, ...values );
		const words = el( 'span', '', label );

		// The name of the control is the words alone, not the "?" that stands behind them
		words.id = 'vcard-name-'+ ( ++helps );
		name.textContent = '';
		name.appendChild( words );
		name.appendChild( wrap );
		control.setAttribute( 'aria-labelledby', words.id );
		control.setAttribute( 'aria-describedby', wrap.querySelector('.vcard-help-text').id );
	};

	/**
	 *	The controls of a group that a block that is off turns off: every input, select, textarea
	 *	and button below the legend but the "?" buttons, which explain what is off and stay readable
	 *
	 *	@param		{Element}		node
	 *	@param		{Array}			[found]
	 *
	 *	@return		{Array}
	 */
	const controls = function( node, found ) {

		found = found || [];

		Array.from( node.children ).forEach( function( child ) {

			if( child.tagName === 'LEGEND' || child.classList.contains('vcard-help') )
				return;

			if( [ 'INPUT', 'SELECT', 'TEXTAREA', 'BUTTON' ].indexOf( child.tagName ) !== -1 )
				found.push( child );

			controls( child, found );
		} );

		return found;
	};

	/**
	 *	Turn a block on or off: off, its controls are disabled - what each had before is kept, so that
	 *	a button that stood disabled by itself stands so again - and the styles dim what is below the legend
	 *
	 *	@param		{Element}		set
	 *	@param		{boolean}		on
	 *
	 *	@return		void
	 */
	const switchBlock = function( set, on ) {

		set.classList.toggle( 'is-off', on === false );

		controls( set ).forEach( function( control ) {

			if( on === false && control.dataset.was === undefined ) {
				control.dataset.was = control.disabled === true ? '1' : '0';
				control.disabled = true;
			}
			else if( on === true && control.dataset.was !== undefined ) {
				control.disabled = control.dataset.was === '1';
				delete control.dataset.was;
			}
		} );
	};

	/**
	 *	A fieldset with its legend: one group of the screen, in the order it is filled in. A group
	 *	that is a block of the card has a switch in its legend; off, its controls are disabled (see
	 *	switchBlock()) and the styles dim what is below the legend
	 *
	 *	@param		{string}		key					The fill of the legend
	 *	@param		{string}		[block]			The block of the card the group is: one of the keys of data.blocks
	 *	@param		{string}		[note]			The fill of the "?" at the legend
	 *	@param		{...*}			[values]
	 *
	 *	@return		{Element}
	 */
	const group = function( key, block, note, ...values ) {

		const set = el( 'fieldset', 'vcard-group' );
		const legend = el( 'legend' );

		const heading = el( 'span', 'vcard-legend-text', text( key ) );

		if( note )
			heading.appendChild( help( text( key ), note, ...values ) );

		legend.appendChild( heading );

		if( block ) {

			set.dataset.block = block;

			const field = Nino.adminUi.switchField( { key : 'block.'+ block, checked : ui.data.data.blocks[block] !== false, label : '' } );
			const input = field.querySelector('input');

			field.classList.add('vcard-block-switch');
			input.setAttribute( 'aria-label', text( key )+ ': '+ text('/_admin/vcard/label/block') );
			input.addEventListener( 'change', function() { switchBlock( set, input.checked ) } );
			ui.blocks[block] = input;
			ui.off.push( function() { switchBlock( set, input.checked ) } );
			legend.appendChild( field );
		}

		set.appendChild( legend );

		return set;
	};

	/**
	 *	A line of text: the workbench's labelled input, named for the error of the server
	 *	and carrying what the project knows as its placeholder
	 *
	 *	@param		{string}		name				The posted key
	 *	@param		{string}		label				The fill of the label
	 *	@param		{string}		value
	 *	@param		{Object}		[options]		{ help, placeholder, maxLength, type, autocomplete }
	 *
	 *	@return		{Element}									The label
	 */
	const line = function( name, label, value, options ) {

		options = options || {};

		const field = Nino.adminUi.textField( {
			label			: text( label ),
			value			: value,
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

		if( options.help )
			helped( field.querySelector('span'), text( label ), input, options.help );

		return field;
	};

	/**
	 *	A text of several lines, as a labelled textarea
	 *
	 *	@param		{string}		name
	 *	@param		{string}		label
	 *	@param		{string}		value
	 *	@param		{Object}		[options]		{ help, placeholder, maxLength, rows }
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

		if( options.help )
			helped( field.querySelector('span'), text( label ), area, options.help );

		ui.controls[name] = area;

		return field;
	};

	/**
	 *	A detail that stands in every language of the site: a group with the label once
	 *	and one input for each language, the language's code in front where there is more than one
	 *
	 *	@param		{string}		name				line, text or hours
	 *	@param		{string}		label
	 *	@param		{Object}		options			{ help, multiline, maxLength, placeholders : { locale : text } }
	 *
	 *	@return		{Element}
	 */
	const languages = function( name, label, options ) {

		const wrap = el( 'div', 'vcard-languages' );
		const title = el( 'span', 'nino-admin-field-name' );
		const words = el( 'span', '', text( label ) );

		words.id = 'vcard-label-'+ name;
		title.appendChild( words );
		wrap.setAttribute( 'role', 'group' );
		wrap.setAttribute( 'aria-labelledby', words.id );
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

		if( options.help ) {
			const note = help( text( label ), options.help );
			title.appendChild( note );
			wrap.setAttribute( 'aria-describedby', note.querySelector('.vcard-help-text').id );
		}

		return wrap;
	};

	const select = function( name, label, value, choices, note ) {

		const field = Nino.adminUi.selectField( {
			key		: name,
			label	: text( label ),
			value	: value,
			options	: choices.map( function( choice ) { return { value : choice, label : text( '/_admin/vcard/'+ name+ '/'+ choice ) } } ),
			note	: undefined,
		} );

		field.querySelector('select').dataset.field = name;
		ui.controls[name] = field.querySelector('select');

		if( note )
			helped( field.querySelector('span'), text( label ), ui.controls[name], note );

		return field;
	};

	const toggle = function( name, label, note, checked ) {

		const field = Nino.adminUi.switchField( { key : name, checked : checked, label : text( label ) } );
		const input = field.querySelector('input');

		ui.controls[name] = input;

		if( note ) {

			const copy = field.querySelector('.nino-admin-switch-copy');
			const words = el( 'span', '', text( label ) );
			const wrap = help( text( label ), note );

			words.id = 'vcard-name-'+ ( ++helps );
			copy.textContent = '';
			copy.appendChild( words );
			copy.appendChild( wrap );
			input.setAttribute( 'aria-labelledby', words.id );
			input.setAttribute( 'aria-describedby', wrap.querySelector('.vcard-help-text').id );
		}

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

		// Said only where the card is not the start page: on / there is nothing to point out
		if( page.state !== 'start' ) {

			const notice = Nino.adminUi.notice( states[page.state] );

			if( page.takenUri !== '' )
				notice.appendChild( help( states[page.state], '/_admin/vcard/status/taken', page.takenUri ) );

			box.appendChild( notice );
		}

		if( page.state === 'start' || page.state === 'page' ) {
			const open = el( 'a', 'nino-admin-btn--small vcard-open', text('/_admin/vcard/label/open') );
			open.href = page.path;
			open.target = '_blank';
			open.rel = 'noopener';
			box.appendChild( open );

			// What to do before the feature is switched off, said only where it matters: with the card on /
			if( page.state === 'start' )
				box.appendChild( help( text('/_admin/vcard/label/open'), '/_admin/vcard/hint/off' ) );
		}

		if( page.start !== null ) {

			const start = Nino.adminUi.button( text('/_admin/vcard/label/start'), function() { takeStart( start ) }, { className : 'nino-admin-btn--small vcard-start', disabled : page.start.blocked } );

			box.appendChild( start );

			if( page.start.blocked === true )
				box.appendChild( Nino.adminUi.hint( text( page.start.reason === 'template' ? '/_admin/vcard/hint/start-template' : '/_admin/vcard/hint/start-blocked' ), 'error' ) );
		}

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

		const set = group( '/_admin/vcard/heading/picture', 'picture' );
		const choice = select( 'picture', '/_admin/vcard/label/picture', ui.data.data.picture, [ 'auto', 'photo', 'logo', 'none' ] );

		set.appendChild( choice );

		ui.photo = el( 'div', 'vcard-photo' );
		set.appendChild( ui.photo );
		drawPhoto();

		// The photo and what is said of it are drawn only where the choice can show a photo
		const follow = function() { ui.photo.hidden = [ 'auto', 'photo' ].indexOf( ui.controls.picture.value ) === -1 };
		ui.controls.picture.addEventListener( 'change', follow );
		follow();

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
			row.appendChild( el( 'span', 'vcard-empty', text('/_admin/vcard/hint/photo-none') ) );

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

		actions.appendChild( help( text('/_admin/vcard/label/photo-choose'), '/_admin/vcard/hint/photo', photo.size, photo.size ) );
		row.appendChild( actions );
		box.appendChild( row );

		if( photo.url !== null ) {

			const alt = el( 'div', 'vcard-languages' );
			const title = el( 'span', 'nino-admin-field-name' );
			const words = el( 'span', '', text('/_admin/vcard/label/photo-alt') );
			const note = help( text('/_admin/vcard/label/photo-alt'), '/_admin/vcard/hint/photo-alt' );

			words.id = 'vcard-label-alt';
			title.appendChild( words );
			title.appendChild( note );
			alt.setAttribute( 'role', 'group' );
			alt.setAttribute( 'aria-labelledby', words.id );
			alt.setAttribute( 'aria-describedby', note.querySelector('.vcard-help-text').id );
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

			box.appendChild( alt );
		}
	};

	/**
	 *	A choice of one among a few, as native radio buttons in a group of their own: the keys of the
	 *	keyboard and the reading of a screen reader are the browser's
	 *
	 *	@param		{string}		name				The name of the choice, which is how read() finds it
	 *	@param		{string}		legend			The words that name the group
	 *	@param		{string}		value				The one that is on
	 *	@param		{Array}			options			[ { value, label, picture } ], the picture an element to show above the words
	 *	@param		{string}		[className]
	 *
	 *	@return		{Element}
	 */
	const choice = function( name, legend, value, options, className ) {

		const box = el( 'div', 'vcard-choice '+ ( className || '' ) );

		box.setAttribute( 'role', 'radiogroup' );
		box.setAttribute( 'aria-label', legend );
		ui.choices[name] = [];

		options.forEach( function( option ) {

			const label = el( 'label', 'vcard-choice-item' );
			const input = el( 'input' );

			input.type = 'radio';
			input.name = 'vcard-'+ name;
			input.value = option.value;
			input.checked = option.value === value;
			input.addEventListener( 'change', sync );
			ui.choices[name].push( input );
			label.appendChild( input );

			if( option.picture )
				label.appendChild( option.picture );

			label.appendChild( el( 'span', 'vcard-choice-text', option.label ) );
			box.appendChild( label );
		} );

		return box;
	};

	/**
	 *	What a choice holds now
	 *
	 *	@param		{string}		name
	 *
	 *	@return		{string}
	 */
	const picked = function( name ) {
		return ui.choices[name].find( function( input ) { return input.checked } ).value;
	};

	/**
	 *	The scheme, the switch and the accent mean something once a design is chosen: without one they are
	 *	dimmed and turned off
	 *
	 *	@return		void
	 */
	const sync = function() {

		const on = picked('design') !== '';

		ui.looks.classList.toggle( 'is-off', on === false );

		ui.choices.scheme.concat( ui.choices.accent, [ ui.controls.switch, ui.colour ] ).forEach( function( control ) { control.disabled = on === false } );
	};

	/**
	 *	The design: the card as the theme draws it or one of three designs over it, then what a design lets the
	 *	owner choose - light, dark or auto, a switch for the visitor, the accent colour
	 *
	 *	@return		{Element}
	 */
	const design = function() {

		const stored = ui.data.data;
		const set = group( '/_admin/vcard/heading/design', undefined, '/_admin/vcard/hint/design' );
		const looks = el( 'div', 'vcard-looks' );

		ui.looks = looks;

		const designs = [ '', 'klar', 'karte', 'foto' ].map( function( key ) {

			let picture;

			if( key === '' )
				picture = el( 'span', 'vcard-design-picture vcard-design-none' );
			else {
				picture = el( 'img', 'vcard-design-picture' );
				picture.src = ui.data.designs[key];
				picture.alt = '';
			}

			return { value : key, label : text( '/_admin/vcard/design/'+ ( key === '' ? 'none' : key ) ), picture : picture };
		} );

		set.appendChild( choice( 'design', text('/_admin/vcard/heading/design'), stored.design, designs, 'vcard-designs' ) );

		looks.appendChild( choice( 'scheme', text('/_admin/vcard/label/scheme'), stored.scheme, [ 'light', 'dark', 'auto' ].map( function( key ) { return { value : key, label : text( '/_admin/vcard/scheme/'+ key ) } } ), 'vcard-schemes' ) );
		looks.appendChild( toggle( 'switch', '/_admin/vcard/label/switch', '', stored.switch ) );

		const accent = el( 'div', 'vcard-accent' );
		const own = stored.accent !== '';

		ui.colour = el( 'input', 'vcard-colour' );
		ui.colour.type = 'color';
		ui.colour.value = own === true ? stored.accent : '#2563a8';
		ui.colour.setAttribute( 'aria-label', text('/_admin/vcard/label/accent-color') );
		ui.colour.addEventListener( 'input', function() { ui.choices.accent.forEach( function( input ) { input.checked = input.value === 'own' } ); sync() } );

		accent.appendChild( choice( 'accent', text('/_admin/vcard/label/accent'), own === true ? 'own' : 'project', [ 'project', 'own' ].map( function( key ) { return { value : key, label : text( '/_admin/vcard/accent/'+ key ) } } ), 'vcard-accents' ) );
		accent.appendChild( ui.colour );
		looks.appendChild( accent );
		set.appendChild( looks );
		sync();

		return set;
	};

	/**
	 *	The logo of the website, which the card shows where the picture is the logo: a group of its own,
	 *	with no switch, since the logo is not a block of the card but the one of the whole website. Its
	 *	two pictures - for a light ground and for a dark one - can be chosen, replaced and removed here.
	 *	They are the image slots of the kernel, so they are saved by the kernel's own actions of the
	 *	Images panel and kept in its place; an account that may not manage images sees them, and can
	 *	change none
	 *
	 *	@return		{Element|null}						Null where the project has no logo slot
	 */
	const logoGroup = function() {

		if( ui.data.logo.slots.length === 0 )
			return null;

		const set = group('/_admin/vcard/heading/logo');

		set.appendChild( el( 'p', 'vcard-note', text( ui.data.logo.manage === true ? '/_admin/vcard/hint/logo' : '/_admin/vcard/hint/logo-readonly' ) ) );

		ui.logo = el( 'div', 'vcard-logo' );
		set.appendChild( ui.logo );

		ui.logoMsg = el( 'p', 'vcard-logo-msg' );
		ui.logoMsg.setAttribute( 'role', 'status' );
		set.appendChild( ui.logoMsg );

		drawLogo();

		return set;
	};

	const drawLogo = function() {

		const box = ui.logo;
		const logo = ui.data.logo;

		box.innerHTML = '';

		logo.slots.forEach( function( slot ) {

			const row = el( 'div', 'vcard-photo-row vcard-logo-row' );
			const name = text( slot.uri === '/logo' ? '/_admin/vcard/label/logo-light' : '/_admin/vcard/label/logo-dark' );
			const label = el( 'span', 'vcard-logo-label', name );

			row.dataset.slot = slot.uri;
			row.appendChild( label );

			if( slot.url !== null ) {
				const img = el( 'img', slot.uri === '/logo' ? 'vcard-logo-img' : 'vcard-logo-img vcard-logo-img--dark' );
				img.src = slot.shown || slot.url;
				img.alt = '';
				img.width = 150;
				img.height = 30;
				row.appendChild( img );
			}
			else
				row.appendChild( el( 'span', 'vcard-empty', text('/_admin/vcard/status/logo-none') ) );

			if( logo.manage === true ) {

				const actions = el( 'div', 'nino-admin-actions' );
				const file = el( 'input' );
				const choose = text( slot.url === null ? '/_admin/vcard/label/logo-choose' : '/_admin/vcard/label/logo-replace' );

				file.type = 'file';
				file.accept = 'image/*';
				file.hidden = true;
				file.addEventListener( 'change', function() {
					if( file.files.length > 0 )
						uploadLogo( slot.uri, file.files[0] );
					file.value = '';
				} );

				actions.appendChild( file );
				actions.appendChild( Nino.adminUi.button( choose, function() { file.click() }, { className : 'nino-admin-btn--small', name : choose+ ' ('+ name+ ')' } ) );

				if( slot.url !== null )
					actions.appendChild( Nino.adminUi.button( text('/_admin/vcard/label/logo-remove'), function( event ) { askLogo( slot.uri, event.currentTarget ) }, { className : 'nino-admin-btn--small nino-admin-btn-danger', name : text('/_admin/vcard/label/logo-remove')+ ' ('+ name+ ')' } ) );

				row.appendChild( actions );
			}

			box.appendChild( row );
		} );
	};

	/**
	 *	Put the answer of an action of the Images panel into the slot it names and draw the logo again.
	 *	The file of a slot has the same name after a replacement, so the browser would show the picture
	 *	it already has: the preview carries a stamp, the address the panel keeps stays clean - as in the
	 *	Images panel, which is told as well
	 *
	 *	@param		{string}		uri					The slot
	 *	@param		{string|null}	url				The address of its picture now
	 *
	 *	@return		void
	 */
	const setLogo = function( uri, url ) {

		ui.data.logo.slots.forEach( function( slot ) {
			if( slot.uri === uri ) {
				slot.url = url;
				slot.shown = url === null ? null : url+ ( url.indexOf('?') === -1 ? '?' : '&' )+ 't='+ Date.now();
			}
		} );

		Nino.admin.touch( 'slots' );
		drawLogo();
		refresh();
	};

	/**
	 *	What the screen says of the logo: said in the line and, where it can be seen, under the pictures,
	 *	in words as well as in colour
	 *
	 *	@param		{string}		message
	 *	@param		{boolean}		[warning]
	 *
	 *	@return		void
	 */
	const sayLogo = function( message, warning ) {

		ui.logoMsg.textContent = message;
		ui.logoMsg.classList.toggle( 'is-warning', warning === true );
		ui.line.idle( message );
	};

	const uploadLogo = function( uri, file ) {

		Nino.adminUi.checkImage( file, function( problem ) {

			if( problem !== null ) {
				ui.line.fail( Nino.adminUi.api.errorText( 400, problem, '/_admin/vcard/error/logo' ) );
				return;
			}

			ui.logoMsg.textContent = '';
			ui.line.idle( text('/_admin/vcard/msg/uploading') );

			// The kernel's own action: its permission, its limits, its one place for the picture
			Nino.adminUi.api.call( 'images/upload', { uri : uri }, function( status, response ) {

				if( status !== 200 || response === null ) {
					ui.line.error( status, response, '/_admin/vcard/error/logo' );
					return;
				}

				setLogo( uri, response.url );

				// Saved either way, but a picture below the size of the slot was scaled up, which is said
				if( response.belowTarget === true && response.source )
					sayLogo( Nino.adminUi.format( text('/_admin/vcard/msg/logo-below'), response.source.width+ ' × '+ response.source.height+ ' px' ), true );
				else
					sayLogo( text('/_admin/vcard/msg/logo-uploaded') );
			}, { file : file } );
		} );
	};

	const askLogo = function( uri, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			message	: text('/_admin/vcard/confirm/logo-remove'),
			yes			: text('/_admin/vcard/label/logo-remove'),
			no			: text('/_admin/vcard/label/cancel'),
			opener		: opener,
			onChoose	: function( yes ) {

				if( yes !== true )
					return;

				Nino.adminUi.api.call( 'images/remove', { uri : uri }, function( status, response ) {

					if( status !== 200 || response === null ) {
						ui.line.error( status, response, '/_admin/vcard/error/logo' );
						return;
					}

					setLogo( uri, null );
					sayLogo( text('/_admin/vcard/msg/logo-removed') );
				} );
			},
		} );
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

		const set = group( '/_admin/vcard/heading/links', 'links', '/_admin/vcard/hint/links', ui.data.limits.links );

		ui.list = el( 'ul', 'vcard-links' );
		ui.rows = [];
		ui.empty = el( 'p', 'vcard-empty', text('/_admin/vcard/hint/links-none') );

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
			design	: picked('design'),
			scheme	: picked('scheme'),
			switch	: ui.controls.switch.checked,
			accent	: picked('accent') === 'own' ? ui.colour.value : '',
			blocks	: {},
			texts		: {},
			links		: [],
			alt			: {},
		};

		// A block that has no switch on the screen keeps the value it has
		Object.keys( ui.data.data.blocks ).forEach( function( block ) {
			out.blocks[block] = ui.blocks[block] ? ui.blocks[block].checked : ui.data.data.blocks[block];
		} );

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
			// The pictures of the designs come with the list alone
			response.designs = ui.data.designs;
			panel.data = response;
			draw( response, panel );
			ui.line.saved();
			// The card is saved; what could not be done after it is said in the line
			if( typeof response.warning === 'string' )
				ui.line.idle( text( '/_admin/error/'+ response.warning ) );
			ui.save.focus();
			refresh();
			done( true );
		} );
	};

	const draw = function( data, panel ) {

		const mount = panel.mount();

		ui = { data : data, panel : panel, controls : {}, choices : {}, looks : null, colour : null, blocks : {}, off : [], logo : null, logoMsg : null, rows : [], alt : {}, photo : null, where : null, form : null, list : null, empty : null, add : null, line : null, save : null, live : null };
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

		form.appendChild( el( 'p', 'vcard-intro', text('/_admin/vcard/intro') ) );
		ui.where = where();
		form.appendChild( ui.where );
		form.appendChild( picture() );

		const logo = logoGroup();
		if( logo !== null )
			form.appendChild( logo );

		form.appendChild( design() );

		const words = group( '/_admin/vcard/heading/name', 'name', '/_admin/vcard/hint/block-name' );
		words.appendChild( line( 'name', '/_admin/vcard/label/name', stored.name, { help : '/_admin/vcard/hint/name', placeholder : inherited.name, maxLength : data.limits.name, autocomplete : 'name' } ) );
		words.appendChild( select( 'kind', '/_admin/vcard/label/kind', stored.kind, [ 'person', 'organization' ], '/_admin/vcard/hint/kind' ) );
		words.appendChild( languages( 'line', '/_admin/vcard/label/line', { help : '/_admin/vcard/hint/line', maxLength : data.limits.line } ) );
		words.appendChild( languages( 'text', '/_admin/vcard/label/text', { help : '/_admin/vcard/hint/text', maxLength : data.limits.text, multiline : true, placeholders : inherited.text } ) );
		form.appendChild( words );

		const contact = group( '/_admin/vcard/heading/contact', 'contact', '/_admin/vcard/hint/contact' );
		contact.appendChild( line( 'phone', '/_admin/vcard/label/phone', stored.phone, { placeholder : inherited.phone, maxLength : data.limits.phone, inputMode : 'tel', autocomplete : 'tel' } ) );
		contact.appendChild( line( 'email', '/_admin/vcard/label/email', stored.email, { placeholder : inherited.email, maxLength : data.limits.email, type : 'email', autocomplete : 'email' } ) );
		contact.appendChild( line( 'website', '/_admin/vcard/label/website', stored.website, { placeholder : inherited.website, maxLength : data.limits.url, type : 'url', autocomplete : 'url' } ) );
		contact.appendChild( area( 'address', '/_admin/vcard/label/address', stored.address, { help : '/_admin/vcard/hint/address', placeholder : inherited.address, maxLength : data.limits.address, rows : 4 } ) );
		contact.appendChild( toggle( 'map', '/_admin/vcard/label/map', '/_admin/vcard/hint/map', stored.map ) );
		form.appendChild( contact );

		form.appendChild( links() );

		const times = group( '/_admin/vcard/heading/times', 'times' );
		times.appendChild( languages( 'hours', '/_admin/vcard/label/hours', { help : '/_admin/vcard/hint/hours', maxLength : data.limits.hours } ) );
		form.appendChild( times );

		// The one group with no switch: what it holds is a switch itself
		const file = group('/_admin/vcard/heading/file');
		file.appendChild( toggle( 'save', '/_admin/vcard/label/save-button', '/_admin/vcard/hint/save-button', stored.save ) );
		form.appendChild( file );

		const legal = group( '/_admin/vcard/heading/legal', data.legal.active === true ? 'legal' : undefined, data.legal.active === true ? '/_admin/vcard/hint/legal' : '/_admin/vcard/hint/legal-none' );
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

		// A block stored as off is drawn off, now that everything it holds is there
		ui.off.forEach( function( apply ) { apply() } );
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
