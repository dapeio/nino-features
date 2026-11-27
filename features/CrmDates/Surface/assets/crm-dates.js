/**
 *	CrmDates		The dates of the clients on the surface of the CRM, the script of the package crm-dates. It docks to the places the surface offers and to no other: an entry of the rail and the page Dates behind it - the list
 *					(#dates), the form of a new date (#dates-new, #dates-new-k<id> with the client chosen) and one date (#date-<name>) -, the tab Dates on the card of a client, the next date as a fact of the card of a project and
 *					as a tile of the overview, a key, and the line of the test phase. What is drawn is the answer of the four actions of the package (crm-dates/list, get, save, delete); a date is one of a client and has no project.
 *
 *					The times are the wall-clock times of the site, as the server writes them (Y-m-dTH:i in the zone the answer names) and as they are typed: they are never turned into the time of the browser. A day without a time
 *					is a day, written as such. Where the server says the dates cannot be read (the type of Events is missing, broken or off) the page says so, and nothing is drawn as "no dates".
 */

( function( wn ) {

	'use strict';

	const crm = wn.Nino.crm;
	const kit = crm.kit.make( 'crm-dates' );
	const KEY = 'crm-dates';
	const own = crm.state.own( KEY, { q : '', focus : false } );

	// The addresses: #dates, #dates-new, #dates-new-k<id of a client>, #date-<name of a date>
	const NEW = /^dates-new(?:-k([1-9][0-9]{0,9}))?$/;
	const ONE = /^date-([A-Za-z0-9][A-Za-z0-9_-]{0,99})$/;

	/**
	 *	What the account may do with the dates: what the server said in its answer
	 *
	 *	@param		{Object}	answer					The body of an answer of the package
	 *
	 *	@return		{Object}									insert, update, delete
	 */
	const can = function( answer ) {

		const found = answer !== undefined && answer !== null && typeof answer.can === 'object' && answer.can !== null ? answer.can : {};

		return { insert : found.insert === true, update : found.update === true, delete : found.delete === true };
	};

	/**
	 *	The day of a moment with its weekday, in the language of the page: Mo, 12.11.2026
	 *
	 *	@param		{string}	moment					Y-m-dTH:i
	 *
	 *	@return		{string}									'' for a text that is none
	 */
	const dayOf = function( moment ) {

		if( typeof moment !== 'string' || /^\d{4}-\d{2}-\d{2}/.test( moment ) === false )
			return '';

		const lang = kit.format.locale() === 'de_DE' ? 'de-DE' : 'en-US';
		const weekday = new Date( Number( moment.slice( 0, 4 ) ), Number( moment.slice( 5, 7 ) ) - 1, Number( moment.slice( 8, 10 ) ) ).toLocaleDateString( lang, { weekday : 'short' } ).replace( /\.$/, '' );

		return weekday+ ', '+ kit.dmy( moment );
	};

	/**
	 *	When a date takes place, in words: the day and the time, to the end of the same day or to the day it ends on; a day without a time says that it is one; a start that is no time says that
	 *
	 *	@param		{Object}	date						As crm-dates/list has it: start, end, all_day
	 *
	 *	@return		{string}
	 */
	const whenOf = function( date ) {

		if( date.unreadable === true )
			return kit.t( 'dates.unreadable-when' );

		const time = function( moment ) { return moment.slice( 11, 16 ) };

		// A day is a date that begins at midnight and has no end, as Events has it; every other date is shown with its start and, where it has one, its end
		if( date.all_day === true )
			return dayOf( date.start )+ ' · '+ kit.t( 'dates.all-day' );

		if( date.end === '' )
			return dayOf( date.start )+ ' '+ time( date.start );

		if( date.end.slice( 0, 10 ) === date.start.slice( 0, 10 ) )
			return dayOf( date.start )+ ' '+ time( date.start )+ '–'+ time( date.end );

		return dayOf( date.start )+ ' '+ time( date.start )+ ' – '+ dayOf( date.end )+ ' '+ time( date.end );
	};

	/**
	 *	The words of a date as plain lines: what the server answers, split into the lines of a stack
	 *
	 *	@param		{string}	text
	 *
	 *	@return		{Array}
	 */
	const stacked = function( text ) {

		const nodes = [];

		String( text ).split( '\n' ).forEach( function( line, at ) {
			if( at > 0 )
				nodes.push( kit.el( 'br' ) );

			nodes.push( line );
		} );

		return nodes;
	};

	/**
	 *	The sentence for dates that cannot be read, or for a refusal the page has no sentence for: never an empty list in its place
	 *
	 *	@param		{string}	state						off, missing or broken
	 *
	 *	@return		{Object}									A note
	 */
	const unreadable = function( state ) {
		return kit.note( 'bad', kit.el( 'span', { id : 'dates-state', text : kit.t( kit.has( 'dates.state-'+ state ) === true ? 'dates.state-'+ state : 'dates.state-broken' ) } ) );
	};

	/**
	 *	The client a date belongs to, as the lists show it: a link to its card
	 *
	 *	@param		{Object}	date
	 *
	 *	@return		{Object}
	 */
	const clientOf = function( date ) {
		return kit.link( '#client-'+ date.client_id, date.client_title, { on : { click : function( event ) { event.stopPropagation() } } } );
	};

	/**
	 *	The table of a list of dates
	 *
	 *	@param		{Array}		dates						As crm-dates/list has them
	 *	@param		{boolean}	withClient			The column of the client
	 *	@param		{string}	empty						The sentence of an empty list
	 *	@param		{string}	id
	 *
	 *	@return		{Object}
	 */
	const table = function( dates, withClient, empty, id ) {

		const columns = withClient === true ? 4 : 3;

		return kit.cardList( kit.el( 'div', { class : 'card wrap-x' }, kit.el( 'table', { class : 'tbl', id : id },
			kit.el( 'thead', {}, kit.el( 'tr', {},
				kit.el( 'th', { scope : 'col', text : kit.t( 'dates.column-when' ) } ), kit.el( 'th', { scope : 'col', text : kit.t( 'dates.column-title' ) } ),
				withClient === true ? kit.el( 'th', { scope : 'col', text : kit.t( 'column.client' ) } ) : null, kit.el( 'th', { scope : 'col', text : kit.t( 'dates.column-place' ) } ) ) ),
			kit.el( 'tbody', {}, dates.length === 0
				? kit.el( 'tr', { class : 'plain' }, kit.el( 'td', { colspan : String( columns ), class : 'muted', text : empty } ) )
				: dates.map( function( date ) {
					return kit.el( 'tr', { dataset : { id : date.id }, on : { click : function() { wn.location.hash = '#date-'+ date.id } } },
						kit.el( 'td', { class : 'num', text : whenOf( date ) }, date.running === true ? [ ' ', kit.badge( 'info', kit.t( 'dates.running' ) ) ] : null ),
						kit.el( 'td', { class : 'wrap' }, kit.link( '#date-'+ date.id, date.title !== '' ? date.title : date.id ) ),
						withClient === true ? kit.el( 'td', {}, clientOf( date ) ) : null,
						kit.el( 'td', { text : date.place } ) );
				} ) ) ) ), withClient === true ? 'dates' : 'client-dates', withClient === true ? [ 'date', 'id', 'who', 'sub' ] : [ 'date', 'id', 'sub' ] );
	};

	/**
	 *	The dates that are over, folded under their own line
	 *
	 *	@param		{Array}		dates
	 *	@param		{boolean}	withClient
	 *
	 *	@return		{Object|null}
	 */
	const past = function( dates, withClient ) {
		return dates.length === 0 ? null : kit.el( 'details', { class : 'fold', id : 'dates-past' }, kit.el( 'summary', { text : kit.t( 'dates.past', dates.length ) } ), table( dates, withClient, '', 'dates-past-list' ) );
	};

	/**
	 *	The dates whose start is no moment, folded under their own line: they are still their client's, and can be opened to be set anew or deleted
	 *
	 *	@param		{Array}		dates
	 *	@param		{boolean}	withClient
	 *
	 *	@return		{Object|null}
	 */
	const unreadableFold = function( dates, withClient ) {
		return dates.length === 0 ? null : kit.el( 'details', { class : 'fold', id : 'dates-unreadable' }, kit.el( 'summary', { text : kit.t( 'dates.unreadable-title', dates.length ) } ), kit.el( 'p', { class : 'state-note', text : kit.t( 'dates.unreadable-note' ) } ), table( dates, withClient, '', 'dates-unreadable-list' ) );
	};

	/**
	 *	What the owner does with the dates that carry the group of a client and are nobody's: for each group the client that has it today, and a button to give them to it and one to delete them. Each asks first, and
	 *	nothing happens of itself
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	body						As crm-dates/list has it
	 *
	 *	@return		{Object|null}
	 */
	const unassignedCard = function( inner, body ) {

		if( Array.isArray( body.unassigned ) === false || body.unassigned.length === 0 )
			return null;

		const rights = can( body );
		const total = body.unassigned.reduce( function( sum, one ) { return sum+ one.count }, 0 );

		// The group is typed in capitals or not, as the server takes it; the dialog compares the field with the group exactly, so what is typed is put into lower case where it stands
		const lower = function( field ) {

			field.addEventListener( 'input', function() {

				if( field.value === field.value.toLowerCase() )
					return;

				field.value = field.value.toLowerCase();
				field.dispatchEvent( new wn.Event( 'input' ) );
			} );
		};

		const act = function( one, mode ) {

			const title = kit.t( mode === 'assign' ? 'dates.assign-title' : 'dates.discard-title' );
			const text = mode === 'assign' ? kit.t( 'dates.assign-text', one.group, one.count, one.client.number+ ' '+ one.client.title ) : kit.t( 'dates.discard-text', one.count );

			// Giving the dates away is answered with a yes to a question that names the client and the number; deleting them is answered by typing the group - the field starts empty and the yes waits for it
			const asked = mode === 'assign'
				? kit.ask( { title : title, text : text, yes : kit.t( 'dates.assign' ) } ).then( function( yes ) { return yes === true ? { group : one.group, mode : mode, client : one.client.number, count : one.count } : null } )
				: kit.dialog( { title : title, text : text, yes : kit.t( 'dates.discard' ), danger : true, body : kit.el( 'p', { class : 'state-note' }, kit.el( 'span', { class : 'dates-group', text : one.group } ) ), fields : [ { name : 'confirm', label : kit.t( 'dates.discard-type' ), hint : kit.t( 'dates.discard-hint', one.group ), maxlength : 20, autocomplete : 'off' } ], unlock : { field : 'confirm', word : one.group }, setup : function( controls ) { lower( controls.confirm ) } } )
					.then( function( values ) { return values === null ? null : { group : one.group, mode : mode, confirm : values.confirm, count : one.count } } );

			asked.then( function( post ) {

				if( post === null || inner.alive() !== true )
					return;

				inner.call( 'crm-dates/adopt', post, 'dates.error-adopt' ).then( function( answer ) {
					if( inner.alive() !== true )
						return;

					// The list is read again after an answer that says it was not what the owner was shown, as after one that went through
					if( answer.ok === true || answer.status === 409 ) {
						if( answer.ok === true )
							kit.toast( kit.t( mode === 'assign' ? 'dates.assigned' : 'dates.discarded', answer.body.adopted ) );

						own.focus = true;
						inner.route();
					}
				} );
			} );
		};

		return kit.card( kit.t( 'dates.unassigned-title', total ), kit.el( 'p', { class : 'state-note', text : kit.t( 'dates.unassigned-note' ) } ), kit.el( 'ul', { class : 'list-mini', id : 'dates-unassigned' }, body.unassigned.map( function( one ) {
			return kit.el( 'li', { dataset : { group : one.group } },
				kit.el( 'span', {}, kit.el( 'b', { text : kit.t( 'dates.unassigned-group', one.group, one.count ) } ), kit.el( 'span', { class : 'state-note block', text : one.client === null ? kit.t( 'dates.unassigned-noclient' ) : kit.t( 'dates.unassigned-client', one.client.number+ ' '+ one.client.title ) } ),
					one.client !== null && one.client.active !== true ? kit.el( 'span', { class : 'state-note block', text : kit.t( 'dates.unassigned-off' ) } ) : null ),
				kit.el( 'span', { class : 'actions' },
					one.client !== null && one.client.active === true && rights.update === true ? kit.el( 'button', { type : 'button', class : 'btn sm', id : 'assign-'+ one.group, text : kit.t( 'dates.assign' ), on : { click : function() { act( one, 'assign' ) } } } ) : null,
					rights.delete === true ? kit.el( 'button', { type : 'button', class : 'btn sm', id : 'discard-'+ one.group, text : kit.t( 'dates.discard' ), on : { click : function() { act( one, 'delete' ) } } } ) : null ) );
		} ) ) );
	};

	/**
	 *	The rows that say a date is a client's where the date is not in the type: how many they are, and a button that takes them away after a question that says so. They are not taken by themselves - an
	 *	older file of the type that is put back and then the right one makes them needed again
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	body						As crm-dates/list has it
	 *
	 *	@return		{Object|null}
	 */
	const lonelyCard = function( inner, body ) {

		if( typeof body.lonely !== 'number' || body.lonely < 1 )
			return null;

		const rights = can( body );
		const count = body.lonely;

		return kit.card( kit.t( 'dates.lonely-title', count ), kit.el( 'p', { class : 'state-note', text : kit.t( 'dates.lonely-note' ) } ),
			rights.delete !== true ? null : kit.el( 'div', { class : 'actions' }, kit.el( 'button', { type : 'button', class : 'btn sm', id : 'dates-tidy', text : kit.t( 'dates.tidy' ), on : { click : function() {

				kit.ask( { title : kit.t( 'dates.tidy-title' ), text : kit.t( count === 1 ? 'dates.tidy-text-one' : 'dates.tidy-text-many', count ), yes : kit.t( 'dates.tidy' ), danger : true } ).then( function( yes ) {

					if( yes !== true || inner.alive() !== true )
						return;

					inner.call( 'crm-dates/tidy', { count : count }, 'dates.error-adopt' ).then( function( answer ) {
						if( inner.alive() !== true )
							return;

						if( answer.ok === true )
							kit.toast( kit.t( 'dates.tidied', answer.body.tidied ) );

						if( answer.ok === true || answer.status === 409 ) {
							own.focus = true;
							inner.route();
						}
					} );
				} );
			} } } ) ) );
	};

	/**
	 *	The line under the lists: where the times are read, and where the dates of the other kinds are kept
	 *
	 *	@param		{Object}	body						An answer of the package
	 *
	 *	@return		{Object}
	 */
	const footnote = function( body ) {
		return kit.el( 'p', { class : 'state-note top', id : 'dates-note', text : kit.t( 'dates.note', body.zone ) } );
	};

	// --- The list -----------------------------------------------------------------------------------------------------

	/**
	 *	@param		{Object}	view						The kit of the page and the params of the address
	 *
	 *	@return		{void}
	 */
	const list = function( inner ) {

		inner.main.appendChild( kit.el( 'p', { class : 'state-note', role : 'status', text : kit.t( 'page.loading' ) } ) );

		inner.api( 'crm-dates/list' ).then( function( answer ) {

			if( inner.alive() !== true )
				return;

			if( answer.ok !== true ) {
				inner.loadFailed( answer, 'error.load', function() { inner.route() } );
				return;
			}

			drawList( inner, answer.body );
		} );
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Object}	body						As crm-dates/list has it
	 *
	 *	@return		{void}
	 */
	const drawList = function( inner, body ) {

		const query = own.q.trim().toLowerCase();
		const only = function( dates ) {
			return dates.filter( function( date ) { return query === '' || ( date.title+ ' '+ date.place+ ' '+ date.client_title+ ' '+ date.client ).toLowerCase().includes( query ) } );
		};

		inner.reveal();

		inner.main.appendChild( inner.head( kit.t( 'dates.title' ), kit.el( 'span', { class : 'sub', text : body.state === 'ok' ? kit.t( 'dates.count', body.upcoming.length ) : '' } ), kit.el( 'span', { class : 'grow' } ),
			body.state !== 'ok' ? null : inner.search( kit.t( 'dates.search' ), own.q, { input : function( event ) {
				own.q = event.target.value;
				inner.keepSearch( function() { drawList( inner, body ) }, true );
			} } ),
			body.state === 'ok' && can( body ).insert === true ? kit.link( '#dates-new', kit.t( 'dates.new' ), { class : 'btn primary', id : 'dates-new' } ) : null ) );

		// What the owner did with the card took the button the focus was on: the focus goes to the heading of the list, where the page begins again
		if( own.focus === true ) {
			const heading = inner.main.querySelector( 'h1' );

			own.focus = false;

			if( heading !== null && heading !== undefined ) {
				heading.setAttribute( 'tabindex', '-1' );
				heading.focus();
			}
		}

		if( body.state !== 'ok' ) {
			inner.main.appendChild( unreadable( body.state ) );
			return;
		}

		inner.main.appendChild( table( only( body.upcoming ), true, kit.t( body.upcoming.length === 0 ? 'dates.empty' : 'dates.none', 60 ), 'dates' ) );
		kit.append( inner.main, [ past( only( body.past ), true ), unreadableFold( only( body.unreadable || [] ), true ), unassignedCard( inner, body ), lonelyCard( inner, body ), footnote( body ) ] );
	};

	// --- The form -----------------------------------------------------------------------------------------------------

	/**
	 *	The moments the fields of the form hold: a start with no time is a day, an end is a day and a time
	 *
	 *	@param		{Object}	values					startDay, startTime, endDay, endTime
	 *
	 *	@return		{Object}									start and end (Y-m-dTH:i; end is '' for none), or problem (the control that is wrong and the key of its sentence)
	 */
	const moments = function( values ) {

		if( /^\d{4}-\d{2}-\d{2}$/.test( values.startDay ) === false )
			return { problem : [ 'start-day', 'dates.err-start' ] };

		const start = values.startDay+ 'T'+ ( values.startTime === '' ? '00:00' : values.startTime );

		if( values.endDay === '' && values.endTime === '' )
			return { start : start, end : '' };

		// An end has a day and a time: a day for an end is no way of saying when it ends, and the page does not say it for the person
		if( values.endTime === '' )
			return { problem : [ 'end-time', 'dates.err-end-time' ] };

		const endDay = values.endDay === '' ? values.startDay : values.endDay;
		const end = endDay+ 'T'+ values.endTime;

		if( /^\d{4}-\d{2}-\d{2}$/.test( endDay ) === false || end <= start )
			return { problem : [ values.endDay === '' ? 'end-time' : 'end-day', 'dates.err-end' ] };

		return { start : start, end : end };
	};

	/**
	 *	The form of a date: a new one, or the one that is changed. Title, client, the day and the time it begins and ends, the place and the words
	 *
	 *	@param		{Object}	spec						clients (those that can be chosen), zone, date (as crm-dates/get has it; null for a new one), preset (the number of the client of a new one)
	 *
	 *	@return		{Object}									form, list and posts()
	 */
	const makeForm = function( spec ) {

		const form = kit.formOf();
		const date = spec.date;
		const chooser = kit.clientField( { id : 'f-client', placeholder : kit.t( 'dates.pick-client' ) }, spec.clients, date === null ? spec.preset : date.client );
		const words = date === null ? '' : date.text;
		const rows = [
			{ name : 'title', label : kit.t( 'dates.f-title' ), control : kit.el( 'input', { id : 'f-title', type : 'text', maxlength : '200', autocomplete : 'off', value : date === null ? '' : date.title } ) },
			{ name : 'client', label : kit.t( 'dates.f-client' ), control : chooser.input, node : chooser.node, picker : chooser },
			{ name : 'start-day', label : kit.t( 'dates.f-start' ), control : kit.el( 'input', { id : 'f-start-day', type : 'date', value : date === null ? '' : date.start.slice( 0, 10 ) } ) },
			{ name : 'start-time', label : kit.t( 'dates.f-start-time' ), control : kit.el( 'input', { id : 'f-start-time', type : 'time', value : date === null || date.all_day === true || date.unreadable === true ? '' : date.start.slice( 11, 16 ) } ), hint : kit.t( 'dates.hint-time', spec.zone ) },
			{ name : 'end-day', label : kit.t( 'dates.f-end' ), control : kit.el( 'input', { id : 'f-end-day', type : 'date', value : date === null || date.end === '' ? '' : date.end.slice( 0, 10 ) } ), hint : kit.t( 'form.optional' ) },
			{ name : 'end-time', label : kit.t( 'dates.f-end-time' ), control : kit.el( 'input', { id : 'f-end-time', type : 'time', value : date === null || date.end === '' ? '' : date.end.slice( 11, 16 ) } ), hint : kit.t( 'form.optional' ) },
			{ name : 'place', label : kit.t( 'dates.f-place' ), control : kit.el( 'input', { id : 'f-place', type : 'text', maxlength : '200', autocomplete : 'off', value : date === null ? '' : date.place } ), hint : kit.t( 'form.optional' ) },
			{ name : 'description', label : kit.t( 'dates.f-description' ), control : kit.el( 'textarea', { id : 'f-description', rows : 4, maxlength : '2000', value : words } ), hint : kit.t( 'form.optional' ) },
		];
		const fields = kit.formList( rows, form );

		/**
		 *	What the form says, checked: the post (the words stay out where they were not changed, since the type holds lists that the form does not) or the control that is wrong
		 *
		 *	@return		{Object}								post, or problem ([ control, key of the sentence ])
		 */
		const posts = function() {

			const values = kit.valuesOfForm( form );
			const title = values.title.trim();

			if( title === '' )
				return { problem : [ 'title', 'dates.err-title' ] };

			const client = spec.clients.find( function( one ) { return one.number === chooser.value() } );

			if( client === undefined )
				return { problem : [ 'client', 'dates.err-client' ] };

			const made = moments( { startDay : values['start-day'], startTime : values['start-time'], endDay : values['end-day'], endTime : values['end-time'] } );

			if( made.problem !== undefined )
				return { problem : made.problem };

			const post = { client : String( client.id ), title : title, start : made.start, end : made.end, place : values.place.trim() };

			if( date !== null )
				post.id = date.id;

			if( date === null ? values.description.trim() !== '' : values.description.trim() !== words )
				post.text = values.description;

			return { post : post };
		};

		return { form : form, list : fields, posts : posts };
	};

	/**
	 *	Say what is wrong with a form and put the person at it
	 *
	 *	@param		{Object}	form
	 *	@param		{Array}		problem					[ control, key of the sentence ]
	 *
	 *	@return		{void}
	 */
	const problemAt = function( form, problem ) {

		const control = form.controls[problem[0]];

		control.classList.add( 'err' );
		control.setAttribute( 'aria-invalid', 'true' );
		form.notes[problem[0]].textContent = kit.t( problem[1] );
		control.focus();
		kit.toast( kit.t( problem[1] ), true );
	};

	/**
	 *	Forget what the last check said at the controls of a form
	 *
	 *	@param		{Object}	form
	 *
	 *	@return		{void}
	 */
	const unmark = function( form ) {

		Object.keys( form.controls ).forEach( function( name ) {
			form.controls[name].classList.remove( 'err' );
			form.controls[name].removeAttribute( 'aria-invalid' );
			form.notes[name].textContent = '';
		} );
	};

	/**
	 *	The clients that can be chosen: those that are on, and the one a date has where it is not
	 *
	 *	@param		{Array}		clients
	 *	@param		{Object}	[date]
	 *
	 *	@return		{Array}
	 */
	const chosable = function( clients, date ) {
		return clients.filter( function( client ) { return client.active === true || ( date !== undefined && date !== null && client.number === date.client ) } );
	};

	/**
	 *	The screen of a new date: #dates-new, or #dates-new-k<id> with the client chosen
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	where						client: the id of the client, '' for none
	 *
	 *	@return		{void}
	 */
	const draftNew = function( inner, where ) {

		inner.main.appendChild( kit.el( 'p', { class : 'state-note', role : 'status', text : kit.t( 'page.loading' ) } ) );

		Promise.all( [ inner.api( 'crm-clients/list' ), inner.api( 'crm-dates/list', { days : 1 } ) ] ).then( function( answers ) {

			if( inner.alive() !== true )
				return;

			const failure = answers.find( function( answer ) { return answer.ok !== true } );

			if( failure !== undefined ) {
				inner.loadFailed( failure, 'error.load', function() { inner.route() } );
				return;
			}

			const body = answers[1].body;

			if( body.state !== 'ok' || can( body ).insert !== true ) {
				inner.reveal();
				inner.main.appendChild( inner.head( kit.t( 'dates.title' ) ) );
				inner.main.appendChild( body.state !== 'ok' ? unreadable( body.state ) : kit.note( 'bad', kit.t( 'dates.forbidden' ) ) );
				return;
			}

			const clients = answers[0].body.clients.filter( function( client ) { return client.active === true } );
			const chosen = clients.find( function( client ) { return String( client.id ) === where.client } );

			drawNew( inner, clients, body, chosen === undefined ? '' : chosen.number );
		} );
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Array}		clients
	 *	@param		{Object}	body						As crm-dates/list has it
	 *	@param		{string}	preset					The number of the client chosen
	 *
	 *	@return		{void}
	 */
	const drawNew = function( inner, clients, body, preset ) {

		const made = makeForm( { clients : clients, zone : body.zone, date : null, preset : preset } );
		const form = made.form;
		const create = kit.el( 'button', { type : 'submit', class : 'btn primary', id : 'date-create', title : kit.t( 'save.shortcut' ), text : kit.t( 'dates.create' ) } );
		let creating = false;

		const submit = function( event ) {

			event.preventDefault();

			if( creating === true )
				return;

			unmark( form );

			const result = made.posts();

			if( result.problem !== undefined ) {
				problemAt( form, result.problem );
				return;
			}

			creating = true;
			create.disabled = true;

			inner.api( 'crm-dates/save', result.post ).then( function( answer ) {

				creating = false;
				create.disabled = false;

				if( answer.ok !== true ) {
					inner.refuseAt( form, answer, 'dates.error-save' );
					return;
				}

				kit.toast( kit.t( 'dates.made', result.post.title ) );

				if( inner.alive() === true ) {
					form.dirty = false;
					wn.location.hash = '#date-'+ answer.body.date.id;
				}
			} );
		};

		inner.reveal();

		const box = kit.el( 'form', { id : 'date-form', novalidate : 'novalidate', on : { submit : submit } },
			inner.head( kit.t( 'dates.new-title' ), kit.el( 'span', { class : 'grow' } ), kit.link( '#dates', kit.t( 'form.cancel' ), { class : 'btn', id : 'date-abort' } ), create ),
			kit.el( 'div', { class : 'card pad' }, made.list ) );

		inner.main.appendChild( inner.crumb( '#dates', kit.t( 'dates.title' ), kit.t( 'dates.new-title' ).toLowerCase() ) );
		inner.main.appendChild( box );
		kit.submitter( 'new-date', box, create, function() { return form.dirty === true } );
		form.controls.title.focus();
	};

	// --- One date -----------------------------------------------------------------------------------------------------

	/**
	 *	@param		{Object}	inner
	 *	@param		{string}	id
	 *
	 *	@return		{void}
	 */
	const one = function( inner, id ) {

		inner.main.appendChild( kit.el( 'p', { class : 'state-note', role : 'status', text : kit.t( 'page.loading' ) } ) );

		Promise.all( [ inner.api( 'crm-dates/get', { id : id } ), inner.api( 'crm-clients/list' ) ] ).then( function( answers ) {

			if( inner.alive() !== true )
				return;

			const answer = answers[0];

			if( answer.status === 404 || answer.status === 409 ) {

				inner.reveal();
				inner.main.appendChild( inner.head( kit.t( 'dates.title' ) ) );

				if( answer.body !== null && [ 'crm_dates_off', 'crm_dates_missing', 'crm_dates_broken' ].includes( answer.body.code ) === true )
					inner.main.appendChild( unreadable( answer.body.code.slice( 10 ) ) );
				else
					inner.main.appendChild( kit.note( 'bad', kit.el( 'span', { id : 'date-gone', text : inner.errorText( answer, 'dates.gone' ) } ), ' ', kit.link( '#dates', kit.t( 'dates.back' ) ) ) );

				return;
			}

			if( answer.ok !== true || answers[1].ok !== true ) {
				inner.loadFailed( answer.ok !== true ? answer : answers[1], 'error.load', function() { inner.route() } );
				return;
			}

			drawOne( inner, { date : answer.body.date, zone : answer.body.zone, can : can( answer.body ), clients : answers[1].body.clients, editing : false, info : null } );
		} );
	};

	/**
	 *	The facts of the date, or - after Edit - the form that changes it
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const infoCard = function( inner, view ) {

		const date = view.date;

		if( view.editing === false ) {

			kit.unsaver( 'date' );

			return kit.el( 'div', { class : 'card pad', id : 'date-info' }, kit.el( 'h3', { text : kit.t( 'dates.card' ) } ), kit.kv( [
				[ kit.t( 'dates.f-start' ), whenOf( date ) ],
				[ kit.t( 'dates.f-place' ), date.place ],
				[ kit.t( 'dates.f-client' ), kit.link( '#client-'+ date.client_id, date.client_title+ ' · '+ date.client ) ],
				[ kit.t( 'dates.f-description' ), date.text === '' ? '' : stacked( date.text ) ],
				[ kit.t( 'dates.f-link' ), date.link ],
			] ) );
		}

		const made = makeForm( { clients : chosable( view.clients, date ), zone : view.zone, date : date, preset : '' } );
		const form = made.form;
		const save = kit.el( 'button', { type : 'submit', class : 'btn sm primary', id : 'date-save', title : kit.t( 'save.shortcut' ), text : kit.t( 'form.save' ) } );
		let saving = false;

		const box = kit.el( 'form', { class : 'card pad', id : 'date-info', novalidate : 'novalidate', on : { submit : function( event ) {

			event.preventDefault();

			if( saving === true )
				return;

			unmark( form );

			const result = made.posts();

			if( result.problem !== undefined ) {
				problemAt( form, result.problem );
				return;
			}

			saving = true;
			save.disabled = true;

			inner.api( 'crm-dates/save', result.post ).then( function( answer ) {

				saving = false;
				save.disabled = false;

				if( answer.ok !== true ) {
					inner.refuseAt( form, answer, 'dates.error-save' );
					return;
				}

				form.dirty = false;
				kit.toast( kit.t( 'dates.saved' ) );

				if( inner.alive() === true ) {
					view.date = answer.body.date;
					view.editing = false;
					drawOne( inner, view );
				}
			} );
		} } }, kit.el( 'h3', { text : kit.t( 'dates.card' ) } ), made.list,
			kit.el( 'div', { class : 'actions top' }, save, kit.el( 'button', { type : 'button', class : 'btn sm', id : 'date-cancel', text : kit.t( 'form.cancel' ), on : { click : function() {
				form.dirty = false;
				view.editing = false;
				view.info.replaceWith( view.info = infoCard( inner, view ) );
			} } } ) ) );

		kit.submitter( 'date', box, save, function() { return form.dirty === true } );

		return box;
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Object}	view
	 *
	 *	@return		{void}
	 */
	const drawOne = function( inner, view ) {

		const date = view.date;
		const items = [];

		inner.reveal();

		if( view.can.update === true )
			items.push( { label : kit.t( 'dates.edit' ), run : function() {

				if( view.editing === true )
					return;

				view.editing = true;
				view.info.replaceWith( view.info = infoCard( inner, view ) );
			} } );

		if( view.can.delete === true )
			items.push( 'sep', { label : kit.t( 'dates.delete' ), danger : true, run : function() {
				kit.ask( { title : kit.t( 'dates.delete' ), text : kit.t( 'dates.delete-text', date.title !== '' ? date.title : date.id ), yes : kit.t( 'dates.delete' ), danger : true } ).then( function( yes ) {

					if( yes !== true || inner.alive() !== true )
						return;

					inner.call( 'crm-dates/delete', { id : date.id }, 'dates.error-delete' ).then( function( answer ) {
						if( answer.ok === true && inner.alive() === true ) {
							kit.toast( kit.t( 'dates.deleted' ) );
							wn.location.hash = '#client-'+ date.client_id+ '-dates';
						}
					} );
				} );
			} } );

		inner.main.appendChild( inner.crumb( '#dates', kit.t( 'dates.title' ), date.title !== '' ? date.title : date.id ) );
		inner.main.appendChild( inner.head( kit.el( 'h1', { text : date.title !== '' ? date.title : date.id } ), date.running === true ? kit.badge( 'info', kit.t( 'dates.running' ) ) : null,
			kit.el( 'span', { class : 'sub', text : whenOf( date ) } ), kit.el( 'span', { class : 'grow' } ),
			items.length === 0 ? null : kit.menu( { label : '⋯', aria : kit.t( 'menu.more' ), items : items } ) ) );

		view.info = infoCard( inner, view );
		inner.main.appendChild( view.info );
	};

	// --- The page -----------------------------------------------------------------------------------------------------

	crm.pages.add( { key : 'dates', hash : [ 'dates', 'date' ], title : function() { return kit.t( 'dates.title' ) }, parse : function( text ) {

		if( text === 'dates' )
			return { screen : 'list' };

		let found = NEW.exec( text );

		if( found !== null )
			return { screen : 'new', client : found[1] === undefined ? '' : found[1] };

		found = ONE.exec( text );

		return found === null ? null : { screen : 'one', id : found[1] };
	}, draw : function( inner, where ) {

		if( where.screen === 'new' )
			draftNew( inner, where );
		else if( where.screen === 'one' )
			one( inner, where.id );
		else
			list( inner );

		return null;
	} } );

	crm.menu.add( { key : 'dates', word : function() { return kit.t( 'nav.dates' ) }, rank : 60, page : 'dates' } );

	// --- The tab of the card of a client ------------------------------------------------------------------------------

	crm.cards.addTab( 'client', { key : 'dates', word : function() { return kit.t( 'dates.tab' ) }, rank : 300, draw : function( inner, record ) {

		const box = kit.el( 'div', { id : 'client-dates' }, kit.el( 'p', { class : 'state-note', role : 'status', text : kit.t( 'page.loading' ) } ) );

		inner.api( 'crm-dates/list', { client : String( record.client.id ), days : 365 } ).then( function( answer ) {

			if( inner.alive() !== true )
				return;

			kit.clear( box );

			if( answer.ok !== true ) {
				box.appendChild( kit.note( 'bad', inner.failed( answer, 'error.load' ) ) );
				return;
			}

			if( answer.body.state !== 'ok' ) {
				box.appendChild( unreadable( answer.body.state ) );
				return;
			}

			kit.append( box, [
				can( answer.body ).insert === true && record.client.active === true ? kit.el( 'div', { class : 'actions top' }, kit.link( '#dates-new-k'+ record.client.id, kit.t( 'dates.new' ), { class : 'btn primary', id : 'client-date-new' } ) ) : null,
				table( answer.body.upcoming, false, kit.t( 'dates.client-empty' ), 'client-dates-list' ),
				past( answer.body.past, false ),
				unreadableFold( answer.body.unreadable, false ),
				footnote( answer.body ) ] );
		} );

		return box;
	} } );

	// --- The next date: a fact of the card of a project, a tile of the overview -----------------------------------------

	crm.cards.addFact( 'project', { key : 'dates', rank : 400, word : function() { return kit.t( 'dates.next' ) }, draw : function( inner, record ) {

		if( record.client === null || record.client === undefined )
			return null;

		const value = kit.el( 'span', { id : 'project-next-date', text : kit.t( 'page.loading' ) } );

		inner.api( 'crm-dates/list', { client : String( record.client.id ), days : 3660 } ).then( function( answer ) {

			if( inner.alive() !== true )
				return;

			kit.clear( value );

			if( answer.ok !== true || answer.body.state !== 'ok' ) {
				value.textContent = kit.t( 'dates.next-unread' );
				return;
			}

			const next = answer.body.upcoming[0];

			if( next === undefined )
				value.textContent = kit.t( 'dates.next-none' );
			else
				kit.append( value, [ kit.link( '#date-'+ next.id, whenOf( next ) ), ' · '+ next.title ] );
		} );

		return value;
	} } );

	crm.overview.addTile( { key : 'dates', rank : 20, draw : function( inner, overview ) {

		const part = overview.packages !== null && typeof overview.packages === 'object' && overview.packages[KEY] !== undefined ? overview.packages[KEY] : null;

		if( part === null )
			return null;

		const next = part.state === 'ok' && part.next !== null && typeof part.next === 'object' ? part.next : undefined;
		const value = part.state !== 'ok' ? '–' : ( next === undefined ? '–' : dayOf( next.start ).slice( 0, 2 )+ ' '+ kit.dmy( next.start ).slice( 0, 6 )+ ( next.all_day === true ? '' : ', '+ next.start.slice( 11, 16 ) ) );
		const hint = part.state !== 'ok' ? kit.t( 'dates.tile-unread' ) : ( next === undefined ? kit.t( 'dates.tile-none' ) : next.title+ ( next.client_title === '' ? '' : ' · '+ next.client_title ) );

		return kit.el( 'a', { class : 'card tile', id : 'tile-dates', href : next === undefined ? '#dates' : '#date-'+ next.id },
			kit.el( 'div', { class : 'l', text : kit.t( 'dates.tile' ) } ), kit.el( 'div', { class : 'v', text : value } ), kit.el( 'div', { class : 'h', text : hint } ) );
	} } );

	crm.keys.add( { key : function() { return kit.t( 'dates.key' ) }, href : '#dates', word : function() { return kit.t( 'dates.key-word' ) } } );

	crm.wipe.addCount( { key : 'dates', rank : 60, goes : function() { return kit.t( 'dates.wipe-goes' ) }, count : function( inner, number ) { return kit.t( 'dates.wipe-count', number ) } } );

}( window ) );
