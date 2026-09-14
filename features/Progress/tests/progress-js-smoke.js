/**
 *	Nino
 *	progress-js-smoke.js	What progress.js does over a dom stand-in: how far
 *												through it thinks the reader is over the whole page
 *												and over one named element, what it does with an
 *												element shorter than the screen, that a bar with a
 *												name becomes a named progressbar and one without is
 *												hidden from a screen reader, and that a page with no
 *												bar on it costs nothing.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node.
 *
 *	Usage: node features/Progress/tests/progress-js-smoke.js
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

function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		properties	: {},
		box					: { top : 0, height : 0 },
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		getBoundingClientRect : function() { return this.box },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
	};

	el.classList = { add : function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() } };
	el.style = { setProperty : function( name, value ) { el.properties[name] = value } };

	return el;
}

/**
 *	A page with a bar on it, the script loaded over it
 *
 *	@param		{Object}	options		{ of, label, scrollHeight, offset, subject, withBar, readyState }
 */
function page( options ) {

	options = options || {};

	const attributes = { 'class' : 'nino-progress' };
	if( options.of !== undefined ) attributes['data-progress-of'] = options.of;
	if( options.label !== undefined ) attributes['data-progress-label'] = options.label;

	const bar = element( attributes );
	const subject = element( { id : 'article' } );

	if( options.subject !== undefined )
		subject.box = options.subject;

	const root = element( {} );
	root.scrollHeight = options.scrollHeight !== undefined ? options.scrollHeight : 3000;
	root.scrollTop = options.offset !== undefined ? options.offset : 0;

	const listeners = {};
	let frame = null;

	const dc = {
		readyState			: options.readyState || 'complete',
		documentElement	: root,
		querySelector		: function( selector ) { return selector === '#article' ? subject : null },
		querySelectorAll	: function( selector ) {
			return ( selector === '.nino-progress' && options.withBar !== false ) ? [ bar ] : [];
		},
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const wn = {
		innerHeight : options.seen !== undefined ? options.seen : 1000,
		pageYOffset : options.offset !== undefined ? options.offset : 0,
		addEventListener : function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		requestAnimationFrame : function( fn ) { frame = fn; return 1 },
	};

	const sandbox = { console : console, document : dc, window : wn };
	sandbox.window.window = wn;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'progress.js' } );

	return {
		bar : bar, subject : subject, window : wn, listeners : listeners,
		at : function() { return bar.properties['--nino-progress'] },
		scrollTo : function( offset, top ) {
			wn.pageYOffset = offset;
			root.scrollTop = offset;
			if( top !== undefined ) subject.box = { top : top, height : subject.box.height };
			( listeners['scroll'] || [] ).forEach( function( fn ) { fn() } );
			if( frame !== null ) { const fn = frame; frame = null; fn() }
		},
	};
}


// --- Over the whole page -------------------------------------------------------

const whole = page( { scrollHeight : 3000, seen : 1000 } );

check( 'a bar comes to life once there is something to measure', whole.bar.matchesClass( 'nino-is-ready' ) === true );
check( 'at the top of the page nothing has been read yet', whole.at() === '0%' );

whole.scrollTo( 1000 );
check( 'halfway down the page is half read', whole.at() === '50%' );

whole.scrollTo( 2000 );
check( '...and at the bottom it is finished', whole.at() === '100%' );

whole.scrollTo( 9999 );
check( 'and a page scrolled past its own end is still just finished', whole.at() === '100%' );

const short = page( { scrollHeight : 500, seen : 1000 } );
check( 'a page shorter than the window has nothing to be through', short.at() === '0%' );


// --- Over one element ----------------------------------------------------------

/*	The whole page counts the footer as part of the article, so "finished"
	arrives after the last paragraph rather than at it	*/
const article = page( { of : '#article', seen : 1000, subject : { top : 0, height : 3000 } } );

check( 'an article at the top of the window has not been read yet', article.at() === '0%' );

article.scrollTo( 1000, -1000 );
check( 'it is half read when half of it has gone past', article.at() === '50%' );

article.scrollTo( 2000, -2000 );
check( '...and finished when its last line has been, not when its bottom edge has', article.at() === '100%' );

article.scrollTo( 3000, -3000 );
check( 'and the footer under it does not go on filling the bar', article.at() === '100%' );

const stubby = page( { of : '#article', seen : 1000, subject : { top : 200, height : 400 } } );
check( 'an article shorter than the screen is not yet read while it is still below the top', stubby.at() === '0%' );

const stubbySeen = page( { of : '#article', seen : 1000, subject : { top : -10, height : 400 } } );
check( '...and finished the moment it is on the screen, rather than never', stubbySeen.at() === '100%' );

const nowhere = page( { of : '#kein-artikel', seen : 1000, scrollHeight : 3000, offset : 1000 } );
check( 'a bar pointed at an element that is not there falls back to the whole page', nowhere.at() === '50%' );


// --- What a screen reader gets -------------------------------------------------

const named = page( { label : 'Lesefortschritt', scrollHeight : 3000, seen : 1000, offset : 1000 } );

check( 'a bar that was given a name is a named progressbar with a value',
	named.bar.getAttribute('role') === 'progressbar'
	&& named.bar.getAttribute('aria-label') === 'Lesefortschritt'
	&& named.bar.getAttribute('aria-valuemin') === '0' && named.bar.getAttribute('aria-valuemax') === '100'
	&& named.bar.getAttribute('aria-valuenow') === '50' );

named.scrollTo( 2000 );
check( '...and the value follows the reading', named.bar.getAttribute('aria-valuenow') === '100' );

check( 'a bar with no name is hidden from a screen reader rather than announced as a number nobody asked for',
	whole.bar.getAttribute('aria-hidden') === 'true' && whole.bar.getAttribute('role') === null
	&& whole.bar.getAttribute('aria-valuenow') === null );


// --- A page with no bar --------------------------------------------------------

const none = page( { withBar : false } );
check( 'a page with no bar on it is not listened to at all', ( none.listeners['scroll'] || [] ).length === 0 );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.bar.matchesClass( 'nino-is-ready' ) === false && ( late.listeners['DOMContentLoaded'] || [] ).length === 1 );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and takes the bars over when it arrives', late.bar.matchesClass( 'nino-is-ready' ) === true );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
