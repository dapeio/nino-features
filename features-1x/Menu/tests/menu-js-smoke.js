/**
 *	Nino
 *	menu-js-smoke.js		What the two scripts of the Menu feature do over a dom stand-in.
 *											Part 1, assets/menu.js on the site: the hover of an entry opens
 *											its panel after a short delay and the pointer leaving closes it
 *											after a longer one, the button turns a panel, a link in a panel
 *											that has the focus holds it open, Escape closes it and hands
 *											the focus to its button, a press or a focus outside closes it,
 *											one panel is open at a time, aria-expanded follows, below 768px
 *											the button turns the panel as an accordion and the clicks in the
 *											panel stay out of the burger overlay's way, and the window
 *											crossing the breakpoint closes what was open. Part 2, the model
 *											of the panel's script (assets/admin.js): the operations on
 *											entries and items, and the columns from one to four.
 *
 *											No jsdom, no dependency: a stand-in with the tree, the classes,
 *											the attributes, the capture and bubble of an event and a clock
 *											the test advances. Part 2 loads the kernel's own Nino.admin.js
 *											from the checkout NINO_ROOT names, else three levels up.
 *
 *	Usage: node features/Menu/tests/menu-js-smoke.js
 *	       NINO_ROOT=../nino node features/Menu/tests/menu-js-smoke.js
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

const feature = path.join( __dirname, '..' );

// --- The stand-in ---------------------------------------------------------------

// The page that is being tested: an element's focus() is its
let stage = null;

/**
 *	One element: the tree, the classes, the attributes, and the listeners an event passes
 *
 *	@param		{string}		tag
 *	@param		{string}		[classes]
 *
 *	@return		{Object}
 */
function element( tag, classes ) {

	const el = {
		tagName			: tag.toUpperCase(),
		parentNode		: null,
		children		: [],
		attributes		: {},
		listeners		: {},
		classes			: new Set( ( classes || '' ).split( ' ' ).filter( function( name ) { return name !== '' } ) ),
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( el.attributes, name ) ? el.attributes[name] : null },
		setAttribute		: function( name, value ) { el.attributes[name] = String( value ) },
		addEventListener	: function( type, fn, capture ) { ( el.listeners[type] = el.listeners[type] || [] ).push( { fn : fn, capture : capture === true } ) },
		appendChild			: function( child ) { child.parentNode = el; el.children.push( child ); return child },
		focus				: function() { stage.focus( el ) },
		querySelector		: function( selector ) { return el.querySelectorAll( selector )[0] || null },
		querySelectorAll	: function( selector ) {
			const name = selector.replace( /^\./, '' );
			const found = [];
			( function walk( node ) {
				node.children.forEach( function( child ) {
					if( child.classes.has( name ) )
						found.push( child );
					walk( child );
				} );
			} )( el );
			return found;
		},
	};

	el.classList = { add : function( name ) { el.classes.add( name ) }, contains : function( name ) { return el.classes.has( name ) } };

	return el;
}

/**
 *	A page with one menu: a list of three entries, the first two with a panel (a link in it, a text, a button), the third plain
 *
 *	@param		{number}		width					The width of the window
 *
 *	@return		{Object}
 */
function siteWorld( width ) {

	let now = 0;
	let nextId = 1;
	const timers = {};
	const document = element( 'document' );
	const resize = [];
	const state = { width : width, flat : false, started : 0, hooks : {} };

	const timer = {
		setTimeout		: function( fn, ms ) { timers[nextId] = { at : now + ms, fn : fn }; return nextId++ },
		clearTimeout	: function( id ) { delete timers[id] },
	};

	document.activeElement = null;
	document.documentElement = element( 'html' );

	const body = document.appendChild( element( 'body' ) );
	const list = body.appendChild( element( 'ul', 'nino-menu' ) );
	const entries = [];

	[ 'a', 'b', 'plain' ].forEach( function( name ) {

		const item = list.appendChild( element( 'li', name === 'plain' ? 'nino-menu-item' : 'nino-menu-item nino-menu-item--panel' ) );
		const entry = { name : name, item : item, link : item.appendChild( element( 'a', 'nino-menu-link' ) ) };

		if( name !== 'plain' ) {
			entry.toggle = item.appendChild( element( 'button', 'nino-menu-toggle' ) );
			entry.toggle.setAttribute( 'aria-expanded', 'false' );
			entry.panel = item.appendChild( element( 'div', 'nino-menu-panel' ) );
			entry.text = entry.panel.appendChild( element( 'p', 'nino-menu-title' ) );
			entry.sub = entry.panel.appendChild( element( 'a', 'nino-menu-link nino-menu-link--sub' ) );
		}

		entries.push( entry );
	} );

	const outside = body.appendChild( element( 'a', 'outside' ) );

	/**
	 *	An event through the tree: the capturing listeners from the top, the target's, then the bubbling ones, until one stops it
	 */
	function dispatch( target, event, bubbles ) {

		event.target = target;
		event.propagationStopped = false;
		event.defaultPrevented = false;
		event.stopPropagation = function() { event.propagationStopped = true };
		event.preventDefault = function() { event.defaultPrevented = true };

		const path = [];
		for( let walk = target; walk !== null; walk = walk.parentNode )
			path.push( walk );

		const run = function( node, capture ) {
			( node.listeners[event.type] || [] ).filter( function( entry ) { return capture === null || entry.capture === capture } ).forEach( function( entry ) { entry.fn( event ) } );
		};

		if( bubbles === false ) {
			run( target, null );
			return event;
		}

		for( let i = path.length - 1; i >= 0 && event.propagationStopped === false; i-- )
			if( path[i] !== target )
				run( path[i], true );

		for( let i = 0; i < path.length && event.propagationStopped === false; i++ )
			run( path[i], false );

		return event;
	}

	stage = {
		focus : function( el ) {
			document.activeElement = el;
			dispatch( el, { type : 'focusin' } );
		},
	};

	const window = {
		// The shape is the stylesheet's: a panel is positioned from 768px, unless the list stands in an open overlay or a rail
		getComputedStyle	: function() { return { position : state.flat === true ? 'static' : ( state.width >= 768 ? 'absolute' : 'static' ) } },
		addEventListener	: function( type, fn ) { if( type === 'resize' ) resize.push( fn ) },
	};

	const context = vm.createContext( {
		window : window,
		document : document,
		setTimeout : timer.setTimeout,
		clearTimeout : timer.clearTimeout,
		Nino : { events : { bindCallback : function( name, fn ) { state.hooks[name] = fn } } },
	} );

	vm.runInContext( fs.readFileSync( path.join( feature, 'assets/menu.js' ), 'utf8' ), context, { filename : 'menu.js' } );

	return {
		list : list,
		entries : entries,
		a : entries[0],
		b : entries[1],
		plain : entries[2],
		outside : outside,
		document : document,
		started : function() { return state.hooks.ready !== undefined },
		start : function() { state.hooks.ready() },
		wait : function( ms ) {
			now += ms;
			Object.keys( timers ).map( Number ).sort( function( x, y ) { return timers[x].at - timers[y].at } ).forEach( function( id ) {
				if( timers[id] !== undefined && timers[id].at <= now ) {
					const due = timers[id];
					delete timers[id];
					due.fn();
				}
			} );
		},
		pending : function() { return Object.keys( timers ).length },
		flat : function( on ) { state.flat = on },
		style : function( shape ) { state.flat = shape === 'static'; state.width = shape === 'absolute' ? 1280 : 390 },
		resize : function( to ) { state.width = to; resize.forEach( function( fn ) { fn() } ) },
		enter : function( entry, type ) { return dispatch( entry.item, { type : 'pointerenter', pointerType : type || 'mouse' }, false ) },
		leave : function( entry, type ) { return dispatch( entry.item, { type : 'pointerleave', pointerType : type || 'mouse' }, false ) },
		click : function( target ) { return dispatch( target, { type : 'click' } ) },
		down : function( target ) { return dispatch( target, { type : 'pointerdown' } ) },
		key : function( key ) { return dispatch( document.activeElement || body, { type : 'keydown', key : key } ) },
		expanded : function( entry ) { return entry.toggle.getAttribute( 'aria-expanded' ) },
		openOnes : function() { return entries.filter( function( entry ) { return entry.toggle !== undefined && entry.toggle.getAttribute( 'aria-expanded' ) === 'true' } ).map( function( entry ) { return entry.name } ).join() },
		focused : function() { return document.activeElement },
	};
}

/** A page whose script has started */
function started( width ) {
	const w = siteWorld( width );
	w.start();
	return w;
}

// --- Part 1: menu.js -------------------------------------------------------------

console.log( 'menu.js - starting\n' );

{
	const w = siteWorld( 1280 );

	check( 'the script waits for the kernel\'s ready callback and touches nothing before it', w.started() === true && w.list.classes.has( 'nino-menu-js' ) === false );

	w.start();
	check( 'a list is taken over: it carries nino-menu-js, which hands the panels from the stylesheet\'s :hover to the script', w.list.classes.has( 'nino-menu-js' ) === true );
	check( '...and every panel is closed to begin with', w.openOnes() === '' && w.expanded( w.a ) === 'false' );
}

{
	const w = siteWorld( 1280 );
	w.document.children[0].children.length = 0;
	w.start();

	check( 'a page without a menu is left alone: the script puts nothing on the document', Object.keys( w.document.listeners ).length === 0 );
}

console.log( '\nmenu.js - the pointer, from 768px\n' );

{
	const w = started( 1280 );

	w.enter( w.a );
	w.wait( 119 );
	check( 'the panel does not open before the delay of about 120 ms is over', w.expanded( w.a ) === 'false' );
	w.wait( 1 );
	check( '...and opens when it is', w.expanded( w.a ) === 'true' );

	w.leave( w.a );
	w.wait( 299 );
	check( 'it stays open for about 300 ms after the pointer has left the entry and its panel', w.expanded( w.a ) === 'true' );
	w.wait( 1 );
	check( '...and closes then', w.expanded( w.a ) === 'false' && w.pending() === 0 );

	w.enter( w.a );
	w.wait( 120 );
	w.leave( w.a );
	w.wait( 200 );
	w.enter( w.a );
	w.wait( 1000 );
	check( 'a pointer that comes back within the delay - over the gap between the entry and the panel - keeps the panel open', w.expanded( w.a ) === 'true' );

	w.leave( w.a );
	w.wait( 1000 );

	w.enter( w.a );
	w.wait( 60 );
	w.leave( w.a );
	w.wait( 1000 );
	check( 'a pointer that passes over an entry without resting on it opens nothing', w.expanded( w.a ) === 'false' && w.pending() === 0 );
}

{
	const w = started( 1280 );

	w.enter( w.a, 'touch' );
	w.enter( w.a, 'pen' );
	w.wait( 1000 );
	check( 'the hover is the mouse\'s: a touch or a pen opens nothing, the button is theirs', w.expanded( w.a ) === 'false' && w.pending() === 0 );

	w.enter( w.plain );
	w.wait( 1000 );
	check( 'an entry without a panel has nothing to open', w.openOnes() === '' );
}

console.log( '\nmenu.js - the button and one at a time\n' );

{
	const w = started( 1280 );

	w.click( w.a.toggle );
	check( 'a press of the button opens the panel at once, and the button says so', w.expanded( w.a ) === 'true' && w.pending() === 0 );
	w.click( w.a.toggle );
	check( '...and the next closes it: the button turns it', w.expanded( w.a ) === 'false' );

	const press = w.click( w.a.toggle );
	check( 'the press does not travel on to the overlay of the burger, which closes on any click that is not its own button', press.propagationStopped === true );

	w.enter( w.b );
	w.wait( 120 );
	check( 'one panel is open at a time: a second entry that opens closes the first', w.openOnes() === 'b' && w.expanded( w.a ) === 'false' );

	w.click( w.a.toggle );
	check( '...so does a press of the button of another', w.openOnes() === 'a' );

	w.leave( w.a );
	w.enter( w.b );
	w.wait( 1000 );
	check( 'the timer of an entry that was left does not close the panel of the one that opened since', w.openOnes() === 'b' );
}

{
	const w = started( 1280 );

	w.enter( w.a );
	w.click( w.a.toggle );
	w.wait( 1000 );
	check( 'a press while the hover is still waiting is the answer: the panel opens, once, and the hover does not turn it again', w.openOnes() === 'a' && w.pending() === 0 );
}

console.log( '\nmenu.js - the keyboard and what is outside\n' );

{
	const w = started( 1280 );

	w.a.link.focus();
	w.a.toggle.focus();
	check( 'the link of an entry and its button, with the focus, open nothing: the Tab key can pass a panel', w.openOnes() === '' );

	w.a.sub.focus();
	check( 'a link inside a panel that has the focus opens the panel', w.openOnes() === 'a' && w.expanded( w.a ) === 'true' );

	w.b.sub.focus();
	check( '...one panel at a time: the focus in another closes the first', w.openOnes() === 'b' );

	w.outside.focus();
	check( 'the focus outside the entry closes its panel', w.openOnes() === '' );

	w.a.sub.focus();
	w.key( 'Escape' );
	check( 'Escape closes the open panel and puts the focus on its button', w.openOnes() === '' && w.focused() === w.a.toggle );

	const idle = w.key( 'Escape' );
	check( '...and does nothing where nothing is open', w.openOnes() === '' && idle.defaultPrevented === false );

	w.click( w.a.toggle );
	w.down( w.a.panel );
	check( 'a press inside the entry leaves the panel open', w.openOnes() === 'a' );

	w.down( w.outside );
	check( 'a press outside closes it', w.openOnes() === '' );

	w.click( w.a.toggle );
	w.key( 'Tab' );
	w.key( 'a' );
	check( 'any other key leaves it', w.openOnes() === 'a' );
}

console.log( '\nmenu.js - below 768px, the accordion in the burger\n' );

{
	const w = started( 390 );

	w.enter( w.a );
	w.wait( 1000 );
	check( 'the hover opens nothing on a phone', w.openOnes() === '' && w.pending() === 0 );

	w.a.sub.focus();
	check( '...nor does the focus in a panel: it is closed behind the button, and a link that is not shown has no focus', w.openOnes() === '' );

	const press = w.click( w.a.toggle );
	check( 'the button turns the panel in the flow, and aria-expanded says it', w.openOnes() === 'a' && w.expanded( w.a ) === 'true' && press.propagationStopped === true );

	w.click( w.b.toggle );
	check( 'it is an accordion: opening one closes the other', w.openOnes() === 'b' );

	w.click( w.b.toggle );
	check( '...and the same button folds it again', w.openOnes() === '' );

	w.click( w.a.toggle );
	const text = w.click( w.a.text );
	check( 'a click on the words of a panel does not close the overlay: it does not reach it', text.propagationStopped === true && w.openOnes() === 'a' );

	const link = w.click( w.a.sub );
	check( '...but a click on a link in it does, as it does on the links of the list', link.propagationStopped === false );

	w.down( w.outside );
	w.key( 'Escape' );
	check( 'a press elsewhere and Escape leave the accordion to the overlay, which closes itself', w.openOnes() === 'a' );
}

console.log( '\nmenu.js - the shape is the stylesheet\'s\n' );

{
	const w = started( 1280 );

	w.flat( true );
	w.enter( w.a );
	w.wait( 1000 );
	check( 'a panel that the style does not position is an accordion at any width: the hover opens nothing at 1280px', w.openOnes() === '' && w.pending() === 0 );

	const press = w.click( w.a.toggle );
	w.click( w.b.toggle );
	check( '...the button turns it, one at a time, and the press stays out of the overlay\'s way', press.propagationStopped === true && w.openOnes() === 'b' );

	w.a.sub.focus();
	w.b.sub.focus();
	check( '...the focus in a panel opens nothing, the pointer and the focus outside close nothing, Escape is left to the overlay', ( w.down( w.outside ), w.outside.focus(), w.key( 'Escape' ), w.openOnes() === 'b' ) );

	w.flat( false );
	w.click( w.b.toggle );
	w.enter( w.a );
	w.wait( 120 );
	check( '...and as soon as the style positions it again it is a dropdown that opens on the hover', w.openOnes() === 'a' );
}

{
	const w = started( 390 );

	w.style( 'absolute' );
	w.enter( w.a );
	w.wait( 120 );
	check( 'the width does not decide: a panel the style positions opens on the hover at 390px too', w.openOnes() === 'a' );
}

{
	const w = started( 1280 );

	w.click( w.a.toggle );
	w.flat( true );
	w.resize( 1280 );
	check( 'a panel that was open as a dropdown is closed when the style makes it an accordion - the overlay opens over a bar that is still wide', w.openOnes() === '' );
}

{
	const w = started( 1280 );

	w.enter( w.a );
	w.wait( 119 );
	w.leave( w.a );
	w.wait( 10 );
	const soon = w.openOnes();
	w.wait( 400 );
	check( 'a pass over an entry that is a moment shorter than the open delay opens nothing, not even for the moment before the close delay would take it away again', soon === '' && w.openOnes() === '' && w.pending() === 0 );
}

console.log( '\nmenu.js - the window crosses the breakpoint\n' );

{
	const w = started( 1280 );

	w.click( w.a.toggle );
	w.resize( 1100 );
	check( 'a window that stays wide leaves the panel open', w.openOnes() === 'a' );

	w.resize( 600 );
	check( 'a window that becomes narrow closes it - it was a dropdown, and is an accordion now', w.openOnes() === '' && w.expanded( w.a ) === 'false' );

	w.click( w.b.toggle );
	w.resize( 1280 );
	check( '...and so does one that becomes wide again', w.openOnes() === '' );
}

// --- Part 2: the panel's script ---------------------------------------------------------

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const kernel = require( path.join( root, 'tests/harness.js' ) );

/**
 *	A file of the feature the way load() and fills() want it: from the root of the checkout, which the feature sits outside of when it is tested from the catalogue
 *
 *	@param		{string}	relative		A path below features/Menu/
 *
 *	@return		{string}
 */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

const adminSource = fs.readFileSync( path.join( feature, 'assets/admin.js' ), 'utf8' );

/**
 *	The model alone: the script run over a Nino that has only what it reads at load, so that its operations can be asked
 *
 *	@return		{Object}
 */
function modelOf() {

	const top = kernel.element('div');
	const Nino = { adminUi : { panel : function() { return {} } }, content : { getText : function() { return '' } } };
	const context = vm.createContext( { document : kernel.documentOf( top ), Nino : Nino, window : { Nino : Nino } } );

	vm.runInContext( adminSource, context, { filename : 'admin.js' } );

	return Nino.admin.menu.model;
}

/** The text of an element stand-in: its own and that of the text nodes under it */
function textOf( el ) {
	return el.textContent+ el.children.map( textOf ).join('');
}

console.log( '\nNino.admin.menu.model - ids, names and numbers\n' );

{
	const m = modelOf();

	check( 'an id is made of a text: lower case words, a letter first, no number at its end, and not "list"',
		m.slug( 'About us' ) === 'about-us' && m.slug( '/blog/2' ) === 'blog-2-x' && m.slug( '1st' ) === 'p-1st' && m.slug( '' ) === 'entry' && m.slug( 'list' ) === 'list-x'
		&& m.slug( 'Über' ) === 'ber' && /^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/.test( m.slug( '!!a--b..c' ) ) === true && m.slug( 'x'.repeat( 60 ) ).length <= 24 );
	check( '...and an id that is taken gets a letter after a hyphen, never a number, however many are taken',
		m.uniqueId( [], 'about' ) === 'about' && m.uniqueId( [ 'about' ], 'about' ) === 'about-a' && m.uniqueId( [ 'about', 'about-a' ], 'about' ) === 'about-b'
		&& m.uniqueId( [ 'about' ].concat( 'abcdefghijklmnopqrstuvwxyz'.split('').map( function( c ) { return 'about-'+ c } ) ), 'about' ) === 'about-aa' );
	check( 'a name is /feature/menu/<menu>/<id>, with -<n> for what stands in the panel', m.nameOf( 'main', 'docs' ) === '/feature/menu/main/docs' && m.nameOf( 'main', 'docs', 3 ) === '/feature/menu/main/docs-3' );

	const entry = { id : 'docs', panel : { columns : 2, cols : [ [ { kind : 'title', key : '/feature/menu/main/docs-1' } ], [ { kind : 'image', slot : '/feature/menu/main/docs-4' } ] ] } };
	check( 'the number of a new item is one past the highest an item has - and one past a key or a slot that an item once had, which the server still knows',
		m.nextNumber( 'main', entry, [] ) === 5 && m.nextNumber( 'main', entry, [ '/feature/menu/main/docs-9', '/feature/menu/main/other-12', '/feature/menu/main/docs-x' ] ) === 10
		&& m.nextNumber( 'main', { id : 'fresh', panel : null }, [ '/feature/menu/main/docs-7' ] ) === 1 );
}

console.log( '\nNino.admin.menu.model - entries and items\n' );

{
	const m = modelOf();
	const entries = [];

	entries.push( m.newEntry( 'main', 'page', entries, { uri : '/about' } ) );
	entries.push( m.newEntry( 'main', 'page', entries, { uri : '/about' } ) );
	entries.push( m.newEntry( 'main', 'link', entries ) );
	entries.push( m.newEntry( 'main', 'link', entries ) );

	check( 'a new page entry has its page, no label key of its own and no panel; its id comes from the page', JSON.stringify( entries[0] ) === '{"id":"about","kind":"page","uri":"/about","label":"","panel":null}' );
	check( '...a second entry for the same page gets an id of its own', entries[1].id === 'about-a' );
	check( '...a link has the label key from its id, an address and a target to fill in', JSON.stringify( entries[2] ) === '{"id":"link","kind":"link","href":"","target":"","label":"/feature/menu/main/link","panel":null}' && entries[3].id === 'link-a' && entries[3].label === '/feature/menu/main/link-a' );

	const order = function() { return entries.map( function( entry ) { return entry.id } ).join() };

	check( 'an entry moves up and down one place and stops at the ends', m.move( entries, 1, -1 ) === 0 && order() === 'about-a,about,link,link-a' && m.move( entries, 0, -1 ) === 0 && order() === 'about-a,about,link,link-a'
		&& m.move( entries, 3, 1 ) === 3 && m.move( entries, 2, 1 ) === 3 && order() === 'about-a,about,link-a,link' );
	check( '...and is removed by its place, a place that is none removes nothing', m.remove( entries, 2 ) === true && order() === 'about-a,about,link' && m.remove( entries, 7 ) === false && m.remove( entries, -1 ) === false && entries.length === 3 );

	const entry = entries[0];
	m.setPanel( entry, true );
	check( 'a panel switched on is one empty column; switched on again it keeps what it holds', JSON.stringify( entry.panel ) === '{"columns":1,"cols":[[]]}' && ( function() { entry.panel.cols[0].push( { kind : 'html', html : 'x' } ); m.setPanel( entry, true ); return entry.panel.cols[0].length === 1 } )() );

	check( 'the columns are one to four: more are empty ones, a number outside is cut to the nearest, a text or nothing is one', [ [ 3, 3 ], [ 4, 4 ], [ 9, 4 ], [ 0, 1 ], [ -2, 1 ], [ '2', 2 ], [ 2.7, 2 ], [ NaN, 1 ], [ undefined, 1 ], [ 'x', 1 ] ].every( function( pair ) {
		const panel = { columns : 1, cols : [ [] ] };
		return m.setColumns( panel, pair[0] ) === pair[1] && panel.columns === pair[1] && panel.cols.length === pair[1];
	} ) );

	const panel = { columns : 4, cols : [ [ 'a' ], [ 'b', 'c' ], [ 'd' ], [ 'e', 'f' ] ] };
	m.setColumns( panel, 2 );
	check( '...fewer hand the items of the columns that go to the last one that stays, in their order', JSON.stringify( panel ) === '{"columns":2,"cols":[["a"],["b","c","d","e","f"]]}' );
	m.setColumns( panel, 3 );
	check( '...and more again start empty', JSON.stringify( panel.cols ) === '[["a"],["b","c","d","e","f"],[]]' );

	const holder = { id : 'docs', panel : { columns : 1, cols : [ [] ] } };
	const items = holder.panel.cols[0];
	[ 'title', 'subtitle', 'image', 'page', 'link', 'html' ].forEach( function( kind ) { items.push( m.newItem( 'main', holder, kind, [], { uri : '/about' } ) ) } );

	check( 'a new item of each kind: a title, a subtitle, a picture and a link are called what no item of the entry is called, a page and a piece of HTML+ start empty',
		JSON.stringify( items ) === JSON.stringify( [
			{ kind : 'title', key : '/feature/menu/main/docs-1' }, { kind : 'subtitle', key : '/feature/menu/main/docs-2' }, { kind : 'image', slot : '/feature/menu/main/docs-3' },
			{ kind : 'page', uri : '/about', label : '' }, { kind : 'link', href : '', target : '', label : '/feature/menu/main/docs-4' }, { kind : 'html', html : '' },
		] ) );

	check( 'an item moves within its column and is removed by its place', m.move( items, 1, -1 ) === 0 && items[0].kind === 'subtitle' && m.remove( items, 0 ) === true && items.length === 5 && items[0].kind === 'title' );

	const title = { kind : 'title', key : '/feature/menu/main/docs-1' };
	check( 'an item changed to another kind keeps its name where the new kind has one: a title becomes a subtitle, a link or a picture of the same name',
		m.convertItem( 'main', holder, title, 'subtitle', [] ).key === '/feature/menu/main/docs-1' && m.convertItem( 'main', holder, title, 'link', [] ).label === '/feature/menu/main/docs-1'
		&& m.convertItem( 'main', holder, title, 'image', [] ).slot === '/feature/menu/main/docs-1' && m.convertItem( 'main', holder, title, 'title', [] ) === title );
	check( '...a page keeps its page, a piece of HTML+ has no name to carry', m.convertItem( 'main', holder, { kind : 'page', uri : '/contact', label : '' }, 'page', [] ).uri === '/contact'
		&& m.convertItem( 'main', holder, { kind : 'link', href : 'https://x', target : '', label : '/feature/menu/main/docs-5' }, 'page', [], { uri : '/about' } ).uri === '/about'
		&& JSON.stringify( m.convertItem( 'main', holder, title, 'html', [] ) ) === '{"kind":"html","html":""}' );
}

console.log( '\nNino.admin.menu.model - what a save posts\n' );

{
	const m = modelOf();
	const entries = [
		{ id : 'about', kind : 'page', uri : '/about', label : '/feature/menu/main/about', panel : null },
		{ id : 'docs', kind : 'link', href : '/x', target : '', label : '/feature/menu/main/docs', panel : { columns : 1, cols : [ [
			{ kind : 'title', key : '/feature/menu/main/docs-1' }, { kind : 'page', uri : '/a', label : '/feature/menu/main/docs-2' }, { kind : 'page', uri : '/b', label : '' }, { kind : 'html', html : '<p>' },
		] ] } },
	];
	const texts = { '/feature/menu/main/about' : { de_DE : '', en_US : '' }, '/feature/menu/main/docs' : { en_US : 'Docs' }, '/feature/menu/main/docs-1' : { en_US : 'Read' }, '/feature/menu/main/docs-2' : { de_DE : 'Zwei' }, '/feature/menu/main/gone-1' : { en_US : 'x' } };
	const before = JSON.stringify( entries );
	const sent = m.payload( 'main', entries, texts );

	check( 'a page whose label has no text in any language is posted with the empty label, the one that has a text keeps its key', sent.entries[0].label === '' && sent.entries[1].panel.cols[0][1].label === '/feature/menu/main/docs-2' && sent.entries[1].panel.cols[0][2].label === '' );
	check( '...the texts are those of the keys the posted entries name, and no others - a key of an item that was taken away is not sent', Object.keys( sent.texts ).sort().join() === '/feature/menu/main/docs,/feature/menu/main/docs-1,/feature/menu/main/docs-2'
		&& sent.texts['/feature/menu/main/docs'].en_US === 'Docs' && sent.key === 'main' );
	check( '...and the model itself is left as it is', JSON.stringify( entries ) === before && entries[0].label === '/feature/menu/main/about' );
	check( 'the keys the entries name are the labels that are keys, the titles, the subtitles and the labels of links', m.keysOf( entries ).join() === '/feature/menu/main/about,/feature/menu/main/docs,/feature/menu/main/docs-1,/feature/menu/main/docs-2' && m.hasText( texts, '/feature/menu/main/docs-2' ) === true && m.hasText( texts, '/feature/menu/main/about' ) === false );
}

// --- the screens, over the kernel's own Nino.admin.js ---------------------------------------

/**
 *	The panel's script in a page of its own, in one interface language: the kernel's Nino.admin.js and the script over a Nino that keeps the requests,
 *	the entry the panel registers and the words it asks for
 *
 *	@param		{string}		locale		en_US or de_DE
 *
 *	@return		{Object}
 */
function panelWorld( locale ) {

	const words = Object.assign( kernel.fills( '_admin/text/'+ locale+ '.php' ), kernel.fills( own( 'text/'+ locale+ '.php' ) ) );
	const missing = [];
	const requests = [];
	const registered = [];
	const asked = [];
	const touched = [];
	const guards = [];
	const top = kernel.element('div');
	const list = top.appendChild( kernel.element('section') );
	const editor = top.appendChild( kernel.element('section') );

	top.id = 'menu-root';
	list.id = 'menu-list';
	editor.id = 'menu-editor';
	editor.className = 'menu-screen admin-hidden';

	const page = kernel.documentOf( top );
	const make = page.createElement;

	page.createElement = function( tag ) {
		const el = make( tag );
		Object.defineProperty( el, 'name', { get : function() { return el.attributes.name ?? '' }, set : function( value ) { el.setAttribute( 'name', value ) } } );
		return el;
	};

	const Nino = {
		content : { getText : function( key ) {
			if( words[key] === undefined )
				missing.push( key );
			return words[key] ?? '';
		} },
		admin : { dirty : { register : function( name, entry ) { registered.push( { name : name, entry : entry } ) }, refresh : function() {}, guard : function( names, go ) { guards.push( names ); go() } }, touch : function( topic ) { touched.push( topic ) } },
	};

	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : { Nino : Nino }, Date : Date } );

	kernel.load( '_admin/assets/Nino.admin.js', context );

	Nino.adminUi.api.call = function( action, payload, callback, extra ) {
		requests.push( { action : action, payload : payload, callback : callback, extra : extra, answered : false } );
	};
	Nino.adminUi.confirm = function( level, options ) { asked.push( { level : level, options : options } ); return true };
	Nino.adminUi.checkImage = function( file, done ) { done( null ) };

	kernel.load( own( 'assets/admin.js' ), context, 'admin.js' );

	const all = function( predicate ) { return kernel.findAll( top, predicate ) };

	return {
		panel : Nino.admin.menu,
		Nino : Nino,
		top : top,
		list : list,
		editor : editor,
		requests : requests,
		registered : registered,
		missing : missing,
		asked : asked,
		touched : touched,
		guards : guards,
		all : all,
		button : function( text ) { return all( function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === text } )[0] ?? null },
		buttons : function( text ) { return all( function( el ) { return el.tagName === 'BUTTON' && textOf( el ) === text } ) },
		control : function( label, nth ) {
			const wraps = all( function( el ) { return el.tagName === 'LABEL' && el.children[0] !== undefined && textOf( el.children[0] ) === label } );
			const wrap = wraps[nth ?? 0];
			return wrap === undefined ? null : ( kernel.findAll( wrap, function( el ) { return [ 'INPUT', 'SELECT', 'TEXTAREA' ].indexOf( el.tagName ) !== -1 } )[0] ?? null );
		},
		last : function( action ) { return requests.filter( function( request ) { return request.action === action } ).pop() ?? null },
		answer : function( action, status, body ) {
			const open = requests.filter( function( request ) { return request.action === action && request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( status, body );
		},
		entry : function() { return registered[0].entry },
		line : function() { return all( function( el ) { return el.classList.contains('nino-admin-status') } )[0] ?? null },
		hidden : function( el ) { return el.classList.contains('admin-hidden') },
	};
}

const MENUS = { menus : [ { key : 'main', entries : 2, kernel : true }, { key : 'side', entries : 1, kernel : false } ], navs : [ 'main', 'footer' ] };
const REGISTRY = {
	pages : [ { uri : '/about', name : 'About', names : { en_US : 'About', de_DE : 'Über uns' } }, { uri : '/contact', name : 'Contact', names : { en_US : 'Contact' } } ],
	navs : [ 'main', 'footer' ], navPages : { main : [ '/about', '/contact', '/home' ], footer : [] },
	locales : [ 'de_DE', 'en_US' ], native : 'en_US', active : [ 'de_DE', 'en_US' ], names : { de_DE : 'Deutsch', en_US : 'English' }, locale : 'en_US',
	slots : [], size : { width : 600, height : 400 },
};
const SIDE = {
	key : 'side', kernel : false,
	entries : [ { id : 'docs', kind : 'link', href : 'https://example.com', target : '', label : '/feature/menu/side/docs', panel : { columns : 2, cols : [ [ { kind : 'title', key : '/feature/menu/side/docs-1' }, { kind : 'image', slot : '/feature/menu/side/docs-2' } ], [] ] } } ],
	texts : { '/feature/menu/side/docs' : { en_US : 'Docs', de_DE : 'Doku' }, '/feature/menu/side/docs-1' : { en_US : 'Read' } },
	taken : [ '/feature/menu/side/docs', '/feature/menu/side/docs-1', '/feature/menu/side/docs-2' ], problems : [],
};

/** The panel shown and its list answered */
function listed( locale ) {
	const w = panelWorld( locale );
	w.panel.showCurrent();
	w.answer( 'menu/list', 200, MENUS );
	return w;
}

/** An existing menu open in the editor */
function opened( locale, menu, registry ) {
	const w = listed( locale );
	w.all( function( el ) { return el.dataset.key === 'side' } )[0].listeners.click[0]();
	w.answer( 'menu/registry', 200, JSON.parse( JSON.stringify( registry ?? REGISTRY ) ) );
	w.answer( 'menu/load', 200, JSON.parse( JSON.stringify( menu ?? SIDE ) ) );
	return w;
}

console.log( '\nNino.admin.menu - the list\n' );

{
	const w = panelWorld('en_US');

	check( 'nothing is asked before the pane is shown, and then menu/list once', w.requests.length === 0 && ( w.panel.showCurrent(), w.panel.showCurrent(), w.requests.length === 1 ) && w.requests[0].action === 'menu/list' );
	check( '...it registers its entry under its uri, which holds nothing unsaved', w.registered.length === 1 && w.registered[0].name === 'menu' && w.entry().isDirty() === false );

	w.answer( 'menu/list', 200, MENUS );
	const rows = w.all( function( el ) { return el.className === 'menu-list-row' } );
	check( 'a row for each menu with its key, its entries and, for a key of the kernel, the badge that says it replaces the navigation', rows.length === 2 && textOf( rows[0] ).indexOf( 'mainEntries: 2Replaces' ) === 0 && textOf( rows[1] ) === 'sideEntries: 1Delete'
		&& w.all( function( el ) { return el.classList.contains('nino-admin-badge') } ).length === 1 );

	const choice = w.control( 'Menu of the kernel\'s navigation' );
	check( 'the form of a new menu offers the keys of the kernel\'s navigations that have no menu, and a key of its own', choice !== null && choice.children.map( function( option ) { return option.value } ).join() === ',footer' );
}

console.log( '\nNino.admin.menu - a new menu\n' );

{
	const w = listed('en_US');
	const form = w.all( function( el ) { return el.tagName === 'FORM' } )[0];
	const key = w.control('Key');
	const choice = w.control( 'Menu of the kernel\'s navigation' );
	const message = function() { return textOf( w.all( function( el ) { return el.className === 'nino-admin-hint' && el.parentNode === form } )[0] ) };
	const submit = function() { fire( form, 'submit' ) };
	const fire = kernel.fire;

	key.value = 'Not A Slug';
	submit();
	check( 'a key that is no slug is said at the form and asks for nothing', message() === 'A key is lower case words joined by hyphens, starting with a letter, up to 32 characters.' && w.requests.length === 1 );

	key.value = 'side';
	submit();
	check( '...a key of a menu that is there is said too', message() === 'There is a menu "side" already.' && w.requests.length === 1 );

	choice.value = 'footer';
	fire( choice, 'change' );
	check( 'a key of the kernel chosen from the select takes the place of the text field', key.disabled === true );

	submit();
	check( '...and opens the editor with the registry asked for first - and no menu to load, there is none', w.last('menu/registry') !== null && w.last('menu/load') === null );

	w.answer( 'menu/registry', 200, REGISTRY );
	check( 'the editor is shown in place of the list, the key as its title, empty, with the notice that it stands in for the kernel\'s navigation',
		w.hidden( w.editor ) === false && w.hidden( w.list ) === true && textOf( w.all( function( el ) { return el.className === 'menu-editor-title' } )[0] ) === 'footer'
		&& w.all( function( el ) { return el.classList.contains('nino-admin-notice') } ).length === 1 && w.all( function( el ) { return el.className === 'nino-admin-empty' } ).length === 1 );
	check( '...it holds unsaved input from the start: nobody has saved this menu', w.entry().isDirty() === true );
}

console.log( '\nNino.admin.menu - the editor\n' );

{
	const w = opened( 'en_US' );
	const fire = kernel.fire;

	check( 'an existing menu is asked for after the registry, and drawn: one entry, its address, its text in the working language, nothing unsaved',
		w.requests.map( function( request ) { return request.action } ).join() === 'menu/list,menu/registry,menu/load' && w.requests[2].payload.key === 'side'
		&& w.control('Address').value === 'https://example.com' && w.control('Text in English').value === 'Docs' && w.entry().isDirty() === false );
	check( '...the panel of the entry is on, with its columns, and closed to begin with', w.control('Columns').value === '2' && w.button('Edit the panel') !== null && w.all( function( el ) { return el.className === 'menu-columns' } ).length === 0 );

	fire( w.button('Edit the panel'), 'click' );
	check( 'the panel is opened to its columns side by side, each with its items and a button to add one', w.all( function( el ) { return el.className === 'menu-columns' } )[0].dataset.columns === '2'
		&& w.all( function( el ) { return el.className === 'menu-column' } ).length === 2 && w.buttons('+ Item').length === 2 && w.button('Close the panel') !== null );
	check( '...the items are a title and a picture; the title shows its text', w.control('Text in English', 1).value === 'Read' && w.control('Item').value === 'title' );

	const text = w.control('Text in English');
	text.value = 'The docs';
	fire( text, 'input' );
	check( 'a text typed marks the menu unsaved and goes into the texts of the working language', w.entry().isDirty() === true && w.line().dataset.state === 'dirty' );

	fire( w.button('Save'), 'click' );
	const sent = w.last('menu/save');
	check( 'Save posts the whole menu in one request: the key, the entries, the texts of the keys they name', sent.payload.key === 'side' && sent.payload.entries.length === 1 && sent.payload.entries[0].panel.cols[0].length === 2
		&& sent.payload.texts['/feature/menu/side/docs'].en_US === 'The docs' && sent.payload.texts['/feature/menu/side/docs'].de_DE === 'Doku' && w.line().dataset.state === 'saving' );

	w.answer( 'menu/save', 200, { key : 'side', entries : 1, made : { keys : [], slots : [] } } );
	check( '...the line says saved, nothing is left unsaved, the Text and Images panels are told, and the list is asked for again', w.line().dataset.state === 'saved' && w.entry().isDirty() === false && w.touched.join() === 'texts,slots' && w.requests.filter( function( request ) { return request.action === 'menu/list' } ).length === 2 );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const told = [];

	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 'menu/save', 400, { error : 'x', code : 'menu_invalid', params : [ 'docs, blog' ] } );
	check( 'a save that is refused is said in the sentence of its code with the entries named, ends in done( false ), and tells nobody that something was saved', w.line().dataset.state === 'error'
		&& textOf( w.line() ) === 'These entries are not valid: docs, blog. Their ids, addresses and keys are checked on saving.' && told.join() === 'false' && w.touched.length === 0 );

	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 'menu/save', 403, { error : 'x', code : 'menu_permission', params : [ '/_admin/keys/manage' ] } );
	check( '...so is the permission that is missing, by name', textOf( w.line() ).indexOf( '/_admin/keys/manage' ) !== -1 && told.join() === 'false,false' );

	fire( w.button('Edit the panel'), 'click' );
	const columns = w.control('Columns');
	columns.value = '3';
	fire( columns, 'change' );
	check( 'the number of columns is changed at once: the panel is drawn with three, the menu is unsaved', w.all( function( el ) { return el.className === 'menu-column' } ).length === 3 && w.entry().isDirty() === true );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;

	fire( w.button('Edit the panel'), 'click' );
	const add = w.buttons('+ Item')[1];
	fire( add, 'click' );
	fire( w.button('Save'), 'click' );
	check( 'an item added to a column is a title called what no item is called, and is in the next save', w.last('menu/save').payload.entries[0].panel.cols[1][0].key === '/feature/menu/side/docs-3' );

	const kind = w.control('Item', 2);
	kind.value = 'html';
	fire( kind, 'change' );
	const area = w.all( function( el ) { return el.tagName === 'TEXTAREA' } )[0];
	area.value = '<p class="x">Own</p>';
	fire( area, 'input' );
	fire( w.button('Save'), 'click' );
	check( 'an item changed to HTML+ is a textarea, and what is typed in it is posted as it was typed', w.last('menu/save').payload.entries[0].panel.cols[1][0].html === '<p class="x">Own</p>' && w.last('menu/save').payload.entries[0].panel.cols[1][0].kind === 'html' );

	const up = w.all( function( el ) { return el.dataset.focus === 'e0.up' } )[0];
	check( 'the buttons that move an entry are there, the first one cannot go up, and an entry that is removed takes its row with it', up.disabled === true );
	fire( w.all( function( el ) { return el.dataset.focus === 'e0.remove' } )[0], 'click' );
	check( '...the list is empty and says so', w.all( function( el ) { return el.className === 'menu-entry nino-admin-card' } ).length === 0 && w.all( function( el ) { return el.className === 'nino-admin-empty' } ).length === 1 );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;

	fire( w.button('+ Entry'), 'click' );
	const entries = w.all( function( el ) { return el.className === 'menu-entry nino-admin-card' } );
	check( 'an entry added is a page of the site - the first the registry knows - and takes the focus on its first button', entries.length === 2 && w.control('Page', 0) !== null && w.control('Page', 0).value === '/about' );

	const kind = w.control('Entry is', 1);
	kind.value = 'link';
	fire( kind, 'change' );
	check( 'the kind is changed to an address: its fields are the address and where it opens, the entry keeps its id', w.control('Address', 1) !== null && w.control('Opens', 1) !== null );

	const label = w.control('Text in English', 1);
	label.value = 'More';
	fire( label, 'input' );
	fire( w.button('Save'), 'click' );
	check( '...and its text is posted under the key made from its id', w.last('menu/save').payload.entries[1].kind === 'link' && w.last('menu/save').payload.entries[1].label === '/feature/menu/side/about'
		&& w.last('menu/save').payload.texts['/feature/menu/side/about'].en_US === 'More' );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const language = w.all( function( el ) { return el.tagName === 'SELECT' && el.classList.contains('nino-admin-locale-select') } )[0];

	language.value = 'de_DE';
	fire( language, 'change' );
	check( 'the language select switches the texts the editor shows: the same entry, the text it has in the other language', w.control('Text in Deutsch').value === 'Doku' && w.control('Text in English') === null );

	const text = w.control('Text in Deutsch');
	text.value = 'Doku neu';
	fire( text, 'input' );
	fire( w.button('Save'), 'click' );
	check( '...and what is typed there is posted for that language, the other untouched', w.last('menu/save').payload.texts['/feature/menu/side/docs'].de_DE === 'Doku neu' && w.last('menu/save').payload.texts['/feature/menu/side/docs'].en_US === 'Docs' );
}

{
	const w = opened( 'en_US', { key : 'side', kernel : false, entries : [ { id : 'about', kind : 'page', uri : '/about', label : '', panel : null } ], texts : {}, taken : [], problems : [] } );
	const fire = kernel.fire;
	const placeholder = function() { return w.control('Text in English') !== null ? w.control('Text in English').placeholder : w.control('Text in Deutsch').placeholder };
	const before = placeholder();
	const language = w.all( function( el ) { return el.tagName === 'SELECT' && el.classList.contains('nino-admin-locale-select') } )[0];

	language.value = 'de_DE';
	fire( language, 'change' );
	check( 'the empty label of a page shows the name the page has in the language of the editor as its placeholder', before === 'About' && placeholder() === 'Über uns' );

	const label = w.control('Text in Deutsch');
	label.value = 'Wir';
	fire( label, 'input' );
	fire( w.button('Save'), 'click' );
	check( '...and a label typed for it gives the page the key of its entry, which an empty one does not', w.last('menu/save').payload.entries[0].label === '/feature/menu/side/about' && w.last('menu/save').payload.texts['/feature/menu/side/about'].de_DE === 'Wir' );
	label.value = '';
	fire( label, 'input' );
	fire( w.button('Save'), 'click' );
	check( '...and one that is emptied again leaves the page with its name, no key', w.last('menu/save').payload.entries[0].label === '' && Object.keys( w.last('menu/save').payload.texts ).length === 0 );
}

{
	const w = opened( 'en_US', { key : 'side', kernel : false, entries : [ { id : 'about', kind : 'page', uri : '/about', label : '', panel : null } ], texts : {}, taken : [], problems : [] } );
	const fire = kernel.fire;
	const input = w.all( function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' } )[0];

	input.checked = true;
	fire( input, 'change', { target : input } );
	check( 'the switch of the panel turns it on and opens it to its one empty column, with the number of columns to choose and a button that closes it', w.control('Columns').value === '1'
		&& w.all( function( el ) { return el.className === 'menu-column' } ).length === 1 && w.button('Close the panel') !== null && w.entry().isDirty() === true );

	fire( w.button('Save'), 'click' );
	check( '...and the save has the panel with one column', JSON.stringify( w.last('menu/save').payload.entries[0].panel ) === '{"columns":1,"cols":[[]]}' );

	const off = w.all( function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' } )[0];
	off.checked = false;
	fire( off, 'change', { target : off } );
	fire( w.button('Save'), 'click' );
	check( 'switched off it is gone: no columns, no button, and the save has none', w.all( function( el ) { return el.className === 'menu-column' } ).length === 0 && w.button('Close the panel') === null && w.last('menu/save').payload.entries[0].panel === null );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const kind = w.control('Entry is');

	kind.value = 'page';
	fire( kind, 'change' );
	fire( w.button('Save'), 'click' );

	const sent = w.last('menu/save').payload.entries[0];
	check( 'an entry changed from an address to a page keeps its id, its panel with its items, and the text it has - which is then the label of the page', sent.kind === 'page' && sent.id === 'docs' && sent.uri === '/about' && sent.panel.cols[0].length === 2
		&& sent.label === '/feature/menu/side/docs' && w.last('menu/save').payload.texts['/feature/menu/side/docs'].en_US === 'Docs' );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const language = w.all( function( el ) { return el.tagName === 'SELECT' && el.classList.contains('nino-admin-locale-select') } )[0];

	fire( w.button('Edit the panel'), 'click' );
	language.value = 'de_DE';
	fire( language, 'change' );
	fire( w.button('Close the panel'), 'click' );
	fire( w.button('Edit the panel'), 'click' );
	check( 'a key that has no text in the language of the editor shows the text of another language as its placeholder, as the kernel\'s fills show it on the page',
		w.control('Text in Deutsch', 0).value === 'Doku' && w.control('Text in Deutsch', 1).value === '' && w.control('Text in Deutsch', 1).placeholder === 'Read' );
}

{
	const only = JSON.parse( JSON.stringify( SIDE ) );
	delete only.texts['/feature/menu/side/docs'].de_DE;

	const w = opened( 'en_US', only );
	const language = w.all( function( el ) { return el.tagName === 'SELECT' && el.classList.contains('nino-admin-locale-select') } )[0];

	check( 'the label of an entry shows what the language it has none in says, and in the language it is typed in the text of the other one, never its own', w.control('Text in English').placeholder === '' && ( language.value = 'de_DE', kernel.fire( language, 'change' ), w.control('Text in Deutsch').placeholder === 'Docs' ) );
	check( '...so that a field that is emptied is not left with its own old text as its hint', ( function() { const w2 = opened('en_US'); return w2.control('Text in English').value === 'Docs' && w2.control('Text in English').placeholder === 'Doku' } )() );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const text = w.control('Text in English');

	text.value = '';
	fire( text, 'input' );
	fire( w.button('Save'), 'click' );
	check( 'a text that is cleared is posted as an empty text, so that the server clears it: the language stays in the texts of its key, empty, and the other language is as it was', w.entry().isDirty() === true
		&& w.last('menu/save').payload.texts['/feature/menu/side/docs'].en_US === '' && w.last('menu/save').payload.texts['/feature/menu/side/docs'].de_DE === 'Doku' );
}

{
	const w = listed('en_US');
	const form = w.all( function( el ) { return el.tagName === 'FORM' } )[0];

	w.control('Key').value = 'panel';
	kernel.fire( form, 'submit' );
	check( 'the key panel is refused for a menu: it is the word of the toggle\'s key', w.last('menu/registry') === null );
}

console.log( '\nNino.admin.menu - the pictures\n' );

{
	const w = opened('en_US');
	const fire = kernel.fire;

	fire( w.button('Edit the panel'), 'click' );
	const input = w.all( function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];
	input.files = [ { size : 12, name : 'a.png' } ];
	fire( input, 'change' );

	check( 'a picture for a slot that is not there makes the slot first - in the size the registry gives, called by the name of the item - and then posts the file to the Images panel',
		w.last('slots/create').payload.uri === '/feature/menu/side/docs-2' && w.last('slots/create').payload.width === 600 && w.last('slots/create').payload.height === 400 && w.last('images/upload') === null );

	w.answer( 'slots/create', 200, { ok : true } );
	check( '...the upload carries the file and the uri of the slot', w.last('images/upload').payload.uri === '/feature/menu/side/docs-2' && w.last('images/upload').extra.file.name === 'a.png' );

	w.answer( 'images/upload', 200, { filename : 'x.webp', url : '/public/images/x.webp' } );
	const thumb = w.all( function( el ) { return el.className === 'menu-picture-thumb' } )[0];
	check( '...and the picture is shown with what was stored said under it; the Images panel is told', thumb.hidden === false && thumb.src.indexOf('/public/images/x.webp') === 0
		&& textOf( w.all( function( el ) { return el.classList.contains('menu-picture-message') } )[0] ) === 'The picture is stored.' && w.touched.indexOf('slots') !== -1 );

	const alt = w.control('Alt text in English');
	check( 'the alt text can be written once there is a picture, and goes to the slot at once - through the Images panel', alt.disabled === false );
	alt.value = 'A workshop';
	fire( alt, 'change' );
	check( '...as the Images panel takes it', w.last('images/alt').payload.uri === '/feature/menu/side/docs-2' && w.last('images/alt').payload.alt.en_US === 'A workshop' );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;

	w.Nino.adminUi.checkImage = function( file, done ) { done( { code : 'image_too_large', params : [ 2 ] } ) };
	fire( w.button('Edit the panel'), 'click' );
	const input = w.all( function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];
	input.files = [ { size : 12 } ];
	fire( input, 'change' );
	check( 'a picture the browser can tell is too large for the server is refused without a request', w.last('slots/create') === null && w.last('images/upload') === null );
}

console.log( '\nNino.admin.menu - a kernel menu, leaving and deleting\n' );

{
	const w = listed('en_US');
	const fire = kernel.fire;

	w.all( function( el ) { return el.dataset.key === 'main' } )[0].listeners.click[0]();
	w.answer( 'menu/registry', 200, REGISTRY );
	w.answer( 'menu/load', 200, { key : 'main', kernel : true, entries : [ { id : 'about', kind : 'page', uri : '/about', label : '', panel : null } ], texts : {}, taken : [], problems : [] } );

	fire( w.button('Take the pages of the kernel\'s menu "main"'), 'click' );
	fire( w.button('Save'), 'click' );
	check( 'the pages of the kernel\'s menu are taken over: those the menu does not have yet are added, in the order the navigation lists them, the ones it has stay',
		w.last('menu/save').payload.entries.map( function( entry ) { return entry.uri } ).join() === '/about,/contact,/home' && w.last('menu/save').payload.entries.map( function( entry ) { return entry.id } ).join() === 'about,contact,home' );

	w.answer( 'menu/save', 200, { key : 'main', entries : 3, made : { keys : [], slots : [] } } );
	fire( w.top.querySelector('.nino-admin-back-link'), 'click', { preventDefault : function() {} } );
	check( 'the back link of a saved menu leaves it without a question', w.guards.length === 0 && w.hidden( w.editor ) === true && w.hidden( w.list ) === false );
}

{
	const w = opened('en_US');
	const fire = kernel.fire;
	const text = w.control('Address');

	text.value = 'https://example.org';
	fire( text, 'input' );
	fire( w.top.querySelector('.nino-admin-back-link'), 'click', { preventDefault : function() {} } );
	check( 'the back link of a menu with unsaved input asks the shell, which is told the panel\'s uri', w.guards.length === 1 && w.guards[0].join() === 'menu' );
}

{
	const w = listed('en_US');
	const fire = kernel.fire;

	fire( w.buttons('Delete')[1], 'click' );
	check( 'delete asks a question of the weight that is asked in both modes, naming the menu', w.asked.length === 1 && w.asked[0].level === 'destructive' && w.asked[0].options.message.indexOf( '"side"' ) !== -1 && w.last('menu/delete') === null );

	w.asked[0].options.onChoose( false );
	check( '...a No deletes nothing', w.last('menu/delete') === null );

	w.asked[0].options.onChoose( true );
	check( '...a Yes posts menu/delete with the key, and the list is asked for again once it is done', w.last('menu/delete').payload.key === 'side' );

	w.answer( 'menu/delete', 200, { key : 'side' } );
	check( '...so that the row is gone', w.last('menu/list') !== null && w.requests.filter( function( request ) { return request.action === 'menu/list' } ).length === 2 );
}

console.log( '\nNino.admin.menu - the words, in both languages\n' );

[ 'en_US', 'de_DE' ].forEach( function( locale ) {

	const w = opened( locale );
	const fire = kernel.fire;

	fire( w.button( locale === 'en_US' ? 'Edit the panel' : 'Panel bearbeiten' ), 'click' );
	fire( w.buttons( locale === 'en_US' ? '+ Item' : '+ Baustein' )[0], 'click' );
	fire( w.button( locale === 'en_US' ? '+ Entry' : '+ Eintrag' ), 'click' );
	fire( w.button( locale === 'en_US' ? 'Save' : 'Speichern' ), 'click' );
	w.answer( 'menu/save', 400, { error : 'x', code : 'menu_texts', params : [] } );

	const list = listed( locale );

	check( locale+ ': the sentence of the code menu_texts is told in the language', textOf( w.line() ) === ( locale === 'en_US'
		? 'The texts are for the keys of this menu, in the languages of the site, one line of at most 300 characters each.'
		: 'Die Texte gehören zu den Schlüsseln dieses Menüs, in den Sprachen der Website, je eine Zeile mit höchstens 300 Zeichen.' ) );
	check( locale+ ': every word the script asked for is in the text files', w.missing.length === 0 && list.missing.length === 0 );
} );

{
	const w = panelWorld('en_US');
	w.panel.showCurrent();
	w.answer( 'menu/list', 500, null );
	check( 'a list that cannot be loaded is said in the mount, in the panel\'s sentence', textOf( w.list ) === '(500) It could not be loaded.' );
}

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
