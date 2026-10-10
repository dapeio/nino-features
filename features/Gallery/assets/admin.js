/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Gallery					The panel of the Gallery feature: the albums a
 *													project has, a new album on a page of its own, and
 *													one album's images on a screen of its own - the
 *													list and those two pages in two panes, stepped
 *													through with the workbench's own back link and
 *													written into the address (#gallery, #gallery/new,
 *													#gallery/<key>). The sizes an album's pictures are
 *													made with are the album's own. An image's alt text
 *													and caption are written per language, with the
 *													switch in the album's toolbar.
 *
 *													Admin/Admin.php beside it does the reading, the
 *													writing and the two image sizes, and hands every
 *													word over already in the session language, so this
 *													file only lays out what it gets. Every action
 *													answers the whole album list, so the panel never
 *													patches its own state from what it just sent.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.gallery = {

		_ready	: false,
		_albums	: [],
		// What a new album starts with - the settings of the feature - and the
		// range each number may take, as the server will hold it to: the form of
		// the sizes is filled from the one and limited by the other
		_defaults : {},
		_ranges : {},
		// The sizes of an album in the order the form draws them, each with the
		// panel's word for it and - where it has one - its hint
		_sizes : [
			{ name : 'thumbWidth', label : '/_admin/gallery/label/thumb-width', hint : '/_admin/gallery/hint/thumb-width' },
			{ name : 'thumbHeight', label : '/_admin/gallery/label/thumb-height', hint : '' },
			{ name : 'largeWidth', label : '/_admin/gallery/label/large-width', hint : '/_admin/gallery/hint/large-width' },
			{ name : 'largeHeight', label : '/_admin/gallery/label/large-height', hint : '' },
			{ name : 'keepRatio', label : '/_admin/gallery/label/keep-ratio', hint : '/_admin/gallery/hint/keep-ratio' },
			{ name : 'columns', label : '/_admin/gallery/label/columns', hint : '/_admin/gallery/hint/columns' },
		],
		// The languages an alt text and a caption can be written in, and the
		// site's own - which is what a picture is shown in where it has none
		// in the current one
		_locales : [],
		_native : '',
		// What php takes in one upload: bytes (0 where it sets no limit) and
		// the same said in words, both worked out server side
		_limits : { bytes : 0, text : '' },
		// How many bytes of an alt text or a caption the server keeps
		// (Gallery::MAX_CAPTION); the answer of gallery/list carries the real one
		_maxText : 300,
		// How many characters the key of a new album may have (Gallery::MAX_KEY),
		// and the answer of gallery/list carries the real one here too
		_maxKey : 64,
		// The album whose screen is open, '' while the list is
		_open		: '',
		// The page of a new album is open, in the place of the list or of an album
		_creating : false,

		/**
		 *	Load the albums and draw whichever level is current - the one the
		 *	address names, where it names this panel
		 *
		 *	@param		{Function}	[then]			Run once the list is back
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('gallery-list');
			if( wrap === null )
				return;

			Nino.admin.gallery._apiCall( 'list', {}, function( status, response ) {
				if( status !== 200 || response === null )
					return Nino.admin.gallery._showError( wrap, status, response );

				// The address is read before anything is drawn: drawing writes it
				Nino.admin.gallery._take( response, Nino.admin.gallery._address() );
				Nino.admin.gallery._ready = true;

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	Re-show whichever level is on - the shell calls this when the
		 *	panel is opened again, and when a step through the browser's history
		 *	has changed the address and nothing else: where the address names
		 *	this panel, the level it names is the one shown. A page that is drawn
		 *	already for that level is shown as it is: the key somebody was typing
		 *	is still there when the panel is opened again
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.gallery._ready === false )
				return Nino.admin.gallery.init();

			const parts = Nino.admin.gallery._address();

			if( parts !== null )
				Nino.admin.gallery._place( parts );

			const level = Nino.admin.gallery._creating === true ? '#new' : Nino.admin.gallery._open;

			if( level !== '' && level === Nino.admin.gallery._drawn() )
				return Nino.admin.gallery._level( 'album', [ level === '#new' ? 'new' : level ] );

			Nino.admin.gallery._render();
		},

		/**
		 *	What the pane of an album's screen holds: '#new' for the page of a new
		 *	album, the key of the album whose page it is, '' for neither - which a
		 *	key cannot be, so the three cannot be mistaken
		 *
		 *	@return		{string}
		 */
		_drawn : function() {

			if( dc.getElementById('gallery-new-form') !== null )
				return '#new';

			const key = dc.getElementById('gallery-album-key');

			return key === null ? '' : key.value;
		},

		/**
		 *	What the address says about this panel: the parts behind #gallery,
		 *	or null where it names another panel - or where the shell has no
		 *	router to ask
		 *
		 *	@return		{Array|null}
		 */
		_address : function() {

			if( typeof Nino.admin.router !== 'object' || typeof Nino.admin.router.current !== 'function' )
				return null;

			const address = Nino.admin.router.current();

			return address.panel === 'gallery' ? address.parts : null;
		},

		/**
		 *	Put the level the parts of an address name in memory, drawing nothing:
		 *	one album's page for the key of an album the list holds - which wins
		 *	over the page of a new album, since a key may be 'new' in a file from
		 *	before album-save refused it - the page of a new album for 'new', and
		 *	the list for anything else
		 *
		 *	@param		{Array}		parts
		 *
		 *	@return		void
		 */
		_place : function( parts ) {

			const name = parts.length === 0 ? '' : String( parts[0] );

			Nino.admin.gallery._open = Nino.admin.gallery._album( name ) !== null ? name : '';
			Nino.admin.gallery._creating = name === 'new' && Nino.admin.gallery._open === '';
		},

		/**
		 *	Take an answer and draw from it. Every action answers the whole
		 *	list, so this is the one path state ever changes on
		 *
		 *	@param		{Object}		response
		 *	@param		{Array|null}	[parts]			The address to follow, once, when the screen is first drawn
		 *
		 *	@return		void
		 */
		_take : function( response, parts ) {

			Nino.admin.gallery._albums = response.albums || [];

			if( response.defaults ) Nino.admin.gallery._defaults = response.defaults;
			if( response.ranges ) Nino.admin.gallery._ranges = response.ranges;
			if( response.locales ) Nino.admin.gallery._locales = response.locales;
			if( response.native ) Nino.admin.gallery._native = response.native;
			if( response.limits ) Nino.admin.gallery._limits = response.limits;
			if( typeof response.maxText === 'number' && response.maxText > 0 ) Nino.admin.gallery._maxText = response.maxText;
			if( typeof response.maxKey === 'number' && response.maxKey > 0 ) Nino.admin.gallery._maxKey = response.maxKey;

			// The language the workbench last worked in, if no panel has set one
			if( response.selectedLocale ) Nino.admin.sessionLocale.init( response.selectedLocale );

			if( parts !== undefined && parts !== null )
				Nino.admin.gallery._place( parts );

			// An album that is gone - deleted here, or by hand in the file -
			// takes its screen with it rather than leaving one about nothing
			if( Nino.admin.gallery._open !== '' && Nino.admin.gallery._album( Nino.admin.gallery._open ) === null )
				Nino.admin.gallery._open = '';

			Nino.admin.gallery._render();
		},

		_render : function() {

			if( Nino.admin.gallery._creating === true )
				return Nino.admin.gallery._renderNew();

			Nino.admin.gallery._open === '' ? Nino.admin.gallery._renderList() : Nino.admin.gallery._renderAlbum();
		},

		/**
		 *	@param		{string}	key
		 *
		 *	@return		{Object|null}
		 */
		_album : function( key ) {
			return Nino.admin.gallery._albums.filter( function( album ) { return album.key === key } )[0] || null;
		},

		/**
		 *	The language the screen edits in: the one the workbench is working
		 *	in, where this project has it - else the site's own
		 *
		 *	@return		{string}
		 */
		_locale : function() {

			const current = Nino.admin.sessionLocale.current;

			return Nino.admin.gallery._locales.indexOf( current ) !== -1 ? current : ( Nino.admin.gallery._native || Nino.admin.gallery._locales[0] || '' );
		},

		/**
		 *	What a stored alt text or caption says in one language, for a
		 *	field to hold: a plain string is every language, a map has an
		 *	entry for the ones it was written in and nothing for the others
		 *
		 *	@param		{string|Object}	value
		 *	@param		{string}				locale
		 *
		 *	@return		{string}
		 */
		_textIn : function( value, locale ) {

			if( typeof value === 'string' )
				return value;

			return ( value && typeof value[locale] === 'string' ) ? value[locale] : '';
		},

		/**
		 *	A text as the server will keep it: trimmed the way php's trim() does
		 *	(the space, tab, line feeds, NUL and vertical tab - not every space
		 *	of Unicode) and cut to _maxText bytes of UTF-8 on a character
		 *	boundary, like Gallery::text(). What a field is compared with on
		 *	blur: a text put in with blanks round it, or longer than the server
		 *	keeps, never equals what comes back, and would be sent again by
		 *	every blur
		 *
		 *	@param		{string}	value
		 *
		 *	@return		{string}
		 */
		_kept : function( value ) {

			const trimmed = String( value ).replace( /^[ \t\n\r\0\x0B]+|[ \t\n\r\0\x0B]+$/g, '' );
			let bytes = 0;
			let kept = '';

			for( const character of trimmed ) {

				const code = character.codePointAt( 0 );
				bytes += code < 0x80 ? 1 : ( code < 0x800 ? 2 : ( code < 0x10000 ? 3 : 4 ) );

				if( bytes > Nino.admin.gallery._maxText )
					break;

				kept += character;
			}

			return kept;
		},

		/**
		 *	What the page will show for one of them in one language - the
		 *	same order Gallery::localized() has: that language, the site's
		 *	own, the first there is
		 *
		 *	@param		{string|Object}	value
		 *	@param		{string}				locale
		 *
		 *	@return		{string}
		 */
		_shownIn : function( value, locale ) {

			if( typeof value === 'string' )
				return value;

			if( !value )
				return '';

			const texts = Object.keys( value ).map( function( key ) { return value[key] } );

			return [ value[locale], value[Nino.admin.gallery._native] ].concat( texts ).filter( function( text ) { return typeof text === 'string' && text !== '' } )[0] || '';
		},

		/**
		 *	Call a gallery/* action. An extra multipart field (a File) is
		 *	handed through the way the Images panel does it
		 *
		 *	The workbench's own request helper posts where this Nino has one -
		 *	it knows the project's directory and what to do when the page has
		 *	outlived its session; the post below is what every panel did before
		 *	it, with the base the asset bundle fills in, because Nino.dir does
		 *	not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		Called with ( xhr.status, xhr.responseJSON )
		 *	@param		{Object}		[extra]			Extra multipart fields, eg. { file : File }
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback, extra ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'gallery/'+ endpoint, payload, callback, extra );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, Object.assign( { action : 'gallery/'+ endpoint, data : JSON.stringify( payload ) }, extra || {} ) );
		},

		/**
		 *	What a failed load says: the server's code in the workbench's
		 *	language, then its own message, then this panel's sentence - where
		 *	this Nino has errorText(). Before it, "(status) message". The
		 *	answers to an edit stay as they are: this panel's server writes
		 *	them in the workbench's language itself (see Admin::_say()) and
		 *	they carry no status number
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key					Fill key of the panel's own sentence
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( key ) );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.gallery._errorText( status, response, '/_admin/common/error/load' );
			container.appendChild( p );
		},

		/**
		 *	The line under an image that says whether its text is saved.
		 *	Where this Nino has Nino.adminUi.status() it is that: "saving",
		 *	"saved at 09:41", or the error state around the sentence the server
		 *	gave. Before it, the same calls write this panel's sentences into
		 *	the paragraph
		 *
		 *	@param		{Element}		msg
		 *
		 *	@return		{Object}						{ saving(), saved(), fail( text ) }
		 */
		_status : function( msg ) {

			if( Nino.adminUi && typeof Nino.adminUi.status === 'function' )
				return Nino.adminUi.status( msg );

			return {
				saving : function() { msg.textContent = Nino.content.getText('/_admin/common/msg/saving') },
				saved	 : function() { msg.textContent = Nino.content.getText('/_admin/common/msg/saved') },
				fail	 : function( text ) { msg.textContent = text },
			};
		},

		/**
		 *	Which of the two panes is on screen, and the address true to it: the
		 *	page of a new album and the page of an album are the one pane
		 *
		 *	@param		{string}	level				'list' or 'album'
		 *	@param		{Array}		parts				What the address says behind #gallery
		 *
		 *	@return		void
		 */
		_level : function( level, parts ) {

			[ 'list', 'album' ].forEach( function( name ) {
				dc.getElementById('gallery-'+ name ).classList.toggle( 'admin-hidden', name !== level );
			} );

			if( typeof Nino.admin.router === 'object' && typeof Nino.admin.router.set === 'function' )
				Nino.admin.router.set( 'gallery', parts );
		},

		/**
		 *	A move the person made - a row opened, a back link, the button that
		 *	adds an album: an entry in the browser's history for Back to return to,
		 *	where the shell keeps one (Nino 1.4). The address a level writes
		 *	itself is then already true
		 *
		 *	@param		{Array}		parts
		 *
		 *	@return		void
		 */
		_go : function( parts ) {

			if( typeof Nino.admin.router === 'object' && typeof Nino.admin.router.go === 'function' )
				Nino.admin.router.go( 'gallery', parts );
		},

		/**
		 *	@param		{string}	key
		 *
		 *	@return		void
		 */
		_openAlbum : function( key ) {

			Nino.admin.gallery._go( [ key ] );
			Nino.admin.gallery._creating = false;
			Nino.admin.gallery._open = key;
			Nino.admin.gallery._renderAlbum();
		},

		/**
		 *	@return		void
		 */
		_openNew : function() {

			Nino.admin.gallery._go( [ 'new' ] );
			Nino.admin.gallery._creating = true;
			Nino.admin.gallery._open = '';
			Nino.admin.gallery._renderNew();
		},

		/**
		 *	@return		void
		 */
		_openList : function() {

			Nino.admin.gallery._go( [] );
			Nino.admin.gallery._creating = false;
			Nino.admin.gallery._open = '';
			Nino.admin.gallery._renderList();
		},

		/**
		 *	The list: one row per album, and below it the button that adds one
		 *
		 *	@return		void
		 */
		_renderList : function() {

			const wrap = dc.getElementById('gallery-list');
			wrap.innerHTML = '';

			if( Nino.admin.gallery._albums.length === 0 )
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/gallery/hint/empty') ) );
			else {
				// The design system's row of buttons - the shape the Features,
				// Routes and Element Types lists have: the whole row opens the
				// album, a chevron says so, and nothing else stands on it
				const rows = dc.createElement('div');
				rows.className = 'nino-admin-list nino-admin-list-buttons';
				Nino.admin.gallery._albums.forEach( function( album ) { rows.appendChild( Nino.admin.gallery._renderRow( album ) ) } );
				wrap.appendChild( rows );
			}

			// Below the list, as the Elements panel puts its New element: the action of the screen
			// is a button in the bar the workbench pins to the bottom, and a page of its own
			const add = dc.createElement('button');
			add.type = 'button';
			add.className = 'nino-admin-btn-primary';
			add.textContent = Nino.content.getText('/_admin/gallery/label/new');
			add.addEventListener( 'click', function() { Nino.admin.gallery._openNew() } );
			wrap.appendChild( Nino.adminUi.listActions( [ add ] ) );

			// The page that was left is let go: a step forward to it draws it anew, as the row does
			dc.getElementById('gallery-album').innerHTML = '';

			Nino.admin.gallery._level( 'list', [] );
		},

		/**
		 *	One album: its name, its shortcode and how many images it holds -
		 *	a button that opens it. Deleting one is done on the album's own
		 *	screen (see _renderAlbum()), where the pictures it would take with
		 *	it are in view; a red button on every row of a list was the one
		 *	thing on it a hand could hit by mistake
		 *
		 *	@param		{Object}	album
		 *
		 *	@return		{Element}							<button>
		 */
		_renderRow : function( album ) {

			const row = dc.createElement('button');
			row.type = 'button';
			row.dataset.album = album.key;
			row.addEventListener( 'click', function() { Nino.admin.gallery._openAlbum( album.key ) } );

			const copy = dc.createElement('div');
			copy.className = 'nino-admin-list-copy';

			const title = dc.createElement('strong');
			title.textContent = album.name;
			copy.appendChild( title );

			const line = dc.createElement('small');
			line.textContent = [
				Nino.content.getText('/_admin/gallery/hint/shortcode').replace( '%s', '[gallery album="'+ album.key+ '"]' ),
				Nino.content.getText('/_admin/gallery/label/count').replace( '%s', album.images.length ),
			].join( ' · ' );
			copy.appendChild( line );

			row.appendChild( copy );

			const chevron = dc.createElement('span');
			chevron.className = 'admin-view-button-chev';
			chevron.setAttribute( 'aria-hidden', 'true' );
			chevron.textContent = '›';
			row.appendChild( chevron );

			return row;
		},

		/**
		 *	The one thing an album's screen can do to the album itself: delete
		 *	it, with its pictures. An action bar under the grid, the way a
		 *	feature's screen carries its Deactivate - and the line it answers
		 *	in beside the button
		 *
		 *	@param		{Object}	album
		 *
		 *	@return		{Element}							<div class="nino-admin-actionbar">
		 */
		_renderAlbumActions : function( album ) {

			const bar = dc.createElement('div');
			bar.className = 'nino-admin-actionbar gallery-album-actions';

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/gallery/label/delete');

			const msg = dc.createElement('p');
			msg.className = 'nino-admin-actionbar-status';
			msg.setAttribute( 'aria-live', 'polite' );

			remove.addEventListener( 'click', function() { Nino.admin.gallery._deleteAlbum( album, remove, msg ) } );
			bar.appendChild( remove );
			bar.appendChild( msg );

			return bar;
		},

		/**
		 *	The back link of both pages, the toolbar's first part: the list
		 *
		 *	@return		{Element}							<a>
		 */
		_renderBack : function() {

			const backLink = dc.createElement('a');
			backLink.href = '#';
			backLink.className = 'nino-admin-back-link';
			backLink.textContent = Nino.content.getText('/_admin/common/label/back');
			backLink.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				Nino.admin.gallery._openList();
			} );

			return backLink;
		},

		/**
		 *	A text field of the design system: its name over the control. A field
		 *	that cannot be left empty gets the mark after its name, drawn for the
		 *	eye and hidden from a screen reader, which is told through aria-required
		 *	on the control instead - the way the Elements panel marks its uri
		 *
		 *	@param		{string}		text				The name of the field
		 *	@param		{Element}		input
		 *	@param		{boolean}		required
		 *
		 *	@return		{Element}							<label>
		 */
		_labelled : function( text, input, required ) {

			const label = dc.createElement('label');
			label.className = 'nino-admin-field';

			const name = dc.createElement('span');
			name.textContent = text;

			if( required === true ) {
				const star = dc.createElement('span');
				star.className = 'nino-admin-required';
				star.setAttribute( 'aria-hidden', 'true' );
				star.textContent = '*';
				name.appendChild( star );
				input.setAttribute( 'aria-required', 'true' );
			}

			label.appendChild( name );
			label.appendChild( input );

			return label;
		},

		/**
		 *	The page of a new album, as the Elements panel has one for a new element:
		 *	a toolbar with the way back, the key - which cannot be left empty - and
		 *	the name, the sizes its pictures will be made with, the upload field switched
		 *	off until there is an album to upload into, and the bar with the button that
		 *	makes it. The album is made by album-save, as a new one; its page opens
		 *	when it is
		 *
		 *	@return		void
		 */
		_renderNew : function() {

			const wrap = dc.getElementById('gallery-album');
			wrap.innerHTML = '';

			wrap.appendChild( Nino.admin.formToolbar( Nino.admin.gallery._renderBack() ) );

			const title = dc.createElement('h3');
			title.textContent = Nino.content.getText('/_admin/gallery/label/new');
			wrap.appendChild( title );

			const form = dc.createElement('form');
			form.id = 'gallery-new-form';

			// A key is a slug, and it is what the shortcode names and the files are
			// filed under - so it is asked for first, and with the reason it cannot be
			// changed afterwards. Not the native required, which takes blanks for a
			// key: the submit below asks for it, trimmed
			const key = dc.createElement('input');
			key.type = 'text';
			key.id = 'gallery-new-key';
			key.className = 'nino-admin-input';
			key.maxLength = Nino.admin.gallery._maxKey;
			form.appendChild( Nino.admin.gallery._labelled( Nino.content.getText('/_admin/gallery/label/key'), key, true ) );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-field-hint';
			hint.textContent = Nino.content.getText('/_admin/gallery/hint/key');
			form.appendChild( hint );

			const name = dc.createElement('input');
			name.type = 'text';
			name.id = 'gallery-new-name';
			name.className = 'nino-admin-input';
			form.appendChild( Nino.admin.gallery._labelled( Nino.content.getText('/_admin/gallery/label/name'), name, false ) );

			// Open: they are part of what is being decided here, and they start as the
			// settings of the feature
			const sizes = Nino.admin.gallery._renderSizes( Nino.admin.gallery._defaults, true );
			form.appendChild( sizes.element );

			form.appendChild( Nino.admin.gallery._renderUpload( null ) );

			const actions = dc.createElement('div');
			actions.className = 'nino-admin-actionbar';

			const create = dc.createElement('button');
			create.type = 'submit';
			create.textContent = Nino.content.getText('/_admin/common/label/create');
			actions.appendChild( create );

			const msg = dc.createElement('p');
			msg.id = 'gallery-new-msg';
			actions.appendChild( msg );
			form.appendChild( actions );

			const line = Nino.admin.gallery._status( msg );

			form.addEventListener( 'submit', function( ev ) {

				ev.preventDefault();

				const album = key.value.trim();

				if( album === '' ) {
					key.setAttribute( 'aria-invalid', 'true' );
					key.addEventListener( 'input', function() { key.removeAttribute('aria-invalid') }, { once : true } );
					key.focus();
					line.fail( Nino.content.getText('/_admin/gallery/error/key') );
					return;
				}

				create.disabled = true;
				line.saving();

				Nino.admin.gallery._apiCall( 'album-save', { album : album, name : name.value.trim(), settings : sizes.read(), isNew : true }, function( status, response ) {

					create.disabled = false;

					if( status !== 200 || response === null ) {
						line.fail( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
						return;
					}

					// The album is made, and its page is the next screen - with the upload on.
					// The address is written over this one: a step back to a page that
					// would make the album a second time is no step to keep
					Nino.admin.gallery._creating = false;
					Nino.admin.gallery._open = album;
					Nino.admin.gallery._take( response );
				} );
			} );

			wrap.appendChild( form );

			Nino.admin.gallery._level( 'album', [ 'new' ] );
		},

		/**
		 *	Delete one album, after asking. Its images go with it - nothing
		 *	else points at them, and that is what the confirmation says
		 *
		 *	@param		{Object}	album
		 *	@param		{Element}	btn
		 *	@param		{Element}	msg
		 *
		 *	@return		void
		 */
		_deleteAlbum : function( album, btn, msg ) {

			if( wn.confirm( Nino.content.getText('/_admin/gallery/confirm/album').replace( '%s', album.name ).replace( '%d', album.images.length ) ) === false )
				return;

			btn.disabled = true;
			msg.textContent = Nino.content.getText('/_admin/common/msg/deleting');

			Nino.admin.gallery._apiCall( 'album-delete', { album : album.key }, function( status, response ) {
				if( status !== 200 || response === null ) {
					btn.disabled = false;
					msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/delete');
					return;
				}
				Nino.admin.gallery._take( response );
			} );
		},

		/**
		 *	One album's screen: its key and its name, the sizes its pictures are
		 *	made with, the field that adds pictures, and the images as a grid
		 *
		 *	@return		void
		 */
		_renderAlbum : function() {

			const wrap	= dc.getElementById('gallery-album');
			const album	= Nino.admin.gallery._album( Nino.admin.gallery._open );

			wrap.innerHTML = '';

			if( album === null )
				return Nino.admin.gallery._renderList();

			const toolbar = Nino.admin.formToolbar( Nino.admin.gallery._renderBack() );

			// A project with one language has nothing to switch
			if( Nino.admin.gallery._locales.length > 1 )
				toolbar.appendChild( Nino.admin.gallery._renderLocaleSelect() );

			wrap.appendChild( toolbar );

			const title = dc.createElement('h3');
			title.textContent = album.name;
			wrap.appendChild( title );

			wrap.appendChild( Nino.admin.gallery._renderName( album, title ) );

			// Said before the upload, not after: what comes out is two derived
			// sizes and the file that was chosen is not kept. The sizes are the
			// album's, and the line follows them when the group above saves new ones
			const sizes = dc.createElement('p');
			sizes.className = 'nino-admin-hint gallery-sizes';
			sizes.textContent = Nino.admin.gallery._sizesHint( album );

			wrap.appendChild( Nino.admin.gallery._renderSizesForm( album, function() { sizes.textContent = Nino.admin.gallery._sizesHint( album ) } ) );
			wrap.appendChild( sizes );

			wrap.appendChild( Nino.admin.gallery._renderUpload( album ) );

			if( album.images.length === 0 )
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/gallery/hint/no-images') ) );
			else {
				const grid = dc.createElement('ul');
				grid.className = 'gallery-grid';
				album.images.forEach( function( image, index ) {
					grid.appendChild( Nino.admin.gallery._renderTile( album, image, index ) );
				} );
				wrap.appendChild( grid );
			}

			wrap.appendChild( Nino.admin.gallery._renderAlbumActions( album ) );
			Nino.admin.gallery._level( 'album', [ album.key ] );
		},

		/**
		 *	What an upload into this album becomes, in words: the sizes of the
		 *	album, and whether the large view keeps the proportions of the picture
		 *
		 *	@param		{Object}	album
		 *
		 *	@return		{string}
		 */
		_sizesHint : function( album ) {

			const settings = album.settings;

			return Nino.content.getText( settings.keepRatio === true ? '/_admin/gallery/hint/sizes-fit' : '/_admin/gallery/hint/sizes-crop' )
				.replace( '%1', settings.thumbWidth+ '×'+ settings.thumbHeight )
				.replace( '%2', settings.largeWidth+ '×'+ settings.largeHeight );
		},

		/**
		 *	The key and the name of an album, on its page. The key is shown and
		 *	never changed - the shortcode names it and the files are filed under
		 *	it - and the name is a field of its own with a Save of its own
		 *
		 *	@param		{Object}	album
		 *	@param		{Element}	title				The heading of the page, which follows the name
		 *
		 *	@return		{Element}							<form>
		 */
		_renderName : function( album, title ) {

			const form = dc.createElement('form');
			form.className = 'gallery-name-form';

			const key = dc.createElement('input');
			key.type = 'text';
			key.id = 'gallery-album-key';
			key.className = 'nino-admin-input';
			key.value = album.key;
			key.readOnly = true;
			form.appendChild( Nino.admin.gallery._labelled( Nino.content.getText('/_admin/gallery/label/key'), key, false ) );

			const shortcode = dc.createElement('p');
			shortcode.className = 'nino-admin-field-hint';
			shortcode.textContent = Nino.content.getText('/_admin/gallery/hint/shortcode').replace( '%s', '[gallery album="'+ album.key+ '"]' );
			form.appendChild( shortcode );

			const name = dc.createElement('input');
			name.type = 'text';
			name.id = 'gallery-album-name';
			name.className = 'nino-admin-input';
			name.value = album.name;
			form.appendChild( Nino.admin.gallery._labelled( Nino.content.getText('/_admin/gallery/label/name'), name, false ) );

			const msg = Nino.admin.gallery._renderSave( form );
			const line = Nino.admin.gallery._status( msg );

			form.addEventListener( 'submit', function( ev ) {

				ev.preventDefault();
				line.saving();

				Nino.admin.gallery._apiCall( 'album-save', { album : album.key, name : name.value.trim() }, function( status, response ) {

					if( status !== 200 || response === null ) {
						line.fail( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
						return;
					}

					Nino.admin.gallery._albums = response.albums || [];

					// What the server kept is what the page says: it keeps the name it had
					// where it was sent an empty one
					const saved = Nino.admin.gallery._album( album.key );

					if( saved !== null ) {
						album.name = saved.name;
						name.value = saved.name;
						title.textContent = saved.name;
					}

					line.saved();
				} );
			} );

			return form;
		},

		/**
		 *	The row a small form of the album's page ends in: its Save, and the
		 *	line it answers in
		 *
		 *	@param		{Element}	parent			Where the row is put
		 *
		 *	@return		{Element}							The line, <p>
		 */
		_renderSave : function( parent ) {

			const row = dc.createElement('div');
			row.className = 'gallery-form-actions';

			const save = dc.createElement('button');
			save.type = 'submit';
			save.textContent = Nino.content.getText('/_admin/common/label/save');
			row.appendChild( save );

			const msg = dc.createElement('p');
			msg.className = 'gallery-form-msg';
			row.appendChild( msg );

			parent.appendChild( row );

			return msg;
		},

		/**
		 *	The sizes of an album as the group they are on its page: folded away, with
		 *	a Save of its own and the word that they apply to pictures uploaded from
		 *	then on
		 *
		 *	@param		{Object}		album
		 *	@param		{Function}	saved				Called when the sizes have been saved
		 *
		 *	@return		{Element}							<form>
		 */
		_renderSizesForm : function( album, saved ) {

			const group = Nino.admin.gallery._renderSizes( album.settings, false );

			const note = dc.createElement('p');
			note.className = 'nino-admin-hint';
			note.textContent = Nino.content.getText('/_admin/gallery/hint/settings');
			group.element.appendChild( note );

			const msg = Nino.admin.gallery._renderSave( group.element );
			const line = Nino.admin.gallery._status( msg );

			const form = dc.createElement('form');
			form.className = 'gallery-sizes-form';
			form.appendChild( group.element );

			form.addEventListener( 'submit', function( ev ) {

				ev.preventDefault();
				line.saving();

				Nino.admin.gallery._apiCall( 'album-save', { album : album.key, settings : group.read() }, function( status, response ) {

					if( status !== 200 || response === null ) {
						line.fail( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
						return;
					}

					Nino.admin.gallery._albums = response.albums || [];

					// The group stays as it is, open and under the cursor; what the page
					// says of the sizes follows what the server holds now
					const now = Nino.admin.gallery._album( album.key );

					if( now !== null )
						album.settings = now.settings;

					saved();
					line.saved();
				} );
			} );

			return form;
		},

		/**
		 *	The sizes an album's pictures are made with, as the group the Features
		 *	panel draws a feature's settings in: a fieldset of numbers, each limited by
		 *	its range, and the switch, under a summary that folds it away
		 *
		 *	@param		{Object}		settings		name => value, the six
		 *	@param		{boolean}		open				Whether the group starts open
		 *
		 *	@return		{Object}							{ element : <details>, read( ) : name => number | boolean }
		 */
		_renderSizes : function( settings, open ) {

			const details = dc.createElement('details');
			details.className = 'gallery-sizes-group';
			details.open = open === true;

			const summary = dc.createElement('summary');
			summary.textContent = Nino.content.getText('/_admin/gallery/label/sizes');
			details.appendChild( summary );

			const fieldset = dc.createElement('fieldset');
			const inputs = {};

			Nino.admin.gallery._sizes.forEach( function( size ) {

				const label = Nino.content.getText( size.label );
				const hint = size.hint === '' ? '' : Nino.content.getText( size.hint );
				const range = Nino.admin.gallery._ranges[size.name] || [];

				const field = size.name === 'keepRatio'
					? Nino.adminUi.switchField( { key : size.name, checked : settings[size.name] === true, label : label, hint : hint } )
					: Nino.adminUi.numberField( { key : size.name, value : settings[size.name], min : range[0], max : range[1], label : label, hint : hint } );

				inputs[size.name] = field.querySelector('input');
				fieldset.appendChild( field );
			} );

			details.appendChild( fieldset );

			return {
				element : details,
				// A number that is none is null, which the server refuses with the others
				read : function() {

					const read = {};

					Nino.admin.gallery._sizes.forEach( function( size ) {

						const number = parseInt( inputs[size.name].value, 10 );

						read[size.name] = size.name === 'keepRatio' ? inputs[size.name].checked === true : ( isNaN( number ) ? null : number );
					} );

					return read;
				},
			};
		},

		/**
		 *	The language switch of an album's toolbar, the one the Elements and
		 *	Text screens have: the choice is the workbench's, so it is still
		 *	there on the next screen that has one
		 *
		 *	@return		{Element}							<select>
		 */
		_renderLocaleSelect : function() {

			const select = dc.createElement('select');
			select.id = 'gallery-locale-select';
			select.className = 'nino-admin-locale-select nino-admin-contextbar-select';
			select.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/locale') );

			Nino.admin.gallery._locales.forEach( function( locale ) {
				const option = dc.createElement('option');
				option.value = locale;
				option.textContent = locale;
				option.selected = ( locale === Nino.admin.gallery._locale() );
				select.appendChild( option );
			} );

			select.addEventListener( 'change', function() {
				Nino.admin.sessionLocale.set( select.value );
				Nino.admin.gallery._renderAlbum();
			} );

			return select;
		},

		/**
		 *	The file field. It commits the moment a file is chosen, the way
		 *	every image field in the workbench does - there is nothing else to
		 *	decide about an upload
		 *
		 *	@param		{Object|null}	album			Where the pictures go - none on the page of a new album, where the field is drawn all the same, switched off
		 *
		 *	@return		{Element}							<div>
		 */
		_renderUpload : function( album ) {

			const wrap = dc.createElement('div');
			wrap.className = 'nino-admin-field';

			const label = dc.createElement('span');
			label.textContent = Nino.content.getText('/_admin/gallery/label/upload');
			wrap.appendChild( label );

			const input = dc.createElement('input');
			input.type = 'file';
			input.id = 'gallery-upload';
			input.accept = 'image/*';
			// Several at once: a gallery is filled from a folder, not one
			// picture at a time
			input.multiple = true;
			wrap.appendChild( input );

			const msg = dc.createElement('p');
			msg.id = 'gallery-upload-msg';
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'aria-live', 'polite' );
			wrap.appendChild( msg );

			// There is no album to upload into until it is made: the field is there to be
			// seen, greyed out, with the word on when it will be on
			if( album === null ) {
				wrap.className += ' gallery-upload-off';
				input.disabled = true;
				input.setAttribute( 'aria-describedby', msg.id );
				msg.textContent = Nino.content.getText('/_admin/gallery/hint/upload-after');
				return wrap;
			}

			input.addEventListener( 'change', function() {
				if( input.files.length === 0 )
					return;
				Nino.admin.gallery._upload( album, Array.prototype.slice.call( input.files ), input, msg );
			} );

			return wrap;
		},

		/**
		 *	Upload the chosen files, one request each and one after the other:
		 *	every answer carries the whole album list, so two in flight would
		 *	race over what the second one saw
		 *
		 *	@param		{Object}	album
		 *	@param		{Array}		files
		 *	@param		{Element}	input
		 *	@param		{Element}	msg
		 *
		 *	@return		void
		 */
		_upload : function( album, files, input, msg ) {

			input.disabled = true;

			let done = 0;

			/*	Drawn first and said afterwards, into the field that drawing
				made: _renderAlbum() empties the screen and builds a new upload
				field with a message of its own, so a word written before it is
				written on an element nobody will see again	*/
			const say = function( text ) {

				Nino.admin.gallery._renderAlbum();

				const said = dc.getElementById('gallery-upload-msg');

				if( said !== null )
					said.textContent = text;
			};

			const next = function() {

				if( files.length === 0 ) {
					input.disabled = false;
					input.value = '';
					say( Nino.content.getText('/_admin/gallery/msg/uploaded').replace( '%s', done ) );
					return;
				}

				const file = files.shift();

				/*	Said before the request, because for a file over post_max_size
					there is no answer worth the name: php drops the whole request,
					the csrf field with it, and what comes back is a refusal that
					names no cause. The limit is the one php reports; the kernel's
					own cap on a picture is explained by the server, afterwards	*/
				if( Nino.admin.gallery._limits.bytes > 0 && file.size > Nino.admin.gallery._limits.bytes ) {
					input.disabled = false;
					input.value = '';
					say( file.name+ ': '+ Nino.content.getText('/_admin/gallery/error/size').replace( '%s', Nino.admin.gallery._limits.text ) );
					return;
				}

				msg.textContent = Nino.content.getText('/_admin/gallery/msg/uploading').replace( '%s', file.name );

				Nino.admin.gallery._apiCall( 'upload', { album : album.key }, function( status, response ) {

					if( status !== 200 || response === null ) {
						input.disabled = false;
						input.value = '';
						/*	Drawn here as well: every file that was answered
							before this one is on the server and in _albums
							already, and a grid still showing the album as it
							stood before the batch says the upload did nothing	*/
						say( file.name+ ': '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/gallery/error/upload') ) );
						return;
					}

					done++;
					Nino.admin.gallery._albums = response.albums || [];
					next();
				}, { file : file } );
			};

			next();
		},

		/**
		 *	One image: the thumbnail as it will be seen, its alt text and its
		 *	caption in the language the screen is on, and moving it or taking
		 *	it away
		 *
		 *	@param		{Object}	album
		 *	@param		{Object}	image
		 *	@param		{number}	index
		 *
		 *	@return		{Element}							<li>
		 */
		_renderTile : function( album, image, index ) {

			const locale = Nino.admin.gallery._locale();

			const tile = dc.createElement('li');
			tile.className = 'gallery-tile';
			tile.dataset.image = image.id;

			// Named the way the page names it: by its alt text, and where it has
			// none by its caption
			const thumb = dc.createElement('img');
			thumb.src = image.thumbUrl;
			thumb.alt = Nino.admin.gallery._shownIn( image.alt, locale ) || Nino.admin.gallery._shownIn( image.caption, locale );
			thumb.loading = 'lazy';
			tile.appendChild( thumb );

			const msg = dc.createElement('p');
			msg.className = 'nino-admin-hint gallery-tile-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			[ 'alt', 'caption' ].forEach( function( name ) {

				const label = Nino.content.getText('/_admin/gallery/label/'+ name);

				const field = dc.createElement('input');
				field.type = 'text';
				field.className = 'nino-admin-input';
				field.dataset.field = name;
				field.value = Nino.admin.gallery._textIn( image[name], locale );
				field.placeholder = label;
				field.setAttribute( 'aria-label', label );
				// On blur rather than on every keystroke: these are sentences,
				// not sliders
				field.addEventListener( 'blur', function() {
					if( Nino.admin.gallery._kept( field.value ) === Nino.admin.gallery._textIn( image[name], locale ) )
						return;
					Nino.admin.gallery._saveText( album, image, name, locale, field, msg );
				} );
				tile.appendChild( field );
			} );

			const actions = dc.createElement('div');
			actions.className = 'gallery-tile-actions';

			const left = dc.createElement('button');
			left.type = 'button';
			left.className = 'nino-admin-btn-secondary';
			left.textContent = '‹';
			left.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/earlier') );
			left.disabled = index === 0;
			left.addEventListener( 'click', function() { Nino.admin.gallery._move( album, index, -1, msg ) } );
			actions.appendChild( left );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/gallery/label/delete');
			remove.addEventListener( 'click', function() { Nino.admin.gallery._deleteImage( album, image, remove, msg ) } );
			actions.appendChild( remove );

			const right = dc.createElement('button');
			right.type = 'button';
			right.className = 'nino-admin-btn-secondary';
			right.textContent = '›';
			right.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/later') );
			right.disabled = index === album.images.length - 1;
			right.addEventListener( 'click', function() { Nino.admin.gallery._move( album, index, 1, msg ) } );
			actions.appendChild( right );

			tile.appendChild( actions );
			tile.appendChild( msg );

			return tile;
		},

		/**
		 *	Save one of an image's two texts in one language - only that one:
		 *	the other is not in the request, so it stays as the server has it
		 *
		 *	@param		{Object}		album
		 *	@param		{Object}		image
		 *	@param		{string}		name				'alt' or 'caption'
		 *	@param		{string}		locale
		 *	@param		{Element}		field
		 *	@param		{Element}		msg
		 *
		 *	@return		void
		 */
		_saveText : function( album, image, name, locale, field, msg ) {

			const line = Nino.admin.gallery._status( msg );
			line.saving();

			const payload = { album : album.key, id : image.id, locale : locale };
			payload[name] = field.value;

			Nino.admin.gallery._apiCall( 'image-save', payload, function( status, response ) {
				if( status !== 200 || response === null ) {
					line.fail( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save') );
					return;
				}
				Nino.admin.gallery._albums = response.albums || [];

				/*	What the next blur is compared against: the tile is not
					rebuilt after a text was saved - the cursor is in the field -
					so the image object it was drawn from is the only record this
					field has of what it has sent. Taken from the answer, not from
					the field: the first translation of a text that was one string
					for every language makes it a map, and the server decides what
					the other languages hold then. Only the text that was saved:
					the other field may have been sent since, and an answer that
					predates it must not take it back. Left at what the screen was
					drawn with, putting a text back to that one read as "nothing
					changed" while the server held what it was sent in between,
					and every other blur sent the same text again	*/
				const saved = ( Nino.admin.gallery._album( album.key ) || { images : [] } ).images.filter( function( one ) { return one.id === image.id } )[0];

				if( saved !== undefined )
					image[name] = saved[name];

				line.saved();
			} );
		},

		/**
		 *	Move one image one place. The whole order is posted, not the
		 *	move: the server checks it against what it has, so a browser
		 *	working from a stale list is refused rather than obeyed
		 *
		 *	@param		{Object}	album
		 *	@param		{number}	index
		 *	@param		{number}	by
		 *	@param		{Element}	msg
		 *
		 *	@return		void
		 */
		_move : function( album, index, by, msg ) {

			const order = album.images.map( function( image ) { return image.id } );
			const to = index + by;

			if( to < 0 || to >= order.length )
				return;

			order.splice( to, 0, order.splice( index, 1 )[0] );

			Nino.admin.gallery._apiCall( 'reorder', { album : album.key, order : order }, function( status, response ) {
				if( status !== 200 || response === null ) {
					msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save');
					return;
				}
				Nino.admin.gallery._take( response );
			} );
		},

		_deleteImage : function( album, image, btn, msg ) {

			if( wn.confirm( Nino.content.getText('/_admin/gallery/confirm/image') ) === false )
				return;

			btn.disabled = true;
			msg.textContent = Nino.content.getText('/_admin/common/msg/deleting');

			Nino.admin.gallery._apiCall( 'image-delete', { album : album.key, id : image.id }, function( status, response ) {
				if( status !== 200 || response === null ) {
					btn.disabled = false;
					msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/delete');
					return;
				}
				Nino.admin.gallery._take( response );
			} );
		},
	};

	Nino.events.bindCallback( 'ready', Nino.admin.gallery.init );

})(window, document, document.documentElement, document.body);
