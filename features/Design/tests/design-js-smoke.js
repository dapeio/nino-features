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
	'/_admin/design/label/state'			: 'Applied file',
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
	'/_admin/design/state/compiled'		: 'Last applied %s',
	'/_admin/design/state/short/current'	: 'Up to date',
	'/_admin/design/state/short/drifted'	: 'Saved, not applied',
	'/_admin/design/state/drifted'		: 'The selection is saved but not compiled.',
	'/_admin/design/state/foreign'		: 'Not written by Design: %s - delivered with the project or edited by hand.',
	'/_admin/design/state/previous'		: 'Previous version from %s',
	'/_admin/design/state/short/foreign'	: 'Not written by Design',
	'/_admin/design/label/save'				: 'Save draft',
	'/_admin/design/label/apply'			: 'Apply to website',
	'/_admin/design/label/restore'		: 'Restore previous version',
	'/_admin/design/hint/actions'			: '"Save draft" remembers your selection and leaves the website as it is.',
	'/_admin/design/confirm/apply'		: 'Applying rewrites: %s.',
	'/_admin/design/confirm/foreign'	: 'Not written by Design, and replaced: %s.',
	'/_admin/design/confirm/lost'			: 'In your file, but not in the new variant: %s - add it again by hand afterwards if you still need it.',
	'/_admin/design/confirm/copy'			: 'What is there now is kept first, in data/design-previous.php.',
	'/_admin/design/confirm/replaces'	: 'That replaces the previous version from %s.',
	'/_admin/design/confirm/restore'	: 'Restore the previous version?',
	'/_admin/design/msg/saved'				: 'Draft saved.',
	'/_admin/design/msg/applied'			: 'Applied.',
	'/_admin/design/msg/takenover'		: 'Files Design had not written were replaced.',
	'/_admin/design/msg/cancelled'		: 'Draft saved, not applied.',
	'/_admin/design/msg/lost'					: 'Not in the frame templates any more: %s.',
	'/_admin/design/msg/restored'			: 'Previous version restored.',
	'/_admin/design/error/restore'		: 'The previous version could not be restored.',
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
			harmony			: { 'default' : 1, steps : [ 'Monochrome', 'Analogous', 'Triadic', 'Complementary' ] },
			temperature	: { 'default' : 3, steps : [ 'Neutral', 'Cool', 'Brand', 'Warm' ] },
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
		// Whose each of the three files is - all of them Design's here
		files		: [
			{ target : '/assets/theme.css', exists : true, state : 'ours' },
			{ target : '/templates/frame-header.tpl', exists : true, state : 'ours' },
			{ target : '/templates/frame-footer.tpl', exists : true, state : 'ours' },
		],
		previous: null,
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
 *	@param		{Object}	[shell]		{ head : false } for a pane without a head; raw: true leaves
 *															the panel's own _apiCall in place and { send } stands in for
 *															Nino.http.sendRequest, { api } for Nino.adminUi.api (a newer
 *															workbench) and { dirty } for the shell's Nino.admin.dirty
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

	/*	What the browser's own question does here: the text it was asked with is
		kept, and the answer is whatever the test last said it would be - yes	*/
	const confirms = [];
	let confirmAnswer = true;

	const Nino = {
		adminUi : {
			actionBar		: function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
			scaleFrame	: function() { return function() {} },
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
			sendRequest : ( shell || {} ).send || function() { throw new Error( 'every request goes through _apiCall, which this test holds' ) },
		},
	};

	if( ( shell || {} ).api )
		Nino.adminUi.api = shell.api;
	if( ( shell || {} ).dirty )
		Nino.admin = { dirty : shell.dirty };

	const sandbox = {
		console			: console,
		document		: dc,
		Nino				: Nino,
		confirm			: function( text ) { confirms.push( text ); return confirmAnswer },
		setTimeout	: function() { return 0 },
		clearTimeout: function() {},
	};
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	const design = Nino.admin.design;

	if( ( shell || {} ).raw !== true )
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
		confirms: confirms,
		/** What the next confirm() answers */
		willConfirm	: function( answer ) { confirmAnswer = answer },
		/** The oldest request not yet answered, answered - the way the server would */
		answer	: function( status, response ) {
			const next = requests.shift();
			next.answer( status, response );
			return next;
		},
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
		[ 'Applied file', 'Up to date' ],
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
		[ 'Applied file', 'Up to date' ],
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
		[ 'Applied file', 'Up to date' ],
	] ) );

const drifted = panel( { current : false } );

check( 'the last row is what the file on disk is, in one word',
	JSON.stringify( drifted.summary().pop() ) === JSON.stringify( [ 'Applied file', 'Saved, not applied' ] ) );

console.log('');


// --- The shape of the column -----------------------------------------------------
//
// The shape is a card of labelled fields in a grid, each with its name over
// the control and the terse line under it, and a block of rows under the card
// that a small heading and a hint line open

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

check( 'and the group over the card is named',
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

console.log('');


// --- Applying asks first ---------------------------------------------------------
//
// A draft is not a website. Two buttons, a line under them saying which is
// which, and a confirmation before anything is written - built from what
// design/plan says, so it names the files, the ones that are not Design's and
// the shortcodes a frame would lose

console.log( 'Applying' );

/** design/plan's answer for a project the wizard just delivered, with [consent-settings] added to the footer by hand */
const delivered = [
	{ target : '/assets/theme.css', exists : true, state : 'foreign', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', exists : true, state : 'foreign', changes : true, lost : [] },
	{ target : '/templates/frame-footer.tpl', exists : true, state : 'foreign', changes : true, lost : [ '[consent-settings]' ] },
];

/** The requests a click on "Apply to website" sends, in order */
function endpoints( screen ) {
	return screen.requests.map( function( request ) { return request.endpoint } );
}

const asking = panel( { files : delivered.map( function( file ) { return Object.assign( {}, file ) } ) } );
const buttons = byTag( byClass( asking.form, 'nino-admin-actionbar' )[0], 'button' ).map( function( button ) { return button.textContent } );

check( 'the two buttons say what they do: save a draft, apply to the website - and there is no take-over button',
	buttons.indexOf('Save draft') !== -1 && buttons.indexOf('Apply to website') !== -1 && buttons.length === 3 );
check( 'a line under the bar says which of the two does what',
	( asking.byId('design-actions-hint') || {} ).textContent === TEXT['/_admin/design/hint/actions']
	&& hasClass( asking.byId('design-actions-hint') || element('p'), 'nino-admin-hint' ) );
check( '...and it stands after the bar, not in it', asking.form.children.indexOf( asking.byId('design-actions-hint') ) > asking.form.children.indexOf( byClass( asking.form, 'nino-admin-actionbar' )[0] ) );
check( 'with files that are not Design\'s the state line says so and names them, rather than only the stylesheet',
	asking.design._stateKey() === 'foreign'
	&& byClass( asking.form, 'design-state--warn' )[0].textContent.indexOf('assets/theme.css, templates/frame-header.tpl, templates/frame-footer.tpl') !== -1 );

asking.byId('design-apply').fire('click');

check( 'applying saves the draft first', JSON.stringify( endpoints( asking ) ) === JSON.stringify( [ 'save' ] ) );

asking.answer( 200, {} );

check( '...then asks what would happen to each file', JSON.stringify( endpoints( asking ) ) === JSON.stringify( [ 'plan' ] ) && asking.confirms.length === 0 );

asking.answer( 200, { files : delivered } );

check( 'the confirmation names the three files, which of them are not Design\'s, and the shortcode a frame would lose',
	asking.confirms.length === 1
	&& asking.confirms[0].indexOf('assets/theme.css, templates/frame-header.tpl, templates/frame-footer.tpl') !== -1
	&& asking.confirms[0].indexOf('Not written by Design, and replaced: assets/theme.css') !== -1
	&& asking.confirms[0].indexOf('[consent-settings]') !== -1 );
check( '...says the present state is kept first, one fact to a line',
	asking.confirms[0].split('\n').length === 4 && asking.confirms[0].indexOf( TEXT['/_admin/design/confirm/copy'] ) !== -1
	&& asking.confirms[0].indexOf('previous version from') === -1 );
check( '...and only then is the apply sent, with force because a file is not Design\'s',
	JSON.stringify( endpoints( asking ) ) === JSON.stringify( [ 'apply' ] ) && asking.requests[0].payload.force === true );

asking.answer( 200, {} );
asking.answer( 200, listing( { exists : true } ) );

check( 'what the screen says afterwards is that it applied, that files were replaced, and what to put back by hand',
	( asking.byId('design-msg') || {} ).textContent === 'Applied. Files Design had not written were replaced. Not in the frame templates any more: [consent-settings].' );

// Saying no leaves the draft saved and the website alone
const declined = panel( {} );
declined.willConfirm( false );
declined.byId('design-apply').fire('click');
declined.answer( 200, {} );
declined.answer( 200, { files : delivered } );

check( 'no in the confirmation sends no apply - it reloads the panel',
	JSON.stringify( endpoints( declined ) ) === JSON.stringify( [ 'list' ] ) && declined.confirms.length === 1 );

declined.answer( 200, listing( {} ) );

check( '...and says the draft is saved and not applied', ( declined.byId('design-msg') || {} ).textContent === 'Draft saved, not applied.' );

// Files that are all Design's: no force, nothing replaced, nothing lost
const ours = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
ours.byId('design-apply').fire('click');
ours.answer( 200, {} );
ours.answer( 200, { files : [
	{ target : '/assets/theme.css', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', exists : true, state : 'ours', changes : false, lost : [] },
	{ target : '/templates/frame-footer.tpl', exists : true, state : 'ours', changes : false, lost : [] },
] } );

check( 'with every file Design\'s the apply is sent without force',
	JSON.stringify( endpoints( ours ) ) === JSON.stringify( [ 'apply' ] ) && ours.requests[0].payload.force === false
	&& ours.confirms[0].indexOf('Not written by Design') === -1 );
check( '...and a version that is about to be replaced is named by its date',
	ours.confirms[0].indexOf('That replaces the previous version from 2026-09-02 08:30.') !== -1 );

// A frame somebody edited in beside a stylesheet that is Design's: the dead end
const edited = panel( {} );
edited.byId('design-apply').fire('click');
edited.answer( 200, {} );
edited.answer( 200, { files : [
	{ target : '/assets/theme.css', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-footer.tpl', exists : true, state : 'edited', changes : true, lost : [] },
] } );

check( 'a frame that was only edited gets force too - the refusal has something to answer to now',
	JSON.stringify( endpoints( edited ) ) === JSON.stringify( [ 'apply' ] ) && edited.requests[0].payload.force === true
	&& edited.confirms[0].indexOf('Not written by Design, and replaced: templates/frame-footer.tpl') !== -1 );

// "Save draft" asks nothing
const drafting = panel( {} );
drafting.byId('design-save').fire('click');
drafting.answer( 200, {} );

check( 'saving a draft sends no plan and asks nothing', drafting.confirms.length === 0 && JSON.stringify( endpoints( drafting ) ) === JSON.stringify( [ 'list' ] ) );

console.log('');


// --- The previous version ----------------------------------------------------------

console.log( 'Restoring' );

const empty = panel( {} );

check( 'with no previous version there is nothing to restore', empty.byId('design-restore') === null );

const kept = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
const restoreButton = kept.byId('design-restore');

check( 'with one the state box dates it and offers to restore it',
	restoreButton !== null && restoreButton.textContent === 'Restore previous version'
	&& byClass( kept.byId('design-state'), 'design-previous' )[0].textContent === 'Previous version from 2026-09-02 08:30' );

kept.willConfirm( false );
restoreButton.fire('click');

check( 'the button asks first, and a no sends nothing', kept.confirms.length === 1 && kept.confirms[0] === 'Restore the previous version?' && kept.requests.length === 0 );

kept.willConfirm( true );
restoreButton.fire('click');

check( 'a yes posts the restore', JSON.stringify( endpoints( kept ) ) === JSON.stringify( [ 'restore' ] ) );

kept.answer( 200, {} );
kept.answer( 200, listing( {} ) );

check( 'and the panel is drawn again from the new state, saying so', ( kept.byId('design-msg') || {} ).textContent === 'Previous version restored.' );

const noted = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
noted.willConfirm( true );
noted.byId('design-restore').fire('click');
noted.answer( 200, { notes : [ 'restored, but could not keep the version it replaced in data/design-previous.php' ] } );
noted.answer( 200, listing( {} ) );

check( 'a restore that went through with a note says restored, and the note beside it',
	( noted.byId('design-msg') || {} ).textContent === 'Previous version restored. restored, but could not keep the version it replaced in data/design-previous.php' );

const failing = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
failing.byId('design-restore').fire('click');
failing.answer( 500, { error : 'could not write /assets/theme.css' } );

check( 'a restore that failed says why and does not redraw', ( failing.byId('design-msg') || {} ).textContent === '(500) could not write /assets/theme.css'
	&& failing.requests.length === 0 );

// --- Asking the workbench ----------------------------------------------------------

console.log( 'The workbench' );

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const wasPosted = [];
const noHelper = panel( {}, { raw : true, send : function( uri, method, callback, data ) { wasPosted.push( [ uri, method, data ] ); callback( { status : 200, responseJSON : { ok : true } } ) } } );
let gotAnswer = null;
noHelper.design._apiCall( 'list', { a : 1 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	wasPosted.length === 1 && wasPosted[0][0] === '[[/nino/dir]]/_admin/' && wasPosted[0][1] === 'POST'
	&& wasPosted[0][2].action === 'design/list' && wasPosted[0][2].data === '{"a":1}' && JSON.stringify( gotAnswer ) === '[200,{"ok":true}]' );

const wasRouted = [];
const withHelper = panel( {}, { raw : true, api : { call : function( action, payload, callback ) { wasRouted.push( [ action, payload ] ); callback( 200, { via : 'api' } ) } } } );
gotAnswer = null;
withHelper.design._apiCall( 'save', { b : 2 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'with it the panel hands the action \'design/<action>\' and the payload to the helper, and posts nothing by hand',
	wasRouted.length === 1 && wasRouted[0][0] === 'design/save' && wasRouted[0][1].b === 2 && gotAnswer[1].via === 'api' );

check( 'a failure says "(status) message" as it always did where the shell has no errorText()',
	noHelper.design._errorText( 503, { error : 'Busy' }, '/_admin/design/error/save' ) === '(503) Busy'
	&& noHelper.design._errorText( 503, null, '/_admin/design/error/save' ) === '(503) /_admin/design/error/save' );
const helped = panel( {}, { raw : true, api : { errorText : function( status, response, key ) { return 'told '+ status+ ' ['+ key+ ']' } } } );
check( '...and what errorText() makes of it where it has one', helped.design._errorText( 503, { error : 'Busy' }, '/_admin/design/error/save' ) === 'told 503 [/_admin/design/error/save]' );

console.log('');


// --- Unsaved input -----------------------------------------------------------------

console.log( 'Unsaved input' );

// The shell's registry in miniature: what the panel registered, and how often it was asked to look again
const registry = { entries : {}, refreshed : 0 };
registry.register = function( name, registered ) { registry.entries[name] = registered };
registry.refresh = function() { registry.refreshed++ };
registry.isDirty = function( names ) { return names.some( function( name ) { return registry.entries[name] !== undefined && registry.entries[name].isDirty() === true } ) };

const tracked = panel( {}, { dirty : registry } );
const registered = registry.entries['design'] || null;

check( 'where the shell has the registry the panel registers under its own uri', registered !== null && typeof registered.isDirty === 'function' && typeof registered.save === 'function' && typeof registered.discard === 'function' );
check( '...a screen drawn from what is stored holds nothing unsaved, and the shell is told when it is drawn', registered.isDirty() === false && registry.refreshed > 0 );

tracked.move( 'Spacing', 'more' );
check( '...a knob that was moved does - the same answer the way back is shown for', registered.isDirty() === true && tracked.byId('design-reset').hidden === false );

tracked.byId('design-reset').fire('click');
check( '...and the way back, which draws the stored selection again, makes it not', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );

tracked.move( 'Spacing', 'more' );
registered.discard();
check( 'discarding takes the stored selection for the screen\'s own', registered.isDirty() === false );

tracked.move( 'Spacing', 'more' );
let savedFor = [];
registered.save( function( ok ) { savedFor.push( ok ) } );
check( 'the shell\'s Save stores the draft - and asks nothing, since applying is a question of its own',
	JSON.stringify( endpoints( tracked ) ) === JSON.stringify( [ 'save' ] ) && tracked.confirms.length === 0 && savedFor.length === 0 );

registered.save( function( ok ) { savedFor.push( ok ) } );
check( '...a second Save while the first is on its way says it did not save', savedFor.length === 1 && savedFor[0] === false );

tracked.answer( 200, {} );
check( '...and the first says it did, once the draft is stored', savedFor.length === 2 && savedFor[1] === true );

const refused = panel( {}, { dirty : registry } );
refused.move( 'Spacing', 'more' );
savedFor = [];
registry.entries['design'].save( function( ok ) { savedFor.push( ok ) } );
refused.answer( 500, { error : 'could not write data/design.php' } );
check( 'a draft the server turns down says so, on the screen and to the shell, and the way back stays',
	savedFor.length === 1 && savedFor[0] === false && ( refused.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php'
	&& refused.byId('design-reset').hidden === false );

// The shell answers a refused Save by showing the panel again
refused.design.showCurrent();
check( '...and the shell showing the panel again does not wipe the reason, nor the selection nobody has stored',
	( refused.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php' && registry.entries['design'].isDirty() === true );

registry.entries['design'].discard();
check( '...while a discard draws the stored selection again, so a leave that does not happen shows the truth',
	registry.entries['design'].isDirty() === false && refused.byId('design-reset').hidden === true );

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
