/**
 *	Nino features
 *	admin.js		The Greeting feature in the workbench, two things in one script:
 *							the card the Dashboard opens with, and the panel that switches its
 *							three lines.
 *
 *							The card is an area at the head of the Dashboard
 *							(Nino.admin.dashboard.head, answered by the callback of
 *							\Nino\Modules\Greeting::head()): a line for the hour of the
 *							browser's clock, a sentence picked at random from those that fit
 *							the hour, the weekday and the day of the year, and a line of facts
 *							the server delivers. The server sends the facts and the sentences;
 *							the hour, the weekday and the dice are the browser's, since the
 *							workbench does not sit in the time zone of the server. now() and
 *							random() are the two things a test replaces.
 *
 *							The panel is three switches, saved in one request.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// --- the card ------------------------------------------------------------

	const greeting = {

		// The last sentence shown, in the session of the browser tab - nothing that outlives it
		memory : 'nino-admin-greeting',

		/**
		 *	@return		{Date}										The clock of the browser
		 */
		now : function() {
			return new Date();
		},

		/**
		 *	@return		{number}									A number from 0 up to, not including, 1
		 */
		random : function() {
			return Math.random();
		},

		/**
		 *	The time of day an hour belongs to: early 5-7, morning 8-10, noon
		 *	11-13, afternoon 14-16, evening 17-21, night 22-4
		 *
		 *	@param		{number}	hour							0-23
		 *
		 *	@return		{string}
		 */
		phase : function( hour ) {

			if( hour >= 5 && hour <= 7 )
				return 'early';
			if( hour >= 8 && hour <= 10 )
				return 'morning';
			if( hour >= 11 && hour <= 13 )
				return 'noon';
			if( hour >= 14 && hour <= 16 )
				return 'afternoon';
			if( hour >= 17 && hour <= 21 )
				return 'evening';

			return 'night';
		},

		/**
		 *	The day of the year that is an occasion: 24-26 December, and
		 *	31 December with 1 January
		 *
		 *	@param		{Date}		date
		 *
		 *	@return		{string}									'christmas', 'newyear' or 'none'
		 */
		occasion : function( date ) {

			const month = date.getMonth();
			const day = date.getDate();

			if( month === 11 && day >= 24 && day <= 26 )
				return 'christmas';
			if( ( month === 11 && day === 31 ) || ( month === 0 && day === 1 ) )
				return 'newyear';

			return 'none';
		},

		/**
		 *	The weekday pool a day is in: Monday, Friday, the weekend - and
		 *	'none' for the other days, which have no pool of their own
		 *
		 *	@param		{Date}		date
		 *
		 *	@return		{string}
		 */
		day : function( date ) {

			const day = date.getDay();

			if( day === 1 )
				return 'monday';
			if( day === 5 )
				return 'friday';

			return day === 0 || day === 6 ? 'weekend' : 'none';
		},

		/**
		 *	The sentences that fit a moment: on an occasion only the ones of
		 *	the occasion, otherwise those of the hour's time of day or of any,
		 *	and of the weekday's pool or of any. Without a name, only the ones
		 *	that need none (no %s)
		 *
		 *	@param		{Array}		sentences					[ { text, phase, day, occasion }, ... ]
		 *	@param		{Date}		date
		 *	@param		{string}	name
		 *
		 *	@return		{Array}
		 */
		fitting : function( sentences, date, name ) {

			const phase = greeting.phase( date.getHours() );
			const day = greeting.day( date );
			const occasion = greeting.occasion( date );

			return ( Array.isArray( sentences ) ? sentences : [] ).filter( function( sentence ) {

				if( sentence === null || typeof sentence !== 'object' || typeof sentence.text !== 'string' || sentence.text === '' )
					return false;

				if( name === '' && sentence.text.indexOf('%s') !== -1 )
					return false;

				if( occasion !== 'none' )
					return sentence.occasion === occasion;

				return ( sentence.occasion === undefined || sentence.occasion === 'none' )
					&& ( sentence.phase === undefined || sentence.phase === 'any' || sentence.phase === phase )
					&& ( sentence.day === undefined || sentence.day === 'any' || sentence.day === day );
			} );
		},

		/**
		 *	One of the sentences, by the dice - never the one shown the last
		 *	time, as long as there is another
		 *
		 *	@param		{Array}		fitting						The sentences that fit
		 *
		 *	@return		{string}									Its text, '' where none fits
		 */
		pick : function( fitting ) {

			let last = '';
			try {
				last = wn.sessionStorage.getItem( greeting.memory ) || '';
			} catch( error ) {
				last = '';
			}

			const others = fitting.filter( function( sentence ) { return sentence.text !== last } );
			const from = others.length > 0 ? others : fitting;

			if( from.length === 0 )
				return '';

			const text = from[ Math.min( from.length - 1, Math.max( 0, Math.floor( greeting.random() * from.length ) ) ) ].text;

			try {
				wn.sessionStorage.setItem( greeting.memory, text );
			} catch( error ) {
				// A browser that keeps nothing: the next one may be the same
			}

			return text;
		},

		/**
		 *	The days between two calendar days of the browser's time zone
		 *
		 *	@param		{number}	previous					A timestamp in seconds
		 *	@param		{Date}		now
		 *
		 *	@return		{number}									0 for today, 1 for yesterday
		 */
		days : function( previous, now ) {

			const then = new Date( previous * 1000 );
			const was = Date.UTC( then.getFullYear(), then.getMonth(), then.getDate() );
			const is = Date.UTC( now.getFullYear(), now.getMonth(), now.getDate() );

			return Math.max( 0, Math.round( ( is - was ) / 86400000 ) );
		},

		/**
		 *	The facts line: up to two of the last visit, the log since it and
		 *	the age of the last backup, in this order. Of three, the visit
		 *	and the backup stay - an old backup is what somebody acts on
		 *
		 *	@param		{Object}	card							The server's answer
		 *	@param		{Date}		now
		 *
		 *	@return		{string[]}
		 */
		facts : function( card, now ) {

			const format = Nino.adminUi.format;
			let visit = null;
			let activity = null;
			let backup = null;

			if( card.previousLogin === 0 )
				visit = Nino.content.getText('/_admin/greeting/fact/first');
			else if( Number.isInteger( card.previousLogin ) === true && card.previousLogin > 0 ) {

				const days = greeting.days( card.previousLogin, now );

				if( days === 0 )
					visit = Nino.content.getText('/_admin/greeting/fact/today');
				else if( days === 1 )
					visit = Nino.content.getText('/_admin/greeting/fact/yesterday');
				else
					visit = format( Nino.content.getText('/_admin/greeting/fact/days'), days );
			}

			if( Number.isInteger( card.activitySince ) === true && card.activitySince >= 1 )
				activity = card.activitySince === 1
					? Nino.content.getText('/_admin/greeting/fact/activity-one')
					: format( Nino.content.getText('/_admin/greeting/fact/activity'), card.activitySince );

			if( Number.isInteger( card.backupDays ) === true && card.backupDays >= 8 )
				backup = format( Nino.content.getText('/_admin/greeting/fact/backup'), card.backupDays );

			if( visit !== null && activity !== null && backup !== null )
				activity = null;

			return [ visit, activity, backup ].filter( function( fact ) { return fact !== null && fact !== '' } );
		},

		/**
		 *	The card: one paragraph for each line that is on and has something
		 *	to say. Everything the server sent, the name above all, is set as
		 *	text
		 *
		 *	@param		{Object|undefined}	card			The server's answer, undefined for none
		 *
		 *	@return		{Element|null}								Null where there is nothing to draw
		 */
		render : function( card ) {

			if( card === null || typeof card !== 'object' )
				return null;

			const show = card.show !== null && typeof card.show === 'object' ? card.show : {};
			const name = typeof card.name === 'string' ? card.name : '';
			const now = greeting.now();
			const lines = [];

			if( show.hello !== false ) {

				const hello = Nino.content.getText('/_admin/greeting/hello/'+ greeting.phase( now.getHours() ) );

				if( hello !== '' )
					lines.push( [ 'hello', hello+ ( name !== '' ? ', '+ name : '' )+ '.' ] );
			}

			if( show.sentence !== false ) {

				const text = greeting.pick( greeting.fitting( card.sentences, now, name ) );

				if( text !== '' )
					lines.push( [ 'sentence', Nino.adminUi.format( text, name ) ] );
			}

			if( show.facts !== false ) {

				const facts = greeting.facts( card, now );

				if( facts.length > 0 )
					lines.push( [ 'facts', facts.join(' · ') ] );
			}

			if( lines.length === 0 )
				return null;

			const element = dc.createElement('div');
			element.id = 'admin-dashboard-greeting';
			element.className = 'nino-admin-card nino-admin-greeting';

			lines.forEach( function( line ) {
				const p = dc.createElement('p');
				p.className = 'nino-admin-greeting-'+ line[0];
				p.textContent = line[1];
				element.appendChild( p );
			} );

			return element;
		},
	};

	// A Nino whose Dashboard has no head draws no card: the panel says so
	const hasHead = function() {
		return typeof Nino.admin.dashboard === 'object' && Nino.admin.dashboard !== null
			&& typeof Nino.admin.dashboard.head === 'object' && Nino.admin.dashboard.head !== null && typeof Nino.admin.dashboard.head.add === 'function';
	};

	if( hasHead() === true )
		Nino.admin.dashboard.head.add( 'greeting', greeting.render );

	// --- the panel -----------------------------------------------------------

	// The three lines, in the order the card has them: the key of the answer and of the word of its label
	const LINES = [ 'hello', 'sentence', 'facts' ];

	let boxes = {};
	let line = null;
	let saved = {};

	const current = function() {
		const now = {};
		LINES.forEach( function( name ) { now[name] = boxes[name].checked === true } );
		return now;
	};

	const isDirty = function() {
		return LINES.some( function( name ) { return boxes[name] !== undefined && boxes[name].checked !== saved[name] } );
	};

	const save = function( done ) {

		line.saving();

		Nino.admin.greeting.call( 'save', { lines : current() }, function( status, response ) {

			if( status !== 200 ) {
				line.error( status, response, '/_admin/greeting/error/save' );
				done( false );
				return;
			}

			LINES.forEach( function( name ) {
				boxes[name].checked = saved[name] = response.lines[name] === true;
			} );
			line.saved();
			if( typeof Nino.admin.dirty === 'object' )
				Nino.admin.dirty.refresh();
			done( true );
		} );
	};

	Nino.admin.greeting = Nino.adminUi.panel( {
		uri		: 'greeting',
		mount	: 'greeting-list',
		dirty	: { isDirty : isDirty, save : save, discard : function() { LINES.forEach( function( name ) { boxes[name].checked = saved[name] } ) } },
		render	: function( data, panel ) {

			const mount = panel.mount();
			const form = dc.createElement('form');
			const bar = Nino.adminUi.actionBar( Nino.adminUi.el( 'div' ) );

			boxes = {};
			saved = {};

			if( hasHead() === false )
				form.appendChild( Nino.adminUi.notice( Nino.content.getText('/_admin/greeting/hint/head') ) );

			const intro = dc.createElement('p');
			intro.className = 'nino-admin-hint';
			intro.textContent = Nino.content.getText('/_admin/greeting/intro');
			form.appendChild( intro );

			LINES.forEach( function( name ) {

				const field = Nino.adminUi.switchField( {
					key 			: name,
					checked 	: data.lines[name] === true,
					label 		: Nino.content.getText('/_admin/greeting/label/'+ name),
					hint 			: Nino.content.getText('/_admin/greeting/hint/'+ name),
				} );

				boxes[name] = field.querySelector('input');
				saved[name] = boxes[name].checked;
				form.appendChild( field );
			} );

			line = Nino.adminUi.status( Nino.adminUi.el( 'span' ) );
			line.bind( form, isDirty );

			form.addEventListener( 'submit', function( ev ) { ev.preventDefault(); save( function() {} ) } );

			bar.appendChild( line.element );
			bar.appendChild( Nino.adminUi.button( Nino.content.getText('/_admin/greeting/label/save'), function() { save( function() {} ) }, { className : 'nino-admin-btn-primary' } ) );
			form.appendChild( bar );

			mount.innerHTML = '';
			mount.appendChild( form );
		},
	} );

	// What a test reaches: the card's own functions
	Nino.admin.greeting.card = greeting;

} )(window, document);
