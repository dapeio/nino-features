/**
 *	Nino
 *	logos-js-smoke.js	What logos.js does over a dom stand-in: the pause button
 *										of a row is shown where the track of the row runs an
 *										animation and stays hidden where it does not - a row
 *										with another look, or under reduced motion - and where
 *										its word is missing or still an unresolved fill; a
 *										press pauses and starts again, toggles the class the
 *										stylesheet of the band reads and aria-pressed - never the
 *										word on the button - and the word is written as text.
 *
 *										No jsdom, no dependency: a small element stand-in and a
 *										Nino that keeps the callback the script binds to the
 *										ready event, so this runs with nothing but node.
 *										logos-smoke.php runs it where node is on the path.
 *
 *	Usage: node features/Logos/tests/logos-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/logos.js' ), 'utf8' );

/**
 *	An element with the attributes, the classes and the listeners the script touches
 *
 *	@param		{Object}		attributes
 *
 *	@return		{Object}
 */
function element( attributes ) {

	const el = {
		attributes	: Object.assign( {}, attributes || {} ),
		listeners		: {},
		children		: {},
		hidden			: false,
		textContent	: '',
		getAttribute	: function( name ) { return Object.prototype.hasOwnProperty.call( el.attributes, name ) ? el.attributes[name] : null },
		setAttribute	: function( name, value ) { el.attributes[name] = String( value ) },
		querySelector	: function( selector ) { return el.children[selector] || null },
		addEventListener	: function( type, fn ) { ( el.listeners[type] = el.listeners[type] || [] ).push( fn ) },
		click			: function() { ( el.listeners.click || [] ).forEach( function( fn ) { fn( {} ) } ) },
	};

	const classes = [];

	el.classList = {
		add			: function( name ) { if( classes.indexOf( name ) === -1 ) classes.push( name ) },
		remove	: function( name ) { const at = classes.indexOf( name ); if( at !== -1 ) classes.splice( at, 1 ) },
		contains: function( name ) { return classes.indexOf( name ) !== -1 },
	};

	return el;
}

/**
 *	A row as the template writes it: the strip with its words, a track and a hidden button
 *
 *	@param		{string}		animation		What the browser says the track's animation-name is
 *	@param		{Object}		[words]			The data attribute of the word, the default word where left out
 *
 *	@return		{Object}
 */
function row( animation, words ) {

	const strip = element( Object.assign( { 'data-logos-pause' : 'Pause logos' }, words || {} ) );
	const track = element( {} );
	const button = element( { 'aria-pressed' : 'false' } );

	track.animationName = animation;
	button.hidden = true;
	strip.children['.nino-logos-strip-list'] = track;
	strip.children['.nino-logos-strip-pause'] = button;
	strip.button = button;

	return strip;
}

/**
 *	The script run over a page of the rows given, with the ready callback fired
 *
 *	@param		{Array}			rows
 *
 *	@return		void
 */
function run( rows ) {

	let ready = null;

	const sandbox = {
		Nino		: { events : { bindCallback : function( name, fn ) { if( name === 'ready' ) ready = fn } } },
		document	: { querySelectorAll : function( selector ) { return selector === '.nino-logos-strip' ? rows : [] } },
		window	: { getComputedStyle : function( el ) { return { animationName : el.animationName } } },
	};

	vm.runInNewContext( source, sandbox, { filename : 'logos.js' } );

	check( 'the script binds one callback to the ready event and does nothing before it', typeof ready === 'function' && rows.every( function( r ) { return r.button === undefined || r.button.hidden === true } ) );
	ready();
}

const band = row( 'nino-logos-strip-run' );
const plain = row( 'none' );
const still = row( 'none' );
const wordless = row( 'nino-logos-strip-run', { 'data-logos-pause' : '' } );
const unresolved = row( 'nino-logos-strip-run', { 'data-logos-pause' : '[[/feature/logos/action/pause]]' } );
const missing = row( 'nino-logos-strip-run' );
delete missing.attributes['data-logos-pause'];
const naked = element( { 'data-logos-pause' : 'Pause' } );

run( [ band, plain, still, wordless, unresolved, missing, naked ] );

check( 'a row whose track runs an animation shows its button, with the word for pausing, not pressed', band.button.hidden === false && band.button.textContent === 'Pause logos' && band.button.getAttribute( 'aria-pressed' ) === 'false' );
check( 'a row whose track has no animation - another look, or reduced motion - keeps the button hidden and writes nothing in it', plain.button.hidden === true && plain.button.textContent === '' && still.button.hidden === true );
check( 'a row with a word missing or still the fill nobody resolved gets no button, and a strip with no button or track is left alone',
	wordless.button.hidden === true && unresolved.button.hidden === true && missing.button.hidden === true && naked.classList.contains( 'nino-logos-strip--paused' ) === false );

band.button.click();

check( 'a press pauses: the class the stylesheet reads is on the row and the button is pressed - and the word stays: the state is aria-pressed, and a word that changed with it would say it twice',
	band.classList.contains( 'nino-logos-strip--paused' ) === true && band.button.getAttribute( 'aria-pressed' ) === 'true' && band.button.textContent === 'Pause logos' );

band.button.click();

check( 'a second press starts it again: the class is gone, the button is not pressed, and the word is still Pause logos',
	band.classList.contains( 'nino-logos-strip--paused' ) === false && band.button.getAttribute( 'aria-pressed' ) === 'false' && band.button.textContent === 'Pause logos' );
check( 'the other rows were not touched by the presses', plain.classList.contains( 'nino-logos-strip--paused' ) === false && wordless.classList.contains( 'nino-logos-strip--paused' ) === false );

check( 'a word is written as text: the script has no innerHTML and no insertAdjacentHTML', /innerHTML|insertAdjacentHTML|document\.write/.test( source ) === false );

console.log( '\n'+ checks +' checks, '+ failures +' failed' );
process.exit( failures === 0 ? 0 : 1 );
