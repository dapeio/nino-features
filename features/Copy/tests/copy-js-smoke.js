/**
 *	Nino
 *	copy-js-smoke.js	What copy.js does over a dom stand-in: that the button comes
 *										out of hiding and names what it copies, what is put into
 *										the clipboard and what is not, that the older way is
 *										tried when the clipboard API is missing or refuses, that
 *										the word on the button says what happened and goes back
 *										afterwards, and that a copy box with no button is left
 *										alone.
 *
 *										No jsdom, no dependency: the same element stand-in the
 *										other feature tests build, so this runs with nothing but
 *										node - and the kernel's own Nino.js, which does the
 *										copying (Nino.clipboard()) and starts the script
 *										(Nino.events): the stand-ins here are the browser's, the
 *										clipboard API and the copy command. NINO_ROOT names the
 *										checkout, else it is three levels up, where a project
 *										keeps it.
 *
 *	Usage: node features/Copy/tests/copy-js-smoke.js
 *	       NINO_ROOT=../nino node features/Copy/tests/copy-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, setImmediate */

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

const source = fs.readFileSync( path.join( __dirname, '../assets/copy.js' ), 'utf8' );
const checkout = process.env.NINO_ROOT || path.join( __dirname, '../../..' );
const kernel = fs.readFileSync( path.join( checkout, '_nino/Nino.js' ), 'utf8' );

function element( tag, attributes ) {

	const el = {
		tagName			: tag.toUpperCase(),
		attributes	: Object.assign( {}, attributes || {} ),
		dataset			: {},
		children		: [],
		listeners		: {},
		hidden			: false,
		style				: {},
		value				: '',
		textContent	: ( attributes || {} ).text || '',
		selected		: false,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		removeChild			: function( child ) { this.children = this.children.filter( function( c ) { return c !== child } ); return child },
		remove					: function() { if( this.parent ) this.parent.removeChild( this ) },
		select					: function() { this.selected = true },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) {
			const name = selector.replace( /^\./, '' );
			let found = null;
			( function walk( node ) {
				for( const child of node.children ) {
					if( found === null && child.matchesClass( name ) ) found = child;
					walk( child );
				}
			} )( this );
			return found;
		},
	};

	el.classList = {
		add				: function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() },
		remove		: function( name ) { el.attributes['class'] = ( el.attributes['class'] || '' ).split( /\s+/ ).filter( function( c ) { return c !== name && c !== '' } ).join(' ') },
		contains	: function( name ) { return el.matchesClass( name ) },
	};

	return el;
}

/**
 *	One copy box as its template draws it, the script loaded over it
 *
 *	@param		{Object}	options		{ shown, value, named, clipboard, legacy, readyState, hold }
 */
function page( options ) {

	options = options || {};

	const box = element( 'span', { 'class' : 'copy' } );
	const text = element( 'span', { 'class' : 'copy-text', text : options.shown !== undefined ? options.shown : 'DE02 1203' } );

	const buttonAttributes = {
		'class'						: 'copy-button',
		'data-copy-do'		: 'Kopieren',
		'data-copy-done'	: 'Kopiert',
		'data-copy-failed': 'Strg+C drücken',
	};
	if( options.value !== undefined ) buttonAttributes['data-copy-value'] = options.value;
	if( options.named !== undefined ) buttonAttributes['data-copy-named'] = options.named;

	const button = element( 'button', buttonAttributes );
	button.hidden = true;

	const word = element( 'span', { 'class' : 'copy-word', text : 'Kopieren' } );
	button.appendChild( word );

	box.appendChild( text );
	if( options.withButton !== false ) box.appendChild( button );

	const body = element( 'body', {} );
	const listeners = {};
	const written = [];
	const commands = [];
	let timer = null;

	const dc = {
		readyState			: options.readyState || 'complete',
		body						: body,
		createElement		: function( tag ) { return element( tag, {} ) },
		querySelector		: function() { return null },
		querySelectorAll	: function( selector ) { return selector === '.copy' ? [ box ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		execCommand			: function( name ) {
			commands.push( name );
			if( options.legacy === false ) return false;
			written.push( body.children.length > 0 ? body.children[body.children.length - 1].value : '' );
			return true;
		},
	};

	const navigator = { userAgent : 'node' };
	const started = [];

	if( options.clipboard !== false )
		navigator.clipboard = {
			writeText : function( value ) {
				if( options.clipboard === 'throws' )
					throw new Error('at once');
				if( options.clipboard === 'refuses' )
					return Promise.reject( new Error('nope') );
				written.push( value );
				return Promise.resolve();
			},
		};

	// What Nino.js needs to load and to start its callbacks: the events of the
	// window and a timer it asks for a page that is ready already - kept apart
	// from the one the script asks for to take the word off the button again
	const sandbox = {
		console : console, document : dc, navigator : navigator, Promise : Promise,
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		setTimeout : function( fn, delay ) {
			if( delay <= 1 ) {
				started.push( fn );
				return started.length;
			}
			timer = fn;
			return 7;
		},
		clearTimeout : function() { timer = null },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( kernel, context, { filename : 'Nino.js' } );
	vm.runInContext( source, context, { filename : 'copy.js' } );

	// A page that is ready when the scripts run starts the callbacks a moment
	// later, from the timer Nino.js asked for; one still loading starts them
	// when the document says so. What a callback throws is thrown again from a
	// timer, and that one is run here too - a script that fails fails the run
	const start = function() {
		while( started.length > 0 )
			started.shift()();
	};

	if( dc.readyState !== 'loading' && options.hold !== true )
		start();

	return {
		box : box, button : button, word : word, body : body, listeners : listeners, start : start,
		written : written, commands : commands,
		press : function() { ( button.listeners['click'] || [] ).forEach( function( fn ) { fn( {} ) } ) },
		settle : function() { return new Promise( function( r ) { setImmediate( r ) } ) },
		expire : function() { if( timer !== null ) { const fn = timer; timer = null; fn() } },
		said : function() { return word.textContent },
	};
}

( async function() {

// --- Taking a copy box over ------------------------------------------------------

const plain = page( {} );

check( 'the button comes out of hiding, because now it can do something', plain.button.hidden === false );
const named = page( { named : 'IBAN' } );
check( 'a button that was told what it copies says both what it does and what it copies, one that was not keeps its word alone',
	named.button.getAttribute('aria-label') === 'Kopieren: IBAN' && plain.button.getAttribute('aria-label') === null );


// --- What gets copied ----------------------------------------------------------

plain.press();
await plain.settle();

check( 'a press copies what is shown', plain.written.join('') === 'DE02 1203' );

const grouped = page( { shown : 'DE02 1203 0000', value : 'DE0212030000' } );
grouped.press();
await grouped.settle();
check( '...or what the call said instead, so a number can be grouped on screen and plain in the clipboard',
	grouped.written.join('') === 'DE0212030000' );

const nothing = page( { shown : '' } );
nothing.press();
await nothing.settle();
check( 'and nothing at all where there is nothing to copy', nothing.written.length === 0 );


// --- Saying what happened ------------------------------------------------------

check( 'the word on the button says it worked, and the button is marked too', plain.said() === 'Kopiert' && plain.button.classList.contains('copy-is-done') === true );

plain.expire();
check( 'after a moment it is a copy button again', plain.said() === 'Kopieren' && plain.button.classList.contains('copy-is-done') === false );


// --- When the clipboard API is not there, or refuses ---------------------------

const old = page( { clipboard : false } );
old.press();
await old.settle();

check( 'a browser with no clipboard API - which is every site served over http - still copies, and takes the field it needed off the page again',
	old.commands.join('') === 'copy' && old.written.join('') === 'DE02 1203' && old.said() === 'Kopiert' && old.body.children.length === 0 );

const hopeless = page( { clipboard : false, legacy : false } );
hopeless.press();
await hopeless.settle();
check( 'and where neither works the button says what to do instead',
	hopeless.said() === 'Strg+C drücken' && hopeless.button.classList.contains('copy-is-failed') === true );

// --- A copy box that is not one --------------------------------------------------

const bare = page( { withButton : false } );
check( 'a copy box with no button in it is left exactly as it was', bare.button.hidden === true );


// --- Starting with the kernel --------------------------------------------------

const held = page( { hold : true } );

check( 'the buttons come out of hiding when Nino.js calls its ready callbacks, and not when the script is loaded',
	held.button.hidden === true );

held.start();
check( '...and then they do', held.button.hidden === false );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.button.hidden === true );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
// What a callback throws is thrown again from a timer, and that one is run here too
late.start();
check( '...and picks the buttons up when it arrives', late.button.hidden === false );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );

} )();
