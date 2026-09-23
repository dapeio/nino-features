/**
 *	Nino
 *	stats-js-smoke.js		What the Stats panel's script draws for one month: the
 *											bar row, over a dom stand-in. The store holds a day once
 *											it has a view, so what the panel is handed is the days
 *											with data - and the row has to be the month anyway, one
 *											column per day, the empty ones as a baseline mark.
 *
 *											No jsdom, no dependency: the same element stand-in the
 *											other feature tests build, so this runs with nothing but
 *											node. stats-smoke.php runs it through node when node is
 *											on the path, so bin/check.sh and CI cover it; it also
 *											runs on its own.
 *
 *	Usage: node features/Stats/tests/stats-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/admin.js' ), 'utf8' );

/**
 *	One element, with just enough of the interface admin.js reaches for
 */
function element( tag ) {
	return {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		className		: '',
		textContent	: '',
		title				: '',
		style				: {},
		appendChild	: function( child ) { this.children.push( child ); return child },
	};
}

/*	The namespace the panel script extends - a local stand-in, not the
	global, so the file lints like a browser script with the rest of them	*/
const nino = {
	admin		: {},
	adminUi	: { emptyState : function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p } },
	content	: { getText : function( key ) { return key } },
	events	: { bindCallback : function() {} },
	http		: { sendRequest : function() { throw new Error( 'the bar row asks the server for nothing' ) } },
};
const sandbox = { console : console, document : { createElement : function( tag ) { return element( tag ) }, getElementById : function() { return null } }, Nino : nino };
sandbox.window = sandbox;
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const stats = nino.admin.stats;
check( 'the panel script boots and draws the bar row on its own', typeof stats._renderBars === 'function' );

/** Every column of a drawn row: its day number, its bar's height and whether it is marked empty */
function columns( row ) {
	return row.children.filter( function( col ) { return col.className.indexOf('stats-bar-col') === 0 } ).map( function( col ) {
		const bar = col.children[0], label = col.children[1];
		return { day : label.textContent, height : bar.style.height, empty : col.className.indexOf('is-empty') !== -1, value : bar.children[0].textContent };
	} );
}

// --- One visit in a month of thirty days --------------------------------------

const september = columns( stats._renderBars( [ { day : '2026-09-22', total : 1 } ], '2026-09' ) );
check( 'a month is drawn as every one of its days, not as the days that counted something', september.length === 30 );
check( '...numbered in order from the first to the last', september.map( function( c ) { return c.day } ).join(' ') === Array.from( { length : 30 }, function( _, i ) { return String( i + 1 ).padStart( 2, '0' ) } ).join(' ') );
check( 'the one day with a view is the full bar', september[21].height === '100%' && september[21].empty === false && september[21].value === '1' );
check( '...and every other day is a baseline mark that says the day was there', september.filter( function( c ) { return c.empty === true && c.height === '2px' && c.value === '0' } ).length === 29 );

// --- The calendar is the month's own -----------------------------------------------

check( 'a month of thirty-one days draws thirty-one columns', columns( stats._renderBars( [ { day : '2026-10-03', total : 4 } ], '2026-10' ) ).length === 31 );
check( 'February follows the leap year', columns( stats._renderBars( [ { day : '2028-02-01', total : 4 } ], '2028-02' ) ).length === 29
	&& columns( stats._renderBars( [ { day : '2027-02-01', total : 4 } ], '2027-02' ) ).length === 28 );

// --- Heights against the busiest day ---------------------------------------------

const busy = columns( stats._renderBars( [ { day : '2026-09-01', total : 8 }, { day : '2026-09-02', total : 2 }, { day : '2026-09-03', total : 0 } ], '2026-09' ) );
check( 'every bar is measured against the busiest day of the month', busy[0].height === '100%' && busy[1].height === '25%' );
check( '...and a day handed with zero views is empty like a day not handed at all', busy[2].empty === true && busy[2].height === '2px' );

// --- The edges --------------------------------------------------------------------------

check( 'a row handed no month name draws the days it was given', columns( stats._renderBars( [ { day : '2026-09-22', total : 1 }, { day : '2026-09-25', total : 3 } ], '' ) ).map( function( c ) { return c.day } ).join(' ') === '22 25' );
const empty = stats._renderBars( [], '2026-09' );
check( 'a month with no data at all says so instead of drawing thirty empty columns', empty.children.length === 1 && empty.children[0].className === 'nino-admin-empty' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
