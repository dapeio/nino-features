/**
 *	Nino features
 *	Builder						The tab "Header/footer" of the pane of the pages (#builder-frames, see Modules\Builder\FramesTab): the frames of the site - the header and the footer, the standard
 *										ones and the named ones - as a table of the workbench with the choice of the side, the search and the actions of a row: edit, choose a block for it, take back the
 *										last choice, keep it as a block, rename, delete - and "New" in the bar; and the editor of one frame, which is the editor of the Builder on a sheet of its own. The editor is
 *										not written here: the tree, the preview, the properties, the texts, the pictures, Undo and Save are Nino.admin.builder.editor - the functions of the panel of the pages -
 *										and what this script brings is the adapter that says where the requests of the sheet go (builder/frame-<action>), where back leads and what the panel is called, and
 *										the list, the forms and the questions that stand round it. Everything it shows is the answer of the API (see Modules\Builder\Admin and Frames); the step of the
 *										account - what it may write in a frame - is the server's, and the editor reads it from the answer.
 *
 *										The address is #builder-frames (the list) and #builder-frames/<frame> (one frame in the editor): header, footer, header-landing - the word of the side and, for a named
 *										frame, its word. The way back from the editor is the list; the old address #builder/@frames leads to the tab.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const TAB = 'builder-frames';

	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/builder/'+ key ), ...params );
	};

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	const here = function() {
		return Nino.admin.router.current();
	};

	// The seam of the panel of the pages: the panel that asks the server, and the editor of its sheets
	const panel = function() {
		return Nino.admin.builder;
	};

	const editor = function() {
		return Nino.admin.builder.editor;
	};

	// What the tab holds: the answer of the list, the choice of the side beside the search and what was typed in it, the control that is the main button, and the frame that is open
	const F = {
		data		: null,
		ready		: false,
		loading	: false,
		side		: 'all',
		query		: '',
		button	: null,
		open		: null,
		keys		: false,
		startup	: false,
	};

	// A frame is the word of its side and, where it is a named one, its word: header, footer-landing
	const KEY = /^(?:header|footer)(?:-[a-z0-9]+(?:-[a-z0-9]+)*)?$/;

	const sideOf = function( frame ) {
		return frame.side === 'footer' ? 'footer' : 'header';
	};

	const partsOf = function( key ) {
		return [ key ];
	};

	// What the address names: the list ('') or the key of a frame; null for an address that is none
	const targetOf = function( parts ) {

		if( parts.length === 0 )
			return { key : '' };

		return parts.length === 1 && KEY.test( parts[0] ) === true ? { key : parts[0] } : null;
	};

	const sheetOpen = function() {

		const model = editor().state();

		return model.sheet === 'block' && model.adapter.panel === TAB && model.ready === true;
	};

	/**
	 *	A request of the tab: the actions of the panel of the pages, in the language the workbench is working in
	 */
	const call = function( action, payload, done, extra ) {
		panel().call( action, Object.assign( { locale : Nino.admin.workLocale.current }, payload ), done, extra );
	};

	const clearFail = function() {

		const old = dc.getElementById('builder-frames-list').querySelector('.builder-list-error');

		if( old !== null )
			old.remove();
	};

	// What went wrong with an action of the list is said above the table, where the person is looking - and nowhere else - until the next action or a new list
	const fail = function( status, response, fallback ) {

		const mount = dc.getElementById('builder-frames-list');

		clearFail();

		const message = el( 'p', 'nino-admin-error builder-list-error', Nino.adminUi.api.errorText( status, response, fallback ) );

		message.setAttribute( 'role', 'alert' );
		mount.insertBefore( message, mount.firstChild );
	};

	// --- the names -----------------------------------------------------------------------------------------------------------

	const frameOf = function( key ) {
		return ( F.data === null ? [] : F.data.frames ).find( function( frame ) { return frame.key === key } );
	};

	// What a frame is called: the standard ones by the word of their side, a named one by the name it was given - and by its word where it has none
	const frameName = function( frame ) {
		return frame.default === true ? word( 'frame/'+ sideOf( frame ) ) : ( frame.name !== '' ? frame.name : frame.key.slice( sideOf( frame ).length + 1 ) );
	};

	// The word of a new frame, made of its name: small letters and digits, the rest a single hyphen, at most the length the server takes
	const frameWordOf = function( text ) {

		const made = String( text ).normalize('NFKD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /ß/g, 'ss' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-+/, '' ).slice( 0, 40 ).replace( /-+$/, '' );

		return made === '' || made === 'none' || made === 'default' ? 'frame' : made;
	};

	// The key a block has from its name: lower case letters, digits and hyphens, beginning with a letter
	const blockKeyOf = function( text ) {

		const key = String( text ).normalize('NFKD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /ß/g, 'ss' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^[^a-z]+/, '' ).slice( 0, 32 ).replace( /-+$/, '' );

		return key === '' ? 'blocks' : key;
	};

	// The name of an action on a frame: the standard one is told by the word of its side alone - its name is that word -, a named one by the side and its name
	const nameOf = function( stem, frame, title ) {
		return frame.default === true ? word( 'frame/'+ stem+ '-'+ sideOf( frame ) ) : word( 'frame/'+ stem+ '-of-'+ sideOf( frame ), title );
	};

	const usesWord = function( uses ) {
		return uses === 0 ? word('frame/uses-none') : ( uses === 1 ? word('frame/uses-one') : word( 'frame/uses-many', String( uses ) ) );
	};

	const nameIn = function( names, code ) {

		if( typeof names === 'string' )
			return names;

		const map = names ?? {};
		const first = Object.keys( map )[0];

		return String( map[code] ?? map.en_US ?? ( first === undefined ? '' : map[first] ) );
	};

	const nameField = function( values ) {

		const work = Nino.admin.workLocale.current;

		return { key : 'name', type : 'locales', label : word('frame/name'), locales : F.data.locales.map( function( entry ) { return entry.locale } ), work : work, values : values, required : false, maxLength : 80,
			validate : function( value ) { return String( ( value ?? {} )[work] ?? '' ).trim() === '' ? word('frame/name-missing') : '' } };
	};

	// A refusal of the server is said at the field it names, in the dialog that asked
	const refused = function( ctl, status, response, fallback ) {

		const text = Nino.adminUi.api.errorText( status, response, fallback );
		const field = ( response ?? {} ).field;

		ctl.fail( [ 'name', 'word', 'from', 'key', 'description' ].indexOf( field ) !== -1 ? field : null, text );
	};

	// The languages of a dialog that asks for a name, but the working one, are in a part that is closed: the rows are in it, hidden, and still the controls of the field
	const tuck = function() {

		const list = Array.from( dc.querySelectorAll('.nino-admin-localerows-list') ).pop() ?? null;
		const rows = list === null ? [] : Array.from( list.querySelectorAll('.nino-admin-localerow') ).filter( function( row ) { return row.classList.contains('is-work') === false } );

		if( rows.length === 0 )
			return;

		const inner = el( 'div', '' );

		inner.appendChild( el( 'p', '', word('frame/more-note') ) );
		rows.forEach( function( row ) { inner.appendChild( row ) } );

		list.appendChild( Nino.adminUi.accordion( { id : 'builder-frames-more', sections : [ { key : 'more', label : word('frame/more-languages'), content : inner } ], collapsible : true, level : 4 } ).el );
	};

	// --- the list ------------------------------------------------------------------------------------------------------------

	// A frame that was changed since the list was drawn - in the editor - is not written over: the list is drawn again and says so
	const frameFailed = function( status, response, frame ) {

		if( status === 409 && ( response ?? {} ).code === 'builder_frame_changed' ) {
			Nino.adminUi.say( word( 'frame/changed-'+ sideOf( frame ) ) );
			reload();
			return;
		}

		fail( status, response, '/_admin/builder/error/frame' );
	};

	// Give a frame a block of its kind: the dialog of the blocks of the panel of the pages, then the question, then the server writes - and the last choice can be taken back
	const askChoose = function( frame, opener ) {

		const name = frameName( frame );

		clearFail();

		panel().chooseFrame( {
			name : name,
			standard : frame.default === true,
			locked : frame.locked === true,
			opener : opener,
			dir : F.data.dir ?? '',
			load : function( done ) { call( 'framechoices', { template : frame.template }, done ) },
			tile : function( key, done ) { call( 'frametile', { template : frame.template, key : key }, done ) },
			side : sideOf( frame ),
			fail : function( status, response ) { frameFailed( status, response, frame ) },
			none : function() { Nino.adminUi.say( word( 'frame/none-'+ sideOf( frame ) ) ) },
			apply : function( key, entry ) {

				call( 'framechoose', { template : frame.template, key : key, base : frame.stamp }, function( status, response ) {

					if( status !== 200 ) {
						frameFailed( status, response, frame );
						return;
					}

					Nino.adminUi.say( word( 'frame/chosen-'+ sideOf( frame ), name, localized( entry.name ) ) );
					reload();
				} );
			},
		} );
	};

	const askUndo = function( frame ) {

		clearFail();

		call( 'framerestore', { template : frame.template, base : frame.stamp }, function( status, response ) {

			if( status !== 200 ) {
				frameFailed( status, response, frame );
				return;
			}

			Nino.adminUi.say( word( 'frame/undone-'+ sideOf( frame ), frameName( frame ) ) );
			reload();
		} );
	};

	// A text that is a map of languages as the words of the panel say it: the one of the workbench, else English, else the first
	const localized = function( value ) {

		if( typeof value === 'string' )
			return value;

		if( value === null || typeof value !== 'object' )
			return '';

		const tag		= String( ( dc.documentElement && dc.documentElement.lang ) || '' ).replace( '-', '_' );
		const names	= Object.keys( value );
		const near	= names.find( function( name ) { return tag !== '' && name.slice( 0, 2 ) === tag.slice( 0, 2 ) } );

		return String( value[tag] ?? ( near !== undefined ? value[near] : undefined ) ?? value.en_US ?? value[names[0]] ?? '' );
	};

	// A new frame: a name in each language, the word - made of the name until it is written over - and what it is made of: the standard frame, another frame of the site or a block, each
	// with its side in front - the side is the one of the source. The texts come along, and the editor opens on the new frame. Where the server refuses after the dialog of the blocks, the
	// form opens again with what was typed and the refusal at its field
	const askNew = function( opener, again ) {

		clearFail();

		const work = Nino.admin.workLocale.current;
		const sources = [];
		const sides = F.side === 'all' ? [ 'header', 'footer' ] : [ F.side ];

		sides.forEach( function( side ) {

			F.data.frames.filter( function( frame ) { return frame.side === side } ).forEach( function( frame ) {
				sources.push( { value : side+ '|'+ frame.template, label : word( 'frame/'+ side )+ ': '+ ( frame.default === true ? word('frame/source-standard') : word( 'frame/source-frame' )+ ' - '+ frameName( frame ) ) } );
			} );

			sources.push( { value : side+ '|@block', label : word( 'frame/'+ side )+ ': '+ word('frame/source-block') } );
		} );

		const was = again ?? { name : {}, word : '', from : sources[0].value };

		// What is asked of the server: the side, the word, the names, and where the frame comes from; a block is chosen after the form
		const make = function( values, block, ctl ) {

			const side = String( values.from ).split('|')[0];
			const chosen = String( values.word ).trim() === '' ? frameWordOf( nameIn( values.name, work ) ) : String( values.word ).trim();
			const asked = Object.assign( { side : side, word : chosen, name : values.name }, block === '' ? { from : String( values.from ).split('|')[1] } : { block : block } );

			call( 'frame-create', asked, function( status, response ) {

				if( status !== 200 ) {
					refused( ctl, status, response, '/_admin/builder/error/frame-create' );
					return;
				}

				ctl.close();
				Nino.adminUi.say( word( 'frame/created-'+ side, nameIn( values.name, work ) ) );
				go( partsOf( response.key ) );
			} );
		};

		Nino.adminUi.formDialog( {
			title		: word('frame/new-title'),
			message	: word('frame/new-message'),
			fields	: [
				nameField( was.name ),
				{ key : 'word', type : 'text', label : word('frame/word-label'), value : was.word, required : false, maxLength : 40, help : word('frame/word-help') },
				{ key : 'from', type : 'select', label : word('frame/source'), options : sources, value : was.from, required : true },
			],
			submit	: word('frame/create'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				if( String( values.from ).split('|')[1] !== '@block' ) {
					make( values, '', ctl );
					return;
				}

				// The form is closed for the dialog of the blocks - a dialog stands alone -; what the server refuses after that opens the form again, with what was typed
				ctl.close();

				const side = String( values.from ).split('|')[0];
				const said = { close : function() {}, idle : function() {}, fail : function( field, text ) {

					askNew( opener, values );

					const input = Array.from( dc.querySelectorAll('input') ).filter( function( one ) { return one.dataset.key === ( field === 'word' ? 'word' : 'name' ) && ( field === 'word' || one.dataset.locale === work ) } ).pop();

					if( input !== undefined )
						Nino.adminUi.fieldError( input.closest('label') ?? input.parentNode, input, 'builder-frame-new-error', text );
					else
						Nino.adminUi.say( text, { kind : 'error' } );
				} };

				panel().chooseFrame( {
					name		: word( 'frame/'+ side ),
					side		: side,
					opener	: opener,
					dir			: F.data.dir ?? '',
					confirm	: false,
					load		: function( done ) { call( 'framechoices', { template : 'frame-'+ side }, done ) },
					tile		: function( key, done ) { call( 'frametile', { template : 'frame-'+ side, key : key }, done ) },
					fail		: function( status, response ) { fail( status, response, '/_admin/builder/error/frame-create' ) },
					none		: function() { Nino.adminUi.say( word( 'frame/none-'+ side ), { kind : 'error' } ) },
					apply		: function( key ) { make( values, key, said ) },
				} );
			},
		} );

		// The word follows the name in the working language until it is written by hand
		const inputs = Array.from( dc.querySelectorAll('input') );
		const named = inputs.filter( function( input ) { return input.dataset.key === 'name' && input.dataset.locale === work } ).pop();
		const typed = inputs.filter( function( input ) { return input.dataset.key === 'word' } ).pop();
		let own = was.word !== '';

		if( named !== undefined && typed !== undefined ) {
			typed.addEventListener( 'input', function() { own = true } );
			named.addEventListener( 'input', function() {

				if( own === false )
					typed.value = frameWordOf( named.value );
			} );
		}

		tuck();
	};

	// A new name of a named frame, in each language: the word and the texts stay
	const askRename = function( frame, opener ) {

		clearFail();

		const work = Nino.admin.workLocale.current;
		const side = sideOf( frame );

		Nino.adminUi.formDialog( {
			title		: word( 'frame/rename-title-'+ side ),
			message	: word('frame/rename-message'),
			fields	: [ nameField( Object.assign( {}, frame.names, { [ work ] : frame.names[work] ?? frame.name } ) ) ],
			submit	: word('frame/rename'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				call( 'frame-rename', { template : frame.template, name : values.name }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/builder/error/frame-rename' );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( 'frame/renamed-'+ side, nameIn( values.name, work ) ) );
					reload();
				} );
			},
		} );

		tuck();
	};

	// A frame taken away, after the question: a frame a page chooses is refused by the server, which gives the number of the pages and names them
	const askRemove = function( frame, opener ) {

		clearFail();

		const title = frameName( frame );
		const side = sideOf( frame );

		// The list knows that pages choose the frame: the question says how many and which - three by name, the rest counted - and has nothing to confirm; the server is the one that decides all the same
		if( frame.uses > 0 ) {

			const pages = ( frame.pages ?? [] ).join(', ')+ ( frame.uses > ( frame.pages ?? [] ).length ? ' '+ word( 'frame/remove-more', String( frame.uses - ( frame.pages ?? [] ).length ) ) : '' );

			Nino.adminUi.choiceDialog( {
				title		: word( 'frame/remove-title-'+ side ),
				message	: word( 'frame/remove-used-'+ side, title, String( frame.uses ), pages ),
				opener	: opener,
				choices	: [ { value : 'ok', label : word('frame/remove-ok'), kind : 'primary' } ],
				onChoose	: function() {},
			} );

			return;
		}

		Nino.adminUi.confirm( 'destructive', {
			title		: word( 'frame/remove-title-'+ side ),
			message	: word( 'frame/remove-message-'+ side, title ),
			yes			: word('frame/remove'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				call( 'frame-remove', { template : frame.template }, function( status, response ) {

					if( status !== 200 ) {
						fail( status, response, '/_admin/builder/error/frame-remove' );
						return;
					}

					Nino.adminUi.say( word( 'frame/removed-'+ side, title ) );
					reload();
				} );
			},
		} );
	};

	// A frame of the site kept as a block of the project: a name in each language, the short name the folder has - made of the name until it is written over - and a description. The
	// block is the frame as it is SAVED: what is not saved in the editor is asked about first, and the frame is not changed. The right that writes an own block is the one of Themelab,
	// whose action it is
	const askAdopt = function( frame, opener ) {

		clearFail();

		const title = frameName( frame );
		const side = sideOf( frame );
		const work = Nino.admin.workLocale.current;
		const locales = F.data.locales.map( function( entry ) { return entry.locale } );
		const first = word( 'frame/adopt-default-name', title );

		// What is asked of the server: the frame, the names and the key that stands in the field - the one that is written
		const asked = function( values, more ) {
			return Object.assign( { template : frame.template, key : String( values.key ).trim() === '' ? blockKeyOf( nameIn( values.name, work ) ) : String( values.key ).trim(), name : values.name, description : values.description, locale : work }, more );
		};

		Nino.adminUi.formDialog( {
			title		: nameOf( 'adopt', frame, title ),
			message	: word( 'frame/adopt-message-'+ side ),
			fields	: [
				nameField( { [ work ] : first } ),
				{ key : 'key', type : 'text', label : word('frame/adopt-key'), value : blockKeyOf( first ), required : true, maxLength : 32, help : word('frame/adopt-key-help') },
				{ key : 'description', type : 'locales', label : word('frame/adopt-description'), locales : locales, work : work, values : {}, required : false, maxLength : 240, multiline : true, rows : 3 },
			],
			submit	: word('frame/adopt-submit'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				const write = function( more ) {

					Nino.admin.themelab.call( 'adopt', asked( values, more ), function( status, response ) {

						if( status !== 200 ) {
							refused( ctl, status, response, '/_admin/builder/error/frame-adopt' );
							return;
						}

						ctl.close();
						Nino.adminUi.say( word( 'frame/adopt-done', nameIn( values.name, work ) ) );
					} );
				};

				// Asked first, nothing written: which language lacks a text for which part - none is made up for it; the person fills them from the working language, or does not
				Nino.admin.themelab.call( 'adopt', asked( values, { check : true } ), function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/builder/error/frame-adopt' );
						return;
					}

					const gaps = Array.isArray( response.gaps ) === true ? response.gaps : [];

					if( gaps.length === 0 ) {
						write( {} );
						return;
					}

					const lines = gaps.map( function( gap ) { return Nino.adminUi.localeName( gap.locale, locales )+ ': '+ gap.parts.join(', ') } );
					const own = gaps.some( function( gap ) { return gap.locale === work } );

					ctl.idle();

					Nino.adminUi.choiceDialog( {
						nested	: true,
						title		: word('frame/adopt-gaps-title'),
						message	: word( own === true ? 'frame/adopt-gaps-own' : 'frame/adopt-gaps-message', lines.join( '; ' ), Nino.adminUi.localeName( work, locales ) ),
						choices	: ( own === true ? [] : [ { value : 'fill', label : word( 'frame/adopt-gaps-fill', Nino.adminUi.localeName( work, locales ) ), kind : 'primary' } ] ).concat( [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' } ] ),
						opener	: opener,
						onChoose	: function( value ) {

							if( value === 'fill' )
								write( { fill : work } );
						},
					} );
				} );
			},
		} );

		tuck();

		// The key that stands in the field is the key that is written: it follows the name in the working language until it is written by hand
		const fields = Array.from( dc.querySelectorAll('input') );
		const named = fields.filter( function( input ) { return input.dataset.key === 'name' && input.dataset.locale === work } ).pop();
		const keyed = fields.filter( function( input ) { return input.dataset.key === 'key' } ).pop();
		let written = false;

		if( named !== undefined && keyed !== undefined ) {
			keyed.addEventListener( 'input', function() { written = true } );
			named.addEventListener( 'input', function() {

				if( written === false )
					keyed.value = blockKeyOf( named.value );
			} );
		}
	};

	// The two lines of a row of the table: the name, and quietly under it the word the frame is called by in the address and in a page's choice
	const nameCell = function( value, row ) {

		const cell = el( 'span', 'builder-cell-name' );

		cell.appendChild( el( 'strong', '', value ) );
		const line = el( 'span', 'builder-cell-file', row.word );

		// On a phone the column of the side gives way, and the side is said here, after the word
		line.appendChild( el( 'span', 'builder-cell-side', ' \u00b7 '+ row.side ) );
		cell.appendChild( line );

		return cell;
	};

	// Whether the pane is as narrow as a phone: under 40 rem the row has the pencil and one menu, "More actions", for everything else - the name and the columns have the room
	const narrow = function() {
		return typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 40rem)').matches === true;
	};

	// The small buttons of a row, the dangerous one last: edit and choose stand in the row, what is left is behind "more" - the table does that by itself; on a narrow pane the pencil is the only one
	// that stands and all of the others are behind "more"
	const rowButtons = function( row ) {
		return narrowed( rowActionButtons( row ) );
	};

	const narrowed = function( buttons ) {

		if( narrow() === false || buttons.length < 2 )
			return buttons;

		const named = function( button ) { return String( button.getAttribute('aria-label') || button.title || button.textContent || '' ).trim() };

		return [ buttons[0], Nino.adminUi.menuButton( {
			label : Nino.adminUi.text('/_admin/common/table/more'),
			small : true,
			items : buttons.slice( 1 ).map( function( button ) {
				return { label : named( button ), danger : button.classList.contains('nino-admin-btn-danger'), onClick : function() { button.click() } };
			} ),
		} ).el ];
	};

	const rowActionButtons = function( row ) {

		const frame = row.frame;
		const side = sideOf( frame );
		const title = frameName( frame );
		const actions = [];

		actions.push( Nino.adminUi.iconButton( 'pencil', nameOf( 'edit', frame, title ), function() { go( partsOf( frame.key ) ) }, { small : true } ) );
		actions.push( Nino.adminUi.button( word('frame/choose'), function( event ) { askChoose( frame, event.currentTarget ) }, { small : true, name : nameOf( 'choose', frame, title ) } ) );

		if( frame.undo === true )
			actions.push( Nino.adminUi.button( word('frame/undo'), function() { askUndo( frame ) }, { small : true, name : nameOf( 'undo', frame, title ) } ) );

		if( F.data.bridge === true && typeof Nino.admin.themelab === 'object' )
			actions.push( Nino.adminUi.button( word('frame/adopt'), function( event ) { askAdopt( frame, event.currentTarget ) }, { small : true, name : nameOf( 'adopt', frame, title ) } ) );

		if( frame.default === false ) {
			actions.push( Nino.adminUi.button( word('frame/rename'), function( event ) { askRename( frame, event.currentTarget ) }, { small : true, name : word( 'frame/rename-of-'+ side, title ) } ) );
			actions.push( Nino.adminUi.button( word('frame/remove'), function( event ) { askRemove( frame, event.currentTarget ) }, { small : true, danger : true, name : word( 'frame/remove-of-'+ side, title ) } ) );
		}

		return actions;
	};

	// The choice of the side beside the search: all, the headers, the footers. It filters at once, with no request
	const choice = function() {

		return { id : 'builder-frames-side', label : word('frame/side-label'), options : [ { value : 'all', label : word('frame/all') }, { value : 'header', label : word('frame/header') }, { value : 'footer', label : word('frame/footer') } ], value : F.side, onChange : function( value ) {

			if( value === F.side )
				return;

			F.side = value;
			render();
			Nino.adminUi.showLevel( 'builder-frames-list', { focus : dc.getElementById('builder-frames-side') ?? false } );
		} };
	};

	// What the main button says: the word of the side that is chosen, and the word for both where both are meant
	const newWord = function() {
		return word( F.side === 'header' ? 'frame/new-header' : ( F.side === 'footer' ? 'frame/new-footer' : 'frame/new' ) );
	};

	const render = function() {

		const data = F.data;
		const mount = dc.getElementById('builder-frames-list');
		const holder = el( 'div', '' );
		const rows = data.frames.filter( function( frame ) { return F.side === 'all' || frame.side === F.side } ).map( function( frame ) {
			return { key : frame.key, name : frameName( frame ), word : frame.default === true ? word('frame/standard') : word( 'frame/word-of', frame.key.slice( sideOf( frame ).length + 1 ) ), side : word( 'frame/'+ sideOf( frame ) ), uses : frame.uses, frame : frame };
		} );

		mount.innerHTML = '';
		F.button = null;
		mount.appendChild( holder );

		Nino.adminUi.table( {
			mount			: holder,
			filter		: choice(),
			rowKey		: 'key',
			columns		: [
				{ key : 'name', label : word('frame/col-name'), type : 'string', render : nameCell },
				{ key : 'side', label : word('frame/col-side'), type : 'string' },
				{ key : 'uses', label : word('frame/col-pages'), type : 'integer', render : function( value ) { return usesWord( value ) } },
			],
			rows			: rows,
			actions		: rowButtons,
			labels		: { search : word('frame/search'), empty : word('frame/none-listed'), noMatch : word('frame/no-match') },
			search		: true,
			query			: F.query,
			onQuery		: function( query ) { F.query = query },
			searchText	: function( row ) { return row.key+ ' '+ row.frame.template+ '.tpl' },
		} );

		F.button = Nino.adminUi.button( newWord(), function() { askNew( F.button ) }, { kind : 'primary', id : 'builder-frame-new' } );
		mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : F.button } ).el );
	};

	// The list of the frames asked for, and drawn
	const load = function( then ) {

		if( F.loading === true )
			return;

		F.loading = true;

		call( 'frame-list', {}, function( status, response ) {

			F.loading = false;

			if( status !== 200 ) {

				F.ready = false;
				fail( status, response, '/_admin/builder/error/frames' );
				Nino.adminUi.showLevel( 'builder-frames-list', { focus : false } );

				return;
			}

			F.data = response;
			F.ready = true;
			render();

			if( typeof then === 'function' )
				then();
		} );
	};

	const reload = function( then ) {
		load( then );
	};

	// The list is left for the editor: its previews are taken out of the page, and the list is drawn again where it is come back to
	const unloadList = function() {

		dc.getElementById('builder-frames-list').innerHTML = '';
		F.button = null;
	};

	// --- the editor: the sheet of a frame ------------------------------------------------------------------------------------

	// The block that takes the place of a frame: the frame is the block of its kind that stands in it, and a block that the project offers for it replaces it - in the draft, so that
	// Undo brings it back. The dialog and its question are the ones of the panel of the pages: a page gives a frame its block by the same one
	const askFrameBlock = function( adapter ) {

		const sheet = editor().state();

		panel().chooseFrame( {
			name		: adapter.name,
			standard	: adapter.frameKey === adapter.side,
			side		: adapter.side,
			opener	: sheet.view.bar.buttons.frameblock,
			dir			: sheet.adapter.dir ?? '',
			load		: function( done ) { editor().call( 'blocks', {}, done ) },
			tile		: function( key, done ) { editor().call( 'tile', { key : key }, done ) },
			fail		: function( status, response ) { sheet.view.bar.line.error( status, response ) },
			none		: function() { sheet.view.bar.line.idle( word( 'frame/none-'+ adapter.side ) ) },
			apply		: function( key, entry ) {

				editor().call( 'block', { key : key }, function( code, block ) {

					if( code !== 200 )
						sheet.view.bar.line.error( code, block );
					else if( editor().replace( block, entry.name ) === false )
						sheet.view.bar.line.fail( word('frame/insert-fail') );
				} );
			},
		} );
	};

	// The sheet of a frame: the requests are the actions of the panel of the pages made for a frame - builder/frame-<action> - and the template is the one every request names. The step the
	// account has - what the editor shows it may change - is the one the server answers with
	const adapterOf = function( key, response ) {

		const entry = frameOf( key );
		const side = response.side === 'footer' ? 'footer' : 'header';
		const title = entry === undefined ? ( key === side ? word( 'frame/'+ side ) : key.slice( side.length + 1 ) ) : frameName( entry );
		const template = 'frame-'+ key;
		const buttons = [ { key : 'frameblock', label : word( 'frame/insert-'+ side ), name : key === side ? word( 'frame/insert-'+ side ) : word( 'frame/insert-of-'+ side, title ), onPress : function() { askFrameBlock( adapter ) } } ];

		// Kept as a block by the right that writes an own block: the one of Themelab, which is where the action is
		if( response.bridge === true && typeof Nino.admin.themelab === 'object' )
			buttons.push( { key : 'frameadopt', label : word('frame/adopt'), name : key === side ? word( 'frame/adopt-'+ side ) : word( 'frame/adopt-of-'+ side, title ), onPress : function() { Nino.admin.dirty.guard( [ TAB ], function() { askAdopt( entry ?? { key : key, template : template, side : side, default : key === side, names : {}, name : title }, editor().state().view.bar.buttons.frameadopt ) } ) } } );

		const adapter = {
			panel		: TAB,
			level		: 'builder-frames-sheet',
			frame		: true,
			frameKey	: key,
			side		: side,
			template	: template,
			name		: title,
			slug		: '',
			dir			: response.dir,
			restore	: true,
			sources	: 'block',
			props		: 'panel',
			call		: function( action, payload, done, extra ) { panel().call( 'frame-'+ action, payload, done, extra ) },
			code		: template,
			base		: function() { return { template : template, locale : editor().state().locale } },
			go			: function( parts, options ) { go( parts, options ) },
			href		: function( parts ) { return Nino.admin.router.href( TAB, parts ) },
			set			: function() { Nino.admin.router.set( TAB, partsOf( key ) ) },
			show		: function() { Nino.adminUi.showLevel( 'builder-frames-sheet', { focus : false } ) },
			fail		: function( level, status, answer ) { panel().fail( level, status, answer ) },
			back		: function() { return { parts : [], name : word('tab/frames'), keep : false, block : false } },
			reload	: function() { openFrame( key ) },
			buttons	: buttons,
		};

		return adapter;
	};

	const openFrame = function( key ) {

		const level = dc.getElementById('builder-frames-sheet');

		clearFail();

		call( 'frame-open', { template : 'frame-'+ key }, function( status, response ) {

			if( status !== 200 ) {
				panel().fail( level, status, response );
				Nino.adminUi.showLevel( 'builder-frames-sheet', { focus : false } );
				return;
			}

			// The language moved while the frame was on its way: it is asked for again in the language of the workbench
			if( response.contentLocale !== Nino.admin.workLocale.current ) {
				openFrame( key );
				return;
			}

			// Whether the account may keep the frame as a block is the list's to say
			const open = function() {

				if( editor().open( adapterOf( key, Object.assign( { bridge : F.data !== null && F.data.bridge === true }, response ) ), response ) === false ) {
					go( [] );
					return;
				}

				F.open = { key : key };
				unloadList();
			};

			// The names and the side of the frames are the list's: a frame the list does not know yet - one that was just made, an address that was typed - is asked for first
			if( F.data === null || frameOf( key ) === undefined )
				load( open );
			else
				open();
		} );
	};

	// --- the tab -------------------------------------------------------------------------------------------------------------

	// Another address of the tab: the unsaved work of the editor is asked about first
	const go = function( parts, options ) {

		Nino.admin.dirty.guard( sheetOpen() === true ? [ TAB ] : [], function() {
			Nino.admin.router.go( TAB, parts );
			follow();
		} );

		return options;
	};

	const follow = function() {

		clearFail();

		const at = here();
		const target = targetOf( at.panel === TAB ? at.parts : [] );

		if( target === null ) {
			go( [] );
			return;
		}

		if( target.key === '' ) {

			editor().close();
			F.open = null;
			Nino.admin.router.set( TAB, [] );

			// The list that is asked for is drawn into its mount, which the sheet had given up
			load( function() { Nino.adminUi.showLevel( 'builder-frames-list', { focus : F.button !== null && F.button.isConnected === true ? F.button : Nino.adminUi.panelName( dc.getElementById('builder-frames-list') ) } ) } );

			return;
		}

		openFrame( target.key );
	};

	// The tab, as the workbench asks for it: by its uri
	wn.Nino.admin[TAB] = {

		go			: go,
		follow	: follow,
		reload	: reload,

		showCurrent : function() {

			const at = here();

			// The editor is one for the pane of the pages as well: a page opened there while a sheet of this tab was open took the sheet, and the list that was unloaded for it is drawn by
			// nobody else. The tab that comes back to no sheet draws its list again, and keeps no frame it has not open
			const lost = F.open !== null && sheetOpen() === false;

			if( lost === true )
				F.open = null;

			if( at.panel !== TAB ) {

				if( lost === true && F.ready === true )
					reload( function() {
						Nino.admin.router.set( TAB, [] );
						Nino.adminUi.showLevel( 'builder-frames-list', { focus : false } );
					} );
				// The workbench shows a tab before it writes the address of it, so that the address is still the one of the tab before when a click on the button of this one comes here: the tab
				// is the one that is shown, and the list - the state of the frames, asked for each time - is drawn and gives the address. A sheet that is open is not given up for it
				else if( F.open === null && sheetOpen() === false ) {
					Nino.admin.router.set( TAB, [] );
					load( function() { Nino.adminUi.showLevel( 'builder-frames-list', { focus : false } ) } );
				}

				return;
			}

			const target = targetOf( at.parts );
			const same = sheetOpen() === true && target !== null && F.open !== null && target.key === F.open.key;

			Nino.admin.router.leave( [ TAB ], sheetOpen() === true && same === false, follow, function() {
				Nino.admin.router.set( TAB, F.open === null ? [] : partsOf( F.open.key ) );
			} );
		},
	};

	// The choice of a language in the workbench asks the sheet about its unsaved work by the name of the tab; what it answers is loaded anew in that language
	Nino.admin.workLocale.follow( TAB, function() {

		// Another panel is the one that is shown: this one asks again when it is shown
		if( here().panel !== TAB )
			return;

		Nino.admin.dirty.guard( [ TAB ], function() {
			editor().hold();
			F.data = null;
			follow();
		}, function() {
			Nino.admin.workLocale.set( editor().state().locale );
		} );
	} );

	// A pane that gets narrow or wide - a phone turned, a window resized - has the row of the other shape: the list is drawn again where it is shown
	if( F.keys === false && typeof window.matchMedia === 'function' )
		window.matchMedia('(max-width: 40rem)').addEventListener( 'change', function() {

			if( F.ready === true && F.data !== null && dc.getElementById('builder-frames-list') !== null && here().panel === TAB && sheetOpen() === false )
				render();
		} );

	// The keys of the workbench over a sheet that is open: the undo and the redo, where no field takes the keys for itself
	if( F.keys === false ) {

		F.keys = true;

		dc.addEventListener( 'keydown', function( event ) {

			if( sheetOpen() === false || here().panel !== TAB || event.ctrlKey === false && event.metaKey === false )
				return;

			const held = dc.activeElement;

			if( held !== null && held !== dc.body && ( held.isContentEditable === true || /^(INPUT|SELECT|TEXTAREA)$/.test( held.tagName ) === true ) )
				return;

			const key = String( event.key ).toLowerCase();

			if( key === 'z' || key === 'y' ) {
				event.preventDefault();
				key === 'y' || event.shiftKey === true ? editor().redo() : editor().undo();
			}
		} );
	}

} )(window,document);
