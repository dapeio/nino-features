/**
 *	Nino features
 *	Blocks						The panel of the workbench for the blocks of the project: the list of them - those of the project, which are changed, and those of the
 *										theme in use, which are looked at - and for a block the editor of the Builder on a sheet of its own. The editor is not written here: the tree
 *										view, the lightbox of a component with its dialog of a source, the frame that draws the block alone, Undo and Redo
 *										and the picture of a block are Nino.admin.builder.editor - the functions of the panel of the pages - and what this script brings is the adapter
 *										that says where the requests of the sheet go (blocks/...), where back leads, what the panel is called, and the list, the forms and the questions
 *										that stand round it. Everything it shows is the answer of the API (see Modules\Blocks\Admin).
 *
 *										The address is #blocks (the blocks of the project), #blocks/<key> (one of them in the editor), #blocks/theme (the blocks of the theme in use) and
 *										#blocks/theme/<key> (one of them, shown and not changed). The list opens first; a click on a row opens the block.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/blocks/'+ key ), ...params );
	};

	// A text that is a map of languages as the words of the page say it: the one of the workbench, else English, else the first
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

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	const here = function() {
		return Nino.admin.router.current();
	};

	// The seam of the Builder: the editor of its panel, for a sheet of a block
	const editor = function() {
		return Nino.admin.builder.editor;
	};

	// What the panel holds beyond the sheet: the tab that is shown, the answer of the list and the control that opened a form
	const N = {
		fresh		: '',
		tab			: 'project',
		open		: null,
		button	: null,
		data		: null,
		keys		: false,
	};

	// The address of the list of a tab, and of a block in it
	const tabParts = function( tab ) {
		return tab === 'theme' ? [ 'theme' ] : ( tab === 'frames' ? [ 'frames' ] : [] );
	};

	const partsOf = function( tab, key ) {
		return tabParts( tab ).concat( [ key ] );
	};

	// What the address names: the tab and the key of the block, '' for the list
	const targetOf = function( parts ) {

		const tab = parts[0] === 'theme' ? 'theme' : ( parts[0] === 'frames' ? 'frames' : 'project' );
		const rest = tab === 'project' ? parts : parts.slice( 1 );

		if( rest.length === 0 )
			return { tab : tab, key : '' };

		// A frame is the header or the footer; a block is any key of the form of a key
		return rest.length === 1 && ( tab === 'frames' ? /^(?:header|footer)$/ : /^[a-z][a-z0-9-]{0,63}$/ ).test( rest[0] ) === true ? { tab : tab, key : rest[0] } : null;
	};

	const sheetOpen = function() {

		const model = editor().state();

		return model.sheet === 'block' && model.adapter.panel === 'blocks' && model.ready === true;
	};

	const fail = function( status, response, key ) {

		const list = dc.getElementById('blocks-list');
		const box = Nino.adminUi.notice( Nino.adminUi.api.errorText( status, response, key ) );
		const old = list.querySelector('.builder-list-error');

		box.classList.add('builder-list-error');
		box.setAttribute( 'role', 'alert' );

		if( old !== null )
			old.remove();

		list.insertBefore( box, list.firstChild );
	};

	const clearFail = function() {

		const box = dc.getElementById('blocks-list').querySelector('.builder-list-error');

		if( box !== null )
			box.remove();
	};

	// --- the list ----------------------------------------------------------------------------------------------------------

	// The adapter of the tiles of the list, which stand outside of any sheet: the request goes to the panel, the picture is asked at the address of the site
	const tiles = function() {

		return {
			panel	: 'blocks',
			dir		: N.data.dir,
			call	: function( action, payload, done, extra ) { Nino.admin.blocks.call( action, payload, done, extra ) },
			base	: function() { return { locale : N.data.contentLocale } },
		};
	};

	const categoryWord = function( category ) {
		return word( 'cat/'+ category );
	};

	const tileCell = function( value, row ) {

		const box = el( 'span', 'builder-tile-thumb blocks-tile' );

		box.dataset.block = row.key;
		box.setAttribute( 'aria-hidden', 'true' );
		editor().tile( box, tiles() );

		return box;
	};

	const nameCell = function( value, row ) {

		const cell = el( 'span', 'builder-cell-name' );

		cell.appendChild( el( 'strong', '', value ) );
		cell.appendChild( el( 'span', 'builder-cell-file', row.key ) );

		return cell;
	};

	// Which buttons a row has: a block of the project is duplicated, renamed and taken away; one of the theme is copied as a block of the project and nothing else
	const rowButtons = function( row ) {

		if( N.tab === 'theme' )
			return [ Nino.adminUi.iconButton( 'copy', word( 'row/copy', row.name ), function( event ) { askName( 'duplicate', row, event.currentTarget ) }, { small : true } ) ];

		const copy = Nino.adminUi.iconButton( 'copy', word( 'row/duplicate', row.name ), function( event ) { askName( 'duplicate', row, event.currentTarget ) }, { small : true } );
		const rename = Nino.adminUi.iconButton( 'pencil', word( 'row/rename', row.name ), function( event ) { askName( 'rename', row, event.currentTarget ) }, { small : true } );
		const drop = Nino.adminUi.iconButton( 'trash-2', word( 'row/delete', row.name ), function( event ) { askDelete( row, event.currentTarget ) }, { small : true, danger : true } );

		return [ copy, rename, drop ];
	};

	const render = function( data ) {

		if( editor().state().sheet === 'block' )
			editor().close();

		N.data = data;
		N.tab = data.tab;

		const mount = dc.getElementById('blocks-list');
		const holder = el( 'div', '' );

		mount.innerHTML = '';

		// The sources of the blocks: the project, and the theme in use where there is one
		const items = [ { key : 'project', label : word('tab/project') } ];

		if( data.theme !== null )
			items.push( { key : 'theme', label : word( 'tab/theme', localized( data.theme.name ) ) } );

		// The frames of the site - its header and its footer - are the third: they are written here and in no other panel
		items.push( { key : 'frames', label : word('tab/frames') } );

		const strip = Nino.adminUi.tabs( items, { active : data.tab, label : word('tab/label'), id : 'blocks-tabs', onSelect : function( key ) {

			if( key !== N.tab )
				Nino.admin.blocks.go( tabParts( key ) );
		} } );

		// The tabs are the head of the pane's: beside its name, where the workbench keeps the tabs of a panel - and where there is no head, over the list
		const head = Nino.adminUi.panelHead( mount );

		if( head !== null )
			head.tabs( strip.el );
		else
			mount.appendChild( strip.el );

		mount.appendChild( holder );

		if( data.tab === 'frames' ) {

			holder.appendChild( Nino.adminUi.list( data.frames.map( function( frame ) {

				return {
					key		: frame.key,
					title	: word( 'frames/'+ frame.key ),
					detail	: word('frames/hint'),
					href	: Nino.admin.router.href( 'blocks', partsOf( 'frames', frame.key ) ),
					onOpen	: function() { Nino.admin.blocks.go( partsOf( 'frames', frame.key ) ) },
				};
			} ), { title : word('tab/frames'), level : 2 } ) );

			return;
		}

		if( data.blocks.length === 0 )
			holder.appendChild( Nino.adminUi.emptyState( word( data.tab === 'theme' ? 'msg/none-theme' : 'msg/none-project' ) ) );
		else
			Nino.adminUi.table( {
				mount			: holder,
				rowKey		: 'key',
				columns		: [
					{ key : 'tile', label : word('list/preview'), type : 'string', render : tileCell, sortable : false },
					{ key : 'name', label : word('list/name'), type : 'string', render : nameCell },
					{ key : 'category', label : word('list/category'), type : 'string', render : function( value ) { return categoryWord( value ) } },
					{ key : 'variants', label : word('list/variants'), type : 'integer' },
				],
				rows			: data.blocks.map( function( block ) { return { key : block.key, tile : block.key, name : localized( block.name ), category : block.group, variants : block.variants } } ),
				actions		: rowButtons,
				labels		: { search : word('list/search'), empty : word('msg/none-project'), noMatch : word('list/no-match') },
				search		: true,
				searchText	: function( row ) { return row.key },
				onRowClick	: function( row ) { Nino.admin.blocks.go( partsOf( N.tab, row.key ) ) },
			} );

		N.button = Nino.adminUi.button( word('new/button'), function() { askNew( N.button ) }, { kind : 'primary', id : 'blocks-new' } );
		mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : N.button } ).el );
	};

	// --- the forms and the questions ----------------------------------------------------------------------------------------

	// What a request that was refused says: at the field the server names, else at the foot of the form
	const refused = function( ctl, status, response, fallback ) {

		const text = Nino.adminUi.api.errorText( status, response, fallback );
		const field = ( response ?? {} ).field;

		ctl.fail( [ 'name', 'category', 'from' ].indexOf( field ) !== -1 ? field : null, text );
	};

	// The name of a block in each language of the site, in a dialog: the working language is the one that is required - the others stand folded below it and a language
	// left empty has the name of the working language - and the dialog begins with a name, in each language
	const nameField = function( values ) {

		const work = Nino.admin.workLocale.current;

		return { key : 'name', type : 'locales', label : word('new/name'), locales : N.data.locales.map( function( entry ) { return entry.locale } ), work : work, values : values, required : false, maxLength : 80,
			validate : function( value ) { return String( ( value ?? {} )[work] ?? '' ).trim() === '' ? word('new/name-missing') : '' } };
	};

	// The languages other than the working one of the name field are folded away under their own line: one more press opens them
	const tuck = function() {

		// The dialog that was just opened is the last in the document, and its field the last list of languages
		const list = Array.from( dc.querySelectorAll('.nino-admin-localerows-list') ).pop() ?? null;
		const rows = list === null ? [] : Array.from( list.querySelectorAll('.nino-admin-localerow') ).filter( function( row ) { return row.classList.contains('is-work') === false } );

		if( rows.length === 0 )
			return;

		const inner = el( 'div', '' );

		inner.appendChild( el( 'p', '', word('new/more-note') ) );
		rows.forEach( function( row ) { inner.appendChild( row ) } );

		// An accordion of the workbench, closed: the rows are in it, hidden, and still the controls of the field
		list.appendChild( Nino.adminUi.accordion( { id : 'blocks-more', sections : [ { key : 'more', label : word('new/more-languages'), content : inner } ], collapsible : true, level : 4 } ).el );
	};

	// What a name in a language is, of a map or a text: the language's, else English, else the first
	const nameIn = function( names, code ) {

		if( typeof names === 'string' )
			return names;

		const map = names ?? {};
		const first = Object.keys( map )[0];

		return String( map[code] ?? map.en_US ?? ( first === undefined ? '' : map[first] ) );
	};

	// The names a form begins with: the name of the block in each language of the site, and behind it - for a copy - the word of a copy in that language
	const copyNames = function( names, copy ) {

		const out = {};

		N.data.locales.forEach( function( entry ) { out[entry.locale] = nameIn( names, entry.locale )+ ( copy === true ? N.data.copy[entry.locale] ?? '' : '' ) } );

		return out;
	};

	// A new block: a name in each language, the category, and what it begins with - nothing, or the copy of a block of the library
	const askNew = function( opener ) {

		clearFail();

		const copies = [ { value : '', label : word('new/empty') } ].concat( N.data.library.map( function( entry ) {
			return { value : entry.key, label : word( 'new/copy-of', localized( entry.name ) ) + ' · ' + ( entry.own === true ? word('tab/project') : localized( entry.origin ) ) };
		} ) );

		Nino.adminUi.formDialog( {
			title		: word('new/title'),
			message	: word('new/message'),
			fields	: [
				nameField( { [ Nino.admin.workLocale.current ] : N.data.fresh[Nino.admin.workLocale.current] ?? '' } ),
				{ key : 'category', type : 'select', label : word('new/category'), options : N.data.categories.map( function( category ) { return { value : category, label : categoryWord( category ) } } ), value : 'content', required : true },
				{ key : 'from', type : 'select', label : word('new/from'), options : copies, value : '', help : word('new/from-help') },
			],
			submit	: word('new/submit'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				Nino.admin.blocks.call( 'create', { name : values.name, category : values.category, from : values.from, locale : Nino.admin.workLocale.current }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/create' );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( 'msg/created', nameIn( values.name, Nino.admin.workLocale.current ) ) );

					// A block that is new has nothing in it: the editor opens with its column chosen, so that the plus is at hand
					N.fresh = response.key;
					Nino.admin.blocks.go( partsOf( 'project', response.key ) );
				} );
			},
		} );

		tuck();
	};

	// A name for a block that is copied or renamed: the dialog asks for it in each language and says what it made
	const askName = function( action, row, opener ) {

		clearFail();

		const own = N.tab === 'project';
		const was = ( N.data.blocks.find( function( block ) { return block.key === row.key } ) ?? {} ).name ?? {};
		const values = copyNames( was, action !== 'rename' );

		Nino.adminUi.formDialog( {
			title		: word( action === 'rename' ? 'rename/title' : ( own === true ? 'dup/title' : 'dup/title-theme' ), row.name ),
			fields	: [ nameField( values ) ],
			submit	: word( action === 'rename' ? 'rename/submit' : ( own === true ? 'dup/submit' : 'dup/submit-theme' ) ),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( given, ctl ) {

				Nino.admin.blocks.call( action, { key : row.key, name : given.name, locale : Nino.admin.workLocale.current }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/'+ action );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( action === 'rename' ? 'msg/renamed' : 'msg/duplicated', nameIn( given.name, Nino.admin.workLocale.current ) ) );

					// A copy of a block of the theme is a block of the project: the list goes to the project
					if( action === 'duplicate' && own === false )
						Nino.admin.blocks.go( [] );
					else
						Nino.admin.blocks.reload();
				} );
			},
		} );

		tuck();
	};

	const askDelete = function( row, opener ) {

		clearFail();

		Nino.adminUi.confirm( 'destructive', {
			title		: word('delete/title'),
			message	: word( 'delete/message', row.name ),
			yes			: word('delete/title'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.blocks.call( 'remove', { key : row.key }, function( status, response ) {

					if( status !== 200 ) {
						fail( status, response, '/_admin/blocks/error/remove' );
						return;
					}

					Nino.adminUi.say( word( 'msg/removed', row.name ) );
					Nino.admin.blocks.reload();
				} );
			},
		} );
	};

	// --- the editor: the sheet of a block ------------------------------------------------------------------------------------

	// Save as: the sheet as it stands - what was changed and not saved with it - is a new block of the project under a name of its own; the editor is on the new one afterwards
	const askSaveAs = function( adapter ) {

		const model = Nino.admin.builder.model;
		const sheet = editor().state();

		const own = adapter.readOnly !== true;
		const field = nameField( copyNames( N.open === null ? {} : N.open.names, true ) );

		// In a block of one's own the field says what is made of what behind its (?) - in a block of the theme it is "save to own blocks" and speaks for itself
		if( own === true )
			field.help = word('saveas/help');

		Nino.adminUi.formDialog( {
			title		: word( own === true ? 'saveas/title' : 'saveas/title-theme' ),
			fields	: [ field ],
			submit	: word( own === true ? 'saveas/submit' : 'saveas/submit-theme' ),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: sheet.view.bar.buttons.saveas,
			onSubmit	: function( values, ctl ) {

				editor().call( 'saveas', { name : values.name, tree : sheet.top, texts : sheet.texts, pictures : model.picturesNow() }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/saveas' );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( 'msg/saved-as', nameIn( values.name, Nino.admin.workLocale.current ) ) );

					// The sheet that was worked on is given up - what it held is in the new block - and the new block is opened
					editor().close();
					Nino.admin.router.go( 'blocks', partsOf( 'project', response.key ) );
					Nino.admin.blocks.follow();
				} );
			},
		} );

		tuck();
	};

	const adapterOf = function( key, theme, response ) {

		const title = localized( response.name );

		const adapter = {
			panel		: 'blocks',
			level		: 'blocks-sheet',
			template	: response.template,
			name		: title,
			slug		: '',
			dir			: response.dir,
			restore	: false,
			readOnly	: theme === true,
			// A block of the theme is only looked at: a quiet badge beside the state of the bar says so, with where it is changed as its reason
			badge		: theme === true ? { text : word('readonly/badge'), reason : word('readonly/reason') } : undefined,
			sources	: 'block',
			uploads	: false,
			call		: function( action, payload, done, extra ) { Nino.admin.blocks.call( action, payload, done, extra ) },
			code		: key,
			// The key and the language every request of the sheet names, and the sentence the preview puts in a column that has nothing in it
			base		: function() { return { key : key, locale : editor().state().locale, hint : word('preview/place') } },
			go			: function( parts, options ) { Nino.admin.blocks.go( parts, options ) },
			href		: function( parts ) { return Nino.admin.router.href( 'blocks', parts ) },
			set			: function() { Nino.admin.router.set( 'blocks', partsOf( theme === true ? 'theme' : 'project', key ) ) },
			show		: function() { Nino.adminUi.showLevel( 'blocks-sheet', { focus : false } ) },
			fail		: function( level, status, answer ) { Nino.admin.blocks.fail( level, status, answer ) },
			back		: function() { return { parts : tabParts( theme === true ? 'theme' : 'project' ), name : word('title'), keep : false, block : false } },
			reload	: function() { openBlock( key, theme ) },
			buttons	: [ { key : 'saveas', label : word( theme === true ? 'saveas/button-theme' : 'saveas/button' ), name : word( theme === true ? 'saveas/name-theme' : 'saveas/name', title ), onPress : function() { askSaveAs( adapter ) } } ],
		};

		return adapter;
	};

	// The block that takes the place of a frame: the frame is the block of its kind that stands in it, and a block that the project offers for it replaces it, after the question
	const askFrameBlock = function( adapter ) {

		const sheet = editor().state();

		editor().call( 'blocks', {}, function( status, response ) {

			if( status !== 200 ) {
				sheet.view.bar.line.error( status, response );
				return;
			}

			const items = response.blocks.map( function( entry ) { return { value : entry.key, label : localized( entry.name ), description : localized( entry.description ) } } );

			if( items.length === 0 ) {
				sheet.view.bar.line.idle( word('frames/no-blocks') );
				return;
			}

			Nino.adminUi.pickDialog( {
				title		: word('frames/insert'),
				message	: word('frames/insert-note'),
				items		: items,
				cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
				opener	: sheet.view.bar.buttons.frameblock,
				onChoose	: function( key ) {

					const entry = response.blocks.find( function( candidate ) { return candidate.key === key } );

					// The whole frame goes: it is asked, and Undo brings it back
					Nino.adminUi.confirm( 'destructive', {
						title		: word('frames/confirm-title'),
						message	: word( 'frames/confirm-message', adapter.name, localized( entry.name ) ),
						yes			: word('frames/confirm-yes'),
						no			: Nino.adminUi.text('/_admin/common/label/cancel'),
						kind		: 'danger',
						opener	: sheet.view.bar.buttons.frameblock,
						onChoose : function( ok ) {

							if( ok === false )
								return;

							editor().call( 'block', { key : key }, function( code, block ) {

								if( code !== 200 )
									sheet.view.bar.line.error( code, block );
								else if( editor().replace( block, entry.name ) === false )
									sheet.view.bar.line.fail( word('frames/insert-fail') );
							} );
						},
					} );
				},
			} );
		} );
	};

	// The sheet of a frame: the requests are the code of the Builder for it, passed on after the right of this panel; the template is the one every request names
	const frameAdapterOf = function( frame, response ) {

		const title = word( 'frames/'+ frame );
		const template = 'frame-'+ frame;

		const adapter = {
			panel		: 'blocks',
			level		: 'blocks-sheet',
			frame		: true,
			template	: template,
			name		: title,
			slug		: '',
			dir			: response.dir,
			restore	: true,
			sources	: 'block',
			call		: function( action, payload, done, extra ) { Nino.admin.blocks.call( 'frame-'+ action, payload, done, extra ) },
			code		: template,
			base		: function() { return { template : template, locale : editor().state().locale, hint : word('preview/place') } },
			go			: function( parts, options ) { Nino.admin.blocks.go( parts, options ) },
			href		: function( parts ) { return Nino.admin.router.href( 'blocks', parts ) },
			set			: function() { Nino.admin.router.set( 'blocks', partsOf( 'frames', frame ) ) },
			show		: function() { Nino.adminUi.showLevel( 'blocks-sheet', { focus : false } ) },
			fail		: function( level, status, answer ) { Nino.admin.blocks.fail( level, status, answer ) },
			back		: function() { return { parts : tabParts( 'frames' ), name : word('title'), keep : false, block : false } },
			reload	: function() { openFrame( frame ) },
			buttons	: [ { key : 'frameblock', label : word('frames/insert'), name : word( 'frames/insert-of', title ), onPress : function() { askFrameBlock( adapter ) } } ],
		};

		return adapter;
	};

	const openFrame = function( frame ) {

		const level = dc.getElementById('blocks-sheet');

		clearFail();

		Nino.admin.blocks.call( 'frame-open', { template : 'frame-'+ frame, locale : Nino.admin.workLocale.current }, function( status, response ) {

			if( status !== 200 ) {
				Nino.admin.blocks.fail( level, status, response );
				Nino.adminUi.showLevel( 'blocks-sheet', { focus : false } );
				return;
			}

			if( response.contentLocale !== Nino.admin.workLocale.current ) {
				openFrame( frame );
				return;
			}

			if( editor().open( frameAdapterOf( frame, response ), response ) === false ) {
				Nino.admin.blocks.go( tabParts( 'frames' ) );
				return;
			}

			N.open = { tab : 'frames', key : frame, names : word( 'frames/'+ frame ) };

			unloadList();
		} );
	};

	// The place of the first column of the block in its tree, as the tree names a place: the index of each level, joined by a point; '' where it has none
	const columnOf = function() {

		const model = Nino.admin.builder.model;
		const root = editor().state().top[model.rootAt()];

		const walk = function( nodes, rel ) {

			for( let at = 0; at < nodes.length; at++ ) {

				const node = nodes[at];
				const here = rel === '' ? String( at ) : rel+ '.'+ at;

				if( node.n === 'section-col' )
					return here;

				const inner = Array.isArray( node.c ) === true ? walk( node.c, here ) : '';

				if( inner !== '' )
					return inner;
			}

			return '';
		};

		return root === undefined || Array.isArray( root.c ) === false ? '' : walk( root.c, '' );
	};

	// The list is left for the editor: its previews - a frame of the site each - are taken out of the page, and the list is drawn again where it is come back to
	const unloadList = function() {

		const list = dc.getElementById('blocks-list');

		list.innerHTML = '';
		N.button = null;
	};

	const openBlock = function( key, theme ) {

		const level = dc.getElementById('blocks-sheet');

		clearFail();

		Nino.admin.blocks.call( 'open', { key : key, locale : Nino.admin.workLocale.current }, function( status, response ) {

			if( status !== 200 ) {
				Nino.admin.blocks.fail( level, status, response );
				Nino.adminUi.showLevel( 'blocks-sheet', { focus : false } );
				return;
			}

			// The language moved while the block was on its way: it is asked for again in the language of the workbench
			if( response.contentLocale !== Nino.admin.workLocale.current ) {
				openBlock( key, theme );
				return;
			}

			if( editor().open( adapterOf( key, theme, response ), response ) === false ) {
				Nino.admin.blocks.go( tabParts( theme === true ? 'theme' : 'project' ) );
				return;
			}

			N.open = { tab : theme === true ? 'theme' : 'project', key : key, names : response.name };

			unloadList();

			// The block that was just made has a section, a row and a column and nothing in them: the column is the chosen entry, which has the plus
			if( N.fresh === key ) {

				N.fresh = '';

				const column = columnOf();

				if( column !== '' )
					editor().select( column );
			}
		} );
	};

	// --- the panel -------------------------------------------------------------------------------------------------------------

	Nino.admin.blocks = Nino.adminUi.panel( {
		uri		: 'blocks',
		mount	: 'blocks-list',
		render	: render,
		payload	: function() {

			const at = here();
			const target = at.panel === 'blocks' ? targetOf( at.parts ) : null;

			return { locale : Nino.admin.workLocale.current, tab : target === null ? N.tab : target.tab };
		},
		show	: 'always',
		onError	: function() { Nino.adminUi.showLevel( 'blocks-list', { focus : false } ) },
	} );

	Object.assign( Nino.admin.blocks, {

		go : function( parts, options ) {

			Nino.admin.dirty.guard( sheetOpen() === true ? [ 'blocks' ] : [], function() {
				Nino.admin.router.go( 'blocks', parts );
				Nino.admin.blocks.follow();
			} );

			return options;
		},

		follow : function() {

			clearFail();

			const at = here();
			const target = targetOf( at.panel === 'blocks' ? at.parts : [] );

			if( target === null ) {
				Nino.admin.blocks.go( [] );
				return;
			}

			if( target.key === '' ) {

				editor().close();
				N.open = null;
				Nino.admin.router.set( 'blocks', tabParts( target.tab ) );

				// The list of the tab that the address names: asked again where it is another tab or the blocks changed
				N.tab = target.tab;
				Nino.admin.blocks.reload( function() { Nino.adminUi.showLevel( 'blocks-list', { focus : N.button !== null && N.button.isConnected === true ? N.button : Nino.adminUi.panelName( dc.getElementById('blocks-list') ) } ) } );

				return;
			}

			if( target.tab === 'frames' )
				openFrame( target.key );
			else
				openBlock( target.key, target.tab === 'theme' );
		},

		showCurrent : function() {

			const at = here();

			if( Nino.admin.blocks.ready === true && at.panel !== 'blocks' )
				return;

			const proceed = function() { Nino.admin.blocks.follow() };

			if( Nino.admin.blocks.ready === false ) {
				Nino.admin.blocks.init( proceed );
				return;
			}

			const there = targetOf( at.parts );
			const same = sheetOpen() === true && there !== null && N.open !== null && there.tab === N.open.tab && there.key === N.open.key;

			Nino.admin.router.leave( [ 'blocks' ], sheetOpen() === true && same === false, proceed, function() {
				Nino.admin.router.set( 'blocks', N.open === null ? [] : partsOf( N.open.tab, N.open.key ) );
			} );
		},
	} );

	// The choice of a language in the workbench asks the sheet about its unsaved work by the name of the panel; what it answers is loaded anew in that language
	Nino.admin.workLocale.follow( 'blocks', function() {

		// Another panel is the one that is shown: this one asks again when it is shown
		if( here().panel !== 'blocks' )
			return;

		Nino.admin.dirty.guard( [ 'blocks' ], function() {
			Nino.admin.blocks.reload( function() { Nino.admin.blocks.follow() } );
		}, function() {
			Nino.admin.workLocale.set( editor().state().locale );
		} );
	} );

	// The keys of the workbench over a sheet that is open: the undo and the redo, where no field takes the keys for itself
	if( N.keys === false ) {

		N.keys = true;

		dc.addEventListener( 'keydown', function( event ) {

			if( sheetOpen() === false || here().panel !== 'blocks' || event.ctrlKey === false && event.metaKey === false )
				return;

			const held = dc.activeElement;

			if( held !== null && held !== dc.body && ( held.isContentEditable === true || /^(INPUT|TEXTAREA|SELECT)$/.test( held.tagName ) === true ) )
				return;

			const key = String( event.key ).toLowerCase();

			if( key === 'z' || key === 'y' ) {
				event.preventDefault();
				key === 'y' || event.shiftKey === true ? editor().redo() : editor().undo();
			}
		} );
	}

} )(window,document);
