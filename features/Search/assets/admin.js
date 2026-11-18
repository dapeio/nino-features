/**
 *	Nino features
 *	admin.js		The panel of the Search feature: the index of every element type with its state and
 *							the one button that rebuilds it, the types with the fields their index holds, and
 *							a probe that asks the real index (see \Nino\Modules\Search\Admin beside this
 *							file). Built from the blocks of the workbench: panel(), tabs(), table(),
 *							checklist(), contextBar(), actionBar().
 *
 *							The addresses are #search (the index), #search/types (the types), #search/types/<type>
 *							(the fields of one type, a view of its own) and #search/probe (the probe).
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	/*	What the last answer of the server holds, and what the screen keeps of its own: the tab that is open, what each
		search holds, the lines a rebuild answered with, the type whose fields are being chosen - its fields in the order
		they were ticked, which is their priority - and what the probe was last asked	*/
	const S = { types : [], weights : {}, tab : 'index', query : { index : '', types : '' }, notes : [], later : [], editing : null, probe : { query : '', types : null, hits : null, limit : 0 } };

	let tabs = null;
	let form = null;
	let bar = null;
	let list = null;

	// The address of each tab: the part after #search/
	const PARTS = { index : [], types : [ 'types' ], probe : [ 'probe' ] };

	// The address of a type is its name without the slash in front
	const slugOf = function( type ) { return type.replace( /^\//, '' ) };

	const decimal = function( number ) { return Number( number ).toFixed( 2 ).replace( '.', t('/_admin/common/unit/decimal') || '.' ) };

	// What a type is called: its title where it has one, else its name
	const nameOf = function( row ) { return row.title || row.type };

	/*	What a field is called, as the Elements panel of the workbench says it: a word of the type's own for the field, else the
		label the model gives it, else the word of the vocabulary the key is, else the key. The key stands quiet behind it where
		it is not the word itself. The key of the word is made of its parts, since it is made of the type	*/
	const fieldLabel = function( row, key ) {

		if( key === '.uri' )
			return t('/_admin/search/label/uri');

		const own = t( [ '', '_admin', 'elements', 'field', slugOf( row.type ), key ].join('/') );
		const declared = ( row.labels || {} )[key];
		const word = t( [ '', '_admin', 'common', 'word', key ].join('/') );
		const label = own !== '' ? own : ( typeof declared === 'string' && declared !== '' ? declared : ( word !== '' ? word : key.charAt(0).toUpperCase()+ key.slice(1) ) );

		return label === key ? key : label+ ' ('+ key+ ')';
	};

	// The words of a problem of the configuration: the engine says a code and what belongs in the sentence
	const ISSUE = {
		name : '/_admin/search/issue/name', list : '/_admin/search/issue/list', notype : '/_admin/search/issue/notype',
		priority : '/_admin/search/issue/priority', noname : '/_admin/search/issue/noname', nofield : '/_admin/search/issue/nofield', none : '/_admin/search/issue/none',
	};

	const issueText = function( issue ) {
		return Nino.adminUi.format( t( ISSUE[issue[0]] ?? ISSUE.none ), ...issue.slice( 1 ) );
	};

	const fieldsOf = function( row ) {
		return Object.keys( row.fields || {} ).map( Number ).sort( function( a, b ) { return a - b } ).map( function( priority ) { return row.fields[priority] } );
	};

	// The state of one row, as a word and a level: what the index is, readable without opening anything
	const stateOf = function( row ) {

		if( ( row.issues || [] ).length > 0 && fieldsOf( row ).length === 0 )
			return { key : 'broken', label : t('/_admin/search/state/broken'), level : 'error' };

		if( row.configured !== true || fieldsOf( row ).length === 0 )
			return { key : 'off', label : t('/_admin/search/state/off'), level : 'muted' };

		if( row.indexed !== true )
			return { key : 'missing', label : t('/_admin/search/state/missing'), level : 'warn' };

		if( row.stale === true )
			return { key : 'stale', label : t('/_admin/search/state/stale'), level : 'warn' };

		return { key : 'current', label : t('/_admin/search/state/current'), level : 'ok' };
	};

	// Whether a type has an index to ask: it is configured with a field, whatever the file says
	const searchable = function( row ) { return row.configured === true && fieldsOf( row ).length > 0 };

	const dirty = function() {
		return S.editing !== null && JSON.stringify( S.editing.order ) !== S.editing.opened;
	};

	// Where a move of the person goes, and the screen follows the address
	const go = function( parts ) {
		Nino.admin.router.go( 'search', parts );
		Nino.admin.search.follow();
	};

	// Leaving the fields of a type asks first where they hold input nobody has saved
	const leave = function( proceed ) {

		if( typeof Nino.admin.dirty === 'object' && S.editing !== null )
			Nino.admin.dirty.guard( [ 'search' ], proceed );
		else
			proceed();
	};

	// --- the cells ---------------------------------------------------------------------------------------------------------------

	// The name of a type, and under it what it is called in the model: the address is code, not a word
	const nameCell = function( value, row ) {

		const cell = el( 'span', 'search-type' );

		cell.appendChild( el( 'strong', '', nameOf( row.row ) ) );
		cell.appendChild( el( 'br' ) );
		cell.appendChild( el( 'code', '', row.type ) );

		return cell;
	};

	// The state as the workbench's badge, and the day the index was built
	const stateCell = function( value, row ) {

		const state = stateOf( row.row );
		const cell = el( 'span' );
		const badge = el( 'span', 'nino-admin-badge', ( state.level === 'ok' ? '✓ ' : '● ' )+ state.label );

		badge.dataset.state = state.level;
		cell.appendChild( badge );

		if( row.row.built )
			cell.appendChild( el( 'small', '', ' '+ Nino.adminUi.format( t('/_admin/search/state/built'), row.row.built.substring( 0, 10 ) ) ) );

		return cell;
	};

	// The fields in the order of their priority, as the code they are
	const fieldsCell = function( value, row ) {

		const fields = fieldsOf( row.row );

		return fields.length === 0 ? '' : el( 'span', '', fields.map( function( field ) { return fieldLabel( row.row, field ) } ).join(', ') );
	};

	// How many elements a type has, and how many of them are in its index where that is fewer
	const countText = function( row ) {
		return row.indexed === true && row.indexedElements !== row.elements ? row.indexedElements+ ' / '+ row.elements : String( row.elements );
	};

	const tableRows = function() {

		return S.types.map( function( row ) {
			return {
				type		: row.type,
				title		: nameOf( row )+ ' '+ row.type,
				fields	: fieldsOf( row ).map( function( field ) { return fieldLabel( row, field ) } ).concat( ( row.issues || [] ).map( issueText ) ).join(' '),
				elements	: row.elements,
				state		: stateOf( row ).label,
				row			: row,
			};
		} );
	};

	// --- the index ------------------------------------------------------------------------------------------------------------------

	/**
	 *	Rebuild every configured index, or one named type, and say what that did: the toast for what was made, the
	 *	notices for what could not be
	 *
	 *	@param		{string}		type				'' for all of them
	 *	@param		{Element}		button
	 */
	const rebuild = function( type, button ) {

		button.disabled = true;

		Nino.admin.search.call( 'createindex', type === '' ? {} : { type : type }, function( status, response ) {

			button.disabled = false;

			if( status !== 200 || response === null || typeof response !== 'object' ) {
				S.notes = [ Nino.adminUi.api.errorText( status, response, '/_admin/search/error/create' ) ];
				draw();
				return;
			}

			// A configured type that could not be indexed is named with its reason and not dropped from the count in silence
			S.later = Object.keys( response.skipped || {} ).map( function( key ) { return key+ ': '+ ( response.skipped[key] || [] ).map( issueText ).join('; ') } );

			Nino.admin.search.reload( function() {
				Nino.adminUi.say( response.created === 0 ? t('/_admin/search/msg/none') : Nino.adminUi.format( t( response.created === 1 ? '/_admin/search/msg/created' : '/_admin/search/msg/created-plural' ), response.created, response.elements ) );
			} );
		} );
	};

	const indexPane = function() {

		const pane = el( 'div' );
		const mount = el( 'div' );

		pane.id = 'search-index';

		// What the index is stands behind the (?) of the title of the section, not in a paragraph over it
		const title = Nino.adminUi.sectionTitle( t('/_admin/search/tab/index') );

		pane.appendChild( title );
		Nino.adminUi.sectionHelp( title, pane, t('/_admin/search/hint/index') );

		S.notes.forEach( function( note ) { pane.appendChild( Nino.adminUi.notice( note ) ) } );

		pane.appendChild( mount );

		if( S.types.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( t('/_admin/search/empty/types') ) );
			return pane;
		}

		Nino.adminUi.table( {
			mount		: mount,
			rowKey	: 'type',
			search	: true,
			query		: S.query.index,
			onQuery	: function( value ) { S.query.index = value },
			rows		: tableRows(),
			labels	: { search : t('/_admin/search/label/search'), empty : t('/_admin/search/empty/types'), noMatch : t('/_admin/search/empty/nomatch') },
			columns	: [
				{ key : 'title', label : t('/_admin/search/label/type'), type : 'string', render : nameCell },
				{ key : 'elements', label : t('/_admin/search/label/elements'), type : 'integer', render : function( value, row ) { return countText( row.row ) } },
				{ key : 'state', label : t('/_admin/search/label/state'), type : 'string', render : stateCell },
			],
			actions	: function( row ) {

				const state = stateOf( row.row );

				// An index that is missing or old is the one a rebuild is for; the others are rebuilt with the button of the bar
				if( state.key !== 'missing' && state.key !== 'stale' )
					return [];

				const make = Nino.adminUi.button( t('/_admin/search/label/rebuild'), function() { rebuild( row.type, make ) }, { small : true } );

				return [ make ];
			},
		} );

		const all = Nino.adminUi.button( t('/_admin/search/label/rebuild-all'), function() { rebuild( '', all ) }, { kind : 'primary' } );

		pane.appendChild( Nino.adminUi.actionBar( { list : true, primary : all } ).el );

		return pane;
	};

	// --- the types ------------------------------------------------------------------------------------------------------------------

	const typesPane = function() {

		const pane = el( 'div' );
		const mount = el( 'div' );

		pane.id = 'search-types';

		const title = Nino.adminUi.sectionTitle( t('/_admin/search/tab/types') );

		pane.appendChild( title );
		Nino.adminUi.sectionHelp( title, pane, t('/_admin/search/hint/types') );

		// A name in the configuration that does not resolve is said with the reason, until it is mended
		S.types.forEach( function( row ) {
			( row.issues || [] ).forEach( function( issue ) { pane.appendChild( Nino.adminUi.notice( row.type+ ': '+ issueText( issue ) ) ) } );
		} );

		pane.appendChild( mount );

		if( S.types.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( t('/_admin/search/empty/types') ) );
			return pane;
		}

		Nino.adminUi.table( {
			mount		: mount,
			rowKey	: 'type',
			search	: true,
			query		: S.query.types,
			onQuery	: function( value ) { S.query.types = value },
			rows		: tableRows(),
			labels	: { search : t('/_admin/search/label/search'), empty : t('/_admin/search/empty/types'), noMatch : t('/_admin/search/empty/nomatch') },
			columns	: [
				{ key : 'title', label : t('/_admin/search/label/type'), type : 'string', render : nameCell },
				{ key : 'fields', label : t('/_admin/search/label/fields'), type : 'string', render : fieldsCell },
			],
			actions	: function( row ) {

				const edit = Nino.adminUi.button( t('/_admin/search/label/edit'), function() { go( [ 'types', slugOf( row.type ) ] ) }, { small : true } );

				return [ edit ];
			},
			onRowClick : function( row ) { go( [ 'types', slugOf( row.type ) ] ) },
		} );

		return pane;
	};

	// --- the fields of one type, a view of its own ----------------------------------------------------------------------

	// What each row of the list says of the place its field holds: nothing for a field that is not ticked
	const paintSlots = function( box, options ) {

		const max = Object.keys( S.weights ).length;

		box.controls.forEach( function( control, at ) {

			const place = S.editing.order.indexOf( options[at].value );
			const state = control.parentNode.querySelector('.nino-admin-checklist-state');

			if( state !== null )
				state.textContent = place === -1 ? '' : Nino.adminUi.format( t('/_admin/search/label/slot'), place + 1, decimal( S.weights[place] ?? 0 ) );

			// The places are as many as the weights the engine has: the rest of the boxes wait until one is let go
			control.disabled = place === -1 && S.editing.order.length >= max;
		} );
	};

	/**
	 *	Write the fields into config.php - and, where the person asked for it, build the index out of them. Configuring
	 *	and indexing are two things, and the bar says both
	 *
	 *	@param		{boolean}		build				Build the index after the save
	 *	@param		{Function}	[done]			Told whether the fields were saved - the shell's Save asks
	 */
	const save = function( build, done ) {

		const finish = function( ok ) {
			if( typeof done === 'function' )
				done( ok );
		};

		const row = S.editing;
		const fields = {};

		row.order.forEach( function( field, place ) { fields[place] = field } );

		bar.line.saving();

		Nino.admin.search.call( 'save', { type : row.type, fields : fields }, function( status, response ) {

			if( status !== 200 || response === null ) {
				bar.line.error( status, response, '/_admin/search/error/save' );
				finish( false );
				return;
			}

			const stay = function( word ) {

				row.opened = JSON.stringify( row.order );
				bar.line.saved();
				finish( true );

				// The state of the type changed with it: the list is asked again, the view stays where it is
				// A choice that is saved and not built is said where the index is out of date after it: it holds what both the old and the new choice share until it is made again
				Nino.admin.search.reload( function() {

					const now = S.types.find( function( found ) { return found.type === row.type } );

					if( typeof word === 'string' )
						Nino.adminUi.say( word );
					else if( build === false && now !== undefined && stateOf( now ).key === 'stale' )
						Nino.adminUi.say( t('/_admin/search/msg/rebuild') );
				} );
			};

			// Nothing is built from no fields: the type is out of the configuration and its index file is gone
			if( build === false || response.removed === true || Object.keys( response.fields || {} ).length === 0 )
				return stay( response.removed === true ? t('/_admin/search/msg/removed') : undefined );

			Nino.admin.search.call( 'createindex', { type : row.type }, function( status, built ) {

				if( status !== 200 || built === null ) {
					bar.line.error( status, built, '/_admin/search/error/create' );
					finish( true );
					return;
				}

				stay( Nino.adminUi.format( t('/_admin/search/msg/created'), built.created, built.elements ) );
			} );
		} );
	};

	const typeView = function( row ) {

		const mount = dc.getElementById('search-type');

		if( mount === null )
			return;

		const fresh = S.editing === null || S.editing.type !== row.type;
		const was = fieldsOf( row );

		if( fresh === true )
			S.editing = { type : row.type, order : was, opened : JSON.stringify( was ) };

		// The view of the type that is open stays as it is after a save - the focus is in it - and only says what the state of the index is now
		const open = fresh === false && form !== null && dc.getElementById('search-type-note') !== null;
		const stale = function() {

			const note = dc.getElementById('search-type-note');

			if( note === null )
				return;

			note.innerHTML = '';

			// An index that is older than the type, or was built out of other fields, is said until it is made again
			if( stateOf( row ).key === 'stale' )
				note.appendChild( Nino.adminUi.notice( t('/_admin/search/hint/stale') ) );
		};

		if( open === true ) {
			stale();
			return;
		}

		const options = ( row.model || [] ).map( function( name ) { return { value : name, label : fieldLabel( row, name ), state : ' ' } } );
		const view = el( 'div' );

		view.appendChild( Nino.adminUi.contextBar( {
			back	: { to : t('/_admin/search/tab/types'), href : Nino.admin.router.href( 'search', PARTS.types ), onClick : function() { leave( function() { S.editing = null; go( PARTS.types ) } ) } },
			title	: nameOf( row ),
			code	: row.type,
		} ) );

		const note = el( 'div' );

		note.id = 'search-type-note';
		view.appendChild( note );

		const box = Nino.adminUi.checklist( {
			key			: 'fields',
			label		: t('/_admin/search/label/fields'),
			help		: t('/_admin/search/hint/fields'),
			options	: options,
			value		: S.editing.order,
			onChange	: function( values ) {

				// A field that stays keeps its place, one that is ticked goes behind them: the order of ticking is the priority
				const kept = S.editing.order.filter( function( field ) { return values.indexOf( field ) !== -1 } );

				values.forEach( function( field ) { if( kept.indexOf( field ) === -1 ) kept.push( field ) } );
				S.editing.order = kept;
				paintSlots( box, options );
			},
		} );

		// The one shape of a form: a fieldset with its legend, the grid in it that puts the label at the left of the field
		const set = el( 'fieldset' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );

		set.appendChild( el( 'legend', '', t('/_admin/search/label/index') ) );
		grid.appendChild( box.el );
		set.appendChild( grid );

		form = el( 'form' );
		form.noValidate = true;
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); save( false ) } );

		const build = Nino.adminUi.button( t('/_admin/search/label/saveandbuild'), function() { save( true ) }, { kind : 'secondary' } );

		bar = Nino.adminUi.actionBar( { status : true, secondary : [ build ], primary : Nino.adminUi.button( t('/_admin/common/label/save'), null, { type : 'submit' } ) } );

		form.appendChild( set );
		form.appendChild( bar.el );
		bar.line.bind( form, dirty );
		paintSlots( box, options );

		view.appendChild( form );
		mount.innerHTML = '';
		mount.appendChild( view );
		stale();
	};

	// --- the probe ------------------------------------------------------------------------------------------------------------------

	const hitRows = function( hits ) {

		return hits.map( function( hit, at ) {
			return { uri : hit.uri, n : at + 1, label : hit.label, score : hit.score, coverage : Math.round( hit.coverage * 100 ), matched : ( hit.matched || [] ).join(', ') };
		} );
	};

	// What the probe found, in the order a page would get it
	const drawHits = function( out ) {

		out.innerHTML = '';

		if( S.probe.hits === null )
			return;

		if( S.probe.hits.length === 0 ) {
			out.appendChild( Nino.adminUi.emptyState( t('/_admin/search/empty/probe') ) );
			return;
		}

		const mount = el( 'div' );

		out.appendChild( mount );

		Nino.adminUi.table( {
			mount		: mount,
			rowKey	: 'uri',
			rows		: hitRows( S.probe.hits ),
			labels	: { search : t('/_admin/search/label/search-hits'), empty : t('/_admin/search/empty/probe'), noMatch : t('/_admin/search/empty/probe') },
			columns	: [
				{ key : 'n', label : '#', type : 'integer' },
				{ key : 'label', label : t('/_admin/search/label/hit'), type : 'string' },
				{ key : 'score', label : t('/_admin/search/label/score'), type : 'double' },
				{ key : 'coverage', label : t('/_admin/search/label/coverage'), type : 'integer', render : function( value ) { return value+ ' %' } },
				{ key : 'matched', label : t('/_admin/search/label/matched'), type : 'string' },
			],
		} );

		if( S.probe.hits.length >= S.probe.limit )
			out.appendChild( el( 'p', 'nino-admin-hint', Nino.adminUi.format( t('/_admin/search/hint/probe-limit'), S.probe.limit ) ) );
	};

	const probePane = function() {

		const pane = el( 'div' );
		const rows = S.types.filter( searchable );

		pane.id = 'search-probe';

		// Asking a type nobody indexed always answers nothing, which reads as a broken probe: the panel says so first
		if( rows.length === 0 ) {
			pane.appendChild( Nino.adminUi.emptyState( t('/_admin/search/empty/probe-types') ) );
			return pane;
		}

		if( S.probe.types === null )
			S.probe.types = rows.map( function( row ) { return row.type } );

		const set = el( 'fieldset' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );
		const legend = el( 'legend', '', t('/_admin/search/tab/probe') );
		const out = el( 'div' );

		// The language the probe asks in is the working language of the workbench, the badge says it where it is not the native one
		const badge = typeof Nino.admin.workLocale === 'object' ? Nino.adminUi.localeBadge( Nino.admin.workLocale.current ) : null;

		if( badge !== null ) {
			legend.appendChild( dc.createTextNode(' ') );
			legend.appendChild( badge );
		}

		set.appendChild( legend );
		Nino.adminUi.sectionHelp( legend, set, t('/_admin/search/hint/probe') );

		const query = Nino.adminUi.textField( { key : 'query', label : t('/_admin/search/label/query'), value : S.probe.query, id : 'search-probe-query', maxLength : 256 } );
		const types = Nino.adminUi.checklist( {
			key			: 'types',
			label		: t('/_admin/search/label/probe-types'),
			options	: rows.map( function( row ) { return { value : row.type, label : nameOf( row ) } } ),
			value		: S.probe.types,
			onChange	: function( values ) { S.probe.types = values },
		} );

		grid.appendChild( query );
		grid.appendChild( types.el );
		set.appendChild( grid );

		const probeForm = el( 'form' );

		probeForm.noValidate = true;
		out.setAttribute( 'role', 'status' );
		out.setAttribute( 'aria-live', 'polite' );

		probeForm.addEventListener( 'submit', function( event ) {

			event.preventDefault();

			const input = query.querySelector('input');

			S.probe.query = input.value;
			Nino.adminUi.clearFieldErrors( probeForm );

			Nino.admin.search.call( 'probe', { query : S.probe.query, types : S.probe.types, locale : typeof Nino.admin.workLocale === 'object' ? ( Nino.admin.workLocale.current ?? '' ) : '' }, function( status, response ) {

				if( status !== 200 || response === null ) {
					Nino.adminUi.fieldError( query, input, 'search-probe-error', Nino.adminUi.api.errorText( status, response, '/_admin/search/error/probe' ) );
					input.focus();
					return;
				}

				S.probe.hits = response.hits || [];
				S.probe.limit = response.limit || 0;
				drawHits( out );
			} );
		} );

		probeForm.appendChild( set );
		probeForm.appendChild( out );
		probeForm.appendChild( Nino.adminUi.actionBar( { primary : Nino.adminUi.button( t('/_admin/search/label/run'), null, { type : 'submit' } ) } ).el );
		pane.appendChild( probeForm );

		drawHits( out );

		return pane;
	};

	// --- the screen ----------------------------------------------------------------------------------------------------------------

	const draw = function() {

		list = dc.getElementById('search-list');

		if( list === null || dc.getElementById('search-type') === null )
			return;

		const index = indexPane();
		const types = typesPane();
		const probe = probePane();

		list.innerHTML = '';

		tabs = Nino.adminUi.tabs( [
			{ key : 'index', label : t('/_admin/search/tab/index'), panel : index },
			{ key : 'types', label : t('/_admin/search/tab/types'), panel : types },
			{ key : 'probe', label : t('/_admin/search/tab/probe'), panel : probe },
		], {
			active	: S.tab,
			id			: 'search-tabs',
			label		: t('/_admin/nav/search'),
			onSelect	: function( key ) { S.tab = key; Nino.admin.router.go( 'search', PARTS[key] ) },
		} );

		list.appendChild( tabs.el );
		list.appendChild( index );
		list.appendChild( types );
		list.appendChild( probe );
	};

	Nino.admin.search = Nino.adminUi.panel( {
		uri		: 'search',
		mount	: 'search-list',
		show	: 'always',
		error	: '/_admin/common/error/load',
		/*	The shell asks Save, Discard or Cancel before a log out or a language change would lose fields nobody has saved	*/
		dirty	: {
			isDirty : dirty,
			save : function( done ) {

				if( S.editing === null || bar === null )
					return done( false );

				save( false, done );
			},
			// What the shell is about to leave takes the choice with it
			discard : function() { S.editing = null },
		},
		render	: function( data ) {

			S.types = data.types || [];
			// What a match in each priority is worth, straight from Search::WEIGHTS
			S.weights = data.weights || {};
			S.notes = S.later;
			S.later = [];

			draw();
			Nino.admin.search.follow();
		},
	} );

	Object.assign( Nino.admin.search, {

		/**
		 *	Show what the address names: the fields of one type, or a tab of the strip
		 *
		 *	@return		void
		 */
		follow : function() {

			const at = Nino.admin.router.current();
			const parts = at.panel === 'search' ? at.parts : [];
			const row = parts[0] === 'types' && typeof parts[1] === 'string' ? S.types.find( function( found ) { return slugOf( found.type ) === parts[1] } ) : undefined;

			if( row !== undefined ) {

				// The view of the type that is open is drawn again after a save and keeps the focus where it is; another one takes it
				const same = S.editing !== null && S.editing.type === row.type;

				typeView( row );
				Nino.adminUi.showLevel( 'search-type', { focus : same === true ? false : true } );
				Nino.admin.router.set( 'search', [ 'types', parts[1] ] );
				return;
			}

			S.editing = null;
			S.tab = parts[0] === 'types' ? 'types' : ( parts[0] === 'probe' ? 'probe' : 'index' );

			if( tabs !== null )
				tabs.select( S.tab );

			Nino.adminUi.showLevel( 'search-list', { focus : false } );
			Nino.admin.router.set( 'search', PARTS[S.tab] );
		},
	} );

	// A view that follows the working language: what the probe found was found in the one that is gone
	if( typeof Nino.admin.workLocale === 'object' )
		Nino.admin.workLocale.follow( 'search', function() {

			S.probe.hits = null;

			if( dc.getElementById('search-list') !== null && Nino.admin.search.ready === true )
				draw();
		} );

} )( window, document );
