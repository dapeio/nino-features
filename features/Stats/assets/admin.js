/**
 *	Nino features
 *	admin.js		The panel of the Stats feature: the month in the context bar, the views of the
 *							month as a bar for each day, and the pages and the referrer hosts as two
 *							tables with a search (see \Nino\Modules\Stats\Admin beside this file).
 *							Read-only, and drawn again with every showing, so the numbers are current.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// The month that is chosen: kept while the panel is on, and chosen again where the list of months no longer holds it
	const S = { months : [], month : '' };

	// The names of the months, one literal lookup each, in the order of the year
	const MONTHS = [ '/_admin/common/word/january', '/_admin/common/word/february', '/_admin/common/word/march', '/_admin/common/word/april', '/_admin/common/word/may', '/_admin/common/word/june',
		'/_admin/common/word/july', '/_admin/common/word/august', '/_admin/common/word/september', '/_admin/common/word/october', '/_admin/common/word/november', '/_admin/common/word/december' ];

	const german = function() { return String( dc.documentElement.lang ).slice( 0, 2 ) === 'de' };

	// A month as the workbench says it: its name and the year
	const monthName = function( month ) {

		const parts = /^(\d{4})-(\d{2})$/.exec( month );

		return parts === null ? month : t( MONTHS[ Number( parts[2] ) - 1 ] )+ ' '+ parts[1];
	};

	// A day as the workbench writes a date: the German way round in German and the year first in any other language
	const dayName = function( day ) {
		return german() === true ? day.slice( 8 )+ '.'+ day.slice( 5, 7 )+ '.'+ day.slice( 0, 4 ) : day;
	};

	// The line over the bars: how many views, how many days - each in the singular where it is one. Two literal lookups for each word
	const summaryText = function( totals ) {
		return totals.views+ ' '+ t( totals.views === 1 ? '/_admin/stats/label/view' : '/_admin/stats/label/views' )
			+ ' · '+ totals.days+ ' '+ t( totals.days === 1 ? '/_admin/stats/label/day' : '/_admin/stats/label/days' );
	};

	// The pages table's rows: the title where the page has one, else the path it was counted under, and the bucket of the pages a day did not keep by a name of its own
	const pageRows = function( rows ) {
		return rows.map( function( row ) {

			const other = row.uri === '/…';

			return { uri : row.uri, title : row.title || ( other ? t('/_admin/stats/label/other') : row.uri ), path : other ? '' : row.uri, views : row.views };
		} );
	};

	// The referrers table's rows: the host, and the bucket of the hosts a day did not keep one by one, since '...' is no host anybody has
	const referrerRows = function( rows ) {
		return rows.map( function( row ) {
			return { host : row.host === '…' ? t('/_admin/stats/label/other-referrers') : row.host, views : row.views };
		} );
	};

	// The bars: one column for each day of the month, its height a share of the busiest day. A day without a view is a column with a mark at its foot and its number, so the row reads as the month it is
	const bars = function( days, month ) {

		const wrap = el( 'div', 'stats-bars' );

		wrap.setAttribute( 'role', 'list' );

		const totals = {};

		days.forEach( function( entry ) { totals[entry.day] = entry.total } );

		const peak = days.reduce( function( most, entry ) { return Math.max( most, entry.total ) }, 0 );
		const parts = /^(\d{4})-(\d{2})$/.exec( month );
		const length = new Date( Number( parts[1] ), Number( parts[2] ), 0 ).getDate();

		for( let at = 1; at <= length; at++ ) {

			const day = month+ '-'+ String( at ).padStart( 2, '0' );
			const total = totals[day] || 0;
			const column = el( 'div', 'stats-bar-col'+ ( total === 0 ? ' is-empty' : '' ) );
			const bar = el( 'div', 'stats-bar' );
			const value = el( 'span', 'stats-bar-value', String( total ) );

			column.setAttribute( 'role', 'listitem' );
			column.setAttribute( 'aria-label', dayName( day )+ ': '+ total );
			column.title = dayName( day )+ ': '+ total;

			bar.style.height = total === 0 ? '' : Math.max( 2, Math.round( ( total / peak ) * 100 ) )+ '%';
			bar.appendChild( value );
			column.appendChild( bar );
			column.appendChild( el( 'span', 'stats-bar-label', String( at ).padStart( 2, '0' ) ) );
			wrap.appendChild( column );
		}

		// The y axis as far as it goes: the busiest day's count, at the top of the row where its bar reaches
		if( peak > 0 )
			wrap.appendChild( el( 'span', 'stats-axis', Nino.adminUi.format( t('/_admin/stats/label/max'), peak ) ) );

		return wrap;
	};

	// One top-50 table with the title of its section over it: the table of the workbench, searched in the browser
	const section = function( title, rows, key, columns, empty, search ) {

		const wrap = el( 'section', 'stats-table' );
		const mount = el( 'div' );

		wrap.appendChild( Nino.adminUi.sectionTitle( title ) );
		wrap.appendChild( mount );

		if( rows.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( empty ) );
			return wrap;
		}

		Nino.adminUi.table( {
			mount	: mount,
			rows	: rows,
			rowKey	: key,
			search	: true,
			columns	: columns,
			labels	: { search : search, empty : empty, noMatch : t('/_admin/stats/nomatch') },
		} );

		return wrap;
	};

	const drawMonth = function( data ) {

		const body = dc.getElementById('stats-body');

		if( body === null )
			return;

		body.innerHTML = '';

		const summary = el( 'p', 'stats-summary', summaryText( data.totals ) );

		summary.setAttribute( 'role', 'status' );
		body.appendChild( summary );

		body.appendChild( Nino.adminUi.sectionTitle( t('/_admin/stats/label/daily') ) );
		body.appendChild( data.days.length === 0 ? Nino.adminUi.emptyState( t('/_admin/stats/empty') ) : bars( data.days, data.month ) );

		const tables = el( 'div', 'stats-tables' );

		tables.appendChild( section( t('/_admin/stats/label/pages'), pageRows( data.uris ), 'uri', [
			{ key : 'title', label : t('/_admin/stats/label/uri'), type : 'string' },
			{ key : 'path', label : t('/_admin/stats/label/path'), type : 'string', render : function( value ) { return el( 'span', 'stats-path', value ) } },
			{ key : 'views', label : t('/_admin/stats/label/views-column'), type : 'integer' },
		], t('/_admin/stats/empty'), t('/_admin/stats/label/search-pages') ) );

		tables.appendChild( section( t('/_admin/stats/label/referrers'), referrerRows( data.referrers ), 'host', [
			{ key : 'host', label : t('/_admin/stats/label/referrer'), type : 'string' },
			{ key : 'views', label : t('/_admin/stats/label/views-column'), type : 'integer' },
		], t('/_admin/stats/empty/referrers'), t('/_admin/stats/label/search-referrers') ) );

		body.appendChild( tables );
	};

	const loadMonth = function( month ) {

		Nino.admin.stats.call( 'month', { month : month }, function( status, response ) {

			if( status !== 200 || response === null )
				return Nino.admin.stats.fail( dc.getElementById('stats-body'), status, response );

			drawMonth( response );
		} );
	};

	Nino.admin.stats = Nino.adminUi.panel( {
		uri		: 'stats',
		mount	: 'stats-list',
		action	: 'months',
		show	: 'always',
		render	: function( data, panel ) {

			const mount = panel.mount();

			S.months = data.months || [];

			if( S.months.indexOf( S.month ) === -1 )
				S.month = S.months[0] || '';

			mount.innerHTML = '';

			if( S.months.length === 0 ) {
				mount.appendChild( Nino.adminUi.emptyState( t('/_admin/stats/empty') ) );
				return;
			}

			// The month is the one choice of the view: it stands in the context bar, where the tools of a view stand
			const select = Nino.adminUi.selectField( {
				key			: 'month',
				label		: t('/_admin/stats/label/month'),
				value		: S.month,
				options		: S.months.map( function( month ) { return { value : month, label : monthName( month ) } } ),
				onChange	: function( month ) {
					S.month = month;
					loadMonth( month );
				},
			} );

			mount.appendChild( Nino.adminUi.contextBar( { tools : [ select ] } ) );

			const body = el( 'div' );

			body.id = 'stats-body';
			mount.appendChild( body );

			loadMonth( S.month );
		},
	} );

} )( window, document );
