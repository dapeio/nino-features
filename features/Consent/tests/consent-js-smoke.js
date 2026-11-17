/**
 *	Nino
 *	consent-js-smoke.js		What consent.js does over a dom stand-in, one pin for each
 *												thing it holds: nothing a third party sends starts before the
 *												choice (the script of a category waits as a placeholder and
 *												is released for what is allowed and for nothing else), the
 *												choice is kept in the cookie the banner names and is read
 *												back, it can be taken back (a category that ran is reloaded
 *												away once), the button that opens the choice again moves
 *												the focus in and gives it back, a modal look holds the page
 *												and the Tab key and gives both back, and a banner that is
 *												only a picture (consent--inline) is none.
 *
 *												No jsdom, no dependency: a little element stand-in and the
 *												kernel's own Nino.js, which starts the script (Nino.events).
 *												NINO_ROOT names the checkout, else it is three levels up,
 *												where a project keeps it.
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

// The element that has the focus, as document.activeElement says it
let focusedNow = null;

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
		appendChild			: function( child ) { child.parentNode = this; this.children.push( child ); return child },
		insertBefore		: function( child, before ) {
			child.parentNode = this;
			this.children.splice( before === null ? this.children.length : this.children.indexOf( before ), 0, child );
			return child;
		},
		focus						: function() { this.focused++; focusedNow = this },
		click						: function() { ( this.listeners['click'] || [] ).forEach( function( fn ) { fn.call( el, {} ) } ) },
		querySelector		: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) { return descendants( this ).filter( function( e ) { return matches( e, selector ) } ) },
	};

	Object.defineProperty( el, 'nextSibling', { get : function() {
		return this.parentNode === null ? null : this.parentNode.children[ this.parentNode.children.indexOf( this ) + 1 ] || null;
	} } );
	Object.defineProperty( el, 'src', { set : function( value ) { this.attributes['src'] = value }, get : function() { return this.attributes['src'] } } );

	return el;
}

function descendants( root ) {
	let found = [];
	root.children.forEach( function( child ) { found.push( child ); found = found.concat( descendants( child ) ) } );
	return found;
}

/**
 *	A selector of the shapes consent.js uses: tag, .class, :not(.class), [attr], [attr="value"] and :checked
 */
function matches( el, selector ) {

	let rest = selector;
	let checked = false;
	const not = /:not\(\.([a-z-]+)\)/.exec( rest );

	if( not !== null ) {
		if( ( el.attributes['class'] || '' ).split( /\s+/ ).indexOf( not[1] ) !== -1 )
			return false;
		rest = rest.replace( not[0], '' );
	}

	if( rest.slice( -8 ) === ':checked' ) {
		checked = true;
		rest = rest.slice( 0, -8 );
	}

	const tag = /^[a-z]+/.exec( rest );
	if( tag !== null && el.tagName !== tag[0] )
		return false;

	for( const c of rest.match( /\.[a-z-]+/g ) || [] )
		if( ( el.attributes['class'] || '' ).split( /\s+/ ).indexOf( c.slice( 1 ) ) === -1 )
			return false;

	for( const a of rest.match( /\[[^\]]+\]/g ) || [] ) {
		const parts = /^\[([^=\]]+)(?:="([^"]*)")?\]$/.exec( a );
		if( parts === null || el.getAttribute( parts[1] ) === null || ( parts[2] !== undefined && el.getAttribute( parts[1] ) !== parts[2] ) )
			return false;
	}

	return checked === false || el.checked === true;
}

/**
 *	One banner as the component draws it, with a checkbox for each category given
 */
function banner( categories, attributes ) {

	const el = element( 'div', Object.assign( { 'class' : 'consent', 'data-consent-cookie' : 'nino_consent', 'data-consent-days' : '180', 'role' : 'dialog' }, attributes || {} ) );
	el.hidden = true;

	const box = function( category, extra ) {
		el.appendChild( element( 'input', Object.assign( { 'type' : 'checkbox', 'data-consent-category' : category }, extra || {} ) ) );
	};

	box( 'necessary', { checked : '', disabled : '' } );
	categories.forEach( function( category ) { box( category ) } );

	[ 'accept-all', 'necessary-only', 'save' ].forEach( function( action ) {
		el.appendChild( element( 'button', { 'class' : 'consent-button', 'data-consent-action' : action } ) );
	} );

	return el;
}

function placeholder( category, src ) {
	return element( 'script', { 'type' : 'text/plain', 'data-consent' : category, 'data-src' : src } );
}

/**
 *	A page with the given elements in its body and consent.js loaded over it
 */
function page( elements, options ) {

	options = options || {};
	focusedNow = null;

	const root = element( 'html', {} );
	const body = element( 'body', {} );
	root.appendChild( body );
	elements.forEach( function( e ) { body.appendChild( e ) } );

	// What the server writes on the root element: the categories the site offers. Left to the banner of the page where the test says nothing
	const offered = options.offered !== undefined ? options.offered : body.querySelectorAll( '[data-consent-category]' ).map( function( box ) { return box.getAttribute( 'data-consent-category' ) } ).join( ',' );
	if( offered !== null )
		root.setAttribute( 'data-consent-offered', 'necessary,'+ offered );

	const listeners = {};
	const fired = [];
	const lines = [];
	const timeouts = [];
	let reloads = 0;
	let cookies = options.cookie !== undefined ? options.cookie : {};

	const dc = {
		body						: body,
		documentElement	: root,
		readyState			: 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		removeEventListener	: function( type, fn ) { listeners[type] = ( listeners[type] || [] ).filter( function( f ) { return f !== fn } ) },
		dispatchEvent		: function( ev ) { fired.push( ev ); ( listeners[ev.type] || [] ).forEach( function( fn ) { fn( ev ) } ) },
		querySelector		: function( selector ) { return body.querySelector( selector ) },
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
	};

	Object.defineProperty( dc, 'activeElement', { get : function() { return focusedNow === null ? body : focusedNow } } );
	Object.defineProperty( dc, 'cookie', {
		get : function() { if( options.raw !== undefined ) return 'nino_consent='+ options.raw; return Object.keys( cookies ).map( function( name ) { return name+ '='+ encodeURIComponent( cookies[name] ) } ).join( '; ' ) },
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

	const sandbox = {
		console : console, document : dc, CustomEvent : CustomEvent, location : { protocol : 'https:', hash : '', reload : function() { reloads++ } },
		navigator : { userAgent : 'node' },
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		requestAnimationFrame : function() {},
		setTimeout : function( fn ) { timeouts.push( fn ); return timeouts.length },
		// The look is modal where the page says --consent-modal: 1, as the variant dialog of the part does
		getComputedStyle : function() { return { getPropertyValue : function( name ) { return name === '--consent-modal' && options.modal === true ? ' 1' : '' } } },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( kernel, context, { filename : 'Nino.js' } );
	vm.runInContext( source, context, { filename : 'consent.js' } );

	// Nino.js starts the ready callbacks from a timer; what a callback throws is thrown from it, and a script that fails fails the run
	while( timeouts.length > 0 )
		timeouts.shift()();

	return {
		root : root, body : body, fired : fired, reloads : function() { return reloads }, lines : lines, listeners : listeners,
		stored : function() { return cookies['nino_consent'] },
		boxes : function( el ) {
			const found = {};
			el.querySelectorAll( '[data-consent-category]' ).forEach( function( b ) { found[ b.getAttribute( 'data-consent-category' ) ] = b.checked } );
			return found;
		},
		action : function( el, name ) { el.querySelector( '[data-consent-action="'+ name+ '"]' ).click() },
		released : function() { return body.querySelectorAll( 'script' ).filter( function( s ) { return s.getAttribute( 'type' ) !== 'text/plain' } ).map( function( s ) { return s.getAttribute( 'src' ) } ) },
	};
}

// --- The gate: nothing starts before the choice ----------------------------------

const gate = banner( [ 'statistics', 'marketing' ] );
const waiting = page( [ gate, placeholder( 'statistics', 'https://stats.example.com/a.js' ), placeholder( 'marketing', 'https://ads.example.com/b.js' ) ] );

check( 'a first visit shows the banner and releases no script of any category', gate.hidden === false && waiting.released().length === 0 && waiting.stored() === undefined
	&& waiting.root.dataset.consent === 'necessary' );

waiting.action( gate, 'accept-all' );
check( 'accepting all releases the scripts of the categories the banner offers, stores the choice and tells the page', waiting.released().join( ' ' ) === 'https://stats.example.com/a.js https://ads.example.com/b.js'
	&& waiting.stored() === 'necessary,statistics,marketing' && gate.hidden === true && waiting.fired.some( function( e ) { return e.type === 'nino:consent' && e.detail.allowed.length === 3 } ) );

const some = banner( [ 'statistics' ] );
const partial = page( [ some, placeholder( 'statistics', 'https://stats.example.com/a.js' ), placeholder( 'marketing', 'https://ads.example.com/b.js' ) ], { cookie : { nino_consent : 'necessary,statistics,marketing,tracking' } } );
check( 'a category the site does not offer is never allowed, whatever the cookie says, and an unknown one is dropped', partial.released().join( ' ' ) === 'https://stats.example.com/a.js' && partial.root.dataset.consent === 'necessary,statistics' && some.hidden === true );

// --- What the site offers is the page's to say, banner or not -------------------------

const noBanner = page( [ placeholder( 'statistics', 'https://stats.example.com/a.js' ), placeholder( 'external', 'https://maps.example.com/m.js' ) ], { offered : 'statistics', cookie : { nino_consent : 'necessary,statistics,external,tracking' } } );
check( 'a page with no banner gives out what the cookie lists and the page offers, and a category it does not offer stays where it is', noBanner.released().join( ' ' ) === 'https://stats.example.com/a.js' && noBanner.root.dataset.consent === 'necessary,statistics' );
const silent = page( [ placeholder( 'statistics', 'https://stats.example.com/a.js' ) ], { offered : null, cookie : { nino_consent : 'necessary,statistics,marketing' } } );
check( 'a page that names no offer allows the necessary category alone', silent.released().length === 0 && silent.root.dataset.consent === 'necessary' );
const hostile = page( [ placeholder( 'tracking', 'https://t.example.com/a.js' ) ], { offered : 'statistics,tracking', cookie : { nino_consent : 'tracking,statistics' } } );
check( 'a name that is no category is dropped from the cookie and from the offer', hostile.released().length === 0 && hostile.root.dataset.consent === 'necessary,statistics' );
const broken = banner( [ 'statistics' ] );
const undecodable = page( [ broken ], { raw : 'statistics%E0%A4%A' } );
check( 'a cookie that cannot be decoded is no choice: the banner is shown, nothing starts and the script does not stop', broken.hidden === false && undecodable.root.dataset.consent === 'necessary' && undecodable.released().length === 0 );

// --- The choice is kept, read back and taken back --------------------------------

const again = banner( [ 'statistics', 'marketing' ] );
const returning = page( [ again ], { cookie : { nino_consent : 'necessary,statistics' } } );
check( 'a stored choice keeps the banner away and is what its boxes show', again.hidden === true && JSON.stringify( returning.boxes( again ) ) === '{"necessary":true,"statistics":true,"marketing":false}' );

const named = banner( [ 'statistics' ], { 'data-consent-cookie' : 'acme_consent', 'data-consent-days' : '90' } );
const own = page( [ named ] );
own.action( named, 'accept-all' );
const line = own.lines[0];
check( 'the choice is written under the name the banner gives, for the days it gives, to the page and nobody else (SameSite, Secure on https)', /^acme_consent=/.test( line )
	&& Math.round( ( Date.parse( /; expires=([^;]+)/.exec( line )[1] ) - Date.now() ) / 864e5 ) === 90 && line.indexOf( 'SameSite=Lax' ) !== -1 && line.indexOf( 'Secure' ) !== -1 && own.lines.length === 1 );

const taken = banner( [ 'statistics' ] );
const running = page( [ taken, placeholder( 'statistics', 'https://stats.example.com/a.js' ), element( 'div', { 'data-consent-show' : 'statistics' } ) ], { cookie : { nino_consent : 'necessary,statistics' } } );
const shown = running.body.querySelector( '[data-consent-show="statistics"]' );
check( 'a category that is allowed shows its block', shown.hidden === false );
running.action( taken, 'necessary-only' );
check( 'withdrawing it stores the narrower choice, hides the block again and reloads the page once, because its script ran', running.stored() === 'necessary' && shown.hidden === true && running.reloads() === 1 );

const idle = banner( [ 'statistics' ] );
const never = page( [ idle ] );
never.action( idle, 'necessary-only' );
check( 'withdrawing what started nothing reloads nothing', never.reloads() === 0 && never.stored() === 'necessary' );

// --- The button that opens the choice again --------------------------------------

const dialog = banner( [ 'statistics' ] );
const opener = element( 'button', { 'class' : 'consent-settings' } );
const reopened = page( [ dialog, opener ], { cookie : { nino_consent : 'necessary' } } );
opener.click();
check( 'the button shows the banner and moves the focus into it', dialog.hidden === false && dialog.focused === 1 );
reopened.action( dialog, 'necessary-only' );
check( 'a choice hides it and gives the focus back to the button', dialog.hidden === true && opener.focused === 1 );

const lonely = element( 'button', { 'class' : 'consent-settings' } );
const bare = page( [ lonely ] );
lonely.click();
check( 'a button on a page with no banner stays as it is, where a draft shows it, and opens nothing', lonely.hidden === false && lonely.focused === 0 && ( lonely.listeners['click'] || [] ).length === 0 && bare.fired.length === 1 );

// --- A modal look holds the page --------------------------------------------------

const modal = banner( [ 'statistics' ] );
const beside = element( 'main', {} );
const first = modal.querySelector( '[data-consent-category="statistics"]' );
const last = modal.querySelector( '[data-consent-action="save"]' );
const held = page( [ beside, modal ], { modal : true } );
check( 'a modal look makes what stands beside the banner inert, says so and takes the focus into it', beside.inert === true && modal.getAttribute( 'aria-modal' ) === 'true' && modal.hidden === false && focusedNow === first );

focusedNow = last;
let prevented = false;
( held.listeners['keydown'] || [] ).forEach( function( fn ) { fn( { key : 'Tab', shiftKey : false, preventDefault : function() { prevented = true } } ) } );
check( 'the Tab key turns round at the end of the banner and does not leave it', prevented === true && focusedNow === first );

held.action( modal, 'save' );
check( 'a choice gives the page back: nothing is inert, the banner is no longer modal and the Tab key is the page\'s', beside.inert === false && modal.getAttribute( 'aria-modal' ) === null && ( held.listeners['keydown'] || [] ).length === 0 );

// --- A picture of a banner is none -----------------------------------------------

const picture = banner( [ 'statistics' ], { 'class' : 'consent consent--inline' } );
picture.hidden = false;
const shownPage = page( [ picture, placeholder( 'statistics', 'https://stats.example.com/a.js' ) ] );
check( 'a banner shown in the flow of a preview is no banner: it is not shown again, holds nothing and releases nothing', picture.hidden === false && shownPage.released().length === 0 && picture.inert !== true );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
