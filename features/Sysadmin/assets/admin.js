/**
 *	Nino features
 *	Modules\Sysadmin		The feature's /_admin panel: who is told, the clocks of the two mails, what the
 *											report finds right now, and one button that sends a test mail to the saved
 *											recipients. The recipients, the distance of two problem mails and the rhythm of
 *											the sign of life are the Settings tab's - the kernel adds it beside this
 *											screen. See Modules\Sysadmin\Admin beside this file.
 */

( function(wn) {

	wn.Nino.admin = wn.Nino.admin || {};

	const word = function( name ) { return Nino.content.getText( '/_admin/sysadmin/'+ name ) };

	// The rank of a finding as a badge: the word says it, the colour only agrees
	const RANKS = { 1 : 'notice', 2 : 'warning', 3 : 'error' };
	const STATES = { notice : 'muted', warning : 'warn', error : 'error' };

	Nino.admin.sysadmin = Nino.adminUi.panel( {
		uri		: 'sysadmin',
		mount	: 'sysadmin-list',
		action	: 'status',
		show	: 'always',
		render	: function( data, panel ) {

			const mount = panel.mount();
			mount.innerHTML = '';

			mount.appendChild( Nino.adminUi.hint( word('hint/intro') ) );

			if( data.recipients.length === 0 )
				mount.appendChild( Nino.adminUi.notice( word('hint/none'), { href : '#sysadmin-settings', label : word('label/settings') } ) );

			mount.appendChild( Nino.admin.sysadmin._facts( data ) );
			mount.appendChild( Nino.admin.sysadmin._findings( data.findings ) );
			mount.appendChild( Nino.admin.sysadmin._test( data ) );
		},
	} );

	Object.assign( Nino.admin.sysadmin, {

		/**
		 *	The facts of the two clocks, as a list of names and values
		 *
		 *	@param		{Object}	data				The answer to sysadmin/status
		 *
		 *	@return		{Element}
		 */
		_facts : function( data ) {

			const never = word('label/never');
			const rows = [
				[ word('label/recipients'), data.recipients.length === 0 ? word('label/nobody') : data.recipients.join( ', ' ) ],
				[ word('label/gap'), data.gap === 1 ? word('label/hour') : Nino.adminUi.format( word('label/hours'), String( data.gap ) ) ],
				[ word('label/last-problem'), data.lastProblem === null ? never : data.lastProblem ],
				[ word('label/beat'), word( 'beat/'+ data.beat ) ],
				[ word('label/last-beat'), data.lastBeat === null ? never : data.lastBeat ],
				[ word('label/next-beat'), data.nextBeat === null ? '-' : data.nextBeat ],
			];

			if( data.aborted > 0 )
				rows.push( [ word('label/aborted'), String( data.aborted ) ] );

			if( data.failed !== null )
				rows.push( [ word('label/failed'), Nino.adminUi.format( word('label/failed-value'), data.failed.at, String( data.failed.count ) ) ] );

			const list = Nino.adminUi.el( 'dl', 'sysadmin-facts' );

			rows.forEach( function( row ) {
				list.appendChild( Nino.adminUi.el( 'dt', '', row[0] ) );
				list.appendChild( Nino.adminUi.el( 'dd', '', row[1] ) );
			} );

			return list;
		},

		/**
		 *	What the report finds right now: a badge with the rank and the sentence, or that there is nothing
		 *
		 *	@param		{Array}		findings		[ { rank, text, event } ]
		 *
		 *	@return		{Element}
		 */
		_findings : function( findings ) {

			const wrap = Nino.adminUi.el( 'div', 'sysadmin-findings' );

			wrap.appendChild( Nino.adminUi.el( 'p', 'nino-admin-eyebrow', word('label/findings') ) );

			if( findings.length === 0 ) {
				wrap.appendChild( Nino.adminUi.emptyState( word('hint/findings-empty') ) );
				return wrap;
			}

			const items = Nino.adminUi.el( 'ul', 'nino-admin-list nino-admin-list-dense' );

			findings.forEach( function( finding ) {

				const item = Nino.adminUi.el( 'li' );
				const badge = Nino.adminUi.el( 'span', 'nino-admin-badge', word( 'rank/'+ ( RANKS[ finding.rank ] || 'notice' ) ) );

				badge.dataset.state = STATES[ RANKS[ finding.rank ] ] || 'muted';
				item.appendChild( badge );
				item.appendChild( document.createTextNode( ' '+ finding.text ) );
				items.appendChild( item );
			} );

			wrap.appendChild( items );

			return wrap;
		},

		/**
		 *	The one action of the screen, in its one bar: the test mail
		 *
		 *	@param		{Object}	data
		 *
		 *	@return		{Element}
		 */
		_test : function( data ) {

			const message = Nino.adminUi.el( 'span' );
			message.id = 'sysadmin-msg';

			const line = Nino.adminUi.status( message, { saving : word('msg/sending') } );
			const send = Nino.adminUi.button( word('label/send'), null, { type : 'submit', id : 'sysadmin-send', className : 'nino-admin-btn-primary', disabled : data.recipients.length === 0 } );
			const bar = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );

			bar.appendChild( line.element );
			bar.appendChild( send );

			const form = Nino.adminUi.el( 'form' );
			form.appendChild( bar );

			form.addEventListener( 'submit', function( event ) {

				event.preventDefault();

				if( send.disabled === true )
					return;

				Nino.adminUi.confirm( 'routine', {
					message		: Nino.adminUi.format( word('confirm/test'), data.recipients.join( ', ' ) ),
					yes				: word('label/send'),
					no				: Nino.content.getText('/_admin/common/label/cancel'),
					opener		: send,
					onChoose	: function( ok ) {

						if( ok === true )
							Nino.admin.sysadmin._send( send, line );
					},
				} );
			} );

			return form;
		},

		/**
		 *	Send the test mail and say how it went. The test leaves no mark on the clocks but its own time, so
		 *	nothing on the screen needs to be asked for again
		 *
		 *	@param		{Element}	send
		 *	@param		{Object}	line				The live line, see Nino.adminUi.status()
		 *
		 *	@return		{void}
		 */
		_send : function( send, line ) {

			send.disabled = true;
			line.saving();

			Nino.admin.sysadmin.call( 'test', {}, function( status, response ) {

				send.disabled = false;

				if( status !== 200 ) {
					line.error( status, response, '/_admin/sysadmin/error/send' );
					return;
				}

				line.idle( Nino.adminUi.format( word('msg/sent'), String( response.sent ) ) );
			} );
		},
	} );

} )(window);
