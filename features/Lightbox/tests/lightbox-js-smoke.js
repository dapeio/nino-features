/**
 *	Nino
 *	lightbox-js-smoke.js	What lightbox.js does over a dom stand-in: which
 *												links it takes and which it leaves alone, the set a
 *												link belongs to, what the overlay carries, moving
 *												through a set with the arrows and with a swipe,
 *												the focus going in and coming back, and the page
 *												behind it not scrolling while one is open.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the workbench's own panel tests build, so this runs
 *												with nothing but node.
 *
 *	Usage: node features/Lightbox/tests/lightbox-js-smoke.js
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
	fs.readFileSync( path.join( __dirname, '../assets/lightbox.js' ), 'utf8' ),
	vm.createContext( sandbox ),
	{ filename : 'lightbox.js' }
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
	return body.children.filter( function( el ) { return String( el.className ).indexOf('nino-lightbox') === 0 } )[0] ?? null;
}

function stage() {
	return overlay() === null ? null : overlay().querySelector('.nino-lightbox-stage');
}

function shownSrc() {
	return overlay() === null ? '' : overlay().querySelector('.nino-lightbox-image').src;
}

function caption() {
	return overlay() === null ? '' : overlay().querySelector('.nino-lightbox-caption').textContent;
}

function counter() {
	return overlay() === null ? '' : overlay().querySelector('.nino-lightbox-count').textContent;
}

function key( name, shift ) {
	let prevented = false;
	( dc.listeners.keydown || [] ).slice().forEach( function( fn ) {
		fn( { key : name, shiftKey : shift === true, preventDefault : function() { prevented = true } } );
	} );
	return prevented;
}

console.log('Lightbox');

check( 'it binds one click listener on the document and nothing on any link', ( dc.listeners.click || [] ).length === 1
	&& LINKS.every( function( a ) { return ( a.listeners.click || [] ).length === 0 } ) );


// --- which links it takes -------------------------------------------------

check( 'a link that never opted in is left to the browser', click( LINKS[5] ) === false && overlay() === null );
check( 'so is one that opted in but does not point at an image - the overlay would show a broken picture with no way back', click( LINKS[4] ) === false && overlay() === null );
check( 'a modified click is left alone too: that is the visitor asking for a tab of their own',
	click( LINKS[0], { metaKey : true } ) === false && click( LINKS[0], { ctrlKey : true } ) === false
	&& click( LINKS[0], { shiftKey : true } ) === false && click( LINKS[0], { button : 1 } ) === false && overlay() === null );

// The click almost always lands on the <img>, not the <a>
check( 'a click on the image inside the link opens it', click( LINKS[0].children[0] ) === true && overlay() !== null );


// --- what is on screen ----------------------------------------------------

check( 'the overlay is a modal dialog, and the page behind it stops scrolling', overlay().attributes.role === 'dialog'
	&& overlay().attributes['aria-modal'] === 'true' && documentElement.classList.contains('nino-lightbox-lock') === true );
check( 'it shows the image the link pointed at', shownSrc() === '/images/gallery/a.jpg' );
check( 'the caption is the one the page wrote', caption() === 'The first one' );
check( 'the set is the group, and the other two of it are in it - the pdf and the ungrouped link are not', counter() === '1 / 3' );
check( 'the focus went to the close button', focused !== null && String( focused.className ).indexOf('nino-lightbox-close') !== -1 );
check( 'both neighbours are preloaded, and only they', preloaded.length === 2
	&& preloaded.indexOf('/images/gallery/b.jpg') !== -1 && preloaded.indexOf('/images/gallery/c.jpg') !== -1 );


// --- moving through the set -----------------------------------------------

check( 'the right arrow moves on, and takes the caption and the counter with it', key('ArrowRight') === true
	&& shownSrc() === '/images/gallery/b.jpg' && counter() === '2 / 3' );
check( 'a picture the page gave no caption falls back to the alt rather than to a filename', caption() === 'alt of /images/gallery/b.jpg' );
check( 'the left arrow moves back', key('ArrowLeft') === true && shownSrc() === '/images/gallery/a.jpg' );
check( 'and it wraps rather than stopping', key('ArrowLeft') === true && shownSrc() === '/images/gallery/c.jpg' && counter() === '3 / 3' );

const next = overlay().querySelector('.nino-lightbox-next');
fire( next, 'click', { preventDefault : function() {}, stopPropagation : function() {} } );
check( 'the next button does what the arrow does', shownSrc() === '/images/gallery/a.jpg' );

// A swipe: horizontal, and far enough that a scroll would not have done it
fire( overlay(), 'touchstart', { changedTouches : [ { clientX : 300, clientY : 200 } ] } );
fire( overlay(), 'touchend', { changedTouches : [ { clientX : 120, clientY : 210 } ] } );
check( 'a swipe left moves on', shownSrc() === '/images/gallery/b.jpg' );

fire( overlay(), 'touchstart', { changedTouches : [ { clientX : 120, clientY : 200 } ] } );
fire( overlay(), 'touchend', { changedTouches : [ { clientX : 300, clientY : 210 } ] } );
check( 'a swipe right moves back', shownSrc() === '/images/gallery/a.jpg' );

fire( overlay(), 'touchstart', { changedTouches : [ { clientX : 200, clientY : 400 } ] } );
fire( overlay(), 'touchend', { changedTouches : [ { clientX : 210, clientY : 120 } ] } );
check( 'scrolling a tall picture is not a swipe', shownSrc() === '/images/gallery/a.jpg' );


// --- the focus stays inside -----------------------------------------------

const buttons = overlay().querySelectorAll('button');
dc.activeElement = buttons[ buttons.length - 1 ];
check( 'Tab off the last control comes back to the first', key('Tab') === true && focused === buttons[0] );
dc.activeElement = buttons[0];
check( 'and Shift+Tab off the first goes to the last', key('Tab', true) === true && focused === buttons[ buttons.length - 1 ] );
dc.activeElement = buttons[1];
check( 'Tab anywhere else is the browser\'s', key('Tab') === false );


// --- closing --------------------------------------------------------------

check( 'Escape closes, unlocks the page and puts the focus back on the link that opened it', key('Escape') === true
	&& documentElement.classList.contains('nino-lightbox-lock') === false && focused === LINKS[0] );

// The overlay leaves after its fade; the timer is the fallback for a browser
// that skipped the transition
timers.splice( 0 ).forEach( function( fn ) { fn() } );
check( '...and the overlay is out of the document afterwards', overlay() === null );

check( 'a key press after closing does nothing at all', key('ArrowRight') === false && overlay() === null );


// --- a set of one ---------------------------------------------------------

const preloadedBefore = preloaded.length;
click( LINKS[3] );
check( 'a link with an empty group is its own set: no arrows, no counter', overlay() !== null
	&& overlay().querySelectorAll('button').length === 1 && overlay().querySelector('.nino-lightbox-count').hidden === true );
check( 'nothing is preloaded for a set of one - there is no neighbour to be ready for', preloaded.length === preloadedBefore );

// The backdrop closes, the picture does not
fire( overlay(), 'click', { target : overlay().querySelector('.nino-lightbox-figure') } );
check( 'a click on the picture is somebody looking, not somebody leaving', overlay() !== null );
fire( overlay(), 'click', { target : stage() } );
timers.splice( 0 ).forEach( function( fn ) { fn() } );
check( 'a click on the room around it closes', overlay() === null );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exitCode = failures === 0 ? 0 : 1;
