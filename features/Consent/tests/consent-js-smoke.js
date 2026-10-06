/**
 *	Nino
 *	consent-js-smoke.js		What consent.js does over a dom stand-in: that a stored
 *												choice is what the banner's checkboxes show when it is
 *												reopened (and that "Save selection" then keeps it), that
 *												a category the site does not offer is never allowed
 *												whatever the cookie says, that placeholders are released
 *												for what is allowed and for nothing else, that the
 *												banner of an older base install is removed, that the
 *												reopen button of a page with no banner is hidden, and
 *												where the focus goes when the banner is reopened and
 *												answered.
 *
 *												No jsdom, no dependency: the element stand-in the other
 *												feature tests build, with the little selector matching
 *												this script needs, so this runs with nothing but node.
 *
 *	Usage: node features/Consent/tests/consent-js-smoke.js
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
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
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
		focus						: function() { this.focused++ },
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
 */
function banner( categories ) {

	const el = element( 'div', { 'class' : 'nino-consent', 'data-consent-cookie' : 'nino_consent', 'data-consent-days' : '180', 'role' : 'dialog' } );
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
 *	@param		{Object}	options		{ cookie, readyState }
 */
function page( elements, options ) {

	options = options || {};

	const root = element( 'html', {} );
	const body = element( 'body', {} );
	root.appendChild( body );
	elements.forEach( function( e ) { body.appendChild( e ) } );

	const listeners = {};
	const fired = [];
	let cookies = options.cookie !== undefined ? options.cookie : {};

	const dc = {
		documentElement	: root,
		readyState			: options.readyState || 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		dispatchEvent		: function( ev ) {
			fired.push( ev );
			( listeners[ev.type] || [] ).forEach( function( fn ) { fn( ev ) } );
		},
		querySelector		: function( selector ) { return body.querySelector( selector ) },
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
	};

	Object.defineProperty( dc, 'cookie', {
		get : function() { return Object.keys( cookies ).map( function( name ) { return name+ '='+ encodeURIComponent( cookies[name] ) } ).join( '; ' ) },
		set : function( line ) {
			const pair = /^([^=]+)=([^;]*)/.exec( line );
			cookies = Object.assign( {}, cookies );
			cookies[pair[1]] = decodeURIComponent( pair[2] );
		},
	} );

	function CustomEvent( type, init ) {
		this.type = type;
		this.detail = ( init || {} ).detail;
	}

	const sandbox = { console : console, document : dc, CustomEvent : CustomEvent, location : { protocol : 'https:' } };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'consent.js' } );

	return {
		root : root, body : body, fired : fired,
		stored : function() { return cookies['nino_consent'] },
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


// --- The file ------------------------------------------------------------------

check( 'the script makes no request and keeps nothing but the one cookie',
	source.indexOf( 'XMLHttpRequest' ) === -1 && source.indexOf( 'fetch(' ) === -1 && source.indexOf( 'localStorage' ) === -1 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
