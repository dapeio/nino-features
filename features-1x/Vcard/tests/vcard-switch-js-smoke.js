/**
 *	Nino
 *	vcard-switch-js-smoke.js	What vcard.js does over a dom stand-in: it shows the switch of the
 *														card and marks the scheme the card wears, a press writes the
 *														scheme on the card or takes it away for auto, the choice is kept
 *														in the browser under the key the Modeswitch uses and read back
 *														on the next page, and a browser that refuses storage gets a
 *														switch that works for the visit. A page without the switch is
 *														left alone.
 *
 *	Usage: node features/Vcard/tests/vcard-switch-js-smoke.js
 *	       vcard-smoke.php runs this where node is on the path.
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

const source = fs.readFileSync( path.join( __dirname, '../assets/vcard.js' ), 'utf8' );

/**
 *	One page: a card that wears a scheme (or none), a switch of three buttons, and a storage
 *
 *	@param		{Object}		options		mode: what the card wears; stored: what the browser holds; refuse: whether storage throws; without: no switch on the page
 *
 *	@return		{Object}
 */
function page( options ) {

	const storage = Object.assign( {}, options.stored || {} );
	const attributes = options.mode === undefined ? {} : { 'data-vcard-mode' : options.mode };
	let listener = null;

	const button = function( scheme ) {
		const el = {
			attributes : { 'data-vcard-scheme' : scheme, 'aria-pressed' : 'false' },
			active : false,
			getAttribute : function( name ) { return el.attributes[name] ?? null },
			setAttribute : function( name, value ) { el.attributes[name] = String( value ) },
			classList : { toggle : function( name, on ) { if( name === 'is-active' ) el.active = on === true } },
			closest : function( selector ) { return selector === '[data-vcard-scheme]' ? el : null },
		};
		return el;
	};

	const buttons = [ button('light'), button('auto'), button('dark') ];

	const group = {
		hidden : true,
		querySelectorAll : function() { return buttons },
		addEventListener : function( name, fn ) { if( name === 'click' ) listener = fn },
	};

	const card = {
		attributes : attributes,
		getAttribute : function( name ) { return attributes[name] ?? null },
		setAttribute : function( name, value ) { attributes[name] = String( value ) },
		removeAttribute : function( name ) { delete attributes[name] },
		querySelector : function() { return options.without === true ? null : group },
	};

	const document = { querySelector : function() { return options.card === false ? null : card } };

	const localStorage = {
		getItem : function( key ) { if( options.refuse === true ) throw new Error('refused'); return storage[key] ?? null },
		setItem : function( key, value ) { if( options.refuse === true ) throw new Error('refused'); storage[key] = value },
	};

	vm.runInNewContext( source, { window : { localStorage : localStorage }, document : document } );

	return {
		attributes : attributes,
		storage : storage,
		group : group,
		pressed : function() { return buttons.filter( function( el ) { return el.active === true && el.attributes['aria-pressed'] === 'true' } ).map( function( el ) { return el.attributes['data-vcard-scheme'] } ).join() },
		press : function( scheme ) { listener( { target : buttons.find( function( el ) { return el.attributes['data-vcard-scheme'] === scheme } ) } ) },
	};
}

console.log( 'vcard.js\n' );

{
	const p = page( {} );
	check( 'the switch is shown, and a card that wears no scheme has auto pressed', p.group.hidden === false && p.pressed() === 'auto' && p.attributes['data-vcard-mode'] === undefined );
	p.press('dark');
	check( 'dark writes the scheme on the card, keeps it under the key of the Modeswitch, and is the one pressed', p.attributes['data-vcard-mode'] === 'dark' && p.storage['nino-mode'] === 'dark' && p.pressed() === 'dark' );
	p.press('light');
	check( 'light replaces it', p.attributes['data-vcard-mode'] === 'light' && p.storage['nino-mode'] === 'light' && p.pressed() === 'light' );
	p.press('auto');
	check( 'auto takes the scheme off the card and stores the word the Modeswitch stores for it', p.attributes['data-vcard-mode'] === undefined && p.storage['nino-mode'] === 'system' && p.pressed() === 'auto' );
}

{
	const p = page( { mode : 'dark' } );
	check( 'where the owner fixed a scheme it is the one pressed until the visitor chooses', p.attributes['data-vcard-mode'] === 'dark' && p.pressed() === 'dark' );
	p.press('auto');
	check( 'and the visitor can take it off', p.attributes['data-vcard-mode'] === undefined && p.pressed() === 'auto' );
}

{
	const p = page( { mode : 'dark', stored : { 'nino-mode' : 'light' } } );
	check( 'a stored choice wins over the owner\'s and is applied at once', p.attributes['data-vcard-mode'] === 'light' && p.pressed() === 'light' );
	const q = page( { mode : 'dark', stored : { 'nino-mode' : 'system' } } );
	check( 'the stored word system is auto', q.attributes['data-vcard-mode'] === undefined && q.pressed() === 'auto' );
	const r = page( { mode : 'dark', stored : { 'nino-mode' : 'nonsense' } } );
	check( 'a stored word that is none of the three is no choice', r.attributes['data-vcard-mode'] === 'dark' && r.pressed() === 'dark' );
}

{
	const p = page( { refuse : true } );
	p.press('dark');
	check( 'a browser that refuses storage still gets a switch that works for the visit', p.attributes['data-vcard-mode'] === 'dark' && p.pressed() === 'dark' );
}

{
	let thrown = false;
	let p = null;
	try {
		p = page( { without : true } );
		page( { card : false } );
	}
	catch( e ) {
		thrown = true;
	}
	check( 'a card without the switch, and a page without the card, are left alone', thrown === false && p.attributes['data-vcard-mode'] === undefined && p.group.hidden === true );
}

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
