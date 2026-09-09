/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Gallery					The panel of the Gallery feature: the albums a
 *													project has, and one album's images on a screen of
 *													its own - two levels in two panes, stepped through
 *													with the workbench's own back link.
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
		// What an upload will be made into, so the screen can say so rather
		// than let somebody find out after twenty files
		_thumb	: [ 0, 0 ],
		_large	: [ 0, 0 ],
		_keepRatio : true,
		// The album whose screen is open, '' while the list is
		_open		: '',

		/**
		 *	Load the albums and draw whichever level is current
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

				Nino.admin.gallery._take( response );
				Nino.admin.gallery._ready = true;

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	Re-show whichever level is on - the shell calls this when the
		 *	panel is opened again
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.gallery._ready === false )
				return Nino.admin.gallery.init();

			Nino.admin.gallery._render();
		},

		/**
		 *	Take an answer and draw from it. Every action answers the whole
		 *	list, so this is the one path state ever changes on
		 *
		 *	@param		{Object}	response
		 *
		 *	@return		void
		 */
		_take : function( response ) {

			Nino.admin.gallery._albums = response.albums || [];

			if( response.thumb ) Nino.admin.gallery._thumb = response.thumb;
			if( response.large ) Nino.admin.gallery._large = response.large;
			if( response.keepRatio !== undefined ) Nino.admin.gallery._keepRatio = response.keepRatio === true;

			// An album that is gone - deleted here, or by hand in the file -
			// takes its screen with it rather than leaving one about nothing
			if( Nino.admin.gallery._open !== '' && Nino.admin.gallery._album( Nino.admin.gallery._open ) === null )
				Nino.admin.gallery._open = '';

			Nino.admin.gallery._render();
		},

		_render : function() {
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
		 *	Call a gallery/* action. An extra multipart field (a File) is
		 *	handed through the way the Images panel does it
		 *
		 *	@param		{string}		endpoint
		 *	@param		{Object}		payload
		 *	@param		{Function}	callback		Called with ( xhr.status, xhr.responseJSON )
		 *	@param		{Object}		[extra]			Extra multipart fields, eg. { file : File }
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback, extra ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, Object.assign( { action : 'gallery/'+ endpoint, data : JSON.stringify( payload ) }, extra || {} ) );
		},

		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/load') );
			container.appendChild( p );
		},

		/**
		 *	Which of the two panes is on screen
		 *
		 *	@param		{string}	level				'list' or 'album'
		 *
		 *	@return		void
		 */
		_level : function( level ) {
			[ 'list', 'album' ].forEach( function( name ) {
				dc.getElementById('gallery-'+ name ).classList.toggle( 'admin-hidden', name !== level );
			} );
		},

		/**
		 *	The list: one row per album, and the field that adds one
		 *
		 *	@return		void
		 */
		_renderList : function() {

			const wrap = dc.getElementById('gallery-list');
			wrap.innerHTML = '';

			if( Nino.admin.gallery._albums.length === 0 )
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/gallery/hint/empty') ) );
			else {
				const rows = dc.createElement('ul');
				rows.className = 'nino-admin-list';
				Nino.admin.gallery._albums.forEach( function( album ) { rows.appendChild( Nino.admin.gallery._renderRow( album ) ) } );
				wrap.appendChild( rows );
			}

			wrap.appendChild( Nino.admin.gallery._renderNew() );

			Nino.admin.gallery._level('list');
		},

		/**
		 *	One album: its name, its shortcode and how many images it holds,
		 *	and the two things that can be done with it
		 *
		 *	@param		{Object}	album
		 *
		 *	@return		{Element}							<li>
		 */
		_renderRow : function( album ) {

			const row = dc.createElement('li');
			row.dataset.album = album.key;

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

			const actions = dc.createElement('div');
			actions.className = 'gallery-actions';

			const msg = dc.createElement('p');
			msg.className = 'nino-admin-hint';
			msg.setAttribute( 'aria-live', 'polite' );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/gallery/label/delete');
			remove.addEventListener( 'click', function() { Nino.admin.gallery._deleteAlbum( album, remove, msg ) } );
			actions.appendChild( remove );

			const open = dc.createElement('button');
			open.type = 'button';
			open.className = 'nino-admin-btn-primary';
			open.textContent = Nino.content.getText('/_admin/gallery/label/open');
			open.addEventListener( 'click', function() {
				Nino.admin.gallery._open = album.key;
				Nino.admin.gallery._renderAlbum();
			} );
			actions.appendChild( open );

			actions.appendChild( msg );
			row.appendChild( actions );

			return row;
		},

		/**
		 *	The field that adds an album: a key, since that is what the
		 *	shortcode and the image paths are built from, and a name
		 *
		 *	@return		{Element}							<form>
		 */
		_renderNew : function() {

			const form = dc.createElement('form');
			form.className = 'nino-admin-actionbar';

			const key = dc.createElement('input');
			key.type = 'text';
			key.id = 'gallery-new-key';
			key.className = 'nino-admin-input';
			key.placeholder = Nino.content.getText('/_admin/gallery/label/key');
			key.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/key') );
			form.appendChild( key );

			const name = dc.createElement('input');
			name.type = 'text';
			name.id = 'gallery-new-name';
			name.className = 'nino-admin-input';
			name.placeholder = Nino.content.getText('/_admin/gallery/label/name');
			name.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/name') );
			form.appendChild( name );

			const add = dc.createElement('button');
			add.type = 'submit';
			add.className = 'nino-admin-btn-primary';
			add.textContent = Nino.content.getText('/_admin/gallery/label/new');
			form.appendChild( add );

			const msg = dc.createElement('p');
			msg.id = 'gallery-new-msg';
			form.appendChild( msg );

			form.addEventListener( 'submit', function( ev ) {
				ev.preventDefault();
				msg.textContent = Nino.content.getText('/_admin/common/msg/saving');
				Nino.admin.gallery._apiCall( 'album-save', { album : key.value.trim(), name : name.value.trim() }, function( status, response ) {
					if( status !== 200 || response === null ) {
						msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save');
						return;
					}
					Nino.admin.gallery._take( response );
				} );
			} );

			return form;
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
		 *	One album's screen: what an upload will be made into, the field
		 *	that adds one, and the images as a grid
		 *
		 *	@return		void
		 */
		_renderAlbum : function() {

			const wrap	= dc.getElementById('gallery-album');
			const album	= Nino.admin.gallery._album( Nino.admin.gallery._open );

			wrap.innerHTML = '';

			if( album === null )
				return Nino.admin.gallery._renderList();

			const backLink = dc.createElement('a');
			backLink.href = '#';
			backLink.className = 'nino-admin-back-link';
			backLink.textContent = Nino.content.getText('/_admin/common/label/back');
			backLink.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				Nino.admin.gallery._open = '';
				Nino.admin.gallery._renderList();
			} );
			wrap.appendChild( Nino.admin.formToolbar( backLink ) );

			const title = dc.createElement('h3');
			title.textContent = album.name;
			wrap.appendChild( title );

			// Said before the upload, not after: what comes out is two derived
			// sizes and the file that was chosen is not kept
			const sizes = dc.createElement('p');
			sizes.className = 'nino-admin-hint gallery-sizes';
			sizes.textContent = Nino.content.getText( Nino.admin.gallery._keepRatio === true ? '/_admin/gallery/hint/sizes-fit' : '/_admin/gallery/hint/sizes-crop' )
				.replace( '%1', Nino.admin.gallery._thumb.join( '×' ) )
				.replace( '%2', Nino.admin.gallery._large.join( '×' ) );
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

			Nino.admin.gallery._level('album');
		},

		/**
		 *	The file field. It commits the moment a file is chosen, the way
		 *	every image field in the workbench does - there is nothing else to
		 *	decide about an upload
		 *
		 *	@param		{Object}	album
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

			const next = function() {

				if( files.length === 0 ) {
					input.disabled = false;
					input.value = '';
					msg.textContent = Nino.content.getText('/_admin/gallery/msg/uploaded').replace( '%s', done );
					Nino.admin.gallery._renderAlbum();
					return;
				}

				const file = files.shift();
				msg.textContent = Nino.content.getText('/_admin/gallery/msg/uploading').replace( '%s', file.name );

				Nino.admin.gallery._apiCall( 'upload', { album : album.key }, function( status, response ) {

					if( status !== 200 || response === null ) {
						input.disabled = false;
						input.value = '';
						msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/gallery/error/upload');
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
		 *	One image: the thumbnail as it will be seen, its caption, and
		 *	moving it or taking it away
		 *
		 *	@param		{Object}	album
		 *	@param		{Object}	image
		 *	@param		{number}	index
		 *
		 *	@return		{Element}							<li>
		 */
		_renderTile : function( album, image, index ) {

			const tile = dc.createElement('li');
			tile.className = 'gallery-tile';
			tile.dataset.image = image.id;

			const thumb = dc.createElement('img');
			thumb.src = image.thumbUrl;
			thumb.alt = image.caption || '';
			thumb.loading = 'lazy';
			tile.appendChild( thumb );

			const msg = dc.createElement('p');
			msg.className = 'nino-admin-hint gallery-tile-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const caption = dc.createElement('input');
			caption.type = 'text';
			caption.className = 'nino-admin-input';
			caption.value = image.caption || '';
			caption.placeholder = Nino.content.getText('/_admin/gallery/label/caption');
			caption.setAttribute( 'aria-label', Nino.content.getText('/_admin/gallery/label/caption') );
			// On blur rather than on every keystroke: a caption is a sentence,
			// not a slider
			caption.addEventListener( 'blur', function() {
				if( caption.value === ( image.caption || '' ) )
					return;
				Nino.admin.gallery._saveCaption( album, image, caption, msg );
			} );
			tile.appendChild( caption );

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

		_saveCaption : function( album, image, field, msg ) {

			msg.textContent = Nino.content.getText('/_admin/common/msg/saving');

			Nino.admin.gallery._apiCall( 'image-save', { album : album.key, id : image.id, caption : field.value }, function( status, response ) {
				if( status !== 200 || response === null ) {
					msg.textContent = ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/save');
					return;
				}
				Nino.admin.gallery._albums = response.albums || [];
				msg.textContent = Nino.content.getText('/_admin/common/msg/saved');
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
