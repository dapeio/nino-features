/**
 *	Nino
 *	greeting-js-smoke.js	What the script of the Greeting feature does over the kernel's own
 *												Nino.admin.js and the Dashboard's script: it registers the card
 *												as an area at the head of the Dashboard and draws it from the
 *												answer, by the clock of the browser (the hour, the weekday, the
 *												day of the year), leaves out what is switched off, and sets the
 *												name as text and nothing else; and its panel draws three
 *												switches, saves them in one request and tells a Nino that has
 *												no head at its Dashboard so. Only Nino itself is a stand-in:
 *												the requests are kept for the test to answer, and the words are
 *												the ones of the text files.
 *
 *												The helpers are those of tests/harness.js of the checkout, three
 *												levels up or the one NINO_ROOT names. greeting-smoke.php runs
 *												this where node is on the path.
 *
 *	Usage: node features/Greeting/tests/greeting-js-smoke.js
 *	       NINO_ROOT=../nino node features/Greeting/tests/greeting-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, findAll, fire, element, documentOf } = require( path.join( root, 'tests/harness.js' ) );

/** A file of the feature the way load() and fills() want it: from the root of the checkout */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

/**
 *	The workbench in one interface language: the kernel's Nino.admin.js, its Dashboard's script
 *	(which holds the head) unless the Nino is one that has none, and the feature's script
 *
 *	@param		{string}		locale			en_US or de_DE
 *	@param		{Object}		[options]		{ head: false } for a Nino whose Dashboard has no head, { shell: false } for a page without the registry
 *
 *	@return		{Object}
 */
function world( locale, options ) {

	options = options || {};

	const words = Object.assign( fills( '_admin/text/'+ locale+ '.php' ), fills( own( 'text/'+ locale+ '.php' ) ) );
	const requests = [];
	const registered = [];
	const asked = [];
	const stored = {};
	let refreshed = 0;

	const mount = element('div');
	mount.id = 'greeting-list';
	const wrap = element('div');
	wrap.id = 'dashboard-list';

	const page = documentOf( mount );
	const found = page.getElementById;
	page.getElementById = function( id ) { return id === 'dashboard-list' ? wrap : found( id ) };

	const Nino = {
		content : { getText : function( key ) { asked.push( key ); return words[key] ?? '' } },
		events : { bindCallback : function() {} },
	};

	if( options.shell !== false )
		Nino.admin = { router : { set : function() {} }, dirty : { register : function( name, entry ) { registered.push( { name : name, entry : entry } ) }, refresh : function() { refreshed++ } } };

	const storage = options.storage ?? {
		getItem : function( key ) { return Object.prototype.hasOwnProperty.call( stored, key ) ? stored[key] : null },
		setItem : function( key, value ) { stored[key] = String( value ) },
	};

	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : { Nino : Nino, sessionStorage : storage } } );
	load( '_admin/assets/Nino.admin.js', context );

	// The one way a script talks to the server is the kernel's api.call(): what it is asked for is kept, to be answered
	Nino.adminUi.api.call = function( action, payload, callback ) {
		requests.push( { action : action, payload : payload, callback : callback, answered : false } );
	};

	if( options.head !== false ) {
		load( '_admin/Nino/Modules/Dashboard/assets/admin.js', context );
	}
	else {
		Nino.admin.dashboard = { init : function() {} };
	}

	load( own( 'assets/admin.js' ), context, 'admin.js' );

	const find = function( root, predicate ) { return findAll( root, predicate )[0] ?? null };

	return {
		Nino : Nino,
		panel : Nino.admin.greeting,
		dashboard : Nino.admin.dashboard,
		mount : mount,
		wrap : wrap,
		requests : requests,
		registered : registered,
		asked : asked,
		stored : stored,
		refreshed : function() { return refreshed },
		entry : function() { return registered[0].entry },
		boxes : function() { return findAll( mount, function( el ) { return el.tagName === 'INPUT' } ) },
		button : function() { return find( mount, function( el ) { return el.tagName === 'BUTTON' } ) },
		line : function() { return find( mount, function( el ) { return el.classList.contains('nino-admin-status') } ) },
		notices : function() { return findAll( mount, function( el ) { return el.className === 'nino-admin-notice' } ) },

		// The answer of the server to the request that is out
		answer : function( status, body ) {
			const open = requests.filter( function( request ) { return request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( status, body );
		},

		// The Dashboard shown with an answer of its summary: the card, if the head is there to draw it
		show : function( body ) {
			Nino.admin.dashboard.init();
			const open = requests.filter( function( request ) { return request.answered === false && request.action === 'dashboard/summary' } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( 200, body );
		},
	};
}

// A Wednesday, a Monday, a Friday, a Saturday, a Sunday of one January
const wednesday = ( hour, minute ) => new Date( 2026, 0, 14, hour ?? 9, minute ?? 0 );
const monday = new Date( 2026, 0, 12, 9, 0 );
const friday = new Date( 2026, 0, 16, 9, 0 );
const saturday = new Date( 2026, 0, 17, 9, 0 );
const sunday = new Date( 2026, 0, 18, 9, 0 );

const w = world('en_US');
// A Nino whose Dashboard has no head draws its own card: what hangs on the head is not asked of it, and the panel says so
const hasHead = typeof w.dashboard.head === 'object';
const words = fills( own('text/en_US.php') );
const card = w.panel.card;

/** The card for an answer of the server, at a moment of the browser's clock; the dice at one value unless given */
function draw( answer, at, dice ) {
	card.now = function() { return at };
	card.random = function() { return dice ?? 0 };
	w.asked.length = 0;
	return card.render( Object.assign( { show : { hello : true, sentence : true, facts : true }, name : '', previousLogin : null, sentences : [] }, answer ) );
}

/** The text of one line of a card, null where it has none */
function line( element, name ) {
	if( element === null )
		return null;
	const hits = findAll( element, function( el ) { return el.className === 'nino-admin-greeting-'+ name } );
	return hits.length === 1 ? hits[0].textContent : null;
}

/** A sentence as the server delivers it */
function sentence( text, when ) {
	return Object.assign( { text : text, phase : 'any', day : 'any', occasion : 'none' }, when ?? {} );
}

console.log('Greeting - the area at the head of the Dashboard\n');

if( hasHead === false )
	console.log('  --  - this Nino has no head at its Dashboard: the checks of the area were NOT run');
else {

check( 'the script registers the card at the head of the Dashboard under the name the server answers with', w.dashboard.head._areas.map( area => area.name ).join() === 'greeting' );

w.show( { tiles : [], notices : [ { text : 'x', values : [], link : '' } ], head : { greeting : { show : { hello : true, sentence : false, facts : false }, name : 'Ada', previousLogin : null } } } );
const at = index => w.wrap.children[index] ?? {};
check( 'the Dashboard draws the card first, before the notices and the tiles', at(0).id === 'admin-dashboard-greeting' && at(1).className === 'nino-admin-notice' && at(2).id === 'admin-dashboard-tiles' );

w.show( { tiles : [], notices : [] } );
check( 'an answer without a head draws no card, and the tiles come first', at(0).id === 'admin-dashboard-tiles' );

}

console.log('\nGreeting - the formula, the name\n');

[ [ 4, 59, 'night' ], [ 5, 0, 'early' ], [ 7, 59, 'early' ], [ 8, 0, 'morning' ], [ 10, 59, 'morning' ], [ 11, 0, 'noon' ],
	[ 13, 59, 'noon' ], [ 14, 0, 'afternoon' ], [ 16, 59, 'afternoon' ], [ 17, 0, 'evening' ], [ 21, 59, 'evening' ], [ 22, 0, 'night' ], [ 0, 0, 'night' ], [ 23, 59, 'night' ] ].forEach( function( limit ) {
	draw( { name : 'Ada' }, wednesday( limit[0], limit[1] ) );
	check( 'at '+ limit[0]+ ':'+ String( limit[1] ).padStart( 2, '0' )+ ' the formula is the one of the '+ limit[2], w.asked.indexOf( '/_admin/greeting/hello/'+ limit[2] ) !== -1
		&& w.asked.filter( key => key.startsWith('/_admin/greeting/hello/') ).length === 1 );
} );

check( 'a name follows the formula after a comma', line( draw( { name : 'David' }, wednesday( 9 ) ), 'hello' ) === words['/_admin/greeting/hello/morning']+ ', David.' );
check( '...without one there is the formula and the full stop, and no comma', line( draw( { name : '' }, wednesday( 9 ) ), 'hello' ) === words['/_admin/greeting/hello/morning']+ '.' );
check( '...a name that is no string is none', line( draw( { name : 7 }, wednesday( 9 ) ), 'hello' ) === words['/_admin/greeting/hello/morning']+ '.' );

{
	const name = '<b>x</b> [[/nino/dir]] %s $&';
	const drawn = draw( { name : name, sentences : [ sentence( 'Hello %s, welcome.' ) ] }, wednesday( 9 ) );
	check( 'a name with markup and a shortcode stays text in the formula', line( drawn, 'hello' ) === words['/_admin/greeting/hello/morning']+ ', '+ name+ '.' );
	check( '...and in the sentence, whose own blank takes it as it stands', line( drawn, 'sentence' ) === 'Hello '+ name+ ', welcome.' );
	check( '...and the card has nothing in it but its lines: no element came out of the name', drawn.children.length === 2 && findAll( drawn, function( el ) { return el.tagName === 'B' } ).length === 0
		&& findAll( drawn, function( el ) { return el.children.length > 0 && el !== drawn } ).length === 0 );
}

console.log('\nGreeting - the sentences\n');

{
	const sentences = [ sentence( 'One %s.' ), sentence( 'Two.' ), sentence( 'Three %s.' ), sentence( 'Four.' ) ];
	const seen = {};
	for( let dice = 0; dice < 1; dice += 0.05 )
		seen[ line( draw( { name : '', sentences : sentences }, wednesday( 9 ), dice ), 'sentence' ) ] = true;
	check( 'an account without a name gets the sentences without a blank only', Object.keys( seen ).sort().join() === 'Four.,Two.' );

	const named = {};
	for( let dice = 0; dice < 1; dice += 0.05 )
		named[ line( draw( { name : 'Ada', sentences : sentences }, wednesday( 9 ), dice ), 'sentence' ) ] = true;
	check( '...and with a name every one of them can come, none of them with the blank left in', Object.keys( named ).sort().join() === 'Four.,One Ada.,Three Ada.,Two.' && Object.keys( named ).every( key => key.indexOf('%s') === -1 ) );
}

{
	const sentences = [ sentence( 'Hour.', { phase : 'morning' } ), sentence( 'Anytime.' ), sentence( 'Monday.', { day : 'monday' } ), sentence( 'Friday.', { day : 'friday' } ),
		sentence( 'Weekend.', { day : 'weekend' } ), sentence( 'Evening.', { phase : 'evening' } ), sentence( 'Monday evening.', { phase : 'evening', day : 'monday' } ) ];
	const texts = function( at ) {
		const seen = {};
		for( let dice = 0; dice < 1; dice += 0.01 )
			seen[ line( draw( { sentences : sentences }, at, dice ), 'sentence' ) ] = true;
		return Object.keys( seen ).sort().join();
	};

	check( 'on a Monday the Monday sentences join those of the hour', texts( monday ) === 'Anytime.,Hour.,Monday.' );
	check( '...and the last one, which the dice at their highest reach, is one of the weekday', line( draw( { sentences : sentences }, monday, 0.999 ), 'sentence' ) === 'Monday.' );
	check( 'on a Friday the Friday sentences', texts( friday ) === 'Anytime.,Friday.,Hour.' );
	check( 'on a Saturday and on a Sunday the weekend sentences', texts( saturday ) === 'Anytime.,Hour.,Weekend.' && texts( sunday ) === 'Anytime.,Hour.,Weekend.' );
	check( 'on a day with no pool of its own none of the three', texts( wednesday( 9 ) ) === 'Anytime.,Hour.' );
	check( 'a sentence of another hour is not among them, and one of a weekday and an hour needs both', texts( new Date( 2026, 0, 12, 19, 0 ) ) === 'Anytime.,Evening.,Monday evening.,Monday.' );
}

{
	const sentences = [ sentence( 'Hour.', { phase : 'morning' } ), sentence( 'Anytime.' ), sentence( 'Monday.', { day : 'monday' } ),
		sentence( 'Christmas.', { occasion : 'christmas' } ), sentence( 'Christmas again.', { occasion : 'christmas', phase : 'evening' } ), sentence( 'New year.', { occasion : 'newyear' } ) ];
	const texts = function( at ) {
		const seen = {};
		for( let dice = 0; dice < 1; dice += 0.01 )
			seen[ line( draw( { sentences : sentences }, at, dice ), 'sentence' ) ] = true;
		return Object.keys( seen ).sort().join();
	};

	check( 'on the 24th of December only the sentences of the occasion are drawn, the hour and the weekday ignored', texts( new Date( 2026, 11, 24, 9, 0 ) ) === 'Christmas again.,Christmas.' );
	check( '...the 25th and the 26th the same, on a Saturday as on any day', texts( new Date( 2026, 11, 25, 9, 0 ) ) === 'Christmas again.,Christmas.' && texts( new Date( 2026, 11, 26, 9, 0 ) ) === 'Christmas again.,Christmas.' );
	check( 'the 23rd and the 27th are days like any other', texts( new Date( 2026, 11, 23, 9, 0 ) ) === 'Anytime.,Hour.' && texts( new Date( 2026, 11, 27, 9, 0 ) ) === 'Anytime.,Hour.' );
	check( 'the 31st of December and the 1st of January are the new year', texts( new Date( 2026, 11, 31, 9, 0 ) ) === 'New year.' && texts( new Date( 2027, 0, 1, 9, 0 ) ) === 'New year.' );
	check( '...and the 2nd of January is not', texts( new Date( 2027, 0, 2, 9, 0 ) ) === 'Anytime.,Hour.' );
	check( 'a sentence of an occasion is never drawn on a day without one', texts( wednesday( 9 ) ).indexOf('Christmas') === -1 && texts( wednesday( 9 ) ).indexOf('New year') === -1 );
}

{
	const sentences = [ sentence( 'Alpha.' ), sentence( 'Beta.' ), sentence( 'Gamma.' ) ];
	delete w.stored[ card.memory ];
	const first = line( draw( { sentences : sentences }, wednesday( 9 ), 0.5 ), 'sentence' );
	const second = line( draw( { sentences : sentences }, wednesday( 9 ), 0.5 ), 'sentence' );
	const third = line( draw( { sentences : sentences }, wednesday( 9 ), 0.5 ), 'sentence' );
	check( 'the same dice twice do not show the same sentence twice', first !== second && second !== third );
	check( '...a run of loads never repeats one', ( function() {
		let last = null;
		for( let i = 0; i < 40; i++ ) {
			const now = line( draw( { sentences : sentences }, wednesday( 9 ), ( i * 0.37 ) % 1 ), 'sentence' );
			if( now === last )
				return false;
			last = now;
		}
		return true;
	} )() );
	check( 'what is remembered is the last sentence, in the session storage and nowhere else', w.stored[ card.memory ] !== undefined && Object.keys( w.stored ).join() === card.memory );
	check( 'a single sentence is shown twice in a row - there is no other to show', line( draw( { sentences : [ sentence( 'Only.' ) ] }, wednesday( 9 ) ), 'sentence' ) === 'Only.'
		&& line( draw( { sentences : [ sentence( 'Only.' ) ] }, wednesday( 9 ) ), 'sentence' ) === 'Only.' );
	check( 'no sentence that fits leaves no line for it', line( draw( { sentences : [ sentence( 'Evening.', { phase : 'evening' } ) ] }, wednesday( 9 ) ), 'sentence' ) === null
		&& line( draw( { sentences : [] }, wednesday( 9 ) ), 'hello' ) !== null );
}

{
	// A browser that keeps nothing: the card is drawn all the same
	const blocked = world( 'en_US', { storage : { getItem : function() { throw new Error('blocked') }, setItem : function() { throw new Error('blocked') } } } );
	blocked.panel.card.now = function() { return wednesday( 9 ) };
	blocked.panel.card.random = function() { return 0 };
	check( 'where the browser keeps nothing the card is drawn all the same', ( function() {
		const drawn = blocked.panel.card.render( { show : { hello : true, sentence : true, facts : false }, name : '', previousLogin : null, sentences : [ sentence( 'Alpha.' ) ] } );
		return line( drawn, 'sentence' ) === 'Alpha.';
	} )() );
}

console.log('\nGreeting - the facts\n');

{
	const now = new Date( 2026, 0, 14, 12, 0 );
	const at = ( year, month, day, hour, minute ) => Math.floor( new Date( year, month, day, hour, minute ).getTime() / 1000 );
	const facts = answer => line( draw( answer, now ), 'facts' );

	check( 'the first visit is told as that', facts( { previousLogin : 0 } ) === words['/_admin/greeting/fact/first'] );
	check( 'a visit earlier today', facts( { previousLogin : at( 2026, 0, 14, 0, 5 ) } ) === words['/_admin/greeting/fact/today'] );
	check( 'yesterday, even a minute before midnight - it is the calendar day of the browser that counts, not 24 hours', facts( { previousLogin : at( 2026, 0, 13, 23, 59 ) } ) === words['/_admin/greeting/fact/yesterday']
		&& line( draw( { previousLogin : at( 2026, 0, 13, 23, 59 ) }, new Date( 2026, 0, 14, 0, 1 ) ), 'facts' ) === words['/_admin/greeting/fact/yesterday'] );
	check( 'a number of days ago, in the words of the language', facts( { previousLogin : at( 2026, 0, 9, 8, 0 ) } ) === words['/_admin/greeting/fact/days'].replace( '%s', '5' ) );
	check( '...across the turn of the month and the year', line( draw( { previousLogin : at( 2025, 11, 30, 8, 0 ) }, now ), 'facts' ) === words['/_admin/greeting/fact/days'].replace( '%s', '15' ) );
	check( 'a visit that is no number says nothing', facts( { previousLogin : null } ) === null && facts( { previousLogin : 'yesterday' } ) === null && facts( {} ) === null );

	check( 'the log since the visit is told from one entry on, one entry in the singular', facts( { activitySince : 0 } ) === null
		&& facts( { activitySince : 1 } ) === words['/_admin/greeting/fact/activity-one']
		&& facts( { activitySince : 12 } ) === words['/_admin/greeting/fact/activity'].replace( '%s', '12' ) );
	check( 'the backup only from the eighth day', facts( { backupDays : 7 } ) === null && facts( { backupDays : 8 } ) === words['/_admin/greeting/fact/backup'].replace( '%s', '8' )
		&& facts( { backupDays : 0 } ) === null );
	check( 'two of them are told in one line, with a dot between', facts( { previousLogin : at( 2026, 0, 14, 8, 0 ), backupDays : 9 } ) === words['/_admin/greeting/fact/today']+ ' · '+ words['/_admin/greeting/fact/backup'].replace( '%s', '9' ) );
	check( '...and never more than two: of three the visit and the backup stay', facts( { previousLogin : at( 2026, 0, 14, 8, 0 ), activitySince : 4, backupDays : 9 } ) === words['/_admin/greeting/fact/today']+ ' · '+ words['/_admin/greeting/fact/backup'].replace( '%s', '9' ) );
	check( 'a figure that is no whole number is not told', facts( { activitySince : '4', backupDays : 8.5 } ) === null );
	check( 'no fact, no line - the card keeps the other two', line( draw( { name : 'Ada', sentences : [ sentence( 'Hi.' ) ] }, now ), 'facts' ) === null && line( draw( { name : 'Ada', sentences : [ sentence( 'Hi.' ) ] }, now ), 'sentence' ) === 'Hi.' );
}

console.log('\nGreeting - the switches\n');

{
	const answer = { name : 'Ada', previousLogin : 0, sentences : [ sentence( 'Hi.' ) ] };
	const lines = show => { const drawn = draw( Object.assign( {}, answer, { show : show } ), wednesday( 9 ) ); return [ line( drawn, 'hello' ), line( drawn, 'sentence' ), line( drawn, 'facts' ) ].map( value => value !== null ) .join() };

	check( 'all three lines when all three are on', lines( { hello : true, sentence : true, facts : true } ) === 'true,true,true' );
	check( 'the formula alone is switched off', lines( { hello : false, sentence : true, facts : true } ) === 'false,true,true' );
	check( 'the sentence alone is switched off', lines( { hello : true, sentence : false, facts : true } ) === 'true,false,true' );
	check( 'the facts alone are switched off', lines( { hello : true, sentence : true, facts : false } ) === 'true,true,false' );
	check( 'two off leave the third', lines( { hello : false, sentence : false, facts : true } ) === 'false,false,true' );
	check( 'all three off, the card is not drawn at all', draw( Object.assign( {}, answer, { show : { hello : false, sentence : false, facts : false } } ), wednesday( 9 ) ) === null );
	check( 'no answer of the server, no card', card.render( undefined ) === null && card.render( null ) === null );
	check( 'the card is a card of the workbench with the id the stylesheet knows it by, and no heading in it', ( function() {
		const drawn = draw( answer, wednesday( 9 ) );
		return drawn.id === 'admin-dashboard-greeting' && drawn.className === 'nino-admin-card nino-admin-greeting'
			&& findAll( drawn, function( el ) { return /^H[1-6]$/.test( el.tagName ) } ).length === 0 && drawn.getAttribute('aria-live') === null && drawn.getAttribute('role') === null;
	} )() );
}

for( const locale of [ 'en_US', 'de_DE' ] ) {
	const stand = world( locale );
	const asked = [];
	stand.Nino.content.getText = ( getText => key => { asked.push( key ); return getText( key ) } )( stand.Nino.content.getText );
	stand.panel.card.now = function() { return wednesday( 9 ) };
	stand.panel.card.random = function() { return 0 };
	const drawn = stand.panel.card.render( { show : { hello : true, sentence : false, facts : true }, name : 'Ada', previousLogin : 0 } );
	const own_words = fills( own( 'text/'+ locale+ '.php' ) );
	check( 'every word the card asks for is there in '+ locale, asked.length > 0 && asked.every( key => own_words[key] !== undefined ) && drawn !== null );
}

console.log('\nGreeting - the panel\n');

{
	const p = world('en_US');

	check( 'nothing is asked before the pane is shown', p.requests.length === 0 && p.registered.length === 0 );

	p.panel.showCurrent();
	p.panel.showCurrent();
	check( 'showing it asks for greeting/list, once however often the shell shows it while the answer is out', p.requests.length === 1 && p.requests[0].action === 'greeting/list' );

	p.answer( 200, { lines : { hello : true, sentence : false, facts : true } } );
	check( 'the three lines are drawn as switches, in the order of the card, each as the server says', p.boxes().length === 3 && p.boxes().map( box => box.checked ).join() === 'true,false,true'
		&& p.boxes().map( box => box.dataset.key ).join() === 'hello,sentence,facts' );
	check( '...with the words of the panel for the labels, and a button that saves with its word', p.button().textContent === words['/_admin/greeting/label/save'] && p.button().className === 'nino-admin-btn-primary'
		&& findAll( p.mount, el => el.tagName === '#TEXT' ).map( el => el.textContent ).indexOf( words['/_admin/greeting/label/sentence'] ) !== -1 );
	check( '...a Nino that has a head says nothing of one', hasHead === false || p.notices().length === 0 );
	check( '...and the shell has the entry, which holds nothing unsaved', p.registered.length === 1 && p.registered[0].name === 'greeting' && p.entry().isDirty() === false );

	p.boxes()[1].checked = true;
	check( 'a switch moved is unsaved input', p.entry().isDirty() === true );

	fire( p.button(), 'click' );
	check( 'a save posts greeting/save with the three lines as booleans', p.requests.length === 2 && p.requests[1].action === 'greeting/save'
		&& JSON.stringify( p.requests[1].payload ) === '{"lines":{"hello":true,"sentence":true,"facts":true}}' && p.line().dataset.state === 'saving' );

	p.answer( 200, { lines : { hello : true, sentence : true, facts : true } } );
	check( '...the line says saved, the shell is told the panel is clean, and the switches are what the server stored', p.line().dataset.state === 'saved' && p.refreshed() === 1
		&& p.entry().isDirty() === false && p.boxes().every( box => box.checked === true ) );

	p.boxes()[0].checked = false;
	p.entry().discard();
	check( 'discarding puts the switches back as they were saved', p.boxes()[0].checked === true && p.entry().isDirty() === false );

	const told = [];
	p.boxes()[2].checked = false;
	p.entry().save( function( ok ) { told.push( ok ) } );
	p.answer( 400, { error : 'facts: expected true or false', code : 'bool', params : [], field : 'facts' } );
	check( 'a refused save is told in the sentence of its code and ends in done( false ), the switches keeping what was set', p.line().dataset.state === 'error' && told.length === 1 && told[0] === false
		&& p.boxes()[2].checked === false && p.entry().isDirty() === true );
}

{
	const p = world( 'en_US', { shell : false } );
	p.panel.showCurrent();
	p.answer( 200, { lines : { hello : true, sentence : true, facts : true } } );
	let threw = null;
	try {
		fire( p.button(), 'click' );
		p.answer( 200, { lines : { hello : true, sentence : true, facts : true } } );
	}
	catch( error ) { threw = error }
	check( 'a page without the shell\'s registry saves all the same', threw === null && p.line().dataset.state === 'saved' && p.registered.length === 0 );
}

{
	const p = world( 'en_US' );
	p.panel.showCurrent();
	p.answer( 500, { error : 'x' } );
	check( 'a list that fails is shown as a failure, and nothing is drawn', p.boxes().length === 0 && findAll( p.mount, el => el.className === 'nino-admin-error' ).length === 1 );
}

console.log('\nGreeting - a Nino whose Dashboard has no head\n');

{
	const old = world( 'en_US', { head : false } );

	check( 'the script registers nothing and throws nothing where the Dashboard has no head', old.dashboard.head === undefined );

	old.panel.showCurrent();
	old.answer( 200, { lines : { hello : true, sentence : true, facts : true } } );
	check( 'the panel says so in its own word, and still shows the three switches', old.notices().length === 1 && old.notices()[0].textContent === words['/_admin/greeting/hint/head'] && old.boxes().length === 3 );
}

done();
