/**
 *	Nino features
 *	sysadmin-js-smoke.js	What the script of the Sysadmin panel does over the kernel's own Nino.admin.js: it asks
 *												for the status, draws who is told, the clocks and what the report finds - the rank as a
 *												badge, the sentence as text and nothing else - says that there is no address where there
 *												is none and leaves the button off, asks before the test mail goes out with the dialog of
 *												the workbench, sends it in one request and says how it went by the line of its one bar.
 *												Only Nino itself is a stand-in: the requests are kept for the test to answer, and the words
 *												are the ones of the text files.
 *
 *												The helpers are those of tests/harness.js of the checkout, three levels up or the one
 *												NINO_ROOT names. sysadmin-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Sysadmin/tests/sysadmin-js-smoke.js
 *	       NINO_ROOT=../nino node features/Sysadmin/tests/sysadmin-js-smoke.js
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
 *	The workbench in one interface language
 *
 *	@param		{string}		locale			en_US or de_DE
 *
 *	@return		{Object}
 */
function world( locale ) {

	const words = Object.assign( fills( '_admin/text/'+ locale+ '.php' ), fills( own( 'text/'+ locale+ '.php' ) ) );
	const requests = [];
	const asked = [];
	const confirms = [];

	const mount = element('div');
	mount.id = 'sysadmin-list';
	documentOf( mount );

	const Nino = {
		content : { getText : function( key ) { asked.push( key ); return words[key] ?? '' } },
		events : { bindCallback : function() {} },
		admin : { router : { set : function() {} }, dirty : { register : function() {}, refresh : function() {} } },
	};

	const context = vm.createContext( { console : console, document : documentOf( mount ), Nino : Nino, window : { Nino : Nino } } );
	load( '_admin/assets/Nino.admin.js', context );

	// The one way a script talks to the server is the kernel's api.call(): what it is asked for is kept, to be answered
	Nino.adminUi.api.call = function( action, payload, callback ) {
		requests.push( { action : action, payload : payload, callback : callback, answered : false } );
	};

	// The dialog of the workbench: what it is asked is kept, and the test answers it
	Nino.adminUi.confirm = function( level, options ) {
		confirms.push( { level : level, options : options } );
		return true;
	};

	load( own( 'assets/admin.js' ), context, 'admin.js' );

	const find = function( root, predicate ) { return findAll( root, predicate )[0] ?? null };

	return {
		Nino : Nino,
		panel : Nino.admin.sysadmin,
		mount : mount,
		words : words,
		requests : requests,
		asked : asked,
		confirms : confirms,
		button : function() { return find( mount, function( el ) { return el.tagName === 'BUTTON' } ) },
		form : function() { return find( mount, function( el ) { return el.tagName === 'FORM' } ) },
		line : function() { return find( mount, function( el ) { return el.classList.contains('nino-admin-status') } ) },
		notices : function() { return findAll( mount, function( el ) { return el.className === 'nino-admin-notice' } ) },
		facts : function() { return findAll( mount, function( el ) { return el.tagName === 'DT' || el.tagName === 'DD' } ).map( el => el.textContent ) },
		badges : function() { return findAll( mount, function( el ) { return el.classList.contains('nino-admin-badge') } ) },
		items : function() { return findAll( mount, function( el ) { return el.tagName === 'LI' } ) },

		// The screen drawn from an answer of the server
		show : function( body ) {
			Nino.admin.sysadmin.showCurrent();
			const open = requests.filter( function( request ) { return request.answered === false && request.action === 'sysadmin/status' } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( 200, body );
		},

		// The answer of the server to the request that is out
		answer : function( status, body ) {
			const open = requests.filter( function( request ) { return request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( status, body );
		},
	};
}

/** All the text of an element and of what is in it, in order */
const full = function( el ) { return String( el.textContent ?? '' )+ ( el.children ?? [] ).map( full ).join('') };

const status = function( extra ) {
	return Object.assign( {
		recipients : [ 'a@example.org', 'b@example.org' ], beat : 'weekly', gap : 24, lastProblem : '2026-11-14 06:00', lastBeat : '2026-11-14 06:00', nextBeat : '2026-11-21 06:00',
		failed : null, aborted : 0, testWait : 0, findings : [ { rank : 3, text : 'A backup did not run', event : false }, { rank : 1, text : 'Texts to translate', event : false } ],
	}, extra ?? {} );
};

[ 'en_US', 'de_DE' ].forEach( function( locale ) {

console.log('Sysadmin - the panel in '+ locale+ '\n');

const w = world( locale );
w.show( status() );

check( 'the screen asks for the status when it is shown, and draws the answer into its mount', w.requests.length === 1 && w.requests[0].action === 'sysadmin/status' && w.mount.children.length > 0 );
check( 'it names who is told, the distance, both clocks and the rhythm, in pairs of a name and a value', w.facts().length === 12 && w.facts()[1] === 'a@example.org, b@example.org' && w.facts().indexOf('2026-11-21 06:00') !== -1 );
check( 'every finding is a row with a badge that says its rank in words, and the state of the badge agrees with it', w.badges().length === 2
	&& w.badges()[0].textContent === w.words['/_admin/sysadmin/rank/error'] && w.badges()[0].dataset.state === 'error' && w.badges()[1].textContent === w.words['/_admin/sysadmin/rank/notice'] && w.badges()[1].dataset.state === 'muted' );
check( '...the sentence is in the row as text', full( w.items()[0] ).indexOf('A backup did not run') !== -1 );
check( 'with an address there is no notice, and the one button of the one bar is on', w.notices().length === 0 && w.button().disabled === false && findAll( w.mount, function( el ) { return el.classList.contains('nino-admin-actionbar') } ).length === 1
	&& findAll( w.mount, function( el ) { return el.tagName === 'BUTTON' } ).length === 1 );

w.show( status( { findings : [ { rank : 2, text : '<img src=x onerror=alert(1)> [[/nino/dir]] %s $&', event : false } ] } ) );
check( 'what the server sent is set as text: no element came out of a finding', findAll( w.mount, function( el ) { return el.tagName === 'IMG' } ).length === 0 && full( w.items()[0] ).indexOf('<img src=x onerror=alert(1)> [[/nino/dir]] %s $&') !== -1 );

w.show( status( { findings : [] } ) );
check( 'a report that finds nothing says so, in the one empty state of the workbench', findAll( w.mount, function( el ) { return el.className === 'nino-admin-empty' } ).length === 1 && w.badges().length === 0 );

w.show( status( { failed : { at : '2026-11-14 07:00', count : 3 } } ) );
check( 'a send that failed is one more row of the facts, with its time and its count', w.facts().length === 14 && w.facts().some( text => text.indexOf('2026-11-14 07:00') !== -1 && text.indexOf('3') !== -1 ) );

w.show( status( { gap : 1, aborted : 2 } ) );
check( 'a distance of one hour is written in the singular, and runs that ended without a word are one more row of the facts', w.facts().indexOf( w.words['/_admin/sysadmin/label/hour'] ) !== -1 && w.facts().indexOf( w.words['/_admin/sysadmin/label/hours'] ) === -1 && w.facts().length === 14 && w.facts().indexOf('2') !== -1 );

w.show( status( { recipients : [], nextBeat : null, lastProblem : null, lastBeat : null } ) );
check( 'with no address the screen says so with a link to the Settings tab, and the button is off', w.notices().length === 1 && w.notices()[0].textContent.indexOf( w.words['/_admin/sysadmin/hint/none'] ) === 0
	&& findAll( w.notices()[0], function( el ) { return el.tagName === 'A' && el.href === '#sysadmin-settings' } ).length === 1 && w.button().disabled === true );
check( '...and a submit of the form does nothing then', ( function() { const before = w.requests.length; fire( w.form(), 'submit', { preventDefault : function() {} } ); return w.requests.length === before && w.confirms.length === 0 } )() );

// The test mail
w.show( status() );
let prevented = 0;
fire( w.form(), 'submit', { preventDefault : function() { prevented++ } } );
check( 'the button submits a form and the script keeps the browser from sending it itself', prevented === 1 );
check( 'the test mail is asked for with the dialog of the workbench, as a routine, naming the addresses', w.confirms.length === 1 && w.confirms[0].level === 'routine'
	&& w.confirms[0].options.message.indexOf('a@example.org, b@example.org') !== -1 && w.confirms[0].options.yes === w.words['/_admin/sysadmin/label/send'] && w.confirms[0].options.no === w.words['/_admin/common/label/cancel'] );
const before = w.requests.length;
w.confirms[0].options.onChoose( false );
check( 'a No sends nothing', w.requests.length === before && w.button().disabled === false );
w.confirms[0].options.onChoose( true );
check( 'a Yes sends one request, test, with nothing in it, and the button is off while it is out', w.requests.length === before + 1 && w.requests[before].action === 'sysadmin/test' && JSON.stringify( w.requests[before].payload ) === '{}' && w.button().disabled === true );
w.answer( 200, { sent : 2 } );
check( 'the line says how many addresses it went to and the button is on again', w.button().disabled === false && w.line().dataset.state === 'idle' && w.line().textContent.indexOf('2') !== -1 );

fire( w.form(), 'submit', { preventDefault : function() {} } );
w.confirms[1].options.onChoose( true );
w.answer( 429, { error : 'a test mail was sent a moment ago', code : 'sysadmin_wait', params : [ '42' ] } );
check( 'a refusal with a code is said in the sentence of the code, with its number, in the one line', w.line().dataset.state === 'error' && w.line().textContent === w.words['/_admin/error/sysadmin_wait'].replace( '%s', '42' ) && w.button().disabled === false );

fire( w.form(), 'submit', { preventDefault : function() {} } );
w.confirms[2].options.onChoose( true );
w.answer( 500, { error : 'the state could not be written' } );
check( 'a failure nobody can cause shows the message of the server with the status, as every panel does', w.line().dataset.state === 'error' && w.line().textContent.indexOf('the state could not be written') !== -1 );

w.panel.showCurrent();
w.answer( 500, { error : 'x' } );
check( 'a failed load is said in the mount, by the panel() of the workbench, and no screen is drawn from it', w.button() === null && full( w.mount ) !== '' );

const missing = w.asked.filter( key => w.words[key] === undefined && key.indexOf('/_admin/sysadmin/') === 0 );
check( 'every word the script asked for is in the text file of this language'+ ( missing.length === 0 ? '' : ' - missing: '+ missing.join() ), missing.length === 0 );

console.log('');
} );

done();
