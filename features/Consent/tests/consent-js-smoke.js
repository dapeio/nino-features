/**
 *	Nino
 *	consent-js-smoke.js		What consent.js does over a dom stand-in: that a stored
 *												choice is what the banner's checkboxes show when it is
 *												reopened (and that "Save selection" then keeps it), that
 *												a category the site does not offer is never allowed
 *												whatever the cookie says, that placeholders are released
 *												for what is allowed and for nothing else, that the
 *												banner of an older base install is removed, that the
 *												reopen button of a page with no banner is hidden, where
 *												the focus goes when the banner is reopened and
 *												answered, what a modal look makes of the page (the
 *												elements beside the banner inert, the focus taken in
 *												and the Tab key kept inside, all of it given back
 *												after a choice), that the script starts when Nino.js says the
 *												page is ready, and that the cookie it reads and writes is
 *												the one the banner names, kept for the days it names,
 *												and that withdrawing a category takes its blocks back
 *												and reloads the page once where something of it ran.
 *
 *												No jsdom, no dependency: the element stand-in the other
 *												feature tests build, with the little selector matching
 *												this script needs, so this runs with nothing but node -
 *												and the kernel's own Nino.js and Nino.ui.js, which the
 *												script starts through (Nino.events) and reads its
 *												banner by (Nino.ui.options()): NINO_ROOT names the
 *												checkout, else it is three levels up, where a project
 *												keeps it.
 *
 *	Usage: node features/Consent/tests/consent-js-smoke.js
 *	       NINO_ROOT=../nino node features/Consent/tests/consent-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let checks = 0;
let failures = 0;

function check( label, condition ) {
	checks++;
	if( condition === true ) {
		console.log( '  ok  - '+ label );
		return;
	}
	failures++;
	console.log( 'FAIL  - '+ label );
}

const source = fs.readFileSync( path.join( __dirname, '../assets/consent.js' ), 'utf8' );
const checkout = process.env.NINO_ROOT || path.join( __dirname, '../../..' );
const kernel = fs.readFileSync( path.join( checkout, '_nino/Nino.js' ), 'utf8' );
const kernelUi = fs.readFileSync( path.join( checkout, '_nino/Nino.ui.js' ), 'utf8' );

// The element that has the focus, as document.activeElement says it
let focusedNow = null;

/**
 *	One element, with just enough of the interface consent.js reaches for
 */
function element( tag, attributes ) {

	const el = {
		tagName				: tag,
		attributes		: Object.assign( {}, attributes || {} ),
		dataset				: {},
		hidden				: false,
		disabled			: Object.prototype.hasOwnProperty.call( attributes || {}, 'disabled' ),
		checked				: Object.prototype.hasOwnProperty.call( attributes || {}, 'checked' ),
		textContent		: '',
		children			: [],
		parentNode		: null,
		listeners			: {},
		focused				: 0,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		removeEventListener	: function( type, fn ) { this.listeners[type] = ( this.listeners[type] || [] ).filter( function( f ) { return f !== fn } ) },
		appendChild			: function( child ) { child.parentNode = this; this.children.push( child ); return child },
		insertBefore		: function( child, before ) {
			child.parentNode = this;
			const at = before === null ? this.children.length : this.children.indexOf( before );
			this.children.splice( at, 0, child );
			return child;
		},
		removeChild			: function( child ) {
			this.children = this.children.filter( function( c ) { return c !== child } );
			child.parentNode = null;
			return child;
		},
		focus						: function() { this.focused++; focusedNow = this },
		click						: function() { ( this.listeners['click'] || [] ).forEach( function( fn ) { fn.call( el, {} ) } ) },
		querySelector		: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) { return descendants( this ).filter( function( e ) { return matches( e, selector ) } ) },
	};

	Object.defineProperty( el, 'nextSibling', { get : function() {
		if( this.parentNode === null )
			return null;
		return this.parentNode.children[ this.parentNode.children.indexOf( this ) + 1 ] || null;
	} } );

	// A script element takes its address through the property, like a real one
	Object.defineProperty( el, 'src', { set : function( value ) { this.attributes['src'] = value }, get : function() { return this.attributes['src'] } } );

	return el;
}

function descendants( root ) {
	let found = [];
	root.children.forEach( function( child ) { found.push( child ); found = found.concat( descendants( child ) ) } );
	return found;
}

/**
 *	A selector of the shapes consent.js uses: tag, .class, [attr], [attr="value"]
 *	and :checked, in any combination
 */
function matches( el, selector ) {

	let rest = selector;
	let checked = false;

	if( rest.slice( -8 ) === ':checked' ) {
		checked = true;
		rest = rest.slice( 0, -8 );
	}

	const tag = /^[a-z]+/.exec( rest );
	if( tag !== null && el.tagName !== tag[0] )
		return false;

	const classes = rest.match( /\.[a-z-]+/g ) || [];
	for( const c of classes )
		if( ( el.attributes['class'] || '' ).split( /\s+/ ).indexOf( c.slice( 1 ) ) === -1 )
			return false;

	const attributes = rest.match( /\[[^\]]+\]/g ) || [];
	for( const a of attributes ) {
		const parts = /^\[([^=\]]+)(?:="([^"]*)")?\]$/.exec( a );
		if( parts === null || el.getAttribute( parts[1] ) === null || ( parts[2] !== undefined && el.getAttribute( parts[1] ) !== parts[2] ) )
			return false;
	}

	return checked === false || el.checked === true;
}

/**
 *	One banner as [consent] writes it, with a checkbox for each category given
 *
 *	@param		{Array}		categories		The optional ones the site offers
 *	@param		{Object}	[attributes]	What it carries instead: data-consent-cookie, data-consent-days
 */
function banner( categories, attributes ) {

	const el = element( 'div', Object.assign( { 'class' : 'nino-consent', 'data-consent-cookie' : 'nino_consent', 'data-consent-days' : '180', 'role' : 'dialog' }, attributes || {} ) );
	el.hidden = true;

	const box = function( category, extra ) {
		el.appendChild( element( 'input', Object.assign( { 'type' : 'checkbox', 'data-consent-category' : category }, extra || {} ) ) );
	};

	box( 'necessary', { checked : '', disabled : '' } );
	categories.forEach( function( category ) { box( category ) } );

	[ 'accept-all', 'necessary-only', 'save' ].forEach( function( action ) {
		el.appendChild( element( 'button', { 'class' : 'nino-consent-btn', 'data-consent-action' : action } ) );
	} );

	return el;
}

/**
 *	A page with the given elements in its body and consent.js loaded over it
 *
 *	@param		{Array}		elements
 *	@param		{Object}	options		{ cookie, readyState, hold, modal }
 */
function page( elements, options ) {

	options = options || {};
	focusedNow = null;

	const root = element( 'html', {} );
	const body = element( 'body', {} );
	// The classes Nino.ui.js puts on the body when the page is ready
	body.classList = { add : function() {}, remove : function() {} };
	root.appendChild( body );
	elements.forEach( function( e ) { body.appendChild( e ) } );

	const listeners = {};
	const fired = [];
	const lines = [];
	const timeouts = [];
	let reloads = 0;
	let cookies = options.cookie !== undefined ? options.cookie : {};

	const dc = {
		body						: body,
		documentElement	: root,
		readyState			: options.readyState || 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		getElementById	: function() { return null },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		removeEventListener	: function( type, fn ) { listeners[type] = ( listeners[type] || [] ).filter( function( f ) { return f !== fn } ) },
		dispatchEvent		: function( ev ) {
			fired.push( ev );
			( listeners[ev.type] || [] ).forEach( function( fn ) { fn( ev ) } );
		},
		querySelector		: function( selector ) { return body.querySelector( selector ) },
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
	};

	Object.defineProperty( dc, 'activeElement', { get : function() { return focusedNow === null ? body : focusedNow } } );

	Object.defineProperty( dc, 'cookie', {
		get : function() { return Object.keys( cookies ).map( function( name ) { return name+ '='+ encodeURIComponent( cookies[name] ) } ).join( '; ' ) },
		set : function( line ) {
			lines.push( line );
			const pair = /^([^=]+)=([^;]*)/.exec( line );
			cookies = Object.assign( {}, cookies );
			cookies[pair[1]] = decodeURIComponent( pair[2] );
		},
	} );

	function CustomEvent( type, init ) {
		this.type = type;
		this.detail = ( init || {} ).detail;
	}

	// What Nino.js and Nino.ui.js need to load and to start their callbacks: a
	// navigator, the events of the window, a location and a timer Nino.js asks
	// for a page that is ready already
	const sandbox = {
		console : console, document : dc, CustomEvent : CustomEvent, location : { protocol : 'https:', hash : '', reload : function() { reloads++ } },
		navigator : { userAgent : 'node' },
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		requestAnimationFrame : function() {},
		setTimeout : function( fn ) { timeouts.push( fn ); return timeouts.length },
		// The look is modal where the page says --consent-modal: 1 (options.modal), as consent.css and design/dialog.css do
		getComputedStyle : function() { return { getPropertyValue : function( name ) { return name === '--consent-modal' && options.modal === true ? ' 1' : '' } } },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( kernel, context, { filename : 'Nino.js' } );
	vm.runInContext( kernelUi, context, { filename : 'Nino.ui.js' } );
	vm.runInContext( source, context, { filename : 'consent.js' } );

	// A page that is ready when the scripts run starts the callbacks a moment
	// later, from the timer Nino.js asked for; one still loading starts them
	// when the document says so. What a callback throws is thrown again from a
	// timer, and that one is run here too - a script that fails fails the run
	const start = function() {
		while( timeouts.length > 0 )
			timeouts.shift()();
	};

	if( dc.readyState !== 'loading' && options.hold !== true )
		start();

	return {
		document : dc, root : root, body : body, fired : fired, reloads : function() { return reloads }, start : start, listeners : listeners, lines : lines,
		stored : function( name ) { return cookies[ name || 'nino_consent' ] },
		boxes : function( el ) {
			const found = {};
			el.querySelectorAll( '[data-consent-category]' ).forEach( function( b ) { found[ b.getAttribute( 'data-consent-category' ) ] = b.checked } );
			return found;
		},
		action : function( el, name ) { el.querySelector( '[data-consent-action="'+ name+ '"]' ).click() },
		scripts : function() { return body.querySelectorAll( 'script' ).filter( function( s ) { return s.getAttribute( 'type' ) !== 'text/plain' } ) },
	};
}

function placeholder( category, src ) {
	const attributes = { 'type' : 'text/plain', 'data-consent' : category };
	if( src !== undefined )
		attributes['data-src'] = src;
	return element( 'script', attributes );
}

/**
 *	The days a cookie line is kept for, from its expires
 *
 *	@param		{string}	line
 *
 *	@return		{number}
 */
function lifetime( line ) {
	return Math.round( ( Date.parse( /; expires=([^;]+)/.exec( line )[1] ) - Date.now() ) / 864e5 );
}


// --- The prefill ---------------------------------------------------------------

const first = banner( [ 'statistics', 'marketing' ] );
const firstVisit = page( [ first ] );

check( 'a first visit shows the banner, with no optional category checked', first.hidden === false
	&& JSON.stringify( firstVisit.boxes( first ) ) === '{"necessary":true,"statistics":false,"marketing":false}' );

const stored = banner( [ 'statistics', 'marketing' ] );
const returning = page( [ stored, element( 'button', { 'class' : 'nino-consent-open' } ) ], { cookie : { nino_consent : 'necessary,statistics' } } );

check( 'a stored choice keeps the banner hidden', stored.hidden === true );
check( '...and is what its checkboxes show once it is reopened', JSON.stringify( returning.boxes( stored ) ) === '{"necessary":true,"statistics":true,"marketing":false}' );

returning.body.querySelector( '.nino-consent-open' ).click();
returning.action( stored, 'save' );
check( '"Save selection" keeps the stored choice instead of quietly revoking it', returning.stored() === 'necessary,statistics' );

const legacy = banner( [ 'statistics', 'marketing' ] );
const old = page( [ legacy ], { cookie : { nino_consent : 'accepted' } } );
check( 'the older banner\'s "accepted" checks every category the site offers', JSON.stringify( old.boxes( legacy ) ) === '{"necessary":true,"statistics":true,"marketing":true}' );

const declined = banner( [ 'statistics' ] );
const refused = page( [ declined ], { cookie : { nino_consent : 'declined' } } );
check( '...and its "declined" the necessary one alone', JSON.stringify( refused.boxes( declined ) ) === '{"necessary":true,"statistics":false}' );

const choosing = banner( [ 'statistics', 'marketing' ] );
const chose = page( [ choosing ], {} );
chose.action( choosing, 'accept-all' );
check( '"Accept all" stores what the banner offers and checks it', chose.stored() === 'necessary,statistics,marketing'
	&& JSON.stringify( chose.boxes( choosing ) ) === '{"necessary":true,"statistics":true,"marketing":true}' );

chose.action( choosing, 'necessary-only' );
check( 'after "Necessary only" the boxes are unchecked again', chose.stored() === 'necessary'
	&& JSON.stringify( chose.boxes( choosing ) ) === '{"necessary":true,"statistics":false,"marketing":false}' );


// --- A category the site does not offer ----------------------------------------

const slim = banner( [ 'statistics' ] );
const offered = page( [ slim, placeholder( 'statistics', 'https://stats.example/s.js' ), placeholder( 'marketing', 'https://ads.example/a.js' ) ], { cookie : { nino_consent : 'necessary,statistics,marketing' } } );

check( 'a category the settings switched off is not in the allowed list, whatever the cookie says',
	offered.root.dataset.consent === 'necessary,statistics' );
check( '...and its placeholder is not released', offered.scripts().length === 1 && offered.scripts()[0].getAttribute( 'src' ) === 'https://stats.example/s.js' );

const everything = banner( [ 'statistics' ] );
const accepted = page( [ everything, placeholder( 'marketing', 'https://ads.example/a.js' ) ], { cookie : { nino_consent : 'accepted' } } );
check( 'the older banner\'s "accepted" does not reach the categories the site switched off either',
	accepted.root.dataset.consent === 'necessary,statistics' && accepted.scripts().length === 0 );

const event = offered.fired[ offered.fired.length - 1 ];
check( 'the nino:consent event carries the list the site accepts', event.type === 'nino:consent' && event.detail.allowed.join( ',' ) === 'necessary,statistics' );

const bare = page( [ placeholder( 'marketing', 'https://ads.example/a.js' ) ], { cookie : { nino_consent : 'necessary,marketing' } } );
check( 'a page with no banner has no offer to read, so the stored list stands there', bare.root.dataset.consent === 'necessary,marketing' && bare.scripts().length === 1 );

const unreleased = page( [ banner( [ 'statistics' ] ), placeholder( 'statistics', 'https://stats.example/s.js' ) ], {} );
check( 'nothing is released before a choice', unreleased.scripts().length === 0 );


// --- The banner of an older base install ---------------------------------------

const leftover = element( 'div', { 'class' : 'nino-cookie-banner nino-cookie-banner--visible' } );
const upgraded = page( [ leftover, banner( [] ) ], {} );
check( 'a .nino-cookie-banner left in the page is removed, so a visitor never sees two', leftover.parentNode === null
	&& upgraded.body.querySelectorAll( '.nino-cookie-banner' ).length === 0 );


// --- The reopen button ---------------------------------------------------------

const orphan = element( 'button', { 'class' : 'nino-consent-open' } );
page( [ orphan ], {} );
check( 'a reopen button on a page with no banner is hidden - it has nothing to open', orphan.hidden === true );

const live = element( 'button', { 'class' : 'nino-consent-open' } );
page( [ live, banner( [ 'statistics' ] ) ], { cookie : { nino_consent : 'necessary' } } );
check( '...and one on a page with a banner is not', live.hidden === false );


// --- Focus ---------------------------------------------------------------------

const dialog = banner( [ 'statistics' ] );
const opener = element( 'button', { 'class' : 'nino-consent-open' } );
const reopened = page( [ opener, dialog ], { cookie : { nino_consent : 'necessary' } } );

check( 'the banner is not focused on load - a stored choice leaves the page as it was', dialog.focused === 0 );

opener.click();
check( 'reopening shows the banner and moves the focus into it', dialog.hidden === false && dialog.focused === 1 );

reopened.action( dialog, 'necessary-only' );
check( 'answering it hides it and gives the focus back to the button that opened it', dialog.hidden === true && opener.focused === 1 );

reopened.action( dialog, 'necessary-only' );
check( 'the focus is returned once, not on every later choice', opener.focused === 1 );

const firstTime = banner( [ 'statistics' ] );
const unused = element( 'button', { 'class' : 'nino-consent-open' } );
const visit = page( [ unused, firstTime ], {} );
visit.action( firstTime, 'necessary-only' );
check( 'a banner nobody reopened moves the focus nowhere, neither in nor out', firstTime.focused === 0 && unused.focused === 0 );


// --- A modal look --------------------------------------------------------------

/**
 *	A keydown of the Tab key, as the document gets it
 */
function tab( run, shift ) {
	const event = { type : 'keydown', key : 'Tab', shiftKey : shift === true, prevented : false, preventDefault : function() { this.prevented = true } };
	run.document.dispatchEvent( event );
	return event;
}

function modalPage( cookie, extra ) {

	const el = banner( [ 'statistics' ] );
	const before = element( 'a', { 'href' : '/' } );
	const wrapper = element( 'div', {} );
	const inside = element( 'p', {} );
	const after = element( 'button', {} );
	const opener = element( 'button', { 'class' : 'nino-consent-open' } );
	const already = element( 'nav', {} );

	already.inert = true;
	wrapper.appendChild( inside );
	wrapper.appendChild( el );

	const run = page( [ before, wrapper, after, opener, already ], Object.assign( { modal : true, cookie : cookie }, extra || {} ) );

	return { run : run, banner : el, before : before, wrapper : wrapper, inside : inside, after : after, opener : opener, already : already };
}

const quiet = banner( [ 'statistics' ] );
const quietLink = element( 'a', { 'href' : '/' } );
const quietPage = page( [ quietLink, quiet ], {} );
check( 'a look that is no modal one holds nothing: the page beside the banner is not inert, the banner says nothing of being modal and the focus is where it was',
	quiet.hidden === false && quietLink.inert !== true && quiet.getAttribute( 'aria-modal' ) === null && quiet.focused === 0 && quietPage.listeners['keydown'] === undefined );

const m = modalPage( undefined );

check( 'a modal look holds the page on a first visit: what stands beside the banner, and beside the element it stands in, is inert - the banner and what it stands in are not',
	m.banner.hidden === false && m.before.inert === true && m.after.inert === true && m.opener.inert === true && m.inside.inert === true
	&& m.wrapper.inert !== true && m.banner.inert !== true );
check( '...the banner says it is modal, and the focus is in it - on its first control', m.banner.getAttribute( 'aria-modal' ) === 'true'
	&& m.banner.querySelector( '[data-consent-category="statistics"]' ).focused === 1 );
check( '...and an element that was inert before the banner came is not the banner\'s to give back', m.already.inert === true );

const statisticsBox = m.banner.querySelector( '[data-consent-category="statistics"]' );
const acceptAll = m.banner.querySelector( '[data-consent-action="accept-all"]' );
const lastButton = m.banner.querySelector( '[data-consent-action="save"]' );

lastButton.focus();
const forward = tab( m.run );
check( 'the Tab key turns round at the end of the banner: from its last control it goes to the first', forward.prevented === true && statisticsBox.focused === 2 );

const backward = tab( m.run, true );
check( '...Shift+Tab at the first goes to the last', backward.prevented === true && lastButton.focused === 2 );

m.before.focus();
const outside = tab( m.run );
check( '...a Tab from outside the banner goes into it, and Shift+Tab to its last control', outside.prevented === true && statisticsBox.focused === 3
	&& ( m.before.focus(), tab( m.run, true ).prevented === true ) && lastButton.focused === 3 );

acceptAll.focus();
const between = tab( m.run );
const letter = { type : 'keydown', key : 'a', shiftKey : false, prevented : false, preventDefault : function() { this.prevented = true } };
lastButton.focus();
m.run.document.dispatchEvent( letter );
check( '...between its controls, and for any other key even at the end of the banner, the browser is left to it', between.prevented === false && letter.prevented === false );

m.run.action( m.banner, 'necessary-only' );
check( 'a choice gives the page back: nothing is inert that the banner made inert, the banner says no more that it is modal and the Tab key is the page\'s again',
	m.banner.hidden === true && m.run.stored() === 'necessary' && m.before.inert === false && m.after.inert === false && m.opener.inert === false && m.inside.inert === false
	&& m.banner.getAttribute( 'aria-modal' ) === null && m.run.listeners['keydown'].length === 0 && tab( m.run ).prevented === false );
check( '...an element that was inert before stays so, and a page that had no focus before the banner has none given back', m.already.inert === true && m.before.focused === 2 && m.after.focused === 0 );

const r = modalPage( { nino_consent : 'necessary' } );
check( 'a stored choice holds nothing, a modal look or not', r.banner.hidden === true && r.before.inert !== true && r.opener.inert !== true && r.banner.focused === 0 && r.run.listeners['keydown'] === undefined );

r.opener.click();
check( 'a banner reopened by its button is held in the same way, the button too: the focus is in the banner, on its first control',
	r.banner.hidden === false && r.opener.inert === true && r.before.inert === true && r.banner.getAttribute( 'aria-modal' ) === 'true'
	&& r.banner.querySelector( '[data-consent-category="statistics"]' ).focused === 1 && r.banner.focused === 0 );

r.run.action( r.banner, 'save' );
check( '...and a choice lifts it and gives the focus back to the button that opened it, which is live to take it', r.opener.inert === false && r.opener.focused === 1 && r.before.inert === false && r.banner.hidden === true );

const h = modalPage( undefined, { hold : true } );
h.before.focus();
h.run.start();
h.run.action( h.banner, 'accept-all' );
check( 'where nothing opened the banner, the focus goes back to where it was before the banner took it', h.before.focused === 2 && h.before.inert === false );


// --- Starting with the kernel --------------------------------------------------

const waiting = page( [ banner( [ 'statistics' ] ), placeholder( 'statistics', 'https://stats.example/s.js' ) ], { cookie : { nino_consent : 'necessary,statistics' }, hold : true } );

check( 'the choice is read when Nino.js calls its ready callbacks, and not when the script is loaded',
	waiting.root.dataset.consent === undefined && waiting.scripts().length === 0 );

waiting.start();
check( '...and then it is on the page, with the placeholder released', waiting.root.dataset.consent === 'necessary,statistics' && waiting.scripts().length === 1 );


// --- The cookie the banner names -----------------------------------------------

const named = banner( [ 'statistics' ], { 'data-consent-cookie' : 'acme_consent', 'data-consent-days' : '90' } );
const own = page( [ named ], {} );
own.action( named, 'accept-all' );
check( 'a choice is stored under the cookie name the banner gives, and for the days it gives',
	own.stored( 'acme_consent' ) === 'necessary,statistics' && own.stored() === undefined && lifetime( own.lines[ own.lines.length - 1 ] ) === 90 );

const remembered = banner( [ 'statistics' ], { 'data-consent-cookie' : 'acme_consent' } );
const known = page( [ remembered ], { cookie : { acme_consent : 'necessary,statistics' } } );
check( '...and is read from that cookie', remembered.hidden === true && known.root.dataset.consent === 'necessary,statistics' );

const unnamed = banner( [ 'statistics' ], { 'data-consent-cookie' : '' } );
const unnamedPage = page( [ unnamed ], {} );
unnamedPage.action( unnamed, 'accept-all' );
check( 'a banner with an empty cookie name keeps the setting\'s default one', unnamedPage.stored() === 'necessary,statistics' );

// 30 days; none; a word; less than none; no attribute at all
const kept = [ '30', '0', 'many', '-5', null ].map( function( days ) {
	const el = banner( [ 'statistics' ] );
	if( days === null )
		delete el.attributes['data-consent-days'];
	else
		el.attributes['data-consent-days'] = days;
	const run = page( [ el ], {} );
	run.action( el, 'accept-all' );
	return lifetime( run.lines[ run.lines.length - 1 ] );
} );
check( 'a lifetime given as a whole number of days is kept, anything else is the 180 days of the default',
	kept.join( ',' ) === '30,180,180,180,180' );


// --- Withdrawing a choice ------------------------------------------------------

function withdrawal( cookie ) {

	const dlg = banner( [ 'statistics', 'marketing' ] );
	const shown = element( 'div', { 'data-consent-show' : 'statistics' } );
	const hiddenBy = element( 'div', { 'data-consent-hide' : 'statistics' } );
	const other = element( 'div', { 'data-consent-show' : 'marketing' } );
	shown.hidden = true;
	const holder = element( 'button', { 'class' : 'nino-consent-open' } );
	const run = page( [ dlg, shown, hiddenBy, other, holder, placeholder( 'statistics', 'https://stats.example/s.js' ) ], { cookie : cookie } );

	return { run : run, dialog : dlg, shown : shown, hiddenBy : hiddenBy, other : other, holder : holder };
}

const give = withdrawal( undefined );
give.run.action( give.dialog, 'accept-all' );
check( 'a choice that gives a category shows its block, hides the block that is hidden by it and releases its script',
	give.shown.hidden === false && give.hiddenBy.hidden === true && give.run.scripts().length === 1 && give.run.reloads() === 0 );

const take = withdrawal( { nino_consent : 'necessary,statistics' } );
take.holder.click();
take.run.action( take.dialog, 'necessary-only' );
check( 'withdrawing it hides the block again and shows the hide-block again',
	take.shown.hidden === true && take.hiddenBy.hidden === false && take.other.hidden === true );
check( '...stores the withdrawn choice and says it on the page', take.run.stored() === 'necessary' && take.run.root.dataset.consent === 'necessary' );
check( '...and reloads the page once, because the script of that category ran', take.run.reloads() === 1 );
check( '...with no focus sent to a page that is going away, and the page given back', take.holder.focused === 0 && take.dialog.hidden === true );

const again = withdrawal( { nino_consent : 'necessary' } );
again.run.action( again.dialog, 'necessary-only' );
check( 'a page that comes up with the withdrawn choice runs nothing of the category and reloads nothing',
	again.run.scripts().length === 0 && again.run.reloads() === 0 && again.shown.hidden === true && again.hiddenBy.hidden === false );

const idle = withdrawal( { nino_consent : 'necessary,marketing' } );
idle.holder.click();
idle.run.action( idle.dialog, 'necessary-only' );
check( 'withdrawing a category that started nothing on this page reloads nothing, and the focus goes back to the button',
	idle.run.reloads() === 0 && idle.run.stored() === 'necessary' && idle.holder.focused === 1 );

const stays = withdrawal( { nino_consent : 'necessary,statistics' } );
stays.holder.click();
stays.dialog.querySelector( '[data-consent-category="marketing"]' ).checked = true;
stays.run.action( stays.dialog, 'save' );
check( 'a choice that keeps the running category reloads nothing',
	stays.run.reloads() === 0 && stays.run.stored() === 'necessary,statistics,marketing' && stays.other.hidden === false );

const frame = element( 'div', { 'class' : 'nino-embed', 'data-embed-consent' : 'external', 'data-embed-src' : 'https://video.example/v' } );
frame.dataset.embedLoaded = 'true';
const framed = page( [ banner( [ 'external' ] ), frame ], { cookie : { nino_consent : 'necessary,external' } } );
framed.action( framed.body.querySelector( '.nino-consent' ), 'necessary-only' );
check( 'an embed that was released by the category goes back to its placeholder by the reload', framed.reloads() === 1 );

const pressed = element( 'div', { 'class' : 'nino-embed', 'data-embed-consent' : 'external', 'data-embed-src' : 'https://video.example/v' } );
pressed.dataset.embedLoaded = 'true';
const pressedPage = page( [ banner( [ 'external' ] ), pressed ], {} );
pressedPage.action( pressedPage.body.querySelector( '.nino-consent' ), 'necessary-only' );
check( '...and one the visitor pressed before any category was given is left alone', pressedPage.reloads() === 0 );

const unloaded = element( 'div', { 'class' : 'nino-embed', 'data-embed-consent' : 'external', 'data-embed-src' : 'http://video.example/v' } );
const unloadedPage = page( [ banner( [ 'external' ] ), unloaded ], { cookie : { nino_consent : 'necessary,external' } } );
unloadedPage.action( unloadedPage.body.querySelector( '.nino-consent' ), 'necessary-only' );
check( 'an embed that never loaded, its source being no https address, causes no reload when its category is withdrawn', unloadedPage.reloads() === 0 );

const basics = element( 'div', { 'data-consent-show' : 'necessary' } );
basics.hidden = true;
page( [ banner( [ 'statistics' ] ), basics ], { cookie : { nino_consent : 'necessary' } } );
check( 'a block that waits for the necessary category is shown on a page that has it', basics.hidden === false );


// --- The file ------------------------------------------------------------------

check( 'the script makes no request and keeps nothing but the one cookie',
	source.indexOf( 'XMLHttpRequest' ) === -1 && source.indexOf( 'fetch(' ) === -1 && source.indexOf( 'localStorage' ) === -1 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
