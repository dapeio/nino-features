/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Sign\Admin			The feature's /_admin panel, "Signatures": the requests for a signature in the standard
 *													directory of the workbench (Nino.adminUi.directory(): the state to narrow to, the search, the
 *													table with its box to choose rows by and the paging; under 40 rem a card for each request) and each
 *													request read in the dialog of a record (Nino.adminUi.record(), address #sign/sign/<id>): the proof of
 *													the signing, the two PDFs, and in its bar Remind, Cancel, Ask again and the deletion. Asking a member
 *													is a view of its own, #sign/sign/@new: its fields are the member, the template, the free texts the
 *													template asks for - as many as it has - and a title, which is more than a dialog holds. See
 *													Modules\Sign\Admin beside this file.
 *
 *													Three parts, one under the other: the DATA (pure functions that know requests and nothing of
 *													the screen), the ACTIONS (what is asked of the server, each ending in a callback with its status and
 *													answer) and the SCREEN, which draws the first with the blocks and calls the second. The list is read
 *													whole - every row has the key that names it in the address - and the directory filters, sorts and
 *													pages in the browser.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( name ) { return t( '/_admin/sign/'+ name ) };
	const el = Nino.adminUi.el;

	// The states in which a request can be reminded and cancelled: it waits, or its time is up
	const WAITING = [ 'requested', 'viewed', 'expired' ];

	// The field of the form a refusal of the server belongs to, by its code
	const FIELD = { sign_template : 'template', sign_member : 'template', sign_account : 'account', sign_long : 'title' };

	// --- the data: requests, and what is made of them ----------------------------------------------------------------------------

	const data = {

		/**
		 *	What names the member of a request in a row and in a question: the name, the address where there is no name, a word for a member who is gone
		 *
		 *	@param		{Object}	request
		 *
		 *	@return		{string}
		 */
		who : function( request ) {

			const member = request.member !== null && typeof request.member === 'object' ? request.member : {};

			return typeof member.label === 'string' && member.label !== '' ? member.label : w('label/member-gone');
		},

		/**
		 *	A time of the server - 'Y-m-d\TH:i:s\Z', UTC - as the minute it is, in words that say which clock it is: 2026-11-02 10:15 UTC
		 *
		 *	@param		{string|null}	time
		 *
		 *	@return		{string}									'' for none
		 */
		when : function( time ) {
			return typeof time === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test( time ) === true ? time.slice( 0, 10 )+ ' '+ time.slice( 11, 16 )+ ' UTC' : '';
		},

		/**
		 *	The time a link is good until, which the server gives as seconds since 1970
		 *
		 *	@param		{number}	seconds
		 *
		 *	@return		{string}									'' for none
		 */
		until : function( seconds ) {
			return Number.isInteger( seconds ) === true && seconds > 0 ? data.when( new Date( seconds * 1000 ).toISOString() ) : '';
		},

		/**
		 *	An address of the server that is a path of this site and nothing else, to be the href of a link
		 *
		 *	@param		{*}				url
		 *
		 *	@return		{string}									'' where it is none
		 */
		safe : function( url ) {
			return typeof url === 'string' && /^\/(?!\/)[^\s\\]*$/.test( url ) === true ? url : '';
		},

		/**
		 *	The row of a request as the directory draws it: what the server gave, the state as a badge with its sign - the sign says it, not the colour alone -,
		 *	the member as the second line of a card, and the day it was asked as a date the workbench writes. The key is a string whatever the id is made of
		 *
		 *	@param		{Object}	request						As the list answers, with its key
		 *
		 *	@return		{Object}
		 */
		rowOf : function( request ) {

			const mark = { signed : '✓ ', declined : '● ', cancelled : '● ', expired : '● ', changed : '● ', requested : '● ', viewed : '● ' }[request.status] ?? '● ';

			return Object.assign( {}, request, {
				key : String( request.key ?? request.id ),
				state : request.status,
				who : data.who( request ),
				waiting : WAITING.indexOf( request.status ) !== -1,
				stateText : mark+ w( 'state/'+ request.status ),
				status : { text : mark+ w( 'state/'+ request.status ), state : { signed : 'ok', requested : 'warn', viewed : 'warn', declined : 'error', changed : 'warn' }[request.status] ?? 'muted' },
				asked : typeof request.created === 'string' ? request.created.replace( /Z$/, '' ) : '',
			} );
		},

		/**
		 *	Whether a row passes the state the directory is narrowed to
		 *
		 *	@param		{Object}	row
		 *	@param		{string}	value						'' for every state, 'waiting' for the requests that wait (or ran out), else a state
		 *
		 *	@return		{boolean}
		 */
		inState : function( row, value ) {
			return value === '' || ( value === 'waiting' ? row.waiting === true : row.state === value );
		},

		/**
		 *	What the form of a new request posts: the member, the template, the free texts - only those the template has - the title and whether the member is mailed
		 *
		 *	@param		{Object}		values					{ account, template, title, notify }
		 *	@param		{Object}		inputs					name => text
		 *
		 *	@return		{Object}
		 */
		askPayload : function( values, inputs ) {
			return { account : String( values.account ), template : String( values.template ), inputs : inputs, title : String( values.title ), notify : values.notify === true };
		},
	};

	// --- the actions: what is asked of the server ------------------------------------------------------------------------------------

	const call = function( action, payload, done ) { Nino.adminUi.api.call( 'sign/'+ action, payload, done ) };

	const actions = {
		list    : function( done ) { call( 'list', {}, done ) },
		options : function( done ) { call( 'options', {}, done ) },
		request : function( payload, done ) { call( 'request', payload, done ) },
		remind  : function( id, notify, done ) { call( 'remind', { id : id, notify : notify }, done ) },
		cancel  : function( id, done ) { call( 'cancel', { id : id }, done ) },
		again   : function( id, notify, done ) { call( 'again', { id : id, notify : notify }, done ) },
		remove  : function( id, done ) { call( 'delete', { id : id }, done ) },
	};

	// --- the screen ----------------------------------------------------------------------------------------------------------------------

	// What the panel keeps: the rows as the server gave them, the days a link is good for, the directory and the record over it, and the view that asks
	const S = { rows : [], days : 14, mount : null, room : null, listMount : null, formMount : null, directory : null, record : null, asking : false, loading : false, options : null, form : null };

	const find = function( key ) {
		return S.rows.find( function( row ) { return row.key === key } ) ?? null;
	};

	const failure = function( status, response, fallback ) {
		return Nino.adminUi.api.errorText( status, response, fallback );
	};

	// A move of the person inside the panel is a new entry of the address; what keeps the address true to the screen replaces one
	const address = function( parts, replace ) {

		if( typeof Nino.admin.router !== 'object' || Nino.admin.router === null )
			return;

		if( replace === true )
			Nino.admin.router.set( 'sign', parts );
		else
			Nino.admin.router.go( 'sign', parts );
	};

	// The rows were changed by the panel itself: the directory draws the row of the key anew and marks it fresh
	const changed = function( request ) {

		const row = data.rowOf( request );

		S.rows = S.rows.map( function( one ) { return one.key === row.key ? row : one } );
		S.directory.update( S.rows, row.key );

		return row;
	};

	// Read again after what the server did changed more than a field: the rows stay where the person put them
	const reload = function( then ) {

		actions.list( function( status, response ) {

			if( status !== 200 || response === null )
				return S.directory.fail( status, response, '/_admin/sign/error/load' );

			S.rows = ( Array.isArray( response.rows ) === true ? response.rows : [] ).map( data.rowOf );
			S.days = Number.isInteger( response.days ) === true ? response.days : 14;
			S.directory.setRows( S.rows, { keep : true } );

			if( typeof then === 'function' )
				then();
		} );
	};

	/**
	 *	The link an owner asked for without a mail: shown once, in a read-only field with a button that copies it, and the sentence that it is
	 *	shown now only. Nothing of the screen holds it once the dialog is closed
	 *
	 *	@param		{string}		link
	 *	@param		{Element}		opener
	 *
	 *	@return		void
	 */
	const showLink = function( link, opener, nested ) {

		const field = Nino.adminUi.textField( { label : w('label/link'), value : link, id : 'sign-link-field' } );
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
		}, { kind : 'secondary', small : true, id : 'sign-link-copy' } ) );

		Nino.adminUi.choiceDialog( {
			title		: w('dialog/link'),
			message	: w('hint/link-once'),
			content	: content,
			size		: 'form',
			opener	: opener,
			nested	: nested === true,
			choices	: [ { value : 'close', label : t('/_admin/common/label/close'), kind : 'primary' } ],
			onChoose	: function() {},
		} );
	};

	/**
	 *	Send the link of a request again, or ask again for one that changed: the question says whether the member is mailed or the link is made and shown once.
	 *	The dialog stands on the record where there is one
	 *
	 *	@param		{Object}		row
	 *	@param		{string}		kind						'remind' or 'again'
	 *	@param		{Element}		opener
	 *
	 *	@return		void
	 */
	const resend = function( row, kind, opener ) {

		Nino.adminUi.choiceDialog( {
			title		: w( kind === 'remind' ? 'dialog/remind' : 'dialog/again' ),
			message	: row.who+ ' - '+ w('hint/notify'),
			size		: 'form',
			opener	: opener,
			nested	: true,
			escape	: 'cancel',
			choices	: [
				{ value : 'cancel', label : t('/_admin/common/label/cancel'), kind : 'secondary' },
				{ value : 'link', label : w('label/link-only'), kind : 'secondary' },
				{ value : 'mail', label : w('label/send-mail'), kind : 'primary' },
			],
			onChoose	: function( how ) {

				if( how !== 'mail' && how !== 'link' )
					return;

				actions[kind]( row.key, how === 'mail', function( status, response ) {

					// The new request is there as well where only its mail could not be sent
					if( status !== 200 || response === null ) {

						if( kind === 'again' && response !== null && typeof response === 'object' && ( response.code === 'sign_mail' || response.code === 'sign_ratelimit' ) )
							reload();

						return S.directory.notice( failure( status, response, '/_admin/sign/error/'+ kind ) );
					}

					Nino.adminUi.say( w( typeof response.link === 'string' ? 'msg/'+ kind+ '-quiet' : 'msg/'+ kind ) );

					if( typeof response.link === 'string' )
						showLink( response.link, opener, true );

					reload();
				} );
			},
		} );
	};

	/**
	 *	Take a request back, after the question of the workbench; on the record the question stands on it (nested)
	 *
	 *	@param		{Object}		row
	 *	@param		{Element}		opener
	 *	@param		{boolean}		nested
	 *	@param		{Function}	done						done( true ) where it went, done( false, { error } ) where it failed, done( null ) where the answer was no
	 *
	 *	@return		void
	 */
	const cancelRequest = function( row, opener, nested, done ) {

		Nino.adminUi.confirm( 'big', {
			title		: w('dialog/cancel'),
			message	: Nino.adminUi.format( w('confirm/cancel'), row.who ),
			yes			: w('dialog/cancel'),
			no			: t('/_admin/common/label/cancel'),
			nested	: nested,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return done( null );

				actions.cancel( row.key, function( status, response ) {

					if( status !== 200 )
						return done( false, { error : failure( status, response, '/_admin/sign/error/cancel' ) } );

					done( true, { row : changed( Object.assign( {}, row, { status : 'cancelled', state : 'cancelled' } ) ) } );
				} );
			},
		} );
	};

	/**
	 *	The deletion of one request, after the question of the workbench; on the record the question stands on it (nested)
	 *
	 *	@param		{Object}		row
	 *	@param		{Element}		opener
	 *	@param		{boolean}		nested
	 *	@param		{Function}	done						done( true ) where it went, done( false, { error } ) where it failed, done( null ) where the answer was no
	 *
	 *	@return		void
	 */
	const removeRequest = function( row, opener, nested, done ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: Nino.adminUi.format( w('confirm/delete'), row.who ),
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			nested	: nested,
			opener	: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return done( null );

				actions.remove( row.key, function( status, response ) {

					if( status !== 200 )
						return done( false, { error : failure( status, response, '/_admin/sign/error/delete' ) } );

					S.rows = S.rows.filter( function( one ) { return one.key !== row.key } );
					S.directory.setRows( S.rows, { keep : true } );
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

		const search = Array.from( S.listMount.querySelectorAll('input') ).find( function( input ) { return input.type === 'search' } );

		if( search !== undefined )
			search.focus();
	};

	/**
	 *	What is done to the rows that are chosen: one question that says how many, then one call for each - the server checks the right and the token of every one -
	 *	one after the other; the first that fails ends the run. Only the keys that are chosen are sent: nothing here is "all"
	 *
	 *	@param		{string}		what						remind, cancel or delete
	 *	@param		{Array}			keys
	 *
	 *	@return		void
	 */
	const many = function( what, keys ) {

		const rows = keys.map( find ).filter( function( row ) { return row !== null && ( what === 'delete' || row.waiting === true ) } );

		if( rows.length === 0 )
			return;

		Nino.adminUi.confirm( what === 'delete' ? 'destructive' : 'big', {
			title		: w( 'bulk/'+ what ),
			message	: Nino.adminUi.format( w( 'confirm/'+ what+ '-many' ), String( rows.length ) ),
			yes			: w( 'bulk/'+ what ),
			no			: t('/_admin/common/label/cancel'),
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				const order = S.directory.rows().map( function( row ) { return row.key } );
				const went = [];
				let failed = null;

				const next = function( at ) {

					if( at >= rows.length || failed !== null ) {

						if( what === 'delete' ) {
							S.rows = S.rows.filter( function( one ) { return went.indexOf( one.key ) === -1 } );
							S.directory.setRows( S.rows, { keep : true } );
						}

						S.directory.clearSelection();

						if( what === 'delete' )
							focusAfter( order, went );

						const finish = function() {
							if( failed !== null )
								S.directory.notice( failed );
							else
								Nino.adminUi.say( Nino.adminUi.format( w( 'msg/'+ what+ '-many' ), String( went.length ) ) );
						};

						return what === 'delete' ? finish() : reload( finish );
					}

					const answer = function( status, response ) {

						if( status !== 200 )
							failed = failure( status, response, '/_admin/sign/error/'+ what );
						else
							went.push( rows[at].key );

						next( at + 1 );
					};

					if( what === 'remind' )
						actions.remind( rows[at].key, true, answer );
					else if( what === 'cancel' )
						actions.cancel( rows[at].key, answer );
					else
						actions.remove( rows[at].key, answer );
				};

				next( 0 );
			},
		} );
	};

	// --- the record: one request, read --------------------------------------------------------------------------------------------------

	// A link to a PDF of a request, opened in a window of its own
	const documentLink = function( row, kind ) {

		const href = data.safe( row.urls[kind] );

		if( row.files[kind] !== true || href === '' )
			return null;

		const link = el( 'a', '', w( kind === 'shown' ? 'label/shown' : 'label/signed-document' ) );

		link.href = href;
		link.target = '_blank';
		link.rel = 'noopener';

		return link;
	};

	const body = function( row ) {

		const lines = el( 'div', 'nino-admin-record-lines' );
		const record = row.record !== null && typeof row.record === 'object' ? row.record : {};
		const member = row.member !== null && typeof row.member === 'object' ? row.member : {};
		const add = function( label, value, options ) { lines.appendChild( Nino.adminUi.readLine( Object.assign( { label : label, value : value }, options ) ) ) };

		S.directory.notice('');

		add( w('label/account'), typeof member.email === 'string' && member.email !== '' ? ( member.name !== '' ? member.name+ ' ('+ member.email+ ')' : member.email ) : w('label/member-gone') );
		add( w('label/template'), row.templateName );
		add( w('label/state'), w( 'state/'+ row.state ) );
		[ [ 'created', row.created ], [ 'viewed', row.viewed ], [ 'signed', row.signed ], [ 'declined', row.declined ], [ 'changed', row.changed ] ].forEach( function( each ) {
			if( data.when( each[1] ) !== '' )
				add( w( 'label/'+ each[0] ), data.when( each[1] ) );
		} );

		if( row.waiting === true )
			add( w('label/expires'), data.until( row.expires ) );

		if( row.state === 'signed' ) {
			add( w('label/address'), record.ip ?? '', { mono : true } );
			add( w('label/agent'), record.agent ?? '' );
			add( w('label/method'), row.method !== '' ? w( 'method/'+ row.method ) : '' );
			lines.appendChild( Nino.adminUi.readLine( { label : w('label/consent'), node : Nino.adminUi.messageBox( record.consent ?? '', { label : w('label/consent') } ) } ) );
			add( w('label/hash-shown'), row.hashShown, { mono : true } );
			add( w('label/hash-signed'), row.hashSigned, { mono : true } );
		}
		else
			add( w('label/hash-shown'), row.hashShown, { mono : true } );

		[ 'shown', 'signed' ].forEach( function( kind ) {

			const link = documentLink( row, kind );

			if( link !== null )
				lines.appendChild( Nino.adminUi.readLine( { label : w( 'label/document-'+ kind ), node : link } ) );
		} );

		return lines;
	};

	// The button of the bar that was pressed: what gets the focus back where the question that it opened is over
	const pressed = function( ctx, label ) {
		return Array.from( ( ctx.bar.el ?? ctx.bar ).querySelectorAll('button') ).find( function( button ) { return button.textContent === label } ) ?? document.activeElement;
	};

	// --- the directory ---------------------------------------------------------------------------------------------------------------------

	const rowActions = function( row ) {

		const list = [ Nino.adminUi.iconButton( 'eye', Nino.adminUi.format( w('row/open'), row.title ), function( event ) {
			S.record.open( row.key, { opener : event.currentTarget } );
		}, { small : true } ) ];

		if( row.waiting === true )
			list.push( Nino.adminUi.iconButton( 'mail', Nino.adminUi.format( w('row/remind'), row.title ), function( event ) { resend( row, 'remind', event.currentTarget ) }, { small : true } ) );

		list.push( Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('row/delete'), row.title ), function( event ) {

			const keys = S.directory.rows().map( function( one ) { return one.key } );

			removeRequest( row, event.currentTarget, false, function( went, result ) {

				if( went === true ) {
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );

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

	const STATES = [ 'requested', 'viewed', 'signed', 'declined', 'cancelled', 'expired', 'changed' ];

	const build = function( mount, state ) {

		S.directory = Nino.adminUi.directory( {
			mount : mount,
			rowKey : 'key',
			state : state,
			columns : [
				{ key : 'title', label : w('label/title'), type : 'string', cell : 'clamp', fill : true, primary : true },
				{ key : 'who', label : w('label/account'), type : 'string', cell : 'clamp', max : '14rem', narrow : 'second' },
				{ key : 'status', label : w('label/state'), type : 'string', cell : 'badge' },
				{ key : 'asked', label : w('label/created'), type : 'datetime', cell : 'date' },
			],
			labels : { search : w('label/search'), empty : w('empty'), noMatch : w('empty/filter'), sortBy : w('sort/by'), sortNone : w('sort/none'), sortUp : w('sort/up'), sortDown : w('sort/down') },
			filters : [ {
				id : 'sign-state',
				label : w('label/state'),
				value : '',
				options : [ { value : '', label : w('state/all') }, { value : 'waiting', label : w('state/waiting') } ].concat( STATES.map( function( state ) { return { value : state, label : w( 'state/'+ state ) } } ) ),
				match : data.inState,
			} ],
			searchText : function( row ) { return [ row.template, row.templateName, row.id, row.who, ( row.member ?? {} ).email ?? '' ].join(' ') },
			actions : rowActions,
			onOpen : function( row ) { S.record.open( row.key, {} ) },
			select : {
				label : w('select/page'),
				rowLabel : function( row ) { return Nino.adminUi.format( w('select/row'), row.title ) },
				actions : [
					{ key : 'remind', label : w('bulk/remind'), run : function( keys ) { many( 'remind', keys ) } },
					{ key : 'cancel', label : w('bulk/cancel'), run : function( keys ) { many( 'cancel', keys ) } },
					{ key : 'delete', label : t('/_admin/common/label/delete'), danger : true, run : function( keys ) { many( 'delete', keys ) } },
				],
			},
		} );

		S.record = Nino.adminUi.record( {
			screen : 'sign',
			readOnly : true,
			directory : S.directory,
			keyOf : function( row ) { return row.key },
			find : find,
			title : function( row ) { return row.title },
			subtitle : function( row ) { return row.id },
			body : body,
			actions : [
				{ label : w('dialog/remind'), kind : 'primary', run : function( row, ctx ) { resend( row, 'remind', pressed( ctx, w('dialog/remind') ) ) }, hidden : function( row ) { return row.waiting !== true } },
				{ label : w('label/again'), run : function( row, ctx ) { resend( row, 'again', pressed( ctx, w('label/again') ) ) }, hidden : function( row ) { return row.state !== 'changed' } },
				{ label : w('dialog/cancel'), run : function( row, ctx ) {
					cancelRequest( row, pressed( ctx, w('dialog/cancel') ), true, function( went, result ) {
						if( went === false )
							S.directory.notice( result.error );
					} );
				}, hidden : function( row ) { return row.waiting !== true } },
			],
			danger : { label : w('confirm/delete-title'), run : function( row, opener, done ) { removeRequest( row, opener, true, done ) } },
			// An address that names a part that can be a request - twelve hex characters - and finds none says so; any other part is the directory, without a word
			missing : function( key ) { if( /^[0-9a-f]{12}$/.test( String( key ) ) === true ) S.directory.notice( w('missing') ) },
		} );
	};

	// --- the view that asks for a signature ---------------------------------------------------------------------------------------------

	// What the form of a new request holds: the controls, so that a save reads them and a refusal marks them
	const drawInputs = function( key ) {

		const form = S.form;

		form.area.innerHTML = '';
		form.inputs = [];

		const template = S.options.templates.find( function( each ) { return each.key === key } );

		if( template === undefined )
			return;

		template.inputs.forEach( function( input ) {

			const label = input.label !== '' ? input.label : input.name;
			const field = Nino.adminUi[ input.multiline === true ? 'textareaField' : 'textField' ]( { label : label, required : input.required === true, maxLength : S.options.limits.input, id : 'sign-input-'+ input.name } );

			form.area.appendChild( field );
			form.inputs.push( { input : input, field : field, control : field.querySelector( input.multiline === true ? 'textarea' : 'input' ) } );
		} );
	};

	const formDirty = function() {
		return S.form !== null && ( S.form.account.value !== '' || S.form.template.value !== '' || S.form.notify.checked !== true || S.form.title.value !== '' || S.form.inputs.some( function( each ) { return each.control.value !== '' } ) );
	};

	const askSubmit = function( done ) {

		const form = S.form;

		if( form === null || form.busy === true )
			return done( false );

		Nino.adminUi.clearFieldErrors( form.form );

		const wrong = function( field, key, text ) {
			form.bar.line.fail( w('error/fields') );
			Nino.adminUi.fieldError( field.wrap, field.control, 'sign-'+ key+ '-error', text );
			field.control.focus();
			done( false );
		};

		if( form.account.value === '' )
			return wrong( { wrap : form.accountField, control : form.account }, 'account', Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/account') ) );

		if( form.template.value === '' )
			return wrong( { wrap : form.templateField, control : form.template }, 'template', Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/template') ) );

		const empty = form.inputs.find( function( each ) { return each.input.required === true && each.control.value.trim() === '' } );

		if( empty !== undefined )
			return wrong( { wrap : empty.field, control : empty.control }, 'input-'+ empty.input.name, Nino.adminUi.format( t('/_admin/common/error/missing'), empty.input.label !== '' ? empty.input.label : empty.input.name ) );

		const inputs = {};

		form.inputs.forEach( function( each ) { inputs[each.input.name] = each.control.value } );
		form.busy = true;
		form.bar.line.saving();

		actions.request( data.askPayload( { account : form.account.value, template : form.template.value, title : form.title.value, notify : form.notify.checked }, inputs ), function( status, response ) {

			form.busy = false;

			if( status !== 200 || response === null ) {

				form.bar.line.error( status, response, '/_admin/sign/error/ask' );

				const code = ( response ?? {} ).code;
				const field = { account : form.accountField, template : form.templateField, title : form.titleField }[FIELD[code]];

				if( field !== undefined )
					Nino.adminUi.fieldError( field, field.querySelector('input,select'), 'sign-'+ FIELD[code]+ '-error', failure( status, response, '/_admin/sign/error/ask' ) );

				// The request is there when only the mail could not be sent
				if( code === 'sign_mail' || code === 'sign_ratelimit' )
					reload();

				return done( false );
			}

			Nino.adminUi.say( w( typeof response.link === 'string' ? 'msg/asked-quiet' : 'msg/asked' ) );

			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.snapshot( 'sign' );

			leaveAsking( function() {
				reload( function() {
					if( typeof response.link === 'string' )
						showLink( response.link, null );
				} );
			} );

			done( true );
		} );
	};

	const leaveAsking = function( then ) {

		S.asking = false;
		S.form = null;
		address( [] );
		Nino.adminUi.backTo( 'sign-list', document.getElementById('sign-new') );

		if( typeof then === 'function' )
			then();
	};

	// The back link: input that nobody sent is asked about first; Cancel keeps the view, its address and what was typed
	const guardedLeave = function() {

		if( typeof Nino.admin.dirty !== 'object' ) {
			leaveAsking();
			return;
		}

		Nino.admin.dirty.guard( [ 'sign' ], function() {
			if( S.asking === true )
				leaveAsking();
		}, function() {} );
	};

	const drawAsking = function( opener ) {

		const mount = S.formMount;

		S.asking = true;
		mount.innerHTML = '';

		const back = Nino.adminUi.contextBar( {
			back : { to : w('nav'), onClick : function() { guardedLeave() } },
			title : w('label/new'),
		} );
		const form = el( 'form' );
		const set = el( 'fieldset' );
		const line = el( 'div', 'nino-admin-fieldgrid' );

		form.noValidate = true;
		set.appendChild( el( 'legend', '', w('label/new') ) );
		set.appendChild( line );
		form.id = 'sign-ask';

		const none = { value : '', label : w('label/choose') };
		const accountField = Nino.adminUi.selectField( { label : w('label/account'), required : true, options : [ none ].concat( S.options.accounts.map( function( each ) { return { value : each.id, label : each.name !== '' ? each.name+ ' ('+ each.email+ ')' : each.email } } ) ), value : '', id : 'sign-ask-account' } );
		const templateField = Nino.adminUi.selectField( { label : w('label/template'), required : true, options : [ none ].concat( S.options.templates.map( function( each ) { return { value : each.key, label : each.name } } ) ), value : '', id : 'sign-ask-template' } );
		const titleField = Nino.adminUi.textField( { label : w('label/title'), help : w('hint/title'), maxLength : S.options.limits.title, autocomplete : 'off', id : 'sign-ask-title' } );
		const notifyField = Nino.adminUi.switchField( { key : 'notify', checked : true, label : w('label/notify'), hint : w('hint/notify') } );
		const area = el( 'div', 'nino-admin-fieldgrid' );

		area.id = 'sign-inputs';
		line.appendChild( accountField );
		line.appendChild( templateField );
		line.appendChild( area );
		line.appendChild( titleField );
		line.appendChild( notifyField );

		const submit = Nino.adminUi.button( w('label/ask'), null, { type : 'submit', id : 'sign-ask-submit' } );
		const bar = Nino.adminUi.actionBar( { status : true, primary : submit } );

		form.appendChild( set );
		form.appendChild( bar.el );
		bar.line.bind( form );

		S.form = { form : form, bar : bar, busy : false, area : area, inputs : [], accountField : accountField, templateField : templateField, titleField : titleField,
			account : accountField.querySelector('select'), template : templateField.querySelector('select'), title : titleField.querySelector('input'), notify : notifyField.querySelector('input') };

		S.form.template.addEventListener( 'change', function() { drawInputs( S.form.template.value ) } );
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); askSubmit( function() {} ) } );

		mount.appendChild( back );
		mount.appendChild( el( 'p', 'nino-admin-hint', Nino.adminUi.format( w('hint/days'), String( S.days ) ) ) );
		mount.appendChild( form );

		if( typeof Nino.admin.dirty === 'object' ) {
			Nino.admin.dirty.register( 'sign', { isDirty : formDirty, save : askSubmit, discard : function() {
				if( S.form !== null ) {
					S.form.account.value = '';
					S.form.template.value = '';
					S.form.notify.checked = true;
					S.form.title.value = '';
					S.form.inputs.forEach( function( each ) { each.control.value = '' } );
				}
			} } );
		}

		Nino.adminUi.showLevel( 'sign-form', { focus : opener === undefined ? false : S.form.account } );
	};

	// The view that asks: what the form offers is read first - the members that are active and the templates that have a field for one
	const ask = function( opener ) {

		if( S.asking === true )
			return;

		address( [ Nino.admin.router.word( 'new' ) ] );

		actions.options( function( status, response ) {

			if( status !== 200 || response === null ) {
				address( [], true );
				return S.directory.notice( failure( status, response, '/_admin/sign/error/load' ) );
			}

			S.options = response;

			if( response.accounts.length === 0 || response.templates.length === 0 ) {
				address( [], true );
				return S.directory.notice( w( response.accounts.length === 0 ? 'hint/members' : 'hint/none' ) );
			}

			drawAsking( opener );
		} );
	};

	// --- the room of the panel and the address ----------------------------------------------------------------------------------------

	const frame = function() {

		S.listMount.innerHTML = '';

		const directory = el( 'div' );
		const bar = el( 'div' );

		S.room = directory;
		directory.id = 'sign-directory';
		bar.id = 'sign-bar';
		S.listMount.appendChild( directory );
		S.listMount.appendChild( bar );

		const make = Nino.adminUi.button( w('label/new'), function() { ask( make ) }, { kind : 'primary', id : 'sign-new' } );

		bar.appendChild( Nino.adminUi.actionBar( { list : true, primary : make } ).el );

		return directory;
	};

	const load = function() {

		if( S.loading === true )
			return;

		S.loading = true;
		S.listMount = S.listMount ?? document.getElementById('sign-list');
		S.formMount = S.formMount ?? document.getElementById('sign-form');

		if( S.directory === null ) {
			build( frame() );
			S.directory.loading( w('loading') );
		}

		actions.list( function( status, response ) {

			S.loading = false;

			if( status !== 200 || response === null )
				return S.directory.fail( status, response, '/_admin/sign/error/load' );

			S.rows = ( Array.isArray( response.rows ) === true ? response.rows : [] ).map( data.rowOf );
			S.days = Number.isInteger( response.days ) === true ? response.days : 14;
			S.directory.setRows( S.rows, { keep : true } );
			follow();
		} );
	};

	// What the address names. The view that asks is the panel's own: #sign/sign/@new. An entry of the directory is the record's: it reads the address itself. A step through
	// the history that leaves the view that asks is answered by showing the list again; an address that names nothing the panel has is written over by the directory's
	const follow = function() {

		if( typeof Nino.admin.router !== 'object' || Nino.admin.router === null )
			return false;

		const router = Nino.admin.router;
		const at = router.current();
		const wanted = at.panel === 'sign' && at.parts.length === 1 && router.isWord( at.parts, 0, 'new' ) === true;

		if( wanted === true && S.asking === false ) {
			ask();
			return true;
		}

		if( wanted === false && S.asking === true ) {

			// A step away from the view that asks keeps what was typed until it is answered: a move that is not made is taken back by the router through the history, and this panel writes no address of its own then
			router.leave( [ 'sign' ], formDirty(), function() {
				if( S.asking === false )
					return;

				S.asking = false;
				S.form = null;
				Nino.adminUi.showLevel( 'sign-list', { focus : false } );
				follow();
			}, function() {} );

			return true;
		}

		if( wanted === true )
			return true;

		const handled = S.record.follow();

		if( at.panel === 'sign' && S.record.isOpen() === false )
			address( [], true );

		return handled;
	};

	Nino.admin.sign = {

		/**
		 *	The shell shows the panel - first, on entering it again, and on every step of the history that it answers. The record reads the address itself and follows one of this
		 *	panel's; the rows are read again only where it had nothing to say
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
