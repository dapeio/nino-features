/**
 *	Nino
 *	toc-js-smoke.js		What toc.js does over a dom stand-in: which headings it
 *										takes and which it leaves, the ids it makes and the one
 *										it must not touch, the list it builds, the anchors it
 *										puts on the headings when the setting asks for it, which
 *										item it marks while the page is scrolled, and that a
 *										page with no headings keeps a nav that says nothing.
 *
 *										No jsdom, no dependency: the same element stand-in the
 *										other feature tests build, so this runs with nothing but
 *										node.
 *
 *	Usage: node features/Toc/tests/toc-js-smoke.js
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
 *	A page: one [toc] nav and the headings it is meant to find
 *
 *	@param		{Object}	options		{ headings, levels, anchors, within, second, readyState }
 */
function page( options ) {

	options = options || {};

	const body = element( 'body', {} );

	function nav( index ) {
		const el = element( 'nav', {
			'class'							: 'nino-toc',
			'data-toc-levels'		: options.levels !== undefined ? options.levels : '2,3',
			'data-toc-within'		: options.within !== undefined ? options.within : '',
			'data-toc-anchors'	: options.anchors !== undefined ? options.anchors : '1',
			'data-toc-label'		: 'Link zu diesem Abschnitt',
		} );
		el.hidden = true;
		const title = element( 'h2', { 'class' : 'nino-toc-title', id : index === 0 ? 'nino-toc-title' : 'nino-toc-title', text : 'Auf dieser Seite' } );
		el.appendChild( title );
		el.appendChild( element( 'ol', { 'class' : 'nino-toc-list' } ) );
		body.appendChild( el );
		return el;
	}

	const first = nav( 0 );
	const other = options.second === true ? nav( 1 ) : null;

	const made = [];
	for( const spec of ( options.headings || [ [ 'h2', 'Erstens' ], [ 'h3', 'Genauer' ], [ 'h2', 'Zweitens' ] ] ) ) {
		const el = element( spec[0], { text : spec[1], 'class' : spec[2] || '', id : spec[3] || '' } );
		el.top = spec[4] !== undefined ? spec[4] : made.length * 400;
		body.appendChild( el );
		made.push( el );
	}

	const listeners = {};
	let frame = null;

	const dc = {
		readyState			: options.readyState || 'complete',
		createElement		: function( tag ) { return element( tag, {} ) },
		getElementById	: function( id ) { return descendants( body ).filter( function( n ) { return n.id === id } )[0] || null },
		querySelector		: function( selector ) { return body.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) { return body.querySelectorAll( selector ) },
		addEventListener	: function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
	};

	const sandbox = {
		console : console,
		document : dc,
		window : { innerHeight : 900, addEventListener : function( type, fn ) { ( listeners[type] = listeners[type] || [] ).push( fn ) },
			requestAnimationFrame : function( fn ) { frame = fn; return 1 } },
	};
	sandbox.window.window = sandbox.window;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'toc.js' } );

	return {
		body : body, nav : first, other : other, headings : made, listeners : listeners,
		items : function( which ) {
			return ( which || first ).querySelectorAll( '.nino-toc-link' );
		},
		texts : function( which ) { return this.items( which ).map( function( a ) { return a.textContent } ) },
		hrefs : function( which ) { return this.items( which ).map( function( a ) { return a.href } ) },
		current : function() {
			return this.items().filter( function( a ) { return a.getAttribute('aria-current') === 'true' } ).map( function( a ) { return a.textContent } );
		},
		scrollTo : function( tops ) {
			for( let i = 0; i < tops.length; i++ ) made[i].top = tops[i];
			( listeners['scroll'] || [] ).forEach( function( fn ) { fn() } );
			if( frame !== null ) { const fn = frame; frame = null; fn() }
		},
	};
}


// --- The list ------------------------------------------------------------------

const plain = page( {} );

check( 'the nav comes out of hiding once there is something in it', plain.nav.hidden === false );
check( 'every heading is in the list, in the order the page has them', plain.texts().join(' | ') === 'Erstens | Genauer | Zweitens' );
check( '...each linking to the heading it names', plain.hrefs().join(' ') === '#erstens #genauer #zweitens' );
check( '...and carrying its level, which is what the indent is made of',
	plain.items().map( function( a ) { return a.parent.className } ).join(' ') === 'nino-toc-item nino-toc-item--2 nino-toc-item nino-toc-item--3 nino-toc-item nino-toc-item--2' );
check( 'the list never lists its own title', plain.texts().indexOf( 'Auf dieser Seite' ) === -1 );


// --- The ids -------------------------------------------------------------------

check( 'a heading with no id is given one made from its own words', plain.headings[0].id === 'erstens' );

const kept = page( { headings : [ [ 'h2', 'Erstens', '', 'schon-da' ] ] } );
check( 'and one that already has an id keeps it - it may be linked to from somewhere else',
	kept.headings[0].id === 'schon-da' && kept.hrefs().join('') === '#schon-da' );

const twins = page( { headings : [ [ 'h2', 'Kontakt' ], [ 'h2', 'Kontakt' ] ] } );
check( 'two headings with the same words get two different ids',
	twins.headings[0].id === 'kontakt' && twins.headings[1].id === 'kontakt-2'
	&& twins.hrefs().join(' ') === '#kontakt #kontakt-2' );

const umlaut = page( { headings : [ [ 'h2', 'Über uns & mehr' ] ] } );
check( '...and a heading with umlauts and punctuation still makes a usable one', umlaut.headings[0].id === 'ueber-uns-mehr' );

/*	An id is checked against the whole page rather than against the list: a
	heading this list does not hold - marked nino-toc-skip here - is not among
	the ones already handed out, and two elements carrying one id is a link
	that lands on whichever of them the browser finds first	*/
const elsewhere = page( { headings : [ [ 'h2', 'Kontakt', 'nino-toc-skip', 'kontakt' ], [ 'h2', 'Kontakt' ] ] } );
check( '...and an id the page already carries outside the list is not handed out a second time',
	elsewhere.headings[1].id === 'kontakt-2' && elsewhere.hrefs().join('') === '#kontakt-2' );


// --- What is left out ----------------------------------------------------------

const skipped = page( { headings : [ [ 'h2', 'Erstens' ], [ 'h2', 'Nicht ins Verzeichnis', 'nino-toc-skip' ], [ 'h2', 'Zweitens' ] ] } );
check( 'a heading marked nino-toc-skip is left out of the list', skipped.texts().join(' | ') === 'Erstens | Zweitens' );
check( '...and given no anchor either', skipped.headings[1].querySelectorAll( '.nino-toc-anchor' ).length === 0 );

const only2 = page( { levels : '2' } );
check( 'a list built from h2 alone leaves the h3 out', only2.texts().join(' | ') === 'Erstens | Zweitens' );

const empty = page( { headings : [] } );
check( 'a page with no headings keeps a nav that says nothing rather than an empty list',
	empty.nav.hidden === true && empty.items().length === 0 );


// --- The anchors ---------------------------------------------------------------

check( 'every listed heading gets a link to itself', plain.headings[0].querySelectorAll( '.nino-toc-anchor' ).length === 1 );
check( '...that points at the heading and names itself for somebody who cannot see it',
	plain.headings[0].querySelector( '.nino-toc-anchor' ).href === '#erstens'
	&& plain.headings[0].querySelector( '.nino-toc-anchor' ).getAttribute( 'aria-label' ) === 'Link zu diesem Abschnitt' );

const bare = page( { anchors : '0' } );
check( 'and none at all where the project switched them off, though the list still works',
	bare.headings[0].querySelectorAll( '.nino-toc-anchor' ).length === 0 && bare.texts().length === 3 );


// --- The section being read ----------------------------------------------------

check( 'the first section is current before anything has been scrolled past', plain.current().join('') === 'Erstens' );

/*	The mark sits a third down the viewport - 306 of 900 here. A heading above
	it has been reached; the last one that has is the section being read	*/
plain.scrollTo( [ -100, 400, 800 ] );
check( 'the last heading above the mark is the one that is current', plain.current().join('') === 'Erstens' );

plain.scrollTo( [ -800, -100, 500 ] );
check( '...and it follows the page down', plain.current().join('') === 'Genauer' );

plain.scrollTo( [ -1600, -900, -100 ] );
check( '...to the last one', plain.current().join('') === 'Zweitens' );
check( 'and only ever one at a time', plain.current().length === 1 );


// --- Two lists on one page -----------------------------------------------------

const twice = page( { second : true } );
check( 'a second list on the same page gets its own title id, so aria-labelledby names the right one',
	twice.other !== null && twice.other.querySelector( '.nino-toc-title' ).id === 'nino-toc-title-2'
	&& twice.other.getAttribute( 'aria-labelledby' ) === 'nino-toc-title-2' );
check( '...and both are filled', twice.texts().length === 3 && twice.texts( twice.other ).length === 3 );

/*	The first list puts an anchor on every heading it lists, and that '#' is
	part of the heading's textContent from then on	*/
check( '...with the headings\' own words in the second one too, not the # of the anchors the first put on them',
	twice.texts( twice.other ).join(' | ') === 'Erstens | Genauer | Zweitens' );


// --- Waiting for the dom -------------------------------------------------------

const late = page( { readyState : 'loading' } );

check( 'a script that ran before the dom was there waits for it rather than finding nothing',
	late.nav.hidden === true && ( late.listeners['DOMContentLoaded'] || [] ).length === 1 );

( late.listeners['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn() } );
check( '...and builds the list when it arrives', late.nav.hidden === false && late.texts().length === 3 );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
