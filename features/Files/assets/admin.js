/**
 *	Nino features
 *	admin.js		The panel of the Files feature, "Files", built on the standard directory and the record of the workbench
 *							(docs/admin-ui.md, "Directory and record"): the folders of the groups are a directory - narrowed by a group,
 *							searched, with a box to choose folders by - and a folder is a structure (a list of rows of its own), so it is a
 *							view of its own with its way back, and in it the files are a directory again; ONE file is a form - its name, whether
 *							the members see it, whether it is final, the folder it is in - and opens in the record, with an address of its own.
 *							Making a folder and renaming one need a name and a group, so they are dialogs (formDialog()); the upload is a dialog
 *							that cuts every file into the chunks the server names. See Modules\Files\Admin beside this file.
 *
 *							Three parts, one under the other: the DATA (pure functions that know folders and files and nothing of the screen), the ACTIONS
 *							(what is asked of the server, each ending in a callback with its status and answer) and the SCREEN, which draws the
 *							first with the blocks and calls the second.
 *
 *							The addresses are #files (the folders), #files/files/@folder/<folder> (a folder with its files: the word @folder and
 *							the key of the folder) and #files/files/<folder>-<file> (a file in the record over its folder: the key is ONE part,
 *							since the record names an entry by one - the kernel's record writes no more).
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( name ) { return t( '/_admin/files/'+ name ) };
	const el = Nino.adminUi.el;

	// The smallest chunk a file is cut into when the web server refuses a request with 413: 128 KiB
	const FLOOR = 131072;

	// The field of a dialog a refusal of the server belongs to, by its code; where the code names none the first field is the one
	const FIELD = { files_name : 'name', files_long : 'name', files_filename : 'name', files_extension : 'name', files_version : 'name', files_group : 'group', files_target : 'folder' };

	// The key of a file in the address: its folder, a hyphen and its id (twelve hexadecimal characters each)
	const FILE_KEY = /^([a-f0-9]{12})-([a-f0-9]{12})$/;
	const FOLDER_ID = /^[a-f0-9]{12}$/;

	// --- the data: folders, files, and what is made of them --------------------------------------------------------------------

	const data = {

		/**
		 *	A size in the largest unit that leaves a number of one digit or more, with the unit and the separator of the
		 *	language of the workbench: 1.5 MB, 120 MB, 512 KB. The rounding is that of Files\Store::size()
		 *
		 *	@param		{number}	bytes
		 *
		 *	@return		{string}
		 */
		size : function( bytes ) {

			const word = function( name ) { return w( 'unit/'+ name ) };
			const separator = word('separator') || '.';

			for( const [ unit, factor ] of [ [ 'gigabyte', 1073741824 ], [ 'megabyte', 1048576 ], [ 'kilobyte', 1024 ] ] )
				if( bytes >= factor ) {

					const value = bytes / factor;
					const text = value < 10 ? ( Math.round( value * 10 ) / 10 ).toFixed( 1 ).replace( /\.0$/, '' ) : String( Math.round( value ) );

					return text.replace( '.', separator )+ ' '+ word( unit );
				}

			return bytes+ ' '+ word('byte');
		},

		/**
		 *	The cut of a file: the chunks a size is divided into, every one but the last exactly a chunk long
		 *
		 *	@param		{number}	size						The bytes of the file
		 *	@param		{number}	chunk						The bytes of a chunk
		 *
		 *	@return		{Array}									[ [ start, end ], ... ], end not included - none for a size or a chunk that is no whole number above 0
		 */
		chunks : function( size, chunk ) {

			const parts = [];

			if( Number.isInteger( size ) === false || Number.isInteger( chunk ) === false || size < 1 || chunk < 1 )
				return parts;

			for( let start = 0; start < size; start += chunk )
				parts.push( [ start, Math.min( start + chunk, size ) ] );

			return parts;
		},

		/**
		 *	The chunk to try after a request of the chunk's size was answered 413 (the web server takes no request so large): half of it,
		 *	not below the floor. A file shorter than the chunk is sent in one piece, so it is its length that is halved
		 *
		 *	@param		{number}	size						The bytes of the file
		 *	@param		{number}	chunk						The bytes of the chunk that was refused
		 *
		 *	@return		{number}									The smaller chunk; 0 when the chunk is at the floor already
		 */
		halve : function( size, chunk ) {

			const sent = Math.min( size, chunk );

			return Number.isInteger( sent ) === true && sent > FLOOR ? Math.max( FLOOR, Math.floor( sent / 2 ) ) : 0;
		},

		/**
		 *	The day of a time of the server - 'Y-m-d\TH:i:s\Z', UTC
		 *
		 *	@param		{string}	time
		 *
		 *	@return		{string}									'' for none
		 */
		day : function( time ) {
			return typeof time === 'string' && /^\d{4}-\d{2}-\d{2}T/.test( time ) === true ? time.slice( 0, 10 ) : '';
		},

		/**
		 *	Who put a file there: the owner, the member by name, a member who is gone - one whose account is deleted, or whose id the entry no longer holds
		 *
		 *	@param		{Object}	file
		 *
		 *	@return		{string}
		 */
		who : function( file ) {

			if( file.by.kind === 'owner' )
				return w('label/by-owner');

			return typeof file.by.label === 'string' && file.by.label !== '' ? file.by.label : w('label/by-gone');
		},

		/**
		 *	The options of the group select: every group there is, all of them first - a lone option is no choice, so a project with one group has none
		 *
		 *	@param		{Array}		groups
		 *
		 *	@return		{Array}										[ { value, label } ], [] where there is nothing to choose
		 */
		groupOptions : function( groups ) {

			if( groups.length < 2 )
				return [];

			return [ { value : '', label : w('filter/all') } ].concat( groups.map( function( group ) { return { value : group, label : group } } ) );
		},

		/**
		 *	The rows of the directory of the folders: a folder as it is drawn, named in the address by its id
		 *
		 *	@param		{Array}		folders
		 *
		 *	@return		{Array}
		 */
		folderRows : function( folders ) {

			return folders.map( function( folder ) {
				return { key : folder.key, id : folder.id, name : folder.name, group : folder.group, count : folder.count, bytes : folder.bytes, upload : folder.upload === true };
			} );
		},

		/**
		 *	The rows of the directory of the files of a folder: a file as it is drawn - named in the address by its folder and its id
		 *
		 *	@param		{Array}		files
		 *
		 *	@return		{Array}
		 */
		fileRows : function( files ) {

			return files.map( function( file ) {
				return {
					key : file.key, id : file.id, folder : file.folder, name : file.name, ext : file.ext, mime : file.mime, sha256 : file.sha256, url : file.url, size : file.size,
					who : data.who( file ), created : file.created, day : data.day( file.created ), visible : file.visible === true, final : file.final === true,
					state : file.gone !== undefined ? 'removed' : ( file.visible === true ? 'visible' : 'hidden' ), gone : data.day( file.gone ),
				};
			} );
		},

		/**
		 *	What may be done with a file in the state it is in: a file whose bytes were removed from the server has no download, no switch, no move and nothing
		 *	to remove - it can be renamed and deleted
		 *
		 *	@param		{Object}	row								A row of fileRows()
		 *
		 *	@return		{Array}										Some of 'download', 'show', 'hide', 'move', 'remove', 'delete', in the order of the buttons
		 */
		can : function( row ) {

			if( row.state === 'removed' )
				return [ 'delete' ];

			return [ 'download', row.state === 'visible' ? 'hide' : 'show', 'move', 'remove', 'delete' ];
		},

		/**
		 *	The folders a file may be moved to: the other folders of its group
		 *
		 *	@param		{Array}		folders
		 *	@param		{Object}	folder						The folder the files are in
		 *
		 *	@return		{Array}
		 */
		targets : function( folders, folder ) {
			return folders.filter( function( other ) { return other.group === folder.group && other.id !== folder.id } );
		},

		/**
		 *	What of a file a person changed in the record: only that is posted
		 *
		 *	@param		{Object}	row								The row as it was
		 *	@param		{Object}	values						{ name, folder, visible, final } as the form holds them
		 *
		 *	@return		{Object}									The fields that differ; {} where nothing does
		 */
		changes : function( row, values ) {

			const changed = {};

			if( values.name !== undefined && values.name !== row.name )
				changed.name = values.name;

			if( values.folder !== undefined && values.folder !== row.folder )
				changed.folder = values.folder;

			if( values.visible !== undefined && values.visible !== row.visible )
				changed.visible = values.visible;

			if( values.final !== undefined && values.final !== row.final )
				changed.final = values.final;

			return changed;
		},

		/**
		 *	The limits a file is held to before a byte is sent: the types and the largest size
		 *
		 *	@param		{File}		file
		 *	@param		{Array}		types						The extensions that may be uploaded
		 *	@param		{number}	max							The largest file in bytes, 0 for no limit
		 *
		 *	@return		{string}									'' for a file that may be sent; 'empty', 'type' or 'size'
		 */
		problem : function( file, types, max ) {

			const dot = file.name.lastIndexOf('.');
			const ext = dot < 1 ? '' : file.name.slice( dot + 1 ).toLowerCase();

			if( file.size < 1 )
				return 'empty';

			if( types.indexOf( ext ) === -1 )
				return 'type';

			return max > 0 && file.size > max ? 'size' : '';
		},

		/**
		 *	What the dialog of an upload posts for a chunk
		 *
		 *	@param		{string}	folder
		 *	@param		{File}		file
		 *	@param		{string}	upload						The id the first chunk was answered with, '' before
		 *	@param		{number}	index
		 *	@param		{number}	count
		 *	@param		{Object}	values						{ visible }: what the dialog read
		 *
		 *	@return		{Object}
		 */
		chunkPayload : function( folder, file, upload, index, count, values ) {
			return { folder : folder, name : file.name, size : file.size, upload : upload, index : index, count : count, visible : values.visible === true, notify : false };
		},
	};

	// --- the actions: what is asked of the server ------------------------------------------------------------------------------------

	const call = function( action, payload, done, extra ) {
		Nino.adminUi.api.call( 'files/'+ action, payload, done, extra );
	};

	const actions = {
		list        : function( folder, done ) { call( 'list', { folder : folder }, done ) },
		add         : function( payload, done ) { call( 'folder/add', payload, done ) },
		rename      : function( id, name, done ) { call( 'folder/save', { id : id, name : name }, done ) },
		mark        : function( id, on, done ) { call( 'folder/upload-target', { id : id, on : on }, done ) },
		removeFolder: function( id, done ) { call( 'folder/delete', { id : id }, done ) },
		save        : function( payload, done ) { call( 'save', payload, done ) },
		notify      : function( ids, done ) { call( 'notify', { ids : ids, notify : true }, done ) },
		remove      : function( id, done ) { call( 'remove', { id : id }, done ) },
		delete      : function( id, done ) { call( 'delete', { id : id }, done ) },
		chunk       : function( payload, blob, done ) { call( 'upload', payload, done, { chunk : blob } ) },
	};

	// --- the screen --------------------------------------------------------------------------------------------------------------------

	// What the panel keeps while its views are drawn again: the answer of the server, the folder that is open and the one whose files are in the answer, the two
	// directories and the record, what the directories show (their state, taken back when they are drawn again), and where a move of the person is going
	const S = {
		groups : [], members : [], folders : [], files : [], types : [], notify : false, limits : { chunk : 0, max : 0, php : 0, quota : 0, used : 0, held : 0 },
		ready : false, loading : false, loaded : '', filesFor : '', moved : false, working : 0,
		list : null, view : null, record : null, listState : {}, viewState : {}, failed : null, listShape : '',
	};

	const folderOf = function( id ) {
		return S.folders.find( function( folder ) { return folder.id === id } ) ?? null;
	};

	const fileOf = function( key ) {
		return S.files.find( function( file ) { return file.key === key } ) ?? null;
	};

	// A sentence with a number in it, in the words for one where the number is 1 and in the others where it is not
	const plural = function( one, many, count, ...params ) {
		return Nino.adminUi.format( w( count === 1 ? one : many ), ...params, count );
	};

	// The folder the address names: its own view (@folder/<folder>) or the record of a file in it (<folder>-<file>); '' for the directory
	const addressFolder = function() {

		const at = Nino.admin.router.current();

		if( at.panel !== 'files' || at.parts.length === 0 )
			return '';

		if( Nino.admin.router.isWord( at.parts, 0, 'folder' ) === true )
			return typeof at.parts[1] === 'string' ? at.parts[1] : '';

		const found = at.parts.length === 1 && typeof at.parts[0] === 'string' ? FILE_KEY.exec( at.parts[0] ) : null;

		return found === null ? '' : found[1];
	};

	// The address of a folder's view
	const viewParts = function( id ) {
		return id === '' ? [] : [ Nino.admin.router.word('folder'), id ];
	};

	// A move of the person inside the panel: the address is a new entry of the history, and the screen follows it
	const go = function( parts ) {
		S.moved = true;
		Nino.admin.router.go( 'files', parts );
		Nino.admin.files.showCurrent();
	};

	// The sentence of a refusal at the field it belongs to, where the dialog says it; the failure of a write is the server's and stands in the first field
	const refused = function( ctl, first, status, response, fallback ) {
		ctl.fail( FIELD[ ( response ?? {} ).code ] ?? first, Nino.adminUi.api.errorText( status, response, fallback ) );
	};

	// An error of an action that has no field stands over the directory that is on screen
	const said = function( status, response, fallback ) {

		if( S.failed === null )
			return;

		S.failed.innerHTML = '';
		Nino.adminUi.showError( S.failed, status, response, fallback );
	};

	// Where the focus goes back to when a dialog closes: found when it closes, since the screen may be drawn again by then
	const returnTo = function( find ) {
		return { focus : function() { const target = find(); if( target !== null && target !== undefined ) target.focus() } };
	};

	/**
	 *	Read the list again - for the folder the address names, whose files are then in the answer - and draw what it holds
	 *
	 *	@param		{Function}	[then]
	 *
	 *	@return		void
	 */
	const load = function( then ) {

		if( S.loading === true )
			return;

		const want = addressFolder();

		S.loading = true;

		actions.list( want, function( status, response ) {

			S.loading = false;

			if( status !== 200 || response === null ) {

				const mount = dc.getElementById('files-list');

				return S.list === null ? Nino.adminUi.showError( mount, status, response, '/_admin/files/error/load' ) : S.list.fail( status, response, '/_admin/files/error/load' );
			}

			S.groups = Array.isArray( response.groups ) === true ? response.groups : [];
			S.members = Array.isArray( response.members ) === true ? response.members : [];
			S.folders = Array.isArray( response.folders ) === true ? response.folders : [];
			S.files = Array.isArray( response.files ) === true ? response.files : [];
			S.types = Array.isArray( response.types ) === true ? response.types : [];
			S.notify = response.notify === true;
			S.limits = Object.assign( {}, S.limits, response.limits );
			S.loaded = folderOf( want ) === null ? '' : want;
			S.ready = true;

			show();

			if( typeof then === 'function' )
				then();
		} );
	};

	/**
	 *	What the address says is shown: the folders, or one folder with its files - and a record that the address names opens over it. Nothing is read
	 *
	 *	@return		void
	 */
	const show = function() {

		drawList();

		if( S.loaded !== '' )
			drawFolder();

		const options = S.moved === true ? {} : { focus : false };

		S.moved = false;
		Nino.adminUi.showLevel( S.loaded === '' ? 'files-list' : 'files-folder', options );

		settle();

		if( S.record !== null )
			S.record.follow();
	};

	// The address is the one of what is on screen: one that names what is shown (the folders, a folder, a file of the folder the record opens over it) stays, any other - a part nobody
	// knows, a folder or a file that is not there - is written over by the address of the screen. A part that could be an identifier and names nothing is said; any other is only written over
	const settle = function() {

		const router = Nino.admin.router;
		const at = router.current();

		if( at.panel !== 'files' )
			return;

		const parts = at.parts.map( String );
		const expected = viewParts( S.loaded ).map( String );
		const key = parts.length === 1 ? parts[0] : '';
		const found = FILE_KEY.exec( key );

		// The file the record is about to open over the folder: its address is the one the router writes - a key typed with an escape in it (%2D) is written in its own form
		if( found !== null && S.loaded !== '' && found[1] === S.loaded && fileOf( key ) !== null ) {

			if( wn.location.hash !== router.href( 'files', [ key ] ) )
				router.set( 'files', [ key ] );

			return;
		}

		// What the screen shows
		if( parts.join('/') === expected.join('/') && wn.location.hash === router.href( 'files', viewParts( S.loaded ) ) )
			return;

		const asked = ( parts.length >= 2 && parts[0] === '@folder' && FOLDER_ID.test( parts[1] ) === true && S.loaded === '' ) ? 'folder' : ( found !== null ? 'file' : '' );

		router.set( 'files', viewParts( S.loaded ) );

		if( asked === 'folder' && S.list !== null )
			S.list.notice( w('error/missing-folder') );

		if( asked === 'file' )
			missingFile();
	};

	// A file the address named and the folder has not: said over the directory that is on screen
	const missingFile = function() {

		const shown = S.loaded === '' ? S.list : S.view;

		if( shown !== null )
			shown.notice( w('error/missing-file') );
	};

	// --- the folders ---------------------------------------------------------------------------------------------------------------

	/**
	 *	The dialog of a new folder: a name and the group it belongs to
	 *
	 *	@param		{Element}	opener
	 *
	 *	@return		void
	 */
	const addFolder = function( opener ) {

		const chosen = typeof S.listState.category === 'string' ? S.listState.category : '';

		Nino.adminUi.formDialog( {
			title		: w('dialog/new'),
			fields	: [
				{ key : 'name', label : w('label/name'), required : true, maxLength : 80, autocomplete : 'off' },
				{ key : 'group', label : w('label/group'), help : w('hint/group'), type : 'select', value : S.members.indexOf( chosen ) !== -1 ? chosen : S.members[0], options : S.members.map( function( group ) { return { value : group, label : group } } ) },
			],
			submit	: w('label/create'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				actions.add( { group : String( values.group ), name : String( values.name ) }, function( status, response ) {

					if( status !== 200 || response === null )
						return refused( ctl, 'name', status, response, '/_admin/files/error/add' );

					ctl.close();
					load( function() {
						Nino.adminUi.say( w('msg/folder-added') );

						if( S.list !== null )
							S.list.focusRow( response.folder.id );
					} );
				} );
			},
		} );
	};

	/**
	 *	The dialog of a rename: the name of a folder
	 *
	 *	@param		{Object}	folder
	 *	@param		{Element}	opener
	 *
	 *	@return		void
	 */
	const renameFolder = function( folder, opener ) {

		Nino.adminUi.formDialog( {
			title		: w('dialog/rename'),
			message	: folder.group,
			fields	: [ { key : 'name', label : w('label/name'), value : folder.name, required : true, maxLength : 80, autocomplete : 'off' } ],
			submit	: t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				if( String( values.name ).trim() === folder.name ) {
					ctl.close();
					return;
				}

				actions.rename( folder.id, String( values.name ), function( status, response ) {

					if( status !== 200 || response === null )
						return refused( ctl, 'name', status, response, '/_admin/files/error/save' );

					ctl.close();
					load( function() { Nino.adminUi.say( t('/_admin/common/msg/saved') ) } );
				} );
			},
		} );
	};

	/**
	 *	The mark that makes a folder the one the members of its group upload into, or takes it off: an immediate action, said as a toast
	 *
	 *	@param		{Object}	folder
	 *
	 *	@return		void
	 */
	const markFolder = function( folder ) {

		const on = folder.upload !== true;

		actions.mark( folder.id, on, function( status, response ) {

			if( status !== 200 )
				return said( status, response, '/_admin/files/error/save' );

			load( function() {
				Nino.adminUi.say( w( on === true ? 'msg/marked' : 'msg/unmarked' ) );

				if( S.loaded === '' && S.list !== null )
					S.list.focusRow( folder.id );
			} );
		} );
	};

	/**
	 *	Something done to each of several things, one request each and in order: the first refusal stops it and is said, the list is read again at the end
	 *
	 *	@param		{Array}			ids
	 *	@param		{Function}	each						each( id, done ) - done( status, response ) of the request
	 *	@param		{Function}	finish					finish( count ) - how many went through, called after the list was read again
	 *	@param		{Function}	[fail]					fail( status, response ) - what a refusal does besides the list being read again; said over the directory by default
	 *
	 *	@return		void
	 */
	const batch = function( ids, each, finish, fail ) {

		let done = 0;

		const next = function( at ) {

			if( at === ids.length )
				return load( function() { finish( done ) } );

			each( ids[at], function( status, response ) {

				if( status !== 200 ) {
					load( function() {
						if( typeof fail === 'function' )
							fail( status, response );
						else
							said( status, response, '/_admin/files/error/save' );
					} );
					return;
				}

				done++;
				next( at + 1 );
			} );
		};

		next( 0 );
	};

	/**
	 *	The deletion of folders with their files, after the workbench's question that names how many folders and files go
	 *
	 *	@param		{Array}			ids					The ids of the folders
	 *	@param		{Element|null}	opener
	 *
	 *	@return		void
	 */
	const removeFolders = function( ids, opener ) {

		const folders = ids.map( folderOf ).filter( function( folder ) { return folder !== null } );
		const count = folders.reduce( function( sum, folder ) { return sum + folder.count }, 0 );
		const names = folders.slice( 0, 5 ).map( function( folder ) { return folder.name } );

		if( folders.length > 5 )
			names.push( Nino.adminUi.format( w('confirm/more'), folders.length - 5 ) );

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/folder-title'),
			message	: folders.length === 1 ? Nino.adminUi.format( w('confirm/folder'), folders[0].name ) : Nino.adminUi.format( w('confirm/folders'), folders.length ),
			details	: names.concat( count === 0 ? [] : [ plural( 'label/count-one', 'label/count', count ) ] ),
			yes			: w('confirm/folder-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				const inside = ids.indexOf( S.loaded ) !== -1;

				if( inside === true ) {
					S.moved = true;
					Nino.admin.router.go( 'files', [] );
				}

				batch( ids, function( id, done ) { actions.removeFolder( id, done ) }, function( done ) {
					Nino.adminUi.say( done === 1 ? t('/_admin/common/msg/deleted') : Nino.adminUi.format( w('msg/folders-deleted'), done ) );
				} );
			},
		} );
	};

	const folderCells = {

		bytes : function( value ) {
			return el( 'span', '', data.size( value ) );
		},

		upload : function( value ) {

			if( value !== true )
				return el( 'span', '', '' );

			const badge = el( 'span', 'nino-admin-badge', '✓ '+ w('state/upload') );

			badge.dataset.state = 'ok';

			return badge;
		},
	};

	// The four words of the select that sorts the cards of a directory under 40 rem: the workbench's own, in both languages
	const sortLabels = function() {
		return { sortBy : t('/_admin/common/table/sort-by'), sortNone : t('/_admin/common/table/sort-default'), sortUp : t('/_admin/common/table/sort-up'), sortDown : t('/_admin/common/table/sort-down') };
	};

	// The directory of the folders: narrowed by a group where there are two or more, searched, with a box to choose folders by
	const drawList = function() {

		const mount = dc.getElementById('files-list');
		const options = data.groupOptions( S.groups );
		const shape = JSON.stringify( [ S.members.length === 0 && S.folders.length === 0, S.folders.length === 0, options ] );

		// The directory is kept while it is drawn again: its rows are replaced and it stays as the person left it
		if( S.list !== null && S.listShape === shape ) {
			S.list.setRows( data.folderRows( S.folders ), { keep : true } );
			return;
		}

		mount.innerHTML = '';
		S.list = null;
		S.listShape = shape;

		if( S.members.length === 0 && S.folders.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( w('empty/groups') ) );
			return;
		}

		const make = Nino.adminUi.button( w('label/new'), function() { addFolder( make ) }, { kind : 'primary', id : 'files-new' } );

		if( S.folders.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( w('empty/folders'), Nino.adminUi.button( w('label/new'), function() { addFolder( make ) }, { kind : 'primary', id : 'files-new-first' } ) ) );
			return;
		}

		S.failed = el( 'div' );
		S.failed.setAttribute( 'role', 'alert' );
		mount.appendChild( S.failed );

		const tableMount = el( 'div' );

		mount.appendChild( tableMount );

		S.list = Nino.adminUi.directory( {
			mount		: tableMount,
			rowKey	: 'key',
			state		: S.listState,
			onState	: function( now ) { S.listState = now },
			labels	: Object.assign( { search : w('label/search'), empty : w('empty/folders'), noMatch : w('empty/filter') }, sortLabels() ),
			category	: options.length === 0 ? undefined : { id : 'files-group', label : w('label/filter'), value : '', options : options, match : function( row, value ) { return value === '' || row.group === value } },
			columns	: [
				{ key : 'name', label : w('label/name'), type : 'string', cell : 'clamp', fill : true, primary : true },
				{ key : 'group', label : w('label/group'), type : 'string', narrow : 'second' },
				{ key : 'count', label : w('label/files'), type : 'integer' },
				{ key : 'bytes', label : w('label/size'), type : 'integer', render : folderCells.bytes, narrow : false },
				{ key : 'upload', label : w('label/upload-folder'), type : 'string', render : folderCells.upload },
			],
			searchText : function( row ) { return row.group },
			select	: {
				label		: w('select/page'),
				rowLabel	: function( row ) { return Nino.adminUi.format( w('select/row'), row.name ) },
				actions	: [ { key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : function( keys ) { removeFolders( keys, null ) } } ],
			},
			actions	: function( row ) {

				const folder = folderOf( row.id );
				const open = Nino.adminUi.button( w('label/open'), function() { go( viewParts( row.id ) ) }, { small : true, name : Nino.adminUi.format( w('label/open-folder'), row.name ) } );
				const rename = Nino.adminUi.button( w('label/rename'), function() { renameFolder( folder, rename ) }, { small : true } );
				const mark = Nino.adminUi.button( w( folder.upload === true ? 'label/unmark' : 'label/mark' ), function() { markFolder( folder ) }, { small : true } );
				const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { removeFolders( [ row.id ], drop ) }, { small : true, kind : 'danger' } );

				return [ open, rename, mark, drop ];
			},
			onOpen	: function( row ) { go( viewParts( row.id ) ) },
		} );

		S.list.setRows( data.folderRows( S.folders ) );

		mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : make } ).el );
	};

	// --- the files of a folder ------------------------------------------------------------------------------------------------------

	const download = function( file ) {

		const link = el( 'a' );

		link.href = file.url+ '?download=1';
		link.rel = 'noopener';
		dc.body.appendChild( link );
		link.click();
		link.remove();
	};

	/**
	 *	Show files to the members or hide them. Showing is told to the group by a mail where the setting says so - and then the owner is asked once, in a dialog
	 *	with the one choice, whether to mail
	 *
	 *	@param		{Array}			keys
	 *	@param		{boolean}		visible
	 *	@param		{Element|Object|null}	opener
	 *
	 *	@return		void
	 */
	const showFiles = function( keys, visible, opener ) {

		const run = function( mail, ctl ) {

			const changed = [];

			batch( keys, function( key, done ) {

				const file = fileOf( key );

				actions.save( { id : file.id, visible : visible, notify : false }, function( status, response ) {

					if( status === 200 && visible === true && file.visible !== true )
						changed.push( file.id );

					done( status, response );
				} );
			}, function( count ) {

				const finish = function() {

					if( ctl !== undefined )
						ctl.close();

					Nino.adminUi.say( plural( visible === true ? 'msg/shown-one' : 'msg/hidden-one', visible === true ? 'msg/shown' : 'msg/hidden', count ) );
				};

				if( mail === true && changed.length > 0 )
					return actions.notify( changed, finish );

				finish();
			}, function( status, response ) {

				if( ctl === undefined )
					return said( status, response, '/_admin/files/error/save' );

				ctl.fail( null, Nino.adminUi.api.errorText( status, response, '/_admin/files/error/save' ) );
			} );
		};

		if( visible === false || S.notify !== true ) {
			run( false );
			return;
		}

		Nino.adminUi.formDialog( {
			title		: w('dialog/show'),
			message	: plural( 'label/count-one', 'label/count', keys.length ),
			fields	: [ { key : 'mail', label : w('label/mail'), note : w('hint/mail'), type : 'check', value : true } ],
			submit	: w('label/show'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) { run( values.mail === true, ctl ) },
		} );
	};

	/**
	 *	Move files into another folder of their group, chosen in a dialog
	 *
	 *	@param		{Array}			keys
	 *	@param		{Object}		folder					The folder they are in
	 *	@param		{Element|Object|null}	opener
	 *
	 *	@return		void
	 */
	const moveFiles = function( keys, folder, opener ) {

		const targets = data.targets( S.folders, folder );

		if( targets.length === 0 ) {
			Nino.adminUi.say( w('msg/no-target') );
			return;
		}

		Nino.adminUi.formDialog( {
			title		: w('dialog/move'),
			message	: plural( 'label/count-one', 'label/count', keys.length ),
			fields	: [ { key : 'folder', label : w('label/target'), required : true, type : 'select', value : targets[0].id, options : targets.map( function( target ) { return { value : target.id, label : target.name } } ) } ],
			submit	: w('label/move'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				batch( keys, function( key, done ) {
					actions.save( { id : fileOf( key ).id, folder : String( values.folder ), notify : false }, done );
				}, function( count ) {
					ctl.close();
					Nino.adminUi.say( plural( 'msg/moved-one', 'msg/moved', count ) );
				}, function( status, response ) {
					refused( ctl, 'folder', status, response, '/_admin/files/error/save' );
				} );
			},
		} );
	};

	/**
	 *	Remove the bytes of files from the server (their rows stay) or delete the files: a question that names what goes and how many
	 *
	 *	@param		{string}		kind						'remove' or 'delete'
	 *	@param		{Array}			keys
	 *	@param		{Element|Object|null}	opener
	 *
	 *	@return		void
	 */
	const takeFiles = function( kind, keys, opener, nested ) {

		const files = keys.map( fileOf ).filter( function( file ) { return file !== null } );
		const names = files.slice( 0, 5 ).map( function( file ) { return file.name } );

		if( files.length > 5 )
			names.push( Nino.adminUi.format( w('confirm/more'), files.length - 5 ) );

		Nino.adminUi.confirm( 'destructive', {
			title		: w( 'confirm/'+ kind+ '-title' ),
			message	: plural( 'confirm/'+ kind+ '-one', 'confirm/'+ kind, files.length ),
			details	: names,
			yes			: w( 'confirm/'+ kind+ '-title' ),
			no			: t('/_admin/common/label/cancel'),
			nested	: nested === true,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				batch( keys, function( key, done ) { actions[kind]( fileOf( key ).id, done ) }, function( count ) {
					Nino.adminUi.say( kind === 'delete' ? ( count === 1 ? t('/_admin/common/msg/deleted') : Nino.adminUi.format( w('msg/files-deleted'), count ) ) : plural( 'msg/removed-one', 'msg/removed', count ) );
				} );
			},
		} );
	};

	// --- the upload: a dialog that cuts every file into chunks and sends them in order -------------------------------------------------

	/**
	 *	One file, chunk after chunk: the next is sent when the answer of the one before has come, and the upload id the first one was answered with goes with every
	 *	one after it. A chunk the web server answers 413 is the sign that it takes no request of this size: the file starts again as a new upload in chunks of half
	 *	the size, down to the floor, where it fails - and the size that works is kept for the files after it
	 *
	 *	@param		{string}		folder
	 *	@param		{File}			file
	 *	@param		{Object}		values					{ visible }
	 *	@param		{Object}		line						{ status, bar } - the elements of its row
	 *	@param		{Function}	finished				finished( id ) with the id of the file that is in, or finished( null, text ) with the sentence of the refusal
	 *
	 *	@return		void
	 */
	const send = function( folder, file, values, line, finished ) {

		let parts = data.chunks( file.size, S.working > 0 ? Math.min( S.working, S.limits.chunk ) : S.limits.chunk );
		let upload = '';

		const next = function( index ) {

			actions.chunk( data.chunkPayload( folder, file, upload, index, parts.length, values ), file.slice( parts[index][0], parts[index][1] ), function( status, response ) {

				if( status === 413 ) {

					const smaller = data.halve( file.size, parts[0][1] - parts[0][0] );

					if( smaller === 0 )
						return finished( null, w('error/floor') );

					S.working = smaller;
					parts = data.chunks( file.size, smaller );
					upload = '';
					line.bar.value = 0;
					line.status.textContent = w('label/smaller');
					return next( 0 );
				}

				if( status !== 200 || response === null || typeof response !== 'object' )
					return finished( null, Nino.adminUi.api.errorText( status, response, '/_admin/files/error/upload' ) );

				if( typeof response.upload === 'string' )
					upload = response.upload;

				line.bar.value = Math.floor( parts[index][1] * 100 / file.size );

				if( index + 1 < parts.length )
					return next( index + 1 );

				finished( response.id );
			} );
		};

		next( 0 );
	};

	/**
	 *	The dialog that uploads: a button that chooses files (several, one choice after another adds to the list), the list of what is chosen with a button to take
	 *	one out, whether the members see them and whether the group is mailed - and, once it is sent, the progress of each file. A file the limits refuse is said at
	 *	the field with its name and left out; the dialog stays open until all the files are in or one of them is refused by the server
	 *
	 *	@param		{Object}		folder
	 *	@param		{Element}		opener
	 *
	 *	@return		void
	 */
	const upload = function( folder, opener ) {

		const queue = [];
		const body = el( 'div', 'files-admin-upload' );
		const progress = el( 'ul', 'nino-admin-list nino-admin-list-dense files-admin-queue' );
		const options = el( 'div', 'nino-admin-fieldgrid' );
		let busy = false;

		const field = Nino.adminUi.fileField( {
			key			: 'files',
			label		: w('label/files'),
			help		: w('hint/upload'),
			note		: Nino.adminUi.format( w('note/upload'), data.size( S.limits.max ), S.types.join(', ') )+ ( S.limits.quota > 0 ? ' '+ Nino.adminUi.format( w('note/quota'), data.size( S.limits.used ), data.size( S.limits.quota ) ) + ( S.limits.held > 0 ? ' '+ Nino.adminUi.format( w('note/held'), data.size( S.limits.held ) ) : '' ) : '' ),
			button	: w('label/choose'),
			id			: 'files-choose',
		} );

		field.input.multiple = true;
		field.input.accept = S.types.map( function( type ) { return '.'+ type } ).join();

		const visible = Nino.adminUi.switchField( { key : 'visible', checked : true, label : w('label/visible'), help : w('hint/visible'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );
		const mail = Nino.adminUi.switchField( { key : 'mail', checked : S.notify, label : w('label/mail'), help : w('hint/mail'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );

		const draw = function() {

			progress.innerHTML = '';

			queue.forEach( function( entry ) {

				const row = el( 'li', 'files-admin-row' );
				const name = el( 'strong', 'files-admin-file', entry.file.name );
				const status = el( 'small', '', data.size( entry.file.size ) );
				const bar = el( 'progress' );

				bar.max = 100;
				bar.value = entry.percent;
				row.dataset.state = entry.state;
				row.appendChild( name );
				row.appendChild( status );
				row.appendChild( bar );

				if( entry.state === 'waiting' && busy === false )
					row.appendChild( Nino.adminUi.iconButton( 'x', Nino.adminUi.format( w('label/take-out'), entry.file.name ), function() {
						queue.splice( queue.indexOf( entry ), 1 );
						draw();
						field.button.focus();
					}, { small : true } ) );

				entry.line = { status : status, bar : bar };

				if( entry.text !== '' )
					status.textContent = entry.text;

				progress.appendChild( row );
			} );
		};

		field.input.addEventListener( 'change', function() {

			if( busy === true )
				return;

			const picked = Array.from( field.input.files ?? [] );

			field.clear();

			const refused = [];

			picked.forEach( function( file ) {

				const problem = data.problem( file, S.types, S.limits.max );

				if( problem !== '' )
					return refused.push( Nino.adminUi.format( w( 'error/'+ problem ), file.name ) );

				queue.push( { file : file, state : 'waiting', percent : 0, text : '' } );
			} );

			// Every file the limits refuse is said by name, together
			if( refused.length > 0 )
				field.error( refused.join(' ') );

			draw();
		} );

		options.appendChild( visible );
		options.appendChild( mail );
		body.appendChild( field.el );
		body.appendChild( progress );
		body.appendChild( options );

		Nino.adminUi.formDialog( {
			title		: Nino.adminUi.format( w('dialog/upload'), folder.name ),
			body		: body,
			submit	: w('label/upload'),
			cancel	: t('/_admin/common/label/cancel'),
			size		: 'form',
			opener	: opener,
			onSubmit	: function( values, ctl ) {

				const waiting = queue.filter( function( entry ) { return entry.state !== 'done' } );

				if( waiting.length === 0 ) {
					field.error( w('error/choose') );
					field.button.focus();
					return ctl.idle();
				}

				busy = true;
				field.button.disabled = true;

				const sent = [];
				const mails = mail.querySelector('input').checked === true;
				const shown = visible.querySelector('input').checked === true;

				const end = function( failure ) {

					busy = false;
					field.button.disabled = false;
					draw();

					const finish = function() {
						load( function() {
							if( sent.length > 0 )
								Nino.adminUi.say( plural( 'msg/uploaded-one', 'msg/uploaded', sent.length ) );
						} );
					};

					if( failure !== undefined ) {

						// The files that are in are in: the list behind the dialog shows them once it is read again
						if( sent.length > 0 )
							finish();

						return ctl.fail( null, failure );
					}

					// One mail names all the files of the upload: they went in without one, and this call mails them
					if( mails === true && shown === true && sent.length > 0 )
						return actions.notify( sent, function() { ctl.close(); finish() } );

					ctl.close();
					finish();
				};

				const next = function( at ) {

					if( at === waiting.length )
						return end();

					const entry = waiting[at];

					entry.state = 'sending';
					draw();

					send( folder.id, entry.file, { visible : shown }, entry.line, function( id, failure ) {

						if( id === null ) {
							entry.state = 'failed';
							entry.text = failure;
							return end( failure );
						}

						entry.state = 'done';
						entry.percent = 100;
						sent.push( id );
						next( at + 1 );
					} );
				};

				next( 0 );
			},
		} );
	};

	// --- the record: one file --------------------------------------------------------------------------------------------------------

	/**
	 *	The content of the record of a file: its name, the folder it is in, whether the members see it and whether it is final - and what of it is only read
	 *
	 *	@param		{Object}	row
	 *	@param		{Object}	ctx								The record's: what is put in it is found again by save()
	 *
	 *	@return		{Element}
	 */
	const body = function( row, ctx ) {

		const fields = el( 'div', 'nino-admin-dialog-fields' );
		const folder = folderOf( row.folder );
		const removed = row.state === 'removed';

		ctx.name = Nino.adminUi.textField( { key : 'name', label : w('label/name'), value : row.name, maxLength : 160, counter : true, required : true } );
		fields.appendChild( ctx.name );

		if( removed === false ) {

			const targets = folder === null ? [] : data.targets( S.folders, folder );

			if( targets.length > 0 ) {
				ctx.folder = Nino.adminUi.selectField( { key : 'folder', label : w('label/target'), value : row.folder, options : [ { value : row.folder, label : folder.name } ].concat( targets.map( function( target ) { return { value : target.id, label : target.name } } ) ) } );
				fields.appendChild( ctx.folder );
			}

			ctx.visible = Nino.adminUi.switchField( { key : 'visible', checked : row.visible, label : w('label/visible'), help : w('hint/visible-file'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );
			ctx.final = Nino.adminUi.switchField( { key : 'final', checked : row.final, label : w('label/final'), help : w('hint/final'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );
			fields.appendChild( ctx.visible );
			fields.appendChild( ctx.final );
		}

		[ [ 'label/by', row.who ], [ 'label/day', row.day === '' ? '' : Nino.adminUi.dateText( row.day ) ], [ 'label/size', data.size( row.size ) ], [ 'label/type', row.mime ] ]
			.concat( removed === true ? [ [ 'label/removed-day', Nino.adminUi.dateText( row.gone ) ] ] : [] )
			.concat( [ [ 'label/checksum', row.sha256 ] ] )
			.forEach( function( line ) {
				fields.appendChild( Nino.adminUi.readLine( { label : w( line[0] ), value : line[1], mono : line[0] === 'label/checksum' } ) );
			} );

		return fields;
	};

	const input = function( field ) {
		return field.querySelector('input, select');
	};

	// The save of the record: what differs is posted, then the row and the directory are brought to what is saved
	const save = function( row, ctx, done ) {

		const values = { name : input( ctx.name ).value.trim() };

		if( ctx.folder !== undefined )
			values.folder = input( ctx.folder ).value;

		if( ctx.visible !== undefined )
			values.visible = input( ctx.visible ).checked === true;

		if( ctx.final !== undefined )
			values.final = input( ctx.final ).checked === true;

		const changed = data.changes( row, values );

		if( Object.keys( changed ).length === 0 )
			return done( true, { row : row } );

		actions.save( Object.assign( { id : row.id, notify : false }, changed ), function( status, response ) {

			if( status !== 200 || response === null )
				return done( false, { error : Nino.adminUi.api.errorText( status, response, '/_admin/files/error/save' ) } );

			const was = fileOf( row.key );
			const now = Object.assign( {}, was, response.file, { key : was.key, url : was.url, by : was.by } );

			// A file that went to another folder is no row of this one any more; the others are brought to what is saved, and the directory stays where it is
			S.files = S.files.map( function( file ) { return file.key === row.key ? now : file } ).filter( function( file ) { return file.folder === S.loaded } );

			const rows = data.fileRows( S.files );

			S.view.update( rows, row.key );
			done( true, { row : rows.find( function( each ) { return each.key === row.key } ) ?? data.fileRows( [ now ] )[0] } );
		} );
	};

	/**
	 *	What takes a file away asks the question - nested, it stands on the record - and acts
	 *
	 *	@param		{Object}		row
	 *	@param		{Element}		opener
	 *	@param		{Function}	done
	 *
	 *	@return		void
	 */
	const removeFile = function( row, opener, done ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: w('confirm/delete-one'),
			details	: [ row.name ],
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			nested	: true,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return done( null );

				actions.delete( row.id, function( status, response ) {

					if( status !== 200 )
						return done( false, { error : Nino.adminUi.api.errorText( status, response, '/_admin/files/error/delete' ) } );

					S.files = S.files.filter( function( file ) { return file.key !== row.key } );
					S.view.setRows( data.fileRows( S.files ), { keep : true } );
					done( true );
				} );
			},
		} );
	};

	// The directory of the open folder, and the record that opens a file of it - one record for the panel, over the directory that is on screen
	const proxy = {
		rows : function() { return S.view === null ? [] : S.view.rows() },
		reveal : function( key ) { if( S.view !== null ) S.view.reveal( key ) },
		focusRow : function( key ) {

			// The record that stood over the folder is closed: an address that was typed is written over by the one of the directory of the screen, which is the folder's while the folder is shown
			const at = Nino.admin.router.current();

			if( S.loaded !== '' && at.panel === 'files' && at.parts.length === 0 )
				Nino.admin.router.set( 'files', viewParts( S.loaded ) );

			if( S.view !== null )
				S.view.focusRow( key );
		},
	};

	const makeRecord = function() {

		return Nino.adminUi.record( {
			screen	: 'files',
			directory	: proxy,
			keyOf		: function( row ) { return row.key },
			find		: function( key ) {
				const file = fileOf( key );
				return file === null ? null : data.fileRows( [ file ] )[0];
			},
			title		: function( row ) { return row.name },
			subtitle	: function( row ) { return row.id },
			body		: body,
			save		: save,
			saveNext	: true,
			danger	: { label : w('label/delete-file'), run : removeFile },
			actions	: [
				{ label : w('label/download'), hidden : function( row ) { return row.state === 'removed' }, run : function( row ) { download( row ) } },
				{ label : w('label/remove'), hidden : function( row ) { return row.state === 'removed' }, run : function( row ) { takeFiles( 'remove', [ row.key ], null, true ) } },
			],
			missing	: function( key ) {

				// A key that could be a file is said, any other is no file and is only written over; the record writes the address of the screen after this, so the folder's is written after that
				if( FILE_KEY.test( key ) === true )
					missingFile();

				Promise.resolve().then( function() {

					const at = Nino.admin.router.current();

					if( S.loaded !== '' && at.panel === 'files' && at.parts.length === 0 )
						Nino.admin.router.set( 'files', viewParts( S.loaded ) );
				} );
			},
		} );
	};

	const fileCells = {

		size : function( value ) {
			return el( 'span', '', data.size( value ) );
		},

		state : function( value, row ) {

			const badge = el( 'span', 'nino-admin-badge', ( value === 'visible' ? '✓ ' : '● ' )+ w( 'state/'+ value ) );

			badge.dataset.state = value === 'visible' ? 'ok' : 'muted';

			if( value === 'removed' && row.gone !== '' )
				badge.title = Nino.adminUi.format( w('label/removed'), Nino.adminUi.dateText( row.gone ) );

			return badge;
		},
	};

	// One folder: its way back, the limits, the directory of its files and the bar with what is done to the folder and the upload
	const drawFolder = function() {

		const folder = folderOf( S.loaded );
		const mount = dc.getElementById('files-folder');

		if( folder === null )
			return;

		// The same folder, drawn again: the rows are replaced and the directory stays as the person left it
		if( S.filesFor === folder.id && S.view !== null && mount.firstChild !== null ) {
			S.view.setRows( data.fileRows( S.files ), { keep : true } );
			return;
		}

		if( S.filesFor !== folder.id )
			S.viewState = {};

		S.filesFor = folder.id;
		mount.innerHTML = '';
		S.view = null;

		const view = el( 'div' );

		view.appendChild( Nino.adminUi.contextBar( {
			back	: { to : w('nav'), href : Nino.admin.router.href( 'files', [] ), onClick : function() { go( [] ) } },
			title	: folder.name,
			code	: folder.group,
		} ) );

		S.failed = el( 'div' );
		S.failed.setAttribute( 'role', 'alert' );
		view.appendChild( S.failed );

		const tableMount = el( 'div' );

		view.appendChild( tableMount );

		// What a selection does is held to what its rows may do: a file whose bytes are gone cannot be shown, moved or removed
		const canIn = function( what ) {
			return function( key ) {
				const file = fileOf( key );
				return file !== null && data.can( data.fileRows( [ file ] )[0] ).indexOf( what ) !== -1;
			};
		};

		// What gets the focus back after a dialog of the selection: the search of the directory - the bar that opened it is gone with the selection
		const back = returnTo( function() { return mount.querySelector('input[type=search]') } );

		S.view = Nino.adminUi.directory( {
			mount		: tableMount,
			rowKey	: 'key',
			state		: S.viewState,
			onState	: function( now ) { S.viewState = now },
			labels	: Object.assign( { search : w('label/search-files'), empty : w('empty/files'), noMatch : w('empty/filter-files') }, sortLabels() ),
			filters	: [ { id : 'files-state', label : w('label/state'), value : '', options : [ { value : '', label : w('filter/all-states') }, { value : 'visible', label : w('state/visible') }, { value : 'hidden', label : w('state/hidden') }, { value : 'removed', label : w('state/removed') } ],
				match : function( row, value ) { return value === '' || row.state === value } } ],
			columns	: [
				{ key : 'name', label : w('label/name'), type : 'string', cell : 'clamp', fill : true, primary : true },
				{ key : 'size', label : w('label/size'), type : 'integer', render : fileCells.size, narrow : false },
				{ key : 'who', label : w('label/by'), type : 'string', cell : 'clamp', max : '14rem', narrow : 'second' },
				{ key : 'day', label : w('label/day'), type : 'string', cell : 'date' },
				{ key : 'state', label : w('label/state'), type : 'string', render : fileCells.state },
			],
			searchText : function( row ) { return row.who },
			select	: {
				label		: w('select/page-files'),
				rowLabel	: function( row ) { return Nino.adminUi.format( w('select/row'), row.name ) },
				actions	: [
					{ key : 'show', label : w('label/show'), run : function( keys ) { showFiles( keys.filter( canIn( 'show' ) ), true, back ) } },
					{ key : 'hide', label : w('label/hide'), run : function( keys ) { showFiles( keys.filter( canIn( 'hide' ) ), false, back ) } },
					{ key : 'move', label : w('label/move'), run : function( keys ) { moveFiles( keys.filter( canIn( 'move' ) ), folder, back ) } },
					{ key : 'remove', label : w('label/remove'), danger : true, run : function( keys ) { takeFiles( 'remove', keys.filter( canIn( 'remove' ) ), back ) } },
					{ key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : function( keys ) { takeFiles( 'delete', keys, back ) } },
				],
			},
			actions	: function( row ) {

				const buttons = [ Nino.adminUi.iconButton( 'pencil', Nino.adminUi.format( w('label/edit-file'), row.name ), function( event ) { S.record.open( row.key, { opener : event.currentTarget } ) }, { small : true } ) ];

				if( data.can( row ).indexOf('download') !== -1 )
					buttons.push( Nino.adminUi.button( w('label/download'), function() { download( row ) }, { small : true } ) );

				buttons.push( Nino.adminUi.button( t('/_admin/common/label/delete'), function() { takeFiles( 'delete', [ row.key ], null ) }, { small : true, kind : 'danger' } ) );

				return buttons;
			},
			onOpen	: function( row ) { S.record.open( row.key, {} ) },
		} );

		S.view.setRows( data.fileRows( S.files ) );

		const uploadButton = Nino.adminUi.button( w('label/upload'), function() { upload( folder, uploadButton ) }, { kind : 'primary', id : 'files-upload' } );
		const rename = Nino.adminUi.button( w('label/rename'), function() { renameFolder( folder, rename ) }, { id : 'files-rename' } );
		const mark = Nino.adminUi.button( w( folder.upload === true ? 'label/unmark' : 'label/mark' ), function() { markFolder( folder ) }, { id : 'files-mark' } );
		const drop = Nino.adminUi.button( w('label/delete-folder'), function() { removeFolders( [ folder.id ], drop ) }, { kind : 'danger', id : 'files-delete-folder' } );

		view.appendChild( Nino.adminUi.actionBar( { list : true, danger : drop, secondary : [ rename, mark ], primary : uploadButton } ).el );
		mount.appendChild( view );

		if( S.record === null )
			S.record = makeRecord();
	};

	// The shell shows the panel - first, on entering it again, and on every step of the history that it answers. The record reads the address itself and follows one of this
	// panel's; where it had nothing to say and no record is open, the address names a folder: that folder is read when it is not the one in hand
	Nino.admin.files = {

		showCurrent : function() {

			if( S.ready === false || addressFolder() !== S.loaded )
				return load();

			if( S.record === null || ( S.record.follow() === false && S.record.isOpen() === false ) )
				show();
		},

		// The data and the actions are the panel's own, the part a directory of the kernel takes over
		model : data,
		actions : actions,
	};

} )( window, document );
