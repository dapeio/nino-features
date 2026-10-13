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

	// "Always re-fetch": there's no drill-down state to preserve, and asking again
	// on every showing keeps the list current with whatever arrived since it was
	// last open. A showing and a delete's reload() both draw it on the status
	// filter that is on screen
	Nino.admin.newsletter = Nino.adminUi.panel( {
		uri		: 'newsletter',
		mount	: 'newsletter-list',
		show	: 'always',
		render	: function( data ) { Nino.admin.newsletter._renderList( data, Nino.admin.newsletter._filter ) },
	} );

	Object.assign( Nino.admin.newsletter, {

		// The status filter on screen - the list takes none, the table and the
		// export do
		_filter : 'all',

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
			const wrap 		= Nino.admin.newsletter.mount();
			wrap.innerHTML = '';

			if( entries.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( Nino.content.getText('/_admin/newsletter/empty') ) );
				return;
			}

			// The confirmed addresses are the subscribers; what is still waiting
			// for its link to be visited is named beside them, not counted in
			const summary = Nino.adminUi.el( 'p', '', counts.subscribed+ ' '+ Nino.content.getText( counts.subscribed === 1 ? '/_admin/newsletter/label/subscriber' : '/_admin/newsletter/label/subscribers' )
				+ ( counts.pending > 0 ? ' · '+ counts.pending+ ' '+ Nino.content.getText('/_admin/newsletter/label/pending') : '' ) );
			summary.id = 'newsletter-summary';
			wrap.appendChild( summary );

			// What the filter says now - the table shows it, the export writes it
			// and a delete draws the list again on it
			Nino.admin.newsletter._filter = filter === 'subscribed' || filter === 'pending' ? filter : 'all';

			wrap.appendChild( Nino.admin.newsletter._renderBcc( entries, response.unsubscribeUrl, function() { return Nino.admin.newsletter._rows( entries, Nino.admin.newsletter._filter ) } ) );

			const statusLabels = {
				subscribed 	: Nino.content.getText('/_admin/newsletter/status/subscribed'),
				pending 		: Nino.content.getText('/_admin/newsletter/status/pending'),
			};

			const filterMount = Nino.adminUi.el( 'div' );
			filterMount.id = 'newsletter-filter';
			wrap.appendChild( filterMount );

			const table = Nino.adminUi.el( 'div' );
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
				rows 		: Nino.admin.newsletter._rows( entries, Nino.admin.newsletter._filter ),
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
							return Nino.adminUi.button( Nino.content.getText('/_admin/newsletter/label/delete'), function() { Nino.admin.newsletter._delete( value ) }, { className : 'nino-admin-btn-danger nino-admin-btn--small' } );
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
				value 		: Nino.admin.newsletter._filter,
				options 	: [
					{ value : 'all', 				label : Nino.content.getText('/_admin/newsletter/filter/all') },
					{ value : 'subscribed', label : statusLabels.subscribed },
					{ value : 'pending', 		label : statusLabels.pending },
				],
				onChange 	: function( value ) {
					Nino.admin.newsletter._filter = value;
					list.setRows( Nino.admin.newsletter._rows( entries, value ) );
				},
			} ) );
		},

		/**
		 *	Delete one subscriber, after a confirm prompt, then ask for the list
		 *	again - simplest way to keep the BCC field/summary count in sync,
		 *	same "always re-fetch" shape the rest of this panel uses - and it is
		 *	drawn on the status filter that was chosen, which reload() leaves
		 *	alone
		 *
		 *	@param		{string}	email
		 *
		 *	@return		void
		 */
		_delete : function( email ) {

			if( wn.confirm( Nino.adminUi.format( Nino.content.getText('/_admin/newsletter/confirm/delete'), email ) ) === false )
				return;

			Nino.admin.newsletter.call( 'delete', { email : email }, function( status, response ) {

				if( status !== 200 )
					return Nino.admin.newsletter.fail( Nino.admin.newsletter.mount(), status, response );

				Nino.admin.newsletter.reload();
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

			const wrap = Nino.adminUi.el( 'div', 'nino-admin-card' );
			wrap.id = 'newsletter-bcc';

			const label = dc.createElement('label');
			label.htmlFor = 'newsletter-bcc-field';
			label.textContent = Nino.content.getText('/_admin/newsletter/label/bcc');
			wrap.appendChild( label );

			// The design system has no helper for a read-only textarea
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
				const hint = Nino.adminUi.hint( Nino.adminUi.format( Nino.content.getText('/_admin/newsletter/hint/unsubscribe'), unsubscribeUrl ) );
				hint.id = 'newsletter-bcc-hint';
				wrap.appendChild( hint );
			}

			const actions = Nino.adminUi.el( 'div', 'nino-admin-actions' );
			actions.id = 'newsletter-bcc-actions';

			const copied = Nino.adminUi.el( 'span', 'admin-hidden', Nino.content.getText('/_admin/newsletter/label/copied') );
			copied.id = 'newsletter-bcc-copied';
			copied.setAttribute( 'aria-live', 'polite' );

			actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/newsletter/label/copy'), function() {

				field.select();

				Nino.clipboard( field.value ).then( function( ok ) {

					copied.classList.remove('admin-hidden');

					if( ok === false ) {
						copied.textContent = Nino.content.getText('/_admin/newsletter/error/copy');
						copied.classList.add('nino-admin-error');
						return;
					}

					copied.textContent = Nino.content.getText('/_admin/newsletter/label/copied');
					copied.classList.remove('nino-admin-error');
					setTimeout( function() { copied.classList.add('admin-hidden') }, 2000 );
				} );
			} ) );
			actions.appendChild( copied );

			actions.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/newsletter/label/export'), function() {
				Nino.admin.exportCsv( Nino.content.getText('/_admin/newsletter/label/filename'), exportRows() );
			}, { id : 'newsletter-export' } ) );

			wrap.appendChild( actions );

			return wrap;
		},
	} );

})(window, document, document.documentElement, document.body);
