/**
 *	Nino features
 *	sign-pad-js-smoke.js	What the script of the signing page does (assets/sign.js): the pad over a stand-in canvas - the place of a pointer, the strokes
 *												drawn as they are made, the picture that is written into the hidden field when the form is sent (dark ink, a ground that is
 *												transparent, nothing where there is no stroke), the button that empties it, the field of the typed name that is hidden while
 *												the pad holds a stroke and is there again when it is emptied - the typed name is a way of its own, so the form is signed with
 *												the keyboard alone - and a page whose canvas cannot draw, which is left to the browser. Only Nino itself is a stand-in.
 *
 *												The helpers - check(), done(), fills(), load(), source(), findAll(), fire(), element() and documentOf() - are those of
 *												tests/harness.js of the checkout, three levels up or the one NINO_ROOT names. sign-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Sign/tests/sign-pad-js-smoke.js
 *	       NINO_ROOT=../nino node features/Sign/tests/sign-pad-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, Buffer */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, source, load, fire, element } = require( path.join( root, 'tests/harness.js' ) );

function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

console.log( '\nsign.js - the pad\n' );

/**
 *	A canvas stand-in: the size it has, the box the page gives it, and a 2d context that writes down what it is told to draw. Its
 *	picture is the list of those calls, so that a test reads what an offscreen canvas was drawn with out of the data URL
 *
 *	@param		{number}	width
 *	@param		{number}	height
 *	@param		{Object}	rect							{ left, top, width, height }
 *	@param		{boolean}	[drawable]				false for a canvas that has no 2d context
 *
 *	@return		{Object}
 */
function canvasOf( width, height, rect, drawable ) {

	const canvas = element('canvas');

	canvas.width = width;
	canvas.height = height;
	canvas.log = [];
	canvas.captured = [];

	const note = function( name ) { return function( ...args ) { canvas.log.push( [ name ].concat( args ) ) } };
	const context = {
		clearRect : note('clearRect'), beginPath : note('beginPath'), moveTo : note('moveTo'), lineTo : note('lineTo'), arc : note('arc'),
		stroke : function() { canvas.log.push( [ 'stroke', context.strokeStyle, context.lineWidth, context.lineCap ] ) },
		fill : function() { canvas.log.push( [ 'fill', context.fillStyle ] ) },
		fillRect : note('fillRect'),
	};

	canvas.getContext = function( kind ) { return kind === '2d' && drawable !== false ? context : null };
	canvas.toDataURL = function( type ) { return 'data:'+ type+ ';base64,'+ Buffer.from( JSON.stringify( canvas.log ) ).toString('base64') };
	canvas.getBoundingClientRect = function() { return rect };
	canvas.setPointerCapture = function( id ) { canvas.captured.push( id ) };

	return canvas;
}

/** What an offscreen canvas was drawn with, out of the data URL the script made of it */
function drawnWith( url ) {
	const data = String( url ).split(',')[1];
	return data === undefined ? [] : JSON.parse( Buffer.from( data, 'base64' ).toString() );
}

/**
 *	The pad's script in a page of its own: one signing form with the pieces sign.js looks for - the box of the canvas, which comes
 *	hidden, the canvas, the button that empties it, the field of the typed name and the hidden field of the picture - over a
 *	Nino that keeps the callbacks bound to the ready event
 *
 *	@param		{Object}	[options]					{ drawable : false for a canvas without a context, pieces : false to leave out the field of the picture }
 *
 *	@return		{Object}
 */
function pad( options ) {

	options = options ?? {};

	const offscreen = [];
	const ready = [];
	const form = element('form');
	const draw = element('div');
	const canvas = canvasOf( 600, 200, { left : 10, top : 20, width : 300, height : 100 }, options.drawable );
	const clear = element('button');
	const typed = element('div');
	const image = element('input');

	form.setAttribute( 'data-sign-form', '' );
	draw.setAttribute( 'data-sign-draw', '' );
	draw.hidden = true;
	canvas.setAttribute( 'data-sign-pad', '' );
	clear.setAttribute( 'data-sign-clear', '' );
	typed.setAttribute( 'data-sign-name', '' );
	image.setAttribute( 'data-sign-image', '' );

	draw.appendChild( canvas );
	draw.appendChild( clear );
	form.appendChild( draw );
	form.appendChild( typed );

	if( options.pieces !== false )
		form.appendChild( image );

	const Nino = { events : { bindCallback : function( name, fn ) { ready.push( { name : name, fn : fn } ) } } };
	const page = {
		createElement : function( tag ) {
			const el = tag === 'canvas' ? canvasOf( 0, 0, { left : 0, top : 0, width : 0, height : 0 }, options.offscreen ) : element( tag );
			if( tag === 'canvas' )
				offscreen.push( el );
			return el;
		},
		querySelectorAll : function( selector ) { return selector === 'form[data-sign-form]' ? [ form ] : [] },
	};
	// Whatever the script says to the person: it says nothing, the words of the page are the page's
	const spoken = [];
	const window = { Nino : Nino, getComputedStyle : function() { return { color : 'rgb(240, 240, 240)' } }, alert : function( text ) { spoken.push( text ) }, confirm : function( text ) { spoken.push( text ); return true } };
	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : window } );

	load( own( 'assets/sign.js' ), context, 'sign.js' );

	return {
		Nino : Nino, spoken : spoken, sign : window.Nino.sign, ready : ready, form : form, draw : draw, canvas : canvas, clear : clear, typed : typed, image : image, offscreen : offscreen,
		start : function() { ready.forEach( function( each ) { each.fn() } ) },

		// A pointer on the page: at a point in the pixels of the page, as an event of the pointer would say it
		point : function( name, x, y, more ) {
			const stopped = { prevented : 0 };
			fire( canvas, name, Object.assign( { pointerType : 'mouse', button : 0, pointerId : 1, clientX : x, clientY : y, preventDefault : function() { stopped.prevented++ } }, more ) );
			return stopped;
		},
		submit : function() { fire( form, 'submit' ) },
	};
}

{
	const p = pad();

	check( 'the script gives Nino.sign its four pure functions and the start, and binds the start to the ready event, nothing more', Object.keys( p.sign ).sort().join() === 'init,inked,paint,picture,place' && p.ready.length === 1 && p.ready[0].name === 'ready' && p.ready[0].fn === p.sign.init );
	check( 'nothing is changed on the page before the ready event: the box of the canvas is hidden, the typed name is shown', p.draw.hidden === true && p.typed.hidden === false && p.form.listeners.submit === undefined );

	// place(): the pixels of the page into the pixels of the canvas, which is drawn at another size
	const rect = { left : 10, top : 20, width : 300, height : 100 };
	const size = { width : 600, height : 200 };
	const at = p.sign.place( rect, size, 40, 70 );
	check( 'a pointer is placed on the canvas by the size the page gives it against the size it has - twice as many pixels here - and the corner is the corner', at.x === 60 && at.y === 100 && p.sign.place( rect, size, 10, 20 ).x === 0 && p.sign.place( rect, size, 310, 120 ).y === 200 );
	check( '...a canvas the page gives no size (hidden, not laid out) is placed as if it were drawn at its own', p.sign.place( { left : 0, top : 0, width : 0, height : 0 }, size, 40, 70 ).x === 40 && p.sign.place( { left : 0, top : 0, width : 0, height : 0 }, size, 40, 70 ).y === 70 );

	check( 'there is something to draw when some stroke has a point, and not before', p.sign.inked( [] ) === false && p.sign.inked( [ [] ] ) === false && p.sign.inked( [ [], [ { x : 1, y : 1 } ] ] ) === true );

	// paint(): a dot for a stroke of one point, a line through the points for more
	const log = [];
	const brush = { clearRect : function( ...a ) { log.push( [ 'clear', ...a ] ) }, beginPath : function() { log.push( [ 'begin' ] ) }, moveTo : function( ...a ) { log.push( [ 'move', ...a ] ) }, lineTo : function( ...a ) { log.push( [ 'line', ...a ] ) },
		arc : function( ...a ) { log.push( [ 'arc', ...a ] ) }, stroke : function() { log.push( [ 'stroke', brush.strokeStyle, brush.lineWidth ] ) }, fill : function() { log.push( [ 'fill', brush.fillStyle ] ) } };
	p.sign.paint( brush, [ [ { x : 5, y : 6 } ], [ { x : 1, y : 1 }, { x : 2, y : 3 }, { x : 4, y : 3 } ], [] ], 600, 200, '#123456' );
	check( 'paint() empties the canvas first and draws a dot for a stroke of one point, a line through the points of a longer one, and nothing for a stroke without a point, all in the colour given and in a line of 3 pixels at this width',
		JSON.stringify( log ) === JSON.stringify( [ [ 'clear', 0, 0, 600, 200 ], [ 'begin' ], [ 'arc', 5, 6, 1.5, 0, Math.PI * 2 ], [ 'fill', '#123456' ], [ 'begin' ], [ 'move', 1, 1 ], [ 'line', 2, 3 ], [ 'line', 4, 3 ], [ 'stroke', '#123456', 3 ] ] )
		&& brush.lineCap === 'round' && brush.lineJoin === 'round' );
	p.sign.paint( brush, [ [ { x : 1, y : 1 }, { x : 2, y : 2 } ] ], 100, 40, '#000' );
	check( '...and a line of at least 2 pixels on a canvas that is small', brush.lineWidth === 2 );

	check( 'picture() of no stroke is nothing at all, and makes no canvas to draw it on', p.sign.picture( [], 600, 200 ) === '' && p.sign.picture( [ [] ], 600, 200 ) === '' && p.offscreen.length === 0 );
	const shot = p.sign.picture( [ [ { x : 1, y : 1 }, { x : 20, y : 9 } ] ], 600, 200 );
	const ops = drawnWith( shot );
	check( 'picture() of a stroke is a PNG data URL of a canvas of the size given, drawn in the dark ink whatever the colours of the pad are, on a ground that is cleared and never filled', shot.indexOf('data:image/png;base64,') === 0
		&& p.offscreen.length === 1 && p.offscreen[0].width === 600 && p.offscreen[0].height === 200 && ( ops[0] ?? [] ).join() === 'clearRect,0,0,600,200' && ops.some( function( op ) { return op[0] === 'stroke' && op[1] === '#10162f' } )
		&& ops.some( function( op ) { return op[0] === 'fillRect' } ) === false );
	const blind = pad( { offscreen : false } );
	check( 'a browser that gives no context to the second canvas gives no picture and no error', blind.sign.picture( [ [ { x : 1, y : 1 } ] ], 600, 200 ) === '' );
}

{
	const p = pad();
	p.start();

	check( 'at the ready event the box of the canvas is shown, and the form is marked as taken over', p.draw.hidden === false && p.form.dataset.signBound === '1' && p.form.listeners.submit.length === 1 );
	check( '...an empty pad has nothing to clear and leaves the typed name where it is', p.clear.disabled === true && p.typed.hidden === false );
	p.start();
	check( 'a second start takes the form over once only', p.form.listeners.submit.length === 1 && p.canvas.listeners.pointerdown.length === 1 );

	const down = p.point( 'pointerdown', 40, 70 );
	check( 'a stroke begins at a pointer going down on the canvas: the page does not scroll under it, the pointer is captured, and the pad draws a dot in the colour of the pad', down.prevented === 1 && p.canvas.captured.join() === '1'
		&& p.canvas.log.filter( function( op ) { return op[0] === 'arc' } ).length === 1 && p.canvas.log.filter( function( op ) { return op[0] === 'arc' } )[0].slice( 1, 3 ).join() === '60,100' && p.canvas.log.filter( function( op ) { return op[0] === 'fill' } ).pop()[1] === 'rgb(240, 240, 240)' );
	check( '...the field of the typed name goes, there is one way to sign at a time, and the button that empties the pad is ready', p.typed.hidden === true && p.clear.disabled === false );

	p.point( 'pointermove', 100, 40 );
	p.point( 'pointermove', 160, 80 );
	const lines = p.canvas.log.filter( function( op ) { return op[0] === 'lineTo' } );
	check( 'a pointer that moves with the button down extends the stroke, in the pixels of the canvas, and the pad is drawn again from its strokes every time', lines.length >= 2 && lines[lines.length - 1].slice( 1 ).join() === '300,120' && lines[lines.length - 2].slice( 1 ).join() === '180,40'
		&& p.canvas.log.filter( function( op ) { return op[0] === 'clearRect' } ).length === 4 );

	p.point( 'pointerup', 160, 80 );
	const count = p.canvas.log.length;
	p.point( 'pointermove', 200, 90 );
	check( 'a pointer that moves after the button is up draws nothing', p.canvas.log.length === count );

	p.point( 'pointerdown', 60, 50 );
	p.point( 'pointercancel', 60, 50 );
	const cancelled = p.canvas.log.length;
	p.point( 'pointermove', 80, 60 );
	check( 'a pointer that was cancelled ends its stroke too: a move after it draws nothing', p.canvas.log.length === cancelled );

	const right = p.point( 'pointerdown', 70, 70, { button : 2 } );
	check( 'the secondary button of a mouse starts no stroke and keeps the context menu of the browser', p.canvas.log.length === cancelled && right.prevented === 0 );
	const touch = p.point( 'pointerdown', 70, 70, { pointerType : 'touch', button : 0 } );
	p.point( 'pointerup', 70, 70 );
	check( '...a finger and a pen start one, whatever their button says', p.canvas.log.length > cancelled && touch.prevented === 1 );

	p.submit();
	const ink = drawnWith( p.image.value );
	check( 'sending the form writes the picture into the hidden field: a PNG of the strokes in the dark ink on a ground that is cleared, the size of the canvas', p.image.value.indexOf('data:image/png;base64,') === 0 && p.offscreen.length === 1
		&& p.offscreen[0].width === 600 && p.offscreen[0].height === 200 && ( ink[0] ?? [] ).join() === 'clearRect,0,0,600,200' && ink.filter( function( op ) { return op[0] === 'stroke' || op[0] === 'fill' } ).every( function( op ) { return op[1] === '#10162f' } )
		&& ink.filter( function( op ) { return op[0] === 'stroke' } ).length === 1 && ink.filter( function( op ) { return op[0] === 'arc' } ).length === 2 && ink.some( function( op ) { return op[0] === 'fillRect' } ) === false );
	check( '...whatever the pad itself is painted in: the pad on the screen was painted in the colour the page gave it, the picture only in the ink', p.canvas.log.filter( function( op ) { return op[0] === 'fill' } ).every( function( op ) { return op[1] === 'rgb(240, 240, 240)' } ) );

	p.clear.click();
	check( 'the button that empties the pad empties it - the strokes, the field of the picture, the pad on screen - and gives the typed name back', p.image.value === '' && p.typed.hidden === false && p.clear.disabled === true
		&& p.canvas.log.slice( -1 )[0].join() === 'clearRect,0,0,600,200' );
	p.submit();
	check( 'a form sent with no stroke leaves the field of the picture empty, and makes no picture', p.image.value === '' && p.offscreen.length === 1 );

	p.point( 'pointerdown', 40, 70 );
	p.point( 'pointerup', 40, 70 );
	p.submit();
	check( 'a single touch is a stroke - a dot -, and signs', drawnWith( p.image.value ).some( function( op ) { return op[0] === 'arc' } ) === true );
	check( 'the pad says nothing to the person in the whole of a signing - no alert, no question: the words of the page are the page\'s', p.spoken.length === 0 );
}

{
	const p = pad( { drawable : false } );
	p.start();
	check( 'a canvas that cannot draw leaves the form to the browser: the box stays hidden, the typed name stays, and nothing is listened to', p.draw.hidden === true && p.typed.hidden === false && p.form.listeners.submit === undefined && p.canvas.listeners.pointerdown === undefined && p.form.dataset.signBound === undefined );

	const noField = pad( { pieces : false } );
	noField.start();
	check( 'a form without the hidden field of the picture is left alone too', noField.draw.hidden === true && noField.form.listeners.submit === undefined );

	const plain = pad();
	plain.canvas.getContext = undefined;
	plain.start();
	check( 'so is one whose canvas has no getContext at all', plain.draw.hidden === true && plain.form.listeners.submit === undefined );
}


console.log( '\nthe source - what the script does not do\n' );

{
	const site = source( own('assets/sign.js') );
	const code = function( text ) { return text.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /^\s*\/\/.*$/gm, '' ) };
	const markup = /\binnerHTML\s*=(?!\s*'')|\.outerHTML\b|insertAdjacentHTML|document\.write|\beval\s*\(|new\s+Function|\bon[a-z]+\s*=\s*['"]/;

	check( 'the script writes no markup: nothing is evaluated or written into the document', markup.test( code( site ) ) === false );
	check( 'the pad reads and writes no word at all: no fill, no textContent, no alert', /getText|textContent|innerText|alert\s*\(|confirm\s*\(/.test( code( site ) ) === false );
}

done();
