/**
 *	Nino
 *	embed-js-smoke.js		What embed.js does over a dom stand-in: that there is no
 *											iframe on the page until one is released, which two
 *											things release one, what the frame is then given, that
 *											releasing the same embed twice builds one frame, that a
 *											press can carry the same provider's other embeds where
 *											the site asked for it, and that an address which is not
 *											https is refused whatever the markup says.
 *
 *											No jsdom, no dependency: the same element stand-in the
 *											other feature tests build, so this runs with nothing
 *											but node.
 *
 *	Usage: node features/Embed/tests/embed-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/embed.js' ), 'utf8' );

/**
 *	One element, with just enough of the interface embed.js reaches for
 */
function element( tag, attributes ) {

	const el = {
		tagName				: tag,
		attributes		: Object.assign( {}, attributes || {} ),
		dataset				: {},
		classes				: {},
		hidden				: true,
		children			: [],
		parentNode		: null,
		listeners			: {},
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		hasAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		appendChild			: function( child ) { child.parentNode = this; this.children.push( child ); return child },
		removeChild			: function( child ) {
			this.children = this.children.filter( function( c ) { return c !== child } );
			child.parentNode = null;
			return child;
		},
		querySelector		: function( selector ) {
			return this.children.filter( function( c ) { return c.matchesClass( selector.replace( /^\./, '' ) ) } )[0] || null;
		},
		matchesClass		: function( name ) {
			return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1;
		},
	};

	el.classList = { add : function( name ) { el.classes[name] = true } };

	return el;
}

/**
 *	One embed as the shortcode writes it: the container carrying the address,
 *	and the hidden button inside it
 *
 *	@param		{Object}	options		{ src, host, consent, remember, title }
 */
function embed( options ) {

	const box = element( 'div', {
		'class'							: 'nino-embed nino-embed--16-9',
		'data-embed-src'		: options.src !== undefined ? options.src : 'https://www.youtube-nocookie.com/embed/abc123?rel=0',
		'data-embed-title'	: options.title !== undefined ? options.title : 'Ein Video',
		'data-embed-consent': options.consent !== undefined ? options.consent : 'external',
		'data-embed-host'		: options.host !== undefined ? options.host : 'youtube-nocookie.com',
	} );

	if( options.remember === true )
		box.attributes['data-embed-remember'] = '1';

	box.appendChild( element( 'button', { 'class' : 'nino-video-poster nino-embed-open' } ) );

	return box;
}

/**
 *	A page with the given embeds on it and the script loaded over it
 *
 *	@param		{Array}		boxes
 *	@param		{Object}	options		{ consent, readyState }
 */
function page( boxes, options ) {

	options = options || {};

	const root = element( 'html', typeof options.consent === 'string' ? { 'data-consent' : options.consent } : {} );
	const listeners = {};
	const fired = [];

	const dc = {
		documentElement	: root,
		readyState			: options.readyState || 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		dispatchEvent		: function( ev ) {
			fired.push( ev );
			( listeners[ev.type] || [] ).forEach( function( fn ) { fn( ev ) } );
		},
		querySelectorAll	: function( selector ) {

			if( selector.indexOf( '.nino-embed[data-embed-src]' ) !== 0 )
				return [];

			let found = boxes.slice();

			if( selector.indexOf( '[data-embed-remember]' ) !== -1 )
				found = found.filter( function( b ) { return b.hasAttribute( 'data-embed-remember' ) } );

			const host = /\[data-embed-host="([^"]*)"\]/.exec( selector );
			if( host !== null )
				found = found.filter( function( b ) { return b.getAttribute( 'data-embed-host' ) === host[1] } );

			return found;
		},
	};

	function CustomEvent( type, init ) {
		this.type = type;
		this.detail = ( init || {} ).detail;
	}

	const sandbox = { console : console, document : dc, CustomEvent : CustomEvent };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'embed.js' } );

	return {
		root : root, listeners : listeners, fired : fired,
		frames : function( box ) { return box.children.filter( function( c ) { return c.tagName === 'iframe' } ) },
		button : function( box ) { return box.children.filter( function( c ) { return c.matchesClass( 'nino-embed-open' ) } )[0] || null },
		press	: function( box ) {
			const button = box.children.filter( function( c ) { return c.matchesClass( 'nino-embed-open' ) } )[0];
			( ( button && button.listeners['click'] ) || [] ).forEach( function( fn ) { fn( {} ) } );
		},
		consent : function( allowed ) {
			root.attributes['data-consent'] = allowed.join( ',' );
			dc.dispatchEvent( new CustomEvent( 'nino:consent', { detail : { allowed : allowed } } ) );
		},
	};
}


// --- Before anything is released ---------------------------------------------

const one = embed( {} );
const quiet = page( [ one ] );

check( 'nothing is fetched until something releases it - there is no iframe on the page', quiet.frames( one ).length === 0 );
check( 'the surface is shown once the script has it, having been written hidden', quiet.button( one ).hidden === false );
check( '...and the address is still only a data attribute', one.getAttribute('data-embed-src').indexOf('https://') === 0 && one.children.length === 1 );


// --- The press ---------------------------------------------------------------

quiet.press( one );

check( 'a press builds exactly one frame', quiet.frames( one ).length === 1 );

const frame = quiet.frames( one )[0];

check( 'the frame gets the address the markup carried', frame.getAttribute('src') === 'https://www.youtube-nocookie.com/embed/abc123?rel=0' );
check( '...and the name the shortcode gave it, so it is not announced as "iframe"', frame.getAttribute('title') === 'Ein Video' );
check( '...and may play fullscreen, which is the whole point of pressing', ( frame.getAttribute('allow') || '' ).indexOf('fullscreen') !== -1 );
check( '...through allow alone - a browser objects to allowfullscreen beside it', frame.getAttribute('allowfullscreen') === null );
check( 'the provider is told which site this is, not which page', frame.getAttribute('referrerpolicy') === 'strict-origin-when-cross-origin' );
check( 'the surface is removed rather than hidden - a hidden button is still in the tab order', quiet.button( one ) === null );
check( 'the box says it is loaded, so the stylesheet can give it its shape', one.classes['nino-is-loaded'] === true );
check( 'and the page is told, with the host it now talks to', quiet.fired.some( function( ev ) {
	return ev.type === 'nino:embed' && ev.detail.host === 'youtube-nocookie.com' && ev.detail.embed === one;
} ) );

quiet.press( one );
check( 'pressing again changes nothing - one embed is one frame', quiet.frames( one ).length === 1 );


// --- Consent instead of a press ----------------------------------------------

const allowed = embed( {} );
const already = page( [ allowed ], { consent : 'necessary,external' } );

check( 'a visitor who already allowed the category is not asked to press', already.frames( allowed ).length === 1 );

const other = embed( {} );
const declined = page( [ other ], { consent : 'necessary' } );

check( 'one who allowed something else is', declined.frames( other ).length === 0 );

declined.consent( [ 'necessary', 'external' ] );
check( '...and changing their mind releases it without a reload', declined.frames( other ).length === 1 );

const none = embed( { consent : '' } );
const never = page( [ none ], { consent : 'necessary,external' } );

check( 'an embed with no category can only ever be pressed, whatever is allowed', never.frames( none ).length === 0 );
never.press( none );
check( '...and pressing it still works', never.frames( none ).length === 1 );

const nowhere = embed( {} );
const bare = page( [ nowhere ] );
check( 'a site without Consent has no allowed list, so every embed waits for a press', bare.frames( nowhere ).length === 0 );


// --- One press for the same provider -----------------------------------------

const first	= embed( { remember : true } );
const second = embed( { remember : true } );
const third	= embed( { remember : true, host : 'player.vimeo.com', src : 'https://player.vimeo.com/video/1?dnt=1' } );
const shared = page( [ first, second, third ], {} );

shared.press( first );

check( 'where the site asked for it, one press carries the same provider\'s other embeds', shared.frames( second ).length === 1 );
check( '...and leaves another provider\'s alone', shared.frames( third ).length === 0 );

const alone	= embed( {} );
const beside = embed( {} );
const strict = page( [ alone, beside ], {} );

strict.press( alone );
check( 'without that setting a press releases the one pressed and nothing else', strict.frames( alone ).length === 1 && strict.frames( beside ).length === 0 );


// --- An address that is not one ----------------------------------------------

const plain = embed( { src : 'http://www.youtube-nocookie.com/embed/abc123' } );
const refused = page( [ plain ], { consent : 'external' } );

check( 'an http address is refused however the embed is released', refused.frames( plain ).length === 0 );
refused.press( plain );
check( '...by the press as well as by the consent', refused.frames( plain ).length === 0 );

const script = embed( { src : 'javascript:alert(1)' } );
const nope = page( [ script ], {} );
nope.press( script );
check( 'and so is anything that is not an address at all', nope.frames( script ).length === 0 );


// --- Waiting for the dom -----------------------------------------------------

const late = embed( {} );
const loading = page( [ late ], { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	loading.button( late ).hidden === true && ( loading.listeners['DOMContentLoaded'] || [] ).length === 1 );

( loading.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and picks the surfaces up when it arrives', loading.button( late ).hidden === false );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
