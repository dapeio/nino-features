/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Members\Admin		The feature's /_admin panel, "Members": the accounts of the members' area in the
 *													standard directory of the workbench (Nino.adminUi.directory(): the group to narrow to,
 *													the search, the state as a select, the table with its box to choose rows by, and
 *													the paging) and each account in the dialog of a record (Nino.adminUi.record(), address
 *													#members/members/<id>): its name, its group and whether it is active, with a link to
 *													sign in, "sign out everywhere" and the deletion in its bar. A new member is made in a
 *													dialog of its own (#members/members/@new): it is no entry of the directory yet. See
 *													Modules\Members\Admin beside this file.
 *
 *													Three parts, one under the other: the DATA (pure functions that know accounts and
 *													nothing of the screen - what an account is called, the row it is drawn as, what a
 *													form posts), the ACTIONS (what is asked of the server, each ending in a callback with
 *													its status and answer) and the SCREEN, which draws the first with the blocks and
 *													calls the second. The list is read whole - every row has the key that names it in the
 *													address - and the directory filters, sorts and pages in the browser.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( name ) { return t( '/_admin/members/'+ name ) };
	const el = Nino.adminUi.el;

	// A slug is never a lone hyphen, so this cannot be a group: the filter for the accounts that have none
	const NONE = '-';

	// The field of a form a refusal of the server belongs to, by its code; where the code names none the first field is the one
	const FIELD = { members_invalid : 'email', members_taken : 'email', members_long : 'name' };

	// --- the data: accounts, and what is made of them -----------------------------------------------------------------------------

	const data = {

		/**
		 *	What names an account in a row and in a question: the name, the address where it has none
		 *
		 *	@param		{Object}	account
		 *
		 *	@return		{string}
		 */
		label : function( account ) {
			return account.name !== '' ? account.name : account.email;
		},

		/**
		 *	The row of an account as the directory draws it: what the server gave, the state as a badge with its sign - the sign says it, not the
		 *	colour alone - and the last sign-in as a clock the workbench writes as a date. The server writes the time in UTC, 'Y-m-d\TH:i:s\Z':
		 *	dateText() takes the clock without the zone sign; an account that never signed in has none
		 *
		 *	@param		{Object}	account						As the list answers, with its key
		 *
		 *	@return		{Object}
		 */
		rowOf : function( account ) {

			const active = account.active === true;

			return Object.assign( {}, account, {
				key : account.key ?? account.id,
				// What names the account: its name, its address where it has none - and the address as a line of its own only where the name stands: one never says it twice
				label : data.label( account ),
				addr : account.name !== '' ? account.email : '',
				status : { text : ( active === true ? '✓ ' : '● ' )+ w( active === true ? 'state/active' : 'state/inactive' ), state : active === true ? 'ok' : 'muted' },
				seen : typeof account.login === 'string' ? account.login.replace( /Z$/, '' ) : '',
			} );
		},

		/**
		 *	The options of the group select: every group in use, and the accounts without one where there are such and
		 *	others have one - a lone option is no choice, so a project without a group has none
		 *
		 *	@param		{Array}		groups						The groups in use, sorted
		 *	@param		{Array}		rows
		 *
		 *	@return		{Array}										[ { value, label } ], [] where there is nothing to choose
		 */
		groupOptions : function( groups, rows ) {

			if( groups.length === 0 )
				return [];

			const options = [ { value : '', label : w('filter/all') } ].concat( groups.map( function( group ) { return { value : group, label : group } } ) );

			if( rows.some( function( row ) { return row.group === '' } ) === true )
				options.push( { value : NONE, label : w('filter/none') } );

			return options;
		},

		/**
		 *	Whether a row passes the group the directory is narrowed to
		 *
		 *	@param		{Object}	row
		 *	@param		{string}	value						'' for every group, NONE for those without one
		 *
		 *	@return		{boolean}
		 */
		inGroup : function( row, value ) {
			return value === '' || ( value === NONE ? row.group === '' : row.group === value );
		},

		/**
		 *	Whether a row passes the state the directory is narrowed to
		 *
		 *	@param		{Object}	row
		 *	@param		{string}	value						'' for every state, 'active' or 'inactive'
		 *
		 *	@return		{boolean}
		 */
		inState : function( row, value ) {
			return value === '' || ( value === 'active' ) === ( row.active === true );
		},

		/**
		 *	What the dialog of a new member posts: the invitation as the way it goes out - 'mail', 'link' (made and shown once)
		 *	or 'none'
		 *
		 *	@param		{Object}	values						What formDialog() read
		 *
		 *	@return		{Object}
		 */
		addPayload : function( values ) {
			return { email : String( values.email ), name : String( values.name ), group : String( values.group ), invite : values.invite !== 'none', notify : values.invite === 'mail' };
		},

		/**
		 *	What the record of an account posts: the name, the group and whether the account is active - never the address
		 *
		 *	@param		{string}	id
		 *	@param		{Object}	values
		 *
		 *	@return		{Object}
		 */
		savePayload : function( id, values ) {
			return { id : id, name : String( values.name ), group : String( values.group ), active : values.active === true };
		},

		/**
		 *	What sending a link posts: 'mail' sends it, 'link' makes it and shows it once
		 *
		 *	@param		{string}	id
		 *	@param		{string}	how
		 *
		 *	@return		{Object}
		 */
		linkPayload : function( id, how ) {
			return { id : id, notify : how !== 'link' };
		},
	};

	// --- the actions: what is asked of the server ------------------------------------------------------------------------------------

	const call = function( action, payload, done ) { Nino.adminUi.api.call( 'members/'+ action, payload, done ) };

	const actions = {
		list    : function( done ) { call( 'list', {}, done ) },
		add     : function( payload, done ) { call( 'add', payload, done ) },
		save    : function( payload, done ) { call( 'save', payload, done ) },
		remove  : function( id, done ) { call( 'delete', { id : id }, done ) },
		link    : function( payload, done ) { call( 'link', payload, done ) },
		signOut : function( id, done ) { call( 'signout', id === '' ? { all : true } : { id : id }, done ) },
	};

	// --- the screen --------------------------------------------------------------------------------------------------------------------

	// What the panel keeps: the rows as the server gave them, the groups in use, the directory and the record over it, and whether the dialog of a new member is open
	const S = { rows : [], groups : [], mount : null, room : null, note : null, directory : null, record : null, shape : '', adding : false, loading : false };

	const find = function( key ) {
		return S.rows.find( function( row ) { return row.key === key } ) ?? null;
	};

	const failure = function( status, response, fallback ) {
		return Nino.adminUi.api.errorText( status, response, fallback );
	};

	// A move of the person inside the panel is a new entry of the address; what closes the dialog of a new member puts the address back to the directory
	const address = function( parts, replace ) {

		if( typeof Nino.admin.router !== 'object' || Nino.admin.router === null )
			return;

		if( replace === true )
			Nino.admin.router.set( 'members', parts );
		else
			Nino.admin.router.go( 'members', parts );
	};

	// The sentence of a refusal at the field of the form it belongs to
	const refused = function( ctl, first, status, response, fallback ) {
		ctl.fail( FIELD[ ( response ?? {} ).code ] ?? first, failure( status, response, fallback ) );
	};

	// The rows were changed by the panel itself: the directory draws the row of the key anew and marks it fresh
	const changed = function( account ) {

		const row = data.rowOf( account );

		S.rows = S.rows.map( function( one ) { return one.key === row.key ? row : one } );
		S.directory.update( S.rows, row.key );

		return row;
	};

	// An account is gone: its row leaves the rows, and the directory keeps what the person chose
	const gone = function( keys ) {

		S.rows = S.rows.filter( function( one ) { return keys.indexOf( one.key ) === -1 } );
		S.directory.setRows( S.rows, { keep : true } );
	};

	/**
	 *	The link an owner asked for without a mail: shown once, in a read-only field with a button that copies it, and the sentence that it is
	 *	shown now only. Nothing of the screen holds it once the dialog is closed
	 *
	 *	@param		{string}	link
	 *	@param		{Element}	opener
	 *
	 *	@return		void
	 */
	const showLink = function( link, opener ) {

		const field = Nino.adminUi.textField( { label : w('label/the-link'), value : link, id : 'members-link-field' } );
		const input = field.querySelector('input');
		const content = el( 'div' );

		input.readOnly = true;
		content.appendChild( field );
		content.appendChild( Nino.adminUi.button( w('label/copy'), function() {

			Nino.clipboard( link ).then( function( ok ) {

				if( ok === true )
					Nino.adminUi.say( w('msg/copied') );
				else {
					input.focus();
					input.select();
				}
			} );
		}, { kind : 'secondary', small : true, id : 'members-link-copy' } ) );

		Nino.adminUi.choiceDialog( {
			title		: w('dialog/link'),
			message	: w('hint/link-once'),
			content	: content,
			size		: 'form',
			opener	: opener,
			// The dialog that made the link is closing and says so in an event of its own, until when a second one is refused: this one stands on it
			nested	: true,
			choices	: [ { value : 'close', label : t('/_admin/common/label/close'), kind : 'primary' } ],
			onChoose	: function() {},
		} );
	};

	/**
	 *	The dialog of a new member: the address, the name, the group and what happens with an invitation. It is no entry of the directory - there is
	 *	no row to open - so it is a dialog of its own, with the address #members/members/@new
	 *
	 *	@param		{Element}	opener
	 *
	 *	@return		void
	 */
	const addMember = function( opener ) {

		S.adding = true;
		address( [ Nino.admin.router.word( 'new' ) ] );

		Nino.adminUi.formDialog( {
			title		: w('dialog/new'),
			fields	: [
				{ key : 'email', label : w('label/email'), type : 'email', required : true, maxLength : 254, autocomplete : 'off' },
				{ key : 'name', label : w('label/name'), maxLength : 120, autocomplete : 'off' },
				{ key : 'group', label : w('label/group'), help : w('hint/group'), maxLength : 60, autocomplete : 'off' },
				{ key : 'invite', label : w('label/invite'), help : w('hint/invite'), type : 'select', value : 'mail', options : [
					{ value : 'mail', label : w('invite/mail') },
					{ value : 'link', label : w('invite/link') },
					{ value : 'none', label : w('invite/none') },
				] },
			],
			submit	: w('label/create'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onCancel	: function() { S.adding = false; address( [], true ) },
			onSubmit	: function( values, ctl ) {

				actions.add( data.addPayload( values ), function( status, response ) {

					// The account is there as well where only the invitation failed (a mail that did not go, the cap of mails): the list is read again then
					if( status !== 200 || response === null ) {

						if( response !== null && typeof response === 'object' && ( response.code === 'members_mail' || response.code === 'members_ratelimit' ) ) {
							ctl.close();
							S.adding = false;
							address( [], true );
							load( function() { S.directory.notice( failure( status, response, '/_admin/members/error/add' ) ) } );
							return;
						}

						return refused( ctl, 'email', status, response, '/_admin/members/error/add' );
					}

					ctl.close();
					S.adding = false;
					address( [], true );

					const row = data.rowOf( Object.assign( { key : response.account.id }, response.account ) );

					S.rows = S.rows.concat( [ row ] ).sort( function( a, b ) { return data.label( a ).toLowerCase() < data.label( b ).toLowerCase() ? -1 : 1 } );
					S.groups = Array.from( new Set( S.rows.map( function( one ) { return one.group } ).filter( function( group ) { return group !== '' } ) ) ).sort();
					redraw();
					S.directory.reveal( row.key );
					Nino.adminUi.say( w( typeof response.link === 'string' ? 'msg/invited-quiet' : ( response.invited === true ? 'msg/invited' : 'msg/added' ) ) );

					if( typeof response.link === 'string' )
						showLink( response.link, opener );
				} );
			},
		} );
	};

	/**
	 *	A link to sign in, sent from a row or from the record: by mail, or made and shown once. The question is a dialog of the workbench that stands
	 *	on the record where there is one
	 *
	 *	@param		{Object}	row
	 *	@param		{Element}	opener
	 *
	 *	@return		void
	 */
	const sendLink = function( row, opener ) {

		// An account that has been switched off since the record was drawn is told by the server, in a sentence of its own
		Nino.adminUi.choiceDialog( {
			title		: w('dialog/link-send'),
			message	: data.label( row )+ ' - '+ w('hint/notify'),
			size		: 'form',
			opener	: opener,
			nested	: true,
			escape	: 'cancel',
			choices	: [
				{ value : 'cancel', label : t('/_admin/common/label/cancel'), kind : 'secondary' },
				{ value : 'link', label : w('invite/link-only'), kind : 'secondary' },
				{ value : 'mail', label : w('invite/mail'), kind : 'primary' },
			],
			onChoose	: function( how ) {

				if( how !== 'mail' && how !== 'link' )
					return;

				actions.link( data.linkPayload( row.key, how ), function( status, response ) {

					if( status !== 200 || response === null )
						return S.directory.notice( failure( status, response, '/_admin/members/error/link' ) );

					Nino.adminUi.say( w( typeof response.link === 'string' ? 'msg/sent-quiet' : 'msg/sent' ) );

					if( typeof response.link === 'string' )
						showLink( response.link, opener );
				} );
			},
		} );
	};

	/**
	 *	The deletion of one account, after the question of the workbench; on the record the question stands on it (nested)
	 *
	 *	@param		{Object}		row
	 *	@param		{Element}		opener
	 *	@param		{boolean}		nested
	 *	@param		{Function}	done						done( true ) where it went, done( false, { error } ) where it failed, done( null ) where the answer was no
	 *
	 *	@return		void
	 */
	const removeMember = function( row, opener, nested, done ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: Nino.adminUi.format( w('confirm/delete'), data.label( row ) ),
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			nested	: nested,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return done( null );

				actions.remove( row.key, function( status, response ) {

					if( status !== 200 )
						return done( false, { error : failure( status, response, '/_admin/members/error/delete' ) } );

					gone( [ row.key ] );
					done( true );
				} );
			},
		} );
	};

	// The focus after rows are gone: the next row of what is in view, else the one before, else the search
	const focusAfter = function( order, went ) {

		const left = S.directory.rows().map( function( row ) { return row.key } );
		const at = went.length === 0 ? -1 : Math.min.apply( null, went.map( function( key ) { return order.indexOf( key ) } ) );
		const next = order.slice( at ).find( function( key ) { return left.indexOf( key ) !== -1 } ) ?? order.slice( 0, Math.max( at, 0 ) ).reverse().find( function( key ) { return left.indexOf( key ) !== -1 } );

		if( next !== undefined )
			return S.directory.focusRow( next );

		const search = Array.from( S.mount.querySelectorAll('input') ).find( function( input ) { return input.type === 'search' } );

		if( search !== null )
			search.focus();
	};

	/**
	 *	The deletion of the accounts that are chosen: one question that says how many, then one delete for each - the server checks the right and the
	 *	token of every one, and every one tells '/members/removed' - one after the other
	 *
	 *	@param		{Array}		keys
	 *
	 *	@return		void
	 */
	const removeMany = function( keys ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-many-title'),
			message	: Nino.adminUi.format( w('confirm/delete-many'), String( keys.length ) ),
			yes			: w('confirm/delete-many-title'),
			no			: t('/_admin/common/label/cancel'),
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				const went = [];
				const order = S.directory.rows().map( function( row ) { return row.key } );
				let failed = null;

				const next = function( at ) {

					if( at >= keys.length || failed !== null ) {

						gone( went );
						S.directory.clearSelection();
						focusAfter( order, went );

						if( failed !== null )
							S.directory.notice( failed );
						else
							Nino.adminUi.say( Nino.adminUi.format( w('msg/deleted-many'), String( went.length ) ) );

						return;
					}

					actions.remove( keys[at], function( status, response ) {

						if( status !== 200 )
							failed = failure( status, response, '/_admin/members/error/delete' );
						else
							went.push( keys[at] );

						next( at + 1 );
					} );
				};

				next( 0 );
			},
		} );
	};

	/**
	 *	Sign out: every member at once - a question of the level big - or one account everywhere
	 *
	 *	@param		{Object|null}	row						The account, null for everybody
	 *	@param		{Element}			opener
	 *	@param		{boolean}			nested
	 *
	 *	@return		void
	 */
	const signOut = function( row, opener, nested ) {

		Nino.adminUi.confirm( 'big', {
			title		: w( row === null ? 'label/signout' : 'label/signout-one' ),
			message	: row === null ? w('confirm/signout') : Nino.adminUi.format( w('confirm/signout-one'), data.label( row ) ),
			yes			: w( row === null ? 'label/signout' : 'label/signout-one' ),
			no			: t('/_admin/common/label/cancel'),
			kind		: 'danger',
			nested	: nested,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				actions.signOut( row === null ? '' : row.key, function( status, response ) {

					if( status !== 200 )
						return S.directory.notice( failure( status, response, '/_admin/members/error/signout' ) );

					Nino.adminUi.say( w( row === null ? 'msg/signedout' : 'msg/signedout-one' ) );
				} );
			},
		} );
	};

	// --- the record: one account ------------------------------------------------------------------------------------------------------

	// The content of the record: the address as a line to read, and the name, the group and the tick of whether the account is active. The body puts the controls in ctx
	// for the save to read
	const body = function( row, ctx ) {

		const fields = el( 'div', 'nino-admin-dialog-fields' );
		const name = Nino.adminUi.textField( { label : w('label/name'), value : row.name, maxLength : 120, counter : true } );
		const group = Nino.adminUi.textField( { label : w('label/group'), help : w('hint/group'), value : row.group, maxLength : 60 } );
		const active = Nino.adminUi.checkField( { label : w('label/active'), note : w('hint/active'), checked : row.active === true } );

		fields.appendChild( Nino.adminUi.readLine( { label : w('label/email'), value : row.email } ) );
		fields.appendChild( name );
		fields.appendChild( group );
		fields.appendChild( active );
		ctx.fields = { name : name, group : group, active : active };

		return fields;
	};

	// The button of the bar that was pressed: what gets the focus back where the question that it opened is over. Not every browser focuses a button by a click, so it is looked for by its name
	const pressed = function( ctx, label ) {
		return Array.from( ( ctx.bar.el ?? ctx.bar ).querySelectorAll('button') ).find( function( button ) { return button.textContent === label } ) ?? document.activeElement;
	};

	const save = function( row, ctx, done ) {

		const values = {
			name : ctx.fields.name.querySelector('input').value,
			group : ctx.fields.group.querySelector('input').value,
			active : ctx.fields.active.querySelector('input').checked === true,
		};

		actions.save( data.savePayload( row.key, values ), function( status, response ) {

			if( status !== 200 || response === null ) {

				const text = failure( status, response, '/_admin/members/error/save' );

				if( ( response ?? {} ).code === 'members_long' )
					Nino.adminUi.fieldError( ctx.fields.name, ctx.fields.name.querySelector('input'), 'members-name-error', text );

				return done( false, { error : text } );
			}

			done( true, { row : changed( Object.assign( { key : row.key }, response.account ) ) } );
		} );
	};

	// --- the directory ---------------------------------------------------------------------------------------------------------------------

	const rowActions = function( row ) {

		const label = data.label( row );
		const list = [ Nino.adminUi.iconButton( 'pencil', Nino.adminUi.format( w('row/open'), label ), function( event ) {
			S.record.open( row.key, { opener : event.currentTarget } );
		}, { small : true } ) ];

		if( row.active === true )
			list.push( Nino.adminUi.iconButton( 'mail', Nino.adminUi.format( w('row/link'), label ), function( event ) { sendLink( row, event.currentTarget ) }, { small : true } ) );

		list.push( Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('row/delete'), label ), function( event ) {

			const opener = event.currentTarget;
			const keys = S.directory.rows().map( function( one ) { return one.key } );

			removeMember( row, opener, false, function( went, result ) {

				if( went === true ) {
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );

					// The focus goes on to the next row of what is in view, else the one before, else the search
					const around = keys.filter( function( key ) { return key !== row.key } );
					const next = around[ Math.min( keys.indexOf( row.key ), around.length - 1 ) ];

					if( next !== undefined )
						S.directory.focusRow( next );
				}
				else if( went === false )
					S.directory.notice( result.error );
			} );
		}, { small : true, danger : true } ) );

		return list;
	};

	const build = function( mount, state ) {

		const options = data.groupOptions( S.groups, S.rows );

		S.directory = Nino.adminUi.directory( {
			mount : mount,
			rowKey : 'key',
			state : state,
			columns : [
				{ key : 'label', label : w('label/name'), type : 'string', cell : 'clamp', fill : true, primary : true },
				{ key : 'addr', label : w('label/email'), type : 'string', cell : 'clamp', max : '16rem', narrow : 'second' },
				{ key : 'group', label : w('label/group'), type : 'string', cell : 'text' },
				{ key : 'status', label : w('label/state'), type : 'string', cell : 'badge' },
				{ key : 'seen', label : w('label/login'), type : 'datetime', cell : 'date' },
			],
			labels : { search : w('label/search'), empty : w('empty'), noMatch : w('empty/filter'), sortBy : w('sort/by'), sortNone : w('sort/none'), sortUp : w('sort/up'), sortDown : w('sort/down') },
			category : options.length === 0 ? undefined : {
				id : 'members-group',
				label : w('label/filter'),
				value : '',
				options : options,
				match : data.inGroup,
			},
			filters : [ {
				id : 'members-state',
				label : w('label/state'),
				value : '',
				options : [ { value : '', label : w('state/all') }, { value : 'active', label : w('state/active') }, { value : 'inactive', label : w('state/inactive') } ],
				match : data.inState,
			} ],
			actions : rowActions,
			onOpen : function( row ) { S.record.open( row.key, {} ) },
			select : {
				label : w('select/page'),
				rowLabel : function( row ) { return Nino.adminUi.format( w('select/row'), data.label( row ) ) },
				actions : [ { key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : removeMany } ],
			},
		} );

		S.record = Nino.adminUi.record( {
			screen : 'members',
			directory : S.directory,
			keyOf : function( row ) { return row.key },
			find : find,
			title : data.label,
			subtitle : function( row ) { return row.name === '' ? '' : row.email },
			body : body,
			save : save,
			saveNext : true,
			danger : { label : w('confirm/delete-title'), run : function( row, opener, done ) { removeMember( row, opener, true, done ) } },
			actions : [
				{ label : w('label/link'), run : function( row, ctx ) { sendLink( row, pressed( ctx, w('label/link') ) ) }, hidden : function( row ) { return row.active !== true } },
				{ label : w('label/signout-one'), run : function( row, ctx ) { signOut( row, pressed( ctx, w('label/signout-one') ), true ) }, hidden : function( row ) { return row.active !== true } },
			],
			// An address that names a part that can be an account - twelve hex characters - and finds none says so; any other part is the directory, without a word
			missing : function( key ) { if( /^[0-9a-f]{12}$/.test( String( key ) ) === true ) S.directory.notice( w('missing') ) },
		} );
	};

	// The room of the panel: the directory, and under it the bar with the buttons that are not of one account
	const frame = function() {

		S.mount.innerHTML = '';

		const note = el( 'div' );
		const directory = el( 'div' );
		const bar = el( 'div' );

		S.note = note;
		S.room = directory;
		note.id = 'members-note';
		directory.id = 'members-directory';
		bar.id = 'members-bar';
		S.mount.appendChild( note );
		S.mount.appendChild( directory );
		S.mount.appendChild( bar );

		const make = Nino.adminUi.button( w('label/new'), function() { addMember( make ) }, { kind : 'primary', id : 'members-new' } );
		const out = Nino.adminUi.button( w('label/signout'), function() { signOut( null, out, false ) }, { kind : 'secondary', id : 'members-signout' } );

		bar.appendChild( Nino.adminUi.actionBar( { list : true, secondary : [ out ], primary : make } ).el );

		return directory;
	};

	// What the server says of the sign-in form: where it cannot let the client go before the work after the answer, a request for an address that has an account is told from
	// one that has none by the time its answer takes, and the form hides nothing. A notice at the top, for as long as that is so
	const drawNote = function( response ) {

		S.note.innerHTML = '';

		// Where the promise holds: always said - as a notice where the server does not let the client go first, as a quiet line with the two limits where it does
		if( response.hides === false )
			S.note.appendChild( Nino.adminUi.notice( w('note/server') ) ).id = 'members-note-server';
		else
			S.note.appendChild( Nino.adminUi.hint( w('note/server-ok') ) ).id = 'members-note-server';

		if( response.braked === true )
			S.note.appendChild( Nino.adminUi.notice( w('note/braked') ) ).id = 'members-note-braked';

		// The mail cap of the kernel acts before the setting where it is the lower: said with its number
		if( typeof response.cap === 'number' && response.cap > 0 )
			S.note.appendChild( Nino.adminUi.notice( Nino.adminUi.format( w('note/cap'), String( response.cap ) ) ) ).id = 'members-note-cap';
	};

	// The directory as it stands, drawn again - a group that comes or goes is another select - with what the person had chosen
	const redraw = function() {

		const shape = S.groups.join('|')+ ( S.rows.some( function( row ) { return row.group === '' } ) === true ? '|'+ NONE : '' );

		if( S.directory !== null && S.shape === shape ) {
			S.directory.setRows( S.rows, { keep : true } );
			return;
		}

		let state;

		if( S.directory !== null ) {

			state = S.directory.state();

			// A group that is gone is no filter any more
			if( state.category !== '' && state.category !== NONE && S.groups.indexOf( state.category ) === -1 )
				state = Object.assign( {}, state, { category : '' } );

			S.record.close();
		}

		build( S.room ?? frame(), state );
		S.shape = shape;
		S.directory.setRows( S.rows, { keep : true } );
	};

	// "Always re-fetch" is the recipe's: the rows are read on entering the panel and when an address leads to none of its records
	const load = function( then ) {

		if( S.loading === true )
			return;

		S.loading = true;
		S.mount = S.mount ?? document.getElementById('members-list');

		// The first time the room and the directory are drawn at once, empty, with the sentence that the members are loading
		if( S.directory === null ) {
			build( frame() );
			S.directory.loading( w('loading') );
		}

		actions.list( function( status, response ) {

			S.loading = false;

			if( status !== 200 || response === null )
				return S.directory === null ? Nino.adminUi.showError( S.mount, status, response, '/_admin/members/error/load' ) : S.directory.fail( status, response, '/_admin/members/error/load' );

			S.rows = ( Array.isArray( response.rows ) === true ? response.rows : [] ).map( data.rowOf );
			S.groups = Array.isArray( response.groups ) === true ? response.groups : [];
			drawNote( response );
			redraw();
			follow();

			if( typeof then === 'function' )
				then();
		} );
	};

	// A step through the history that leaves the dialog of a new member: the dialog is asked to close as Escape asks it - one with nothing typed in it closes, one with input
	// asks what to do with it, which is the question of the workbench - and where it stays, the address is written to what the screen shows
	const leaveAdding = function() {

		const open = Array.from( document.querySelectorAll('dialog[open]') );
		const top = open[ open.length - 1 ];

		if( top !== undefined && typeof wn.KeyboardEvent === 'function' ) {

			top.dispatchEvent( new wn.KeyboardEvent( 'keydown', { key : 'Escape', bubbles : true, cancelable : true } ) );

			if( document.querySelector('dialog[open]') === null ) {
				S.adding = false;
				address( [], true );
				return;
			}
		}

		if( Nino.admin.router.refuse() === false )
			address( [ Nino.admin.router.word( 'new' ) ], true );
	};

	// What the address names. The dialog of a new member is the panel's own: #members/members/@new. An entry of the directory is the record's: it reads the address itself.
	// A dialog of a new member that is open is the screen: a step through the history that leaves it is taken back - what is typed in it is not lost - and where it cannot be, the
	// address is written to what the screen shows
	const follow = function() {

		if( typeof Nino.admin.router !== 'object' || Nino.admin.router === null )
			return false;

		const router = Nino.admin.router;
		const at = router.current();
		const wanted = at.panel === 'members' && at.parts.length === 1 && router.isWord( at.parts, 0, 'new' ) === true;

		if( S.adding === true ) {

			if( wanted === false )
				leaveAdding();

			return true;
		}

		if( wanted === true ) {
			addMember( null );
			return true;
		}

		const handled = S.record.follow();

		// Nothing is open: the address is the directory's, whatever stood after it - a part this panel has not, an entry that is gone, a stray character - written over by the one of the directory
		if( at.panel === 'members' && S.record.isOpen() === false )
			address( [], true );

		return handled;
	};

	Nino.admin.members = {

		/**
		 *	The shell shows the panel - first, on entering it again, and on every step of the history that it answers. The record reads the address itself and follows one of this panel's; the
		 *	rows are read again only where it had nothing to say
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( S.directory === null )
				return load();

			if( follow() === false && S.record.isOpen() === false )
				load();
		},

		// The data and the actions are the panel's own, the part the screen is drawn from: public so that they can be tried without a page
		model : data,
		actions : actions,
	};

} )(window);
