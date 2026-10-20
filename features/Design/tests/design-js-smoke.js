/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screen is, over a dom
 *												stand-in: the strip of eight tabs in the head of the
 *												pane, and under it the controls of the tab that is open
 *												- the three cards of the Theme tab: the list of the
 *												themes, with its row for a new one and its field for
 *												an import, the active theme with its name, its
 *												description and the chip that says the screen differs
 *												from it, and the css of the theme, which every input
 *												mirrors into the preview - with a load (and the
 *												typefaces it offers to install), a create, a save, a
 *												duplicate, a delete, an export and an import made at
 *												once, and the sentence a refusal is told in; the two
 *												frames, the root size and the width of the
 *												page as two strips of five, and the animation; the five
 *												font roles, with a size that is typed; the two cards
 *												of the Typefaces tab - the families that are installed
 *												and the list of those on offer, searched, told apart
 *												by category in one row and paged - the rows in a box
 *												as tall as the window has room for, the pager after
 *												the box - with an install and a removal made at once,
 *												the names set in their own faces and a sentence of
 *												its own for each thing a refusal of the list can be
 *												about; the template of content, buttons and
 *												components over its named knobs, the content's in four
 *												groups under a strip of tabs of their own,
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
 *												(DESIGN_LISTING), design/webfonts/list
 *												(DESIGN_WEBFONTS) and the actions of the Theme tab
 *												(DESIGN_THEMES) really gave, which the screen is
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
		// The controls of a card the Theme tab switches off while a request is on its way: a list of tags, of which one may be asked for by its type - 'button, input[type="file"]'
		querySelectorAll	: function( selector ) {
			const wanted = selector.split(',').map( function( one ) { return /^([a-z]+)(?:\[type="([a-z]+)"\])?$/.exec( one.trim() ) } );
			return descendants( this ).filter( function( el ) {
				return wanted.some( function( found ) { return found !== null && el.tagName === found[1].toUpperCase() && ( found[2] === undefined || el.type === found[2] ) } );
			} );
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
	// The element after it in its parent, as a browser's insertBefore() takes it for a place: null for the last
	Object.defineProperty( el, 'nextSibling', { get : function() { return el.parent === null ? null : ( el.parent.children[el.parent.children.indexOf( el ) + 1] || null ) } } );
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

const TABS = [ 'theme', 'global', 'fonts', 'typefaces', 'content', 'buttons', 'components', 'colours' ];

/** The kind of every knob, by tab, as design/list gives them: a scale has five positions, -2 to +2, a choice three, -1 to +1 */
const KINDS = {
	content 		: { 'hero-title' : 'scale', 'hero-text' : 'scale', quiet : 'scale', loud : 'scale', 'section-title' : 'scale', 'section-subtitle' : 'scale', 'section-space' : 'scale', 'section-line' : 'scale', 'card-title' : 'scale', 'card-subtitle' : 'scale', 'card-space' : 'scale', corners : 'scale', border : 'scale', shadow : 'scale' },
	buttons 		: { size : 'scale', spread : 'scale', space : 'scale', corners : 'scale' },
	components 	: { scale : 'scale', space : 'scale', corners : 'scale', border : 'scale', zebra : 'choice', labels : 'choice' },
};

/** The names of the knobs of each tab, in the order of the tab */
const KNOBS = {};
Object.keys( KINDS ).forEach( function( tab ) { KNOBS[tab] = Object.keys( KINDS[tab] ) } );

/** How a tab shows its knobs, as design/list gives it: the content in four groups - the knobs of each in the order they are shown in - and the others in none */
const GROUPS = {
	content : { hero : [ 'hero-title', 'hero-text' ], section : [ 'section-title', 'section-subtitle', 'section-space', 'section-line' ], card : [ 'card-title', 'card-subtitle', 'card-space', 'corners', 'border', 'shadow' ], variants : [ 'quiet', 'loud' ] },
};

/** The knobs of the content in the order the groups show them in, one group after the other */
const SHOWN = [].concat.apply( [], Object.keys( GROUPS.content ).map( function( group ) { return GROUPS.content[group] } ) );

/** The word on the tab of the group a knob of the content stands in */
function groupWord( knob ) {
	return Object.keys( GROUPS.content ).filter( function( group ) { return GROUPS.content[group].indexOf( knob ) !== -1 } ).map( function( group ) { return TEXT['/_admin/design/group/content/'+ group+ '/label'] } )[0];
}

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

/** The themes design/list lists: some the feature ships - made up here, the real ones are read below - then the one the project made */
const THEMES = [
	{ slug : 'nino', name : 'Nino', description : 'The framework as it is', shipped : true },
	{ slug : 'magazine', name : 'Magazine', description : 'Lifted cards and a drop cap', shipped : true },
	{ slug : 'poster', name : 'Poster', description : 'An eyebrow over every title', shipped : true },
	{ slug : 'statement', name : 'Statement', description : 'Large numbers', shipped : true },
	{ slug : 'product', name : 'Product', description : 'Pills and tabular figures', shipped : true },
	{ slug : 'office', name : 'Office', description : 'A small root size', shipped : true },
	{ slug : 'mein-theme', name : 'My theme', description : 'The theme of this project', shipped : false },
];

/** The whole answer of design/list, with whatever a test wants different in it */
function listing( over ) {

	const roles = [ 'title', 'subtitle', 'text', 'button', 'code' ];
	const fonts = {};
	const defaults = {
		title 		: { family : 'open-sans', weight : 'bold', uppercase : false, size : '' },
		subtitle 	: { family : 'open-sans', weight : 'normal', uppercase : false, size : '' },
		text 			: { family : 'open-sans', weight : 'normal', uppercase : false, size : '' },
		button 		: { family : 'open-sans', weight : 'bold', uppercase : false, size : '' },
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
			part( 'header', 'frame', 'first', {
				first : { name : 'Plain bar', description : 'A bar with a rule under it' },
				second : { name : 'Centred', description : 'The logo in the middle' },
			} ),
			part( 'footer', 'frame', 'first', {
				first : { name : 'Plain footer', description : 'Two columns' },
				second : { name : 'Dark footer', description : 'On the dark ground' },
			} ),
			/*	A library made up for this test, with names of its own - the real one is read from the answer of the server, below: most of its
				templates answer to every knob of their tab, a template of the Buttons leaves the corners out, and the third template of the
				Content leaves the shadow out - what a library of somebody else's can hold, and what the screen has to draw all the same	*/
			part( 'content', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.content.slice() },
				second : { name : 'Box', description : 'A centred opening with tight lines', knobs : KNOBS.content.slice() },
				third : { name : 'Foreign', description : 'Large type, no shadow', knobs : without( KNOBS.content, [ 'shadow' ] ) },
			} ),
			part( 'buttons', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.buttons.slice() },
				third : { name : 'Square', description : 'No radius, spaced letters and a 2px line', knobs : without( KNOBS.buttons, [ 'corners' ] ) },
			} ),
			part( 'components', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.components.slice() },
				fourth : { name : 'Filled', description : 'Tinted fields without a line', knobs : KNOBS.components.slice() },
			} ),
		],
		tabs 			: JSON.parse( JSON.stringify( KINDS ) ),
		groups 		: JSON.parse( JSON.stringify( GROUPS ) ),
		knobs 		: knobs,
		steps 		: [ 'least', 'less', 'default', 'more', 'most' ],
		size 			: 0,
		width 		: 0,
		whitespace : 0,
		// The Theme tab: the theme the stored setup was taken from and the css of its own, the themes there are - the shipped ones, then the project's - and the active one as its file holds it
		theme 		: '',
		css 			: '',
		running 	: '',
		themes 		: THEMES.map( function( theme ) { return Object.assign( {}, theme ) } ),
		active 		: null,
		motion 		: { effect : '', strength : '', speed : '' },
		effects 	: [ 'zoom', 'zoom-out', 'slide-left', 'slide-right', 'slide-up', 'slide-down', 'blur', 'flip' ],
		strengths : [ 'soft', 'medium', 'hard' ],
		speeds 		: [ 'fast', 'medium', 'slow' ],
		roles 		: roles,
		fonts 		: fonts,
		families 	: {
			'open-sans' 		: { name : 'Open Sans', category : 'sans', system : false },
			'system-sans' 		: { name : 'System sans-serif', category : 'sans', system : true },
			'system-serif' 		: { name : 'System serif', category : 'serif', system : true },
			'system-mono' 		: { name : 'System monospace', category : 'mono', system : true },
		},
		options 	: {
			title 		: [ '', 'open-sans', 'system-sans', 'system-serif', 'system-mono' ],
			subtitle 	: [ '', 'open-sans', 'system-sans', 'system-serif', 'system-mono' ],
			text 			: [ '', 'open-sans', 'system-sans', 'system-serif', 'system-mono' ],
			button 		: [ '', 'open-sans', 'system-sans', 'system-serif', 'system-mono' ],
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

	const stage = { scrolls : [], turned : [], asked : [], listeners : [], frames : [], reachable : true, scrollY : 100, clearance : 0, tops : { type : 700, content : 300, cards : 900, buttons : 1200, components : 1500, colours : 450, footer : 4000 } };

	stage.style = element('style');
	// The room main keeps at its top for the fixed header, which the header template of the page sets: none until a test says
	stage.main = {};
	// The elements in view that carry a viewport animation, as the page's own script has marked them
	stage.animated = [];
	// The page's fonts, as the page reports them: null where it has no FontFaceSet to ask
	stage.fonts = null;
	stage.document = {
		body 							: {},
		documentElement 	: { scrollTop : 0, scrollHeight : 4000, scrollWidth : 1000 },
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
			// A turn of the wheel, handed on to the page: where the page stands and how big its window is are the test's
			scrollBy : function( options ) { stage.turned.push( options ) },
			scrollX : 0,
			innerHeight : 600,
			innerWidth : 1000,
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

	/*	The workbench's own questions (Nino.adminUi.choiceDialog()): each is kept with the options it was asked with and the element it stands in -
		drawn the way the kernel draws one: a body with a title, the text under the id its aria-describedby names, and the buttons - until a test
		answers it. The ones that stand open are found by byId(), as a browser finds an element of the document	*/
	const asks = [];

	function byId( id ) {
		if( id === 'design-form' )
			return form;
		const inPane = descendants( pane ).filter( function( el ) { return el.id === id } )[0];
		if( inPane !== undefined )
			return inPane;
		return asks.filter( function( ask ) { return ask.open === true } ).map( function( ask ) {
			return descendants( ask.root ).filter( function( el ) { return el.id === id } )[0];
		} ).filter( function( el ) { return el !== undefined } )[0] || null;
	}

	// What the frame shows: its own page, which the panel reaches into
	const stage = frameStage();

	const dc = {
		documentElement	: element('html'),
		body						: element('body'),
		createElement		: function( tag ) {
			const el = element( tag );
			// A link that is clicked is a download: where it pointed, what it is called, and whether it stood in the document while it was clicked
			if( tag === 'a' )
				el.click = function() { downloads.push( { href : el.href, download : el.download, mounted : el.parent !== null } ) };
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

	/*	What the Theme tab hands to the browser and takes from it: the links that were clicked, the blobs that were made and the addresses made of
		them and given back, and the readers a file was given to - each waits for a test to say how the read ends (see FileReader below)	*/
	const downloads = [];
	const blobs = [];
	const urls = { made : [], revoked : [] };
	const readers = [];

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
				// The code's sentence, with the params filled in, where the answer has one the workbench knows; else "(status) message"
				errorText : function( status, response, key ) {
					const body = response !== null && typeof response === 'object' ? response : {};
					if( typeof body.code === 'string' && TEXT['/_admin/error/'+ body.code] !== undefined ) {
						let out = TEXT['/_admin/error/'+ body.code];
						( Array.isArray( body.params ) === true ? body.params : [] ).forEach( function( value ) { out = out.replace( /%[sdn]/, function() { return String( value ) } ) } );
						return out;
					}
					return '('+ status+ ') '+ ( body.error ? body.error : Nino.content.getText( key ) );
				},
			},
			// The workbench's small builders, as it builds them: an element with a class and a text, a button, a paragraph of hint and a labelled text input
			el					: function( tag, className, text ) {
				const el = element( tag );
				if( typeof className === 'string' && className !== '' )
					el.className = className;
				if( text !== undefined )
					el.textContent = text;
				return el;
			},
			button			: function( text, onClick, options ) {
				options = options || {};
				const button = element('button');
				button.type = 'button';
				if( options.className )
					button.className = options.className;
				button.textContent = text;
				if( options.name ) {
					button.title = options.name;
					button.setAttribute( 'aria-label', options.name );
				}
				button.disabled = options.disabled === true;
				if( options.id )
					button.id = options.id;
				if( typeof onClick === 'function' )
					button.addEventListener( 'click', onClick );
				return button;
			},
			hint				: function( text, kind ) {
				const hint = element('p');
				hint.className = kind === 'error' ? 'nino-admin-hint nino-admin-error' : 'nino-admin-hint';
				hint.textContent = text;
				return hint;
			},
			textField		: function( options ) {
				const field = element('label');
				field.className = options.className || 'nino-admin-field';
				const name = element('span');
				name.textContent = options.label === undefined || options.label === null ? '' : options.label;
				field.appendChild( name );
				const input = element('input');
				input.className = 'nino-admin-input';
				input.type = options.type || 'text';
				if( options.value !== undefined && options.value !== null )
					input.value = options.value;
				if( options.placeholder )
					input.placeholder = options.placeholder;
				if( typeof options.maxLength === 'number' )
					input.maxLength = options.maxLength;
				if( options.id )
					input.id = options.id;
				input.addEventListener( 'input', function() {
					if( typeof options.onInput === 'function' )
						options.onInput( input.value );
				} );
				field.appendChild( input );
				if( options.hint ) {
					const hint = element('small');
					hint.className = 'nino-admin-hint';
					hint.textContent = options.hint;
					field.appendChild( hint );
				}
				return field;
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
			/* One question at a time, as the kernel has it: a second while one stands is refused. Where the test says there is no <dialog> the
			   question is a confirm() - OK for the primary choice, Cancel for the secondary one */
			choiceDialog : function( options ) {
				if( asks.some( function( ask ) { return ask.open === true } ) === true )
					return false;
				const root = element('dialog');
				root.className = 'nino-admin-dialog';
				const body = element('div');
				body.className = 'nino-admin-dialog-body';
				if( options.title ) {
					const title = element('h2');
					title.className = 'nino-admin-dialog-title';
					title.textContent = options.title;
					body.appendChild( title );
				}
				const text = element('p');
				text.id = 'nino-admin-choice-text';
				text.textContent = options.message;
				body.appendChild( text );
				const buttons = element('div');
				buttons.className = 'nino-admin-dialog-actions';
				const ask = { options : options, root : root, open : true, text : text, buttons : buttons,
					choose : function( value ) { ask.open = false; options.onChoose( value ) } };
				options.choices.forEach( function( choice ) {
					const button = element('button');
					button.className = 'nino-admin-btn-'+ choice.kind;
					button.textContent = choice.label;
					button.addEventListener( 'click', function() { ask.choose( choice.value ) } );
					buttons.appendChild( button );
				} );
				body.appendChild( buttons );
				root.appendChild( body );
				asks.push( ask );
				return true;
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

	/*	What a download is made of: a Blob of the text, an address for it that is revoked when the link has been clicked, and - for an import - a
		FileReader that is given a file and waits for the test to say how the read ends: finish( text ) is a read that worked, fail() one that did not	*/
	sandbox.Blob = function( parts, options ) {
		this.parts = parts;
		this.type = ( options || {} ).type;
		blobs.push( this );
	};
	sandbox.URL = {
		createObjectURL : function( blob ) { urls.made.push( blob ); return 'blob:design/'+ urls.made.length },
		revokeObjectURL : function( url ) { urls.revoked.push( url ) },
	};
	sandbox.FileReader = function() {
		this.listeners = {};
		this.result = null;
		this.file = null;
		readers.push( this );
	};
	sandbox.FileReader.prototype.addEventListener = function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) };
	sandbox.FileReader.prototype.readAsText = function( file ) { this.file = file };
	sandbox.FileReader.prototype.finish = function( text ) { this.result = text; ( this.listeners.load || [] ).forEach( function( fn ) { fn() } ) };
	sandbox.FileReader.prototype.fail = function() { ( this.listeners.error || [] ).forEach( function( fn ) { fn() } ) };

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
	design._takeStored( design._data );
	design._ready	= true;

	// The tab the screen is drawn on: Global, which the sections of this test were written on - { tab : 'default' } leaves it to the panel, which opens on the Theme
	if( ( shell || {} ).tab !== 'default' )
		design._tab = ( shell || {} ).tab || 'global';

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
		/** The questions of the workbench that were asked, in order: { options, root, open, text, buttons, choose( value ) } */
		asks		: asks,
		stage		: stage,
		/** The Theme tab's dealings with the browser: the links clicked, the blobs made, the addresses made of them and revoked, and the readers a file was given to - and the body of the document, where a link stands while it is clicked */
		docBody		: dc.body,
		downloads	: downloads,
		blobs			: blobs,
		urls			: urls,
		readers		: readers,
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
		/** Move a knob of a group of the open tab, the way a hand does: open the group by the word on its tab, then press the button */
		moveIn : function( group, label, value ) {
			this.group( group );
			this.move( label, value );
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
		/** Open another group of the knobs of the open tab, by the word on its tab - the strip is in the column, under the select of the template */
		group : function( label ) {
			byTag( byClass( form, 'design-groups' )[0] || element('div'), 'button' ).filter( function( button ) {
				return button.textContent === label;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** The strip of the groups of the open tab, null where the tab has none */
		groups : function() { return byClass( form, 'design-groups' )[0] || null },
		/** The knob rows of the open tab: the rows of every group of it one after the other, where it has groups - the first group is open again at the end */
		allRows : function() {
			const strip = byClass( form, 'design-groups' )[0];
			if( strip === undefined )
				return byClass( form, 'design-knob-row' );
			const buttons = byTag( strip, 'button' );
			let all = [];
			buttons.forEach( function( button ) { button.fire('click'); all = all.concat( byClass( form, 'design-knob-row' ) ) } );
			buttons[0].fire('click');
			return all;
		},
		/** What the knob rows of the open tab say, every group of it one after the other (see lit()) */
		litAll : function() {
			const strip = byClass( form, 'design-groups' )[0];
			if( strip === undefined )
				return this.lit();
			const screen = this;
			const buttons = byTag( strip, 'button' );
			let all = [];
			buttons.forEach( function( button ) { all = all.concat( ( button.fire('click'), screen.lit() ) ) } );
			buttons[0].fire('click');
			return all;
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

	if( screen.byId('design-themes') !== null )
		return 'theme';

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
// so the screen under it draws no heading of its own. The strip of the eight tabs
// stands in that row beside the name, where the Features panel's does: it
// switches the column of controls and nothing else, and the frame beside the
// column stays on every tab

console.log( 'The head' );

const headed = panel();
const strip = headed.headStrips()[0] || null;
const tabs = strip === null ? [] : byTag( strip, 'button' );

check( 'the strip has eight tabs - Theme, Global, Fonts, Typefaces, Content, Buttons, Components and Colours, in that order: the theme is the whole of the setup and stands first, the loader after the Fonts it lists its families in',
	headed.design.TABS.join() === TABS.join() && tabs.map( function( tab ) { return tab.textContent } ).join() === 'Theme,Global,Fonts,Typefaces,Content,Buttons,Components,Colours'
	&& TEXT['/_admin/design/tab/theme'] === 'Theme' );
check( '...a tablist of tabs, the open one selected and the others not - the frame goes to the top of the page for the Theme tab as it does for Global',
	strip !== null && strip.getAttribute('role') === 'tablist' && tabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' } )
	&& tabs.map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'false,true,false,false,false,false,false,false'
	&& headed.design._tab === 'global' && headed.design.ANCHORS.theme === 'global' && headed.design.ANCHORS.theme === headed.design.ANCHORS.global );

/*	Left to itself the panel opens on the Theme tab: the first thing a person does is pick a theme. Every other screen of this test is drawn on Global, which
	its sections were written on (see panel())	*/
const defaulted = panel( {}, { tab : 'default' } );
const defaultedStrip = defaulted.headStrips()[0];

check( 'the panel opens on the Theme tab - the one selected in the strip and the one drawn in the column: its cards, and none of the frames the Global tab has',
	defaulted.design._tab === 'theme' && byTag( defaultedStrip, 'button' ).map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false,false,false,false,false,false,false'
	&& defaulted.byId('design-themes') !== null && defaulted.byId('design-theme-current') !== null && defaulted.byId('design-set-header') === null );
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
	&& byTag( shownStrip, 'button' )[7].getAttribute('aria-selected') === 'true' && byTag( shownStrip, 'button' )[1].getAttribute('aria-selected') === 'false'
	&& tabOf( headed ) === 'colours' && headed.byId('design-frame') !== null );

/*	The keyboard is the reason the strip is not drawn again on a switch. An
	arrow key moves the focus to the next tab and opens it; a strip drawn anew
	with the column took that tab out of the document, and the focus fell onto
	the page, so the next arrow key had nowhere to start from	*/
const keyed = panel();
const keyTabs = byTag( keyed.headStrips()[0] || element('div'), 'button' );

focused = null;
if( keyTabs.length === 8 )
	press( keyTabs[1], 'ArrowRight' );

check( 'an arrow key on the strip opens the next tab and the focus stays on the tab it moved to, in the head',
	keyTabs.length === 8 && focused === keyTabs[2] && keyTabs[2].parent === keyed.headStrips()[0]
	&& keyTabs[2].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'fonts' );

if( keyTabs.length === 8 )
	press( keyTabs[2], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === 8 && focused === keyTabs[1] && keyTabs[1].parent === keyed.headStrips()[0]
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && tabOf( keyed ) === 'global' );

if( keyTabs.length === 8 )
	press( keyTabs[1], 'ArrowLeft' );

check( '...to the first tab, which is the Theme, with the list of the themes in the column', keyTabs.length === 8 && focused === keyTabs[0] && tabOf( keyed ) === 'theme' );

if( keyTabs.length === 8 )
	press( keyTabs[0], 'ArrowLeft' );

check( '...and from the first tab the arrow key wraps round to the last', keyTabs.length === 8 && focused === keyTabs[7] && tabOf( keyed ) === 'colours' );

if( keyTabs.length === 8 )
	press( keyTabs[7], 'ArrowRight' );

check( '...and from the last it wraps round to the first', keyTabs.length === 8 && focused === keyTabs[0] && tabOf( keyed ) === 'theme' );

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
check( '...and the page in it is a picture, not a page to use: the frame is inert and out of the tab order - a walk of the keyboard would cross every link of the specimen before it reached the bar - and its title, which names what it shows, stays',
	column.byId('design-frame').getAttribute('inert') === '' && column.byId('design-frame').getAttribute('tabindex') === '-1'
	&& column.byId('design-frame').title === TEXT['/_admin/design/label/preview'] && column.byId('design-frame').title !== '' );

/*	An inert frame lets the pointer through to the stage under it, so the wheel is turned on the page in the frame by the panel: by the unit the wheel counts in
	- pixels, lines of 16, pages of the window - and only while the page can go that way. At its end, or at its top, the wheel is left alone and goes on to the panel,
	as it did when the frame took it itself	*/
const wheeled = function( screen, delta ) {
	const event = Object.assign( { deltaX : 0, deltaY : 0, deltaMode : 0, stopped : false, preventDefault : function() { event.stopped = true } }, delta );
	( byClass( screen.form, 'design-stage' )[0].listeners.wheel || [] ).forEach( function( fn ) { fn( event ) } );
	return event;
};
const turning = panel();
const turnsOf = function() { return JSON.stringify( turning.stage.turned.splice( 0 ) ) };
const pixels = wheeled( turning, { deltaY : 120 } );

check( 'a turn of the wheel over the preview is turned on the page in the frame - and taken: the panel does not scroll as well',
	turnsOf() === JSON.stringify( [ { top : 120, left : 0, behavior : 'instant' } ] ) && pixels.stopped === true );
wheeled( turning, { deltaY : 3, deltaMode : 1 } );
wheeled( turning, { deltaY : 1, deltaMode : 2 } );
check( '...by lines of 16 pixels and by pages of the window - the window of the page, 600 pixels high here',
	turnsOf() === JSON.stringify( [ { top : 48, left : 0, behavior : 'instant' }, { top : 600, left : 0, behavior : 'instant' } ] ) );
wheeled( turning, { deltaX : -40 } );
check( '...across as well as down, and not across where the page is at its left edge',
	turnsOf() === '[]' );
turning.stage.scrollY = 3400;
const atEnd = wheeled( turning, { deltaY : 100 } );
turning.stage.scrollY = 0;
const atTop = wheeled( turning, { deltaY : -100 } );
check( 'at the end of the page the wheel down is left alone, and so is the wheel up at its top - it goes on to the panel, where it did when the frame took it itself',
	turnsOf() === '[]' && atEnd.stopped === false && atTop.stopped === false );
turning.stage.scrollY = 100;
turning.stage.reachable = false;
check( '...and a frame that cannot be reached is not turned and raises nothing', threw( function() { wheeled( turning, { deltaY : 100 } ) } ) === false && turnsOf() === '[]' );

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
steady.moveIn( 'Variants', 'Quiet variant, text size', 1 );

check( 'a tab that is opened and a knob that is moved change nothing of them: the same line with the same words, the same chip with its word',
	steady.line() === steadyLine && steady.chip() === steadyChip && steady.line().textContent === 'Draft saved.' && steady.line().title === 'Draft saved.'
	&& steady.chip().textContent === 'Up to date' && steady.chip().dataset.state === 'current' );

const told = panel( { notes : [ 'there is no part "nowhere" - dropped' ] } );

check( 'what the server dropped or replaced on the way is told at the top of the column, over the open tab',
	byClass( told.byId('design-controls'), 'nino-admin-error' ).map( function( line ) { return line.textContent } ).join() === 'there is no part "nowhere" - dropped'
	&& told.byId('design-controls').children.indexOf( byClass( told.byId('design-controls'), 'nino-admin-error' )[0] ) === 0
	&& told.byId('design-controls').children[1] === told.body() );

/*	A note that carries a code is said in the words of the workbench, as a refusal is: the sentence of its code with the params in it, the part and the template that
	stands in its place by the names the screen has for them - and a note that is a sentence is the sentence. What the screen reads again - a theme that is loaded, a draft
	that is reset - is listed anew: the notes are those of what was read	*/
const NOTE_THEME = { error : 'the theme "magazine" is not there - none is active', code : 'design_theme_missing', params : [ 'magazine' ] };
const noteSet = function( part, wanted, used ) {
	return { error : '"'+ part+ '" names the set "'+ wanted+ '", which is not in the library - using "'+ used+ '"', code : 'design_set_missing', params : [ part, wanted, used ] };
};
const noteLines = function( screen ) {
	return byClass( screen.byId('design-controls'), 'nino-admin-error' ).filter( function( line ) { return line.parent === screen.byId('design-controls') } ).map( function( line ) { return line.textContent } );
};
const codedNotes = panel( { notes : [ NOTE_THEME, noteSet( 'header', 'v9', 'first' ), noteSet( 'content', 'v3', 'second' ), 'there is no part "nowhere" - dropped' ] } );

check( 'a note that carries a code is said in the words of the panel - the sentence of the code with its params in it, the part and the template that stands in by the names the screen has for them - and a note that is a sentence is the sentence',
	JSON.stringify( noteLines( codedNotes ) ) === JSON.stringify( [
		say( '/_admin/error/design_theme_missing', 'magazine' ),
		say( '/_admin/error/design_set_missing', TEXT['/_admin/design/part/header'], 'v9', 'Plain bar' ),
		say( '/_admin/error/design_set_missing', TEXT['/_admin/design/part/content'], 'v3', 'Box' ),
		'there is no part "nowhere" - dropped',
	] ) && noteLines( codedNotes ).every( function( line ) { return line.indexOf('%s') === -1 && line.indexOf('design_') === -1 } ) );
check( '...as many lines as the notes, each over the tab and under the strip, as the one that is a sentence was',
	byClass( codedNotes.byId('design-controls'), 'nino-admin-error' ).length === 4 && codedNotes.byId('design-controls').children[4] === codedNotes.body() );
check( '...and a note that is a sentence is said as it is',
	codedNotes.design._noteText( 'a sentence' ) === 'a sentence' );

console.log('');

// --- Global: the templates, the page and its animation ------------------------------

console.log( 'Global' );

const global = panel();
const setSelect = function( name ) { return global.byId('design-set-'+ name) };

check( 'the Global tab offers the two frames as two selects - the header and the footer, each over its catalogue, on the one that is chosen - and none of the three templates of the knob tabs',
	setSelect('header') !== null && setSelect('footer') !== null
	&& JSON.stringify( offered( setSelect('header') ) ) === JSON.stringify( [ [ 'first', 'Plain bar' ], [ 'second', 'Centred' ] ] )
	&& JSON.stringify( offered( setSelect('footer') ) ) === JSON.stringify( [ [ 'first', 'Plain footer' ], [ 'second', 'Dark footer' ] ] )
	&& [ 'header', 'footer' ].map( function( name ) { return shown( setSelect( name ) ) } ).join() === 'first,first'
	&& setSelect('content') === null && setSelect('buttons') === null && setSelect('components') === null
	&& byTag( global.body(), 'select' ).filter( function( select ) { return /^design-set-/.test( select.id ) } ).length === 2 );
check( '...named in the words of the panel, with the description of the chosen one under it - and no sentence under them about the templates that are chosen elsewhere',
	byTag( global.body(), 'label' ).map( function( label ) { return label.textContent } ).slice( 0, 2 ).join() === 'Header template,Footer template'
	&& byTag( setSelect('header').parent, 'small' )[0].textContent === 'A bar with a rule under it'
	&& byTag( setSelect('header').parent.parent, 'p' ).length === 0 && byClass( global.body(), 'design-note' ).length === 0 );

const headerField = setSelect('header').parent;
focused = null;
choose( setSelect('header'), 'second' );

check( 'choosing another one is the selection on screen, and the note under it follows the choice',
	global.design._edit.parts.header === 'second' && byTag( setSelect('header').parent, 'small' )[0].textContent === 'The logo in the middle'
	&& global.byId('design-reset').hidden === false );
check( '...a frame changes the document and not the card: the field and its select stand where they were, and the focus is not moved',
	setSelect('header').parent === headerField && descendants( global.pane ).indexOf( setSelect('header') ) !== -1 && focused === null );

/*	The root size, the width and the whitespace of the page are strips of five, like the knobs of the tabs: a card of their own between
	the frames and the animation, with a name and the note under it and the five numbers -2 to +2 - no select, no field
	grid, and no word for a position, which a strip does not have	*/
const globalCards = global.body().children;
const strips = globalCards[1] || element('div');
const sizeStrip = 'Page, root size';
const widthStrip = 'Page, width';
const whitespaceStrip = 'Page, whitespace';
const stripLabels = function( label ) { return global.steps( label ).map( function( button ) { return button.textContent } ).join() };

check( 'the root size, the width and the whitespace of the page are three strips in a card of their own, between the frames and the animation, the way the knob rows of a tab stand in theirs: Page, root size, Page, width and Page, whitespace',
	globalCards.length === 3 && hasClass( globalCards[0], 'design-form-section' ) && hasClass( strips, 'design-block' ) && hasClass( strips, 'nino-admin-card' )
	&& hasClass( globalCards[2], 'design-form-section' ) && byTag( globalCards[2], 'select' ).length === 3
	&& strips.children.length === 3 && strips.children.every( function( row ) { return hasClass( row, 'design-knob-row' ) } )
	&& strips.children.map( rowName ).join() === 'Page, root size,Page, width,Page, whitespace'
	&& global.byId('design-size') === null && global.byId('design-pagewidth') === null && byTag( strips, 'select' ).length === 0 && byClass( strips, 'design-form-grid' ).length === 0 );
check( '...each over its note, with the five buttons of a scale: the numbers from the middle, -2 to +2, written with the minus sign of arithmetic (U+2212) and not a hyphen - the title of a button is its label - and the middle one pressed to begin with',
	stripLabels( sizeStrip ) === SIGNED.join() && stripLabels( widthStrip ) === SIGNED.join() && stripLabels( whitespaceStrip ) === SIGNED.join() && stripLabels( sizeStrip ).indexOf('-') === -1
	&& global.steps( sizeStrip ).concat( global.steps( widthStrip ), global.steps( whitespaceStrip ) ).every( function( button ) { return button.title === button.textContent && button.disabled === false } )
	&& global.pressed( sizeStrip ) === 'false,false,true,false,false' && global.pressed( widthStrip ) === 'false,false,true,false,false' && global.pressed( whitespaceStrip ) === 'false,false,true,false,false'
	&& global.steps( sizeStrip ).map( function( button ) { return button.dataset.value } ).join() === '-2,-1,0,1,2'
	&& global.steps( whitespaceStrip ).map( function( button ) { return button.dataset.value } ).join() === '-2,-1,0,1,2' );
check( '...a group of buttons as the knob rows are, named by what it sets, and the notes under the names say where the ends are: 85 % \u2194 145 % of the browser\'s own size, 48 rem \u2194 to the edge, and 75 % \u2194 200 % of the distances',
	strips.children.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& byClass( global.knobRow( sizeStrip ), 'design-knob-steps' )[0].getAttribute('aria-label') === 'Page, root size'
	&& byTag( global.knobRow( sizeStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/size'] && TEXT['/_admin/design/hint/size'] === '85 % \u2194 145 % of the browser\'s own size, never a fixed number of pixels'
	&& byTag( global.knobRow( widthStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/pagewidth'] && TEXT['/_admin/design/hint/pagewidth'] === '48 rem \u2194 to the edge'
	&& byClass( global.knobRow( whitespaceStrip ), 'design-knob-steps' )[0].getAttribute('aria-label') === 'Page, whitespace'
	&& byTag( global.knobRow( whitespaceStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/whitespace'] && TEXT['/_admin/design/hint/whitespace'] === '75 % \u2194 200 % of the distances' );
check( '...the script keeps no list of the widths and no step to read a word off any more: PAGE_WIDTHS and _stepOf are gone, and a position of a scale reaches two to each side, a choice one',
	global.design.PAGE_WIDTHS === undefined && global.design._stepOf === undefined && JSON.stringify( global.design.RANGE ) === JSON.stringify( { scale : 2, choice : 1 } )
	&& JSON.stringify( global.design._positions('scale') ) === '[-2,-1,0,1,2]' && JSON.stringify( global.design._positions('choice') ) === '[-1,0,1]' );

global.move( sizeStrip, 2 );
global.move( widthStrip, 1 );
global.move( whitespaceStrip, -1 );

check( 'pressing a button stores the number, as a number: +2 for the size, +1 for the width and -1 for the whitespace, and the button is lit',
	global.design._edit.size === 2 && global.design._edit.width === 1 && global.design._edit.whitespace === -1
	&& typeof global.design._edit.size === 'number' && typeof global.design._edit.width === 'number' && typeof global.design._edit.whitespace === 'number'
	&& global.pressed( sizeStrip ) === 'false,false,false,false,true' && global.pressed( widthStrip ) === 'false,false,false,true,false' && global.pressed( whitespaceStrip ) === 'false,true,false,false,false' );
check( '...each of the five can be pressed, for the size, the width and the whitespace, and each is what the model keeps as it was pressed - -2 stores -2, +2 stores 2',
	[ -2, -1, 0, 1, 2 ].every( function( position ) {
		global.move( sizeStrip, position );
		global.move( widthStrip, position );
		global.move( whitespaceStrip, position );
		return global.design._edit.size === position && global.design._edit.width === position && global.design._edit.whitespace === position
			&& global.pressed( sizeStrip ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join()
			&& global.pressed( widthStrip ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join()
			&& global.pressed( whitespaceStrip ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join();
	} ) );
check( '...and a strip of Global is a change like any other: it marks the selection as not stored, and is what a preview carries',
	global.byId('design-reset').hidden === false && ( global.design._previewNow(), global.requests[global.requests.length - 1].payload.size === 2 && global.requests[global.requests.length - 1].payload.width === 2
		&& global.requests[global.requests.length - 1].payload.whitespace === 2 ) );

global.move( sizeStrip, 2 );
global.move( widthStrip, 1 );
global.move( whitespaceStrip, -1 );

const effect = global.byId('design-motion-effect');
const strength = global.byId('design-motion-strength');
const speed = global.byId('design-motion-speed');

check( 'the animation is three selects: the effect - which starts on what Nino.css does by itself - the strength and the speed',
	JSON.stringify( offered( effect ) ) === JSON.stringify( [ [ '', 'Nino\'s default' ], [ 'zoom', 'Zoom' ], [ 'zoom-out', 'Zoom out' ], [ 'slide-left', 'Slides left' ], [ 'slide-right', 'Slides right' ], [ 'slide-up', 'Slides up' ], [ 'slide-down', 'Slides down' ], [ 'blur', 'Blur' ], [ 'flip', 'Flip' ] ] )
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

check( 'the tab is drawn again from what is chosen, and its controls show every choice of it - the two frames, the size, the width, the whitespace and the animation - with nothing under the frame to list them',
	[ 'header', 'footer' ].map( function( name ) { return shown( global.byId('design-set-'+ name) ) } ).join() === 'second,first'
	&& global.pressed( sizeStrip ) === 'false,false,false,false,true' && global.pressed( widthStrip ) === 'false,false,false,true,false' && global.pressed( whitespaceStrip ) === 'false,true,false,false,false'
	&& global.design._edit.size === 2 && global.design._edit.width === 1 && global.design._edit.whitespace === -1
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
// three words that knob has for them - and with the icon of what it sets. The Content
// has fourteen of them, which it shows in four groups: a strip of tabs under the select,
// and under it the rows of the group that is open (see 'The groups of the knobs'). A knob
// the chosen template does not answer to stands where it always does, greyed out,
// with the line that says whose decision that is

console.log( 'The knob tabs' );

const moved = listing().knobs;
moved.content.shadow = 1;
moved.buttons.corners = -1;

const rows = panel( { knobs : moved } );
rows.tab('Content');

const contentRows = byClass( rows.form, 'design-knob-row' );

const groupStrip = rows.groups();

check( 'the Content tab shows its fourteen knobs in four groups: a strip of four tabs under the select of the template - Hero, Section, Card and Variants - and under it the rows of the first group only, the title and the subtitle of the hero',
	tabOf( rows ) === 'content' && groupStrip !== null && byTag( groupStrip, 'button' ).map( function( tab ) { return tab.textContent } ).join() === 'Hero,Section,Card,Variants'
	&& contentRows.length === 2 && contentRows.map( rowName ).join() === 'Title (Hero), text size,Subtitle (Hero), text size'
	&& contentRows.map( function( row ) { return row.dataset.knob } ).join() === GROUPS.content.hero.join() );

// ...and the other groups show the rest, one after the other, each in its own order: the groups are walked, and the first is open again at the end
const everyContentRow = rows.allRows();

check( '...the other three show the rest - fourteen rows in all, named in the panel\'s words and in the order the groups have them',
	everyContentRow.length === 14 && everyContentRow.map( function( row ) { return row.dataset.knob } ).join() === SHOWN.join()
	&& everyContentRow.map( rowName ).join() === SHOWN.map( function( knob ) { return TEXT['/_admin/design/knob/content/'+ knob+ '/label'] } ).join() );

/*	The template of a tab is chosen on the tab: the same select as it was on
	Global, with the same id, at the top of the card - and the rows after it	*/
const templateFirst = [ [ 'Content', 'content', 2 ], [ 'Buttons', 'buttons', 4 ], [ 'Components', 'components', 6 ] ].every( function( entry ) {

	rows.tab( entry[0] );

	const card = rows.byId('design-knobs');
	const select = rows.byId('design-set-'+ entry[1]);
	// The rows of a tab with groups stand in a box of their own under the strip, the rows of the others in the card itself
	const grouped = rows.groups() !== null;
	const box = grouped === true ? rows.byId('design-knob-rows') : card;
	const shownRows = byClass( card, 'design-knob-row' );

	return select !== null && card.children[0] === select.parent && hasClass( card.children[0], 'design-field' ) && grouped === ( entry[1] === 'content' )
		&& card.children.length === ( grouped === true ? 3 : entry[2] + 1 ) && ( grouped === false || ( card.children[1] === rows.groups() && card.children[2] === box ) )
		&& shownRows.length === entry[2] && shownRows.every( function( row ) { return row.parent === box } )
		&& shownRows.every( function( row, at ) { return row.dataset.knob === ( grouped === true ? GROUPS.content.hero : KNOBS[entry[1]] )[at] } )
		&& byTag( card.children[0], 'label' )[0].textContent === TEXT['/_admin/design/part/'+ entry[1]] && tabOf( rows ) === entry[1]
		&& shown( select ) === 'first' && byTag( card.children[0], 'small' )[0].textContent === 'The framework as it is';
} );
rows.tab('Content');

check( 'each of the three knob tabs offers its template first, in a select named in the panel\'s words with the description of the chosen one under it, and the rows of its knobs after it',
	templateFirst === true && JSON.stringify( offered( rows.byId('design-set-content') ) ) === JSON.stringify( [ [ 'first', 'Nino' ], [ 'second', 'Box' ], [ 'third', 'Foreign' ] ] ) );
check( '...each with a note beside its name, and a strip of five numbers: -2 to +2, written with the minus of arithmetic - the same on every knob of a scale, whatever it sets',
	everyContentRow.every( function( row ) {
		const knob = row.dataset.knob;
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

	worded.allRows().forEach( function( row ) {

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

check( 'a scale is five numbers and a choice the three words of the knob, each capitalised - twenty-two scales and two choices, twenty-four rows, those of every group of the Content among them - with the title of each button its label, and the values on them -2 to +2 and -1 to +1'
	+ ( wordProblems.length === 0 ? '' : ' - not so for '+ wordProblems.join(' | ') ), wordRows === 24 && scaleRows === 22 && choiceRows === 2 && wordProblems.length === 0 );
check( '...the middle word of a choice is its own, as it was: Rows between Off and Rows with a line, Above between In the row and Floating - and the two stand at it to begin with',
	CHOICE_WORDS['components/zebra'].split(',')[1] === 'Rows' && CHOICE_WORDS['components/labels'].split(',')[1] === 'Above'
	&& ( function() { worded.tab('Components'); return worded.pressed('Table, stripes') === 'false,true,false' && worded.pressed('Field, labels') === 'false,true,false' } )() );
const heroPressed = rows.pressed('Title (Hero), text size');
rows.group('Card');
const shadowPressed = rows.pressed('Card, shadow');
rows.group('Hero');

check( '...a group of buttons, not a tablist - the one it stands at is pressed and the others are not: the middle for a knob nobody moved, the +1 the shadow stands at',
	everyContentRow.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' } )
	&& heroPressed === 'false,false,true,false,false' && shadowPressed === 'false,false,false,true,false' );
check( 'there is no heading and no hint over the rows any more: the select says which template these knobs are the knobs of',
	byClass( rows.byId('design-knobs'), 'design-section-label' ).length === 0 && byTag( rows.byId('design-knobs'), 'strong' ).length === 0
	&& byTag( rows.byId('design-knobs'), 'small' ).filter( function( small ) { return small.parent === rows.byId('design-knobs').children[0] } ).length === 1 );
check( 'a template that answers to every knob greys none of them out, in whichever group',
	byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& rows.allRows().every( function( row ) { return hasClass( row, 'design-knob-row--off' ) === false && byTag( row, 'button' ).every( function( button ) { return button.disabled === false } ) } )
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
/** What the rows of a knob tab say, from the positions its knobs stand at: the label of each and the label of the button lit - the number of a scale, the word of a choice. The knobs in the order the tab shows them in, the Content's in its groups' */
function knobWords( tab, positions ) {
	return ( tab === 'content' ? SHOWN : KNOBS[tab] ).filter( function( knob ) { return positions[knob] !== undefined } ).map( function( knob ) {
		return [ TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/label'], KINDS[tab][knob] === 'scale' ? SIGNED[positions[knob] + 2] : TEXT['/_admin/design/knob/'+ tab+ '/'+ knob+ '/'+ [ 'less', 'default', 'more' ][positions[knob] + 1]] ];
	} );
}

const litContent = rows.litAll();

check( 'the rows say, for every knob the template answers to, the number of the position it stands at - read off the button that is lit, group after group, with nothing under the frame to say it again',
	JSON.stringify( litContent ) === JSON.stringify( knobWords( 'content', { 'hero-title' : -2, 'hero-text' : 2, 'section-title' : 0, 'section-subtitle' : 0, 'section-space' : 0, 'section-line' : 0, 'card-title' : 0, 'card-subtitle' : 0, 'card-space' : 0, corners : 0, border : 0, shadow : 1, quiet : 0, loud : 0 } ) )
	&& rows.byId('design-summary') === null );
check( '...in numbers: \u22122 for the title of the hero, +2 for its subtitle, 0 for the knobs nobody moved and +1 for the shadow of a card',
	litContent.map( function( row ) { return row.join(': ') } ).join(' | ')
		=== 'Title (Hero), text size: \u22122 | Subtitle (Hero), text size: +2 | Title (Section), text size: 0 | Subtitle (Section), text size: 0 | Section, space: 0 | Section, rule: 0 | Title (Card), text size: 0 | Subtitle (Card), text size: 0 | Card, space: 0 | Card, corners: 0 | Card, line: 0 | Card, shadow: +1 | Quiet variant, text size: 0 | Loud variant, text size: 0' );

/*	Another template brings its own knobs, and the ones the last one had are
	not this one's: a position the new template does not answer to goes back to
	the middle, the way the server holds it anyway - so the screen never shows a
	setup the stored one could not be	*/
const oldSelect = rows.byId('design-set-content');
const oldStrip = rows.groups();
const oldRows = byClass( rows.form, 'design-knob-row' );
focused = null;
choose( oldSelect, 'third' );

check( 'choosing a template that leaves a knob out - one of somebody else\'s library: those the feature ships answer to all fourteen - puts the position of that knob back in the middle, and leaves the others where they were',
	rows.design._edit.parts.content === 'third' && rows.design._edit.knobs.content.shadow === 0
	&& rows.design._edit.knobs.content['hero-title'] === -2 && rows.design._edit.knobs.content['hero-text'] === 2 );
check( '...the rows of the tab are drawn again - two new ones for the two knobs of the group that is open, the hero - and the select is a new one on the template that was chosen, which has the focus afterwards; the strip of the groups is a new one as well, with the same group open',
	oldRows.length === 2 && oldRows.every( function( row ) { return descendants( rows.pane ).indexOf( row ) === -1 } )
	&& byClass( rows.form, 'design-knob-row' ).length === 2 && descendants( rows.pane ).indexOf( oldSelect ) === -1
	&& rows.byId('design-set-content') !== null && rows.byId('design-set-content') !== oldSelect && shown( rows.byId('design-set-content') ) === 'third'
	&& focused === rows.byId('design-set-content') && tabOf( rows ) === 'content'
	&& rows.groups() !== null && rows.groups() !== oldStrip && descendants( rows.pane ).indexOf( oldStrip ) === -1
	&& byTag( rows.groups(), 'button' ).map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false,false,false' );
check( '...and the line under the select describes the template that was chosen, and the select stands on it',
	byTag( rows.byId('design-set-content').parent, 'small' )[0].textContent === 'Large type, no shadow' && shown( rows.byId('design-set-content') ) === 'third' );

const foreignRows = rows.allRows();
rows.group('Card');
const foreign = byClass( rows.form, 'design-knob-row' );
const shadow = rows.knobRow('Card, shadow');

check( 'on the tab the knob the template does not answer to is still a row, greyed out - not hidden, so the tab is the same screen for every template: fourteen rows in the groups, the six of the Card among them, and one of the fourteen grey',
	foreignRows.length === 14 && foreignRows.filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) } ).length === 1
	&& foreign.length === 6 && shadow !== null && hasClass( shadow, 'design-knob-row--off' )
	&& foreign.filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) } ).length === 1 );
check( '...its five buttons switched off and none of them pressed',
	byTag( shadow, 'button' ).length === 5 && byTag( shadow, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& byTag( rows.knobRow('Card, corners'), 'button' ).every( function( button ) { return button.disabled === false } ) );
check( '...and the line under its name says whose decision that is, in place of its note',
	( byTag( shadow, 'small' )[0] || {} ).textContent === 'This template does not answer to “Card, shadow”.'
	&& ( byTag( rows.knobRow('Card, corners'), 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/content/corners/note']
	&& shown( rows.byId('design-set-content') ) === 'third' );

const litForeign = rows.litAll();

check( 'a row that is greyed out says no position, since it compiles nothing: thirteen of the fourteen knobs have a button that is lit, and the shadow is not one of them',
	litForeign.length === 13 && litForeign.every( function( row ) { return row[0] !== 'Card, shadow' } ) );

choose( rows.byId('design-set-content'), 'first' );
rows.group('Card');

check( 'choosing the template that has it again takes the grey away, with the knob in the middle',
	byClass( rows.form, 'design-knob-row--off' ).length === 0 && rows.design._edit.knobs.content.shadow === 0
	&& rows.pressed('Card, shadow') === 'false,false,true,false,false' );

rows.group('Hero');

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

choose( rows.byId('design-set-buttons'), 'third' );

check( '...it is back in the middle, and the row is grey',
	rows.design._edit.knobs.buttons.corners === 0 && byClass( rows.form, 'design-knob-row--off' ).length === 1
	&& rows.knobRow('Button, corners') !== null && hasClass( rows.knobRow('Button, corners'), 'design-knob-row--off' ) );

rows.tab('Components');
choose( rows.byId('design-set-components'), 'fourth' );

check( 'the Components tab has six rows and none of them grey for Filled, which answers to the line under a field and to where the labels stand as well',
	tabOf( rows ) === 'components' && byClass( rows.form, 'design-knob-row' ).length === 6
	&& byClass( rows.form, 'design-knob-row--off' ).length === 0
	&& JSON.stringify( rows.lit().map( function( row ) { return row[0] } ) ) === JSON.stringify( [ 'scale', 'space', 'corners', 'border', 'zebra', 'labels' ].map( function( knob ) { return TEXT['/_admin/design/knob/components/'+ knob+ '/label'] } ) ) );

console.log('');

// --- The groups of the knobs -----------------------------------------------------------------
//
// The Content has fourteen knobs, which a column does not show at once, and design/list gives
// them in four groups (Setup::GROUPS). The tab draws a strip of tabs under the select of the
// template - a strip of the same make as the eight tabs of the head, built by the same helper -
// and under it the rows of the group that is open and of no other. A group is the screen's
// and no part of the selection: another group changes the rows and the fit of the stage and
// nothing else - no request, no timer, nothing unsaved, no jump of the frame - and the group is
// remembered for the tab while the panel lives

console.log( 'The groups of the knobs' );

const grp = panel();
grp.tab('Content');

const grpStrip = grp.groups();
const grpTabs = grpStrip === null ? [] : byTag( grpStrip, 'button' );
const grpBox = grp.byId('design-knob-rows');
// Looked up when asked, since a tab that is opened again draws the strip again: which tab of the strip is selected, and the knobs of the rows under it
const grpOpen = function() { return byTag( grp.groups(), 'button' ).map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() };
const grpShown = function() { return byClass( grp.byId('design-knobs'), 'design-knob-row' ).map( function( row ) { return row.dataset.knob } ).join() };

check( 'the strip of the groups is a tablist of the same make as the main strip - the workbench\'s bar of tabs, one button for each group in the order design/list gives them, named in the panel\'s words, the first selected - in the card of the template, between its select and the rows',
	grpStrip !== null && grpStrip.getAttribute('role') === 'tablist' && hasClass( grpStrip, 'nino-admin-tabs' ) && hasClass( grpStrip, 'nino-admin-tabs--bar' ) && hasClass( grpStrip, 'design-groups' )
	&& grpTabs.length === 4 && grpTabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' && hasClass( tab, 'nino-admin-tab' ) && tab.type === 'button' } )
	&& grpTabs.map( function( tab ) { return tab.dataset.group } ).join() === 'hero,section,card,variants'
	&& grpTabs.map( function( tab ) { return tab.textContent } ).join() === 'Hero,Section,Card,Variants'
	&& grpOpen() === 'true,false,false,false' && grpStrip.getAttribute('aria-label') === TEXT['/_admin/design/tab/content']
	&& grp.byId('design-knobs').children.length === 3 && grp.byId('design-knobs').children[1] === grpStrip && grp.byId('design-knobs').children[2] === grpBox );
check( '...and it is not the head\'s: the head keeps its one strip of eight tabs with the Content selected, and the strip of the groups stands in the column',
	grp.headStrips().length === 1 && byTag( grp.headStrips()[0], 'button' ).length === 8 && byTag( grp.headStrips()[0], 'button' )[4].getAttribute('aria-selected') === 'true'
	&& byClass( grp.head, 'design-groups' ).length === 0 && hasClass( grpStrip, 'admin-panel-tabs' ) === false && descendants( grp.body() ).indexOf( grpStrip ) !== -1 );

const grpOthers = panel();
const grpWith = [ 'Global', 'Fonts', 'Buttons', 'Components', 'Colours' ].filter( function( name ) { grpOthers.tab( name ); return grpOthers.groups() !== null } );
grpOthers.tab('Buttons');
const grpButtons = grpOthers.byId('design-knobs');

check( 'only the Content has groups: Global, Fonts, Buttons, Components and Colours draw no strip of them, and the rows of Buttons stand in the card itself, under the select, as they did',
	grpWith.length === 0 && grpOthers.byId('design-knob-rows') === null && byClass( grpButtons, 'design-knob-row' ).length === 4 && byClass( grpButtons, 'design-knob-row' ).every( function( row ) { return row.parent === grpButtons } )
	&& byClass( grpOthers.pane, 'design-groups' ).length === 0 );

const grpFlat = panel( { groups : {} } );
const grpOld = panel( { groups : undefined } );

check( 'a server that gives no groups - an answer with none, or with the key left out - gets the Content as one card of fourteen rows under the select, as every other tab, and no strip',
	[ grpFlat, grpOld ].every( function( screen ) {
		screen.tab('Content');
		const card = screen.byId('design-knobs');
		return screen.groups() === null && screen.byId('design-knob-rows') === null && card.children.length === 15
			&& byClass( card, 'design-knob-row' ).map( function( row ) { return row.dataset.knob } ).join() === KNOBS.content.join();
	} ) );

const grpTwo = panel( { groups : { content : { card : GROUPS.content.card, hero : GROUPS.content.hero } } } );
grpTwo.tab('Content');

check( 'the strip follows the answer: two groups, in the order the answer has them, are two tabs, the first of them open - with its six rows under it',
	byTag( grpTwo.groups(), 'button' ).map( function( tab ) { return tab.dataset.group } ).join() === 'card,hero'
	&& byTag( grpTwo.groups(), 'button' ).map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false'
	&& byClass( grpTwo.byId('design-knobs'), 'design-knob-row' ).map( function( row ) { return row.dataset.knob } ).join() === GROUPS.content.card.join() );

// Another group
const grpEdit = JSON.stringify( grp.design._edit );
const grpRequests = grp.requests.length;
const grpTimers = grp.timers.length;
const grpFits = grp.fitted.length;
const grpScrolls = grp.stage.scrolls.length;
const grpAsked = grp.stage.asked.length;
const grpOld1 = byClass( grp.byId('design-knobs'), 'design-knob-row' );

grpTabs[1].fire('click');

check( 'pressing the tab of another group shows the rows of that group and of no other: Section has its four, in the order of the group - the title, the subtitle, the space and the rule - and its tab is the one that is selected',
	grpShown() === GROUPS.content.section.join() && grpOpen() === 'false,true,false,false' && grp.design._groupOf('content') === 'section'
	&& grp.design._group.content === 'section' );
check( '...the rows are drawn again and the strip is not: the old rows are out of the card, the box they stand in is the same, and so are the strip and the four tabs of it - the tab the focus is on stays in the document',
	grpOld1.length === 2 && grpOld1.every( function( row ) { return descendants( grp.pane ).indexOf( row ) === -1 } )
	&& grp.byId('design-knob-rows') === grpBox && grp.groups() === grpStrip && byTag( grp.groups(), 'button' ).every( function( tab, at ) { return tab === grpTabs[at] } )
	&& descendants( grp.pane ).indexOf( grpTabs[1] ) !== -1 && grpBox.children.length === 4 );
check( '...a group is the screen\'s and no part of the selection: the selection is as it was, nothing is marked as unsaved, no request is made and no timer set - the frame does not jump, and the stage is fitted, once',
	JSON.stringify( grp.design._edit ) === grpEdit && grp.design._isDirty() === false && grp.byId('design-reset').hidden === true
	&& grp.requests.length === grpRequests && grp.timers.length === grpTimers
	&& grp.stage.scrolls.length === grpScrolls && grp.stage.asked.length === grpAsked && grp.fitted.length === grpFits + 1 );

const grpSame = byClass( grp.byId('design-knobs'), 'design-knob-row' );
grpTabs[1].fire('click');

check( 'the group that is open is not opened again: the same rows stand and the stage is not fitted for them',
	byClass( grp.byId('design-knobs'), 'design-knob-row' ).length === 4 && byClass( grp.byId('design-knobs'), 'design-knob-row' ).every( function( row, at ) { return row === grpSame[at] } )
	&& grp.fitted.length === grpFits + 1 );

// ...every group has its rows, in the order of the group, and a knob in a group is reached by it alone
grpTabs[2].fire('click');
const grpCard = grpShown();
grpTabs[3].fire('click');
const grpVariants = grpShown();

check( '...the Card has its six - the title and the subtitle, the space, the corners, the line and the shadow - the Variants its two, quiet and loud, and a switch to each is one fit',
	grpCard === GROUPS.content.card.join() && grpVariants === GROUPS.content.variants.join() && grpOpen() === 'false,false,false,true' && grp.fitted.length === grpFits + 3 );

// A knob that is moved stays moved
grp.group('Hero');
grp.move( 'Title (Hero), text size', 1 );
grp.group('Section');
grp.group('Hero');

check( 'a knob that was moved keeps its place while another group is shown: back on the Hero the title stands at +1, drawn from the selection and not from the row that was drawn before',
	grp.pressed('Title (Hero), text size') === 'false,false,false,true,false' && grp.design._edit.knobs.content['hero-title'] === 1 && grp.byId('design-reset').hidden === false );

grp.move( 'Title (Hero), text size', 0 );
grp.group('Card');
grp.tab('Buttons');
grp.tab('Content');

check( 'the group that was open is remembered for the tab: another tab and back, and the Card is open again - the six rows under a strip that has its tab selected',
	grpOpen() === 'false,false,true,false' && grpShown() === GROUPS.content.card.join() && grp.groups() !== grpStrip );

choose( grp.byId('design-set-content'), 'third' );

check( '...and so is it by a template that is chosen, which draws the card and the strip again: the Card is open, with its six rows, the shadow of them grey',
	grpOpen() === 'false,false,true,false' && grpShown() === GROUPS.content.card.join() && hasClass( grp.knobRow('Card, shadow'), 'design-knob-row--off' ) );

choose( grp.byId('design-set-content'), 'first' );
grp.design.showCurrent();

check( '...and by the whole screen drawn again, as the panel is when it is shown once more',
	grpOpen() === 'false,false,true,false' && grpShown() === GROUPS.content.card.join() );

grp.design._group.content = 'nowhere';
grp.design.showCurrent();

check( 'a group the answer does not have is no group: the tab opens the first, as a tab with none chosen does',
	grp.design._groupOf('content') === 'hero' && grpOpen() === 'true,false,false,false' && grpShown() === GROUPS.content.hero.join() );

// The keyboard: the arrow keys of a tablist
const grpKeys = byTag( grp.groups(), 'button' );
focused = null;
press( grpKeys[0], 'ArrowRight' );

check( 'an arrow key on the strip moves the focus to the next tab and opens its group, and the focus stays on the tab it moved to - a tab of the strip that stands, since only the rows are drawn again',
	focused === grpKeys[1] && grpKeys[1].parent === grp.groups() && grpOpen() === 'false,true,false,false' && grpShown() === GROUPS.content.section.join() );

press( grpKeys[1], 'ArrowLeft' );
press( grpKeys[0], 'ArrowLeft' );

check( '...it walks back, and from the first tab it wraps round to the last: the Variants',
	focused === grpKeys[3] && grpOpen() === 'false,false,false,true' && grpShown() === GROUPS.content.variants.join() );

press( grpKeys[3], 'ArrowRight' );

check( '...and from the last to the first', focused === grpKeys[0] && grpOpen() === 'true,false,false,false' && grpShown() === GROUPS.content.hero.join() );

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
	JSON.stringify( offered( family('title') ) ) === JSON.stringify( [ [ '', 'System (default)' ], [ 'open-sans', 'Open Sans' ],
		[ 'system-sans', 'System sans-serif' ], [ 'system-serif', 'System serif' ], [ 'system-mono', 'System monospace' ] ] )
	&& shown( family('title') ) === 'open-sans' && shown( family('text') ) === 'open-sans' );
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
	&& fonts.design.TABS.length === 8
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).getAttribute('min')+ '-'+ size( role ).getAttribute('max') } ).join() === '1-4,0.8-2.5,0.8-1.5,0.6-1.5,0.6-1.3'
	&& byTag( table, 'select' ).filter( function( select ) { return select.dataset.key === 'size' } ).length === 0 );
check( '...and its value is the size that is in force: the one the listing says is in force while none is chosen - 1.6, 1.125, 1, 1 and 0.875 - as the value and not as a placeholder, so that the spinner has a number to count from; the model keeps the empty size it had',
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

check( 'the table drawn again shows each role as it was set, in words: the family, the weight, the case where it is upper, and the size - the one that was typed, else the one the listing says is in force',
	[ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) {
		const upperCase = fonts.byId('design-font-'+ role+ '-uppercase');
		return [ wordOf( family( role ) ), wordOf( weight( role ) ), upperCase === null ? null : upperCase.checked, size( role ).value ].join(', ');
	} ).join(' | ')
		=== 'System serif, Light, true, 2.4 | System (default), Normal, false, 1.125 | Open Sans, Normal, false, 1 | Open Sans, Bold, false, 1 | System monospace, Normal, , 0.875'
	&& fonts.byId('design-summary') === null );

fonts.design._previewNow();
const fontPreview = fonts.requests[fonts.requests.length - 1];

check( 'the preview asked for carries the role that was changed, with the rest of the selection beside it - the frame is the answer to every control',
	fontPreview.endpoint === 'preview' && JSON.stringify( fontPreview.payload.fonts.title ) === JSON.stringify( { family : 'system-serif', weight : 'light', uppercase : true, size : '2.4' } )
	&& Object.keys( fontPreview.payload ).sort().join() === 'colours,css,fonts,full,knobs,motion,parts,size,theme,whitespace,width' && fontPreview.payload.full === true );

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
	to the one the listing says is in force is no choice - the model keeps the empty size, as it did, and the role goes
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

check( 'a size typed back to the one the listing says is in force is kept as the empty size - 1.6 for the title, 1.1250 for the subtitle, 1.0004 for the text, which comes to 1, and .875 for the code - and the field goes on showing the number',
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
const BROUGHT = [ 'open-sans', 'system-sans', 'system-serif', 'system-mono' ];

/** The whole answer of design/webfonts/list, and - with a msg - of the install and the removal after it */
function shelfOf( over ) {
	return Object.assign( { families : [], fetched : 0, url : 'https://webfonts.test/webfonts.json', key : true, builtin : BROUGHT, installed : {}, broken : {}, uses : {}, refusal : '', refusalKind : '' }, over || {} );
}

/**
 *	The answer of design/list once families are installed, as the server gives it: they stand among the families after the
 *	four the feature brings, and in the options of the roles but the code - which is offered the monospace ones only
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
// The pager stands after the box in the card, not in it
const pager = function( screen ) { return byClass( availableCard( screen || shelves ), 'design-faces-pager' )[0] || null };
const pageOf = function( screen ) { return pager( screen ) === null ? null : byClass( pager( screen ), 'design-faces-place' )[0].textContent };
const pagerButtons = function( screen ) { return pager( screen ) === null ? [] : byTag( pager( screen ), 'button' ) };

check( 'thirty families are a page of 24 and a page of 6: the first twenty-four rows, in the order of the list, and under them Previous - off -, "1 of 2" and Next',
	faceRows( results() ).length === 24 && faceSlugs( results() ) === thirty.slice( 0, 24 ).map( function( entry ) { return entry.slug } ).join()
	&& pageOf() === '1 of 2' && pagerButtons().map( function( button ) { return button.textContent+ ':'+ button.disabled } ).join() === 'Previous:true,Next:false'
	&& shelves.design.FACES_PER_PAGE === 24 );
check( 'the pager stands after the box in the card and not in it, so that it stays where it is while the box scrolls: the box holds the rows of the page and nothing else, and the one pager is what follows it in the card',
	byClass( results(), 'design-faces-pager' ).length === 0 && results().children.length === 24 && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1
	&& availableCard( shelves ).children[availableCard( shelves ).children.indexOf( results() ) + 1] === pager() );

pagerButtons()[1].fire('click');

check( '...Next shows the six that are left, and is off itself',
	faceSlugs( results() ) === 'face-25,face-26,face-27,face-28,face-29,face-30' && pageOf() === '2 of 2'
	&& pagerButtons().map( function( button ) { return button.disabled } ).join() === 'false,true' );

pagerButtons()[0].fire('click');

check( '...and Previous the page before',
	faceRows( results() ).length === 24 && faceRows( results() )[0].dataset.slug === 'face-01' && pageOf() === '1 of 2' );

results().scrollTop = 300;
pagerButtons()[1].fire('click');

check( 'a page turned starts at the top of its box, whatever the page before was scrolled to - and it has one pager, its own: the one of the page before went with its rows',
	results().scrollTop === 0 && pageOf() === '2 of 2' && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1 && results().children.length === 6 );

pagerButtons()[0].fire('click');

const boxBefore = results();

results().scrollTop = 200;
shelves.design._paintFaces();

check( 'a card that is drawn again - the answer of an install or of a removal, a list that was loaded - has its box scrolled to where the box before it was: a new box, at 200, with the rows of the same page and one pager after it',
	results() !== boxBefore && results().scrollTop === 200 && faceRows( results() ).length === 24 && pageOf() === '1 of 2' && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1 );

results().scrollTop = 200;
shelves.byId('design-faces-load').fire('click');
shelves.answer( 200, shelfOf( { families : thirty, fetched : LOADED_AT } ) );

check( 'a list loaded again starts at its first page, and that page at the top of its box - what the box before was scrolled to was another list\'s',
	results().scrollTop === 0 && pageOf() === '1 of 2' && faceRows( results() ).length === 24 );

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
		offerOf( 'open-sans', 'Open Sans', 'sans' ),
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
		const own = faceRowOf( availableCard( offering ), 'open-sans' );
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

owning.design._edit.fonts.subtitle.family = 'open-sans';
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
owned.answer( 200, shelfOf( { families : [ offerOf( 'open-sans', 'Open Sans', 'sans' ) ], fetched : LOADED_AT, builtin : [] } ) );

check( 'a row of the list for a family the feature brings says "installed" because the answer of the tab names it, and not because design/list has it among its families: with the answer naming none, the row offers to install',
	owned.design._data.families['open-sans'] !== undefined && byClass( faceRowOf( availableCard( owned ), 'open-sans' ), 'design-face-state' ).length === 0
	&& faceWord( faceRowOf( availableCard( owned ), 'open-sans' ) ) === 'Install'
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

check( 'a block of rows has its title and nothing under it, on every tab: the three cards of the theme, the animation of Global, the table of the fonts, the two cards of the typefaces, the tuning and the mode of the palette carry a heading of one title each - no small under it',
	JSON.stringify( headings ) === JSON.stringify( { theme : 'Themes:1:0,Theme:1:0,Custom css:1:0', global : 'Animation:1:0', fonts : 'Fonts:1:0', typefaces : 'Installed:1:0,Available:1:0', content : '', buttons : '', components : '', colours : 'Tuning:1:0,Mode:1:0' } ) );

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
	'move-vertical' : '<path d="M12 2v20"/><path d="m8 18 4 4 4-4"/><path d="m8 6 4-4 4 4"/>',
	// The rule under the title of a section
	'minus' : '<path d="M5 12h14"/>',
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
	'global/size' : 'a-large-small', 'global/width' : 'move-horizontal', 'global/whitespace' : 'move-vertical',
	'typefaces/family' : 'a-large-small',
	'content/hero-title' : 'a-large-small', 'content/hero-text' : 'a-large-small', 'content/quiet' : 'a-large-small', 'content/loud' : 'a-large-small',
	'content/section-title' : 'a-large-small', 'content/section-subtitle' : 'a-large-small', 'content/section-space' : 'scaling', 'content/section-line' : 'minus',
	'content/card-title' : 'a-large-small', 'content/card-subtitle' : 'a-large-small', 'content/card-space' : 'align-vertical-space-around', 'content/corners' : 'square-round-corner',
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

	icons.allRows().forEach( function( row ) {

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

check( 'every row of the three knob tabs - twenty-four of them, those of every group of the Content among them - carries an svg.design-icon before its name, with the data-icon of the table, and the shape of Lucide\'s that name has, verbatim'
	+ ( iconProblems.length === 0 ? '' : ' - not so for '+ iconProblems.join() ), iconRows === 24 && iconProblems.length === 0 );

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
choose( icons.byId('design-set-content'), 'third' );
icons.group('Card');

check( '...a row that is greyed out keeps its icon, with the line that says whose decision that is in the box beside it',
	hasClass( icons.knobRow('Card, shadow'), 'design-knob-row--off' ) && byTag( icons.knobRow('Card, shadow'), 'svg' )[0].dataset.icon === 'square-stack'
	&& byTag( icons.knobRow('Card, shadow'), 'small' )[0].textContent === 'This template does not answer to “Card, shadow”.' );

icons.tab('Global');

check( 'the three strips of Global take theirs by the same mechanism: the root size the letters of two sizes, the width the two arrows that point apart and the whitespace the two that point up and down - Lucide\'s a-large-small, move-horizontal and move-vertical, each with the shape of its name, verbatim, in front of the box that holds the name',
	[ [ 'Page, root size', 'a-large-small' ], [ 'Page, width', 'move-horizontal' ], [ 'Page, whitespace', 'move-vertical' ] ].every( function( entry ) {
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

/*	Another group of the knobs is another height of the rows over the stage, and in one column that moves the stage as another
	tab does: the Hero has two rows and the Card six. The fit gives the frame another height, and the page in it lays itself out
	again only after its window has heard the resize - the place it showed, the cards after a card knob was moved, is where it
	stood at the old height until then. So the jump back to that place waits for the page as a tab's does, and a group that leaves
	the stage as tall as it was has nothing to put back. The test puts the stage where the rows of the group about to be opened
	have it, and opens the group by hand	*/
put( 1180, 187, 732, 799, 0, false );
rm.tab('Content');
rm.stage.resize();
rm.stage.animate();
elapse( 300 );

const rmHero = parseFloat( rmStage.style.height );
const rmGroupFits = rm.fitted.length;
const rmGroupScrolls = rm.stage.scrolls.length;
const rmGroupRequests = rm.requests.length;

put( 1180, 187, 1032, 1099, 0, false );
rm.group('Card');

check( 'in one column another group of the knobs fits the stage to where it stands over the rows of that group, with no preview asked for: the Card has six rows to the Hero\'s two, the stage stands 300px lower, and with less than 16 rem left under it it is as tall as the window it fills with the top of its pane at the top of it - 1180px less the 67px the stage stands in its pane, the bar and the gap',
	rm.design._tab === 'content' && rm.design._groupOf('content') === 'card' && rm.requests.length === rmGroupRequests && rm.fitted.length === rmGroupFits + 1
	&& near( rmHero, 1180 - 799 - 68 - 11.2 ) && near( parseFloat( rmStage.style.height ), 1180 - 67 - 68 - 11.2 ) );
check( '...and the jump waits for the page in the frame, as a tab\'s does: the stage changed its height, so nothing is scrolled when the group is shown, one resize is listened for on the frame\'s window, and a timer of 300ms stands behind it',
	rm.stage.scrolls.length === rmGroupScrolls && rm.stage.listeners.length === 1 && rm.stage.listeners[0][0] === 'resize' && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 1 );

rm.stage.resize();

check( '...the resize itself scrolls nothing: it asks the frame\'s window for an animation frame, which is queued after the one the page lays its covers out in',
	rm.stage.scrolls.length === rmGroupScrolls && rm.stage.frames.length === 1 && rm.stage.listeners.length === 1 );

rm.stage.animate();
elapse( 300 );

check( '...and in that frame the page is put back, once, to where it was looked at - no knob was moved since the tab was opened, so the anchor of the tab: the hero, 300px below the top of the window with the page scrolled 100px, so 400 - the listener is taken away and the timer behind it adds none',
	rm.stage.scrolls.length === rmGroupScrolls + 1 && JSON.stringify( rm.stage.scrolls[rmGroupScrolls] ) === JSON.stringify( { top : 400, behavior : 'smooth' } )
	&& rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 0 );

// A card knob is moved: the cards are where a change is looked at, and the next group is to leave the page there
rm.move( 'Title (Card), text size', 1 );

const rmPlaced = rm.stage.scrolls.length;

put( 1180, 187, 732, 799, 0, false );
rm.group('Hero');

check( 'the place that is put back is the place of the last change: after a card knob was moved the frame stood at the cards, and the Hero, whose two rows put the stage 300px higher and make it that much shorter, waits for the page the same way - nothing is scrolled when it is shown, one resize is listened for, a timer stands behind it',
	rm.design._target === 'cards' && near( parseFloat( rmStage.style.height ), rmHero ) && rm.stage.scrolls.length === rmPlaced
	&& rm.stage.listeners.length === 1 && rm.stage.listeners[0][0] === 'resize' && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 1 );

rm.stage.resize();
rm.stage.animate();
elapse( 300 );

check( '...and then it is put back there, once, and not at the anchor of the tab: the cards, 900px below the top of the window with the page scrolled 100px, so 1000 - the listener is taken away, and the timer adds no second scroll',
	rm.stage.scrolls.length === rmPlaced + 1 && JSON.stringify( rm.stage.scrolls[rmPlaced] ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) && rm.stage.asked[rm.stage.asked.length - 1] === 'cards'
	&& rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 0 );

// Another group with the rows of the same height: the stage stands where it did and the fit leaves it as tall as it was
const rmKept = rm.stage.scrolls.length;
const rmKeptAsked = rm.stage.asked.length;
const rmKeptFits = rm.fitted.length;

rm.group('Variants');

check( 'a group that leaves the stage as tall as it was has nothing to put back: the Variants, two rows as the Hero has, fit the stage to the same height and scroll nothing - no resize is listened for, no timer is set, and the page in the frame is not even asked for the element',
	rm.design._groupOf('content') === 'variants' && rm.fitted.length === rmKeptFits + 1 && near( parseFloat( rmStage.style.height ), rmHero )
	&& rm.stage.scrolls.length === rmKept && rm.stage.asked.length === rmKeptAsked && rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 0 );

// With the switch off the frame never scrolls by itself, whatever the height does
rm.design._follow = false;
put( 1180, 187, 1032, 1099, 0, false );
rm.group('Card');
rm.stage.resize();
rm.stage.animate();
elapse( 300 );

check( 'with Preview follows off nothing is scrolled even where the height changed, as for a tab: the stage is fitted to the Card\'s rows, the wait is over, and the page in the frame is left where it is - not even asked for the element',
	near( parseFloat( rmStage.style.height ), 1180 - 67 - 68 - 11.2 ) && rm.stage.scrolls.length === rmKept && rm.stage.asked.length === rmKeptAsked
	&& rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 && rm.waiting( 300 ).length === 0 );

rm.design._follow = true;

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


// --- The room of the list of typefaces -----------------------------------------------------
//
// The box the rows of a page of the Typefaces tab stand in is fitted to the window as the stage is: as tall as
// the window has left under it - its height less the top of the box with the page scrolled to the top, less the action
// bar, the pager that stands after the box and the gap the stack of the preview keeps from the bar - so that the pager
// ends that gap above the bar. Five rows at the least; where the room is less than that the box is made for the window
// it fills when the page is scrolled to its card, the top of the card at the top of the window. A box that holds the
// sentence of no match is not fitted. The numbers are the window's and the places of the card, the box, its first row
// and the pager, which a test says: the stand-in has no layout of its own

console.log( 'The room of the list of typefaces' );

const lf = panel();
lf.tab('Typefaces');
lf.answer( 200, shelfOf( { families : thirty, fetched : LOADED_AT } ) );

lf.byId('design-frame').parent.parent.computed = { rowGap : '11.2px' };

/** How high a row of the list is where it stands: a row nobody measured has no height, and five of none are 11 rem */
let lfRow = 52;

/**
 *	Put the card of the list, its box, the first row and the pager where a window has them: the card at 150px with the page
 *	scrolled to the top, the box 200px below it with a height of its own and the pager at the foot of it, 40px high - and, with
 *	the page scrolled by $scrolled, that much higher
 */
function lay( root, scrolled ) {

	const at = function( id ) { return descendants( root ).filter( function( el ) { return el.id === id } )[0] };
	const card = at('design-faces-available');
	const box = at('design-faces-results');
	const first = byClass( box, 'design-face-row' )[0];
	const foot = byClass( card, 'design-faces-pager' )[0];

	card.rect = { top : 150 - scrolled, left : 0, width : 360, height : 700 };
	box.rect = { top : 350 - scrolled, left : 0, width : 330, height : 500 };

	if( first !== undefined )
		first.rect = { top : 350 - scrolled, left : 0, width : 330, height : lfRow };

	if( foot !== undefined )
		foot.rect = { top : 850 - scrolled, left : 0, width : 330, height : 40 };

	lf.form.scrollTop = scrolled;
}

// A row and a pager are told where they stand as they are made, and a column drawn again is laid out the same way
const lfRowOf = lf.design._availableRow;
const lfPagerOf = lf.design._pager;
const lfRender = lf.design._renderTypefaces;

lf.design._availableRow = function( entry ) {
	const row = lfRowOf.call( lf.design, entry );
	row.rect = { top : 350, left : 0, width : 330, height : lfRow };
	return row;
};
lf.design._pager = function( pages ) {
	const foot = lfPagerOf.call( lf.design, pages );
	foot.rect = { top : 850, left : 0, width : 330, height : 40 };
	return foot;
};
lf.design._renderTypefaces = function( body ) {
	lfRender.call( lf.design, body );
	lay( body, 0 );
};

/** Lay the list out for a window of that height, with the page scrolled by so much, fit the screen, and say what height the box has then */
function fitList( height, scrolled ) {
	lf.view.innerHeight = height;
	lay( lf.form, scrolled );
	lf.design._fit();
	return lf.byId('design-faces-results').style.height;
}

const lfBox = function() { return lf.byId('design-faces-results') };
const lfPane = lf.byId('design-preview');

// The pane sticks beside the controls, as it does in two columns; a check of the one-column layout says static
lfPane.computed = { position : 'sticky' };

check( 'the box of the list is as tall as the window has left under it, as the stage is: the height of the window less the top of the box, the action bar - 68px - the pager that stands after it, 40px, and the 11.2px gap of the stack of the preview: 800 - 350 - 68 - 40 - 11.2, so that the pager ends the gap above the bar',
	near( parseFloat( fitList( 800, 0 ) ), 800 - 350 - 68 - 40 - 11.2 ) && lfBox().style.height === ( 800 - 350 - 68 - 11.2 - 40 )+ 'px' );
check( '...whatever the page is scrolled to: the box is measured where it stands with the page at its top, as the stage is',
	near( parseFloat( fitList( 800, 120 ) ), 800 - 350 - 68 - 40 - 11.2 ) && near( parseFloat( fitList( 800, 40 ) ), 800 - 350 - 68 - 40 - 11.2 ) );
lfBox().scrollTop = 200;
check( '...and the scroll of the box itself is not the page\'s: a box scrolled by 200 is as tall as it was',
	near( parseFloat( fitList( 800, 0 ) ), 800 - 350 - 68 - 40 - 11.2 ) );
lfBox().scrollTop = 0;
check( 'a window that is higher has more rows to show: 1000 and 1200',
	near( parseFloat( fitList( 1000, 0 ) ), 1000 - 350 - 68 - 40 - 11.2 ) && near( parseFloat( fitList( 1200, 0 ) ), 1200 - 350 - 68 - 40 - 11.2 ) );
check( '...never under five rows: five times the 52px of the first row and the four hairlines between them, 264px, where the window is too low for more - and where even the card brought to the top of the window leaves less',
	fitList( 500, 0 ) === ( 5 * 52 + 4 )+ 'px' && fitList( 100, 0 ) === ( 5 * 52 + 4 )+ 'px' );
check( 'beside the preview, in two columns, a window whose room under the box is less than five rows gives five rows and no more - the page scrolls by what they overhang, not for the list: 700px high, 214px under the box, 264px',
	fitList( 700, 0 ) === ( 5 * 52 + 4 )+ 'px' && fitList( 700, 90 ) === ( 5 * 52 + 4 )+ 'px' );

lfPane.computed = { position : 'static' };

check( 'in one column, under the controls, where the room under the box is less than five rows but the window is not that low, the box is made for the window it fills when the page is scrolled to its card, the top of the card at the top of the window: 700px less the 200px the box stands inside the card - the search and the categories are in view with it - the bar, the gap and the pager',
	near( parseFloat( fitList( 700, 0 ) ), 700 - 200 - 68 - 11.2 - 40 ) && near( parseFloat( fitList( 700, 90 ) ), 700 - 200 - 68 - 11.2 - 40 ) );

lfPane.computed = { position : 'sticky' };

lfRow = 0;

check( '...and a row that has no height to count five of is the 11 rem of a box that holds no row: 176px at a root of 16px',
	fitList( 400, 0 ) === '176px' );

lf.view.fontSize = '15px';

check( '...the rem is the root\'s own: 165px at a root of 15px',
	fitList( 400, 0 ) === '165px' );

lfRow = 52;
lf.view.fontSize = '16px';

lf.view.innerHeight = 900;
lf.design._fit();

check( 'a window that is resized fits the box again: 900px high, the box 100px taller than it was at 800',
	near( parseFloat( lfBox().style.height ), 900 - 350 - 68 - 40 - 11.2 ) );

lf.view.innerHeight = 1000;
lf.design._fitLater();
lf.waiting( 150 )[0][0]();

check( '...and so does what runs when the resizing has stopped',
	near( parseFloat( lfBox().style.height ), 1000 - 350 - 68 - 40 - 11.2 ) );

lfBox().style.height = '123px';
lfBox().rect = { top : 0, left : 0, width : 0, height : 0 };
lf.design._fit();

check( 'a tab that is not on screen has no box to give room to: its height is left as it is',
	lfBox().style.height === '123px' );

lay( lf.form, 0 );
lf.view.innerHeight = 800;

// ---- A list of one page has no pager under the box, and the box has the room it took

const lfSearch = lf.byId('design-faces-search');
const lfWas = lfBox().style.height;

lfSearch.value = 'face 2';
lfSearch.fire('input');

check( 'the rows that the search lets through fit the box as they are drawn, with no resize and no tab: a list of ten stands on one page with no pager after the box, and the box has the 40px of it as well - 800 - 350 - 68 - 11.2',
	byClass( availableCard( lf ), 'design-faces-pager' ).length === 0 && faceRows( lfBox() ).length === 10 && lfWas !== lfBox().style.height
	&& near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 11.2 ) );

// ---- The sentence of no match is not fitted

lfSearch.value = 'nothing of this name';
lfSearch.fire('input');

check( 'a box that holds the sentence of no match is not fitted: its height is cleared, and what is left of it is what the stylesheet gives it',
	faceRows( lfBox() ).length === 0 && lfBox().style.height === '' && hints( lfBox() ).length === 1 );

lf.design._fit();

check( '...and a fit that comes later - another window, another tab - leaves it as it is',
	lfBox().style.height === '' );

lfSearch.value = '';
lfSearch.fire('input');

check( 'the rows that come back are fitted as they are drawn, with the pager after the box again: 800 - 350 - 68 - 40 - 11.2',
	faceRows( lfBox() ).length === 24 && byClass( availableCard( lf ), 'design-faces-pager' ).length === 1 && near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 40 - 11.2 ) );

// ---- Leaving the tab and coming back

const lfOld = lfBox();

lf.tab('Global');
lf.tab('Typefaces');

check( 'leaving the tab and coming back draws the card again, and the box that stands in the new column is fitted once the column is in the document, with no answer to wait for: 800 - 350 - 68 - 40 - 11.2',
	lfBox() !== null && lfBox() !== lfOld && faceRows( lfBox() ).length === 24 && near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 40 - 11.2 ) );

console.log('');


// --- The frame follows the tab -------------------------------------------------------------
//
// One page with an anchor for each tab. The frame is of this very origin, so
// the panel scrolls its window by hand: smoothly after a tab is opened and after
// every change, at once after the page was rebuilt

console.log( 'The anchors' );

const anchors = panel();

check( 'each tab has the id of an element on the specimen: global, global, type, type, content, buttons, components and colours - the Theme is all of the page and goes to its top as Global does, Fonts and Typefaces are the typography block, Colours the grounds',
	JSON.stringify( anchors.design.ANCHORS ) === JSON.stringify( { theme : 'global', global : 'global', fonts : 'type', typefaces : 'type', content : 'content', buttons : 'buttons', components : 'components', colours : 'colours' } ) );

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
anchors.design._frames = 'first|first';
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a frame that cannot be reached, which is built anew and plays on its own', hero.log.length === 0 && threw( function() { anchors.design._replay() } ) === false );

anchors.stage.reachable = true;
anchors.stage.animated = [];
anchors.design._frames = 'first|first';
anchors.design._edit.size = 'm';
anchors.design._edit.motion = { effect : '', strength : '', speed : '' };
anchors.design._replays = false;
anchors.requests.length = 0;

// A frame that cannot be reached: the panel asks for the whole page instead of showing a stale one
anchors.stage.reachable = false;
anchors.design._frames = 'first|first';
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

fw.group('Variants');

check( '...nor does another group of the knobs: the rows under the strip are drawn again, the stage is fitted for them - one fit more - and the frame stays where it is',
	fw.stage.scrolls.length === fwScrolled && fw.stage.asked.length === fwAsked && fw.fitted.length === fwFitted + 5 && fw.requests.length === 0 );

fw.move( 'Quiet variant, text size', 1 );
fw.design._previewNow();
fw.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a change: the preview comes back and its stylesheet is swapped inside the frame that stands, which stays where it is - and the stage is fitted once more with the answer',
	fw.stage.style.textContent === ':root{--x:1}' && fw.stage.scrolls.length === fwScrolled && fw.stage.asked.indexOf('cards') === -1
	&& fw.fitted.length === fwFitted + 6 && fw.design._edit.knobs.content.quiet === 1 );

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
// change is one target, put back to the tab's own when a tab is opened or a template is chosen - a footer
// that is chosen is the one template that is looked at somewhere else: where the page ends

console.log( 'Where the preview jumps' );

const jp = panel();
const jpLast = function() { return jp.stage.scrolls[jp.stage.scrolls.length - 1] };
const jpAsked = function() { return jp.stage.asked[jp.stage.asked.length - 1] };

/** What the preview answers while the frame stands: a stylesheet to swap, and then the page goes where the change is looked at */
function jpAnswer() {
	jp.design._previewNow();
	jp.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
}

check( 'the specimen knows where a change of a knob is looked at: the two knobs of the hero and the title and the rule of a section go to the hero, the subtitle of a section to the plain section, the title and the subtitle of a card and every other knob of Content to the cards - the five new ones named, none of them left to the rule for the rest - and no other tab has an entry',
	JSON.stringify( jp.design.KNOB_ANCHORS ) === JSON.stringify( { content : { 'hero-title' : 'content', 'hero-text' : 'content', 'section-title' : 'content', 'section-subtitle' : 'colours', 'section-line' : 'content', 'card-title' : 'cards', 'card-subtitle' : 'cards', '*' : 'cards' } } )
	&& Object.keys( jp.design.KNOB_ANCHORS ).join() === 'content' && jp.design._target === null );

jp.tab('Content');
const jpOpened = jp.stage.scrolls.length;

check( 'opening a tab goes to the anchor of the tab and leaves no target: Content is the hero',
	jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) && jp.design._target === null );

jp.moveIn( 'Variants', 'Quiet variant, text size', 1 );

check( 'a knob of Content that is not the hero\'s sets the target to the cards, and nothing scrolls until the preview has answered - nor does the group that was opened to reach it',
	jp.design._target === 'cards' && jp.stage.scrolls.length === jpOpened );

jpAnswer();

check( '...and then the frame goes to the cards: the element with the id cards, from where the page stands',
	jpAsked() === 'cards' && jp.stage.scrolls.length === jpOpened + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

const jpWhere = {};

KNOBS.content.forEach( function( knob ) {

	jp.moveIn( groupWord( knob ), TEXT['/_admin/design/knob/content/'+ knob+ '/label'], -1 );
	jpWhere[knob] = jp.design._target;
} );

check( 'each knob of Content has its place: the title and the text of the hero, and the title and the rule of a section, the hero - the subtitle of a section the plain section, which is where one that this knob sizes stands - the other nine - quiet, loud, the space of the sections, the title and the subtitle of a card, the space, the corners, the line and the shadow - the cards',
	JSON.stringify( jpWhere ) === JSON.stringify( { 'hero-title' : 'content', 'hero-text' : 'content', quiet : 'cards', loud : 'cards', 'section-title' : 'content', 'section-subtitle' : 'colours', 'section-space' : 'cards', 'section-line' : 'content', 'card-title' : 'cards', 'card-subtitle' : 'cards', 'card-space' : 'cards', corners : 'cards', border : 'cards', shadow : 'cards' } ) );

jp.moveIn( 'Hero', 'Title (Hero), text size', 1 );
jpAnswer();

check( 'a change of the title of the hero goes to the hero: #content',
	jp.design._target === 'content' && jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.moveIn( 'Hero', 'Subtitle (Hero), text size', 1 );
jpAnswer();

check( '...and so does one of the text of the hero', jp.design._target === 'content' && jpAsked() === 'content' );

jp.moveIn( 'Card', 'Card, shadow', 1 );
jpAnswer();

check( 'one of the cards goes back to the cards, whichever knob was moved before it', jp.design._target === 'cards' && jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

// A template is the tab's, not the knob's
choose( jp.byId('design-set-content'), 'second' );

check( 'choosing a template puts the target back to the tab\'s own, and the frame goes to the anchor of the tab when the preview has answered',
	jp.design._target === null );

jpAnswer();

check( '...the hero, where it went when the tab was opened', jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

jp.moveIn( 'Card', 'Card, corners', 1 );
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
choose( jp.byId('design-set-header'), 'second' );

check( 'choosing a frame on Global is a template too: the target goes back', jp.design._target === null );

// A footer is looked at where it starts: the specimen ends in an anchor of its own, and Global - which is the top of the page - leaves it the frame
jp.tab('Global');
jp.design._target = 'cards';
const jpScrolled = jp.stage.scrolls.length;
choose( jp.byId('design-set-footer'), 'second' );

check( 'choosing the footer on Global makes the end of the page the place it is looked at in: the target is the id of the anchor the specimen ends in, not the top - and nothing scrolls until the preview has answered',
	jp.design._target === 'footer' && jp.stage.scrolls.length === jpScrolled );

jpAnswer();

check( '...and then the frame goes to the foot of the page, which Global would have left at the top: the anchor of the footer, from where the page stands - no room taken off for the header, which is no concern of the end of a page',
	jpAsked() === 'footer' && jp.stage.scrolls.length === jpScrolled + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 4100, behavior : 'smooth' } ) && jp.design._tab === 'global' );

jp.stage.clearance = 96;
jp.design._jump();

check( '...whatever room the header asks of the top of main: the footer\'s anchor is placed where it stands', JSON.stringify( jpLast() ) === JSON.stringify( { top : 4100, behavior : 'smooth' } ) );

jp.stage.clearance = 0;
choose( jp.byId('design-set-header'), 'first' );
jpAnswer();

check( 'a header that is chosen after it is looked at at the top again, as it was: the target goes back, and the frame goes to the top of the page that Global is',
	jp.design._target === null && jp.stage.scrolls.length === jpScrolled + 3 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && jpAsked() === 'global' );

jp.design._target = 'footer';
jp.tab('Content');

check( 'the footer is no place of a tab: opening one - and moving a knob on the way - puts the frame where it goes, not at the foot of the page', jp.design._target === null && jpAsked() === 'content' );

jp.tab('Global');
jp.design._target = 'footer';
jp.design._follow = false;
const jpHeld = jp.stage.scrolls.length;
choose( jp.byId('design-set-footer'), 'first' );
jp.design._jump();
jp.design._follow = true;

check( 'with the follow switch off a footer that is chosen does not scroll the frame either', jp.stage.scrolls.length === jpHeld && jp.design._target === 'footer' );

// Opening the tab already open changes nothing, and so does a change that is no knob
jp.tab('Content');
jp.moveIn( 'Variants', 'Quiet variant, text size', -1 );
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

check( 'every id the script jumps to is on the specimen exactly once: the eight of the tabs - the Theme goes where Global does, to the top of the page -, the hero, the plain section and the cards - sixteen places, of which the ids of the five new knobs are three',
	jumpedTo.length === 16 && jumpedTo.every( function( id ) { return specimenIds[id] === 1 } ) && specimenIds.cards === 1 );
check( '...and the footer, which no tab has, is looked at in an element of its own: the id footer, once, an empty block at the end of the specimen - after the last section, where the footer of the frame starts',
	specimenIds.footer === 1 && /<\/section>\s*<div id="footer"><\/div>\s*$/.test( specimen ) && specimen.lastIndexOf('id="footer"') > specimen.lastIndexOf('<section ') && Object.keys( jp.design.ANCHORS ).every( function( tab ) { return jp.design.ANCHORS[tab] !== 'footer' } ) );
check( '...and the Cards section is the element that has the id: the second section of the page, after the hero and before the grounds',
	specimen.indexOf('<section id="cards" class="nino-section" aria-label="Cards">') !== -1 && specimen.indexOf('id="content"') < specimen.indexOf('id="cards"')
	&& specimen.indexOf('id="cards"') < specimen.indexOf('id="colours"') );

console.log('');


// --- What the preview carries ----------------------------------------------------------------

console.log( 'The preview' );

const asked = panel();
asked.design._previewNow();
const first = asked.requests[asked.requests.length - 1];

check( 'the first preview of a visit asks for the whole document, with every key of the model: the templates, the knobs, the size, the width, the animation, the fonts, the palette, and the theme with the css of its own',
	first.endpoint === 'preview' && first.payload.full === true && Object.keys( first.payload ).sort().join() === 'colours,css,fonts,full,knobs,motion,parts,size,theme,whitespace,width'
	&& first.payload.parts.header === 'first' && first.payload.parts.components === 'first' && first.payload.knobs.content['hero-title'] === 0
	&& first.payload.motion.effect === '' && first.payload.colours.mode === 'auto' );
check( '...and while it is asked for, the line over the frame says so, and its title says the same - the chip shows as much of the line as the head has room for',
	asked.byId('design-preview-msg').textContent === TEXT['/_admin/design/msg/previewing'] && asked.byId('design-preview-msg').title === TEXT['/_admin/design/msg/previewing']
	&& hasClass( asked.byId('design-preview-msg'), 'nino-admin-hint' ) );

asked.answer( 200, { css : ':root{}', document : '<!doctype html>', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'an answer with nothing to add empties the line, and its title with it',
	asked.byId('design-preview-msg').textContent === '' && asked.byId('design-preview-msg').title === '' && hasClass( asked.byId('design-preview-msg'), 'nino-admin-hint' ) );

asked.design._previewNow();

check( '...the next one only a stylesheet, as long as the header and the footer are the ones the frame was built with', asked.requests[asked.requests.length - 1].payload.full === false );

asked.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [ 'there is no template "x" - using "first"' ] } );

check( 'what the server had to replace comes back beside the preview, in the line over the frame - and as the title of the line, where the chip cuts it',
	( asked.byId('design-preview-msg') || {} ).textContent === 'there is no template "x" - using "first"' && hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' )
	&& asked.byId('design-preview-msg').title === 'there is no template "x" - using "first"' );

choose( asked.byId('design-set-footer'), 'second' );
asked.design._previewNow();

check( '...and another header or footer is markup, so the document is asked for again', asked.requests[asked.requests.length - 1].payload.full === true );

asked.answer( 500, { error : 'broken' } );

check( 'a preview that failed says so in that line, as an error, and in its title as well',
	hasClass( asked.byId('design-preview-msg'), 'nino-admin-error' ) && asked.byId('design-preview-msg').textContent === '(500) broken' && asked.byId('design-preview-msg').title === '(500) broken' );

console.log('');

// --- Applying asks first ---------------------------------------------------------
//
// A draft is not a website. Two buttons, and a question before anything is
// written - the workbench's own dialog, built from what design/plan says: it says
// in words what is written, which of it is not Design's and which shortcodes a
// frame would lose, and the paths of the files stand in a <details> under it.
// What was done is said in the line of the action bar, and the chip beside it is
// drawn again

console.log( 'Applying' );

/** design/plan's answer for a project the wizard just delivered, with [consent-settings] added to the footer by hand */
const delivered = [
	{ target : '/assets/theme.css', part : '', exists : true, state : 'foreign', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', part : 'header', exists : true, state : 'foreign', changes : true, lost : [] },
	{ target : '/templates/frame-footer.tpl', part : 'footer', exists : true, state : 'foreign', changes : true, lost : [ '[consent-settings]' ] },
];

/** The words of a file in a dialog before an apply: the one of its part, the stylesheet being the look */
const fileWord = function( part ) { return TEXT['/_admin/design/confirm/file/'+ ( part || 'look' )] };

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

const question = asking.asks[0];
const lines = question === undefined ? [] : question.options.message.split('\n');

check( 'the question is the workbench\'s own dialog and no confirm() of the browser: titled by the button that asked, with the choice that applies for the primary one and the one that does not for the secondary',
	asking.confirms.length === 0 && asking.asks.length === 1 && question.options.title === TEXT['/_admin/design/label/apply']
	&& question.options.choices.map( function( choice ) { return choice.value+ ':'+ choice.kind } ).join() === 'apply:primary,cancel:secondary'
	&& question.options.choices[0].label === TEXT['/_admin/design/confirm/yes'] && typeof question.options.onChoose === 'function' );
check( '...it names the three files by what they are for - the look, the header, the footer -, which of them are not Design\'s, and the shortcode a frame would lose: in words, with no path in the text',
	lines.indexOf( say( '/_admin/design/confirm/apply', [ fileWord( '' ), fileWord( 'header' ), fileWord( 'footer' ) ].join(', ') ) ) !== -1
	&& lines.indexOf( say( '/_admin/design/confirm/foreign', [ fileWord( '' ), fileWord( 'header' ), fileWord( 'footer' ) ].join(', ') ) ) !== -1
	&& lines.indexOf( say( '/_admin/design/confirm/lost', '[consent-settings]' ) ) !== -1
	&& /assets\/|templates\/|\.css|\.tpl|design-previous/.test( question.options.message ) === false );
check( '...says the present state is kept first, one fact to a line, and no word about a version the project does not have',
	lines.length === 4 && lines.indexOf( TEXT['/_admin/design/confirm/copy'] ) !== -1 && question.options.message.indexOf( TEXT['/_admin/design/confirm/replaces'].split('%s')[0] ) === -1 );

/*	The paths are in a <details> that the dialog is given after it has drawn its text, behind it and before the buttons: closed, a summary, and a line for each file - the path
	and what happens to it, in words	*/
const detail = question.text.nextSibling;
const detailLines = byTag( detail, 'li' ).map( function( item ) { return [ byTag( item, 'code' )[0].textContent, byClass( item, 'design-confirm-state' )[0].textContent.trim() ] } );

check( 'the paths of the files are in a closed <details> under the text and before the buttons: its summary and a line for each file - the path, as a project knows it, and what happens to the file in words',
	detail !== null && detail.tagName === 'DETAILS' && hasClass( detail, 'design-confirm-files' ) && detail.getAttribute('open') === null && byTag( detail, 'summary' )[0].textContent === TEXT['/_admin/design/confirm/files']
	&& question.text.parent.children.indexOf( detail ) === question.text.parent.children.indexOf( question.buttons ) - 1
	&& JSON.stringify( detailLines ) === JSON.stringify( [
		[ 'assets/theme.css', TEXT['/_admin/design/confirm/state/foreign'] ],
		[ 'templates/frame-header.tpl', TEXT['/_admin/design/confirm/state/foreign'] ],
		[ 'templates/frame-footer.tpl', TEXT['/_admin/design/confirm/state/foreign'] ],
	] ) && hasClass( question.text, 'design-confirm-text' ) );

question.choose('apply');

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
declined.byId('design-apply').fire('click');
declined.answer( 200, {} );
declined.answer( 200, { files : delivered } );
declined.asks[0].choose('cancel');

check( 'no in the dialog sends no apply - it reloads the panel',
	JSON.stringify( endpoints( declined ) ) === JSON.stringify( [ 'list' ] ) && declined.asks.length === 1 && declined.confirms.length === 0 );

declined.answer( 200, listing( {} ) );

check( '...and says the draft is saved and not applied, in the line of the bar', ( declined.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/cancelled']
	&& declined.line().parent === declined.bar() && declined.line().title === TEXT['/_admin/design/msg/cancelled'] );
check( '...and the focus is on the button again: the screen was drawn anew from the answer, and the button that asked is gone with the old one - the dialog gave the focus back to it while it was switched off',
	focused === declined.byId('design-apply') && focused !== null );

// Files that are all Design's: no force, nothing replaced, nothing lost
const ours = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
ours.byId('design-apply').fire('click');
ours.answer( 200, {} );
ours.answer( 200, { files : [
	{ target : '/assets/theme.css', part : '', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', part : 'header', exists : true, state : 'ours', changes : false, lost : [] },
	{ target : '/templates/frame-footer.tpl', part : 'footer', exists : true, state : 'ours', changes : false, lost : [] },
] } );
const oursLines = ours.asks[0].options.message.split('\n');
const oursDetail = byTag( ours.asks[0].text.nextSibling, 'li' ).map( function( item ) { return byClass( item, 'design-confirm-state' )[0].textContent.trim() } );
ours.asks[0].choose('apply');

check( 'with every file Design\'s the apply is sent without force, and nothing is said of files that are not Design\'s',
	JSON.stringify( endpoints( ours ) ) === JSON.stringify( [ 'apply' ] ) && ours.requests[0].payload.force === false
	&& oursLines.some( function( line ) { return line.indexOf( TEXT['/_admin/design/confirm/foreign'].split('%s')[0] ) === 0 } ) === false );
check( '...a version that is about to be replaced is named by its date, and the details say of each file what happens to it: the one that changes is replaced, the ones that stay are as they are',
	oursLines.indexOf( say( '/_admin/design/confirm/replaces', '2026-09-02 08:30' ) ) !== -1
	&& JSON.stringify( oursDetail ) === JSON.stringify( [ TEXT['/_admin/design/confirm/state/ours'], TEXT['/_admin/design/confirm/state/same'], TEXT['/_admin/design/confirm/state/same'] ] ) );

// A frame somebody edited in beside a stylesheet that is Design's: the dead end
const edited = panel( {} );
edited.byId('design-apply').fire('click');
edited.answer( 200, {} );
edited.answer( 200, { files : [
	{ target : '/assets/theme.css', part : '', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-header.tpl', part : 'header', exists : true, state : 'ours', changes : true, lost : [] },
	{ target : '/templates/frame-footer.tpl', part : 'footer', exists : true, state : 'edited', changes : true, lost : [] },
] } );
const editedLines = edited.asks[0].options.message.split('\n');
edited.asks[0].choose('apply');

check( 'a frame that was only edited gets force too - the refusal has something to answer to now - and the dialog says which: by what it is for, and in the details as edited by hand',
	JSON.stringify( endpoints( edited ) ) === JSON.stringify( [ 'apply' ] ) && edited.requests[0].payload.force === true
	&& editedLines.indexOf( say( '/_admin/design/confirm/foreign', fileWord( 'footer' ) ) ) !== -1 );

/*	What reading the setup replaced is said once more in the dialog, in the words the notes use - the templates in one line, those that were not there and those that stand
	in their place, and the theme -, as far as it is still so of the draft: a template chosen since, or a theme named since, is no longer one the dialog has to say	*/
const replacedTemplates = function( over, before ) {
	const screen = panel( Object.assign( { notes : [ NOTE_THEME, noteSet( 'header', 'v9', 'first' ), noteSet( 'footer', 'v2', 'first' ) ] }, over || {} ) );
	if( typeof before === 'function' )
		before( screen );
	screen.byId('design-apply').fire('click');
	screen.answer( 200, {} );
	screen.answer( 200, { files : delivered } );
	return screen.asks[0].options.message.split('\n');
};
const replacedFirst = replacedTemplates();

check( 'where reading the setup replaced a theme and templates the dialog says it first: the templates in one line - those that were not there, then those that stand in their place, by the names the screen has for them, each in quotes since a name carries commas of its own - and the theme in the words of its note',
	replacedFirst[0] === say( '/_admin/design/confirm/replaced', '"v9", "v2"', '"Plain bar", "Plain footer"' ) && replacedFirst[1] === say( '/_admin/error/design_theme_missing', 'magazine' )
	&& replacedFirst.length === 6 && replacedFirst[2] === say( '/_admin/design/confirm/apply', [ fileWord( '' ), fileWord( 'header' ), fileWord( 'footer' ) ].join(', ') ) );

const movedOn = replacedTemplates( {}, function( screen ) { screen.tab('Global'); choose( screen.byId('design-set-header'), 'second' ) } );
const themedSince = replacedTemplates( { theme : 'mein-theme' } );

check( '...and a template that was chosen since is not said - the draft has moved past the note - nor is a theme that is named since: the dialog says what is so now',
	movedOn[0] === say( '/_admin/design/confirm/replaced', '"v2"', '"Plain footer"' ) && movedOn[1] === say( '/_admin/error/design_theme_missing', 'magazine' ) && movedOn.length === 6
	&& themedSince[0] === say( '/_admin/design/confirm/replaced', '"v9", "v2"', '"Plain bar", "Plain footer"' ) && themedSince.indexOf( say( '/_admin/error/design_theme_missing', 'magazine' ) ) === -1 && themedSince.length === 5
	&& replacedTemplates( { notes : [] } ).length === 4 && replacedTemplates( { notes : [ 'there is no part "nowhere" - dropped' ] } ).length === 4 );

const twice = panel();
let twiceAnswer = null;
twice.design._askApply( delivered, function() {} );
twice.design._askApply( delivered, function( yes ) { twiceAnswer = yes } );

check( 'a question that cannot be asked - the workbench has one standing - is answered no: nothing is sent and no second dialog is drawn',
	twiceAnswer === false && twice.asks.length === 1 && twice.requests.length === 0 );

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


// --- The Theme tab ---------------------------------------------------------------------------
//
// The whole setup under a name. The tab is three cards: the list of the themes, with the row
// that makes one out of the screen and the field that takes a file in; the active theme, with
// its name and its description, the chip that says the screen differs from it and the buttons
// that keep the screen in it and take it out as a file; and the css of the theme, which every
// input mirrors into the preview. What a button does is read off the requests it posts and the
// answers it is given: the working copy a load makes of a theme, the typefaces the theme names
// that the project has not installed - asked for before anything is drawn -, the browser's
// download and file reader the export and the import go through, and the sentence a refusal is
// told in. The answers the server really gave are the last half of this file

console.log( 'The Theme tab' );

/** The eight keys a theme keeps, out of an answer of design/list: the setup without the theme it names and the css it carries */
function snapshotOf( data ) {
	const copy = JSON.parse( JSON.stringify( data ) );
	const parts = {};
	copy.parts.forEach( function( row ) { parts[row.part] = row.set } );
	return { size : copy.size, width : copy.width, whitespace : copy.whitespace, motion : copy.motion, parts : parts, knobs : copy.knobs, fonts : copy.fonts, colours : copy.colours };
}

/** A theme as design/list gives the active one: its row of the list and what its file holds - the setup of the stand-in's own listing unless it is given */
function activeOf( slug, name, description, shipped, css, setup ) {
	return { slug : slug, name : name, description : description, shipped : shipped, setup : setup || snapshotOf( listing() ), css : css };
}

/** The list of the themes with one more at the end: the one a create, a duplicate or an import made */
function withTheme( slug, name, description ) {
	return THEMES.concat( [ { slug : slug, name : name, description : description, shipped : false } ] ).map( function( theme ) { return Object.assign( {}, theme ) } );
}

/** What the roles are offered with some families more, those that took the typefaces that were installed (the code is offered none of them: it takes a monospace) */
function optionsWith( slugs ) {
	const options = listing().options;
	[ 'title', 'subtitle', 'text', 'button' ].forEach( function( role ) { slugs.forEach( function( slug ) { options[role].push( slug ) } ) } );
	return options;
}

/** The row of the list for a theme, by its slug, in the screen that is drawn */
function themeRow( screen, slug ) {
	return byClass( screen.form, 'design-theme-row' ).filter( function( row ) { return row.dataset.slug === slug } )[0] || null;
}

/** The buttons at the end of the row of a theme */
function themeButtons( screen, slug ) {
	return byTag( byClass( themeRow( screen, slug ), 'design-theme-buttons' )[0], 'button' );
}

/** The slugs of the rows of the list, in the order they are drawn, as one string */
function slugsOf( screen ) {
	return byClass( screen.form, 'design-theme-row' ).map( function( row ) { return row.dataset.slug } ).join();
}

/** The words of the elements of a class below a node, as one string */
function textsOf( root, className ) {
	return byClass( root, className ).map( function( el ) { return el.textContent } ).join();
}

/** Every button of the first two cards of the tab, and the field of the file: what is switched off while a request of the tab is on its way */
function themeControls( screen ) {
	return byTag( screen.byId('design-themes'), 'button' ).concat( byTag( screen.byId('design-theme-current'), 'button' ), [ screen.byId('design-theme-import') ] );
}

/** The setup of a theme the way a file holds it: the stand-in's own, with the templates, the knob, the size and the fonts that are given in place */
function themeSetup( fonts ) {
	const setup = snapshotOf( listing() );
	setup.parts.header = 'second';
	setup.parts.content = 'third';
	setup.knobs.content['card-space'] = 1;
	setup.size = -1;
	Object.keys( fonts || {} ).forEach( function( role ) { setup.fonts[role].family = fonts[role] } );
	return setup;
}

/** What design/theme/load answers for the theme of the stand-in, naming the typefaces that are not installed here */
function themeLoad( slug, setup, missing ) {
	return { slug : slug, name : slug === 'magazine' ? 'Magazine' : 'Nino', description : slug === 'magazine' ? 'Lifted cards and a drop cap' : 'The framework as it is', shipped : true, setup : JSON.parse( JSON.stringify( setup ) ),
		css : slug === 'magazine' ? ".nino-section-title { border-bottom-color: red; }\n" : '', missing : missing, notes : [] };
}

const INTER = { slug : 'inter', name : 'Inter', inList : true };
const LORA = { slug : 'lora', name : 'Lora', inList : true };
const NOWHERE = { slug : 'nowhere-sans', name : 'nowhere-sans', inList : false };

/** A page answer for a preview that worked and has nothing to say */
function previewed( css, document ) {
	return { css : css, document : document, style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] };
}

const ownCss = ".typed { margin: 0 }\n";
const own = activeOf( 'mein-theme', 'My theme', 'The theme of this project', false, ownCss );

/** A screen on the Theme tab, the project's own theme the active one - the one the website runs as well, unless a test says another */
function themed( over ) {
	const screen = panel( Object.assign( { theme : 'mein-theme', running : 'mein-theme', css : ownCss, active : own }, over || {} ) );
	screen.tab('Theme');
	return screen;
}

// The three cards ------------------------------------------------------------------------------

const drawn = themed();
const cards = drawn.body().children;
const slugs = byClass( drawn.form, 'design-theme-row' ).map( function( row ) { return row.dataset.slug } );

check( 'the Theme tab is three cards in the column - the themes, the active theme and its css - each a card of the workbench with a title of its own, and no strip of groups',
	cards.map( function( card ) { return card.id } ).join() === 'design-themes,design-theme-current,design-theme-css-card'
	&& cards.every( function( card ) { return hasClass( card, 'nino-admin-card' ) && hasClass( card, 'design-block' ) } )
	&& cards.map( function( card ) { return byTag( card, 'strong' )[0].textContent } ).join() === 'Themes,Theme,Custom css' && drawn.groups() === null );
check( '...the list is a row for each theme in the order design/list gives them - the six the feature ships, then the one of the project - with its name, and under the name its description',
	slugs.join() === 'nino,magazine,poster,statement,product,office,mein-theme' && drawn.byId('design-theme-list').tagName === 'UL'
	&& slugs.every( function( slug, at ) {
		const row = themeRow( drawn, slug );
		return row.tagName === 'LI' && textsOf( row, 'design-theme-name' ) === THEMES[at].name && textsOf( row, 'design-theme-description' ) === THEMES[at].description && byTag( row, 'small' ).length === 1;
	} ) );
check( '...a chip "shipped" on each of the six the feature ships and on no other, a chip "active" on the theme the working copy names - and the mark of it on its row, to the eye and to whoever hears the list - both the workbench\'s own chip, in the colour of what it says',
	THEMES.every( function( theme ) { return byClass( themeRow( drawn, theme.slug ), 'design-theme-shipped' ).length === ( theme.shipped === true ? 1 : 0 ) } )
	&& byClass( drawn.form, 'design-theme-shipped' ).every( function( chip ) { return chip.textContent === 'shipped' && hasClass( chip, 'nino-admin-badge' ) && chip.dataset.state === 'muted' } )
	&& byClass( drawn.form, 'design-theme-active' ).length === 1 && textsOf( themeRow( drawn, 'mein-theme' ), 'design-theme-active' ) === 'active'
	&& byClass( drawn.form, 'design-theme-active' )[0].dataset.state === 'ok' && hasClass( byClass( drawn.form, 'design-theme-active' )[0], 'nino-admin-badge' )
	&& byClass( drawn.form, 'design-theme-row--active' ).length === 1 && hasClass( themeRow( drawn, 'mein-theme' ), 'design-theme-row--active' )
	&& themeRow( drawn, 'mein-theme' ).getAttribute('aria-current') === 'true' && themeRow( drawn, 'nino' ).getAttribute('aria-current') === null );
check( '...each row has Load and Duplicate, and a theme of the project has Delete as well - a shipped theme is a file of the feature and has none -, each button named by the theme it is about for whoever hears them read out',
	THEMES.every( function( theme ) {
		const buttons = themeButtons( drawn, theme.slug );
		return buttons.map( function( button ) { return button.id } ).join() === [ 'load', 'duplicate' ].concat( theme.shipped === true ? [] : [ 'delete' ] ).map( function( word ) { return 'design-theme-'+ word+ '-'+ theme.slug } ).join()
			&& buttons.map( function( button ) { return button.textContent } ).join() === ( theme.shipped === true ? 'Load,Duplicate' : 'Load,Duplicate,Delete' )
			&& buttons.every( function( button ) { return button.type === 'button' && button.getAttribute('aria-label') === button.textContent+ ' - '+ theme.name && button.title === button.getAttribute('aria-label') } );
	} )
	&& hasClass( drawn.byId('design-theme-delete-mein-theme'), 'nino-admin-btn-danger' ) && drawn.byId('design-theme-delete-nino') === null );

const makerRow = byClass( drawn.form, 'design-theme-new' )[0];
const newField = drawn.byId('design-theme-new');
const importField = drawn.byId('design-theme-import');

check( '...under the list the row that makes a theme of the screen: a text field for the name and the button Create, on one line',
	makerRow !== undefined && makerRow.parent === drawn.byId('design-themes') && newField.tagName === 'INPUT' && newField.parent.parent === makerRow && hasClass( newField.parent, 'nino-admin-field' )
	&& newField.parent.children[0].textContent === 'New theme from the current state' && newField.placeholder === 'Name' && newField.maxLength === 80
	&& makerRow.children.indexOf( drawn.byId('design-theme-create') ) === 1 && drawn.byId('design-theme-create').textContent === 'Create' && hasClass( drawn.byId('design-theme-create'), 'nino-admin-btn-primary' ) );
check( '...and the field that takes a file in: a labelled file input that asks for json',
	importField.tagName === 'INPUT' && importField.type === 'file' && importField.accept === '.json,application/json' && importField.parent.tagName === 'LABEL' && hasClass( importField.parent, 'nino-admin-field' )
	&& importField.parent.children[0].textContent === 'Import a theme file' && importField.parent.parent === drawn.byId('design-themes') );

// The active theme ------------------------------------------------------------------------------

const head = drawn.byId('design-theme-current-head');

check( 'the active theme is a head with its name, under it its two fields - the name and the description as they are typed, held to the lengths the server cuts them to - and the two buttons that keep the screen in it and take it out as a file; the screen is the theme, so there is no chip',
	textsOf( head, 'design-theme-current-name' ) === 'My theme' && drawn.byId('design-theme-changed') === null
	&& drawn.byId('design-theme-name').value === 'My theme' && drawn.byId('design-theme-name').maxLength === 80 && drawn.byId('design-theme-name').disabled === false
	&& drawn.byId('design-theme-description').value === 'The theme of this project' && drawn.byId('design-theme-description').maxLength === 400 && drawn.byId('design-theme-description').disabled === false
	&& drawn.byId('design-theme-name').parent.children[0].textContent === 'Name' && drawn.byId('design-theme-description').parent.children[0].textContent === 'Description'
	&& byTag( drawn.byId('design-theme-name').parent, 'small' ).length === 0
	&& drawn.byId('design-theme-save').textContent === 'Save theme' && hasClass( drawn.byId('design-theme-save'), 'nino-admin-btn-primary' ) && drawn.byId('design-theme-export').textContent === 'Export' );

const shippedActive = themed( { theme : 'magazine', running : 'magazine', active : activeOf( 'magazine', 'Magazine', 'Lifted cards and a drop cap', true, '' ) } );

check( '...a shipped theme is shown as it is and cannot be saved into: its name and description are read-only with the sentence that says why under the name, there is no button to save it - it can still be taken out as a file',
	shippedActive.byId('design-theme-name').disabled === true && shippedActive.byId('design-theme-description').disabled === true
	&& textsOf( shippedActive.byId('design-theme-name').parent, 'nino-admin-hint' ) === 'Shipped - duplicate it to change it.' && shippedActive.byId('design-theme-save') === null
	&& shippedActive.byId('design-theme-export') !== null && textsOf( shippedActive.byId('design-theme-list'), 'design-theme-active' ) === 'active' );

const unthemed = panel();
unthemed.tab('Theme');

check( '...with no theme the card says so and has nothing else - no name, no description, no button: the state of the screen belongs to none',
	textsOf( unthemed.byId('design-theme-current'), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/current/none'] && unthemed.byId('design-theme-name') === null && unthemed.byId('design-theme-description') === null
	&& unthemed.byId('design-theme-save') === null && unthemed.byId('design-theme-export') === null && byTag( unthemed.byId('design-theme-current'), 'button' ).length === 0
	&& unthemed.byId('design-theme-current-head') === null && byClass( unthemed.form, 'design-theme-active' ).length === 0 && byClass( unthemed.form, 'design-theme-row--active' ).length === 0
	&& slugsOf( unthemed ) === 'nino,magazine,poster,statement,product,office,mein-theme' );

// The css ---------------------------------------------------------------------------------------

const box = drawn.byId('design-theme-css');

check( 'the css of the theme is a textarea in a labelled field of its own - the label is the title of the card, kept for whoever hears the field and drawn out of sight - sized for code: fourteen rows, no spell check, no capital, no wrapping of a line',
	box.tagName === 'TEXTAREA' && box.parent.tagName === 'LABEL' && hasClass( box.parent, 'nino-admin-field' ) && box.parent.children[0].textContent === 'Custom css' && hasClass( box.parent.children[0], 'design-theme-css-name' )
	&& hasClass( box, 'design-theme-css' ) && box.rows === 14 && box.spellcheck === false && box.getAttribute('autocapitalize') === 'off' && box.getAttribute('wrap') === 'off'
	&& box.value === ownCss && box.parent.parent === drawn.byId('design-theme-css-card') );
check( '...with the hint under it: what the section does, what is refused, and the path of the project written as the shortcode it is, as it is',
	textsOf( drawn.byId('design-theme-css-card'), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/css/hint'] && TEXT['/_admin/design/theme/css/hint'].indexOf('[[/nino/public]]') !== -1 );

drawn.design._previewNow();
drawn.answer( 200, previewed( ':root{}', '<!doctype html>' ) );
const framed = drawn.byId('design-frame').srcdoc;

box.value = ".typed { margin: 1px }\n";
box.fire('input');

check( 'an input in the field sets the css of the working copy and asks for a preview - after the debounce every control has - and the screen is no longer the stored one: the way back is shown',
	drawn.design._edit.css === ".typed { margin: 1px }\n" && drawn.waiting( 350 ).length === 1 && drawn.design._isDirty() === true && drawn.byId('design-reset').hidden === false );
check( '...nor the theme it was taken from, which the chip in the head of the card says at once, in the colour of a thing to look at',
	drawn.byId('design-theme-changed') !== null && drawn.byId('design-theme-changed').textContent === 'changed' && drawn.byId('design-theme-changed').dataset.state === 'warn'
	&& hasClass( drawn.byId('design-theme-changed'), 'nino-admin-badge' ) && drawn.byId('design-theme-changed').parent === byTag( drawn.byId('design-theme-current-head'), 'strong' )[0] );

drawn.design._previewNow();

check( '...and the preview it asks for carries the css and the theme beside the rest of the selection',
	drawn.requests[drawn.requests.length - 1].endpoint === 'preview' && drawn.requests[drawn.requests.length - 1].payload.css === ".typed { margin: 1px }\n"
	&& drawn.requests[drawn.requests.length - 1].payload.theme === 'mein-theme' && drawn.requests[drawn.requests.length - 1].payload.full === false );

drawn.answer( 400, { error : 'a css must not carry an @import', code : 'design_import', field : 'css' } );

check( 'a css the server turns down is told in the line over the frame by the sentence of its code, in the colour of a failure - and the page in the frame stays as it was',
	drawn.byId('design-preview-msg').textContent === TEXT['/_admin/error/design_import'] && drawn.byId('design-preview-msg').title === TEXT['/_admin/error/design_import']
	&& hasClass( drawn.byId('design-preview-msg'), 'nino-admin-error' ) && drawn.byId('design-frame').srcdoc === framed && drawn.stage.style.textContent === '' );

box.value = ".typed { margin: 2px }\n";
box.fire('input');
drawn.design._previewNow();
drawn.answer( 200, previewed( ':root{}.typed{margin:2px}', '' ) );

check( '...and one it takes is swapped into the page that stands in the frame, the line over it empty and quiet again',
	drawn.stage.style.textContent === ':root{}.typed{margin:2px}' && drawn.byId('design-preview-msg').textContent === '' && hasClass( drawn.byId('design-preview-msg'), 'nino-admin-hint' )
	&& drawn.byId('design-frame').srcdoc === framed );

box.value = ownCss;
box.fire('input');

check( '...and the css put back as the theme has it, the chip goes with the difference; the way back goes with it',
	drawn.byId('design-theme-changed') === null && drawn.design._isDirty() === false && drawn.byId('design-reset').hidden === true );

drawn.design._edit.css = 'a{}';
check( '...a changed css is unsaved, and so is another theme with the same setup, and put back neither is',
	drawn.design._isDirty() === true && ( drawn.design._edit.css = ownCss, drawn.design._isDirty() ) === false
	&& ( drawn.design._edit.theme = 'magazine', drawn.design._isDirty() ) === true && ( drawn.design._edit.theme = 'mein-theme', drawn.design._isDirty() ) === false
	&& Object.keys( drawn.design._selection( drawn.design._data ) ).indexOf('theme') !== -1 && Object.keys( drawn.design._selection( drawn.design._data ) ).indexOf('css') !== -1 );

// What is typed stays while the panel lives ---------------------------------------------------------

const typing = themed();
const typedName = typing.byId('design-theme-name');
typedName.value = 'Typed name';
typedName.fire('input');
typing.byId('design-theme-description').value = 'Typed description';
typing.byId('design-theme-description').fire('input');
typing.byId('design-theme-new').value = 'A new one';
typing.byId('design-theme-new').fire('input');
typing.tab('Fonts');
typing.tab('Theme');

check( 'the name and the description typed for the active theme, and the name typed for a new one, are kept when another tab is opened and this one comes back: they are no part of the draft',
	typing.byId('design-theme-name').value === 'Typed name' && typing.byId('design-theme-description').value === 'Typed description' && typing.byId('design-theme-new').value === 'A new one'
	&& typing.design._themeText.name === 'Typed name' && typing.design._themeNew === 'A new one' && typing.design._isDirty() === false );

console.log('');

// --- Load ------------------------------------------------------------------------------------------
//
// A theme becomes the working copy and a draft: nothing is stored, the way back is shown. A
// typeface it names that the project has not installed is offered for install first, the ones the
// list of the Typefaces tab has by name, in one question

console.log( 'Load' );

const withFonts = themeSetup( { title : 'inter', text : 'nowhere-sans' } );
const loading = themed();
loading.byId('design-theme-load-magazine').fire('click');

check( 'Load posts design/theme/load with the slug and nothing else, and switches the buttons and the file field of the tab off until it is answered - a second action over the first would answer over a state the screen does not have',
	JSON.stringify( loading.requests.map( function( request ) { return [ request.endpoint, request.payload ] } ) ) === JSON.stringify( [ [ 'theme/load', { slug : 'magazine' } ] ] )
	&& themeControls( loading ).length === 19 && themeControls( loading ).every( function( control ) { return control.disabled === true } ) && loading.design._themeBusy === true && loading.line().textContent === '' );

loading.byId('design-theme-export').fire('click');
loading.byId('design-theme-load-poster').fire('click');
loading.byId('design-theme-create').fire('click');

check( '...and a press on another button meanwhile posts nothing', loading.requests.length === 1 );

loading.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );

check( 'an answer that names typefaces the project has not installed asks once, naming those the list of the Typefaces tab has - one it has not is not offered - and installs the first; nothing is drawn yet',
	loading.confirms.length === 1 && loading.confirms[0] === say( '/_admin/design/theme/confirm/fonts', 'Inter' )
	&& loading.requests.length === 1 && loading.requests[0].endpoint === 'webfonts/install' && JSON.stringify( loading.requests[0].payload ) === JSON.stringify( { slug : 'inter' } )
	&& loading.design._edit.theme === 'mein-theme' && loading.design._themeBusy === true );

loading.answer( 200, { msg : 'Inter 4.0 is installed.' } );

check( '...and when they are installed it reads design/list again, for the selects of the Fonts tab, before the screen is drawn',
	loading.requests.length === 1 && loading.requests[0].endpoint === 'list' && loading.design._edit.theme === 'mein-theme' && loading.design._data.options.title.indexOf('inter') === -1 );

loading.answer( 200, listing( { theme : 'mein-theme', css : ownCss, active : own, options : optionsWith( [ 'inter' ] ) } ) );

check( 'the theme is then the working copy - its templates, its knobs, its size, its css and the name of the theme; of the typefaces the one that was installed is in the title and the one the list does not have is the face of Nino',
	loading.design._edit.theme === 'magazine' && loading.design._edit.css === ".nino-section-title { border-bottom-color: red; }\n" && loading.design._edit.parts.header === 'second' && loading.design._edit.parts.content === 'third'
	&& loading.design._edit.parts.buttons === 'first' && loading.design._edit.knobs.content['card-space'] === 1 && loading.design._edit.size === -1
	&& loading.design._edit.fonts.title.family === 'inter' && loading.design._edit.fonts.text.family === '' && loading.design._edit.fonts.subtitle.family === 'open-sans' );
check( '...and the active theme: its record is the theme as it was taken over, the name and description fields are its own, and the screen is the theme, so there is no chip',
	loading.design._active.slug === 'magazine' && loading.design._active.shipped === true && loading.design._active.css === loading.design._edit.css
	&& JSON.stringify( loading.design._active.setup ) === JSON.stringify( loading.design._snapshotOf( loading.design._edit ) )
	&& loading.design._themeText.name === 'Magazine' && loading.byId('design-theme-name').value === 'Magazine' && loading.byId('design-theme-name').disabled === true
	&& textsOf( loading.byId('design-theme-current-head'), 'design-theme-current-name' ) === 'Magazine' && loading.byId('design-theme-changed') === null
	&& loading.byId('design-theme-css').value === loading.design._edit.css && hasClass( themeRow( loading, 'magazine' ), 'design-theme-row--active' ) && hasClass( themeRow( loading, 'mein-theme' ), 'design-theme-row--active' ) === false );
check( '...nothing is stored: the way back is shown and the shell would ask before it lost the draft; the buttons are on again, and the bar says what was installed, that the theme is loaded and which typeface the project lacks - in the colour of a note',
	loading.design._isDirty() === true && loading.byId('design-reset').hidden === false && loading.design._themeBusy === false && themeControls( loading ).every( function( control ) { return control.disabled === false } )
	&& loading.line().textContent === [ 'Inter 4.0 is installed.', say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'nowhere-sans' ) ].join(' ')
	&& loading.line().className === 'nino-admin-hint' && loading.requests.length === 0 );

loading.tab('Global');
const loadedHeader = loading.byId('design-set-header');
check( '...and the other tabs show the loaded values: the frame, the size, the template of the Content with the position of its knob',
	shown( loadedHeader ) === 'second' && loading.pressed('Page, root size') === [ -2, -1, 0, 1, 2 ].map( function( at ) { return String( at === -1 ) } ).join() );

loading.tab('Content');
loading.group('Card');
check( '...(Content, in the group the knob stands in)',
	shown( loading.byId('design-set-content') ) === 'third' && loading.pressed('Card, space') === [ -2, -1, 0, 1, 2 ].map( function( at ) { return String( at === 1 ) } ).join() );

loading.tab('Fonts');
check( '...(Fonts)', shown( loading.byId('design-font-title-family') ) === 'inter' && shown( loading.byId('design-font-text-family') ) === '' );

// A no installs nothing
const declining = themed();
declining.willConfirm( false );
declining.byId('design-theme-load-magazine').fire('click');
declining.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );

check( 'a no installs nothing and takes the theme over at once: the typefaces that are not here are the face of Nino, and every one of them is named in the bar, those the list has as well',
	declining.confirms.length === 1 && declining.requests.length === 0 && declining.design._edit.theme === 'magazine' && declining.design._edit.fonts.title.family === '' && declining.design._edit.fonts.text.family === ''
	&& declining.line().textContent === [ say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'Inter, nowhere-sans' ) ].join(' ') && declining.line().className === 'nino-admin-hint'
	&& declining.design._themeBusy === false );

// Two typefaces, installed one after the other; the first fails
const failingInstall = themed();
failingInstall.byId('design-theme-load-magazine').fire('click');
failingInstall.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter', text : 'lora' } ), [ INTER, LORA ] ) );

check( 'the typefaces are named together, in the order the theme names them',
	failingInstall.confirms[0] === say( '/_admin/design/theme/confirm/fonts', 'Inter, Lora' ) && failingInstall.requests[0].endpoint === 'webfonts/install' && failingInstall.requests[0].payload.slug === 'inter' );

failingInstall.answer( 500, { error : 'could not fetch the family' } );

check( '...an install that fails stops the chain - the next one is not asked for - and the list is read all the same; the theme is taken over, with the failure said in red before the rest',
	failingInstall.requests.length === 1 && failingInstall.requests[0].endpoint === 'list' );

failingInstall.answer( 200, listing( { theme : 'mein-theme', css : ownCss, active : own } ) );

check( '...(the theme is the working copy; the typefaces that did not come are the face of Nino, named)',
	failingInstall.design._edit.theme === 'magazine' && failingInstall.design._edit.fonts.title.family === '' && failingInstall.design._edit.fonts.text.family === ''
	&& failingInstall.line().textContent === [ say( '/_admin/design/typefaces/error/install', 'could not fetch the family' ), say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'Inter, Lora' ) ].join(' ')
	&& failingInstall.line().className === 'nino-admin-error' );

// The list that cannot be read after the installs
const unlisted = themed();
unlisted.byId('design-theme-load-magazine').fire('click');
unlisted.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter' } ), [ INTER ] ) );
unlisted.answer( 200, { msg : 'Inter 4.0 is installed.' } );
unlisted.answer( 500, {} );

check( 'a list that cannot be read after the installs does not hold the theme back: it is taken over with the families the selects had before - the typeface that was installed is not among them yet, so the title has the face of Nino and the bar names it',
	unlisted.requests.length === 0 && unlisted.design._edit.theme === 'magazine' && unlisted.design._edit.fonts.title.family === '' && unlisted.design._themeBusy === false
	&& unlisted.line().textContent === [ 'Inter 4.0 is installed.', say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'Inter' ) ].join(' ') );

// Both installed, one after the other
const installing = themed();
installing.byId('design-theme-load-magazine').fire('click');
installing.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter', text : 'lora' } ), [ INTER, LORA ] ) );
installing.answer( 200, { msg : 'Inter 4.0 is installed.' } );

check( 'a yes installs them one after the other: the second is asked for only when the first is answered',
	installing.requests.length === 1 && installing.requests[0].endpoint === 'webfonts/install' && installing.requests[0].payload.slug === 'lora' );

installing.answer( 200, { msg : 'Lora 1.0 is installed.' } );
installing.answer( 200, listing( { options : optionsWith( [ 'inter', 'lora' ] ), active : own, theme : 'mein-theme', css : ownCss } ) );

check( '...the list is read after the last, and what each install said is in the bar before the rest',
	installing.requests.length === 0 && installing.design._edit.theme === 'magazine' && installing.line().textContent.indexOf( 'Inter 4.0 is installed. Lora 1.0 is installed. ' ) === 0
	&& installing.design._edit.fonts.text.family === 'lora' && installing.design._edit.fonts.title.family === 'inter' );

// A theme with nothing missing is taken over without a question
const plainLoad = themed();
plainLoad.byId('design-theme-load-nino').fire('click');
plainLoad.answer( 200, themeLoad( 'nino', snapshotOf( listing() ), [] ) );

check( 'a theme that names nothing the project lacks is taken over without a question, the bar saying only that it is loaded - and quietly',
	plainLoad.confirms.length === 0 && plainLoad.requests.length === 0 && plainLoad.design._edit.theme === 'nino' && plainLoad.line().textContent === say( '/_admin/design/theme/msg/loaded', 'Nino' ) && plainLoad.line().className === ''
	&& plainLoad.design._isDirty() === true && plainLoad.byId('design-theme-changed') === null );

/*	A brand colour is the project's. A theme that names no colours - every theme the feature ships - is taken over with the colours the screen has, and the
	compare of the chip "changed" leaves the palette out where the theme has none: the colours of the screen are no change of a theme that says nothing of them	*/
const brandOf = function( screen ) { return screen.design._edit.colours.primary };
const withoutColours = function( setup ) {
	const copy = JSON.parse( JSON.stringify( setup ) );
	delete copy.colours;
	return copy;
};
const keeping = themed( { colours : Object.assign( {}, listing().colours, { primary : '#b5562a' } ) } );
keeping.byId('design-theme-load-magazine').fire('click');
keeping.answer( 200, themeLoad( 'magazine', withoutColours( themeSetup() ), [] ) );

check( 'a theme that names no colours is taken over with the colours the screen has - the brand colour is the project\'s - and with everything else the theme chooses',
	brandOf( keeping ) === '#b5562a' && keeping.design._edit.colours.mode === listing().colours.mode && keeping.design._edit.theme === 'magazine'
	&& keeping.design._edit.parts.header === 'second' && keeping.design._edit.parts.content === 'third' && keeping.design._edit.size === -1 && keeping.design._edit.knobs.content['card-space'] === 1 );
check( '...the record of the theme the chip compares with has no colours either, so the screen is the theme: no chip "changed" - a colour moved afterwards is no change of it, and a knob moved is',
	Object.prototype.hasOwnProperty.call( keeping.design._active.setup, 'colours' ) === false && keeping.byId('design-theme-changed') === null && keeping.design._themeChanged() === false
	&& ( keeping.design._edit.colours.primary = '#112233', keeping.design._themeChanged() ) === false
	&& ( keeping.design._edit.size = 1, keeping.design._themeChanged() ) === true );

const adopting = themed( { colours : Object.assign( {}, listing().colours, { primary : '#b5562a' } ) } );
const colourful = themeSetup();
colourful.colours = Object.assign( {}, colourful.colours, { primary : '#2e7d32' } );
adopting.byId('design-theme-load-magazine').fire('click');
adopting.answer( 200, themeLoad( 'magazine', colourful, [] ) );

check( 'a theme that names colours - one of the project, written from the screen with them - takes them over, and its record has them: the compare reads the palette again',
	brandOf( adopting ) === '#2e7d32' && adopting.design._active.setup.colours.primary === '#2e7d32' && adopting.design._themeChanged() === false
	&& ( adopting.design._edit.colours.primary = '#112233', adopting.design._themeChanged() ) === true );

/*	The chips of a row. "Active" is the theme the website runs - the one of the last apply -, and a theme the screen was taken from that the website does not run says
	so: loaded, not applied. The theme that is both is told once, as the one the website runs	*/
const chipsOf = function( screen, slug ) {
	return byClass( themeRow( screen, slug ), 'nino-admin-badge' ).filter( function( chip ) { return hasClass( chip, 'design-theme-shipped' ) === false } ).map( function( chip ) { return chip.textContent } );
};
const running = themed();
const otherRuns = themed( { running : 'nino' } );
const neverApplied = themed( { running : '' } );
const loadedOne = themed();
loadedOne.byId('design-theme-load-magazine').fire('click');
loadedOne.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( 'the chip "active" is on the theme the website runs - the draft of it, where the draft is that theme, and the one the last apply was made from where it is another - and on no other',
	chipsOf( running, 'mein-theme' ).join() === TEXT['/_admin/design/theme/active'] && byClass( running.form, 'design-theme-loaded' ).length === 0 && byClass( running.form, 'design-theme-active' ).length === 1
	&& chipsOf( otherRuns, 'nino' ).join() === TEXT['/_admin/design/theme/active'] && byClass( otherRuns.form, 'design-theme-active' ).length === 1
	&& byClass( neverApplied.form, 'design-theme-active' ).length === 0 );
check( '...a theme the screen was taken from that the website does not run says that it is loaded and not applied - the chip of a draft is a warning, "active" is the other - where one is none the website runs, or another',
	chipsOf( otherRuns, 'mein-theme' ).join() === TEXT['/_admin/design/theme/loaded'] && chipsOf( neverApplied, 'mein-theme' ).join() === TEXT['/_admin/design/theme/loaded']
	&& byClass( otherRuns.form, 'design-theme-loaded' ).length === 1 && byClass( otherRuns.form, 'design-theme-loaded' )[0].dataset.state === 'warn'
	&& hasClass( byClass( otherRuns.form, 'design-theme-loaded' )[0], 'nino-admin-badge' ) && TEXT['/_admin/design/theme/loaded'] !== TEXT['/_admin/design/theme/active'] );
check( 'a theme that was just loaded is not the one the website runs, and says so: the chip of the one that is loaded is "loaded, not applied", the one the website runs keeps its "active" - and the row of the draft is the one marked as current',
	chipsOf( loadedOne, 'magazine' ).join() === TEXT['/_admin/design/theme/loaded'] && chipsOf( loadedOne, 'mein-theme' ).join() === TEXT['/_admin/design/theme/active']
	&& byClass( loadedOne.form, 'design-theme-active' ).length === 1 && byClass( loadedOne.form, 'design-theme-loaded' ).length === 1
	&& themeRow( loadedOne, 'magazine' ).getAttribute('aria-current') === 'true' && themeRow( loadedOne, 'mein-theme' ).getAttribute('aria-current') === null );

/*	What reading a theme replaced is listed over the tab, instead of what was listed before; the way back lists the notes of the stored setup again. The bar says that the theme is loaded, and no more	*/
const rereading = themed( { notes : [ NOTE_THEME ] } );

check( 'the notes of the stored setup are listed over the tab',
	JSON.stringify( noteLines( rereading ) ) === JSON.stringify( [ say( '/_admin/error/design_theme_missing', 'magazine' ) ] ) );

rereading.byId('design-theme-load-magazine').fire('click');
rereading.answer( 200, Object.assign( themeLoad( 'magazine', themeSetup(), [] ), { notes : [ noteSet( 'header', 'v9', 'second' ) ] } ) );

check( 'a theme that is loaded replaces them with the notes of what was read of it - and the bar says that it is loaded, quietly: the notes are over the tab, not in the line',
	JSON.stringify( noteLines( rereading ) ) === JSON.stringify( [ say( '/_admin/error/design_set_missing', TEXT['/_admin/design/part/header'], 'v9', 'Centred' ) ] )
	&& rereading.line().textContent === say( '/_admin/design/theme/msg/loaded', 'Magazine' ) && rereading.line().className === '' );

rereading.byId('design-reset').fire('click');

check( '...and the way back lists the notes of the stored setup again, which is what the screen is then',
	JSON.stringify( noteLines( rereading ) ) === JSON.stringify( [ say( '/_admin/error/design_theme_missing', 'magazine' ) ] ) );

const cleaning = themed( { notes : [ NOTE_THEME, noteSet( 'header', 'v9', 'first' ) ] } );
cleaning.byId('design-theme-load-magazine').fire('click');
cleaning.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( 'a theme that had nothing replaced lists nothing: the notes of the screen it replaces are gone, over the tab as well as after another tab is opened',
	noteLines( cleaning ).length === 0 && ( cleaning.tab('Global'), noteLines( cleaning ).length === 0 ) && ( cleaning.tab('Theme'), noteLines( cleaning ).length === 0 ) );

// A load that is refused
const refusedLoad = themed();
refusedLoad.byId('design-theme-load-magazine').fire('click');
refusedLoad.answer( 404, { error : 'there is no such theme', code : 'design_theme' } );

check( 'a load the server refuses says why by the sentence of its code, in red, switches the buttons on again and changes nothing',
	refusedLoad.line().textContent === TEXT['/_admin/error/design_theme'] && refusedLoad.line().className === 'nino-admin-error' && refusedLoad.design._edit.theme === 'mein-theme'
	&& themeControls( refusedLoad ).every( function( control ) { return control.disabled === false } ) && refusedLoad.design._isDirty() === false );

// A late answer
const late = themed();
late.byId('design-theme-load-magazine').fire('click');
late.byId('design-reset').fire('click');

check( 'the way back while a load is on its way switches the buttons on again',
	late.design._themeBusy === false && themeControls( late ).every( function( control ) { return control.disabled === false } ) );

late.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( '...and the answer that comes after it draws over nothing: the screen is the stored selection and the bar says it was reverted',
	late.design._edit.theme === 'mein-theme' && late.design._isDirty() === false && late.line().textContent === TEXT['/_admin/design/msg/reverted'] && late.confirms.length === 0 );

console.log('');

// --- Create, save, duplicate, delete -----------------------------------------------------------------
//
// A theme is made of the screen under a name, and the screen is then in it - the draft names it, which
// the way back takes away again, for nothing is stored until the draft is. Saving puts the screen into
// the theme, with the name and the description as they are typed

console.log( 'Create, save, duplicate, delete' );

const making = panel();
making.tab('Theme');
making.byId('design-theme-new').value = 'Mein Name';
making.byId('design-theme-new').fire('input');
making.byId('design-theme-create').fire('click');

check( 'Create posts design/theme/create with the name that is typed, an empty description and the working copy: the templates, the knobs, the size, the width, the animation, the fonts, the palette, the theme and the css',
	making.requests.length === 1 && making.requests[0].endpoint === 'theme/create'
	&& Object.keys( making.requests[0].payload ).sort().join() === 'colours,css,description,fonts,knobs,motion,name,parts,size,theme,whitespace,width'
	&& making.requests[0].payload.name === 'Mein Name' && making.requests[0].payload.description === '' && making.requests[0].payload.theme === '' && making.requests[0].payload.css === ''
	&& JSON.stringify( making.requests[0].payload.parts ) === JSON.stringify( making.design._edit.parts ) && JSON.stringify( making.requests[0].payload.knobs ) === JSON.stringify( making.design._edit.knobs )
	&& themeControls( making ).every( function( control ) { return control.disabled === true } ) );

making.answer( 200, { slug : 'mein-name', themes : withTheme( 'mein-name', 'Mein Name', '' ), active : activeOf( 'mein-name', 'Mein Name', '', false, '' ) } );

check( 'the answer makes the draft name the theme - the way back is shown, for nothing is stored - and the card of the active theme shows its name, with its fields: the name it was given and no description; no chip, the screen is the theme',
	making.design._edit.theme === 'mein-name' && making.design._active.slug === 'mein-name' && making.design._data.theme === '' && making.design._isDirty() === true && making.byId('design-reset').hidden === false
	&& textsOf( making.byId('design-theme-current-head'), 'design-theme-current-name' ) === 'Mein Name' && making.byId('design-theme-name').value === 'Mein Name' && making.byId('design-theme-description').value === ''
	&& making.byId('design-theme-changed') === null && making.byId('design-theme-save') !== null );
check( '...the list is drawn again with the new theme, last, active; the field of the new name is empty again and the buttons are on',
	slugsOf( making ) === 'nino,magazine,poster,statement,product,office,mein-theme,mein-name' && hasClass( themeRow( making, 'mein-name' ), 'design-theme-row--active' ) && byClass( making.form, 'design-theme-row--active' ).length === 1
	&& making.byId('design-theme-new').value === '' && making.design._themeNew === '' && themeControls( making ).every( function( control ) { return control.disabled === false } )
	&& making.line().textContent === say( '/_admin/design/theme/msg/created', 'Mein Name' ) && making.line().className === '' );

making.tab('Content');
making.moveIn( 'Hero', 'Title (Hero), text size', 1 );
making.tab('Theme');

check( 'a knob moved afterwards shows the chip "changed" in the head of the card when the tab comes back: the screen is no longer the theme',
	making.byId('design-theme-changed') !== null && making.byId('design-theme-changed').textContent === 'changed' );

making.byId('design-theme-name').value = 'Neuer Name';
making.byId('design-theme-name').fire('input');
making.byId('design-theme-description').value = 'Neue Beschreibung';
making.byId('design-theme-description').fire('input');
making.byId('design-theme-save').fire('click');

check( 'Save theme posts design/theme/save with the slug, the two fields as they are typed and the working copy - the name does not change the slug',
	making.requests.length === 1 && making.requests[0].endpoint === 'theme/save' && making.requests[0].payload.slug === 'mein-name'
	&& making.requests[0].payload.name === 'Neuer Name' && making.requests[0].payload.description === 'Neue Beschreibung' && making.requests[0].payload.theme === 'mein-name'
	&& making.requests[0].payload.knobs.content['hero-title'] === 1 && Object.keys( making.requests[0].payload ).sort().join() === 'colours,css,description,fonts,knobs,motion,name,parts,size,slug,theme,whitespace,width' );

making.answer( 200, {
	themes : withTheme( 'mein-name', 'Neuer Name', 'Neue Beschreibung' ),
	active : activeOf( 'mein-name', 'Neuer Name', 'Neue Beschreibung', false, '', JSON.parse( JSON.stringify( making.design._snapshotOf( making.design._edit ) ) ) ),
} );

check( 'the answer is the theme as the server holds it: the chip goes, the list and the card say the new name and description, the bar says it was saved',
	making.byId('design-theme-changed') === null && textsOf( making.byId('design-theme-current-head'), 'design-theme-current-name' ) === 'Neuer Name' && textsOf( themeRow( making, 'mein-name' ), 'design-theme-name' ) === 'Neuer Name'
	&& textsOf( themeRow( making, 'mein-name' ), 'design-theme-description' ) === 'Neue Beschreibung' && making.design._themeText.name === 'Neuer Name' && making.design._themeText.description === 'Neue Beschreibung'
	&& making.design._active.name === 'Neuer Name' && making.line().textContent === say( '/_admin/design/theme/msg/saved', 'Neuer Name' ) && making.design._data.theme === '' );

// Enter in the field of the new name
const entering = panel();
entering.tab('Theme');
entering.byId('design-theme-new').value = 'By enter';
entering.byId('design-theme-new').fire('input');
press( entering.byId('design-theme-new'), 'Enter' );

check( 'Enter in the field of the new name creates, the way the button does', entering.requests.length === 1 && entering.requests[0].endpoint === 'theme/create' && entering.requests[0].payload.name === 'By enter' );

// Saving the theme that is stored changes what the way back goes back to
const stored = themed();
stored.byId('design-theme-name').value = 'Renamed';
stored.byId('design-theme-name').fire('input');
stored.byId('design-theme-save').fire('click');
stored.answer( 200, { themes : stored.design._data.themes.map( function( theme ) { return theme.slug === 'mein-theme' ? Object.assign( {}, theme, { name : 'Renamed' } ) : theme } ), active : activeOf( 'mein-theme', 'Renamed', 'The theme of this project', false, ownCss ) } );
stored.design._edit.css = 'a{}';
stored.byId('design-reset').fire('click');

check( 'the theme the stored setup names, saved, is what the way back goes back to: the name it has now',
	stored.design._active.name === 'Renamed' && stored.design._data.active.name === 'Renamed' && stored.design._themeText.name === 'Renamed' && stored.design._edit.css === ownCss && stored.design._isDirty() === false );

// Duplicate
const copying = themed();
copying.byId('design-theme-duplicate-magazine').fire('click');

check( 'Duplicate posts design/theme/duplicate with the slug - a shipped theme too, which is how it is changed', copying.requests.length === 1 && copying.requests[0].endpoint === 'theme/duplicate'
	&& JSON.stringify( copying.requests[0].payload ) === JSON.stringify( { slug : 'magazine' } ) );

copying.answer( 200, { slug : 'magazine-copy', themes : withTheme( 'magazine-copy', 'Magazine copy', 'Lifted cards and a drop cap' ) } );

check( '...the list is drawn again with the copy in it and nothing is loaded: the working copy and the active theme are as they were; the bar names both',
	slugsOf( copying ) === 'nino,magazine,poster,statement,product,office,mein-theme,magazine-copy' && copying.design._edit.theme === 'mein-theme' && copying.design._isDirty() === false
	&& hasClass( themeRow( copying, 'mein-theme' ), 'design-theme-row--active' ) && themeButtons( copying, 'magazine-copy' ).length === 3
	&& copying.line().textContent === say( '/_admin/design/theme/msg/duplicated', 'Magazine', 'Magazine copy' ) && themeControls( copying ).every( function( control ) { return control.disabled === false } ) );

// Delete
const deleting = themed();
deleting.willConfirm( false );
deleting.byId('design-theme-delete-mein-theme').fire('click');

check( 'Delete asks first, naming the theme, and a no does nothing',
	deleting.confirms.length === 1 && deleting.confirms[0] === say( '/_admin/design/theme/confirm/delete', 'My theme' ) && deleting.requests.length === 0 && deleting.design._themeBusy === false );

deleting.willConfirm( true );
deleting.byId('design-theme-delete-mein-theme').fire('click');

check( '...a yes posts design/theme/delete with the slug', deleting.requests.length === 1 && deleting.requests[0].endpoint === 'theme/delete' && JSON.stringify( deleting.requests[0].payload ) === JSON.stringify( { slug : 'mein-theme' } ) );

deleting.answer( 200, { themes : THEMES.filter( function( theme ) { return theme.shipped === true } ), theme : '' } );

check( 'the answer takes the theme out of the list; it was the active one, so the screen has none now: the card says so, and the stored setup names none either, so the screen is still the stored selection',
	slugsOf( deleting ) === 'nino,magazine,poster,statement,product,office' && deleting.design._edit.theme === '' && deleting.design._active === null && deleting.design._data.theme === '' && deleting.design._data.active === null
	&& textsOf( deleting.byId('design-theme-current'), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/current/none'] && deleting.byId('design-theme-name') === null && byClass( deleting.form, 'design-theme-active' ).length === 0
	&& deleting.design._themeText.name === '' && deleting.design._isDirty() === false && deleting.line().textContent === say( '/_admin/design/theme/msg/deleted', 'My theme' ) );

// Deleting a theme that is not the active one leaves the screen alone
const deletingOther = themed( { themes : withTheme( 'other', 'Other', '' ) } );
deletingOther.byId('design-theme-delete-other').fire('click');
deletingOther.answer( 200, { themes : THEMES.map( function( theme ) { return Object.assign( {}, theme ) } ), theme : 'mein-theme' } );

check( '...a theme that is not the active one is taken out of the list and the screen stays as it is, the stored setup still naming its theme',
	slugsOf( deletingOther ) === 'nino,magazine,poster,statement,product,office,mein-theme' && deletingOther.design._edit.theme === 'mein-theme' && deletingOther.design._active.slug === 'mein-theme'
	&& deletingOther.design._data.theme === 'mein-theme' && deletingOther.design._data.active.slug === 'mein-theme' && deletingOther.byId('design-theme-name').value === 'My theme' );

// A delete that comes after the screen was reverted
const lateDelete = themed();
lateDelete.byId('design-theme-delete-mein-theme').fire('click');
lateDelete.byId('design-reset').fire('click');
lateDelete.answer( 200, { themes : [], theme : '' } );

check( '...and an answer that comes after the way back changes nothing on the screen', slugsOf( lateDelete ) === 'nino,magazine,poster,statement,product,office,mein-theme' && lateDelete.design._edit.theme === 'mein-theme' );

console.log('');


// --- Export and import ----------------------------------------------------------------------------------
//
// A theme leaves as a file the way the Language panel's export does - a blob, a link clicked and taken
// away, the address given back - and comes in through the file field: read as text, posted whole

console.log( 'Export and import' );

const exporting = themed();
exporting.byId('design-theme-export').fire('click');

check( 'Export posts design/theme/export with the slug of the active theme', exporting.requests.length === 1 && exporting.requests[0].endpoint === 'theme/export'
	&& JSON.stringify( exporting.requests[0].payload ) === JSON.stringify( { slug : 'mein-theme' } ) );

exporting.answer( 200, { json : '{\n\t"format": 1\n}', file : 'nino-theme-mein-theme.json' } );

check( '...and turns the answer into a download: a blob of its text as json, an address for it, a link to the address with the name of the file, clicked while it stands in the document and taken out of it after, the address given back',
	exporting.blobs.length === 1 && exporting.blobs[0].parts.join() === '{\n\t"format": 1\n}' && exporting.blobs[0].type === 'application/json'
	&& exporting.urls.made.length === 1 && exporting.urls.made[0] === exporting.blobs[0]
	&& exporting.downloads.length === 1 && exporting.downloads[0].href === 'blob:design/1' && exporting.downloads[0].download === 'nino-theme-mein-theme.json' && exporting.downloads[0].mounted === true
	&& exporting.docBody.children.length === 0 && exporting.urls.revoked.join() === 'blob:design/1' );
check( '...the bar says it was exported, naming the theme, and the buttons are on again',
	exporting.line().textContent === say( '/_admin/design/theme/msg/exported', 'My theme' ) && themeControls( exporting ).every( function( control ) { return control.disabled === false } ) );

const importing = themed();
importing.byId('design-theme-import').files = [];
importing.byId('design-theme-import').fire('change');

check( 'a file field that changed to no file does nothing', importing.readers.length === 0 && importing.requests.length === 0 );

const picked = { name : 'theme.json' };
importing.byId('design-theme-import').files = [ picked ];
importing.byId('design-theme-import').value = 'C:\\fakepath\\theme.json';
importing.byId('design-theme-import').fire('change');

check( 'a file is read as text - nothing is posted before the read is done',
	importing.readers.length === 1 && importing.readers[0].file === picked && importing.requests.length === 0 );

importing.readers[0].finish( '{"format":1,"name":"Imported"}' );

check( '...the field is emptied, so that the same file can be chosen again, and design/theme/import is posted with the text, as it is',
	importing.byId('design-theme-import').value === '' && importing.requests.length === 1 && importing.requests[0].endpoint === 'theme/import'
	&& JSON.stringify( importing.requests[0].payload ) === JSON.stringify( { json : '{"format":1,"name":"Imported"}' } ) && importing.byId('design-theme-import').disabled === true );

importing.answer( 200, { slug : 'imported', themes : withTheme( 'imported', 'Imported', '' ) } );

check( '...the list is drawn again with the theme that came in - not loaded: the screen is as it was - and the bar says it was imported',
	slugsOf( importing ) === 'nino,magazine,poster,statement,product,office,mein-theme,imported' && importing.design._edit.theme === 'mein-theme' && importing.design._isDirty() === false
	&& importing.line().textContent === say( '/_admin/design/theme/msg/imported', 'Imported' ) && themeControls( importing ).every( function( control ) { return control.disabled === false } ) );

importing.byId('design-theme-import').files = [ picked ];
importing.byId('design-theme-import').fire('change');
importing.readers[1].fail();

check( 'a file that cannot be read is told by the sentence of the import, in red, and posts nothing',
	importing.requests.length === 0 && importing.line().textContent === TEXT['/_admin/design/theme/error/import'] && importing.line().className === 'nino-admin-error' );

// A refused import says why by the code
const refusing = themed();
refusing.byId('design-theme-import').files = [ picked ];
refusing.byId('design-theme-import').fire('change');
refusing.readers[0].finish( 'not json' );
refusing.answer( 400, { error : 'that is no theme file of this feature', code : 'design_file' } );

check( 'a file that is no theme file is told by the sentence of its code, in red; the buttons are on again and the list is as it was',
	refusing.line().textContent === TEXT['/_admin/error/design_file'] && refusing.line().className === 'nino-admin-error' && themeControls( refusing ).every( function( control ) { return control.disabled === false } )
	&& slugsOf( refusing ) === 'nino,magazine,poster,statement,product,office,mein-theme' );

refusing.byId('design-theme-import').files = [ picked ];
refusing.byId('design-theme-import').fire('change');
refusing.readers[1].finish( '{"name":"Imported"}' );
refusing.answer( 409, { error : 'a theme with the slug "imported" exists already', code : 'design_taken', params : [ 'imported' ] } );

check( '...a slug that is taken says which, the slug in the sentence of its code', refusing.line().textContent === say( '/_admin/error/design_taken', 'imported' ) && refusing.line().textContent.indexOf('imported') !== -1 );

console.log('');


// --- What goes wrong ------------------------------------------------------------------------------------
//
// A failure of an action says its sentence in the line of the bar, in red: the sentence of its code
// where the server gave one a person can cause, else the sentence the server wrote with the status in
// front; the workbench's text of the action stands behind both for an answer that says nothing. The
// buttons are on again either way and the screen is as it was

console.log( 'What goes wrong' );

const actions = {
	load 				: function( screen ) { screen.byId('design-theme-load-magazine').fire('click') },
	create			: function( screen ) { screen.byId('design-theme-new').value = 'x'; screen.byId('design-theme-new').fire('input'); screen.byId('design-theme-create').fire('click') },
	save 				: function( screen ) { screen.byId('design-theme-save').fire('click') },
	duplicate		: function( screen ) { screen.byId('design-theme-duplicate-magazine').fire('click') },
	'delete'		: function( screen ) { screen.byId('design-theme-delete-mein-theme').fire('click') },
	'export'		: function( screen ) { screen.byId('design-theme-export').fire('click') },
	'import'		: function( screen ) {
		screen.byId('design-theme-import').files = [ picked ];
		screen.byId('design-theme-import').fire('change');
		screen.readers[0].finish( '{}' );
	},
};

let said = [];
let spokenFailures = [];
let coded = [];
let unchanged = [];

Object.keys( actions ).forEach( function( action ) {

	const bare = themed();
	actions[action]( bare );
	bare.answer( 500, {} );

	if( bare.line().textContent !== '(500) '+ TEXT['/_admin/design/theme/error/'+ action] || bare.line().className !== 'nino-admin-error' || bare.line().title !== bare.line().textContent )
		said.push( action+ ': '+ bare.line().textContent );

	const spoken = themed();
	actions[action]( spoken );
	spoken.answer( 500, { error : 'could not write /data/themes/mein-theme.php' } );

	if( spoken.line().textContent !== '(500) could not write /data/themes/mein-theme.php' || spoken.line().className !== 'nino-admin-error' )
		spokenFailures.push( action+ ': '+ spoken.line().textContent );

	const refusedBy = themed();
	actions[action]( refusedBy );
	refusedBy.answer( 400, { error : 'the name has no letter or digit in it', code : 'design_name' } );

	if( refusedBy.line().textContent !== TEXT['/_admin/error/design_name'] || refusedBy.line().className !== 'nino-admin-error' )
		coded.push( action+ ': '+ refusedBy.line().textContent );

	if( themeControls( refusedBy ).some( function( control ) { return control.disabled === true } ) || refusedBy.design._themeBusy === true || refusedBy.design._isDirty() === true
		|| refusedBy.design._edit.theme !== 'mein-theme' || refusedBy.requests.length !== 0 || refusedBy.confirms.length !== ( action === 'delete' ? 1 : 0 ) )
		unchanged.push( action );
} );

check( 'an action that fails without a word of its own says the sentence of its key behind the status, in red, with the whole of it as the title of the line - for each of the seven'+ ( said.length === 0 ? '' : ' - '+ said.join(' | ') ),
	Object.keys( actions ).length === 7 && said.length === 0 );
check( '...the sentence of the server where it wrote one, the same way'+ ( spokenFailures.length === 0 ? '' : ' - '+ spokenFailures.join(' | ') ), spokenFailures.length === 0 );
check( '...and a failure with a code says the sentence of the code, for each of the seven'+ ( coded.length === 0 ? '' : ' - '+ coded.join(' | ') ), coded.length === 0 );
check( '...and the buttons are on again and the screen is as it was: no draft, no theme changed, nothing asked for after it'+ ( unchanged.length === 0 ? '' : ' - '+ unchanged.join() ), unchanged.length === 0 );

console.log('');


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

	check( 'the server\'s answer is in the shape the screen draws: the tabs name the kind of each knob - a scale or a choice - and are the kinds the stand-in has, the steps are five words, the size, the width and the whitespace are numbers with no list of sizes beside them, and the palette gives each knob its kind',
		real.steps.join() === 'least,less,default,more,most' && typeof real.size === 'number' && typeof real.width === 'number' && typeof real.whitespace === 'number' && real.sizes === undefined
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

	check( 'the server\'s answer draws all eight tabs, each with controls in it and the state of the files on the chip in the bar'+ ( failed.length === 0 ? '' : ' - threw: '+ failed.join() ),
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
	check( '...the size, the width and the whitespace, three strips, on the numbers stored, and the animation on the values stored',
		whole.pressed('Page, root size') === pressedAt( real.size ) && whole.pressed('Page, width') === pressedAt( real.width ) && whole.pressed('Page, whitespace') === pressedAt( real.whitespace )
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
		// The names in the order the tab shows them in: the knobs of its groups one after the other where it has groups, else as the tab lists them
		const grouped = ( real.groups || {} )[tab];
		const names = grouped === undefined ? Object.keys( real.tabs[tab] ) : [].concat.apply( [], Object.keys( grouped ).map( function( key ) { return grouped[key] } ) );
		const drawnRows = whole.allRows();

		if( selectOf( row ) === false || whole.byId('design-knobs').children[0] !== whole.byId('design-set-'+ tab).parent )
			tabProblems.push( tab+ ': the select of its template, first in the card' );
		const off = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) } ).map( function( one ) { return one.dataset.knob } );
		const lit = drawnRows.filter( function( one ) { return hasClass( one, 'design-knob-row--off' ) === false } ).every( function( one ) {
			const position = real.knobs[tab][one.dataset.knob];
			const reach = real.tabs[tab][one.dataset.knob] === 'scale' ? [ -2, -1, 0, 1, 2 ] : [ -1, 0, 1 ];
			return byTag( one, 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() === reach.map( function( at ) { return String( at === position ) } ).join();
		} );

		if( drawnRows.map( function( one ) { return one.dataset.knob } ).join() !== names.join() || names.slice().sort().join() !== Object.keys( real.tabs[tab] ).sort().join() )
			tabProblems.push( tab+ ': rows '+ drawnRows.map( function( one ) { return one.dataset.knob } ).join() );
		if( off.join() !== names.filter( function( knob ) { return answered.indexOf( knob ) === -1 } ).join() )
			tabProblems.push( tab+ ': grey rows '+ off.join() );
		if( lit === false )
			tabProblems.push( tab+ ': a step is lit that the knob does not stand at' );
		if( whole.litAll().length !== answered.length )
			tabProblems.push( tab+ ': '+ whole.litAll().length+ ' rows lit for '+ answered.length+ ' knobs' );
	} );

	check( 'each knob tab is the select of its template over a strip for every knob of the tab, grey where the chosen template does not answer to it, and the others lit on the position stored - on five buttons for a scale, three for a choice'
		+ ( tabProblems.length === 0 ? '' : ' - '+ tabProblems.join(' | ') ), tabProblems.length === 0 && Object.keys( real.tabs ).join() === 'content,buttons,components' );
	check( '...the Content is drawn in the groups the server gave and only there: a strip of tabs, one for each group in the order of the answer, named in the panel\'s words, and every knob of the tab in exactly one group',
		Object.keys( real.groups || {} ).join() === 'content' && Object.keys( real.groups.content ).join() === 'hero,section,card,variants'
		&& ( function() {
			whole.tab('Content');
			return byTag( whole.groups(), 'button' ).map( function( button ) { return button.textContent } ).join() === Object.keys( real.groups.content ).map( function( key ) { return TEXT['/_admin/design/group/content/'+ key+ '/label'] } ).join();
		} )()
		&& [].concat.apply( [], Object.keys( real.groups.content ).map( function( key ) { return real.groups.content[key] } ) ).sort().join() === Object.keys( real.tabs.content ).sort().join()
		&& [ 'buttons', 'components' ].every( function( tab ) { return whole.tab( TEXT['/_admin/design/tab/'+ tab] ) === undefined && whole.groups() === null } ) );
	check( '...and every template the library ships has a name and answers to knobs of its own tab, in the order of the tab - a template that leaves one out is a grey row for it, as the check above holds - and the one that is chosen is one of the catalogue',
		Object.keys( real.tabs ).every( function( tab ) {
			const order = Object.keys( real.tabs[tab] );
			return Object.keys( rowOf( tab ).catalogue ).length > 0 && Object.keys( rowOf( tab ).catalogue ).every( function( set ) {
				const entry = rowOf( tab ).catalogue[set];
				return typeof entry.name === 'string' && entry.name !== '' && entry.knobs.every( function( knob ) { return order.indexOf( knob ) !== -1 } )
					&& entry.knobs.join() === order.filter( function( knob ) { return entry.knobs.indexOf( knob ) !== -1 } ).join();
			} );
		} )
		&& real.parts.every( function( row ) { return Object.keys( row.catalogue ).indexOf( row.set ) !== -1 } ) );

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
		sent.endpoint === 'preview' && Object.keys( sent.payload ).sort().join() === 'colours,css,fonts,full,knobs,motion,parts,size,theme,whitespace,width'
		&& JSON.stringify( sent.payload.parts ) === JSON.stringify( ( function() { const parts = {}; real.parts.forEach( function( row ) { parts[row.part] = row.set } ); return parts } )() )
		&& JSON.stringify( sent.payload.knobs ) === JSON.stringify( real.knobs ) && JSON.stringify( sent.payload.fonts ) === JSON.stringify( real.fonts )
		&& JSON.stringify( sent.payload.motion ) === JSON.stringify( real.motion ) && sent.payload.size === real.size && sent.payload.width === real.width && sent.payload.whitespace === real.whitespace
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

// --- What the actions of the Theme tab really answered -------------------------------------------------
//
// design-smoke.php writes down what the server answered to each of them - the list with and without an
// active theme, a theme loaded and one that names typefaces the project has not installed, and the
// answers of a create, a save, a duplicate, a delete, an export and an import - and hands the file to
// this run (DESIGN_THEMES). The stand-in above is what the tab is drawn from in the checks before; this
// is the half that keeps it honest: the same tab, drawn from, and driven by, the answers of the server

console.log( 'The answers of the Theme tab' );

const themesFile = process.env.DESIGN_THEMES || '';

if( themesFile === '' || fs.existsSync( themesFile ) === false )
	console.log( '  --  no answers of the Theme tab were handed to this run (DESIGN_THEMES), so the tab is not drawn from the real ones' );
else {

	const answered = JSON.parse( fs.readFileSync( themesFile, 'utf8' ) );
	const none = answered.none;
	const activeList = answered.active;
	const wholeTab = function( list ) {
		const screen = panel( list, { whole : true } );
		screen.tab('Theme');
		return screen;
	};
	const listed = function( themes ) { return themes.map( function( theme ) { return theme.slug } ).join() };

	check( 'the answers are in the shape the tab reads: the list gives the theme the setup names, its css, the themes and the active one beside everything else; a load the theme with its setup, its css, the typefaces missing and the notes; a create, a save, a duplicate, a delete, an export and an import what the tab takes over',
		none.theme === '' && none.css === '' && none.active === null && Array.isArray( none.themes ) && none.themes.length > 0 && none.themes[0].shipped === true
		&& none.themes.every( function( theme ) { return /^[a-z0-9]+(-[a-z0-9]+)*$/.test( theme.slug ) && typeof theme.name === 'string' && theme.name !== '' && typeof theme.description === 'string' } )
		&& Object.keys( none.themes[0] ).join() === 'slug,name,description,shipped' && Object.keys( activeList.active ).join() === 'slug,name,description,shipped,setup,css'
		&& Object.keys( activeList.active.setup ).join() === 'size,width,whitespace,motion,parts,knobs,fonts,colours'
		&& Object.keys( answered.load ).join() === 'slug,name,description,shipped,setup,css,missing,notes' && answered.load.shipped === true && Object.keys( answered.load.setup ).join() === 'size,width,whitespace,motion,parts,knobs,fonts'
		&& answered.load.notes.every( function( note ) { return typeof note === 'string' || ( typeof note.error === 'string' && typeof note.code === 'string' && Array.isArray( note.params ) ) } )
		&& typeof none.running === 'string' && typeof activeList.running === 'string'
		&& Object.keys( answered.loadMissing.missing[0] ).join() === 'slug,name,inList'
		&& Object.keys( answered.created ).join() === 'slug,themes,active' && Object.keys( answered.saved ).join() === 'themes,active' && Object.keys( answered.duplicated ).join() === 'slug,themes'
		&& Object.keys( answered.deleted ).join() === 'themes,theme' && Object.keys( answered.exported ).join() === 'json,file' && Object.keys( answered.imported ).join() === 'slug,themes' );

	// The list with no theme
	const noneTab = wholeTab( none );

	check( 'the tab is drawn from the list with no theme: a row for each theme, shipped, in the order given, with the name and the description the server wrote in the language of the workbench; none is active, none can be deleted, and the card of the active theme says there is none',
		slugsOf( noneTab ) === listed( none.themes ) && none.themes.every( function( theme ) {
			const row = themeRow( noneTab, theme.slug );
			return textsOf( row, 'design-theme-name' ) === theme.name && textsOf( row, 'design-theme-description' ) === theme.description && byClass( row, 'design-theme-shipped' ).length === 1 && themeButtons( noneTab, theme.slug ).length === 2;
		} )
		&& byClass( noneTab.form, 'design-theme-active' ).length === 0 && textsOf( noneTab.byId('design-theme-current'), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/current/none'] );

	// The list with a theme of the project active - the one the website runs as well, which the list says by the theme of the last apply
	const activeTab = wholeTab( Object.assign( {}, activeList, { running : activeList.active.slug } ) );
	const activeSlug = activeList.active.slug;

	check( 'drawn from the list with a theme of the project active: the shipped ones first and that one after them, with the chip and the mark, a Delete on it alone; the head says its name, the fields hold its name and its description',
		slugsOf( activeTab ) === listed( activeList.themes ) && activeList.themes[0].shipped === true
		&& ( function() {
			const flags = activeList.themes.map( function( theme ) { return theme.shipped } );
			return flags.indexOf( false ) === -1 || flags.slice( flags.indexOf( false ) ).every( function( flag ) { return flag === false } );
		} )()
		&& hasClass( themeRow( activeTab, activeSlug ), 'design-theme-row--active' ) && byClass( activeTab.form, 'design-theme-row--active' ).length === 1 && textsOf( themeRow( activeTab, activeSlug ), 'design-theme-active' ) === 'active'
		&& activeList.themes.every( function( theme ) { return ( activeTab.byId('design-theme-delete-'+ theme.slug) !== null ) === ( theme.shipped === false ) } )
		&& textsOf( activeTab.byId('design-theme-current-head'), 'design-theme-current-name' ) === activeList.active.name
		&& activeTab.byId('design-theme-name').value === activeList.active.name && activeTab.byId('design-theme-description').value === activeList.active.description && activeTab.byId('design-theme-name').disabled === false );
	check( '...the css field holds the css the setup carries, which is not the theme\'s: the chip says the screen differs from the theme; the css of the theme in its place and the chip goes - the setup the list gives and the setup of the theme\'s file are the same, to the last key',
		activeTab.byId('design-theme-css').value === activeList.css && activeList.css !== activeList.active.css && activeTab.byId('design-theme-changed') !== null && activeTab.design._themeChanged() === true
		&& ( activeTab.design._edit.css = activeList.active.css, activeTab.design._themeChanged() ) === false
		&& JSON.stringify( activeTab.design._canonical( activeTab.design._snapshotOf( activeTab.design._edit ) ) ) === JSON.stringify( activeTab.design._canonical( activeList.active.setup ) ) );

	// Load
	const loaded = wholeTab( none );
	loaded.byId('design-theme-load-'+ answered.load.slug).fire('click');
	loaded.answer( 200, answered.load );

	const offered = function( screen, role ) { return screen.design._data.options[role] };

	check( 'a theme the server loaded is the working copy as it came: no question, the templates, the knobs, the size and the width, the animation as the file holds them - and the palette as the screen has it, for the file of a theme the feature ships names none - and of the fonts the families the roles are offered, the others the face of Nino',
		loaded.confirms.length === 0 && loaded.requests.length === 0 && loaded.design._edit.theme === answered.load.slug && loaded.design._edit.css === answered.load.css
		&& JSON.stringify( loaded.design._edit.parts ) === JSON.stringify( answered.load.setup.parts ) && JSON.stringify( loaded.design._edit.knobs ) === JSON.stringify( answered.load.setup.knobs )
		&& loaded.design._edit.size === answered.load.setup.size && loaded.design._edit.width === answered.load.setup.width && loaded.design._edit.whitespace === answered.load.setup.whitespace && JSON.stringify( loaded.design._edit.motion ) === JSON.stringify( answered.load.setup.motion )
		&& Object.prototype.hasOwnProperty.call( answered.load.setup, 'colours' ) === false && JSON.stringify( loaded.design._edit.colours ) === JSON.stringify( none.colours )
		&& Object.keys( answered.load.setup.fonts ).every( function( role ) { return loaded.design._edit.fonts[role].family === ( offered( loaded, role ).indexOf( answered.load.setup.fonts[role].family ) !== -1 ? answered.load.setup.fonts[role].family : '' ) } ) );
	check( '...it is the active theme - shipped, so its name and description are read-only - as it was taken over, so the screen is the theme and there is no chip; the way back is shown, the bar says it was loaded and the shortcodes of the css were not touched',
		loaded.design._active.slug === answered.load.slug && loaded.design._active.shipped === true && loaded.byId('design-theme-name').disabled === true && loaded.byId('design-theme-name').value === answered.load.name
		&& loaded.byId('design-theme-changed') === null && loaded.design._themeChanged() === false && loaded.design._isDirty() === true && loaded.byId('design-reset').hidden === false
		&& loaded.line().textContent.indexOf( say( '/_admin/design/theme/msg/loaded', answered.load.name ) ) === 0 && loaded.byId('design-theme-css').value === answered.load.css );

	loaded.tab('Content');
	check( '...and the other tabs show it: the template of the Content is the theme\'s',
		shown( loaded.byId('design-set-content') ) === answered.load.setup.parts.content );

	// A theme that names typefaces the project has not installed
	const withMissing = wholeTab( Object.assign( {}, none, { themes : answered.created.themes } ) );
	const askable = answered.loadMissing.missing.filter( function( entry ) { return entry.inList === true } );
	withMissing.byId('design-theme-load-'+ answered.loadMissing.slug).fire('click');
	withMissing.answer( 200, answered.loadMissing );

	check( 'a theme that names typefaces the project has not installed asks for those the list has, by the name the list gives them - the one it does not have is not asked for - and a yes posts the install of the first',
		askable.length === 1 && answered.loadMissing.missing.length === 2 && withMissing.confirms.length === 1 && withMissing.confirms[0] === say( '/_admin/design/theme/confirm/fonts', askable.map( function( entry ) { return entry.name } ).join(', ') )
		&& withMissing.requests.length === 1 && withMissing.requests[0].endpoint === 'webfonts/install' && withMissing.requests[0].payload.slug === askable[0].slug );

	// Create, save, duplicate, delete, export and import
	const created = wholeTab( none );
	created.byId('design-theme-new').value = answered.created.active.name;
	created.byId('design-theme-new').fire('input');
	created.byId('design-theme-create').fire('click');
	created.answer( 200, answered.created );

	check( 'the answer of a create is the list with the theme in it and the theme as the server holds it: the rows are the list, the draft names the new theme and the card shows its name',
		slugsOf( created ) === listed( answered.created.themes ) && created.design._edit.theme === answered.created.slug && created.design._active.slug === answered.created.slug
		&& textsOf( created.byId('design-theme-current-head'), 'design-theme-current-name' ) === answered.created.active.name && created.byId('design-theme-name').value === answered.created.active.name
		&& created.byId('design-theme-description').value === answered.created.active.description && hasClass( themeRow( created, answered.created.slug ), 'design-theme-row--active' ) );

	const saved = wholeTab( activeList );
	saved.byId('design-theme-save').fire('click');
	saved.answer( 200, answered.saved );

	check( 'the answer of a save: the list, and the theme as it is now - the name and the description the fields have, the chip as the setup of the file against the screen says',
		slugsOf( saved ) === listed( answered.saved.themes ) && saved.design._active.slug === answered.saved.active.slug && saved.design._themeText.name === answered.saved.active.name
		&& saved.byId('design-theme-description').value === answered.saved.active.description && saved.line().textContent === say( '/_admin/design/theme/msg/saved', answered.saved.active.name ) );

	const copied = wholeTab( answered.created.themes.length > 0 ? Object.assign( {}, none, { themes : answered.created.themes } ) : none );
	copied.byId('design-theme-duplicate-'+ none.themes[0].slug).fire('click');
	copied.answer( 200, answered.duplicated );

	check( 'the answer of a duplicate: the list with the copy in it, which has all three buttons, a project theme; nothing is loaded and the bar names the theme and its copy',
		slugsOf( copied ) === listed( answered.duplicated.themes ) && themeButtons( copied, answered.duplicated.slug ).length === 3 && copied.design._edit.theme === ''
		&& copied.line().textContent === say( '/_admin/design/theme/msg/duplicated', none.themes[0].name,
			answered.duplicated.themes.filter( function( theme ) { return theme.slug === answered.duplicated.slug } )[0].name ) );

	const deleted = wholeTab( activeList );
	deleted.byId('design-theme-delete-'+ activeSlug).fire('click');
	deleted.answer( 200, answered.deleted );

	check( 'the answer of a delete: the list as the server has it, and the theme the setup names now - none, since it was the one deleted: the screen has no active theme and the card says so',
		slugsOf( deleted ) === listed( answered.deleted.themes ) && answered.deleted.theme === '' && deleted.design._edit.theme === '' && deleted.design._data.theme === '' && deleted.design._active === null
		&& textsOf( deleted.byId('design-theme-current'), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/current/none'] && deleted.byId('design-theme-name') === null );

	const exported = wholeTab( none );
	exported.design._edit.theme = answered.load.slug;
	exported.design._active = Object.assign( {}, answered.load, { setup : exported.design._snapshotOf( exported.design._edit ) } );
	exported.design._paintTheme();
	exported.byId('design-theme-export').fire('click');
	exported.answer( 200, answered.exported );

	check( 'the answer of an export is the text of the file and the name to save it under: a blob of the text as it is, a link with that name',
		exported.blobs.length === 1 && exported.blobs[0].parts[0] === answered.exported.json && /^nino-theme-[a-z0-9-]+\.json$/.test( answered.exported.file ) && exported.downloads.length === 1
		&& exported.downloads[0].download === answered.exported.file && JSON.parse( answered.exported.json ).format === 1 );

	const imported = wholeTab( none );
	imported.byId('design-theme-import').files = [ { name : answered.exported.file } ];
	imported.byId('design-theme-import').fire('change');
	imported.readers[0].finish( answered.exported.json );
	imported.answer( 200, answered.imported );

	check( 'the text of an export is posted as it is, whole, and the answer of the import is the list with the theme in it - not loaded',
		imported.requests.length === 0 && slugsOf( imported ) === listed( answered.imported.themes ) && imported.design._edit.theme === '' && themeButtons( imported, answered.imported.slug ).length === 3 );

	check( 'every word the tab shows is one the panel\'s texts carry',
		[ noneTab, activeTab, loaded, created, deleted ].every( function( screen ) {
			return descendants( screen.pane ).every( function( el ) {
				return [ el.textContent, el.getAttribute('title'), el.getAttribute('aria-label') ].every( function( text ) { return typeof text !== 'string' || text.indexOf('/_admin/') !== 0 } );
			} );
		} ) );
}

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
