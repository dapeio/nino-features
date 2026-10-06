/**
 *	Nino										A compact filesystembased php framework
 *	Modules									Optional modules
 *	Stats										The Stats module's /_admin panel: a month selector, a
 *													plain-css bar per day and the two top-50 tables (pages,
 *													referrer hosts) - see \Nino\Modules\Stats\Admin beside
 *													this file. Read-only, same "always re-fetch" shape as
 *													Newsletter's panel: no drill-down state to preserve, and
 *													re-fetching on every tab switch keeps the numbers current.
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.stats = {

		_months : [],
		_current : '',

		/**
		 *	Load the list of months that have data and render the pane
		 *
		 *	@return		void
		 */
		init : function() {

			if( dc.getElementById('stats-list') === null )
				return;

			Nino.admin.stats._apiCall( 'months', {}, function( status, response ) {
				if( status !== 200 || response === null )
					return Nino.admin.stats._showError( status, response );

				Nino.admin.stats._months = response.months || [];
				if( Nino.admin.stats._months.indexOf( Nino.admin.stats._current ) === -1 )
					Nino.admin.stats._current = Nino.admin.stats._months[0] || '';

				Nino.admin.stats._render();
			} );
		},

		/**
		 *	Re-fetch and re-show when the tab is switched to
		 *
		 *	@return		void
		 */
		showCurrent : function() {
			Nino.admin.stats.init();
		},

		/**
		 *	Call a stats/* admin action. The workbench's own request helper
		 *	posts where this Nino has one - it knows the project's directory
		 *	and what to do when the page has outlived its session; the post
		 *	below is what every panel did before it, with the base the asset
		 *	bundle fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint			Action name (eg. "months", becomes "stats/months")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'stats/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'stats/'+ endpoint, data : JSON.stringify( payload ) } );
		},

		/**
		 *	What a failed request says: the server's code in the workbench's
		 *	language, then its own message, then this panel's sentence - where
		 *	this Nino has errorText(). Before it, "(status) message"
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *	@param		{string}		key					Fill key of the panel's own sentence
		 *
		 *	@return		{string}
		 */
		_errorText : function( status, response, key ) {

			if( Nino.adminUi && Nino.adminUi.api && typeof Nino.adminUi.api.errorText === 'function' )
				return Nino.adminUi.api.errorText( status, response, key );

			return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( key ) );
		},

		/**
		 *	Show a failed request's status/error in the list mount
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		void
		 */
		_showError : function( status, response ) {
			const wrap = dc.getElementById('stats-list');
			wrap.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.stats._errorText( status, response, '/_admin/stats/error/load' );
			wrap.appendChild( p );
		},

		/**
		 *	Render the month selector and load its data
		 *
		 *	@return		void
		 */
		_render : function() {

			const wrap = dc.getElementById('stats-list');
			wrap.innerHTML = '';

			if( Nino.admin.stats._months.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/stats/empty') ) );
				return;
			}

			const label = dc.createElement('label');
			label.htmlFor = 'stats-month';
			label.textContent = Nino.content.getText('/_admin/stats/label/month');
			wrap.appendChild( label );

			const select = dc.createElement('select');
			select.id = 'stats-month';
			Nino.admin.stats._months.forEach( function( month ) {
				const option = dc.createElement('option');
				option.value = month;
				option.textContent = month;
				select.appendChild( option );
			} );
			select.value = Nino.admin.stats._current;
			select.addEventListener( 'change', function() {
				Nino.admin.stats._current = select.value;
				Nino.admin.stats._loadMonth( select.value );
			} );
			wrap.appendChild( select );

			const body = dc.createElement('div');
			body.id = 'stats-body';
			wrap.appendChild( body );

			Nino.admin.stats._loadMonth( Nino.admin.stats._current );
		},

		/**
		 *	Fetch one month's aggregated numbers and render them
		 *
		 *	@param		{string}	month				'YYYY-MM'
		 *
		 *	@return		void
		 */
		_loadMonth : function( month ) {

			if( month === '' )
				return;

			Nino.admin.stats._apiCall( 'month', { month : month }, function( status, response ) {
				if( status !== 200 || response === null )
					return Nino.admin.stats._showError( status, response );

				Nino.admin.stats._renderMonth( response );
			} );
		},

		/**
		 *	Render one month: the summary line, the bar row and the two tables
		 *
		 *	@param		{Object}	data				{ month, days, totals, uris, referrers }
		 *
		 *	@return		void
		 */
		_renderMonth : function( data ) {

			const body = dc.getElementById('stats-body');
			if( body === null )
				return;
			body.innerHTML = '';

			const summary = dc.createElement('p');
			summary.id = 'stats-summary';
			summary.textContent = Nino.admin.stats._summaryText( data.totals );
			body.appendChild( summary );

			body.appendChild( Nino.admin.stats._renderBars( data.days, String( data.month || '' ) ) );

			const tables = dc.createElement('div');
			tables.id = 'stats-tables';
			tables.appendChild( Nino.admin.stats._renderTable( 'uri', Nino.content.getText('/_admin/stats/label/uri'), Nino.admin.stats._pageRows( data.uris ), [
				{ key : 'title', label : Nino.content.getText('/_admin/stats/label/uri'), type : 'string' },
				{ key : 'path', label : Nino.content.getText('/_admin/stats/label/path'), type : 'string', render : Nino.admin.stats._pathCell },
				{ key : 'views', label : Nino.content.getText('/_admin/stats/label/views'), type : 'integer' },
			], Nino.content.getText('/_admin/stats/empty') ) );
			tables.appendChild( Nino.admin.stats._renderTable( 'host', Nino.content.getText('/_admin/stats/label/referrer'), data.referrers, [
				{ key : 'host', label : Nino.content.getText('/_admin/stats/label/referrer'), type : 'string' },
				{ key : 'views', label : Nino.content.getText('/_admin/stats/label/views'), type : 'integer' },
			], Nino.content.getText('/_admin/stats/empty/referrers') ) );
			body.appendChild( tables );
		},

		/**
		 *	The line over the bars: how many views, how many days - each in the
		 *	singular where it is one, so a single view reads "1 view" and not
		 *	"1 views". Two literal lookups per word rather than a key built from
		 *	the number, which the static check every workbench script is held to
		 *	could not see
		 *
		 *	@param		{Object}	totals			{ views, days }
		 *
		 *	@return		{string}
		 */
		_summaryText : function( totals ) {
			return totals.views+ ' '+ Nino.content.getText( totals.views === 1 ? '/_admin/stats/label/view' : '/_admin/stats/label/views' )
				+ ' · '+ totals.days+ ' '+ Nino.content.getText( totals.days === 1 ? '/_admin/stats/label/day' : '/_admin/stats/label/days' );
		},

		/**
		 *	The pages table's rows: the title where the page has one, else the
		 *	path it was counted under - and the overflow bucket by a name of its
		 *	own, since '/…' is not an address anybody can open - plus the path
		 *	in a column of its own. The bucket has none
		 *
		 *	@param		{Array}		rows				[ { uri, title, views }, ... ]
		 *
		 *	@return		{Array}								[ { uri, title, path, views }, ... ]
		 */
		_pageRows : function( rows ) {
			return rows.map( function( row ) {
				const other = row.uri === '/…';
				return {
					uri		: row.uri,
					title	: row.title || ( other ? Nino.content.getText('/_admin/stats/label/other') : row.uri ),
					path	: other ? '' : row.uri,
					views	: row.views,
				};
			} );
		},

		/**
		 *	The path cell: the address, set back from the title beside it
		 *
		 *	@param		{string}	value
		 *
		 *	@return		{Element}
		 */
		_pathCell : function( value ) {
			const span = dc.createElement('span');
			span.className = 'stats-path';
			span.textContent = value;
			return span;
		},

		/**
		 *	The day-by-day bar row: one plain <div> per day of the month, its
		 *	height a percentage of the month's busiest day - no chart library,
		 *	just css (see admin.css's #stats-bars).
		 *
		 *	Every day of the month, not only the days that counted something:
		 *	the store holds a day once it has a view (see Stats::count()), so a
		 *	month with one visit used to be one bar the width of the panel and
		 *	no calendar around it. A day without a view is a column with a
		 *	baseline mark and its number, so the row reads as the month it is
		 *
		 *	@param		{Array}		days				[ { day, total }, ... ], oldest first
		 *	@param		{string}	month				'YYYY-MM', the month the row is of
		 *
		 *	@return		{Element}
		 */
		_renderBars : function( days, month ) {

			const wrap = dc.createElement('div');
			wrap.id = 'stats-bars';

			if( days.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/stats/empty') ) );
				return wrap;
			}

			const totals = {};
			days.forEach( function( entry ) { totals[entry.day] = entry.total } );
			const peak = days.reduce( function( m, entry ) { return Math.max( m, entry.total ) }, 0 );
			const max = Math.max( peak, 1 );

			// The month's length from its own calendar; a row handed days with
			// no month name draws the days it was given
			const parts = /^(\d{4})-(\d{2})$/.exec( month || '' );
			const count = parts === null ? 0 : new Date( Number( parts[1] ), Number( parts[2] ), 0 ).getDate();
			const keys = count > 0
				? Array.from( { length : count }, function( _, i ) { return month+ '-'+ String( i + 1 ).padStart( 2, '0' ) } )
				: days.map( function( entry ) { return entry.day } );

			keys.forEach( function( day ) {

				const total = totals[day] || 0;

				const col = dc.createElement('div');
				col.className = 'stats-bar-col'+ ( total === 0 ? ' is-empty' : '' );
				col.title = day+ ': '+ total;

				const bar = dc.createElement('div');
				bar.className = 'stats-bar';
				bar.style.height = total === 0 ? '2px' : Math.max( 2, Math.round( ( total / max ) * 100 ) )+ '%';

				const value = dc.createElement('span');
				value.className = 'stats-bar-value';
				value.textContent = String( total );
				bar.appendChild( value );

				const label = dc.createElement('span');
				label.className = 'stats-bar-label';
				label.textContent = day.slice( 8 );

				col.appendChild( bar );
				col.appendChild( label );
				wrap.appendChild( col );
			} );

			// The y axis, as far as it goes: the busiest day's count, at the top
			// of the row where its bar reaches. The heights above stay a share
			// of that one number - a scale of nice numbers would make one view
			// less than a full bar, and a single day with a single view is the
			// month the row is most often looking at
			if( peak > 0 ) {
				const axis = dc.createElement('span');
				axis.className = 'stats-axis';
				axis.textContent = Nino.content.getText('/_admin/stats/label/max').replace( '%d', String( peak ) );
				wrap.appendChild( axis );
			}

			return wrap;
		},

		/**
		 *	One top-50 table (pages, or referrer hosts) - the shared,
		 *	searchable/sortable table component, same as Newsletter's list
		 *
		 *	@param		{string}	key					Row property that identifies a row ('uri' or 'host')
		 *	@param		{string}	label				The heading of the card
		 *	@param		{Array}		rows				[ { [key]: string, views: number, ... }, ... ]
		 *	@param		{Array}		columns			The table's columns, see Nino.adminUi.table()
		 *	@param		{string}	empty				What the card says when there are no rows
		 *
		 *	@return		{Element}
		 */
		_renderTable : function( key, label, rows, columns, empty ) {

			const wrap = dc.createElement('div');
			wrap.className = 'nino-admin-card stats-table';

			const heading = dc.createElement('h3');
			heading.textContent = label;
			wrap.appendChild( heading );

			if( rows.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( empty ) );
				return wrap;
			}

			const mount = dc.createElement('div');
			wrap.appendChild( mount );

			Nino.adminUi.table( {
				mount 	: mount,
				rows 		: rows,
				rowKey 	: key,
				columns : columns,
				labels 	: {
					search 	: Nino.content.getText('/_admin/stats/label/search'),
					empty 	: empty,
					noMatch : Nino.content.getText('/_admin/stats/nomatch'),
				},
			} );

			return wrap;
		},
	};

} )(window, document, document.documentElement, document.body);
