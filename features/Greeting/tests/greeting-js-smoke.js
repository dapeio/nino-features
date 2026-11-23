/**
 *	Nino
 *	greeting-js-smoke.js	What the script of the Greeting feature does over the kernel's own
 *												Nino.admin.js and the Dashboard's script: it registers the card as
 *												an area at the head of the Dashboard and draws it from the answer by
 *												the clock of the browser, leaves out what is switched off and sets
 *												the name as text; and its panel shows the same card, or says that
 *												all three lines are off. Only Nino itself is a stand-in: the
 *												requests are kept for the test to answer, and the words are the
 *												ones of the text files.
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
const { check, done, fills, load, findAll, element, documentOf, fire, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

/** A file of the feature the way load() and fills() want it: from the root of the checkout */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

const words = Object.assign( fills( '_admin/text/en_US.php' ), fills( own('text/en_US.php') ) );
const requests = [];
const stored = {};

const mount = element('div');
mount.id = 'greeting-list';
const wrap = element('div');
wrap.id = 'dashboard-list';
const page = documentOf( mount );
const found = page.getElementById;
page.getElementById = function( id ) { return id === 'dashboard-list' ? wrap : found( id ) };

const codec = shellRouter( { greeting : [ [ 'greeting', 'greeting' ], [ 'greeting-settings', 'settings' ] ] } );

const stub = {
	content : { getText : function( key ) { return words[key] ?? '' } },
	events : { bindCallback : function() {} },
	// The router of the workbench itself: the way to the settings is an address it writes and reads
	admin : { router : codec.stub( { set : function() {} } ) },
};
const storage = {
	getItem : function( key ) { return Object.prototype.hasOwnProperty.call( stored, key ) ? stored[key] : null },
	setItem : function( key, value ) { stored[key] = String( value ) },
};
const context = vm.createContext( { console : console, document : page, Nino : stub, window : { Nino : stub, sessionStorage : storage, location : { hash : '' } } } );

load( '_admin/assets/Nino.admin.js', context );
stub.adminUi.api.call = function( action, payload, callback ) { requests.push( { action : action, callback : callback } ) };
load( '_admin/Nino/Modules/Dashboard/assets/admin.js', context );
load( own('assets/admin.js'), context, 'admin.js' );

const card = stub.admin.greeting.card;
const all = { hello : true, sentence : true, facts : true };

/** The card for an answer of the server at a moment of the browser's clock, the dice at one value unless given */
function draw( answer, at, dice ) {
	card.now = function() { return at };
	card.random = function() { return dice ?? 0 };
	return card.render( Object.assign( { show : all, name : '', previousLogin : null, sentences : [] }, answer ) );
}

/** The text of one line of a card, null where it has none */
function line( drawn, name ) {
	const hits = drawn === null ? [] : findAll( drawn, function( el ) { return el.className === 'greeting-'+ name } );
	return hits.length === 1 ? hits[0].textContent : null;
}

const sentence = ( text, when ) => Object.assign( { text : text, phase : 'any', day : 'any', occasion : 'none' }, when ?? {} );
const wednesday = ( hour ) => new Date( 2026, 0, 14, hour, 0 );

console.log('Greeting - the area at the head of the Dashboard\n');

check( 'the script registers the card at the head of the Dashboard under the name the server answers with', stub.admin.dashboard.head._areas.map( area => area.name ).join() === 'greeting' );

stub.admin.dashboard.init();
requests.filter( r => r.action === 'dashboard/summary' ).pop().callback( 200, { tiles : [], notices : [ { text : 'x', values : [], link : '' } ], head : { greeting : { show : { hello : true, sentence : false, facts : false }, name : 'Ada', previousLogin : null } } } );
check( 'the Dashboard draws the card before the notices and the tiles', wrap.children[0].className.includes('greeting-card') === true && wrap.children[1].className === 'nino-admin-notice' && wrap.children[2].id === 'admin-dashboard-tiles' );

console.log('\nGreeting - the hour, the name, the sentences\n');

const phases = [ [ 4, 'night' ], [ 5, 'early' ], [ 8, 'morning' ], [ 11, 'noon' ], [ 14, 'afternoon' ], [ 17, 'evening' ], [ 22, 'night' ] ];
check( 'the formula follows the hour of the browser: the six times of day begin at 5, 8, 11, 14, 17 and 22', phases.every( p => line( draw( {}, wednesday( p[0] ) ), 'hello' ) === words['/_admin/greeting/hello/'+ p[1]]+ '.' ) );

const markup = '<b>x</b> [[/nino/dir]] %s $&';
const drawn = draw( { name : markup, sentences : [ sentence( 'Hello %s, welcome.' ) ] }, wednesday( 9 ) );
check( 'a name with markup and a shortcode stays text, in the formula and in the sentence', line( drawn, 'hello' ) === words['/_admin/greeting/hello/morning']+ ', '+ markup+ '.'
	&& line( drawn, 'sentence' ) === 'Hello '+ markup+ ', welcome.' && findAll( drawn, el => el.tagName === 'B' ).length === 0 );

check( 'a sentence with a blank is for an account that has a name', line( draw( { name : '', sentences : [ sentence( 'Hi %s.' ) ] }, wednesday( 9 ) ), 'sentence' ) === null
	&& line( draw( { name : 'Ada', sentences : [ sentence( 'Hi %s.' ) ] }, wednesday( 9 ) ), 'sentence' ) === 'Hi Ada.' );

const pool = [ sentence( 'Any.' ), sentence( 'Night.', { phase : 'night' } ), sentence( 'Monday.', { day : 'monday' } ), sentence( 'Christmas.', { occasion : 'christmas' } ) ];
const at = ( date ) => card.fitting( pool, date, 'Ada' ).map( s => s.text ).join();
check( 'a sentence fits the hour, the weekday and the occasion, and an occasion has only its own', at( new Date( 2026, 0, 14, 23 ) ) === 'Any.,Night.' && at( new Date( 2026, 0, 12, 9 ) ) === 'Any.,Monday.'
	&& at( new Date( 2026, 11, 25, 9 ) ) === 'Christmas.' );

const two = [ sentence( 'One.' ), sentence( 'Two.' ) ];
const first = card.pick( two );
check( 'the sentence is never the one shown the last time while there is another', card.pick( two ) !== first );

check( 'a line that is off is not drawn, and a card with no line is none', line( draw( { show : { hello : true, sentence : false, facts : false } }, wednesday( 9 ) ), 'sentence' ) === null
	&& draw( { show : { hello : false, sentence : false, facts : false } }, wednesday( 9 ) ) === null );

const facts = ( answer ) => line( draw( Object.assign( { show : { hello : false, sentence : false, facts : true } }, answer ), new Date( 2026, 0, 14, 9 ) ), 'facts' );
check( 'the facts are the visit, the log and the backup, and of three the log is the one left out', facts( { previousLogin : 0 } ) === words['/_admin/greeting/fact/first']
	&& facts( { previousLogin : Math.floor( new Date( 2026, 0, 14, 7 ).getTime() / 1000 ), activitySince : 3, backupDays : 9 } ) === words['/_admin/greeting/fact/today']+ ' · '+ words['/_admin/greeting/fact/backup'].replace( '%s', '9' ) );

console.log('\nGreeting - the panel\n');

const answer = ( status, body ) => requests.filter( r => r.action === 'greeting/list' ).pop().callback( status, body );
stub.admin.greeting.showCurrent();
answer( 200, { show : all, name : 'Ada', previousLogin : null, sentences : [] } );
check( 'the panel shows the card of the account', findAll( mount, el => el.className.includes('greeting-card') ).length === 1 );

stub.admin.greeting.showCurrent();
answer( 200, { show : { hello : false, sentence : false, facts : false }, name : 'Ada' } );
const empty = findAll( mount, el => el.className.includes('nino-admin-empty') );
check( 'with all three lines off it says so in an empty state with a button to the settings, and no card', empty.length === 1 && findAll( mount, el => el.tagName === 'BUTTON' ).length === 1 && findAll( mount, el => el.className.includes('greeting-card') ).length === 0 );

fire( findAll( mount, el => el.tagName === 'BUTTON' )[0], 'click' );
check( 'the button to the settings moves to the tab of the settings: the address the router writes, and reads back as that tab', context.window.location.hash === '#greeting/settings' && codec.read( context.window.location.hash ).panel === 'greeting-settings' );

done();
