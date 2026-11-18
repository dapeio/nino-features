/**
 *	Nino features
 *	admin.js		The panel of the Redirects feature: the rules in a table with a search and
 *							a dialog for one rule, the addresses nothing answered with the one button
 *							that makes a rule of one, and the probe as a view of its own (see
 *							Modules\Redirects\Admin beside this file). Built from the blocks of the
 *							workbench: panel(), tabs(), table(), formDialog(), confirm(), actionBar().
 *
 *							The addresses are #redirects (the rules), #redirects/missing (the
 *							addresses nothing answered) and #redirects/probe (the probe).
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// What the last answer of the server holds, and what the screen keeps of its own: the tab that is open, what each search holds, the sentences a save warned with
	const S = { rules : [], routes : [], misses : [], statuses : [], recording : true, limit : 0, notes : [], tab : 'rules', query : { rules : '', missing : '' }, warned : [] };

	let tabs = null;

	// The address of each tab and of the probe: the part after #redirects/
	const PARTS = { rules : [], missing : [ 'missing' ], probe : [ 'probe' ] };

	// A time the way the workbench writes a date: the German way round in German, the year first in any other language, no seconds
	const dateOf = function( value ) {

		const parts = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}:\d{2})/.exec( String( value ) );

		if( parts === null )
			return String( value ) === '' ? '\u2013' : String( value );

		return ( String( dc.documentElement.lang ).slice( 0, 2 ) === 'de' ? parts[3]+ '.'+ parts[2]+ '.'+ parts[1] : parts[1]+ '-'+ parts[2]+ '-'+ parts[3] )+ ', '+ parts[4];
	};

	const tabParts = function( key ) { return PARTS[key] };

	// --- one rule, in a dialog ------------------------------------------------------------------------------------------------------

	// The field a refusal is about: the code of the refusal says it
	const FIELD = { redirects_from : 'from', redirects_taken : 'from', redirects_loop_self : 'from', redirects_loop_subtree : 'from', redirects_loop_chain : 'to', redirects_to : 'to', redirects_status : 'status' };

	const pagesList = function() {

		const list = el( 'datalist' );

		list.id = 'redirects-pages';

		S.routes.forEach( function( route ) {

			const option = el( 'option' );

			option.value = route.path;

			if( route.label !== route.path )
				option.label = route.label;

			list.appendChild( option );
		} );

		return list;
	};

	/**
	 *	The dialog of one rule: four fields. A new rule is made in it, an old one edited - the old address a rule had when the dialog
	 *	opened is what the save renames from
	 *
	 *	@param		{Object}		rule				{ from, to, status, subtree, was }
	 *	@param		{Element}		[opener]		What had the focus
	 */
	const openRule = function( rule, opener ) {

		const body = el( 'div', 'nino-admin-dialog-fields' );

		const from = Nino.adminUi.textField( { key : 'from', label : t('/_admin/redirects/label/from'), help : t('/_admin/redirects/help/from'), value : rule.from, placeholder : t('/_admin/redirects/placeholder/from'), required : true, maxLength : 253 } );
		const to = Nino.adminUi.textField( { key : 'to', label : t('/_admin/redirects/label/to'), help : t('/_admin/redirects/help/to'), value : rule.to, placeholder : t('/_admin/redirects/placeholder/to'), required : true, maxLength : 2000 } );
		const status = Nino.adminUi.selectField( {
			key : 'status', label : t('/_admin/redirects/label/status'), help : t('/_admin/redirects/help/status'), value : String( rule.status ),
			options : S.statuses.map( function( code ) { return { value : String( code ), label : t( '/_admin/redirects/status/'+ code ) } } ),
		} );
		const subtree = Nino.adminUi.switchField( { key : 'subtree', checked : rule.subtree === true, label : t('/_admin/redirects/label/subtree'), help : t('/_admin/redirects/help/subtree') } );

		// The pages of this site a target is picked from: the free text stays what decides, an address of another site and a page that is not made yet are as good
		to.querySelector('input').setAttribute( 'list', 'redirects-pages' );

		[ from, to, status, subtree, pagesList() ].forEach( function( part ) { body.appendChild( part ) } );

		const controls = { from : [ from, from.querySelector('input') ], to : [ to, to.querySelector('input') ], status : [ status, status.querySelector('select') ] };
		const creating = rule.was === '';
		let errors = 0;

		Nino.adminUi.formDialog( {
			title		: t( creating === true ? '/_admin/redirects/dialog/create' : '/_admin/redirects/dialog/edit' ),
			body		: body,
			submit	: creating === true ? t('/_admin/redirects/dialog/create') : t('/_admin/common/label/save'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			onSubmit : function( values, ctl ) {

				Nino.adminUi.clearFieldErrors( body );

				const refuse = function( key, text ) {

					const field = controls[key] ?? controls.from;

					Nino.adminUi.fieldError( field[0], field[1], 'redirects-rule-error-'+ ( ++errors ), text );
					field[1].focus();
					ctl.idle();
				};

				const missing = function( key, label ) { return Nino.adminUi.format( t('/_admin/common/error/missing'), label ) };

				if( controls.from[1].value.trim() === '' )
					return refuse( 'from', missing( 'from', t('/_admin/redirects/label/from') ) );

				if( controls.to[1].value.trim() === '' )
					return refuse( 'to', missing( 'to', t('/_admin/redirects/label/to') ) );

				Nino.admin.redirects.call( 'save', {
					from : controls.from[1].value, to : controls.to[1].value, status : parseInt( controls.status[1].value, 10 ),
					subtree : subtree.querySelector('input').checked, was : rule.was,
				}, function( code, response ) {

					if( code !== 200 || response === null ) {
						refuse( FIELD[ ( response ?? {} ).code ] ?? 'from', Nino.adminUi.api.errorText( code, response, '/_admin/redirects/error/save' ) );
						return;
					}

					S.rules = response.rules || [];
					S.notes = response.notes || [];
					S.warned = response.warnings || [];
					// The address has an answer now, so it is not an address nothing answers - the server dropped it, and the screen agrees without asking again
					S.misses = S.misses.filter( function( miss ) { return miss.path !== response.saved } );
					ctl.close();
					draw();
					Nino.adminUi.say( t('/_admin/common/msg/saved') );
				} );
			},
		} );
	};

	const ruleOf = function( row ) {
		return { from : row.from, to : row.to, status : row.status, subtree : row.subtree === true, was : row.from };
	};

	// What a deletion leaves the focus on: the next row of the table, else the one before, else the sentence that says there is none
	const focusAround = function( mount, keys, gone ) {

		const at = keys.indexOf( gone );
		const around = keys.filter( function( key ) { return key !== gone } );
		const key = around[ Math.min( at, around.length - 1 ) ];
		const target = key === undefined ? mount.querySelector('.nino-admin-empty') : mount.querySelector( '[data-row="'+ CSS.escape( key ) +'"] button, [data-row="'+ CSS.escape( key ) +'"]' );

		if( target === null )
			return;

		if( target.hasAttribute('tabindex') === false && target.tagName !== 'BUTTON' )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	const removeRule = function( rule, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: t('/_admin/redirects/confirm/delete-title'),
			message	: Nino.adminUi.format( t('/_admin/redirects/confirm/delete'), rule.from ),
			yes			: t('/_admin/redirects/confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.redirects.call( 'delete', { from : rule.from }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( dc.getElementById('redirects-rules'), status, response, '/_admin/redirects/error/save' );

					const keys = S.rules.map( function( row ) { return row.from } );

					S.rules = response.rules || [];
					S.warned = [];
					draw();
					focusAround( dc.getElementById('redirects-rules'), keys, rule.from );
					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// --- the rules ----------------------------------------------------------------------------------------------------------------

	// What a rule's target leads to, said only where it leads somewhere worth a second look: nothing, a loop, a chain that is too long or another rule
	const answerCell = function( value ) {

		if( value !== 'nothing' && value !== 'loop' && value !== 'chain' && value !== 'rule' )
			return '';

		const badge = el( 'span', 'nino-admin-badge', '● '+ t( '/_admin/redirects/answer/'+ value ) );

		badge.dataset.state = value === 'rule' ? 'muted' : 'warn';

		return badge;
	};

	// The old address, and where a rule covers everything below it a badge says so; the address of a rule is code, not a word
	const fromCell = function( value, row ) {

		const cell = el( 'span', 'redirects-from' );

		cell.appendChild( el( 'code', '', value ) );

		if( row.subtree === true ) {
			cell.appendChild( el( 'br' ) );
			cell.appendChild( el( 'span', 'nino-admin-badge', t('/_admin/redirects/label/subtree-badge') ) );
		}

		return cell;
	};

	// How often a rule was followed, and when it was last: two facts in one column, so that the buttons of the row stay in sight
	const hitsCell = function( value, row ) {

		const cell = el( 'span', 'redirects-hits', String( value ) );

		if( value > 0 ) {
			cell.appendChild( el( 'br' ) );
			cell.appendChild( el( 'small', '', dateOf( row.last ) ) );
		}

		return cell;
	};

	// Where a rule sends, and under it the kind of the redirect: two facts in one column
	const toCell = function( value, row ) {

		const cell = el( 'span', 'redirects-to', value );

		cell.appendChild( el( 'br' ) );
		cell.appendChild( el( 'small', '', t( '/_admin/redirects/status/'+ row.status ) ) );

		return cell;
	};

	const rulesPane = function() {

		const pane = el( 'div', 'redirects-rules' );
		const mount = el( 'div' );

		pane.id = 'redirects-rules';

		// What the rules are is behind the (?) of the title of the section, not a paragraph over it
		const title = Nino.adminUi.sectionTitle( t('/_admin/redirects/tab/rules') );

		pane.appendChild( title );
		Nino.adminUi.sectionHelp( title, pane, t('/_admin/redirects/hint/rules') );

		S.notes.concat( S.warned ).forEach( function( note ) { pane.appendChild( Nino.adminUi.notice( note ) ) } );

		pane.appendChild( mount );

		if( S.rules.length === 0 )
			mount.appendChild( Nino.adminUi.emptyState( t('/_admin/redirects/empty/rules') ) );
		else
			Nino.adminUi.table( {
				mount		: mount,
				rowKey	: 'from',
				search	: true,
				query		: S.query.rules,
				onQuery	: function( value ) { S.query.rules = value },
				rows		: S.rules,
				labels	: { search : t('/_admin/redirects/label/search'), empty : t('/_admin/redirects/empty/rules'), noMatch : t('/_admin/redirects/empty/nomatch') },
				searchText : function( row ) { return t( '/_admin/redirects/status/'+ row.status ) },
				columns	: [
					{ key : 'from', label : t('/_admin/redirects/label/from'), type : 'string', render : fromCell },
					{ key : 'to', label : t('/_admin/redirects/label/to'), type : 'string', wrap : true, render : toCell },
					{ key : 'answer', label : t('/_admin/redirects/label/answer'), type : 'string', render : answerCell },
					{ key : 'hits', label : t('/_admin/redirects/label/hits'), type : 'integer', render : hitsCell },
				],
				actions	: function( row ) {

					const edit = Nino.adminUi.button( t('/_admin/redirects/label/edit'), function() { openRule( ruleOf( row ), edit ) }, { small : true } );
					const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { removeRule( row, drop ) }, { small : true, kind : 'danger' } );

					return [ edit, drop ];
				},
				onRowClick : function( row ) { openRule( ruleOf( row ), mount.querySelector( '[data-row="'+ CSS.escape( row.from ) +'"]' ) ) },
			} );

		const make = Nino.adminUi.button( t('/_admin/redirects/label/new'), function() { openRule( { from : '', to : '', status : 301, subtree : false, was : '' }, make ) }, { kind : 'primary' } );
		const probe = Nino.adminUi.button( t('/_admin/redirects/label/probe'), function() { go( 'probe' ) }, { kind : 'secondary' } );

		pane.appendChild( Nino.adminUi.actionBar( { list : true, secondary : [ probe ], primary : make } ).el );

		return pane;
	};

	// --- the addresses nothing answered ------------------------------------------------------------------------------------------

	const removeMisses = function( opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: t('/_admin/redirects/confirm/forget-title'),
			message	: t('/_admin/redirects/confirm/forget'),
			yes			: t('/_admin/redirects/confirm/forget-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.redirects.call( 'forget', {}, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( dc.getElementById('redirects-missing'), status, response, '/_admin/redirects/error/save' );

					S.misses = [];
					draw();
					Nino.adminUi.say( t('/_admin/redirects/msg/removed-all') );
				} );
			},
		} );
	};

	const missesPane = function() {

		const pane = el( 'div', 'redirects-misses' );
		const mount = el( 'div' );

		pane.id = 'redirects-missing';

		const title = Nino.adminUi.sectionTitle( t('/_admin/redirects/tab/missing') );

		pane.appendChild( title );
		Nino.adminUi.sectionHelp( title, pane, t('/_admin/redirects/hint/missing') );

		// What it comes to and stays in view: how many are kept
		if( S.recording === true && S.limit > 0 )
			pane.appendChild( el( 'p', 'nino-admin-hint', Nino.adminUi.format( t('/_admin/redirects/note/limit'), S.limit ) ) );

		// Remembering is off, so nothing new comes in: a state that stays until the setting is turned on, with the way to it
		if( S.recording === false )
			pane.appendChild( Nino.adminUi.notice( t('/_admin/redirects/hint/off'), { href : '#redirects-settings', label : t('/_admin/common/split/settings') } ) );

		pane.appendChild( mount );

		if( S.misses.length === 0 )
			mount.appendChild( Nino.adminUi.emptyState( t('/_admin/redirects/empty/missing') ) );
		else {

			Nino.adminUi.table( {
				mount		: mount,
				rowKey	: 'path',
				search	: true,
				query		: S.query.missing,
				onQuery	: function( value ) { S.query.missing = value },
				rows		: S.misses,
				labels	: { search : t('/_admin/redirects/label/search-missing'), empty : t('/_admin/redirects/empty/missing'), noMatch : t('/_admin/redirects/empty/nomatch-missing') },
				columns	: [
					{ key : 'path', label : t('/_admin/redirects/label/path'), type : 'string' },
					{ key : 'count', label : t('/_admin/redirects/label/count'), type : 'integer' },
					{ key : 'last', label : t('/_admin/redirects/label/last'), type : 'string', render : dateOf },
				],
				actions	: function( row ) {

					const make = Nino.adminUi.button( t('/_admin/redirects/label/make'), function() {
						// What is missing is the target, and that is the one thing a person has to decide here
						openRule( { from : row.path, to : '', status : 301, subtree : false, was : '' }, make );
					}, { small : true } );

					const drop = Nino.adminUi.button( t('/_admin/common/label/remove'), function() {

						Nino.admin.redirects.call( 'forget', { path : row.path }, function( status, response ) {

							if( status !== 200 || response === null )
								return Nino.adminUi.showError( dc.getElementById('redirects-missing'), status, response, '/_admin/redirects/error/save' );

							const keys = S.misses.map( function( miss ) { return miss.path } );

							S.misses = S.misses.filter( function( miss ) { return miss.path !== row.path } );
							draw();
							focusAround( dc.getElementById('redirects-missing'), keys, row.path );
							Nino.adminUi.say( t('/_admin/redirects/msg/removed') );
						} );
					}, { small : true } );

					return [ make, drop ];
				},
			} );

			// A bar of a list with the one button that is a danger: no main button of its own, so the list's bar, not actionBar()
			const all = Nino.adminUi.button( t('/_admin/redirects/label/forget'), function() { removeMisses( all ) }, { kind : 'danger' } );

			pane.appendChild( Nino.adminUi.listActions( [ all ] ) );
		}

		return pane;
	};

	// --- the probe, a view of its own ------------------------------------------------------------------------------------------

	const probeView = function() {

		const mount = dc.getElementById('redirects-probe');
		const view = el( 'div' );
		const form = el( 'form' );
		const set = el( 'fieldset' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );
		const result = el( 'div' );

		form.noValidate = true;
		result.setAttribute( 'role', 'status' );
		result.setAttribute( 'aria-live', 'polite' );

		view.appendChild( Nino.adminUi.contextBar( {
			back	: { to : mount, href : Nino.admin.router.href( 'redirects', PARTS.rules ), onClick : function() { go( 'rules' ) } },
			title	: t('/_admin/redirects/label/probe'),
		} ) );

		const path = Nino.adminUi.textField( { key : 'path', label : t('/_admin/redirects/label/path'), help : t('/_admin/redirects/help/probe'), placeholder : t('/_admin/redirects/placeholder/from'), required : true, maxLength : 253 } );
		const input = path.querySelector('input');

		set.appendChild( el( 'legend', '', t('/_admin/redirects/label/probe') ) );
		grid.appendChild( path );
		set.appendChild( grid );
		set.appendChild( result );
		form.appendChild( set );

		const run = Nino.adminUi.button( t('/_admin/redirects/label/probe-run'), null, { kind : 'primary', type : 'submit' } );

		form.appendChild( Nino.adminUi.actionBar( { primary : run } ).el );

		form.addEventListener( 'submit', function( event ) {

			event.preventDefault();
			Nino.adminUi.clearFieldErrors( form );
			result.textContent = '';

			const refuse = function( text ) {
				Nino.adminUi.fieldError( path, input, 'redirects-probe-error', text );
				input.focus();
			};

			if( input.value.trim() === '' )
				return refuse( Nino.adminUi.format( t('/_admin/common/error/missing'), t('/_admin/redirects/label/path') ) );

			Nino.admin.redirects.call( 'probe', { path : input.value }, function( status, response ) {

				if( status !== 200 || response === null )
					return refuse( Nino.adminUi.api.errorText( status, response, '/_admin/redirects/error/save' ) );

				const word = response.answer === 'route' ? [ 'ok', 'probe-route' ] : ( response.answer === 'rule' ? [ 'muted', 'probe-rule' ] : [ 'warn', 'probe-nothing' ] );
				const badge = el( 'span', 'nino-admin-badge', ( response.answer === 'nothing' ? '● ' : '✓ ' )+ t( '/_admin/redirects/probe/'+ response.answer ) );
				const text = response.answer === 'rule' ? Nino.adminUi.format( t('/_admin/redirects/msg/probe-rule'), response.from, response.to ) : t( '/_admin/redirects/msg/'+ word[1] );

				badge.dataset.state = word[0];
				result.appendChild( badge );
				result.appendChild( el( 'p', '', text ) );
			} );
		} );

		view.appendChild( form );
		mount.innerHTML = '';
		mount.appendChild( view );
	};

	// --- the screen ----------------------------------------------------------------------------------------------------------------

	const draw = function() {

		const list = dc.getElementById('redirects-list');

		if( list === null || dc.getElementById('redirects-probe') === null )
			return;

		const rules = rulesPane();
		const missing = missesPane();

		list.innerHTML = '';

		tabs = Nino.adminUi.tabs( [
			{ key : 'rules', label : t('/_admin/redirects/tab/rules'), panel : rules },
			{ key : 'missing', label : t('/_admin/redirects/tab/missing'), panel : missing },
		], {
			active	: S.tab,
			id			: 'redirects-tabs',
			label		: t('/_admin/redirects/label/title'),
			onSelect	: function( key ) { S.tab = key; Nino.admin.router.go( 'redirects', tabParts( key ) ) },
		} );

		// A count at a tab is a badge, not a part of the word
		if( S.misses.length > 0 )
			tabs.tabs.missing.appendChild( el( 'span', 'nino-admin-badge nino-admin-tab-badge', String( S.misses.length ) ) );

		list.appendChild( tabs.el );
		list.appendChild( rules );
		list.appendChild( missing );

		probeView();
	};

	// A move of the person inside the panel: the address is a new entry, and the screen follows it
	const go = function( where ) {
		Nino.admin.router.go( 'redirects', tabParts( where ) );
		Nino.admin.redirects.follow();
	};

	Nino.admin.redirects = Nino.adminUi.panel( {
		uri		: 'redirects',
		mount	: 'redirects-list',
		show	: 'always',
		render	: function( data ) {

			S.rules = data.rules || [];
			S.routes = data.routes || [];
			S.misses = data.misses || [];
			S.statuses = data.statuses || [];
			S.recording = data.recording !== false;
			S.limit = data.limit || 0;
			S.notes = data.notes || [];
			S.warned = [];

			draw();
			Nino.admin.redirects.follow();
		},
	} );

	Object.assign( Nino.admin.redirects, {

		/**
		 *	Show what the address names: the probe, or the rules or the addresses nothing answered in the tab strip
		 *
		 *	@return		void
		 */
		follow : function() {

			const at = Nino.admin.router.current();
			const parts = at.panel === 'redirects' ? at.parts : [];

			if( parts[0] === 'probe' ) {
				Nino.adminUi.showLevel( 'redirects-probe' );
				Nino.admin.router.set( 'redirects', PARTS.probe );
				return;
			}

			S.tab = parts[0] === 'missing' ? 'missing' : 'rules';

			if( tabs !== null )
				tabs.select( S.tab );

			Nino.adminUi.showLevel( 'redirects-list', { focus : false } );
			Nino.admin.router.set( 'redirects', tabParts( S.tab ) );
		},
	} );

} )( window, document );
