/**
 *	Nino
 *	gallery-js-smoke.js	What gallery.js does over a dom stand-in, in a few pins: which links it takes and which it leaves to the
 *											browser, the set of a link, the arrows, the swipe, the focus kept inside and given back, the words of the
 *											controls, an empty caption, and that what a page says is written as text and never as markup.
 *
 *											No jsdom, no dependency: an element stand-in, so this runs with nothing but node. The PHP suite beside it
 *											starts it.
 *
 *	Usage: node features/Gallery/tests/gallery-js-smoke.js
 */

'use strict';

// A feature's own tests are not under the checkout's tests/, which is where
// the shared lint config declares node's globals - so this file declares its
// own, the way typewriter-js-smoke.js does
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

// Reads and writes el.className itself rather than keeping a set beside it:
// the script assigns className directly first and then adds a class, and a
// set that started empty would drop what the assignment put there
function classList( el ) {
	const read = function() { return String( el.className ).split(' ').filter( Boolean ) };
	const write = function( values ) { el.className = values.join(' ') };
	return {
		add : function( value ) { const v = read(); if( v.indexOf( value ) === -1 ) v.push( value ); write( v ) },
		remove : function( value ) { write( read().filter( function( c ) { return c !== value } ) ) },
		contains : function( value ) { return read().indexOf( value ) !== -1 },
		toggle : function( value ) { this.contains( value ) ? this.remove( value ) : this.add( value ) },
	};
}

function findAll( root, predicate ) {
	const found = [];
	( function walk( node ) {
		( node.children || [] ).forEach( function( child ) {
			if( predicate( child ) === true )
				found.push( child );
			walk( child );
		} );
	} )( root );
	return found;
}

function matches( el, selector ) {
	if( selector.charAt( 0 ) === '.' )
		return String( el.className ).split(' ').indexOf( selector.slice( 1 ) ) !== -1;
	if( selector === 'img' || selector === 'button' )
		return el.tagName === selector.toUpperCase();
	// a[data-lightbox]
	const attr = /^a\[(.+)\]$/.exec( selector );
	if( attr !== null )
		return el.tagName === 'A' && el.attributes[attr[1]] !== undefined;
	return false;
}

let focused = null;

function element( tag ) {
	const el = {
		tagName : String( tag ).toUpperCase(),
		className : '',
		textContent : '',
		type : '',
		src : '',
		alt : '',
		hidden : false,
		attributes : {},
		children : [],
		listeners : {},
		parentNode : null,
		appendChild : function( child ) { child.parentNode = el; el.children.push( child ); return child },
		removeChild : function( child ) { el.children = el.children.filter( function( c ) { return c !== child } ); child.parentNode = null },
		setAttribute : function( name, value ) { el.attributes[name] = String( value ) },
		getAttribute : function( name ) { return el.attributes[name] ?? null },
		hasAttribute : function( name ) { return el.attributes[name] !== undefined },
		addEventListener : function( name, fn ) { ( el.listeners[name] = el.listeners[name] || [] ).push( fn ) },
		removeEventListener : function( name, fn ) { el.listeners[name] = ( el.listeners[name] || [] ).filter( function( f ) { return f !== fn } ) },
		focus : function() { focused = el },
		querySelector : function( selector ) { return findAll( el, function( n ) { return matches( n, selector ) } )[0] ?? null },
		querySelectorAll : function( selector ) { return findAll( el, function( n ) { return matches( n, selector ) } ) },
	};
	el.classList = classList( el );
	return el;
}

function fire( el, name, event ) {
	( el.listeners[name] || [] ).slice().forEach( function( fn ) { fn( event ) } );
}

/** A thumbnail link, the way [gallery] renders one */
function link( href, group, caption ) {
	const a = element('a');
	a.setAttribute( 'href', href );
	if( group !== null )
		a.setAttribute( 'data-lightbox', group );
	if( caption )
		a.setAttribute( 'data-caption', caption );
	const img = element('img');
	img.setAttribute( 'alt', 'alt of '+ href );
	a.appendChild( img );
	return a;
}


// --- the document ---------------------------------------------------------

const body = element('body');
const documentElement = element('html');

const LINKS = [
	link( '/images/gallery/a.jpg', 'trip', 'The first one' ),
	link( '/images/gallery/b.jpg', 'trip', '' ),
	link( '/images/gallery/c.jpg', 'trip', 'The third one' ),
	link( '/images/gallery/solo.jpg', '' ),
	// Not an image, and one that never opted in at all
	link( '/downloads/report.pdf', 'trip' ),
	link( '/images/gallery/plain.jpg', null ),
];
LINKS.forEach( function( a ) { body.appendChild( a ) } );

let raf = null;
const timers = [];

const dc = {
	body : body,
	documentElement : documentElement,
	activeElement : null,
	createElement : element,
	listeners : {},
	addEventListener : function( name, fn ) { ( dc.listeners[name] = dc.listeners[name] || [] ).push( fn ) },
	removeEventListener : function( name, fn ) { dc.listeners[name] = ( dc.listeners[name] || [] ).filter( function( f ) { return f !== fn } ) },
	querySelectorAll : function( selector ) { return findAll( body, function( n ) { return matches( n, selector ) } ) },
};

const preloaded = [];

const sandbox = {
	console : console,
	requestAnimationFrame : function( fn ) { raf = fn },
	setTimeout : function( fn ) { timers.push( fn ); return timers.length },
	Image : function() { const self = this; self.src = ''; Object.defineProperty( self, 'src', { set : function( value ) { preloaded.push( value ) }, get : function() { return '' } } ) },
};
sandbox.window = sandbox;
sandbox.document = dc;

vm.runInContext(
	fs.readFileSync( path.join( __dirname, '../assets/gallery.js' ), 'utf8' ),
	vm.createContext( sandbox ),
	{ filename : 'gallery.js' }
);

/** Click a link the way a browser reports it */
function click( target, modifiers ) {
	let prevented = false;
	const ev = Object.assign( {
		target : target,
		button : 0,
		defaultPrevented : false,
		preventDefault : function() { prevented = true },
		stopPropagation : function() {},
	}, modifiers || {} );
	fire( dc, 'click', ev );
	if( raf !== null ) { const fn = raf; raf = null; fn() }
	return prevented;
}

function overlay() {
	return body.children.filter( function( el ) { return String( el.className ).indexOf('gallery-view') === 0 } )[0] ?? null;
}

function stage() {
	return overlay() === null ? null : overlay().querySelector('.gallery-view-stage');
}

function shownSrc() {
	return overlay() === null ? '' : overlay().querySelector('.gallery-view-image').src;
}

function caption() {
	return overlay() === null ? '' : overlay().querySelector('.gallery-view-caption').textContent;
}

function counter() {
	return overlay() === null ? '' : overlay().querySelector('.gallery-view-count').textContent;
}

function key( name, shift ) {
	let prevented = false;
	( dc.listeners.keydown || [] ).slice().forEach( function( fn ) {
		fn( { key : name, shiftKey : shift === true, preventDefault : function() { prevented = true } } );
	} );
	return prevented;
}


function shut() {
	key('Escape');
	if( overlay() !== null )
		fire( overlay(), 'transitionend', { target : overlay() } );
	timers.splice( 0 ).forEach( function( fn ) { fn() } );
}

function labels() {
	return overlay().querySelectorAll('button').map( function( el ) { return el.getAttribute('aria-label') } );
}

console.log('Gallery overlay');

check( 'it binds one click listener on the document and nothing on any link', ( dc.listeners.click || [] ).length === 1
	&& LINKS.every( function( a ) { return ( a.listeners.click || [] ).length === 0 } ) );

// --- which links it takes
check( 'a link that never opted in, one that points at no image and a modified click are left to the browser', click( LINKS[5] ) === false && click( LINKS[4] ) === false
	&& click( LINKS[0], { metaKey : true } ) === false && click( LINKS[0], { button : 1 } ) === false && overlay() === null );
const script = link( 'javascript:alert(1)//.jpg', 'trip' );
const padded = link( ' \tjavascript:alert(1)//.png', 'trip' );
body.appendChild( script );
body.appendChild( padded );
const tabbed = link( 'java\tscript:alert(1)//x.jpg', 'trip' );
const broken = link( 'java\nscr\ript:alert(1)//x.png', 'trip' );
body.appendChild( tabbed );
body.appendChild( broken );
check( 'a tab or a line break inside the scheme is read as a browser reads it: java<tab>script: is not opened', click( tabbed ) === false && click( broken ) === false && overlay() === null );
check( 'a link whose address has a scheme other than http and https is not opened, though it ends like a picture, and is no part of a set', click( script ) === false && click( padded ) === false && click( link( 'data:image/svg+xml,<svg/>#.svg', 'trip' ) ) === false && overlay() === null );

// --- the overlay
check( 'a click on the image inside a link opens it: a modal dialog, the page behind it locked, the focus on the close button', click( LINKS[0].children[0] ) === true && overlay() !== null
	&& overlay().attributes.role === 'dialog' && overlay().attributes['aria-modal'] === 'true' && documentElement.classList.contains('gallery-view-lock') === true
	&& focused !== null && String( focused.className ).indexOf('gallery-view-close') !== -1 );
check( 'it shows the picture, its caption and the place in the set - the set is the group, the pdf and the ungrouped link are not in it', shownSrc() === '/images/gallery/a.jpg' && caption() === 'The first one' && counter() === '1 / 3'
	&& overlay().querySelector('.gallery-view-image').alt === 'alt of /images/gallery/a.jpg' && preloaded.length === 2 );

// --- moving through the set
check( 'the arrows move on and back and wrap; a picture without a caption of its own is named by its alt text', key('ArrowRight') === true && shownSrc() === '/images/gallery/b.jpg' && caption() === 'alt of /images/gallery/b.jpg'
	&& key('ArrowLeft') === true && key('ArrowLeft') === true && shownSrc() === '/images/gallery/c.jpg' && counter() === '3 / 3' );
fire( overlay().querySelector('.gallery-view-next'), 'click', { preventDefault : function() {}, stopPropagation : function() {} } );
check( 'the next button does what the arrow does', shownSrc() === '/images/gallery/a.jpg' );
fire( overlay(), 'touchstart', { changedTouches : [ { clientX : 300, clientY : 200 } ] } );
fire( overlay(), 'touchend', { changedTouches : [ { clientX : 120, clientY : 210 } ] } );
fire( overlay(), 'touchstart', { changedTouches : [ { clientX : 200, clientY : 400 } ] } );
fire( overlay(), 'touchend', { changedTouches : [ { clientX : 210, clientY : 120 } ] } );
check( 'a swipe left moves on, and scrolling a tall picture is no swipe', shownSrc() === '/images/gallery/b.jpg' );

// --- the focus stays inside, and goes back
const buttons = overlay().querySelectorAll('button');
dc.activeElement = buttons[ buttons.length - 1 ];
const wrapsForward = key('Tab') === true && focused === buttons[0];
dc.activeElement = buttons[0];
check( 'Tab off the last control comes back to the first, Shift+Tab off the first goes to the last, anywhere else Tab is the browser\'s', wrapsForward === true && key('Tab', true) === true && focused === buttons[ buttons.length - 1 ]
	&& ( dc.activeElement = buttons[1], key('Tab') === false ) );
check( 'Escape closes, unlocks the page and puts the focus back on the link that opened it, and the overlay leaves with its own fade only', key('Escape') === true
	&& documentElement.classList.contains('gallery-view-lock') === false && focused === LINKS[0]
	&& ( fire( overlay(), 'transitionend', { target : overlay().querySelector('.gallery-view-close') } ), overlay() !== null )
	&& ( fire( overlay(), 'transitionend', { target : overlay() } ), overlay() === null ) && key('ArrowRight') === false );
timers.splice( 0 ).forEach( function( fn ) { fn() } );

// --- a set of one, and the room around the picture
click( LINKS[3] );
check( 'a link with an empty group is a set of one: no arrows, no counter, and a click on the picture is no click on the room', overlay().querySelectorAll('button').length === 1 && overlay().querySelector('.gallery-view-count').hidden === true
	&& ( fire( overlay(), 'click', { target : overlay().querySelector('.gallery-view-figure') } ), overlay() !== null ) );
fire( overlay(), 'click', { target : stage() } );
timers.splice( 0 ).forEach( function( fn ) { fn() } );
check( 'a click on the room around it closes', overlay() === null );

// --- the words on the controls
click( LINKS[0].children[0] );
check( 'every control and the dialog are named; with no language on the page the words are English', labels().join(' | ') === 'Close | Previous image | Next image' && ( overlay().getAttribute('aria-label') || '' ) !== '' );
shut();
documentElement.setAttribute( 'lang', 'de-AT' );
LINKS[0].setAttribute( 'data-label-close', 'Zu' );
click( LINKS[0].children[0] );
check( 'a language of the page is read by its primary subtag, and a label the link carries wins over it', labels().join(' | ') === 'Zu | Vorheriges Bild | N\u00e4chstes Bild' );
shut();
documentElement.setAttribute( 'lang', '' );

// --- what a page says is text
const QUIET = link( '/images/gallery/quiet.jpg', 'quiet', '' );
QUIET.setAttribute( 'data-caption', '' );
const WILD = link( '/images/gallery/wild.jpg', 'wild', '<img src=x onerror=alert(1)> [csrf]' );
WILD.children[0].setAttribute( 'alt', '"><script>alert(1)</script>' );
body.appendChild( QUIET );
body.appendChild( WILD );
click( QUIET.children[0] );
const quiet = caption();
shut();
click( WILD.children[0] );
check( 'a caption that is present but empty means none, not the alt text', quiet === '' );
check( 'a caption and an alt text are written as text: the words arrive as they are, and no element is made of them', caption() === '<img src=x onerror=alert(1)> [csrf]' && overlay().querySelector('.gallery-view-caption').children.length === 0
	&& overlay().querySelector('.gallery-view-image').alt === '"><script>alert(1)</script>' && findAll( overlay(), function( node ) { return node.tagName === 'SCRIPT' || ( node.tagName === 'IMG' && node.className !== 'gallery-view-image' ) } ).length === 0 );
shut();

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exitCode = failures === 0 ? 0 : 1;
