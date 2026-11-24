/**
 *	Nino features
 *	admin.js		The panel of the Pdf feature: three tabs, drawn by one script of three namespaces - the shell asks for a
 *							screen by the uri of its tab. Nino.admin.pdf is the Layout, the panel's own screen, Nino.admin['pdf-blocks']
 *							the library of text blocks and Nino.admin['pdf-templates'] the templates. Built from the blocks of the
 *							workbench (docs/admin-ui.md): panel(), directory(), record(), accordion(), the field blocks, fileField(),
 *							formDialog(), pickDialog(), confirm(), contextBar() and actionBar().
 *
 *							The addresses are #pdf (the layout, #pdf/pdf), #pdf/pictures, #pdf/blocks and #pdf/blocks/<key> (the record of a block opens over the directory), and #pdf/templates,
 *							#pdf/templates/@new and #pdf/templates/<key>.
 *
 *							Layout: the paper, the four margins, the font and the base size, the header and the footer as HTML and the
 *							design as CSS are one form, saved as a whole (model A). The logo and the signature of the owner are put in and
 *							taken away at once (model C) and are no part of the form. A text block is a record of the standard directory (saved as a whole, model A; New is a dialog of three fields). A template is a
 *							form whose blocks are the sections of an accordion; its preview asks the server for the PDF of what the form
 *							holds, saved or not, and shows it with a link that downloads it.
 *
 *							Admin/Admin.php does the reading and writing. A refusal comes back as a code which the workbench says in the
 *							session language; the field it names gets the sentence.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( key ) { return Nino.content.getText( '/_admin/pdf/'+ key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };
	const call = function( action, payload, done, extra ) { Nino.adminUi.api.call( 'pdf/'+ action, payload, done, extra ) };
	const refresh = function() { if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty !== null ) Nino.admin.dirty.refresh() };

	// What a header, a footer and a text may hold between double brackets
	const TOKENS = '[[page]], [[pages]], [[name]], [[/text/key]]';
	const BODY_TOKENS = '[[name]], [[a.b]], [[/text/key]]';

	const TYPES = [ 'html', 'slot', 'input', 'signature', 'pagebreak' ];

	// The sentence of a failed request, the panel's own for the code
	const said = function( status, response, key ) { return Nino.adminUi.api.errorText( status, response, key ) };

	// The control of a field block: its input, select or textarea
	const control = function( field ) { return field.querySelector('input, select, textarea') };

	// Say a refusal at a field and put the focus there
	let serial = 0;
	const refuse = function( field, text ) {

		const input = control( field );

		Nino.adminUi.fieldError( field, input, 'pdf-error-'+ ( ++serial ), text );
		return input;
	};

	// A request that failed after a question was answered, said above the list: the sentence stays until the list is drawn again
	const refusedAbove = function( mount, status, response, key ) {

		Array.prototype.forEach.call( mount.querySelectorAll('.pdf-refusal'), function( old ) { old.parentNode.removeChild( old ) } );

		const holder = el( 'div', 'pdf-refusal' );

		Nino.adminUi.showError( holder, status, response, key );
		holder.firstChild.setAttribute( 'role', 'alert' );
		mount.insertBefore( holder, mount.firstChild );
	};

	// The place of the PDF a preview made: a frame and the link that downloads it
	const previewBox = function( title ) {

		const box = el( 'div', 'pdf-preview' );
		const link = el( 'a', 'nino-admin-linkbutton', w('label/download') );
		const frame = el( 'iframe', 'pdf-preview-frame' );

		box.hidden = true;
		box.blobUrl = '';
		frame.setAttribute( 'title', title );
		box.appendChild( link );
		box.appendChild( frame );

		return box;
	};

	const showPdf = function( box, pdf, file ) {

		const bytes = Uint8Array.from( atob( pdf ), function( char ) { return char.charCodeAt( 0 ) } );

		if( box.blobUrl !== '' )
			URL.revokeObjectURL( box.blobUrl );

		box.blobUrl = URL.createObjectURL( new Blob( [ bytes ], { type : 'application/pdf' } ) );
		box.querySelector('iframe').src = box.blobUrl;
		box.querySelector('a').href = box.blobUrl;
		box.querySelector('a').setAttribute( 'download', file );
		box.hidden = false;
	};

	const dropPdf = function( box ) {

		if( box !== null && box.blobUrl !== '' )
			URL.revokeObjectURL( box.blobUrl );
	};

	// A section of a form: a fieldset with its legend and the grid that puts a label at the left of its field
	const section = function( title, help ) {

		const set = el( 'fieldset' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );
		const legend = el( 'legend', '', title );

		set.appendChild( legend );
		set.appendChild( grid );

		if( help !== undefined )
			Nino.adminUi.sectionHelp( legend, set, help );

		return { set : set, grid : grid };
	};

	// A number the server holds a field to: whole numbers go as numbers, anything else as typed so that the server says what is wrong with it
	const numeric = function( value ) {
		return value.trim() !== '' && isNaN( Number( value ) ) === false ? Number( value ) : value;
	};

	/**
	 *	The addresses of a tab that has a list and one record on a screen of its own: #pdf/<tab>, #pdf/<tab>/@new and #pdf/<tab>/<key>. The word @new
	 *	is the record that is not saved yet and a key is a key, whatever it is called. What the tab draws is its own - hooks.view() is the screen that
	 *	is open, null on the list, hooks.show( record, focus ) opens one, hooks.list() draws the directory. Leaving a screen with input nobody saved
	 *	asks the shell first. Nothing here writes an address but Nino.admin.router
	 *
	 *	@param		{string}		uri
	 *	@param		{string}		listMount
	 *	@param		{Object}		hooks			{ view, setView, find, show, list }
	 *
	 *	@return		{Object}							{ go, follow, showCurrent }
	 */
	const addressing = function( uri, listMount, hooks ) {

		// The parts of an address: the word @new for a record that is not saved yet, its key, none for the directory
		const partsOf = function( was ) {
			return was === null ? [] : [ was === '' ? Nino.admin.router.word('new') : was ];
		};

		// What an address names: '' for @new, the key of a record, null for the directory - and for a word this tab has not
		const targetOf = function( at ) {

			const router = Nino.admin.router;

			if( at.panel !== uri || at.parts.length === 0 )
				return null;

			if( router.isWord( at.parts, 0, 'new' ) === true )
				return '';

			return router.wordOf( at.parts, 0 ) === '' ? String( at.parts[0] ) : null;
		};

		const addressOf = function() {
			const view = hooks.view();
			return partsOf( view === null ? null : view.was );
		};

		const follow = function( focus ) {

			const target = targetOf( Nino.admin.router.current() );
			const record = target === null || target === '' ? undefined : hooks.find( target );
			const view = hooks.view();

			if( target === '' ) {

				if( view === null || view.was !== '' )
					hooks.show( null, focus );

				Nino.admin.router.set( uri, partsOf( '' ) );
				return;
			}

			if( record !== undefined ) {

				if( view === null || view.was !== record.key )
					hooks.show( record, focus );

				Nino.admin.router.set( uri, partsOf( record.key ) );
				return;
			}

			const left = view !== null && view.was !== '' ? view.was : null;

			hooks.setView( null );
			hooks.list();
			Nino.adminUi.showLevel( listMount, { focus : false } );

			// The way back from a record puts the focus on its row, which the directory has drawn again
			if( left !== null && focus.focus !== false && typeof hooks.focusRow === 'function' )
				hooks.focusRow( left );

			// A key that names nothing is said over the list, as the record of a directory says it
			if( target !== null && target !== '' && typeof hooks.missing === 'function' )
				hooks.missing( target );

			Nino.admin.router.set( uri, [] );
		};

		// Move to a record: '' for one that is not saved yet, null for the directory
		const go = function( was ) {

			const proceed = function() {
				Nino.admin.router.go( uri, partsOf( was ) );
				follow( {} );
			};

			const view = hooks.view();

			if( view !== null && view.was !== was && typeof Nino.admin.dirty === 'object' && Nino.admin.dirty !== null )
				return Nino.admin.dirty.guard( [ uri ], proceed, function() {} );

			proceed();
		};

		const showCurrent = function( panel ) {

			const at = Nino.admin.router.current();

			if( panel.ready === true && at.panel !== uri ) {
				Nino.admin.router.set( uri, addressOf() );
				return;
			}

			if( panel.ready === false ) {
				panel.init();
				return;
			}

			const view = hooks.view();
			const leaving = view !== null && targetOf( at ) !== view.was;

			// A move inside the screen that is open leaves nothing: it is followed, and what the person typed stays
			if( leaving === false ) {
				follow( { focus : false } );
				return;
			}

			Nino.admin.router.leave( [ uri ], true, function() { hooks.setView( null ); follow( { focus : false } ) }, function() {
				Nino.admin.router.set( uri, addressOf() );
			} );
		};

		return { go : go, follow : follow, showCurrent : showCurrent };
	};

	// The row that is next to the ones that went takes the focus: the one that follows the first of them, else the last that is left, else the search
	const focusNear = function( directory, before, gone ) {

		const left = before.filter( function( key ) { return gone.indexOf( key ) === -1 } );
		const at = before.findIndex( function( key ) { return gone.indexOf( key ) !== -1 } );
		const key = left[ Math.min( at, left.length - 1 ) ];

		if( key === undefined || directory.focusRow( key ) === false )
			directory.focusSearch();
	};

	// The words of the sort select of a card, and of the box that chooses rows, in the panel's own text
	const directoryWords = function( search, empty ) {
		return { search : search, empty : empty, noMatch : w('empty/nomatch'), sortBy : w('label/sort-by'), sortNone : w('label/sort-none'), sortUp : w('label/sort-up'), sortDown : w('label/sort-down') };
	};

	// Delete the chosen rows: one question that says how many, then one delete call for each - every call is its own request with its own right and token.
	// What the server refused is said over the list with the sentence of the first refusal; what went is out of the rows
	const deleteMany = function( keys, one ) {

		Nino.adminUi.confirm( 'destructive', {
			title : Nino.adminUi.format( w( 'confirm/'+ one.word +'-many-title' ), keys.length ), message : Nino.adminUi.format( w( 'confirm/'+ one.word +'-many' ), keys.length ),
			yes : Nino.adminUi.format( w( 'confirm/'+ one.word +'-many-title' ), keys.length ), no : t('/_admin/common/label/cancel'),
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				const gone = [];
				let refused = null;
				const before = one.rows().map( function( row ) { return row.key } );

				const next = function( at ) {

					if( at >= keys.length ) {
						one.after( gone, before );

						if( refused !== null )
							refusedAbove( one.mount(), refused[0], refused[1], '/_admin/pdf/error/delete' );
						else if( gone.length > 0 )
							Nino.adminUi.say( t('/_admin/common/msg/deleted') );

						return;
					}

					call( one.action, { key : keys[at] }, function( status, response ) {

						if( status === 200 && response !== null )
							gone.push( keys[at] );
						else if( refused === null )
							refused = [ status, response ];

						next( at + 1 );
					} );
				};

				next( 0 );
			},
		} );
	};

	// ------------------------------------------------------------------ Layout

	const L = { data : null, form : null, bar : null, controls : {}, saved : '', design : null, preview : null };

	// What the form holds right now, read back from its controls
	const layoutValues = function() {

		const c = L.controls;

		return {
			format : control( c.format ).value, font : control( c.font ).value, size : numeric( control( c.size ).value ),
			margins : c.margins.map( function( field ) { return numeric( control( field ).value ) } ),
			header : control( c.header ).value, footer : control( c.footer ).value, design : control( c.design ).value,
		};
	};

	const layoutPrint = function() { return L.form === null ? '' : JSON.stringify( layoutValues() ) };

	// The field a refusal names, as the server calls it
	const layoutField = function( name ) {

		const c = L.controls;

		return { format : c.format, font : c.font, size : c.size, margins : c.margins[0], header : c.header, footer : c.footer, design : c.design }[name];
	};

	const saveLayout = function( done ) {

		const values = layoutValues();
		const posted = JSON.stringify( values );

		Nino.adminUi.clearFieldErrors( L.form );
		L.bar.line.saving();

		call( 'layout/save', values, function( status, response ) {

			if( status !== 200 || response === null ) {

				L.bar.line.error( status, response, '/_admin/pdf/error/save' );

				const field = response !== null && typeof response === 'object' && typeof response.field === 'string' ? layoutField( response.field ) : undefined;

				if( field !== undefined )
					refuse( field, said( status, response, '/_admin/pdf/error/save' ) ).focus();

				done( false );
				return;
			}

			// The texts are the ones the server stored - the list of what a document may hold has cleaned the two bands, the design is trimmed -
			// and the form shows them from now on, unless the person has gone on typing since the post
			const unchanged = layoutPrint() === posted;
			const stored = JSON.parse( posted );

			[ 'header', 'footer', 'design' ].forEach( function( name ) {

				stored[name] = response.layout[name];

				if( unchanged === true )
					control( L.controls[name] ).value = stored[name];
			} );

			L.saved = JSON.stringify( stored );
			L.data = response;
			Nino.admin.pdf.data = response;
			control( L.controls.sheet ).value = response.sheet;
			layoutRefusal( response.refusal ?? null );
			L.bar.line.saved();
			refresh();
			done( true );
		} );
	};

	// A stored design that is no sheet is said with why it is not read; nothing of it is lost
	const layoutRefusal = function( refusal ) {

		if( L.design === null )
			return;

		const old = L.design.querySelector('.nino-admin-notice');

		if( old !== null )
			old.parentNode.removeChild( old );

		if( refusal !== null )
			L.design.insertBefore( Nino.adminUi.notice( Nino.adminUi.format( w('label/design-stored'), said( 400, refusal, '/_admin/pdf/error/save' ) ) ), L.design.children[1] );
	};

	const previewDesign = function() {

		L.bar.line.saving();

		call( 'layout/preview', {}, function( status, response ) {

			if( status !== 200 || response === null ) {
				L.bar.line.error( status, response, '/_admin/pdf/error/preview' );
				return;
			}

			showPdf( L.preview, response.pdf, response.file );
			L.bar.line.idle( '' );
		} );
	};

	const renderLayout = function( data, panel ) {

		const mount = panel.mount();
		const form = el( 'form' );
		const c = {};
		const band = function( key ) {
			return Nino.adminUi.codeField( { key : key, label : w( 'label/'+ key ), help : Nino.adminUi.format( w('hint/band'), TOKENS ), value : data.layout[key], rows : 6, maxLength : data.ranges.band } );
		};

		L.data = data;
		L.form = form;
		form.noValidate = true;
		mount.innerHTML = '';

		// The page: the paper, the font and its size
		const page = section( w('label/page') );

		c.format = Nino.adminUi.selectField( { key : 'format', label : w('label/paper'), value : data.layout.format, options : data.papers.map( function( paper ) { return { value : paper, label : w( 'paper/'+ paper ) } } ) } );
		c.font = Nino.adminUi.selectField( { key : 'font', label : w('label/font'), help : w('hint/font'), value : data.layout.font,
			options : data.fonts.map( function( font ) { return { value : font, label : w( 'font/'+ font ) } } ).concat( Object.keys( data.owned || {} ).map( function( font ) { return { value : font, label : data.owned[font] } } ) ) } );
		c.size = Nino.adminUi.numberField( { key : 'size', label : w('label/size'), value : data.layout.size, min : data.ranges.size[0], max : data.ranges.size[1] } );
		[ c.format, c.font, c.size ].forEach( function( field ) { page.grid.appendChild( field ) } );

		if( data.ignored !== undefined )
			page.set.appendChild( Nino.adminUi.hint( Nino.adminUi.format( w('hint/fonts-ignored'), data.ignored.join( ', ' ) ) ) );

		form.appendChild( page.set );

		const margins = section( w('label/margins'), w('hint/margins') );

		c.margins = [ 'top', 'right', 'bottom', 'left' ].map( function( side, at ) {

			const field = Nino.adminUi.numberField( { key : 'margin-'+ side, label : w( 'label/'+ side ), value : data.layout.margins[at], min : data.ranges.margin[0], max : data.ranges.margin[1] } );

			margins.grid.appendChild( field );

			return field;
		} );
		form.appendChild( margins.set );

		const bands = section( w('label/bands') );

		c.header = band( 'header' );
		c.footer = band( 'footer' );
		bands.grid.appendChild( c.header );
		bands.grid.appendChild( c.footer );
		form.appendChild( bands.set );

		// The design: the rules of the feature to read, the owner's over them to write
		const design = section( w('label/design'), w('hint/design') );

		c.sheet = Nino.adminUi.codeField( { key : 'sheet', label : w('label/design-sheet'), value : data.sheet, rows : 18, note : data.owned === undefined ? undefined : Nino.adminUi.format( w('hint/fonts'), Object.keys( data.owned ).map( function( font ) { return data.owned[font] } ).join( ', ' ) ) } );
		control( c.sheet ).readOnly = true;
		c.design = Nino.adminUi.codeField( { key : 'design', label : w('label/design-own'), help : w('hint/design-own'), value : data.layout.design, rows : 8, maxLength : data.ranges.design } );
		design.grid.appendChild( c.sheet );
		design.grid.appendChild( c.design );
		L.design = design.set;
		L.preview = previewBox( w('label/preview') );
		design.set.appendChild( L.preview );
		form.appendChild( design.set );
		L.controls = c;
		layoutRefusal( data.refusal ?? null );

		const look = Nino.adminUi.button( w('label/design-preview'), previewDesign, { kind : 'secondary' } );

		L.bar = Nino.adminUi.actionBar( {
			status : true,
			secondary : [ look ],
			primary : Nino.adminUi.button( t('/_admin/common/label/save'), null, { type : 'submit', kind : 'primary' } ),
		} );

		form.appendChild( L.bar.el );
		L.bar.line.bind( form );
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); saveLayout( function() {} ) } );
		mount.appendChild( form );
		L.saved = layoutPrint();
	};

	Nino.admin.pdf = Nino.adminUi.panel( {
		uri			: 'pdf',
		mount		: 'pdf-layout',
		action	: 'layout/get',
		show		: 'keep',
		error		: '/_admin/pdf/error/load',
		dirty		: {
			isDirty	: function() { return L.form !== null && layoutPrint() !== L.saved },
			save		: function( done ) { saveLayout( done ) },
			discard	: function() { dropPdf( L.preview ); L.form = null },
		},
		render	: renderLayout,
	} );

	// ------------------------------------------------------------------ Pictures

	// The logo and the owner's signature: a file to put in and a picture with the button that takes it away. Each is written when it is chosen
	// and taken away when it is confirmed (model C of the guide), and the toast says what was done
	const P = { data : null };

	const drawPictures = function( data, focus ) {

		const mount = dc.getElementById('pdf-pictures');
		const holder = el('div');
		const done = function( kind, word ) {
			return function( status, response ) {

				if( status !== 200 || response === null ) {
					pickers[kind].error( said( status, response, '/_admin/pdf/error/image' ) );
					return;
				}

				P.data = response;
				Nino.admin['pdf-pictures'].data = response;
				drawPictures( response, kind );
				Nino.adminUi.say( word );
			};
		};
		const pickers = {};

		mount.innerHTML = '';
		holder.className = 'pdf-pictures';

		[ 'logo', 'signature' ].forEach( function( kind ) {

			const set = el( 'fieldset' );
			const legend = el( 'legend', '', w( 'label/'+ kind ) );
			const name = data.layout[kind];
			const uri = data.images[kind];
			const label = w( 'label/'+ kind );

			set.appendChild( legend );
			Nino.adminUi.sectionHelp( legend, set, w( 'hint/'+ kind ) );

			pickers[kind] = Nino.adminUi.fileField( {
				key : kind, label : w('label/file'), accept : 'image/png,image/jpeg', maxBytes : Nino.adminUi.limits().bytes,
				note : Nino.adminUi.uploadHint() === null ? undefined : Nino.adminUi.uploadHint().textContent,
				onChange : function( file ) {

					if( file === null )
						return;

					Nino.adminUi.checkImage( file, function( refusal ) {

						if( refusal !== null ) {
							pickers[kind].error( said( 400, refusal, '/_admin/pdf/error/image' ) );
							return;
						}

						call( 'layout/image', { kind : kind }, done( kind, t('/_admin/common/msg/saved') ), { file : file } );
					} );
				},
			} );

			const grid = el( 'div', 'nino-admin-fieldgrid' );

			grid.appendChild( pickers[kind].el );
			set.appendChild( grid );

			if( uri !== '' ) {

				const img = el( 'img', 'pdf-picture-image' );

				img.src = uri;
				img.alt = label;
				set.appendChild( img );
			}
			else if( name !== '' )
				set.appendChild( Nino.adminUi.hint( name ) );
			else
				set.appendChild( Nino.adminUi.hint( w('label/none') ) );

			if( name !== '' ) {

				const drop = Nino.adminUi.button( t('/_admin/common/label/remove'), function() {

					Nino.adminUi.confirm( 'destructive', {
						title : w('confirm/picture-title'), message : Nino.adminUi.format( w('confirm/picture'), label ), yes : w('confirm/picture-title'), no : t('/_admin/common/label/cancel'),
						opener : drop,
						onChoose : function( ok ) { if( ok === true ) call( 'layout/image/delete', { kind : kind }, done( kind, t('/_admin/common/msg/deleted') ) ) },
					} );
				}, { small : true, kind : 'danger' } );

				set.appendChild( drop );
			}

			holder.appendChild( set );
		} );

		mount.appendChild( holder );

		// The screen is drawn again from the answer: the focus goes to the field of the picture that was put in or taken away
		if( focus !== undefined && pickers[focus] !== undefined )
			pickers[focus].button.focus();
	};

	Nino.admin['pdf-pictures'] = Nino.adminUi.panel( {
		uri			: 'pdf-pictures',
		mount		: 'pdf-pictures',
		action	: 'get',
		show		: 'always',
		error		: '/_admin/pdf/error/load',
		render	: function( data ) { P.data = data; drawPictures( data ) },
	} );

	// The requests of this tab are the panel's own: pdf/layout/<action>
	Nino.admin['pdf-pictures'].call = function( action, payload, done, extra ) { call( 'layout/'+ action, payload, done, extra ) };

	// ------------------------------------------------------------------ Text blocks

	// The text blocks are a directory and each opens in a record: a block is a form (a title, a key, a text)
	const B = { items : [], rows : [], ranges : { title : 120, html : 20000 }, directory : null, record : null, ready : false };

	const blockRow = function( block ) { return { key : block.key, title : block.title, html : block.html, chars : block.html.length } };

	const takeBlocks = function( response ) {

		B.items = response.blocks;
		B.rows = response.blocks.map( blockRow );
		B.ranges = response.ranges;
	};

	// A block taken out of the rows by the panel: the rows are the directory's, the record is over them
	const dropBlocks = function( gone, before ) {

		B.rows = B.rows.filter( function( row ) { return gone.indexOf( row.key ) === -1 } );
		B.items = B.items.filter( function( block ) { return gone.indexOf( block.key ) === -1 } );
		B.directory.setRows( B.rows, { keep : true } );
		focusNear( B.directory, before, gone );
	};

	const blockMount = function() { return dc.getElementById('pdf-blocks-list') };

	// The record's body: a title, the key that cannot change and the text. The record keeps what ctx holds for the save
	const blockForm = function( row, ctx ) {

		const set = el( 'div', 'nino-admin-dialog-fields' );

		ctx.fields = {
			title : Nino.adminUi.textField( { key : 'title', label : w('label/title'), help : w('hint/title'), value : row.title, required : true, maxLength : B.ranges.title, counter : true } ),
			key : Nino.adminUi.textField( { key : 'key', label : w('label/key'), help : w('hint/key'), value : row.key, maxLength : 40 } ),
			html : Nino.adminUi.codeField( { key : 'html', label : w('label/html'), help : Nino.adminUi.format( w('hint/block-html'), BODY_TOKENS ), value : row.html, rows : 14, maxLength : B.ranges.html, counter : true } ),
		};
		control( ctx.fields.key ).disabled = true;
		[ ctx.fields.title, ctx.fields.key, ctx.fields.html ].forEach( function( field ) { set.appendChild( field ) } );

		return set;
	};

	const saveBlock = function( row, ctx, done ) {

		const title = control( ctx.fields.title ).value.trim();
		const html = control( ctx.fields.html ).value;

		Nino.adminUi.clearFieldErrors( ctx.form );

		call( 'blocks/save', { key : row.key, isNew : false, title : title, html : html }, function( status, response ) {

			if( status !== 200 || response === null ) {

				const sentence = said( status, response, '/_admin/pdf/error/save' );
				const field = response !== null && typeof response === 'object' ? ctx.fields[ response.field ] : undefined;

				if( field !== undefined )
					refuse( field, sentence ).focus();

				return done( false, { error : sentence } );
			}

			takeBlocks( response );

			const saved = B.rows.find( function( one ) { return one.key === row.key } );

			// What the server stored of the text - the list of what a document may hold has cleaned it - is what the form shows from now on
			control( ctx.fields.html ).value = saved.html;
			B.directory.update( B.rows, row.key );
			done( true, { row : saved } );
		} );
	};

	// The question before a block is taken away, on the record that is open - nested - or over the directory
	const removeBlock = function( row, opener, done ) {

		Nino.adminUi.confirm( 'destructive', {
			title : w('confirm/block-title'), message : Nino.adminUi.format( w('confirm/block'), row.title ), yes : w('confirm/block-title'), no : t('/_admin/common/label/cancel'), nested : true, opener : opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return done( null );

				call( 'blocks/delete', { key : row.key }, function( status, response ) {

					if( status !== 200 || response === null )
						return done( false, { error : said( status, response, '/_admin/pdf/error/delete' ) } );

					const before = B.rows.map( function( one ) { return one.key } );

					dropBlocks( [ row.key ], before );
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
					done( true );
				} );
			},
		} );
	};

	// A new block is made in a dialog of three fields; the block is in the rows when it is made, and its row has the focus
	const newBlock = function( opener ) {

		let target = null;
		const back = { focus : function() { if( target !== null ) B.directory.focusRow( target ); else opener.focus() } };

		Nino.adminUi.formDialog( {
			title : w('label/new-block-title'), submit : w('label/create-block'), cancel : t('/_admin/common/label/cancel'), opener : back,
			fields : [
				{ key : 'title', label : w('label/title'), help : w('hint/title'), required : true, maxLength : B.ranges.title },
				{ key : 'key', label : w('label/key'), help : w('hint/key-new'), maxLength : 40 },
				{ key : 'html', type : 'code', label : w('label/html'), help : Nino.adminUi.format( w('hint/block-html'), BODY_TOKENS ), rows : 10, maxLength : B.ranges.html },
			],
			onSubmit : function( values, ctl ) {

				const title = values.title.trim();
				const key = values.key.trim() === '' ? Nino.slug( title, 40 ) : values.key.trim();

				call( 'blocks/save', { key : key, isNew : true, title : title, html : values.html }, function( status, response ) {

					if( status !== 200 || response === null ) {

						const field = response !== null && typeof response === 'object' && [ 'title', 'key', 'html' ].indexOf( response.field ) !== -1 ? response.field : 'title';

						return ctl.fail( field, said( status, response, '/_admin/pdf/error/save' ) );
					}

					takeBlocks( response );
					target = key;
					B.directory.setRows( B.rows, { keep : true } );
					ctl.close();
					Nino.adminUi.say( t('/_admin/common/msg/saved') );
				} );
			},
		} );
	};

	const drawBlocks = function( mount ) {

		mount.innerHTML = '';

		const holder = el('div');
		const bar = Nino.adminUi.actionBar( { list : true, primary : Nino.adminUi.button( w('label/new-block'), function() { newBlock( bar.primaryButton ) }, { kind : 'primary' } ) } );

		bar.primaryButton = bar.el.querySelector('button');
		mount.appendChild( holder );
		mount.appendChild( bar.el );

		B.directory = Nino.adminUi.directory( {
			mount : holder, rowKey : 'key', labels : directoryWords( w('label/search-blocks'), w('empty/blocks') ),
			columns : [
				{ key : 'title', label : w('label/title'), type : 'string', cell : 'clamp', max : '24rem', primary : true },
				{ key : 'key', label : w('label/key'), type : 'string', cell : 'clamp', fill : true, narrow : 'second' },
				{ key : 'chars', label : w('label/chars'), type : 'integer', cell : 'limit', limit : function() { return B.ranges.html }, over : w('label/over') },
			],
			searchText : function( row ) { return row.html },
			select : { label : w('label/select-blocks'), rowLabel : function( row ) { return row.title }, actions : [ { key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : function( keys ) {
				deleteMany( keys, { word : 'block', action : 'blocks/delete', rows : function() { return B.directory.rows() }, mount : blockMount, after : dropBlocks } );
			} } ] },
			actions : function( row ) {

				const edit = Nino.adminUi.iconButton( 'pencil', Nino.adminUi.format( w('label/edit-block'), row.title ), function( event ) { B.record.open( row.key, { opener : event.currentTarget } ) }, { small : true } );
				const drop = Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('label/delete-one'), row.title ), function( event ) {
					removeBlock( row, event.currentTarget, function( ok, info ) { if( ok === false ) refusedAbove( blockMount(), 400, { error : info.error }, '/_admin/pdf/error/delete' ) } );
				}, { small : true, danger : true } );

				return [ edit, drop ];
			},
			onOpen : function( row ) { B.record.open( row.key, {} ) },
		} );

		B.record = Nino.adminUi.record( {
			screen : 'pdf-blocks', directory : B.directory, keyOf : function( row ) { return row.key },
			find : function( key ) { return B.rows.find( function( row ) { return row.key === key } ) ?? null },
			title : function( row ) { return row.title }, subtitle : function( row ) { return row.key },
			saveNext : true, body : blockForm, save : saveBlock, danger : { label : w('label/delete-block'), run : removeBlock },
			missing : function() { B.directory.notice( w('missing/block') ) },
		} );
	};

	// The blocks come whole, the directory filters and sorts in the browser; once they are in, an address that names a block opens it
	const loadBlocks = function() {

		const mount = blockMount();

		if( mount === null )
			return;

		call( 'blocks/list', {}, function( status, response ) {

			if( status !== 200 || response === null )
				return B.directory === null ? Nino.adminUi.showError( mount, status, response, '/_admin/pdf/error/load' ) : B.directory.fail( status, response, '/_admin/pdf/error/load' );

			takeBlocks( response );

			if( B.directory === null )
				drawBlocks( mount );

			B.directory.setRows( B.rows, { keep : true } );
			B.ready = true;
			B.directory.notice('');
			B.record.follow();
		} );
	};

	Nino.admin['pdf-blocks'] = {
		showCurrent : function() {

			if( B.directory === null )
				return loadBlocks();

			// What the last address said - a block that is not there - is said once: the next address starts without it
			B.directory.notice('');

			if( B.record.follow() === false && B.record.isOpen() === false )
				loadBlocks();
		},
	};

	// ------------------------------------------------------------------ Templates

	const T = { items : [], offer : { tags : [], library : [], paper : [ 210, 297 ], ranges : { title : 120, html : 20000, label : 120, blocks : 60 } }, view : null, directory : null, state : {} };

	const templateFind = function( key ) { return T.items.find( function( template ) { return template.key === key } ) };

	const takeList = function( response ) {

		// A tag the settings no longer list is said in a column of its own, with a word and a sign
		T.items = response.templates.map( function( template ) { return Object.assign( {}, template, { note : template.known === false ? { text : w('label/tag-gone'), state : 'warn' } : '' } ) } );
		T.offer = { tags : response.tags, library : response.library, paper : response.paper, ranges : response.ranges };
	};

	const templates = addressing( 'pdf-templates', 'pdf-templates-list', {
		view		: function() { return T.view },
		setView	: function( view ) { dropPdf( T.view === null ? null : T.view.preview ); T.view = view },
		find		: templateFind,
		show		: function( template, focus ) { openTemplate( template, focus ) },
		list		: function() { drawTemplates() },
		focusRow	: function( key ) { if( T.directory !== null ) T.directory.focusRow( key ) },
		missing	: function() { if( T.directory !== null ) T.directory.notice( w('missing/template') ) },
	} );

	const templateMount = function() { return dc.getElementById('pdf-templates-list') };

	const removeTemplate = function( template, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title : w('confirm/template-title'), message : Nino.adminUi.format( w('confirm/template'), template.name ), yes : w('confirm/template-title'), no : t('/_admin/common/label/cancel'), opener : opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				call( 'templates/delete', { key : template.key }, function( status, response ) {

					if( status !== 200 || response === null )
						return refusedAbove( templateMount(), status, response, '/_admin/pdf/error/delete' );

					const before = T.items.map( function( one ) { return one.key } );
					const fromList = T.view === null;

					takeList( response );
					dropPdf( T.view === null ? null : T.view.preview );
					T.view = null;

					if( fromList === true ) {
						T.directory.setRows( T.items, { keep : true } );
						focusNear( T.directory, before, [ template.key ] );
					}
					else {
						Nino.admin.router.go( 'pdf-templates', [] );
						templates.follow( {} );
					}

					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// A copy of a template: it needs a name and nothing else, so it is a dialog of one field
	const duplicateTemplate = function( template, origin ) {

		// Where the focus goes when the dialog is closed - the kernel gives the focus back to the opener once the dialog has closed: the opener is the row the
		// focus belongs to, found when it is asked for - the copy's, else the original's. A dialog that was cancelled made no copy, and the focus goes back to
		// the button that opened it
		let target = template.key;
		const opener = { focus : function() {

			if( target === template.key )
				return origin.focus();

			if( T.directory.focusRow( target ) === false )
				T.directory.focusRow( template.key );
		} };

		Nino.adminUi.formDialog( {
			title : w('dialog/duplicate'), submit : w('dialog/duplicate'), cancel : t('/_admin/common/label/cancel'), opener : opener,
			fields : [ { key : 'name', label : w('label/name'), value : Nino.adminUi.format( w('label/copy-of'), template.name ), required : true, maxLength : T.offer.ranges.title } ],
			onSubmit : function( values, ctl ) {

				call( 'templates/duplicate', { key : template.key, name : values.name }, function( status, response ) {

					if( status !== 200 || response === null )
						return ctl.fail( 'name', said( status, response, '/_admin/pdf/error/save' ) );

					takeList( response );
					target = response.key;
					T.directory.setRows( T.items, { keep : true } );
					T.directory.reveal( target );
					ctl.close();
					Nino.adminUi.say( t('/_admin/common/msg/saved') );
				} );
			},
		} );
	};

	// The tag is the category of the directory where the project has tags: "all", the tags - the ones of the settings and the ones the templates carry - and "no tag"
	const tagCategory = function() {

		const tags = T.offer.tags.slice();

		T.items.forEach( function( template ) { if( template.tag !== '' && tags.indexOf( template.tag ) === -1 ) tags.push( template.tag ) } );

		if( tags.length === 0 )
			return undefined;

		const options = [ { value : '', label : w('label/all-tags') } ].concat( tags.map( function( tag ) { return { value : tag, label : tag } } ) );

		if( T.items.some( function( template ) { return template.tag === '' } ) === true )
			options.push( { value : '@none', label : w('label/no-tag') } );

		return { id : 'pdf-tag', label : w('label/tag'), value : '', options : options, match : function( row, value ) { return value === '' || ( value === '@none' ? row.tag === '' : row.tag === value ) } };
	};

	const drawTemplates = function() {

		const mount = templateMount();

		if( mount === null )
			return;

		mount.innerHTML = '';

		const holder = el('div');

		mount.appendChild( holder );
		mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : Nino.adminUi.button( w('label/new-template'), function() { templates.go( '' ) }, { kind : 'primary' } ) } ).el );

		T.directory = Nino.adminUi.directory( {
			mount : holder, rowKey : 'key', labels : directoryWords( w('label/search-templates'), w('empty/templates') ), state : T.state, onState : function( state ) { T.state = state },
			category : tagCategory(),
			columns : [
				{ key : 'name', label : w('label/name'), type : 'string', cell : 'clamp', max : '24rem', primary : true },
				{ key : 'key', label : w('label/key'), type : 'string', cell : 'clamp', fill : true, narrow : 'second' },
				{ key : 'tag', label : w('label/tag'), type : 'string' },
				{ key : 'note', label : w('label/state'), type : 'string', cell : 'badge', narrow : false },
				{ key : 'blocks', label : w('label/blocks-column'), type : 'integer' },
			],
			select : { label : w('label/select-templates'), rowLabel : function( row ) { return row.name }, actions : [ { key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : function( keys ) {
				deleteMany( keys, { word : 'template', action : 'templates/delete', rows : function() { return T.directory.rows() }, mount : templateMount, after : function( gone, before ) {

					T.items = T.items.filter( function( row ) { return gone.indexOf( row.key ) === -1 } );
					T.directory.setRows( T.items, { keep : true } );
					focusNear( T.directory, before, gone );
				} } );
			} } ] },
			actions : function( row ) {

				const edit = Nino.adminUi.iconButton( 'pencil', Nino.adminUi.format( w('label/edit-template'), row.name ), function() { templates.go( row.key ) }, { small : true } );
				const copy = Nino.adminUi.iconButton( 'copy', Nino.adminUi.format( w('label/duplicate-one'), row.name ), function( event ) { duplicateTemplate( row, event.currentTarget ) }, { small : true } );
				const drop = Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('label/delete-one'), row.name ), function( event ) { removeTemplate( row, event.currentTarget ) }, { small : true, danger : true } );

				return [ edit, copy, drop ];
			},
			onOpen : function( row ) { templates.go( row.key ) },
		} );

		T.directory.setRows( T.items );
	};

	// The block of a template as the form holds it: every field of its type, the text block with the library's key or a text of its own - and as the server takes it
	const held = function( block ) {

		if( block.type === 'html' )
			return { type : 'html', source : typeof block.block === 'string' ? block.block : '', html : typeof block.html === 'string' ? block.html : '' };

		return Object.assign( {}, block, block.type === 'signature' ? { page : String( block.page ) } : {} );
	};

	const sent = function( block ) {

		if( block.type === 'html' )
			return block.source !== '' ? { type : 'html', block : block.source } : { type : 'html', html : block.html };

		return block;
	};

	const fresh = function( type ) {

		const library = T.offer.library;

		return {
			html		: { type : 'html', source : library.length > 0 ? library[0].key : '', html : '' },
			slot		: { type : 'slot', name : 'table' },
			input		: { type : 'input', name : 'input', label : '', multiline : false, required : false },
			signature	: { type : 'signature', name : 'signature', party : 'owner', label : '', page : 'last', x : 20, y : Math.max( T.offer.paper[1] - 60, 20 ), w : 60, h : 20, line : true },
			pagebreak	: { type : 'pagebreak' },
		}[type];
	};

	// What stands on the closed head of a block: the name it is called by, or the text it takes
	const summaryOf = function( block ) {

		if( block.type === 'html' )
			return block.source === '' ? w('label/own-text') : ( T.offer.library.find( function( one ) { return one.key === block.source } ) || { title : block.source } ).title;

		return block.type === 'pagebreak' ? '' : ( block.type === 'signature' && block.label !== '' ? block.label : block.name );
	};

	const templateValues = function( view ) {

		return { key : control( view.controls.key ).value.trim(), name : control( view.controls.name ).value.trim(), tag : view.controls.tag === undefined ? '' : control( view.controls.tag ).value, blocks : view.draft.blocks.map( sent ) };
	};

	const templatePrint = function( view ) { return JSON.stringify( templateValues( view ) ) };

	// One block of the template as the fields of its type: every edit goes into the draft
	const blockBody = function( view, block, at ) {

		const body = el( 'div', 'pdf-block' );
		const changed = function() { view.accordion.update( String( at ), { summary : summaryOf( block ) } ); refresh() };
		const field = function( label, name, options ) {
			return Nino.adminUi.textField( Object.assign( { key : name, label : w( 'label/'+ label ), value : String( block[name] ), onInput : function( value ) { block[name] = value; changed() } }, options || {} ) );
		};
		const flag = function( label, name ) {
			return Nino.adminUi.switchField( { key : name, label : w( 'label/'+ label ), checked : block[name] === true, onChange : function( on ) { block[name] = on === true; changed() } } );
		};
		const grid = el( 'div', 'nino-admin-fieldgrid' );

		// The cluster that moves and takes away the block: part of the draft, nothing asked
		const cluster = el( 'span', 'nino-admin-move' );
		const label = ( at + 1 )+ '. '+ w( 'type/'+ block.type );
		const up = Nino.adminUi.iconButton( 'arrow-up', Nino.adminUi.format( w('label/move-up'), label ), function() { moveBlock( view, at, -1 ) }, { small : true, disabled : at === 0 } );
		const down = Nino.adminUi.iconButton( 'arrow-down', Nino.adminUi.format( w('label/move-down'), label ), function() { moveBlock( view, at, 1 ) }, { small : true, disabled : at === view.draft.blocks.length - 1 } );
		const drop = Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('label/block-remove'), label ), function() { removeBlockOf( view, at ) }, { small : true, danger : true } );

		[ up, down, drop ].forEach( function( button ) { cluster.appendChild( button ) } );
		view.clusters[at] = [ up, down, drop ];
		body.appendChild( cluster );
		body.appendChild( grid );

		if( block.type === 'pagebreak' ) {
			grid.appendChild( Nino.adminUi.hint( w('hint/pagebreak') ) );
			return body;
		}

		if( block.type === 'html' ) {

			const own = Nino.adminUi.codeField( { key : 'html', label : w('label/html'), help : Nino.adminUi.format( w('hint/block-html'), BODY_TOKENS ), value : block.html, rows : 8, maxLength : T.offer.ranges.html, onInput : function( value ) { block.html = value; changed() } } );
			const options = [ { value : '', label : w('label/own-text') } ].concat( T.offer.library.map( function( one ) { return { value : one.key, label : one.title } } ) );
			const source = Nino.adminUi.selectField( { key : 'source', label : w('label/source'), help : w('hint/source'), value : block.source, options : options, onChange : function( value ) {
				block.source = value;
				own.hidden = value !== '';
				changed();
			} } );

			own.hidden = block.source !== '';
			grid.appendChild( source );
			grid.appendChild( own );
			return body;
		}

		grid.appendChild( field( 'name', 'name', { maxLength : 40, help : w( block.type === 'slot' ? 'hint/slot' : 'hint/name' ), required : true } ) );

		if( block.type === 'slot' )
			return body;

		grid.appendChild( field( 'label', 'label', { maxLength : T.offer.ranges.label } ) );

		if( block.type === 'input' ) {
			grid.appendChild( flag( 'multiline', 'multiline' ) );
			grid.appendChild( flag( 'required', 'required' ) );
			return body;
		}

		grid.appendChild( Nino.adminUi.selectField( { key : 'party', label : w('label/party'), help : w('hint/party'), value : block.party, onChange : function( value ) { block.party = value; changed() },
			options : [ 'owner', 'member' ].map( function( party ) { return { value : party, label : w( 'party/'+ party ) } } ) } ) );
		grid.appendChild( field( 'page', 'page', { maxLength : 4, help : w('hint/page') } ) );

		[ [ 'x', T.offer.paper[0] ], [ 'y', T.offer.paper[1] ], [ 'w', T.offer.paper[0] ], [ 'h', T.offer.paper[1] ] ].forEach( function( edge ) {

			const box = field( edge[0], edge[0], { type : 'number', onInput : function( value ) { block[edge[0]] = numeric( value ); changed() } } );
			const input = control( box );

			input.min = '0';
			input.max = String( edge[1] );
			input.step = '0.1';
			grid.appendChild( box );
		} );

		grid.appendChild( flag( 'line', 'line' ) );

		return body;
	};

	// The blocks of the draft as the sections of an accordion, drawn again whenever their number or their order changes
	const drawBlocksOf = function( view, open ) {

		view.clusters = [];

		const accordion = Nino.adminUi.accordion( {
			id : 'pdf-blocks-sections', multiple : true, open : open,
			sections : view.draft.blocks.map( function( block, at ) {
				return { key : String( at ), label : ( at + 1 )+ '. '+ w( 'type/'+ block.type ), summary : summaryOf( block ), content : function() { return blockBody( view, block, at ) } };
			} ),
		} );

		view.accordion = accordion;
		view.blocksMount.innerHTML = '';

		if( view.draft.blocks.length === 0 )
			view.blocksMount.appendChild( Nino.adminUi.emptyState( w('empty/blocks-of') ) );
		else
			view.blocksMount.appendChild( accordion.el );
	};

	const moveBlock = function( view, at, step ) {

		const to = at + step;
		const blocks = view.draft.blocks;

		if( to < 0 || to >= blocks.length )
			return;

		blocks.splice( to, 0, blocks.splice( at, 1 )[0] );
		drawBlocksOf( view, [ String( to ) ] );
		refresh();

		// The focus goes with the block: to the same button of its new place, else to the one that is left of the pair
		const again = view.clusters[to] === undefined ? undefined : view.clusters[to][ step < 0 ? 0 : 1 ];

		if( again !== undefined )
			( again.disabled === true ? view.clusters[to][ step < 0 ? 1 : 0 ] : again ).focus();
	};

	const removeBlockOf = function( view, at ) {

		view.draft.blocks.splice( at, 1 );
		drawBlocksOf( view, [] );
		refresh();
		view.add.focus();
	};

	const addBlock = function( view ) {

		if( view.draft.blocks.length >= T.offer.ranges.blocks ) {
			view.bar.line.fail( Nino.adminUi.format( t('/_admin/error/pdf_blocks_many'), T.offer.ranges.blocks ) );
			return;
		}

		Nino.adminUi.pickDialog( {
			title : w('dialog/add-block'), size : 'wide', cancel : t('/_admin/common/label/cancel'), opener : view.add,
			items : TYPES.map( function( type ) { return { value : type, label : w( 'type/'+ type ), description : w( 'type-hint/'+ type ) } } ),
			onChoose : function( type ) {

				view.draft.blocks.push( fresh( type ) );
				drawBlocksOf( view, [ String( view.draft.blocks.length - 1 ) ] );
				refresh();
			},
		} );
	};

	// What a refusal of the server is about: the field it names, and for a block the section that holds it
	const refuseTemplate = function( view, status, response ) {

		const named = response !== null && typeof response === 'object' ? response.field : '';
		const text = said( status, response, '/_admin/pdf/error/save' );
		let target = null;

		if( named === 'blocks' ) {

			const number = Array.isArray( response.params ) === true ? parseInt( response.params[0], 10 ) : NaN;

			view.blocksError = el( 'p', 'nino-admin-field-error', text );
			view.blocksError.setAttribute( 'role', 'alert' );
			view.blocksMount.parentNode.insertBefore( view.blocksError, view.blocksMount );

			if( Number.isInteger( number ) === true && number >= 1 && number <= view.draft.blocks.length ) {
				drawBlocksOf( view, view.accordion.get().concat( [ String( number - 1 ) ] ) );
				target = view.clusters[ number - 1 ] === undefined ? view.add : view.accordion.body( String( number - 1 ) ).querySelector('input, select, textarea') || view.add;
			}
			else
				target = view.add;
		}
		else if( view.controls[named] !== undefined )
			target = refuse( view.controls[named], text );

		if( target !== null )
			target.focus();
	};

	const saveTemplate = function( done ) {

		const view = T.view;
		const values = templateValues( view );
		const isNew = view.was === '';

		Nino.adminUi.clearFieldErrors( view.form );

		if( view.blocksError !== null && view.blocksError.parentNode !== null )
			view.blocksError.parentNode.removeChild( view.blocksError );

		view.blocksError = null;
		view.bar.line.saving();

		call( 'templates/save', { key : isNew === true ? values.key : view.was, isNew : isNew, name : values.name, tag : values.tag, blocks : values.blocks }, function( status, response ) {

			if( status !== 200 || response === null ) {
				view.bar.line.error( status, response, '/_admin/pdf/error/save' );
				refuseTemplate( view, status, response );
				done( false );
				return;
			}

			takeList( response );

			// The screen is drawn again from what the server holds - its own texts are the cleaned ones - and a new template has its address and its Delete
			call( 'templates/get', { key : response.key }, function( getStatus, got ) {

				if( getStatus === 200 && got !== null ) {
					T.offer = { tags : got.tags, library : got.library, paper : got.paper, ranges : got.ranges };
					showTemplate( { key : got.key, name : got.template.name, tag : got.template.tag, blocks : got.template.blocks }, { focus : false } );
				}

				T.view.bar.line.saved();
				Nino.admin.router.set( 'pdf-templates', [ response.key ] );
				refresh();
				done( true );
			} );
		} );
	};

	const previewTemplate = function() {

		const view = T.view;
		const values = templateValues( view );

		view.bar.line.saving();

		call( 'preview', { name : values.name, tag : values.tag, blocks : values.blocks }, function( status, response ) {

			if( status !== 200 || response === null ) {
				view.bar.line.error( status, response, '/_admin/pdf/error/preview' );
				return;
			}

			showPdf( view.preview, response.pdf, ( values.key !== '' ? values.key : 'preview' )+ '.pdf' );
			view.bar.line.idle( '' );
		} );
	};

	// The template of a key is asked for before its screen is drawn: the list holds its name, its tag and the number of its blocks, no more
	const openTemplate = function( record, focus ) {

		if( record === null ) {

			// A new template is offered what the project has now - the library may have grown in the other tab
			call( 'templates/list', {}, function( status, response ) {

				if( status === 200 && response !== null )
					takeList( response );

				showTemplate( null, focus );
			} );

			return;
		}

		call( 'templates/get', { key : record.key }, function( status, response ) {

			if( status !== 200 || response === null ) {
				Nino.adminUi.showError( dc.getElementById('pdf-templates-list'), status, response, '/_admin/pdf/error/load' );
				Nino.adminUi.showLevel( 'pdf-templates-list', { focus : false } );
				return;
			}

			T.offer = { tags : response.tags, library : response.library, paper : response.paper, ranges : response.ranges };
			showTemplate( { key : response.key, name : response.template.name, tag : response.template.tag, blocks : response.template.blocks }, focus );
		} );
	};

	/**
	 *	One template on its own screen, on a working copy: leaving it unsaved changes nothing that was loaded
	 *
	 *	@param		{Object|null}		template		{ key, name, tag, blocks } as the server read it; null for a new one
	 *	@param		{Object}				[focus]			{ focus : false } where the screen was not opened by the person
	 */
	const showTemplate = function( template, focus ) {

		const mount = dc.getElementById('pdf-templates-edit');
		const draft = { blocks : template === null ? [] : template.blocks.map( held ) };
		const view = { was : template === null ? '' : template.key, draft : draft, controls : {}, form : null, bar : null, saved : '', accordion : null, blocksMount : null, blocksError : null, clusters : [], add : null, preview : null };
		let touched = template !== null;

		dropPdf( T.view === null ? null : T.view.preview );
		T.view = view;
		mount.innerHTML = '';
		mount.appendChild( Nino.adminUi.contextBar( {
			back : { to : w('label/templates'), href : Nino.admin.router.href( 'pdf-templates', [] ), onClick : function() { templates.go( null ) } },
			title : template === null ? w('label/new-template-title') : template.name,
		} ) );

		const form = el( 'form' );
		const about = section( w('label/template') );
		const tags = T.offer.tags.slice();
		const tag = template === null ? '' : template.tag;

		form.noValidate = true;
		view.form = form;

		// A tag the project no longer lists stays what the template has, and is offered for it until another is chosen
		if( tag !== '' && tags.indexOf( tag ) === -1 )
			tags.push( tag );

		view.controls.name = Nino.adminUi.textField( { key : 'name', label : w('label/name'), help : w('hint/template-name'), value : template === null ? '' : template.name, required : true, maxLength : T.offer.ranges.title, id : 'pdf-template-name',
			onInput : function( value ) {

				// The key of a new template follows its name until the key is written by hand
				if( touched === false )
					control( view.controls.key ).value = Nino.slug( value, 40 );
			} } );
		view.controls.key = Nino.adminUi.textField( { key : 'key', label : w('label/key'), help : w('hint/key'), value : template === null ? '' : template.key, required : true, maxLength : 40, id : 'pdf-template-key', onInput : function() { touched = true } } );
		control( view.controls.key ).disabled = template !== null;
		[ view.controls.name, view.controls.key ].forEach( function( field ) { about.grid.appendChild( field ) } );

		// A tag is the project's word and optional: the field is there where the project has tags, or the template carries one
		if( tags.length > 0 ) {
			view.controls.tag = Nino.adminUi.selectField( { key : 'tag', label : w('label/tag'), help : w('hint/tag'), value : tag, options : [ { value : '', label : w('label/no-tag') } ].concat( tags.map( function( one ) { return { value : one, label : one } } ) ) } );
			about.grid.appendChild( view.controls.tag );
		}

		form.appendChild( about.set );

		// The blocks of the document, in their order
		const listed = el( 'div', 'pdf-blocks' );
		const title = Nino.adminUi.sectionTitle( w('label/blocks-title') );

		listed.appendChild( title );
		Nino.adminUi.sectionHelp( title, listed, w('hint/blocks-of') );
		view.blocksMount = el('div');
		listed.appendChild( view.blocksMount );
		view.add = Nino.adminUi.button( w('label/add-block'), function() { addBlock( view ) }, { kind : 'secondary' } );
		listed.appendChild( view.add );
		form.appendChild( listed );

		view.preview = previewBox( w('label/preview') );
		form.appendChild( view.preview );

		const drop = template === null ? undefined : Nino.adminUi.button( w('label/delete-template'), function() { removeTemplate( template, drop ) }, { kind : 'danger' } );

		view.bar = Nino.adminUi.actionBar( {
			danger : drop,
			status : true,
			secondary : [ Nino.adminUi.button( w('label/preview'), previewTemplate, { kind : 'secondary' } ) ],
			primary : Nino.adminUi.button( template === null ? w('label/create-template') : t('/_admin/common/label/save'), null, { type : 'submit', kind : 'primary' } ),
		} );

		form.appendChild( view.bar.el );
		view.bar.line.bind( form );
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); saveTemplate( function() {} ) } );
		mount.appendChild( form );
		drawBlocksOf( view, [] );
		view.saved = templatePrint( view );

		Nino.adminUi.showLevel( 'pdf-templates-edit', focus === undefined || focus === null || focus.focus !== false ? { focus : control( view.controls.name ) } : focus );
	};

	Nino.admin['pdf-templates'] = Nino.adminUi.panel( {
		uri			: 'pdf-templates',
		mount		: 'pdf-templates-list',
		show		: 'always',
		error		: '/_admin/pdf/error/load',
		dirty		: {
			isDirty	: function() { return T.view !== null && templatePrint( T.view ) !== T.view.saved },
			save		: function( done ) { saveTemplate( done ) },
			discard	: function() { if( T.view !== null ) { dropPdf( T.view.preview ); T.view.saved = templatePrint( T.view ) } },
		},
		render	: function( data ) {

			takeList( data );

			if( T.view === null )
				templates.follow( { focus : false } );
		},
	} );

	// The requests of this tab are the panel's own: pdf/templates/<action>
	Nino.admin['pdf-templates'].call = function( action, payload, done, extra ) { call( 'templates/'+ action, payload, done, extra ) };
	Nino.admin['pdf-templates'].showCurrent = function() { templates.showCurrent( Nino.admin['pdf-templates'] ) };

} )(window,document);
