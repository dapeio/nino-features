/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the strip of six tabs in the head of the
 *												pane, and under it the controls of the tab that is open
 *												- the five templates, the page and its animation; the
 *												five font roles; the named knobs of content, buttons
 *												and components, greyed where the chosen template does
 *												not answer to them; the palette and its mode - the
 *												state of the files over them, the summary under the
 *												preview frame, and the scroll of the frame to the
 *												anchor of the tab.
 *
 *												The summary is the half a picture cannot give: a
 *												preview says what a design looks like and nothing
 *												about which selection produced it, so the checks here
 *												read it back after every kind of change the screen
 *												allows - a knob moved, another template chosen, a font
 *												changed, another tab opened.
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
	content 		: [ 'hero-title', 'hero-text', 'quiet', 'loud', 'section-space', 'article-space', 'corners', 'border', 'shadow' ],
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
		sizes 		: [ 's', 'm', 'l' ],
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
		fontsizes : {
			title 		: [ '1.6', '1.7', '2.4', '4' ],
			subtitle 	: [ '1', '1.5', '2' ],
			text 			: [ '0.875', '1', '1.125', '1.25' ],
			button 		: [ '0.75', '1', '1.25' ],
			code 			: [ '0.75', '0.875', '1' ],
		},
		inherited : { title : '1.6', subtitle : '1.125', text : '1', button : '1', code : '0.875' },
		colours		: { primary : '#4faae8', secondary : '', mode : 'auto' },
		palette		: {
			harmony			: { 'default' : 1, steps : [ 'Monochrome', 'Analogous', 'Triadic', 'Complementary' ] },
			temperature	: { 'default' : 3, steps : [ 'Neutral', 'Cool', 'Brand', 'Warm' ] },
			saturation	: { 'default' : 2, steps : [ 'Muted', 'Standard', 'Rich' ] },
			contrast		: { 'default' : 2, steps : [ 'Soft', 'Standard', 'Strong' ] },
			depth				: { 'default' : 2, steps : [ 'Flat', 'Standard', 'Raised' ] },
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

	const stage = { scrolls : [], asked : [], reachable : true, scrollY : 100, clearance : 0, tops : { type : 700, content : 300, buttons : 1200, components : 1500, colours : 450 } };

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
			requests.push( { endpoint : endpoint, payload : JSON.parse( JSON.stringify( payload ) ), answer : callback } );
		};

	design._data	= ( shell || {} ).whole === true ? over : listing( over );
	design._edit	= design._selection( design._data );
	design._ready	= true;
	design._render();

	return {
		design 	: design,
		requests: requests,
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
				return ( byClass( row, 'design-knob-label' )[0] || {} ).textContent === label;
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

/** The word over the controls - which group of the design the column is showing */
function eyebrow( screen ) {
	return ( byClass( screen.form, 'design-eyebrow' )[0] || {} ).textContent;
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
	&& eyebrow( headed ) === 'Palette' && headed.byId('design-frame') !== null );

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
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && eyebrow( keyed ) === 'Font roles' );

if( keyTabs.length === 6 )
	press( keyTabs[1], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === 6 && focused === keyTabs[0] && keyTabs[0].parent === keyed.headStrips()[0]
	&& keyTabs[0].getAttribute('aria-selected') === 'true' && eyebrow( keyed ) === 'Templates and page' );

if( keyTabs.length === 6 )
	press( keyTabs[0], 'ArrowLeft' );

check( '...and from the first tab the arrow key wraps round to the last', keyTabs.length === 6 && focused === keyTabs[5] && eyebrow( keyed ) === 'Palette' );

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


// --- The shape of the column -----------------------------------------------------
//
// Controls on one side and the page on the other: the state of the files and
// the open tab in the column, the frame and the summary in the preview, the
// two buttons under both

console.log( 'The column' );

const column = panel();
const layout = column.byId('design-layout');
const controls = column.byId('design-controls');
const previewBox = column.byId('design-preview');

check( 'the screen is a layout of two columns - the controls and the preview - with the bar of actions under it',
	layout !== null && layout.children.length === 2 && layout.children[0] === controls && layout.children[1] === previewBox
	&& column.form.children.indexOf( layout ) < column.form.children.indexOf( byClass( column.form, 'nino-admin-actionbar' )[0] ) );
check( 'the state of the files stands first in the column, over the open tab - true whichever tab is open',
	controls.children[0] === column.byId('design-state') && hasClass( controls.children[0], 'nino-admin-card' ) );
check( '...then one eyebrow naming the group of controls under it, and the box the tab fills',
	controls.children[1] === byClass( controls, 'design-eyebrow' )[0] && controls.children[1].textContent === 'Templates and page'
	&& controls.children[2] === column.body() && controls.children.length === 3 );
check( 'the frame and the summary stand in the preview, the summary under the frame',
	column.byId('design-frame') !== null && descendants( previewBox ).indexOf( column.byId('design-summary') ) > descendants( previewBox ).indexOf( column.byId('design-frame') ) );
check( '...the frame a sandbox that lets the page run and be reached by the panel, and no more',
	column.byId('design-frame').getAttribute('sandbox') === 'allow-scripts allow-same-origin' );

const stateBox = column.byId('design-state');

check( 'the state card says when the file was last applied and that it answers to the selection - and nothing is marked while it does',
	( byTag( stateBox, 'strong' )[0] || {} ).textContent === 'Applied file' && ( byTag( stateBox, 'small' )[0] || {} ).textContent === 'Last applied 2026-09-01 10:00'
	&& byClass( stateBox, 'design-state--warn' ).length === 0 && byTag( stateBox, 'p' )[0].textContent === TEXT['/_admin/design/state/current'] );

const drifted = panel( { current : false } );

check( '...and a file that does not answer to it is marked, and the last row of the summary says it in one word',
	byClass( drifted.byId('design-state'), 'design-state--warn' )[0].textContent === TEXT['/_admin/design/state/drifted']
	&& JSON.stringify( drifted.summary().pop() ) === JSON.stringify( [ 'Applied file', 'Saved, not applied' ] ) );

const told = panel( { notes : [ 'there is no part "nowhere" - dropped' ] } );

check( 'what the server dropped or replaced on the way is told in the column, under the state of the files',
	byClass( told.byId('design-controls'), 'nino-admin-error' ).map( function( line ) { return line.textContent } ).join() === 'there is no part "nowhere" - dropped'
	&& told.byId('design-controls').children.indexOf( byClass( told.byId('design-controls'), 'nino-admin-error' )[0] ) === 1 );

console.log('');

// --- Global: the templates, the page and its animation ------------------------------

console.log( 'Global' );

const global = panel();
const setSelect = function( name ) { return global.byId('design-set-'+ name) };

check( 'the five templates are five selects, in the order the cascade wants them, each over its catalogue',
	[ 'header', 'footer', 'content', 'buttons', 'components' ].every( function( name ) { return setSelect( name ) !== null } )
	&& JSON.stringify( offered( setSelect('header') ) ) === JSON.stringify( [ [ 'v1', 'v1 - Plain bar' ], [ 'v2', 'v2 - Centred' ] ] )
	&& JSON.stringify( offered( setSelect('buttons') ) ) === JSON.stringify( [ [ 'v1', 'v1 - Nino' ], [ 'v3', 'v3 - Square' ] ] )
	&& [ 'header', 'footer', 'content', 'buttons', 'components' ].map( function( name ) { return shown( setSelect( name ) ) } ).join() === 'v1,v1,v1,v1,v1' );
check( '...named in the words of the panel, with the description of the chosen one under it',
	byTag( global.body(), 'label' ).map( function( label ) { return label.textContent } ).slice( 0, 5 ).join() === 'Header template,Footer template,Content template,Buttons template,Components template'
	&& byTag( setSelect('header').parent, 'small' )[0].textContent === 'A bar with a rule under it' );

choose( setSelect('header'), 'v2' );

check( 'choosing another one is the selection on screen, and the note under it follows the choice',
	global.design._edit.parts.header === 'v2' && byTag( setSelect('header').parent, 'small' )[0].textContent === 'The logo in the middle'
	&& global.byId('design-reset').hidden === false );

check( 'the size is one select - small, default, large - and the width of the page another: narrow, default, wide, as the numbers the model keeps',
	JSON.stringify( offered( global.byId('design-size') ) ) === JSON.stringify( [ [ 's', 'small' ], [ 'm', 'default' ], [ 'l', 'large' ] ] ) && shown( global.byId('design-size') ) === 'm'
	&& JSON.stringify( offered( global.byId('design-pagewidth') ) ) === JSON.stringify( [ [ '-1', 'narrow' ], [ '0', 'default' ], [ '1', 'wide' ] ] ) && shown( global.byId('design-pagewidth') ) === '0' );

choose( global.byId('design-size'), 'l' );
choose( global.byId('design-pagewidth'), '1' );

check( 'choosing them stores a size and a number',
	global.design._edit.size === 'l' && global.design._edit.width === 1 );

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
		[ 'Root size', 'large' ],
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
// A tab for each of the three templates that have knobs, one row for every knob
// the tab has - each at less, the template as it is, or more, in the words that
// knob has for them. A knob the chosen template does not answer to stands where
// it always does, greyed out, with the line that says whose decision that is

console.log( 'The knob tabs' );

const moved = listing().knobs;
moved.content.shadow = 1;
moved.buttons.corners = -1;

const rows = panel( { knobs : moved } );
rows.tab('Content');

const contentRows = byClass( rows.form, 'design-knob-row' );

check( 'the Content tab has a row for each of its nine knobs, named in the panel\'s words and in the order of the tab',
	eyebrow( rows ) === 'Hero, sections and cards' && contentRows.length === 9
	&& contentRows.map( function( row ) { return ( byClass( row, 'design-knob-label' )[0] || {} ).textContent } ).join()
		=== KNOBS.content.map( function( knob ) { return TEXT['/_admin/design/knob/content/'+ knob+ '/label'] } ).join()
	&& contentRows.map( function( row ) { return row.dataset.knob } ).join() === KNOBS.content.join() );
check( '...each with a note beside its name, and three positions in the words that knob has for them',
	contentRows.every( function( row, at ) {
		const knob = KNOBS.content[at];
		return ( byTag( row, 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/'+ knob+ '/note']
			&& byTag( row, 'button' ).map( function( button ) { return button.textContent } ).join() === [ 'less', 'default', 'more' ].map( function( step ) {
				return TEXT['/_admin/design/knob/content/'+ knob+ '/'+ step];
			} ).join();
	} ) );
check( '...a group of three steps, not a tablist - the one it stands at is pressed and the other two are not',
	contentRows.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& byTag( rows.knobRow('Hero title'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,true,false'
	&& byTag( rows.knobRow('Shadow of a card'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true' );
check( 'and the block over the rows says which template these knobs are the knobs of, and where that is chosen',
	byClass( rows.form, 'design-section-label' ).filter( function( heading ) { return heading.parent === rows.byId('design-knobs') } ).length === 1
	&& ( byTag( rows.byId('design-knobs'), 'strong' )[0] || {} ).textContent === 'Template: v1 - Nino'
	&& ( byTag( rows.byId('design-knobs'), 'small' )[0] || {} ).textContent === TEXT['/_admin/design/hint/knobs'] );
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
		knobSummary( 'content', { 'hero-title' : -1, 'hero-text' : 1, quiet : 0, loud : 0, 'section-space' : 0, 'article-space' : 0, corners : 0, border : 0, shadow : 1 } ),
		[ [ 'Applied file', 'Up to date' ] ] ) ) );

/*	Another template brings its own knobs, and the ones the last one had are
	not this one's: a position the new template does not answer to goes back to
	the middle, the way the server holds it anyway - so the screen never shows a
	setup the stored one could not be	*/
rows.tab('Global');
choose( rows.byId('design-set-content'), 'v2' );

check( 'choosing a template that leaves a knob out puts the position of that knob back in the middle, and leaves the others where they were',
	rows.design._edit.parts.content === 'v2' && rows.design._edit.knobs.content.shadow === 0
	&& rows.design._edit.knobs.content['hero-title'] === -1 && rows.design._edit.knobs.content['hero-text'] === 1 );

rows.tab('Content');

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
	&& ( byTag( rows.byId('design-knobs'), 'strong' )[0] || {} ).textContent === 'Template: v2 - Poster' );
check( 'the summary leaves the knob out, since it compiles nothing: eight knobs, the template and the file',
	rows.summary().length === 10 && rows.summary().every( function( row ) { return row[0] !== 'Shadow of a card' } ) && rows.summary()[0][1] === 'v2 - Poster' );

rows.tab('Global');
choose( rows.byId('design-set-content'), 'v1' );
rows.tab('Content');

check( 'choosing the template that has it again takes the grey away, with the knob in the middle',
	byClass( rows.form, 'design-knob-row--off' ).length === 0 && rows.design._edit.knobs.content.shadow === 0
	&& byTag( rows.knobRow('Shadow of a card'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,true,false' );

// The other two tabs, and their own templates
rows.tab('Buttons');
check( 'the Buttons tab has its four knobs, and Square leaves the corners to the template - which the stored position cannot have been',
	eyebrow( rows ) === 'Buttons' && byClass( rows.form, 'design-knob-row' ).length === 4 && byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& rows.design._edit.knobs.buttons.corners === -1 );

rows.tab('Global');
choose( rows.byId('design-set-buttons'), 'v3' );
rows.tab('Buttons');

check( '...it is back in the middle, and the row is grey',
	rows.design._edit.knobs.buttons.corners === 0 && byClass( rows.form, 'design-knob-row--off' ).length === 1
	&& rows.knobRow('Corners') !== null && hasClass( rows.knobRow('Corners'), 'design-knob-row--off' ) );

rows.tab('Global');
choose( rows.byId('design-set-components'), 'v4' );
rows.tab('Components');

check( 'the Components tab has six rows, two of them grey for Filled: the lines and where the labels stand',
	eyebrow( rows ) === 'Forms, tables and blocks' && byClass( rows.form, 'design-knob-row' ).length === 6
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
	eyebrow( fonts ) === 'Font roles' && table.getAttribute('role') === 'table'
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
check( 'a weight is one of three words, the size starts on what Nino.css gives the role - named, value first so that a column too narrow for the word still shows it, and not one of the steps - and goes on in rem',
	JSON.stringify( offered( weight('title') ) ) === JSON.stringify( [ [ 'light', 'Light' ], [ 'normal', 'Normal' ], [ 'bold', 'Bold' ] ] ) && shown( weight('title') ) === 'bold' && shown( weight('code') ) === 'normal'
	&& JSON.stringify( offered( size('title') ) ) === JSON.stringify( [ [ '', '1.6 rem (default)' ], [ '1.6', '1.6 rem' ], [ '1.7', '1.7 rem' ], [ '2.4', '2.4 rem' ], [ '4', '4 rem' ] ] )
	&& offered( size('subtitle') )[0][1] === '1.125 rem (default)' && offered( size('code') )[0][1] === '0.875 rem (default)' && shown( size('title') ) === '' );
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
choose( size('title'), '2.4' );

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

console.log('');


// --- Colours: the palette and its mode ------------------------------------------------

console.log( 'Colours' );

const colours = panel();
colours.tab('Colours');

check( 'the Colours tab has the brand colour, the five knobs the rest of the palette is solved with - the second colour at the end of the first of them - and the mode',
	eyebrow( colours ) === 'Palette' && colours.byId('design-colour-primary').value === '#4faae8'
	&& byClass( colours.form, 'design-knob-row' ).map( function( row ) { return ( byClass( row, 'design-knob-label' )[0] || {} ).textContent } ).join() === 'Second colour,Temperature,Saturation,Contrast,Depth'
	&& colours.byId('design-colour-secondary') !== null && colours.byId('design-colour-mode') !== null );
check( '...four positions for Harmony and Temperature, three for the others, each in the words of the panel - and the one the knob starts on lit',
	[ 4, 4, 3, 3, 3 ].join() === byClass( colours.form, 'design-knob-row' ).map( function( row ) { return byTag( row, 'button' ).length } ).filter( function( count, at ) { return at < 5 } ).join()
	&& byTag( colours.knobRow('Temperature'), 'button' ).map( function( button ) { return button.textContent } ).join() === 'Neutral,Cool,Brand,Warm'
	&& byTag( colours.knobRow('Temperature'), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === 'false,false,true,false' );
check( 'the mode is a select of three words - automatic, light only, dark only - under a heading of its own, and starts on the first',
	JSON.stringify( offered( colours.byId('design-colour-mode') ) ) === JSON.stringify( [ [ 'auto', 'automatic' ], [ 'light', 'light only' ], [ 'dark', 'dark only' ] ] )
	&& shown( colours.byId('design-colour-mode') ) === 'auto' && colours.byId('design-colour-mode').getAttribute('aria-label') === 'Mode'
	&& byClass( colours.form, 'design-colour-mode' ).length === 1 );

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
		[ 'Contrast', 'Standard' ], [ 'Depth', 'Standard' ], [ 'Mode', 'dark only' ], [ 'Applied file', 'Up to date' ],
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


// --- What the preview carries ----------------------------------------------------------------

console.log( 'The preview' );

const asked = panel();
asked.design._previewNow();
const first = asked.requests[asked.requests.length - 1];

check( 'the first preview of a visit asks for the whole document, with every key of the model: the templates, the knobs, the size, the width, the animation, the fonts and the palette',
	first.endpoint === 'preview' && first.payload.full === true && Object.keys( first.payload ).sort().join() === 'colours,fonts,full,knobs,motion,parts,size,width'
	&& first.payload.parts.header === 'v1' && first.payload.parts.components === 'v1' && first.payload.knobs.content['hero-title'] === 0
	&& first.payload.motion.effect === '' && first.payload.colours.mode === 'auto' );

asked.answer( 200, { css : ':root{}', document : '<!doctype html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
asked.design._previewNow();

check( '...the next one only a stylesheet, as long as the header and the footer are the ones the frame was built with', asked.requests[asked.requests.length - 1].payload.full === false );

asked.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [ 'there is no template "x" - using "v1"' ] } );

check( 'what the server had to replace comes back beside the preview, in the line over the frame',
	( asked.byId('design-preview-msg') || {} ).textContent === 'there is no template "x" - using "v1"' && hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' ) );

choose( asked.byId('design-set-footer'), 'v2' );
asked.design._previewNow();

check( '...and another header or footer is markup, so the document is asked for again', asked.requests[asked.requests.length - 1].payload.full === true );

asked.answer( 500, { error : 'broken' } );

check( 'a preview that failed says so in that line, as an error', hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' ) && asked.byId('design-preview-msg').textContent === '(500) broken' );

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

check( 'with no previous version there is nothing to restore', empty.byId('design-restore') === null );

const kept = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
const restoreButton = kept.byId('design-restore');

check( 'with one the state box dates it and offers to restore it',
	restoreButton !== null && restoreButton.textContent === 'Restore previous version'
	&& byClass( kept.byId('design-state'), 'design-previous' )[0].textContent === 'Previous version from 2026-09-02 08:30' );

kept.willConfirm( false );
restoreButton.fire('click');

check( 'the button asks first, and a no sends nothing', kept.confirms.length === 1 && kept.confirms[0] === TEXT['/_admin/design/confirm/restore'] && kept.requests.length === 0 );

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

	check( 'Global is drawn from the catalogue and the choices the server gave: each template a select over what the library has, on the one that is chosen',
		real.parts.every( function( row ) {
			const select = whole.byId('design-set-'+ row.part);
			return select !== null && offered( select ).map( function( option ) { return option[0] } ).join() === Object.keys( row.catalogue ).join() && shown( select ) === row.set;
		} ) );
	check( '...the size, the width and the animation on the values stored',
		shown( whole.byId('design-size') ) === real.size && shown( whole.byId('design-pagewidth') ) === String( real.width )
		&& shown( whole.byId('design-motion-effect') ) === real.motion.effect && shown( whole.byId('design-motion-strength') ) === real.motion.strength
		&& shown( whole.byId('design-motion-speed') ) === real.motion.speed
		&& offered( whole.byId('design-motion-effect') ).map( function( option ) { return option[0] } ).join() === [ '' ].concat( real.effects ).join()
		&& whole.byId('design-motion-strength').disabled === ( real.motion.effect === '' ) );

	whole.tab('Fonts');

	check( 'Fonts is drawn from the roles, the families each role is offered and the fonts that are stored: a row for each role, every control on the stored value',
		byTag( whole.form, 'tr' ).filter( function( row ) { return row.dataset.role !== undefined } ).map( function( row ) { return row.dataset.role } ).join() === real.roles.join()
		&& real.roles.every( function( role ) {
			const chosen = real.fonts[role];
			return offered( whole.byId('design-font-'+ role+ '-family') ).map( function( option ) { return option[0] } ).join() === real.options[role].join()
				&& shown( whole.byId('design-font-'+ role+ '-family') ) === chosen.family && shown( whole.byId('design-font-'+ role+ '-weight') ) === chosen.weight
				&& shown( whole.byId('design-font-'+ role+ '-size') ) === chosen.size
				&& offered( whole.byId('design-font-'+ role+ '-size') ).map( function( option ) { return option[0] } ).join() === [ '' ].concat( real.fontsizes[role] ).join()
				&& ( role === 'code' ? whole.byId('design-font-code-uppercase') === null : whole.byId('design-font-'+ role+ '-uppercase').checked === chosen.uppercase );
		} ) );

	let tabProblems = [];

	Object.keys( real.tabs ).forEach( function( tab ) {

		whole.tab( TEXT['/_admin/design/tab/'+ tab] );

		const row = rowOf( tab );
		const answered = ( row.catalogue[row.set] || {} ).knobs || [];
		const drawnRows = byClass( whole.form, 'design-knob-row' );
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

	check( 'each knob tab is a row for every knob of the tab, grey where the chosen template does not answer to it, the others lit on the position stored, and a summary of the rest'
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
