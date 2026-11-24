/**
 *	Nino features
 *	admin.js		The panel of the Gallery feature: the albums in a table with a search and a
 *							dialog for a new one, and one album as a view of its own with two parts - its
 *							pictures as cards, which are added (several at once), described in the working
 *							language of the workbench, put in another place and deleted at once, and its
 *							name and the sizes its pictures are made with as a form. Built from the blocks
 *							of the workbench: panel(), tabs(), table(), cards(), fileField(), formDialog(),
 *							confirm(), actionBar() (see Modules\Gallery\Admin beside this file).
 *
 *							The addresses are #gallery (the albums), #gallery/gallery/<key> (the pictures of an
 *							album) and #gallery/gallery/<key>/@settings (its name and sizes). An album may be
 *							called settings: that is its key, and @settings is the word of the tab.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// What the last answer of the server holds, and what the screen keeps of its own: the album that is open, its part, what the search holds
	const S = { albums : [], ranges : {}, native : '', maxText : 300, maxKey : 64, maxImages : 200, tab : 'images', open : '', query : '' };

	// What the open album's view holds: the elements that are drawn again when the working language moves
	const V = { bar : null, cards : null, form : null, context : null, tabs : null, saved : '', read : function() { return '' }, save : function( done ) { done( false ) }, busy : false };

	// The key of an album, as the server holds it to (Gallery::KEY_PATTERN)
	const KEY = /^[a-z][a-z0-9-]*$/;

	// The settings of an album that are numbers, in the order the form has them
	const SIZES = [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ];

	// The word of a failed request, whose code says what was wrong and at which field of a dialog
	const FIELD = { gallery_key : 'key', gallery_long : 'key', gallery_taken : 'key', gallery_albums_full : 'key' };

	const albumOf = function( key ) {
		return S.albums.find( function( album ) { return album.key === key } ) ?? null;
	};

	// The language the workbench edits content in: one for the whole workbench, chosen in its rail
	const work = function() {
		return String( ( Nino.admin.workLocale ?? {} ).current ?? S.native );
	};

	// What an alt text or a caption says in one language: nothing where it has no words of it
	const textIn = function( value, locale ) {
		return typeof value === 'string' ? value : ( value !== null && typeof value === 'object' && typeof value[locale] === 'string' ? value[locale] : '' );
	};

	// What names a picture on its card: its caption, else its alt text, in the working language, else in another that has words
	const shownIn = function( value, locale ) {

		if( typeof value === 'string' )
			return value;

		const map = value !== null && typeof value === 'object' ? value : {};
		const first = Object.keys( map ).find( function( code ) { return typeof map[code] === 'string' && map[code] !== '' } );

		return textIn( map, locale ) || textIn( map, S.native ) || ( first === undefined ? '' : map[first] );
	};

	const nameOf = function( image, index ) {
		const locale = work();
		return shownIn( image.caption, locale ) || shownIn( image.alt, locale ) || Nino.adminUi.format( t('/_admin/gallery/label/picture'), index + 1 );
	};

	const countText = function( album ) {
		return Nino.adminUi.format( t( album.images.length === 1 ? '/_admin/gallery/label/count-one' : '/_admin/gallery/label/count' ), album.images.length );
	};

	// The parts of an address: the key of an album, and the word of its settings where that part is open
	const partsOf = function( key, tab ) {
		return tab === 'settings' ? [ key, Nino.admin.router.word('settings') ] : [ key ];
	};

	// A move of the person inside the panel: the address is a new entry, and the screen follows it
	const go = function( parts ) {
		Nino.admin.router.go( 'gallery', parts );
		Nino.admin.gallery.follow( true );
	};

	// What a deletion leaves the focus on: the next of the entries, else the one before, else the sentence that says there are none
	const focusAround = function( root, keys, gone ) {

		const at = keys.indexOf( gone );
		const around = keys.filter( function( key ) { return key !== gone } );
		const key = around[ Math.min( at, around.length - 1 ) ];
		const target = key === undefined ? root.querySelector('.nino-admin-empty') : root.querySelector( '[data-row="'+ CSS.escape( key ) +'"] button, [data-key="'+ CSS.escape( key ) +'"] button' );

		if( target === null )
			return;

		if( target.hasAttribute('tabindex') === false && target.tagName !== 'BUTTON' )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	// What gets the focus back when a dialog closes: the dialog gives it back after the screen was drawn again, so the control that opened it may be gone. The answer is
	// a place that is looked for then - the card of a picture
	const returnTo = function( find ) {
		return { focus : function() { const target = find(); if( target !== null ) target.focus() } };
	};

	// --- an album is made, an album is deleted --------------------------------------------------------------------------------------

	const newAlbum = function( opener ) {

		Nino.adminUi.formDialog( {
			title		: t('/_admin/gallery/dialog/create'),
			fields	: [
				{ key : 'key', label : t('/_admin/gallery/label/key'), help : t('/_admin/gallery/hint/key'), required : true, maxLength : S.maxKey, autocomplete : 'off' },
				{ key : 'name', label : t('/_admin/gallery/label/name'), help : t('/_admin/gallery/hint/name'), maxLength : 120 },
			],
			submit	: t('/_admin/gallery/label/create'),
			cancel	: t('/_admin/common/label/cancel'),
			// Back to the button where the person cancels; where the album was made, on the heading of its view: the button is in a level that is hidden by then
			opener	: returnTo( function() {

				const heading = S.open === '' || V.context === null ? null : V.context.querySelector('.nino-admin-contextbar-title');

				if( heading !== null )
					heading.setAttribute( 'tabindex', '-1' );

				return heading ?? opener;
			} ),
			onSubmit : function( values, ctl ) {

				const key = String( values.key ).trim();

				if( KEY.test( key ) === false || key.length > S.maxKey )
					return ctl.fail( 'key', t('/_admin/gallery/error/key-invalid') );

				Nino.admin.gallery.call( 'album-save', { album : key, name : String( values.name ).trim(), isNew : true }, function( status, response ) {

					if( status !== 200 || response === null ) {
						ctl.fail( FIELD[ ( response ?? {} ).code ] ?? 'key', Nino.adminUi.api.errorText( status, response, '/_admin/gallery/error/save' ) );
						return;
					}

					S.albums = response.albums || [];
					ctl.close();
					Nino.adminUi.say( t('/_admin/gallery/msg/created') );
					go( [ key ] );
				} );
			},
		} );
	};

	const removeAlbum = function( album, opener, done ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: t('/_admin/gallery/confirm/album-title'),
			message	: Nino.adminUi.format( t('/_admin/gallery/confirm/album'), album.name ),
			details	: [ countText( album ) ],
			yes			: t('/_admin/gallery/confirm/album-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.gallery.call( 'album-delete', { album : album.key }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( dc.getElementById( S.open === '' ? 'gallery-list' : 'gallery-album' ), status, response, '/_admin/gallery/error/save' );

					const keys = S.albums.map( function( row ) { return row.key } );

					S.albums = response.albums || [];
					done( keys );
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// --- the albums ------------------------------------------------------------------------------------------------------------------

	const nameCell = function( value, row ) {

		const cell = el( 'span', 'gallery-admin-name', value );

		cell.appendChild( el( 'br' ) );
		cell.appendChild( el( 'code', '', row.key ) );

		return cell;
	};

	const drawList = function() {

		const mount = dc.getElementById('gallery-list');
		const body = el( 'div', 'gallery-admin-list' );
		const table = el( 'div' );

		mount.innerHTML = '';
		body.appendChild( table );

		if( S.albums.length === 0 )
			table.appendChild( Nino.adminUi.emptyState( t('/_admin/gallery/empty/albums') ) );
		else
			Nino.adminUi.table( {
				mount		: table,
				rowKey	: 'key',
				search	: true,
				query		: S.query,
				onQuery	: function( value ) { S.query = value },
				rows		: S.albums.map( function( album ) { return { key : album.key, name : album.name, count : album.images.length } } ),
				labels	: { search : t('/_admin/gallery/label/search'), empty : t('/_admin/gallery/empty/albums'), noMatch : t('/_admin/gallery/empty/nomatch') },
				columns	: [
					{ key : 'name', label : t('/_admin/gallery/label/name'), type : 'string', render : nameCell },
					{ key : 'count', label : t('/_admin/gallery/label/images'), type : 'integer' },
				],
				actions	: function( row ) {

					const open = Nino.adminUi.button( t('/_admin/gallery/label/open'), function() { go( [ row.key ] ) }, { small : true } );
					const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() {

						removeAlbum( albumOf( row.key ), drop, function( keys ) {
							drawList();
							focusAround( mount, keys, row.key );
						} );
					}, { small : true, kind : 'danger' } );

					return [ open, drop ];
				},
				onRowClick : function( row ) { go( [ row.key ] ) },
			} );

		const make = Nino.adminUi.button( t('/_admin/gallery/label/new'), function() { newAlbum( make ) }, { kind : 'primary' } );

		body.appendChild( Nino.adminUi.actionBar( { list : true, primary : make } ).el );
		mount.appendChild( body );
	};

	// --- one picture: its words and its place, and its deletion ----------------------------------------------------------

	const editImage = function( album, image ) {

		const locale = work();
		const position = album.images.indexOf( image );

		Nino.adminUi.formDialog( {
			title		: t('/_admin/gallery/dialog/edit'),
			message	: Nino.adminUi.format( t('/_admin/gallery/msg/in-language'), Nino.adminUi.localeName( locale ) ),
			fields	: [
				{ key : 'caption', label : t('/_admin/gallery/label/caption'), help : t('/_admin/gallery/hint/caption'), value : textIn( image.caption, locale ), maxLength : S.maxText },
				{ key : 'alt', label : t('/_admin/gallery/label/alt'), help : t('/_admin/gallery/hint/alt'), value : textIn( image.alt, locale ), maxLength : S.maxText },
				{ key : 'position', label : t('/_admin/gallery/label/position'), help : t('/_admin/gallery/hint/position'), type : 'select', value : String( position + 1 ),
					options : album.images.map( function( other, at ) { return { value : String( at + 1 ), label : String( at + 1 ) } } ) },
			],
			submit	: t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: returnTo( function() { return V.cards === null ? null : V.cards.querySelector( '[data-key="'+ CSS.escape( image.id ) +'"] button' ) } ),
			onSubmit : function( values, ctl ) {

				const words = {};
				const moved = parseInt( values.position, 10 ) - 1;

				[ 'caption', 'alt' ].forEach( function( name ) {
					if( values[name] !== textIn( image[name], locale ) )
						words[name] = values[name];
				} );

				const finish = function( response ) {

					S.albums = response.albums || S.albums;
					ctl.close();
					drawImages();
					Nino.adminUi.say( t('/_admin/common/msg/saved') );
				};

				// The order is the second request: the words are in the file by then, and it names every picture as the server has it now
				const reorder = function( response ) {

					if( moved === position || moved < 0 || moved >= album.images.length )
						return finish( response );

					const ids = ( ( response.albums || [] ).find( function( row ) { return row.key === album.key } ) ?? album ).images.map( function( row ) { return row.id } );

					ids.splice( moved, 0, ids.splice( ids.indexOf( image.id ), 1 )[0] );

					Nino.admin.gallery.call( 'reorder', { album : album.key, order : ids }, function( status, answer ) {

						if( status !== 200 || answer === null )
							return ctl.fail( 'position', Nino.adminUi.api.errorText( status, answer, '/_admin/gallery/error/save' ) );

						finish( answer );
					} );
				};

				if( Object.keys( words ).length === 0 )
					return reorder( { albums : S.albums } );

				Nino.admin.gallery.call( 'image-save', Object.assign( { album : album.key, id : image.id, locale : locale }, words ), function( status, response ) {

					if( status !== 200 || response === null )
						return ctl.fail( 'caption', Nino.adminUi.api.errorText( status, response, '/_admin/gallery/error/save' ) );

					reorder( response );
				} );
			},
		} );
	};

	const removeImage = function( album, image, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: t('/_admin/gallery/confirm/image-title'),
			message	: t('/_admin/gallery/confirm/image'),
			yes			: t('/_admin/gallery/confirm/image-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.gallery.call( 'image-delete', { album : album.key, id : image.id }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( V.cards, status, response, '/_admin/gallery/error/save' );

					const keys = album.images.map( function( row ) { return row.id } );

					S.albums = response.albums || [];
					drawImages();
					focusAround( V.cards.parentNode, keys, image.id );
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// The cards of the open album, drawn again where they stand: after an upload, a save, a move, a deletion and a change of the working language
	const drawImages = function() {

		const album = albumOf( S.open );

		if( V.cards === null || album === null )
			return;

		const fresh = el( 'div', 'gallery-admin-cards' );

		if( album.images.length === 0 )
			fresh.appendChild( Nino.adminUi.emptyState( t('/_admin/gallery/empty/images') ) );
		else
			fresh.appendChild( Nino.adminUi.cards( album.images.map( function( image, index ) {

				const name = nameOf( image, index );
				const picture = el( 'img', 'gallery-admin-picture' );

				picture.src = image.thumbUrl;
				picture.alt = name;
				picture.loading = 'lazy';

				const edit = Nino.adminUi.button( t('/_admin/gallery/label/edit'), function() { editImage( album, image ) }, { small : true } );
				const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { removeImage( album, image, drop ) }, { small : true, kind : 'danger' } );

				return { key : image.id, title : name, picture : picture, badges : [ String( index + 1 ) ], actions : [ edit, drop ] };
			} ), { label : t('/_admin/gallery/label/images') } ) );

		V.cards.replaceWith( fresh );
		V.cards = fresh;
	};

	// --- the pictures of an album: the upload and the cards -----------------------------------------------------------------

	// What an upload is held to and made into, said once and behind the (?) of the field
	const sizesHelp = function( album ) {

		const set = album.settings;

		return Nino.adminUi.format( t( set.keepRatio === true ? '/_admin/gallery/hint/sizes-fit' : '/_admin/gallery/hint/sizes-crop' ), set.thumbWidth+ ' × '+ set.thumbHeight, set.largeWidth+ ' × '+ set.largeHeight );
	};

	const uploadNote = function( album ) {

		const limits = Nino.adminUi.limits();

		return ( limits.bytes > 0 && limits.pixels > 0 ? Nino.adminUi.format( t('/_admin/common/hint/upload'), Nino.adminUi.megabytes( limits.bytes ), limits.pixels / 1000000 )+ ' ' : '' )
			+ Nino.adminUi.format( t('/_admin/gallery/note/upload'), album.images.length, S.maxImages );
	};

	// The files one after the other, one request each: every answer holds the whole list of albums, so two in flight would race over what the second one saw
	const upload = function( album, files, field, progress ) {

		let done = 0;

		V.busy = true;
		field.button.disabled = true;
		field.el.setAttribute( 'aria-busy', 'true' );

		const end = function() {

			V.busy = false;
			field.button.disabled = false;
			field.el.removeAttribute('aria-busy');
			progress.textContent = '';
			drawImages();

			if( done > 0 )
				Nino.adminUi.say( Nino.adminUi.format( t( done === 1 ? '/_admin/gallery/msg/uploaded-one' : '/_admin/gallery/msg/uploaded' ), done ) );
		};

		// A refusal is said at the field, with the name of the file it is about, and stops the rest: they would meet the same wall
		const refuse = function( file, text ) {
			end();
			field.error( file.name+ ': '+ text );
			field.button.focus();
		};

		const next = function() {

			if( files.length === 0 )
				return end();

			const file = files.shift();

			progress.textContent = Nino.adminUi.format( t('/_admin/gallery/msg/uploading'), file.name );

			// What cannot work is found out here, in words, before megabytes are sent; the server stays the authority
			Nino.adminUi.checkImage( file, function( problem ) {

				if( problem !== null )
					return refuse( file, Nino.adminUi.api.errorText( 400, problem, '/_admin/gallery/error/upload' ) );

				Nino.admin.gallery.call( 'upload', { album : album.key }, function( status, response ) {

					if( status !== 200 || response === null )
						return refuse( file, Nino.adminUi.api.errorText( status, response, '/_admin/error/gallery_upload' ) );

					done++;
					S.albums = response.albums || [];
					album = albumOf( album.key ) ?? album;
					next();
				}, { file : file } );
			} );
		};

		next();
	};

	const imagesPane = function( album ) {

		const pane = el( 'div', 'gallery-admin-images' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );
		const progress = el( 'p', 'nino-admin-hint' );

		progress.setAttribute( 'role', 'status' );

		const field = Nino.adminUi.fileField( {
			key			: 'upload',
			label		: t('/_admin/gallery/label/upload'),
			help		: sizesHelp( album ),
			note		: uploadNote( album ),
			accept	: 'image/jpeg,image/png,image/gif,image/webp',
			maxBytes: Nino.adminUi.limits().bytes,
			button	: t('/_admin/gallery/label/choose'),
		} );

		// Several pictures at once: the field takes one file, the input behind it holds them all, and each is sent on its own
		field.input.multiple = true;
		field.input.addEventListener( 'change', function() {

			const files = Array.from( field.input.files ?? [] );

			if( files.length === 0 || V.busy === true )
				return;

			field.clear();

			if( album.images.length + files.length > S.maxImages )
				return field.error( Nino.adminUi.format( t('/_admin/error/gallery_images_full'), S.maxImages ) );

			upload( album, files, field, progress );
		} );

		grid.appendChild( field.el );
		pane.appendChild( grid );
		pane.appendChild( progress );

		V.cards = el( 'div' );
		pane.appendChild( V.cards );

		return pane;
	};

	// --- an album: its name and the sizes its pictures are made with ---------------------------------------------------

	const settingsPane = function( album ) {

		const pane = el( 'div', 'gallery-admin-settings' );
		const form = el( 'form' );
		const fields = {};

		form.noValidate = true;

		const about = el( 'fieldset' );
		const aboutGrid = el( 'div', 'nino-admin-fieldgrid' );

		about.appendChild( el( 'legend', '', t('/_admin/gallery/label/album') ) );
		about.appendChild( aboutGrid );

		fields.name = Nino.adminUi.textField( { key : 'name', label : t('/_admin/gallery/label/name'), help : t('/_admin/gallery/hint/name'), value : album.name, maxLength : 120, id : 'gallery-name' } );
		fields.name.querySelector('input').name = 'name';
		aboutGrid.appendChild( fields.name );

		const sizes = el( 'fieldset' );
		const sizesGrid = el( 'div', 'nino-admin-fieldgrid' );
		const legend = el( 'legend', '', t('/_admin/gallery/label/sizes') );

		sizes.appendChild( legend );
		sizes.appendChild( sizesGrid );
		Nino.adminUi.sectionHelp( legend, sizes, t('/_admin/gallery/hint/settings') );

		const labels = { thumbWidth : 'thumb-width', thumbHeight : 'thumb-height', largeWidth : 'large-width', largeHeight : 'large-height', columns : 'columns' };

		SIZES.forEach( function( name ) {

			const range = S.ranges[name] ?? [ undefined, undefined ];

			fields[name] = Nino.adminUi.numberField( { key : name, value : album.settings[name], min : range[0], max : range[1], label : t( '/_admin/gallery/label/'+ labels[name] ), help : t( '/_admin/gallery/hint/'+ labels[name] ) } );
			// The name the server says a refusal is about: the line of the bar marks the field that has it and puts the focus there
			fields[name].querySelector('input').name = name;
			sizesGrid.appendChild( fields[name] );

			// The ratio goes after the sizes of the large view, as the settings of the feature have it
			if( name === 'largeHeight' ) {
				fields.keepRatio = Nino.adminUi.switchField( { key : 'keepRatio', checked : album.settings.keepRatio === true, label : t('/_admin/gallery/label/keep-ratio'), help : t('/_admin/gallery/hint/keep-ratio'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );
				fields.keepRatio.querySelector('input').name = 'keepRatio';
				sizesGrid.appendChild( fields.keepRatio );
			}
		} );

		const read = function() {

			const values = { name : fields.name.querySelector('input').value, settings : { keepRatio : fields.keepRatio.querySelector('input').checked } };

			SIZES.forEach( function( name ) { values.settings[name] = parseInt( fields[name].querySelector('input').value, 10 ) } );

			return values;
		};

		const drop = Nino.adminUi.button( t('/_admin/gallery/label/delete'), function() {

			removeAlbum( album, drop, function() {
				V.form = null;
				Nino.admin.router.go( 'gallery', [] );
				Nino.admin.gallery.follow( true );
			} );
		}, { kind : 'danger' } );

		V.bar = Nino.adminUi.actionBar( { danger : drop, status : true, primary : Nino.adminUi.button( t('/_admin/common/label/save'), null, { type : 'submit' } ) } );

		form.appendChild( about );
		form.appendChild( sizes );
		form.appendChild( V.bar.el );
		V.bar.line.bind( form );
		V.form = form;
		V.read = function() { return JSON.stringify( read() ) };
		V.saved = V.read();

		// Saving model A of the guide: a form, Save in the bar, the answer as the badge and a toast
		V.save = function( done ) {

			Nino.adminUi.clearFieldErrors( form );
			V.bar.line.saving();

			const values = read();

			Nino.admin.gallery.call( 'album-save', { album : album.key, name : values.name, settings : values.settings }, function( status, response ) {

				if( status !== 200 || response === null ) {
					V.bar.line.error( status, response, '/_admin/gallery/error/save' );
					done( false );
					return;
				}

				S.albums = response.albums || [];
				album = albumOf( album.key ) ?? album;

				// The view stays as it is: the name in the bar of the context and what the pictures are made with are the saved ones now
				const title = V.context.querySelector('.nino-admin-contextbar-title');

				if( title !== null )
					title.textContent = title.title = album.name;

				V.saved = V.read();
				V.bar.line.saved();

				if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty !== null )
					Nino.admin.dirty.refresh();

				done( true );
			} );
		};

		form.addEventListener( 'submit', function( event ) { event.preventDefault(); V.save( function() {} ) } );
		pane.appendChild( form );

		return pane;
	};

	// --- the screen ---------------------------------------------------------------------------------------------------------------------

	const drawAlbum = function( key ) {

		const album = albumOf( key );
		const mount = dc.getElementById('gallery-album');

		if( album === null )
			return;

		S.open = key;
		V.form = null;
		V.cards = null;

		const view = el( 'div' );
		const context = Nino.adminUi.contextBar( {
			back	: { to : mount, href : Nino.admin.router.href( 'gallery', [] ), onClick : function() { go( [] ) } },
			title	: album.name,
			code	: album.key,
		} );
		const images = imagesPane( album );
		const settings = settingsPane( album );

		view.appendChild( context );
		Nino.adminUi.localeBadge( work(), context );

		const tabs = Nino.adminUi.tabs( [
			{ key : 'images', label : t('/_admin/gallery/tab/images'), panel : images },
			{ key : 'settings', label : t('/_admin/gallery/tab/settings'), panel : settings },
		], {
			active	: S.tab,
			id			: 'gallery-tabs',
			label		: t('/_admin/gallery/label/parts'),
			onSelect	: function( tab ) { S.tab = tab; Nino.admin.router.go( 'gallery', partsOf( key, tab ) ) },
		} );

		view.appendChild( tabs.el );
		view.appendChild( images );
		view.appendChild( settings );

		mount.innerHTML = '';
		mount.appendChild( view );
		V.context = context;
		V.tabs = tabs;
		drawImages();
	};

	Nino.admin.gallery = Nino.adminUi.panel( {
		uri		: 'gallery',
		mount	: 'gallery-list',
		show	: 'always',
		dirty	: {
			isDirty	: function() { return V.form !== null && V.form.isConnected === true && V.read() !== V.saved },
			save		: function( done ) { V.save( done ) },
			discard	: function() { V.form = null },
		},
		render	: function( data ) {

			S.albums = data.albums || [];
			S.ranges = data.ranges || {};
			S.native = data.native || '';
			S.maxText = data.maxText || S.maxText;
			S.maxKey = data.maxKey || S.maxKey;
			S.maxImages = data.maxImages || S.maxImages;

			Nino.admin.gallery.follow( false );
		},
	} );

	Object.assign( Nino.admin.gallery, {

		/**
		 *	Show what the address names: an album with the part it names, or the albums
		 *
		 *	@param		{boolean}		moved			The person moved here (a click, the back link): the focus goes to the heading of the level
		 *
		 *	@return		void
		 */
		follow : function( moved ) {

			const at = Nino.admin.router.current();
			const parts = at.panel === 'gallery' ? at.parts : [];
			const key = parts[0] ?? '';
			const options = moved === true ? {} : { focus : false };

			if( key !== '' && albumOf( key ) !== null ) {

				S.tab = Nino.admin.router.isWord( parts, 1, 'settings' ) === true ? 'settings' : 'images';
				drawAlbum( key );
				Nino.adminUi.showLevel( 'gallery-album', options );
				Nino.admin.router.set( 'gallery', partsOf( key, S.tab ) );
				return;
			}

			S.open = '';
			V.form = null;
			V.cards = null;
			drawList();
			Nino.adminUi.showLevel( 'gallery-list', options );
			Nino.admin.router.set( 'gallery', [] );
		},

		/**
		 *	The address moved: a move inside the album that is open - to its other part - turns the part and leaves the form as it is, with what
		 *	was typed in it; a move that leaves the album asks first where the form holds input nobody has saved
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			const router = Nino.admin.router;
			const at = router.current();
			const parts = at.panel === 'gallery' ? at.parts : [];

			if( Nino.admin.gallery.ready === false ) {
				Nino.admin.gallery.init();
				return;
			}

			if( at.panel === 'gallery' && S.open !== '' && parts[0] === S.open && V.form !== null && V.form.isConnected === true ) {
				S.tab = router.isWord( parts, 1, 'settings' ) === true ? 'settings' : 'images';
				V.tabs.select( S.tab );
				router.set( 'gallery', partsOf( S.open, S.tab ) );
				return;
			}

			router.leave( [ 'gallery' ], at.panel === 'gallery' && S.open !== '', function() { Nino.admin.gallery.init() }, function() {
				router.set( 'gallery', S.open === '' ? [] : partsOf( S.open, S.tab ) );
			} );
		},
	} );

	// The pictures are named in the working language: they are drawn again when it moves. The settings of the album are no text of a language, and what is typed in them stays
	if( typeof Nino.admin.workLocale === 'object' && Nino.admin.workLocale !== null && typeof Nino.admin.workLocale.follow === 'function' )
		Nino.admin.workLocale.follow( null, function( code ) {

			if( S.open === '' || V.cards === null )
				return;

			Nino.adminUi.localeBadge( code, V.context );
			drawImages();
		} );

} )( window, document );
