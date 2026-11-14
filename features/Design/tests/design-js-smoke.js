/**
 *	Nino
 *	design-js-smoke.js		What the Design panel's screens are, over a dom stand-in: the kernel's strip
 *												of three tabs on top of the pane - the panel's own screen, which the strip
 *												calls Themes, the loader and the Settings the registry attaches - the first two
 *												a namespace the shell asks for by the uri of its tab. The panel's own screen has
 *												two modes: the list of the themes, and the setup by its objects, with the bar of
 *												the objects on top of the pane - Back to list, which is no tab, the strip of a tab
 *												for each object and the select of the same, which the stylesheet shows below the
 *												width where the rail of the workbench gives its links up - and under it the
 *												controls of the object that is open. Where a control stands is one table, which
 *												design/list hands over - for every control of the setup the aspect it stands in,
 *												the object it is set on and its place in the row - and the screen is drawn from
 *												it and from nothing else: an object draws a block for each aspect it has a
 *												control in, in the table's order - Base, Text, Surface, Colour, Motion and, for
 *												the page, CSS -, the blocks of an accordion, one open and the head of a closed
 *												one naming what stands off the defaults - in Base the template that is chosen,
 *												whichever it is - a press opening its block and closing the other; and in a block
 *												exactly the controls the table gives that object in that aspect, each saying
 *												which keys it stands for; a template chosen draws the whole column of the controls
 *												anew, the open block open and the focus on the new select. The list of the
 *												themes is a row for each theme: Load, Duplicate, Export, Delete (a theme of the
 *												project) and Edit in that order, a pencil on a theme of the project that turns its
 *												name and description into two fields - Enter saves, Escape cancels - and under the
 *												list the field for an import. Edit on the loaded theme opens the setup at once, on
 *												any other it loads first; Back to list leaves it with the draft kept, and the chip
 *												on the button of Themes in the strip says that the screen differs from the theme,
 *												renewed whenever the shell shows the tab. Save theme stands in the action bar while
 *												the setup is open and a theme of the project is loaded. The block CSS, the page's
 *												alone, has no controls of the table and one textarea, taller than a field of a form,
 *												which every input mirrors into the preview and which the draft and the theme keep.
 *												The loader has no bar, no preview: its two cards - the families that are installed
 *												and the list of those on offer, searched, told apart by category in one row and
 *												paged - the rows in a box as tall as the window has room for, the pager after the
 *												box - with an install and a removal made at once, the names set in their own faces
 *												and a sentence of its own for each thing a refusal of the list can be about.
 *												Whichever of the two tabs the shell shows first, before anything of the panel
 *												has been read, asks for the list itself and draws once it is there - the
 *												loader with no preview and no bar behind it, in no mount - and says a failure
 *												of the list where it stands.
 *												Base has the choice of a set in the block of the object each part is the template
 *												of, seven in all; Text the root size and the five font roles, with a size that is
 *												typed, and the sizes of the navigation, the titles and their variants, each knob a
 *												strip with its icon - five numbers, or the three words of a choice - greyed out
 *												where the chosen template does not answer to it, a frame's knobs as a set's;
 *												Surface the same over the heights, the space, the corners, the lines and the shadows
 *												- the two radii and the corners of a kind of surface, one choice of two words for
 *												the cover, the row and the column of a section; Colour the palette and its mode
 *												in the block of the page, and after it the dimming of a section, the tone of the
 *												shadow of a card and the stripes of a table in the blocks of theirs; Motion the
 *												animation of the page in the block of the page, and after it what a card and a
 *												button do under the pointer - one preview and one bar, drawn in both modes of
 *												the panel's own screen, one at a time: the action bar, with the line the panel says
 *												things in, the chip for the state of the files and the way back to the previous
 *												version, the head of the preview - one row, with the viewport switch of three icon
 *												buttons and the switch that lets the frame follow - the room the frame takes under
 *												it, down to the bar, and the scroll of the frame to the anchor of the object, or of
 *												the control that was changed - which waits for the page in it where the object, or
 *												the answer to a change, changed the stage's height.
 *
 *												A feature that brings variants (Setup::features()) is an object after the eight,
 *												a tab and an option of the select named by the feature: Base has the select of
 *												its variants, and none for a feature with one, and the aspects after it are the
 *												ones their heads put its knobs on: a scale and a choice as the knobs
 *												of the parts are, a ground and an ink as a select of the surfaces and of the
 *												tones of the palette, a colour as two inputs, a light half and a dark half,
 *												with the line of what they measure; a knob the variant that is chosen does
 *												not declare is greyed out, a choice kept for a feature that is not active is
 *												drawn nowhere and posted all the same. A feature may bring a block to the
 *												specimen (`anchor` of the feature in the answer, its id): opening it, choosing a
 *												variant of it and moving a knob of it scroll the frame there, and to the top of
 *												the page where the feature has none or the page in the frame has no such id.
 *
 *												The sections below were written for a screen of six views, one aspect of the
 *												design over all its objects, and read the controls of one by walking the objects
 *												(look(), eachBlock() and seek() here: the screen looked at through an aspect, the
 *												column drawn for each object that has a block of it and nothing else moved);
 *												the sections that press a tab, use the select, read the bar, the anchors and the
 *												fit of the stage press the tabs of the objects themselves (go()).
 *
 *												A template that brings styles - css files beside it, each style declared in
 *												the head of the template - has a select for each in the block of its part,
 *												under the select of the template: None first and the options by the names of
 *												their files, the description of the one chosen under it. A choice is a key of
 *												the working copy that goes with every request, is taken over from a stored
 *												selection and from a theme, is compared by the chip, is the template's and
 *												is gone with a change of the template, and the head of the closed block names
 *												the options chosen.
 *
 *												A preview says what a design looks like and nothing about which selection
 *												produced it, and nothing stands under the frame to say it: the checks here
 *												read what the screen says off the controls that are drawn, after every kind
 *												of change it allows - a knob moved, another template chosen, a font changed,
 *												another object opened. The words of the steps are read off the buttons that
 *												are drawn, and every other word the screen says is compared with the text
 *												file's own, read by its key: a word the owner changes turns no check red,
 *												and a literal stands only where the suite itself put the word into the
 *												stand-in's answer.
 *
 *												No jsdom, no dependency: the same element stand-in the other feature tests
 *												build, so this runs with nothing but node. design-smoke.php runs it through
 *												node when node is on the path, so bin/check.sh and CI cover it - and hands
 *												it the answers design/list (DESIGN_LISTING), design/webfonts/list
 *												(DESIGN_WEBFONTS), the actions of the themes (DESIGN_THEMES) and - with a
 *												feature that brings variants active - the answer of design/list for it
 *												(DESIGN_FEATURES) and - with a template that brings styles - the answer of
 *												design/list for that (DESIGN_STYLES) and - with a feature that declares styles
 *												for all its variants - the one for that (DESIGN_FEATURE_STYLES) really gave,
 *												which the screen is then drawn from as well;
 *												it also runs on its own.
 *
 *												The questions the panel asks - the apply, the missing typefaces, a theme's
 *												deletion, the way back to the previous version - are weighed by the workbench's
 *												mode (Nino.adminUi.confirm()): the apply is asked on a live site, with what the
 *												shell adds above the paths of the files, and not on a site that is being built;
 *												the deletion and the way back are asked in both modes. Ctrl+S is the entry's
 *												shortcut: Development applies, Production stores the draft, and a screen with
 *												no bar says it did not save.
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

	/*	The workbench's own words that the panel borrows are not the panel's: the two states of a switch, which the head of a block says the case switch of a
		role with. The stand-in has them as the workbench's text file has them	*/
	texts['/_admin/common/label/on'] = 'on';
	texts['/_admin/common/label/off'] = 'off';
	texts['/_admin/common/label/rename'] = 'Rename';
	texts['/_admin/common/label/delete'] = 'Delete';
	texts['/_admin/common/label/save'] = 'Save';
	texts['/_admin/common/label/cancel'] = 'Cancel';
	texts['/_admin/common/label/back'] = 'Back to list';

	return texts;
} )();

// What the shell adds under the question of a live site that is not in maintenance - the stand-in's own sentence, the shell's is not the panel's
const LIVE_NOTE = 'Visitors see this change at once.';

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
		removeAttribute	: function( name ) { delete this.attributes[name] },
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
			// A row of the loader is asked for the element its name is in
			if( selector.charAt(0) === '.' )
				return byClass( this, selector.slice(1) )[0] || null;
			return byTag( this, selector )[0] || null;
		},
		// The controls of a card the list of the themes switches off while a request is on its way: a list of tags, of which one may be asked for by its type - 'button, input[type="file"]'
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
		// A bar wider than the pane is scrolled to the tab that is brought into sight: the options it was asked with
		scrollIntoView	: function( options ) { this.scrolledInto = options },
		// The kernel's tab strip moves the focus along with the arrow keys
		focus						: function() { focused = this },
		// Whether a node is this one or stands below it, as Node.contains() says
		contains				: function( node ) { return node === this || descendants( this ).indexOf( node ) !== -1 },
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

/** The name a knob row carries: the first line of the box that stands beside its icon - or, in the row of a toggle, the label of its switch */
function rowName( row ) {
	return ( byClass( row, 'design-knob-text' )[0] || byClass( row, 'nino-admin-switch-copy' )[0] || {} ).textContent;
}

/** The terse line under the name of a row of a strip: the note, or what Automatic is on the strip of a tier */
function rowNote( row ) {
	return ( byTag( byClass( row, 'design-knob-text' )[0] || element('span'), 'small' )[0] || {} ).textContent;
}

/** Whether a knob row is a switch: the row of a toggle, which has no strip of buttons */
function isSwitch( row ) {
	return hasClass( row, 'design-knob-row--switch' );
}

/** Whether a knob row is a select: the row of an ink, which has the tones of the page for its options and no strip of buttons */
function isSelect( row ) {
	return hasClass( row, 'design-knob-row--select' );
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

/** The views of the setup, in the order the strip in the bar at the top of the pane shows them: the keys, not the words on them (Setup::TABS) */
const TABS = [ 'templates', 'type', 'surfaces', 'colours', 'motion', 'css' ];

/** The views that draw blocks, one for each object that has a control on them: all but the last, which is the one field of the css */
const BLOCK_VIEWS = TABS.filter( function( key ) { return key !== 'css' } );

/** The objects the blocks of the tabs are of, in the order the blocks follow one another (Setup::OBJECTS) */
const OBJECTS = [ 'page', 'header', 'footer', 'section', 'tiles', 'card', 'buttons', 'lists', 'tables', 'forms', 'tabs', 'accordion' ];

/** The tabs of the pane in the order the shell's strip shows them, by uri: the panel's own screen, which says Themes, the loader and the Settings tab the registry attaches */
const PANE = [ 'design', 'design-typefaces', 'design-settings' ];

/** The width below which the strip of the objects is a select: the query the script asks the window and the stylesheet of the panel answers (and the rail of the workbench, which gives its links up for a select at the same width) */
const NARROW_QUERY = '(max-width: 63.999rem)';

/** The kind of every knob, by part, as design/list gives them: a scale has five positions, -2 to +2, a choice three, -1 to +1, a toggle two, 0 and 1, an ink a tone of the page or none */
const KINDS = {
	header 		: { nav : 'scale', height : 'scale', space : 'scale', logo : 'scale', ground : 'choice', 'border-size' : 'scale', 'border-color' : 'choice', shadow : 'scale', position : 'choice', cta : 'choice', behaviour : 'choice', 'nav-case' : 'toggle', 'menu-size' : 'scale', 'menu-weight' : 'choice', 'menu-tracking' : 'scale' },
	footer 		: { scale : 'scale', height : 'scale', space : 'scale', logo : 'scale', ground : 'choice', 'border-size' : 'scale', 'border-color' : 'choice', divider : 'scale', position : 'choice', 'nav-case' : 'toggle' },
	section 	: { quiet : 'scale', loud : 'scale', title : 'scale', subtitle : 'scale', 'title-tracking' : 'scale', space : 'scale', rule : 'grade', 'rule-length' : 'choice', 'title-shadow' : 'grade', dim : 'scale', 'title-ground' : 'choice', 'rule-color' : 'choice', align : 'choice', measure : 'choice', 'title-case' : 'toggle', 'subtitle-case' : 'toggle', 'title-ink' : 'ink', 'subtitle-ink' : 'ink' },
	tiles 		: { title : 'scale', subtitle : 'scale', text : 'scale', 'title-tracking' : 'scale', space : 'scale', gap : 'grade', place : 'choice', dim : 'scale', hover : 'choice', 'title-ink' : 'ink', 'subtitle-ink' : 'ink' },
	card 			: { title : 'scale', subtitle : 'scale', 'title-tracking' : 'scale', space : 'scale', border : 'scale', shadow : 'grade', tone : 'choice', 'title-ground' : 'choice', hover : 'choice', align : 'choice', 'title-case' : 'toggle', 'subtitle-case' : 'toggle', 'title-ink' : 'ink', 'subtitle-ink' : 'ink' },
	buttons 	: { size : 'scale', spread : 'scale', tracking : 'scale', space : 'scale', corners : 'scale', outline : 'scale', hover : 'choice', case : 'toggle' },
	lists 		: { scale : 'scale', space : 'scale', marker : 'choice' },
	tables 		: { scale : 'scale', space : 'scale', border : 'scale', zebra : 'choice', stripes : 'scale' },
	forms 		: { scale : 'scale', space : 'scale', corners : 'scale', line : 'scale', labels : 'choice', edge : 'ink', label : 'ink', 'label-case' : 'toggle' },
	tabs 			: { scale : 'scale', space : 'scale', line : 'scale', active : 'ink' },
	accordion : { scale : 'scale', space : 'scale', line : 'scale', marker : 'choice', 'marker-ink' : 'ink', weight : 'toggle' },
};

/** The names of the knobs of each part, in the order of the part */
const KNOBS = {};
Object.keys( KINDS ).forEach( function( part ) { KNOBS[part] = Object.keys( KINDS[part] ) } );

/**
 *	Where every control stands, as design/list hands the table over (Setup::LAYOUT): the key the choice is kept under in the setup and
 *	[ tab, object, order ]. Keys that stand at one place are one control. This is the stand-in the screen is drawn from in most of the checks;
 *	the one the server really gave is held to it where that answer is read, below
 */
const LAYOUT = {
	'parts/header'                    : [ 'templates', 'header', 1 ],
	'navs/header'                     : [ 'templates', 'header', 2 ],
	'knobs/header/position'           : [ 'templates', 'header', 3 ],
	'knobs/header/cta'                : [ 'templates', 'header', 4 ],
	'knobs/header/behaviour'          : [ 'templates', 'header', 5 ],
	'parts/footer'                    : [ 'templates', 'footer', 1 ],
	'navs/footer'                     : [ 'templates', 'footer', 2 ],
	'knobs/footer/position'           : [ 'templates', 'footer', 3 ],
	'parts/section'                   : [ 'templates', 'section', 1 ],
	'knobs/section/align'             : [ 'templates', 'section', 2 ],
	'knobs/section/measure'           : [ 'templates', 'section', 3 ],
	'parts/tiles'                     : [ 'templates', 'tiles', 1 ],
	'parts/card'                      : [ 'templates', 'card', 1 ],
	'knobs/card/align'                : [ 'templates', 'card', 2 ],
	'parts/buttons'                   : [ 'templates', 'buttons', 1 ],
	'parts/lists'                     : [ 'templates', 'lists', 1 ],
	'parts/tables'                    : [ 'templates', 'tables', 1 ],
	'parts/forms'                     : [ 'templates', 'forms', 1 ],
	'parts/tabs'                      : [ 'templates', 'tabs', 1 ],
	'parts/accordion'                 : [ 'templates', 'accordion', 1 ],

	'size'                            : [ 'type', 'page', 1 ],
	'fonts/title'                     : [ 'type', 'page', 2 ],
	'fonts/subtitle'                  : [ 'type', 'page', 3 ],
	'fonts/text'                      : [ 'type', 'page', 4 ],
	'fonts/button'                    : [ 'type', 'page', 5 ],
	'fonts/code'                      : [ 'type', 'page', 6 ],
	'knobs/header/nav'                : [ 'type', 'header', 1 ],
	'knobs/header/nav-case'           : [ 'type', 'header', 2 ],
	'knobs/header/menu-size'          : [ 'type', 'header', 3 ],
	'knobs/header/menu-weight'        : [ 'type', 'header', 4 ],
	'knobs/header/menu-tracking'      : [ 'type', 'header', 5 ],
	'knobs/footer/scale'              : [ 'type', 'footer', 1 ],
	'knobs/footer/nav-case'           : [ 'type', 'footer', 2 ],
	'knobs/section/title'             : [ 'type', 'section', 1 ],
	'knobs/section/subtitle'          : [ 'type', 'section', 2 ],
	'knobs/section/quiet'             : [ 'type', 'section', 3 ],
	'knobs/section/loud'              : [ 'type', 'section', 4 ],
	'knobs/section/title-tracking'    : [ 'type', 'section', 5 ],
	'knobs/section/title-case'        : [ 'type', 'section', 6 ],
	'knobs/section/subtitle-case'     : [ 'type', 'section', 7 ],
	'knobs/tiles/title'               : [ 'type', 'tiles', 1 ],
	'knobs/tiles/subtitle'            : [ 'type', 'tiles', 2 ],
	'knobs/tiles/text'                : [ 'type', 'tiles', 3 ],
	'knobs/tiles/title-tracking'      : [ 'type', 'tiles', 4 ],
	'knobs/card/title'                : [ 'type', 'card', 1 ],
	'knobs/card/subtitle'             : [ 'type', 'card', 2 ],
	'knobs/card/title-tracking'       : [ 'type', 'card', 3 ],
	'knobs/card/title-case'           : [ 'type', 'card', 4 ],
	'knobs/card/subtitle-case'        : [ 'type', 'card', 5 ],
	'knobs/buttons/size'              : [ 'type', 'buttons', 1 ],
	'knobs/buttons/spread'            : [ 'type', 'buttons', 2 ],
	'knobs/buttons/tracking'          : [ 'type', 'buttons', 3 ],
	'knobs/buttons/case'              : [ 'type', 'buttons', 4 ],
	'knobs/lists/scale'               : [ 'type', 'lists', 1 ],
	'knobs/tables/scale'              : [ 'type', 'tables', 1 ],
	'knobs/forms/scale'               : [ 'type', 'forms', 1 ],
	'knobs/forms/label-case'          : [ 'type', 'forms', 2 ],
	'knobs/tabs/scale'                : [ 'type', 'tabs', 1 ],
	'knobs/accordion/scale'           : [ 'type', 'accordion', 1 ],
	'knobs/accordion/weight'          : [ 'type', 'accordion', 2 ],

	'width'                           : [ 'surfaces', 'page', 1 ],
	'whitespace'                      : [ 'surfaces', 'page', 2 ],
	'radius'                          : [ 'surfaces', 'page', 3 ],
	'radius-alt'                      : [ 'surfaces', 'page', 4 ],
	'radii/image'                     : [ 'surfaces', 'page', 5 ],
	'knobs/header/height'             : [ 'surfaces', 'header', 1 ],
	'knobs/header/space'              : [ 'surfaces', 'header', 2 ],
	'knobs/header/border-size'        : [ 'surfaces', 'header', 3 ],
	'knobs/header/logo'               : [ 'surfaces', 'header', 4 ],
	'knobs/header/shadow'             : [ 'surfaces', 'header', 5 ],
	'knobs/footer/height'             : [ 'surfaces', 'footer', 1 ],
	'knobs/footer/space'              : [ 'surfaces', 'footer', 2 ],
	'knobs/footer/border-size'        : [ 'surfaces', 'footer', 3 ],
	'knobs/footer/logo'               : [ 'surfaces', 'footer', 4 ],
	'knobs/footer/divider'            : [ 'surfaces', 'footer', 5 ],
	'knobs/section/space'             : [ 'surfaces', 'section', 1 ],
	'knobs/section/rule'              : [ 'surfaces', 'section', 2 ],
	'knobs/section/rule-length'       : [ 'surfaces', 'section', 3 ],
	'knobs/section/title-shadow'      : [ 'surfaces', 'section', 4 ],
	'radii/cover'                     : [ 'surfaces', 'section', 5 ],
	'radii/row'                       : [ 'surfaces', 'section', 5 ],
	'radii/column'                    : [ 'surfaces', 'section', 5 ],
	'knobs/tiles/space'               : [ 'surfaces', 'tiles', 1 ],
	'knobs/tiles/gap'                 : [ 'surfaces', 'tiles', 2 ],
	'knobs/tiles/place'               : [ 'surfaces', 'tiles', 3 ],
	'knobs/tiles/hover'               : [ 'surfaces', 'tiles', 4 ],
	'radii/tile'                      : [ 'surfaces', 'tiles', 5 ],
	'knobs/card/space'                : [ 'surfaces', 'card', 1 ],
	'knobs/card/border'               : [ 'surfaces', 'card', 2 ],
	'knobs/card/shadow'               : [ 'surfaces', 'card', 3 ],
	'radii/card'                      : [ 'surfaces', 'card', 4 ],
	'knobs/buttons/space'             : [ 'surfaces', 'buttons', 1 ],
	'knobs/buttons/corners'           : [ 'surfaces', 'buttons', 2 ],
	'knobs/buttons/outline'           : [ 'surfaces', 'buttons', 3 ],
	'knobs/lists/space'               : [ 'surfaces', 'lists', 1 ],
	'knobs/lists/marker'              : [ 'surfaces', 'lists', 2 ],
	'knobs/tables/space'              : [ 'surfaces', 'tables', 1 ],
	'knobs/tables/border'             : [ 'surfaces', 'tables', 2 ],
	'knobs/tables/zebra'              : [ 'surfaces', 'tables', 3 ],
	'knobs/forms/space'               : [ 'surfaces', 'forms', 1 ],
	'knobs/forms/corners'             : [ 'surfaces', 'forms', 2 ],
	'knobs/forms/line'                : [ 'surfaces', 'forms', 3 ],
	'knobs/forms/labels'              : [ 'surfaces', 'forms', 4 ],
	'knobs/tabs/space'                : [ 'surfaces', 'tabs', 1 ],
	'knobs/tabs/line'                 : [ 'surfaces', 'tabs', 2 ],
	'knobs/accordion/space'           : [ 'surfaces', 'accordion', 1 ],
	'knobs/accordion/line'            : [ 'surfaces', 'accordion', 2 ],
	'knobs/accordion/marker'          : [ 'surfaces', 'accordion', 3 ],

	'colours/primary'                 : [ 'colours', 'page', 1 ],
	'colours/secondary'               : [ 'colours', 'page', 2 ],
	'colours/harmony'                 : [ 'colours', 'page', 2 ],
	'colours/temperature'             : [ 'colours', 'page', 3 ],
	'colours/saturation'              : [ 'colours', 'page', 4 ],
	'colours/contrast'                : [ 'colours', 'page', 5 ],
	'colours/depth'                   : [ 'colours', 'page', 6 ],
	'colours/mode'                    : [ 'colours', 'page', 7 ],
	'knobs/header/ground'             : [ 'colours', 'header', 1 ],
	'knobs/header/border-color'       : [ 'colours', 'header', 2 ],
	'knobs/footer/ground'             : [ 'colours', 'footer', 1 ],
	'knobs/footer/border-color'       : [ 'colours', 'footer', 2 ],
	'knobs/section/dim'               : [ 'colours', 'section', 1 ],
	'knobs/section/title-ground'      : [ 'colours', 'section', 2 ],
	'knobs/section/rule-color'        : [ 'colours', 'section', 3 ],
	'knobs/section/title-ink'         : [ 'colours', 'section', 4 ],
	'knobs/section/subtitle-ink'      : [ 'colours', 'section', 5 ],
	'knobs/tiles/dim'                 : [ 'colours', 'tiles', 1 ],
	'knobs/tiles/title-ink'           : [ 'colours', 'tiles', 2 ],
	'knobs/tiles/subtitle-ink'        : [ 'colours', 'tiles', 3 ],
	'knobs/card/tone'                 : [ 'colours', 'card', 1 ],
	'knobs/card/title-ground'         : [ 'colours', 'card', 2 ],
	'knobs/card/title-ink'            : [ 'colours', 'card', 3 ],
	'knobs/card/subtitle-ink'         : [ 'colours', 'card', 4 ],
	'knobs/tables/stripes'            : [ 'colours', 'tables', 1 ],
	'knobs/forms/edge'                : [ 'colours', 'forms', 1 ],
	'knobs/forms/label'               : [ 'colours', 'forms', 2 ],
	'knobs/tabs/active'               : [ 'colours', 'tabs', 1 ],
	'knobs/accordion/marker-ink'      : [ 'colours', 'accordion', 1 ],

	'motion/effect'                   : [ 'motion', 'page', 1 ],
	'motion/strength'                 : [ 'motion', 'page', 2 ],
	'motion/speed'                    : [ 'motion', 'page', 3 ],
	'motion/hover'                    : [ 'motion', 'page', 4 ],
	'motion/hover-strength'           : [ 'motion', 'page', 5 ],
	'knobs/card/hover'                : [ 'motion', 'card', 1 ],
	'knobs/buttons/hover'             : [ 'motion', 'buttons', 1 ],
};

/** The table as the answer carries it */
function layoutTable() {
	return { tabs : TABS.slice(), objects : OBJECTS.slice(), controls : JSON.parse( JSON.stringify( LAYOUT ) ) };
}

/** What the five buttons of a scale say, from the least to the most: the distance from the middle, the minus U+2212 and not a hyphen */
const SIGNED = [ '\u22122', '\u22121', '0', '+1', '+2' ];
const stripLabelsOf = function( screen, label ) { return screen.steps( label ).map( function( button ) { return button.textContent } ).join() };

/** The kinds of surface the radius is chosen for, and the two words a kind is set to, as design/list gives them: a strip of two for each kind */
const SURFACES = [ 'cover', 'row', 'column', 'tile', 'card', 'image' ];
const RADIUS_WORDS = [ 'standard', 'alternative' ];

/*	What design/list says of the tablet and the phone besides what they have set (see Setup::TIER_CONTROLS and the tables beside it): the tiers, the controls with the knobs each moves, the
	positions of each and the tables of the root size, the gutter of a row, the gap and the width of the page, and the blocks the controls stand in. The last half of this file holds it against the answer the server really gave	*/
const TIER_SPEC = {
	tiers : [ 'm', 's' ],
	controls : { size : [], title : [ [ 'section', 'title' ], [ 'section', 'loud' ] ], subtitle : [ [ 'section', 'subtitle' ] ], areas : [ [ 'section', 'space' ] ], margin : [], inset : [ [ 'card', 'space' ], [ 'tiles', 'space' ] ], gap : [ [ 'tiles', 'gap' ] ], header : [ [ 'header', 'height' ], [ 'header', 'logo' ] ], button : [ [ 'buttons', 'size' ] ] },
	positions : { size : [ -2, -1, 0, 1, 2 ], title : [ -2, -1, 1, 2 ], subtitle : [ -2, -1, 1, 2 ], areas : [ -2, -1, 1, 2 ], margin : [ -2, -1, 0, 1, 2 ], inset : [ -2, -1, 1, 2 ], gap : [ -2, -1, 1, 2 ], header : [ -2, -1, 1, 2 ], button : [ -2, -1, 1, 2 ] },
	sizes : { '-2' : [ '85%', '95.625%' ], '-1' : [ '92.5%', '104.0625%' ], '0' : [ '100%', '112.5%' ], '1' : [ '115%', '129.375%' ], '2' : [ '145%', '163.125%' ] },
	margins : { '-2' : [ '.5rem', '1rem' ], '-1' : [ '.75rem', '1.5rem' ], '0' : [ '1rem', '2rem' ], '1' : [ '1.5rem', '2.5rem' ], '2' : [ '2rem', '3rem' ] },
	gaps : { '-2' : '.5', '-1' : '.75', '1' : '1.5', '2' : '2' },
	widths : { '-2' : [ '48rem', 'var(--space-2)', 'var(--space-3)' ], '-1' : [ '64rem', 'var(--space-2)', 'var(--space-3)' ], '1' : [ 'none', 'var(--space-2)', 'var(--space-2)' ], '2' : [ 'none', 'var(--space-2)', '0' ] },
	blocks : { type : [ 'size', 'title', 'subtitle', 'button' ], surfaces : [ 'areas', 'margin', 'inset', 'gap', 'header' ] },
};

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

/**
 *	The words a ground is chosen among and the tones an ink is chosen among, as design/list hands them over (Colours::SURFACES and the keys of Setup::INKS): the
 *	panel has a word under each of them in its texts, and the lists are read off those
 */
function vocabulary( prefix ) {
	return Object.keys( TEXT ).filter( function( key ) { return key.indexOf( prefix ) === 0 } ).map( function( key ) { return key.slice( prefix.length ) } );
}

const GROUNDS = vocabulary('/_admin/design/ground/');
const INKS = vocabulary('/_admin/design/ink/');

/** A knob a head declares, as design/list answers it (Setup::declared()): its kind, the tab the head puts it on, its label, the words of the three positions of a choice - a list on the wire for the other kinds - and where it stands before anything is chosen */
function featureKnob( kind, tab, label, standard, options ) {
	return { kind : kind, tab : tab, label : label, options : options || [], 'default' : standard };
}

/**
 *	The features that bring variants, as design/list answers them (Admin::_features()), in the order of the catalogue - by their names - with what the setup chose
 *	for them: `chosen` is key => { set, knobs, styles }, and a key the catalogue does not have is a feature that is not active, which rides along with its entry. Two are made
 *	up here. The first, Zinc, whose key sorts before the other's, has two variants: the regular one a knob of each of four kinds - a scale on the Type view, a choice
 *	on Surfaces, a ground and an ink on Colours - and the loud one the same and two more, a pair of colours and a scale on Motion. The second, Mosaic, has the one
 *	variant and a knob on the Type view. Zinc declares two styles for both its variants, Look with two options and Edge with none; Mosaic declares none. Each feature has the anchor of its block on the specimen, as the server answers it ('' for none): Zinc brings a block, an id of the
 *	stage, Mosaic brings none
 */
function featuresAnswer( chosen ) {

	const picked = chosen || {};
	const regular = {
		size 		: featureKnob( 'scale', 'type', 'Size', 0 ),
		line 		: featureKnob( 'choice', 'surfaces', 'Line', 0, { less : 'Thin', 'default' : 'Normal', more : 'Thick' } ),
		submit 	: featureKnob( 'ground', 'colours', 'Submit', 'brand-safe' ),
		label 	: featureKnob( 'ink', 'colours', 'Label', 'subtitle' ),
	};
	const loud = Object.assign( {}, regular, {
		flip 		: featureKnob( 'colour', 'colours', 'Flip', [ '#b03a2e', '#e8705f' ] ),
		turn 		: featureKnob( 'scale', 'motion', 'Turn', 0 ),
	} );
	const answer = {
		mosaic 	: { name : 'Mosaic', anchor : '', active : true, 'default' : 'regular', catalogue : { regular : { name : 'Plain', description : 'Mosaic as they come', knobs : { grid : featureKnob( 'scale', 'type', 'Grid', 0 ) } } }, styles : [], set : '', knobs : [], picked : [] },
		awning 	: { name : 'Zinc', anchor : 'feature-awning', active : true, 'default' : 'regular', catalogue : {
			regular : { name : 'Regular', description : 'The awning as it hangs', knobs : regular },
			loud 		: { name : 'Loud', description : 'The awning, louder', knobs : loud },
		}, styles : {
			look : { label : 'Look', options : { soft : { name : 'Soft', description : 'Rounded and quiet.' }, bold : { name : 'Bold', description : '' } } },
			edge : { label : 'Edge', options : [] },
		}, set : '', knobs : [], picked : [] },
	};

	Object.keys( picked ).forEach( function( key ) {
		answer[key] = Object.assign( answer[key] || { name : key, anchor : '', active : false, 'default' : '', catalogue : {}, styles : [] }, { set : picked[key].set, knobs : picked[key].knobs, picked : picked[key].styles || [] } );
	} );

	return JSON.parse( JSON.stringify( answer ) );
}

/** The table of the stand-in with the rows the features add (Setup::layout(), Setup::objects()): one object for each feature that has variants, after the eight, a row for the choice of the variant on Templates and a place for each knob the variants declare, on the tab its head names - counted from 1 on the tab, a knob placed where the first variant that declares it puts it */
function layoutWith( features ) {

	const table = layoutTable();

	Object.keys( features ).forEach( function( key ) {

		const catalogue = features[key].catalogue;

		if( Object.keys( catalogue ).length === 0 )
			return;

		const count = {};

		table.objects.push( key );
		table.controls['features/'+ key+ '/set'] = [ 'templates', key, 1 ];

		Object.keys( catalogue ).forEach( function( set ) {
			Object.keys( catalogue[set].knobs ).forEach( function( knob ) {

				if( table.controls['features/'+ key+ '/knobs/'+ knob] !== undefined )
					return;

				count[ catalogue[set].knobs[knob].tab ] = ( count[ catalogue[set].knobs[knob].tab ] || 0 ) + 1;
				table.controls['features/'+ key+ '/knobs/'+ knob] = [ catalogue[set].knobs[knob].tab, key, count[ catalogue[set].knobs[knob].tab ] ];
			} );
		} );
	} );

	return table;
}

/** The answer of design/list with the two features of the stand-in in it - their catalogue, the rows of the table - and what is chosen for them; `over` has what a test wants different, as in listing() */
function featured( chosen, over ) {

	const features = featuresAnswer( chosen );

	return Object.assign( { features : features, layout : layoutWith( features ) }, over || {} );
}

/** The whole answer of design/list, with whatever a test wants different in it */
function listing( over ) {

	const roles = [ 'title', 'subtitle', 'text', 'button', 'code' ];
	const fonts = {};
	const defaults = {
		title 		: { family : 'open-sans', weight : 'bold', size : '' },
		subtitle 	: { family : 'open-sans', weight : 'normal', size : '' },
		text 			: { family : 'open-sans', weight : 'normal', size : '' },
		button 		: { family : 'open-sans', weight : 'bold', size : '' },
		code 			: { family : '', weight : 'normal', size : '' },
	};
	roles.forEach( function( role ) { fonts[role] = Object.assign( {}, defaults[role] ) } );

	const knobs = {};
	Object.keys( KNOBS ).forEach( function( tab ) {
		knobs[tab] = {};
		KNOBS[tab].forEach( function( knob ) { knobs[tab][knob] = KINDS[tab][knob] === 'ink' ? '' : 0 } );
	} );

	// Where the shipped theme puts the two knobs of the header that are not in the middle: the navigation on the right, the button hidden
	knobs.header.position = 1;
	knobs.header.cta = -1;

	/*	What a fresh install starts from, the keys that choose something - what the head of a closed block is compared with: the middle everywhere, the standard
		corners and the alternative radius at +1, the faces as the roles have them above. As the server hands it over as `defaults`, but for the library's own
		choice of the seven templates, which is not the stand-in's	*/
	const fresh = {
		size 				: 0,
		width 			: 0,
		whitespace 	: 0,
		radius 			: 0,
		'radius-alt' : 1,
		radii 			: { cover : 'standard', row : 'standard', column : 'standard', tile : 'standard', card : 'standard', image : 'standard' },
		motion 			: { effect : '', strength : '', speed : '', hover : '', 'hover-strength' : '' },
		parts 			: { header : 'first', footer : 'first', section : 'first', tiles : 'first', card : 'first', buttons : 'first', lists : 'first', tables : 'first', forms : 'first', tabs : 'first', accordion : 'first' },
		features 	: [],
		navs 				: { header : '', footer : '' },
		knobs 			: JSON.parse( JSON.stringify( knobs ) ),
		// No style is chosen for any template: an empty list for each part, as the server hands it over
		styles 			: { header : [], footer : [], section : [], tiles : [], card : [], buttons : [], lists : [], tables : [], forms : [], tabs : [], accordion : [] },
		fonts 			: JSON.parse( JSON.stringify( fonts ) ),
		colours 		: { primary : '#4faae8', secondary : '', harmony : 1, temperature : 3, saturation : 3, contrast : 3, depth : 3, mode : 'auto' },
	};

	return Object.assign( {
		parts : [
			part( 'header', 'frame', 'first', {
				first : { name : 'Plain bar', description : 'A bar with a rule under it', knobs : KNOBS.header.slice() },
				second : { name : 'Centred', description : 'The logo in the middle', knobs : KNOBS.header.slice() },
				third : { name : 'Slim', description : 'A bar without a button', knobs : without( KNOBS.header, [ 'position', 'cta' ] ) },
			} ),
			part( 'footer', 'frame', 'first', {
				first : { name : 'Plain footer', description : 'Two columns', knobs : KNOBS.footer.slice() },
				second : { name : 'Dark footer', description : 'On the dark ground', knobs : KNOBS.footer.slice() },
			} ),
			/*	A library made up for this test, with names of its own - the real one is read from the answer of the server, below: most of its
				templates answer to every knob of their part, a template of the Buttons leaves the corners out, and the third template of the
				Cards leaves the shadow out - what a library of somebody else's can hold, and what the screen has to draw all the same	*/
			part( 'section', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.section.slice() },
				second : { name : 'Box', description : 'A centred opening with tight lines', knobs : without( KNOBS.section, [ 'title-case', 'subtitle-case' ] ) },
				third : { name : 'Foreign', description : 'Large type, no dimming', knobs : without( KNOBS.section, [ 'dim' ] ) },
			} ),
			part( 'tiles', 'set', 'first', {
				first : { name : 'Nino', description : 'Tiles as the framework has them', knobs : KNOBS.tiles.slice() },
				second : { name : 'Poster', description : 'The words at the foot over a fade', knobs : KNOBS.tiles.slice() },
			} ),
			part( 'card', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.card.slice() },
				second : { name : 'Panel', description : 'A ground and a line', knobs : without( KNOBS.card, [ 'title-case', 'subtitle-case' ] ) },
				third : { name : 'Foreign', description : 'Large type, no shadow', knobs : without( KNOBS.card, [ 'shadow' ] ) },
			} ),
			part( 'buttons', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.buttons.slice() },
				third : { name : 'Square', description : 'No radius, spaced letters and a 2px line', knobs : without( KNOBS.buttons, [ 'corners' ] ) },
			} ),
			part( 'lists', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.lists.slice() },
				// The one template of the stand-in that brings styles: two, each with its label and the options by the names of their files
				second : { name : 'Ruled', description : 'A line between the items', knobs : KNOBS.lists.slice(), styles : {
					rule : { label : 'Line between the items', options : { dashed : { name : 'Dashed', description : 'A dashed line' }, dotted : { name : 'Dotted', description : 'A dotted line' } } },
					tone : { label : 'Tone of the line', options : { quiet : { name : 'Quiet', description : '' }, strong : { name : 'Strong', description : 'The line in the ink of the page' } } },
				} },
			} ),
			part( 'tables', 'set', 'first', {
				first : { name : 'Nino', description : 'The framework as it is', knobs : KNOBS.tables.slice() },
				fourth : { name : 'Filled', description : 'Tinted cells without a line', knobs : KNOBS.tables.slice() },
			} ),
			part( 'forms', 'set', 'first', {
				first : { name : 'Nino', description : 'The form as the kernel draws it', knobs : KNOBS.forms.slice() },
				second : { name : 'Line', description : 'A rule under the words', knobs : without( KNOBS.forms, [ 'corners', 'label-case' ] ) },
				third : { name : 'Plain', description : 'No tone of its own to move', knobs : without( KNOBS.forms, [ 'edge' ] ) },
			} ),
			part( 'tabs', 'set', 'first', {
				first : { name : 'Nino', description : 'A line under the open tab', knobs : KNOBS.tabs.slice() },
				second : { name : 'Pills', description : 'The open tab is a surface', knobs : without( KNOBS.tabs, [ 'line' ] ) },
			} ),
			part( 'accordion', 'set', 'first', {
				first : { name : 'Nino', description : 'A line under each entry', knobs : KNOBS.accordion.slice() },
				second : { name : 'Boxed', description : 'Each entry in a box', knobs : KNOBS.accordion.slice() },
			} ),
		],
		kinds 		: JSON.parse( JSON.stringify( KINDS ) ),
		// The features that bring variants: none here, a list on the wire; the words a ground and an ink are chosen among; what the colours of the features measure - a list on the wire, where none does
		features 	: [],
		// What the tablet and the phone have set, and what the screen needs to say what Automatic is: the tables of Setup, which the answer of design/list carries
		tiers 		: { m : [], s : [] },
		tierSpec 	: JSON.parse( JSON.stringify( TIER_SPEC ) ),
		grounds 	: GROUNDS.slice(),
		inks 			: INKS.slice(),
		contrasts : [],
		layout 		: layoutTable(),
		defaults 	: fresh,
		// The navigation chosen for the header and the footer, and what each can be: the kernel's two menus under the group kernel, what a feature offers under features
		navs 			: { header : '', footer : '' },
		navigations : {
			header : [ { key : 'nav-main', title : 'Navigation "main" (kernel)', group : 'kernel' }, { key : 'nav-footer', title : 'Navigation "footer" (kernel)', group : 'kernel' }, { key : 'menu-main', title : 'Main menu', group : 'features' } ],
			footer : [ { key : 'nav-main', title : 'Navigation "main" (kernel)', group : 'kernel' }, { key : 'nav-footer', title : 'Navigation "footer" (kernel)', group : 'kernel' }, { key : 'menu-main', title : 'Main menu', group : 'features' } ],
		},
		knobs 		: knobs,
		styles 		: JSON.parse( JSON.stringify( fresh.styles ) ),
		steps 		: [ 'least', 'less', 'default', 'more', 'most' ],
		size 			: 0,
		width 		: 0,
		whitespace : 0,
		// The radius of the surfaces: the standard strength at the middle, the alternative one at +1 and every kind of surface on the standard - the keys are the kinds there are - and the two words there are
		radius 		: 0,
		'radius-alt' : 1,
		radii 		: { cover : 'standard', row : 'standard', column : 'standard', tile : 'standard', card : 'standard', image : 'standard' },
		radiusWords : RADIUS_WORDS.slice(),
		// The themes: the theme the stored setup was taken from and the css of its own, the themes there are - the shipped ones, then the project's - and the active one as its file holds it
		theme 		: '',
		css 			: '',
		running 	: '',
		themes 		: THEMES.map( function( theme ) { return Object.assign( {}, theme ) } ),
		active 		: null,
		motion 		: { effect : '', strength : '', speed : '', hover : '', 'hover-strength' : '' },
		effects 	: [ 'zoom', 'zoom-out', 'slide-left', 'slide-right', 'slide-up', 'slide-down', 'blur', 'flip' ],
		hovers 		: [ 'zoom', 'bright', 'zoom-bright', 'none' ],
		strengths : [ 'soft', 'medium', 'hard' ],
		speeds 		: [ 'fast', 'medium', 'slow' ],
		roles 		: roles,
		fonts 		: fonts,
		families 	: {
			'open-sans' 		: { name : 'Open Sans', category : 'sans', system : false },
			'system-sans' 		: { name : familyWord('system-sans'), category : 'sans', system : true },
			'system-serif' 		: { name : familyWord('system-serif'), category : 'serif', system : true },
			'system-mono' 		: { name : familyWord('system-mono'), category : 'mono', system : true },
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
			harmony			: { kind : 'choice', 'default' : 1, steps : colourStepWords( 'harmony', 4 ) },
			temperature	: { kind : 'choice', 'default' : 3, steps : colourStepWords( 'temperature', 4 ) },
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

	const stage = { scrolls : [], turned : [], asked : [], listeners : [], frames : [], reachable : true, scrollY : 100, clearance : 0, tops : { type : 700, content : 300, cards : 900, buttons : 1200, lists : 1400, tables : 1500, forms : 1650, colours : 450, 'feature-awning' : 3400, footer : 4000 } };

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
			return stage.tops[id] === undefined && ( id !== 'global' || stage.noGlobal === true ) ? null : { getBoundingClientRect : function() { return { top : stage.tops[id] || 0 } } };
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
	title.textContent = TEXT['/_admin/nav/design'];
	const actions = element('div');
	actions.className = 'admin-panel-actions';
	/*	The strip the shell draws in the head, one button for each tab of the pane - the panel's own screen first, which says Themes where the rail says Design, then
		the tabs by their weights, the Settings tab the registry attaches last (see \Nino\Admin\Panels::panesHtml()): the ids and data attributes the kernel gives them	*/
	const registry = element('div');
	registry.className = 'nino-admin-tabs nino-admin-tabs--bar nino-admin-tabs--panel admin-panel-tabs';
	const TABS_OF_PANE = PANE;
	const TAB_WORDS = { design : '/_admin/nav/themes', 'design-typefaces' : '/_admin/nav/design-typefaces', 'design-settings' : '/_admin/features/tab/settings' };
	// Where each tab keeps its screen: the mounts the panel's own class and its tab class name (see Admin::panes(), Webfonts\Tab::panes())
	const MOUNTS = { design : 'design-form', 'design-typefaces' : 'design-typefaces-form' };

	TABS_OF_PANE.forEach( function( uri ) {
		const button = element('button');
		button.id = 'admin-tabbutton-'+ uri;
		button.className = 'nino-admin-tab';
		button.dataset.tab = uri;
		button.textContent = TEXT[TAB_WORDS[uri]] || 'Settings';
		registry.appendChild( button );
	} );

	head.appendChild( title );
	head.appendChild( registry );
	head.appendChild( actions );

	if( ( shell || {} ).head !== false )
		pane.appendChild( head );

	// One pane for each tab, with its mount in it; the Settings tab has none of this panel's
	const forms = {};

	TABS_OF_PANE.forEach( function( uri ) {
		const tabPane = element('div');
		tabPane.id = 'admin-tab-'+ uri;
		tabPane.dataset.tab = uri;
		pane.appendChild( tabPane );

		if( MOUNTS[uri] !== undefined ) {
			const mount = element('div');
			mount.id = MOUNTS[uri];
			tabPane.appendChild( mount );
			forms[uri] = mount;
		}
	} );

	// The mount of the screen that carries the preview and the bar, and the tab of the pane the shell has open, by its uri
	const live = function() { return forms.design };
	let opened = 'design';

	/*	The workbench's own questions (Nino.adminUi.choiceDialog()): each is kept with the options it was asked with and the element it stands in -
		drawn the way the kernel draws one: a body with a title, the text under the id its aria-describedby names, and the buttons - until a test
		answers it. The ones that stand open are found by byId(), as a browser finds an element of the document	*/
	const asks = [];

	function byId( id ) {
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
		// The element that has the focus, as document.activeElement says it: the one focused last while it stands in the pane and is not switched off, else the body, where a browser drops the focus
		get activeElement() { return focused !== null && focused.disabled !== true && descendants( pane ).indexOf( focused ) !== -1 ? focused : this.body },
		fonts						: {
			add			: function( face ) { typeset.set.push( face ) },
			delete	: function( face ) { typeset.set = typeset.set.filter( function( one ) { return one !== face } ) },
		},
	};

	const requests = [];

	/*	What the list of the themes hands to the browser and takes from it: the links that were clicked, the blobs that were made and the addresses made of
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
	// The observers of the boxes the script watches, in the order they were made
	const resizers = [];

	const view = { innerHeight : 800, fontSize : '16px', bar : '4.25rem', narrow : false };

	const rootStyle = {
		get fontSize() { return view.fontSize },
		getPropertyValue : function( name ) { return name === '--nino-admin-actionbar-height' ? view.bar : '' },
	};

	// The widths the frame was asked to be fitted at, in order - and how tall the port was then, which is what the stand-in of scaleFrame() records
	const fitted = [];

	/*	What the browser's own question does here: the text it was asked with is kept - the panel asks none, and the checks hold it to that -, and the answer is
		whatever the test last said it would be - yes	*/
	const confirms = [];
	let confirmAnswer = true;

	// What the panel told the other panels and the window beside the workbench, as the arguments of each Nino.admin.touch()
	const touched = [];

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
				// A required field is marked the way the workbench marks every one: the asterisk behind its name, out of reach of assistive technology, and aria-required on the control
				if( options.required === true ) {
					const star = element('span');
					star.className = 'nino-admin-required';
					star.setAttribute( 'aria-hidden', 'true' );
					star.textContent = '*';
					name.appendChild( star );
				}
				field.appendChild( name );
				const input = element('input');
				input.className = 'nino-admin-input';
				input.type = options.type || 'text';
				if( options.required === true )
					input.setAttribute( 'aria-required', 'true' );
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
			// A refusal under its field, as the workbench draws one: a paragraph after the label, the control marked invalid and described by it
			fieldError	: function( wrapper, control, id, text ) {
				const error = element('p');
				error.id = id;
				error.className = 'nino-admin-field-error';
				error.textContent = text;
				wrapper.parent.insertBefore( error, wrapper.nextSibling );
				if( control !== null ) {
					control.setAttribute( 'aria-invalid', 'true' );
					control.setAttribute( 'aria-describedby', id );
				}
				return error;
			},
			clearFieldErrors : function( root ) {
				byClass( root, 'nino-admin-field-error' ).forEach( function( el ) { el.remove() } );
				descendants( root ).filter( function( el ) { return el.getAttribute('aria-invalid') !== null } ).forEach( function( el ) {
					el.removeAttribute('aria-invalid');
					el.removeAttribute('aria-describedby');
				} );
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
			   Nino.admin.js answers it: null outside a pane with a head. A strip
			   handed to tabs() goes to the top of the tab's pane where the head holds
			   the registry's strip - buttons the shell wires through data-tab - and
			   the screen asking is one of its tabs, in place of the strip that stood
			   there; else after the name, in place of the strip that stood there */
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
						let tabPane = el;
						while( tabPane && tabPane.parent !== at )
							tabPane = tabPane.parent;
						const held = row.querySelector(':scope > .admin-panel-tabs');
						if( tabPane && tabPane.dataset.tab !== undefined && held !== null && byTag( held, 'button' ).some( function( button ) { return button.dataset.tab !== undefined } ) ) {
							const own = tabPane.querySelector(':scope > .admin-panel-tabs');
							if( own !== null && own !== strip )
								own.remove();
							strip.classList.add('admin-panel-tabs');
							tabPane.insertBefore( strip, tabPane.firstChild );
							return strip;
						}
						const before = held;
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
				// What the shell adds under the question, as the kernel draws it: a paragraph of its own
				if( typeof options.note === 'string' && options.note !== '' ) {
					const note = element('p');
					note.id = 'nino-admin-choice-note';
					note.textContent = options.note;
					body.appendChild( note );
				}
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
				// ...with the hint beside the label and the word of the state after it, which the kernel draws as well
				if( options.hint ) {
					const hint = element('small');
					hint.textContent = options.hint;
					copy.appendChild( hint );
				}
				label.appendChild( copy );
				const state = element('span');
				state.className = 'nino-admin-switch-state';
				state.textContent = TEXT[ input.checked ? '/_admin/common/label/on' : '/_admin/common/label/off' ];
				input.addEventListener( 'change', function() { state.textContent = TEXT[ input.checked ? '/_admin/common/label/on' : '/_admin/common/label/off' ] } );
				label.appendChild( state );
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
	Nino.admin = {
		dirty : ( shell || {} ).dirty || { register : function() {}, isDirty : function() { return false }, refresh : function() {} },
		touch : function() { touched.push( Array.prototype.slice.call( arguments ) ) },
	};

	/*	The workbench's mode and the questions it weighs - as the kernel does. `levels` keeps each question the panel put to it, with
		the level it was put at and whether the mode asked it. A question that is asked is the dialog's, drawn and held until a test answers it (settle()), as
		the kernel draws it: one at a time, the answer that goes on red for a destructive level	*/
	const shellMode = { mode : 'production', maintenance : false };
	const levels = [];

	{
		Nino.admin.mode = {
			current : function() { return shellMode.mode },
			policy : function( level ) {
				const live = shellMode.mode === 'production';
				if( level === 'routine' )
					return { ask : live, note : '' };
				if( level === 'big' )
					return { ask : live, note : live === true && shellMode.maintenance === false ? LIVE_NOTE : '' };
				return { ask : true, note : '' };
			},
		};
		Nino.adminUi.confirm = function( level, options ) {
			const policy = Nino.admin.mode.policy( level );
			levels.push( { level : level, options : options, asked : policy.ask } );
			if( policy.ask === false ) {
				options.onChoose( true );
				return true;
			}
			return Nino.adminUi.choiceDialog( {
				title : options.title, message : options.message, note : policy.note, opener : options.opener,
				choices : [
					{ value : 'yes', label : options.yes, kind : options.kind ?? ( level === 'routine' || level === 'big' ? 'primary' : 'danger' ) },
					{ value : 'no', label : options.no, kind : 'secondary' },
				],
				onChoose : function( value ) { options.onChoose( value === 'yes' ) },
			} );
		};
	}

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
	// The window answers a media query it is asked for: only the one that makes the strip of the objects a select, and only while the test says the window is that narrow
	sandbox.ResizeObserver = function( callback ) {
		this.callback = callback;
		this.watched = [];
		this.ended = 0;
		resizers.push( this );
	};
	sandbox.ResizeObserver.prototype.observe = function( el ) { this.watched.push( el ) };
	sandbox.ResizeObserver.prototype.disconnect = function() { this.watched = []; this.ended++ };
	sandbox.matchMedia = function( query ) { return { matches : view.narrow === true && query === NARROW_QUERY } };

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

	// The namespaces the shell asks for the screens of the tabs by their uri (see script.js, show()): a tab the script has no namespace for is a screen nobody can show
	[ 'design', 'design-typefaces' ].forEach( function( uri ) {
		if( Nino.admin[uri] === undefined || typeof Nino.admin[uri].showCurrent !== 'function' )
			throw new Error( 'the script has no screen for the tab '+ uri+ ': the shell asks Nino.admin[\''+ uri+ '\'].showCurrent() for it' );
	} );

	const design = Nino.admin.design;
	const setup = Nino.admin.design.views;
	const faces = Nino.admin['design-typefaces'];

	if( ( shell || {} ).raw !== true )
		design._apiCall = function( endpoint, payload, callback ) {
			requests.push( { endpoint : endpoint, payload : JSON.parse( JSON.stringify( payload ) ), answer : callback } );
		};

	// Left to itself, the script is on the panel's own screen
	const loadedOn = design._screen;

	/*	{ cold : true } is a panel nothing of which has been read: the shell shows the first tab, the script asks for the list itself (see init()) and the test gives the answer
		(answer()) - so nothing is stored, nothing is chosen and nothing is drawn before then	*/
	if( ( shell || {} ).cold !== true ) {

		design._data	= ( shell || {} ).whole === true ? over : listing( over );
		design._takeStored( design._data );
		design._ready	= true;

		/*	The mode the screen is drawn in: the setup, at the object the panel starts on - { screen : 'themes' } draws the list of the themes, which is what
			the panel opens on, and { object : 'header' } another object. The aspect the sections of this test look at (see look()) is put afterwards	*/
		if( ( shell || {} ).screen === 'themes' )
			design._screen = 'themes';
		else {
			design._screen = 'setup';
			setup._tab = ( shell || {} ).object || 'page';
		}

		design._render();
	}

	// What the first drawing fitted - the stage takes its room as the screen is drawn - is told apart from what a test then does
	const drawn = { fits : fitted.splice( 0 ).length };

	const api = {
		design 	: design,
		/** The aspect the screen is looked at through: the sections of this test were written for a screen of six views, one aspect of the design over all its objects, and read the controls of one by walking the objects (see look()). null: the screen as it is */
		lens		: null,
		/** The object that was looked at last, by aspect: where look() goes back to */
		lensAt	: {},
		/** The screen the script was on when it was loaded, before anything chose one */
		loadedOn	: loadedOn,
		setup		: setup,
		faces		: faces,
		requests: requests,
		fitted	: fitted,
		drawn		: drawn,
		timers	: timers,
		resizers: resizers,
		heard		: heard,
		view		: view,
		/** The delay and the function of the timers that are set and not cleared, the one that waits for the window to stop first */
		waiting	: function( delay ) {
			return timers.filter( function( timer ) { return timer !== null && timer[1] === delay } );
		},
		confirms: confirms,
		touched	: touched,
		/** The questions of the workbench that were asked, in order: { options, root, open, text, buttons, choose( value ) } */
		asks		: asks,
		stage		: stage,
		/** The list of the themes' dealings with the browser: the links clicked, the blobs made, the addresses made of them and revoked, and the readers a file was given to - and the body of the document, where a link stands while it is clicked */
		docBody		: dc.body,
		/** The document's root element, which scrolls the page in a narrow window */
		root			: dc.documentElement,
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
		/** What settle() answers the question that stands open with */
		willConfirm	: function( answer ) { confirmAnswer = answer },
		/** The question of the workbench that stands open, answered as willConfirm() said: the apply's is answered by the check that looks at it, with choose() */
		settle		: function() {
			const open = asks.filter( function( ask ) { return ask.open === true } )[0];
			if( open !== undefined )
				open.choose( confirmAnswer === false ? 'no' : 'yes' );
		},
		/** The questions put to the user, by their text: the dialogs of the workbench, the apply's among them */
		questions	: function() {
			return asks.map( function( ask ) { return ask.options.message } );
		},
		/** A question of the workbench's own that stands open, so that the next one cannot be asked */
		holdOpen	: function() { Nino.adminUi.choiceDialog( { message : 'held', choices : [ { value : 'ok', label : 'OK', kind : 'primary' } ], onChoose : function() {} } ) },
		/** The mode the workbench is in, and whether its maintenance page is up  */
		setMode	: function( mode, maintenance ) { shellMode.mode = mode; shellMode.maintenance = maintenance === true },
		/** The questions put to the workbench's confirm(), in order: { level, options, asked } */
		levels	: levels,
		/** The oldest request not yet answered, answered - the way the server would */
		answer	: function( status, response ) {
			const next = requests.shift();
			next.answer( status, response );
			return next;
		},
		/** The mount of the screen that carries the preview and the bar, whichever of the two it is drawn in */
		get form() { return live() },
		/** The two mounts, by the uri of the tab they stand in */
		forms		: forms,
		pane		: pane,
		head		: head,
		/** The uri of the tab of the pane the shell has open: the panel's own screen at the start */
		get opened() { return opened },
		/** The namespaces the shell asks for the screens of the tabs, by the uri of the tab: Nino.admin as the script left it */
		admin		: Nino.admin,
		/** The strip the shell draws on top of the pane, and the button of one of its tabs by the uri of the tab */
		registry: registry,
		kernelButton : function( uri ) { return byId( 'admin-tabbutton-'+ uri ) },
		/** The shell shows a tab of the pane - the one that is open, the others hidden - and asks the namespace of that tab for its screen, as script.js does in openTab() and show() */
		open		: function( uri ) {
			opened = uri;
			TABS_OF_PANE.forEach( function( each ) {
				byId( 'admin-tab-'+ each ).hidden = each !== uri;
				byId( 'admin-tabbutton-'+ each ).classList.toggle( 'is-active', each === uri );
			} );
			if( Nino.admin[uri] !== undefined && typeof Nino.admin[uri].showCurrent === 'function' )
				Nino.admin[uri].showCurrent();
		},
		/** The bar of the views: at the top of the tab's pane, under the shell's strip - or after the name in the head where the pane has no strip of tabs */
		headStrips : function() {
			return byClass( pane, 'design-tabs' );
		},
		/** The action bar, and what the panel says in it: the line, and the chip for the state of the files */
		bar			: function() { return byClass( live(), 'nino-admin-actionbar' )[0] || null },
		line		: function() { return byId('design-msg') },
		chip		: function() { return byId('design-state-word') },
		/** What the knob rows of the open tab say, as [ label, word of the step that is lit ] - a row that is greyed out says nothing - block after block, each opened in turn where the tab is an accordion */
		lit			: function() {

			let said = [];

			eachBlock( live(), function( card ) {
				said = said.concat( byClass( card, 'design-knob-row' ).filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) === false } ).map( function( row ) {
					return [ rowName( row ), litOf( row ) ];
				} ) );
			} );

			return said;
		},
		/** One knob row of the open tab, by the name it carries - in the block that is open or in another, which is opened for it */
		knobRow : function( label ) {
			return seek( live(), function() {
				return byClass( live(), 'design-knob-row' ).filter( function( row ) {
					return rowName( row ) === label;
				} )[0] || null;
			} );
		},
		/** The buttons of a strip - a knob row, or a strip of the page - by the name it carries */
		steps : function( label ) {
			const row = this.knobRow( label );
			return row === null ? [] : byTag( row, 'button' );
		},
		/** Move a knob, or a strip of the page, the way a hand does: press the button whose value it is */
		move : function( label, value ) {
			this.steps( label ).filter( function( button ) {
				return button.dataset.value === String( value );
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** What a strip says it stands at: the pressed state of its buttons, in order */
		pressed : function( label ) {
			return this.steps( label ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join();
		},
		/** The setup open, as Edit on the loaded theme leaves it: the panel's own screen shown, and the mode of it the setup - drawn straight, with no theme loaded for it. Where an aspect is looked at, the screen is put back to it (see look()) */
		enter : function() {
			if( opened !== 'design' )
				this.open('design');
			if( design._screen !== 'setup' ) {
				design._screen = 'setup';
				design._render();
				if( this.lens !== null )
					this.look( this.lens );
			}
		},
		/** Look at an aspect of the design, the way the sections of this test were written: the screen drawn for the first object that has a control on it - or the one it was looked at under last - with its block open. No tab is pressed and nothing moves in the frame: it is the column and nothing else (see goTo()). The controls of the aspect of the other objects are reached by walking them (see eachBlock() and seek()) */
		look : function( aspect ) {
			this.enter();
			// Looked at again, as a press on the tab that is open: the screen stays where it is
			const same = this.lens === aspect;
			this.lens = aspect;
			const objects = tableObjects( design._data.layout, aspect );
			if( objects.length === 0 )
				return;
			const now = setup._current();
			lookAtObject( this, same === true && objects.indexOf( now ) !== -1 ? now : ( objects.indexOf( this.lensAt[aspect] ) !== -1 ? this.lensAt[aspect] : objects[0] ), aspect );
		},
		/** Open another aspect or another object by the word on it: an aspect is looked at (see look()), an object is the tab pressed, wherever the strip stands */
		tab : function( label ) {
			const aspect = ( design._data.layout.tabs || [] ).filter( function( each ) { return tabWord( each ) === label } )[0];
			if( aspect !== undefined )
				return this.look( aspect );
			this.enter();
			byTag( byClass( pane, 'design-tabs' )[0], 'button' ).filter( function( button ) {
				return button.textContent === label;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** The tab of an object pressed, by its key, the way a hand does - the strip of the objects, not the select */
		go : function( object ) {
			this.enter();
			this.lens = null;
			byTag( byClass( pane, 'design-tabs' )[0], 'button' ).filter( function( button ) {
				return button.dataset.tab === object;
			} ).forEach( function( button ) { button.fire('click') } );
		},
		/** Back to the list: the button of the bar of the views pressed, as a hand does - the list shown as it is where the views are not open */
		back : function() {
			if( design._screen === 'setup' )
				byId('design-back').fire('click');
		},
		/** Open an aspect of the setup by its key (the keys of TABS, not the words on the buttons), or an object by its key - or, with 'theme', the list of the themes, the shell showing the tab and the way back pressed where the views are open - or, with 'typefaces', the loader */
		show : function( tab ) {
			if( tab === 'theme' ) {
				if( opened !== 'design' )
					this.open('design');
				this.back();
			}
			else if( tab === 'typefaces' ) {
				if( opened !== 'design-typefaces' )
					this.open('design-typefaces');
			}
			else if( ( design._data.layout.objects || [] ).indexOf( tab ) !== -1 )
				this.go( tab );
			else
				this.look( tab );
		},
		/** The controls of the open view: the box under the eyebrow */
		body : function() { return byId('design-body') },
		/** The mount of the loader, which holds the line it says things in and its two cards - the tab has neither preview nor bar - and that line */
		faceBox : function() { return forms['design-typefaces'] },
		faceLine : function() { return byId('design-faces-msg') },
		byId : byId,
	};

	// The helpers below reach the screen of a mount from the mount: eachBlock() and seek() are handed the element
	forms.design.owner = api;

	// The sections of this test were written on the Templates of a screen of six views: that is what a panel looks at, unless it is told another aspect or none
	if( design._screen === 'setup' && ( shell || {} ).lens !== false && ( shell || {} ).object === undefined )
		api.look( ( shell || {} ).tab || 'templates' );

	return api;
}

/** A text of the panel with its %s filled in, the way the script's own _text() does */
function say( key ) {
	let out = TEXT[key];
	Array.prototype.slice.call( arguments, 1 ).forEach( function( value ) { out = out.replace( /%[sdn]/, function() { return String( value ) } ) } );
	return out;
}

/** The card of the list of the themes: the one card the panel's own screen has in its first mode */
const LIST_CARD = 'design-themes';

/** The body of the block CSS, the aspect that is the page's alone: the one field of the css of the theme */
const CSS_CARD = 'design-row-css-page-body';

/** Which aspect the controls are those of, read off what is drawn rather than off the panel's own idea of it: the tab of the pane the shell has open - the loader, or the panel's own screen, whose card says which mode and, in the setup, which aspect the screen is looked at through (see look()), else the aspect of the block that is open */
function tabOf( screen ) {

	if( screen.opened === 'design-typefaces' )
		return screen.byId('design-faces-installed') !== null ? 'typefaces' : null;

	if( screen.byId( LIST_CARD ) !== null )
		return 'theme';

	if( screen.lens !== null && screen.lens !== undefined )
		return screen.lens;

	const card = byClass( screen.form, 'design-form-section' ).filter( function( el ) { return el.dataset.tab !== undefined } )[0];

	return card === undefined ? null : card.dataset.tab;
}

/** What is drawn on the screen, as one word: 'theme' for the list, the key of the view for a view, 'typefaces' for the loader - what a check reads where it asks what the screen shows and not which tab */
function screenOf( screen ) { return tabOf( screen ) }

/** The words on the buttons of the strip the shell draws on top of the pane, in order, as the stand-in has them */
function kernelLabels( screen ) { return byTag( screen.registry, 'button' ).map( function( button ) { return button.textContent } ).join() }

/** The word on a tab, on an object and on a knob: the texts of their own keys, so that a check names a control by what the panel calls it and not by a word typed here */
function tabWord( tab ) { return TEXT['/_admin/design/tab/'+ tab+ '/label'] }
function objectWord( object ) { return TEXT['/_admin/design/object/'+ object+ '/label'] }
function knobWord( part, knob ) { return TEXT['/_admin/design/knob/'+ part+ '/'+ knob+ '/label'] }

/**
 *	The words of the rest of what the screen says, read the same way: the chip's word for a state of the files, the name of a font role and of a
 *	column of the table of the roles, of a family the feature brings and of a weight, and the name of a colour knob with the words of its positions
 */
function shortWord( state ) { return TEXT['/_admin/design/state/short/'+ state] }
function roleWord( role ) { return TEXT['/_admin/design/font/role/'+ role] }
function columnWord( column ) { return TEXT['/_admin/design/font/head/'+ column] }
function familyWord( slug ) { return TEXT['/_admin/design/family/'+ slug] }
function weightWord( weight ) { return TEXT['/_admin/design/weight/'+ weight] }
function colourWord( knob ) { return TEXT['/_admin/design/colour/'+ knob+ '/label'] }
function colourStepWords( knob, count ) {

	const words = [];

	for( let position = 1; position <= count; position++ )
		words.push( TEXT['/_admin/design/colour/'+ knob+ '/'+ position] );

	return words;
}
function colourSteps( knob, count ) { return colourStepWords( knob, count ).join() }

/**
 *	What the table gives a tab, as the screen is to draw it, counted from the table design/list handed over and from nothing else: for each
 *	object that has a control on the tab, in the order the table names the objects, the controls of it in the order of their places, each as the
 *	keys that stand at that place, joined by a space
 */
function tableRows( layout, tab ) {

	const rows = [];

	layout.objects.forEach( function( object ) {

		const places = {};

		Object.keys( layout.controls ).forEach( function( key ) {
			const at = layout.controls[key];
			if( at[0] === tab && at[1] === object )
				( places[ at[2] ] = places[ at[2] ] || [] ).push( key );
		} );

		const orders = Object.keys( places ).map( Number ).sort( function( a, b ) { return a - b } );

		if( orders.length > 0 )
			rows.push( { object : object, controls : orders.map( function( order ) { return places[order].join(' ') } ) } );
	} );

	return rows;
}

/** The blocks of the open view, every view the table draws being an accordion: the cards that have a head - the list of the themes, the loader of the typefaces and the view CSS, which the table does not draw, have none */
function blocksOf( root ) {
	return byClass( root, 'design-accordion' );
}

/** The head of a block: the button that opens and closes it */
function headButton( card ) {
	return byClass( card, 'design-accordion-head' )[0] || null;
}

/** Whether a block is open, as its head says */
function isOpen( card ) {
	return headButton( card ).getAttribute('aria-expanded') === 'true';
}

/** The head of a block pressed, the way a hand does: the block opens, or - where it was the open one - closes */
function toggle( card ) {
	headButton( card ).fire('click');
}

/** A block opened by its head, unless it is open */
function openBlock( card ) {

	if( isOpen( card ) === false )
		toggle( card );
}

/** The tab as it was before a walk through its blocks: the block that was open open again, and none open where none was */
function restoreBlocks( root, was ) {

	if( was !== null )
		return openBlock( was );

	const open = blocksOf( root ).filter( isOpen )[0];

	if( open !== undefined )
		toggle( open );
}

/** The block of the aspect the screen is looked at through, under an object: the object is drawn for it, the block as it stands */
function lensBlock( screen, object ) {
	goTo( screen, object );
	return blocksOf( screen.form ).filter( function( card ) { return card.dataset.tab === screen.lens } )[0];
}

/** What the head of that block says once it is closed, the block closed first where it is open */
function closedLine( screen, object ) {

	if( isOpen( lensBlock( screen, object ) ) === true )
		toggle( lensBlock( screen, object ) );

	return byClass( lensBlock( screen, object ), 'design-accordion-summary' )[0].textContent;
}

/** The aspects an object has a block of, in the order of the table */
function aspectsOf( layout, object ) {
	return layout.tabs.filter( function( aspect ) { return tableObjects( layout, aspect ).indexOf( object ) !== -1 } );
}

/** The objects that have a block of an aspect, in the order of the table, counted from the table design/list handed over and from nothing else: the css is the page's, and no control of the table */
function tableObjects( layout, aspect ) {

	return layout.objects.filter( function( object ) {
		return aspect === 'css' ? object === 'page' : Object.keys( layout.controls ).some( function( key ) { return layout.controls[key][0] === aspect && layout.controls[key][1] === object } );
	} );
}

/** Another object, drawn without a tab pressed: the column is replaced and nothing else moves - no jump, no fit - the way a walk through the objects looks at each */
function goTo( screen, object, force ) {

	if( force !== true && screen.setup._current() === object && screen.byId('design-controls') !== null && blocksOf( screen.form ).length > 0 )
		return;

	screen.setup._tab = object;
	screen.byId('design-controls').replaceWith( screen.design._renderControls() );
}

/** An object drawn with the block of an aspect open, and remembered as where that aspect was looked at */
function lookAtObject( screen, object, aspect ) {

	goTo( screen, object );
	screen.lensAt[aspect] = object;

	const card = blocksOf( screen.form ).filter( function( block ) { return block.dataset.tab === aspect } )[0];

	if( card !== undefined )
		openBlock( card );
}

/** What a walk through the objects changes, and puts back: the object that is open, and the block that is open under each */
function saveWalk( screen ) {
	return { object : screen.setup._current(), open : JSON.stringify( screen.setup._open ), lensAt : JSON.stringify( screen.lensAt ) };
}

function endWalk( screen, saved ) {
	screen.setup._open = JSON.parse( saved.open );
	screen.lensAt = JSON.parse( saved.lensAt );
	goTo( screen, saved.object, true );
}

/** The screen a mount belongs to, where the screen is looked at through an aspect (see look()), else null */
function lensOf( root ) {
	return root.owner !== undefined && root.owner.lens !== null && root.owner.lens !== undefined && root.owner.design._screen === 'setup' ? root.owner : null;
}

/**
 *	Every block of the open tab, one after the other, each opened in turn - which closes the one that was open - and handed to a function; at the end the
 *	tab is as it was. Where the screen is looked at through an aspect, the blocks are that aspect's, one in each object that has one: the objects are walked. A tab with no blocks, the Theme and the Typefaces, has nothing to hand over
 */
function eachBlock( root, visit ) {

	const screen = lensOf( root );

	if( screen === null ) {

		const cards = blocksOf( root );
		const was = cards.filter( isOpen )[0] || null;

		cards.forEach( function( card ) {
			openBlock( card );
			visit( card );
		} );

		return restoreBlocks( root, was );
	}

	const saved = saveWalk( screen );

	tableObjects( screen.design._data.layout, screen.lens ).forEach( function( object ) {
		lookAtObject( screen, object, screen.lens );
		visit( blocksOf( root ).filter( function( block ) { return block.dataset.tab === screen.lens } )[0] );
	} );

	endWalk( screen, saved );
}

/**
 *	What `find` answers with once it answers with something, looked for in the block that is open and then in the others, which are opened one after the other
 *	for it - the one it is found in stays open. Where the screen is looked at through an aspect, in the objects that have a block of it instead. Where it is nowhere the tab is as it was, and the answer is null
 */
function seek( root, find ) {

	const screen = lensOf( root );

	if( screen === null ) {

		const found = find();

		if( found !== null )
			return found;

		const cards = blocksOf( root );
		const was = cards.filter( isOpen )[0] || null;
		let hit = null;

		cards.some( function( card ) {
			openBlock( card );
			hit = find();
			return hit !== null;
		} );

		if( hit === null )
			restoreBlocks( root, was );

		return hit;
	}

	const saved = saveWalk( screen );
	const objects = tableObjects( screen.design._data.layout, screen.lens );
	const order = objects.indexOf( saved.object ) === -1 ? objects : [ saved.object ].concat( objects.filter( function( object ) { return object !== saved.object } ) );
	let hit = null;

	order.some( function( object ) {
		lookAtObject( screen, object, screen.lens );
		hit = find();
		return hit !== null;
	} );

	if( hit === null )
		endWalk( screen, saved );

	return hit;
}

/** The card of a row that an element of it stands in: the nearest of its ancestors that says which object it is the row of - the body of a block is between them. An element that was collected from a block (knobRows()) says its card itself: the body of a block that is closed again is let go of, and takes its controls with it */
function cardOf( el ) {

	if( el.card !== undefined )
		return el.card;

	let at = el;

	while( at !== null && at.dataset.object === undefined )
		at = at.parent;

	return at;
}

/** What the open tab draws: a card for each row it has, in the order they stand in, and in each the keys of every control it carries, in the order they stand in - a block that is closed has none until it is opened, which this does in turn */
function drawnRows( screen ) {

	const rows = [];

	eachBlock( screen.form, function( card ) {
		rows.push( {
			object 		: card.dataset.object,
			controls 	: descendants( card ).filter( function( el ) { return el.dataset.control !== undefined } ).map( function( el ) { return el.dataset.control } ),
		} );
	} );

	return rows;
}

/** The control of the open tab that stands for a key of the table: the element that says so in data-control - in the block the table puts it in, which is opened for it where it is closed */
function controlOf( screen, key ) {

	const find = function() {
		return descendants( screen.form ).filter( function( el ) { return el.dataset.control !== undefined && el.dataset.control.split(' ').indexOf( key ) !== -1 } )[0] || null;
	};
	const place = ( ( screen.design._data.layout || {} ).controls || {} )[key] || [];

	// The object the control stands under is drawn for it, wherever the screen was
	if( place.length > 0 && screen.setup._current() !== place[1] )
		goTo( screen, place[1] );

	const card = blocksOf( screen.form ).filter( function( block ) { return block.dataset.tab === place[0] && block.dataset.object === place[1] } )[0];

	if( card !== undefined )
		openBlock( card );

	return find();
}

/** The select of a template, the part's, in the block of its object on the Templates view - which is opened for it where it is closed, and the one that was open closes: null where the open tab has none */
function setSelect( screen, part ) {

	const field = controlOf( screen, 'parts/'+ part );

	return field === null ? null : byTag( field, 'select' )[0] || null;
}

/** Press the button of a strip that holds a value, the strip found by the key the table has the control under - a knob of one object is told from the knob of another that goes by the same name */
function pressKey( screen, key, value ) {
	byTag( controlOf( screen, key ), 'button' ).filter( function( button ) { return button.dataset.value === String( value ) } ).forEach( function( button ) { button.fire('click') } );
}

/** Move a knob by the key the table has it under, the way a hand does: press the button of a strip that holds the position, or turn a switch on at 1 and off at 0 */
function moveKey( screen, key, position ) {

	const row = controlOf( screen, key );

	if( row !== null && isSwitch( row ) === true ) {
		const input = byTag( row, 'input' )[0];
		input.checked = position === 1;
		input.fire('change');
		return;
	}

	// An ink is chosen among the tones of the page, whichever the position says: a tone is no position
	if( row !== null && isSelect( row ) === true ) {
		choose( byTag( row, 'select' )[0], 'primary' );
		return;
	}

	pressKey( screen, key, position );
}

/** What the buttons of a strip say is pressed, in order, the strip found by the key the table has the control under - where knobs of two objects go by the same label, as the space of a section and of a card do, the label does not tell them apart */
function pressedKey( screen, key ) {
	return byTag( controlOf( screen, key ), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join();
}

/** The smallest change of the selection: the title of a section a step up, on the Type view - which is opened for it */
function nudge( screen, position ) {
	screen.tab( tabWord('type') );
	pressKey( screen, 'knobs/section/title', position === undefined ? 1 : position );
}

/** What the knob rows of the open tab say, in the order the table gives them: [ label, word of the step that is lit ] for each row that is not greyed out */
function knobLit( screen ) {

	let said = [];

	eachBlock( screen.form, function( card ) {
		said = said.concat( byClass( card, 'design-knob-row' ).filter( function( row ) {
			return typeof row.dataset.control === 'string' && row.dataset.control.indexOf('knobs/') === 0 && hasClass( row, 'design-knob-row--off' ) === false;
		} ).map( function( row ) {
			return [ rowName( row ), litOf( row ) ];
		} ) );
	} );

	return said;
}

/** What a knob row says it stands at: the word on the button of a strip that is lit, the word of the state of a switch - on or off */
function litOf( row ) {

	if( isSwitch( row ) === true )
		return byTag( row, 'input' )[0].checked === true ? TEXT['/_admin/common/label/on'] : TEXT['/_admin/common/label/off'];

	if( isSelect( row ) === true )
		return wordOf( byTag( row, 'select' )[0] );

	return byTag( row, 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).map( function( button ) { return button.textContent } ).join();
}

/** The knob rows of the open tab that are greyed out, in every block - each opened in turn, and the tab as it was after it */
function offRows( screen ) {
	return knobRows( screen ).filter( function( row ) { return hasClass( row, 'design-knob-row--off' ) } );
}

/** The knob rows of the open tab, greyed out or not - those of the blocks that are closed as they are drawn when the block is opened */
function knobRows( screen ) {

	let rows = [];

	eachBlock( screen.form, function( card ) {
		rows = rows.concat( byClass( card, 'design-knob-row' ).filter( function( row ) { return typeof row.dataset.control === 'string' && row.dataset.control.indexOf('knobs/') === 0 } ).map( function( row ) {
			row.card = card;
			return row;
		} ) );
	} );

	return rows;
}

/** A key pressed on a tab, the way the kernel's tabKeys() hears it */
function press( button, key ) {
	( button.listeners.keydown || [] ).forEach( function( fn ) { fn( { key : key, preventDefault : function() {} } ) } );
}

check( 'the stand-in reads the panel\'s words out of text/en_US.php', Object.keys( TEXT ).length > 150
	&& TABS.every( function( tab ) { return typeof tabWord( tab ) === 'string' && tabWord( tab ) !== '' } ) && OBJECTS.every( function( object ) { return typeof objectWord( object ) === 'string' && objectWord( object ) !== '' } )
	&& TEXT['/_admin/design/hint/unanswered'].indexOf('%s') !== -1 );


// --- The pane ------------------------------------------------------------------
//
// Three tabs on top of the pane - Themes, Typefaces and the Settings the registry
// attaches - are the shell's strip, and each of the first two is a namespace the shell
// asks for its screen by the uri of its tab (see script.js, show()). The panel's name is
// the head's, so no screen draws a heading of its own. The panel's own screen has two
// modes, the list of the themes and the setup in six views, drawn in one mount over one
// preview and one bar. The bar of the views - Back to list and the strip of the six -
// stands at the top of the pane of the tab, under the shell's strip, where the Features
// panel's own strip stands in its tab: the strip switches the column of controls and
// nothing else, and the frame beside the column stays on every view

console.log( 'The pane' );

const headed = panel( {}, { lens : false } );
const strip = byClass( headed.pane, 'design-tabs' )[0] || null;
const wrap = strip === null ? null : strip.children[1];
const tablist = wrap === null ? null : wrap.children[0];
const tabs = tablist === null ? [] : byTag( tablist, 'button' );
const objectSelect = strip === null ? null : strip.children[3];
const tierStrip = strip === null ? null : strip.children[2];
const tierTabs = tierStrip === null ? [] : byTag( tierStrip, 'button' );
const kernelTabs = byTag( headed.registry, 'button' );
const tableObjectKeys = headed.design._data.layout.objects;
// ...and Tablet and Mobile, which are no objects, stand in a strip of their own beside the objects' (see the screens of the tiers below)
const tierTabKeys = headed.design._tierKeys().map( function( tier ) { return 'tier:'+ tier } );

check( 'the shell asks two namespaces for the screens of the pane, by the uris of the tabs: Nino.admin.design for the panel\'s own screen and Nino.admin[\'design-typefaces\'] for the loader - each a screen of its own that answers showCurrent() - and the setup is a namespace of the panel\'s, Nino.admin.design.views',
	Object.keys( headed.admin ).filter( function( key ) { return key !== 'dirty' && key !== 'mode' && key !== 'touch' } ).sort().join() === 'design,design-typefaces'
	&& PANE.slice( 0, 2 ).every( function( uri ) { return typeof headed.admin[uri].showCurrent === 'function' && headed.admin[uri].showCurrent !== headed.admin.design.init } )
	&& headed.setup === headed.admin.design.views && headed.admin.design !== headed.setup && headed.setup !== headed.faces && headed.faces === headed.admin['design-typefaces']
	&& kernelTabs.map( function( tab ) { return tab.dataset.tab } ).join() === PANE.join() && kernelTabs.map( function( tab ) { return tab.id } ).join() === PANE.map( function( uri ) { return 'admin-tabbutton-'+ uri } ).join() );
check( 'the strip on top has a tab for each object of the table design/list handed over, in its order, each named by the text of its own key - the eight of Setup::OBJECTS first, and neither the theme, the typefaces nor an aspect is one',
	headed.design._objectKeys().join() === tableObjectKeys.join() && tableObjectKeys.slice( 0, OBJECTS.length ).join() === OBJECTS.join() && tabs.length === tableObjectKeys.length
	&& tabs.map( function( tab ) { return tab.dataset.tab } ).join() === tableObjectKeys.join()
	&& tabs.map( function( tab ) { return tab.id } ).join() === tableObjectKeys.map( function( key ) { return 'design-tab-'+ key } ).join()
	&& tabs.slice( 0, OBJECTS.length ).map( function( tab ) { return tab.textContent } ).join() === OBJECTS.map( objectWord ).join()
	&& TABS.every( function( aspect ) { return tabs.every( function( tab ) { return tab.dataset.tab !== aspect } ) } ) );
check( '...and the texts name the aspects and no other: the key of one that is gone is one nobody reads',
	Object.keys( TEXT ).filter( function( key ) { return /^\/_admin\/design\/tab\/[a-z0-9-]+\/label$/.test( key ) } ).map( function( key ) { return key.split('/')[4] } ).sort().join() === TABS.slice().sort().join() );
check( '...a tablist of tabs, named for the object, the open one selected and the others not - the first object at the start of a visit',
	tablist !== null && tablist.getAttribute('role') === 'tablist' && tablist.getAttribute('aria-label') === TEXT['/_admin/design/label/object'] && hasClass( tablist, 'nino-admin-tabs--bar' )
	&& tabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' && hasClass( tab, 'nino-admin-tab' ) } )
	&& tabs.map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === tableObjectKeys.map( function( key ) { return String( key === tableObjectKeys[0] ) } ).join()
	&& headed.setup._tab === tableObjectKeys[0] && headed.setup._current() === tableObjectKeys[0] && headed.design.THEMES_ANCHOR === 'global' );
check( '...the same choice as a select, drawn from the same model: an option for each tab in the same order - the objects first, then those of Tablet and Mobile - with the same words, the open object chosen, named for the object - the stylesheet shows the select where the strip gives way (see the stylesheet below)',
	objectSelect !== null && objectSelect.tagName === 'SELECT' && objectSelect.id === 'design-object-select' && objectSelect.getAttribute('aria-label') === TEXT['/_admin/design/label/object']
	&& offered( objectSelect ).map( function( option ) { return option[0] } ).join() === tabs.concat( tierTabs ).map( function( tab ) { return tab.dataset.tab } ).join()
	&& offered( objectSelect ).map( function( option ) { return option[1] } ).join() === tabs.concat( tierTabs ).map( function( tab ) { return tab.textContent } ).join() && shown( objectSelect ) === tableObjectKeys[0] );
check( 'the bar begins with Back to list: a button of the bar, in the words of the workbench, that is no tab - no role, no data-tab, not in the tablist - and the strip of the objects, the strip of Tablet and Mobile and the select follow it, nothing else',
	strip !== null && strip.id === 'design-tabs' && hasClass( strip, 'nino-admin-tabs' ) && hasClass( strip, 'nino-admin-tabs--bar' ) && strip.children.length === 4 && strip.children[0] === headed.byId('design-back')
	&& strip.children[0].tagName === 'BUTTON' && strip.children[0].type === 'button' && strip.children[0].textContent === TEXT['/_admin/common/label/back'] && hasClass( strip.children[0], 'nino-admin-tab' )
	&& strip.children[0].getAttribute('role') === null && strip.children[0].dataset.tab === undefined && tabs.indexOf( strip.children[0] ) === -1 && byTag( strip, 'button' ).length === tableObjectKeys.length + tierTabKeys.length + 3
	&& hasClass( wrap, 'design-views-wrap' ) && wrap.children.length === 3 && wrap.children.slice( 1 ).every( function( arrow ) { return hasClass( arrow, 'design-views-more' ) } )
	&& tierTabs.map( function( tab ) { return tab.dataset.tab } ).join() === tierTabKeys.join() && hasClass( tierStrip, 'design-tiers' ) && tierStrip.getAttribute('role') === 'tablist' && tierStrip.parent === strip
	&& tierTabs.every( function( tab ) { return tab.getAttribute('role') === 'tab' && tab.parent === tierStrip && wrap.children[0].children.indexOf( tab ) === -1 } ) );

/*	Left to itself the panel opens on its own screen, in its list: the first thing a person does is pick a theme. The setup is what the sections of this test are drawn on,
	the Base of the header the first of them (see panel())	*/
const defaulted = panel( {}, { screen : 'themes' } );

check( 'the panel opens on its own screen, which the strip calls Themes - the one the shell has open and the one drawn: the list of the themes, with the preview and the bar, and none of the choices the setup has - and no bar of the objects',
	defaulted.opened === 'design' && defaulted.loadedOn === 'themes' && defaulted.design._screen === 'themes' && tabOf( defaulted ) === 'theme' && defaulted.byId('design-set-header') === null
	&& defaulted.byId('design-layout') !== null && defaulted.byId('design-frame') !== null && defaulted.byId('design-save') !== null && defaulted.forms.design.children.length > 0
	&& byClass( defaulted.pane, 'design-tabs' ).length === 0 && defaulted.byId('design-back') === null );
check( 'the screen draws no heading of its own - the head names the panel',
	byTag( headed.form, 'h1' ).concat( byTag( headed.form, 'h2' ), byTag( headed.form, 'h3' ) ).length === 0 && byTag( defaulted.form, 'h2' ).length === 0 );
check( 'the bar of the objects is handed to the top of the pane of the tab, under the shell\'s strip, and not to the head: the head keeps the name, the shell\'s three tabs and the slot for actions, the bar is the first thing in the pane of its tab',
	strip !== null && strip.parent === headed.byId('admin-tab-design') && headed.byId('admin-tab-design').children[0] === strip && strip.classList.contains('admin-panel-tabs')
	&& headed.head.children.length === 3 && hasClass( headed.head.children[0], 'admin-panel-title' ) && headed.head.children[1] === headed.registry && hasClass( headed.head.children[2], 'admin-panel-actions' )
	&& byClass( headed.form, 'design-tabs' ).length === 0 && byTag( headed.registry, 'button' ).length === 3 );

// One preview and one bar, in one mount: the list and the setup are drawn in it one at a time, and what the other held is gone
const inPane = function( screen, id ) { return descendants( screen.pane ).filter( function( el ) { return el.id === id } ).length };
const once = [ 'design-layout', 'design-frame', 'design-save', 'design-msg' ];

check( 'one preview and one bar: drawn on the setup the layout stands in the mount of the panel\'s own screen and the mount of the loader is empty - and the same mount holds the list once the way back is pressed - with one layout, one frame and one bar in the document each time',
	headed.forms.design.children.length > 0 && headed.forms['design-typefaces'].children.length === 0 && once.every( function( id ) { return inPane( headed, id ) === 1 } ) && headed.design._screen === 'setup'
	&& ( headed.byId('design-back').fire('click'), headed.forms.design.children.length > 0 && once.every( function( id ) { return inPane( headed, id ) === 1 } )
		&& headed.byId('design-layout').parent === headed.forms.design && tabOf( headed ) === 'theme' && headed.design._screen === 'themes' ) );

/*	Back to list leaves the setup and discards nothing: the list is drawn in the mount of the setup, the bar taken out of the pane, no request made, and the draft stays -
	the working copy as it was, the way back still shown	*/
const bowing = panel();
nudge( bowing );
const bowingEdit = JSON.stringify( bowing.design._edit );
const bowingBar = byClass( bowing.pane, 'design-tabs' )[0];
const bowingRequests = bowing.requests.length;

bowing.byId('design-back').fire('click');

check( 'Back to list draws the list in the mount, takes the bar of the objects out of the pane, posts no action of the themes - the preview that follows a drawing asks for itself after its delay, as it does on a switch of object - and keeps the draft: the working copy is as it was, the way back is shown',
	bowing.design._screen === 'themes' && tabOf( bowing ) === 'theme' && byClass( bowing.pane, 'design-tabs' ).length === 0 && bowingBar.parent === null && bowing.byId('design-back') === null
	&& JSON.stringify( bowing.design._edit ) === bowingEdit && bowing.design._isDirty() === true && bowing.byId('design-reset').hidden === false
	&& bowing.requests.length === bowingRequests && bowing.byId('design-layout') !== null && bowing.forms.design.children.length > 0 );

// The mode that was open is the mode the shell shows again, as the setup was before: the panel remembers it while it lives, and the first time it is the list
const remembering = panel( {}, { lens : false } );
remembering.byId('design-back').fire('click');
const rememberedList = remembering.byId( LIST_CARD );
remembering.open('design-typefaces');
remembering.open('design');

check( 'the shell showing the panel\'s own screen draws the mode that was open: the list, after the loader was shown, drawn anew',
	tabOf( remembering ) === 'theme' && remembering.design._screen === 'themes' && remembering.byId( LIST_CARD ) !== null && remembering.byId( LIST_CARD ) !== rememberedList && byClass( remembering.pane, 'design-tabs' ).length === 0 );

remembering.design._screen = 'setup';
remembering.design._render();
remembering.open('design-typefaces');
remembering.open('design');

check( '...and the setup, where it was open: the bar and the object that was open last, the list gone from the mount',
	remembering.design._screen === 'setup' && byClass( remembering.pane, 'design-tabs' ).length === 1 && remembering.byId( LIST_CARD ) === null
	&& remembering.setup._current() === tableObjectKeys[0] && remembering.byId( 'design-tab-'+ tableObjectKeys[0] ).getAttribute('aria-selected') === 'true' );

const firstShown = panel( {}, { cold : true, screen : 'themes' } );
firstShown.open('design');
firstShown.answer( 200, listing() );

check( '...and the first time it is shown, before anything was chosen, it is the list',
	firstShown.design._screen === 'themes' && tabOf( firstShown ) === 'theme' );

// Opening the setup again draws the screen again (showCurrent())
headed.design._screen = 'setup';
headed.design.showCurrent();
headed.design.showCurrent();

check( '...and a screen drawn again puts one bar there, not a second beside the first',
	byClass( headed.pane, 'design-tabs' ).length === 1 && headed.byId('admin-tab-design').children.length === 2
	&& hasClass( headed.byId('admin-tab-design').children[0], 'design-tabs' ) );

/*	A tab is pressed: the column is drawn for the object, the bar stays the very element it was - the keyboard is on the tab - and the select
	says the same object. The aspects are those of the table: one block for each aspect the object has a control on	*/
const shownStrip = byClass( headed.pane, 'design-tabs' )[0] || null;
const shownSelect = headed.byId('design-object-select');
headed.go('header');

check( 'another object redraws the column under the bar and leaves the bar standing - the same element, its tab lit, and the select at the same object',
	shownStrip !== null && byClass( headed.pane, 'design-tabs' )[0] === shownStrip && byClass( headed.pane, 'design-tabs' ).length === 1 && headed.byId('design-object-select') === shownSelect
	&& headed.byId('design-tab-header').getAttribute('aria-selected') === 'true' && headed.byId( 'design-tab-'+ tableObjectKeys[0] ).getAttribute('aria-selected') === 'false'
	&& shown( shownSelect ) === 'header' && headed.setup._current() === 'header' && headed.byId('design-frame') !== null );

const selecting = panel( {}, { lens : false } );
const selectBar = selecting.byId('design-object-select');
const selectStrip = byClass( selecting.pane, 'design-tabs' )[0];
const selectColumn = selecting.byId('design-controls');
focused = null;
selectBar.children.forEach( function( option ) { option.selected = option.value === 'footer' } );
selectBar.value = 'footer';
selectBar.fire('change');

check( 'the select switches the object as a tab does: the column is drawn for it, the strip lights its tab, the bar and the select stay the elements they were',
	selecting.setup._current() === 'footer' && selecting.byId('design-controls') !== selectColumn && selecting.byId('design-tab-footer').getAttribute('aria-selected') === 'true'
	&& selecting.byId( 'design-tab-'+ tableObjectKeys[0] ).getAttribute('aria-selected') === 'false' && byClass( selecting.pane, 'design-tabs' )[0] === selectStrip
	&& selecting.byId('design-object-select') === selectBar && shown( selectBar ) === 'footer'
	&& blocksOf( selecting.form ).every( function( block ) { return block.dataset.object === 'footer' } ) && blocksOf( selecting.form ).length > 0 );

/*	The keyboard is the reason the strip is not drawn again on a switch. An
	arrow key moves the focus to the next tab and opens its object; a strip drawn anew
	with the column took that tab out of the document, and the focus fell onto
	the page, so the next arrow key had nowhere to start from. The arrow keys
	walk the objects and not the way back, which is no tab	*/
const keyed = panel( {}, { lens : false } );
const keyTabs = byTag( byClass( keyed.pane, 'design-tabs' )[0] && byClass( keyed.pane, 'design-tabs' )[0].children[1].children[0] || element('div'), 'button' );
const keyColumns = [ keyed.byId('design-controls') ];

focused = null;
if( keyTabs.length === tableObjectKeys.length )
	press( keyTabs[0], 'ArrowRight' );
keyColumns.push( keyed.byId('design-controls') );

check( 'an arrow key on the strip opens the next object and the focus stays on the tab it moved to, in the strip - the panel follows: the column is drawn for that object',
	keyTabs.length === tableObjectKeys.length && focused === keyTabs[1] && keyTabs[1].parent === byClass( keyed.pane, 'design-tabs' )[0].children[1].children[0]
	&& keyTabs[1].getAttribute('aria-selected') === 'true' && keyed.setup._current() === tableObjectKeys[1] && keyColumns[1] !== keyColumns[0]
	&& blocksOf( keyed.form ).length > 0 && blocksOf( keyed.form ).every( function( block ) { return block.dataset.object === tableObjectKeys[1] } ) && shown( keyed.byId('design-object-select') ) === tableObjectKeys[1] );

if( keyTabs.length === tableObjectKeys.length )
	press( keyTabs[1], 'ArrowLeft' );

check( '...so the next one has a tab to start from, and walks back',
	keyTabs.length === tableObjectKeys.length && focused === keyTabs[0] && keyTabs[0].parent === byClass( keyed.pane, 'design-tabs' )[0].children[1].children[0]
	&& keyTabs[0].getAttribute('aria-selected') === 'true' && keyed.setup._current() === tableObjectKeys[0] );

if( keyTabs.length === tableObjectKeys.length )
	press( keyTabs[0], 'ArrowLeft' );

check( '...and from the first object the arrow key wraps round to the last - the last feature -, with its blocks in the column - and not onto Back to list, nor onto Tablet',
	keyTabs.length === tableObjectKeys.length && focused === keyTabs[tableObjectKeys.length - 1] && keyed.setup._current() === tableObjectKeys[tableObjectKeys.length - 1] && focused !== keyed.byId('design-back')
	&& blocksOf( keyed.form ).every( function( block ) { return block.dataset.object === tableObjectKeys[tableObjectKeys.length - 1] } ) );

if( keyTabs.length === tableObjectKeys.length )
	press( keyTabs[tableObjectKeys.length - 1], 'ArrowRight' );

check( '...and from the last it wraps round to the first', keyTabs.length === tableObjectKeys.length && focused === keyTabs[0] && keyed.setup._current() === tableObjectKeys[0] );

/*	The script drawn outside its pane: the bar has nowhere else to go, so it
	opens the column the way it did - and the heading stays gone there as well	*/
const bare = panel( {}, { head : false, lens : false } );
const opened = hasClass( ( bare.byId('design-controls') || element('div') ).children[0] || element('div'), 'design-tabs' );
bare.go('card');

check( 'where the pane has no head, the bar opens the column and comes back with it on a switch - with no heading over it',
	opened === true && hasClass( bare.byId('design-controls').children[0], 'design-tabs' ) && byClass( bare.pane, 'design-tabs' ).length === 1
	&& bare.setup._current() === 'card' && byTag( bare.form, 'h2' ).length === 0 );

// --- The objects come first ------------------------------------------------------------------
//
// The strip on top is the objects - the eight and each feature that brings variants -, the blocks under it are the aspects of the one that is open, and the
// table design/list hands over says which control stands where: a control stands under the aspect and the object it names, and nowhere else. Counted from that
// table, not from a list of controls here

console.log( 'The objects' );

const ob = panel( featured(), { lens : false } );
const obLayout = ob.design._data.layout;
const obFeatures = Object.keys( ob.design._data.features );
const obDrawn = {};
const obProblems = [];

obLayout.objects.forEach( function( object ) {

	ob.go( object );

	const blocks = blocksOf( ob.form );
	const wanted = aspectsOf( obLayout, object );

	// Each aspect in turn is opened and read: the controls it draws, the keys of each
	blocks.forEach( function( card ) {
		openBlock( blocksOf( ob.form ).filter( function( each ) { return each.dataset.tab === card.dataset.tab } )[0] );
		( obDrawn[object] = obDrawn[object] || {} )[card.dataset.tab] = descendants( ob.form ).filter( function( el ) { return el.dataset.control !== undefined } ).map( function( el ) { return el.dataset.control } );
	} );

	if( blocks.map( function( card ) { return card.dataset.tab } ).join() !== wanted.join() )
		obProblems.push( object+ ' draws '+ blocks.map( function( card ) { return card.dataset.tab } ).join() );

	if( obLayout.tabs.filter( function( aspect ) { return wanted.indexOf( aspect ) !== -1 } ).join() !== wanted.join() )
		obProblems.push( object+ ' is out of the order of the table' );

	if( blocksOf( ob.form ).filter( function( each ) { return each.dataset.object !== object } ).length > 0 )
		obProblems.push( object+ ' draws the block of another object' );
} );

check( 'an object draws the aspects the table gives it, in the fixed order of the table and only those it has a control on - the eight and each feature alike'+ ( obProblems.length === 0 ? '' : ' - not so for '+ obProblems.join(' | ') ),
	obProblems.length === 0 && obLayout.objects.length === OBJECTS.length + obFeatures.length && obFeatures.length > 0
	&& obLayout.objects.every( function( object ) { return aspectsOf( obLayout, object ).length > 0 } ) );
check( '...and every control stands under the aspect and the object the table gives it - read the other way round than the table is: each key of the table is drawn once, under its object and in its aspect, in the order of its place, and nothing else is drawn',
	( function() {
		const seen = [];
		let good = true;
		obLayout.objects.forEach( function( object ) {
			Object.keys( obDrawn[object] ).forEach( function( aspect ) {
				const keys = [].concat.apply( [], obDrawn[object][aspect].map( function( control ) { return control.split(' ') } ) );
				const at = Object.keys( obLayout.controls ).filter( function( key ) { return obLayout.controls[key][0] === aspect && obLayout.controls[key][1] === object } );
				good = good && JSON.stringify( keys.slice().sort() ) === JSON.stringify( at.slice().sort() )
					&& JSON.stringify( obDrawn[object][aspect] ) === JSON.stringify( tableRows( obLayout, aspect ).filter( function( row ) { return row.object === object } ).map( function( row ) { return row.controls } ).reduce( function( all, one ) { return all.concat( one ) }, [] ) )
					&& keys.every( function( key ) { return seen.indexOf( key ) === -1 } );
				keys.forEach( function( key ) { seen.push( key ) } );
			} );
		} );
		return good && JSON.stringify( seen.sort() ) === JSON.stringify( Object.keys( obLayout.controls ).sort() );
	} )() );

const obFresh = panel( featured(), { lens : false } );

check( '...the first aspect is open when an object is shown, and only that one: a visit to every object leaves each on its first, none of them has two open',
	obLayout.objects.every( function( object ) {
		obFresh.go( object );
		const blocks = blocksOf( obFresh.form );
		return blocks.filter( isOpen ).length === 1 && isOpen( blocks[0] ) === true && obFresh.setup._blockOf( object, obFresh.setup._rows( object ) ) === blocks[0].dataset.tab;
	} ) );
check( '...a feature is a tab and an option after the eight, named by the feature, and its aspects are the tabs its knobs name: Base for the choice of its variant, then the tab of each knob its variants declare',
	obFeatures.every( function( key ) {
		const tabs = Object.keys( ob.design._data.features[key].catalogue ).reduce( function( found, set ) {
			Object.keys( ob.design._data.features[key].catalogue[set].knobs ).forEach( function( knob ) { found.push( ob.design._data.features[key].catalogue[set].knobs[knob].tab ) } );
			return found;
		}, [ 'templates' ] );
		return aspectsOf( obLayout, key ).join() === obLayout.tabs.filter( function( aspect ) { return tabs.indexOf( aspect ) !== -1 } ).join();
	} ) && byTag( byClass( ob.pane, 'design-views' )[0], 'button' ).slice( OBJECTS.length, OBJECTS.length + obFeatures.length ).length === obFeatures.length );

// The strip scrolls where it is wider than the bar, and the edge that has more behind it says so
const obList = byClass( ob.pane, 'design-views' )[0];
const obWrap = obList.parent;
obList.scrollWidth = 900;
obList.clientWidth = 300;
obList.scrollLeft = 0;
obList.fire('scroll');
const obAtStart = [ hasClass( obWrap, 'is-more-start' ), hasClass( obWrap, 'is-more-end' ) ];
obList.scrollLeft = 200;
obList.fire('scroll');
const obInBetween = [ hasClass( obWrap, 'is-more-start' ), hasClass( obWrap, 'is-more-end' ) ];
obList.scrollLeft = 600;
obList.fire('scroll');
const obAtEnd = [ hasClass( obWrap, 'is-more-start' ), hasClass( obWrap, 'is-more-end' ) ];

check( 'the strip of the objects is shadowed at the edge that has more behind it: only at the right while it stands at its start, at both while it is scrolled, only at the left at its end - and the tab that is open is brought into sight',
	obAtStart.join() === 'false,true' && obInBetween.join() === 'true,true' && obAtEnd.join() === 'true,false'
	&& JSON.stringify( ob.byId( 'design-tab-'+ obLayout.objects[0] ).scrolledInto ) === JSON.stringify( { block : 'nearest', inline : 'nearest' } ) );

// A strip without a scrollbar is moved by the pointer: the wheel, across the strip, and an arrow at each edge
const obMoves = [];
obList.scrollBy = function( options ) { obMoves.push( options ) };
const obWheel = function( delta ) {
	const event = Object.assign( { deltaX : 0, deltaY : 0, deltaMode : 0, stopped : false, preventDefault : function() { event.stopped = true } }, delta );
	( obList.listeners.wheel || [] ).forEach( function( fn ) { fn( event ) } );
	return event;
};
const obAt = function( left ) { obList.scrollLeft = left; obList.scrollWidth = 900; obList.clientWidth = 300 };
const obMoved = function() { return JSON.stringify( obMoves.splice( 0 ).map( function( move ) { return [ move.left, move.top, move.behavior ] } ) ) };

obAt( 0 );
const obDown = obWheel( { deltaY : 120 } );
const obDownMoved = obMoved();
obAt( 200 );
const obUp = obWheel( { deltaY : -80 } );
const obUpMoved = obMoved();
const obSide = obWheel( { deltaX : 40, deltaY : 5 } );
const obSideMoved = obMoved();
obWheel( { deltaY : 3, deltaMode : 1 } );
obWheel( { deltaY : 1, deltaMode : 2 } );
const obUnits = obMoved();

check( 'a turn of the wheel over the strip of the objects is turned across it - the vertical wheel as well as the horizontal one, the stronger of the two - and taken, so that the panel does not scroll with it',
	obDownMoved === JSON.stringify( [ [ 120, 0, 'instant' ] ] ) && obDown.stopped === true && obUpMoved === JSON.stringify( [ [ -80, 0, 'instant' ] ] ) && obUp.stopped === true
	&& obSideMoved === JSON.stringify( [ [ 40, 0, 'instant' ] ] ) && obSide.stopped === true );
check( '...by lines of 16 pixels and by pages of the strip, 300 pixels wide here',
	obUnits === JSON.stringify( [ [ 48, 0, 'instant' ], [ 300, 0, 'instant' ] ] ) );

obAt( 0 );
const obAtLeft = obWheel( { deltaY : -100 } );
obAt( 600 );
const obAtRight = obWheel( { deltaY : 100 } );
const obNone = obWheel( { deltaY : 0 } );

check( 'at the start of the strip the wheel up is left alone, and at its end the wheel down is - it goes on to the panel - and a wheel that turns nothing is no move',
	obMoved() === '[]' && obAtLeft.stopped === false && obAtRight.stopped === false && obNone.stopped === false );

const obArrows = byClass( obWrap, 'design-views-more' );
obAt( 200 );
obArrows.forEach( function( arrow ) { arrow.fire('click') } );

check( 'the strip has an arrow at each of its edges, the start one first, and a press on it moves the strip by three quarters of its width, back and forth - a button that is no stop of the Tab key, which walks the tabs, and is hidden from assistive technology',
	obArrows.length === 2 && obArrows.every( function( arrow ) { return arrow.tagName === 'BUTTON' && arrow.type === 'button' && arrow.tabIndex === -1 && arrow.getAttribute('aria-hidden') === 'true' } )
	&& hasClass( obArrows[0], 'design-views-more--start' ) && hasClass( obArrows[1], 'design-views-more--end' ) && obArrows[0].parent === obWrap && obArrows[1].parent === obWrap
	&& obMoved() === JSON.stringify( [ [ -225, 0, 'smooth' ], [ 225, 0, 'smooth' ] ] ) );

// The stylesheet shows an arrow only where its edge has more behind it, never on a touch screen, and keeps a tab the focus walks to clear of the shadow
const obSheet = fs.readFileSync( path.join( __dirname, '../assets/admin.css' ), 'utf8' );
const obShown = /\.design-views-wrap\.is-more-start\s*>\s*\.design-views-more--start\s*,\s*[^{}]*\.design-views-wrap\.is-more-end\s*>\s*\.design-views-more--end\s*\{\s*display:\s*flex/.test( obSheet );
const obCoarse = ( obSheet.split('@media (pointer: coarse)')[1] || '' ).split('}')[0];

check( 'the stylesheet draws an arrow only at an edge that has more behind it, hides it for a coarse pointer, and keeps the scroll of the strip clear of the shadow at its edges',
	obShown && /design-views-more--start[^{}]*,[^{}]*design-views-more--end\s*\{\s*display:\s*none/.test( obCoarse ) && /\.design-views\s*\{\s*scroll-padding-inline:\s*2rem/.test( obSheet )
	&& /\.design-views-more\s*\{\s*display:\s*none/.test( obSheet ) );

obAt( 0 );

// The select and the strip are one choice, drawn twice: whichever is used, the other says the same
const obSelect = ob.byId('design-object-select');
const obLast = obLayout.objects[obLayout.objects.length - 1];
ob.go( obLayout.objects[0] );
obSelect.value = obLast;
obSelect.children.forEach( function( option ) { option.selected = option.value === obLast } );
obSelect.fire('change');

check( 'the select is the same choice as the strip: an option for each tab in the same order, and what is chosen in it is shown on the strip - the tab lit, the others not, one tab stop - and drawn in the column',
	offered( obSelect ).map( function( option ) { return option[0] } ).join() === obLayout.objects.concat( tierTabKeys ).join() && ob.setup._current() === obLast
	&& ob.byId( 'design-tab-'+ obLast ).getAttribute('aria-selected') === 'true' && ob.byId( 'design-tab-'+ obLast ).tabIndex === 0
	&& obLayout.objects.filter( function( object ) { return object !== obLast } ).every( function( object ) { return ob.byId( 'design-tab-'+ object ).getAttribute('aria-selected') === 'false' && ob.byId( 'design-tab-'+ object ).tabIndex === -1 } )
	&& blocksOf( ob.form ).every( function( card ) { return card.dataset.object === obLast } ) );

ob.go( obLayout.objects[1] );

check( '...and a tab pressed is shown in the select',
	shown( obSelect ) === obLayout.objects[1] && ob.setup._current() === obLayout.objects[1] );

// A strip that comes back - the select stood in its place and the window was widened - brings the tab that is open into sight, once, as its box goes from no width to some
const obWatch = ob.resizers[ob.resizers.length - 1];
const obOpenTab = function() { return ob.byId( 'design-tab-'+ ob.setup._current() ) };
const obStrip = byClass( ob.pane, 'design-views' )[0];

obStrip.clientWidth = 0;
obWatch.callback( [] );
delete obOpenTab().scrolledInto;
obStrip.clientWidth = 600;
obWatch.callback( [] );
const obBrought = obOpenTab().scrolledInto;
delete obOpenTab().scrolledInto;
obWatch.callback( [] );

check( 'the strip brings the open tab into sight when it comes back from no width - the select was what showed - and not at every change of its size after that: the observer watches the strip alone',
	obWatch.watched.length === 1 && obWatch.watched[0] === obStrip && JSON.stringify( obBrought ) === JSON.stringify( { block : 'nearest', inline : 'nearest' } ) && obOpenTab().scrolledInto === undefined );

// A screen drawn again lets the observer of the bar before it go: one is watching, the one of the bar that is there
const obBefore = ob.resizers.length;
ob.design._render();

check( '...and a screen that is drawn again lets go of the observer it had: the one before is disconnected, one new one watches the new strip, and no more are alive than one',
	ob.resizers.length === obBefore + 1 && obWatch.ended === 1 && obWatch.watched.length === 0 && ob.resizers[ob.resizers.length - 1].watched[0] === byClass( ob.pane, 'design-views' )[0]
	&& ob.resizers.filter( function( one ) { return one.watched.length > 0 } ).length === 1 );

// The strip and the select are the tabs of one panel: the column is the tabpanel of the object that is open
ob.go( obLayout.objects[2] );
const obPanel = ob.byId('design-controls');
const obTabsAll = obLayout.objects.map( function( object ) { return ob.byId( 'design-tab-'+ object ) } );

check( 'the column is the tabpanel of the tab that is open: role tabpanel, labelled by that tab - and drawn again for another object it is labelled by that one - and every tab says which panel it controls',
	obPanel.getAttribute('role') === 'tabpanel' && obPanel.getAttribute('aria-labelledby') === 'design-tab-'+ obLayout.objects[2] && ob.byId( obPanel.getAttribute('aria-labelledby') ).getAttribute('aria-selected') === 'true'
	&& obTabsAll.every( function( tab ) { return tab.getAttribute('aria-controls') === 'design-controls' } ) && ob.byId('design-controls') === obPanel
	&& ( ob.go( obLayout.objects[3] ), ob.byId('design-controls').getAttribute('aria-labelledby') === 'design-tab-'+ obLayout.objects[3] )
	&& ( ob.back(), ob.byId('design-controls').getAttribute('role') === null && ob.byId('design-controls').getAttribute('aria-labelledby') === null ) );

// The stylesheet shows one of the two, at the width where the rail of the workbench gives its links up for a select
const styleSheet = fs.readFileSync( path.join( __dirname, '../assets/admin.css' ), 'utf8' );
const narrowRule = styleSheet.split('@media '+ NARROW_QUERY)[1] || '';
const kernelSheet = path.join( process.env.NINO_ROOT || path.join( __dirname, '../../..' ), '_admin/assets/style.css' );

check( 'the stylesheet shows the select and hides the strip and its shadows below the width the script asks the window for, and the select is hidden above it - the same query the rail of the workbench uses for its own select',
	narrowRule.indexOf('.design-object-select') !== -1 && /\.design-views-wrap\s*,\s*[^{}]*\.design-tiers\s*\{\s*display:\s*none/.test( narrowRule ) && /\.design-object-select\s*\{\s*display:\s*(block|flex)/.test( narrowRule )
	&& /\.design-object-select\s*\{\s*display:\s*none/.test( styleSheet.split('@media '+ NARROW_QUERY)[0] )
	&& ( fs.existsSync( kernelSheet ) === false || fs.readFileSync( kernelSheet, 'utf8' ).indexOf( '@media '+ NARROW_QUERY ) !== -1 ) );

console.log('');


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
	byClass( column.form, 'design-eyebrow' ).length === 1 && byClass( previewBox, 'design-eyebrow' )[0].textContent === TEXT['/_admin/design/preview/eyebrow'] );
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

check( 'the chip says the state of the files in one word - the word of its key - while the file answers to the selection',
	column.chip().textContent === shortWord('current') && column.chip().dataset.state === 'current' && column.design._stateKey() === 'current' );
check( '...saved and not applied once the file does not, a file Design did not write named as such, and a file never applied as that',
	JSON.stringify( stateWord( { current : false } ) ) === JSON.stringify( [ shortWord('drifted'), 'drifted' ] )
	&& JSON.stringify( stateWord( { files : foreignFiles } ) ) === JSON.stringify( [ shortWord('foreign'), 'foreign' ] )
	&& JSON.stringify( stateWord( { exists : false, compiled : '', current : false } ) ) === JSON.stringify( [ shortWord('missing'), 'missing' ] ) );
check( '...and its title says what the word is about, the applied file - the label the last row of the summary had',
	column.chip().title === TEXT['/_admin/design/label/state']+ ': '+ shortWord('current') );

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
steady.design._say( steady.line(), TEXT['/_admin/design/msg/saved'] );
[ 'type', 'surfaces', 'colours', 'templates' ].forEach( function( key ) { steady.tab( tabWord( key ) ) } );
steady.tab( tabWord('type') );
pressKey( steady, 'knobs/section/title', 1 );

check( 'a tab that is opened and a knob that is moved change nothing of them: the same line with the same words, the same chip with its word',
	steady.line() === steadyLine && steady.chip() === steadyChip && steady.line().textContent === TEXT['/_admin/design/msg/saved'] && steady.line().title === TEXT['/_admin/design/msg/saved']
	&& steady.chip().textContent === shortWord('current') && steady.chip().dataset.state === 'current' );

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
const codedNotes = panel( { notes : [ NOTE_THEME, noteSet( 'header', 'v9', 'first' ), noteSet( 'section', 'v3', 'second' ), 'there is no part "nowhere" - dropped' ] } );

check( 'a note that carries a code is said in the words of the panel - the sentence of the code with its params in it, the part and the template that stands in by the names the screen has for them - and a note that is a sentence is the sentence',
	JSON.stringify( noteLines( codedNotes ) ) === JSON.stringify( [
		say( '/_admin/error/design_theme_missing', 'magazine' ),
		say( '/_admin/error/design_set_missing', TEXT['/_admin/design/part/header'], 'v9', 'Plain bar' ),
		say( '/_admin/error/design_set_missing', TEXT['/_admin/design/part/section'], 'v3', 'Box' ),
		'there is no part "nowhere" - dropped',
	] ) && noteLines( codedNotes ).every( function( line ) { return line.indexOf('%s') === -1 && line.indexOf('design_') === -1 } ) );
check( '...as many lines as the notes, each over the tab and under the strip, as the one that is a sentence was',
	byClass( codedNotes.byId('design-controls'), 'nino-admin-error' ).length === 4 && codedNotes.byId('design-controls').children[4] === codedNotes.body() );
check( '...and a note that is a sentence is said as it is',
	codedNotes.design._noteText( 'a sentence' ) === 'a sentence' );

console.log('');

// --- The Templates view: the seven choices of a set ------------------------------------------------
//
// An accordion of seven blocks, one for each object that has a template - the header, the footer, a section, a
// card, the buttons, the lists and the tables, in the order of the objects, which is the order the parts are
// compiled in. A block holds the select of its part: the one word of its label, the catalogue as its options, the
// template that is chosen and the description of that template under it. The head of a closed block names the
// template that is chosen, whichever it is - a template is a choice and no position, so it is told at the
// defaults too - and a change in the open block is in its head once the block is closed. Besides the selects only
// the knobs of the header that the table puts on the tab stand there; the rest stand on Type, Surfaces and Colours, with the objects they set

console.log( 'Templates' );

const tpl = panel();
const tplParts = listing().parts.map( function( row ) { return row.part } );
const tplRows = tableRows( tpl.design._data.layout, 'templates' );
const tplBlocks = function() { return blocksOf( tpl.form ) };
const tplSelect = function( name ) { return setSelect( tpl, name ) };
const tplSaid = function( card ) { return byClass( card, 'design-accordion-summary' )[0].textContent };
const tplWord = function( part, set ) { return listing().parts.filter( function( row ) { return row.part === part } )[0].catalogue[set].name };

// The Base block of each object, read off what is drawn: the objects walked, each opened at it
const tplDrawn = [];
eachBlock( tpl.form, function( card ) {
	tplDrawn.push( { object : card.dataset.object, tab : card.dataset.tab, open : isOpen( card ), head : headButton( card ) !== null && card.children[0] === headButton( card ), classes : hasClass( card, 'nino-admin-card' ) && hasClass( card, 'design-accordion' ) } );
} );

check( 'Base is a block under each object a part is the template of - eleven in all, in the order the table gives the objects: each stands under an object, none under no object, and there is no strip of screens and no heading - and the block is the aspect, its card says which',
	tabOf( tpl ) === 'templates' && tplParts.length === 11 && tplRows.length === 11 && tplParts.join() === 'header,footer,section,tiles,card,buttons,lists,tables,forms,tabs,accordion'
	&& tplRows.map( function( row ) { return row.object } ).join() === OBJECTS.filter( function( object ) { return tplRows.some( function( row ) { return row.object === object } ) } ).join()
	&& tplRows.every( function( row ) { return row.object !== '' && row.controls.filter( function( key ) { return key.indexOf('parts/') === 0 } ).length === 1 } )
	&& tplDrawn.map( function( card ) { return card.object } ).join() === tplRows.map( function( row ) { return row.object } ).join()
	&& tplDrawn.every( function( card ) { return card.tab === 'templates' && card.open === true && card.head && card.classes } ) );
check( '...every block drawn from the table: the rows it has are the rows the table gives, each with its one control, and the first of them is open - the object of each is the one the table puts the part under',
	JSON.stringify( drawnRows( tpl ) ) === JSON.stringify( tplRows ) && tplRows.every( function( row, at ) { return row.controls[0] === 'parts/'+ tplParts[at] } )
	&& byTag( tpl.body(), 'select' ).length === 2 && byClass( tpl.body(), 'design-section-label' ).length === 0
	&& byClass( tpl.body(), 'design-knob-row' ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/header/position,knobs/header/cta,knobs/header/behaviour' );
check( '...each over its catalogue, on the one that is chosen, with the one word of label/template over it and the description of the chosen one under it - the two frames and the three templates of the knobs alike - one select in a block, and no sentence under it',
	listing().parts.every( function( row ) {
		const select = tplSelect( row.part );
		return select !== null && JSON.stringify( offered( select ) ) === JSON.stringify( Object.keys( row.catalogue ).map( function( set ) { return [ set, row.catalogue[set].name ] } ) )
			&& shown( select ) === row.set && select.id === 'design-set-'+ row.part && byTag( select.parent, 'label' )[0].textContent === TEXT['/_admin/design/label/template']
			&& ( byTag( select.parent, 'small' )[0] || {} ).textContent === row.catalogue[row.set].description && select.parent.dataset.control === 'parts/'+ row.part
			&& cardOf( select ).dataset.object === tplRows[ tplParts.indexOf( row.part ) ].object && byTag( cardOf( select ), 'select' ).length === ( row.part === 'header' || row.part === 'footer' ? 2 : 1 )
			&& byTag( cardOf( select ), 'p' ).length === 0 && byClass( cardOf( select ), 'design-note' ).length === 0;
	} ) );
check( '...and what stands in a Base block beside the select is what the table puts on Templates under that object: the position, the button and the behaviour after the select of the header, the position after the select of the footer, and the select alone in the other nine - each strip a knob row in the block of its object, with its three words',
	JSON.stringify( tplRows.map( function( row ) { return row.controls } ) ) === JSON.stringify( [ [ 'parts/header', 'navs/header', 'knobs/header/position', 'knobs/header/cta', 'knobs/header/behaviour' ], [ 'parts/footer', 'navs/footer', 'knobs/footer/position' ], [ 'parts/section', 'knobs/section/align', 'knobs/section/measure' ], [ 'parts/tiles' ], [ 'parts/card', 'knobs/card/align' ], [ 'parts/buttons' ], [ 'parts/lists' ], [ 'parts/tables' ], [ 'parts/forms' ], [ 'parts/tabs' ], [ 'parts/accordion' ] ] )
	&& [ 'header', 'footer' ].every( function( object ) {
		return [ 'position' ].concat( object === 'header' ? [ 'cta', 'behaviour' ] : [] ).every( function( knob ) {
			const row = controlOf( tpl, 'knobs/'+ object+ '/'+ knob );
			return row !== null && hasClass( row, 'design-knob-row' ) && cardOf( row ) === cardOf( tplSelect( object ) ) && byTag( row, 'button' ).map( function( button ) { return button.textContent } ).join() === [ 'less', 'default', 'more' ].map( function( word ) { return TEXT['/_admin/design/knob/'+ object+ '/'+ knob+ '/'+ word] } ).join();
		} );
	} ) );
check( '...a select says what it is for with one word, the same over every one of them, and not with the part it is the select of: the head of its block says the object, and the object a second time would be too much',
	listing().parts.every( function( row ) { return byTag( tplSelect( row.part ).parent, 'label' )[0].textContent === TEXT['/_admin/design/label/template'] && byTag( tplSelect( row.part ).parent, 'label' )[0].textContent !== TEXT['/_admin/design/part/'+ row.part] } )
	&& TEXT['/_admin/design/label/template'] !== undefined && TEXT['/_admin/design/label/template'] !== '' );

/*	The navigation of a frame: a select of its own after the one of the template, in the block of the header and of the footer. As the project has it first, then the options
	in a group each - the kernel's menus, then what the features offer; a choice is the selection on screen, asks for the whole document of the preview and is said by the
	head of the closed block	*/
const navSelect = function( screen, part ) {
	const field = controlOf( screen, 'navs/'+ part );
	return field === null ? null : byTag( field, 'select' )[0] || null;
};
const navGroups = function( select ) {
	return select.children.map( function( child ) { return child.tagName === 'OPTGROUP' ? [ child.label, child.children.map( function( option ) { return option.value } ) ] : [ '', [ child.value ] ] } );
};

check( 'the header and the footer have a select Navigation after the one of the template: the control of the table, in the block of its object, labelled with the word of its own and not the template\'s',
	[ 'header', 'footer' ].every( function( part ) {
		const select = navSelect( tpl, part );
		return select !== null && select.id === 'design-nav-'+ part && select.parent.dataset.control === 'navs/'+ part
			&& byTag( select.parent, 'label' )[0].textContent === TEXT['/_admin/design/navigation/label'] && TEXT['/_admin/design/navigation/label'] !== TEXT['/_admin/design/label/template']
			&& cardOf( select ) === cardOf( tplSelect( part ) ) && byTag( cardOf( select ), 'select' ).slice( 0, 2 ).map( function( one ) { return one.id } ).join() === 'design-set-'+ part+ ',design-nav-'+ part;
	} ) );
check( '...its first entry is As the project has it, the key empty and chosen, and then the options of the answer for that part in two groups: the menus of the kernel under Kernel, what features offer under Features',
	[ 'header', 'footer' ].every( function( part ) {
		const select = navSelect( tpl, part );
		return shown( select ) === '' && select.children[0].tagName === 'OPTION' && select.children[0].value === '' && select.children[0].textContent === TEXT['/_admin/design/navigation/project']
			&& JSON.stringify( navGroups( select ).slice( 1 ) ) === JSON.stringify( [ [ TEXT['/_admin/design/navigation/kernel'], [ 'nav-main', 'nav-footer' ] ], [ TEXT['/_admin/design/navigation/features'], [ 'menu-main' ] ] ] )
			&& offered( select ).map( function( pair ) { return pair[1] } ).join() === [ TEXT['/_admin/design/navigation/project'] ].concat( tpl.design._data.navigations[part].map( function( option ) { return option.title } ) ).join();
	} ) );

navSelect( tpl, 'header' ).focus();
choose( navSelect( tpl, 'header' ), 'menu-main' );
tpl.design._previewNow();
const navAsked = tpl.requests[tpl.requests.length - 1];

check( '...choosing one is the selection on screen: the model holds the key for that part alone, the preview asked for rebuilds the document with the key posted beside the parts, and the bar can save it',
	tpl.design._edit.navs.header === 'menu-main' && tpl.design._edit.navs.footer === '' && navSelect( tpl, 'header' ).value === 'menu-main' && tpl.design._isDirty() === true && tpl.byId('design-reset').hidden === false
	&& navAsked.endpoint === 'preview' && navAsked.payload.full === true && JSON.stringify( navAsked.payload.navs ) === JSON.stringify( { header : 'menu-main', footer : '' } ) && tpl.design._target === 'global' );
check( '...and the footer\'s choice looks at the foot of the page',
	( choose( navSelect( tpl, 'footer' ), 'nav-footer' ), tpl.design._edit.navs.footer === 'nav-footer' && tpl.design._target === 'footer' ) );

// A setup that stored a choice is drawn with it: the select of each frame shows the stored key, and the model holds it
const navStored = panel( { navs : { header : 'nav-footer', footer : 'menu-main' } } );

check( 'a stored choice is what the selects show after the screen is read: the key of each frame is the selected option and the model has both',
	shown( navSelect( navStored, 'header' ) ) === 'nav-footer' && shown( navSelect( navStored, 'footer' ) ) === 'menu-main'
	&& JSON.stringify( navStored.design._edit.navs ) === JSON.stringify( { header : 'nav-footer', footer : 'menu-main' } ) && navStored.design._isDirty() === false );
check( 'the note of an option that is gone says the frame by the word of its object and the key, in the words of the code',
	navStored.design._noteText( { error : 'x', code : 'design_navigation_missing', params : [ 'footer', 'menu-gone' ] } )
		=== TEXT['/_admin/error/design_navigation_missing'].replace( '%s', TEXT['/_admin/design/object/footer/label'] ).replace( '%s', 'menu-gone' ) );

// The document of the preview is rebuilt when a frame changes, and the navigation of a frame is part of it: a choice asks for the whole document, the same choice again for none
tpl.design._frames = [ tpl.design._edit.parts.header, tpl.design._edit.parts.footer, 'menu-main', 'nav-footer' ].join('|');
tpl.design._previewNow();
const navSame = tpl.requests[tpl.requests.length - 1];
choose( navSelect( tpl, 'header' ), 'nav-legal' );
tpl.design._previewNow();
const navChanged = tpl.requests[tpl.requests.length - 1];
choose( navSelect( tpl, 'header' ), 'menu-main' );

check( '...and the preview asks for the whole document only when the navigation differs from the one the document in the frame was built with',
	navSame.payload.full === false && navChanged.payload.full === true && navChanged.payload.navs.header === 'nav-legal' );

const navHead = cardOf( navSelect( tpl, 'header' ) );
toggle( navHead );

check( '...the head of the closed block names it after the template, by the title of the option, and a part left as the project has it says nothing of it',
	tplSaid( navHead ) === tplWord( 'header', tpl.design._edit.parts.header ) + ' · ' + TEXT['/_admin/design/navigation/label'] + ' Main menu'
	&& ( toggle( navHead ), choose( navSelect( tpl, 'header' ), '' ), toggle( navHead ), tplSaid( navHead ) === tplWord( 'header', tpl.design._edit.parts.header ) ) );

const navSaved = panel( {}, { lens : false, object : 'header' } );
navSaved.answer = navSaved.answer || null;
choose( navSelect( navSaved, 'header' ), 'nav-main' );
const navSnapshot = navSaved.design._snapshotOf( navSaved.design._edit );

check( '...a theme keeps it among the choices it compares the screen with: the snapshot carries navs, and a navigation that differs from the theme is a change',
	JSON.stringify( navSnapshot.navs ) === JSON.stringify( { header : 'nav-main', footer : '' } ) && Object.keys( navSnapshot ).indexOf( 'navs' ) === Object.keys( navSnapshot ).indexOf( 'parts' ) + 1 );

// The Base block is the open one at the start and its head says nothing; closed, it names the set that is chosen - the fresh install's among them
const tplFresh = panel();
const tplFreshHeads = [];

eachBlock( tplFresh.form, function( card ) {
	const was = byClass( card, 'design-accordion-summary' )[0].textContent;
	toggle( card );
	tplFreshHeads.push( { object : card.dataset.object, open : was === '' && headButton( card ).getAttribute('aria-expanded') === 'false', said : byClass( card, 'design-accordion-summary' )[0].textContent } );
} );

check( 'the head of a closed Base block names the template that is chosen - the word of the set as the catalogue of the answer has it - also while the selection stands at the defaults, where no other aspect says anything; the head of the open block says nothing, as everywhere: closing it puts the set of its part in its head, for all eleven',
	tplFreshHeads.length === 11 && tplFreshHeads.map( function( head ) { return head.said } ).join() === tplParts.map( function( part ) { return tplWord( part, 'first' ) } ).join() && tplFreshHeads.every( function( head ) { return head.open } )
	&& tplFresh.design._isDirty() === false );
check( '...an object opens on its first aspect: one block open, the first, and the blocks are the aspects the table gives the object, in its order',
	( function() {
		const fresh = panel( {}, { lens : false, object : 'header' } );
		const blocks = blocksOf( fresh.form );
		return blocks.map( function( card ) { return card.dataset.tab } ).join() === aspectsOf( fresh.design._data.layout, 'header' ).join() && blocks.filter( isOpen ).length === 1 && isOpen( blocks[0] ) === true;
	} )() );

let tplHeaderField = tplSelect('header').parent;
let tplHeaderBlock = cardOf( tplSelect('header') );
const tplHeaderOldSelect = tplSelect('header');
tplHeaderOldSelect.focus();
const focusedBefore = focused;
choose( tplSelect('header'), 'second' );

check( 'choosing another one is the selection on screen, and the note under it follows the choice',
	tpl.design._edit.parts.header === 'second' && byTag( tplSelect('header').parent, 'small' )[0].textContent === listing().parts[0].catalogue.second.description
	&& tpl.byId('design-reset').hidden === false );
check( '...a template draws the block of its object anew, on the tab that is open: the select in it is a new one on the template that was chosen, the block stands where it stood in the order, and it is open as it was',
	tplSelect('header').parent !== tplHeaderField && descendants( tpl.pane ).indexOf( tplSelect('header') ) !== -1 && shown( tplSelect('header') ) === 'second'
	&& cardOf( tplSelect('header') ) !== tplHeaderBlock && tplBlocks().indexOf( cardOf( tplSelect('header') ) ) === 0 && isOpen( cardOf( tplSelect('header') ) ) === true && tplBlocks().length === aspectsOf( tpl.design._data.layout, 'header' ).length );

tplHeaderField = tplSelect('header').parent;
tplHeaderBlock = cardOf( tplSelect('header') );
check( '...and the focus stays on the select: the select the person pressed is replaced with the card, and the new one, found by its id, is the element that has the focus',
	focusedBefore === tplHeaderOldSelect && focused !== null && focused === tplSelect('header') && focused.id === 'design-set-header' );

// A change of the template draws the whole column of the controls anew, the way a switch of the view does, and not the card of the part alone (decision 12)
const swp = panel();
const swpColumn = swp.byId('design-controls');
const swpOpenBefore = JSON.stringify( swp.setup._open );
const swpOffIds = function() { return byClass( swp.form, 'design-knob-row--off' ).map( function( row ) { return row.dataset.control } ).join() };

setSelect( swp, 'header' ).focus();
choose( setSelect( swp, 'header' ), 'third' );

const swpAfterThird = swp.byId('design-controls');

check( 'a template that is chosen draws the whole column of the controls anew, as a switch of the view does: the old column is out of the document and a new one stands in its place in the layout, with the one box of the view in it',
	swpColumn !== swpAfterThird && swpColumn.parent === null && descendants( swp.pane ).indexOf( swpColumn ) === -1
	&& swpAfterThird.parent === swp.byId('design-layout') && swp.byId('design-layout').children[0] === swpAfterThird && swp.byId('design-layout').children.length === 2
	&& swpAfterThird.children.indexOf( swp.body() ) !== -1 && swp.body().children.length === aspectsOf( swp.design._data.layout, 'header' ).length );
check( '...the block that was open is open still - the same object, and the view\'s own memory of it untouched - and the only one open',
	blocksOf( swp.form ).filter( isOpen ).length === 1 && blocksOf( swp.form ).filter( isOpen )[0].dataset.object === 'header' && JSON.stringify( swp.setup._open ) === swpOpenBefore );
check( '...the focus is on the select of the part, the new one: the one that had it is part of the column that went',
	focused !== null && focused === swp.byId('design-set-header') && descendants( swpColumn ).indexOf( focused ) === -1 && shown( focused ) === 'third' );
check( '...and the strips of the part show what the template that is chosen answers to: the two knobs the Slim header does not declare are greyed out',
	swpOffIds() === 'knobs/header/position,knobs/header/cta' );

choose( setSelect( swp, 'header' ), 'second' );

check( 'a knob that only the template chosen next answers to is no longer greyed out: the strips of the position and of the button are on again, in a column that is a new one, with the same block open and the focus on the new select',
	swpOffIds() === '' && swp.byId('design-controls') !== swpAfterThird && descendants( swp.pane ).indexOf( swpAfterThird ) === -1
	&& blocksOf( swp.form ).filter( isOpen )[0].dataset.object === 'header' && focused === swp.byId('design-set-header') && shown( focused ) === 'second' );

const swpOther = panel();
setSelect( swpOther, 'card' );
const swpFocusFree = swpOther.byId('design-controls');
focused = null;
choose( setSelect( swpOther, 'card' ), 'second' );

check( '...a select that did not have the focus is not given it by the redraw: the column is new all the same',
	swpOther.byId('design-controls') !== swpFocusFree && focused !== swpOther.byId('design-set-card') );

check( '...and the head of the block says nothing while the block is open, as it did',
	tplSaid( tplHeaderBlock ) === '' );

toggle( tplHeaderBlock );

check( '...closing the block shows the new template in its head, without the block being drawn again: the head names the set that was chosen in it, the other heads are as they were',
	tplSaid( tplHeaderBlock ) === tplWord( 'header', 'second' ) && tplBlocks()[0] === tplHeaderBlock && headButton( tplHeaderBlock ).getAttribute('aria-expanded') === 'false' );

// A change in a block that was opened for it: the same with the cards, whose knobs a new template may not answer to
const tplCard = tplSelect('card');
choose( tplCard, 'third' );
const tplCardBlock = cardOf( tplSelect('card') );

check( '...and the same where the block is another one: the choice is in its head once it is closed, and the head of the one closed before is not touched',
	tpl.design._edit.parts.card === 'third' && isOpen( tplCardBlock ) === true && isOpen( tplHeaderBlock ) === false
	&& tplSaid( tplCardBlock ) === '' && ( toggle( tplCardBlock ), tplSaid( tplCardBlock ) === tplWord( 'card', 'third' ) )
	&& tplSaid( tplHeaderBlock ) === tplWord( 'header', 'second' ) );
check( '...a choice taken back is taken back in the head: the set of the first choice is named again, the same word as before',
	( openBlock( tplBlocks().filter( function( card ) { return card.dataset.object === 'card' } )[0] ), choose( tplSelect('card'), 'first' ), toggle( tplBlocks().filter( function( card ) { return card.dataset.object === 'card' } )[0] ),
		tplSaid( tplBlocks().filter( function( card ) { return card.dataset.object === 'card' } )[0] ) === tplWord( 'card', 'first' ) ) );

console.log('');

// --- The styles of a template ----------------------------------------------------------------
//
// A template brings css files beside it that a person chooses one of, a select for each style its head declares. The select stands in the block of its
// part under the select of the template and its description, before the strips; it has None first and the options by the names their files give them, and
// the description of the one chosen under it. A choice is a key of the working copy, goes with every request the selection goes with, and is the
// template's: another template starts on None. The head of the closed block names the options chosen

console.log( 'The styles of a template' );

const styCatalogue = function() { return listing().parts.filter( function( row ) { return row.part === 'lists' } )[0].catalogue };
const styDefs = styCatalogue().second.styles;
const styKeys = Object.keys( styDefs );
const sty = panel();
const styField = function( style ) { return sty.byId( 'design-style-lists-'+ style ) };
const styBlock = function() { return cardOf( setSelect( sty, 'lists' ) ) };
// The block as it stands, opened or not: setSelect() opens it
const styCard = function() { return blocksOf( sty.form ).filter( function( card ) { return card.dataset.object === 'lists' } )[0] };
const styBody = function() { return byClass( styBlock(), 'design-accordion-body' )[0] };
const stySelects = function() { return byClass( styBody(), 'design-style-select' ) };
const styNone = TEXT['/_admin/design/style/none'];
const styLast = function() { return sty.requests[sty.requests.length - 1] };

check( 'a template that brings no style has no select for one: none in the block of its part, with the select of the template alone in it',
	stySelects().length === 0 && byTag( styBody(), 'select' ).length === 1 && styKeys.length === 2 && styCatalogue().first.styles === undefined && sty.design._edit.styles.lists !== undefined );

choose( setSelect( sty, 'lists' ), 'second' );

check( 'a template that brings styles has a select for each, in the order its head declares them, under the select of the template and before the strips of the part: no control of the table, so none of them stands for a key',
	stySelects().length === 2 && styKeys.every( function( style, at ) { return stySelects()[at] === styField( style ).parent } )
	&& styBody().children.indexOf( stySelects()[0] ) === styBody().children.indexOf( setSelect( sty, 'lists' ).parent ) + 1 && styBody().children.indexOf( stySelects()[1] ) === styBody().children.indexOf( stySelects()[0] ) + 1
	&& stySelects().every( function( field ) { return field.dataset.control === undefined && hasClass( field, 'design-field' ) } ) && tableRows( sty.design._data.layout, 'templates' ).every( function( row ) { return row.controls.every( function( key ) { return key.indexOf('styles') === -1 } ) } ) );
check( '...each headed with the label its head gives the style, over a select that has None first - the word the texts have for it - and the options after it by the names their files give them, in the order of the answer: it stands on None, with no line under it',
	styKeys.every( function( style ) {
		const select = styField( style );
		return select !== null && JSON.stringify( offered( select ) ) === JSON.stringify( [ [ '', styNone ] ].concat( Object.keys( styDefs[style].options ).map( function( option ) { return [ option, styDefs[style].options[option].name ] } ) ) )
			&& shown( select ) === '' && byTag( select.parent, 'label' )[0].textContent === styDefs[style].label && byTag( select.parent, 'label' )[0].getAttribute('for') === select.id
			&& byTag( select.parent, 'small' ).length === 0 && select.parent.dataset.style === style;
	} ) && styNone !== undefined && styNone !== '' && styKeys.every( function( style ) { return styField( style ).id === 'design-style-lists-'+ style } ) );

sty.design._target = null;
choose( styField('rule'), 'dotted' );

check( 'choosing an option writes it into the working copy under the part and the style, and the line under the select says what the option is: the description its file gives it',
	JSON.stringify( sty.design._edit.styles.lists ) === JSON.stringify( { rule : 'dotted' } ) && styField('rule').value === 'dotted'
	&& ( byTag( styField('rule').parent, 'small' )[0] || { textContent : null } ).textContent === styDefs.rule.options.dotted.description && byTag( styField('tone').parent, 'small' ).length === 0
	&& sty.byId('design-reset').hidden === false && sty.design._isDirty() === true );

sty.design._previewNow();

check( '...it asks for the preview with the selection - the styles in it, as the working copy has them - and the frame goes to where the template is looked at: the object of the part',
	styLast().endpoint === 'preview' && JSON.stringify( styLast().payload.styles.lists ) === JSON.stringify( { rule : 'dotted' } ) && styLast().payload.parts.lists === 'second'
	&& JSON.stringify( Object.keys( styLast().payload.styles ) ) === JSON.stringify( Object.keys( listing().styles ) ) && sty.design._target === sty.design.OBJECT_ANCHORS.lists );

choose( styField('tone'), 'quiet' );

check( '...a second style is a key of its own beside the first, and an option with no description has no line under its select',
	JSON.stringify( sty.design._edit.styles.lists ) === JSON.stringify( { rule : 'dotted', tone : 'quiet' } ) && ( byTag( styField('tone').parent, 'small' )[0] || { textContent : '' } ).textContent === '' );

choose( styField('tone'), 'strong' );
choose( styField('rule'), '' );

check( '...None is the template as it is: the style has no entry then, the other stays, and the line under the select is gone',
	JSON.stringify( sty.design._edit.styles.lists ) === JSON.stringify( { tone : 'strong' } ) && ( byTag( styField('rule').parent, 'small' )[0] || { textContent : '' } ).textContent === ''
	&& ( byTag( styField('tone').parent, 'small' )[0] || { textContent : null } ).textContent === styDefs.tone.options.strong.description );

choose( styField('rule'), 'dashed' );
toggle( styCard() );

check( 'the head of the block, once it is closed, names the template and after it the option of each style chosen, as the select shows it - and just the template where none is chosen',
	isOpen( styCard() ) === false && byClass( styCard(), 'design-accordion-summary' )[0].textContent === [ styCatalogue().second.name, styDefs.rule.options.dashed.name, styDefs.tone.options.strong.name ].join(' · ')
	&& ( openBlock( styCard() ), choose( styField('tone'), '' ), choose( styField('rule'), '' ), toggle( styCard() ), isOpen( styCard() ) === false && byClass( styCard(), 'design-accordion-summary' )[0].textContent === styCatalogue().second.name ) );

openBlock( styCard() );
choose( styField('rule'), 'dashed' );
choose( styField('tone'), 'strong' );
choose( setSelect( sty, 'lists' ), 'first' );

check( 'another template starts on None: the styles of the part are the old template\'s and are gone from the working copy, and the select of a style the new template does not bring is gone with them',
	JSON.stringify( sty.design._edit.styles.lists ) === '{}' && stySelects().length === 0 && sty.design._edit.parts.lists === 'first' );

sty.design._previewNow();

check( '...and the preview is asked for with the part\'s styles empty, in the selection like every part\'s',
	styLast().endpoint === 'preview' && JSON.stringify( styLast().payload.styles.lists ) === '{}' && styLast().payload.parts.lists === 'first' );

choose( setSelect( sty, 'lists' ), 'second' );

check( '...and the template chosen again brings its selects, on None as before: nothing of the choice made earlier comes back',
	stySelects().length === 2 && styKeys.every( function( style ) { return shown( styField(style) ) === '' } ) && JSON.stringify( sty.design._edit.styles.lists ) === '{}' );

// Saved, put back, compared with a theme
choose( styField('rule'), 'dotted' );
sty.byId('design-save').fire('click');

check( 'the save posts the styles with the rest of the selection',
	styLast().endpoint === 'save' && JSON.stringify( styLast().payload.styles.lists ) === JSON.stringify( { rule : 'dotted' } ) && Object.keys( styLast().payload.styles ).join() === Object.keys( listing().styles ).join() );

const styBack = panel();
choose( setSelect( styBack, 'lists' ), 'second' );
choose( styBack.byId('design-style-lists-rule'), 'dotted' );
styBack.byId('design-reset').fire('click');

check( 'the way back puts the stored selection back, the styles with it: the working copy is the stored one and the select is on None',
	JSON.stringify( styBack.design._edit.styles.lists ) === '{}' && styBack.design._isDirty() === false && styBack.byId('design-reset').hidden === true && styBack.design._edit.parts.lists === 'first' );

// A stored choice is on the screen: the answer says the template and the style chosen for it
const styStored = panel( { parts : listing().parts.map( function( row ) { return row.part === 'lists' ? Object.assign( {}, row, { set : 'second' } ) : row } ), styles : Object.assign( listing().styles, { lists : { rule : 'dashed', tone : 'strong' } } ) } );
const styStoredBlock = cardOf( setSelect( styStored, 'lists' ) );

check( 'a stored choice is drawn as the answer says it: the selects stand on the options, the lines under them are the descriptions, and the screen is the stored selection - nothing to go back to',
	shown( styStored.byId('design-style-lists-rule') ) === 'dashed' && shown( styStored.byId('design-style-lists-tone') ) === 'strong'
	&& byTag( styStored.byId('design-style-lists-rule').parent, 'small' )[0].textContent === styDefs.rule.options.dashed.description && styStored.design._isDirty() === false && styStored.byId('design-reset').hidden === true
	&& ( toggle( styStoredBlock ), byClass( styStoredBlock, 'design-accordion-summary' )[0].textContent === [ styCatalogue().second.name, styDefs.rule.options.dashed.name, styDefs.tone.options.strong.name ].join(' · ') ) );
check( '...and a choice the answer holds for a style the template does not bring is not said in the head, and no select is drawn for it',
	( function() {
		const stray = panel( { styles : Object.assign( listing().styles, { header : { nowhere : 'x' } } ) } );
		const head = cardOf( setSelect( stray, 'header' ) );
		return stray.byId('design-style-header-nowhere') === null && byClass( head, 'design-style-select' ).length === 0 && ( toggle( head ), byClass( head, 'design-accordion-summary' )[0].textContent === listing().parts[0].catalogue.first.name );
	} )() );

// The answer of the server with a template that brings styles
const stylesFile = process.env.DESIGN_STYLES || '';

if( stylesFile === '' || fs.existsSync( stylesFile ) === false )
	console.log( '  --  no answer of design/list with a template that brings styles was handed to this run (DESIGN_STYLES), so the selects of the styles are not drawn from the real one' );
else {

	const realSt = JSON.parse( fs.readFileSync( stylesFile, 'utf8' ) );
	const realRow = realSt.parts.filter( function( row ) { return Object.keys( row.catalogue ).some( function( set ) { return Object.keys( realSt.parts[0].catalogue ).length > 0 && Object.keys( row.catalogue[set].styles ).length > 0 } ) } )[0];
	const realSet = realRow.set;
	const realStyles = realRow.catalogue[realSet].styles;
	const realKeys = Object.keys( realStyles );
	const realScreen = panel( realSt );
	const realField = function( style ) { return realScreen.byId( 'design-style-'+ realRow.part+ '-'+ style ) };

	// A select for each style the real answer brings, drawn on the options it chose
	setSelect( realScreen, realRow.part );

	check( 'the server\'s answer is in the shape the screen draws: every template brings its styles as a list or an object of styles with a label and the options with a name and a description, and a template that brings none an empty list - and no file of the server in them',
		realSt.parts.every( function( row ) {
			return Object.keys( row.catalogue ).every( function( set ) {
				const styles = row.catalogue[set].styles;
				return styles !== undefined && ( Array.isArray( styles ) ? styles.length === 0 : Object.keys( styles ).every( function( style ) {
					return /^[a-z0-9]+(-[a-z0-9]+)*$/.test( style ) && typeof styles[style].label === 'string' && styles[style].label !== '' && Object.keys( styles[style] ).join() === 'label,options'
						&& ( Array.isArray( styles[style].options ) ? true : Object.keys( styles[style].options ).every( function( option ) { return Object.keys( styles[style].options[option] ).join() === 'name,description' && typeof styles[style].options[option].name === 'string' } ) );
				} ) );
			} );
		} ) && realKeys.length >= 2 && JSON.stringify( realSt.parts ).indexOf('"file"') === -1 && Object.keys( realSt.styles ).join() === Object.keys( listing().styles ).join() );
	check( '...the screen draws a select for each style of the template chosen - None first, then the options in the order of the answer - on the option the setup chose, with the line of its description under it',
		realKeys.every( function( style ) {
			const chosen = ( Array.isArray( realSt.styles[realRow.part] ) ? {} : realSt.styles[realRow.part] )[style] || '';
			return realField( style ) !== null && JSON.stringify( offered( realField( style ) ).map( function( pair ) { return pair[0] } ) ) === JSON.stringify( [ '' ].concat( Object.keys( realStyles[style].options ) ) )
				&& offered( realField( style ) )[0][1] === styNone && shown( realField( style ) ) === chosen && byTag( realField( style ).parent, 'label' )[0].textContent === realStyles[style].label
				&& ( chosen === '' ? byTag( realField( style ).parent, 'small' ).length === 0 : byTag( realField( style ).parent, 'small' )[0].textContent === realStyles[style].options[chosen].description );
		} ) );

	// Every option of every style is chosen once, and the preview is asked for with it
	const realProblems = [];

	realKeys.forEach( function( style ) {
		Object.keys( realStyles[style].options ).forEach( function( option ) {
			choose( realField( style ), option );
			realScreen.design._previewNow();
			const asked = realScreen.requests[realScreen.requests.length - 1];

			if( asked.payload.styles[realRow.part][style] !== option || realScreen.design._edit.styles[realRow.part][style] !== option )
				realProblems.push( style+ '/'+ option );
		} );
	} );

	check( 'each option of each style the server brings can be chosen, and goes with the preview as it is named'+ ( realProblems.length === 0 ? '' : ' - not so for '+ realProblems.join() ), realProblems.length === 0 && realKeys.length >= 2 );
}

console.log('');



// --- The rows: what the table gives a tab ----------------------------------------------------------
//
// Where a control stands is the table design/list hands over, and the screen is drawn from it and from
// nothing else. A tab is a card for each object that has a control on it, in the order the table names
// the objects, and in a card the controls of that object in the order of their places; a card says which
// tab and which object it is the row of, and a control which keys of the setup it stands for. What is
// expected here is counted from the table of the answer (tableRows()), not from any name

console.log( 'The rows' );

const rw = panel();
const rwLayout = rw.design._data.layout;
const rwTabs = [ 'templates', 'type', 'surfaces', 'colours', 'motion' ];
const rwProblems = [];
const rwKeys = [];

rwTabs.forEach( function( tab ) {

	rw.show( tab );

	const wanted = tableRows( rwLayout, tab );
	const drawn = drawnRows( rw );

	if( wanted.length === 0 || JSON.stringify( drawn ) !== JSON.stringify( wanted ) )
		rwProblems.push( tab+ ': '+ drawn.map( function( row ) { return row.object+ '/'+ row.controls.length } ).join() );

	drawn.forEach( function( row ) {
		row.controls.forEach( function( control ) {
			control.split(' ').forEach( function( key ) {
				rwKeys.push( key );
				if( rwLayout.controls[key][0] !== tab || rwLayout.controls[key][1] !== row.object )
					rwProblems.push( key+ ' stands on '+ tab+ '/'+ row.object );
			} );
		} );
	} );
} );

check( 'a tab draws exactly the rows the table gives it, in the table\'s order, and a row exactly its controls, in the order of their places: the templates a row for each part under the object it is the template of, Type and Surfaces a row for each object that has a control on them, Colours and Motion the page\'s own'
	+ ( rwProblems.length === 0 ? '' : ' - not so for '+ rwProblems.join(' | ') ), rwProblems.length === 0 );
check( '...and every control of the table is drawn once, on the tab and under the object the table names for it: the keys of all the cards together are the keys of the table, each of them once',
	JSON.stringify( rwKeys.slice().sort() ) === JSON.stringify( Object.keys( rwLayout.controls ).sort() ) && new Set( rwKeys ).size === rwKeys.length );
// What an object draws, read off the screen: the blocks of an accordion, each an aspect the table gives it, and a css that is the page's own
const rwBlocksOf = function( screen, object ) {
	screen.go( object );
	return blocksOf( screen.form ).map( function( card ) { return card.dataset.tab } );
};

check( '...every object is an accordion of its aspects, the page\'s own and a feature\'s alike: the blocks are the aspects the table gives it, in the order of the table, each a card with a head, a button, as its first child, named by the word of its aspect - the css the page\'s alone, a block of its own after the others, which the table draws no controls for'
	+ ' - and what the table draws no objects for - the themes, which are their screens, and the loader of the typefaces - has no block at all',
	rwLayout.objects.every( function( object ) {
		const wanted = aspectsOf( rwLayout, object );
		return rwBlocksOf( rw, object ).join() === wanted.join() && blocksOf( rw.form ).every( function( card ) {
			const head = headButton( card );
			return card.children[0] === head && head.tagName === 'BUTTON' && byClass( head, 'design-accordion-name' )[0].textContent === tabWord( card.dataset.tab ) && card.dataset.object === object;
		} ) && byClass( rw.form, 'design-form-section' ).length === wanted.length;
	} )
	&& rwLayout.objects.filter( function( object ) { return rwBlocksOf( rw, object ).indexOf('css') !== -1 } ).join() === 'page' && TABS.filter( function( tab ) { return rwTabs.indexOf( tab ) === -1 } ).join() === 'css' && rwTabs.join() === BLOCK_VIEWS.join()
	&& ( rw.show('theme'), blocksOf( rw.form ).length === 0 && byClass( rw.form, 'design-accordion-head' ).length === 0 && byClass( rw.form, 'design-form-section' ).length === 0 )
	&& ( rw.show('typefaces'), blocksOf( rw.forms['design-typefaces'] ).length === 0 && byClass( rw.forms['design-typefaces'], 'design-accordion-head' ).length === 0 && byClass( rw.forms['design-typefaces'], 'design-form-section' ).length === 0 ) );

/*	The aspects are in the order of the table and an aspect has a block under an object only where the object has a control on it. Counted from the table, the
	objects are the keys of the table and no word of the panel: each aspect is under the objects the table puts a control under, in the order of the objects	*/
check( 'every aspect stands under the objects the table gives it, in the order of the objects, and under no other: the walk through the objects finds the blocks of an aspect exactly where tableObjects() counts them from the table - and the objects keep the order of the table',
	rwTabs.every( function( tab ) {
		const found = [];
		rw.show( tab );
		eachBlock( rw.form, function( card ) { found.push( card.dataset.object ) } );
		return found.join() === tableObjects( rwLayout, tab ).join() && found.length > 0 && found.map( function( object ) { return rwLayout.objects.indexOf( object ) } ).every( function( at, i, all ) { return i === 0 || at > all[i - 1] } );
	} ) && rwLayout.objects.slice( 0, OBJECTS.length ).join() === OBJECTS.join() );

/*	The screen has no other source: a table with the aspects in another order, a control moved to another aspect, another object and another place, and an
	object left with no control on an aspect is drawn as that table says - the blocks, their controls - and a block with none is not drawn	*/
const rwOther = layoutTable();
rwOther.tabs = [ 'templates', 'type', 'surfaces', 'motion', 'colours' ];
rwOther.controls['size'] = [ 'surfaces', 'card', 5 ];
delete rwOther.controls['knobs/tables/scale'];

const rwShifted = panel( { layout : rwOther }, { lens : false } );
const rwShiftedBlocks = {};

rwOther.objects.forEach( function( object ) { rwShiftedBlocks[object] = rwBlocksOf( rwShifted, object ) } );
rwShifted.show( 'type' );
const rwShiftedType = drawnRows( rwShifted );
rwShifted.show( 'surfaces' );
const rwShiftedSurfaces = drawnRows( rwShifted );

check( 'the screen follows the table it is handed and nothing else: the aspects in the order it gives - Motion before Colour, and no CSS, the table does not name it -, a control that was moved drawn where it was moved to - the root size last in the Surface block of the card - and not where it stood, and an object left with no control on an aspect has no block there',
	rwShifted.design._tabKeys().join() === rwOther.tabs.join() && rwOther.objects.every( function( object ) { return rwShiftedBlocks[object].join() === aspectsOf( rwOther, object ).join() } )
	&& rwShiftedBlocks.page.indexOf('motion') < rwShiftedBlocks.page.indexOf('colours') && rwShiftedBlocks.page.indexOf('css') === -1 && rwShiftedBlocks.tables.indexOf('type') === -1
	&& JSON.stringify( rwShiftedType ) === JSON.stringify( tableRows( rwOther, 'type' ) ) && JSON.stringify( rwShiftedSurfaces ) === JSON.stringify( tableRows( rwOther, 'surfaces' ) )
	&& rwShiftedType.map( function( row ) { return row.object } ).join() === 'page,header,footer,section,tiles,card,buttons,lists,forms,tabs,accordion' && rwShiftedType[0].controls.indexOf('size') === -1
	&& rwShiftedSurfaces.filter( function( row ) { return row.object === 'card' } )[0].controls.slice( -1 )[0] === 'size'
	&& cardOf( controlOf( rwShifted, 'size' ) ).dataset.object === 'card' && cardOf( controlOf( rwShifted, 'size' ) ).dataset.tab === 'surfaces' );

console.log('');


// --- The accordion: the rows of every tab the table draws, as blocks ---------------------------------------
//
// Every object the table draws is an accordion of its aspects, counted from the table. The head of a block is a button with the word of its
// aspect, a chevron and, while the block is closed, the controls of its row that stand elsewhere than at the defaults, each as its label and the
// word of its position - and in Base, which is a choice and no position, the template that is chosen; the controls themselves are drawn while the
// block is open and not before. One block is open - the one the person left open under the object, the first to begin with - and opening one
// closes it; each object keeps its own. What is expected is counted from the table and read off the buttons that are drawn and the texts of
// their keys: no word of the panel is typed here

console.log( 'The accordion' );

const acTabs = TABS.filter( function( tab ) { return tableObjects( listing().layout, tab ).length > 0 } );
const ac = panel();
const acScreen = function( tab ) { ac.show( tab ) };
const acObjects = ac.design._data.layout.objects;
const acHeads = function() { return byClass( ac.form, 'design-accordion-head' ) };
const acOpen = function() { return acHeads().filter( function( head ) { return head.getAttribute('aria-expanded') === 'true' } ) };
// The block of the aspect the screen is looked at through, under an object: the object is drawn for it
const acCard = function( object ) { goTo( ac, object ); return blocksOf( ac.form ).filter( function( card ) { return card.dataset.tab === ac.lens } )[0] };
const acLine = function( object ) { return byClass( acCard( object ), 'design-accordion-summary' )[0].textContent };
// What its head says once it is closed, the block closed first where it is open
const acClosedLine = function( object ) { if( isOpen( acCard( object ) ) === true ) toggle( acCard( object ) ); return acLine( object ) };
const acBodies = function() { return byClass( ac.form, 'design-accordion-body' ) };
// Each object drawn in turn, quietly, and the screen put back where it was
const acEach = function( visit ) { const was = ac.setup._current(); acObjects.forEach( function( object ) { goTo( ac, object, true ); visit( object ) } ); goTo( ac, was, true ) };
const acSeparator = ' · ';

/** The word an option of a select has: the label of the option that holds the value */
const acOption = function( select, value ) { return offered( select ).filter( function( option ) { return option[0] === value } )[0][1] };

/** What a head says of a control: its label and the word of the position it stands at - the word read off the button of the strip that holds the value */
const acSays = function( label, row, value ) { return label+ ' '+ byTag( row, 'button' ).filter( function( button ) { return button.dataset.value === String( value ) } )[0].textContent };

check( 'every object the table draws - the eight and a feature\'s alike - draws an accordion: a head for each aspect it has a control on, in the order of the table, each a button of its own',
	acTabs.join() === 'templates,type,surfaces,colours,motion,css' && ( function() {
		let good = true;
		acEach( function( object ) {
			const aspects = aspectsOf( ac.design._data.layout, object );
			good = good && acHeads().length === aspects.length && blocksOf( ac.form ).map( function( card ) { return card.dataset.tab } ).join() === aspects.join() && aspects.length > 0
				&& acHeads().every( function( head ) { return head.tagName === 'BUTTON' && head.type === 'button' && cardOf( head ).children[0] === head } );
		} );
		return good;
	} )() );
check( '...exactly one block is open, the first aspect of the object, and its body is drawn with exactly the controls the table gives the row - the others are closed and draw no body and no control at all',
	( function() {
		let good = true;
		acEach( function( object ) {
			const rows = tableRows( ac.design._data.layout, aspectsOf( ac.design._data.layout, object )[0] ).filter( function( row ) { return row.object === object } );
			good = good && acOpen().length === 1 && blocksOf( ac.form ).every( function( card, at ) {
				const body = byClass( card, 'design-accordion-body' )[0];
				const controls = descendants( card ).filter( function( el ) { return el.dataset.control !== undefined } ).map( function( el ) { return el.dataset.control } );
				return at === 0
					? isOpen( card ) === true && body !== undefined && JSON.stringify( controls ) === JSON.stringify( rows.length === 0 ? [] : rows[0].controls )
					: isOpen( card ) === false && body === undefined && controls.length === 0;
			} );
		} );
		return good;
	} )() );
check( '...a head says in aria-controls which body it is the head of while its block is open - the id of the body that is drawn - and says nothing of a body while it is closed, where there is none in the document',
	( function() {
		let good = true;
		acEach( function() {
			good = good && blocksOf( ac.form ).every( function( card ) {
				const body = byClass( card, 'design-accordion-body' )[0];
				const target = headButton( card ).getAttribute('aria-controls');
				return body === undefined ? target === null : typeof target === 'string' && target !== '' && body.id === target && ac.byId( target ) === body;
			} );
		} );
		return good;
	} )() );
check( '...the head is named by the word of its aspect - the element it is labelled by says that word, so that what a closed block says of its row is no part of its name - and described by the line that says it, a second element of the head; the chevron is the last, and decoration: out of the reach of assistive technology',
	( function() {
		let good = true;
		acEach( function() {
			good = good && blocksOf( ac.form ).every( function( card ) {
				const head = headButton( card );
				const named = ac.byId( head.getAttribute('aria-labelledby') );
				const described = ac.byId( head.getAttribute('aria-describedby') );
				const chevron = byTag( head, 'svg' )[0];
				return head.children.length === 3 && named === head.children[0] && named.textContent === tabWord( card.dataset.tab )
					&& described === head.children[1] && hasClass( described, 'design-accordion-summary' )
					&& chevron === head.children[2] && chevron.getAttribute('aria-hidden') === 'true' && chevron.getAttribute('focusable') === 'false' && chevron.dataset.icon === 'chevron-down';
			} );
		} );
		return good;
	} )() );
check( '...and the head of a block that stands at the defaults says nothing: the line of every block of a fresh setup is empty - the open one and the closed ones - in every aspect but Base, whose heads name the template that is chosen, whichever it is',
	( function() {
		let good = true;
		acEach( function() {
			good = good && blocksOf( ac.form ).every( function( card ) {
				const line = byClass( card, 'design-accordion-summary' )[0];
				return card.dataset.tab === 'templates' ? ( line.textContent === '' || isOpen( card ) === false ) : line.textContent === '' && line.title === '';
			} );
		} );
		return good;
	} )() );

// Opening a block closes the open one, and pressing the open one closes it: the heads stay the elements they are, so that the focus stays where the hand put it
acScreen('surfaces');
goTo( ac, 'card' );

const acHeadsBefore = acHeads();
const acFirstBlock = blocksOf( ac.form )[0];
const acSecond = acCard('card');

toggle( acSecond );

check( 'pressing the head of another block opens it and closes the open one: the block that was open has no body any more and its head says it is closed, the one that was pressed has its body drawn and says it is open - and the heads are the elements they were',
	acSecond !== acFirstBlock && acOpen().length === 1 && acOpen()[0] === headButton( acSecond ) && byClass( acFirstBlock, 'design-accordion-body' ).length === 0 && isOpen( acFirstBlock ) === false
	&& byClass( acSecond, 'design-accordion-body' ).length === 1 && acBodies().length === 1 && acHeads().every( function( head, at ) { return head === acHeadsBefore[at] } ) );

toggle( acSecond );

check( '...pressing the head of the open block closes it, which leaves none open - and pressing it once more opens it again',
	acOpen().length === 0 && acBodies().length === 0 && ( toggle( acSecond ), acOpen().length === 1 && acOpen()[0] === headButton( acSecond ) && acBodies().length === 1 ) );

// A change in a block, and the block closed: the head names the control, by its label and the word of the position as the strip shows it
const acBorderKey = 'knobs/card/border';
const acShadowKey = 'knobs/card/shadow';

pressKey( ac, acBorderKey, 1 );

const acBorderSays = acSays( knobWord( 'card', 'border' ), controlOf( ac, acBorderKey ), 1 );

pressKey( ac, acShadowKey, 3 );

const acShadowSays = acSays( knobWord( 'card', 'shadow' ), controlOf( ac, acShadowKey ), 3 );

toggle( acCard('card') );

check( 'after a change in a block, and the block closed, its head names the controls that were moved - the label of each and the word of its position, from the least row of the table to the last, joined by a middle dot - and a control nobody moved is not named',
	acOpen().length === 0 && acLine('card') === acBorderSays+ acSeparator+ acShadowSays && acLine('card').split( acSeparator ).length === 2
	&& acLine('card').indexOf( knobWord( 'card', 'space' ) ) === -1 && acLine('page') === '' && acLine('section') === ''
	&& byClass( acCard('card'), 'design-accordion-summary' )[0].title === acLine('card') );

toggle( acCard('card') );

check( '...and an open block says nothing: the line is empty again while the controls themselves stand in view, on the positions they were moved to',
	acLine('card') === '' && byClass( acCard('card'), 'design-accordion-summary' )[0].title === '' && pressedKey( ac, acBorderKey ) === 'false,false,false,true,false'
	&& pressedKey( ac, acShadowKey ) === 'false,false,false,true,false' );

// The other kinds of control: a choice - in its words -, a strip of the page, the corners that are one choice for the cover, the row and the column of a section
pressKey( ac, 'knobs/tables/zebra', 1 );

const acZebraSays = acSays( knobWord( 'tables', 'zebra' ), controlOf( ac, 'knobs/tables/zebra' ), 1 );

pressKey( ac, 'width', 2 );

const acWidthSays = acSays( TEXT['/_admin/design/label/pagewidth'], controlOf( ac, 'width' ), 2 );

pressKey( ac, 'radii/cover', 'alternative' );

const acCornersSays = acSays( TEXT['/_admin/design/radii/section'], controlOf( ac, 'radii/cover' ), 'alternative' );


check( '...a choice is named with the word it has for its position, as its button says it, and a scale with the number on its button, from \u22122 to +2, a grade from 0 to +4',
	acClosedLine('tables') === knobWord( 'tables', 'zebra' )+ ' '+ TEXT['/_admin/design/knob/tables/zebra/more'] && acZebraSays === acClosedLine('tables')
	&& acBorderSays === knobWord( 'card', 'border' )+ ' '+ SIGNED[3] && acShadowSays === knobWord( 'card', 'shadow' )+ ' +3' );
check( '...a strip of the page is named by the label of its key, the width of the page with its +2 - and the heads that were closed in turn kept what they said',
	acClosedLine('page') === acWidthSays && acWidthSays === TEXT['/_admin/design/label/pagewidth']+ ' '+ SIGNED[4] && acClosedLine('card') === acBorderSays+ acSeparator+ acShadowSays && acOpen().length === 0 );
check( '...and the corners of a section are one control and are named once, whatever kinds they stand for: the label of the choice and the word of its strip - the cover, the row and the column are all on it',
	acClosedLine('section') === acCornersSays && acClosedLine('section').split( acSeparator ).length === 1 && acCornersSays === TEXT['/_admin/design/radii/section']+ ' '+ TEXT['/_admin/design/radii/alternative']
	&& ac.design._edit.radii.cover === 'alternative' && ac.design._edit.radii.row === 'alternative' && ac.design._edit.radii.column === 'alternative' );

// A font role says it by the cell of its table: the role and the column, and what the cell shows
acScreen('type');

const acWeight = ac.byId('design-font-title-weight');
const acFamily = ac.byId('design-font-title-family');
const acOtherFamily = offered( acFamily ).map( function( option ) { return option[0] } ).filter( function( value ) { return value !== shown( acFamily ) } )[0];

choose( acFamily, acOtherFamily );
choose( acWeight, 'light' );
type( ac.byId('design-font-title-size'), '2.4' );
pressKey( ac, 'size', 1 );

const acSizeSays = acSays( TEXT['/_admin/design/label/size'], controlOf( ac, 'size' ), 1 );
const acRoleSays = function( column, word ) { return TEXT['/_admin/design/font/role/title']+ ' - '+ TEXT['/_admin/design/font/head/'+ column]+ ' '+ word };
const acFamilySays = acRoleSays( 'family', acOption( acFamily, acOtherFamily ) );
const acWeightSays = acRoleSays( 'weight', acOption( acWeight, 'light' ) );
const acRemSays = acRoleSays( 'size', ac.byId('design-font-title-size').value+ ' '+ TEXT['/_admin/design/fontsize/unit'] );


check( 'a font role is named by the cells of its table that differ - the role and the column, "Title - Weight", and the word the cell shows: the family and the weight as their selects say them, the size as it is typed with its unit - after the size of the page, which stands before the table, and in the order of the columns',
	acClosedLine('page') === [ acSizeSays, acFamilySays, acWeightSays, acRemSays ].join( acSeparator ) && acFamilySays.slice( -acOption( acFamily, acOtherFamily ).length ) === acOption( acFamily, acOtherFamily ) && acWeightSays === TEXT['/_admin/design/font/role/title']+ ' - '+ TEXT['/_admin/design/font/head/weight']+ ' '+ TEXT['/_admin/design/weight/light']
	&& acRemSays.slice( -4 ) === ' '+ TEXT['/_admin/design/fontsize/unit'] && acOption( acWeight, 'light' ) === TEXT['/_admin/design/weight/light'] );
check( '...the roles that stand at the defaults are not named, nor the cells of a role that do: no code row, no subtitle, no cell of the title that stands where it stood - four lines, and no other',
	acClosedLine('page').indexOf( TEXT['/_admin/design/font/role/code'] ) === -1 && acClosedLine('page').indexOf( TEXT['/_admin/design/font/role/subtitle'] ) === -1 && acClosedLine('page').split( acSeparator ).length === 4
	&& ac.design._edit.fonts.code.family === '' && ac.design._edit.fonts.subtitle.weight === 'normal' );

// A knob the chosen template does not answer to is greyed out, and no part of what the design is: it is never named
const acGrey = panel();
acGrey.show( 'templates' );
choose( setSelect( acGrey, 'card' ), 'third' );
acGrey.show( 'surfaces' );
pressKey( acGrey, 'knobs/card/border', 1 );

const acGreyBorder = acSays( knobWord( 'card', 'border' ), controlOf( acGrey, 'knobs/card/border' ), 1 );

// The position of a knob the template leaves out is the middle: a stored setup that has another one cannot have been made with this template - the model is told so by hand
acGrey.design._edit.knobs.card.shadow = 2;
toggle( acGrey.byId('design-row-surfaces-card') );

const acGreyLine = byClass( acGrey.byId('design-row-surfaces-card'), 'design-accordion-summary' )[0].textContent;
const acGreyOff = acGrey.setup._answered('card').indexOf('shadow') === -1;

acGrey.show( 'templates' );
choose( setSelect( acGrey, 'card' ), 'first' );
acGrey.design._edit.knobs.card.shadow = 2;
acGrey.show( 'surfaces' );
goTo( acGrey, 'card', true );

const acGreyNamedLine = byClass( acGrey.byId('design-row-surfaces-card'), 'design-accordion-summary' )[0].textContent;
const acGreyShadow = acSays( knobWord( 'card', 'shadow' ), controlOf( acGrey, 'knobs/card/shadow' ), 2 );

check( 'a knob that is greyed out is never named in a head, whatever position the model holds for it: the card names the border and not the shadow the template does not answer to - which the template that answers to it has named as it should, after the border',
	acGreyOff === true && acGreyLine === acGreyBorder && acGreyNamedLine === acGreyBorder+ acSeparator+ acGreyShadow );

// A knob of the frames is named as any other: the height of the header, moved, stands in the closed head of the header on Surfaces
const acFrame = panel();
acFrame.show( 'surfaces' );
pressKey( acFrame, 'knobs/header/height', 2 );
pressKey( acFrame, 'knobs/header/border-size', -1 );

const acFrameCard = blocksOf( acFrame.form ).filter( function( card ) { return card.dataset.object === 'header' && card.dataset.tab === 'surfaces' } )[0];

toggle( acFrameCard );
const acFrameHeader = byClass( acFrameCard, 'design-accordion-summary' )[0].textContent;
goTo( acFrame, 'footer' );

check( 'a moved height of the header is named in the closed head of the header on Surfaces, by the label of the knob and the number on its button, in the order of the table - and so is the line of it, one step less, by the number on its button - while the head of the footer, which nobody moved, says nothing',
	acFrameHeader === knobWord( 'header', 'height' )+ ' '+ SIGNED[4]+ acSeparator+ knobWord( 'header', 'border-size' )+ ' '+ SIGNED[1]
	&& byClass( blocksOf( acFrame.form ).filter( function( card ) { return card.dataset.object === 'footer' && card.dataset.tab === 'surfaces' } )[0], 'design-accordion-summary' )[0].textContent === ''
	&& acFrame.design._edit.knobs.header.height === 2 && acFrame.design._edit.knobs.header['border-size'] === -1 );

// The block that is open under an object is kept for it while the panel lives: another object is shown, and the object is shown again
const acKeep = panel( {}, { lens : false } );
const acKeepLayout = acKeep.design._data.layout;
const acKeepOpen = function( object ) {
	acKeep.go( object );
	return blocksOf( acKeep.form ).filter( isOpen ).map( function( card ) { return card.dataset.tab } ).join();
};
const acKeepSecond = aspectsOf( acKeepLayout, 'card' )[2];

acKeep.go('card');
toggle( acKeep.byId( 'design-row-'+ acKeepSecond+ '-card' ) );
acKeep.go('buttons');

const acKeptCard = acKeepOpen('card');
const acKeptButtons = acKeepOpen('buttons');

check( 'the block that is open under an object is the one that was left open there once another object was shown and this one is shown again - and each object keeps its own: the buttons are on their first aspect, the card on the one that was opened',
	acKeptCard === acKeepSecond && acKeepSecond !== aspectsOf( acKeepLayout, 'card' )[0] && acKeptButtons === aspectsOf( acKeepLayout, 'buttons' )[0] && acKeep.setup._open.card === acKeepSecond && acKeep.setup._open.buttons === undefined );

toggle( acKeep.byId( 'design-row-'+ acKeptButtons+ '-buttons' ) );
acKeep.go('lists');

const acClosedButtons = acKeepOpen('buttons');
const acKeptAgain = acKeepOpen('card');

check( '...a block that was closed is closed when the object is shown again, with none open - and the other object has kept its own',
	acClosedButtons === '' && acKeep.setup._open.buttons === null && acKeptAgain === acKeepSecond );
check( '...an object that nobody chose a block under has its first one open, and the memory is the script\'s for the life of the panel: nothing is stored, no preview is asked for and the selection is as it was',
	panel( {}, { lens : false } ).setup._open.card === undefined && panel( {}, { lens : false } ).setup._blockOf( 'card', panel( {}, { lens : false } ).setup._rows('card') ) === aspectsOf( acKeepLayout, 'card' )[0]
	&& Object.keys( acKeep.setup._open ).sort().join() === 'buttons,card' && acKeep.requests.length === 0 && acKeep.byId('design-reset').hidden === true && acKeep.design._isDirty() === false );

// CSS is the page's block, with a field and no control of the table; the themes are their screens and the typefaces are the loader, and draw no accordion
check( 'the aspect the table draws no controls for - CSS, the page\'s own - is a block like the others, with a head and a body, and its body holds the one field of the css and no control of the table; the list of the themes and the loader of the typefaces draw no accordion: no head, no button that opens or closes a block, no block, and no control of the table',
	( function() {
		ac.show('css');
		const body = ac.byId( CSS_CARD );
		const card = blocksOf( ac.form ).filter( function( block ) { return block.dataset.tab === 'css' } )[0];
		return acTabs.indexOf('css') !== -1 && card !== undefined && card.dataset.object === 'page' && isOpen( card ) === true && body !== null && body.parent === card && body.id === headButton( card ).getAttribute('aria-controls')
			&& byTag( body, 'textarea' ).length === 1 && byTag( body, 'textarea' )[0].id === 'design-theme-css' && descendants( body ).every( function( el ) { return el.dataset.control === undefined } );
	} )() && [ 'theme', 'typefaces' ].every( function( tab ) {
		ac.show( tab );
		const where = tab === 'typefaces' ? ac.forms['design-typefaces'] : ac.form;
		return blocksOf( where ).length === 0 && byClass( where, 'design-accordion-head' ).length === 0 && descendants( where ).every( function( el ) { return el.getAttribute('aria-expanded') === null && el.getAttribute('aria-controls') === null && el.dataset.control === undefined } )
			&& byClass( where, 'design-accordion-body' ).length === 0;
	} ) );

// Opening and closing blocks is a matter of the column: the selection stays, no preview is asked for and the frame does not move - a change in a block jumps, as it did
const acStill = panel();
acStill.show( 'surfaces' );
goTo( acStill, 'card' );
const acStillAspects = aspectsOf( acStill.design._data.layout, 'card' );
const acStillBefore = [ JSON.stringify( acStill.design._edit ), acStill.requests.length, acStill.timers.length, acStill.stage.scrolls.length, acStill.design._target, acStill.fitted.length ];

acStillAspects.concat( acStillAspects.slice( -1 ) ).forEach( function( aspect ) { toggle( blocksOf( acStill.form ).filter( function( card ) { return card.dataset.tab === aspect } )[0] ) } );

check( 'opening and closing blocks moves nothing of the page: the selection is as it was and not marked as changed, no request was made, no timer set for one, the frame was not asked to scroll or to be fitted again, and the place the frame goes to is the one it had',
	JSON.stringify( [ JSON.stringify( acStill.design._edit ), acStill.requests.length, acStill.timers.length, acStill.stage.scrolls.length, acStill.design._target, acStill.fitted.length ] ) === JSON.stringify( acStillBefore )
	&& acStill.byId('design-reset').hidden === true && acStill.design._isDirty() === false );

pressKey( acStill, 'knobs/card/border', 1 );

check( '...a change inside a block jumps as it did: the frame is asked to go to the place the object is looked at in - the cards for the card - and the way back is shown',
	acStill.design._target === 'cards' && acStill.byId('design-reset').hidden === false && acStill.design._edit.knobs.card.border === 1 );

// What is drawn again is drawn from the model: a reset takes every head back to what is stored, and the block that is open stays open
const acReset = acStill.design._data.knobs.card.border;
acStill.design._revert();

check( '...a reset draws the screen again from the stored selection: the heads of the blocks name what the stored selection has - nothing, at the defaults - and the block that was open is open still',
	acStill.design._edit.knobs.card.border === acReset && blocksOf( acStill.form ).filter( function( card ) { return card.dataset.tab !== 'templates' } ).every( function( card ) { return byClass( card, 'design-accordion-summary' )[0].textContent === '' } )
	&& blocksOf( acStill.form ).filter( isOpen ).map( function( card ) { return card.dataset.tab } ).join() === 'surfaces' );

// The head that was pressed stays where it stood in the window: the block that was open can stand above it, and its body goes with the press - so the page is
// scrolled by what the head moved, by whatever scrolls the column. The stand-in has no layout: where a head stands is said by a rule of the test - 100px for the
// first, 100px for every head above it and 300px for every body that is drawn above it
const acHold = panel();
acHold.show( 'type' );
goTo( acHold, 'card' );

const acHoldCards = blocksOf( acHold.form );
const acHoldTop = function( card ) {
	const above = acHoldCards.slice( 0, acHoldCards.indexOf( card ) );
	return 100 + 100 * above.length + 300 * above.filter( isOpen ).length;
};

acHoldCards.forEach( function( card ) { headButton( card ).getBoundingClientRect = function() { return { top : acHoldTop( card ) } } } );

// A column that scrolls in a pane of its own, as it does in a wide window: the pane holds more than it shows, and says that it scrolls
const acPane = acHold.form.parent;
acPane.scrollHeight = 2000;
acPane.clientHeight = 800;
acPane.computed = { overflowY : 'auto' };
acPane.scrollTop = 900;
acHold.root.scrollTop = 40;

const acHoldAt = function( tab ) { return acHoldCards.filter( function( card ) { return card.dataset.tab === tab } )[0] };
const acHoldTabs = aspectsOf( acHold.design._data.layout, 'card' );

toggle( acHoldAt( acHoldTabs[4] ) );

check( 'a block opened under the one that was open - whose body, 300px of the column above the head, goes with the press - leaves the head where it stood: the pane is scrolled up by what the head moved, and the window is not scrolled',
	isOpen( acHoldAt( acHoldTabs[4] ) ) === true && isOpen( acHoldAt( acHoldTabs[0] ) ) === false && acPane.scrollTop === 600 && acHold.root.scrollTop === 40 );

toggle( acHoldAt( acHoldTabs[3] ) );

check( '...a block opened above the open one - whose body goes with the press from under the head - moves nothing: the head stands where it stood and the pane is not scrolled',
	isOpen( acHoldAt( acHoldTabs[3] ) ) === true && isOpen( acHoldAt( acHoldTabs[4] ) ) === false && acPane.scrollTop === 600 );

toggle( acHoldAt( acHoldTabs[3] ) );

check( '...and neither does the head of the block that is open, which closes it: nothing above it changed',
	isOpen( acHoldAt( acHoldTabs[3] ) ) === false && blocksOf( acHold.form ).filter( isOpen ).length === 0 && acPane.scrollTop === 600 );

toggle( acHoldAt( acHoldTabs[1] ) );
toggle( acHoldAt( acHoldTabs[3] ) );

check( '...and so it is for every press on a head under the open block: the head of the fourth aspect under the open second is where it was, the pane scrolled up by the 300px that went from above it',
	isOpen( acHoldAt( acHoldTabs[3] ) ) === true && isOpen( acHoldAt( acHoldTabs[1] ) ) === false && acPane.scrollTop === 300 && acHold.root.scrollTop === 40 );

// A window that scrolls itself, as a narrow one does: no pane of the column scrolls, and the window's root element is moved instead
const acWindow = panel();
acWindow.show( 'surfaces' );
const acWindowCards = blocksOf( acWindow.form );
const acWindowTop = function( card ) { return 100 + 300 * acWindowCards.slice( 0, acWindowCards.indexOf( card ) ).filter( isOpen ).length };

acWindowCards.forEach( function( card ) { headButton( card ).getBoundingClientRect = function() { return { top : acWindowTop( card ) } } } );
acWindow.root.scrollTop = 700;

toggle( acWindowCards[4] );

check( '...where no pane of the column scrolls the window does, by the same: the root element of the page is scrolled up by the 300px the head moved',
	isOpen( acWindowCards[4] ) === true && isOpen( acWindowCards[0] ) === false && acWindow.root.scrollTop === 400 );

console.log('');


// --- The knobs: a strip each, in the row and on the tab the table gives them ----------------------
//
// A strip for every knob of a template: five numbers, -2 to +2, the 0 the template as it is, or for a choice
// the three words that knob has for them - with the icon of what it sets - and for a toggle, the case of a
// thing, no strip but the switch of the workbench, off and on. The forty-six of them stand
// in the rows of Type, Surfaces, Colours and Motion, each under the object it sets (the table says which): the
// sizes of the titles on Type, the space, the lines and the shadows on Surfaces, the dimming, the tone and the
// stripes on Colours, what a card and a button do under the pointer on Motion. A knob the chosen template does
// not answer to stands where it always does, greyed out, with the line that says whose decision that is

console.log( 'The knobs' );

/** What the rows of a tab say, from the positions its knobs stand at and the order the table gives them: [ label, word of the button that is lit - or of the state of the switch ] for each knob */
function knobWords( layout, tab, positions ) {

	const words = [];

	tableRows( layout, tab ).forEach( function( row ) {
		row.controls.forEach( function( control ) {

			const path = control.split(' ')[0].split('/');

			if( path[0] !== 'knobs' )
				return;

			const position = positions[ path[1] ][ path[2] ];

			words.push( [ knobWord( path[1], path[2] ), knobStands( path[1], path[2], position ) ] );
		} );
	} );

	return words;
}

/** The word a position of a knob is said with: the signed number of a scale, the word of the knob for a choice, on or off for a toggle */
function knobStands( part, knob, position ) {

	if( KINDS[part][knob] === 'toggle' )
		return TEXT['/_admin/common/label/'+ ( position === 1 ? 'on' : 'off' )];

	if( KINDS[part][knob] === 'ink' )
		return position === '' ? TEXT['/_admin/design/label/own'] : TEXT['/_admin/design/ink/'+ position];

	if( KINDS[part][knob] === 'grade' )
		return position === 0 ? '0' : '+'+ position;

	return KINDS[part][knob] === 'scale' ? SIGNED[position + 2] : TEXT['/_admin/design/knob/'+ part+ '/'+ knob+ '/'+ [ 'less', 'default', 'more' ][position + 1]];
}

const kindCount = function( kind ) {
	return Object.keys( KINDS ).reduce( function( sum, part ) { return sum + Object.keys( KINDS[part] ).filter( function( knob ) { return KINDS[part][knob] === kind } ).length }, 0 );
};

/*	A scale is five numbers and says nothing in words: the note under its name is where the ends are. A choice - the ground of a header and
	of a footer, the tone and the hover of a card, the hover of a button, the marker of a list and the stripes of a table - is the three
	words that knob has for its positions, and they begin with a capital letter, as the words of the Colours view do. Read off the buttons
	that are drawn - their text and their title - and not off the table they come from, so that a word that goes back to lower case is
	seen. The kind of a knob is what design/list says of it, and the strip follows: the values on the buttons of a scale are -2 to +2,
	those of a choice -1 to +1	*/
const CHOICE_WORDS = {};

Object.keys( KINDS ).forEach( function( part ) {
	Object.keys( KINDS[part] ).filter( function( knob ) { return KINDS[part][knob] === 'choice' } ).forEach( function( knob ) {
		CHOICE_WORDS[part+ '/'+ knob] = [ 'less', 'default', 'more' ].map( function( word ) { return TEXT['/_admin/design/knob/'+ part+ '/'+ knob+ '/'+ word] } ).join();
	} );
} );
const wordProblems = [];
let wordRows = 0;
let scaleRows = 0;
let choiceRows = 0;
let toggleRows = 0;
let inkRows = 0;
let gradeRows = 0;
const worded = panel();
const knobKeys = Object.keys( worded.design._data.layout.controls ).filter( function( key ) { return key.indexOf('knobs/') === 0 } );

knobKeys.forEach( function( key ) {

	const path = key.split('/');

	worded.show( worded.design._data.layout.controls[key][0] );

	const row = controlOf( worded, key );

	if( row === null || hasClass( row, 'design-knob-row' ) === false ) {
		wordProblems.push( key+ ': not drawn' );
		return;
	}

	// A toggle is a switch: a checkbox of the workbench with the name of the knob for its label and the note for its hint, off at the position the defaults put it, and no button at all
	if( worded.design._data.kinds[ path[1] ][ path[2] ] === 'toggle' ) {

		const input = byTag( row, 'input' )[0];
		const state = byClass( row, 'nino-admin-switch-state' )[0];

		wordRows++;
		toggleRows++;

		if( isSwitch( row ) === false || byTag( row, 'button' ).length !== 0 || input === undefined || input.type !== 'checkbox' || input.disabled !== false
			|| input.checked !== ( worded.design._data.defaults.knobs[ path[1] ][ path[2] ] === 1 ) || state === undefined || state.textContent !== TEXT['/_admin/common/label/off']
			|| ( byTag( row, 'small' )[0] || {} ).textContent !== TEXT['/_admin/design/knob/'+ path[1]+ '/'+ path[2]+ '/note'] || rowName( row ) !== knobWord( path[1], path[2] ) )
			wordProblems.push( key+ ': a switch that is none' );

		return;
	}

	// An ink is a select of the tones of the page, none first - the tone the template gives - with the name of the knob for its label and the note beside it, at none to begin with
	if( worded.design._data.kinds[ path[1] ][ path[2] ] === 'ink' ) {

		const select = byTag( row, 'select' )[0];

		wordRows++;
		inkRows++;

		if( select === undefined || byTag( row, 'button' ).length !== 0 || select.disabled !== false || shown( select ) !== worded.design._data.defaults.knobs[ path[1] ][ path[2] ]
			|| JSON.stringify( offered( select ) ) !== JSON.stringify( [ [ '', TEXT['/_admin/design/label/own'] ] ].concat( INKS.map( function( tone ) { return [ tone, TEXT['/_admin/design/ink/'+ tone] ] } ) ) )
			|| ( byTag( row, 'small' )[0] || {} ).textContent !== TEXT['/_admin/design/knob/'+ path[1]+ '/'+ path[2]+ '/note'] || rowName( row ) !== knobWord( path[1], path[2] ) )
			wordProblems.push( key+ ': a select that is none' );

		return;
	}

	const buttons = byTag( row, 'button' );
	const drawn = buttons.map( function( button ) { return button.textContent } ).join();
	const choice = CHOICE_WORDS[ path[1]+ '/'+ path[2] ] !== undefined;
	const grade = worded.design._data.kinds[ path[1] ][ path[2] ] === 'grade';

	wordRows++;

	if( choice === true )
		choiceRows++;
	else if( grade === true )
		gradeRows++;
	else
		scaleRows++;

	// A grade only grows: 0 at the left, the kernel's own, and +1 to +4 after it
	if( drawn !== ( choice === true ? CHOICE_WORDS[ path[1]+ '/'+ path[2] ] : ( grade === true ? '0,+1,+2,+3,+4' : SIGNED.join() ) ) || buttons.map( function( button ) { return button.title } ).join() !== drawn
		|| ( choice === true && buttons.some( function( button ) { return button.textContent.charAt(0) !== button.textContent.charAt(0).toUpperCase() } ) )
		|| buttons.map( function( button ) { return button.dataset.value } ).join() !== ( choice === true ? '-1,0,1' : ( grade === true ? '0,1,2,3,4' : '-2,-1,0,1,2' ) )
		|| worded.design._data.kinds[ path[1] ][ path[2] ] !== ( choice === true ? 'choice' : ( grade === true ? 'grade' : 'scale' ) )
		|| ( byTag( row, 'small' )[0] || {} ).textContent !== TEXT['/_admin/design/knob/'+ path[1]+ '/'+ path[2]+ '/note'] || rowName( row ) !== knobWord( path[1], path[2] ) )
		wordProblems.push( key+ ': '+ drawn );
} );

check( 'a scale is five numbers, a grade five that only grow, a choice the three words of the knob, each capitalised, a toggle a switch and an ink a select of the tones with none first - a row for every knob the table has, each on the tab and under the object it names, with the title of each button its label, the values on them -2 to +2, 0 to +4 and -1 to +1, and a note beside the name'
	+ ( wordProblems.length === 0 ? '' : ' - not so for '+ wordProblems.join(' | ') ),
	wordRows === knobKeys.length && wordRows === Object.keys( KNOBS ).reduce( function( sum, part ) { return sum + KNOBS[part].length }, 0 )
	&& scaleRows === kindCount('scale') && gradeRows === kindCount('grade') && choiceRows === kindCount('choice') && toggleRows === kindCount('toggle') && inkRows === kindCount('ink') && choiceRows > 0 && toggleRows === 9 && scaleRows === 54 && gradeRows === 4 && inkRows === 10 && wordProblems.length === 0 );
check( '...the twenty-five choices stand at the word the defaults put them at to begin with, which is the middle but for the navigation and the button of the header - the lit button of the three is the one of that position - on the tab the table puts each on',
	Object.keys( CHOICE_WORDS ).length === 25 && Object.keys( CHOICE_WORDS ).every( function( at ) {
		const key = 'knobs/'+ at;
		const stands = worded.design._data.defaults.knobs[ at.split('/')[0] ][ at.split('/')[1] ];
		worded.show( worded.design._data.layout.controls[key][0] );
		return CHOICE_WORDS[at].split(',').length === 3 && pressedKey( worded, key ) === [ 0, 1, 2 ].map( function( lit ) { return String( lit === stands + 1 ) } ).join();
	} ) );

const moved = listing().knobs;
moved.card.shadow = 1;
moved.buttons.corners = -1;

const kn = panel( { knobs : moved } );
const knLayout = kn.design._data.layout;
const sectionTitle = knobWord( 'section', 'title' );
const sectionSubtitle = knobWord( 'section', 'subtitle' );

kn.tab( tabWord('type') );

const knTypeRows = knobRows( kn );

check( 'the knobs of Type are the rows of its objects, in the order the table gives them - the sizes of the navigation of the header, of the footer, of the titles of a section and of its quiet and loud variants, of a card, of the buttons, of the lists and of the tables - and none of the knobs of Surfaces: a row for each, named in the panel\'s words',
	tabOf( kn ) === 'type' && knTypeRows.map( rowName ).join() === knobWords( knLayout, 'type', moved ).map( function( word ) { return word[0] } ).join()
	&& knTypeRows.length === knobKeys.filter( function( key ) { return knLayout.controls[key][0] === 'type' } ).length );

kn.tab( tabWord('surfaces') );

check( '...and those of Surfaces the rows of the space, the lines and the shadows, each under the object it sets',
	knobRows( kn ).map( rowName ).join() === knobWords( knLayout, 'surfaces', moved ).map( function( word ) { return word[0] } ).join()
	&& knobRows( kn ).length === knobKeys.filter( function( key ) { return knLayout.controls[key][0] === 'surfaces' } ).length
	&& knobRows( kn ).every( function( row ) { return knLayout.controls[ row.dataset.control ][1] === cardOf( row ).dataset.object } ) );

const knOtherRows = {};

[ 'colours', 'motion' ].forEach( function( tab ) { kn.tab( tabWord( tab ) ); knOtherRows[tab] = knobRows( kn ) } );

check( '...and those of Colours the rows of the ground of the header, the colour of its line, the ground of the footer, the colour of its line, the dimming, the ground of the title and the colour of the rule of a section, the tone of the shadow and the ground of the title of a card and the stripes of a table, and those of Motion what a card and a button do under the pointer - each under the object it sets, after what the page has on the tab',
	[ 'colours', 'motion' ].every( function( tab ) {
		return knOtherRows[tab].map( rowName ).join() === knobWords( knLayout, tab, moved ).map( function( word ) { return word[0] } ).join()
			&& knOtherRows[tab].length === knobKeys.filter( function( key ) { return knLayout.controls[key][0] === tab } ).length
			&& knOtherRows[tab].every( function( row ) { return knLayout.controls[ row.dataset.control ][1] === cardOf( row ).dataset.object && knLayout.controls[ row.dataset.control ][1] !== 'page' } );
	} ) && knOtherRows.colours.length === 21 && knOtherRows.motion.length === 2 );

kn.tab( tabWord('surfaces') );

const knTitlePressed = ( kn.tab( tabWord('type') ), kn.pressed( sectionTitle ) );
const knShadowPressed = ( kn.tab( tabWord('surfaces') ), pressedKey( kn, 'knobs/card/shadow' ) );

check( '...a group of buttons, not a tablist - the one it stands at is pressed and the others are not: the middle for a knob nobody moved, the +1 the shadow stands at',
	knobRows( kn ).filter( function( row ) { return isSwitch( row ) === false && isSelect( row ) === false } ).every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' && byClass( row, 'design-knob-steps' )[0].getAttribute('aria-label') === rowName( row ) } )
	&& knTitlePressed === 'false,false,true,false,false' && knShadowPressed === 'false,true,false,false,false' );
check( 'a template that answers to every knob greys none of them out, on whichever tab',
	[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].every( function( tab ) {
		kn.show( tab );
		return offRows( kn ).length === 0 && knobRows( kn ).every( function( row ) { return byTag( row, 'button' ).concat( byTag( row, 'input' ) ).every( function( control ) { return control.disabled === false } ) } );
	} ) );

kn.tab( tabWord('type') );
kn.move( sectionTitle, 1 );

check( 'moving a knob is the selection on screen: the position is a number, the button it stands at is lit',
	kn.design._edit.knobs.section.title === 1 && typeof kn.design._edit.knobs.section.title === 'number'
	&& kn.pressed( sectionTitle ) === 'false,false,false,true,false'
	&& kn.byId('design-reset').hidden === false );

kn.move( sectionTitle, -2 );
kn.move( sectionSubtitle, 2 );

check( '...to the ends of the scale it is -2 and +2: each stores the number it is labelled with, and each knob keeps its own position',
	kn.design._edit.knobs.section.title === -2 && kn.design._edit.knobs.section.subtitle === 2 && kn.design._edit.knobs.section.quiet === 0
	&& kn.pressed( sectionTitle ) === 'true,false,false,false,false' && kn.pressed( sectionSubtitle ) === 'false,false,false,false,true' );

const knTabs = [ 'templates', 'type', 'surfaces', 'colours', 'motion' ];
const knLit = {};

knTabs.forEach( function( tab ) { kn.tab( tabWord( tab ) ); knLit[tab] = knobLit( kn ) } );

const knAllLit = function() { return knTabs.reduce( function( words, tab ) { return words.concat( knLit[tab] ) }, [] ) };

check( 'the rows say, for every knob the template answers to, the number of the position it stands at - read off the button that is lit, row after row in the order of the table, on Templates, Type, Surfaces, Colours and Motion, with nothing under the frame to say it again',
	knTabs.every( function( tab ) { return JSON.stringify( knLit[tab] ) === JSON.stringify( knobWords( knLayout, tab, kn.design._edit.knobs ) ) } )
	&& knAllLit().length === knobKeys.length && kn.byId('design-summary') === null );
// The lines of knLit are in the order of the table, and a label may stand for two knobs: a word is found by the key of its knob
const knKeys = knTabs.reduce( function( keys, tab ) {
	return keys.concat( [].concat.apply( [], tableRows( knLayout, tab ).map( function( row ) { return row.controls.map( function( control ) { return control.split(' ')[0] } ) } ) ).filter( function( key ) { return key.indexOf('knobs/') === 0 } ) );
}, [] );
const knWord = function( key ) { return knAllLit()[ knKeys.indexOf( key ) ][1] };
const knMiddles = Object.keys( CHOICE_WORDS ).map( function( at ) { return TEXT['/_admin/design/knob/'+ at+ '/default'] } );

const knStands = function( key ) {
	const at = key.split('/');
	return knobStands( at[1], at[2], kn.design._data.defaults.knobs[ at[1] ][ at[2] ] );
};

check( '...in numbers: −2 for the title of a section, +2 for its subtitle, −1 for the corners of the buttons, +1 for the shadow of a card and, for the knobs nobody moved, the word of the position the defaults put them at - 0 for a scale, the middle word for a choice but for the navigation and the button of the header',
	knWord('knobs/section/title') === '−2' && knWord('knobs/section/subtitle') === '+2' && knWord('knobs/buttons/corners') === '−1' && knWord('knobs/card/shadow') === '+1'
	&& knAllLit().every( function( word, at ) { return [ 'knobs/section/title', 'knobs/section/subtitle', 'knobs/buttons/corners', 'knobs/card/shadow' ].indexOf( knKeys[at] ) !== -1 || word[1] === knStands( knKeys[at] ) } ) && knMiddles.length === 25 );

/*	Another template brings its own knobs, and the ones the last one had are
	not this one's: a position the new template does not answer to goes back to
	the default's position, the way the server holds it anyway - so the screen never shows a
	setup the stored one could not be. The rows stand on Templates, Type, Surfaces, Colours and Motion and are
	drawn when those are opened: the knob the template leaves out is still a row,
	greyed out	*/
const knRowsBefore = JSON.stringify( knTabs.map( function( tab ) { kn.show( tab ); return drawnRows( kn ) } ) );

kn.tab( tabWord('templates') );
choose( setSelect( kn, 'card' ), 'third' );

check( 'choosing a template that leaves a knob out - one of somebody else\'s library: those the feature ships answer to every knob of their part - puts the position of that knob back in the middle, and leaves the others where they were',
	kn.design._edit.parts.card === 'third' && kn.design._edit.knobs.card.shadow === 0
	&& kn.design._edit.knobs.section.title === -2 && kn.design._edit.knobs.section.subtitle === 2 );

kn.tab( tabWord('surfaces') );

const knShadow = controlOf( kn, 'knobs/card/shadow' );

check( 'on the tab the knob the template does not answer to is still a row, greyed out - not hidden, so the tabs are the same screens for every template: the rows of every tab are the rows they were, and one knob of the lot is grey',
	JSON.stringify( knTabs.map( function( tab ) { kn.show( tab ); return drawnRows( kn ) } ) ) === knRowsBefore
	&& ( kn.tab( tabWord('surfaces') ), offRows( kn ).length === 1 && offRows( kn )[0].dataset.control === 'knobs/card/shadow' )
	&& ( kn.tab( tabWord('type') ), offRows( kn ).length === 0 ) );

kn.tab( tabWord('surfaces') );

const knShadowRow = controlOf( kn, 'knobs/card/shadow' );

check( '...its buttons switched off and none of them pressed',
	knShadow !== null && byTag( knShadowRow, 'button' ).length === 5 && byTag( knShadowRow, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& byTag( controlOf( kn, 'knobs/card/border' ), 'button' ).every( function( button ) { return button.disabled === false } ) );
check( '...and the line under its name says whose decision that is, in place of its note',
	( byTag( knShadowRow, 'small' )[0] || {} ).textContent === say( '/_admin/design/hint/unanswered', knobWord( 'card', 'shadow' ) )
	&& ( byTag( controlOf( kn, 'knobs/card/border' ), 'small' )[0] || {} ).textContent === TEXT['/_admin/design/knob/card/border/note'] );
check( 'a row that is greyed out says no position, since it compiles nothing: every other knob of the tab has a button that is lit, and the shadow is not one of them',
	knobLit( kn ).length === knobWords( knLayout, 'surfaces', kn.design._edit.knobs ).length - 1 && offRows( kn ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/card/shadow' );

kn.tab( tabWord('templates') );
choose( setSelect( kn, 'card' ), 'first' );
kn.tab( tabWord('surfaces') );

check( 'choosing the template that has it again takes the grey away, with the knob in the middle',
	offRows( kn ).length === 0 && kn.design._edit.knobs.card.shadow === 0
	&& pressedKey( kn, 'knobs/card/shadow' ) === 'true,false,false,false,false' );

// The buttons' own template leaves the corners to the template: the position the stored setup cannot have had
check( 'the buttons\' template Square leaves the corners out, which the stored position cannot have been: its row is the one grey row of Surfaces, once that template is chosen',
	kn.design._edit.knobs.buttons.corners === -1 && offRows( kn ).length === 0
	&& ( kn.tab( tabWord('templates') ), choose( setSelect( kn, 'buttons' ), 'third' ), kn.tab( tabWord('surfaces') ), kn.design._edit.knobs.buttons.corners === 0
		&& offRows( kn ).length === 1 && hasClass( controlOf( kn, 'knobs/buttons/corners' ), 'design-knob-row--off' ) ) );

kn.tab( tabWord('templates') );
choose( setSelect( kn, 'tables' ), 'fourth' );
kn.tab( tabWord('surfaces') );

check( 'Filled, the template of the tables, answers to every knob of its part: none of the rows of its object on Surfaces is grey - the three of them, the text size standing on Type and the stripes on Colours',
	knobRows( kn ).filter( function( row ) { return cardOf( row ).dataset.object === 'tables' } ).length === KNOBS.tables.length - 2 && knobRows( kn ).every( function( row ) { return cardOf( row ).dataset.tab === 'surfaces' } )
	&& knobRows( kn ).filter( function( row ) { return cardOf( row ).dataset.object === 'tables' && hasClass( row, 'design-knob-row--off' ) } ).length === 0 );

console.log('');


// --- The page's strips and the corners ------------------------------------------------------------------
//
// The root size, the width, the whitespace and the two radii of the page are strips of five, like the knobs: no select, no field
// grid, and no word for a position, which a strip does not have. The root size stands on Type, in the card of the page, over the
// table of the font roles; the other four stand on Surfaces, in the card of the page, with the corners the images take. A corner
// choice is a strip of two words: the strength a kind of surface takes where nothing chooses, Standard or Alternative

console.log( 'The page' );

const sizeStrip = TEXT['/_admin/design/label/size'];
const widthStrip = TEXT['/_admin/design/label/pagewidth'];
const whitespaceStrip = TEXT['/_admin/design/label/whitespace'];
const radiusStrip = TEXT['/_admin/design/label/radius'];
const radiusAltStrip = TEXT['/_admin/design/label/radius-alt'];
const pgStrips = { type : [ sizeStrip ], surfaces : [ widthStrip, whitespaceStrip, radiusStrip, radiusAltStrip ] };
const pg = panel();
const stripLabels = function( label ) { return pg.steps( label ).map( function( button ) { return button.textContent } ).join() };
const bodyOf = function( card ) { return byClass( card, 'design-accordion-body' )[0] };
const stripRowsOf = function( card ) { return bodyOf( card ).children.filter( function( child ) { return hasClass( child, 'design-knob-row' ) } ) };

pg.tab( tabWord('type') );

const pgTypeCard = pg.byId('design-row-type-page');
const pgTypeRows = stripRowsOf( pgTypeCard );
// A block that is closed lets go of its body (one aspect is open under the page at a time): what is read of it is read now
const pgTypeBody = bodyOf( pgTypeCard );

pg.tab( tabWord('surfaces') );

const pgCard = pg.byId('design-row-surfaces-page');
const pgRows = stripRowsOf( pgCard );
const pgBody = bodyOf( pgCard );

check( 'the root size is a strip in the block of the page on Type - the first thing in its body, before the table of the roles - and the width, the whitespace and the two radii strips in the block of the page on Surfaces, in the order of the table, with the corners of the images after them: each named in the panel\'s words, none of them a select or a field grid',
	hasClass( pgTypeCard, 'nino-admin-card' ) && pgTypeRows.map( rowName ).join() === pgStrips.type.join() && pgTypeBody.children.indexOf( pgTypeRows[0] ) === 0
	&& pgTypeBody.children.indexOf( pgTypeRows[0] ) < pgTypeBody.children.indexOf( byTag( pgTypeBody, 'table' )[0] )
	&& hasClass( pgCard, 'nino-admin-card' ) && pgRows.slice( 0, 4 ).map( rowName ).join() === pgStrips.surfaces.join() && pgRows.length === 5
	&& pgBody.children.slice( 0, 5 ).every( function( child ) { return hasClass( child, 'design-knob-row' ) } )
	&& byTag( pgBody, 'select' ).length === 0 && byClass( pgBody, 'design-form-grid' ).length === 0 && pg.byId('design-size') === null && pg.byId('design-pagewidth') === null
	&& pgRows.slice( 0, 4 ).map( function( row ) { return row.dataset.control } ).join() === 'width,whitespace,radius,radius-alt' );
check( '...each over its note, with the five buttons of a scale: the numbers from the middle, -2 to +2, written with the minus sign of arithmetic (U+2212) and not a hyphen - the title of a button is its label - and the middle one pressed to begin with, the alternative radius at +1',
	pgStrips.surfaces.every( function( label ) { return stripLabels( label ) === SIGNED.join() && pg.steps( label ).map( function( button ) { return button.dataset.value } ).join() === '-2,-1,0,1,2' } )
	&& pgStrips.surfaces.every( function( label ) { return pg.steps( label ).every( function( button ) { return button.title === button.textContent && button.disabled === false } ) } )
	&& [ widthStrip, whitespaceStrip, radiusStrip ].every( function( label ) { return pg.pressed( label ) === 'false,false,true,false,false' } ) && pg.pressed( radiusAltStrip ) === 'false,false,false,true,false'
	&& ( pg.tab( tabWord('type') ), stripLabels( sizeStrip ) === SIGNED.join() && stripLabels( sizeStrip ).indexOf('-') === -1 && pg.pressed( sizeStrip ) === 'false,false,true,false,false' ) );
check( '...a group of buttons as the knob rows are, named by what it sets, and under each name the note of its key, which says where the ends are',
	pgTypeRows.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' && byClass( row, 'design-knob-steps' )[0].getAttribute('aria-label') === rowName( row ) } )
	&& pgRows.every( function( row ) { return byClass( row, 'design-knob-steps' )[0].getAttribute('role') === 'group' && byClass( row, 'design-knob-steps' )[0].getAttribute('aria-label') === rowName( row ) } )
	&& byTag( pg.knobRow( sizeStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/size'] && TEXT['/_admin/design/hint/size'] !== ''
	&& ( pg.tab( tabWord('surfaces') ), byTag( pg.knobRow( widthStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/pagewidth'] ) && TEXT['/_admin/design/hint/pagewidth'] !== ''
	&& byTag( pg.knobRow( whitespaceStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/whitespace'] && TEXT['/_admin/design/hint/whitespace'] !== ''
	&& byTag( pg.knobRow( radiusStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/radius'] && byTag( pg.knobRow( radiusAltStrip ), 'small' )[0].textContent === TEXT['/_admin/design/hint/radius-alt'] );
check( '...the script keeps no list of the widths and no step to read a word off any more: PAGE_WIDTHS and _stepOf are gone, and a position of a scale reaches two to each side, a choice one, a toggle the middle and the one after it, a grade four from the first (the same answer Setup::positions() gives), the middle being the word default and the one after it more',
	pg.design.PAGE_WIDTHS === undefined && pg.design._stepOf === undefined && JSON.stringify( pg.design.RANGE ) === JSON.stringify( { scale : [ -2, 2 ], choice : [ -1, 1 ], toggle : [ 0, 1 ], grade : [ 0, 4 ], ink : [ 0, 0 ] } )
	&& JSON.stringify( pg.setup._positions('scale') ) === '[-2,-1,0,1,2]' && JSON.stringify( pg.setup._positions('choice') ) === '[-1,0,1]' && JSON.stringify( pg.setup._positions('toggle') ) === '[0,1]' && JSON.stringify( pg.setup._positions('grade') ) === '[0,1,2,3,4]'
	&& JSON.stringify( pg.setup._positions('ink') ) === '[0]' && JSON.stringify( pg.setup._positions('nothing') ) === '[0]' && pg.setup._word( 0 ) === 'default' && pg.setup._word( 1 ) === 'more' && pg.setup._word( -2 ) === 'least' && pg.setup._word( 2 ) === 'most' );

pg.move( widthStrip, 1 );
pg.move( whitespaceStrip, -1 );
pg.move( radiusStrip, -2 );
pg.move( radiusAltStrip, 2 );
pg.tab( tabWord('type') );
pg.move( sizeStrip, 2 );

check( 'pressing a button stores the number, as a number: +2 for the size, +1 for the width, -1 for the whitespace, -2 for the standard radius and +2 for the alternative one, and the button is lit - and the strips that are not open are as they were',
	pg.design._edit.size === 2 && pg.design._edit.width === 1 && pg.design._edit.whitespace === -1 && pg.design._edit.radius === -2 && pg.design._edit['radius-alt'] === 2
	&& [ 'size', 'width', 'whitespace', 'radius', 'radius-alt' ].every( function( key ) { return typeof pg.design._edit[key] === 'number' } )
	&& pg.pressed( sizeStrip ) === 'false,false,false,false,true'
	&& ( pg.tab( tabWord('surfaces') ), pg.pressed( widthStrip ) === 'false,false,false,true,false' && pg.pressed( whitespaceStrip ) === 'false,true,false,false,false'
		&& pg.pressed( radiusStrip ) === 'true,false,false,false,false' && pg.pressed( radiusAltStrip ) === 'false,false,false,false,true' ) );
check( '...each of the five can be pressed, for the size, the width, the whitespace and the two radii, and each is what the model keeps as it was pressed - -2 stores -2, +2 stores 2',
	[ -2, -1, 0, 1, 2 ].every( function( position ) {
		const lit = function( label ) { return pg.pressed( label ) === SIGNED.map( function( word, at ) { return String( at === position + 2 ) } ).join() };
		pg.tab( tabWord('type') );
		pg.move( sizeStrip, position );
		const size = lit( sizeStrip );
		pg.tab( tabWord('surfaces') );
		pgStrips.surfaces.forEach( function( label ) { pg.move( label, position ) } );
		return size && pgStrips.surfaces.every( lit ) && [ 'size', 'width', 'whitespace', 'radius', 'radius-alt' ].every( function( key ) { return pg.design._edit[key] === position } );
	} ) );
check( '...and a strip of the page is a change like any other: it marks the selection as not stored, and is what a preview carries',
	pg.byId('design-reset').hidden === false && ( pg.design._previewNow(), pg.requests[pg.requests.length - 1].payload.size === 2 && pg.requests[pg.requests.length - 1].payload.width === 2
		&& pg.requests[pg.requests.length - 1].payload.whitespace === 2 && pg.requests[pg.requests.length - 1].payload.radius === 2 && pg.requests[pg.requests.length - 1].payload['radius-alt'] === 2 ) );

// The corners a kind of surface takes: the corner choices the table has, one for each place of it that has radii keys
const cr = panel( { radii : { cover : 'standard', row : 'standard', column : 'standard', tile : 'standard', card : 'standard', image : 'standard' } } );
const crLayout = cr.design._data.layout;
const crKeys = Object.keys( crLayout.controls ).filter( function( key ) { return key.indexOf('radii/') === 0 } );
const crPlaces = {};
crKeys.forEach( function( key ) { ( crPlaces[ crLayout.controls[key].join('/') ] = crPlaces[ crLayout.controls[key].join('/') ] || [] ).push( key ) } );

cr.tab( tabWord('surfaces') );

const crChoices = Object.keys( crPlaces ).map( function( place ) { return controlOf( cr, crPlaces[place][0] ) } );
const crOf = function( keys ) { return controlOf( cr, keys[0] ) };
const crLit = function( row ) { return byTag( row, 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() };
const crPress = function( row, word ) { byTag( row, 'button' ).filter( function( button ) { return button.dataset.value === word } ).forEach( function( button ) { button.fire('click') } ) };
const crLabelOf = function( row ) { return rowName( row ) };

check( 'the corners a kind of surface takes are a choice of two words for each place of the table that has them - not one for each kind: the cover, the row and the column of a section are ONE choice, named for the section, that stands for the three keys, and a card and the pictures have a choice each, named for the kind',
	crKeys.length === SURFACES.length && Object.keys( crPlaces ).length === 4 && crChoices.every( function( row ) { return row !== null && hasClass( row, 'design-knob-row' ) } )
	&& crOf( crPlaces[ crLayout.controls['radii/cover'].join('/') ] ).dataset.control === 'radii/cover radii/row radii/column'
	&& crPlaces[ crLayout.controls['radii/cover'].join('/') ].join() === 'radii/cover,radii/row,radii/column' && crLabelOf( controlOf( cr, 'radii/cover' ) ) === TEXT['/_admin/design/radii/section']
	&& crLabelOf( controlOf( cr, 'radii/card' ) ) === TEXT['/_admin/design/radii/card'] && crLabelOf( controlOf( cr, 'radii/image' ) ) === TEXT['/_admin/design/radii/image'] && crLabelOf( controlOf( cr, 'radii/tile' ) ) === TEXT['/_admin/design/radii/tile']
	&& controlOf( cr, 'radii/cover' ) === controlOf( cr, 'radii/row' ) && controlOf( cr, 'radii/row' ) === controlOf( cr, 'radii/column' ) && controlOf( cr, 'radii/card' ) !== controlOf( cr, 'radii/cover' )
	&& cardOf( controlOf( cr, 'radii/cover' ) ).dataset.object === 'section' && cardOf( controlOf( cr, 'radii/tile' ) ).dataset.object === 'tiles' && cardOf( controlOf( cr, 'radii/card' ) ).dataset.object === 'card' && cardOf( controlOf( cr, 'radii/image' ) ).dataset.object === 'page' );
check( '...and the two words, Standard and Alternative, in that order, each a button of the strip whose value is the word the setup keeps - the standard one pressed for every choice to begin with, nothing said under the name',
	crChoices.every( function( row ) {
		return byTag( row, 'button' ).length === 2 && byTag( row, 'button' ).map( function( button ) { return button.textContent } ).join() === RADIUS_WORDS.map( function( word ) { return TEXT['/_admin/design/radii/'+ word] } ).join()
			&& byTag( row, 'button' ).map( function( button ) { return button.dataset.value } ).join() === RADIUS_WORDS.join() && crLit( row ) === 'true,false'
			&& byTag( row, 'button' ).every( function( button ) { return button.title === button.textContent && button.disabled === false } ) && ( byTag( row, 'small' )[0] || {} ).textContent === '';
	} ) );

crPress( controlOf( cr, 'radii/cover' ), 'alternative' );

check( 'a press on the choice of the section writes the three keys the same - the cover, the row and the column on the word - and the card and the pictures stay on the standard: one choice, three keys',
	[ 'cover', 'row', 'column' ].every( function( kind ) { return cr.design._edit.radii[kind] === 'alternative' } ) && cr.design._edit.radii.card === 'standard' && cr.design._edit.radii.image === 'standard'
	&& crLit( controlOf( cr, 'radii/cover' ) ) === 'false,true' && crLit( controlOf( cr, 'radii/card' ) ) === 'true,false' && crLit( controlOf( cr, 'radii/image' ) ) === 'true,false'
	&& cr.byId('design-reset').hidden === false );

crPress( controlOf( cr, 'radii/cover' ), 'standard' );
crPress( controlOf( cr, 'radii/card' ), 'alternative' );
crPress( controlOf( cr, 'radii/image' ), 'alternative' );

check( '...and back: the three on the standard again, and the choice of a card or of the pictures writes its own key and no other',
	[ 'cover', 'row', 'column' ].every( function( kind ) { return cr.design._edit.radii[kind] === 'standard' } ) && cr.design._edit.radii.card === 'alternative' && cr.design._edit.radii.image === 'alternative'
	&& crLit( controlOf( cr, 'radii/cover' ) ) === 'true,false' && crLit( controlOf( cr, 'radii/card' ) ) === 'false,true' && crLit( controlOf( cr, 'radii/image' ) ) === 'false,true' );
check( '...each of the two words can be pressed for each choice, and each is what the model keeps, as the string it is',
	crChoices.every( function( row ) {
		return RADIUS_WORDS.slice().reverse().every( function( word ) {
			crPress( row, word );
			const keys = row.dataset.control.split(' ').map( function( key ) { return key.split('/')[1] } );
			return keys.every( function( kind ) { return cr.design._edit.radii[kind] === word } ) && crLit( row ) === RADIUS_WORDS.map( function( other ) { return String( other === word ) } ).join();
		} );
	} ) );

/*	The three kinds of a section can differ - a theme file says so, and the keys are kept separately. The choice reads as the word the three agree on; where
	they differ it shows the cover's and says in its note that they do, until it is chosen once: then they agree, and the note is gone	*/
const crMixed = panel( { radii : { cover : 'alternative', row : 'standard', column : 'alternative', card : 'standard', image : 'standard' } } );
crMixed.tab( tabWord('surfaces') );
const crMixedRow = controlOf( crMixed, 'radii/cover' );

check( 'where the three differ the choice shows the cover\'s word and says in its note that the three differ until it is set once - and the choices of the other kinds say nothing',
	crLit( crMixedRow ) === 'false,true' && ( byTag( crMixedRow, 'small' )[0] || {} ).textContent === TEXT['/_admin/design/hint/radii'] && TEXT['/_admin/design/hint/radii'] !== ''
	&& crMixed.design._edit.radii.row === 'standard' && ( byTag( controlOf( crMixed, 'radii/card' ), 'small' )[0] || {} ).textContent === '' );

crPress( crMixedRow, 'standard' );

check( '...a press sets all three the same, and the note is gone: nothing is left to say',
	[ 'cover', 'row', 'column' ].every( function( kind ) { return crMixed.design._edit.radii[kind] === 'standard' } ) && ( byTag( crMixedRow, 'small' )[0] || {} ).textContent === '' && crLit( crMixedRow ) === 'true,false' );

const crAgreed = panel( { radii : { cover : 'alternative', row : 'alternative', column : 'alternative', card : 'standard', image : 'standard' } } );
crAgreed.tab( tabWord('surfaces') );

check( '...and where the three agree the choice shows that word, with nothing under it',
	crLit( controlOf( crAgreed, 'radii/cover' ) ) === 'false,true' && ( byTag( controlOf( crAgreed, 'radii/cover' ), 'small' )[0] || {} ).textContent === ''
	&& crLit( controlOf( crAgreed, 'radii/card' ) ) === 'true,false' );

crPress( controlOf( cr, 'radii/cover' ), 'alternative' );
crPress( controlOf( cr, 'radii/card' ), 'standard' );
cr.design._previewNow();

check( 'a corner choice is a change like any other: the selection is marked as not stored, and the preview carries the three keys of the section the same, each kind with the word it has',
	cr.design._isDirty() === true && JSON.stringify( cr.requests[cr.requests.length - 1].payload.radii ) === JSON.stringify( cr.design._edit.radii )
	&& [ 'cover', 'row', 'column' ].every( function( kind ) { return cr.requests[cr.requests.length - 1].payload.radii[kind] === 'alternative' } ) && cr.requests[cr.requests.length - 1].payload.radii.card === 'standard' );

console.log('');


// --- The Motion view: the animation of the page and the hover --------------------------------------------
//
// Three blocks. The first is the page's, three selects - the effect, the strength and the speed: the animation belongs to the
// page, a block of its own under the word of the page, open to begin with and closable like every other, drawn afresh from the
// selection whenever it is opened. Closed, its head names the effect and the strength and the speed that are set. The other two
// are what a card does under the pointer and what a button does: a strip of three words each, in the block of their object

console.log( 'Motion' );

const mo = panel();
mo.tab( tabWord('motion') );

const effect = mo.byId('design-motion-effect');
const strength = mo.byId('design-motion-strength');
const speed = mo.byId('design-motion-speed');
const hover = mo.byId('design-motion-hover');
const hoverStrength = mo.byId('design-motion-hover-strength');

/** What a select of the animation offers: the default first, then a word for each value of the answer, taken from the texts by its key */
function motionOffer( key, values ) {
	return [ [ '', TEXT['/_admin/design/motion/default'] ] ].concat( values.map( function( value ) { return [ value, TEXT['/_admin/design/motion/'+ key+ '/'+ value] ] } ) );
}

const moFresh = panel();
moFresh.tab( tabWord('motion') );

check( 'the Motion view is the animation of the page, the hover of its pictures and what a card and a button do under the pointer: three blocks, the first open, with five selects - the effect, which starts on what Nino.css does by itself, the strength and the speed of the animation, the kind of hover and its strength - the controls the table gives the page there',
	tabOf( mo ) === 'motion' && mo.body().children.every( function( card ) { return hasClass( card, 'design-form-section' ) } ) && byTag( mo.body(), 'select' ).length === 5
	&& byClass( mo.byId('design-row-motion-page'), 'design-accordion-body' ).length === 1 && JSON.stringify( drawnRows( moFresh ) ) === JSON.stringify( tableRows( mo.design._data.layout, 'motion' ) )
	&& JSON.stringify( offered( effect ) ) === JSON.stringify( motionOffer( 'effect', mo.design._data.effects ) ) && mo.design._data.effects.length === 8
	&& JSON.stringify( offered( strength ) ) === JSON.stringify( motionOffer( 'strength', mo.design._data.strengths ) ) && mo.design._data.strengths.length === 3
	&& JSON.stringify( offered( speed ) ) === JSON.stringify( motionOffer( 'speed', mo.design._data.speeds ) ) && mo.design._data.speeds.length === 3
	&& JSON.stringify( offered( hover ) ) === JSON.stringify( motionOffer( 'hover', mo.design._data.hovers ) ) && mo.design._data.hovers.length === 4
	&& JSON.stringify( offered( hoverStrength ) ) === JSON.stringify( motionOffer( 'hover-strength', mo.design._data.strengths ) )
	&& shown( effect ) === '' && shown( hover ) === '' && shown( hoverStrength ) === '' && mo.byId('design-set-header') === null );
check( '...the two of the hover are named by the texts of their own keys - the label of each over its select, in the order of the table after the animation\'s three - and the five stand in one grid',
	byTag( mo.body(), 'label' ).map( function( label ) { return label.getAttribute('for') } ).join() === [ 'effect', 'strength', 'speed', 'hover', 'hover-strength' ].map( function( key ) { return 'design-motion-'+ key } ).join()
	&& byTag( mo.body(), 'label' ).map( function( label ) { return label.textContent } ).join() === [ 'effect', 'strength', 'speed', 'hover', 'hover-strength' ].map( function( key ) { return TEXT['/_admin/design/motion/'+ key+ '/label'] } ).join()
	&& byClass( mo.body(), 'design-form-grid' ).length === 1 && byTag( byClass( mo.body(), 'design-form-grid' )[0], 'select' ).length === 5 );
check( '...and the strength and the speed are switched off while there is no effect for them to belong to - the kind of hover is not, and the strength of the hover is, while it is none of the kinds that has one',
	strength.disabled === true && speed.disabled === true && effect.disabled === false && hover.disabled === false && hoverStrength.disabled === true );

choose( effect, 'blur' );

check( '...an effect switches them on', strength.disabled === false && speed.disabled === false && mo.design._edit.motion.effect === 'blur' );

choose( strength, 'hard' );
choose( speed, 'slow' );

check( '...and what they are set to is the selection',
	JSON.stringify( mo.design._edit.motion ) === JSON.stringify( { effect : 'blur', strength : 'hard', speed : 'slow', hover : '', 'hover-strength' : '' } ) );

mo.tab( tabWord('type') );
mo.tab( tabWord('motion') );

check( 'another tab and back, and the animation is drawn again from what is chosen: the choices are on the selects',
	tabOf( mo ) === 'motion' && shown( mo.byId('design-motion-effect') ) === 'blur' && shown( mo.byId('design-motion-strength') ) === 'hard' && shown( mo.byId('design-motion-speed') ) === 'slow' );

choose( mo.byId('design-motion-effect'), '' );

check( 'no effect again is Nino\'s default again, and the other two are switched off with it',
	mo.byId('design-motion-strength').disabled === true && mo.byId('design-motion-speed').disabled === true
	&& JSON.stringify( mo.design._edit.motion ) === JSON.stringify( { effect : '', strength : 'hard', speed : 'slow', hover : '', 'hover-strength' : '' } ) );

// The hover of the pictures: the kind switches the strength on, except the kind that is none and the empty one, which have none; the animation is not what they wait for
const mq = panel();
mq.tab( tabWord('motion') );

const mqHover = mq.byId('design-motion-hover');
const mqStrength = mq.byId('design-motion-hover-strength');
const mqKinds = mq.design._data.hovers;
const mqSleeps = mqKinds.map( function( kind ) { return choose( mqHover, kind ), String( mqStrength.disabled ) } ).join();

check( 'the strength of the hover is switched off while there is no kind of hover - and with the kind that is none, which has no strength: a kind that does something switches it on, whatever the animation is',
	mq.design._data.hovers.indexOf('none') !== -1 && mqSleeps === mqKinds.map( function( kind ) { return String( kind === 'none' ) } ).join() && mq.byId('design-motion-effect').disabled === false
	&& ( choose( mqHover, '' ), mqStrength.disabled === true ) && ( choose( mqHover, 'zoom' ), mqStrength.disabled === false ) && ( choose( mqHover, 'none' ), mqStrength.disabled === true ) && ( choose( mqHover, 'zoom-bright' ), mqStrength.disabled === false )
	&& mq.byId('design-motion-strength').disabled === true && mq.byId('design-motion-speed').disabled === true );

choose( mqStrength, 'hard' );
choose( mqHover, 'none' );

check( '...and what they are set to is the selection - the strength is kept where the kind is none, asleep, as the strength of an effect is kept where there is no effect - and the animation is left as it was',
	JSON.stringify( mq.design._edit.motion ) === JSON.stringify( { effect : '', strength : '', speed : '', hover : 'none', 'hover-strength' : 'hard' } ) && mqStrength.disabled === true );

mq.tab( tabWord('type') );
mq.tab( tabWord('motion') );

check( '...another tab and back, and the hover is drawn again from what is chosen - on the selects, the strength still asleep behind the kind that is none',
	shown( mq.byId('design-motion-hover') ) === 'none' && shown( mq.byId('design-motion-hover-strength') ) === 'hard' && mq.byId('design-motion-hover-strength').disabled === true
	&& ( choose( mq.byId('design-motion-hover'), 'bright' ), mq.byId('design-motion-hover-strength').disabled === false ) );

// The one block of the page: open to begin with, closable, remembered - and its head, closed, says what stands off the defaults
const mb = panel();
mb.show( 'motion' );
const mbBlock = function() { goTo( mb, 'page' ); return blocksOf( mb.form ).filter( function( card ) { return card.dataset.tab === 'motion' } )[0] };
const mbSaid = function() { return byClass( mbBlock(), 'design-accordion-summary' )[0].textContent };
const mbPart = function( key, value ) { return TEXT['/_admin/design/motion/'+ key+ '/label']+ ' '+ TEXT['/_admin/design/motion/'+ key+ '/'+ value] };

const mbWalked = [];
eachBlock( mb.form, function( card ) { mbWalked.push( [ card.dataset.object, byClass( headButton( card ), 'design-accordion-name' )[0].textContent, byTag( card, 'select' ).length ] ) } );

check( 'Motion is a block under the page, the card and the buttons, in that order: a head named by the word of its aspect, the page\'s open to begin with with the five selects in its body, and the other two drawn when they are opened',
	mbWalked.map( function( entry ) { return entry[0] } ).join() === tableObjects( listing().layout, 'motion' ).join() && tableObjects( listing().layout, 'motion' ).join() === 'page,card,buttons'
	&& mbWalked.every( function( entry ) { return entry[1] === tabWord('motion') } ) && mbWalked[0][2] === 5
	&& isOpen( mbBlock() ) === true && blocksOf( mb.form ).filter( isOpen ).length === 1 && mb.setup._blockOf( 'page', mb.setup._rows('page') ) === 'motion' );

toggle( mbBlock() );

check( '...closable like every other block: closed it has no body and none of its selects in the document, and its head says nothing at the defaults',
	isOpen( mbBlock() ) === false && byClass( mb.form, 'design-accordion-body' ).length === 0 && mb.byId('design-motion-effect') === null && mbSaid() === '' && mb.setup._open.page === null );
check( '...and remembered while the panel lives, as the blocks of the other tabs are: another object and back and it is closed still, and opened again it is the block that was open',
	( mb.go( 'card' ), mb.go( 'page' ), isOpen( mbBlock() ) === false && mb.byId('design-motion-effect') === null ) && ( toggle( mbBlock() ), isOpen( mbBlock() ) === true && mb.byId('design-motion-effect') !== null && mb.setup._open.page === 'motion' ) );

choose( mb.byId('design-motion-effect'), 'blur' );
toggle( mbBlock() );

check( 'a head that is closed names the effect that was chosen - the label of the select and the word of the value, from the texts of their keys - and the strength and the speed it says nothing of while they stand at what Nino.css does by itself',
	mbSaid() === mbPart( 'effect', 'blur' ) && byClass( mbBlock(), 'design-accordion-summary' )[0].title === mbPart( 'effect', 'blur' ) && mb.design._edit.motion.strength === '' && mb.design._edit.motion.speed === '' );

toggle( mbBlock() );
choose( mb.byId('design-motion-strength'), 'hard' );
choose( mb.byId('design-motion-speed'), 'slow' );
toggle( mbBlock() );

check( '...and the strength and the speed once they are chosen, each as its label and its word, in the order of the table, joined by a middle dot',
	mbSaid() === [ mbPart( 'effect', 'blur' ), mbPart( 'strength', 'hard' ), mbPart( 'speed', 'slow' ) ].join( acSeparator ) );

toggle( mbBlock() );
choose( mb.byId('design-motion-effect'), '' );
toggle( mbBlock() );

check( '...with no effect there is nothing for them to belong to: the head says nothing, though the selection keeps the strength and the speed that were set, which their selects - switched off - do not offer',
	mbSaid() === '' && mb.design._edit.motion.strength === 'hard' && mb.design._edit.motion.speed === 'slow' );

// ...and the hover of the pictures after the animation, in the order of the table: the kind, and the strength once the kind has one
toggle( mbBlock() );
choose( mb.byId('design-motion-hover'), 'zoom-bright' );
toggle( mbBlock() );

const mbKind = mbSaid();

toggle( mbBlock() );
choose( mb.byId('design-motion-hover-strength'), 'soft' );
toggle( mbBlock() );

const mbBoth = mbSaid();

toggle( mbBlock() );
choose( mb.byId('design-motion-effect'), 'blur' );
toggle( mbBlock() );

const mbAll = mbSaid();

toggle( mbBlock() );
choose( mb.byId('design-motion-hover'), 'none' );
toggle( mbBlock() );

const mbNone = mbSaid();

toggle( mbBlock() );
choose( mb.byId('design-motion-hover'), '' );
toggle( mbBlock() );

check( 'a head that is closed names the kind of hover that was chosen - the label of the select and the word of the value - and its strength once it is chosen, after the animation\'s in the order of the table: the strength it says nothing of while the kind has none',
	mbKind === mbPart( 'hover', 'zoom-bright' ) && mbBoth === [ mbPart( 'hover', 'zoom-bright' ), mbPart( 'hover-strength', 'soft' ) ].join( acSeparator )
	&& mbAll === [ mbPart( 'effect', 'blur' ), mbPart( 'strength', 'hard' ), mbPart( 'speed', 'slow' ), mbPart( 'hover', 'zoom-bright' ), mbPart( 'hover-strength', 'soft' ) ].join( acSeparator ) );
check( '...the kind that is none says itself and not the strength, which the selection keeps, asleep; and with no kind the head says nothing of the hover',
	mbNone === [ mbPart( 'effect', 'blur' ), mbPart( 'strength', 'hard' ), mbPart( 'speed', 'slow' ), mbPart( 'hover', 'none' ) ].join( acSeparator ) && mb.design._edit.motion['hover-strength'] === 'soft'
	&& mbSaid() === [ mbPart( 'effect', 'blur' ), mbPart( 'strength', 'hard' ), mbPart( 'speed', 'slow' ) ].join( acSeparator ) );
toggle( mbBlock() );
choose( mb.byId('design-motion-effect'), '' );
toggle( mbBlock() );

// What a card and a button do under the pointer: a strip of three words in the block of its object
const mh = panel();
mh.show( 'motion' );
const mhBlock = function( object ) { goTo( mh, object ); return blocksOf( mh.form ).filter( function( card ) { return card.dataset.tab === 'motion' } )[0] };
// What the head says once the block is closed
const mhSaid = function( object ) { if( isOpen( mhBlock( object ) ) === true ) toggle( mhBlock( object ) ); return byClass( mhBlock( object ), 'design-accordion-summary' )[0].textContent };

pressKey( mh, 'knobs/card/hover', 1 );

const mhCardPressed = pressedKey( mh, 'knobs/card/hover' );
const mhCardWords = byTag( controlOf( mh, 'knobs/card/hover' ), 'button' ).map( function( button ) { return button.textContent } ).join();

pressKey( mh, 'knobs/buttons/hover', -1 );

check( 'the hover of a card and of a button is a strip of three words each, in the block of its object: the middle one lit to begin with, the words those of the knob in the texts, and a press stores the position as a number - each object has its own block open',
	mhCardPressed === 'false,false,true' && mhCardWords === [ 'less', 'default', 'more' ].map( function( word ) { return TEXT['/_admin/design/knob/card/hover/'+ word] } ).join()
	&& mh.design._edit.knobs.card.hover === 1 && mh.design._edit.knobs.buttons.hover === -1 && pressedKey( mh, 'knobs/buttons/hover' ) === 'true,false,false'
	&& isOpen( mhBlock('buttons') ) === true && isOpen( mhBlock('card') ) === true );
check( '...and a block that is closed names it in its head - the label of the knob and the word of the position, from the texts of their keys - while the head of the page, which nobody moved, says nothing',
	mhSaid('card') === knobWord( 'card', 'hover' )+ ' '+ TEXT['/_admin/design/knob/card/hover/more'] && ( mhSaid('buttons') === knobWord( 'buttons', 'hover' )+ ' '+ TEXT['/_admin/design/knob/buttons/hover/less'] )
	&& mhSaid('page') === '' );
check( '...and a change of the hover is looked at in the place of its object: the card for the card and the buttons for the button',
	( pressKey( mh, 'knobs/card/hover', -1 ), mh.design._target === 'cards' ) && ( pressKey( mh, 'knobs/buttons/hover', 1 ), mh.design._target === 'buttons' ) );

console.log('');


// --- The loader and the views ----------------------------------------------------------
//
// The panel's own screen is the list of the themes and the setup in six views, in one mount; the loader of the
// typefaces is a tab of its own, with its own mount. The list is checked with the themes, further down

console.log( 'The loader and the views' );

// The Type view has its rows, and the loader is a tab of its own
const scrFonts = panel();
scrFonts.show( 'type' );

check( 'the Type view opens on the blocks of its objects, the first open with the root size and the table of the five roles in it, and none of the loader\'s cards',
	tabOf( scrFonts ) === 'type' && screenOf( scrFonts ) === 'type' && JSON.stringify( drawnRows( scrFonts ) ) === JSON.stringify( tableRows( scrFonts.design._data.layout, 'type' ) )
	&& scrFonts.body().children.every( function( child ) { return hasClass( child, 'design-accordion' ) } ) && byTag( scrFonts.form, 'table' ).length === 1
	&& scrFonts.byId('design-faces-installed') === null && scrFonts.byId('design-faces-available') === null );

scrFonts.show( 'typefaces' );

const scrFaces = scrFonts.forms['design-typefaces'];

check( 'the tab Typefaces is the loader: the line it says what an action did in, empty, and the two cards of the typefaces - those that are installed and those on offer - in its own mount, with no bar over them, no block and no control of the table, and the table of the roles is not in it',
	tabOf( scrFonts ) === 'typefaces' && screenOf( scrFonts ) === 'typefaces' && scrFaces.children.map( function( child ) { return child.id || child.className } ).join() === 'design-faces-msg,design-faces-cards'
	&& scrFonts.byId('design-faces-msg').textContent === '' && scrFonts.byId('design-faces-msg').getAttribute('aria-live') === 'polite'
	&& byClass( scrFaces, 'design-faces-cards' )[0].children.map( function( card ) { return card.id } ).join() === 'design-faces-installed,design-faces-available'
	&& byClass( scrFaces, 'design-faces-cards' )[0].children.every( function( card ) { return hasClass( card, 'nino-admin-card' ) } ) && blocksOf( scrFaces ).length === 0 && byClass( scrFaces, 'design-form-section' ).length === 0
	&& descendants( scrFaces ).every( function( el ) { return el.dataset.control === undefined } ) && byTag( scrFaces, 'table' ).length === 0 );
check( '...the loader draws neither the preview nor the bar: no frame, no layout, no strip, no button of the bar, no line of it - those stand in the screens that carry them, and the loader is no view of the setup: opening it changes nothing of the selection, asks for nothing but the list the server kept, and leaves the bar of the screen that has one as it is - the way back is not shown',
	[ 'design-layout', 'design-preview', 'design-frame', 'design-save', 'design-apply', 'design-reset', 'design-msg', 'design-state-word', 'design-controls', 'design-body' ].every( function( id ) { return descendants( scrFaces ).filter( function( el ) { return el.id === id } ).length === 0 } )
	&& byClass( scrFaces, 'nino-admin-actionbar' ).length === 0 && byClass( scrFaces, 'design-tabs' ).length === 0
	&& scrFonts.design._isDirty() === false && scrFonts.byId('design-reset').hidden === true && scrFonts.requests.map( function( request ) { return request.endpoint } ).join() === 'webfonts/list' );

// The views have no strip in the column: the bar of the views is the pane's, and the column holds cards
check( 'every view of the setup has its blocks, or the card of the css, as the whole of the column, the first thing in its box a card - and the bar of the views is not in the column but at the top of the pane',
	TABS.every( function( key ) {
		scrFonts.show( key );
		return scrFonts.body().children.length > 0 && scrFonts.body().children.every( function( child ) { return hasClass( child, 'nino-admin-card' ) } ) && byClass( scrFonts.body(), 'design-tabs' ).length === 0;
	} ) && byClass( scrFonts.pane, 'design-tabs' ).length === 1 && byClass( scrFonts.pane, 'design-tabs' )[0].parent === scrFonts.byId('admin-tab-design') );

console.log('');


// --- Fonts: the five roles in a table ------------------------------------------------
//
// Five rows and four columns - the family, the weight, the case and the size.
// The controls stand bare in the cells and the words are in the column heads;
// each control still carries its role and its column as its accessible name

console.log( 'Fonts' );

const fonts = panel();
fonts.tab( tabWord('type') );

const table = byTag( fonts.form, 'table' )[0] || element('table');
const fontRows = byTag( table, 'tbody' )[0] ? byTag( byTag( table, 'tbody' )[0], 'tr' ) : [];

check( 'the Page card of Type holds one table of the roles, said to be one: a header of four columns - the role and the family, the weight and the size - over five rows, one for each role of Nino.css',
	tabOf( fonts ) === 'type' && cardOf( table ).dataset.object === 'page' && cardOf( table ).dataset.tab === 'type' && table.getAttribute('role') === 'table'
	&& byTag( byTag( table, 'thead' )[0], 'th' ).map( function( th ) { return th.textContent } ).join() === ','+ [ 'family', 'weight', 'size' ].map( columnWord ).join()
	&& byTag( byTag( table, 'thead' )[0], 'th' ).every( function( th ) { return th.getAttribute('role') === 'columnheader' } )
	&& fontRows.map( function( row ) { return row.dataset.role } ).join() === 'title,subtitle,text,button,code'
	&& fontRows.map( function( row ) { return byTag( row, 'th' )[0].textContent } ).join() === [ 'title', 'subtitle', 'text', 'button', 'code' ].map( roleWord ).join()
	&& fontRows.every( function( row ) { return row.getAttribute('role') === 'row' && byTag( row, 'th' )[0].getAttribute('role') === 'rowheader' } ) );

const family = function( role ) { return fonts.byId('design-font-'+ role+ '-family') };
const weight = function( role ) { return fonts.byId('design-font-'+ role+ '-weight') };
const size = function( role ) { return fonts.byId('design-font-'+ role+ '-size') };

check( 'a family is a select of what the role may be given: the system\'s own first, a webface by its name, a system stack in the words of the panel',
	JSON.stringify( offered( family('title') ) ) === JSON.stringify( [ [ '', familyWord('default') ], [ 'open-sans', 'Open Sans' ],
		[ 'system-sans', familyWord('system-sans') ], [ 'system-serif', familyWord('system-serif') ], [ 'system-mono', familyWord('system-mono') ] ] )
	&& shown( family('title') ) === 'open-sans' && shown( family('text') ) === 'open-sans' );
check( '...the code is offered the system\'s monospace and nothing else, by its own name',
	JSON.stringify( offered( family('code') ) ) === JSON.stringify( [ [ '', familyWord('default-code') ] ] ) && shown( family('code') ) === '' );
check( 'a weight is one of three words',
	JSON.stringify( offered( weight('title') ) ) === JSON.stringify( [ [ 'light', weightWord('light') ], [ 'normal', weightWord('normal') ], [ 'bold', weightWord('bold') ] ] ) && shown( weight('title') ) === 'bold' && shown( weight('code') ) === 'normal' );
check( 'a size is typed and not picked: a number field in each row, stepping by a tenth, with the limits of the role',
	fontRows.every( function( row ) {
		const input = size( row.dataset.role );
		return input !== null && input.tagName === 'INPUT' && input.type === 'number' && input.getAttribute('step') === '.1' && input.getAttribute('inputmode') === 'decimal'
			&& input.dataset.role === row.dataset.role && input.dataset.key === 'size';
	} )
	&& fonts.design._tabKeys().length === TABS.length
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).getAttribute('min')+ '-'+ size( role ).getAttribute('max') } ).join() === '1-4,0.8-2.5,0.8-1.5,0.6-1.5,0.6-1.3'
	&& byTag( table, 'select' ).filter( function( select ) { return select.dataset.key === 'size' } ).length === 0 );
check( '...and its value is the size that is in force: the one the listing says is in force while none is chosen - 1.6, 1.125, 1, 1 and 0.875 - as the value and not as a placeholder, so that the spinner has a number to count from; the model keeps the empty size it had',
	[ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) { return size( role ).value } ).join() === '1.6,1.125,1,1,0.875'
	&& [ 'title', 'subtitle', 'text', 'button', 'code' ].every( function( role ) { return size( role ).getAttribute('placeholder') === null && fonts.design._edit.fonts[role].size === '' } ) );
check( '...and the unit stands after it as a small word of its own, in the same cell',
	fontRows.every( function( row ) {
		const cell = size( row.dataset.role ).parent;
		const unit = byClass( cell, 'design-fonts-unit' )[0];
		return cell.tagName === 'TD' && hasClass( cell, 'design-fonts-size' ) && unit !== undefined && unit.textContent === TEXT['/_admin/design/fontsize/unit']
			&& cell.children.indexOf( unit ) === cell.children.indexOf( size( row.dataset.role ) ) + 1;
	} ) );
check( '...every control names itself by its role and its column, since the cells carry no words',
	weight('title').getAttribute('aria-label') === roleWord('title')+ ' - '+ columnWord('weight') && family('code').getAttribute('aria-label') === roleWord('code')+ ' - '+ columnWord('family')
	&& size('button').getAttribute('aria-label') === roleWord('button')+ ' - '+ columnWord('size') );
check( 'a role has no case: no checkbox anywhere in the table - the three cells of a row are the family, the weight and the size',
	byTag( table, 'input' ).filter( function( input ) { return input.type === 'checkbox' } ).length === 0
	&& fontRows.every( function( row ) { return byTag( row, 'td' ).length === 3 && byTag( row, 'td' ).map( function( td ) { return byTag( td, 'select' ).concat( byTag( td, 'input' ) )[0].dataset.key } ).join() === 'family,weight,size' } ) );

/*	Changing one thing of one role: through _setFont(), which stores it and
	asks for a preview - and the preview carries the role	*/
const setFontCalls = [];
const setFont = fonts.setup._setFont;
fonts.setup._setFont = function( role, key, value ) { setFontCalls.push( [ role, key, value ] ); return setFont.call( fonts.design, role, key, value ) };

choose( family('title'), 'system-serif' );
choose( weight('title'), 'light' );
type( size('title'), '2.4' );

choose( family('subtitle'), '' );

check( 'a change goes through _setFont() with the role, the key and the value - the words the model keeps',
	JSON.stringify( setFontCalls ) === JSON.stringify( [ [ 'title', 'family', 'system-serif' ], [ 'title', 'weight', 'light' ], [ 'title', 'size', '2.4' ], [ 'subtitle', 'family', '' ] ] )
	&& JSON.stringify( fonts.design._edit.fonts.title ) === JSON.stringify( { family : 'system-serif', weight : 'light', size : '2.4' } )
	&& fonts.design._edit.fonts.subtitle.family === '' && fonts.byId('design-reset').hidden === false );
fonts.tab( tabWord('colours') );
fonts.tab( tabWord('type') );

check( 'the table drawn again shows each role as it was set, in words: the family, the weight and the size - the one that was typed, else the one the listing says is in force',
	[ 'title', 'subtitle', 'text', 'button', 'code' ].map( function( role ) {
		return [ wordOf( family( role ) ), wordOf( weight( role ) ), size( role ).value ].join(', ');
	} ).join(' | ')
		=== [
			[ familyWord('system-serif'), weightWord('light'), '2.4' ], [ familyWord('default'), weightWord('normal'), '1.125' ],
			[ 'Open Sans', weightWord('normal'), '1' ], [ 'Open Sans', weightWord('bold'), '1' ], [ familyWord('default-code'), weightWord('normal'), '0.875' ],
		].map( function( cells ) { return cells.join(', ') } ).join(' | ')
	&& fonts.byId('design-summary') === null );

fonts.design._previewNow();
const fontPreview = fonts.requests[fonts.requests.length - 1];

check( 'the preview asked for carries the role that was changed, with the rest of the selection beside it - the frame is the answer to every control',
	fontPreview.endpoint === 'preview' && JSON.stringify( fontPreview.payload.fonts.title ) === JSON.stringify( { family : 'system-serif', weight : 'light', size : '2.4' } )
	&& Object.keys( fontPreview.payload ).sort().join() === 'colours,css,features,fonts,full,knobs,motion,navs,parts,radii,radius,radius-alt,size,styles,theme,tiers,whitespace,width' && fontPreview.payload.full === true );

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
forced.tab( tabWord('type') );

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
spun.tab( tabWord('type') );
const spunTitle = spun.byId('design-font-title-size');

spin( spunTitle );

check( 'the spinner counts from the size in force: from 1.6 it goes up to 1.7 - not from the least of the role, 1, which is where an empty field starts - and what it reached is chosen',
	spunTitle.value === '1.7' && spun.design._edit.fonts.title.size === '1.7' );

console.log('');


// --- Type: the case of a thing, a switch under the sizes of its object ----------------------------------
//
// The eight toggles of the model - the navigation of the header and of the footer, the title and the subtitle of a section and of a card, the
// buttons, the labels of a form - are the workbench's switch, one for each, on the Type view under the sizes of their object: on at the position 1, off at 0, and
// greyed out and switched off like any knob where the chosen template does not answer to it. The closed head of the block says one that is on

console.log( 'The case switches' );

const sw = panel();

sw.tab( tabWord('type') );

const swOrder = {};

drawnRows( sw ).forEach( function( row ) { swOrder[row.object] = row.controls } );

check( 'the nine switches stand on the Type view, each under the sizes of its object and after them: the navigation of the header and of the footer, the title and the subtitle of a section and of a card, the buttons, the labels of a form, the weight of the titles of an accordion',
	JSON.stringify( swOrder.header ) === JSON.stringify( [ 'knobs/header/nav', 'knobs/header/nav-case', 'knobs/header/menu-size', 'knobs/header/menu-weight', 'knobs/header/menu-tracking' ] ) && JSON.stringify( swOrder.footer ) === JSON.stringify( [ 'knobs/footer/scale', 'knobs/footer/nav-case' ] )
	&& JSON.stringify( swOrder.section ) === JSON.stringify( [ 'knobs/section/title', 'knobs/section/subtitle', 'knobs/section/quiet', 'knobs/section/loud', 'knobs/section/title-tracking', 'knobs/section/title-case', 'knobs/section/subtitle-case' ] )
	&& JSON.stringify( swOrder.card ) === JSON.stringify( [ 'knobs/card/title', 'knobs/card/subtitle', 'knobs/card/title-tracking', 'knobs/card/title-case', 'knobs/card/subtitle-case' ] )
	&& JSON.stringify( swOrder.buttons ) === JSON.stringify( [ 'knobs/buttons/size', 'knobs/buttons/spread', 'knobs/buttons/tracking', 'knobs/buttons/case' ] ) && JSON.stringify( swOrder.forms ) === JSON.stringify( [ 'knobs/forms/scale', 'knobs/forms/label-case' ] )
	&& JSON.stringify( swOrder.accordion ) === JSON.stringify( [ 'knobs/accordion/scale', 'knobs/accordion/weight' ] )
	&& knobRows( sw ).filter( isSwitch ).length === 9 && knobRows( sw ).filter( isSwitch ).every( function( row ) { return LAYOUT[ row.dataset.control ][0] === 'type' } ) );

const swKeys = knobRows( sw ).filter( isSwitch ).map( function( row ) { return row.dataset.control } );
const swInput = function( key ) { return byTag( controlOf( sw, key ), 'input' )[0] };
const swState = function( key ) { return byClass( controlOf( sw, key ), 'nino-admin-switch-state' )[0].textContent };

check( 'a switch is the workbench\'s own: a checkbox with the label of the knob, its note for the hint and the word of its state after it - off to begin with, since the defaults put every one of the nine at 0',
	swKeys.length === 9 && swKeys.every( function( key ) {
		const at = key.split('/');
		return swInput( key ).type === 'checkbox' && swInput( key ).checked === false && swState( key ) === TEXT['/_admin/common/label/off'] && swInput( key ).disabled === false
			&& hasClass( controlOf( sw, key ), 'design-knob-row' ) && hasClass( controlOf( sw, key ), 'design-knob-row--off' ) === false
			&& rowName( controlOf( sw, key ) ) === knobWord( at[1], at[2] ) && byTag( controlOf( sw, key ), 'small' )[0].textContent === TEXT['/_admin/design/knob/'+ at[1]+ '/'+ at[2]+ '/note'];
	} ) && sw.design._data.defaults.knobs.section['title-case'] === 0 );

// On: the knob is 1, a number, the word of the switch says it, a change is a change like any other
swInput('knobs/section/title-case').checked = true;
swInput('knobs/section/title-case').fire('change');

check( 'turned on, a switch moves its knob to 1 - a number - and no other knob: the title of a section is in capitals, the subtitle is not, and the sizes stand where they stood',
	sw.design._edit.knobs.section['title-case'] === 1 && typeof sw.design._edit.knobs.section['title-case'] === 'number' && sw.design._edit.knobs.section['subtitle-case'] === 0
	&& sw.design._edit.knobs.section.title === 0 && swState('knobs/section/title-case') === TEXT['/_admin/common/label/on'] && swState('knobs/section/subtitle-case') === TEXT['/_admin/common/label/off']
	&& sw.byId('design-reset').hidden === false && sw.design._target === 'content' );

sw.design._previewNow();

const swPreviewOn = sw.requests[sw.requests.length - 1];

check( '...and a preview is asked for with the knob at 1 in the selection it carries, the rest of the selection beside it',
	swPreviewOn.endpoint === 'preview' && swPreviewOn.payload.knobs.section['title-case'] === 1 && swPreviewOn.payload.knobs.section['subtitle-case'] === 0 && swPreviewOn.payload.knobs.card['title-case'] === 0 );

swInput('knobs/section/title-case').checked = false;
swInput('knobs/section/title-case').fire('change');
sw.design._previewNow();

const swPreviewOff = sw.requests[sw.requests.length - 1];

check( 'turned off again, it is 0 - and the preview carries 0, the selection is the one it was and there is nothing to save',
	sw.design._edit.knobs.section['title-case'] === 0 && swPreviewOff.payload.knobs.section['title-case'] === 0 && swState('knobs/section/title-case') === TEXT['/_admin/common/label/off']
	&& sw.design._isDirty() === false );

// The closed head says what is on, as it says what stands elsewhere than at the defaults: the label of the switch and the word of its state
sw.design._edit.knobs.section['title-case'] = 0;
swInput('knobs/card/title-case').checked = true;
swInput('knobs/card/title-case').fire('change');
swInput('knobs/card/subtitle-case').checked = true;
swInput('knobs/card/subtitle-case').fire('change');
swInput('knobs/buttons/case').checked = true;
swInput('knobs/buttons/case').fire('change');
sw.tab( tabWord('surfaces') );
sw.tab( tabWord('type') );
// The head of the block that is open says nothing, the controls themselves being in view: every block is closed
blocksOf( sw.form ).filter( isOpen ).forEach( toggle );

const swLine = function( object ) { return closedLine( sw, object ) };

check( 'the closed head of a block names a switch that is on by its label and the word of its state, after the sizes that were moved, and a block whose switches are off says nothing of them',
	swLine('card') === knobWord( 'card', 'title-case' )+ ' '+ TEXT['/_admin/common/label/on']+ ' · '+ knobWord( 'card', 'subtitle-case' )+ ' '+ TEXT['/_admin/common/label/on']
	&& swLine('buttons') === knobWord( 'buttons', 'case' )+ ' '+ TEXT['/_admin/common/label/on']
	&& swLine('section') === '' && swLine('header') === '' && swLine('footer') === '' );

// ...and a template that does not answer to it: the switch is off, switched off and grey, the note says whose decision that is - a position the old template held goes back to 0
const swOff = panel();

swOff.tab( tabWord('templates') );
swOff.design._edit.knobs.card['title-case'] = 1;
swOff.design._edit.knobs.section['subtitle-case'] = 1;
choose( setSelect( swOff, 'card' ), 'second' );
choose( setSelect( swOff, 'section' ), 'second' );
swOff.tab( tabWord('type') );

const swOffRow = function( key ) { return controlOf( swOff, key ) };

check( 'where the chosen template does not answer to a switch it is a row greyed out and not hidden: the switch is off and switched off, the line under its name says whose decision that is in place of the note, and a position the earlier template held is 0 again',
	[ 'knobs/card/title-case', 'knobs/card/subtitle-case', 'knobs/section/title-case', 'knobs/section/subtitle-case' ].every( function( key ) {
		const at = key.split('/');
		const row = swOffRow( key );
		return row !== null && isSwitch( row ) && hasClass( row, 'design-knob-row--off' ) && byTag( row, 'input' )[0].disabled === true && byTag( row, 'input' )[0].checked === false
			&& byTag( row, 'small' )[0].textContent === say( '/_admin/design/hint/unanswered', knobWord( at[1], at[2] ) ) && swOff.design._edit.knobs[ at[1] ][ at[2] ] === 0;
	} )
	&& hasClass( swOffRow('knobs/buttons/case'), 'design-knob-row--off' ) === false && byTag( swOffRow('knobs/buttons/case'), 'input' )[0].disabled === false
	&& hasClass( swOffRow('knobs/card/title'), 'design-knob-row--off' ) === false && offRows( swOff ).length === 4 );
check( '...and a row that is greyed out says nothing in the head of its block, and the template that answers again takes the grey away - the switch is off, as it was left',
	byClass( lensBlock( swOff, 'card' ), 'design-accordion-summary' )[0].textContent.indexOf( knobWord( 'card', 'title-case' ) ) === -1
	&& ( swOff.tab( tabWord('templates') ), choose( setSelect( swOff, 'card' ), 'first' ), swOff.tab( tabWord('type') ), offRows( swOff ).length === 2 )
	&& byTag( swOffRow('knobs/card/title-case'), 'input' )[0].disabled === false && byTag( swOffRow('knobs/card/title-case'), 'input' )[0].checked === false
	&& swOff.design._edit.knobs.card['title-case'] === 0 );

// The model can hold a 1 for a knob the template does not answer to - a file written by hand, a theme that was imported - and the switch does not light it: a thing the page does not do is not on
const swHeld = listing();

swHeld.knobs.card['title-case'] = 1;
swHeld.parts.filter( function( row ) { return row.part === 'card' } )[0].set = 'second';

const swHeldPanel = panel( swHeld );

swHeldPanel.tab( tabWord('type') );

check( 'a switch whose knob holds 1 while the template chosen does not answer to it is off and switched off, and the head of its block does not say it is on',
	byTag( controlOf( swHeldPanel, 'knobs/card/title-case' ), 'input' )[0].checked === false && byTag( controlOf( swHeldPanel, 'knobs/card/title-case' ), 'input' )[0].disabled === true
	&& swHeldPanel.design._edit.knobs.card['title-case'] === 1 && ( blocksOf( swHeldPanel.form ).filter( isOpen ).forEach( toggle ), byClass( lensBlock( swHeldPanel, 'card' ), 'design-accordion-summary' )[0].textContent.indexOf( knobWord( 'card', 'title-case' ) ) === -1 ) );

console.log('');


// --- Type: the quiet and the loud variant ----------------------------------------------------------
//
// Two knobs of the section set the size of type, the text of a quiet and of a loud title. The table puts them with the other sizes of a
// section: the last two rows of the card of the section on Type, strips like every knob - one function draws them - so they read and write
// the same values of the setup, preview as the others do, are looked at in the first section of the page, with the other knobs of the section,
// and grey out where the template of the sections declares no ladder for them

console.log( 'The variants' );

const varied = panel();
varied.tab( tabWord('type') );

const variedName = function( knob ) { return knobWord( 'section', knob ) };
const variedRows = [ 'quiet', 'loud' ].map( function( knob ) { return controlOf( varied, 'knobs/section/'+ knob ) } );
const variedBody = variedRows[0] === null ? null : variedRows[0].parent;
const variedCard = variedBody === null ? null : cardOf( variedBody );

check( 'the quiet and the loud variant are the sizes of the block of the section on Type that come last, in that order, named by the texts of their keys, and the letter-spacing of the title and the two switches of its case follow them - the table puts them there with the other sizes of a section',
	variedCard !== null && variedRows[1].parent === variedBody && variedCard.dataset.object === 'section' && variedCard.dataset.tab === 'type'
	&& variedBody.children[variedBody.children.length - 5] === variedRows[0] && variedBody.children[variedBody.children.length - 4] === variedRows[1] && variedBody.children[variedBody.children.length - 3].dataset.control === 'knobs/section/title-tracking'
	&& variedBody.children.slice( -2 ).every( function( row ) { return isSwitch( row ) } ) && variedBody.children.slice( -2 ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/section/title-case,knobs/section/subtitle-case'
	&& variedRows.map( rowName ).join() === [ 'quiet', 'loud' ].map( variedName ).join() );
check( '...each a strip of five positions signed from −2 to +2 like every scale, the middle one lit',
	[ 'quiet', 'loud' ].every( function( knob ) {
		return varied.steps( variedName( knob ) ).map( function( button ) { return button.textContent } ).join() === SIGNED.join() && varied.pressed( variedName( knob ) ) === 'false,false,true,false,false';
	} ) );

varied.move( variedName('quiet'), 1 );
varied.move( variedName('loud'), -2 );

check( 'a press stores the number it is labelled with in the setup of the section, where the other knobs of it are - the quiet variant +1, the loud one −2 - and lights that button alone, the knobs round them where they were',
	varied.design._edit.knobs.section.quiet === 1 && varied.design._edit.knobs.section.loud === -2 && varied.design._edit.knobs.section.title === 0 && varied.design._edit.knobs.card.shadow === 0
	&& varied.pressed( variedName('quiet') ) === 'false,false,false,true,false' && varied.pressed( variedName('loud') ) === 'true,false,false,false,false' );
check( '...and it is a change like any other: the way back is shown, a preview is asked for after the debounce, and it is looked at in the first section of the page, where the loud variant sizes the hero',
	varied.design._isDirty() === true && varied.byId('design-reset').hidden === false && varied.waiting( 350 ).length === 1 && varied.design._target === 'content' );

varied.design._previewNow();
const variedPreview = varied.requests[varied.requests.length - 1];

check( '...the preview it asks for carries both, with the rest of the selection beside them',
	variedPreview.endpoint === 'preview' && variedPreview.payload.knobs.section.quiet === 1 && variedPreview.payload.knobs.section.loud === -2 && variedPreview.payload.knobs.section.title === 0 );

varied.tab( tabWord('surfaces') );
openBlock( lensBlock( varied, 'section' ) );
const variedOnSurfaces = descendants( lensBlock( varied, 'section' ) ).filter( function( el ) { return el.dataset.control !== undefined && /knobs\/section\/(quiet|loud)/.test( el.dataset.control ) } );
varied.tab( tabWord('type') );

check( 'the positions are the setup\'s and not the screen\'s: another tab and back, and the rows are drawn anew from the setup - +1 and −2 - while Surfaces has a row for neither',
	variedOnSurfaces.length === 0 && controlOf( varied, 'knobs/section/quiet' ) !== variedRows[0]
	&& varied.pressed( variedName('quiet') ) === 'false,false,false,true,false' && varied.pressed( variedName('loud') ) === 'true,false,false,false,false' );

varied.show( 'typefaces' );
const variedOnInstall = descendants( varied.faceBox() ).filter( function( el ) { return el.dataset.control !== undefined } );
varied.show( 'type' );

check( '...the rows belong to the Type view and to no other: the loader has none of them, and the Type view has them again, with the positions',
	variedOnInstall.length === 0 && controlOf( varied, 'knobs/section/quiet' ) !== null && varied.pressed( variedName('quiet') ) === 'false,false,false,true,false' );

/*	A template that declares no ladder for them: the library of somebody else's, whose third template of the sections leaves the two out. Chosen on
	the Templates view, it puts their positions back in the middle (the server holds them there anyway) and the rows on Type are greyed out, not hidden	*/
const noLadders = listing().parts;
noLadders.filter( function( one ) { return one.part === 'section' } )[0].catalogue.third.knobs = without( KNOBS.section, [ 'quiet', 'loud' ] );

const greyed = panel( { parts : noLadders } );
greyed.tab( tabWord('type') );
greyed.move( variedName('quiet'), 2 );
const greyedBefore = [ greyed.design._edit.knobs.section.quiet, offRows( greyed ).length ];

greyed.tab( tabWord('templates') );
choose( setSelect( greyed, 'section' ), 'third' );
greyed.tab( tabWord('type') );
const greyedRows = [ 'quiet', 'loud' ].map( function( knob ) { return controlOf( greyed, 'knobs/section/'+ knob ) } );

check( 'where the template of the sections declares no ladder for them the two rows are greyed out and not hidden - the same rows of the same card, every button switched off and none pressed, the line under the name saying whose decision that is',
	greyedBefore.join() === '2,0' && greyedRows.every( function( row ) { return row !== null } ) && greyedRows.every( function( row ) {
		return hasClass( row, 'design-knob-row--off' ) && byTag( row, 'button' ).length === 5 && byTag( row, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' }
			) && ( byTag( row, 'small' )[0] || {} ).textContent === say( '/_admin/design/hint/unanswered', rowName( row ) );
	} ) && offRows( greyed ).length === 2 );
check( '...and choosing that template put what was moved back in the middle, the model as the server holds it',
	greyed.design._edit.knobs.section.quiet === 0 && greyed.design._edit.knobs.section.loud === 0 );

greyed.tab( tabWord('templates') );
choose( setSelect( greyed, 'section' ), 'first' );
greyed.tab( tabWord('type') );

check( '...and a template that has them again takes the grey away, with both in the middle',
	offRows( greyed ).length === 0 && greyed.pressed( variedName('quiet') ) === 'false,false,true,false,false' && greyed.pressed( variedName('loud') ) === 'false,false,true,false,false' );

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
const LOADED_WORDS = say( '/_admin/design/typefaces/available/loaded', '2026-10-12 12:00' );

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

// What the buttons of a row say - install, which is update where the list has another version, and remove - and the line that names the roles which use a family: the words of the panel
const INSTALL = TEXT['/_admin/design/typefaces/available/install'];
const REMOVE = TEXT['/_admin/design/typefaces/installed/remove'];
const usedBy = function( roles ) { return say( '/_admin/design/typefaces/installed/used', roles.map( roleWord ).join(', ') ) };

/** The buttons of the tab that do something to a family or to the list: install, update, remove and load */
const faceActions = function( screen ) {
	return byTag( screen.faceBox(), 'button' ).filter( function( button ) {
		return hasClass( button, 'nino-admin-btn-primary' ) || hasClass( button, 'nino-admin-btn-danger' ) || button.id === 'design-faces-load';
	} );
};

const shelves = panel();
shelves.show( 'typefaces' );

check( 'the loader is the tab Typefaces, a tab of its own: the line it says things in and two cards in its mount, with no strip of screens over them - the families that are installed and those that are on offer, each a block with its title and nothing under it',
	shelves.design._tabKeys().indexOf('typefaces') === -1 && tabOf( shelves ) === 'typefaces' && shelves.opened === 'design-typefaces' && screenOf( shelves ) === 'typefaces'
	&& shelves.faceBox().children.length === 2 && shelves.faceBox().children[0] === shelves.faceLine() && byClass( shelves.faceBox(), 'design-faces-cards' )[0] === shelves.faceBox().children[1]
	&& shelves.faceBox().children[1].children[0] === installedCard( shelves ) && shelves.faceBox().children[1].children[1] === availableCard( shelves )
	&& [ installedCard( shelves ), availableCard( shelves ) ].every( function( card ) { return hasClass( card, 'nino-admin-card' ) && hasClass( card, 'design-block' ) && byTag( card, 'small' ).length === 0 } )
	&& byTag( installedCard( shelves ), 'strong' )[0].textContent === TEXT['/_admin/design/typefaces/installed/title'] && byTag( availableCard( shelves ), 'strong' )[0].textContent === TEXT['/_admin/design/typefaces/available/title'] );
check( 'opening the tab asks for the list the server kept and not for the repository: one request, design/webfonts/list with refresh off - nothing leaves the server on its own',
	shelves.requests.length === 1 && shelves.requests[0].endpoint === 'webfonts/list' && JSON.stringify( shelves.requests[0].payload ) === '{"refresh":false}' );
check( '...and until it is answered the first card is its title and the second its title and the button that loads the list - no search field, no row',
	installedCard( shelves ).children.length === 1 && availableCard( shelves ).children.length === 2
	&& shelves.byId('design-faces-load') !== null && shelves.byId('design-faces-load').textContent === TEXT['/_admin/design/typefaces/available/load'] && shelves.byId('design-faces-load').disabled === false
	&& shelves.byId('design-faces-search') === null && faceRows( shelves.faceBox() ).length === 0 );

shelves.answer( 200, shelfOf() );

check( 'nothing installed and nothing loaded: the first card says how a typeface comes, the second that the list is fetched when the button is pressed and not before - and no time stands beside the button',
	JSON.stringify( hints( installedCard( shelves ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/installed/empty'] ] )
	&& JSON.stringify( hints( availableCard( shelves ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/unloaded'] ] )
	&& byClass( shelves.faceBox(), 'design-faces-when' ).length === 0 && shelves.byId('design-faces-search') === null );

// ---- Without an address, and without a key

const switchedOff = panel( { webfonts : { url : false, key : false } } );
switchedOff.show( 'typefaces' );
switchedOff.answer( 200, shelfOf( { url : '', key : false, installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) } } ) );

const noKey = panel( { webfonts : { url : true, key : false } } );
noKey.show( 'typefaces' );
noKey.answer( 200, shelfOf( { key : false, installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) } } ) );

check( 'with no address the second card is one sentence - the repository is switched off - and has no button, and the families that are installed stay where they are',
	JSON.stringify( hints( availableCard( switchedOff ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/off'] ] ) && switchedOff.byId('design-faces-load') === null
	&& availableCard( switchedOff ).children.length === 2 && faceSlugs( installedCard( switchedOff ) ) === 'inter' );
check( 'with an address and no key it is one sentence as well - the list cannot be checked - and has no button either: nothing would be fetched that could not be believed',
	JSON.stringify( hints( availableCard( noKey ) ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/nokey'] ] ) && noKey.byId('design-faces-load') === null
	&& faceSlugs( installedCard( noKey ) ) === 'inter' );

// ---- The settings of the repository are another panel's: the tab follows what the server says now, not what design/list said when the panel was opened

const changed = panel();
changed.show( 'typefaces' );
changed.answer( 200, shelfOf( { url : '', key : true } ) );

const changedBack = panel( { webfonts : { url : false, key : false } } );
changedBack.show( 'typefaces' );
changedBack.answer( 200, shelfOf( { url : 'https://webfonts.test/webfonts.json', key : true } ) );

const keyLost = panel();
keyLost.show( 'typefaces' );
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
	&& shelves.byId('design-faces-load').disabled === true && shelves.faceLine().textContent === '' );

shelves.answer( 200, shelfOf( { refusal : 'the typeface list could not be fetched: timeout', refusalKind : 'unreachable' } ) );

check( 'a list that could not be loaded is one sentence where the list would be, in the colour of an error, and the same in the line of the bar - the sentence of what the refusal is about, in the words of the workbench - and the button works again',
	byClass( availableCard( shelves ), 'nino-admin-error' ).length === 1
	&& byClass( availableCard( shelves ), 'nino-admin-error' )[0].textContent === TEXT['/_admin/design/typefaces/available/unreachable']
	&& shelves.faceLine().textContent === TEXT['/_admin/design/typefaces/available/unreachable'] && shelves.faceLine().className === 'nino-admin-error'
	&& shelves.byId('design-faces-load').disabled === false && shelves.byId('design-faces-search') === null );
check( '...the reason the server gives is English and is on screen nowhere: it is the title of the sentence in the card, and in the bar it follows the sentence in the title, in brackets',
	byClass( availableCard( shelves ), 'nino-admin-error' )[0].title === 'the typeface list could not be fetched: timeout'
	&& shelves.faceLine().title === TEXT['/_admin/design/typefaces/available/unreachable']+ ' (the typeface list could not be fetched: timeout)'
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

	refused.show( 'typefaces' );
	refused.answer( 200, shelfOf() );
	refused.byId('design-faces-load').fire('click');
	refused.answer( 200, shelfOf( Object.assign( { refusal : reason, refusalKind : kind }, REFUSED[kind][1] ) ) );

	const card = byClass( availableCard( refused ), 'nino-admin-error' )[0] || null;

	if( sentence === undefined )
		refusalProblems.push( kind+ ' has no words' );
	else {
		if( refused.faceLine().textContent !== sentence || refused.faceLine().className !== 'nino-admin-error' )
			refusalProblems.push( kind+ ': the bar says "'+ refused.faceLine().textContent+ '"' );
		if( refused.faceLine().title !== sentence+ ' ('+ reason+ ')' )
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
	&& shelves.faceLine().textContent === say( '/_admin/design/typefaces/msg/loaded', 0 ) && shelves.faceLine().className === '' && shelves.byId('design-faces-search') === null );

shelves.byId('design-faces-load').fire('click');
shelves.answer( 500, null );

check( 'a request that fails says so in the line of the bar, and the tab stays as it was',
	shelves.faceLine().textContent === '(500) '+ TEXT['/_admin/design/typefaces/error/list'] && shelves.faceLine().className === 'nino-admin-error'
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
const placeOf = function( at, total ) { return say( '/_admin/design/typefaces/page/of', at, total ) };

check( 'thirty families are a page of 24 and a page of 6: the first twenty-four rows, in the order of the list, and under them Previous - off -, "1 of 2" and Next',
	faceRows( results() ).length === 24 && faceSlugs( results() ) === thirty.slice( 0, 24 ).map( function( entry ) { return entry.slug } ).join()
	&& pageOf() === placeOf( 1, 2 ) && pagerButtons().map( function( button ) { return button.textContent+ ':'+ button.disabled } ).join() === TEXT['/_admin/design/typefaces/page/previous']+ ':true,'+ TEXT['/_admin/design/typefaces/page/next']+ ':false'
	&& shelves.faces.FACES_PER_PAGE === 24 );
check( 'the pager stands after the box in the card and not in it, so that it stays where it is while the box scrolls: the box holds the rows of the page and nothing else, and the one pager is what follows it in the card',
	byClass( results(), 'design-faces-pager' ).length === 0 && results().children.length === 24 && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1
	&& availableCard( shelves ).children[availableCard( shelves ).children.indexOf( results() ) + 1] === pager() );

pagerButtons()[1].fire('click');

check( '...Next shows the six that are left, and is off itself',
	faceSlugs( results() ) === 'face-25,face-26,face-27,face-28,face-29,face-30' && pageOf() === placeOf( 2, 2 )
	&& pagerButtons().map( function( button ) { return button.disabled } ).join() === 'false,true' );

pagerButtons()[0].fire('click');

check( '...and Previous the page before',
	faceRows( results() ).length === 24 && faceRows( results() )[0].dataset.slug === 'face-01' && pageOf() === placeOf( 1, 2 ) );

results().scrollTop = 300;
pagerButtons()[1].fire('click');

check( 'a page turned starts at the top of its box, whatever the page before was scrolled to - and it has one pager, its own: the one of the page before went with its rows',
	results().scrollTop === 0 && pageOf() === placeOf( 2, 2 ) && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1 && results().children.length === 6 );

pagerButtons()[0].fire('click');

const boxBefore = results();

results().scrollTop = 200;
shelves.faces._paintFaces();

check( 'a card that is drawn again - the answer of an install or of a removal, a list that was loaded - has its box scrolled to where the box before it was: a new box, at 200, with the rows of the same page and one pager after it',
	results() !== boxBefore && results().scrollTop === 200 && faceRows( results() ).length === 24 && pageOf() === placeOf( 1, 2 ) && byClass( availableCard( shelves ), 'design-faces-pager' ).length === 1 );

results().scrollTop = 200;
shelves.byId('design-faces-load').fire('click');
shelves.answer( 200, shelfOf( { families : thirty, fetched : LOADED_AT } ) );

check( 'a list loaded again starts at its first page, and that page at the top of its box - what the box before was scrolled to was another list\'s',
	results().scrollTop === 0 && pageOf() === placeOf( 1, 2 ) && faceRows( results() ).length === 24 );

check( 'a row is the icon the other rows of the panel have, the name, and under it the weights and the licence - a link to where the files came from, opened in a tab of its own that knows nothing of this one - and Install',
	( function() {
		const row = faceRowOf( results(), 'face-01' );
		const link = byTag( row, 'a' )[0];
		const label = byClass( row, 'design-face-label' )[0];
		return faceName( row ).textContent === 'Face 01' && faceNotes( row ).join() === '400, 700 · OFL-1.1'
			&& label.children[0].tagName === 'SVG' && label.children[0].dataset.icon === 'a-large-small' && hasClass( label, 'design-knob-label' )
			&& link.textContent === 'OFL-1.1' && link.href === 'https://fonts.test/face-01' && link.target === '_blank' && link.rel === 'noopener noreferrer'
			&& byTag( row, 'button' ).length === 1 && faceButton( row ).textContent === INSTALL && hasClass( faceButton( row ), 'nino-admin-btn-primary' ) && faceButton( row ).disabled === false
			&& faceButton( row ).getAttribute('aria-label') === INSTALL+ ' - Face 01';
	} )() );

// ---- The search and the categories

const search = function() { return shelves.byId('design-faces-search') };
const chips = function() { return byTag( byClass( availableCard( shelves ), 'design-knob-row' )[0], 'button' ) };
const chip = function( label ) { return chips().filter( function( button ) { return button.textContent === label } )[0] };
const categoryWord = function( kind ) { return TEXT['/_admin/design/typefaces/category/'+ kind] };
const litChips = function() { return chips().map( function( button ) { return button.getAttribute('aria-pressed') } ).join() };

check( 'over the rows are a search field - named by its placeholder - and the categories as a strip of the kind the knobs have: All lit, then Sans, Serif, Display, Handwriting, Mono and Other',
	search() !== null && search().type === 'search' && search().placeholder === TEXT['/_admin/design/typefaces/available/search'] && search().getAttribute('aria-label') === TEXT['/_admin/design/typefaces/available/search']
	&& byClass( availableCard( shelves ), 'design-knob-row' ).length === 1 && rowName( byClass( availableCard( shelves ), 'design-knob-row' )[0] ) === TEXT['/_admin/design/typefaces/available/category']
	&& chips().map( function( button ) { return button.textContent } ).join() === [ 'all', 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ].map( categoryWord ).join()
	&& chips().map( function( button ) { return button.dataset.value } ).join() === 'all,sans,serif,display,handwriting,mono,other'
	&& litChips() === 'true,false,false,false,false,false,false' && shelves.faces.FACE_CATEGORIES.join() === 'all,sans,serif,display,handwriting,mono,other' );

const field = search();
field.value = 'FACE 2';
field.fire('input');

check( 'a word typed in the search narrows the list by name, whatever its case, to the first page of what is left - and the field is the one that was typed in, so that it keeps the focus',
	search() === field && faceSlugs( results() ) === 'face-20,face-21,face-22,face-23,face-24,face-25,face-26,face-27,face-28,face-29' && pager() === null );

chip( categoryWord('serif') ).fire('click');

check( '...a category narrows it again: the serif families of those, and its chip is the lit one',
	faceSlugs( results() ) === 'face-20,face-26' && litChips() === 'false,false,true,false,false,false,false' && search() === field );

field.value = 'nothing of this name';
field.fire('input');

check( '...and what nothing matches is a sentence, with no pager under it',
	JSON.stringify( hints( results() ) ) === JSON.stringify( [ TEXT['/_admin/design/typefaces/available/nomatch'] ] ) && faceRows( results() ).length === 0 && pager() === null );

field.value = '';
field.fire('input');
chip( categoryWord('all') ).fire('click');

check( 'the search emptied and All pressed give the thirty back, from the first page',
	faceRows( results() ).length === 24 && pageOf() === placeOf( 1, 2 ) && litChips() === 'true,false,false,false,false,false,false' );

pagerButtons()[1].fire('click');
chip( categoryWord('mono') ).fire('click');

check( 'a category chosen while the second page is open starts from the first page of what it lets through',
	faceSlugs( results() ) === 'face-05,face-11,face-17,face-23,face-29' && pager() === null );

chip( categoryWord('all') ).fire('click');
pagerButtons()[1].fire('click');
field.value = 'face';
field.fire('input');

check( '...and so does a word typed while it is open',
	faceRows( results() ).length === 24 && faceRows( results() )[0].dataset.slug === 'face-01' && pageOf() === placeOf( 1, 2 ) );

field.value = '';
field.fire('input');

// ---- What a row says of a family that is installed

const offering = panel();
offering.show( 'typefaces' );
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
		return state !== undefined && state.textContent === TEXT['/_admin/design/typefaces/available/installed'] && byTag( same, 'button' ).length === 0
			&& update.textContent === say( '/_admin/design/typefaces/available/update', '2.0' ) && hasClass( update, 'nino-admin-btn-primary' ) && install.textContent === INSTALL
			&& update.getAttribute('aria-label') === say( '/_admin/design/typefaces/available/update', '2.0' )+ ' - Lora' && install.getAttribute('aria-label') === INSTALL+ ' - Fresh';
	} )() );
check( '...a family the feature brings itself is installed in a way, and has no button; a weight axis is told as one',
	( function() {
		const own = faceRowOf( availableCard( offering ), 'open-sans' );
		return byClass( own, 'design-face-state' ).length === 1 && byTag( own, 'button' ).length === 0
			&& faceNotes( faceRowOf( availableCard( offering ), 'axis' ) ).join() === say( '/_admin/design/typefaces/weights/variable', '100-900' )+ ' · OFL-1.1';
	} )() );

// ---- The families that are installed

const owning = panel();
owning.show( 'typefaces' );
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
	faceNotes( faceRowOf( installedCard( owning ), 'inter' ) )[0] === '400, 700 · 4.0 · OFL-1.1' && faceNotes( faceRowOf( installedCard( owning ), 'roboto' ) )[0] === say( '/_admin/design/typefaces/weights/variable', '100-900' )+ ' · 3.2 · OFL-1.1'
	&& faceName( faceRowOf( installedCard( owning ), 'roboto' ) ).textContent === 'Roboto Flex'
	&& faceRows( installedCard( owning ) ).slice( 0, 3 ).every( function( row ) { return byClass( row, 'design-face-label' )[0].children[0].dataset.icon === 'a-large-small' } ) );
check( 'a family a role of the saved setup uses cannot be removed: its button is off, its title names the roles and so does a second line under the name - in the words of the Type view',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'inter' );
		const remove = faceButton( row );
		return remove.disabled === true && remove.title === usedBy( [ 'title', 'text' ] ) && remove.textContent === REMOVE && hasClass( remove, 'nino-admin-btn-danger' )
			&& remove.getAttribute('aria-label') === REMOVE+ ' - Inter'
			&& JSON.stringify( faceNotes( row ) ) === JSON.stringify( [ '400, 700 · 4.0 · OFL-1.1', usedBy( [ 'title', 'text' ] ) ] );
	} )() );
check( '...and one that no role uses has the button, working, with no second line',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'lora' );
		return faceButton( row ).disabled === false && faceButton( row ).title === REMOVE && faceNotes( row ).length === 1;
	} )() );

owning.design._edit.fonts.subtitle.family = 'lora';
owning.faces._paintFaces();

check( 'a family chosen for a role in the selection being edited is as little to be removed as one the saved setup gives it, before anything is saved',
	faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).disabled === true && faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).title === usedBy( [ 'subtitle' ] )
	&& faceNotes( faceRowOf( installedCard( owning ), 'lora' ) )[1] === usedBy( [ 'subtitle' ] ) && faceButton( faceRowOf( installedCard( owning ), 'roboto' ) ).disabled === false );

owning.design._edit.fonts.subtitle.family = 'inter';
owning.faces._paintFaces();

check( '...and both give a family their roles together, in the order of the roles of the Type view',
	faceButton( faceRowOf( installedCard( owning ), 'inter' ) ).title === usedBy( [ 'title', 'subtitle', 'text' ] ) && faceButton( faceRowOf( installedCard( owning ), 'lora' ) ).disabled === false );

owning.design._edit.fonts.subtitle.family = 'open-sans';
owning.faces._paintFaces();

check( 'a directory that is no family is listed greyed, under its own name, with the reason - and a Remove that works whatever the reason is',
	( function() {
		const row = faceRowOf( installedCard( owning ), 'odd-one' );
		return hasClass( row, 'design-face-row--off' ) && faceName( row ).textContent === 'odd-one' && faceNotes( row ).join() === say( '/_admin/design/typefaces/installed/broken', 'there is no font.json' )
			&& faceButton( row ).disabled === false && faceButton( row ).textContent === REMOVE && hasClass( faceButton( row ), 'nino-admin-btn-danger' ) && faceButton( row ).getAttribute('aria-label') === REMOVE+ ' - odd-one'
			&& hasClass( faceRowOf( installedCard( owning ), 'lora' ), 'design-face-row--off' ) === false;
	} )() );

check( 'the tab asked for the list that was kept when it was opened, and for nothing more: no row, no search and no chip asks the server anything',
	askedOf( owning ) === '' && askedOf( offering ) === '' && askedOf( shelves ) === '' );

faceButton( faceRowOf( installedCard( owning ), 'odd-one' ) ).fire('click');

check( 'the Remove of a directory that is no family asks for that name, as it is: the server decides what it is',
	owning.requests.length === 1 && owning.requests[0].endpoint === 'webfonts/remove' && JSON.stringify( owning.requests[0].payload ) === '{"slug":"odd-one"}' );

// ---- An install, a refusal, a removal

const doing = panel();
doing.show( 'typefaces' );

const listed = [ offerOf( 'inter', 'Inter', 'sans', { version : '4.0' } ), offerOf( 'lora', 'Lora', 'serif', { version : '2.0' } ), offerOf( 'fresh', 'Fresh', 'display' ) ];
const listedAnswer = function( over ) { return shelfOf( Object.assign( { families : listed, fetched : LOADED_AT }, over || {} ) ) };
const plain = { inter : installedOf( 'inter', 'Inter', '4.0' ), lora : installedOf( 'lora', 'Lora', '1.0', { category : 'serif' } ) };
const withFresh = Object.assign( {}, plain, { fresh : installedOf( 'fresh', 'Fresh', '1.0', { category : 'display' } ) } );

// What the server says in the line of the bar for an install, an update and a removal: the sentences of the texts, with the family filled in
const FRESH_INSTALLED = say( '/_admin/design/typefaces/msg/installed', 'Fresh', '1.0' );
const FRESH_REMOVED = say( '/_admin/design/typefaces/msg/removed', 'Fresh' );
const LORA_UPDATED = say( '/_admin/design/typefaces/msg/updated', 'Lora', '2.0' );

doing.answer( 200, listedAnswer( { installed : plain, uses : { inter : [ 'text' ], lora : [] } } ) );

const draftBefore = JSON.stringify( doing.design._edit );
const enabledBefore = faceActions( doing ).filter( function( button ) { return button.disabled === false } ).length;

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');

check( 'Install asks the server for that family and for nothing else: design/webfonts/install with the slug - and the buttons of the tab are off while it is on its way, the line of the bar empty',
	doing.requests.length === 1 && doing.requests[0].endpoint === 'webfonts/install' && JSON.stringify( doing.requests[0].payload ) === '{"slug":"fresh"}'
	&& enabledBefore === 4 && faceActions( doing ).length === 5 && faceActions( doing ).every( function( button ) { return button.disabled === true } ) && doing.faceLine().textContent === '' );

doing.faces._installFace('lora');
doing.faces._removeFace('lora');

check( '...and a second action asked for while the first is on its way is not sent', doing.requests.length === 1 );

doing.answer( 409, { error : 'the archive does not match its checksum' } );

check( 'a refusal is told in the line of the bar with the reason, as an error, and the buttons work again; nothing is read again and nothing changed on the tab',
	doing.faceLine().textContent === say( '/_admin/design/typefaces/error/install', 'the archive does not match its checksum' ) && doing.faceLine().className === 'nino-admin-error'
	&& doing.requests.length === 0 && faceSlugs( installedCard( doing ) ) === 'inter,lora'
	&& faceActions( doing ).filter( function( button ) { return button.disabled === false } ).length === enabledBefore );

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 500, null );

check( 'a failure that has no words of its own is told the way the workbench tells one, after what failed - and no %s is left in it',
	doing.faceLine().textContent.indexOf( say( '/_admin/design/typefaces/error/install', '(500)' ) ) === 0 && doing.faceLine().textContent.indexOf('%s') === -1 && doing.faceLine().className === 'nino-admin-error' );

faceButton( faceRowOf( availableCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 200, listedAnswer( { msg : FRESH_INSTALLED, installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

check( 'an install that went through is the new state of both cards, from the one answer: the family stands among the installed, the row of the list says "installed" - and the line of the bar says it, as the server wrote it',
	faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' && byClass( faceRowOf( availableCard( doing ), 'fresh' ), 'design-face-state' ).length === 1
	&& byTag( faceRowOf( availableCard( doing ), 'fresh' ), 'button' ).length === 0
	&& doing.faceLine().textContent === FRESH_INSTALLED && doing.faceLine().className === '' );
check( '...then design/list is asked again, for the selects of the font roles: and nothing else is sent, no save and no preview - the draft is the one it was',
	askedOf( doing ) === 'list' && JSON.stringify( doing.requests[0].payload ) === '{}' && JSON.stringify( doing.design._edit ) === draftBefore && doing.design._isDirty() === false );

doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora', fresh : 'Fresh' } ) );
doing.show( 'type' );

check( '...and the family is offered in the select of a font role, drawn afresh when the Type view is opened, under the name it has',
	offered( doing.byId('design-font-title-family') ).some( function( option ) { return option[0] === 'fresh' && option[1] === 'Fresh' } ) );

doing.show( 'typefaces' );
doing.answer( 200, listedAnswer( { installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

faceButton( faceRowOf( availableCard( doing ), 'lora' ) ).fire('click');
doing.answer( 200, listedAnswer( {
	msg 			: LORA_UPDATED,
	installed : Object.assign( {}, withFresh, { lora : installedOf( 'lora', 'Lora', '2.0', { category : 'serif' } ) } ),
	uses 			: { inter : [ 'text' ], lora : [], fresh : [] },
} ) );

check( 'an update is the same: the family that was there is the new version, and the row of the list says "installed" where the button said "update to 2.0"',
	doing.faceLine().textContent === LORA_UPDATED && faceNotes( faceRowOf( installedCard( doing ), 'lora' ) )[0] === '400, 700 · 2.0 · OFL-1.1'
	&& byClass( faceRowOf( availableCard( doing ), 'lora' ), 'design-face-state' ).length === 1 && askedOf( doing ) === 'list' );

doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora', fresh : 'Fresh' } ) );
faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');

check( 'Remove asks for that family: design/webfonts/remove with the slug',
	doing.requests.length === 1 && doing.requests[0].endpoint === 'webfonts/remove' && JSON.stringify( doing.requests[0].payload ) === '{"slug":"fresh"}' );

doing.answer( 409, { error : 'the typeface "fresh" is used by title' } );

check( 'a removal the server refuses says why in the line of the bar, as an error, and the family stays',
	doing.faceLine().textContent === say( '/_admin/design/typefaces/error/remove', 'the typeface "fresh" is used by title' ) && doing.faceLine().className === 'nino-admin-error' && faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' );

faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 409, { error : 'used by title, text', code : 'used', params : [ 'title', 'text' ] } );

check( 'a family a role still uses is refused with the roles in the words of the workbench, not in the server\'s',
	doing.faceLine().textContent === say( '/_admin/design/typefaces/error/used', roleWord('title')+ ', '+ roleWord('text') ) && doing.faceLine().className === 'nino-admin-error' && faceSlugs( installedCard( doing ) ) === 'inter,lora,fresh' );

faceButton( faceRowOf( installedCard( doing ), 'fresh' ) ).fire('click');
doing.answer( 200, listedAnswer( {
	msg 			: FRESH_REMOVED,
	installed : { inter : plain.inter, lora : installedOf( 'lora', 'Lora', '2.0', { category : 'serif' } ) },
	uses 			: { inter : [ 'text' ], lora : [] },
} ) );

check( 'a removal that went through takes the family out of the first card; the row of the list offers to install it again, right then - design/list still says what it said before, with the family among its families, as the server\'s does for what is installed; the line of the bar says it, and design/list is read again',
	faceSlugs( installedCard( doing ) ) === 'inter,lora' && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === INSTALL
	&& byClass( faceRowOf( availableCard( doing ), 'fresh' ), 'design-face-state' ).length === 0 && doing.design._data.families.fresh !== undefined
	&& doing.faceLine().textContent === FRESH_REMOVED && askedOf( doing ) === 'list' );

// The families the feature brings are named by the answer of the tab, and by nothing else: design/list has them, and has the installed ones too
const owned = panel();
owned.show( 'typefaces' );
owned.answer( 200, shelfOf( { families : [ offerOf( 'open-sans', 'Open Sans', 'sans' ) ], fetched : LOADED_AT, builtin : [] } ) );

check( 'a row of the list for a family the feature brings says "installed" because the answer of the tab names it, and not because design/list has it among its families: with the answer naming none, the row offers to install',
	owned.design._data.families['open-sans'] !== undefined && byClass( faceRowOf( availableCard( owned ), 'open-sans' ), 'design-face-state' ).length === 0
	&& faceWord( faceRowOf( availableCard( owned ), 'open-sans' ) ) === INSTALL
	&& doing.faces._faces.builtin.join() === BROUGHT.join() );

doing.answer( 500, null );

check( 'when design/list cannot be read again the tab stays as it is: the line of the bar keeps what the action said, and the row of the list still offers to install',
	doing.faceLine().textContent === FRESH_REMOVED && faceSlugs( installedCard( doing ) ) === 'inter,lora' && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === INSTALL );

const rowBefore = faceRowOf( availableCard( doing ), 'fresh' );

doing.design._relist();
doing.answer( 200, listingOf( { inter : 'Inter', lora : 'Lora' } ) );

check( '...and when it is read again the open tab is drawn again, from what it knows, and the row of the list offers to install it still: the selects of the font roles have lost the family and the tab has not been told twice',
	faceRowOf( availableCard( doing ), 'fresh' ) !== rowBefore && faceWord( faceRowOf( availableCard( doing ), 'fresh' ) ) === INSTALL
	&& doing.design._data.families.fresh === undefined && faceSlugs( installedCard( doing ) ) === 'inter,lora' && doing.faceLine().textContent === FRESH_REMOVED && askedOf( doing ) === '' );

// ---- Only the last answer is the state, and an action that is on its way answers for the tab

const racing = panel();
racing.show( 'typefaces' );
racing.byId('design-faces-load').fire('click');

check( 'the list that was kept and the list loaded were asked for in turn',
	askedOf( racing ) === 'webfonts/list,webfonts/list' && racing.requests[0].payload.refresh === false && racing.requests[1].payload.refresh === true );

racing.requests[1].answer( 200, shelfOf( { families : listed, fetched : LOADED_AT } ) );
racing.requests[0].answer( 200, shelfOf() );

check( 'an answer that comes after a later one is not the state: the older request answering last does not take the list away',
	racing.faces._faces.families.length === 3 && faceRows( availableCard( racing ) ).length === 3 );

const busy = panel();
busy.show( 'typefaces' );
busy.answer( 200, listedAnswer( { installed : plain, uses : { inter : [ 'text' ], lora : [] } } ) );
faceButton( faceRowOf( availableCard( busy ), 'fresh' ) ).fire('click');
busy.show( 'type' );
busy.show( 'typefaces' );

check( 'a tab that is opened while an action is on its way does not ask for the list: the answer of the action is the state, and the buttons are off until it comes',
	askedOf( busy ) === 'webfonts/install' && faceActions( busy ).length === 5 && faceActions( busy ).every( function( button ) { return button.disabled === true } ) );

busy.answer( 200, listedAnswer( { msg : FRESH_INSTALLED, installed : withFresh, uses : { inter : [ 'text' ], lora : [], fresh : [] } } ) );

check( '...and it draws the tab it finds',
	faceSlugs( installedCard( busy ) ) === 'inter,lora,fresh' && busy.faceLine().textContent === FRESH_INSTALLED && askedOf( busy ) === 'list' );

const leaving = panel();
leaving.show( 'typefaces' );
leaving.tab( tabWord('surfaces') );

check( 'the answer of a request that outlived the tab is kept and draws nothing, and does not fail - the tab was left',
	threw( function() { leaving.answer( 200, shelfOf( { installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) }, uses : { inter : [] } } ) ) } ) === false
	&& leaving.faces._faces !== null && leaving.byId('design-faces-installed') === null );

leaving.show( 'typefaces' );

check( '...the next time the tab is opened it is drawn from what is known at once, and the server is asked again',
	faceSlugs( installedCard( leaving ) ) === 'inter' && askedOf( leaving ) === 'webfonts/list' );

const leavingBy = panel();
leavingBy.show( 'typefaces' );
leavingBy.show( 'type' );

check( 'the loader is left for the view Type of the setup as it is for any other tab: the answer of a request that outlived the tab is kept and draws nothing, and does not fail - the rows of the Type view stand',
	threw( function() { leavingBy.answer( 200, shelfOf( { installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) }, uses : { inter : [] } } ) ) } ) === false
	&& leavingBy.faces._faces !== null && leavingBy.byId('design-faces-installed') === null && screenOf( leavingBy ) === 'type' && blocksOf( leavingBy.form ).length > 0 );

// ---- An edit made while a request is on its way is the screen's

const raced = panel();
raced.design._edit.size = 1;
raced.byId('design-save').fire('click');
raced.design._edit.size = 2;
raced.answer( 200, {} );

check( 'a save is answered by reading the stored setup again', JSON.stringify( endpoints( raced ) ) === JSON.stringify( [ 'list' ] ) && raced.requests[0].payload.size === undefined );

raced.answer( 200, listing( { size : 1, notes : [ { note : 'kept' } ], active : 'fresh' } ) );

check( '...the notes and the active theme of the answer are taken all the same, as the copy is the only thing that is not', JSON.stringify( raced.design._notes ) === JSON.stringify( [ { note : 'kept' } ] ) && raced.design._active === 'fresh' );
check( 'a knob moved while the save was on its way is still where it was moved when the stored setup is read: the screen holds it and the way back is offered, as the selection is not the stored one',
	raced.design._edit.size === 2 && raced.design._data.size === 1 && raced.design._isDirty() === true && raced.byId('design-reset').hidden === false && raced.byId('design-save').disabled === false
	&& raced.byId('design-msg').textContent === TEXT['/_admin/design/msg/saved'] );

raced.byId('design-save').fire('click');

check( '...and the next save sends it', raced.requests.length === 1 && raced.requests[0].endpoint === 'save' && raced.requests[0].payload.size === 2 );

raced.answer( 200, {} );
raced.answer( 200, listing( { size : 2 } ) );

check( '...after which the selection is the stored one', raced.design._edit.size === 2 && raced.design._isDirty() === false && raced.byId('design-reset').hidden === true );

const raceQuiet = panel();
raceQuiet.design._edit.size = 1;
raceQuiet.byId('design-save').fire('click');
raceQuiet.answer( 200, {} );
const quietBefore = raceQuiet.design._edit.whitespace;
raceQuiet.answer( 200, listing( { size : 1, whitespace : quietBefore + 1 } ) );

check( 'a save that nothing was changed during is read as it was: the screen is drawn from the stored setup, which holds what the edit does not', raceQuiet.design._edit.whitespace === quietBefore + 1 && raceQuiet.design._edit.size === 1 && raceQuiet.design._isDirty() === false && raceQuiet.byId('design-reset').hidden === true );

const racedApply = panel();
racedApply.design._edit.size = 1;
racedApply.byId('design-apply').fire('click');
racedApply.answer( 200, {} );
racedApply.answer( 200, { files : [] } );
racedApply.design._edit.size = 2;
racedApply.asks[0].choose('yes');
racedApply.answer( 200, {} );
racedApply.answer( 200, listing( { size : 1, exists : true } ) );

check( 'an apply that was answered while a knob was being moved leaves the knob where it is, and the selection unsaved', racedApply.requests.length === 0
	&& racedApply.design._edit.size === 2 && racedApply.design._isDirty() === true && racedApply.byId('design-msg').textContent === TEXT['/_admin/design/msg/applied'] );

const raceDeclined = panel();
raceDeclined.design._edit.size = 1;
raceDeclined.byId('design-apply').fire('click');
raceDeclined.answer( 200, {} );
raceDeclined.answer( 200, { files : [] } );
raceDeclined.design._edit.size = 2;
raceDeclined.asks[0].choose('no');
raceDeclined.answer( 200, listing( { size : 1 } ) );

check( '...and so does a question that is answered with a no', raceDeclined.design._edit.size === 2 && raceDeclined.design._isDirty() === true && raceDeclined.byId('design-msg').textContent === TEXT['/_admin/design/msg/cancelled'] );

// The previews of a selection that moves are answered in any order: the frame shows the newest
const ordered = panel();
const orderedFrame = ordered.byId('design-frame');
const racePreviewed = function( size, document ) {
	ordered.design._edit.size = size;
	ordered.design._previewNow();
	const request = ordered.requests[ordered.requests.length - 1];
	return { request : request, answer : function( status ) { request.answer( status, status === 200 ? { css : ':root{}', document : document, style : 'design-preview-css', notes : [] } : null ) } };
};
const raceOlder = racePreviewed( 1, '<html>older</html>' );
const raceNewer = racePreviewed( 2, '<html>newer</html>' );

raceNewer.answer( 200 );
raceOlder.answer( 200 );

check( 'the answer of an older preview that comes after the answer of a newer one is dropped: the frame shows the selection that is on screen', orderedFrame.srcdoc === '<html>newer</html>' && ordered.design._edit.size === 2 );

const raceFirst = racePreviewed( 3, '<html>first</html>' );
const raceLast = racePreviewed( 4, '<html>last</html>' );

raceFirst.answer( 200 );

check( '...and one that comes before it is not shown either: the frame keeps what it has until the answer to the last request is here', orderedFrame.srcdoc === '<html>newer</html>' );

raceLast.answer( 200 );

check( '...and then shows it', orderedFrame.srcdoc === '<html>last</html>' );

const raceFailing = racePreviewed( 5, '' );
const raceSucceeding = racePreviewed( 6, '<html>sixth</html>' );

raceSucceeding.answer( 200 );
raceFailing.answer( 500 );

check( 'a preview that failed after a newer one succeeded says nothing: the line is the newest\'s', orderedFrame.srcdoc === '<html>sixth</html>' && ordered.byId('design-preview-msg').className === 'nino-admin-hint' );

// ---- The names are set in their own faces

const painted = panel();
painted.show( 'typefaces' );

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

painted.tab( tabWord('surfaces') );

check( 'leaving the tab for another lets go of what it held: the faces are out of the fonts of the document, the observer has stopped watching and is let go - a face is a file the browser keeps',
	painted.typeset.set.length === 0 && observer.watched.length === 0 && observer.ended >= 1 && painted.faces._facesObserver === null && Object.keys( painted.faces._facesLoaded ).length === 0 );

painted.show( 'typefaces' );

check( 'coming back to the tab draws it from what is known and sets the installed names again, with faces made again - and a new observer watches the rows for the visit',
	painted.typeset.made.length === 6 && painted.typeset.made[4].family === 'design-installed-inter' && painted.typeset.made[5].family === 'design-installed-quote'
	&& painted.typeset.set.length === 2 && painted.typeset.observers.length === 2 && painted.typeset.observers[1].watched.length === 24 );

painted.answer( 200, paintedAnswer );

check( 'the answer to the second visit draws the same rows again and makes no face twice; every load the tab asked of a face was given its catch',
	painted.typeset.made.length === 6 && painted.typeset.set.length === 2 && painted.typeset.made.every( function( face ) { return face.caught === face.loads } ) );

painted.show( 'type' );

check( 'leaving the loader for the view Type of the setup lets go of what it held as leaving for any other tab does: the faces are out of the fonts of the document, the observer has stopped watching and is let go - the rows of the Type view stand in its place',
	painted.typeset.set.length === 0 && painted.typeset.observers[1].watched.length === 0 && painted.typeset.observers[1].ended >= 1 && painted.faces._facesObserver === null
	&& Object.keys( painted.faces._facesLoaded ).length === 0 && screenOf( painted ) === 'type' && blocksOf( painted.form ).length > 0 );

console.log('');


// ---- The screen that carries the bar drawn again while the loader is the tab that is shown: the loader has no bar to press Reset on, and is left as it is

const redrawn = panel();
redrawn.show( 'typefaces' );
redrawn.answer( 200, shelfOf( { installed : { inter : installedOf( 'inter', 'Inter', '4.0' ) }, uses : { inter : [] }, families : thirty, fetched : LOADED_AT } ) );
redrawn.design._edit.fonts.title.family = 'inter';

const redrawnNote = faceNotes( faceRowOf( installedCard( redrawn ), 'inter' ) ).length;
const redrawnCards = installedCard( redrawn );

redrawn.design._revert();

check( 'the stored selection put back while the loader is shown - what the shell\'s Discard does - draws the screen that carries the bar and leaves the loader alone: the two cards are the ones that were drawn, the list is not asked for again, no face is made twice and the one observer still watches the rows',
	threw( function() { redrawn.design._revert() } ) === false && redrawn.opened === 'design-typefaces' && screenOf( redrawn ) === 'typefaces' && redrawn.design._isDirty() === false && redrawnNote === 1
	&& installedCard( redrawn ) === redrawnCards && faceSlugs( installedCard( redrawn ) ) === 'inter' && faceRows( availableCard( redrawn ) ).length === 24 && askedOf( redrawn ) === ''
	&& redrawn.typeset.made.filter( function( face ) { return face.family === 'design-installed-inter' } ).length === 1
	&& redrawn.typeset.observers.length === 1 && redrawn.typeset.observers[0].watched.length === 24 );

// --- Colours: the palette and its mode ------------------------------------------------

console.log( 'Colours' );

const colours = panel();
colours.tab( tabWord('colours') );

check( 'the Colours view has the brand colour, the five knobs the rest of the palette is solved with - the second colour at the end of the first of them - and the mode, and each of the five rows has the class the stylesheet takes a row of the palette by',
	tabOf( colours ) === 'colours' && colours.byId('design-colour-primary').value === '#4faae8'
	&& byClass( colours.form, 'design-knob-row' ).map( rowName ).join() === [ 'harmony', 'temperature', 'saturation', 'contrast', 'depth' ].map( function( knob ) { return TEXT['/_admin/design/colour/'+ knob+ '/label'] } ).join()
	&& byClass( colours.form, 'design-colour-knob' ).map( rowName ).join() === byClass( colours.form, 'design-knob-row' ).map( rowName ).join()
	&& colours.byId('design-colour-secondary') !== null && colours.byId('design-colour-mode') !== null );
check( '...four positions for Harmony and Temperature, in the words of the panel, and five for the others, numbered from the middle - and the one the knob starts on lit',
	[ 4, 4, 5, 5, 5 ].join() === byClass( colours.form, 'design-knob-row' ).map( function( row ) { return byTag( row, 'button' ).length } ).filter( function( count, at ) { return at < 5 } ).join()
	&& byTag( colours.knobRow( colourWord('temperature') ), 'button' ).map( function( button ) { return button.textContent } ).join() === colourSteps( 'temperature', 4 )
	&& colours.pressed( colourWord('temperature') ) === 'false,false,true,false' );
/*	A choice says its names and a scale its numbers: the three scales of the palette are the same five buttons the knobs of the other tabs
	have, the 0 the framework's own, which is the third position of five, and what they store is the position - 1 to 5 - and not the number
	on the button. The names of the two choices are what they were, each capitalised, and the words of the three scales are gone	*/
check( '...the words of the two choices are those the text file has for their positions, in its order; the three scales say \u22122 \u22121 0 +1 +2, the 0 lit at the start, and press the positions 1 to 5',
	[ [ colourWord('harmony'), colourSteps( 'harmony', 4 ) ], [ colourWord('temperature'), colourSteps( 'temperature', 4 ) ] ].every( function( entry ) {
		return byTag( colours.knobRow( entry[0] ), 'button' ).map( function( button ) { return button.textContent } ).join() === entry[1];
	} )
	&& [ 'saturation', 'contrast', 'depth' ].map( colourWord ).every( function( name ) {
		return colours.steps( name ).map( function( button ) { return button.textContent } ).join() === SIGNED.join() && colours.steps( name ).map( function( button ) { return button.title } ).join() === SIGNED.join()
			&& colours.steps( name ).map( function( button ) { return button.dataset.value } ).join() === '1,2,3,4,5' && colours.pressed( name ) === 'false,false,true,false,false'
			&& colours.lit().filter( function( row ) { return row[0] === name } )[0][1] === '0';
	} ) );
check( 'the mode is a select of the three modes the palette has, in the words of the text file, under a heading of its own, and starts on the first',
	JSON.stringify( offered( colours.byId('design-colour-mode') ) ) === JSON.stringify( [ 'auto', 'light', 'dark' ].map( function( mode ) { return [ mode, TEXT['/_admin/design/colour/mode/'+ mode] ] } ) )
	&& shown( colours.byId('design-colour-mode') ) === 'auto' && colours.byId('design-colour-mode').getAttribute('aria-label') === TEXT['/_admin/design/colour/mode/label']
	&& byClass( colours.form, 'design-colour-mode' ).length === 1 );
check( '...and they stay lower case, as the steps of the knobs over it are capitalised: the Colours view keeps its select lower case',
	offered( colours.byId('design-colour-mode') ).every( function( option ) { return option[1].charAt(0) === option[1].charAt(0).toLowerCase() } ) );

choose( colours.byId('design-colour-mode'), 'dark' );

check( 'choosing a mode is the selection on screen', colours.design._edit.colours.mode === 'dark' && colours.byId('design-reset').hidden === false );

const brand = colours.byId('design-colour-primary');
brand.value = '#8b1d3f';
brand.fire('change');

check( 'a new brand colour is the selection, read off the input that was changed, and the rows of the palette are drawn again from it',
	colours.design._edit.colours.primary === '#8b1d3f' && descendants( colours.byId('design-controls') ).indexOf( brand ) === -1 && colours.byId('design-colour-primary') !== brand && colours.byId('design-colour-primary').value === '#8b1d3f' );

byTag( colours.knobRow( colourWord('saturation') ), 'button' )[4].fire('click');
byTag( colours.knobRow( colourWord('contrast') ), 'button' )[0].fire('click');

check( 'a position of a knob is a number, 1 to 5: +2 on a scale is the fifth position and \u22122 the first',
	colours.design._edit.colours.saturation === 5 && colours.design._edit.colours.contrast === 1 && typeof colours.design._edit.colours.saturation === 'number' );
check( 'the rows show the brand colour, the second colour - Monochrome while nobody chose one - every knob at the position it stands at, and the mode, with nothing under the frame to say it again - and after the page\'s five the ground of the header, the colour of its line, the ground of the footer, the colour of its line, the dimming, the ground of the title and the colour of the rule of a section, the tone of the shadow and the ground of the title of a card and the stripes of a table, and the tone of the lines and of the labels of a form, in the middle',
	colours.byId('design-colour-primary').value === '#8b1d3f'
	&& JSON.stringify( colours.lit() ) === JSON.stringify( [ [ colourWord('harmony'), TEXT['/_admin/design/colour/harmony/1'] ], [ colourWord('temperature'), TEXT['/_admin/design/colour/temperature/3'] ], [ colourWord('saturation'), '+2' ], [ colourWord('contrast'), '\u22122' ], [ colourWord('depth'), '0' ],
		[ knobWord( 'header', 'ground' ), TEXT['/_admin/design/knob/header/ground/default'] ], [ knobWord( 'header', 'border-color' ), TEXT['/_admin/design/knob/header/border-color/default'] ], [ knobWord( 'footer', 'ground' ), TEXT['/_admin/design/knob/footer/ground/default'] ], [ knobWord( 'footer', 'border-color' ), TEXT['/_admin/design/knob/footer/border-color/default'] ],
		[ knobWord( 'section', 'dim' ), '0' ], [ knobWord( 'section', 'title-ground' ), TEXT['/_admin/design/knob/section/title-ground/default'] ], [ knobWord( 'section', 'rule-color' ), TEXT['/_admin/design/knob/section/rule-color/default'] ], [ knobWord( 'section', 'title-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'section', 'subtitle-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'tiles', 'dim' ), '0' ], [ knobWord( 'tiles', 'title-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'tiles', 'subtitle-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'card', 'tone' ), TEXT['/_admin/design/knob/card/tone/default'] ], [ knobWord( 'card', 'title-ground' ), TEXT['/_admin/design/knob/card/title-ground/default'] ], [ knobWord( 'card', 'title-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'card', 'subtitle-ink' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'tables', 'stripes' ), '0' ],
		[ knobWord( 'forms', 'edge' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'forms', 'label' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'tabs', 'active' ), TEXT['/_admin/design/label/own'] ], [ knobWord( 'accordion', 'marker-ink' ), TEXT['/_admin/design/label/own'] ] ] )
	&& wordOf( colours.byId('design-colour-mode') ) === TEXT['/_admin/design/colour/mode/dark'] && colours.byId('design-summary') === null );

// A second colour somebody chose overrides the wheel; choosing a position on the wheel gives it up
const second = colours.byId('design-colour-secondary');
second.value = '#0f766e';
second.fire('change');

check( 'a second colour that was chosen is shown as chosen: the swatch has the hex, where the row lit the place on the wheel',
	colours.design._edit.colours.secondary === '#0f766e' && colours.byId('design-colour-secondary').value === '#0f766e'
	&& byClass( colours.form, 'design-knob-reset' ).length === 1
	&& byTag( colours.knobRow( colourWord('harmony') ), 'button' ).filter( function( button ) { return button.getAttribute('aria-pressed') === 'true' } ).length === 0 );

byTag( colours.knobRow( colourWord('harmony') ), 'button' )[2].fire('click');

check( '...and a position on the wheel gives it up again', colours.design._edit.colours.secondary === '' && colours.design._edit.colours.harmony === 3
	&& colours.lit()[0][1] === TEXT['/_admin/design/colour/harmony/3'] && byClass( colours.form, 'design-knob-reset' ).length === 0 );

// The row that is drawn again is the one the table puts the second colour in, with the Harmony it ends: a table that moves them moves it
const colourMoved = layoutTable();
colourMoved.controls['colours/secondary'] = [ 'surfaces', 'card', 9 ];
colourMoved.controls['colours/harmony'] = [ 'surfaces', 'card', 9 ];

const cm = panel( { layout : colourMoved } );
cm.tab( tabWord('surfaces') );
goTo( cm, 'card' );
openBlock( cm.byId('design-row-surfaces-card') );

const cmRow = cm.byId('design-row-surfaces-card');
const cmSwatch = cm.byId('design-colour-secondary');

cmSwatch.value = '#0f766e';
cmSwatch.fire('change');

check( 'a change of the second colour draws again the row the table puts it in, wherever that is: moved to the card of Surfaces it is that card, with a new swatch in it that has the colour - and there is no row of the Colours view to draw',
	cmRow !== null && cmSwatch !== null && cm.byId('design-row-surfaces-card') !== null && cm.byId('design-row-surfaces-card') !== cmRow
	&& cm.byId('design-colour-secondary') !== null && cm.byId('design-colour-secondary') !== cmSwatch && cm.byId('design-colour-secondary').value === '#0f766e'
	&& cm.design._edit.colours.secondary === '#0f766e' && cm.byId('design-row-colours-page') === null && cm.design._target === 'colours' );

// The Colours view is four blocks - the page's palette first, open to begin with, then the dimming of a section, the tone of the shadow of a card and the stripes of a table - closable like every other; closed, a head says what stands off the defaults
const cs = panel();
cs.tab( tabWord('colours') );
const csBlock = function() { goTo( cs, 'page' ); return blocksOf( cs.form ).filter( function( card ) { return card.dataset.tab === 'colours' } )[0] };
const csSaid = function() { return byClass( csBlock(), 'design-accordion-summary' )[0].textContent };

const csWalked = [];
eachBlock( cs.form, function( card ) { csWalked.push( [ card.dataset.object, byClass( headButton( card ), 'design-accordion-name' )[0].textContent ] ) } );

check( 'Colour is a block under ten objects, the page\'s first and then the header\'s, the footer\'s, the section\'s, the tiles\', the card\'s, the tables\', the forms\', the tabs\' and the accordion\'s: a head named by the word of its aspect, and in the body of the page\'s the brand colour, the second colour, the five knobs and the mode - open to begin with',
	csWalked.map( function( entry ) { return entry[0] } ).join() === 'page,header,footer,section,tiles,card,tables,forms,tabs,accordion' && csWalked.every( function( entry ) { return entry[1] === tabWord('colours') } )
	&& csBlock().dataset.tab === 'colours' && csBlock().dataset.object === 'page' && isOpen( csBlock() ) === true && blocksOf( cs.form ).filter( isOpen ).length === 1
	&& byClass( csBlock(), 'design-knob-row' ).length === 5
	&& cs.byId('design-colour-primary') !== null && cs.byId('design-colour-secondary') !== null && cs.byId('design-colour-mode') !== null
	&& JSON.stringify( drawnRows( cs ) ) === JSON.stringify( tableRows( cs.design._data.layout, 'colours' ) ) );

toggle( csBlock() );

check( '...closable like every other block: closed it has no body and no control in the document, its head says nothing at the defaults, and the tab remembers it while the panel lives - opened again it is the block that was open',
	isOpen( csBlock() ) === false && byClass( cs.form, 'design-accordion-body' ).length === 0 && cs.byId('design-colour-primary') === null && csSaid() === '' && cs.setup._open.page === null
	&& ( cs.go( 'card' ), cs.go( 'page' ), isOpen( csBlock() ) === false && cs.byId('design-colour-primary') === null )
	&& ( toggle( csBlock() ), isOpen( csBlock() ) === true && cs.byId('design-colour-primary') !== null && cs.setup._open.page === 'colours' ) );

// What stands off the defaults: the hex of the brand colour, a knob by its label and the word of its position - as its strip shows it - and the mode by its label and its word
const csBrand = cs.byId('design-colour-primary');
csBrand.value = '#8b1d3f';
csBrand.fire('change');
choose( cs.byId('design-colour-mode'), 'dark' );
pressKey( cs, 'colours/saturation', 5 );
pressKey( cs, 'colours/temperature', 4 );
pressKey( cs, 'colours/harmony', 2 );

const csSays = {
	primary 			: TEXT['/_admin/design/label/primary']+ ' #8b1d3f',
	harmony 			: acSays( colourWord('harmony'), controlOf( cs, 'colours/harmony' ), 2 ),
	temperature 	: acSays( colourWord('temperature'), controlOf( cs, 'colours/temperature' ), 4 ),
	saturation 		: acSays( colourWord('saturation'), controlOf( cs, 'colours/saturation' ), 5 ),
	mode 					: TEXT['/_admin/design/colour/mode/label']+ ' '+ TEXT['/_admin/design/colour/mode/dark'],
};

toggle( csBlock() );

check( 'a closed head names what was moved: the brand colour by its hex, the knobs by their label and the word of the position - a number for a scale, a name for a choice - and the mode by its label and its word, in the order of the table, joined by a middle dot, and a knob nobody moved is not named',
	csSaid() === [ csSays.primary, csSays.harmony, csSays.temperature, csSays.saturation, csSays.mode ].join( acSeparator )
	&& csSays.saturation === colourWord('saturation')+ ' '+ SIGNED[4] && csSays.temperature === colourWord('temperature')+ ' '+ TEXT['/_admin/design/colour/temperature/4'] && csSays.harmony === colourWord('harmony')+ ' '+ TEXT['/_admin/design/colour/harmony/2']
	&& csSaid().indexOf( colourWord('contrast') ) === -1 && csSaid().indexOf( colourWord('depth') ) === -1 && byClass( csBlock(), 'design-accordion-summary' )[0].title === csSaid() );

toggle( csBlock() );

const csSecond = cs.byId('design-colour-secondary');
csSecond.value = '#0f766e';
csSecond.fire('change');
toggle( csBlock() );

check( '...a second colour that was chosen is named by its hex in the place of the Harmony it ends - which says nothing while one is set, as its strip lights no position - and the rest as it was',
	csSaid() === [ csSays.primary, TEXT['/_admin/design/label/secondary']+ ' #0f766e', csSays.temperature, csSays.saturation, csSays.mode ].join( acSeparator ) && csSaid().indexOf( csSays.harmony ) === -1
	&& cs.design._edit.colours.harmony === 2 );

toggle( csBlock() );
choose( cs.byId('design-colour-mode'), 'auto' );
toggle( csBlock() );

check( '...and a knob put back at the default is named no more: the mode at automatic leaves the head without it',
	csSaid().indexOf( csSays.mode ) === -1 && csSaid().split( acSeparator ).length === 4 );

cs.design._revert();

check( '...a reset draws the screen again from the stored selection: the block that was closed is closed still and its head says nothing, as at the defaults - and opened, its controls are those of the stored selection',
	isOpen( csBlock() ) === false && csSaid() === '' && ( toggle( csBlock() ), cs.byId('design-colour-primary').value === cs.design._data.colours.primary && shown( cs.byId('design-colour-mode') ) === cs.design._data.colours.mode ) );

const unsafe = panel( { brand : { light : { safe : false, ratio : 3.1, target : 4.5 } } } );
unsafe.tab( tabWord('colours') );

check( 'a brand colour text does not survive on says so under the colour, with the numbers - and says nothing where it does',
	unsafe.byId('design-brand-warning').hidden === false && unsafe.byId('design-brand-warning').textContent === say( '/_admin/design/msg/brand-unsafe', 3.1, 4.5 )
	&& colours.byId('design-brand-warning').hidden === true );

// The dimming of a section, the tone of the shadow of a card and the stripes of a table: a strip each, in the block of their object after the page's
const co = panel();
co.show( 'colours' );
const coSaid = function( object ) { return closedLine( co, object ) };

pressKey( co, 'knobs/section/dim', 2 );
pressKey( co, 'knobs/card/tone', 1 );
pressKey( co, 'knobs/tables/stripes', -1 );

check( 'the dimming of a section, the tone of the shadow of a card and the stripes of a table are a strip each in the block of their object on Colours, after the page\'s, and a head that is closed names a moved one - the label of the knob and the word of its position, a number for a scale and a name for a choice - while the head of the page, which nobody moved, says nothing',
	tableObjects( co.design._data.layout, 'colours' ).join() === 'page,header,footer,section,tiles,card,tables,forms,tabs,accordion'
	&& co.design._edit.knobs.section.dim === 2 && co.design._edit.knobs.card.tone === 1 && co.design._edit.knobs.tables.stripes === -1
	&& coSaid('section') === knobWord( 'section', 'dim' )+ ' '+ SIGNED[4] && coSaid('card') === knobWord( 'card', 'tone' )+ ' '+ TEXT['/_admin/design/knob/card/tone/more']
	&& coSaid('tables') === knobWord( 'tables', 'stripes' )+ ' '+ SIGNED[1] && coSaid('page') === '' );

const coGrey = panel();
coGrey.show( 'templates' );
choose( setSelect( coGrey, 'section' ), 'third' );
coGrey.show( 'colours' );

const coGreyRow = controlOf( coGrey, 'knobs/section/dim' );

check( 'a template that leaves the dimming out greys its row out on Colours as it does on any tab: the row is there, in the block of the section, its buttons off and none pressed, with the line that says whose decision that is - and no other row of the tab is grey',
	coGreyRow !== null && cardOf( coGreyRow ).dataset.object === 'section' && hasClass( coGreyRow, 'design-knob-row--off' ) && byTag( coGreyRow, 'button' ).length === 5
	&& byTag( coGreyRow, 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& ( byTag( coGreyRow, 'small' )[0] || {} ).textContent === say( '/_admin/design/hint/unanswered', knobWord( 'section', 'dim' ) )
	&& offRows( coGrey ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/section/dim' );

// A frame is asked for its knobs as a set is: the second template of the header leaves the height out
const frameLess = listing().parts;
frameLess.filter( function( one ) { return one.part === 'header' } )[0].catalogue.second.knobs = without( KNOBS.header, [ 'height', 'position', 'cta' ] );

const fg = panel( { parts : frameLess } );
fg.show( 'surfaces' );
pressKey( fg, 'knobs/header/height', 1 );
fg.show( 'templates' );
pressKey( fg, 'knobs/header/position', -1 );
pressKey( fg, 'knobs/header/cta', 1 );
choose( setSelect( fg, 'header' ), 'second' );

check( 'a template of the header that leaves the position and the button out greys their strips out under its select at once, on the tab where it was chosen, and puts both back at the position the defaults hold - not in the middle, not where they were moved to',
	fg.design._edit.knobs.header.position === fg.design._data.defaults.knobs.header.position && fg.design._edit.knobs.header.cta === fg.design._data.defaults.knobs.header.cta
	&& fg.design._data.defaults.knobs.header.position !== 0 && offRows( fg ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/header/position,knobs/header/cta'
	&& cardOf( controlOf( fg, 'knobs/header/position' ) ) === cardOf( setSelect( fg, 'header' ) ) && byTag( controlOf( fg, 'knobs/header/cta' ), 'button' ).every( function( button ) { return button.disabled === true } )
	&& pressedKey( fg, 'knobs/header/cta' ) === 'false,false,false' );

// ...and a footer is asked for its knobs the same way: the second template of the footer leaves the position out
const footerLess = listing().parts;
footerLess.filter( function( one ) { return one.part === 'footer' } )[0].catalogue.second.knobs = without( KNOBS.footer, [ 'position' ] );

const ff = panel( { parts : footerLess } );
ff.show( 'templates' );
pressKey( ff, 'knobs/footer/position', -1 );

check( 'the position of the footer is a strip of three on Templates, under the select of the template, and a press on it moves the position: the left button is lit and the knob stands at -1',
	ff.design._edit.knobs.footer.position === -1 && pressedKey( ff, 'knobs/footer/position' ) === 'true,false,false' && cardOf( controlOf( ff, 'knobs/footer/position' ) ) === cardOf( setSelect( ff, 'footer' ) ) && offRows( ff ).length === 0 );

choose( setSelect( ff, 'footer' ), 'second' );

check( '...and a template of the footer that leaves it out greys the strip out under its select at once and puts the knob back at the position the defaults hold - not where it was moved to - with the strips of the line, which that template answers to, still on',
	ff.design._edit.knobs.footer.position === ff.design._data.defaults.knobs.footer.position && ff.design._edit.knobs.footer.position !== -1
	&& offRows( ff ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/footer/position'
	&& byTag( controlOf( ff, 'knobs/footer/position' ), 'button' ).every( function( button ) { return button.disabled === true } ) && pressedKey( ff, 'knobs/footer/position' ) === 'false,false,false'
	&& [ 'surfaces', 'colours' ].every( function( tab ) { ff.show( tab ); return offRows( ff ).length === 0 } ) );

fg.show( 'surfaces' );

const fgHeight = controlOf( fg, 'knobs/header/height' );

check( 'a frame answers to the knobs its stylesheet declares ladders for, as a set does: a header template that leaves the height out puts the position of it back in the middle and greys its row out on Surfaces, the space and the line of the header staying on - and its navigation on Type is on',
	fg.design._edit.parts.header === 'second' && fg.design._edit.knobs.header.height === 0 && hasClass( fgHeight, 'design-knob-row--off' ) && byTag( fgHeight, 'button' ).every( function( button ) { return button.disabled === true } )
	&& offRows( fg ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/header/height'
	&& byTag( controlOf( fg, 'knobs/header/space' ), 'button' ).every( function( button ) { return button.disabled === false } ) && byTag( controlOf( fg, 'knobs/header/border-size' ), 'button' ).every( function( button ) { return button.disabled === false } )
	&& ( fg.show( 'type' ), offRows( fg ).length === 0 ) );

console.log('');


// --- The form: an object of the page with its own tab ---------------------------------------------------
//
// The form is the ninth object and the eighth part: a tab Form after the tables, with a block for each aspect the table puts a control of it on -
// Base, Text, Surface and Colour, none on Motion - and in each the controls the table names, in its order: the select of the template, the size of the
// text and the switch for the case of the labels, the space, the corners, the line and the place of a label, the tone of the lines and of the labels. An
// ink is no strip and no switch but a select of the tones of the page with none first, and the template that does not answer to it greys it out

console.log( 'The form' );

const fm = panel();

check( 'the form is an object of the page after the tables and before the tabs, a tab of its own, and has a block on Base, Text, Surface and Colour - in the order of the table, and none on Motion',
	fm.design._data.layout.objects.slice( -3 )[0] === 'forms' && fm.design._data.layout.objects.indexOf('forms') === fm.design._data.layout.objects.indexOf('tables') + 1
	&& aspectsOf( fm.design._data.layout, 'forms' ).join() === 'templates,type,surfaces,colours' && fm.design._data.kinds.forms.edge === 'ink' && fm.design._data.kinds.forms['label-case'] === 'toggle' );

const fmRows = {};

[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {
	fm.show( tab );
	drawnRows( fm ).filter( function( row ) { return row.object === 'forms' } ).forEach( function( row ) { fmRows[tab] = row.controls } );
} );

check( '...and each block holds the controls the table gives it, in the order of the table: the select of the template; the size of the text and the switch of the case; the space, the corners, the line and the place of the label; the tone of the lines and of the labels',
	JSON.stringify( fmRows ) === JSON.stringify( {
		templates : [ 'parts/forms' ],
		type : [ 'knobs/forms/scale', 'knobs/forms/label-case' ],
		surfaces : [ 'knobs/forms/space', 'knobs/forms/corners', 'knobs/forms/line', 'knobs/forms/labels' ],
		colours : [ 'knobs/forms/edge', 'knobs/forms/label' ],
	} ) );

fm.show( 'colours' );

const fmEdge = controlOf( fm, 'knobs/forms/edge' );
const fmEdgeSelect = byTag( fmEdge, 'select' )[0];

check( 'the tone of the lines is a select of the tones of the page with none first, the label of the knob over it with its icon and its note, and it stands at none to begin with - the tone the template gives',
	isSelect( fmEdge ) && hasClass( fmEdge, 'design-knob-row' ) && byTag( fmEdge, 'button' ).length === 0 && fmEdgeSelect.disabled === false && fmEdgeSelect.id === 'design-knob-forms-edge'
	&& JSON.stringify( offered( fmEdgeSelect ) ) === JSON.stringify( [ [ '', TEXT['/_admin/design/label/own'] ] ].concat( INKS.map( function( tone ) { return [ tone, TEXT['/_admin/design/ink/'+ tone] ] } ) ) )
	&& shown( fmEdgeSelect ) === '' && rowName( fmEdge ) === knobWord( 'forms', 'edge' ) && byTag( byClass( fmEdge, 'design-knob-label' )[0], 'svg' ).length === 1 );

choose( fmEdgeSelect, 'primary' );
fm.design._previewNow();

const fmPreview = fm.requests[fm.requests.length - 1];

check( 'choosing a tone moves the knob to the tone - a word, not a position -, asks for a preview with it, looks at the form and is a change the screen can save; the other ink stays where it was',
	fm.design._edit.knobs.forms.edge === 'primary' && fm.design._edit.knobs.forms.label === '' && fmPreview.endpoint === 'preview' && fmPreview.payload.knobs.forms.edge === 'primary' && fmPreview.payload.knobs.forms.label === ''
	&& fm.design._target === 'forms' && fm.design._isDirty() === true );
check( '...and a closed head of the block names it by the label of the knob and the word of the tone, while the head of the other tones says nothing',
	closedLine( fm, 'forms' ) === knobWord( 'forms', 'edge' )+ ' '+ TEXT['/_admin/design/ink/primary'] );

fm.show( 'colours' );
choose( byTag( controlOf( fm, 'knobs/forms/edge' ), 'select' )[0], '' );

check( '...and none again is the knob back at the tone the template gives: an empty word, the head says nothing and there is nothing to save',
	fm.design._edit.knobs.forms.edge === '' && closedLine( fm, 'forms' ) === '' && fm.design._isDirty() === false );

// A template that does not answer to the knobs it leaves out: the one with no corners and no case of the labels greys them out, an ink it declares stays
fm.show( 'templates' );
choose( setSelect( fm, 'forms' ), 'second' );
fm.show( 'type' );
const fmCase = controlOf( fm, 'knobs/forms/label-case' );
fm.show( 'surfaces' );
const fmCorners = controlOf( fm, 'knobs/forms/corners' );

check( 'a template of the form that leaves the case of the labels and the corners out greys the switch and the strip out and puts both back at the default; the ones it answers to stay on',
	isSwitch( fmCase ) && hasClass( fmCase, 'design-knob-row--off' ) && byTag( fmCase, 'input' )[0].disabled === true && hasClass( fmCorners, 'design-knob-row--off' ) && byTag( fmCorners, 'button' ).every( function( button ) { return button.disabled === true } )
	&& offRows( fm ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/forms/corners' && fm.design._edit.knobs.forms['label-case'] === 0 );

// An ink that the chosen template does not answer to is switched off, greyed out with the line that says so, and goes back to none with the change of the template
fm.show( 'colours' );
choose( byTag( controlOf( fm, 'knobs/forms/edge' ), 'select' )[0], 'focus' );
fm.show( 'templates' );
choose( setSelect( fm, 'forms' ), 'third' );
fm.show( 'colours' );

const fmGrey = controlOf( fm, 'knobs/forms/edge' );

check( 'a template of the form that leaves the tone of the lines out switches the select off and greys its row out, with the line that says so in the place of the note, and the tone chosen goes back to none - the other ink, which it answers to, stays on',
	hasClass( fmGrey, 'design-knob-row--off' ) && byTag( fmGrey, 'select' )[0].disabled === true && fm.design._edit.knobs.forms.edge === '' && shown( byTag( fmGrey, 'select' )[0] ) === ''
	&& ( byTag( fmGrey, 'small' )[0] || {} ).textContent !== TEXT['/_admin/design/knob/forms/edge/note'] && byTag( byTag( controlOf( fm, 'knobs/forms/label' ), 'select' )[0], 'option' ).length === INKS.length + 1
	&& byTag( controlOf( fm, 'knobs/forms/label' ), 'select' )[0].disabled === false && offRows( fm ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/forms/edge' );

const fmOff = panel( { knobs : ( function() { const k = listing().knobs; k.forms.edge = 'focus'; return k } )() } );

fmOff.show( 'colours' );
check( 'an ink the stored setup holds is the tone of the select, when the template answers to it',
	shown( byTag( controlOf( fmOff, 'knobs/forms/edge' ), 'select' )[0] ) === 'focus' && fmOff.design._edit.knobs.forms.edge === 'focus' );

// The ink of a title and of a subtitle: six more inks, a select of the tones under the content section, the tiles and the card on the Colours tab
const titleInkRows = [ [ 'section', 'title-ink' ], [ 'section', 'subtitle-ink' ], [ 'tiles', 'title-ink' ], [ 'tiles', 'subtitle-ink' ], [ 'card', 'title-ink' ], [ 'card', 'subtitle-ink' ] ];
const inkPanel = panel();

inkPanel.show( 'colours' );

check( 'the colour of a title and of a subtitle is a select of the tones of the page for the content section, the tiles and the card on the Colours tab, each after the controls its object has there, with none first - the tone the template gives - and standing at none to begin with',
	titleInkRows.every( function( entry ) {
		const row = controlOf( inkPanel, 'knobs/'+ entry[0]+ '/'+ entry[1] );
		const select = byTag( row, 'select' )[0];
		return isSelect( row ) && select.disabled === false && select.id === 'design-knob-'+ entry[0]+ '-'+ entry[1] && shown( select ) === '' && byTag( select, 'option' ).length === INKS.length + 1
			&& rowName( row ) === knobWord( entry[0], entry[1] ) && byTag( byClass( row, 'design-knob-label' )[0], 'svg' ).length === 1;
	} )
	&& drawnRows( inkPanel ).filter( function( row ) { return [ 'section', 'tiles', 'card' ].indexOf( row.object ) !== -1 } ).map( function( row ) { return row.object+ ':'+ row.controls.slice( -2 ).join() } ).join( ' ' )
		=== 'section:knobs/section/title-ink,knobs/section/subtitle-ink tiles:knobs/tiles/title-ink,knobs/tiles/subtitle-ink card:knobs/card/title-ink,knobs/card/subtitle-ink' );

titleInkRows.forEach( function( entry ) {
	choose( byTag( controlOf( inkPanel, 'knobs/'+ entry[0]+ '/'+ entry[1] ), 'select' )[0], 'accent' );
} );
inkPanel.design._previewNow();

const inkPreview = inkPanel.requests[inkPanel.requests.length - 1];

check( '...choosing a tone for each moves the six knobs to the word of the tone, asks for a preview with them and is a change the screen can save; choosing none again puts each back at none',
	titleInkRows.every( function( entry ) { return inkPanel.design._edit.knobs[entry[0]][entry[1]] === 'accent' && inkPreview.payload.knobs[entry[0]][entry[1]] === 'accent' } ) && inkPanel.design._isDirty() === true
	&& ( function() {
		titleInkRows.forEach( function( entry ) { choose( byTag( controlOf( inkPanel, 'knobs/'+ entry[0]+ '/'+ entry[1] ), 'select' )[0], '' ) } );
		return titleInkRows.every( function( entry ) { return inkPanel.design._edit.knobs[entry[0]][entry[1]] === '' } ) && inkPanel.design._isDirty() === false;
	} )() );

// A grade: the strip of five whose first button is the kernel's own, 0, and the four after it grow to the right - the rule under a title, the first of them
const gradePanel = panel();
const ruleName = knobWord( 'section', 'rule' );

gradePanel.show( 'surfaces' );

check( 'a grade is a strip of five buttons that only grow: 0, +1, +2, +3 and +4 from the left, the first - the kernel\'s own - pressed to begin with; the other kinds keep their strips',
	gradePanel.design._data.kinds.section.rule === 'grade' && stripLabelsOf( gradePanel, ruleName ) === '0,+1,+2,+3,+4' && gradePanel.pressed( ruleName ) === 'true,false,false,false,false'
	&& gradePanel.steps( ruleName ).map( function( button ) { return button.dataset.value } ).join() === '0,1,2,3,4'
	&& stripLabelsOf( gradePanel, knobWord( 'section', 'space' ) ) === SIGNED.join() );

gradePanel.move( ruleName, 4 );

check( '...and a press moves the knob to the position, 4 for the last: the rung full, a change the screen can save, and the first button back puts it at the kernel\'s',
	gradePanel.design._edit.knobs.section.rule === 4 && gradePanel.pressed( ruleName ) === 'false,false,false,false,true' && gradePanel.design._isDirty() === true
	&& ( function() { gradePanel.move( ruleName, 0 ); return gradePanel.design._edit.knobs.section.rule === 0 && gradePanel.design._isDirty() === false } )() );

console.log('');


// --- The features that bring variants ------------------------------------------------------------
//
// An active feature that ships stylesheets in a design/ directory of its own brings variants and knobs into the panel
// (Setup::features()). The variant is chosen on the Templates view, in a block of the feature after the blocks of the seven
// parts, and the knobs of the variant stand on the tab their head names, in a block of the feature after the blocks of the
// objects - all of it drawn from the table design/list hands over, as the rest of the screen is. The checks read the stand-in's
// answer (featured()) and the controls drawn from it: the names are the features' own - the manifest's for a feature, the head's
// for a variant and for a knob - and every other word is the panel's, read by its key

console.log( 'Features' );

const fx = panel( featured() );
const fxData = fx.design._data.features;
const fxOrder = Object.keys( fxData );
const fxZinc = fxData.awning;
const fxLoud = fxZinc.catalogue.loud.knobs;
const fxRegular = fxZinc.catalogue.regular.knobs;
const fxPlace = function( screen, key, knob ) { return screen.design._data.layout.controls[ knob === undefined ? 'features/'+ key+ '/set' : 'features/'+ key+ '/knobs/'+ knob ] };

/** The control of a feature on the tab the table puts it on, which is opened for it: the choice of the variant, or a knob of it */
const fxControl = function( screen, key, knob ) {
	screen.show( fxPlace( screen, key, knob )[0] );
	return controlOf( screen, 'features/'+ key+ ( knob === undefined ? '/set' : '/knobs/'+ knob ) );
};
const fxBlock = function( screen, key ) { return lensBlock( screen, key ) || null };
const fxObjects = function( screen ) { return tableObjects( screen.design._data.layout, screen.lens ) };
const fxSelect = function( screen, key ) { return byTag( fxControl( screen, key ), 'select' )[0] || null };
/** What the head of the block of a feature says on a tab, with the block closed - another block opened in its place */
const fxSaid = function( screen, tab, key ) {
	screen.show( tab );
	return closedLine( screen, key );
};
const fxFeatures = function( screen ) { return JSON.stringify( screen.design._edit.features ) };
const fxLastPayload = function( screen ) { screen.design._previewNow(); return screen.requests[screen.requests.length - 1].payload };

const fxByName = fxOrder.slice().sort( function( a, b ) { return fxData[a].name.localeCompare( fxData[b].name ) } );

check( 'a feature that brings variants has a block of its own on the Templates view, after the blocks of the parts: the table has one object more for it, after the eight, in the order of the answer - by the names of the features, not by their keys - and the tab is drawn from it',
	fxOrder.join() === fxByName.join() && fxOrder.join() !== fxOrder.slice().sort().join() && fxOrder.length === 2
	&& JSON.stringify( fx.design._data.layout.objects ) === JSON.stringify( OBJECTS.concat( fxOrder ) )
	&& JSON.stringify( drawnRows( fx ) ) === JSON.stringify( tableRows( fx.design._data.layout, 'templates' ) )
	&& fxObjects( fx ).join() === tplRows.map( function( row ) { return row.object } ).concat( fxOrder ).join() );
check( '...each feature is a tab after the eight objects, in the order of the answer, and an option of the select in the same place, both named by the feature: the name its manifest gives it, which is no text of the panel - and the block of its variant is the Base of the object, like every other',
	fxOrder.every( function( key ) { return fx.byId('design-tab-'+ key).textContent === fxData[key].name && TEXT['/_admin/design/object/'+ key+ '/label'] === undefined && byClass( fxBlock( fx, key ), 'design-accordion-name' )[0].textContent === tabWord('templates') } )
	&& byTag( byClass( fx.pane, 'design-views' )[0], 'button' ).slice( OBJECTS.length, OBJECTS.length + fxOrder.length ).map( function( tab ) { return tab.textContent } ).join() === fxOrder.map( function( key ) { return fxData[key].name } ).join()
	&& offered( fx.byId('design-object-select') ).slice( OBJECTS.length, OBJECTS.length + fxOrder.length ).map( function( option ) { return option[1] } ).join() === fxOrder.map( function( key ) { return fxData[key].name } ).join()
	&& blocksOf( fx.form ).every( function( card ) { return hasClass( card, 'design-accordion' ) } ) );

const fxSet = fxSelect( fx, 'awning' );

check( '...the variant of a feature that brings two is chosen by a select of them - the names the heads give them - with the one word of label/template over it and the description of the chosen one under it, as for a part: it stands on the variant the feature has before anything is chosen',
	fxSet !== null && JSON.stringify( offered( fxSet ) ) === JSON.stringify( Object.keys( fxZinc.catalogue ).map( function( set ) { return [ set, fxZinc.catalogue[set].name ] } ) )
	&& shown( fxSet ) === fxZinc['default'] && fxSet.id === 'design-feature-awning' && fxSet.parent.dataset.control === 'features/awning/set'
	&& byTag( fxSet.parent, 'label' )[0].textContent === TEXT['/_admin/design/label/template'] && byTag( fxSet.parent, 'label' )[0].getAttribute('for') === fxSet.id
	&& byTag( fxSet.parent, 'small' )[0].textContent === fxZinc.catalogue[ fxZinc['default'] ].description
	&& byTag( cardOf( fxSet ), 'select' ).length === 1 + Object.keys( fxZinc.styles ).length && cardOf( fxSet ).dataset.object === 'awning' );
check( '...a feature with one variant only has nothing to choose: its block shows the description of the variant and no select',
	fxSelect( fx, 'mosaic' ) === null && byTag( fxBlock( fx, 'mosaic' ), 'select' ).length === 0
	&& byTag( controlOf( fx, 'features/mosaic/set' ), 'small' )[0].textContent === fxData.mosaic.catalogue.regular.description );

// The heads of the closed blocks of the features say nothing while the variant is the one the feature has before anything is chosen, and name the variant otherwise
const fxHeadsAtStart = fxOrder.map( function( key ) { return fxSaid( fx, 'templates', key ) } );

choose( fxSelect( fx, 'awning' ), 'loud' );

check( 'choosing a variant is the selection on screen: the choice for the feature has the variant and no knob yet, the description under the select follows, the screen is no longer the stored one - the way back is shown - and the select stands where it was',
	fxFeatures( fx ) === JSON.stringify( { awning : { set : 'loud', knobs : {}, styles : {} } } ) && fxSelect( fx, 'awning' ).value === 'loud'
	&& byTag( fxSelect( fx, 'awning' ).parent, 'small' )[0].textContent === fxZinc.catalogue.loud.description
	&& fx.design._isDirty() === true && fx.byId('design-reset').hidden === false );
check( '...the preview is asked for with the choices for the features beside the rest of the selection: an object of entries, the variant and the position of each knob that was moved',
	JSON.stringify( fxLastPayload( fx ).features ) === JSON.stringify( { awning : { set : 'loud', knobs : {}, styles : {} } } ) && fx.requests[fx.requests.length - 1].endpoint === 'preview' );
check( '...and the head of the closed block names the variant, as the catalogue of the answer has it: nothing at the start, and nothing of the feature that has the one variant',
	fxHeadsAtStart.join() === ',' && fxSaid( fx, 'templates', 'awning' ) === fxZinc.catalogue.loud.name && fxSaid( fx, 'templates', 'mosaic' ) === '' );

choose( fxSelect( fx, 'awning' ), 'regular' );

check( '...the variant the feature has before anything is chosen, chosen again, is no choice: the entry is gone, the screen is the stored one, the way back is hidden, and the next preview carries no feature',
	fxFeatures( fx ) === '{}' && fx.design._isDirty() === false && fx.byId('design-reset').hidden === true && JSON.stringify( fxLastPayload( fx ).features ) === '{}'
	&& fxSaid( fx, 'templates', 'awning' ) === '' );

// The knobs stand on the tab their head names, in a block of the feature after the blocks of the objects
const fxTabs = [ 'type', 'surfaces', 'colours', 'motion' ];
const fxTabProblems = fxTabs.filter( function( tab ) {
	fx.show( tab );
	const objects = fxObjects( fx );
	const own = objects.filter( function( object ) { return fxOrder.indexOf( object ) !== -1 } );
	return own.length === 0 || objects.slice( objects.length - own.length ).join() !== own.join() || own.join() !== fxOrder.filter( function( key ) { return own.indexOf( key ) !== -1 } ).join()
		|| JSON.stringify( drawnRows( fx ) ) !== JSON.stringify( tableRows( fx.design._data.layout, tab ) );
} );
const fxKnobsAgree = fxOrder.every( function( key ) {
	return Object.keys( fxData[key].catalogue ).every( function( set ) {
		return Object.keys( fxData[key].catalogue[set].knobs ).every( function( knob ) { return fxPlace( fx, key, knob )[0] === fxData[key].catalogue[set].knobs[knob].tab && fxPlace( fx, key, knob )[1] === key } );
	} );
} );

check( 'the knobs of a feature stand on the tab their head names, each of those tabs having a block of the feature after the blocks of the objects, in the order of the features - and the rows of a tab are the rows the table gives it, in its order'
	+ ( fxTabProblems.length === 0 ? '' : ' - not so for '+ fxTabProblems.join() ), fxTabProblems.length === 0 && fxKnobsAgree === true );
check( '...a knob is placed where the first variant that declares it puts it, and a knob only one variant declares has its row for all of them: the table has the knobs of the loud variant too',
	Object.keys( fxLoud ).every( function( knob ) { return fxPlace( fx, 'awning', knob ) !== undefined } ) && Object.keys( fxRegular ).length < Object.keys( fxLoud ).length
	&& Object.keys( fxLoud ).filter( function( knob ) { return fxRegular[knob] === undefined } ).every( function( knob ) { return fxControl( fx, 'awning', knob ) !== null } ) );

// A scale and a choice are strips, as the knobs of the parts are
const fxSizeKey = 'features/awning/knobs/size';
const fxSizeRow = fxControl( fx, 'awning', 'size' );

check( 'a scale a feature brings is a strip of five positions, −2 to +2, under the label its head gives it, the middle one lit while nothing is chosen - the row says which control it is and which knob',
	fxSizeRow.dataset.control === fxSizeKey && fxSizeRow.dataset.knob === 'size' && rowName( fxSizeRow ) === fxRegular.size.label && hasClass( fxSizeRow, 'design-knob-row--off' ) === false
	&& byTag( fxSizeRow, 'button' ).map( function( button ) { return button.textContent } ).join() === SIGNED.join() && pressedKey( fx, fxSizeKey ) === 'false,false,true,false,false' );

pressKey( fx, fxSizeKey, 1 );

check( '...pressing a position chooses it: the feature is chosen at the variant it stands at, with the position of the knob - the strip lights it - and the preview carries it',
	fxFeatures( fx ) === JSON.stringify( { awning : { set : 'regular', knobs : { size : 1 }, styles : {} } } ) && pressedKey( fx, fxSizeKey ) === 'false,false,false,true,false'
	&& fx.design._isDirty() === true && JSON.stringify( fxLastPayload( fx ).features ) === fxFeatures( fx ) );

pressKey( fx, fxSizeKey, 0 );

check( '...and the position put back is no choice: a feature at its variant with every knob at the position its head says is the feature as it is, and has no entry - the screen is the stored one again',
	fxFeatures( fx ) === '{}' && fx.design._isDirty() === false && pressedKey( fx, fxSizeKey ) === 'false,false,true,false,false' );

pressKey( fx, fxSizeKey, 1 );

const fxLineRow = fxControl( fx, 'awning', 'line' );
const fxLineKey = 'features/awning/knobs/line';

check( 'a choice a feature brings is a strip of three, the words its head gives the positions - less, the default and more - under its label, the middle one lit while nothing is chosen',
	rowName( fxLineRow ) === fxRegular.line.label && byTag( fxLineRow, 'button' ).map( function( button ) { return button.textContent } ).join() === [ fxRegular.line.options.less, fxRegular.line.options['default'], fxRegular.line.options.more ].join()
	&& pressedKey( fx, fxLineKey ) === 'false,true,false' );

pressKey( fx, fxLineKey, 1 );

check( '...and a position of it is chosen as one of a scale is: the entry holds the knobs that were moved, in the order they were moved',
	fxFeatures( fx ) === JSON.stringify( { awning : { set : 'regular', knobs : { size : 1, line : 1 }, styles : {} } } ) && pressedKey( fx, fxLineKey ) === 'false,false,true' );

// A ground is a select of the surfaces of the palette, an ink one of the tones of its text and lines
const fxGroundSelect = function() { return byTag( fxControl( fx, 'awning', 'submit' ), 'select' )[0] };
const fxInkSelect = function() { return byTag( fxControl( fx, 'awning', 'label' ), 'select' )[0] };
const fxWordsOf = function( words, kind ) { return words.map( function( word ) { return [ word, TEXT['/_admin/design/'+ kind+ '/'+ word] ] } ) };

check( 'a ground a feature brings is a select of the surfaces of the palette - the words the panel has for them, in the palette\'s order, as the answer lists them - under the label its head gives it, standing on the surface its head says',
	fxGroundSelect() !== null && fxGroundSelect().id === 'design-feature-awning_submit' && JSON.stringify( offered( fxGroundSelect() ) ) === JSON.stringify( fxWordsOf( fx.design._data.grounds, 'ground' ) )
	&& fx.design._data.grounds.length > 1 && offered( fxGroundSelect() ).every( function( pair ) { return typeof pair[1] === 'string' && pair[1] !== '' && pair[1].indexOf('/_admin/') !== 0 } )
	&& shown( fxGroundSelect() ) === fxRegular.submit['default'] && byTag( fxGroundSelect().parent, 'label' )[0].textContent === fxRegular.submit.label && fxGroundSelect().disabled === false );
check( '...an ink one of the tones - the words of the panel for them, as the answer lists them - the same way',
	fxInkSelect() !== null && fxInkSelect().id === 'design-feature-awning_label' && JSON.stringify( offered( fxInkSelect() ) ) === JSON.stringify( fxWordsOf( fx.design._data.inks, 'ink' ) )
	&& fx.design._data.inks.length > 1 && offered( fxInkSelect() ).every( function( pair ) { return typeof pair[1] === 'string' && pair[1] !== '' && pair[1].indexOf('/_admin/') !== 0 } )
	&& shown( fxInkSelect() ) === fxRegular.label['default'] && byTag( fxInkSelect().parent, 'label' )[0].textContent === fxRegular.label.label );

const fxOtherGround = fx.design._data.grounds.filter( function( word ) { return word !== fxRegular.submit['default'] } )[0];
const fxOtherInk = fx.design._data.inks.filter( function( word ) { return word !== fxRegular.label['default'] } )[0];

choose( fxGroundSelect(), fxOtherGround );
choose( fxInkSelect(), fxOtherInk );

check( '...choosing a surface or a tone chooses it for the knob: the entry holds the word, and the preview carries it',
	JSON.stringify( fx.design._edit.features.awning.knobs ) === JSON.stringify( { size : 1, line : 1, submit : fxOtherGround, label : fxOtherInk } ) && fx.design._edit.features.awning.set === 'regular'
	&& fxGroundSelect().value === fxOtherGround && fxInkSelect().value === fxOtherInk && JSON.stringify( fxLastPayload( fx ).features ) === fxFeatures( fx ) );

// The knobs of the loud variant that the regular one does not declare are drawn, greyed out and switched off, in the words of a knob of a part that its template does not answer to
const fxFlipRow = function() { return fxControl( fx, 'awning', 'flip' ) };
const fxHalf = function( half ) { return fx.byId('design-feature-awning_flip_'+ half) };
const fxUnanswered = function( knob ) { return say( '/_admin/design/hint/unanswered', knob.label ) };

check( 'a knob the chosen variant does not declare has its row all the same, greyed out and switched off - the strip of a scale, the two colours of a pair - with the sentence a knob of a part has, naming the knob',
	hasClass( fxControl( fx, 'awning', 'turn' ), 'design-knob-row--off' ) === true && byTag( fxControl( fx, 'awning', 'turn' ), 'button' ).every( function( button ) { return button.disabled === true && button.getAttribute('aria-pressed') === 'false' } )
	&& byTag( byClass( fxControl( fx, 'awning', 'turn' ), 'design-knob-text' )[0], 'small' )[0].textContent === fxUnanswered( fxLoud.turn )
	&& byTag( fxFlipRow(), 'input' ).length === 2 && byTag( fxFlipRow(), 'input' ).every( function( input ) { return input.disabled === true } )
	&& byTag( byClass( fxFlipRow(), 'design-knob-text' )[0], 'small' )[0].textContent === fxUnanswered( fxLoud.flip )
	&& byTag( fxControl( fx, 'awning', 'submit' ), 'select' )[0].disabled === false );

fx.show( 'templates' );
choose( fxSelect( fx, 'awning' ), 'loud' );

check( '...the loud variant chosen, the same rows are live: the entry keeps the knobs that were moved, since the loud variant declares them too',
	fxFeatures( fx ) === JSON.stringify( { awning : { set : 'loud', knobs : { size : 1, line : 1, submit : fxOtherGround, label : fxOtherInk }, styles : {} } } )
	&& hasClass( fxControl( fx, 'awning', 'turn' ), 'design-knob-row--off' ) === false && byTag( fxControl( fx, 'awning', 'turn' ), 'button' ).every( function( button ) { return button.disabled === false } )
	&& byTag( fxFlipRow(), 'input' ).every( function( input ) { return input.disabled === false } ) );

// A ground and an ink that only the loud variant declares: their selects go the way the strips and the colours do
const fxLateAnswer = featuresAnswer();

fxLateAnswer.awning.catalogue.loud.knobs.edge = featureKnob( 'ground', 'colours', 'Edge', 'brand-safe' );
fxLateAnswer.awning.catalogue.loud.knobs.stripe = featureKnob( 'ink', 'colours', 'Stripe', 'subtitle' );

const fxLate = panel( { features : fxLateAnswer, layout : layoutWith( fxLateAnswer ) } );
const fxLateSelect = function( knob ) { return byTag( fxControl( fxLate, 'awning', knob ), 'select' )[0] };
const fxLateOff = [ fxLateSelect('edge').disabled, fxLateSelect('stripe').disabled, byTag( fxControl( fxLate, 'awning', 'edge' ), 'small' ).map( function( small ) { return small.textContent } ).join() ];

choose( fxSelect( fxLate, 'awning' ), 'loud' );

check( '...a ground and an ink that only the loud variant declares have their selects switched off, with the sentence of a knob the variant does not answer to under the label, while the regular one is chosen - and the loud one switches them on and takes the sentence away',
	JSON.stringify( fxLateOff ) === JSON.stringify( [ true, true, fxUnanswered( fxLateAnswer.awning.catalogue.loud.knobs.edge ) ] )
	&& fxLateSelect('edge').disabled === false && fxLateSelect('stripe').disabled === false && byTag( fxControl( fxLate, 'awning', 'edge' ), 'small' ).length === 0 );

// A pair of colours: the label over two colour inputs, a light half and a dark half
check( 'a pair of colours is the label its head gives over two colour inputs, the light half and the dark half, each under the word of its half, standing at the pair its head says - and under them the line of what they measure, which is hidden while nothing was measured against them',
	rowName( fxFlipRow() ) === fxLoud.flip.label && fxFlipRow().dataset.knob === 'flip' && fxFlipRow().dataset.control === 'features/awning/knobs/flip'
	&& fxHalf('light') !== null && fxHalf('dark') !== null && fxHalf('light').type === 'color' && fxHalf('dark').type === 'color'
	&& fxHalf('light').value === fxLoud.flip['default'][0] && fxHalf('dark').value === fxLoud.flip['default'][1]
	&& byClass( fxFlipRow(), 'design-colour-half' ).map( function( box ) { return byTag( box, 'small' )[0].textContent } ).join() === [ TEXT['/_admin/design/label/light'], TEXT['/_admin/design/label/dark'] ].join()
	&& fx.byId('design-feature-awning_flip_contrast').hidden === true );

check( '...each colour input has a name of its own that says which colour and which half: the word that is drawn beside it, and after it the label of the knob - what a person is told who reaches the input alone, and what a voice control matches by the word it sees',
	[ 'light', 'dark' ].every( function( half ) {
		const word = TEXT['/_admin/design/label/'+ half];
		return fxHalf( half ).getAttribute('aria-label') === word+ ' - '+ fxLoud.flip.label && byClass( fxFlipRow(), 'design-colour-half' ).some( function( box ) { return byTag( box, 'small' )[0].textContent === word && byTag( box, 'input' )[0] === fxHalf( half ) } );
	} ) );

/*	The ids of the controls of a feature: a key and a knob are letters, digits and hyphens, so a hyphen between the parts of an id would make the light half of
	the colour "x" and a knob that is called "x-light" one id - in the one block of the feature, where both of them stand at once	*/
const fxClashData = featuresAnswer();
fxClashData.a = { name : 'Single', active : true, 'default' : 'regular', catalogue : { regular : { name : 'Regular', description : '', knobs : {
	x : featureKnob( 'colour', 'colours', 'X', [ '#b03a2e', '#e8705f' ] ),
	'x-light' : featureKnob( 'ink', 'colours', 'X light', 'subtitle' ),
} } }, set : '', knobs : [] };

const fxClash = panel( { features : fxClashData, layout : layoutWith( fxClashData ) } );
const fxClashHalf = byTag( fxControl( fxClash, 'a', 'x' ), 'input' )[0];
const fxClashInk = byTag( controlOf( fxClash, 'features/a/knobs/x-light' ), 'select' )[0];

check( 'the controls of a feature have ids that no knob can make equal: the light half of the colour "x" and the ink "x-light" stand in one block as two elements with two ids, each found by its own - and the label of the ink points at the ink',
	fxClashHalf !== undefined && fxClashInk !== undefined && fxClashHalf.id !== fxClashInk.id
	&& fxClash.byId( fxClashHalf.id ) === fxClashHalf && fxClash.byId( fxClashInk.id ) === fxClashInk && byTag( fxClashInk.parent, 'label' )[0].getAttribute('for') === fxClashInk.id );

fxHalf('light').value = '#112233';
fxHalf('light').fire('change');
fxHalf('dark').value = '#aabbcc';
fxHalf('dark').fire('change');

check( '...a colour put into one half is the pair with the other as it stood, in the order light and dark - and the preview carries the pair',
	JSON.stringify( fx.design._edit.features.awning.knobs.flip ) === JSON.stringify( [ '#112233', '#aabbcc' ] ) && fx.design._edit.features.awning.set === 'loud'
	&& JSON.stringify( fxLastPayload( fx ).features.awning.knobs.flip ) === JSON.stringify( [ '#112233', '#aabbcc' ] ) );

fxControl( fx, 'awning', 'turn' );
pressKey( fx, 'features/awning/knobs/turn', 2 );

// The heads of the closed blocks say what stands off the defaults: the label and the word of the value
check( 'the head of a closed block of a feature says the knobs on its tab that stand elsewhere than its head says they do - the label and the word of the value, a position as its strip shows it, a surface or a tone by the panel\'s word, a pair by its two colours - joined as the heads of the objects are',
	[ fxSaid( fx, 'type', 'awning' ) ].concat( [ fxSaid( fx, 'surfaces', 'awning' ), fxSaid( fx, 'colours', 'awning' ), fxSaid( fx, 'motion', 'awning' ) ] ).join('|')
		=== [ fxRegular.size.label+ ' +1', fxRegular.line.label+ ' '+ fxRegular.line.options.more,
			[ fxRegular.submit.label+ ' '+ TEXT['/_admin/design/ground/'+ fxOtherGround], fxRegular.label.label+ ' '+ TEXT['/_admin/design/ink/'+ fxOtherInk], fxLoud.flip.label+ ' #112233 / #aabbcc' ].join(' · '),
			fxLoud.turn.label+ ' +2' ].join('|') );

// Another variant: the knobs it does not declare go, as the position of a knob a template does not answer to goes back to the middle
fx.show( 'templates' );
choose( fxSelect( fx, 'awning' ), 'regular' );

check( 'another variant is the knobs it declares: those the last one declared and this one does not are not this one\'s - they go from the entry - and the rows of them are greyed out',
	fxFeatures( fx ) === JSON.stringify( { awning : { set : 'regular', knobs : { size : 1, line : 1, submit : fxOtherGround, label : fxOtherInk }, styles : {} } } )
	&& byTag( fxFlipRow(), 'input' ).every( function( input ) { return input.disabled === true } ) && hasClass( fxControl( fx, 'awning', 'turn' ), 'design-knob-row--off' ) === true );

choose( fxSelect( fx, 'awning' ), 'loud' );

check( '...and the variant chosen again brings them back at the position their head says: what was taken from the entry is not kept to give back',
	fx.design._edit.features.awning.knobs.flip === undefined && fx.design._edit.features.awning.knobs.turn === undefined
	&& ( fxFlipRow(), fxHalf('light').value === fxLoud.flip['default'][0] && fxHalf('dark').value === fxLoud.flip['default'][1] )
	&& ( fxControl( fx, 'awning', 'turn' ), pressedKey( fx, 'features/awning/knobs/turn' ) === 'false,false,true,false,false' ) );

// The way back takes the choices back with the rest of the selection
fx.byId('design-reset').fire('click');

check( 'the way back puts the choices for the features back to the stored ones, with the rest of the selection: none here - the entry, the select and the strips are what they were',
	fxFeatures( fx ) === '{}' && fx.design._isDirty() === false && shown( fxSelect( fx, 'awning' ) ) === fxZinc['default']
	&& ( fxControl( fx, 'awning', 'size' ), pressedKey( fx, fxSizeKey ) === 'false,false,true,false,false' ) );

// What the colours of a feature measure on the page: the numbers are the server's - design/list and every preview carry them -, the words the panel's
const fxMeasure = function( light, dark ) { return { awning : { flip : { light : { ratio : light, target : 3, safe : light >= 3 }, dark : { ratio : dark, target : 3, safe : dark >= 3 } } } } };
const fxWeak = panel( featured( { awning : { set : 'loud', knobs : { flip : [ '#eeeeee', '#222222' ] } } }, { contrasts : fxMeasure( 1.2, 1.3 ) } ) );
const fxWeakLine = function() { fxControl( fxWeak, 'awning', 'flip' ); return fxWeak.byId('design-feature-awning_flip_contrast') };

check( 'a colour of a feature that does not reach the ratio a mark on a page needs is said under the pair, in the panel\'s words with the numbers of the server: the two ratios and the one the mark needs - the colours stay as they are, nothing refuses them',
	fxWeakLine().hidden === false && fxWeakLine().textContent === say( '/_admin/design/label/contrast', 1.2, 1.3, 3 ) && hasClass( fxWeakLine(), 'nino-admin-hint' ) && hasClass( fxWeakLine(), 'design-colour-warning' )
	&& fxWeak.byId('design-feature-awning_flip_light').value === '#eeeeee' && fxWeak.byId('design-feature-awning_flip_dark').value === '#222222' );

fxWeak.design._previewNow();
fxWeak.answer( 200, Object.assign( previewed( ':root{}', '<!doctype html>' ), { contrasts : fxMeasure( 4.5, 1.3 ) } ) );

check( '...every preview brings the measure again and the line is written in place, without the column being drawn again: one half below the ratio is enough to say it, both above it say nothing',
	fxWeakLine().hidden === false && fxWeakLine().textContent === say( '/_admin/design/label/contrast', 4.5, 1.3, 3 ) && fxWeak.byId('design-feature-awning_flip_contrast') === fxWeakLine()
	&& ( fxWeak.design._previewNow(), fxWeak.answer( 200, Object.assign( previewed( ':root{}', '<!doctype html>' ), { contrasts : fxMeasure( 4.5, 5 ) } ) ), fxWeakLine().hidden === true ) );

// A choice kept for a feature that is not active has no block and no row, and rides along
const fxGhost = panel( featured( { awning : { set : 'loud', knobs : { size : 1 } }, ghost : { set : 'x', knobs : { a : 1 } } } ) );

check( 'a choice kept for a feature that is not active - the answer lists it, flagged - has no object in the table, no block on any tab and no row: nothing of it is drawn',
	fxGhost.design._data.features.ghost.active === false && fxGhost.design._data.layout.objects.indexOf('ghost') === -1 && Object.keys( fxGhost.design._data.layout.controls ).every( function( key ) { return key.indexOf('features/ghost/') === -1 } )
	&& [ 'templates', 'type', 'surfaces', 'colours', 'motion' ].every( function( tab ) { fxGhost.show( tab ); return fxObjects( fxGhost ).indexOf('ghost') === -1 } ) );
check( '...and it is still the screen\'s choice: it is posted with the rest, so that a save keeps it, and a screen that did not touch it is the stored one',
	JSON.stringify( fxGhost.design._edit.features.ghost ) === JSON.stringify( { set : 'x', knobs : { a : 1 }, styles : {} } ) && JSON.stringify( fxLastPayload( fxGhost ).features.ghost ) === JSON.stringify( { set : 'x', knobs : { a : 1 }, styles : {} } )
	&& fxGhost.design._isDirty() === false && fxFeatures( fxGhost ).indexOf('"awning":{"set":"loud","knobs":{"size":1},"styles":{}}') !== -1 );

// What the server dropped or replaced on the way is told in the panel's words, the feature by the name its manifest gives it
const fxNoted = panel( featured( {}, { notes : [
	{ error : 'a sentence in English', code : 'design_set_missing', params : [ 'awning', 'gone', 'regular' ] },
	{ error : 'a sentence in English', code : 'design_feature_inactive', params : [ 'ghost' ] },
] } ) );

check( 'a note about a feature is said in the words of the panel as a note about a part is: a variant that is not there is told by the name of the feature and of the variant that stands in its place, a choice kept for a feature that is not active by its key',
	JSON.stringify( noteLines( fxNoted ) ) === JSON.stringify( [
		say( '/_admin/error/design_set_missing', fxZinc.name, 'gone', fxZinc.catalogue.regular.name ),
		say( '/_admin/error/design_feature_inactive', 'ghost' ),
	] ) && noteLines( fxNoted ).every( function( line ) { return line.indexOf('%s') === -1 && line.indexOf('design_') === -1 } ) );

// Every word of the rows of the features is one of the panel's or the feature's own: no key stands on the screen for a text nobody wrote
const fxUnsaid = [];

[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {
	fx.show( tab );
	eachBlock( fx.form, function() {
		descendants( fx.pane ).forEach( function( el ) {
			[ el.textContent, el.getAttribute('title'), el.getAttribute('aria-label') ].forEach( function( text ) { if( typeof text === 'string' && text.indexOf('/_admin/') === 0 ) fxUnsaid.push( text ) } );
		} );
	} );
} );

check( '...and every word the blocks of the features show is one the texts carry'+ ( fxUnsaid.length === 0 ? '' : ' - missing: '+ fxUnsaid.slice( 0, 4 ).join() ), fxUnsaid.length === 0 );

console.log('');


// --- The headings over the blocks of rows -------------------------------------------------------
//
// A block of rows has its title and nothing under it: the line that said what the rows are
// is not drawn on any tab, and the words that only it read are gone from the panel's words

console.log( 'The headings' );

const heads = panel();
const headings = {};
const headingsOf = function( where ) {
	return byClass( where, 'design-section-label' ).map( function( label ) {
		return byTag( label, 'strong' )[0].textContent+ ':'+ label.children.length+ ':'+ byTag( label, 'small' ).length;
	} ).join();
};

heads.show( 'theme' );
headings.theme = headingsOf( heads.form );

TABS.forEach( function( name ) {
	heads.show( name );
	headings[name] = headingsOf( heads.form );
} );

heads.show( 'typefaces' );
headings.typefaces = headingsOf( heads.faceBox() );

const headOf = function( title ) { return title+ ':1:0' };
check( 'a block of rows has its title and nothing under it, on every screen of every tab: the card of the list of the themes, the field of the css has none, the head of its block names it, and the two cards of the loader, the heading of the roles table in the open block of the page on Type - the word of an object is the head of its block and no heading, and the open block of the Templates, of the Surfaces and of the Motion has none at all - the tuning and the mode of the palette carry a heading of one title each - no small under it',
	JSON.stringify( headings ) === JSON.stringify( {
		theme : TEXT['/_admin/design/theme/list/title']+ ':1:0',
		templates : '',
		type : headOf( TEXT['/_admin/design/label/fonts'] ),
		surfaces : '',
		colours : headOf( TEXT['/_admin/design/label/tuning'] )+ ','+ headOf( TEXT['/_admin/design/colour/mode/label'] ),
		motion : '',
		css : '',
		typefaces : TEXT['/_admin/design/typefaces/installed/title']+ ':1:0,'+ TEXT['/_admin/design/typefaces/available/title']+ ':1:0' } ) );

const titled = heads.design._sectionLabel( 'A title' );
const iconed = heads.design._sectionLabel( 'Another', heads.design._icon('colours/mode') );

check( '...the heading is drawn from its title, and from an icon where the block has one - which comes first, in the title - and there is no note to give it',
	heads.design._sectionLabel.length === 2 && titled.children.length === 1 && byTag( titled, 'strong' )[0].textContent === 'A title' && byTag( titled, 'small' ).length === 0
	&& byTag( iconed, 'strong' )[0].children[0].tagName === 'SVG' && byTag( iconed, 'strong' )[0].textContent === 'Another' && byTag( iconed, 'small' ).length === 0 );
check( '...and the sentences that stood under them - the fonts, the animation, the tuning and the mode - are gone from the panel\'s words, with the hint of the two buttons and the words of the size only the summary used',
	[ 'hint/fonts', 'hint/motion', 'hint/tuning', 'colour/mode/note', 'hint/actions', 'fontsize/default', 'fontsize/decimal' ].every( function( key ) { return TEXT['/_admin/design/'+ key] === undefined } )
	&& TEXT['/_admin/design/fontsize/unit'] !== undefined && TEXT['/_admin/design/hint/size'] !== undefined && TEXT['/_admin/design/hint/pagewidth'] !== undefined );

console.log('');


// --- The icons ----------------------------------------------------------------------------------
//
// A row of the Type and Surfaces views, and every row of the Colours view, open with an icon, so that a
// person feels what a knob sets before reading it - and the names of the Colours view start at
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
	'list' : '<path d="M3 12h.01"/><path d="M3 18h.01"/><path d="M3 6h.01"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M8 6h13"/>',
	'palette' : '<path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>',
	'thermometer' : '<path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>',
	'droplet' : '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
	'contrast' : '<circle cx="12" cy="12" r="10"/><path d="M12 18a6 6 0 0 0 0-12v12z"/>',
	'sun-moon' : '<path d="M12 2v2"/><path d="M14.837 16.385a6 6 0 1 1-7.223-7.222c.624-.147.97.66.715 1.248a4 4 0 0 0 5.26 5.259c.589-.255 1.396.09 1.248.715"/><path d="M16 12a4 4 0 0 0-4-4"/><path d="m19 5-1.256 1.256"/><path d="M20 12h2"/>',
	// The spread of the buttons, and the width of the page
	'chevrons-left-right' : '<path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/>',
	'tracking' : '<path d="M3 4v16"/><path d="M21 4v16"/><path d="M7 12h10"/><path d="m10 9-3 3 3 3"/><path d="m14 9 3 3-3 3"/>',
	'align-center' : '<path d="M17 12H7"/><path d="M19 18H5"/><path d="M21 6H3"/>',
	'unfold-horizontal' : '<path d="M16 12h6"/><path d="M8 12H2"/><path d="M12 2v2"/><path d="M12 8v2"/><path d="M12 14v2"/><path d="M12 20v2"/><path d="m19 15 3-3-3-3"/><path d="m5 9-3 3 3 3"/>',
	'move-horizontal' : '<path d="m18 8 4 4-4 4"/><path d="M2 12h20"/><path d="m6 8-4 4 4 4"/>',
	'arrow-down-to-line' : '<path d="M12 17V3"/><path d="m6 11 6 6 6-6"/><path d="M19 21H5"/>',
	'move-vertical' : '<path d="M12 2v20"/><path d="m8 18 4 4 4-4"/><path d="m8 6 4-4 4 4"/>',
	// The rule under the title of a section
	'minus' : '<path d="M5 12h14"/>',
	// The three devices of the viewport switch, the shapes the Builder's panel carries as its symbols
	'smartphone' : '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
	'tablet' : '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/>',
	'monitor' : '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
	// The reload of the preview, beside the three devices
	'refresh-cw' : '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
	// The chevron of the head of a block, turned over while the block is open
	'chevron-down' : '<path d="m6 9 6 6 6-6"/>',
	'pencil' : '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
};

/** Which icon each button of the viewport switch takes */
const VIEWPORT_ICON = { phone : 'smartphone', tablet : 'tablet', desktop : 'monitor' };

/** Which icon each row takes, by the key the script looks it up under: the strips of the page, the rows of the loader, the knobs and the rows of the palette */
const ICON_OF = {
	'page/size' : 'a-large-small', 'page/width' : 'move-horizontal', 'page/whitespace' : 'move-vertical',
	'page/radius' : 'square-round-corner', 'page/radius-alt' : 'square-round-corner',
	'page/radii-section' : 'square-round-corner', 'page/radii-tile' : 'square-round-corner', 'page/radii-card' : 'square-round-corner', 'page/radii-image' : 'square-round-corner',
	'typefaces/family' : 'a-large-small',
	'header/nav' : 'a-large-small', 'header/menu-size' : 'a-large-small', 'header/menu-weight' : 'minus', 'header/menu-tracking' : 'tracking', 'header/height' : 'move-vertical', 'header/space' : 'align-vertical-space-around', 'header/logo' : 'scaling', 'header/ground' : 'contrast', 'header/border-size' : 'minus', 'header/border-color' : 'palette', 'header/shadow' : 'square-stack', 'header/position' : 'move-horizontal', 'header/cta' : 'square-round-corner', 'header/behaviour' : 'arrow-down-to-line',
	'footer/scale' : 'a-large-small', 'footer/height' : 'move-vertical', 'footer/space' : 'align-vertical-space-around', 'footer/logo' : 'scaling', 'footer/ground' : 'contrast', 'footer/border-size' : 'minus', 'footer/border-color' : 'palette', 'footer/divider' : 'minus', 'footer/position' : 'move-horizontal',
	'section/quiet' : 'a-large-small', 'section/loud' : 'a-large-small',
	'section/title' : 'a-large-small', 'section/subtitle' : 'a-large-small', 'section/title-tracking' : 'chevrons-left-right', 'section/space' : 'scaling', 'section/rule' : 'minus', 'section/rule-length' : 'move-horizontal', 'section/title-shadow' : 'square-stack', 'section/dim' : 'contrast',
	'section/title-ground' : 'square', 'section/rule-color' : 'palette', 'section/title-ink' : 'palette', 'section/subtitle-ink' : 'palette', 'section/align' : 'align-center', 'section/measure' : 'unfold-horizontal',
	'tiles/title' : 'a-large-small', 'tiles/subtitle' : 'a-large-small', 'tiles/text' : 'a-large-small', 'tiles/title-tracking' : 'chevrons-left-right', 'tiles/space' : 'align-vertical-space-around', 'tiles/gap' : 'unfold-horizontal',
	'tiles/place' : 'align-center', 'tiles/dim' : 'contrast', 'tiles/hover' : 'move-vertical', 'tiles/title-ink' : 'palette', 'tiles/subtitle-ink' : 'palette',
	'card/title' : 'a-large-small', 'card/subtitle' : 'a-large-small', 'card/title-tracking' : 'chevrons-left-right', 'card/space' : 'align-vertical-space-around',
	'card/border' : 'square', 'card/shadow' : 'square-stack', 'card/tone' : 'palette', 'card/title-ground' : 'square', 'card/title-ink' : 'palette', 'card/subtitle-ink' : 'palette', 'card/hover' : 'move-vertical', 'card/align' : 'align-center',
	'buttons/size' : 'a-large-small', 'buttons/spread' : 'chevrons-left-right', 'buttons/tracking' : 'tracking', 'buttons/space' : 'align-vertical-space-around', 'buttons/corners' : 'square-round-corner', 'buttons/outline' : 'minus',
	'buttons/hover' : 'move-vertical',
	'lists/scale' : 'a-large-small', 'lists/space' : 'align-vertical-space-around', 'lists/marker' : 'list',
	'tables/scale' : 'a-large-small', 'tables/space' : 'align-vertical-space-around', 'tables/border' : 'square', 'tables/zebra' : 'rows-3', 'tables/stripes' : 'contrast',
	'forms/scale' : 'a-large-small', 'forms/space' : 'align-vertical-space-around', 'forms/corners' : 'square-round-corner', 'forms/line' : 'minus', 'forms/labels' : 'move-horizontal', 'forms/edge' : 'palette', 'forms/label' : 'palette',
	'tabs/scale' : 'a-large-small', 'tabs/space' : 'align-vertical-space-around', 'tabs/line' : 'minus', 'tabs/active' : 'palette',
	'accordion/scale' : 'a-large-small', 'accordion/space' : 'align-vertical-space-around', 'accordion/line' : 'minus', 'accordion/marker' : 'list', 'accordion/marker-ink' : 'palette',
	'colours/primary' : 'palette', 'colours/harmony' : 'palette', 'colours/temperature' : 'thermometer', 'colours/saturation' : 'droplet',
	'colours/contrast' : 'contrast', 'colours/depth' : 'square-stack', 'colours/mode' : 'sun-moon',
	'tier/size' : 'a-large-small', 'tier/title' : 'a-large-small', 'tier/subtitle' : 'a-large-small', 'tier/areas' : 'move-vertical', 'tier/margin' : 'move-horizontal',
	'tier/inset' : 'align-vertical-space-around', 'tier/gap' : 'unfold-horizontal', 'tier/header' : 'scaling', 'tier/button' : 'a-large-small',
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
let switchRows = 0;

/** The tab a control stands on, by its key in the table */
const iconTab = function( key ) { return LAYOUT[key][0] };

[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {

	icons.show( tab );

	knobRows( icons ).forEach( function( row ) {

		const key = row.dataset.control.split('/').slice( 1 ).join('/');

		// The row of a toggle is the workbench's switch and draws no icon: the track comes first in it, and the name after the track
		if( isSwitch( row ) === true ) {
			switchRows++;
			if( byTag( row, 'svg' ).length !== 0 || ICON_OF[key] !== undefined )
				iconProblems.push( key );
			return;
		}

		const label = byClass( row, 'design-knob-label' )[0];
		const svg = byTag( label, 'svg' )[0];
		const box = byClass( label, 'design-knob-text' )[0];

		iconRows++;

		if( svg === undefined || box === undefined || label.children[0] !== svg || label.children[1] !== box || svg.dataset.icon !== ICON_OF[key]
			|| byTag( label, 'svg' ).length !== 1 || hasClass( svg, 'design-icon' ) === false || markupOf( svg ) !== LUCIDE[ICON_OF[key]] )
			iconProblems.push( key );
	} );
} );

check( 'every knob row of Templates, Type, Surfaces, Colours and Motion - one for each knob the table has - carries an svg.design-icon before its name, with the data-icon of the table, and the shape of Lucide\'s that name has, verbatim - but the nine switches, which are the workbench\'s switch and have no icon, and the table has none for them'
	+ ( iconProblems.length === 0 ? '' : ' - not so for '+ iconProblems.join() ), iconRows + switchRows === knobKeys.length && switchRows === kindCount('toggle') && iconRows > 0 && iconProblems.length === 0 );

icons.show( 'type' );
const iconRow = controlOf( icons, 'knobs/section/title' );
const iconLabel = byClass( iconRow, 'design-knob-label' )[0];
const iconSvg = byTag( iconLabel, 'svg' )[0];

check( '...the icon is decoration, drawn on a 24 by 24 grid in the svg namespace: hidden from assistive technology, out of the tab order, and the class that carries the stroke and the size',
	iconSvg.namespaceURI === 'http://www.w3.org/2000/svg' && iconSvg.getAttribute('viewBox') === '0 0 24 24' && iconSvg.getAttribute('aria-hidden') === 'true'
	&& iconSvg.getAttribute('focusable') === 'false' && hasClass( iconSvg, 'design-icon' ) && iconSvg.children.every( function( part ) { return part.namespaceURI === 'http://www.w3.org/2000/svg' } )
	&& iconSvg.children.length === 4 && iconSvg.children.every( function( part ) { return part.tagName === 'PATH' } ) );
check( '...and the label is the row of the two: the icon, then a box that holds the name with its note under it - the name is the box\'s own text, the note a small in it',
	iconLabel.children.length === 2 && iconLabel.children[1].tagName === 'SPAN' && hasClass( iconLabel.children[1], 'design-knob-text' )
	&& iconLabel.children[1].textContent === knobWord( 'section', 'title' ) && byTag( iconLabel.children[1], 'small' ).length === 1
	&& byTag( iconLabel.children[1], 'small' )[0].textContent === TEXT['/_admin/design/knob/section/title/note'] && byTag( iconLabel, 'button' ).length === 0 );

// A row the template does not answer to is grey, and keeps its icon: what it sets is not a thing that depends on the template
icons.tab( tabWord('templates') );
choose( setSelect( icons, 'card' ), 'third' );
icons.tab( tabWord('surfaces') );

check( '...a row that is greyed out keeps its icon, with the line that says whose decision that is in the box beside it',
	hasClass( controlOf( icons, 'knobs/card/shadow' ), 'design-knob-row--off' ) && byTag( controlOf( icons, 'knobs/card/shadow' ), 'svg' )[0].dataset.icon === 'square-stack'
	&& byTag( controlOf( icons, 'knobs/card/shadow' ), 'small' )[0].textContent === say( '/_admin/design/hint/unanswered', knobWord( 'card', 'shadow' ) ) );

// The strips of the page stand on Type (the root size) and on Surfaces (the rest): the kinds of surface are the choices of two words
check( 'the strips of the page take theirs by the same mechanism, on the tab they stand on: the root size the letters of two sizes, the width the two arrows that point apart, the whitespace the two that point up and down, and the two radii and the corners of each choice of a kind of surface the square with one round corner - Lucide\'s a-large-small, move-horizontal, move-vertical and square-round-corner, each with the shape of its name, verbatim, in front of the box that holds the name',
	[ [ 'size', 'a-large-small' ], [ 'width', 'move-horizontal' ], [ 'whitespace', 'move-vertical' ], [ 'radius', 'square-round-corner' ], [ 'radius-alt', 'square-round-corner' ],
		[ 'radii/cover', 'square-round-corner' ], [ 'radii/card', 'square-round-corner' ], [ 'radii/image', 'square-round-corner' ] ].every( function( entry ) {
		icons.show( iconTab( entry[0] ) );
		const label = byClass( controlOf( icons, entry[0] ), 'design-knob-label' )[0];
		const svg = byTag( label, 'svg' );
		return svg.length === 1 && label.children.length === 2 && label.children[0] === svg[0] && hasClass( label.children[1], 'design-knob-text' )
			&& svg[0].dataset.icon === entry[1] && markupOf( svg[0] ) === LUCIDE[entry[1]] && hasClass( svg[0], 'design-icon' );
	} ) );

icons.tab( tabWord('colours') );

const colourIcon = function( row ) { return ( byTag( row, 'svg' )[0] || { dataset : {} } ).dataset.icon };
const brandLabel = byClass( icons.form, 'design-colour-label' )[0];

check( 'the rows of the Colours view that are a colour get theirs by the same mechanism: the brand colour and the second colour - the row of Harmony - the palette',
	hasClass( brandLabel, 'design-knob-label' ) && brandLabel.getAttribute('for') === 'design-colour-primary' && byTag( brandLabel, 'svg' )[0].dataset.icon === 'palette'
	&& brandLabel.children[1].textContent === TEXT['/_admin/design/label/primary'] && markupOf( byTag( brandLabel, 'svg' )[0] ) === LUCIDE['palette']
	&& colourIcon( icons.knobRow( colourWord('harmony') ) ) === 'palette' && markupOf( byTag( icons.knobRow( colourWord('harmony') ), 'svg' )[0] ) === LUCIDE['palette'] );
check( '...temperature takes the thermometer, saturation the drop, contrast the circle with one half filled and depth the stack, each with the shape of Lucide\'s that name has, verbatim',
	[ [ colourWord('temperature'), 'thermometer' ], [ colourWord('saturation'), 'droplet' ], [ colourWord('contrast'), 'contrast' ], [ colourWord('depth'), 'square-stack' ] ].every( function( entry ) {
		const svg = byTag( icons.knobRow( entry[0] ), 'svg' );
		return svg.length === 1 && svg[0].dataset.icon === entry[1] && markupOf( svg[0] ) === LUCIDE[entry[1]];
	} ) );
check( '...and the mode the sun and the moon - before the title of its block, which has no label of its own to put it in',
	byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' ).length === 1 && byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0].dataset.icon === 'sun-moon'
	&& markupOf( byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0] ) === LUCIDE['sun-moon'] && byTag( byClass( icons.form, 'design-colour-mode' )[0], 'strong' )[0].children[0] === byTag( byClass( icons.form, 'design-colour-mode' )[0], 'svg' )[0] );

// Every label of the tab - the brand colour and the five knobs - opens with an icon and has the name right after it, so that the names stand at one left edge
const colourLabels = byClass( icons.form, 'design-knob-label' );

check( '...so that every row of the Colours view starts with its icon, the name right after it, and none is left to start at the edge: the brand colour, Harmony, temperature, saturation, contrast and depth',
	colourLabels.length === 6 && colourLabels.every( function( label ) {
		return label.children.length === 2 && label.children[0].tagName === 'SVG' && hasClass( label.children[0], 'design-icon' ) && hasClass( label.children[1], 'design-knob-text' );
	} ) && colourLabels.map( function( label ) { return label.children[0].dataset.icon } ).join() === 'palette,palette,thermometer,droplet,contrast,square-stack' );

const mapped = icons.design.KNOB_ICONS;

// A row whose entry is taken away is drawn without its icon, and nothing takes the place: the label is the box of the name alone
const thermometer = mapped['colours/temperature'];
delete mapped['colours/temperature'];
icons.setup._redrawColours();

check( 'a knob with no entry in the map has no icon, and nothing stands in for it: not a row, not a fallback shape',
	icons.design._icon('section/nothing') === null && icons.design._icon('') === null && icons.design._icon('constructor') === null
	&& byTag( icons.knobRow( colourWord('temperature') ), 'svg' ).length === 0 && byClass( icons.knobRow( colourWord('temperature') ), 'design-knob-label' )[0].children.length === 1
	&& hasClass( byClass( icons.knobRow( colourWord('temperature') ), 'design-knob-label' )[0].children[0], 'design-knob-text' )
	&& colourIcon( icons.knobRow( colourWord('saturation') ) ) === 'droplet' );

mapped['colours/temperature'] = thermometer;
icons.setup._redrawColours();

check( '...and the row has it again when the entry is back', colourIcon( icons.knobRow( colourWord('temperature') ) ) === 'thermometer' );

mapped['section/nowhere'] = 'no-such-icon';

check( '...nor has a knob whose icon the script has no shape for',
	icons.design._icon('section/nowhere') === null );

delete mapped['section/nowhere'];

check( 'the script\'s two maps are the tables above: the same knobs with the same icons, and a shape for each icon that is used - no more, no fewer, verbatim',
	JSON.stringify( Object.keys( mapped ).sort() ) === JSON.stringify( Object.keys( ICON_OF ).sort() ) && Object.keys( ICON_OF ).every( function( key ) { return mapped[key] === ICON_OF[key] } )
	&& JSON.stringify( Object.keys( icons.design.ICONS ).sort() ) === JSON.stringify( Object.keys( LUCIDE ).sort() )
	&& Object.keys( ICON_OF ).every( function( key ) { return markupOf( icons.design._icon( key ) ) === LUCIDE[ICON_OF[key]] } ) );
check( '...and the shapes in use are the rows\', the three devices of the viewport switch, the reload, the chevron of a head and the pencil of a row of the list: each shape of the table is drawn by a row, by a width, by the reload, by a head or by a theme\'s row, and each width has the device the table says',
	JSON.stringify( Object.keys( LUCIDE ).sort() ) === JSON.stringify( Object.keys( ICON_OF ).map( function( key ) { return ICON_OF[key] } ).concat( Object.keys( VIEWPORT_ICON ).map( function( key ) { return VIEWPORT_ICON[key] } ), [ 'refresh-cw', 'chevron-down', 'pencil' ] )
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

icons.show( 'type' );

check( 'the roles table is a table with row headers and no knob rows: it has no icon - the knob rows of Type stand outside it, in the cards of their objects',
	byTag( icons.form, 'table' ).length === 1 && byTag( byTag( icons.form, 'table' )[0], 'svg' ).length === 0 && byClass( byTag( icons.form, 'table' )[0], 'design-knob-row' ).length === 0
	&& knobRows( icons ).length > 0 && knobRows( icons ).every( function( row ) { return descendants( byTag( icons.form, 'table' )[0] ).indexOf( row ) === -1 } ) );

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
	byClass( pvHead.children[0], 'design-eyebrow' ).length === 1 && byTag( pvHead.children[0], 'strong' )[0].textContent === TEXT['/_admin/design/label/preview']
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
	&& pv.byId('design-reload').title === TEXT['/_admin/design/label/reload'] && pv.byId('design-reload').getAttribute('aria-label') === TEXT['/_admin/design/label/reload'] && pv.byId('design-reload').textContent === '' );

// The width of the preview is the Builder's switch: three icon buttons in a group
const pvGroup = pvHead.children[1].children[0];
const pvButtons = byTag( pvGroup, 'button' );
const pvWidths = pv.design.WIDTHS;
const pvWords = pvWidths.map( function( width ) { return TEXT['/_admin/design/width/'+ width.key] } ).join();

check( 'the select of the width and its label are gone: no design-width, no select in the head, no word "Width" over anything - the text of that label is gone from the panel\'s words too',
	pv.byId('design-width') === null && byTag( pvHead, 'select' ).length === 0 && byTag( pvHead, 'label' ).every( function( label ) { return label.textContent !== 'Width' } )
	&& TEXT['/_admin/design/label/width'] === undefined );
check( 'the width is three icon buttons in a group the way the Builder has them: the group is named by what it is, there is a button each for the phone, the tablet and the desktop - and none for all of them',
	pvGroup.getAttribute('role') === 'group' && pvGroup.getAttribute('aria-label') === TEXT['/_admin/design/label/viewports']
	&& pvButtons.length === 3 && pvButtons.map( function( button ) { return button.id } ).join() === 'design-viewport-phone,design-viewport-tablet,design-viewport-desktop'
	&& pvWidths.map( function( width ) { return width.key+ ':'+ width.px } ).join() === 'phone:390,tablet:820,desktop:1280'
	&& pvButtons.every( function( button ) { return button.tagName === 'BUTTON' && button.type === 'button' && hasClass( button, 'design-viewport' ) } ) );
check( '...each with the name of its width as its title and as its name for a screen reader, since an icon has none: the three words of the text file',
	pvButtons.map( function( button ) { return button.title } ).join() === pvWords && pvButtons.map( function( button ) { return button.getAttribute('aria-label') } ).join() === pvWords
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
const rmFirst = place( 1180, 187, 732, 799, 0, false );
const rmTabFits = rm.fitted.length;

put( 1180, 187, 520, 587, 0, false );
rm.go( 'card' );

check( 'in one column a tab that is opened fits the stage to where it stands under the controls of that tab, with no preview asked for or answered on the way: a shorter column, the stage 212px higher, is 212px taller',
	rm.setup._current() === 'card' && rm.requests.length === 0 && rm.fitted.length === rmTabFits + 1 && rm.fitted[rm.fitted.length - 1].port === rmStage
	&& near( parseFloat( rmStage.style.height ), 1180 - 587 - 68 - 11.2 ) && near( parseFloat( rmStage.style.height ), rmFirst + 212 ) );

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

check( '...and in that frame the page is scrolled, once, to the anchor of the object - the cards of the card, 900px below the top of the window with the page scrolled 100px, so 1000 - and the listener is taken away',
	rm.stage.scrolls.length === 1 && JSON.stringify( rm.stage.scrolls[0] ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 );

elapse( 300 );

check( '...and the timer behind it, when it elapses, adds none', rm.stage.scrolls.length === 1 && rm.waiting( 300 ).length === 0 );

put( 1180, 187, 1032, 1099, 0, false );
rm.go( 'lists' );

check( '...a taller one, with less than 16 rem left under the stage, gives it the height of the window it fills with the top of its pane at the top of it, as on a phone: 1180px less the 67px the stage stands in its pane, the bar and the gap',
	rm.setup._current() === 'lists' && near( parseFloat( rmStage.style.height ), 1180 - 67 - 68 - 11.2 ) && rm.fitted.length === rmTabFits + 2 );

// The frame may not be made another size after all, and then no resize comes: the timer takes the jump there
const rmWaited = rm.stage.scrolls.length;

elapse( 300 );

check( 'where no resize comes the timer takes the jump there, 300ms after the tab was opened: once, to the anchor of the object - the lists, 1400px below the top of the window with the page scrolled 100px, so 1500 - and the listener is taken away',
	rmWaited === 1 && rm.stage.scrolls.length === 2 && JSON.stringify( rm.stage.scrolls[1] ) === JSON.stringify( { top : 1500, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 );

put( 1180, 187, 732, 799, 0, false );
rm.go( 'page' );

check( '...and the way back gives the stage the height it began with: it does not depend on which tabs were opened before',
	rm.setup._current() === 'page' && near( parseFloat( rmStage.style.height ), rmFirst ) && near( rmFirst, 1180 - 799 - 68 - 11.2 ) && rm.requests.length === 0 && rm.fitted.length === rmTabFits + 3 );

// Whichever comes first jumps and the other finds it done: here the resize is heard, then the timer elapses, and the frame it asked for comes last
const rmHeld = rm.stage.scrolls.length;

rm.stage.resize();
elapse( 300 );
rm.stage.animate();

check( '...and the jump is made once, whichever comes first: the resize heard and the timer before the animation frame it asked for - one scroll, to the top of the page, which the page is',
	rmHeld === 2 && rm.stage.scrolls.length === 3 && JSON.stringify( rm.stage.scrolls[2] ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && rm.stage.listeners.length === 0 && rm.stage.frames.length === 0 );

// Beside the controls the pane sticks and the column does not move the stage: it has the same height under every tab, from a height that was not the right one
rmStage.style.height = '123px';
put( 800, 71, 71, 138, 0, true );
rm.go( 'buttons' );

check( '...and in two columns, where the pane sticks, a tab fits it as well: 800px high, the stage at 138px, whatever height it had before',
	near( parseFloat( rmStage.style.height ), 800 - 138 - 68 - 11.2 ) && rm.fitted.length === rmTabFits + 4 );

// What that tab waited for comes. Then a tab that leaves the stage as tall as it was has nothing to wait for
rm.stage.resize();
rm.stage.animate();
elapse( 300 );

const rmStill = rm.stage.scrolls.length;

put( 800, 71, 71, 138, 0, true );
rm.go( 'card' );

check( 'where the fit leaves the stage as tall as it was - in two columns every tab has the one height - the jump is made at once: one scroll, to the anchor of the tab, with no resize listened for and no timer set',
	rm.setup._current() === 'card' && rm.stage.scrolls.length === rmStill + 1 && JSON.stringify( rm.stage.scrolls[rmStill] ) === JSON.stringify( { top : 1000, behavior : 'smooth' } )
	&& rm.stage.listeners.length === 0 && rm.waiting( 300 ).length === 0 );

// A frame whose page cannot be reached has nothing to wait for, and a window that cannot be listened to is not waited on
const rmWindow = rm.stage.document.defaultView;
const rmListen = rmWindow.addEventListener;

rm.stage.reachable = false;
put( 1180, 187, 520, 587, 0, false );

check( 'a frame the panel cannot reach is not waited for: the tab is opened, nothing throws, nothing is listened to and no timer is set',
	threw( function() { rm.go( 'footer' ) } ) === false && rm.setup._current() === 'footer' && rm.stage.listeners.length === 0 && rm.waiting( 300 ).length === 0 );

rm.stage.reachable = true;
rmWindow.addEventListener = undefined;
put( 1180, 187, 1032, 1099, 0, false );

const rmGone = rm.stage.scrolls.length;

rm.go( 'card' );

check( '...nor is a window that cannot be listened to: the jump is made at once, to the anchor of the tab, and no timer is set',
	rm.stage.scrolls.length === rmGone + 1 && JSON.stringify( rm.stage.scrolls[rmGone] ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) && rm.waiting( 300 ).length === 0 );

rmWindow.addEventListener = rmListen;

// The colours column is the one the checks that follow read the brand warning off
rm.tab( tabWord('colours') );

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

check( '...then the page is scrolled, once, to the anchor of the open object - the page\'s, the top of the page - the listener is taken away, and the timer behind it adds no second scroll',
	rm.stage.scrolls.length === rmAnswered + 1 && JSON.stringify( rm.stage.scrolls[rmAnswered] ) === JSON.stringify( { top : 0, behavior : 'smooth' } )
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


// --- The room of the list of typefaces -----------------------------------------------------
//
// The box the rows of a page of the loader stand in is fitted to the window as the stage is: as tall as the
// window has left under it - its height less the top of the box with the page scrolled to the top, less the pager
// that stands after the box, less the foot of the card - 1.5 rem - and less the room the pane keeps under its
// content for a bar, which the tab has none of but whose padding it has all the same. Five rows at the least;
// where the room is less than that the box is made for the window it fills when the page is scrolled to its
// card, the top of the card at the top of the window. A box that holds the sentence of no match is not fitted. The
// numbers are the window's and the places of the card, the box, its first row and the pager, which a test says: the
// stand-in has no layout of its own

console.log( 'The room of the list of typefaces' );

const lf = panel();
lf.show( 'typefaces' );
lf.answer( 200, shelfOf( { families : thirty, fetched : LOADED_AT } ) );

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

	lf.faceBox().scrollTop = scrolled;
}

// A row and a pager are told where they stand as they are made, and a column drawn again is laid out the same way
const lfRowOf = lf.faces._availableRow;
const lfPagerOf = lf.faces._pager;
const lfRender = lf.faces._renderTypefaces;

lf.faces._availableRow = function( entry ) {
	const row = lfRowOf.call( lf.faces, entry );
	row.rect = { top : 350, left : 0, width : 330, height : lfRow };
	return row;
};
lf.faces._pager = function( pages ) {
	const foot = lfPagerOf.call( lf.faces, pages );
	foot.rect = { top : 850, left : 0, width : 330, height : 40 };
	return foot;
};
lf.faces._renderTypefaces = function( body ) {
	lfRender.call( lf.faces, body );
	lay( body, 0 );
};

/** Lay the list out for a window of that height, with the page scrolled by so much, fit the screen, and say what height the box has then */
function fitList( height, scrolled ) {
	lf.view.innerHeight = height;
	lay( lf.faceBox(), scrolled );
	lf.design._fit();
	return lf.byId('design-faces-results').style.height;
}

const lfBox = function() { return lf.byId('design-faces-results') };

check( 'the box of the list is as tall as the window has left under it: the height of the window less the top of the box, the room the pane keeps for a bar - 68px, which the tab has none of - the pager that stands after it, 40px, and what the card keeps under it, 24px: 800 - 350 - 68 - 40 - 24, so that the card ends above where the bar would stand',
	near( parseFloat( fitList( 800, 0 ) ), 800 - 350 - 68 - 40 - 24 ) && lfBox().style.height === ( 800 - 350 - 68 - 24 - 40 )+ 'px' );
check( '...whatever the page is scrolled to: the box is measured where it stands with the page at its top, as the stage is',
	near( parseFloat( fitList( 800, 120 ) ), 800 - 350 - 68 - 40 - 24 ) && near( parseFloat( fitList( 800, 40 ) ), 800 - 350 - 68 - 40 - 24 ) );
lfBox().scrollTop = 200;
check( '...and the scroll of the box itself is not the page\'s: a box scrolled by 200 is as tall as it was',
	near( parseFloat( fitList( 800, 0 ) ), 800 - 350 - 68 - 40 - 24 ) );
lfBox().scrollTop = 0;
check( 'a window that is higher has more rows to show: 1000 and 1200',
	near( parseFloat( fitList( 1000, 0 ) ), 1000 - 350 - 68 - 40 - 24 ) && near( parseFloat( fitList( 1200, 0 ) ), 1200 - 350 - 68 - 40 - 24 ) );
check( '...never under five rows: five times the 52px of the first row and the four hairlines between them, 264px, where the window is too low for more - and where even the card brought to the top of the window leaves less',
	fitList( 500, 0 ) === ( 5 * 52 + 4 )+ 'px' && fitList( 100, 0 ) === ( 5 * 52 + 4 )+ 'px' );
check( 'where the room under the box is less than five rows but the window is not that low - the cards stand one over the other in a narrow window, and the box far down - the box is made for the window it fills when the page is scrolled to its card, the top of the card at the top of the window: 700px less the 200px the box stands inside the card - the search and the categories are in view with it - the room of the bar, the pager and the foot of the card',
	near( parseFloat( fitList( 700, 0 ) ), 700 - 200 - 68 - 40 - 24 ) && near( parseFloat( fitList( 700, 90 ) ), 700 - 200 - 68 - 40 - 24 ) );

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
	near( parseFloat( lfBox().style.height ), 900 - 350 - 68 - 40 - 24 ) );

lf.view.innerHeight = 1000;
lf.design._fitLater();
lf.waiting( 150 )[0][0]();

check( '...and so does what runs when the resizing has stopped',
	near( parseFloat( lfBox().style.height ), 1000 - 350 - 68 - 40 - 24 ) );

lfBox().style.height = '123px';
lfBox().rect = { top : 0, left : 0, width : 0, height : 0 };
lf.design._fit();

check( 'a tab that is not on screen has no box to give room to: its height is left as it is',
	lfBox().style.height === '123px' );

lay( lf.faceBox(), 0 );
lf.view.innerHeight = 800;

// ---- A list of one page has no pager under the box, and the box has the room it took

const lfSearch = lf.byId('design-faces-search');
const lfWas = lfBox().style.height;

lfSearch.value = 'face 2';
lfSearch.fire('input');

check( 'the rows that the search lets through fit the box as they are drawn, with no resize and no tab: a list of ten stands on one page with no pager after the box, and the box has the 40px of it as well - 800 - 350 - 68 - 24',
	byClass( availableCard( lf ), 'design-faces-pager' ).length === 0 && faceRows( lfBox() ).length === 10 && lfWas !== lfBox().style.height
	&& near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 24 ) );

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

check( 'the rows that come back are fitted as they are drawn, with the pager after the box again: 800 - 350 - 68 - 40 - 24',
	faceRows( lfBox() ).length === 24 && byClass( availableCard( lf ), 'design-faces-pager' ).length === 1 && near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 40 - 24 ) );

// ---- Leaving the tab and coming back

const lfOld = lfBox();

lf.tab( tabWord('surfaces') );
lf.show( 'typefaces' );

check( 'leaving the tab and coming back draws the card again, and the box that stands in the new column is fitted once the column is in the document, with no answer to wait for: 800 - 350 - 68 - 40 - 24',
	lfBox() !== null && lfBox() !== lfOld && faceRows( lfBox() ).length === 24 && near( parseFloat( lfBox().style.height ), 800 - 350 - 68 - 40 - 24 ) );

console.log('');


// --- The frame follows the tab -------------------------------------------------------------
//
// One page with an anchor for each place the panel looks at. The frame is of this very origin, so
// the panel scrolls its window by hand: smoothly after a tab is opened and after
// every change, at once after the page was rebuilt

console.log( 'The anchors' );

/** The ids of the specimen the objects and a few controls of their own are looked at in (OBJECT_ANCHORS, CONTROL_ANCHORS) */
const OBJECT_AIMS = { page : 'global', header : 'global', footer : 'footer', section : 'content', tiles : 'tiles', card : 'cards', buttons : 'buttons', lists : 'lists', tables : 'tables', forms : 'forms', tabs : 'tabs', accordion : 'accordion' };
const CONTROL_AIMS = { 'colours/primary' : 'colours', 'colours/secondary' : 'colours', 'colours/harmony' : 'colours', 'colours/temperature' : 'colours', 'colours/saturation' : 'colours', 'colours/contrast' : 'colours', 'colours/depth' : 'colours', 'colours/mode' : 'colours',
	'motion/hover' : 'cards', 'motion/hover-strength' : 'cards',
	'knobs/section/align' : 'cards', 'knobs/section/measure' : 'cards', 'knobs/section/title-tracking' : 'cards', 'knobs/section/rule' : 'cards', 'knobs/section/rule-length' : 'cards', 'knobs/section/title-shadow' : 'cards', 'knobs/section/title-ground' : 'cards', 'knobs/section/rule-color' : 'cards' };

/**
 *	Where a control is looked at, from the table: the id it has of its own, else that of the object it stands under - how many blocks the object draws is no part of it.
 *	A control of a feature has no id of its own and stands under no object of the page: it is looked at in the block of its feature, where it brings one, else at the top of the page -
 *	`features` is design/list's list of them
 */
function aimOf( layout, key, features ) {

	const at = layout.controls[key];

	if( key.indexOf('features/') === 0 )
		return ( ( features || {} )[ at[1] ] || {} ).anchor || OBJECT_AIMS.page;

	return CONTROL_AIMS[key] || OBJECT_AIMS[ at[1] ] || OBJECT_AIMS.page;
}

const anchors = panel();

check( 'each object has the id of an element on the specimen it is looked at in: global for the page and the header - the top of the page -, footer for the footer, content, tiles, cards, buttons, lists, tables and forms for the others, one for each of the ten; the aspects have none, and the list of the themes is looked at in the top of the page',
	Object.keys( anchors.design.OBJECT_ANCHORS ).join() === OBJECTS.join() && JSON.stringify( anchors.design.OBJECT_ANCHORS ) === JSON.stringify( OBJECT_AIMS ) && anchors.design.THEMES_ANCHOR === 'global' && anchors.design.ANCHORS === undefined );

const last = function() { return anchors.stage.scrolls[anchors.stage.scrolls.length - 1] };

anchors.go('card');
check( 'opening an object asks the frame for the element of that object and scrolls to it, smoothly, from where the page stands: the cards for the card',
	anchors.stage.asked[anchors.stage.asked.length - 1] === 'cards' && anchors.stage.scrolls.length === 1
	&& JSON.stringify( last() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

anchors.go('buttons');
check( '...the block of the buttons for the buttons', anchors.stage.asked[anchors.stage.asked.length - 1] === 'buttons' && JSON.stringify( last() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

anchors.go('page');
check( 'the top of the page, header and all, is where the page goes, wherever the anchor stands',
	JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && anchors.stage.scrolls.length === 3 );

anchors.tab( tabWord('surfaces') );
check( 'an aspect opened moves nothing: the frame stays where the object put it',
	anchors.stage.scrolls.length === 3 );

// What a change of a control of the tables is looked at in: their block
pressKey( anchors, 'knobs/tables/space', 1 );
const before = anchors.stage.scrolls.length;



// A change on the tab: the preview comes back as a stylesheet, which is swapped, and the page goes to the anchor again
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'after every change the preview\'s stylesheet is swapped inside the frame that stands, and the page goes to where the change is looked at - the tables, for a control of theirs',
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

check( '...and then scrolls at once, to where the change is looked at - at once even where the page asks for smooth scrolling of its own - and once: the handler takes itself away',
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

anchors.stage.tops.tables = 1560;
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
anchors.stage.tops.tables = 1500;

/*	The header is fixed, and a page keeps room for it at the top of main - each
	header template sets how much. An anchor stands that far from the top, where
	the first section of a page does, and not under the bar: the bar folds away
	when the page is scrolled down and is back when it is scrolled up, so its
	own height is not a distance to scroll by	*/
anchors.stage.clearance = 144;
anchors.design._jump();

check( 'an anchor stands as far from the top of the frame as main keeps room for the fixed header, and not under the bar', JSON.stringify( last() ) === JSON.stringify( { top : 1456, behavior : 'smooth' } ) );

anchors.stage.tops.content = 20;
pressKey( anchors, 'knobs/section/space', 1 );
anchors.design._jump();

check( '...and never above the top of the page: the first section, which stands where main\'s room ends, is the top', JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

anchors.go('header');

check( '...while the top of the page is the top of the page whatever room the header takes', JSON.stringify( last() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

anchors.stage.clearance = 0;
anchors.stage.tops.content = 300;

/*	A viewport animation plays when an element comes into view, so one that
	stands in view already shows nothing of a choice made while it is there:
	after the animation was changed the ones in view go back to their start,
	without a transition, and are let in again with the one the page has now -
	with the classes put back as the page's own script believes them to be	*/
const hero = animated();
const card = animated('opacity .3s');
anchors.stage.animated = [ hero, card ];

anchors.tab( tabWord('motion') );
anchors.setup._setMotion( 'effect', 'flip' );
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

anchors.setup._setMotion( 'speed', 'fast' );
anchors.stage.reachable = false;
anchors.design._frames = 'first|first||';
anchors.design._previewNow();
anchors.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a frame that cannot be reached, which is built anew and plays on its own', hero.log.length === 0 && threw( function() { anchors.design._replay() } ) === false );

anchors.stage.reachable = true;
anchors.stage.animated = [];
anchors.design._frames = 'first|first||';
anchors.design._edit.size = 'm';
anchors.design._edit.motion = { effect : '', strength : '', speed : '', hover : '', 'hover-strength' : '' };
anchors.design._replays = false;
anchors.requests.length = 0;

// The hover of the pictures is seen under the pointer and nothing comes into view for it: a choice of it sets the place the preview looks at and asks for no replay of the page
anchors.setup._setMotion( 'hover', 'zoom' );

check( 'choosing the kind of hover of the pictures asks for no replay of the page, and sets the place the preview looks at - the cards, whose first row has the pictures',
	anchors.design._replays === false && anchors.design._target === 'cards' && anchors.design._edit.motion.hover === 'zoom' );

anchors.design._target = null;
anchors.setup._setMotion( 'hover-strength', 'hard' );

check( '...and so does choosing its strength', anchors.design._replays === false && anchors.design._target === 'cards' && anchors.design._edit.motion['hover-strength'] === 'hard' );

anchors.design._edit.motion = { effect : '', strength : '', speed : '', hover : '', 'hover-strength' : '' };
anchors.design._target = null;
anchors.requests.length = 0;

// A frame that cannot be reached: the panel asks for the whole page instead of showing a stale one
anchors.stage.reachable = false;
anchors.design._frames = 'first|first||';
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
	&& byClass( fwField, 'nino-admin-switch-copy' )[0].textContent === TEXT['/_admin/design/label/follow'] );
check( '...it is on at the start of the visit: checked, and the panel\'s flag says so - and nothing of it is in the selection',
	fwInput.checked === true && fw.design._follow === true && fw.design._target === null
	&& JSON.stringify( fw.design._selection( fw.design._data ) ).indexOf('follow') === -1 && Object.keys( fw.design._edit ).indexOf('follow') === -1 );

fw.go( 'lists' );
const fwScrolled = fw.stage.scrolls.length;
const fwFitted = fw.fitted.length;

check( 'while it is on, the frame jumps by itself: an object that is opened scrolls it, to the anchor of the object',
	fwScrolled === 1 && JSON.stringify( fw.stage.scrolls[0] ) === JSON.stringify( { top : 1500, behavior : 'smooth' } ) );

fwInput.checked = false;
fwInput.fire('change');

check( 'switched off, the flag is false - and that sends no request, marks nothing unsaved and does not scroll the frame',
	fw.design._follow === false && fw.requests.length === 0 && fw.design._isDirty() === false && fw.byId('design-reset').hidden === true && fw.stage.scrolls.length === fwScrolled );

const fwAsked = fw.stage.asked.length;

fw.go( 'section' );
fw.go( 'page' );
fw.go( 'footer' );
fw.show('theme');

check( 'with it off an object that is opened does not scroll the frame, nor does the list of the themes that the way back draws - three objects and the list, no scrollTo, and the frame\'s page is not even asked for the element - but the stage is fitted all the same, by each of them: four fits',
	fw.stage.scrolls.length === fwScrolled && fw.stage.asked.length === fwAsked && fw.opened === 'design' && fw.fitted.length === fwFitted + 4 );

fw.tab( tabWord('surfaces') );
pressKey( fw, 'knobs/card/space', 1 );
fw.design._previewNow();
fw.answer( 200, { css : ':root{--x:1}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( '...nor does a change: the preview comes back and its stylesheet is swapped inside the frame that stands, which stays where it is - and the stage is fitted once more with the answer - after the fit of the setup drawn again',
	fw.stage.style.textContent === ':root{--x:1}' && fw.stage.scrolls.length === fwScrolled && fw.stage.asked.indexOf('cards') === -1
	&& fw.fitted.length === fwFitted + 6 && fw.design._edit.knobs.card.space === 1 );

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
// Opening a tab goes to the anchor of the tab. A change of one control goes to where that control shows,
// which follows from the table: the id it has of its own - a template to where its set is shown, the
// controls of the palette to the grounds -, else the id of the object it stands under - the page the top
// of the page, a section, its two variants included, the first section of it, a card the cards, the
// buttons, the lists and the tables their blocks -, else the anchor of its tab. The place of a change is
// one target, put back to the tab's own when a tab is opened - a footer that is chosen is the one
// template that is looked at somewhere else: where the page ends

console.log( 'Where the preview jumps' );

const jp = panel();
const jpLayout = jp.design._data.layout;
const jpLast = function() { return jp.stage.scrolls[jp.stage.scrolls.length - 1] };
const jpAsked = function() { return jp.stage.asked[jp.stage.asked.length - 1] };

/** What the preview answers while the frame stands: a stylesheet to swap, and then the page goes where the change is looked at */
function jpAnswer() {
	jp.design._previewNow();
	jp.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
}

check( 'the specimen knows where a control is looked at, by the object it stands under and by itself: the page and the header at the top of the page, the footer at its foot, a section in the first section of the page, a card in the cards, the buttons, the lists, the tables and the forms each in their block - and the eight controls of the palette in the grounds, where the page of the colours has its own anchor no more than the page of the other tabs has the top of the page, and the two of the hover of the pictures in the cards, whose first row has pictures that say the class; the maps say no more than that, and a template is looked at where its object is',
	JSON.stringify( jp.design.OBJECT_ANCHORS ) === JSON.stringify( OBJECT_AIMS ) && JSON.stringify( jp.design.CONTROL_ANCHORS ) === JSON.stringify( CONTROL_AIMS ) && jp.design._target === null );
check( '...and the place of every control follows from the table of the answer: what is set on the page - the root size and the roles of the fonts as much as the width, the whitespace and the corners - and the animation at the top, the palette at the grounds, a section in its section, the two variants of its titles included, a card in the cards, the buttons, the lists and the tables in their blocks, the header at the top of the page and the footer at its foot - the ids the maps name, counted from the table and not from the names of the controls',
	Object.keys( jpLayout.controls ).every( function( key ) { return jp.setup._anchorOf( key ) === aimOf( jpLayout, key ) } )
	&& [ [ 'width', 'global' ], [ 'radii/image', 'global' ], [ 'motion/effect', 'global' ], [ 'size', 'global' ], [ 'fonts/code', 'global' ], [ 'fonts/title', 'global' ], [ 'knobs/section/quiet', 'content' ], [ 'knobs/section/loud', 'content' ], [ 'colours/depth', 'colours' ], [ 'colours/primary', 'colours' ], [ 'colours/mode', 'colours' ],
		[ 'parts/header', 'global' ], [ 'parts/footer', 'footer' ], [ 'parts/section', 'content' ], [ 'parts/tiles', 'tiles' ], [ 'parts/card', 'cards' ], [ 'parts/buttons', 'buttons' ], [ 'parts/lists', 'lists' ], [ 'parts/tables', 'tables' ], [ 'parts/forms', 'forms' ],
		[ 'knobs/header/height', 'global' ], [ 'knobs/header/ground', 'global' ], [ 'knobs/footer/space', 'footer' ], [ 'knobs/footer/ground', 'footer' ], [ 'knobs/footer/border-size', 'footer' ], [ 'knobs/footer/border-color', 'footer' ], [ 'knobs/footer/position', 'footer' ],
		[ 'knobs/section/space', 'content' ], [ 'radii/cover', 'content' ], [ 'knobs/tiles/space', 'tiles' ], [ 'knobs/tiles/gap', 'tiles' ], [ 'radii/tile', 'tiles' ], [ 'knobs/tiles/dim', 'tiles' ], [ 'knobs/section/dim', 'content' ], [ 'knobs/section/align', 'cards' ], [ 'knobs/section/title-shadow', 'cards' ], [ 'knobs/section/title-ground', 'cards' ], [ 'knobs/section/rule-color', 'cards' ], [ 'knobs/card/space', 'cards' ], [ 'knobs/card/tone', 'cards' ], [ 'knobs/card/hover', 'cards' ], [ 'radii/card', 'cards' ], [ 'motion/hover', 'cards' ], [ 'motion/hover-strength', 'cards' ],
		[ 'knobs/buttons/space', 'buttons' ], [ 'knobs/buttons/hover', 'buttons' ], [ 'knobs/lists/marker', 'lists' ], [ 'knobs/lists/space', 'lists' ], [ 'knobs/tables/zebra', 'tables' ], [ 'knobs/tables/stripes', 'tables' ], [ 'knobs/forms/space', 'forms' ], [ 'knobs/forms/edge', 'forms' ], [ 'knobs/forms/label-case', 'forms' ] ].every( function( entry ) { return jp.setup._anchorOf( entry[0] ) === entry[1] } ) );

/*	The place follows the table it is handed: a control moved to another object is looked at where that object is, and a change of it
	sets that as the target	*/
const jpOther = layoutTable();
jpOther.controls['knobs/card/space'] = [ 'surfaces', 'buttons', 9 ];
jpOther.controls['width'] = [ 'surfaces', 'section', 9 ];
jpOther.controls['knobs/tables/zebra'] = [ 'surfaces', 'card', 9 ];
jpOther.controls['colours/depth'] = [ 'surfaces', 'card', 9 ];

const jpMoved = panel( { layout : jpOther } );

jpMoved.show('surfaces');
pressKey( jpMoved, 'width', 1 );

const jpMovedTo = jpMoved.design._target;

pressKey( jpMoved, 'knobs/card/space', 1 );

check( 'the anchors follow the table: the space of a card moved to the buttons is looked at in their block, the width of the page moved to the section in the first section of the page, the stripes of a table moved to the cards in the cards - while a control of the palette is looked at in the grounds wherever the table puts it, a knob moved to the cards as much as the others, since its anchor is its own - and a change of the control sets that place as the target',
	jpMoved.setup._anchorOf('knobs/card/space') === 'buttons' && jpMoved.setup._anchorOf('width') === 'content' && jpMoved.setup._anchorOf('knobs/tables/zebra') === 'cards'
	&& jpMoved.setup._anchorOf('colours/depth') === 'colours' && jpMoved.setup._anchorOf('colours/saturation') === 'colours'
	&& jpMovedTo === 'content' && jpMoved.design._target === 'buttons' );

/*	And it does not depend on how many rows a tab has: the three controls of the animation moved under the card leave the Motion
	tab one row, the card's, and are looked at in the cards - a tab of one row gives a control the anchor of its object as much
	as a tab of five does, and the page's anchor is the tab's only where the table puts the control under the page	*/
const jpAlone = layoutTable();
jpAlone.controls['motion/effect'] = [ 'motion', 'card', 1 ];
jpAlone.controls['motion/strength'] = [ 'motion', 'card', 2 ];
jpAlone.controls['motion/speed'] = [ 'motion', 'card', 3 ];

const jpOneRow = panel( { layout : jpAlone } );

check( 'the place of a control on a tab of one row is its object\'s as much as on a tab of five: the animation moved under the card, the one row of its tab, is looked at in the cards and not at the top of the page',
	jpOneRow.setup._anchorOf('motion/effect') === 'cards' && jpOneRow.setup._anchorOf('motion/strength') === 'cards' && jpOneRow.setup._anchorOf('motion/speed') === 'cards'
	&& jpMoved.setup._anchorOf('motion/effect') === 'global' );

jp.go( 'card' );
const jpOpened = jp.stage.scrolls.length;

check( 'opening an object goes to the anchor of the object and leaves no target: the card is the cards',
	jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) && jp.design._target === null );

pressKey( jp, 'knobs/card/title', 1 );

check( 'a control that is not the page\'s sets the target to the place of its object, and nothing scrolls until the preview has answered: the title of a card, the cards',
	jp.design._target === 'cards' && jp.stage.scrolls.length === jpOpened );

jpAnswer();

check( '...and then the frame goes to the cards: the element with the id cards, from where the page stands',
	jpAsked() === 'cards' && jp.stage.scrolls.length === jpOpened + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

const jpWhere = {};
const jpKnobs = Object.keys( LAYOUT ).filter( function( key ) { return key.indexOf('knobs/') === 0 } );

jpKnobs.forEach( function( key ) {
	jp.show( LAYOUT[key][0] );
	jp.design._target = null;
	moveKey( jp, key, [ 'toggle', 'grade' ].indexOf( KINDS[ key.split('/')[1] ][ key.split('/')[2] ] ) !== -1 ? 1 : -1 );
	jpWhere[key] = jp.design._target;
} );

check( 'each knob has its place - a switch as much as a strip - by the object its row is of: the sizes, the two variants, the space, the rule, the dimming and the case of a section the first section, which the hero is - a card\'s and the sizes of its title the cards, the buttons\', the lists\' and the tables\' their blocks, the header\'s the top of the page and the footer\'s its foot',
	jpKnobs.length === kindCount('scale') + kindCount('choice') + kindCount('toggle') + kindCount('ink') + kindCount('grade') && jpKnobs.every( function( key ) { return jpWhere[key] === aimOf( jpLayout, key ) } )
	&& jpWhere['knobs/section/title'] === 'content' && jpWhere['knobs/section/space'] === 'content' && jpWhere['knobs/section/dim'] === 'content' && jpWhere['knobs/section/quiet'] === 'content' && jpWhere['knobs/section/loud'] === 'content'
	&& jpWhere['knobs/card/subtitle'] === 'cards' && jpWhere['knobs/card/tone'] === 'cards' && jpWhere['knobs/card/hover'] === 'cards' && jpWhere['knobs/buttons/corners'] === 'buttons' && jpWhere['knobs/buttons/hover'] === 'buttons'
	&& jpWhere['knobs/section/title-ink'] === 'content' && jpWhere['knobs/section/subtitle-ink'] === 'content' && jpWhere['knobs/tiles/title-ink'] === 'tiles' && jpWhere['knobs/card/subtitle-ink'] === 'cards'
	&& jpWhere['knobs/section/title-case'] === 'content' && jpWhere['knobs/card/title-case'] === 'cards' && jpWhere['knobs/buttons/case'] === 'buttons' && jpWhere['knobs/footer/nav-case'] === 'footer' && jpWhere['knobs/header/nav-case'] === 'global'
	&& jpWhere['knobs/lists/marker'] === 'lists' && jpWhere['knobs/tables/zebra'] === 'tables' && jpWhere['knobs/tables/stripes'] === 'tables'
	&& jpWhere['knobs/forms/space'] === 'forms' && jpWhere['knobs/forms/edge'] === 'forms' && jpWhere['knobs/forms/label-case'] === 'forms'
	&& jpWhere['knobs/header/nav'] === 'global' && jpWhere['knobs/header/height'] === 'global' && jpWhere['knobs/footer/scale'] === 'footer' && jpWhere['knobs/footer/ground'] === 'footer' );

/*	One change of every other kind of control: a strip of the page, the corners of a kind of surface, a font role, a part of the
	animation, a part of the palette and the mode, and a template - each sets the place the table gives it	*/
const jpDriven = [
	[ 'type', undefined, 'size', function() { pressKey( jp, 'size', 1 ) } ],
	[ 'type', undefined, 'fonts/title', function() { choose( jp.byId('design-font-title-weight'), 'light' ) } ],
	[ 'surfaces', undefined, 'width', function() { pressKey( jp, 'width', 1 ) } ],
	[ 'surfaces', undefined, 'whitespace', function() { pressKey( jp, 'whitespace', 1 ) } ],
	[ 'surfaces', undefined, 'radius', function() { pressKey( jp, 'radius', 1 ) } ],
	[ 'surfaces', undefined, 'radius-alt', function() { pressKey( jp, 'radius-alt', 1 ) } ],
	[ 'surfaces', undefined, 'radii/image', function() { pressKey( jp, 'radii/image', 'alternative' ) } ],
	[ 'surfaces', undefined, 'radii/cover', function() { pressKey( jp, 'radii/cover', 'alternative' ) } ],
	[ 'surfaces', undefined, 'radii/card', function() { pressKey( jp, 'radii/card', 'alternative' ) } ],
	[ 'colours', undefined, 'colours/saturation', function() { byTag( controlOf( jp, 'colours/saturation' ), 'button' )[0].fire('click') } ],
	[ 'colours', undefined, 'colours/mode', function() { choose( jp.byId('design-colour-mode'), 'dark' ) } ],
	[ 'motion', undefined, 'motion/effect', function() { controlOf( jp, 'motion/effect' ); choose( jp.byId('design-motion-effect'), 'flip' ) } ],
	[ 'motion', undefined, 'motion/hover', function() { controlOf( jp, 'motion/hover' ); choose( jp.byId('design-motion-hover'), 'zoom' ) } ],
	[ 'motion', undefined, 'motion/hover-strength', function() { controlOf( jp, 'motion/hover-strength' ); choose( jp.byId('design-motion-hover-strength'), 'hard' ) } ],
	[ 'templates', undefined, 'parts/header', function() { choose( setSelect( jp, 'header' ), 'second' ) } ],
	[ 'templates', undefined, 'parts/footer', function() { choose( setSelect( jp, 'footer' ), 'second' ) } ],
	[ 'templates', undefined, 'parts/section', function() { choose( setSelect( jp, 'section' ), 'second' ) } ],
	[ 'templates', undefined, 'parts/card', function() { choose( setSelect( jp, 'card' ), 'second' ) } ],
	[ 'templates', undefined, 'parts/buttons', function() { choose( setSelect( jp, 'buttons' ), 'third' ) } ],
	[ 'templates', undefined, 'parts/lists', function() { choose( setSelect( jp, 'lists' ), 'second' ) } ],
	[ 'templates', undefined, 'parts/tables', function() { choose( setSelect( jp, 'tables' ), 'fourth' ) } ],
];
const jpDrivenAt = {};

jpDriven.forEach( function( entry ) {
	jp.show( entry[0], entry[1] );
	jp.design._target = null;
	entry[3]();
	jpDrivenAt[ entry[2] ] = jp.design._target;
} );

check( 'a change of any other control sets its place as well - read off the target the change left: a strip of the page, the corners of a kind of surface, a font role, a part of the animation, the palette and its mode, and a template; each where the table has it',
	jpDriven.every( function( entry ) { return jpDrivenAt[ entry[2] ] === aimOf( jpLayout, entry[2] ) } )
	&& jpDrivenAt['size'] === 'global' && jpDrivenAt['fonts/title'] === 'global' && jpDrivenAt['width'] === 'global' && jpDrivenAt['radii/cover'] === 'content' && jpDrivenAt['radii/card'] === 'cards'
	&& jpDrivenAt['colours/saturation'] === 'colours' && jpDrivenAt['motion/effect'] === 'global' && jpDrivenAt['motion/hover'] === 'cards' && jpDrivenAt['motion/hover-strength'] === 'cards' && jpDrivenAt['parts/header'] === 'global' && jpDrivenAt['parts/footer'] === 'footer' && jpDrivenAt['parts/section'] === 'content' && jpDrivenAt['parts/card'] === 'cards'
	&& jpDrivenAt['parts/buttons'] === 'buttons' && jpDrivenAt['parts/lists'] === 'lists' && jpDrivenAt['parts/tables'] === 'tables' );

jp.show( 'type' );
pressKey( jp, 'knobs/section/title', 1 );
jpAnswer();

check( 'a change of the title of a section goes to the first section, where the hero and the sections of the page are shown: #content',
	jp.design._target === 'content' && jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

pressKey( jp, 'knobs/section/subtitle', 1 );
jpAnswer();

check( '...and so does one of its subtitle', jp.design._target === 'content' && jpAsked() === 'content' );

pressKey( jp, 'knobs/section/quiet', 1 );
jpAnswer();

check( 'a change of a variant, which stands in the row of the section on Type, goes to the first section, where the hero is: #content',
	jp.design._target === 'content' && jpAsked() === 'content' );

jp.tab( tabWord('surfaces') );
pressKey( jp, 'knobs/card/shadow', 1 );
jpAnswer();

check( 'one of the cards goes to the cards, whichever knob was moved before it', jp.design._target === 'cards' && jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

// A template is looked at where its set is shown
jp.tab( tabWord('templates') );
choose( setSelect( jp, 'section' ), 'second' );

check( 'choosing a template sets the place it is looked at in - the section set, the first section of the page - and the frame goes there when the preview has answered',
	jp.design._target === 'content' );

jpAnswer();

check( '...the first section, where the hero and the sections of the page are shown', jpAsked() === 'content' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 400, behavior : 'smooth' } ) );

choose( setSelect( jp, 'buttons' ), 'second' );
jpAnswer();

check( '...the buttons in their block, the lists and the tables in theirs, the cards in the cards',
	jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } )
	&& ( choose( setSelect( jp, 'lists' ), 'second' ), jpAnswer(), jpAsked() === 'lists' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1500, behavior : 'smooth' } ) )
	&& ( choose( setSelect( jp, 'tables' ), 'fourth' ), jpAnswer(), jpAsked() === 'tables' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1600, behavior : 'smooth' } ) )
	&& ( choose( setSelect( jp, 'card' ), 'second' ), jpAnswer(), jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) ) );

jp.tab( tabWord('surfaces') );
pressKey( jp, 'knobs/card/border', 1 );
jp.go( 'buttons' );

check( 'opening another object puts the target back, and the frame goes to the anchor of that object: the place a change was looked at belonged to the object that was left',
	jp.design._target === null && jpAsked() === 'buttons' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1300, behavior : 'smooth' } ) );

jp.tab( tabWord('surfaces') );
pressKey( jp, 'width', 1 );

check( 'a control of the page is looked at at the top of the page: the width of Surfaces, #global',
	jp.design._target === 'global' );

jpAnswer();

check( '...and the frame goes there: the element global is the top of the page, header and all', jpAsked() === 'global' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

jp.tab( tabWord('type') );
pressKey( jp, 'size', 1 );
jpAnswer();

check( '...and so is the root size of Type: the page is looked at at the top of it wherever it is set, and the frame goes there',
	jp.design._target === 'global' && jpAsked() === 'global' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

choose( jp.byId('design-font-title-weight'), 'light' );
jpAnswer();

check( '...and so is the weight of a font role, the other half of the page row of Type', jp.design._target === 'global' && jpAsked() === 'global' );

jp.tab( tabWord('type') );
pressKey( jp, 'knobs/buttons/size', 1 );

check( '...and a knob of the buttons, which stands on Type among the others, has the place of its object: #buttons', jp.design._target === 'buttons' );

jp.tab( tabWord('surfaces') );
pressKey( jp, 'knobs/tables/space', 1 );

check( '...and so does one of the tables: #tables', jp.design._target === 'tables' );

pressKey( jp, 'knobs/lists/space', 1 );

check( '...and one of the lists: #lists', jp.design._target === 'lists' );

pressKey( jp, 'knobs/header/space', 1 );

check( '...and one of the header the top of the page, one of the footer its foot: #global and #footer', jp.design._target === 'global' && ( pressKey( jp, 'knobs/footer/space', 1 ), jp.design._target === 'footer' ) );

jp.go( 'card' );

check( 'back on the card the frame goes to the cards, not to the blocks the last change of the object left was looked at in',
	jp.design._target === null && jpAsked() === 'cards' && JSON.stringify( jpLast() ) === JSON.stringify( { top : 1000, behavior : 'smooth' } ) );

// The footer is chosen on the Templates view, and that is a template as well
jp.tab( tabWord('templates') );
jp.design._target = 'cards';
choose( setSelect( jp, 'header' ), 'second' );

check( 'choosing the header is a template too: the target is the top of the page - where a header is looked at - and not what it was', jp.design._target === 'global' );

// A footer is looked at where it starts: the specimen ends in an anchor of its own, which no tab has
jp.design._target = 'cards';
const jpScrolled = jp.stage.scrolls.length;
choose( setSelect( jp, 'footer' ), 'second' );

check( 'choosing the footer makes the end of the page the place it is looked at in: the target is the id of the anchor the specimen ends in, not the top - and nothing scrolls until the preview has answered',
	jp.design._target === 'footer' && jp.stage.scrolls.length === jpScrolled );

jpAnswer();

check( '...and then the frame goes to the foot of the page, which the footer\'s Base would have left at the top: the anchor of the footer, from where the page stands - no room taken off for the header, which is no concern of the end of a page',
	jpAsked() === 'footer' && jp.stage.scrolls.length === jpScrolled + 1 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 4100, behavior : 'smooth' } ) && jp.setup._current() === 'footer' );

jp.stage.clearance = 96;
jp.design._jump();

check( '...whatever room the header asks of the top of main: the footer\'s anchor is placed where it stands', JSON.stringify( jpLast() ) === JSON.stringify( { top : 4100, behavior : 'smooth' } ) );

jp.stage.clearance = 0;
choose( setSelect( jp, 'header' ), 'first' );
jpAnswer();

check( 'a header that is chosen after is looked at at the top again, as it was: the target is the top of the page, and the frame goes there',
	jp.design._target === 'global' && jp.stage.scrolls.length === jpScrolled + 3 && JSON.stringify( jpLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) && jpAsked() === 'global' );

jp.design._target = 'footer';
jp.go( 'lists' );

check( 'the footer is no place of another object: opening one puts the frame where it goes, not at the foot of the page', jp.design._target === null && jpAsked() === 'lists' );

jp.tab( tabWord('templates') );
jp.design._target = 'footer';
jp.design._follow = false;
const jpHeld = jp.stage.scrolls.length;
choose( setSelect( jp, 'footer' ), 'first' );
jp.design._jump();
jp.design._follow = true;

check( 'with the follow switch off a footer that is chosen does not scroll the frame either', jp.stage.scrolls.length === jpHeld && jp.design._target === 'footer' );

// Opening the object already open changes nothing
jp.go( 'card' );
pressKey( jp, 'knobs/card/title', -1 );
jp.go( 'card' );

check( 'the object that is open already is not opened again: its target stays where the last change of a control put it',
	jp.design._target === 'cards' );

jp.tab( tabWord('colours') );
byTag( controlOf( jp, 'colours/saturation' ), 'button' )[0].fire('click');
jp.design._previewNow();
jp.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );

check( 'a knob of the palette is looked at in the grounds', jp.design._target === 'colours' && jpAsked() === 'colours' );

/*	A feature may bring a block to the specimen: design/list answers the id of its section as `anchor` with the feature, '' for none. A control of a feature has an entry in none of the maps - the page knows no
	feature -, and goes to the block of its feature, whichever variant is chosen, else to the anchor of its tab. An id that the page in the frame has not got is looked at where the tab is	*/
const jf = panel( featured() );
const jfLayout = jf.design._data.layout;
const jfFeatures = jf.design._data.features;
const jfLast = function() { return jf.stage.scrolls[jf.stage.scrolls.length - 1] };
const jfAsked = function() { return jf.stage.asked[jf.stage.asked.length - 1] };
const jfKeys = function( key ) { return Object.keys( jfLayout.controls ).filter( function( control ) { return control.indexOf('features/'+ key+ '/') === 0 } ) };
const jfNamed = Object.keys( jfFeatures ).map( function( key ) { return [ key, jfFeatures[key].anchor ] } );
const jfNaming = jfNamed.filter( function( one ) { return one[1] !== '' } );
// The one feature that brings a block: [ feature, id ]
const jfName = jfNaming[0] || [ '', '' ];
const jfOther = Object.keys( jfFeatures ).filter( function( key ) { return key !== jfName[0] } )[0];
const jfVariants = Object.keys( ( jfFeatures[ jfName[0] ] || { catalogue : {} } ).catalogue );
const jfDefault = ( jfFeatures[ jfName[0] ] || {} )['default'];
const jfLoud = jfVariants.filter( function( set ) { return set !== jfDefault } )[0];

/** What the preview answers while the frame stands: a stylesheet to swap, and then the page goes where the change is looked at */
function jfAnswer() {
	jf.design._previewNow();
	jf.answer( 200, { css : ':root{}', document : '', style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] } );
}

check( 'the features of the answer carry the anchor of their block, as design/list gives it: a string with each, the id of an element of the page on one of them and an empty one on the other - so that a feature with a block and one without are both drawn',
	jfNamed.length >= 2 && jfNamed.every( function( one ) { return typeof one[1] === 'string' } ) && jfNaming.length === 1 && jf.stage.tops[ jfName[1] ] !== undefined
	&& jfFeatures[ jfOther ] !== undefined && jfVariants.length >= 2 && jfLoud !== undefined );
check( 'a control of a feature has an entry in none of the maps - no place of its own, and no object of the page that it stands under - and the maps are what they were: the page knows no feature',
	jfKeys( jfName[0] ).length >= 7 && jfKeys( jfOther ).length >= 2
	&& Object.keys( jfLayout.controls ).filter( function( key ) { return key.indexOf('features/') === 0 } ).every( function( key ) { return jf.design.CONTROL_ANCHORS[key] === undefined && jf.design.OBJECT_ANCHORS[ jfLayout.controls[key][1] ] === undefined } )
	&& JSON.stringify( jf.design.OBJECT_ANCHORS ) === JSON.stringify( OBJECT_AIMS ) && JSON.stringify( jf.design.CONTROL_ANCHORS ) === JSON.stringify( CONTROL_AIMS ) );
check( 'every control of the feature that brings a block is looked at in it - the choice of the variant and each knob of it, on whichever tab - and every control of the feature that brings none where its tab is: the table gives the same answer for every row, the rows of the features among them',
	Object.keys( jfLayout.controls ).every( function( key ) { return jf.setup._anchorOf( key ) === aimOf( jfLayout, key, jfFeatures ) } )
	&& jfKeys( jfName[0] ).every( function( key ) { return jf.setup._anchorOf( key ) === jfName[1] } )
	&& jfKeys( jfOther ).every( function( key ) { return jf.setup._anchorOf( key ) === OBJECT_AIMS.page } ) && jf.setup._anchorOf('width') === OBJECT_AIMS.page
	&& jf.setup._anchorOf( 'features/'+ jfOther+ '/set' ) === OBJECT_AIMS.page );

const jfSet = fxSelect( jf, jfName[0] );
const jfScrolled = jf.stage.scrolls.length;

choose( jfSet, jfLoud );

check( 'choosing a variant of that feature makes its block the target of the change - the id of the feature, not the anchor of the tab - and nothing scrolls until the preview has answered',
	jf.design._edit.features[ jfName[0] ].set === jfLoud && jf.design._target === jfName[1] && jf.design._target !== OBJECT_AIMS.page && jf.stage.scrolls.length === jfScrolled );

jfAnswer();

check( '...and then the frame goes there: the element with that id, from where the page stands, as for a place of the page',
	jfAsked() === jfName[1] && jf.stage.scrolls.length === jfScrolled + 1 && JSON.stringify( jfLast() ) === JSON.stringify( { top : jf.stage.tops[ jfName[1] ] + jf.stage.scrollY - jf.stage.clearance, behavior : 'smooth' } ) );

fxControl( jf, jfName[0], 'size' );
pressKey( jf, 'features/'+ jfName[0]+ '/knobs/size', 1 );

check( 'a knob of that feature goes there as well, on an aspect that looks elsewhere for the page: the size, in Text',
	jf.design._target === jfName[1] && jf.design.OBJECT_ANCHORS.page !== jfName[1] );

fxControl( jf, jfOther, 'grid' );
pressKey( jf, 'features/'+ jfOther+ '/knobs/grid', 1 );

check( 'a knob of a feature that brings no block is looked at at the top of the page, whatever the other feature brings',
	jf.design._target === jf.design.OBJECT_ANCHORS.page && jf.design._target !== jfName[1] );

choose( fxSelect( jf, jfName[0] ), jfDefault );

check( 'choosing the variant the feature starts from goes to its block as well: it is the feature that has the place, not the variant',
	jf.design._edit.features[ jfName[0] ].set === jfDefault && jf.design._target === jfName[1] );

jfAnswer();

check( '...and the frame goes there', jfAsked() === jfName[1] && JSON.stringify( jfLast() ) === JSON.stringify( { top : jf.stage.tops[ jfName[1] ] + jf.stage.scrollY - jf.stage.clearance, behavior : 'smooth' } ) );

// A feature with one variant has no select to choose it with; the call is the one the select makes
jf.setup._setFeatureSet( jfOther, Object.keys( jfFeatures[ jfOther ].catalogue )[0] );

check( 'choosing the variant of a feature that brings no block goes where the page looks: the top of the page',
	jf.design._target === OBJECT_AIMS.page );

jfAnswer();

check( '...and the frame goes there', jfAsked() === OBJECT_AIMS.page && JSON.stringify( jfLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

// The variant that is chosen is the one on the screen, stored or chosen here - and a feature that comes to the screen with a variant chosen is looked at in its block from the first change
const jfStored = panel( featured( { [ jfName[0] ] : { set : jfLoud, knobs : {} } } ) );
const jfStoredLayout = jfStored.design._data.layout;

fxControl( jfStored, jfName[0], 'size' );
pressKey( jfStored, 'features/'+ jfName[0]+ '/knobs/size', 1 );

check( 'a feature that came to the screen with a variant chosen is looked at in its block from its first change, each control of the table at the place it follows from',
	jfStored.design._target === jfName[1]
	&& Object.keys( jfStoredLayout.controls ).every( function( key ) { return jfStored.setup._anchorOf( key ) === aimOf( jfStoredLayout, key, jfStored.design._data.features ) } )
	&& jfKeys( jfName[0] ).every( function( key ) { return jfStored.setup._anchorOf( key ) === jfName[1] } ) );

// An id the page in the frame has not got: asked for, given up for the anchor of the tab
jf.go( 'card' );
jf.design._target = 'nowhere';
const jfAskedFrom = jf.stage.asked.length;
const jfScrolledFrom = jf.stage.scrolls.length;
jf.design._jump();

check( 'an id the page in the frame has not got - the block of a feature, and a project may have a specimen of its own - is asked for and given up for the anchor of the open object: the frame goes there and not nowhere',
	jf.stage.asked.slice( jfAskedFrom ).join() === 'nowhere,'+ OBJECT_AIMS.card && jf.stage.scrolls.length === jfScrolledFrom + 1
	&& JSON.stringify( jfLast() ) === JSON.stringify( { top : jf.stage.tops[ OBJECT_AIMS.card ] + jf.stage.scrollY - jf.stage.clearance, behavior : 'smooth' } ) );

jf.show('theme');
jf.design._target = 'nowhere';
const jfAskedThemes = jf.stage.asked.length;
jf.design._jump();

check( '...and on the list of the themes to the top of the page, where that screen looks',
	jf.stage.asked.slice( jfAskedThemes ).join() === 'nowhere,'+ jf.design.THEMES_ANCHOR && JSON.stringify( jfLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

jf.go( 'lists' );
jf.design._target = 'nowhere';
delete jf.stage.tops.lists;
const jfScrolledLast = jf.stage.scrolls.length;
const jfThrew = threw( function() { jf.design._jump() } );
jf.stage.tops.lists = 1400;

check( '...and where the page has not the anchor of the object either the frame goes to the top of the page, which every specimen has, and nothing throws',
	jfThrew === false && jf.stage.scrolls.length === jfScrolledLast + 1 && jfAsked() === OBJECT_AIMS.page && JSON.stringify( jfLast() ) === JSON.stringify( { top : 0, behavior : 'smooth' } ) );

// ...and where the top of the page is not there either - a specimen that is no specimen - the frame stays where it is, and nothing throws
jf.design._target = 'nowhere';
delete jf.stage.tops.lists;
jf.stage.noGlobal = true;
const jfScrolledEnd = jf.stage.scrolls.length;
const jfThrewEnd = threw( function() { jf.design._jump() } );
jf.stage.noGlobal = false;
jf.stage.tops.lists = 1400;

check( '...and where the top of the page is missing too, the end of the chain, the frame stays where it is and nothing throws',
	jfThrewEnd === false && jf.stage.scrolls.length === jfScrolledEnd );

jf.design._target = 'nowhere';
jf.design._follow = false;
const jfAskedOff = jf.stage.asked.length;
const jfScrolledOff = jf.stage.scrolls.length;
jf.design._jump();
jf.design._follow = true;

check( 'with the follow switch off nothing is asked for and nothing scrolls, for an id the page has not got as for any other', jf.stage.asked.length === jfAskedOff && jf.stage.scrolls.length === jfScrolledOff );

// Every id the script jumps to is an element of the specimen, once - read off the template the server answers the page from
const specimen = fs.readFileSync( path.join( __dirname, '../templates/preview-specimen.tpl' ), 'utf8' );
const specimenIds = {};
( specimen.match( /\sid="[a-z-]+"/g ) || [] ).forEach( function( found ) { specimenIds[found.slice( 5, -1 )] = ( specimenIds[found.slice( 5, -1 )] || 0 ) + 1 } );
const jumpedTo = [ jp.design.OBJECT_ANCHORS, jp.design.CONTROL_ANCHORS ].reduce( function( ids, map ) {
	return ids.concat( Object.keys( map ).map( function( key ) { return map[key] } ) );
}, [] ).filter( function( id, at, all ) { return all.indexOf( id ) === at } );

check( 'every id the script jumps to is on the specimen exactly once: the top of the page, the first section, the cards, the buttons, the lists, the tables and the forms, the tabs and the accordion, the tiles, the grounds and, at the end, the footer - twelve places, those of the objects and of the controls with a place of their own',
	jumpedTo.length === 12 && jumpedTo.every( function( id ) { return specimenIds[id] === 1 } ) && specimenIds.cards === 1 && specimenIds.lists === 1 && specimenIds.tables === 1 && specimenIds.forms === 1 && specimenIds.tabs === 1 && specimenIds.accordion === 1 );
check( '...and the footer, which is looked at at the end of the page, is an element of its own: the id footer, once, an empty block at the end of the specimen - after the last section, where the footer of the frame starts',
	specimenIds.footer === 1 && /<\/section>\s*\[\[features\]\]\s*<div id="footer"><\/div>\s*$/.test( specimen ) && specimen.lastIndexOf('id="footer"') > specimen.lastIndexOf('<section ') );
check( '...and the Cards section is the element that has the id, with an accessible name of its own: the second section of the page, after the hero and before the grounds',
	/<section id="cards" class="nino-section" aria-label="[^"]+">/.test( specimen ) === true && specimen.indexOf('id="content"') < specimen.indexOf('id="cards"')
	&& specimen.indexOf('id="cards"') < specimen.indexOf('id="colours"') );
const blockFrame = fs.readFileSync( path.join( __dirname, '../templates/preview-block.tpl' ), 'utf8' );

check( '...and the blocks of the features have a place of their own, which no map of the script knows: the token [[features]], once, after the last section of the specimen and before the footer - and each is the section the block template makes, with the id of its feature, an accessible name and the row of the feature in it',
	( specimen.match( /\[\[features\]\]/g ) || [] ).length === 1 && specimen.indexOf('[[features]]') > specimen.lastIndexOf('<section ') && specimen.indexOf('[[features]]') < specimen.indexOf('id="footer"')
	&& jumpedTo.every( function( id ) { return id.indexOf('feature-') !== 0 } )
	&& /^<section id="\[\[id\]\]" class="nino-section" aria-label="\[\[name\]\]">\s*\[\[block\]\]\s*<\/section>\s*$/.test( blockFrame ) === true );

console.log('');


// --- The tablet and the phone ------------------------------------------------------------------
//
// Two tabs after the objects, apart from them: what the two widths do differently from the desktop, whose settings the objects have. Each draws the accordion of the objects - blocks of Text and Surface, a strip
// for each control in them, Automatic first, then the positions - and opens the preview at the width of its own; a position is part of the working copy, the preview and the theme

console.log( 'The tablet and the phone' );

const tr = panel( {}, { lens : false } );
const trSpec = tr.design._data.tierSpec;
// The row of a control of a tier: in the block that holds it, which is opened where it is closed - one block is open at a time, and the strips of a closed block are not drawn
const trFind = function( screen, tier, control ) {
	return byClass( screen.form, 'design-knob-row' ).filter( function( row ) { return row.dataset.tier === tier && row.dataset.control === 'tier/'+ control } )[0];
};
const trRow = function( screen, tier, control ) {

	if( trFind( screen, tier, control ) === undefined )
		openBlock( blocksOf( screen.form ).filter( function( block ) { return trSpec.blocks[ block.dataset.tab ].indexOf( control ) !== -1 } )[0] );

	return trFind( screen, tier, control );
};
const trPressed = function( screen, tier, control ) {
	return byTag( trRow( screen, tier, control ), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join();
};
const trPress = function( screen, tier, control, value ) {
	byTag( trRow( screen, tier, control ), 'button' ).filter( function( button ) { return button.dataset.value === String( value ) } ).forEach( function( button ) { button.fire('click') } );
};
const trViewports = function( screen ) { return byTag( byClass( screen.pane, 'design-viewports' )[0], 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() };

check( 'the tabs of Tablet and Mobile are a strip of their own beside the strip of the objects, which scrolls without it - no object, a key no feature can have - named by the panel\'s words, each tab controlling the column, and the select has them after the objects',
	byTag( tr.byId('design-tier-strip'), 'button' ).map( function( button ) { return button.dataset.tab } ).join() === 'tier:m,tier:s'
	&& byTag( tr.byId('design-tier-strip'), 'button' ).map( function( button ) { return button.textContent } ).join() === TEXT['/_admin/design/tier/m/label']+ ','+ TEXT['/_admin/design/tier/s/label']
	&& byTag( tr.byId('design-object-strip'), 'button' ).every( function( button ) { return button.dataset.tab.indexOf( 'tier:' ) !== 0 } )
	&& hasClass( tr.byId('design-tier-strip').parent, 'design-tabs' ) && tr.byId('design-object-strip').parent.parent === tr.byId('design-tabs')
	&& tr.byId('design-tab-tier:m').getAttribute('aria-controls') === 'design-controls' && tr.byId('design-tab-tier:m').getAttribute('aria-selected') === 'false'
	&& offered( tr.byId('design-object-select') ).slice( -2 ).map( function( option ) { return option[0]+ ' '+ option[1] } ).join() === 'tier:m '+ TEXT['/_admin/design/tier/m/label']+ ',tier:s '+ TEXT['/_admin/design/tier/s/label']
	&& tr.design._objectKeys().every( function( key ) { return key.indexOf( 'tier:' ) !== 0 } ) );

check( 'each strip of the bar is one stop of the Tab key: the tab that is open, and - for the strip that holds none, the objects while Tablet is open and Tablet and Mobile while an object is - its first tab',
	byTag( tr.byId('design-tier-strip'), 'button' ).filter( function( button ) { return button.tabIndex === 0 } ).map( function( button ) { return button.dataset.tab } ).join() === 'tier:m'
	&& ( tr.go( 'tier:m' ), byTag( tr.byId('design-object-strip'), 'button' ).filter( function( button ) { return button.tabIndex === 0 } ).map( function( button ) { return button.dataset.tab } ).join() === tableObjectKeys[0]
		&& byTag( tr.byId('design-tier-strip'), 'button' ).filter( function( button ) { return button.tabIndex === 0 } ).map( function( button ) { return button.dataset.tab } ).join() === 'tier:m' )
	&& ( tr.go( 'card' ), byTag( tr.byId('design-tier-strip'), 'button' ).filter( function( button ) { return button.tabIndex === 0 } ).map( function( button ) { return button.dataset.tab } ).join() === 'tier:m'
		&& byTag( tr.byId('design-object-strip'), 'button' ).filter( function( button ) { return button.tabIndex === 0 } ).map( function( button ) { return button.dataset.tab } ).join() === 'card' ) );

// What a position of an offset says: the rungs the knobs come to - each from its own desktop rung, held to the ends of its ladder
tr.design._edit.knobs.section.title = 1;
tr.design._edit.knobs.section.loud = -2;
tr.go( 'tier:m' );

check( 'an offset says what it comes to: Automatic the desktop\'s own rungs of the knobs, a step above or below each knob\'s rung held to the end of its ladder - the title of a button; two knobs at different rungs are two numbers',
	rowNote( trRow( tr, 'm', 'title' ) ) === say( '/_admin/design/tier/auto/ladder', TEXT['/_admin/design/tier/knob/section-title']+ ' +1 \u00b7 '+ TEXT['/_admin/design/tier/knob/section-loud']+ ' \u22122' )
	&& byTag( trRow( tr, 'm', 'title' ), 'button' ).map( function( button ) { return button.title } ).join() === [ [ '\u22122', '\u22121', '\u22122' ], [ '\u22121', '0', '\u22122' ], null, [ '+1', '+2', '\u22121' ], [ '+2', '+2', '0' ] ].map( function( step ) { return step === null ? TEXT['/_admin/design/tier/auto'] : step[0]+ ': '+ TEXT['/_admin/design/tier/knob/section-title']+ ' '+ step[1]+ ' \u00b7 '+ TEXT['/_admin/design/tier/knob/section-loud']+ ' '+ step[2] } ).join() );
tr.design._edit.knobs.section.title = 0;
tr.design._edit.knobs.section.loud = 0;

tr.go( 'tier:m' );

// The blocks of a tier are the blocks of an object: the shape of a card, its head and its body, and of the strip in it, compared with those of an object's - but for the classes of the buttons Automatic and the values a tier alone has
const trShape = function( el, deep ) {
	return el.tagName+ '.'+ String( el.className ).split(/\s+/).filter( function( name ) { return name !== '' && name.indexOf('design-tier-') !== 0 } ).sort().join('.')+ '['+ Object.keys( el.attributes ).filter( function( name ) { return [ 'id', 'title', 'class', 'aria-label' ].indexOf( name ) === -1 } ).sort().join()+ ']'
		+ ( deep === true ? '(' + el.children.map( function( child ) { return trShape( child, true ) } ).join() + ')' : '' );
};
const trBlockShape = function( card ) {

	const body = byClass( card, 'design-accordion-body' )[0];
	const row = body === undefined ? undefined : byClass( body, 'design-knob-row' )[0];

	return [ trShape( card ), trShape( headButton( card ), true ), body === undefined ? '' : trShape( body ), row === undefined ? '' : trShape( row ), row === undefined ? '' : trShape( byClass( row, 'design-knob-steps' )[0] ), row === undefined ? '' : trShape( byTag( row, 'button' )[0] ) ].join(' ');
};

tr.go( 'tier:m' );

const trCards = blocksOf( tr.form );
const trAspects = Object.keys( trSpec.blocks );

check( 'opening the tablet draws the accordion of the objects: the sentence of the widths over it and, in the order of the aspects, a block for each aspect the table of the tiers names - the first open, the others closed with no body drawn - each head named by the word the objects\' blocks of that aspect have',
	tr.setup._current() === 'tier:m' && tr.byId('design-controls').getAttribute('aria-labelledby') === 'design-tab-tier:m'
	&& trAspects.length === 2 && trCards.map( function( card ) { return card.dataset.tab } ).join() === trAspects.join() && trAspects.join() === tr.design._data.layout.tabs.filter( function( tab ) { return trAspects.indexOf( tab ) !== -1 } ).join()
	&& trCards.every( function( card ) { return card.dataset.object === 'tier:m' && hasClass( card, 'nino-admin-card' ) && hasClass( card, 'design-form-section' ) && byClass( card, 'design-accordion-name' )[0].textContent === tabWord( card.dataset.tab ) } )
	&& trCards.filter( isOpen ).length === 1 && isOpen( trCards[0] ) === true && byClass( trCards[1], 'design-accordion-body' ).length === 0
	&& trCards[0].parent.children[0] === tr.byId('design-tier-m') && trCards[0].parent === trCards[1].parent && tr.byId('design-tier-m').textContent === TEXT['/_admin/design/tier/m/hint'] && hasClass( tr.byId('design-tier-m'), 'nino-admin-hint' ) );

check( '...the controls of the table stand in the blocks once each, in the order of the table inside a block, each a strip named by its own word with the icon of its row - and nothing of a tier stands outside a block',
	Object.keys( trSpec.controls ).slice().sort().join() === trAspects.reduce( function( all, aspect ) { return all.concat( trSpec.blocks[aspect] ) }, [] ).sort().join()
	&& trAspects.reduce( function( all, aspect ) { return all.concat( trSpec.blocks[aspect] ) }, [] ).length === Object.keys( trSpec.controls ).length
	&& trAspects.every( function( aspect ) { return trSpec.blocks[aspect].join() === Object.keys( trSpec.controls ).filter( function( control ) { return trSpec.blocks[aspect].indexOf( control ) !== -1 } ).join() } )
	&& trAspects.every( function( aspect ) {
		const card = trCards.filter( function( each ) { return each.dataset.tab === aspect } )[0];
		openBlock( card );
		return byClass( card, 'design-knob-row' ).map( function( row ) { return row.dataset.control } ).join() === trSpec.blocks[aspect].map( function( control ) { return 'tier/'+ control } ).join()
			&& trSpec.blocks[aspect].every( function( control ) { return rowName( trFind( tr, 'm', control ) ) === TEXT['/_admin/design/tier/control/'+ control+ '/label'] && byTag( trFind( tr, 'm', control ), 'svg' )[0].dataset.icon === ICON_OF['tier/'+ control] } );
	} ) );

const trObject = panel( {}, { lens : false } );
goTo( trObject, 'card' );
const trObjectCards = blocksOf( trObject.form ).filter( function( card ) { return card.dataset.tab === 'surfaces' || card.dataset.tab === 'type' } );
openBlock( trObjectCards[0] );

const trSame = function( at ) { return trBlockShape( trCards[at] ) === trBlockShape( trObjectCards[at] ) };

check( 'a block of a tier is made as a block of an object: the same card, the same head with its name, its line and its chevron, the same body and, in it, the same strip of the workbench - compared with the blocks of the card, each open and closed',
	trObjectCards.length === 2 && ( openBlock( trCards[0] ), openBlock( trObjectCards[0] ), trSame( 0 ) && trSame( 1 ) && trBlockShape( trCards[0] ).indexOf('design-knob-row') !== -1 && trBlockShape( trCards[1] ).indexOf('design-knob-row') === -1 )
	&& ( openBlock( trCards[1] ), openBlock( trObjectCards[1] ), trSame( 0 ) && trSame( 1 ) && trBlockShape( trCards[1] ).indexOf('design-knob-row') !== -1 ) && byClass( tr.form, 'design-tier' ).length === 0 );

tr.go( 'tier:m' );

check( '...every strip has Automatic, lit - nothing is set: first on the root size and the margin, which state a value and go on with five positions, and in the middle on every offset - between the two steps below and the two above the desktop\'s own - the gap among them, which has no middle value of its own',
	Object.keys( trSpec.controls ).every( function( control ) {
		const buttons = byTag( trRow( tr, 'm', control ), 'button' );
		const at = buttons.map( function( button ) { return button.dataset.value } ).indexOf( '' );
		const offset = trSpec.controls[control].length > 0;
		return at === ( offset === true ? 2 : 0 ) && buttons[at].textContent === TEXT['/_admin/design/tier/auto'] && hasClass( buttons[at], 'design-tier-auto' ) && buttons[at].getAttribute('aria-pressed') === 'true'
			&& buttons.filter( function( button, index ) { return index !== at } ).map( function( button ) { return button.dataset.value } ).join() === trSpec.positions[control].join()
			&& buttons.filter( function( button, index ) { return index !== at } ).every( function( button ) { return button.getAttribute('aria-pressed') === 'false' } );
	} ) && byTag( trRow( tr, 'm', 'gap' ), 'button' ).map( function( button ) { return button.textContent } ).join() === '\u22122,\u22121,'+ TEXT['/_admin/design/tier/auto']+ ',+1,+2'
	&& byTag( trRow( tr, 'm', 'inset' ), 'button' ).map( function( button ) { return button.textContent } ).join() === '\u22122,\u22121,'+ TEXT['/_admin/design/tier/auto']+ ',+1,+2' );

tr.go( 'tier:s' );

check( 'the two that state a value say it on the buttons - a touch screen has no title to read: the root size as a percentage and the margin in rem, the values of the phone\'s half of the tables - and an offset says the number of rungs; the title of each is the whole sentence',
	byTag( trRow( tr, 's', 'size' ), 'button' ).slice( 1 ).map( function( button ) { return button.textContent } ).join() === '85 %,92.5 %,100 %,115 %,145 %'
	&& byTag( trRow( tr, 's', 'margin' ), 'button' ).slice( 1 ).map( function( button ) { return button.textContent } ).join() === '0.5 rem,0.75 rem,1 rem,1.5 rem,2 rem'
	&& byTag( trRow( tr, 's', 'size' ), 'button' )[3].title === '0: '+ say( '/_admin/design/tier/value/size', '100', '16' ) && byTag( trRow( tr, 's', 'subtitle' ), 'button' ).map( function( button ) { return button.textContent } ).join() === '\u22122,\u22121,'+ TEXT['/_admin/design/tier/auto']+ ',+1,+2'
	&& byTag( trRow( tr, 's', 'gap' ), 'button' )[0].title === '\u22122: '+ say( '/_admin/design/tier/value/gap', '0.5', say( '/_admin/design/tier/rung', '0' ) )
	&& byTag( trRow( tr, 's', 'gap' ), 'button' )[4].title === '+2: '+ say( '/_admin/design/tier/value/gap', '2', say( '/_admin/design/tier/rung', '+2' ) ) );

tr.go( 'tier:m' );

check( 'what Automatic is stands under the name of each control, in the words of its kind and at this moment: the root size of the tablet - the second half of the pair of the page\'s own position - and the gutter, then the position the knob of the desktop stands at, and the gap as the page has it',
	rowNote( trRow( tr, 'm', 'size' ) ) === say( '/_admin/design/tier/auto/size', '112.5', '18' ) && rowNote( trRow( tr, 'm', 'margin' ) ) === say( '/_admin/design/tier/auto/margin', '2' )
	&& rowNote( trRow( tr, 'm', 'gap' ) ) === TEXT['/_admin/design/tier/auto/gap'] && rowNote( trRow( tr, 'm', 'areas' ) ) === say( '/_admin/design/tier/auto/ladder', say( '/_admin/design/tier/rung', '0' ) )
	&& ( tr.go( 'tier:s' ), rowNote( trRow( tr, 's', 'size' ) ) === say( '/_admin/design/tier/auto/size', '100', '16' ) && rowNote( trRow( tr, 's', 'margin' ) ) === say( '/_admin/design/tier/auto/margin', '1' ) ) );

tr.go( 'tier:m' );

check( 'the preview is at the width of the tier when its tab is opened: the tablet\'s button is lit and the frame is as wide as the tablet\'s button says - and the phone\'s after the phone - an object that is opened after it leaves the width as it is',
	tr.design._width === tr.design.WIDTHS.filter( function( width ) { return width.key === 'tablet' } )[0].px && trViewports( tr ) === 'false,true,false'
	&& ( tr.go( 'tier:s' ), tr.design._width === tr.design.WIDTHS.filter( function( width ) { return width.key === 'phone' } )[0].px && trViewports( tr ) === 'true,false,false' )
	&& ( tr.go( 'card' ), tr.design._width === tr.design.WIDTHS.filter( function( width ) { return width.key === 'phone' } )[0].px && trViewports( tr ) === 'true,false,false' ) );

tr.go( 'tier:m' );
tr.requests.length = 0;
trPress( tr, 'm', 'size', 1 );
tr.design._previewNow();

check( 'a position pressed is a position of the working copy, lit with Automatic dark, and the preview asked for carries the tiers with it - nothing else of the model moved - and is looked at where the control shows',
	JSON.stringify( tr.design._edit.tiers ) === JSON.stringify( { m : { size : 1 }, s : {} } ) && trPressed( tr, 'm', 'size' ) === 'false,false,false,false,true,false'
	&& JSON.stringify( tr.requests[tr.requests.length - 1].payload.tiers ) === JSON.stringify( { m : { size : 1 }, s : {} } ) && tr.design._target === 'global'
	&& tr.byId('design-reset').hidden === false && tr.design._isDirty() === true );

trPress( tr, 'm', 'title', -2 );
trPress( tr, 'm', 'gap', 2 );
tr.go( 'tier:s' );
trPress( tr, 's', 'button', -1 );

check( '...each tier has its own, and a control of another tier is not moved: the phone and the tablet keep what each was given',
	JSON.stringify( tr.design._edit.tiers ) === JSON.stringify( { m : { size : 1, title : -2, gap : 2 }, s : { button : -1 } } ) && trPressed( tr, 's', 'size' ) === 'true,false,false,false,false,false'
	&& trPressed( tr, 's', 'button' ) === 'false,true,false,false,false' && tr.design._target === 'buttons' );

trPress( tr, 's', 'button', '' );
tr.go( 'tier:m' );
trPress( tr, 'm', 'size', '' );

check( '...and Automatic takes a control back to nothing: the key is gone, not set to a value - and the selection that is left is the one that was, the way back drawn only for what differs',
	JSON.stringify( tr.design._edit.tiers ) === JSON.stringify( { m : { title : -2, gap : 2 }, s : {} } ) && trPressed( tr, 'm', 'size' ) === 'true,false,false,false,false,false' && tr.design._isDirty() === true
	&& ( trPress( tr, 'm', 'title', '' ), trPress( tr, 'm', 'gap', '' ), tr.design._isDirty() === false && JSON.stringify( tr.design._edit.tiers ) === JSON.stringify( { m : {}, s : {} } ) ) );

// The head of a closed block says what in it is off Automatic, as the head of a block of an object says what is off the defaults: the name of the control and the word its button shows
const trCard = function( screen, aspect ) { return blocksOf( screen.form ).filter( function( card ) { return card.dataset.tab === aspect } )[0] };
const trLine = function( screen, aspect ) { return byClass( trCard( screen, aspect ), 'design-accordion-summary' )[0].textContent };
const trWord = function( screen, tier, control, value ) { return byTag( trRow( screen, tier, control ), 'button' ).filter( function( button ) { return button.dataset.value === String( value ) } )[0].textContent };

tr.go( 'tier:m' );
trPress( tr, 'm', 'size', 1 );
trPress( tr, 'm', 'button', -1 );

const trSizeWord = trWord( tr, 'm', 'size', 1 );

trPress( tr, 'm', 'gap', 2 );

check( 'the head of a closed block names what in it is off Automatic - the control and the word its button shows: the value for the two that state one, the number of rungs for the others - joined by the middle dot, in the order of the block; the block that is open says nothing',
	isOpen( trCard( tr, 'surfaces' ) ) === true && trLine( tr, 'surfaces' ) === '' && isOpen( trCard( tr, 'type' ) ) === false
	&& trLine( tr, 'type' ) === TEXT['/_admin/design/tier/control/size/label']+ ' '+ trSizeWord+ ' \u00b7 '+ TEXT['/_admin/design/tier/control/button/label']+ ' \u22121'
	&& byClass( trCard( tr, 'type' ), 'design-accordion-summary' )[0].title === trLine( tr, 'type' ) );

toggle( trCard( tr, 'surfaces' ) );

check( '...closed, the other block says its own, and the other tier has its own line and its own open block: what the tablet was given is not said of Mobile',
	trLine( tr, 'surfaces' ) === TEXT['/_admin/design/tier/control/gap/label']+ ' +2' && trLine( tr, 'type' ) !== ''
	&& ( tr.go( 'tier:s' ), blocksOf( tr.form ).every( function( card ) { return isOpen( card ) === ( card.dataset.tab === 'type' ) && byClass( card, 'design-accordion-summary' )[0].textContent === '' } ) ) );

tr.go( 'tier:m' );
trPress( tr, 'm', 'gap', '' );
toggle( trCard( tr, 'surfaces' ) );

check( '...and a position taken back to Automatic is said no more',
	isOpen( trCard( tr, 'surfaces' ) ) === false && trLine( tr, 'surfaces' ) === '' && trLine( tr, 'type' ) !== '' );

check( '...the open block is remembered for each tier, as for each object: the one that was left open is open when the tab is opened again, whichever tab was open between, Mobile has its own, and a block closed stays closed',
	( openBlock( trCard( tr, 'surfaces' ) ), tr.go( 'tier:s' ), isOpen( trCard( tr, 'type' ) ) === true && isOpen( trCard( tr, 'surfaces' ) ) === false )
	&& ( tr.go( 'card' ), tr.go( 'tier:m' ), isOpen( trCard( tr, 'surfaces' ) ) === true && isOpen( trCard( tr, 'type' ) ) === false )
	&& ( toggle( trCard( tr, 'surfaces' ) ), tr.go( 'tier:s' ), tr.go( 'tier:m' ), blocksOf( tr.form ).filter( isOpen ).length === 0 ) );

trPress( tr, 'm', 'size', '' );
trPress( tr, 'm', 'button', '' );

// What Automatic means follows the settings of the objects, which are the desktop's: the root size of the page, the width of it and the position of a knob
tr.design._edit.size = -2;
tr.design._edit.width = 2;
tr.design._edit.knobs.section.space = 1;
tr.go( 'tier:s' );
tr.go( 'tier:m' );

check( 'Automatic follows the page: the root size the setup has - for the width of the tier - the gutter its width makes, and the position of the knob of the desktop',
	rowNote( trRow( tr, 'm', 'size' ) ) === say( '/_admin/design/tier/auto/size', '95.63', '15.3' ) && rowNote( trRow( tr, 'm', 'margin' ) ) === say( '/_admin/design/tier/auto/margin', '0' )
	&& rowNote( trRow( tr, 'm', 'areas' ) ) === say( '/_admin/design/tier/auto/ladder', say( '/_admin/design/tier/rung', '+1' ) ) && ( tr.go( 'tier:s' ), rowNote( trRow( tr, 's', 'size' ) ) === say( '/_admin/design/tier/auto/size', '85', '13.6' ) && rowNote( trRow( tr, 's', 'margin' ) ) === say( '/_admin/design/tier/auto/margin', '1' ) ) );

// A control that moves the knobs of a part is greyed out where no template that is chosen declares one: switched off, lit none, with the line that says so in place of the note
const trAnswers = panel( { parts : listing().parts.map( function( row ) { return row.part === 'buttons' ? Object.assign( {}, row, { catalogue : { first : Object.assign( {}, row.catalogue.first, { knobs : [] } ), second : Object.assign( {}, row.catalogue.second, { knobs : [] } ) } } ) : row } ) }, { lens : false } );
trAnswers.go( 'tier:s' );

check( 'a control whose knobs no template that is chosen declares is greyed out and switched off - the strip lit none - with the line that says so where the note would be; the others are not, and the three that move no knob never are',
	hasClass( trRow( trAnswers, 's', 'button' ), 'design-knob-row--off' ) && byTag( trRow( trAnswers, 's', 'button' ), 'button' ).every( function( button ) { return button.disabled === true } )
	&& trPressed( trAnswers, 's', 'button' ) === 'false,false,false,false,false' && rowNote( trRow( trAnswers, 's', 'button' ) ) === say( '/_admin/design/hint/unanswered', TEXT['/_admin/design/tier/control/button/label'] )
	&& [ 'size', 'title', 'margin', 'gap', 'inset', 'header' ].every( function( control ) { return hasClass( trRow( trAnswers, 's', control ), 'design-knob-row--off' ) === false } ) );

// A control no template that is chosen answers to says nothing: its strip is greyed out, as a knob is that says nothing in the head of an object
trAnswers.design._edit.tiers = { m : {}, s : { size : 1, button : 1 } };
goTo( trAnswers, 'tier:s', true );

const trAnswersSize = trWord( trAnswers, 's', 'size', 1 );

if( isOpen( trCard( trAnswers, 'type' ) ) === true )
	toggle( trCard( trAnswers, 'type' ) );

check( '...and a control that no template chosen answers to is left out of the head of its block, as a knob that is greyed out is left out of the head of an object',
	trLine( trAnswers, 'type' ) === TEXT['/_admin/design/tier/control/size/label']+ ' '+ trAnswersSize );

// What a setup carries is read as a copy of its own, an empty list on the wire is a tier that sets nothing, and a tier the answer does not know is not made
check( 'the tiers of an answer are a copy of their own, one list for each tier the answer names - an empty list on the wire is no choice - and what is not a tier is not kept',
	JSON.stringify( tr.design._tiersOf( { m : [], s : { size : 1 }, xl : { size : 1 } } ) ) === JSON.stringify( { m : {}, s : { size : 1 } } ) && JSON.stringify( tr.design._tiersOf( [] ) ) === JSON.stringify( { m : {}, s : {} } )
	&& JSON.stringify( tr.design._tiersOf( undefined ) ) === JSON.stringify( { m : {}, s : {} } ) && ( function() { const given = { m : { size : 1 } }; const copy = tr.design._tiersOf( given ); copy.m.size = 2; return given.m.size === 1 } )() );

// --- What the preview carries ----------------------------------------------------------------

console.log( 'The preview' );

const asked = panel();
asked.design._previewNow();
const first = asked.requests[asked.requests.length - 1];

check( 'the first preview of a visit asks for the whole document, with every key of the model: the templates, the knobs, the styles of the templates, the choices for the features, the size, the width, the animation, the fonts, the palette, and the theme with the css of its own',
	first.endpoint === 'preview' && first.payload.full === true && Object.keys( first.payload ).sort().join() === 'colours,css,features,fonts,full,knobs,motion,navs,parts,radii,radius,radius-alt,size,styles,theme,tiers,whitespace,width'
	&& first.payload.parts.header === 'first' && first.payload.parts.tables === 'first' && first.payload.knobs.section.title === 0
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

choose( setSelect( asked, 'footer' ), 'second' );
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
// The paths of the files under a question: what stands right before its buttons, after the text and after what the shell adds to it
const detailsOf = function( ask ) {
	const kids = ask.buttons.parent.children;
	return kids[kids.indexOf( ask.buttons ) - 1];
};
const fileWord = function( part ) { return TEXT['/_admin/design/confirm/file/'+ ( part || 'look' )] };

/** The requests a click on "Apply to website" sends, in order */
function endpoints( screen ) {
	return screen.requests.map( function( request ) { return request.endpoint } );
}

const asking = panel( { files : delivered.map( function( file ) { return Object.assign( {}, file ) } ) } );
const buttons = byTag( byClass( asking.form, 'nino-admin-actionbar' )[0], 'button' ).map( function( button ) { return button.textContent } );

check( 'the two buttons say what they do: save a draft, apply to the website - and there is no take-over button, and no way back to a version that is not there',
	buttons.indexOf( TEXT['/_admin/design/label/save'] ) !== -1 && buttons.indexOf( TEXT['/_admin/design/label/apply'] ) !== -1 && buttons.length === 3 && asking.byId('design-restore') === null );
check( 'no line under the bar says which of the two does what: the hint of the actions is gone from the screen and from the panel\'s words in both languages - nobody read it',
	asking.byId('design-actions-hint') === null && TEXT['/_admin/design/hint/actions'] === undefined && asking.form.children[asking.form.children.length - 1] === asking.bar() );
check( 'with files that are not Design\'s the state is foreign, and the chip says so in one word - the files are named where the question is asked, in the confirmation below',
	asking.design._stateKey() === 'foreign' && asking.chip().textContent === shortWord('foreign') && asking.chip().dataset.state === 'foreign'
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
	&& question.options.choices.map( function( choice ) { return choice.value+ ':'+ choice.kind } ).join() === 'yes:primary,no:secondary'
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
const detail = detailsOf( question );
const detailLines = byTag( detail, 'li' ).map( function( item ) { return [ byTag( item, 'code' )[0].textContent, byClass( item, 'design-confirm-state' )[0].textContent.trim() ] } );

check( 'the paths of the files are in a closed <details> under the text and before the buttons: its summary and a line for each file - the path, as a project knows it, and what happens to the file in words',
	detail !== null && detail.tagName === 'DETAILS' && hasClass( detail, 'design-confirm-files' ) && detail.getAttribute('open') === null && byTag( detail, 'summary' )[0].textContent === TEXT['/_admin/design/confirm/files']
	&& question.text.parent.children.indexOf( detail ) === question.text.parent.children.indexOf( question.buttons ) - 1
	&& JSON.stringify( detailLines ) === JSON.stringify( [
		[ 'assets/theme.css', TEXT['/_admin/design/confirm/state/foreign'] ],
		[ 'templates/frame-header.tpl', TEXT['/_admin/design/confirm/state/foreign'] ],
		[ 'templates/frame-footer.tpl', TEXT['/_admin/design/confirm/state/foreign'] ],
	] ) && hasClass( question.text, 'design-confirm-text' ) );

question.choose('yes');

check( '...and only then is the apply sent, with force because a file is not Design\'s',
	JSON.stringify( endpoints( asking ) ) === JSON.stringify( [ 'apply' ] ) && asking.requests[0].payload.force === true );

asking.answer( 200, {} );
asking.answer( 200, listing( { exists : true } ) );

check( 'what the screen says afterwards is that it applied, that files were replaced, and what to put back by hand',
	( asking.byId('design-msg') || {} ).textContent === [ TEXT['/_admin/design/msg/applied'], TEXT['/_admin/design/msg/takenover'], say( '/_admin/design/msg/lost', '[consent-settings]' ) ].join(' ') );
check( '...in the line of the bar, the whole of it its title, and the chip is drawn again from what the server then answered: the files are Design\'s now and the state is up to date',
	asking.line().parent === asking.bar() && asking.line().title === asking.line().textContent && asking.line().className === ''
	&& asking.chip().parent === asking.bar() && asking.chip().textContent === shortWord('current') && asking.chip().dataset.state === 'current' );

/*	A navigation that is chosen is a file of the apply like the frames: named in the question by the part it is for and what it is - the navigation of the header, of the footer -,
	and its path stands in the details with what happens to it	*/
const withNavs = delivered.concat( [
	{ target : '/templates/html-header-nav.tpl', part : 'header', kind : 'nav', exists : true, state : 'foreign', changes : true, lost : [ '[navigation nav="main"]' ] },
	{ target : '/templates/html-footer-nav.tpl', part : 'footer', kind : 'nav', exists : false, state : 'missing', changes : true, lost : [] },
] );
const navAsking = panel( {} );
navAsking.byId('design-apply').fire('click');
navAsking.answer( 200, {} );
navAsking.answer( 200, { files : withNavs } );
const navLines = navAsking.asks[0].options.message.split('\n');
const navWord = function( part ) { return TEXT['/_admin/design/confirm/file/'+ part+ '-nav'] };

check( 'the files of the two navigations are named by what they are for, in the question and not by their path, and a file that is not Design\'s is said so with the shortcode it would lose',
	navWord('header') !== undefined && navWord('footer') !== undefined && navWord('header') !== navWord('footer')
	&& navLines.indexOf( say( '/_admin/design/confirm/apply', [ fileWord( '' ), fileWord( 'header' ), fileWord( 'footer' ), navWord('header'), navWord('footer') ].join(', ') ) ) !== -1
	&& navLines.indexOf( say( '/_admin/design/confirm/foreign', [ fileWord( '' ), fileWord( 'header' ), fileWord( 'footer' ), navWord('header') ].join(', ') ) ) !== -1
	&& navLines.indexOf( say( '/_admin/design/confirm/lost', '[navigation nav="main"]' ) ) !== -1 && /assets\/|templates\/|\.css|\.tpl/.test( navAsking.asks[0].options.message ) === false );
check( '...and their paths stand in the details, the one that is not there created',
	byTag( detailsOf( navAsking.asks[0] ), 'li' ).slice( 3 ).map( function( item ) { return [ byTag( item, 'code' )[0].textContent, byClass( item, 'design-confirm-state' )[0].textContent.trim() ].join(' ') } ).join() === [
		'templates/html-header-nav.tpl '+ TEXT['/_admin/design/confirm/state/foreign'], 'templates/html-footer-nav.tpl '+ TEXT['/_admin/design/confirm/state/missing'] ].join() );

// Saying no leaves the draft saved and the website alone
const declined = panel( {} );
declined.byId('design-apply').fire('click');
declined.answer( 200, {} );
declined.answer( 200, { files : delivered } );
declined.asks[0].choose('no');

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
const oursDetail = byTag( detailsOf( ours.asks[0] ), 'li' ).map( function( item ) { return byClass( item, 'design-confirm-state' )[0].textContent.trim() } );
ours.asks[0].choose('yes');

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
edited.asks[0].choose('yes');

check( 'a frame that was only edited gets force too - the refusal has something to answer to now - and the dialog says which: by what it is for, and in the details as edited by hand',
	JSON.stringify( endpoints( edited ) ) === JSON.stringify( [ 'apply' ] ) && edited.requests[0].payload.force === true
	&& editedLines.indexOf( say( '/_admin/design/confirm/foreign', fileWord( 'footer' ) ) ) !== -1 );

/*	What an apply tells the others: the frames and theme.css are files of every page, so the touch of the templates carries no address - the window beside the
	workbench loads the page it shows anew, and the panels that show templates ask again when they are shown. After the answer of the server, once, before the panel is
	drawn again; not for a draft that was only saved, not for a no, not for an apply that failed	*/
( function() {
	const told = function( screen ) { return JSON.stringify( screen.touched ) };
	const toTheApply = function( screen ) {
		screen.byId('design-apply').fire('click');
		screen.answer( 200, {} );
		screen.answer( 200, { files : delivered.map( function( file ) { return Object.assign( {}, file ) } ) } );
	};

	const applied = panel( {} );
	toTheApply( applied );
	applied.asks[0].choose('yes');
	check( 'saving the draft and asking what an apply would write tell nobody: the touch waits for the apply to be answered', JSON.stringify( endpoints( applied ) ) === JSON.stringify( [ 'apply' ] ) && applied.touched.length === 0 );

	applied.answer( 200, {} );
	check( 'a successful apply touches the templates once, with that kind alone, before the panel is asked for its listing anew',
		told( applied ) === JSON.stringify( [ [ 'templates' ] ] ) && JSON.stringify( endpoints( applied ) ) === JSON.stringify( [ 'list' ] ) );

	applied.answer( 200, listing( { exists : true } ) );
	check( '...and drawing the panel again from the listing touches nothing more', told( applied ) === JSON.stringify( [ [ 'templates' ] ] ) );

	const refused = panel( {} );
	toTheApply( refused );
	refused.asks[0].choose('no');
	refused.answer( 200, listing( {} ) );
	check( 'a no to the question tells nobody', refused.touched.length === 0 );

	const failed = panel( {} );
	toTheApply( failed );
	failed.asks[0].choose('yes');
	failed.answer( 500, { error : 'could not write /assets/theme.css' } );
	check( 'an apply that failed tells nobody, and says why', failed.touched.length === 0 && ( failed.byId('design-msg') || {} ).textContent === '(500) could not write /assets/theme.css' );

	const restored = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
	restored.willConfirm( false );
	restored.byId('design-restore').fire('click');
	restored.settle();
	check( 'a no to the question of the restore tells nobody', restored.touched.length === 0 );

	restored.willConfirm( true );
	restored.byId('design-restore').fire('click');
	restored.settle();
	check( 'the restore tells nobody before the server has answered', JSON.stringify( endpoints( restored ) ) === JSON.stringify( [ 'restore' ] ) && restored.touched.length === 0 );

	restored.answer( 200, {} );
	check( 'a restore that went through touches the templates once, with that kind alone, before the panel is asked for its listing anew',
		told( restored ) === JSON.stringify( [ [ 'templates' ] ] ) && JSON.stringify( endpoints( restored ) ) === JSON.stringify( [ 'list' ] ) );

	const lost = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
	lost.byId('design-restore').fire('click');
	lost.settle();
	lost.answer( 500, { error : 'could not write /assets/theme.css' } );
	check( 'a restore that failed tells nobody', lost.touched.length === 0 );
} )();

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

const movedOn = replacedTemplates( {}, function( screen ) { screen.tab( tabWord('templates') ); choose( setSelect( screen, 'header' ), 'second' ) } );
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

check( 'saving a draft sends no plan and asks nothing', drafting.questions().length === 0 && JSON.stringify( endpoints( drafting ) ) === JSON.stringify( [ 'list' ] ) );

drafting.answer( 200, listing( { current : false } ) );

check( '...it says that the draft is saved in the line of the bar, and the chip is drawn again from the answer: the file does not answer to the selection - saved, not applied',
	drafting.line().textContent === TEXT['/_admin/design/msg/saved'] && drafting.line().title === TEXT['/_admin/design/msg/saved'] && drafting.line().className === ''
	&& drafting.chip().textContent === shortWord('drifted') && drafting.chip().dataset.state === 'drifted' && drafting.chip().parent === drafting.bar() );

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
	&& restoreButton.textContent === TEXT['/_admin/design/label/restore'] && restoreButton.title === say( '/_admin/design/state/previous', '2026-09-02 08:30' )
	&& keptBar.children.map( function( button ) { return button.id } ).join() === 'design-msg,design-state-word,design-restore,design-reset,design-save,design-apply'
	&& restoreButton.hidden === false && kept.byId('design-reset').hidden === true );
check( '...and the card that used to hold the button, and the line that dated the version, are gone',
	kept.byId('design-state') === null && byClass( kept.form, 'design-previous' ).length === 0 && byClass( kept.byId('design-controls'), 'nino-admin-hint' ).length === 0 );

kept.willConfirm( false );
restoreButton.fire('click');
kept.settle();

check( 'the button asks first, and a no sends nothing - it is the restore of the screen that is called, with the button itself',
	kept.questions().length === 1 && kept.questions()[0] === TEXT['/_admin/design/confirm/restore'] && kept.requests.length === 0
	&& restoreCalls.length === 1 && restoreCalls[0] === restoreButton );

kept.willConfirm( true );
restoreButton.fire('click');
kept.settle();

check( 'a yes posts the restore', JSON.stringify( endpoints( kept ) ) === JSON.stringify( [ 'restore' ] ) );

kept.answer( 200, {} );
kept.answer( 200, listing( {} ) );

check( 'and the panel is drawn again from the new state, saying so in the line of the bar', ( kept.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored']
	&& kept.line().parent === kept.bar() && kept.line().title === TEXT['/_admin/design/msg/restored'] && kept.chip().textContent === shortWord('current') );

const noted = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
noted.willConfirm( true );
noted.byId('design-restore').fire('click');
noted.settle();
noted.answer( 200, { notes : [ 'restored, but could not keep the version it replaced in data/design-previous.php' ] } );
noted.answer( 200, listing( { current : false } ) );

check( 'a restore that went through with a note says restored, and the note beside it - in the line of the bar with the class of a note - and the chip is the state the restored files are in: saved, not applied',
	( noted.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/restored']+ ' restored, but could not keep the version it replaced in data/design-previous.php'
	&& noted.line().className === 'nino-admin-hint' && noted.chip().textContent === shortWord('drifted') && noted.chip().dataset.state === 'drifted' );

const failing = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [] } } );
failing.byId('design-restore').fire('click');
failing.settle();
failing.answer( 500, { error : 'could not write /assets/theme.css' } );

check( 'a restore that failed says why and does not redraw: in the line of the bar, which carries the class of a failure and the whole of it as its title',
	( failing.byId('design-msg') || {} ).textContent === '(500) could not write /assets/theme.css' && failing.line().className === 'nino-admin-error'
	&& failing.line().title === '(500) could not write /assets/theme.css' && failing.requests.length === 0 );

/*	The weight of the questions that were the browser's confirm(): the way back to the previous version cannot be taken back as the workbench counts - destructive -,
	and is asked in both modes	*/
const MODES = [ 'production', 'development' ];

MODES.forEach( function( mode ) {
	const weighed = panel( { previous : { at : '2026-09-02T08:30:00+00:00', files : [ '/assets/theme.css' ] } } );
	weighed.setMode( mode );
	weighed.byId('design-restore').fire('click');
	weighed.settle();
	check( 'the way back to the previous version asks in '+ mode+ ', before anything is sent: a destructive question, with the label of the button for the answer that goes on and Cancel for the other, the focus coming back to the button',
		weighed.questions().length === 1 && weighed.questions()[0] === TEXT['/_admin/design/confirm/restore'] && JSON.stringify( endpoints( weighed ) ) === JSON.stringify( [ 'restore' ] )
		&& weighed.levels.length === 1 && weighed.levels[0].level === 'destructive' && weighed.levels[0].asked === true && weighed.levels[0].options.yes === TEXT['/_admin/design/label/restore']
		&& weighed.levels[0].options.no === TEXT['/_admin/common/label/cancel'] && weighed.levels[0].options.opener === weighed.byId('design-restore') );
} );

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
function shellRegistry() {

	const made = { entries : {}, refreshed : 0 };

	made.register = function( name, registered ) { made.entries[name] = registered };
	made.refresh = function() { made.refreshed++ };
	made.isDirty = function( names ) { return names.some( function( name ) { return made.entries[name] !== undefined && made.entries[name].isDirty() === true } ) };

	return made;
}

const registry = shellRegistry();

const tracked = panel( {}, { dirty : registry } );
const registered = registry.entries['design'] || null;

check( 'the panel registers under the uri of its tab - design - with the three the shell asks for, and under no other: the setup is a mode of that screen and not a tab of its own, and the loader has no bar and keeps nothing of the draft',
	registered !== null && typeof registered.isDirty === 'function' && typeof registered.save === 'function' && typeof registered.discard === 'function' && Object.keys( registry.entries ).join() === 'design' );
check( '...a screen drawn from what is stored holds nothing unsaved, and the shell is told when it is drawn', registered.isDirty() === false && registry.refreshed > 0 );

tracked.tab( tabWord('type') );
check( '...opening a tab changes nothing that is unsaved', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );

pressKey( tracked, 'knobs/section/title', 1 );
check( '...a knob that was moved does - the same answer the way back is shown for', registered.isDirty() === true && tracked.byId('design-reset').hidden === false );

tracked.byId('design-reset').fire('click');
check( '...and the way back, which draws the stored selection again, makes it not', registered.isDirty() === false && tracked.byId('design-reset').hidden === true );
check( '...and says so in the line of the bar, on the screen that was drawn again, with the chip beside it as the files stand',
	tracked.line().textContent === TEXT['/_admin/design/msg/reverted'] && tracked.line().title === TEXT['/_admin/design/msg/reverted'] && tracked.line().className === ''
	&& tracked.line().parent === tracked.bar() && tracked.chip().textContent === shortWord('current') && tracked.chip().parent === tracked.bar() );

nudge( tracked );
registered.discard();
check( 'discarding takes the stored selection for the screen\'s own', registered.isDirty() === false );

nudge( tracked );
let savedFor = [];
registered.save( function( ok ) { savedFor.push( ok ) } );
check( 'the shell\'s Save stores the draft - and asks nothing, since applying is a question of its own',
	JSON.stringify( endpoints( tracked ) ) === JSON.stringify( [ 'save' ] ) && tracked.questions().length === 0 && savedFor.length === 0 );

registered.save( function( ok ) { savedFor.push( ok ) } );
check( '...a second Save while the first is on its way says it did not save', savedFor.length === 1 && savedFor[0] === false );

tracked.answer( 200, {} );
check( '...and the first says it did, once the draft is stored', savedFor.length === 2 && savedFor[1] === true );
check( '...and that is all the shell\'s Save sends: the screen is read again from the store - no plan, no apply', JSON.stringify( endpoints( tracked ) ) === JSON.stringify( [ 'list' ] ) );

const refused = panel( {}, { dirty : registry } );
nudge( refused );
savedFor = [];
registry.entries['design'].save( function( ok ) { savedFor.push( ok ) } );
refused.answer( 500, { error : 'could not write data/design.php' } );
check( 'a draft the server turns down says so, in the line of the bar and to the shell, as a failure with the whole of it as its title, and the way back stays',
	savedFor.length === 1 && savedFor[0] === false && ( refused.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php'
	&& refused.line().className === 'nino-admin-error' && refused.line().title === '(500) could not write data/design.php'
	&& refused.byId('design-reset').hidden === false );

// The shell answers a refused Save by showing the tab of the entry that refused, by its uri
refused.design.showCurrent();
check( '...and the shell showing the tab again does not wipe the reason, nor the selection nobody has stored',
	( refused.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php' && registry.entries['design'].isDirty() === true );

registry.entries['design'].discard();
check( '...while a discard draws the stored selection again, so a leave that does not happen shows the truth',
	registry.entries['design'].isDirty() === false && refused.byId('design-reset').hidden === true );

// --- The weight of an apply, and Ctrl+S, by the mode of the workbench -----------------------------------------

/*	The press on Apply, up to the answer of design/plan: the screen at the point where the panel asks, or does not. A kernel that weighs its questions asks by the mode
	(the question is of the level big); one that does not asks every time, and the mode is no business of it	*/
function planned( mode, maintenance ) {

	const screen = panel( {} );

	screen.setMode( mode, maintenance );
	screen.byId('design-apply').fire('click');
	screen.answer( 200, {} );
	screen.answer( 200, { files : delivered.map( function( file ) { return Object.assign( {}, file ) } ) } );

	return screen;
}

/** What stands in the dialog of a question, in order: the title, the text, what the shell adds, the paths, the buttons - by what they are */
function dialogOrder( ask ) {
	return ask.buttons.parent.children.map( function( el ) { return el.id || el.tagName.toLowerCase() } ).join();
}

const liveApply = planned( 'production', false );
const liveAsk = liveApply.asks[0];

check( 'an apply on a live site is asked about, in the workbench\'s dialog, with the words of the apply and the two answers of a question - and the panel owns no sentence about the site being live'
	+ ': it is a question of the level big',
	liveApply.asks.length === 1 && liveAsk.options.title === TEXT['/_admin/design/label/apply'] && liveAsk.options.choices[0].label === TEXT['/_admin/design/confirm/yes'] && liveAsk.options.choices[1].label === TEXT['/_admin/common/label/cancel']
	&& liveApply.levels.length === 1 && liveApply.levels[0].level === 'big' && liveApply.levels[0].asked === true );

check( 'what the shell adds to the question stands as a paragraph of its own between the text and the paths, in that order: the question, the note, the files, the buttons - the paths do not come between the question and what the shell says about it',
	dialogOrder( liveAsk ) === 'h2,nino-admin-choice-text,nino-admin-choice-note,details,div'
		&& liveApply.byId('nino-admin-choice-note').textContent === LIVE_NOTE && detailsOf( liveAsk ).tagName === 'DETAILS' );

const kids = liveAsk.buttons.parent.children;
check( '...and the note is the one the shell gave, the details follow it directly', kids.indexOf( detailsOf( liveAsk ) ) === kids.indexOf( liveApply.byId('nino-admin-choice-note') ) + 1 );

const quiet = planned( 'production', true );
check( 'a live site in maintenance is asked about as well, with nothing added: no paragraph for the shell, the paths right after the text', quiet.asks.length === 1 && quiet.byId('nino-admin-choice-note') === null
	&& dialogOrder( quiet.asks[0] ) === 'h2,nino-admin-choice-text,details,div' );

const building = planned( 'development', false );
check( 'a site that is being built is not asked: design/plan is followed by the apply at once, with no dialog drawn, and the question was the shell\'s to answer',
	building.asks.length === 0 && JSON.stringify( endpoints( building ) ) === JSON.stringify( [ 'apply' ] ) && building.levels.length === 1 && building.levels[0].level === 'big' && building.levels[0].asked === false );
check( '...with the take-over of files that are not Design\'s all the same - the plan said so, and the line of the bar says it afterwards where the dialog did not', building.requests[0].payload.force === true );

building.answer( 200, {} );
building.answer( 200, listing( { exists : true } ) );
check( '...and the line says that it applied, that files were replaced and what to put back by hand', ( building.byId('design-msg') || {} ).textContent === [ TEXT['/_admin/design/msg/applied'], TEXT['/_admin/design/msg/takenover'], say( '/_admin/design/msg/lost', '[consent-settings]' ) ].join(' ') );

// Ctrl+S: the shell calls what the entry of the panel says for the mode it is in, whether anything is unsaved or not
const typed = panel( {}, { dirty : registry } );
const typedEntry = registry.entries['design'];

check( 'the entry has a shortcut with a function for each of the two modes of the workbench, and for no other - the shell names them development and production',
	typeof typedEntry.shortcut === 'object' && typedEntry.shortcut !== null && Object.keys( typedEntry.shortcut ).sort().join() === 'development,production'
	&& typeof typedEntry.shortcut.development === 'function' && typeof typedEntry.shortcut.production === 'function' );

( function() {
	const told = [];
	typedEntry.shortcut.production( function( ok ) { told.push( ok ) } );
	check( 'in Production Ctrl+S stores the draft and goes no further: one save, with the whole selection, and nothing asked', JSON.stringify( endpoints( typed ) ) === JSON.stringify( [ 'save' ] ) && told.length === 0 && typed.asks.length === 0 && typed.confirms.length === 0 );
	typed.answer( 200, {} );
	check( '...the shell is told that it was stored, once; the screen is drawn again from the store, and neither a plan nor an apply is asked for', JSON.stringify( told ) === JSON.stringify( [ true ] ) && JSON.stringify( endpoints( typed ) ) === JSON.stringify( [ 'list' ] ) );
	typed.answer( 200, listing( {} ) );
	check( '...and the line of the bar says the draft is saved', ( typed.byId('design-msg') || {} ).textContent === TEXT['/_admin/design/msg/saved'] && endpoints( typed ).length === 0 && told.length === 1 );
} )();

( function() {
	const screen = panel( {}, { dirty : registry } );
	const entry = registry.entries['design'];
	const told = [];
	screen.setMode( 'development' );
	entry.shortcut.development( function( ok ) { told.push( ok ) } );
	check( 'in Development Ctrl+S applies what is on screen: it begins with the save of the draft, and the run of the shell is over when the draft is stored', JSON.stringify( endpoints( screen ) ) === JSON.stringify( [ 'save' ] ) && told.length === 0 );
	entry.shortcut.development( function( ok ) { told.push( ok ) } );
	check( '...the button of the apply is off while the run is on its way, so that a second one says it did not save and sends nothing more', screen.byId('design-apply').disabled === true && JSON.stringify( told ) === JSON.stringify( [ false ] ) && JSON.stringify( endpoints( screen ) ) === JSON.stringify( [ 'save' ] ) );
	told.length = 0;
	screen.answer( 200, {} );
	check( '...then it asks design/plan, as the button does', JSON.stringify( told ) === JSON.stringify( [ true ] ) && JSON.stringify( endpoints( screen ) ) === JSON.stringify( [ 'plan' ] ) );
	screen.answer( 200, { files : delivered.map( function( file ) { return Object.assign( {}, file ) } ) } );

	check( '...and applies without a question, on a site that is being built', screen.asks.length === 0 && JSON.stringify( endpoints( screen ) ) === JSON.stringify( [ 'apply' ] ) && told.length === 1 );
} )();

( function() {
	// The bar is not there: a screen nothing of which has been read, which has no button to press
	const cold = panel( {}, { dirty : registry, cold : true } );
	const entry = registry.entries['design'];
	const told = [];
	[ 'development', 'production' ].forEach( function( mode ) { entry.shortcut[mode]( function( ok ) { told.push( ok ) } ) } );
	check( 'with no bar on screen both functions say they did not save, once each, send nothing and say nothing', JSON.stringify( told ) === JSON.stringify( [ false, false ] ) && cold.requests.length === 0 && cold.byId('design-msg') === null );
} )();

( function() {
	const screen = panel( {}, { dirty : registry } );
	const entry = registry.entries['design'];
	const told = [];
	entry.shortcut.production( function( ok ) { told.push( ok ) } );
	entry.shortcut.production( function( ok ) { told.push( ok ) } );
	check( 'a second Ctrl+S while the first is on its way says it did not save and sends nothing more', JSON.stringify( told ) === JSON.stringify( [ false ] ) && JSON.stringify( endpoints( screen ) ) === JSON.stringify( [ 'save' ] ) );
	screen.answer( 500, { error : 'could not write data/design.php' } );
	check( '...and a draft that is turned down is a Ctrl+S that did not save, with the reason in the line of the bar', JSON.stringify( told ) === JSON.stringify( [ false, false ] ) && ( screen.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php' );
} )();

// One draft, one question, in both modes of the screen: the list of the themes and the views answer to the one entry
const unsavedMoved = panel( {}, { dirty : registry } );
nudge( unsavedMoved );
const unsavedEntry = registry.entries['design'];
const unsavedRefreshed = registry.refreshed;

check( 'one draft, one question: the selection nobody has stored is unsaved while the views are drawn, and the shell asks the one entry and saves once',
	unsavedEntry.isDirty() === true && registry.isDirty( [ 'design' ] ) === true && unsavedMoved.byId('design-reset').hidden === false );

unsavedMoved.byId('design-back').fire('click');

check( '...the way back to the list keeps the question where it was - the draft is the same, drawn in the other mode, with the way back shown there - and the loader, which draws neither, leaves it as well',
	unsavedEntry.isDirty() === true && unsavedMoved.byId('design-reset').hidden === false && unsavedMoved.byId('design-save') !== null && unsavedMoved.design._isDirty() === true
	&& registry.refreshed > unsavedRefreshed && tabOf( unsavedMoved ) === 'theme'
	&& ( unsavedMoved.open('design-typefaces'), unsavedEntry.isDirty() === true && unsavedMoved.design._isDirty() === true && unsavedMoved.byId('design-reset').hidden === false && tabOf( unsavedMoved ) === 'typefaces' ) );

// A Save that fails on the list is shown by the uri of the tab: the reason stays where it was said
const refusedThemes = panel( {}, { dirty : registry, screen : 'themes' } );
nudge( refusedThemes );
refusedThemes.back();
refusedThemes.open('design');
savedFor = [];
registry.entries['design'].save( function( ok ) { savedFor.push( ok ) } );
refusedThemes.answer( 500, { error : 'could not write data/design.php' } );
refusedThemes.design.showCurrent();

check( 'a draft that is turned down while the list is drawn is brought on screen by the uri of the tab, and its reason stays: the shell showing the screen again draws nothing anew while the selection is unsaved',
	savedFor.length === 1 && savedFor[0] === false && ( refusedThemes.byId('design-msg') || {} ).textContent === '(500) could not write data/design.php' && registry.entries['design'].isDirty() === true
	&& refusedThemes.opened === 'design' && refusedThemes.design._screen === 'themes' );


// --- The themes ------------------------------------------------------------------------------
//
// The whole setup under a name. The panel's own screen, which the strip calls Themes, is in its first mode the
// list of the themes - a row for each, with its chips and its description and the buttons Load, Duplicate, Export,
// Delete and Edit, a pencil on a theme of the project that turns its name and its description into two fields, and
// under the list the field that takes a file in. Edit opens the second mode, the setup in six views, of the theme of
// the row, and Back to list leaves it with the draft kept; Save theme in the action bar puts the screen into the
// loaded theme of the project. The css of the theme is the view CSS of the setup, which every input mirrors into the
// preview. What a button does is read off the requests it posts and the answers it is given: the working copy a load
// makes of a theme, the typefaces the theme names that the project has not installed - asked for before anything is
// drawn -, the browser's download and file reader the export and the import go through, and the sentence a refusal is
// told in. The answers the server really gave are the last half of this file

console.log( 'The themes' );

/** The keys a theme keeps, out of an answer of design/list: the setup without the theme it names and the css it carries */
function snapshotOf( data ) {
	const copy = JSON.parse( JSON.stringify( data ) );
	const parts = {};
	copy.parts.forEach( function( row ) { parts[row.part] = row.set } );
	return { size : copy.size, width : copy.width, whitespace : copy.whitespace, radius : copy.radius, 'radius-alt' : copy['radius-alt'], radii : copy.radii, motion : copy.motion, parts : parts, navs : copy.navs, knobs : copy.knobs, styles : copy.styles, features : chosenOf( copy.features ), fonts : copy.fonts, colours : copy.colours };
}

/** What the setup chose for the features, out of the features of an answer of design/list: key => { set, knobs } for each that has a variant chosen - and none, an empty list on the wire, where nothing is chosen */
function chosenOf( features ) {

	const given = Array.isArray( features ) === true ? {} : ( features || {} );
	const entries = {};

	Object.keys( given ).forEach( function( key ) {
		if( given[key].set !== '' )
			entries[key] = { set : given[key].set, knobs : given[key].knobs };
	} );

	return Object.keys( entries ).length === 0 ? [] : entries;
}

/** A theme as design/list gives the active one: its row of the list and what its file holds - the setup of the stand-in's own listing unless it is given */
function activeOf( slug, name, description, shipped, css, setup ) {
	return { slug : slug, name : name, description : description, shipped : shipped, setup : setup || snapshotOf( listing() ), css : css };
}

/** The list of the themes with one more at the end: the one a duplicate or an import made */
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

/** Every button of the list of the themes, and the field of the file: what is switched off while a request of the list is on its way */
function themeControls( screen ) {
	return screen.byId( LIST_CARD ) === null ? [] : byTag( screen.byId( LIST_CARD ), 'button' ).concat( [ screen.byId('design-theme-import') ].filter( function( field ) { return field !== null } ) );
}

/** The setup of a theme the way a file holds it: the stand-in's own, with the templates, the knob, the size, the radius and the fonts that are given in place */
function themeSetup( fonts ) {
	const setup = snapshotOf( listing() );
	setup.parts.header = 'second';
	setup.parts.card = 'third';
	setup.knobs.card.space = 1;
	setup.size = -1;
	setup.radius = 1;
	setup['radius-alt'] = 2;
	setup.radii.card = 'alternative';
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

// What the server says in the line of the bar of a typeface that was installed for a theme
const INTER_INSTALLED = say( '/_admin/design/typefaces/msg/installed', 'Inter', '4.0' );
const LORA_INSTALLED = say( '/_admin/design/typefaces/msg/installed', 'Lora', '1.0' );

/** A page answer for a preview that worked and has nothing to say */
function previewed( css, document ) {
	return { css : css, document : document, style : 'design-preview-css', accent : '#8ad0f5', brand : listing().brand, notes : [] };
}

const ownCss = ".typed { margin: 0 }\n";
const own = activeOf( 'mein-theme', 'My theme', 'The theme of this project', false, ownCss );

/**
 *	The panel with the project's own theme loaded and run, on the list of the themes - or, with `mode` 'setup', on the views of the setup, as Edit leaves them. `shell` is what
 *	panel() takes beside it
 */
function themed( over, mode, shell ) {
	return panel( Object.assign( { theme : 'mein-theme', running : 'mein-theme', css : ownCss, active : own }, over || {} ), Object.assign( {}, shell || {}, mode === 'setup' ? {} : { screen : 'themes' } ) );
}

/** The marks of a theme in the tab button of the strip: the chip "changed" the script appends to the button of the panel's own screen */
function tabChips( screen ) { return byClass( screen.kernelButton('design'), 'design-tab-changed' ) }

// The tiers are part of what a theme is: the working copy is compared with the theme's own, and a position makes the chip
const trTheme = themed( {}, 'setup' );
trTheme.design._edit.tiers = trTheme.design._tiersOf( {} );

check( 'the tiers are in the snapshot a theme is compared by where something is set - and in none where nothing is: the chip says changed after a position and not before, and a position put back is no change',
	trTheme.design._themeChanged() === false && ( trTheme.design._edit.tiers.s.size = -1, trTheme.design._themeChanged() === true ) && ( delete trTheme.design._edit.tiers.s.size, trTheme.design._themeChanged() === false )
	&& Object.keys( trTheme.design._snapshotOf( trTheme.design._edit ) ).indexOf( 'tiers' ) === -1 && ( trTheme.design._edit.tiers.m.gap = 1, Object.keys( trTheme.design._snapshotOf( trTheme.design._edit ) ).indexOf( 'tiers' ) !== -1 ) );

// The list of the themes ------------------------------------------------------------------------

const drawn = themed();
const cards = drawn.body().children;
const slugs = byClass( drawn.form, 'design-theme-row' ).map( function( row ) { return row.dataset.slug } );

check( 'the list is one card of the workbench with a title of its own - the list of the themes, with the field that takes a file in - and nothing else in the column: no strip over it, and no bar of the views in the pane',
	cards.map( function( card ) { return card.id } ).join() === LIST_CARD && hasClass( cards[0], 'nino-admin-card' ) && hasClass( cards[0], 'design-block' )
	&& byTag( cards[0], 'strong' )[0].textContent === TEXT['/_admin/design/theme/list/title'] && drawn.body().children.length === 1 && byClass( drawn.pane, 'design-tabs' ).length === 0 );
check( '...the list is a row for each theme in the order design/list gives them - the six the feature ships, then the one of the project - with its name, and under the name its description',
	slugs.join() === 'nino,magazine,poster,statement,product,office,mein-theme' && drawn.byId('design-theme-list').tagName === 'UL'
	&& slugs.every( function( slug, at ) {
		const row = themeRow( drawn, slug );
		return row.tagName === 'LI' && textsOf( row, 'design-theme-name' ) === THEMES[at].name && textsOf( row, 'design-theme-description' ) === THEMES[at].description && byTag( row, 'small' ).length === 1;
	} ) );
check( '...a chip "shipped" on each of the six the feature ships and on no other, a chip "active" on the theme the working copy names - and the mark of it on its row, to the eye and to whoever hears the list - both the workbench\'s own chip, in the colour of what it says',
	THEMES.every( function( theme ) { return byClass( themeRow( drawn, theme.slug ), 'design-theme-shipped' ).length === ( theme.shipped === true ? 1 : 0 ) } )
	&& byClass( drawn.form, 'design-theme-shipped' ).every( function( chip ) { return chip.textContent === TEXT['/_admin/design/theme/shipped'] && hasClass( chip, 'nino-admin-badge' ) && chip.dataset.state === 'muted' } )
	&& byClass( drawn.form, 'design-theme-active' ).length === 1 && textsOf( themeRow( drawn, 'mein-theme' ), 'design-theme-active' ) === TEXT['/_admin/design/theme/active']
	&& byClass( drawn.form, 'design-theme-active' )[0].dataset.state === 'ok' && hasClass( byClass( drawn.form, 'design-theme-active' )[0], 'nino-admin-badge' )
	&& byClass( drawn.form, 'design-theme-row--active' ).length === 1 && hasClass( themeRow( drawn, 'mein-theme' ), 'design-theme-row--active' )
	&& themeRow( drawn, 'mein-theme' ).getAttribute('aria-current') === 'true' && themeRow( drawn, 'nino' ).getAttribute('aria-current') === null );

const ROW_BUTTONS = [ 'load', 'duplicate', 'export', 'delete', 'edit' ];

check( '...each row has the buttons Load, Duplicate, Export and Edit in that order, and a theme of the project has Delete as well, before Edit - a shipped theme is a file of the feature and has none -, each button named by the theme it is about for whoever hears them read out',
	THEMES.every( function( theme ) {
		const wanted = ROW_BUTTONS.filter( function( word ) { return word !== 'delete' || theme.shipped === false } );
		const buttons = themeButtons( drawn, theme.slug );
		return buttons.map( function( button ) { return button.id } ).join() === wanted.map( function( word ) { return 'design-theme-'+ word+ '-'+ theme.slug } ).join()
			&& buttons.map( function( button ) { return button.textContent } ).join() === wanted.map( function( key ) { return TEXT['/_admin/design/theme/'+ key] } ).join()
			&& buttons.every( function( button ) { return button.type === 'button' && button.getAttribute('aria-label') === button.textContent+ ' - '+ theme.name && button.title === button.getAttribute('aria-label') } );
	} )
	&& hasClass( drawn.byId('design-theme-delete-mein-theme'), 'nino-admin-btn-danger' ) && drawn.byId('design-theme-delete-nino') === null && drawn.byId('design-theme-edit-nino') !== null );

const pencil = drawn.byId('design-theme-rename-mein-theme');

check( '...a pencil after the chips of a theme of the project, and on no shipped theme: an icon alone - the pencil of the icons - named for whoever hears it by the workbench\'s word for renaming and the theme',
	pencil !== null && pencil.type === 'button' && pencil.parent === byClass( themeRow( drawn, 'mein-theme' ), 'design-theme-head' )[0] && pencil.parent.children[pencil.parent.children.length - 1] === pencil
	&& pencil.parent.children.indexOf( pencil ) > pencil.parent.children.indexOf( byClass( pencil.parent, 'design-theme-active' )[0] ) && pencil.textContent === ''
	&& pencil.getAttribute('aria-label') === TEXT['/_admin/common/label/rename']+ ' - My theme' && pencil.title === pencil.getAttribute('aria-label')
	&& byTag( pencil, 'svg' ).length === 1 && byTag( pencil, 'svg' )[0].dataset.icon === 'pencil' && byTag( pencil, 'svg' )[0].getAttribute('aria-hidden') === 'true'
	&& THEMES.filter( function( theme ) { return theme.shipped === true } ).every( function( theme ) { return drawn.byId('design-theme-rename-'+ theme.slug ) === null } )
	&& byClass( drawn.form, 'design-theme-pencil' ).length === 1 );

const importField = drawn.byId('design-theme-import');

check( '...and under the list the field that takes a file in: a labelled file input that asks for json',
	importField.tagName === 'INPUT' && importField.type === 'file' && importField.accept === '.json,application/json' && importField.parent.tagName === 'LABEL' && hasClass( importField.parent, 'nino-admin-field' )
	&& importField.parent.children[0].textContent === TEXT['/_admin/design/theme/import/label'] && importField.parent.parent === drawn.byId('design-themes') );

// Edit ---------------------------------------------------------------------------------------------

const editing = themed();
editing.design._edit.size = 1;
editing.setup._tab = 'footer';
editing.design._target = 'cards';
const editingEdit = JSON.stringify( editing.design._edit );
editing.byId('design-theme-edit-mein-theme').focus();
focused = null;
editing.byId('design-theme-edit-mein-theme').fire('click');

check( 'Edit on the loaded theme opens the views at once, with no request - the place the last change was looked at is let go of, so that the frame goes to the object\'s own: the object that was open last, under the bar that begins with Back to list, and the draft is as it was - what was changed and not stored stays, and the way back is shown',
	editing.requests.length === 0 && editing.design._screen === 'setup' && editing.setup._current() === 'footer' && editing.byId('design-back') !== null && byClass( editing.pane, 'design-tabs' ).length === 1
	&& editing.byId( LIST_CARD ) === null && JSON.stringify( editing.design._edit ) === editingEdit && editing.design._isDirty() === true && editing.byId('design-reset').hidden === false
	&& editing.design._themeBusy === false && editing.opened === 'design' && editing.design._target === null );

check( '...the focus the press on Edit had is not lost with the list: it is on the tab of the object that is open - the one tab of the strip that is a tab stop - and that tab is brought into sight in the bar, which scrolls inside itself on a narrow pane',
	focused === editing.byId('design-tab-footer') && editing.byId('design-tab-footer').getAttribute('aria-selected') === 'true'
	&& JSON.stringify( editing.byId('design-tab-footer').scrolledInto ) === JSON.stringify( { block : 'nearest', inline : 'nearest' } ) && editing.byId('design-tab-page').scrolledInto === undefined );

const lastView = themed();
lastView.enter();
lastView.go('card');
lastView.back();
lastView.byId('design-theme-edit-mein-theme').fire('click');

check( '...on the object that was open when the setup was left - the page at the start of a visit - with the tab lit',
	lastView.setup._current() === 'card' && lastView.byId('design-tab-card').getAttribute('aria-selected') === 'true' && themed().setup._current() === 'page'
	&& ( function() { const first = themed(); first.byId('design-theme-edit-mein-theme').fire('click'); return first.setup._current() === 'page' && first.byId('design-tab-page').getAttribute('aria-selected') === 'true' } )() );

const loadingEdit = themed();
const editFits = loadingEdit.fitted.length;
loadingEdit.byId('design-theme-edit-magazine').fire('click');

check( 'Edit on another theme loads it first, as Load does: design/theme/load with the slug and nothing else, the list still on screen and its buttons off until the answer is in',
	JSON.stringify( loadingEdit.requests.map( function( request ) { return [ request.endpoint, request.payload ] } ) ) === JSON.stringify( [ [ 'theme/load', { slug : 'magazine' } ] ] )
	&& loadingEdit.design._screen === 'themes' && tabOf( loadingEdit ) === 'theme' && themeControls( loadingEdit ).every( function( control ) { return control.disabled === true } ) && loadingEdit.design._themeBusy === true );

focused = null;
loadingEdit.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( '...and the views open when it is taken over, in the one drawing of the screen: the theme is the working copy, the bar of the views is there with Back to list first, the list is gone, the bar says it was loaded',
	loadingEdit.design._screen === 'setup' && loadingEdit.design._edit.theme === 'magazine' && loadingEdit.design._active.slug === 'magazine' && loadingEdit.setup._current() === 'page' && loadingEdit.byId('design-back') !== null
	&& loadingEdit.byId( LIST_CARD ) === null && loadingEdit.fitted.length === editFits + 1 && loadingEdit.requests.length === 0 && loadingEdit.design._themeBusy === false
	&& loadingEdit.line().textContent === say( '/_admin/design/theme/msg/loaded', 'Magazine' ) );

check( '...and the focus is on the object that is open then, as it is where the loaded theme was edited at once',
	focused === loadingEdit.byId('design-tab-page') && loadingEdit.byId('design-tab-page').scrolledInto !== undefined );

const editingFonts = themed();
editingFonts.byId('design-theme-edit-magazine').fire('click');
editingFonts.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter' } ), [ INTER ] ) );
editingFonts.settle();
editingFonts.answer( 200, { msg : INTER_INSTALLED } );
editingFonts.answer( 200, listing( { theme : 'mein-theme', css : ownCss, active : own, options : optionsWith( [ 'inter' ] ) } ) );

check( '...the typefaces the theme names are asked for and installed first, the list read again, and then the views open on the theme',
	editingFonts.questions().length === 1 && editingFonts.design._screen === 'setup' && editingFonts.design._edit.theme === 'magazine' && editingFonts.design._edit.fonts.title.family === 'inter' && editingFonts.byId('design-back') !== null );

const refusedEdit = themed();
refusedEdit.byId('design-theme-edit-magazine').fire('click');
refusedEdit.answer( 404, { error : 'there is no such theme', code : 'design_theme' } );

check( '...a theme the server refuses to load stays on the list: the sentence of its code in red, the buttons on again, the theme and the mode as they were',
	refusedEdit.design._screen === 'themes' && tabOf( refusedEdit ) === 'theme' && refusedEdit.design._edit.theme === 'mein-theme' && refusedEdit.line().textContent === TEXT['/_admin/error/design_theme']
	&& refusedEdit.line().className === 'nino-admin-error' && themeControls( refusedEdit ).every( function( control ) { return control.disabled === false } ) );

const busyEdit = themed();
busyEdit.byId('design-theme-load-magazine').fire('click');
busyEdit.design._editTheme('mein-theme');

check( '...and a press on Edit while another action is on its way opens nothing and posts nothing',
	busyEdit.requests.length === 1 && busyEdit.design._screen === 'themes' );

const editShipped = themed( {}, 'list' );
editShipped.byId('design-theme-edit-nino').fire('click');
editShipped.answer( 200, themeLoad( 'nino', snapshotOf( listing() ), [] ) );

check( '...a shipped theme is edited like any other: loaded, then the views - it is the bar of the action that has no Save theme for it (see below)',
	editShipped.design._screen === 'setup' && editShipped.design._edit.theme === 'nino' && editShipped.design._active.shipped === true );

// Back to list --------------------------------------------------------------------------------------

const backing = themed( {}, 'setup' );
backing.show('type');
backing.move( sizeStrip, 1 );
backing.design._target = 'cards';
backing.byId('design-back').focus();
focused = null;
const backingEdit = JSON.stringify( backing.design._edit );
const backingRequests = backing.requests.length;
backing.byId('design-back').fire('click');

check( 'Back to list shows the list with the draft kept: the working copy is as it was, the way back is shown, and no theme action was posted',
	backing.design._screen === 'themes' && tabOf( backing ) === 'theme' && JSON.stringify( backing.design._edit ) === backingEdit && backing.design._isDirty() === true && backing.byId('design-reset').hidden === false
	&& backing.requests.length === backingRequests && byClass( backing.pane, 'design-tabs' ).length === 0 && backing.design._target === null );
check( '...the focus the press on Back had goes to the Edit button of the theme that is loaded - the first row\'s where none is - and not to the page',
	focused === backing.byId('design-theme-edit-mein-theme') && ( function() { const none = panel(); focused = null; none.byId('design-back').fire('click'); return focused === none.byId('design-theme-edit-nino') } )() );
check( '...and the chip on the button of Themes still says so, now that the list is drawn - the list marks the theme the screen was taken from',
	backing.design._themeChanged() === true && tabChips( backing ).length === 1 && hasClass( themeRow( backing, 'mein-theme' ), 'design-theme-row--active' ) );

// A theme that is loaded while the setup is open leaves the object and the aspect where they were
const objKept = themed( {}, 'setup', { lens : false } );
objKept.go('card');
toggle( objKept.byId('design-row-surfaces-card') );
objKept.design._loadTheme('magazine');
objKept.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( 'a theme that is loaded keeps the object and the aspect that were open: the card, its Surface block open, the tab lit - and the controls drawn from the theme that was taken over',
	objKept.design._edit.theme === 'magazine' && objKept.design._screen === 'setup' && objKept.setup._current() === 'card' && objKept.setup._open.card === 'surfaces'
	&& blocksOf( objKept.form ).filter( isOpen ).map( function( card ) { return card.dataset.tab } ).join() === 'surfaces' && objKept.byId('design-tab-card').getAttribute('aria-selected') === 'true'
	&& shown( objKept.byId('design-object-select') ) === 'card' && objKept.design._edit.knobs.card.space === 1
	&& pressedKey( objKept, 'knobs/card/space' ) === 'false,false,false,true,false' );

// Where the window is narrow the focus a press on Edit had goes to the select, which is the control there; wide, to the tab
const nb = themed();
nb.setup._tab = 'card';
nb.byId('design-theme-edit-mein-theme').focus();
focused = null;
nb.view.narrow = true;
nb.byId('design-theme-edit-mein-theme').fire('click');
const nbNarrow = focused;
nb.back();
nb.view.narrow = false;
focused = null;
nb.byId('design-theme-edit-mein-theme').fire('click');

check( 'after Edit the focus is on the control that is shown: the select where the window is narrower than the rail of the workbench, the tab of the open object where it is not',
	nbNarrow !== null && nbNarrow.id === 'design-object-select' && focused === nb.byId('design-tab-card') );

// The css, in the view CSS of the setup ------------------------------------------------------------------

drawn.show('css');

let box = drawn.byId('design-theme-css');

check( 'the css of the theme is a textarea in a labelled field of its own - the label is the title of the card, kept for whoever hears the field and drawn out of sight - sized for code and for a stylesheet of some length: thirty rows, no spell check, no capital, no wrapping of a line',
	box.tagName === 'TEXTAREA' && box.parent.tagName === 'LABEL' && hasClass( box.parent, 'nino-admin-field' ) && box.parent.children[0].textContent === TEXT['/_admin/design/theme/css/title'] && hasClass( box.parent.children[0], 'design-theme-css-name' )
	&& hasClass( box, 'design-theme-css' ) && box.rows === 30 && box.spellcheck === false && box.getAttribute('autocapitalize') === 'off' && box.getAttribute('wrap') === 'off'
	&& box.value === ownCss && box.parent.parent === drawn.byId( CSS_CARD ) && drawn.byId( CSS_CARD ).parent === lensBlock( drawn, 'page' ) );
check( '...with the hint under it: what the section does, what is refused, and the path of the project written as the shortcode it is, as it is',
	textsOf( drawn.byId( CSS_CARD ), 'nino-admin-hint' ) === TEXT['/_admin/design/theme/css/hint'] && TEXT['/_admin/design/theme/css/hint'].indexOf('[[/nino/public]]') !== -1 );

drawn.design._previewNow();
drawn.answer( 200, previewed( ':root{}', '<!doctype html>' ) );

box.value = ".typed { margin: 1px }\n";
box.fire('input');

check( 'an input in the field sets the css of the working copy and asks for a preview - after the debounce every control has - and the screen is no longer the stored one: the way back is shown',
	drawn.design._edit.css === ".typed { margin: 1px }\n" && drawn.waiting( 350 ).length === 1 && drawn.design._isDirty() === true && drawn.byId('design-reset').hidden === false );
check( '...nor the theme it was taken from, which the chip on the button of Themes says at once, in the views as it does on the list - in the colour of a thing to look at',
	drawn.design._themeChanged() === true && tabChips( drawn ).length === 1 && tabChips( drawn )[0].textContent === TEXT['/_admin/design/theme/current/changed'] && tabChips( drawn )[0].dataset.state === 'warn'
	&& hasClass( tabChips( drawn )[0], 'nino-admin-badge' ) && box.value === ".typed { margin: 1px }\n" );

drawn.back();
drawn.show('css');
box = drawn.byId('design-theme-css');
drawn.design._previewNow();

check( '...and the preview it asks for carries the css and the theme beside the rest of the selection - the whole page, the first one after the screen was drawn anew by the way back and the views opened again: the frame is built again',
	drawn.requests[drawn.requests.length - 1].endpoint === 'preview' && drawn.requests[drawn.requests.length - 1].payload.css === ".typed { margin: 1px }\n"
	&& drawn.requests[drawn.requests.length - 1].payload.theme === 'mein-theme' && drawn.requests[drawn.requests.length - 1].payload.full === true );

drawn.answer( 200, previewed( ':root{}.typed{margin:1px}', '<!doctype html><html>again</html>' ) );

const framed = drawn.byId('design-frame').srcdoc;

box.fire('input');
drawn.design._previewNow();

check( '...and the next one, after another input with the page standing in the frame, asks for the stylesheet alone',
	drawn.requests[drawn.requests.length - 1].endpoint === 'preview' && drawn.requests[drawn.requests.length - 1].payload.css === ".typed { margin: 1px }\n" && drawn.requests[drawn.requests.length - 1].payload.full === false );

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

check( '...and the css put back as the theme has it, the chip goes with the difference and the way back goes with it',
	drawn.design._themeChanged() === false && drawn.design._isDirty() === false && drawn.byId('design-reset').hidden === true && tabChips( drawn ).length === 0 );

drawn.design._edit.css = 'a{}';
check( '...a changed css is unsaved, and so is another theme with the same setup, and put back neither is',
	drawn.design._isDirty() === true && ( drawn.design._edit.css = ownCss, drawn.design._isDirty() ) === false
	&& ( drawn.design._edit.theme = 'magazine', drawn.design._isDirty() ) === true && ( drawn.design._edit.theme = 'mein-theme', drawn.design._isDirty() ) === false
	&& Object.keys( drawn.design._selection( drawn.design._data ) ).indexOf('theme') !== -1 && Object.keys( drawn.design._selection( drawn.design._data ) ).indexOf('css') !== -1 );

// The chip on the button of Themes -------------------------------------------------------------------
//
// "Changed" stands on the button of the panel's own screen in the shell's strip on top of the pane - the one that
// says Themes - while the screen differs from the theme, whichever mode and tab are open: one function draws and
// removes it (see _refreshThemeChip()), and a hand on Type or on Surfaces sees at the strip of the pane that what is
// on the screen is no longer the theme. The button is the shell's, which may draw its strip anew: the chip is put
// back whenever the shell shows one of the tabs

const flagged = themed();
const themeTab = function( screen ) { return screen.kernelButton('design') };

check( 'while the screen is the theme the button of Themes carries no chip, whichever tab is open - the list, the views of the setup and the loader',
	flagged.design._themeChanged() === false && tabChips( flagged ).length === 0 && TABS.every( function( key ) { flagged.show( key ); return tabChips( flagged ).length === 0 } )
	&& ( flagged.show( 'typefaces' ), tabChips( flagged ).length === 0 ) && ( flagged.show( 'theme' ), tabChips( flagged ).length === 0 ) );

// The loader, shown with the others, asked for the list the server kept and nobody answers it here: what is asked for from now on is what the checks below read
flagged.requests.splice( 0 );
flagged.show( 'type' );
flagged.move( sizeStrip, 1 );

check( 'a knob moved on a view draws it at once: one chip, the workbench\'s own, in the colour of a thing to look at, inside the button of Themes in the strip of the shell - and on no other button of the strip',
	flagged.design._themeChanged() === true && tabChips( flagged ).length === 1 && hasClass( tabChips( flagged )[0], 'nino-admin-badge' ) && tabChips( flagged )[0].dataset.state === 'warn'
	&& tabChips( flagged )[0].textContent === TEXT['/_admin/design/theme/current/changed'] && tabChips( flagged )[0].parent === themeTab( flagged ) && themeTab( flagged ).children[themeTab( flagged ).children.length - 1] === tabChips( flagged )[0]
	&& byClass( flagged.registry, 'design-tab-changed' ).length === 1 && byClass( flagged.head, 'design-tab-changed' ).length === 1 && byClass( byClass( flagged.pane, 'design-tabs' )[0], 'design-tab-changed' ).length === 0 );
check( '...it is no part of the name of the tab: the label stays the text of the button, the word the strip of the shell says - the chip is the one element in it - and the button is still the shell\'s own one for the panel\'s screen, with its id and its uri',
	themeTab( flagged ).textContent === TEXT['/_admin/nav/themes'] && themeTab( flagged ).children.length === 1 && themeTab( flagged ).dataset.tab === 'design' && themeTab( flagged ).id === 'admin-tabbutton-design' && tabOf( flagged ) === 'type'
	&& kernelLabels( flagged ) === [ '/_admin/nav/themes', '/_admin/nav/design-typefaces', '/_admin/features/tab/settings' ].map( function( key ) { return TEXT[key] || 'Settings' } ).join() );

flagged.back();

check( '...the same chip stands on the button while the list is drawn: the one chip, whichever mode the screen is in',
	tabOf( flagged ) === 'theme' && tabChips( flagged ).length === 1 && flagged.design._themeChanged() === true );

/*	The shell may draw its strip anew - a button without the chip it was given - and the chip is renewed whenever it shows one of the tabs: the panel's own screen, with the draft
	drawn anew or left as it stands where it is unsaved, and the loader, which draws neither preview nor bar. A strip drawn anew has no chip to take away, and one that has the chip
	already is given no second	*/
const renew = function( screen, show ) {
	tabChips( screen ).forEach( function( chip ) { chip.remove() } );
	const was = tabChips( screen ).length;
	show();
	return was === 0 && tabChips( screen ).length === 1 && themeTab( screen ).children.length === 1;
};

check( 'a strip of the shell that was drawn anew is renewed with the chip it should have when the shell shows a tab of the pane again: the panel\'s own screen - the draft is unsaved, so nothing is drawn anew over it, and the chip comes back all the same - and the loader',
	flagged.design._isDirty() === true && renew( flagged, function() { flagged.open('design') } ) && renew( flagged, function() { flagged.open('design-typefaces') } ) && renew( flagged, function() { flagged.open('design') } ) );
check( '...and a button that has the chip already is given no second one, however often the tabs are shown',
	( flagged.open('design'), flagged.open('design'), flagged.open('design-typefaces'), flagged.open('design'), tabChips( flagged ).length === 1 && themeTab( flagged ).children.length === 1 ) );

/*	With a selection nobody has stored the shell may show the screen that is drawn once more - after a Save that failed, or from its own strip - and the panel draws nothing anew over
	it (see _show()): the line of the bar and the box of the screen stay the ones that stood. The strip may have been drawn anew all the same, so the chip is renewed on that way too	*/
const heldRegistry = shellRegistry();
const held = themed( {}, 'list', { dirty : heldRegistry } );

held.show( 'type' );
held.move( sizeStrip, 1 );
held.open('design');

const heldLine = held.byId('design-msg');
const heldLayout = held.byId('design-layout');

check( '...and where the screen is drawn already and its selection is unsaved - the shell showing it again draws nothing anew - the chip is renewed all the same, the line of the bar and the layout being the ones that stood',
	heldRegistry.entries['design'].isDirty() === true && heldLine !== null && renew( held, function() { held.open('design') } ) && held.byId('design-msg') === heldLine && held.byId('design-layout') === heldLayout );

/*	The shell may show any tab of the pane first - a link to #design-typefaces or #design opens it - before anything of the panel has been read: the screen that is
	shown asks for the list (init()), draws what it has once the list is there, and draws nothing before. The loader has no preview and no bar and draws none - nowhere in the
	document, and not into the mount of the panel's own screen behind it, where a preview would be waited for (the timer of 0 ms) in a mount nobody sees -, and the chip is on the button of Themes as it is with the
	other tabs: the stored selection here is not the theme it names	*/
const coldOver = { theme : 'mein-theme', running : 'mein-theme', css : ownCss, active : own, size : 2 };
const endpointsOf = function( screen ) { return screen.requests.map( function( request ) { return request.endpoint } ).join() };

const coldFaces = panel( coldOver, { cold : true } );
coldFaces.open('design-typefaces');

const coldFacesAsked = endpointsOf( coldFaces );
const coldFacesEmpty = coldFaces.design._ready === false && coldFaces.faceBox().children.length === 0 && coldFaces.forms.design.children.length === 0 && coldFaces.byId('design-frame') === null;

coldFaces.answer( 200, listing( coldOver ) );

check( 'the loader as the first screen of the pane asks for the list of the panel and draws nothing until it is there, then its two cards - and neither preview nor bar anywhere in the document, nothing in the mount of the panel\'s own screen, no preview waited for - and the chip on the button of Themes',
	coldFacesAsked === 'list' && coldFacesEmpty === true && coldFaces.design._ready === true && tabOf( coldFaces ) === 'typefaces' && coldFaces.byId('design-faces-installed') !== null && coldFaces.byId('design-faces-available') !== null
	&& [ 'design-layout', 'design-frame', 'design-save', 'design-apply', 'design-reset', 'design-msg' ].every( function( id ) { return coldFaces.byId( id ) === null } )
	&& coldFaces.forms.design.children.length === 0 && endpointsOf( coldFaces ) === 'webfonts/list' && coldFaces.waiting( 0 ).length === 0
	&& tabChips( coldFaces ).length === 1 );

coldFaces.open('design');

check( '...and the list is drawn when the shell shows the panel\'s own screen, the first time: one preview and one bar in its mount, the mount of the loader emptied, the one chip still the one',
	tabOf( coldFaces ) === 'theme' && coldFaces.design._screen === 'themes' && coldFaces.byId('design-frame') !== null && coldFaces.byId('design-save') !== null && coldFaces.forms.design.children.length > 0
	&& coldFaces.faceBox().children.length === 0 && tabChips( coldFaces ).length === 1 && coldFaces.waiting( 0 ).length === 1 );

const coldThemes = panel( coldOver, { cold : true } );
coldThemes.open('design');

const coldThemesAsked = endpointsOf( coldThemes );

coldThemes.answer( 200, listing( coldOver ) );

check( 'the panel\'s own screen as the first screen asks for the list and draws the list of the themes when it is there, with one preview and one bar in its mount, and the mount of the loader empty',
	coldThemesAsked === 'list' && tabOf( coldThemes ) === 'theme' && coldThemes.byId('design-frame') !== null && coldThemes.byId('design-save') !== null && coldThemes.faceBox().children.length === 0 );

const coldFailed = panel( coldOver, { cold : true } );
coldFailed.open('design-typefaces');
coldFailed.answer( 500, null );

check( 'a list of the panel that fails is said where the screen that asked stands - in the mount of the loader - and the panel stays unread: not drawn into a mount nobody sees',
	byClass( coldFailed.faceBox(), 'nino-admin-error' ).length === 1 && coldFailed.design._ready === false && coldFailed.forms.design.children.length === 0 && coldFailed.byId('design-frame') === null );

// The loader asked for the list again each time it was shown, and nobody answers it here
flagged.requests.splice( 0 );

flagged.design._edit.size = 0;
flagged.design._preview();

check( '...a change put back is no change: the chip goes, and the way back with it',
	flagged.design._themeChanged() === false && tabChips( flagged ).length === 0 && flagged.design._isDirty() === false && flagged.byId('design-reset').hidden === true );

flagged.design._edit.size = 1;
flagged.design._preview();

check( '...and a change again draws it again, from the same call',
	tabChips( flagged ).length === 1 );

/*	An apply writes the website and keeps the screen as it is, and the screen is still not its theme: the chip compares with the file of the theme, as the head's always did,
	so it stays	*/
flagged.show( 'type' );
flagged.byId('design-apply').fire('click');
flagged.answer( 200, {} );
flagged.answer( 200, { files : delivered } );
flagged.asks[0].choose('yes');
flagged.answer( 200, {} );
flagged.answer( 200, listing( { theme : 'mein-theme', running : 'mein-theme', css : ownCss, active : own, size : 1, exists : true } ) );

check( 'an apply keeps the chip: it writes the website with the screen as it is, and the screen is still not the theme',
	flagged.design._edit.size === 1 && flagged.design._themeChanged() === true && tabChips( flagged ).length === 1 && flagged.byId('design-msg').textContent.indexOf( TEXT['/_admin/design/msg/applied'] ) === 0 );

flagged.byId('design-theme-save').fire('click');
flagged.answer( 200, { themes : THEMES.map( function( theme ) { return Object.assign( {}, theme ) } ),
	active : activeOf( 'mein-theme', 'My theme', 'The theme of this project', false, ownCss, JSON.parse( JSON.stringify( flagged.design._snapshotOf( flagged.design._edit ) ) ) ) } );

check( 'saving the theme makes the screen the theme again, and the chip goes with it',
	flagged.design._themeChanged() === false && tabChips( flagged ).length === 0 );

// The way back, and a theme that is loaded
const flaggedBack = themed();
flaggedBack.show( 'type' );
flaggedBack.move( sizeStrip, -1 );
const flaggedChips = tabChips( flaggedBack ).length;
flaggedBack.byId('design-reset').fire('click');

check( 'the way back takes the chip away with the change: it is drawn from the stored selection again, which is the theme',
	flaggedChips === 1 && tabChips( flaggedBack ).length === 0 && flaggedBack.design._themeChanged() === false );

const flaggedLoad = themed();
flaggedLoad.show( 'type' );
flaggedLoad.move( sizeStrip, 1 );
flaggedLoad.back();
flaggedLoad.byId('design-theme-load-magazine').fire('click');
flaggedLoad.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( 'a theme that is loaded is the screen: the chip goes with the difference, the screen being the file it was taken from',
	flaggedLoad.design._active.slug === 'magazine' && flaggedLoad.design._themeChanged() === false && tabChips( flaggedLoad ).length === 0 );

nudge( flaggedLoad );

check( '...and what is changed after the load shows it again',
	flaggedLoad.design._themeChanged() === true && tabChips( flaggedLoad ).length === 1 );

const flaggedDelete = themed();
flaggedDelete.show( 'type' );
flaggedDelete.move( sizeStrip, 1 );
const flaggedDeleteChips = tabChips( flaggedDelete ).length;
flaggedDelete.back();
flaggedDelete.byId('design-theme-delete-mein-theme').fire('click');
flaggedDelete.settle();
flaggedDelete.answer( 200, { themes : THEMES.filter( function( theme ) { return theme.shipped === true } ), theme : '' } );

check( 'the theme that is deleted while it is the active one leaves the screen with none to differ from, and the chip goes from the label although the screen that is open is the list',
	flaggedDeleteChips === 1 && flaggedDelete.design._active === null && flaggedDelete.design._themeChanged() === false && tabChips( flaggedDelete ).length === 0 );

const unflagged = panel();
unflagged.show( 'type' );
unflagged.move( sizeStrip, 1 );

check( 'a screen that names no theme has no theme to differ from: no chip at the label, whatever is moved',
	unflagged.design._themeChanged() === false && tabChips( unflagged ).length === 0 );

console.log('');

// --- Load ------------------------------------------------------------------------------------------
//
// A theme becomes the working copy and a draft: nothing is stored, the way back is shown. A
// typeface it names that the project has not installed is offered for install first, the ones the
// list of the Typefaces tab has by name, in one question

console.log( 'Load' );

const withFonts = themeSetup( { title : 'inter', text : 'nowhere-sans' } );
/** The controls of the list that are switched off while a request is on its way: four buttons on a shipped theme, six with the pencil and Delete on a theme of the project, and the field of the file */
const THEME_CONTROLS = THEMES.filter( function( theme ) { return theme.shipped === true } ).length * 4 + THEMES.filter( function( theme ) { return theme.shipped === false } ).length * 6 + 1;
const loading = themed();
loading.byId('design-theme-load-magazine').fire('click');

check( 'Load posts design/theme/load with the slug and nothing else, and switches the buttons and the file field of the screen off until it is answered - a second action over the first would answer over a state the screen does not have',
	JSON.stringify( loading.requests.map( function( request ) { return [ request.endpoint, request.payload ] } ) ) === JSON.stringify( [ [ 'theme/load', { slug : 'magazine' } ] ] )
	&& themeControls( loading ).length === THEME_CONTROLS && themeControls( loading ).every( function( control ) { return control.disabled === true } ) && loading.design._themeBusy === true && loading.line().textContent === '' );

loading.byId('design-theme-load-poster').fire('click');
loading.byId('design-theme-export-nino').fire('click');
loading.byId('design-theme-edit-office').fire('click');
loading.byId('design-theme-rename-mein-theme').fire('click');

check( '...and a press on another button meanwhile posts nothing, whichever it is: Load, Export, Edit or the pencil', loading.requests.length === 1
	&& themeControls( loading ).every( function( control ) { return control.disabled === true } ) && loading.design._themeRename === '' );

loading.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );
loading.settle();

check( 'an answer that names typefaces the project has not installed asks once, naming those the list of the Typefaces tab has - one it has not is not offered - and installs the first; nothing is drawn yet',
	loading.questions().length === 1 && loading.questions()[0] === say( '/_admin/design/theme/confirm/fonts', 'Inter' )
	&& loading.requests.length === 1 && loading.requests[0].endpoint === 'webfonts/install' && JSON.stringify( loading.requests[0].payload ) === JSON.stringify( { slug : 'inter' } )
	&& loading.design._edit.theme === 'mein-theme' && loading.design._themeBusy === true );

loading.answer( 200, { msg : INTER_INSTALLED } );

check( '...and when they are installed it reads design/list again, for the selects of the Type view, before the screen is drawn',
	loading.requests.length === 1 && loading.requests[0].endpoint === 'list' && loading.design._edit.theme === 'mein-theme' && loading.design._data.options.title.indexOf('inter') === -1 );

loading.answer( 200, listing( { theme : 'mein-theme', css : ownCss, active : own, options : optionsWith( [ 'inter' ] ) } ) );

check( 'the theme is then the working copy - its templates, its knobs, its size, its css and the name of the theme; of the typefaces the one that was installed is in the title and the one the list does not have is the face of Nino',
	loading.design._edit.theme === 'magazine' && loading.design._edit.css === ".nino-section-title { border-bottom-color: red; }\n" && loading.design._edit.parts.header === 'second' && loading.design._edit.parts.card === 'third'
	&& loading.design._edit.parts.buttons === 'first' && loading.design._edit.knobs.card.space === 1 && loading.design._edit.size === -1
	&& loading.design._edit.fonts.title.family === 'inter' && loading.design._edit.fonts.text.family === '' && loading.design._edit.fonts.subtitle.family === 'open-sans' );
check( '...and the active theme: its record is the theme as it was taken over, and the list - the screen is Load still, none switched by itself - marks the row of the draft and no other',
	loading.design._active.slug === 'magazine' && loading.design._active.shipped === true && loading.design._active.css === loading.design._edit.css
	&& JSON.stringify( loading.design._active.setup ) === JSON.stringify( loading.design._snapshotOf( loading.design._edit ) )
	&& loading.design._themeChanged() === false
	&& screenOf( loading ) === 'theme' && hasClass( themeRow( loading, 'magazine' ), 'design-theme-row--active' ) && hasClass( themeRow( loading, 'mein-theme' ), 'design-theme-row--active' ) === false );
check( '...nothing is stored: the way back is shown and the shell would ask before it lost the draft; the buttons are on again, and the bar says what was installed, that the theme is loaded and which typeface the project lacks - in the colour of a note',
	loading.design._isDirty() === true && loading.byId('design-reset').hidden === false && loading.design._themeBusy === false && themeControls( loading ).every( function( control ) { return control.disabled === false } )
	&& loading.line().textContent === [ INTER_INSTALLED, say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'nowhere-sans' ) ].join(' ')
	&& loading.line().className === 'nino-admin-hint' && loading.requests.length === 0 );

check( '...no mode switched by itself - the list was where it was, and the screen is the theme as it was taken over, so there is no chip on the button of Themes',
	loading.design._screen === 'themes' && tabChips( loading ).length === 0 && textsOf( themeRow( loading, 'magazine' ), 'design-theme-name' ) === 'Magazine' );

loading.show('css');

check( '...and the field of the css on Custom CSS holds the css of the theme', loading.byId('design-theme-css').value === loading.design._edit.css );

loading.tab( tabWord('templates') );
const loadedHeader = shown( setSelect( loading, 'header' ) );
const loadedCard = shown( setSelect( loading, 'card' ) );

loading.tab( tabWord('type') );
check( '...and the other tabs show the loaded values: the frame and the template of the cards on Templates, the size and the typefaces on Type',
	loadedHeader === 'second' && loadedCard === 'third' && loading.pressed( TEXT['/_admin/design/label/size'] ) === [ -2, -1, 0, 1, 2 ].map( function( at ) { return String( at === -1 ) } ).join()
	&& shown( loading.byId('design-font-title-family') ) === 'inter' && shown( loading.byId('design-font-text-family') ) === '' );

loading.tab( tabWord('surfaces') );
check( '...(Surfaces, the knob of a card)',
	pressedKey( loading, 'knobs/card/space' ) === [ -2, -1, 0, 1, 2 ].map( function( at ) { return String( at === 1 ) } ).join() );

// The focus after a Load that draws the list anew: the press is gone with the old list, and the Load button of the row has it again
const loadFocus = themed();
const loadFocusOld = loadFocus.byId('design-theme-load-magazine');
loadFocusOld.focus();
loadFocusOld.fire('click');
loadFocus.answer( 200, themeLoad( 'magazine', themeSetup( {} ), [] ) );

check( 'the focus a press on Load had is on the Load button of the row again once the list is drawn anew - a new element, the old one is out of the document - and not on the page',
	focused !== null && focused === loadFocus.byId('design-theme-load-magazine') && focused !== loadFocusOld && descendants( loadFocus.pane ).indexOf( loadFocusOld ) === -1 && loadFocus.design._edit.theme === 'magazine' );

// A no installs nothing
const declining = themed();
declining.willConfirm( false );
declining.byId('design-theme-load-magazine').fire('click');
declining.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );
declining.settle();

check( 'a no installs nothing and takes the theme over at once: the typefaces that are not here are the face of Nino, and every one of them is named in the bar, those the list has as well',
	declining.questions().length === 1 && declining.requests.length === 0 && declining.design._edit.theme === 'magazine' && declining.design._edit.fonts.title.family === '' && declining.design._edit.fonts.text.family === ''
	&& declining.line().textContent === [ say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'Inter, nowhere-sans' ) ].join(' ') && declining.line().className === 'nino-admin-hint'
	&& declining.design._themeBusy === false );

// Two typefaces, installed one after the other; the first fails
const failingInstall = themed();
failingInstall.byId('design-theme-load-magazine').fire('click');
failingInstall.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter', text : 'lora' } ), [ INTER, LORA ] ) );
failingInstall.settle();

check( 'the typefaces are named together, in the order the theme names them',
	failingInstall.questions()[0] === say( '/_admin/design/theme/confirm/fonts', 'Inter, Lora' ) && failingInstall.requests[0].endpoint === 'webfonts/install' && failingInstall.requests[0].payload.slug === 'inter' );

/*	A routine question: a live site asks before it installs the typefaces a theme names, a site that is being built installs them - the first of them is on its way at once	*/
MODES.forEach( function( mode ) {
	const weighed = themed();
	const asked = mode === 'production';
	weighed.setMode( mode );
	weighed.byId('design-theme-load-magazine').fire('click');
	weighed.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );
	weighed.settle();
	check( 'the typefaces a theme names are '+ ( asked === true ? 'asked about' : 'installed without a question' )+ ' in '+ mode+ ': a routine question, the answer that goes on called Install',
		weighed.questions().length === ( asked === true ? 1 : 0 ) && weighed.requests.length === 1 && weighed.requests[0].endpoint === 'webfonts/install'
		&& weighed.levels.length === 1 && weighed.levels[0].level === 'routine' && weighed.levels[0].asked === asked && weighed.levels[0].options.yes === TEXT['/_admin/design/typefaces/available/install'] );
} );

{
	// A question that cannot be asked, since another stands open, is a no: the theme is taken as it is and the screen is free again
	const refused = themed();
	refused.holdOpen();
	refused.byId('design-theme-load-magazine').fire('click');
	refused.answer( 200, themeLoad( 'magazine', withFonts, [ INTER, NOWHERE ] ) );
	check( 'the typefaces a theme names are not installed when their question cannot be asked: no install is sent, the theme is the screen\'s and the panel is not left busy',
		refused.requests.length === 0 && refused.design._edit.theme === 'magazine' && refused.design._themeBusy === false && refused.levels.length === 1 && refused.levels[0].asked === true );
}

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
unlisted.settle();
unlisted.answer( 200, { msg : INTER_INSTALLED } );
unlisted.answer( 500, {} );

check( 'a list that cannot be read after the installs does not hold the theme back: it is taken over with the families the selects had before - the typeface that was installed is not among them yet, so the title has the face of Nino and the bar names it',
	unlisted.requests.length === 0 && unlisted.design._edit.theme === 'magazine' && unlisted.design._edit.fonts.title.family === '' && unlisted.design._themeBusy === false
	&& unlisted.line().textContent === [ INTER_INSTALLED, say( '/_admin/design/theme/msg/loaded', 'Magazine' ), say( '/_admin/design/theme/msg/unavailable', 'Inter' ) ].join(' ') );

// Both installed, one after the other
const installing = themed();
installing.byId('design-theme-load-magazine').fire('click');
installing.answer( 200, themeLoad( 'magazine', themeSetup( { title : 'inter', text : 'lora' } ), [ INTER, LORA ] ) );
installing.settle();
installing.answer( 200, { msg : INTER_INSTALLED } );

check( 'a yes installs them one after the other: the second is asked for only when the first is answered',
	installing.requests.length === 1 && installing.requests[0].endpoint === 'webfonts/install' && installing.requests[0].payload.slug === 'lora' );

installing.answer( 200, { msg : LORA_INSTALLED } );
installing.answer( 200, listing( { options : optionsWith( [ 'inter', 'lora' ] ), active : own, theme : 'mein-theme', css : ownCss } ) );

check( '...the list is read after the last, and what each install said is in the bar before the rest',
	installing.requests.length === 0 && installing.design._edit.theme === 'magazine' && installing.line().textContent.indexOf( INTER_INSTALLED+ ' '+ LORA_INSTALLED+ ' ' ) === 0
	&& installing.design._edit.fonts.text.family === 'lora' && installing.design._edit.fonts.title.family === 'inter' );

// A theme with nothing missing is taken over without a question
const plainLoad = themed();
plainLoad.byId('design-theme-load-nino').fire('click');
plainLoad.answer( 200, themeLoad( 'nino', snapshotOf( listing() ), [] ) );

check( 'a theme that names nothing the project lacks is taken over without a question, the bar saying only that it is loaded - and quietly',
	plainLoad.questions().length === 0 && plainLoad.requests.length === 0 && plainLoad.design._edit.theme === 'nino' && plainLoad.line().textContent === say( '/_admin/design/theme/msg/loaded', 'Nino' ) && plainLoad.line().className === ''
	&& plainLoad.design._isDirty() === true && plainLoad.design._themeChanged() === false && tabChips( plainLoad ).length === 0 );

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
	&& keeping.design._edit.parts.header === 'second' && keeping.design._edit.parts.card === 'third' && keeping.design._edit.size === -1 && keeping.design._edit.knobs.card.space === 1
	&& keeping.design._edit.radius === 1 && keeping.design._edit['radius-alt'] === 2 && keeping.design._edit.radii.card === 'alternative' && keeping.design._edit.radii.image === 'standard' );
check( '...the record of the theme the chip compares with has no colours either, so the screen is the theme: no chip "changed" - a colour moved afterwards is no change of it, and a knob moved is',
	Object.prototype.hasOwnProperty.call( keeping.design._active.setup, 'colours' ) === false && tabChips( keeping ).length === 0 && keeping.design._themeChanged() === false
	&& ( keeping.design._edit.colours.primary = '#112233', keeping.design._themeChanged() ) === false
	&& ( keeping.design._edit.size = 1, keeping.design._themeChanged() ) === true );

keeping.design._edit.size = -1;

check( '...and the radius of the page is a part of the theme like the rest: a strip of it moved, or the strength of a kind of surface, is a change of the theme, and put back it is none',
	keeping.design._themeChanged() === false
	&& ( keeping.design._edit.radius = -2, keeping.design._themeChanged() ) === true && ( keeping.design._edit.radius = 1, keeping.design._themeChanged() ) === false
	&& ( keeping.design._edit['radius-alt'] = 0, keeping.design._themeChanged() ) === true && ( keeping.design._edit['radius-alt'] = 2, keeping.design._themeChanged() ) === false
	&& ( keeping.design._edit.radii.card = 'standard', keeping.design._themeChanged() ) === true && ( keeping.design._edit.radii.card = 'alternative', keeping.design._themeChanged() ) === false );

const adopting = themed( { colours : Object.assign( {}, listing().colours, { primary : '#b5562a' } ) } );
const colourful = themeSetup();
colourful.colours = Object.assign( {}, colourful.colours, { primary : '#2e7d32' } );
adopting.byId('design-theme-load-magazine').fire('click');
adopting.answer( 200, themeLoad( 'magazine', colourful, [] ) );

check( 'a theme that names colours - one of the project, written from the screen with them - takes them over, and its record has them: the compare reads the palette again',
	brandOf( adopting ) === '#2e7d32' && adopting.design._active.setup.colours.primary === '#2e7d32' && adopting.design._themeChanged() === false
	&& ( adopting.design._edit.colours.primary = '#112233', adopting.design._themeChanged() ) === true );

/*	The choices for the features are the theme's: the server answers them for every theme, an empty list where it names none, and the screen takes them in place of its own. A theme of the
	project, written from the screen with them, takes them over and its record has them, so a choice moved afterwards is a change of it; one written with none takes the screen's away again	*/
const chosenFeatures = { awning : { set : 'loud', knobs : { size : 1 } } };

const recalled = themeSetup();
recalled.features = { awning : { set : 'regular', knobs : { line : 1 }, styles : {} } };
const recalling = themed( featured( chosenFeatures ) );
recalling.byId('design-theme-load-magazine').fire('click');
recalling.answer( 200, themeLoad( 'magazine', recalled, [] ) );

check( 'a theme that names choices for the features - one of the project, written from the screen with them - takes them over in place of the screen\'s, and its record has them: the compare reads them again',
	JSON.stringify( recalling.design._edit.features ) === JSON.stringify( recalled.features ) && JSON.stringify( recalling.design._active.setup.features ) === JSON.stringify( recalled.features )
	&& recalling.design._themeChanged() === false && ( recalling.design._edit.features.awning.knobs.line = -1, recalling.design._themeChanged() ) === true );

const clearing = themed( featured( chosenFeatures ) );
clearing.byId('design-theme-load-magazine').fire('click');
clearing.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );

check( '...and one written with none - an empty list on the wire - takes the screen\'s choices away, and is the theme the screen is: no chip, no entry left',
	Array.isArray( themeSetup().features ) === true && themeSetup().features.length === 0 && JSON.stringify( clearing.design._edit.features ) === '{}' && clearing.design._themeChanged() === false
	&& tabChips( clearing ).length === 0 );

// A theme brings the styles of the templates with the rest of its setup: taken over on a load, compared by the chip
const styTheme = themed();
const styThemeSetup = themeSetup();
styThemeSetup.parts.lists = 'second';
styThemeSetup.styles.lists = { rule : 'dashed' };
styTheme.byId('design-theme-load-magazine').fire('click');
styTheme.answer( 200, themeLoad( 'magazine', styThemeSetup, [] ) );

check( 'a theme that is loaded sets the styles of the screen to its own - the template it names and the option it chose - and the screen is that theme: no chip, nothing to go back to',
	JSON.stringify( styTheme.design._edit.styles.lists ) === JSON.stringify( { rule : 'dashed' } ) && styTheme.design._edit.styles.header !== undefined && Object.keys( styTheme.design._edit.styles ).join() === Object.keys( listing().styles ).join()
	&& styTheme.design._themeChanged() === false && tabChips( styTheme ).length === 0 );

styTheme.byId('design-theme-edit-magazine').fire('click');
setSelect( styTheme, 'lists' );

check( '...its option stands in the select of the template in the views, and another option is a change against the theme - the chip says so - which put back is none',
	shown( styTheme.byId('design-style-lists-rule') ) === 'dashed' && ( choose( styTheme.byId('design-style-lists-rule'), 'dotted' ), styTheme.design._themeChanged() === true && tabChips( styTheme ).length === 1 )
	&& ( choose( styTheme.byId('design-style-lists-rule'), 'dashed' ), styTheme.design._themeChanged() === false && tabChips( styTheme ).length === 0 ) );

check( '...and a theme that names no styles - an empty list for each part on the wire - puts the screen back to none: nothing of the choice the screen had is kept',
	( function() {
		const load = themed();
		load.design._edit.parts.lists = 'second';
		load.design._edit.styles.lists = { rule : 'dotted' };
		load.byId('design-theme-load-magazine').fire('click');
		load.answer( 200, themeLoad( 'magazine', themeSetup(), [] ) );
		return load.design._edit.parts.lists === 'first' && JSON.stringify( load.design._edit.styles.lists ) === '{}' && load.design._themeChanged() === false;
	} )() );


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
	noteLines( cleaning ).length === 0 && ( cleaning.tab( tabWord('templates') ), noteLines( cleaning ).length === 0 ) && ( cleaning.show('theme'), noteLines( cleaning ).length === 0 ) );

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
	late.design._edit.theme === 'mein-theme' && late.design._isDirty() === false && late.line().textContent === TEXT['/_admin/design/msg/reverted'] && late.questions().length === 0 );

console.log('');

// --- Save, rename, duplicate, delete -----------------------------------------------------------------
//
// Save theme puts the screen into the loaded theme of the project, from the action bar, while the views are open:
// its setup and its css, with the name and the description the theme has. The pencil of a row turns its name and its
// description into two fields, and only those two are written. A copy is made by Duplicate, a theme is taken away by
// Delete, with a question first

console.log( 'Save, rename, duplicate, delete' );

const saving = themed( {}, 'setup' );
const savingBar = saving.bar();

check( 'Save theme stands in the action bar while the views are open and a theme of the project is loaded: a button of its own, before the way back, with the buttons that keep - it is on, and it is not the primary one, which is Apply',
	saving.byId('design-theme-save') !== null && saving.byId('design-theme-save').parent === savingBar && savingBar.children.indexOf( saving.byId('design-theme-save') ) < savingBar.children.indexOf( saving.byId('design-reset') )
	&& saving.byId('design-theme-save').textContent === TEXT['/_admin/design/theme/save'] && saving.byId('design-theme-save').disabled === false && hasClass( saving.byId('design-theme-save'), 'nino-admin-btn-primary' ) === false
	&& saving.byId('design-theme-save').type === 'button' );

const listBar = themed();
const shippedBar = themed( { theme : 'magazine', running : 'magazine', active : activeOf( 'magazine', 'Magazine', 'Lifted cards and a drop cap', true, '' ) }, 'setup' );
const noThemeBar = panel();

check( '...and nowhere else: not in the bar of the list, not with a shipped theme loaded - it is a file of the feature, there is no way to save into it - and not with no theme loaded, where there is nothing to save into',
	listBar.byId('design-save') !== null && listBar.byId('design-theme-save') === null && shippedBar.byId('design-save') !== null && shippedBar.byId('design-theme-save') === null
	&& noThemeBar.byId('design-save') !== null && noThemeBar.byId('design-theme-save') === null );

saving.show( 'type' );
saving.move( sizeStrip, 1 );
saving.byId('design-theme-save').focus();
saving.byId('design-theme-save').fire('click');
focused = null;

check( 'Save theme posts design/theme/save with the slug and the working copy and no name and no description - the theme keeps its own - and switches the buttons off, its own among them, until it is answered',
	saving.requests.length === 1 && saving.requests[0].endpoint === 'theme/save' && saving.requests[0].payload.slug === 'mein-theme' && saving.requests[0].payload.theme === 'mein-theme'
	&& saving.requests[0].payload.knobs.section !== undefined && saving.requests[0].payload.size === 1
	&& Object.keys( saving.requests[0].payload ).sort().join() === 'colours,css,features,fonts,knobs,motion,navs,parts,radii,radius,radius-alt,size,slug,styles,theme,tiers,whitespace,width'
	&& saving.byId('design-theme-save').disabled === true && saving.design._themeBusy === true );

saving.byId('design-theme-save').fire('click');

check( '...a second press meanwhile posts nothing', saving.requests.length === 1 );

saving.answer( 200, {
	themes : saving.design._data.themes.map( function( theme ) { return theme.slug === 'mein-theme' ? Object.assign( {}, theme, { name : 'Mein Thema' } ) : theme } ),
	active : activeOf( 'mein-theme', 'Mein Thema', 'The theme of this project', false, ownCss, JSON.parse( JSON.stringify( saving.design._snapshotOf( saving.design._edit ) ) ) ),
} );

const savingFocus = focused;
const savingButton = saving.byId('design-theme-save');

check( 'the answer is the theme as the server holds it: the chip goes, the button is on again, the bar says it was saved by the name the server gives it, and the list - when it is shown - has the name the file has',
	saving.design._themeChanged() === false && tabChips( saving ).length === 0 && saving.byId('design-theme-save').disabled === false && saving.design._active.name === 'Mein Thema'
	&& saving.line().textContent === say( '/_admin/design/theme/msg/saved', 'Mein Thema' ) && saving.design._data.theme === 'mein-theme' && saving.design._data.active.name === 'Mein Thema'
	&& ( saving.back(), textsOf( themeRow( saving, 'mein-theme' ), 'design-theme-name' ) === 'Mein Thema' ) );

check( '...and the button has the focus back once it is on again: a button that is switched off drops it to the page, and the keyboard stays on Save theme',
	savingFocus === savingButton && savingFocus !== null );

const savingFailed = themed( {}, 'setup' );
savingFailed.byId('design-theme-save').focus();
savingFailed.byId('design-theme-save').fire('click');
focused = null;
savingFailed.answer( 500, {} );

check( '...also where the save failed: the button is on again with the focus on it',
	focused === savingFailed.byId('design-theme-save') && savingFailed.byId('design-theme-save').disabled === false );

const savingMoved = themed( {}, 'setup' );
savingMoved.byId('design-theme-save').focus();
savingMoved.byId('design-theme-save').fire('click');
savingMoved.byId('design-tab-card').focus();
savingMoved.answer( 200, {
	themes : savingMoved.design._data.themes,
	active : activeOf( 'mein-theme', 'My theme', 'The theme of this project', false, ownCss, JSON.parse( JSON.stringify( savingMoved.design._snapshotOf( savingMoved.design._edit ) ) ) ),
} );

check( '...but a focus that was moved on while the save ran - to a tab of the views - stays where it was put and is not pulled back to the button',
	focused === savingMoved.byId('design-tab-card') && focused !== null && savingMoved.byId('design-theme-save').disabled === false );

// Saving the theme that is stored changes what the way back goes back to
const stored = themed( {}, 'setup' );
stored.byId('design-theme-save').fire('click');
stored.answer( 200, { themes : stored.design._data.themes.map( function( theme ) { return theme.slug === 'mein-theme' ? Object.assign( {}, theme, { name : 'Renamed' } ) : theme } ), active : activeOf( 'mein-theme', 'Renamed', 'The theme of this project', false, ownCss ) } );
stored.design._edit.css = 'a{}';
stored.byId('design-reset').fire('click');

check( 'the theme the stored setup names, saved, is what the way back goes back to: the name it has now',
	stored.design._active.name === 'Renamed' && stored.design._data.active.name === 'Renamed' && stored.design._edit.css === ownCss && stored.design._isDirty() === false );

// Rename ------------------------------------------------------------------------------------------------

const renaming = themed();
renaming.byId('design-theme-rename-mein-theme').fire('click');
const renamingRow = themeRow( renaming, 'mein-theme' );

check( 'the pencil turns the name and the description of the row into two fields, with what the theme has in them, held to 80 and 400 characters, labelled by the words of the fields - and the row\'s buttons are Save and Cancel instead of the five, the first the primary one',
	renaming.requests.length === 0 && renaming.design._themeRename === 'mein-theme' && byClass( renamingRow, 'design-theme-text' )[0].children.length === 2
	&& renaming.byId('design-theme-name').value === 'My theme' && renaming.byId('design-theme-name').maxLength === 80 && renaming.byId('design-theme-description').value === 'The theme of this project' && renaming.byId('design-theme-description').maxLength === 400
	&& renaming.byId('design-theme-name').parent.children[0].textContent === TEXT['/_admin/design/theme/field/name'] && renaming.byId('design-theme-description').parent.children[0].textContent === TEXT['/_admin/design/theme/field/description']
	&& hasClass( renaming.byId('design-theme-name').parent, 'nino-admin-field' ) && hasClass( renamingRow, 'design-theme-row--renaming' )
	&& themeButtons( renaming, 'mein-theme' ).map( function( button ) { return button.id } ).join() === 'design-theme-rename-save,design-theme-rename-cancel'
	&& themeButtons( renaming, 'mein-theme' ).map( function( button ) { return button.textContent } ).join() === TEXT['/_admin/common/label/save']+ ','+ TEXT['/_admin/common/label/cancel']
	&& hasClass( renaming.byId('design-theme-rename-save'), 'nino-admin-btn-primary' ) && renaming.byId('design-theme-rename-save').type === 'button'
	&& renaming.byId('design-theme-rename-mein-theme') === null && renaming.byId('design-theme-delete-mein-theme') === null && renaming.byId('design-theme-edit-mein-theme') === null
	&& renaming.byId('design-theme-load-magazine') !== null && focused === renaming.byId('design-theme-name') );

check( '...the other rows are as they were, and only one row is a pair of fields: the pencil of another row opens its fields and closes these',
	themeRow( renaming, 'nino' ).children.length === 2 && byClass( renaming.form, 'design-theme-row--renaming' ).length === 1 && renaming.byId('design-theme-edit-nino') !== null );

check( 'the name of the fields is a required one, marked the way the workbench marks every one - the asterisk behind its name, out of reach of assistive technology, and aria-required on the control - and the description is not',
	renaming.byId('design-theme-name').getAttribute('aria-required') === 'true' && byClass( renaming.byId('design-theme-name').parent, 'nino-admin-required' ).length === 1
	&& byClass( renaming.byId('design-theme-name').parent, 'nino-admin-required' )[0].getAttribute('aria-hidden') === 'true' && renaming.byId('design-theme-description').getAttribute('aria-required') === null
	&& byClass( renaming.byId('design-theme-description').parent, 'nino-admin-required' ).length === 0 );

renaming.byId('design-theme-name').value = 'Typed name';
renaming.byId('design-theme-name').fire('input');
renaming.byId('design-theme-description').value = 'Typed description';
renaming.byId('design-theme-description').fire('input');
renaming.byId('design-theme-duplicate-nino').fire('click');
renaming.answer( 200, { slug : 'nino-copy', themes : withTheme( 'nino-copy', 'Nino copy', 'The framework as it is' ) } );

check( 'what is typed in the fields stays when the list is drawn again by another action of the list - and it is no part of the draft',
	renaming.byId('design-theme-name').value === 'Typed name' && renaming.byId('design-theme-description').value === 'Typed description' && renaming.design._themeRename === 'mein-theme' && renaming.design._isDirty() === false );

renaming.byId('design-theme-rename-save').fire('click');

check( 'Save posts design/theme/rename with the slug, the name and the description as they are typed - and nothing of the working copy - and switches the buttons off until it is answered',
	renaming.requests.length === 1 && renaming.requests[0].endpoint === 'theme/rename' && JSON.stringify( renaming.requests[0].payload ) === JSON.stringify( { slug : 'mein-theme', name : 'Typed name', description : 'Typed description' } )
	&& themeControls( renaming ).every( function( control ) { return control.disabled === true } ) && renaming.design._themeBusy === true );

focused = null;
renaming.answer( 200, {
	themes : withTheme( 'nino-copy', 'Nino copy', 'The framework as it is' ).map( function( theme ) { return theme.slug === 'mein-theme' ? Object.assign( {}, theme, { name : 'Typed name', description : 'Typed description' } ) : theme } ),
	active : activeOf( 'mein-theme', 'Typed name', 'Typed description', false, ".server { margin: 0 }\n" ),
} );

check( 'the answer closes the fields and repaints the row with the new words, the buttons are the five again and on, and the bar says it was renamed, by the name it has now',
	renaming.design._themeRename === '' && byClass( renaming.form, 'design-theme-row--renaming' ).length === 0 && renaming.byId('design-theme-name') === null
	&& textsOf( themeRow( renaming, 'mein-theme' ), 'design-theme-name' ) === 'Typed name' && textsOf( themeRow( renaming, 'mein-theme' ), 'design-theme-description' ) === 'Typed description'
	&& themeButtons( renaming, 'mein-theme' ).length === 5 && themeControls( renaming ).every( function( control ) { return control.disabled === false } )
	&& renaming.line().textContent === say( '/_admin/design/theme/msg/renamed', 'Typed name' ) && renaming.line().className === '' );
check( 'the focus the Save of the fields had goes to the pencil of the row, which is drawn again: the keyboard stays where the rename was made',
	focused === renaming.byId('design-theme-rename-mein-theme') && focused !== null );
check( '...the theme the screen was taken from has its new words in the record the chip compares with, and the setup and the css it was taken from: the screen is still the theme, and a rename is no change of it - the stored setup\'s record is the one the way back goes to',
	renaming.design._active.name === 'Typed name' && renaming.design._active.description === 'Typed description' && JSON.stringify( renaming.design._active.setup ) === JSON.stringify( own.setup ) && renaming.design._active.css === ownCss
	&& renaming.design._themeChanged() === false && renaming.design._data.active.name === 'Typed name' && renaming.design._isDirty() === false );

// A rename of a theme that is not the loaded one leaves the record of the loaded one alone
const renamingOther = themed( { themes : withTheme( 'other', 'Other', 'Another one' ) } );
renamingOther.byId('design-theme-rename-other').fire('click');
renamingOther.byId('design-theme-name').value = 'Other, renamed';
renamingOther.byId('design-theme-name').fire('input');
renamingOther.byId('design-theme-rename-save').fire('click');
renamingOther.answer( 200, { themes : withTheme( 'other', 'Other, renamed', 'Another one' ), active : activeOf( 'other', 'Other, renamed', 'Another one', false, '' ) } );

check( 'a theme that is not the loaded one is renamed in the list and nowhere else: the record of the loaded theme is as it was, the stored one too',
	textsOf( themeRow( renamingOther, 'other' ), 'design-theme-name' ) === 'Other, renamed' && renamingOther.design._active.slug === 'mein-theme' && renamingOther.design._active.name === 'My theme'
	&& renamingOther.design._data.active.name === 'My theme' && renamingOther.requests.length === 0 );

// Enter saves, Escape and Cancel close
const entering = themed();
entering.byId('design-theme-rename-mein-theme').fire('click');
entering.byId('design-theme-name').value = 'By enter';
entering.byId('design-theme-name').fire('input');
press( entering.byId('design-theme-description'), 'Enter' );

check( 'Enter in either field saves, the way the button does: design/theme/rename with the slug and the two fields',
	entering.requests.length === 1 && entering.requests[0].endpoint === 'theme/rename' && entering.requests[0].payload.slug === 'mein-theme' && entering.requests[0].payload.name === 'By enter' && entering.requests[0].payload.description === 'The theme of this project' );

const escaping = themed();
escaping.byId('design-theme-rename-mein-theme').fire('click');
escaping.byId('design-theme-name').value = 'Never saved';
escaping.byId('design-theme-name').fire('input');
press( escaping.byId('design-theme-name'), 'Escape' );

check( 'Escape closes the fields without a request: the row is as it was, with the name it had, the pencil back with the focus on it, and what was typed is let go of',
	escaping.requests.length === 0 && escaping.design._themeRename === '' && escaping.byId('design-theme-name') === null && textsOf( themeRow( escaping, 'mein-theme' ), 'design-theme-name' ) === 'My theme'
	&& themeButtons( escaping, 'mein-theme' ).length === 5 && focused === escaping.byId('design-theme-rename-mein-theme') );

const cancelling = themed();
cancelling.byId('design-theme-rename-mein-theme').fire('click');
cancelling.byId('design-theme-rename-cancel').fire('click');
cancelling.byId('design-theme-rename-mein-theme').fire('click');

check( 'Cancel does the same, and the fields opened again have what the theme has in them, not what was typed before',
	cancelling.requests.length === 0 && cancelling.byId('design-theme-name').value === 'My theme' );

const refusedRename = themed();
refusedRename.byId('design-theme-rename-mein-theme').fire('click');
refusedRename.byId('design-theme-name').value = '';
refusedRename.byId('design-theme-name').fire('input');
refusedRename.byId('design-theme-rename-save').focus();
refusedRename.byId('design-theme-rename-save').fire('click');
focused = null;
refusedRename.answer( 400, { error : 'the name is empty', code : 'design_name' } );

const refusedField = refusedRename.byId('design-theme-name');
const refusedError = byClass( refusedRename.form, 'nino-admin-field-error' );

check( 'a name the server turns down is told by the sentence of its code, in red; the fields stay open with what was typed, the buttons are on again, and nothing else changed',
	refusedRename.line().textContent === TEXT['/_admin/error/design_name'] && refusedRename.line().className === 'nino-admin-error' && refusedRename.design._themeRename === 'mein-theme'
	&& refusedRename.byId('design-theme-name').value === '' && themeControls( refusedRename ).every( function( control ) { return control.disabled === false } ) && refusedRename.design._isDirty() === false );
check( '...and the sentence stands under the field as well, linked to it, with the focus on it: one sentence after the label of the name, the control marked invalid and described by it',
	refusedError.length === 1 && refusedError[0].textContent === TEXT['/_admin/error/design_name'] && refusedError[0].parent === refusedField.parent.parent && refusedError[0].parent.children.indexOf( refusedError[0] ) === refusedError[0].parent.children.indexOf( refusedField.parent ) + 1
	&& refusedField.getAttribute('aria-invalid') === 'true' && refusedField.getAttribute('aria-describedby') === refusedError[0].id && focused === refusedField );

refusedRename.byId('design-theme-rename-save').fire('click');
refusedRename.answer( 400, { error : 'the name has no letter or digit in it', code : 'design_name' } );

check( '...a second refusal leaves one sentence, not two - and one that is no refusal of the name puts none there',
	byClass( refusedRename.form, 'nino-admin-field-error' ).length === 1 && ( function() {
		const other = themed();
		other.byId('design-theme-rename-mein-theme').fire('click');
		other.byId('design-theme-rename-save').fire('click');
		other.answer( 500, { error : 'could not write /data/themes/mein-theme.php' } );
		return byClass( other.form, 'nino-admin-field-error' ).length === 0 && other.byId('design-theme-name').getAttribute('aria-invalid') === null;
	} )() );

const lateRename = themed();
lateRename.byId('design-theme-rename-mein-theme').fire('click');
lateRename.byId('design-theme-rename-save').fire('click');
lateRename.byId('design-reset').fire('click');
lateRename.answer( 200, { themes : [], active : activeOf( 'mein-theme', 'Late', '', false, '' ) } );

check( '...and an answer that comes after the way back changes nothing on the screen', slugsOf( lateRename ) === 'nino,magazine,poster,statement,product,office,mein-theme' && lateRename.design._active.name === 'My theme' );

// Duplicate
const copying = themed();
const copyingPressed = copying.byId('design-theme-duplicate-magazine');
copyingPressed.focus();
copyingPressed.fire('click');

check( 'Duplicate posts design/theme/duplicate with the slug - a shipped theme too, which is how it is changed', copying.requests.length === 1 && copying.requests[0].endpoint === 'theme/duplicate'
	&& JSON.stringify( copying.requests[0].payload ) === JSON.stringify( { slug : 'magazine' } ) );

copying.answer( 200, { slug : 'magazine-copy', themes : withTheme( 'magazine-copy', 'Magazine copy', 'Lifted cards and a drop cap' ) } );

check( '...the list is drawn again with the copy in it and nothing is loaded: the working copy and the active theme are as they were; the bar names both',
	slugsOf( copying ) === 'nino,magazine,poster,statement,product,office,mein-theme,magazine-copy' && copying.design._edit.theme === 'mein-theme' && copying.design._isDirty() === false
	&& hasClass( themeRow( copying, 'mein-theme' ), 'design-theme-row--active' ) && themeButtons( copying, 'magazine-copy' ).length === 5 && copying.byId('design-theme-rename-magazine-copy') !== null
	&& copying.line().textContent === say( '/_admin/design/theme/msg/duplicated', 'Magazine', 'Magazine copy' ) && themeControls( copying ).every( function( control ) { return control.disabled === false } ) );
check( '...and the focus the press had is on the Duplicate button of that row again, the new one the list was drawn with',
	focused !== null && focused === copying.byId('design-theme-duplicate-magazine') && focused !== copyingPressed );

// Delete
const deleting = themed();
deleting.willConfirm( false );
deleting.byId('design-theme-delete-mein-theme').fire('click');
deleting.settle();

check( 'Delete asks first, naming the theme, and a no does nothing',
	deleting.questions().length === 1 && deleting.questions()[0] === say( '/_admin/design/theme/confirm/delete', 'My theme' ) && deleting.requests.length === 0 && deleting.design._themeBusy === false );

MODES.forEach( function( mode ) {
	const weighed = themed();
	weighed.setMode( mode );
	weighed.byId('design-theme-delete-mein-theme').fire('click');
	weighed.settle();
	check( '...in '+ mode+ ' as well - deleting a theme cannot be taken back, and the shell asks it whatever the mode'+ ': a destructive question, the answer that goes on called Delete',
		weighed.questions().length === 1 && weighed.questions()[0] === say( '/_admin/design/theme/confirm/delete', 'My theme' ) && JSON.stringify( endpoints( weighed ) ) === JSON.stringify( [ 'theme/delete' ] )
		&& weighed.levels.length === 1 && weighed.levels[0].level === 'destructive' && weighed.levels[0].asked === true && weighed.levels[0].options.yes === TEXT['/_admin/common/label/delete'] && weighed.levels[0].options.no === TEXT['/_admin/common/label/cancel'] );
} );

deleting.willConfirm( true );
deleting.byId('design-theme-delete-mein-theme').fire('click');
deleting.settle();

check( '...a yes posts design/theme/delete with the slug', deleting.requests.length === 1 && deleting.requests[0].endpoint === 'theme/delete' && JSON.stringify( deleting.requests[0].payload ) === JSON.stringify( { slug : 'mein-theme' } ) );

deleting.answer( 200, { themes : THEMES.filter( function( theme ) { return theme.shipped === true } ), theme : '' } );

check( 'the answer takes the theme out of the list; it was the active one, so the screen has none now, and the stored setup names none either, so the screen is still the stored selection',
	slugsOf( deleting ) === 'nino,magazine,poster,statement,product,office' && deleting.design._edit.theme === '' && deleting.design._active === null && deleting.design._data.theme === '' && deleting.design._data.active === null
	&& byClass( deleting.form, 'design-theme-active' ).length === 0
	&& deleting.design._isDirty() === false && deleting.line().textContent === say( '/_admin/design/theme/msg/deleted', 'My theme' ) );

// Deleting a theme that is not the active one leaves the screen alone
const deletingOther = themed( { themes : withTheme( 'other', 'Other', '' ) } );
deletingOther.byId('design-theme-delete-other').fire('click');
deletingOther.settle();
deletingOther.answer( 200, { themes : THEMES.map( function( theme ) { return Object.assign( {}, theme ) } ), theme : 'mein-theme' } );

check( '...a theme that is not the active one is taken out of the list and the screen stays as it is, the stored setup still naming its theme',
	slugsOf( deletingOther ) === 'nino,magazine,poster,statement,product,office,mein-theme' && deletingOther.design._edit.theme === 'mein-theme' && deletingOther.design._active.slug === 'mein-theme'
	&& deletingOther.design._data.theme === 'mein-theme' && deletingOther.design._data.active.slug === 'mein-theme' );

// A delete that comes after the screen was reverted
const lateDelete = themed();
lateDelete.byId('design-theme-delete-mein-theme').fire('click');
lateDelete.settle();
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
exporting.byId('design-theme-export-magazine').fire('click');

check( 'Export on a row posts design/theme/export with the slug of the theme of that row - not the loaded one -, and switches the buttons off until it is answered',
	exporting.requests.length === 1 && exporting.requests[0].endpoint === 'theme/export' && JSON.stringify( exporting.requests[0].payload ) === JSON.stringify( { slug : 'magazine' } )
	&& themeControls( exporting ).every( function( control ) { return control.disabled === true } ) );

exporting.answer( 200, { json : '{\n\t"format": 1\n}', file : 'nino-theme-magazine.json' } );

check( '...and turns the answer into a download: a blob of its text as json, an address for it, a link to the address with the name of the file, clicked while it stands in the document and taken out of it after, the address given back',
	exporting.blobs.length === 1 && exporting.blobs[0].parts.join() === '{\n\t"format": 1\n}' && exporting.blobs[0].type === 'application/json'
	&& exporting.urls.made.length === 1 && exporting.urls.made[0] === exporting.blobs[0]
	&& exporting.downloads.length === 1 && exporting.downloads[0].href === 'blob:design/1' && exporting.downloads[0].download === 'nino-theme-magazine.json' && exporting.downloads[0].mounted === true
	&& exporting.docBody.children.length === 0 && exporting.urls.revoked.join() === 'blob:design/1' );
check( '...the bar says it was exported, naming the theme, and the buttons are on again',
	exporting.line().textContent === say( '/_admin/design/theme/msg/exported', 'Magazine' ) && themeControls( exporting ).every( function( control ) { return control.disabled === false } ) );

const importing = themed();
importing.byId('design-theme-import').files = [];
importing.byId('design-theme-import').fire('change');

check( 'a file field that changed to no file does nothing', importing.readers.length === 0 && importing.requests.length === 0 );

const picked = { name : 'theme.json' };
const importingField = importing.byId('design-theme-import');
importingField.focus();
importingField.files = [ picked ];
importingField.value = 'C:\\fakepath\\theme.json';
importingField.fire('change');

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
check( '...and the focus the file field had is on the field again, the new one the list was drawn with',
	focused !== null && focused === importing.byId('design-theme-import') && focused !== importingField );

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
	rename			: function( screen ) { screen.byId('design-theme-rename-mein-theme').fire('click'); screen.byId('design-theme-rename-save').fire('click') },
	save 				: function( screen ) { screen.byId('design-theme-save').fire('click') },
	duplicate		: function( screen ) { screen.byId('design-theme-duplicate-magazine').fire('click') },
	'delete'		: function( screen ) { screen.byId('design-theme-delete-mein-theme').fire('click'); screen.settle() },
	'export'		: function( screen ) { screen.byId('design-theme-export-mein-theme').fire('click') },
	'import'		: function( screen ) {
		screen.byId('design-theme-import').files = [ picked ];
		screen.byId('design-theme-import').fire('change');
		screen.readers[0].finish( '{}' );
	},
};

/** The mode each action is made in: the list of the themes has Load, Rename, Duplicate, Delete, Export and the file, the views have Save theme in the action bar */
const actionScreens = { load : 'list', rename : 'list', save : 'setup', duplicate : 'list', 'delete' : 'list', 'export' : 'list', 'import' : 'list' };

let said = [];
let spokenFailures = [];
let coded = [];
let unchanged = [];

Object.keys( actions ).forEach( function( action ) {

	const bare = themed( {}, actionScreens[action] );
	actions[action]( bare );
	bare.answer( 500, {} );

	if( bare.line().textContent !== '(500) '+ TEXT['/_admin/design/theme/error/'+ action] || bare.line().className !== 'nino-admin-error' || bare.line().title !== bare.line().textContent )
		said.push( action+ ': '+ bare.line().textContent );

	const spoken = themed( {}, actionScreens[action] );
	actions[action]( spoken );
	spoken.answer( 500, { error : 'could not write /data/themes/mein-theme.php' } );

	if( spoken.line().textContent !== '(500) could not write /data/themes/mein-theme.php' || spoken.line().className !== 'nino-admin-error' )
		spokenFailures.push( action+ ': '+ spoken.line().textContent );

	const refusedBy = themed( {}, actionScreens[action] );
	actions[action]( refusedBy );
	refusedBy.answer( 400, { error : 'the name has no letter or digit in it', code : 'design_name' } );

	if( refusedBy.line().textContent !== TEXT['/_admin/error/design_name'] || refusedBy.line().className !== 'nino-admin-error' )
		coded.push( action+ ': '+ refusedBy.line().textContent );

	if( themeControls( refusedBy ).some( function( control ) { return control.disabled === true } ) || refusedBy.design._themeBusy === true || refusedBy.design._isDirty() === true
		|| ( refusedBy.byId('design-theme-save') !== null && refusedBy.byId('design-theme-save').disabled === true ) || refusedBy.design._edit.theme !== 'mein-theme' || refusedBy.requests.length !== 0 || refusedBy.questions().length !== ( action === 'delete' ? 1 : 0 ) )
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
// was moved - another template for each of the three parts, a font changed, the animation,
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

	/** The controls of a table as [ key, tab, object, order ], by key - the table the answer carries and the stand-in compared as the same thing, whatever order their keys are written in */
	const sortedControls = function( layout ) { return Object.keys( layout.controls ).sort().map( function( key ) { return [ key ].concat( layout.controls[key] ) } ) };

	check( 'the server\'s answer is in the shape the screen draws: the kinds name the kind of each knob - a scale or a choice - and are the kinds the stand-in has, the table of the controls is the stand-in\'s - the six views, the eight objects and the place of every control -, the steps are five words, the size, the width, the whitespace and the two radii are numbers with no list of sizes beside them, the kinds of surface are the keys of the strengths and the two words are the ones the stand-in has - with no list of the kinds beside them -, and the palette gives each knob its kind',
		real.steps.join() === 'least,less,default,more,most' && typeof real.size === 'number' && typeof real.width === 'number' && typeof real.whitespace === 'number' && real.sizes === undefined
		&& typeof real.radius === 'number' && typeof real['radius-alt'] === 'number'
		&& real.surfaces === undefined && JSON.stringify( real.radiusWords ) === JSON.stringify( RADIUS_WORDS )
		&& JSON.stringify( Object.keys( real.radii ) ) === JSON.stringify( SURFACES ) && Object.keys( real.radii ).every( function( kind ) { return RADIUS_WORDS.indexOf( real.radii[kind] ) !== -1 } )
		&& JSON.stringify( real.kinds ) === JSON.stringify( KINDS )
		&& JSON.stringify( real.layout.tabs ) === JSON.stringify( TABS ) && JSON.stringify( real.layout.objects ) === JSON.stringify( OBJECTS )
		&& JSON.stringify( sortedControls( real.layout ) ) === JSON.stringify( sortedControls( layoutTable() ) )
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

	/** Everything a person can open on the screen that carries the preview, in order: the list of the themes, then each view of the setup */
	const OPENABLE = [ 'theme' ].concat( TABS );

	/** Whether what the open screen draws has something in it: the column */
	const filled = function( screen ) { return screen.body().children.length > 0 };

	OPENABLE.forEach( function( name ) {

		if( threw( function() { whole.show( name ) } ) === true ) {
			failed.push( name );
			return;
		}

		drawn.push( name );

		if( filled( whole ) === false || whole.chip() === null || whole.chip().textContent === '' )
			blank.push( name );

		unsaid = unsaid.concat( unwritten( whole ) );
	} );

	check( 'the server\'s answer draws every screen that carries the preview and the bar - the list of the themes and the six views of the setup - each with controls in it and the state of the files on the chip in the bar'+ ( failed.length === 0 ? '' : ' - threw: '+ failed.join() ),
		drawn.join() === OPENABLE.join() && failed.length === 0 && blank.length === 0 );
	check( '...and every word the screen shows for it is one the panel\'s texts carry - the effects, the families, the knobs, the modes'+ ( unsaid.length === 0 ? '' : ' - missing: '+ unsaid.slice( 0, 4 ).join() ), unsaid.length === 0 );

	/*	Every tab is drawn from the table the server gave and from nothing else: the rows it names, in its order, and in each the controls it names, in theirs	*/
	const fromTable = [ 'templates', 'type', 'surfaces', 'colours', 'motion' ];
	const fromTableProblems = fromTable.filter( function( tab ) {
		whole.show( tab );
		return tableRows( real.layout, tab ).length === 0 || JSON.stringify( drawnRows( whole ) ) !== JSON.stringify( tableRows( real.layout, tab ) );
	} );

	check( 'each tab is drawn from the table the server gave and from nothing else: the rows it names for the tab, in its order, and in each row exactly the controls it names for the object, in the order of their places'
		+ ( fromTableProblems.length === 0 ? '' : ' - not so for '+ fromTableProblems.join() ), fromTableProblems.length === 0 );

	check( 'the answer carries the setup a fresh install starts from, cut to the keys that choose something - the stand-in\'s defaults, but for the seven templates, which are the library\'s - and none of the project\'s own: no theme, no css, no record of a compile',
		typeof real.defaults === 'object' && real.defaults !== null
		&& [ 'size', 'width', 'whitespace', 'radius', 'radius-alt', 'radii', 'motion', 'knobs', 'fonts', 'colours' ].every( function( key ) { return JSON.stringify( real.defaults[key] ) === JSON.stringify( listing().defaults[key] ) } )
		&& JSON.stringify( Object.keys( real.defaults.parts ) ) === JSON.stringify( real.parts.map( function( row ) { return row.part } ) )
		&& [ 'format', 'theme', 'css', 'compiled' ].every( function( key ) { return real.defaults[key] === undefined } ) );

	/** The row of the answer's catalogue for a part, by the part's name */
	const rowOf = function( name ) { return real.parts.filter( function( row ) { return row.part === name } )[0] };

	/*	What the head of a closed block ought to say, worked out from the answer and the table alone - the selection, the setup it is compared with and
		the catalogue that says which knobs a template answers to - and not from the script: for each control of the row in the order of the table, its
		label and the word of its position where the position is not the default's, the knobs the chosen template does not answer to left out	*/
	const oracle = function( tab, object ) {

		const lines = [];
		const at = function( value ) { return SIGNED[value + 2] };
		const row = tableRows( real.layout, tab ).filter( function( each ) { return each.object === object } )[0];

		row.controls.forEach( function( control ) {

			const keys = control.split(' ');
			const path = keys[0].split('/');

			if( path[0] === 'knobs' ) {
				const part = rowOf( path[1] );
				const answered = ( ( part.catalogue[part.set] || {} ).knobs || [] ).indexOf( path[2] ) !== -1;
				const position = real.knobs[ path[1] ][ path[2] ];
				const kind = real.kinds[ path[1] ][ path[2] ];
				const word = kind === 'scale' ? at( position ) : ( kind === 'grade' ? ( position === 0 ? '0' : '+'+ position ) : kind === 'toggle' ? TEXT['/_admin/common/label/'+ ( position === 1 ? 'on' : 'off' )] : TEXT['/_admin/design/knob/'+ path[1]+ '/'+ path[2]+ '/'+ [ 'less', 'default', 'more' ][position + 1]] );
				if( answered === true && position !== real.defaults.knobs[ path[1] ][ path[2] ] )
					lines.push( knobWord( path[1], path[2] )+ ' '+ word );
			}
			else if( path[0] === 'radii' ) {
				const kinds = keys.map( function( key ) { return key.split('/')[1] } );
				if( kinds.some( function( kind ) { return real.radii[kind] !== real.defaults.radii[kind] } ) )
					lines.push( TEXT['/_admin/design/radii/'+ ( kinds.length > 1 ? object : kinds[0] )]+ ' '+ TEXT['/_admin/design/radii/'+ real.radii[ kinds[0] ]] );
			}
			else if( path[0] === 'fonts' ) {
				const chosen = real.fonts[ path[1] ];
				const given = real.defaults.fonts[ path[1] ];
				const label = function( column ) { return TEXT['/_admin/design/font/role/'+ path[1]]+ ' - '+ TEXT['/_admin/design/font/head/'+ column] };
				if( chosen.family !== given.family ) {
					// A system stack is worded by the panel's text and a typeface by its own name
					const family = real.families[ chosen.family ] || {};
					lines.push( label('family')+ ' '+ ( family.system === true ? TEXT['/_admin/design/family/'+ chosen.family] : family.name ) );
				}
				if( chosen.weight !== given.weight )
					lines.push( label('weight')+ ' '+ TEXT['/_admin/design/weight/'+ chosen.weight] );
				if( chosen.size !== given.size )
					lines.push( label('size')+ ' '+ chosen.size+ ' '+ TEXT['/_admin/design/fontsize/unit'] );
			}
			else if( path[0] === 'parts' ) {
				// A template is a choice and no position: the block says the one that is chosen, whichever it is
				const part = rowOf( path[1] );
				lines.push( part.catalogue[part.set].name );
			}
			else if( path[0] === 'colours' ) {
				const chosen = real.colours || {};
				const given = real.defaults.colours;
				const valueOf = function( knob ) { return chosen[knob] === undefined ? real.palette[knob]['default'] : chosen[knob] };
				keys.forEach( function( key ) {
					const knob = key.split('/')[1];
					if( knob === 'primary' ) {
						if( chosen.primary !== given.primary )
							lines.push( TEXT['/_admin/design/label/primary']+ ' '+ chosen.primary );
					}
					else if( knob === 'secondary' ) {
						if( ( chosen.secondary || '' ) !== '' )
							lines.push( TEXT['/_admin/design/label/secondary']+ ' '+ chosen.secondary );
					}
					// While a second colour is set the strip of Harmony lights no position, and says nothing
					else if( knob === 'harmony' && ( chosen.secondary || '' ) !== '' ) {
					}
					else if( knob === 'mode' ) {
						if( valueOf('mode') !== given.mode )
							lines.push( TEXT['/_admin/design/colour/mode/label']+ ' '+ TEXT['/_admin/design/colour/mode/'+ valueOf('mode')] );
					}
					else if( valueOf( knob ) !== given[knob] )
						lines.push( colourWord( knob )+ ' '+ ( real.palette[knob].kind === 'scale' ? SIGNED[ valueOf( knob ) - 1 ] : TEXT['/_admin/design/colour/'+ knob+ '/'+ valueOf( knob )] ) );
				} );
			}
			else if( path[0] === 'motion' ) {
				// Strength and speed mean something once there is an effect, the strength of the hover once there is a kind of it that has one
				if( real.motion[ path[1] ] !== real.defaults.motion[ path[1] ] && ( path[1] === 'effect' || path[1] === 'hover' || ( path[1] === 'hover-strength' ? [ '', 'none' ].indexOf( real.motion.hover ) === -1 : real.motion.effect !== '' ) ) )
					lines.push( TEXT['/_admin/design/motion/'+ path[1]+ '/label']+ ' '+ TEXT['/_admin/design/motion/'+ path[1]+ '/'+ real.motion[ path[1] ]] );
			}
			else if( real[ keys[0] ] !== real.defaults[ keys[0] ] ) {
				const words = { size : 'size', width : 'pagewidth', whitespace : 'whitespace', radius : 'radius', 'radius-alt' : 'radius-alt' };
				lines.push( TEXT['/_admin/design/label/'+ words[ keys[0] ]]+ ' '+ at( real[ keys[0] ] ) );
			}
		} );

		return lines.join(' · ');
	};

	/** What the heads of an aspect say, by the object of the block, with every block closed - the objects walked, each block closed by a press on its head and read */
	const closedLines = function( tab ) {

		whole.show( tab );

		const said = {};

		tableObjects( real.layout, tab ).forEach( function( object ) { said[object] = closedLine( whole, object ) } );

		return said;
	};

	const headProblems = [];
	let headsSaid = 0;

	[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {

		const said = closedLines( tab );

		tableRows( real.layout, tab ).forEach( function( row ) {

			headsSaid += said[row.object] === '' ? 0 : 1;

			if( said[row.object] !== oracle( tab, row.object ) )
				headProblems.push( tab+ '/'+ row.object+ ': '+ said[row.object] );
		} );
	} );

	check( 'the heads of the blocks name, for the selection the server gave - another template for each of the three parts, a font changed, the width, the animation, the mode - what it moved from the defaults, as an oracle that reads the answer and the table alone has it, in every block of every tab the table draws: the knobs the template does not answer to left out, the corners of a section once, the template that is chosen in each block of the templates, the colours and the animation in the one block of their tab'
		+ ( headProblems.length === 0 ? '' : ' - not so for '+ headProblems.join(' | ') ), headProblems.length === 0 && headsSaid > 0 );

	whole.show( 'templates' );

	/** Whether the select of a part is drawn in its block on the tab that is open, which is opened for it, over what the library has, on the template that is chosen */
	const selectOf = function( row ) {
		const select = setSelect( whole, row.part );
		return select !== null && offered( select ).map( function( option ) { return option[0] } ).join() === Object.keys( row.catalogue ).join() && shown( select ) === row.set;
	};

	/** What the buttons of a strip say is pressed, the strip found by the key the table has the control under */
	const litOf = function( key ) { return byTag( controlOf( whole, key ), 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() };

	check( 'Templates is drawn from the catalogue and the choices the server gave: a block for each of the eleven parts, each with a select over what the library has, on the one that is chosen - and nothing else on the tab but the strips of the header, of the footer, of the section and of the card that the table puts there',
		real.parts.length === 11 && tableObjects( real.layout, 'templates' ).length === 11 && real.parts.every( selectOf ) && knobRows( whole ).every( function( row ) { return [ 'header', 'footer', 'section', 'card' ].indexOf( cardOf( row ).dataset.object ) !== -1 } )
		&& knobRows( whole ).map( function( row ) { return row.dataset.control } ).join() === 'knobs/header/position,knobs/header/cta,knobs/header/behaviour,knobs/footer/position,knobs/section/align,knobs/section/measure,knobs/card/align' );

	whole.show( 'type' );

	check( '...the root size of the page, a strip on the number stored, is the first row of Type\'s, and the strips of the page that are not its size stand on Surfaces',
		whole.pressed( sizeStrip ) === pressedAt( real.size ) && whole.knobRow( widthStrip ) === null && whole.knobRow( radiusStrip ) === null && whole.byId('design-motion-effect') === null );

	whole.show( 'surfaces' );

	check( '...the width and the whitespace of the page and the two radii, on the numbers stored, are the first row of Surfaces\'',
		whole.pressed( widthStrip ) === pressedAt( real.width ) && whole.pressed( whitespaceStrip ) === pressedAt( real.whitespace )
		&& whole.pressed( radiusStrip ) === pressedAt( real.radius ) && whole.pressed( radiusAltStrip ) === pressedAt( real['radius-alt'] )
		&& whole.knobRow( sizeStrip ) === null && whole.byId('design-motion-effect') === null );

	/*	The corners of each kind of surface are a strip of two words on the word stored; the cover, the row and the column of a section are one choice, read as the
		word the three agree on - or as the cover's, where they do not, with the difference said	*/
	const kindsAgree = real.radii.cover === real.radii.row && real.radii.row === real.radii.column;

	check( '...the corners the image and the card take are a strip of two words on the word stored, and the section\'s are one choice for the cover, the row and the column: on the word the three have stored, or on the cover\'s where they differ - and then it says so',
		[ 'image', 'card' ].every( function( kind ) { return litOf( 'radii/'+ kind ) === RADIUS_WORDS.map( function( word ) { return String( word === real.radii[kind] ) } ).join() } )
		&& litOf( 'radii/cover' ) === RADIUS_WORDS.map( function( word ) { return String( word === real.radii.cover ) } ).join()
		&& controlOf( whole, 'radii/cover' ).dataset.control === 'radii/cover radii/row radii/column'
		&& ( byTag( controlOf( whole, 'radii/cover' ), 'small' )[0].textContent === ( kindsAgree === true ? '' : TEXT['/_admin/design/hint/radii'] ) ) );

	whole.show( 'motion' );

	check( '...and the animation on the values stored is the Motion view\'s',
		shown( whole.byId('design-motion-effect') ) === real.motion.effect && shown( whole.byId('design-motion-strength') ) === real.motion.strength
		&& shown( whole.byId('design-motion-speed') ) === real.motion.speed && shown( whole.byId('design-motion-hover') ) === real.motion.hover && shown( whole.byId('design-motion-hover-strength') ) === real.motion['hover-strength']
		&& offered( whole.byId('design-motion-hover') ).map( function( option ) { return option[0] } ).join() === [ '' ].concat( real.hovers ).join() && whole.byId('design-motion-hover-strength').disabled === ( [ '', 'none' ].indexOf( real.motion.hover ) !== -1 )
		&& offered( whole.byId('design-motion-effect') ).map( function( option ) { return option[0] } ).join() === [ '' ].concat( real.effects ).join()
		&& whole.byId('design-motion-strength').disabled === ( real.motion.effect === '' ) && whole.knobRow( radiusStrip ) === null );

	whole.show( 'type' );

	check( 'Type\'s row of the page is drawn from the roles, the families each role is offered, the bounds of a size and the fonts that are stored: a row of the table for each role, every control on the stored value',
		byTag( whole.form, 'tr' ).filter( function( row ) { return row.dataset.role !== undefined } ).map( function( row ) { return row.dataset.role } ).join() === real.roles.join()
		&& real.roles.every( function( role ) {
			const chosen = real.fonts[role];
			return offered( whole.byId('design-font-'+ role+ '-family') ).map( function( option ) { return option[0] } ).join() === real.options[role].join()
				&& shown( whole.byId('design-font-'+ role+ '-family') ) === chosen.family && shown( whole.byId('design-font-'+ role+ '-weight') ) === chosen.weight
				&& whole.byId('design-font-'+ role+ '-size').value === ( chosen.size || real.inherited[role] )
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('min') === String( real.bounds[role][0] ) && whole.byId('design-font-'+ role+ '-size').getAttribute('max') === String( real.bounds[role][1] )
				&& whole.byId('design-font-'+ role+ '-size').getAttribute('placeholder') === null
				&& Object.keys( chosen ).join() === 'family,weight,size';
		} ) );

	/*	Every knob of the table has its strip, on the tab and in the row the table gives it: five buttons for a scale, three for a choice, lit on the position stored - and grey
		where the template chosen for its part does not answer to it	*/
	const tabProblems = [];
	let knobsDrawn = 0;

	[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {

		whole.show( tab );

		knobRows( whole ).forEach( function( row ) {

			const path = row.dataset.control.split('/');
			const part = rowOf( path[1] );
			const off = ( ( part.catalogue[part.set] || {} ).knobs || [] ).indexOf( path[2] ) === -1;
			const kind = real.kinds[ path[1] ][ path[2] ];
			const reach = kind === 'scale' ? [ -2, -1, 0, 1, 2 ] : ( kind === 'grade' ? [ 0, 1, 2, 3, 4 ] : [ -1, 0, 1 ] );
			const lit = reach.map( function( at ) { return String( at === real.knobs[ path[1] ][ path[2] ] ) } ).join();

			knobsDrawn++;

			if( real.layout.controls[ row.dataset.control ][0] !== tab )
				tabProblems.push( row.dataset.control+ ': not on '+ tab );
			if( hasClass( row, 'design-knob-row--off' ) !== off )
				tabProblems.push( row.dataset.control+ ': grey is '+ String( hasClass( row, 'design-knob-row--off' ) ) );

			// A toggle is a switch and no strip: on at the position 1, and switched off together with its row where the template does not answer to it
			if( kind === 'toggle' ) {

				const input = byTag( row, 'input' )[0];

				if( isSwitch( row ) === false || byTag( row, 'button' ).length !== 0 || input === undefined || input.type !== 'checkbox' )
					tabProblems.push( row.dataset.control+ ': not a switch' );
				else if( input.disabled !== off || input.checked !== ( off === false && real.knobs[ path[1] ][ path[2] ] === 1 ) )
					tabProblems.push( row.dataset.control+ ': the switch does not stand at the position stored' );

				return;
			}

			// An ink is a select of the tones of the page, at the tone stored - none is the tone the template gives - and switched off with its row
			if( kind === 'ink' ) {

				const select = byTag( row, 'select' )[0];

				if( isSelect( row ) === false || byTag( row, 'button' ).length !== 0 || select === undefined )
					tabProblems.push( row.dataset.control+ ': not a select' );
				else if( select.disabled !== off || shown( select ) !== real.knobs[ path[1] ][ path[2] ] )
					tabProblems.push( row.dataset.control+ ': the select does not stand at the tone stored' );

				return;
			}

			if( off === false && byTag( row, 'button' ).map( function( button ) { return button.getAttribute('aria-pressed') } ).join() !== lit )
				tabProblems.push( row.dataset.control+ ': a step is lit that the knob does not stand at' );
			if( byTag( row, 'button' ).length !== reach.length )
				tabProblems.push( row.dataset.control+ ': '+ byTag( row, 'button' ).length+ ' buttons' );
		} );
	} );

	check( 'each knob is a strip in the row the table gives it - or a switch, for a toggle -, grey where the template chosen for its part does not answer to it, and the others lit on the position stored - on five buttons for a scale, three for a choice'
		+ ( tabProblems.length === 0 ? '' : ' - '+ tabProblems.join(' | ') ),
		tabProblems.length === 0 && knobsDrawn === Object.keys( real.layout.controls ).filter( function( key ) { return key.indexOf('knobs/') === 0 } ).length && knobsDrawn > 0 );
	check( '...and every template the library ships has a name and answers to knobs of its own part, in the order of the part - a template that leaves one out is a grey row for it, as the check above holds - and the one that is chosen is one of the catalogue',
		Object.keys( real.kinds ).every( function( part ) {
			const order = Object.keys( real.kinds[part] );
			return Object.keys( rowOf( part ).catalogue ).length > 0 && Object.keys( rowOf( part ).catalogue ).every( function( set ) {
				const entry = rowOf( part ).catalogue[set];
				return typeof entry.name === 'string' && entry.name !== '' && entry.knobs.every( function( knob ) { return order.indexOf( knob ) !== -1 } )
					&& entry.knobs.join() === order.filter( function( knob ) { return entry.knobs.indexOf( knob ) !== -1 } ).join();
			} );
		} )
		&& real.parts.every( function( row ) { return Object.keys( row.catalogue ).indexOf( row.set ) !== -1 } ) );
	check( '...a frame is asked as a set is: the catalogue of the header and of the footer carries a list of knobs for every template',
		real.parts.length === 11 && real.parts.map( function( row ) { return row.part } ).join() === Object.keys( real.kinds ).join()
		&& [ 'header', 'footer' ].every( function( part ) {
			return rowOf( part ).kind === 'frame' && Object.keys( rowOf( part ).catalogue ).length > 0 && Object.keys( rowOf( part ).catalogue ).every( function( set ) {
				return Array.isArray( rowOf( part ).catalogue[set].knobs );
			} );
		} ) );

	whole.tab( tabWord('colours') );

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
		sent.endpoint === 'preview' && Object.keys( sent.payload ).sort().join() === 'colours,css,features,fonts,full,knobs,motion,navs,parts,radii,radius,radius-alt,size,styles,theme,tiers,whitespace,width'
		&& JSON.stringify( sent.payload.parts ) === JSON.stringify( ( function() { const parts = {}; real.parts.forEach( function( row ) { parts[row.part] = row.set } ); return parts } )() )
		&& JSON.stringify( sent.payload.knobs ) === JSON.stringify( real.knobs ) && JSON.stringify( sent.payload.fonts ) === JSON.stringify( real.fonts )
		&& JSON.stringify( sent.payload.motion ) === JSON.stringify( real.motion ) && sent.payload.size === real.size && sent.payload.width === real.width && sent.payload.whitespace === real.whitespace
		&& sent.payload.radius === real.radius && sent.payload['radius-alt'] === real['radius-alt'] && JSON.stringify( sent.payload.radii ) === JSON.stringify( real.radii )
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

		typed.show( 'typefaces' );
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
			&& faceRows( availableCard( typed ) ).length === Math.min( shelved.families.length, typed.faces.FACES_PER_PAGE ) );
		check( '...with who uses what: a family a role of the saved setup uses has its Remove off, and the others have it on',
			slugs.every( function( slug ) { return faceButton( faceRowOf( installedCard( typed ), slug ) ).disabled === ( ( shelved.uses[slug] || [] ).length > 0 ) } )
			&& slugs.some( function( slug ) { return ( shelved.uses[slug] || [] ).length > 0 } ) );
		check( '...the name of an installed family is set in the file the server named for it - a url of the project\'s own - and every word on the screen is one the panel\'s texts carry',
			slugs.every( function( slug, at ) { return typed.typeset.made[at].source === 'url("'+ shelved.installed[slug].face+ '")' && shelved.installed[slug].face.indexOf('[[') === -1 } )
			&& unwritten( typed ).length === 0 );
	}
}

console.log('');

// --- What the actions of the themes really answered ----------------------------------------------------
//
// design-smoke.php writes down what the server answered to each of them - the list with and without an
// active theme, a theme loaded and one that names typefaces the project has not installed, and the
// answers of a rename, a save, a duplicate, a delete, an export and an import - and hands the file to
// this run (DESIGN_THEMES). The stand-in above is what the tab is drawn from in the checks before; this
// is the half that keeps it honest: the same tab, drawn from, and driven by, the answers of the server

console.log( 'The answers of the themes' );

const themesFile = process.env.DESIGN_THEMES || '';

if( themesFile === '' || fs.existsSync( themesFile ) === false )
	console.log( '  --  no answers of the themes were handed to this run (DESIGN_THEMES), so the screen is not drawn from the real ones' );
else {

	const answered = JSON.parse( fs.readFileSync( themesFile, 'utf8' ) );
	const none = answered.none;
	const activeList = answered.active;
	const wholeTab = function( list, mode ) {
		const screen = panel( list, { whole : true, screen : mode === 'setup' ? undefined : 'themes' } );
		return screen;
	};
	const listed = function( themes ) { return themes.map( function( theme ) { return theme.slug } ).join() };

	check( 'the answers are in the shape the tab reads: the list gives the theme the setup names, its css, the themes and the active one beside everything else; a load the theme with its setup, its css, the typefaces missing and the notes; a rename, a save, a duplicate, a delete, an export and an import what the tab takes over',
		none.theme === '' && none.css === '' && none.active === null && Array.isArray( none.themes ) && none.themes.length > 0 && none.themes[0].shipped === true
		&& none.themes.every( function( theme ) { return /^[a-z0-9]+(-[a-z0-9]+)*$/.test( theme.slug ) && typeof theme.name === 'string' && theme.name !== '' && typeof theme.description === 'string' } )
		&& Object.keys( none.themes[0] ).join() === 'slug,name,description,shipped' && Object.keys( activeList.active ).join() === 'slug,name,description,shipped,setup,css'
		&& Object.keys( activeList.active.setup ).join() === 'size,width,whitespace,radius,radius-alt,radii,motion,parts,navs,knobs,styles,features,fonts,colours'
		&& Object.keys( answered.load ).join() === 'slug,name,description,shipped,setup,css,missing,notes' && answered.load.shipped === true && Object.keys( answered.load.setup ).join() === 'size,width,whitespace,radius,radius-alt,radii,motion,parts,navs,knobs,styles,features,fonts'
		&& answered.load.notes.every( function( note ) { return typeof note === 'string' || ( typeof note.error === 'string' && typeof note.code === 'string' && Array.isArray( note.params ) ) } )
		&& typeof none.running === 'string' && typeof activeList.running === 'string'
		&& Object.keys( answered.loadMissing.missing[0] ).join() === 'slug,name,inList'
		&& Object.keys( answered.renamed ).join() === 'themes,active' && Object.keys( answered.saved ).join() === 'themes,active' && Object.keys( answered.duplicated ).join() === 'slug,themes'
		&& Object.keys( answered.deleted ).join() === 'themes,theme' && Object.keys( answered.exported ).join() === 'json,file' && Object.keys( answered.imported ).join() === 'slug,themes' );

	check( 'the tables the script draws the tablet and the phone from are the server\'s: the stand-in of this file says what design/list says of them, and what it says is set - an empty list for each tier, whichever the selection',
		JSON.stringify( none.tierSpec ) === JSON.stringify( TIER_SPEC ) && JSON.stringify( none.tiers ) === JSON.stringify( { m : [], s : [] } ) && none.defaults.tiers === undefined
		&& Object.keys( none.tierSpec ).join() === 'tiers,controls,positions,sizes,margins,gaps,widths,blocks' );

	// The list with no theme
	const noneTab = wholeTab( none );

	check( 'the tab is drawn from the list with no theme: a row for each theme, shipped, in the order given, with the name and the description the server wrote in the language of the workbench; none is active, none can be deleted or renamed, each has the four buttons it can have',
		slugsOf( noneTab ) === listed( none.themes ) && none.themes.every( function( theme ) {
			const row = themeRow( noneTab, theme.slug );
			return textsOf( row, 'design-theme-name' ) === theme.name && textsOf( row, 'design-theme-description' ) === theme.description && byClass( row, 'design-theme-shipped' ).length === 1 && themeButtons( noneTab, theme.slug ).length === 4 && noneTab.byId('design-theme-rename-'+ theme.slug ) === null;
		} )
		&& byClass( noneTab.form, 'design-theme-active' ).length === 0 && noneTab.design._active === null );

	// The list with a theme of the project active - the one the website runs as well, which the list says by the theme of the last apply
	const activeTab = wholeTab( Object.assign( {}, activeList, { running : activeList.active.slug } ) );
	const activeSlug = activeList.active.slug;

	check( 'drawn from the list with a theme of the project active: the shipped ones first and that one after them, with the chip and the mark, a Delete and a pencil on it alone; its row says its name and its description',
		slugsOf( activeTab ) === listed( activeList.themes ) && activeList.themes[0].shipped === true
		&& ( function() {
			const flags = activeList.themes.map( function( theme ) { return theme.shipped } );
			return flags.indexOf( false ) === -1 || flags.slice( flags.indexOf( false ) ).every( function( flag ) { return flag === false } );
		} )()
		&& hasClass( themeRow( activeTab, activeSlug ), 'design-theme-row--active' ) && byClass( activeTab.form, 'design-theme-row--active' ).length === 1 && textsOf( themeRow( activeTab, activeSlug ), 'design-theme-active' ) === TEXT['/_admin/design/theme/active']
		&& activeList.themes.every( function( theme ) { return ( activeTab.byId('design-theme-delete-'+ theme.slug) !== null ) === ( theme.shipped === false ) } )
		&& textsOf( themeRow( activeTab, activeSlug ), 'design-theme-name' ) === activeList.active.name && textsOf( themeRow( activeTab, activeSlug ), 'design-theme-description' ) === activeList.active.description
		&& activeTab.byId('design-theme-rename-'+ activeSlug ) !== null );
	check( '...the css field on Custom CSS holds the css the setup carries, which is not the theme\'s: the chip on the button of Themes says the screen differs from the theme; the css of the theme in its place and the chip goes - the setup the list gives and the setup of the theme\'s file are the same, to the last key',
		( activeTab.show('css'), activeTab.byId('design-theme-css').value === activeList.css ) && activeList.css !== activeList.active.css
		&& activeTab.design._themeChanged() === true && tabChips( activeTab ).length === 1
		&& ( activeTab.design._edit.css = activeList.active.css, activeTab.design._themeChanged() ) === false
		&& JSON.stringify( activeTab.design._canonical( activeTab.design._snapshotOf( activeTab.design._edit ) ) ) === JSON.stringify( activeTab.design._canonical( activeList.active.setup ) ) );

	// Load
	const loaded = wholeTab( none );
	loaded.byId('design-theme-load-'+ answered.load.slug).fire('click');
	loaded.answer( 200, answered.load );

	const offered = function( screen, role ) { return screen.design._data.options[role] };

	check( 'a theme the server loaded is the working copy as it came: no question, the templates, the knobs, the size and the width, the animation as the file holds them - and the palette as the screen has it, for the file of a theme the feature ships names none - and of the fonts the families the roles are offered, the others the face of Nino',
		loaded.questions().length === 0 && loaded.requests.length === 0 && loaded.design._edit.theme === answered.load.slug && loaded.design._edit.css === answered.load.css
		&& JSON.stringify( loaded.design._edit.parts ) === JSON.stringify( answered.load.setup.parts ) && JSON.stringify( loaded.design._edit.knobs ) === JSON.stringify( answered.load.setup.knobs )
		&& loaded.design._edit.size === answered.load.setup.size && loaded.design._edit.width === answered.load.setup.width && loaded.design._edit.whitespace === answered.load.setup.whitespace && JSON.stringify( loaded.design._edit.motion ) === JSON.stringify( answered.load.setup.motion )
		&& loaded.design._edit.radius === answered.load.setup.radius && loaded.design._edit['radius-alt'] === answered.load.setup['radius-alt'] && JSON.stringify( loaded.design._edit.radii ) === JSON.stringify( answered.load.setup.radii )
		&& Object.prototype.hasOwnProperty.call( answered.load.setup, 'colours' ) === false && JSON.stringify( loaded.design._edit.colours ) === JSON.stringify( none.colours )
		&& Object.keys( answered.load.setup.fonts ).every( function( role ) { return loaded.design._edit.fonts[role].family === ( offered( loaded, role ).indexOf( answered.load.setup.fonts[role].family ) !== -1 ? answered.load.setup.fonts[role].family : '' ) } ) );
	check( '...it is the active theme - shipped, so it has no pencil - as it was taken over, so the screen is the theme and there is no chip; the way back is shown, the bar says it was loaded and the shortcodes of the css were not touched',
		loaded.design._active.slug === answered.load.slug && loaded.design._active.shipped === true && loaded.design._themeChanged() === false && loaded.design._isDirty() === true && loaded.byId('design-reset').hidden === false
		&& loaded.line().textContent.indexOf( say( '/_admin/design/theme/msg/loaded', answered.load.name ) ) === 0
		&& tabChips( loaded ).length === 0 && hasClass( themeRow( loaded, answered.load.slug ), 'design-theme-row--active' ) && loaded.byId('design-theme-rename-'+ answered.load.slug ) === null
		&& ( loaded.show('css'), loaded.byId('design-theme-css').value === answered.load.css ) );

	loaded.tab( tabWord('templates') );
	check( '...and the other tabs show it: the template of the cards is the theme\'s',
		shown( setSelect( loaded, 'card' ) ) === answered.load.setup.parts.card );

	// A theme that names typefaces the project has not installed
	const withMissing = wholeTab( Object.assign( {}, none, { themes : answered.renamed.themes } ) );
	const askable = answered.loadMissing.missing.filter( function( entry ) { return entry.inList === true } );
	withMissing.byId('design-theme-load-'+ answered.loadMissing.slug).fire('click');
	withMissing.answer( 200, answered.loadMissing );
	withMissing.settle();

	check( 'a theme that names typefaces the project has not installed asks for those the list has, by the name the list gives them - the one it does not have is not asked for - and a yes posts the install of the first',
		askable.length === 1 && answered.loadMissing.missing.length === 2 && withMissing.questions().length === 1 && withMissing.questions()[0] === say( '/_admin/design/theme/confirm/fonts', askable.map( function( entry ) { return entry.name } ).join(', ') )
		&& withMissing.requests.length === 1 && withMissing.requests[0].endpoint === 'webfonts/install' && withMissing.requests[0].payload.slug === askable[0].slug );

	// Rename, save, duplicate, delete, export and import
	const renamed = wholeTab( activeList );
	renamed.byId('design-theme-rename-'+ activeList.active.slug ).fire('click');
	renamed.byId('design-theme-name').value = answered.renamed.active.name;
	renamed.byId('design-theme-name').fire('input');
	renamed.byId('design-theme-rename-save').fire('click');
	renamed.answer( 200, answered.renamed );

	check( 'the answer of a rename is the list with the theme under its new words and the theme as the server holds it: the fields are closed, the row says the name and the description the server wrote, and the rows are the list',
		renamed.design._themeRename === '' && textsOf( themeRow( renamed, answered.renamed.active.slug ), 'design-theme-name' ) === answered.renamed.active.name
		&& textsOf( themeRow( renamed, answered.renamed.active.slug ), 'design-theme-description' ) === answered.renamed.active.description && slugsOf( renamed ) === listed( answered.renamed.themes )
		&& renamed.line().textContent === say( '/_admin/design/theme/msg/renamed', answered.renamed.active.name ) );

	const saved = wholeTab( activeList, 'setup' );
	saved.byId('design-theme-save').fire('click');
	saved.answer( 200, answered.saved );

	check( 'the answer of a save: the list, and the theme as it is now - the name and the description the file has, the chip as the setup of the file against the screen says',
		saved.design._active.slug === answered.saved.active.slug && saved.design._active.name === answered.saved.active.name && saved.design._active.description === answered.saved.active.description
		&& saved.line().textContent === say( '/_admin/design/theme/msg/saved', answered.saved.active.name ) && ( saved.back(), slugsOf( saved ) === listed( answered.saved.themes ) ) );

	const copied = wholeTab( answered.renamed.themes.length > 0 ? Object.assign( {}, none, { themes : answered.renamed.themes } ) : none );
	copied.byId('design-theme-duplicate-'+ none.themes[0].slug).fire('click');
	copied.answer( 200, answered.duplicated );

	check( 'the answer of a duplicate: the list with the copy in it, which has all five buttons, a project theme; nothing is loaded and the bar names the theme and its copy',
		slugsOf( copied ) === listed( answered.duplicated.themes ) && themeButtons( copied, answered.duplicated.slug ).length === 5 && copied.design._edit.theme === ''
		&& copied.line().textContent === say( '/_admin/design/theme/msg/duplicated', none.themes[0].name,
			answered.duplicated.themes.filter( function( theme ) { return theme.slug === answered.duplicated.slug } )[0].name ) );

	const deleted = wholeTab( activeList );
	deleted.byId('design-theme-delete-'+ activeSlug).fire('click');
	deleted.settle();
	deleted.answer( 200, answered.deleted );

	check( 'the answer of a delete: the list as the server has it, and the theme the setup names now - none, since it was the one deleted: the screen has no active theme',
		slugsOf( deleted ) === listed( answered.deleted.themes ) && answered.deleted.theme === '' && deleted.design._edit.theme === '' && deleted.design._data.theme === '' && deleted.design._active === null
 );

	const exported = wholeTab( none );
	exported.byId('design-theme-export-'+ answered.load.slug).fire('click');
	exported.answer( 200, answered.exported );

	check( 'the answer of an export is the text of the file and the name to save it under: a blob of the text as it is, a link with that name - and the request was for the theme of the row',
		exported.blobs.length === 1 && exported.blobs[0].parts[0] === answered.exported.json && /^nino-theme-[a-z0-9-]+\.json$/.test( answered.exported.file ) && exported.downloads.length === 1
		&& exported.downloads[0].download === answered.exported.file && JSON.parse( answered.exported.json ).format === 1 );

	const imported = wholeTab( none );
	imported.byId('design-theme-import').files = [ { name : answered.exported.file } ];
	imported.byId('design-theme-import').fire('change');
	imported.readers[0].finish( answered.exported.json );
	imported.answer( 200, answered.imported );

	check( 'the text of an export is posted as it is, whole, and the answer of the import is the list with the theme in it - not loaded',
		imported.requests.length === 0 && slugsOf( imported ) === listed( answered.imported.themes ) && imported.design._edit.theme === '' && themeButtons( imported, answered.imported.slug ).length === 5 );

	check( 'every word the tab shows, in both of its modes, is one the panel\'s texts carry',
		[ noneTab, activeTab, loaded, renamed, deleted ].every( function( screen ) {
			return [ 'theme', 'type' ].every( function( key ) {
				screen.show( key );
				return descendants( screen.pane ).every( function( el ) {
					return [ el.textContent, el.getAttribute('title'), el.getAttribute('aria-label') ].every( function( text ) { return typeof text !== 'string' || text.indexOf('/_admin/') !== 0 } );
				} );
			} );
		} ) );
}

console.log('');

// --- The styles of a feature ---------------------------------------------------------------------
//
// A feature declares styles once, for all its variants (Setup::featureStyles()): the select of each stands in the block of the feature on Templates, under the select
// of its variant and the description of it, "None" first and the options after it by the names their files give them. A choice is the feature's, a key `styles` of its
// entry in the working copy, goes with every request the selection goes with, and a change of the variant leaves it where it is. The frame goes to the block of the
// feature (F05's anchor), and the head of the closed block names the options chosen after the variant

console.log( 'The styles of a feature' );

const fsDefs = featured().features.awning.styles;
const fsKeys = Object.keys( fsDefs );
const fst = panel( featured() );
// The block of the feature is opened for it: a closed block has drawn nothing yet
const fsField = function( screen, style ) { fxControl( screen, 'awning' ); return screen.byId( 'design-style-awning-'+ style ) };
const fsBody = function( screen ) { return byClass( cardOf( fxSelect( screen, 'awning' ) ), 'design-accordion-body' )[0] };
const fsSelects = function( screen ) { return byClass( fsBody( screen ), 'design-style-select' ) };
const fsLast = function( screen ) { return screen.requests[screen.requests.length - 1] };

check( 'a feature that declares styles has a select for each in its block, in the order its head declares them, under the select of its variant: no control of the table, so none of them stands for a key - and a feature that declares none has none',
	fsKeys.join() === 'look,edge' && fsSelects( fst ).length === 2 && fsKeys.every( function( style, at ) { return fsSelects( fst )[at] === fsField( fst, style ).parent } )
	&& fsBody( fst ).children.indexOf( fsSelects( fst )[0] ) === fsBody( fst ).children.indexOf( fxSelect( fst, 'awning' ).parent ) + 1 && fsBody( fst ).children.indexOf( fsSelects( fst )[1] ) === fsBody( fst ).children.indexOf( fsSelects( fst )[0] ) + 1
	&& fsSelects( fst ).every( function( field ) { return field.dataset.control === undefined && hasClass( field, 'design-field' ) } )
	&& tableRows( fst.design._data.layout, 'templates' ).every( function( row ) { return row.controls.every( function( key ) { return key.indexOf('styles') === -1 } ) } )
	&& byClass( fxBlock( fst, 'mosaic' ), 'design-style-select' ).length === 0 );
check( '...each headed with the label its head gives the style, over a select that has None first - the word the texts have for it - and the options after it by the names their files give them, in the order of the answer: it stands on None with no line under it, and a style whose files are none offers None alone',
	fsKeys.every( function( style ) {
		const select = fsField( fst, style );
		const options = fsDefs[style].options;
		return select !== null && JSON.stringify( offered( select ) ) === JSON.stringify( [ [ '', styNone ] ].concat( Object.keys( options ).map( function( option ) { return [ option, options[option].name ] } ) ) )
			&& shown( select ) === '' && byTag( select.parent, 'label' )[0].textContent === fsDefs[style].label && byTag( select.parent, 'label' )[0].getAttribute('for') === select.id
			&& byTag( select.parent, 'small' ).length === 0 && select.parent.dataset.style === style && select.id === 'design-style-awning-'+ style;
	} ) && offered( fsField( fst, 'edge' ) ).length === 1 && offered( fsField( fst, 'look' ) ).length === 3 );

fst.design._target = null;
choose( fsField( fst, 'look' ), 'soft' );

check( 'choosing an option writes it into the entry of the feature, which is chosen at the variant it stands at for it: the styles are the feature\'s and not the variant\'s - and the line under the select is the description of the option',
	JSON.stringify( fst.design._edit.features ) === JSON.stringify( { awning : { set : 'regular', knobs : {}, styles : { look : 'soft' } } } ) && fsField( fst, 'look' ).value === 'soft'
	&& byTag( fsField( fst, 'look' ).parent, 'small' )[0].textContent === fsDefs.look.options.soft.description && fst.design._isDirty() === true && fst.byId('design-reset').hidden === false );
check( '...it asks for the preview with the choices for the features, the styles in them, and the frame goes to the block of the feature: the anchor the answer gives it',
	JSON.stringify( fxLastPayload( fst ).features ) === JSON.stringify( { awning : { set : 'regular', knobs : {}, styles : { look : 'soft' } } } ) && fsLast( fst ).endpoint === 'preview' && fst.design._target === fxZinc.anchor );

choose( fsField( fst, 'look' ), 'bold' );

check( '...an option with no description has no line under its select, and a second choice is the same key with the other option',
	fst.design._edit.features.awning.styles.look === 'bold' && ( byTag( fsField( fst, 'look' ).parent, 'small' )[0] || { textContent : '' } ).textContent === '' );

choose( fsField( fst, 'look' ), 'soft' );
choose( fxSelect( fst, 'awning' ), 'loud' );

check( 'a change of the variant leaves the styles as they are: the entry has the other variant and the same option, the select stands on it, and the next preview carries both',
	JSON.stringify( fst.design._edit.features.awning ) === JSON.stringify( { set : 'loud', knobs : {}, styles : { look : 'soft' } } ) && fsField( fst, 'look' ).value === 'soft' && fsSelects( fst ).length === 2
	&& JSON.stringify( fxLastPayload( fst ).features.awning ) === JSON.stringify( { set : 'loud', knobs : {}, styles : { look : 'soft' } } ) );

choose( fxSelect( fst, 'awning' ), 'regular' );

check( '...and the variant the feature starts from, chosen again, is no choice for the variant alone: the style keeps the entry, which then says the variant it has before anything is chosen',
	JSON.stringify( fst.design._edit.features.awning ) === JSON.stringify( { set : 'regular', knobs : {}, styles : { look : 'soft' } } ) && fst.design._isDirty() === true );

check( 'the head of the closed block names the option of each style chosen after the variant, as the select shows it - the options alone while the variant is the one the feature starts from',
	fxSaid( fst, 'templates', 'awning' ) === fsDefs.look.options.soft.name && ( choose( fxSelect( fst, 'awning' ), 'loud' ), fxSaid( fst, 'templates', 'awning' ) === [ fxZinc.catalogue.loud.name, fsDefs.look.options.soft.name ].join(' · ') ) );

choose( fsField( fst, 'look' ), '' );

check( 'None is the feature as it is: the style has no entry then, and with the variant at the one the feature starts from the entry is gone, the screen is the stored one and the line under the select is gone',
	JSON.stringify( fst.design._edit.features.awning ) === JSON.stringify( { set : 'loud', knobs : {}, styles : {} } ) && fsField( fst, 'look' ).value === '' && ( byTag( fsField( fst, 'look' ).parent, 'small' )[0] || { textContent : '' } ).textContent === ''
	&& ( choose( fxSelect( fst, 'awning' ), 'regular' ), fxFeatures( fst ) === '{}' && fst.design._isDirty() === false && fst.byId('design-reset').hidden === true ) );

choose( fsField( fst, 'look' ), 'bold' );
fst.byId('design-save').fire('click');

check( 'the save posts the styles of the feature with the rest of the selection, and the way back puts the stored selection back: no style chosen, the select on None',
	fsLast( fst ).endpoint === 'save' && JSON.stringify( fsLast( fst ).payload.features.awning.styles ) === JSON.stringify( { look : 'bold' } )
	&& ( fst.byId('design-reset').fire('click'), fxFeatures( fst ) === '{}' && shown( fsField( fst, 'look' ) ) === '' && fst.design._isDirty() === false ) );

// A stored choice is on the screen: the answer says the styles chosen for the feature
const fsStored = panel( featured( { awning : { set : 'loud', knobs : {}, styles : { look : 'bold', gone : 'x' } } } ) );

check( 'a stored choice is drawn as the answer says it: the select stands on the option, the screen is the stored selection with nothing to go back to, the head names the variant and the option - and a style the answer holds that the feature does not declare is not said and has no select',
	shown( fsField( fsStored, 'look' ) ) === 'bold' && fsStored.design._isDirty() === false && fsStored.byId('design-reset').hidden === true && fsStored.byId('design-style-awning-gone') === null
	&& fxSaid( fsStored, 'templates', 'awning' ) === [ fxZinc.catalogue.loud.name, fsDefs.look.options.bold.name ].join(' · ') );

// A theme carries them: the entry of the feature in its setup has the styles
const fsTheme = themeSetup();
fsTheme.features = { awning : { set : 'regular', knobs : {}, styles : { look : 'soft' } } };

const fsThemed = themed( featured() );
fsThemed.byId('design-theme-load-magazine').fire('click');
fsThemed.answer( 200, themeLoad( 'magazine', fsTheme, [] ) );

check( 'a theme that names styles for a feature takes them over with the entry, and its record has them: the compare reads them again, and a style moved afterwards is a change of it',
	JSON.stringify( fsThemed.design._edit.features ) === JSON.stringify( fsTheme.features ) && JSON.stringify( fsThemed.design._active.setup.features ) === JSON.stringify( fsTheme.features )
	&& fsThemed.design._themeChanged() === false && ( fsThemed.design._edit.features.awning.styles.look = 'bold', fsThemed.design._themeChanged() ) === true );

// The answer of the server with a feature that declares styles
const featureStylesFile = process.env.DESIGN_FEATURE_STYLES || '';

if( featureStylesFile === '' || fs.existsSync( featureStylesFile ) === false )
	console.log( '  --  no answer of design/list with a feature that declares styles was handed to this run (DESIGN_FEATURE_STYLES), so the selects of the styles of a feature are not drawn from the real one' );
else {

	const realFs = JSON.parse( fs.readFileSync( featureStylesFile, 'utf8' ) );
	const realKey = Object.keys( realFs.features ).filter( function( key ) { return realFs.features[key].active === true && Object.keys( realFs.features[key].styles ).length > 1 } )[0];
	const realFeature = realFs.features[realKey];
	const realStyleKeys = Object.keys( realFeature.styles );
	const realFsScreen = panel( realFs );
	const realFsField = function( style ) { return realFsScreen.byId( 'design-style-'+ realKey+ '-'+ style ) };

	fxControl( realFsScreen, realKey );

	check( 'the server\'s answer is in the shape the screen draws: every feature brings its styles as a list or an object of styles with a label and the options with a name and a description, the ones chosen apart from them, in `picked` - and no file of the server in them',
		Object.keys( realFs.features ).every( function( key ) {
			const feature = realFs.features[key];
			return feature.styles !== undefined && feature.picked !== undefined && ( Array.isArray( feature.styles ) ? feature.styles.length === 0 : Object.keys( feature.styles ).every( function( style ) {
				return /^[a-z][a-z0-9]*(-[a-z0-9]+)*$/.test( style ) && typeof feature.styles[style].label === 'string' && feature.styles[style].label !== '' && Object.keys( feature.styles[style] ).join() === 'label,options'
					&& ( Array.isArray( feature.styles[style].options ) ? true : Object.keys( feature.styles[style].options ).every( function( option ) { return Object.keys( feature.styles[style].options[option] ).join() === 'name,description' && typeof feature.styles[style].options[option].name === 'string' } ) );
			} ) );
		} ) && realStyleKeys.length >= 2 && JSON.stringify( realFs.features ).indexOf('"file"') === -1 );
	check( '...the screen draws a select for each style of the feature - None first, then the options in the order of the answer - on the option the setup chose, with the line of its description under it, in the block of the feature',
		realStyleKeys.every( function( style ) {
			const chosen = ( Array.isArray( realFeature.picked ) ? {} : realFeature.picked )[style] || '';
			return realFsField( style ) !== null && JSON.stringify( offered( realFsField( style ) ).map( function( pair ) { return pair[0] } ) ) === JSON.stringify( [ '' ].concat( Object.keys( realFeature.styles[style].options ) ) )
				&& offered( realFsField( style ) )[0][1] === styNone && shown( realFsField( style ) ) === chosen && byTag( realFsField( style ).parent, 'label' )[0].textContent === realFeature.styles[style].label
				&& ( byTag( realFsField( style ).parent, 'small' )[0] || { textContent : '' } ).textContent === ( chosen === '' ? '' : realFeature.styles[style].options[chosen].description )
				&& cardOf( realFsField( style ) ) === fxBlock( realFsScreen, realKey );
		} ) );

	// Every option of every style is chosen once, and the preview is asked for with it, the frame going to the block of the feature
	const realFsProblems = [];

	realStyleKeys.forEach( function( style ) {
		Object.keys( realFeature.styles[style].options ).forEach( function( option ) {
			realFsScreen.design._target = null;
			choose( realFsField( style ), option );
			realFsScreen.design._previewNow();
			const asked = realFsScreen.requests[realFsScreen.requests.length - 1];

			if( asked.payload.features[realKey].styles[style] !== option || realFsScreen.design._edit.features[realKey].styles[style] !== option || realFsScreen.design._target !== realFeature.anchor )
				realFsProblems.push( style+ '/'+ option );
		} );
	} );

	check( 'each option of each style the server brings for a feature can be chosen, and goes with the preview as it is named, to the block of the feature'+ ( realFsProblems.length === 0 ? '' : ' - not so for '+ realFsProblems.join() ), realFsProblems.length === 0 && realStyleKeys.length >= 2 && realFeature.anchor !== '' );
}

console.log('');

// --- The answer of design/list with a feature in it --------------------------------------------------
//
// The server's own answer for a project with an active feature that brings variants - the fixture of the PHP suite, with a choice for it and a choice kept for a
// feature that is not there: the screen drawn from it, and nothing but it. The features, their variants and knobs are whatever the answer says; the checks hold the
// screen to the answer and the answer to the shape the screen reads

console.log( 'The answer of design/list with a feature' );

const featuresFile = process.env.DESIGN_FEATURES || '';

if( featuresFile === '' || fs.existsSync( featuresFile ) === false )
	console.log( '  --  no answer of design/list with a feature in it was handed to this run (DESIGN_FEATURES), so the blocks of the features are not drawn from the real one' );
else {

	const realFx = JSON.parse( fs.readFileSync( featuresFile, 'utf8' ) );
	const kinds = [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
	const live = Object.keys( realFx.features ).filter( function( key ) { return realFx.features[key].active === true } );
	const kept = Object.keys( realFx.features ).filter( function( key ) { return realFx.features[key].active === false } );
	const realScreen = panel( realFx, { whole : true } );
	const hex = /^#[0-9a-f]{6}$/i;
	const range = { scale : 2, choice : 1 };

	/** What the buttons of a strip say is pressed when it stands at a position */
	const litAt = function( kind, position ) {
		const out = [];
		for( let at = 0; at <= range[kind] * 2; at++ )
			out.push( String( at === position + range[kind] ) );
		return out.join();
	};
	const entryOf = function( key ) {
		const feature = realFx.features[key];
		return { set : feature.set === '' ? feature['default'] : feature.set, knobs : Array.isArray( feature.knobs ) === true ? {} : feature.knobs };
	};
	const knobsOf = function( key ) {
		const catalogue = realFx.features[key].catalogue;
		return Object.keys( catalogue ).reduce( function( all, set ) { return Object.keys( catalogue[set].knobs ).map( function( knob ) { return [ set, knob, catalogue[set].knobs[knob] ] } ).concat( all ) }, [] );
	};

	check( 'the server\'s answer with a feature in it is in the shape the screen draws: the features that are active have a name, a variant they start from and the variants with their names, descriptions and knobs - each knob of one of the five kinds, on a tab the table draws, with a label and a position, a surface, a tone or a pair of colours it stands at before anything is chosen',
		live.length >= 1 && live.every( function( key ) {
			const feature = realFx.features[key];
			return typeof feature.name === 'string' && feature.name !== '' && typeof feature.anchor === 'string' && ( feature.anchor === '' || /^feature-[a-z][a-z0-9-]*$/.test( feature.anchor ) === true )
				&& feature.catalogue[ feature['default'] ] !== undefined && Object.keys( feature.catalogue ).length >= 1
				&& Object.keys( feature.catalogue ).every( function( set ) {
					const variant = feature.catalogue[set];
					return typeof variant.name === 'string' && variant.name !== '' && typeof variant.description === 'string'
						&& Object.keys( variant.knobs ).every( function( knob ) {
							const meta = variant.knobs[knob];
							return kinds.indexOf( meta.kind ) !== -1 && [ 'templates', 'type', 'surfaces', 'colours', 'motion' ].indexOf( meta.tab ) !== -1 && typeof meta.label === 'string' && meta.label !== ''
								&& ( meta.kind === 'scale' ? Number.isInteger( meta['default'] ) && Math.abs( meta['default'] ) <= range.scale
									: meta.kind === 'choice' ? Number.isInteger( meta['default'] ) && Math.abs( meta['default'] ) <= range.choice && [ 'less', 'default', 'more' ].every( function( word ) { return typeof meta.options[word] === 'string' && meta.options[word] !== '' } )
									: meta.kind === 'ground' ? realFx.grounds.indexOf( meta['default'] ) !== -1
									: meta.kind === 'ink' ? realFx.inks.indexOf( meta['default'] ) !== -1
									: Array.isArray( meta['default'] ) === true && meta['default'].length === 2 && meta['default'].every( function( colour ) { return hex.test( colour ) } ) );
						} );
				} );
		} ) );
	check( '...the knobs of every kind among them, the surfaces and the tones the panel has a word for, and the table has the object and the rows of each feature: the choice of its variant on Templates and a place for each knob on the tab its head names',
		kinds.every( function( kind ) { return live.some( function( key ) { return knobsOf( key ).some( function( found ) { return found[2].kind === kind } ) } ) } )
		&& realFx.grounds.length > 1 && realFx.grounds.every( function( word ) { return typeof TEXT['/_admin/design/ground/'+ word] === 'string' } )
		&& realFx.inks.length > 1 && realFx.inks.every( function( word ) { return typeof TEXT['/_admin/design/ink/'+ word] === 'string' } )
		&& JSON.stringify( realFx.layout.objects ) === JSON.stringify( OBJECTS.concat( live ) )
		&& live.every( function( key ) {
			return JSON.stringify( realFx.layout.controls['features/'+ key+ '/set'] ) === JSON.stringify( [ 'templates', key, 1 ] )
				&& knobsOf( key ).every( function( found ) { const at = realFx.layout.controls['features/'+ key+ '/knobs/'+ found[1]]; return at !== undefined && at[0] === found[2].tab && at[1] === key && at[2] >= 1 } );
		} ) );
	check( '...a choice kept for a feature that is not there rides along flagged, with the variant and the knobs as they were chosen, and has no object in the table',
		kept.length >= 1 && kept.every( function( key ) {
			const feature = realFx.features[key];
			return feature.active === false && typeof feature.set === 'string' && feature.set !== '' && realFx.layout.objects.indexOf( key ) === -1 && Object.keys( realFx.layout.controls ).every( function( place ) { return place.indexOf('features/'+ key+ '/') !== 0 } );
		} ) );

	/*	The screen goes where the answer says: a feature that brings a block is looked at there, one that brings none where the tab is - read for the features that bring a choice of variants */
	const realJump = panel( realFx, { whole : true } );
	const realAimed = [];

	live.filter( function( key ) { return Object.keys( realFx.features[key].catalogue ).length > 1 } ).forEach( function( key ) {
		Object.keys( realFx.features[key].catalogue ).forEach( function( set ) {
			choose( fxSelect( realJump, key ), set );
			realAimed.push( [ realJump.design._target, realFx.features[key].anchor || OBJECT_AIMS.page ] );
		} );
	} );

	check( 'the screen drawn from the server\'s answer goes where each feature of it says it is looked at: choosing a variant makes the id of its block the target of the change, and the anchor of the tab the target where the feature brings none',
		realAimed.length >= 2 && realAimed.every( function( aimed ) { return aimed[0] === aimed[1] } ) && realAimed.some( function( aimed ) { return aimed[1] !== OBJECT_AIMS.page } ) );

	// Every tab is drawn from the table the server gave, the blocks of the features after those of the objects
	const realProblems = [ 'templates', 'type', 'surfaces', 'colours', 'motion' ].filter( function( tab ) {
		realScreen.show( tab );
		const objects = fxObjects( realScreen );
		const own = objects.filter( function( object ) { return live.indexOf( object ) !== -1 } );
		return JSON.stringify( drawnRows( realScreen ) ) !== JSON.stringify( tableRows( realFx.layout, tab ) ) || objects.slice( objects.length - own.length ).join() !== own.join()
			|| ( tab === 'templates' && own.join() !== live.join() );
	} );

	check( 'each tab is drawn from the table the server gave and from nothing else, the blocks of the features after the blocks of the objects'+ ( realProblems.length === 0 ? '' : ' - not so for '+ realProblems.join() ), realProblems.length === 0 );

	/*	Each feature stands as the answer says: the variant that is chosen, and every knob of it at its value - the position a strip lights, the surface or the tone a select
		shows, the two colours of a pair - and at the default its head says where nothing was chosen	*/
	const standing = [];

	live.forEach( function( key ) {

		const entry = entryOf( key );
		const feature = realFx.features[key];
		const select = byTag( fxControl( realScreen, key ), 'select' )[0] || null;

		if( Object.keys( feature.catalogue ).length > 1 && ( select === null || shown( select ) !== entry.set ) )
			standing.push( key );

		Object.keys( feature.catalogue[entry.set].knobs ).forEach( function( knob ) {

			const meta = feature.catalogue[entry.set].knobs[knob];
			const value = entry.knobs[knob] === undefined ? meta['default'] : entry.knobs[knob];
			const control = fxControl( realScreen, key, knob );
			const path = 'features/'+ key+ '/knobs/'+ knob;
			let fine = control !== null;

			if( fine === true && ( meta.kind === 'scale' || meta.kind === 'choice' ) )
				fine = rowName( control ) === meta.label && pressedKey( realScreen, path ) === litAt( meta.kind, value )
					&& byTag( control, 'button' ).length === range[meta.kind] * 2 + 1 && ( meta.kind === 'scale' || byTag( control, 'button' ).map( function( button ) { return button.textContent } ).join() === [ meta.options.less, meta.options['default'], meta.options.more ].join() );
			else if( fine === true && ( meta.kind === 'ground' || meta.kind === 'ink' ) )
				fine = shown( byTag( control, 'select' )[0] ) === value && offered( byTag( control, 'select' )[0] ).length === realFx[ meta.kind === 'ground' ? 'grounds' : 'inks' ].length
					&& byTag( control, 'label' )[0].textContent === meta.label;
			else if( fine === true )
				fine = byTag( control, 'input' ).map( function( input ) { return input.value } ).join() === value.join() && byClass( control, 'design-knob-text' )[0].textContent === meta.label;

			if( fine === false )
				standing.push( key+ '/'+ knob );
		} );
	} );

	check( 'the screen stands as the answer says for every feature: the variant that is chosen on its select, every knob of it at its value - a strip lit at the position, a select on the surface or the tone, a pair of colours in its two inputs - under the label its head gave it'
		+ ( standing.length === 0 ? '' : ' - not so for '+ standing.slice( 0, 4 ).join() ), standing.length === 0 && realScreen.design._isDirty() === false );
	const pickedOf = function( key ) { return Array.isArray( realFx.features[key].picked ) === true ? {} : realFx.features[key].picked };

	check( '...the choice kept for a feature that is not there is the screen\'s still, and goes out with the next preview, and with the choices that are there as the answer gave them',
		kept.every( function( key ) { return JSON.stringify( realScreen.design._edit.features[key] ) === JSON.stringify( { set : realFx.features[key].set, knobs : realFx.features[key].knobs, styles : pickedOf( key ) } ) } )
		&& live.filter( function( key ) { return realFx.features[key].set !== '' } ).every( function( key ) { return JSON.stringify( fxLastPayload( realScreen ).features[key] ) === JSON.stringify( { set : realFx.features[key].set, knobs : realFx.features[key].knobs, styles : pickedOf( key ) } ) } )
		&& kept.every( function( key ) { return fxLastPayload( realScreen ).features[key] !== undefined } ) );

	// What the colours of the feature measure on the page is the server's: the line under a pair says it where a half falls short
	const measured = realFx.contrasts;
	const lines = [];

	Object.keys( measured ).forEach( function( key ) {
		Object.keys( measured[key] ).forEach( function( knob ) {
			const found = measured[key][knob];
			const unsafe = found.light.safe === false || found.dark.safe === false;
			fxControl( realScreen, key, knob );
			const line = realScreen.byId('design-feature-'+ key+ '_'+ knob+ '_contrast');
			if( line === null || line.hidden !== ( unsafe === false ) || ( unsafe === true && line.textContent !== say( '/_admin/design/label/contrast', found.light.ratio, found.dark.ratio, found.light.target ) ) )
				lines.push( key+ '/'+ knob );
		} );
	} );

	check( 'what the colours of a feature measure is the answer\'s: the line under each pair is hidden where both halves reach the ratio, and says the two ratios and the one a mark needs where one does not'+ ( lines.length === 0 ? '' : ' - not so for '+ lines.join() ),
		lines.length === 0 && Object.keys( measured ).length >= 1 );

	// Every word the screen shows for the features is one the panel's texts carry
	const missing = [];

	[ 'templates', 'type', 'surfaces', 'colours', 'motion' ].forEach( function( tab ) {
		realScreen.show( tab );
		eachBlock( realScreen.form, function() {
			descendants( realScreen.pane ).forEach( function( el ) {
				[ el.textContent, el.getAttribute('title'), el.getAttribute('aria-label') ].forEach( function( text ) { if( typeof text === 'string' && text.indexOf('/_admin/') === 0 ) missing.push( text ) } );
			} );
		} );
	} );

	check( '...and every word the screen shows for them is one the texts carry'+ ( missing.length === 0 ? '' : ' - missing: '+ missing.slice( 0, 4 ).join() ), missing.length === 0 );
}

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
