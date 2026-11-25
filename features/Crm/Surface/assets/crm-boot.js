/**
 *	Crm		The surface of the CRM in the browser, the last of its four files: the router (the hash names the screen, the page that owns the address draws it), the keys, and the start of the page. The handle
 *				Nino.crm.app is there for the tests (without the state of the page); the start waits for the document where it is still being parsed, so that the scripts of the packages, which load after this one, register before the first drawing.
 */

( function( wn, dc ) {

	'use strict';

	const core = wn.Nino.crm.core;
	const { api, appList, appOf, cardList, clear, clientTitle, closeMenus, dialog, drawMenu, el, entryFailed, errorText, fieldKind, guarded, hitsOf, home, homeOffer, homePut, keyTable, kitOf, leave, loading, logbookSentence, mailtoOf, markRail, menuFeed, packageStates, phaseWord, projectLabel, refresh, reset, resolveHash, reveal, shortcut, showPage, state, t, telOf, toast, unsaved, valueDraftOf, valueKey, valuePosts, visibleClients, wipeSentence, wordOf } = core;

	// --- the router --------------------------------------------------------------------------------------------------

	/**
	 *	Which screen a hash names: the page that owns the address reads it
	 *
	 *	@param		{string}	hash
	 *
	 *	@return		{Object}									screen, and id, or category and offer
	 */
	const parse = function( hash ) {
		return resolveHash( hash ).where;
	};
	/**
	 *	On the phone the rail is one row that scrolls: the entry of the screen that is open is brought into it (the row scrolls, the page does not), as the tabs of a project are
	 *
	 *	@return		{void}
	 */
	const showActiveEntry = function() {

		const nav = dc.getElementById( 'nav' );

		if( nav !== null )
			bringIntoRow( nav, nav.querySelector( '.on' ) );
	};
	/**
	 *	Scroll a row that scrolls sideways by the part of an entry that is out of sight (the row scrolls, the page does not)
	 *
	 *	@param		{Object}		row
	 *	@param		{Object|null}	entry
	 *
	 *	@return		{void}
	 */
	const bringIntoRow = function( row, entry ) {

		if( entry === null || entry === undefined || typeof entry.getBoundingClientRect !== 'function' )
			return;

		const box = row.getBoundingClientRect();
		const spot = entry.getBoundingClientRect();

		if( spot.right > box.right )
			row.scrollLeft += spot.right - box.right;
		else if( spot.left < box.left )
			row.scrollLeft -= box.left - spot.left;
	};
	// --- the keys ----------------------------------------------------------------------------------------------------

	// The screens g and a letter lead to

	/**
	 *	The shortcuts the page has, as the dialog lists them: the keys and what they do
	 *
	 *	@return		{Array}										[ [ keys, words ] ]
	 */
	const shortcuts = function() {

		const table = keyTable();
		const rows = Array.from( state.main.querySelectorAll( '.menu' ) ).some( function( one ) { return String( one.querySelector( 'button' )?.className ).split( ' ' ).includes( 'primary' ) } ) === true ? [ [ 'n', t( 'keys.n' ) ] ] : [];

		Object.keys( table ).forEach( function( letter ) { rows.push( [ 'g '+ letter, wordOf( table[letter].word, table[letter].href ) ] ) } );

		return rows.concat( [ [ '↑ ↓', t( 'keys.updown' ) ], [ 'Enter', t( 'keys.enter' ) ], [ '/', t( 'keys.slash' ) ], [ t( 'keys.save-chord' ), t( 'keys.save' ) ], [ '?', t( 'keys.help' ) ], [ 'Esc', t( 'keys.esc' ) ] ] );
	};
	/**
	 *	The list of the shortcuts in a dialog
	 *
	 *	@return		{void}
	 */
	const showKeys = function() {
		dialog( { title : t( 'keys.title' ), text : t( 'keys.text' ), facts : shortcuts(), close : true } );
	};
	/**
	 *	Go to a screen by a key: a draft that holds what was not stored asks first, as a link away from it does
	 *
	 *	@param		{string}	hash
	 *
	 *	@return		{void}
	 */
	const goTo = function( hash ) {
		leave( function() { wn.location.hash = hash } );
	};
	/**
	 *	Whether a node takes what is typed: a field, a select, a text area or an element that is edited - not a box, a radio, a button
	 *
	 *	@param		{Object|null}	node
	 *
	 *	@return		{boolean}
	 */
	const typing = function( node ) {

		if( node === null || node === undefined )
			return false;

		// A box, a choice of a group and a button take no text: the keys are the page's while one of them has the focus
		if( String( node.tagName ) === 'INPUT' && [ 'checkbox', 'radio', 'button', 'submit', 'reset' ].includes( String( node.type ).toLowerCase() ) === true )
			return false;

		return [ 'INPUT', 'TEXTAREA', 'SELECT' ].includes( String( node.tagName ) ) === true || node.isContentEditable === true;
	};
	/**
	 *	The rows of the list on screen that open something: the rows of the first list table (a table that became cards on the phone) with a link to a screen of the page - or with a file to
	 *	open (a link marked data-enter) - and that are not folded away
	 *
	 *	@return		{Array}
	 */
	const listRows = function() {

		const table = Array.from( state.main.querySelectorAll( 'table' ) ).find( function( one ) { return String( one.className ).split( ' ' ).includes( 'cards' ) === true } );

		if( table === undefined )
			return [];

		return Array.from( table.querySelectorAll( 'tr' ) ).filter( function( row ) { return row.parentNode.tagName === 'TBODY' && row.hidden !== true && ( openingLink( row ) !== null || row.querySelector( '[data-enter]' ) !== null ) } );
	};
	/**
	 *	@param		{Object}	row
	 *
	 *	@return		{string|null}							The hash the first link of the row goes to; null for a row with none
	 */
	const openingLink = function( row ) {

		const anchor = Array.from( row.querySelectorAll( 'a' ) ).find( function( one ) { return String( one.getAttribute( 'href' ) ).charAt( 0 ) === '#' } );

		return anchor === undefined ? null : anchor.getAttribute( 'href' );
	};
	/**
	 *	Mark a row of the list as the one the arrows are on: it takes the focus, the others leave the tab order
	 *
	 *	@param		{Array}		rows
	 *	@param		{Object|null}	row
	 *
	 *	@return		{void}
	 */
	const markRow = function( rows, row ) {

		rows.forEach( function( one ) {
			one.setAttribute( 'tabindex', one === row ? '0' : '-1' );
			one.setAttribute( 'aria-selected', one === row ? 'true' : 'false' );
		} );

		state.row = row;

		if( row !== null ) {
			row.focus();

			if( typeof row.scrollIntoView === 'function' )
				row.scrollIntoView( { block : 'nearest' } );
		}
	};
	/**
	 *	A key that is not typed into a field: n opens the menu New, g and the letter of a screen go to it (see Nino.crm.keys), ? lists the keys, the arrows move through
	 *	the rows of a list, Enter opens the row that is marked. Nothing here works with Ctrl, Cmd or Alt, in a field, or while a dialog is open
	 *
	 *	@param		{Object}	event
	 *
	 *	@return		{void}
	 */
	const keys = function( event ) {

		const key = String( event.key );

		if( event.ctrlKey === true || event.metaKey === true || event.altKey === true || event.isComposing === true || typing( event.target ) === true || typing( dc.activeElement ) === true || state.dialogs.children.length > 0 )
			return;

		// An app has no keys: its fields take what is typed, and nothing else is bound
		if( state.app !== '' )
			return;

		if( state.chord === true ) {
			state.chord = false;

			if( keyTable()[key] !== undefined ) {
				event.preventDefault();
				goTo( keyTable()[key].href );
				return;
			}
		}

		if( key === 'g' && event.shiftKey !== true ) {
			state.chord = true;
			wn.setTimeout( function() { state.chord = false }, 1500 );
			return;
		}

		if( key === '?' ) {
			event.preventDefault();
			showKeys();
			return;
		}

		if( key === 'n' && event.shiftKey !== true ) {

			const menu = Array.from( state.main.querySelectorAll( '.menu' ) ).find( function( one ) { return String( one.querySelector( 'button' )?.className ).split( ' ' ).includes( 'primary' ) } );

			if( menu !== undefined ) {
				event.preventDefault();
				closeMenus();
				menu.querySelector( 'button' ).click();
				menu.querySelectorAll( 'button' )[1]?.focus();
			}

			return;
		}

		if( state.main.querySelector( '.menu.open' ) !== null )
			return;

		if( key === 'ArrowDown' || key === 'ArrowUp' ) {

			const rows = listRows();

			if( rows.length === 0 )
				return;

			event.preventDefault();

			const at = rows.indexOf( state.row );

			markRow( rows, rows[key === 'ArrowDown' ? Math.min( rows.length - 1, at + 1 ) : ( at === -1 ? rows.length - 1 : Math.max( 0, at - 1 ) )] );
			return;
		}

		if( key === 'Enter' && state.row !== null && state.row.isConnected !== false ) {

			const active = dc.activeElement;
			const hash = openingLink( state.row );
			const entry = hash === null ? state.row.querySelector( '[data-enter]' ) : null;

			// The focus is on the row (or on nothing): a button or a link in the row has its own Enter
			if( ( hash !== null || entry !== null ) && ( active === null || active === undefined || active === state.row || String( active.tagName ) === 'BODY' ) ) {
				event.preventDefault();

				if( hash !== null )
					goTo( hash );
				else
					entry.click();
			}
		}
	};
	const route = function() {

		// The address changed under a screen that holds what was not stored (the back button, an address typed, a link the page did not see): it is put back and the question is asked; a yes goes on to the address
		const wanted = wn.location.hash;

		if( state.booted === true && wanted !== state.at && state.leaving !== wanted && unsaved() !== null ) {

			wn.history.replaceState( null, '', state.at === '' ? '#' : state.at );
			leave( function() { state.leaving = wanted; wn.location.hash = wanted } );
			return;
		}

		state.leaving = '';
		state.at = wanted;

		// An app has its screen, whatever the address says: the one the app reads from it
		const app = appOf();
		const found = app === null ? resolveHash( wn.location.hash ) : { entry : null, where : app.where === null ? {} : app.where( wn.location.hash ) };

		const where = found.where;

		state.view++;
		state.draft = null;
		state.saver = {};
		state.row = null;
		closeMenus();

		// An item is being saved: the screen that was asked for is drawn once the posts are through, so that it holds what they made
		if( state.busy !== null ) {
			clear( state.main );
			state.main.appendChild( el( 'p', { class: 'state-note', role: 'status', text: t( 'values.saving' ) } ) );
			state.busy.then( function() { route() } );
			return;
		}

		state.lit = found.entry === null ? '' : found.entry.key;
		markRail();
		showActiveEntry();

		// The first showing of the page, whichever screen the address names, once per page: a product whose status is not whole starts on the setup.
		// A status that cannot be read lets the screen draw
		if( where.screen !== 'setup' && state.entered === false && state.app === '' ) {

			const mine = state.view;

			state.entered = true;
			loading();

			api( 'crm/status' ).then( function( answer ) {

				if( state.view !== mine )
					return;

				// The answer is handed on: the setup draws from it and does not ask again
				if( answer.ok === true && answer.body.whole === false ) {
					state.seen = answer.body;
					wn.location.hash = '#setup';
				}
				else
					showFound( found, mine );
			} );

			return;
		}

		showFound( found, state.view );
	};
	/**
	 *	Draw what an address names: a page of the registry, or the screen of the app
	 *
	 *	@param		{Object}	found						entry and where
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const showFound = function( found, mine ) {
		if( found.entry === null ) {

			const made = guarded( function() { return appOf().draw( Object.assign( kitOf( mine ), { main : state.main } ), found.where ) } );

			if( made.ok === false ) {
				reveal();
				state.main.appendChild( entryFailed( 'extension.page-failed', appOf().key ) );
			}
		}
		else
			showPage( found, mine );
	};
	/**
	 *	Start the page: read its data block, set the shell up and draw the screen the hash names
	 *
	 *	@param		{Object}	[block]					The element with the JSON; the page's own by default
	 *
	 *	@return		{void}
	 */
	const boot = function( block ) {

		const data = JSON.parse( ( block || dc.getElementById( 'crm-data' ) ).textContent );

		reset();

		state.base = data.base;
		state.csrf = data.csrf;
		state.day = data.today ?? '';
		state.perm = data.perm;
		state.locale = data.locale === 'de_DE' ? 'de_DE' : 'en_US';
		state.perms = Object.assign( { crm : true, elements : false, text : false }, data.perms );
		state.packages = data.packages !== null && typeof data.packages === 'object' ? data.packages : {};

		// A package that tells the page that its permission is held says so in its data: perm true; the kit asks for it by the key of the package
		Object.keys( state.packages ).forEach( function( key ) {
			if( state.packages[key] !== null && typeof state.packages[key] === 'object' && typeof state.packages[key].perm === 'boolean' && Object.prototype.hasOwnProperty.call( state.perms, key ) === false )
				state.perms[key] = state.packages[key].perm;
		} );

		wn.Nino.crm.format.locale( state.locale );
		packageStates.forEach( function( own ) { own.reset() } );
		state.device = Object.assign( { remembered : false, count : 0 }, data.device );
		state.user = data.user.name;
		state.app = appList.list.some( function( app ) { return app.key === data.app } ) === true ? data.app : '';
		// A word is found by its own key: a key such as constructor or __proto__ is none
		state.texts = Object.assign( Object.create( null ), data.texts );
		state.links = data.links;
		state.main = dc.getElementById( 'main' );
		state.toast = dc.getElementById( 'toast' );
		state.dialogs = dc.getElementById( 'dialogs' );
		// The rail is drawn from the entries of the core, of the scripts of the features and of the server
		menuFeed( data.menu );
		drawMenu();

		wn.addEventListener( 'hashchange', route );

		// The browser's offer to install the page, and an install made another way
		home.offer = null;
		home.card = null;
		wn.addEventListener( 'beforeinstallprompt', homeOffer );
		wn.addEventListener( 'appinstalled', homePut );

		dc.addEventListener( 'click', function( event ) {

			const target = event.target !== null && event.target !== undefined && typeof event.target.closest === 'function' ? event.target : null;

			closeMenus( target === null ? null : target.closest( '.menu' ) );

			// A save is running: the way out waits for it (see route()), and nothing is asked
			if( state.busy !== null && typeof target?.closest === 'function' && target.closest( 'a' ) !== null && String( target.closest( 'a' ).getAttribute( 'href' ) ).charAt( 0 ) === '#' ) {
				toast( t( 'values.saving' ) );
				return;
			}

			const anchor = target === null ? null : target.closest( 'a' );
			const href = anchor === null ? '' : anchor.getAttribute( 'href' );

			// The link to the content of the page: the focus goes to it, the address stays and so does the screen
			if( anchor !== null && anchor.classList.contains( 'skip' ) === true ) {
				event.preventDefault();
				state.main.focus();
				return;
			}

			// A link away from a screen that holds what was not stored asks first
			if( typeof href === 'string' && href.charAt( 0 ) === '#' && unsaved() !== null ) {
				event.preventDefault();
				leave( function() { wn.location.hash = href } );
			}
		} );

		wn.addEventListener( 'beforeunload', function( event ) {
			if( unsaved() !== null ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );

		dc.addEventListener( 'keydown', function( event ) {

			// Ctrl+S or Cmd+S, not with Alt or Shift: as the workbench has it
			if( ( event.ctrlKey === true || event.metaKey === true ) && event.altKey !== true && event.shiftKey !== true && String( event.key ).toLowerCase() === 's' ) {
				shortcut( event );
				return;
			}

			if( event.key === 'Escape' ) {
				state.chord = false;
				closeMenus();
				return;
			}

			const tag = dc.activeElement !== null && dc.activeElement !== undefined ? String( dc.activeElement.tagName ) : '';

			if( event.key === '/' && [ 'INPUT', 'TEXTAREA', 'SELECT' ].includes( tag ) === false ) {
				const field = dc.getElementById( 'q' );

				if( field !== null ) {
					event.preventDefault();
					field.focus();
				}

				return;
			}

			keys( event );
		} );

		// The list of the keys is a button in the foot of the rail, too
		const listing = dc.getElementById( 'keys-open' );

		if( listing !== null && listing !== undefined )
			listing.addEventListener( 'click', showKeys );

		// Sign out: this device is out of the list of the devices that stay signed in and the session ends; the page is the sign-in then
		const leaving = dc.getElementById( 'signout' );

		if( leaving !== null && leaving !== undefined )
			leaving.addEventListener( 'click', function() { leave( function() { api( 'crm/signout' ).then( function() { wn.location.reload() } ) } ) } );

		route();

		// The count in the rail: the first screen reads the books itself when it is the overview
		if( state.app === '' && [ 'overview', 'setup' ].includes( parse( wn.location.hash ).screen ) === false )
			refresh();

		// What a script registers from here on redraws the part of the surface that shows it
		state.booted = true;

		// Nothing is added to Nino.crm and nothing is set again from here on: the names are published while the scripts load
		Object.freeze( wn.Nino.crm );
	};
	/**
	 *	The handle of the page for the tests and for nothing else: the router and the pure functions. The start is not in it - the page starts once, by itself - and neither is the state of the page
	 */
	core.expose( 'app', {
		route : route,
		parse : parse,
		el : el,
		t : t,
		model : Object.freeze( { cardList : cardList, shortcuts : shortcuts, visibleClients : visibleClients, clientTitle : clientTitle, errorText : errorText, logbookSentence : logbookSentence, mailtoOf : mailtoOf, telOf : telOf, hitsOf : hitsOf, projectLabel : projectLabel,
			valueKey : valueKey, valueDraftOf : valueDraftOf, valuePosts : valuePosts, fieldKind : fieldKind, phaseWord : phaseWord, keyTable : keyTable, wipeSentence : wipeSentence } ),
	} );

	Object.assign( core, { bringIntoRow, route } );

	// The files are all there: nothing more is shared
	delete wn.Nino.crm.core;

	// The page starts when the document is parsed and every deferred script has run - the scripts of the features are deferred behind this one and register their entries before the first drawing, which is then the only one
	if( dc.readyState === 'complete' )
		boot();
	else
		dc.addEventListener( 'DOMContentLoaded', function() { boot() } );
} )( window, document );
