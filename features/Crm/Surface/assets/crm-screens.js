/**
 *	Crm		The surface of the CRM in the browser, the third of its four files: the screens of the core, each registered through the points of crm-points.js with the kit of crm-kit.js, so that a package can do the same.
 *				The overview (with its search over the clients and the projects), the clients (the list, the card with its tabs, a new client), the projects (the list, a new one, the card with its tabs: tasks, releases, logbook), the
 *				values (the types - the phases of a project - as tabs, the list of the elements of a type and the form of one, which are drawn from the definition of the type and edit through the panel's actions), the settings (the
 *				company, the wordmark of the mails, the hosts, the numbers, the mail notifications, the test phase), the setup (the lines of the status with their fixes) and the app of the tasks.
 */

( function( wn, dc ) {

	'use strict';

	const core = wn.Nino.crm.core;
	const { ADDRESS, CLIENT_FIELDS, PAGE_SIZE, WIPE_STAYS, addApp, addBlock, addCardTab, addKey, addMenu, addPage, addSearch, addSettingsSection, addSettingsTab, addTile, addWipe, api, append, ask, badge, call, card, cardFacts, cardList, cardStrip, clear, clientField, clientPicker, clientTitle, columnRole, columnTd, columnTh, copyText, coreValues, countOpen, dataList, deviceCard, dialog, dmy, el, entryFailed, entryNode, errorText, extraText, failed, formDone, formList, formOf, formParts, formValues, guarded, has, head, headItems, headParts, holds, homeHint, keepSearch, kitOf, kv, link, listColumns, listMarks, loadColumns, loadFailed, loading, logbookKind, mailtoOf, menu, needExtra, needKnown, note, overviewBlocks, overviewTiles, packageStates, pagerOf, pickerClients, projectLabel, projectNo, refresh, refuseAt, reveal, saver, search, searchBox, sentenceWith, settingsOrdered, settingsTabs, setupStray, setupWhole, sortHead, stamp, state, step, stepList, stepStand, submitter, t, tabPattern, telOf, tile, toast, unsaver, valuesOfForm, wipeLines, wipeSentence, wordOf } = core;

	/**
	 *	@param		{Object}	need						An entry of needs()
	 *
	 *	@return		{Object|Array|null}				Its line, or lines: the core has no need of its own, a package registers the kinds it names (overview.addNeed)
	 */
	const needLine = function( need ) {
		return needExtra( need );
	};
	/**
	 *	The card of what needs the owner: the lines the packages draw for the needs the server names, the first ten and the number of the others
	 *
	 *	@param		{Object}	overview				As the overview has it: needs
	 *
	 *	@return		{Object}
	 */
	const needsCard = function( overview ) {

		const needs = overview.needs.filter( needKnown );
		const shown = needs.slice( 0, 10 );

		return card( t( 'needs.title' ), el( 'ul', { class: 'list-mini' },
			shown.length === 0 ? el( 'li', { class: 'muted', text: t( 'needs.none' ) } ) : shown.map( needLine ),
			needs.length > shown.length ? el( 'li', { class: 'muted', text: t( 'needs.more', needs.length - shown.length ) } ) : null ) );
	};
	/**
	 *	The overview: what the core reads at a glance (crm/overview) and what the packages registered to load (overview.addData), then the search, the hint for the home screen, the tiles and the blocks
	 *
	 *	@param		{Object}	where
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const overviewScreen = function( where, mine ) {

		loading();

		const kit = kitOf( mine );

		// What a package loads for the overview is its own risk: a source that fails leaves its key out and the screen is drawn
		const sources = settingsOrdered( dataList.list ).map( function( source ) {

			const made = guarded( function() { return source.load( kit ) } );

			return Promise.resolve( made.ok === true ? made.value : null ).then( function( value ) { return [ source.key, value === undefined ? null : value ] }, function() { return [ source.key, null ] } );
		} );

		Promise.all( [ api( 'crm/overview' ), Promise.all( sources ) ] ).then( function( answers ) {

			const answer = answers[0];

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			countOpen( answer.body );

			// What the tiles and the blocks are given: the answer of the overview, and what the packages loaded beside it
			const loaded = Object.assign( {}, answer.body, Object.fromEntries( answers[1].filter( function( pair ) { return pair[0] !== 'summary' && pair[0] !== 'needs' && pair[0] !== 'packages' } ) ) );

			reveal();

			const find = searchBox( mine );

			state.main.appendChild( head( t( 'overview.title' ), el( 'span', { class: 'sub', text: new Date().toLocaleDateString( state.locale.replace( '_', '-' ), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' } ) } ), el( 'span', { class: 'grow' } ), find ) );

			const hint = homeHint();

			if( hint !== null )
				state.main.appendChild( hint );

			state.main.appendChild( overviewTiles( loaded, mine ) );
			overviewBlocks( loaded, mine ).forEach( function( node ) { state.main.appendChild( node ) } );
		} );
	};
	/**
	 *	@param		{string}	name
	 *	@param		{string}	value
	 *
	 *	@return		{Object}									The text field of a client's property
	 */
	const clientControl = function( name, value, id ) {

		const limit = name === 'country' ? '2' : ( name === 'buyer_reference' ? '100' : '160' );

		return el( 'input', { id : id || 'f-'+ name, type : name === 'email' ? 'email' : 'text', value : value, maxlength : limit, autocomplete : 'off', inputmode : name === 'phone' ? 'tel' : null, list : name === 'country' ? 'countries' : null } );
	};
	/**
	 *	The row of the form of a client that says whether it is a business
	 *
	 *	@param		{boolean}	on
	 *
	 *	@return		{Object}
	 */
	const businessRow = function( on ) {
		return { name : 'business', label : t( 'clientfield.business' ), control : el( 'input', { id : 'f-business', type : 'checkbox', role : 'switch', checked : on } ), hint : t( 'clientfield.business-hint' ) };
	};
	/**
	 *	@param		{Object}	client
	 *
	 *	@return		{Array}										The lines of its address: street, zip and city, the country where it is not Germany
	 */
	const addressOf = function( client ) {
		return [ client.street, ( client.zip+ ' '+ client.city ).trim(), client.country !== '' && client.country !== 'DE' ? client.country : '' ].filter( function( part ) { return part !== '' } );
	};
	/**
	 *	@param		{Array}		lines
	 *
	 *	@return		{Object|string}						The lines one under the other; '' for none, which a definition list leaves out
	 */
	const stacked = function( lines ) {
		return lines.length === 0 ? '' : el( 'span', {}, lines.map( function( part, index ) { return [ index === 0 ? null : el( 'br' ), part ] } ) );
	};
	/**
	 *	@param		{Object}	client
	 *
	 *	@return		{boolean}									Whether the client has neither a company nor a name yet
	 */
	const clientBare = function( client ) {
		return client.company === '' && client.name === '';
	};
	const clientKeys = {
		number : function( client ) { return client.number },
		company : function( client ) { return client.company.toLowerCase() },
		person : function( client ) { return client.name.toLowerCase() },
		city : function( client ) { return client.city.toLowerCase() },
		active : function( client ) { return client.active === true ? 1 : 0 },
	};
	/**
	 *	The rows the list of the clients shows: the search over number, company, name, mail and city, then the order (a tie goes by the id)
	 *
	 *	@param		{Array}		clients
	 *	@param		{Object}	filter					q, sort, dir
	 *
	 *	@return		{Array}
	 */
	const visibleClients = function( clients, filter ) {

		const query = filter.q.trim().toLowerCase();
		const key = clientKeys[filter.sort] || clientKeys.number;

		const rows = clients.filter( function( client ) {
			return query === '' || ( client.number+ ' '+ client.company+ ' '+ client.name+ ' '+ client.email+ ' '+ client.city ).toLowerCase().includes( query );
		} );

		rows.sort( function( a, b ) {

			const left = key( a );
			const right = key( b );
			const order = typeof left === 'string' ? left.localeCompare( right, state.locale.replace( '_', '-' ) ) : left - right;

			return order !== 0 ? order * filter.dir : a.id - b.id;
		} );

		return rows;
	};
	const clientBadge = function( client ) {
		return client.active === true ? badge( 'ok', t( 'clients.active' ) ) : badge( 'muted', t( 'clients.inactive' ) );
	};
	const clientsScreen = function( where, mine ) {

		loading();

		api( 'crm-clients/list' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			state.people = answer.body.clients;
			state.erasing = Array.isArray( answer.body.erasing ) === true ? answer.body.erasing : [];
			state.wiping = answer.body.wiping === true;

			// What the columns of the packages need is read once, before the rows are drawn: a column that cannot be read stays empty
			loadColumns( 'client', state.people ).then( function() {
				if( state.view === mine )
					drawClients();
			} );
		} );
	};
	const drawClients = function() {

		const filter = state.cfilter;
		const rows = visibleClients( state.people, filter );
		const pages = Math.max( 1, Math.ceil( rows.length / PAGE_SIZE ) );

		filter.page = Math.min( Math.max( 1, filter.page ), pages );

		const shown = rows.slice( ( filter.page - 1 ) * PAGE_SIZE, filter.page * PAGE_SIZE );
		const again = function() { drawClients() };
		const heading = function( key, label, right ) { return sortHead( filter, key, label, right, again, 1 ) };
		const extra = listColumns( 'client' );

		const body = el( 'tbody', {}, shown.length === 0
			? el( 'tr', { class: 'plain' }, el( 'td', { colspan: String( 5 + extra.length ), class: 'muted', text: state.people.length === 0 ? t( 'clients.empty' ) : t( 'clients.none' ) } ) )
			: shown.map( function( client ) {
				return el( 'tr', { dataset: { id: client.id }, on: { click: function() { wn.location.hash = '#client-'+ client.id } } },
					el( 'td', { class: 'num' }, link( '#client-'+ client.id, client.number ) ),
					el( 'td', {}, clientBare( client ) ? [ el( 'span', { class: 'bare', text: clientTitle( client ) } ), el( 'span', { class: 'bare', text: ' · '+ t( 'clients.bare' ) } ) ] : ( client.company === '' ? el( 'span', { class: 'muted', text: '–' } ) : client.company ) ,
						listMarks( 'client', client ) ),
					el( 'td', { text: client.name } ),
					el( 'td', {}, client.city, client.country !== 'DE' ? el( 'span', { class: 'muted', text: ' ('+ client.country+ ')' } ) : null ),
					extra.map( function( column ) { return columnTd( column, client ) } ),
					el( 'td', {}, clientBadge( client ) ) );
			} ) );

		const table = el( 'div', { class: 'card wrap-x' }, el( 'table', { class: 'tbl' },
			el( 'thead', {}, el( 'tr', {}, heading( 'number', t( 'column.number' ) ), heading( 'company', t( 'column.company' ) ), heading( 'person', t( 'column.person' ) ), heading( 'city', t( 'column.city' ) ), extra.map( columnTh ), heading( 'active', t( 'column.state' ) ) ) ),
			body ) );

		reveal();

		state.main.appendChild( head( t( 'clients.title' ), el( 'span', { class: 'sub', text: t( 'clients.count', rows.length, state.people.length ) } ), el( 'span', { class: 'grow' } ),
			search( t( 'clients.search' ), filter.q, { input: function( event ) {
				filter.q = event.target.value;
				filter.page = 1;
				keepSearch( drawClients, true );
			} } ), state.wiping === true
				? [ el( 'button', { type : 'button', class : 'btn primary', id : 'new-client', disabled : true, 'aria-describedby' : 'new-client-why', title : t( 'clients.new-wiping' ) , text : t( 'clients.new' ) } ), el( 'span', { class : 'state-note', id : 'new-client-why', text : t( 'clients.new-wiping' ) } ) ]
				: link( '#client-new', t( 'clients.new' ), { class: 'btn primary', id: 'new-client' } ) ) );
		append( state.main, [ state.wiping === true ? pendingWipe( state.view ) : null, state.erasing.map( pendingDelete ), cardList( table, 'clients', [ 'id', 'who', 'sub', 'city' ].concat( extra.map( columnRole ), [ 'state' ] ), filter, again ), pagerOf( filter, pages, again ) ] );
	};
	// --- The card of a client -------------------------------------------------------------------------------------------------

	const clientScreen = function( where, mine ) {

		loading();

		Promise.all( [ api( 'crm-clients/get', { id : String( where.id ) } ), api( 'crm-projects/list', { client : String( where.id ) } ) ] ).then( function( answers ) {

			if( state.view !== mine )
				return;

			// The projects are a tab of their own: where they cannot be read the card has the others
			if( answers[0].ok !== true ) {
				loadFailed( answers[0], 'error.load', function() { core.route() } );
				return;
			}

			const projects = answers[1].ok === true && answers[1].body.missing !== true ? answers[1].body : null;

			state.tab = where.tab;

			loadColumns( 'client-project', projects === null ? [] : projects.projects ).then( function() {

				if( state.view !== mine )
					return;

				drawClient( { client : answers[0].body.client, projects : projects === null ? null : projects.projects, shared : projects !== null && Array.isArray( projects.shared ) ? projects.shared : [], editing : false, box : null, master : null } );
			} );
		} );
	};
	/**
	 *	The master data of the card: the facts of the client, or - after Edit master data - the form that changes them
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const masterCard = function( view ) {

		const client = view.client;

		if( view.editing === false ) {
			unsaver( 'master' );
			return el( 'div', { class: 'card pad', id: 'master' }, el( 'h3', { text: t( 'card.master' ) } ), kv( [
				[ t( 'client.company' ), client.company ],
				[ t( 'client.name' ), client.name ],
				[ t( 'client.email' ), mailtoOf( client.email ) === '' ? client.email : link( mailtoOf( client.email ), client.email, { id : 'client-mail' } ) ],
				[ t( 'card.phone' ), telOf( client.phone ) === '' ? client.phone : link( telOf( client.phone ), client.phone, { id : 'client-tel' } ) ],
				[ t( 'client.address' ), stacked( addressOf( client ) ) ],
				[ t( 'client.vat' ), client.vat_id ],
				[ t( 'card.business' ), client.business === true ? t( 'card.business-yes' ) : t( 'card.business-no' ) ],
				[ t( 'card.buyer-reference' ), client.buyer_reference ],
				[ t( 'card.since' ), dmy( client.created ) ],
			].concat( cardFacts( 'client', view ) ) ) );
		}

		const form = formOf();
		const rows = CLIENT_FIELDS.map( function( name ) { return { name : name, label : t( 'clientfield.'+ name ), control : clientControl( name, client[name] ) } } ).concat( [ businessRow( client.business === true ) ] );
		const save = el( 'button', { type: 'submit', class: 'btn sm primary', id: 'client-save', title: t( 'save.shortcut' ), text: t( 'form.save' ) } );

		const kit = kitOf( view.mine );
		const parts = formParts( 'client-master', kit, form );

		const box = el( 'form', { class: 'card pad', id: 'master', novalidate: 'novalidate', on: { submit: function( event ) {

			event.preventDefault();
			save.disabled = true;

			api( 'crm-clients/save', Object.assign( clientPayload( client, coreValues( form ) ), formValues( 'client-master', kit ) ) ).then( function( answer ) {

				save.disabled = false;

				if( state.view !== view.mine )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'error.client-save' );
					return;
				}

				view.client = answer.body.client;
				view.editing = false;
				formDone( 'client-master', kit, answer );
				toast( t( 'card.saved' ) );
				drawClient( view );
			} );
		} } },
		el( 'h3', { text: t( 'card.master' ) } ), formList( rows, form ), parts,
		el( 'div', { class: 'actions top' }, save, el( 'button', { type: 'button', class: 'btn sm', id: 'client-cancel', text: t( 'form.cancel' ), on: { click: function() {
			view.editing = false;
			state.draft = null;
			view.master.replaceWith( view.master = masterCard( view ) );
		} } } ) ),
		el( 'datalist', { id: 'countries' }, [ 'DE', 'AT', 'CH' ].map( function( code ) { return el( 'option', { value: code } ) } ) ) );

		submitter( 'master', box, save, function() { return form.dirty === true } );

		return box;
	};
	/**
	 *	What a save of a client posts: every field of it, since the model takes a field that is not posted for empty - the notes among them
	 *
	 *	@param		{Object}	client
	 *	@param		{Object}	[over]					Fields that take the place of the client's
	 *
	 *	@return		{Object}
	 */
	const clientPayload = function( client, over ) {

		const data = { id : String( client.id ) };

		CLIENT_FIELDS.concat( [ 'notes' ] ).forEach( function( name ) { data[name] = client[name] } );
		data.business = client.business === true;

		return Object.assign( data, over || {} );
	};
	/**
	 *	The projects of the client, the newest first, the done ones greyed; New project starts one for the client
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const tabClientProjects = function( view ) {

		const client = view.client;

		if( view.projects === null )
			return note( '', sentenceWith( t( 'projects.missing' ), 'projects-missing' ) );

		const extra = listColumns( 'client-project' );

		return el( 'div', { id: 'client-projects' },
			state.perms.elements === true && client.active === true ? el( 'div', { class: 'actions top' }, link( '#projects-new-k'+ client.id, t( 'projects.new' ), { class: 'btn primary', id: 'client-project-new' } ) ) : null,
			cardList( el( 'div', { class: 'card wrap-x' }, el( 'table', { class: 'tbl' },
				el( 'thead', {}, el( 'tr', {}, el( 'th', { text: t( 'projects.column-project' ) } ), el( 'th', { text: t( 'projects.column-started' ) } ), extra.map( columnTh ), el( 'th', { text: t( 'column.state' ) } ) ) ),
				el( 'tbody', {}, view.projects.length === 0
					? el( 'tr', { class: 'plain' }, el( 'td', { colspan: String( 3 + extra.length ), class: 'muted', text: t( 'projects.client-empty' ) } ) )
					: view.projects.map( function( project ) {
						return el( 'tr', { dataset: { id: project.id }, class: project.done === true ? 'dim' : null, on: { click: function() { wn.location.hash = '#project-'+ project.id } } },
							el( 'td', { class: 'wrap' }, projectNo( project.id ), ' ', projectLink( project ) ),
							el( 'td', { text: dmy( project.started ) } ),
							extra.map( function( column ) { return columnTd( column, project ) } ),
							el( 'td', {}, projectBadge( project ) ) );
					} ) ) ) ), 'client-projects', [ 'id', 'date:l' ].concat( extra.map( columnRole ), [ 'state' ] ) ),
			sharedProjects( view ) );
	};
	/**
	 *	The projects of other clients that are released to the client, under its own projects: the project (a link to its card), the client it belongs to and the day the release began. Nothing where there are none
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object|null}
	 */
	const sharedProjects = function( view ) {

		if( Array.isArray( view.shared ) === false || view.shared.length === 0 )
			return null;

		return el( 'div', { class: 'card pad shared-card', id: 'client-shared' }, el( 'h3', { text: t( 'projects.shared-title' ) } ),
			el( 'ul', { class: 'list-mini shared', id: 'client-shared-list' }, view.shared.map( function( one ) {
				return el( 'li', { class: 'shared-row', dataset: { id: one.id, owner: String( one.owner_id ) } },
					el( 'span', { class: 'shared-project' }, projectNo( one.id ), ' ', projectLink( one ), sourceMark( one.source ) === null ? null : [ ' ', sourceMark( one.source ) ] ),
					el( 'span', { class: 'shared-side' },
						el( 'span', { class: 'shared-owner' }, one.owner_id > 0 ? link( '#client-'+ one.owner_id, t( 'projects.shared-owner', one.owner_title !== '' ? one.owner_title : one.owner ) ) : t( 'projects.shared-owner', one.owner ) ),
						el( 'span', { class: 'r shared-since', text: t( 'shares.since', dmy( one.since ) ) } ) ) );
			} ) ) );
	};
	// --- Logbook -----------------------------------------------------------------------------------------------------
	// The tab logbook of the card of a client and of a project: what happened, with the time, the newest first. The entries are read when the tab opens (crm-logbook/list, 50 a page, Load older asks for the page after the last
	// one drawn), the line of the owner is written with crm-logbook/add. The sentence of an entry of the CRM is a word of the surface with the values of the entry; the sentence of a feature is drawn as it came, as text.

	/**
	 *	The sentence of an entry: the one its source wrote, else the word of the kind (the CRM's own) with the label of the project; the kind itself where there is no word
	 *
	 *	@param		{Object}	entry						As crm-logbook/list has it
	 *
	 *	@return		{string}
	 */
	const logbookSentence = function( entry ) {

		// An entry of a kind nobody here knows is shown as it is: the key of its kind and, beside it, the source that wrote it
		const raw = function() { return typeof entry.source === 'string' && entry.source !== '' && entry.source !== 'crm' ? t( 'logbook.unknown', String( entry.kind ), entry.source ) : String( entry.kind ) };

		if( typeof entry.text === 'string' && entry.text !== '' )
			return entry.text;

		// A kind a package entered: its script says the sentence, with the values of the entry
		const entered = logbookKind( entry );

		if( entered !== null ) {

			const said = guarded( function() { return entered.sentence( kitOf( state.view ), entry ) } );

			return said.ok === true && typeof said.value === 'string' && said.value !== '' ? said.value : raw();
		}

		const key = 'logbook.kind-'+ entry.kind;

		if( entry.source !== 'crm' || typeof state.texts[key] !== 'string' )
			return raw();

		const data = entry.data !== null && typeof entry.data === 'object' ? entry.data : {};
		const named = typeof data.label === 'string' && data.label !== '' ? data.label : t( 'logbook.unnamed' );

		// A release names the project and the client it is released to; one that ended because the client became the owner says so
		if( entry.kind === 'project-shared' || entry.kind === 'project-unshared' ) {

			const owned = entry.kind === 'project-unshared' && data.owner === true && typeof state.texts[key+ '-owner'] === 'string';

			return t( owned === true ? key+ '-owner' : key, named, typeof data.client === 'string' ? data.client : t( 'logbook.unnamed' ) );
		}

		return t( key, named );
	};
	/**
	 *	Who did it, as a person reads it: an address as it is, the two words for the ones that are no account
	 *
	 *	@param		{string}	by
	 *
	 *	@return		{string}									'' for none
	 */
	const logbookWho = function( by ) {

		if( by === 'system' || by === 'visitor' )
			return t( 'logbook.by-'+ by );

		return typeof by === 'string' && by !== '' ? by : '';
	};
	/**
	 *	One row of the logbook: the time, the sentence and, under it, who did it and where to go - what a package links, and the project (in the card of a client; as a link while it is there)
	 *
	 *	@param		{Object}	entry
	 *	@param		{boolean}	withProject			The card of a client names the project of an entry
	 *
	 *	@return		{Object}
	 */
	const logbookRow = function( entry, withProject ) {

		const who = logbookWho( entry.by );
		const entered = logbookKind( entry );
		const going = entered === null || entered.link === null ? { ok : false } : guarded( function() { return entered.link( kitOf( state.view ), entry ) } );
		const document = going.ok === true && going.value !== null && typeof going.value === 'object' && typeof going.value.text === 'string' && going.value.text !== '' && ADDRESS.test( String( going.value.href ) ) === true ? link( going.value.href, going.value.text, { class: 'logbook-link' } ) : null;
		const project = withProject === true && entry.project !== '' ? ( entry.project_known === true
			? link( '#project-'+ entry.project, entry.project_label, { class: 'logbook-link logbook-project' } )
			: el( 'span', { class: 'logbook-project', text: entry.project_label } ) ) : null;
		const meta = [ entry.source === 'crm' ? null : el( 'span', { class: 'logbook-source', title: t( 'logbook.source' ), text: entry.source } ), who === '' ? null : el( 'span', { class: 'logbook-by', text: t( 'logbook.by', who ) } ), document, project ].filter( function( one ) { return one !== null } );

		return el( 'li', { class: 'logbook-row', tabIndex: -1, dataset: { id: String( entry.id ), kind: entry.kind, source: entry.source } },
			el( 'time', { class: 'logbook-at', datetime: entry.at, text: stamp( entry.at ) } ),
			el( 'div', { class: 'logbook-main' },
				el( 'span', { class: 'logbook-text', text: logbookSentence( entry ) } ),
				meta.length === 0 ? null : el( 'span', { class: 'logbook-meta' }, meta.flatMap( function( one, index ) { return index === 0 ? [ one ] : [ ' · ', one ] } ) ) ) );
	};
	/**
	 *	The tab of the logbook of a client or a project, read when it opens
	 *
	 *	@param		{Object}	scope						client (the id) or project (the id)
	 *
	 *	@return		{Object}
	 */
	const logbookTab = function( scope ) {

		const mine = state.view;
		const withProject = scope.client !== undefined;
		const post = function( action, data ) { return api( action, Object.assign( {}, scope, data ) ) };
		const field = el( 'input', { type: 'text', class: 'logbook-field', id: 'logbook-new-text', maxlength: '500', autocomplete: 'off' } );
		const label = el( 'label', { class: 'logbook-label', for: 'logbook-new-text', text: t( 'logbook.new' ) } );
		const add = el( 'button', { type: 'submit', class: 'btn primary', id: 'logbook-add', text: t( 'logbook.add' ) } );
		const problem = el( 'p', { class: 'state-note bad', id: 'logbook-new-error', role: 'alert', hidden: true } );
		const form = el( 'form', { class: 'logbook-new', id: 'logbook-new', novalidate: 'novalidate' }, label, el( 'div', { class: 'logbook-line' }, field, add ) );
		const list = el( 'ul', { class: 'logbook', id: 'logbook' } );
		const status = el( 'p', { class: 'state-note', id: 'logbook-state', role: 'status' } );
		const more = el( 'button', { type: 'button', class: 'btn', id: 'logbook-more', text: t( 'logbook.more' ), hidden: true } );
		const retry = el( 'button', { type: 'button', class: 'btn', id: 'logbook-retry', text: t( 'logbook.retry' ), hidden: true } );
		const box = el( 'div', { class: 'card pad logbook-card', id: 'logbook-card' }, el( 'h3', { text: t( 'logbook.title' ) } ), form, problem, list, status, el( 'div', { class: 'actions' }, more, retry ) );
		let last = null;
		let busy = false;
		let writing = false;

		// What the tab says besides the list: loading, nothing yet, or why the list could not be read
		const say = function( kind, text ) {

			status.textContent = text;
			status.classList.toggle( 'bad', kind === 'bad' );
			status.setAttribute( 'role', kind === 'bad' ? 'alert' : 'status' );
			status.hidden = text === '';
		};

		const empty = function() { say( list.children.length === 0 ? 'note' : '', list.children.length === 0 ? t( 'logbook.empty' ) : '' ) };

		const load = function() {

			if( busy === true )
				return;

			busy = true;
			more.disabled = true;
			retry.hidden = true;
			say( 'note', t( 'page.loading' ) );

			post( 'crm-logbook/list', last === null ? {} : { before : String( last ) } ).then( function( answer ) {

				busy = false;

				if( state.view !== mine )
					return;

				more.disabled = false;

				if( answer.ok !== true ) {
					say( 'bad', errorText( answer, 'logbook.error' ) );
					retry.hidden = false;
					return;
				}

				const entries = answer.body.entries;
				const rows = entries.map( function( entry ) { return logbookRow( entry, withProject ) } );

				rows.forEach( function( row ) { list.appendChild( row ) } );

				if( entries.length > 0 )
					last = entries[entries.length - 1].id;

				more.hidden = answer.body.more !== true;
				empty();

				// A keyboard that pressed Load older goes on at the first of the new rows
				if( rows.length > 0 && rows.length < list.children.length )
					rows[0].focus();
			} );
		};

		const write = function() {

			const text = field.value.trim();

			if( writing === true )
				return;

			// Nothing typed is said, not passed over: the field stays and the sentence is there for a screen reader
			if( text === '' ) {
				problem.textContent = t( 'logbook.nothing' );
				problem.hidden = false;
				return;
			}

			writing = true;
			add.disabled = true;
			problem.hidden = true;

			post( 'crm-logbook/add', { text : text } ).then( function( answer ) {

				writing = false;
				add.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					problem.textContent = errorText( answer, 'error.request' );
					problem.hidden = false;
					return;
				}

				list.insertBefore( logbookRow( answer.body.entry, withProject ), list.firstChild );
				field.value = '';
				field.focus();
				empty();
			} );
		};

		form.addEventListener( 'submit', function( event ) { event.preventDefault(); write() } );
		field.addEventListener( 'input', function() { problem.hidden = true } );
		more.addEventListener( 'click', load );
		retry.addEventListener( 'click', load );
		load();

		return box;
	};
	const tabClientLogbook = function( view ) { return logbookTab( { client : String( view.client.id ) } ) };
	const tabProjectLogbook = function( view ) { return logbookTab( { project : view.project.id } ) };
	const tabClientNotes = function( view ) {

		const area = el( 'textarea', { id: 'notes', rows: 5, maxlength: '2000', class: 'area', 'aria-label': t( 'client-notes.title' ), value: view.client.notes } );
		let running = false;

		const save = function() {

			running = true;

			call( 'crm-clients/save', clientPayload( view.client, { notes : area.value } ), 'error.client-save' ).then( function( answer ) {

				running = false;

				if( answer.ok === true ) {
					view.client = answer.body.client;
					toast( t( 'notes.done' ) );
				}
			} );
		};

		const box = card( t( 'client-notes.title' ), area, el( 'div', { class: 'actions top' }, el( 'button', { type: 'button', class: 'btn sm', id: 'notes-save', title: t( 'save.shortcut' ), text: t( 'notes.save' ), on: { click: save } } ) ) );

		saver( 'tab', box, save, function() { return area.value !== String( view.client.notes ?? '' ) }, function() { return running } );

		return box;
	};
	/**
	 *	Switch the client on or off: a package that keeps something of the client follows, or the client stays as it was
	 *
	 *	@param		{Object}	view
	 *	@param		{boolean}	active
	 *
	 *	@return		{Promise}
	 */
	const switchClient = function( view, active ) {

		return step( 'crm-clients/active', { id : String( view.client.id ), active : active }, 'error.client-active' ).then( function( answer ) {

			if( answer.ok !== true || state.view !== view.mine )
				return answer;

			view.client = answer.body.client;
			view.editing = false;
			toast( active === true ? t( 'card.activated' ) : t( 'card.deactivated' ) );
			drawClient( view );

			return answer;
		} );
	};
	// --- Delete a client -----------------------------------------------------------------------------------------------

	/**
	 *	The list of what goes with a client, from the preview of the server: the client itself, every count above nought, in the order the server names them, and what a package holds. A line is a li with the key of what it counts in
	 *	data-line; a count nobody has a word for is a line with its key and its number
	 *
	 *	@param		{Object}	preview
	 *
	 *	@return		{Object}
	 */
	const deleteList = function( preview ) {

		const lines = [ el( 'li', { dataset : { line : 'base' }, text : t( 'client-delete.line-base' ) } ) ];

		Object.keys( preview.counts ).forEach( function( key ) {

			if( preview.counts[key] <= 0 )
				return;

			// A count the server names that the page has no word for is drawn as it is, the key and the number: the dialog says what the server takes
			lines.push( el( 'li', { dataset : { line : key, count : String( preview.counts[key] ) }, text : has( 'client-delete.line-'+ key ) === true ? t( 'client-delete.line-'+ key, preview.counts[key] ) : t( 'client-delete.line-other', key, preview.counts[key] ) } ) );
		} );

		preview.features.forEach( function( feature ) {
			if( feature.count > 0 )
				lines.push( el( 'li', { dataset : { line : 'feature', feature : feature.key, count : String( feature.count ) }, text : t( 'client-delete.line-feature', feature.label, feature.count ) } ) );
		} );

		return el( 'div', {}, el( 'h3', { class : 'wipe-head', text : t( 'client-delete.goes' ) } ), el( 'ul', { class : 'wipe-list', id : 'delete-list' }, lines ) );
	};
	/**
	 *	Why a client is not deleted, in words: a project another client names, a package that holds it back, the reasons a package entered, a missing permission. No button goes with it
	 *
	 *	@param		{Object}	preview
	 *
	 *	@return		{Object}
	 */
	const deleteReasons = function( preview ) {

		const reasons = [];

		preview.reasons.forEach( function( reason ) {

			if( reason === 'shared' )
				reasons.push( el( 'p', { dataset : { reason : 'shared' }, text : t( 'client-delete.blocked-shared', preview.shared.map( projectLabel ).join( ', ' ) ) } ) );
			else if( reason === 'held' )
				preview.features.filter( function( feature ) { return feature.blocks === true } ).forEach( function( feature ) {
					reasons.push( el( 'p', { dataset : { reason : 'held', feature : feature.key }, text : t( 'client-delete.blocked-held', feature.label, feature.why ) } ) );
				} );
			else
				reasons.push( el( 'p', { dataset : { reason : reason }, text : has( 'client-delete.blocked-'+ reason ) === true ? t( 'client-delete.blocked-'+ reason ) : t( 'client-delete.blocked-other', reason ) } ) );
		} );

		if( preview.permitted !== true )
			reasons.push( el( 'p', { dataset : { reason : 'perm' }, text : t( 'client-delete.blocked-perm' ) } ) );

		return el( 'div', { class : 'delete-blocked', id : 'delete-blocked' }, reasons );
	};
	/**
	 *	The dialog that deletes: the number is typed to open the button (the server compares it as well). A refusal of the server is said in the dialog, which stays; a step that stopped after the database
	 *	is said and the next press finishes it (by the number, as the client is not there any more). With the step done the toast says so and the page goes back to the list
	 *
	 *	@param		{Object}	spec						number, title, text, body, yes, post (a function of the typed number and of whether the run is a resume that answers the data of the post)
	 *
	 *	@return		{Promise}
	 */
	const deleteDialog = function( spec ) {

		let resume = spec.resume === true;

		// The steps of a package that was removed hold the deletion up: each can be given up, one by one and by a question that names it. What is given up is sent with the next press
		const skipped = [];
		let offered = [];
		const skipBox = el( 'div', { class : 'delete-skip', id : 'delete-skip' } );
		const showSkips = function() {

			clear( skipBox );

			if( skipped.length > 0 )
				skipBox.appendChild( el( 'p', { class : 'state-note', dataset : { skipped : skipped.join( ',' ) }, text : t( 'client-delete.skipped', skipped.join( ', ' ) ) } ) );

			offered.filter( function( key ) { return skipped.includes( key ) === false } ).forEach( function( key ) {
				skipBox.appendChild( el( 'p', {}, el( 'button', { type : 'button', class : 'btn', dataset : { skip : key }, text : t( 'client-delete.skip-button', key ), on : { click : function() {
					ask( { title : t( 'client-delete.skip-title', key ), text : t( 'client-delete.skip-text', key ), yes : t( 'client-delete.skip-yes' ), danger : true } ).then( function( yes ) {
						if( yes === true ) {
							skipped.push( key );
							showSkips();
						}
					} );
				} } } ) ) );
			} );
		};

		return dialog( {
			title : spec.title, text : spec.text, wide : true, danger : true, yes : spec.yes, body : [ spec.body ?? null, skipBox ],
			fields : [ { name : 'confirm', label : t( 'client-delete.type', spec.number ), maxlength : 20, autocomplete : 'off' } ],
			unlock : { field : 'confirm', word : spec.number },
			run : function( values ) {

				return api( 'crm-clients/delete', Object.assign( spec.post( values.confirm, resume ), skipped.length > 0 ? { skip : skipped.slice() } : {} ) ).then( function( answer ) {

					// What a package held may be partly deleted after a refusal of a step: the next screen reads them from the server
					forgetClients();

					if( answer.ok !== true ) {

						// A package the deletion was begun with is gone: the server names the steps that may be given up (only where every package is removed and none has data left: crm_package_gone; none where a package is only off or its data are still there)
						offered = answer.status === 409 && typeof answer.body === 'object' && answer.body !== null && answer.body.code === 'crm_package_gone' && Array.isArray( answer.body.skippable ) === true ? answer.body.skippable.filter( function( key ) { return typeof key === 'string' } ) : [];
						showSkips();

						// The database step went through: the client is not to be found by its id any more, the next press names the number
						if( answer.status === 500 && typeof answer.body === 'object' && answer.body !== null && /^crm_clients_delete_/.test( String( answer.body.code ) ) === true )
							resume = true;

						return errorText( answer, 'error.client-delete' );
					}

					toast( Array.isArray( answer.body.skipped ) === true && answer.body.skipped.length > 0 ? t( 'client-delete.done-skipped', answer.body.number, answer.body.skipped.join( ', ' ) ) : t( 'client-delete.done', answer.body.number ) );
					refresh();

					if( wn.location.hash === '#clients' )
						core.route();
					else
						wn.location.hash = '#clients';

					return null;
				} );
			},
		} );
	};
	/**
	 *	Delete a client: the server says what hangs on it and whether it may go; a client that may not gets the reasons and a button that closes, one that may gets the list and the field for the number
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Promise}
	 */
	const deleteClient = function( view ) {

		return api( 'crm-clients/delete-preview', { id : String( view.client.id ) } ).then( function( answer ) {

			if( answer.ok !== true )
				return failed( answer, 'error.client-delete-preview' );

			const preview = answer.body.preview;

			if( preview.deletable !== true || preview.permitted !== true )
				return dialog( { title : t( 'client-delete.blocked-title', preview.number ), body : deleteReasons( preview ), close : true } );

			return deleteDialog( {
				number : preview.number, title : t( 'client-delete.title', preview.number ), text : t( 'client-delete.text' ), body : deleteList( preview ), yes : t( 'client-delete.yes' ),
				post : function( confirm, resume ) { return resume === true ? { number : preview.number, confirm : confirm } : { id : String( view.client.id ), confirm : confirm } },
			} );
		} );
	};
	/**
	 *	The note in the list of the clients for a deletion that stopped after the database: the client is gone from the list, a rest is not. The button finishes it
	 *
	 *	@param		{string}	number
	 *
	 *	@return		{Object}
	 */
	const pendingDelete = function( number ) {

		return el( 'div', { class : 'card pad delete-pending', dataset : { number : number } }, el( 'p', { text : t( 'client-delete.pending', number ) } ),
			el( 'div', { class : 'actions' }, el( 'button', { type : 'button', class : 'btn danger', text : t( 'client-delete.finish' ), on : { click : function() {
				deleteDialog( { number : number, title : t( 'client-delete.finish-title', number ), text : t( 'client-delete.finish-text' ), yes : t( 'client-delete.finish' ), resume : true, post : function( confirm ) { return { number : number, confirm : confirm } } } );
			} } } ) ) );
	};
	/**
	 *	The run of the test phase that is not finished, above the list of the clients: what it means and the button that finishes it - the dialog of the test phase, which reads the steps of the packages from the settings
	 *
	 *	@param		{number}	mine
	 *
	 *	@return		{Object}
	 */
	const pendingWipe = function( mine ) {

		return el( 'div', { class : 'card pad wipe-pending', role : 'status' }, el( 'p', { text : t( 'wipe.pending' ) } ),
			el( 'div', { class : 'actions' }, el( 'button', { type : 'button', class : 'btn danger', id : 'wipe-finish', text : t( 'wipe.finish' ), on : { click : function() {
				api( 'crm/settings-get' ).then( function( answer ) {
					wipeDialog( mine, answer.ok === true && answer.body.wipe !== undefined && Array.isArray( answer.body.wipe.steps ) === true ? answer.body.wipe.steps : [] );
				} );
			} } } ) ) );
	};
	/**
	 *	The menu ⋯ of the head of a client: the items the packages add, Edit and the switch of the client
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const clientMenu = function( view ) {

		const client = view.client;
		const items = headItems( 'client', view );

		items.push(
			{ label : t( 'card.edit' ), run : function() {
				if( view.editing === true )
					return;
				view.editing = true;
				view.master.replaceWith( view.master = masterCard( view ) );
			} },
			'sep',
			client.active === true ? { label : t( 'card.deactivate' ), danger : true, run : function() { switchClient( view, false ) } } : { label : t( 'card.activate' ), run : function() { switchClient( view, true ) } },
			{ label : t( 'client-delete.menu' ), danger : true, run : function() { deleteClient( view ) } } );

		return menu( { label : '⋯', aria : t( 'menu.more' ), items : items } );
	};
	const drawClient = function( view ) {

		const client = view.client;

		view.mine = state.view;
		state.draft = null;
		reveal();

		state.main.appendChild( el( 'div', { class: 'crumb' }, link( '#clients', t( 'clients.title' ) ), ' › '+ client.number ) );

		state.main.appendChild( head( el( 'h1', { text: clientTitle( client ) } ), clientBadge( client ), el( 'span', { class: 'sub num', text: client.number } ), headParts( 'client', 'marks', view ),
			el( 'span', { class: 'grow' } ),
			headParts( 'client', 'actions', view ),
			view.menu = clientMenu( view ) ) );

		if( client.active === false )
			state.main.appendChild( note( '', t( 'card.inactive-note' ) ) );

		headParts( 'client', 'notes', view ).forEach( function( node ) { state.main.appendChild( node ) } );

		view.master = masterCard( view );
		state.main.appendChild( el( 'div', { class: 'cols-3 gap' }, view.master ) );

		const body = el( 'div', { id: 'tab' } );

		// A tab is the body under the strip: the card above it - a form that is open in it - is not drawn again
		const tabs = cardStrip( 'client', view, body, function() { unsaver( 'tab' ) } );

		state.main.appendChild( tabs.strip );
		state.main.appendChild( body );
		tabs.show( tabs.start );
	};
	// --- New client ---------------------------------------------------------------------------------------------------

	const clientNewScreen = function( where, mine ) {

		const form = formOf();
		const kit = kitOf( mine, form );
		const rows = CLIENT_FIELDS.map( function( name ) { return { name : name, label : t( 'clientfield.'+ name ), control : clientControl( name, name === 'country' ? 'DE' : '' ) } } ).concat( [ businessRow( false ) ] );
		const create = el( 'button', { type: 'submit', class: 'btn primary', id: 'client-create', title: t( 'save.shortcut' ), text: t( 'newclient.create' ) } );
		const master = formList( rows, form );
		const parts = formParts( 'client-new', kit, form );

		const submit = function( event ) {

			event.preventDefault();
			create.disabled = true;

			api( 'crm-clients/save', Object.assign( coreValues( form ), formValues( 'client-new', kit ) ) ).then( function( answer ) {

				create.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'error.client-save' );
					return;
				}

				const made = answer.body.client;

				form.dirty = false;
				state.draft = null;

				toast( t( 'newclient.done', made.number ) );
				formDone( 'client-new', kit, answer );
				refresh();
				wn.location.hash = '#client-'+ made.id;
			} );
		};

		reveal();

		const box = el( 'form', { id: 'new-client-form', novalidate: 'novalidate', on: { submit: submit } },
			head( t( 'newclient.title' ), el( 'span', { class: 'grow' } ), link( '#clients', t( 'form.cancel' ), { class: 'btn', id: 'client-abort' } ), create ),
			el( 'p', { class: 'state-note', id: 'newclient-optional', text: t( 'newclient.optional' ) } ),
			el( 'div', { class: 'cols' },
				parts,
				el( 'div', { class: 'card pad', id: 'newclient-master' }, el( 'h3', { text: t( 'newclient.master' ) } ), el( 'p', { class: 'state-note', id: 'newclient-master-text', text: t( 'newclient.master-text' ) } ), master ) ),
			el( 'datalist', { id: 'countries' }, [ 'DE', 'AT', 'CH' ].map( function( code ) { return el( 'option', { value: code } ) } ) ) );

		state.main.appendChild( el( 'div', { class: 'crumb' }, link( '#clients', t( 'clients.title' ) ), ' › '+ t( 'newclient.title' ) ) );
		state.main.appendChild( box );
		submitter( 'new', box, create );

		form.controls.company.focus();
	};
	// --- Values -------------------------------------------------------------------------------------------------------
	// The values the owner sets freely are elements of types that one screen offers: the tabs are the types (the answer of the Elements panel to elements/types, cut by the
	// endpoint to the types it lets the screen edit), and the list and the form are drawn from the definition of the type, its model, as the Elements panel of the workbench
	// draws them. Everything is written through the panel's own actions: elements/save (one language per post, the native one first, with the values that are the same in all
	// of them) and elements/delete.

	/**
	 *	@param		{string}	locale					de_DE
	 *
	 *	@return		{string}									de
	 */
	const languageOf = function( locale ) {
		return String( locale ).split( '_' )[0];
	};
	/**
	 *	The languages of the site, the native one first: the order of the cards of the editor and of its posts
	 *
	 *	@param		{Array}		locales
	 *	@param		{string}	native
	 *
	 *	@return		{Array}
	 */
	const nativeFirst = function( locales, native ) {
		return ( locales.includes( native ) === true ? [ native ] : [] ).concat( locales.filter( function( locale ) { return locale !== native } ) );
	};
	/**
	 *	@param		{string}	locale					de_DE
	 *
	 *	@return		{string}									The name of the language in the language of the page where the browser knows it, else the code in capitals
	 */
	const languageName = function( locale ) {

		const code = languageOf( locale );

		try {
			return new wn.Intl.DisplayNames( [ state.locale.replace( '_', '-' ) ], { type : 'language' } ).of( code ) || code.toUpperCase();
		}
		catch( e ) {
			return code.toUpperCase();
		}
	};
	// The fields that put the elements of a type in order, the first the type has
	const ORDER_KEYS = [ 'order', 'sort' ];
	// What a name of an element looks like, and how long it is at most: the same the endpoint asks of the key of a phase
	const VALUE_KEY = /^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/;
	const VALUE_KEY_MAX = 30;
	/**
	 *	The name of an element from its title: lower case words, umlauts spelled out, a dash between them
	 *
	 *	@param		{string}	title
	 *
	 *	@return		{string}									mueller-ltd; '' where nothing is left of the title
	 */
	const valueKey = function( title ) {

		const umlauts = { '\u00e4' : 'ae', '\u00f6' : 'oe', '\u00fc' : 'ue', '\u00df' : 'ss' };
		const slug = String( title ).toLowerCase().replace( /[\u00e4\u00f6\u00fc\u00df]/g, function( char ) { return umlauts[char] } ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).replace( /^[0-9-]+/, '' );

		return slug.slice( 0, VALUE_KEY_MAX ).replace( /-+$/, '' );
	};
	/**
	 *	How the form draws a field of the model: a line, a text area, a choice, a whole number, a decimal number, a switch or a day; none for a field the form does not offer (rich text,
	 *	images, lists, references)
	 *
	 *	@param		{Object}	field						The definition in the model
	 *
	 *	@return		{string}									line, text, select, integer, number, switch, date; '' for none
	 */
	const fieldKind = function( field ) {

		if( field.type === 'string' )
			return field.html === true ? '' : ( Array.isArray( field.options ) === true ? 'select' : ( field.breaks === true || field.inputsize > 1 ? 'text' : 'line' ) );

		return { integer : 'integer', double : 'number', boolean : 'switch', date : 'date' }[field.type] || '';
	};
	/**
	 *	@param		{Object}	type						As elements/types has it
	 *
	 *	@return		{Array}										The keys of the fields the form offers, in the order of the model
	 */
	const offered = function( type ) {
		return Object.keys( type.model ).filter( function( key ) { return fieldKind( type.model[key] ) !== '' && ( type.hidden || [] ).includes( key ) === false } );
	};
	/**
	 *	What the account may do with a type, as the Elements panel reads the rights of the answer: a right the answer does not name is a right
	 *
	 *	@param		{Object}	type
	 *	@param		{string}	what						insert, delete or update
	 *	@param		{string}	[key]						The field, for update
	 *
	 *	@return		{boolean}
	 */
	const may = function( type, what, key ) {

		const rights = type.rights !== undefined && type.rights !== null ? type.rights : {};

		return what === 'update' ? ( rights.update || {} )[key] !== false : rights[what] !== false;
	};
	/**
	 *	@param		{Object}	type
	 *
	 *	@return		{string}									The field that puts the elements in order; '' for a type that has none
	 */
	const orderKeyOf = function( type ) {
		return ORDER_KEYS.find( function( key ) { return type.model[key] !== undefined && type.model[key].type === 'integer' } ) || '';
	};
	/**
	 *	@param		{Object}	type
	 *	@param		{string}	key
	 *
	 *	@return		{string}									The word of the field: the label the type's model has for it in this language, else the key
	 */
	const labelOf = function( type, key ) {
		return type.model[key] !== undefined && typeof type.model[key].label === 'string' && type.model[key].label !== '' ? type.model[key].label : key;
	};
	/**
	 *	The word of a type on its tab
	 *
	 *	@param		{Object}	type
	 *
	 *	@return		{string}
	 */
	const typeWord = function( type ) {
		return typeof state.texts['values.type-'+ type.type] === 'string' ? t( 'values.type-'+ type.type ) : ( type.title || type.type );
	};
	/**
	 *	The word of a choice of a field: a choice a package has a word for says it (values.option-<field>-<value>), the others show their value
	 *
	 *	@param		{string}	key
	 *	@param		{string}	value
	 *
	 *	@return		{string}
	 */
	const optionWord = function( key, value ) {

		if( value === '' )
			return '–';

		// The choices a package has words for
		return typeof state.texts['values.option-'+ key+ '-'+ value] === 'string' ? t( 'values.option-'+ key+ '-'+ value ) : value;
	};
	/**
	 *	A value as the form and the list write it
	 *
	 *	@param		{Object}	field						The definition in the model
	 *	@param		{*}				value
	 *
	 *	@return		{string}									A number with the comma of the language; '' for none
	 */
	const valueText = function( field, value ) {

		if( value === null || value === undefined )
			return '';

		// A decimal is written with two decimals in the way of the language, the groups of thousands for money, the rate without its sign
		if( field.type === 'double' )
			return field.suffix === '%' ? wn.Nino.crm.format.rate( Math.round( value * 100 ) ).replace( / %$/, '' ) : wn.Nino.crm.format.money( Math.round( value * 100 ), '' );

		return String( value );
	};
	/**
	 *	What was typed into a field, as the value the panel is posted
	 *
	 *	@param		{string}	kind
	 *	@param		{string}	text
	 *	@param		{Object}	[field]					The definition in the model, for its sign
	 *
	 *	@return		{Object}									value (null for nothing typed) or bad
	 */
	const valueOfText = function( kind, text, field ) {

		const typed = String( text ).trim();

		if( typed === '' )
			return { value : null };

		if( kind === 'integer' )
			return /^-?[0-9]{1,9}$/.test( typed ) === true ? { value : Number( typed ) } : { bad : true };

		if( kind === 'number' ) {

			// As a price is read: 1.500 is neither a thousand nor one and a half, at most two decimals, not above the highest price; money and rates are not below nothing, a rate not above 100
			const cents = wn.Nino.crm.format.parseMoney( typed );
			const unsigned = field !== undefined && ( field.suffix === '€' || field.suffix === '%' );

			if( cents === null || ( unsigned === true && cents < 0 ) || ( field !== undefined && field.suffix === '%' && cents > 10000 ) )
				return { bad : true };

			return { value : cents / 100 };
		}

		return { value : typed };
	};
	/**
	 *	What the editor starts from
	 *
	 *	@param		{Object}			type
	 *	@param		{Object|null}	got					The answer of elements/get; null for a new element
	 *	@param		{Array}				locales
	 *	@param		{Object}			[defaults]	What a new element starts with: field => value
	 *
	 *	@return		{Object}										shared (key => the text of the control, a switch as true or false) and words (locale => key => text)
	 */
	const valueDraftOf = function( type, got, locales, defaults ) {

		const draft = { shared : {}, words : {} };
		const global = got === null ? {} : got.global || {};

		locales.forEach( function( locale ) { draft.words[locale] = {} } );

		offered( type ).forEach( function( key ) {

			const field = type.model[key];
			const kind = fieldKind( field );

			if( field.locale === true ) {
				locales.forEach( function( locale ) {
					const one = got === null ? null : ( got.locales || {} )[locale];
					draft.words[locale][key] = one !== null && one !== undefined ? valueText( field, one[key] ) : '';
				} );
				return;
			}

			const start = got === null && defaults !== undefined && defaults[key] !== undefined ? defaults[key] : global[key];

			// A choice with a default starts at it, so that the control never stands on a value that is none of its options
			draft.shared[key] = kind === 'switch' ? start === true : valueText( field, kind === 'select' && start === undefined ? field.default : start );
		} );

		return draft;
	};
	/**
	 *	What a save of the editor posts, in the order it posts it, or what keeps it back. The panel saves one language per post; the first post is the native language and carries the
	 *	values that are the same in all of them (and makes a new element), then each other language whose words were changed. A new element posts what it has; a change posts only
	 *	what was changed, so that an account that may change some fields is not refused for the ones it left alone and a value another place left out is not made up. A language
	 *	that has words needs the fields the model requires of it
	 *
	 *	@param		{Object}	type
	 *	@param		{Object}	read						What the controls hold, as valueDraftOf() has it
	 *	@param		{Object}	base						What the editor started from
	 *	@param		{Array}		locales					The native language first
	 *	@param		{boolean}	isNew						Whether the element is made by this save
	 *
	 *	@return		{Object}									problems ([ { name, sentence } ]: the control and its sentence) and posts ([ { locale, fields } ])
	 */
	const valuePosts = function( type, read, base, locales, isNew ) {

		const problems = [];
		const posts = [];
		const keys = offered( type ).filter( function( key ) { return may( type, 'update', key ) === true } );
		const wordKeys = keys.filter( function( key ) { return type.model[key].locale === true } );
		const sharedKeys = keys.filter( function( key ) { return type.model[key].locale !== true } );
		const fieldsOf = {};

		const typed = function( key, text, name ) {

			const field = type.model[key];
			const kind = fieldKind( field );
			const got = kind === 'switch' ? { value : text === true } : valueOfText( kind, text, field );

			if( got.bad === true )
				problems.push( { name : name, sentence : t( kind === 'integer' ? 'values.err-integer' : 'values.err-number', labelOf( type, key ) ) } );

			return got;
		};

		locales.forEach( function( locale, index ) {

			const has = wordKeys.some( function( key ) { return String( read.words[locale][key] ).trim() !== '' } );

			// A language of a new element that has no words is not made
			const changed = wordKeys.filter( function( key ) { return ( isNew === true && ( index === 0 || has === true ) ) || ( isNew === false && String( read.words[locale][key] ).trim() !== String( base.words[locale][key] ).trim() ) } );
			fieldsOf[locale] = {};

			// A language that has words, or the native one of a new element, needs what the model requires; a required field emptied cannot be saved
			wordKeys.forEach( function( key ) {

				const empty = String( read.words[locale][key] ).trim() === '';

				if( type.model[key].required === true && empty === true && ( ( isNew === true && index === 0 ) || ( changed.length > 0 && ( has === true || String( base.words[locale][key] ).trim() !== '' ) ) ) )
					problems.push( { name : key+ '.'+ locale, sentence : t( index === 0 ? 'values.err-required' : 'values.err-required-lang', labelOf( type, key ) ) } );
			} );

			changed.forEach( function( key ) {
				const got = typed( key, read.words[locale][key], key+ '.'+ locale );

				if( got.bad !== true && ( got.value !== null || type.model[key].required !== true ) )
					fieldsOf[locale][key] = got.value === null ? '' : got.value;
			} );
		} );

		const shared = {};

		sharedKeys.forEach( function( key ) {

			const kind = fieldKind( type.model[key] );
			const same = kind === 'switch' ? read.shared[key] === base.shared[key] : String( read.shared[key] ).trim() === String( base.shared[key] ).trim();

			if( isNew === false && same === true )
				return;

			const got = typed( key, read.shared[key], key );

			if( got.bad === true )
				return;

			if( got.value === null ) {

				if( type.model[key].required === true )
					problems.push( { name : key, sentence : t( 'values.err-required', labelOf( type, key ) ) } );

				// A text that was emptied is posted empty; a number cannot be taken away, and nothing typed into a field of a new element is nothing posted
				else if( isNew === false && [ 'line', 'text', 'select' ].includes( kind ) === true )
					shared[key] = '';

				return;
			}

			shared[key] = got.value;
		} );

		if( problems.length > 0 )
			return { problems : problems, posts : [] };

		locales.forEach( function( locale, index ) {

			const fields = Object.assign( {}, fieldsOf[locale] );

			if( index === 0 )
				Object.assign( fields, shared );

			if( ( index === 0 && isNew === true ) || Object.keys( fields ).length > 0 )
				posts.push( { locale : locale, fields : fields } );
		} );

		return { problems : [], posts : posts };
	};
	/**
	 *	@param		{string}	type						The name of a type; '' for the screen alone
	 *	@param		{string}	[tail]					neu, or e/ and the key of an element
	 *
	 *	@return		{string}									The address of the screen
	 */
	const valuesHash = function( type, tail ) {
		return '#values'+ ( type === '' ? '' : '/'+ type )+ ( tail === undefined ? '' : '/'+ tail );
	};
	/**
	 *	The screen of the values: the tabs of the types, the list of the elements of the chosen type, or the form of one
	 *
	 *	@param		{Object}	where						type ('' for the first), view (list, new or edit) and uri of an element that is there; legacy for an old address, which is put right
	 *	@param		{number}	mine						The view that draws
	 *
	 *	@return		{void}
	 */
	const valuesScreen = function( where, mine ) {

		if( state.perms.elements !== true ) {
			reveal();
			state.main.appendChild( head( t( 'values.title' ) ) );
			state.main.appendChild( note( 'bad', t( 'values.forbidden' ) ) );
			return;
		}

		loading();

		api( 'elements/types' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			const types = answer.body.types;
			const missing = answer.body.missing || [];
			const type = where.type === '' ? types[0] : types.find( function( one ) { return one.type === where.type } );
			const meta = { locales : nativeFirst( answer.body.locales || [], answer.body.native || '' ), native : answer.body.native || '', types : types, missing : missing };

			if( type === undefined ) {
				reveal();
				drawValuesHead( meta, null );
				state.main.appendChild( note( '', types.length > 0 && missing.includes( where.type ) === false ? [ t( 'values.unknown' ), ' ' ] : ( types.length === 0 ? sentenceWith( t( 'values.no-types' ), 'values-no-types' ) : sentenceWith( t( 'values.missing', typeWord( { type : where.type } ) ), 'values-missing' ) ) ) );
				return;
			}

			if( where.view === 'list' ) {
				valuesList( meta, type, mine );
				return;
			}

			// A type this account may not add to has no form for a new element
			if( where.view === 'new' && may( type, 'insert' ) === false ) {
				reveal();
				drawValuesHead( meta, type );
				state.main.appendChild( note( 'bad', el( 'span', { text: t( 'values.forbidden-new' ) } ), ' ', link( valuesHash( type.type ), t( 'values.back' ) ) ) );
				return;
			}

			// A new element of a type with a field for the order starts behind the last: the list is read for the highest number
			const orderKey = orderKeyOf( type );

			Promise.all( [ where.view === 'edit' ? api( 'elements/get', { type : type.type, uri : where.uri } ) : null, where.view === 'new' && orderKey !== '' ? api( 'elements/list', { type : type.type, locale : meta.native } ) : null ] ).then( function( both ) {

				const got = both[0];

				if( state.view !== mine )
					return;

				if( got !== null && got.ok !== true && got.status !== 404 ) {
					loadFailed( got, 'error.load', function() { core.route() } );
					return;
				}

				if( both[1] !== null && both[1].ok !== true ) {
					loadFailed( both[1], 'error.load', function() { core.route() } );
					return;
				}

				const start = Object.assign( {}, type.defaults || {} );

				if( both[1] !== null )
					start[orderKey] = both[1].body.elements.reduce( function( top, one ) { return Math.max( top, Number( one.values[orderKey] ) || 0 ) }, 0 ) + 10;

				reveal();

				if( got !== null && got.ok !== true ) {
					drawValuesHead( meta, type );
					state.main.appendChild( note( 'bad', el( 'span', { text: t( 'values.gone' ) } ), ' ', link( valuesHash( type.type ), t( 'values.back' ) ) ) );
					return;
				}

				drawValue( meta, type, got === null ? null : got.body, where.uri, mine, start );
			} );
		} );
	};
	/**
	 *	The head of the screen and the strip of the types
	 *
	 *	@param		{Object}				meta
	 *	@param		{Object|null}		chosen			The type that is open
	 *
	 *	@return		{void}
	 */
	const drawValuesHead = function( meta, chosen ) {

		state.main.appendChild( head( t( 'values.title' ), el( 'span', { class: 'sub', text: t( 'values.sub' ) } ) ) );

		if( meta.types.length === 0 )
			return;

		state.main.appendChild( el( 'div', { class: 'tabs top', role: 'tablist', id: 'values-types', 'aria-label': t( 'values.types' ) }, meta.types.map( function( type ) {
			return el( 'a', { href: valuesHash( type.type ), role: 'tab', class: chosen !== null && chosen.type === type.type ? 'on' : '', 'aria-selected': chosen !== null && chosen.type === type.type ? 'true' : 'false', dataset: { type: type.type }, text: typeWord( type ) } );
		} ) ) );
	};
	/**
	 *	The text a cell of the list shows for the value of a field
	 *
	 *	@param		{Object}	type
	 *	@param		{string}	key
	 *	@param		{*}				value
	 *
	 *	@return		{string}
	 */
	const valueCell = function( type, key, value ) {

		const field = type.model[key];

		if( value === null || value === undefined || value === '' )
			return '';

		if( field.type === 'boolean' )
			return t( value === true ? 'values.yes' : 'values.no' );

		if( field.type === 'string' && Array.isArray( field.options ) === true )
			return optionWord( key, String( value ) );

		return valueText( field, value )+ ( typeof field.suffix === 'string' && field.suffix !== '' ? ' '+ field.suffix : '' );
	};
	/**
	 *	The list of the elements of a type: a search, the way to a new one and to the panel, and a row for each with its name, its key and the values a cell can show, in the order of the
	 *	type; a type that has a field for the order has the arrows that move an element up and down
	 *
	 *	@param		{Object}	meta
	 *	@param		{Object}	type
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const valuesList = function( meta, type, mine ) {

		api( 'elements/list', { type : type.type, locale : meta.native } ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			state.wbody = { meta : meta, type : type, elements : answer.body.elements, columns : answer.body.columns };
			state.wtype = type.type;
			drawValuesList();
		} );
	};
	const drawValuesList = function() {

		const body = state.wbody;
		const type = body.type;
		const filter = state.wfilter;
		const orderKey = orderKeyOf( type );
		const query = filter.q.trim().toLowerCase();

		// A type with no title - the panel then names a row by its key - is named by its first text of the language, and that text is no column of its own
		const wordKeys = offered( type ).filter( function( key ) { return type.model[key].locale === true } );
		const nameKey = type.model.title !== undefined || wordKeys.length === 0 ? '' : wordKeys[0];
		const named = function( one ) { return nameKey !== '' && typeof one.values[nameKey] === 'string' && one.values[nameKey] !== '' ? one.values[nameKey] : one.label };
		const columns = body.columns.filter( function( key ) {
			const field = type.model[key];
			return key !== 'title' && key !== nameKey && field !== undefined && fieldKind( field ) !== '' && fieldKind( field ) !== 'text' && ( type.hidden || [] ).includes( key ) === false;
		} );
		const order = function( one ) { return orderKey !== '' && one.values[orderKey] !== null && one.values[orderKey] !== undefined ? Number( one.values[orderKey] ) : 0 };
		const ordered = body.elements.slice();

		if( orderKey !== '' )
			ordered.sort( function( a, b ) { return order( a ) - order( b ) || a.uri.localeCompare( b.uri ) } );

		const rows = ordered.filter( function( one ) {
			return query === '' || ( named( one )+ ' '+ one.uri+ ' '+ columns.map( function( key ) { return valueCell( type, key, one.values[key] ) } ).join( ' ' ) ).toLowerCase().includes( query );
		} );

		const pages = Math.max( 1, Math.ceil( rows.length / PAGE_SIZE ) );
		filter.page = Math.min( Math.max( 1, filter.page ), pages );
		const shown = rows.slice( ( filter.page - 1 ) * PAGE_SIZE, filter.page * PAGE_SIZE );
		const again = function() { drawValuesList() };
		const panel = state.links.workbench+ '#elements/'+ type.type;

		reveal();
		drawValuesHead( body.meta, type );

		state.main.appendChild( el( 'div', { class: 'toolbar' }, el( 'span', { class: 'sub', text: t( 'values.count', body.elements.length ) } ), el( 'span', { class: 'grow' } ),
			search( t( 'values.search' ), filter.q, { input: function( event ) {
				filter.q = event.target.value;
				filter.page = 1;
				keepSearch( again, true );
			} } ),
			panel === undefined ? null : link( panel, t( 'values.panel' ), { class: 'btn', id: 'values-panel', target : '_blank', rel : 'noopener' } ),
			may( type, 'insert' ) === false ? null : link( valuesHash( type.type, 'new' ), t( 'values.new' ), { class: 'btn primary', id: 'values-new' } ) ) );

		// What a type says of itself above its list is what the server sends for it: its hint, in the language of the installation
		if( typeof type.hint === 'string' && type.hint !== '' )
			state.main.appendChild( el( 'p', { class: 'state-note', id: 'values-hint', text: type.hint } ) );

		const move = function( index, step ) {

			const target = index + step;

			if( target < 0 || target >= ordered.length )
				return;

			const moved = { uri : ordered[index].uri, which : step < 0 ? 'up' : 'down' };

			// The elements as they stand - all of them, whatever the search shows - are numbered 10, 20, 30 ... in the new order; those whose number is that already are not posted
			const next = ordered.slice();
			next.splice( target, 0, next.splice( index, 1 )[0] );

			const changes = next.map( function( one, at ) { return { uri : one.uri, value : ( at + 1 ) * 10, was : order( one ) } } ).filter( function( one ) { return one.value !== one.was } );
			const send = function( at ) {

				if( at >= changes.length ) {
					toast( t( 'values.moved' ) );
					state.wfocus = moved;
					core.route();
					return;
				}

				const fields = {};
				fields[orderKey] = changes[at].value;

				call( 'elements/save', { type : type.type, uri : changes[at].uri, locale : body.meta.native, isNew : false, fields : fields }, 'values.error-move' ).then( function( saved ) {
					if( saved.ok === true )
						send( at + 1 );
					else {
						state.wfocus = moved;
						core.route();
					}
				} );
			};

			send( 0 );
		};

		const arrows = function( one ) {

			const at = ordered.indexOf( one );

			return el( 'td', { class: 'r act' }, el( 'button', { type: 'button', class: 'btn sm', dataset: { move: 'up' }, 'aria-label': t( 'values.up', named( one ) ), disabled: at === 0, text: '↑', on: { click: function( event ) { event.stopPropagation(); move( at, -1 ) } } } ),
				el( 'button', { type: 'button', class: 'btn sm', dataset: { move: 'down' }, 'aria-label': t( 'values.down', named( one ) ), disabled: at === ordered.length - 1, text: '↓', on: { click: function( event ) { event.stopPropagation(); move( at, 1 ) } } } ) );
		};

		const heads = [ t( 'values.column-title' ), t( 'values.column-key' ) ].concat( columns.map( function( key ) { return labelOf( type, key ) } ) );
		const names = [ 'id', 'sub' ].concat( columns.map( function( key ) { return 'v-'+ key+ ':l' } ) );

		if( orderKey !== '' )
			heads.push( '' );

		state.main.appendChild( cardList( el( 'div', { class: 'card wrap-x' }, el( 'table', { class: 'tbl', id: 'values' },
			el( 'thead', {}, el( 'tr', {}, heads.map( function( word ) { return el( 'th', { scope: 'col', text: word } ) } ) ) ),
			el( 'tbody', {}, shown.length === 0
				? el( 'tr', { class: 'plain' }, el( 'td', { colspan: String( heads.length ), class: 'muted', text: t( body.elements.length > 0 ? 'values.none' : 'values.empty' ) } ) )
				: shown.map( function( one ) {
					return el( 'tr', { dataset: { id: one.uri }, on: { click: function() { wn.location.hash = valuesHash( type.type, 'e/'+ one.uri ) } } },
						el( 'td', { class: 'wrap' }, link( valuesHash( type.type, 'e/'+ one.uri ), named( one ) ) ),
						el( 'td', { class: 'num', text: one.uri } ),
						columns.map( function( key ) { return el( 'td', { class: type.model[key].type === 'string' ? '' : 'r num', text: valueCell( type, key, one.values[key] ) } ) } ),
						orderKey === '' ? null : arrows( one ) );
				} ) ) ) ), 'values', names.concat( orderKey === '' ? [] : [ 'act' ] ) ) );

		append( state.main, [ pagerOf( filter, pages, again ) ] );

		// After an arrow the list is drawn anew: the focus goes to the arrow of the element that moved (to its other arrow where it stands at an end now)
		if( state.wfocus !== undefined && state.wfocus !== null ) {

			const row = state.main.querySelector( 'tr[data-id="'+ state.wfocus.uri+ '"]' );
			const arrow = function( which ) { return row === null ? null : row.querySelector( 'button[data-move="'+ which+ '"]' ) };
			const target = arrow( state.wfocus.which ) !== null && arrow( state.wfocus.which ).disabled === false ? arrow( state.wfocus.which ) : arrow( state.wfocus.which === 'up' ? 'down' : 'up' );

			if( target !== null )
				target.focus();

			state.wfocus = null;
		}
	};
	/**
	 *	A control for a field of the model
	 *
	 *	@param		{string}	key
	 *	@param		{Object}	field
	 *	@param		{string|boolean}	value
	 *	@param		{string}	id
	 *
	 *	@return		{Object}
	 */
	const valueControl = function( key, field, value, id ) {

		const kind = fieldKind( field );

		if( kind === 'switch' )
			return el( 'input', { id : id, type : 'checkbox', role : 'switch', checked : value === true } );

		if( kind === 'select' )
			return el( 'select', { id : id, value : String( value ) }, field.options.map( function( option ) { return el( 'option', { value : String( option ), text : optionWord( key, String( option ) ) } ) } ) );

		if( kind === 'text' )
			return el( 'textarea', { id : id, rows : field.inputsize > 1 ? field.inputsize : 4, maxlength : String( field.maxlength || 2000 ), value : String( value ) } );

		return el( 'input', { id : id, type : kind === 'date' ? 'date' : 'text', value : String( value ), maxlength : kind === 'line' ? String( field.maxlength || 200 ) : null, autocomplete : 'off', inputmode : kind === 'integer' ? 'numeric' : ( kind === 'number' ? 'decimal' : null ), class : kind === 'line' || kind === 'date' ? null : 'short' } );
	};
	/**
	 *	The form of an element: a card for each language of the site (the native one first) with the fields the model keeps per language, one for the key and the values that are the same
	 *	in all of them, Save, and the menu with Delete
	 *
	 *	@param		{Object}				meta
	 *	@param		{Object}				type
	 *	@param		{Object|null}		got					The answer of elements/get; null for a new element
	 *	@param		{string}				[given]			The key of an element that is there
	 *	@param		{number}				mine
	 *	@param		{Object}				[start]			What a new element starts with: field => value
	 *
	 *	@return		{void}
	 */
	const drawValue = function( meta, type, got, given, mine, start ) {

		const locales = meta.locales.length > 0 ? meta.locales : [ meta.native ];
		const draft = valueDraftOf( type, got, locales, start );
		const form = formOf();
		const keys = offered( type );
		const wordKeys = keys.filter( function( key ) { return type.model[key].locale === true } );
		const sharedKeys = keys.filter( function( key ) { return type.model[key].locale !== true } );
		const native = locales[0];
		const nameKey = wordKeys.includes( 'title' ) ? 'title' : ( wordKeys[0] || '' );
		const heading = given === undefined ? t( 'values.new-title' ) : ( nameKey !== '' && draft.words[native][nameKey] !== '' ? draft.words[native][nameKey] : given );
		let uri = given;
		let saving = false;
		let keyTouched = false;

		const cardOf = function( locale, index ) {

			const rows = wordKeys.map( function( key ) {
				const field = type.model[key];
				const row = { name : key+ '.'+ locale, label : labelOf( type, key ), control : valueControl( key, field, draft.words[locale][key], 'f-'+ key+ '-'+ locale ) };

				if( index > 0 && key === nameKey && field.required === true )
					row.hint = t( 'values.hint-lang' );
				else if( field.type === 'double' )
					row.hint = t( 'values.hint-number' );

				return row;
			} );

			return el( 'div', { class: 'card pad', id: 'value-lang-'+ locale, dataset: { locale: locale } }, el( 'h3', { text: index === 0 ? t( 'values.card-native', languageName( locale ) ) : languageName( locale ) } ), formList( rows, form ) );
		};

		const keyRow = given !== undefined || type.autoincrement === true ? [] : [ { name : 'uri', label : t( 'values.f-key' ), hint : t( 'values.hint-key' ), control : el( 'input', { id : 'f-uri', type : 'text', value : '', maxlength : String( VALUE_KEY_MAX ), autocomplete : 'off', class : 'short' } ) } ];
		const rows = keyRow.concat( sharedKeys.map( function( key ) {
			const field = type.model[key];
			const suffix = typeof field.suffix === 'string' && field.suffix !== '' ? field.suffix : '';
			return { name : key, label : labelOf( type, key )+ ( suffix === '' ? '' : ' ('+ suffix+ ')' ), control : valueControl( key, field, draft.shared[key], 'f-'+ key ), hint : field.type === 'double' ? t( 'values.hint-number' ) : undefined };
		} ) );
		const shared = rows.length === 0 ? null : el( 'div', { class: 'card pad', id: 'value-shared' }, el( 'h3', { text: t( 'values.card-shared' ) } ), formList( rows, form ),
			type.autoincrement === true && given === undefined ? el( 'p', { class: 'state-note', text: t( 'values.hint-numbered', type.nextUri ) } ) : null );

		const cards = wordKeys.length === 0 ? [] : locales.map( cardOf );

		// A field this account may not write is drawn and locked, and the save leaves it out
		keys.filter( function( key ) { return may( type, 'update', key ) === false } ).forEach( function( key ) {
			Object.keys( form.controls ).filter( function( name ) { return name === key || name.indexOf( key+ '.' ) === 0 } ).forEach( function( name ) { form.controls[name].disabled = true } );
		} );

		if( form.controls.uri !== undefined && nameKey !== '' )
			form.controls[nameKey+ '.'+ native].addEventListener( 'input', function() {
				if( keyTouched === false )
					form.controls.uri.value = valueKey( form.controls[nameKey+ '.'+ native].value );
			} );

		if( form.controls.uri !== undefined )
			form.controls.uri.addEventListener( 'input', function() { keyTouched = true } );

		const uriNote = el( 'span', { class: 'sub num', id: 'value-uri', text: uri === undefined ? '' : '/'+ type.type+ '/'+ uri } );
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: 'value-save', title: t( 'save.shortcut' ), text: t( 'form.save' ) } );

		const mark = function( name, sentence ) {

			if( form.controls[name] === undefined )
				return false;

			form.controls[name].classList.add( 'err' );
			form.controls[name].setAttribute( 'aria-invalid', 'true' );
			form.notes[name].textContent = sentence;

			return true;
		};

		const read = function() {

			const values = { shared : {}, words : {} };

			locales.forEach( function( locale ) {
				values.words[locale] = {};
				wordKeys.forEach( function( key ) { values.words[locale][key] = form.controls[key+ '.'+ locale].value } );
			} );

			sharedKeys.forEach( function( key ) { values.shared[key] = fieldKind( type.model[key] ) === 'switch' ? form.controls[key].checked : form.controls[key].value } );

			return values;
		};

		// The question and the refusal leave the focus nowhere: it goes back to the menu that asked
		const focusMenu = function() {

			const menuBox = state.view === mine ? state.main.querySelector( '.menu' ) : null;
			const button = menuBox === null ? null : menuBox.querySelector( 'button' );

			if( button !== null )
				button.focus();
		};

		const remove = function() {

			ask( { title : t( 'values.delete' ), text : t( 'values.delete-text', heading ), yes : t( 'values.delete' ), danger : true } ).then( function( yes ) {

				if( yes !== true || state.view !== mine ) {
					focusMenu();
					return;
				}

				call( 'elements/delete', { type : type.type, uri : uri }, 'values.error-delete' ).then( function( answer ) {
					if( answer.ok === true && state.view === mine ) {
						toast( t( 'values.deleted' ) );
						wn.location.hash = valuesHash( type.type );
					}
					else
						focusMenu();
				} );
			} );
		};

		const submit = function( event ) {

			event.preventDefault();

			if( saving === true )
				return;

			Object.keys( form.controls ).forEach( function( name ) {
				form.controls[name].classList.remove( 'err' );
				form.controls[name].removeAttribute( 'aria-invalid' );
				form.notes[name].textContent = '';
			} );

			const typed = read();
			const made = valuePosts( type, typed, draft, locales, uri === undefined );
			let key = uri;

			// The key of a new element: what was typed, else what the title makes of it; a numbered type takes none
			if( uri === undefined && type.autoincrement !== true ) {

				key = form.controls.uri.value.trim() !== '' ? form.controls.uri.value.trim() : ( nameKey === '' ? '' : valueKey( typed.words[native][nameKey] ) );

				if( made.problems.length === 0 && ( VALUE_KEY.test( key ) === false || key.length > VALUE_KEY_MAX ) )
					made.problems.push( { name : 'uri', sentence : t( 'values.err-key' ) } );
			}

			if( made.problems.length > 0 ) {

				made.problems.forEach( function( problem ) { mark( problem.name, problem.sentence ) } );
				form.controls[made.problems[0].name].focus();
				toast( made.problems[0].sentence, true );
				return;
			}

			// Nothing was changed: nothing is posted, and the element is read again
			if( made.posts.length === 0 ) {
				core.route();
				return;
			}

			// While the posts go out, a way out of the screen waits for them (see route())
			let release = function() {};

			state.busy = new Promise( function( resolve ) { release = resolve } );
			saving = true;
			save.disabled = true;

			const over = function() {
				saving = false;
				save.disabled = false;
				state.busy = null;
				release();
			};

			// Where a refusal belongs: the panel names the posted key, the control is the one of the language that was being saved. An answer that comes after the screen was left is said in the
			// toast all the same: the element may be half saved, and the person is to know
			const refuse = function( answer, locale ) {

				const body = answer.body !== null && typeof answer.body === 'object' ? answer.body : null;

				// The keys of the model are the panel's words: the sentence names the fields as the screen does
				if( body !== null && body.code === 'elements_fields_not_allowed' && Array.isArray( body.params ) === true )
					body.params = body.params.map( function( names ) {
						return String( names ).split( ', ' ).map( function( name ) { return type.model[name] === undefined ? name : labelOf( type, name ) } ).join( ', ' );
					} );

				if( body !== null && typeof body.field === 'string' && form.controls[body.field+ '.'+ locale] !== undefined )
					body.field = body.field+ '.'+ locale;

				refuseAt( form, answer, 'values.error-save' );
			};

			const send = function( index ) {

				const one = made.posts[index];
				const first = index === 0 && uri === undefined;

				api( 'elements/save', { type : type.type, uri : first === true ? ( type.autoincrement === true ? '' : key ) : uri, locale : one.locale, isNew : first, fields : one.fields } ).then( function( answer ) {

					if( answer.ok !== true ) {
						refuse( answer, one.locale );
						over();
						return;
					}

					// The element is there from the first post on: a later language that is refused is changed by the next save, and the element is not made twice
					if( first === true )
						uri = type.autoincrement === true && answer.body !== null && typeof answer.body.element === 'object' ? String( answer.body.element['.uri'] ).split( '/' ).pop() : key;

					if( index === 0 )
						uriNote.textContent = '/'+ type.type+ '/'+ uri;

					// What went out is what the element holds now: a later save of the same form posts what was changed since
					Object.keys( one.fields ).forEach( function( name ) {
						if( wordKeys.includes( name ) === true )
							draft.words[one.locale][name] = typed.words[one.locale][name];
						else if( sharedKeys.includes( name ) === true )
							draft.shared[name] = typed.shared[name];
					} );

					if( index + 1 < made.posts.length ) {
						send( index + 1 );
						return;
					}

					over();

					if( state.view !== mine )
						return;

					toast( t( 'values.saved' ) );
					state.wfocus = 'save';

					if( given === undefined )
						wn.location.hash = valuesHash( type.type, 'e/'+ uri );
					else
						core.route();
				} );
			};

			send( 0 );
		};

		state.main.appendChild( el( 'div', { class: 'crumb' }, link( valuesHash( type.type ), typeWord( type ) ), ' › ', heading ) );
		const box = el( 'form', { id: 'value-form', novalidate: 'novalidate', on: { submit: submit } },
			head( heading, uriNote, el( 'span', { class: 'grow' } ), given === undefined || may( type, 'delete' ) === false ? null : menu( { label : '⋯', aria : t( 'values.more' ), items : [ { label : t( 'values.delete' ), danger : true, run : remove } ] } ), save ),
			el( 'div', { class: 'tpl-grid' }, el( 'div', { class: 'stack' }, cards ), shared ) );

		state.main.appendChild( box );
		submitter( 'value', box, save );

		// After a save the form is drawn anew (or the element opens): the focus is on Save, not on the page
		if( state.wfocus === 'save' ) {
			state.wfocus = null;
			save.focus();
		}
	};
	// --- Projects ----------------------------------------------------------------------------------------------------
	// A project is an element of the type crm-projects. The surface drives the Elements panel's own actions for it (elements/save makes one - the uri of a
	// new one is the number the type gives it, so none is posted - and changes it, elements/delete removes it); crm-projects/list and crm-projects/get read it
	// with the figures the packages add.

	const PROJECT_TYPE = 'crm-projects';
	/**
	 *	The projects the list shows: the done ones only where the switch is on, those in the phase the filter names (all phases for none), then the search over the title, the client and its number
	 *
	 *	@param		{Array}		projects				As crm-projects/list has them, the newest first
	 *	@param		{Object}	filter					q, all (the done ones too), client (the id of a client; '' for every one), phase ('' for every phase)
	 *
	 *	@return		{Array}
	 */
	const visibleProjects = function( projects, filter ) {

		const query = filter.q.trim().toLowerCase();

		return projects.filter( function( project ) {
			return ( filter.all === true || project.done === false ) && ( filter.client === undefined || filter.client === '' || String( project.client_id ) === filter.client ) && ( !filter.phase || project.phase === filter.phase ) && ( query === '' || ( project.title+ ' '+ projectLabel( project.id )+ ' '+ project.id+ ' '+ project.client_title+ ' '+ project.client ).toLowerCase().includes( query ) );
		} );
	};
	/**
	 *	The pill of the phase a project is in
	 *
	 *	@param		{Object}	project
	 *
	 *	@return		{Object|null}							null for a project that has none
	 */
	const phaseBadge = function( project ) {
		return phaseWord( project.phase ) !== '' ? el( 'span', { class: 'badge b-phase', dataset: { phase: project.phase }, text: phaseWord( project.phase ) } ) : null;
	};
	/**
	 *	The word of a phase: the title the element of the phase has in the native language, else the word of the module for the four it brings
	 *
	 *	@param		{string}	key						The key of the phase (state.phases has the phases of the last list or card that was read)
	 *
	 *	@return		{string}									'' for a key that is none of the phases
	 */
	const phaseWord = function( key ) {

		const phase = state.phases.find( function( one ) { return one.key === key } );

		if( phase === undefined )
			return '';

		return phase.title !== '' ? phase.title : ( typeof state.texts['phase.'+ key] === 'string' ? t( 'phase.'+ key ) : key );
	};
	/**
	 *	The options of a select of phases
	 *
	 *	@return		{Array}
	 */
	const phaseOptions = function() {
		return state.phases.map( function( phase ) { return el( 'option', { value: phase.key, text: phaseWord( phase.key ) } ) } );
	};
	/**
	 *	The select in the head of a project's card that puts the project into a phase: it saves at once
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const phaseSelect = function( view ) {

		const project = view.project;
		const select = el( 'select', { id: 'project-phase', value: phaseWord( project.phase ) !== '' ? project.phase : '', on: { change: function() {

			select.disabled = true;

			call( 'crm-projects/phase', { id : project.id, phase : select.value }, 'projects.error-phase' ).then( function( answer ) {

				select.disabled = false;

				if( answer.ok !== true ) {
					select.value = phaseWord( project.phase ) !== '' ? project.phase : '';
					return;
				}

				toast( t( 'projects.phase-saved' ) );

				if( state.view === view.mine )
					reloadProject( view );
			} );
		} } }, el( 'option', { value: '', text: t( 'projects.phase-none' ) } ), phaseOptions() );

		return el( 'label', { class: 'phase', for: 'project-phase' }, el( 'span', { text: t( 'projects.phase' ) } ), select );
	};
	const projectBadge = function( project ) {

		if( project.done === true )
			return badge( 'muted', t( 'projects.done' ) );

		return badge( 'info', t( 'projects.running' ) );
	};
	const projectName = function( project ) {
		return project.title !== '' ? project.title : projectLabel( project.id );
	};
	const projectLink = function( project ) {
		return link( '#project-'+ project.id, projectName( project ) );
	};
	/**
	 *	Save fields of a project through the Elements panel's own action; a new one is posted without a uri and gets the next number of the type
	 *
	 *	@param		{string}	uri							'' for a new project
	 *	@param		{string}	native					The language the site is written in
	 *	@param		{Object}	fields
	 *
	 *	@return		{Promise}
	 */
	const projectSave = function( uri, native, fields ) {
		return api( 'elements/save', { type : PROJECT_TYPE, uri : uri, locale : native, isNew : uri === '', fields : fields } );
	};
	const projectsScreen = function( where, mine ) {

		loading();

		// The clients are the filter's options: a list that cannot be read leaves the filter out
		Promise.all( [ api( 'crm-projects/list' ), api( 'crm-clients/list' ) ] ).then( function( answers ) {

			if( state.view !== mine )
				return;

			if( answers[0].ok !== true ) {
				loadFailed( answers[0], 'error.load', function() { core.route() } );
				return;
			}

			state.jbody = answers[0].body;
			state.phases = answers[0].body.phases || [];
			state.jclients = answers[1].ok === true ? answers[1].body.clients : [];

			// The filter by client: one picker for as long as the screen is open, so that what is typed in it survives the list being drawn anew
			state.jpick = state.jclients.length === 0 ? null : clientField( { id: 'p-client', label: t( 'projects.client-filter' ), empty: t( 'projects.client-all' ), picked: function( one ) {
				state.jfilter.client = one === null ? '' : one.value;
				state.jfilter.page = 1;
				state.jpick.keep( drawProjects );
			} }, pickerClients( state.jclients ), state.jfilter.client );

			// A client the filter names that the list does not have filters nothing
			if( state.jpick !== null && state.jpick.value() === '' )
				state.jfilter.client = '';

			loadColumns( 'project', state.jbody.missing === true ? [] : state.jbody.projects ).then( function() {
				if( state.view === mine )
					drawProjects();
			} );
		} );
	};
	const drawProjects = function() {

		const body = state.jbody;
		const filter = state.jfilter;
		const edits = state.perms.elements === true;
		const rows = body.missing === true ? [] : visibleProjects( body.projects, filter );
		const pages = Math.max( 1, Math.ceil( rows.length / PAGE_SIZE ) );

		filter.page = Math.min( Math.max( 1, filter.page ), pages );

		const shown = rows.slice( ( filter.page - 1 ) * PAGE_SIZE, filter.page * PAGE_SIZE );
		const again = function() { drawProjects() };
		const extra = listColumns( 'project' );

		reveal();

		state.main.appendChild( head( t( 'projects.title' ), el( 'span', { class: 'sub', text: t( 'projects.count', rows.length, body.projects.length ) } ), el( 'span', { class: 'grow' } ),
			body.missing === true ? null : search( t( 'projects.search' ), filter.q, { input: function( event ) {
				filter.q = event.target.value;
				filter.page = 1;
				keepSearch( drawProjects, true );
			} } ),
			body.missing === true || state.jpick === null ? null : state.jpick.node,
			body.missing === true ? null : el( 'label', { class: 'sw', for: 'p-all' }, el( 'input', { type: 'checkbox', role: 'switch', id: 'p-all', checked: filter.all, on: { change: function( event ) {
				filter.all = event.target.checked;
				filter.page = 1;
				again();
			} } } ), el( 'span', { text: t( 'projects.show-done' ) } ) ),
			body.missing === true ? null : el( 'select', { id: 'p-phase', 'aria-label': t( 'projects.phase' ), value: filter.phase, on: { change: function( event ) {
				filter.phase = event.target.value;
				filter.page = 1;
				again();
			} } }, el( 'option', { value: '', text: t( 'projects.phase-all' ) } ), phaseOptions() ),
			edits === true && body.missing !== true ? link( '#projects-new', t( 'projects.new' ), { class: 'btn primary', id: 'projects-new' } ) : null ) );

		if( body.missing === true ) {
			state.main.appendChild( note( '', sentenceWith( t( 'projects.missing' ), 'projects-missing' ) ) );
			return;
		}

		append( state.main, [ cardList( el( 'div', { class: 'card wrap-x' }, el( 'table', { class: 'tbl', id: 'projects' },
			el( 'thead', {}, el( 'tr', {}, el( 'th', { scope: 'col', text: t( 'projects.column-project' ) } ), el( 'th', { scope: 'col', text: t( 'column.client' ) } ), el( 'th', { scope: 'col', text: t( 'projects.column-started' ) } ),
				extra.map( columnTh ), el( 'th', { scope: 'col', text: t( 'column.state' ) } ), el( 'th', { scope: 'col', text: t( 'projects.phase' ) } ) ) ),
			el( 'tbody', {}, shown.length === 0
				? el( 'tr', { class: 'plain' }, el( 'td', { colspan: String( 5 + extra.length ), class: 'muted', text: t( body.projects.length === 0 ? 'projects.empty' : 'projects.none' ) } ) )
				: shown.map( function( project ) {
					return el( 'tr', { dataset: { id: project.id }, class: project.done === true ? 'dim' : null, on: { click: function() { wn.location.hash = '#project-'+ project.id } } },
						el( 'td', { class: 'wrap' }, projectNo( project.id ), ' ', projectLink( project ) ),
						el( 'td', {}, project.client_id === 0 ? project.client : link( '#client-'+ project.client_id, project.client_title, { on: { click: function( event ) { event.stopPropagation() } } } ) ),
						el( 'td', { text: dmy( project.started ) } ),
						extra.map( function( column ) { return columnTd( column, project ) } ),
						el( 'td', {}, projectBadge( project ), listMarks( 'project', project ) ),
						el( 'td', {}, phaseBadge( project ) ) );
				} ) ) ) ), 'projects', [ 'id', 'who', 'date:l' ].concat( extra.map( columnRole ), [ 'state', 'phase' ] ) ) ] );
		append( state.main, [ pagerOf( filter, pages, again ), el( 'p', { class: 'state-note top', text: t( edits === true ? 'projects.note-edit' : 'projects.note' ) } ) ] );
	};
	/**
	 *	The screen of a new project: #projects-new, or #projects-new-k<id> with the client chosen
	 *
	 *	@param		{Object}	where						client (the id of the client), absent or '' for none
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const projectNewScreen = function( where, mine ) {

		if( state.perms.elements !== true ) {
			reveal();
			state.main.appendChild( head( t( 'projects.title' ) ) );
			state.main.appendChild( note( 'bad', t( 'projects.forbidden' ) ) );
			return;
		}

		loading();

		Promise.all( [ api( 'crm-clients/list' ), api( 'crm-projects/list', { open : true } ) ] ).then( function( answers ) {

			if( state.view !== mine )
				return;

			const failure = answers.find( function( answer ) { return answer.ok !== true } );

			if( failure !== undefined ) {
				loadFailed( failure, 'error.load', function() { core.route() } );
				return;
			}

			if( answers[1].body.missing === true ) {
				reveal();
				state.main.appendChild( head( t( 'projects.title' ) ) );
				state.main.appendChild( note( '', sentenceWith( t( 'projects.missing' ), 'projects-missing' ) ) );
				return;
			}

			drawProjectNew( answers[0].body.clients.filter( function( client ) { return client.active === true } ), answers[1].body, where.client || '', mine );
		} );
	};
	const drawProjectNew = function( clients, list, preset, mine ) {

		const form = formOf();
		const kit = kitOf( mine, form );
		const known = clients.some( function( client ) { return String( client.id ) === preset } );
		const clientPick = clientField( { id: 'f-client', placeholder: t( 'field.client-choose' ) }, pickerClients( clients ), known ? preset : '' );
		const create = el( 'button', { type: 'submit', class: 'btn primary', id: 'project-create', title: t( 'save.shortcut' ), text: t( 'projects.create' ) } );
		const rows = [
			{ name : 'title', label : t( 'projects.f-title' ), control : el( 'input', { id: 'f-title', type: 'text', maxlength: '200', autocomplete: 'off' } ) },
			{ name : 'client', label : t( 'projects.f-client' ), control : clientPick.input, node : clientPick.node, picker : clientPick },
			{ name : 'description', label : t( 'projects.f-description' ), control : el( 'textarea', { id: 'f-description', rows: 4, maxlength: '2000' } ), hint : t( 'form.optional' ) },
			{ name : 'started', label : t( 'projects.f-started' ), control : el( 'input', { id: 'f-started', type: 'date' } ), hint : t( 'form.optional' ) },
		];

		const parts = formParts( 'project-new', kit, form );

		let creating = false;

		const submit = function( event ) {

			event.preventDefault();

			if( creating === true )
				return;

			Object.keys( form.controls ).forEach( function( name ) {
				form.controls[name].classList.remove( 'err' );
				form.controls[name].removeAttribute( 'aria-invalid' );
				form.notes[name].textContent = '';
			} );

			const title = form.controls.title.value.trim();
			const client = clients.find( function( one ) { return String( one.id ) === clientPick.value() } );
			const problem = title === '' ? [ 'title', 'projects.err-title' ] : ( client === undefined ? [ 'client', 'projects.err-client' ] : null );

			if( problem !== null ) {
				form.controls[problem[0]].classList.add( 'err' );
				form.controls[problem[0]].setAttribute( 'aria-invalid', 'true' );
				form.notes[problem[0]].textContent = t( problem[1] );
				form.controls[problem[0]].focus();
				toast( t( problem[1] ), true );
				return;
			}

			const fields = { title : title, client : client.number };

			if( form.controls.description.value.trim() !== '' )
				fields.description = form.controls.description.value;

			if( form.controls.started.value !== '' )
				fields.started = form.controls.started.value;

			let release = function() {};

			// While the posts go out, a way out of the screen waits for them (see route())
			state.busy = new Promise( function( resolve ) { release = resolve } );
			creating = true;
			create.disabled = true;

			const over = function() {
				creating = false;
				create.disabled = false;
				state.busy = null;
				release();
			};

			// The values of the forms of the packages travel under packages, key by key: the server tells each package what was posted for it
			api( 'elements/save', Object.assign( { type : PROJECT_TYPE, uri : '', locale : list.native, isNew : true, fields : fields }, formValues( 'project-new', kit ) ) ).then( function( answer ) {

				over();

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'projects.error-save' );
					return;
				}

				const id = String( answer.body.element['.uri'] ).split( '/' ).pop();

				toast( t( 'projects.made', title ) );
				formDone( 'project-new', kit, answer );

				if( state.view === mine ) {
					form.dirty = false;
					state.draft = null;
					wn.location.hash = '#project-'+ id;
				}
			} );
		};

		reveal();

		const box = el( 'form', { id: 'project-form', novalidate: 'novalidate', on: { submit: submit } },
			head( t( 'projects.new-title' ), el( 'span', { class: 'grow' } ), link( '#projects', t( 'form.cancel' ), { class: 'btn', id: 'project-abort' } ), create ),
			el( 'div', { class: 'card pad' }, formList( rows, form ) ), parts );

		state.main.appendChild( el( 'div', { class: 'crumb' }, link( '#projects', t( 'projects.title' ) ), ' › '+ t( 'projects.new-title' ) ) );
		state.main.appendChild( box );
		submitter( 'new-project', box, create, function() { return form.dirty === true } );
		form.controls.title.focus();
	};
	// --- The card of a project ------------------------------------------------------------------------------------------------

	const projectScreen = function( where, mine ) {

		loading();

		api( 'crm-projects/get', { id : where.id } ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.status === 404 ) {
				reveal();
				state.main.appendChild( head( t( 'projects.title' ) ) );
				state.main.appendChild( note( answer.body !== null && answer.body.code === 'crm_projects_missing' ? '' : 'bad', el( 'span', { text: errorText( answer, 'projects.gone' ) } ), ' ', link( '#projects', t( 'projects.back' ) ) ) );
				return;
			}

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			state.tab = where.tab;
			state.phases = answer.body.phases || [];
			drawProject( Object.assign( {}, answer.body, { editing : false, info : null, mine : mine } ) );
		} );
	};
	/**
	 *	Read the project again and draw its card, on the tab that was open
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{void}
	 */
	const reloadProject = function( view ) {

		const mine = state.view;

		api( 'crm-projects/get', { id : view.project.id } ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				failed( answer, 'error.load' );
				return;
			}

			state.phases = answer.body.phases || [];
			drawProject( Object.assign( {}, answer.body, { editing : false, info : null, mine : mine } ) );
		} );
	};
	/**
	 *	The facts of the project, or - after Edit - the form that changes the title, the note and the day
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const projectCard = function( view ) {

		const project = view.project;

		if( view.editing === false ) {

			unsaver( 'project' );

			return el( 'div', { class: 'card pad', id: 'project-info' }, el( 'h3', { text: t( 'projects.card' ) } ), kv( [
				[ t( 'projects.f-number' ), projectLabel( project.id ) ],
				[ t( 'projects.f-client' ), view.client === null ? project.client : link( '#client-'+ view.client.id, view.client.title+ ' · '+ view.client.number ) ],
				[ t( 'projects.f-started' ), dmy( project.started ) ],
				[ t( 'projects.f-description' ), project.description === '' ? '' : stacked( project.description.split( '\n' ) ) ],
			].concat( cardFacts( 'project', view ) ) ) );
		}

		const form = formOf();
		const save = el( 'button', { type: 'submit', class: 'btn sm primary', id: 'project-save', title: t( 'save.shortcut' ), text: t( 'form.save' ) } );
		const rows = [
			{ name : 'title', label : t( 'projects.f-title' ), control : el( 'input', { id: 'f-title', type: 'text', value: project.title, maxlength: '200', autocomplete: 'off' } ) },
			{ name : 'description', label : t( 'projects.f-description' ), control : el( 'textarea', { id: 'f-description', rows: 4, maxlength: '2000', value: project.description } ) },
			{ name : 'started', label : t( 'projects.f-started' ), control : el( 'input', { id: 'f-started', type: 'date', value: project.started } ) },
		];
		let saving = false;

		const box = el( 'form', { class: 'card pad', id: 'project-info', novalidate: 'novalidate', on: { submit: function( event ) {

			event.preventDefault();

			if( saving === true )
				return;

			Object.keys( form.controls ).forEach( function( name ) {
				form.controls[name].classList.remove( 'err' );
				form.controls[name].removeAttribute( 'aria-invalid' );
				form.notes[name].textContent = '';
			} );

			const typed = { title : form.controls.title.value.trim(), description : form.controls.description.value, started : form.controls.started.value };

			if( typed.title === '' ) {
				form.controls.title.classList.add( 'err' );
				form.controls.title.setAttribute( 'aria-invalid', 'true' );
				form.notes.title.textContent = t( 'projects.err-title' );
				form.controls.title.focus();
				toast( t( 'projects.err-title' ), true );
				return;
			}

			// Only what changed is posted
			const fields = {};

			Object.keys( typed ).forEach( function( name ) {
				if( typed[name] !== project[name] )
					fields[name] = typed[name];
			} );

			if( Object.keys( fields ).length === 0 ) {
				form.dirty = false;
				view.editing = false;
				view.info.replaceWith( view.info = projectCard( view ) );
				return;
			}

			let release = function() {};

			state.busy = new Promise( function( resolve ) { release = resolve } );
			saving = true;
			save.disabled = true;

			const over = function() {
				saving = false;
				save.disabled = false;
				state.busy = null;
				release();
			};

			projectSave( project.id, view.native, fields ).then( function( answer ) {

				if( answer.ok !== true ) {
					over();
					refuseAt( form, answer, 'projects.error-save' );
					return null;
				}

				return answer;
			} ).then( function( result ) {

				if( result === null )
					return;

				over();
				form.dirty = false;
				toast( t( 'projects.saved' ) );

				if( state.view === view.mine )
					reloadProject( view );
			} );
		} } },
		el( 'h3', { text: t( 'projects.card' ) } ), formList( rows, form ),
		el( 'div', { class: 'actions top' }, save, el( 'button', { type: 'button', class: 'btn sm', id: 'project-cancel', text: t( 'form.cancel' ), on: { click: function() {
			view.editing = false;
			state.draft = null;
			view.info.replaceWith( view.info = projectCard( view ) );
		} } } ) ) );

		submitter( 'project', box, save, function() { return form.dirty === true } );

		return box;
	};
	/**
	 *	The menu ⋯ of the head of a project: the items the packages add and - for an account that edits the Elements - change, finish or reopen and delete
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object|null}							null where there is nothing to offer
	 */
	const projectMenu = function( view ) {

		const project = view.project;
		const items = headItems( 'project', view );

		if( state.perms.elements === true ) {

			if( items.length > 0 )
				items.push( 'sep' );

			items.push(
				{ label : t( 'projects.edit' ), run : function() {
					if( view.editing === true )
						return;
					view.editing = true;
					view.info.replaceWith( view.info = projectCard( view ) );
				} },
				{ label : t( project.done === true ? 'projects.reopen' : 'projects.finish' ), run : function() {
					projectSave( project.id, view.native, { done : project.done !== true } ).then( function( answer ) {
						if( answer.ok !== true ) {
							failed( answer, 'projects.error-save' );
							return;
						}

						toast( t( project.done === true ? 'projects.reopened' : 'projects.finished' ) );

						if( state.view === view.mine )
							reloadProject( view );
					} );
				} },
				'sep',
				{ label : t( 'projects.delete' ), danger : true, run : function() { removeProject( view ) } } );
		}

		return items.length === 0 ? null : menu( { label : '⋯', aria : t( 'menu.more' ), items : items } );
	};
	/**
	 *	Delete a project, after the question: the Elements panel's own delete
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{void}
	 */
	const removeProject = function( view ) {

		const project = view.project;

		ask( { title : t( 'projects.delete' ), text : t( 'projects.delete-text', projectName( project ) ), yes : t( 'projects.delete' ), danger : true } ).then( function( yes ) {

			if( yes !== true || state.view !== view.mine )
				return;

			call( 'elements/delete', { type : PROJECT_TYPE, uri : project.id }, 'projects.error-delete' ).then( function( answer ) {
				if( answer.ok === true && state.view === view.mine ) {
					// The project is gone; where the rows that hung on it could not be cleared at once the server says so, and the status of the setup clears them
					toast( answer.body !== null && typeof answer.body === 'object' && answer.body.leftover === true ? t( 'projects.deleted-leftover' ) : t( 'projects.deleted' ) );
					wn.location.hash = '#projects';
				}
			} );
		} );
	};
	// --- Tasks -----------------------------------------------------------------------------------------------------
	// The tasks of a project are a plain list: a field on top for a new one, the open ones from the top, the done ones below them and dimmed. The tab of the tasks of the card and the app at /crm/todo draw the same block; the app has no
	// date and keeps the actions of a row in its menu. Every action posts and draws the list again from the answer, which is the whole list of the project; a text that was typed and not stored is kept through that
	// (drafts), and is stored when the field loses the focus, after a pause in the typing and with Ctrl+S

	const TASK_PAUSE = 1500;
	/**
	 *	The field of a new task and the field of a text grow with what is typed into them
	 *
	 *	@param		{Object}	area
	 *
	 *	@return		{void}
	 */
	const growArea = function( area ) {

		// The rows are counted up until the text fits: the height is the browser's, nothing is set on the element's style
		area.rows = 1;

		if( area.clientHeight === 0 )
			return;

		while( area.scrollHeight > area.clientHeight + 1 && area.rows < 40 )
			area.rows++;
	};
	/**
	 *	The block of the tasks of a project
	 *
	 *	@param		{string}	project					The id of the project
	 *	@param		{Array}		tasks						The tasks as crm-tasks/list has them
	 *	@param		{Object}	options					app (the app: no date, the actions in the menu of the row), mine (the number of the view that draws it), counted (called with the number of open tasks after every answer), focus (the field of a new task takes the focus)
	 *
	 *	@return		{Object}									node (the form and the list), field (the field of a new task) and list (the ul)
	 */
	const tasksBlock = function( project, tasks, options ) {

		let current = tasks;
		const drafts = {};
		const timers = {};
		const slots = [];
		const busy = {};
		const field = el( 'textarea', { class: 'task-field', id: 'task-new-text', rows: 1, maxlength: '4000', placeholder: t( 'tasks.new' ), 'aria-label': t( 'tasks.new' ) } );
		const add = el( 'button', { type: 'submit', class: 'btn sm primary', id: 'task-add', text: t( 'tasks.add' ) } );
		const form = el( 'form', { class: 'task-new', id: 'task-new', novalidate: 'novalidate' }, field, add );
		const list = el( 'ul', { class: 'tasks', id: 'tasks' } );
		const live = function() { return state.view === options.mine };
		let adding = false;
		let draw = null;

		const take = function( answer ) {

			current = answer.body.tasks;

			if( typeof options.counted === 'function' )
				options.counted( answer.body.open );

			draw();
		};

		const post = function( action, data, fallback ) {

			return call( action, Object.assign( { project : project }, data ), fallback || 'tasks.error' ).then( function( answer ) {

				if( answer.ok === true && live() === true )
					take( answer );

				return answer;
			} );
		};

		const create = function() {

			const text = field.value;

			if( adding === true || text.trim() === '' )
				return;

			adding = true;
			add.disabled = true;

			post( 'crm-tasks/add', { text : text } ).then( function( answer ) {

				adding = false;
				add.disabled = false;

				if( answer.ok === true ) {
					field.value = '';
					growArea( field );
					field.focus();
				}
			} );
		};

		form.addEventListener( 'submit', function( event ) { event.preventDefault(); create() } );
		field.addEventListener( 'input', function() { growArea( field ) } );
		field.addEventListener( 'keydown', function( event ) {

			if( event.key === 'Enter' && event.shiftKey !== true && event.isComposing !== true ) {
				event.preventDefault();
				create();
			}
		} );

		const keep = function( id, area ) {

			wn.clearTimeout( timers[id] );
			timers[id] = undefined;

			const text = drafts[id];

			if( text === undefined || busy[id] === true )
				return Promise.resolve();

			busy[id] = true;

			return call( 'crm-tasks/text', { id : id, text : text }, 'tasks.error' ).then( function( answer ) {

				busy[id] = false;

				if( answer.ok !== true )
					return;

				const found = current.find( function( task ) { return task.id === id } );

				if( found !== undefined )
					found.text = text;

				if( drafts[id] === text )
					delete drafts[id];
				else if( drafts[id] !== undefined )
					keep( id, area );
			} );
		};

		const act = function( action, id, extra ) {
			return post( action, Object.assign( { id : id }, extra || {} ) );
		};

		const remove = function( task ) {

			ask( { title : t( 'tasks.delete-title' ), text : t( 'tasks.delete-text' ), yes : t( 'tasks.delete' ), danger : true } ).then( function( yes ) {

				if( yes === true && live() === true ) {
					delete drafts[task.id];
					act( 'crm-tasks/delete', task.id );
				}
			} );
		};

		const copy = function( task ) {

			copyText( drafts[task.id] ?? task.text ).then( function( ok ) {
				toast( t( ok === true ? 'tasks.copied' : 'tasks.copy-failed' ), ok !== true );
			} );
		};

		draw = function() {

			slots.splice( 0 ).forEach( unsaver );
			clear( list );

			if( current.length === 0 ) {
				list.appendChild( el( 'li', { class: 'task-empty muted', text: t( 'tasks.empty' ) } ) );
				return;
			}

			const open = current.filter( function( task ) { return task.done === false } );

			current.forEach( function( task ) {

				const first = task.done === false && open[0] === task;
				const last = task.done === false && open[open.length - 1] === task;
				const area = el( 'textarea', { class: 'task-text', rows: 1, maxlength: '4000', 'aria-label': t( 'tasks.text' ), value: drafts[task.id] ?? task.text } );
				const box = el( 'input', { type: 'checkbox', class: 'task-box', checked: task.done === true, 'aria-label': t( 'tasks.done' ), on: { change: function( event ) {

					event.target.disabled = true;
					act( 'crm-tasks/done', task.id, { on : event.target.checked === true } ).then( function( answer ) {
						if( answer.ok !== true && live() === true )
							event.target.checked = task.done === true;

						event.target.disabled = false;
					} );
				} } } );
				const items = [];

				if( task.done === false ) {
					items.push( { label : t( 'tasks.up' ), run : function() { act( 'crm-tasks/move', task.id, { dir : 'up' } ) }, off : first } );
					items.push( { label : t( 'tasks.down' ), run : function() { act( 'crm-tasks/move', task.id, { dir : 'down' } ) }, off : last } );
				}

				items.push( { label : t( 'tasks.copy' ), run : function() { copy( task ) } } );
				items.push( { label : t( 'tasks.delete' ), run : function() { remove( task ) }, danger : true } );

				const shown = items.filter( function( item ) { return item.off !== true } );
				const dated = options.app === true ? null : el( 'span', { class: 'task-date muted', text: task.done === true ? t( 'tasks.done-on', dmy( task.done_at ) ) : dmy( task.created_at ) } );
				const icons = options.app === true ? null : el( 'span', { class: 'task-icons' }, [
					task.done === true ? null : el( 'button', { type: 'button', class: 'btn sm quiet task-up', title: t( 'tasks.up' ), 'aria-label': t( 'tasks.up' ), disabled: first, text: '↑', on: { click: function() { act( 'crm-tasks/move', task.id, { dir : 'up' } ) } } } ),
					task.done === true ? null : el( 'button', { type: 'button', class: 'btn sm quiet task-down', title: t( 'tasks.down' ), 'aria-label': t( 'tasks.down' ), disabled: last, text: '↓', on: { click: function() { act( 'crm-tasks/move', task.id, { dir : 'down' } ) } } } ),
					el( 'button', { type: 'button', class: 'btn sm quiet task-copy', title: t( 'tasks.copy' ), 'aria-label': t( 'tasks.copy' ), text: '⧉', on: { click: function() { copy( task ) } } } ),
					el( 'button', { type: 'button', class: 'btn sm quiet task-delete', title: t( 'tasks.delete' ), 'aria-label': t( 'tasks.delete' ), text: '✕', on: { click: function() { remove( task ) } } } ) ] );
				const row = el( 'li', { class: 'task'+ ( task.done === true ? ' done' : '' ), dataset: { id: String( task.id ) } },
					el( 'label', { class: 'hit task-hit' }, box ),
					el( 'div', { class: 'task-main' }, area,
						el( 'div', { class: 'task-meta' }, dated ) ),
					icons,
					el( 'span', { class: 'task-more' }, menu( { label : '⋯', aria : t( 'tasks.menu' ), button : 'sm quiet', items : shown } ) ) );
				const slot = 'task-'+ task.id;

				area.addEventListener( 'input', function() {

					drafts[task.id] = area.value;
					growArea( area );
					wn.clearTimeout( timers[task.id] );
					timers[task.id] = wn.setTimeout( function() { if( live() === true ) keep( task.id, area ) }, TASK_PAUSE );
				} );
				area.addEventListener( 'blur', function() { if( drafts[task.id] !== undefined && live() === true ) keep( task.id, area ) } );
				saver( slot, row, function() { keep( task.id, area ) }, function() { return drafts[task.id] !== undefined && drafts[task.id] !== task.text }, function() { return busy[task.id] === true } );
				slots.push( slot );
				list.appendChild( row );
				growArea( area );
			} );
		};

		draw();

		if( options.focus === true )
			wn.setTimeout( function() { field.focus() }, 0 );

		return { node : el( 'div', { class: 'tasks-block' }, form, list ), field : field, list : list, grow : function() { list.querySelectorAll( 'textarea' ).forEach( growArea ); growArea( field ) } };
	};
	/**
	 *	The tab of the tasks: the tasks of the project, read when the tab opens
	 *
	 *	@param		{Object}	view
	 *
	 *	@return		{Object}
	 */
	const tabProjectTasks = function( view ) {

		const box = el( 'div', { class: 'card pad tasks-card', id: 'tasks-card' }, el( 'p', { class: 'state-note', role: 'status', text: t( 'page.loading' ) } ) );
		const mine = state.view;

		api( 'crm-tasks/list', { project : view.project.id } ).then( function( answer ) {

			if( state.view !== mine )
				return;

			clear( box );

			if( answer.ok !== true ) {
				box.appendChild( note( 'bad', errorText( answer, 'error.load' ) ) );
				return;
			}

			const block = tasksBlock( view.project.id, answer.body.tasks, { mine : mine, counted : function( open ) {
				view.tasks = open;

				if( view.tasksNode !== undefined && view.tasksNode !== null )
					view.tasksNode.textContent = String( open );
			} } );

			box.appendChild( block.node );
			block.grow();
		} );

		return box;
	};
	// --- Releases ---------------------------------------------------------------------------------------------------
	// The tab of the releases of the card of a project: the clients the project is released to, the earliest first, each with the day it began and - when it is not the owner's own - the source that made it; Add a client
	// releases it to another client (the client the project belongs to and the ones it is released to are not offered), ending a share asks first. The server answers every change with the list as it is then (crm-projects/share,
	// crm-projects/unshare); a client that is switched off may be released to and says so.

	/**
	 *	The mark of the source of a release: the key of the feature that made it as a small text, as the logbook shows the source of an entry; none for the CRM's own
	 *
	 *	@param		{string}	source
	 *
	 *	@return		{Object|null}
	 */
	const sourceMark = function( source ) {
		return source === 'crm' ? null : el( 'span', { class: 'logbook-source share-source', title: t( 'shares.source' ), text: source } );
	};
	/**
	 *	The tab of the releases
	 *
	 *	@param		{Object}	view						The card of the project, with shares (crm-projects/get) and client
	 *
	 *	@return		{Object}
	 */
	const tabProjectShares = function( view ) {

		const project = view.project;
		const mine = state.view;
		const list = el( 'ul', { class: 'list-mini shares', id: 'shares' } );
		const status = el( 'p', { class: 'state-note', id: 'shares-state', role: 'status', tabindex: '-1' } );
		const choose = clientPicker( { id: 'share-client', list: t( 'shares.list' ), none: t( 'shares.no-match' ), more: function( rest ) { return t( 'shares.more', rest ) }, picked: function() { problem.hidden = true } } );
		const add = el( 'button', { type: 'submit', class: 'btn primary', id: 'share-add', text: t( 'shares.add-button' ) } );
		const problem = el( 'p', { class: 'state-note bad', id: 'share-error', role: 'alert', hidden: true } );
		const form = el( 'form', { class: 'share-new', id: 'share-new', novalidate: 'novalidate' },
			el( 'label', { class: 'share-label', for: 'share-client', text: t( 'shares.add' ) } ),
			el( 'div', { class: 'share-line' }, choose.node, add ) );
		const box = el( 'div', { class: 'card pad shares-card', id: 'shares-card' }, el( 'h3', { text: t( 'shares.title' ) } ), el( 'p', { class: 'state-note', text: t( 'shares.hint' ) } ), list, status, form, problem );
		let clients = null;

		const owner = function( one ) { return view.client !== null && view.client !== undefined && one.id === view.client.id };
		const named = function( one ) { return one.number+ ' · '+ clientTitle( one ) };

		const counted = function() {

			if( view.sharesNode !== undefined && view.sharesNode !== null )
				view.sharesNode.textContent = String( view.shares.length );
		};

		const options = function() {

			const taken = view.shares.map( function( share ) { return share.client_id } );
			const free = ( clients || [] ).filter( function( one ) { return owner( one ) === false && taken.includes( one.id ) === false } );

			choose.items( free.map( function( one ) { return { value: String( one.id ), text: named( one ), label: named( one )+ ( one.active === true ? '' : ' ('+ t( 'clients.inactive' )+ ')' ) } } ) );
			choose.enable( clients !== null && free.length > 0 );
			add.disabled = choose.input.disabled;
			status.textContent = clients !== null && free.length === 0 && view.shares.length > 0 ? t( 'shares.none-left' ) : '';
		};

		// After a change the focus goes to the field, so that the next client can be chosen; where there is no client left to choose, to the sentence that says so
		const back = function() {

			if( choose.input.disabled === true )
				status.focus();
			else
				choose.focus();
		};

		const end = function( share ) {

			ask( { title : t( 'shares.end-title' ), text : t( 'shares.end-text', projectName( project ), named( share ) ), yes : t( 'shares.end-yes' ), danger : true } ).then( function( yes ) {

				if( yes !== true || state.view !== mine )
					return null;

				return api( 'crm-projects/unshare', { id : project.id, client : String( share.client_id ) } ).then( function( answer ) {

					if( state.view !== mine )
						return;

					if( answer.ok !== true ) {
						failed( answer, 'shares.error-end' );
						return;
					}

					view.shares = answer.body.shares;
					toast( t( answer.body.removed === true ? 'shares.ended' : 'shares.gone', named( share ) ) );
					draw();
					back();
				} );
			} );
		};

		// A release a feature made is the feature's to end by its own rule; "keep" makes it the owner's, so that the feature leaves it
		const keep = function( share ) {

			api( 'crm-projects/keep', { id : project.id, client : String( share.client_id ) } ).then( function( answer ) {

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					failed( answer, 'shares.error-keep' );
					return;
				}

				view.shares = answer.body.shares;
				toast( t( answer.body.kept === true ? 'shares.kept' : 'shares.kept-already', named( share ) ) );
				draw();

				// The button that was pressed is gone with the mark: the focus stays on the row, at the button that ends its release
				const row = Array.from( list.children ).find( function( li ) { return li.dataset.client === String( share.client_id ) } );
				const end = row === undefined ? null : row.querySelector( '.share-end' );

				if( end !== null )
					end.focus();
			} );
		};

		const draw = function() {

			clear( list );
			counted();
			options();

			if( view.shares.length === 0 ) {
				list.appendChild( el( 'li', { class: 'share-empty', text: t( 'shares.empty' ) } ) );
				return;
			}

			view.shares.forEach( function( share ) {
				list.appendChild( el( 'li', { class: 'share', dataset: { client: String( share.client_id ), source: share.source } },
					el( 'span', { class: 'share-who' },
						el( 'span', { class: 'pno num', text: share.number } ), ' ', link( '#client-'+ share.client_id, share.title !== '' ? share.title : share.number, { class: 'share-link' } ),
						share.active === true ? null : [ ' ', badge( 'muted', t( 'clients.inactive' ) ) ],
						sourceMark( share.source ) === null ? null : [ ' ', sourceMark( share.source ) ] ),
					el( 'span', { class: 'share-side' },
						el( 'span', { class: 'r share-since', text: t( 'shares.since', dmy( share.since ) ) } ),
						share.source === 'crm' ? null : el( 'button', { type: 'button', class: 'btn sm quiet share-keep', text: t( 'shares.keep' ), 'aria-label': t( 'shares.keep-for', named( share ) ), title: t( 'shares.keep-hint' ), on: { click: function() { keep( share ) } } } ),
						el( 'button', { type: 'button', class: 'btn sm quiet share-end', text: t( 'shares.end' ), 'aria-label': t( 'shares.end-for', named( share ) ), on: { click: function() { end( share ) } } } ) ) ) );
			} );
		};

		form.addEventListener( 'submit', function( event ) {

			event.preventDefault();

			const wanted = choose.value();

			if( wanted === '' ) {
				problem.textContent = t( 'shares.pick' );
				problem.hidden = false;
				choose.input.focus();
				return;
			}

			const one = ( clients || [] ).find( function( client ) { return String( client.id ) === wanted } );

			problem.hidden = true;
			add.disabled = true;
			api( 'crm-projects/share', { id : project.id, client : wanted } ).then( function( answer ) {

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					problem.textContent = errorText( answer, 'shares.error-add' );
					problem.hidden = false;
					add.disabled = false;
					return;
				}

				view.shares = answer.body.shares;
				toast( t( answer.body.created === true ? 'shares.added' : 'shares.already', one === undefined ? '' : named( one ) ) );
				choose.reset();
				draw();
				back();
			} );
		} );
		draw();

		api( 'crm-clients/list' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				status.textContent = errorText( answer, 'shares.error' );
				return;
			}

			clients = answer.body.clients;
			options();
		} );

		return box;
	};
	const drawProject = function( view ) {

		const project = view.project;

		view.mine = state.view;
		state.draft = null;
		reveal();

		state.main.appendChild( el( 'div', { class: 'crumb' }, link( '#projects', t( 'projects.title' ) ), ' › '+ projectName( project ) ) );
		state.main.appendChild( head( el( 'h1', { text: projectName( project ) } ), projectBadge( project ),
			el( 'span', { class: 'sub' }, el( 'span', { class: 'num', id: 'project-no', text: projectLabel( project.id ) } ), ' · ', view.client === null ? project.client : link( '#client-'+ view.client.id, view.client.title ), project.started === '' ? null : ' · '+ t( 'projects.since', dmy( project.started ) ) ),
			phaseSelect( view ),
			el( 'span', { class: 'grow' } ),
			headParts( 'project', 'actions', view ),
			projectMenu( view ) ) );

		headParts( 'project', 'notes', view ).forEach( function( node ) { state.main.appendChild( node ) } );

		view.info = projectCard( view );
		state.main.appendChild( view.info );

		const body = el( 'div', { id: 'tab' } );
		const tabs = cardStrip( 'project', view, body );

		// The numbers of the tabs that the card keeps up to date as tasks and releases change
		view.tasksNode = tabs.badges.tasks ?? null;
		view.sharesNode = tabs.badges.shares ?? null;

		state.main.appendChild( tabs.strip );
		state.main.appendChild( body );
		tabs.show( tabs.start );
	};
	// --- The app of the tasks ---------------------------------------------------------------------------------------------
	// The page at /crm/todo (the data block says app: todo) is one screen: the head in one line - the title and the menu - over a row of tabs, one for each project that has open tasks, and the list of the tasks of the project
	// that is open, drawn as the tab of the tasks of the card draws it, with no date and the actions of a task in the menu of its row. The hash is #tasks-<id>; without one the first tab is open, and any other hash is the same screen.
	// The project a person chose from the menu (Other project) is a tab at the end while it is open, and so is the one whose last task was done, until another tab is chosen. What is done goes through the same functions as in the card

	/**
	 *	The top of the app: the head with the title and, in the row, the menu of the app
	 *
	 *	@param		{Object}	title					The head of the app
	 *	@param		{Array}		items					The items of the menu, as menu() has them; none, no menu
	 *
	 *	@return		{Object}
	 */
	const appTop = function( title, items ) {
		return el( 'div', { class: 'fb-top', id: 'fb-top' }, title, items.length === 0 ? null : el( 'div', { class: 'fb-more', id: 'fb-more' }, menu( { label : '⋯', aria : t( 'todo.more' ), button : 'quiet', items : items } ) ) );
	};
	/**
	 *	Which project the hash of the app names
	 *
	 *	@param		{string}	hash
	 *
	 *	@return		{Object}									screen (tasks) and id ('' for none: the first tab)
	 */
	const todoWhere = function( hash ) {

		const found = /^tasks-([A-Za-z0-9_]{1,40})$/.exec( String( hash || '' ).replace( /^#/, '' ) );

		return { screen : 'tasks', id : found === null ? '' : found[1] };
	};
	/**
	 *	The words of a tab: the client, the number of the project and its title
	 *
	 *	@param		{Object}	project					client_title, label, title
	 *
	 *	@return		{string}
	 */
	const todoTabName = function( project ) {
		return ( project.client_title === '' ? '' : project.client_title+ ' · ' )+ project.label+ ( project.title === '' ? '' : ' '+ project.title );
	};
	/**
	 *	The project a person chooses from the others: the dialog with the select, the hash of the project as the answer
	 *
	 *	@param		{Array}		others					The projects that are not finished and have no open task
	 *
	 *	@return		{void}
	 */
	const todoOther = function( others ) {

		if( others.length === 0 ) {
			toast( t( 'todo.other-none' ) );
			return;
		}

		dialog( { title : t( 'todo.other-title' ), text : t( 'todo.other-text' ), yes : t( 'todo.other-yes' ), fields : [ { name : 'project', label : t( 'todo.other-field' ), options : others.map( function( project ) { return { value : project.id, label : todoTabName( project ) } } ), value : others[0].id } ] } ).then( function( values ) {
			if( values !== null )
				wn.location.hash = '#tasks-'+ values.project;
		} );
	};
	/**
	 *	The screen of the app: the head, the tabs and the tasks of the project that is open
	 *
	 *	@param		{Object}	where						id
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const todoScreen = function( where, mine ) {

		loading();

		api( 'crm-tasks/open' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			const projects = answer.body.projects;
			const others = answer.body.others;
			const everyone = projects.concat( others );
			const asked = where.id === '' ? null : everyone.find( function( project ) { return project.id === where.id } );
			const chosen = asked !== undefined && asked !== null ? asked : projects[0];
			const tabs = chosen !== undefined && projects.includes( chosen ) === false ? projects.concat( [ chosen ] ) : projects;
			const more = others.filter( function( project ) { return project !== chosen } );
			const items = [ { label : t( 'todo.other' ), run : function() { todoOther( more ) } } ];

			if( chosen !== undefined )
				items.push( { label : t( 'todo.surface' ), href : state.base+ '/crm#project-'+ chosen.id+ '-tasks' } );

			reveal();
			state.main.appendChild( appTop( head( el( 'h1', { text: t( 'tasks.title' ) } ) ), items ) );

			const hint = homeHint();

			if( hint !== null )
				state.main.appendChild( hint );

			if( chosen === undefined ) {
				state.main.appendChild( note( '', t( 'todo.none' ) ) );
				return;
			}

			const counts = {};
			const strip = el( 'div', { class: 'tabs todo-tabs', id: 'todo-tabs', role: 'tablist', 'aria-label': t( 'todo.tabs' ) }, tabs.map( function( project ) {

				const on = project === chosen;
				const count = el( 'span', { class: 'n', text: String( project.open ) } );

				counts[project.id] = count;

				return el( 'a', { href: '#tasks-'+ project.id, role: 'tab', 'aria-selected': on ? 'true' : 'false', class: on ? 'on' : '', title: todoTabName( project ), dataset: { project: project.id } },
					el( 'span', { class: 'todo-tab-name', text: todoTabName( project ) } ), count );
			} ) );

			state.main.appendChild( strip );

			const holder = el( 'div', { class: 'card pad tasks-card', id: 'tasks-card', role: 'tabpanel' }, el( 'p', { class: 'state-note', role: 'status', text: t( 'page.loading' ) } ) );

			state.main.appendChild( holder );

			const active = strip.querySelector( '[aria-selected="true"]' );

			if( active !== null && typeof active.scrollIntoView === 'function' )
				active.scrollIntoView( { block : 'nearest', inline : 'start' } );

			api( 'crm-tasks/list', { project : chosen.id } ).then( function( listed ) {

				if( state.view !== mine )
					return;

				clear( holder );

				if( listed.ok !== true ) {
					holder.appendChild( note( 'bad', errorText( listed, 'error.load' ) ) );
					return;
				}

				const block = tasksBlock( chosen.id, listed.body.tasks, { app : true, mine : mine, counted : function( open ) { counts[chosen.id].textContent = String( open ) } } );

				holder.appendChild( block.node );
				block.grow();
			} );
		} );
	};
	// --- Setup -------------------------------------------------------------------------------------------------------

	// The values the sentence of a line in a state names, in the order it names them: it is written for one state of one line
	const SETUP_VALUES = {
		'schema-ok' : [ 'version' ], 'schema-none' : [ 'version' ], 'schema-behind' : [ 'stored', 'version', 'lacks' ], 'addresses-ok' : [ 'crm', 'app' ],
		'mail-failed' : [ 'failed', 'waiting' ], 'mail-stuck' : [ 'waiting' ], 'mail-head-partial' : [ 'lacks' ],
	};
	// A line that needs nothing done: in order, or - the addresses - the owner's own
	const complete = function( line ) {
		return line.state === 'ok' || line.state === 'own';
	};
	/**
	 *	What a line of the status is called: the name it gave with its state where it is a line of a package, else the word of the surface for it, else its key
	 *
	 *	@param		{Object}	line
	 *
	 *	@return		{string}
	 */
	const setupLabel = function( line ) {
		return typeof line.label === 'string' && line.label !== '' ? line.label : ( has( 'setup.line-'+ line.key ) === true ? t( 'setup.line-'+ line.key ) : line.key );
	};
	/**
	 *	The sentence of a line in its state, with the values the state brought; a line of a package says its own, as a detail of its answer
	 *
	 *	@param		{Object}	line
	 *
	 *	@return		{string}									'' where nobody has a sentence for it
	 */
	const statusLine = function( line ) {

		if( typeof line.detail === 'string' )
			return line.detail;

		const key = 'setup.detail-'+ line.key+ '-'+ line.state;

		if( has( key ) === false )
			return '';

		return t( key, ...( SETUP_VALUES[line.key+ '-'+ line.state] ?? [] ).map( function( name ) {
			const value = line[name];
			return Array.isArray( value ) === true ? value.join( ', ' ) : ( value === '' ? '-' : value );
		} ) );
	};
	/**
	 *	The rows of the status: a row for each line the server names that no step of a package took over - the label, the sentence, the state, and a button where the line has a fix and is not as it should be
	 *	and the steps of the packages after them. A press runs the fix of the line and draws the answer
	 *
	 *	@param		{Object}	status					As crm/status has it
	 *	@param		{number}	mine
	 *
	 *	@return		{Array}
	 */
	const setupRows = function( status, mine ) {

		const kit = kitOf( mine );

		const stray = setupStray( status ).map( function( line ) {

			const fine = complete( line ) === true || line.hint === true;
			const sentence = statusLine( line );
			let mend = null;

			if( line.fixable === true && complete( line ) === false ) {

				mend = el( 'button', { type: 'button', class: 'btn sm', dataset: { fix: line.key }, text: t( 'setup.mend' ), 'aria-label': t( 'setup.mend-line', setupLabel( line ) ), on: { click: function() {

					mend.disabled = true;

					call( 'crm/setup', { key : line.key }, 'error.setup' ).then( function( answer ) {

						mend.disabled = false;

						if( answer.ok === true && state.view === mine ) {
							refresh();
							drawSetup( answer.body, t( 'setup.mended', setupLabel( line ) ), mine );
						}
					} );
				} } } );
			}

			return el( 'li', { dataset: { key: line.key, state: complete( line ) ? 'ok' : ( fine ? 'hint' : 'todo' ) } },
				el( 'span', {}, el( 'b', { text: setupLabel( line ) } ), sentence === '' ? null : el( 'span', { class: 'state-note block', text: sentence } ) ),
				el( 'span', {}, mend, badge( complete( line ) ? 'ok' : ( fine ? 'muted' : 'warn' ), t( 'setup.state-'+ ( has( 'setup.state-'+ line.state ) === true ? line.state : 'other' ) ) ) ) );
		} );

		return settingsOrdered( stepList.list ).map( function( step ) {

			const stand = stepStand( step, status );

			if( stand.done === null )
				return null;

			const detail = stand.failed === true ? entryFailed( 'extension.step-failed', step.key ) : ( step.draw === null ? null : entryNode( function() { return step.draw( kit, status ) }, 'extension.step-failed', step.key ) );

			return el( 'li', { dataset: { key: step.key, state: stand.done ? 'ok' : 'todo' } },
				el( 'span', {}, el( 'b', { text: wordOf( step.word, step.key ) } ), detail ),
				badge( stand.done ? 'signed' : 'warn', t( stand.done ? 'setup.state-ok' : 'setup.state-todo' ) ) );
		} ).concat( stray );
	};
	/**
	 *	The screen of the status: the lines with the sentence of each and the button of the ones that can be mended, then - where all is in order - the way on to the overview
	 *
	 *	@param		{Object}	status					As crm/status has it
	 *	@param		{string}	said						What the last press said, kept over the screen that is drawn again
	 *	@param		{number}	mine						The view that draws it
	 *
	 *	@return		{void}
	 */
	const drawSetup = function( status, said, mine ) {

		const whole = setupWhole( status );

		reveal();
		state.main.appendChild( head( t( 'setup.title' ) ) );
		state.main.appendChild( el( 'div', { class: 'card pad' },
			el( 'p', { class: 'state-note', text: t( 'setup.intro' ) } ),
			el( 'h3', { text: t( 'setup.rows' ) } ),
			el( 'ul', { class: 'list-mini', id: 'setup-rows' }, setupRows( status, mine ) ),
			whole ? el( 'p', { class: 'state-note', id: 'setup-done' }, el( 'b', { text: t( 'setup.done' ) } ), ' ', link( '#overview', t( 'setup.next' ) ) ) : null,
			el( 'p', { class: 'state-note', id: 'setup-said', role: 'status', text: said } ) ) );
	};
	/**
	 *	The first run: the status is read, and a product that is whole has no use for this screen
	 *
	 *	@param		{Object}	where
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const setupScreen = function( where, mine ) {

		state.entered = true;

		if( state.seen !== null ) {
			const status = state.seen;

			state.seen = null;
			drawSetup( status, '', mine );
			return;
		}

		loading();

		api( 'crm/status' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			if( answer.ok !== true ) {
				loadFailed( answer, 'error.load', function() { core.route() } );
				return;
			}

			if( answer.body.whole === true && setupWhole( answer.body ) === true ) {
				wn.location.hash = '#overview';
				return;
			}

			drawSetup( answer.body, '', mine );
		} );
	};
	// --- Settings ----------------------------------------------------------------------------------------------------

	const settingsScreen = function( where, mine ) {

		loading();
		state.company = null;

		Promise.all( [ api( 'crm/settings-get' ), api( 'crm/notifications' ) ] ).then( function( answers ) {

			if( state.view !== mine )
				return;

			const failure = answers.find( function( answer ) { return answer.ok !== true } );

			if( failure !== undefined ) {
				loadFailed( failure, 'error.load', function() { core.route() } );
				return;
			}

			drawSettings( answers[0].body, answers[1].body, where );
		} );
	};
	// --- Company data -------------------------------------------------------------------------------------------------

	// The texts of the company the wizard writes: the surface edits these six through the Text panel's actions (text/keys, text/savebatch), for the keys below the prefix alone
	const COMPANY_PREFIX = '/project/company/';
	const COMPANY_KEYS = [ 'general/name', 'general/description', 'contact/address', 'contact/country', 'contact/email', 'contact/phone' ];
	const COMPANY_AREAS = [ 'general/description', 'contact/address' ];
	/**
	 *	A stored text as the person reads it: the entities the text fill hands out for a quote, a bracket, an ampersand and the signs of markup are the signs they stand for. One pass, so
	 *	that "&amp;quot;" is "&quot;" and not a quote
	 *
	 *	@param		{string}	text
	 *
	 *	@return		{string}
	 */
	const readableText = function( text ) {

		const named = { quot : '"', amp : '&', lt : '<', gt : '>', apos : "'" };

		return text.replace( /&(quot|amp|lt|gt|apos|#[0-9]{1,7}|#x[0-9a-f]{1,6});/gi, function( found, name ) {

			if( named[name.toLowerCase()] !== undefined )
				return named[name.toLowerCase()];

			const code = name[1] === 'x' || name[1] === 'X' ? parseInt( name.slice( 2 ), 16 ) : parseInt( name.slice( 1 ), 10 );

			return code > 0 && code <= 0x10FFFF && ( code < 0xD800 || code > 0xDFFF ) ? String.fromCodePoint( code ) : found;
		} );
	};
	/**
	 *	What the block shows: a field for each of the six texts in each language that has it - a text of all languages in the first card (the native language's) alone
	 *
	 *	@param		{Object}	body						The answer of text/keys (for an account with the Text panel) or of settings-get (company): keys, locales, native
	 *	@param		{boolean}	editable				Whether the answer is the panel's, which says for each key whether it may be written
	 *
	 *	@return		{Object}									locales (the native first), fields ([ { id, name, locale, value, writable } ] - locale '*' for a text of all), base and typed (id => text)
	 */
	const companyOf = function( body, editable ) {

		const entries = {};
		const locales = nativeFirst( ( body.locales || [] ).map( function( one ) { return typeof one === 'string' ? one : one.locale } ), body.native );
		const fields = [];
		const base = {};

		( body.keys || [] ).forEach( function( entry ) {
			if( typeof entry.key === 'string' && entry.key.indexOf( COMPANY_PREFIX ) === 0 )
				entries[entry.key.slice( COMPANY_PREFIX.length )] = entry;
		} );

		locales.forEach( function( locale, index ) {
			COMPANY_KEYS.forEach( function( name ) {

				const entry = entries[name];

				if( entry === undefined || ( entry.global === true && index > 0 ) )
					return;

				const where = entry.global === true ? '*' : locale;
				const id = name+ '|'+ where;
				const value = readableText( String( ( entry.values || {} )[where] ?? '' ) );

				base[id] = value;
				fields.push( { id : id, name : name, locale : where, card : locale, value : value, writable : editable === true && entry.writable !== false } );
			} );
		} );

		return { locales : locales, fields : fields, base : base, typed : {}, editable : editable };
	};
	/**
	 *	What the person changed: the fields whose text is not the stored one, as the items of a batch of the Text panel
	 *
	 *	@param		{Object}	company
	 *
	 *	@return		{Array}										[ { id, key, locale, value } ]
	 */
	const companyChanges = function( company ) {

		return company.fields.filter( function( field ) {
			return field.writable === true && company.typed[field.id] !== undefined && company.typed[field.id] !== company.base[field.id];
		} ).map( function( field ) {
			return { id : field.id, key : COMPANY_PREFIX+ field.name, locale : field.locale, value : company.typed[field.id] };
		} );
	};
	/**
	 *	The sentence beside a field the panel refused: its answer for a key is a word of its own ("not allowed" for a key of a role that may not write it), the sentence of
	 *	the panel's own failure otherwise
	 *
	 *	@param		{*}				result
	 *
	 *	@return		{string}
	 */
	const companySentence = function( result ) {
		return result !== undefined && result !== null && result.error === 'not allowed' ? t( 'company.error-denied' ) : t( 'text.error-save' );
	};
	/**
	 *	The block of the company data: a card for each language of the site, the native one first, with the fields of the company; Save posts the changed ones (the texts that are the
	 *	same in all languages with the first language that changed, then each other language that changed, since the panel answers one result to a key). Without the permission of
	 *	the Text panel the values are read, and the sentence says what is missing
	 *
	 *	@param		{Object}	company
	 *	@param		{Object}	sibling					The form of the screen next to it, which keeps the page's guard when this one is saved
	 *
	 *	@return		{Object}
	 */
	const companyCard = function( company, sibling ) {

		const later = [];
		const form = { controls : {}, notes : {}, dirty : false, form : true };
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: 'company-save', title: t( 'save.shortcut' ), text: t( 'company.save' ) } );
		const cards = company.locales.map( function( locale, index ) {

			const mine = company.fields.filter( function( field ) { return field.card === locale } );

			if( mine.length === 0 )
				return null;

			const title = el( 'h4', { text: index === 0 ? t( 'company.card-native', languageName( locale ) ) : languageName( locale ) } );

			if( company.editable === false )
				return el( 'div', { class: 'card pad', id: 'company-lang-'+ locale, dataset: { locale: locale } }, title, kv( mine.map( function( field ) { return [ t( 'company.'+ field.name.replace( '/', '-' ) ), field.value === '' ? '' : stacked( field.value.split( '\n' ) ) ] } ) ) );

			const rows = mine.map( function( field ) {

				const id = 'f-company-'+ field.name.replace( '/', '-' )+ ( field.locale === '*' ? '' : '-'+ locale );
				const shown = company.typed[field.id] ?? field.value;
				const control = COMPANY_AREAS.includes( field.name ) === true
					? el( 'textarea', { id: id, rows: 3, maxlength: '2000', value: shown } )
					: el( 'input', { id: id, type: field.name === 'contact/email' ? 'email' : 'text', value: shown, maxlength: '2000', autocomplete: 'off', inputmode: field.name === 'contact/phone' ? 'tel' : null } );

				if( field.writable === false ) {
					control.setAttribute( 'readonly', 'readonly' );
					control.classList.add( 'ro' );
				}

				// What is typed is kept over a drawing of the screen; the form is dirty while a text differs from the stored one (after the listener of formList(), which marks it for any input)
				later.push( function() {
					[ 'input', 'change' ].forEach( function( name ) {
						control.addEventListener( name, function() {
							company.typed[field.id] = control.value;
							form.dirty = companyChanges( company ).length > 0;

							if( form.dirty === true && ( state.draft === null || state.draft.dirty !== true ) )
								state.draft = form;
						} );
					} );
				} );

				return { name : field.id, label : t( 'company.'+ field.name.replace( '/', '-' ) ), control : control, hint : field.locale === '*' ? t( 'company.shared' ) : undefined };
			} );

			const list = formList( rows, form );

			later.forEach( function( watch ) { watch() } );
			later.length = 0;

			return el( 'div', { class: 'card pad', id: 'company-lang-'+ locale, dataset: { locale: locale } }, title, list );
		} ).filter( function( one ) { return one !== null } );

		if( cards.length === 0 )
			return el( 'div', { class: 'card pad gap', id: 'company' }, el( 'h3', { text: t( 'company.title' ) } ), el( 'p', { class: 'state-note', text: t( 'company.none' ) } ) );

		if( company.editable === false )
			return el( 'div', { class: 'card pad gap', id: 'company' }, el( 'h3', { text: t( 'company.title' ) } ), el( 'p', { class: 'state-note', id: 'company-readonly', text: t( 'company.readonly' ) } ), el( 'div', { class: 'cols' }, cards ) );

		const mine = state.view;

		const submit = function( event ) {

			event.preventDefault();

			const changes = companyChanges( company );

			// Nothing was changed: nothing is posted
			if( changes.length === 0 )
				return;

			// One batch to a language: the globals ride with the first language that changed
			const order = company.locales.filter( function( locale ) { return changes.some( function( change ) { return change.locale === locale } ) } );
			const batches = ( order.length > 0 ? order : [ '*' ] ).map( function( locale, index ) {
				return changes.filter( function( change ) { return change.locale === locale || ( index === 0 && change.locale === '*' ) } );
			} );

			save.disabled = true;
			Object.keys( form.notes ).forEach( function( id ) {
				form.notes[id].textContent = '';
				form.controls[id].classList.remove( 'err' );
				form.controls[id].removeAttribute( 'aria-invalid' );
			} );

			const send = function( index ) {

				if( index >= batches.length ) {
					save.disabled = false;
					form.dirty = companyChanges( company ).length > 0;

					if( form.dirty === false && state.draft === form )
						state.draft = sibling.dirty === true ? sibling : null;

					toast( t( 'company.saved' ) );
					return;
				}

				call( 'text/savebatch', { items : batches[index].map( function( change ) { return { key : change.key, locale : change.locale, value : change.value } } ) }, 'text.error-save' ).then( function( answer ) {

					const results = answer.ok === true && answer.body !== null && typeof answer.body === 'object' && answer.body.results !== null && typeof answer.body.results === 'object' ? answer.body.results : {};
					let refused = false;

					batches[index].forEach( function( change ) {

						const result = results[change.key];

						if( result !== undefined && result !== null && result.ok === true ) {
							company.base[change.id] = String( result.value ?? '' );
							delete company.typed[change.id];

							if( form.controls[change.id] !== undefined && form.controls[change.id].parentNode !== null )
								form.controls[change.id].value = company.base[change.id];

							return;
						}

						// A key the panel did not write: its sentence beside the field. A request that failed as a whole has said so in the toast
						refused = true;

						if( answer.ok === true && form.controls[change.id] !== undefined ) {
							form.controls[change.id].classList.add( 'err' );
							form.controls[change.id].setAttribute( 'aria-invalid', 'true' );
							form.notes[change.id].textContent = companySentence( result );
						}
					} );

					if( answer.ok !== true || refused === true ) {
						save.disabled = false;
						form.dirty = companyChanges( company ).length > 0;

						if( answer.ok === true && state.view === mine ) {
							const first = batches[index].find( function( change ) { return form.controls[change.id] !== undefined && form.notes[change.id].textContent !== '' } );

							toast( companySentence( results[first === undefined ? '' : first.key] ), true );

							if( first !== undefined && form.controls[first.id].parentNode !== null )
								form.controls[first.id].focus();
						}

						return;
					}

					send( index+ 1 );
				} );
			};

			send( 0 );
		};

		form.dirty = companyChanges( company ).length > 0;

		const box = el( 'form', { class: 'card pad gap', id: 'company', novalidate: 'novalidate', on: { submit: submit } },
			el( 'h3', { text: t( 'company.title' ) } ), el( 'p', { class: 'state-note', text: t( 'company.note' ) } ),
			el( 'div', { class: 'cols' }, cards ),
			el( 'div', { class: 'actions top' }, save, el( 'span', { class: 'state-note save-what', text: t( 'company.saves' ) } ) ) );

		if( form.dirty === true )
			state.draft = form;

		submitter( 'company', box, save, function() { return companyChanges( company ).length > 0 } );

		return box;
	};
	/**
	 *	Draw the block of the company data into its place: from what the screen holds already, from the Text panel (for an account with its permission: the texts and whether each may be
	 *	written), else from the CRM's own read of the texts
	 *
	 *	@param		{Object}	holder
	 *	@param		{Object}	view						The answer of settings-get
	 *	@param		{Object}	sibling					The form of the screen next to it
	 *	@param		{number}	mine
	 *
	 *	@return		{Promise}								Done when the block is drawn, whether it was read from the server or not
	 */
	const drawCompany = function( holder, view, sibling, mine ) {

		const draw = function( company ) {
			clear( holder );
			holder.appendChild( companyCard( company, sibling ) );
		};

		if( state.company !== null ) {
			draw( state.company );
			return Promise.resolve();
		}

		if( state.perms.text !== true ) {
			draw( companyOf( view.company, false ) );
			return Promise.resolve();
		}

		holder.appendChild( el( 'p', { class: 'state-note', role: 'status', text: t( 'page.loading' ) } ) );

		return api( 'text/keys' ).then( function( answer ) {

			if( state.view !== mine )
				return;

			clear( holder );

			if( answer.ok !== true ) {
				holder.appendChild( note( 'bad', el( 'span', { text: errorText( answer, 'text.error-load' ) } ), el( 'button', { type: 'button', class: 'btn sm', id: 'company-retry', text: t( 'page.retry' ), on: { click: function() {
					clear( holder );
					drawCompany( holder, view, sibling, mine );
				} } } ) ) );
				return;
			}

			state.company = companyOf( answer.body, true );
			draw( state.company );
		} );
	};
	/**
	 *	A block of the settings with the one field of a host (crm.example.com): the host the CRM is served under, or the host of a package that is served under one of its own, with a Save of its own (and Ctrl+S).
	 *	Empty leaves it unset. The server checks the name; the field only keeps it short
	 *
	 *	@param		{Object}	spec						key (the setting), id (the block), and the words: title, note, label, hint, save, saves, saved, same
	 *	@param		{Object}	settings				As settings-get has them
	 *	@param		{Object}	sibling					The form of the screen next to it, which keeps the page's guard when this one is saved
	 *	@param		{Array}		[rows]					Pairs of a word and a value that the block shows above the field: where the three things are
	 *
	 *	@return		{Object}
	 */
	const hostCard = function( spec, settings, sibling, rows ) {

		// A word of the block is the key of a text, or a function that says it (where a package adds to the sentence)
		const specWord = function( word ) { return typeof word === 'function' ? word() : t( word ) };

		const form = { controls : {}, notes : {}, dirty : false, form : true };
		const input = el( 'input', { id: 'f-'+ spec.key, type: 'text', value: settings[spec.key] ?? '', maxlength: '253', inputmode: 'url', autocomplete: 'off', autocapitalize: 'none', spellcheck: 'false' } );
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: spec.id+ '-save', title: t( 'save.shortcut' ), text: t( spec.save ) } );
		const mine = state.view;
		let stored = input.value;

		const list = formList( [ { name : spec.key, label : t( spec.label ), control : input, hint : specWord( spec.hint ) } ], form );

		[ 'input', 'change' ].forEach( function( name ) {
			input.addEventListener( name, function() {
				form.dirty = input.value !== stored;

				if( form.dirty === true && ( state.draft === null || state.draft.dirty !== true ) )
					state.draft = form;
			} );
		} );

		const box = el( 'form', { class: 'card pad gap', id: spec.id, novalidate: 'novalidate', on: { submit: function( event ) {

			event.preventDefault();

			if( input.value === stored ) {
				toast( t( spec.same ) );
				return;
			}

			save.disabled = true;

			api( 'crm/settings-save', { [spec.key] : input.value } ).then( function( answer ) {

				save.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'error.settings' );
					return;
				}

				stored = answer.body.settings[spec.key];
				settings[spec.key] = stored;
				input.value = stored;
				form.notes[spec.key].textContent = '';
				input.classList.remove( 'err' );
				input.removeAttribute( 'aria-invalid' );
				form.dirty = false;

				if( state.draft === form )
					state.draft = sibling.dirty === true ? sibling : null;

				toast( t( spec.saved ) );
			} );
		} } },
			el( 'h3', { text: t( spec.title ) } ), el( 'p', { class: 'state-note', text: specWord( spec.note ) } ), rows === undefined ? null : kv( rows ),
			list,
			el( 'div', { class: 'actions top' }, save, el( 'span', { class: 'state-note save-what', text: t( spec.saves ) } ) ) );

		submitter( spec.id, box, save, function() { return input.value !== stored } );

		return box;
	};
	// The block of the host the workbench is served under: the words are keys of the texts
	const APP_HOST = { key : 'app_host', id : 'app-host', title : 'app-host.title', note : 'app-host.note', label : 'app-host.label', hint : 'app-host.hint', save : 'app-host.save', saves : 'app-host.saves', saved : 'app-host.saved', same : 'app-host.same' };
	// The block of the host the CRM is served under: the words are keys of the texts, written out here for the one who looks for them
	const CRM_HOST = { key : 'crm_host', id : 'crm-host', title : 'crm-host.title', note : function() { return t( 'crm-host.note', extraText( 'crm-host-note' ) ) }, label : 'crm-host.label', hint : function() { return extraText( 'crm-host-hint' ) || t( 'crm-host.hint' ) }, save : 'crm-host.save', saves : 'crm-host.saves', saved : 'crm-host.saved', same : 'crm-host.same' };
	/**
	 *	The block of the wordmark of the settings, under the block of the company data: the two lines of the wordmark (the first is the name, the second the address of the website) and the two colours, with
	 *	a Save of its own (and Ctrl+S), and the button Test mail to me, which sends the band as it is saved to the contact address of the company. Every field may stay empty: without
	 *	the first line the mails show the logo and the name of the site. A color is a #rrggbb; the field says what the empty one means
	 *
	 *	@param		{Object}	settings				As settings-get has them
	 *	@param		{Object}	sibling					The form of the screen next to it, which keeps the page's guard when this one is saved
	 *
	 *	@return		{Object}
	 */
	const brandCard = function( settings, sibling ) {

		const form = { controls : {}, notes : {}, dirty : false, form : true };
		const keys = [ 'brand_word', 'brand_domain', 'brand_color', 'brand_ink' ];
		const limits = { brand_word : '24', brand_domain : '40', brand_color : '7', brand_ink : '7' };
		const inputs = {};
		const stored = {};
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: 'brand-save', title: t( 'save.shortcut' ), text: t( 'brand.save' ) } );
		const test = el( 'button', { type: 'button', class: 'btn', id: 'brand-test', text: t( 'brand.test' ) } );
		const mine = state.view;

		keys.forEach( function( key ) {
			stored[key] = settings[key] ?? '';
			inputs[key] = el( 'input', { id: 'f-'+ key, type: 'text', value: stored[key], maxlength: limits[key], autocomplete: 'off', spellcheck: 'false', placeholder: key === 'brand_color' ? '#54BCD6' : ( key === 'brand_ink' ? '#444647' : '' ) } );
		} );

		const changed = function() {
			return keys.some( function( key ) { return inputs[key].value !== stored[key] } );
		};

		const rows = keys.map( function( key ) {
			return { name : key, label : t( 'brand.'+ key.replace( 'brand_', '' ) ), control : inputs[key], hint : key === 'brand_color' ? t( 'brand.hint-color' )+ extraText( 'brand-color' ) : t( 'brand.hint-'+ key.replace( 'brand_', '' ) ) };
		} );
		const list = formList( rows, form );

		keys.forEach( function( key ) {
			[ 'input', 'change' ].forEach( function( name ) {
				inputs[key].addEventListener( name, function() {
					form.dirty = changed();
					test.disabled = form.dirty;

					if( form.dirty === true && ( state.draft === null || state.draft.dirty !== true ) )
						state.draft = form;
				} );
			} );
		} );

		test.addEventListener( 'click', function() {

			// The mail shows what is saved: with something typed that is not, there is nothing to test yet
			if( form.dirty === true )
				return;

			test.disabled = true;

			call( 'crm/brand-test', {}, 'error.brand-test' ).then( function( answer ) {

				test.disabled = form.dirty;

				if( answer.ok !== true || state.view !== mine )
					return;

				const sent = answer.body.mailed > 0;

				toast( t( sent === true ? 'brand.tested' : 'error.brand-test' ), sent === false );
			} );
		} );

		const box = el( 'form', { class: 'card pad gap', id: 'brand', novalidate: 'novalidate', on: { submit: function( event ) {

			event.preventDefault();

			if( changed() === false )
				return;

			save.disabled = true;

			const posted = {};

			keys.forEach( function( key ) { posted[key] = inputs[key].value } );

			api( 'crm/settings-save', posted ).then( function( answer ) {

				save.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'error.settings' );
					return;
				}

				keys.forEach( function( key ) {
					stored[key] = answer.body.settings[key];
					inputs[key].value = stored[key];
					form.notes[key].textContent = '';
					inputs[key].classList.remove( 'err' );
					inputs[key].removeAttribute( 'aria-invalid' );
				} );

				form.dirty = false;
				test.disabled = false;

				if( state.draft === form )
					state.draft = sibling.dirty === true ? sibling : null;

				toast( t( 'brand.saved' ) );
			} );
		} } },
			el( 'h3', { text: t( 'brand.title' ) } ), el( 'p', { class: 'state-note', text: t( 'brand.note' ) } ),
			list,
			el( 'div', { class: 'actions top' }, save, test, el( 'span', { class: 'state-note save-what', text: t( 'brand.saves' ) } ) ) );

		submitter( 'brand', box, save, function() { return changed() } );

		return box;
	};
	/**
	 *	The switches of the block of the mail notifications, in two groups: the ones a package enters (crm/notifications names them, in the order of their rank) that end in _owner are the mails to the owner,
	 *	the others the mails to the customers. The words of a switch are the package's: mailnotes.sw-<key> is its label and mailnotes.hint-<key> the sentence under it
	 *
	 *	@param		{Object}	mails						As crm/notifications has it
	 *
	 *	@return		{Array}										[ [ group, [ key ] ] ] - a group with no switch is not there
	 */
	const mailGroups = function( mails ) {

		const keys = Object.keys( mails.switches );

		return [ [ 'owner', keys.filter( function( key ) { return /_owner$/.test( key ) } ) ], [ 'clients', keys.filter( function( key ) { return /_owner$/.test( key ) === false } ) ] ].filter( function( group ) { return group[1].length > 0 } );
	};
	/**
	 *	The block of the mail notifications of the settings: every notification that is the owner's to switch, in two groups - the mails to the owner and the mails to the customers - and the technique that
	 *	sends them (the address, the sender, the mail cap), read only, and one Save (and Ctrl+S) for the switches; only the switches that were changed are posted. Without a switch of a package there is no group and no button
	 *
	 *	@param		{Object}	mails						As crm/notifications has it: switches, owner, sender, cap
	 *	@param		{Object}	sibling					The form of the screen next to it, which keeps the page's guard when this one is saved
	 *
	 *	@return		{Object}
	 */
	const mailNotesCard = function( mails, sibling, carry ) {

		const form = { controls : {}, notes : {}, dirty : false, form : true };
		const stored = Object.assign( {}, mails.switches );
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: 'mailnotes-save', title: t( 'save.shortcut' ), text: t( 'mailnotes.save' ) } );
		const mine = state.view;
		const groups = mailGroups( mails );
		const keys = groups.flatMap( function( group ) { return group[1] } );

		const controls = {};

		keys.forEach( function( key ) {
			controls[key] = el( 'input', { id: 'f-mail_'+ key, type: 'checkbox', role: 'switch', checked: stored[key] === '1' } );
		} );

		// Switches that were changed in the block this one replaces: they keep their value where they can still be used
		Object.keys( carry || {} ).forEach( function( key ) {
			if( controls[key] !== undefined && controls[key].disabled === false )
				controls[key].checked = carry[key] === '1';
		} );

		// The switches that were changed, as they are posted
		const changes = function() {

			const posted = {};

			keys.forEach( function( key ) {
				if( ( controls[key].checked ? '1' : '0' ) !== stored[key] )
					posted[key] = controls[key].checked ? '1' : '0';
			} );

			return posted;
		};

		const rows = function( group ) {
			return formList( group[1].map( function( key ) {
				return { name : key, label : has( 'mailnotes.sw-'+ key ) === true ? t( 'mailnotes.sw-'+ key ) : key, control : controls[key], hint : has( 'mailnotes.hint-'+ key ) === true ? t( 'mailnotes.hint-'+ key ) : undefined };
			} ), form );
		};

		const lists = groups.map( rows );

		keys.forEach( function( key ) {
			[ 'input', 'change' ].forEach( function( name ) {
				controls[key].addEventListener( name, function() {
					form.dirty = Object.keys( changes() ).length > 0;

					if( form.dirty === true && ( state.draft === null || state.draft.dirty !== true ) )
						state.draft = form;
				} );
			} );
		} );

		form.dirty = Object.keys( changes() ).length > 0;

		if( form.dirty === true && ( state.draft === null || state.draft.dirty !== true ) )
			state.draft = form;

		const box = el( 'form', { class: 'card pad gap', id: 'mailnotes', novalidate: 'novalidate', on: { submit: function( event ) {

			event.preventDefault();

			const posted = changes();

			if( Object.keys( posted ).length === 0 ) {
				toast( t( 'mailnotes.same' ) );
				return;
			}

			save.disabled = true;

			api( 'crm/notifications-save', posted ).then( function( answer ) {

				save.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {

					const sentence = refuseAt( form, answer, 'error.mailnotes' );
					const carried = changes();

					// A feature that was switched off since the block was drawn: the block is read again, and its row is grey
					if( answer.body !== null && typeof answer.body === 'object' && answer.body.code === 'crm_notify_off' )
						call( 'crm/notifications', {}, 'error.load' ).then( function( now ) {
							if( now.ok === true && state.view === mine )
								state.mailnotes.redraw( now.body, true, carried, { field : answer.body.field, sentence : sentence } );
						} );

					return;
				}

				keys.forEach( function( key ) {
					stored[key] = answer.body.switches[key];
					controls[key].checked = stored[key] === '1';
				} );

				// What the screen redraws from is what is stored now
				Object.assign( mails.switches, answer.body.switches );
				form.dirty = false;

				if( state.draft === form )
					state.draft = sibling.dirty === true ? sibling : null;

				toast( t( 'mailnotes.saved' ) );
			} );
		} } },
			el( 'h3', { text: t( 'mailnotes.title' ) } ), el( 'p', { class: 'state-note', text: extraText( 'mails-note' ) || t( 'mailnotes.note' ) } ),
			el( 'p', { class: 'state-note', id: 'mailnotes-owner' }, mails.owner === '' ? el( 'span', { class: 'bad', text: t( 'mailnotes.owner-none' ) } ) : t( 'mailnotes.owner', mails.owner ), ' ',
				link( '#settings/general/company', t( 'mailnotes.owner-link' ) ) ),
			groups.map( function( group, at ) { return [ el( 'h4', { text: t( 'mailnotes.group-'+ group[0] ) } ), lists[at] ] } ),
			el( 'h4', { text: t( 'mailnotes.group-technique' ) } ),
			kv( [
				[ t( 'mailnotes.sender' ), mails.sender ],
				[ t( 'mailnotes.cap' ), el( 'span', { id: 'mailnotes-cap' }, mails.cap === 0 ? t( 'mailnotes.cap-none' ) : t( 'mailnotes.cap-value', mails.cap ) ) ],
			] ),
			el( 'p', { class: 'state-note', text: t( 'mailnotes.technique-note' ) } ),
			keys.length === 0 ? null : el( 'div', { class: 'actions top' }, save, el( 'span', { class: 'state-note save-what', text: t( 'mailnotes.saves' ) } ) ) );

		submitter( 'mailnotes', box, save, function() { return Object.keys( changes() ).length > 0 } );

		// The block drawn again with what the module has now - after a run of Einrichten, which may have switched a feature on -, unless the person is changing a switch
		state.mailnotes = { view : mine, redraw : function( now, force, carry, refusal ) {

			if( form.dirty === true && force !== true )
				return;

			if( state.draft === form )
				state.draft = sibling.dirty === true ? sibling : null;

			Object.assign( mails, now );

			const drawn = mailNotesCard( mails, sibling, carry );

			box.replaceWith( drawn );

			// The sentence of the refusal stays beside the switch it was said for
			if( refusal !== undefined && refusal.field !== '' && drawn.querySelector( '#f-mail_'+ refusal.field ) !== null )
				drawn.querySelector( '#f-mail_'+ refusal.field ).closest( 'dd' ).querySelector( '.state-note.bad' ).textContent = refusal.sentence;
		} };

		return box;
	};
	/**
	 *	The line over a screen that leads back: a link and, after it, where the person is
	 *
	 *	@param		{string}	href
	 *	@param		{string}	text						The word of the link
	 *	@param		{string}	here						Where the person is: the number of the client, the name of the project
	 *
	 *	@return		{Object}
	 */
	const crumb = function( href, text, here ) {
		return el( 'div', { class: 'crumb' }, link( href, text ), ' › '+ here );
	};
	/**
	 *	The bar over the sections of the tab that is open, which jumps to a block: a button for each, sticky under the top of the page, and the block that is in view is marked. A block is reached with
	 *	scrollIntoView, not with an address - the address names the screen and the tab
	 *
	 *	@param		{number}	mine
	 *	@param		{Array}		blocks					[ [ id, word ] ]
	 *
	 *	@return		{Object}
	 */
	const jumpBar = function( mine, blocks ) {

		const buttons = blocks.map( function( block ) {
			return el( 'button', { type: 'button', class: 'jump-to', dataset: { block: block[0] }, text: block[1], on: { click: function() {

				const target = dc.getElementById( block[0] );

				if( target !== null && target !== undefined && typeof target.scrollIntoView === 'function' )
					target.scrollIntoView( { block : 'start' } );

				// The choice stands until the person scrolls: the page cannot always be brought to the block (two blocks side by side share a top, the last one is above the foot of the page)
				held = block[0];
				mark( block[0] );
			} } } );
		} );

		let held = null;

		const mark = function( id ) {
			buttons.forEach( function( button ) {
				button.classList.toggle( 'on', button.dataset.block === id );

				if( button.dataset.block === id )
					button.setAttribute( 'aria-current', 'true' );
				else
					button.removeAttribute( 'aria-current' );
			} );

			// The bar scrolls sideways on the phone: the block that is open is brought into it
			core.bringIntoRow( bar, buttons.find( function( button ) { return button.dataset.block === id } ) );
		};

		// The block that is in view is the one whose top has passed the bar last - of blocks that share a top the first - and, at the foot of the page, the last block that is in the picture
		const follow = function() {

			if( state.view !== mine ) {
				wn.removeEventListener( 'scroll', follow );
				return;
			}

			if( held !== null )
				return;

			const top = function( id ) {

				const target = dc.getElementById( id );

				return target !== null && target !== undefined && typeof target.getBoundingClientRect === 'function' ? target.getBoundingClientRect().top : null;
			};

			let current = blocks[0][0];
			let best = -Infinity;

			blocks.forEach( function( block ) {

				const at = top( block[0] );

				if( at !== null && at <= 90 && at > best ) {
					current = block[0];
					best = at;
				}
			} );

			const root = dc.documentElement;

			if( typeof wn.scrollY === 'number' && typeof wn.innerHeight === 'number' && root !== undefined && wn.scrollY + wn.innerHeight >= root.scrollHeight - 2 )
				blocks.forEach( function( block ) {

					const at = top( block[0] );

					if( at !== null && at < wn.innerHeight )
						current = block[0];
				} );

			mark( current );
		};

		// Only the person lets go of a choice: by the wheel, a finger or a key
		const release = function() { held = null };

		const bar = el( 'nav', { class: 'jump', id: 'settings-jump', 'aria-label': t( 'settings.jump' ) }, buttons );

		// The screen drawn anew takes the listener of the one before away: there is one
		if( state.follow !== null ) {
			wn.removeEventListener( 'scroll', state.follow );
			[ 'wheel', 'touchmove', 'keydown' ].forEach( function( name ) { wn.removeEventListener( name, state.follow.release ) } );
		}

		follow.release = release;
		state.follow = follow;
		wn.addEventListener( 'scroll', follow, { passive : true } );
		[ 'wheel', 'touchmove', 'keydown' ].forEach( function( name ) { wn.addEventListener( name, release, { passive : true } ) } );
		mark( blocks[0][0] );

		return bar;
	};
	/**
	 *	What the lists remember of the clients, forgotten: the next screen reads them anew
	 *
	 *	@return		{void}
	 */
	const forgetClients = function() {

		Object.assign( state, { summary : null, people : [], row : null, jbody : null, jclients : [], jpick : null } );
		packageStates.forEach( function( own ) { own.reset() } );
	};
	/**
	 *	The dialog of the test phase: what goes and what stays, the word, and the step that deletes. A refusal of the server is said in the dialog, which stays; with the step done
	 *	the toast gives the counts and the page goes to the overview, which reads everything anew
	 *
	 *	@param		{number}	mine
	 *	@param		{Array}		[steps]					The steps the server runs for the packages (crm/settings-get)
	 *
	 *	@return		{Promise}
	 */
	const wipeDialog = function( mine, steps ) {

		const list = function( heading, lines, prefix ) {
			return el( 'div', {}, el( 'h3', { class: 'wipe-head', text: t( heading ) } ), el( 'ul', { class: 'wipe-list', dataset: { wipe: prefix } }, lines.map( function( line ) { return el( 'li', { text : line } ) } ) ) );
		};

		return dialog( {
			title : t( 'wipe.dialog-title' ), text : t( 'wipe.dialog-text' ), wide : true, danger : true, yes : t( 'wipe.yes' ),
			body : el( 'div', { class: 'wipe-lists' }, list( 'wipe.goes', wipeLines( steps ), 'goes' ), list( 'wipe.stays', WIPE_STAYS.map( function( key ) { return t( 'wipe.stays-'+ key ) } ), 'stays' ) ),
			fields : [ { name : 'confirm', label : t( 'wipe.type', t( 'wipe.word' ) ), maxlength : 20, autocomplete : 'off' } ],
			unlock : { field : 'confirm', word : t( 'wipe.word' ) },
			run : function( values ) {

				return wipeRun( mine, values.confirm, [], false );
			},
		} );
	};
	/**
	 *	One press of the test phase: the answer, and what a person does with it. A refusal of the server is said in the dialog, which stays; with the step done the toast gives the counts and the page goes to the overview, which reads
	 *	everything anew. Where the run that was begun holds what a package that is removed - or that is on and has no such step any more - left over, the owner is asked whether to give it up: with the key of the package typed, which sends it
	 *
	 *	@param		{number}	mine
	 *	@param		{string}	word						The word of the test phase, as it was typed
	 *	@param		{Array}		forget					The keys of the steps that are given up
	 *
	 *	@return		{Promise}									null where the dialog is done, else the sentence it says
	 */
	const wipeRun = function( mine, word, forget, discard ) {

		const sent = { confirm : word };

		if( forget.length > 0 )
			sent.forget = forget;

		if( discard === true )
			sent.discard = true;

		return api( 'crm/wipe', sent ).then( function( answer ) {

			// What a package held may be partly deleted after a refusal of a step: the next screen reads them from the server
			forgetClients();

			const body = answer.body !== null && typeof answer.body === 'object' ? answer.body : {};

			// The package, not the step, is what the owner knows: the answer says its key first and the key of its step second
			if( answer.ok !== true && [ 'crm_wipe_held_gone', 'crm_wipe_held_step' ].includes( body.code ) === true && Array.isArray( body.params ) === true && typeof body.params[0] === 'string' && typeof body.params[1] === 'string' ) {

				const pack = body.params[0];
				const step = body.params[1];

				dialog( {
					title : t( 'wipe.giveup-title' ), text : t( body.code === 'crm_wipe_held_gone' ? 'wipe.giveup-gone' : 'wipe.giveup-step', pack ), danger : true, yes : t( 'wipe.giveup-yes' ),
					fields : [ { name : 'confirm', label : t( 'wipe.giveup-type', pack ), maxlength : 40, autocomplete : 'off' } ],
					unlock : { field : 'confirm', word : pack },
					run : function( values ) { return wipeRun( mine, word, forget.concat( [ { step : step, package : values.confirm } ] ), false ) },
				} );

				return null;
			}

			// A row that cannot be read has no way out but the owner's: the rest is thrown away, with the word of the test phase typed again and a sentence that says what is lost
			if( answer.ok !== true && body.code === 'crm_wipe_journal' && discard !== true ) {

				dialog( {
					title : t( 'wipe.discard-title' ), text : t( 'wipe.discard-text' ), danger : true, yes : t( 'wipe.discard-yes' ),
					fields : [ { name : 'confirm', label : t( 'wipe.type', t( 'wipe.word' ) ), maxlength : 20, autocomplete : 'off' } ],
					unlock : { field : 'confirm', word : t( 'wipe.word' ) },
					run : function( values ) { return wipeRun( mine, values.confirm, forget, true ) },
				} );

				return null;
			}

			if( answer.ok !== true )
				return errorText( answer, 'error.wipe' );

			toast( wipeSentence( answer.body ) );

			if( state.view === mine )
				wn.location.hash = '#overview';

			return null;
		} );
	};
	/**
	 *	The block of the test phase, the last of the settings: a sentence and the button that opens the dialog
	 *
	 *	@param		{number}	mine
	 *	@param		{Array}		[steps]					As wipeDialog() has them
	 *
	 *	@return		{Object}
	 */
	const wipeCard = function( mine, steps ) {

		return el( 'div', { class: 'card pad gap danger-zone', id: 'testphase' }, el( 'h3', { text: t( 'wipe.title' ) } ), el( 'p', { class: 'state-note', text: t( 'wipe.note' ) } ),
			el( 'div', { class: 'actions' }, el( 'button', { type: 'button', class: 'btn danger', id: 'wipe-open', text: t( 'wipe.button' ), on: { click: function() { wipeDialog( mine, steps ) } } } ) ) );
	};
	/**
	 *	The section of the numbers: the number the next client and the next project get, the numbers the counting starts at and the name of the owner, in one form with one Save
	 *
	 *	@param		{Object}	kit							See drawSettings()
	 *
	 *	@return		{Object}
	 */
	const numbersCard = function( kit ) {

		const settings = kit.settings;
		const form = kit.form;
		const view = kit.view;
		const mine = kit.mine;

		const numbers = formList( [ 'client', 'project' ].map( function( kind ) {
			return { name : 'start_'+ kind, label : t( 'settings.start-'+ kind ), control : el( 'input', { id: 'f-start_'+ kind, type: 'text', value: settings['start_'+ kind] ?? '1', maxlength: '7', inputmode: 'numeric', autocomplete: 'off', class: 'short' } ) };
		} ), form );

		const owner = formList( [
			{ name : 'seller_name', label : t( 'settings.seller-name' ), control : el( 'input', { id: 'f-seller_name', type: 'text', value: settings.seller_name ?? '', maxlength: '120', autocomplete: 'off' } ), hint : t( 'settings.seller-name-hint' ) },
		], form );

		const next = el( 'p', { class: 'state-note' }, el( 'b', { text: t( 'settings.next' )+ ': ' } ), el( 'span', { class: 'num', id: 'next-numbers', text: [ 'client', 'project' ].map( function( kind ) { return view.next[kind] } ).filter( function( number ) { return typeof number === 'string' && number !== '' } ).join( ' · ' ) } ) );
		const save = el( 'button', { type: 'submit', class: 'btn primary', id: 'settings-save', title: t( 'save.shortcut' ), text: t( 'settings.save' ) } );

		const submit = function( event ) {

			event.preventDefault();
			save.disabled = true;

			api( 'crm/settings-save', valuesOfForm( form ) ).then( function( answer ) {

				save.disabled = false;

				if( state.view !== mine )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'error.settings' );
					return;
				}

				form.dirty = false;
				state.draft = null;
				toast( t( 'settings.saved' ) );

				call( 'crm/settings-get', {}, 'error.load' ).then( function( again ) {
					if( again.ok === true && state.view === mine )
						kit.redraw( again.body );
				} );
			} );
		};

		const box = el( 'form', { id: 'settings-form', novalidate: 'novalidate', on: { submit: submit } },
			head( el( 'span', { class: 'grow' } ), el( 'span', { class: 'state-note save-what', id: 'settings-what', text: t( 'settings.saves' ) } ), save ),
			el( 'div', { class: 'cols' },
				el( 'div', { class: 'card pad', id: 'numbers' }, el( 'h3', { text: t( 'settings.numbers' ) } ), numbers, next, el( 'p', { class: 'state-note', text: t( 'settings.start-note' ) } ), el( 'p', { class: 'state-note', text: t( 'settings.start-ids-note' ) } ) ),
				el( 'div', { class: 'card pad', id: 'seller' }, el( 'h3', { text: t( 'settings.seller' ) } ), owner, el( 'p', { class: 'state-note', text: t( 'settings.seller-note' ) } ) ) ) );

		submitter( 'settings', box, save, function() { return form.dirty === true } );

		return box;
	};
	/**
	 *	The tabs keep to the pattern of the keys of a tab strip: the arrows, Home and End move the focus along the tabs, Enter (and the space bar) opens the one that has it. A tab is a link to its address, so that
	 *	a draft that holds what was not stored makes the page ask first, as it does for every link away from the screen
	 *
	 *	@param		{Object}	strip
	 *
	 *	@return		{void}
	 */
	const settingsStripKeys = function( strip ) {

		strip.addEventListener( 'keydown', function( event ) {

			const all = Array.from( strip.querySelectorAll( '[role="tab"]' ) );
			const at = all.indexOf( dc.activeElement );

			if( at === -1 || event.ctrlKey === true || event.metaKey === true || event.altKey === true )
				return;

			const to = { ArrowRight : ( at + 1 ) % all.length, ArrowLeft : ( at + all.length - 1 ) % all.length, Home : 0, End : all.length - 1 }[event.key];

			if( to !== undefined ) {
				event.preventDefault();
				all.forEach( function( one, index ) { one.tabIndex = index === to ? 0 : -1 } );
				all[to].focus();
				core.bringIntoRow( strip, all[to] );
				return;
			}

			if( event.key === ' ' || event.key === 'Enter' ) {
				event.preventDefault();

				// The screen is drawn anew with the tab in focus; a tab that is open draws nothing, and a refusal of the guard leaves the screen as it is (see the click of the page)
				state.tabFocus = all[at].getAttribute( 'aria-selected' ) !== 'true';
				all[at].click();
			}
		} );
	};
	/**
	 *	The screen of the settings: the heading, the tabs, and a panel for each tab with its sections - every tab is built, the one that is chosen is shown - and over the sections of the tab that is open the bar that jumps to them.
	 *	All panels are built so that the sections reach one another (a section may draw the block of another again) and the guard of the page sees every form
	 *
	 *	@param		{Object}	view						As crm/settings-get has it
	 *	@param		{Object}	mails						As crm/notifications has it
	 *	@param		{Object}	[where]					tab (the key of the tab), section (the id of the block to bring into view)
	 *
	 *	@return		{void}
	 */
	const drawSettings = function( view, mails, where ) {

		const form = formOf();
		const mine = state.view;
		const waits = [];
		let building = '';
		const tabs = settingsTabs();
		const asked = where !== undefined && typeof where.tab === 'string' ? where.tab : '';
		const chosen = tabs.find( function( tab ) { return tab.key === asked } ) ?? tabs[0];

		const kit = Object.assign( kitOf( mine, form ), {
			view : view, settings : view.settings, mails : mails,
			redraw : function( again ) { drawSettings( again, mails, { tab : chosen === undefined ? '' : chosen.key } ) },
			// A section that fills itself later hands the promise of it over: an address that names a block is reached again when all of them are done
			wait : function( promise ) {

				// A promise that is refused is the section's to say; here it is logged and counts as done - in a tab that is not open as well
				const done = Promise.resolve( promise ).catch( function( error ) { console.error( error ); return null } );

				if( building === chosen.key )
					waits.push( done );
			},
		} );

		reveal();
		state.main.appendChild( head( t( 'settings.title' ) ) );

		if( chosen === undefined )
			return;

		const strip = el( 'div', { class: 'tabs top settings-tabs', id: 'settings-tabs', role: 'tablist', 'aria-label': t( 'settings.tabs' ), dataset: { settingsTabs: 'true' } }, tabs.map( function( tab ) {
			return el( 'a', { href: '#settings/'+ tab.key, role: 'tab', id: 'tab-'+ tab.key, class: tab === chosen ? 'on' : '', 'aria-selected': tab === chosen ? 'true' : 'false', 'aria-controls': 'panel-'+ tab.key, tabIndex: tab === chosen ? 0 : -1, dataset: { tab: tab.key }, text: wordOf( tab.word, t( 'settings.tab-extra' ) ) } );
		} ) );

		settingsStripKeys( strip );
		state.main.appendChild( strip );

		tabs.forEach( function( tab ) {

			building = tab.key;

			const panel = el( 'div', { class: 'settings-panel', id: 'panel-'+ tab.key, role: 'tabpanel', 'aria-labelledby': 'tab-'+ tab.key, hidden: tab !== chosen } );

			tab.sections.forEach( function( section ) {

				let node = null;

				// A section of a feature that fails - its function throws, or answers what is no node - does not take the screen down with it: its place says so, in words for the owner
				try {
					node = section.build( kit );

					if( node !== null && node !== undefined && typeof node.appendChild !== 'function' )
						throw new TypeError( 'The settings section "'+ section.key+ '" built no node' );

					if( node !== null && node !== undefined && node.id === '' )
						node.id = section.key;
				}
				catch( error ) {
					console.error( error );
					node = note( 'bad', t( 'settings.section-failed' ), ' ', el( 'span', { class: 'state-note', text: t( 'settings.section-failed-key', section.key ) } ) );
					node.id = section.key;
				}

				if( node === null || node === undefined )
					return;

				panel.appendChild( node );
			} );

			if( tab === chosen ) {

				const blocks = tab.sections.flatMap( function( section ) { return section.blocks.map( function( block ) { return [ block[0], wordOf( block[1], t( 'settings.jump-extra' ) ) ] } ) } );

				// Nothing to jump between in a tab with one block
				if( blocks.length > 1 )
					panel.insertBefore( jumpBar( mine, blocks ), panel.firstChild );
			}

			state.main.appendChild( panel );
		} );

		state.settingsRedraw = function() {
			if( state.view === mine && ( state.draft === null || state.draft.dirty !== true ) )
				core.route();
		};

		// The tab was opened with the keys: its tab has the focus again, as the screen was drawn anew
		if( state.tabFocus === true ) {
			state.tabFocus = false;
			dc.getElementById( 'tab-'+ chosen.key ).focus();
		}

		// An address that names a block: the block is in view, and its pill marked. A section that fills itself later is reached once more when it is done, unless the person has used the page since
		let touched = false;

		if( state.settingsInput !== null ) {
			state.settingsInput.names.forEach( function( name ) { wn.removeEventListener( name, state.settingsInput.fn ) } );
			state.settingsInput = null;
		}

		const names = [ 'wheel', 'touchstart', 'touchmove', 'keydown', 'pointerdown', 'mousedown' ];
		const touch = function() { touched = true };

		state.settingsInput = { names : names, fn : touch };
		names.forEach( function( name ) { wn.addEventListener( name, touch, { passive : true } ) } );

		const reach = function() {

			const target = dc.getElementById( where.section );
			const panel = dc.getElementById( 'panel-'+ chosen.key );
			const pill = state.main.querySelector( '.jump-to[data-block="'+ where.section+ '"]' );

			if( target !== null && target !== undefined && holds( panel, target ) === true ) {
				if( pill !== null && pill !== undefined )
					pill.click();
				else if( typeof target.scrollIntoView === 'function' )
					target.scrollIntoView( { block : 'start' } );
			}
		};

		if( where !== undefined && typeof where.section === 'string' && where.section !== '' ) {

			reach();

			// What the person does while the section fills - the wheel, a finger, a key, the pointer - is theirs: the page is not taken back to the block then, as the bar of the pills lets go of a choice
			if( waits.length > 0 )
				Promise.all( waits ).then( function() {
					if( state.view === mine && touched === false )
						reach();
				} );
		}
	};
	/**
	 *	The tabs and the sections of the core. Ranks run in tens, so that a feature (rank 500 unless it says) stands between Mails and System, or anywhere it asks to
	 *
	 *	@return		{void}
	 */
	const registerSettings = function() {

		const word = function( key ) { return function() { return t( key ) } };
		const tab = function( key, rank ) { addSettingsTab( { key : key, word : word( 'settings.tab-'+ key ), rank : rank } ) };

		[ [ 'general', 100 ], [ 'mails', 400 ], [ 'system', 900 ] ].forEach( function( one ) { tab( one[0], one[1] ) } );

		const section = function( key, tabKey, rank, jump, build ) { addSettingsSection( { key : key, tab : tabKey, word : word( 'settings.jump-'+ jump ), rank : rank, build : build } ) };

		section( 'company-holder', 'general', 10, 'company', function( kit ) {

			const holder = el( 'div', { class: 'gap', id: 'company-holder' } );

			kit.wait( drawCompany( holder, kit.view, kit.form, kit.mine ) );

			return holder;
		} );
		section( 'brand', 'general', 20, 'brand', function( kit ) { return brandCard( kit.settings, kit.form ) } );
		section( 'crm-host', 'general', 30, 'crm-host', function( kit ) { return hostCard( CRM_HOST, kit.settings, kit.form ) } );
		section( 'app-host', 'general', 35, 'app-host', function( kit ) {

			// The rows are the server's: where the workbench, the CRM and - where a package has one - its own are, each with the word of its key
			return hostCard( APP_HOST, kit.settings, kit.form, kit.view.addresses.map( function( one ) { return [ has( 'app-host.row-'+ one.key ) === true ? t( 'app-host.row-'+ one.key ) : one.key, one.address === '' ? t( 'app-host.none' ) : one.address ] } ) );
		} );
		addSettingsSection( { key : 'numbers', tab : 'general', rank : 40, build : numbersCard, blocks : [ [ 'numbers', word( 'settings.jump-numbers' ) ], [ 'seller', word( 'settings.jump-seller' ) ] ] } );

		section( 'mailnotes', 'mails', 10, 'mailnotes', function( kit ) { return mailNotesCard( kit.mails, kit.form ) } );

		section( 'access', 'system', 10, 'access', function() {
			return el( 'div', { class: 'card pad gap', id: 'access' }, el( 'h3', { text: t( 'settings.access' ) } ),
				el( 'p', { class: 'state-note', text: t( 'settings.access-text', state.perm ) } ),
				el( 'p', { class: 'state-note' }, link( state.links.users, t( 'settings.access-link' ), { target : '_blank', rel : 'noopener' } ) ) );
		} );

		// What deletes the movements of the clients for the test phase: the last block of the last tab
		section( 'testphase', 'system', 20, 'testphase', function( kit ) { return wipeCard( kit.mine, kit.view.wipe === undefined ? [] : kit.view.wipe.steps ) } );
	};
	registerSettings();
	// The addresses of the pages of the core: for each page the function that reads an address of it, null where the address is none of its
	const coreParse = {

		overview : function( text ) {
			return text === 'overview' ? { screen : 'overview' } : null;
		},

		clients : function( text ) {

			if( text === 'clients' )
				return { screen : 'clients' };

			if( text === 'client-new' )
				return { screen : 'clientnew' };

			const found = new RegExp( '^client-([1-9][0-9]{0,9})(?:-('+ tabPattern( 'client' )+ '))?$' ).exec( text );

			return found === null ? null : { screen : 'client', id : Number( found[1] ), tab : found[2] === undefined ? '' : found[2] };
		},

		projects : function( text ) {

			if( text === 'projects' )
				return { screen : 'projects' };

			let found = /^projects-new(?:-k([1-9][0-9]{0,9}))?$/.exec( text );

			if( found !== null )
				return { screen : 'projectnew', client : found[1] === undefined ? '' : found[1] };

			found = new RegExp( '^project-([A-Za-z0-9_]{1,40})(?:-('+ tabPattern( 'project' )+ '))?$' ).exec( text );

			return found === null ? null : { screen : 'project', id : found[1], tab : found[2] === undefined ? '' : found[2] };
		},

		// The screen of the values with its types
		values : function( text ) {

			if( text === 'values' )
				return { screen : 'values', type : '', view : 'list' };

			const found = /^values\/([a-z][a-z0-9_-]{0,39})(?:\/(new)|\/e\/([A-Za-z0-9][A-Za-z0-9_-]{0,99}))?$/.exec( text );

			return found === null ? null : { screen : 'values', type : found[1], view : found[2] !== undefined ? 'new' : ( found[3] !== undefined ? 'edit' : 'list' ), uri : found[3] };
		},

		settings : function( text ) {

			if( text === 'settings' )
				return { screen : 'settings' };

			// A tab of the settings, and the block of the tab to bring into view
			const found = /^settings\/([a-z][a-z0-9-]{0,39})(?:\/([a-z][a-z0-9-]{0,39}))?$/.exec( text );

			if( found === null )
				return null;

			return found[2] === undefined ? { screen : 'settings', tab : found[1] } : { screen : 'settings', tab : found[1], section : found[2] };
		},

		setup : function( text ) {
			return text === 'setup' ? { screen : 'setup' } : null;
		},
	};
	// The functions that draw the screens of the pages of the core, by the name an address gives them
	const coreScreens = { overview : overviewScreen, clients : clientsScreen, client : clientScreen, clientnew : clientNewScreen, projects : projectsScreen, projectnew : projectNewScreen, project : projectScreen,
		values : valuesScreen, settings : settingsScreen, setup : setupScreen };
	/**
	 *	The core registers its own entries through the functions a package uses. Ranks run in tens for the rail, the tiles, the blocks and the steps, and in hundreds for the tabs of the cards, so that an entry of
	 *	a package (rank 500 unless it says) stands after the last of the core, or anywhere it asks to
	 *
	 *	@return		{void}
	 */
	const registerCore = function() {

		const word = function( key ) { return function() { return t( key ) } };
		const countWord = function( key ) { return function( kit, number ) { return t( key, number ) } };

		// The pages: the key, the first address is the one the rail links to
		const page = function( key, hashes, title ) {
			addPage( { key : key, hash : hashes, title : title, parse : coreParse[key], draw : function( kit, where ) { return coreScreens[where.screen]( where, kit.mine ) } } );
		};

		page( 'overview', [ 'overview' ], word( 'overview.title' ) );
		page( 'clients', [ 'clients', 'client' ], word( 'clients.title' ) );
		page( 'projects', [ 'projects', 'project' ], word( 'projects.title' ) );
		page( 'settings', [ 'settings' ], word( 'settings.title' ) );
		page( 'values', [ 'values' ], word( 'values.title' ) );
		page( 'setup', [ 'setup' ], word( 'setup.title' ) );

		// The rail
		const entry = function( key, rank, label, extra ) { addMenu( Object.assign( { key : key, word : word( label ), rank : rank, page : key }, extra || {} ) ) };

		entry( 'overview', 10, 'nav.overview' );
		entry( 'clients', 30, 'nav.clients', { count : function() { return state.summary !== null && state.summary.clients > 0 ? String( state.summary.clients ) : '' } } );
		entry( 'projects', 40, 'nav.projects' );
		entry( 'settings', 900, 'nav.settings' );
		entry( 'values', 950, 'nav.values', { visible : function() { return state.perms.elements === true } } );

		// The tabs of the card of a client...
		const tab = function( kind, key, rank, count, draw ) { addCardTab( kind, { key : key, word : word( kind+ '-tab.'+ key ), rank : rank, count : count, draw : draw } ) };
		const drawn = function( make ) { return function( kit, record ) { return make( record ) } };

		tab( 'client', 'projects', 200, function( record ) { return record.projects !== null ? record.projects.length : null }, drawn( tabClientProjects ) );
		tab( 'client', 'notes', 500, null, drawn( tabClientNotes ) );
		tab( 'client', 'logbook', 600, null, drawn( tabClientLogbook ) );

		// ...and of a project
		tab( 'project', 'tasks', 300, function( record ) { return record.tasks }, drawn( tabProjectTasks ) );
		tab( 'project', 'shares', 400, function( record ) { return record.shares.length }, drawn( tabProjectShares ) );
		tab( 'project', 'logbook', 500, null, drawn( tabProjectLogbook ) );

		// The tile of the overview: the clients that are active. What the money and the dates say is the packages' to add
		addTile( { key : 'clients', rank : 10, draw : function( kit, loaded ) {

			const count = loaded.summary.clients;

			return tile( t( 'tile.clients' ), String( count ), t( count === 1 ? 'tile.clients-one' : 'tile.clients-many' ), '' );
		} } );

		// The blocks of the overview: what needs the owner, then the device
		addBlock( { key : 'needs', rank : 10, size : 'half', draw : function( kit, loaded ) { return needsCard( loaded ) } } );
		addBlock( { key : 'device', rank : 30, draw : function() { return deviceCard() } } );

		// The keys that go to a screen of the core: the letter is a word of the language
		addKey( { key : function() { return t( 'keys.go-overview' ) }, href : '#overview', word : word( 'keys.word-overview' ) } );
		addKey( { key : function() { return t( 'keys.go-clients' ) }, href : '#clients', word : word( 'keys.word-clients' ) } );
		addKey( { key : function() { return t( 'keys.go-projects' ) }, href : '#projects', word : word( 'keys.word-projects' ) } );

		// The search over the clients and over the projects
		addSearch( { key : 'client', rank : 10, load : function( kit ) {
			return kit.api( 'crm-clients/list' ).then( function( answer ) { return answer.ok === true ? answer.body.clients : [] } );
		}, hits : function( kit, rows, holds ) {
			return rows.filter( function( one ) { return holds( [ one.number, one.title, one.company, one.name, one.email, one.city ] ) } ).map( function( one ) {
				return { label : clientTitle( one ), hint : one.number, href : '#client-'+ one.id };
			} );
		} } );
		addSearch( { key : 'project', rank : 20, load : function( kit ) {
			return kit.api( 'crm-projects/list' ).then( function( answer ) { return answer.ok === true && answer.body.missing !== true ? answer.body.projects : [] } );
		}, hits : function( kit, rows, holds ) {
			return rows.filter( function( one ) { return holds( [ one.id, projectLabel( one.id ), one.title, one.client_title, one.client ] ) } ).map( function( one ) {
				return { label : one.title !== '' ? one.title : projectLabel( one.id ), hint : projectLabel( one.id )+ ' · '+ one.client_title, href : '#project-'+ one.id };
			} );
		} } );

		// What the test phase deletes: the lines of the dialog and the words of the toast
		addWipe( { key : 'clients', rank : 10, goes : word( 'wipe.goes-clients' ), count : countWord( 'wipe.count-clients' ) } );
		addWipe( { key : 'projects', rank : 20, goes : word( 'wipe.goes-projects' ), count : countWord( 'wipe.count-projects' ) } );
		addWipe( { key : 'records', rank : 30, goes : word( 'wipe.goes-records' ), count : countWord( 'wipe.count-records' ) } );
		addWipe( { key : 'logbook', rank : 40, goes : word( 'wipe.goes-logbook' ), count : countWord( 'wipe.count-logbook' ) } );
		addWipe( { key : 'shares', rank : 50, goes : word( 'wipe.goes-shares' ), count : countWord( 'wipe.count-shares' ) } );
		addWipe( { key : 'numbers', rank : 900, goes : word( 'wipe.goes-numbers' ) } );

		// The app of the tasks
		addApp( { key : 'todo', route : '/crm/todo', home : 'todo.home-', where : todoWhere, draw : function( kit, where ) { todoScreen( where, kit.mine ) } } );
	};
	registerCore();

	Object.assign( core, { complete, crumb, fieldKind, hostCard, logbookSentence, phaseWord, statusLine, valueDraftOf, valueKey, valuePosts, visibleClients } );

} )( window, document );
