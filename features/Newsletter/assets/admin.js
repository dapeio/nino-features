/**
 *	Nino										A compact filesystembased php framework
 *	Modules									Optional modules
 *	Nino										Framework
 *	admin.js								The Newsletter feature's /_admin panel: view of every signup
 *													\Nino\Modules\Newsletter records - see Newsletter\Admin in
 *													Admin/Admin.php - plus a one-line, copyable BCC address
 *													field, since the actual send always happens elsewhere (own
 *													mail client, or a project-specific ESP), never from Nino
 *													itself. Delete is the only write this panel does, but not
 *													the only way an entry goes: a subscriber takes themselves
 *													off the list through the unsubscribe link the feature's own
 *													GET /.newsletter route answers (see Modules\Newsletter).
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn,dc,dE,bd) {

	wn.Nino.admin = wn.Nino.admin || {};

	Nino.admin.newsletter = {

		/**
		 *	Load the recorded signups and render them. Same "always
		 *	re-fetch" shape as logs.js - there's no drill-down state to
		 *	preserve, and re-fetching on every tab switch keeps the list
		 *	current with whatever arrived since it was last open
		 *
		 *	@return		void
		 */
		init : function() {
			Nino.admin.newsletter._load( 'all' );
		},

		/**
		 *	Fetch the list and draw it with a status filter chosen - 'all' for a
		 *	fresh screen, the one that was on screen after a delete
		 *
		 *	@param		{string}	filter				'all', 'subscribed' or 'pending'
		 *
		 *	@return		void
		 */
		_load : function( filter ) {

			if( dc.getElementById('newsletter-list') === null )
				return;

			Nino.admin.newsletter._apiCall( 'list', {}, function( status, response ) {
				if( status !== 200 || response === null )
					return Nino.admin.newsletter._showError( status, response );

				Nino.admin.newsletter._renderList( response, filter );
			} );
		},

		/**
		 *	Re-fetch and re-show the list when the tab is switched to
		 *
		 *	@return		void
		 */
		showCurrent : function() {
			Nino.admin.newsletter.init();
		},

		/**
		 *	Call a newsletter/* admin action. The workbench's own request helper
		 *	posts where this Nino has one - it knows the project's directory
		 *	and what to do when the page has outlived its session; the post
		 *	below is what every panel did before it, with the base the asset
		 *	bundle fills in, because Nino.dir does not exist before Nino 1.3.2
		 *
		 *	@param		{string}		endpoint			Action name (eg. "list", becomes "newsletter/list")
		 *	@param		{Object}		payload				Request payload, sent json-encoded as "data"
		 *	@param		{Function}	callback			Called with ( xhr.status, xhr.responseJSON )
		 *
		 *	@return		void
		 */
		_apiCall : function( endpoint, payload, callback ) {

			if( Nino.adminUi && Nino.adminUi.api )
				return Nino.adminUi.api.call( 'newsletter/'+ endpoint, payload, callback );

			Nino.http.sendRequest( '[[/nino/dir]]/_admin/', 'POST', function( xhr ) {
				callback( xhr.status, xhr.responseJSON );
			}, { action : 'newsletter/'+ endpoint, data : JSON.stringify( payload ) } );
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
		 *	Show a failed request's status/error
		 *
		 *	@param		{number}		status
		 *	@param		{*}					response
		 *
		 *	@return		void
		 */
		_showError : function( status, response ) {
			const wrap = dc.getElementById('newsletter-list');
			wrap.innerHTML = '';
			const p = dc.createElement('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.admin.newsletter._errorText( status, response, '/_admin/newsletter/error/load' );
			wrap.appendChild( p );
		},

		/**
		 *	The rows a status filter lets through - all of them for 'all' (or
		 *	anything that is not one of the two statuses), else the entries of
		 *	that status. Pure: the table and the CSV export both ask it, so
		 *	what is on screen is what is exported
		 *
		 *	@param		{Array}		entries				[ { email, status, date, ip }, ... ]
		 *	@param		{string}	filter				'all', 'subscribed' or 'pending'
		 *
		 *	@return		{Array}
		 */
		_rows : function( entries, filter ) {

			if( filter !== 'subscribed' && filter !== 'pending' )
				return entries;

			return entries.filter( function( entry ) { return ( entry.status === 'pending' ? 'pending' : 'subscribed' ) === filter } );
		},

		/**
		 *	The BCC line: the confirmed addresses, comma-separated - a
		 *	standard BCC field's own separator. A pending address has not
		 *	agreed to anything yet and is not mailed
		 *
		 *	@param		{Array}		entries				[ { email, status, date, ip }, ... ]
		 *
		 *	@return		{string}
		 */
		_bccLine : function( entries ) {
			return Nino.admin.newsletter._rows( entries, 'subscribed' ).map( function( entry ) { return entry.email ?? '' } ).join(', ');
		},

		/**
		 *	Render the subscriber count, a copyable BCC address line and
		 *	the individual entries, most recent first (already sorted that
		 *	way by the server), filterable by status
		 *
		 *	@param		{Object}	response			{ entries : [ { email, status, date, ip }, ... ], counts : { subscribed, pending }, unsubscribeUrl }
		 *	@param		{string}	[filter]			The status filter to start on, 'all' when left out
		 *
		 *	@return		void
		 */
		_renderList : function( response, filter ) {

			const entries = response.entries;
			const counts 	= response.counts || { subscribed : 0, pending : 0 };
			const wrap 		= dc.getElementById('newsletter-list');
			wrap.innerHTML = '';

			if( entries.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/newsletter/empty') ) );
				return;
			}

			// The confirmed addresses are the subscribers; what is still waiting
			// for its link to be visited is named beside them, not counted in
			const summary = dc.createElement('p');
			summary.id = 'newsletter-summary';
			summary.textContent = counts.subscribed+ ' '+ Nino.content.getText( counts.subscribed === 1 ? '/_admin/newsletter/label/subscriber' : '/_admin/newsletter/label/subscribers' )
				+ ( counts.pending > 0 ? ' · '+ counts.pending+ ' '+ Nino.content.getText('/_admin/newsletter/label/pending') : '' );
			wrap.appendChild( summary );

			// What the filter says now - the table shows it and the export writes it
			const state = { filter : filter === 'subscribed' || filter === 'pending' ? filter : 'all' };

			wrap.appendChild( Nino.admin.newsletter._renderBcc( entries, response.unsubscribeUrl, function() { return Nino.admin.newsletter._rows( entries, state.filter ) } ) );

			const statusLabels = {
				subscribed 	: Nino.content.getText('/_admin/newsletter/status/subscribed'),
				pending 		: Nino.content.getText('/_admin/newsletter/status/pending'),
			};

			const filterMount = dc.createElement('div');
			filterMount.id = 'newsletter-filter';
			wrap.appendChild( filterMount );

			const table = dc.createElement('div');
			table.id = 'newsletter-entries';
			wrap.appendChild( table );

			// Subscribers are records, not cards - the shared table gives this
			// list search, sorting and paging that it never had. The mail column
			// and the per-row delete are drawn through the column render hook,
			// so both stay sortable/searchable on their plain values - and so
			// does the status, which is searched and sorted on its slug while
			// the cell says it in words
			const list = Nino.adminUi.table( {
				mount 	: table,
				rows 		: Nino.admin.newsletter._rows( entries, state.filter ),
				rowKey 	: 'email',
				columns : [
					{ key : 'email', label : Nino.content.getText('/_admin/newsletter/label/mail'), type : 'string',
					  render : function( value ) {
							const link = dc.createElement('a');
							link.href = 'mailto:'+ ( value ?? '' );
							link.textContent = value ?? '';
							return link;
						} },
					{ key : 'status', label : Nino.content.getText('/_admin/newsletter/label/status'), type : 'string',
					  render : function( value ) { return statusLabels[ value ] ?? value } },
					{ key : 'date', label : Nino.content.getText('/_admin/newsletter/label/date'), type : 'datetime' },
					{ key : 'email', label : '', type : 'string',
					  render : function( value ) {
							const btn = dc.createElement('button');
							btn.type = 'button';
							btn.className = 'nino-admin-btn-danger newsletter-entry-delete';
							btn.textContent = Nino.content.getText('/_admin/newsletter/label/delete');
							btn.addEventListener( 'click', function() { Nino.admin.newsletter._delete( value, state.filter ) } );
							return btn;
						} },
				],
				labels 	: {
					search 	: Nino.content.getText('/_admin/newsletter/label/search'),
					// Only reached under a status filter - with no entries at
					// all the list says so before this table is drawn
					empty 	: Nino.content.getText('/_admin/newsletter/emptyfilter'),
					noMatch : Nino.content.getText('/_admin/newsletter/nomatch'),
				},
			} );

			// A change sets the rows of the table that is there - search box and
			// all - instead of drawing it again
			filterMount.appendChild( Nino.adminUi.selectField( {
				key 			: 'newsletter-filter',
				label 		: Nino.content.getText('/_admin/newsletter/label/filter'),
				value 		: state.filter,
				options 	: [
					{ value : 'all', 				label : Nino.content.getText('/_admin/newsletter/filter/all') },
					{ value : 'subscribed', label : statusLabels.subscribed },
					{ value : 'pending', 		label : statusLabels.pending },
				],
				onChange 	: function( value ) {
					state.filter = value;
					list.setRows( Nino.admin.newsletter._rows( entries, state.filter ) );
				},
			} ) );
		},

		/**
		 *	Delete one subscriber, after a confirm prompt, then re-fetch
		 *	the list - simplest way to keep the BCC field/summary count in
		 *	sync, same "always re-fetch" shape the rest of this panel uses -
		 *	and draw it on the status filter that was chosen
		 *
		 *	@param		{string}	email
		 *	@param		{string}	filter				The status filter on screen
		 *
		 *	@return		void
		 */
		_delete : function( email, filter ) {

			if( wn.confirm( email+ Nino.content.getText('/_admin/newsletter/confirm/delete') ) === false )
				return;

			Nino.admin.newsletter._apiCall( 'delete', { email : email }, function( status, response ) {
				if( status !== 200 )
					return Nino.admin.newsletter._showError( status, response );

				Nino.admin.newsletter._load( filter );
			} );
		},

		/**
		 *	Build the read-only BCC textarea (every confirmed email, comma-
		 *	separated - a standard BCC field's own separator) plus its
		 *	copy-to-clipboard button and the address of the page a subscriber
		 *	without a link asks for one at - the actual send still happens
		 *	outside Nino (own mail client for a small list, a project's
		 *	ESP for a larger one), this only gets the addresses there. The
		 *	export is a separate thing: it writes the rows the filter lets
		 *	through, pending ones included, with their status
		 *
		 *	@param		{Array}			entries				[ { email, status, date, ip }, ... ]
		 *	@param		{string}		unsubscribeUrl		Where a subscriber asks for an unsubscribe link, '' for none
		 *	@param		{Function}	exportRows				Called when the export is clicked, answers the rows to write
		 *
		 *	@return		{Element}
		 */
		_renderBcc : function( entries, unsubscribeUrl, exportRows ) {

			const wrap = dc.createElement('div');
			wrap.id = 'newsletter-bcc';
			wrap.className = 'nino-admin-card';

			const label = dc.createElement('label');
			label.htmlFor = 'newsletter-bcc-field';
			label.textContent = Nino.content.getText('/_admin/newsletter/label/bcc');
			wrap.appendChild( label );

			const field = dc.createElement('textarea');
			field.id = 'newsletter-bcc-field';
			field.readOnly = true;
			field.value = Nino.admin.newsletter._bccLine( entries );
			field.addEventListener( 'click', function() { this.select() } );
			wrap.appendChild( field );

			// A BCC mail has no personal unsubscribe link to carry, so this one
			// is the way out it can: the page asks for the address and mails
			// the personal link
			if( typeof unsubscribeUrl === 'string' && unsubscribeUrl !== '' ) {
				const hint = dc.createElement('p');
				hint.id = 'newsletter-bcc-hint';
				hint.className = 'nino-admin-hint';
				hint.textContent = Nino.content.getText('/_admin/newsletter/hint/unsubscribe').replace( '%s', unsubscribeUrl );
				wrap.appendChild( hint );
			}

			const actions = dc.createElement('div');
			actions.id = 'newsletter-bcc-actions';
			actions.className = 'nino-admin-actionbar';

			const copyBtn = dc.createElement('button');
			copyBtn.type = 'button';
			copyBtn.textContent = Nino.content.getText('/_admin/newsletter/label/copy');
			copyBtn.addEventListener( 'click', function() {
				field.select();
				const write = wn.navigator.clipboard && typeof wn.navigator.clipboard.writeText === 'function'
					? wn.navigator.clipboard.writeText( field.value )
					: new Promise( function( resolve, reject ) {
						try {
							dc.execCommand('copy') === true ? resolve() : reject();
						} catch(e) { reject(e) }
					} );
				write.then( function() {
					copied.textContent = Nino.content.getText('/_admin/newsletter/label/copied');
					copied.classList.remove('text-import-error');
					copied.classList.remove('admin-hidden');
					setTimeout( function() { copied.classList.add('admin-hidden') }, 2000 );
				} ).catch( function() {
					copied.textContent = Nino.content.getText('/_admin/newsletter/error/copy');
					copied.classList.add('text-import-error');
					copied.classList.remove('admin-hidden');
				} );
			} );
			actions.appendChild( copyBtn );

			const copied = dc.createElement('span');
			copied.id = 'newsletter-bcc-copied';
			copied.className = 'admin-hidden';
			copied.setAttribute( 'aria-live', 'polite' );
			copied.textContent = Nino.content.getText('/_admin/newsletter/label/copied');
			actions.appendChild( copied );

			const exportBtn = dc.createElement('button');
			exportBtn.type = 'button';
			exportBtn.id = 'newsletter-export';
			exportBtn.textContent = Nino.content.getText('/_admin/newsletter/label/export');
			exportBtn.addEventListener( 'click', function() {
				Nino.admin.exportCsv( Nino.content.getText('/_admin/newsletter/label/filename'), exportRows() );
			} );
			actions.appendChild( exportBtn );

			wrap.appendChild( actions );

			return wrap;
		},
	};

})(window, document, document.documentElement, document.body);
