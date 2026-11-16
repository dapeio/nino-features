/**
 *	Nino
 *	collections-js-smoke.js	What the script of the filter does over the kernel's stand-in of a page: a box that holds
 *													rows marked with a value gets a row of buttons - one for all and one for each value, in the
 *													order the box says in data-order, the values it does not name after them in the order they
 *													first stand - and a status; a button hides the rows of the other values with
 *													the attribute hidden, says which button is on with aria-pressed and what is shown in the
 *													status; the first drawing says nothing; a box with one value or none is left as it is; two
 *													boxes do not touch each other's rows; a value is the name of a button as text and never as
 *													markup.
 *
 *													The helpers - check(), done(), findAll(), fire(), element() and documentOf() - are those of
 *													tests/harness.js of the checkout, three levels up or the one NINO_ROOT names.
 *													collections-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Collections/tests/collections-js-smoke.js
 *	       NINO_ROOT=../nino node features/Collections/tests/collections-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, findAll, fire, element, documentOf } = require( path.join( root, 'tests/harness.js' ) );

const script = fs.readFileSync( path.join( __dirname, '../assets/collection-filter.js' ), 'utf8' );

/**
 *	A box of the filter with rows of the given values, each a child of it
 *
 *	@param		{string[]}		values		The data-collection-value of each row
 *	@param		{Object}			attributes	More attributes of the box
 *
 *	@return		{Object}
 */
function box( values, attributes ) {

	const el = element( 'div' );

	el.className = 'collection-filter';
	el.setAttribute( 'data-collection-filter', '' );
	el.setAttribute( 'data-all', 'Alle' );
	el.setAttribute( 'data-shown', '{shown} von {total}' );
	el.setAttribute( 'data-label', 'Filter' );

	Object.keys( attributes || {} ).forEach( function( name ) {
		el.setAttribute( name, attributes[name] );
	} );

	values.forEach( function( value ) {

		const row = element( 'li' );

		row.setAttribute( 'data-collection-value', value );
		el.appendChild( row );
	} );

	return el;
}

/**
 *	The script run over a page that holds the boxes
 *
 *	@param		{Object[]}		boxes
 *	@param		{string}			state			The readyState of the document
 *
 *	@return		{Object}									The mount and the listeners of the document
 */
function run( boxes, state ) {

	const mount = element( 'main' );
	const listeners = {};

	boxes.forEach( function( one ) {
		mount.appendChild( one );
	} );

	const document = Object.assign( documentOf( mount ), {
		readyState : state || 'complete',
		addEventListener : function( name, fn ) {
			listeners[name] = fn;
		},
	} );

	vm.runInNewContext( script, { document : document }, { filename : 'collection-filter.js' } );

	return { mount : mount, listeners : listeners };
}

const buttonsOf = function( one ) { return findAll( one, function( el ) { return el.tagName === 'BUTTON' } ) };
const rowsOf = function( one ) { return findAll( one, function( el ) { return el.tagName === 'LI' } ) };
const statusOf = function( one ) { return findAll( one, function( el ) { return el.getAttribute( 'role' ) === 'status' } )[0] };
const navOf = function( one ) { return findAll( one, function( el ) { return el.getAttribute( 'role' ) === 'group' } )[0] };
const hiddenOf = function( one ) { return rowsOf( one ).map( function( row ) { return row.hidden } ) };
const pressedOf = function( one ) { return buttonsOf( one ).map( function( button ) { return button.getAttribute( 'aria-pressed' ) } ) };

// one button for all and one for each value, in the order the values first stand, a row of buttons that has a name, and a status
const one = box( [ 'Bau', 'Fenster', 'Bau', 'Metall', '', 'Bau' ] );
run( [ one ] );

check( 'a button for all and one for each value, in the order the values first stand - an empty value has none and is no value', buttonsOf( one ).map( function( button ) { return button.textContent } ).join( '|' ) === 'Alle|Bau|Fenster|Metall' );
check( 'every button is a button of its own and not a submit, and has the class the style addresses', buttonsOf( one ).every( function( button ) { return button.type === 'button' && button.className === 'collection-filter-button' } ) );
check( 'the buttons are in a group - a div, no landmark - that has the name the box says, and the status is a status that comes after it - both before the rows', navOf( one ).getAttribute( 'aria-label' ) === 'Filter' && navOf( one ).className === 'collection-filter-nav'
	&& statusOf( one ).className === 'collection-filter-status' && one.children[0] === navOf( one ) && one.children[1] === statusOf( one ) && one.children.length === 8 );
check( 'at the start all is on, every row is shown and the status says nothing', pressedOf( one ).join( '|' ) === 'true|false|false|false' && hiddenOf( one ).every( function( hidden ) { return hidden === false } ) && statusOf( one ).textContent === '' );

// a value: its rows stay, the others are hidden; the button says it is on; the status says how many
fire( buttonsOf( one )[1], 'click' );
check( 'a button for a value hides the rows of the others, and a row with no value too', hiddenOf( one ).join( '|' ) === 'false|true|false|true|true|false' );
check( 'the button of the value is the one that is on, and only that one', pressedOf( one ).join( '|' ) === 'false|true|false|false' );
check( 'the status says how many of how many are shown, in the words of the box', statusOf( one ).textContent === '3 von 6' );
fire( buttonsOf( one )[3], 'click' );
check( 'another value shows the rows of that one and nothing else', hiddenOf( one ).join( '|' ) === 'true|true|true|false|true|true' && pressedOf( one ).join( '|' ) === 'false|false|false|true' && statusOf( one ).textContent === '1 von 6' );
fire( buttonsOf( one )[0], 'click' );
check( 'all shows every row again, and says so', hiddenOf( one ).every( function( hidden ) { return hidden === false } ) && pressedOf( one ).join( '|' ) === 'true|false|false|false' && statusOf( one ).textContent === '6 von 6' );

// the order of the buttons is the one the box says in data-order - the sort of the kernel in the language of the page; a value it does not name follows, in the order the rows stand
const ordered = box( [ 'Zebra', 'Bau', 'Ärzte', 'Neu', 'Alt', 'Bau' ], { 'data-order' : '["Ärzte","Bau","Zebra"]' } );
run( [ ordered ] );
check( 'the buttons stand in the order the box says, and the values it does not name come after them in the order the rows stand', buttonsOf( ordered ).map( function( button ) { return button.textContent } ).join( '|' ) === 'Alle|Ärzte|Bau|Zebra|Neu|Alt' );
fire( buttonsOf( ordered )[1], 'click' );
check( 'a button of the new order still shows the rows of its own value', hiddenOf( ordered ).join( '|' ) === 'true|true|false|true|true|true' && statusOf( ordered ).textContent === '1 von 6' );
const broken = [ '{', '"Zebra"', '{"a":1}', 'null', '' ].map( function( order ) {

	const one = box( [ 'Zebra', 'Bau', 'Ärzte' ], { 'data-order' : order } );

	run( [ one ] );

	return buttonsOf( one ).map( function( button ) { return button.textContent } ).join( '|' );
} );
const unsaid = box( [ 'Zebra', 'Bau', 'Ärzte' ] );
run( [ unsaid ] );
check( 'an order that is no list - broken, a text, an object, null, nothing - and a box with none leave the buttons in the order the rows stand', broken.every( function( names ) { return names === 'Alle|Zebra|Bau|Ärzte' } ) && buttonsOf( unsaid ).map( function( button ) { return button.textContent } ).join( '|' ) === 'Alle|Zebra|Bau|Ärzte' );

// a box with one value, or none, is left as it is
const single = box( [ 'Bau', 'Bau', '' ] );
const empty = box( [] );
run( [ single, empty ] );
check( 'a box whose rows have one value, or none, gets no button and no status: there is nothing to choose', buttonsOf( single ).length === 0 && statusOf( single ) === undefined && single.children.length === 3 && buttonsOf( empty ).length === 0 && empty.children.length === 0 );

// two boxes: each has its own rows, and a box in a box has its own too
const first = box( [ 'a', 'b' ] );
const second = box( [ 'x', 'y', 'z' ], { 'data-label' : 'Zweiter' } );
const inner = box( [ 'p', 'q' ] );
first.children[0].appendChild( inner );
run( [ first, second ] );
check( 'two boxes are two filters: a button of one hides no row of the other', buttonsOf( first ).filter( function( button ) { return button.parentNode === navOf( first ) } ).length === 3 && buttonsOf( second ).length === 4 );
fire( buttonsOf( second )[1], 'click' );
check( '...and the rows of the first are all still shown', hiddenOf( second ).join( '|' ) === 'false|true|true' && rowsOf( first ).filter( function( row ) { return row.parentNode === first } ).every( function( row ) { return row.hidden === false } ) );
fire( navOf( first ).children[2], 'click' );
check( 'a filter inside a row of another one is its own: the rows of the inner box are not the rows of the outer one - the outer one hides the row that holds the box, the inner one keeps its rows',
	first.children[2].hidden === true && first.children[3].hidden === false && inner.children.length === 4 && rowsOf( inner ).every( function( row ) { return row.hidden === false } ) );

// a value is text: the name of a button is what is written, never markup
const hostile = box( [ '<img src=x onerror=alert(1)>', '"><script>x</script>' ] );
run( [ hostile ] );
const names = buttonsOf( hostile ).map( function( button ) { return button.textContent } );
check( 'the name of a button is the value as text: a tag, a quote and a script in it are characters, and no element is made of them', names[1] === '<img src=x onerror=alert(1)>' && names[2] === '"><script>x</script>'
	&& findAll( hostile, function( el ) { return [ 'IMG', 'SCRIPT' ].indexOf( el.tagName ) !== -1 } ).length === 0 );

// the page is not ready yet: the script waits for it, and does nothing before
const early = box( [ 'a', 'b' ] );
const started = run( [ early ], 'loading' );
check( 'where the page is still loading it waits for DOMContentLoaded and has made no button yet', buttonsOf( early ).length === 0 && typeof started.listeners.DOMContentLoaded === 'function' );
started.listeners.DOMContentLoaded();
check( '...and makes them when the page is there', buttonsOf( early ).length === 3 );

done();
