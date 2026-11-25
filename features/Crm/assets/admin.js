/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Crm\Admin				The feature's /_admin panel, "CRM": the way to the surface of the CRM and the status of the core - the lines of what is there and what is
 *													missing in the standard directory of the workbench (Nino.adminUi.directory()), a button at a line that has a fix - and the data of
 *													packages that are gone, with the typed confirmation that takes it away. The panel has one screen and no record: the address is
 *													#crm/crm, and an address that says more is written over by it. See Modules\Crm\Admin beside this file.
 *
 *													Three parts, one under the other: the DATA (pure functions that know the lines and nothing of the screen - what a line is called,
 *													the badge of its state, the sentence it says, the row of an orphan), the ACTIONS (what is asked of the server, each ending in a
 *													callback with its status and answer) and the SCREEN, which draws the first with the blocks and calls the second.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( name ) { return t( '/_admin/crm/'+ name ) };

	// A word the panel has, or '' for one it has not: the text system answers a key it does not know with the key itself, which starts with a slash and is no word
	const has = function( name ) {

		const word = w( name );

		return word === '' || word.charAt(0) === '/' ? '' : word;
	};
	const el = Nino.adminUi.el;

	// The states that are as they should be, the ones that are a hint of the owner's own and the ones that are a fault; any other is something to do
	const FINE = [ 'ok', 'own' ];
	const QUIET = [ 'default' ];
	const FAULT = [ 'error', 'foreign', 'damaged', 'unwritable', 'invalid', 'failed', 'stuck', 'ahead', 'unraised' ];

	// The panel's state: what the server answered last, and the blocks drawn from it
	const S = { data : null, mount : null, status : null, orphans : null, bar : null };

	// --- the data: lines, orphans, and what is made of them -------------------------------------------------------------------

	const data = {

		/**
		 *	What a line is called: the name it gave with its state where it is a line of a package, else the word the panel has for the lines of the core, else its key
		 *
		 *	@param		{Object}	line
		 *
		 *	@return		{string}
		 */
		label : function( line ) {

			if( typeof line.label === 'string' && line.label !== '' )
				return line.label;

			return has( 'line/'+ line.key ) || line.key;
		},

		/**
		 *	The badge of a state: a sign and the word - the sign says it, not the colour alone - and the look the state has
		 *
		 *	@param		{string}	state
		 *
		 *	@return		{Object}
		 */
		badge : function( state ) {

			const look = FINE.indexOf( state ) !== -1 ? 'ok' : ( QUIET.indexOf( state ) !== -1 ? 'muted' : ( FAULT.indexOf( state ) !== -1 ? 'error' : 'warn' ) );
			const sign = look === 'ok' ? '✓ ' : ( look === 'muted' ? '○ ' : '● ' );
			return { text : sign + ( has( 'state/'+ state ) || state ), state : look };
		},

		/**
		 *	The sentence of a line in its state, with the values the state brought; a line of a package says its own, as a detail of its answer
		 *
		 *	@param		{Object}	line
		 *
		 *	@return		{string}
		 */
		detail : function( line ) {

			if( typeof line.detail === 'string' )
				return line.detail;

			// A line of a package says its package and the version it asked for
			if( typeof line.package === 'string' )
				return Nino.adminUi.format( has( 'detail/package-'+ line.state ) || '%s %s', line.package, String( line.version ) );

			const word = has( 'detail/'+ line.key+ '-'+ line.state );

			if( word === '' )
				return '';

			const lacks = Array.isArray( line.lacks ) === true ? line.lacks.join(', ') : '';
			// The values a sentence names, in the order it names them: it is written for one state of one line
			const values = {
				'schema-ok' : [ line.version ],
				'schema-none' : [ line.version ],
				'schema-behind' : [ line.stored, line.version, lacks ],
				'addresses-ok' : [ line.crm === '' ? '-' : line.crm, line.app === '' ? '-' : line.app ],
				'mail-failed' : [ line.failed, line.waiting ],
				'mail-stuck' : [ line.waiting ],
				'mail-head-partial' : [ lacks ],
			}[line.key+ '-'+ line.state] ?? [];

			return Nino.adminUi.format( word, ...values.map( String ) );
		},

		/**
		 *	The row of a line as the directory draws it
		 *
		 *	@param		{Object}	line							As the server answers it
		 *
		 *	@return		{Object}
		 */
		rowOf : function( line ) {
			return Object.assign( {}, line, { label : data.label( line ), status : data.badge( line.state ), note : data.detail( line ) } );
		},

		/**
		 *	The row of a package whose data stays behind (a package that is gone): its key, and what is in it - how many tables, which version
		 *
		 *	@param		{Object}	orphan
		 *
		 *	@return		{Object}
		 */
		orphanOf : function( orphan ) {
			return Object.assign( {}, orphan, { status : { text : '● '+ w('orphan/removed'), state : 'warn' } } );
		},
	};

	// --- the actions -----------------------------------------------------------------------------------------------------------

	const call = function( action, payload, done ) {
		Nino.adminUi.api.call( 'crm/'+ action, payload, done );
	};

	const failure = function( status, response, fallback ) {
		return Nino.adminUi.api.errorText( status, response, fallback );
	};

	const actions = {

		/**
		 *	Mend one line by its key
		 *
		 *	@param		{string}		key
		 *	@param		{Function}	done					( status, response )
		 *
		 *	@return		void
		 */
		mend : function( key, done ) {
			call( 'setup', { key : key }, done );
		},

		/**
		 *	Take away the data of a package, with its name typed as the proof of it
		 *
		 *	@param		{string}		key
		 *	@param		{string}		typed
		 *	@param		{Function}	done
		 *
		 *	@return		void
		 */
		drop : function( key, typed, done ) {
			call( 'drop-data', { key : key, typed : typed }, done );
		},
	};

	// --- the screen ------------------------------------------------------------------------------------------------------------

	// The address is the panel's one screen: #crm, or #crm/crm as it is typed. Whatever else the router reads there - a part behind it, a tab the panel has not - is written over by the one the screen has
	const address = function() {

		const router = Nino.admin.router;

		if( typeof router !== 'object' || router === null )
			return;

		// As the router reads it: the panel's own screen with no part behind it is the screen as it is; a tab the panel has not (known is false) or a part is written over
		const here = router.current();

		if( here.panel === 'crm' && ( here.known !== true || here.parts.length > 0 ) )
			router.set( 'crm', [] );
	};

	// The way to the surface: a link to the address the server gave, and a sentence - no button that leads nowhere - where that is no address
	const opening = function( open ) {

		const set = el( 'fieldset', 'nino-admin-card' );

		set.appendChild( el( 'legend', '', w('section/surface') ) );

		// A path of this site or an address of https, in one piece: what is none of them - a script, data, a host with no scheme - is no way to follow
		if( open !== undefined && open !== null && typeof open.address === 'string' && /^(?:https:\/\/[^\s\/\\@]+\/[^\s\\]*|\/(?![\/\\])[^\s\\]*)$/.test( open.address ) === true ) {

			const link = el( 'a', 'nino-admin-linkbutton', w('label/open') );

			link.href = open.address;
			link.id = 'crm-open';
			set.appendChild( link );
		}
		else
			set.appendChild( Nino.adminUi.hint( w('open/none') ) );

		return set;
	};

	// The focus after the screen is drawn again: on the row the person worked on where it is still there, else on the first row of the lines
	const focusLine = function( key ) {

		if( S.status !== null && S.status.focusRow( key ) === true )
			return;

		const first = S.status === null ? undefined : S.status.rows()[0];

		if( first !== undefined && S.status !== null )
			S.status.focusRow( first.key );
	};

	const mend = function( row, button ) {

		// A press while the answer is out is no second request
		if( button.disabled === true )
			return;

		button.disabled = true;

		actions.mend( row.key, function( status, response ) {

			button.disabled = false;

			if( status !== 200 || response === null ) {
				S.status.notice( failure( status, response, '/_admin/crm/error/failed' ) );
				return;
			}

			S.data = response;
			draw();
			Nino.adminUi.say( w('msg/mended') );
			focusLine( row.key );
		} );
	};

	const drop = function( row, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title : w('drop/title'),
			message : Nino.adminUi.format( w('drop/message'), row.package, String( row.tables ) ),
			yes : w('drop/yes'),
			no : t('/_admin/common/label/cancel'),
			kind : 'danger',
			typed : row.package,
			opener : opener,
			onChoose : function( ok, typed ) {

				if( ok !== true )
					return;

				actions.drop( row.package, String( typed ?? '' ), function( status, response ) {

					if( status !== 200 || response === null ) {
						S.orphans.notice( failure( status, response, '/_admin/crm/error/failed' ) );
						return;
					}

					S.data = response;
					draw();
					Nino.adminUi.say( w('msg/dropped') );

					// The row is gone with its data: the focus goes to the next one, else to the lines
					const next = S.orphans === null ? undefined : S.orphans.rows()[0];

					if( next !== undefined )
						S.orphans.focusRow( next.key );
					else
						focusLine( ( S.status.rows()[0] ?? {} ).key );
				} );
			},
		} );
	};

	const lines = function( mount ) {

		S.status = Nino.adminUi.directory( {
			mount : mount,
			rowKey : 'key',
			columns : [
				{ key : 'label', label : w('label/line'), type : 'string', cell : 'clamp', primary : true },
				{ key : 'status', label : w('label/state'), type : 'string', cell : 'badge' },
				{ key : 'note', label : w('label/detail'), type : 'string', cell : 'clamp', fill : true, narrow : 'second' },
			],
			labels : { search : w('label/search-lines'), empty : w('label/empty-lines'), noMatch : w('label/nomatch-lines') },
			actions : function( row ) {

				if( row.fixable !== true || FINE.indexOf( row.state ) !== -1 || row.hint === true )
					return [];

				const button = Nino.adminUi.button( w('label/mend'), function() { mend( row, button ) }, { kind : 'secondary', small : true, name : Nino.adminUi.format( w('label/mend-line'), row.label ) } );

				return [ button ];
			},
		} );

		S.status.setRows( S.data.lines.map( data.rowOf ) );
	};

	const orphans = function( mount ) {

		S.orphans = Nino.adminUi.directory( {
			mount : mount,
			rowKey : 'key',
			columns : [
				{ key : 'package', label : w('label/package'), type : 'string', cell : 'clamp', primary : true },
				{ key : 'status', label : w('label/state'), type : 'string', cell : 'badge' },
				{ key : 'tables', label : w('label/tables'), type : 'integer' },
				{ key : 'version', label : w('label/version'), type : 'integer' },
			],
			labels : { search : w('label/search-orphans'), empty : w('label/empty-orphans'), noMatch : w('label/nomatch-orphans') },
			actions : function( row ) {

				const button = Nino.adminUi.button( w('label/drop'), function() { drop( row, button ) }, { kind : 'danger', small : true, name : Nino.adminUi.format( w('label/drop-of'), row.package ) } );

				return [ button ];
			},
		} );

		S.orphans.setRows( S.data.orphans.map( data.orphanOf ) );
	};

	// The screen from what the server answered: the way to the surface, the lines, and - only where a package left data behind - that data
	const draw = function() {

		const mount = S.mount;

		mount.innerHTML = '';
		S.status = null;
		S.orphans = null;

		mount.appendChild( opening( S.data.open ) );

		const lineBox = el( 'div' );

		mount.appendChild( Nino.adminUi.sectionTitle( w('section/status'), 3 ) );
		mount.appendChild( lineBox );
		lines( lineBox );

		if( S.data.orphans.length > 0 ) {

			const orphanBox = el( 'div' );

			mount.appendChild( Nino.adminUi.sectionTitle( w('section/orphans'), 3 ) );
			mount.appendChild( orphanBox );
			orphans( orphanBox );
		}
	};

	const panel = Nino.adminUi.panel( {
		uri : 'crm',
		mount : 'crm-list',
		action : 'status',
		show : 'always',
		render : function( answer, owner ) {

			S.data = answer;
			S.mount = owner.mount();
			draw();
		},
	} );

	// Showing the panel keeps the address true to the screen first, then asks for the lines: they change while the owner is elsewhere
	const showing = panel.showCurrent;

	panel.showCurrent = function() {

		address();

		return showing.call( panel );
	};

	Nino.admin.crm = Object.assign( panel, { model : data, actions : actions } );

} )(window);
