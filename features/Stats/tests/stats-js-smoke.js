/**
 *	Nino
 *	stats-js-smoke.js		What the Stats panel's script does: the panel it
 *											builds on Nino.adminUi.panel() - the months asked for on
 *											every showing, the selector, the month asked for when one
 *											is chosen, a failure said under the mount - and what it
 *											draws for one month: the bar row, over a dom stand-in.
 *											The store holds a day once it has a view, so what the
 *											panel is handed is the days with data - and the row has
 *											to be the month anyway, one column per day, the empty
 *											ones as a baseline mark. The row's axis label, the
 *											summary line in the singular where it is one, and the
 *											tables: the pages with a title column and a path column,
 *											and the two empty texts.
 *
 *											No jsdom, no dependency: the same element stand-in the
 *											other feature tests build, so this runs with nothing but
 *											node. The workbench (Nino.adminUi, Nino.content) is a
 *											stand-in of its own, kept to what docs/features.md says
 *											of it. stats-smoke.php runs this through node when node
 *											is on the path, so bin/check.sh and CI cover it; it also
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

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		className		: '',
		id					: '',
		textContent	: '',
		title				: '',
		style				: {},
		appendChild	: function( child ) { this.children.push( child ); return child },
	};

	// Emptied the way a browser empties an element, which is the only use the panel makes of it
	Object.defineProperty( el, 'innerHTML', { set : function( html ) { if( html === '' ) this.children.length = 0 }, get : function() { return '' } } );

	return el;
}

/** The element of that id anywhere under a node, as getElementById() finds it */
function find( node, id ) {

	if( node.id === id )
		return node;

	for( const child of node.children ) {
		const hit = find( child, id );
		if( hit !== null )
			return hit;
	}

	return null;
}

/*	The namespace the panel script extends - a local stand-in, not the
	global, so the file lints like a browser script with the rest of them.
	The workbench is a stand-in too, kept to what the manual says of the
	members the script calls: api.call() keeps its requests and answers from
	the table below; showError() writes the one line the real one writes, in
	place of whatever stood in the container; panel() is the lifecycle the
	manual describes - one request at a time, the answer drawn by render(),
	a failure said under the mount with the panel's key, showCurrent() asking
	again on every showing where the spec says 'always'	*/
const requests = [];
const tableCalls = [];
const fields = [];
const answers = {};
const page = element('div');
const list = element('div');
list.id = 'stats-list';
page.appendChild( list );

const nino = {
	admin		: {},
	adminUi	: {
		emptyState : function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
		// What the shared table component was handed, call by call
		table : function( options ) { tableCalls.push( options ) },
		// The field a person chooses a month in: kept, so the test can choose
		selectField : function( options ) { const field = element('div'); field.className = 'nino-admin-field'; fields.push( options ); return field },
		// The placeholders %s %d %n, filled in order
		format : function( text, ...params ) { let at = 0; return String( text ).replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } ) },
		api : { call : function( action, payload, callback ) {
			requests.push( { action : action, payload : payload } );
			const answer = answers[action]( payload );
			callback( answer[0], answer[1] );
		} },
		showError : function( container, status, response, key ) {
			container.innerHTML = '';
			const p = element('p');
			p.className = 'nino-admin-error';
			p.textContent = 'told '+ status+ ' '+ ( response.code || response.error )+ ' ['+ key+ ']';
			container.appendChild( p );
			return p;
		},
		panel : function( spec ) {
			const self = {
				uri			: spec.uri,
				ready		: false,
				loading	: false,
				data		: null,
				mount		: function() { return sandbox.document.getElementById( spec.mount ) },
				call		: function( action, payload, done ) { nino.adminUi.api.call( spec.uri+ '/'+ action, payload, done ) },
				fail		: function( container, status, response, key ) { return nino.adminUi.showError( container, status, response, key || '/_admin/'+ spec.uri+ '/error/load' ) },
				init		: function() {
					if( self.loading === true || self.mount() === null )
						return;
					self.loading = true;
					self.call( spec.action || 'list', spec.payload || {}, function( status, response ) {
						self.loading = false;
						if( status === 200 ) {
							self.data = response;
							self.ready = true;
							spec.render( response, self );
							return;
						}
						self.fail( self.mount(), status, response );
						self.ready = false;
					} );
				},
				showCurrent : function() {
					if( self.ready === false || spec.show === 'always' )
						self.init();
				},
			};
			return self;
		},
	},
	// The key itself, except where a placeholder has to be filled
	content	: { getText : function( key ) { return key === '/_admin/stats/label/max' ? 'Peak: %d' : key } },
};
const sandbox = { console : console, document : { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return find( page, id ) } }, Nino : nino };
sandbox.window = sandbox;
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const stats = nino.admin.stats;

/** Every column of a drawn row: its day number, its bar's height and whether it is marked empty */
function columns( row ) {
	return row.children.filter( function( col ) { return col.className.indexOf('stats-bar-col') === 0 } ).map( function( col ) {
		const bar = col.children[0], label = col.children[1];
		return { day : label.textContent, height : bar.style.height, empty : col.className.indexOf('is-empty') !== -1, value : bar.children[0].textContent };
	} );
}

// --- The panel: the months, then the month ---------------------------------------

const views = { '2026-10' : 8, '2026-09' : 3 };
answers['stats/months'] = function() { return [ 200, { months : [ '2026-10', '2026-09' ] } ] };
answers['stats/month'] = function( payload ) {
	return [ 200, {
		month		: payload.month,
		days		: [ { day : payload.month+ '-01', total : views[payload.month] } ],
		totals	: { views : views[payload.month], days : 1 },
		uris		: [ { uri : '/about', title : 'About Us', views : views[payload.month] } ],
		referrers	: [ { host : 'example.org', views : 3 } ],
	} ];
};
const actions = function() { return requests.map( function( request ) { return request.action } ).join(' ') };
const bodyOf = function() { return find( page, 'stats-body' ) };

check( 'nothing is asked for before the panel is shown', requests.length === 0 );

stats.showCurrent();
check( 'showing the panel asks for the months, and then for the newest of them', actions() === 'stats/months stats/month' && requests[1].payload.month === '2026-10' );
check( 'the selector offers the months as the server listed them, the newest chosen', fields.length === 1 && fields[0].key === 'month' && fields[0].value === '2026-10'
	&& fields[0].label === '/_admin/stats/label/month' && fields[0].options.map( function( option ) { return option.value+ '='+ option.label } ).join(' ') === '2026-10=2026-10 2026-09=2026-09' );
check( '...and the month is drawn under it: the summary line, the bar row, the tables', list.children.length === 2 && list.children[1] === bodyOf()
	&& bodyOf().children[0].id === 'stats-summary' && bodyOf().children[0].textContent === '8 /_admin/stats/label/views · 1 /_admin/stats/label/day'
	&& bodyOf().children[1].id === 'stats-bars' && bodyOf().children[2].id === 'stats-tables' );

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

check( 'the pages table is handed a title column, a path column and the views', tableCalls.length === 2
	&& tableCalls[0].columns.map( function( column ) { return column.key } ).join(' ') === 'title path views' && tableCalls[0].rowKey === 'uri' );
check( '...the title column is named for the page, the path column for the path', tableCalls[0].columns[0].label === '/_admin/stats/label/uri' && tableCalls[0].columns[1].label === '/_admin/stats/label/path' );
const pathCell = tableCalls[0].columns[1].render( '/about', tableCalls[0].rows[0] );
check( '...the path is drawn as text in a muted cell', pathCell.className === 'stats-path' && pathCell.textContent === '/about' );
check( 'the referrers table has its two columns', tableCalls[1].columns.map( function( column ) { return column.key } ).join(' ') === 'host views' && tableCalls[1].rowKey === 'host' );

tableCalls.length = 0;
stats._renderMonth( { month : '2026-09', days : [], totals : { views : 0, days : 0 }, uris : [], referrers : [] } );
const tablesBox = bodyOf().children[bodyOf().children.length - 1];
const empties = tablesBox.children.map( function( card ) { return card.children[1].textContent } );
check( 'no pages and no referrers: each card says what is missing in words of its own', tableCalls.length === 0
	&& empties[0] === '/_admin/stats/empty' && empties[1] === '/_admin/stats/empty/referrers' );

// --- Choosing a month, showing again, failing -------------------------------------

requests.length = 0;
fields[0].onChange( '2026-09' );
check( 'choosing a month asks for that month and draws its numbers in place of the last one\'s', actions() === 'stats/month' && requests[0].payload.month === '2026-09'
	&& bodyOf().children.filter( function( child ) { return child.id === 'stats-summary' } ).length === 1
	&& bodyOf().children[0].textContent === '3 /_admin/stats/label/views · 1 /_admin/stats/label/day' );

requests.length = 0;
fields.length = 0;
stats.showCurrent();
check( 'showing the panel again asks for the months again - the numbers are current on every showing - and keeps the month chosen',
	actions() === 'stats/months stats/month' && requests[1].payload.month === '2026-09' && fields.length === 1 && fields[0].value === '2026-09' );

answers['stats/months'] = function() { return [ 200, { months : [ '2026-10' ] } ] };
requests.length = 0;
fields.length = 0;
stats.showCurrent();
check( 'a month that has left the list - the retention swept it - falls back to the newest', fields[0].value === '2026-10' && requests[1].payload.month === '2026-10' );

answers['stats/months'] = function() { return [ 200, { months : [] } ] };
requests.length = 0;
fields.length = 0;
stats.showCurrent();
check( 'no month at all says so: no selector, and no month asked for', actions() === 'stats/months' && fields.length === 0
	&& list.children.length === 1 && list.children[0].className === 'nino-admin-empty' && list.children[0].textContent === '/_admin/stats/empty' );

answers['stats/months'] = function() { return [ 503, { error : 'Busy' } ] };
requests.length = 0;
stats.showCurrent();
check( 'a months list that fails to load is said under the mount in the panel\'s own sentence, and nothing more is asked', actions() === 'stats/months'
	&& list.children.length === 1 && list.children[0].className === 'nino-admin-error' && list.children[0].textContent === 'told 503 Busy [/_admin/stats/error/load]' );

answers['stats/months'] = function() { return [ 200, { months : [ '2026-10' ] } ] };
answers['stats/month'] = function() { return [ 400, { code : 'stats_month' } ] };
requests.length = 0;
stats.showCurrent();
check( 'a month the server refuses is said the same way, in place of the selector and the numbers', actions() === 'stats/months stats/month'
	&& list.children.length === 1 && list.children[0].textContent === 'told 400 stats_month [/_admin/stats/error/load]' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
