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

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.search = {

		_ready		: false,
		// One row per element type the project has - what indexState() answered
		_types		: [],
		// What a match in each priority is worth, straight from Search::WEIGHTS
		_weights	: {},
		// The model field types a slot may be given (Search::INDEXABLE)
		_indexable: [],
		_locales	: [],
		// The type being edited, as a working copy: leaving without saving
		// changes nothing. null while the list is on screen
		_editing	: null,
		// What the probe last ran, so switching panels and back keeps it
		_probe		: { query : '', types : [], locale : '', hits : null, limit : 0 },

		/**
		 *	Load the index state and draw the list
		 *
		 *	@param		{Function}	[then]			Run once the list is back
		 *
		 *	@return		void
		 */
		init : function( then ) {

			const wrap = dc.getElementById('search-list');
			if( wrap === null )
				return;

			Nino.admin.search._apiCall( 'list', {}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.search._showError( wrap, status, response );

				Nino.admin.search._types			= response.types || [];
				Nino.admin.search._weights		= response.weights || {};
				Nino.admin.search._indexable	= response.indexable || [];
				Nino.admin.search._locales		= response.locales || [];
				Nino.admin.search._ready			= true;
				Nino.admin.search._renderList();

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

			if( Nino.admin.search._ready === false )
				return Nino.admin.search.init();

			if( Nino.admin.search._editing !== null )
				return Nino.admin.search._renderType();

			Nino.admin.search._renderList();
		},

		/**
		 *	Call a search/* action
		 *
		 *	@param		{string}		endpoint			Action name, eg. "list" -> "search/list"
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'search/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	Show a failed request's status/error in a container
		 *
		 *	@param		{Element}		container
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		void
		 */
		_showError : function( container, status, response ) {
			container.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/common/error/load') );
			container.appendChild( p );
		},

		/**
		 *	Which of the three panes is on screen
		 *
		 *	@param		{string}	level				'list', 'type' or 'probe'
		 *
		 *	@return		void
		 */
		_level : function( level ) {
			[ 'list', 'type', 'probe' ].forEach( function( name ) {
				const pane = dc.getElementById('search-'+ name );
				if( pane !== null )
					pane.classList.toggle( 'admin-hidden', name !== level );
			} );
		},

		/**
		 *	A text fill, with %s/%d/%n filled in
		 *
		 *	@param		{string}	key
		 *	@param		{...*}		values
		 *
		 *	@return		{string}
		 */
		_text : function( key ) {
			const values = Array.prototype.slice.call( arguments, 1 );
			let out = Nino.content.getText( key );
			values.forEach( function( value ) { out = out.replace( /%[sdn]/, String( value ) ) } );
			return out;
		},

		/**
		 *	The state of one row, as a word and a class - the list's whole point
		 *	is that this is readable without opening anything
		 *
		 *	@param		{Object}	row				One indexState() row
		 *
		 *	@return		{Object}						{ key, label, level }
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

			const wrap = dc.getElementById('search-list');
			if( wrap === null )
				return;

			Nino.admin.search._editing = null;
			Nino.admin.search._level('list');
			wrap.innerHTML = '';

			const heading = dc.createElement('h2');
			heading.textContent = Nino.content.getText('/_admin/search/label/title');
			wrap.appendChild( heading );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/search/hint/intro');
			wrap.appendChild( hint );

			if( Nino.admin.search._types.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/search/hint/empty') ) );
				return;
			}

			wrap.appendChild( Nino.admin.search._renderTable() );

			const msg = dc.createElement('p');
			msg.id = 'search-list-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const rebuild = dc.createElement('button');
			rebuild.type = 'button';
			rebuild.id = 'search-createindex';
			rebuild.className = 'nino-admin-btn-primary';
			rebuild.textContent = Nino.content.getText('/_admin/search/label/rebuild-all');
			rebuild.addEventListener( 'click', function() { Nino.admin.search._rebuild( '', rebuild, msg ) } );

			const probe = dc.createElement('button');
			probe.type = 'button';
			probe.id = 'search-openprobe';
			probe.textContent = Nino.content.getText('/_admin/search/label/probe');
			probe.addEventListener( 'click', function() { Nino.admin.search._renderProbe() } );

			const actions = dc.createElement('div');
			actions.appendChild( rebuild );
			actions.appendChild( probe );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );
			wrap.appendChild( msg );
		},

		/**
		 *	The list itself
		 *
		 *	@return		{Element}
		 */
		_renderTable : function() {

			const table = dc.createElement('table');
			table.className = 'nino-admin-table';
			table.id = 'search-types';

			const head = dc.createElement('tr');
			[ 'type', 'fields', 'elements', 'state' ].forEach( function( name ) {
				const th = dc.createElement('th');
				th.textContent = Nino.content.getText('/_admin/search/label/'+ name );
				head.appendChild( th );
			} );
			head.appendChild( dc.createElement('th') );
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

			const tr = dc.createElement('tr');
			tr.setAttribute( 'data-type', row.type );

			const name = dc.createElement('td');
			const strong = dc.createElement('strong');
			strong.textContent = row.title || row.type;
			name.appendChild( strong );
			const uri = dc.createElement('small');
			uri.textContent = ' '+ row.type;
			name.appendChild( uri );
			tr.appendChild( name );

			// The fields as chips in priority order, so the weighting is
			// readable without opening the editor
			const fields = dc.createElement('td');
			const keys = Object.keys( row.fields || {} ).sort();

			if( keys.length === 0 )
				fields.textContent = '—';
			else
				keys.forEach( function( priority ) {
					const chip = dc.createElement('code');
					chip.className = 'search-chip';
					chip.textContent = row.fields[priority];
					chip.title = Nino.admin.search._text( '/_admin/search/label/slot', priority );
					fields.appendChild( chip );
					fields.appendChild( dc.createTextNode(' ') );
				} );

			( row.issues || [] ).forEach( function( issue ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-error';
				p.textContent = issue;
				fields.appendChild( p );
			} );
			tr.appendChild( fields );

			const counts = dc.createElement('td');
			counts.textContent = row.indexed === true && row.indexedElements !== row.elements
				? row.indexedElements+ ' / '+ row.elements
				: String( row.elements );
			tr.appendChild( counts );

			const state = Nino.admin.search._state( row );
			const cell = dc.createElement('td');
			const badge = dc.createElement('span');
			badge.className = 'search-state search-state--'+ state.level;
			badge.textContent = state.label;
			cell.appendChild( badge );

			if( row.built )
				cell.appendChild( dc.createTextNode( ' '+ Nino.admin.search._text( '/_admin/search/state/built', row.built.substring( 0, 10 ) ) ) );

			tr.appendChild( cell );

			const actions = dc.createElement('td');
			const edit = dc.createElement('button');
			edit.type = 'button';
			edit.className = 'search-btn-small';
			edit.textContent = Nino.content.getText('/_admin/search/label/edit');
			edit.addEventListener( 'click', function() { Nino.admin.search._editType( row ) } );
			actions.appendChild( edit );

			if( state.key === 'missing' || state.key === 'stale' ) {
				const rebuild = dc.createElement('button');
				rebuild.type = 'button';
				rebuild.className = 'search-btn-small';
				rebuild.textContent = Nino.content.getText('/_admin/search/label/rebuild');
				rebuild.addEventListener( 'click', function() {
					Nino.admin.search._rebuild( row.type, rebuild, dc.getElementById('search-list-msg') );
				} );
				actions.appendChild( rebuild );
			}

			tr.appendChild( actions );

			return tr;
		},

		/**
		 *	Rebuild every configured index, or one named type
		 *
		 *	@param		{string}		type			'' for all of them
		 *	@param		{Element}		btn
		 *	@param		{Element}		msg
		 *
		 *	@return		void
		 */
		_rebuild : function( type, btn, msg ) {

			btn.disabled = true;

			if( msg !== null )
				msg.textContent = Nino.content.getText('/_admin/search/msg/creating');

			Nino.admin.search._apiCall( 'createindex', type === '' ? {} : { type : type }, function( status, response ) {

				btn.disabled = false;

				if( status !== 200 || response === null || typeof response !== 'object' ) {
					if( msg !== null )
						msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/search/error/create') );
					return;
				}

				const said = response.created === 0
					? Nino.content.getText('/_admin/search/msg/none')
					: Nino.admin.search._text( response.created === 1 ? '/_admin/search/msg/created' : '/_admin/search/msg/created-plural', response.created, response.elements );

				// A configured type that could not be indexed is named with its
				// reason rather than dropped from the count in silence
				const skipped = Object.keys( response.skipped || {} ).map( function( key ) {
					return key+ ': '+ ( response.skipped[key] || [] ).join('; ');
				} );

				Nino.admin.search.init( function() {
					const back = dc.getElementById('search-list-msg');
					if( back === null )
						return;
					back.textContent = said;
					skipped.forEach( function( line ) {
						const p = dc.createElement('p');
						p.className = 'nino-admin-error';
						p.textContent = line;
						back.appendChild( p );
					} );
				} );
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

			Nino.admin.search._level('type');
			wrap.innerHTML = '';

			const back = dc.createElement('a');
			back.href = '#';
			back.textContent = Nino.content.getText('/_admin/search/label/back');
			back.addEventListener( 'click', function( ev ) { ev.preventDefault(); Nino.admin.search._renderList() } );
			wrap.appendChild( Nino.adminUi.contextBar( back ) );

			const heading = dc.createElement('h2');
			heading.textContent = ( row.title || row.type )+ ' — '+ Nino.content.getText('/_admin/search/label/fields');
			wrap.appendChild( heading );

			[ '/_admin/search/hint/slots', '/_admin/search/hint/indexable' ].forEach( function( key ) {
				const hint = dc.createElement('p');
				hint.className = 'nino-admin-hint';
				hint.textContent = Nino.content.getText( key );
				wrap.appendChild( hint );
			} );

			if( Nino.admin.search._state( row ).key === 'stale' ) {
				const stale = dc.createElement('p');
				stale.className = 'nino-admin-hint search-hint-warn';
				stale.textContent = Nino.content.getText('/_admin/search/hint/stale');
				wrap.appendChild( stale );
			}

			const slots = dc.createElement('div');
			slots.id = 'search-slots';

			Object.keys( Nino.admin.search._weights ).sort().forEach( function( priority ) {
				slots.appendChild( Nino.admin.search._renderSlot( priority, row ) );
			} );

			wrap.appendChild( slots );

			const msg = dc.createElement('p');
			msg.id = 'search-type-msg';
			msg.setAttribute( 'aria-live', 'polite' );

			const save = dc.createElement('button');
			save.type = 'button';
			save.id = 'search-save';
			save.className = 'nino-admin-btn-primary';
			save.textContent = Nino.content.getText('/_admin/search/label/saveandbuild');
			save.addEventListener( 'click', function() { Nino.admin.search._save( save, msg ) } );

			const actions = dc.createElement('div');
			actions.appendChild( save );
			wrap.appendChild( Nino.adminUi.actionBar( actions ) );
			wrap.appendChild( msg );
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

			const wrap = dc.createElement('div');
			wrap.className = 'nino-admin-field';

			const label = dc.createElement('label');
			label.setAttribute( 'for', 'search-slot-'+ priority );
			// Two places always: json turns 1.00 into 1 and 0.70 into 0.7, and
			// "1" beside "0.45" reads as two different kinds of number rather
			// than as one scale
			label.textContent = Nino.admin.search._text( '/_admin/search/label/slot', priority )
				+ ' — '+ Nino.admin.search._text( '/_admin/search/label/weight', Number( Nino.admin.search._weights[priority] ).toFixed( 2 ) );
			wrap.appendChild( label );

			const select = dc.createElement('select');
			select.id = 'search-slot-'+ priority;
			select.setAttribute( 'data-priority', priority );

			const none = dc.createElement('option');
			none.value = '';
			none.textContent = Nino.content.getText('/_admin/search/label/nofield');
			select.appendChild( none );

			( row.model || [] ).forEach( function( field ) {
				const option = dc.createElement('option');
				option.value = field;
				option.textContent = field;
				if( row.fields[priority] === field )
					option.selected = true;
				select.appendChild( option );
			} );

			select.addEventListener( 'change', function() {
				if( select.value === '' )
					delete Nino.admin.search._editing.fields[priority];
				else
					Nino.admin.search._editing.fields[priority] = select.value;
			} );

			wrap.appendChild( select );

			return wrap;
		},

		/**
		 *	Write the slots into config.php, then build the index out of them -
		 *	configuring and indexing are two things, and the button says both
		 *
		 *	@param		{Element}		btn
		 *	@param		{Element}		msg
		 *
		 *	@return		void
		 */
		_save : function( btn, msg ) {

			const row = Nino.admin.search._editing;
			btn.disabled = true;
			msg.textContent = '';

			Nino.admin.search._apiCall( 'save', { type : row.type, fields : row.fields }, function( status, response ) {

				if( status !== 200 || response === null ) {
					btn.disabled = false;
					msg.className = 'nino-admin-error';
					msg.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/search/error/save') );
					return;
				}

				if( response.removed === true || Object.keys( response.fields || {} ).length === 0 ) {
					btn.disabled = false;
					return Nino.admin.search.init( function() {
						const back = dc.getElementById('search-list-msg');
						if( back !== null )
							back.textContent = Nino.content.getText( response.removed === true ? '/_admin/search/msg/removed' : '/_admin/search/msg/saved' );
					} );
				}

				Nino.admin.search._apiCall( 'createindex', { type : row.type }, function( status, built ) {

					btn.disabled = false;

					const said = status === 200 && built !== null && ( built.created || 0 ) > 0
						? Nino.admin.search._text( '/_admin/search/msg/created', built.created, built.elements )
						: Nino.content.getText('/_admin/search/msg/saved');

					Nino.admin.search.init( function() {
						const back = dc.getElementById('search-list-msg');
						if( back !== null )
							back.textContent = said;
					} );
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

			Nino.admin.search._level('probe');
			wrap.innerHTML = '';

			const back = dc.createElement('a');
			back.href = '#';
			back.textContent = Nino.content.getText('/_admin/search/label/back');
			back.addEventListener( 'click', function( ev ) { ev.preventDefault(); Nino.admin.search._renderList() } );
			wrap.appendChild( Nino.adminUi.contextBar( back ) );

			const heading = dc.createElement('h2');
			heading.textContent = Nino.content.getText('/_admin/search/label/probe');
			wrap.appendChild( heading );

			const hint = dc.createElement('p');
			hint.className = 'nino-admin-hint';
			hint.textContent = Nino.content.getText('/_admin/search/hint/probe');
			wrap.appendChild( hint );

			// Only the types that really are indexed - asking an unconfigured
			// one always answers nothing, which reads as a broken probe
			const searchable = Nino.admin.search._types.filter( function( row ) {
				return row.configured === true && Object.keys( row.fields || {} ).length > 0;
			} );

			if( searchable.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/search/msg/none') ) );
				return;
			}

			if( Nino.admin.search._probe.types.length === 0 )
				Nino.admin.search._probe.types = searchable.map( function( row ) { return row.type } );

			wrap.appendChild( Nino.admin.search._renderProbeForm( searchable ) );

			const results = dc.createElement('div');
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

			const form = dc.createElement('div');

			const queryField = dc.createElement('div');
			queryField.className = 'nino-admin-field';

			const label = dc.createElement('label');
			label.setAttribute( 'for', 'search-probe-query' );
			label.textContent = Nino.content.getText('/_admin/search/label/query');
			queryField.appendChild( label );

			const input = dc.createElement('input');
			input.type = 'search';
			input.id = 'search-probe-query';
			input.value = Nino.admin.search._probe.query;
			queryField.appendChild( input );
			form.appendChild( queryField );

			const types = dc.createElement('div');
			types.id = 'search-probe-types';
			searchable.forEach( function( row ) {

				const box = dc.createElement('label');
				const check = dc.createElement('input');
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

			if( Nino.admin.search._locales.length > 1 ) {

				const localeField = dc.createElement('div');
				localeField.className = 'nino-admin-field';

				const localeLabel = dc.createElement('label');
				localeLabel.setAttribute( 'for', 'search-probe-locale' );
				localeLabel.textContent = Nino.content.getText('/_admin/search/label/locale');
				localeField.appendChild( localeLabel );

				const select = dc.createElement('select');
				select.id = 'search-probe-locale';
				Nino.admin.search._locales.forEach( function( locale ) {
					const option = dc.createElement('option');
					option.value = locale;
					option.textContent = locale;
					if( Nino.admin.search._probe.locale === locale )
						option.selected = true;
					select.appendChild( option );
				} );
				localeField.appendChild( select );
				form.appendChild( localeField );
			}

			const run = dc.createElement('button');
			run.type = 'button';
			run.id = 'search-probe-run';
			run.className = 'nino-admin-btn-primary';
			run.textContent = Nino.content.getText('/_admin/search/label/run');
			run.addEventListener( 'click', function() { Nino.admin.search._runProbe() } );

			input.addEventListener( 'keydown', function( ev ) {
				if( ev.key === 'Enter' )
					Nino.admin.search._runProbe();
			} );

			const actions = dc.createElement('div');
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

			Nino.admin.search._apiCall( 'probe', {
				query 	: Nino.admin.search._probe.query,
				types 	: Nino.admin.search._probe.types,
				locale	: Nino.admin.search._probe.locale,
			}, function( status, response ) {

				if( status !== 200 || response === null )
					return Nino.admin.search._showError( out, status, response );

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

			const table = dc.createElement('table');
			table.className = 'nino-admin-table';
			table.id = 'search-probe-hits';

			const head = dc.createElement('tr');
			[ '#', '/_admin/search/label/hit', '/_admin/search/label/score', '/_admin/search/label/coverage', '/_admin/search/label/matched' ].forEach( function( key ) {
				const th = dc.createElement('th');
				th.textContent = key.charAt(0) === '/' ? Nino.content.getText( key ) : key;
				head.appendChild( th );
			} );
			table.appendChild( head );

			hits.forEach( function( hit, at ) {

				const tr = dc.createElement('tr');
				tr.setAttribute( 'data-uri', hit.uri );

				[
					String( at + 1 ),
					hit.label,
					String( hit.score ),
					Math.round( hit.coverage * 100 )+ '%',
					( hit.matched || [] ).join(', '),
				].forEach( function( value ) {
					const td = dc.createElement('td');
					td.textContent = value;
					tr.appendChild( td );
				} );

				table.appendChild( tr );
			} );

			out.appendChild( table );

			if( hits.length >= Nino.admin.search._probe.limit ) {
				const note = dc.createElement('p');
				note.className = 'nino-admin-hint';
				note.textContent = Nino.admin.search._text( '/_admin/search/hint/probe-limit', Nino.admin.search._probe.limit );
				out.appendChild( note );
			}
		},
	};

	Nino.events.bindCallback( 'ready', Nino.admin.search.init );

})(window, document, document.documentElement, document.body);
