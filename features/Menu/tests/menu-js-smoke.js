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
		addEventListener : function( type, fn ) { ( documentListeners[type] = documentListeners[type] || [] ).push( fn ) },
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
			( documentListeners[type] || [] ).forEach( function( fn ) { fn( Object.assign( { target : target || outside }, event || {} ) ) } );
		},
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

done();
