/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the strip of seven tabs in the head of the
 *												pane, and under it the controls of the tab that is open
 *												- the two frames, the root size and the width of the
 *												page as two strips of five, and the animation; the five
 *												font roles, with a size that is typed; the two cards
 *												of the Typefaces tab - the families that are installed
 *												and the list of those on offer, searched, told apart
 *												by category and paged, with an install and a removal
 *												made at once, the names set in their own faces and a
 *												sentence of its own for each thing a refusal of the
 *												list can be about; the template of content, buttons and
 *												components over its named knobs,
 *												each a strip with its icon - five numbers, or the three
 *												words of a choice - greyed where the chosen template
 *												does not answer to them; the palette and its mode - the
 *												action bar, with the line the panel says things in, the
 *												chip for the state of the files and the way back to the
 *												previous version, the head of the preview - one row,
 *												with the viewport switch of three icon buttons and the
 *												switch that lets the frame follow - the room the frame
 *												takes under it, down to the bar, and the scroll of the
 *												frame to the anchor of the tab - which waits for the
 *												page in it where the tab, or the answer to a change,
 *												changed the stage's height - or to where the change
 *												of a knob shows.
 *
 *												A preview says what a design looks like and nothing
 *												about which selection produced it, and nothing stands
 *												under the frame to say it: the checks here read what the
 *												screen says off the controls that are drawn, after every
 *												kind of change it allows - a knob moved, another template
 *												chosen, a font changed, another tab opened. The words of
 *												the steps are read off the buttons that are drawn.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node. design-smoke.php runs it through
 *												node when node is on the path, so bin/check.sh and
 *												CI cover it - and hands it the answers design/list
 *												(DESIGN_LISTING) and design/webfonts/list
 *												(DESIGN_WEBFONTS) really gave, which the screen is
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
		style				: {},
		// Where the element stands in the window - nowhere, which is where one is that is not on screen - for a test to place the ones that are measured
		rect				: { top : 0, left : 0, width : 0, height : 0 },
		// Which of its computed style a test says: what getComputedStyle() answers for it
		computed		: {},
		getBoundingClientRect	: function() { return this.rect },
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
			// A row of the Typefaces tab is asked for the element its name is in
			if( selector.charAt(0) === '.' )
				return byClass( this, selector.slice(1) )[0] || null;
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

/** The word a select shows - the label of the option marked selected */
function wordOf( select ) {
	const on = byTag( select, 'option' ).filter( function( option ) { return option.selected === true } )[0];
	return on === undefined ? null : on.textContent;
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

const TABS = [ 'global', 'fonts', 'typefaces', 'content', 'buttons', 'components', 'colours' ];

/** The kind of every knob, by tab, as design/list gives them: a scale has five positions, -2 to +2, a choice three, -1 to +1 */
const KINDS = {
	content 		: { 'hero-title' : 'scale', 'hero-text' : 'scale', quiet : 'scale', loud : 'scale', 'section-space' : 'scale', 'card-space' : 'scale', corners : 'scale', border : 'scale', shadow : 'scale' },
	buttons 		: { size : 'scale', spread : 'scale', space : 'scale', corners : 'scale' },
	components 	: { scale : 'scale', space : 'scale', corners : 'scale', border : 'scale', zebra : 'choice', labels : 'choice' },
};

/** The names of the knobs of each tab, in the order of the tab */
const KNOBS = {};
Object.keys( KINDS ).forEach( function( tab ) { KNOBS[tab] = Object.keys( KINDS[tab] ) } );

/** What the five buttons of a scale say, from the least to the most: the distance from the middle, the minus U+2212 and not a hyphen */
const SIGNED = [ '\u22122', '\u22121', '0', '+1', '+2' ];

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
		tabs 			: JSON.parse( JSON.stringify( KINDS ) ),
		knobs 		: knobs,
		steps 		: [ 'least', 'less', 'default', 'more', 'most' ],
		size 			: 0,
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
			harmony			: { kind : 'choice', 'default' : 1, steps : [ 'Monochrome', 'Analogous', 'Triadic', 'Complementary' ] },
			temperature	: { kind : 'choice', 'default' : 3, steps : [ 'Neutral', 'Cool', 'Brand', 'Warm' ] },
			saturation	: { kind : 'scale', 'default' : 3, steps : 5 },
			contrast		: { kind : 'scale', 'default' : 3, steps : 5 },
			depth				: { kind : 'scale', 'default' : 3, steps : 5 },
			mode				: { kind : 'choice', 'default' : 'auto', steps : [ 'auto', 'light', 'dark' ] },
		},
		// The repository of typefaces has an address and a key, so that the Available half of the Typefaces tab is offered
		webfonts: { url : true, key : true },
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
 *	the window it scrolls and waits on: what it listens to and the animation
 *	frames it asks for are recorded, and the test lets them come (resize(),
 *	animate()) as it lets the timers elapse. Where each anchor stands is the
 *	test's to say.
 */
function frameStage() {

	const stage = { scrolls : [], asked : [], listeners : [], frames : [], reachable : true, scrollY : 100, clearance : 0, tops : { type : 700, content : 300, cards : 900, buttons : 1200, components : 1500, colours : 450 } };

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
			// [ type, function ] of what the panel listens to, and the functions it asked an animation frame for
			addEventListener : function( type, fn ) { stage.listeners.push( [ type, fn ] ) },
			removeEventListener : function( type, fn ) { stage.listeners = stage.listeners.filter( function( one ) { return one[0] !== type || one[1] !== fn } ) },
			requestAnimationFrame : function( fn ) { stage.frames.push( fn ); return stage.frames.length },
			getComputedStyle : function( node ) { return node === stage.main ? { paddingTop : stage.clearance+ 'px' } : {} },
		},
	};
	Object.defineProperty( stage.document, 'fonts', { get : function() { return stage.fonts } } );
	Object.defineProperty( stage.document.defaultView, 'scrollY', { get : function() { return stage.scrollY } } );

	// The window hears a resize: whatever listens to it is called, the way the browser does it
	stage.resize = function() { stage.listeners.slice().forEach( function( one ) { if( one[0] === 'resize' ) one[1]() } ) };
	// The animation frames that were asked for come: each runs once
	stage.animate = function() { stage.frames.splice( 0 ).forEach( function( fn ) { fn() } ) };

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
 *	the screen where there is none
 *
 *	@param		{Object}	[over]		What this screen's listing has differently
 *	@param		{Object}	[shell]		{ head : false } for a pane without a head; raw: true leaves
 *															the panel's own _apiCall in place, and { api } stands in for
 *															Nino.adminUi.api and { dirty } for the shell's
 *															Nino.admin.dirty;
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
		fonts						: {
			add			: function( face ) { typeset.set.push( face ) },
			delete	: function( face ) { typeset.set = typeset.set.filter( function( one ) { return one !== face } ) },
		},
	};

	const requests = [];

	/*	The fonts of the document the panel is in, and what the Typefaces tab sets names in them with: a FontFace is kept
		as it was made and as often as it was asked to load, the FontFaceSet as it was added to and taken from, and an
		observer as it was told what to watch. A test says how a load ends (fails) and lets the observer see a row	*/
	const typeset = { made : [], set : [], fails : false, observers : [] };

	/*	The timers the script sets, in order, as [ function, delay ] - one that was cleared is null.
		None of them fires by itself: what the clicking asks for is what is asserted on, and a
		test runs the one it wants (see timer())	*/
	const timers = [];

	// What the script listens to on the window, as [ type, function, capture ]
	const heard = [];

	/*	What the window says of itself, and a test changes: the height of the room the frame takes
		the stage's from, the font size of the root and the bar's height as the workbench declares
		it - a length in rem, which the root's font size turns into pixels	*/
	const view = { innerHeight : 800, fontSize : '16px', bar : '4.25rem' };

	const rootStyle = {
		get fontSize() { return view.fontSize },
		getPropertyValue : function( name ) { return name === '--nino-admin-actionbar-height' ? view.bar : '' },
	};

	// The widths the frame was asked to be fitted at, in order - and how tall the port was then, which is what the stand-in of scaleFrame() records
	const fitted = [];

	/*	What the browser's own question does here: the text it was asked with is
		kept, and the answer is whatever the test last said it would be - yes	*/
	const confirms = [];
	let confirmAnswer = true;

	const Nino = {
		adminUi : {
			// The workbench's request helper: every request goes through _apiCall,
			// which this test holds. errorText() says a failure that has no code of
			// its own as "(status) message"
			api					: {
				call : function() { throw new Error( 'every request goes through _apiCall, which this test holds' ) },
				errorText : function( status, response, key ) { return '('+ status+ ') '+ ( ( response && response.error ) ? response.error : Nino.content.getText( key ) ) },
			},
			actionBar		: function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
			// Records what it was asked to fit: the frame, the port and the layout width
			scaleFrame	: function( frame, port, width ) { fitted.push( { frame : frame, port : port, width : width, height : port.style.height } ); return function() {} },
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
	};

	if( ( shell || {} ).api )
		Nino.adminUi.api = shell.api;

	// The shell's registry of unsaved input: nothing is unsaved in it unless a test brings its own
	Nino.admin = { dirty : ( shell || {} ).dirty || { register : function() {}, isDirty : function() { return false }, refresh : function() {} } };

	const sandbox = {
		console			: console,
		document		: dc,
		Nino				: Nino,
		confirm			: function( text ) { confirms.push( text ); return confirmAnswer },
		setTimeout	: function( fn, delay ) { timers.push( [ fn, delay ] ); return timers.length },
		clearTimeout: function( id ) { if( id > 0 ) timers[id - 1] = null },
		addEventListener	: function( type, fn, capture ) { heard.push( [ type, fn, capture ] ) },
		getComputedStyle	: function( node ) { return node === dc.documentElement ? rootStyle : node.computed },
	};
	Object.defineProperty( sandbox, 'innerHeight', { get : function() { return view.innerHeight } } );
	sandbox.window = sandbox;

	sandbox.FontFace = function( family, source ) {
		this.family = family;
		this.source = source;
		this.loads = 0;
		this.caught = 0;
		typeset.made.push( this );
	};
	/*	A load that fails is a promise that is rejected, and one nobody has a hand on is an error that ends a run of node: the
		hands put on it are counted (caught), and a test says that every load was given one	*/
	sandbox.FontFace.prototype.load = function() {
		const face = this;
		const outcome = typeset.fails === true ? Promise.reject( new Error('NetworkError') ) : Promise.resolve( face );
		const catcher = outcome.catch;
		face.loads++;
		outcome.catch = function() { face.caught++; return catcher.apply( outcome, arguments ) };
		return outcome;
	};

	sandbox.IntersectionObserver = function( callback ) {
		this.callback = callback;
		this.watched = [];
		this.unwatched = [];
		this.ended = 0;
		typeset.observers.push( this );
	};
	sandbox.IntersectionObserver.prototype.observe = function( el ) { this.watched.push( el ) };
	sandbox.IntersectionObserver.prototype.unobserve = function( el ) {
		this.watched = this.watched.filter( function( one ) { return one !== el } );
		this.unwatched.push( el );
	};
	sandbox.IntersectionObserver.prototype.disconnect = function() { this.watched = []; this.ended++ };

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

	// What the first drawing fitted - the stage takes its room as the screen is drawn - is told apart from what a test then does
	const drawn = { fits : fitted.splice( 0 ).length };

	return {
		design 	: design,
		requests: requests,
		fitted	: fitted,
		drawn		: drawn,
		timers	: timers,
		heard		: heard,
		view		: view,
		/** The delay and the function of the timers that are set and not cleared, the one that waits for the window to stop first */
		waiting	: function( delay ) {
			return timers.filter( function( timer ) { return timer !== null && timer[1] === delay } );
		},
		confirms: confirms,
		stage		: stage,
		/** The fonts of the document: the faces that were made, those in its set, the observers, and whether a load fails */
		typeset	: typeset,
		/** The row comes into view: the observer is told so, the way the browser tells it */
		see			: function( row ) {
			const observer = typeset.observers[typeset.observers.length - 1];
			observer.callback( [ { isIntersecting : true, target : row } ] );
		},
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
		/** The action bar, and what the panel says in it: the line, and the chip for the state of the files */
		bar			: function() { return byClass( form, 'nino-admin-actionbar' )[0] || null },
		line		: function() { return byId('design-msg') },
		chip		: function() { return byId('design-state-word') },
		/** What the knob rows of the open tab say, as [ label, word of the step that is lit ] - a row that is greyed out says nothing */
		lit			: function() {
			return byClass( form, 'design-knob-row' ).filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) === false } ).map( function( row ) {
				return [ rowName( row ), byTag( row, 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).map( function( button ) { return button.textContent } ).join() ];
			} );
		},
		/** One knob row of the open tab, by the name it carries */
		knobRow : function( label ) {
			return byClass( form, 'design-knob-row' ).filter( function( row ) {
				return rowName( row ) === label;
			} )[0] || null;
		},
		/** The buttons of a strip - a knob row, or a row of Global - by the name it carries */
		steps : function( label ) {
			const row = this.knobRow( label );
			return row === null ? [] : byTag( row, 'button' );
		},
		/** Move a knob, or a strip of Global, the way a hand does: press the button whose value it is */
		move : function( label, value ) {
			this.steps( label ).filter( function( button ) {
				return button.dataset.value === String( value );
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** What a strip says it stands at: the pressed state of its buttons, in order */
		pressed : function( label ) {
			return this.steps( label ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join();
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

	if( screen.byId('design-faces-installed') !== null )
		return 'typefaces';

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

check( 'the stand-in reads the panel\'s words out of text/en_US.php', Object.keys( TEXT ).length > 150 && TEXT['/_admin/design/tab/global'] === 'Global'
	&& TEXT['/_admin/design/hint/unanswered'].indexOf('%s') !== -1 );


// --- The pane's head -------------------------------------------------------------
//
// The panel's name is the head's - the row the shell renders over every pane -
// so the screen under it draws no heading of its own. The strip of the seven tabs
// stands in that row beside the name, where the Features panel's does: it
// switches the column of controls and nothing else, and the frame beside the
// column stays on every tab

console.log( 'The head' );

const headed = panel();
const strip = headed.headStrips()[0] || null;
const tabs = strip === null ? [] : byTag( strip, 'button' );

check( 'the strip has seven tabs - Global, Fonts, Typefaces, Content, Buttons, Components and Colours, in that order: the loader stands after the Fonts it lists its families in',
	headed.design.TABS.join() === TABS.join() && tabs.map( function( tab ) { return tab.textContent } ).join() === 'Global,Fonts,Typefaces,Content,Buttons,Components,Colours' );
check( '...a tablist of tabs, the first of them selected and the others not',
	strip !== null && strip.getAttribute('role') === 'tablist' && tabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' } )
	&& tabs.map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false,false,false,false,false,false' );
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
	&& byTag( shownStrip, 'button' )[6].getAttribute('aria-selected') === 'true' && byTag( shownStrip, 'button' )[0].getAttribute('aria-selected') === 'false'
	&& tabOf( headed ) === 'colours' && headed.byId('design-frame') !== null );

/*	The keyboard is the reason the strip is not drawn again on a switch. An
	arrow key moves the focus to the next tab and opens it; a strip drawn anew
	with the column took that tab out of the document, and the focus fell onto
	the page, so the next arrow key had nowhere to start from	*/
const keyed = panel();
const keyTabs = byTag( keyed.headStrips()[0] || element('div'), 'button' );

focused = null;
if( keyTabs.length === 7 )
	press( keyTabs[0], 'ArrowRight' );

check( 'an arrow key on the strip opens the next tab and the focus stays on the tab it moved to, in the head',
	keyTabs.length === 7 && focused === keyTabs[1] && keyTabs[1].parent === keyed.headStrips()[0]
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'fonts' );

if( keyTabs.length === 7 )
	press( keyTabs[1], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === 7 && focused === keyTabs[0] && keyTabs[0].parent === keyed.headStrips()[0]
	&& keyTabs[0].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'global' );

if( keyTabs.length === 7 )
	press( keyTabs[0], 'ArrowLeft' );

check( '...and from the first tab the arrow key wraps round to the last', keyTabs.length === 7 && focused === keyTabs[6] && tabOf( keyed ) === 'colours' );

/*	The script drawn outside its pane: the strip has nowhere else to go, so it
	opens the column the way it did - and the heading stays gone there as well	*/
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
// the frame in the preview with nothing under it, the action bar under both - and
// what the panel says in it: the line of what was done, the chip for the state of the files

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
check( 'the frame stands in the preview and nothing stands under it: no summary - not the list of what the tab chose, not a row for the state of the files - anywhere on the screen',
	column.byId('design-frame') !== null && column.byId('design-summary') === null && byClass( column.form, 'design-summary' ).length === 0
	&& byClass( column.form, 'design-summary-row' ).length === 0
	&& byClass( previewBox, 'design-preview-sticky' )[0].children.length === 2 && hasClass( byClass( previewBox, 'design-preview-sticky' )[0].children[1], 'design-stage' ) );
check( '...the frame a sandbox that lets the page run and be reached by the panel, and no more',
	column.byId('design-frame').getAttribute('sandbox') === 'allow-scripts allow-same-origin' );

/*	What the panel says stands in the action bar, before the buttons: the line - a paragraph the
	workbench's bar takes for its status line, a polite live region - and beside it a chip with the
	state of the files in one word, which is the same under every tab: the file answers to the
	selection, the draft is not applied, a file is not Design's, or it was never applied	*/
const bar = column.bar();

check( 'what the panel says stands in the action bar, before the buttons: the line first - #design-msg, a paragraph and a polite live region, empty to begin with - and the chip beside it, #design-state-word',
	bar !== null && bar.children[0] === column.line() && bar.children[1] === column.chip()
	&& column.line().tagName === 'P' && column.line().getAttribute('aria-live') === 'polite' && column.line().textContent === ''
	&& column.chip().tagName === 'SPAN' && hasClass( column.chip(), 'design-state-word' )
	&& bar.children.slice( 2 ).every( function( child ) { return child.tagName === 'BUTTON' } ) && bar.children.map( function( child ) { return child.id } ).join() === 'design-msg,design-state-word,design-reset,design-save,design-apply' );
check( '...and nothing stands under the bar: the line that told what the two buttons are is gone, and so is the line of what was done that stood after it - the bar is the last thing of the screen',
	column.byId('design-actions-hint') === null && column.form.children.length === 2 && column.form.children[1] === bar
	&& column.form.children.indexOf( column.line() ) === -1 && descendants( column.form ).filter( function( el ) { return el.id === 'design-msg' } ).length === 1 );

const stateWord = function( over ) { const screen = panel( over ); return [ screen.chip().textContent, screen.chip().dataset.state ] };
const foreignFiles = [ { target : '/assets/theme.css', exists : true, state : 'foreign' }, { target : '/templates/frame-header.tpl', exists : true, state : 'ours' }, { target : '/templates/frame-footer.tpl', exists : true, state : 'ours' } ];

check( 'the chip says the state of the files in one word: up to date while the file answers to the selection',
	column.chip().textContent === 'Up to date' && column.chip().dataset.state === 'current' && column.design._stateKey() === 'current' );
check( '...saved and not applied once the file does not, a file Design did not write named as such, and a file never applied as that',
	JSON.stringify( stateWord( { current : false } ) ) === JSON.stringify( [ 'Saved, not applied', 'drifted' ] )
	&& JSON.stringify( stateWord( { files : foreignFiles } ) ) === JSON.stringify( [ 'Not written by Design', 'foreign' ] )
	&& JSON.stringify( stateWord( { exists : false, compiled : '', current : false } ) ) === JSON.stringify( [ 'Never applied', 'missing' ] ) );
check( '...and its title says what the word is about, the applied file - the label the last row of the summary had',
	column.chip().title === TEXT['/_admin/design/label/state']+ ': Up to date' && column.chip().title === 'Applied file: Up to date' );

/*	The line is one line: cut where the bar has no more room for it, so the whole of it is its title	*/
const saying = panel();
saying.design._say( saying.line(), 'It did not work', 'nino-admin-error' );

check( 'what is said in the line is its text and its title - the whole of it, since the bar cuts a line that is longer than its room - and a failure carries the class the workbench colours red',
	saying.line().textContent === 'It did not work' && saying.line().title === 'It did not work' && saying.line().className === 'nino-admin-error' );

saying.design._say( saying.line(), 'Done' );

check( '...said again, it is the new words with the old class gone, and an empty line has no title',
	saying.line().textContent === 'Done' && saying.line().title === 'Done' && saying.line().className === ''
	&& ( saying.design._say( saying.line(), '' ), saying.line().textContent === '' && saying.line().title === '' ) );
check( '...and there is nothing to say it to where the screen has no line',
	threw( function() { saying.design._say( null, 'nothing is listening' ) } ) === false );

/*	A tab changes nothing of them, and nor does a change on the screen: no file is touched by either	*/
const steady = panel();
const steadyLine = steady.line();
const steadyChip = steady.chip();
steady.design._say( steady.line(), 'Draft saved.' );
[ 'Fonts', 'Content', 'Colours', 'Global' ].forEach( function( name ) { steady.tab( name ) } );
steady.tab('Content');
steady.move( 'Quiet variant, text size', 1 );

check( 'a tab that is opened and a knob that is moved change nothing of them: the same line with the same words, the same chip with its word',
	steady.line() === steadyLine && steady.chip() === steadyChip && steady.line().textContent === 'Draft saved.' && steady.line().title === 'Draft saved.'
	&& steady.chip().textContent === 'Up to date' && steady.chip().dataset.state === 'current' );

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

/*	The root size and the width of the page are strips of five, like the knobs of the tabs: a card of their own between
	the frames and the animation, with a name and the note under it and the five numbers -2 to +2 - no select, no field
	grid, and no word for a position, which a strip does not have	*/
const globalCards = global.body().children;
const strips = globalCards[1] || element('div');
const sizeStrip = 'Page, root size';
const widthStrip = 'Page, width';
const stripLabels = function( label ) { return global.steps( label ).map( function( button ) { return button.textContent } ).join() };

check( 'the root size and the width of the page are two strips in a card of their own, between the frames and the animation, the way the knob rows of a tab stand in theirs: Page, root size and Page, width',
	globalCards.length === 3 && hasClass( globalCards[0], 'design-form-section' ) && hasClass( strips, 'design-block' ) && hasClass( strips, 'nino-admin-card' )
	&& hasClass( globalCards[2], 'design-form-section' ) && byTag( globalCards[2], 'select' ).length === 3
	&& strips.children.length === 2 && strips.children.every( function( row ) { return hasClass( row, 'design-knob-row' ) } )
	&& strips.children.map( rowName ).join() === 'Page, root size,Page, width'
	&& global.byId('design-size') === null && global.byId('design-pagewidth') === null && byTag( strips, 'select' ).length === 0 && byClass( strips, 'design-form-grid' ).length === 0 );
check( '...each over its note, with the five buttons of a scale: the numbers from the middle, -2 to +2, written with the minus sign of arithmetic (U+2212) and not a hyphen - the title of a button is its label - and the middle one pressed to begin with',
	stripLabels( sizeStrip ) === SIGNED.join() && stripLabels( widthStrip ) === SIGNED.join() && stripLabels( sizeStrip ).indexOf('-') === -1
	&& global.steps( sizeStrip ).concat( global.steps( widthStrip ) ).every( function( button ) { return button.title === button.textContent && button.disabled === false } )
	&& global.pressed( sizeStrip ) === 'false,false,true,false,false' && global.pressed( widthStrip ) === 'false,false,true,false,false'
	&& global.steps( sizeStrip ).map( function( button ) { return button.dataset.value } ).join() === '-2,-1,0,1,2' );
check( '...a group of buttons as the knob rows are, named by what it sets, and the notes under the names say where the ends are: 70 % \u2194 130 % of the browser\'s own size, and 48 rem \u2194 to the edge',
	strips.children.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& byClass( global.knobRow( sizeStrip ), 'design-knob-steps' )[0].getAttribute('aria-label') === 'Page, root size'
	&& byTag( global.knobRow( sizeStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/size'] && TEXT['/_admin/design/hint/size'] === '70 % \u2194 130 % of the browser\'s own size, never a fixed number of pixels'
	&& byTag( global.knobRow( widthStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/pagewidth'] && TEXT['/_admin/design/hint/pagewidth'] === '48 rem \u2194 to the edge' );
check( '...the script keeps no list of the widths and no step to read a word off any more: PAGE_WIDTHS and _stepOf are gone, and a position of a scale reaches two to each side, a choice one',
	global.design.PAGE_WIDTHS === undefined && global.design._stepOf === undefined && JSON.stringify( global.design.RANGE ) === JSON.stringify( { scale : 2, choice : 1 } )
	&& JSON.stringify( global.design._positions('scale') ) === '[-2,-1,0,1,2]' && JSON.stringify( global.design._positions('choice') ) === '[-1,0,1]' );

global.move( sizeStrip, 2 );
global.move( widthStrip, 1 );

check( 'pressing a button stores the number, as a number: +2 for the size and +1 for the width, and the button is lit',
	global.design._edit.size === 2 && global.design._edit.width === 1 && typeof global.design._edit.size === 'number' && typeof global.design._edit.width === 'number'
	&& global.pressed( sizeStrip ) === 'false,false,false,false,true' && global.pressed( widthStrip ) === 'false,false,false,true,false' );
check( '...each of the five can be pressed, for the size and for the width, and each is what the model keeps as it was pressed - -2 stores -2, +2 stores 2',
	[ -2, -1, 0, 1, 2 ].every( function( position ) {
		global.move( sizeStrip, position );
		global.move( widthStrip, position );
		return global.design._edit.size === position && global.design._edit.width === position
			&& global.pressed( sizeStrip ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join()
			&& global.pressed( widthStrip ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join();
	} ) );
check( '...and a strip of Global is a change like any other: it marks the selection as not stored, and is what a preview carries',
	global.byId('design-reset').hidden === false && ( global.design._previewNow(), global.requests[global.requests.length - 1].payload.size === 2 && global.requests[global.requests.length - 1].payload.width === 2 ) );

global.move( sizeStrip, 2 );
global.move( widthStrip, 1 );

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
global.tab('Fonts');
global.tab('Global');

check( 'the tab is drawn again from what is chosen, and its controls show every choice of it - the two frames, the size, the width and the animation - with nothing under the frame to list them',
	[ 'header', 'footer' ].map( function( name ) { return shown( global.byId('design-set-'+ name) ) } ).join() === 'v2,v1'
	&& global.pressed( sizeStrip ) === 'false,false,false,false,true' && global.pressed( widthStrip ) === 'false,false,false,true,false' && global.design._edit.size === 2 && global.design._edit.width === 1
	&& shown( global.byId('design-motion-effect') ) === 'blur' && shown( global.byId('design-motion-strength') ) === 'hard' && shown( global.byId('design-motion-speed') ) === 'slow'
	&& global.byId('design-summary') === null && byClass( global.form, 'design-summary-row' ).length === 0 );

choose( global.byId('design-motion-effect'), '' );

check( 'no effect again is Nino\'s default again, and the other two are switched off with it',
	global.byId('design-motion-strength').disabled === true && global.byId('design-motion-speed').disabled === true
	&& JSON.stringify( global.design._edit.motion ) === JSON.stringify( { effect : '', strength : 'hard', speed : 'slow' } ) );

console.log('');


// --- The knob tabs ----------------------------------------------------------------
//
// A tab for each of the three templates that have knobs: the select the template
// is chosen in at the top of the card, and one strip for every knob the tab has
// under it - five numbers, -2 to +2, the 0 the template as it is, or for a choice the
// three words that knob has for them - and with the icon of what it sets. A knob
// the chosen template does not answer to stands where it always does, greyed out,
// with the line that says whose decision that is

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
check( '...each with a note beside its name, and a strip of five numbers: -2 to +2, written with the minus of arithmetic - the same on every knob of a scale, whatever it sets',
	contentRows.every( function( row, at ) {
		const knob = KNOBS.content[at];
		return ( byTag( row, 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/'+ knob+ '/note']
			&& byTag( row, 'button' ).map( function( button ) { return button.textContent } ).join() === SIGNED.join();
	} ) );
/*	A scale is five numbers and says nothing in words: the note under its name is where the ends are. A choice - the stripes of a table
	and the labels of a form - is the three words that knob has for its positions, and they begin with a capital letter, as the words of the
	Colours tab do. Read off the buttons that are drawn - their text and their title - and not off the table they come from, so that a
	word that goes back to lower case is seen. The kind of a knob is what design/list says of it, and the strip follows: the values on the
	buttons of a scale are -2 to +2, those of a choice -1 to +1	*/
const CHOICE_WORDS = { 'components/zebra' : 'Off,Rows,Rows with a line', 'components/labels' : 'In the row,Above,Floating' };
const wordProblems = [];
let wordRows = 0;
let scaleRows = 0;
let choiceRows = 0;
const worded = panel();

[ [ 'Content', 'content' ], [ 'Buttons', 'buttons' ], [ 'Components', 'components' ] ].forEach( function( entry ) {

	worded.tab( entry[0] );

	byClass( worded.form, 'design-knob-row' ).forEach( function( row ) {

		const buttons = byTag( row, 'button' );
		const drawn = buttons.map( function( button ) { return button.textContent } ).join();
		const key = entry[1]+ '/'+ row.dataset.knob;
		const choice = CHOICE_WORDS[key] !== undefined;

		wordRows++;

		if( choice === true )
			choiceRows++;
		else
			scaleRows++;

		if( drawn !== ( choice === true ? CHOICE_WORDS[key] : SIGNED.join() ) || buttons.map( function( button ) { return button.title } ).join() !== drawn
			|| ( choice === true && buttons.some( function( button ) { return button.textContent.charAt(0) !== button.textContent.charAt(0).toUpperCase() } ) )
			|| buttons.map( function( button ) { return button.dataset.value } ).join() !== ( choice === true ? '-1,0,1' : '-2,-1,0,1,2' )
			|| worded.design._data.tabs[entry[1]][row.dataset.knob] !== ( choice === true ? 'choice' : 'scale' ) )
			wordProblems.push( key+ ': '+ drawn );
	} );
} );

check( 'a scale is five numbers and a choice the three words of the knob, each capitalised - seventeen scales and two choices, nineteen rows - with the title of each button its label, and the values on them -2 to +2 and -1 to +1'
	+ ( wordProblems.length === 0 ? '' : ' - not so for '+ wordProblems.join(' | ') ), wordRows === 19 && scaleRows === 17 && choiceRows === 2 && wordProblems.length === 0 );
check( '...the middle word of a choice is its own, as it was: Rows between Off and Rows with a line, Above between In the row and Floating - and the two stand at it to begin with',
	CHOICE_WORDS['components/zebra'].split(',')[1] === 'Rows' && CHOICE_WORDS['components/labels'].split(',')[1] === 'Above'
	&& ( function() { worded.tab('Components'); return worded.pressed('Table, stripes') === 'false,true,false' && worded.pressed('Field, labels') === 'false,true,false' } )() );
check( '...a group of buttons, not a tablist - the one it stands at is pressed and the others are not: the middle for a knob nobody moved, the +1 the shadow stands at',
	contentRows.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& rows.pressed('Title (Hero), text size') === 'false,false,true,false,false'
	&& rows.pressed('Card, shadow') === 'false,false,false,true,false' );
check( 'there is no heading and no hint over the rows any more: the select says which template these knobs are the knobs of',
	byClass( rows.byId('design-knobs'), 'design-section-label' ).length === 0 && byTag( rows.byId('design-knobs'), 'strong' ).length === 0
	&& byTag( rows.byId('design-knobs'), 'small' ).filter( function( small ) { return small.parent === rows.byId('design-knobs').children[0] } ).length === 1 );
check( 'a template that answers to every knob greys none of them out',
	byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& byTag( rows.byId('design-knobs'), 'button' ).every( function( button ) { return button.disabled === false } ) );

rows.move( 'Title (Hero), text size', 1 );

check( 'moving a knob is the selection on screen: the position is a number, the button it stands at is lit',
	rows.design._edit.knobs.content['hero-title'] === 1 && typeof rows.design._edit.knobs.content['hero-title'] === 'number'
	&& rows.pressed('Title (Hero), text size') === 'false,false,false,true,false'
	&& rows.byId('design-reset').hidden === false );

rows.move( 'Title (Hero), text size', -2 );
rows.move( 'Subtitle (Hero), text size', 2 );

check( '...to the ends of the scale it is -2 and +2: each stores the number it is labelled with, and each knob keeps its own position',
	rows.design._edit.knobs.content['hero-title'] === -2 && rows.design._edit.knobs.content['hero-text'] === 2 && rows.design._edit.knobs.content.quiet === 0
	&& rows.pressed('Title (Hero), text size') === 'true,false,false,false,false' && rows.pressed('Subtitle (Hero), text size') === 'false,false,false,false,true' );
/** What the rows of a knob tab say, from the positions its knobs stand at: the label of each and the label of the button lit - the number of a scale, the word of a choice */
function knobWords( tab, positions ) {
	return KNOBS[tab].filter( function( knob ) { return positions[knob] !== undefined } ).map( function( knob ) {
		return [ TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/label'], KINDS[tab][knob] === 'scale' ? SIGNED[positions[knob] + 2] : TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/'+ [ 'less', 'default', 'more' ][positions[knob] + 1]] ];
	} );
}

check( 'the rows say, for every knob the template answers to, the number of the position it stands at - read off the button that is lit, with nothing under the frame to say it again',
	JSON.stringify( rows.lit() ) === JSON.stringify( knobWords( 'content', { 'hero-title' : -2, 'hero-text' : 2, quiet : 0, loud : 0, 'section-space' : 0, 'card-space' : 0, corners : 0, border : 0, shadow : 1 } ) )
	&& rows.byId('design-summary') === null );
check( '...in numbers: \u22122 for the title of the hero, +2 for its subtitle, 0 for the knobs nobody moved and +1 for the shadow of a card',
	rows.lit().map( function( row ) { return row.join(': ') } ).join(' | ')
		=== 'Title (Hero), text size: \u22122 | Subtitle (Hero), text size: +2 | Quiet variant, text size: 0 | Loud variant, text size: 0 | Section, space: 0 | Card, space: 0 | Card, corners: 0 | Card, line: 0 | Card, shadow: +1' );

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
	&& rows.design._edit.knobs.content['hero-title'] === -2 && rows.design._edit.knobs.content['hero-text'] === 2 );
check( '...the rows of the tab are drawn again - nine new ones for the nine knobs - and the select is a new one on the template that was chosen, which has the focus afterwards',
	oldRows.length === 9 && oldRows.every( function( row ) { return descendants( rows.pane ).indexOf( row ) === -1 } )
	&& byClass( rows.form, 'design-knob-row' ).length === 9 && descendants( rows.pane ).indexOf( oldSelect ) === -1
	&& rows.byId('design-set-content') !== null && rows.byId('design-set-content') !== oldSelect && shown( rows.byId('design-set-content') ) === 'v2'
	&& focused === rows.byId('design-set-content') && tabOf( rows ) === 'content' );
check( '...and the line under the select describes the template that was chosen, and the select stands on it',
	byTag( rows.byId('design-set-content').parent, 'small' )[0].textContent === 'Large type, no shadow' && shown( rows.byId('design-set-content') ) === 'v2' );

const poster = byClass( rows.form, 'design-knob-row' );
const shadow = rows.knobRow('Card, shadow');

check( 'on the tab the knob the template does not answer to is still a row, greyed out - not hidden, so the tab is the same screen for every template',
	poster.length === 9 && shadow !== null && hasClass( shadow, 'design-knob-row--off' )
	&& poster.filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) } ).length === 1 );
check( '...its five buttons switched off and none of them pressed',
	byTag( shadow, 'button' ).length === 5 && byTag( shadow, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& byTag( rows.knobRow('Title (Hero), text size'), 'button' ).every( function( button ) { return button.disabled === false } ) );
check( '...and the line under its name says whose decision that is, in place of its note',
	( byTag( shadow, 'small' )[0] || {} ).textContent === 'This template does not answer to “Card, shadow”.'
	&& ( byTag( rows.knobRow('Title (Hero), text size'), 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/hero-title/note']
	&& shown( rows.byId('design-set-content') ) === 'v2' );
check( 'a row that is greyed out says no position, since it compiles nothing: eight of the nine knobs have a button that is lit, and the shadow is not one of them',
	rows.lit().length === 8 && rows.lit().every( function( row ) { return row[0] !== 'Card, shadow' } ) );

choose( rows.byId('design-set-content'), 'v1' );

check( 'choosing the template that has it again takes the grey away, with the knob in the middle',
	byClass( rows.form, 'design-knob-row--off' ).length === 0 && rows.design._edit.knobs.content.shadow === 0
	&& rows.pressed('Card, shadow') === 'false,false,true,false,false' );

// The other two tabs, and their own templates
rows.tab('Buttons');
check( 'the Buttons tab has its four knobs, and Square leaves the corners to the template - which the stored position cannot have been',
	tabOf( rows ) === 'buttons' && byClass( rows.form, 'design-knob-row' ).length === 4 && byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& rows.design._edit.knobs.buttons.corners === -1 );
check( '...they are the size of the button, how far the small and the big one stand from it, the space in it and its corners, in that order - and the small and the big button are no knobs of their own any more: no row, no key in the answer or in the edit, no word and no icon of theirs',
	byClass( rows.form, 'design-knob-row' ).map( function( row ) { return row.dataset.knob } ).join() === 'size,spread,space,corners'
	&& Object.keys( rows.design._data.tabs.buttons ).join() === 'size,spread,space,corners'
	&& Object.keys( rows.design._edit.knobs.buttons ).sort().join() === 'corners,size,space,spread'
	&& Object.keys( TEXT ).filter( function( key ) { return /^\/_admin\/design\/knob\/buttons\/(small|big)\//.test( key ) } ).length === 0
	&& rows.design.KNOB_ICONS['buttons/small'] === undefined && rows.design.KNOB_ICONS['buttons/big'] === undefined );

choose( rows.byId('design-set-buttons'), 'v3' );

check( '...it is back in the middle, and the row is grey',
	rows.design._edit.knobs.buttons.corners === 0 && byClass( rows.form, 'design-knob-row--off' ).length === 1
	&& rows.knobRow('Button, corners') !== null && hasClass( rows.knobRow('Button, corners'), 'design-knob-row--off' ) );

rows.tab('Components');
choose( rows.byId('design-set-components'), 'v4' );

check( 'the Components tab has six rows, two of them grey for Filled: the lines and where the labels stand',
	tabOf( rows ) === 'components' && byClass( rows.form, 'design-knob-row' ).length === 6
	&& byClass( rows.form, 'design-knob-row--off' ).map( function( row ) { return row.dataset.knob } ).join() === 'border,labels'
	&& JSON.stringify( rows.lit().map( function( row ) { return row[0] } ) ) === JSON.stringify( [ 'scale', 'space', 'corners', 'zebra' ].map( function( knob ) { return TEXT['/_admin/design/knob/components/'+ knob+ '/label'] } ) ) );

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
check( 'a size is typed and not picked: a number field in each row, stepping by a tenth, with the limits of the role',
	fontRows.every( function( row ) {
		const input = size( row.dataset.role );
		return input !== null && input.tagName === 'INPUT' && input.type === 'number' && input.getAttribute('step') === '.1' && input.getAttribute('inputmode') === 'decimal'
			&& input.dataset.role === row.dataset.role && input.dataset.key === 'size';
	} )
	&& fonts.design.TABS.length === 7
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).getAttribute('min')+ '-'+ size( role ).getAttribute('max') } ).join() === '1-4,0.8-2.5,0.8-1.5,0.6-1.5,0.6-1.3'
	&& byTag( table, 'select' ).filter( function( select ) { return select.dataset.key === 'size' } ).length === 0 );
check( '...and its value is the size that is in force: the one Nino.css gives the role while none is chosen - 1.6, 1.125, 1, 1 and 0.875 - as the value and not as a placeholder, so that the spinner has a number to count from; the model keeps the empty size it had',
	[ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).value } ).join() === '1.6,1.125,1,1,0.875'
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].every( function( role ) { return size( role ).getAttribute('placeholder') === null && fonts.design._edit.fonts[role].size === '' } ) );
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
fonts.tab('Colours');
fonts.tab('Fonts');

check( 'the table drawn again shows each role as it was set, in words: the family, the weight, the case where it is upper, and the size - the one that was typed, else the one Nino.css gives the role',
	[ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) {
		const upperCase = fonts.byId('design-font-'+ role+ '-uppercase');
		return [ wordOf( family( role ) ), wordOf( weight( role ) ), upperCase === null ? null : upperCase.checked, size( role ).value ].join(', ');
	} ).join(' | ')
		=== 'System serif, Light, true, 2.4 | System (default), Normal, false, 1.125 | Source Sans 3, Normal, false, 1 | Source Sans 3, Bold, false, 1 | System monospace, Normal, , 0.875'
	&& fonts.byId('design-summary') === null );

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
check( '...a number is kept to three decimals and written as short as it can be: 1.1250 is 1.125, 1.23456 is 1.235',
	fonts.design._edit.fonts.text.size === '1.125' && size('text').value === '1.125' && fonts.design._edit.fonts.button.size === '1.235' && size('button').value === '1.235' );

type( size('title'), '2.0' );
type( size('subtitle'), '' );
type( size('text'), 'abc' );

check( '...2.0 is 2; empty is the default again, and a text that is no number is the default too - the model keeps the empty size, and the field shows the size that is in force: 1.125 for the subtitle, 1 for the text',
	fonts.design._edit.fonts.title.size === '2' && size('title').value === '2' && fonts.design._edit.fonts.subtitle.size === '' && size('subtitle').value === '1.125'
	&& fonts.design._edit.fonts.text.size === '' && size('text').value === '1' );

/*	The size in force is the field's value, so that the spinner starts from it. A size typed that comes
	to the one Nino.css gives the role is no choice - the model keeps the empty size, as it did, and the role goes
	on following its default - and any other is the number	*/
const forced = panel();
forced.tab('Fonts');

const forcedSize = function( role ) { return forced.byId('design-font-'+ role+ '-size') };

/** The spinner of a number field in miniature: from the number the field has to the next one of the grid its step and its least make, from the least where there is none */
function spin( input ) {
	const step = Number( input.getAttribute('step') );
	const least = Number( input.getAttribute('min') );
	const was = input.value === '' ? NaN : Number( input.value );
	input.value = String( Math.round( ( isNaN( was ) ? least : least + ( Math.floor( ( was - least ) / step + 1e-9 ) + 1 ) * step ) * 1000 ) / 1000 );
	input.fire('change');
}

type( forcedSize('title'), '1.6' );
type( forcedSize('subtitle'), '1.1250' );
type( forcedSize('text'), '1.0004' );
type( forcedSize('code'), '.875' );

check( 'a size typed back to the one Nino.css gives the role is kept as the empty size - 1.6 for the title, 1.1250 for the subtitle, 1.0004 for the text, which comes to 1, and .875 for the code - and the field goes on showing the number',
	[ 'title', 'subtitle', 'text', 'code' ].every( function( role ) { return forced.design._edit.fonts[role].size === '' } )
	&& [ 'title', 'subtitle', 'text', 'code' ].map( function( role ) { return forcedSize( role ).value } ).join() === '1.6,1.125,1,0.875'
	&& forced.design._isDirty() === false );

type( forcedSize('title'), '1.7' );
type( forcedSize('button'), '1.001' );

check( '...and any other is the number: 1.7 for the title, and 1.001 for the button, which is not 1 - held to three decimals',
	forced.design._edit.fonts.title.size === '1.7' && forcedSize('title').value === '1.7' && forced.design._edit.fonts.button.size === '1.001' && forcedSize('button').value === '1.001'
	&& forced.design._isDirty() === true );

type( forcedSize('title'), '1.60' );

check( '...so a role set to a size and then back to its default is the model it was: nothing to save',
	forced.design._edit.fonts.title.size === '' && forcedSize('title').value === '1.6' );

const spun = panel();
spun.tab('Fonts');
const spunTitle = spun.byId('design-font-title-size');

spin( spunTitle );

check( 'the spinner counts from the size in force: from 1.6 it goes up to 1.7 - not from the least of the role, 1, which is where an empty field starts - and what it reached is chosen',
	spunTitle.value === '1.7' && spun.design._edit.fonts.title.size === '1.7' );

console.log('');


// --- Typefaces: what the project has, and what the repository offers -----------------------------
//
// Two cards. The first holds the families that are installed, each name set in its own face, with its
// weights, its version, its licence and the button that removes it; the second the list of those the
// repository offers, which is read when its button is pressed - searched, told apart by category, a page
// of 24 at a time. An install and a removal are made at once and are no part of the draft: the line of
// the bar says what they did, or why not

console.log( 'Typefaces' );

/** One family of the list the repository offers, as design/webfonts/list gives it */
function offerOf( slug, family, category, over ) {
	return Object.assign( {
		slug 			: slug,
		family 		: family,
		category 	: category,
		version 	: '1.0',
		license 	: 'OFL-1.1',
		source 		: 'https://fonts.test/'+ slug,
		weights 	: [ 400, 700 ],
		styles 		: [ 'normal' ],
		files 		: [ { weight : 400, style : 'normal', file : '400.woff2' }, { weight : 700, style : 'normal', file : '700.woff2' } ],
		archive 	: 'https://webfonts.test/'+ slug+ '-1.0.tar.gz',
		sha256 		: 'a'.repeat( 64 ),
		size 			: 1000,
		preview 	: 'https://webfonts.test/'+ slug+ '-1.0-preview.woff2',
		released 	: '2026-10-12',
	}, over || {} );
}

/** One family the project has installed, as the same answer gives it: the file its name is set in is the project's own */
function installedOf( slug, name, version, over ) {
	return Object.assign( {
		name 			: name,
		category 	: 'sans',
		version 	: version,
		license 	: 'OFL-1.1',
		source 		: 'https://fonts.test/'+ slug,
		weights 	: [ 400, 700 ],
		axis 			: [],
		face 			: '/public/assets/fonts/'+ slug+ '/400.woff2',
	}, over || {} );
}

/** The slugs of the families the feature brings itself, as the answer of the tab names them: the families of design/list have them, and the installed ones as well */
const BROUGHT = [ 'source-sans-3', 'league-spartan', 'system-sans', 'system-serif', 'system-mono' ];

/** The whole answer of design/webfonts/list, and - with a msg - of the install and the removal after it */
function shelfOf( over ) {
	return Object.assign( { families : [], fetched : 0, url : 'https://webfonts.test/webfonts.json', key : true, builtin : BROUGHT, installed : {}, broken : {}, uses : {}, refusal : '', refusalKind : '' }, over || {} );
}

/**
 *	The answer of design/list once families are installed, as the server gives it: they stand among the families after the
 *	five the feature brings, and in the options of the roles but the code - which is offered the monospace ones only
 *
 *	@param		{Object}	installed		slug => name
 *
 *	@return		{Object}
 */
function listingOf( installed ) {

	const base = listing();
	const families = Object.assign( {}, base.families );
	const options = {};

	Object.keys( base.options ).forEach( function( role ) { options[role] = base.options[role].slice() } );

	Object.keys( installed ).forEach( function( slug ) {

		families[slug] = { name : installed[slug], category : 'sans', system : false };

		Object.keys( options ).filter( function( role ) { return role !== 'code' } ).forEach( function( role ) { options[role].push( slug ) } );
	} );

	return listing( { families : families, options : options } );
}

/** When the list of these tests was loaded, and what the panel makes of it */
const LOADED_AT = 1791806400;
const LOADED_WORDS = 'loaded 2026-10-12 12:00 UTC';

const installedCard = function( screen ) { return screen.byId('design-faces-installed') };
const availableCard = function( screen ) { return screen.byId('design-faces-available') };
const faceRows = function( card ) { return byClass( card, 'design-face-row' ) };
const faceName = function( row ) { return byClass( row, 'design-face-name' )[0] };
const faceSlugs = function( card ) { return faceRows( card ).map( function( row ) { return row.dataset.slug } ).join() };
const noteWords = function( note ) { return note.children.length === 0 ? note.textContent : note.children.map( function( part ) { return part.textContent } ).join('') };
const faceNotes = function( row ) { return byClass( row, 'design-face-note' ).map( noteWords ) };
const hints = function( card ) { return byClass( card, 'nino-admin-hint' ).map( function( p ) { return p.textContent } ) };
const askedOf = function( screen ) { return screen.requests.map( function( request ) { return request.endpoint } ).join() };
const faceButton = function( row ) { return byTag( row, 'button' )[0] || null };
// What the button of a row says, and a word that no button says where there is none: a row that lost its button fails a check and does not end the run
const faceWord = function( row ) { return faceButton( row ) === null ? '(no button)' : faceButton( row ).textContent };
const faceRowOf = function( card, slug ) { return faceRows( card ).filter( function( row ) { return row.dataset.slug === slug } )[0] || null };

/** The buttons of the tab that do something to a family or to the list: install, update, remove and load */
const faceActions = function( screen ) {
	return byTag( screen.body(), 'button' ).filter( function( button ) {
		return hasClass( button, 'nino-admin-btn-primary' ) || hasClass( button, 'nino-admin-btn-danger' ) || button.id === 'design-faces-load';
	} );
};

const shelves = panel();
shelves.tab('Typefaces');

check( 'the Typefaces tab stands after Fonts and is two cards in the box under the strip: the families that are installed and those that are on offer, each a block with its title and nothing under it',
	shelves.design.TABS.indexOf('typefaces') === shelves.design.TABS.indexOf('fonts') + 1 && tabOf( shelves ) === 'typefaces' && shelves.design._tab === 'typefaces'
	&& shelves.body().children.length === 2 && shelves.body().children[0] === installedCard( shelves ) && shelves.body().children[1] === availableCard( shelves )
	&& [ installedCard( shelves ), availableCard( shelves ) ].every( function( card ) { return hasClass( card, 'nino-admin-card' ) && hasClass( card, 'design-block' ) && byTag( card, 'small' ).length === 0 } )
	&& byTag( installedCard( shelves ), 'strong' )[0].textContent === 'Installed' && byTag( availableCard( shelves ), 'strong' )[0].textContent === 'Available' );
check( 'opening the tab asks for the list the server kept and not for the repository: one request, design/webfonts/list with refresh off - nothing leaves the server on its own',
	shelves.requests.length === 1 && shelves.requests[0].endpoint === 'webfonts/list' && JSON.stringify( shelves.requests[0].payload ) === '{"refresh":false}' );
check( '...and until it is answered the first card is its title and the second its title and the button that loads the list - no search field, no row',
	installedCard( shelves ).children.length === 1 && availableCard( shelves ).children.length === 2
	&& shelves.byId('design-faces-load') !== null && shelves.byId('design-faces-load').textContent === 'Load available typefaces' && shelves.byId('design-faces-load').disabled === false
	&& shelves.byId('design-faces-search') === null && faceRows( shelves.body() ).length === 0 );

shelves.answer( 200, shelfOf() );

check( 'nothing installed and nothing loaded: the first card says how a typeface comes, the second that the list is fetched when the button is pressed and not before - and no time stands beside the button',
	JSON.stringify( hints( installedCard( shelves ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/installed/empty'] ] )
	&& JSON.stringify( hints( availableCard( shelves ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/unloaded'] ] )
	&& byClass( shelves.body(), 'design-faces-when' ).length === 0 && shelves.byId('design-faces-search') === null );

// ---- Without an address, and without a key

const switchedOff = panel( { webfonts : { url : false, key : false } } );
switchedOff.tab('Typefaces');
switchedOff.answer( 200, shelfOf( { url : '', key : false, installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) } } ) );

const noKey = panel( { webfonts : { url : true, key : false } } );
noKey.tab('Typefaces');
noKey.answer( 200, shelfOf( { key : false, installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) } } ) );

check( 'with no address the second card is one sentence - the repository is switched off - and has no button, and the families that are installed stay where they are',
	JSON.stringify( hints( availableCard( switchedOff ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/off'] ] ) && switchedOff.byId('design-faces-load') === null
	&& availableCard( switchedOff ).children.length === 2 && faceSlugs( installedCard( switchedOff ) ) === 'inter' );
check( 'with an address and no key it is one sentence as well - the list cannot be checked - and has no button either: nothing would be fetched that could not be believed',
	JSON.stringify( hints( availableCard( noKey ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/nokey'] ] ) && noKey.byId('design-faces-load') === null
	&& faceSlugs( installedCard( noKey ) ) === 'inter' );

// ---- The settings of the repository are another panel's: the tab follows what the server says now, not what design/list said when the panel was opened

const changed = panel();
changed.tab('Typefaces');
changed.answer( 200, shelfOf( { url : '', key : true } ) );

const changedBack = panel( { webfonts : { url : false, key : false } } );
changedBack.tab('Typefaces');
changedBack.answer( 200, shelfOf( { url : 'https://webfonts.test/webfonts.json', key : true } ) );

const keyLost = panel();
keyLost.tab('Typefaces');
keyLost.answer( 200, shelfOf( { key : false } ) );

check( 'the repository was switched off after the panel was opened: the second card says so as soon as the tab has its answer, and the button is gone',
	JSON.stringify( hints( availableCard( changed ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/off'] ] ) && changed.byId('design-faces-load') === null );
check( '...and the key that was lost says that, with no button either',
	JSON.stringify( hints( availableCard( keyLost ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/nokey'] ] ) && keyLost.byId('design-faces-load') === null );
check( '...and an address and a key put in after the panel was opened give the button, where design/list said there was neither',
	changedBack.byId('design-faces-load') !== null && hints( availableCard( changedBack ) ).length === 1 && hints( availableCard( changedBack ) )[0] === TEXT['/_admin/design/typefaces/available/unloaded'] );

// ---- The list is loaded: a refusal, a list that is empty, a request that fails

shelves.byId('design-faces-load').fire('click');

check( 'the button asks the repository: design/webfonts/list with refresh on, the button switched off while the request is on its way, and the line of the bar empty',
	shelves.requests.length === 1 && shelves.requests[0].endpoint === 'webfonts/list' && JSON.stringify( shelves.requests[0].payload ) === '{"refresh":true}'
	&& shelves.byId('design-faces-load').disabled === true && shelves.line().textContent === '' );

shelves.answer( 200, shelfOf( { refusal : 'the typeface list could not be fetched: timeout', refusalKind : 'unreachable' } ) );

check( 'a list that could not be loaded is one sentence where the list would be, in the colour of an error, and the same in the line of the bar - the sentence of what the refusal is about, in the words of the workbench - and the button works again',
	byClass( availableCard( shelves ), 'nino-admin-error' ).length === 1
	&& byClass( availableCard( shelves ), 'nino-admin-error' )[0].textContent === TEXT['/_admin/design/typefaces/available/unreachable']
	&& shelves.line().textContent === TEXT['/_admin/design/typefaces/available/unreachable'] && shelves.line().className === 'nino-admin-error'
	&& shelves.byId('design-faces-load').disabled === false && shelves.byId('design-faces-search') === null );
check( '...the reason the server gives is English and is on screen nowhere: it is the title of the sentence in the card, and in the bar it follows the sentence in the title, in brackets',
	byClass( availableCard( shelves ), 'nino-admin-error' )[0].title === 'the typeface list could not be fetched: timeout'
	&& shelves.line().title === TEXT['/_admin/design/typefaces/available/unreachable']+ ' (the typeface list could not be fetched: timeout)'
	&& descendants( shelves.pane ).every( function( el ) { return String( el.textContent ).indexOf('timeout') === -1 } ) );

// ---- What a refusal is about: a sentence of its own for each, the reason of the server behind it

/** kind => [ the reason the server gives, what else the answer says ] - the answer of an address that is none says so in `url`, of a key that is none in `key` */
const REFUSED = {
	off 				: [ 'the typeface repository is switched off', { url : '' } ],
	nokey 			: [ 'no public key for the repository - set it in the feature\'s settings or in Webfonts::PUBLIC_KEY', { key : false } ],
	unreachable	: [ 'the typeface list signature could not be fetched: http 404', {} ],
	signature 	: [ 'the typeface list signature does not verify', {} ],
	parse 			: [ 'typeface list entry 1: "license" must be one of OFL-1.1, Apache-2.0, UFL-1.0', {} ],
};

const refusalProblems = [];

Object.keys( REFUSED ).forEach( function( kind ) {

	const refused = panel();
	const sentence = TEXT['/_admin/design/typefaces/available/'+ kind];
	const reason = REFUSED[kind][0];

	refused.tab('Typefaces');
	refused.answer( 200, shelfOf() );
	refused.byId('design-faces-load').fire('click');
	refused.answer( 200, shelfOf( Object.assign( { refusal : reason, refusalKind : kind }, REFUSED[kind][1] ) ) );

	const card = byClass( availableCard( refused ), 'nino-admin-error' )[0] || null;

	if( sentence === undefined )
		refusalProblems.push( kind+ ' has no words' );
	else {
		if( refused.line().textContent !== sentence || refused.line().className !== 'nino-admin-error' )
			refusalProblems.push( kind+ ': the bar says "'+ refused.line().textContent+ '"' );
		if( refused.line().title !== sentence+ ' ('+ reason+ ')' )
			refusalProblems.push( kind+ ': the title of the bar' );
		// The address that is none and the key that is none have the sentence in the card as they always had: a note, not an error
		if( [ 'unreachable', 'signature', 'parse' ].indexOf( kind ) !== -1 && ( card === null || card.textContent !== sentence || card.title !== reason ) )
			refusalProblems.push( kind+ ': the card' );
		if( [ 'off', 'nokey' ].indexOf( kind ) !== -1 && hints( availableCard( refused ) ).join() !== sentence )
			refusalProblems.push( kind+ ': the note in the card' );
	}

	if( descendants( refused.pane ).some( function( el ) { return String( el.textContent ).indexOf( reason ) !== -1 } ) )
		refusalProblems.push( kind+ ': the reason is on screen' );
} );

check( 'each thing a refusal can be about has a sentence of its own, in the card and in the bar - the repository switched off, no key to check it with, not reached, a signature that does not hold, a list that is no list - and the English reason of the server on screen nowhere'
	+ ( refusalProblems.length === 0 ? '' : ' - '+ refusalProblems.join(' | ') ), refusalProblems.length === 0 && Object.keys( REFUSED ).length === 5 );

shelves.byId('design-faces-load').fire('click');
shelves.answer( 200, shelfOf( { fetched : LOADED_AT } ) );

check( 'a list that names no typeface says so, with the time it was loaded beside the button - and the address it came from as its title; the line of the bar says it was loaded, with how many there are',
	JSON.stringify( hints( availableCard( shelves ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/none'] ] )
	&& byClass( availableCard( shelves ), 'design-faces-when' ).map( function( when ) { return when.textContent } ).join() === LOADED_WORDS
	&& byClass( availableCard( shelves ), 'design-faces-when' )[0].title === 'https://webfonts.test/webfonts.json'
	&& shelves.line().textContent === 'The list of typefaces is loaded (0 available).' && shelves.line().className === '' && shelves.byId('design-faces-search') === null );

shelves.byId('design-faces-load').fire('click');
shelves.answer( 500, null );

check( 'a request that fails says so in the line of the bar, and the screen stays as it was',
	shelves.line().textContent === '(500) The list of typefaces could not be loaded.' && shelves.line().className === 'nino-admin-error'
	&& shelves.byId('design-faces-load').disabled === false && hints( availableCard( shelves ) ).length === 1 );

// ---- A list of thirty over two pages

const CATEGORIES_IN_TURN = [ 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ];
const thirty = [];

for( let at = 1; at <= 30; at++ ) {
	const number = ( at < 10 ? '0' : '' )+ at;
	thirty.push( offerOf( 'face-'+ number, 'Face '+ number, CATEGORIES_IN_TURN[( at - 1 ) % 6] ) );
}

shelves.byId('design-faces-load').fire('click');
shelves.answer( 200, shelfOf( { families : thirty, fetched : LOADED_AT } ) );

const results = function( screen ) { return ( screen || shelves ).byId('design-faces-results') };
const pager = function( screen ) { return byClass( results( screen ), 'design-faces-pager' )[0] || null };
const pageOf = function( screen ) { return pager( screen ) === null ? null : byClass( pager( screen ), 'design-faces-place' )[0].textContent };
const pagerButtons = function( screen ) { return pager( screen ) === null ? [] : byTag( pager( screen ), 'button' ) };

check( 'thirty families are a page of 24 and a page of 6: the first twenty-four rows, in the order of the list, and under them Previous - off -, "1 of 2" and Next',
	faceRows( results() ).length === 24 && faceSlugs( results() ) === thirty.slice( 0, 24 ).map( function( entry ) { return entry.slug } ).join()
	&& pageOf() === '1 of 2' && pagerButtons().map( function( button ) { return button.textContent+ ':'+ button.disabled } ).join() === 'Previous:true,Next:false'
	&& shelves.design.FACES_PER_PAGE === 24 );

pagerButtons()[1].fire('click');

check( '...Next shows the six that are left, and is off itself',
	faceSlugs( results() ) === 'face-25,face-26,face-27,face-28,face-29,face-30' && pageOf() === '2 of 2'
	&& pagerButtons().map( function( button ) { return button.disabled } ).join() === 'false,true' );

pagerButtons()[0].fire('click');

check( '...and Previous the page before',
	faceRows( results() ).length === 24 && faceRows( results() )[0].dataset.slug === 'face-01' && pageOf() === '1 of 2' );

check( 'a row is the icon the other rows of the panel have, the name, and under it the weights and the licence - a link to where the files came from, opened in a tab of its own that knows nothing of this one - and Install',
	( function() {
		const row = faceRowOf( results(), 'face-01' );
		const link = byTag( row, 'a' )[0];
		const label = byClass( row, 'design-face-label' )[0];
		return faceName( row ).textContent === 'Face 01' && faceNotes( row ).join() === '400, 700 · OFL-1.1'
			&& label.children[0].tagName === 'SVG' && label.children[0].dataset.icon === 'a-large-small' && hasClass( label, 'design-knob-label' )
			&& link.textContent === 'OFL-1.1' && link.href === 'https://fonts.test/face-01' && link.target === '_blank' && link.rel === 'noopener noreferrer'
			&& byTag( row, 'button' ).length === 1 && faceButton( row ).textContent === 'Install' && hasClass( faceButton( row ), 'nino-admin-btn-primary' ) && faceButton( row ).disabled === false
			&& faceButton( row ).getAttribute('aria-label') === 'Install - Face 01';
	} )() );

// ---- The search and the categories

const search = function() { return shelves.byId('design-faces-search') };
const chips = function() { return byTag( byClass( availableCard( shelves ), 'design-knob-row' )[0], 'button' ) };
const chip = function( label ) { return chips().filter( function( button ) { return button.textContent === label } )[0] };
const litChips = function() { return chips().map( function( button ) { return button.getAttribute('aria-pressed') } ).join() };

check( 'over the rows are a search field - named by its placeholder - and the categories as a strip of the kind the knobs have: All lit, then Sans, Serif, Display, Handwriting, Mono and Other',
	search() !== null && search().type === 'search' && search().placeholder === 'Search typefaces' && search().getAttribute('aria-label') === 'Search typefaces'
	&& byClass( availableCard( shelves ), 'design-knob-row' ).length === 1 && rowName( byClass( availableCard( shelves ), 'design-knob-row' )[0] ) === 'Category'
	&& chips().map( function( button ) { return button.textContent } ).join() === 'All,Sans,Serif,Display,Handwriting,Mono,Other'
	&& chips().map( function( button ) { return button.dataset.value } ).join() === 'all,sans,serif,display,handwriting,mono,other'
	&& litChips() === 'true,false,false,false,false,false,false' && shelves.design.FACE_CATEGORIES.join() === 'all,sans,serif,display,handwriting,mono,other' );

const field = search();
field.value = 'FACE 2';
field.fire('input');

check( 'a word typed in the search narrows the list by name, whatever its case, to the first page of what is left - and the field is the one that was typed in, so that it keeps the focus',
	search() === field && faceSlugs( results() ) === 'face-20,face-21,face-22,face-23,face-24,face-25,face-26,face-27,face-28,face-29' && pager() === null );

chip('Serif').fire('click');

check( '...a category narrows it again: the serif families of those, and its chip is the lit one',
	faceSlugs( results() ) === 'face-20,face-26' && litChips() === 'false,false,true,false,false,false,false' && search() === field );

field.value = 'nothing of this name';
field.fire('input');

check( '...and what nothing matches is a sentence, with no pager under it',
	JSON.stringify( hints( results() ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/nomatch'] ] ) && faceRows( results() ).length === 0 && pager() === null );

field.value = '';
field.fire('input');
chip('All').fire('click');

check( 'the search emptied and All pressed give the thirty back, from the first page',
	faceRows( results() ).length === 24 && pageOf() === '1 of 2' && litChips() === 'true,false,false,false,false,false,false' );

pagerButtons()[1].fire('click');
chip('Mono').fire('click');

check( 'a category chosen while the second page is open starts from the first page of what it lets through',
	faceSlugs( results() ) === 'face-05,face-11,face-17,face-23,face-29' && pager() === null );

chip('All').fire('click');
pagerButtons()[1].fire('click');
field.value = 'face';
field.fire('input');

check( '...and so does a word typed while it is open',
	faceRows( results() ).length === 24 && faceRows( results() )[0].dataset.slug === 'face-01' && pageOf() === '1 of 2' );

field.value = '';
field.fire('input');

// ---- What a row says of a family that is installed

const offering = panel();
offering.tab('Typefaces');
offering.answer( 200, shelfOf( {
	families 	: [
		offerOf( 'inter', 'Inter', 'sans', { version : '4.0' } ),
		offerOf( 'lora', 'Lora', 'serif', { version : '2.0' } ),
		offerOf( 'fresh', 'Fresh', 'display' ),
		offerOf( 'source-sans-3', 'Source Sans 3', 'sans' ),
		offerOf( 'axis', 'Axis', 'sans', { weights : [ '100 900' ] } ),
	],
	fetched 	: LOADED_AT,
	installed : { inter : installedOf( 'inter', 'Inter', '4.0' ), lora : installedOf( 'lora', 'Lora', '1.0', { category : 'serif' } ) },
} ) );

check( 'a family of the list that is installed in the version the list has says "installed" where the button was; one installed in another version offers to update to the version of the list; one that is not installed offers to install',
	( function() {
		const same = faceRowOf( availableCard( offering ), 'inter' );
		const state = byClass( same, 'design-face-state' )[0];
		const update = faceButton( faceRowOf( availableCard( offering ), 'lora' ) );
		const install = faceButton( faceRowOf( availableCard( offering ), 'fresh' ) );
		return state !== undefined && state.textContent === 'installed' && byTag( same, 'button' ).length === 0
			&& update.textContent === 'update to 2.0' && hasClass( update, 'nino-admin-btn-primary' ) && install.textContent === 'Install'
			&& update.getAttribute('aria-label') === 'update to 2.0 - Lora' && install.getAttribute('aria-label') === 'Install - Fresh';
	} )() );
check( '...a family the feature brings itself is installed in a way, and has no button; a weight axis is told as one',
	( function() {
		const own = faceRowOf( availableCard( offering ), 'source-sans-3' );
		return byClass( own, 'design-face-state' ).length === 1 && byTag( own, 'button' ).length === 0
			&& faceNotes( faceRowOf( availableCard( offering ), 'axis' ) ).join() === 'variable 100-900 · OFL-1.1';
	} )() );

// ---- The families that are installed

const owning = panel();
owning.tab('Typefaces');
owning.answer( 200, shelfOf( {
	installed : {
		inter 	: installedOf( 'inter', 'Inter', '4.0' ),
		lora 		: installedOf( 'lora', 'Lora', '1.0', { category : 'serif' } ),
		roboto 	: installedOf( 'roboto', 'Roboto Flex', '3.2', { weights : [ 100, 900 ], axis : [ 100, 900 ] } ),
	},
	uses 			: { inter : [ 'title', 'text' ], lora : [], roboto : [] },
	broken 		: { 'odd-one' : 'there is no font.json' },
	families 	: [ offerOf( 'inter', 'Inter', 'sans', { version : '4.0' } ) ],
	fetched 	: LOADED_AT,
} ) );

check( 'each installed family is a row, in the order the server gives them, and after them the directories that are none',
	faceSlugs( installedCard( owning ) ) === 'inter,lora,roboto,odd-one' && hints( installedCard( owning ) ).length === 0 );
check( '...with its name, and under it its weights, its version and its licence - "variable 100-900" where the family has an axis -, and the icon the other rows have',
	faceNotes( faceRowOf( installedCard( owning ), 'inter' ) )[0] === '400, 700 · 4.0 · OFL-1.1' && faceNotes( faceRowOf( installedCard( owning ), 'roboto' ) )[0] === 'variable 100-900 · 3.2 · OFL-1.1'
	&& faceName( faceRowOf( installedCard( owning ), 'roboto' ) ).textContent === 'Roboto Flex'
	&& faceRows( installedCard( owning ) ).slice( 0, 3 ).every( function( row ) { return byClass( row, 'design-face-label' )[0].children[0].dataset.icon === 'a-large-small' } ) );
check( 'a family a role of the saved setup uses cannot be removed: its button is off, its title names the roles and so does a second line under the name - in the words of the Fonts tab',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'inter' );
		const remove = faceButton( row );
		return remove.disabled === true && remove.title === 'used by Title, Text' && remove.textContent === 'Remove' && hasClass( remove, 'nino-admin-btn-danger' )
			&& remove.getAttribute('aria-label') === 'Remove - Inter'
			&& JSON.stringify( faceNotes( row ) ) === JSON.stringify( [ '400, 700 · 4.0 · OFL-1.1', 'used by Title, Text' ] );
	} )() );
check( '...and one that no role uses has the button, working, with no second line',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'lora' );
		return faceButton( row ).disabled === false && faceButton( row ).title === 'Remove' && faceNotes( row ).length === 1;
	} )() );

owning.design._edit.fonts.subtitle.family = 'lora';
owning.design._paintFaces();

check( 'a family chosen for a role in the selection being edited is as little to be removed as one the saved setup gives it, before anything is saved',
	faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).disabled === true && faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).title === 'used by Subtitle'
	&& faceNotes( faceRowOf( installedCard( owning ), 'lora' ) )[1] === 'used by Subtitle' && faceButton( faceRowOf( installedCard( owning ), 'roboto' ) ).disabled === false );

owning.design._edit.fonts.subtitle.family = 'inter';
owning.design._paintFaces();

check( '...and both give a family their roles together, in the order of the roles of the Fonts tab',
	faceButton( faceRowOf( installedCard( owning ), 'inter' ) ).title === 'used by Title, Subtitle, Text' && faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).disabled === false );

owning.design._edit.fonts.subtitle.family = 'source-sans-3';
owning.design._paintFaces();

check( 'a directory that is no family is listed greyed, under its own name, with the reason - and a Remove that works whatever the reason is',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'odd-one' );
		return hasClass( row, 'design-face-row--off' ) && faceName( row ).textContent === 'odd-one' && faceNotes( row ).join() === 'Not a typeface: there is no font.json'
			&& faceButton( row ).disabled === false && faceButton( row ).textContent === 'Remove' && hasClass( faceButton( row ), 'nino-admin-btn-danger' ) && faceButton( row ).getAttribute('aria-label') === 'Remove - odd-one'
			&& hasClass( faceRowOf( installedCard( owning ), 'lora' ), 'design-face-row--off' ) === false;
	} )() );

check( 'the tab asked for the list that was kept when it was opened, and for nothing more: no row, no search and no chip asks the server anything',
	askedOf( owning ) === '' && askedOf( offering ) === '' && askedOf( shelves ) === '' );

faceButton( faceRowOf( installedCard( owning ), 'odd-one' ) ).fire('click');

check( 'the Remove of a directory that is no family asks for that name, as it is: the server decides what it is',
	owning.requests.length === 1 && owning.requests[0].endpoint === 'webfonts/remove' && JSON.stringify( owning.requests[0].payload ) === '{"slug":"odd-one"}' );

// ---- An install, a refusal, a removal

const doing = panel();
doing.tab('Typefaces');

const listed = [ offerOf( 'inter', 'Inter', 'sans', { version : '4.0' } ), offerOf( 'lora', 'Lora', 'serif', { version : '2.0' } ), offerOf( 'fresh', 'Fresh', 'display' ) ];
const listedAnswer = function( over ) { return shelfOf( Object.assign( { families : listed, fetched : LOADED_AT }, over || {} ) ) };
const plain = { inter : installedOf( 'inter', 'Inter', '4.0' ), lora : installedOf( 'lora', 'Lora', '1.0', { category : 'serif' } ) };
const withFresh = Object.assign( {}, plain, { fresh : installedOf( 'fresh', 'Fresh', '1.0', { category : 'display' } ) } );

doing.answer( 200, listedAnswer( { installed : plain, uses : { inter : [ 'text' ], lora : [] } } ) );

const draftBefore = JSON.stringify( doing.design._edit );
const enabledBefore = faceActions( doing ).filter( function( button ) { return button.disabled === false } ).length;

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');

check( 'Install asks the server for that family and for nothing else: design/webfonts/install with the slug - and the buttons of the tab are off while it is on its way, the line of the bar empty',
	doing.requests.length === 1 && doing.requests[0].endpoint === 'webfonts/install' && JSON.stringify( doing.requests[0].payload ) === '{"slug":"fresh"}'
	&& enabledBefore === 4 && faceActions( doing ).length === 5 && faceActions( doing ).every( function( button ) { return button.disabled === true } ) && doing.line().textContent === '' );

doing.design._installFace('lora');
doing.design._removeFace('lora');

check( '...and a second action asked for while the first is on its way is not sent', doing.requests.length === 1 );

doing.answer( 409, { error : 'the archive does not match its checksum' } );

check( 'a refusal is told in the line of the bar with the reason, as an error, and the buttons work again; nothing is read again and nothing changed on the screen',
	doing.line().textContent === 'Not installed: the archive does not match its checksum' && doing.line().className === 'nino-admin-error'
	&& doing.requests.length === 0 && faceSlugs( installedCard( doing ) ) === 'inter,lora'
	&& faceActions( doing ).filter( function( button ) { return button.disabled === false } ).length === enabledBefore );

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 500, null );

check( 'a failure that has no words of its own is told the way the workbench tells one, after what failed - and no %s is left in it',
	doing.line().textContent.indexOf('Not installed: (500)') === 0 && doing.line().textContent.indexOf('%s') === -1 && doing.line().className === 'nino-admin-error' );

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 200, listedAnswer( { msg : 'Fresh 1.0 is installed.', installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

check( 'an install that went through is the new state of both cards, from the one answer: the family stands among the installed, the row of the list says "installed" - and the line of the bar says it, as the server wrote it',
	faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' && byClass( faceRowOf( availableCard( doing ), 'fresh' ), 'design-face-state' ).length === 1
	&& byTag( faceRowOf( availableCard( doing ), 'fresh' ), 'button' ).length === 0
	&& doing.line().textContent === 'Fresh 1.0 is installed.' && doing.line().className === '' );
check( '...then design/list is asked again, for the selects of the Fonts tab: and nothing else is sent, no save and no preview - the draft is the one it was',
	askedOf( doing ) === 'list' && JSON.stringify( doing.requests[0].payload ) === '{}' && JSON.stringify( doing.design._edit ) === draftBefore && doing.design._isDirty() === false );

doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora', fresh : 'Fresh' } ) );
doing.tab('Fonts');

check( '...and the family is offered in the select of the Fonts tab, under the name it has',
	offered( doing.byId('design-font-title-family') ).some( function( option ) { return option[0] === 'fresh' && option[1] === 'Fresh' } ) );

doing.tab('Typefaces');
doing.answer( 200, listedAnswer( { installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

faceButton( faceRowOf( availableCard( doing ), 'lora' ) ).fire('click');
doing.answer( 200, listedAnswer( {
	msg 			: 'Lora is updated to 2.0.',
	installed : Object.assign( {}, withFresh, { lora : installedOf( 'lora', 'Lora', '2.0', { category : 'serif' } ) } ),
	uses 			: { inter : [ 'text' ], lora : [], fresh : [] },
} ) );

check( 'an update is the same: the family that was there is the new version, and the row of the list says "installed" where the button said "update to 2.0"',
	doing.line().textContent === 'Lora is updated to 2.0.' && faceNotes( faceRowOf( installedCard( doing ), 'lora' ) )[0] === '400, 700 · 2.0 · OFL-1.1'
	&& byClass( faceRowOf( availableCard( doing ), 'lora' ), 'design-face-state' ).length === 1 && askedOf( doing ) === 'list' );

doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora', fresh : 'Fresh' } ) );
faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');

check( 'Remove asks for that family: design/webfonts/remove with the slug',
	doing.requests.length === 1 && doing.requests[0].endpoint === 'webfonts/remove' && JSON.stringify( doing.requests[0].payload ) === '{"slug":"fresh"}' );

doing.answer( 409, { error : 'the typeface "fresh" is used by title' } );

check( 'a removal the server refuses says why in the line of the bar, as an error, and the family stays',
	doing.line().textContent === 'Not removed: the typeface "fresh" is used by title' && doing.line().className === 'nino-admin-error' && faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' );

faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 409, { error : 'used by title, text', code : 'used', params : [ 'title', 'text' ] } );

check( 'a family a role still uses is refused with the roles in the words of the workbench, not in the server\'s',
	doing.line().textContent === 'Not removed: a role of the setup uses it - Title, Text' && doing.line().className === 'nino-admin-error' && faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' );

faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 200, listedAnswer( {
	msg 			: 'Fresh is removed.',
	installed : { inter : plain.inter, lora : installedOf( 'lora', 'Lora', '2.0', { category : 'serif' } ) },
	uses 			: { inter : [ 'text' ], lora : [] },
} ) );

check( 'a removal that went through takes the family out of the first card; the row of the list offers to install it again, right then - design/list still says what it said before, with the family among its families, as the server\'s does for what is installed; the line of the bar says it, and design/list is read again',
	faceSlugs( installedCard( doing ) ) === 'inter,lora' && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === 'Install'
	&& byClass( faceRowOf( availableCard( doing ), 'fresh' ), 'design-face-state' ).length === 0 && doing.design._data.families.fresh !== undefined
	&& doing.line().textContent === 'Fresh is removed.' && askedOf( doing ) === 'list' );

// The families the feature brings are named by the answer of the tab, and by nothing else: design/list has them, and has the installed ones too
const owned = panel();
owned.tab('Typefaces');
owned.answer( 200, shelfOf( { families : [ offerOf( 'source-sans-3', 'Source Sans 3', 'sans' ) ], fetched : LOADED_AT, builtin : [] } ) );

check( 'a row of the list for a family the feature brings says "installed" because the answer of the tab names it, and not because design/list has it among its families: with the answer naming none, the row offers to install',
	owned.design._data.families['source-sans-3'] !== undefined && byClass( faceRowOf( availableCard( owned ), 'source-sans-3' ), 'design-face-state' ).length === 0
	&& faceWord( faceRowOf( availableCard( owned ), 'source-sans-3' ) ) === 'Install'
	&& doing.design._faces.builtin.join() === BROUGHT.join() );

doing.answer( 500, null );

check( 'when design/list cannot be read again the screen stays as it is: the line of the bar keeps what the action said, and the row of the list still offers to install',
	doing.line().textContent === 'Fresh is removed.' && faceSlugs( installedCard( doing ) ) === 'inter,lora' && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === 'Install' );

const rowBefore = faceRowOf( availableCard( doing ), 'fresh' );

doing.design._relist();
doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora' } ) );

check( '...and when it is read again the open tab is drawn again, from what it knows, and the row of the list offers to install it still: the selects of the Fonts tab have lost the family and the tab has not been told twice',
	faceRowOf( availableCard( doing ), 'fresh' ) !== rowBefore && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === 'Install'
	&& doing.design._data.families.fresh === undefined && faceSlugs( installedCard( doing ) ) === 'inter,lora' && doing.line().textContent === 'Fresh is removed.' && askedOf( doing ) === '' );

// ---- Only the last answer is the state, and an action that is on its way answers for the tab

const racing = panel();
racing.tab('Typefaces');
racing.byId('design-faces-load').fire('click');

check( 'the list that was kept and the list loaded were asked for in turn',
	askedOf( racing ) === 'webfonts/list,webfonts/list' && racing.requests[0].payload.refresh === false && racing.requests[1].payload.refresh === true );

racing.requests[1].answer( 200, shelfOf( { families : listed, fetched : LOADED_AT } ) );
racing.requests[0].answer( 200, shelfOf() );

check( 'an answer that comes after a later one is not the state: the older request answering last does not take the list away',
	racing.design._faces.families.length === 3 && faceRows( availableCard( racing ) ).length === 3 );

const busy = panel();
busy.tab('Typefaces');
busy.answer( 200, listedAnswer( { installed : plain, uses : { inter : [ 'text' ], lora : [] } } ) );
faceButton( faceRowOf( availableCard( busy ), 'fresh' ) ).fire('click');
busy.tab('Fonts');
busy.tab('Typefaces');

check( 'a tab that is opened while an action is on its way does not ask for the list: the answer of the action is the state, and the buttons are off until it comes',
	askedOf( busy ) === 'webfonts/install' && faceActions( busy ).length === 5 && faceActions( busy ).every( function( button ) { return button.disabled === true } ) );

busy.answer( 200, listedAnswer( { msg : 'Fresh 1.0 is installed.', installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

check( '...and it draws the tab it finds',
	faceSlugs( installedCard( busy ) ) === 'inter,lora,fresh' && busy.line().textContent === 'Fresh 1.0 is installed.' && askedOf( busy ) === 'list' );

const leaving = panel();
leaving.tab('Typefaces');
leaving.tab('Global');

check( 'the answer of a request that outlived the tab is kept and draws nothing, and does not fail',
	threw( function() { leaving.answer( 200, shelfOf( { installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) }, uses : { inter : [] } } ) ) } ) === false
	&& leaving.design._faces !== null && leaving.byId('design-faces-installed') === null );

leaving.tab('Typefaces');

check( '...the next time the tab is opened it is drawn from what is known at once, and the server is asked again',
	faceSlugs( installedCard( leaving ) ) === 'inter' && askedOf( leaving ) === 'webfonts/list' );

// ---- The names are set in their own faces

const painted = panel();
painted.tab('Typefaces');

const paintedAnswer = shelfOf( {
	installed 	: { inter : installedOf( 'inter', 'Inter', '4.0' ), quote : installedOf( 'quote', 'Quote', '1.0', { face : '/public/assets/fonts/quote/a"b\\c.woff2' } ) },
	uses 				: { inter : [], quote : [] },
	families 		: thirty,
	fetched 		: LOADED_AT,
} );

painted.answer( 200, paintedAnswer );

check( 'the name of an installed family is set in its own face as the row is drawn - the project serves the file itself: one FontFace for the family, made from the url of its file, added to the fonts of the document and asked to load',
	painted.typeset.made.length === 2 && painted.typeset.made[0].family === 'design-installed-inter' && painted.typeset.made[0].source === 'url("/public/assets/fonts/inter/400.woff2")'
	&& painted.typeset.set.length === 2 && painted.typeset.made.every( function( face ) { return face.loads === 1 } )
	&& faceName( faceRowOf( installedCard( painted ), 'inter' ) ).style.fontFamily === '"design-installed-inter", system-ui, sans-serif' );
check( '...a url is written as a string: a quote or a backslash in it stays in the string',
	painted.typeset.made[1].source === 'url("/public/assets/fonts/quote/a\\"b\\\\c.woff2")' );

const observer = painted.typeset.observers[painted.typeset.observers.length - 1];

check( 'the names of the list are set in nothing yet: the rows of the page are watched, and a face is made for none of them until it is in view',
	painted.typeset.observers.length === 1 && observer.watched.length === 24 && observer.watched.every( function( row ) { return hasClass( row, 'design-face-row' ) } )
	&& painted.typeset.made.length === 2 && faceName( faceRowOf( availableCard( painted ), 'face-01' ) ).style.fontFamily === undefined );

painted.see( faceRowOf( availableCard( painted ), 'face-03' ) );

check( 'a row that comes into view sets its name in the preview its family publishes - a FontFace of its own, added to the fonts of the document, asked to load once - and is not watched any more',
	painted.typeset.made.length === 3 && painted.typeset.made[2].family === 'design-available-face-03' && painted.typeset.made[2].source === 'url("https://webfonts.test/face-03-1.0-preview.woff2")'
	&& painted.typeset.set.length === 3 && painted.typeset.made[2].loads === 1
	&& faceName( faceRowOf( availableCard( painted ), 'face-03' ) ).style.fontFamily === '"design-available-face-03", system-ui, sans-serif'
	&& observer.watched.length === 23 && observer.unwatched.length === 1 && observer.unwatched[0] === faceRowOf( availableCard( painted ), 'face-03' ) );

pagerButtons( painted )[1].fire('click');
pagerButtons( painted )[0].fire('click');

check( 'a page turned and turned back draws the rows again and watches them again - the rows of the page that was left are not watched any more',
	painted.typeset.observers.length === 1 && observer.watched.length === 24 && painted.typeset.made.length === 3
	&& faceName( faceRowOf( availableCard( painted ), 'face-03' ) ).style.fontFamily === undefined );

painted.see( faceRowOf( availableCard( painted ), 'face-03' ) );

check( '...and the row of the family that was seen before is set in the face the document has: none is made twice',
	painted.typeset.made.length === 3 && painted.typeset.set.length === 3 && painted.typeset.made[2].loads === 1
	&& faceName( faceRowOf( availableCard( painted ), 'face-03' ) ).style.fontFamily === '"design-available-face-03", system-ui, sans-serif' );

painted.typeset.fails = true;
painted.see( faceRowOf( availableCard( painted ), 'face-04' ) );
painted.typeset.fails = false;

check( 'a file that cannot be loaded leaves nothing undone: the load is given a hand to catch it with, and the name stays in the face of the page',
	painted.typeset.made.length === 4 && painted.typeset.made[3].loads === 1 && painted.typeset.made[3].caught === 1 && painted.typeset.made.every( function( face ) { return face.caught === face.loads } ) );

painted.tab('Global');

check( 'leaving the tab lets go of what it held: the faces are out of the fonts of the document, the observer has stopped watching and is let go - a face is a file the browser keeps',
	painted.typeset.set.length === 0 && observer.watched.length === 0 && observer.ended >= 1 && painted.design._facesObserver === null && Object.keys( painted.design._facesLoaded ).length === 0 );

painted.tab('Typefaces');

check( 'coming back to the tab draws it from what is known and sets the installed names again, with faces made again - and a new observer watches the rows for the visit',
	painted.typeset.made.length === 6 && painted.typeset.made[4].family === 'design-installed-inter' && painted.typeset.made[5].family === 'design-installed-quote'
	&& painted.typeset.set.length === 2 && painted.typeset.observers.length === 2 && painted.typeset.observers[1].watched.length === 24 );

painted.answer( 200, paintedAnswer );

check( 'the answer to the second visit draws the same rows again and makes no face twice; every load the tab asked of a face was given its catch',
	painted.typeset.made.length === 6 && painted.typeset.set.length === 2 && painted.typeset.made.every( function( face ) { return face.caught === face.loads } ) );

console.log('');


// ---- The screen drawn again over the open tab: Reset puts the stored selection back and draws everything, the tab included

const redrawn = panel();
redrawn.tab('Typefaces');
redrawn.answer( 200, shelfOf( { installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) }, uses : { inter : [] }, families : thirty, fetched : LOADED_AT } ) );
redrawn.design._edit.fonts.title.family = 'inter';

const redrawnNote = faceNotes( faceRowOf( installedCard( redrawn ), 'inter' ) ).length;

redrawn.design._revert();

check( 'Reset over the open Typefaces tab draws the screen again, the tab with it: the two cards stand from what is known, the list that was kept is asked for again, the selection is the stored one - and no face is made twice, one observer watches the rows',
	threw( function() { redrawn.design._revert() } ) === false && redrawn.design._tab === 'typefaces' && redrawn.design._isDirty() === false && redrawnNote === 1
	&& faceSlugs( installedCard( redrawn ) ) === 'inter' && faceRows( availableCard( redrawn ) ).length === 24 && askedOf( redrawn ) === 'webfonts/list,webfonts/list'
	&& redrawn.typeset.made.filter( function( face ) { return face.family === 'design-installed-inter' } ).length === 1
	&& redrawn.typeset.observers.length === 1 && redrawn.typeset.observers[0].watched.length === 24 );

// --- Colours: the palette and its mode ------------------------------------------------

console.log( 'Colours' );

const colours = panel();
colours.tab('Colours');

check( 'the Colours tab has the brand colour, the five knobs the rest of the palette is solved with - the second colour at the end of the first of them - and the mode',
	tabOf( colours ) === 'colours' && colours.byId('design-colour-primary').value === '#4faae8'
	&& byClass( colours.form, 'design-knob-row' ).map( rowName ).join() === 'Second colour,Temperature,Saturation,Contrast,Depth'
	&& colours.byId('design-colour-secondary') !== null && colours.byId('design-colour-mode') !== null );
check( '...four positions for Harmony and Temperature, in the words of the panel, and five for the others, numbered from the middle - and the one the knob starts on lit',
	[ 4, 4, 5, 5, 5 ].join() === byClass( colours.form, 'design-knob-row' ).map( function( row ) { return byTag( row, 'button' ).length } ).filter( function( count, at ) { return at < 5 } ).join()
	&& byTag( colours.knobRow('Temperature'), 'button' ).map( function( button ) { return button.textContent } ).join() === 'Neutral,Cool,Brand,Warm'
	&& colours.pressed('Temperature') === 'false,false,true,false' );
/*	A choice says its names and a scale its numbers: the three scales of the palette are the same five buttons the knobs of the other tabs
	have, the 0 the framework's own, which is the third position of five, and what they store is the position - 1 to 5 - and not the number
	on the button. The names of the two choices are what they were, each capitalised, and the words of the three scales are gone	*/
check( '...the words of the two choices are Monochrome, Analogous, Triadic, Complementary and Neutral, Cool, Brand, Warm; Saturation, Contrast and Depth say \u22122 \u22121 0 +1 +2, the 0 lit at the start, and press the positions 1 to 5',
	[ [ 'Second colour', 'Monochrome,Analogous,Triadic,Complementary' ], [ 'Temperature', 'Neutral,Cool,Brand,Warm' ] ].every( function( entry ) {
		return byTag( colours.knobRow( entry[0] ), 'button' ).map( function( button ) { return button.textContent } ).join() === entry[1];
	} )
	&& [ 'Saturation', 'Contrast', 'Depth' ].every( function( name ) {
		return colours.steps( name ).map( function( button ) { return button.textContent } ).join() === SIGNED.join() && colours.steps( name ).map( function( button ) { return button.title } ).join() === SIGNED.join()
			&& colours.steps( name ).map( function( button ) { return button.dataset.value } ).join() === '1,2,3,4,5' && colours.pressed( name ) === 'false,false,true,false,false'
			&& colours.lit().filter( function( row ) { return row[0] === name } )[0][1] === '0';
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

byTag( colours.knobRow('Saturation'), 'button' )[4].fire('click');
byTag( colours.knobRow('Contrast'), 'button' )[0].fire('click');

check( 'a position of a knob is a number, 1 to 5: +2 on a scale is the fifth position and \u22122 the first',
	colours.design._edit.colours.saturation === 5 && colours.design._edit.colours.contrast === 1 && typeof colours.design._edit.colours.saturation === 'number' );
check( 'the rows show the brand colour, the second colour - Monochrome while nobody chose one - every knob at the position it stands at, and the mode, with nothing under the frame to say it again',
	colours.byId('design-colour-primary').value === '#8b1d3f'
	&& JSON.stringify( colours.lit() ) === JSON.stringify( [ [ 'Second colour', 'Monochrome' ], [ 'Temperature', 'Brand' ], [ 'Saturation', '+2' ], [ 'Contrast', '\u22122' ], [ 'Depth', '0' ] ] )
	&& wordOf( colours.byId('design-colour-mode') ) === 'dark only' && colours.byId('design-summary') === null );

// A second colour somebody chose overrides the wheel; choosing a position on the wheel gives it up
const second = colours.byId('design-colour-secondary');
second.value = '#0f766e';
second.fire('change');

check( 'a second colour that was chosen is shown as chosen: the swatch has the hex, where the row lit the place on the wheel',
	colours.design._edit.colours.secondary === '#0f766e' && colours.byId('design-colour-secondary').value === '#0f766e'
	&& byClass( colours.form, 'design-knob-reset' ).length === 1
	&& byTag( colours.knobRow('Second colour'), 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).length === 0 );

byTag( colours.knobRow('Second colour'), 'button' )[2].fire('click');

check( '...and a position on the wheel gives it up again', colours.design._edit.colours.secondary === '' && colours.design._edit.colours.harmony === 3
	&& colours.lit()[0][1] === 'Triadic' && byClass( colours.form, 'design-knob-reset' ).length === 0 );

const unsafe = panel( { brand : { light : { safe : false, ratio : 3.1, target : 4.5 } } } );
unsafe.tab('Colours');

check( 'a brand colour text does not survive on says so under the colour, with the numbers - and says nothing where it does',
	unsafe.byId('design-brand-warning').hidden === false && unsafe.byId('design-brand-warning').textContent === say( '/_admin/design/msg/brand-unsafe', 3.1, 4.5 )
	&& colours.byId('design-brand-warning').hidden === true );

console.log('');


// --- The headings over the blocks of rows -------------------------------------------------------
//
// A block of rows has its title and nothing under it: the line that said what the rows are
// is not drawn on any tab, and the words that only it read are gone from the panel's words

console.log( 'The headings' );

const heads = panel();
const headings = {};

TABS.forEach( function( name ) {
	heads.tab( TEXT['/_admin/design/tab/'+ name] );
	headings[name] = byClass( heads.form, 'design-section-label' ).map( function( label ) {
		return byTag( label, 'strong' )[0].textContent+ ':'+ label.children.length+ ':'+ byTag( label, 'small' ).length;
	} ).join();
} );

check( 'a block of rows has its title and nothing under it, on every tab: the animation of Global, the table of the fonts, the two cards of the typefaces, the tuning and the mode of the palette carry a heading of one title each - no small under it',
	JSON.stringify( headings ) === JSON.stringify( { global : 'Animation:1:0', fonts : 'Fonts:1:0', typefaces : 'Installed:1:0,Available:1:0', content : '', buttons : '', components : '', colours : 'Tuning:1:0,Mode:1:0' } ) );

const titled = heads.design._sectionLabel( 'A title' );
const iconed = heads.design._sectionLabel( 'Another', heads.design._icon('colours/mode') );

check( '...the heading is drawn from its title, and from an icon where the block has one - which comes first, in the title - and there is no note to give it',
	heads.design._sectionLabel.length === 2 && titled.children.length === 1 && byTag( titled, 'strong' )[0].textContent === 'A title' && byTag( titled, 'small' ).length === 0
	&& byTag( iconed, 'strong' )[0].children[0].tagName === 'SVG' && byTag( iconed, 'strong' )[0].textContent === 'Another' && byTag( iconed, 'small' ).length === 0 );
check( '...and the sentences that stood under them - the fonts, the animation, the tuning and the mode - are gone from the panel\'s words, with the hint of the two buttons and the words of the size only the summary used',
	[ 'hint/fonts', 'hint/motion', 'hint/tuning', 'colour/mode/note', 'hint/actions', 'fontsize/default', 'fontsize/decimal' ].every( function( key ) { return TEXT['/_admin/design/'+ key] === undefined } )
	&& TEXT['/_admin/design/fontsize/unit'] === 'rem' && TEXT['/_admin/design/hint/size'] !== undefined && TEXT['/_admin/design/hint/pagewidth'] !== undefined );

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
	// The spread of the buttons, and the width of the page
	'chevrons-left-right' : '<path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/>',
	'move-horizontal' : '<path d="m18 8 4 4-4 4"/><path d="M2 12h20"/><path d="m6 8-4 4 4 4"/>',
	// The three devices of the viewport switch, the shapes the Builder's panel carries as its symbols
	'smartphone' : '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
	'tablet' : '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>',
	'monitor' : '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
	// The reload of the preview, beside the three devices
	'refresh-cw' : '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
};

/** Which icon each button of the viewport switch takes */
const VIEWPORT_ICON = { phone : 'smartphone', tablet : 'tablet', desktop : 'monitor' };

/** Which icon each row takes: the two strips of Global, the rows of the Typefaces tab, the knobs of the three tabs, and the rows of the Colours tab */
const ICON_OF = {
	'global/size' : 'a-large-small', 'global/width' : 'move-horizontal',
	'typefaces/family' : 'a-large-small',
	'content/hero-title' : 'a-large-small', 'content/hero-text' : 'a-large-small', 'content/quiet' : 'a-large-small', 'content/loud' : 'a-large-small',
	'content/section-space' : 'scaling', 'content/card-space' : 'align-vertical-space-around', 'content/corners' : 'square-round-corner',
	'content/border' : 'square', 'content/shadow' : 'square-stack',
	'buttons/size' : 'a-large-small', 'buttons/spread' : 'chevrons-left-right', 'buttons/space' : 'align-vertical-space-around', 'buttons/corners' : 'square-round-corner',
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
const iconRow = icons.knobRow('Title (Hero), text size');
const iconLabel = byClass( iconRow, 'design-knob-label' )[0];
const iconSvg = byTag( iconLabel, 'svg' )[0];

check( '...the icon is decoration, drawn on a 24 by 24 grid in the svg namespace: hidden from assistive technology, out of the tab order, and the class that carries the stroke and the size',
	iconSvg.namespaceURI === 'http://www.w3.org/2000/svg' && iconSvg.getAttribute('viewBox') === '0 0 24 24' && iconSvg.getAttribute('aria-hidden') === 'true'
	&& iconSvg.getAttribute('focusable') === 'false' && hasClass( iconSvg, 'design-icon' ) && iconSvg.children.every( function( part ) { return part.namespaceURI === 'http://www.w3.org/2000/svg' } )
	&& iconSvg.children.length === 4 && iconSvg.children.every( function( part ) { return part.tagName === 'PATH' } ) );
check( '...and the label is the row of the two: the icon, then a box that holds the name with its note under it - the name is the box\'s own text, the note a small in it',
	iconLabel.children.length === 2 && iconLabel.children[1].tagName === 'SPAN' && hasClass( iconLabel.children[1], 'design-knob-text' )
	&& iconLabel.children[1].textContent === 'Title (Hero), text size' && byTag( iconLabel.children[1], 'small' ).length === 1
	&& byTag( iconLabel.children[1], 'small' )[0].textContent === TEXT['/_admin/design/knob/content/hero-title/note'] && byTag( iconLabel, 'button' ).length === 0 );

// A row the template does not answer to is grey, and keeps its icon: what it sets is not a thing that depends on the template
choose( icons.byId('design-set-content'), 'v2' );

check( '...a row that is greyed out keeps its icon, with the line that says whose decision that is in the box beside it',
	hasClass( icons.knobRow('Card, shadow'), 'design-knob-row--off' ) && byTag( icons.knobRow('Card, shadow'), 'svg' )[0].dataset.icon === 'square-stack'
	&& byTag( icons.knobRow('Card, shadow'), 'small' )[0].textContent === 'This template does not answer to “Card, shadow”.' );

icons.tab('Global');

check( 'the two strips of Global take theirs by the same mechanism: the root size the letters of two sizes, the width the two arrows that point apart - Lucide\'s a-large-small and move-horizontal, each with the shape of its name, verbatim, in front of the box that holds the name',
	[ [ 'Page, root size', 'a-large-small' ], [ 'Page, width', 'move-horizontal' ] ].every( function( entry ) {
		const label = byClass( icons.knobRow( entry[0] ), 'design-knob-label' )[0];
		const svg = byTag( label, 'svg' );
		return svg.length === 1 && label.children.length === 2 && label.children[0] === svg[0] && hasClass( label.children[1], 'design-knob-text' )
			&& svg[0].dataset.icon === entry[1] && markupOf( svg[0] ) === LUCIDE[entry[1]] && hasClass( svg[0], 'design-icon' );
	} ) );

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

check( 'the head of the preview is one row: the heading and the bar are its two children, in that order - first in the stack, with the frame under it and nothing else, the stage the last thing of the pane',
	pvHead.parent === pvSticky && pvSticky.children[0] === pvHead && pvHead.children.length === 2
	&& hasClass( pvHead.children[0], 'design-preview-heading' ) && hasClass( pvHead.children[1], 'design-preview-bar' )
	&& pvSticky.children.length === 2 && hasClass( pvSticky.children[1], 'design-stage' ) && pv.byId('design-summary') === null
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


// --- The room of the frame -----------------------------------------------------------------
//
// The stage - the box the frame is scaled into - is as tall as the window has left under it,
// less the action bar and the gap the stack keeps between its parts, and never under 16 rem:
// the frame ends a gap above the bar. The numbers are the window's - its height, the font size
// of its root, the bar's height the workbench declares - and the places of the stage, its pane and
// the layout, which a test says: the stand-in has no layout of its own

console.log( 'The room of the frame' );

const rm = panel();
const rmStage = rm.byId('design-frame').parent;
const rmPane = rm.byId('design-preview');
const rmLayout = rm.byId('design-layout');
const rmBar = rm.bar();

/** Two pixel numbers that are the same one, but for the way a float is written */
const near = function( one, other ) { return Math.abs( one - other ) < 0.001 };

/** The timers of that delay that are set and not cleared elapse: each runs once and is not waiting any more */
function elapse( delay ) {
	rm.timers.forEach( function( timer, at ) {
		if( timer === null || timer[1] !== delay )
			return;
		rm.timers[at] = null;
		timer[0]();
	} );
}

/**
 *	Put everything where a window of that height has it - the layout of the screen, the pane
 *	in it, the stage in the pane, and how far the page is scrolled. The pane of a wide window
 *	sticks; the one of a narrow window does not
 */
function put( height, layoutTop, paneTop, stageTop, scrolled, sticky ) {
	rm.view.innerHeight = height;
	rmLayout.rect = { top : layoutTop, left : 0, width : 900, height : 700 };
	rmPane.rect = { top : paneTop, left : 0, width : 600, height : 500 };
	rmPane.computed = { position : sticky === true ? 'sticky' : 'static' };
	rmStage.rect = { top : stageTop, left : 0, width : 565, height : 352 };
	rmLayout.parent.scrollTop = scrolled;
}

/** Put everything there, fit the stage, and say how tall it is then */
function place( height, layoutTop, paneTop, stageTop, scrolled, sticky ) {
	put( height, layoutTop, paneTop, stageTop, scrolled, sticky );
	rm.design._fit();
	return parseFloat( rmStage.style.height );
}

rmStage.parent.computed = { rowGap : '11.2px' };

check( 'the stage is given its room as the screen is drawn - once, before any preview has come back - and the port is measured with that room: the height is on it when the scaler is asked',
	rm.drawn.fits === 1 && ( function() { place( 800, 71, 71, 138, 0, true ); return rm.fitted[rm.fitted.length - 1].port === rmStage && rm.fitted[rm.fitted.length - 1].height === rmStage.style.height } )() );
check( 'it is as tall as the window has left under it: the window\'s height less the top of the stage, the action bar - 4.25 rem, as the workbench declares it, 68px at a root of 16px - and the gap of the stack, 11.2px: 800 - 138 - 68 - 11.2',
	near( parseFloat( rmStage.style.height ), 800 - 138 - 68 - 11.2 ) && rmStage.style.height === ( 800 - 138 - 68 - 11.2 )+ 'px' );
check( '...whatever the page is scrolled to: the preview of a wide window sticks to the top of its pane once the controls are scrolled, and the stage is measured where it stands with the page at its top',
	near( place( 800, -229, 15, 82, 300, true ), 800 - 138 - 68 - 11.2 ) && near( place( 800, 21, 21, 88, 50, true ), 800 - 138 - 68 - 11.2 ) );

check( 'a higher window has less room and a lower one more: 600 and 1000',
	near( place( 600, 71, 71, 138, 0, true ), 600 - 138 - 68 - 11.2 ) && near( place( 1000, 71, 71, 138, 0, true ), 1000 - 138 - 68 - 11.2 ) );
check( '...never under 16 rem: 256px at a root of 16px, where the window is too low for more',
	place( 400, 71, 71, 138, 0, true ) === 256 && place( 100, 71, 71, 138, 0, true ) === 256 );

rm.view.fontSize = '15px';
rmStage.parent.computed = { rowGap : '10.5px' };

check( 'the rem is the root\'s own: the workbench\'s root is 15px, and its bar 4.25 rem is 63.75px there, the floor 240px',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 63.75 - 10.5 ) && place( 300, 71, 71, 138, 0, true ) === 240 );

rm.view.bar = '64px';

check( '...and a bar declared in px is as many px',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 64 - 10.5 ) );

rm.view.bar = '';

check( '...and a workbench that does not declare it has the 4.25 rem it ships with',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 63.75 - 10.5 ) );

rm.view.bar = '4.25rem';
rmBar.rect = { top : 712, left : 0, width : 1055, height : 88 };

check( 'a bar that is taller than it declares - it wraps into rows, on a phone - is what the frame stays above: its own height, 88px',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 88 - 10.5 ) );
check( '...and the page, which clears the foot of its content for the bar at the height declared, is given the rest by the layout as its bottom padding: the 24px the 88px come to over 63.75px',
	rmLayout.style.paddingBottom === '24px' );

rmBar.rect = { top : 760, left : 0, width : 1055, height : 40 };

check( '...and one that is lower than it declares is not a reason to take the room: the declared height is the least, and the layout has no padding of its own then',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 63.75 - 10.5 ) && rmLayout.style.paddingBottom === '' );

rmBar.rect = { top : 736, left : 0, width : 1055, height : 64 };

check( '...nor is the quarter of a pixel the bar is high above its declared height, at a root of 15px: 64px over 63.75px is no padding',
	near( place( 800, 71, 71, 138, 0, true ), 800 - 138 - 64 - 10.5 ) && rmLayout.style.paddingBottom === '' );

rmBar.rect = { top : 0, left : 0, width : 0, height : 0 };
rm.view.fontSize = '16px';
rmStage.parent.computed = { rowGap : '11.2px' };

/*	One column: the pane stands under the controls and is not sticky. On a tablet the stage is in the window, and has what
	is left under it; on a phone it is below the window - and what is left is less than the least, so it is made for the
	window it fills when the page is scrolled to it, with the top of its pane at the top of the window	*/
check( 'in a narrow window, where the preview stands under the controls, the stage takes the room it has under its top where that is 16 rem or more: a tablet of 1180px, the stage at 799px',
	near( place( 1180, 187, 732, 799, 0, false ), 1180 - 799 - 68 - 11.2 ) );
check( '...wherever the page is scrolled to: 400px up and 24px up give the same stage',
	near( place( 1180, 187 - 24, 732 - 24, 799 - 24, 24, false ), 1180 - 799 - 68 - 11.2 ) && near( place( 1180, 187 - 400, 732 - 400, 799 - 400, 400, false ), 1180 - 799 - 68 - 11.2 ) );

rmBar.rect = { top : 756, left : 0, width : 390, height : 88 };

check( '...and on a phone, where the stage is below the window and the room under it would be less than 16 rem, it is made for the window it fills with the top of its pane at the top of it: the window less the 100px the stage stands in its pane, the bar of two rows and the gap',
	near( place( 844, 218, 857, 957, 0, false ), 844 - 100 - 88 - 11.2 ) && near( place( 844, 218 - 500, 857 - 500, 957 - 500, 500, false ), 844 - 100 - 88 - 11.2 ) );
check( '...which is more than the least, and the same at every scroll: the preview is the height of the window when it is scrolled to',
	place( 844, 218, 857, 957, 0, false ) > 256 && place( 844, 218 - 856, 857 - 856, 957 - 856, 856, false ) === place( 844, 218, 857, 957, 0, false ) );
check( '...and the layout gives the page the bar it has beyond the bar it declares - 20px, the 88px over the 68px - so that the page can be scrolled until the top of the pane is at the top of the window',
	rmLayout.style.paddingBottom === '20px' );

rmBar.rect = { top : 712, left : 0, width : 390, height : 132 };

check( '...whatever the rows come to: a bar of three rows, 132px, is 64px over, and the stage is that much lower for it',
	near( place( 844, 218, 857, 957, 0, false ), 844 - 100 - 132 - 11.2 ) && rmLayout.style.paddingBottom === '64px' );

rmBar.rect = { top : 0, left : 0, width : 0, height : 0 };

/*	Opening a tab draws the controls anew, and in one column they stand over the stage: a column of another
	height moves it, and nothing asks for a preview on the way. So the stage is fitted by the tab itself, to where
	it stands then, and has the height of the place and not of the tab opened before. The stand-in lays nothing out:
	the test puts the stage where the column of the tab about to be opened has it (put), and opens the tab by hand	*/
const rmGlobal = place( 1180, 187, 732, 799, 0, false );
const rmTabFits = rm.fitted.length;

put( 1180, 187, 520, 587, 0, false );
rm.tab('Fonts');

check( 'in one column a tab that is opened fits the stage to where it stands under the controls of that tab, with no preview asked for or answered on the way: a shorter column, the stage 212px higher, is 212px taller',
	rm.design._tab === 'fonts' && rm.requests.length === 0 && rm.fitted.length === rmTabFits + 1 && rm.fitted[rm.fitted.length - 1].port === rmStage
	&& near( parseFloat( rmStage.style.height ), 1180 - 587 - 68 - 11.2 ) && near( parseFloat( rmStage.style.height ), rmGlobal + 212 ) );

/*	The frame has another height with the stage, and the page in it lays itself out again only after its window has heard the
	resize: it sets the heights of its covers in an animation frame that follows it. An anchor measured before that is where it
	stood at the old height, so the jump of a tab that changed the height waits - for the resize, and for the animation frame
	after it, which is queued after the page's own. The stand-in records what is listened to and asked for, and the test says
	when the resize comes	*/
check( 'the jump of that tab waits for the page in the frame: nothing is scrolled when the tab is opened, one resize is listened for on the frame\'s window, and a timer of 300ms stands behind it',
	rm.stage.scrolls.length === 0 && rm.stage.listeners.length === 1 && rm.stage.listeners[0][0] === 'resize' && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 1 );

rm.stage.resize();

check( '...the resize itself scrolls nothing: it asks the frame\'s window for an animation frame, which is queued after the one the page lays its covers out in',
	rm.stage.scrolls.length === 0 && rm.stage.frames.length === 1 && rm.stage.listeners.length === 1 );

rm.stage.animate();

check( '...and in that frame the page is scrolled, once, to the anchor of the tab - the typography block of Fonts, 700px below the top of the window with the page scrolled 100px, so 800 - and the listener is taken away',
	rm.stage.scrolls.length === 1 && JSON.stringify( rm.stage.scrolls[0] ) === JSON.stringify( { top : 800, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 );

elapse( 300 );

check( '...and the timer behind it, when it elapses, adds none', rm.stage.scrolls.length === 1 && rm.waiting( 300 ).length === 0 );

put( 1180, 187, 1032, 1099, 0, false );
rm.tab('Content');

check( '...a taller one, with less than 16 rem left under the stage, gives it the height of the window it fills with the top of its pane at the top of it, as on a phone: 1180px less the 67px the stage stands in its pane, the bar and the gap',
	rm.design._tab === 'content' && near( parseFloat( rmStage.style.height ), 1180 - 67 - 68 - 11.2 ) && rm.fitted.length === rmTabFits + 2 );

// The frame may not be made another size after all, and then no resize comes: the timer takes the jump there
const rmWaited = rm.stage.scrolls.length;

elapse( 300 );

check( 'where no resize comes the timer takes the jump there, 300ms after the tab was opened: once, to the anchor of the tab - the hero, 300px below the top of the window with the page scrolled 100px, so 400 - and the listener is taken away',
	rmWaited === 1 && rm.stage.scrolls.length === 2 && JSON.stringify( rm.stage.scrolls[1] ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 );

put( 1180, 187, 732, 799, 0, false );
rm.tab('Global');

check( '...and the way back gives the stage the height it began with: it does not depend on which tabs were opened before',
	rm.design._tab === 'global' && near( parseFloat( rmStage.style.height ), rmGlobal ) && near( rmGlobal, 1180 - 799 - 68 - 11.2 ) && rm.requests.length === 0 && rm.fitted.length === rmTabFits + 3 );

// Whichever comes first jumps and the other finds it done: here the resize is heard, then the timer elapses, and the frame it asked for comes last
const rmHeld = rm.stage.scrolls.length;

rm.stage.resize();
elapse( 300 );
rm.stage.animate();

check( '...and the jump is made once, whichever comes first: the resize heard and the timer before the animation frame it asked for - one scroll, to the top of the page, which Global is',
	rmHeld === 2 && rm.stage.scrolls.length === 3 && JSON.stringify( rm.stage.scrolls[2] ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 );

// Beside the controls the pane sticks and the column does not move the stage: it has the same height under every tab, from a height that was not the right one
rmStage.style.height = '123px';
put( 800, 71, 71, 138, 0, true );
rm.tab('Colours');

check( '...and in two columns, where the pane sticks, a tab fits it as well: 800px high, the stage at 138px, whatever height it had before',
	near( parseFloat( rmStage.style.height ), 800 - 138 - 68 - 11.2 ) && rm.fitted.length === rmTabFits + 4 );

// What that tab waited for comes. Then a tab that leaves the stage as tall as it was has nothing to wait for
rm.stage.resize();
rm.stage.animate();
elapse( 300 );

const rmStill = rm.stage.scrolls.length;

put( 800, 71, 71, 138, 0, true );
rm.tab('Fonts');

check( 'where the fit leaves the stage as tall as it was - in two columns every tab has the one height - the jump is made at once: one scroll, to the anchor of the tab, with no resize listened for and no timer set',
	rm.design._tab === 'fonts' && rm.stage.scrolls.length === rmStill + 1 && JSON.stringify( rm.stage.scrolls[rmStill] ) === JSON.stringify( { top : 800, behavior : 'smooth' } )
	&& rm.stage.listeners.length === 0 && rm.waiting( 300 ).length === 0 );

// A frame whose page cannot be reached has nothing to wait for, and a window that cannot be listened to is not waited on
const rmWindow = rm.stage.document.defaultView;
const rmListen = rmWindow.addEventListener;

rm.stage.reachable = false;
put( 1180, 187, 520, 587, 0, false );

check( 'a frame the panel cannot reach is not waited for: the tab is opened, nothing throws, nothing is listened to and no timer is set',
	threw( function() { rm.tab('Components') } ) === false && rm.design._tab === 'components' && rm.stage.listeners.length === 0 && rm.waiting( 300 ).length === 0 );

rm.stage.reachable = true;
rmWindow.addEventListener = undefined;
put( 1180, 187, 1032, 1099, 0, false );

const rmGone = rm.stage.scrolls.length;

rm.tab('Buttons');

check( '...nor is a window that cannot be listened to: the jump is made at once, to the anchor of the tab, and no timer is set',
	rm.stage.scrolls.length === rmGone + 1 && JSON.stringify( rm.stage.scrolls[rmGone] ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) && rm.waiting( 300 ).length === 0 );

rmWindow.addEventListener = rmListen;

// The colours column is the one the checks that follow read the brand warning off
rm.tab('Colours');

/*	The answer of a preview can give the colours column another line, the brand warning, and in one column that
	moves the stage. It is fitted once the answer is taken over and not before: the stage stands 40px lower while
	the warning is shown, which the stand-in is told by the warning's own state	*/
put( 1180, 187, 732, 799, 0, false );

const rmWarning = rm.byId('design-brand-warning');
const rmLowered = function() { return { top : 799 + ( rmWarning.hidden === false ? 40 : 0 ), left : 0, width : 565, height : 352 } };

Object.defineProperty( rmStage, 'rect', { configurable : true, enumerable : true, get : rmLowered } );
rm.design._fit();

const rmUnwarned = parseFloat( rmStage.style.height );
const rmAnswered = rm.stage.scrolls.length;

rm.design._previewNow();
rm.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : { light : { safe : false, ratio : 3.1, target : 4.5 } }, notes : [] } );

check( 'the answer of a preview is fitted once it is taken over, not before: the brand warning it shows puts the stage 40px lower in one column, and the stage is that much shorter for it',
	rmWarning.hidden === false && near( rmUnwarned, 1180 - 799 - 68 - 11.2 ) && near( parseFloat( rmStage.style.height ), rmUnwarned - 40 ) );

/*	The jump after the answer is made like the one of a tab: the fit gave the frame another height, and the page in it lays
	itself out again only after its window has heard the resize - an anchor measured before that is where it stood at the old
	height. So the answer scrolls nothing when it is taken over, and the page goes to the anchor once the frame has heard it	*/
check( '...and the jump that follows it waits for the page in the frame, as a tab\'s does: the stage changed its height, so nothing is scrolled when the answer is taken over, one resize is listened for on the frame\'s window, and a timer of 300ms stands behind it',
	rm.stage.scrolls.length === rmAnswered && rm.stage.listeners.length === 1 && rm.stage.listeners[0][0] === 'resize' && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 1 );

rm.stage.resize();
rm.stage.animate();
elapse( 300 );

check( '...then the page is scrolled, once, to the anchor of the open tab - the grounds of Colours, 450px below the top of the window with the page scrolled 100px, so 550 - the listener is taken away, and the timer behind it adds no second scroll',
	rm.stage.scrolls.length === rmAnswered + 1 && JSON.stringify( rm.stage.scrolls[rmAnswered] ) === JSON.stringify( { top : 550, behavior : 'smooth' } )
	&& rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 0 );

// The stage is a plain place again, for the checks that follow
Object.defineProperty( rmStage, 'rect', { configurable : true, enumerable : true, writable : true, value : { top : 799, left : 0, width : 565, height : 352 } } );

rmStage.style.height = '123px';
rmStage.rect = { top : 0, left : 0, width : 0, height : 0 };
rm.design._fit();

check( 'a pane that is not on screen has no stage to give room to: its height is left as it is - and the scaler is asked all the same',
	rmStage.style.height === '123px' && rm.fitted[rm.fitted.length - 1].port === rmStage );

check( 'the window\'s resizing is heard once however often the screen is drawn: one listener, on the window, that waits for the movement to stop',
	rm.heard.length === 1 && rm.heard[0][0] === 'resize' && rm.heard[0][1] === rm.design._fitLater && rm.design._bound === true );

const rmFits = rm.fitted.length;

rm.design.showCurrent();
rm.design.showCurrent();

check( '...and not again by a screen that is drawn anew - each drawing fits its stage once, and sets no listener',
	rm.heard.length === 1 && rm.fitted.length === rmFits + 2 );

rm.design._fitLater();
rm.design._fitLater();

check( 'a resizing asks for the fit when it has stopped: the second step of it cleared the wait of the first, and one of 150ms is waiting',
	rm.waiting( 150 ).length === 1 && rm.waiting( 150 )[0][0] === rm.design._fit );

// The screen was drawn anew: the stage that is there now is the one that is fitted
const rmDrawn = rm.byId('design-frame').parent;
rmDrawn.parent.computed = { rowGap : '11.2px' };
rmDrawn.rect = { top : 799, left : 0, width : 565, height : 352 };
rm.byId('design-preview').rect = { top : 732, left : 0, width : 600, height : 500 };
rm.byId('design-layout').rect = { top : 187, left : 0, width : 770, height : 700 };
rm.view.innerHeight = 1280;
rm.waiting( 150 )[0][0]();

check( '...and what runs then fits the stage to the window as it is by then: 1280px high, the stage at 799px',
	rmDrawn !== rmStage && near( parseFloat( rmDrawn.style.height ), 1280 - 799 - 68 - 11.2 ) && rm.fitted[rm.fitted.length - 1].height === rmDrawn.style.height );

console.log('');


// --- The frame follows the tab -------------------------------------------------------------
//
// One page with an anchor for each tab. The frame is of this very origin, so
// the panel scrolls its window by hand: smoothly after a tab is opened and after
// every change, at once after the page was rebuilt

console.log( 'The anchors' );

const anchors = panel();

check( 'each tab has the id of an element on the specimen: global, type, type, content, buttons, components and colours - Fonts and Typefaces are the typography block, Colours the grounds',
	JSON.stringify( anchors.design.ANCHORS ) === JSON.stringify( { global : 'global', fonts : 'type', typefaces : 'type', content : 'content', buttons : 'buttons', components : 'components', colours : 'colours' } ) );

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
const fwFitted = fw.fitted.length;

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

check( 'with it off a tab that is opened does not scroll the frame - four tabs, no scrollTo, and the frame\'s page is not even asked for the element - but the stage is fitted all the same, by each of them: four fits',
	fw.stage.scrolls.length === fwScrolled && fw.stage.asked.length === fwAsked && fw.design._tab === 'content' && fw.fitted.length === fwFitted + 4 );

fw.move( 'Quiet variant, text size', 1 );
fw.design._previewNow();
fw.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a change: the preview comes back and its stylesheet is swapped inside the frame that stands, which stays where it is - and the stage is fitted once more with the answer',
	fw.stage.style.textContent === ':root{--x:1}' && fw.stage.scrolls.length === fwScrolled && fw.stage.asked.indexOf('cards') === -1
	&& fw.fitted.length === fwFitted + 5 && fw.design._edit.knobs.content.quiet === 1 );

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

jp.move( 'Quiet variant, text size', 1 );

check( 'a knob of Content that is not the hero\'s sets the target to the cards, and nothing scrolls until the preview has answered',
	jp.design._target === 'cards' && jp.stage.scrolls.length === jpOpened );

jpAnswer();

check( '...and then the frame goes to the cards: the element with the id cards, from where the page stands',
	jpAsked() === 'cards' && jp.stage.scrolls.length === jpOpened + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

const jpWhere = {};

KNOBS.content.forEach( function( knob ) {

	jp.move( TEXT['/_admin/design/knob/content/'+ knob+ '/label'], -1 );
	jpWhere[knob] = jp.design._target;
} );

check( 'each knob of Content has its place: the title and the text of the hero the hero, the other seven - quiet, loud, the spaces, the corners, the line and the shadow - the cards',
	JSON.stringify( jpWhere ) === JSON.stringify( { 'hero-title' : 'content', 'hero-text' : 'content', quiet : 'cards', loud : 'cards', 'section-space' : 'cards', 'card-space' : 'cards', corners : 'cards', border : 'cards', shadow : 'cards' } ) );

jp.move( 'Title (Hero), text size', 1 );
jpAnswer();

check( 'a change of the title of the hero goes to the hero: #content',
	jp.design._target === 'content' && jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.move( 'Subtitle (Hero), text size', 1 );
jpAnswer();

check( '...and so does one of the text of the hero', jp.design._target === 'content' && jpAsked() === 'content' );

jp.move( 'Card, shadow', 1 );
jpAnswer();

check( 'one of the cards goes back to the cards, whichever knob was moved before it', jp.design._target === 'cards' && jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

// A template is the tab's, not the knob's
choose( jp.byId('design-set-content'), 'v2' );

check( 'choosing a template puts the target back to the tab\'s own, and the frame goes to the anchor of the tab when the preview has answered',
	jp.design._target === null );

jpAnswer();

check( '...the hero, where it went when the tab was opened', jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.move( 'Card, corners', 1 );
jp.tab('Buttons');

check( 'opening another tab puts the target back as well, and the frame goes to the anchor of that tab: the place a change was looked at belonged to the tab that was left',
	jp.design._target === null && jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

jp.move( 'Button, size', 1 );

check( 'a knob of a tab with no entry of its own goes to the anchor of the tab: Buttons, #buttons',
	jp.design._target === 'buttons' );

jpAnswer();

check( '...and the frame goes there', jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

jp.tab('Components');
jp.move( 'Table, stripes', 1 );

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
jp.move( 'Quiet variant, text size', -1 );
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

check( 'every id the script jumps to is on the specimen exactly once: the seven of the tabs and the cards',
	jumpedTo.length === 10 && jumpedTo.every( function( id ) { return specimenIds[id] === 1 } ) && specimenIds.cards === 1 );
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
// A draft is not a website. Two buttons, and a confirmation before anything is
// written - built from what design/plan says, so it names the files, the ones
// that are not Design's and the shortcodes a frame would lose. What was done is
// said in the line of the action bar, and the chip beside it is drawn again

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
check( 'no line under the bar says which of the two does what: the hint of the actions is gone from the screen and from the panel\'s words in both languages - nobody read it',
	asking.byId('design-actions-hint') === null && TEXT['/_admin/design/hint/actions'] === undefined && asking.form.children[asking.form.children.length - 1] === asking.bar() );
check( 'with files that are not Design\'s the state is foreign, and the chip says so in one word - the files are named where the question is asked, in the confirmation below',
	asking.design._stateKey() === 'foreign' && asking.chip().textContent === 'Not written by Design' && asking.chip().dataset.state === 'foreign'
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
check( '...in the line of the bar, the whole of it its title, and the chip is drawn again from what the server then answered: the files are Design\'s now and the state is up to date',
	asking.line().parent === asking.bar() && asking.line().title === asking.line().textContent && asking.line().className === ''
	&& asking.chip().parent === asking.bar() && asking.chip().textContent === 'Up to date' && asking.chip().dataset.state === 'current' );

// Saying no leaves the draft saved and the website alone
const declined = panel( {} );
declined.willConfirm( false );
declined.byId('design-apply').fire('click');
declined.answer( 200, {} );
declined.answer( 200, { files : delivered } );

check( 'no in the confirmation sends no apply - it reloads the panel',
	JSON.stringify( endpoints( declined ) ) === JSON.stringify( [ 'list' ] ) && declined.confirms.length === 1 );

declined.answer( 200, listing( {} ) );

check( '...and says the draft is saved and not applied, in the line of the bar', ( declined.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/cancelled']
	&& declined.line().parent === declined.bar() && declined.line().title === TEXT['/_admin/design/msg/cancelled'] );

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

drafting.answer( 200, listing( { current : false } ) );

check( '...it says that the draft is saved in the line of the bar, and the chip is drawn again from the answer: the file does not answer to the selection - saved, not applied',
	drafting.line().textContent === TEXT['/_admin/design/msg/saved'] && drafting.line().title === TEXT['/_admin/design/msg/saved'] && drafting.line().className === ''
	&& drafting.chip().textContent === 'Saved, not applied' && drafting.chip().dataset.state === 'drifted' && drafting.chip().parent === drafting.bar() );

console.log('');


// --- The previous version ----------------------------------------------------------

console.log( 'Restoring' );

const empty = panel( {} );

check( 'with no previous version there is nothing to restore: no button, and the action bar holds the line, the chip and the three buttons it always did',
	empty.byId('design-restore') === null
	&& byClass( empty.form, 'nino-admin-actionbar' )[0].children.map( function( button ) { return button.id } ).join() === 'design-msg,design-state-word,design-reset,design-save,design-apply' );

const kept = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
const restoreButton = kept.byId('design-restore');
const keptBar = byClass( kept.form, 'nino-admin-actionbar' )[0];
const restoreCalls = [];
const restoreWay = kept.design._restore;
kept.design._restore = function( button ) { restoreCalls.push( button ); return restoreWay.call( kept.design, button ) };

check( 'with one the action bar has a second quiet button, before Reset: the label of the way back and, for its title, the sentence with the date of that version',
	restoreButton !== null && restoreButton.tagName === 'BUTTON' && restoreButton.type === 'button' && restoreButton.parent === keptBar
	&& restoreButton.textContent === 'Restore previous version' && restoreButton.title === 'Previous version from 2026-09-02 08:30'
	&& keptBar.children.map( function( button ) { return button.id } ).join() === 'design-msg,design-state-word,design-restore,design-reset,design-save,design-apply'
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

check( 'and the panel is drawn again from the new state, saying so in the line of the bar', ( kept.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored']
	&& kept.line().parent === kept.bar() && kept.line().title === TEXT['/_admin/design/msg/restored'] && kept.chip().textContent === 'Up to date' );

const noted = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
noted.willConfirm( true );
noted.byId('design-restore').fire('click');
noted.answer( 200, { notes : [ 'restored, but could not keep the version it replaced in data/design-previous.php' ] } );
noted.answer( 200, listing( { current : false } ) );

check( 'a restore that went through with a note says restored, and the note beside it - in the line of the bar with the class of a note - and the chip is the state the restored files are in: saved, not applied',
	( noted.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored']+ ' restored, but could not keep the version it replaced in data/design-previous.php'
	&& noted.line().className === 'nino-admin-hint' && noted.chip().textContent === 'Saved, not applied' && noted.chip().dataset.state === 'drifted' );

const failing = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
failing.byId('design-restore').fire('click');
failing.answer( 500, { error : 'could not write /assets/theme.css' } );

check( 'a restore that failed says why and does not redraw: in the line of the bar, which carries the class of a failure and the whole of it as its title',
	( failing.byId('design-msg') || {} ).textContent === '(500) could not write /assets/theme.css' && failing.line().className === 'nino-admin-error'
	&& failing.line().title === '(500) could not write /assets/theme.css' && failing.requests.length === 0 );

// --- Asking the workbench ----------------------------------------------------------

console.log( 'The workbench' );

const wasRouted = [];
const withHelper = panel( {}, { raw : true, api : { call : function( action, payload, callback ) { wasRouted.push( [ action, payload ] ); callback( 200, { via : 'api' } ) } } } );
let gotAnswer = null;
withHelper.design._apiCall( 'save', { b : 2 }, function( status, response ) { gotAnswer = [ status, response ] } );
check( 'the panel hands the action \'design/<action>\' and the payload to the workbench\'s request helper',
	wasRouted.length === 1 && wasRouted[0][0] === 'design/save' && wasRouted[0][1].b === 2 && gotAnswer[1].via === 'api' );

const helped = panel( {}, { raw : true, api : { errorText : function( status, response, key ) { return 'told '+ status+ ' ['+ key+ ']' } } } );
helped.design._showError( helped.form, 503, { error : 'Busy' } );
check( 'a failed request is said as the workbench says it, with the panel\'s own sentence for what it was doing',
	byClass( helped.form, 'nino-admin-error' ).length === 1 && byClass( helped.form, 'nino-admin-error' )[0].textContent === 'told 503 [/_admin/common/error/load]' );

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

check( 'the panel registers under its own uri', registered !== null && typeof registered.isDirty === 'function' && typeof registered.save === 'function' && typeof registered.discard === 'function' );
check( '...a screen drawn from what is stored holds nothing unsaved, and the shell is told when it is drawn', registered.isDirty() === false && registry.refreshed > 0 );

tracked.tab('Content');
check( '...opening a tab changes nothing that is unsaved', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );

tracked.move( 'Title (Hero), text size', 1 );
check( '...a knob that was moved does - the same answer the way back is shown for', registered.isDirty() === true && tracked.byId('design-reset').hidden === false );

tracked.byId('design-reset').fire('click');
check( '...and the way back, which draws the stored selection again, makes it not', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );
check( '...and says so in the line of the bar, on the screen that was drawn again, with the chip beside it as the files stand',
	tracked.line().textContent === TEXT['/_admin/design/msg/reverted'] && tracked.line().title === TEXT['/_admin/design/msg/reverted'] && tracked.line().className === ''
	&& tracked.line().parent === tracked.bar() && tracked.chip().textContent === 'Up to date' && tracked.chip().parent === tracked.bar() );

tracked.tab('Content');
tracked.move( 'Title (Hero), text size', 1 );
registered.discard();
check( 'discarding takes the stored selection for the screen\'s own', registered.isDirty() === false );

tracked.tab('Content');
tracked.move( 'Title (Hero), text size', 1 );
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
refused.move( 'Title (Hero), text size', 1 );
savedFor = [];
registry.entries['design'].save( function( ok ) { savedFor.push( ok ) } );
refused.answer( 500, { error : 'could not write data/design.php' } );
check( 'a draft the server turns down says so, in the line of the bar and to the shell, as a failure with the whole of it as its title, and the way back stays',
	savedFor.length === 1 && savedFor[0] === false && ( refused.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php'
	&& refused.line().className === 'nino-admin-error' && refused.line().title === '(500) could not write data/design.php'
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

	/** What the five buttons of a strip say is pressed when it stands at a position: -2 to +2 */
	const pressedAt = function( position ) { return SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join() };

	check( 'the server\'s answer is in the shape the screen draws: the tabs name the kind of each knob - a scale or a choice - and are the kinds the stand-in has, the steps are five words, the size and the width are numbers with no list of sizes beside them, and the palette gives each knob its kind',
		real.steps.join() === 'least,less,default,more,most' && typeof real.size === 'number' && typeof real.width === 'number' && real.sizes === undefined
		&& JSON.stringify( real.tabs ) === JSON.stringify( KINDS )
		&& Object.keys( real.palette ).every( function( key ) { return [ 'scale', 'choice' ].indexOf( real.palette[key].kind ) !== -1 } )
		&& real.palette.saturation.steps === 5 && real.palette.harmony.steps.length === 4 );

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

		if( whole.body().children.length === 0 || whole.chip() === null || whole.chip().textContent === '' )
			blank.push( tab );

		unsaid = unsaid.concat( unwritten( whole ) );
	} );

	check( 'the server\'s answer draws all seven tabs, each with controls in it and the state of the files on the chip in the bar'+ ( failed.length === 0 ? '' : ' - threw: '+ failed.join() ),
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
	check( '...the size and the width, two strips, on the numbers stored, and the animation on the values stored',
		whole.pressed('Page, root size') === pressedAt( real.size ) && whole.pressed('Page, width') === pressedAt( real.width )
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
				&& whole.byId('design-font-'+ role+ '-size').value === ( chosen.size || real.inherited[role] )
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('min') === String( real.bounds[role][0] ) && whole.byId('design-font-'+ role+ '-size').getAttribute('max') === String( real.bounds[role][1] )
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('placeholder') === null
				&& ( role === 'code' ? whole.byId('design-font-code-uppercase') === null : whole.byId('design-font-'+ role+ '-uppercase').checked === chosen.uppercase );
		} ) );

	let tabProblems = [];

	Object.keys( real.tabs ).forEach( function( tab ) {

		whole.tab( TEXT['/_admin/design/tab/'+ tab] );

		const row = rowOf( tab );
		const answered = ( row.catalogue[row.set] || {} ).knobs || [];
		const names = Object.keys( real.tabs[tab] );
		const drawnRows = byClass( whole.form, 'design-knob-row' );

		if( selectOf( row ) === false || whole.byId('design-knobs').children[0] !== whole.byId('design-set-'+ tab).parent )
			tabProblems.push( tab+ ': the select of its template, first in the card' );
		const off = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) } ).map( function( one ) { return one.dataset.knob } );
		const lit = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) === false } ).every( function( one ) {
			const position = real.knobs[tab][one.dataset.knob];
			const reach = real.tabs[tab][one.dataset.knob] === 'scale' ? [ -2, -1, 0, 1, 2 ] : [ -1, 0, 1 ];
			return byTag( one, 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === reach.map( function( at ) { return String( at === position ) } ).join();
		} );

		if( drawnRows.map( function( one ) { return one.dataset.knob } ).join() !== names.join() )
			tabProblems.push( tab+ ': rows '+ drawnRows.map( function( one ) { return one.dataset.knob } ).join() );
		if( off.join() !== names.filter( function( knob ) { return answered.indexOf( knob ) === -1 } ).join() )
			tabProblems.push( tab+ ': grey rows '+ off.join() );
		if( lit === false )
			tabProblems.push( tab+ ': a step is lit that the knob does not stand at' );
		if( whole.lit().length !== answered.length )
			tabProblems.push( tab+ ': '+ whole.lit().length+ ' rows lit for '+ answered.length+ ' knobs' );
	} );

	check( 'each knob tab is the select of its template over a strip for every knob of the tab, grey where the chosen template does not answer to it, and the others lit on the position stored - on five buttons for a scale, three for a choice'
		+ ( tabProblems.length === 0 ? '' : ' - '+ tabProblems.join(' | ') ), tabProblems.length === 0 && Object.keys( real.tabs ).join() === 'content,buttons,components' );
	check( '...and the templates the server stored are the ones with something grey: a template that leaves a knob out is on show',
		Object.keys( real.tabs ).some( function( tab ) { return ( ( rowOf( tab ).catalogue[ rowOf( tab ).set ] || {} ).knobs || [] ).length < Object.keys( real.tabs[tab] ).length } ) );

	whole.tab('Colours');

	check( 'Colours is drawn from the palette the server published: the five knobs, each over as many positions as it has - the names of a choice, the number of a scale - and the mode on the stored word',
		byClass( whole.form, 'design-knob-row' ).length === Object.keys( real.palette ).length - 1
		&& Object.keys( real.palette ).filter( function( key ) { return key !== 'mode' } ).every( function( key ) {
			return byTag( whole.knobRow( TEXT['/_admin/design/colour/'+ key+ '/label'] ), 'button' ).length === ( real.palette[key].kind === 'scale' ? real.palette[key].steps : real.palette[key].steps.length );
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

	check( 'design/list says what the Typefaces tab needs to know before it asks for anything: whether the repository has an address and whether it has a key, two booleans and nothing else',
		Object.keys( real.webfonts || {} ).join() === 'url,key' && typeof real.webfonts.url === 'boolean' && typeof real.webfonts.key === 'boolean' );

	const webfontsFile = process.env.DESIGN_WEBFONTS || '';

	if( webfontsFile === '' || fs.existsSync( webfontsFile ) === false )
		console.log( '  --  no answer of design/webfonts/list was handed to this run (DESIGN_WEBFONTS), so the Typefaces tab is not drawn from the real one' );
	else {

		const shelved = JSON.parse( fs.readFileSync( webfontsFile, 'utf8' ) );
		const typed = panel( Object.assign( {}, real, { webfonts : { url : true, key : true } } ), { whole : true } );

		typed.tab('Typefaces');
		typed.answer( 200, shelved );

		const slugs = Object.keys( shelved.installed );
		const installedNow = faceRows( installedCard( typed ) );

		check( 'the answer of design/webfonts/list is in the shape the Typefaces tab draws: the halves of the tab are keyed as the stand-in has them, and the families, the installed ones and the directories that are none are what the server gave',
			Object.keys( shelved ).join() === Object.keys( shelfOf() ).join() && slugs.length > 0 && Object.keys( shelved.broken ).length > 0 && shelved.families.length > 0 && shelved.fetched > 0 && shelved.refusal === ''
			&& slugs.every( function( slug ) { return Object.keys( shelved.installed[slug] ).join() === Object.keys( installedOf( slug, 'x', '1' ) ).join() } )
			&& shelved.families.every( function( entry ) { return Object.keys( entry ).join() === Object.keys( offerOf( 'x', 'X', 'sans' ) ).join() } ) );
		check( '...and the tab draws a row for each of them: the installed families by their names, then the directories that are none - and a row of the list for each family on the first page',
			installedNow.map( function( row ) { return row.dataset.slug } ).join() === slugs.concat( Object.keys( shelved.broken ) ).join()
			&& slugs.every( function( slug ) { return faceName( faceRowOf( installedCard( typed ), slug ) ).textContent === shelved.installed[slug].name } )
			&& faceRows( availableCard( typed ) ).length === Math.min( shelved.families.length, typed.design.FACES_PER_PAGE ) );
		check( '...with who uses what: a family a role of the saved setup uses has its Remove off, and the others have it on',
			slugs.every( function( slug ) { return faceButton( faceRowOf( installedCard( typed ), slug ) ).disabled === ( ( shelved.uses[slug] || [] ).length > 0 ) } )
			&& slugs.some( function( slug ) { return ( shelved.uses[slug] || [] ).length > 0 } ) );
		check( '...the name of an installed family is set in the file the server named for it - a url of the project\'s own - and every word on the screen is one the panel\'s texts carry',
			slugs.every( function( slug, at ) { return typed.typeset.made[at].source === 'url("'+ shelved.installed[slug].face+ '")' && shelved.installed[slug].face.indexOf('[[') === -1 } )
			&& unwritten( typed ).length === 0 );
	}
}

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
