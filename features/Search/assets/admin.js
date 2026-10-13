/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Search					The feature's /_admin panel, "Search": the index as a
 *													list, one type's four priority slots as an editor, and
 *													a probe that runs a real search and shows the scores
 *													(see Modules\Search\Admin beside this file). Ships with
 *													the feature and is loaded exactly while it is active.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The three screens are three elements of the pane, one on show at a time
	const levels = Nino.adminUi.levels( 'search', [ 'list', 'type', 'probe' ] );

	/*	The list is the screen the panel opens on, and the first answer is all the
		panel asks for: what a save or a rebuild changes is read again with
		reload(). 'keep' draws it again with every showing - the editor, which
		holds a working copy of its own, is the one screen that is not drawn from
		the answer (see showCurrent() below)	*/
	Nino.admin.search = Nino.adminUi.panel( {
		uri		: 'search',
		mount	: 'search-list',
		show	: 'keep',
		error	: '/_admin/common/error/load',
		/*	The shell asks Save, Discard or Cancel before the editor's back link,
			a log out or a language change would lose slots nobody has saved	*/
		dirty	: {
			isDirty : function() { return Nino.admin.search._isDirty() },
			save : function( done ) {

				if( Nino.admin.search._editing === null || Nino.admin.search._saveButton === null )
					return done( false );

				Nino.admin.search._save( Nino.admin.search._saveButton, Nino.admin.search._line, done );
			},
			// What the shell is about to leave takes the editor with it
			discard : function() { Nino.admin.search._opened = JSON.stringify( Nino.admin.search._editing.fields ) },
		},
		render	: function( data ) {

			Nino.admin.search._types		= data.types || [];
			// What a match in each priority is worth, straight from Search::WEIGHTS
			Nino.admin.search._weights	= data.weights || {};
			Nino.admin.search._locales	= data.locales || [];
			Nino.admin.search._renderList();
		},
	} );

	const showList = Nino.admin.search.showCurrent;

	Object.assign( Nino.admin.search, {

		// One row per element type the project has - what indexState() answered
		_types		: [],
		_weights	: {},
		_locales	: [],
		// The type being edited, as a working copy: leaving without saving
		// changes nothing. null while the list is on screen
		_editing	: null,
		// Its slots as they were when the editor opened, as the json they are
		// compared with: the editor holds something nobody has saved when its
		// slots are not that any more
		_opened		: '',
		// The status line and the Save of the editor on show, which the
		// shell's own Save presses
		_line			: null,
		_saveButton	: null,
		// The line the list says what a build or a save did on, and the lines
		// under it for what could not be built. null while the list is not drawn
		_listLine	: null,
		_listNotes	: null,
		// What the probe last ran, so switching panels and back keeps it
		_probe		: { query : '', types : [], locale : '', hits : null, limit : 0 },

		/**
		 *	Re-show whichever screen is on - the shell calls this when the panel
		 *	is opened again. The list is drawn from the answer; the editor from
		 *	its working copy, and not at all while it holds slots nobody has
		 *	saved
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			if( Nino.admin.search.ready === false || Nino.admin.search._editing === null )
				return showList();

			// The shell brings the panel on screen again after a Save it
			// asked for failed: drawn anew, the reason the save gave would
			// be gone, and so would the slots nobody has saved
			if( Nino.admin.dirty.isDirty( [ 'search' ] ) === false )
				Nino.admin.search._renderType();
		},

		/**
		 *	Whether the editor holds slots nobody has saved
		 *
		 *	@return		{boolean}
		 */
		_isDirty : function() {
			return Nino.admin.search._editing !== null && JSON.stringify( Nino.admin.search._editing.fields ) !== Nino.admin.search._opened;
		},

		/**
		 *	Leave the type's editor - after asking, when it holds slots nobody
		 *	has saved
		 *
		 *	@param		{Function}	proceed
		 *
		 *	@return		void
		 */
		_leave : function( proceed ) {
			Nino.admin.dirty.guard( [ 'search' ], proceed );
		},

		/**
		 *	The state of one row, as a word and a level - the list's whole point
		 *	is that this is readable without opening anything
		 *
		 *	@param		{Object}	row				One indexState() row
		 *
		 *	@return		{Object}						{ key, label, level }, the level being the badge's state
		 */
		_state : function( row ) {

			if( ( row.issues || [] ).length > 0 && ( row.fields === undefined || Object.keys( row.fields ).length === 0 ) )
				return { key : 'broken', label : Nino.content.getText('/_admin/search/state/broken'), level : 'error' };

			if( row.configured !== true || Object.keys( row.fields || {} ).length === 0 )
				return { key : 'off', label : Nino.content.getText('/_admin/search/state/off'), level : 'muted' };

			if( row.indexed !== true )
				return { key : 'missing', label : Nino.content.getText('/_admin/search/state/missing'), level : 'warn' };

			if( row.stale === true )
				return { key : 'stale', label : Nino.content.getText('/_admin/search/state/stale'), level : 'warn' };

			return { key : 'current', label : Nino.content.getText('/_admin/search/state/current'), level : 'ok' };
		},

		// ---- the list --------------------------------------------------

		/**
		 *	One row per element type, the two rebuild actions and the way into
		 *	the probe
		 *
		 *	@return		void
		 */
		_renderList : function() {

			const wrap = Nino.admin.search.mount();
			if( wrap === null )
				return;

			Nino.admin.search._editing = null;
			Nino.admin.search._listLine = null;
			Nino.admin.search._listNotes = null;
			levels.show('list');
			wrap.innerHTML = '';

			/*	No heading of its own: the head the shell renders over the pane
				names the panel (Nino.adminUi.panelHead()), and a second name a
				line under it would only say the same thing again. The editor
				and the probe keep theirs - they are a level below this list,
				like every form in the workbench, and their context bar is the
				way back	*/
			wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/search/hint/intro') ) );

			if( Nino.admin.search._types.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/search/hint/empty') ) );
				return;
			}

			wrap.appendChild( Nino.admin.search._renderTable() );

			const rebuild = Nino.adminUi.button( Nino.content.getText('/_admin/search/label/rebuild-all'), function() {
				Nino.admin.search._rebuild( '', rebuild );
			}, { id : 'search-createindex', className : 'nino-admin-btn-primary' } );

			const probe = Nino.adminUi.button( Nino.content.getText('/_admin/search/label/probe'), function() {
				Nino.admin.search._renderProbe();
			}, { id : 'search-openprobe' } );

			const actions = Nino.adminUi.el('div');
			actions.appendChild( rebuild );
			actions.appendChild( probe );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );

			const msg = Nino.adminUi.el('p');
			msg.id = 'search-list-msg';
			Nino.admin.search._listLine = Nino.adminUi.status( msg );
			wrap.appendChild( msg );

			Nino.admin.search._listNotes = Nino.adminUi.el('div');
			Nino.admin.search._listNotes.id = 'search-list-notes';
			wrap.appendChild( Nino.admin.search._listNotes );
		},

		/**
		 *	The list itself
		 *
		 *	@return		{Element}
		 */
		_renderTable : function() {

			const table = Nino.adminUi.el( 'table', 'nino-admin-table' );
			table.id = 'search-types';

			const head = Nino.adminUi.el('tr');
			[ 'type', 'fields', 'elements', 'state' ].forEach( function( name ) {
				head.appendChild( Nino.adminUi.el( 'th', '', Nino.content.getText('/_admin/search/label/'+ name ) ) );
			} );
			head.appendChild( Nino.adminUi.el('th') );
			table.appendChild( head );

			Nino.admin.search._types.forEach( function( row ) {
				table.appendChild( Nino.admin.search._renderRow( row ) );
			} );

			return table;
		},

		/**
		 *	One type
		 *
		 *	@param		{Object}	row				One indexState() row
		 *
		 *	@return		{Element}
		 */
		_renderRow : function( row ) {

			const tr = Nino.adminUi.el('tr');
			tr.setAttribute( 'data-type', row.type );

			const name = Nino.adminUi.el('td');
			name.appendChild( Nino.adminUi.el( 'strong', '', row.title || row.type ) );
			name.appendChild( Nino.adminUi.el( 'small', '', ' '+ row.type ) );
			tr.appendChild( name );

			// The fields as chips in priority order, so the weighting is
			// readable without opening the editor
			const fields = Nino.adminUi.el('td');
			const keys = Object.keys( row.fields || {} ).sort();

			if( keys.length === 0 )
				fields.textContent = '—';
			else
				keys.forEach( function( priority ) {
					const chip = Nino.adminUi.el( 'code', 'search-chip', row.fields[priority] );
					chip.title = Nino.adminUi.format( Nino.content.getText('/_admin/search/label/slot'), priority );
					fields.appendChild( chip );
					fields.appendChild( dc.createTextNode(' ') );
				} );

			( row.issues || [] ).forEach( function( issue ) {
				fields.appendChild( Nino.adminUi.hint( issue, 'error' ) );
			} );
			tr.appendChild( fields );

			tr.appendChild( Nino.adminUi.el( 'td', '', row.indexed === true && row.indexedElements !== row.elements
				? row.indexedElements+ ' / '+ row.elements
				: String( row.elements ) ) );

			const state = Nino.admin.search._state( row );
			const cell = Nino.adminUi.el('td');
			const badge = Nino.adminUi.el( 'span', 'nino-admin-badge', state.label );
			badge.dataset.state = state.level;
			cell.appendChild( badge );

			if( row.built )
				cell.appendChild( dc.createTextNode( ' '+ Nino.adminUi.format( Nino.content.getText('/_admin/search/state/built'), row.built.substring( 0, 10 ) ) ) );

			tr.appendChild( cell );

			const actions = Nino.adminUi.el( 'div', 'nino-admin-actions' );
			actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/search/label/edit'), function() {
				Nino.admin.search._editType( row );
			}, { className : 'nino-admin-btn--small' } ) );

			if( state.key === 'missing' || state.key === 'stale' ) {
				const rebuild = Nino.adminUi.button( Nino.content.getText('/_admin/search/label/rebuild'), function() {
					Nino.admin.search._rebuild( row.type, rebuild );
				}, { className : 'nino-admin-btn--small' } );
				actions.appendChild( rebuild );
			}

			const last = Nino.adminUi.el('td');
			last.appendChild( actions );
			tr.appendChild( last );

			return tr;
		},

		/**
		 *	What a build or a save did, on the list's own line - drawn anew
		 *	with the list, so this is said once the list is back
		 *
		 *	@param		{string}		text
		 *	@param		{Array}			[notes]				Lines for what could not be built, under the first
		 *
		 *	@return		void
		 */
		_said : function( text, notes ) {

			if( Nino.admin.search._listLine === null )
				return;

			Nino.admin.search._listLine.idle( text );

			( notes || [] ).forEach( function( note ) {
				Nino.admin.search._listNotes.appendChild( Nino.adminUi.hint( note, 'error' ) );
			} );
		},

		/**
		 *	Rebuild every configured index, or one named type
		 *
		 *	@param		{string}		type			'' for all of them
		 *	@param		{Element}		btn
		 *
		 *	@return		void
		 */
		_rebuild : function( type, btn ) {

			const line = Nino.admin.search._listLine;

			btn.disabled = true;
			line.idle( Nino.content.getText('/_admin/search/msg/creating') );

			Nino.admin.search.call( 'createindex', type === '' ? {} : { type : type }, function( status, response ) {

				btn.disabled = false;

				if( status !== 200 || response === null || typeof response !== 'object' )
					return line.error( status, response, '/_admin/search/error/create' );

				const said = response.created === 0
					? Nino.content.getText('/_admin/search/msg/none')
					: Nino.adminUi.format( Nino.content.getText( response.created === 1 ? '/_admin/search/msg/created' : '/_admin/search/msg/created-plural' ), response.created, response.elements );

				// A configured type that could not be indexed is named with its
				// reason rather than dropped from the count in silence
				const skipped = Object.keys( response.skipped || {} ).map( function( key ) {
					return key+ ': '+ ( response.skipped[key] || [] ).join('; ');
				} );

				Nino.admin.search.reload( function() { Nino.admin.search._said( said, skipped ) } );
			} );
		},

		// ---- one type --------------------------------------------------

		/**
		 *	Open a type's four slots
		 *
		 *	@param		{Object}	row
		 *
		 *	@return		void
		 */
		_editType : function( row ) {

			Nino.admin.search._editing = JSON.parse( JSON.stringify( row ) );
			Nino.admin.search._editing.fields = Nino.admin.search._editing.fields || {};

			// What is on screen now is what is saved
			Nino.admin.search._opened = JSON.stringify( Nino.admin.search._editing.fields );
			Nino.admin.search._renderType();
		},

		/**
		 *	Four selects over the type's own model. A field name cannot be
		 *	mistyped into a slot this way, which is the single most common way
		 *	the configuration used to end up doing nothing
		 *
		 *	@return		void
		 */
		_renderType : function() {

			const wrap = dc.getElementById('search-type');
			const row	 = Nino.admin.search._editing;
			if( wrap === null || row === null )
				return;

			levels.show('type');
			wrap.innerHTML = '';

			const back = Nino.adminUi.el( 'a', '', Nino.content.getText('/_admin/search/label/back') );
			back.href = '#';
			back.addEventListener( 'click', function( ev ) {
				ev.preventDefault();
				Nino.admin.search._leave( function() { Nino.admin.search._renderList() } );
			} );
			wrap.appendChild( Nino.adminUi.contextBar( back ) );

			wrap.appendChild( Nino.adminUi.el( 'h2', '', ( row.title || row.type )+ ' — '+ Nino.content.getText('/_admin/search/label/fields') ) );

			[ '/_admin/search/hint/slots', '/_admin/search/hint/indexable' ].forEach( function( key ) {
				wrap.appendChild( Nino.adminUi.hint( Nino.content.getText( key ) ) );
			} );

			if( Nino.admin.search._state( row ).key === 'stale' )
				wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/search/hint/stale'), 'error' ) );

			const slots = Nino.adminUi.el('div');
			slots.id = 'search-slots';

			Object.keys( Nino.admin.search._weights ).sort().forEach( function( priority ) {
				slots.appendChild( Nino.admin.search._renderSlot( priority, row ) );
			} );

			wrap.appendChild( slots );

			const msg = Nino.adminUi.el('p');
			msg.id = 'search-type-msg';

			// Following the slots: a changed select turns "saved" into
			// "unsaved changes", and a slot put back as it was turns it back
			const line = Nino.adminUi.status( msg );
			line.bind( slots, Nino.admin.search._isDirty );

			const save = Nino.adminUi.button( Nino.content.getText('/_admin/search/label/saveandbuild'), function() {
				Nino.admin.search._save( save, line );
			}, { id : 'search-save', className : 'nino-admin-btn-primary' } );

			const actions = Nino.adminUi.el('div');
			actions.appendChild( save );
			actions.appendChild( msg );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );

			// The shell's own Save presses the same button
			Nino.admin.search._line = line;
			Nino.admin.search._saveButton = save;
		},

		/**
		 *	One priority slot
		 *
		 *	@param		{string}	priority
		 *	@param		{Object}	row
		 *
		 *	@return		{Element}
		 */
		_renderSlot : function( priority, row ) {

			const model = row.model || [];

			return Nino.adminUi.selectField( {
				key			: 'slot-'+ priority,
				// Two places always: json turns 1.00 into 1 and 0.70 into 0.7, and
				// "1" beside "0.45" reads as two different kinds of number rather
				// than as one scale
				label		: Nino.adminUi.format( Nino.content.getText('/_admin/search/label/slot'), priority )
					+ ' — '+ Nino.adminUi.format( Nino.content.getText('/_admin/search/label/weight'), Number( Nino.admin.search._weights[priority] ).toFixed( 2 ) ),
				options	: [ { value : '', label : Nino.content.getText('/_admin/search/label/nofield') } ].concat( model.map( function( name ) {
					return { value : name, label : name };
				} ) ),
				value		: model.indexOf( row.fields[priority] ) === -1 ? '' : row.fields[priority],
				onChange	: function( value ) {
					if( value === '' )
						delete Nino.admin.search._editing.fields[priority];
					else
						Nino.admin.search._editing.fields[priority] = value;
				},
			} );
		},

		/**
		 *	Write the slots into config.php, then build the index out of them -
		 *	configuring and indexing are two things, and the button says both
		 *
		 *	@param		{Element}		btn
		 *	@param		{Object}		line				The editor's status line
		 *	@param		{Function}	[done]			Told whether the slots were saved - the shell's Save asks
		 *
		 *	@return		void
		 */
		_save : function( btn, line, done ) {

			const row = Nino.admin.search._editing;
			const finish = function( ok ) {
				if( typeof done === 'function' )
					done( ok );
			};

			// A second click while one is on its way
			if( btn.disabled === true )
				return finish( false );

			btn.disabled = true;
			line.saving();

			Nino.admin.search.call( 'save', { type : row.type, fields : row.fields }, function( status, response ) {

				if( status !== 200 || response === null ) {
					btn.disabled = false;
					line.error( status, response, '/_admin/search/error/save' );
					return finish( false );
				}

				// Nothing is built from no slots, so the slots being stored is
				// all there was to do
				if( response.removed === true || Object.keys( response.fields || {} ).length === 0 ) {
					btn.disabled = false;
					finish( true );
					return Nino.admin.search.reload( function() {
						Nino.admin.search._said( Nino.content.getText( response.removed === true ? '/_admin/search/msg/removed' : '/_admin/search/msg/saved' ) );
					} );
				}

				Nino.admin.search.call( 'createindex', { type : row.type }, function( status, built ) {

					btn.disabled = false;

					// What asked for the save (a log out, a language change)
					// goes on only now: the index is built from the slots, and
					// a request after the session ended is refused
					finish( true );

					const said = status === 200 && built !== null && ( built.created || 0 ) > 0
						? Nino.adminUi.format( Nino.content.getText('/_admin/search/msg/created'), built.created, built.elements )
						: Nino.content.getText('/_admin/search/msg/saved');

					Nino.admin.search.reload( function() { Nino.admin.search._said( said ) } );
				} );
			} );
		},

		// ---- the probe -------------------------------------------------

		/**
		 *	A query, the types to ask, and the hits with their scores. The
		 *	screen this panel exists for: the ranking is the feature, and a
		 *	ranking nobody can see is a ranking nobody can tune
		 *
		 *	@return		void
		 */
		_renderProbe : function() {

			const wrap = dc.getElementById('search-probe');
			if( wrap === null )
				return;

			levels.show('probe');
			wrap.innerHTML = '';

			// Only the types that really are indexed - asking an unconfigured
			// one always answers nothing, which reads as a broken probe
			const searchable = Nino.admin.search._types.filter( function( row ) {
				return row.configured === true && Object.keys( row.fields || {} ).length > 0;
			} );

			const back = Nino.adminUi.el( 'a', '', Nino.content.getText('/_admin/search/label/back') );
			back.href = '#';
			back.addEventListener( 'click', function( ev ) { ev.preventDefault(); Nino.admin.search._renderList() } );

			wrap.appendChild( Nino.adminUi.contextBar( back ) );
			wrap.appendChild( Nino.adminUi.el( 'h2', '', Nino.content.getText('/_admin/search/label/probe') ) );
			wrap.appendChild( Nino.adminUi.hint( Nino.content.getText('/_admin/search/hint/probe') ) );

			if( searchable.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/search/msg/none') ) );
				return;
			}

			if( Nino.admin.search._probe.types.length === 0 )
				Nino.admin.search._probe.types = searchable.map( function( row ) { return row.type } );

			wrap.appendChild( Nino.admin.search._renderProbeForm( searchable ) );

			const results = Nino.adminUi.el('div');
			results.id = 'search-probe-results';
			wrap.appendChild( results );

			if( Nino.admin.search._probe.hits !== null )
				Nino.admin.search._renderHits();
		},

		/**
		 *	The probe's own controls
		 *
		 *	@param		{Array}		searchable		The types that are actually indexed
		 *
		 *	@return		{Element}
		 */
		_renderProbeForm : function( searchable ) {

			const form = Nino.adminUi.el('div');

			const queryField = Nino.adminUi.textField( {
				type		: 'search',
				id			: 'search-probe-query',
				value		: Nino.admin.search._probe.query,
				label		: Nino.content.getText('/_admin/search/label/query'),
			} );
			form.appendChild( queryField );

			const types = Nino.adminUi.el('div');
			types.id = 'search-probe-types';
			searchable.forEach( function( row ) {

				const box = Nino.adminUi.el('label');
				const check = Nino.adminUi.el('input');
				check.type = 'checkbox';
				check.value = row.type;
				check.checked = Nino.admin.search._probe.types.indexOf( row.type ) !== -1;
				check.addEventListener( 'change', function() {
					const at = Nino.admin.search._probe.types.indexOf( row.type );
					if( check.checked === true && at === -1 )
						Nino.admin.search._probe.types.push( row.type );
					if( check.checked === false && at !== -1 )
						Nino.admin.search._probe.types.splice( at, 1 );
				} );
				box.appendChild( check );
				box.appendChild( dc.createTextNode( ' '+ ( row.title || row.type ) ) );
				types.appendChild( box );
			} );
			form.appendChild( types );

			// The language the probe asks in: a field of the form, with its
			// name, and not the pill that says which translation a form shows
			if( Nino.admin.search._locales.length > 1 ) {

				const locale = Nino.admin.search._probe.locale;

				const localeField = Nino.adminUi.selectField( {
					key			: 'locale',
					label		: Nino.content.getText('/_admin/search/label/locale'),
					options	: Nino.admin.search._locales.map( function( code ) { return { value : code, label : code } } ),
					value		: Nino.admin.search._locales.indexOf( locale ) === -1 ? Nino.admin.search._locales[0] : locale,
				} );
				localeField.querySelector('select').id = 'search-probe-locale';
				form.appendChild( localeField );
			}

			const run = Nino.adminUi.button( Nino.content.getText('/_admin/search/label/run'), function() {
				Nino.admin.search._runProbe();
			}, { id : 'search-probe-run', className : 'nino-admin-btn-primary' } );

			queryField.querySelector('input').addEventListener( 'keydown', function( ev ) {
				if( ev.key === 'Enter' )
					Nino.admin.search._runProbe();
			} );

			const actions = Nino.adminUi.el('div');
			actions.appendChild( run );
			form.appendChild( Nino.adminUi.actionBar( actions ) );

			return form;
		},

		/**
		 *	Run the probe against the index that is on disk right now
		 *
		 *	@return		void
		 */
		_runProbe : function() {

			const input	 = dc.getElementById('search-probe-query');
			const locale = dc.getElementById('search-probe-locale');
			const out		 = dc.getElementById('search-probe-results');

			if( input === null || out === null )
				return;

			Nino.admin.search._probe.query	= input.value;
			Nino.admin.search._probe.locale	= locale === null ? '' : locale.value;

			Nino.admin.search.call( 'probe', {
				query 	: Nino.admin.search._probe.query,
				types 	: Nino.admin.search._probe.types,
				locale	: Nino.admin.search._probe.locale,
			}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.search.fail( out, status, response );

				Nino.admin.search._probe.hits		= response.hits || [];
				Nino.admin.search._probe.limit	= response.limit || 0;
				Nino.admin.search._renderHits();
			} );
		},

		/**
		 *	What the probe found, in the order a page would get it
		 *
		 *	@return		void
		 */
		_renderHits : function() {

			const out = dc.getElementById('search-probe-results');
			if( out === null )
				return;

			out.innerHTML = '';

			const hits = Nino.admin.search._probe.hits || [];

			if( hits.length === 0 ) {
				out.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/search/hint/probe-empty') ) );
				return;
			}

			const table = Nino.adminUi.el( 'table', 'nino-admin-table' );
			table.id = 'search-probe-hits';

			const head = Nino.adminUi.el('tr');
			[ '#', '/_admin/search/label/hit', '/_admin/search/label/score', '/_admin/search/label/coverage', '/_admin/search/label/matched' ].forEach( function( key ) {
				head.appendChild( Nino.adminUi.el( 'th', '', key.charAt(0) === '/' ? Nino.content.getText( key ) : key ) );
			} );
			table.appendChild( head );

			hits.forEach( function( hit, at ) {

				const tr = Nino.adminUi.el('tr');
				tr.setAttribute( 'data-uri', hit.uri );

				[
					String( at + 1 ),
					hit.label,
					String( hit.score ),
					Math.round( hit.coverage * 100 )+ '%',
					( hit.matched || [] ).join(', '),
				].forEach( function( value ) {
					tr.appendChild( Nino.adminUi.el( 'td', '', value ) );
				} );

				table.appendChild( tr );
			} );

			out.appendChild( table );

			if( hits.length >= Nino.admin.search._probe.limit )
				out.appendChild( Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/search/hint/probe-limit'), Nino.admin.search._probe.limit ) ) );
		},
	} );

})(window, document);
