/**
 *	Nino										A compact filesystembased php framework
 *	Modules									Optional modules
 *	Nino										Framework
 *	admin.js								The Newsletter feature's /_admin panel: the subscribers as a table with a
 *													search, a status as a badge, a copyable BCC line and the CSV export in the
 *													bar of the list, and the removal of an address in its row - see
 *													Newsletter\Admin in Admin/Admin.php. The send always happens elsewhere (own
 *													mail client, or a project-specific ESP), never from Nino itself. A subscriber
 *													takes themselves off the list through the unsubscribe link the feature's own
 *													GET /.newsletter route answers (see Modules\Newsletter).
 *													Built from the blocks of the workbench (docs/admin-ui.md): panel(), table(),
 *													selectField(), listActions(), button(), confirm(), say(), emptyState().
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( name ) { return t( '/_admin/newsletter/'+ name ) };
	const el = Nino.adminUi.el;

	// What the panel keeps while its list is drawn again: the status on show and what was typed into the search
	const S = { filter : 'all', query : '', on : { subscribed : false, pending : false } };

	// Both chips on or both off is every address, one on is that status
	const filterOf = function( on ) {
		return on.subscribed === on.pending ? 'all' : ( on.subscribed === true ? 'subscribed' : 'pending' );
	};

	/**
	 *	The status of an entry: 'pending' until its confirm link was pressed, 'subscribed' otherwise - the reading the
	 *	server applies as well, so a row that has none is a subscriber
	 *
	 *	@param		{Object}	entry
	 *
	 *	@return		{string}
	 */
	const statusOf = function( entry ) {
		return entry.status === 'pending' ? 'pending' : 'subscribed';
	};

	/**
	 *	The rows a status filter lets through - all of them for 'all' (or anything that is not one of the two
	 *	statuses). Pure: the table, the BCC line and the CSV export all ask it, so what is on screen is what is exported
	 *
	 *	@param		{Array}		entries			[ { email, status, date, ip }, ... ]
	 *	@param		{string}	filter			'all', 'subscribed' or 'pending'
	 *
	 *	@return		{Array}
	 */
	const rowsOf = function( entries, filter ) {

		if( filter !== 'subscribed' && filter !== 'pending' )
			return entries;

		return entries.filter( function( entry ) { return statusOf( entry ) === filter } );
	};

	/**
	 *	The BCC line: the confirmed addresses, comma-separated - a standard BCC field's own separator. A pending
	 *	address has not agreed to anything yet and is not mailed
	 *
	 *	@param		{Array}		entries
	 *
	 *	@return		{string}
	 */
	const bccLine = function( entries ) {
		// An address with a comma or a quotation mark - a list of Nino 1 may hold one - would be read as two in a line that separates by commas
		return rowsOf( entries, 'subscribed' ).map( function( entry ) { return entry.email ?? '' } ).filter( function( email ) { return email !== '' && /[",]/.test( email ) === false } ).join(', ');
	};

	/**
	 *	What a deletion leaves the focus on: the next row of the table, else the one before, else the sentence that
	 *	says there is none
	 *
	 *	@param		{Element}	mount
	 *	@param		{Array}		keys				The addresses in the order they stood
	 *	@param		{string}	gone				The one that is gone
	 *
	 *	@return		void
	 */
	const focusAround = function( mount, keys, gone ) {

		const at = keys.indexOf( gone );
		const around = keys.filter( function( key ) { return key !== gone } );
		const key = around[ Math.min( at, around.length - 1 ) ];
		let target = key === undefined ? mount.querySelector('.nino-admin-empty') : mount.querySelector( '[data-row="'+ CSS.escape( key ) +'"] button, [data-row="'+ CSS.escape( key ) +'"]' );

		// The table draws its first page again after a reload, so a row of a later page is not there: the focus goes to the search of the
		// list, and where there is none to the list itself - never to the page. table() has no option that holds the page
		if( target === null )
			target = mount.querySelector('input[type=search]') ?? mount;

		if( target.hasAttribute('tabindex') === false && target.tagName !== 'BUTTON' )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	/**
	 *	Delete one subscriber, after the workbench's question, then ask for the list again - the simplest way to keep
	 *	the filter, the counts and the BCC line in step - and say so as a toast
	 *
	 *	@param		{Array}			entries		The list as it stands
	 *	@param		{string}		email
	 *	@param		{Element}		opener		The button that asked, which gets the focus back where the question is answered with no
	 *
	 *	@return		void
	 */
	const remove = function( entries, email, opener ) {

		const keys = rowsOf( entries, S.filter ).map( function( entry ) { return entry.email } );

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: Nino.adminUi.format( w('confirm/delete'), email ),
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.newsletter.call( 'delete', { email : email }, function( status, response ) {

					if( status !== 200 )
						return Nino.admin.newsletter.fail( Nino.admin.newsletter.mount(), status, response, '/_admin/newsletter/error/delete' );

					Nino.admin.newsletter.reload( function() {
						focusAround( Nino.admin.newsletter.mount(), keys, email );
						Nino.adminUi.say( t('/_admin/common/msg/deleted') );
					} );
				} );
			},
		} );
	};

	const draw = function( response, panel ) {

		const entries = Array.isArray( response.entries ) === true ? response.entries : [];
		const counts = response.counts || { subscribed : 0, pending : 0 };
		const mount = panel.mount();

		mount.innerHTML = '';

		if( entries.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( w('empty') ) );
			return;
		}

		S.filter = filterOf( S.on );

		const statusLabels = {
			subscribed	: w('status/subscribed'),
			pending			: w('status/pending'),
		};

		// The explanation of the list stands behind the (?) of its title: where a subscriber without a link asks for one
		const title = Nino.adminUi.sectionTitle( w('label/subscribers') );

		mount.appendChild( title );

		// A BCC mail has no personal unsubscribe link to carry, so this is the way out it can: the page asks for the address and mails the personal link
		if( typeof response.unsubscribeUrl === 'string' && response.unsubscribeUrl !== '' )
			Nino.adminUi.sectionHelp( title, mount, Nino.adminUi.format( w('hint/unsubscribe'), response.unsubscribeUrl ) );

		// What narrows the list stands over it, in the bar of the filters: the status as two chips, with how many there are of each
		const filterMount = el( 'div' );

		mount.appendChild( filterMount );

		const tableMount = el( 'div' );

		mount.appendChild( tableMount );

		// Subscribers are records: the shared table gives the list search, sorting and paging. A row has no action that opens it,
		// so no row is a target of its own - the buttons in it are
		const list = Nino.adminUi.table( {
			mount		: tableMount,
			rowKey	: 'email',
			search	: true,
			query		: S.query,
			onQuery	: function( value ) { S.query = value },
			rows		: rowsOf( entries, S.filter ),
			labels	: { search : w('label/search'), empty : w('emptyfilter'), noMatch : w('nomatch') },
			columns	: [
				// The address is text: a link in a cell would be a target of 14 px, which the guide does not take, and mailing one subscriber is no job of this panel
				{ key : 'email', label : w('label/mail'), type : 'string' },
				{ key : 'status', label : w('label/status'), type : 'string', render : function( value ) {
					const state = value === 'pending' ? 'pending' : 'subscribed';
					const badge = el( 'span', 'nino-admin-badge', ( state === 'pending' ? '● ' : '✓ ' )+ statusLabels[state] );
					badge.dataset.state = state === 'pending' ? 'warn' : 'ok';
					return badge;
				} },
				{ key : 'date', label : w('label/date'), type : 'datetime' },
			],
			actions	: function( row ) {

				const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { remove( entries, row.email, drop ) }, { small : true, kind : 'danger' } );

				return [ drop ];
			},
		} );

		const chip = function( status, text ) {
			return Nino.adminUi.chip( text, { pressed : S.on[status], onToggle : function( pressed ) {
				S.on[status] = pressed;
				S.filter = filterOf( S.on );
				list.setRows( rowsOf( entries, S.filter ) );
			} } );
		};

		filterMount.appendChild( Nino.adminUi.filterBar( { items : [ chip( 'subscribed', statusLabels.subscribed+ ' ('+ counts.subscribed+ ')' ), chip( 'pending', statusLabels.pending+ ' ('+ counts.pending+ ')' ) ] } ).el );

		// What the bar of the list does with the list: the confirmed addresses as a line to paste into a BCC field, and the file
		const buttons = [];
		const failed = el( 'div' );

		failed.setAttribute( 'role', 'alert' );

		if( counts.subscribed > 0 )
			buttons.push( Nino.adminUi.button( w('label/copy-bcc'), function() {

				Nino.clipboard( bccLine( entries ) ).then( function( ok ) {

					failed.innerHTML = '';

					if( ok === false ) {
						// A sentence of the workbench's list of codes, so that no status stands before it
						Nino.adminUi.showError( failed, 0, { code : 'newsletter_copy' }, '' );
						return;
					}

					Nino.adminUi.say( Nino.adminUi.format( w( counts.subscribed === 1 ? 'msg/bcc-one' : 'msg/bcc-many' ), counts.subscribed ) );
				} );
			}, { kind : 'secondary', id : 'newsletter-bcc' } ) );

		// The file is made in the browser from what the guarded list answered - which holds no credential - and what is on show is what is written
		buttons.push( Nino.adminUi.button( w('label/export'), function() {

			Nino.admin.exportCsv( w('label/filename'), rowsOf( entries, S.filter ).map( function( entry ) {
				return { email : entry.email, status : statusOf( entry ), date : entry.date ?? '', ip : entry.ip ?? '' };
			} ) );
		}, { kind : 'secondary', id : 'newsletter-export' } ) );

		mount.appendChild( failed );

		mount.appendChild( Nino.adminUi.listActions( buttons ) );
	};

	// "Always re-fetch": there's no drill-down state to preserve, and asking again on every showing keeps the list current
	// with whatever arrived since it was last open
	Nino.admin.newsletter = Nino.adminUi.panel( {
		uri			: 'newsletter',
		mount		: 'newsletter-list',
		show		: 'always',
		render	: draw,
	} );

} )(window);
