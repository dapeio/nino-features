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

	Nino.admin.redirects = {

		_ready			: false,
		_rules			: [],
		// The pages of this site a target can be picked from: { path, label }.
		// Read with the list, because it is what the routes are right now
		_routes			: [],
		_misses			: [],
		_statuses		: [ 301, 302 ],
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
		// Which of the two screens is on
		_screen			: 'rules',
		_probe			: { path : '', answer : null },

		/**
		 *	Read everything and draw both screens
		 *
		 *	@param		{Function}	[then]		Run once the answer is in
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('redirects-rules');
			if( wrap === null )
				return;

			Nino.admin.redirects._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.redirects._showError( wrap, status, response );

				Nino.admin.redirects._rules			= response.rules || [];
				Nino.admin.redirects._routes		= response.routes || [];
				Nino.admin.redirects._misses		= response.misses || [];
				Nino.admin.redirects._statuses	= response.statuses || [ 301, 302 ];
				Nino.admin.redirects._recording	= response.recording !== false;
				Nino.admin.redirects._limit			= response.limit || 0;
				Nino.admin.redirects._notes			= response.notes || [];
				Nino.admin.redirects._ready			= true;

				Nino.admin.redirects._render();

				if( typeof then === 'function' )
					then();
			} );
		},

		/**
		 *	Re-show whichever screen is on - the shell calls this when the panel
		 *	is opened again
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.redirects._ready === false )
				return Nino.admin.redirects.init();

			// The shell brings the panel on screen again after a Save it asked
			// for failed: drawn anew, the reason the save gave would be gone,
			// and so would the rule nobody has saved
			if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty.isDirty( [ 'redirects' ] ) === true )
				return;

			Nino.admin.redirects._render();
		},

		/**
		 *	Call a redirects/* action. The workbench's own request helper posts
		 *	where this Nino has one - it knows the project's directory and what
		 *	to do when the page has outlived its session; the post below is
		 *	what every panel did before it, with the base the asset bundle
		 *	fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint	Action name, eg. "list" -> "redirects/list"
		 *	@param		{Object}		payload		Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback	Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'redirects/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'redirects/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the server's code in the workbench's
		 *	language, then its own message, then this panel's sentence - where
		 *	this Nino has errorText(). Before it, "(status) message"
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key				Fill key of the panel's own sentence, '' for none
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : ( key === '' ? '' : Nino.content.getText( key ) ) );
		},

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
		 *	Leave the editor - after asking, where this Nino can, when it holds
		 *	something nobody has saved
		 *
		 *	@param		{Function}	proceed
		 *	@param		{Function}	[onCancel]		What puts the screen back as it was, when whoever asked says no
		 *
		 *	@return		void
		 */
		_leave : function( proceed, onCancel ) {

			if( typeof Nino.admin.dirty === 'object' )
				return Nino.admin.dirty.guard( [ 'redirects' ], proceed, onCancel );

			proceed();
		},

		/**
		 *	A text fill, with %s filled in
		 *
		 *	@param		{string}	key
		 *	@param		{...*}		values
		 *
		 *	@return		{string}
		 */
		_say : function( key ) {
			const values = Array.prototype.slice.call( arguments, 1 );
			let text = Nino.content.getText( key );
			values.forEach( function( value ) { text = text.replace( '%s', String( value ) ) } );
			return text;
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
				than standing beside it. Where there is no head - a kernel from
				before it, or the script drawn somewhere other than its pane -
				the strip stands at the top of whichever mount is on rather than
				in the rules mount: it is the way back, and a way back drawn into
				a mount that is off the screen is no way back at all	*/
			const strip	= Nino.admin.redirects._tabs();
			const head	= typeof Nino.adminUi.panelHead === 'function' ? Nino.adminUi.panelHead( rules ) : null;

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

			rules.classList.toggle( 'admin-hidden', onRules === false );
			misses.classList.toggle( 'admin-hidden', onRules );
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

			const bar = dc.createElement('div');
			bar.className = 'nino-admin-tabs nino-admin-tabs--bar redirects-tabs';
			bar.setAttribute( 'role', 'tablist' );

			const buttons = {};

			[ [ 'rules', '/_admin/redirects/label/title', Nino.admin.redirects._rules.length ],
				[ 'missing', '/_admin/redirects/label/tile', Nino.admin.redirects._misses.length ] ].forEach( function( tab ) {

				const button = dc.createElement('button');
				button.type = 'button';
				button.className = 'nino-admin-tab';
				button.setAttribute( 'role', 'tab' );
				button.dataset.screen = tab[0];
				button.textContent = Nino.content.getText( tab[1] )+ ' ('+ tab[2]+ ')';
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

			const box = dc.createElement('div');

			box.appendChild( Nino.admin.redirects._hint('/_admin/redirects/hint/rules') );
			Nino.admin.redirects._notes.forEach( function( note ) {
				const line = dc.createElement('p');
				line.className = 'nino-admin-hint nino-admin-error';
				line.textContent = note;
				box.appendChild( line );
			} );

			const add = dc.createElement('button');
			add.type = 'button';
			add.className = 'nino-admin-btn nino-admin-btn-primary';
			add.textContent = Nino.content.getText('/_admin/redirects/label/new');
			add.addEventListener( 'click', function() {
				Nino.admin.redirects._open( { from : '', to : '', status : 301, subtree : false, was : '' } );
			} );

			box.appendChild( Nino.adminUi.listActions( [ add ] ) );

			const message = dc.createElement('p');
			message.id = 'redirects-msg';
			message.className = 'nino-admin-hint';
			message.setAttribute( 'aria-live', 'polite' );
			box.appendChild( message );

			const mount = dc.createElement('div');
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
		 *	worth a second look: nothing, a loop, or another rule. A target a
		 *	page, a file or another site answers is the ordinary case and says
		 *	nothing
		 *
		 *	@param		{string}	answer		Rules::answer()'s word for it
		 *
		 *	@return		{Element|string}
		 */
		_flag : function( answer ) {

			if( answer !== 'nothing' && answer !== 'loop' && answer !== 'rule' )
				return '';

			const flag = dc.createElement('span');
			flag.className = 'redirects-flag'+ ( answer === 'rule' ? '' : ' nino-admin-error' );
			flag.textContent = Nino.content.getText('/_admin/redirects/answer/'+ answer );

			return flag;
		},

		/**
		 *	Edit and delete, for one rule
		 *
		 *	@param		{Object}	rule
		 *
		 *	@return		{Element}
		 */
		_rowActions : function( rule ) {

			const wrap = dc.createElement('div');
			wrap.className = 'redirects-row-actions';

			const edit = dc.createElement('button');
			edit.type = 'button';
			edit.className = 'nino-admin-btn';
			edit.textContent = Nino.content.getText('/_admin/redirects/label/edit');
			edit.addEventListener( 'click', function() {
				Nino.admin.redirects._open( {
					from : rule.from, to : rule.to, status : rule.status, subtree : rule.subtree === true, was : rule.from,
				} );
			} );

			const remove = dc.createElement('button');
			remove.type = 'button';
			remove.className = 'nino-admin-btn nino-admin-btn-danger';
			remove.textContent = Nino.content.getText('/_admin/redirects/label/delete');
			remove.addEventListener( 'click', function() {

				if( wn.confirm( Nino.admin.redirects._say('/_admin/redirects/confirm/delete', rule.from ) ) === false )
					return;

				Nino.admin.redirects._apiCall( 'delete', { from : rule.from }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._rules = response.rules || [];
					Nino.admin.redirects._render();
					Nino.admin.redirects._message( 200, null, '/_admin/redirects/msg/deleted' );
				} );
			} );

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
			const box = dc.createElement('div');

			const back = dc.createElement('button');
			back.type = 'button';
			back.className = 'nino-admin-btn';
			back.textContent = Nino.content.getText('/_admin/redirects/label/back');
			back.addEventListener( 'click', function() {
				Nino.admin.redirects._leave( function() {
					Nino.admin.redirects._editing = null;
					Nino.admin.redirects._render();
				} );
			} );

			box.appendChild( Nino.adminUi.contextBar( back, [] ) );

			box.appendChild( Nino.admin.redirects._field( '/_admin/redirects/label/from', edit.from, function( value ) { edit.from = value }, '/old/page' ) );
			const target = Nino.admin.redirects._field( '/_admin/redirects/label/to', edit.to, function( value ) { edit.to = value }, '/new/page' );
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

			box.appendChild( Nino.adminUi.switchField( {
				key			: 'subtree',
				checked	: edit.subtree === true,
				label		: Nino.content.getText('/_admin/redirects/label/subtree'),
				hint		: Nino.content.getText('/_admin/redirects/hint/subtree'),
				on			: Nino.content.getText('/_admin/redirects/label/on'),
				off			: Nino.content.getText('/_admin/redirects/label/off'),
			} ) );

			const subtree = box.lastChild.querySelector('input');
			if( subtree !== null )
				subtree.addEventListener( 'change', function() { edit.subtree = subtree.checked } );

			const message = dc.createElement('p');
			message.id = 'redirects-msg';
			message.className = 'nino-admin-hint';
			message.setAttribute( 'aria-live', 'polite' );

			const save = dc.createElement('button');
			save.type = 'button';
			save.className = 'nino-admin-btn nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/redirects/label/save');
			Nino.admin.redirects._saveButton = save;
			save.addEventListener( 'click', function() { Nino.admin.redirects._save( edit, save ) } );

			const bar = dc.createElement('div');
			bar.appendChild( save );
			bar.appendChild( message );

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

			Nino.admin.redirects._apiCall( 'save', edit, function( status, response ) {

				save.disabled = false;

				if( status !== 200 || response === null ) {
					Nino.admin.redirects._message( status, response );
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
				Nino.admin.redirects._message( 200, null, '/_admin/redirects/msg/saved' );
				Nino.admin.redirects._warn( response.warnings || [] );

				finish( true );
			} );
		},

		/**
		 *	One text field, since a path is neither a select nor a switch
		 *
		 *	@param		{string}		labelKey
		 *	@param		{string}		value
		 *	@param		{Function}	onInput
		 *	@param		{string}		placeholder
		 *
		 *	@return		{Element}
		 */
		_field : function( labelKey, value, onInput, placeholder ) {

			const field = dc.createElement('label');
			field.className = 'nino-admin-field';

			const name = dc.createElement('span');
			name.textContent = Nino.content.getText( labelKey );
			field.appendChild( name );

			const input = dc.createElement('input');
			input.type = 'text';
			input.className = 'nino-admin-input';
			input.value = value || '';
			input.placeholder = placeholder;
			input.addEventListener( 'input', function() { onInput( input.value ) } );
			field.appendChild( input );

			return field;
		},

		/**
		 *	The probe. A redirect is invisible until somebody follows one, and a
		 *	rule that does not fire looks exactly like a rule that is not there
		 *
		 *	@return		{Element}
		 */
		_renderProbe : function() {

			const box = dc.createElement('fieldset');
			box.className = 'redirects-probe';

			const legend = dc.createElement('legend');
			legend.textContent = Nino.content.getText('/_admin/redirects/label/probe');
			box.appendChild( legend );

			const field = Nino.admin.redirects._field( '/_admin/redirects/label/path', Nino.admin.redirects._probe.path, function( value ) {
				Nino.admin.redirects._probe.path = value;
			}, '/old/page' );
			box.appendChild( field );

			const out = dc.createElement('p');
			out.className = 'nino-admin-hint';
			out.setAttribute( 'aria-live', 'polite' );

			const run = dc.createElement('button');
			run.type = 'button';
			run.className = 'nino-admin-btn';
			run.textContent = Nino.content.getText('/_admin/redirects/label/probe-run');
			run.addEventListener( 'click', function() {

				Nino.admin.redirects._apiCall( 'probe', { path : Nino.admin.redirects._probe.path }, function( status, response ) {

					if( status !== 200 || response === null ) {
						out.classList.add('nino-admin-error');
						out.textContent = Nino.admin.redirects._errorText( status, response, '' );
						return;
					}

					out.classList.remove('nino-admin-error');
					out.textContent = response.answer === 'route'
						? Nino.content.getText('/_admin/redirects/msg/probe-route')
						: ( response.answer === 'rule'
							? Nino.admin.redirects._say('/_admin/redirects/msg/probe-rule', response.from, response.to )
							: Nino.content.getText('/_admin/redirects/msg/probe-nothing') );
				} );
			} );

			const bar = dc.createElement('div');
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

			const box = dc.createElement('div');

			box.appendChild( Nino.admin.redirects._hint('/_admin/redirects/hint/missing') );

			if( Nino.admin.redirects._recording === false )
				box.appendChild( Nino.admin.redirects._hint('/_admin/redirects/hint/off', true ) );
			else if( Nino.admin.redirects._limit > 0 )
				box.appendChild( Nino.admin.redirects._hint( Nino.admin.redirects._say('/_admin/redirects/hint/limit', Nino.admin.redirects._limit ) ) );

			// Forgetting can fail, and _message() says so on the line with this
			// id - which is on the other screen while this one is on, so this
			// screen carries one of its own. Only the screen that is on is
			// drawn at all, so there is still exactly one of them
			const message = dc.createElement('p');
			message.id = 'redirects-msg';
			message.className = 'nino-admin-hint';
			message.setAttribute( 'aria-live', 'polite' );
			box.appendChild( message );

			if( Nino.admin.redirects._misses.length === 0 ) {
				box.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/redirects/empty/missing') ) );
				return box;
			}

			const forget = dc.createElement('button');
			forget.type = 'button';
			forget.className = 'nino-admin-btn nino-admin-btn-danger';
			forget.textContent = Nino.content.getText('/_admin/redirects/label/forget');
			forget.addEventListener( 'click', function() {

				if( wn.confirm( Nino.content.getText('/_admin/redirects/confirm/forget') ) === false )
					return;

				Nino.admin.redirects._apiCall( 'forget', {}, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._misses = [];
					Nino.admin.redirects._render();
				} );
			} );

			box.appendChild( Nino.adminUi.listActions( [ forget ] ) );

			const mount = dc.createElement('div');
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

			const wrap = dc.createElement('div');
			wrap.className = 'redirects-row-actions';

			const make = dc.createElement('button');
			make.type = 'button';
			make.className = 'nino-admin-btn nino-admin-btn-primary';
			make.textContent = Nino.content.getText('/_admin/redirects/label/make');
			make.addEventListener( 'click', function() {
				// Opened on the rules screen with the address already in it:
				// what is missing is the target, and that is the only thing
				// somebody actually has to decide here
				Nino.admin.redirects._screen	= 'rules';
				Nino.admin.redirects._open( { from : miss.path, to : '', status : 301, subtree : false, was : '' } );
			} );

			const drop = dc.createElement('button');
			drop.type = 'button';
			drop.className = 'nino-admin-btn';
			drop.textContent = Nino.content.getText('/_admin/redirects/label/forget-one');
			drop.addEventListener( 'click', function() {

				Nino.admin.redirects._apiCall( 'forget', { path : miss.path }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._misses = Nino.admin.redirects._misses.filter( function( row ) { return row.path !== miss.path } );
					Nino.admin.redirects._render();
				} );
			} );

			wrap.appendChild( make );
			wrap.appendChild( drop );

			return wrap;
		},

		/**
		 *	One line of hint
		 *
		 *	@param		{string}	keyOrText
		 *	@param		{boolean}	[warn]
		 *
		 *	@return		{Element}
		 */
		_hint : function( keyOrText, warn ) {

			const line = dc.createElement('p');
			line.className = 'nino-admin-hint'+ ( warn === true ? ' nino-admin-error' : '' );
			line.textContent = Nino.adminUi.text( keyOrText );

			return line;
		},

		/**
		 *	What a call answered, on whichever message line is on screen
		 *
		 *	@param		{number}	status
		 *	@param		{?Object}	response
		 *	@param		{string}	[okKey]
		 *
		 *	@return		void
		 */
		_message : function( status, response, okKey ) {

			const line = dc.getElementById('redirects-msg');
			if( line === null )
				return;

			if( status === 200 ) {
				line.classList.remove('nino-admin-error');
				line.textContent = okKey ? Nino.content.getText( okKey ) : '';
				return;
			}

			line.classList.add('nino-admin-error');
			line.textContent = Nino.admin.redirects._errorText( status, response, '/_admin/redirects/error/load' );
		},

		/**
		 *	What a save said about the rule it saved, on the message line the
		 *	editor left behind. The rule is written either way - this is a
		 *	sentence, not a refusal - so it replaces the plain "Saved." rather
		 *	than standing beside an error
		 *
		 *	@param		{Array}		warnings		Sentences, already in the workbench's language
		 *
		 *	@return		void
		 */
		_warn : function( warnings ) {

			const line = dc.getElementById('redirects-msg');
			if( line === null || warnings.length === 0 )
				return;

			line.classList.add('nino-admin-error');
			line.textContent = warnings.join(' ');
		},

		/**
		 *	The panel could not be read at all
		 *
		 *	@param		{Element}	wrap
		 *	@param		{number}	status
		 *	@param		{?Object}	response
		 *
		 *	@return		void
		 */
		_showError : function( wrap, status, response ) {

			wrap.innerHTML = '';

			const line = dc.createElement('p');
			line.className = 'nino-admin-hint nino-admin-error';
			line.textContent = Nino.admin.redirects._errorText( status, response, '/_admin/redirects/error/load' );

			wrap.appendChild( line );
		},
	};

	/*	The shell asks Save, Discard or Cancel before a log out or a language
		change would lose a rule nobody has saved, and a reload gets the
		browser's own question. A panel registers where this Nino has the
		registry and does without where it has not - panel scripts run on
		older workbenches too	*/
	if( typeof Nino.admin.dirty === 'object' )
		Nino.admin.dirty.register( 'redirects', {
			isDirty : function() { return Nino.admin.redirects._isDirty() },
			save : function( done ) {

				const save = Nino.admin.redirects._saveButton;

				if( Nino.admin.redirects._editing === null || save === null )
					return done( false );

				Nino.admin.redirects._save( Nino.admin.redirects._editing, save, done );
			},
			// What the shell is about to leave takes the editor with it
			discard : function() { Nino.admin.redirects._opened = JSON.stringify( Nino.admin.redirects._editing ) },
		} );

} )( window, document );
