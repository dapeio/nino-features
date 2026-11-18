/**
 *	Nino
 *	progress-js-smoke.js	What progress.js does over a dom stand-in: how far
 *												through it thinks the reader is over the whole page
 *												and over one named element, what it does with an
 *												element shorter than the screen, that a bar with a
 *												name becomes a named progressbar and one without is
 *												hidden from a screen reader, that a bar written into
 *												a section with a transform is moved to the body before
 *												it is measured and one that stands there already stays,
 *												and that a page with no bar on it costs nothing.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node - and the kernel's own Nino.js,
 *												which the script is written against and which runs
 *												here as it is on disk: from the checkout three
 *												levels up, or the one NINO_ROOT names. The ready
 *												callback and the throttle of the scroll and the
 *												resize are its.
 *
 *	Usage: node features/Progress/tests/progress-js-smoke.js
 *	       NINO_ROOT=../nino node features/Progress/tests/progress-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/progress.js' ), 'utf8' );

// The script of the kernel that progress.js is written against, read from the
// checkout this runs against: three levels up when the feature sits in a
// project's features/, else the one NINO_ROOT names
const kernel = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const nino = new vm.Script( fs.readFileSync( path.join( kernel, '_nino/Nino.js' ), 'utf8' ), { filename : 'Nino.js' } );

function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		properties	: {},
		box					: { top : 0, height : 0 },
		parentNode	: null,
		children	: [],
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		getBoundingClientRect : function() { return this.box },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		// As the DOM does it: a node that stands somewhere leaves that place first
		appendChild			: function( child ) {
			if( child.parentNode !== null )
				child.parentNode.removeChild( child );
			child.parentNode = this;
			this.children.push( child );
			return child;
		},
		removeChild			: function( child ) {
			this.children = this.children.filter( function( node ) { return node !== child } );
			child.parentNode = null;
			return child;
		},
	};

	el.classList = { add : function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() } };
	el.style = { setProperty : function( name, value ) { el.properties[name] = value } };

	return el;
}

/**
 *	A page with a bar on it, the kernel's Nino.js and then the script loaded over
 *	it. A document that is there already is ready when this returns; one that is
 *	not is ready when its DOMContentLoaded listeners are called
 *
 *	@param		{Object}	options		{ of, label, scrollHeight, offset, subject, withBar, readyState, inSection }
 */
function page( options ) {

	options = options || {};

	const attributes = { 'class' : 'progress' };
	if( options.of !== undefined ) attributes['data-progress-of'] = options.of;
	if( options.label !== undefined ) attributes['data-progress-label'] = options.label;

	const bar = element( attributes );
	const subject = element( { id : 'article' } );

	// Where the bar stood when it was measured first: the move has to come before
	// that, not after. And how often it was painted
	let paintedUnder;
	let paints = 0;
	const setProperty = bar.style.setProperty;
	bar.style.setProperty = function( name, value ) {
		if( paintedUnder === undefined ) paintedUnder = bar.parentNode;
		paints++;
		setProperty( name, value );
	};

	// The bar is written first thing in the body or - the way the Builder places
	// it - into a section, which carries a transform once it has been animated
	// into view
	const body = element( {} );
	const footer = element( { 'class' : 'footer' } );
	let section = null;

	if( options.inSection === true ) {
		section = element( { 'class' : 'section-wrap' } );
		section.style.transform = 'translateY(0px)';
		body.appendChild( section );
		section.appendChild( bar );
	}
	else
		body.appendChild( bar );

	body.appendChild( footer );

	if( options.subject !== undefined )
		subject.box = options.subject;

	const root = element( {} );
	root.scrollHeight = options.scrollHeight !== undefined ? options.scrollHeight : 3000;
	root.scrollTop = options.offset !== undefined ? options.offset : 0;

	const listeners = {};
	const frames = [];
	const timers = [];

	const dc = {
		readyState			: options.readyState || 'complete',
		documentElement	: root,
		body				: body,
		querySelector		: function( selector ) { return selector === '#article' ? subject : null },
		querySelectorAll	: function( selector ) {
			return ( selector === '.progress' && options.withBar !== false ) ? [ bar ] : [];
		},
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	// The window is the global of the page, as it is in a browser: Nino.js puts Nino on it
	const wn = {
		console : console,
		document : dc,
		navigator : { userAgent : 'node' },
		innerHeight : options.seen !== undefined ? options.seen : 1000,
		pageYOffset : options.offset !== undefined ? options.offset : 0,
		Event : function( type ) { this.type = type },
		dispatchEvent : function( event ) { ( listeners[event.type] || [] ).forEach( function( fn ) { fn( event ) } ) },
		addEventListener : function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		requestAnimationFrame : function( fn ) { frames.push( fn ); return frames.length },
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
	};
	wn.window = wn;

	const context = vm.createContext( wn );

	nino.runInContext( context );

	// What the script binds to the kernel's events: Nino.events.bindCallback() as
	// the kernel has it, noting its first argument and handing the call on
	const bound = [];
	const bindCallback = wn.Nino.events.bindCallback;
	wn.Nino.events.bindCallback = function( evt, cb ) {
		bound.push( evt );
		return bindCallback.call( this, evt, cb );
	};

	vm.runInContext( source, context, { filename : 'progress.js' } );

	// Run what was asked of requestAnimationFrame() and of setTimeout(), and what that
	// asked for in turn. A callback of Nino.js that throws is thrown again from a timer,
	// which this runs too - so the error ends the run instead of passing unseen
	const runFrames = function() {
		while( frames.length > 0 )
			frames.splice( 0 ).forEach( function( fn ) { fn() } );
	};
	const settle = function() {
		while( timers.length > 0 )
			timers.splice( 0 ).forEach( function( fn ) { fn() } );
	};
	const fire = function( type ) {
		( listeners[type] || [] ).forEach( function( fn ) { fn( { type : type } ) } );
	};

	// A document that is there already: Nino.js calls its ready callbacks back a
	// millisecond after it was loaded, and the resize and the scroll it dispatches
	// first take their frame
	if( dc.readyState !== 'loading' ) {
		settle();
		runFrames();
	}

	return {
		bar : bar, subject : subject, window : wn, listeners : listeners,
		body : body, section : section, footer : footer, bound : bound,
		paintedUnder : function() { return paintedUnder },
		paints : function() { return paints },
		frames : function() { return frames.length },
		fire : fire,
		runFrames : runFrames,
		at : function() { return bar.properties['--progress-value'] },
		scrollTo : function( offset, top ) {
			wn.pageYOffset = offset;
			root.scrollTop = offset;
			if( top !== undefined ) subject.box = { top : top, height : subject.box.height };
			fire( 'scroll' );
			runFrames();
		},
		resizeTo : function( seen ) {
			wn.innerHeight = seen;
			fire( 'resize' );
			runFrames();
		},
	};
}


// --- Over the whole page and over one element ----------------------------------

const whole = page( { scrollHeight : 3000, seen : 1000 } );
check( 'a bar comes to life once there is something to measure, and at the top of the page nothing has been read', whole.bar.matchesClass( 'progress-is-ready' ) === true && whole.at() === '0%' );

whole.scrollTo( 1000 );
const half = whole.at();
whole.scrollTo( 9999 );
check( 'halfway down the page is half read, and a page scrolled past its own end is just finished', half === '50%' && whole.at() === '100%' );
check( 'a page shorter than the window has nothing to be through', page( { scrollHeight : 500, seen : 1000 } ).at() === '0%' );

const article = page( { of : '#article', seen : 1000, subject : { top : 0, height : 3000 } } );
article.scrollTo( 1000, -1000 );
const articleHalf = article.at();
article.scrollTo( 2000, -2000 );
const articleDone = article.at();
article.scrollTo( 3000, -3000 );
check( 'an article is half read when half of it has gone past, finished when its last line has been, and the footer under it does not go on filling the bar',
	articleHalf === '50%' && articleDone === '100%' && article.at() === '100%' );
check( 'an article shorter than the screen is finished the moment it is on the screen, and a selector that finds nothing measures the page',
	page( { of : '#article', seen : 1000, subject : { top : -10, height : 400 } } ).at() === '100%' && page( { of : '#kein-artikel', seen : 1000, scrollHeight : 3000, offset : 1000 } ).at() === '50%' );


// --- Following the scroll and the resize ------------------------------------------

const burst = page( { scrollHeight : 3000, seen : 1000, offset : 1000 } );
const painted = burst.paints();
burst.fire( 'scroll' );
burst.fire( 'scroll' );
const oneFrame = burst.frames() === 1;
burst.runFrames();
check( 'a bar follows the scroll and the resize through the kernel\'s own events, a burst is one frame and one paint', burst.bound.join() === 'ready,scroll,resize' && oneFrame === true && burst.paints() === painted + 1 );
burst.resizeTo( 500 );
check( 'a resize paints again: the same place over a shorter window is a different share', burst.at() === '40%' );


// --- What a screen reader gets ----------------------------------------------------

const named = page( { label : 'Lesefortschritt', scrollHeight : 3000, seen : 1000, offset : 1000 } );
const value = named.bar.getAttribute('aria-valuenow');
named.scrollTo( 2000 );
check( 'a bar that was given a name is a named progressbar with a value that follows the reading',
	named.bar.getAttribute('role') === 'progressbar' && named.bar.getAttribute('aria-label') === 'Lesefortschritt' && named.bar.getAttribute('aria-valuemin') === '0'
	&& named.bar.getAttribute('aria-valuemax') === '100' && value === '50' && named.bar.getAttribute('aria-valuenow') === '100' );
check( 'a bar with no name is hidden from a screen reader rather than announced as a number nobody asked for',
	whole.bar.getAttribute('aria-hidden') === 'true' && whole.bar.getAttribute('role') === null && whole.bar.getAttribute('aria-valuenow') === null );


// --- Where the bar stands ---------------------------------------------------------

const nested = page( { inSection : true } );
check( 'a bar written into a section that has a transform is a child of the body before it is measured, and the section does not hold it any more',
	nested.bar.parentNode === nested.body && nested.paintedUnder() === nested.body && nested.section.children.indexOf( nested.bar ) === -1 );
const placed = page( {} );
check( 'a bar that is a child of the body already is left where it is', placed.body.children[0] === placed.bar && placed.body.children[1] === placed.footer );


// --- A page with no bar, and waiting for the dom ----------------------------------

check( 'a page with no bar on it binds nothing but its wait for the page', page( { withBar : false } ).bound.join() === 'ready' );

const late = page( { readyState : 'loading' } );
const waited = late.bar.matchesClass( 'progress-is-ready' ) === false && ( late.listeners['DOMContentLoaded'] || [] ).length === 1;
( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( 'a script that ran before the dom was there waits for it, and takes the bars over when it arrives', waited === true && late.bar.matchesClass( 'progress-is-ready' ) === true );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
