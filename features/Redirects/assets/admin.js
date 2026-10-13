/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Redirects				The feature's /_admin panel, "Redirects": the rules
 *													with the editor and a probe, and the addresses
 *													nothing answered, each with the one button that
 *													turns it into a rule (see Modules\Redirects\Admin
 *													beside this file). Ships with the feature and is
 *													loaded exactly while it is active.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	/*	The answer whose fields the script holds. Nino.adminUi.panel() draws the
		first answer again with every showing ('keep'), and the fields are newer
		than it: a save and a delete change the rules, a forgotten address leaves
		the list. render() takes an answer into the fields once, and every
		redraw after it - a showing, a save, a delete - draws from the fields,
		never from panel.data	*/
	let taken = null;

	Nino.admin.redirects = Nino.adminUi.panel( {
		uri		: 'redirects',
		mount	: 'redirects-rules',
		show	: 'keep',
		/*	The shell asks Save, Discard or Cancel before a log out or a language
			change would lose a rule nobody has saved, and a reload gets the
			browser's own question	*/
		dirty	: {
			isDirty : function() { return Nino.admin.redirects._isDirty() },
			save : function( done ) {

				const save = Nino.admin.redirects._saveButton;

				if( Nino.admin.redirects._editing === null || save === null )
					return done( false );

				Nino.admin.redirects._save( Nino.admin.redirects._editing, save, done );
			},
			// What the shell is about to leave takes the editor with it
			discard : function() { Nino.admin.redirects._opened = JSON.stringify( Nino.admin.redirects._editing ) },
		},
		render	: function( data ) {

			if( data !== taken ) {
				taken = data;
				Nino.admin.redirects._rules			= data.rules || [];
				Nino.admin.redirects._routes		= data.routes || [];
				Nino.admin.redirects._misses		= data.misses || [];
				Nino.admin.redirects._statuses	= data.statuses || [];
				Nino.admin.redirects._recording	= data.recording !== false;
				Nino.admin.redirects._limit			= data.limit || 0;
				Nino.admin.redirects._notes			= data.notes || [];
			}

			Nino.admin.redirects._render();
		},
	} );

	Object.assign( Nino.admin.redirects, {

		_rules			: [],
		// The pages of this site a target can be picked from: { path, label }.
		// Read with the list, because it is what the routes are right now
		_routes			: [],
		_misses			: [],
		_statuses		: [],
		// Whether this installation writes down what happened at all. The
		// second screen says so rather than looking empty for a reason nobody
		// can see from it
		_recording	: true,
		_limit			: 0,
		// What normalising the stored file had to drop. Shown rather than
		// swallowed: a rule that silently went away is a redirect somebody
		// believes is in place
		_notes			: [],

		// The rule being edited, as a working copy - leaving without saving
		// changes nothing. null while the list is on screen
		_editing		: null,
		// What it was when it was opened, as the json it is compared with: the
		// editor holds something nobody has saved when it is not that any more
		_opened			: '',
		// The Save of the editor on show, which the shell's own Save presses
		_saveButton	: null,
		// The status line of the screen on show (see _messageLine())
		_line				: null,
		// Which of the two screens is on
		_screen			: 'rules',
		_probe			: { path : '', answer : null },

		/**
		 *	Open the editor on a rule, as a working copy
		 *
		 *	@param		{Object}	rule			{ from, to, status, subtree, was }
		 *
		 *	@return		void
		 */
		_open : function( rule ) {

			Nino.admin.redirects._editing	= rule;
			Nino.admin.redirects._opened	= JSON.stringify( rule );
			Nino.admin.redirects._render();
		},

		/**
		 *	Whether the editor holds anything nobody has saved
		 *
		 *	@return		{boolean}
		 */
		_isDirty : function() {
			return Nino.admin.redirects._editing !== null && JSON.stringify( Nino.admin.redirects._editing ) !== Nino.admin.redirects._opened;
		},

		/**
		 *	Leave the editor - after asking, when it holds something nobody has
		 *	saved
		 *
		 *	@param		{Function}	proceed
		 *	@param		{Function}	[onCancel]		What puts the screen back as it was, when whoever asked says no
		 *
		 *	@return		void
		 */
		_leave : function( proceed, onCancel ) {
			Nino.admin.dirty.guard( [ 'redirects' ], proceed, onCancel );
		},

		/**
		 *	Both screens, and the strip that says which one is on
		 *
		 *	@return		void
		 */
		_render : function() {

			const rules 	= dc.getElementById('redirects-rules');
			const misses	= dc.getElementById('redirects-misses');

			if( rules === null || misses === null )
				return;

			const onRules = Nino.admin.redirects._screen !== 'missing';
			const pane		= onRules === true ? rules : misses;

			rules.innerHTML = '';
			misses.innerHTML = '';

			/*	Two mounts, one screen at a time: panes() hands the shell two
				divs in the same panel rather than two tabs of its own, so which
				of them is on is this script's to say. Only the one that is on
				is filled. The rules table and the probe used to stand over the
				addresses because the rules mount was drawn and then never
				hidden.

				The strip goes into the head the shell renders over the pane,
				beside the panel's name (Nino.adminUi.panelHead()), the row every
				panel with screens of its own puts its strip in. It is handed
				over on every draw, since the counts in it change with the
				lists, and tabs() takes the place of the one drawn before rather
				than standing beside it. Where there is no head - the script
				drawn somewhere other than its pane - the strip stands at the top
				of whichever mount is on rather than in the rules mount: it is the
				way back, and a way back drawn into a mount that is off the screen
				is no way back at all	*/
			const strip	= Nino.admin.redirects._tabs();
			const head	= Nino.adminUi.panelHead( rules );

			if( head === null )
				pane.appendChild( strip );
			else
				head.tabs( strip );

			if( onRules === false )
				pane.appendChild( Nino.admin.redirects._renderMisses() );
			else if( Nino.admin.redirects._editing !== null )
				pane.appendChild( Nino.admin.redirects._renderEditor() );
			else
				pane.appendChild( Nino.admin.redirects._renderRules() );

			Nino.adminUi.levels( 'redirects', [ 'rules', 'misses' ] ).show( onRules === true ? 'rules' : 'misses' );
		},

		/**
		 *	The strip that switches between the two screens, beside the
		 *	panel's name in the head (see _render()). A tablist, not a group:
		 *	these really are two panels, and the count beside the second is
		 *	what makes somebody look at it
		 *
		 *	@return		{Element}
		 */
		_tabs : function() {

			const bar = Nino.adminUi.el( 'div', 'nino-admin-tabs nino-admin-tabs--bar redirects-tabs' );
			bar.setAttribute( 'role', 'tablist' );

			const buttons = {};

			[ [ 'rules', '/_admin/redirects/label/title', Nino.admin.redirects._rules.length ],
				[ 'missing', '/_admin/redirects/label/tile', Nino.admin.redirects._misses.length ] ].forEach( function( tab ) {

				const button = Nino.adminUi.button( Nino.content.getText( tab[1] )+ ' ('+ tab[2]+ ')', undefined, { className : 'nino-admin-tab' } );
				button.setAttribute( 'role', 'tab' );
				button.dataset.screen = tab[0];
				buttons[tab[0]] = button;
				bar.appendChild( button );
			} );

			// buttonRow() paints the state and leaves the acting to the caller
			// - the whole panel is drawn again, which is also what moves the
			// editor out of the way when somebody leaves it by the tab
			Nino.adminUi.buttonRow( buttons, Nino.admin.redirects._screen, function( screen ) {
				// The strip painted the screen it was asked for before this ran:
				// drawn again, it is put back if the editor is kept
				Nino.admin.redirects._leave( function() {
					Nino.admin.redirects._screen = screen;
					Nino.admin.redirects._editing = null;
					Nino.admin.redirects._render();
				}, function() { Nino.admin.redirects._render() } );
			}, 'aria-selected' );

			return bar;
		},

		/**
		 *	The rules: what a redirect answers, where it sends, and how often
		 *	somebody has taken it
		 *
		 *	@return		{Element}
		 */
		_renderRules : function() {

			const box = Nino.adminUi.el('div');

			box.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/redirects/hint/rules') ) );
			Nino.admin.redirects._notes.forEach( function( note ) {
				box.appendChild( Nino.adminUi.el( 'p', 'nino-admin-hint nino-admin-error', note ) );
			} );

			const add = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/new'), function() {
				Nino.admin.redirects._open( { from : '', to : '', status : 301, subtree : false, was : '' } );
			}, { className : 'nino-admin-btn nino-admin-btn-primary' } );

			box.appendChild( Nino.adminUi.listActions( [ add ] ) );
			box.appendChild( Nino.admin.redirects._messageLine() );

			const mount = Nino.adminUi.el('div');
			box.appendChild( mount );

			if( Nino.admin.redirects._rules.length === 0 )
				mount.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/redirects/empty/rules') ) );
			else
				Nino.adminUi.table( {
					mount		: mount,
					rowKey	: 'from',
					rows		: Nino.admin.redirects._rules,
					labels	: {
						search	: Nino.content.getText('/_admin/redirects/label/search'),
						empty		: Nino.content.getText('/_admin/redirects/empty/rules'),
						noMatch	: Nino.content.getText('/_admin/redirects/empty/nomatch'),
					},
					columns	: [
						{ key : 'from', label : Nino.content.getText('/_admin/redirects/label/from'), type : 'string' },
						{ key : 'to', label : Nino.content.getText('/_admin/redirects/label/to'), type : 'string' },
						{ key : 'answer', label : Nino.content.getText('/_admin/redirects/label/answer'), type : 'string', render : function( value ) {
							return Nino.admin.redirects._flag( value );
						} },
						{ key : 'status', label : Nino.content.getText('/_admin/redirects/label/status'), type : 'integer',
							render : function( value ) { return Nino.content.getText('/_admin/redirects/status/'+ value ) } },
						{ key : 'subtree', label : Nino.content.getText('/_admin/redirects/label/subtree'), type : 'string',
							render : function( value ) { return Nino.content.getText( value === true ? '/_admin/redirects/label/on' : '/_admin/redirects/label/off' ) } },
						{ key : 'hits', label : Nino.content.getText('/_admin/redirects/label/hits'), type : 'integer' },
						{ key : 'last', label : Nino.content.getText('/_admin/redirects/label/last'), type : 'string' },
						{ key : 'from', label : '', type : 'string', render : function( value, row ) {
							return Nino.admin.redirects._rowActions( row );
						} },
					],
				} );

			box.appendChild( Nino.admin.redirects._renderProbe() );

			return box;
		},

		/**
		 *	What a rule's target leads to, said only where it leads somewhere
		 *	worth a second look: nothing, a loop, a chain that is too long, or
		 *	another rule. A target a
		 *	page, a file or another site answers is the ordinary case and says
		 *	nothing
		 *
		 *	@param		{string}	answer		Rules::answer()'s word for it
		 *
		 *	@return		{Element|string}
		 */
		_flag : function( answer ) {

			if( answer !== 'nothing' && answer !== 'loop' && answer !== 'chain' && answer !== 'rule' )
				return '';

			return Nino.adminUi.el( 'span', 'redirects-flag'+ ( answer === 'rule' ? '' : ' nino-admin-error' ), Nino.content.getText('/_admin/redirects/answer/'+ answer ) );
		},

		/**
		 *	Edit and delete, for one rule
		 *
		 *	@param		{Object}	rule
		 *
		 *	@return		{Element}
		 */
		_rowActions : function( rule ) {

			const wrap = Nino.adminUi.el( 'div', 'redirects-row-actions' );

			const edit = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/edit'), function() {
				Nino.admin.redirects._open( {
					from : rule.from, to : rule.to, status : rule.status, subtree : rule.subtree === true, was : rule.from,
				} );
			}, { className : 'nino-admin-btn nino-admin-btn--small' } );

			const remove = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/delete'), function() {

				if( wn.confirm( Nino.adminUi.format( Nino.content.getText('/_admin/redirects/confirm/delete'), rule.from ) ) === false )
					return;

				Nino.admin.redirects.call( 'delete', { from : rule.from }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._rules = response.rules || [];
					Nino.admin.redirects._render();
					Nino.admin.redirects._message( 200, null, '/_admin/redirects/msg/deleted' );
				} );
			}, { className : 'nino-admin-btn nino-admin-btn--small nino-admin-btn-danger' } );

			wrap.appendChild( edit );
			wrap.appendChild( remove );

			return wrap;
		},

		/**
		 *	One rule, as a form. Four fields, and the third is the one worth
		 *	getting right: 301 is what a search engine acts on and a browser
		 *	caches, 302 is what it forgets
		 *
		 *	@return		{Element}
		 */
		_renderEditor : function() {

			const edit = Nino.admin.redirects._editing;
			const box = Nino.adminUi.el('div');

			const back = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/back'), function() {
				Nino.admin.redirects._leave( function() {
					Nino.admin.redirects._editing = null;
					Nino.admin.redirects._render();
				} );
			}, { className : 'nino-admin-btn' } );

			box.appendChild( Nino.adminUi.contextBar( back, [] ) );

			box.appendChild( Nino.adminUi.textField( { label : Nino.content.getText('/_admin/redirects/label/from'), value : edit.from, placeholder : '/old/page', onInput : function( value ) { edit.from = value } } ) );
			const target = Nino.adminUi.textField( { label : Nino.content.getText('/_admin/redirects/label/to'), value : edit.to, placeholder : '/new/page', onInput : function( value ) { edit.to = value } } );
			box.appendChild( target );

			/*	The pages this site has, under the free text: a target is a path
				somebody would otherwise type from memory. Picking one writes its
				path into the field and into the working copy, and the select
				goes back to its first entry so the next pick is a change again.
				The free text stays what decides - an address on another site, a
				page that is not made yet	*/
			const pick = Nino.adminUi.selectField( {
				key				: 'pick',
				label			: Nino.content.getText('/_admin/redirects/label/pick'),
				options		: [ { value : '', label : '\u2014' } ].concat( Nino.admin.redirects._routes.map( function( route ) {
					return { value : route.path, label : route.label === route.path ? route.path : route.label+ ' ('+ route.path+ ')' };
				} ) ),
				value			: '',
				onChange	: function( value ) {

					if( value === '' )
						return;

					edit.to = value;

					const input = target.querySelector('input');
					if( input !== null )
						input.value = value;

					const select = pick.querySelector('select');
					if( select !== null )
						select.value = '';
				},
			} );
			box.appendChild( pick );

			box.appendChild( Nino.adminUi.selectField( {
				key				: 'status',
				label			: Nino.content.getText('/_admin/redirects/label/status'),
				options		: Nino.admin.redirects._statuses.map( function( status ) {
					return { value : String( status ), label : Nino.content.getText('/_admin/redirects/status/'+ status ) };
				} ),
				value			: String( edit.status ),
				onChange	: function( value ) { edit.status = parseInt( value, 10 ) },
			} ) );

			const wide = Nino.adminUi.switchField( {
				key			: 'subtree',
				checked	: edit.subtree === true,
				label		: Nino.content.getText('/_admin/redirects/label/subtree'),
				hint		: Nino.content.getText('/_admin/redirects/hint/subtree'),
				on			: Nino.content.getText('/_admin/redirects/label/on'),
				off			: Nino.content.getText('/_admin/redirects/label/off'),
			} );
			box.appendChild( wide );

			const subtree = wide.querySelector('input');
			if( subtree !== null )
				subtree.addEventListener( 'change', function() { edit.subtree = subtree.checked } );

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/save'), function() { Nino.admin.redirects._save( edit, save ) }, { className : 'nino-admin-btn nino-admin-btn-primary' } );
			Nino.admin.redirects._saveButton = save;

			const bar = Nino.adminUi.el('div');
			bar.appendChild( save );
			bar.appendChild( Nino.admin.redirects._messageLine() );

			box.appendChild( Nino.adminUi.actionBar( bar ) );

			return box;
		},

		/**
		 *	Save the rule being edited
		 *
		 *	@param		{Object}		edit
		 *	@param		{Element}		save
		 *	@param		{Function}	[done]		Told whether it was saved - the shell's Save asks
		 *
		 *	@return		void
		 */
		_save : function( edit, save, done ) {

			const finish = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			// A second click while one is on its way
			if( save.disabled === true )
				return finish( false );

			save.disabled = true;
			Nino.admin.redirects._line.saving();

			Nino.admin.redirects.call( 'save', edit, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null ) {
					Nino.admin.redirects._line.error( status, response, '/_admin/redirects/error/load' );
					return finish( false );
				}

				Nino.admin.redirects._rules		= response.rules || [];
				Nino.admin.redirects._notes		= response.notes || [];
				// The address is answered now, so it is not an address
				// nothing answers - the server drops it, and the screen has
				// to agree without asking again
				Nino.admin.redirects._misses	= Nino.admin.redirects._misses.filter( function( miss ) { return miss.path !== response.saved } );
				Nino.admin.redirects._editing	= null;
				Nino.admin.redirects._render();
				Nino.admin.redirects._line.saved();
				Nino.admin.redirects._warn( response.warnings || [] );

				finish( true );
			} );
		},

		/**
		 *	The probe. A redirect is invisible until somebody follows one, and a
		 *	rule that does not fire looks exactly like a rule that is not there
		 *
		 *	@return		{Element}
		 */
		_renderProbe : function() {

			const box = Nino.adminUi.el( 'fieldset', 'redirects-probe' );

			box.appendChild( Nino.adminUi.el( 'legend', '', Nino.content.getText('/_admin/redirects/label/probe') ) );

			const field = Nino.adminUi.textField( { label : Nino.content.getText('/_admin/redirects/label/path'), value : Nino.admin.redirects._probe.path, placeholder : '/old/page', onInput : function( value ) {
				Nino.admin.redirects._probe.path = value;
			} } );
			box.appendChild( field );

			// The answer of the probe, said aloud to a screen reader as it comes
			const out = Nino.adminUi.hint('');
			out.setAttribute( 'aria-live', 'polite' );

			const run = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/probe-run'), function() {

				Nino.admin.redirects.call( 'probe', { path : Nino.admin.redirects._probe.path }, function( status, response ) {

					if( status !== 200 || response === null ) {
						out.classList.add('nino-admin-error');
						out.textContent = Nino.adminUi.api.errorText( status, response, '' );
						return;
					}

					out.classList.remove('nino-admin-error');
					out.textContent = response.answer === 'route'
						? Nino.content.getText('/_admin/redirects/msg/probe-route')
						: ( response.answer === 'rule'
							? Nino.adminUi.format( Nino.content.getText('/_admin/redirects/msg/probe-rule'), response.from, response.to )
							: Nino.content.getText('/_admin/redirects/msg/probe-nothing') );
				} );
			}, { className : 'nino-admin-btn' } );

			const bar = Nino.adminUi.el('div');
			bar.appendChild( run );
			bar.appendChild( out );
			box.appendChild( Nino.adminUi.actionBar( bar ) );

			return box;
		},

		/**
		 *	The addresses nothing answered, and the one button each of them is
		 *	for
		 *
		 *	@return		{Element}
		 */
		_renderMisses : function() {

			const box = Nino.adminUi.el('div');

			box.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/redirects/hint/missing') ) );

			if( Nino.admin.redirects._recording === false )
				box.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/redirects/hint/off'), 'error' ) );
			else if( Nino.admin.redirects._limit > 0 )
				box.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/redirects/hint/limit'), Nino.admin.redirects._limit ) ) );

			// Forgetting can fail, and _message() says so on the line of the
			// screen that is on - which is this one's own. Only the screen that
			// is on is drawn at all, so there is exactly one of them
			box.appendChild( Nino.admin.redirects._messageLine() );

			if( Nino.admin.redirects._misses.length === 0 ) {
				box.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/redirects/empty/missing') ) );
				return box;
			}

			const forget = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/forget'), function() {

				if( wn.confirm( Nino.content.getText('/_admin/redirects/confirm/forget') ) === false )
					return;

				Nino.admin.redirects.call( 'forget', {}, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._misses = [];
					Nino.admin.redirects._render();
				} );
			}, { className : 'nino-admin-btn nino-admin-btn-danger' } );

			box.appendChild( Nino.adminUi.listActions( [ forget ] ) );

			const mount = Nino.adminUi.el('div');
			box.appendChild( mount );

			Nino.adminUi.table( {
				mount		: mount,
				rowKey	: 'path',
				rows		: Nino.admin.redirects._misses,
				labels	: {
					search	: Nino.content.getText('/_admin/redirects/label/search'),
					empty		: Nino.content.getText('/_admin/redirects/empty/missing'),
					noMatch	: Nino.content.getText('/_admin/redirects/empty/nomatch'),
				},
				columns	: [
					{ key : 'path', label : Nino.content.getText('/_admin/redirects/label/path'), type : 'string' },
					{ key : 'count', label : Nino.content.getText('/_admin/redirects/label/count'), type : 'integer' },
					{ key : 'last', label : Nino.content.getText('/_admin/redirects/label/last'), type : 'string' },
					{ key : 'path', label : '', type : 'string', render : function( value, row ) {
						return Nino.admin.redirects._missActions( row );
					} },
				],
			} );

			return box;
		},

		/**
		 *	Make a rule out of one, or forget it
		 *
		 *	@param		{Object}	miss
		 *
		 *	@return		{Element}
		 */
		_missActions : function( miss ) {

			const wrap = Nino.adminUi.el( 'div', 'redirects-row-actions' );

			const make = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/make'), function() {
				// Opened on the rules screen with the address already in it:
				// what is missing is the target, and that is the only thing
				// somebody actually has to decide here
				Nino.admin.redirects._screen	= 'rules';
				Nino.admin.redirects._open( { from : miss.path, to : '', status : 301, subtree : false, was : '' } );
			}, { className : 'nino-admin-btn nino-admin-btn--small nino-admin-btn-primary' } );

			const drop = Nino.adminUi.button( Nino.content.getText('/_admin/redirects/label/forget-one'), function() {

				Nino.admin.redirects.call( 'forget', { path : miss.path }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._misses = Nino.admin.redirects._misses.filter( function( row ) { return row.path !== miss.path } );
					Nino.admin.redirects._render();
				} );
			}, { className : 'nino-admin-btn nino-admin-btn--small' } );

			wrap.appendChild( make );
			wrap.appendChild( drop );

			return wrap;
		},

		/**
		 *	The line the messages of a screen stand in, drawn into each of the
		 *	three places it is wanted - under the rules' own button, in the bar of
		 *	the editor, over the addresses - and the status line the workbench
		 *	makes of it. The one drawn last is the one on screen: a screen is
		 *	drawn whole, and only one at a time
		 *
		 *	@return		{Element}
		 */
		_messageLine : function() {

			const line = Nino.adminUi.status( Nino.adminUi.el('p') );

			line.element.id = 'redirects-msg';
			Nino.admin.redirects._line = line;

			return line.element;
		},

		/**
		 *	What a call answered, on the message line that is on screen
		 *
		 *	@param		{number}	status
		 *	@param		{?Object}	response
		 *	@param		{string}	[okKey]
		 *
		 *	@return		void
		 */
		_message : function( status, response, okKey ) {

			const line = Nino.admin.redirects._line;

			if( line === null )
				return;

			if( status === 200 )
				line.idle( okKey ? Nino.content.getText( okKey ) : '' );
			else
				line.error( status, response, '/_admin/redirects/error/load' );
		},

		/**
		 *	What a save said about the rule it saved, below the message line the
		 *	editor left behind. The rule is written either way - this is a
		 *	warning, not a state of the save and not a refusal - so it stands
		 *	beside the line that says it was saved
		 *
		 *	@param		{Array}		warnings		Sentences, already in the workbench's language
		 *
		 *	@return		void
		 */
		_warn : function( warnings ) {

			const line = Nino.admin.redirects._line;

			if( line === null || warnings.length === 0 )
				return;

			const warning = Nino.adminUi.hint( warnings.join(' '), 'error' );

			// An element put into the page is read out only when it is an alert
			warning.setAttribute( 'role', 'alert' );
			line.element.insertAdjacentElement( 'afterend', warning );
		},
	} );

} )( window, document );
