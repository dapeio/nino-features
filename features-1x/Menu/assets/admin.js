/**
 *	Nino - Menu
 *	admin.js		The Menu panel's script: the list of the menus and the editor of one.
 *							The editor works on a copy of the menu - its entries, and the texts of the
 *							keys they name in every language - that one Save writes whole
 *							(menu/save); the keys and the slots it needs are made by the server, the
 *							pictures are posted to the Images panel (images/upload) as the Builder
 *							posts them. The model - the operations on entries, items and columns -
 *							is a plain object, Nino.admin.menu.model, which tests/menu-js-smoke.js
 *							runs without a page.
 *
 *							Every word is a fill (/_admin/menu/...), every text goes into the page as
 *							text, and every request is the kernel's Nino.adminUi.api.call().
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const MAX_COLUMNS = 4;
	const MAX_ID = 24;
	const KINDS = [ 'title', 'subtitle', 'image', 'page', 'link', 'html' ];

	// --- The model: plain data in, plain data out --------------------------------------------------

	const model = {

		MAX_COLUMNS : MAX_COLUMNS,
		KINDS : KINDS,

		/**
		 *	A text made into an id of an entry: lower case words, starting with a letter, not ending in a number
		 *	(that ending is the number of an item) and not "list" (that is the id of the list in the hull)
		 *
		 *	@param		{string}		text
		 *
		 *	@return		{string}
		 */
		slug : function( text ) {

			let slug = String( text ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );

			if( slug === '' )
				slug = 'entry';

			if( /^[a-z]/.test( slug ) === false )
				slug = 'p-'+ slug;

			slug = slug.slice( 0, MAX_ID ).replace( /-+$/, '' );

			return /-[0-9]+$/.test( slug ) === true || slug === 'list' ? slug+ '-x' : slug;
		},

		/**
		 *	An id that is not taken: the base, else the base with a letter after a hyphen (never a number)
		 *
		 *	@param		{Array}			taken
		 *	@param		{string}		base
		 *
		 *	@return		{string}
		 */
		uniqueId : function( taken, base ) {

			if( taken.indexOf( base ) === -1 )
				return base;

			const letters = 'abcdefghijklmnopqrstuvwxyz';

			for( let n = 0; n < 702; n++ ) {

				const word = n < 26 ? letters[n] : letters[Math.floor( n / 26 ) - 1]+ letters[n % 26];

				if( taken.indexOf( base+ '-'+ word ) === -1 )
					return base+ '-'+ word;
			}

			return base;
		},

		/**
		 *	The name of a text key or an image slot of an entry: /feature/menu/<menu>/<id>, and with a number
		 *	/feature/menu/<menu>/<id>-<n> for what stands in its panel
		 *
		 *	@param		{string}		menu
		 *	@param		{string}		id
		 *	@param		{number}		[number]
		 *
		 *	@return		{string}
		 */
		nameOf : function( menu, id, number ) {
			return '/feature/menu/'+ menu+ '/'+ id+ ( number > 0 ? '-'+ number : '' );
		},

		/**
		 *	The name an item is called by, whatever its kind: its key, its slot or its label
		 *
		 *	@param		{Object}		item
		 *
		 *	@return		{string}
		 */
		itemName : function( item ) {
			return item.key ?? item.slot ?? item.label ?? '';
		},

		/**
		 *	The number a new item of an entry gets: one more than the highest that an item of it has or that a key or a slot
		 *	of the menu - an item that was taken away - has had
		 *
		 *	@param		{string}		menu
		 *	@param		{Object}		entry
		 *	@param		{Array}			[taken]				The names of the keys and slots that are there
		 *
		 *	@return		{number}
		 */
		nextNumber : function( menu, entry, taken ) {

			const start = model.nameOf( menu, entry.id )+ '-';
			const names = ( taken || [] ).slice();
			let highest = 0;

			( entry.panel === null ? [] : entry.panel.cols ).forEach( function( items ) {
				items.forEach( function( item ) { names.push( model.itemName( item ) ) } );
			} );

			names.forEach( function( name ) {
				if( name.indexOf( start ) === 0 && /^[0-9]+$/.test( name.slice( start.length ) ) === true )
					highest = Math.max( highest, Number( name.slice( start.length ) ) );
			} );

			return highest + 1;
		},

		/**
		 *	A new entry, not yet in the list: its id is made from what it points at and is not one of the others
		 *
		 *	@param		{string}		menu
		 *	@param		{string}		kind					page or link
		 *	@param		{Array}			entries
		 *	@param		{Object}		[from]				{ uri } for a page - the first the site has - and { base } for the word the id is made from
		 *
		 *	@return		{Object}
		 */
		newEntry : function( menu, kind, entries, from ) {

			from = from || {};

			const id = model.uniqueId( entries.map( function( entry ) { return entry.id } ), model.slug( from.base ?? ( kind === 'page' ? String( from.uri ?? '' ).replace( /\//g, '-' ) : 'link' ) ) );

			return kind === 'page'
				? { id : id, kind : 'page', uri : from.uri ?? '', label : '', panel : null }
				: { id : id, kind : 'link', href : '', target : '', label : model.nameOf( menu, id ), panel : null };
		},

		/**
		 *	Move an entry or an item one place up (-1) or down (1) in its list
		 *
		 *	@param		{Array}			list
		 *	@param		{number}		index
		 *	@param		{number}		direction
		 *
		 *	@return		{number}									The place it is at now; the same where it could not move
		 */
		move : function( list, index, direction ) {

			const to = index + ( direction < 0 ? -1 : 1 );

			if( index < 0 || index >= list.length || to < 0 || to >= list.length )
				return index;

			list.splice( to, 0, list.splice( index, 1 )[0] );

			return to;
		},

		/**
		 *	Take an entry or an item out of its list
		 *
		 *	@param		{Array}			list
		 *	@param		{number}		index
		 *
		 *	@return		{boolean}									Whether there was one
		 */
		remove : function( list, index ) {

			if( index < 0 || index >= list.length )
				return false;

			list.splice( index, 1 );

			return true;
		},

		/**
		 *	The panel of an entry on or off: on, it is one empty column
		 *
		 *	@param		{Object}		entry
		 *	@param		{boolean}		on
		 *
		 *	@return		void
		 */
		setPanel : function( entry, on ) {
			entry.panel = on === true ? ( entry.panel ?? { columns : 1, cols : [ [] ] } ) : null;
		},

		/**
		 *	The number of the columns of a panel, one to four: more are empty, fewer hand their items to the last that stays
		 *
		 *	@param		{Object}		panel
		 *	@param		{number}		columns
		 *
		 *	@return		{number}									The number it has now
		 */
		setColumns : function( panel, columns ) {

			columns = Math.min( MAX_COLUMNS, Math.max( 1, Math.floor( Number( columns ) ) || 1 ) );

			while( panel.cols.length < columns )
				panel.cols.push( [] );

			// What the columns that go held is added to the last that stays, in their order
			if( panel.cols.length > columns )
				panel.cols[columns - 1] = panel.cols[columns - 1].concat( ...panel.cols.splice( columns ) );

			panel.columns = columns;

			return columns;
		},

		/**
		 *	A new item of a kind, called what no item of the entry is called
		 *
		 *	@param		{string}		menu
		 *	@param		{Object}		entry
		 *	@param		{string}		kind
		 *	@param		{Array}			[taken]
		 *	@param		{Object}		[defaults]		{ uri } the page a page item starts at
		 *
		 *	@return		{Object}
		 */
		newItem : function( menu, entry, kind, taken, defaults ) {

			const name = model.nameOf( menu, entry.id, model.nextNumber( menu, entry, taken ) );

			switch( kind ) {
				case 'image':		return { kind : 'image', slot : name };
				case 'page':		return { kind : 'page', uri : ( defaults || {} ).uri ?? '', label : '' };
				case 'link':		return { kind : 'link', href : '', target : '', label : name };
				case 'html':		return { kind : 'html', html : '' };
				default:				return { kind : kind === 'subtitle' ? 'subtitle' : 'title', key : name };
			}
		},

		/**
		 *	An item changed into another kind: a name it has stays its name where the new kind has one, an address and a page
		 *	are carried over where there is one, the rest starts empty
		 *
		 *	@param		{string}		menu
		 *	@param		{Object}		entry
		 *	@param		{Object}		item
		 *	@param		{string}		kind
		 *	@param		{Array}			[taken]
		 *	@param		{Object}		[defaults]
		 *
		 *	@return		{Object}
		 */
		convertItem : function( menu, entry, item, kind, taken, defaults ) {

			const name = model.itemName( item );
			const fresh = model.newItem( menu, entry, kind, taken, defaults );

			if( item.kind === kind )
				return item;

			if( name !== '' && /^\/feature\/menu\//.test( name ) === true ) {
				if( fresh.key !== undefined ) fresh.key = name;
				if( fresh.slot !== undefined ) fresh.slot = name;
				if( kind === 'link' ) fresh.label = name;
			}

			if( kind === 'link' && item.kind === 'page' )
				fresh.href = '';

			if( kind === 'page' && typeof item.uri === 'string' && item.uri !== '' )
				fresh.uri = item.uri;

			return fresh;
		},

		/**
		 *	Every text key the entries name: the labels that are keys, the titles and the subtitles
		 *
		 *	@param		{Array}			entries
		 *
		 *	@return		{Array}
		 */
		keysOf : function( entries ) {

			const keys = [];

			entries.forEach( function( entry ) {

				if( entry.label !== '' )
					keys.push( entry.label );

				( entry.panel === null ? [] : entry.panel.cols ).forEach( function( items ) {
					items.forEach( function( item ) {
						if( item.kind === 'title' || item.kind === 'subtitle' )
							keys.push( item.key );
						else if( ( item.kind === 'page' || item.kind === 'link' ) && item.label !== '' )
							keys.push( item.label );
					} );
				} );
			} );

			return keys;
		},

		/**
		 *	The texts of the keys the entries name, and no others, every language that has one
		 *
		 *	@param		{Array}			entries
		 *	@param		{Object}		texts					key => { locale : text }
		 *
		 *	@return		{Object}
		 */
		textsOf : function( entries, texts ) {

			const sent = {};

			model.keysOf( entries ).forEach( function( key ) {
				sent[key] = Object.assign( {}, texts[key] ?? {} );
			} );

			return sent;
		},

		/**
		 *	Whether a key has a text in any language
		 *
		 *	@param		{Object}		texts
		 *	@param		{string}		key
		 *
		 *	@return		{boolean}
		 */
		hasText : function( texts, key ) {
			return Object.keys( texts[key] ?? {} ).some( function( locale ) { return String( texts[key][locale] ) !== '' } );
		},

		/**
		 *	What a save posts: the entries as the server reads them, a page whose label has no text a page with the empty label
		 *	(its name), and the texts of the keys that are in them
		 *
		 *	@param		{string}		menu
		 *	@param		{Array}			entries
		 *	@param		{Object}		texts
		 *
		 *	@return		{Object}									{ key, entries, texts }
		 */
		payload : function( menu, entries, texts ) {

			const cleaned = JSON.parse( JSON.stringify( entries ) );
			const label = function( holder ) {
				if( holder.kind === 'page' && model.hasText( texts, holder.label ) === false )
					holder.label = '';
			};

			cleaned.forEach( function( entry ) {
				label( entry );
				( entry.panel === null ? [] : entry.panel.cols ).forEach( function( items ) { items.forEach( label ) } );
			} );

			return { key : menu, entries : cleaned, texts : model.textsOf( cleaned, texts ) };
		},
	};

	// --- The panel ---------------------------------------------------------------------------------

	const t = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/menu/'+ key ), ...params );
	};

	// What the editor works on: the answers of the server and the copy of the menu that is open
	const state = {
		registry : null,
		menus : [],
		navs : [],
		current : null,			// { key, kernel, entries, texts, taken } - the open menu
		saved : '',					// the payload as it was last saved or loaded
		locale : '',
		open : {},					// entry id => whether its panel is open in the editor
		focus : '',					// data-focus of the control that takes the focus when the editor is drawn again
		line : null,
		note : { },					// picture note by slot uri: { text, kind }
	};

	const root = function( id ) { return dc.getElementById( id ) };

	const payload = function() {
		return model.payload( state.current.key, state.current.entries, state.current.texts );
	};

	const isDirty = function() {
		return state.current !== null && JSON.stringify( payload() ) !== state.saved;
	};

	const changed = function() {

		if( state.line !== null && isDirty() === true )
			state.line.dirty();
		else if( state.line !== null )
			state.line.idle();

		if( typeof Nino.admin.dirty === 'object' )
			Nino.admin.dirty.refresh();
	};

	// The name of the language the editor works in, as the site names it
	const language = function() {
		return state.registry.names[state.locale] ?? state.locale;
	};

	const slotOf = function( uri ) {
		return state.registry.slots.find( function( slot ) { return slot.uri === uri } ) ?? null;
	};

	const pageName = function( uri ) {
		const page = state.registry.pages.find( function( one ) { return one.uri === uri } );
		return page === undefined ? '' : ( page.names[state.locale] ?? page.name );
	};

	const text = function( key ) {
		return ( state.current.texts[key] ?? {} )[state.locale] ?? '';
	};

	// What a page shows where a key has no text in the language of the editor: the text of another language, the native one first, as the kernel's fills have it
	const fallback = function( key ) {

		const order = [ state.registry.native ].concat( state.registry.locales );

		for( let n = 0; n < order.length; n++ )
			if( order[n] !== state.locale && String( ( state.current.texts[key] ?? {} )[order[n]] ?? '' ) !== '' )
				return state.current.texts[key][order[n]];

		return '';
	};

	const writeText = function( key, value ) {

		state.current.texts[key] = state.current.texts[key] ?? {};

		// An empty text is a text: the server is told to clear it, which it does not know where nothing is sent
		state.current.texts[key][state.locale] = value;
	};

	const button = function( label, onClick, name, focus, className ) {

		const one = Nino.adminUi.button( label, onClick, { className : className ?? 'nino-admin-btn-secondary nino-admin-btn--small', name : name } );

		if( focus !== undefined )
			one.dataset.focus = focus;

		return one;
	};

	/**
	 *	The up, down and remove buttons at the end of a row of an ordered list
	 */
	const moves = function( list, index, focus, redraw, afterRemove ) {

		const box = Nino.adminUi.el( 'span', 'nino-admin-move' );

		box.appendChild( button( '↑', function() {
			const to = model.move( list, index, -1 );
			state.focus = focus( to, 'up' );
			redraw();
		}, t('label/up'), focus( index, 'up' ) ) ).disabled = index === 0;

		box.appendChild( button( '↓', function() {
			const to = model.move( list, index, 1 );
			state.focus = focus( to, 'down' );
			redraw();
		}, t('label/down'), focus( index, 'down' ) ) ).disabled = index === list.length - 1;

		box.appendChild( button( '×', function() {
			model.remove( list, index );
			if( typeof afterRemove === 'function' )
				afterRemove();
			state.focus = '';
			redraw();
		}, t('label/remove'), focus( index, 'remove' ), 'nino-admin-btn-danger nino-admin-btn--small' ) );

		return box;
	};

	const kindSelect = function( label, kinds, value, onChange, prefix ) {
		return Nino.adminUi.selectField( {
			key : 'kind',
			label : label,
			value : value,
			options : kinds.map( function( kind ) { return { value : kind, label : t( prefix+ kind ) } } ),
			onChange : onChange,
		} );
	};

	const pageSelect = function( uri, onChange ) {

		const options = state.registry.pages.map( function( page ) { return { value : page.uri, label : page.name+ ' ('+ page.uri+ ')' } } );

		// A page that is not in the list - one without a name now - stays what it is
		if( uri !== '' && state.registry.pages.every( function( page ) { return page.uri !== uri } ) )
			options.unshift( { value : uri, label : uri } );

		return Nino.adminUi.selectField( { key : 'uri', label : t('label/page'), value : uri, options : options, onChange : onChange } );
	};

	/**
	 *	The label of a page or a link: a text per language - for a page the name of the page is what stands where there is none
	 */
	const labelField = function( holder, menu, entry, label, placeholder ) {

		const name = holder.label;

		return Nino.adminUi.textField( {
			label : label,
			value : name === '' ? '' : text( name ),
			maxLength : 300,
			placeholder : name !== '' && fallback( name ) !== '' ? fallback( name ) : placeholder,
			onInput : function( value ) {

				// A page has no key until a text is written for it
				if( holder.kind === 'page' && holder.label === '' )
					holder.label = holder === entry ? model.nameOf( menu, entry.id ) : model.nameOf( menu, entry.id, model.nextNumber( menu, entry, state.current.taken ) );

				writeText( holder.label, value );
				changed();
			},
		} );
	};

	const targetSelect = function( holder ) {
		return Nino.adminUi.selectField( {
			key : 'target',
			label : t('label/target'),
			value : holder.target,
			options : [ { value : '', label : t('target/same') }, { value : '_blank', label : t('target/blank') } ],
			onChange : function( value ) { holder.target = value; changed() },
		} );
	};

	const addressField = function( holder ) {
		return Nino.adminUi.textField( {
			label : t('label/address'),
			value : holder.href,
			maxLength : 500,
			placeholder : 'https://',
			onInput : function( value ) { holder.href = value; changed() },
		} );
	};

	/**
	 *	The picture of an image item: what it shows, the upload, and the alt text of the working language. The slot is made by
	 *	the first upload where it is not there, as the Builder makes its slots, and the picture goes to the Images panel
	 */
	const pictureField = function( item, redraw ) {

		const wrap = Nino.adminUi.el( 'div', 'menu-picture' );
		const slot = slotOf( item.slot );
		const thumb = Nino.adminUi.el( 'img', 'menu-picture-thumb' );
		const message = Nino.adminUi.el( 'p', 'nino-admin-hint menu-picture-message' );
		const note = state.note[item.slot] ?? { text : '', kind : '' };
		const file = Nino.adminUi.el( 'input' );
		const say = function( words, kind ) {
			state.note[item.slot] = { text : words, kind : kind };
			message.textContent = words;
			message.classList.toggle( 'is-error', kind === 'error' );
			message.hidden = words === '';
		};
		const upload = t('label/upload');

		thumb.alt = '';
		thumb.hidden = slot === null || typeof slot.url !== 'string';

		if( thumb.hidden === false )
			thumb.src = slot.url;

		file.type = 'file';
		file.accept = 'image/*';
		file.className = 'nino-admin-sr-only';
		file.setAttribute( 'aria-label', upload );
		file.addEventListener( 'change', function() {
			if( file.files.length > 0 )
				send( item, file.files[0], file, say, redraw );
		} );

		const choose = button( upload, function() { file.click() }, undefined, undefined, 'nino-admin-btn-secondary' );
		const hint = Nino.adminUi.uploadHint();

		say( note.text, note.kind );

		wrap.appendChild( thumb );

		const controls = Nino.adminUi.el( 'div', 'menu-picture-controls' );
		controls.appendChild( choose );
		controls.appendChild( file );
		controls.appendChild( message );

		if( hint !== null )
			controls.appendChild( hint );

		wrap.appendChild( controls );

		// The alt text is the slot's own, and the slot is there when a picture is
		const alt = Nino.adminUi.textField( {
			label : t('label/alt', language()),
			value: slot !== null ? ( slot.alt[state.locale] ?? '' ) : '',
			maxLength : 250,
			onInput : function() {},
		} );
		const input = alt.querySelector('input');

		input.disabled = slot === null || typeof slot.url !== 'string';
		input.addEventListener( 'change', function() {

			const sent = {};
			sent[state.locale] = input.value;

			Nino.adminUi.api.call( 'images/alt', { uri : item.slot, alt : sent }, function( status, response ) {

				if( status !== 200 || response === null )
					return say( Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/alt' ), 'error' );

				slot.alt = response.alt;
				say( t('picture/alt-saved'), '' );
			} );
		} );

		wrap.appendChild( alt );

		return wrap;
	};

	/**
	 *	Store the picture chosen for an item: the slot first where there is none, then the Images panel's own upload
	 */
	const send = function( item, picture, field, say, redraw ) {

		field.disabled = true;
		say( t('picture/busy'), '' );

		const done = function( words, kind ) {
			field.disabled = false;
			state.note[item.slot] = { text : words, kind : kind };
			redraw();
		};

		const store = function() {

			Nino.adminUi.api.call( 'images/upload', { uri : item.slot }, function( status, response ) {

				if( status !== 200 || response === null )
					return done( Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/upload' ), 'error' );

				const held = slotOf( item.slot );

				held.url = response.url+ ( response.url.indexOf('?') === -1 ? '?' : '&' )+ 't='+ Date.now();

				if( typeof Nino.admin.touch === 'function' )
					Nino.admin.touch('slots');

				done( response.belowTarget === true && response.source ? t('picture/small', response.source.width+ ' × '+ response.source.height+ ' px') : t('picture/stored'), response.belowTarget === true ? 'warning' : '' );
			}, { file : picture } );
		};

		Nino.adminUi.checkImage( picture, function( rejection ) {

			if( rejection !== null )
				return done( Nino.adminUi.api.errorText( 400, rejection ), 'error' );

			if( slotOf( item.slot ) !== null )
				return store();

			const size = state.registry.size;

			Nino.adminUi.api.call( 'slots/create', { uri : item.slot, label : item.slot.slice( '/feature/menu/'.length ).replace( /-/g, ' ' ), width : size.width, height : size.height }, function( status, response ) {

				// A slot that is there already (409) is the one to use
				if( status !== 200 && status !== 409 )
					return done( Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/slot' ), 'error' );

				state.registry.slots.push( { uri : item.slot, width : size.width, height : size.height, url : null, alt : {} } );
				store();
			} );
		} );
	};

	/**
	 *	One item of a column
	 */
	const drawItem = function( entry, column, index, redraw ) {

		const menu = state.current.key;
		const items = entry.panel.cols[column];
		const item = items[index];
		const row = Nino.adminUi.el( 'li', 'menu-item' );
		const head = Nino.adminUi.el( 'div', 'menu-item-head' );
		const focus = function( at, role ) { return 'i'+ entry.id+ '.'+ column+ '.'+ at+ '.'+ role };

		head.appendChild( kindSelect( t('label/item'), KINDS, item.kind, function( kind ) {
			items[index] = model.convertItem( menu, entry, item, kind, state.current.taken, { uri: ( state.registry.pages[0] ?? { uri : '' } ).uri } );
			changed();
			redraw();
		}, 'item/' ) );
		head.appendChild( moves( items, index, focus, redraw ) );
		row.appendChild( head );

		switch( item.kind ) {

			case 'title':
			case 'subtitle':
				row.appendChild( Nino.adminUi.textField( { label : t('label/text', language()), value : text( item.key ), placeholder : fallback( item.key ), maxLength : 300, onInput : function( value ) { writeText( item.key, value ); changed() } } ) );
				break;

			case 'image':
				row.appendChild( pictureField( item, redraw ) );
				break;

			case 'page':
				row.appendChild( pageSelect( item.uri, function( value ) { item.uri = value; changed() } ) );
				row.appendChild( labelField( item, menu, entry, t('label/label', language()), pageName( item.uri ) ) );
				break;

			case 'link':
				row.appendChild( addressField( item ) );
				row.appendChild( targetSelect( item ) );
				row.appendChild( labelField( item, menu, entry, t('label/label', language()), '' ) );
				break;

			default: {
				const area = Nino.adminUi.el( 'textarea', 'nino-admin-input menu-html' );
				const label = Nino.adminUi.el( 'label', 'nino-admin-field' );

				area.value = item.html;
				area.rows = 5;
				area.maxLength = 20000;
				area.spellcheck = false;
				area.addEventListener( 'input', function() { item.html = area.value; changed() } );
				label.appendChild( Nino.adminUi.el( 'span', '', t('label/html') ) );
				label.appendChild( area );
				row.appendChild( label );
			}
		}

		return row;
	};

	/**
	 *	The columns of an entry's panel, side by side
	 */
	const drawPanel = function( entry, redraw ) {

		const wrap = Nino.adminUi.el( 'div', 'menu-columns' );

		wrap.dataset.columns = String( entry.panel.columns );

		entry.panel.cols.forEach( function( items, column ) {

			const col = Nino.adminUi.el( 'section', 'menu-column' );
			const list = Nino.adminUi.el( 'ul', 'menu-items' );

			col.appendChild( Nino.adminUi.el( 'h4', 'menu-column-title', t('label/column', String( column + 1 )) ) );

			items.forEach( function( item, index ) { list.appendChild( drawItem( entry, column, index, redraw ) ) } );
			col.appendChild( list );

			const add = button( '+ '+ t('label/add-item'), function() {
				items.push( model.newItem( state.current.key, entry, 'title', state.current.taken ) );
				state.focus = 'i'+ entry.id+ '.'+ column+ '.'+ ( items.length - 1 )+ '.up';
				changed();
				redraw();
			}, undefined, 'a'+ entry.id+ '.'+ column );

			col.appendChild( add );
			wrap.appendChild( col );
		} );

		return wrap;
	};

	/**
	 *	One entry of the menu
	 */
	const drawEntry = function( entry, index, redraw ) {

		const menu = state.current.key;
		const row = Nino.adminUi.el( 'li', 'menu-entry nino-admin-card' );
		const head = Nino.adminUi.el( 'div', 'menu-entry-head' );
		const focus = function( at, role ) { return 'e'+ at+ '.'+ role };

		head.appendChild( Nino.adminUi.el( 'strong', 'menu-entry-name', t('label/entry', String( index + 1 )) ) );
		head.appendChild( kindSelect( t('label/kind'), [ 'page', 'link' ], entry.kind, function( kind ) {

			if( kind === entry.kind )
				return;

			const next = model.newEntry( menu, kind, [], { uri : ( state.registry.pages[0] ?? { uri : '' } ).uri, base : entry.id } );

			// The entry keeps its id, and so its keys and the keys of its panel
			next.id = entry.id;
			next.label = kind === 'link' || model.hasText( state.current.texts, model.nameOf( menu, entry.id ) ) === true ? model.nameOf( menu, entry.id ) : '';
			next.panel = entry.panel;
			state.current.entries[index] = next;
			changed();
			redraw();
		}, 'kind/' ) );
		head.appendChild( moves( state.current.entries, index, focus, redraw ) );
		row.appendChild( head );

		const fields = Nino.adminUi.el( 'div', 'menu-entry-fields' );

		if( entry.kind === 'page' ) {
			fields.appendChild( pageSelect( entry.uri, function( value ) { entry.uri = value; changed() } ) );
			fields.appendChild( labelField( entry, menu, entry, t('label/label', language()), pageName( entry.uri ) ) );
		}
		else {
			fields.appendChild( addressField( entry ) );
			fields.appendChild( targetSelect( entry ) );
			fields.appendChild( labelField( entry, menu, entry, t('label/label', language()), '' ) );
		}

		row.appendChild( fields );

		const panel = Nino.adminUi.el( 'div', 'menu-entry-panel' );

		panel.appendChild( Nino.adminUi.switchField( {
			key : 'panel',
			label : t('label/panel'),
			checked : entry.panel !== null,
			on : t('label/on'),
			off : t('label/off'),
		} ) );

		panel.querySelector('input').addEventListener( 'change', function( event ) {
			model.setPanel( entry, event.target.checked );
			state.open[entry.id] = event.target.checked;
			changed();
			redraw();
		} );

		if( entry.panel !== null ) {

			const columns = Nino.adminUi.selectField( {
				key : 'columns',
				label : t('label/columns'),
				value : entry.panel.columns,
				options: [ 1, 2, 3, 4 ].map( function( number ) { return { value : number, label : String( number ) } } ),
				onChange : function( value ) {
					model.setColumns( entry.panel, Number( value ) );
					changed();
					redraw();
				},
			} );

			const toggle = button( t( state.open[entry.id] === true ? 'label/close-panel' : 'label/edit-panel' ), function() {
				state.open[entry.id] = state.open[entry.id] !== true;
				state.focus = 'p'+ entry.id;
				redraw();
			}, undefined, 'p'+ entry.id, 'nino-admin-btn-secondary' );

			toggle.setAttribute( 'aria-expanded', state.open[entry.id] === true ? 'true' : 'false' );
			panel.appendChild( columns );
			panel.appendChild( toggle );

			if( state.open[entry.id] === true )
				panel.appendChild( drawPanel( entry, redraw ) );
		}

		row.appendChild( panel );

		return row;
	};

	/**
	 *	The editor of the open menu, drawn into its level
	 */
	const drawEditor = function() {

		const editor = root('menu-editor');
		const menu = state.current;
		const redraw = function() { drawEditor() };

		editor.innerHTML = '';

		const back = Nino.adminUi.el( 'a', 'nino-admin-back-link', t('label/back') );

		back.href = '#menu';
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			leave();
		} );

		const locales = state.registry.locales;
		const select = Nino.adminUi.localeSelect( {
			locales : locales,
			inactive : locales.filter( function( locale ) { return state.registry.active.indexOf( locale ) === -1 } ),
			current : state.locale,
			label : t('label/language'),
			onChange : function( locale ) { state.locale = locale; redraw() },
		} );

		editor.appendChild( Nino.adminUi.contextBar( back, [ select ] ) );
		editor.appendChild( Nino.adminUi.el( 'h2', 'menu-editor-title', menu.key ) );

		if( menu.kernel === true )
			editor.appendChild( Nino.adminUi.notice( t('state/kernel', menu.key) ) );

		const tools = Nino.adminUi.el( 'div', 'nino-admin-actions' );

		if( menu.kernel === true )
			tools.appendChild( button( t('label/seed', menu.key), function() {

				const have = menu.entries.filter( function( entry ) { return entry.kind === 'page' } ).map( function( entry ) { return entry.uri } );

				( state.registry.navPages[menu.key] ?? [] ).forEach( function( uri ) {
					if( have.indexOf( uri ) === -1 )
						menu.entries.push( model.newEntry( menu.key, 'page', menu.entries, { uri : uri } ) );
				} );

				changed();
				redraw();
			}, undefined, undefined, 'nino-admin-btn-secondary' ) );

		if( tools.children.length > 0 )
			editor.appendChild( tools );

		const list = Nino.adminUi.el( 'ol', 'menu-entries' );

		if( menu.entries.length === 0 )
			editor.appendChild( Nino.adminUi.emptyState( t('state/no-entries') ) );

		menu.entries.forEach( function( entry, index ) { list.appendChild( drawEntry( entry, index, redraw ) ) } );
		editor.appendChild( list );

		const add = Nino.adminUi.el( 'div', 'nino-admin-actions' );

		add.appendChild( button( '+ '+ t('label/add-entry'), function() {
			menu.entries.push( model.newEntry( menu.key, 'page', menu.entries, { uri : ( state.registry.pages[0] ?? { uri : '' } ).uri } ) );
			state.focus = 'e'+ ( menu.entries.length - 1 )+ '.up';
			changed();
			redraw();
		}, undefined, 'add', 'nino-admin-btn-secondary' ) );
		editor.appendChild( add );

		const bar = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );
		const status = Nino.adminUi.el( 'span' );

		state.line = Nino.adminUi.status( status );
		bar.appendChild( status );
		bar.appendChild( Nino.adminUi.button( t('label/save'), function() { save( function() {} ) }, { className : 'nino-admin-btn-primary' } ) );
		editor.appendChild( bar );

		if( isDirty() === true )
			state.line.dirty();

		if( state.focus !== '' ) {
			const target = Array.from( editor.querySelectorAll('[data-focus]') ).find( function( one ) { return one.dataset.focus === state.focus } );

			if( target !== undefined && target.disabled !== true )
				target.focus();

			state.focus = '';
		}
	};

	const save = function( done ) {

		if( state.current === null ) {
			done( true );
			return;
		}

		const sent = payload();

		state.line.saving();

		Nino.admin.menu.call( 'save', sent, function( status, response ) {

			if( status !== 200 ) {
				state.line.error( status, response, '/_admin/menu/error/save' );
				done( false );
				return;
			}

			state.saved = JSON.stringify( sent );
			state.current.taken = state.current.taken.concat( response.made.keys, response.made.slots ).filter( function( name, at, all ) { return all.indexOf( name ) === at } );
			state.line.saved();

			// The texts and the slots are the Text and Images panels' to show again
			if( typeof Nino.admin.touch === 'function' ) {
				Nino.admin.touch('texts');
				Nino.admin.touch('slots');
			}

			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();

			Nino.admin.menu.reload( function() {} );
			done( true );
		} );
	};

	const leave = function() {

		const go = function() {
			state.current = null;
			state.line = null;
			Nino.adminUi.backTo( 'menu-list', null );
		};

		if( typeof Nino.admin.dirty === 'object' && isDirty() === true )
			Nino.admin.dirty.guard( [ 'menu' ], go );
		else
			go();
	};

	/**
	 *	Open a menu in the editor: the registry first - the pages, the languages and the slots as they are now - then the menu
	 */
	const open = function( key, kernel, fresh ) {

		const start = function( loaded ) {

			state.current = { key : key, kernel : kernel, entries : loaded.entries, texts : loaded.texts, taken : loaded.taken };
			state.saved = fresh === true ? '' : JSON.stringify( payload() );
			state.open = {};
			state.note = {};
			state.locale = state.registry.locale;
			drawEditor();
			Nino.adminUi.showLevel( 'menu-editor' );
		};

		Nino.admin.menu.call( 'registry', {}, function( status, response ) {

			if( status !== 200 ) {
				Nino.adminUi.showError( root('menu-list'), status, response, '/_admin/menu/error/registry' );
				return;
			}

			state.registry = response;

			if( fresh === true ) {
				start( { entries : [], texts : {}, taken : [] } );
				return;
			}

			Nino.admin.menu.call( 'load', { key : key }, function( loadStatus, loaded ) {

				if( loadStatus !== 200 ) {
					Nino.adminUi.showError( root('menu-list'), loadStatus, loaded, '/_admin/menu/error/load' );
					return;
				}

				start( loaded );
			} );
		} );
	};

	/**
	 *	The list of the menus, with the form of a new one
	 */
	const drawList = function( data ) {

		const mount = root('menu-list');
		const list = Nino.adminUi.el( 'ul', 'nino-admin-list menu-list' );

		state.menus = data.menus;
		state.navs = data.navs;
		mount.innerHTML = '';

		if( data.menus.length === 0 )
			mount.appendChild( Nino.adminUi.emptyState( t('state/no-menus') ) );

		data.menus.forEach( function( menu ) {

			const row = Nino.adminUi.el( 'li', 'menu-list-row' );
			const opener = Nino.adminUi.el( 'button', 'nino-admin-list-link', '' );
			const name = Nino.adminUi.el( 'strong', '', menu.key );
			const meta = Nino.adminUi.el( 'small', '', t('label/entries', String( menu.entries )) );

			opener.type = 'button';
			opener.dataset.key = menu.key;
			opener.appendChild( name );
			opener.appendChild( meta );

			if( menu.kernel === true ) {
				const badge = Nino.adminUi.el( 'span', 'nino-admin-badge', t('label/replaces') );

				badge.dataset.state = 'ok';
				opener.appendChild( badge );
			}

			opener.addEventListener( 'click', function() { open( menu.key, menu.kernel, false ) } );
			row.appendChild( opener );
			row.appendChild( button( t('label/delete'), function() {

				Nino.adminUi.confirm( 'destructive', {
					message : t('confirm/delete', menu.key),
					yes : Nino.content.getText('/_admin/common/label/delete'),
					no : Nino.content.getText('/_admin/common/label/cancel'),
					opener : row,
					onChoose : function( ok ) {

						if( ok !== true )
							return;

						Nino.admin.menu.call( 'delete', { key : menu.key }, function( status, response ) {

							if( status !== 200 ) {
								Nino.adminUi.showError( mount, status, response, '/_admin/menu/error/delete' );
								return;
							}

							Nino.admin.menu.reload( function() {} );
						} );
					},
				} );
			}, undefined, undefined, 'nino-admin-btn-danger nino-admin-btn--small' ) );

			list.appendChild( row );
		} );

		mount.appendChild( list );
		mount.appendChild( drawCreate( data ) );
	};

	/**
	 *	The form of a new menu: the key of a navigation of the kernel that has no menu yet, or a key of its own
	 */
	const drawCreate = function( data ) {

		const form = Nino.adminUi.el( 'form', 'menu-new nino-admin-card' );
		const taken = data.menus.map( function( menu ) { return menu.key } );
		const free = data.navs.filter( function( nav ) { return taken.indexOf( nav ) === -1 } );
		const choices = [ { value : '', label : t('label/own-key') } ].concat( free.map( function( nav ) { return { value : nav, label : nav } } ) );
		const choice = Nino.adminUi.selectField( { key : 'nav', label : t('label/kernel-key'), value : '', options : choices } );
		const key = Nino.adminUi.textField( { label : t('label/key'), key : 'key', maxLength : 32, placeholder : 'footer-links' } );
		const input = key.querySelector('input');
		const select = choice.querySelector('select');
		const message = Nino.adminUi.el( 'p', 'nino-admin-hint', '' );

		input.pattern = '[a-z][a-z0-9]*(-[a-z0-9]+)*';

		select.addEventListener( 'change', function() {
			input.disabled = select.value !== '';
			input.value = '';
			message.textContent = '';
		} );

		form.addEventListener( 'submit', function( event ) {

			event.preventDefault();

			const wanted = select.value !== '' ? select.value : input.value.trim();

			if( /^[a-z][a-z0-9]*(-[a-z0-9]+)*$/.test( wanted ) === false || wanted.length > 32 || wanted === 'panel' ) {
				message.textContent = t('state/key-invalid');
				input.focus();
				return;
			}

			if( taken.indexOf( wanted ) !== -1 ) {
				message.textContent = t('state/key-taken', wanted);
				return;
			}

			open( wanted, data.navs.indexOf( wanted ) !== -1, true );
		} );

		form.appendChild( Nino.adminUi.el( 'h3', '', t('label/new') ) );
		form.appendChild( choice );
		form.appendChild( key );
		form.appendChild( message );
		form.appendChild( Nino.adminUi.button( t('label/create'), null, { type : 'submit', className : 'nino-admin-btn-primary' } ) );

		return form;
	};

	Nino.admin.menu = Nino.adminUi.panel( {
		uri : 'menu',
		mount : 'menu-list',
		dirty : { isDirty : isDirty, save : save, discard : function() {
			if( state.current !== null )
				state.saved = JSON.stringify( payload() );
		} },
		render : function( data ) {
			drawList( data );

			// Drawn when its answer arrives and not opened by the person: the focus stays
			if( state.current === null )
				Nino.adminUi.showLevel( 'menu-list', { focus : false } );
		},
	} );

	Nino.admin.menu.model = model;

} )(window,document);
