/**
 *	Nino - Files
 *	files.js	The script of the upload form [files-upload] on the site. A form with
 *						the class files-upload posts to /.files/upload and works without this
 *						script, for one file at a time; with it, the submit is taken over:
 *						every file is cut into chunks of data-files-chunk bytes and posted in
 *						order with the fields of the form (folder, the csrf token) and the
 *						ones of the upload (name, size, upload, index and count), so that a
 *						file is not bound by the post size of the server. A web server that
 *						takes less than that (its own limit, which php does not see) answers
 *						a chunk with 413: the file then starts again as a new upload in chunks
 *						of half the size, down to 128 KiB, and the size that works is kept
 *						for the files after it. Each file has a row
 *						in the list of the form with its progress and, when it is done or
 *						refused, the word of the answer: a refusal names its code, and the
 *						word of the code is one of the fills the Jstext module hands the
 *						script (/feature/files/state/ and /feature/files/script/, which the
 *						class publishes, as the kernel's Jstext module writes them: text, never entities). The files the person chose stand in a list of their
 *						own before anything is sent (their name and size, and a button to
 *						take one out of the selection - only the selection, nothing is
 *						uploaded or deleted by it); a list the form does not have is no
 *						reason to leave the form alone. A file over data-files-max is refused before a byte
 *						is sent. When every file is in, the page is loaded again with
 *						?files=sent, so that the state line says what happened and a browser
 *						component on the page lists the new files.
 *
 *						Bundled into the project's script bundle by the manifest's assets, so
 *						it is a static file that is never rendered through the fill engine:
 *						every word is asked for with Nino.content.getText(), and every value
 *						that comes from the page or from a file is set as text. It needs
 *						nothing of the kernel's script but Nino.http, Nino.content, Nino.events
 *						and Nino.dir - nothing of Basis or of the kernel's interface scripts.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc) {

	'use strict';

	/**
	 *	What the script reads off the form: data-files-chunk and data-files-max, whole numbers of bytes, and data-files-multiple, a flag
	 *
	 *	@param		{Element}	element
	 *
	 *	@return		{Object}									{ chunk, max, multiple }
	 */
	const options = function( element ) {

		const number = function( name ) {
			const value = parseInt( element.dataset[name], 10 );
			return Number.isInteger( value ) === true && value > 0 ? value : 0;
		};

		return { chunk : number('filesChunk'), max : number('filesMax'), multiple : element.dataset.filesMultiple === '1' };
	};

	// The smallest chunk a file is cut into when the web server refuses a request with 413: 128 KiB
	const FLOOR = 131072;

	/**
	 *	The chunk an earlier upload of this tab found the web server takes, kept over the reload that ends a submit. Storage may be missing or full:
	 *	then nothing is kept and the next submit starts at the setting
	 *
	 *	@param		{string}	key
	 *
	 *	@return		{number}									The bytes; 0 for none, or for a value that is none a chunk can be
	 */
	const kept = function( key ) {

		try {
			const value = parseInt( wn.sessionStorage.getItem( key ), 10 );
			return Number.isInteger( value ) === true && value >= FLOOR ? value : 0;
		}
		catch( e ) {
			return 0;
		}
	};

	/**
	 *	@param		{string}	key
	 *	@param		{number}	chunk
	 *
	 *	@return		{void}
	 */
	const keep = function( key, chunk ) {

		try {
			wn.sessionStorage.setItem( key, String( chunk ) );
		}
		catch( e ) {
			// Storage is not there: the next submit starts at the setting again
		}
	};

	// A code of a refusal is files_ and a word; its sentence is /feature/files/state/<word>
	const CODE = /^files_([a-z]+)$/;

	/**
	 *	The chunks a file of a size is cut into: every one but the last is exactly a chunk long. Pure
	 *
	 *	@param		{number}	size						The bytes of the file
	 *	@param		{number}	chunk						The bytes of a chunk
	 *
	 *	@return		{Array}										[ [ start, end ], ... ], end not included - none for a size or a chunk that is no whole number above 0
	 */
	const chunks = function( size, chunk ) {

		const parts = [];

		if( Number.isInteger( size ) === false || Number.isInteger( chunk ) === false || size < 1 || chunk < 1 )
			return parts;

		for( let start = 0; start < size; start += chunk )
			parts.push( [ start, Math.min( start + chunk, size ) ] );

		return parts;
	};

	/**
	 *	The chunk to try after a request of the chunk's size was answered 413 (the web server takes no request so large): half of it,
	 *	not below the floor. A file shorter than the chunk is sent in one piece, so it is its length that is halved. Pure
	 *
	 *	@param		{number}	size						The bytes of the file
	 *	@param		{number}	chunk						The bytes of the chunk that was refused
	 *
	 *	@return		{number}									The smaller chunk; 0 when the chunk is at the floor already - nothing smaller is tried
	 */
	const halve = function( size, chunk ) {

		const sent = Math.min( size, chunk );

		return Number.isInteger( sent ) === true && sent > FLOOR ? Math.max( FLOOR, Math.floor( sent / 2 ) ) : 0;
	};

	/**
	 *	How far a file is, in whole percent. Pure
	 *
	 *	@param		{number}	sent						The bytes that are in
	 *	@param		{number}	size						The bytes of the file
	 *
	 *	@return		{number}									0 to 100
	 */
	const percent = function( sent, size ) {
		return size > 0 ? Math.max( 0, Math.min( 100, Math.floor( sent * 100 / size ) ) ) : 0;
	};

	/**
	 *	A word of the feature, as the page publishes it
	 *
	 *	@param		{string}	key							Below /feature/files/, eg. 'state/type'
	 *
	 *	@return		{string}
	 */
	const word = function( key ) {
		return Nino.content.getText( '/feature/files/'+ key );
	};

	/**
	 *	What an answer that is none of a success says: the word of its code, else - a post the csrf guard refused is a 403
	 *	with no body - that the session has run out, else - no answer at all - that the connection failed, else the write
	 *	that could not be made
	 *
	 *	@param		{Object}	xhr							The finished request: the status, the body in responseJSON
	 *
	 *	@return		{string}
	 */
	const refusal = function( xhr ) {

		const body = xhr.responseJSON !== null && typeof xhr.responseJSON === 'object' ? xhr.responseJSON : {};
		const found = typeof body.code === 'string' ? CODE.exec( body.code ) : null;

		if( found !== null && word( 'state/'+ found[1] ) !== '' )
			return word( 'state/'+ found[1] );

		if( typeof xhr.getAllResponseHeaders === 'function' && xhr.getAllResponseHeaders() === '' )
			return word('script/offline');

		return xhr.status === 403 ? word('script/expired') : word('state/write');
	};

	/**
	 *	A row of the list for one file
	 *
	 *	@param		{Element}	list
	 *	@param		{string}	name
	 *
	 *	@return		{Object}									{ item, status, bar }
	 */
	const row = function( list, name ) {

		const item = dc.createElement('li');
		const label = dc.createElement('span');
		const status = dc.createElement('span');
		const bar = dc.createElement('progress');

		item.className = 'files-upload-item';
		item.dataset.state = 'waiting';
		label.className = 'files-upload-name';
		label.textContent = name;
		status.className = 'files-upload-status';
		status.textContent = word('script/waiting');
		bar.className = 'files-upload-bar';
		bar.max = 100;
		bar.value = 0;

		item.appendChild( label );
		item.appendChild( status );
		item.appendChild( bar );
		list.appendChild( item );

		return { item : item, status : status, bar : bar };
	};

	/**
	 *	Put a row in a state
	 *
	 *	@param		{Object}	line
	 *	@param		{string}	state						sending, done or failed
	 *	@param		{string}	text
	 *
	 *	@return		void
	 */
	const mark = function( line, state, text ) {
		line.item.dataset.state = state;
		line.status.textContent = text;
	};

	// The field is valid again once what was refused is off the page: the sentences of the refusals leave its description
	const unflag = function( input ) {

		input.setAttribute( 'aria-invalid', 'false' );
		input.setAttribute( 'aria-describedby', ( input.getAttribute('aria-describedby') || '' ).split(/\s+/).filter( function( id ) { return id !== '' && id.indexOf('files-upload-refusal-') !== 0 } ).join(' ') );
	};

	// The counter of the ids of the sentences of refusals on a page
	let refusals = 0;

	/**
	 *	A file that failed: its row says why, and the field that takes files says it too - it is invalid, and the sentence of the row is what describes it, by an id
	 *
	 *	@param		{Object}	form						The form's settings: { input, ... }
	 *	@param		{Object}	line						The row of the file
	 *	@param		{string}	text
	 *
	 *	@return		void
	 */
	const fail = function( form, line, text ) {

		mark( line, 'failed', text );

		line.status.id = 'files-upload-refusal-'+ ( ++refusals );
		line.status.setAttribute( 'role', 'alert' );

		if( form.input === undefined )
			return;

		const kept = ( form.input.getAttribute('aria-describedby') || '' ).split(/\s+/).filter( Boolean );

		form.input.setAttribute( 'aria-invalid', 'true' );
		form.input.setAttribute( 'aria-describedby', kept.concat( [ line.status.id ] ).join(' ') );
	};

	/**
	 *	Send one file, chunk after chunk: the next is posted when the answer of the one before has come, and the upload id
	 *	the first one was answered with goes with every one after it. A chunk the web server answers 413 is the sign that the
	 *	server takes no request of this size (its own limit, which php does not see): the file starts again from its first
	 *	chunk, as a new upload, in chunks of half the size - down to the floor, where it fails - and the size that works is
	 *	left in form.chunk for the files after it
	 *
	 *	@param		{Object}		form						The form's settings: { element, folder, token, chunk }
	 *	@param		{File}			file
	 *	@param		{Object}		line						The row of the file
	 *	@param		{Function}	finished				Called with true once the file is in, false when it is not
	 *
	 *	@return		void
	 */
	const send = function( form, file, line, finished ) {

		let parts = chunks( file.size, form.chunk );
		let upload = '';

		mark( line, 'sending', word('script/sending') );

		const next = function( index ) {

			Nino.http.sendRequest( Nino.dir+ '/.files/upload', 'POST', function( xhr ) {

				const body = xhr.responseJSON !== null && typeof xhr.responseJSON === 'object' ? xhr.responseJSON : {};

				if( xhr.status === 413 ) {

					const smaller = halve( file.size, form.chunk );

					if( smaller === 0 ) {

						// A refusal that names its cause (a code the script has a word for) says it; one that does not is the web server's
						const found = typeof body.code === 'string' ? CODE.exec( body.code ) : null;

						fail( form, line, found !== null && word( 'state/'+ found[1] ) !== '' ? refusal( xhr ) : word('script/floor') );
						finished( false );
						return;
					}

					form.chunk = smaller;
					parts = chunks( file.size, smaller );
					upload = '';
					line.bar.value = 0;
					mark( line, 'sending', word('script/smaller') );
					next( 0 );
					return;
				}

				if( xhr.status !== 200 ) {
					fail( form, line, refusal( xhr ) );
					finished( false );
					return;
				}

				if( typeof body.upload === 'string' )
					upload = body.upload;

				line.bar.value = percent( parts[index][1], file.size );

				if( index + 1 < parts.length ) {
					next( index + 1 );
					return;
				}

				mark( line, 'done', word('script/done') );
				finished( true );
			}, {
				folder	: form.folder,
				name		: file.name,
				size		: file.size,
				upload	: upload,
				index		: index,
				count		: parts.length,
				chunk		: file.slice( parts[index][0], parts[index][1] ),
				_csrf		: form.token,
			} );
		};

		next( 0 );
	};

	/**
	 *	The page again with ?files=sent: the state line says what happened, and a browser component lists what came
	 *
	 *	@return		void
	 */
	const reload = function() {

		const url = new URL( wn.location.href );

		url.searchParams.set( 'files', 'sent' );
		wn.location.assign( url.pathname+ url.search );
	};

	/**
	 *	The size of a file the way a person reads it: bytes, KB or MB with at most one decimal, in the language of the page. Pure
	 *
	 *	@param		{number}	size						The bytes
	 *
	 *	@return		{string}
	 */
	const readable = function( size ) {

		if( Number.isFinite( size ) === false || size < 0 )
			return '';

		const unit = size < 1024 ? [ 1, 'B' ] : ( size < 1048576 ? [ 1024, 'KB' ] : [ 1048576, 'MB' ] );
		let text = String( Math.round( size / unit[0] * 10 ) / 10 );

		try {
			text = ( Math.round( size / unit[0] * 10 ) / 10 ).toLocaleString( dc.documentElement.lang || undefined );
		}
		catch( e ) {
			// A language the browser does not know: the number stands as it is
		}

		return text+ ' '+ unit[1];
	};

	/**
	 *	A row of the list of the chosen files: the name, the size and the button that takes the file out of the selection
	 *
	 *	@param		{Element}	list
	 *	@param		{File}		file
	 *	@param		{Function}	remove					Called with the file when the button is pressed
	 *
	 *	@return		{void}
	 */
	const pick = function( list, file, remove ) {

		const item = dc.createElement('li');
		const label = dc.createElement('span');
		const size = dc.createElement('span');
		const button = dc.createElement('button');
		const icon = dc.createElement('span');

		item.className = 'files-upload-pick';
		label.className = 'files-upload-name';
		label.textContent = file.name;
		size.className = 'files-upload-size';
		size.textContent = readable( file.size );
		button.className = 'files-upload-remove';
		button.type = 'button';
		button.title = word('script/remove');
		button.setAttribute( 'aria-label', word('script/remove')+ ': '+ file.name );
		icon.className = 'files-upload-icon';
		icon.setAttribute( 'aria-hidden', 'true' );
		button.addEventListener( 'click', function() { remove( file ) } );

		button.appendChild( icon );
		item.appendChild( label );
		item.appendChild( size );
		item.appendChild( button );
		list.appendChild( item );
	};

	/**
	 *	Take a form over: its submit sends every file the person chose, one after the other
	 *
	 *	@param		{Element}	element					A form.files-upload
	 *
	 *	@return		void
	 */
	const take = function( element ) {

		if( element.dataset.filesBound === '1' || typeof wn.FormData !== 'function' || typeof wn.File !== 'function' )
			return;

		const input = element.querySelector('input[type="file"]');
		const list = element.querySelector('.files-upload-list');
		const button = element.querySelector('button[type="submit"]');
		const folder = element.querySelector('input[name="folder"]');
		const token = element.querySelector('input[name="_csrf"]');
		const settings = options( element );

		// Without what it needs the form is left to the browser, which posts it whole
		if( input === null || list === null || folder === null || settings.chunk < 1 )
			return;

		element.dataset.filesBound = '1';
		input.multiple = settings.multiple;

		// The list of the chosen files and the sentence under it: the form may not have them, then the person sees no list before the submit
		const chosen = element.querySelector('.files-upload-chosen');
		const note = element.querySelector('.files-upload-chosen-hint');
		// The files the person took out of the selection where the input cannot be set to the rest: they are left out of the submit
		let dropped = [];
		let busy = false;
		// Kept per form, by its action, over the reload at the end of a submit
		const memory = 'nino-files-chunk:'+ ( element.getAttribute('action') || '' );
		let working = kept( memory );

		/**
		 *	The files of the selection: what the input holds without the ones that were taken out
		 *
		 *	@return		{Array}
		 */
		const selection = function() {
			return Array.from( input.files || [] ).filter( function( file ) { return dropped.indexOf( file ) === -1 } );
		};

		/**
		 *	The list of the chosen files, drawn again from the selection
		 *
		 *	@return		{void}
		 */
		const show = function() {

			if( chosen === null )
				return;

			const files = selection();

			chosen.textContent = '';
			chosen.hidden = files.length === 0;

			if( note !== null ) {
				note.textContent = word('script/chosen');
				note.hidden = files.length === 0;
			}

			files.forEach( function( file ) { pick( chosen, file, takeOut ) } );
		};

		/**
		 *	Take a file out of the selection, before anything is sent: the input is set to the files that are left where the browser lets a script do that, else the file is
		 *	remembered as left out. The file on the person's disk and the server are not touched
		 *
		 *	@param		{File}		file
		 *
		 *	@return		{void}
		 */
		const takeOut = function( file ) {

			if( busy === true )
				return;

			const before = selection();
			const at = before.indexOf( file );
			const rest = before.filter( function( other ) { return other !== file } );
			let set = false;

			if( rest.length === 0 && typeof input.value === 'string' ) {
				input.value = '';
				set = Array.from( input.files || [] ).length === 0;
			}
			else if( typeof wn.DataTransfer === 'function' ) {
				try {
					const transfer = new wn.DataTransfer();
					rest.forEach( function( other ) { transfer.items.add( other ) } );
					input.files = transfer.files;
					set = Array.from( input.files || [] ).length === rest.length;
				}
				catch( e ) {
					set = false;
				}
			}

			dropped = set === true ? [] : dropped.concat( [ file ] );
			show();

			// The button that was pressed is gone: the focus goes to the cross that took its place, else the one before, else the field that chooses files
			const crosses = chosen === null ? [] : Array.from( chosen.querySelectorAll('button') );
			const next = crosses.length === 0 ? input : crosses[Math.min( at, crosses.length - 1 )];

			if( typeof next.focus === 'function' )
				next.focus();
		};

		if( chosen !== null )
			input.addEventListener( 'change', function() {

				if( busy === true )
					return;

				// A new choice replaces the old one, and the progress list of an earlier submit is not about it
				dropped = [];
				unflag( input );
				list.hidden = true;
				list.textContent = '';
				show();
			} );

		element.addEventListener( 'submit', function( event ) {

			const all = Array.from( input.files || [] );
			const files = selection();

			if( busy === true ) {
				event.preventDefault();
				return;
			}

			// Nothing chosen: the browser asks for a file, as the attribute required says
			if( all.length === 0 )
				return;

			event.preventDefault();

			// Everything was taken out of a selection the input could not be set to: there is nothing to send
			if( files.length === 0 )
				return;

			busy = true;
			list.hidden = false;
			list.textContent = '';
			unflag( input );

			// The progress takes the place of the chosen files
			if( chosen !== null ) {
				chosen.hidden = true;

				if( note !== null )
					note.hidden = true;
			}

			if( button !== null )
				button.disabled = true;

			const run = { element : element, input : input, folder : folder.value, token : token === null ? '' : token.value, chunk : working > 0 ? Math.min( working, settings.chunk ) : settings.chunk };
			let ok = true;

			// Every file has its row from the start - the ones that are not at their turn yet wait in it
			const lines = files.map( function( file ) { return row( list, file.name ) } );

			const go = function( at ) {

				if( at === files.length ) {

					busy = false;
					input.value = '';
					dropped = [];

					if( button !== null )
						button.disabled = false;

					if( ok === true )
						reload();

					return;
				}

				const file = files[at];
				const line = lines[at];

				// Refused before a byte is sent: a file that is too large, or empty
				if( ( settings.max > 0 && file.size > settings.max ) || file.size < 1 ) {
					fail( run, line, word( file.size < 1 ? 'state/chunk' : 'state/size' ) );
					ok = false;
					go( at + 1 );
					return;
				}

				send( run, file, line, function( sent ) {
					ok = ok === true && sent === true;
					working = run.chunk;

					if( working < settings.chunk )
						keep( memory, working );
					go( at + 1 );
				} );
			};

			go( 0 );
		} );
	};

	/**
	 *	Take over every upload form of the page
	 *
	 *	@return		void
	 */
	const init = function() {
		Array.from( dc.querySelectorAll('form.files-upload') ).forEach( take );
	};

	wn.Nino.files = { chunks : chunks, halve : halve, floor : FLOOR, percent : percent, readable : readable, init : init };

	Nino.events.bindCallback( 'ready', init );

} )(window,document);
