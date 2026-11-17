/**
 *	Nino features
 *	admin.js		The panel of the Sysadmin feature: who is told, the clocks of the two mails and what
 *							the report finds right now as dense lists, and the test mail as an action of its own
 *							whose answer is a toast. The recipients, the distance of two problem mails and the
 *							rhythm of the sign of life are the Settings tab's - the kernel adds it beside this
 *							screen. See Modules\Sysadmin\Admin beside this file.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const word = function( name ) { return Nino.content.getText( '/_admin/sysadmin/'+ name ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// The rank of a finding as a badge: the word says it, the colour only agrees
	const RANKS = { 1 : 'notice', 2 : 'warning', 3 : 'error' };
	const STATES = { notice : 'muted', warning : 'warn', error : 'error' };

	// The facts of the two clocks as a dense list, each a name and its value
	const facts = function( data ) {

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

		const section = el( 'section', 'sysadmin-facts' );
		const list = el( 'ul', 'nino-admin-list nino-admin-list-dense' );

		const title = Nino.adminUi.sectionTitle( word('label/state') );

		section.appendChild( title );
		// What the feature does is behind the (?) of the first section, not a paragraph over the screen
		Nino.adminUi.sectionHelp( title, section, word('hint/intro') );

		rows.forEach( function( row ) {

			const item = el( 'li', 'sysadmin-fact' );

			item.appendChild( el( 'span', 'sysadmin-fact-name', row[0] ) );
			item.appendChild( el( 'span', 'sysadmin-fact-value', row[1] ) );
			list.appendChild( item );
		} );

		section.appendChild( list );

		return section;
	};

	// What the report finds right now: the rank as a badge and the sentence, or that there is nothing
	const findings = function( list ) {

		const section = el( 'section', 'sysadmin-findings' );

		section.appendChild( Nino.adminUi.sectionTitle( word('label/findings') ) );

		if( list.length === 0 ) {
			section.appendChild( Nino.adminUi.emptyState( word('hint/findings-empty') ) );
			return section;
		}

		const items = el( 'ul', 'nino-admin-list nino-admin-list-dense' );

		list.forEach( function( finding ) {

			const item = el( 'li', 'sysadmin-finding' );
			const rank = RANKS[ finding.rank ] || 'notice';
			const badge = el( 'span', 'nino-admin-badge', ( rank === 'notice' ? '● ' : '▲ ' )+ word( 'rank/'+ rank ) );

			badge.dataset.state = STATES[rank] || 'muted';
			item.appendChild( badge );
			item.appendChild( el( 'span', 'sysadmin-finding-text', finding.text ) );
			items.appendChild( item );
		} );

		section.appendChild( items );

		return section;
	};

	// The one action of the screen, in its one bar: the test mail, asked once and answered as a toast, a failure in the line of the bar
	const test = function( data, panel ) {

		const none = data.recipients.length === 0;
		const send = Nino.adminUi.button( word('label/send'), null, { kind : 'primary', id : 'sysadmin-send', disabled : none } );
		const bar = Nino.adminUi.actionBar( { status : { fresh : true }, primary : send } );

		// A locked button says why: the reason is its title, and the notice over the screen has the way to the settings
		if( none === true ) {
			send.title = word('hint/none');
			bar.el.insertBefore( Nino.adminUi.fieldHelp( word('hint/none'), { field : send, name : word('label/send') } ), send );
		}

		send.addEventListener( 'click', function() {

			Nino.adminUi.confirm( 'routine', {
				title		: word('label/send'),
				message		: Nino.adminUi.format( word('confirm/test'), data.recipients.join( ', ' ) ),
				yes			: word('label/send'),
				no			: Nino.content.getText('/_admin/common/label/cancel'),
				opener		: send,
				onChoose	: function( ok ) {

					if( ok !== true )
						return;

					send.disabled = true;
					bar.line.idle( word('msg/sending') );

					panel.call( 'test', {}, function( status, response ) {

						send.disabled = false;

						if( status !== 200 ) {
							bar.line.error( status, response, '/_admin/sysadmin/error/send' );
							return;
						}

						bar.line.idle('');
						Nino.adminUi.say( Nino.adminUi.format( word('msg/sent'), String( response.sent ) ) );
					} );
				},
			} );
		} );

		return bar.el;
	};

	Nino.admin.sysadmin = Nino.adminUi.panel( {
		uri		: 'sysadmin',
		mount	: 'sysadmin-list',
		action	: 'status',
		show	: 'always',
		render	: function( data, panel ) {

			const mount = panel.mount();

			mount.innerHTML = '';

			if( data.recipients.length === 0 )
				mount.appendChild( Nino.adminUi.notice( word('hint/none'), { href : '#sysadmin-settings', label : word('label/settings') } ) );

			mount.appendChild( facts( data ) );
			mount.appendChild( findings( data.findings ) );
			mount.appendChild( test( data, panel ) );
		},
	} );

} )( window, document );
