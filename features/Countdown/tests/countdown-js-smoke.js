/**
 *	Nino
 *	countdown-js-smoke.js		What countdown.js does over a dom stand-in: that the
 *													date steps aside and the counter comes out of
 *													hiding, how a remainder is split across the parts
 *													one countdown was written with, that a unit is
 *													named in the right one of its two forms, what
 *													happens at the moment itself, and that a countdown
 *													with a moment nobody can read is left alone.
 *
 *													No jsdom, no dependency: the same element stand-in
 *													the other feature tests build, so this runs with
 *													nothing but node.
 *
 *	Usage: node features/Countdown/tests/countdown-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/countdown.js' ), 'utf8' );

/**
 *	One element, with just enough of the interface countdown.js reaches for
 */
function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		classes			: {},
		hidden			: false,
		children		: [],
		textContent	: '',
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) { return find( this, selector.replace( /^\./, '' ) )[0] || null },
		querySelectorAll	: function( selector ) { return find( this, selector.replace( /^\./, '' ) ) },
	};

	el.classList = {
		add				: function( name ) { el.classes[name] = true },
		contains	: function( name ) { return el.classes[name] === true },
	};

	return el;
}

/** Every descendant of $root carrying $name */
function find( root, name ) {
	let found = [];
	for( const child of root.children ) {
		if( child.matchesClass( name ) ) found.push( child );
		found = found.concat( find( child, name ) );
	}
	return found;
}

/**
 *	One countdown as the shortcode writes it, the script loaded over it at a
 *	clock the test sets, and the hooks a test needs back
 *
 *	@param		{Object}	options		{ to, now, units, done, readyState }
 */
function page( options ) {

	options = options || {};

	const units = options.units || [ 'days', 'hours', 'minutes', 'seconds' ];
	const one = { days : 'day', hours : 'hour', minutes : 'minute', seconds : 'second' };

	const countdown = element( {
		'class'								: 'nino-countdown',
		'data-countdown-to'		: options.to !== undefined ? options.to : '2026-12-24T18:00:00+01:00',
		'data-countdown-done'	: options.done !== undefined ? options.done : 'Es ist so weit',
	} );

	const date = element( { 'class' : 'nino-countdown-date' } );
	date.textContent = '2026-12-24 18:00';

	const parts = element( { 'class' : 'nino-countdown-parts' } );
	parts.hidden = true;

	for( const unit of units ) {
		const part = element( {
			'class'									: 'nino-countdown-part',
			'data-countdown-unit'		: unit,
			'data-countdown-one'		: one[unit],
			'data-countdown-many'		: unit,
		} );
		part.children.push( element( { 'class' : 'nino-countdown-value' } ) );
		part.children.push( element( { 'class' : 'nino-countdown-name' } ) );
		parts.children.push( part );
	}

	countdown.children.push( date, parts );

	const listeners = {};
	let ticker = null;

	const dc = {
		readyState			: options.readyState || 'complete',
		querySelectorAll	: function( selector ) {
			return selector === '.nino-countdown[data-countdown-to]'
				? ( countdown.getAttribute('data-countdown-to') !== null ? [ countdown ] : [] )
				: [];
		},
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const sandbox = {
		console : console,
		document : dc,
		Date : Object.assign( function(){}, { now : function() { return options.now || Date.parse('2026-12-24T18:00:00+01:00') - 1000 }, parse : Date.parse } ),
		setInterval : function( fn ) { ticker = fn; return 1 },
		clearInterval : function() { ticker = null },
	};
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'countdown.js' } );

	return {
		countdown : countdown, date : date, parts : parts, listeners : listeners,
		ticking : function() { return ticker !== null },
		tick : function() { if( ticker !== null ) ticker() },
		values : function() {
			return find( parts, 'nino-countdown-value' ).map( function( v ) { return v.textContent } );
		},
		names : function() {
			return find( parts, 'nino-countdown-name' ).map( function( n ) { return n.textContent } );
		},
	};
}

const AT = Date.parse('2026-12-24T18:00:00+01:00');


// --- Taking a countdown over ---------------------------------------------------

const running = page( { now : AT - ( 2 * 86400 + 3 * 3600 + 4 * 60 + 5 ) * 1000 } );

check( 'the counter comes out of hiding', running.parts.hidden === false );
check( '...and the date steps aside, having been what stood there without a script', running.date.hidden === true );
check( 'the remainder is split largest part first', running.values().join(':') === '2:3:4:5' );
check( '...and a clock is running for it', running.ticking() === true );


// --- One of the two forms of a name --------------------------------------------

const singular = page( { now : AT - ( 86400 + 3600 + 60 + 1 ) * 1000 } );

check( 'a part standing at one is named in the singular', singular.names().join(':') === 'day:hour:minute:second' );

const plural = page( { now : AT - ( 2 * 86400 + 2 * 3600 + 2 * 60 + 2 ) * 1000 } );
check( '...and one standing at anything else in the plural', plural.names().join(':') === 'days:hours:minutes:seconds' );

const zero = page( { now : AT - ( 2 * 60 + 30 ) * 1000 } );
check( 'a part with nothing left in it still says so rather than disappearing', zero.values().join(':') === '0:0:2:30' );


// --- Fewer parts than four -----------------------------------------------------

const coarse = page( { units : [ 'days', 'hours' ], now : AT - ( 4 * 86400 + 12 * 3600 + 59 * 60 ) * 1000 } );

check( 'a countdown written in two parts carries its whole remainder in them', coarse.values().join(':') === '4:12' );

const daysOnly = page( { units : [ 'days' ], now : AT - ( 4 * 86400 + 23 * 3600 ) * 1000 } );
check( '...and one written in days alone says four for four and a half, not five', daysOnly.values().join(':') === '4' );


// --- The moment itself ---------------------------------------------------------

const ending = page( { now : AT - 1000 } );

check( 'a second before, it is still counting', ending.parts.hidden === false && ending.countdown.classes['nino-is-done'] === undefined );

const over = page( { now : AT } );

check( 'at the moment itself the counter goes', over.parts.hidden === true );
check( '...and what the shortcode was given for afterwards stands in its place', over.date.textContent === 'Es ist so weit' && over.date.hidden === false );
check( '...the countdown says it is done', over.countdown.classes['nino-is-done'] === true );
check( '...and the clock is stopped, because there is nothing left to count', over.ticking() === false );

const silent = page( { now : AT + 5000, done : '' } );
check( 'a countdown given no sentence for afterwards puts its date back - what has run out is still a date',
	silent.date.textContent === '2026-12-24 18:00' && silent.date.hidden === false );


// --- A moment that is not one --------------------------------------------------

const broken = page( { to : 'irgendwann' } );

check( 'a countdown whose moment cannot be read is left exactly as it was',
	broken.parts.hidden === true && broken.date.hidden === false && broken.ticking() === false );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading', now : AT - 60000 } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.parts.hidden === true && ( late.listeners['DOMContentLoaded'] || [] ).length === 1 );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and picks the countdowns up when it arrives', late.parts.hidden === false && late.values().join(':') === '0:0:1:0' );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
