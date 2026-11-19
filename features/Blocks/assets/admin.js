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

	// The number of variants has their names as its tooltip
	const variantCell = function( value, row ) {

		const cell = el( 'span', '', String( value ) );

		if( row.names !== undefined && row.names !== '' )
			cell.title = row.names;

		return cell;
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
		const rename = Nino.adminUi.iconButton( 'pencil', word( 'row/details', row.name ), function( event ) { askListDetails( row, event.currentTarget ) }, { small : true } );
		const drop = Nino.adminUi.iconButton( 'trash-2', word( 'row/delete', row.name ), function( event ) { askDelete( row, event.currentTarget ) }, { small : true, danger : true } );

		return [ copy, rename, drop ];
	};

	// --- the export and the import of own blocks ----------------------------------------------------------------------------

	// The key a package has from its name: lower case letters, digits and hyphens, beginning with a letter
	const packKeyOf = function( text ) {

		const key = String( text ).normalize('NFKD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /ß/g, 'ss' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^[^a-z]+/, '' ).slice( 0, 32 ).replace( /-+$/, '' );

		return key === '' ? 'blocks' : key;
	};

	// The file is asked of the server by a form that posts into a frame nobody sees: a file that is saved does not load, so what loads there is the refusal
	const download = function( body ) {

		let frame = dc.getElementById('blocks-download');

		if( frame === null ) {
			frame = dc.createElement('iframe');
			frame.id = 'blocks-download';
			frame.name = 'blocks-download';
			frame.hidden = true;
			frame.setAttribute( 'title', word('export/button') );
			frame.setAttribute( 'tabindex', '-1' );
			dc.body.appendChild( frame );
		}

		const fields = { action : 'blocks/export', data : JSON.stringify( body ) };
		const csrf = dc.querySelector( 'input[name="_csrf"]' );

		if( csrf !== null )
			fields._csrf = csrf.value;

		const form = dc.createElement('form');

		form.method = 'post';
		// Where every panel posts: the directory of the project and the workbench's own address, with its slash
		form.action = ( typeof Nino.dir === 'string' ? Nino.dir : '' )+ '/_admin'+ '/';
		form.target = 'blocks-download';
		form.hidden = true;

		Object.keys( fields ).forEach( function( name ) {

			const input = dc.createElement('input');

			input.type = 'hidden';
			input.name = name;
			input.value = fields[name];
			form.appendChild( input );
		} );

		dc.body.appendChild( form );
		form.submit();
		dc.body.removeChild( form );
	};

	// The package of the blocks that are chosen in the list: one package, whatever their number
	const askExport = function( keys, opener ) {

		clearFail();

		if( keys.length === 0 )
			return;

		const work = Nino.admin.workLocale.current;
		const one = keys.length === 1 ? ( N.data.blocks.find( function( block ) { return block.key === keys[0] } ) ?? null ) : null;
		const name = one !== null ? nameIn( one.name, work ) : String( ( N.data.package.name ?? {} )[work] ?? '' ).replace( '%s', N.data.site );

		Nino.adminUi.formDialog( {
			title		: word('export/title'),
			message	: word('export/note'),
			fields	: [
				{ key : 'name', type : 'text', label : word('export/name'), value : name, required : true, maxLength : 80 },
				{ key : 'key', type : 'text', label : word('export/key'), value : packKeyOf( name ), required : true, maxLength : 32, help : word('export/key-help') },
				{ key : 'version', type : 'text', label : word('export/version'), value : N.data.package.version, required : true, maxLength : 14 },
				{ key : 'description', type : 'text', label : word('export/description'), value : '', maxLength : 240 },
			],
			submit	: word('export/submit'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				const body = { keys : keys, key : String( values.key ).trim(), version : String( values.version ).trim(), name : String( values.name ).trim(), description : String( values.description ?? '' ).trim(), locale : work };

				// Asked first, so that a refusal is said here at its field; the file that follows is the one that was asked about
				Nino.admin.blocks.call( 'export', Object.assign( { check : true }, body ), function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/export' );
						return;
					}

					ctl.close();
					download( body );
					Nino.adminUi.say( word( 'export/done', response.file ) );
				} );
			},
		} );
	};

	// What a package holds, one line for each block: the box before its name, and quietly what it is; where its key is taken, the choice of what is done with it under it
	const importRows = function( preview, picks ) {

		const holder = el( 'div', 'blocks-import' );

		holder.appendChild( el( 'p', 'nino-admin-hint', word( 'import/pack', preview.pack.name, preview.pack.version ) ) );

		preview.blocks.forEach( function( block ) {

			const row = el( 'div', 'blocks-import-row' );
			const pick = { take : true, how : 'skip' };
			const line = el( 'div', 'blocks-import-line' );
			const tick = Nino.adminUi.checkField( { key : 'take:'+ block.key, label : block.name, checked : true, className : 'nino-admin-field blocks-import-take', onChange : function( on ) { pick.take = on } } );
			const facts = el( 'small', 'nino-admin-hint blocks-import-facts', [ categoryWord( block.category ), word( 'import/col-variants' )+ ': '+ block.variants.length, word( 'import/col-pictures' )+ ': '+ block.images ].join( ' · ' ) );
			const names = block.variants.filter( function( label ) { return label !== '' } ).join( ', ' );

			if( names !== '' )
				facts.title = names;

			picks[block.key] = pick;
			line.appendChild( tick );
			line.appendChild( facts );
			row.appendChild( line );

			if( block.state === 'taken' ) {

				const options = [ { value : 'skip', label : word('import/how-skip') }, { value : 'copy', label : word('import/how-copy') } ];

				if( block.replace === true )
					options.push( { value : 'replace', label : word('import/how-replace') } );

				const note = el( 'p', 'nino-admin-hint blocks-import-warn', word('import/replace-note') );
				const choice = Nino.adminUi.selectField( { key : 'how:'+ block.key, label : word('import/exists'), options : options, value : 'skip', className : 'nino-admin-field blocks-import-how', onChange : function( value ) {

					pick.how = value;
					note.hidden = value !== 'replace';
					choice.classList.toggle( 'is-replace', value === 'replace' );
				} } );

				note.hidden = true;
				row.appendChild( choice );
				row.appendChild( note );
			}

			holder.appendChild( row );
		} );

		return holder;
	};

	// The result of an import: a line for each block, and where one was missing, which and why
	const askImportResult = function( results, opener ) {

		const holder = el( 'div', 'blocks-import' );
		const list = el( 'ul', 'blocks-import-results' );
		const failed = results.find( function( result ) { return result.result === 'failed' } );

		results.forEach( function( result ) {

			const line = el( 'li', result.result === 'failed' ? 'is-failed' : '', word( 'import/result-'+ result.result, result.name, result.result === 'failed' ? ( result.message ?? '' ) : ( result.as ?? '' ) ) );

			list.appendChild( line );
		} );

		if( failed !== undefined )
			holder.appendChild( el( 'p', 'nino-admin-error', word( 'import/stopped', failed.name ) ) );

		holder.appendChild( list );

		Nino.adminUi.choiceDialog( {
			title		: word('import/done-title'),
			content	: holder,
			opener	: opener,
			choices	: [ { value : 'close', label : word('import/close'), kind : 'primary' } ],
			onChoose	: function() { Nino.admin.blocks.go( [] ) },
		} );
	};

	const askImportPreview = function( preview, opener ) {

		const picks = {};

		Nino.adminUi.choiceDialog( {
			title		: word('import/title'),
			content	: importRows( preview, picks ),
			size		: 'form',
			opener	: opener,
			choices	: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' }, { value : 'import', label : word('import/submit'), kind : 'primary' } ],
			guard		: function( value ) {

				if( value !== 'import' || Object.keys( picks ).some( function( key ) { return picks[key].take === true } ) === true )
					return true;

				Nino.adminUi.say( word('import/none'), { kind : 'info' } );

				return false;
			},
			onChoose	: function( value ) {

				// A preview that is left lets go of the bytes that are held for it
				if( value !== 'import' ) {
					Nino.admin.blocks.call( 'import-drop', { id : preview.id, locale : Nino.admin.workLocale.current }, function() {} );
					return;
				}

				Nino.admin.blocks.call( 'import', { id : preview.id, items : picks, locale : Nino.admin.workLocale.current }, function( status, response ) {

					if( status !== 200 ) {
						fail( status, response, '/_admin/blocks/error/import' );
						return;
					}

					askImportResult( response.results, opener );
				} );
			},
		} );
	};

	const askImport = function( opener ) {

		clearFail();

		const holder = el( 'div', 'blocks-import' );
		const file = Nino.adminUi.fileField( { key : 'file', label : word('import/file'), accept : '.gz,.tgz,application/gzip', maxBytes : N.data.package.bytes, note : word( 'import/limits', Math.round( N.data.package.bytes / 1024 )+ ' KiB' ), button : word('import/file-button') } );
		let preview = null;

		holder.appendChild( el( 'p', '', word('import/note') ) );
		holder.appendChild( file.el );

		Nino.adminUi.choiceDialog( {
			title		: word('import/title'),
			content	: holder,
			size		: 'form',
			focus		: file.button,
			opener	: opener,
			choices	: [ { value : 'cancel', label : Nino.adminUi.text('/_admin/common/label/cancel'), kind : 'secondary' }, { value : 'check', label : word('import/check'), kind : 'primary' } ],
			guard		: function( value, handle ) {

				if( value !== 'check' )
					return true;

				if( preview !== null )
					return true;

				const chosen = file.value();

				if( chosen === null ) {
					file.error( word('import/no-file') );
					return false;
				}

				handle.buttons.check.disabled = true;
				file.clearError();

				Nino.admin.blocks.call( 'import-check', { locale : Nino.admin.workLocale.current }, function( status, response ) {

					handle.buttons.check.disabled = false;

					if( status !== 200 ) {
						file.error( Nino.adminUi.api.errorText( status, response, '/_admin/blocks/error/import' ) );
						return;
					}

					preview = response;
					handle.close( 'check' );
				}, { file : chosen } );

				return false;
			},
			onChoose	: function( value ) {

				if( value === 'check' && preview !== null )
					askImportPreview( preview, opener );
			},
		} );
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
					{ key : 'variants', label : word('list/variants'), type : 'integer', render : variantCell },
				],
				rows			: data.blocks.map( function( block ) { return { key : block.key, pick : block.key, tile : block.key, name : localized( block.name ), category : block.group, variants : block.variants, names : ( block.variantNames ?? [] ).map( localized ).join( ', ' ) } } ),
				actions		: rowButtons,
				// The own blocks are chosen in the list and handed out or taken away together; the theme's blocks are only looked at and copied
				select		: N.tab === 'project' ? { label : word('list/pick-head'), rowLabel : function( row ) { return word( 'list/pick', row.name ) }, actions : [
					{ key : 'export', label : word('export/button'), run : function( keys ) { askExport( keys, dc.activeElement ) } },
					{ key : 'delete', label : word('delete/many'), danger : true, run : function( keys ) { askDeleteMany( keys, dc.activeElement ) } },
				] } : undefined,
				labels		: { search : word('list/search'), empty : word('msg/none-project'), noMatch : word('list/no-match') },
				search		: true,
				searchText	: function( row ) { return row.key },
				onRowClick	: function( row ) { Nino.admin.blocks.go( partsOf( N.tab, row.key ) ) },
			} );

		N.button = Nino.adminUi.button( word('new/button'), function() { askNew( N.button ) }, { kind : 'primary', id : 'blocks-new' } );

		// The own blocks are taken in beside the button of a new block: a package comes in
		const beside = [];

		if( N.tab === 'project' )
			beside.push( Nino.adminUi.button( word('import/button'), function( event ) { askImport( event.currentTarget ) }, { kind : 'secondary', id : 'blocks-import' } ) );

		mount.appendChild( Nino.adminUi.actionBar( { list : true, secondary : beside, primary : N.button } ).el );
	};

	// --- the forms and the questions ----------------------------------------------------------------------------------------

	// What a request that was refused says: at the field the server names, else at the foot of the form
	const refused = function( ctl, status, response, fallback ) {

		const text = Nino.adminUi.api.errorText( status, response, fallback );
		const field = ( response ?? {} ).field;

		ctl.fail( [ 'name', 'category', 'from', 'key', 'version', 'description', 'file' ].indexOf( field ) !== -1 ? field : null, text );
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

	// The details of a block of the project from the list: name, description, category and rank - the places of the pictures are managed where the block is open
	const askListDetails = function( row, opener ) {

		clearFail();

		const block = N.data.blocks.find( function( entry ) { return entry.key === row.key } ) ?? {};
		const locales = N.data.locales.map( function( entry ) { return entry.locale } );
		const work = Nino.admin.workLocale.current;

		Nino.adminUi.formDialog( {
			title		: word( 'details/title', row.name ),
			fields	: [
				{ key : 'name', type : 'locales', label : word('details/name'), locales : locales, work : work, values : copyNames( block.name ?? {}, false ), required : false, maxLength : 80,
					validate : function( value ) { return String( ( value ?? {} )[work] ?? '' ).trim() === '' ? word('new/name-missing') : '' } },
				{ key : 'description', type : 'locales', label : word('details/description'), locales : locales, work : work, values : block.description ?? {}, required : false, maxLength : 240, multiline : true, rows : 3 },
				{ key : 'category', type : 'select', label : word('details/category'), help : word('details/category-help'), options : CATEGORIES.map( function( one ) { return { value : one, label : word( 'cat/'+ one ) } } ), value : CATEGORIES.indexOf( block.category ) === -1 ? 'content' : block.category },
				{ key : 'rank', type : 'number', label : word('details/rank'), help : word('details/rank-help'), value : String( block.rank ?? 0 ) },
			],
			submit	: word('details/submit'),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( given, ctl ) {

				Nino.admin.blocks.call( 'rename', { key : row.key, name : given.name, description : given.description, category : given.category, rank : given.rank, locale : work }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/rename' );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( 'msg/renamed', nameIn( given.name, work ) ) );
					Nino.admin.blocks.reload();
				} );
			},
		} );

		tuck();
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
		const values = copyNames( was, true );

		Nino.adminUi.formDialog( {
			title		: word( own === true ? 'dup/title' : 'dup/title-theme', row.name ),
			fields	: [ nameField( values ) ],
			submit	: word( own === true ? 'dup/submit' : 'dup/submit-theme' ),
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( given, ctl ) {

				Nino.admin.blocks.call( action, { key : row.key, name : given.name, locale : Nino.admin.workLocale.current }, function( status, response ) {

					if( status !== 200 ) {
						refused( ctl, status, response, '/_admin/blocks/error/'+ action );
						return;
					}

					ctl.close();
					Nino.adminUi.say( word( 'msg/duplicated', nameIn( given.name, Nino.admin.workLocale.current ) ) );

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

	// Several own blocks at once, after ONE question that says how many: each is asked of the server on its own - the right, the token and the checks are those of the one block - and
	// what was refused is said with its name, the rest is taken away
	const askDeleteMany = function( keys, opener ) {

		clearFail();

		if( keys.length === 0 )
			return;

		Nino.adminUi.confirm( 'destructive', {
			title		: word( 'delete/many-title', keys.length ),
			message	: word( 'delete/many-message', keys.length ),
			yes			: word('delete/many'),
			no			: Nino.adminUi.text('/_admin/common/label/cancel'),
			kind		: 'danger',
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				const refused = [];
				let gone = 0;

				const next = function( at ) {

					if( at >= keys.length ) {

						if( refused.length > 0 )
							fail( refused[0].status, refused[0].response, '/_admin/blocks/error/remove' );
						else
							Nino.adminUi.say( word( 'msg/removed-many', gone ) );

						Nino.admin.blocks.reload();
						return;
					}

					Nino.admin.blocks.call( 'remove', { key : keys[at] }, function( status, response ) {

						if( status === 200 )
							gone++;
						else
							refused.push( { key : keys[at], status : status, response : response } );

						next( at + 1 );
					} );
				};

				next( 0 );
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

	const MAX_VARIANTS = 8;
	const CATEGORIES = [ 'intro', 'content', 'trust', 'offer', 'action', 'contact' ];

	// --- the variants and the details of a block ---------------------------------------------------------------------------------
	//
	// A variant is a set: place => attribute => value, over the template. In the editor the set of each variant is carried by the calls it is about - node.v[name] = { attribute : value } -
	// so that a call that is moved, copied or taken out takes its sets with it, and nothing is counted by place until the server is told; node.o keeps what the template has of each
	// attribute that a set names (null: not written). The attributes of the tree - a.* - are those of the variant that is edited, which is why the draft that is sent is the template and
	// the draft that is drawn is the tree as it stands

	const stateOf = function() {
		return editor().state();
	};

	const firstOf = function( x ) {
		return ( x.vars ?? [] )[0] === undefined ? '' : x.vars[0].name;
	};

	// Whether there is a choice: more than the standard
	const varied = function() {
		return ( stateOf().extra.vars ?? [] ).length > 1;
	};

	// Whether a variant other than the standard is the one that is edited: what is a look is then the variant's and not the template's
	const inVariant = function() {

		const x = stateOf().extra;

		return varied() === true && x.edited !== firstOf( x );
	};

	const rootOf = function( top ) {
		return top.findIndex( function( node ) { return node.n === 'section-wrap' } );
	};

	// Every call below a root with its place - 'root', or the indices from the root down, the blanks counted
	const calls = function( root, each, place ) {

		each( root, place ?? 'root' );

		( Array.isArray( root.c ) === true ? root.c : [] ).forEach( function( child, at ) {
			if( child.n !== undefined )
				calls( child, each, ( place ?? 'root' ) === 'root' ? String( at ) : place+ '.'+ at );
		} );
	};

	const cloned = function( node, template ) {

		const out = Object.assign( {}, node );

		if( node.a !== undefined )
			out.a = Object.assign( {}, node.a );

		// The template has the values the sets leave alone: what is written in the tree is that of the variant that is edited
		if( template === true && node.o !== undefined )
			Object.keys( node.o ).forEach( function( name ) {

				if( node.o[name] === null )
					delete out.a[name];
				else
					out.a[name] = node.o[name];
			} );

		delete out.o;
		delete out.v;

		if( Array.isArray( node.c ) === true )
			out.c = node.c.map( function( child ) { return cloned( child, template ) } );

		return out;
	};

	const sorted = function( map ) {

		const out = {};

		Object.keys( map ).sort().forEach( function( name ) { out[name] = map[name] } );

		return out;
	};

	// The sets of the variants as the server takes them, from the calls: [ { name, label, set } ]
	const setsOut = function( top, x ) {

		const root = top[rootOf( top )];
		const list = ( x.vars ?? [] ).map( function( variant ) { return { name : variant.name, label : variant.label, set : {} } } );

		if( root === undefined )
			return list;

		calls( root, function( node, place ) {

			list.forEach( function( variant, at ) {

				const set = at === 0 || node.v === undefined || node.v[variant.name] === undefined ? {} : node.v[variant.name];

				if( Object.keys( set ).length > 0 )
					variant.set[place] = sorted( set );
			} );
		} );

		return list;
	};

	// What of the draft is the draft: the tree as the template has it, the variants with their sets, the details - and nothing of which variant is looked at
	const canon = function( top, x ) {
		return { tree : top.map( function( node ) { return cloned( node, true ) } ), variants : setsOut( top, x ), more : { vars : ( x.vars ?? [] ).map( function( variant ) { return [ variant.name, variant.label ] } ), details : x.details ?? null, own : x.ownChosen ?? null } };
	};

	// The attributes of the calls are those of the variant that is edited
	const materialize = function( top, name ) {

		const x = stateOf().extra;
		const root = top[rootOf( top )];

		if( root === undefined )
			return;

		calls( root, function( node ) {

			if( node.o === undefined )
				return;

			const own = name === firstOf( x ) || node.v === undefined || node.v[name] === undefined ? {} : node.v[name];

			Object.keys( node.o ).forEach( function( attribute ) {

				const value = Object.hasOwn( own, attribute ) === true ? own[attribute] : node.o[attribute];

				node.a = node.a ?? {};

				if( value === null || value === undefined )
					delete node.a[attribute];
				else
					node.a[attribute] = value;
			} );
		} );
	};

	// The sets of the answer are put on the calls they name, and the template's value of every attribute they name is kept beside it
	const attach = function( top, list ) {

		const root = top[rootOf( top )];

		if( root === undefined )
			return;

		const by = {};

		calls( root, function( node, place ) { by[place] = node } );

		list.forEach( function( variant, at ) {

			if( at === 0 )
				return;

			Object.keys( variant.set ?? {} ).forEach( function( place ) {

				const node = by[place];

				if( node === undefined )
					return;

				node.o = node.o ?? {};
				node.v = node.v ?? {};
				node.v[variant.name] = Object.assign( {}, variant.set[place] );
				Object.keys( variant.set[place] ).forEach( function( attribute ) {
					if( Object.hasOwn( node.o, attribute ) === false )
						node.o[attribute] = ( node.a ?? {} )[attribute] ?? null;
				} );
			} );
		} );
	};

	// The tree and the words of the draft in the form the server takes: no sets on the calls
	const outOf = function( action, body ) {

		const x = stateOf().extra;

		if( Array.isArray( body.tree ) === true )
			body.tree = body.tree.map( function( node ) { return cloned( node, action === 'save' || action === 'saveas' ) } );

		if( ( action === 'save' || action === 'saveas' ) && x.vars !== undefined ) {

			body.variants = setsOut( stateOf().top, x );
			body.details = x.details;
		}

		// The pictures that were chosen for a place, or taken away: what the save makes of the staged files
		if( action === 'save' && x.ownChosen !== undefined && Object.keys( x.ownChosen ).length > 0 )
			body.own = x.ownChosen;
	};

	// What is made of an answer of the server: the variants and the details as they are saved, the variant that was edited kept where it is still there
	const tookFrom = function( response, previous ) {

		const x = stateOf().extra;
		const list = Array.isArray( response.alternatives ) === true ? response.alternatives : [];

		x.vars = list.map( function( variant ) { return { name : variant.name, label : Object.assign( {}, variant.label ) } } );
		x.details = response.details === undefined ? undefined : JSON.parse( JSON.stringify( response.details ) );
		x.own = response.own === undefined || Array.isArray( response.own ) === true ? {} : Object.assign( {}, response.own );
		x.ownChosen = {};
		x.edited = x.vars.some( function( variant ) { return variant.name === previous.edited } ) === true ? previous.edited : firstOf( x );
		attach( stateOf().top, list );
		materialize( stateOf().top, x.edited );
	};

	const labelOf = function( variant ) {

		const x = variant.label ?? {};

		return String( x[Nino.admin.workLocale.current] ?? x.en_US ?? x[Object.keys( x )[0]] ?? '' ) || word('variants/standard');
	};

	// The value an attribute has when it is not written
	const defaultOf = function( node, name ) {

		const declared = ( ( stateOf().schemas[node.n] ?? {} ).attributes ?? {} )[name];

		return declared === undefined ? '' : String( declared.default );
	};

	// An attribute of a call is changed: the template's, or - in a variant other than the standard, for a look - the set of that variant
	const attributeOf = function( node, name, value ) {

		const s = stateOf();
		const x = s.extra;

		if( varied() === false )
			return false;

		const declared = ( ( s.schemas[node.n] ?? {} ).attributes ?? {} )[name];

		node.a = node.a ?? {};

		if( x.edited === firstOf( x ) ) {

			if( node.o !== undefined && Object.hasOwn( node.o, name ) === true )
				node.o[name] = value;

			return false;
		}

		if( declared === undefined || declared.class !== true )
			return false;

		node.o = node.o ?? {};
		node.v = node.v ?? {};

		if( Object.hasOwn( node.o, name ) === false )
			node.o[name] = node.a[name] ?? null;

		const set = node.v[x.edited] ?? {};
		const template = node.o[name] === null ? defaultOf( node, name ) : String( node.o[name] );
		const want = value === null ? defaultOf( node, name ) : String( value );

		if( want === template ) {
			delete set[name];

			if( node.o[name] === null )
				delete node.a[name];
			else
				node.a[name] = node.o[name];
		}
		else {
			set[name] = want;
			node.a[name] = value === null ? want : value;
		}

		if( Object.keys( set ).length === 0 )
			delete node.v[x.edited];
		else
			node.v[x.edited] = set;

		// The row says it is the variant's: the box is drawn again when this step is done
		wn.setTimeout( function() { const box = stateOf().box; if( box !== null && box !== undefined && typeof box.draw === 'function' ) box.draw() }, 0 );

		return true;
	};

	// What a row of the look says in a variant: a row that writes the set of the variant has a dot before its name - filled where the set has a value for it, a ring where it could - and,
	// where it has one, the way back to the template as a small button. A row that writes the template carries nothing
	const decorateOf = function( row, rel, names ) {

		if( inVariant() === false )
			return;

		const s = stateOf();
		const node = Nino.admin.builder.model.relNode( rel );

		if( node === null || node === undefined )
			return;

		const kinds = names.map( function( name ) { return ( ( ( s.schemas[node.n] ?? {} ).attributes ?? {} )[name] ?? {} ).class === true } );

		if( kinds.every( function( looks ) { return looks === false } ) === true )
			return;

		const own = ( node.v ?? {} )[s.extra.edited] ?? {};
		const set = names.filter( function( name ) { return Object.hasOwn( own, name ) } );
		const dot = el( 'span', 'builder-var-dot'+ ( set.length > 0 ? ' is-set' : '' ) );

		dot.appendChild( el( 'span', 'nino-admin-sr-only', word( set.length > 0 ? 'variants/dot-set' : 'variants/dot-free' ) ) );
		row.classList.add('builder-var-look');
		row.insertBefore( dot, row.firstChild );

		if( set.length === 0 )
			return;

		row.classList.add('builder-var-set');

		row.appendChild( Nino.adminUi.iconButton( 'undo-2', word('variants/reset'), function() {

			editor().change( function() {

				set.forEach( function( name ) {

					const here = Nino.admin.builder.model.relNode( rel );

					if( here.v !== undefined && here.v[s.extra.edited] !== undefined ) {

						delete here.v[s.extra.edited][name];

						if( Object.keys( here.v[s.extra.edited] ).length === 0 )
							delete here.v[s.extra.edited];

						if( Object.keys( here.v ).length === 0 )
							delete here.v;
					}

					if( here.o[name] === null )
						delete here.a[name];
					else
						here.a[name] = here.o[name];
				} );
			} );

			wn.setTimeout( function() { if( s.box !== null && typeof s.box.draw === 'function' ) s.box.draw() }, 0 );
		}, { small : true, className : 'builder-var-reset' } ) );
	};

	// The head of a section of a component in a variant says which variant is edited, and behind a (?) what the dot is: one sentence for the whole lightbox
	const noteOf = function() {

		if( inVariant() === false )
			return null;

		const s = stateOf();
		const now = ( s.extra.vars ?? [] ).find( function( variant ) { return variant.name === s.extra.edited } );
		const note = el( 'p', 'nino-admin-hint builder-var-note' );

		note.appendChild( document.createTextNode( word( 'variants/button', now === undefined ? word('variants/standard') : labelOf( now ) ) ) );
		note.appendChild( Nino.adminUi.fieldHelp( word('variants/note-help'), { field : note, name : word('variants/note-name') } ) );

		return note;
	};

	const slugOf = function( text ) {

		return String( text ).normalize('NFKD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /ß/g, 'ss' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, 40 ) || 'variante';
	};

	const switchTo = function( name ) {

		const x = stateOf().extra;

		if( x.edited === name )
			return;

		told = name;
		editor().reshape( function() {
			materialize( stateOf().top, name );
			x.edited = name;
		} );
		paintVariant();
	};

	// The button of the bar says which variant is edited
	const paintVariant = function() {

		const s = stateOf();
		const button = s.view !== null && s.view.bar.buttons !== undefined ? s.view.bar.buttons.variant : undefined;

		if( button === undefined || button === null )
			return;

		const now = ( s.extra.vars ?? [] ).find( function( variant ) { return variant.name === s.extra.edited } );
		const text = word( 'variants/button', now === undefined ? word('variants/standard') : labelOf( now ) );

		const span = button.querySelector('span');

		// The name of the button is the words it shows: a person who speaks to the page says what she reads
		( span === null ? button : span ).textContent = text;
		button.setAttribute( 'aria-label', text );
		button.title = text;
		button.dataset.variant = s.extra.edited ?? '';
	};

	const askNewVariant = function( theme, opener ) {

		const x = stateOf().extra;

		if( ( x.vars ?? [] ).length >= MAX_VARIANTS ) {
			Nino.adminUi.say( word( 'variants/many', MAX_VARIANTS ) );
			return;
		}

		const now = ( x.vars ?? [] ).find( function( variant ) { return variant.name === x.edited } ) ?? { label : {} };
		const locales = ( stateOf().locales ?? [] ).map( function( entry ) { return entry.locale } );

		Nino.adminUi.formDialog( {
			title			: word('variants/new-title'),
			message		: word( 'variants/new-note', labelOf( now ) ),
			fields		: [ { key : 'name', type : 'locales', label : word('variants/name'), locales : locales, work : Nino.admin.workLocale.current, values : {}, required : false, maxLength : 80,
				validate : function( value ) { return String( ( value ?? {} )[Nino.admin.workLocale.current] ?? '' ).trim() === '' ? word('variants/name-missing') : '' } } ],
			submit		: word('variants/new-submit'),
			cancel		: Nino.adminUi.text('/_admin/common/label/cancel'),
			opener		: opener,
			onSubmit	: function( values, ctl ) {

				const label = {};

				locales.forEach( function( locale ) { label[locale] = String( values.name[locale] ?? '' ).trim() === '' ? String( values.name[Nino.admin.workLocale.current] ).trim() : String( values.name[locale] ).trim() } );

				let name = slugOf( label[Nino.admin.workLocale.current] );
				let count = 2;

				while( ( x.vars ?? [] ).some( function( variant ) { return variant.name === name } ) === true )
					name = slugOf( label[Nino.admin.workLocale.current] )+ '-'+ ( count++ );

				const from = x.edited;

				told = name;
				editor().change( function() {

					const root = stateOf().top[rootOf( stateOf().top )];

					x.vars.push( { name : name, label : label } );

					// It begins as the variant that is edited: what that sets, it sets
					calls( root, function( node ) {
						if( from !== firstOf( x ) && node.v !== undefined && node.v[from] !== undefined ) {
							node.v[name] = Object.assign( {}, node.v[from] );
						}
					} );

					materialize( stateOf().top, name );
					x.edited = name;
				} );

				ctl.close();
				paintVariant();
			},
		} );
	};

	// Move, rename and delete: the first is the template, stays the first and is never taken away
	const askManage = function( opener ) {

		const x = stateOf().extra;
		const box = el( 'div', 'builder-var-manage' );
		const work = Nino.admin.workLocale.current;

		const draw = function() {

			box.innerHTML = '';

			x.vars.forEach( function( variant, at ) {

				const row = el( 'div', 'builder-var-row' );
				const field = Nino.adminUi.textField( { key : 'var-'+ variant.name, label : at === 0 ? word('variants/standard-of') : word( 'variants/name-of', at ), maxLength : 80, value : String( variant.label[work] ?? labelOf( variant ) ) } );
				const input = field.querySelector('input');

				input.addEventListener( 'change', function() {

					const text = input.value.trim();

					if( text === '' )
						return;

					editor().change( function() {

						const was = String( variant.label[work] ?? '' );

						Object.keys( variant.label ).forEach( function( locale ) {
							if( locale === work || variant.label[locale] === was )
								variant.label[locale] = text;
						} );
					} );
					paintVariant();
				} );

				row.appendChild( field );

				if( at > 0 ) {

					[ [ 'arrow-up', 'variants/up', -1 ], [ 'arrow-down', 'variants/down', 1 ] ].forEach( function( move ) {

						const to = at + move[2];
						const button = Nino.adminUi.iconButton( move[0], word( move[1], labelOf( variant ) ), function() {

							if( to < 1 || to >= x.vars.length )
								return;

							editor().change( function() { x.vars.splice( to, 0, x.vars.splice( at, 1 )[0] ) } );
							draw();
						}, { small : true, disabled : to < 1 || to >= x.vars.length } );

						row.appendChild( button );
					} );

					row.appendChild( Nino.adminUi.iconButton( 'trash-2', word( 'variants/delete', labelOf( variant ) ), function( event ) {

						Nino.adminUi.confirm( 'destructive', {
							title		: word('variants/delete-title'),
							message	: word( 'variants/delete-message', labelOf( variant ) ),
							yes			: word('variants/delete-yes'),
							no			: Nino.adminUi.text('/_admin/common/label/cancel'),
							kind		: 'danger',
							visitors	: false,
							opener	: event.currentTarget,
							nested	: true,
							onChoose : function( ok ) {

								if( ok !== true )
									return;

								editor().change( function() {

									const root = stateOf().top[rootOf( stateOf().top )];

									calls( root, function( node ) {
										if( node.v !== undefined ) {
											delete node.v[variant.name];

											if( Object.keys( node.v ).length === 0 )
												delete node.v;
										}
									} );

									x.vars = x.vars.filter( function( other ) { return other.name !== variant.name } );

									if( x.edited === variant.name ) {
										told = firstOf( x );
										x.edited = firstOf( x );
										materialize( stateOf().top, x.edited );
									}
								} );
								draw();
								paintVariant();
							},
						} );
					}, { small : true, danger : true } ) );
				}

				box.appendChild( row );
			} );
		};

		draw();

		Nino.adminUi.choiceDialog( {
			title			: word('variants/manage-title'),
			content		: box,
			size			: 'form',
			className	: 'builder-lightbox',
			opener		: opener,
			choices		: [ { value : 'close', label : Nino.adminUi.text('/_admin/common/label/close'), kind : 'secondary' } ],
			onChoose	: function() { paintVariant() },
		} );
	};

	// The variants of the block in a menu, with the way to a new one and to the management of them
	const askVariant = function( theme, opener ) {

		const x = stateOf().extra;
		const items = ( x.vars ?? [] ).map( function( variant, at ) { return { value : variant.name, label : labelOf( variant ), description : at === 0 ? word('variants/standard-note') : '' } } );

		if( theme !== true ) {
			items.push( { value : '@new', label : word('variants/new') } );

			if( varied() === true )
				items.push( { value : '@manage', label : word('variants/manage') } );
		}

		Nino.adminUi.pickDialog( {
			title		: word( theme === true ? 'variants/menu-title-view' : 'variants/menu-title' ),
			items		: items,
			cancel	: Nino.adminUi.text('/_admin/common/label/cancel'),
			size		: 'narrow',
			opener	: opener,
			onChoose	: function( value ) {

				if( value === '@new' )
					askNewVariant( theme, opener );
				else if( value === '@manage' )
					askManage( opener );
				else
					switchTo( value );
			},
		} );
	};

	// The picture places that no call of the draft reads: told from what the draft reads now - the sources the last preview named - and, before there is one, from what the sheet
	// was opened with. The kernel holds no place that is not read, so such a place goes when the block is saved
	const unusedNow = function() {

		const s = stateOf();
		const d = s.extra.details;

		if( d === undefined || d === null )
			return [];

		const sources = Array.isArray( s.sources ) === true ? s.sources.filter( function( entry ) { return entry.kind === 'image' && typeof entry.key === 'string' } ) : [];
		const top = s.top ?? [];
		const root = top[rootOf( top )];
		const base = root === undefined ? '' : String( ( root.a ?? {} ).id ?? '' );

		if( Array.isArray( s.sources ) === false || s.sources.length === 0 || base === '' )
			return Object.keys( d.images ).filter( function( name ) { return d.images[name].used === false } );

		const read = new Set();

		sources.forEach( function( entry ) {

			const found = /^\/template\/[^/]+\/([^/]+)\/(.+)$/.exec( entry.key );

			if( found === null )
				return;

			const inner = found[1] === base ? '' : ( found[1].indexOf( base+ '-' ) === 0 ? found[1].slice( base.length + 1 ) : found[1] );

			read.add( ( inner === '' ? '' : inner+ '-' )+ found[2] );
		} );

		return Object.keys( d.images ).filter( function( name ) { return read.has( name ) === false } );
	};

	// The button of the details says, quietly, when places of pictures go with the next save
	const paintDetails = function() {

		const s = stateOf();
		const button = s.view !== null && s.view.bar.buttons !== undefined ? s.view.bar.buttons.details : undefined;

		if( button === undefined || button === null )
			return;

		const gone = unusedNow();
		const was = button.querySelector('.builder-var-dot');

		if( was !== null )
			was.remove();

		if( gone.length === 0 ) {
			button.removeAttribute('data-notice');
			button.title = word( 'details/button-of', localized( ( s.extra.details ?? { names : {} } ).names ) );
			return;
		}

		const dot = el( 'span', 'builder-device-dot builder-var-dot' );

		dot.setAttribute( 'aria-hidden', 'true' );
		button.appendChild( dot );
		button.dataset.notice = gone.join(' ');
		button.title = word( 'details/falls-away', gone.join(', ') );
	};

	// The variant that was told last: a step back or forward may land in another variant than the one that is shown - the change it takes back was made there - and the bar says so
	let told = null;

	const painted = function() {

		const s = stateOf();

		paintVariant();
		paintDetails();

		if( s.extra.edited === undefined )
			return;

		if( told !== null && told !== s.extra.edited && s.view !== null && s.view.bar.line !== undefined ) {

			const now = ( s.extra.vars ?? [] ).find( function( variant ) { return variant.name === s.extra.edited } );

			s.view.bar.line.idle( word( 'variants/edited', now === undefined ? word('variants/standard') : labelOf( now ) ) );
		}

		told = s.extra.edited;
	};

	// The details of the block in a lightbox: its names and descriptions in each language, the category, the rank and the places of its pictures. Each change is a step of the undo,
	// and is saved with the block
	const askDetails = function( opener ) {

		const x = stateOf().extra;
		const d = x.details;

		if( d === undefined || d === null )
			return;

		const locales = ( stateOf().locales ?? [] ).map( function( entry ) { return entry.locale } );
		const work = Nino.admin.workLocale.current;
		const holder = el( 'div', 'builder-box' );
		// An undo puts the details back as another object: what is changed is always those of the draft as it is now
		const set = function( make ) { editor().change( function() { make( stateOf().extra.details ) } ) };
		const sections = [];

		const rows = function( key, label, help, values, multiline, limit ) {

			const handle = Nino.adminUi.localeRows( { key : key, label : label, help : help, locales : locales, work : work, values : values, required : false, maxLength : limit, multiline : multiline, rows : 3, onChange : function( locale, text ) { set( function( now ) { now[key === 'name' ? 'names' : 'descriptions'][locale] = text } ) } } );

			return handle.el;
		};

		const body = function( parts ) {

			const part = el( 'div', 'nino-admin-dialog-fields builder-box-body' );

			parts.forEach( function( one ) { part.appendChild( one ) } );

			return part;
		};

		sections.push( { key : 'name', label : word('details/name'), summary : String( d.names[work] ?? '' ), content : function() { return body( [ rows( 'name', word('details/name'), undefined, d.names, false, 80 ), rows( 'description', word('details/description'), undefined, d.descriptions, true, 240 ) ] ) } } );

		sections.push( { key : 'order', label : word('details/order'), summary : word( 'cat/'+ d.category )+ ' · '+ d.rank, content : function() {

			const category = Nino.adminUi.selectField( { key : 'category', label : word('details/category'), help : word('details/category-help'), options : CATEGORIES.map( function( one ) { return { value : one, label : word( 'cat/'+ one ) } } ), value : d.category, onChange : function( value ) { set( function( now ) { now.category = value } ) } } );
			const rank = Nino.adminUi.numberField( { key : 'rank', label : word('details/rank'), help : word('details/rank-help'), value : d.rank, min : 0, max : 999 } );

			rank.querySelector('input').addEventListener( 'change', function( event ) {

				const number = Math.max( 0, Math.min( 999, Math.round( Number( event.target.value ) ) ) );

				event.target.value = String( Number.isNaN( number ) === true ? 0 : number );
				set( function( now ) { now.rank = Number.isNaN( number ) === true ? 0 : number } );
			} );

			return body( [ category, rank ] );
		} } );

		sections.push( { key : 'images', label : word('details/images'), summary : String( Object.keys( d.images ).length ), content : function() {

			const list = el( 'div', 'builder-var-images' );

			const draw = function() {

				list.innerHTML = '';

				if( Object.keys( stateOf().extra.details.images ).length === 0 )
					list.appendChild( el( 'p', 'nino-admin-hint', word('details/images-none') ) );

				Object.keys( stateOf().extra.details.images ).forEach( function( name ) {

					const image = stateOf().extra.details.images[name];
					const group = el( 'div', 'builder-var-image' );
					const head = el( 'div', 'builder-var-image-head' );

					head.appendChild( el( 'strong', '', name ) );

					const lost = unusedNow().indexOf( name ) !== -1;

					if( lost === true )
						head.appendChild( el( 'span', 'nino-admin-badge', word('details/unused') ) );

					group.appendChild( head );

					// The format of a place - the placeholder and the size - is written where the place is: in the lightbox of the component that reads it. This is the list of them
					group.appendChild( el( 'p', 'nino-admin-hint', word( 'details/image-is', String( image.width ), String( image.height ), word( 'details/ph-'+ String( image.placeholder ).slice( String( image.placeholder ).lastIndexOf('/') + 1 ) ) ) ) );

					// A place no call of the block reads any more is taken away here; one that is read is made where the picture is put in
					if( lost === true )
						group.appendChild( Nino.adminUi.button( word( 'details/image-delete', name ), function() {
							set( function( now ) { delete now.images[name] } );
							draw();
						}, { small : true, kind : 'danger' } ) );

					list.appendChild( group );
				} );
			};

			draw();

			return list;
		} } );

		const accordion = Nino.adminUi.accordion( { id : 'blocks-details-acc', level : 3, open : 'name', sections : sections, onToggle : function() {} } );

		holder.appendChild( accordion.el );

		Nino.adminUi.choiceDialog( {
			title			: word( 'details/title', labelOf( { label : d.names } ) ),
			content		: holder,
			size			: 'form',
			className	: 'builder-lightbox',
			opener		: opener,
			choices		: [ { value : 'close', label : Nino.adminUi.text('/_admin/common/label/close'), kind : 'secondary' } ],
			onChoose	: function() {},
		} );
	};

	const adapterOf = function( key, theme, response ) {

		told = null;

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
			// The properties of a component open in the column beside the preview, not in a lightbox
			props		: 'panel',
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
			// The variants and the details: what the editor keeps beside the tree, and how it sends and takes it
			canon		: canon,
			out			: outOf,
			took		: tookFrom,
			attribute	: attributeOf,
			decorate	: decorateOf,
			note		: noteOf,
			painted	: painted,
			buttons	: [ { key : 'saveas', label : word( theme === true ? 'saveas/button-theme' : 'saveas/button' ), name : word( theme === true ? 'saveas/name-theme' : 'saveas/name', title ), onPress : function() { askSaveAs( adapter ) } } ],
			// What is about the block as a whole stands beside its name in the bar of the context: its details and the variant that is edited
			context	: ( theme === true ? [] : [ { key : 'details', icon : 'settings-2', label : word('details/button'), name : word( 'details/button-of', title ), onPress : function( event ) { askDetails( stateOf().view.bar.buttons.details ) } } ] ).concat( [ { key : 'variant', icon : 'panels-top-left', label : word( 'variants/button', word('variants/standard') ), name : word( 'variants/button', word('variants/standard') ), onPress : function() { askVariant( theme, stateOf().view.bar.buttons.variant ) } } ] ),
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
			props		: 'panel',
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
			paintVariant();

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
			editor().hold();
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
