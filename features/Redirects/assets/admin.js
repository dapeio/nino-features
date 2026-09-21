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

			Nino.admin.redirects._render();
		},

		/**
		 *	Call a redirects/* action
		 *
		 *	@param		{string}		endpoint	Action name, eg. "list" -> "redirects/list"
		 *	@param		{Object}		payload		Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback	Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'redirects/'+ endpoint, data : JSON.stringify( payload ) } );
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
				is filled, and the strip goes into it rather than into the rules
				mount - it is the way back, and a way back drawn into a mount
				that is off the screen is no way back at all. The rules table
				and the probe used to stand over the addresses for the same
				reason: the rules mount was drawn and then never hidden	*/
			pane.appendChild( Nino.admin.redirects._tabs() );

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
		 *	The strip over both screens. A tablist, not a group: these really
		 *	are two panels, and the count beside the second is what makes
		 *	somebody look at it
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
				Nino.admin.redirects._screen = screen;
				Nino.admin.redirects._editing = null;
				Nino.admin.redirects._render();
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
				Nino.admin.redirects._editing = { from : '', to : '', status : 301, subtree : false, was : '' };
				Nino.admin.redirects._render();
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
				Nino.admin.redirects._editing = {
					from : rule.from, to : rule.to, status : rule.status, subtree : rule.subtree === true, was : rule.from,
				};
				Nino.admin.redirects._render();
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
				Nino.admin.redirects._editing = null;
				Nino.admin.redirects._render();
			} );

			box.appendChild( Nino.adminUi.contextBar( back, [] ) );

			box.appendChild( Nino.admin.redirects._field( '/_admin/redirects/label/from', edit.from, function( value ) { edit.from = value }, '/old/page' ) );
			box.appendChild( Nino.admin.redirects._field( '/_admin/redirects/label/to', edit.to, function( value ) { edit.to = value }, '/new/page' ) );

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
			save.addEventListener( 'click', function() {

				save.disabled = true;

				Nino.admin.redirects._apiCall( 'save', edit, function( status, response ) {

					save.disabled = false;

					if( status !== 200 || response === null )
						return Nino.admin.redirects._message( status, response );

					Nino.admin.redirects._rules		= response.rules || [];
					Nino.admin.redirects._notes		= response.notes || [];
					// The address is answered now, so it is not an address
					// nothing answers - the server drops it, and the screen has
					// to agree without asking again
					Nino.admin.redirects._misses	= Nino.admin.redirects._misses.filter( function( miss ) { return miss.path !== response.saved } );
					Nino.admin.redirects._editing	= null;
					Nino.admin.redirects._render();
					Nino.admin.redirects._message( 200, null, '/_admin/redirects/msg/saved' );
				} );
			} );

			const bar = dc.createElement('div');
			bar.appendChild( save );
			bar.appendChild( message );

			box.appendChild( Nino.adminUi.actionBar( bar ) );

			return box;
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
						out.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : '' );
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
				Nino.admin.redirects._editing = { from : miss.path, to : '', status : 301, subtree : false, was : '' };
				Nino.admin.redirects._screen	= 'rules';
				Nino.admin.redirects._render();
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
			line.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/redirects/error/load') );
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
			line.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/redirects/error/load') );

			wrap.appendChild( line );
		},
	};

} )( window, document );
