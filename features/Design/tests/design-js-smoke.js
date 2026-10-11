/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the strip of six tabs in the head of the
 *												pane, and under it the controls of the tab that is open
 *												- the two frames, the page and its animation; the five
 *												font roles, with a size that is typed; the template of
 *												content, buttons and components over its named knobs,
 *												each row with its icon, greyed where the chosen template
 *												does not answer to them; the palette and its mode - the
 *												summary under the preview frame, the action bar with
 *												the way back to the previous version, the head of the
 *												preview - one row, with the viewport switch of three
 *												icon buttons and the switch that lets the frame follow
 *												- and the scroll of the frame to the anchor of the tab,
 *												or to where the change of a knob shows.
 *
 *												The summary is the half a picture cannot give: a
 *												preview says what a design looks like and nothing
 *												about which selection produced it, so the checks here
 *												read it back after every kind of change the screen
 *												allows - a knob moved, another template chosen, a font
 *												changed, another tab opened. The words of the steps are
 *												read off the buttons that are drawn.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node. design-smoke.php runs it through
 *												node when node is on the path, so bin/check.sh and
 *												CI cover it - and hands it the answer design/list
 *												really gave (DESIGN_LISTING), which the screen is
 *												then drawn from as well; it also runs on its own.
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

/*	The panel's own words, read out of text/en_US.php as the workbench reads
	them - one entry to a line, a quote escaped with a backslash. A key with no
	entry answers as itself, which is what the workbench does with a fill nobody
	wrote, so a text the script asks for and the file lacks shows up as a key on
	the screen and in the checks below	*/
const TEXT = ( function() {

	const texts = {};
	const line = /^\s*'\[\[(\/[^\]]+)\]\]'\s*=>\s*'((?:[^'\\]|\\.)*)'\s*,?\s*$/gm;
	const file = fs.readFileSync( path.join( __dirname, '../text/en_US.php' ), 'utf8' );
	let found;

	while( ( found = line.exec( file ) ) !== null )
		texts[found[1]] = found[2].replace( /\\(['\\])/g, '$1' );

	return texts;
} )();

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
		removeEventListener	: function( type, fn ) { this.listeners[type] = ( this.listeners[type] || [] ).filter( function( listener ) { return listener !== fn } ) },
		// A handler is called on the element it was put on, as a browser does: the colour inputs read this.value
		fire						: function( type ) {
			const self = this;
			( this.listeners[type] || [] ).slice().forEach( function( fn ) { fn.call( self, { preventDefault : function() {} } ) } );
		},
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
	// The box an element stands in, as the scaler of the frame asks for it: null where it is not mounted
	Object.defineProperty( el, 'parentNode', { get : function() { return el.parent } } );
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

/** The value a select shows - the option marked selected, as a browser's select.value would say */
function shown( select ) {
	const on = byTag( select, 'option' ).filter( function( option ) { return option.selected === true } )[0];
	return on === undefined ? null : on.value;
}

/** What a select offers, as [ value, label ] pairs */
function offered( select ) {
	return byTag( select, 'option' ).map( function( option ) { return [ option.value, option.textContent ] } );
}

/** Choose a value in a select, the way a hand does */
function choose( select, value ) {
	select.value = value;
	select.fire('change');
}

/** Type a value into a field, the way a hand does: the field has it, and says that it changed */
function type( input, text ) {
	input.value = text;
	input.fire('change');
}

/** The name a knob row carries: the first line of the box that stands beside its icon */
function rowName( row ) {
	return ( byClass( row, 'design-knob-text' )[0] || {} ).textContent;
}

/** Run a function and say whether it threw */
function threw( fn ) {
	try {
		fn();
		return false;
	}
	catch( e ) {
		return true;
	}
}

const TABS = [ 'global', 'fonts', 'content', 'buttons', 'components', 'colours' ];

const KNOBS = {
	content 		: [ 'hero-title', 'hero-text', 'quiet', 'loud', 'section-space', 'card-space', 'corners', 'border', 'shadow' ],
	buttons 		: [ 'small', 'big', 'space', 'corners' ],
	components 	: [ 'scale', 'space', 'corners', 'border', 'zebra', 'labels' ],
};

/** A list without some of its items */
function without( list, drop ) {
	return list.filter( function( item ) { return drop.indexOf( item ) === -1 } );
}

/** One part, as design/list answers it: the set that is chosen and the catalogue it is chosen from */
function part( name, kind, set, catalogue ) {
	return { part : name, kind : kind, set : set, catalogue : catalogue };
}

/** The whole answer of design/list, with whatever a test wants different in it */
function listing( over ) {

	const roles = [ 'title', 'subtitle', 'text', 'button', 'code' ];
	const fonts = {};
	const defaults = {
		title 		: { family : 'league-spartan', weight : 'bold', uppercase : false, size : '' },
		subtitle 	: { family : 'source-sans-3', weight : 'normal', uppercase : false, size : '' },
		text 			: { family : 'source-sans-3', weight : 'normal', uppercase : false, size : '' },
		button 		: { family : 'source-sans-3', weight : 'bold', uppercase : false, size : '' },
		code 			: { family : '', weight : 'normal', uppercase : false, size : '' },
	};
	roles.forEach( function( role ) { fonts[role] = Object.assign( {}, defaults[role] ) } );

	const knobs = {};
	Object.keys( KNOBS ).forEach( function( tab ) {
		knobs[tab] = {};
		KNOBS[tab].forEach( function( knob ) { knobs[tab][knob] = 0 } );
	} );

	return Object.assign( {
		parts : [
			part( 'header', 'frame', 'v1', {
				v1 : { name : 'Plain bar', description : 'A bar with a rule under it' },
				v2 : { name : 'Centred', description : 'The logo in the middle' },
			} ),
			part( 'footer', 'frame', 'v1', {
				v1 : { name : 'Plain footer', description : 'Two columns' },
				v2 : { name : 'Dark footer', description : 'On the dark ground' },
			} ),
			part( 'content', 'set', 'v1', {
				v1 : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.content.slice() },
				v2 : { name : 'Poster', description : 'Large type, no shadow', knobs : without( KNOBS.content, [ 'shadow' ] ) },
			} ),
			part( 'buttons', 'set', 'v1', {
				v1 : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.buttons.slice() },
				v3 : { name : 'Square', description : 'No corners', knobs : without( KNOBS.buttons, [ 'corners' ] ) },
			} ),
			part( 'components', 'set', 'v1', {
				v1 : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.components.slice() },
				v4 : { name : 'Filled', description : 'Fields on a ground', knobs : without( KNOBS.components, [ 'border', 'labels' ] ) },
			} ),
		],
		tabs 			: { content : KNOBS.content.slice(), buttons : KNOBS.buttons.slice(), components : KNOBS.components.slice() },
		knobs 		: knobs,
		steps 		: [ 'less', 'default', 'more' ],
		size 			: 'm',
		sizes 		: [ 'xs', 's', 'm', 'l', 'xl' ],
		width 		: 0,
		motion 		: { effect : '', strength : '', speed : '' },
		effects 	: [ 'zoom', 'zoom-out', 'slide-left', 'slide-right', 'blur', 'flip' ],
		strengths : [ 'soft', 'medium', 'hard' ],
		speeds 		: [ 'fast', 'medium', 'slow' ],
		roles 		: roles,
		fonts 		: fonts,
		families 	: {
			'source-sans-3' 	: { name : 'Source Sans 3', category : 'sans', system : false },
			'league-spartan' 	: { name : 'League Spartan', category : 'sans', system : false },
			'system-sans' 		: { name : 'System sans-serif', category : 'sans', system : true },
			'system-serif' 		: { name : 'System serif', category : 'serif', system : true },
			'system-mono' 		: { name : 'System monospace', category : 'mono', system : true },
		},
		options 	: {
			title 		: [ '', 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ],
			subtitle 	: [ '', 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ],
			text 			: [ '', 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ],
			button 		: [ '', 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ],
			code 			: [ '' ],
		},
		weights 	: [ 'light', 'normal', 'bold' ],
		bounds 		: { title : [ 1, 4 ], subtitle : [ 0.8, 2.5 ], text : [ 0.8, 1.5 ], button : [ 0.6, 1.5 ], code : [ 0.6, 1.3 ] },
		inherited : { title : '1.6', subtitle : '1.125', text : '1', button : '1', code : '0.875' },
		colours		: { primary : '#4faae8', secondary : '', mode : 'auto' },
		palette		: {
			harmony			: { 'default' : 1, steps : [ 'Monochrome', 'Analogous', 'Triadic', 'Complementary' ] },
			temperature	: { 'default' : 3, steps : [ 'Neutral', 'Cool', 'Brand', 'Warm' ] },
			saturation	: { 'default' : 2, steps : [ 'Muted', 'Default', 'Rich' ] },
			contrast		: { 'default' : 2, steps : [ 'Soft', 'Default', 'Strong' ] },
			depth				: { 'default' : 2, steps : [ 'Flat', 'Default', 'Raised' ] },
			mode				: { 'default' : 'auto', steps : [ 'auto', 'light', 'dark' ] },
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
 *	The page in the frame, as far as the panel reaches into it: the elements
 *	it asks for by id - the sheet it swaps and the anchors it scrolls to - and
 *	the window it scrolls. Where each anchor stands is the test's to say.
 */
function frameStage() {

	const stage = { scrolls : [], asked : [], reachable : true, scrollY : 100, clearance : 0, tops : { type : 700, content : 300, cards : 900, buttons : 1200, components : 1500, colours : 450 } };

	stage.style = element('style');
	// The room main keeps at its top for the fixed header, which the header template of the page sets: none until a test says
	stage.main = {};
	// The elements in view that carry a viewport animation, as the page's own script has marked them
	stage.animated = [];
	// The page's fonts, as the page reports them: null where it has no FontFaceSet to ask
	stage.fonts = null;
	stage.document = {
		body 							: {},
		documentElement 	: { scrollTop : 0 },
		querySelector			: function( selector ) { return selector === 'main' ? stage.main : null },
		querySelectorAll	: function( selector ) { return selector === '.nino-vpa--visible' ? stage.animated : [] },
		getElementById		: function( id ) {
			stage.asked.push( id );
			if( id === 'design-preview-css' )
				return stage.style;
			return stage.tops[id] === undefined && id !== 'global' ? null : { getBoundingClientRect : function() { return { top : stage.tops[id] || 0 } } };
		},
		defaultView : {
			scrollTo : function( options ) { stage.scrolls.push( options ) },
			getComputedStyle : function( node ) { return node === stage.main ? { paddingTop : stage.clearance+ 'px' } : {} },
		},
	};
	Object.defineProperty( stage.document, 'fonts', { get : function() { return stage.fonts } } );
	Object.defineProperty( stage.document.defaultView, 'scrollY', { get : function() { return stage.scrollY } } );

	return stage;
}

/**
 *	An element of the page in the frame that carries a viewport animation and
 *	stands in view: what is done to its classes, and the transition it had
 *	at the time
 */
function animated( transition ) {

	const el = { style : { transition : transition || '' }, log : [] };

	el.classList = {
		remove	: function() { el.log.push( [ 'remove', Array.prototype.slice.call( arguments ).join(), el.style.transition ] ) },
		add			: function() { el.log.push( [ 'add', Array.prototype.slice.call( arguments ).join(), el.style.transition ] ) },
	};

	return el;
}

/**
 *	The panel's screen, drawn out of one design/list answer.
 *
 *	Every request the script makes goes through _apiCall(), which is held here
 *	the way the other feature tests hold it - and the preview behind it is
 *	debounced, so a stand-in timer that never fires is exactly what a test of
 *	the screen wants: what is asserted on is what the click wrote, not what an
 *	answer wrote back. A preview is asked for by hand (_previewNow()) where a
 *	test wants to read what it carries.
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
 *															workbench) and { dirty } for the shell's Nino.admin.dirty;
 *															whole: true takes `over` as the whole answer and fills nothing in
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

	// What the frame shows: its own page, which the panel reaches into
	const stage = frameStage();

	const dc = {
		documentElement	: element('html'),
		body						: element('body'),
		createElement		: function( tag ) {
			const el = element( tag );
			// The frame's page, or the refusal a browser gives for one it may not reach
			if( tag === 'iframe' )
				Object.defineProperty( el, 'contentDocument', { get : function() {
					if( stage.reachable === false )
						throw new Error('Blocked a frame from accessing a cross-origin frame');
					return stage.document;
				} } );
			return el;
		},
		// The elements of an icon: the same stand-in, which says in which namespace it was made
		createElementNS	: function( namespace, tag ) {
			const el = element( tag );
			el.namespaceURI = namespace;
			return el;
		},
		getElementById	: function( id ) { return byId( id ) },
	};

	const requests = [];

	// The widths the frame was asked to be fitted at, in order - what the stand-in of scaleFrame() records
	const fitted = [];

	/*	What the browser's own question does here: the text it was asked with is
		kept, and the answer is whatever the test last said it would be - yes	*/
	const confirms = [];
	let confirmAnswer = true;

	const Nino = {
		adminUi : {
			actionBar		: function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
			// Records what it was asked to fit: the frame, the port and the layout width
			scaleFrame	: function( frame, port, width ) { fitted.push( { frame : frame, port : port, width : width } ); return function() {} },
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
				// The kernel's own: the key is the data-key of the input, and the label is the copy beside the track
				if( options.key )
					input.dataset.key = options.key;
				label.appendChild( input );
				const copy = element('span');
				copy.className = 'nino-admin-switch-copy';
				copy.textContent = options.label;
				label.appendChild( copy );
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
			requests.push( { endpoint : endpoint, payload : JSON.parse( JSON.stringify( payload ) ), answer : callback } );
		};

	design._data	= ( shell || {} ).whole === true ? over : listing( over );
	design._edit	= design._selection( design._data );
	design._ready	= true;
	design._render();

	return {
		design 	: design,
		requests: requests,
		fitted	: fitted,
		confirms: confirms,
		stage		: stage,
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
		/** One knob row of the open tab, by the name it carries */
		knobRow : function( label ) {
			return byClass( form, 'design-knob-row' ).filter( function( row ) {
				return rowName( row ) === label;
			} )[0] || null;
		},
		/** The step buttons of a knob row, by the step they stand for */
		steps : function( label ) {
			const row = this.knobRow( label );
			return row === null ? [] : byTag( row, 'button' );
		},
		/** Move a knob the way a hand does */
		move : function( label, step ) {
			this.steps( label ).filter( function( button ) {
				return button.dataset.step === step;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** Open another tab, by the word on it, wherever its strip stands */
		tab : function( label ) {
			byTag( byClass( pane, 'design-tabs' )[0], 'button' ).filter( function( button ) {
				return button.textContent === label;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** The controls of the open tab: the box under the eyebrow */
		body : function() { return byId('design-body') },
		byId : byId,
	};
}

/** A text of the panel with its %s filled in, the way the script's own _text() does */
function say( key ) {
	let out = TEXT[key];
	Array.prototype.slice.call( arguments, 1 ).forEach( function( value ) { out = out.replace( /%[sdn]/, function() { return String( value ) } ) } );
	return out;
}

/** Which tab the controls are those of, read off what is drawn rather than off the panel's own idea of it */
function tabOf( screen ) {

	if( screen.byId('design-colours') !== null )
		return 'colours';

	if( byTag( screen.form, 'table' ).length === 1 )
		return 'fonts';

	if( screen.byId('design-set-header') !== null )
		return 'global';

	return [ 'content', 'buttons', 'components' ].filter( function( tab ) { return screen.byId('design-set-'+ tab) !== null } )[0] || null;
}

/** A key pressed on a tab, the way the kernel's tabKeys() hears it */
function press( button, key ) {
	( button.listeners.keydown || [] ).forEach( function( fn ) { fn( { key : key, preventDefault : function() {} } ) } );
}

check( 'the stand-in reads the panel\'s words out of text/en_US.php', Object.keys( TEXT ).length > 200 && TEXT['/_admin/design/tab/global'] === 'Global'
	&& TEXT['/_admin/design/hint/unanswered'].indexOf('%s') !== -1 );


// --- The pane's head -------------------------------------------------------------
//
// The panel's name is the head's - the row the shell renders over every pane -
// so the screen under it draws no heading of its own. The strip of the six tabs
// stands in that row beside the name, where the Features panel's does: it
// switches the column of controls and nothing else, and the frame beside the
// column stays on every tab

console.log( 'The head' );

const headed = panel();
const strip = headed.headStrips()[0] || null;
const tabs = strip === null ? [] : byTag( strip, 'button' );

check( 'the strip has six tabs - Global, Fonts, Content, Buttons, Components and Colours, in that order',
	headed.design.TABS.join() === TABS.join() && tabs.map( function( tab ) { return tab.textContent } ).join() === 'Global,Fonts,Content,Buttons,Components,Colours' );
check( '...a tablist of tabs, the first of them selected and the others not',
	strip !== null && strip.getAttribute('role') === 'tablist' && tabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' } )
	&& tabs.map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false,false,false,false,false' );
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

const shownStrip = headed.headStrips()[0] || null;
headed.tab('Colours');

check( 'another tab redraws the column under the head and leaves the strip standing - the same element, its tab lit',
	shownStrip !== null && headed.headStrips()[0] === shownStrip && headed.headStrips().length === 1
	&& byTag( shownStrip, 'button' )[5].getAttribute('aria-selected') === 'true' && byTag( shownStrip, 'button' )[0].getAttribute('aria-selected') === 'false'
	&& tabOf( headed ) === 'colours' && headed.byId('design-frame') !== null );

/*	The keyboard is the reason the strip is not drawn again on a switch. An
	arrow key moves the focus to the next tab and opens it; a strip drawn anew
	with the column took that tab out of the document, and the focus fell onto
	the page, so the next arrow key had nowhere to start from	*/
const keyed = panel();
const keyTabs = byTag( keyed.headStrips()[0] || element('div'), 'button' );

focused = null;
if( keyTabs.length === 6 )
	press( keyTabs[0], 'ArrowRight' );

check( 'an arrow key on the strip opens the next tab and the focus stays on the tab it moved to, in the head',
	keyTabs.length === 6 && focused === keyTabs[1] && keyTabs[1].parent === keyed.headStrips()[0]
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'fonts' );

if( keyTabs.length === 6 )
	press( keyTabs[1], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === 6 && focused === keyTabs[0] && keyTabs[0].parent === keyed.headStrips()[0]
	&& keyTabs[0].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'global' );

if( keyTabs.length === 6 )
	press( keyTabs[0], 'ArrowLeft' );

check( '...and from the first tab the arrow key wraps round to the last', keyTabs.length === 6 && focused === keyTabs[5] && tabOf( keyed ) === 'colours' );

/*	A kernel from before the head, or the script drawn outside its pane: the
	strip has nowhere else to go, so it opens the column the way it did - and
	the heading stays gone there as well	*/
const bare = panel( {}, { head : false } );
const opened = hasClass( ( bare.byId('design-controls') || element('div') ).children[0] || element('div'), 'design-tabs' );
bare.tab('Colours');

check( 'where the pane has no head, the strip opens the column and comes back with it on a switch - with no heading over it',
	opened === true && hasClass( bare.byId('design-controls').children[0], 'design-tabs' ) && byClass( bare.pane, 'design-tabs' ).length === 1
	&& tabOf( bare ) === 'colours' && byTag( bare.form, 'h2' ).length === 0 );

console.log('');


// --- The shape of the column -----------------------------------------------------
//
// Controls on one side and the page on the other: the open tab in the column,
// the frame and the summary in the preview, the action bar under both

console.log( 'The column' );

const column = panel();
const layout = column.byId('design-layout');
const controls = column.byId('design-controls');
const previewBox = column.byId('design-preview');

check( 'the screen is a layout of two columns - the controls and the preview - with the bar of actions under it',
	layout !== null && layout.children.length === 2 && layout.children[0] === controls && layout.children[1] === previewBox
	&& column.form.children.indexOf( layout ) < column.form.children.indexOf( byClass( column.form, 'nino-admin-actionbar' )[0] ) );
check( 'the column holds the box the open tab fills and nothing over it: no card for the state of the files, no eyebrow naming the group of controls',
	controls.children.length === 1 && controls.children[0] === column.body()
	&& column.byId('design-state') === null && byClass( column.form, 'design-state' ).length === 0 && byClass( controls, 'design-eyebrow' ).length === 0 );
check( '...the one eyebrow the screen has is the preview\'s, over the title of the frame',
	byClass( column.form, 'design-eyebrow' ).length === 1 && byClass( previewBox, 'design-eyebrow' )[0].textContent === TEXT['/_admin/design/preview/eyebrow']
	&& byClass( previewBox, 'design-eyebrow' )[0].textContent === 'This selection' );
check( 'the frame and the summary stand in the preview, the summary under the frame',
	column.byId('design-frame') !== null && descendants( previewBox ).indexOf( column.byId('design-summary') ) > descendants( previewBox ).indexOf( column.byId('design-frame') ) );
check( '...the frame a sandbox that lets the page run and be reached by the panel, and no more',
	column.byId('design-frame').getAttribute('sandbox') === 'allow-scripts allow-same-origin' );

/*	What the state card said is the last row of the summary now, in one word, and it
	is the same under every tab: the file answers to the selection, the draft is
	not applied, a file is not Design's, or it was never applied	*/
const stateWord = function( over ) { return panel( over ).summary().pop() };
const foreignFiles = [ { target : '/assets/theme.css', exists : true, state : 'foreign' }, { target : '/templates/frame-header.tpl', exists : true, state : 'ours' }, { target : '/templates/frame-footer.tpl', exists : true, state : 'ours' } ];

check( 'the state of the files is the last row of the summary under the frame, in one word: up to date while the file answers to the selection',
	JSON.stringify( column.summary().pop() ) === JSON.stringify( [ 'Applied file', 'Up to date' ] ) && column.design._stateKey() === 'current' );
check( '...saved and not applied once the file does not, a file Design did not write named as such, and a file never applied as that',
	JSON.stringify( stateWord( { current : false } ) ) === JSON.stringify( [ 'Applied file', 'Saved, not applied' ] )
	&& JSON.stringify( stateWord( { files : foreignFiles } ) ) === JSON.stringify( [ 'Applied file', 'Not written by Design' ] )
	&& JSON.stringify( stateWord( { exists : false, compiled : '', current : false } ) ) === JSON.stringify( [ 'Applied file', 'Never applied' ] ) );

const told = panel( { notes : [ 'there is no part "nowhere" - dropped' ] } );

check( 'what the server dropped or replaced on the way is told at the top of the column, over the open tab',
	byClass( told.byId('design-controls'), 'nino-admin-error' ).map( function( line ) { return line.textContent } ).join() === 'there is no part "nowhere" - dropped'
	&& told.byId('design-controls').children.indexOf( byClass( told.byId('design-controls'), 'nino-admin-error' )[0] ) === 0
	&& told.byId('design-controls').children[1] === told.body() );

console.log('');

// --- Global: the templates, the page and its animation ------------------------------

console.log( 'Global' );

const global = panel();
const setSelect = function( name ) { return global.byId('design-set-'+ name) };

check( 'the Global tab offers the two frames as two selects - the header and the footer, each over its catalogue, on the one that is chosen - and none of the three templates of the knob tabs',
	setSelect('header') !== null && setSelect('footer') !== null
	&& JSON.stringify( offered( setSelect('header') ) ) === JSON.stringify( [ [ 'v1', 'v1 - Plain bar' ], [ 'v2', 'v2 - Centred' ] ] )
	&& JSON.stringify( offered( setSelect('footer') ) ) === JSON.stringify( [ [ 'v1', 'v1 - Plain footer' ], [ 'v2', 'v2 - Dark footer' ] ] )
	&& [ 'header', 'footer' ].map( function( name ) { return shown( setSelect( name ) ) } ).join() === 'v1,v1'
	&& setSelect('content') === null && setSelect('buttons') === null && setSelect('components') === null
	&& byTag( global.body(), 'select' ).filter( function( select ) { return /^design-set-/.test( select.id ) } ).length === 2 );
check( '...named in the words of the panel, with the description of the chosen one under it - and no sentence under them about the templates that are chosen elsewhere',
	byTag( global.body(), 'label' ).map( function( label ) { return label.textContent } ).slice( 0, 2 ).join() === 'Header template,Footer template'
	&& byTag( setSelect('header').parent, 'small' )[0].textContent === 'A bar with a rule under it'
	&& byTag( setSelect('header').parent.parent, 'p' ).length === 0 && byClass( global.body(), 'design-note' ).length === 0 );

const headerField = setSelect('header').parent;
focused = null;
choose( setSelect('header'), 'v2' );

check( 'choosing another one is the selection on screen, and the note under it follows the choice',
	global.design._edit.parts.header === 'v2' && byTag( setSelect('header').parent, 'small' )[0].textContent === 'The logo in the middle'
	&& global.byId('design-reset').hidden === false );
check( '...a frame changes the document and not the card: the field and its select stand where they were, and the focus is not moved',
	setSelect('header').parent === headerField && descendants( global.pane ).indexOf( setSelect('header') ) !== -1 && focused === null );

check( 'the size is one select of five - very small, smaller, default, larger, very large - and the width of the page another: narrow, default, wide, as the numbers the model keeps',
	JSON.stringify( offered( global.byId('design-size') ) ) === JSON.stringify( [ [ 'xs', 'very small' ], [ 's', 'smaller' ], [ 'm', 'default' ], [ 'l', 'larger' ], [ 'xl', 'very large' ] ] ) && shown( global.byId('design-size') ) === 'm'
	&& JSON.stringify( offered( global.byId('design-pagewidth') ) ) === JSON.stringify( [ [ '-1', 'narrow' ], [ '0', 'default' ], [ '1', 'wide' ] ] ) && shown( global.byId('design-pagewidth') ) === '0' );
check( '...the old words are gone - nothing is called small or large any more - and the hint under each says what is true now: the five percentages below 768 px and 1.125 times that from it, and 56 rem, 120 rem and the edge',
	offered( global.byId('design-size') ).every( function( option ) { return [ 'small', 'large' ].indexOf( option[1] ) === -1 } )
	&& byTag( global.byId('design-size').parent, 'small' )[0].textContent === TEXT['/_admin/design/hint/size'] && TEXT['/_admin/design/hint/size'].indexOf('70, 85, 100, 115 or 130 percent') !== -1
	&& TEXT['/_admin/design/hint/size'].indexOf('below 768 px and 1.125 times that from it') !== -1
	&& byTag( global.byId('design-pagewidth').parent, 'small' )[0].textContent === 'Narrow keeps the content at 56 rem, normal at 120 rem, wide lets it run to the edge.' );

check( '...and the words of both selects stay lower case, as the words of a select do - the three words of a knob are the capitalised ones, and the options of the size and of the width are not',
	offered( global.byId('design-size') ).concat( offered( global.byId('design-pagewidth') ) ).every( function( option ) { return option[1].charAt(0) === option[1].charAt(0).toLowerCase() } ) );

choose( global.byId('design-size'), 'xl' );
choose( global.byId('design-pagewidth'), '1' );

check( 'choosing them stores a size and a number',
	global.design._edit.size === 'xl' && global.design._edit.width === 1 );
check( '...each of the five can be chosen, and each is what the model keeps as it was chosen',
	[ 'xs', 's', 'm', 'l', 'xl' ].every( function( size ) { choose( global.byId('design-size'), size ); return global.design._edit.size === size } ) );
check( '...and the summary under the frame says each of them in its word, the same the select has: very small, smaller, default, larger, very large',
	[ [ 'xs', 'very small' ], [ 's', 'smaller' ], [ 'm', 'default' ], [ 'l', 'larger' ], [ 'xl', 'very large' ] ].every( function( entry ) {
		choose( global.byId('design-size'), entry[0] );
		return global.summary().filter( function( row ) { return row[0] === 'Root size' } ).map( function( row ) { return row[1] } ).join() === entry[1];
	} ) );

choose( global.byId('design-size'), 'xl' );

const effect = global.byId('design-motion-effect');
const strength = global.byId('design-motion-strength');
const speed = global.byId('design-motion-speed');

check( 'the animation is three selects: the effect - which starts on what Nino.css does by itself - the strength and the speed',
	JSON.stringify( offered( effect ) ) === JSON.stringify( [ [ '', 'Nino\'s default' ], [ 'zoom', 'Zoom' ], [ 'zoom-out', 'Zoom out' ], [ 'slide-left', 'From the right' ], [ 'slide-right', 'From the left' ], [ 'blur', 'Blur' ], [ 'flip', 'Flip' ] ] )
	&& JSON.stringify( offered( strength ) ) === JSON.stringify( [ [ '', 'Nino\'s default' ], [ 'soft', 'Soft' ], [ 'medium', 'Medium' ], [ 'hard', 'Hard' ] ] )
	&& JSON.stringify( offered( speed ) ) === JSON.stringify( [ [ '', 'Nino\'s default' ], [ 'fast', 'Fast' ], [ 'medium', 'Medium' ], [ 'slow', 'Slow' ] ] )
	&& shown( effect ) === '' );
check( '...and the strength and the speed are switched off while there is no effect for them to belong to',
	strength.disabled === true && speed.disabled === true && effect.disabled === false );

choose( effect, 'blur' );

check( '...an effect switches them on', strength.disabled === false && speed.disabled === false && global.design._edit.motion.effect === 'blur' );

choose( strength, 'hard' );
choose( speed, 'slow' );

check( '...and what they are set to is the selection',
	JSON.stringify( global.design._edit.motion ) === JSON.stringify( { effect : 'blur', strength : 'hard', speed : 'slow' } ) );
check( 'the summary under the frame names every choice of the tab - the five templates, the size, the width and the animation',
	JSON.stringify( global.summary() ) === JSON.stringify( [
		[ 'Header template', 'v2 - Centred' ],
		[ 'Footer template', 'v1 - Plain footer' ],
		[ 'Content template', 'v1 - Nino' ],
		[ 'Buttons template', 'v1 - Nino' ],
		[ 'Components template', 'v1 - Nino' ],
		[ 'Root size', 'very large' ],
		[ 'Page width', 'wide' ],
		[ 'Animation', 'Blur, Hard, Slow' ],
		[ 'Applied file', 'Up to date' ],
	] ) );

choose( effect, '' );

check( 'no effect again is Nino\'s default again, and the other two are switched off with it',
	strength.disabled === true && speed.disabled === true && global.summary()[7][1] === 'Nino\'s default' );

console.log('');


// --- The knob tabs ----------------------------------------------------------------
//
// A tab for each of the three templates that have knobs: the select the template
// is chosen in at the top of the card, and one row for every knob the tab has
// under it - each at less, the template as it is, or more, in the words that knob
// has for them, and with the icon of what it sets. A knob the chosen template does
// not answer to stands where it always does, greyed out, with the line that says
// whose decision that is

console.log( 'The knob tabs' );

const moved = listing().knobs;
moved.content.shadow = 1;
moved.buttons.corners = -1;

const rows = panel( { knobs : moved } );
rows.tab('Content');

const contentRows = byClass( rows.form, 'design-knob-row' );

check( 'the Content tab has a row for each of its nine knobs, named in the panel\'s words and in the order of the tab',
	tabOf( rows ) === 'content' && contentRows.length === 9
	&& contentRows.map( rowName ).join()
		=== KNOBS.content.map( function( knob ) { return TEXT['/_admin/design/knob/content/'+ knob+ '/label'] } ).join()
	&& contentRows.map( function( row ) { return row.dataset.knob } ).join() === KNOBS.content.join() );

/*	The template of a tab is chosen on the tab: the same select as it was on
	Global, with the same id, at the top of the card - and the rows after it	*/
const templateFirst = [ [ 'Content', 'content', 9 ], [ 'Buttons', 'buttons', 4 ], [ 'Components', 'components', 6 ] ].every( function( entry ) {

	rows.tab( entry[0] );

	const card = rows.byId('design-knobs');
	const select = rows.byId('design-set-'+ entry[1]);

	return select !== null && card.children[0] === select.parent && hasClass( card.children[0], 'design-field' ) && card.children.length === entry[2] + 1
		&& card.children.slice( 1 ).every( function( row, at ) { return hasClass( row, 'design-knob-row' ) && row.dataset.knob === KNOBS[entry[1]][at] } )
		&& byTag( card.children[0], 'label' )[0].textContent === TEXT['/_admin/design/part/'+ entry[1]] && tabOf( rows ) === entry[1]
		&& shown( select ) === 'v1' && byTag( card.children[0], 'small' )[0].textContent === 'The framework as it is';
} );
rows.tab('Content');

check( 'each of the three knob tabs offers its template first, in a select named in the panel\'s words with the description of the chosen one under it, and the rows of its knobs after it',
	templateFirst === true && JSON.stringify( offered( rows.byId('design-set-content') ) ) === JSON.stringify( [ [ 'v1', 'v1 - Nino' ], [ 'v2', 'v2 - Poster' ] ] ) );
check( '...each with a note beside its name, and three positions in the words that knob has for them',
	contentRows.every( function( row, at ) {
		const knob = KNOBS.content[at];
		return ( byTag( row, 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/'+ knob+ '/note']
			&& byTag( row, 'button' ).map( function( button ) { return button.textContent } ).join() === [ 'less', 'default', 'more' ].map( function( step ) {
				return TEXT['/_admin/design/knob/content/'+ knob+ '/'+ step];
			} ).join();
	} ) );
/*	The three words of a knob begin with a capital letter, as the words of the Colours tab do. Read off the buttons that are
	drawn - their text and their title - and not off the table they come from, so that a word that goes back to lower case is
	seen. The middle word is Default for every knob but the two that have a middle of their own: the stripes of a table (Rows,
	between Off and Rows with a line) and the labels of a form (Above, between In the row and Floating)	*/
const STEP_WORDS = {
	content : {
		'hero-title' : 'Smaller,Default,Larger', 'hero-text' : 'Smaller,Default,Larger', quiet : 'Smaller,Default,Larger', loud : 'Smaller,Default,Larger',
		'section-space' : 'Tight,Default,Wide', 'card-space' : 'Tight,Default,Wide', corners : 'Sharp,Default,Round', border : 'None,Default,Thick', shadow : 'None,Default,Strong',
	},
	buttons : { small : 'Smaller,Default,Larger', big : 'Smaller,Default,Larger', space : 'Tight,Default,Wide', corners : 'Sharp,Default,Round' },
	components : {
		scale : 'Smaller,Default,Larger', space : 'Tight,Default,Wide', corners : 'Sharp,Default,Round', border : 'None,Default,Thick',
		zebra : 'Off,Rows,Rows with a line', labels : 'In the row,Above,Floating',
	},
};
const wordProblems = [];
let wordRows = 0;
const worded = panel();

[ [ 'Content', 'content' ], [ 'Buttons', 'buttons' ], [ 'Components', 'components' ] ].forEach( function( entry ) {

	worded.tab( entry[0] );

	byClass( worded.form, 'design-knob-row' ).forEach( function( row ) {

		const buttons = byTag( row, 'button' );
		const drawn = buttons.map( function( button ) { return button.textContent } ).join();

		wordRows++;

		if( drawn !== STEP_WORDS[entry[1]][row.dataset.knob] || buttons.map( function( button ) { return button.title } ).join() !== drawn
			|| buttons.some( function( button ) { return button.textContent.charAt(0) !== button.textContent.charAt(0).toUpperCase() } ) )
			wordProblems.push( entry[1]+ '/'+ row.dataset.knob+ ': '+ drawn );
	} );
} );

check( 'the three words of every knob begin with a capital letter, on the buttons and in their titles, nineteen knobs in all - the same words as before, nothing else of them changed'
	+ ( wordProblems.length === 0 ? '' : ' - not so for '+ wordProblems.join(' | ') ), wordRows === 19 && wordProblems.length === 0 );
check( '...and the middle word is Default for every knob but the stripes of a table and the labels of a form, which keep the middle of their own: Rows and Above',
	Object.keys( STEP_WORDS ).every( function( tab ) {
		return Object.keys( STEP_WORDS[tab] ).every( function( knob ) {
			return STEP_WORDS[tab][knob].split(',')[1] === ( [ 'zebra', 'labels' ].indexOf( knob ) === -1 ? 'Default' : ( knob === 'zebra' ? 'Rows' : 'Above' ) );
		} );
	} ) );
check( '...a group of three steps, not a tablist - the one it stands at is pressed and the other two are not',
	contentRows.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& byTag( rows.knobRow('Hero title'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,true,false'
	&& byTag( rows.knobRow('Shadow of a card'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true' );
check( 'there is no heading and no hint over the rows any more: the select says which template these knobs are the knobs of',
	byClass( rows.byId('design-knobs'), 'design-section-label' ).length === 0 && byTag( rows.byId('design-knobs'), 'strong' ).length === 0
	&& byTag( rows.byId('design-knobs'), 'small' ).filter( function( small ) { return small.parent === rows.byId('design-knobs').children[0] } ).length === 1 );
check( 'a template that answers to every knob greys none of them out',
	byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& byTag( rows.byId('design-knobs'), 'button' ).every( function( button ) { return button.disabled === false } ) );

rows.move( 'Hero title', 'more' );

check( 'moving a knob is the selection on screen: the position is a number, the step it stands at is lit',
	rows.design._edit.knobs.content['hero-title'] === 1
	&& byTag( rows.knobRow('Hero title'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true'
	&& rows.byId('design-reset').hidden === false );

rows.move( 'Hero title', 'less' );
rows.move( 'Hero text', 'more' );

check( '...to less it is -1, and each knob keeps its own position',
	rows.design._edit.knobs.content['hero-title'] === -1 && rows.design._edit.knobs.content['hero-text'] === 1 && rows.design._edit.knobs.content.quiet === 0 );
/** The rows of the summary a knob tab gives, from the positions its knobs stand at: the label of each and the word of its step */
function knobSummary( tab, positions ) {
	return KNOBS[tab].filter( function( knob ) { return positions[knob] !== undefined } ).map( function( knob ) {
		return [ TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/label'], TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/'+ [ 'less', 'default', 'more' ][positions[knob] + 1]] ];
	} );
}

check( 'the summary names the template and, for every knob the template answers to, the word of the position it stands at',
	JSON.stringify( rows.summary() ) === JSON.stringify( [ [ 'Content template', 'v1 - Nino' ] ].concat(
		knobSummary( 'content', { 'hero-title' : -1, 'hero-text' : 1, quiet : 0, loud : 0, 'section-space' : 0, 'card-space' : 0, corners : 0, border : 0, shadow : 1 } ),
		[ [ 'Applied file', 'Up to date' ] ] ) ) );
check( '...in the capitalised words: Smaller for the title of the hero, Larger for its text, Default for the knobs nobody moved and Strong for the shadow',
	rows.summary().slice( 1, -1 ).map( function( row ) { return row.join(': ') } ).join()
		=== 'Hero title: Smaller,Hero text: Larger,Quiet variant: Default,Loud variant: Default,Space of a section: Default,Space of a card: Default,Corners of a card: Default,Line round a card: Default,Shadow of a card: Strong' );

/*	Another template brings its own knobs, and the ones the last one had are
	not this one's: a position the new template does not answer to goes back to
	the middle, the way the server holds it anyway - so the screen never shows a
	setup the stored one could not be	*/
const oldSelect = rows.byId('design-set-content');
const oldRows = byClass( rows.form, 'design-knob-row' );
focused = null;
choose( oldSelect, 'v2' );

check( 'choosing a template that leaves a knob out puts the position of that knob back in the middle, and leaves the others where they were',
	rows.design._edit.parts.content === 'v2' && rows.design._edit.knobs.content.shadow === 0
	&& rows.design._edit.knobs.content['hero-title'] === -1 && rows.design._edit.knobs.content['hero-text'] === 1 );
check( '...the rows of the tab are drawn again - nine new ones for the nine knobs - and the select is a new one on the template that was chosen, which has the focus afterwards',
	oldRows.length === 9 && oldRows.every( function( row ) { return descendants( rows.pane ).indexOf( row ) === -1 } )
	&& byClass( rows.form, 'design-knob-row' ).length === 9 && descendants( rows.pane ).indexOf( oldSelect ) === -1
	&& rows.byId('design-set-content') !== null && rows.byId('design-set-content') !== oldSelect && shown( rows.byId('design-set-content') ) === 'v2'
	&& focused === rows.byId('design-set-content') && tabOf( rows ) === 'content' );
check( '...and the line under the select describes the template that was chosen, the summary names it',
	byTag( rows.byId('design-set-content').parent, 'small' )[0].textContent === 'Large type, no shadow' && rows.summary()[0][1] === 'v2 - Poster' );

const poster = byClass( rows.form, 'design-knob-row' );
const shadow = rows.knobRow('Shadow of a card');

check( 'on the tab the knob the template does not answer to is still a row, greyed out - not hidden, so the tab is the same screen for every template',
	poster.length === 9 && shadow !== null && hasClass( shadow, 'design-knob-row--off' )
	&& poster.filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) } ).length === 1 );
check( '...its three steps switched off and none of them pressed',
	byTag( shadow, 'button' ).length === 3 && byTag( shadow, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& byTag( rows.knobRow('Hero title'), 'button' ).every( function( button ) { return button.disabled === false } ) );
check( '...and the line under its name says whose decision that is, in place of its note',
	( byTag( shadow, 'small' )[0] || {} ).textContent === 'This template does not answer to “Shadow of a card”.'
	&& ( byTag( rows.knobRow('Hero title'), 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/hero-title/note']
	&& shown( rows.byId('design-set-content') ) === 'v2' );
check( 'the summary leaves the knob out, since it compiles nothing: eight knobs, the template and the file',
	rows.summary().length === 10 && rows.summary().every( function( row ) { return row[0] !== 'Shadow of a card' } ) && rows.summary()[0][1] === 'v2 - Poster' );

choose( rows.byId('design-set-content'), 'v1' );

check( 'choosing the template that has it again takes the grey away, with the knob in the middle',
	byClass( rows.form, 'design-knob-row--off' ).length === 0 && rows.design._edit.knobs.content.shadow === 0
	&& byTag( rows.knobRow('Shadow of a card'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,true,false' );

// The other two tabs, and their own templates
rows.tab('Buttons');
check( 'the Buttons tab has its four knobs, and Square leaves the corners to the template - which the stored position cannot have been',
	tabOf( rows ) === 'buttons' && byClass( rows.form, 'design-knob-row' ).length === 4 && byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& rows.design._edit.knobs.buttons.corners === -1 );

choose( rows.byId('design-set-buttons'), 'v3' );

check( '...it is back in the middle, and the row is grey',
	rows.design._edit.knobs.buttons.corners === 0 && byClass( rows.form, 'design-knob-row--off' ).length === 1
	&& rows.knobRow('Corners') !== null && hasClass( rows.knobRow('Corners'), 'design-knob-row--off' ) );

rows.tab('Components');
choose( rows.byId('design-set-components'), 'v4' );

check( 'the Components tab has six rows, two of them grey for Filled: the lines and where the labels stand',
	tabOf( rows ) === 'components' && byClass( rows.form, 'design-knob-row' ).length === 6
	&& byClass( rows.form, 'design-knob-row--off' ).map( function( row ) { return row.dataset.knob } ).join() === 'border,labels'
	&& JSON.stringify( rows.summary().slice( 1, -1 ).map( function( row ) { return row[0] } ) ) === JSON.stringify( [ 'scale', 'space', 'corners', 'zebra' ].map( function( knob ) { return TEXT['/_admin/design/knob/components/'+ knob+ '/label'] } ) ) );

console.log('');

// --- Fonts: the five roles in a table ------------------------------------------------
//
// Five rows and four columns - the family, the weight, the case and the size.
// The controls stand bare in the cells and the words are in the column heads;
// each control still carries its role and its column as its accessible name

console.log( 'Fonts' );

const fonts = panel();
fonts.tab('Fonts');

const table = byTag( fonts.form, 'table' )[0] || element('table');
const fontRows = byTag( table, 'tbody' )[0] ? byTag( byTag( table, 'tbody' )[0], 'tr' ) : [];

check( 'the Fonts tab is one table, said to be one: a header of four columns over five rows, one for each role of Nino.css',
	tabOf( fonts ) === 'fonts' && table.getAttribute('role') === 'table'
	&& byTag( byTag( table, 'thead' )[0], 'th' ).map( function( th ) { return th.textContent } ).join() === ',Family,Weight,Uppercase,Size'
	&& byTag( byTag( table, 'thead' )[0], 'th' ).every( function( th ) { return th.getAttribute('role') === 'columnheader' } )
	&& fontRows.map( function( row ) { return row.dataset.role } ).join() === 'title,subtitle,text,button,code'
	&& fontRows.map( function( row ) { return byTag( row, 'th' )[0].textContent } ).join() === 'Title,Subtitle,Text,Buttons,Code'
	&& fontRows.every( function( row ) { return row.getAttribute('role') === 'row' && byTag( row, 'th' )[0].getAttribute('role') === 'rowheader' } ) );

const family = function( role ) { return fonts.byId('design-font-'+ role+ '-family') };
const weight = function( role ) { return fonts.byId('design-font-'+ role+ '-weight') };
const size = function( role ) { return fonts.byId('design-font-'+ role+ '-size') };

check( 'a family is a select of what the role may be given: the system\'s own first, a webface by its name, a system stack in the words of the panel',
	JSON.stringify( offered( family('title') ) ) === JSON.stringify( [ [ '', 'System (default)' ], [ 'source-sans-3', 'Source Sans 3' ], [ 'league-spartan', 'League Spartan' ],
		[ 'system-sans', 'System sans-serif' ], [ 'system-serif', 'System serif' ], [ 'system-mono', 'System monospace' ] ] )
	&& shown( family('title') ) === 'league-spartan' && shown( family('text') ) === 'source-sans-3' );
check( '...the code is offered the system\'s monospace and nothing else, by its own name',
	JSON.stringify( offered( family('code') ) ) === JSON.stringify( [ [ '', 'System monospace' ] ] ) && shown( family('code') ) === '' );
check( 'a weight is one of three words',
	JSON.stringify( offered( weight('title') ) ) === JSON.stringify( [ [ 'light', 'Light' ], [ 'normal', 'Normal' ], [ 'bold', 'Bold' ] ] ) && shown( weight('title') ) === 'bold' && shown( weight('code') ) === 'normal' );
check( 'a size is typed and not picked: a number field in each row - empty to begin with, stepping by a tenth - with the size Nino.css gives the role as its placeholder, the number alone, and the limits of the role',
	fontRows.every( function( row ) {
		const input = size( row.dataset.role );
		return input !== null && input.tagName === 'INPUT' && input.type === 'number' && input.getAttribute('step') === '.1' && input.getAttribute('inputmode') === 'decimal'
			&& input.dataset.role === row.dataset.role && input.dataset.key === 'size' && input.value === '';
	} )
	&& fonts.design.TABS.length === 6 && [ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).getAttribute('placeholder') } ).join() === '1.6,1.125,1,1,0.875'
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).getAttribute('min')+ '-'+ size( role ).getAttribute('max') } ).join() === '1-4,0.8-2.5,0.8-1.5,0.6-1.5,0.6-1.3'
	&& byTag( table, 'select' ).filter( function( select ) { return select.dataset.key === 'size' } ).length === 0 );
check( '...and the unit stands after it as a small word of its own, in the same cell',
	fontRows.every( function( row ) {
		const cell = size( row.dataset.role ).parent;
		const unit = byClass( cell, 'design-fonts-unit' )[0];
		return cell.tagName === 'TD' && hasClass( cell, 'design-fonts-size' ) && unit !== undefined && unit.textContent === 'rem'
			&& cell.children.indexOf( unit ) === cell.children.indexOf( size( row.dataset.role ) ) + 1;
	} ) );
check( '...every control names itself by its role and its column, since the cells carry no words',
	weight('title').getAttribute('aria-label') === 'Title - Weight' && family('code').getAttribute('aria-label') === 'Code - Family'
	&& size('button').getAttribute('aria-label') === 'Buttons - Size' );
check( 'the case is a switch for the four roles of running text, off to begin with - and the code has none, a dash in its place',
	[ 'title', 'subtitle', 'text', 'button' ].every( function( role ) { return fonts.byId('design-font-'+ role+ '-uppercase') !== null && fonts.byId('design-font-'+ role+ '-uppercase').checked === false } )
	&& fonts.byId('design-font-code-uppercase') === null && byClass( fontRows[4], 'design-fonts-none' )[0].textContent === '—' );

/*	Changing one thing of one role: through _setFont(), which stores it and
	asks for a preview - and the preview carries the role	*/
const setFontCalls = [];
const setFont = fonts.design._setFont;
fonts.design._setFont = function( role, key, value ) { setFontCalls.push( [ role, key, value ] ); return setFont.call( fonts.design, role, key, value ) };

choose( family('title'), 'system-serif' );
choose( weight('title'), 'light' );
type( size('title'), '2.4' );

const upper = fonts.byId('design-font-title-uppercase');
upper.checked = true;
upper.fire('change');

choose( family('subtitle'), '' );

check( 'a change goes through _setFont() with the role, the key and the value - the case as a boolean, the rest as the words the model keeps',
	JSON.stringify( setFontCalls ) === JSON.stringify( [ [ 'title', 'family', 'system-serif' ], [ 'title', 'weight', 'light' ], [ 'title', 'size', '2.4' ], [ 'title', 'uppercase', true ], [ 'subtitle', 'family', '' ] ] )
	&& JSON.stringify( fonts.design._edit.fonts.title ) === JSON.stringify( { family : 'system-serif', weight : 'light', uppercase : true, size : '2.4' } )
	&& fonts.design._edit.fonts.subtitle.family === '' && fonts.byId('design-reset').hidden === false );
check( 'the summary has a row for each role, in words: the family, the weight, the case where it is upper, the size',
	JSON.stringify( fonts.summary() ) === JSON.stringify( [
		[ 'Title', 'System serif, Light, Uppercase, 2.4 rem' ],
		[ 'Subtitle', 'System (default), Normal, 1.125 rem (default)' ],
		[ 'Text', 'Source Sans 3, Normal, 1 rem (default)' ],
		[ 'Buttons', 'Source Sans 3, Bold, 1 rem (default)' ],
		[ 'Code', 'System monospace, Normal, 0.875 rem (default)' ],
		[ 'Applied file', 'Up to date' ],
	] ) );

fonts.design._previewNow();
const fontPreview = fonts.requests[fonts.requests.length - 1];

check( 'the preview asked for carries the role that was changed, with the rest of the selection beside it - the frame is the answer to every control',
	fontPreview.endpoint === 'preview' && JSON.stringify( fontPreview.payload.fonts.title ) === JSON.stringify( { family : 'system-serif', weight : 'light', uppercase : true, size : '2.4' } )
	&& Object.keys( fontPreview.payload ).sort().join() === 'colours,fonts,full,knobs,motion,parts,size,width' && fontPreview.payload.full === true );

/*	A size is held inside the bounds of its role the way the server holds it
	(Fonts::size()): in the model, and in the field, which shows what was kept	*/
type( size('title'), '9' );
type( size('subtitle'), '0.2' );
type( size('text'), '1.1250' );
type( size('button'), '1.23456' );
type( size('code'), '2' );

check( 'a size beyond the bounds of its role is held at the nearer one - in the model, and in the field, which shows what was kept: 9 for the title is 4, 0.2 for the subtitle is 0.8, 2 for the code is 1.3',
	fonts.design._edit.fonts.title.size === '4' && size('title').value === '4' && fonts.design._edit.fonts.subtitle.size === '0.8' && size('subtitle').value === '0.8'
	&& fonts.design._edit.fonts.code.size === '1.3' && size('code').value === '1.3' );

// The bounds are sizes themselves, and one between two steps of the field is kept as it was typed
const atBounds = [];
type( size('button'), '0.59' );
atBounds.push( fonts.design._edit.fonts.button.size, size('button').value );
type( size('subtitle'), '0.8' );
atBounds.push( fonts.design._edit.fonts.subtitle.size );
type( size('subtitle'), '2.5' );
atBounds.push( fonts.design._edit.fonts.subtitle.size );
type( size('code'), '1.3' );
atBounds.push( fonts.design._edit.fonts.code.size, size('code').value );
type( size('text'), '1.25' );
atBounds.push( fonts.design._edit.fonts.text.size, size('text').value );

check( '...and the bounds are sizes themselves: .59 for the button is held at .6, .8 and 2.5 stay for the subtitle, 1.3 stays for the code - and 1.25, which is between two steps of the field, stays 1.25',
	atBounds.join() === '0.6,0.6,0.8,2.5,1.3,1.3,1.25,1.25' );

type( size('button'), '1.23456' );
type( size('text'), '1.1250' );
type( size('code'), '2' );
type( size('subtitle'), '0.2' );
check( '...a number is kept to three decimals and written as short as it can be: 1.1250 is 1.125, 1.23456 is 1.235 - and the summary says it in rem',
	fonts.design._edit.fonts.text.size === '1.125' && size('text').value === '1.125' && fonts.design._edit.fonts.button.size === '1.235' && size('button').value === '1.235'
	&& fonts.summary()[2][1] === 'Source Sans 3, Normal, 1.125 rem' && fonts.summary()[0][1] === 'System serif, Light, Uppercase, 4 rem' );

type( size('title'), '2.0' );
type( size('subtitle'), '' );
type( size('text'), 'abc' );

check( '...2.0 is 2; empty is the default again, and a text that is no number is the default too, with the field emptied',
	fonts.design._edit.fonts.title.size === '2' && size('title').value === '2' && fonts.design._edit.fonts.subtitle.size === '' && size('subtitle').value === ''
	&& fonts.design._edit.fonts.text.size === '' && size('text').value === '' && fonts.summary()[1][1] === 'System (default), Normal, 1.125 rem (default)' );

/*	The decimal sign is the workbench's language's, and it dresses the summary: 1,125 rem in German. The field does not
	take it - a number field drops a typed comma - so its placeholder is the number as the field shows and takes it, with
	the point, in every language	*/
TEXT['/_admin/design/fontsize/decimal'] = ',';
const german = panel();
german.tab('Fonts');

check( 'the summary uses the decimal sign of the language, 1,125 rem in German - the placeholder of a size does not: it is the number as the field shows and takes it, 1.6 and 1.125',
	german.summary()[1][1] === 'Source Sans 3, Normal, 1,125 rem (default)' && german.byId('design-font-title-size').getAttribute('placeholder') === '1.6'
	&& german.byId('design-font-subtitle-size').getAttribute('placeholder') === '1.125' && german.byId('design-font-title-size').getAttribute('min') === '1' );
check( '...and the field is still the number field it was, the unit after it: the language changes the words round it, not the field',
	german.byId('design-font-title-size').type === 'number' && german.byId('design-font-title-size').getAttribute('step') === '.1'
	&& german.byId('design-font-title-size').value === '' && byClass( german.byId('design-font-title-size').parent, 'design-fonts-unit' )[0].textContent === 'rem' );

TEXT['/_admin/design/fontsize/decimal'] = '.';

console.log('');


// --- Colours: the palette and its mode ------------------------------------------------

console.log( 'Colours' );

const colours = panel();
colours.tab('Colours');

check( 'the Colours tab has the brand colour, the five knobs the rest of the palette is solved with - the second colour at the end of the first of them - and the mode',
	tabOf( colours ) === 'colours' && colours.byId('design-colour-primary').value === '#4faae8'
	&& byClass( colours.form, 'design-knob-row' ).map( rowName ).join() === 'Second colour,Temperature,Saturation,Contrast,Depth'
	&& colours.byId('design-colour-secondary') !== null && colours.byId('design-colour-mode') !== null );
check( '...four positions for Harmony and Temperature, three for the others, each in the words of the panel - and the one the knob starts on lit',
	[ 4, 4, 3, 3, 3 ].join() === byClass( colours.form, 'design-knob-row' ).map( function( row ) { return byTag( row, 'button' ).length } ).filter( function( count, at ) { return at < 5 } ).join()
	&& byTag( colours.knobRow('Temperature'), 'button' ).map( function( button ) { return button.textContent } ).join() === 'Neutral,Cool,Brand,Warm'
	&& byTag( colours.knobRow('Temperature'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true,false' );
/*	One middle word for every knob: the middle position of Saturation, Contrast and Depth is Default, as that of the knobs
	of the other tabs is, and not Standard - and the other words of the Colours tab are what they were, each capitalised. Read
	off the buttons that are drawn; the one a knob starts on is the middle one	*/
check( '...the words of the five are Monochrome, Analogous, Triadic, Complementary - Neutral, Cool, Brand, Warm - Muted, Default, Rich - Soft, Default, Strong - Flat, Default, Raised: the middle of the three scales is Default, and the one that is lit at the start',
	[ [ 'Second colour', 'Monochrome,Analogous,Triadic,Complementary' ], [ 'Temperature', 'Neutral,Cool,Brand,Warm' ], [ 'Saturation', 'Muted,Default,Rich' ], [ 'Contrast', 'Soft,Default,Strong' ], [ 'Depth', 'Flat,Default,Raised' ] ].every( function( entry ) {
		return byTag( colours.knobRow( entry[0] ), 'button' ).map( function( button ) { return button.textContent } ).join() === entry[1];
	} )
	&& [ 'Saturation', 'Contrast', 'Depth' ].every( function( name ) {
		return byTag( colours.knobRow( name ), 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).map( function( button ) { return button.textContent } ).join() === 'Default';
	} ) );
check( 'the mode is a select of three words - automatic, light only, dark only - under a heading of its own, and starts on the first',
	JSON.stringify( offered( colours.byId('design-colour-mode') ) ) === JSON.stringify( [ [ 'auto', 'automatic' ], [ 'light', 'light only' ], [ 'dark', 'dark only' ] ] )
	&& shown( colours.byId('design-colour-mode') ) === 'auto' && colours.byId('design-colour-mode').getAttribute('aria-label') === 'Mode'
	&& byClass( colours.form, 'design-colour-mode' ).length === 1 );
check( '...and they stay lower case, as the steps of the knobs over it are capitalised: the Colours tab keeps its select lower case',
	offered( colours.byId('design-colour-mode') ).every( function( option ) { return option[1].charAt(0) === option[1].charAt(0).toLowerCase() } ) );

choose( colours.byId('design-colour-mode'), 'dark' );

check( 'choosing a mode is the selection on screen', colours.design._edit.colours.mode === 'dark' && colours.byId('design-reset').hidden === false );

const brand = colours.byId('design-colour-primary');
brand.value = '#8b1d3f';
brand.fire('change');

check( 'a new brand colour is the selection, read off the input that was changed, and the rows of the palette are drawn again from it',
	colours.design._edit.colours.primary === '#8b1d3f' && descendants( colours.byId('design-controls') ).indexOf( brand ) === -1 && colours.byId('design-colour-primary') !== brand && colours.byId('design-colour-primary').value === '#8b1d3f' );

byTag( colours.knobRow('Saturation'), 'button' )[2].fire('click');

check( 'a position of a knob is a number', colours.design._edit.colours.saturation === 3 );
check( 'the summary has the brand colour, the second colour - Monochrome while nobody chose one - every knob and the mode',
	JSON.stringify( colours.summary() ) === JSON.stringify( [
		[ 'Brand colour', '#8b1d3f' ], [ 'Second colour', 'Monochrome' ], [ 'Temperature', 'Brand' ], [ 'Saturation', 'Rich' ],
		[ 'Contrast', 'Default' ], [ 'Depth', 'Default' ], [ 'Mode', 'dark only' ], [ 'Applied file', 'Up to date' ],
	] ) );

// A second colour somebody chose overrides the wheel; choosing a position on the wheel gives it up
const second = colours.byId('design-colour-secondary');
second.value = '#0f766e';
second.fire('change');

check( 'a second colour that was chosen is shown as chosen, and the summary says the hex where it said the place on the wheel',
	colours.design._edit.colours.secondary === '#0f766e' && colours.summary()[1][1] === '#0f766e'
	&& byClass( colours.form, 'design-knob-reset' ).length === 1
	&& byTag( colours.knobRow('Second colour'), 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).length === 0 );

byTag( colours.knobRow('Second colour'), 'button' )[2].fire('click');

check( '...and a position on the wheel gives it up again', colours.design._edit.colours.secondary === '' && colours.design._edit.colours.harmony === 3
	&& colours.summary()[1][1] === 'Triadic' && byClass( colours.form, 'design-knob-reset' ).length === 0 );

const unsafe = panel( { brand : { light : { safe : false, ratio : 3.1, target : 4.5 } } } );
unsafe.tab('Colours');

check( 'a brand colour text does not survive on says so under the colour, with the numbers - and says nothing where it does',
	unsafe.byId('design-brand-warning').hidden === false && unsafe.byId('design-brand-warning').textContent === say( '/_admin/design/msg/brand-unsafe', 3.1, 4.5 )
	&& colours.byId('design-brand-warning').hidden === true );

console.log('');


// --- The icons ----------------------------------------------------------------------------------
//
// A row of the three knob tabs, and every row of the Colours tab, open with an icon, so that a
// person feels what a knob sets before reading it - and the names of the Colours tab start at
// one left edge, none of them a row without. The shapes are Lucide's;
// the two tables below are the other copy of them - which icon a knob takes, and the shapes
// as the markup they were given in - and the script's own maps are held to them, so that
// neither can drift from the other. A knob with no entry has no icon, and no shape stands in

console.log( 'The icons' );

/** Lucide's shapes the rows use, by their Lucide name, as the markup the parts of each are made of - verbatim */
const LUCIDE = {
	'a-large-small' : '<path d="m15 16 2.536-7.328a1.02 1.02 1 0 1 1.928 0L22 16"/><path d="M15.697 14h5.606"/><path d="m2 16 4.039-9.69a.5.5 0 0 1 .923 0L11 16"/><path d="M3.304 13h6.392"/>',
	'scaling' : '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M14 15H9v-5"/><path d="M16 3h5v5"/><path d="M21 3 9 15"/>',
	'align-vertical-space-around' : '<rect width="10" height="6" x="7" y="9" rx="2"/><path d="M22 20H2"/><path d="M22 4H2"/>',
	'square-round-corner' : '<path d="M21 11a8 8 0 0 0-8-8"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
	'square' : '<rect width="18" height="18" x="3" y="3" rx="2"/>',
	'square-stack' : '<path d="M4 10c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2"/><path d="M10 16c-1.1 0-2-.9-2-2v-4c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2"/><rect width="8" height="8" x="14" y="14" rx="2"/>',
	'rows-3' : '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M21 9H3"/><path d="M21 15H3"/>',
	'text-cursor-input' : '<path d="M12 20h-1a2 2 0 0 1-2-2 2 2 0 0 1-2 2H6"/><path d="M13 8h7a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-7"/><path d="M5 16H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h1"/><path d="M6 4h1a2 2 0 0 1 2 2 2 2 0 0 1 2-2h1"/><path d="M9 6v12"/>',
	'palette' : '<path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>',
	'thermometer' : '<path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>',
	'droplet' : '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
	'contrast' : '<circle cx="12" cy="12" r="10"/><path d="M12 18a6 6 0 0 0 0-12v12z"/>',
	'sun-moon' : '<path d="M12 2v2"/><path d="M14.837 16.385a6 6 0 1 1-7.223-7.222c.624-.147.97.66.715 1.248a4 4 0 0 0 5.26 5.259c.589-.255 1.396.09 1.248.715"/><path d="M16 12a4 4 0 0 0-4-4"/><path d="m19 5-1.256 1.256"/><path d="M20 12h2"/>',
	// The three devices of the viewport switch, the shapes the Builder's panel carries as its symbols
	'smartphone' : '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
	'tablet' : '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>',
	'monitor' : '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
	// The reload of the preview, beside the three devices
	'refresh-cw' : '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
};

/** Which icon each button of the viewport switch takes */
const VIEWPORT_ICON = { phone : 'smartphone', tablet : 'tablet', desktop : 'monitor' };

/** Which icon each row takes: the knobs of the three tabs, and the rows of the Colours tab */
const ICON_OF = {
	'content/hero-title' : 'a-large-small', 'content/hero-text' : 'a-large-small', 'content/quiet' : 'a-large-small', 'content/loud' : 'a-large-small',
	'content/section-space' : 'scaling', 'content/card-space' : 'align-vertical-space-around', 'content/corners' : 'square-round-corner',
	'content/border' : 'square', 'content/shadow' : 'square-stack',
	'buttons/small' : 'a-large-small', 'buttons/big' : 'a-large-small', 'buttons/space' : 'align-vertical-space-around', 'buttons/corners' : 'square-round-corner',
	'components/scale' : 'a-large-small', 'components/space' : 'align-vertical-space-around', 'components/corners' : 'square-round-corner',
	'components/border' : 'square', 'components/zebra' : 'rows-3', 'components/labels' : 'text-cursor-input',
	'colours/primary' : 'palette', 'colours/harmony' : 'palette', 'colours/temperature' : 'thermometer', 'colours/saturation' : 'droplet',
	'colours/contrast' : 'contrast', 'colours/depth' : 'square-stack', 'colours/mode' : 'sun-moon',
};

/** The parts of an svg as markup, in the order they were made in and with their attributes in the order they were given in */
function markupOf( svg ) {
	return svg.children.map( function( part ) {
		return '<'+ part.tagName.toLowerCase()+ Object.keys( part.attributes ).map( function( name ) { return ' '+ name+ '="'+ part.attributes[name]+ '"' } ).join('')+ '/>';
	} ).join('');
}

const icons = panel();
const iconProblems = [];
let iconRows = 0;

[ [ 'Content', 'content' ], [ 'Buttons', 'buttons' ], [ 'Components', 'components' ] ].forEach( function( entry ) {

	icons.tab( entry[0] );

	byClass( icons.form, 'design-knob-row' ).forEach( function( row ) {

		const key = entry[1]+ '/'+ row.dataset.knob;
		const label = byClass( row, 'design-knob-label' )[0];
		const svg = byTag( label, 'svg' )[0];
		const box = byClass( label, 'design-knob-text' )[0];

		iconRows++;

		if( svg === undefined || box === undefined || label.children[0] !== svg || label.children[1] !== box || svg.dataset.icon !== ICON_OF[key]
			|| byTag( label, 'svg' ).length !== 1 || hasClass( svg, 'design-icon' ) === false || markupOf( svg ) !== LUCIDE[ICON_OF[key]] )
			iconProblems.push( key );
	} );
} );

check( 'every row of the three knob tabs - nineteen of them - carries an svg.design-icon before its name, with the data-icon of the table, and the shape of Lucide\'s that name has, verbatim'
	+ ( iconProblems.length === 0 ? '' : ' - not so for '+ iconProblems.join() ), iconRows === 19 && iconProblems.length === 0 );

icons.tab('Content');
const iconRow = icons.knobRow('Hero title');
const iconLabel = byClass( iconRow, 'design-knob-label' )[0];
const iconSvg = byTag( iconLabel, 'svg' )[0];

check( '...the icon is decoration, drawn on a 24 by 24 grid in the svg namespace: hidden from assistive technology, out of the tab order, and the class that carries the stroke and the size',
	iconSvg.namespaceURI === 'http://www.w3.org/2000/svg' && iconSvg.getAttribute('viewBox') === '0 0 24 24' && iconSvg.getAttribute('aria-hidden') === 'true'
	&& iconSvg.getAttribute('focusable') === 'false' && hasClass( iconSvg, 'design-icon' ) && iconSvg.children.every( function( part ) { return part.namespaceURI === 'http://www.w3.org/2000/svg' } )
	&& iconSvg.children.length === 4 && iconSvg.children.every( function( part ) { return part.tagName === 'PATH' } ) );
check( '...and the label is the row of the two: the icon, then a box that holds the name with its note under it - the name is the box\'s own text, the note a small in it',
	iconLabel.children.length === 2 && iconLabel.children[1].tagName === 'SPAN' && hasClass( iconLabel.children[1], 'design-knob-text' )
	&& iconLabel.children[1].textContent === 'Hero title' && byTag( iconLabel.children[1], 'small' ).length === 1
	&& byTag( iconLabel.children[1], 'small' )[0].textContent === TEXT['/_admin/design/knob/content/hero-title/note'] && byTag( iconLabel, 'button' ).length === 0 );

// A row the template does not answer to is grey, and keeps its icon: what it sets is not a thing that depends on the template
choose( icons.byId('design-set-content'), 'v2' );

check( '...a row that is greyed out keeps its icon, with the line that says whose decision that is in the box beside it',
	hasClass( icons.knobRow('Shadow of a card'), 'design-knob-row--off' ) && byTag( icons.knobRow('Shadow of a card'), 'svg' )[0].dataset.icon === 'square-stack'
	&& byTag( icons.knobRow('Shadow of a card'), 'small' )[0].textContent === 'This template does not answer to “Shadow of a card”.' );

icons.tab('Colours');

const colourIcon = function( row ) { return ( byTag( row, 'svg' )[0] || { dataset : {} } ).dataset.icon };
const brandLabel = byClass( icons.form, 'design-colour-label' )[0];

check( 'the rows of the Colours tab that are a colour get theirs by the same mechanism: the brand colour and the second colour - the row of Harmony - the palette',
	hasClass( brandLabel, 'design-knob-label' ) && brandLabel.getAttribute('for') === 'design-colour-primary' && byTag( brandLabel, 'svg' )[0].dataset.icon === 'palette'
	&& brandLabel.children[1].textContent === 'Brand colour' && markupOf( byTag( brandLabel, 'svg' )[0] ) === LUCIDE['palette']
	&& colourIcon( icons.knobRow('Second colour') ) === 'palette' && markupOf( byTag( icons.knobRow('Second colour'), 'svg' )[0] ) === LUCIDE['palette'] );
check( '...temperature takes the thermometer, saturation the drop, contrast the circle with one half filled and depth the stack, each with the shape of Lucide\'s that name has, verbatim',
	[ [ 'Temperature', 'thermometer' ], [ 'Saturation', 'droplet' ], [ 'Contrast', 'contrast' ], [ 'Depth', 'square-stack' ] ].every( function( entry ) {
		const svg = byTag( icons.knobRow( entry[0] ), 'svg' );
		return svg.length === 1 && svg[0].dataset.icon === entry[1] && markupOf( svg[0] ) === LUCIDE[entry[1]];
	} ) );
check( '...and the mode the sun and the moon - before the title of its block, which has no label of its own to put it in',
	byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' ).length === 1 && byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0].dataset.icon === 'sun-moon'
	&& markupOf( byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0] ) === LUCIDE['sun-moon'] && byTag( byClass( icons.form, 'design-colour-mode' )[0], 'strong' )[0].children[0] === byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0] );

// Every label of the tab - the brand colour and the five knobs - opens with an icon and has the name right after it, so that the names stand at one left edge
const colourLabels = byClass( icons.form, 'design-knob-label' );

check( '...so that every row of the Colours tab starts with its icon, the name right after it, and none is left to start at the edge: the brand colour, Harmony, temperature, saturation, contrast and depth',
	colourLabels.length === 6 && colourLabels.every( function( label ) {
		return label.children.length === 2 && label.children[0].tagName === 'SVG' && hasClass( label.children[0], 'design-icon' ) && hasClass( label.children[1], 'design-knob-text' );
	} ) && colourLabels.map( function( label ) { return label.children[0].dataset.icon } ).join() === 'palette,palette,thermometer,droplet,contrast,square-stack' );

const mapped = icons.design.KNOB_ICONS;

// A row whose entry is taken away is drawn without its icon, and nothing takes the place: the label is the box of the name alone
const thermometer = mapped['colours/temperature'];
delete mapped['colours/temperature'];
icons.design._redrawColours();

check( 'a knob with no entry in the map has no icon, and nothing stands in for it: not a row, not a fallback shape',
	icons.design._icon('content/nothing') === null && icons.design._icon('') === null && icons.design._icon('constructor') === null
	&& byTag( icons.knobRow('Temperature'), 'svg' ).length === 0 && byClass( icons.knobRow('Temperature'), 'design-knob-label' )[0].children.length === 1
	&& hasClass( byClass( icons.knobRow('Temperature'), 'design-knob-label' )[0].children[0], 'design-knob-text' )
	&& colourIcon( icons.knobRow('Saturation') ) === 'droplet' );

mapped['colours/temperature'] = thermometer;
icons.design._redrawColours();

check( '...and the row has it again when the entry is back', colourIcon( icons.knobRow('Temperature') ) === 'thermometer' );

mapped['content/nowhere'] = 'no-such-icon';

check( '...nor has a knob whose icon the script has no shape for',
	icons.design._icon('content/nowhere') === null );

delete mapped['content/nowhere'];

check( 'the script\'s two maps are the tables above: the same knobs with the same icons, and a shape for each icon that is used - no more, no fewer, verbatim',
	JSON.stringify( Object.keys( mapped ).sort() ) === JSON.stringify( Object.keys( ICON_OF ).sort() ) && Object.keys( ICON_OF ).every( function( key ) { return mapped[key] === ICON_OF[key] } )
	&& JSON.stringify( Object.keys( icons.design.ICONS ).sort() ) === JSON.stringify( Object.keys( LUCIDE ).sort() )
	&& Object.keys( ICON_OF ).every( function( key ) { return markupOf( icons.design._icon( key ) ) === LUCIDE[ICON_OF[key]] } ) );
check( '...and the shapes in use are the rows\', the three devices of the viewport switch and the reload: each shape of the table is drawn by a row, by a width or by the reload, and each width has the device the table says',
	JSON.stringify( Object.keys( LUCIDE ).sort() ) === JSON.stringify( Object.keys( ICON_OF ).map( function( key ) { return ICON_OF[key] } ).concat( Object.keys( VIEWPORT_ICON ).map( function( key ) { return VIEWPORT_ICON[key] } ), [ 'refresh-cw' ] )
		.filter( function( name, at, all ) { return all.indexOf( name ) === at } ).sort() )
	&& icons.design.WIDTHS.length === 3 && icons.design.WIDTHS.every( function( width ) { return width.icon === VIEWPORT_ICON[width.key] && markupOf( icons.design._svg( width.icon ) ) === LUCIDE[width.icon] } ) );

/*	The three devices are the Builder's, and the copy is held to the original where this checkout has the
	Builder's panel: the shapes of its symbols for the phone, the tablet and the monitor, verbatim	*/
const builderPanel = path.join( __dirname, '../../Builder/templates/panel.tpl' );

if( fs.existsSync( builderPanel ) === false )
	console.log( '  --  this checkout has no Builder, so the three devices are not compared with its symbols' );
else {

	const symbols = fs.readFileSync( builderPanel, 'utf8' );
	const symbolOf = function( name ) {
		const found = new RegExp( '<symbol id="builder-icon-'+ name+ '" viewBox="0 0 24 24">(.*?)</symbol>' ).exec( symbols );
		return found === null ? null : found[1];
	};

	check( '...the phone, the tablet and the monitor are the shapes of the Builder\'s symbols, verbatim - the same icons of the same license',
		[ 'smartphone', 'tablet', 'monitor' ].every( function( name ) { return symbolOf( name ) !== null && symbolOf( name ) === LUCIDE[name] } ) );
}

icons.tab('Fonts');

check( 'the Fonts table is a table with row headers and no knob rows: it has no icon',
	byTag( icons.form, 'table' ).length === 1 && byTag( byTag( icons.form, 'table' )[0], 'svg' ).length === 0 && byClass( icons.form, 'design-knob-row' ).length === 0 );

console.log('');


// --- The head of the preview ---------------------------------------------------------------
//
// One row: the heading, with the line the preview says things in, on one side, and on the other
// the bar of the frame's controls - the viewport switch, which is the Builder's three icon
// buttons, the switch that lets the frame follow, and the reload. Whether the bar stands beside the
// heading or under it is the stylesheet's flex-wrap and nothing here (design-smoke.php holds the rule)

console.log( 'The head of the preview' );

const pv = panel();
const pvSticky = byClass( pv.byId('design-preview'), 'design-preview-sticky' )[0];
const pvHead = byClass( pvSticky, 'design-preview-head' )[0] || element('div');

check( 'the head of the preview is one row: the heading and the bar are its two children, in that order - first in the stack, with the frame and the summary under it and nothing else',
	pvHead.parent === pvSticky && pvSticky.children[0] === pvHead && pvHead.children.length === 2
	&& hasClass( pvHead.children[0], 'design-preview-heading' ) && hasClass( pvHead.children[1], 'design-preview-bar' )
	&& pvSticky.children.length === 3 && hasClass( pvSticky.children[1], 'design-stage' ) && pvSticky.children[2] === pv.byId('design-summary')
	&& byClass( pvSticky, 'design-preview-heading' ).length === 1 && byClass( pvSticky, 'design-preview-bar' ).length === 1 );
check( '...the heading is the eyebrow over the title and nothing else; the bar holds the viewport switch, the switch that lets the frame follow and the reload, in that order',
	byClass( pvHead.children[0], 'design-eyebrow' ).length === 1 && byTag( pvHead.children[0], 'strong' )[0].textContent === 'Preview'
	&& pvHead.children[0].children.length === 1 && byClass( pvHead.children[0], 'design-eyebrow' )[0].parent === pvHead.children[0].children[0]
	&& pvHead.children[1].children.length === 3 && hasClass( pvHead.children[1].children[0], 'design-viewports' )
	&& hasClass( pvHead.children[1].children[1], 'design-preview-follow' ) && pvHead.children[1].children[2] === pv.byId('design-reload') );
// The line the preview says things in lies over the frame, in the stage, so that it takes no room in the head and the frame stays where it is
check( '...the chip the preview says things in is in the stage, after the frame, over it - not in the heading - and is empty at the start',
	pv.byId('design-preview-msg').parent === pvSticky.children[1] && pvSticky.children[1].children.length === 2 && pvSticky.children[1].children[0] === pv.byId('design-frame')
	&& pvSticky.children[1].children[1] === pv.byId('design-preview-msg') && pv.byId('design-preview-msg').textContent === '' && pv.byId('design-preview-msg').getAttribute('aria-live') === 'polite' );
// The reload is an icon button like the three devices, its words as its title: the bar stays narrow enough to stand beside the heading in the preview's pane of a 1280px window
check( '...the reload is an icon button in the devices\' style with Lucide\'s refresh-cw, its words its title and its aria-label, and no text of its own',
	hasClass( pv.byId('design-reload'), 'design-viewport' ) && hasClass( pv.byId('design-reload'), 'design-reload' ) && pv.byId('design-reload').children.length === 1
	&& pv.byId('design-reload').children[0].tagName === 'SVG' && pv.byId('design-reload').children[0].dataset.icon === 'refresh-cw' && markupOf( pv.byId('design-reload').children[0] ) === LUCIDE['refresh-cw']
	&& pv.byId('design-reload').title === 'Reload preview' && pv.byId('design-reload').getAttribute('aria-label') === 'Reload preview' && pv.byId('design-reload').textContent === '' );

// The width of the preview is the Builder's switch: three icon buttons in a group
const pvGroup = pvHead.children[1].children[0];
const pvButtons = byTag( pvGroup, 'button' );
const pvWidths = pv.design.WIDTHS;

check( 'the select of the width and its label are gone: no design-width, no select in the head, no word "Width" over anything - the text of that label is gone from the panel\'s words too',
	pv.byId('design-width') === null && byTag( pvHead, 'select' ).length === 0 && byTag( pvHead, 'label' ).every( function( label ) { return label.textContent !== 'Width' } )
	&& TEXT['/_admin/design/label/width'] === undefined );
check( 'the width is three icon buttons in a group the way the Builder has them: the group is named by what it is, there is a button each for the phone, the tablet and the desktop - and none for all of them',
	pvGroup.getAttribute('role') === 'group' && pvGroup.getAttribute('aria-label') === 'Viewport of the preview' && pvGroup.getAttribute('aria-label') === TEXT['/_admin/design/label/viewports']
	&& pvButtons.length === 3 && pvButtons.map( function( button ) { return button.id } ).join() === 'design-viewport-phone,design-viewport-tablet,design-viewport-desktop'
	&& pvWidths.map( function( width ) { return width.key+ ':'+ width.px } ).join() === 'phone:390,tablet:820,desktop:1280'
	&& pvButtons.every( function( button ) { return button.tagName === 'BUTTON' && button.type === 'button' && hasClass( button, 'design-viewport' ) } ) );
check( '...each with the name of its width as its title and as its name for a screen reader, since an icon has none: Phone, Tablet and Desktop',
	pvButtons.map( function( button ) { return button.title } ).join() === 'Phone,Tablet,Desktop' && pvButtons.map( function( button ) { return button.getAttribute('aria-label') } ).join() === 'Phone,Tablet,Desktop'
	&& pvButtons.map( function( button ) { return button.textContent } ).join('') === '' );
check( '...and the icon of its device, Lucide\'s smartphone, tablet and monitor, drawn as the icons of the rows are: an svg.design-icon on a 24 by 24 grid, hidden from assistive technology, the only child of the button',
	pvButtons.every( function( button, at ) {
		const svg = button.children[0];
		return button.children.length === 1 && svg.tagName === 'SVG' && hasClass( svg, 'design-icon' ) && svg.namespaceURI === 'http://www.w3.org/2000/svg'
			&& svg.getAttribute('viewBox') === '0 0 24 24' && svg.getAttribute('aria-hidden') === 'true' && svg.getAttribute('focusable') === 'false'
			&& svg.dataset.icon === VIEWPORT_ICON[pvWidths[at].key] && markupOf( svg ) === LUCIDE[VIEWPORT_ICON[pvWidths[at].key]];
	} ) );
check( '...the desktop is pressed to begin with and the other two are not: aria-pressed on all three, and the lit one marked',
	pvButtons.map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true'
	&& pvButtons.map( function( button ) { return hasClass( button, 'is-active' ) } ).join() === 'false,false,true' && pv.design._width === 1280 );

pvButtons[0].fire('click');

check( 'a click on a width presses it and lets go of the one that was, takes the width from the table and fits the frame at it - the frame, in the box that holds it',
	pvButtons.map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'true,false,false' && pv.design._width === 390
	&& pv.fitted.length === 1 && pv.fitted[0].width === 390 && pv.fitted[0].frame === pv.byId('design-frame') && pv.fitted[0].port === pv.byId('design-frame').parent );

pvButtons[1].fire('click');
pvButtons[2].fire('click');
pvButtons[0].fire('click');

check( '...each of the three in its turn: the tablet is 820, the desktop 1280, and every click asks the workbench again - the width is a choice, and the scaler closes over the one it was given',
	pv.fitted.map( function( fit ) { return fit.width } ).join() === '390,820,1280,390' && pv.design._width === 390
	&& pvButtons.map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'true,false,false' );
check( 'the viewport is not the selection: choosing one sends no request, marks nothing unsaved and leaves the width of the page, which is the model\'s, where it was',
	pv.requests.length === 0 && pv.design._isDirty() === false && pv.byId('design-reset').hidden === true && pv.design._edit.width === 0 );

// A screen drawn again keeps the width that was chosen
pv.design.showCurrent();

check( '...and a screen drawn again keeps it: the phone is pressed, and the frame is fitted at it once the preview answers',
	byTag( byClass( pv.byId('design-preview'), 'design-viewports' )[0], 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'true,false,false'
	&& byClass( pv.byId('design-preview'), 'design-viewports' ).length === 1 );

pv.design._previewNow();
pv.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...at the width that is chosen, not at the desktop', pv.fitted[pv.fitted.length - 1].width === 390 );

console.log('');


// --- The frame follows the tab -------------------------------------------------------------
//
// One page with an anchor for each tab. The frame is of this very origin, so
// the panel scrolls its window by hand: smoothly after a tab is opened and after
// every change, at once after the page was rebuilt

console.log( 'The anchors' );

const anchors = panel();

check( 'each tab has the id of an element on the specimen: global, type, content, buttons, components and colours - Fonts is the typography block, Colours the grounds',
	JSON.stringify( anchors.design.ANCHORS ) === JSON.stringify( { global : 'global', fonts : 'type', content : 'content', buttons : 'buttons', components : 'components', colours : 'colours' } ) );

const last = function() { return anchors.stage.scrolls[anchors.stage.scrolls.length - 1] };

anchors.tab('Buttons');
check( 'opening a tab asks the frame for the element of that tab and scrolls to it, smoothly, from where the page stands',
	anchors.stage.asked[anchors.stage.asked.length - 1] === 'buttons' && anchors.stage.scrolls.length === 1
	&& JSON.stringify( last() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

anchors.tab('Fonts');
check( '...the typography block for Fonts', anchors.stage.asked[anchors.stage.asked.length - 1] === 'type' && JSON.stringify( last() ) === JSON.stringify( { top : 800, behavior : 'smooth' } ) );

anchors.tab('Colours');
check( '...the grounds for Colours', anchors.stage.asked[anchors.stage.asked.length - 1] === 'colours' && JSON.stringify( last() ) === JSON.stringify( { top : 550, behavior : 'smooth' } ) );

anchors.tab('Global');
check( 'Global is the top of the page, header and all, wherever the anchor stands',
	JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && anchors.stage.scrolls.length === 4 );

anchors.tab('Components');
const before = anchors.stage.scrolls.length;

// A change on the tab: the preview comes back as a stylesheet, which is swapped, and the page goes to the anchor again
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'after every change the preview\'s stylesheet is swapped inside the frame that stands, and the page goes to the anchor of the open tab',
	anchors.stage.style.textContent === ':root{--x:1}' && anchors.stage.scrolls.length === before + 1
	&& JSON.stringify( last() ) === JSON.stringify( { top : 1600, behavior : 'smooth' } ) );

// A page that was rebuilt starts at its top by itself: the jump waits for it, and has nothing to scroll away from
const frame = anchors.byId('design-frame');
anchors.design._frames = null;
anchors.design._previewNow();
const loadsBefore = anchors.stage.scrolls.length;
anchors.answer( 200, { css : ':root{}', document : '<!doctype html><html></html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'a document that was rebuilt is set on the frame, and the jump waits for it to load', frame.srcdoc === '<!doctype html><html></html>'
	&& anchors.stage.scrolls.length === loadsBefore && ( frame.listeners.load || [] ).length === 1 );

frame.fire('load');

check( '...and then scrolls at once, to the anchor of the tab - at once even where the page asks for smooth scrolling of its own - and once: the handler takes itself away',
	anchors.stage.scrolls.length === loadsBefore + 1 && JSON.stringify( last() ) === JSON.stringify( { top : 1600, behavior : 'instant' } ) && ( frame.listeners.load || [] ).length === 0 );

/*	A sheet that was swapped declares its webfaces again, and the page lays
	out with the fallback faces until they are back: an anchor measured there
	is not where it stands a moment later. The jump waits for the faces	*/
let fontsAreIn = null;
anchors.stage.fonts = { status : 'loading', ready : { then : function( fn ) { fontsAreIn = fn } } };
const waiting = anchors.stage.scrolls.length;
anchors.design._jump();

check( 'where the page is still loading its webfaces the jump waits for them, and measures when they are in',
	anchors.stage.scrolls.length === waiting && typeof fontsAreIn === 'function' );

anchors.stage.tops.components = 1560;
anchors.stage.fonts = { status : 'loaded', ready : { then : function() {} } };
fontsAreIn();

check( '...then scrolls to where the anchor stands now, once',
	anchors.stage.scrolls.length === waiting + 1 && JSON.stringify( last() ) === JSON.stringify( { top : 1660, behavior : 'smooth' } ) );

anchors.stage.fonts = { status : 'loading', ready : { then : function( fn ) { fn() } } };
const looping = anchors.stage.scrolls.length;
anchors.design._jump();

check( '...and does not wait twice for faces that are still not in when it looks again', anchors.stage.scrolls.length === looping + 1 );
anchors.stage.fonts = null;

// A visitor who asked for less motion does not get a scroll across the page
anchors.stage.document.defaultView.matchMedia = function( query ) { return { matches : query === '(prefers-reduced-motion: reduce)' } };
anchors.design._jump();

check( 'a visitor who asked for less motion is taken to the anchor at once, not across the page', JSON.stringify( last() ) === JSON.stringify( { top : 1660, behavior : 'instant' } ) );

anchors.stage.document.defaultView.matchMedia = function() { return { matches : false } };
anchors.design._jump();

check( '...and one who did not is scrolled smoothly', JSON.stringify( last() ) === JSON.stringify( { top : 1660, behavior : 'smooth' } ) );
delete anchors.stage.document.defaultView.matchMedia;
anchors.stage.tops.components = 1500;

/*	The header is fixed, and a page keeps room for it at the top of main - each
	header template sets how much. An anchor stands that far from the top, where
	the first section of a page does, and not under the bar: the bar folds away
	when the page is scrolled down and is back when it is scrolled up, so its
	own height is not a distance to scroll by	*/
anchors.stage.clearance = 144;
anchors.design._jump();

check( 'an anchor stands as far from the top of the frame as main keeps room for the fixed header, and not under the bar', JSON.stringify( last() ) === JSON.stringify( { top : 1456, behavior : 'smooth' } ) );

anchors.stage.tops.content = 20;
anchors.tab('Content');

check( '...and never above the top of the page: the hero, which stands where main\'s room ends, is the top', JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

anchors.tab('Global');

check( '...while Global is the top of the page whatever room the header takes', JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

anchors.stage.clearance = 0;
anchors.stage.tops.content = 300;
anchors.tab('Components');

/*	A viewport animation plays when an element comes into view, so one that
	stands in view already shows nothing of a choice made while it is there:
	after the animation was changed the ones in view go back to their start,
	without a transition, and are let in again with the one the page has now -
	with the classes put back as the page's own script believes them to be	*/
const hero = animated();
const card = animated('opacity .3s');
anchors.stage.animated = [ hero, card ];

anchors.tab('Global');
anchors.design._setMotion( 'effect', 'flip' );
check( 'choosing an animation asks for the page to play it again, and that is all it does until the preview answers', anchors.design._replays === true && hero.log.length === 0 && card.log.length === 0 );

anchors.design._previewNow();
anchors.answer( 200, { css : ':root{--x:2}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'the elements in view go back to where they start with no transition, and come in again with the one they had',
	JSON.stringify( hero.log ) === JSON.stringify( [ [ 'remove', 'nino-vpa--visible,nino-vpa--visible-once', 'none' ], [ 'add', 'nino-vpa--visible,nino-vpa--visible-once', '' ] ] )
	&& JSON.stringify( card.log ) === JSON.stringify( [ [ 'remove', 'nino-vpa--visible,nino-vpa--visible-once', 'none' ], [ 'add', 'nino-vpa--visible,nino-vpa--visible-once', 'opacity .3s' ] ] )
	&& card.style.transition === 'opacity .3s' && anchors.design._replays === false );

hero.log.length = 0;
card.log.length = 0;
anchors.design._edit.size = 's';
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{--x:3}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...and a change that is no animation plays nothing again', hero.log.length === 0 && card.log.length === 0 );

anchors.design._setMotion( 'speed', 'fast' );
anchors.stage.reachable = false;
anchors.design._frames = 'v1|v1';
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a frame that cannot be reached, which is built anew and plays on its own', hero.log.length === 0 && threw( function() { anchors.design._replay() } ) === false );

anchors.stage.reachable = true;
anchors.stage.animated = [];
anchors.design._frames = 'v1|v1';
anchors.design._edit.size = 'm';
anchors.design._edit.motion = { effect : '', strength : '', speed : '' };
anchors.design._replays = false;
anchors.requests.length = 0;

// A frame that cannot be reached: the panel asks for the whole page instead of showing a stale one
anchors.stage.reachable = false;
anchors.design._frames = 'v1|v1';
anchors.design._previewNow();
const unreachable = anchors.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'a frame the panel cannot reach is rebuilt, not trusted: the next preview asks for the whole document, and nothing throws',
	anchors.design._frames === null && unreachable.payload.full === false && threw( function() { anchors.design._jump() } ) === false );

console.log('');


// --- The switch that lets the frame follow ---------------------------------------------------
//
// On at the start of every visit and stored nowhere. Off, the frame never scrolls by itself: not after
// a change, not after a tab is opened, and a page that is built anew stays at its top. On again, it
// jumps once, to where it would have been

console.log( 'The follow switch' );

const fw = panel();
const fwField = byClass( fw.byId('design-preview'), 'design-preview-follow' )[0] || element('label');
const fwInput = byTag( fwField, 'input' )[0] || element('input');
const fwBar = byClass( fw.byId('design-preview'), 'design-preview-bar' )[0];

check( 'the bar has a switch the workbench draws, between the viewport buttons and the reload: a checkbox with the key design-follow, named "Preview follows", compact by its class',
	fwField.tagName === 'LABEL' && hasClass( fwField, 'nino-admin-switch' ) && hasClass( fwField, 'design-preview-follow' )
	&& fwBar.children.indexOf( fwField ) === fwBar.children.indexOf( byClass( fwBar, 'design-viewports' )[0] ) + 1 && fwBar.children.indexOf( fwField ) === fwBar.children.indexOf( fw.byId('design-reload') ) - 1
	&& fwInput.type === 'checkbox' && fwInput.dataset.key === 'design-follow' && fwInput.id === 'design-follow'
	&& byClass( fwField, 'nino-admin-switch-copy' )[0].textContent === 'Preview follows' && TEXT['/_admin/design/label/follow'] === 'Preview follows' );
check( '...it is on at the start of the visit: checked, and the panel\'s flag says so - and nothing of it is in the selection',
	fwInput.checked === true && fw.design._follow === true && fw.design._target === null
	&& JSON.stringify( fw.design._selection( fw.design._data ) ).indexOf('follow') === -1 && Object.keys( fw.design._edit ).indexOf('follow') === -1 );

fw.tab('Content');
const fwScrolled = fw.stage.scrolls.length;

check( 'while it is on, the frame jumps by itself: a tab that is opened scrolls it, to the anchor of the tab',
	fwScrolled === 1 && JSON.stringify( fw.stage.scrolls[0] ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

fwInput.checked = false;
fwInput.fire('change');

check( 'switched off, the flag is false - and that sends no request, marks nothing unsaved and does not scroll the frame',
	fw.design._follow === false && fw.requests.length === 0 && fw.design._isDirty() === false && fw.byId('design-reset').hidden === true && fw.stage.scrolls.length === fwScrolled );

const fwAsked = fw.stage.asked.length;

fw.tab('Buttons');
fw.tab('Components');
fw.tab('Global');
fw.tab('Content');

check( 'with it off a tab that is opened does not scroll the frame - four tabs, no scrollTo, and the frame\'s page is not even asked for the element',
	fw.stage.scrolls.length === fwScrolled && fw.stage.asked.length === fwAsked && fw.design._tab === 'content' );

fw.move( 'Quiet variant', 'more' );
fw.design._previewNow();
fw.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a change: the preview comes back and its stylesheet is swapped inside the frame that stands, which stays where it is',
	fw.stage.style.textContent === ':root{--x:1}' && fw.stage.scrolls.length === fwScrolled && fw.stage.asked.indexOf('cards') === -1
	&& fw.fitted.length === 1 && fw.design._edit.knobs.content.quiet === 1 );

fw.design._frames = null;
fw.design._previewNow();
fw.answer( 200, { css : ':root{}', document : '<!doctype html><html></html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
fw.byId('design-frame').fire('load');

check( '...nor a page that is built anew: it is set on the frame, the load is heard, and the page stays at the top it starts at',
	fw.byId('design-frame').srcdoc === '<!doctype html><html></html>' && fw.stage.scrolls.length === fwScrolled && ( fw.byId('design-frame').listeners.load || [] ).length === 0 );

// The screen drawn again from scratch: the switch is what it was left at
fw.design.showCurrent();
fw.design._previewNow();
fw.answer( 200, { css : ':root{}', document : '<!doctype html><html></html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
fw.byId('design-frame').fire('load');

check( '...nor one the whole screen was drawn anew for, which keeps the switch where it was left: off',
	byTag( byClass( fw.byId('design-preview'), 'design-preview-follow' )[0], 'input' )[0].checked === false && fw.stage.scrolls.length === fwScrolled );

// Back on: once, to the place the last change was looked at
const fwBack = byTag( byClass( fw.byId('design-preview'), 'design-preview-follow' )[0], 'input' )[0];
fwBack.checked = true;
fwBack.fire('change');

check( 'switched on again, the frame jumps once - to where the last change was looked at, the cards, and not to the anchor of the tab',
	fw.design._follow === true && fw.stage.scrolls.length === fwScrolled + 1
	&& JSON.stringify( fw.stage.scrolls[fwScrolled] ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) && fw.stage.asked[fw.stage.asked.length - 1] === 'cards' );

fwBack.checked = false;
fwBack.fire('change');
fwBack.checked = true;
fwBack.fire('change');

check( '...and once for each time: switching it off does nothing, switching it on again jumps again',
	fw.stage.scrolls.length === fwScrolled + 2 );

// A jump that waits for the webfaces looks again when they are in, and finds the switch off
let fwFonts = null;
fw.stage.fonts = { status : 'loading', ready : { then : function( fn ) { fwFonts = fn } } };
const fwWaiting = fw.stage.scrolls.length;
fw.design._jump();
fwBack.checked = false;
fwBack.fire('change');
fw.stage.fonts = { status : 'loaded', ready : { then : function() {} } };
fwFonts();

check( 'a jump that waited for the webfaces and finds the switch off when they are in does not scroll',
	typeof fwFonts === 'function' && fw.stage.scrolls.length === fwWaiting );

fw.stage.fonts = null;

console.log('');


// --- Where the preview jumps ------------------------------------------------------------------
//
// Opening a tab goes to the anchor of the tab. A change of one knob goes to where that knob shows:
// on Content the title and the text of the hero to the hero, every other knob to the cards (which
// have the id for it on the specimen); on a tab with no entry the anchor of the tab. The place of a
// change is one target, put back to the tab's own when a tab is opened or a template is chosen

console.log( 'Where the preview jumps' );

const jp = panel();
const jpLast = function() { return jp.stage.scrolls[jp.stage.scrolls.length - 1] };
const jpAsked = function() { return jp.stage.asked[jp.stage.asked.length - 1] };

/** What the preview answers while the frame stands: a stylesheet to swap, and then the page goes where the change is looked at */
function jpAnswer() {
	jp.design._previewNow();
	jp.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
}

check( 'the specimen knows where a change of a knob is looked at: the hero\'s two knobs go to the hero, every other knob of Content to the cards - and no other tab has an entry',
	JSON.stringify( jp.design.KNOB_ANCHORS ) === JSON.stringify( { content : { 'hero-title' : 'content', 'hero-text' : 'content', '*' : 'cards' } } )
	&& Object.keys( jp.design.KNOB_ANCHORS ).join() === 'content' && jp.design._target === null );

jp.tab('Content');
const jpOpened = jp.stage.scrolls.length;

check( 'opening a tab goes to the anchor of the tab and leaves no target: Content is the hero',
	jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) && jp.design._target === null );

jp.move( 'Quiet variant', 'more' );

check( 'a knob of Content that is not the hero\'s sets the target to the cards, and nothing scrolls until the preview has answered',
	jp.design._target === 'cards' && jp.stage.scrolls.length === jpOpened );

jpAnswer();

check( '...and then the frame goes to the cards: the element with the id cards, from where the page stands',
	jpAsked() === 'cards' && jp.stage.scrolls.length === jpOpened + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

const jpWhere = {};

KNOBS.content.forEach( function( knob ) {

	jp.move( TEXT['/_admin/design/knob/content/'+ knob+ '/label'], 'less' );
	jpWhere[knob] = jp.design._target;
} );

check( 'each knob of Content has its place: the title and the text of the hero the hero, the other seven - quiet, loud, the spaces, the corners, the line and the shadow - the cards',
	JSON.stringify( jpWhere ) === JSON.stringify( { 'hero-title' : 'content', 'hero-text' : 'content', quiet : 'cards', loud : 'cards', 'section-space' : 'cards', 'card-space' : 'cards', corners : 'cards', border : 'cards', shadow : 'cards' } ) );

jp.move( 'Hero title', 'more' );
jpAnswer();

check( 'a change of the title of the hero goes to the hero: #content',
	jp.design._target === 'content' && jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.move( 'Hero text', 'more' );
jpAnswer();

check( '...and so does one of the text of the hero', jp.design._target === 'content' && jpAsked() === 'content' );

jp.move( 'Shadow of a card', 'more' );
jpAnswer();

check( 'one of the cards goes back to the cards, whichever knob was moved before it', jp.design._target === 'cards' && jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

// A template is the tab's, not the knob's
choose( jp.byId('design-set-content'), 'v2' );

check( 'choosing a template puts the target back to the tab\'s own, and the frame goes to the anchor of the tab when the preview has answered',
	jp.design._target === null );

jpAnswer();

check( '...the hero, where it went when the tab was opened', jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.move( 'Corners of a card', 'more' );
jp.tab('Buttons');

check( 'opening another tab puts the target back as well, and the frame goes to the anchor of that tab: the place a change was looked at belonged to the tab that was left',
	jp.design._target === null && jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

jp.move( 'Small buttons', 'more' );

check( 'a knob of a tab with no entry of its own goes to the anchor of the tab: Buttons, #buttons',
	jp.design._target === 'buttons' );

jpAnswer();

check( '...and the frame goes there', jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

jp.tab('Components');
jp.move( 'Stripes', 'more' );

check( '...and so does one of Components: #components', jp.design._target === 'components' );

jp.tab('Content');

check( 'back on Content the frame goes to the hero, not to the cards the last change of that tab was looked at in',
	jp.design._target === null && jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

// The header and the footer are chosen on Global, and that is a template as well
jp.tab('Global');
jp.design._target = 'cards';
choose( jp.byId('design-set-header'), 'v2' );

check( 'choosing a frame on Global is a template too: the target goes back', jp.design._target === null );

// Opening the tab already open changes nothing, and so does a change that is no knob
jp.tab('Content');
jp.move( 'Quiet variant', 'less' );
jp.tab('Content');

check( 'the tab that is open already is not opened again: its target stays where the last change of a knob put it',
	jp.design._target === 'cards' );

jp.tab('Colours');
byTag( jp.knobRow('Saturation'), 'button' )[0].fire('click');
jp.design._previewNow();
jp.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'a knob of the palette has no place of its own: the grounds, where opening the tab went', jp.design._target === null && jpAsked() === 'colours' );

// Every id the script jumps to is an element of the specimen, once - read off the template the server answers the page from
const specimen = fs.readFileSync( path.join( __dirname, '../templates/preview-specimen.tpl' ), 'utf8' );
const specimenIds = {};
( specimen.match( /\sid="[a-z-]+"/g ) || [] ).forEach( function( found ) { specimenIds[found.slice( 5, -1 )] = ( specimenIds[found.slice( 5, -1 )] || 0 ) + 1 } );
const jumpedTo = Object.keys( jp.design.ANCHORS ).map( function( tab ) { return jp.design.ANCHORS[tab] } )
	.concat( Object.keys( jp.design.KNOB_ANCHORS ).reduce( function( ids, tab ) { return ids.concat( Object.keys( jp.design.KNOB_ANCHORS[tab] ).map( function( knob ) { return jp.design.KNOB_ANCHORS[tab][knob] } ) ) }, [] ) );

check( 'every id the script jumps to is on the specimen exactly once: the six of the tabs and the cards',
	jumpedTo.length === 9 && jumpedTo.every( function( id ) { return specimenIds[id] === 1 } ) && specimenIds.cards === 1 );
check( '...and the Cards section is the element that has the id: the second section of the page, after the hero and before the grounds',
	specimen.indexOf('<section id="cards" class="nino-section" aria-label="Cards">') !== -1 && specimen.indexOf('id="content"') < specimen.indexOf('id="cards"')
	&& specimen.indexOf('id="cards"') < specimen.indexOf('id="colours"') );

console.log('');


// --- What the preview carries ----------------------------------------------------------------

console.log( 'The preview' );

const asked = panel();
asked.design._previewNow();
const first = asked.requests[asked.requests.length - 1];

check( 'the first preview of a visit asks for the whole document, with every key of the model: the templates, the knobs, the size, the width, the animation, the fonts and the palette',
	first.endpoint === 'preview' && first.payload.full === true && Object.keys( first.payload ).sort().join() === 'colours,fonts,full,knobs,motion,parts,size,width'
	&& first.payload.parts.header === 'v1' && first.payload.parts.components === 'v1' && first.payload.knobs.content['hero-title'] === 0
	&& first.payload.motion.effect === '' && first.payload.colours.mode === 'auto' );
check( '...and while it is asked for, the line over the frame says so, and its title says the same - the chip shows as much of the line as the head has room for',
	asked.byId('design-preview-msg').textContent === TEXT['/_admin/design/msg/previewing'] && asked.byId('design-preview-msg').title === TEXT['/_admin/design/msg/previewing']
	&& hasClass( asked.byId('design-preview-msg'), 'nino-admin-hint' ) );

asked.answer( 200, { css : ':root{}', document : '<!doctype html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'an answer with nothing to add empties the line, and its title with it',
	asked.byId('design-preview-msg').textContent === '' && asked.byId('design-preview-msg').title === '' && hasClass( asked.byId('design-preview-msg'), 'nino-admin-hint' ) );

asked.design._previewNow();

check( '...the next one only a stylesheet, as long as the header and the footer are the ones the frame was built with', asked.requests[asked.requests.length - 1].payload.full === false );

asked.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [ 'there is no template "x" - using "v1"' ] } );

check( 'what the server had to replace comes back beside the preview, in the line over the frame - and as the title of the line, where the chip cuts it',
	( asked.byId('design-preview-msg') || {} ).textContent === 'there is no template "x" - using "v1"' && hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' )
	&& asked.byId('design-preview-msg').title === 'there is no template "x" - using "v1"' );

choose( asked.byId('design-set-footer'), 'v2' );
asked.design._previewNow();

check( '...and another header or footer is markup, so the document is asked for again', asked.requests[asked.requests.length - 1].payload.full === true );

asked.answer( 500, { error : 'broken' } );

check( 'a preview that failed says so in that line, as an error, and in its title as well',
	hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' ) && asked.byId('design-preview-msg').textContent === '(500) broken' && asked.byId('design-preview-msg').title === '(500) broken' );

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

check( 'the two buttons say what they do: save a draft, apply to the website - and there is no take-over button, and no way back to a version that is not there',
	buttons.indexOf('Save draft') !== -1 && buttons.indexOf('Apply to website') !== -1 && buttons.length === 3 && asking.byId('design-restore') === null );
check( 'a line under the bar says which of the two does what',
	( asking.byId('design-actions-hint') || {} ).textContent === TEXT['/_admin/design/hint/actions']
	&& hasClass( asking.byId('design-actions-hint') || element('p'), 'nino-admin-hint' ) );
check( '...and it stands after the bar, not in it', asking.form.children.indexOf( asking.byId('design-actions-hint') ) > asking.form.children.indexOf( byClass( asking.form, 'nino-admin-actionbar' )[0] ) );
check( 'with files that are not Design\'s the state is foreign, and the summary says so in one word - the files are named where the question is asked, in the confirmation below',
	asking.design._stateKey() === 'foreign' && JSON.stringify( asking.summary().pop() ) === JSON.stringify( [ 'Applied file', 'Not written by Design' ] )
	&& byClass( asking.form, 'design-state--warn' ).length === 0 && asking.byId('design-state') === null );

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
	( asking.byId('design-msg') || {} ).textContent === [ TEXT['/_admin/design/msg/applied'], TEXT['/_admin/design/msg/takenover'], say( '/_admin/design/msg/lost', '[consent-settings]' ) ].join(' ') );

// Saying no leaves the draft saved and the website alone
const declined = panel( {} );
declined.willConfirm( false );
declined.byId('design-apply').fire('click');
declined.answer( 200, {} );
declined.answer( 200, { files : delivered } );

check( 'no in the confirmation sends no apply - it reloads the panel',
	JSON.stringify( endpoints( declined ) ) === JSON.stringify( [ 'list' ] ) && declined.confirms.length === 1 );

declined.answer( 200, listing( {} ) );

check( '...and says the draft is saved and not applied', ( declined.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/cancelled'] );

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

check( 'with no previous version there is nothing to restore: no button, and the action bar holds the three it always did',
	empty.byId('design-restore') === null
	&& byClass( empty.form, 'nino-admin-actionbar' )[0].children.map( function( button ) { return button.id } ).join() === 'design-reset,design-save,design-apply' );

const kept = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
const restoreButton = kept.byId('design-restore');
const keptBar = byClass( kept.form, 'nino-admin-actionbar' )[0];
const restoreCalls = [];
const restoreWay = kept.design._restore;
kept.design._restore = function( button ) { restoreCalls.push( button ); return restoreWay.call( kept.design, button ) };

check( 'with one the action bar has a second quiet button, before Reset: the label of the way back and, for its title, the sentence with the date of that version',
	restoreButton !== null && restoreButton.tagName === 'BUTTON' && restoreButton.type === 'button' && restoreButton.parent === keptBar
	&& restoreButton.textContent === 'Restore previous version' && restoreButton.title === 'Previous version from 2026-09-02 08:30'
	&& keptBar.children.map( function( button ) { return button.id } ).join() === 'design-restore,design-reset,design-save,design-apply'
	&& restoreButton.hidden === false && kept.byId('design-reset').hidden === true );
check( '...and the card that used to hold the button, and the line that dated the version, are gone',
	kept.byId('design-state') === null && byClass( kept.form, 'design-previous' ).length === 0 && byClass( kept.byId('design-controls'), 'nino-admin-hint' ).length === 0 );

kept.willConfirm( false );
restoreButton.fire('click');

check( 'the button asks first, and a no sends nothing - it is the restore of the screen that is called, with the button itself',
	kept.confirms.length === 1 && kept.confirms[0] === TEXT['/_admin/design/confirm/restore'] && kept.requests.length === 0
	&& restoreCalls.length === 1 && restoreCalls[0] === restoreButton );

kept.willConfirm( true );
restoreButton.fire('click');

check( 'a yes posts the restore', JSON.stringify( endpoints( kept ) ) === JSON.stringify( [ 'restore' ] ) );

kept.answer( 200, {} );
kept.answer( 200, listing( {} ) );

check( 'and the panel is drawn again from the new state, saying so', ( kept.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored'] );

const noted = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
noted.willConfirm( true );
noted.byId('design-restore').fire('click');
noted.answer( 200, { notes : [ 'restored, but could not keep the version it replaced in data/design-previous.php' ] } );
noted.answer( 200, listing( {} ) );

check( 'a restore that went through with a note says restored, and the note beside it',
	( noted.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored']+ ' restored, but could not keep the version it replaced in data/design-previous.php' );

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
	&& noHelper.design._errorText( 503, null, '/_admin/design/error/save' ) === '(503) '+ TEXT['/_admin/design/error/save'] );
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

tracked.tab('Content');
check( '...opening a tab changes nothing that is unsaved', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );

tracked.move( 'Hero title', 'more' );
check( '...a knob that was moved does - the same answer the way back is shown for', registered.isDirty() === true && tracked.byId('design-reset').hidden === false );

tracked.byId('design-reset').fire('click');
check( '...and the way back, which draws the stored selection again, makes it not', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );

tracked.tab('Content');
tracked.move( 'Hero title', 'more' );
registered.discard();
check( 'discarding takes the stored selection for the screen\'s own', registered.isDirty() === false );

tracked.tab('Content');
tracked.move( 'Hero title', 'more' );
let savedFor = [];
registered.save( function( ok ) { savedFor.push( ok ) } );
check( 'the shell\'s Save stores the draft - and asks nothing, since applying is a question of its own',
	JSON.stringify( endpoints( tracked ) ) === JSON.stringify( [ 'save' ] ) && tracked.confirms.length === 0 && savedFor.length === 0 );

registered.save( function( ok ) { savedFor.push( ok ) } );
check( '...a second Save while the first is on its way says it did not save', savedFor.length === 1 && savedFor[0] === false );

tracked.answer( 200, {} );
check( '...and the first says it did, once the draft is stored', savedFor.length === 2 && savedFor[1] === true );

const refused = panel( {}, { dirty : registry } );
refused.tab('Content');
refused.move( 'Hero title', 'more' );
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


// --- The answer design/list really gives ---------------------------------------------------
//
// design-smoke.php writes down what the server answered for a project whose selection
// was moved - another template for each of the three tabs, a font changed, the animation,
// the width and the mode set - and hands the file to this run (DESIGN_LISTING). The
// stand-in above is what the screen is drawn from in the checks before; this is the half
// that keeps the stand-in honest: the same screen, drawn from the answer of the server

console.log( 'The answer of design/list' );

const listingFile = process.env.DESIGN_LISTING || '';

if( listingFile === '' || fs.existsSync( listingFile ) === false )
	console.log( '  --  no answer of design/list was handed to this run (DESIGN_LISTING), so the screen is not drawn from the real one' );
else {

	const real = JSON.parse( fs.readFileSync( listingFile, 'utf8' ) );
	const whole = panel( real, { whole : true } );

	/** Every text the screen shows that is a key nobody wrote: what the workbench shows for a fill that has no entry */
	const unwritten = function( screen ) {
		return descendants( screen.pane ).filter( function( el ) {
			return [ el.textContent, el.getAttribute('title'), el.getAttribute('aria-label') ].some( function( text ) { return typeof text === 'string' && text.indexOf('/_admin/') === 0 } );
		} ).map( function( el ) { return el.textContent || el.getAttribute('title') || el.getAttribute('aria-label') } );
	};

	let drawn = [];
	let failed = [];
	let blank = [];
	let unsaid = [];

	TABS.forEach( function( tab ) {

		if( threw( function() { whole.tab( TEXT['/_admin/design/tab/'+ tab] ) } ) === true ) {
			failed.push( tab );
			return;
		}

		drawn.push( tab );

		if( whole.body().children.length === 0 || whole.summary().length < 2 )
			blank.push( tab );

		unsaid = unsaid.concat( unwritten( whole ) );
	} );

	check( 'the server\'s answer draws all six tabs, each with controls in it and a summary under the frame'+ ( failed.length === 0 ? '' : ' - threw: '+ failed.join() ),
		drawn.join() === TABS.join() && failed.length === 0 && blank.length === 0 );
	check( '...and every word the screen shows for it is one the panel\'s texts carry - the effects, the families, the knobs, the modes'+ ( unsaid.length === 0 ? '' : ' - missing: '+ unsaid.slice( 0, 4 ).join() ), unsaid.length === 0 );

	whole.tab('Global');

	const rowOf = function( name ) { return real.parts.filter( function( row ) { return row.part === name } )[0] };

	/** Whether the select of a part is drawn on the tab that is open, over what the library has, on the template that is chosen */
	const selectOf = function( row ) {
		const select = whole.byId('design-set-'+ row.part);
		return select !== null && offered( select ).map( function( option ) { return option[0] } ).join() === Object.keys( row.catalogue ).join() && shown( select ) === row.set;
	};

	check( 'Global is drawn from the catalogue and the choices the server gave: each frame a select over what the library has, on the one that is chosen - and none of the three templates of the knob tabs',
		real.parts.filter( function( row ) { return row.kind === 'frame' } ).length === 2 && real.parts.filter( function( row ) { return row.kind === 'frame' } ).every( selectOf )
		&& real.parts.filter( function( row ) { return row.kind === 'set' } ).every( function( row ) { return whole.byId('design-set-'+ row.part) === null } ) );
	check( '...the size, the width and the animation on the values stored',
		shown( whole.byId('design-size') ) === real.size && shown( whole.byId('design-pagewidth') ) === String( real.width )
		&& shown( whole.byId('design-motion-effect') ) === real.motion.effect && shown( whole.byId('design-motion-strength') ) === real.motion.strength
		&& shown( whole.byId('design-motion-speed') ) === real.motion.speed
		&& offered( whole.byId('design-motion-effect') ).map( function( option ) { return option[0] } ).join() === [ '' ].concat( real.effects ).join()
		&& whole.byId('design-motion-strength').disabled === ( real.motion.effect === '' ) );

	whole.tab('Fonts');

	check( 'Fonts is drawn from the roles, the families each role is offered, the bounds of a size and the fonts that are stored: a row for each role, every control on the stored value',
		byTag( whole.form, 'tr' ).filter( function( row ) { return row.dataset.role !== undefined } ).map( function( row ) { return row.dataset.role } ).join() === real.roles.join()
		&& real.roles.every( function( role ) {
			const chosen = real.fonts[role];
			return offered( whole.byId('design-font-'+ role+ '-family') ).map( function( option ) { return option[0] } ).join() === real.options[role].join()
				&& shown( whole.byId('design-font-'+ role+ '-family') ) === chosen.family && shown( whole.byId('design-font-'+ role+ '-weight') ) === chosen.weight
				&& whole.byId('design-font-'+ role+ '-size').value === chosen.size
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('min') === String( real.bounds[role][0] ) && whole.byId('design-font-'+ role+ '-size').getAttribute('max') === String( real.bounds[role][1] )
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('placeholder') === real.inherited[role]
				&& ( role === 'code' ? whole.byId('design-font-code-uppercase') === null : whole.byId('design-font-'+ role+ '-uppercase').checked === chosen.uppercase );
		} ) );

	let tabProblems = [];

	Object.keys( real.tabs ).forEach( function( tab ) {

		whole.tab( TEXT['/_admin/design/tab/'+ tab] );

		const row = rowOf( tab );
		const answered = ( row.catalogue[row.set] || {} ).knobs || [];
		const drawnRows = byClass( whole.form, 'design-knob-row' );

		if( selectOf( row ) === false || whole.byId('design-knobs').children[0] !== whole.byId('design-set-'+ tab).parent )
			tabProblems.push( tab+ ': the select of its template, first in the card' );
		const off = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) } ).map( function( one ) { return one.dataset.knob } );
		const lit = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) === false } ).every( function( one ) {
			const position = real.knobs[tab][one.dataset.knob];
			return byTag( one, 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === [ -1, 0, 1 ].map( function( at ) { return String( at === position ) } ).join();
		} );

		if( drawnRows.map( function( one ) { return one.dataset.knob } ).join() !== real.tabs[tab].join() )
			tabProblems.push( tab+ ': rows '+ drawnRows.map( function( one ) { return one.dataset.knob } ).join() );
		if( off.join() !== real.tabs[tab].filter( function( knob ) { return answered.indexOf( knob ) === -1 } ).join() )
			tabProblems.push( tab+ ': grey rows '+ off.join() );
		if( lit === false )
			tabProblems.push( tab+ ': a step is lit that the knob does not stand at' );
		if( whole.summary().length !== answered.length + 2 )
			tabProblems.push( tab+ ': summary of '+ whole.summary().length );
	} );

	check( 'each knob tab is the select of its template over a row for every knob of the tab, grey where the chosen template does not answer to it, the others lit on the position stored, and a summary of the rest'
		+ ( tabProblems.length === 0 ? '' : ' - '+ tabProblems.join(' | ') ), tabProblems.length === 0 && Object.keys( real.tabs ).join() === 'content,buttons,components' );
	check( '...and the templates the server stored are the ones with something grey: a template that leaves a knob out is on show',
		Object.keys( real.tabs ).some( function( tab ) { return ( ( rowOf( tab ).catalogue[ rowOf( tab ).set ] || {} ).knobs || [] ).length < real.tabs[tab].length } ) );

	whole.tab('Colours');

	check( 'Colours is drawn from the palette the server published: the five knobs, each over as many positions as it has, and the mode on the stored word',
		byClass( whole.form, 'design-knob-row' ).length === Object.keys( real.palette ).length - 1
		&& Object.keys( real.palette ).filter( function( key ) { return key !== 'mode' } ).every( function( key ) {
			return byTag( whole.knobRow( TEXT['/_admin/design/colour/'+ key+ '/label'] ), 'button' ).length === real.palette[key].steps.length;
		} )
		&& offered( whole.byId('design-colour-mode') ).map( function( option ) { return option[0] } ).join() === real.palette.mode.steps.join()
		&& shown( whole.byId('design-colour-mode') ) === real.colours.mode );

	whole.design._previewNow();
	const sent = whole.requests[whole.requests.length - 1];

	check( 'the selection the screen posts is made of exactly the keys design/save and design/preview take, each of the shape the server answered it in',
		sent.endpoint === 'preview' && Object.keys( sent.payload ).sort().join() === 'colours,fonts,full,knobs,motion,parts,size,width'
		&& JSON.stringify( sent.payload.parts ) === JSON.stringify( ( function() { const parts = {}; real.parts.forEach( function( row ) { parts[row.part] = row.set } ); return parts } )() )
		&& JSON.stringify( sent.payload.knobs ) === JSON.stringify( real.knobs ) && JSON.stringify( sent.payload.fonts ) === JSON.stringify( real.fonts )
		&& JSON.stringify( sent.payload.motion ) === JSON.stringify( real.motion ) && sent.payload.size === real.size && sent.payload.width === real.width
		&& JSON.stringify( sent.payload.colours ) === JSON.stringify( real.colours ) );
	check( '...and a screen drawn from the answer holds nothing unsaved', whole.design._isDirty() === false );
}

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
