/**
 *	Nino
 *	toc-js-smoke.js		What toc.js does over a dom stand-in, one pin each: the
 *										list it builds from the headings it takes, the ids it
 *										makes and the one it must not touch, the anchors, the
 *										item it marks while the page is scrolled, and that a
 *										page with no headings keeps a nav that says nothing.
 *
 *										No jsdom, no dependency: the same element stand-in the
 *										other feature tests build, so this runs with nothing but
 *										node - and the kernel's own Nino.js, which the script is
 *										written against and which runs here as it is on disk:
 *										from the checkout three levels up, or the one NINO_ROOT
 *										names. The ready callback, the throttle of the scroll
 *										and the resize and the slug of an id are its.
 *
 *	Usage: node features/Toc/tests/toc-js-smoke.js
 *	       NINO_ROOT=../nino node features/Toc/tests/toc-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/toc.js' ), 'utf8' );

// The script of the kernel that toc.js is written against, read from the
// checkout this runs against: three levels up when the feature sits in a
// project's features/, else the one NINO_ROOT names
const kernel = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const nino = new vm.Script( fs.readFileSync( path.join( kernel, '_nino/Nino.js' ), 'utf8' ), { filename : 'Nino.js' } );

/** Every descendant of $root, depth first */
function descendants( root ) {
	let all = [];
	for( const child of root.children ) {
		all.push( child );
		all = all.concat( descendants( child ) );
	}
	return all;
}

/**
 *	One element, with just enough of the interface toc.js reaches for
 */
function element( tag, attributes ) {

	const el = {
		tagName			: tag.toUpperCase(),
		nodeType		: 1,
		attributes	: Object.assign( {}, attributes || {} ),
		children		: [],
		parent			: null,
		hidden			: false,
		top					: 0,
		id					: ( attributes || {} ).id || '',
		_text				: ( attributes || {} ).text || '',
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		getBoundingClientRect : function() { return { top : this.top } },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		closest					: function( selector ) {
			const name = selector.replace( /^\./, '' );
			let node = this;
			while( node !== null ) {
				if( node.matchesClass !== undefined && node.matchesClass( name ) ) return node;
				node = node.parent;
			}
			return null;
		},
		querySelector		: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) {
			const wanted = selector.split( ',' ).map( function( s ) { return s.trim() } );
			return descendants( this ).filter( function( node ) {
				return wanted.some( function( s ) {
					return s.charAt( 0 ) === '.' ? node.matchesClass( s.slice( 1 ) ) : node.tagName === s.toUpperCase();
				} );
			} );
		},
	};

	el.classList = {
		add				: function( name ) { el.attributes['class'] = ( ( el.attributes['class'] || '' ) + ' ' + name ).trim() },
		contains	: function( name ) { return el.matchesClass( name ) },
	};

	Object.defineProperty( el, 'textContent', {
		get : function() { return el._text + el.children.map( function( c ) { return c.textContent } ).join('') },
		set : function( value ) { el._text = String( value ); el.children = [] },
	} );

	/*	The dom keeps a heading's own words in a text node among its children,
		which is what toc.js walks to read them without the anchor it appends.
		Here they are in _text, so childNodes hands them out as one text node
		in front of the elements - where they are, since everything the script
		appends goes to the end	*/
	Object.defineProperty( el, 'childNodes', {
		get : function() {
			const own = el._text === '' ? [] : [ { nodeType : 3, textContent : el._text } ];
			return own.concat( el.children );
		},
	} );

	Object.defineProperty( el, 'className', {
		get : function() { return el.attributes['class'] || '' },
		set : function( value ) { el.attributes['class'] = String( value ) },
	} );

	Object.defineProperty( el, 'href', {
		get : function() { return el.attributes['href'] || '' },
		set : function( value ) { el.attributes['href'] = String( value ) },
	} );

	return el;
}

/**
 *	A page: one [toc] nav and the headings it is meant to find, the kernel's
 *	Nino.js and then the script loaded over it. A document that is there already
 *	is ready when this returns; one that is not is ready when its
 *	DOMContentLoaded listeners are called
 *
 *	@param		{Object}	options		{ headings, levels, anchors, within, second, readyState }
 *										headings: [ tag, text, class, id, top, inside the toc-scope wrapper ]
 */
function page( options ) {

	options = options || {};

	const body = element( 'body', {} );

	function nav( index ) {
		const el = element( 'nav', {
			'class'							: 'toc',
			'data-toc-levels'		: options.levels !== undefined ? options.levels : '2,3',
			'data-toc-within'		: options.within !== undefined ? options.within : '',
			'data-toc-anchors'	: options.anchors !== undefined ? options.anchors : '1',
			'data-toc-label'		: 'Link zu diesem Abschnitt',
		} );
		el.hidden = true;
		const title = element( 'h2', { 'class' : 'toc-title', id : index === 0 ? 'toc-title' : 'toc-title', text : 'Auf dieser Seite' } );
		el.appendChild( title );
		el.appendChild( element( 'ol', { 'class' : 'toc-list' } ) );
		body.appendChild( el );
		return el;
	}

	const first = nav( 0 );
	const other = options.second === true ? nav( 1 ) : null;

	/*	A heading whose sixth entry is true stands inside one wrapper with the
		class toc-scope, opened where the first of them is - what a within=
		selector is pointed at	*/
	const made = [];
	let scope = null;
	for( const spec of ( options.headings || [ [ 'h2', 'Erstens' ], [ 'h3', 'Genauer' ], [ 'h2', 'Zweitens' ] ] ) ) {
		const el = element( spec[0], { text : spec[1], 'class' : spec[2] || '', id : spec[3] || '' } );
		el.top = spec[4] !== undefined ? spec[4] : made.length * 400;
		if( spec[5] === true ) {
			if( scope === null )
				scope = body.appendChild( element( 'div', { 'class' : 'toc-scope' } ) );
			scope.appendChild( el );
		}
		else
			body.appendChild( el );
		made.push( el );
	}

	const listeners = {};
	const frames = [];
	const timers = [];

	const dc = {
		readyState			: options.readyState || 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		getElementById	: function( id ) { return descendants( body ).filter( function( n ) { return n.id === id } )[0] || null },
		/*	The stand-in reads classes and tag names only; a bracket it refuses
			the way a browser refuses a selector it cannot parse - by throwing.
			Except the one Nino.js asks for when it is loaded, the carrier of the
			directory the project is served from, which this page has none of	*/
		querySelector		: function( selector ) {
			if( selector === '[data-dir]' )
				return null;
			if( selector.indexOf( '[' ) !== -1 )
				throw new Error( 'not a valid selector: ' + selector );
			return body.querySelectorAll( selector )[0] || null;
		},
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	// The window is the global of the page, as it is in a browser: Nino.js puts Nino on it
	const wn = {
		console : console,
		document : dc,
		navigator : { userAgent : 'node' },
		innerHeight : 900,
		Event : function( type ) { this.type = type },
		dispatchEvent : function( event ) { ( listeners[event.type] || [] ).forEach( function( fn ) { fn( event ) } ) },
		addEventListener : function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
		requestAnimationFrame : function( fn ) { frames.push( fn ); return frames.length },
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
	};
	wn.window = wn;

	const context = vm.createContext( wn );

	nino.runInContext( context );
	vm.runInContext( source, context, { filename : 'toc.js' } );

	// Run what was asked of requestAnimationFrame() and of setTimeout(), and what that
	// asked for in turn. A callback of Nino.js that throws is thrown again from a timer,
	// which this runs too - so the error ends the run instead of passing unseen
	const runFrames = function() {
		while( frames.length > 0 )
			frames.splice( 0 ).forEach( function( fn ) { fn() } );
	};
	const settle = function() {
		while( timers.length > 0 )
			timers.splice( 0 ).forEach( function( fn ) { fn() } );
	};
	const fire = function( type ) {
		( listeners[type] || [] ).forEach( function( fn ) { fn( { type : type } ) } );
	};

	// A document that is there already: Nino.js calls its ready callbacks back a
	// millisecond after it was loaded, and the resize and the scroll it dispatches
	// first take their frame
	if( dc.readyState !== 'loading' ) {
		settle();
		runFrames();
	}

	return {
		body : body, nav : first, other : other, headings : made, listeners : listeners,
		items : function( which ) {
			return ( which || first ).querySelectorAll( '.toc-link' );
		},
		texts : function( which ) { return this.items( which ).map( function( a ) { return a.textContent } ) },
		hrefs : function( which ) { return this.items( which ).map( function( a ) { return a.href } ) },
		current : function() {
			return this.items().filter( function( a ) { return a.getAttribute('aria-current') === 'true' } ).map( function( a ) { return a.textContent } );
		},
		scrollTo : function( tops ) {
			for( let i = 0; i < tops.length; i++ ) made[i].top = tops[i];
			fire( 'scroll' );
			runFrames();
		},
		resizeTo : function( seen ) {
			wn.innerHeight = seen;
			fire( 'resize' );
			runFrames();
		},
	};
}


// --- The list ------------------------------------------------------------------

const plain = page( {} );

check( 'the nav comes out of hiding once there is something in it', plain.nav.hidden === false );
check( 'every heading is in the list, in the order the page has them, each linking to its heading and carrying its level',
	plain.texts().join(' | ') === 'Erstens | Genauer | Zweitens' && plain.hrefs().join(' ') === '#erstens #genauer #zweitens'
	&& plain.items().map( function( a ) { return a.parent.className } ).join(' ') === 'toc-item toc-item--2 toc-item toc-item--3 toc-item toc-item--2' );
check( 'the list never lists its own title', plain.texts().indexOf( 'Auf dieser Seite' ) === -1 );

// The words of a heading are text in the list, and the id made of them has no character of markup in it
const hostile = page( { headings : [ [ 'h2', '<img src=x onerror=alert(1)> & "quote"' ] ] } );
check( 'the words of a heading stay text: set as text, never as markup, and the id made of them is a slug',
	hostile.items()[0].textContent === '<img src=x onerror=alert(1)> & "quote"' && hostile.items()[0].children.length === 0 && /^[a-z0-9-]+$/.test( hostile.headings[0].id ) === true
	&& /^#[a-z0-9-]+$/.test( hostile.items()[0].href ) === true );


// --- The ids -------------------------------------------------------------------

const kept = page( { headings : [ [ 'h2', 'Erstens', '', 'schon-da' ], [ 'h2', 'Kontakt' ], [ 'h2', 'Kontakt' ], [ 'h2', '???' ] ] } );
check( 'a heading with no id is given one made from its words, one that has an id keeps it, equal words get a number, no word gets the feature\'s own',
	plain.headings[0].id === 'erstens' && kept.headings[0].id === 'schon-da' && kept.hrefs().join(' ') === '#schon-da #kontakt #kontakt-2 #abschnitt'
	&& kept.headings[1].id === 'kontakt' && kept.headings[2].id === 'kontakt-2' );

const elsewhere = page( { headings : [ [ 'h2', 'Kontakt', 'toc-skip', 'kontakt' ], [ 'h2', 'Kontakt' ] ] } );
check( 'an id the page already carries outside the list is not handed out a second time', elsewhere.headings[1].id === 'kontakt-2' && elsewhere.hrefs().join('') === '#kontakt-2' );


// --- What is left out, and where it looks ---------------------------------------

const skipped = page( { headings : [ [ 'h2', 'Erstens' ], [ 'h2', 'Nicht ins Verzeichnis', 'toc-skip' ], [ 'h3', 'Zweitens' ] ], levels : '2' } );
check( 'a heading marked toc-skip is left out and gets no anchor, and a level the list was not asked for is left out',
	skipped.texts().join(' | ') === 'Erstens' && skipped.headings[1].querySelectorAll( '.toc-anchor' ).length === 0 );

const scoped = [ [ 'h2', 'Vorher' ], [ 'h2', 'Drinnen', '', '', undefined, true ], [ 'h3', 'Genauer', '', '', undefined, true ], [ 'h2', 'Danach' ] ];
check( 'a list pointed at part of the page takes the headings in there, and one pointed at no selector takes the whole page',
	page( { headings : scoped, within : '.toc-scope' } ).texts().join(' | ') === 'Drinnen | Genauer' && page( { headings : scoped, within : '[' } ).texts().length === 4 );

const empty = page( { headings : [] } );
check( 'a page with no headings keeps a nav that says nothing rather than an empty list', empty.nav.hidden === true && empty.items().length === 0 );


// --- The anchors ---------------------------------------------------------------

check( 'every listed heading gets a link to itself that names itself for somebody who cannot see it, and none where the setting is off',
	plain.headings[0].querySelectorAll( '.toc-anchor' ).length === 1 && plain.headings[0].querySelector( '.toc-anchor' ).href === '#erstens'
	&& plain.headings[0].querySelector( '.toc-anchor' ).getAttribute( 'aria-label' ) === 'Link zu diesem Abschnitt'
	&& page( { anchors : '0' } ).headings[0].querySelectorAll( '.toc-anchor' ).length === 0 );


// --- The section being read ----------------------------------------------------

check( 'the first section is current before anything has been scrolled past', plain.current().join('') === 'Erstens' );

plain.scrollTo( [ -800, -100, 500 ] );
check( 'the last heading above the mark is the one that is current, and only ever one', plain.current().join('') === 'Genauer' && plain.current().length === 1 );

const resized = page( {} );
resized.scrollTo( [ -100, 400, 800 ] );
resized.resizeTo( 1500 );
check( 'a resize moves the mark, which is a third down the window', resized.current().join('') === 'Genauer' );


// --- Two lists, and waiting for the dom ----------------------------------------

const twice = page( { second : true } );
check( 'a second list on the page gets its own title id, is filled, and reads the words of the headings and not the anchors of the first',
	twice.other.querySelector( '.toc-title' ).id === 'toc-title-2' && twice.other.getAttribute( 'aria-labelledby' ) === 'toc-title-2'
	&& twice.texts( twice.other ).join(' | ') === 'Erstens | Genauer | Zweitens' );

const late = page( { readyState : 'loading' } );
const waited = late.nav.hidden === true && ( late.listeners['DOMContentLoaded'] || [] ).length === 1;
( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( 'a script that ran before the dom was there waits for it, and builds the list when it arrives', waited === true && late.nav.hidden === false && late.texts().length === 3 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
