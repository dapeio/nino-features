/**
 *	Nino
 *	copy-js-smoke.js	What copy.js does over a dom stand-in: that the button comes
 *										out of hiding and names what it copies, what is put into
 *										the clipboard and what is not, that the older way is
 *										tried when the clipboard API is missing or refuses, that
 *										the word on the button says what happened and goes back
 *										afterwards, and that a [copy] with no button is left
 *										alone.
 *
 *										No jsdom, no dependency: the same element stand-in the
 *										other feature tests build, so this runs with nothing but
 *										node.
 *
 *	Usage: node features/Copy/tests/copy-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/copy.js' ), 'utf8' );

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
		appendChild			: function( child ) { this.children.push( child ); return child },
		removeChild			: function( child ) { this.children = this.children.filter( function( c ) { return c !== child } ); return child },
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
 *	One [copy] as the shortcode writes it, the script loaded over it
 *
 *	@param		{Object}	options		{ shown, value, named, clipboard, legacy, readyState }
 */
function page( options ) {

	options = options || {};

	const box = element( 'span', { 'class' : 'nino-copy' } );
	const text = element( 'span', { 'class' : 'nino-copy-text', text : options.shown !== undefined ? options.shown : 'DE02 1203' } );

	const buttonAttributes = {
		'class'						: 'nino-copy-btn',
		'data-copy-do'		: 'Kopieren',
		'data-copy-done'	: 'Kopiert',
		'data-copy-failed': 'Strg+C drücken',
	};
	if( options.value !== undefined ) buttonAttributes['data-copy-value'] = options.value;
	if( options.named !== undefined ) buttonAttributes['data-copy-named'] = options.named;

	const button = element( 'button', buttonAttributes );
	button.hidden = true;

	const word = element( 'span', { 'class' : 'nino-copy-word', text : 'Kopieren' } );
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
		querySelectorAll	: function( selector ) { return selector === '.nino-copy' ? [ box ] : [] },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		execCommand			: function( name ) {
			commands.push( name );
			if( options.legacy === false ) return false;
			written.push( body.children.length > 0 ? body.children[body.children.length - 1].value : '' );
			return true;
		},
	};

	const navigator = {};

	if( options.clipboard !== false )
		navigator.clipboard = {
			writeText : function( value ) {
				if( options.clipboard === 'refuses' )
					return Promise.reject( new Error('nope') );
				written.push( value );
				return Promise.resolve();
			},
		};

	const sandbox = {
		console : console, document : dc, navigator : navigator, Promise : Promise,
		setTimeout : function( fn ) { timer = fn; return 7 },
		clearTimeout : function() { timer = null },
	};
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'copy.js' } );

	return {
		box : box, button : button, word : word, body : body, listeners : listeners,
		written : written, commands : commands,
		press : function() { ( button.listeners['click'] || [] ).forEach( function( fn ) { fn( {} ) } ) },
		settle : function() { return new Promise( function( r ) { setImmediate( r ) } ) },
		expire : function() { if( timer !== null ) { const fn = timer; timer = null; fn() } },
		said : function() { return word.textContent },
	};
}

( async function() {

// --- Taking a [copy] over ------------------------------------------------------

const plain = page( {} );

check( 'the button comes out of hiding, because now it can do something', plain.button.hidden === false );
check( 'a button that was not told what it copies is left with the word on it alone', plain.button.getAttribute('aria-label') === null );

const named = page( { named : 'IBAN' } );
check( '...and one that was says both: what it does and what it copies', named.button.getAttribute('aria-label') === 'Kopieren: IBAN' );


// --- What gets copied ----------------------------------------------------------

plain.press();
await plain.settle();

check( 'a press copies what is shown', plain.written.join('') === 'DE02 1203' );

const grouped = page( { shown : 'DE02 1203 0000', value : 'DE021203 0000'.replace( / /g, '' ) } );
grouped.press();
await grouped.settle();
check( '...or what the shortcode said instead, so a number can be grouped on screen and plain in the clipboard',
	grouped.written.join('') === 'DE0212030000' );

const nothing = page( { shown : '' } );
nothing.press();
await nothing.settle();
check( 'and nothing at all where there is nothing to copy', nothing.written.length === 0 );


// --- Saying what happened ------------------------------------------------------

check( 'the word on the button says it worked, not only its colour', plain.said() === 'Kopiert' );
check( '...and the button is marked for whoever can see it too', plain.button.classList.contains('nino-is-done') === true );

plain.expire();
check( 'after a moment it is a copy button again', plain.said() === 'Kopieren' && plain.button.classList.contains('nino-is-done') === false );


// --- When the clipboard API is not there, or refuses ---------------------------

const old = page( { clipboard : false } );
old.press();
await old.settle();

check( 'a browser with no clipboard API - which is every site served over http - still copies',
	old.commands.join('') === 'copy' && old.written.join('') === 'DE02 1203' && old.said() === 'Kopiert' );
check( '...and the field that way needs is taken off the page again', old.body.children.length === 0 );

const refused = page( { clipboard : 'refuses' } );
refused.press();
await refused.settle();
check( 'an API that refuses falls back to the older way rather than reporting failure straight away',
	refused.commands.join('') === 'copy' && refused.said() === 'Kopiert' );

const hopeless = page( { clipboard : false, legacy : false } );
hopeless.press();
await hopeless.settle();
check( 'and where neither works the button says what to do instead',
	hopeless.said() === 'Strg+C drücken' && hopeless.button.classList.contains('nino-is-failed') === true );


// --- A [copy] that is not one --------------------------------------------------

const bare = page( { withButton : false } );
check( 'a [copy] with no button in it is left exactly as it was', bare.button.hidden === true );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.button.hidden === true && ( late.listeners['DOMContentLoaded'] || [] ).length === 1 );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and picks the buttons up when it arrives', late.button.hidden === false );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );

} )();
