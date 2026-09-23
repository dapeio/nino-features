/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the form card the part and its size are
 *												chosen in, the titled block the knob rows stand in,
 *												and the summary under the preview frame - the list
 *												that says, in words, what the frame beside it is
 *												showing and what is about to compile.
 *
 *												The list is the half a picture cannot give: a
 *												preview says what a design looks like and nothing
 *												about which selection produced it, so the checks
 *												here read it back after every kind of change the
 *												screen allows - a knob moved, another part opened,
 *												the other half of the design switched to.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node. design-smoke.php runs it through
 *												node when node is on the path, so bin/check.sh and
 *												CI cover it; it also runs on its own.
 *
 *	Usage: node features/Design/tests/design-js-smoke.js
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

const source = fs.readFileSync( path.join( __dirname, '../assets/admin.js' ), 'utf8' );

// The panel's own words, as text/en_US.php carries them - the ones this test
// reads back off the screen. A key with no entry answers as itself, which is
// what the workbench does with a fill nobody wrote
const TEXT = {
	'/_admin/design/label/title'			: 'Design',
	'/_admin/design/label/picker'			: 'Part',
	'/_admin/design/label/variant'		: 'Variant',
	'/_admin/design/label/global'			: 'Global',
	'/_admin/design/label/size'				: 'Root size',
	'/_admin/design/label/state'			: 'Compiled file',
	'/_admin/design/label/preview'		: 'Preview',
	'/_admin/design/label/finetune'		: 'Finetuning',
	'/_admin/design/label/tuning'			: 'Tuning',
	'/_admin/design/group/selection'	: 'Selection',
	'/_admin/design/group/palette'		: 'Palette',
	'/_admin/design/preview/eyebrow'	: 'This selection',
	'/_admin/design/hint/size'				: 'Scales the whole page through the root font size.',
	'/_admin/design/hint/finetune'		: 'One step below what the chosen set declares, the set as it is, or one step above.',
	'/_admin/design/hint/tuning'			: 'How the rest of the palette is solved out of the brand colour.',
	'/_admin/design/part/section'			: 'Section',
	'/_admin/design/part/header'			: 'Header',
	'/_admin/design/size/m'						: 'default',
	'/_admin/design/step/less'				: '−1',
	'/_admin/design/step/default'			: '0',
	'/_admin/design/step/more'				: '+1',
	'/_admin/design/knob/volume/label'		: 'Headings',
	'/_admin/design/knob/volume/default'	: 'Standard',
	'/_admin/design/knob/spacing/label'		: 'Spacing',
	'/_admin/design/knob/spacing/default'	: 'Standard',
	'/_admin/design/knob/spacing/more'		: 'Airy',
	'/_admin/design/knob/shaping/label'		: 'Corners',
	'/_admin/design/knob/shaping/default'	: 'Standard',
	'/_admin/design/tab/structure'		: 'Structure',
	'/_admin/design/tab/colours'			: 'Colours',
	'/_admin/design/label/primary'		: 'Brand colour',
	'/_admin/design/label/secondary'	: 'Second colour',
	'/_admin/design/colour/harmony/1'					: 'Monochrome',
	'/_admin/design/colour/temperature/label'	: 'Temperature',
	'/_admin/design/colour/temperature/3'			: 'Brand',
	'/_admin/design/colour/saturation/label'	: 'Saturation',
	'/_admin/design/colour/saturation/2'			: 'Standard',
	'/_admin/design/colour/contrast/label'		: 'Contrast',
	'/_admin/design/colour/contrast/2'				: 'Standard',
	'/_admin/design/colour/depth/label'				: 'Depth',
	'/_admin/design/colour/depth/2'						: 'Standard',
	'/_admin/design/state/current'		: 'The file answers to this selection.',
	'/_admin/design/state/compiled'		: 'Last compiled %s',
	'/_admin/design/state/short/current'	: 'Up to date',
	'/_admin/design/state/short/drifted'	: 'Saved, not compiled',
	'/_admin/design/state/drifted'		: 'The selection is saved but not compiled.',
};

/**
 *	One element, with just enough of the interface admin.js reaches for
 */
function element( tag ) {

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		parent			: null,
		dataset			: {},
		attributes	: {},
		classes			: {},
		listeners		: {},
		className		: '',
		textContent	: '',
		value				: '',
		hidden			: false,
		disabled		: false,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		removeEventListener	: function() {},
		fire						: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {} } ) } ) },
		// _switchTab() and _redrawColours() swap a whole column for a new one
		replaceWith			: function( node ) {
			const parent = this.parent;
			const at = parent === null ? -1 : parent.children.indexOf( this );
			if( at === -1 )
				return;
			node.parent = parent;
			parent.children[at] = node;
			this.parent = null;
		},
		// The switch the preview bar is built from is asked for its own input
		querySelector		: function( selector ) { return byTag( this, selector )[0] || null },
	};

	el.classList = {
		add				: function( name ) { el.classes[name] = true },
		toggle		: function( name, on ) { if( on === true ) el.classes[name] = true; else delete el.classes[name] },
		contains	: function( name ) { return el.classes[name] === true },
	};

	// The one thing the panel empties a pane with
	Object.defineProperty( el, 'innerHTML', {
		get : function() { return '' },
		set : function( value ) {
			if( String( value ) !== '' )
				throw new Error( 'the stand-in only takes innerHTML = \'\'' );
			el.children.forEach( function( child ) { child.parent = null } );
			el.children = [];
		},
	} );

	return el;
}

/** Every element below $root, depth first */
function descendants( root ) {
	let all = [];
	root.children.forEach( function( child ) {
		all.push( child );
		all = all.concat( descendants( child ) );
	} );
	return all;
}

/** Every element below $root carrying $className */
function byClass( root, className ) {
	return descendants( root ).filter( function( el ) {
		return ( ' '+ el.className+ ' ' ).indexOf( ' '+ className+ ' ' ) !== -1;
	} );
}

/** Every element below $root of that tag */
function byTag( root, tag ) {
	return descendants( root ).filter( function( el ) { return el.tagName === String( tag ).toUpperCase() } );
}

/** One part, as design/list answers it */
function part( name, kind, knobs ) {
	return {
		part : name, kind : kind, set : 'v1', moved : {}, knobs : knobs,
		catalogue : { v1 : { name : 'Nino', description : 'The framework as it is' }, v2 : { name : 'Wide', description : 'Wider' } },
	};
}

/** The whole answer of design/list, with whatever a test wants different in it */
function listing( over ) {
	return Object.assign( {
		parts		: [ part( 'header', 'frame', [] ), part( 'section', 'set', [ 'volume', 'spacing' ] ) ],
		knobs		: {},
		global	: [ 'volume', 'spacing', 'shaping' ],
		size		: 'm',
		steps		: [ 'less', 'default', 'more' ],
		sizes		: [ 's', 'm', 'l' ],
		colours	: { primary : '#4faae8', secondary : '' },
		palette	: {
			harmony			: { kind : 'choice', 'default' : 1, steps : [ 'Monochrome', 'Analogous', 'Triadic', 'Complementary' ] },
			temperature	: { kind : 'choice', 'default' : 3, steps : [ 'Neutral', 'Cool', 'Brand', 'Warm' ] },
			saturation	: { 'default' : 2, steps : [ 'Muted', 'Standard', 'Rich' ] },
			contrast		: { 'default' : 2, steps : [ 'Soft', 'Standard', 'Strong' ] },
			depth				: { 'default' : 2, steps : [ 'Flat', 'Standard', 'Raised' ] },
		},
		brand		: { light : { safe : true, ratio : 5.4, target : 4.5 } },
		accent	: '#8ad0f5',
		notes		: [],
		target	: '/assets/theme.css',
		exists	: true,
		ours		: true,
		compiled: '2026-09-01T10:00:00+00:00',
		current	: true,
	}, over || {} );
}

/**
 *	The panel's screen, drawn out of one design/list answer.
 *
 *	Every request the script makes goes through _apiCall(), which is held here
 *	the way the other feature tests hold it - and the preview behind it is
 *	debounced, so a stand-in timer that never fires is exactly what a test of
 *	the screen wants: what is asserted on is what the click wrote, not what an
 *	answer wrote back
 *
 *	@param		{Object}	[over]		What this screen's listing has differently
 */
function panel( over ) {

	const form = element('div');
	form.id = 'design-form';

	function byId( id ) {
		if( id === 'design-form' )
			return form;
		return descendants( form ).filter( function( el ) { return el.id === id } )[0] || null;
	}

	const dc = {
		documentElement	: element('html'),
		body						: element('body'),
		createElement		: function( tag ) { return element( tag ) },
		getElementById	: function( id ) { return byId( id ) },
	};

	const requests = [];

	const Nino = {
		adminUi : {
			actionBar		: function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
			scaleFrame	: function() { return function() {} },
			text				: function( value ) { return String( value ) },
			/* The workbench's own painter: one button lit, every button flagged */
			buttonRow		: function( buttons, active, onSelect, flag ) {
				const attribute = flag || 'aria-pressed';
				const paint = function( key ) {
					Object.keys( buttons ).forEach( function( candidate ) {
						const on = candidate === key;
						buttons[candidate].classList.toggle( 'is-active', on );
						buttons[candidate].setAttribute( attribute, on === true ? 'true' : 'false' );
					} );
				};
				Object.keys( buttons ).forEach( function( key ) {
					buttons[key].addEventListener( 'click', function() { paint( key ); onSelect( key ) } );
				} );
				paint( active );
				return paint;
			},
			switchField	: function( options ) {
				const label = element('label');
				label.className = 'nino-admin-switch';
				const input = element('input');
				input.type = 'checkbox';
				input.checked = options.checked === true;
				label.appendChild( input );
				return label;
			},
		},
		content : {
			getText : function( key ) { return TEXT[key] !== undefined ? TEXT[key] : key },
		},
		events : {
			bindCallback : function() {},
		},
		http : {
			sendRequest : function() { throw new Error( 'every request goes through _apiCall, which this test holds' ) },
		},
	};

	const sandbox = {
		console			: console,
		document		: dc,
		Nino				: Nino,
		setTimeout	: function() { return 0 },
		clearTimeout: function() {},
	};
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	const design = Nino.admin.design;

	design._apiCall = function( endpoint, payload, callback ) {
		requests.push( { endpoint : endpoint, payload : payload, answer : callback } );
	};

	design._data	= listing( over );
	design._edit	= design._selection( design._data );
	design._ready	= true;
	design._render();

	return {
		design 	: design,
		requests: requests,
		form		: form,
		/** The summary under the frame, as [ label, value ] pairs */
		summary : function() {
			const list = byId('design-summary');
			return list === null ? [] : list.children.map( function( row ) {
				return [ ( byTag( row, 'span' )[0] || {} ).textContent, ( byTag( row, 'strong' )[0] || {} ).textContent ];
			} );
		},
		/** One knob row on the left, by the name it carries */
		knobRow : function( label ) {
			return byClass( form, 'design-knob-row' ).filter( function( row ) {
				return ( byClass( row, 'design-knob-label' )[0] || {} ).textContent === label;
			} )[0] || null;
		},
		/** Move a knob the way a hand does */
		move : function( label, step ) {
			byTag( this.knobRow( label ), 'button' ).filter( function( button ) {
				return button.dataset.step === step;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** Open another part in the picker */
		open : function( name ) {
			const picker = byId('design-picker');
			picker.value = name;
			picker.fire('change');
		},
		/** Switch to the other half of the design */
		tab : function( label ) {
			byTag( byClass( form, 'design-tabs' )[0], 'button' ).filter( function( button ) {
				return button.textContent === label;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		byId : byId,
	};
}


// --- The summary under the frame -------------------------------------------------

console.log( 'The summary' );

const screen = panel();

const preview = screen.byId('design-preview');
const summary = screen.byId('design-summary');

check( 'the summary stands inside the preview column, under the frame',
	summary !== null && preview !== null && descendants( preview ).indexOf( summary ) > descendants( preview ).indexOf( screen.byId('design-frame') ) );

check( '...and says which part is open, what the page is measured in, and where each of the three knobs stands',
	JSON.stringify( screen.summary() ) === JSON.stringify( [
		[ 'Part', 'Global' ],
		[ 'Root size', 'default' ],
		[ 'Headings', 'Standard' ],
		[ 'Spacing', 'Standard' ],
		[ 'Corners', 'Standard' ],
		[ 'Compiled file', 'Up to date' ],
	] ) );

/*	The value, not the click: a knob says what will compile, and the list is
	read as the answer to "what am I looking at" rather than as a log of what
	was pressed */
screen.move( 'Spacing', 'more' );

check( 'a knob that was moved is a knob the list says the new value of',
	JSON.stringify( screen.summary()[3] ) === JSON.stringify( [ 'Spacing', 'Airy' ] ) );

screen.open('section');

check( 'opening a part puts that part and the variant it is on in the list, over its own two knobs',
	JSON.stringify( screen.summary() ) === JSON.stringify( [
		[ 'Part', 'Section' ],
		[ 'Variant', 'v1' ],
		[ 'Root size', 'default' ],
		[ 'Headings', 'Standard' ],
		[ 'Spacing', 'Airy' ],
		[ 'Compiled file', 'Up to date' ],
	] ) );

screen.tab('Colours');

check( 'the palette half lists the two colours and every knob the palette is solved with',
	JSON.stringify( screen.summary() ) === JSON.stringify( [
		[ 'Brand colour', '#4faae8' ],
		[ 'Second colour', 'Monochrome' ],
		[ 'Temperature', 'Brand' ],
		[ 'Saturation', 'Standard' ],
		[ 'Contrast', 'Standard' ],
		[ 'Depth', 'Standard' ],
		[ 'Compiled file', 'Up to date' ],
	] ) );

const drifted = panel( { current : false } );

check( 'the last row is what the file on disk is, in one word',
	JSON.stringify( drifted.summary().pop() ) === JSON.stringify( [ 'Compiled file', 'Saved, not compiled' ] ) );

console.log('');


// --- The shape of the column -----------------------------------------------------
//
// The section composer of the Templates feature is the shape being followed
// here: a card of labelled fields in a grid, each with its name over the
// control and the terse line under it, and a block of rows under the card that
// a small heading and a hint line open

console.log( 'The column' );

const column = panel();
const grid = byClass( column.form, 'design-form-grid' )[0] || null;
const fields = grid === null ? [] : byClass( grid, 'design-field' );

check( 'the part and the size are two fields of one grid, not a select above a card',
	fields.length === 2
	&& ( byTag( fields[0], 'select' )[0] || {} ).id === 'design-picker'
	&& ( byTag( fields[1], 'select' )[0] || {} ).id === 'design-size' );
check( '...each with its name over the control and what it does under it',
	fields.length === 2
	&& ( byTag( fields[1], 'label' )[0] || {} ).textContent === 'Root size'
	&& ( byTag( fields[1], 'small' )[0] || {} ).textContent === TEXT['/_admin/design/hint/size'] );

const knobs = column.byId('design-knobs');
const label = knobs === null ? null : byClass( knobs, 'design-section-label' )[0] || null;

check( 'the knob rows stand in a block of their own, opened by a small heading and a hint line',
	label !== null
	&& ( byTag( label, 'strong' )[0] || {} ).textContent === 'Finetuning'
	&& ( byTag( label, 'small' )[0] || {} ).textContent === TEXT['/_admin/design/hint/finetune']
	&& byClass( knobs, 'design-knob-row' ).length === 3 );

check( 'and the group over the card is named, the way the composer names one',
	( byClass( column.form, 'design-eyebrow' )[0] || {} ).textContent === 'Selection' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
