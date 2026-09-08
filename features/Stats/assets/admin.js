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
		 *	Call a stats/* admin action
		 *
		 *	@param		{string}		endpoint			Action name (eg. "months", becomes "stats/months")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {
			Nino.http.sendRequest( '/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'stats/'+ endpoint, data : JSON.stringify( payload ) } );
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
			p.textContent = '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText('/_admin/stats/error/load') );
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
			summary.textContent = data.totals.views+ ' '+ Nino.content.getText('/_admin/stats/label/views')
				+ ' · '+ data.totals.days+ ' '+ Nino.content.getText('/_admin/stats/label/days');
			body.appendChild( summary );

			body.appendChild( Nino.admin.stats._renderBars( data.days ) );

			const tables = dc.createElement('div');
			tables.id = 'stats-tables';
			tables.appendChild( Nino.admin.stats._renderTable( 'uri', Nino.content.getText('/_admin/stats/label/uri'), data.uris ) );
			tables.appendChild( Nino.admin.stats._renderTable( 'host', Nino.content.getText('/_admin/stats/label/referrer'), data.referrers ) );
			body.appendChild( tables );
		},

		/**
		 *	The day-by-day bar row: one plain <div> per day, its height a
		 *	percentage of the month's busiest day - no chart library, just
		 *	css (see admin.css's #stats-bars)
		 *
		 *	@param		{Array}		days				[ { day, total }, ... ], oldest first
		 *
		 *	@return		{Element}
		 */
		_renderBars : function( days ) {

			const wrap = dc.createElement('div');
			wrap.id = 'stats-bars';

			if( days.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/stats/empty') ) );
				return wrap;
			}

			const max = days.reduce( function( m, entry ) { return Math.max( m, entry.total ) }, 1 );

			days.forEach( function( entry ) {

				const col = dc.createElement('div');
				col.className = 'stats-bar-col';
				col.title = entry.day+ ': '+ entry.total;

				const bar = dc.createElement('div');
				bar.className = 'stats-bar';
				bar.style.height = Math.max( 2, Math.round( ( entry.total / max ) * 100 ) )+ '%';

				const value = dc.createElement('span');
				value.className = 'stats-bar-value';
				value.textContent = String( entry.total );
				bar.appendChild( value );

				const label = dc.createElement('span');
				label.className = 'stats-bar-label';
				label.textContent = entry.day.slice( 8 );

				col.appendChild( bar );
				col.appendChild( label );
				wrap.appendChild( col );
			} );

			return wrap;
		},

		/**
		 *	One top-50 table (pages, or referrer hosts) - the shared,
		 *	searchable/sortable table component, same as Newsletter's list
		 *
		 *	@param		{string}	key					Row property holding the label column ('uri' or 'host')
		 *	@param		{string}	label				Column caption
		 *	@param		{Array}		rows				[ { [key]: string, views: number }, ... ]
		 *
		 *	@return		{Element}
		 */
		_renderTable : function( key, label, rows ) {

			const wrap = dc.createElement('div');
			wrap.className = 'nino-admin-card stats-table';

			const heading = dc.createElement('h3');
			heading.textContent = label;
			wrap.appendChild( heading );

			if( rows.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/stats/empty') ) );
				return wrap;
			}

			const mount = dc.createElement('div');
			wrap.appendChild( mount );

			Nino.adminUi.table( {
				mount 	: mount,
				rows 		: rows,
				rowKey 	: key,
				columns : [
					{ key : key, label : label, type : 'string' },
					{ key : 'views', label : Nino.content.getText('/_admin/stats/label/views'), type : 'integer' },
				],
				labels 	: {
					search 	: Nino.content.getText('/_admin/stats/label/search'),
					empty 	: Nino.content.getText('/_admin/stats/empty'),
					noMatch : Nino.content.getText('/_admin/stats/nomatch'),
				},
			} );

			return wrap;
		},
	};

} )(window, document, document.documentElement, document.body);
