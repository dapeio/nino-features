/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the form card the part and its size are
 *												chosen in, the titled block the knob rows stand in,
 *												the summary under the preview frame - the list
 *												that says, in words, what the frame beside it is
 *												showing and what is about to compile - and the
 *												head of the pane the screen stands in, which names
 *												the panel and carries the Structure / Colours strip.
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
		/*	The switch the preview bar is built from is asked for its own
			input; the head's parts are asked for by class, one level down,
			the way Nino.adminUi.panelHead() reaches them	*/
		querySelector		: function( selector ) {
			if( selector.indexOf( ':scope > .' ) === 0 )
				return this.children.filter( function( child ) { return hasClass( child, selector.slice( 10 ) ) } )[0] || null;
			return byTag( this, selector )[0] || null;
		},
		// The pane is what carries data-panel, which is all closest() is asked for
		closest					: function() {
			let at = this;
			while( at !== null && at.dataset.panel === undefined )
				at = at.parent;
			return at;
		},
		// Enough of a live tree for a strip to be put into the head and taken out again
		insertAdjacentElement	: function( where, node ) {
			const siblings = this.parent.children;
			if( node.parent !== null )
				node.remove();
			siblings.splice( siblings.indexOf( this ) + ( where === 'afterend' ? 1 : 0 ), 0, node );
			node.parent = this.parent;
			return node;
		},
		insertBefore		: function( node, reference ) {
			if( node.parent !== null )
				node.remove();
			const at = reference ? this.children.indexOf( reference ) : -1;
			this.children.splice( at === -1 ? this.children.length : at, 0, node );
			node.parent = this;
			return node;
		},
		remove					: function() {
			if( this.parent === null )
				return;
			this.parent.children.splice( this.parent.children.indexOf( this ), 1 );
			this.parent = null;
		},
		// The kernel's tab strip moves the focus along with the arrow keys
		focus						: function() { focused = this },
	};

	Object.defineProperty( el, 'firstChild', { get : function() { return el.children[0] || null } } );

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

/** Whichever element was focused last - what document.activeElement is in a browser */
let focused = null;

/** Whether an element carries a class - in its className, or added through classList */
function hasClass( el, className ) {
	return ( ' '+ el.className+ ' ' ).indexOf( ' '+ className+ ' ' ) !== -1 || el.classList.contains( className );
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
	return descendants( root ).filter( function( el ) { return hasClass( el, className ) } );
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
 *	answer wrote back.
 *
 *	The mount stands in its pane, under the head the shell renders over every
 *	panel (see \Nino\Admin\Panels::panesHtml()): the panel's name, the slot
 *	for actions - and whatever strip is handed to it. { head : false } draws
 *	the screen where there is none, the way it is drawn on a kernel from
 *	before the head
 *
 *	@param		{Object}	[over]		What this screen's listing has differently
 *	@param		{Object}	[shell]		{ head : false } for a pane without a head
 */
function panel( over, shell ) {

	const pane = element('div');
	pane.id = 'admin-content-design';
	pane.dataset.panel = 'design';

	const head = element('div');
	head.className = 'admin-panel-head';
	const title = element('h2');
	title.className = 'admin-panel-title';
	title.textContent = 'Design';
	const actions = element('div');
	actions.className = 'admin-panel-actions';
	head.appendChild( title );
	head.appendChild( actions );

	if( ( shell || {} ).head !== false )
		pane.appendChild( head );

	const form = element('div');
	form.id = 'design-form';
	pane.appendChild( form );

	function byId( id ) {
		if( id === 'design-form' )
			return form;
		return descendants( pane ).filter( function( el ) { return el.id === id } )[0] || null;
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
			/* The workbench's own painter: one button lit, every button flagged -
			   and on a tablist the arrow keys its tabKeys() adds: the focus
			   moves to the next tab, and that tab is opened */
			buttonRow		: function( buttons, active, onSelect, flag ) {
				const attribute = flag || 'aria-pressed';
				const keys = Object.keys( buttons );
				const paint = function( key ) {
					keys.forEach( function( candidate ) {
						const on = candidate === key;
						buttons[candidate].classList.toggle( 'is-active', on );
						buttons[candidate].setAttribute( attribute, on === true ? 'true' : 'false' );
					} );
				};
				keys.forEach( function( key, at ) {
					buttons[key].addEventListener( 'click', function() { paint( key ); onSelect( key ) } );
					if( attribute === 'aria-selected' )
						buttons[key].addEventListener( 'keydown', function( ev ) {
							const next = keys[( at + ( ev.key === 'ArrowLeft' ? keys.length - 1 : 1 ) ) % keys.length];
							buttons[next].focus();
							paint( next );
							onSelect( next );
						} );
				} );
				paint( active );
				return paint;
			},
			/* The head of the pane an element stands in, as the kernel's
			   Nino.admin.js answers it: null outside a pane with a head, and a
			   strip handed to tabs() goes in after the name, in place of the
			   strip that stood there */
			panelHead		: function( el ) {
				const at = el && typeof el.closest === 'function' ? el.closest('[data-panel]') : null;
				const row = at ? at.querySelector(':scope > .admin-panel-head') : null;
				if( !row )
					return null;
				return {
					element	: row,
					title		: row.querySelector(':scope > .admin-panel-title'),
					actions	: row.querySelector(':scope > .admin-panel-actions'),
					tabs		: function( strip ) {
						const before = row.querySelector(':scope > .admin-panel-tabs');
						if( before !== null && before !== strip )
							before.remove();
						strip.classList.add('admin-panel-tabs');
						const name = row.querySelector(':scope > .admin-panel-title');
						if( name !== null )
							name.insertAdjacentElement( 'afterend', strip );
						else
							row.insertBefore( strip, row.firstChild );
						return strip;
					},
				};
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
		pane		: pane,
		head		: head,
		/** The strips in the head, after the name */
		headStrips : function() {
			return head.children.filter( function( child ) { return hasClass( child, 'design-tabs' ) } );
		},
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
		/** Switch to the other half of the design, wherever its strip stands */
		tab : function( label ) {
			byTag( byClass( pane, 'design-tabs' )[0], 'button' ).filter( function( button ) {
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

console.log('');


// --- The pane's head -------------------------------------------------------------
//
// The panel's name is the head's - the row the shell renders over every pane -
// so the screen under it draws no heading of its own. The strip that switches
// the two halves stands in that row beside the name, where the Features
// panel's does: it switches the column of controls and nothing else, and the
// frame beside the column stays on both tabs

console.log( 'The head' );

/** A key pressed on a tab, the way the kernel's tabKeys() hears it */
function press( button, key ) {
	( button.listeners.keydown || [] ).forEach( function( fn ) { fn( { key : key, preventDefault : function() {} } ) } );
}

/** The word over the card - which half of the design the column is showing */
function eyebrow( screen ) {
	return ( byClass( screen.form, 'design-eyebrow' )[0] || {} ).textContent;
}

const headed = panel();
const strip = headed.headStrips()[0] || null;

check( 'the screen draws no heading of its own - the head names the panel',
	byTag( headed.form, 'h1' ).concat( byTag( headed.form, 'h2' ), byTag( headed.form, 'h3' ) ).length === 0 );
check( 'the strip is handed to the pane\'s head and stands right after the name, not over the column',
	strip !== null && headed.head.children[1] === strip && hasClass( headed.head.children[0], 'admin-panel-title' )
	&& strip.classList.contains('admin-panel-tabs') && byClass( headed.form, 'design-tabs' ).length === 0 );

// Opening the panel again draws the screen again (showCurrent())
headed.design.showCurrent();

check( '...and a screen drawn again puts one strip there, not a second beside the first',
	headed.headStrips().length === 1 && headed.head.children.length === 3
	&& hasClass( headed.head.children[1], 'design-tabs' ) && hasClass( headed.head.children[2], 'admin-panel-actions' ) );

const shown = headed.headStrips()[0] || null;
headed.tab('Colours');

check( 'the other half redraws the column under the head and leaves the strip standing - the same element, its tab lit',
	shown !== null && headed.headStrips()[0] === shown && headed.headStrips().length === 1
	&& byTag( shown, 'button' )[1].getAttribute('aria-selected') === 'true' && eyebrow( headed ) === 'Palette'
	&& headed.byId('design-frame') !== null );

/*	The keyboard is the reason the strip is not drawn again on a switch. An
	arrow key moves the focus to the next tab and opens it; a strip drawn anew
	with the column took that tab out of the document, and the focus fell onto
	the page, so the next arrow key had nowhere to start from	*/
const keyed = panel();
const keyTabs = byTag( keyed.headStrips()[0] || element('div'), 'button' );

focused = null;
if( keyTabs.length === 2 )
	press( keyTabs[0], 'ArrowRight' );

check( 'an arrow key on the strip opens the other half and the focus stays on the tab it moved to, in the head',
	keyTabs.length === 2 && focused === keyTabs[1] && keyTabs[1].parent === keyed.headStrips()[0]
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && eyebrow( keyed ) === 'Palette' );

if( keyTabs.length === 2 )
	press( keyTabs[1], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === 2 && focused === keyTabs[0] && keyTabs[0].parent === keyed.headStrips()[0]
	&& keyTabs[0].getAttribute('aria-selected') === 'true' && eyebrow( keyed ) === 'Selection' );

/*	A kernel from before the head, or the script drawn outside its pane: the
	strip has nowhere else to go, so it opens the column the way it did - and
	the heading stays gone there as well	*/
const bare = panel( {}, { head : false } );
const opened = hasClass( ( bare.byId('design-controls') || element('div') ).children[0] || element('div'), 'design-tabs' );
bare.tab('Colours');

check( 'where the pane has no head, the strip opens the column and comes back with it on a switch - with no heading over it',
	opened === true && hasClass( bare.byId('design-controls').children[0], 'design-tabs' ) && byClass( bare.pane, 'design-tabs' ).length === 1
	&& eyebrow( bare ) === 'Palette' && byTag( bare.form, 'h2' ).length === 0 );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
