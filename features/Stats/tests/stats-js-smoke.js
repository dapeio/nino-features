/**
 *	Nino
 *	stats-js-smoke.js		What the Stats panel's script draws for one month: the
 *											bar row, over a dom stand-in. The store holds a day once
 *											it has a view, so what the panel is handed is the days
 *											with data - and the row has to be the month anyway, one
 *											column per day, the empty ones as a baseline mark. The
 *											row's axis label, the summary line in the singular where
 *											it is one, and the tables: the pages with a title
 *											column and a path column, and the two empty texts.
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
const tableCalls = [];
const body = element('div');

const nino = {
	admin		: {},
	adminUi	: {
		emptyState : function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
		// What the shared table component was handed, call by call
		table : function( options ) { tableCalls.push( options ) },
	},
	// The key itself, except where a placeholder has to be filled
	content	: { getText : function( key ) { return key === '/_admin/stats/label/max' ? 'Peak: %d' : key } },
	events	: { bindCallback : function() {} },
	http		: { sendRequest : function() { throw new Error( 'the bar row asks the server for nothing' ) } },
};
const sandbox = { console : console, document : { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return id === 'stats-body' ? body : null } }, Nino : nino };
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

const empty = stats._renderBars( [], '2026-09' );
check( 'a month with no data at all says so instead of drawing thirty empty columns', empty.children.length === 1 && empty.children[0].className === 'nino-admin-empty' );

// --- The axis ------------------------------------------------------------------------

const axisOf = function( row ) { return row.children.filter( function( child ) { return child.className === 'stats-axis' } ) };
const withAxis = stats._renderBars( [ { day : '2026-09-01', total : 8 }, { day : '2026-09-02', total : 2 } ], '2026-09' );
check( 'the busiest day\'s count is written at the top of the row', axisOf( withAxis ).length === 1 && axisOf( withAxis )[0].textContent === 'Peak: 8' );
check( '...and the bars are still a share of it - thirty columns, 100% and 25%', columns( withAxis ).length === 30 && columns( withAxis )[0].height === '100%' && columns( withAxis )[1].height === '25%' );
check( 'one view is a full bar and the label says one', columns( stats._renderBars( [ { day : '2026-09-22', total : 1 } ], '2026-09' ) )[21].height === '100%'
	&& axisOf( stats._renderBars( [ { day : '2026-09-22', total : 1 } ], '2026-09' ) )[0].textContent === 'Peak: 1' );
check( 'a row with no day that counted something has no axis label', axisOf( stats._renderBars( [ { day : '2026-09-01', total : 0 } ], '2026-09' ) ).length === 0
	&& axisOf( stats._renderBars( [], '2026-09' ) ).length === 0 );

// --- The summary line ----------------------------------------------------------------------

check( 'one view on one day reads in the singular, both words', stats._summaryText( { views : 1, days : 1 } ) === '1 /_admin/stats/label/view · 1 /_admin/stats/label/day' );
check( 'more are plural, each word on its own count', stats._summaryText( { views : 12, days : 1 } ) === '12 /_admin/stats/label/views · 1 /_admin/stats/label/day'
	&& stats._summaryText( { views : 1, days : 3 } ) === '1 /_admin/stats/label/view · 3 /_admin/stats/label/days' );
check( 'none is plural too', stats._summaryText( { views : 0, days : 0 } ) === '0 /_admin/stats/label/views · 0 /_admin/stats/label/days' );

// --- The tables --------------------------------------------------------------------------------

const rows = stats._pageRows( [
	{ uri : '/about', title : 'About Us', views : 5 },
	{ uri : '/docs/intro', title : '', views : 3 },
	{ uri : '/…', title : '', views : 2 },
] );
check( 'a page row shows the title, else the path it was counted under', rows[0].title === 'About Us' && rows[1].title === '/docs/intro' );
check( '...the overflow bucket by a name of its own and no path', rows[2].title === '/_admin/stats/label/other' && rows[2].path === '' );
check( '...every other row keeps its path in a column of its own', rows[0].path === '/about' && rows[1].path === '/docs/intro' && rows[0].views === 5 );

stats._renderMonth( { month : '2026-09', days : [ { day : '2026-09-01', total : 8 } ], totals : { views : 8, days : 1 }, uris : [ { uri : '/about', title : 'About Us', views : 8 } ], referrers : [ { host : 'example.org', views : 3 } ] } );
check( 'the summary line is the singular-aware one', body.children[0].textContent === '8 /_admin/stats/label/views · 1 /_admin/stats/label/day' );
check( 'the pages table is handed a title column, a path column and the views', tableCalls.length === 2
	&& tableCalls[0].columns.map( function( column ) { return column.key } ).join(' ') === 'title path views' && tableCalls[0].rowKey === 'uri' );
check( '...the title column is named for the page, the path column for the path', tableCalls[0].columns[0].label === '/_admin/stats/label/uri' && tableCalls[0].columns[1].label === '/_admin/stats/label/path' );
const pathCell = tableCalls[0].columns[1].render( '/about', tableCalls[0].rows[0] );
check( '...the path is drawn as text in a muted cell', pathCell.className === 'stats-path' && pathCell.textContent === '/about' );
check( 'the referrers table has its two columns', tableCalls[1].columns.map( function( column ) { return column.key } ).join(' ') === 'host views' && tableCalls[1].rowKey === 'host' );

tableCalls.length = 0;
body.children.length = 0;
stats._renderMonth( { month : '2026-09', days : [], totals : { views : 0, days : 0 }, uris : [], referrers : [] } );
const tablesBox = body.children[body.children.length - 1];
const empties = tablesBox.children.map( function( card ) { return card.children[1].textContent } );
check( 'no pages and no referrers: each card says what is missing in words of its own', tableCalls.length === 0
	&& empties[0] === '/_admin/stats/empty' && empties[1] === '/_admin/stats/empty/referrers' );

// --- Asking the workbench ------------------------------------------------------------

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const wasPosted = [];
const keepSend = nino.http.sendRequest;
nino.http.sendRequest = function( uri, method, callback, data ) { wasPosted.push( [ uri, method, data ] ); callback( { status : 200, responseJSON : { ok : true } } ) };
let gotAnswer = null;
nino.admin.stats._apiCall( 'list', { a : 1 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	wasPosted.length === 1 && wasPosted[0][0] === '[[/nino/dir]]/_admin/' && wasPosted[0][1] === 'POST'
	&& wasPosted[0][2].action === 'stats/list' && wasPosted[0][2].data === '{"a":1}' && JSON.stringify( gotAnswer ) === '[200,{"ok":true}]' );

const wasRouted = [];
nino.adminUi.api = { call : function( action, payload, callback ) { wasRouted.push( [ action, payload ] ); callback( 200, { via : 'api' } ) } };
gotAnswer = null;
nino.admin.stats._apiCall( 'list', { b : 2 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'with it the action \'stats/<action>\' and the payload go to the helper, and nothing is posted by hand',
	wasRouted.length === 1 && wasRouted[0][0] === 'stats/list' && wasRouted[0][1].b === 2 && wasPosted.length === 1 && gotAnswer[1].via === 'api' );

check( 'a failed request says "(status) message" as it always did where the shell has no errorText()',
	nino.admin.stats._errorText( 503, { error : 'Busy' }, '/_admin/stats/error/load' ) === '(503) Busy'
	&& nino.admin.stats._errorText( 503, null, '/_admin/stats/error/load' ) === '(503) /_admin/stats/error/load' );
nino.adminUi.api.errorText = function( status, response, key ) { return 'told '+ status+ ' '+ response.error+ ' ['+ key+ ']' };
check( '...and what errorText() makes of it where it has one', nino.admin.stats._errorText( 503, { error : 'Busy' }, '/_admin/stats/error/load' ) === 'told 503 Busy [/_admin/stats/error/load]' );
delete nino.adminUi.api;
nino.http.sendRequest = keepSend;

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
