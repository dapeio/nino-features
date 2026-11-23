/**
 *	Nino
 *	menu-js-smoke.js	What menu.js does over a stand-in of a page: that the button beside an entry turns its panel and says
 *										so in aria-expanded, that a second panel closes the first, that Escape closes the panel and hands
 *										the focus to the button, that the focus or a press outside closes it, that a mouse that rests on an
 *										entry opens it after a delay and leaves it after another while a keyboard that is in it keeps it
 *										open, that a dropdown that would cross the edge of the window is moved back by the difference, and
 *										that a panel that is no dropdown (the accordion of a phone) is left where it is. The panel script is
 *										run by the PHP suite; the structure of it is pinned there.
 *
 *										No jsdom, no dependency: the element stand-in of the kernel's harness. NINO_ROOT names the
 *										checkout, else it is three levels up, where a project keeps it.
 *
 *	Usage: node features/Menu/tests/menu-js-smoke.js
 *	       NINO_ROOT=../nino node features/Menu/tests/menu-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const checkout = process.env.NINO_ROOT || path.join( __dirname, '../../..' );
const { check, done, element, fire, findAll } = require( path.join( checkout, 'tests/harness.js' ) );

const source = fs.readFileSync( path.join( __dirname, '../assets/menu.js' ), 'utf8' );

/**
 *	A page with one menu of two entries that have a panel and one that has none, and the script loaded over it
 *
 *	@param		{Object}	[options]		{ dropdown } whether the stylesheet makes the panels dropdowns, { width } the width of the window
 */
function page( options ) {

	options = Object.assign( { dropdown : true, width : 1000 }, options || {} );

	const nav = element( 'nav' );
	const list = element( 'ul' );
	const items = [];
	const timers = [];
	const documentListeners = {};
	const captured = {};
	// Whether the menu is open - its list drawn: a panel of a menu that is shut has no box
	const shown = { menu : true };
	// What stands in the top layer ({ layer }) - modal says whether it is a modal dialog -, and a browser that does not know :popover-open: it throws at that selector and answers ':modal' alone
	const top = { layer : null, modal : false, throws : false };

	nav.className = 'menu';
	nav.appendChild( list );

	[ 'a', 'b', 'plain' ].forEach( function( id ) {

		const item = element( 'li' );
		const link = element( 'a' );

		item.className = id === 'plain' ? 'menu-item' : 'menu-item menu-item--panel';
		link.className = 'menu-link';
		item.appendChild( link );
		list.appendChild( item );

		if( id !== 'plain' ) {

			const toggle = element( 'button' );
			const panel = element( 'div' );
			const inner = element( 'a' );

			toggle.className = 'menu-toggle';
			toggle.setAttribute( 'aria-expanded', 'false' );
			panel.className = 'menu-panel';
			panel.box = { left : 100, right : 500 };
			panel.style.removeProperty = function( name ) { delete panel.style[name] };
			panel.getClientRects = function() { return shown.menu === true && open( item ) === true ? [ { width : 1 } ] : [] };
			panel.getBoundingClientRect = function() {
				const shift = parseFloat( panel.style['--menu-shift'] ?? '0' );
				return { left : panel.box.left + shift, right : panel.box.right + shift };
			};
			inner.className = 'menu-link menu-link--sub';
			panel.appendChild( inner );
			item.appendChild( toggle );
			item.appendChild( panel );
			item.toggle = toggle;
			item.panel = panel;
			item.inner = inner;
			// The pointer has to be told apart from the keyboard: a mouse's enter and leave
			item.enter = function() { fire( item, 'pointerenter', { pointerType : 'mouse' } ) };
			item.leave = function() { fire( item, 'pointerleave', { pointerType : 'mouse' } ) };
			items.push( item );
		}
	} );

	const outside = element( 'a' );
	const body = element( 'body' );

	body.appendChild( nav );
	body.appendChild( outside );

	const document = {
		querySelectorAll : function( selector ) { return selector === '.menu' ? [ nav ] : [] },
		querySelector : function( selector ) {
			if( selector === ':modal, :popover-open:not([popover=manual])' ) {
				if( top.throws === true )
					throw new SyntaxError( selector+ ' is no selector' );
				return top.layer;
			}
			return selector === ':modal' ? ( top.modal === true ? top.layer : null ) : null;
		},
		addEventListener : function( type, fn, capture ) { ( documentListeners[type] = documentListeners[type] || [] ).push( fn ); captured[type] = capture === true },
		get activeElement() { return findAll( body, function( node ) { return node.focused === true } )[0] ?? null },
		documentElement : { clientWidth : options.width },
	};

	const context = {
		document : document,
		window : {
			getComputedStyle : function( node ) { return { position : options.dropdown === true && node.className === 'menu-panel' ? 'absolute' : 'static' } },
			addEventListener : function( type, fn ) { ( documentListeners['window:'+ type] = documentListeners['window:'+ type] || [] ).push( fn ) },
		},
		Nino : { events : { bindCallback : function( name, fn ) { fn() } } },
		setTimeout : function( fn, time ) { timers.push( { fn : fn, time : time } ); return timers.length },
		clearTimeout : function( id ) { if( timers[ id - 1 ] ) timers[ id - 1 ].fn = null },
		parseFloat : parseFloat,
	};

	vm.runInNewContext( source, context, { filename : 'menu.js' } );

	return {
		nav : nav,
		items : items,
		outside : outside,
		fire : function( type, event, target ) {
			const sent = Object.assign( { target : target || outside, defaultPrevented : false, preventDefault : function() { sent.defaultPrevented = true } }, event || {} );
			( documentListeners[type] || [] ).forEach( function( fn ) { fn( sent ) } );
			return sent;
		},
		captured : captured,
		shown : shown,
		top : top,
		resize : function() { ( documentListeners['window:resize'] || [] ).forEach( function( fn ) { fn() } ) },
		// What waits for its time is let go, in the order it was asked for
		wait : function() { timers.splice( 0 ).forEach( function( timer ) { if( timer.fn !== null ) timer.fn() } ) },
		timers : timers,
	};
}


const open = function( item ) { return item.toggle.getAttribute( 'aria-expanded' ) === 'true' };

let q = page();
check( 'the script marks each menu as started (menu--ready), which is what folds the panels: a script that never gets here leaves them open', q.nav.classList.contains( 'menu--ready' ) === true );

q = page();
q.items[0].enter();
q.wait();
fire( q.items[0].toggle, 'click' );
check( 'a press on the button of a panel that the mouse opened by resting on it holds it open', open( q.items[0] ) === true );
fire( q.items[0].toggle, 'click' );
check( '...and the next press closes it', open( q.items[0] ) === false );

let p = page();
let [ a, b ] = p.items;

a.toggle.focus();
fire( a.toggle, 'click' );
check( 'a press on the button opens the panel and says so in aria-expanded', open( a ) === true );
fire( a.toggle, 'click' );
check( 'a second press closes it', open( a ) === false );

fire( a.toggle, 'click' );
fire( b.toggle, 'click' );
check( 'a second panel closes the first: one is open at a time', open( b ) === true && open( a ) === false );

a.inner.focus();
p.fire( 'keydown', { key : 'Escape' } );
check( 'Escape closes the open panel', open( b ) === false );

fire( a.toggle, 'click' );
a.inner.focus();
p.fire( 'keydown', { key : 'Escape' } );
check( 'Escape closes a panel the focus was in and hands the focus to its button', open( a ) === false && a.toggle.focused === true );

fire( a.toggle, 'click' );
p.outside.focus();
p.fire( 'focusin', {}, p.outside );
check( 'the focus that leaves the entry - the Tab key walks on - closes it', open( a ) === false );

fire( a.toggle, 'click' );
p.fire( 'focusin', {}, a.inner );
check( 'the focus that moves into the panel of the entry keeps it open', open( a ) === true );
p.fire( 'pointerdown', {}, p.outside );
check( 'a press outside the entry closes it', open( a ) === false );

p = page();
[ a, b ] = p.items;
a.enter();
check( 'a mouse that rests on an entry opens its panel after a delay and not before', open( a ) === false && p.timers.length === 1 && p.timers[0].time > 0 );
p.wait();
check( '...and the panel is open once the delay has run', open( a ) === true );
a.leave();
check( 'a mouse that leaves the entry and the panel closes it after a delay and not before', open( a ) === true && p.timers.length === 1 );
p.wait();
check( '...and the panel is closed once the delay has run', open( a ) === false );

a.enter();
a.leave();
p.wait();
check( 'a panel the pointer has left before the delay has run is not opened', open( a ) === false );

fire( a, 'pointerenter', { pointerType : 'touch' } );
check( 'a touch opens nothing by itself: the hover only adds to the button', p.timers.length === 0 && open( a ) === false );

fire( a.toggle, 'click' );
a.inner.focus();
a.leave();
p.wait();
check( 'an entry that has the keyboard in it stays open when the mouse passes over and away', open( a ) === true );

p = page( { width : 700 } );
[ a ] = p.items;
a.panel.box = { left : 400, right : 800 };
fire( a.toggle, 'click' );
check( 'a dropdown that would cross the right edge of the window is moved back by the difference, with room to spare', a.panel.style['--menu-shift'] === '-108px' );
p.items[1].panel.box = { left : -50, right : 300 };
fire( p.items[1].toggle, 'click' );
check( '...and one that would cross the left edge is moved in', p.items[1].panel.style['--menu-shift'] === '58px' );

p = page( { dropdown : false } );
[ a, b ] = p.items;
a.panel.box = { left : 400, right : 800 };
fire( a.toggle, 'click' );
a.enter();
check( 'a panel that is no dropdown - the accordion of a small screen - is turned by the button, is not moved and does not open on hover', open( a ) === true && a.panel.style['--menu-shift'] === undefined && p.timers.length === 0 );

p = page( { dropdown : false } );
[ a ] = p.items;
fire( a.toggle, 'click' );
p.fire( 'pointerdown', {}, p.outside );
p.fire( 'focusin', {}, p.outside );
check( 'an accordion is not closed by a press or the focus outside: the list would move under the pointer before the click lands', open( a ) === true );

p = page();
[ a ] = p.items;
fire( a.toggle, 'click' );
p.items[1].panel.box = { left : 100, right : 500 };
p.resize();
check( 'a resize that leaves the shape as it was keeps the panel open', open( a ) === true );

// --- Escape, with the script of the header (the overlay: an accordion under the entry) -------------------------------------------------

p = page( { dropdown : false } );
[ a, b ] = p.items;
check( 'Escape is heard in the capture phase: before the script of the header, which listens as the page does, so that the key goes to the panel first', p.captured.keydown === true );
fire( a.toggle, 'click' );
a.inner.focus();
let sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'an accordion that is shown takes the Escape - it closes, the focus goes to its button, and preventDefault says the key is taken, so that the header leaves the menu open', open( a ) === false && a.toggle.focused === true && sent.defaultPrevented === true );
sent = p.fire( 'keydown', { key : 'Escape' } );
check( '...and the next Escape, with no panel open, is not taken: it is the header\'s', sent.defaultPrevented === false && open( a ) === false );

fire( b.toggle, 'click' );
p.outside.focus();
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'an accordion takes the Escape when the focus is not in it - on the page, after a press on the ground of the overlay - as well: the panel is the first to go', open( b ) === false && sent.defaultPrevented === true );

fire( a.toggle, 'click' );
p.shown.menu = false;
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'an accordion of a menu that is shut is closed by Escape and the key is not taken: nobody sees it, and the Escape belongs to whoever is above (a dialog)', open( a ) === false && sent.defaultPrevented === false );

// A menu that lies behind an overlay - the one of the footer while the menu of the header is open - is inert: its panel is closed and the key is left to the overlay
p = page( { dropdown : false } );
[ a, b ] = p.items;
fire( a.toggle, 'click' );
p.nav.setAttribute( 'inert', '' );
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'an accordion of a menu that is inert - behind the overlay of another menu - is closed by Escape and the key is not taken: the overlay in front is the one the key closes', open( a ) === false && sent.defaultPrevented === false );

p = page();
[ a ] = p.items;
fire( a.toggle, 'click' );
a.inner.focus();
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'a dropdown closes with Escape as it did, and the key is not taken: there is no overlay of the header to leave', open( a ) === false && sent.defaultPrevented === false );


// What stands in the top layer has the Escape before the panel - and the script of the header leaves it to it as well
p = page( { dropdown : false } );
[ a ] = p.items;
fire( a.toggle, 'click' );
p.top.layer = element( 'dialog' );
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'a modal dialog or a popover that closes with Escape (in the header, over the panel) has the key: the panel stays open, the key is not taken', open( a ) === true && sent.defaultPrevented === false );
p.top.layer = null;
sent = p.fire( 'keydown', { key : 'Escape' } );
check( '...and once it is gone the Escape is the panel\'s', open( a ) === false && sent.defaultPrevented === true );

p = page( { dropdown : false } );
[ a ] = p.items;
fire( a.toggle, 'click' );
p.top.throws = true;
p.top.layer = element( 'dialog' );
p.top.modal = true;
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'a browser that does not know :popover-open is asked for the modal dialog alone: one that is open has the key', open( a ) === true && sent.defaultPrevented === false );
p.top.layer = null;
sent = p.fire( 'keydown', { key : 'Escape' } );
check( '...and with none open the Escape is the panel\'s', open( a ) === false && sent.defaultPrevented === true );

p = page( { dropdown : false } );
[ a ] = p.items;
fire( a.toggle, 'click' );
p.top.layer = null;
sent = p.fire( 'keydown', { key : 'Escape' } );
check( 'a manual popover - a toast, which Escape does not close - is no layer: the panel has the key (the stand-in answers none for it, as :not( [popover=manual] ) does)', open( a ) === false && sent.defaultPrevented === true );

sent = p.fire( 'keydown', { key : 'a' } );
check( 'another key is nobody\'s', sent.defaultPrevented === false );

// --- the two scripts of the page: the panels (menu.js) and the page behind an overlay (header-nav.js of the kernel) -------------------------------------------------
// Both listen to Escape on the document. A kernel that has the mark data-header-menu holds a menu of this feature as it holds its own; one that has not leaves it alone and the panels are what they were

// Basis is required by the feature and stands where the kernel says: below features/
const basisDir = require( 'child_process' ).execFileSync( 'php', [ '-r', 'require $argv[1]. "/_nino/Nino.php"; $app = []; echo \\Nino\\Features::get( $app, "basis" )["dir"] ?? "";', checkout ], { encoding : 'utf8' } ).trim();
const headerSource = fs.readFileSync( path.join( basisDir, 'assets/header-nav.js' ), 'utf8' );
const { matches } = require( path.join( checkout, 'tests/harness.js' ) );

/**
 *	A header with the menu of this feature, an overlay as the stylesheet of the header makes it where the menu is open, the page beside it, and the two scripts started over it in the order
 *	asked for: the document has capturing and bubbling listeners, called in the order the browser calls them
 *
 *	@param		{string}	order			'panels first' | 'header first': which script is started first
 */
function together( order ) {

	const body = element( 'body' );
	const aside = element( 'aside' );
	const main = element( 'main' );
	const header = element( 'header' );
	const inner = element( 'div' );
	const nav = element( 'nav' );
	const details = element( 'details' );
	const summary = element( 'summary' );
	const list = element( 'ul' );
	const item = element( 'li' );
	const link = element( 'a' );
	const toggle = element( 'button' );
	const panel = element( 'div' );
	const sub = element( 'a' );
	const listeners = [];
	const stored = {};
	// What stands in the top layer, as the browser answers for a modal dialog or a popover that closes with Escape
	const layer = { node : null };
	const root = element( 'html' );
	const all = [ body, aside, main, header, inner, nav, details, summary, list, item, link, toggle, panel, sub ];

	all.forEach( function( node ) { if( typeof node.matches !== 'function' ) node.matches = function( selector ) { return matches( node, selector ) } } );
	header.className = 'header-wrap';
	nav.className = 'menu';
	details.className = 'menu-details';
	details.setAttribute( 'data-header-menu', '' );
	summary.className = 'menu-burger';
	list.className = 'menu-list';
	item.className = 'menu-item menu-item--panel';
	link.className = 'menu-link';
	toggle.className = 'menu-toggle';
	toggle.setAttribute( 'aria-expanded', 'false' );
	panel.className = 'menu-panel';
	sub.className = 'menu-link menu-link--sub';
	Object.defineProperty( details, 'open', {
		get : function() { return details.hasAttribute( 'open' ) },
		set : function( value ) { value === true ? details.setAttribute( 'open', '' ) : details.removeAttribute( 'open' ) },
	} );
	header.checkVisibility = function() { return true };
	// What the browser draws: a panel has a box where the menu is open and the panel is
	panel.getClientRects = function() { return details.open === true && toggle.getAttribute( 'aria-expanded' ) === 'true' ? [ { width : 1 } ] : [] };
	panel.getBoundingClientRect = function() { return { left : 0, right : 100 } };
	panel.style.removeProperty = function( name ) { delete panel.style[name] };

	body.appendChild( aside );
	body.appendChild( main );
	body.appendChild( header );
	header.appendChild( inner );
	inner.appendChild( nav );
	nav.appendChild( details );
	details.appendChild( summary );
	details.appendChild( list );
	list.appendChild( item );
	item.appendChild( link );
	item.appendChild( toggle );
	item.appendChild( panel );
	panel.appendChild( sub );

	root.style = {
		// The shorthand overflow is the two long forms: giving either one back with no value takes it away
		setProperty : function( name, value ) { value === '' ? delete stored[ name.indexOf( 'overflow-' ) === 0 ? 'overflow' : name ] : stored[name] = value },
		getPropertyValue : function( name ) { return stored[name] ?? '' },
		getPropertyPriority : function() { return '' },
	};
	root.clientWidth = 1000;
	root.getAttribute = function( name ) { return name === 'style' ? ( Object.keys( stored ).length > 0 ? 'x' : null ) : null };
	root.removeAttribute = function() {};

	const document = {
		documentElement : root,
		body : body,
		querySelectorAll : function( selector ) { return body.querySelectorAll( selector ) },
		querySelector : function( selector ) { return selector.indexOf( ':' ) === -1 ? body.querySelector( selector ) : layer.node },
		addEventListener : function( type, fn, capture ) { listeners.push( { type : type, fn : fn, capture : capture === true } ) },
		get activeElement() { return findAll( body, function( node ) { return node.focused === true } )[0] ?? null },
	};
	const window = {
		innerWidth : 1000,
		scrollX : 0,
		scrollY : 0,
		scrollTo : function() {},
		getComputedStyle : function( node ) { return { position : node === header && details.open === true ? 'fixed' : 'static', display : 'block', backgroundColor : 'rgb(255, 255, 255)' } },
		addEventListener : function() {},
	};
	const starts = [];
	const context = {
		document : document,
		window : window,
		getComputedStyle : window.getComputedStyle,
		MutationObserver : function() { this.observe = function() {}; this.disconnect = function() {} },
		Nino : { events : { bindCallback : function( name, fn ) { starts.push( fn ) } } },
		setTimeout : function() { return 1 },
		clearTimeout : function() {},
		parseFloat : parseFloat,
	};
	Object.assign( window, context );

	const scripts = { panels : source, header : headerSource };

	( order === 'header first' ? [ 'header', 'panels' ] : [ 'panels', 'header' ] ).forEach( function( name ) {
		starts.length = 0;
		vm.runInNewContext( scripts[name], context, { filename : name+ '.js' } );
		starts.forEach( function( fn ) { fn() } );
	} );

	const q = {
		nodes : { aside : aside, main : main, header : header, details : details, summary : summary, toggle : toggle, sub : sub },
		root : root,
		stored : stored,
		// A key as the browser sends it: the listeners of the capture phase, then those of the bubble phase, each in the order they were added
		key : function( key ) {
			const event = { type : 'keydown', key : key, target : body, defaultPrevented : false, preventDefault : function() { event.defaultPrevented = true } };
			[ true, false ].forEach( function( capture ) { listeners.filter( function( one ) { return one.type === 'keydown' && one.capture === capture } ).forEach( function( one ) { one.fn( event ) } ) } );
			return event;
		},
		turn : function( open ) {
			details.open = open;
			const event = { type : 'toggle', target : details };
			listeners.filter( function( one ) { return one.type === 'toggle' } ).forEach( function( one ) { one.fn( event ) } );
		},
		press : function() { fire( toggle, 'click' ) },
		layer : layer,
		inert : function() { return findAll( body, function( node ) { return node.inert === true } ).length },
		panelOpen : function() { return toggle.getAttribute( 'aria-expanded' ) === 'true' },
	};

	return q;
}

[ 'panels first', 'header first' ].forEach( function( order ) {

		const t = together( order );
		t.turn( true );
		const held = t.inert() > 0;
		t.press();
		t.nodes.sub.focus();

		const first = t.key( 'Escape' );
		check( '['+ order+ '] Escape with a panel open: the panel is the first to go - it closes, the focus is its button\'s - and the menu is still open, whether the kernel holds the page or not',
			t.panelOpen() === false && t.nodes.details.open === true && t.nodes.toggle.focused === true && first.defaultPrevented === true );
		check( '['+ order+ '] ...the page stays held after that Escape (nothing was let go by the key that closed the panel)', t.inert() > 0 === held && ( held === false || t.stored.overflow === 'hidden' ) );

		const second = t.key( 'Escape' );

		if( held === true )
			check( '['+ order+ '] ...and the next Escape closes the menu, which is the header\'s: it is closed, the button has the focus, and nothing is held (one script for each key, never both, never none)',
				t.nodes.details.open === false && t.nodes.summary.focused === true && second.defaultPrevented === true && t.inert() === 0 && t.stored.overflow === undefined );
		else
			check( '['+ order+ '] ...the kernel here does not know the mark: it holds nothing, takes no Escape, and the second Escape is nobody\'s - the menu stays as it was', t.inert() === 0 && second.defaultPrevented === false && t.nodes.details.open === true );

		const third = t.key( 'Escape' );
		check( '['+ order+ '] ...and one more Escape is nobody\'s', third.defaultPrevented === false && t.inert() === 0 );

		// A dialog in the top layer over the open panel: it has the Escape, which neither script takes; the panel and the menu wait
		const w = together( order );
		w.turn( true );
		w.press();
		w.layer.node = element( 'dialog' );
		w.nodes.header.appendChild( w.layer.node );
		const over = w.key( 'Escape' );
		check( '['+ order+ '] a dialog or a popover on top of an open panel has the Escape: neither script takes it - the panel and the menu stay as they are', over.defaultPrevented === false && w.panelOpen() === true && w.nodes.details.open === true );
		w.layer.node = null;
		const next = w.key( 'Escape' );
		check( '['+ order+ '] ...and when it has closed the next Escape goes to the panel, as before', next.defaultPrevented === true && w.panelOpen() === false && w.nodes.details.open === true );

		// A panel left open when the menu was shut: the Escape that follows belongs to whoever is on top, not to a panel nobody sees
		const u = together( order );
		u.turn( true );
		u.press();
		u.turn( false );
		const shut = u.key( 'Escape' );
		check( '['+ order+ '] a panel that stayed open in a menu that was shut does not take an Escape (a dialog over the page would lose its key): nothing is prevented, and the panel closes', shut.defaultPrevented === false && u.panelOpen() === false && u.inert() === 0 );
	} );

done();
