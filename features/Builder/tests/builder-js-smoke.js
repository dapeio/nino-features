/**
 *	Nino
 *	builder-js-smoke.js		What the panel's admin.js does over a stand-in for the page
 *												- no jsdom, no dependency, nothing but node.
 *
 *												The model half is driven as it is: moving a node inside its
 *												level and refusing it across levels, a copy that takes new
 *												ids and new keys, delete; what the tools of a frame offer,
 *												what a delete asks about; the animation - an effect and its
 *												strength as one word, what a section is to the template
 *												(like it, off or its own, for a template that animates its
 *												sections and one that does not, every row of the table), the
 *												sections that follow the template's switch; the order of a
 *												loop, the widths and the hidden viewports of a column; the
 *												names of a new key and what the grammar says of one; the red
 *												sources of a loop that changed; the preview of each view;
 *												what a save's answer means and what the two answers to a
 *												conflict send; the unsaved state. All of it over the example
 *												page of the concept (fixtures/page-home.json, which
 *												builder-smoke.php writes from what the Reader makes of
 *												page-home.tpl) and the registry of the kernel's own
 *												components (registry.json, written there as well).
 *
 *												The half that draws is run over a small stand-in of the
 *												page: elements with a tree, attributes, classes, selectors
 *												and events, and the template of the panel
 *												(templates/panel.tpl) parsed into it, so that every fragment
 *												the script clones is the real one. A dialog is opened as the
 *												page opens it, its controls are found by the name a person
 *												knows them by - the label of a field, the word of a cell of a
 *												table, the title of an icon - and changed the way a person
 *												changes them, so what a form writes into the model is
 *												measured, and so are the heads of the frames, the blocks of a
 *												dialog and what the head of a closed one says, the groups, the
 *												radio groups, the icons, the question marks with their tips,
 *												the name that is changed where it stands and the focus it
 *												leaves behind, what an animation leaves to choose (a column
 *												has no delay and no duration, none has no speed), the
 *												viewport that shows all of them, and the dialog of a source
 *												with the four answers it gives: a key, a slot, a new key and
 *												a fixed value.
 *
 *												The language and what is written in it: the language of the
 *												texts, chosen in the bar over the preview (the native one at
 *												the start, the choice kept nowhere); the first line of a text
 *												in the placeholder of its component, cut and never markup;
 *												the text of a key under its source, as a line or as the
 *												editor of the workbench in the profile of the key's format,
 *												kept in the model by language - a change, and nothing sent
 *												until the save; and the picture of a slot, in a component and
 *												behind a section, stored through the actions of the Images
 *												panel over a stand-in for the API (the slot made first where
 *												it is not there, then the picture, then the alt text), with
 *												every way it fails - and a save that stopped on the way, after
 *												which the slots are asked for again.
 *
 *												The grounds and the places: the surface of a column (its
 *												colour with counter, which the patch resolves by the pairs of
 *												Nino.css, the picture behind it and the slot an upload makes for
 *												it, the dimming that goes with the picture), the border of a
 *												section as a strength and a switch that waits for one, the
 *												cover of a section - a switch, its height and the place of its
 *												content - and the parallax and the dimming of its picture, which
 *												wait for the picture, the
 *												frame of a column in the ground it paints - dark where its
 *												picture is dimmed -, the alignment of a column in one line and
 *												as an icon in its head, the lightbox that
 *												lists every component, in groups, and greys what the place of
 *												the column does not take - with the reason -, and the second
 *												line of a placeholder that says what a component draws.
 *
 *												The card, a component that holds components, over the page of
 *												the fixtures that has two (fixtures/page-home-card.json): a path
 *												one level deeper and the moving, copying and deleting that work
 *												at it, the keys and slots of a copy, the sources that are red in
 *												it, the lightbox by place - the card in a column and in the bare
 *												cell of a loop, its children inside it, nothing a loop does not
 *												take -, its frame in the preview with the tint it has by the
 *												pairs and the mark of its picture, its tools at depth four, its
 *												form - the ground as the colour select, the words of the Builder
 *												for the values, the focus and the dimming that wait for the
 *												picture, the picture from a slot, from a field or from an upload
 *												- and the spacing of a column, which is the box of its sides.
 *
 *												The icon of a component in the lightbox: each item holds the svg
 *												the parts of the registry's entry are built into, tag by tag
 *												and attribute by attribute - the twelve of the kernel, and
 *												the two of a feature that has its own - or, for a component
 *												that gives none, the puzzle of the sprite; the parts the
 *												script does not know are left out, nothing is written as
 *												markup, and the stylesheet lays the lightbox out as rows that
 *												wrap for a phone and as a row for each group where the dialog is
 *												wide. The
 *												stand-in remembers the namespace an element is made in, to
 *												tell an svg element from an html one.
 *
 *												The lightbox and the form of a component: a click on a
 *												component adds it and the dialog goes on to its settings, the
 *												focus on the first control - and Escape leaves it in; where the
 *												focus is when the form of a node is closed, by Escape, by the
 *												button or by the cross (the control that opened it was drawn
 *												away with the preview: the focus goes to the settings tool the
 *												preview has drawn in its place); the
 *												blocks a component has something for (content, link, surface,
 *												spacing, properties) and the kinds of input the
 *												registry asks for - a row of buttons, a row of icons, a
 *												select, a switch, a checkbox -, tried on a component of a
 *												feature the stand-in registry makes up. The section: the
 *												padded cover, the whitespace, the background inside - what it
 *												needs and what takes it away -, the dimming of a plain section
 *												and the loop, which makes a section of every element; what
 *												the head of a frame says of them, and of a link; that a click
 *												in the preview leaves the page where it is (the stand-in
 *												records what is scrolled into view); and the stamps of the
 *												workbench (a stand-in with touch and stamp): what the panel
 *												asks for again when another panel wrote - and not while a
 *												dialog is open - and what it writes itself, which is no news
 *												to it.
 *
 *												The dialogs of the elements: the table of the blocks of each
 *												kind - what a dialog shows is what the table says and the
 *												element has -, the accordion with one block open, the head of a
 *												closed one saying only what deviates from the default of the
 *												element, the block that was open last remembered by the kind of
 *												element, the box of the six sides, the offsets of a picture,
 *												the question mark that carries what a field has to say - wired
 *												to its tip by aria, closed by Escape - and no sentence under a
 *												field; and the stylesheet that gives the dialog its width and
 *												lets the accordion give way in it: the open block grows and its
 *												body scrolls, so that the heads and the actions stay in sight,
 *												the room the accordion keeps for its heads and the floor of its
 *												body, and what is placed in the box that scrolls.
 *
 *	Usage: node features/Builder/tests/builder-js-smoke.js
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
const template = fs.readFileSync( path.join( __dirname, '../templates/panel.tpl' ), 'utf8' );
const stylesheet = fs.readFileSync( path.join( __dirname, '../assets/admin.css' ), 'utf8' );
const fixture = function( name ) { return JSON.parse( fs.readFileSync( path.join( __dirname, 'fixtures', name ), 'utf8' ) ) };

// The words of the panel, as the English file has them
const words = {};
fs.readFileSync( path.join( __dirname, '../text/en_US.php' ), 'utf8' ).split( '\n' ).forEach( function( line ) {
	const found = /^\s*'\[\[(\/[^\]]+)\]\]'\s*=>\s*'(.*)',\s*$/.exec( line );
	if( found !== null )
		words[found[1]] = found[2].replace( /\\'/g, "'" );
} );

// ...and the few words of the workbench the panel borrows from it
Object.assign( words, {
	'/_admin/common/label/save' : 'Save',
	'/_admin/common/label/delete' : 'Delete',
	'/_admin/common/label/rename' : 'Rename',
	'/_admin/common/label/back' : 'Back to list',
	'/_admin/common/label/moveup' : 'Move up',
	'/_admin/common/label/movedown' : 'Move down',
	'/_admin/common/label/cancel' : 'Cancel',
	'/_admin/common/label/width' : 'Width (px)',
	'/_admin/common/label/height' : 'Height (px)',
	'/_admin/common/error/save' : 'Failed to save.',
} );

// ------------------------------------------------------------ A small stand-in of the page

const VOID = [ 'input', 'br', 'hr', 'img', 'link', 'meta' ];
// What does not bubble, as in the page
const STAYS = [ 'blur', 'focus', 'close', 'mouseleave' ];
// The attributes that are a property of their own as well, and the flags that are none but their presence
const REFLECTED = { id : 'id', className : 'class', title : 'title', type : 'type', name : 'name', href : 'href', placeholder : 'placeholder', alt : 'alt', src : 'src' };
const FLAGS = [ 'hidden', 'disabled', 'readOnly' ];
// The namespaces an element is made in: the page makes its elements in html, and the icon of a component in svg
const HTML_NS = 'http://www.w3.org/1999/xhtml';
const SVG_NS = 'http://www.w3.org/2000/svg';

class Text {
	constructor( data ) {
		this.nodeType = 3;
		this.data = String( data );
		this.parentNode = null;
	}
	get textContent() { return this.data }
	set textContent( value ) { this.data = String( value ) }
	cloneNode() { return new Text( this.data ) }
}

class Tag {

	constructor( tag ) {

		const self = this;

		this.nodeType = 1;
		this.localName = tag.toLowerCase();
		this.tagName = tag.toUpperCase();
		this.namespaceURI = HTML_NS;
		this.childNodes = [];
		this.parentNode = null;
		this.attrs = {};
		this.listeners = {};
		this.value = '';
		this.checked = false;
		this.open = false;
		this.tabIndex = 0;
		this.scrollTop = 0;
		this.content = null;
		this.style = {
			values : {},
			setProperty( key, value ) { this.values[key] = String( value ) },
			removeProperty( key ) { delete this.values[key] },
			getPropertyValue( key ) { return this.values[key] ?? '' },
		};
		this.dataset = new Proxy( {}, {
			get( target, key ) { return self.attrs[ 'data-'+ String( key ).replace( /[A-Z]/g, function( letter ) { return '-'+ letter.toLowerCase() } ) ] },
			set( target, key, value ) { self.attrs[ 'data-'+ String( key ).replace( /[A-Z]/g, function( letter ) { return '-'+ letter.toLowerCase() } ) ] = String( value ); return true },
		} );
		this.classList = {
			list() { return self.className.split( /\s+/ ).filter( function( name ) { return name !== '' } ) },
			add( ...names ) { const now = this.list(); names.forEach( function( name ) { if( now.indexOf( name ) === -1 ) now.push( name ) } ); self.setAttribute( 'class', now.join(' ') ) },
			remove( ...names ) { self.setAttribute( 'class', this.list().filter( function( name ) { return names.indexOf( name ) === -1 } ).join(' ') ) },
			contains( name ) { return this.list().indexOf( name ) !== -1 },
			toggle( name, force ) {
				const on = force === undefined ? this.contains( name ) === false : force === true;
				if( on === true ) this.add( name ); else this.remove( name );
				return on;
			},
		};
	}

	get className() { return this.attrs['class'] ?? '' }
	// A browser ignores className set on an svg element (its className is a read-only SVGAnimatedString): the
	// stand-in throws instead, so that an icon built that way is seen - classList and the class attribute work
	set className( value ) {
		if( this.namespaceURI === SVG_NS )
			throw new Error( 'className of an svg element is read-only: set its class attribute' );
		if( String( value ) === '' ) delete this.attrs['class']; else this.attrs['class'] = String( value );
	}

	get children() { return this.childNodes.filter( function( node ) { return node.nodeType === 1 } ) }
	get firstChild() { return this.childNodes[0] ?? null }
	get firstElementChild() { return this.children[0] ?? null }
	get nextSibling() { const at = this.parentNode === null ? -1 : this.parentNode.childNodes.indexOf( this ); return at === -1 ? null : this.parentNode.childNodes[at + 1] ?? null }
	get previousSibling() { const at = this.parentNode === null ? -1 : this.parentNode.childNodes.indexOf( this ); return at < 1 ? null : this.parentNode.childNodes[at - 1] }

	get textContent() { return this.childNodes.map( function( node ) { return node.textContent } ).join('') }
	set textContent( value ) {
		this.childNodes.forEach( function( node ) { node.parentNode = null } );
		this.childNodes = [];
		if( String( value ) !== '' ) this.appendChild( new Text( value ) );
	}
	set innerHTML( value ) {
		if( value !== '' ) throw new Error( 'the stand-in takes an empty innerHTML only' );
		this.textContent = '';
	}

	attr( key ) {
		if( FLAGS.indexOf( key ) !== -1 ) return this.attrs[key] === undefined ? undefined : '';
		return this.attrs[key];
	}
	getAttribute( key ) { return this.attrs[key] === undefined ? null : this.attrs[key] }
	setAttribute( key, value ) { this.attrs[key] = String( value ) }
	hasAttribute( key ) { return this.attrs[key] !== undefined }

	appendChild( node ) { return this.insertBefore( node, null ) }
	insertBefore( node, ref ) {
		if( node.parentNode !== null && node.parentNode !== undefined )
			node.parentNode.childNodes.splice( node.parentNode.childNodes.indexOf( node ), 1 );
		node.parentNode = this;
		if( ref === null || ref === undefined )
			this.childNodes.push( node );
		else
			this.childNodes.splice( this.childNodes.indexOf( ref ), 0, node );
		return node;
	}
	remove() {
		if( this.parentNode === null ) return;
		const at = this.parentNode.childNodes.indexOf( this );
		this.parentNode.childNodes.splice( at, 1 );
		this.parentNode = null;
		// A field that is taken out of the page while it has the focus loses it
		if( this.document !== undefined && this.document.focused === this ) {
			this.document.focused = null;
			this.dispatch( 'blur' );
		}
	}
	contains( node ) { for( let at = node; at !== null && at !== undefined; at = at.parentNode ) if( at === this ) return true; return false }
	get isConnected() { return this.document !== undefined && this.document.root.contains( this ) }

	cloneNode( deep ) {
		const copy = new Tag( this.localName );
		copy.document = this.document;
		copy.namespaceURI = this.namespaceURI;
		Object.keys( this.attrs ).forEach( function( key ) { copy.attrs[key] = this.attrs[key] }, this );
		if( this.content !== null ) copy.content = this.content.cloneNode( true );
		if( deep === true )
			this.childNodes.forEach( function( node ) { copy.appendChild( node.cloneNode( true ) ) } );
		return copy;
	}

	addEventListener( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) }
	/**
	 *	What the page does when something happens: the listeners of the element are called, and then those of the
	 *	elements around it unless one stops it
	 */
	dispatch( type, more ) {
		const event = Object.assign( { type : type, target : this, defaultPrevented : false, stopped : false,
			preventDefault() { this.defaultPrevented = true }, stopPropagation() { this.stopped = true } }, more || {} );
		for( let at = this; at !== null && at !== undefined && event.stopped === false; at = at.parentNode ) {
			( at.listeners ? at.listeners[type] || [] : [] ).slice().forEach( function( fn ) { fn.call( at, event ) } );
			if( STAYS.indexOf( type ) !== -1 ) break;
		}
		return event;
	}
	click() { return this.dispatch('click') }
	focus() {

		// A control in a dialog that is not open is not rendered, and a browser does not give it the focus: where the focus was stays, and is what the dialog remembers when it opens
		for( let at = this.parentNode; at !== null && at !== undefined; at = at.parentNode )
			if( at.localName === 'dialog' && at.open !== true )
				return;

		const was = this.document === undefined ? null : this.document.activeElement;
		if( this.document !== undefined ) this.document.activeElement = this;
		if( was !== null && was !== this ) was.dispatch('blur');
	}
	select() {}
	// What is brought into view is told: a click on what is in view must not move the page
	scrollIntoView( options ) { if( this.document !== undefined ) this.document.scrolled.push( { el : this, options : options } ) }
	getBoundingClientRect() { return { top : 0, left : 0, bottom : 0, right : 0, width : 0, height : 0 } }
	// A dialog remembers the control that had the focus when it was opened and, as a browser closes it, gives the focus back to it - where it is
	// still in the page: what was in the dialog cannot hold the focus any more, and a control that was drawn away is not there to be given it
	showModal() { this.open = true; this.previouslyFocused = this.document === undefined ? null : this.document.activeElement }
	close() {

		const page = this.document;

		this.open = false;

		if( page !== undefined && page.focused !== null && this.contains( page.focused ) )
			page.focused = null;

		if( this.previouslyFocused !== null && this.previouslyFocused !== undefined && page !== undefined && page.root.contains( this.previouslyFocused ) )
			this.previouslyFocused.focus();

		this.previouslyFocused = null;
		this.dispatch('close');
	}

	get outerHTML() {
		const attributes = Object.keys( this.attrs ).map( function( key ) { return this.attrs[key] === '' ? ' '+ key : ' '+ key+ '="'+ this.attrs[key]+ '"' }, this ).join('');
		return VOID.indexOf( this.localName ) !== -1 ? '<'+ this.localName+ attributes+ '>' : '<'+ this.localName+ attributes+ '>'+ this.childNodes.map( function( node ) { return node.outerHTML ?? node.data } ).join('')+ '</'+ this.localName+ '>';
	}

	/**
	 *	The elements below this one, in the order of the document
	 */
	all() {
		const found = [];
		const walk = function( node ) { node.children.forEach( function( child ) { found.push( child ); walk( child ) } ) };
		walk( this );
		return found;
	}
	matches( selector ) { return parseSelector( selector ).some( function( chain ) { return matchChain( this, chain, null ) }, this ) }
	closest( selector ) { for( let at = this; at !== null && at !== undefined && at.nodeType === 1; at = at.parentNode ) if( at.matches( selector ) ) return at; return null }
	querySelectorAll( selector ) { return this.all().filter( function( node ) { return node.matches( selector ) } ) }
	querySelector( selector ) { return this.querySelectorAll( selector )[0] ?? null }
}

// The properties that are attributes as well
Object.keys( REFLECTED ).forEach( function( property ) {
	if( property === 'className' ) return;
	Object.defineProperty( Tag.prototype, property, {
		get() { return this.attrs[ REFLECTED[property] ] ?? '' },
		set( value ) { this.attrs[ REFLECTED[property] ] = String( value ) },
	} );
} );
FLAGS.forEach( function( flag ) {
	Object.defineProperty( Tag.prototype, flag, {
		get() { return this.attrs[flag] !== undefined },
		set( value ) { if( value === true ) this.attrs[flag] = ''; else delete this.attrs[flag] },
	} );
} );

/**
 *	A selector as a list of chains - one for each part of a list - each chain a list of [ combinator, compound ]
 *	from the left: the compounds are a tag, an id, classes, attributes and :not(:disabled)
 */
function parseSelector( selector ) {

	return selector.split( /,(?![^[]*\])/ ).map( function( part ) {

		const chain = [];
		const pattern = /\s*([>\s])?\s*((?:[a-z*][a-z0-9-]*)?(?:#[\w-]+|\.[\w-]+|\[[^\]]+\]|:not\(:disabled\))*)/gy;
		let combinator = ' ';
		let found;

		part = part.trim();
		pattern.lastIndex = 0;

		while( pattern.lastIndex < part.length && ( found = pattern.exec( part ) ) !== null ) {
			chain.push( [ chain.length === 0 ? ' ' : ( found[1] === '>' ? '>' : ' ' ), found[2] ] );
			combinator = found[1];
			if( found[0] === '' ) break;
		}

		return chain.length === 0 ? [ [ combinator, '' ] ] : chain;
	} );
}

function matchCompound( node, compound ) {

	const pieces = compound.match( /^[a-z*][a-z0-9-]*|#[\w-]+|\.[\w-]+|\[[^\]]+\]|:not\(:disabled\)/g ) || [];

	return pieces.every( function( piece ) {

		if( piece === '*' ) return true;
		if( piece.charAt(0) === '#' ) return node.attrs.id === piece.slice( 1 );
		if( piece.charAt(0) === '.' ) return node.classList.contains( piece.slice( 1 ) );
		if( piece === ':not(:disabled)' ) return node.disabled === false;

		if( piece.charAt(0) === '[' ) {
			const found = /^\[([\w-]+)(?:="([^"]*)")?\]$/.exec( piece );
			const value = found[1] === 'class' ? node.className : node.attr( found[1] );
			return found[2] === undefined ? value !== undefined : value === found[2];
		}

		return node.localName === piece;
	} );
}

/**
 *	Whether a node matches a chain: the last compound is its own, the ones before it its parents or ancestors
 */
function matchChain( node, chain, scope ) {

	const last = chain[chain.length - 1];

	if( matchCompound( node, last[1] ) === false ) return false;

	if( chain.length === 1 ) return true;

	const rest = chain.slice( 0, -1 );

	if( last[0] === '>' )
		return node.parentNode !== null && node.parentNode.nodeType === 1 && matchChain( node.parentNode, rest, scope );

	for( let at = node.parentNode; at !== null && at !== undefined && at.nodeType === 1; at = at.parentNode )
		if( matchChain( at, rest, scope ) ) return true;

	return false;
}

/**
 *	A page: the elements it makes, those of the template of the panel parsed into it, and the one that has the focus
 */
class Document {

	constructor() {
		this.focused = null;
		this.scrolled = [];
		this.root = this.make('div');
		this.documentElement = this.make('html');
		this.body = this.make('body');
		this.listeners = {};
	}

	// The element that has the focus; a page whose element left it - taken out with what held it - has none
	get activeElement() { return this.focused !== null && this.root.contains( this.focused ) ? this.focused : null }
	set activeElement( element ) { this.focused = element }

	make( tag ) {
		const element = new Tag( tag );
		element.document = this;
		return element;
	}
	createElement( tag ) { return this.make( tag ) }
	// An element in the namespace asked for, which it remembers: a check tells an svg element from an html one by it
	createElementNS( ns, tag ) {
		const element = this.make( tag );
		element.namespaceURI = ns;
		return element;
	}
	createTextNode( text ) { return new Text( text ) }
	getElementById( id ) { return this.root.all().find( function( node ) { return node.attrs.id === id } ) ?? null }
	querySelector( selector ) { return this.root.querySelector( selector ) }
	querySelectorAll( selector ) { return this.root.querySelectorAll( selector ) }
	addEventListener( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) }

	/**
	 *	The template of the panel, parsed: a copy of what the page gets of it, with the words of its fills
	 *
	 *	@param		{string}		html
	 *	@param		{Function}	say								A key's words
	 */
	load( html, say ) {

		const text = html.replace( /<!--[\s\S]*?-->/g, '' ).replace( /\[\[(\/[^\]]+)\]\]/g, function( all, key ) { return say( key ) } );
		const tokens = /<(\/)?([a-zA-Z][a-zA-Z0-9-]*)((?:\s+[^\s=>\/]+(?:="[^"]*")?)*)\s*(\/)?>|([^<]+)/g;
		const stack = [ this.root ];
		let opened = null;
		let found;

		while( ( found = tokens.exec( text ) ) !== null ) {

			const top = stack[stack.length - 1];

			if( found[5] !== undefined ) {
				// Blanks between elements are no node; the blanks that are all an element holds - a line break in a div - are
				const alone = opened === top && text.charAt( tokens.lastIndex ) === '<' && text.charAt( tokens.lastIndex + 1 ) === '/';
				if( found[5].trim() !== '' || top.localName === 'pre' || alone === true ) top.appendChild( new Text( found[5].replace( /&times;/g, '×' ) ) );
				continue;
			}

			if( found[1] === '/' ) {
				stack.pop();
				opened = null;
				continue;
			}

			const element = this.make( found[2] );

			// An svg is an element of svg, and so is whatever stands in it, as the page parses it
			if( found[2] === 'svg' || top.namespaceURI === SVG_NS )
				element.namespaceURI = SVG_NS;

			( found[3] || '' ).replace( /([^\s=]+)(?:="([^"]*)")?/g, function( all, name, value ) { element.attrs[name] = value === undefined ? '' : value } );

			// What a template holds goes into its content, not into its children
			if( top.localName === 'template' ) {
				if( top.content === null ) top.content = this.make('template-content');
				top.content.appendChild( element );
			} else
				top.appendChild( element );

			opened = null;

			if( found[4] !== '/' && VOID.indexOf( element.localName ) === -1 ) {
				stack.push( element );
				opened = element;
			}
		}
	}
}

// ------------------------------------------------------------ The page the panel draws on, and the panel


let requests = [];
let questions = [];
// What the list of templates handed the table of the workbench to draw
let tables = [];
let answers = [];
let changes = 0;
// The pictures the workbench was asked to check before they were sent
let checked = [];

// The page: the template of the panel, with its words, parsed - everything the script draws, it draws from that
const screen = new Document();

screen.load( template, function( key ) { return words[key] ?? '' } );

const made = function( tag, className, text ) {
	const element = screen.createElement( tag );
	if( className !== undefined ) element.className = className;
	if( text !== undefined ) element.textContent = text;
	return element;
};

const sandbox = {
	document : screen,
	console : console,
};

sandbox.window = sandbox;
sandbox.Nino = {
	dir : '',
	events : { bindCallback : function() {} },
	content : { getText : function( key ) { return Object.prototype.hasOwnProperty.call( words, key ) ? words[key] : '' } },
	admin : {
		router : { current : function() { return { panel : 'builder', parts : [] } }, set : function() {}, go : function() {}, leave : function( names, leaving, proceed ) { proceed() } },
		dirty : { entry : null, register : function( name, entry ) { this.entry = entry }, refresh : function() {} },
		sessionLocale : { current : 'en_US' },
		// What the panels have written, by kind, as the workbench counts it: a panel touches a kind after a write and reads the stamp
		stamps : {},
		touch : function( topic ) { this.stamps[topic] = ( this.stamps[topic] ?? 0 ) + 1 },
		stamp : function( topic ) { return this.stamps[topic] ?? 0 },
	},
	// The fields of the workbench, as it makes them: a label with its words and its control
	adminUi : {
		format : function( text, ...params ) {
			let at = 0;
			return String( text ?? '' ).replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } );
		},
		api : { call : function( action, payload, callback, extra ) { requests.push( { action : action, payload : payload, callback : callback, extra : extra } ) }, errorText : function( status, response, key ) { return '('+ status+ ') '+ ( key ?? ( response ?? {} ).code ) } },
		// What an upload control tells before a file is chosen, and the check of a file before it is sent: a file that says it is too large is refused
		uploadHint : function() { return made( 'p', 'nino-admin-hint', 'At most 8 MB and 20 megapixels.' ) },
		checkImage : function( file, done ) { checked.push( file ); done( file.refused === true ? { code : 'image_too_large', params : [ 8 ] } : null ) },
		selectField : function( options ) {

			const field = made( 'label', options.className || 'nino-admin-field' );
			const select = made( 'select', 'nino-admin-input' );

			field.appendChild( made( 'span', undefined, options.label || options.key ) );

			if( options.hint )
				select.setAttribute( 'title', options.hint );

			( options.options || [] ).forEach( function( option ) {
				const el = made( 'option', undefined, option.label );
				el.value = String( option.value );
				select.appendChild( el );
			} );

			select.value = String( options.value );
			select.addEventListener( 'change', function() { options.onChange( select.value ) } );
			field.appendChild( select );

			return field;
		},
		switchField : function( options ) {

			const label = made( 'label', 'nino-admin-switch' );
			const input = made('input');
			const copy = made( 'span', 'nino-admin-switch-copy' );

			input.type = 'checkbox';
			input.checked = options.checked === true;
			label.appendChild( input );
			copy.appendChild( screen.createTextNode( options.label ) );

			if( options.hint )
				copy.appendChild( made( 'small', undefined, options.hint ) );

			label.appendChild( copy );

			return label;
		},
		numberField : function( options ) {

			const label = made( 'label', 'nino-admin-field' );
			const input = made('input');

			input.type = 'number';
			input.value = options.value;
			label.appendChild( made( 'span', undefined, options.label ) );
			label.appendChild( input );

			if( options.hint )
				label.appendChild( made( 'small', 'nino-admin-hint', options.hint ) );

			return label;
		},
		// What a row of buttons does in the workbench: one is active, the choice of the others is told, an aria attribute says which
		buttonRow : function( buttons, active, onSelect, flag ) {

			const attribute = flag || 'aria-pressed';
			const keys = Object.keys( buttons ).filter( function( key ) { return buttons[key] !== null && buttons[key] !== undefined } );
			const paint = function( key ) {
				keys.forEach( function( candidate ) {
					buttons[candidate].classList.toggle( 'is-active', candidate === key );
					buttons[candidate].setAttribute( attribute, candidate === key ? 'true' : 'false' );

					// A tab list is one stop of the tab key, not one for each tab
					if( attribute === 'aria-selected' )
						buttons[candidate].tabIndex = candidate === key ? 0 : -1;
				} );
			};

			keys.forEach( function( key ) {
				buttons[key].addEventListener( 'click', function() {
					paint( key );
					if( typeof onSelect === 'function' )
						onSelect( key );
				} );
			} );

			paint( active );

			return paint;
		},
		emptyState : function() { return made('div') },
		listActions : function() { return made('div') },
		notice : function( text, link ) { return made( 'p', 'nino-admin-notice', text+ ( link === undefined ? '' : ' '+ link.label ) ) },
		table : function( options ) { tables.push( options ); return { setRows : function() {} } },
		status : function() {
			const calls = [];
			const status = { state : 'idle', calls : calls };
			[ 'idle', 'saving', 'saved', 'dirty', 'fail', 'error' ].forEach( function( name ) {
				status[name] = function( text ) { status.state = name === 'fail' ? 'error' : name; status.text = text; calls.push( name ) };
			} );
			return status;
		},
		choiceDialog : function( options ) { questions.push( options ); return true },
	},
};

vm.createContext( sandbox );
vm.runInContext( source, sandbox, { filename : 'admin.js' } );

const builder = sandbox.Nino.admin.builder;
const registryFixture = fixture('registry.json');

// What the registry calls a component in a language of the site: the words of the kernel the PHP suite ran against, so that none is typed here
const labelIn = function( name, locale ) { return registryFixture.components[name].labels[locale] };

// What the preview says for a component that has no text to say, in whichever language the editor shows: its label in the language of the workbench, as the registry answers it
const titleLabel = registryFixture.components.title.label;

/**
 *	The groups of the lightbox, in the order it lists them: the words a component of the kernel or of a module names as its group,
 *	in the order the registry's answer lists them (groups, written by the PHP suite from the kernel it ran against, so that no word
 *	is typed here) - and after them the Features, for what a feature registered
 */
const groupWords = registryFixture.groups;

/**
 *	Which group of the lightbox a component is in, as the place of the group in that order: the place of the word its schema names as
 *	its group, and the one after all of them for what a feature registered (its "by"). The registry says it, no name is typed here
 */
const groupOf = function( name, all ) {

	const schema = all[name];

	if( typeof schema.by === 'string' && schema.by !== 'nino' )
		return groupWords.length;

	return groupWords.indexOf( schema.group );
};

/**
 *	The labels of the components that belong in a place, as one string, in the order the lightbox lists them: its groups one after the
 *	other, each in the order of the registry. A component that says where it belongs ("in") is in those places and no other, one that
 *	says nothing is in every one. Read off the registry the PHP suite wrote from the kernel it ran against, so that no list of
 *	components is typed here
 */
const belonging = function( place, components ) {

	const all = components || registryFixture.components;
	const names = Object.keys( all ).filter( function( name ) { return all[name]['in'] === undefined || all[name]['in'].indexOf( place ) !== -1 } );

	return groupWords.concat( [ 'features' ] ).map( function( word, group ) { return names.filter( function( name ) { return groupOf( name, all ) === group } ) } ).flat().map( function( name ) { return all[name].label } ).join();
};

/**
 *	The registry as the panel gets it: the components and the stacks of the kernel
 *	and a project with one element type, two image slots and the frames
 */
const registry = function() {
	return Object.assign( JSON.parse( JSON.stringify( registryFixture ) ), {
		types		: [
			{ uri : '/services', title : 'Services', fields : { title : { type : 'string' }, summary : { type : 'string', blocks : true }, image : { type : 'image' }, price : { type : 'double' } } },
			{ uri : '/team', title : 'Team', fields : { name : { type : 'string' }, photo : { type : 'image' } } },
		],
		slots		: [ { uri : '/template/page-home/hero/background', label : 'Background', width : 1600, height : 900, hasImage : true, url : '/uploads/hero.1600x900.jpg', alt : { de_DE : 'Ein Held' } }, { uri : '/project/logo/header/image', label : 'Logo', width : 300, height : 100, hasImage : false, url : null, alt : [] } ],
		locales	: [ { locale : 'en_US', name : 'English (US)' }, { locale : 'de_DE', name : 'Deutsch' } ],
		native	: 'en_US',
		headers	: [ 'html-header', 'html-header-slim' ],
		footers	: [ 'html-footer' ],
		fieldTypes : [ 'string', 'integer', 'double', 'boolean', 'array', 'date', 'datetime', 'image', 'element' ],
	} );
};

const page = function() { return builder._normalise( fixture('page-home.json') ) };
const known = function() { return [
	{ key : '/template/page-home/hero/title', global : false, format : 'plain', maxlength : 160, values : { en_US : 'Welcome', de_DE : 'Willkommen' } },
	{ key : '/template/page-home/hero/subtitle', global : true, format : 'plain', maxlength : 160, values : { '*' : 'We build' } },
] };

// ------------------------------------------------------------ What a person does

/**
 *	The name a control has for a person who does not see it: its label, or the word a table or a
 *	group of radio buttons gives it
 */
const nameOf = function( el ) {

	if( el.getAttribute('aria-label') !== null )
		return el.getAttribute('aria-label');

	const label = el.closest('label');

	if( label === null )
		return '';

	if( el.type === 'checkbox' && label.classList.contains('nino-admin-switch') )
		return label.querySelector('.nino-admin-switch-copy').firstChild.textContent;

	if( el.type === 'checkbox' && label.classList.contains('builder-check') )
		return label.children[1].textContent;

	return label.children[0].textContent;
};

const kindOf = function( el ) {

	if( el.getAttribute('role') === 'radiogroup' )
		return 'group';

	if( el.localName === 'select' )
		return 'select';

	if( el.type === 'checkbox' )
		return el.closest('.nino-admin-switch') === null ? 'box' : 'switch';

	return el.type === 'number' ? 'number' : 'text';
};

/**
 *	The controls below an element, in the order of the page: a select, a switch, a box, a number, a line of
 *	text, a group of radio buttons (whose buttons are not counted one by one)
 */
const controlsIn = function( root ) {
	return root.all().filter( function( el ) {
		return el.localName === 'select' || el.localName === 'textarea' || ( el.localName === 'input' && el.type !== 'radio' ) || el.getAttribute('role') === 'radiogroup';
	} ).map( function( el ) { return { el : el, kind : kindOf( el ), label : nameOf( el ) } } );
};

const labelList = function( root ) { return controlsIn( root ).map( function( control ) { return control.label } ) };
const labelsIn = function( root ) { return labelList( root ).join() };

const controlOf = function( root, label, nth ) {

	const found = controlsIn( root ).filter( function( control ) { return control.label === label } )[nth ?? 0];

	if( found === undefined )
		throw new Error( 'no control "'+ label+ '" in '+ labelsIn( root ) );

	return found;
};

const radiosOf = function( group ) { return group.querySelectorAll('input') };

/**
 *	What a control holds now: the value of a select or a field, whether a switch is on, the value of the radio button that is chosen
 */
const currentOf = function( control ) {

	if( control.kind === 'switch' || control.kind === 'box' )
		return control.el.checked;

	if( control.kind === 'group' )
		return ( radiosOf( control.el ).find( function( radio ) { return radio.checked } ) ?? { value : null } ).value;

	return control.el.value;
};

const valueAt = function( root, label, nth ) { return currentOf( controlOf( root, label, nth ) ) };

/**
 *	A control changed the way a person changes it, and the event the page listens for sent: a select takes one of its
 *	options, a radio button of a group is chosen, a switch is turned, a field is typed into
 */
const change = function( root, label, value, nth ) {

	const control = controlOf( root, label, nth );
	const el = control.el;

	if( control.kind === 'select' ) {

		if( el.children.some( function( option ) { return option.value === String( value ) } ) === false )
			throw new Error( 'the select "'+ label+ '" has no option "'+ value+ '"' );

		el.value = String( value );
		el.dispatch('change');
	} else if( control.kind === 'switch' || control.kind === 'box' ) {
		el.checked = value === true;
		el.dispatch('change');
	} else if( control.kind === 'group' ) {

		const radios = radiosOf( el );
		const radio = radios.find( function( candidate ) { return candidate.value === String( value ) } );

		if( radio === undefined )
			throw new Error( 'the group "'+ label+ '" has no button "'+ value+ '"' );

		if( radio.disabled === true )
			return false;

		radios.forEach( function( candidate ) { candidate.checked = candidate === radio } );
		radio.dispatch('change');
	} else {
		el.value = String( value );
		el.dispatch('input');
		el.dispatch('change');
	}

	return true;
};

// Every block of the dialog that is open drawn again, as a choice that changed what the blocks show would have them drawn
const redrawBlocks = function() { builder._refreshBlock( ...builder._dialogBlocks.ids ) };

const pressKey = function( el, key ) { return el.dispatch( 'keydown', { key : key } ) };

// ------------------------------------------------------------ What stands on the page

const dialogBox = function() { return screen.getElementById('builder-dialog') };
const pickerBox = function() { return screen.getElementById('builder-picker') };
const dialogText = function( id ) { return screen.getElementById( id ).textContent };
// The body of a block of the dialog that is open, by the id of the block: every block is in the page, and the ones that are closed are hidden
const blockOf = function( id ) { return screen.getElementById( 'builder-body-'+ id ) };
const pickerPane = function( id ) { return screen.getElementById( 'builder-picker-pane-'+ id ) };
const pickerStrip = function() { return screen.getElementById('builder-picker-tabs') };
const fixedField = function() { return pickerPane('fixed').querySelector('input') };
const dialogContent = function() { return screen.getElementById('builder-dialog-content') };
const preview = function() { return screen.getElementById('builder-preview') };
// The frame of a path in the preview, and one of its tools
const frameOf = function( at ) { return preview().querySelector('[data-path="'+ at+ '"]') };
const toolOf = function( at, tool ) { return frameOf( at ).querySelectorAll('button').find( function( button ) { return button.dataset.tool === tool } ) };

// The tabs of a strip - the picker's: [ id, word ] each, in the order they are drawn
const tabsOf = function( strip, prefix ) {
	return strip.querySelectorAll('[role="tab"]').map( function( tab ) { return [ tab.id.replace( prefix+ '-tab-', '' ), tab.textContent ] } );
};

// The blocks of the dialog that is open, by id in the order they are drawn, and the one that is open - the others are closed, and the body of a closed one is hidden
const blockIds = function() { return dialogContent().querySelectorAll('.builder-block').map( function( part ) { return part.id.replace( 'builder-block-', '' ) } ) };
const openBlock = function() { return dialogContent().querySelectorAll('.builder-block-body').filter( function( body ) { return body.hidden === false } ).map( function( body ) { return body.id.replace( 'builder-body-', '' ) } ).join() };
// The head of a block: its button, and what it says of the element - nothing where the block is on its defaults
const headOf = function( id ) { return screen.getElementById( 'builder-head-'+ id ) };
const saysOf = function( id ) { return headOf( id ).querySelector('.builder-block-says') };
// The sentence a field has to say, behind the question mark that stands in its row - null for a field that has none
const tipOf = function( control ) {
	const row = control.el.closest('.builder-row');
	return row === null || row.querySelector('.builder-help-tip') === null ? null : row.querySelector('.builder-help-tip').textContent;
};

// The tab that is shown: the one whose pane is not hidden
const shownTab = function( strip, prefix ) {
	return strip.querySelectorAll('[role="tab"]').filter( function( tab ) {
		return screen.getElementById( tab.getAttribute('aria-controls') ).hidden === false;
	} ).map( function( tab ) { return tab.id.replace( prefix+ '-tab-', '' ) } ).join();
};

const texts = function( els ) { return els.map( function( el ) { return el.textContent } ).join() };
const classes = function( els ) { return els.map( function( el ) { return el.className } ).join() };
const titlesIn = function( root ) { return texts( root.querySelectorAll('.builder-group-title') ) };

// The icon a control has, by the sprite symbol its <use> points at
const iconOf = function( el ) { const use = el.querySelector('use'); return use === null ? null : use.getAttribute('href').replace( '#builder-icon-', '' ) };

// The first button of an element with the words given
const buttonOf = function( root, text ) {

	const found = root.querySelectorAll('button').find( function( button ) { return button.textContent === text || button.getAttribute('aria-label') === text } );

	if( found === undefined )
		throw new Error( 'no button "'+ text+ '"' );

	return found;
};

// Every control that is an icon and nothing else, and does not say what it is: it needs a title, and a label for a screen reader
const unnamed = function( root ) {
	return root.all().filter( function( el ) {

		if( el.localName === 'button' && el.querySelector('use') !== null && el.textContent.trim() === '' )
			return el.title === '' || el.getAttribute('aria-label') !== el.title;

		if( el.localName === 'label' && el.classList.contains('builder-segment-option') && el.querySelector('use') !== null )
			return el.title === '' || el.querySelector('input').getAttribute('aria-label') !== el.title;

		return false;
	} );
};

// Whether every icon below an element is a symbol of the sprite of the template
const iconsKnown = function( root ) {
	return root.all().filter( function( el ) { return el.localName === 'use' } ).every( function( use ) {
		const symbol = screen.getElementById( use.getAttribute('href').slice( 1 ) );
		return symbol !== null && symbol.localName === 'symbol';
	} );
};

// The lightbox the picker of a component opens in the dialog: its items - one for every component of the registry - and the labels of
// those that are not greyed, which are the components that can be added where it was opened. The label is in the span of its own
const lightboxOf = function() { return screen.getElementById('builder-dialog-content').querySelector('.builder-lightbox') };
const pickLabel = function( item ) { return item.children[1].textContent };
const itemsOf = function( box ) { return box.querySelectorAll('.builder-pick-item') };
const pickAt = function( path ) { builder._pickComponent( path ); return lightboxOf() };
// An item by the name of its component, which is the registry's. Its label is the kernel's wording in the language of the workbench, and nothing to find it by
const itemNamed = function( box, name ) { return itemsOf( box ).find( function( item ) { return item.dataset.name === name } ) };
// A group of the lightbox by its word - the registry's, or features - and the first item of it that can be added where the lightbox was opened, and the first that is greyed
const groupIn = function( box, word ) { return box.children.find( function( group ) { return group.dataset.group === word } ) };
const firstOffered = function( box, word ) { return itemsOf( groupIn( box, word ) ).find( function( item ) { return item.disabled === false } ) };
const firstGreyed = function( box, word ) { return itemsOf( groupIn( box, word ) ).find( function( item ) { return item.disabled === true } ) };
// What the dialog that opens for a component is titled
const titleOf = function( name ) { return 'Component "'+ registryFixture.components[name].label+ '"' };
const takenIn = function( box ) { return itemsOf( box ).filter( function( item ) { return item.disabled === false } ).map( pickLabel ).join() };
const offeredAt = function( path ) { return takenIn( pickAt( path ) ) };
// Why a component cannot go where the lightbox was opened: its title as that button has it, '' for one that can, null for one that is not listed
const reasonOf = function( path, label ) {
	const item = itemsOf( pickAt( path ) ).find( function( candidate ) { return pickLabel( candidate ) === label } );
	return item === undefined ? null : ( item.disabled === true ? item.title : '' );
};

// What a select holds - the value and the word of each option - and what a row of buttons holds: the value of each and its word, the face of a word or the title of an icon, and the icon it wears
const segmentOf = function( root, label ) { return controlOf( root, label ).el.querySelectorAll('.builder-segment-option').map( function( option ) { return option.querySelector('input').value+ ':'+ option.textContent } ).join() };
const optionsOf = function( pane, label ) { return controlOf( pane, label ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() };
// A row of buttons as it is drawn: the value of each, its word - the title of an icon, the face of a word - and the icon it wears
const rowOf = function( pane, label ) {
	return controlOf( pane, label ).el.querySelectorAll('.builder-segment-option').map( function( option ) {
		const face = option.querySelector('.builder-segment-face');
		return option.querySelector('input').value+ ':'+ ( face === null ? option.title : face.textContent )+ ':'+ ( iconOf( option ) ?? '' );
	} ).join();
};

console.log( 'The model' );

let model = page();

check( 'the model php sends has its empty maps as lists, and they are maps here', Array.isArray( fixture('page-home.json').blocks[0].cols[0].hidden ) === true
	&& JSON.stringify( model.blocks[0].cols[0].hidden ) === '{}' && model.blocks[0].cols[0].components[0].attributes.tag === 'h3' );
check( 'a path names its node: the template, a section, a column, a component, a stack', builder._get( model, [] ) === model && builder._get( model, [ 0 ] ).id === 'hero' && builder._get( model, [ 1, 1 ] ).stack.name === 'stack'
	&& builder._get( model, [ 0, 0, 2 ] ).name === 'button' && builder._get( model, [ 1, 1, 'x' ] ).source === '/services' && builder._get( model, [ 1, 0, 'x' ] ) === null && builder._get( model, [ 9 ] ) === null && builder._get( model, [ 2, 0 ] ) === null );
check( 'what a node is: template, section, a block of html, column, stack, component', [ [], [ 0 ], [ 2 ], [ 0, 0 ], [ 1, 1, 'x' ], [ 0, 0, 1 ] ].map( function( at ) { return builder._kind( model, at ) } ).join() === 'template,section,html,col,stack,component' );

console.log( '\nMoving, copying, deleting' );

check( 'a section moves to the place of another and the others close up', builder._move( model, [ 0 ], [ 1 ] ) === true && model.blocks.map( function( block ) { return block.id || 'html' } ).join() === 'services,hero,html,contact' );
check( '...and back', builder._move( model, [ 1 ], [ 0 ] ) === true && model.blocks[0].id === 'hero' );
check( 'a column moves inside its section, a component inside its column', builder._move( model, [ 1, 0 ], [ 1, 1 ] ) === true && model.blocks[1].cols[0].stack !== null
	&& builder._move( model, [ 0, 0, 0 ], [ 0, 0, 2 ] ) === true && model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'subtitle,button,title' );
model = page();
check( 'nothing moves across levels: a component into another column, a column into another section, a section into a column, a stack at all',
	builder._move( model, [ 0, 0, 0 ], [ 1, 0, 0 ] ) === false && builder._move( model, [ 0, 0 ], [ 1, 0 ] ) === false && builder._move( model, [ 0 ], [ 0, 0 ] ) === false
	&& builder._move( model, [ 1, 1, 'x' ], [ 1, 1, 0 ] ) === false && builder._move( model, [], [ 0 ] ) === false );
check( '...nor to its own place, nor to one that is not there', builder._move( model, [ 0 ], [ 0 ] ) === false && builder._move( model, [ 0 ], [ 9 ] ) === false && JSON.stringify( model ) === JSON.stringify( page() ) );
check( 'a step up from the first place and down from the last are none, and a step is a move', builder._step( model, [ 0 ], -1 ) === null && builder._step( model, [ 3 ], 1 ) === null && builder._same( builder._step( model, [ 3 ], -1 ), [ 2 ] ) && model.blocks[2].id === 'contact' );
model = page();
check( 'a step moves a node within its level, a section among sections, a column among columns, a component among the components of its column - and the others close up', ( function() {
	const names = function() { return model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() };
	const down = builder._step( model, [ 0, 0, 0 ], 1 );
	const moved = names();
	const section = builder._step( model, [ 1 ], -1 );
	return builder._same( down, [ 0, 0, 1 ] ) && moved === 'subtitle,title,button' && builder._same( section, [ 0 ] ) && model.blocks[0].id === 'services' && model.blocks[1].id === 'hero'
		&& builder._step( model, [ 1, 0 ], 1 ) === null && builder._step( model, [ 0, 0 ], -1 ) === null;
} )() );

model = page();

check( 'a copy of a section takes an id of its own, and keys and slots of its own where the original has them: new ones, made with the text the key has now in each language it has one in - and with none where it has none',
	( function() {
		const at = builder._duplicate( model, registry(), 'page-home', [ 0 ], function( key ) { return key === '/template/page-home/hero/title' ? { en_US : 'Welcome', de_DE : 'Willkommen' } : {} } );
		const copy = model.blocks[1];
		return builder._same( at, [ 1 ] ) && copy.id === 'hero-2' && model.blocks[0].id === 'hero' && model.blocks.length === 5
			&& copy.cols[0].components[0].source === '/template/page-home/hero-2/title' && JSON.stringify( copy.cols[0].components[0].create ) === '{}' && JSON.stringify( copy.cols[0].components[0].values ) === '{"en_US":"Welcome","de_DE":"Willkommen"}'
			&& copy.cols[0].components[1].source === '/template/page-home/hero-2/subtitle' && JSON.stringify( copy.cols[0].components[1].create ) === '{}' && copy.cols[0].components[1].values === undefined
			&& copy.background.slot === '/template/page-home/hero-2/background' && copy.background.create.label === 'Background' && copy.background.focus === 5
			&& copy.cols[0].components[2].source === '/_nino/webpage/contact/name' && copy.cols[0].components[2].create === undefined
			&& model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' && model.blocks[0].cols[0].components[0].create === undefined && model.blocks[0].background.create === undefined;
	} )() );
check( '...a second copy takes the next id', builder._same( builder._duplicate( model, registry(), 'page-home', [ 0 ] ), [ 1 ] ) && model.blocks[1].id === 'hero-3' );
model = page();
check( 'a copy of a component in its section has a name beside the original (title-2), and one that is not the section\'s own keeps its source', ( function() {
	const first = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return { en_US : 'Welcome' } } );
	const second = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return { en_US : 'Welcome' } } );
	const third = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 4 ] );
	const components = model.blocks[0].cols[0].components;
	return builder._same( first, [ 0, 0, 1 ] ) && components[1].source === '/template/page-home/hero/title-3' && JSON.stringify( components[1].values ) === '{"en_US":"Welcome"}' && builder._same( second, [ 0, 0, 1 ] )
		&& components[2].source === '/template/page-home/hero/title-2' && components[0].source === '/template/page-home/hero/title' && components[0].create === undefined && builder._same( third, [ 0, 0, 5 ] ) && components[5].source === '/_nino/webpage/contact/name' && components[5].create === undefined;
} )() );
model = page();
check( 'a copy of a column takes new keys beside the original for every component, and the copy shares nothing with it', ( function() {
	builder._duplicate( model, registry(), 'page-home', [ 0, 0 ] );
	const copy = model.blocks[0].cols[1];
	copy.components[0].attributes.tag = 'h4';
	return copy.components[0].source === '/template/page-home/hero/title-2' && copy.components[1].source === '/template/page-home/hero/subtitle-2' && model.blocks[0].cols[0].components[0].attributes.tag === 'h3' && model.blocks[0].cols.length === 2;
} )() );
model = page();
model.blocks[1].cols[0].background = { slot : '/template/page-home/services/column-background', focus : 3 };
check( 'a copy of a section takes a slot of its own for the picture behind each of its columns, and a copy of a column one beside the original: new, labelled as a background, with the focus - and nothing is shared with the original', ( function() {

	builder._duplicate( model, registry(), 'page-home', [ 1 ] );

	const copy = model.blocks[2].cols[0].background;
	const whole = copy.slot === '/template/page-home/services-2/column-background' && copy.create.label === 'Background' && copy.focus === 3 && model.blocks[1].cols[0].background.create === undefined && copy !== model.blocks[1].cols[0].background;

	builder._duplicate( model, registry(), 'page-home', [ 1, 0 ] );

	const beside = model.blocks[1].cols[1].background;

	return whole && beside.slot === '/template/page-home/services/column-background-2' && beside.create.label === 'Background' && beside.focus === 3 && beside !== model.blocks[1].cols[0].background
		&& model.blocks[1].cols[0].background.slot === '/template/page-home/services/column-background' && model.blocks[1].cols[0].background.create === undefined;
} )() );
model = page();
check( 'a column that has no picture is copied without one', ( function() {
	builder._duplicate( model, registry(), 'page-home', [ 1, 0 ] );
	return model.blocks[1].cols[1].background === null && model.blocks[1].cols[1].color === '' && model.blocks[1].cols[1].dim === false;
} )() );
model = page();
check( 'a block of html is copied as it is, a stack cannot be copied', ( function() {
	const html = builder._duplicate( model, registry(), 'page-home', [ 2 ] );
	return builder._same( html, [ 3 ] ) && model.blocks[3].kind === 'html' && model.blocks[3].source === model.blocks[2].source && model.blocks[3] !== model.blocks[2] && builder._duplicate( model, registry(), 'page-home', [ 1, 1, 'x' ] ) === null;
} )() );

model = page();
check( 'delete takes a section, a column or a component out', builder._remove( model, [ 1, 1, 0 ] ) === true && model.blocks[1].cols[1].components.length === 3
	&& builder._remove( model, [ 2 ] ) === true && model.blocks.length === 3 && builder._remove( model, [ 0, 0 ] ) === true && model.blocks[0].cols.length === 0 && builder._remove( model, [ 9 ] ) === false && builder._remove( model, [] ) === false );
model = page();
check( '...a loop is not taken out like that: it is set to Static in the properties of its column, and the components stay', builder._remove( model, [ 1, 1, 'x' ] ) === false && model.blocks[1].cols[1].stack !== null && model.blocks[1].cols[1].components.length === 4
	&& builder._remove( model, [ 1, 0, 'x' ] ) === false && builder._remove( model, [ 0, 5, 'x' ] ) === false && model.blocks[1].cols.length === 2 );

console.log( '\nThe tools of a frame' );

model = page();
const toolbar = function( at ) { return JSON.stringify( builder._toolbar( model, at ) ) };
check( 'a section has settings, HTML+, up, down, duplicate and delete - the first cannot go up, the last cannot go down', toolbar( [ 1 ] ) === '{"settings":true,"html":true,"up":true,"down":true,"duplicate":true,"delete":true}'
	&& builder._toolbar( model, [ 0 ] ).up === false && builder._toolbar( model, [ 0 ] ).down === true && builder._toolbar( model, [ 3 ] ).down === false && builder._toolbar( model, [ 3 ] ).up === true );
check( '...a block of html has no tool of its own to edit it as HTML+: its settings are the editor', builder._toolbar( model, [ 2 ] ).html === null && builder._toolbar( model, [ 2 ] ).settings === true && builder._toolbar( model, [ 2 ] ).duplicate === true );
check( '...a column and a component move inside their level: the first of two cannot go up, the last cannot go down', builder._toolbar( model, [ 1, 0 ] ).up === false && builder._toolbar( model, [ 1, 0 ] ).down === true && builder._toolbar( model, [ 1, 1 ] ).down === false
	&& builder._toolbar( model, [ 1, 1, 0 ] ).up === false && builder._toolbar( model, [ 1, 1, 3 ] ).down === false && builder._toolbar( model, [ 1, 1, 3 ] ).up === true && builder._toolbar( model, [ 1, 1, 0 ] ).html === null );
check( '...the only column of a section is not deleted: a section has one at least', builder._toolbar( model, [ 0, 0 ] ).delete === false && builder._toolbar( model, [ 1, 0 ] ).delete === true && builder._toolbar( model, [ 0, 0, 0 ] ).delete === true );
check( '...a loop is no frame and has no tools: it is set in the properties of its column', builder._toolbar( model, [ 1, 1, 'x' ] ) === null );
check( '...the template has no tools, and a path the model has nothing at has none to use', builder._toolbar( model, [] ) === null && builder._toolbar( model, [ 9, 0, 0 ] ).up === false && builder._toolbar( model, [ 9, 0, 0 ] ).delete === false );

check( 'what a frame holds that a delete would take with it: the components of a column and its loop, all of a section - a loop holds nothing of its own, it is the column\'s', builder._children( model, [ 0 ] ) === 3 && builder._children( model, [ 1 ] ) === 7 && builder._children( model, [ 0, 0 ] ) === 3
	&& builder._children( model, [ 1, 1 ] ) === 5 && builder._children( model, [ 1, 1, 'x' ] ) === 0 && builder._children( model, [ 0, 0, 0 ] ) === 0 && builder._children( model, [ 2 ] ) === 0 && builder._children( model, [ 9 ] ) === 0 );
check( '...a section with nothing in it, as a new one is, holds nothing', builder._children( { blocks : [ builder._newSection( model ) ] }, [ 0 ] ) === 0 );

console.log( '\nThe loop\'s name, the animation of the template and of a section, the order of a loop, the widths of a column' );

check( 'the plain stack of the kernel is called the loop, the others by the label they registered', builder._stackLabel( registry(), 'stack' ) === 'Element loop' && builder._stackLabel( registry(), 'slider' ) === 'Slider' && builder._stackLabel( registry(), 'nothing' ) === 'nothing' );

check( 'an effect and its strength are one word of the model, and the plain effect, which has neither, is none', builder._effectJoin( 'zoom', 'soft' ) === 'zoom-soft' && builder._effectJoin( 'zoom-out', 'hard' ) === 'zoom-out-hard' && builder._effectJoin( 'slide-left', 'medium' ) === 'slide-left-medium'
	&& builder._effectJoin( '', 'hard' ) === '' );
check( '...and the word is taken apart again, the effects with a hyphen in them included: every effect with every strength reads back as itself', JSON.stringify( builder._effectSplit( 'zoom-out-soft' ) ) === '{"effect":"zoom-out","strength":"soft"}'
	&& JSON.stringify( builder._effectSplit( '' ) ) === '{"effect":"","strength":""}' && JSON.stringify( builder._effectSplit( null ) ) === '{"effect":"","strength":""}' && JSON.stringify( builder._effectSplit( 'blur' ) ) === '{"effect":"","strength":""}'
	&& builder.EFFECTS.slice( 1 ).every( function( effect ) {
		return builder.STRENGTHS.every( function( strength ) {
			const split = builder._effectSplit( builder._effectJoin( effect, strength ) );
			return split.effect === effect && split.strength === strength;
		} );
	} ) );

check( 'the animation of a section or a column is read from its classes, and written in the order the Writer writes them', JSON.stringify( builder._vpaParse( 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium' ) ) === '{"vpa":"zoom-soft","vpaSpeed":"medium","vpaMode":""}'
	&& JSON.stringify( builder._vpaParse( 'nino-vpa--repeat nino-vpa--speed-slow nino-vpa--blur-hard' ) ) === '{"vpa":"blur-hard","vpaSpeed":"slow","vpaMode":"repeat"}' && JSON.stringify( builder._vpaParse( 'nino-vpa' ) ) === '{"vpa":"","vpaSpeed":"","vpaMode":""}'
	&& JSON.stringify( builder._vpaParse( null ) ) === '{"vpa":null,"vpaSpeed":"","vpaMode":""}' && builder._vpaClasses( { vpa : 'zoom-soft', vpaSpeed : 'medium', vpaMode : 'repeat' } ) === 'nino-vpa nino-vpa--zoom-soft nino-vpa--speed-medium nino-vpa--repeat'
	&& builder._vpaClasses( { vpa : '', vpaSpeed : '', vpaMode : '' } ) === 'nino-vpa' && builder._vpaClasses( { vpa : null, vpaSpeed : 'fast', vpaMode : '' } ) === null );
check( '...whatever a file says reads back as itself', [ 'nino-vpa', 'nino-vpa nino-vpa--zoom-soft', 'nino-vpa nino-vpa--blur-hard nino-vpa--speed-fast nino-vpa--repeat', 'nino-vpa nino-vpa--speed-slow' ].every( function( classes ) { return builder._vpaClasses( builder._vpaParse( classes ) ) === classes } ) );

// A section as the dialog sees it: the animation of its classes, and its own delay and duration
const animated = function( vpa, more ) { return Object.assign( { vpa : vpa, vpaSpeed : '', vpaMode : '', vpaDelay : '', vpaDuration : '' }, more || {} ) };
const holds = function( animate, rows ) {
	return rows.every( function( row ) {
		const mode = builder._animationMode( animate, row[0] );
		if( mode !== row[1] )
			console.log( '      animate '+ animate+ ', '+ JSON.stringify( row[0] )+ ': '+ mode+ ', not '+ row[1] );
		return mode === row[1];
	} );
};

check( 'with a template that animates its sections a section is off with no animation, like the template with the bare one - no effect, no speed, no mode, no delay, no duration - and has one of its own with anything else', holds( true, [
	[ animated( null ), 'off' ], [ animated( null, { vpaSpeed : 'fast' } ), 'off' ],
	[ animated( '' ), 'like' ],
	[ animated( '', { vpaSpeed : 'fast' } ), 'own' ], [ animated( '', { vpaMode : 'repeat' } ), 'own' ], [ animated( '', { vpaDelay : '200ms' } ), 'own' ], [ animated( '', { vpaDuration : '1s' } ), 'own' ],
	[ animated( 'zoom-soft' ), 'own' ], [ animated( 'blur-hard', { vpaSpeed : 'slow' } ), 'own' ],
] ) );
check( 'with a template that does not, none is like the template (which is off as well) and everything else is the section\'s own, the bare class among it', holds( false, [
	[ animated( null ), 'like' ], [ animated( null, { vpaDelay : '1s' } ), 'like' ],
	[ animated( '' ), 'own' ], [ animated( '', { vpaSpeed : 'slow' } ), 'own' ],
	[ animated( 'zoom-soft' ), 'own' ], [ animated( 'flip-medium', { vpaMode : 'repeat' } ), 'own' ],
] ) );

check( 'a section is made like the template, off or its own: like is the bare class where the template animates and none where it does not - its speed, mode, delay and duration go -, off is none, own starts as the plain one', ( function() {
	const settings = animated( 'blur-hard', { vpaSpeed : 'fast', vpaMode : 'repeat', vpaDelay : '1s', vpaDuration : '2s' } );
	const state = function() { return JSON.stringify( settings ) };
	builder._animationSet( true, settings, 'like' );
	const likeOn = state();
	builder._animationSet( true, settings, 'off' );
	const off = state();
	builder._animationSet( true, settings, 'own' );
	const own = state();
	settings.vpa = 'zoom-soft';
	builder._animationSet( true, settings, 'own' );
	const kept = settings.vpa;
	builder._animationSet( false, settings, 'like' );
	return likeOn === '{"vpa":"","vpaSpeed":"","vpaMode":"","vpaDelay":"","vpaDuration":""}' && off === '{"vpa":null,"vpaSpeed":"","vpaMode":"","vpaDelay":"","vpaDuration":""}' && own === likeOn && kept === 'zoom-soft'
		&& state() === off && builder._animationMode( false, settings ) === 'like';
} )() );

check( 'a new section is made with the animation of the template: the bare class where it animates its sections, none where it does not - and it is like the template either way, with nothing said of the width, the place and the padding of its row, no arrow and no offset of the picture of its row, the settings in the order the Reader reads them', ( function() {
	const off = page();
	const on = page();
	on.animate = true;
	const first = builder._newSection( on );
	const second = builder._newSection( off );
	return first.settings.vpa === '' && second.settings.vpa === null && builder._animationMode( true, first.settings ) === 'like' && builder._animationMode( false, second.settings ) === 'like'
		&& [ 'row', 'rowPlace', 'rowPl', 'rowPr', 'rowPt', 'rowPb', 'rowBgPl', 'rowBgPr' ].every( function( key ) { return first.settings[key] === '' && second.settings[key] === '' } ) && first.settings.arrow === false && second.settings.arrow === false
		&& first.settings.vpaSpeed === '' && first.settings.vpaDelay === ''
		&& Object.keys( first.settings ).join() === Object.keys( fixture('page-home.json').blocks[0].settings ).join();
} )() );
check( '...and a new column has the keys of the Reader\'s, in the order it reads them, the offsets of its picture among them as none', Object.keys( builder._newCol() ).join() === Object.keys( fixture('page-home.json').blocks[0].cols[0] ).join()
	&& builder._newCol().bgPl === '' && builder._newCol().bgPr === '' );

check( 'the sections that are like the template are found - and, when the template\'s switch is turned, follow it: on, the ones with no animation get the bare class; off, the ones that have just that lose it. The others stay as they are', ( function() {

	const turned = function( animate, sections ) {
		const model = page();
		model.animate = animate;
		model.blocks = sections.map( function( settings, at ) { return Object.assign( builder._newSection( model ), { id : 's'+ at, settings : Object.assign( builder._newSection( model ).settings, settings ) } ) } ).concat( [ model.blocks[2] ] );
		const before = builder._likeSections( model );
		model.animate = animate === false;
		builder._followSections( model, before );
		return { before : before.join(), after : model.blocks.filter( function( block ) { return block.kind === 'section' } ).map( function( block ) { return JSON.stringify( block.settings.vpa )+ builder._animationMode( model.animate, block.settings ) } ).join() };
	};

	// Switched on: none (like the template) gets the bare class; the section with an effect, the plain one and the one with a speed are their own
	const on = turned( false, [ { vpa : null }, { vpa : 'zoom-soft' }, { vpa : '' }, { vpa : null }, { vpa : '', vpaSpeed : 'fast' } ] );
	// Switched off: the bare one is like the template and loses it; none (off) stays none and becomes like the template (off) by it
	const off = turned( true, [ { vpa : '' }, { vpa : null }, { vpa : 'flip-hard' }, { vpa : '' }, { vpa : '', vpaDelay : '1s' } ] );

	return on.before === '0,3' && on.after === '""like,"zoom-soft"own,""like,""like,""own' && off.before === '0,3' && off.after === 'nulllike,nulllike,"flip-hard"own,nulllike,""own';
} )() );
check( 'the order of a loop is a field and a direction, written as the loop reads them: a minus for descending, none for no field - and a list of fields is left as it is', ( function() {
	const stack = { attributes : { sort : 'title' } };
	const was = JSON.stringify( builder._sortState( stack ) );
	builder._sortSet( stack, 'summary', true );
	const down = stack.attributes.sort;
	builder._sortSet( stack, 'summary', false );
	const up = stack.attributes.sort;
	builder._sortSet( stack, '', true );
	return was === '{"field":"title","descending":false,"several":false}' && down === '-summary' && up === 'summary' && stack.attributes.sort === ''
		&& JSON.stringify( builder._sortState( { attributes : { sort : '-date' } } ) ) === '{"field":"date","descending":true,"several":false}' && builder._sortState( { attributes : { sort : 'a,-b' } } ).several === true
		&& builder._sortState( { attributes : {} } ).field === '';
} )() );

check( 'the width of a column in a viewport is a number, and hidden is an entry for the viewports that hide it only', ( function() {
	const col = builder._newCol();
	builder._setWidth( col, 's', '50' );
	builder._setWidth( col, 'l', 25 );
	builder._setHidden( col, 'm', true );
	builder._setHidden( col, 's', true );
	builder._setHidden( col, 's', false );
	return JSON.stringify( col.width ) === '{"s":50,"m":100,"l":25}' && JSON.stringify( col.hidden ) === '{"m":true}';
} )() );

console.log( '\nNames and keys' );

model = page();
const hero = model.blocks[0];
check( 'a word is a segment: lower case, a hyphen for the rest', builder._segment( 'Hello World!' ) === 'hello-world' && builder._segment( '  --Title_2 ' ) === 'title-2' && builder._segment( '###' ) === '' );
check( 'a new key is named by the kind of the component, and numbered where the section has the name (title, title-2)', builder._keyName( hero, 'page-home', 'title' ) === 'title-2' && builder._keyName( hero, 'page-home', 'button' ) === 'button'
	&& builder._keyName( hero, 'page-home', 'background' ) === 'background-2' && builder._keyName( model.blocks[3], 'page-home', 'title' ) === 'title-2' && builder._keyName( hero, 'page-home', '###' ) === 'item' );
check( '...and where the project has the key without the file knowing it', builder._keyName( hero, 'page-home', 'button', [ { key : '/template/page-home/hero/button' }, { key : '/template/page-home/hero/button-2' } ] ) === 'button-3' && builder._keyName( hero, 'page-home', 'text', [ { key : '/template/page-home/other/text' } ] ) === 'text' );
model = page();
model.blocks[1].cols[0].background = { slot : '/template/page-home/services/column-background', focus : null };
check( 'the picture behind a column has a name of its section: the names a new key may not take include it, and the next new one is numbered', builder._names( model.blocks[1], 'page-home', [] )['column-background'] === true && builder._keyName( model.blocks[1], 'page-home', 'column-background' ) === 'column-background-2'
	&& builder._nameProblem( model.blocks[1], 'page-home', 'column-background' ) === 'taken' );
model = page();
check( 'the key of a name is /template/<file>/<section>/<name>, and the grammar holds it', builder._keyUri( 'page-home', 'hero', 'cta' ) === '/template/page-home/hero/cta' && builder.KEY.test( '/template/page-home/hero/cta' ) === true
	&& builder.KEY.test( '/template/page-home/hero/Cta' ) === false && builder.KEY.test( '/template/page-home/hero' ) === false && builder.KEY.test( '/_nino/webpage/contact/name' ) === false && builder.KEY.test( '/template/a/b/c/d' ) === false );
check( 'a name that will not do says why: the grammar, or a name the section has', builder._nameProblem( hero, 'page-home', 'cta' ) === '' && builder._nameProblem( hero, 'page-home', 'Bad Name' ) === 'grammar' && builder._nameProblem( hero, 'page-home', '' ) === 'grammar'
	&& builder._nameProblem( hero, 'page-home', 'title' ) === 'taken' && builder._nameProblem( hero, 'page-home', 'cta', [ { key : '/template/page-home/hero/cta' } ] ) === 'taken' && builder._nameProblem( hero, 'page-home', 'a--b' ) === 'grammar' );

const reg = registry();
check( 'a new section has a free id, one column over the whole width, nothing in it', ( function() {
	const first = builder._newSection( model );
	model.blocks.push( first );
	const second = builder._newSection( model );
	return first.id === 'section' && second.id === 'section-2' && first.cols.length === 1 && JSON.stringify( first.cols[0].width ) === '{"s":100,"m":100,"l":100}' && first.cols[0].components.length === 0 && first.settings.vpa === null && first.background === null;
} )() );
check( 'a new component has the attributes of its schema, and in a static column a key of its own that is made at save', ( function() {
	const title = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'title', [] );
	const image = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'image', [] );
	const html = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'html', [] );
	const spacer = builder._newComponent( model, reg, 'page-home', [ 0, 0 ], 'spacer', [] );
	return title.source === '/template/page-home/hero/title-2' && JSON.stringify( title.create ) === '{}' && title.values === undefined && title.attributes.tag === 'h3' && title.text === null
		&& image.source === '/template/page-home/hero/image' && image.create.label === 'Image' && image.create.width === 1600 && html.content === '' && html.source === '' && html.create === undefined && spacer.source === '' && spacer.create === undefined;
} )() );
check( '...and in a stack none: its source is a field, chosen in the form', builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).source === '' && builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).create === undefined );

	// A loop of sections owns the names <its id>-<n>: a new section beside a loop called section is not section-2, which the save would refuse, and a copy of the loop is not news-2
	const looped = { blocks : [ Object.assign( builder._newSection( model ), { id : 'section', stack : { name : 'sections', attributes : { id : 'section' } } } ), Object.assign( builder._newSection( model ), { id : 'news', stack : { name : 'sections', attributes : {} } } ) ], animate : false };
	check( 'the names a loop of sections makes for its own sections are taken: section-2 beside a loop called section, news-7 beside one called news, and a free name is free',
		builder._loopNames( looped ).join( ',' ) === 'section,news' && builder._sectionTaken( looped, 'section-2' ) === true && builder._sectionTaken( looped, 'news-7' ) === true && builder._sectionTaken( looped, 'news' ) === true && builder._sectionTaken( looped, 'intro' ) === false && builder._sectionTaken( looped, 'news-x' ) === false );
	check( '...so a new section beside a loop called section is section-new, which no loop makes, and the ids of a model without loops are taken by blocks alone', builder._newSection( looped ).id === 'section-new' && builder._loopNames( model ).length === 0 && /^section(-\d+)?$/.test( builder._newSection( model ).id ) === true );

console.log( '\nThe red source' );

model = page();
check( 'the example page has no source that means nothing where it stands', builder._red( model, reg ).length === 0 );
model.blocks[1].cols[1].stack = null;
check( 'a stack changed to static: every field of the element is red in that column - and nothing else', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return [ entry.path.join('.'), entry.source, entry.why ] } ) )
	=== JSON.stringify( [ [ '1.1.0', 'image', 'static' ], [ '1.1.1', 'title', 'static' ], [ '1.1.2', 'summary', 'static' ], [ '1.1.3', '.uri', 'static' ] ] ) );
model.blocks[1].cols[1].stack = { name : 'stack', source : '/services', attributes : { cols : '100 50 50' } };
check( 'and the stack back: nothing is red', builder._red( model, reg ).length === 0 );
model.blocks[1].cols[1].stack.source = '/team';
check( 'a stack of a type that has not the fields: the fields it has not are red, the ones it has (.uri, a key) are not', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return entry.source+ ':'+ entry.why } ) ) === JSON.stringify( [ 'image:field', 'title:field', 'summary:field' ] ) );
model.blocks[1].cols[1].stack.source = '/nowhere';
check( 'a stack of a type the Elements panel does not know is red itself, and its components are not judged by it', JSON.stringify( builder._red( model, reg ).map( function( entry ) { return entry.path.join('.')+ ':'+ entry.why } ) ) === JSON.stringify( [ '1.1.x:type' ] ) );
model.blocks[1].cols[1].stack.source = '/services';
model.blocks[1].cols[1].components[1].source = '/template/page-home/services/title';
model.blocks[1].cols[1].components[2].source = '';
check( 'a key stands anywhere, and no source is none', builder._red( model, reg ).length === 0 );
model.blocks[0].cols[0].components[0].source = 'title';
check( 'a field name in a column that has no stack is red, and nothing else on the page is', builder._red( model, reg ).length === 1 && builder._red( model, reg )[0].why === 'static' && builder._same( builder._red( model, reg )[0].path, [ 0, 0, 0 ] ) );

console.log( '\nThe preview' );

model = page();
const plan = function( viewport ) { return builder._preview( model, reg, viewport ) };
check( 'a section is a frame with its colour and whether a picture stands behind it, a block of html a frame of its own', plan('l').map( function( block ) { return block.kind+ ':'+ block.id+ ':'+ block.color+ ':'+ block.background } ).join()
	=== 'section:hero:black:true,section:services::false,html:::false,section:contact:primary:false' );
check( 'the columns have the width of the viewport', plan('l')[0].cols[0].width === 66 && plan('m')[0].cols[0].width === 100 && plan('s')[0].cols[0].width === 100 && plan('l')[1].cols.map( function( col ) { return col.width } ).join() === '50,50' );
model.blocks[1].cols[0].width = { s : 100, m : 50, l : 25 };
model.blocks[1].cols[0].hidden = { s : true, m : true };
check( 'and so has their visibility: a hidden column is hidden in its viewports, drawn all the same', plan('s')[1].cols[0].hidden === true && plan('m')[1].cols[0].hidden === true && plan('l')[1].cols[0].hidden === false
	&& plan('s')[1].cols[0].width === 100 && plan('m')[1].cols[0].width === 50 && plan('l')[1].cols[0].width === 25 && plan('s')[1].cols[0].components.length === 2 );
check( 'the view that shows all the viewports (g) has the widths of the widest, hides no column - and every view says in which viewports a column is hidden', plan('g')[0].cols[0].width === 66 && plan('g')[1].cols[0].width === 25 && plan('g')[1].cols[0].hidden === false
	&& plan('g')[1].cols[0].hiddenIn.join() === 's,m' && plan('s')[1].cols[0].hiddenIn.join() === 's,m' && plan('l')[1].cols[0].hiddenIn.join() === 's,m' && plan('l')[1].cols[1].hiddenIn.length === 0 );
check( 'a viewport changes widths and visibility and nothing else', ( function() {
	const strip = function( viewport ) {
		return JSON.stringify( plan( viewport ).map( function( block ) { return [ block.path, block.kind, block.id, block.color, block.cols.map( function( col ) { return [ col.path, col.components.map( function( component ) { return [ component.path, component.name, component.image ] } ) ] } ) ] } ) );
	};
	return strip('s') === strip('m') && strip('m') === strip('l') && strip('l') === strip('g');
} )() );
check( 'a component is a placeholder with the image the registry names - the kernel\'s five, and block for the rest', plan('l')[0].cols[0].components.map( function( component ) { return component.image+ ':'+ component.label } ).join() === 'title:Title (Section),title:Subtitle (Section),button:Button' );
model.blocks[0].cols[0].components.push( { name : 'unknown-one', source : '', text : null, attributes : {} } );
model.blocks[0].cols[0].components.push( { name : 'spacer', source : '', text : null, attributes : {} } );
check( '...a component the registry does not know and one that names no image are blocks, labelled with what they are', plan('l')[0].cols[0].components.slice( 3 ).map( function( component ) { return component.image+ ':'+ component.label } ).join() === 'block:unknown-one,block:Spacer' );
check( 'a loop is no frame of the preview: the column that runs it says so - the label of the loop, the type it runs over by the title the Elements panel gives it, where its settings are - and keeps its components, which are what each cell shows', ( function() {
	const stack = plan('l')[1].cols[1].stack;
	return JSON.stringify( Object.keys( stack ) ) === '["path","label","type"]' && stack.label === 'Element loop' && stack.type === 'Services' && builder._same( stack.path, [ 1, 1, 'x' ] )
		&& plan('s')[1].cols[1].stack.label === 'Element loop' && plan('l')[1].cols[0].stack === null && plan('l')[1].cols[1].components.length === 4;
} )() );
check( '...a type the Elements panel does not know, or that has no title there, is told by its uri', ( function() {
	model.blocks[1].cols[1].stack.source = '/nowhere';
	const unknown = plan('l')[1].cols[1].stack.type;
	const untitled = builder._preview( model, Object.assign( registry(), { types : [ { uri : '/nowhere', title : '', fields : {} } ] } ), 'l' )[1].cols[1].stack.type;
	model.blocks[1].cols[1].stack.source = '/services';
	return unknown === '/nowhere' && untitled === '/nowhere';
} )() );
model.blocks[1].cols[1].stack.name = 'slider';
check( '...a registered loop is called by the label it registered', plan('l')[1].cols[1].stack.label === 'Slider' );

console.log( '\nUnsaved changes and saving' );

model = page();
let doc = { file : 'page-home', hash : 'h1', model : model, saved : JSON.stringify( model ), usedBy : [] };
check( 'a document as loaded holds nothing unsaved, and none at all holds none', builder._unsaved( doc ) === false && builder._unsaved( null ) === false );
model.blocks[0].id = 'start';
check( 'a change makes it unsaved...', builder._unsaved( doc ) === true );
model.blocks[0].id = 'hero';
check( '...and the change taken back makes it saved again', builder._unsaved( doc ) === false );
model.blocks[0].cols[0].hidden.s = true;
check( 'a column made hidden is a change, and made visible again is none', builder._unsaved( doc ) === true && ( delete model.blocks[0].cols[0].hidden.s ) && builder._unsaved( doc ) === false );
check( 'the request of a save is the file, the model and the hash, and force only where it is asked', JSON.stringify( Object.keys( builder._saveRequest( doc, false ) ) ) === '["file","model","hash"]' && builder._saveRequest( doc, true ).force === true && builder._saveRequest( doc, true ).hash === 'h1' );
check( 'what an answer means: saved, a conflict, a model that is not valid with its problems, the rest an error', builder._outcome( 200, { model : {}, hash : 'x' } ).kind === 'saved' && builder._outcome( 409, { code : 'builder_conflict' } ).kind === 'conflict'
	&& builder._outcome( 400, { code : 'builder_invalid', params : [ 'a', 'b' ] } ).problems.join() === 'a,b' && builder._outcome( 400, { code : 'builder_value', params : [ 'c' ] } ).kind === 'invalid'
	&& builder._outcome( 409, { code : 'builder_key_exists', params : [ 'k' ] } ).kind === 'error' && builder._outcome( 409, { code : 'builder_key_exists', params : [ 'k' ] } ).problems.join() === 'k'
	&& builder._outcome( 500, null ).kind === 'error' && builder._outcome( 200, null ).kind === 'error' && builder._outcome( 403, { code : 'builder_permission' } ).kind === 'error' );
check( 'a conflict is answered by Reload (the file as it is, the changes dropped), by Save anyway (the same model, forced), or by Cancel (nothing)', JSON.stringify( builder._conflictRequest( doc, 'reload' ) ) === JSON.stringify( { action : 'builder/load', payload : { file : 'page-home' } } )
	&& builder._conflictRequest( doc, 'force' ).action === 'builder/save' && builder._conflictRequest( doc, 'force' ).payload.force === true && builder._conflictRequest( doc, 'force' ).payload.model === model && builder._conflictRequest( doc, 'cancel' ) === null );
check( 'a refused save names its blocks by the id of the section or the number of the block', ( function() {
	const blame = builder._blame( model, [ 'the section "services": the source "x" means nothing', 'block 3 is neither a section nor a block of html', 'the name is one line', 'the section "gone" is not valid', 'block 9 is no block' ] );
	return blame.blocks[1].length === 1 && blame.blocks[2].length === 1 && blame.general.length === 3 && blame.blocks[0] === undefined;
} )() );
check( 'a reason is said in the words of the panel, with its line and its detail', builder._reasonText( { line : 3, code : 'second-row', detail : '', text : 'x' } ) === 'Line 3: The section has more than one row'
	&& builder._reasonText( { line : 7, code : 'section-attribute', detail : 'onclick', text : 'x' } ) === 'Line 7: The section has the attribute onclick, which the Builder does not keep'
	&& builder._reasonText( { line : 0, code : 'new-code', detail : '', text : 'its own sentence' } ) === 'its own sentence' );
check( 'an error is said in the panel\'s own sentence for the code the server gave, else the workbench\'s', builder._errorText( 409, { code : 'builder_conflict' }, '/x' ) === words['/_admin/builder/error/conflict']
	&& builder._errorText( 409, { code : 'builder_key_exists' }, '/x' ) === words['/_admin/builder/error/key-exists'] && builder._errorText( 500, { code : 'builder_nothing_like_it' }, '/x' ) === '(500) /x' && builder._errorText( 500, null, '/x' ) === '(500) /x' );

console.log( '\nThe panel at work' );

const registered = sandbox.Nino.admin.dirty.entry;
check( 'the panel registers with the shell\'s unsaved input: a question of whether, a save, a discard, the bar', registered !== null && typeof registered.isDirty === 'function' && typeof registered.save === 'function' && typeof registered.discard === 'function' && typeof registered.bar === 'function' );

// What the script wires once, when the page is there
builder._bind();

// What stands where the page would draw: the document, as _openEditor() makes it, with no dialog open and nothing asked or sent
const open = function( withModel ) {

	const loaded = withModel === undefined ? page() : withModel;

	builder._registry = registry();
	builder._locale = builder._nativeLocale( builder._registry );
	builder._doc = { file : 'page-home', hash : 'h1', model : loaded, saved : JSON.stringify( loaded ), usedBy : [ { route : 'GET://', uri : '/home' }, { route : 'GET://about', uri : '/about' } ] };
	builder._sel = [];
	builder._fresh = {};
	builder._problems = [];
	builder._saving = false;
	builder._keys = known();
	builder._status = sandbox.Nino.adminUi.status();
	builder._dialogOptions = null;
	builder._dialogOpener = null;
	builder._closing = 0;
	builder._viewport = 'l';
	builder._selectView('l');
	dialogBox().open = false;
	dialogBox().previouslyFocused = null;
	pickerBox().open = false;
	pickerBox().previouslyFocused = null;
	requests = [];
	questions = [];
	changes = 0;

	return builder._doc;
};

// A change of the model is counted
const afterwards = builder._changed;
builder._changed = function() { changes++; afterwards.call( builder ) };
doc = open();
builder._renderEditor();
check( 'the editor draws itself from the model: the preview, the bar, without a page to complain', builder._doc.model.blocks.length === 4 && builder._status.state === 'idle' && builder._unsaved( builder._doc ) === false && preview().children.length === 4 );

const headsOf = function() { return preview().querySelectorAll('.builder-frame-head, .builder-pcol-head') };
check( 'every frame has a head of three parts, in this order: its title, what is said of it, its tools - the sections, the block of html and the columns', headsOf().length === 8
	&& headsOf().every( function( head ) { return classes( head.children ) === 'builder-head-title,builder-head-status,builder-tools' } ) );
check( '...the title of a section is its name, that of a block of html is HTML+, and that of a column is its width in the viewport', texts( preview().querySelectorAll('.builder-frame-name') ) === 'hero,services,HTML+,contact'
	&& texts( preview().querySelectorAll('.builder-col-name') ) === '66%,50%,50%,100%' );
check( '...a section has a pencil right of its name, which is a button with the word for its title and its label - the block of html and the columns have none', preview().querySelectorAll('.builder-rename').length === 3
	&& preview().querySelectorAll('.builder-rename').every( function( pencil ) {
		return pencil.localName === 'button' && pencil.title === 'Rename' && pencil.getAttribute('aria-label') === 'Rename' && iconOf( pencil ) === 'pencil' && pencil.previousSibling.className === 'builder-frame-name';
	} ) && preview().children[2].querySelector('.builder-rename') === null && preview().querySelector('.builder-pcol-head .builder-rename') === null );
check( '...the tools of a frame are the last part of its head', headsOf().every( function( head ) { return head.children[2].querySelectorAll('button').length > 0 } )
	&& headsOf()[0].children[2].querySelectorAll('button').map( function( button ) { return button.dataset.tool } ).join() === 'settings,html,up,down,duplicate,delete' );
check( '...what is said of a section is a picture behind it, as an icon with its word for the title and the label: the section with one says it, the others do not', ( function() {
	const status = preview().children[0].querySelector('.builder-frame-head .builder-head-status');
	const item = status.querySelector('.is-background');
	return status.children.length === 1 && item.localName === 'span' && item.title === 'A picture behind the section' && item.getAttribute('role') === 'img' && item.getAttribute('aria-label') === item.title && iconOf( item ) === 'image'
		&& preview().children[1].querySelector('.builder-frame-head .builder-head-status').children.length === 0;
} )() );

const loopItem = function() { return preview().querySelector('.builder-status-item.is-loop') };
check( 'the loop is no frame of its own: the column that runs it says "Element loop · <type>" in its head, as a button that has the settings of the loop for its title - and the components sit directly in the column', ( function() {
	const item = loopItem();
	const col = preview().querySelector('[data-path="1.1"]');
	return preview().querySelectorAll('.builder-status-item.is-loop').length === 1 && item.localName === 'button' && item.textContent === 'Element loop · Services' && item.title === 'Settings of the loop: Element loop · Services' && iconOf( item ) === 'loop'
		&& col.querySelector('.builder-head-status').contains( item ) && classes( col.querySelector('.builder-col-body').children ) === 'builder-ph builder-pick,builder-ph has-draws builder-pick,builder-ph builder-pick,builder-ph has-draws builder-pick,builder-adds'
		&& preview().all().filter( function( el ) { return el.classList.contains('builder-pick') && /\.x$/.test( el.dataset.path ?? '' ) } ).length === 0 && preview().querySelector('.builder-stack') === null;
} )() );
check( '...its title carries the status as well, since in a narrow column the text of the button is cut off and the title is not: the settings of the loop, a colon and the status - and the words of the title have a place for it in both languages', ( function() {
	const german = /^\s*'\[\[\/_admin\/builder\/menu\/loop\]\]'\s*=>\s*'(.*)',\s*$/m.exec( fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' ) );
	return loopItem().title === 'Settings of the loop: '+ loopItem().textContent && words['/_admin/builder/menu/loop'].split('%s').length === 2 && german !== null && german[1].split('%s').length === 2;
} )() );
check( '...and the loop is set in the properties of the column: the status opens the column dialog on it', ( function() {
	loopItem().click();
	const opened = dialogBox().open === true && openBlock() === 'properties' && dialogText('builder-dialog-kind') === 'Column of "services"' && builder._same( builder._sel, [ 1, 1, 'x' ] );
	dialogBox().open = false;
	return opened;
} )() );

check( 'every level ends in a button that adds to it - a section a column, a column a component - and there is no button for a loop', ( function() {
	const adds = preview().querySelectorAll('.builder-add-button').map( function( button ) { return button.textContent } );
	return adds.filter( function( text ) { return text === '+ Component' } ).length === 4 && adds.filter( function( text ) { return text === '+ Column' } ).length === 3 && adds.length === 7 && adds.every( function( text ) { return /loop/i.test( text ) === false } );
} )() );
check( '...the end of a column is a button for a component and nothing else - where the column has a loop as well as where it has none', ( function() {
	const plain = builder._addsRow( [ 0, 0 ] );
	const looped = builder._addsRow( [ 1, 1 ] );
	return plain.children.length === 1 && looped.children.length === 1 && plain.children[0].textContent === '+ Component' && looped.children[0].textContent === '+ Component';
} )() );
check( '...and a component is chosen in a lightbox, which opens in the dialog and lists every component of the registry: what belongs where the column is can be added, the rest stands there greyed - the first section is the hero, anywhere else is a section, the cell of a loop among it, which holds no place of its own, and what names no place belongs in all of them; the registry\'s "in" says, and the list is the registry\'s, as it has it', ( function() {

	const count = itemsOf( pickAt( [ 1, 0 ] ) ).length;

	return belonging( 'hero' ) !== '' && belonging( 'section' ) !== '' && offeredAt( [ 0, 0 ] ) === belonging( 'hero' ) && offeredAt( [ 1, 0 ] ) === belonging( 'section' )
		&& offeredAt( [ 1, 1 ] ) === offeredAt( [ 1, 0 ] ) && offeredAt( [ 3, 0 ] ) === offeredAt( [ 1, 0 ] ) && count === Object.keys( registryFixture.components ).length
		&& dialogBox().open === true && dialogBox().classList.contains('is-wide') && dialogText('builder-dialog-kind') === 'Insert a component' && dialogContent().querySelector('.builder-blocks') === null;
} )() );
check( '...the lightbox is in groups, each with a heading: what the kernel and the modules of a project registered by the group its schema names - the words of the registry, in the order the lightbox lists them - and then the Features for what a feature registered, each in the order of the registry; a group with nothing in it is not drawn, and a feature has a heading of its own where there is more than one', ( function() {

	const components = builder._registry.components;
	const groups = function() { return pickAt( [ 1, 0 ] ).children.map( function( group ) { return group.dataset.group+ ':'+ itemsOf( group ).map( pickLabel ).join() } ).join(' | ') };
	// What the registry says each group holds, in its own order: the labels of the components of the kernel that name it
	const labels = function( word ) { return Object.keys( components ).filter( function( name ) { return components[name].by === 'nino' && components[name].group === word } ).map( function( name ) { return components[name].label } ).join() };
	// ...and the words of a list that have such a component: the groups the lightbox draws, whichever and however many the registry has
	const filled = function( words ) { return words.filter( function( word ) { return labels( word ) !== '' } ) };
	const listed = function( words ) { return filled( words ).map( function( word ) { return word+ ':'+ labels( word ) } ).join(' | ') };
	const heading = function( box ) { return box.querySelectorAll('.builder-lightbox-sub').map( function( sub ) { return sub.textContent } ).join() };
	const headings = function() { return pickAt( [ 1, 0 ] ).children.map( function( group ) { return group.children[0].textContent } ).join() };
	const kernel = groups();
	const said = headings();

	builder._registry.features = { gallery : 'Gallery', forms : 'Forms' };
	components['stub-forms'] = { label : 'Stub forms', preview : 'block', by : 'forms' };
	components['stub-gallery'] = { label : 'Stub gallery', preview : 'block', by : 'gallery' };
	components['stub-more'] = { label : 'Stub more', preview : 'block', by : 'forms', in : [ 'card' ] };

	const several = groups();
	const named = heading( lightboxOf() );

	delete components['stub-gallery'];

	const one = groups();
	const alone = heading( lightboxOf() );

	delete components['stub-forms'];
	delete components['stub-more'];
	delete builder._registry.features;

	// A registry with none of the first group it has a component in: that group is not drawn
	const kept = builder._registry;
	const left = filled( groupWords )[0];

	builder._registry = Object.assign( {}, kept, { components : Object.keys( components ).filter( function( name ) { return components[name].group !== left } ).reduce( function( all, name ) { all[name] = components[name]; return all }, {} ) } );

	const without = groups();

	// The order is the registry's and none of the script's own: the list turned round turns the lightbox round
	builder._registry = Object.assign( {}, kept, { groups : groupWords.slice().reverse() } );

	const turned = groups();

	builder._registry = kept;

	return Array.isArray( groupWords ) && groupWords.length > 1 && turned !== kernel && turned === listed( groupWords.slice().reverse() ) && Object.keys( components ).every( function( name ) { return groupOf( name, components ) !== -1 } )
		&& kernel !== '' && kernel === listed( groupWords ) && said === filled( groupWords ).map( function( word ) { return words['/_admin/builder/pick/'+ word] } ).join() && said.indexOf(',,') === -1
		&& several === kernel+ ' | features:Stub forms,Stub more,Stub gallery' && named === 'Forms,Gallery' && one === kernel+ ' | features:Stub forms,Stub more' && alone === ''
		&& without !== kernel && without === listed( groupWords.filter( function( word ) { return word !== left } ) ) && groups() === kernel;
} )() );
check( '...a component of a feature stands under the feature whatever else its schema says - it is no group\'s: the words of a group are the kernel\'s and a project\'s, and what a feature registered goes last', ( function() {

	const components = builder._registry.components;

	components['stub-forms'] = { label : 'Stub forms', preview : 'block', by : 'forms', group : groupWords[0] };

	// Where the stub stands in each group that is drawn: in none of the registry's, and first in the Features, which come last
	const stands = pickAt( [ 1, 0 ] ).children.map( function( group ) { return [ group.dataset.group, itemsOf( group ).map( pickLabel ).indexOf( 'Stub forms' ) ] } );
	const last = stands.length - 1;

	delete components['stub-forms'];

	return last > 0 && stands[last][0] === 'features' && stands[last][1] === 0 && stands.slice( 0, last ).every( function( found ) { return groupWords.indexOf( found[0] ) !== -1 && found[1] === -1 } );
} )() );
check( '...they stand in the order of the registry and in none of the script\'s own: the components as the kernel registers them, then those of a feature in the order it registered them - and a registry in another order is listed in that order, not in the alphabet\'s', ( function() {

	const kept = builder._registry;
	const flipped = {};
	const kernel = offeredAt( [ 1, 0 ] );

	builder._registry = Object.assign( {}, kept, { components : Object.assign( {}, kept.components, { zulu : { label : 'Zulu', preview : 'block', by : 'hello' }, alpha : { label : 'Alpha', preview : 'block', by : 'hello' } } ) } );

	const featured = offeredAt( [ 1, 0 ] );

	Object.keys( kept.components ).reverse().forEach( function( name ) { flipped[name] = kept.components[name] } );
	builder._registry = Object.assign( {}, kept, { components : flipped } );

	const other = offeredAt( [ 1, 0 ] );

	builder._registry = kept;

	return kernel === belonging( 'section' ) && featured === kernel+ ',Zulu,Alpha' && other === belonging( 'section', flipped );
} )() );
check( '...a component that says where it belongs (its "in") can be added there and nowhere else - the hero is the first section, whether or not it is a cover, a section is any other column, the cell of a loop included -, one that says nothing can be added everywhere, and one a loop does not take can be added in no column that runs one: the script names no component', ( function() {

	const components = builder._registry.components;
	const blocks = builder._doc.model.blocks;
	const offered = function( path ) { return offeredAt( path ).split(',').filter( function( label ) { return /^Stub/.test( label ) } ).join() };

	components['stub-card'] = { label : 'Stub card', preview : 'block', in : [ 'card' ], group : 'card' };
	components['stub-hero'] = { label : 'Stub hero', preview : 'block', in : [ 'hero' ], group : 'hero' };
	components['stub-two'] = { label : 'Stub two', preview : 'block', in : [ 'section', 'card' ], group : 'section' };
	components['stub-any'] = { label : 'Stub any', preview : 'block', group : 'global' };
	components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false, group : 'global' };

	const found = [ offered( [ 0, 0 ] ), offered( [ 1, 0 ] ), offered( [ 1, 1 ] ), offered( [ 3, 0 ] ) ].join(' | ');

	// The first section is no cover: it is the hero all the same; the second is one, and is no hero for that
	blocks[0].settings.cover = null;
	blocks[1].settings.cover = 90;

	return found === 'Stub hero,Stub any,Stub alone | Stub two,Stub any,Stub alone | Stub two,Stub any | Stub two,Stub any,Stub alone'
		&& offered( [ 0, 0 ] ) === 'Stub hero,Stub any,Stub alone' && offered( [ 1, 0 ] ) === 'Stub two,Stub any,Stub alone' && offered( [ 1, 1 ] ) === 'Stub two,Stub any';
} )() );
check( '...what cannot go where the lightbox was opened is there all the same, greyed, and says why: a disabled button with the reason for its title and - for whoever does not see the grey - in a span of its own that only a screen reader reads; the reason is that it belongs in a card only, in the first section only or in other places, or that an element loop does not take it - and what can go is a button with nothing of the kind', ( function() {

	const components = builder._registry.components;
	const blocks = builder._doc.model.blocks;

	components['stub-hero'] = { label : 'Stub hero', preview : 'block', in : [ 'hero' ], group : 'hero' };
	components['stub-else'] = { label : 'Stub else', preview : 'block', in : [ 'footer' ], group : 'global' };
	components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false, group : 'global' };
	blocks[0].settings.cover = null;
	blocks[1].settings.cover = null;

	const item = function( path, label ) { return itemsOf( pickAt( path ) ).find( function( candidate ) { return pickLabel( candidate ) === label } ) };
	const grey = item( [ 1, 0 ], 'Stub hero' );
	const plain = item( [ 0, 0 ], 'Stub hero' );
	const found = [ reasonOf( [ 1, 0 ], 'Title (Card)' ), reasonOf( [ 1, 0 ], 'Stub hero' ), reasonOf( [ 0, 0 ], 'Stub hero' ), reasonOf( [ 1, 0 ], 'Stub else' ), reasonOf( [ 1, 1 ], 'Stub alone' ), reasonOf( [ 1, 0 ], 'Stub alone' ) ].join(' | ');

	return found === 'Only in a card | Only in the first section |  | Not offered here | Not in an element loop | ' && reasonOf( [ 1, 0 ], 'Nothing' ) === null
		&& grey.disabled === true && grey.title === 'Only in the first section' && grey.children.length === 3 && grey.children[2].localName === 'span' && grey.children[2].className === 'nino-admin-sr-only' && grey.children[2].textContent === grey.title
		&& plain.disabled === false && plain.title === '' && plain.children.length === 2 && plain.querySelector('.nino-admin-sr-only') === null;
} )() );
check( '...a greyed component takes no click: it is not added, the lightbox stays, and the document does not change', ( function() {

	const box = pickAt( [ 1, 0 ] );
	const cols = builder._doc.model.blocks[1].cols[0].components.length;
	const before = changes;
	const grey = itemsOf( box ).find( function( item ) { return item.disabled === true } );

	grey.click();

	return grey !== undefined && builder._doc.model.blocks[1].cols[0].components.length === cols && changes === before && lightboxOf() === box && dialogBox().open === true;
} )() );
builder._closeDialog();

doc = open();
doc.model.blocks[1].cols[0].hidden = { s : true, m : true };
doc.model.blocks[2].reason = { line : 3, code : 'second-row', detail : '', text : '' };
builder._renderEditor();
const hiddenItem = function() { return preview().querySelector('[data-path="1.0"] .builder-status-item.is-hidden') };
check( 'a column that is hidden in the viewport shown says so in its head as an icon - and is drawn all the same: not in the viewport that shows it, in which it is hidden with the word for it', hiddenItem() === null
	&& ( builder._setView('s'), hiddenItem() !== null && hiddenItem().getAttribute('aria-label') === 'Hidden in this viewport' && hiddenItem().title === 'Hidden in this viewport' && iconOf( hiddenItem() ) === 'hidden'
		&& preview().querySelector('[data-path="1.0"]').classList.contains('is-hidden') && preview().querySelector('[data-path="1.0"] .builder-ph') !== null ) );
check( '...in the view of all the viewports it is not hidden, and the head says in which of them it is', ( builder._setView('g'), preview().querySelector('[data-path="1.0"]').classList.contains('is-hidden') === false && hiddenItem().getAttribute('aria-label') === 'Hidden in: Mobile, Tablet' ) );
check( '...a block of html that cannot be read says why in its head', ( builder._setView('l'), preview().children[2].querySelector('.builder-head-status .is-warning').getAttribute('aria-label') === 'Line 3: The section has more than one row' ) );

doc = open();
doc.model.blocks[1].cols[0].text = 'center';
doc.model.blocks[1].cols[0].stackAlign = 'end';
builder._renderEditor();
const alignOf = function( path ) { return preview().querySelector('[data-path="'+ path+ '"] .builder-pcol-head .builder-head-status').querySelectorAll('.is-align') };
const alignList = function( path ) { return alignOf( path ).map( function( item ) { return item.localName+ ':'+ iconOf( item )+ ':'+ item.title+ ':'+ item.getAttribute('aria-label')+ ':'+ item.textContent } ).join('|') };
check( 'a column whose alignment is not the default says so in its head, with the icon of the choice and no text: the vertical alignment of its components, then the horizontal one - each a button with the alignment and its word for the title and the label', alignList('1.0')
	=== 'button:stack-end:Vertical alignment: End:Vertical alignment: End:|button:align-center:Horizontal alignment: Centre:Horizontal alignment: Centre:' );
check( '...the default shows nothing: a column that is left as it is has no item - and the one that has the horizontal alignment only has that one', alignOf('1.1').length === 0 && alignOf('3.0').length === 0 && alignList('0.0') === 'button:align-left:Horizontal alignment: Left:Horizontal alignment: Left:' );
check( '...the same in every view: the alignment is no property of a viewport, and the view of all of them says it too', [ 's', 'm', 'l', 'g' ].every( function( view ) { return builder._setView( view ), alignOf('1.0').length === 2 && alignOf('0.0').length === 1 } ) && ( builder._setView('l'), true ) );
check( '...the words of the titles have a place for the alignment in both languages', ( function() {
	const german = fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' );
	return [ 'align-text', 'align-stack' ].every( function( word ) {
		const found = new RegExp( "'\\[\\[/_admin/builder/preview/"+ word+ "\\]\\]'\\s*=>\\s*'(.*)'," ).exec( german );
		return words['/_admin/builder/preview/'+ word].split('%s').length === 2 && found !== null && found[1].split('%s').length === 2;
	} );
} )() );
check( '...a click on it opens the dialog of the column on its block of the width and the alignment, with the column selected - wherever the dialog stood before', ( function() {
	alignOf('1.0')[1].click();
	const opened = dialogBox().open === true && dialogText('builder-dialog-kind') === 'Column of "services"' && openBlock() === 'width' && builder._same( builder._sel, [ 1, 0 ] );
	builder._closeDialog();
	builder._openSettings( [ 1, 0 ] );
	headOf('properties').click();
	alignOf('1.0')[0].click();
	return opened && openBlock() === 'width';
} )() );
builder._closeDialog();

doc = open();
doc.model.blocks[1].cols[1].background = { slot : '/template/page-home/services/column-background', focus : null };
builder._renderEditor();
check( 'a column with a picture behind it says so in its head with the item the section has for it - the icon of an image and the word for a column, not the section\'s -, a column without one says nothing, and the section, which has none, says nothing either', ( function() {
	const item = preview().querySelector('[data-path="1.1"] .builder-pcol-head .is-background');
	return item !== null && item.localName === 'span' && item.title === 'A picture behind the column' && item.title !== words['/_admin/builder/preview/background'] && item.getAttribute('role') === 'img' && item.getAttribute('aria-label') === item.title && iconOf( item ) === 'image'
		&& preview().querySelector('[data-path="1.0"] .builder-pcol-head .is-background') === null && preview().children[1].querySelector('.builder-frame-head .is-background') === null;
} )() );
check( '...and the German file has the word for a column as well, one of its own', ( function() {
	const german = fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' );
	const column = /^\s*'\[\[\/_admin\/builder\/preview\/column-background\]\]'\s*=>\s*'(.*)',\s*$/m.exec( german );
	const section = /^\s*'\[\[\/_admin\/builder\/preview\/background\]\]'\s*=>\s*'(.*)',\s*$/m.exec( german );
	return column !== null && section !== null && column[1] === 'Ein Bild hinter der Spalte' && column[1] !== section[1];
} )() );

const tintOf = function( around, color ) {
	doc = open();
	builder._doc.model.blocks[1].settings.color = around;
	builder._doc.model.blocks[1].cols[0].color = color;
	builder._renderEditor();
	return preview().querySelector('[data-path="1.0"]').className.split(' ').filter( function( name ) { return name.indexOf('builder-color-') === 0 } ).join();
};
check( 'a column that paints a ground has its frame tinted as the frame of a section is - builder-color-<ground> - and one with none of its own has no tint of its own', tintOf( '', '' ) === '' && tintOf( '', 'alt' ) === 'builder-color-alt' && tintOf( '', 'tint' ) === 'builder-color-tint' && tintOf( '', 'dark' ) === 'builder-color-dark'
	&& tintOf( 'dark', 'black' ) === 'builder-color-black' && tintOf( 'alt', 'primary' ) === 'builder-color-primary' && tintOf( '', 'brand-alt' ) === 'builder-color-brand-alt' && preview().children[1].classList.contains('builder-color-plain') );
check( '...counter is the counter of the ground of the section, by the pairs of Nino.css: default and alt, tint to default, dark and black, primary and brand-alt - and the default is the plain frame', tintOf( '', 'counter' ) === 'builder-color-alt' && tintOf( 'alt', 'counter' ) === 'builder-color-plain' && tintOf( 'tint', 'counter' ) === 'builder-color-plain'
	&& tintOf( 'dark', 'counter' ) === 'builder-color-black' && tintOf( 'black', 'counter' ) === 'builder-color-dark' && tintOf( 'primary', 'counter' ) === 'builder-color-brand-alt' && tintOf( 'brand-alt', 'counter' ) === 'builder-color-primary' );
check( '...the section keeps its own ground, whatever its columns paint', ( tintOf( 'dark', 'counter' ), preview().children[1].classList.contains('builder-color-dark') && preview().children[1].classList.contains('builder-color-black') === false ) );

const dimTintOf = function( around, color, dim, picture ) {
	doc = open();
	builder._doc.model.blocks[1].settings.color = around;
	builder._doc.model.blocks[1].cols[0].color = color;
	builder._doc.model.blocks[1].cols[0].dim = dim;
	builder._doc.model.blocks[1].cols[0].background = picture === true ? { slot : '/template/page-home/services/column-background', focus : null } : null;
	builder._renderEditor();
	return preview().querySelector('[data-path="1.0"]').className.split(' ').filter( function( name ) { return name.indexOf('builder-color-') === 0 } ).join();
};
check( '...a column whose picture is dimmed is tinted dark, whatever colour it names - none, one of its own, counter - and whatever the section is, as Nino.css paints it', dimTintOf( '', '', true, true ) === 'builder-color-dark' && dimTintOf( '', 'alt', true, true ) === 'builder-color-dark'
	&& dimTintOf( 'tint', 'counter', true, true ) === 'builder-color-dark' && dimTintOf( 'dark', 'black', true, true ) === 'builder-color-dark' && dimTintOf( 'primary', 'primary', true, true ) === 'builder-color-dark' && dimTintOf( 'dark', 'counter', true, true ) === 'builder-color-dark' );
check( '...a dimming without a picture is no ground, and a picture that is not dimmed paints nothing: the frame is tinted by the colour the column names, or by none', dimTintOf( '', '', true, false ) === '' && dimTintOf( '', 'alt', true, false ) === 'builder-color-alt'
	&& dimTintOf( 'dark', 'counter', true, false ) === 'builder-color-black' && dimTintOf( '', '', false, true ) === '' && dimTintOf( '', 'alt', false, true ) === 'builder-color-alt' && dimTintOf( 'dark', 'counter', false, true ) === 'builder-color-black' );
check( '...the stylesheet tints the frame of a column as it tints the frame of a section, and the frame of a card and the body of a section whose background is inside the same: the same ground for each colour, painted as a colour alone so that the stripes of a hidden column stay, and the plain ground for the counter of a light one', [ 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ].every( function( color ) {
	return stylesheet.indexOf( '.builder-frame.builder-color-'+ color+ ',\n.builder-frame-body.builder-color-'+ color+ ',\n.builder-pcol.builder-color-'+ color+ ',\n.builder-card.builder-color-'+ color+ ' {\n\tbackground-color: var(--builder-ground-'+ color+ ');' ) !== -1;
} ) && stylesheet.indexOf( '.builder-pcol.builder-color-plain,\n.builder-card.builder-color-plain {\n\tbackground-color: var(--builder-ground-plain);' ) !== -1 && /\.builder-pcol\.is-hidden \{[^}]*background-image/.test( stylesheet ) );

check( 'the preview is shown in four views: Mobile, Tablet, Desktop and Global, each a button with its icon from the sprite and its word', ( function() {
	const buttons = builder.VIEWS.map( function( view ) { return screen.getElementById( 'builder-viewport-'+ view ) } );
	return builder.VIEWS.join() === 's,m,l,g' && buttons.map( iconOf ).join() === 'smartphone,tablet,monitor,monitor-smartphone' && texts( buttons ) === 'Mobile,Tablet,Desktop,Global' && buttons.every( function( button ) { return button.title === button.textContent } );
} )() );
check( '...pressing one shows the preview in it: the button is the one that is pressed, the others are not', ( function() {
	const pressed = function() { return builder.VIEWS.map( function( view ) { return screen.getElementById( 'builder-viewport-'+ view ).getAttribute('aria-pressed') } ).join() };
	const start = pressed();
	screen.getElementById('builder-viewport-g').click();
	const global = builder._viewport === 'g' && preview().dataset.viewport === 'g' && pressed() === 'false,false,false,true';
	screen.getElementById('builder-viewport-s').click();
	return start === 'false,false,true,false' && global && builder._viewport === 's' && preview().dataset.viewport === 's' && pressed() === 'true,false,false,false';
} )() );
check( 'a double click selects no text: the second mousedown of it is prevented on every frame that can be picked - a section, a column, a component - where the browser would select the word under the pointer, and so is the third; the first and a plain one are not, so that a click and a drag select as they did - and the double click goes on to open the form', ( function() {

	doc = open();
	builder._renderEditor();

	const frames = preview().all().filter( function( el ) { return el.classList.contains('builder-pick') } );
	const prevented = function( detail ) { return frames.filter( function( el ) { return el.dispatch( 'mousedown', detail === undefined ? {} : { detail : detail } ).defaultPrevented === true } ).length };
	const counted = [ prevented( 1 ), prevented( 2 ), prevented( 3 ), prevented() ].join();

	preview().querySelector('[data-path="0.0.0"] .builder-ph-label').dispatch('dblclick');

	return frames.length === 20 && counted === '0,20,20,0' && dialogBox().open === true && dialogText('builder-dialog-kind') === 'Component "Title (Section)"';
} )() );
builder._closeDialog();
console.log( '\nRenaming a section where its name stands' );

doc = open();
builder._renderEditor();
const pencilOf = function( at ) { return preview().children[at].querySelector('.builder-rename') };
const frameName = function( at ) { return preview().children[at].querySelector('.builder-frame-name') };
const fieldOf = function( at ) { return preview().children[at].querySelector('.builder-name-input') };

pencilOf( 0 ).click();
check( 'the pencil turns the name into a field in the place it stands: the field holds the name and has the focus, the name and the pencil are out of sight', fieldOf( 0 ) !== null && fieldOf( 0 ).value === 'hero' && screen.activeElement === fieldOf( 0 ) && frameName( 0 ).hidden === true && pencilOf( 0 ).hidden === true
	&& fieldOf( 0 ).nextSibling === frameName( 0 ) && fieldOf( 0 ).getAttribute('aria-label') === 'Id' && builder._doc.model.blocks[0].id === 'hero' );
check( '...a click in the field is not a click on the frame', ( function() {
	builder._sel = [];
	fieldOf( 0 ).click();
	return builder._same( builder._sel, [] );
} )() );
check( '...nor is the mousedown of a double click in it: the frame keeps that one from selecting a word, and a word of the name is what a double click in the field is for', fieldOf( 0 ).dispatch( 'mousedown', { detail : 2 } ).defaultPrevented === false
	&& fieldOf( 0 ).parentNode.dispatch( 'mousedown', { detail : 2 } ).defaultPrevented === true );
fieldOf( 0 ).value = 'start';
const enter = pressKey( fieldOf( 0 ), 'Enter' );
check( 'Enter takes the name: the field is gone, the name and the pencil are back, the pencil has the focus - and a section with keys under its name is asked first, since they move with it', enter.defaultPrevented === true && fieldOf( 0 ) === null && frameName( 0 ).hidden === false && pencilOf( 0 ).hidden === false
	&& screen.activeElement === pencilOf( 0 ) && questions.length === 1 && questions[0].title === 'Rename the section' && questions[0].message === 'Rename the section "hero" to "start"? Its text keys and image slots are moved to the new name when the template is saved.'
	&& questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'rename,cancel' && builder._doc.model.blocks[0].id === 'hero' && changes === 0 );
questions[0].onChoose( 'cancel' );
check( '...Cancel leaves the section as it is', builder._doc.model.blocks[0].id === 'hero' && builder._doc.model.blocks[0].renamedFrom === undefined && frameName( 0 ).textContent === 'hero' );
questions[0].onChoose( 'rename' );
check( '...Rename moves it, says where it was renamed from, and draws the preview again with the new name', builder._doc.model.blocks[0].id === 'start' && builder._doc.model.blocks[0].renamedFrom === 'hero' && frameName( 0 ).textContent === 'start' && changes === 1 && builder._unsaved( builder._doc ) === true );
check( '...and the focus stays with the pencil: the one of the preview that was drawn again, since the one that had it is gone', screen.activeElement === pencilOf( 0 ) && screen.activeElement.isConnected === true );

questions = [];
pencilOf( 0 ).click();
fieldOf( 0 ).value = 'elsewhere';
const escaped = pressKey( fieldOf( 0 ), 'Escape' );
check( 'Escape discards what was typed: the field is gone, the name is as it was, nothing is asked - and the Escape is not the one that closes the dialog the field may be in', escaped.defaultPrevented === true && escaped.stopped === true && fieldOf( 0 ) === null && frameName( 0 ).textContent === 'start' && frameName( 0 ).hidden === false
	&& questions.length === 0 && builder._doc.model.blocks[0].id === 'start' && screen.activeElement === pencilOf( 0 ) );

pencilOf( 1 ).click();
const leaving = fieldOf( 1 );
leaving.value = 'offer';
// The focus goes to the Save button, which has the field send its blur - and the browser may send a second one when the field is taken out of the page
screen.getElementById('builder-save').focus();
leaving.dispatch('blur');
check( 'leaving the field takes the name as Enter does - once: the field is gone and the question is asked', fieldOf( 1 ) === null && questions.length === 1 && frameName( 1 ).hidden === false
	&& questions[0].message === 'Rename the section "services" to "offer"? Its text keys and image slots are moved to the new name when the template is saved.' );
questions[0].onChoose( 'rename' );
check( '...and the focus is where the person took it: the preview drawn again for the new name does not take it to the pencil', screen.activeElement === screen.getElementById('builder-save') && frameName( 1 ).textContent === 'offer' );
check( '...and the way back to the name the section was saved with is no rename at all: nothing is asked - the keys have not moved yet - and the section is as it was saved', ( function() {
	const section = builder._doc.model.blocks[1];
	const was = section.renamedFrom === 'services' && section.id === 'offer';
	questions = [];
	pencilOf( 1 ).click();
	fieldOf( 1 ).value = 'services';
	pressKey( fieldOf( 1 ), 'Enter' );
	return was && questions.length === 0 && section.id === 'services' && section.renamedFrom === undefined && frameName( 1 ).textContent === 'services';
} )() );

doc = open();
builder._renderEditor();
const rename = function( at, text ) {
	pencilOf( at ).click();
	fieldOf( at ).value = text;
	pressKey( fieldOf( at ), 'Enter' );
};
rename( 1, 'Not A Slug' );
check( 'a name that is no slug is not taken, and the bar says so', builder._doc.model.blocks[1].id === 'services' && questions.length === 0 && builder._status.state === 'error' && builder._status.text === words['/_admin/builder/error/id-slug'] && fieldOf( 1 ) === null && frameName( 1 ).textContent === 'services' );
builder._status = sandbox.Nino.adminUi.status();
rename( 1, 'contact' );
check( '...nor is one another section has', builder._doc.model.blocks[1].id === 'services' && questions.length === 0 && builder._status.state === 'error' && builder._status.text === words['/_admin/builder/error/id-taken'] );
builder._status = sandbox.Nino.adminUi.status();
rename( 1, '' );
check( '...nor is none', builder._doc.model.blocks[1].id === 'services' && builder._status.text === words['/_admin/builder/error/id-slug'] );
builder._status = sandbox.Nino.adminUi.status();
rename( 1, 'services' );
check( '...and the name it has is no change and no complaint', builder._doc.model.blocks[1].id === 'services' && builder._status.state === 'idle' && questions.length === 0 && changes === 0 );

builder._addSection();
questions = [];
rename( 4, 'faq' );
check( 'a section made here that holds no key is renamed at once, without a question - and the pencil of the frame that was drawn for the new name has the focus', questions.length === 0 && builder._doc.model.blocks[4].id === 'faq' && builder._doc.model.blocks[4].renamedFrom === undefined && builder._fresh.faq === true && builder._fresh.section === undefined
	&& frameName( 4 ).textContent === 'faq' && screen.activeElement === pencilOf( 4 ) );
builder._addComponent( [ 4, 0 ], 'title' );
rename( 4, 'questions' );
check( '...one that holds a key is asked as a saved one is - and answered, its sources are written new under the new name: nothing is moved that was never made', ( function() {
	const asked = questions.length === 1;
	questions[0].onChoose( 'rename' );
	const component = builder._doc.model.blocks[4].cols[0].components[0];
	return asked && builder._doc.model.blocks[4].id === 'questions' && builder._doc.model.blocks[4].renamedFrom === undefined && component.source === '/template/page-home/questions/title' && JSON.stringify( component.create ) === '{}' && builder._fresh.questions === true;
} )() );

console.log( '\nRenaming a section in the title of its dialog' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
const heading = screen.getElementById('builder-dialog-heading');
const titleName = screen.getElementById('builder-dialog-name');
const problemLine = screen.getElementById('builder-dialog-problems');
const idLabel = words['/_admin/builder/section/id'];
const loopIdLabel = words['/_admin/builder/stack/id'];
check( 'the title of the dialog of a section is the kind and the name, with the pencil right of the name', dialogText('builder-dialog-kind') === 'Section' && titleName.hidden === false && titleName.textContent === 'hero' && heading.querySelectorAll('.builder-rename').length === 1
	&& iconOf( heading.querySelector('.builder-rename') ) === 'pencil' && heading.querySelector('.builder-rename').title === 'Rename' );
check( '...and the form has the id as a field as well: the name is in the title and in the properties, where it is changed the same way', controlsIn( dialogContent() ).filter( function( control ) { return control.label === idLabel } ).length === 1 );
heading.querySelector('.builder-rename').click();
const titleField = heading.querySelector('.builder-name-input');
check( '...the pencil turns the name into a field in the title', titleField !== null && titleField.value === 'hero' && titleName.hidden === true && titleField.parentNode === titleName.parentNode );
titleField.value = 'Bad Name';
pressKey( titleField, 'Enter' );
check( '...a name that will not do is said in the dialog, which stays open', problemLine.hidden === false && problemLine.textContent === words['/_admin/builder/error/id-slug'] && dialogBox().open === true && builder._doc.model.blocks[0].id === 'hero' );
heading.querySelector('.builder-rename').click();
let reached = false;
dialogBox().addEventListener( 'keydown', function() { reached = true } );
const titleEscape = pressKey( heading.querySelector('.builder-name-input'), 'Escape' );
check( '...Escape leaves the field and does not reach the dialog, which stays open', heading.querySelector('.builder-name-input') === null && titleName.hidden === false && titleEscape.defaultPrevented === true && reached === false && dialogBox().open === true );
heading.querySelector('.builder-rename').click();
heading.querySelector('.builder-name-input').value = 'top';
pressKey( heading.querySelector('.builder-name-input'), 'Enter' );
check( '...a name that will do asks the same question about the keys', questions.length === 1 && questions[0].message === 'Rename the section "hero" to "top"? Its text keys and image slots are moved to the new name when the template is saved.' && builder._doc.model.blocks[0].id === 'hero' );
questions[0].onChoose( 'rename' );
check( '...and once it is answered the title and the preview have the new name', builder._doc.model.blocks[0].id === 'top' && titleName.textContent === 'top' && frameName( 0 ).textContent === 'top' && builder._doc.model.blocks[0].renamedFrom === 'hero' && problemLine.hidden === true );
builder._openSettings( [ 0, 0 ] );
check( 'the dialogs of the other nodes have no name in their title and no pencil', titleName.hidden === true && heading.querySelectorAll('.builder-rename').length === 0 );
builder._openSettings( [] );
builder._openSettings( [ 3 ] );
check( '...and the dialog of a section has one pencil however often it is opened', heading.querySelectorAll('.builder-rename').length === 1 && titleName.textContent === 'contact' );
console.log( '\nThe id of a section in its properties' );

doc = open();
builder._renderEditor();
builder._lastBlock = {};
builder._openSettings( [ 0 ] );
builder._openBlock('properties');
const idField = function() { return controlOf( blockOf('properties'), idLabel ) };
const idLabels = labelList( blockOf('properties') );
const idBefore = changes;
check( 'the id of a section is a field of its properties, after its classes: labelled Id, marked with the setting it names, holding the id the section has - and the only field of the form with that label, the name in the title being no field', controlsIn( dialogContent() ).filter( function( control ) { return control.label === idLabel } ).length === 1
	&& idField().kind === 'text' && idField().el.dataset.key === 'id' && idField().el.value === 'hero' && idLabels.slice( -3 ).join() === [ words['/_admin/builder/section/custom'], words['/_admin/builder/section/row-custom'], idLabel ].join() && titleName.textContent === 'hero' && saysOf('properties').textContent.indexOf('hero') === -1 );
change( blockOf('properties'), idLabel, 'Not A Slug' );
check( 'a name that will not do is said where the pencil says it - in the dialog, which stays open - and the field has the id the section has again: nothing is renamed and nothing is asked', problemLine.hidden === false && problemLine.textContent === words['/_admin/builder/error/id-slug'] && idField().el.value === 'hero'
	&& builder._doc.model.blocks[0].id === 'hero' && questions.length === 0 && dialogBox().open === true );
change( blockOf('properties'), idLabel, 'contact' );
check( '...nor is the id another section has', problemLine.textContent === words['/_admin/builder/error/id-taken'] && idField().el.value === 'hero' && builder._doc.model.blocks[0].id === 'hero' && questions.length === 0 );
change( blockOf('properties'), idLabel, 'hero' );
check( '...and the id the section has is no change and no complaint: what was said is gone', problemLine.hidden === true && questions.length === 0 && changes === idBefore );
idField().el.value = 'typed-then-escape';
const idEscape = pressKey( idField().el, 'Escape' );
check( 'Escape in the field puts the id the section has back and stays in the field, as the pencil\'s field does: nothing is renamed, nothing is asked, the dialog stays open and the Escape does not reach it - and with the id as it is, an Escape is left to the dialog',
	idEscape.defaultPrevented === true && idEscape.stopped === true && idField().el.value === 'hero' && builder._doc.model.blocks[0].id === 'hero' && questions.length === 0 && dialogBox().open === true
	&& pressKey( idField().el, 'Escape' ).defaultPrevented === false );
change( blockOf('properties'), idLabel, 'top' );
check( 'a name that will do asks what the pencil asks, since the keys and slots of the section move with its name - and nothing is renamed until it is answered: the section, the title and the field have the id they had',
	questions.length === 1 && questions[0].message === words['/_admin/builder/confirm/rename'].replace( '%s', 'hero' ).replace( '%s', 'top' ) && questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'rename,cancel'
	&& builder._doc.model.blocks[0].id === 'hero' && titleName.textContent === 'hero' && idField().el.value === 'hero' && changes === idBefore );
questions[0].onChoose( 'cancel' );
check( '...No leaves everything as it is: the id, the title, the field and where the section was renamed from', builder._doc.model.blocks[0].id === 'hero' && builder._doc.model.blocks[0].renamedFrom === undefined && titleName.textContent === 'hero' && idField().el.value === 'hero' );
questions = [];
change( blockOf('properties'), idLabel, 'top' );
questions[0].onChoose( 'rename' );
check( '...Yes renames the section as the pencil does - it says where it was renamed from, for the server moves the keys - and the title, the field and the preview have the new name', builder._doc.model.blocks[0].id === 'top' && builder._doc.model.blocks[0].renamedFrom === 'hero' && titleName.textContent === 'top'
	&& idField().el.value === 'top' && frameName( 0 ).textContent === 'top' && problemLine.hidden === true && changes === idBefore + 1 && builder._unsaved( builder._doc ) === true );
questions = [];
heading.querySelector('.builder-rename').click();
heading.querySelector('.builder-name-input').value = 'start';
pressKey( heading.querySelector('.builder-name-input'), 'Enter' );
check( 'the pencil in the title and the field are one rename: what the pencil renames the field shows - the keys have not moved yet, so nothing is asked - and a second rename keeps where the first was from', questions.length === 0 && idField().el.value === 'start' && titleName.textContent === 'start' && builder._doc.model.blocks[0].id === 'start' && builder._doc.model.blocks[0].renamedFrom === 'hero' );
builder._closeDialog();
builder._openSettings( [ 3 ] );
builder._openBlock('properties');
change( blockOf('properties').querySelector('.builder-loop'), words['/_admin/builder/stack/kind'], 'sections' );
questions = [];
change( blockOf('properties'), idLabel, 'contact-us' );
questions[0].onChoose( 'rename' );
check( 'the id of the loop that makes the sections is the id of the section, and shows the new one once the section is renamed: it is drawn again', builder._doc.model.blocks[3].id === 'contact-us' && controlOf( blockOf('properties').querySelector('.builder-loop'), loopIdLabel ).el.value === 'contact-us' && idField().el.value === 'contact-us' && titleName.textContent === 'contact-us' );
builder._closeDialog();
builder._addSection();
questions = [];
builder._openSettings( [ 4 ] );
builder._openBlock('properties');
change( blockOf('properties'), idLabel, 'faq' );
check( 'a section made here that holds no key is renamed at once through the field, without a question: the title, the field and the preview have the name, and nothing is said of where it was renamed from', questions.length === 0 && builder._doc.model.blocks[4].id === 'faq' && builder._doc.model.blocks[4].renamedFrom === undefined && builder._fresh.faq === true
	&& titleName.textContent === 'faq' && idField().el.value === 'faq' && frameName( 4 ).textContent === 'faq' );
builder._closeDialog();
builder._addComponent( [ 4, 0 ], 'title' );
builder._closeDialog();
builder._openSettings( [ 4 ] );
builder._openBlock('properties');
questions = [];
change( blockOf('properties'), idLabel, 'questions' );
check( '...one that holds a key is asked as a saved one is and, answered, its sources are written new under the new name - nothing is moved that was never made', ( function() {
	const asked = questions.length === 1;
	questions[0].onChoose( 'rename' );
	const component = builder._doc.model.blocks[4].cols[0].components[0];
	return asked && builder._doc.model.blocks[4].id === 'questions' && builder._doc.model.blocks[4].renamedFrom === undefined && component.source === '/template/page-home/questions/title' && builder._fresh.questions === true && idField().el.value === 'questions' && titleName.textContent === 'questions';
} )() );
builder._closeDialog();
console.log( '\nThe dialog of the template' );

doc = open();
builder._doc.model.wrapClass = 'site-wrap';
builder._renderEditor();
builder._openSettings( [] );
const templateBody = dialogContent();
const templateKeep = function( model ) { return JSON.stringify( [ model.animate, model.vpa, model.vpaSpeed, model.wrapClass ] ) };
const fold = templateBody.querySelector('details');
check( 'the dialog of the template has no tabs and no name in its title; its fields are in groups under their headings: General, Frames, Viewport animation - and, folded away in the last, the kind of animation for this template', dialogText('builder-dialog-kind') === 'Template' && screen.getElementById('builder-dialog-name').hidden === true
	&& dialogContent().querySelector('.builder-blocks') === null && titlesIn( templateBody ) === 'General,Frames,Viewport animation,Kind for this template' && fold.localName === 'details' && fold.hasAttribute('open') === false && fold.open === false
	&& fold.parentNode.parentNode.querySelector('.builder-group-title').textContent === 'Viewport animation' && fold.querySelector('summary').textContent === 'Kind for this template' );
check( '...the fields in the groups: the name and the file, the header and the footer, the switch of the animation, and in the fold the effect, its strength and the speed - one under the other', labelsIn( templateBody ) === 'Name,File,Header,Footer,Animate the sections,Effect,Strength,Speed'
	&& templateBody.querySelectorAll('.builder-line').length === 0 && labelsIn( fold ) === 'Effect,Strength,Speed' );
check( '...the file is text that cannot be changed, with the slug the text keys of the page are made of in its hint; the name is a field', controlOf( templateBody, 'File' ).el.readOnly === true && valueAt( templateBody, 'File' ) === 'page-home.tpl' && controlOf( templateBody, 'Name' ).el.readOnly === false && valueAt( templateBody, 'Name' ) === 'Home'
	&& tipOf( controlOf( templateBody, 'File' ) ) === 'The slug is "home". The name of the file is the category of the text keys of the page (/template/page-home/...), so it does not change.' );
check( '...the effect is a select with words - the plain one first, the others in the order of Nino.css, the four slides named for the motion the visitor sees: slide-left starts at a positive translateX, to the right of its place, and ends at 0, so it slides left; slide-up starts below its place and slides up -, the strength a group of radio buttons, the speed a select whose first word is the frame', controlOf( templateBody, 'Effect' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join()
	=== '=Default - fade in and rise,zoom=Zoom,zoom-out=Zoom out,slide-left=Slides left,slide-right=Slides right,slide-up=Slides up,slide-down=Slides down,flip=Flip,blur=Blur'
	&& controlOf( templateBody, 'Speed' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Like the frame,fast=Fast,medium=Medium,slow=Slow'
	&& controlOf( templateBody, 'Strength' ).kind === 'group' && radiosOf( controlOf( templateBody, 'Strength' ).el ).map( function( radio ) { return radio.value } ).join() === 'soft,medium,hard' );

const strengthOf = function() { return controlOf( templateBody, 'Strength' ) };
const strengthOff = function() { return radiosOf( strengthOf().el ).every( function( radio ) { return radio.disabled === true } ) && strengthOf().el.classList.contains('is-disabled') };
const strengthOn = function() { return radiosOf( strengthOf().el ).every( function( radio ) { return radio.disabled === false } ) && strengthOf().el.classList.contains('is-disabled') === false };
check( 'with the plain effect the strength is disabled and greyed, whatever is chosen in it is not taken', valueAt( templateBody, 'Effect' ) === '' && strengthOff() && change( templateBody, 'Strength', 'hard' ) === false && builder._doc.model.vpa === '' && changes === 0 );
change( templateBody, 'Animate the sections', true );
change( templateBody, 'Effect', 'zoom' );
check( 'the switch writes animate; an effect enables the strength and is written with it as one word - the strength it starts with is medium', builder._doc.model.animate === true && builder._doc.model.vpa === 'zoom-medium' && strengthOn() && valueAt( templateBody, 'Strength' ) === 'medium' && changes === 2 );
change( templateBody, 'Strength', 'hard' );
check( '...a strength is written with the effect it is chosen for, and stays where the effect is changed', builder._doc.model.vpa === 'zoom-hard' && ( change( templateBody, 'Effect', 'slide-left' ), builder._doc.model.vpa === 'slide-left-hard' ) && valueAt( templateBody, 'Strength' ) === 'hard' );
change( templateBody, 'Effect', '' );
check( '...the plain effect is no word at all, and disables the strength again - which comes back as it was left, the next time an effect is chosen', builder._doc.model.vpa === '' && strengthOff() && ( change( templateBody, 'Effect', 'blur' ), builder._doc.model.vpa === 'blur-hard' && strengthOn() ) );
change( templateBody, 'Speed', 'slow' );
check( '...the speed is written as it is chosen, empty for the frame\'s own', builder._doc.model.vpaSpeed === 'slow' && ( change( templateBody, 'Speed', '' ), builder._doc.model.vpaSpeed === '' ) && ( change( templateBody, 'Speed', 'fast' ), builder._doc.model.vpaSpeed === 'fast' ) );
change( templateBody, 'Name', 'Start' );
change( templateBody, 'Header', 'html-header-slim' );
change( templateBody, 'Footer', '' );
check( '...and what the dialog writes is the name, the frames, animate, the effect with its strength and the speed - the other classes of the wrap are left as they are', templateKeep( builder._doc.model ) === '[true,"blur-hard","fast","site-wrap"]' && builder._doc.model.name === 'Start' && builder._doc.model.header === 'html-header-slim' && builder._doc.model.footer === ''
	&& builder._unsaved( builder._doc ) === true );

doc = open();
builder._doc.model.animate = true;
builder._doc.model.vpa = 'flip-hard';
builder._doc.model.vpaSpeed = 'medium';
builder._openSettings( [] );
check( 'a template with an effect shows it: the effect and its strength, the speed, the switch - and the strength enabled', valueAt( dialogContent(), 'Animate the sections' ) === true && valueAt( dialogContent(), 'Effect' ) === 'flip' && valueAt( dialogContent(), 'Strength' ) === 'hard' && valueAt( dialogContent(), 'Speed' ) === 'medium'
	&& radiosOf( controlOf( dialogContent(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === false } ) );

// The sections of the page with the animation given: the first, the second and the last
const animation = function( animate, kinds ) {
	doc = open();
	builder._doc.model.animate = animate;
	[ 0, 1, 3 ].forEach( function( at, k ) { builder._vpaApply( builder._doc.model.blocks[at].settings, kinds[k] ) } );
	builder._openSettings( [] );
	return builder._doc.model;
};
const closeDialog = function() { screen.getElementById('builder-dialog-close').click() };
const modes = function( model ) { return [ 0, 1, 3 ].map( function( at ) { return builder._animationMode( model.animate, model.blocks[at].settings ) } ).join() };

console.log( '\nThe sections follow the switch of the template' );

let model3 = animation( false, [ 'nino-vpa nino-vpa--zoom-soft', null, null ] );
change( dialogContent(), 'Animate the sections', true );
closeDialog();
check( 'turned on, the template asks whether the sections with no animation of their own follow - how many, and Yes the first of the answers', questions.length === 1 && questions[0].title === 'VPA of the template'
	&& questions[0].message === 'Should all the sections with no animation of their own follow? Sections affected: 2.' && questions[0].choices.map( function( choice ) { return choice.value+ ':'+ choice.kind } ).join() === 'follow:primary,stay:secondary' && model3.blocks[1].settings.vpa === null );
questions[0].onChoose( 'stay' );
check( '...No leaves them as they are, which with the switch on is no animation', model3.blocks[1].settings.vpa === null && model3.blocks[3].settings.vpa === null && modes( model3 ) === 'own,off,off' );
questions[0].onChoose( 'follow' );
check( '...Yes gives them the animation of the template, and not the one that has an animation of its own', model3.blocks[1].settings.vpa === '' && model3.blocks[3].settings.vpa === '' && model3.blocks[0].settings.vpa === 'zoom-soft' && modes( model3 ) === 'own,like,like' && builder._unsaved( builder._doc ) === true );

model3 = animation( true, [ 'nino-vpa', null, 'nino-vpa nino-vpa--blur-hard' ] );
const beforeOff = modes( model3 );
change( dialogContent(), 'Animate the sections', false );
closeDialog();
check( 'turned off, it asks whether the sections that follow the default follow - the ones with the bare class, not those with none or an animation of their own', questions.length === 1 && questions[0].message === 'Should all the sections that follow the default go along? Sections affected: 1.'
	&& questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'follow,stay' && beforeOff === 'like,off,own' );
questions[0].onChoose( 'follow' );
check( '...Yes takes the bare class from them, and the others stay as they are', model3.blocks[0].settings.vpa === null && model3.blocks[1].settings.vpa === null && model3.blocks[3].settings.vpa === 'blur-hard' && modes( model3 ) === 'like,like,own' );

animation( false, [ null, null, null ] );
change( dialogContent(), 'Animate the sections', true );
change( dialogContent(), 'Animate the sections', false );
closeDialog();
check( 'a switch turned and turned back asks nothing', questions.length === 0 );
animation( false, [ null, null, null ] );
change( dialogContent(), 'Effect', 'zoom' );
change( dialogContent(), 'Speed', 'slow' );
closeDialog();
check( 'changing the kind of the animation asks nothing', questions.length === 0 && builder._doc.model.vpa === 'zoom-medium' );
animation( false, [ 'nino-vpa', 'nino-vpa nino-vpa--flip-soft', 'nino-vpa nino-vpa--speed-fast' ] );
change( dialogContent(), 'Animate the sections', true );
closeDialog();
check( 'a template whose sections all have an animation of their own has none to ask', questions.length === 0 );
animation( false, [ null, 'nino-vpa nino-vpa--flip-soft', null ] );
change( dialogContent(), 'Animate the sections', true );
dialogBox().close();
check( 'the dialog closed by the browser - Escape - asks as well; and once', questions.length === 1 && questions[0].message === 'Should all the sections with no animation of their own follow? Sections affected: 2.' && ( builder._closeDialog(), questions.length === 1 ) );
animation( false, [ null, null, null ] );
change( dialogContent(), 'Animate the sections', true );
builder._openSettings( [ 1 ] );
check( 'the dialog replaced by another asks before it goes', questions.length === 1 && dialogText('builder-dialog-kind') === 'Section' );
console.log( '\nThe dialog of a section' );

doc = open();
builder._renderEditor();
builder._lastBlock = {};
builder._openSettings( [ 0 ] );
const settingsOf = function() { return builder._doc.model.blocks[0].settings };
const word = function( key ) { return words['/_admin/builder/'+ key] };
// The name a select of the box of the spacing has: its side and whether it is outside or inside
const sideLabel = function( side, place ) { return word('section/spacing-cell').replace( '%s', word('section/'+ side) ).replace( '%s', word('spacing/'+ place) ) };
const boxesIn = function() { return blockOf('spacing').querySelectorAll('.builder-box') };
const stepsOf = function( box ) { return box.querySelectorAll('select').map( function( select ) { return select.dataset.key } ).join() };

check( 'the dialog of a section has the blocks the table has for it, in the order of the table - the ids and how many - and no strip of tabs: surface, spacing, width and properties', JSON.stringify( blockIds() ) === JSON.stringify( builder.BLOCKS.section.map( function( block ) { return block.id } ) )
	&& blockIds().join() === 'surface,spacing,width,properties' && screen.getElementById('builder-dialog-tabs') === null && dialogContent().querySelector('.builder-tabpane') === null );
check( '...every block is named by the words of its own key: a head that is a button with the name in it - and no block but the first has what it says of the element in front of the name', blockIds().every( function( id ) {
	return headOf( id ).localName === 'button' && headOf( id ).querySelector('.builder-block-name').textContent === word('block/'+ id+ '/label') && word('block/'+ id+ '/label') !== '';
} ) );
check( '...the blocks are an accordion: the head controls its body and says whether it is open, the body is a region the head names, and exactly one block is open - the first where no other was open before', blockIds().every( function( id ) {
	const body = blockOf( id );
	return headOf( id ).getAttribute('aria-controls') === body.id && body.getAttribute('role') === 'region' && body.getAttribute('aria-labelledby') === headOf( id ).id && dialogContent().contains( body );
} ) && blockIds().map( function( id ) { return headOf( id ).getAttribute('aria-expanded') } ).join() === 'true,false,false,false' && openBlock() === 'surface'
	&& blockIds().map( function( id ) { return blockOf( id ).hidden } ).join() === 'false,true,true,true' );
headOf('width').click();
const pressed = openBlock()+ '/'+ blockIds().map( function( id ) { return headOf( id ).getAttribute('aria-expanded') } ).join();
headOf('width').click();
const closed = openBlock()+ '/'+ blockIds().map( function( id ) { return headOf( id ).getAttribute('aria-expanded') } ).join();
headOf('surface').click();
check( '...a head pressed opens its block and closes the one that was open, one at a time; pressed once more it closes its block, and none is open', pressed === 'width/false,false,true,false' && closed === '/false,false,false,false' && openBlock() === 'surface' );
// The controls the table lists for a block, as the dialog has drawn them, and the keys they write
const heldIn = function( id ) { return builder._dialogBlocks.parts[id].spec.held };
const keysHeld = function( id ) { return Array.from( new Set( [].concat( ...heldIn( id ).map( function( control ) { return control.keys } ) ) ) ) };
const keysDrawn = function( id ) { return Array.from( new Set( blockOf( id ).all().filter( function( el ) { return el.dataset.key !== undefined } ).map( function( el ) { return el.dataset.key } ) ) ) };
check( '...the table says what each block holds: what is drawn in a block is marked with keys of the controls the table lists for it, and a control that has keys of its own draws them - the corners are one toggle, of the row where the background stands inside and of the cover otherwise', ( function() {
	const within = blockIds().every( function( id ) { return keysDrawn( id ).every( function( key ) { return keysHeld( id ).indexOf( key ) !== -1 } ) } );
	const drawn = [ 'color', 'border', 'borderPrimary', 'background', 'cover', 'parallax', 'dim', 'inner', 'padded', 'radius', 'rowBgPl', 'rowBgPr', 'arrow' ].every( function( key ) { return keysDrawn('surface').indexOf( key ) !== -1 } );
	const corners = function( inner ) { settingsOf().inner = inner; redrawBlocks(); return keysDrawn('surface').filter( function( key ) { return key === 'radius' || key === 'rowRadius' } ).join() };
	const both = [ corners( false ), corners( true ) ];
	settingsOf().inner = false;
	redrawBlocks();
	return within === true && drawn === true && both.join() === 'radius,rowRadius';
} )() );

const coverLabel = word('section/cover');
const heightLabel = word('section/cover-height');
const paddedLabel = word('section/padded');
const radiusLabel = word('common/radius-alt');
const hoverLabel = word('common/hover');
const placeLabel = word('section/content-pos');
const offsetLabel = function( side ) { return word('section/spacing-cell').replace( '%s', word('section/offset') ).replace( '%s', word('section/'+ side) ) };
const surface = function() { return blockOf('surface') };
check( 'Surface: the colour, the line with its strength and the switch for the brand colour, the picture with the upload, its alt text and its focus, the cover and its height, the parallax, the scrim, the background inside, the frame of a cover, the corners, the offset of the picture at the sides and the arrow - one column, one field under the other, in this order and nothing else',
	JSON.stringify( labelList( surface() ) ) === JSON.stringify( [ word('section/color'), word('section/border'), word('section/border-primary'), word('picture/upload'), 'Alt text (English (US))', word('source/no-focus'), word('source/focus'), coverLabel, heightLabel,
		word('section/parallax'), word('section/dim'), word('section/inner-background'), paddedLabel, radiusLabel, offsetLabel('left'), offsetLabel('right'), word('section/arrow') ] )
	&& controlsIn( surface() ).map( function( control ) { return control.kind } ).join() === 'select,select,switch,text,text,switch,group,switch,number,switch,switch,switch,switch,switch,select,select,box'
	&& surface().querySelector('.builder-line') === null && surface().querySelector('table') === null );
check( '...the hero opens with the cover on, 100 in the number and the corners of the cover asleep - the cover has no room of the page around it - and the sentence the cover has to say is behind its question mark, not under it', valueAt( surface(), coverLabel ) === true && String( valueAt( surface(), heightLabel ) ) === '100'
	&& controlOf( surface(), heightLabel ).el.disabled === false && controlOf( surface(), radiusLabel ).el.disabled === true && tipOf( controlOf( surface(), coverLabel ) ) === word('hint/cover') && controlOf( surface(), coverLabel ).el.closest('label').querySelector('small') === null );
check( '...the head of the surface says what the hero has that is not the default: its colour, that it has a picture, the cover with its height, the scrim and the arrow - and none of what is off', saysOf('surface').textContent === [ builder._optionLabel( 'color', 'black' ), word('source/background-image'), coverLabel+ ' 100 %', word('section/dim'), word('section/arrow') ].join(' · ')
	&& saysOf('surface').hidden === false );

const widthBody = function() { return blockOf('width') };
check( 'Width and alignment: the width of the row as three choices with an icon each, the maximum width it may have, where a narrower row stands, the alignment of the text and of the columns and the place of the content of a cover - one column, in this order',
	JSON.stringify( labelList( widthBody() ) ) === JSON.stringify( [ word('section/width'), word('section/row'), word('section/row-place'), word('section/text'), word('section/row-align'), placeLabel ] )
	&& controlsIn( widthBody() ).map( function( control ) { return control.kind } ).join() === 'group,select,group,group,group,select' && widthBody().querySelector('.builder-line') === null );
check( '...the width is the width of the content, the full width of the page or to its edge: three buttons with an icon each and their words for title and label, and the hero - whose row has a maximum width - opens on the content', rowOf( widthBody(), word('section/width') ) === 'content:'+ word('option/width-content')+ ':width-content,full:'+ word('option/width-full')+ ':width-full,edge:'+ word('option/width-edge')+ ':width-edge'
	&& valueAt( widthBody(), word('section/width') ) === 'content' && unnamed( widthBody() ).length === 0 && iconsKnown( widthBody() ) );
check( '...the maximum width is a select of three - the grid\'s own, which is the default, narrow and wide - and it opens on what the section says', optionsOf( widthBody(), word('section/row') ) === '='+ word('option/row-default')+ ',narrow='+ word('option/row-narrow')+ ',wide='+ word('option/row-wide') && valueAt( widthBody(), word('section/row') ) === 'wide' );
check( '...the place of the row is a row of three buttons, the left, the middle - which is none - and the right, in the order they are seen, with the words of the panel, and it opens on the middle; what it says is behind its question mark',
	rowOf( widthBody(), word('section/row-place') ) === 'left:'+ word('option/row-place-left')+ ':,:'+ word('option/row-place-default')+ ':,right:'+ word('option/row-place-right')+ ':' && valueAt( widthBody(), word('section/row-place') ) === ''
	&& tipOf( controlOf( widthBody(), word('section/row-place') ) ) === word('hint/row-place') );
check( '...the width writes the row: the content width leaves the maximum width as it is, the full width takes it away, and to the edge takes it away and has no padding at the sides - the padding of the row at the left and the right is what the box of the row under spacing draws as well', ( function() {
	const state = function() { return [ settingsOf().row, settingsOf().rowPl, settingsOf().rowPr ].join('/') };
	const at = function( width ) { change( widthBody(), word('section/width'), width ); return state()+ ':'+ valueAt( widthBody(), word('section/width') ) };
	const first = state();
	const kept = at( 'content' );
	const edge = at( 'edge' );
	const full = at( 'full' );
	const content = at( 'content' );
	const edgeAgain = at( 'edge' );
	const contentAgain = at( 'content' );
	return first === 'wide//' && kept === 'wide//:content' && edge === 'full/0/0:edge' && full === 'full//:full' && content === '//:content' && edgeAgain === 'full/0/0:edge' && contentAgain === '//:content';
} )() );
settingsOf().row = 'wide';
redrawBlocks();
check( '...the place of the row sleeps while the row has no maximum width, whatever it is the width of - the buttons are out of reach and the model keeps the place it holds - and so does the maximum width itself: they are for a row that is as wide as its content', ( function() {
	const place = function() { return radiosOf( controlOf( widthBody(), word('section/row-place') ).el ) };
	const states = function() { return place().map( function( radio ) { return radio.disabled } ).join() + '|' + controlOf( widthBody(), word('section/row') ).el.disabled };
	change( widthBody(), word('section/row-place'), 'right' );
	const placed = settingsOf().rowPlace === 'right' && states() === 'false,false,false|false';
	change( widthBody(), word('section/width'), 'full' );
	const asleep = states() === 'true,true,true|true' && change( widthBody(), word('section/row-place'), 'left' ) === false && settingsOf().rowPlace === 'right' && valueAt( widthBody(), word('section/row-place') ) === 'right';
	change( widthBody(), word('section/width'), 'content' );
	change( widthBody(), word('section/row'), 'narrow' );
	const awake = settingsOf().row === 'narrow' && states() === 'false,false,false|false' && ( change( widthBody(), word('section/row-place'), 'left' ), settingsOf().rowPlace === 'left' );
	change( widthBody(), word('section/row'), 'wide' );
	change( widthBody(), word('section/row-place'), '' );
	return placed && asleep && awake && settingsOf().row === 'wide' && settingsOf().rowPlace === '' && valueAt( widthBody(), word('section/row') ) === 'wide';
} )() );
check( '...the alignments are two rows of icons: radio buttons, each with its word as its title and its label - the vertical one Top, Middle and Bottom, the horizontal one Inherited as a word and Left, Centre and Right', ( function() {
	const vertical = controlOf( widthBody(), word('section/row-align') ).el;
	const horizontal = controlOf( widthBody(), word('section/text') ).el;
	const options = function( group ) { return group.querySelectorAll('.builder-segment-option').map( function( option ) { return [ option.querySelector('input').value, option.title, option.querySelector('input').getAttribute('aria-label'), iconOf( option ) ].join(':') } ).join() };
	return vertical.getAttribute('role') === 'radiogroup' && horizontal.getAttribute('role') === 'radiogroup'
		&& options( vertical ) === ':Top:Top:align-top,middle:Middle:Middle:align-middle,bottom:Bottom:Bottom:align-bottom'
		&& options( horizontal ) === ':::,left:Left:Left:align-left,center:Centre:Centre:align-center,right:Right:Right:align-right'
		&& horizontal.querySelector('.builder-segment-face').textContent === 'Inherited'
		&& radiosOf( vertical ).every( function( radio ) { return radio.type === 'radio' && radio.name === radiosOf( vertical )[0].name } ) && radiosOf( vertical )[0].name !== radiosOf( horizontal )[0].name;
} )() );
check( '...and the choice is written: the vertical alignment as it is chosen, the horizontal one too', valueAt( widthBody(), word('section/row-align') ) === 'middle' && ( change( widthBody(), word('section/row-align'), 'bottom' ), settingsOf().rowAlign === 'bottom' ) && ( change( widthBody(), word('section/row-align'), '' ), settingsOf().rowAlign === '' )
	&& valueAt( widthBody(), word('section/text') ) === '' && ( change( widthBody(), word('section/text'), 'center' ), settingsOf().text === 'center' ) && valueAt( widthBody(), word('section/text') ) === 'center' );
change( widthBody(), word('section/text'), '' );
change( widthBody(), word('section/row-align'), 'middle' );
check( '...the head of the block says what is not the default - the maximum width the hero has and the alignment it has - and nothing of the width, which is the content\'s', saysOf('width').textContent === [ builder._optionLabel( 'row', 'wide' ), builder._optionLabel( 'row-align', 'middle' ) ].join(' · '));

const spacingBody = function() { return blockOf('spacing') };
check( 'Spacing: the whitespace of the page, then the box of the section - outside above, inside above, left, right and below, outside below - and under it, with its name, the box of its row, which has the inside alone: a box is a group of selects around a small rectangle',
	JSON.stringify( labelList( spacingBody() ) ) === JSON.stringify( [ word('section/whitespace'), sideLabel( 'above', 'outside' ), sideLabel( 'above', 'inside' ), sideLabel( 'left', 'inside' ), sideLabel( 'right', 'inside' ), sideLabel( 'below', 'inside' ), sideLabel( 'below', 'outside' ),
		sideLabel( 'above', 'inside' ), sideLabel( 'left', 'inside' ), sideLabel( 'right', 'inside' ), sideLabel( 'below', 'inside' ) ] )
	&& boxesIn().length === 2 && boxesIn().every( function( box ) { return box.getAttribute('role') === 'group' && box.querySelector('.builder-box-frame') !== null && box.querySelector('.builder-box-core') !== null } )
	&& boxesIn().map( function( box ) { return box.getAttribute('aria-label') } ).join() === word('tree/section')+ ','+ word('spacing/row') && boxesIn()[1].querySelector('.builder-box-name').textContent === word('spacing/row')
	&& spacingBody().querySelector('table') === null );
check( '...the section\'s box is the six sides of the section, in the order the Reader keeps them, and the row\'s the four inside sides of the row - each select marked with the setting it writes - and a step is none, shown as a dash, or 0 to 6', stepsOf( boxesIn()[0] ) === 'mt,pt,pl,pr,pb,mb' && stepsOf( boxesIn()[1] ) === 'rowPt,rowPl,rowPr,rowPb' && builder.SIDES.join() === 'mt,mb,pt,pb,pl,pr'
	&& boxesIn().every( function( box ) { return box.querySelectorAll('select').every( function( select ) { return select.children.map( function( option ) { return option.value } ).join() === ',0,1,2,3,4,5,6' && select.children[0].textContent === '–' && select.children[3].textContent === '2' } ) } ) );
check( '...each select writes its own setting, the section\'s box the section\'s and the row\'s box the row\'s - above and below outside, the four sides inside - and none of the others', ( function() {
	const own = function() { return builder.SIDES.map( function( side ) { return settingsOf()[side] } ).join() };
	const row = function() { return [ 'rowPt', 'rowPb', 'rowPl', 'rowPr' ].map( function( key ) { return settingsOf()[key] } ).join() };
	const before = changes;
	change( boxesIn()[0], sideLabel( 'above', 'outside' ), '3' );
	change( boxesIn()[0], sideLabel( 'below', 'outside' ), '5' );
	change( boxesIn()[0], sideLabel( 'above', 'inside' ), '1' );
	change( boxesIn()[0], sideLabel( 'below', 'inside' ), '2' );
	change( boxesIn()[0], sideLabel( 'left', 'inside' ), '4' );
	change( boxesIn()[0], sideLabel( 'right', 'inside' ), '6' );
	const section = own() === '3,5,1,2,4,6' && row() === ',,,';
	change( boxesIn()[1], sideLabel( 'above', 'inside' ), '0' );
	change( boxesIn()[1], sideLabel( 'below', 'inside' ), '6' );
	change( boxesIn()[1], sideLabel( 'left', 'inside' ), '2' );
	change( boxesIn()[1], sideLabel( 'right', 'inside' ), '3' );
	const sides = row() === '0,6,2,3' && own() === '3,5,1,2,4,6' && changes === before + 10 && valueAt( boxesIn()[1], sideLabel( 'left', 'inside' ) ) === '2';
	return section && sides;
} )() );
check( '...the head of the block says each box that has a side set - outside above and below, then inside above, below, left and right, a dash for a side that is not set - and the row\'s with its name in front and the inside alone, and the whitespace of the page', ( function() {
	const first = word('spacing/outside')+ ' 3/5 · '+ word('spacing/inside')+ ' 1/2/4/6';
	const second = word('spacing/row')+ ' '+ word('spacing/inside')+ ' 0/6/2/3';
	const both = saysOf('spacing').textContent === first+ ' · '+ second;
	change( boxesIn()[0], sideLabel( 'left', 'inside' ), '' );
	change( boxesIn()[0], sideLabel( 'right', 'inside' ), '' );
	change( boxesIn()[0], sideLabel( 'above', 'inside' ), '' );
	change( boxesIn()[0], sideLabel( 'below', 'inside' ), '' );
	const dashes = saysOf('spacing').textContent === word('spacing/outside')+ ' 3/5 · '+ word('spacing/inside')+ ' –/–/–/– · '+ second;
	[ 'above', 'below' ].forEach( function( side ) { change( boxesIn()[0], sideLabel( side, 'outside' ), '' ) } );
	[ 'above', 'below', 'left', 'right' ].forEach( function( side ) { change( boxesIn()[1], sideLabel( side, 'inside' ), '' ) } );
	return both && dashes && saysOf('spacing').textContent === '' && saysOf('spacing').hidden === true && builder.SIDES.every( function( side ) { return settingsOf()[side] === '' } );
} )() );
check( '...the box of the row and the width are the same two settings: the width to the edge shows the row\'s left and right at 0, and a side the box sets changes what the width says - to the edge only where both are 0 with the full width', ( function() {
	change( widthBody(), word('section/width'), 'edge' );
	const shown = valueAt( boxesIn()[1], sideLabel( 'left', 'inside' ) )+ valueAt( boxesIn()[1], sideLabel( 'right', 'inside' ) );
	change( boxesIn()[1], sideLabel( 'left', 'inside' ), '1' );
	const moved = valueAt( widthBody(), word('section/width') );
	change( boxesIn()[1], sideLabel( 'left', 'inside' ), '0' );
	const back = valueAt( widthBody(), word('section/width') );
	change( widthBody(), word('section/width'), 'content' );
	const cleared = valueAt( boxesIn()[1], sideLabel( 'left', 'inside' ) )+ valueAt( boxesIn()[1], sideLabel( 'right', 'inside' ) )+ settingsOf().rowPl+ settingsOf().rowPr;
	settingsOf().row = 'wide';
	redrawBlocks();
	return shown === '00' && moved === 'full' && back === 'edge' && cleared === '' && settingsOf().row === 'wide';
} )() );
check( 'Spacing: the whitespace is a select of the four rungs around the middle, with the words of the panel - the empty one is the page\'s own, which the Design sets - and it writes the word of the one chosen; what it says is behind its question mark', optionsOf( spacingBody(), word('section/whitespace') ) === '='+ word('option/whitespace-default')+ ',least='+ word('option/whitespace-least')+ ',less='+ word('option/whitespace-less')+ ',more='+ word('option/whitespace-more')+ ',most='+ word('option/whitespace-most')
	&& valueAt( spacingBody(), word('section/whitespace') ) === '' && ( change( spacingBody(), word('section/whitespace'), 'most' ), settingsOf().whitespace === 'most' ) && saysOf('spacing').textContent === word('section/whitespace')+ ' '+ word('option/whitespace-most').toLowerCase()
	&& ( change( spacingBody(), word('section/whitespace'), '' ), settingsOf().whitespace === '' ) && tipOf( controlOf( spacingBody(), word('section/whitespace') ) ) === word('hint/whitespace') );

const ground = dialogContent();
check( 'the parallax and the dimming are two switches, in reach where there is a picture and, for the dimming, a cover or a parallax: the hero has the dimming on and the parallax off - and the dimming has what it says behind its question mark, the parallax has none', controlOf( ground, 'Parallax effect' ).kind === 'switch' && controlOf( ground, 'Dim the picture' ).kind === 'switch'
	&& valueAt( ground, 'Parallax effect' ) === false && valueAt( ground, 'Dim the picture' ) === true
	&& controlOf( ground, 'Parallax effect' ).el.disabled === false && controlOf( ground, 'Dim the picture' ).el.disabled === false
	&& tipOf( controlOf( ground, 'Parallax effect' ) ) === null && tipOf( controlOf( ground, 'Dim the picture' ) ) === word('hint/dim-text') );
check( '...the colour is a select with a patch beside it that follows the choice - and the patch is a patch of the page, not of the picture', ( function() {
	const swatch = ground.querySelector('.builder-swatch');
	const first = swatch.dataset.color;
	change( ground, 'Background colour', 'dark' );
	const second = swatch.dataset.color;
	change( ground, 'Background colour', '' );
	return first === 'black' && second === 'dark' && swatch.dataset.color === 'plain' && swatch.getAttribute('aria-hidden') === 'true' && swatch.parentNode.contains( controlOf( ground, 'Background colour' ).el ) && settingsOf().color === ''
		&& controlOf( ground, 'Background colour' ).el.children.map( function( option ) { return option.value } ).join() === ',alt,tint,dark,black,primary,brand-alt' && swatch.style.getPropertyValue('--builder-swatch') === '';
} )() );
check( '...the colours and the border have words of their own, not the names of the classes of Nino.css - and the empty value of the border has its word, not the lower case none of the label', controlOf( ground, 'Background colour' ).el.children.map( function( option ) { return option.textContent } ).join() === 'Default,Alternate,Tinted,Dark,Black,Brand colour,Second colour'
	&& controlOf( ground, 'Border' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=None,1=Thin,2=Medium,3=Strong' );
check( '...the border is a strength and, under it, a switch for the brand colour: the select writes the strength, the switch the colour - two settings of the section, and neither takes the other along; a switch alone is a border of the brand colour with no strength', ( function() {

	const state = function() { return settingsOf().border+ '/'+ settingsOf().borderPrimary };
	const start = state();

	change( ground, 'Border', '2' );

	const strength = state();

	change( ground, 'In the brand colour', true );

	const both = state();

	change( ground, 'Border', '' );

	const alone = state();

	// The switch is out of reach while the border is none: it is turned off by way of a strength
	change( ground, 'Border', '1' );
	change( ground, 'In the brand colour', false );
	change( ground, 'Border', '' );

	return start === '/false' && strength === '2/false' && both === '2/true' && alone === '/true' && state() === '/false'
		&& controlOf( ground, 'In the brand colour' ).kind === 'switch' && valueAt( ground, 'Border' ) === '' && valueAt( ground, 'In the brand colour' ) === false;
} )() );
check( '...the switch waits for a strength: it takes no choice while the border is none and takes one as soon as a strength is chosen - and the model keeps what it holds, so the brand colour of a border whose strength is taken away stays on, out of reach', ( function() {

	const primary = function() { return controlOf( ground, 'In the brand colour' ).el };
	const start = primary().disabled;

	change( ground, 'Border', '3' );

	const chosen = primary().disabled;

	change( ground, 'In the brand colour', true );
	change( ground, 'Border', '' );

	const none = [ primary().disabled, primary().checked, settingsOf().border, settingsOf().borderPrimary ].join();

	change( ground, 'Border', '1' );

	const again = [ primary().disabled, primary().checked, settingsOf().border, settingsOf().borderPrimary ].join();

	change( ground, 'In the brand colour', false );
	change( ground, 'Border', '' );

	return start === true && chosen === false && none === 'true,true,,true' && again === 'false,true,1,true' && primary().disabled === true && primary().checked === false && settingsOf().borderPrimary === false;
} )() );
check( '...a brand colour with no strength, as a file may hold it, is shown as it is - on, out of reach until a strength is chosen - and opening the form changes nothing in the model', ( function() {

	const before = changes;

	settingsOf().borderPrimary = true;
	builder._closeDialog();
	builder._openSettings( [ 0 ] );

	const alone = dialogContent();
	const shown = [ valueAt( alone, 'Border' ), controlOf( alone, 'In the brand colour' ).el.disabled, valueAt( alone, 'In the brand colour' ) ].join();
	const kept = [ settingsOf().border, settingsOf().borderPrimary, changes - before ].join();

	change( alone, 'Border', '2' );
	change( alone, 'In the brand colour', false );
	change( alone, 'Border', '' );

	return shown === ',true,true' && kept === ',true,0' && controlOf( alone, 'In the brand colour' ).el.disabled === true && settingsOf().borderPrimary === false && settingsOf().border === '';
} )() );
check( '...the word for the brand colour as a border is gone from the options of the select, in both languages: the switch has it', Object.prototype.hasOwnProperty.call( words, '/_admin/builder/option/border-primary' ) === false && fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' ).indexOf('/_admin/builder/option/border-primary') === -1
	&& words['/_admin/builder/section/border-primary'] === 'In the brand colour' && builder.BORDERS.join() === ',1,2,3' );
sandbox.getComputedStyle = function() {
	return { getPropertyValue : function( property ) { return { '--color-section-dark-bg' : ' #112233 ', '--color-primary' : 'rgb(10, 20, 30)' }[property] ?? '' } };
};
builder._openSettings( [ 0 ] );
change( dialogContent(), 'Background colour', 'dark' );
const patch = function() { return dialogContent().querySelector('.builder-swatch').style.getPropertyValue('--builder-swatch') };
const darkPatch = patch();
change( dialogContent(), 'Background colour', 'primary' );
const primaryPatch = patch();
change( dialogContent(), 'Background colour', 'tint' );
check( '...the colour of the patch is read at the time from the custom properties of Nino.css the preview has - and where the page does not know one the patch has no colour of its own, and is the neutral grey of the stylesheet', darkPatch === '#112233' && primaryPatch === 'rgb(10, 20, 30)' && patch() === '' && dialogContent().querySelector('.builder-swatch').dataset.color === 'tint' );
delete sandbox.getComputedStyle;
check( 'the picture behind the section: a row with the slot as text and an icon button beside it that has the source for its title and its label', ( function() {
	const row = dialogContent().querySelector('.builder-source');
	const button = row.querySelector('button');
	return row.querySelector('.builder-source-caption').textContent === 'Current image slot:' && row.querySelector('.builder-source-current').textContent === '/template/page-home/hero/background' && button.className === 'builder-icon-btn' && button.title === 'Source'
		&& button.getAttribute('aria-label') === 'Source' && iconOf( button ) === 'source' && row.querySelector('.builder-source-line').contains( button );
} )() );

const focusGrid = function() { return controlOf( dialogContent(), 'Manual focus' ).el };
check( 'the focus of the picture is a grid of nine radio buttons in the order of Nino.css, each with its place for its label, and a switch that says there is none - the focus the section has is the one that is on', ( function() {
	return focusGrid().getAttribute('role') === 'radiogroup' && radiosOf( focusGrid() ).map( function( radio ) { return radio.value+ '='+ radio.getAttribute('aria-label') } ).join() === '1=Top left,2=Top centre,3=Top right,4=Centre left,5=Centre,6=Centre right,7=Bottom left,8=Bottom centre,9=Bottom right'
		&& valueAt( dialogContent(), 'Manual focus' ) === '5' && valueAt( dialogContent(), 'Automatic focus' ) === false && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === false } ) && focusGrid().classList.contains('is-disabled') === false;
} )() );
change( dialogContent(), 'Manual focus', '9' );
check( '...a place is written as its number', builder._doc.model.blocks[0].background.focus === 9 && valueAt( dialogContent(), 'Manual focus' ) === '9' );
change( dialogContent(), 'Automatic focus', true );
check( '...no focus is null, and the grid is greyed and takes no choice while there is none', builder._doc.model.blocks[0].background.focus === null && focusGrid().classList.contains('is-disabled') && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === true && radio.checked === false } )
	&& change( dialogContent(), 'Manual focus', '3' ) === false && builder._doc.model.blocks[0].background.focus === null );
change( dialogContent(), 'Automatic focus', false );
check( '...and a focus again starts in the middle', builder._doc.model.blocks[0].background.focus === 5 && valueAt( dialogContent(), 'Manual focus' ) === '5' && focusGrid().classList.contains('is-disabled') === false );
change( dialogContent(), 'Parallax effect', true );
buttonOf( dialogContent(), 'Remove the picture' ).click();
check( 'the picture is taken away with its button: no background, the row says none, the focus and the button are off', builder._doc.model.blocks[0].background === null && dialogContent().querySelector('.builder-source-current').textContent === 'none' && focusGrid().classList.contains('is-disabled')
	&& controlOf( dialogContent(), 'Automatic focus' ).el.disabled === true && buttonOf( dialogContent(), 'Remove the picture' ).disabled === true && preview().children[0].querySelector('.is-background') === null );
check( '...a section that loses its picture loses the parallax and the dimming with it, and both are off and out of reach again', settingsOf().parallax === false && settingsOf().dim === false && valueAt( dialogContent(), 'Parallax effect' ) === false && valueAt( dialogContent(), 'Dim the picture' ) === false
	&& controlOf( dialogContent(), 'Parallax effect' ).el.disabled === true && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === true && settingsOf().cover === 100 );
builder._openSettings( [ 1 ] );
const sectionOne = function() { return builder._doc.model.blocks[1].settings };
check( '...so is a section that has none to begin with, and so are its parallax and its dimming', controlOf( dialogContent(), 'Automatic focus' ).el.disabled === true && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === true } ) && buttonOf( dialogContent(), 'Remove the picture' ).disabled === true
	&& controlOf( dialogContent(), 'Parallax effect' ).el.disabled === true && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === true );
check( 'a section that is no cover has the switch off, a number that is empty and takes no input, and the place of its content out of reach - a parallax has the place as a cover has, and a section that is neither stands in the flow of the page', valueAt( dialogContent(), 'Cover' ) === false && valueAt( dialogContent(), heightLabel ) === ''
	&& controlOf( dialogContent(), heightLabel ).el.disabled === true && controlOf( dialogContent(), placeLabel ).el.disabled === true && sectionOne().cover === null && sectionOne().parallax === false );
change( dialogContent(), 'Cover', true );
check( '...the cover switched on is 90 high, what Nino.ui.js makes of a cover that names no height: the model has it, the number shows it and takes input, and the place of the content wakes', sectionOne().cover === 90 && valueAt( dialogContent(), 'Cover' ) === true
	&& String( valueAt( dialogContent(), heightLabel ) ) === '90' && controlOf( dialogContent(), heightLabel ).el.disabled === false && controlOf( dialogContent(), placeLabel ).el.disabled === false );
change( dialogContent(), heightLabel, 60 );
check( '...a height typed is written', sectionOne().cover === 60 && valueAt( dialogContent(), 'Cover' ) === true );
check( '...and a field on its way to a number does not take the cover away: one emptied, or one out of bounds, leaves the height as it is - and once the typing is over the field says the height in force', [ '', 0, 101, -5 ].every( function( typed ) { change( dialogContent(), heightLabel, typed ); return sectionOne().cover === 60 } ) && String( valueAt( dialogContent(), heightLabel ) ) === '60' );
change( dialogContent(), placeLabel, 'bottom' );
change( dialogContent(), heightLabel, 150 );
check( '...and once the typing is over a value out of bounds goes back to the height in force: 150 typed, 60 read', String( valueAt( dialogContent(), heightLabel ) ) === '60' && sectionOne().cover === 60 );
const bottom = sectionOne().contentPos;
change( dialogContent(), placeLabel, 'top' );
const top = sectionOne().contentPos;
change( dialogContent(), placeLabel, '' );
check( '...the place of the content is written as its word, the top or the bottom, and the middle as none', bottom === 'bottom' && top === 'top' && sectionOne().contentPos === '' && valueAt( dialogContent(), placeLabel ) === '' );
sectionOne().dim = true;
change( dialogContent(), 'Cover', false );
check( 'the cover switched off is none in the model, the number is empty and out of reach, the place of the content sleeps again - and the scrim goes with the cover, as there is nothing left to dim', sectionOne().cover === null && valueAt( dialogContent(), 'Cover' ) === false
	&& valueAt( dialogContent(), heightLabel ) === '' && controlOf( dialogContent(), heightLabel ).el.disabled === true && controlOf( dialogContent(), placeLabel ).el.disabled === true && sectionOne().dim === false );
sectionOne().parallax = true;
sectionOne().dim = true;
redrawBlocks();
check( 'a parallax a template wrote without a picture, or that lost it, is not stuck: a switch that is on is in reach, and switched off it takes the scrim with it and both sleep', controlOf( dialogContent(), 'Parallax effect' ).el.disabled === false && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false
	&& change( dialogContent(), 'Parallax effect', false ) === true && sectionOne().parallax === false && sectionOne().dim === false
	&& controlOf( dialogContent(), 'Parallax effect' ).el.disabled === true && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === true );
builder._doc.model.blocks[1].background = { slot : '/template/page-home/services/background', focus : 5 };
redrawBlocks();
check( 'with a picture the parallax and the dimming are both in reach, whatever the section is: the scrim is for the picture, and a plain section has one of its own as a cover and a parallax have', controlOf( dialogContent(), 'Parallax effect' ).el.disabled === false && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false );
change( dialogContent(), 'Parallax effect', true );
check( '...a parallax wakes the place of the content in the width block, where the cover stays off, and leaves the dimming in reach', sectionOne().parallax === true && sectionOne().cover === null && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false
	&& controlOf( dialogContent(), placeLabel ).el.disabled === false && valueAt( dialogContent(), 'Cover' ) === false );
change( dialogContent(), 'Dim the picture', true );
change( dialogContent(), 'Parallax effect', false );
check( '...and switched off in a section that is no cover the scrim stays - it is the section\'s own over its picture now - while the place of the content sleeps again, in the width block', sectionOne().parallax === false && sectionOne().dim === true && valueAt( dialogContent(), 'Dim the picture' ) === true
	&& controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false && controlOf( dialogContent(), placeLabel ).el.disabled === true );
change( dialogContent(), 'Cover', true );
check( '...a cover leaves the dimming in reach and the scrim as it was', sectionOne().cover === 90 && sectionOne().dim === true && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false );
change( dialogContent(), 'Dim the picture', true );
change( dialogContent(), 'Parallax effect', true );
change( dialogContent(), 'Parallax effect', false );
check( '...the parallax switched off in a cover keeps the scrim, which is the cover\'s', sectionOne().cover === 90 && sectionOne().parallax === false && sectionOne().dim === true && valueAt( dialogContent(), 'Dim the picture' ) === true );
change( dialogContent(), 'Parallax effect', true );
change( dialogContent(), 'Cover', false );
check( '...and so is the cover switched off in a parallax: the scrim stays, and so does the place of the content', sectionOne().cover === null && sectionOne().parallax === true && sectionOne().dim === true
	&& controlOf( dialogContent(), placeLabel ).el.disabled === false && valueAt( dialogContent(), 'Dim the picture' ) === true );
change( dialogContent(), 'Cover', true );
change( dialogContent(), heightLabel, 60 );
change( dialogContent(), placeLabel, 'bottom' );
change( dialogContent(), 'Border', '3' );
change( dialogContent(), 'In the brand colour', true );
check( '...the other fields of the picture and of the cover write their settings: the parallax, the dimming, the cover and its height in the surface and the place of the content in the width block', ( function() {
	const settings = sectionOne();
	return settings.parallax === true && settings.dim === true && settings.contentPos === 'bottom' && settings.cover === 60 && settings.border === '3' && settings.borderPrimary === true;
} )() );
buttonOf( dialogContent(), 'Remove the picture' ).click();
check( '...and the picture taken away takes the parallax and the dimming with it, and nothing of the cover: its height and the place of its content stay', sectionOne().parallax === false && sectionOne().dim === false && sectionOne().cover === 60 && sectionOne().contentPos === 'bottom'
	&& controlOf( dialogContent(), 'Parallax effect' ).el.disabled === true && controlOf( dialogContent(), heightLabel ).el.disabled === false );
change( dialogContent(), 'Cover', false );
builder._doc.model.blocks[1].background = { slot : '/template/page-home/services/background', focus : 5 };
redrawBlocks();
change( dialogContent(), 'Parallax effect', true );
const reach = controlOf( dialogContent(), placeLabel ).el.disabled === false;
buttonOf( dialogContent(), 'Remove the picture' ).click();
check( '...and from a parallax that is no cover the picture taken away takes the reach of the place of the content with it, in the width block: the parallax was all that gave it', reach === true && sectionOne().parallax === false && sectionOne().cover === null
	&& controlOf( dialogContent(), placeLabel ).el.disabled === true );

doc = open();
builder._openSettings( [ 0 ] );
// The group of the animation in the properties of the section: the field that chooses it and the ones that follow where it is the section's own
const animationPane = function() { return blockOf('properties').querySelectorAll('.builder-group').find( function( group ) { return group.querySelector('.builder-group-title').textContent === word('group/animation') } ).querySelector('.builder-group-body') };
check( 'Viewport animation: the section carries what the template gives, none, or an animation of its own - with a template that does not animate, none is like the template and there is no off', controlOf( animationPane(), 'Animation' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'like=Like the template (off),own=Own'
	&& tipOf( controlOf( animationPane(), 'Animation' ) ) === words['/_admin/builder/hint/vpa-section-off'] );
check( '...a section with the plain animation has an animation of its own where the template has none: its fields are there - the effect and its strength, the speed and how often, the delay and the duration, one under the other', valueAt( animationPane(), 'Animation' ) === 'own'
	&& JSON.stringify( labelList( animationPane() ) ) === JSON.stringify( [ 'Animation', 'Effect', 'Strength', 'Speed', 'Repeat', 'Delay', 'Duration' ] ) && animationPane().querySelector('.builder-line') === null );
check( '...its effects are those of Nino.css and no word for none - that is the choice above -, its repeat is once or repeat, its speed the three', controlOf( animationPane(), 'Effect' ).el.children.map( function( option ) { return option.value } ).join() === ',zoom,zoom-out,slide-left,slide-right,slide-up,slide-down,flip,blur'
	&& controlOf( animationPane(), 'Repeat' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Once,repeat=Repeat' && controlOf( animationPane(), 'Speed' ).el.children.map( function( option ) { return option.value } ).join() === ',fast,medium,slow'
	&& radiosOf( controlOf( animationPane(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === true } ) );
change( animationPane(), 'Effect', 'zoom' );
change( animationPane(), 'Strength', 'soft' );
change( animationPane(), 'Speed', 'fast' );
change( animationPane(), 'Repeat', 'repeat' );
change( animationPane(), 'Delay', '200ms' );
change( animationPane(), 'Duration', '1s' );
check( '...and writes the effect with its strength as one word, the speed, the repeat, the delay and the duration', JSON.stringify( [ settingsOf().vpa, settingsOf().vpaSpeed, settingsOf().vpaMode, settingsOf().vpaDelay, settingsOf().vpaDuration ] ) === '["zoom-soft","fast","repeat","200ms","1s"]'
	&& builder._animationMode( false, settingsOf() ) === 'own' );
change( animationPane(), 'Animation', 'like' );
check( 'like the template is no animation where the template has none, and the fields of its own go - delay and duration with them', settingsOf().vpa === null && settingsOf().vpaSpeed === '' && settingsOf().vpaDelay === '' && JSON.stringify( labelList( animationPane() ) ) === JSON.stringify( [ 'Animation' ] ) && valueAt( animationPane(), 'Animation' ) === 'like' );
change( animationPane(), 'Animation', 'own' );
check( '...own starts as the plain animation, with the fields to change it', settingsOf().vpa === '' && labelList( animationPane() ).length === 7 && valueAt( animationPane(), 'Animation' ) === 'own' && valueAt( animationPane(), 'Effect' ) === '' );

doc = open();
builder._doc.model.animate = true;
builder._openSettings( [ 0 ] );
check( 'with a template that animates its sections: like the template, off or own - a section with the bare class is like the template, and its fields are not shown', controlOf( animationPane(), 'Animation' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'like=Like the template,off=Off,own=Own'
	&& valueAt( animationPane(), 'Animation' ) === 'like' && labelList( animationPane() ).length === 1 && tipOf( controlOf( animationPane(), 'Animation' ) ) === words['/_admin/builder/hint/vpa-section'] );
change( animationPane(), 'Animation', 'off' );
check( '...off is no class at all', settingsOf().vpa === null && valueAt( animationPane(), 'Animation' ) === 'off' && labelList( animationPane() ).length === 1 );
change( animationPane(), 'Animation', 'own' );
check( '...own opens the fields, and the choice stays while the dialog is open: it is the section\'s own animation once something is changed in it', labelList( animationPane() ).length === 7 && valueAt( animationPane(), 'Animation' ) === 'own' && settingsOf().vpa === '' && builder._animationMode( true, settingsOf() ) === 'like'
	&& ( change( animationPane(), 'Speed', 'slow' ), builder._animationMode( true, settingsOf() ) === 'own' ) );
change( animationPane(), 'Animation', 'like' );
check( '...and like the template is the bare class again', settingsOf().vpa === '' && settingsOf().vpaSpeed === '' && builder._animationMode( true, settingsOf() ) === 'like' );
builder._openSettings( [ 0 ] );
check( 'a section opened again shows what the classes say: like, off or own', valueAt( animationPane(), 'Animation' ) === 'like' && ( function() {
	settingsOf().vpa = 'flip-hard';
	builder._openSettings( [ 0 ] );
	const own = valueAt( animationPane(), 'Animation' ) === 'own' && valueAt( animationPane(), 'Effect' ) === 'flip' && valueAt( animationPane(), 'Strength' ) === 'hard';
	settingsOf().vpa = null;
	builder._openSettings( [ 0 ] );
	return own && valueAt( animationPane(), 'Animation' ) === 'off';
} )() );
check( '...and a repeat that a hand-written file has (visible, visible-once) stays in the list and is shown, so that reading the file changes nothing', ( function() {
	settingsOf().vpa = 'zoom-soft';
	settingsOf().vpaMode = 'visible';
	builder._openSettings( [ 0 ] );
	return controlOf( animationPane(), 'Repeat' ).el.children.map( function( option ) { return option.value } ).join() === ',repeat,visible' && valueAt( animationPane(), 'Repeat' ) === 'visible' && settingsOf().vpaMode === 'visible';
} )() );

const custom = blockOf('properties').querySelectorAll('.builder-group').find( function( group ) { return group.querySelector('.builder-group-title').textContent === word('group/custom') } ).querySelector('.builder-group-body');
check( 'CSS classes: the class of the section and the class of the row, one under the other, and what Nino.css has to say of classes behind the question mark of the first', JSON.stringify( labelList( custom ) ) === JSON.stringify( [ 'Class of the section', 'Class of the row' ] ) && custom.querySelector('.builder-line') === null
	&& tipOf( controlOf( custom, 'Class of the section' ) ) === words['/_admin/builder/hint/custom'] && ( change( custom, 'Class of the section', 'my-hero' ), change( custom, 'Class of the row', 'my-row' ), settingsOf().custom === 'my-hero' && settingsOf().rowCustom === 'my-row' ) );

doc = open();
builder._problems = [ 'the section "services": the source "x" means nothing here', 'the section "hero": the name is bad' ];
builder._openSettings( [ 1 ] );
check( 'what a refused save said of a section stands over its form, above the blocks, and in none of them', dialogContent().children[0].className === 'nino-admin-error' && dialogContent().children[0].textContent === 'the section "services": the source "x" means nothing here' && dialogContent().children[1].className === 'builder-blocks'
	&& blockIds().every( function( id ) { return blockOf( id ).querySelectorAll('.nino-admin-error').every( function( el ) { return el.hidden === true } ) } ) );

doc = open();
settingsOf().rowAlign = 'center';
builder._openSettings( [ 0 ] );
check( 'a vertical alignment written by hand that the form has no button for - nino-grid-center - is left as it is: no button is chosen, until one is', valueAt( dialogContent(), 'Vertical content alignment' ) === null && settingsOf().rowAlign === 'center'
	&& ( change( dialogContent(), 'Vertical content alignment', 'middle' ), settingsOf().rowAlign === 'middle' ) );
console.log( '\nPadded, whitespace, the background inside, the dimming of a plain section and the loop of a section' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
const settingsAt = function( b ) { return builder._doc.model.blocks[b].settings };
const blockAt = function( b ) { return builder._doc.model.blocks[b] };
const reachOf = function( pane, label ) { return controlOf( pane, label ).el.disabled === false };
check( 'Padded is a switch of the cover and the parallax, which keep the room of the page around them: in reach where the section is one of the two - the hero is a cover - and writes the setting', reachOf( dialogContent(), paddedLabel ) === true && valueAt( dialogContent(), paddedLabel ) === false
	&& ( change( dialogContent(), paddedLabel, true ), settingsAt( 0 ).padded === true ) && valueAt( dialogContent(), paddedLabel ) === true );
change( dialogContent(), 'Cover', false );
check( '...with the cover gone - and no parallax to be one - the room around it goes too, and the switch sleeps: there is no cover to keep it around', settingsAt( 0 ).cover === null && settingsAt( 0 ).padded === false && reachOf( dialogContent(), paddedLabel ) === false && valueAt( dialogContent(), paddedLabel ) === false );
change( dialogContent(), 'Parallax effect', true );
check( '...a parallax is a picture that fills the section as a cover does: it wakes the switch again, and the cover that is switched on after it keeps what was set', reachOf( dialogContent(), paddedLabel ) === true && ( change( dialogContent(), paddedLabel, true ), change( dialogContent(), 'Cover', true ), settingsAt( 0 ).padded === true ) );
change( dialogContent(), 'Parallax effect', false );
check( '...and a parallax switched off in a section that is a cover keeps it too; a section that is neither - and was padded by a hand-written file - can still switch it off, which is all it can do', settingsAt( 0 ).padded === true && ( change( dialogContent(), 'Cover', false ), settingsAt( 0 ).padded === false ) && ( function() {
	builder._closeDialog();
	settingsAt( 3 ).padded = true;
	builder._openSettings( [ 3 ] );
	const asleep = reachOf( dialogContent(), paddedLabel ) === true && valueAt( dialogContent(), paddedLabel ) === true;
	change( dialogContent(), paddedLabel, false );
	return asleep && settingsAt( 3 ).padded === false && reachOf( dialogContent(), paddedLabel ) === false;
} )() );

builder._closeDialog();

// The background inside: colour, picture and scrim stand on the row of the section, not on the section
doc = open();
builder._renderEditor();
builder._openSettings( [ 1 ] );
const insideOf = function() { return controlOf( dialogContent(), 'Background inside' ) };
check( 'Background inside is a switch under the picture: asleep while the section has neither a colour nor a picture - there is nothing to put inside -, in reach with one of the two', insideOf().el.disabled === true && valueAt( dialogContent(), 'Background inside' ) === false
	&& ( change( dialogContent(), 'Background colour', 'alt' ), insideOf().el.disabled === false ) && tipOf( insideOf() ) === words['/_admin/builder/hint/inner-background'] );
change( dialogContent(), 'Background inside', true );
check( '...and writes the setting: the colour of the section is the row\'s then, as the model has it - the switch changes nothing else', settingsAt( 1 ).inner === true && settingsAt( 1 ).color === 'alt' );
change( dialogContent(), 'Background colour', '' );
check( '...the colour that goes while no picture is there takes the switch with it - it could say nothing but "inside" of nothing -, and the switch sleeps again', settingsAt( 1 ).inner === false && valueAt( dialogContent(), 'Background inside' ) === false && insideOf().el.disabled === true );
blockAt( 1 ).background = { slot : '/template/page-home/services/background', focus : 5 };
redrawBlocks();
check( '...a picture of the section is a thing to put inside as well, with no colour; and a cover or a parallax - the picture the section stands on - is not: the switch is asleep there, and switching either of them on takes the background out of the row again', insideOf().el.disabled === false
	&& ( change( dialogContent(), 'Background inside', true ), settingsAt( 1 ).inner === true ) && ( change( dialogContent(), 'Cover', true ), settingsAt( 1 ).inner === false && insideOf().el.disabled === true )
	&& ( change( dialogContent(), 'Cover', false ), insideOf().el.disabled === false ) && ( change( dialogContent(), 'Background inside', true ), change( dialogContent(), 'Parallax effect', true ), settingsAt( 1 ).inner === false && insideOf().el.disabled === true ) );
change( dialogContent(), 'Parallax effect', false );
check( '...with the picture taken away - and the colour of the section none - the background is not inside any more; with a colour left it stays where it was', ( function() {
	change( dialogContent(), 'Background colour', 'tint' );
	change( dialogContent(), 'Background inside', true );
	buttonOf( dialogContent(), 'Remove the picture' ).click();
	const kept = settingsAt( 1 ).inner === true && insideOf().el.disabled === false;
	change( dialogContent(), 'Background colour', '' );
	return kept && settingsAt( 1 ).inner === false && insideOf().el.disabled === true;
} )() );

// The scrim of a plain section: the dimming is for a picture, whichever kind of section holds it
builder._closeDialog();
doc = open();
builder._renderEditor();
builder._openSettings( [ 1 ] );
const dimOf = function() { return controlOf( dialogContent(), 'Dim the picture' ) };
check( 'the dimming is for a picture and for nothing else: a plain section has none to dim until it has one, then it is in reach as it is in a cover and a parallax - with the cover and the parallax off', dimOf().el.disabled === true && valueAt( dialogContent(), 'Dim the picture' ) === false
	&& ( blockAt( 1 ).background = { slot : '/template/page-home/services/background', focus : 5 }, redrawBlocks(), dimOf().el.disabled === false ) && settingsAt( 1 ).cover === null && settingsAt( 1 ).parallax === false );
change( dialogContent(), 'Dim the picture', true );
check( '...and writes the setting of the section, not of a cover: the plain section is dimmed over its picture - and loses the dimming with the picture, as the others do', settingsAt( 1 ).dim === true && ( buttonOf( dialogContent(), 'Remove the picture' ).click(), settingsAt( 1 ).dim === false && dimOf().el.disabled === true ) );
builder._closeDialog();

// The loop of a section: a section that makes a section of every element
doc = open();
builder._renderEditor();
builder._openSettings( [ 3 ] );
const loopTab = function() { return blockOf('properties').querySelector('.builder-loop') };
check( 'a section has its loop in its properties, before the animation, with the choice of the loop and nothing else while there is none: Static, and the loop of the registry whose cell is a section - the stack that says so (its "cell") is the only one, and none of those of a column is among them', JSON.stringify( blockIds() ) === '["surface","spacing","width","properties"]' && blockOf('properties').children[0] === loopTab()
	&& JSON.stringify( labelList( loopTab() ) ) === '["Loop"]' && valueAt( loopTab(), 'Loop' ) === '' && optionsOf( loopTab(), 'Loop' ) === '=Static,sections=Element loop' && loopTab().querySelectorAll('.builder-group').length === 0
	&& builder._sectionLoop( builder._registry ) === 'sections' && builder._sectionLoop( { stacks : { stack : { grid : true } } } ) === '' );
change( loopTab(), 'Loop', 'sections' );
check( '...the loop that is chosen runs over the first type of the project with the attributes the stack has, and the properties show its form in two groups, Data and Order - no cells, no gap, no equal height: a section is the cell -, with the id of the loop that is the id of the section, shown and not to be typed, and the sentence that says how the sections are numbered after it', ( function() {

	const loop = blockAt( 3 ).stack;
	const id = controlOf( loopTab(), 'Id of the loop' );

	// The attributes of the stack's own, where the registry gives it some, stand in a third group after the two
	const own = Object.keys( registry().stacks.sections.attributes || {} );

	return loop.name === 'sections' && loop.source === '/services' && loop.attributes.id === '' && titlesIn( loopTab() ) === ( own.length > 0 ? 'Data,Order,Own' : 'Data,Order' )
		&& JSON.stringify( labelList( loopTab() ) ) === JSON.stringify( [ 'Loop', 'Type', 'Query', 'Id of the loop', 'Sort by', 'Direction', 'Limit', 'Offset' ].concat( own.map( function( key ) { return registry().stacks.sections.attributes[key].label } ) ) )
		&& id.el.readOnly === true && id.el.value === 'contact' && tipOf( id ) === words['/_admin/builder/hint/sections-id'].replace( /%s/g, 'contact' );
} )() );
check( '...a section that runs a loop says so in its head, as a column does: "Element loop" and the type, a button that opens the properties of the section - and the loop is in no other block\'s way: the columns of the section are the cell, once for each element', ( function() {

	builder._closeDialog();
	builder._renderEditor();

	const item = preview().children[3].querySelector('.builder-frame-head .builder-status-item.is-loop');
	const opened = item !== null && item.localName === 'button' && item.textContent === 'Element loop · Services' && item.title === 'Settings of the loop: Element loop · Services' && iconOf( item ) === 'loop';

	item.click();

	return opened && dialogText('builder-dialog-kind') === 'Section' && openBlock() === 'properties' && builder._same( builder._sel, [ 3 ] )
		&& preview().children[0].querySelector('.builder-frame-head .builder-status-item.is-loop') === null;
} )() );
check( '...what the loop of a section takes of the other blocks is its own: the cover is as high as the page makes it - the height is asleep and is 90, the one height a call has - and the animation is the section\'s as it was, delay and duration among it: the document takes them of a loop, so the fields stay and what they hold is kept', ( function() {

	builder._closeDialog();
	builder._openSettings( [ 0 ] );

	if( valueAt( dialogContent(), 'Animation' ) !== 'own' )
		change( dialogContent(), 'Animation', 'own' );

	change( animationPane(), 'Delay', '200ms' );
	change( animationPane(), 'Duration', '1s' );

	const before = labelList( animationPane() );
	const height = controlOf( dialogContent(), heightLabel );
	const free = height.el.disabled === false && String( height.el.value ) === '100';

	change( dialogContent(), 'Loop', 'sections' );

	return free === true && controlOf( dialogContent(), heightLabel ).el.disabled === true && String( controlOf( dialogContent(), heightLabel ).el.value ) === '90' && settingsAt( 0 ).cover === 90
		&& JSON.stringify( labelList( animationPane() ) ) === JSON.stringify( before ) && before.indexOf('Delay') !== -1 && before.indexOf('Duration') !== -1 && settingsAt( 0 ).vpaDelay === '200ms' && settingsAt( 0 ).vpaDuration === '1s';
} )() );
check( '...a column of such a section may not run a loop of its own, and the loop of the section is not put in a loop: the one that would be the loop in a loop is held back, in the properties of the section as in those of a column, with the reason', ( function() {

	builder._closeDialog();
	builder._openSettings( [ 0, 0 ] );

	const column = controlOf( dialogContent(), 'Loop' ).el.disabled === true && dialogContent().querySelector('.nino-admin-notice').textContent.indexOf('The section makes a section of every element') === 0;

	builder._closeDialog();
	builder._openSettings( [ 1 ] );

	return column && controlOf( dialogContent(), 'Loop' ).el.disabled === true && dialogContent().querySelector('.nino-admin-notice').textContent.indexOf('A column of this section runs a loop of its own') === 0 && blockAt( 1 ).stack === null;
} )() );
check( '...and the picker of a column of such a section holds back what a loop does not take, as it does in the column that runs one', ( function() {
	builder._registry.components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false, group : 'global' };
	const looped = reasonOf( [ 0, 0 ], 'Stub alone' ) === 'Not in an element loop';
	const plain = reasonOf( [ 1, 0 ], 'Stub alone' ) === '';
	delete builder._registry.components['stub-alone'];
	return looped && plain;
} )() );
builder._closeDialog();
builder._openSettings( [ 0 ] );
change( dialogContent(), 'Loop', '' );
check( '...Static again takes the loop away and nothing else: the form is the choice alone, and the cover keeps the height it has', blockAt( 0 ).stack === null && JSON.stringify( labelList( loopTab() ) ) === '["Loop"]' && settingsAt( 0 ).cover === 90 && controlOf( dialogContent(), heightLabel ).el.disabled === false );
builder._closeDialog();

console.log( '\nWhat the preview says of a section and of a link, and where a click leaves the page' );

doc = open();
const planned = builder._doc.model;
planned.blocks[0].settings.padded = true;
planned.blocks[0].settings.whitespace = 'more';
planned.blocks[3].settings.whitespace = 'least';
planned.blocks[3].settings.inner = true;
planned.blocks[1].background = { slot : '/template/page-home/services/background', focus : 5 };
planned.blocks[1].settings.inner = true;
builder._renderEditor();
const statusClasses = function( b ) { return preview().children[b].querySelector('.builder-frame-head .builder-head-status').children.map( function( item ) { return item.className.replace( 'builder-status-item ', '' ) } ).join() };
const statusTitles = function( b ) { return preview().children[b].querySelector('.builder-frame-head .builder-head-status').children.map( function( item ) { return item.title } ).join(' | ') };
check( 'a section says what it is besides its picture, in the middle of its head, an icon each with its words for the title: that the cover is padded, that its whitespace is not the page\'s, that its background is inside, on the row, and - where the picture is on the row - that the picture is there', statusClasses( 0 ) === 'is-background,is-padded,is-whitespace'
	&& statusTitles( 0 ) === 'A picture behind the section | Padded: the cover keeps the room of the page around it | Whitespace: Wider' && statusClasses( 3 ) === 'is-inner,is-whitespace' && statusTitles( 3 ) === 'The background is inside, on the row | Whitespace: Much narrower'
	&& statusClasses( 1 ) === 'is-background,is-inner' && preview().children[1].querySelector('.is-background').title === 'A picture behind the row' && statusClasses( 2 ) === '' );
check( '...each is an icon of the sprite and a picture for a screen reader, with its title for its name - the words have a place for the whitespace in both languages', [ 0, 3, 1 ].every( function( b ) {
	return preview().children[b].querySelector('.builder-frame-head .builder-head-status').children.every( function( item ) {
		return item.localName === 'span' && item.getAttribute('role') === 'img' && item.getAttribute('aria-label') === item.title && iconOf( item ) !== null && iconsKnown( item ) === true;
	} );
} ) && [ 'padded', 'whitespace', 'inner', 'row-background', 'linked' ].every( function( word ) { return words['/_admin/builder/preview/'+ word] !== undefined } )
	&& words['/_admin/builder/preview/whitespace'].split('%s').length === 2 && /preview\/whitespace\]\]'\s*=>\s*'[^']*%s[^']*'/.test( fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' ) ) );
check( '...a padded section that is not a cover says nothing of it - the room is around a cover or a parallax, nothing else: what the file holds is what the Reader and the Writer keep - and a background that cannot be inside, because the section is a cover, is not said to be', ( function() {

	planned.blocks[3].settings.padded = true;
	planned.blocks[1].settings.cover = 90;
	builder._renderEditor();

	const found = statusClasses( 3 ) === 'is-inner,is-whitespace' && statusClasses( 1 ) === 'is-background';

	planned.blocks[3].settings.padded = false;
	planned.blocks[1].settings.cover = null;
	builder._renderEditor();

	return found;
} )() );
check( 'the background that is inside is the row\'s: the area of the row of a section is tinted - the frame is not, and the head keeps the colour of the page - and a section whose background is not inside is tinted as a whole, as it was', ( function() {

	const frame = preview().children[3];
	const body = frame.querySelector('.builder-frame-body');
	const inside = frame.classList.contains('builder-color-plain') && body.classList.contains('builder-color-primary');

	planned.blocks[3].settings.inner = false;
	builder._renderEditor();

	const outside = preview().children[3].classList.contains('builder-color-primary') && preview().children[3].querySelector('.builder-frame-body').classList.contains('builder-color-primary') === false;

	planned.blocks[3].settings.inner = true;
	builder._renderEditor();

	return inside && outside;
} )() );

check( 'a component that is a link and has an address to go to says so where the preview cannot draw an anchor: a small icon in its placeholder, with the word for its title - one that is no link, or has no address, says nothing', ( function() {

	planned.blocks[0].cols[0].components[0].attributes.href = '/contact';
	planned.blocks[0].cols[0].components[1].attributes.href = '';
	planned.blocks[0].cols[0].components[2].attributes.href = '/contact';
	builder._renderEditor();

	const linked = frameOf( '0.0.0' );
	const item = linked.querySelector('.builder-status-item.is-linked');

	return linked.classList.contains('is-linked') && item !== null && item.title === 'Linked' && item.getAttribute('aria-label') === 'Linked' && iconOf( item ) === 'link'
		&& frameOf( '0.0.1' ).classList.contains('is-linked') === false && frameOf( '0.0.1' ).querySelector('.is-linked') === null
		&& frameOf( '0.0.2' ).classList.contains('is-linked') === false && frameOf( '0.0.2' ).querySelector('.is-linked') === null && builder._registry.components.button.link === false;
} )() );
check( '...the preview plan says it from the schema - the key "link" - and from the attribute, and names no component', builder._preview( planned, builder._registry, 'l' )[0].cols[0].components.map( function( component ) { return component.linked } ).join() === 'true,false,false'
	&& builder._preview( Object.assign( {}, planned ), Object.assign( {}, builder._registry, { components : Object.assign( {}, builder._registry.components, { title : Object.assign( {}, builder._registry.components.title, { link : false } ) } ) } ), 'l' )[0].cols[0].components[0].linked === false );

// A click on what is in view selects it and leaves the page where it is; every other way to select brings the frame into view
doc = open();
builder._renderEditor();
screen.scrolled = [];
const target = frameOf( '1.0.0' );
target.click();
check( 'a click on what is in the preview selects it and does not scroll the page: the frame is marked, and nothing was brought into view', builder._same( builder._sel, [ 1, 0, 0 ] ) && target.classList.contains('is-selected') && screen.scrolled.length === 0 );
check( '...nor does a double click, which opens the form, nor a click on a section or a column, nor the tools of a frame - the settings - nor what is said in its head: a loop and an alignment', ( function() {

	target.dispatch('dblclick');
	const form = dialogBox().open === true && dialogText('builder-dialog-kind') === 'Component "Title (Section)"';
	builder._closeDialog();
	frameOf( '1' ).click();
	frameOf( '1.0' ).click();
	toolOf( '1', 'settings' ).click();
	builder._closeDialog();
	toolOf( '1.0.0', 'settings' ).click();
	builder._closeDialog();
	preview().querySelector('[data-path="1.1"] .builder-status-item.is-loop').click();
	builder._closeDialog();

	return form && screen.scrolled.length === 0 && builder._same( builder._sel, [ 1, 1, 'x' ] );
} )() );
check( '...a preview drawn again keeps the selection and does not scroll either', ( function() {
	builder._renderEditor();
	builder._renderPreview();
	return screen.scrolled.length === 0 && preview().querySelector('.is-selected') !== null;
} )() );
check( '...every other way to select brings the frame into view, as nearest as it can: a component that is added, a step, a copy beside the original, and a selection that is no click - the first source a refused save found red is one', ( function() {

	const scrolled = function( action ) {
		screen.scrolled = [];
		action();
		return screen.scrolled.map( function( entry ) { return entry.el.dataset.path+ ':'+ entry.options.block } ).join();
	};

	const added = scrolled( function() { builder._addComponent( [ 1, 0 ], 'text' ) } );
	const stepped = scrolled( function() { builder._stepNode( [ 1, 0, 0 ], 1 ) } );
	const copied = scrolled( function() { builder._duplicateAt( [ 1, 0, 0 ] ) } );
	const chosen = scrolled( function() { builder._select( [ 3 ] ) } );

	return added === '1.0.2:nearest' && stepped === '1.0.1:nearest' && copied === '1.0.1:nearest' && chosen === '3:nearest';
} )() );

console.log( '\nThe dialog of a column, and the viewports in its tables' );

doc = open();
builder._renderEditor();
builder._lastBlock = {};
builder._openSettings( [ 0, 0 ] );
const colOf = function() { return builder._doc.model.blocks[0].cols[0] };
check( 'the dialog of a column has the blocks the table has for it, in the order of the table - surface, spacing, width and properties - and opens on the first: the name of the section is not part of its title, no pencil is', dialogText('builder-dialog-kind') === 'Column of "hero"' && screen.getElementById('builder-dialog-name').hidden === true
	&& JSON.stringify( blockIds() ) === JSON.stringify( builder.BLOCKS.column.map( function( block ) { return block.id } ) ) && blockIds().join() === 'surface,spacing,width,properties' && openBlock() === 'surface' && screen.getElementById('builder-dialog-heading').querySelectorAll('.builder-rename').length === 0 );
check( '...the section\'s dialog and the column\'s are of their own: the block that was open last in a dialog of a section is not the one a column opens on - the builder keeps the last one for each kind of element, in memory and nowhere else', ( function() {
	headOf('width').click();
	builder._closeDialog();
	builder._openSettings( [ 0 ] );
	const section = openBlock();
	headOf('properties').click();
	builder._closeDialog();
	builder._openSettings( [ 0, 0 ] );
	const column = openBlock();
	builder._closeDialog();
	builder._openSettings( [ 0 ] );
	const again = openBlock();
	builder._closeDialog();
	builder._openSettings( [ 0, 0 ] );
	return section === 'surface' && column === 'width' && again === 'properties' && builder._lastBlock.section === 'properties' && builder._lastBlock.column === 'width' && sandbox.localStorage === undefined;
} )() );
headOf('width').click();

const widths = function() { return blockOf('width').querySelector('table') };
check( 'in a viewport the table of the widths has the row of that viewport only - the device with its icon and its word, the width, whether the column is hidden there - and under it a link to all of them', ( function() {
	const rows = widths().querySelectorAll('tbody tr');
	return texts( widths().querySelectorAll('thead th') ) === 'Device,Width,Hidden' && rows.length === 1 && rows[0].querySelector('th').textContent === 'Desktop' && iconOf( rows[0] ) === 'monitor' && rows[0].querySelector('th').getAttribute('scope') === 'row'
		&& valueAt( dialogContent(), 'Width, Desktop' ) === '66' && valueAt( dialogContent(), 'Hidden, Desktop' ) === false && controlOf( dialogContent(), 'Hidden, Desktop' ).kind === 'box'
		&& controlOf( dialogContent(), 'Width, Desktop' ).el.children.map( function( option ) { return option.value } ).join() === '25,33,50,66,75,100' && blockOf('width').querySelectorAll('.builder-link-btn').map( function( link ) { return link.textContent } ).join() === 'All viewports'
		&& widths().nextSibling === blockOf('width').querySelector('.builder-link-btn');
} )() );
check( '...and the rest of the block: the vertical alignment of the components and the horizontal alignment of the text, in this order, both as icons - the vertical one with Off as a word, and without a sentence - one under the other', JSON.stringify( labelList( blockOf('width') ) ) === JSON.stringify( [ 'Width, Desktop', 'Hidden, Desktop', 'Vertical alignment', 'Horizontal text alignment' ] )
	&& valueAt( dialogContent(), 'Horizontal text alignment' ) === 'left' && valueAt( dialogContent(), 'Vertical alignment' ) === '' && controlOf( dialogContent(), 'Vertical alignment' ).el.querySelectorAll('.builder-segment-option').map( function( option ) { return option.querySelector('input').value+ ':'+ ( iconOf( option ) ?? option.textContent ) } ).join()
		=== ':Off,start:stack-start,center:stack-center,end:stack-end' && tipOf( controlOf( dialogContent(), 'Vertical alignment' ) ) === null && blockOf('width').querySelector('.builder-line') === null );
change( dialogContent(), 'Width, Desktop', '33' );
change( dialogContent(), 'Horizontal text alignment', 'right' );
change( dialogContent(), 'Vertical alignment', 'center' );
check( '...what the block writes is the width of the viewport and the alignments', colOf().width.l === 33 && colOf().text === 'right' && colOf().stackAlign === 'center' && JSON.stringify( colOf().width ) === '{"s":100,"m":100,"l":33}' );
check( '...the head of the block says the widths of the three viewports, and that the column is hidden where it is, and the alignments - the widths where one is not the whole width', saysOf('width').textContent === [ '100/100/33 %', builder._optionLabel( 'stack-align', 'center' ), builder._optionLabel( 'text', 'right' ) ].join(' · ') );

headOf('spacing').click();
const colBoxes = function() { return blockOf('spacing').querySelectorAll('.builder-box') };
check( 'Spacing of a column: the gap between its components, then the box of its sides - outside above, inside above, left, right and below, outside below - the six sides of the column, none set to begin with', JSON.stringify( labelList( blockOf('spacing') ) ) === JSON.stringify( [ word('col/stack-gap'), sideLabel( 'above', 'outside' ), sideLabel( 'above', 'inside' ), sideLabel( 'left', 'inside' ), sideLabel( 'right', 'inside' ), sideLabel( 'below', 'inside' ), sideLabel( 'below', 'outside' ) ] )
	&& colBoxes().length === 1 && stepsOf( colBoxes()[0] ) === 'mt,pt,pl,pr,pb,mb' && colBoxes()[0].getAttribute('aria-label') === word('block/spacing/label') && builder.SIDES.every( function( side ) { return colOf()[side] === '' } ) && colOf().p === undefined
	&& optionsOf( blockOf('spacing'), word('col/stack-gap') ) === '='+ word('option/space-default')+ ',0=0,1=1,2=2,3=3,4=4,5=5,6=6' && tipOf( controlOf( blockOf('spacing'), word('col/stack-gap') ) ) === null
	&& colBoxes()[0].querySelector('.builder-help-tip').textContent === word('hint/spacing') );
change( dialogContent(), word('col/stack-gap'), '3' );
change( colBoxes()[0], sideLabel( 'above', 'inside' ), '1' );
change( colBoxes()[0], sideLabel( 'left', 'inside' ), '0' );
change( colBoxes()[0], sideLabel( 'below', 'outside' ), '4' );
check( '...what the block writes is the gap and the sides of the column, each a step as text - 0 is a step of its own and is not none - and into nothing else: the settings of the section have none of them', colOf().stackGap === '3' && colOf().pt === '1' && colOf().pl === '0' && colOf().mb === '4' && colOf().mt === '' && colOf().pb === '' && colOf().pr === ''
	&& valueAt( colBoxes()[0], sideLabel( 'left', 'inside' ) ) === '0' && builder._doc.model.blocks[0].settings.mt === '' && builder._doc.model.blocks[0].settings.pt === '' && builder._doc.model.blocks[0].settings.pl === ''
	&& saysOf('spacing').textContent === [ word('spacing/gap')+ ' 3', word('spacing/outside')+ ' –/4 · '+ word('spacing/inside')+ ' 1/–/0/–' ].join(' · ') );
change( colBoxes()[0], sideLabel( 'above', 'inside' ), '' );
check( '...none puts the step away again', colOf().pt === '' && colOf().pl === '0' );
change( dialogContent(), word('col/stack-gap'), '' );
change( colBoxes()[0], sideLabel( 'left', 'inside' ), '' );
change( colBoxes()[0], sideLabel( 'below', 'outside' ), '' );
check( '...a block with nothing set says nothing in its head', saysOf('spacing').textContent === '' && saysOf('spacing').hidden === true );
headOf('width').click();

buttonOf( dialogContent(), 'All viewports' ).click();
check( 'the link shows all the viewports: the preview is in the view of all of them (and its button the pressed one), and the tables of the dialog have a row for each - the link is gone, the block is where it was', builder._viewport === 'g' && preview().dataset.viewport === 'g' && screen.getElementById('builder-viewport-g').getAttribute('aria-pressed') === 'true'
	&& widths().querySelectorAll('tbody tr').map( function( row ) { return row.querySelector('th').textContent+ ':'+ iconOf( row ) } ).join() === 'Mobile:smartphone,Tablet:tablet,Desktop:monitor' && blockOf('width').querySelector('.builder-link-btn') === null
	&& JSON.stringify( labelList( blockOf('width') ).slice( 0, 6 ) ) === JSON.stringify( [ 'Width, Mobile', 'Hidden, Mobile', 'Width, Tablet', 'Hidden, Tablet', 'Width, Desktop', 'Hidden, Desktop' ] ) && openBlock() === 'width' && dialogBox().open === true );
builder._openSettings( [ 0, 0 ] );
check( 'in the view of all the viewports a dialog opened has the three rows from the start - and no link', widths().querySelectorAll('tbody tr').length === 3 && blockOf('width').querySelector('.builder-link-btn') === null );
change( dialogContent(), 'Width, Mobile', '50' );
change( dialogContent(), 'Width, Desktop', '25' );
change( dialogContent(), 'Hidden, Tablet', true );
change( dialogContent(), 'Hidden, Mobile', true );
change( dialogContent(), 'Hidden, Mobile', false );
check( '...what the table writes is the width of each viewport, and the viewports that hide the column: none that shows it', colOf().width.s === 50 && colOf().width.l === 25 && colOf().width.m === 100 && JSON.stringify( colOf().hidden ) === '{"m":true}' );

[ [ 's', 'Mobile', 'smartphone' ], [ 'm', 'Tablet', 'tablet' ] ].forEach( function( view ) {
	builder._setView( view[0] );
	builder._openSettings( [ 0, 0 ] );
	check( 'in the view of '+ view[1]+ ' the table has its row alone, with the link under it', widths().querySelectorAll('tbody tr').length === 1 && widths().querySelector('th[scope="row"]').textContent === view[1] && iconOf( widths() ) === view[2]
		&& JSON.stringify( labelList( blockOf('width') ).slice( 0, 2 ) ) === JSON.stringify( [ 'Width, '+ view[1], 'Hidden, '+ view[1] ] ) && blockOf('width').querySelectorAll('.builder-link-btn').length === 1 );
} );
builder._setView('l');

console.log( '\nThe background of a column' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
const colGround = function() { return blockOf('surface') };
const groundOf = function() { return builder._doc.model.blocks[1].cols[0] };
check( 'Surface of a column: the colour first, with the patch beside it, then the picture - the upload and its alt text, the focus -, the dimming, the offset of the picture at the sides and the toggle of the radius, one under the other; whether the picture is a parallax is the section\'s alone, and is not here', JSON.stringify( labelList( colGround() ) ) === JSON.stringify( [ 'Background colour', 'Upload a picture', 'Alt text (English (US))', 'Automatic focus', 'Manual focus', 'Dim the picture', offsetLabel('left'), offsetLabel('right'), radiusLabel ] )
	&& colGround().children[0].className === 'builder-with-swatch' && colGround().children[0].contains( controlOf( colGround(), 'Background colour' ).el ) && colGround().children[0].querySelector('.builder-swatch') !== null && openBlock() === 'surface' && colGround().querySelector('.builder-line') === null );
check( '...the colour is none of its own - which is the surroundings the column is in -, counter, and the colours of a section, in the words the section has for them', controlOf( colGround(), 'Background colour' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join()
	=== '=Like the surroundings,counter=Counter,alt=Alternate,tint=Tinted,dark=Dark,black=Black,primary=Brand colour,brand-alt=Second colour' && valueAt( colGround(), 'Background colour' ) === '' );
check( '...the choice is written into the column and into nothing else', ( function() {

	change( colGround(), 'Background colour', 'counter' );

	const counter = groundOf().color;

	change( colGround(), 'Background colour', 'dark' );

	const dark = groundOf().color;
	const rest = builder._doc.model.blocks[1].settings.color+ '/'+ builder._doc.model.blocks[1].cols[1].color;

	change( colGround(), 'Background colour', '' );

	return counter === 'counter' && dark === 'dark' && groundOf().color === '' && rest === '/';
} )() );
check( '...the patch is the ground the column ends up with - the section\'s where the column has none of its own - and for counter the counter of the ground of the section, by the pairs of Nino.css: default and alt, tint to default, dark and black, primary and brand-alt', ( function() {

	const pairs = { '' : 'alt', alt : 'plain', tint : 'plain', dark : 'black', black : 'dark', primary : 'brand-alt', 'brand-alt' : 'primary' };

	return Object.keys( pairs ).every( function( around ) {

		builder._closeDialog();
		builder._doc.model.blocks[1].settings.color = around;
		builder._openSettings( [ 1, 0 ] );
		builder._openBlock('surface');

		const swatch = colGround().querySelector('.builder-swatch');
		const own = swatch.dataset.color;

		change( colGround(), 'Background colour', 'counter' );

		const counter = swatch.dataset.color;

		change( colGround(), 'Background colour', 'tint' );

		const tint = swatch.dataset.color;

		change( colGround(), 'Background colour', '' );

		return own === ( around === '' ? 'plain' : around ) && counter === pairs[around] && tint === 'tint' && swatch.getAttribute('aria-hidden') === 'true' && groundOf().color === '';
	} );
} )() );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
check( '...the dimming is a switch under the rows of the picture: off while there is no picture to darken - it is greyed out and the column has none', controlOf( colGround(), 'Dim the picture' ).kind === 'switch' && controlOf( colGround(), 'Dim the picture' ).el.disabled === true && valueAt( colGround(), 'Dim the picture' ) === false && groundOf().dim === false );
groundOf().background = { slot : '/template/page-home/services/column-background', focus : null };
redrawBlocks();
check( '...with a picture it can be turned on, and writes the dim of the column', controlOf( colGround(), 'Dim the picture' ).el.disabled === false && ( change( colGround(), 'Dim the picture', true ), groundOf().dim === true ) && valueAt( colGround(), 'Dim the picture' ) === true );
change( colGround(), 'Automatic focus', false );

const middle = groundOf().background.focus;

change( colGround(), 'Manual focus', '9' );
check( '...the focus of the picture is the grid of the section\'s: it starts in the middle, a place is written as its number into the background of the column - none is null - and the section is left alone', middle === 5 && groundOf().background.focus === 9 && ( change( colGround(), 'Automatic focus', true ), groundOf().background.focus === null ) && builder._doc.model.blocks[1].background === null );
buttonOf( colGround(), 'Remove the picture' ).click();
check( '...a picture taken away takes the dimming with it: the column has no background, its dim is off in the model, and the switch is off and greyed out', groundOf().background === null && groundOf().dim === false && controlOf( colGround(), 'Dim the picture' ).el.disabled === true
	&& valueAt( colGround(), 'Dim the picture' ) === false && buttonOf( colGround(), 'Remove the picture' ).disabled === true );

console.log( '\nThe loop of a column' );

const loopPane = function() { return blockOf('properties').querySelector('.builder-loop') };

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1 ] );
check( 'in the view of one viewport the cells of a loop are a row, with the link to all of them under the table', loopPane().querySelectorAll('tbody tr').length === 1 && loopPane().querySelectorAll('.builder-link-btn').length === 1 && loopPane().querySelector('tbody th').textContent === 'Desktop' );
headOf('width').click();
buttonOf( blockOf('width'), 'All viewports' ).click();
check( '...the link pressed in the block of the width has the loop show all of them as well, which is not the block that is open', loopPane().querySelectorAll('tbody tr').length === 3 && loopPane().querySelectorAll('.builder-link-btn').length === 0 && blockOf('width').querySelectorAll('tbody tr').length === 3
	&& openBlock() === 'width' );
doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1, 'x' ] );
buttonOf( loopPane(), 'All viewports' ).click();
check( '...and the other way round', blockOf('width').querySelectorAll('tbody tr').length === 3 && loopPane().querySelectorAll('tbody tr').length === 3 && openBlock() === 'properties' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0 ] );
check( 'the loop of a column that runs none is the choice of the loop and nothing else: Static, and the loops of the registry - the plain one called the element loop', JSON.stringify( labelList( loopPane() ) ) === JSON.stringify( [ 'Loop' ] ) && valueAt( dialogContent(), 'Loop' ) === ''
	&& controlOf( dialogContent(), 'Loop' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Static,stack=Element loop,slider=Slider,filter=Filter,list=List' && loopPane().querySelectorAll('.builder-group').length === 0 );
change( dialogContent(), 'Loop', 'stack' );
check( '...a loop that is chosen is the kernel\'s, over the first type of the project, with the attributes it has - and the properties show its form in groups: the data it runs over, the order, the grid of the cells', ( function() {
	const stack = colOf().stack;
	return stack.name === 'stack' && stack.source === '/services' && stack.attributes.cols === '100 50 33' && stack.attributes.gap === '2' && stack.attributes.id === '' && titlesIn( loopPane() ) === 'Data,Order,Grid' && builder._doc.model.blocks[0].cols[0].components.length === 3;
} )() );

doc = open();
builder._renderEditor();
builder._setView('g');
builder._openSettings( [ 1, 1, 'x' ] );
check( 'the loop of a column opens the form of the column on its properties (the loop is no frame, but the column\'s own settings)', dialogText('builder-dialog-kind') === 'Column of "services"' && openBlock() === 'properties' );
check( 'the form of the loop: the kind, then the groups - Data (the type, a note, the query), Order (the field and the direction, the limit and the offset), Grid (the cells of each viewport, the gap and the equal height, the id) - and no field for the cards of the cells: the loop is the grid alone', titlesIn( loopPane() ) === 'Data,Order,Grid'
	&& JSON.stringify( labelList( loopPane() ) ) === JSON.stringify( [ 'Loop', 'Type', 'Query', 'Sort by', 'Direction', 'Limit', 'Offset', 'Cell width, Mobile', 'Cell width, Tablet', 'Cell width, Desktop', 'Gap between the cells', 'Cells of equal height', 'Id of the loop' ] ) );
check( '...the groups hold what they are called after: the type and the query in Data, the order in Order, the cells in Grid with the gap, the equal height and the id below - one field under the other', ( function() {
	const groups = loopPane().querySelectorAll('.builder-group');
	return groups.map( function( group ) { return labelList( group ).join('|') } ).join('~') === 'Type|Query~Sort by|Direction|Limit|Offset~Cell width, Mobile|Cell width, Tablet|Cell width, Desktop|Gap between the cells|Cells of equal height|Id of the loop'
		&& loopPane().querySelector('.builder-line') === null;
} )() );
const stackNow = function() { return builder._doc.model.blocks[1].cols[1].stack };
check( '...the stacks of the kernel carry no attribute for the cards of the cells any more - the cell is a div, and what stands in it is the cell\'s components -, so the loop has none to set and writes none: the grid is all it brings', ( function() {
	const kernel = Object.keys( builder._registry.stacks ).every( function( name ) { return Object.prototype.hasOwnProperty.call( builder._registry.stacks[name].defaults, 'card' ) === false } );
	change( loopPane(), 'Gap between the cells', '4' );
	const written = Object.prototype.hasOwnProperty.call( stackNow().attributes, 'card' ) === false;
	change( loopPane(), 'Gap between the cells', '2' );
	return kernel === true && written === true && loopPane().querySelector('.builder-group') !== null && labelList( loopPane() ).indexOf('Cards') === -1;
} )() );
check( '...and the words of the cards are gone from both languages: the field of the loop and the words of its options', [ 'en_US.php', 'de_DE.php' ].every( function( file ) {
	const texts = fs.readFileSync( path.join( __dirname, '../text/'+ file ), 'utf8' );
	return texts.indexOf('/_admin/builder/stack/card') === -1 && texts.indexOf('/_admin/builder/option/card-') === -1;
} ) && words['/_admin/builder/stack/card'] === undefined );
check( '...the type is one of the Elements panel\'s, with the title it has there', controlOf( loopPane(), 'Type' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '/services=Services (/services),/team=Team (/team)' );
check( '...the cells are a table with a row for each viewport - the device with its icon -, in the view of all of them', loopPane().querySelectorAll('tbody tr').map( function( row ) { return row.querySelector('th').textContent+ ':'+ iconOf( row ) } ).join() === 'Mobile:smartphone,Tablet:tablet,Desktop:monitor'
	&& loopPane().querySelector('table').querySelectorAll('thead th').length === 2 && texts( loopPane().querySelector('table').querySelectorAll('thead th') ) === 'Device,Cell width' );
check( '...a loop that names fewer widths than there are viewports takes the last for the others: two widths are three cells, one is three of one - and a width that is chosen writes all three', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	const shown = function( cols ) {
		stack.attributes.cols = cols;
		builder._openSettings( [ 1, 1, 'x' ] );
		return [ 'Mobile', 'Tablet', 'Desktop' ].map( function( device ) { return valueAt( loopPane(), 'Cell width, '+ device ) } ).join();
	};
	const two = shown( '100 50' );
	const one = shown( '33' );
	change( loopPane(), 'Cell width, Mobile', '25' );
	const written = stack.attributes.cols;
	stack.attributes.cols = '100 50 50';
	builder._openSettings( [ 1, 1, 'x' ] );
	return two === '100,50,50' && one === '33,33,33' && written === '25 33 33';
} )() );
check( '...the order is a field and two toggles: arrows up and down, a group of two radio buttons with the word for each as the title and the label - of which the one for ascending is on where the loop says title', ( function() {
	const direction = controlOf( loopPane(), 'Direction' ).el;
	return valueAt( loopPane(), 'Sort by' ) === 'title' && direction.getAttribute('role') === 'radiogroup' && valueAt( loopPane(), 'Direction' ) === 'asc'
		&& direction.querySelectorAll('.builder-segment-option').map( function( option ) { return option.querySelector('input').value+ ':'+ option.title+ ':'+ option.querySelector('input').getAttribute('aria-label')+ ':'+ iconOf( option ) } ).join() === 'asc:Ascending:Ascending:arrow-up,desc:Descending:Descending:arrow-down';
} )() );
change( loopPane(), 'Sort by', 'summary' );
change( loopPane(), 'Direction', 'desc' );
change( loopPane(), 'Limit', 12 );
change( loopPane(), 'Offset', 3 );
change( loopPane(), 'Cell width, Tablet', '33' );
change( loopPane(), 'Cells of equal height', false );
change( loopPane(), 'Gap between the cells', '4' );
change( loopPane(), 'Id of the loop', 'services-loop' );
change( loopPane(), 'Query', 'status=1' );
check( 'what the form writes is what the call carries: a field and a direction as the loop reads them, the numbers as text, the cells as three widths', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.attributes.sort === '-summary' && stack.attributes.limit === '12' && stack.attributes.offset === '3' && stack.attributes.cols === '100 33 50' && stack.attributes.autoheight === '0' && stack.attributes.gap === '4' && stack.attributes.id === 'services-loop' && stack.attributes.query === 'status=1';
} )() );
change( loopPane(), 'Direction', 'asc' );
check( '...the other arrow is ascending, and the field stays', builder._doc.model.blocks[1].cols[1].stack.attributes.sort === 'summary' && valueAt( loopPane(), 'Sort by' ) === 'summary' );
change( loopPane(), 'Sort by', 'price' );
check( '...and the direction stays where a field is chosen after it', builder._doc.model.blocks[1].cols[1].stack.attributes.sort === 'price' && ( change( loopPane(), 'Direction', 'desc' ), builder._doc.model.blocks[1].cols[1].stack.attributes.sort === '-price' ) );
change( loopPane(), 'Sort by', '' );
check( '...no field is no order', builder._doc.model.blocks[1].cols[1].stack.attributes.sort === '' );
builder._doc.model.blocks[1].cols[1].stack.attributes.sort = 'a,-b';
change( loopPane(), 'Type', '/team' );
check( 'a list of fields is a line of text that is left as it is, with no arrows beside it', labelList( loopPane() ).indexOf( 'Direction' ) === -1 && valueAt( loopPane(), 'Sort by' ) === 'a,-b' );
check( 'a loop of another type leaves the components where they are - what their sources mean there is red until changed', builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 3 && builder._doc.model.blocks[1].cols[1].stack.source === '/team'
	&& preview().querySelector('[data-path="1.1"] .is-loop').textContent === 'Element loop · Team' && preview().querySelector('[data-path="1.1"] .is-loop').title === 'Settings of the loop: Element loop · Team' );
change( loopPane(), 'Loop', '' );
check( 'Static drops the loop and keeps the components, and every field of the element is red - the status of the loop is gone from the head of the column', builder._doc.model.blocks[1].cols[1].stack === null && builder._doc.model.blocks[1].cols[1].components.length === 4 && builder._red( builder._doc.model, builder._registry ).length === 4
	&& JSON.stringify( labelList( loopPane() ) ) === JSON.stringify( [ 'Loop' ] ) && preview().querySelector('[data-path="1.1"] .is-loop') === null );
change( loopPane(), 'Loop', 'slider' );
check( 'a registered loop comes with its own attributes and the loop it has, over a type of the project; one with no grid has no group for it, and its id is among the data - its own attributes are a group of their own', ( function() {
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.name === 'slider' && stack.source === '/services' && stack.attributes.width === '75%' && stack.attributes.limit === '0' && builder._red( builder._doc.model, builder._registry ).length === 0 && titlesIn( loopPane() ) === 'Data,Order,Own'
		&& labelList( loopPane().querySelectorAll('.builder-group')[0] ).join() === 'Type,Query,Id of the loop' && labelList( loopPane().querySelectorAll('.builder-group')[2] ).join() === 'Slide width,Least slide width' && preview().querySelector('[data-path="1.1"] .is-loop').textContent === 'Slider · Services'
		&& preview().querySelector('[data-path="1.1"] .is-loop').title === 'Settings of the loop: Slider · Services';
} )() );
change( loopPane(), 'Slide width', '60%' );
check( '...and what the attributes of its own write is a string', builder._doc.model.blocks[1].cols[1].stack.attributes.width === '60%' );
change( loopPane(), 'Loop', 'stack' );
check( '...and going on to another loop keeps what both have', ( function() {
	builder._doc.model.blocks[1].cols[1].stack.attributes.limit = '5';
	change( loopPane(), 'Loop', 'list' );
	const stack = builder._doc.model.blocks[1].cols[1].stack;
	return stack.name === 'list' && stack.attributes.limit === '5' && stack.source === '/services' && titlesIn( loopPane() ) === 'Data,Order,Own' && labelList( loopPane().querySelectorAll('.builder-group')[2] ).join() === 'Style';
} )() );
change( loopPane(), 'Loop', 'filter' );
check( '...a loop with a grid and attributes of its own has all four groups', titlesIn( loopPane() ) === 'Data,Order,Grid,Own' && labelList( loopPane().querySelectorAll('.builder-group')[3] ).join() === 'Filter by,Label of the first button' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1, 'x' ] );
change( loopPane(), 'Loop', 'filter' );
check( 'the kernel\'s filter stack has the grid and its id like the plain one, and no row for cards: the attribute is not carried over to it', labelList( loopPane() ).indexOf('Id of the loop') !== -1 && labelList( loopPane() ).indexOf('Cards') === -1 && stackNow().name === 'filter'
	&& Object.prototype.hasOwnProperty.call( stackNow().attributes, 'card' ) === false && stackNow().attributes.gap === '2' );

console.log( '\nThe animation and the classes of a column' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0 ] );
const colAnimation = function() { return blockOf('properties').querySelectorAll('.builder-group').find( function( group ) { return group.querySelector('.builder-group-title').textContent === word('group/animation') } ).querySelector('.builder-group-body') };
const pickedOff = function( label ) { return controlOf( colAnimation(), label ).el.disabled === true };
check( 'the animation of a column is its own, and has no like, off or own: the effect and its strength, the speed and how often - the effect is none before it is the plain one, and the words under it are those of a column', JSON.stringify( labelList( colAnimation() ) ) === JSON.stringify( [ 'Effect', 'Strength', 'Speed', 'Repeat' ] )
	&& controlOf( colAnimation(), 'Effect' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'none=No animation,=Default - fade in and rise,zoom=Zoom,zoom-out=Zoom out,slide-left=Slides left,slide-right=Slides right,slide-up=Slides up,slide-down=Slides down,flip=Flip,blur=Blur'
	&& valueAt( colAnimation(), 'Effect' ) === 'none' && colAnimation().querySelector('.builder-line') === null
	&& controlOf( colAnimation(), 'Effect' ).el.title === '' && colAnimation().querySelector('.nino-admin-hint') === null );
check( '...it has no delay and no duration, whatever the animation is: the file keeps a column\'s animation as classes, and a column has no attribute for either - the animation of a section has both', ( function() {
	const column = labelList( colAnimation() );
	builder._openSettings( [ 0 ] );
	const section = labelList( dialogContent() );
	builder._openSettings( [ 0, 0 ] );
	return column.indexOf('Delay') === -1 && column.indexOf('Duration') === -1 && section.indexOf('Delay') !== -1 && section.indexOf('Duration') !== -1;
} )() );
check( '...with none there is nothing to speed up or to repeat: the speed and the repeat are none to choose, as the strength is', pickedOff('Speed') === true && pickedOff('Repeat') === true && radiosOf( controlOf( colAnimation(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === true } ) );
change( colAnimation(), 'Effect', '' );
check( '...the plain effect has them on, and the strength still off - an effect that has one has all three', colOf().vpa === '' && pickedOff('Speed') === false && pickedOff('Repeat') === false && radiosOf( controlOf( colAnimation(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === true } )
	&& ( change( colAnimation(), 'Effect', 'zoom-out' ), pickedOff('Speed') === false && pickedOff('Repeat') === false && radiosOf( controlOf( colAnimation(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === false } ) ) );
check( '...none is no class, the plain effect the bare class, an effect with its strength the one word - and none switches them off again', colOf().vpa === 'zoom-out-medium' && ( change( colAnimation(), 'Effect', '' ), colOf().vpa === '' ) && ( change( colAnimation(), 'Effect', 'zoom-out' ), colOf().vpa === 'zoom-out-medium' ) && ( change( colAnimation(), 'Strength', 'hard' ), colOf().vpa === 'zoom-out-hard' )
	&& ( change( colAnimation(), 'Effect', 'none' ), colOf().vpa === null ) && pickedOff('Speed') === true && pickedOff('Repeat') === true && radiosOf( controlOf( colAnimation(), 'Strength' ).el ).every( function( radio ) { return radio.disabled === true } ) );
change( colAnimation(), 'Effect', 'zoom' );
change( colAnimation(), 'Speed', 'slow' );
change( colAnimation(), 'Repeat', 'repeat' );
check( '...the speed and the repeat write their settings - and the delay and the duration of a column are what they were: none', colOf().vpaSpeed === 'slow' && colOf().vpaMode === 'repeat' && colOf().vpaDelay === '' && colOf().vpaDuration === '' && colOf().vpa === 'zoom-hard' );
check( '...a repeat that a hand-written file has (visible, visible-once) stays in the list and is shown, so that reading the file changes nothing', ( function() {
	colOf().vpaMode = 'visible-once';
	builder._openSettings( [ 0, 0 ] );
	const modes = controlOf( colAnimation(), 'Repeat' ).el.children.map( function( option ) { return option.value } ).join();
	const shown = valueAt( colAnimation(), 'Repeat' );
	colOf().vpaMode = 'repeat';
	return modes === ',repeat,visible-once' && shown === 'visible-once' && pickedOff('Repeat') === false;
} )() );
change( dialogContent(), 'Class of the column', 'my-col' );
const colCustom = blockOf('properties').querySelectorAll('.builder-group').find( function( group ) { return group.querySelector('.builder-group-title').textContent === word('group/custom') } ).querySelector('.builder-group-body');
check( 'the class of a column is the only field of the CSS classes group, with what Nino.css has to say of classes behind its question mark', JSON.stringify( labelList( colCustom ) ) === JSON.stringify( [ 'Class of the column' ] ) && colOf().custom === 'my-col' && tipOf( controlOf( colCustom, 'Class of the column' ) ) === words['/_admin/builder/hint/custom'] );
console.log( '\nThe dialog of a component' );

doc = open();
builder._renderEditor();
builder._lastBlock = {};
builder._openSettings( [ 0, 0, 0 ] );
const componentAt = function( at ) { return builder._doc.model.blocks[at[0]].cols[at[1]].components[at[2]] };
// Whether the blocks of a dialog are some of the blocks the table has for the kind of element, in the order of the table
const inTableOrder = function( kind, ids ) {
	const order = builder.BLOCKS[kind].map( function( block ) { return block.id } );
	return ids.every( function( id, at ) { return order.indexOf( id ) !== -1 && ( at === 0 || order.indexOf( id ) > order.indexOf( ids[at - 1] ) ) } );
};
check( 'the dialog of a component has the blocks it has something for, in the order of the table - for a text that is a link: content, link, spacing and properties; the title names the component by its label, and no pencil is in it', dialogText('builder-dialog-kind') === 'Component "Title (Section)"'
	&& blockIds().join() === 'content,link,spacing,properties' && inTableOrder( 'component', blockIds() ) && screen.getElementById('builder-dialog-name').hidden === true && dialogBox().classList.contains('is-wide') === false );
check( '...Content: the source as text, with an icon button beside it that opens the dialog of the sources - and under it, for a key the project knows, the text it holds in the language the editor works in', ( function() {
	const row = blockOf('content').querySelector('.builder-source');
	const button = row.querySelector('button');
	return blockOf('content').children.length === 2 && blockOf('content').children[0] === row && blockOf('content').children[1].className === 'builder-content-extra' && row.querySelector('.builder-source-caption').textContent === 'Current:' && row.querySelector('.builder-source-caption').hidden === false
		&& row.querySelector('.builder-source-current').textContent === '/template/page-home/hero/title' && row.querySelector('.builder-source-name').hidden === true
		&& button.className === 'builder-icon-btn' && button.title === 'Source' && button.getAttribute('aria-label') === 'Source' && iconOf( button ) === 'source' && JSON.stringify( labelList( blockOf('content') ) ) === '["Text (English (US))"]';
} )() );
check( '...the head of the content says the first words of the text, in the language of the editor - and nothing of what is not there', saysOf('content').textContent === 'Welcome' && saysOf('content').hidden === false );
check( '...Properties: the variants first, a row of buttons for each, one to a line, in the order of the schema - the words of the panel for the values, Default for none - and the one the component has is the one that is chosen; then the other attributes in the order of the schema, here the HTML tag alone, a select that offers the tags the registry offers, as many and in its order, whichever tags the kernel has; and the class of the component last',
	JSON.stringify( labelList( blockOf('properties') ) ) === JSON.stringify( [ 'Style', 'Text colour', 'HTML tag', 'Class of the component' ] ) && controlsIn( blockOf('properties') ).map( function( control ) { return control.kind } ).join() === 'group,group,select,text'
	&& segmentOf( blockOf('properties'), 'Style' ) === ':Default,loud:Loud,quiet:Quiet' && segmentOf( blockOf('properties'), 'Text colour' ) === ':Default,muted:Muted,accent:Accent' && valueAt( blockOf('properties'), 'Style' ) === 'loud' && valueAt( blockOf('properties'), 'Text colour' ) === ''
	&& controlOf( blockOf('properties'), 'HTML tag' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === registryFixture.components[componentAt( [ 0, 0, 0 ] ).name].attributes.tag.options.map( function( option ) { return option+ '='+ option } ).join()
	&& registryFixture.components[componentAt( [ 0, 0, 0 ] ).name].attributes.tag.options.length >= 3 && valueAt( blockOf('properties'), 'HTML tag' ) === 'h3' );
check( '...the head of the properties says what the component has that is not the default: the style it has, and nothing of the colour it has none of', saysOf('properties').textContent === words['/_admin/builder/option/variant-loud'] && saysOf('properties').hidden === false );
check( '...Link: the two attributes every link has and the schema does not declare - where it leads, as a source, and the window it opens in, a select of the same window and a new one - and no note over them', ( function() {
	const body = blockOf('link');
	return classes( body.children ) === 'builder-source,nino-admin-field' && JSON.stringify( labelList( body ) ) === JSON.stringify( [ 'Opens in' ] ) && body.querySelector('.nino-admin-hint') === null
		&& body.querySelector('.builder-source-name').textContent === 'Link' && body.querySelector('.builder-source-current').textContent === 'none' && controlOf( body, 'Opens in' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Same window,_blank=New window'
		&& valueAt( body, 'Opens in' ) === '' && saysOf('link').hidden === true;
} )() );
change( dialogContent(), 'HTML tag', 'h4' );
change( dialogContent(), 'Style', '' );
change( dialogContent(), 'Text colour', 'accent' );
change( dialogContent(), 'Opens in', '_blank' );
buttonOf( blockOf('link'), 'Source' ).click();
buttonOf( pickerStrip(), 'Static' ).click();
fixedField().value = '/contact';
buttonOf( pickerPane('fixed'), 'Use it' ).click();
change( dialogContent(), 'Class of the component', 'big' );
check( '...and write the attributes - the properties, the variants and the link of the component under the names of the schema and of the call: href and target - the class of the component the class', componentAt( [ 0, 0, 0 ] ).attributes.tag === 'h4' && componentAt( [ 0, 0, 0 ] ).attributes.style === '' && componentAt( [ 0, 0, 0 ] ).attributes.color === 'accent'
	&& componentAt( [ 0, 0, 0 ] ).attributes.target === '_blank' && componentAt( [ 0, 0, 0 ] ).attributes.href === '/contact' && componentAt( [ 0, 0, 0 ] ).attributes['class'] === 'big'
	&& tipOf( controlOf( blockOf('properties'), 'Class of the component' ) ) === words['/_admin/builder/hint/custom'] && changes === 6
	&& valueAt( blockOf('properties'), 'Text colour' ) === 'accent' && blockOf('link').querySelector('.builder-source-current').textContent === '“/contact”'
	&& saysOf('link').textContent === [ '/contact', words['/_admin/builder/option/target-blank'] ].join(' · ') );

builder._openSettings( [ 0, 0, 2 ] );
check( 'a link is a source of its own - the address stands in the link block with the window it opens in, whichever the component: the button has its link as an attribute of its own, and the block for it, and its other attributes - style, size and width - are its properties', dialogText('builder-dialog-kind') === 'Component "Button"'
	&& blockIds().join() === 'content,link,spacing,properties' && classes( blockOf('link').children ) === 'builder-source,nino-admin-field' && JSON.stringify( labelList( blockOf('link') ) ) === JSON.stringify( [ 'Opens in' ] )
	&& blockOf('link').querySelector('.builder-source-name').textContent === 'Link' && blockOf('link').querySelector('.builder-source-current').textContent === '/_nino/webpage/contact/uri' && blockOf('link').querySelector('.builder-source-caption').hidden === true
	&& JSON.stringify( labelList( blockOf('properties') ) ) === JSON.stringify( [ 'Style', 'Size', 'Width', 'Class of the component' ] ) && controlsIn( blockOf('properties') ).map( function( control ) { return control.kind } ).join() === 'select,group,group,text' );
check( '...a choice of a few the schema says is a segment is a row of buttons - the size and the width of a button, with the words of the panel - and writes the value like a select does, the empty one for the default', segmentOf( dialogContent(), 'Size' ) === ':Normal,small:Small,big:Big' && segmentOf( dialogContent(), 'Width' ) === ':Automatic,full:Full width'
	&& valueAt( dialogContent(), 'Size' ) === '' && ( change( dialogContent(), 'Width', 'full' ), componentAt( [ 0, 0, 2 ] ).attributes.width === 'full' ) && valueAt( dialogContent(), 'Width' ) === 'full'
	&& ( change( dialogContent(), 'Size', 'big' ), componentAt( [ 0, 0, 2 ] ).attributes.size === 'big' ) && ( change( dialogContent(), 'Size', '' ), componentAt( [ 0, 0, 2 ] ).attributes.size === '' ) );
builder._openSettings( [ 1, 1, 0 ] );
check( '...the image has a block for its surface, which holds the toggle of its radius and the one of its hover, and its properties in the order of the schema - the focus, the switch for none and the nine places, among them - with its class last', ( function() {

	const attributes = registry().components.image.attributes;
	const places = [ '', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
	const focus = function( name ) { return attributes[name].type === 'select' && attributes[name].options.join() === places.join() };
	const kinds = { select : 'select', string : 'text', bool : 'switch' };

	return dialogText('builder-dialog-kind') === 'Component "Image"' && focus( 'focus' ) === true && blockIds().join() === 'content,link,surface,spacing,properties' && inTableOrder( 'component', blockIds() )
		&& JSON.stringify( labelList( blockOf('surface') ) ) === JSON.stringify( [ radiusLabel, hoverLabel ] )
		&& JSON.stringify( labelList( blockOf('properties') ) ) === JSON.stringify( [].concat.apply( [], Object.keys( attributes ).map( function( name ) { return focus( name ) === true ? [ 'Automatic focus', 'Focus' ] : [ attributes[name].label ] } ) ).concat( [ 'Class of the component' ] ) )
		&& controlsIn( blockOf('properties') ).map( function( control ) { return control.kind } ).join() === Object.keys( attributes ).map( function( name ) { return focus( name ) === true ? 'switch,group' : kinds[attributes[name].type] } ).concat( [ 'text' ] ).join();
} )() );

builder._doc.model.blocks[0].cols[0].components.push( { name : 'spacer', source : '', text : null, attributes : { size : '2', 'class' : '' } } );
builder._doc.model.blocks[0].cols[0].components.push( { name : 'unknown-one', source : '', text : null, attributes : { 'class' : '' } } );
builder._doc.model.blocks[0].cols[0].components.push( { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>x</p>' } );
const blocksOfComponent = function( at ) { builder._openSettings( at ); return blockIds().join() };
const registryWith = registry();
registryWith.components.divider = { label : 'Divider', source : 'none', loop : true, preview : 'block', attributes : [], defaults : { 'class' : '' } };
builder._doc.model.blocks[0].cols[0].components.push( { name : 'divider', source : '', text : null, attributes : { 'class' : '' } } );
builder._registry = registryWith;
check( 'a component has the blocks it has something for: no content where it has no source (a spacer, a divider), the spacing and the properties - the class of the component - always, and the editor of the content for a component the registry does not know and for one whose content is HTML',
	blocksOfComponent( [ 0, 0, 3 ] ) === 'spacing,properties' && blocksOfComponent( [ 0, 0, 4 ] ) === 'content,spacing,properties' && blocksOfComponent( [ 0, 0, 5 ] ) === 'content,spacing,properties' && blocksOfComponent( [ 0, 0, 6 ] ) === 'spacing,properties' );
builder._openSettings( [ 0, 0, 3 ] );
check( '...the spacer holds its size, and its title is its label', dialogText('builder-dialog-kind') === 'Component "Spacer"' && JSON.stringify( labelList( blockOf('properties') ) ) === JSON.stringify( [ 'Size', 'Class of the component' ] ) && valueAt( dialogContent(), 'Size' ) === '2' );
builder._openSettings( [ 0, 0, 4 ] );
check( '...a component the registry does not know is called by its name, and has a source of the text kind', dialogText('builder-dialog-kind') === 'Component "unknown-one"' && blockOf('content').querySelector('.builder-source') !== null );
builder._openSettings( [ 0, 0, 5 ] );
check( '...the content that is HTML has the editor of the workbench in its block, and a dialog of the width of the others', blockOf('content').querySelector('.builder-content') !== null && dialogBox().classList.contains('is-wide') === false );
console.log( '\nThe table of the blocks is the one place that says what a dialog shows' );

doc = open();
builder._renderEditor();
builder._lastBlock = {};
// The dialog of an element opened as the page opens it - with the table the page has, or with another: a copy of it that was changed
const openWith = function( at, table ) {

	const original = builder.BLOCKS;

	builder.BLOCKS = table === undefined ? original : table;

	try {
		builder._openSettings( at );
	} finally {
		builder.BLOCKS = original;
	}
};
const tableWith = function() { return JSON.parse( JSON.stringify( builder.BLOCKS ) ) };
// A control struck out of the table of a kind of element, and put at the end of a block of it where one is named
const tableMoved = function( table, kind, name, to ) {

	table[kind].forEach( function( block ) { block.controls = block.controls.filter( function( control ) { return control !== name } ) } );

	if( to !== undefined )
		table[kind].find( function( block ) { return block.id === to } ).controls.push( name );

	return table;
};
const drawnIn = function( id, key ) { return blockOf( id ).all().filter( function( el ) { return el.dataset.key === key } ).length };
const drawnAnywhere = function( key ) { return blockIds().reduce( function( count, id ) { return count + drawnIn( id, key ) }, 0 ) };
// The controls of a component as its dialog makes them, and each of them once whatever the names it stands under
const controlsOfComponent = function( at ) {
	const made = builder._componentControls( at, { editors : [], editor : null, typed : false } );
	return Array.from( new Set( Object.keys( made ).map( function( name ) { return made[name] } ) ) );
};
const everyComponent = function( then ) {

	return Object.keys( builder._registry.components ).every( function( name ) {

		const components = builder._doc.model.blocks[0].cols[0].components;

		components.push( builder._newComponent( builder._doc.model, builder._registry, builder._doc.file, [ 0, 0 ], name, known() ) );

		return then( name, [ 0, 0, components.length - 1 ] );
	} );
};

check( 'the table lists every control of a section and of a column once, and nothing that is none: the names of the table and the controls the dialog makes are the same', [ [ 'section', builder._sectionControls( [ 0 ] ) ], [ 'column', builder._colControls( [ 0, 0 ] ) ] ].every( function( pair ) {

	const listed = [].concat( ...builder.BLOCKS[pair[0]].map( function( block ) { return block.controls } ) );

	return new Set( listed ).size === listed.length && JSON.stringify( listed.slice().sort() ) === JSON.stringify( Object.keys( pair[1] ).sort() );
} ) );
check( '...and of a component: each control it has is listed, once - in a block of the kinds of content, link, surface, spacing and properties - and every attribute of its schema stands in one control, the one of its group, whichever component of the registry it is', everyComponent( function( name, at ) {

	const listed = [].concat( ...builder.BLOCKS.component.map( function( block ) { return block.controls } ) );
	const made = builder._componentControls( at, { editors : [], editor : null, typed : false } );
	const written = [].concat( ...controlsOfComponent( at ).map( function( control ) { return control.keys } ) );

	return Object.keys( made ).every( function( key ) { return listed.indexOf( key ) !== -1 } ) && new Set( listed ).size === listed.length
		&& Object.keys( builder._declared( builder._registry.components[name] ) ).every( function( attribute ) { return written.filter( function( key ) { return key === attribute } ).length === 1 } );
} ) );
check( '...each control draws marks of the settings it writes only, whichever element it is of: a control that has keys marks nothing else', ( function() {

	const only = function( control ) {
		const scratch = screen.createElement('div');
		control.build( scratch );
		return scratch.all().filter( function( el ) { return el.dataset.key !== undefined } ).every( function( el ) { return control.keys.indexOf( el.dataset.key ) !== -1 } );
	};
	const section = builder._sectionControls( [ 0 ] );
	const column = builder._colControls( [ 0, 0 ] );

	return Object.keys( section ).every( function( name ) { return only( section[name] ) } ) && Object.keys( column ).every( function( name ) { return only( column[name] ) } )
		&& everyComponent( function( name, at ) { return controlsOfComponent( at ).every( only ) } );
} )() );

doc = open();
builder._renderEditor();
check( 'what a dialog draws in a block is what the table lists for it: in the dialog of a section, of a column and of every component of the registry, whatever is marked with a setting in a block is a setting of a control the table lists for that block', ( function() {

	const within = function() { return blockIds().every( function( id ) { return keysDrawn( id ).every( function( key ) { return keysHeld( id ).indexOf( key ) !== -1 } ) } ) };

	builder._openSettings( [ 0 ] );

	const section = within();

	builder._openSettings( [ 0, 0 ] );

	const column = within();

	return section === true && column === true && everyComponent( function( name, at ) { builder._openSettings( at ); return within() } );
} )() );

doc = open();
builder._renderEditor();
builder._doc.model.blocks[0].settings.arrow = true;
openWith( [ 0 ], tableMoved( tableWith(), 'section', 'arrow', 'properties' ) );
check( 'a control moved in the table is drawn in the block it was moved to: the arrow of a section, moved from Surface to Properties, is a field of the properties and none of the surface - and the heads follow it, the one of the properties says it and the one of the surface does not', drawnIn( 'properties', 'arrow' ) === 1 && drawnIn( 'surface', 'arrow' ) === 0
	&& saysOf('properties').textContent.indexOf( word('section/arrow') ) !== -1 && saysOf('surface').textContent.indexOf( word('section/arrow') ) === -1 );
openWith( [ 0 ], tableMoved( tableWith(), 'section', 'whitespace', 'width' ) );
check( '...so is a setting that has a block of its own to leave: the whitespace of a section, moved from Spacing to Width and alignment, is a select of the width and none of the spacing', drawnIn( 'width', 'whitespace' ) === 1 && drawnIn( 'spacing', 'whitespace' ) === 0 && labelList( blockOf('width') ).indexOf( word('section/whitespace') ) !== -1 );
openWith( [ 0 ], tableMoved( tableWith(), 'section', 'border' ) );
check( 'a control struck out of every block of the table is not drawn at all: the border of a section is no field of any block - and the switch for its brand colour, which the table still lists, is', drawnAnywhere( 'border' ) === 0 && drawnAnywhere( 'borderPrimary' ) === 1 );
openWith( [ 0 ], tableMoved( tableWith(), 'section', 'id' ) );
check( '...nor is the id of a section, which is a control like the others: the field is gone, the pencil in the title is not', drawnAnywhere( 'id' ) === 0 && labelList( dialogContent() ).indexOf( idLabel ) === -1 && screen.getElementById('builder-dialog-heading').querySelectorAll('.builder-rename').length === 1 );
openWith( [ 0 ], ( function() {
	const table = tableWith();
	table.section.find( function( block ) { return block.id === 'width' } ).controls = [];
	return table;
} )() );
check( 'a block none of whose controls the table lists is not drawn: the dialog of a section with no control in Width and alignment has the other three blocks, in the order of the table', blockIds().join() === 'surface,spacing,properties' );
openWith( [ 0 ] );
settingsOf().custom = 'one';
settingsOf().rowCustom = 'two';
settingsOf().radius = 'alternative';
settingsOf().rowRadius = 'alternative';
redrawBlocks();
check( 'a head says a word once, however many controls say it: the classes of a section and of its row, the radius of its cover and of its row', saysOf('properties').textContent.split( word('group/custom') ).length === 2 && saysOf('surface').textContent.split( word('common/radius-alt') ).length === 2 );
settingsOf().custom = '';
settingsOf().rowCustom = '';
settingsOf().radius = '';
settingsOf().rowRadius = '';
openWith( [ 0 ], tableMoved( tableWith(), 'section', 'rowPt', 'surface' ) );
check( 'a control that writes several settings is drawn once, where the first of the names it stands under is: the box of the row, moved to Surface by one of its names, is there and not twice - and the other names it stands under draw nothing more', drawnIn( 'surface', 'rowPt' ) === 1 && drawnIn( 'spacing', 'rowPt' ) === 0 && drawnAnywhere( 'rowPb' ) === 1 );
openWith( [ 0, 0 ], tableMoved( tableWith(), 'column', 'stackGap', 'width' ) );
check( 'the same holds for a column: the gap between its components, moved from Spacing to Width and alignment, is a select of the width', drawnIn( 'width', 'stackGap' ) === 1 && drawnIn( 'spacing', 'stackGap' ) === 0 );
openWith( [ 0, 0 ], tableMoved( tableWith(), 'column', 'dim' ) );
check( '...a control struck out is not drawn: the dimming of the picture behind a column', drawnAnywhere( 'dim' ) === 0 && drawnAnywhere( 'color' ) === 1 );
openWith( [ 0, 0 ], tableMoved( tableWith(), 'column', 'width', 'properties' ) );
const viewports = builder._views().length;
const stays = drawnIn( 'width', 'width' ) === viewports && drawnIn( 'width', 'hidden' ) === viewports && drawnAnywhere( 'width' ) === viewports;
openWith( [ 0, 0 ], tableMoved( tableMoved( tableWith(), 'column', 'width', 'properties' ), 'column', 'hidden', 'properties' ) );
check( '...a control that writes several settings goes with all of its names: the table of the viewports - the width of the column and where it is hidden - stays where the other name stands when one of them is moved, and is in the properties, one row to a viewport and none left behind, when both are', stays === true
	&& drawnIn( 'properties', 'width' ) === viewports && drawnIn( 'properties', 'hidden' ) === viewports && drawnIn( 'width', 'width' ) === 0 && drawnIn( 'width', 'hidden' ) === 0 );
check( '...and the status of a frame that opens the dialog on a block opens the one the table has put the control in: the loop of a section and of a column, the alignments of a column - wherever the table has them', ( function() {
	const original = builder.BLOCKS;
	builder.BLOCKS = tableMoved( tableWith(), 'column', 'stackAlign', 'surface' );
	const block = builder._blockOf( 'column', 'stackAlign' );
	builder.BLOCKS = original;
	return block === 'surface' && builder._blockOf( 'column', 'stackAlign' ) === 'width' && builder._blockOf( 'column', 'text' ) === 'width' && builder._blockOf( 'section', 'stack' ) === 'properties' && builder._blockOf( 'column', 'stack' ) === 'properties' && builder._blockOf( 'column', 'nothing' ) === undefined;
} )() );
openWith( [ 0, 0, 0 ], tableMoved( tableMoved( tableWith(), 'component', 'class', 'spacing' ), 'component', 'attributes' ) );
check( 'a component has its controls by the table too: its class, moved to Spacing, is a field of the spacing and none of the properties, and its other attributes, struck out, are not drawn - the HTML tag of a title', drawnIn( 'spacing', 'class' ) === 1 && drawnIn( 'properties', 'class' ) === 0 && drawnAnywhere( 'tag' ) === 0 );
openWith( [ 0, 0, 0 ], tableMoved( tableWith(), 'component', 'variants', 'surface' ) );
check( '...its variants, moved to Surface, are the surface - a block the title had none of - and the attributes that are none of them stay among the properties', blockIds().join() === 'content,link,surface,spacing,properties' && drawnIn( 'surface', 'style' ) === 1 && drawnIn( 'properties', 'style' ) === 0 && drawnIn( 'properties', 'tag' ) === 1 );
openWith( [ 0, 0, 0 ], tableMoved( tableWith(), 'component', 'source' ) );
check( '...and the content of a component, struck out, leaves the dialog without the block', blockIds().join() === 'link,spacing,properties' );
builder._closeDialog();
doc = open();
doc.model.blocks[1].cols[0].stackAlign = 'end';
builder._doc.model.blocks[3].stack = builder._stackFor( builder._registry, 'sections', null );
builder._renderEditor();
check( 'what is said of a frame in the preview opens its dialog on the block the table has put the control in: the loop of a section and the alignment of a column, with the table changed to have them in the surface', ( function() {

	const original = builder.BLOCKS;

	builder.BLOCKS = tableMoved( tableMoved( tableWith(), 'section', 'stack', 'surface' ), 'column', 'stackAlign', 'surface' );

	try {
		preview().children[3].querySelector('.builder-frame-head .builder-status-item.is-loop').click();

		const loop = openBlock() === 'surface' && dialogText('builder-dialog-kind') === 'Section';

		builder._closeDialog();
		alignOf('1.0')[0].click();

		return loop && openBlock() === 'surface' && dialogText('builder-dialog-kind') === 'Column of "services"';
	} finally {
		builder.BLOCKS = original;
	}
} )() );
builder._closeDialog();
// The keys the controls of a block are marked with, in the order they stand in the body
const keysInOrder = function( id ) { return blockOf( id ).all().filter( function( el ) { return el.dataset.key !== undefined } ).map( function( el ) { return el.dataset.key } ) };
openWith( [ 0 ] );
const straightKeys = keysInOrder('properties');
builder._closeDialog();
const turned = tableWith();
turned.section.filter( function( block ) { return block.id === 'properties' } )[0].controls.reverse();
openWith( [ 0 ], turned );
check( 'the controls inside a block stand in the order the table lists them: the properties of a section, drawn with the block\'s controls reversed in a copy of the table, come out reversed - the id first',
	straightKeys.length > 2 && straightKeys[0] !== 'id' && keysInOrder('properties').join() === straightKeys.slice().reverse().join() && keysInOrder('properties')[0] === 'id' );
builder._closeDialog();
console.log( '\nThe question mark, the heads of closed blocks and the sentence that stands under no field' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
builder._openBlock('properties');
const helpsIn = function() { return dialogContent().querySelectorAll('.builder-help') };
const helpOf = function( control ) { return control.el.closest('.builder-row').querySelector('.builder-help') };
const buttonOfHelp = function( help ) { return help.querySelector('.builder-help-btn') };
const tipOfHelp = function( help ) { return help.querySelector('.builder-help-tip') };
check( 'a sentence a field has to say is behind a question mark in the row of the field, or in the head of the box of the sides - a button named after the field and the tip it describes, which is a tooltip: hidden at rest, an id of its own, and the button points at it by aria-describedby and at nothing else', helpsIn().length > 0 && helpsIn().every( function( help ) {
	return buttonOfHelp( help ).localName === 'button' && buttonOfHelp( help ).type === 'button' && buttonOfHelp( help ).textContent === '?' && tipOfHelp( help ).getAttribute('role') === 'tooltip' && tipOfHelp( help ).hidden === true
		&& tipOfHelp( help ).id !== '' && buttonOfHelp( help ).getAttribute('aria-describedby') === tipOfHelp( help ).id && tipOfHelp( help ).textContent !== '' && ( help.closest('.builder-row') !== null || help.closest('.builder-box-head') !== null ) && help.closest('.builder-block-body') !== null;
} ) && new Set( helpsIn().map( function( help ) { return tipOfHelp( help ).id } ) ).size === helpsIn().length
	&& buttonOfHelp( helpOf( controlOf( blockOf('properties'), 'Class of the component' ) ) ).getAttribute('aria-label') === word('label/help-of').replace( '%s', 'Class of the component' ) );
check( '...it opens where the button has the focus and where the pointer is over it, and closes where either goes - the pointer does not close it while the focus is there -, one click opens it and the next closes it, and Escape closes the tip and leaves the dialog open', ( function() {

	const help = helpOf( controlOf( blockOf('properties'), 'Class of the component' ) );
	const button = buttonOfHelp( help );
	const tip = tipOfHelp( help );
	const shown = function() { return tip.hidden === false };
	const states = [];

	button.dispatch('focus');
	states.push( shown() );
	button.dispatch('blur');
	states.push( shown() );
	help.dispatch('mouseenter');
	states.push( shown() );
	help.dispatch('mouseleave');
	states.push( shown() );
	button.click();
	states.push( shown() );
	button.click();
	states.push( shown() );
	button.click();
	button.focus();
	help.dispatch('mouseleave');
	states.push( shown() );

	const escape = pressKey( button, 'Escape' );

	states.push( shown() );

	return states.join() === 'true,false,true,false,true,false,true,false' && escape.defaultPrevented === true && dialogBox().open === true && pressKey( button, 'Escape' ).defaultPrevented === false;
} )() );
check( '...a field with nothing to say has no mark - the sentences are behind the marks and under nothing: a dialog of an element has no hint under a field and no small print, whichever element it is - what stands under the upload of a picture is what became of it, not a hint', ( function() {

	const none = function( at ) {

		builder._openSettings( at );

		const clean = dialogContent().querySelectorAll('.nino-admin-hint').filter( function( el ) { return el.classList.contains('builder-picture-message') === false } ).length === 0 && dialogContent().querySelectorAll('small').length === 0
			&& dialogContent().querySelectorAll('.builder-help').length > 0;

		builder._closeDialog();

		return clean;
	};

	return tipOf( controlOf( blockOf('properties'), 'HTML tag' ) ) === null && [ [ 0 ], [ 0, 0 ], [ 0, 0, 0 ], [ 0, 0, 2 ], [ 1, 1, 0 ], [ 1, 1 ] ].every( none );
} )() );

doc = open();
builder._doc.model.blocks.push( builder._newSection( builder._doc.model ) );
builder._doc.model.blocks[4].cols.push( builder._newCol() );
builder._renderEditor();
builder._openSettings( [ 4 ] );
check( 'the heads of a section that was just made say nothing - its blocks are on their defaults, and a closed block says only what deviates from them: the head stands there, muted, with its name', blockIds().every( function( id ) { return saysOf( id ).textContent === '' && saysOf( id ).hidden === true && headOf( id ).querySelector('.builder-block-name').textContent !== '' } ) );
builder._closeDialog();
builder._openSettings( [ 4, 0 ] );
check( '...and so do those of a column that was just made, and of a component the editor makes new - the text of a column of its own, which has nothing but the key it was given', blockIds().every( function( id ) { return saysOf( id ).textContent === '' } ) );
builder._closeDialog();

console.log( '\nThe kinds of input of the attributes of a component' );

// A component of a feature that has one attribute of each kind the registry can ask for: the registry of the kernel does not carry them all, and the panel knows the types and the hints, not the components
doc = open();
builder._renderEditor();
builder._registry.components['stub-kinds'] = {
	label : 'Stub kinds', source : 'none', loop : true, preview : 'block', link : true, by : 'hello',
	attributes : {
		mode		: { type : 'select', label : 'Mode', hint : 'How it works.', input : 'segment', options : [ '', 'one', 'two' ], default : '' },
		place		: { type : 'select', label : 'Place', hint : '', input : 'icons', options : [ '', 'start', 'center', 'end' ], default : '' },
		pick		: { type : 'select', label : 'Pick', hint : '', options : [ '', 'alpha', 'beta' ], default : '' },
		forced	: { type : 'select', label : 'Forced', hint : '', input : 'select', options : [ '', 'x', 'y' ], default : '' },
		flag		: { type : 'bool', label : 'Flag', hint : 'Small.', input : 'checkbox', default : false },
		lever		: { type : 'bool', label : 'Lever', hint : '', default : false },
		tone		: { type : 'variant', label : 'Tone', hint : '', options : [ '', 'warm', 'cold-snap' ], default : '' },
		where		: { type : 'variant', label : 'Where', hint : '', input : 'icons', options : [ '', 'left', 'center', 'right' ], default : '' },
		look		: { type : 'variant', label : 'Look', hint : '', input : 'select', options : [ '', 'a', 'b' ], default : '' },
	},
	defaults : { mode : '', place : '', pick : '', forced : '', flag : '0', lever : '0', tone : '', where : '', look : '', href : '', target : '', 'class' : '' },
};
builder._doc.model.blocks[0].cols[0].components.push( { name : 'stub-kinds', source : '', text : null, attributes : { mode : '', place : '', pick : '', forced : '', flag : '0', lever : '0', tone : '', where : '', look : '', href : '', target : '', 'class' : '' } } );
builder._lastBlock = {};
builder._openSettings( [ 0, 0, 3 ] );

const kindsAt = [ 0, 0, 3 ];
const kindsOf = function() { return builder._get( builder._doc.model, kindsAt ).attributes };
const kinds = function() { return blockOf('properties') };
check( 'a component that has no source and attributes of every kind has the blocks it has something for - link, spacing and properties - and no content', blockIds().join() === 'link,spacing,properties' && openBlock() === 'link' );
check( 'Properties: the variants first - a row of buttons unless the schema says another way, one to a line, the words of the panel for its values and none for the empty one, the icons of an alignment where the schema asks for them, a select where it asks for one - and then a field for each of the others, by its type and the input the schema asks for: a choice of a few as a row of buttons (segment), the places of a stack as a row of icons (icons), the rest of the choices as a select (select, which is what none says as well), a flag as a switch or, where the schema says so, as a plain checkbox',
	JSON.stringify( labelList( kinds() ) ) === JSON.stringify( [ 'Tone', 'Where', 'Look', 'Mode', 'Place', 'Pick', 'Forced', 'Flag', 'Lever', 'Class of the component' ] )
	&& controlsIn( kinds() ).map( function( control ) { return control.kind } ).join() === 'group,group,select,group,group,select,select,box,switch,text' );
check( '...a segment has the words of its values - the panel\'s where it has them, the value made into words where it has not, none for the empty one - and the icons of a stack the icons of the three places, with the word of each for its title; a select that is no segment has its values as they are; a variant has Default for none', segmentOf( kinds(), 'Mode' ) === ':none,one:One,two:Two'
	&& rowOf( kinds(), 'Place' ) === ':none:,start:Start:stack-start,center:Center:stack-center,end:End:stack-end' && optionsOf( kinds(), 'Pick' ) === '=none,alpha=alpha,beta=beta' && optionsOf( kinds(), 'Forced' ) === '=none,x=x,y=y'
	&& segmentOf( kinds(), 'Tone' ) === ':Default,warm:Warm,cold-snap:Cold snap' && rowOf( kinds(), 'Where' ) === ':Default:,left:Left:align-left,center:Center:align-center,right:Right:align-right' && optionsOf( kinds(), 'Look' ) === '=Default,a=a,b=b' );
check( '...a sentence is behind the question mark of the field it belongs to - a segment\'s as well as a checkbox\'s - and a field that has none has no mark', tipOf( controlOf( kinds(), 'Mode' ) ) === 'How it works.' && tipOf( controlOf( kinds(), 'Flag' ) ) === 'Small.' && controlOf( kinds(), 'Flag' ).el.closest('label').classList.contains('builder-check') === true
	&& controlOf( kinds(), 'Lever' ).el.closest('.nino-admin-switch') !== null && tipOf( controlOf( kinds(), 'Lever' ) ) === null && tipOf( controlOf( kinds(), 'Place' ) ) === null && kinds().querySelector('.nino-admin-hint') === null && kinds().querySelector('small') === null );
check( '...each kind writes what a person chose into the attribute, as the call has it: the word of a button, of an icon or of an option, "1" for a flag that is on and "0" for one that is off - the checkbox and the switch alike, and the variants the word chosen', ( function() {

	change( kinds(), 'Mode', 'two' );
	change( kinds(), 'Place', 'end' );
	change( kinds(), 'Pick', 'beta' );
	change( kinds(), 'Forced', 'x' );
	change( kinds(), 'Flag', true );
	change( kinds(), 'Lever', true );

	const on = [ kindsOf().mode, kindsOf().place, kindsOf().pick, kindsOf().forced, kindsOf().flag, kindsOf().lever ].join();

	change( kinds(), 'Flag', false );
	change( kinds(), 'Lever', false );
	change( kinds(), 'Mode', '' );
	change( kinds(), 'Tone', 'cold-snap' );
	change( kinds(), 'Where', 'center' );
	change( kinds(), 'Look', 'b' );

	return on === 'two,end,beta,x,1,1' && kindsOf().flag === '0' && kindsOf().lever === '0' && kindsOf().mode === '' && valueAt( kinds(), 'Mode' ) === '' && valueAt( kinds(), 'Place' ) === 'end'
		&& [ kindsOf().tone, kindsOf().where, kindsOf().look ].join() === 'cold-snap,center,b' && valueAt( kinds(), 'Tone' ) === 'cold-snap';
} )() );
check( 'Link: only where the schema says the component is a link - and the component without the key has none, with the properties it has: the address and the window it opens in are the attributes of the call that every link has, and are written as href and target', JSON.stringify( labelList( blockOf('link') ) ) === JSON.stringify( [ 'Opens in' ] )
	&& ( change( blockOf('link'), 'Opens in', '_blank' ), kindsOf().target === '_blank' ) && ( function() {
		delete builder._registry.components['stub-kinds'].link;
		builder._openSettings( kindsAt );
		return blockIds().join() === 'spacing,properties';
	} )() );
delete builder._registry.components['stub-kinds'];
builder._closeDialog();

check( 'an attribute of a schema stands in the block its schema names or its kind puts it in: one that names the tab of the background is of the surface, an address and the window of a link and whether all of a surface is the link are of the link, every other is a property - so the blocks are made of what the schema holds', ( function() {

	const names = function( attributes, surface ) {
		builder._registry.components['stub-only'] = { label : 'Stub only', source : 'none', loop : true, preview : 'block', attributes : attributes, surface : surface, defaults : { 'class' : '' } };
		builder._doc.model.blocks[0].cols[0].components[3] = { name : 'stub-only', source : '', text : null, attributes : { 'class' : '' } };
		builder._openSettings( kindsAt );
		return blockIds().map( function( id ) { return id+ ':'+ labelList( blockOf( id ) ).join('|') } ).join(' / ');
	};
	const found = [
		names( { tone : { type : 'variant', label : 'Tone', hint : '', options : [ '', 'warm' ], default : '' } } ),
		names( { go : { type : 'href', label : 'Go', hint : '', default : '' }, how : { type : 'select', label : 'How', hint : '', options : [ '', '_blank' ], default : '' }, target : { type : 'select', label : 'Opens', hint : '', options : [ '', '_blank' ], default : '' }, linked : { type : 'bool', label : 'Linked', hint : '', default : false } } ),
		names( { tint : { type : 'select', label : 'Tint', hint : '', tab : 'background', options : [ '', 'alt', 'tint' ], default : '' }, mode : { type : 'select', label : 'Mode', hint : '', options : [ '', 'one' ], default : '' } } ),
	];

	delete builder._registry.components['stub-only'];
	builder._closeDialog();

	return found[0] === 'spacing:'+ [ sideLabel( 'above', 'outside' ), sideLabel( 'above', 'inside' ), sideLabel( 'left', 'inside' ), sideLabel( 'right', 'inside' ), sideLabel( 'below', 'inside' ), sideLabel( 'below', 'outside' ) ].join('|')+ ' / properties:Tone|Class of the component'
		&& found[1].indexOf('link:') === 0 && found[1].indexOf('link:Opens|Linked') !== -1 && found[1].indexOf('properties:How|Class of the component') !== -1
		&& found[2].indexOf('surface:Tint') === 0 && found[2].indexOf('properties:Mode|Class of the component') !== -1;
} )() );

console.log( '\nThe dialog of a source' );

const sourceButton = function( pane ) { return pane.querySelector('.builder-source button') };
const pickItems = function() { return pickerPane('pick').querySelectorAll('.builder-pick-item') };

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
sourceButton( dialogContent() ).click();
check( 'the source button opens a second dialog above the one of the component - a smaller one of its own, the first stays open - with three tabs for a text: Choose a value, Create new and Static', pickerBox().open === true && dialogBox().open === true && pickerBox().classList.contains('builder-picker-dialog')
	&& pickerBox().querySelector('.nino-admin-dialog-title').textContent === 'Source' && JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ) ) === '[["pick","Choose a value"],["new","Create new"],["fixed","Static"]]' && shownTab( pickerStrip(), 'builder-picker' ) === 'pick' );
check( '...its tabs are tabs and its panes tab panels, as the other dialog\'s are', pickerStrip().querySelectorAll('[role="tab"]').every( function( tab ) {
	const pane = screen.getElementById( tab.getAttribute('aria-controls') );
	return pane.getAttribute('role') === 'tabpanel' && pane.getAttribute('aria-labelledby') === tab.id;
} ) && pickerStrip().querySelectorAll('[role="tab"]').map( function( tab ) { return tab.tabIndex } ).join() === '0,-1,-1' );
check( 'Choose a value: what the source is now, a search box that starts with the keys of the section, and the keys of the project it finds - the one that is now the source marked', ( function() {
	const now = pickerPane('pick').querySelector('.builder-source-now');
	const search = pickerPane('pick').querySelector('input[type="search"]');
	return now.querySelector('span').textContent === 'Current:' && now.querySelector('.builder-source-current').textContent === '/template/page-home/hero/title' && search.value === '/template/page-home/hero/' && search.getAttribute('aria-label') === 'Find a key by its name or its text'
		&& pickItems().map( function( item ) { return item.querySelector('.builder-pick-name').textContent+ '='+ item.querySelector('.builder-pick-sub').textContent+ ( item.classList.contains('is-current') ? '*' : '' ) } ).join() === '/template/page-home/hero/title=Welcome*,/template/page-home/hero/subtitle=We build';
} )() );
check( '...the search narrows the list by the name and the text of a key - and says when there is none', ( function() {
	const search = pickerPane('pick').querySelector('input[type="search"]');
	search.value = 'build';
	search.dispatch('input');
	const found = pickItems().length;
	search.value = 'nothing like it';
	search.dispatch('input');
	const none = pickItems().length === 0 && pickerPane('pick').querySelector('.builder-picker-list').textContent === 'Nothing found.';
	search.value = '';
	search.dispatch('input');
	return found === 1 && none && pickItems().length === 2;
} )() );
pickItems()[1].click();
check( '...a key that is chosen is the source of the component, with no instruction to make it - and the dialog of the source closes, the one below it does not and shows the new source', componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/subtitle' && componentAt( [ 0, 0, 0 ] ).create === undefined && componentAt( [ 0, 0, 0 ] ).text === null
	&& pickerBox().open === false && dialogBox().open === true && dialogContent().querySelector('.builder-source-current').textContent === '/template/page-home/hero/subtitle' && changes === 1 );

sourceButton( dialogContent() ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
const fresh = function( selector ) { return pickerPane('new').querySelector( selector ) };
check( 'Create new: a new key, named by the kind of the component and numbered where the section has the name, with the key it makes shown and no size - a key has none', shownTab( pickerStrip(), 'builder-picker' ) === 'new' && fresh('.builder-new-title').textContent === 'New key' && fresh('.builder-new-name').value === 'title-2'
	&& fresh('.builder-new-uri').textContent === '/template/page-home/hero/title-2' && fresh('.builder-new-message').textContent === '' && fresh('.builder-new-use').disabled === false && fresh('.builder-new-use').textContent === 'Use it' && fresh('.builder-new-size').hidden === true );
const typed = function( text ) { fresh('.builder-new-name').value = text; fresh('.builder-new-name').dispatch('input') };
typed('Bad Name');
const bad = fresh('.builder-new-message').textContent === words['/_admin/builder/source/name-grammar'] && fresh('.builder-new-use').disabled === true;
typed('title');
check( '...a name that will not do says why - the grammar, a name the section has - and the button is off', bad && fresh('.builder-new-message').textContent === words['/_admin/builder/source/name-taken'] && fresh('.builder-new-use').disabled === true );
typed('cta');
check( '...a name that will do shows the key it makes', fresh('.builder-new-uri').textContent === '/template/page-home/hero/cta' && fresh('.builder-new-message').textContent === '' && fresh('.builder-new-use').disabled === false );
fresh('.builder-new-use').click();
check( '...and using it makes the key the source, with a mark that says no more than that the key is made - the dialog closes', componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/cta' && JSON.stringify( componentAt( [ 0, 0, 0 ] ).create ) === '{}' && pickerBox().open === false
	&& dialogContent().querySelector('.builder-source-current').textContent === '/template/page-home/hero/cta' );
check( '...and no text is typed for it in any language: the label of the component is no text of the key', componentAt( [ 0, 0, 0 ] ).values === undefined );

sourceButton( dialogContent() ).click();
buttonOf( pickerStrip(), 'Static' ).click();
check( 'Static: a value that stands in the template - a field with a sentence behind its question mark and a button to use it', shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && labelsIn( pickerPane('fixed') ) === 'Fixed value' && fixedField().value === '' && buttonOf( pickerPane('fixed'), 'Use it' ).textContent === 'Use it'
	&& tipOf( controlOf( pickerPane('fixed'), 'Fixed value' ) ) === words['/_admin/builder/hint/fixed'] );
fixedField().value = 'Mehr';
const used = pressKey( fixedField(), 'Enter' );
check( '...the value is the text of the call and no source - Enter uses it as the button does, and goes no further: the key is not for the button the dialog gives the focus back to -, and the row shows it in quotes', used.defaultPrevented === true && componentAt( [ 0, 0, 0 ] ).text === 'Mehr' && componentAt( [ 0, 0, 0 ] ).source === '' && componentAt( [ 0, 0, 0 ] ).create === undefined && pickerBox().open === false
	&& dialogContent().querySelector('.builder-source-current').textContent === '“Mehr”' );
sourceButton( dialogContent() ).click();
check( '...and a source that is a fixed value opens on the tab for it, with the value in the field', shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && fixedField().value === 'Mehr' );
screen.getElementById('builder-picker-close').click();
check( '...closing the dialog of the source gives no answer', pickerBox().open === false && dialogBox().open === true && componentAt( [ 0, 0, 0 ] ).text === 'Mehr' && changes === 3 );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
buttonOf( dialogContent(), 'Source' ).click();
check( 'the picture behind a section is chosen in the same dialog, with the tabs of a picture: Choose a value and Create new - no Static', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ) ) === '[["pick","Choose a value"],["new","Create new"]]'
	&& pickerPane('pick').querySelector('.builder-label').textContent === 'Image slot' && pickerPane('pick').querySelector('input[type="search"]').getAttribute('aria-label') === 'Find an image slot' && pickerPane('pick').querySelector('.builder-source-current').textContent === '/template/page-home/hero/background' );
check( '...the slots start with those of the section, each with the picture it has as a small thumbnail - none where it has none - and the word for an empty one', ( function() {
	const search = pickerPane('pick').querySelector('input[type="search"]');
	const first = pickItems().length === 1 && pickItems()[0].querySelector('img').getAttribute('src') === '/uploads/hero.1600x900.jpg' && pickItems()[0].querySelector('img').getAttribute('alt') === '' && pickItems()[0].classList.contains('is-current');
	search.value = '';
	search.dispatch('input');
	return first && pickItems().length === 2 && pickItems()[1].querySelector('img') === null && pickItems()[1].querySelector('.builder-pick-sub').textContent === 'Logo – no picture yet';
} )() );
pickItems()[1].click();
check( '...a slot that is chosen is the picture, and the focus stays - the row, the preview and the focus below it follow', builder._doc.model.blocks[0].background.slot === '/project/logo/header/image' && builder._doc.model.blocks[0].background.focus === 5 && builder._doc.model.blocks[0].background.create === undefined && pickerBox().open === false
	&& dialogContent().querySelector('.builder-source-current').textContent === '/project/logo/header/image' );

doc = open();
builder._openSettings( [ 0 ] );
buttonOf( dialogContent(), 'Source' ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
check( 'a new slot has a name of the section\'s kind, and a size - 1600 by 900 to start with', fresh('.builder-new-title').textContent === 'New image slot' && fresh('.builder-new-name').value === 'background-2' && fresh('.builder-new-uri').textContent === '/template/page-home/hero/background-2'
	&& fresh('.builder-new-size').hidden === false && fresh('.builder-new-width').value === '1600' && fresh('.builder-new-height').value === '900' && fresh('.builder-new-width-label').textContent === words['/_admin/common/label/width'] );
fresh('.builder-new-width').value = '800';
fresh('.builder-new-width').dispatch('input');
fresh('.builder-new-height').value = '400';
fresh('.builder-new-height').dispatch('input');
fresh('.builder-new-use').click();
check( '...using it makes the slot the picture, with what it is made with: the label and the size', builder._doc.model.blocks[0].background.slot === '/template/page-home/hero/background-2' && builder._doc.model.blocks[0].background.focus === 5
	&& JSON.stringify( builder._doc.model.blocks[0].background.create ) === '{"label":"Background","width":800,"height":400}' && pickerBox().open === false );
check( 'a slot is no slot where a segment of it starts with a digit, and the dialog says so before the server does: a section id of 2col blames the section - a name of 2x blames the name, not the section - and a key may have such a name', ( function() {
	const shown = function( id, name, kind ) {
		doc = open();
		builder._doc.model.blocks[0].id = id;
		builder._openSettings( [ 0 ] );
		buttonOf( dialogContent(), 'Source' ).click();
		buttonOf( pickerStrip(), 'Create new' ).click();
		if( kind === 'key' ) {
			pickerBox().open = false;
			builder._doc.model.blocks[0].cols[0].components.length = 1;
			builder._openSettings( [ 0, 0, 0 ] );
			sourceButton( dialogContent() ).click();
			buttonOf( pickerStrip(), 'Create new' ).click();
		}
		typed( name );
		return { message : fresh('.builder-new-message').textContent, disabled : fresh('.builder-new-use').disabled };
	};
	const digitSection = shown( '2col', 'background', 'slot' );
	const digitName = shown( 'intro', '2x', 'slot' );
	const keyDigitName = shown( 'intro', '2x', 'key' );
	return digitSection.message === words['/_admin/builder/source/name-slot'] && digitSection.disabled === true && digitName.message === words['/_admin/builder/source/name-slot-name'] && digitName.disabled === true && keyDigitName.message === '' && keyDigitName.disabled === false;
} )() );
check( 'the slots of the project come with the picture they have for the thumbnail: the url, and none where there is no picture', ( function() {
	doc = open();
	builder._openSettings( [ 0 ] );
	buttonOf( dialogContent(), 'Source' ).click();
	pickerPane('pick').querySelector('input[type="search"]').value = '';
	pickerPane('pick').querySelector('input[type="search"]').dispatch('input');
	return pickItems().map( function( item ) { return item.querySelector('img') === null ? '' : item.querySelector('img').getAttribute('src') } ).join() === '/uploads/hero.1600x900.jpg,';
} )() );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1, 0 ] );
sourceButton( dialogContent() ).click();
check( 'in a loop the source of a picture is a field of the element first - the fields of the type that hold a picture, and none - then the slots of the project', ( function() {
	const field = controlOf( pickerPane('pick'), 'Field of the element' );
	return pickerPane('pick').querySelector('.builder-source-current').textContent === 'image' && field.el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=none,image=image' && valueAt( pickerPane('pick'), 'Field of the element' ) === 'image'
		&& tipOf( field ) === words['/_admin/builder/hint/field'] && pickerPane('pick').querySelector('.builder-label').textContent === 'Image slot' && pickItems().length === 0;
} )() );
change( pickerPane('pick'), 'Field of the element', '' );
check( '...and the choice of none is the choice of no source', componentAt( [ 1, 1, 0 ] ).source === '' && pickerBox().open === false && dialogContent().querySelector('.builder-source-current').textContent === 'none' );
builder._openSettings( [ 1, 1, 1 ] );
sourceButton( dialogContent() ).click();
check( 'the source of a text in a loop is a field of the element that holds a text, the id of the element, or a key - the keys of the section first', controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value } ).join() === ',title,summary,.id'
	&& valueAt( pickerPane('pick'), 'Field of the element' ) === 'title' && pickItems().length === 2 && ( change( pickerPane('pick'), 'Field of the element', 'summary' ), componentAt( [ 1, 1, 1 ] ).source === 'summary' ) );
builder._openSettings( [ 1, 1, 3 ] );
check( 'the button in a loop has the source it has and the fixed text it has: the dialog opens on the fixed value', dialogContent().querySelector('.builder-source-current').textContent === '.uri' && ( sourceButton( dialogContent() ).click(), shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && fixedField().value === 'Mehr' ) );
builder._openSettings( [ 1, 1, 3 ] );
buttonOf( blockOf('link'), 'Source' ).click();
check( 'the link of a button in a loop may be the uri or the id of the element - or a fixed address, which is a value that stands in the template', controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value } ).join() === ',title,summary,.id,.uri'
	&& JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","fixed"]' );
buttonOf( pickerStrip(), 'Static' ).click();
fixedField().value = '/contact';
buttonOf( pickerPane('fixed'), 'Use it' ).click();
check( '...an attribute has no key to make: it has no Create new, and what is used as a fixed value is written as it is', componentAt( [ 1, 1, 3 ] ).attributes.href === '/contact' && pickerBox().open === false && blockOf('link').querySelector('.builder-source-current').textContent === '“/contact”' );

doc = open();
builder._openSettings( [ 0, 0, 2 ] );
buttonOf( blockOf('link'), 'Source' ).click();
pickItems();
check( 'the link of a button outside a loop is a key; typed addresses are shown as the fixed values they are', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","fixed"]' && pickerPane('pick').querySelector('.builder-source-current').textContent === '/_nino/webpage/contact/uri'
	&& pickerPane('pick').querySelector('.builder-label').textContent === 'Text key' );

doc = open();
builder._keys = [];
builder._keysAnswered = true;
builder._openSettings( [ 0, 0, 0 ] );
sourceButton( dialogContent() ).click();
check( 'where the keys of the project may not be read the key is typed: a field with its sentence behind the question mark, and a key that will do is the answer', ( function() {
	const field = controlOf( pickerPane('pick'), 'Text key' );
	field.el.value = 'nothing';
	field.el.dispatch('input');
	field.el.dispatch('change');
	const refused = componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/title';
	field.el.value = '/template/page-home/hero/other';
	field.el.dispatch('change');
	return tipOf( field ) === words['/_admin/builder/hint/typed'] && refused && componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/other' && pickerBox().open === false;
} )() );
builder._keysAnswered = false;

console.log( '\nThe link: the pages of the site, a page chosen by its address, an address made a key' );

// The pages the list of keys tells of - the routes that are pages -, as the answer of text/keys has them
const sitePages = function() { return [
	{ uri : '/contact', httpUri : '/contact-us', template : 'page-contact', category : 'page-contact', templateName : 'Contact' },
	{ uri : '/home', httpUri : '/', template : 'page-home', category : 'page-home', templateName : null },
] };
const pageKey = function( at ) { return '/_nino/webpage'+ sitePages()[at].uri+ '/uri' };

doc = open();
builder._renderEditor();
requests = [];
builder._keysAsked = false;
builder._keysRead = false;
builder._pages = [];
builder._loadKeys();

const keysAsked = requests[requests.length - 1];

keysAsked.callback( 200, { keys : known(), pages : sitePages().concat( [ 'nothing', { uri : '' }, { httpUri : '/x' } ] ) } );
check( 'the list of keys comes with the pages of the site, and the panel keeps what is a page - one that has a uri - and takes the answer for what it is: this account may read the keys, and may make one', keysAsked.action === 'text/keys' && builder._keysRead === true
	&& JSON.stringify( builder._pages ) === JSON.stringify( sitePages() ) );
requests = [];
builder._keysAsked = false;
builder._loadKeys();
requests[requests.length - 1].callback( 200, { keys : known() } );
check( '...an answer without the pages - where the Routes panel is not there - has none, and one that is a refusal takes nothing away', builder._pages.length === 0 && ( builder._keysAsked = false, builder._loadKeys(), requests[requests.length - 1].callback( 403, { error : 'no' } ), builder._keysRead === true && builder._keys.length === 2 ) );
builder._pages = sitePages();

builder._openSettings( [ 0, 0, 2 ] );
buttonOf( blockOf('link'), 'Source' ).click();
check( 'the dialog of the address of a link has a way for each of the four: Choose a value, Internal page, Create new and Static - and opens on the page the link leads to, where it leads to one', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","internal","new","fixed"]'
	&& JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[1] } ) ) === JSON.stringify( [ words['/_admin/builder/source/tab-pick'], words['/_admin/builder/source/tab-internal'], words['/_admin/builder/source/tab-new'], words['/_admin/builder/source/tab-fixed'] ] )
	&& shownTab( pickerStrip(), 'builder-picker' ) === 'internal' && builder._pageOf( componentAt( [ 0, 0, 2 ] ).attributes.href ) !== null );
buttonOf( pickerStrip(), words['/_admin/builder/source/tab-pick'] ).click();
check( '...Choose a value lists the addresses of the pages in front of the texts - each the key that holds the address, and the address of the page with the template it shows - with the page the link leads to marked, and starts with no search, so that they are in sight', ( function() {
	const search = pickerPane('pick').querySelector('input[type="search"]');
	return search.value === '' && pickItems().map( function( item ) { return item.querySelector('.builder-pick-name').textContent+ '='+ item.querySelector('.builder-pick-sub').textContent+ ( item.classList.contains('is-current') ? '*' : '' ) } ).join()
		=== pageKey( 0 )+ '=/contact-us - Contact*,'+ pageKey( 1 )+ '=/ - page-home,/template/page-home/hero/title=Welcome,/template/page-home/hero/subtitle=We build';
} )() );
buttonOf( pickerStrip(), words['/_admin/builder/source/tab-internal'] ).click();
check( '...Internal page: a select of the pages, by their address and the template they show, with none in front - on the page the link leads to - and its sentence behind the question mark', ( function() {
	const select = controlOf( pickerPane('internal'), words['/_admin/builder/source/page'] );
	return select.kind === 'select' && select.el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '='+ words['/_admin/builder/label/none']+ ','+ pageKey( 0 )+ '=/contact-us - Contact,'+ pageKey( 1 )+ '=/ - page-home' && valueAt( pickerPane('internal'), words['/_admin/builder/source/page'] ) === pageKey( 0 )
		&& tipOf( select ) === words['/_admin/builder/hint/page'];
} )() );
changes = 0;
change( pickerPane('internal'), words['/_admin/builder/source/page'], '' );
check( '...none is no choice: the link is as it was, and the dialog stays', componentAt( [ 0, 0, 2 ] ).attributes.href === pageKey( 0 ) && pickerBox().open === true && changes === 0 );
change( pickerPane('internal'), words['/_admin/builder/source/page'], pageKey( 1 ) );
check( '...a page that is chosen is the address of the link - the key that holds its address, which the Writer makes the fill of it, so that the link goes to the page in the language the visitor reads - and the dialog closes, the form behind it shows it', componentAt( [ 0, 0, 2 ] ).attributes.href === pageKey( 1 )
	&& pickerBox().open === false && blockOf('link').querySelector('.builder-source-current').textContent === pageKey( 1 ) && changes === 1 && componentAt( [ 0, 0, 2 ] ).create === undefined );

buttonOf( blockOf('link'), 'Source' ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
requests = [];
check( 'Create new: a new key for the address, named by the attribute and numbered where the section has the name, with the key it makes shown - and a field for the address, which is what the key holds: the button waits for one', shownTab( pickerStrip(), 'builder-picker' ) === 'new' && fresh('.builder-new-title').textContent === 'New key'
	&& fresh('.builder-new-uri').textContent === '/template/page-home/hero/'+ fresh('.builder-new-name').value && labelsIn( pickerPane('new') ).indexOf('Name,Address') === 0 && fresh('.builder-new-use').disabled === true && fresh('.builder-new-message').textContent === words['/_admin/builder/source/name-address'] );

const addressField = function() { return controlOf( pickerPane('new'), words['/_admin/builder/source/address'] ) };
const nameOfNew = fresh('.builder-new-name').value;

addressField().el.value = '/shop';
addressField().el.dispatch('input');
check( '...an address typed lets it be used - and none of the checks of a name is left out: a name that will not do is refused as it is for a text', fresh('.builder-new-message').textContent === '' && fresh('.builder-new-use').disabled === false
	&& ( typed('Bad Name'), fresh('.builder-new-use').disabled === true && fresh('.builder-new-message').textContent === words['/_admin/builder/source/name-grammar'] ) && ( typed( nameOfNew ), fresh('.builder-new-use').disabled === false ) );
fresh('.builder-new-use').click();
check( '...using it makes the key at once, with the Text panel: a key every language shares, in plain text, holding the address - the form waits for the answer and can be used no more in the meantime, and the link is not changed yet', requests.length === 1 && requests[0].action === 'keys/create'
	&& JSON.stringify( requests[0].payload ) === JSON.stringify( { key : '/template/page-home/hero/'+ nameOfNew, global : true, format : 'plain', value : '/shop' } ) && fresh('.builder-new-use').disabled === true && pickerBox().open === true && componentAt( [ 0, 0, 2 ] ).attributes.href === pageKey( 1 ) );
requests[0].callback( 409, { error : 'key already exists', code : 'keys_exists' } );
check( '...a refusal is said in the dialog, in the words of the panel for a key that could not be made, and nothing is lost: the name and the address are as they were, the button is there again, the link is as it was', fresh('.builder-new-message').textContent === '(409) /_admin/builder/error/key'
	&& fresh('.builder-new-use').disabled === false && fresh('.builder-new-name').value === nameOfNew && addressField().el.value === '/shop' && componentAt( [ 0, 0, 2 ] ).attributes.href === pageKey( 1 ) && pickerBox().open === true );
requests = [];
changes = 0;
fresh('.builder-new-use').click();
requests[0].callback( 200, { ok : true, key : '/template/page-home/hero/'+ nameOfNew } );
check( '...an answer of 200 makes the key the address of the link - with no mark that makes it with the document, it is made - and the dialog closes, and the keys of the project are asked for again, so that the key is among them', componentAt( [ 0, 0, 2 ] ).attributes.href === '/template/page-home/hero/'+ nameOfNew
	&& componentAt( [ 0, 0, 2 ] ).create === undefined && componentAt( [ 0, 0, 2 ] ).slots === undefined && pickerBox().open === false && changes === 1 && requests.length === 2 && requests[1].action === 'text/keys'
	&& blockOf('link').querySelector('.builder-source-current').textContent === '/template/page-home/hero/'+ nameOfNew );
requests = [];

doc = open();
builder._keysRead = false;
builder._pages = [];
builder._openSettings( [ 0, 0, 2 ] );
buttonOf( blockOf('link'), 'Source' ).click();
check( 'where the keys may not be read there is no Create new for a link - the Text panel would refuse the key - and where the site has no pages there is no Internal page: the ways are the two there were', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","fixed"]' );
builder._closeDialog();
builder._keysRead = true;
builder._pages = sitePages();

console.log( '\nThe dialog of a source as a dialog of the workbench could take it over' );

// The options are a plain object and nothing of the form that opened it: the dialog answers by the two functions it was given
doc = open();
const answer = [];
const plainOptions = function( more ) {
	return Object.assign( { kind : 'text', fixed : true, create : true, fields : null, value : '/template/page-home/hero/title', text : null, section : builder._doc.model.blocks[0], name : 'title', seed : 'Title',
		onPick : function( source, create ) { answer.push( [ source, create ] ) }, onFixed : function( text ) { answer.push( text ) } }, more || {} );
};
builder._sourceDialog( plainOptions() );
pickItems()[1].click();
buttonOf( pickerStrip(), 'Create new' ).click();
fresh('.builder-new-use').click();
buttonOf( pickerStrip(), 'Static' ).click();
fixedField().value = 'Hello';
buttonOf( pickerPane('fixed'), 'Use it' ).click();
check( 'a text: a key from the list, a new key - with a mark that says no more than that it is made: what it holds is what is typed for it -, a fixed value - the answers are the arguments of the two functions, and each closes the dialog', JSON.stringify( answer ) === '[["/template/page-home/hero/subtitle",null],["/template/page-home/hero/title-2",{}],"Hello"]' && pickerBox().open === false );
answer.length = 0;
builder._sourceDialog( plainOptions( { kind : 'image', fixed : false, name : 'background', seed : 'Background', value : '' } ) );
pickerPane('pick').querySelector('input[type="search"]').value = '';
pickerPane('pick').querySelector('input[type="search"]').dispatch('input');
pickItems()[0].click();
buttonOf( pickerStrip(), 'Create new' ).click();
fresh('.builder-new-use').click();
check( 'a picture: a slot from the list, a new slot with its label and its size - and no tab for a fixed value where the options say there is none', JSON.stringify( answer ) === '[["/template/page-home/hero/background",null],["/template/page-home/hero/background-2",{"label":"Background","width":1600,"height":900}]]'
	&& answer.length === 2 );
builder._sourceDialog( plainOptions( { create : false, fixed : false } ) );
check( 'a source that may not be made new or typed in has the list alone', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick"]' );
builder._sourceDialog( plainOptions( { fields : { title : { type : 'string' }, image : { type : 'image' } }, value : 'title', kind : 'href' } ) );
check( 'the fields of the type of a loop are what the options say they are: a link may be the id and the uri, a text the id', controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value } ).join() === ',title,.id,.uri' && valueAt( pickerPane('pick'), 'Field of the element' ) === 'title' );
builder._sourceDialog( plainOptions() );
builder._closeDialog();
check( 'the dialog of a source goes with the dialog it was opened from', pickerBox().open === false );
console.log( '\nThe editor of HTML+' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 2 ] );
check( 'a block of html has no form: it has the editor, which is a dialog of its own, titled as such, wide, with nothing in it to choose but the markup', dialogText('builder-dialog-kind') === words['/_admin/builder/html/title'] && dialogBox().classList.contains('is-wide') && dialogContent().querySelector('.builder-blocks') === null
	&& JSON.stringify( labelList( dialogContent() ) ) === JSON.stringify( [ 'The markup of the block' ] ) && dialogContent().querySelector('.builder-html-note').textContent === words['/_admin/builder/html/note'] );
const markup = function() { return dialogContent().querySelector('.builder-html-source') };
const applyButton = function() { return screen.getElementById('builder-dialog-actions').children[0] };
check( '...with the source of the block in it, and applying it sends that source to be read again', markup().value === builder._doc.model.blocks[2].source && applyButton().textContent === 'Apply' && ( applyButton().click(), requests.length === 1
	&& requests[0].action === 'builder/source' && requests[0].payload.model.blocks[2].source === builder._doc.model.blocks[2].source && requests[0].payload.model.blocks[2].edited === true && dialogBox().open === true ) );

doc = open();
builder._editBlock( null );
check( 'a new block of html starts as the skeleton of the template, which is no section, and is titled as a new one', dialogText('builder-dialog-kind') === words['/_admin/builder/html/title-new'] && markup().value === '<div>\n</div>' && markup().value === screen.getElementById('builder-tpl-skeleton').content.firstElementChild.outerHTML );
applyButton().click();
check( '...and is applied as a block of html without a reason, to be read again', requests.length === 1 && requests[0].payload.model.blocks[4].kind === 'html' && requests[0].payload.model.blocks[4].reason === null && requests[0].payload.model.blocks[4].edited === true );

doc = open();
builder._editBlock( [ 0 ] );
requests[0].callback( 200, { parts : [ { kind : 'section', block : 0, source : '<section id="hero"></section>' } ] } );
check( 'a section is edited as the markup the builder writes for it: the server says, and the note says what applying it does', markup().value === '<section id="hero"></section>' && dialogContent().querySelector('.builder-html-note').textContent === words['/_admin/builder/html/note-section'] );
applyButton().click();
requests[1].callback( 400, { code : 'builder_invalid', params : [ 'block 1 is neither a section nor a block of html' ] } );
check( '...and what the server refuses it for is said in the dialog, which stays open', dialogText('builder-dialog-problems').indexOf( 'block 1' ) !== -1 && screen.getElementById('builder-dialog-problems').hidden === false && dialogBox().open === true );
console.log( '\nThe editor of an [html] component' );

doc = open();
const content = { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>Hello <strong>world</strong></p>' };
builder._doc.model.blocks[0].cols[0].components.push( content );
const editors = [];
sandbox.Nino.admin.htmlEditor = { create : function( mount, value, maxlength, rows, format ) {
	const editor = { value : value, mount : mount, maxlength : maxlength, format : format, getValue : function() { return editor.value }, destroy : function() {} };
	editors.push( editor );
	return editor;
} };
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
check( 'the content of an [html] component is edited in the workbench\'s HTML editor, in the blocks format, with a limit above none (an editor of none trims every edit to nothing)', editors.length === 1
	&& editors[0].value === '<p>Hello <strong>world</strong></p>' && editors[0].format === 'blocks' && editors[0].maxlength > 0 && editors[0].maxlength === builder.CONTENT_MAX );
editors[0].value = '<p>Hello again</p>';
editors[0].mount.dispatch( 'input' );
check( '...and what is typed goes into the component, which is a change', content.content === '<p>Hello again</p>' && changes === 1 && builder._unsaved( builder._doc ) === true );
builder._closeDialog();
check( '...and closed after typing it stays what was typed', content.content === '<p>Hello again</p>' );

doc = open();
const plain = { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<h2>Heading</h2>' };
builder._doc.model.blocks[0].cols[0].components.push( plain );
builder._doc.saved = JSON.stringify( builder._doc.model );
editors.length = 0;
const changesBefore = changes;
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
editors[0].value = '<p>Heading</p>';
builder._closeDialog();
check( 'a dialog of an [html] component opened and closed without typing leaves the content byte for byte (the editor\'s normal form of <h2>Heading</h2> is another) and the document is not unsaved', plain.content === '<h2>Heading</h2>' && changes === changesBefore && builder._unsaved( builder._doc ) === false );
editors.length = 0;
builder._openSettings( [ 0, 0, builder._doc.model.blocks[0].cols[0].components.length - 1 ] );
editors[0].value = '<h2>Heading!</h2>';
editors[0].mount.dispatch( 'input' );
builder._closeDialog();
check( '...and one with typing writes the value, and that is a change', plain.content === '<h2>Heading!</h2>' && changes > changesBefore && builder._unsaved( builder._doc ) === true );
delete sandbox.Nino.admin.htmlEditor;

console.log( '\nThe link to the real page' );

doc = open();
const anchor = { hidden : true, href : '' };
builder._pageLink( anchor );
check( 'the link goes to the address the first route serves the page at (GET:// is /), not to the uri of the route\'s webpage (/home, which is none)', anchor.hidden === false && anchor.href === '/' );
builder._sel = [ 1, 0 ];
builder._pageLink( anchor );
check( '...with the id of the section the selection is in, as an anchor', anchor.href === '/#services' );
builder._doc.usedBy = [ { route : 'GET://legal/imprint', uri : '/legal/imprint' }, { route : 'POST://form', uri : '/form' } ];
builder._pageLink( anchor );
check( '...a page of a path is its path, and a route that is no GET is no page', anchor.href === '/legal/imprint#services' && builder._addresses( builder._doc.usedBy ).join() === '/legal/imprint' );
builder._doc.usedBy = [ { route : 'POST://form', uri : '/form' } ];
builder._pageLink( anchor );
check( '...and where no route answers a GET there is no link', anchor.hidden === true && builder._addresses( [ { route : 'GET://', uri : '/home' }, { route : 'GET://', uri : '/start' }, { route : 'GET://x', uri : '' } ] ).join() === '/,/x' );

console.log( '\nThe list and the copy of a template' );

doc = open();
builder._files = [ { file : 'page-home', name : 'Home', editable : true } ];
builder._duplicateTemplate( 'page-home' );
const copyName = function() { return controlOf( dialogContent(), 'Name' ) };
const problemsText = function() { return dialogText('builder-dialog-problems') };
const copyButton = function() { return screen.getElementById('builder-dialog-actions').children[0] };
check( 'a template is copied from the list: the dialog asks for the name of the new one, with the name of the old one in it, and sends the old file and the new name', ( function() {
	const asked = copyName().el.value;
	change( dialogContent(), 'Name', 'My copy' );
	requests = [];
	copyButton().click();
	return dialogText('builder-dialog-kind') === 'Duplicate' && asked === 'Copy of Home' && texts( screen.getElementById('builder-dialog-actions').children ) === 'Duplicate,Cancel' && dialogContent().querySelector('.builder-blocks') === null
		&& requests.length === 1 && requests[0].action === 'builder/duplicate' && JSON.stringify( requests[0].payload ) === '{"file":"page-home","name":"My copy"}' && dialogBox().open === true;
} )() );
check( '...keys and image slots that are in the way are named in the dialog after the sentence of the code, in the panel\'s own words: the server answers the bare uris, the keys and then the slots', ( function() {
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ [ '/template/page-home-copy/hero/title', '/template/page-home-copy/hero/subtitle' ], [ '/template/page-home-copy/hero/background' ] ] } );
	return screen.getElementById('builder-dialog-problems').hidden === false && problemsText() === words['/_admin/builder/error/key-exists']
		+ ' '+ words['/_admin/builder/error/keys-in-the-way'].replace( '%s', '/template/page-home-copy/hero/title, /template/page-home-copy/hero/subtitle' )
		+ ' '+ words['/_admin/builder/error/slots-in-the-way'].replace( '%s', '/template/page-home-copy/hero/background' );
} )() );
check( '...a list with nothing in it says nothing of its kind, and no sentence of the server\'s is shown: an answer in sentences names nothing', ( function() {
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ [], [ '/template/page-home-copy/hero/background' ] ] } );
	const first = problemsText();
	requests[0].callback( 409, { code : 'builder_key_exists', params : [ 'the key "/template/page-home-copy/hero/title" is there already' ] } );
	return first === words['/_admin/builder/error/key-exists']+ ' '+ words['/_admin/builder/error/slots-in-the-way'].replace( '%s', '/template/page-home-copy/hero/background' ) && problemsText() === words['/_admin/builder/error/key-exists'];
} )() );
check( '...a key or a slot that a panel refused - another request made it in the meantime - is named by its uri after the sentence of its code, and params of another status are not shown', ( function() {
	requests[0].callback( 409, { code : 'builder_slot', params : [ '/template/page-home-copy/hero/background' ] } );
	const slot = problemsText();
	requests[0].callback( 409, { code : 'builder_key', params : [ '/template/page-home-copy/hero/title' ] } );
	const key = problemsText();
	requests[0].callback( 403, { code : 'builder_permission', params : [ '/_admin/keys/manage' ] } );
	return slot === words['/_admin/builder/error/slot']+ ' /template/page-home-copy/hero/background' && key === words['/_admin/builder/error/key']+ ' /template/page-home-copy/hero/title' && problemsText() === words['/_admin/builder/error/permission'];
} )() );
check( '...a name that makes a file that is there is said in the dialog, which stays open - and the copy is opened where it was made', ( function() {
	requests[0].callback( 409, { code : 'builder_exists' } );
	const refused = problemsText() === words['/_admin/builder/error/exists'] && dialogBox().open === true;
	requests = [];
	const route = [];
	const realGo = sandbox.Nino.admin.router.go;
	sandbox.Nino.admin.router.go = function( panel, parts ) { route.push( panel+ ':'+ parts.join() ) };
	builder._visit( 'page-home-copy' );
	sandbox.Nino.admin.router.go = realGo;
	return refused === true && route.join() === 'builder:page-home-copy' && requests.map( function( request ) { return request.action } ).sort().join() === 'builder/list,builder/load,builder/registry' && requests.find( function( request ) { return request.action === 'builder/load' } ).payload.file === 'page-home-copy';
} )() );
check( '...and a template the project has no file for is copied from its file name', ( function() {
	builder._files = [];
	builder._duplicateTemplate( 'page-none' );
	return copyName().el.value === 'Copy of page-none';
} )() );
builder._files = [ { file : 'page-home', name : 'Home', editable : true } ];
builder._duplicateTemplate( 'page-home' );
requests = [];
copyButton().click();
requests[0].callback( 200, { model : { file : 'page-home-copy' } } );
check( 'a copy that was made closes the dialog and opens the copy', dialogBox().open === false && requests.some( function( request ) { return request.action === 'builder/load' && request.payload.file === 'page-home-copy' } ) );
check( 'the list has a name, a file, the frames, the sections and the routes, and the buttons - no count of blocks of HTML+, no column for whether the file is read completely - sorted as the server sorts it', ( function() {
	let given = null;
	const realTable = sandbox.Nino.adminUi.table;
	sandbox.Nino.adminUi.table = function( options ) { given = options; return { setRows : function() {} } };
	builder._files = [
		{ file : 'page-a', name : 'A', header : 'html-header', footer : '', sections : 2, foreign : 1, readable : true, reason : null, editable : true, usedBy : [ { route : 'GET://a', uri : '/a' } ] },
		{ file : 'page-b', name : '', header : '', footer : '', sections : 0, foreign : 1, readable : false, reason : { line : 3, code : 'second-row', detail : '', text : '' }, editable : true, usedBy : [] },
	];
	builder._renderList();
	sandbox.Nino.adminUi.table = realTable;
	return given.columns.map( function( column ) { return column.key } ).join() === 'name,file,sections,header,footer,usedBy,actions' && given.rows.map( function( row ) { return row.file } ).join() === 'page-a,page-b'
		&& given.rows.map( function( row ) { return row.name } ).join() === 'A,page-b' && given.rows.map( function( row ) { return row.header+ '|'+ row.footer } ).join() === 'html-header|none,none|none';
} )() );
check( '...a template that is not read completely has a mark by its name, with the reason of the first block it failed at for a tooltip - one that is read has none', ( function() {
	let given = null;
	const realTable = sandbox.Nino.adminUi.table;
	sandbox.Nino.adminUi.table = function( options ) { given = options; return { setRows : function() {} } };
	builder._renderList();
	sandbox.Nino.adminUi.table = realTable;
	const warnings = given.rows.map( function( row ) { return row.warning } );
	const marks = {};
	const realOne2 = builder._one;
	builder._one = function( root, selector ) { const found = realOne2.call( builder, root, selector ); marks[selector] = found; return found };
	builder._nameCell( given.rows[1] );
	const warned = { hidden : marks['.builder-name-warning'].hidden, title : marks['.builder-name-warning'].title, text : marks['.builder-name-text'].textContent };
	builder._nameCell( given.rows[0] );
	builder._one = realOne2;
	return warnings.join( '|' ) === '|Line 3: The section has more than one row' && warned.hidden === false && warned.title === 'Line 3: The section has more than one row' && warned.text === 'page-b' && marks['.builder-name-warning'].hidden === true;
} )() );
check( '...and the three buttons of a row: open, duplicate, delete - the first two off for a file that is no page template of the grammar', ( function() {
	const buttons = builder._rowActions( { file : 'page-a', editable : false } ).querySelectorAll('button');
	return texts( buttons ) === 'Open,Duplicate,Delete' && buttons[0].disabled === true && buttons[1].disabled === true && buttons[2].disabled !== true;
} )() );

console.log( '\nThe tools of the frames, and the buttons that add' );

doc = open();
builder._renderEditor();
// The tools of one frame, as they are when it is drawn - drawing the preview again, as a change does, makes others
const toolsOf = function( path ) {
	const found = {};
	const bar = builder._tools( path );
	[ 'settings', 'html', 'up', 'down', 'duplicate', 'delete' ].forEach( function( name ) { found[name] = bar.querySelector('[data-tool="'+ name+ '"]') } );
	found.bar = bar;
	return found;
};
const component = toolsOf( [ 0, 0, 0 ] );
check( 'the tools of a frame are the buttons of its toolbar: the first component cannot go up, has no tool for HTML+, and has the others', component.up.disabled === true && component.down.disabled === false && component.html.hidden === true && component.settings.hidden === false
	&& component.duplicate.disabled === false && component.delete.disabled === false );
check( '...each is an icon with the word for its title and its label', component.bar.getAttribute('role') === 'toolbar' && component.bar.getAttribute('aria-label') === 'Tools' && unnamed( component.bar ).length === 0 && component.bar.querySelectorAll('button').length === 6
	&& component.bar.querySelectorAll('button').map( function( button ) { return button.title } ).join() === 'Settings,Edit as HTML+,Move up,Move down,Duplicate,Delete' );
component.down.dispatch( 'click' );
check( '...and pressed they work on the frame: down moves the title behind the subtitle', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name } ).join() === 'subtitle,title,button' && changes === 1 );
component.duplicate.dispatch( 'click' );
check( '...duplicate copies what stands first now, behind it', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name+ ':'+ node.source.split('/').pop() } ).join() === 'subtitle:subtitle,subtitle:subtitle-2,title:title,button:name' && changes === 2 );
component.delete.dispatch( 'click' );
check( '...delete takes it away at once, as it holds nothing', builder._doc.model.blocks[0].cols[0].components.map( function( node ) { return node.name+ ':'+ node.source.split('/').pop() } ).join() === 'subtitle:subtitle-2,title:title,button:name' && changes === 3 && questions.length === 0 );
const section = toolsOf( [ 0 ] );
check( 'the tools of a section have one for HTML+ more', section.html.hidden === false && section.up.disabled === true && section.down.disabled === false && section.settings.hidden === false && section.delete.disabled === false );
check( '...and the loop has none: every frame, every column and every component has its tools in its head, and the loop - which is the status of its column - is not among them', ( function() {
	const model = builder._doc.model;
	const frames = model.blocks.length;
	const cols = model.blocks.reduce( function( sum, block ) { return sum + ( block.cols || [] ).length }, 0 );
	const components = model.blocks.reduce( function( sum, block ) { return sum + ( block.cols || [] ).reduce( function( inner, col ) { return inner + col.components.length }, 0 ) }, 0 );
	return preview().querySelectorAll('.builder-tools').length === frames + cols + components && preview().querySelector('.is-loop').querySelector('.builder-tools') === null;
} )() );
requests = [];
section.html.dispatch( 'click' );
check( '...the one for HTML+ asks the server for the markup of the section', requests.length === 1 && requests[0].action === 'builder/source' );
doc = open();
builder._duplicateAt( [ 0 ] );
check( 'a copy of a section from its tools is a new section: its keys are new, so it may be renamed without moving anything, and it is the selection', builder._doc.model.blocks[1].id === 'hero-2' && builder._fresh['hero-2'] === true && builder._fresh['hero'] !== true
	&& builder._same( builder._sel, [ 1 ] ) && changes === 1 && builder._doc.model.blocks[2].id === 'services' );
check( '...a copy of a component stands behind it, with a key beside the old one, and a copy of a column behind it', ( function() {
	builder._duplicateAt( [ 0, 0, 0 ] );
	const first = builder._doc.model.blocks[0].cols[0].components;
	builder._duplicateAt( [ 0, 0 ] );
	return first[1].name === 'title' && first[1].source === '/template/page-home/hero/title-2' && JSON.stringify( first[1].create ) === '{}' && JSON.stringify( first[1].values ) === '{"en_US":"Welcome","de_DE":"Willkommen"}' && builder._doc.model.blocks[0].cols.length === 2 && builder._same( builder._sel, [ 0, 1 ] );
} )() );
check( '...and a loop is not copied: it has no tool for it', ( function() {
	const before = JSON.stringify( builder._doc.model );
	builder._duplicateAt( [ 2, 1, 'x' ] );
	return JSON.stringify( builder._doc.model ) === before;
} )() );

doc = open();
changes = 0;
builder._stepNode( [ 0, 0, 0 ], 1 );
check( 'down moves a component behind the next, and it stays the selection', builder._doc.model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'subtitle,title,button' && builder._same( builder._sel, [ 0, 0, 1 ] ) && changes === 1 );
builder._stepNode( [ 0, 0, 1 ], -1 );
builder._stepNode( [ 0 ], -1 );
check( '...up moves it back, and from the first place there is no step: the document is not changed by it', builder._doc.model.blocks[0].cols[0].components.map( function( component ) { return component.name } ).join() === 'title,subtitle,button' && changes === 2 && builder._unsaved( builder._doc ) === false );
builder._stepNode( [ 1 ], -1 );
check( '...a section moves among the sections', builder._doc.model.blocks.map( function( block ) { return block.id || 'html' } ).join() === 'services,hero,html,contact' && builder._same( builder._sel, [ 0 ] ) );
builder._stepNode( [ 0, 0 ], 1 );
builder._stepNode( [ 0, 1 ], -1 );
check( '...and a column among the columns of its section', changes === 5 && builder._doc.model.blocks[0].cols.length === 2 && builder._doc.model.blocks[0].cols[0].stack === null && builder._doc.model.blocks[0].cols[1].stack !== null );

doc = open();
changes = 0;
questions = [];
builder._deleteAt( [ 0, 0, 2 ] );
check( 'a component is deleted at once, and the parent is the selection', builder._doc.model.blocks[0].cols[0].components.length === 2 && questions.length === 0 && builder._same( builder._sel, [ 0, 0 ] ) && changes === 1 );
builder._addSection();
builder._deleteAt( [ 4 ] );
check( '...so is a section that holds nothing', builder._doc.model.blocks.length === 4 && questions.length === 0 );
builder._deleteAt( [ 0 ] );
check( 'a section with something in it asks first, and nothing is deleted until the answer says so', questions.length === 1 && builder._doc.model.blocks.length === 4 && questions[0].title === 'Section' && questions[0].message === words['/_admin/builder/confirm/delete-node']
	&& questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'delete,cancel' );
questions[0].onChoose( 'cancel' );
check( '...Cancel leaves it', builder._doc.model.blocks.length === 4 && builder._doc.model.blocks[0].id === 'hero' );
questions[0].onChoose( 'delete' );
check( '...Delete takes it, with everything in it', builder._doc.model.blocks.length === 3 && builder._doc.model.blocks[0].id === 'services' && builder._same( builder._sel, [] ) );
questions = [];
builder._deleteAt( [ 0, 1 ] );
check( 'a column with a loop asks too, and the loop takes its components with it', questions.length === 1 && questions[0].title === 'Column' && ( questions[0].onChoose( 'cancel' ), builder._doc.model.blocks[0].cols.length === 2 ) );
questions = [];
builder._deleteAt( [ 0, 1, 'x' ] );
check( '...a loop is no node to delete: it is set to Static in its column, and deleting it does nothing - no question, no change', questions.length === 0 && builder._doc.model.blocks[0].cols[1].stack !== null && builder._doc.model.blocks[0].cols[1].components.length === 4 );
builder._doc.model.blocks[0].cols[1].stack = null;
builder._doc.model.blocks[0].cols[1].components = [];
builder._deleteAt( [ 0, 1 ] );
check( '...an empty column is deleted without a question', questions.length === 0 && builder._doc.model.blocks[0].cols.length === 1 );

doc = open();
builder._doc.model.animate = true;
builder._addSection();
builder._addCol( [ 0 ] );
builder._addComponent( [ 0, 0 ], 'button' );
check( 'a new section is below the last, fresh, with the animation of the template - the bare class where it animates its sections; a column ends the row of its section; a component ends its column - with its key', builder._doc.model.blocks[4].id === 'section' && builder._fresh.section === true && builder._doc.model.blocks[4].settings.vpa === ''
	&& builder._animationMode( true, builder._doc.model.blocks[4].settings ) === 'like' && builder._doc.model.blocks[0].cols.length === 2 && JSON.stringify( builder._doc.model.blocks[0].cols[1].width ) === '{"s":100,"m":100,"l":100}' && JSON.stringify( builder._doc.model.blocks[0].cols[0].width ) === '{"s":100,"m":100,"l":66}'
	&& builder._doc.model.blocks[0].cols[0].components.length === 4 && builder._doc.model.blocks[0].cols[0].components[3].source === '/template/page-home/hero/button' && JSON.stringify( builder._doc.model.blocks[0].cols[0].components[3].create ) === '{}' && builder._same( builder._sel, [ 0, 0, 3 ] ) );
builder._addCol( [ 1 ] );
builder._addComponent( [ 1, 1 ], 'title' );
check( '...at the end of the level they are meant for: the column of the services is the third, the component stands last in the loop', builder._doc.model.blocks[1].cols.length === 3 && builder._doc.model.blocks[1].cols[1].components.length === 5 && builder._doc.model.blocks[1].cols[1].components[4].name === 'title'
	&& builder._doc.model.blocks[1].cols[1].components[4].source === '' && builder._doc.model.blocks[1].cols[1].components[4].create === undefined && builder._doc.model.blocks.length === 5 );
check( '...the buttons of the preview do the same: a section, a column - and a component, which is chosen in the lightbox the button opens, and goes in at once: the dialog stays and shows its settings', ( function() {
	doc = open();
	builder._renderEditor();
	screen.getElementById('builder-add-section').click();
	preview().children[0].querySelector('.builder-add-col').click();
	preview().children[0].querySelector('.builder-adds .builder-add-button').click();
	const box = lightboxOf();
	const opened = dialogBox().open === true && box !== null && itemsOf( box ).length === Object.keys( registryFixture.components ).length && takenIn( box ) === belonging('hero');
	itemNamed( box, 'button' ).click();
	return opened && dialogBox().open === true && lightboxOf() === null && dialogText('builder-dialog-kind') === titleOf('button') && builder._same( builder._sel, [ 0, 0, 3 ] )
		&& builder._doc.model.blocks.length === 5 && builder._doc.model.blocks[0].cols.length === 2 && builder._doc.model.blocks[0].cols[0].components.length === 4 && builder._doc.model.blocks[0].cols[0].components[3].name === 'button'
		&& preview().children.length === 5 && changes === 3;
} )() );
builder._closeDialog();

doc = open();
builder._rename( [ 0 ], 'start' );
check( 'a section that was saved and is renamed says from where: the server moves its keys, and the sources stay as they are', builder._doc.model.blocks[0].id === 'start' && builder._doc.model.blocks[0].renamedFrom === 'hero'
	&& builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' );
builder._rename( [ 0 ], 'intro' );
check( '...a second rename keeps the first name, and the way back to it is no rename at all', builder._doc.model.blocks[0].renamedFrom === 'hero' && ( builder._rename( [ 0 ], 'hero' ), builder._doc.model.blocks[0].renamedFrom === undefined ) );
builder._addSection();
builder._addComponent( [ 4, 0 ], 'title' );
builder._rename( [ 4 ], 'faq' );
check( 'a section made here and renamed is written new: nothing was ever saved under its old name', builder._doc.model.blocks[4].id === 'faq' && builder._doc.model.blocks[4].renamedFrom === undefined && builder._doc.model.blocks[4].cols[0].components[0].source === '/template/page-home/faq/title'
	&& JSON.stringify( builder._doc.model.blocks[4].cols[0].components[0].create ) === '{}' && builder._fresh.faq === true && builder._fresh.section === undefined );
console.log( '\nSaving the document' );

doc = open();
builder._doc.model.blocks[0].cols[0].components[0].source = 'title';
builder._save( function( ok ) { answers.push( ok ) } );
check( 'a source that means nothing is not saved: no request goes, the first red one is selected, and the line says why', requests.length === 0 && answers[answers.length - 1] === false && builder._status.state === 'error' && builder._same( builder._sel, [ 0, 0, 0 ] ) );

doc = open();
builder._doc.model.name = 'Start';
builder._save( function( ok ) { answers.push( ok ) } );
check( 'a save sends the file, the model and the hash, and nothing else while it runs: a second one is none', requests.length === 1 && requests[0].action === 'builder/save' && requests[0].payload.file === 'page-home' && requests[0].payload.hash === 'h1' && requests[0].payload.model.name === 'Start'
	&& requests[0].payload.force === undefined && builder._saving === true && ( builder._save(), requests.length === 1 ) );
const saved = JSON.parse( JSON.stringify( builder._doc.model ) );
saved.blocks[0].id = 'hero';
requests[0].callback( 200, { model : saved, hash : 'h2' } );
check( 'the answer replaces the model and the hash, and the document holds nothing unsaved', builder._doc.hash === 'h2' && builder._doc.model.name === 'Start' && builder._unsaved( builder._doc ) === false && builder._saving === false && builder._status.state === 'saved' && answers[answers.length - 1] === true && builder._fresh.x === undefined );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 409, { code : 'builder_conflict', error : 'x' } );
check( 'a conflict asks: reload, save anyway or cancel', questions.length === 1 && questions[0].choices.map( function( choice ) { return choice.value } ).join() === 'reload,force,cancel' && builder._saving === false && builder._unsaved( builder._doc ) === true );
questions[0].onChoose( 'force' );
check( '...Save anyway sends the same model again with force, and the old hash', requests.length === 2 && requests[1].action === 'builder/save' && requests[1].payload.force === true && requests[1].payload.hash === 'h1' && requests[1].payload.model.name === 'Start' );
questions[0].onChoose( 'reload' );
check( '...Reload loads the file again - the model, the registry, the list - and nothing is sent that writes', requests.slice( 2 ).map( function( request ) { return request.action } ).sort().join() === 'builder/list,builder/load,builder/registry' );
const before = requests.length;
questions[0].onChoose( 'cancel' );
check( '...Cancel does nothing', requests.length === before && builder._unsaved( builder._doc ) === true );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 400, { code : 'builder_invalid', error : 'x', params : [ 'the section "services": the source "x" means nothing here', 'the name is bad' ] } );
check( 'a model the server refuses stays unsaved, with its problems at the blocks they name', builder._problems.length === 2 && builder._blame( builder._doc.model, builder._problems ).blocks[1].length === 1 && builder._unsaved( builder._doc ) === true && builder._status.state === 'error' );
builder._changed();
check( '...the next change lets them go', builder._problems.length === 0 );

doc = open();
builder._doc.model.name = 'Start';
builder._save();
requests[0].callback( 403, { code : 'builder_permission', error : 'x', params : [ '/_admin/keys/manage' ] } );
check( 'a refusal for a permission is an error and nothing is lost', builder._unsaved( builder._doc ) === true && builder._saving === false && builder._status.state === 'error' );

check( 'the shell is told what is unsaved, and can drop it', ( function() {
	doc = open();
	const clean = registered.isDirty() === false;
	builder._doc.model.name = 'Start';
	const dirty = registered.isDirty() === true;
	registered.discard();
	return clean === true && dirty === true && registered.isDirty() === false && builder._doc.model.name === 'Home';
} )() );
check( '...and a save it asks for reports how it went', ( function() {
	doc = open();
	builder._doc.model.name = 'Start';
	let said = null;
	registered.save( function( ok ) { said = ok } );
	requests[0].callback( 200, { model : builder._doc.model, hash : 'h3' } );
	return said === true;
} )() );

console.log( '\nThe blocks of HTML+' );

doc = open();
let bar = null;
builder._applyBlock( [ 2 ], '<section id="map"></section>', 'html', function( text ) { bar = text } );
check( 'a block is applied by sending the model with the block edited, and asking the server to read it again', requests.length === 1 && requests[0].action === 'builder/source' && requests[0].payload.model.blocks[2].edited === true && requests[0].payload.model.blocks[2].kind === 'html'
	&& requests[0].payload.model.blocks[2].source === '<section id="map"></section>' && requests[0].payload.model.blocks[2].reason === null && builder._doc.model.blocks[2].edited === undefined );
const again = JSON.parse( JSON.stringify( requests[0].payload.model ) );
again.blocks[2] = { kind : 'section', id : 'map', settings : builder._doc.model.blocks[0].settings, background : null, cols : [ builder._newCol() ] };
requests[0].callback( 200, { model : again, parts : [], source : '' } );
check( '...what comes back is the model: a block that reads as a section is one, and is new to the page', builder._doc.model.blocks[2].kind === 'section' && builder._fresh.map === true && builder._same( builder._sel, [ 2 ] ) );

doc = open();
builder._applyBlock( [ 0 ], '<div></div>', 'section', function( text ) { bar = text } );
check( 'a section edited as html is a block that has no reason yet, for the server to find one', requests[0].payload.model.blocks[0].kind === 'html' && requests[0].payload.model.blocks[0].reason.code === '' );
requests[0].callback( 400, { code : 'builder_invalid', params : [ 'block 1 is neither a section nor a block of html' ] } );
check( '...and what the server refuses it for is said in the dialog', typeof bar === 'string' && bar.indexOf( 'block 1' ) !== -1 && builder._doc.model.blocks[0].kind === 'section' );

doc = open();
builder._doc.model.blocks[0].renamedFrom = 'intro';
builder._doc.model.blocks[0].cols[0].components[0].create = { value : 'Welcome' };
builder._doc.model.blocks[0].cols[0].components[1].create = { value : 'We build' };
builder._doc.model.blocks[0].background.create = { label : 'Hero', width : 1600, height : 900 };
builder._applyBlock( [ 0 ], '<section id="hero"></section>', 'section', function( text ) { bar = text } );
const reread = JSON.parse( JSON.stringify( builder._doc.model ) );
delete reread.blocks[0].renamedFrom;
reread.blocks[0].cols[0].components.forEach( function( component ) { delete component.create } );
delete reread.blocks[0].background.create;
requests[0].callback( 200, { model : reread, parts : [], source : '' } );
check( 'a section applied as html keeps what the markup cannot carry: the keys and slots it is to make, and where it was renamed from', ( function() {
	const section = builder._doc.model.blocks[0];
	return ( section.cols[0].components[0].create || {} ).value === 'Welcome' && ( section.cols[0].components[1].create || {} ).value === 'We build' && ( section.background.create || {} ).width === 1600 && section.renamedFrom === 'intro'
		&& section.cols[0].components[2].create === undefined && builder._fresh.hero === undefined;
} )() );

doc = open();
builder._doc.model.blocks[0].renamedFrom = 'intro';
builder._doc.model.blocks[0].cols[0].components[0].create = { value : 'Welcome' };
builder._applyBlock( [ 0 ], '<section id="top"></section>', 'section', function( text ) { bar = text } );
const moved = JSON.parse( JSON.stringify( builder._doc.model ) );
moved.blocks[0].id = 'top';
delete moved.blocks[0].renamedFrom;
moved.blocks[0].cols[0].components[0].source = '/template/page-home/top/title';
delete moved.blocks[0].cols[0].components[0].create;
requests[0].callback( 200, { model : moved, parts : [], source : '' } );
check( '...but not for a section whose id or sources are not the ones it had: nothing is made of them that was not asked for', builder._doc.model.blocks[0].renamedFrom === undefined && builder._doc.model.blocks[0].cols[0].components[0].create === undefined );

console.log( '\nEvery form and menu is drawn' );

const drawn = function( what, run ) {
	try {
		run();
		return true;
	} catch( error ) {
		console.log( '      '+ what+ ': '+ error.message );
		return false;
	}
};

doc = open();
builder._renderEditor();
builder._doc.model.blocks[1].cols[1].stack.attributes.sort = 'a,b';
builder._doc.model.blocks[1].cols[1].components.push( { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>x</p>' } );
builder._doc.model.blocks[1].cols[1].components.push( { name : 'spacer', source : '', text : null, attributes : { size : '2' } } );
builder._doc.model.blocks[3].cols.push( builder._newCol() );
builder._doc.model.blocks[3].cols[1].components.push( { name : 'image', source : '/template/page-home/contact/image', text : null, attributes : {}, create : { label : 'Image', width : 1600, height : 900 } } );

const paths = [];
builder._doc.model.blocks.forEach( function( block, b ) {
	paths.push( [ b ] );
	( block.cols || [] ).forEach( function( col, c ) {
		paths.push( [ b, c ] );
		if( col.stack !== null )
			paths.push( [ b, c, 'x' ] );
		col.components.forEach( function( component, k ) { paths.push( [ b, c, k ] ) } );
	} );
} );
paths.push( [] );

// What every dialog and the preview have in common, looked for in each view: the icons, the groups of radio buttons, the tabs
const faults = [];
const inspect = function( where, root ) {

	if( unnamed( root ).length > 0 )
		faults.push( where+ ': an icon control with no title or no label that says the same' );

	if( iconsKnown( root ) === false )
		faults.push( where+ ': an icon that is no symbol of the sprite' );

	root.querySelectorAll('[role="radiogroup"]').forEach( function( group ) {

		const radios = radiosOf( group );
		const names = radios.map( function( radio ) { return radio.name } ).filter( function( name, at, list ) { return list.indexOf( name ) === at } );
		const chosen = radios.filter( function( radio ) { return radio.checked } ).length;

		if( group.getAttribute('aria-label') === null || group.getAttribute('aria-label') === '' || radios.length === 0 || radios.some( function( radio ) { return radio.type !== 'radio' } ) || names.length !== 1 || chosen > 1 )
			faults.push( where+ ': a radio group that is none ('+ group.getAttribute('aria-label')+ ')' );

		if( chosen === 0 && radios.some( function( radio ) { return radio.disabled === false } ) )
			faults.push( where+ ': a radio group with none of its buttons chosen ('+ group.getAttribute('aria-label')+ ')' );
	} );

	root.querySelectorAll('[role="tab"]').forEach( function( tab ) {

		const pane = screen.getElementById( tab.getAttribute('aria-controls') );

		if( pane === null || pane.getAttribute('role') !== 'tabpanel' || pane.getAttribute('aria-labelledby') !== tab.id )
			faults.push( where+ ': the tab '+ tab.id+ ' and its pane do not belong together' );
	} );
};

check( 'the form of every node can be drawn: '+ paths.length+ ' of them - the template, the sections, the blocks, the columns, the loops, the components - in each view of the preview', builder.VIEWS.every( function( view ) {
	builder._setView( view );
	return paths.filter( function( at ) {
		return drawn( 'settings '+ at.join('.')+ ' in '+ view, function() {
			builder._openSettings( at );
			inspect( 'settings '+ at.join('.')+ ' in '+ view, dialogBox() );
		} ) === false;
	} ).length === 0;
} ) );
builder._setView('l');
check( '...and every icon of them has a title and a label that say the same, every group of choices is a radio group a screen reader reads as a field, every tab has its pane', faults.length === 0 );
if( faults.length > 0 )
	console.log( '      '+ faults.slice( 0, 6 ).join( '\n      ' ) );
check( 'the tools of every frame can be drawn - a loop has none -, and the frame the pointer is over is the one that has them seen', paths.filter( function( at ) {
	return at.length > 0 && at[at.length - 1] !== 'x' && drawn( 'tools '+ at.join('.'), function() { builder._tools( at ) } ) === false;
} ).length === 0 && drawn( 'hover', function() {
	const first = { classList : { add : function() {}, remove : function() {} } };
	builder._hover( first );
	builder._hover( first );
	builder._hover( null );
} ) && builder._hovered === null );
check( 'the preview of every view and the buttons at the end of a column can be drawn - and its icons are those of the sprite', builder.VIEWS.every( function( view ) {
	return drawn( 'preview '+ view, function() {
		builder._setView( view );
		builder._pickComponent( [ 0, 0 ], made('button') );
		builder._pickComponent( [ 1, 1 ], made('button') );
		builder._addsRow( [ 0, 0 ] );
		builder._addsRow( [ 1, 1 ] );
		inspect( 'preview '+ view, preview() );
	} );
} ) && faults.length === 0 );
builder._setView('l');
check( 'the list, with templates and with none, the name with its mark, and the dialogs that make a new template and a copy of one', drawn( 'list', function() {
	builder._files = [ { file : 'page-home', name : '', header : '', footer : '', sections : 1, foreign : 1, readable : false, reason : { line : 3, code : 'second-row', detail : '', text : '' }, editable : false, usedBy : [] } ];
	builder._renderList();
	builder._rowActions( { file : 'page-home', editable : true } );
	builder._nameCell( { name : 'Home', warning : '' } );
	builder._nameCell( { name : 'Home', warning : 'Line 3: The section has more than one row' } );
	builder._newTemplate();
	builder._duplicateTemplate( 'page-home' );
	builder._duplicateTemplate( 'page-none' );
	builder._confirmDelete( 'page-home' );
} ) );
check( 'the source row of every kind: a key in a section, a field in a loop, a picture, a link, the background - and the dialog of the source with no key to find', drawn( 'source row', function() {
	const section = builder._doc.model.blocks[1];
	const base = { fixed : true, create : true, fields : null, value : '', text : null, section : section, name : 'title', seed : 'Title', caption : 'Current:', onPick : function() {}, onFixed : function() {} };
	[ 'text', 'href', 'image' ].forEach( function( kind ) {
		builder._sourceRow( Object.assign( {}, base, { kind : kind } ) );
		builder._sourceRow( Object.assign( {}, base, { kind : kind, fields : registry().types[0].fields, value : 'title', text : 'x', red : { why : 'field' }, label : 'Link' } ) );
	} );
	builder._keys = [];
	builder._sourceDialog( Object.assign( {}, base, { kind : 'text' } ) );
	builder._backgroundField( [ 0 ] );
} ) );
check( 'the HTML+ editor and the source view', drawn( 'html', function() {
	builder._editBlock( [ 2 ] );
	builder._editBlock( null );
	builder._editBlock( [ 0 ] );
	requests[requests.length - 1].callback( 200, { parts : [ { kind : 'section', block : 0, source : '<section></section>' } ] } );
	builder._showSource();
	requests[requests.length - 1].callback( 200, { parts : [ { kind : 'head', block : null, source : 'a' }, { kind : 'html', block : 2, source : 'b' }, { kind : 'foot', block : null, source : 'c' } ] } );
} ) );
check( 'the errors of the panel, and an editor that is opened and fails to load', drawn( 'load', function() {
	builder._showError( made('div'), 500, { code : 'offline' } );
	requests = [];
	builder._openEditor( 'page-home' );
	requests.forEach( function( request ) { request.callback( 404, { code : 'builder_missing' } ) } );
	builder._openEditor( 'page-home' );
	requests.slice( 3 ).forEach( function( request ) {
		if( request.action === 'builder/load' )
			request.callback( 200, { model : page(), hash : 'h9', source : '' } );
		else if( request.action === 'builder/registry' )
			request.callback( 200, registry() );
		else
			request.callback( 200, { templates : [ { file : 'page-home', usedBy : [ { route : 'GET://', uri : '/home' } ] } ] } );
	} );
} ) && builder._doc !== null && builder._doc.hash === 'h9' && builder._doc.usedBy.length === 1 );

check( 'the stylesheet keeps what the script hides hidden (a button with display set is shown all the same), paints the sections in grey levels, shows the tools of the frame the pointer is over and of the selected one, gives the dialog of a form 40rem - a column of fields about 560px wide -, the wide one of the lightbox and the editors 52rem and the dialog of a source 34rem - and has no picture of its own: the icons are the sprite\'s', ( function() {
	const colours = stylesheet.split( '.builder-frame.builder-color-' ).slice( 1 ).map( function( rule ) { return rule.slice( 0, rule.indexOf( '}' ) ) } );
	return stylesheet.indexOf( '.builder-icon-btn[hidden] {\n\tdisplay: none;' ) !== -1 && colours.length === 6 && colours.every( function( rule ) { return /var\(--editor-(blue|orange)/.test( rule ) === false } )
		&& stylesheet.indexOf( '.builder-pick.is-hover > header > .builder-tools' ) !== -1 && stylesheet.indexOf( '.builder-pick.is-selected > .builder-tools' ) !== -1 && stylesheet.indexOf( 'width: min(40rem, calc(100vw - 2rem));' ) !== -1 && stylesheet.indexOf( 'width: min(52rem, calc(100vw - 2rem));' ) !== -1
		&& stylesheet.indexOf( 'width: min(34rem, calc(100vw - 2rem));' ) !== -1 && stylesheet.indexOf( 'data:' ) === -1 && stylesheet.indexOf( 'url(' ) === -1;
} )() );
check( 'the template holds a fragment for everything the script clones, and the script clones everything the template holds - and none for a tree', ( function() {
	const used = [];
	source.replace( /_fragment\(([^)]*)\)/g, function( all, inner ) { inner.replace( /'([a-z]+)'/g, function( found, name ) { used.push( name ) } ) } );
	const held = [];
	template.replace( /<template id="builder-tpl-([a-z]+)"/g, function( all, name ) { held.push( name ) } );
	return used.length > 0 && used.every( function( name ) { return held.indexOf( name ) !== -1 } ) && held.filter( function( name ) { return used.indexOf( name ) === -1 } ).length === 0 && template.indexOf( 'builder-tpl-row' ) === -1 && template.indexOf( 'builder-tree' ) === -1 && source.indexOf( 'builder-tree' ) === -1;
} )() );
check( 'every icon the script names is a symbol of the sprite, and every symbol of the sprite is named: by the script, or by the template', ( function() {
	const symbols = [];
	template.replace( /<symbol id="builder-icon-([a-z-]+)"/g, function( all, name ) { symbols.push( name ) } );
	const named = [];
	template.replace( /href="#builder-icon-([a-z-]+)"/g, function( all, name ) { named.push( name ) } );
	// The script names them as an icon (_icon, _iconButton, _statusItem), as the icon of an option, in DEVICES and in ALIGN_ICONS, and for each of the WIDTHS (the start of a name that is put together is no name)
	source.replace( /_(?:icon|iconButton)\( '([a-z-]+)'/g, function( all, name ) { named.push( name ) } );
	source.replace( /_statusItem\( '[a-z]+', '([a-z-]+)'/g, function( all, name ) { named.push( name ) } );
	source.replace( /icon\s*:\s*'([a-z-]+)'(?!\s*\+)/g, function( all, name ) { named.push( name ) } );
	Object.keys( builder.DEVICES ).forEach( function( key ) { named.push( builder.DEVICES[key] ) } );
	builder.WIDTHS.forEach( function( value ) { named.push( 'width-'+ value ) } );
	Object.keys( builder.ALIGN_ICONS ).forEach( function( setting ) { Object.keys( builder.ALIGN_ICONS[setting] ).forEach( function( value ) { named.push( builder.ALIGN_ICONS[setting][value] ) } ) } );
	return named.every( function( name ) { return symbols.indexOf( name ) !== -1 } ) && symbols.every( function( name ) { return named.indexOf( name ) !== -1 } );
} )() );
check( 'every text the script and the template ask for by its key is there in both languages - a key that is missing is a word missing on the page - and the two files have the same keys', ( function() {
	const german = {};
	fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' ).split( '\n' ).forEach( function( line ) {
		const found = /^\s*'\[\[(\/[^\]]+)\]\]'\s*=>/.exec( line );
		if( found !== null )
			german[found[1]] = true;
	} );
	const asked = {};
	source.replace( /'(\/_admin\/builder\/[a-z0-9/-]+)'/g, function( all, key ) { asked[key] = true } );
	template.replace( /\[\[(\/_admin\/builder\/[a-z0-9/-]+)\]\]/g, function( all, key ) { asked[key] = true } );
	// A key that ends in a slash or a hyphen is the start of one the script puts together
	const whole = Object.keys( asked ).filter( function( key ) { return /[/-]$/.test( key ) === false } );
	const english = Object.keys( words ).filter( function( key ) { return key.indexOf('/_admin/builder/') === 0 } );
	return whole.length > 100 && whole.every( function( key ) { return Object.prototype.hasOwnProperty.call( words, key ) && german[key] === true } ) && english.length === Object.keys( german ).length && english.every( function( key ) { return german[key] === true } );
} )() );
console.log( '\nThe refusal to delete' );

( function() {
	const lines = [];
	const realEl = builder._el;
	const realLoad = builder._loadList;
	builder._el = function( tag, cls, text ) { if( tag === 'p' ) lines.push( text ); return realEl.apply( builder, arguments ) };
	questions.length = 0;
	requests.length = 0;
	builder._confirmDelete( 'page-home' );
	questions[questions.length - 1].onChoose( 'delete' );
	requests[requests.length - 1].callback( 409, { params : [ 'GET://', 'GET://contact', 'POST://form' ] } );
	builder._el = realEl;
	builder._loadList = realLoad;
	check( 'the refusal names the addresses the list shows (the routes\' keys through _addresses), not uris', lines.length === 1 && /\/, \/contact$/.test( lines[0] ) && lines[0].indexOf( 'GET:' ) === -1 );
} )();

console.log( '\nThe language of the editor, the text of a key and the picture of a slot' );

// The editor of the workbench for the texts that are more than a line, as the dialogs make it: it has the text in its own normal form - no white space
// between the tags - and the text a person typed (value)
const textEditors = [];
sandbox.Nino.admin.htmlEditor = { create : function( mount, value, maxlength, rows, format ) {
	const editor = { value : String( value ).replace( />\s+</g, '><' ), mount : mount, maxlength : maxlength, rows : rows, format : format, destroyed : false,
		getValue : function() { return editor.value }, destroy : function() { editor.destroyed = true } };
	// The box the editor of the workbench types in
	mount.appendChild( made( 'div', 'nino-admin-richtext-content' ) );
	textEditors.push( editor );
	return editor;
} };

const picker = function() { return screen.getElementById('builder-locale') };
const lines = function() { return preview().querySelectorAll('.builder-ph-label').map( function( label ) { return label.textContent } ) };
const heroTitle = function() { return builder._doc.model.blocks[0].cols[0].components[0] };
const lineLabel = 'Text (English (US))';

doc = open();
builder._renderEditor();
check( 'the language is chosen in the bar over the preview, next to the viewports and before them: a select with the words for its title and its label, an option for each language of the site by the name the project gives it, the native one chosen', picker().localName === 'select'
	&& picker().nextSibling === screen.getElementById('builder-viewport-s').parentNode && picker().parentNode.className === 'builder-pane-tools' && picker().title === 'Language of the texts' && picker().getAttribute('aria-label') === 'Language of the texts'
	&& texts( picker().children ) === 'English (US),Deutsch' && picker().children.map( function( option ) { return option.value } ).join() === 'en_US,de_DE' && picker().value === 'en_US' && picker().hidden === false && builder._locale === 'en_US' );
check( '...the language the editor starts in is the native one of the site, the first it has where the native one is none of them, and none for a site that names none', builder._nativeLocale( { locales : [ { locale : 'en_US', name : '' }, { locale : 'de_DE', name : '' } ], native : 'de_DE' } ) === 'de_DE'
	&& builder._nativeLocale( { locales : [ { locale : 'en_US', name : '' }, { locale : 'de_DE', name : '' } ], native : 'fr_FR' } ) === 'en_US' && builder._nativeLocale( {} ) === '' );
check( '...a site with one language has nothing to choose, and the select is out of sight; a language with no name is called by its code', ( function() {
	builder._registry.locales = [ { locale : 'en_US', name : '' } ];
	builder._drawLocales();
	const one = picker().hidden === true && texts( picker().children ) === 'en_US';
	builder._registry.locales = registry().locales;
	builder._drawLocales();
	return one && picker().hidden === false;
} )() );
check( 'the preview says the first line of the text of each component in that language - the title and the subtitle of the hero - and the label of the ones that have none (a key nobody knows)', lines().slice( 0, 3 ).join() === 'Welcome,We build,Button' );
picker().value = 'de_DE';
picker().dispatch('change');
check( 'choosing another language draws the preview in it, and is no change of the document: nothing is unsaved, nothing asked for, nothing kept', builder._locale === 'de_DE' && lines().slice( 0, 3 ).join() === 'Willkommen,We build,Button' && builder._unsaved( builder._doc ) === false && changes === 0 && requests.length === 0 );

builder._locale = 'de_DE';
requests = [];
builder._openEditor( 'page-home' );
requests.forEach( function( request ) {
	if( request.action === 'builder/load' )
		request.callback( 200, { model : page(), hash : 'h9', source : '' } );
	else if( request.action === 'builder/registry' )
		request.callback( 200, registry() );
	else if( request.action === 'text/keys' )
		request.callback( 200, { keys : known() } );
	else
		request.callback( 200, { templates : [ { file : 'page-home', usedBy : [] } ] } );
} );
check( 'a document that is opened starts in the native language again: the choice of the last one is kept nowhere', builder._locale === 'en_US' && picker().value === 'en_US' && lines().slice( 0, 3 ).join() === 'Welcome,We build,Button' );
check( '...and the keys of the project are asked for with it, and the preview is drawn again with them', requests.some( function( request ) { return request.action === 'text/keys' } ) === true );

console.log( '\nThe first line of a text' );

check( 'a text is a line as a placeholder says it: plain text as it is, the tags gone, the end of a paragraph or an item and a break ending the line, the first line that has any text', builder._firstLine( 'Welcome', 60 ) === 'Welcome'
	&& builder._firstLine( '<p>Hello <strong>big</strong> world</p><p>Second</p>', 60 ) === 'Hello big world' && builder._firstLine( 'One<br>Two', 60 ) === 'One' && builder._firstLine( '<ul><li>First</li><li>Second</li></ul>', 60 ) === 'First'
	&& builder._firstLine( "\n\n  One\nTwo", 60 ) === 'One' && builder._firstLine( '<p></p><p> Second </p>', 60 ) === 'Second' && builder._firstLine( '', 60 ) === '' && builder._firstLine( null, 60 ) === '' && builder._firstLine( ' <p> </p> ', 60 ) === '' );
check( '...the entities read as the text they are - and what they make is text and no markup: an escaped tag is not stripped, a tag that is none stays', builder._firstLine( 'Tom &amp; Jerry &quot;at&quot; &#039;home&#039; &#91;x&#93; &nbsp;here &#x41;&#66;', 60 ) === 'Tom & Jerry "at" \'home\' [x] here AB'
	&& builder._firstLine( '&lt;b&gt;bold&lt;/b&gt;', 60 ) === '<b>bold</b>' && builder._firstLine( '&unknown; &#0; &#9999999;', 60 ) === '&unknown; &#0; &#9999999;' && builder._firstLine( 'a   b\t c', 60 ) === 'a b c' );
check( '...a line of more than the characters asked for is cut and ends in an ellipsis - at the characters and not at the bytes of the text', builder._firstLine( 'x'.repeat( 61 ), 60 ) === 'x'.repeat( 60 )+ '…' && builder._firstLine( 'x'.repeat( 60 ), 60 ) === 'x'.repeat( 60 )
	&& builder._firstLine( 'abc def ghi', 5 ) === 'abc d…' && builder._firstLine( 'abc def ghi', 4 ) === 'abc…' && builder._firstLine( 'ab ', 2 ) === 'ab' && builder._firstLine( '\u{1F600}'.repeat( 4 ), 3 ) === '\u{1F600}'.repeat( 3 )+ '…' && builder.LINE_MAX === 60 );

console.log( '\nThe text of a key under its source' );

doc = open();
builder._renderEditor();
changes = 0;
builder._openSettings( [ 0, 0, 0 ] );
const contentPane = function() { return blockOf('content') };
const field = function() { return controlOf( contentPane(), lineLabel ) };
check( 'a component with a text key has the text of its key under its source, in the language the editor works in: a line of text for a key of the plain format, with the limit of the key, a sentence behind the question mark that says when it is written - and no editor of the workbench', field().kind === 'text'
	&& field().el.localName === 'input' && field().el.value === 'Welcome' && field().el.maxLength === 160 && field().el.spellcheck === true && contentPane().children[1].className === 'builder-content-extra'
	&& tipOf( field() ) === 'Written to the key when the template is saved.' && contentPane().querySelector('.nino-admin-hint') === null && textEditors.length === 0 );
change( contentPane(), lineLabel, 'Hello' );
check( 'what is typed is kept in the model, under the language - a change, and nothing is sent: the key is written when the document is saved - and the preview behind shows it', JSON.stringify( heroTitle().values ) === '{"en_US":"Hello"}' && changes === 1
	&& builder._unsaved( builder._doc ) === true && requests.length === 0 && lines()[0] === 'Hello' && builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' );
change( contentPane(), lineLabel, 'Welcome' );
check( '...typed back to what the key holds it is no text at all: none is kept, and the document is as it was saved', heroTitle().values === undefined && builder._unsaved( builder._doc ) === false && lines()[0] === 'Welcome' );
change( contentPane(), lineLabel, 'Hello' );
builder._closeDialog();
builder._openSettings( [ 0, 0, 0 ] );
check( '...the dialog opened again shows the text that was typed and not saved, and not the one the key holds', field().el.value === 'Hello' );
builder._closeDialog();
builder._setLocale('de_DE');
builder._openSettings( [ 0, 0, 0 ] );
check( 'in another language it is the text of that language: the label says it by its name, the key holds a German text, and what is typed there is kept beside the other', controlsIn( contentPane() ).map( function( control ) { return control.label } ).join() === 'Text (Deutsch)'
	&& valueAt( contentPane(), 'Text (Deutsch)' ) === 'Willkommen' && ( change( contentPane(), 'Text (Deutsch)', 'Hallo' ), JSON.stringify( heroTitle().values ) === '{"en_US":"Hello","de_DE":"Hallo"}' ) && lines()[0] === 'Hallo' );
builder._closeDialog();
builder._setLocale('en_US');
builder._openSettings( [ 0, 0, 1 ] );
check( 'a key every language shares has one text, the same in each: it is called so, the shared text is shown, and a text typed for it is kept once - in the language it was typed in last', controlsIn( contentPane() ).map( function( control ) { return control.label } ).join() === 'Text (all languages)'
	&& valueAt( contentPane(), 'Text (all languages)' ) === 'We build' && ( change( contentPane(), 'Text (all languages)', 'We make' ), JSON.stringify( builder._doc.model.blocks[0].cols[0].components[1].values ) === '{"en_US":"We make"}' ) );
builder._closeDialog();
builder._setLocale('de_DE');
builder._openSettings( [ 0, 0, 1 ] );
check( '...it shows the shared text typed in the other language, and what is typed here replaces it', valueAt( contentPane(), 'Text (all languages)' ) === 'We make' && ( change( contentPane(), 'Text (all languages)', 'We craft' ), JSON.stringify( builder._doc.model.blocks[0].cols[0].components[1].values ) === '{"de_DE":"We craft"}' )
	&& ( change( contentPane(), 'Text (all languages)', 'We build' ), builder._doc.model.blocks[0].cols[0].components[1].values === undefined ) );
builder._closeDialog();
builder._setLocale('en_US');

doc = open();
builder._renderEditor();
const stripped = {
	'a fixed text'								: [ 1, 1, 3 ],
	'the field of a loop'					: [ 1, 1, 1 ],
	'a picture in a loop'					: [ 1, 1, 0 ],
	'a key nobody knows'					: [ 0, 0, 2 ],
};
check( 'the text has a field where the source is a text key the project knows - and not for a fixed text, the field of an element in a loop, a picture, a key that is not in the list (the account may not read them, or it is hidden)', Object.keys( stripped ).every( function( what ) {
	builder._openSettings( stripped[what] );
	const children = dialogContent().children.length;
	builder._closeDialog();
	return children === 1 || console.log( '      '+ what+ ': '+ children+ ' children' ) === undefined;
} ) );
check( '...nor for a component that has a fixed text and a key both - the model of a source that was left half changed: the text is fixed', ( function() {
	const both = Object.assign( {}, heroTitle(), { text : 'Fixed' } );
	return both.source === '/template/page-home/hero/title' && builder._textOf( both, 'en_US', builder._keys, builder._registry ) === null && builder._textOf( heroTitle(), 'en_US', builder._keys, builder._registry ) !== null;
} )() );
builder._keys = [];
builder._openSettings( [ 0, 0, 0 ] );
check( '...nor for an account that cannot read the keys: it knows no text to show, and it would only be the text of a key it cannot see', dialogContent().children.length === 1 );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'title' );
builder._addComponent( [ 0, 0 ], 'text' );
const newTitle = builder._doc.model.blocks[0].cols[0].components[3];
const newText = builder._doc.model.blocks[0].cols[0].components[4];
builder._openSettings( [ 0, 0, 3 ] );
check( 'a key the save is going to make has a field too: a line of text for a component that asks for no format, with no text in it - the key holds none yet - and the label of the component as its placeholder, which is no value', field().el.value === '' && field().el.localName === 'input' && field().el.getAttribute('placeholder') === labelIn( 'title', 'en_US' )
	&& JSON.stringify( newTitle.create ) === '{}' && newTitle.values === undefined && textEditors.length === 0 );
check( '...a text typed there is kept in the language it is typed in, as for a key that is there - and no other language has one: the label is nothing the key is made with', ( change( contentPane(), lineLabel, 'My title' ), JSON.stringify( newTitle.values ) === JSON.stringify( { en_US : 'My title' } ) && JSON.stringify( newTitle.create ) === '{}' ) );
builder._closeDialog();
builder._setLocale('de_DE');
builder._openSettings( [ 0, 0, 3 ] );
check( '...in the other language the field is empty and has the label of the component in that language as its placeholder: what was typed in the first one is not there', controlOf( contentPane(), 'Text (Deutsch)' ).el.value === '' && controlOf( contentPane(), 'Text (Deutsch)' ).el.getAttribute('placeholder') === labelIn( 'title', 'de_DE' )
	&& labelIn( 'title', 'de_DE' ) !== labelIn( 'title', 'en_US' ) && builder._textOf( newTitle, 'de_DE', builder._keys, builder._registry ).value === '' );
builder._closeDialog();
builder._setLocale('en_US');
builder._openSettings( [ 0, 0, 4 ] );
const madeEditor = textEditors[textEditors.length - 1];
check( 'a component with a format of its own - [text] - makes its key in that format, and the field is the editor of the workbench in the profile of it: blocks by default, with a limit and not none - and empty, as the key holds no text', madeEditor.format === 'blocks' && madeEditor.value === '' && madeEditor.maxlength === builder.CONTENT_MAX
	&& madeEditor.mount.parentNode.className === 'builder-value' && madeEditor.mount.className.indexOf('builder-content') !== -1 && contentPane().querySelector('.builder-value .builder-label').textContent === lineLabel && contentPane().querySelectorAll('input').length === 0 );
const valueGroup = contentPane().querySelector('.builder-value');
check( '...an editor is no input that a label wraps, so the field is a group that its label names and its sentence - behind the question mark beside the name - describes, as the Text panel names its editors', valueGroup.getAttribute('role') === 'group' && valueGroup.getAttribute('aria-labelledby') === valueGroup.querySelector('.builder-label').id
	&& valueGroup.getAttribute('aria-describedby') === valueGroup.querySelector('.builder-help-tip').id && valueGroup.querySelector('.builder-label').id !== '' && valueGroup.querySelector('.builder-help-tip').id !== '' && valueGroup.querySelector('.builder-label').id !== valueGroup.querySelector('.builder-help-tip').id
	&& valueGroup.querySelector('.builder-help-tip').textContent === words['/_admin/builder/value/hint'] && valueGroup.querySelector('.nino-admin-hint') === null );
check( '...an editor that holds no text says what it is for as an input does: the label of the component in the language it is typed in is its placeholder, drawn by the stylesheet while the box is blank - and it is no text', madeEditor.mount.querySelector('.nino-admin-richtext-content').dataset.placeholder === labelIn( 'text', 'en_US' )
	&& madeEditor.mount.querySelector('.nino-admin-richtext-content').classList.contains('is-blank') === true && stylesheet.indexOf( '.builder-value .nino-admin-richtext-content.is-blank::before {\n\tcontent: attr(data-placeholder);' ) !== -1 );
madeEditor.value = '<p>Hi</p>';
madeEditor.mount.dispatch('input');
check( '...what is typed in it is kept as the editor has it, and the preview says its first line - and the box is no longer blank, and blank again where it is emptied', JSON.stringify( newText.values ) === '{"en_US":"<p>Hi</p>"}' && lines()[4] === 'Hi' && madeEditor.mount.querySelector('.nino-admin-richtext-content').classList.contains('is-blank') === false
	&& ( madeEditor.value = '', madeEditor.mount.dispatch('input'), madeEditor.mount.querySelector('.nino-admin-richtext-content').classList.contains('is-blank') === true && newText.values === undefined ) && ( madeEditor.value = '<p>Hi</p>', madeEditor.mount.dispatch('input'), true ) );
builder._closeDialog();
check( '...and the dialog lets go of the editor when it closes', madeEditor.destroyed === true );
newText.attributes.format = 'lines';
builder._openSettings( [ 0, 0, 4 ] );
const lined = textEditors[textEditors.length - 1];
newText.attributes.format = 'nonsense';
builder._closeDialog();
builder._openSettings( [ 0, 0, 4 ] );
check( '...in the profile its format attribute says (lines, inline) - and plain for a format there is none of, which is how the field shows it: the save refuses a format outside the options of the attribute', lined.format === 'lines' && textEditors.length === 2 && field().el.localName === 'input' && builder._createFormat( registry(), newText ) === 'plain'
	&& builder._createFormat( registry(), newTitle ) === 'plain' && ( newText.attributes.format = 'blocks', builder._createFormat( registry(), newText ) === 'blocks' ) );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._keys = known().concat( [
	{ key : '/template/page-home/services/text', global : false, format : 'blocks', maxlength : 400, values : { en_US : '<p>One</p>\n<p>Two</p>', de_DE : '<p>Eins</p>' } },
	{ key : '/template/page-home/services/title', global : false, format : 'plain', maxlength : 160, values : { en_US : 'One\nTwo', de_DE : 'Eins' } },
	{ key : '/template/page-home/contact/title', global : false, html : true, maxlength : 100, values : { en_US : 'A <em>title</em>' } },
] );
textEditors.length = 0;
builder._openSettings( [ 1, 0, 1 ] );
const stored = textEditors[0];
check( 'a key that is there keeps its format and the field follows it: blocks is the editor in the blocks profile, with the text of the key and its limit - the format of the key and not the one the component would ask for', stored.format === 'blocks' && stored.maxlength === 400 && stored.value === '<p>One</p><p>Two</p>' );
stored.mount.dispatch('input');
stored.mount.dispatch('click');
stored.mount.dispatch('keyup');
stored.mount.dispatch('focusout');
builder._closeDialog();
check( '...the editor shows the text in its own normal form, which is not the key\'s: opened, clicked in and closed, the text is none to keep and the document is as it was saved', builder._doc.model.blocks[1].cols[0].components[1].values === undefined && builder._unsaved( builder._doc ) === false && changes === 0 );
builder._openSettings( [ 1, 0, 1 ] );
const toolbarEditor = textEditors[textEditors.length - 1];
toolbarEditor.value = '<p><strong>One</strong></p><p>Two</p>';
toolbarEditor.mount.dispatch('click');
check( '...a button of its toolbar changes the text without a key press: it is a click, and the text is kept', JSON.stringify( builder._doc.model.blocks[1].cols[0].components[1].values ) === '{"en_US":"<p><strong>One</strong></p><p>Two</p>"}' && builder._unsaved( builder._doc ) === true );
builder._closeDialog();
builder._openSettings( [ 1, 0, 0 ] );
check( '...a key of the plain format that holds a line break is a few lines of text, so that nothing of it is lost by being shown', field().el.localName === 'textarea' && field().el.value === 'One\nTwo' );
builder._closeDialog();
builder._doc.model.blocks[3].cols[0].components[0].source = '/template/page-home/contact/title';
builder._openSettings( [ 3, 0, 0 ] );
check( '...and a key with no format of its own is read as the keys list reads it: html is inline', textEditors[textEditors.length - 1].format === 'inline' && textEditors[textEditors.length - 1].maxlength === 100 );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._keys = known().concat( [ { key : '/template/page-home/hero/other', global : false, format : 'plain', maxlength : 160, values : { en_US : 'Another' } } ] );
builder._openSettings( [ 0, 0, 0 ] );
change( contentPane(), lineLabel, 'Typed' );
const pickKey = function() {
	sourceButton( contentPane() ).click();
	pickerPane('pick').querySelector('input[type="search"]').value = 'other';
	pickerPane('pick').querySelector('input[type="search"]').dispatch('input');
	pickItems()[0].click();
};
pickKey();
check( 'another key as the source is another text: the field shows the text of that key, and what was typed for the one that was is not carried to it', heroTitle().source === '/template/page-home/hero/other' && heroTitle().values === undefined
	&& valueAt( contentPane(), lineLabel ) === 'Another' && builder._unsaved( builder._doc ) === true );
builder._closeDialog();
builder._openSettings( [ 0, 0, 0 ] );
change( contentPane(), lineLabel, 'Typed again' );
sourceButton( contentPane() ).click();
buttonOf( pickerStrip(), 'Static' ).click();
pickerPane('fixed').querySelector('input').value = 'Mehr';
buttonOf( pickerPane('fixed'), 'Use it' ).click();
check( '...a fixed value as the source has no field, and no text of a key is left behind', heroTitle().text === 'Mehr' && heroTitle().values === undefined && contentPane().children.length === 1 );
builder._closeDialog();
builder._openSettings( [ 0, 0, 1 ] );
sourceButton( contentPane() ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
fresh('.builder-new-use').click();
check( '...and the key the source makes is a key to fill: a new one has the field of a key the save makes, empty', valueAt( contentPane(), lineLabel ) === '' && JSON.stringify( builder._doc.model.blocks[0].cols[0].components[1].create ) === '{}' );
builder._closeDialog();

console.log( '\nThe preview says the text and shows the picture' );

doc = open();
builder._renderEditor();
builder._keys = known().concat( [ { key : '/template/page-home/contact/title', global : false, format : 'inline', maxlength : 100, values : { en_US : '<p>A very long first line of a text that goes on and on and on past the sixty characters it may have</p><p>Second</p>', de_DE : '&lt;b&gt;Zwei&lt;/b&gt; &amp; <img src="x" onerror="alert(1)">Drei' } } ] );
builder._renderEditor();
const contactPh = function() { return preview().querySelector('[data-path="3.0.0"]') };
check( 'the placeholder of a text component says the first line of the text in the language of the editor, cut to sixty characters, with the label of the component for its title - and has a mark that it says a text', contactPh().querySelector('.builder-ph-label').textContent === 'A very long first line of a text that goes on and on and on…'
	&& Array.from( contactPh().querySelector('.builder-ph-label').textContent ).length === 60 && contactPh().title === 'Title (Section)' && contactPh().classList.contains('has-line') && preview().querySelector('[data-path="0.0.0"]').classList.contains('has-line') );
check( '...a placeholder with no text to say is as it was: the label of the component, no title, no mark - the ones of a key nobody knows, a key with no text in the language, a field of a loop, a fixed text', preview().querySelector('[data-path="0.0.2"] .builder-ph-label').textContent === 'Button'
	&& preview().querySelector('[data-path="0.0.2"]').title === '' && preview().querySelector('[data-path="0.0.2"]').classList.contains('has-line') === false && preview().querySelector('[data-path="1.1.1"] .builder-ph-label').textContent === 'Title (Section)' && preview().querySelector('[data-path="1.1.3"] .builder-ph-label').textContent === 'Button' );
builder._setLocale('de_DE');
check( '...the text is set as text and is never markup: an escaped tag is a tag shown, a tag that is none is gone with its attributes', contactPh().querySelector('.builder-ph-label').textContent === '<b>Zwei</b> & Drei' && contactPh().querySelectorAll('b').length === 0 && contactPh().querySelector('.builder-ph-label').children.length === 0
	&& contactPh().querySelectorAll('img').every( function( image ) { return image.className === 'builder-ph-picture' && image.src === '' } ) );
builder._setLocale('en_US');
heroTitle().values = { en_US : 'Typed, not saved' };
builder._renderPreview();
check( '...the text that was typed and not saved is the one it says, before the one the key holds', lines()[0] === 'Typed, not saved' );
delete heroTitle().values;
builder._doc.model.blocks[0].cols[0].components.push( { name : 'title', source : '/template/page-home/hero/new', text : null, attributes : { tag : 'h3', style : '', 'class' : '' }, create : {} } );
builder._renderPreview();
const madeAt = function() { return preview().querySelector('[data-path="0.0.3"]') };
check( '...a key the save is going to make has none until one is typed for it: the placeholder says the label of the component and is no text - no title, no mark - and the text typed for it is the line, as for a key that is there', lines()[3] === titleLabel && madeAt().title === '' && madeAt().classList.contains('has-line') === false
	&& ( builder._doc.model.blocks[0].cols[0].components[3].values = { en_US : 'Made when saved' }, builder._renderPreview(), lines()[3] === 'Made when saved' && madeAt().title === titleLabel && madeAt().classList.contains('has-line') === true )
	&& ( builder._setLocale('de_DE'), lines()[3] === titleLabel && madeAt().title === '' && madeAt().classList.contains('has-line') === false ) && ( builder._setLocale('en_US'), true ) );
check( 'the preview that is told no language and no keys says what a component is, as it did: no line, no picture', builder._preview( builder._doc.model, builder._registry, 'l' )[0].cols[0].components.every( function( component ) { return component.line === '' && component.picture === '' } )
	&& builder._preview( builder._doc.model, builder._registry, 'l', { locale : 'en_US', keys : builder._keys } )[0].cols[0].components[0].line === 'Welcome' );

builder._doc.model.blocks[0].cols[0].components.push( { name : 'image', source : '/template/page-home/hero/background', text : null, attributes : registry().components.image.defaults } );
builder._doc.model.blocks[0].cols[0].components.push( { name : 'image', source : '/project/logo/header/image', text : null, attributes : registry().components.image.defaults } );
builder._renderPreview();
const pictureAt = function( index ) { return preview().querySelector('[data-path="0.0.'+ index+ '"]') };
check( 'the placeholder of a picture shows the picture of its slot where the slot has one - in place of the image of its kind -, and keeps the image where it has none', pictureAt( 4 ).classList.contains('has-picture') && pictureAt( 4 ).querySelector('.builder-ph-picture').src === '/uploads/hero.1600x900.jpg'
	&& pictureAt( 4 ).title === 'Image' && pictureAt( 4 ).querySelector('.builder-ph-label').textContent === 'Image' && pictureAt( 5 ).classList.contains('has-picture') === false && pictureAt( 5 ).querySelector('.builder-ph-picture').src === ''
	&& pictureAt( 5 ).title === '' && builder._preview( builder._doc.model, builder._registry, 'l', { locale : 'en_US', keys : [] } )[0].cols[0].components.map( function( component ) { return component.picture } ).join() === ',,,,/uploads/hero.1600x900.jpg,' );
check( '...the picture is the slot\'s and nothing of the loop\'s: the field of a picture in a loop has none to show', preview().querySelector('[data-path="1.1.0"]').classList.contains('has-picture') === false );
check( '...the stylesheet shows the picture only where there is one, in place of the icon, and the bar has room for the language', stylesheet.indexOf( '.builder-ph.has-picture .builder-ph-picture {\n\tdisplay: block;' ) !== -1 && stylesheet.indexOf( '.builder-ph.has-picture .builder-ph-icon {\n\tdisplay: none;' ) !== -1
	&& stylesheet.indexOf( '.builder-ph-picture {\n\tdisplay: none;' ) !== -1 && stylesheet.indexOf( '.builder-pane-tools {' ) !== -1 && stylesheet.indexOf( '.builder-locale[hidden] {\n\tdisplay: none;' ) !== -1
	&& stylesheet.indexOf( '.builder-picture-message.is-warning {\n\tcolor: var(--editor-orange);' ) !== -1 && stylesheet.indexOf( '.builder-picture-message.is-error {\n\tcolor: var(--editor-red);' ) !== -1 );

console.log( '\nThe second line of a placeholder' );

doc = open();
builder._renderEditor();
const phOf = function( path ) { return preview().querySelector('[data-path="'+ path+ '"]') };
const drawsOf = function( path ) { return phOf( path ).querySelector('.builder-ph-draws') };
check( 'a component that says what it draws has a second line in its placeholder, under the first and in a block of its own with it: the tag in upper case, the class as it is, the attribute of the component put in for its name - a title at its default tag draws H3 .nino-section-title, a subtitle DIV .nino-section-subtitle', drawsOf('0.0.0').textContent === 'H3 .nino-section-title' && drawsOf('0.0.0').hidden === false
	&& drawsOf('0.0.0').parentNode.className === 'builder-ph-text' && classes( drawsOf('0.0.0').parentNode.children ) === 'builder-ph-label,builder-ph-draws' && phOf('0.0.0').classList.contains('has-draws') && drawsOf('0.0.1').textContent === 'DIV .nino-section-subtitle' && drawsOf('0.0.2').textContent === 'A .nino-btn' );
builder._keys = known();
builder._renderEditor();
check( '...the first line is what it was: the label of the component, or the first line of its text - and the second goes under that, while the title is still the label', phOf('0.0.0').querySelector('.builder-ph-label').textContent === 'Welcome' && phOf('0.0.0').title === 'Title (Section)'
	&& drawsOf('0.0.0').textContent === 'H3 .nino-section-title' && phOf('0.0.2').querySelector('.builder-ph-label').textContent === 'Button' && phOf('0.0.2').title === '' );
builder._doc.model.blocks[0].cols[0].components[0].attributes.tag = 'h4';
builder._renderEditor();
check( '...a title with the tag h4 draws H4 .nino-section-title; where the component has no value for the name, the default of the schema is put in', drawsOf('0.0.0').textContent === 'H4 .nino-section-title'
	&& ( delete builder._doc.model.blocks[0].cols[0].components[0].attributes.tag, builder._renderEditor(), drawsOf('0.0.0').textContent === 'H3 .nino-section-title' ) );
check( 'a component that does not say what it draws is as it was: one line, the mark is not set - and the block of the second line is there, out of sight and empty', drawsOf('1.0.1') !== null && drawsOf('1.0.1').hidden === true && drawsOf('1.0.1').textContent === '' && phOf('1.0.1').classList.contains('has-draws') === false
	&& phOf('1.0.1').querySelector('.builder-ph-label').textContent === 'Text' && phOf('0.0.1').classList.contains('has-draws') === true );
check( 'a scroll hint is a block component like the others: its placeholder wears the block image of the registry\'s preview, with the label of the component and the second line it says it draws - the anchor and the class of the arrow -, and it is a frame that can be picked and has its tools', ( function() {

	builder._doc.model.blocks[0].cols[0].components.push( { name : 'scroll-hint', source : '', text : null, attributes : { to : '', 'class' : '' } } );
	builder._renderEditor();

	const hint = phOf('0.0.3');
	const found = hint !== null && hint.querySelector('use').getAttribute('href') === '#builder-ph-block' && screen.getElementById('builder-ph-block') !== null && hint.querySelector('.builder-ph-label').textContent === 'Scroll hint'
		&& drawsOf('0.0.3').textContent === 'A .nino-hero-arrowdown' && hint.classList.contains('has-draws') && hint.classList.contains('builder-pick') && builder._registry.components['scroll-hint'].preview === 'block';

	builder._doc.model.blocks[0].cols[0].components.pop();
	builder._renderEditor();

	return found;
} )() );
check( '...what a schema says is put in as it is said: the tag is the first word and in upper case, a name in braces is the attribute of the component - the default of the schema where it has none, nothing where the schema has none either -, a word alone is a tag alone, the whole tag may be a name in braces - as it is in the kernel\'s titles -, and a schema that says nothing, or something that is no text, says no line',
	builder._draws( { draws : '{tag} .nino-x', defaults : { tag : 'div' } }, { attributes : { tag : 'h3' } } ) === 'H3 .nino-x' && builder._draws( { draws : '{tag} .nino-x', defaults : { tag : 'div' } }, { attributes : {} } ) === 'DIV .nino-x'
	&& builder._draws( { draws : 'h{level} .nino-x' }, { attributes : { level : '4' } } ) === 'H4 .nino-x' && builder._draws( { draws : 'h{level} .nino-x', defaults : { level : '2' } }, { attributes : {} } ) === 'H2 .nino-x'
	&& builder._draws( { draws : 'h{level} .nino-x' }, { attributes : {} } ) === 'H .nino-x' && builder._draws( { draws : 'hr' }, {} ) === 'HR' && builder._draws( { draws : 'p .a-{style}' }, { attributes : { style : 'loud' } } ) === 'P .a-loud'
	&& builder._draws( {}, { attributes : {} } ) === '' && builder._draws( { draws : '' }, {} ) === '' && builder._draws( { draws : 5 }, {} ) === '' );
check( '...the stylesheet sets the image of a placeholder with a second line at the top and leaves the one-line placeholder centred, as it was; the line is in the code font of the workbench, at half strength, on one line cut off with an ellipsis - and out of sight while it has nothing to say', stylesheet.indexOf( '.builder-ph.has-draws {\n\talign-items: flex-start;' ) !== -1
	&& /\.builder-ph \{[^}]*align-items: center;/.test( stylesheet ) && /\.builder-ph-draws \{[^}]*font-family: var\(--nino-admin-mono\);[^}]*opacity: \.5;/.test( stylesheet ) && stylesheet.indexOf( '.builder-ph-draws[hidden] {\n\tdisplay: none;' ) !== -1
	&& stylesheet.indexOf( '.builder-ph-label,\n.builder-ph-draws {\n\tdisplay: block;\n\toverflow: hidden;\n\ttext-overflow: ellipsis;\n\twhite-space: nowrap;' ) !== -1 );

console.log( '\nThe colours of a choice and the room in the frames' );

// A rule of the stylesheet by its selector, as the selector stands on a line of its own with nothing after it but the brace - the
// last of a list of selectors is that line as well, and its rule is the list's: the one that has the comma before it is passed over
const ruleOf = function( selector ) {

	for( let at = stylesheet.indexOf( '\n'+ selector+ ' {\n' ); at !== -1; at = stylesheet.indexOf( '\n'+ selector+ ' {\n', at + 1 ) )
		if( stylesheet.charAt( at - 1 ) !== ',' )
			return stylesheet.slice( at, stylesheet.indexOf( '\n}', at ) );

	return '';
};

check( 'what the Builder draws over the page is not the page\'s: the title of a head, each thing that is said of a frame and its tools stand on a chip - solid, in the colours of the workbench, whatever ground the preview paints under them - and every ground sets the ink that reads on it, a light one as well, so that a card of a light ground in a section of a black one is no white on white', ( function() {

	const chip = '.builder-head-title,\n.builder-head-status .builder-status-item,\n.builder-tools {\n\tborder: 1px solid var(--editor-border);\n\tborder-radius: var(--editor-radius-sm);\n\tbackground: var(--editor-bg-elevated);\n\tcolor: var(--editor-text);\n}';
	// Whether the rule a selector stands in - alone or in a list - sets a colour
	const sets = function( selector ) {

		const at = stylesheet.search( new RegExp( '\\n'+ selector.replace( /\./g, '\\.' )+ '(,| \\{)' ) );

		return at !== -1 && stylesheet.slice( stylesheet.indexOf( '{', at ), stylesheet.indexOf( '\n}', at ) ).indexOf( '\n\tcolor: ' ) !== -1;
	};
	const grounds = [ 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt', 'plain' ];

	return stylesheet.indexOf( chip ) !== -1 && ruleOf('.builder-pcol-head').indexOf('opacity') === -1 && ruleOf('.builder-card-head').indexOf('opacity') === -1
		&& grounds.every( function( ground ) { return sets( '.builder-card.builder-color-'+ ground ) && sets( '.builder-pcol.builder-color-'+ ground ) } );
} )() );
check( 'a dialog is never taller than the window and has one region that scrolls: it is a column of its head, its content and its actions, limited to the height of the window less a margin - the plain height first, the dynamic one after it for a browser that has it - and clipping, and the content is what may shrink and scroll where the form has no blocks - its rows as high as what is in them, so that no box in it is shrunk and cuts off the rest - so that the actions stay in sight', ( function() {

	const dialog = ruleOf('.builder-dialog').split('\n');
	const limits = dialog.filter( function( line ) { return /^\tmax-height: calc\(100d?vh - 2rem\);$/.test( line ) } );
	const lines = function( selector ) { return ruleOf( selector ).split('\n') };

	return limits.length === 2 && limits[0].indexOf('100vh') !== -1 && limits[1].indexOf('100dvh') !== -1 && dialog.indexOf( '\toverflow: hidden;' ) !== -1
		&& lines('.builder-dialog[open]').indexOf( '\tdisplay: flex;' ) !== -1 && lines('.builder-dialog[open]').indexOf( '\tflex-direction: column;' ) !== -1
		&& lines('.builder-dialog .nino-admin-dialog-body').indexOf( '\tmin-height: 0;' ) !== -1
		&& lines('.builder-dialog-content').indexOf( '\tflex: 1 1 auto;' ) !== -1 && lines('.builder-dialog-content').indexOf( '\tmin-height: 0;' ) !== -1 && lines('.builder-dialog-content').indexOf( '\toverflow: auto;' ) !== -1
		&& lines('.builder-dialog-content').indexOf( '\tgrid-auto-rows: max-content;' ) !== -1;
} )() );

// What a rule of the stylesheet says: whether it has every one of the declarations given, each as the line it is written on
const ruleHas = function( selector, declarations ) {

	const lines = ruleOf( selector ).split('\n');

	return lines.length > 1 && declarations.every( function( declaration ) { return lines.indexOf( '\t'+ declaration+ ';' ) !== -1 } );
};
// The width a dialog's rule gives it, in rem: the first number of its min()
const widthOf = function( selector ) {

	const found = /\twidth: min\(([0-9.]+)rem, calc\(100vw - 2rem\)\);/.exec( ruleOf( selector ) );

	return found === null ? NaN : Number( found[1] );
};

check( 'the dialog of a form is 40rem wide, the wide one of the lightbox and the editors stays 52rem, and the dialog of a source - 34rem - stands over the form smaller than it, as the label column of the fields stays what it was and the controls take the room', widthOf('.builder-dialog') === 40
	&& widthOf('.builder-dialog.is-wide') === 52 && widthOf('.builder-picker-dialog') === 34 && widthOf('.builder-picker-dialog') < widthOf('.builder-dialog') && widthOf('.builder-dialog') < widthOf('.builder-dialog.is-wide')
	&& ruleHas( '.builder-block-body', [ '--builder-label: 8.25rem' ] ) );
check( 'the content of a form of blocks is a column - the problems of a refused save over the accordion, each at the height it has - that keeps scrolling as a whole where nothing else gives way, and the accordion takes what is left of it, a column of its blocks that keeps its border and its rounded corners', ruleHas( '.builder-dialog-content:has(> .builder-blocks)', [ 'display: flex', 'flex-direction: column' ] )
	&& ruleHas( '.builder-dialog-content', [ 'flex: 1 1 auto', 'min-height: 0', 'overflow: auto', 'position: relative' ] )
	&& ruleHas( '.builder-blocks', [ 'display: flex', 'flex: 1 1 auto', 'flex-direction: column', 'border: 1px solid var(--editor-separator)', 'border-radius: var(--editor-radius-sm)', 'overflow: hidden' ] ) );
check( 'the blocks of the accordion keep their heads whatever the room is - each a column that does not grow, may be shrunk to nothing and so lets its body give way - and the open one grows: its body is what scrolls - auto, and not scroll, with the room of the bar kept and a floor under which it does not give way, its rows as high as what is in them - and is the box that the switches and the radios in it are placed in, while the bodies of the others stay hidden', ruleHas( '.builder-block', [ 'display: flex', 'flex: 0 0 auto', 'flex-direction: column', 'min-height: 0' ] )
	&& ruleHas( '.builder-block.is-open', [ 'flex: 1 1 auto' ] ) && ruleHas( '.builder-block.is-open > .builder-block-body', [ 'position: relative', 'flex: 1 1 auto', 'grid-auto-rows: max-content', 'align-content: start', 'min-height: var(--builder-floor)', 'overflow-y: auto', 'scrollbar-gutter: stable' ] )
	&& ruleHas( '.builder-block-body[hidden]', [ 'display: none' ] ) );
check( 'the accordion keeps the room for what it must not lose while a block is open - the head of every block and the floor of the open body, 2.4rem a head and 10rem a floor, the one head of the stylesheet for the rule of the head and for the sum - a head and a line for each block and one line more: the count has a rule for each number of blocks a form can have, which is as many as the table has different ones, and the box of the accordion asks for none of it while every block is closed - so that a window too low for it makes the content scroll as a whole', ( function() {

	const named = {};

	Object.keys( builder.BLOCKS ).forEach( function( kind ) { builder.BLOCKS[kind].forEach( function( block ) { named[block.id] = true } ) } );

	const counted = Array.from( { length : Object.keys( named ).length - 1 }, function( unused, at ) { return at + 2 } );

	return ruleHas( '.builder-blocks', [ '--builder-head: 2.4rem', '--builder-floor: 10rem', '--builder-count: 1' ] ) && ruleHas( '.builder-block-head', [ 'min-height: var(--builder-head)' ] )
		&& ruleHas( '.builder-blocks:has(> .is-open)', [ 'min-height: calc(var(--builder-count) * (var(--builder-head) + 1px) + 1px + var(--builder-floor))' ] )
		&& counted.every( function( count ) { return ruleHas( '.builder-blocks:has(> :nth-child('+ count+ '))', [ '--builder-count: '+ count ] ) } ) && /\tmin-height: (?!0;)/.test( ruleOf('.builder-blocks') ) === false;
} )() );
check( 'the options of a choice wear the colours of the knob strips of the Design feature: the elevated ground and the quiet ink, the ink of the text for one that is pointed at and not chosen - in a segment that takes a choice, not in a disabled one - and for the one that is chosen the blue and the ink of an active tab - without the ring and the soft blue it had', ( function() {

	const option = ruleOf('.builder-segment-option');
	const hover = ruleOf('.builder-segment:not(.is-disabled) .builder-segment-option:hover:not(:has(input:checked))');
	const chosen = ruleOf('.builder-segment-option:has(input:checked)');

	return option.indexOf( '\tbackground: var(--editor-bg-elevated);\n\tcolor: var(--editor-text-secondary);\n' ) !== -1 && hover === '\n.builder-segment:not(.is-disabled) .builder-segment-option:hover:not(:has(input:checked)) {\n\tcolor: var(--editor-text);'
		&& chosen === '\n.builder-segment-option:has(input:checked) {\n\tbackground: var(--editor-blue);\n\tcolor: var(--nino-admin-active-text);' && stylesheet.indexOf( 'editor-blue-soft);\n\tbox-shadow: inset' ) === -1;
} )() );
check( '...their sizes, their borders and the disabled rule are as they were, the focus is the outline it was, and on a chosen option the ring is the ink of an active tab inside the blue, so that the keyboard\'s focus is seen on the fill', ruleOf('.builder-segment-option').indexOf( '\tmin-width: 2.5rem;\n\theight: calc(var(--nino-admin-control-height) - 2px);\n\tpadding: 0 .7rem;\n\tborder-right: 1px solid var(--editor-border);\n' ) !== -1
	&& ruleOf('.builder-segment') === '\n.builder-segment {\n\tdisplay: flex;\n\twidth: fit-content;\n\tmax-width: 100%;\n\toverflow: hidden;\n\tborder: 1px solid var(--editor-border);\n\tborder-radius: var(--editor-radius-sm);'
	&& ruleOf('.builder-segment.is-disabled') === '\n.builder-segment.is-disabled {\n\topacity: .45;' && ruleOf('.builder-segment-option:has(input:focus-visible)') === '\n.builder-segment-option:has(input:focus-visible) {\n\toutline: 2px solid var(--editor-blue);\n\toutline-offset: -2px;'
	&& ruleOf('.builder-segment-option:has(input:checked:focus-visible)') === '\n.builder-segment-option:has(input:checked:focus-visible) {\n\toutline-color: var(--nino-admin-active-text);\n\toutline-offset: -4px;' );
check( 'every frame of the preview has .2rem more room on each side than it had, and the placeholder .5rem all round: the head of a section .45 .6 .45 .8, its body .7, the head of a column .3 .35 .3 .6, its body .5, the head of a card .25 .35 .25 .6, its body .5 - the gaps are as they were', [
	[ '.builder-frame-head', '.45rem .6rem .45rem .8rem' ], [ '.builder-frame-body', '.7rem' ], [ '.builder-pcol-head', '.3rem .35rem .3rem .6rem' ], [ '.builder-col-body', '.5rem' ],
	[ '.builder-card-head', '.25rem .35rem .25rem .6rem' ], [ '.builder-card-body', '.5rem' ], [ '.builder-ph', '.5rem' ],
].every( function( entry ) { return ruleOf( entry[0] ).split('\n').indexOf( '\tpadding: '+ entry[1]+ ';' ) !== -1 } )
	&& ruleOf('.builder-frame-body').indexOf( '\tgap: var(--builder-gap);\n' ) !== -1 && ruleOf('.builder-col-body').indexOf( '\tgap: .3rem;\n' ) !== -1 && ruleOf('.builder-card-body').indexOf( '\tgap: .3rem;\n' ) !== -1 && ruleOf('.builder-ph').indexOf( '\tgap: .4rem;\n' ) !== -1
	&& ruleOf('.builder-preview').split('\n').indexOf( '\tpadding: 1rem;' ) !== -1 );

console.log( '\nThe picture of a slot' );

const fileField = function( root ) { return root.querySelector('input[type="file"]') };
const thumbOf = function( root ) { return root.querySelector('.builder-picture-thumb') };
const noteOf = function( root ) { return root.querySelector('.builder-picture-message') };
const altLabel = 'Alt text (English (US))';
const house = { name : 'house.jpg', refused : false };
const choose = function( root, file ) {
	const input = fileField( root );
	input.files = [ file ];
	input.dispatch('change');
};
const actionsOf = function() { return requests.map( function( request ) { return request.action } ).join() };

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'image' );
const imageAt = [ 0, 0, 3 ];
const image = function() { return builder._get( builder._doc.model, imageAt ) };
builder._openSettings( imageAt );
changes = 0;
check( 'the content of a picture is its slot, and under it the picture: a thumbnail that is out of sight where the slot has none, the choice of a file that has the words for its label and the limits an upload runs into above it, what is done with the first picture, and the alt text', fileField( contentPane() ).getAttribute('aria-label') === 'Upload a picture'
	&& thumbOf( contentPane() ).hidden === true && contentPane().querySelector('.builder-picture-controls .builder-help-tip').textContent === 'The first picture makes the slot and is stored at once. At most 8 MB and 20 megapixels.'
	&& contentPane().querySelector('.builder-picture-controls small') === null && noteOf( contentPane() ).hidden === true );
check( '...the alt text is the one of the language the editor works in - at first the placeholder the picture was made with in that language, the label of the component -; a slot that is not there yet cannot take it, so it waits in the mark that makes the slot and is said so', valueAt( contentPane(), altLabel ) === labelIn( 'image', 'en_US' )
	&& controlOf( contentPane(), altLabel ).el.disabled === false && tipOf( controlOf( contentPane(), altLabel ) ) === 'Stored with the slot as soon as it is made.' );
change( contentPane(), altLabel, 'A house' );
check( '...what is typed goes into the mark of the slot, under the language, as a change - and nothing is sent - and the other language keeps its placeholder', JSON.stringify( image().create ) === JSON.stringify( { label : 'Image', width : 1600, height : 900, alt : { en_US : 'A house', de_DE : labelIn( 'image', 'de_DE' ) } } ) && changes === 1 && requests.length === 0 );
builder._closeDialog();
builder._setLocale('de_DE');
builder._openSettings( imageAt );
check( '...in another language it is another text, at first the placeholder of that language; typed, it joins the first - and emptied, it is gone again', valueAt( contentPane(), 'Alt text (Deutsch)' ) === labelIn( 'image', 'de_DE' ) && ( change( contentPane(), 'Alt text (Deutsch)', 'Ein Haus' ), JSON.stringify( image().create.alt ) === '{"en_US":"A house","de_DE":"Ein Haus"}' )
	&& ( change( contentPane(), 'Alt text (Deutsch)', '' ), JSON.stringify( image().create.alt ) === '{"en_US":"A house"}' ) && ( change( contentPane(), 'Alt text (Deutsch)', 'Ein Haus' ), true ) );
builder._closeDialog();
builder._setLocale('en_US');
builder._openSettings( imageAt );
changes = 0;
requests = [];
choose( contentPane(), house );
check( 'the first picture of a slot that is not there makes the slot at once, with the label and the size of its mark - the file is looked at first, as the Images panel looks at it - and the field is off while it is on its way', checked.length === 1 && checked[0] === house && actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/hero/image","label":"Image","width":1600,"height":900}' && fileField( contentPane() ).disabled === true && noteOf( contentPane() ).textContent === 'Storing the picture ...' );
requests[0].callback( 200, { ok : true, uri : '/template/page-home/hero/image' } );
check( '...the slot is there, so the source is the slot and no mark is left to make it - the document is told - and the picture of the Images panel\'s own upload follows, with the file', image().create === undefined && image().source === '/template/page-home/hero/image' && changes === 1
	&& builder._slotOf( '/template/page-home/hero/image' ) !== null && builder._slotOf( '/template/page-home/hero/image' ).hasImage === false && actionsOf() === 'slots/create,images/upload'
	&& JSON.stringify( requests[1].payload ) === '{"uri":"/template/page-home/hero/image"}' && requests[1].extra.file === house );
requests[1].callback( 200, { filename : 'hero/image.1600x900.jpg', url : '/uploads/image.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...the alt text that was waiting is written once the picture is stored, to the slot that is there now', actionsOf() === 'slots/create,images/upload,images/alt' && JSON.stringify( requests[2].payload ) === '{"uri":"/template/page-home/hero/image","alt":{"en_US":"A house","de_DE":"Ein Haus"}}' );
requests[2].callback( 200, { alt : { en_US : 'A house', de_DE : 'Ein Haus' } } );
check( '...and the form shows it: the thumbnail, a note that the picture is stored, the alt text of the slot with the hint that it is stored at once - and the preview has the picture in the placeholder of the component', thumbOf( contentPane() ).hidden === false && thumbOf( contentPane() ).src.indexOf( '/uploads/image.jpg?t=' ) === 0
	&& noteOf( contentPane() ).textContent === 'The picture is stored.' && noteOf( contentPane() ).classList.contains('is-error') === false && fileField( contentPane() ).disabled === false && valueAt( contentPane(), altLabel ) === 'A house'
	&& tipOf( controlOf( contentPane(), altLabel ) ) === 'Stored at once, with the slot.' && preview().querySelector('[data-path="0.0.3"] .builder-ph-picture').src.indexOf( '/uploads/image.jpg' ) === 0
	&& builder._slotOf( '/template/page-home/hero/image' ).hasImage === true && builder._altOf( builder._slotOf( '/template/page-home/hero/image' ) ).de_DE === 'Ein Haus' );

choose( contentPane(), house );
check( 'a picture for a slot that is there is the upload alone: no slot is made, and nothing is written that nobody typed', actionsOf() === 'slots/create,images/upload,images/alt,images/upload' && requests[3].extra.file === house );
requests[3].callback( 200, { filename : 'hero/image.1600x900.jpg', url : '/uploads/image.jpg', source : { width : 800, height : 600 }, belowTarget : true } );
check( '...a picture smaller than the slot is stored all the same, and the form says that it is scaled up - as a warning in words, which is no error: the picture is stored', noteOf( contentPane() ).textContent === 'The picture is smaller than the slot (800 × 600 px) and is scaled up.'
	&& noteOf( contentPane() ).classList.contains('is-warning') === true && noteOf( contentPane() ).classList.contains('is-error') === false && actionsOf() === 'slots/create,images/upload,images/alt,images/upload' );

doc = open();
builder._renderEditor();
builder._doc.model.blocks[0].cols[0].components.push( { name : 'image', source : '/template/page-home/hero/background', text : null, attributes : registry().components.image.defaults } );
builder._openSettings( [ 0, 0, 3 ] );
check( 'the slot that is there shows its picture and the alt text it holds in the language of the editor - none in English, the German one in German', thumbOf( contentPane() ).hidden === false && thumbOf( contentPane() ).src === '/uploads/hero.1600x900.jpg' && valueAt( contentPane(), altLabel ) === ''
	&& ( builder._closeDialog(), builder._setLocale('de_DE'), builder._openSettings( [ 0, 0, 3 ] ), valueAt( contentPane(), 'Alt text (Deutsch)' ) === 'Ein Held' ) );
builder._closeDialog();
builder._setLocale('en_US');
builder._openSettings( [ 0, 0, 3 ] );
changes = 0;
requests = [];
const unsavedBefore = builder._unsaved( builder._doc );
change( contentPane(), altLabel, 'A hero' );
check( 'an alt text for a slot that is there is written at once, through the Images panel: that language alone, the others as they are - and the document has nothing to save for it', actionsOf() === 'images/alt' && JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/hero/background","alt":{"en_US":"A hero"}}'
	&& changes === 0 && builder._unsaved( builder._doc ) === unsavedBefore && builder._doc.model.blocks[0].cols[0].components[3].create === undefined );
requests[0].callback( 200, { alt : { de_DE : 'Ein Held', en_US : 'A hero' } } );
check( '...the slot has it from then on, and the form says so', builder._altOf( builder._slotOf( '/template/page-home/hero/background' ) ).en_US === 'A hero' && noteOf( contentPane() ).textContent === 'The alternative text is stored.' );
change( contentPane(), altLabel, 'A hero' );
check( '...an alt text that is the one the slot has is not written again', requests.length === 1 );
change( contentPane(), altLabel, 'A hero!' );
requests[1].callback( 403, { code : 'permission' } );
check( '...one that cannot be written says why where the form says it, and is an error', noteOf( contentPane() ).textContent === '(403) /_admin/builder/error/alt' && noteOf( contentPane() ).classList.contains('is-error') === true );
requests = [];
choose( contentPane(), house );
check( 'a picture for a slot that is there is stored at its own uri: the upload alone', actionsOf() === 'images/upload' && requests[0].payload.uri === '/template/page-home/hero/background' && requests[0].extra.file === house );
requests[0].callback( 200, { filename : 'f', url : '/uploads/hero.1600x900.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...the picture of a slot that is replaced is told to the browser as another, the name it is stored under being the same: the url the form keeps has a stamp, the one the registry names for the picker has not been touched', thumbOf( contentPane() ).src.indexOf( '/uploads/hero.1600x900.jpg?t=' ) === 0 );

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'image' );
builder._openSettings( imageAt );
requests = [];
changes = 0;
house.refused = true;
choose( contentPane(), house );
check( 'a picture the check of the workbench refuses - too large -, is refused in words and goes nowhere: no slot is made, nothing is sent, the form is on again', requests.length === 0 && noteOf( contentPane() ).textContent === '(400) image_too_large' && noteOf( contentPane() ).classList.contains('is-error') === true
	&& fileField( contentPane() ).disabled === false && image().create !== undefined );
house.refused = false;
choose( contentPane(), house );
requests[0].callback( 500, null );
check( 'a slot that cannot be made stops it where it is: the error is said, the mark that makes the slot is still there, nothing is uploaded', actionsOf() === 'slots/create' && noteOf( contentPane() ).textContent === '(500) /_admin/builder/error/make-slot' && image().create !== undefined && builder._slotOf( image().source ) === null && changes === 0 );
choose( contentPane(), house );
requests[1].callback( 409, { code : 'keys_exists' } );
check( 'a slot that is there already - another request made it in the meantime - is the one to use: the upload goes on, the mark is let go of', image().create === undefined && actionsOf() === 'slots/create,slots/create,images/upload' && builder._slotOf( image().source ) !== null );
requests[2].callback( 400, { code : 'image_unreadable' } );
requests[3].callback( 200, { alt : { en_US : labelIn( 'image', 'en_US' ), de_DE : labelIn( 'image', 'de_DE' ) } } );
check( 'a picture that cannot be stored says so - the code the Images panel gives is the workbench\'s to say - and the slot stays, without a picture: the source is the slot', noteOf( contentPane() ).textContent === '(400) /_admin/builder/error/upload' && noteOf( contentPane() ).classList.contains('is-error') === true
	&& builder._slotOf( image().source ).hasImage === false && thumbOf( contentPane() ).hidden === true );

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'image' );
builder._openSettings( imageAt );
requests = [];
choose( contentPane(), house );
builder._closeDialog();
let thrown = null;
try {
	requests[0].callback( 200, { ok : true } );
	requests[1].callback( 200, { filename : 'f', url : '/uploads/image.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
} catch( error ) {
	thrown = error;
}
check( 'a dialog that is closed while a picture is on its way takes it all the same: the model and the preview have it, and nothing is drawn into a form that is gone', thrown === null && image().create === undefined && builder._slotOf( image().source ).hasImage === true
	&& preview().querySelector('[data-path="0.0.3"] .builder-ph-picture').src.indexOf( '/uploads/image.jpg' ) === 0 && dialogBox().open === false );

console.log( '\nThe picture behind a section' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1 ] );
builder._openBlock('surface');
check( 'the picture behind a section has the same under its source: the upload and the alt text - and where the section has none the alt text has no slot to wait in, and says that a picture comes first', fileField( dialogContent() ) !== null && thumbOf( dialogContent() ).hidden === true
	&& controlOf( dialogContent(), altLabel ).el.disabled === true && tipOf( controlOf( dialogContent(), altLabel ) ) === 'Upload a picture first, or choose a slot with the Source button.' );
requests = [];
changes = 0;
choose( dialogContent(), house );
check( '...a picture chosen for a section that has none makes a slot of the kind of a new one - the name background, free in the section, the size it starts with, and for its label the name of the section and the word for a background - and the background is that slot, without a mark', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/background","label":"services: Background","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the section has the picture behind it, with no focus until it is chosen and nothing to make', JSON.stringify( builder._doc.model.blocks[1].background ) === '{"slot":"/template/page-home/services/background","focus":null}' && changes === 1 && actionsOf() === 'slots/create,images/upload' );
requests[1].callback( 200, { filename : 'f', url : '/uploads/services.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the surface is drawn again with it: the thumbnail, the focus and the button that takes the picture away are on, the alt text can be written - and the preview says that a picture is behind the section', thumbOf( dialogContent() ).hidden === false && thumbOf( dialogContent() ).src.indexOf( '/uploads/services.jpg' ) === 0
	&& controlOf( dialogContent(), 'Automatic focus' ).el.disabled === false && buttonOf( dialogContent(), 'Remove the picture' ).disabled === false && controlOf( dialogContent(), altLabel ).el.disabled === false && noteOf( dialogContent() ).textContent === 'The picture is stored.'
	&& preview().children[1].querySelector('.is-background') !== null && openBlock() === 'surface' );
builder._closeDialog();
builder._doc.model.blocks[1].id = '2col';
builder._openSettings( [ 1 ] );
builder._openBlock('surface');
builder._doc.model.blocks[1].background = null;
redrawBlocks();
requests = [];
choose( dialogContent(), house );
check( 'a section whose id starts with a digit cannot have a slot made of its name: it is told so, as the form of a new slot tells it, and nothing is sent', requests.length === 0 && noteOf( dialogContent() ).textContent === words['/_admin/builder/source/name-slot'] && noteOf( dialogContent() ).classList.contains('is-error') );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
builder._openBlock('surface');
check( 'the slot a section has shows its picture and the alt text it holds', thumbOf( dialogContent() ).src === '/uploads/hero.1600x900.jpg' && valueAt( dialogContent(), altLabel ) === '' );
builder._doc.model.blocks[0].background = { slot : '/template/page-home/hero/background-2', focus : 5, create : { label : 'Background', width : 800, height : 400, alt : { en_US : 'Waves' } } };
redrawBlocks();
check( '...one that is made when the document is saved holds the alt text in its mark', valueAt( dialogContent(), altLabel ) === 'Waves' && ( change( dialogContent(), altLabel, 'More waves' ), builder._doc.model.blocks[0].background.create.alt.en_US === 'More waves' ) && changes > 0 );
requests = [];
choose( dialogContent(), house );
requests[0].callback( 200, { ok : true } );
check( '...it is made with the label and the size of the mark, and the alt text it holds goes to it once the picture is stored', JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/hero/background-2","label":"Background","width":800,"height":400}' && builder._doc.model.blocks[0].background.create === undefined
	&& builder._doc.model.blocks[0].background.focus === 5 );
requests[1].callback( 200, { filename : 'f', url : '/uploads/b2.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...(the focus it has stays)', actionsOf() === 'slots/create,images/upload,images/alt' && JSON.stringify( requests[2].payload ) === '{"uri":"/template/page-home/hero/background-2","alt":{"en_US":"More waves"}}' );
builder._closeDialog();

console.log( '\nThe picture behind a column' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
const columnAt = function( at ) { return builder._doc.model.blocks[1].cols[at] };
check( 'the picture behind a column has what the one behind a section has under its source: the upload and the alt text - which has no slot to wait in while there is no picture, and says that a picture comes first - and not whether the picture is a parallax, which is the section\'s alone', fileField( dialogContent() ) !== null && thumbOf( dialogContent() ).hidden === true
	&& controlOf( dialogContent(), altLabel ).el.disabled === true && tipOf( controlOf( dialogContent(), altLabel ) ) === 'Upload a picture first, or choose a slot with the Source button.'
	&& dialogContent().querySelector('.builder-source-current').textContent === 'none' && labelList( dialogContent() ).indexOf('Parallax effect') === -1 );
requests = [];
changes = 0;
choose( dialogContent(), house );
check( '...a picture chosen for a column that has none makes a slot of the kind of a new one - the name column-background, free in the section, the size it starts with, and for its label the name of the section, the number of the column and the word for a background', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background","label":"services: Column 1: background","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the background of the column is that slot, with no focus until it is chosen and nothing to make - the section has none of its own', JSON.stringify( columnAt( 0 ).background ) === '{"slot":"/template/page-home/services/column-background","focus":null}' && builder._doc.model.blocks[1].background === null && changes === 1 && actionsOf() === 'slots/create,images/upload' );
requests[1].callback( 200, { filename : 'f', url : '/uploads/column.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the surface is drawn again with it: the thumbnail, the focus, the button that takes the picture away, the alt text and the dimming are on - and the preview says that a picture is behind the column, not behind the section', thumbOf( dialogContent() ).hidden === false && thumbOf( dialogContent() ).src.indexOf( '/uploads/column.jpg' ) === 0
	&& controlOf( dialogContent(), 'Automatic focus' ).el.disabled === false && buttonOf( dialogContent(), 'Remove the picture' ).disabled === false && controlOf( dialogContent(), altLabel ).el.disabled === false && controlOf( dialogContent(), 'Dim the picture' ).el.disabled === false
	&& noteOf( dialogContent() ).textContent === 'The picture is stored.' && preview().querySelector('[data-path="1.0"] .is-background') !== null && preview().children[1].querySelector('.builder-frame-head .is-background') === null && openBlock() === 'surface' );
builder._closeDialog();
builder._openSettings( [ 1, 1 ] );
builder._openBlock('surface');
requests = [];
choose( dialogContent(), house );
check( 'the picture behind the next column of the section takes the next name, and the number of the column is in its label', JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background-2","label":"services: Column 2: background","width":1600,"height":900}' );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
columnAt( 0 ).background = { slot : '/template/page-home/services/column-background-2', focus : 5, create : { label : 'services: Column 1: background', width : 800, height : 400, alt : { en_US : 'Waves' } } };
redrawBlocks();
check( 'a picture that is made when the document is saved holds its alt text in its mark, as the one behind a section does', valueAt( dialogContent(), altLabel ) === 'Waves' && ( change( dialogContent(), altLabel, 'More waves' ), columnAt( 0 ).background.create.alt.en_US === 'More waves' ) );
requests = [];
choose( dialogContent(), house );
requests[0].callback( 200, { ok : true } );
check( '...it is made with the label and the size of the mark, the focus stays, and the alt text goes to the slot once the picture is stored', JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background-2","label":"services: Column 1: background","width":800,"height":400}' && columnAt( 0 ).background.create === undefined && columnAt( 0 ).background.focus === 5 );
requests[1].callback( 200, { filename : 'f', url : '/uploads/c2.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...(the alt text is written for the slot of the column)', actionsOf() === 'slots/create,images/upload,images/alt' && JSON.stringify( requests[2].payload ) === '{"uri":"/template/page-home/services/column-background-2","alt":{"en_US":"More waves"}}' );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
buttonOf( colGround(), 'Source' ).click();
check( 'the picture behind a column is chosen in the dialog of the section\'s, with the tabs of a picture: Choose a value and Create new - no Static', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ) ) === '[["pick","Choose a value"],["new","Create new"]]' && pickerPane('pick').querySelector('.builder-label').textContent === 'Image slot'
	&& pickerPane('pick').querySelector('.builder-source-current').textContent === 'none' );
buttonOf( pickerStrip(), 'Create new' ).click();
check( '...a new slot starts with the name column-background, free in the section, and the size of a new slot - the same name numbered where the section has it already', fresh('.builder-new-title').textContent === 'New image slot' && fresh('.builder-new-name').value === 'column-background' && fresh('.builder-new-uri').textContent === '/template/page-home/services/column-background'
	&& fresh('.builder-new-width').value === '1600' && fresh('.builder-new-height').value === '900' );
fresh('.builder-new-use').click();
check( '...using it makes the slot the picture of the column, with what it is made with: the label that names the column - and no focus until it is chosen', JSON.stringify( groundOf().background ) === '{"slot":"/template/page-home/services/column-background","focus":null,"create":{"label":"Column 1: background","width":1600,"height":900}}'
	&& pickerBox().open === false && colGround().querySelector('.builder-source-current').textContent === '/template/page-home/services/column-background' && builder._doc.model.blocks[1].background === null && preview().querySelector('[data-path="1.0"] .is-background') !== null );
builder._closeDialog();
builder._openSettings( [ 1, 1 ] );
builder._openBlock('surface');
buttonOf( colGround(), 'Source' ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
check( '...the next column of the section takes the next name, whichever column has the first', fresh('.builder-new-name').value === 'column-background-2' && fresh('.builder-new-uri').textContent === '/template/page-home/services/column-background-2' );
screen.getElementById('builder-picker-close').click();
builder._closeDialog();

console.log( '\nA document that carries texts and slots' );

doc = open();
builder._renderEditor();
heroTitle().values = { de_DE : 'Hallo' };
builder._doc.model.blocks[0].cols[0].components.push( { name : 'image', source : '/template/page-home/hero/photo', text : null, attributes : registry().components.image.defaults, create : { label : 'Photo', width : 800, height : 400, alt : { en_US : 'Photo' } } } );
check( 'the texts typed for the keys and the alt texts of the slots to be made are in the model, so the request of a save carries them - and the document is unsaved for them', builder._unsaved( builder._doc ) === true
	&& builder._saveRequest( builder._doc, false ).model.blocks[0].cols[0].components[0].values.de_DE === 'Hallo' && builder._saveRequest( builder._doc, false ).model.blocks[0].cols[0].components[3].create.alt.en_US === 'Photo' );
builder._save();
const sentModel = requests[0].payload.model;
const savedBack = JSON.parse( JSON.stringify( page() ) );
requests[0].callback( 200, { model : savedBack, hash : 'h2' } );
check( 'a save that is answered puts the model the server read in its place - which has none of the texts, they are the keys\' now - and asks for the keys of the project and the slots again, since it wrote some and made others', sentModel.blocks[0].cols[0].components[0].values.de_DE === 'Hallo'
	&& builder._doc.model.blocks[0].cols[0].components[0].values === undefined && builder._unsaved( builder._doc ) === false && actionsOf() === 'builder/save,text/keys,builder/registry' );
builder._setLocale('de_DE');
const newKeys = known();
newKeys[0].values.de_DE = 'Hallo';
requests[1].callback( 200, { keys : newKeys } );
const newRegistry = registry();
newRegistry.slots.push( { uri : '/template/page-home/hero/photo', label : 'Photo', width : 800, height : 400, hasImage : false, url : null, alt : { en_US : 'Photo' } } );
requests[2].callback( 200, newRegistry );
check( '...the preview says the text that was written, and the slots are those of the project now, the one that was made among them', lines()[0] === 'Hallo' && builder._slotOf( '/template/page-home/hero/photo' ) !== null && builder._altOf( builder._slotOf( '/template/page-home/hero/photo' ) ).en_US === 'Photo' );
builder._setLocale('en_US');

// An account without the permission of the Text panel is refused the keys with a 403 (the kernel's guardPerm), it is not answered an empty list - and only an answer of 200 replaces the keys the panel has
builder._keysAsked = false;
builder._loadKeys();
const refusedKeys = requests[requests.length - 1];
refusedKeys.callback( 403, { error : 'not allowed' } );
builder._openSettings( [ 0, 0, 0 ] );
check( '...asked for again, the keys are refused to an account that may not read them any more (403): the ones it has stay, a refusal takes none of them away - so the preview still says the texts it read, and the field under the source is drawn from them',
	refusedKeys.action === 'text/keys' && JSON.stringify( builder._keys ) === JSON.stringify( newKeys ) && lines()[0] === 'Welcome' && labelsIn( contentPane() ) === lineLabel && controlsIn( contentPane() )[0].el.value === 'Welcome' );
builder._closeDialog();

builder._keys = null;
builder._keysAsked = false;
builder._keysAnswered = false;
builder._loadKeys();
requests[requests.length - 1].callback( 403, { error : 'not allowed' } );
builder._openSettings( [ 0, 0, 0 ] );
check( '...an account that never could read them is refused the first time (403) and has none: the keys are an empty list that has been answered, the preview says the label of the component, and there is no text under the source',
	JSON.stringify( builder._keys ) === '[]' && builder._keysAnswered === true && lines()[0] === 'Title (Section)' && dialogContent().children.length === 1 );
builder._closeDialog();

doc = open();
heroTitle().values = { en_US : 'Typed' };
registered.discard();
check( 'the shell that drops what is unsaved drops the texts with it: they are part of the document and of nothing else', heroTitle().values === undefined && registered.isDirty() === false );

check( 'a copy of a component has the texts of the original: the key of the copy is new, made with the text the original has in each language, and the texts typed for the original are typed for the copy', ( function() {
	const model = page();
	model.blocks[0].cols[0].components[0].values = { de_DE : 'Hallo' };
	builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return { en_US : 'Welcome', de_DE : 'Willkommen' } } );
	const copy = model.blocks[0].cols[0].components[1];
	return copy.source === '/template/page-home/hero/title-2' && JSON.stringify( copy.create ) === '{}' && JSON.stringify( copy.values ) === '{"en_US":"Welcome","de_DE":"Hallo"}' && copy.values !== model.blocks[0].cols[0].components[0].values;
} )() );
check( 'a section that is edited as HTML+ and read again keeps the texts typed for its keys, with the instructions to make them', ( function() {
	const old = page().blocks[0];
	old.cols[0].components[0].values = { de_DE : 'Hallo' };
	const again = page().blocks[0];
	builder._carry( old, again );
	return JSON.stringify( again.cols[0].components[0].values ) === '{"de_DE":"Hallo"}' && again.cols[0].components[1].values === undefined && again.cols[0].components[0].values !== old.cols[0].components[0].values;
} )() );
check( '...and the picture behind a column that is still to be made, where the same slot is behind the column when it is read again - a slot that is another one is left alone', ( function() {

	const old = page().blocks[1];
	const again = page().blocks[1];

	old.cols[0].background = { slot : '/template/page-home/services/column-background', focus : 5, create : { label : 'services: Column 1: background', width : 800, height : 400 } };
	old.cols[1].background = { slot : '/template/page-home/services/column-background-2', focus : null, create : { label : 'services: Column 2: background', width : 1600, height : 900 } };
	again.cols[0].background = { slot : '/template/page-home/services/column-background', focus : 5 };
	again.cols[1].background = { slot : '/template/page-home/services/other', focus : null };
	builder._carry( old, again );

	return JSON.stringify( again.cols[0].background.create ) === JSON.stringify( old.cols[0].background.create ) && again.cols[0].background.create !== old.cols[0].background.create && again.cols[1].background.create === undefined;
} )() );

console.log( '\nA save that stopped' );

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'image' );
builder._openSettings( imageAt );
change( contentPane(), altLabel, 'A house' );
builder._closeDialog();
requests = [];
builder._save();
requests[0].callback( 500, { code : 'builder_write', error : 'x' } );
check( 'a save that stopped on the way - the file could not be written, a panel could not make a slot - asks for the slots of the registry again: it may have made those its marks name, and it keeps them', actionsOf() === 'builder/save,builder/registry'
	&& builder._status.state === 'error' && builder._unsaved( builder._doc ) === true && image().create !== undefined && builder._slotOf( image().source ) === null );
const stopped = registry();
stopped.slots.push( { uri : '/template/page-home/hero/image', label : 'Image', width : 1600, height : 900, hasImage : false, url : null, alt : { en_US : 'A house' } } );
requests[1].callback( 200, stopped );
builder._openSettings( imageAt );
const shown = valueAt( contentPane(), altLabel );
requests = [];
changes = 0;
change( contentPane(), altLabel, 'A big house' );
check( '...the slot is there for the editor then: its alt text is the one the save wrote, and one typed next goes to the slot at once - not into the mark, which the retry of the save passes over because the slot is there, and the text would be lost',
	builder._slotOf( image().source ) !== null && shown === 'A house' && actionsOf() === 'images/alt' && JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/hero/image","alt":{"en_US":"A big house"}}' && image().create.alt.en_US === 'A house' && changes === 0 );
builder._closeDialog();

doc = open();
builder._doc.model.name = 'Start';
requests = [];
builder._save();
requests[0].callback( 400, { code : 'builder_invalid', error : 'x', params : [ 'the name is bad' ] } );
builder._save();
requests[1].callback( 409, { code : 'builder_conflict', error : 'x' } );
check( '...a save the server refused before it made anything - a model that is not valid, a file that changed - asks for nothing', actionsOf() === 'builder/save,builder/save' );

console.log( '\nA section made here keeps the picture that was stored for it' );

doc = open();
builder._renderEditor();
builder._addSection();
const sectionAt = builder._doc.model.blocks.length - 1;
builder._addComponent( [ sectionAt, 0 ], 'image' );
builder._addComponent( [ sectionAt, 0 ], 'title' );
builder._doc.model.blocks[sectionAt].background = { slot : '/template/page-home/section/background', focus : null, create : { label : 'Background', width : 1600, height : 900 } };
builder._registry.slots.push( { uri : '/template/page-home/section/image', label : 'Image', width : 1600, height : 900, hasImage : true, url : '/uploads/i.jpg', alt : {} } );
builder._rename( [ sectionAt ], 'about' );
check( 'a section made here is renamed by writing its sources new, since nothing of it was made - but for a slot that is there already: a picture was stored for it, and it stays what the source names, whatever the section is called', builder._doc.model.blocks[sectionAt].id === 'about'
	&& builder._doc.model.blocks[sectionAt].cols[0].components[0].source === '/template/page-home/section/image' && builder._doc.model.blocks[sectionAt].cols[0].components[1].source === '/template/page-home/about/title'
	&& builder._doc.model.blocks[sectionAt].background.slot === '/template/page-home/about/background' && builder._doc.model.blocks[sectionAt].renamedFrom === undefined && builder._fresh.about === true );
doc = open();
builder._renderEditor();
builder._addSection();
const freshAt = builder._doc.model.blocks.length - 1;
builder._addCol( [ freshAt ] );
builder._doc.model.blocks[freshAt].cols[0].background = { slot : '/template/page-home/section/column-background', focus : null, create : { label : 'section: Column 1: background', width : 1600, height : 900 } };
builder._doc.model.blocks[freshAt].cols[1].background = { slot : '/template/page-home/section/column-picture', focus : 5 };
builder._registry.slots.push( { uri : '/template/page-home/section/column-picture', label : 'Picture', width : 1600, height : 900, hasImage : true, url : '/uploads/c.jpg', alt : {} } );
builder._rename( [ freshAt ], 'team' );
check( 'the picture behind a column of a section made here goes with the new name of the section, as the picture behind the section does - but not where a picture was stored for the slot', builder._doc.model.blocks[freshAt].id === 'team' && builder._doc.model.blocks[freshAt].cols[0].background.slot === '/template/page-home/team/column-background'
	&& builder._doc.model.blocks[freshAt].cols[1].background.slot === '/template/page-home/section/column-picture' && builder._doc.model.blocks[freshAt].renamedFrom === undefined );
doc = open();
builder._renderEditor();
builder._doc.model.blocks[1].cols[0].background = { slot : '/template/page-home/services/column-background', focus : null };
builder._rename( [ 1 ], 'offer' );
check( '...in a section that was saved nothing is moved here: the server moves what it has under the old name, and the sources stay as they are', builder._doc.model.blocks[1].id === 'offer' && builder._doc.model.blocks[1].renamedFrom === 'services' && builder._doc.model.blocks[1].cols[0].background.slot === '/template/page-home/services/column-background' );

delete sandbox.Nino.admin.htmlEditor;

console.log( '\nA component that holds components' );

// The page of the card fixture, as the editor has it: a card beside a picture, and a loop whose cell holds a card
const cardPage = function() { return builder._normalise( fixture('page-home-card.json') ) };
const cardAt = [ 0, 1, 0 ];
const cardIn = function( model ) { return builder._get( model, cardAt ) };
// ...opened in the editor, as the file it was read from
const openCard = function() {
	const opened = open( cardPage() );
	opened.file = 'page-home-card';
	return opened;
};

check( 'a component whose schema has "holds" is a container, and the place it holds is read from the registry - the script names no component: the card of the kernel holds the place card, every other component holds none', builder._holds( registry(), 'card' ) === 'card'
	&& builder._holds( registry(), 'text' ) === '' && builder._holds( registry(), 'nothing-like-it' ) === '' && ( function() {
		const own = registry();
		own.components.stub = { label : 'Stub', source : 'content', preview : 'block', holds : 'tile' };
		own.components.broken = { label : 'Broken', source : 'content', preview : 'block', holds : 5 };
		return builder._holds( own, 'stub' ) === 'tile' && builder._holds( own, 'broken' ) === '';
	} )() );

const cardModel = cardPage();
check( 'a path into a container is one deeper - block, column, component, child: the child is a node, the container is one, and a path that leads nowhere is null', builder._get( cardModel, [ 0, 1, 0, 1 ] ).name === 'text' && builder._get( cardModel, cardAt ).name === 'card'
	&& builder._get( cardModel, [ 0, 1, 0, 3 ] ) === null && builder._get( cardModel, [ 0, 1, 0, 0, 0 ] ) === null && builder._get( cardModel, [ 0, 0, 0, 0 ] ) === null && builder._get( cardModel, [ 1, 0, 'x', 0 ] ) === null
	&& builder._get( cardModel, [ 1, 0, 'x' ] ) === cardModel.blocks[1].cols[0].stack && builder._get( cardModel, [ 1, 0, 0, 0 ] ).source === 'title' );
check( '...a child stands in the list of its container, a container in the list of its column; both are components, and a container holds its children as work', builder._place( cardModel, [ 0, 1, 0, 2 ] ).list === cardIn( cardModel ).components && builder._place( cardModel, [ 0, 1, 0, 2 ] ).index === 2
	&& builder._place( cardModel, cardAt ).list === cardModel.blocks[0].cols[1].components && builder._place( cardModel, [ 0, 1, 0, 9 ] ) === null && builder._kind( cardModel, [ 0, 1, 0, 2 ] ) === 'component' && builder._kind( cardModel, cardAt ) === 'component'
	&& builder._children( cardModel, cardAt ) === 3 && builder._children( cardModel, [ 0, 1, 0, 2 ] ) === 0 && builder._children( cardModel, [ 0, 0, 0 ] ) === 0 );

const movable = cardPage();
check( 'a child is moved within the list of its container and nowhere else: not to another level, not to another container, and a component does not enter a container by moving', ( function() {

	const holder = builder._get( movable, cardAt );
	const second = JSON.parse( JSON.stringify( holder ) );
	const names = function() { return holder.components.map( function( child ) { return child.name } ).join() };

	movable.blocks[0].cols[1].components.push( second );

	const before = names();
	const moved = builder._move( movable, [ 0, 1, 0, 0 ], [ 0, 1, 0, 2 ] );
	const after = names();
	const refused = [ builder._move( movable, [ 0, 1, 0, 0 ], [ 0, 1, 1, 0 ] ), builder._move( movable, [ 0, 1, 0, 1 ], [ 0, 1, 1, 2 ] ), builder._move( movable, [ 0, 1, 0, 0 ], [ 0, 1, 1 ] ), builder._move( movable, [ 0, 1, 1 ], [ 0, 1, 0, 1 ] ), builder._move( movable, [ 0, 1, 0, 0 ], [ 0, 1, 0, 7 ] ) ];

	return before === 'card-title,text,button' && moved === true && after === 'text,button,card-title' && refused.every( function( answer ) { return answer === false } ) && names() === after && second.components.map( function( child ) { return child.name } ).join() === before;
} )() );
check( '...a step is one place up or down in the same list: the path of the child follows it, and the first has no step up as the last has none down', ( function() {

	const model = cardPage();

	return JSON.stringify( builder._step( model, [ 0, 1, 0, 1 ], -1 ) ) === '[0,1,0,0]' && builder._get( model, [ 0, 1, 0, 0 ] ).name === 'text' && builder._step( model, [ 0, 1, 0, 0 ], -1 ) === null && builder._step( model, [ 0, 1, 0, 2 ], 1 ) === null
		&& JSON.stringify( builder._step( model, [ 0, 1, 0, 0 ], 1 ) ) === '[0,1,0,1]';
} )() );
check( '...the tools of a child are those of a component: settings, up and down where it has a neighbour, duplicate and delete - and the container has its own place in its column', ( function() {

	const model = cardPage();
	const tools = function( path ) { return JSON.stringify( builder._toolbar( model, path ) ) };

	return tools( [ 0, 1, 0, 0 ] ) === '{"settings":true,"html":null,"up":false,"down":true,"duplicate":true,"delete":true}' && tools( [ 0, 1, 0, 2 ] ) === '{"settings":true,"html":null,"up":true,"down":false,"duplicate":true,"delete":true}'
		&& tools( [ 0, 1, 0, 1 ] ) === '{"settings":true,"html":null,"up":true,"down":true,"duplicate":true,"delete":true}' && tools( cardAt ) === '{"settings":true,"html":null,"up":false,"down":false,"duplicate":true,"delete":true}';
} )() );
check( '...a child is taken out of its container, a container with its children out of its column - and the first is the end of the level it stood in', ( function() {

	const model = cardPage();
	const removed = builder._remove( model, [ 0, 1, 0, 1 ] );
	const left = builder._get( model, cardAt ).components.map( function( child ) { return child.name } ).join();
	const gone = builder._remove( model, cardAt );

	return removed === true && left === 'card-title,button' && gone === true && model.blocks[0].cols[1].components.length === 0 && builder._remove( model, [ 0, 1, 0, 0 ] ) === false;
} )() );

check( 'a new container starts with no children and no content, and the defaults of its attributes; a new child gets a key of its own in the section, as every component of a static column does', ( function() {

	const model = cardPage();
	const holder = builder._newComponent( model, registry(), 'page-home-card', [ 0, 1 ], 'card', [] );
	const child = builder._newComponent( model, registry(), 'page-home-card', cardAt, 'card-title', [] );
	const loopChild = builder._newComponent( model, registry(), 'page-home-card', [ 1, 0, 0 ], 'card-title', [] );

	return JSON.stringify( holder.components ) === '[]' && Object.prototype.hasOwnProperty.call( holder, 'content' ) === false && holder.source === '' && holder.create === undefined && holder.attributes.fill === '0' && holder.attributes.background === '' && holder.attributes.padding === ''
		&& child.source === '/template/page-home-card/story/card-title' && JSON.stringify( child.create ) === '{}' && child.values === undefined && Object.prototype.hasOwnProperty.call( child, 'components' ) === false
		&& loopChild.source === '' && loopChild.create === undefined;
} )() );
check( '...the content of the other component that takes one - [html] - stays a content: it has no children', ( function() {
	const html = builder._newComponent( cardPage(), registry(), 'page-home-card', [ 0, 1 ], 'html', [] );
	return html.content === '' && Object.prototype.hasOwnProperty.call( html, 'components' ) === false;
} )() );

console.log( '\nCopies, names, sources and the look of a component that holds components' );

check( 'a copy of a container is a deep one, with its children: the keys they read their texts from and the picture an attribute names are new ones in the section - made when the document is saved - and the original keeps what it had', ( function() {

	const model = cardPage();
	const holder = cardIn( model );

	holder.attributes.background = '/template/page-home-card/story/card-picture';
	holder.slots = { background : { label : 'Card - Background picture', width : 800, height : 400 } };

	const to = builder._duplicate( model, registry(), 'page-home-card', cardAt, function( source ) { return { en_US : 'text of '+ source.split('/').pop() } } );
	const copy = builder._get( model, to );

	return JSON.stringify( to ) === '[0,1,1]' && model.blocks[0].cols[1].components.length === 2 && copy !== holder && copy.components !== holder.components && copy.components.length === 3
		&& copy.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story/title-2,/template/page-home-card/story/text-2,/template/page-home-card/story/button-2'
		&& JSON.stringify( copy.components.map( function( child ) { return child.create } ) ) === '[{},{},{}]' && JSON.stringify( copy.components.map( function( child ) { return child.values } ) ) === '[{"en_US":"text of title"},{"en_US":"text of text"},{"en_US":"text of button"}]'
		&& copy.attributes.background === '/template/page-home-card/story/card-picture-2' && JSON.stringify( copy.slots ) === '{"background":{"label":"Card - Background picture","width":1600,"height":900,"from":"/template/page-home-card/story/card-picture"}}'
		&& holder.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story/title,/template/page-home-card/story/text,/template/page-home-card/story/button' && holder.components[0].create === undefined
		&& holder.attributes.background === '/template/page-home-card/story/card-picture' && holder.slots.background.width === 800;
} )() );
check( '...a copy of a child stands right after it in its container, with a key of its own', ( function() {

	const model = cardPage();
	const to = builder._duplicate( model, registry(), 'page-home-card', [ 0, 1, 0, 0 ], function() { return { en_US : 'Hello' } } );

	return JSON.stringify( to ) === '[0,1,0,1]' && cardIn( model ).components.length === 4 && builder._get( model, to ).name === 'card-title' && builder._get( model, to ).source === '/template/page-home-card/story/title-2'
		&& JSON.stringify( builder._get( model, to ).values ) === '{"en_US":"Hello"}' && builder._get( model, [ 0, 1, 0, 0 ] ).source === '/template/page-home-card/story/title';
} )() );
check( '...a copy of a section takes everything it owns under its new id: the keys of the children and the picture behind a card', ( function() {

	const model = cardPage();

	cardIn( model ).attributes.background = '/template/page-home-card/story/card-picture';
	builder._duplicate( model, registry(), 'page-home-card', [ 0 ], function() { return { en_US : 'Hello' } } );

	const copy = model.blocks[1];
	const holder = builder._get( model, [ 1, 1, 0 ] );

	return copy.id === 'story-2' && holder.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story-2/title,/template/page-home-card/story-2/text,/template/page-home-card/story-2/button'
		&& holder.attributes.background === '/template/page-home-card/story-2/card-picture' && JSON.stringify( holder.slots ) === '{"background":{"label":"Card - Background picture","width":1600,"height":900,"from":"/template/page-home-card/story/card-picture"}}' && cardIn( model ).attributes.background === '/template/page-home-card/story/card-picture'
		&& cardIn( model ).slots === undefined;
} )() );
check( 'a name for a new key or slot is free among the sources of the whole section: the children of a container and the pictures its attributes name count', ( function() {

	const model = cardPage();
	const section = model.blocks[0];

	cardIn( model ).attributes.background = '/template/page-home-card/story/card-picture';

	const names = builder._names( section, 'page-home-card', [] );

	return Object.keys( names ).sort().join() === 'button,card-picture,photo,text,title' && builder._keyName( section, 'page-home-card', 'card-title', [] ) === 'card-title' && builder._keyName( section, 'page-home-card', 'title', [] ) === 'title-2'
		&& builder._keyName( section, 'page-home-card', 'card-picture', [] ) === 'card-picture-2' && builder._nameProblem( section, 'page-home-card', 'text', [] ) === 'taken' && builder._nameProblem( section, 'page-home-card', 'card-picture', [] ) === 'taken';
} )() );

const redOf = function( model ) { return builder._red( model, registry() ).map( function( entry ) { return entry.path.join('.')+ ':'+ entry.why+ ':'+ entry.source } ).join() };
check( 'what a source means where it stands is told in a container as in a column: the children of a card in a loop read the fields of the element, those of a card in a static column read keys only - and the page of the fixture is clean', ( function() {

	const model = cardPage();
	const clean = redOf( model );

	cardIn( model ).components[0].source = 'title';
	builder._get( model, [ 1, 0, 0, 0 ] ).source = 'nothing-like-it';

	return clean === '' && redOf( model ) === '0.1.0.0:static:title,1.0.0.0:field:nothing-like-it';
} )() );
check( '...and so does the picture an attribute names: in a loop an image field of the element or a slot, in a static column a slot only - a field that is none, or a special source of a text, is red on the container', ( function() {

	const model = cardPage();
	const loopCard = function() { return builder._get( model, [ 1, 0, 0 ] ) };
	const states = [];

	// A field of the element
	states.push( redOf( model ) );

	// ...a name the type has no field for, and the sources of a text, which are no picture
	loopCard().attributes.background = 'photo';
	states.push( redOf( model ) );
	loopCard().attributes.background = '.id';
	states.push( redOf( model ) );

	// ...a slot is fine, wherever it is
	loopCard().attributes.background = '/template/page-home-card/services/card-picture';
	states.push( redOf( model ) );

	// ...a static column has no element to read a field of
	cardIn( model ).attributes.background = 'image';
	states.push( redOf( model ) );
	cardIn( model ).attributes.background = '/template/page-home-card/story/card-picture';
	states.push( redOf( model ) );

	return states.join(' | ') === ' | 1.0.0:field:photo | 1.0.0:field:.id |  | 0.1.0:static:image | ';
} )() );

doc = openCard();
check( 'where a component would stand: inside a container the place it holds, in a column the hero where the column is in the first section - a cover or none - and a section otherwise - the cell of a loop is a column\'s place, since it holds none of its own', ( function() {

	const model = builder._doc.model;
	const before = [ builder._where( model, [ 0, 1 ] ), builder._where( model, cardAt ), builder._where( model, [ 1, 0 ] ), builder._where( model, [ 1, 0, 0 ] ) ].join();

	model.blocks[0].settings.cover = 90;

	const own = registry();

	own.components.card.holds = 'tile';

	return before === 'hero,card,section,card' && [ builder._where( model, [ 0, 0 ] ), builder._where( model, [ 0, 1 ] ), builder._where( model, cardAt ), builder._where( model, [ 1, 0 ] ) ].join() === 'hero,hero,card,section'
		&& builder._where( model, cardAt, own ) === 'tile';
} )() );

check( 'what the schema says of a picture is known by type and place and never by name: the focus is a select over none and the nine places, the dimming a switch that follows a picture - or the focus that follows it -, a ground a select over the grounds of a column', ( function() {

	const places = [ '', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
	const declared = builder._declared( registry().components.card );
	const roles = builder._roles( declared );
	const own = {
		a : { type : 'image' }, b : { type : 'bool' }, c : { type : 'image' }, d : { type : 'select', options : places }, e : { type : 'bool' }, f : { type : 'string' }, g : { type : 'bool' },
		h : { type : 'select', options : places.slice().reverse() }, i : { type : 'bool' }, j : { type : 'select', options : [ '', '1' ] },
	};

	return JSON.stringify( roles ) === '{"focus":{"role":"focus","picture":"background"},"dim":{"role":"dim","picture":"background"}}' && JSON.stringify( builder._pictures( declared ) ) === '["background"]'
		&& JSON.stringify( builder._roles( own ) ) === '{"b":{"role":"dim","picture":"a"},"d":{"role":"focus","picture":"c"},"e":{"role":"dim","picture":"c"},"h":{"role":"focus","picture":"c"}}' && JSON.stringify( builder._roles( registry().components.image.attributes ) ) === '{"focus":{"role":"focus","picture":""}}'
		&& builder._isFocus( declared.focus ) === true && builder._isFocus( own.j ) === false && builder._isFocus( declared.padding ) === false && builder._isGround( declared.ground ) === true && builder._isGround( declared.align ) === false
		&& builder._isGround( { type : 'select', options : builder.COLORS } ) === false && builder._isGround( { type : 'select', options : builder.COL_COLORS.slice().reverse() } ) === true && builder._isGround( { type : 'string', options : builder.COL_COLORS } ) === false;
} )() );

check( 'the ground the content of a column stands on is the one the column paints - dark where its picture is dimmed - and else its section\'s: counter is the counter of that, by the pairs', ( function() {

	const section = function( color ) { return { settings : { color : color } } };
	const surface = function( around, color, dim, picture ) { return builder._surface( section( around ), { color : color, dim : dim, background : picture === true ? { slot : '/s' } : null } ) };

	return surface( '', '', false, false ) === '' && surface( 'alt', '', false, false ) === 'alt' && surface( 'alt', 'dark', false, false ) === 'dark' && surface( 'dark', 'counter', false, false ) === 'black' && surface( '', 'counter', false, false ) === 'alt'
		&& surface( 'alt', 'counter', false, false ) === '' && surface( 'alt', '', true, true ) === 'dark' && surface( 'tint', 'primary', true, true ) === 'dark' && surface( 'alt', '', true, false ) === 'alt' && surface( 'alt', '', false, true ) === 'alt';
} )() );
check( '...a component that holds components looks as its attributes say: the ground it paints - none, one, the counter of the ground it stands on - and dark where its picture is dimmed, whatever ground it names; a dimming without a picture is nothing', ( function() {

	const schema = registry().components.card;
	const look = function( ground, picture, dim, surface ) {
		return builder._look( schema, { attributes : { ground : ground, background : picture, dim : dim } }, surface );
	};
	const flat = function( found ) { return found.color+ ':'+ found.picture };

	return flat( look( '', '', '0', '' ) ) === ':false' && flat( look( 'tint', '', '0', 'dark' ) ) === 'tint:false' && flat( look( 'counter', '', '0', '' ) ) === 'alt:false' && flat( look( 'counter', '', '0', 'dark' ) ) === 'black:false'
		&& flat( look( 'counter', '', '0', 'alt' ) ) === 'plain:false' && flat( look( 'counter', '', '0', 'tint' ) ) === 'plain:false' && flat( look( 'counter', '', '0', 'primary' ) ) === 'brand-alt:false'
		&& flat( look( '', '/s', '0', '' ) ) === ':true' && flat( look( 'alt', '/s', '1', '' ) ) === 'dark:true' && flat( look( '', '/s', '1', '' ) ) === 'dark:true' && flat( look( 'counter', 'image', '1', 'alt' ) ) === 'dark:true'
		&& flat( look( 'alt', '', '1', '' ) ) === 'alt:false' && flat( builder._look( registry().components.text, { attributes : {} }, 'alt' ) ) === ':false';
} )() );

check( 'the preview of a container is its label, the ground it paints, whether it has a picture behind it and its children as placeholders with their own path, label and line: a column without one has components as it had', ( function() {

	const plan = builder._preview( cardPage(), registry(), 'l', { locale : 'en_US', keys : [] } );
	const card = plan[0].cols[1].components[0];
	const loopCard = plan[1].cols[0].components[0];

	return card.holds === true && card.label === 'Card' && card.color === 'alt' && card.background === false && JSON.stringify( card.path ) === '[0,1,0]' && card.children.length === 3
		&& card.children.map( function( child ) { return child.path.join('.')+ ':'+ child.label+ ':'+ child.draws } ).join() === '0.1.0.0:Title (Card):H3 .nino-card-title,0.1.0.1:Text:,0.1.0.2:Button:A .nino-btn' && card.children.every( function( child ) { return child.holds === undefined } )
		&& loopCard.holds === true && loopCard.color === 'dark' && loopCard.background === true && loopCard.children.length === 1 && plan[0].cols[0].components[0].holds === undefined && plan[0].cols[0].components[0].children === undefined;
} )() );
check( '...the tint of a container follows the ground of its column or, where the column has none, of its section', ( function() {

	const model = cardPage();
	const tint = function( around, color, ground ) {
		model.blocks[0].settings.color = around;
		model.blocks[0].cols[1].color = color;
		cardIn( model ).attributes.ground = ground;
		return builder._preview( model, registry(), 'l' )[0].cols[1].components[0].color;
	};

	return tint( '', '', '' ) === '' && tint( '', '', 'alt' ) === 'alt' && tint( 'dark', '', 'counter' ) === 'black' && tint( '', 'alt', 'counter' ) === 'plain' && tint( 'alt', 'dark', 'counter' ) === 'black' && tint( 'tint', 'counter', 'counter' ) === 'alt'
		&& tint( 'dark', 'counter', 'counter' ) === 'dark' && tint( '', 'primary', 'counter' ) === 'brand-alt' && tint( 'primary', 'counter', 'tint' ) === 'tint';
} )() );

console.log( '\nThe picker of a card, and what it adds' );

doc = openCard();
builder._renderEditor();

// What belongs in each place, as the registry says it
const inCard = belonging( 'card' );
const inSection = belonging( 'section' );
const inHero = belonging( 'hero' );

check( 'the lightbox offers the card where the card says it belongs - a column of a section, and the bare cell of a loop, which holds no place of its own - and what a column is offered besides', offeredAt( [ 1, 0 ] ) === inSection );
check( '...in the first section it is offered as well, whether it is a cover or none: the hero is a place the card belongs in, and a cover does not make it one', ( function() {
	const without = [ offeredAt( [ 0, 0 ] ), offeredAt( [ 0, 1 ] ) ];
	builder._doc.model.blocks[0].settings.cover = 90;
	const offered = offeredAt( [ 0, 0 ] );
	builder._doc.model.blocks[0].settings.cover = null;
	return without.join() === [ inHero, inHero ].join() && offered === inHero;
} )() );
check( '...inside a card it offers what belongs to the place the card holds - the title and the subtitle of a card - and not the card itself, and not the title of a section nor the title of a hero, whose places are others; what it does not offer stands there greyed, and says it is not for a card', offeredAt( cardAt ) === inCard && offeredAt( [ 1, 0, 0 ] ) === inCard
	&& offeredAt( cardAt ).split(',').indexOf('Card') === -1 && offeredAt( [ 0, 1 ] ).split(',').indexOf('Title (Card)') === -1 && offeredAt( [ 1, 0 ] ).split(',').indexOf('Title (Card)') === -1
	&& reasonOf( cardAt, 'Card' ) === 'Not in a card' && reasonOf( cardAt, 'Title (Section)' ) === 'Not in a card' && reasonOf( cardAt, 'Title (Card)' ) === '' && reasonOf( [ 1, 0 ], 'Card' ) === '' );
check( '...the button of the card in the preview opens the same lightbox, and the button of its column the column\'s', ( function() {

	preview().querySelector('[data-path="0.1.0"] .builder-adds .builder-add-button').click();

	const own = takenIn( lightboxOf() );

	const adds = preview().querySelector('[data-path="0.1"]').querySelectorAll('.builder-adds');

	adds[adds.length - 1].querySelector('.builder-add-button').click();

	return adds.length === 2 && own === inCard && takenIn( lightboxOf() ) === inHero;
} )() );

check( '...a component a loop does not take is offered in no column that runs one, and in no card inside it - at depth three and at depth four alike - and everywhere else; a container that says it belongs in a card is offered in none', ( function() {

	const components = builder._registry.components;

	components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false, group : 'global' };
	components['stub-holder'] = { label : 'Stub holder', source : 'content', preview : 'block', in : [ 'hero', 'section', 'card' ], holds : 'card', group : 'global' };

	const stub = function( path ) { return offeredAt( path ).split(',').filter( function( label ) { return /^Stub/.test( label ) } ).join() };
	const found = [ stub( [ 0, 1 ] ), stub( cardAt ), stub( [ 1, 0 ] ), stub( [ 1, 0, 0 ] ) ].join(' | ');

	delete components['stub-alone'];
	delete components['stub-holder'];

	return found === 'Stub alone,Stub holder | Stub alone | Stub holder | ';
} )() );

check( 'a card added to a column is its last component, with no children, selected - and drawn as a frame of its own with a button that adds to it', ( function() {

	const col = builder._doc.model.blocks[0].cols[1];

	builder._addComponent( [ 0, 1 ], 'card' );

	const added = col.components[col.components.length - 1];

	return col.components.length === 2 && added.name === 'card' && JSON.stringify( added.components ) === '[]' && added.content === undefined && builder._same( builder._sel, [ 0, 1, 1 ] )
		&& preview().querySelectorAll('[data-path="0.1"] .builder-card').length === 2 && preview().querySelector('[data-path="0.1.1"]').classList.contains('is-selected') && preview().querySelector('[data-path="0.1.1"] .builder-adds') !== null
		&& preview().querySelector('[data-path="0.1.1"]').querySelectorAll('.builder-ph').length === 0;
} )() );
check( '...a component added to a card is its last child, with a key of its own in the section, selected; the same in the card of a loop, where it reads a field of the element and needs no key', ( function() {

	const card = cardIn( builder._doc.model );

	builder._addComponent( cardAt, 'card-subtitle' );
	builder._addComponent( [ 1, 0, 0 ], 'card-subtitle' );

	const added = card.components[card.components.length - 1];
	const looped = builder._get( builder._doc.model, [ 1, 0, 0, 1 ] );

	return card.components.length === 4 && added.name === 'card-subtitle' && added.source === '/template/page-home-card/story/card-subtitle' && JSON.stringify( added.create ) === '{}'
		&& looped.name === 'card-subtitle' && looped.source === '' && looped.create === undefined && builder._same( builder._sel, [ 1, 0, 0, 1 ] ) && preview().querySelector('[data-path="0.1.0"]').querySelectorAll('.builder-ph').length === 4
		&& preview().querySelector('[data-path="1.0.0.1"]').classList.contains('is-selected') && builder._doc.model.blocks[1].cols[0].components.length === 1;
} )() );
check( '...and the document that came of it is unsaved, with the children where the Reader puts them and the marks the panel keeps', builder._unsaved( builder._doc ) === true && builder._saveRequest( builder._doc, false ).model.blocks[0].cols[1].components[0].components.length === 4
	&& JSON.stringify( builder._saveRequest( builder._doc, false ).model.blocks[0].cols[1].components[0].components[3].create ) === '{}' );

console.log( '\nThe lightbox adds a component and goes on to its settings' );

doc = open();
builder._renderEditor();
const offered = firstOffered( pickAt( [ 3, 0 ] ), 'section' );
const offeredName = offered.dataset.name;
offered.click();
const addedAt = [ 3, 0, 2 ];
check( 'a click on a component of the lightbox adds it at the end of the column and the dialog goes on to its settings, in the same dialog: the lightbox is gone, the form of the component stands there with its blocks, the component is the selection, and the first control of the form has the focus', dialogBox().open === true && lightboxOf() === null
	&& builder._doc.model.blocks[3].cols[0].components.length === 3 && builder._doc.model.blocks[3].cols[0].components[2].name === offeredName && dialogText('builder-dialog-kind') === titleOf( offeredName )
	&& blockIds().join() === 'content,link,spacing,properties' && builder._same( builder._sel, addedAt )
	&& screen.activeElement !== null && dialogContent().contains( screen.activeElement ) && screen.activeElement.disabled === false );
check( '...it is a component as any is added: its key is made in the section, the document has changed - once - and the preview shows it as the selection', builder._doc.model.blocks[3].cols[0].components[2].source.indexOf( '/template/page-home/contact/'+ offeredName ) === 0 && builder._doc.model.blocks[3].cols[0].components[2].create !== undefined
	&& changes === 1 && builder._unsaved( builder._doc ) === true && preview().querySelector('[data-path="3.0.2"]').classList.contains('is-selected') );
dialogBox().close();
check( '...Escape - the close the browser does - ends the dialog with the component in: nothing is asked and nothing is taken back', questions.length === 0 && dialogBox().open === false && builder._dialogOptions === null && builder._doc.model.blocks[3].cols[0].components.length === 3
	&& preview().querySelector('[data-path="3.0.2"]') !== null );

console.log( '\nThe focus when the form of a node is closed' );

/*	What a person does with the keyboard: the button that adds to the column has the focus and is pressed, the item of the lightbox has the focus and is pressed. The preview is
	drawn again for the component that was added, so that the button that opened the lightbox is not on the page when the dialog is closed - the browser has nothing to give the
	focus back to	*/
const addButtonOf = function( at ) { const adds = frameOf( at ).querySelectorAll('.builder-adds'); return adds[adds.length - 1].querySelector('.builder-add-button') };
const addWith = function( at, word ) {

	const trigger = addButtonOf( at );

	trigger.focus();
	trigger.click();

	const item = firstOffered( lightboxOf(), word );

	item.focus();
	item.click();

	return trigger;
};
const onTool = function( at ) { return screen.activeElement !== null && screen.activeElement === toolOf( at, 'settings' ) && screen.activeElement.isConnected === true };

doc = open();
builder._renderEditor();
const gone = addWith( '3.0', 'section' );
check( 'a component added by the keyboard in the lightbox has its settings open, and the button that opened the lightbox is not on the page any more: the preview was drawn again for the new component', dialogBox().open === true
	&& dialogText('builder-dialog-kind') === titleOf( builder._get( builder._doc.model, builder._sel ).name ) && gone.isConnected === false && screen.activeElement !== null && dialogContent().contains( screen.activeElement ) );
dialogBox().close();
check( 'Escape - the close the browser does - leaves the focus on the settings tool of the component that was added: where it is drawn in the preview now, and not on the page', dialogBox().open === false && onTool( '3.0.2' ) );

doc = open();
builder._renderEditor();
addWith( '3.0', 'section' );
buttonOf( screen.getElementById('builder-dialog-actions'), 'Close' ).click();
check( '...the Close button of the dialog does the same', dialogBox().open === false && builder._dialogOptions === null && onTool( '3.0.2' ) );

doc = open();
builder._renderEditor();
addWith( '3.0', 'section' );
screen.getElementById('builder-dialog-close').click();
check( '...and so does the cross in its head', dialogBox().open === false && builder._dialogOptions === null && onTool( '3.0.2' ) );

doc = openCard();
builder._renderEditor();
addWith( '0.1.0', 'card' );
dialogBox().close();
check( '...in a card too: the tool of the child that was added, one level deeper', dialogBox().open === false && onTool( '0.1.0.3' ) );

doc = open();
builder._renderEditor();
const left = addButtonOf( '3.0' );
left.focus();
left.click();
dialogBox().close();
check( 'a lightbox that is closed with no choice leaves the preview as it was - no component was added, so the button that opened it is where it was - and the focus is on that button: the browser gives it back',
	dialogBox().open === false && left.isConnected === true && screen.activeElement === left && builder._doc.model.blocks[3].cols[0].components.length === 2 && changes === 0 );

[ [ 'a component', '3.0.0', 'properties', 'HTML tag', 'h4' ], [ 'a column', '3.0', 'width', 'Width, Desktop', '33' ], [ 'a section', '3', 'surface', 'Cover', true ] ].forEach( function( kind ) {

	doc = open();
	builder._renderEditor();

	const tool = toolOf( kind[1], 'settings' );

	tool.focus();
	tool.click();
	dialogBox().close();
	const kept = tool.isConnected === true && screen.activeElement === tool;

	tool.focus();
	tool.click();
	change( blockOf( kind[2] ), kind[3], kind[4] );
	dialogBox().close();
	check( 'the settings of '+ kind[0]+ ' give the focus back to the tool that opened them where nothing changed - the browser does - and, where a change in the form drew the preview again and took the tool with it, '
		+ 'to the tool that is drawn in its place', kept === true && tool.isConnected === false && changes === 1 && onTool( kind[1] ) );
} );

doc = open();
builder._renderEditor();
const applied = toolOf( '2', 'settings' );
applied.focus();
applied.click();
screen.getElementById('builder-dialog-actions').children[0].click();
requests[0].callback( 200, { model : JSON.parse( JSON.stringify( builder._doc.model ) ) } );
check( 'the editor of HTML+, applied: the block is read again and the preview drawn again - the tool that opened the editor went with the old preview - and the focus is on the settings tool of the block, where it is drawn now',
	dialogBox().open === false && applied.isConnected === false && onTool( '2' ) );

doc = open();
builder._renderEditor();
screen.focused = null;
frameOf('3.0.0').dispatch('dblclick');
change( dialogContent(), 'HTML tag', 'h4' );
dialogBox().close();
check( 'a form that a double click opened, with the focus on no control, leaves the focus where the browser has it: nothing is taken to a tool the person never was on', screen.activeElement === null && changes === 1 );

doc = openCard();
builder._renderEditor();
const cardOffered = firstOffered( pickAt( cardAt ), 'card' );
cardOffered.click();
check( 'in a card the component goes in as a child at the end of the card, and the settings are the child\'s, in the dialog the lightbox was in', dialogText('builder-dialog-kind') === titleOf( cardOffered.dataset.name ) && cardIn( builder._doc.model ).components.length === 4
	&& cardIn( builder._doc.model ).components[3].name === cardOffered.dataset.name && builder._same( builder._sel, [ 0, 1, 0, 3 ] ) && lightboxOf() === null && blockIds().join() === 'content,link,spacing,properties' );
dialogBox().close();
itemNamed( pickAt( [ 1, 0 ] ), 'image' ).click();
check( '...in a column that runs a loop it goes in as a component of the cell, with no key of its own to make - and a picture, the settings of which have the content of a picture', builder._get( builder._doc.model, [ 1, 0, 1 ] ).name === 'image' && builder._get( builder._doc.model, [ 1, 0, 1 ] ).source === '' && builder._get( builder._doc.model, [ 1, 0, 1 ] ).create === undefined
	&& dialogText('builder-dialog-kind') === titleOf('image') && builder._same( builder._sel, [ 1, 0, 1 ] ) && dialogContent() !== null );
dialogBox().close();
check( '...and what a card says it does not take stands in its lightbox greyed, and a click on it does nothing: the card is not put in a card, the title of a section is not put in one', ( function() {

	pickAt( cardAt );

	const card = itemNamed( lightboxOf(), 'card' );
	const section = firstGreyed( lightboxOf(), 'section' );
	const length = cardIn( builder._doc.model ).components.length;
	const before = changes;

	card.click();
	section.click();

	return card.disabled === true && section.disabled === true && cardIn( builder._doc.model ).components.length === length && changes === before && lightboxOf() !== null;
} )() );
builder._closeDialog();

console.log( '\nThe icon of a component in the picker' );

doc = openCard();
builder._renderEditor();

// The items of the lightbox of a column, or of a component that holds components: one for every component of the registry
const pickedItems = function( path ) { return itemsOf( pickAt( path ) ) };
// What the parts of an icon say, and what an svg is made of: the tag of each part and its attributes, by name in the alphabet's order
const partsSaid = function( parts ) { return JSON.stringify( parts.map( function( part ) { return [ part[0], Object.keys( part[1] ).sort().map( function( name ) { return [ name, part[1][name] ] } ) ] } ) ) };
const partsBuilt = function( svg ) { return JSON.stringify( svg.children.map( function( part ) { return [ part.localName, Object.keys( part.attrs ).sort().map( function( name ) { return [ name, part.attrs[name] ] } ) ] } ) ) };

check( 'every item of the lightbox is a button that holds an icon first and its label after it, the label in a span of its own - one that is greyed has the reason in a third - so that an item that can be added has the label alone for its text and is found by it', ( function() {

	const box = pickAt( [ 1, 0 ] );
	const items = itemsOf( box );

	return items.length === Object.keys( registryFixture.components ).length && items.every( function( item ) {
		return item.localName === 'button' && item.type === 'button' && item.children[0].localName === 'svg' && item.children[1].localName === 'span' && item.children[1].className === 'builder-pick-label'
			&& ( item.disabled === true ? item.children.length === 3 && item.textContent === pickLabel( item )+ item.title : item.children.length === 2 && item.textContent === pickLabel( item ) );
	} ) && takenIn( box ) === inSection && texts( items.filter( function( item ) { return item.disabled === false } ) ) === inSection && buttonOf( box, 'Button' ) === items.find( function( item ) { return pickLabel( item ) === 'Button' } )
		&& buttonOf( box, 'Button' ).children[1].textContent === 'Button';
} )() );
check( '...the icon of a component of the kernel is the one the registry gives it: the svg holds the parts of its entry, tag by tag and attribute by attribute - every one of them, in the pickers of the hero, of a section and of a card', ( function() {

	const components = builder._registry.components;
	const names = Object.keys( components );
	const seen = {};
	const same = [ [ 0, 0 ], [ 1, 0 ], cardAt ].every( function( path ) {
		return pickedItems( path ).every( function( item ) {

			const name = names.find( function( candidate ) { return components[candidate].label === pickLabel( item ) } );

			seen[name] = true;

			return name !== undefined && Array.isArray( components[name].icon ) === true && partsBuilt( item.children[0] ) === partsSaid( components[name].icon );
		} );
	} );

	return same && names.length > 0 && names.length === Object.keys( registryFixture.components ).length && Object.keys( seen ).length === names.length && names.every( function( name ) { return components[name].icon.length > 0 } );
} )() );
check( '...it is an svg of its own and decoration: made in the namespace of svg, as every part of it is - an element of the page is not -, 24 by 24, out of reach of assistive technology, with the class of the icons and the lightbox\'s, and it draws with its parts, not with the sprite', ( function() {

	const svg = pickedItems( [ 1, 0 ] )[0].children[0];

	return svg.namespaceURI === SVG_NS && svg.children.length === 3 && svg.all().every( function( el ) { return el.namespaceURI === SVG_NS } ) && made('button').namespaceURI === HTML_NS && lightboxOf().namespaceURI === HTML_NS
		&& svg.getAttribute('viewBox') === '0 0 24 24' && svg.getAttribute('aria-hidden') === 'true' && svg.getAttribute('focusable') === 'false' && svg.className === 'builder-icon builder-pick-icon' && svg.querySelector('use') === null;
} )() );

( function() {

	const components = builder._registry.components;
	const every = [
		[ 'path', { d : 'M4 4h16' } ], [ 'circle', { cx : '12', cy : '12', r : '3' } ], [ 'ellipse', { cx : '12', cy : '12', rx : '8', ry : '4' } ], [ 'rect', { x : '3', y : '3', width : '18', height : '18' } ],
		[ 'rect', { x : '5', y : '5', width : '14', height : '14', rx : '2', ry : '2' } ], [ 'line', { x1 : '4', y1 : '4', x2 : '20', y2 : '20' } ], [ 'polyline', { points : '4 4 12 12 20 4' } ], [ 'polygon', { points : '12 3 21 21 3 21' } ],
	];
	const second = [ [ 'path', { d : 'M2 12h20' } ], [ 'path', { d : 'M12 2v20' } ] ];

	// Two components of a feature with an icon of their own, and four that name none - or name something that is no list of parts
	components['stub-own'] = { label : 'Stub own', preview : 'block', by : 'hello', icon : every };
	components['stub-second'] = { label : 'Stub second', preview : 'block', by : 'hello', icon : second };
	components['stub-none'] = { label : 'Stub none', preview : 'block', by : 'hello' };
	components['stub-text'] = { label : 'Stub text', preview : 'block', by : 'hello', icon : 'heading' };
	components['stub-empty'] = { label : 'Stub empty', preview : 'block', by : 'hello', icon : [] };
	components['stub-map'] = { label : 'Stub map', preview : 'block', by : 'hello', icon : { d : 'M0 0' } };

	const box = pickAt( [ 1, 0 ] );
	const items = itemsOf( box );
	const stub = {};

	items.filter( function( item ) { return /^Stub/.test( pickLabel( item ) ) } ).forEach( function( item ) { stub[pickLabel( item )] = item } );

	check( '...a component of a feature is offered with its own icon, built the same whichever of the seven tags its parts name - a rect with its radii and one without -, and after the kernel\'s as it was', takenIn( box ) === inSection+ ',Stub own,Stub second,Stub none,Stub text,Stub empty,Stub map'
		&& partsBuilt( stub['Stub own'].children[0] ) === partsSaid( every ) && stub['Stub own'].children[0].children.map( function( part ) { return part.localName } ).join() === 'path,circle,ellipse,rect,rect,line,polyline,polygon'
		&& partsBuilt( stub['Stub second'].children[0] ) === partsSaid( second ) && stub['Stub own'].children[0].className === 'builder-icon builder-pick-icon' && stub['Stub own'].children[0].namespaceURI === SVG_NS );

	const puzzle = screen.getElementById('builder-icon-puzzle');
	const worn = [ 'Stub none', 'Stub text', 'Stub empty', 'Stub map' ].map( function( label ) { return stub[label] } );

	check( '...a component that names no icon - or names something that is no list of parts - wears the puzzle of the sprite: the svg of the icons with a use of the symbol, the class of the others and nothing built into it', worn.every( function( item ) {
		const svg = item.children[0];
		return svg.localName === 'svg' && svg.namespaceURI === SVG_NS && svg.children.length === 1 && svg.children[0].localName === 'use' && svg.children[0].getAttribute('href') === '#builder-icon-puzzle' && iconOf( item ) === 'puzzle'
			&& svg.className === 'builder-icon builder-pick-icon' && svg.getAttribute('aria-hidden') === 'true';
	} ) && stub['Stub own'].querySelector('use') === null && stub['Stub second'].querySelector('use') === null );
	check( '...the puzzle is a symbol of the sprite, a path of 24 by 24, and the picker passes the inspection every form passes: each icon in it is a symbol of the sprite, and a control that is an icon has a name', puzzle !== null && puzzle.localName === 'symbol' && puzzle.getAttribute('viewBox') === '0 0 24 24'
		&& puzzle.children.length === 1 && puzzle.children[0].localName === 'path' && iconsKnown( box ) === true && unnamed( box ).length === 0 );

	components['stub-odd'] = { label : 'Stub odd', preview : 'block', by : 'hello', icon : [
		[ 'script', { src : 'x.js' } ], [ 'path', { d : 'M0 0h24', onclick : 'x()', fill : 'red', style : 'x' } ], [ 'constructor', { d : 'M1 1' } ], [ 'circle', 'cx=1' ], [ 'rect', null ], 'path', null, [ 'line' ],
		[ 'circle', { r : '2', cx : '12', cy : '12', d : 'M2 2' } ], [ 'svg', {} ], [ [ 'path' ], { d : 'M3 3' } ],
	] };

	const odd = pickedItems( [ 1, 0 ] ).filter( function( item ) { return pickLabel( item ) === 'Stub odd' } )[0].children[0];

	check( '...the script builds no element and sets no attribute it does not know, whatever the registry says: a part of a tag that is none of the seven - a script, a name every object has, a nested svg -, a part that is no tag with its attributes, an attribute the tag does not take, are left out', partsBuilt( odd )
		=== partsSaid( [ [ 'path', { d : 'M0 0h24' } ], [ 'circle', { r : '2', cx : '12', cy : '12' } ] ] ) && odd.all().every( function( el ) { return [ 'path', 'circle' ].indexOf( el.localName ) !== -1 && el.getAttribute('onclick') === null && el.getAttribute('style') === null && el.getAttribute('fill') === null } )
		&& odd.all().length === 2 );

	Object.keys( components ).filter( function( name ) { return /^stub-/.test( name ) } ).forEach( function( name ) { delete components[name] } );
} )();

check( '...none of it is written as markup: the script assigns an empty string to innerHTML and nothing else - the stand-in takes no other, and throws on it - and parses no markup into the page', ( function() {

	const assigned = source.match( /\.innerHTML\s*=[^;]*;/g ) ?? [];

	return assigned.length > 0 && assigned.every( function( found ) { return /^\.innerHTML\s*=\s*'';$/.test( found ) } ) && /insertAdjacentHTML|\.outerHTML\s*=|document\.write|createContextualFragment|DOMParser/.test( source ) === false
		&& ( function() { try { made('div').innerHTML = '<svg></svg>'; return false } catch( error ) { return true } } )();
} )() );
check( '...the stylesheet lays the lightbox out as rows that wrap in the dialog of a phone - a heading for each group, under it the items, each the icon and the label in the middle of the line - and, from the width of the dialog on, each group as a row of its own: its heading in a column the groups share, its items beside it in a grid of cells of one width that wraps; the groups stand in the document in the order they are listed in, so that the keys go along a row and on to the next; the icon is a square of a rem that does not shrink, and an item that cannot be added is dimmed and has no pointer', ( function() {

	const lines = function( selector ) { return ruleOf( selector ).split('\n') };
	const has = function( selector, list ) { return list.every( function( line ) { return lines( selector ).indexOf( line ) !== -1 } ) };
	// The blocks of the stylesheet for a width of the window that have a rule of the lightbox in them, the narrowest first as the stylesheet has them
	const blocks = Array.from( stylesheet.matchAll( /\n@media \(min-width: ([0-9.]+)rem\) \{\n([\s\S]*?)\n\}(?=\n)/g ) ).filter( function( found ) { return found[2].indexOf('.builder-lightbox') !== -1 } ).map( function( found ) { return { width : Number( found[1] ), body : found[2] } } );
	const holds = function( block, list ) { return block !== undefined && list.every( function( line ) { return block.body.indexOf( line ) !== -1 } ) };

	return has( '.builder-lightbox-row', [ '\tdisplay: flex;', '\tflex-wrap: wrap;' ] ) && has( '.builder-pick-item', [ '\tdisplay: flex;', '\talign-items: center;' ] ) && has( '.builder-pick-icon', [ '\tflex: none;', '\twidth: 1rem;', '\theight: 1rem;' ] )
		&& has( '.builder-lightbox .builder-pick-item:disabled', [ '\topacity: .45;', '\tcursor: default;' ] ) && has( '.builder-lightbox', [ '\tdisplay: grid;' ] )
		&& blocks.length === 1 && blocks[0].width === 36
		&& holds( blocks[0], [ '\t\tgrid-template-columns: fit-content( 12rem ) minmax( 0, 1fr );', '\t\tgrid-template-columns: subgrid;', '\t\tgrid-template-columns: repeat( auto-fill, minmax( 10.5rem, 1fr ) );', '\t.builder-lightbox-row {\n\t\tdisplay: grid;' ] );
} )() );

console.log( '\nThe form of a card' );

openCard();
builder._renderEditor();
builder._openSettings( cardAt );

const holderPane = function() { return dialogContent() };
const holderNow = function() { return cardIn( builder._doc.model ) };

// The attributes of the card as the registry has them: the form of the card is cut from their types, in their order - no pin of it names an attribute or a label
const cardDeclared = registry().components.card.attributes;
const cardDefaults = registry().components.card.defaults;
const cardNames = Object.keys( cardDeclared ).filter( function( name ) { return cardDeclared[name].type !== 'variant' } );
// The labels the registry gives the ground, the padding and the two alignments: the pins name them by what the registry says
const cardGround = cardDeclared.ground.label;
const cardPadding = cardDeclared.padding.label;
const cardVertical = cardDeclared.vertical.label;
const cardHorizontal = cardDeclared.align.label;
// A focus is a select over none and the nine places, whatever it is called
const cardIsFocus = function( name ) { return cardDeclared[name].type === 'select' && cardDeclared[name].options.join() === [ '', 1, 2, 3, 4, 5, 6, 7, 8, 9 ].join() };
// The controls an attribute has, by label: the upload of the Builder for a picture, the switch for none and then the grid for a focus, the label of the schema for the rest
const cardControls = function( name ) {
	return cardDeclared[name].type === 'image' ? [ word('picture/upload') ] : ( cardIsFocus( name ) === true ? [ word('source/no-focus'), cardDeclared[name].label ] : [ cardDeclared[name].label ] );
};
// ... and their kinds: a flag is a switch (a box where the schema says so), a number a number, a text and the address of a picture a line of text, a choice a select - a row of buttons where the schema draws it so, and the grid of a focus
const cardKinds = function( name ) {

	const declared = cardDeclared[name];

	if( declared.type === 'image' )
		return [ 'text' ];

	if( cardIsFocus( name ) === true )
		return [ 'switch', 'group' ];

	if( declared.type === 'bool' )
		return [ declared.input === 'checkbox' ? 'box' : 'switch' ];

	return [ { int : 'number', string : 'text' }[declared.type] ?? ( declared.input === 'segment' || declared.input === 'icons' ? 'group' : 'select' ) ];
};
// What hangs on the picture and waits for one: the picture, its focus, and the switch that follows them - its dimming
const cardPictured = ( function() {

	const found = [];
	let chained = false;

	cardNames.forEach( function( name ) {

		const declared = cardDeclared[name];

		if( declared.type === 'image' || ( chained === true && ( cardIsFocus( name ) === true || declared.type === 'bool' ) ) )
			found.push( name );

		chained = declared.type === 'image' || ( chained === true && cardIsFocus( name ) === true );
	} );

	return found;
} )();

// The block an attribute of the card is in: its tab says the surface, the address and the window are the link, any other is a property - read from the registry, not from the script
const cardBlockOf = function( name ) {
	return cardDeclared[name].tab === 'background' ? 'surface' : ( cardDeclared[name].type === 'href' || [ 'target', 'linked' ].indexOf( name ) !== -1 ? 'link' : 'properties' );
};
const cardHas = function( id ) { return cardNames.filter( function( name ) { return cardBlockOf( name ) === id } ) };
const classLabel = word('component/custom');
check( 'the form of a card has the label of the component for its title and the blocks the card has - its link, its surface, its spacing and its properties, in that order - and no content, no tabs: it holds components, which are edited where they stand', dialogText('builder-dialog-kind') === 'Card'
	&& blockIds().join() === 'link,surface,spacing,properties' && screen.getElementById('builder-dialog-tabs') === null && screen.getElementById('builder-dialog-name').hidden === true && dialogContent() !== null );
check( '...in each block a field for each attribute of the schema that stands there - the schema says by its tab where the picture and its ground go, and the card is the link by its own attribute -, in the order of the schema, by the type the schema gives it - a switch for a flag, a select for a choice, a row of buttons where the schema draws it so, the upload of the Builder for a picture, for a focus its switch for none and then the grid -, the offsets and the toggle of the radius after the ground, the classes last of the properties',
	cardNames.length > 0 && cardHas('link').length > 0 && cardHas('surface').length > 0 && cardHas('properties').length > 0
	&& JSON.stringify( labelList( blockOf('link') ) ) === JSON.stringify( cardHas('link').map( cardControls ).flat() )
	&& JSON.stringify( labelList( blockOf('surface') ) ) === JSON.stringify( cardHas('surface').map( cardControls ).flat().concat( [ offsetLabel('left'), offsetLabel('right'), radiusLabel ] ) )
	&& JSON.stringify( labelList( blockOf('properties') ) ) === JSON.stringify( cardHas('properties').map( cardControls ).flat().concat( [ classLabel ] ) )
	&& JSON.stringify( controlsIn( blockOf('surface') ).map( function( control ) { return control.kind } ) ) === JSON.stringify( cardHas('surface').map( cardKinds ).flat().concat( [ 'select', 'select', 'switch' ] ) )
	&& JSON.stringify( controlsIn( blockOf('properties') ).map( function( control ) { return control.kind } ) ) === JSON.stringify( cardHas('properties').map( cardKinds ).flat().concat( [ 'text' ] ) ) );
check( '...one column: a field under the other, in no line and no table, and the offsets of the picture at the sides are the ones of the section', dialogContent().querySelector('.builder-line') === null && dialogContent().querySelector('table') === null
	&& controlOf( blockOf('surface'), offsetLabel('left') ).kind === 'select' && optionsOf( blockOf('surface'), offsetLabel('left') ) === optionsOf( blockOf('surface'), offsetLabel('right') ) );
check( '...the ground is the colour select of a column - none of its own, which is the surroundings, counter and the six - with the patch of the colour it ends up in beside it, in the words of the grounds of a column', optionsOf( holderPane(), cardGround )
	=== '=Like the surroundings,counter=Counter,alt=Alternate,tint=Tinted,dark=Dark,black=Black,primary=Brand colour,brand-alt=Second colour' && holderPane().querySelector('.builder-with-swatch').contains( controlOf( holderPane(), cardGround ).el ) && holderPane().querySelector('.builder-with-swatch').contains( holderPane().querySelector('.builder-swatch') )
	&& holderPane().querySelector('.builder-swatch').getAttribute('aria-hidden') === 'true' && valueAt( holderPane(), cardGround ) === 'counter' );
check( '...the ground has no hint, so no question mark: the select and the patch stand by themselves, and the registry hands the attribute over without one', tipOf( controlOf( blockOf('surface'), cardGround ) ) === null && holderPane().querySelector('.builder-with-swatch').querySelector('.nino-admin-hint') === null
	&& registry().components.card.attributes.ground.hint === '' );
check( '...the padding has the steps of the section\'s spacing, in a select; the two alignments are rows of icons, as the schema says (its input) - the vertical one with its own words, top, middle, bottom, last element at the bottom, the horizontal one with the words of the alignment of a text, Inherited as a word and Left, Centre and Right as icons; each attribute has the words of the Builder, and the values the file has', optionsOf( holderPane(), cardPadding ) === '=Default,0=0,1=1,2=2,3=3,4=4,5=5,6=6'
	&& rowOf( holderPane(), cardVertical ) === ':Top:align-top,middle:Middle:align-middle,bottom:Bottom:align-bottom,last-bottom:Last element at the bottom:align-last-bottom' && rowOf( holderPane(), cardHorizontal ) === ':Inherited:,left:Left:align-left,center:Centre:align-center,right:Right:align-right'
	&& controlOf( holderPane(), cardVertical ).kind === 'group' && controlOf( holderPane(), cardHorizontal ).kind === 'group' && valueAt( holderPane(), cardPadding ) === '3' && valueAt( holderPane(), cardVertical ) === '' );

const swatchOf = function() { return holderPane().querySelector('.builder-swatch').dataset.color };
check( '...the patch is the ground the card ends up with: the ground of its column - or of its section where the column has none - for none of its own, the counter of that, by the pairs of Nino.css, for counter, and the ground itself for the rest', ( function() {

	const pairs = { '' : 'alt', alt : 'plain', tint : 'plain', dark : 'black', black : 'dark', primary : 'brand-alt', 'brand-alt' : 'primary' };

	return Object.keys( pairs ).every( function( around ) {

		builder._closeDialog();
		builder._doc.model.blocks[0].settings.color = around;
		builder._openSettings( cardAt );

		const counter = swatchOf();

		change( holderPane(), cardGround, '' );

		const none = swatchOf();

		change( holderPane(), cardGround, 'tint' );

		const own = swatchOf();

		change( holderPane(), cardGround, 'counter' );

		return counter === pairs[around] && none === ( around === '' ? 'plain' : around ) && own === 'tint' && holderNow().attributes.ground === 'counter';
	} );
} )() );
check( '...a column that paints a ground of its own is what the card stands on - counter is the counter of that, none is that -, and a column whose picture is dimmed is dark, whatever it paints', ( function() {

	builder._closeDialog();
	builder._doc.model.blocks[0].settings.color = 'primary';
	builder._doc.model.blocks[0].cols[1].color = 'alt';
	builder._openSettings( cardAt );

	const own = swatchOf();

	change( holderPane(), cardGround, '' );

	const none = swatchOf();

	builder._closeDialog();
	builder._doc.model.blocks[0].cols[1].color = 'counter';
	builder._openSettings( cardAt );

	const counter = swatchOf();

	builder._closeDialog();
	builder._doc.model.blocks[0].cols[1].color = '';
	builder._doc.model.blocks[0].cols[1].background = { slot : '/template/page-home-card/story/column-picture', focus : null };
	builder._doc.model.blocks[0].cols[1].dim = true;
	builder._doc.model.blocks[0].settings.color = '';
	builder._openSettings( cardAt );

	const dimmed = swatchOf();

	builder._closeDialog();
	builder._doc.model.blocks[0].cols[1].background = null;
	builder._doc.model.blocks[0].cols[1].dim = false;
	builder._doc.model.blocks[0].settings.color = '';
	holderNow().attributes.ground = 'counter';
	builder._openSettings( cardAt );

	return own === 'plain' && none === 'alt' && counter === 'brand-alt' && dimmed === 'dark';
} )() );

check( '...what the fields write is the attributes of the card, as the call writes them - a switch is 1 or 0, a choice is its option -, into the card and into nothing else: every attribute that does not wait for a picture is given a value other than its default, and the card then holds the defaults of the schema with those', ( function() {

	const column = JSON.stringify( builder._doc.model.blocks[0].cols[1].components.slice( 1 ) )+ JSON.stringify( builder._doc.model.blocks[0].cols[0] );
	const written = {};
	const free = cardNames.filter( function( name ) { return cardPictured.indexOf( name ) === -1 } );

	free.forEach( function( name ) {

		const declared = cardDeclared[name];

		if( declared.type === 'bool' )
			written[name] = '1';
		else if( declared.type === 'select' )
			written[name] = declared.options.filter( function( option ) { return option !== cardDefaults[name] } ).pop();
		else
			written[name] = declared.type === 'int' ? String( declared.max ?? 3 ) : 'text';

		change( holderPane(), declared.label, declared.type === 'bool' ? true : written[name] );
	} );

	return free.length > 0 && free.every( function( name ) { return written[name] !== undefined && written[name] !== cardDefaults[name] } )
		&& JSON.stringify( holderNow().attributes ) === JSON.stringify( Object.assign( {}, cardDefaults, written ) )
		&& column === JSON.stringify( builder._doc.model.blocks[0].cols[1].components.slice( 1 ) )+ JSON.stringify( builder._doc.model.blocks[0].cols[0] ) && holderNow().components.length === 3 && Object.prototype.hasOwnProperty.call( holderNow(), 'content' ) === false
		&& free.every( function( name ) { return valueAt( holderPane(), cardDeclared[name].label ) === ( cardDeclared[name].type === 'bool' ? true : written[name] ) } );
} )() );
cardNames.filter( function( name ) { return cardPictured.indexOf( name ) === -1 } ).forEach( function( name ) {
	change( holderPane(), cardDeclared[name].label, cardDeclared[name].type === 'bool' ? false : cardDefaults[name] );
} );
check( '...taken back, a switch is 0 again and a choice with none is the empty word: the card holds the defaults of the schema again', JSON.stringify( holderNow().attributes ) === JSON.stringify( cardDefaults ) );

check( 'the focus of the picture is the grid of the one behind a section - a switch for none and nine places - and waits for a picture: while the card has none the switch and the grid are off, and so is the dimming', ( function() {

	const grid = controlOf( holderPane(), 'Focus' ).el;

	return grid.getAttribute('role') === 'radiogroup' && radiosOf( grid ).length === 9 && radiosOf( grid ).every( function( radio ) { return radio.disabled === true && radio.checked === false } ) && grid.classList.contains('is-disabled') === true
		&& controlOf( holderPane(), 'Automatic focus' ).el.disabled === true && valueAt( holderPane(), 'Automatic focus' ) === true && controlOf( holderPane(), 'Dim the picture' ).el.disabled === true && valueAt( holderPane(), 'Dim the picture' ) === false;
} )() );

holderNow().attributes.background = '/template/page-home-card/story/card-picture';
holderNow().attributes.focus = '';
redrawBlocks();
check( '...with a picture the switch for none is on, as the focus is none, and turning it off starts in the middle, as the picture of a section does; the grid chooses a place, written as its number - and none is empty', ( function() {

	const none = controlOf( holderPane(), 'Automatic focus' ).el;
	const before = [ none.disabled, valueAt( holderPane(), 'Automatic focus' ), holderNow().attributes.focus ].join();

	change( holderPane(), 'Automatic focus', false );

	const middle = holderNow().attributes.focus;
	const checkedAfter = valueAt( holderPane(), 'Focus' );

	change( holderPane(), 'Focus', '9' );

	const corner = holderNow().attributes.focus;

	change( holderPane(), 'Focus', '1' );

	const other = holderNow().attributes.focus;

	change( holderPane(), 'Automatic focus', true );

	return before === 'false,true,' && middle === '5' && checkedAfter === '5' && corner === '9' && other === '1' && holderNow().attributes.focus === '' && valueAt( holderPane(), 'Automatic focus' ) === true
		&& radiosOf( controlOf( holderPane(), 'Focus' ).el ).every( function( radio ) { return radio.checked === false } );
} )() );
check( '...the grid names its places to whoever does not see it, in the words of the picture of a section', controlOf( holderPane(), 'Focus' ).el.getAttribute('aria-label') === 'Focus' && radiosOf( controlOf( holderPane(), 'Focus' ).el ).map( function( radio ) { return radio.getAttribute('aria-label') } ).join()
	=== 'Top left,Top centre,Top right,Centre left,Centre,Centre right,Bottom left,Bottom centre,Bottom right' );
check( '...the dimming is a switch that is on for a picture, and writes 1 or 0 - and a picture taken away takes the dimming with it: the card has no picture, the attribute is 0, and the switch is off and greyed out', ( function() {

	const dim = controlOf( holderPane(), 'Dim the picture' ).el;
	const enabled = dim.disabled === false;

	change( holderPane(), 'Dim the picture', true );

	const on = holderNow().attributes.dim;

	buttonOf( holderPane(), 'Remove the picture' ).click();

	return enabled === true && on === '1' && holderNow().attributes.background === '' && holderNow().attributes.dim === '0' && controlOf( holderPane(), 'Dim the picture' ).el.disabled === true && valueAt( holderPane(), 'Dim the picture' ) === false
		&& controlOf( holderPane(), 'Automatic focus' ).el.disabled === true && buttonOf( holderPane(), 'Remove the picture' ).disabled === true && holderNow().slots === undefined;
} )() );
check( '...and the focus goes with it: a place chosen for the picture is none again, the attribute is empty, and the switch for none is on and greyed out with no place of the grid chosen', ( function() {

	holderNow().attributes.background = '/template/page-home-card/story/card-picture';
	redrawBlocks();
	change( holderPane(), 'Automatic focus', false );
	change( holderPane(), 'Focus', '9' );

	const placed = holderNow().attributes.focus;

	buttonOf( holderPane(), 'Remove the picture' ).click();

	return placed === '9' && holderNow().attributes.background === '' && holderNow().attributes.focus === '' && valueAt( holderPane(), 'Automatic focus' ) === true && controlOf( holderPane(), 'Automatic focus' ).el.disabled === true
		&& radiosOf( controlOf( holderPane(), 'Focus' ).el ).every( function( radio ) { return radio.checked === false && radio.disabled === true } );
} )() );

openCard();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
check( 'the focus of a component that is a picture itself - [image], whose picture is its source - is the grid as well, and is on from the start: no attribute of the type image comes before it, so there is no picture it would wait for', controlOf( dialogContent(), 'Focus' ).kind === 'group'
	&& controlOf( dialogContent(), 'Automatic focus' ).el.disabled === false && valueAt( dialogContent(), 'Automatic focus' ) === true && radiosOf( controlOf( dialogContent(), 'Focus' ).el ).every( function( radio ) { return radio.disabled === true } )
	&& ( change( dialogContent(), 'Automatic focus', false ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '5' ) && ( change( dialogContent(), 'Focus', '3' ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '3' )
	&& ( change( dialogContent(), 'Automatic focus', true ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '' ) );
builder._closeDialog();

console.log( '\nThe radius of a surface' );

// What the page says is the default of a kind of surface - the markers --radius-<kind>-default of its style sheets - reaches the panel as the radii of the registry: the server reads them,
// where the panel has no style sheet of the site. A stub stands for them here, set after the editor was opened, as the registry of the panel is made when it is
const surfaceKinds = [ 'cover', 'row', 'column', 'card', 'image' ];
const markersAre = function( word, over ) {

	const radii = {};

	surfaceKinds.forEach( function( kind ) { radii[kind] = word } );
	builder._registry.radii = Object.assign( radii, over || {} );
};

check( 'a component is a surface where the registry says so - the script names no component: the schema has the key surface, the card and the picture of the kernel say theirs, one that says none is none, and so is a value that is no word', builder._surfaceOf( registry().components.card ) === 'card'
	&& builder._surfaceOf( registry().components.image ) === 'image' && builder._surfaceOf( registry().components.title ) === '' && builder._surfaceOf( { surface : 'tile' } ) === 'tile' && builder._surfaceOf( { surface : 5 } ) === '' && builder._surfaceOf( {} ) === ''
	&& Object.keys( registry().components ).filter( function( name ) { return builder._surfaceOf( registry().components[name] ) !== '' } ).every( function( name ) { return surfaceKinds.indexOf( registry().components[name].surface ) !== -1 } ) );

check( 'the default of a kind of surface is the marker of the page, standard or alternative, from the registry: a kind with no marker, a marker that is no word of the two and a registry with none at all say the standard one, which is what the kernel says where no Design sets any', ( function() {

	open();
	markersAre( 'standard', { card : 'alternative', image : 'sideways', column : undefined } );

	const said = [ builder._radiusDefault('card'), builder._radiusDefault('image'), builder._radiusDefault('column'), builder._radiusDefault('cover'), builder._radiusDefault('nothing') ].join();

	delete builder._registry.radii;

	return said === 'alternative,standard,standard,standard,standard' && builder._radiusDefault('card') === 'standard';
} )() );

check( '...the strength in force on an element is the one it says - the setting is its class - or, where it says none, the default of its kind; an element that says the standard strength keeps it where the page\'s default is the alternative one', ( function() {

	open();

	const forced = function( marker ) {
		markersAre( marker );
		return [ builder._radiusIn( '', 'card' ), builder._radiusIn( 'standard', 'card' ), builder._radiusIn( 'alternative', 'card' ) ].join();
	};

	return forced('alternative') === 'alternative,standard,alternative' && forced('standard') === 'standard,standard,alternative';
} )() );

// A component that is a surface, opened with the marker said: what the toggle shows, what the model holds, and what it holds after each switch of the toggle in turn
const switched = function( at, marker, held, steps, over ) {

	openCard();
	markersAre( marker, over );
	builder._get( builder._doc.model, at ).radius = held;
	builder._renderEditor();
	builder._openSettings( at );

	const trail = [ valueAt( dialogContent(), radiusLabel ), builder._get( builder._doc.model, at ).radius ];

	steps.forEach( function( on ) {
		change( dialogContent(), radiusLabel, on );
		trail.push( builder._get( builder._doc.model, at ).radius );
	} );
	builder._closeDialog();

	return JSON.stringify( trail );
};
const shownPictureAt = [ 0, 0, 0 ];

check( 'the toggle of a card shows the strength in force: with the page\'s default standard it is off for a card with no class of its own and for one that says standard, on for one that says alternative - switched on it writes the alternative strength, switched off again it writes nothing, and a card that said alternative loses its class off',
	switched( cardAt, 'standard', '', [ true, false ] ) === '[false,"","alternative",""]' && switched( cardAt, 'standard', 'standard', [ true, false ] ) === '[false,"standard","alternative",""]'
	&& switched( cardAt, 'standard', 'alternative', [ false, true ] ) === '[true,"alternative","","alternative"]' );
check( '...and the mirror image with the alternative as the page\'s default: on for a card with no class of its own and for one that says alternative, off for one that says standard - switched off it writes the standard strength, switched on again it writes nothing',
	switched( cardAt, 'alternative', '', [ false, true ] ) === '[true,"","standard",""]' && switched( cardAt, 'alternative', 'alternative', [ false, true ] ) === '[true,"alternative","standard",""]' && switched( cardAt, 'alternative', 'standard', [ true ] ) === '[false,"standard",""]' );
check( '...a card the editor never touched follows a later change of the default - it shows what the page says now, and the model still holds nothing - and one the editor set keeps what was set; the default is the kind\'s own: the card\'s, not the picture\'s',
	switched( cardAt, 'standard', '', [] ) === '[false,""]' && switched( cardAt, 'alternative', '', [] ) === '[true,""]' && switched( cardAt, 'alternative', 'standard', [] ) === '[false,"standard"]'
	&& switched( cardAt, 'standard', '', [], { image : 'alternative' } ) === '[false,""]' && switched( cardAt, 'standard', 'alternative', [], { card : 'alternative' } ) === '[true,"alternative"]' );
check( '...a picture has the toggle as well, with the default of its own kind, and writes the same: the page\'s default for the picture is the alternative one here and the card\'s the standard one',
	switched( shownPictureAt, 'standard', '', [ false, true ], { image : 'alternative' } ) === '[true,"","standard",""]' && switched( shownPictureAt, 'standard', '', [ true, false ] ) === '[false,"","alternative",""]'
	&& switched( shownPictureAt, 'standard', 'alternative', [], { card : 'alternative' } ) === '[true,"alternative"]' );

// The toggle is the last field of the surface of a card and the one before the hover of a picture, in reach always: the card and the picture paint a surface of their own
check( '...it is in reach on a card and on a picture whatever they hold - they are a surface of their own - and a component that is none has no such toggle, and no radius of its own in the model', ( function() {

	openCard();
	builder._renderEditor();
	builder._openSettings( cardAt );

	const card = controlOf( dialogContent(), radiusLabel ).el.disabled === false && labelList( blockOf('surface') ).pop() === radiusLabel;

	builder._closeDialog();
	builder._openSettings( shownPictureAt );

	const image = controlOf( dialogContent(), radiusLabel ).el.disabled === false && labelList( blockOf('surface') ).join() === [ radiusLabel, hoverLabel ].join();

	builder._closeDialog();
	builder._openSettings( [ 0, 1, 0, 0 ] );

	const child = labelList( dialogContent() ).indexOf( radiusLabel ) === -1 && 'radius' in builder._get( builder._doc.model, [ 0, 1, 0, 0 ] ) === false;

	builder._closeDialog();

	return card === true && image === true && child === true;
} )() );

check( '...and a component the registry calls a surface by a name the script has never seen is one: the toggle is in its surface - a block it has for no other reason - and writes its radius, whatever the component is called', ( function() {

	open();
	builder._registry.components.stub = { label : 'Stub', source : 'content', preview : 'block', surface : 'tile' };
	builder._doc.model.blocks[0].cols[0].components.push( { name : 'stub', source : '', text : null, attributes : { 'class' : '' }, radius : '', content : '' } );
	markersAre( 'standard', { tile : 'alternative' } );
	builder._renderEditor();
	builder._openSettings( [ 0, 0, 3 ] );

	const blocked = blockIds().join() === 'content,surface,spacing,properties';
	const shown = blocked === true && valueAt( dialogContent(), radiusLabel ) === true;

	if( blocked === true )
		change( dialogContent(), radiusLabel, false );

	const written = builder._get( builder._doc.model, [ 0, 0, 3 ] ).radius;

	builder._closeDialog();

	return shown === true && written === 'standard';
} )() );

check( 'what the editor makes new has the setting where it means something: a section has the radius of its cover and of its row, a column its own - none of them says anything yet -, and a component the registry calls a surface has one too, which a component that is none does not', ( function() {

	const model = page();
	const section = builder._newSection( model );
	const column = builder._newCol();
	const made = function( name ) { return builder._newComponent( model, registry(), 'page-home', [ 0, 0 ], name, [] ) };
	const own = registry();

	own.components.stub = { label : 'Stub', source : 'text', preview : 'block', surface : 'tile' };

	return section.settings.radius === '' && section.settings.rowRadius === '' && column.radius === '' && made('card').radius === '' && made('image').radius === '' && 'radius' in made('title') === false
		&& builder._newComponent( model, own, 'page-home', [ 0, 0 ], 'stub', [] ).radius === '';
} )() );

// The toggle of a padded cover: the room of the page around a cover or a parallax is what it takes
doc = open();
markersAre('standard');
builder._renderEditor();
builder._openSettings( [ 0 ] );
check( 'the toggle of a cover sleeps unless the cover keeps the room of the page around it - Padded: the hero is a cover that does not, so it sleeps - and wakes with the switch; what it writes is the radius of the section',
	controlOf( dialogContent(), radiusLabel ).el.disabled === true && valueAt( dialogContent(), radiusLabel ) === false && ( change( dialogContent(), paddedLabel, true ), controlOf( dialogContent(), radiusLabel ).el.disabled === false )
	&& ( change( dialogContent(), radiusLabel, true ), settingsAt( 0 ).radius === 'alternative' ) && ( change( dialogContent(), radiusLabel, false ), settingsAt( 0 ).radius === '' ) && settingsOf().rowRadius === '' );
change( dialogContent(), radiusLabel, true );
change( dialogContent(), paddedLabel, false );
check( '...it sleeps again with the switch and keeps what it holds - the setting waits in the model, and the toggle shows it - and a parallax, which keeps the room as a cover does, wakes it', controlOf( dialogContent(), radiusLabel ).el.disabled === true && valueAt( dialogContent(), radiusLabel ) === true && settingsAt( 0 ).radius === 'alternative'
	&& ( change( dialogContent(), 'Cover', false ), change( dialogContent(), 'Parallax effect', true ), change( dialogContent(), paddedLabel, true ), controlOf( dialogContent(), radiusLabel ).el.disabled === false ) );
builder._closeDialog();

// The toggle of a row: what the row paints is its colour and its picture, which it has where the background stands inside
doc = open();
markersAre('standard');
builder._renderEditor();
builder._openSettings( [ 1 ] );
check( 'the toggle of the row sleeps where the row paints nothing - the background does not stand inside - and wakes when it does: the switch Background inside is in the same block, and the toggle follows it; it writes the radius of the row',
	controlOf( dialogContent(), radiusLabel ).el.disabled === true && ( change( dialogContent(), 'Background colour', 'alt' ), controlOf( dialogContent(), radiusLabel ).el.disabled === true )
	&& ( change( dialogContent(), 'Background inside', true ), controlOf( dialogContent(), radiusLabel ).el.disabled === false ) && ( change( dialogContent(), radiusLabel, true ), settingsAt( 1 ).rowRadius === 'alternative' && settingsAt( 1 ).radius === '' )
	&& valueAt( dialogContent(), radiusLabel ) === true );
check( '...it is the toggle of the corners of the section for the one that paints: with the colour that takes the background inside with it the corners are the cover\'s again, which sleeps - there is no room of the page around it - and the row keeps what it holds, as does a cover that takes the background inside with it', ( function() {

	change( dialogContent(), 'Background colour', '' );

	const colour = controlOf( dialogContent(), radiusLabel ).el.disabled === true && valueAt( dialogContent(), radiusLabel ) === false && settingsAt( 1 ).inner === false && settingsAt( 1 ).rowRadius === 'alternative';

	change( dialogContent(), 'Background colour', 'alt' );
	change( dialogContent(), 'Background inside', true );

	const woken = controlOf( dialogContent(), radiusLabel ).el.disabled === false && valueAt( dialogContent(), radiusLabel ) === true;

	change( dialogContent(), 'Cover', true );

	return colour === true && woken === true && controlOf( dialogContent(), radiusLabel ).el.disabled === true && settingsAt( 1 ).inner === false && settingsAt( 1 ).rowRadius === 'alternative';
} )() );
builder._closeDialog();

// The toggle of a column: the ground it paints and the picture behind it
doc = open();
markersAre('standard');
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
builder._openBlock('surface');
check( 'the toggle of a column sleeps where the column paints nothing - no ground, no picture - and wakes with either; it writes the radius of the column and keeps it while it sleeps',
	controlOf( dialogContent(), radiusLabel ).el.disabled === true && ( change( dialogContent(), 'Background colour', 'counter' ), controlOf( dialogContent(), radiusLabel ).el.disabled === false )
	&& ( change( dialogContent(), radiusLabel, true ), builder._doc.model.blocks[1].cols[0].radius === 'alternative' ) && ( change( dialogContent(), 'Background colour', '' ), controlOf( dialogContent(), radiusLabel ).el.disabled === true )
	&& valueAt( dialogContent(), radiusLabel ) === true && builder._doc.model.blocks[1].cols[0].radius === 'alternative' );
builder._doc.model.blocks[1].cols[0].background = { slot : '/template/page-home/services/background', focus : 5 };
redrawBlocks();
check( '...a picture behind the column wakes it as a ground does', controlOf( dialogContent(), radiusLabel ).el.disabled === false );
builder._closeDialog();

console.log( '\nThe hover of a picture' );

// A picture is what the registry says it is - a schema whose source is an image: the frame of [image], the picture of a card and a component of a feature the script has never seen - and the script
// names none. Each is made as the editor makes one and set where its schema lets it stand: in the card of the card page where it names the place the card holds, in the first column otherwise
const pictureNames = Object.keys( registry().components ).filter( function( name ) { return registry().components[name].source === 'image' } );
let hoverAt = [];
const pictureOpened = function( name, over ) {

	openCard();
	builder._registry = over ?? registry();

	const model = builder._doc.model;
	const container = cardIn( model );
	const schema = builder._registry.components[name];
	const inCard = Array.isArray( schema['in'] ) === true && schema['in'].indexOf( builder._holds( builder._registry, container.name ) ) !== -1;
	const holder = inCard === true ? container.components : model.blocks[0].cols[0].components;

	holder.push( builder._newComponent( model, builder._registry, 'page-home-card', inCard === true ? cardAt : [ 0, 0 ], name, [] ) );
	hoverAt = inCard === true ? cardAt.concat( [ holder.length - 1 ] ) : [ 0, 0, holder.length - 1 ];
	builder._renderEditor();
	builder._openSettings( hoverAt );

	return builder._get( model, hoverAt );
};
// The blocks of the dialog of a picture, and what its block for the surface lists
const surfaceOfPicture = function( name ) {

	pictureOpened( name );

	const block = blockOf('surface');
	const found = [ block === null ? [] : labelList( block ), blockIds() ];

	builder._closeDialog();

	return found;
};
// Whether the dialog that is open has the switch of the hover: a picture that has none fails its check and does not stop the script
const hoverIn = function() { return blockOf('surface') !== null && labelList( blockOf('surface') ).indexOf( hoverLabel ) !== -1 };

check( 'a picture has the switch of the hover in the block for its surface, whatever the schema says it paints: every component whose source is an image - the frame of [image] and the picture of a card among them -, after the radius where the picture has one, and the block is in the place the table gives it',
	[ 'image', 'card-image' ].every( function( name ) { return pictureNames.indexOf( name ) !== -1 } ) && pictureNames.every( function( name ) {

		const [ labels, ids ] = surfaceOfPicture( name );
		const radius = builder._surfaceOf( registry().components[name] ) !== '';

		return ids.indexOf('surface') !== -1 && inTableOrder( 'component', ids ) && labels.join() === ( radius === true ? [ radiusLabel, hoverLabel ] : [ hoverLabel ] ).join();
	} ) );

check( '...a component that is no picture has no such switch and no setting hover of its own: a text has no block for its surface at all, a card has the one for its radius and its picture alone - the table lists the hover in the surface of a component, after the radius', ( function() {

	openCard();
	builder._renderEditor();
	builder._openSettings( [ 0, 1, 0, 1 ] );

	const text = blockIds().indexOf('surface') === -1 && labelList( dialogContent() ).indexOf( hoverLabel ) === -1;

	builder._closeDialog();
	builder._openSettings( cardAt );

	const card = labelList( blockOf('surface') ).indexOf( hoverLabel ) === -1 && 'hover' in builder._get( builder._doc.model, cardAt ) === false;
	const controls = builder.BLOCKS.component.filter( function( block ) { return block.id === 'surface' } )[0].controls;

	builder._closeDialog();

	return text === true && card === true && controls.indexOf('hover') === controls.indexOf('radius') + 1 && controls.indexOf('radius') !== -1
		&& builder.BLOCKS.component.filter( function( block ) { return block.controls.indexOf('hover') !== -1 } ).length === 1
		&& [ 'section', 'column' ].every( function( kind ) { return builder.BLOCKS[kind].every( function( block ) { return block.controls.indexOf('hover') === -1 } ) } );
} )() );

check( '...the switch is marked with the key it writes, the table holds that key in the surface of the picture and the form draws it there, once - and the sentence it has to say is behind its question mark, in words the panel has', ( function() {

	pictureOpened('card-image');

	const drawn = hoverIn() === true && keysDrawn('surface').join() === 'hover' && keysHeld('surface').indexOf('hover') !== -1;
	const tip = drawn === true ? tipOf( controlOf( blockOf('surface'), hoverLabel ) ) : null;

	builder._closeDialog();

	return drawn === true && typeof tip === 'string' && tip !== '' && tip === words['/_admin/builder/hint/hover'] && hoverLabel !== undefined && hoverLabel !== '';
} )() );

check( '...switching it writes the setting - on, or empty - and each switch is a change of the document: the same for every picture, whatever it is called, and the switch shows what the picture holds', pictureNames.every( function( name ) {

	const node = pictureOpened( name );

	if( hoverIn() === false ) {
		builder._closeDialog();
		return false;
	}

	const before = valueAt( blockOf('surface'), hoverLabel ) === false && node.hover === '';

	changes = 0;
	change( dialogContent(), hoverLabel, true );

	const on = node.hover === 'on' && changes === 1 && valueAt( blockOf('surface'), hoverLabel ) === true;

	change( dialogContent(), hoverLabel, false );

	const off = node.hover === '' && changes === 2 && valueAt( blockOf('surface'), hoverLabel ) === false;

	builder._closeDialog();
	builder._openSettings( hoverAt );
	node.hover = 'on';
	redrawBlocks();

	const held = valueAt( blockOf('surface'), hoverLabel ) === true;

	builder._closeDialog();

	return before === true && on === true && off === true && held === true;
} ) );

check( '...the head of the block names it while it is on - after the radius the picture says, in the order of the table - and says nothing of it while it is off', pictureNames.every( function( name ) {

	const node = pictureOpened( name );

	if( hoverIn() === false ) {
		builder._closeDialog();
		return false;
	}

	const radius = 'radius' in node;

	if( radius === true )
		node.radius = 'alternative';

	redrawBlocks();

	const said = function() { return saysOf('surface').hidden === true ? '' : saysOf('surface').textContent };
	const off = said() === ( radius === true ? radiusLabel : '' );

	change( dialogContent(), hoverLabel, true );

	const on = said() === ( radius === true ? [ radiusLabel, hoverLabel ] : [ hoverLabel ] ).join(' · ');

	change( dialogContent(), hoverLabel, false );
	builder._closeDialog();

	return off === true && on === true && said() === ( radius === true ? radiusLabel : '' );
} ) );

check( '...and a component the registry calls a picture by a name the script has never seen has the switch: it is in the surface - a block it has for no other reason - and writes its hover, whatever the component is called', ( function() {

	const own = registry();

	own.components.stub = { label : 'Stub', source : 'image', preview : 'block' };

	const node = pictureOpened( 'stub', own );
	const blocked = hoverIn() === true && labelList( blockOf('surface') ).join() === hoverLabel && node.hover === '';

	if( blocked === true )
		change( dialogContent(), hoverLabel, true );

	const written = node.hover;

	builder._closeDialog();

	return blocked === true && written === 'on';
} )() );

check( 'what the editor makes new has the setting where it means something: a component whose source is an image has the hover, off - the picture of a card and one of a feature the script has never seen among them - and a component that is none does not have it', ( function() {

	const model = page();
	const own = registry();

	own.components.stub = { label : 'Stub', source : 'image', preview : 'block' };

	return Object.keys( own.components ).length > pictureNames.length + 1 && Object.keys( own.components ).every( function( name ) {

		const made = builder._newComponent( model, own, 'page-home', [ 0, 0 ], name, [] );

		return own.components[name].source === 'image' ? made.hover === '' : 'hover' in made === false;
	} );
} )() );

console.log( '\nThe picture behind a card' );

openCard();
builder._renderEditor();
builder._openSettings( cardAt );
builder._openBlock('surface');

check( 'the picture behind a card is a source row - the label of the attribute over it, what it is now below it, the button of the dialog of the sources beside it - and under it the upload and the way to take it away; it has no alt text: the picture behind a content is decoration', ( function() {

	const row = holderPane().querySelector('.builder-source');

	return row !== null && row.querySelector('.builder-source-name').hidden === false && row.querySelector('.builder-source-name').textContent === 'Background picture' && row.querySelector('.builder-source-current').textContent === 'none'
		&& row.querySelector('.builder-source-caption').textContent === 'Current image slot:' && iconOf( row.querySelector('button') ) === 'source' && fileField( holderPane() ) !== null && thumbOf( holderPane() ).hidden === true
		&& labelList( holderPane() ).filter( function( label ) { return /^Alt text/.test( label ) } ).length === 0 && buttonOf( holderPane(), 'Remove the picture' ).disabled === true && holderPane().querySelector('.builder-source-red').hidden === true;
} )() );

requests = [];
changes = 0;
choose( holderPane(), house );
check( '...a picture chosen for a card that has none makes a slot of the kind of a new one - named after the component and the attribute, free in the section, the size a new slot starts with, and for its label the section, the component and the attribute', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home-card/story/card-background","label":"story: Card - Background picture","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the picture of the card is that slot, with nothing left to make - the slot is there - and the document is changed', holderNow().attributes.background === '/template/page-home-card/story/card-background' && holderNow().slots === undefined && actionsOf() === 'slots/create,images/upload' && changes === 1 );
requests[1].callback( 200, { filename : 'f', url : '/uploads/card.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the form is drawn again with it: the thumbnail, the focus and the dimming are on, the picture can be taken away, and the preview says that a picture is behind the card, not behind the column or the section', thumbOf( holderPane() ).hidden === false && thumbOf( holderPane() ).src.indexOf('/uploads/card.jpg') === 0
	&& controlOf( holderPane(), 'Automatic focus' ).el.disabled === false && controlOf( holderPane(), 'Dim the picture' ).el.disabled === false && buttonOf( holderPane(), 'Remove the picture' ).disabled === false && noteOf( holderPane() ).textContent === 'The picture is stored.'
	&& preview().querySelector('[data-path="0.1.0"] .is-background') !== null && preview().querySelector('[data-path="0.1.0"] .is-background').title === 'Picture behind the card' && preview().querySelector('[data-path="0.1"] .builder-pcol-head .is-background') === null
	&& preview().children[0].querySelector('.builder-frame-head .is-background') === null && openBlock() === 'surface' );
builder._closeDialog();

openCard();
builder._renderEditor();
builder._openSettings( cardAt );
builder._openBlock('surface');
buttonOf( holderPane(), 'Source' ).click();
check( 'the picture behind a card is chosen in the dialog of the sources, with the tabs of a picture - Choose a value and Create new, no Static -, and a card in a static column offers the slots of the project and no field of an element', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ) ) === '[["pick","Choose a value"],["new","Create new"]]'
	&& pickerPane('pick').querySelector('.builder-label').textContent === 'Image slot' && labelList( pickerPane('pick') ).indexOf('Field of the element') === -1 && pickerPane('pick').querySelector('.builder-source-current').textContent === 'none' );
( function() {
	const search = pickerPane('pick').querySelector('input[type="search"]');
	search.value = '';
	search.dispatch('input');
} )();
pickItems()[1].click();
check( '...a slot that is chosen is the picture: the attribute holds it, there is nothing to make, and the form follows - the row, the thumbnail of a slot that has none is out of sight, the dimming and the focus wait no more', holderNow().attributes.background === '/project/logo/header/image' && holderNow().slots === undefined && pickerBox().open === false
	&& holderPane().querySelector('.builder-source-current').textContent === '/project/logo/header/image' && controlOf( holderPane(), 'Dim the picture' ).el.disabled === false && buttonOf( holderPane(), 'Remove the picture' ).disabled === false );
buttonOf( holderPane(), 'Source' ).click();
buttonOf( pickerStrip(), 'Create new' ).click();
check( 'Create new: a new slot is named after the component and the attribute, in the section, and has the size a new slot starts with', fresh('.builder-new-title').textContent === 'New image slot' && fresh('.builder-new-name').value === 'card-background' && fresh('.builder-new-uri').textContent === '/template/page-home-card/story/card-background'
	&& fresh('.builder-new-size').hidden === false && fresh('.builder-new-width').value === '1600' && fresh('.builder-new-height').value === '900' );
fresh('.builder-new-width').value = '800';
fresh('.builder-new-width').dispatch('input');
fresh('.builder-new-use').click();
check( '...using it makes the slot the picture of the card, and the mark that makes it when the document is saved stays on the card - under the name of the attribute, with the label and the size - and on nothing else', holderNow().attributes.background === '/template/page-home-card/story/card-background'
	&& JSON.stringify( holderNow().slots ) === '{"background":{"label":"Card - Background picture","width":800,"height":900}}' && holderNow().create === undefined && pickerBox().open === false && preview().querySelector('[data-path="0.1.0"] .is-background') !== null
	&& holderPane().querySelector('.builder-source-current').textContent === '/template/page-home-card/story/card-background' );
check( '...a slot that is yet to be made has an upload: the first picture makes it from the mark, the size it says, and the mark is gone once it is there', ( function() {

	requests = [];
	choose( holderPane(), house );

	const first = actionsOf() === 'slots/create' && JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home-card/story/card-background","label":"Card - Background picture","width":800,"height":900}';

	requests[0].callback( 200, { ok : true } );

	return first && holderNow().slots === undefined && holderNow().attributes.background === '/template/page-home-card/story/card-background' && actionsOf() === 'slots/create,images/upload';
} )() );
requests[1].callback( 200, { filename : 'f', url : '/uploads/made.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
buttonOf( holderPane(), 'Remove the picture' ).click();
check( '...taking the picture away empties the attribute and puts the mark away with it: nothing is left to make, and the preview says no picture is behind the card', holderNow().attributes.background === '' && holderNow().slots === undefined && preview().querySelector('[data-path="0.1.0"] .is-background') === null
	&& holderPane().querySelector('.builder-source-current').textContent === 'none' && buttonOf( holderPane(), 'Remove the picture' ).disabled === true );
builder._closeDialog();

holderNow().attributes.background = '/template/page-home-card/story/card-picture';
holderNow().slots = { background : { label : 'Card - Background picture', width : 800, height : 400, alt : { en_US : 'Waves' } } };
builder._openSettings( cardAt );
check( 'the mark of a slot that is made later holds an alt text the card does not show or edit: the picture behind a card has none to give, and the mark is kept as it is', holderNow().slots.background.alt.en_US === 'Waves' && labelList( holderPane() ).filter( function( label ) { return /^Alt text/.test( label ) } ).length === 0 );
builder._closeDialog();

openCard();
builder._renderEditor();
builder._openSettings( [ 1, 0, 0 ] );
check( 'the picture behind a card in a loop is a field of the element or a slot: the dialog of the sources offers the image fields of the type next to the slots, and the card of the page reads the field "image"', holderPane().querySelector('.builder-source-current').textContent === 'image' && fileField( holderPane() ) === null
	&& buttonOf( holderPane(), 'Remove the picture' ).disabled === false && controlOf( holderPane(), 'Dim the picture' ).el.disabled === false && valueAt( holderPane(), 'Dim the picture' ) === true && valueAt( holderPane(), 'Focus' ) === '5' );
buttonOf( holderPane(), 'Source' ).click();
check( '...Choose a value: the field of the element - the fields of the type that hold a picture, and none - above the slots', labelList( pickerPane('pick') )[0] === 'Field of the element' && controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=none,image=image'
	&& valueAt( pickerPane('pick'), 'Field of the element' ) === 'image' );
change( pickerPane('pick'), 'Field of the element', '' );
check( '...none empties the attribute, which takes the dimming with it and leaves nothing to upload to: the upload is there for a slot that is yet to be, and the form is drawn again', builder._get( builder._doc.model, [ 1, 0, 0 ] ).attributes.background === '' && builder._get( builder._doc.model, [ 1, 0, 0 ] ).attributes.dim === '0'
	&& fileField( holderPane() ) !== null && controlOf( holderPane(), 'Dim the picture' ).el.disabled === true && valueAt( holderPane(), 'Dim the picture' ) === false && pickerBox().open === false );
builder._closeDialog();

builder._get( builder._doc.model, [ 1, 0, 0 ] ).attributes.background = 'photo';
builder._repaint();
builder._openSettings( [ 1, 0, 0 ] );
check( '...a field the type has no such field of is red where the source stands, in the form and on the card in the preview, and the document is not saved with it', holderPane().querySelector('.builder-source-red').hidden === false && holderPane().querySelector('.builder-source-red').textContent === words['/_admin/builder/tree/red-field']
	&& preview().querySelector('[data-path="1.0.0"]').classList.contains('is-red') === true && ( function() { requests = []; builder._save(); return requests.length === 0 && builder._status.state === 'error' } )() );
builder._closeDialog();

console.log( '\nThe card in the preview' );

openCard();
builder._renderEditor();

const colBody = function( at ) { return frameOf( at ).querySelector('.builder-col-body') };
check( 'a component that holds components is a frame of its own in the body of its column - lighter than the frame of a column, a card with a head of the same three parts - and the column ends in its own button, as it did', classes( colBody('0.1').children ) === 'builder-card builder-pick builder-color-alt,builder-adds'
	&& classes( frameOf('0.1.0').children ) === 'builder-card-head,builder-card-body' && classes( frameOf('0.1.0').querySelector('.builder-card-head').children ) === 'builder-head-title,builder-head-status,builder-tools' && frameOf('0.1.0').localName === 'div'
	&& texts( frameOf('0.1.0').querySelectorAll('.builder-card-name') ) === 'Card' );
check( '...its body has its children as placeholders, in order, each a frame of its own with its tools, and ends in a button that adds a child; the placeholders say what the components draw', classes( frameOf('0.1.0').querySelector('.builder-card-body').children ) === 'builder-ph has-draws builder-pick,builder-ph builder-pick,builder-ph has-draws builder-pick,builder-adds'
	&& frameOf('0.1.0').querySelectorAll('.builder-ph').map( function( ph ) { return ph.dataset.path } ).join() === '0.1.0.0,0.1.0.1,0.1.0.2' && frameOf('0.1.0.0').querySelector('.builder-ph-draws').textContent === 'H3 .nino-card-title'
	&& frameOf('0.1.0.0').querySelectorAll('button').map( function( button ) { return button.dataset.tool } ).join() === 'settings,html,up,down,duplicate,delete' && frameOf('0.1.0').querySelector('.builder-adds .builder-add-button').textContent === '+ Component' );
check( '...a card with no picture behind it says nothing in its head; the head of the column and the head of the section say nothing of the card', frameOf('0.1.0').querySelector('.builder-head-status').children.length === 0 && frameOf('0.1').querySelector('.builder-pcol-head .builder-head-status').children.length === 0
	&& frameOf('0.1.0').querySelector('.builder-card-head .builder-rename') === null );
check( '...a card in a loop is drawn once, in the cell of each element as the components of the column are - and the loop is in the head of its column, not in the card', preview().querySelectorAll('[data-path="1.0"] .builder-card').length === 1 && frameOf('1.0.0').querySelectorAll('.builder-ph').length === 1
	&& frameOf('1.0').querySelector('.builder-pcol-head .is-loop') !== null && frameOf('1.0.0').querySelector('.is-loop') === null );

const cardTintOf = function( around, color, ground, picture, dim ) {
	openCard();
	builder._doc.model.blocks[0].settings.color = around;
	builder._doc.model.blocks[0].cols[1].color = color;
	cardIn( builder._doc.model ).attributes.ground = ground;
	cardIn( builder._doc.model ).attributes.background = picture === true ? '/template/page-home-card/story/card-picture' : '';
	cardIn( builder._doc.model ).attributes.dim = dim === true ? '1' : '0';
	builder._renderEditor();
	return frameOf('0.1.0').className.split(' ').filter( function( name ) { return name.indexOf('builder-color-') === 0 } ).join();
};
check( 'a card that paints a ground is tinted as a column is - builder-color-<ground> - and one that paints none has no tint of its own', cardTintOf( '', '', '', false, false ) === '' && cardTintOf( '', '', 'alt', false, false ) === 'builder-color-alt' && cardTintOf( '', '', 'tint', false, false ) === 'builder-color-tint'
	&& cardTintOf( '', '', 'dark', false, false ) === 'builder-color-dark' && cardTintOf( 'dark', 'black', 'primary', false, false ) === 'builder-color-primary' && cardTintOf( '', '', 'brand-alt', false, false ) === 'builder-color-brand-alt' && cardTintOf( 'tint', '', '', false, false ) === '' );
check( '...counter is the counter of the ground of the column - or of the section where the column has none - by the pairs of Nino.css: default and alt, tint to default, dark and black, primary and brand-alt - and the default is the plain frame', cardTintOf( '', '', 'counter', false, false ) === 'builder-color-alt'
	&& cardTintOf( 'alt', '', 'counter', false, false ) === 'builder-color-plain' && cardTintOf( 'tint', '', 'counter', false, false ) === 'builder-color-plain' && cardTintOf( 'dark', '', 'counter', false, false ) === 'builder-color-black' && cardTintOf( 'black', '', 'counter', false, false ) === 'builder-color-dark'
	&& cardTintOf( 'primary', '', 'counter', false, false ) === 'builder-color-brand-alt' && cardTintOf( 'brand-alt', '', 'counter', false, false ) === 'builder-color-primary'
	&& cardTintOf( 'primary', 'alt', 'counter', false, false ) === 'builder-color-plain' && cardTintOf( 'alt', 'dark', 'counter', false, false ) === 'builder-color-black' && cardTintOf( 'dark', 'counter', 'counter', false, false ) === 'builder-color-dark' );
check( '...a card whose picture is dimmed is tinted dark, whatever ground it names, and whatever its column is; a dimming without a picture is no ground, and a picture that is not dimmed paints nothing', cardTintOf( '', '', '', true, true ) === 'builder-color-dark' && cardTintOf( 'tint', 'alt', 'counter', true, true ) === 'builder-color-dark'
	&& cardTintOf( '', '', 'alt', true, true ) === 'builder-color-dark' && cardTintOf( '', '', '', false, true ) === '' && cardTintOf( '', '', 'alt', false, true ) === 'builder-color-alt' && cardTintOf( '', '', '', true, false ) === '' && cardTintOf( '', '', 'alt', true, false ) === 'builder-color-alt' );
check( '...the section and the column keep their own ground, whatever the card paints', ( cardTintOf( 'dark', '', 'counter', false, false ), frameOf('0.1').classList.contains('builder-color-black') === false && frameOf('0.1').classList.contains('builder-color-dark') === false && preview().children[0].classList.contains('builder-color-dark') ) );
check( '...a picture behind the card is said in its head with the item the section and the column have for it - an icon of an image and the word for a card - and nowhere else: a card with one, in a column that has its own', ( function() {

	cardTintOf( '', '', '', true, false );

	const item = frameOf('0.1.0').querySelector('.builder-card-head .is-background');

	return item !== null && item.localName === 'span' && item.title === 'Picture behind the card' && item.title !== words['/_admin/builder/preview/background'] && item.title !== words['/_admin/builder/preview/column-background'] && item.getAttribute('role') === 'img'
		&& item.getAttribute('aria-label') === item.title && iconOf( item ) === 'image' && frameOf('0.1').querySelector('.builder-pcol-head .is-background') === null && frameOf('0.1.0').querySelectorAll('.is-background').length === 1;
} )() );
check( '...and the German file has the word, one of its own', ( function() {
	const german = fs.readFileSync( path.join( __dirname, '../text/de_DE.php' ), 'utf8' );
	const card = /^\s*'\[\[\/_admin\/builder\/preview\/card-background\]\]'\s*=>\s*'(.*)',\s*$/m.exec( german );
	return card !== null && card[1] === 'Bild hinter der Karte' && words['/_admin/builder/preview/card-background'] === 'Picture behind the card';
} )() );

openCard();
builder._renderEditor();
const namesOf = function() { return cardIn( builder._doc.model ).components.map( function( child ) { return child.name } ).join() };
check( 'the tools of a frame at depth four: a child can go up and down in its card, and the first has no step up as the last has none down - both are greyed out', toolOf( '0.1.0.0', 'up' ).disabled === true && toolOf( '0.1.0.0', 'down' ).disabled === false && toolOf( '0.1.0.2', 'down' ).disabled === true
	&& toolOf( '0.1.0.2', 'up' ).disabled === false && toolOf( '0.1.0.1', 'up' ).disabled === false && toolOf( '0.1.0.1', 'down' ).disabled === false );
toolOf( '0.1.0.1', 'up' ).click();
check( '...up moves the child one place up in the list of its card and keeps it selected, at its new path', namesOf() === 'text,card-title,button' && builder._same( builder._sel, [ 0, 1, 0, 0 ] ) && frameOf('0.1.0.0').classList.contains('is-selected') && frameOf('0.1.0.0').querySelector('.builder-ph-label').textContent === 'Text' && changes === 1 );
toolOf( '0.1.0.0', 'down' ).click();
toolOf( '0.1.0.1', 'down' ).click();
check( '...down one place down; a card has the order its children have in the model', namesOf() === 'card-title,button,text' && builder._same( builder._sel, [ 0, 1, 0, 2 ] ) );
toolOf( '0.1.0.2', 'duplicate' ).click();
check( '...duplicate puts a copy right after the child, with a key of its own, and selects the copy', namesOf() === 'card-title,button,text,text' && builder._same( builder._sel, [ 0, 1, 0, 3 ] ) && builder._get( builder._doc.model, [ 0, 1, 0, 3 ] ).source === '/template/page-home-card/story/text-2'
	&& JSON.stringify( builder._get( builder._doc.model, [ 0, 1, 0, 3 ] ).create ) === '{}' && frameOf('0.1.0.3').classList.contains('is-selected') && builder._fresh.story === undefined );
questions = [];
toolOf( '0.1.0.3', 'delete' ).click();
check( '...delete takes a child out without a question, since it holds nothing, and selects the card it stood in', namesOf() === 'card-title,button,text' && questions.length === 0 && builder._same( builder._sel, cardAt ) && frameOf('0.1.0').classList.contains('is-selected') );

toolOf( '0.1.0', 'duplicate' ).click();
check( 'the tools of the card are those of a component of a column: duplicate copies it with its children, right after it, and selects the copy; the card has no step up or down in a column that holds nothing else, and has them next to another', builder._doc.model.blocks[0].cols[1].components.length === 2
	&& builder._get( builder._doc.model, [ 0, 1, 1 ] ).components.length === 3 && builder._same( builder._sel, [ 0, 1, 1 ] ) && toolOf( '0.1.0', 'up' ).disabled === true && toolOf( '0.1.0', 'down' ).disabled === false && toolOf( '0.1.1', 'up' ).disabled === false && toolOf( '0.1.1', 'down' ).disabled === true );
toolOf( '0.1.1', 'up' ).click();
check( '...moving a card moves its children with it', builder._same( builder._sel, [ 0, 1, 0 ] ) && builder._get( builder._doc.model, [ 0, 1, 0 ] ).components[0].source === '/template/page-home-card/story/title-2' && frameOf('0.1.0.0').dataset.path === '0.1.0.0' && frameOf('0.1.0').classList.contains('is-selected') );
questions = [];
toolOf( '0.1.0', 'delete' ).click();
check( '...deleting a card that holds children asks first, as a frame that holds work does - and the question names a component; the answer decides', questions.length === 1 && questions[0].title === 'Component' && builder._doc.model.blocks[0].cols[1].components.length === 2 );
questions[0].onChoose('cancel');
check( '...Cancel leaves it where it is', builder._doc.model.blocks[0].cols[1].components.length === 2 );
questions[0].onChoose('delete');
check( '...Delete takes the card with its children out of the column, and selects the column', builder._doc.model.blocks[0].cols[1].components.length === 1 && builder._same( builder._sel, [ 0, 1 ] ) && preview().querySelectorAll('[data-path="0.1"] .builder-card').length === 1 );

openCard();
builder._renderEditor();
builder._doc.model.blocks[0].cols[1].components[0].components = [];
builder._renderEditor();
questions = [];
toolOf( '0.1.0', 'delete' ).click();
check( '...a card with no children is deleted without a question, like an empty column', questions.length === 0 && builder._doc.model.blocks[0].cols[1].components.length === 0 );

openCard();
builder._renderEditor();
frameOf('0.1.0.1').click();
check( 'a click on a child selects the child and not the card it stands in, nor the column; a click on the card selects the card, and on the head of a column the column', builder._same( builder._sel, [ 0, 1, 0, 1 ] ) && frameOf('0.1.0.1').classList.contains('is-selected') && frameOf('0.1.0').classList.contains('is-selected') === false && frameOf('0.1').classList.contains('is-selected') === false );
frameOf('0.1.0').querySelector('.builder-card-head').click();
check( '...the head of the card selects the card', builder._same( builder._sel, cardAt ) && frameOf('0.1.0').classList.contains('is-selected') && frameOf('0.1.0.1').classList.contains('is-selected') === false );
frameOf('0.1').querySelector('.builder-pcol-head').click();
check( '...and the head of the column the column', builder._same( builder._sel, [ 0, 1 ] ) && frameOf('0.1').classList.contains('is-selected') === true && frameOf('0.1.0').classList.contains('is-selected') === false );
frameOf('0.1.0').dispatch('dblclick');
check( 'a double click on the card opens the form of the card, one on a child the form of that component', dialogBox().open === true && dialogText('builder-dialog-kind') === 'Card' && blockIds().join() === 'link,surface,spacing,properties' );
frameOf('0.1.0.1').dispatch('dblclick');
check( '...the form of a component at depth four is the form every component has: for a text that is a link its content, its link, its spacing and its properties', dialogBox().open === true && dialogText('builder-dialog-kind') === 'Component "Text"' && blockIds().join() === 'content,link,spacing,properties' );
builder._closeDialog();
toolOf( '0.1.0', 'settings' ).click();
check( 'the tool for the settings selects the frame and opens its form: the card\'s', dialogText('builder-dialog-kind') === 'Card' && builder._same( builder._sel, cardAt ) );
toolOf( '0.1.0.2', 'settings' ).click();
check( '...and a child\'s', dialogText('builder-dialog-kind') === 'Component "Button"' && builder._same( builder._sel, [ 0, 1, 0, 2 ] ) && dialogBox().open === true );
builder._closeDialog();

check( 'the pointer is over the innermost frame: a child of a card, not the card nor the column - the tools of the frame it is over are seen', ( function() {

	frameOf('0.1.0.1').querySelector('.builder-ph-label').dispatch('mouseover');

	const child = frameOf('0.1.0.1').classList.contains('is-hover') && frameOf('0.1.0').classList.contains('is-hover') === false && frameOf('0.1').classList.contains('is-hover') === false;

	frameOf('0.1.0').querySelector('.builder-card-name').dispatch('mouseover');

	return child && frameOf('0.1.0').classList.contains('is-hover') && frameOf('0.1.0.1').classList.contains('is-hover') === false;
} )() );

console.log( '\nEvery form of a card, what it says when it is not read, and what a section keeps when it is renamed or read again' );

openCard();
builder._renderEditor();
builder._doc.model.blocks[1].cols[0].components[0].attributes.background = '';
cardIn( builder._doc.model ).attributes.background = '/project/logo/header/image';

const cardPaths = [];
builder._doc.model.blocks.forEach( function( block, b ) {
	cardPaths.push( [ b ] );
	( block.cols || [] ).forEach( function( col, c ) {
		cardPaths.push( [ b, c ] );
		if( col.stack !== null )
			cardPaths.push( [ b, c, 'x' ] );
		col.components.forEach( function( component, k ) {
			cardPaths.push( [ b, c, k ] );
			( component.components || [] ).forEach( function( child, j ) { cardPaths.push( [ b, c, k, j ] ) } );
		} );
	} );
} );
cardPaths.push( [] );

faults.length = 0;
check( 'the form of every node of a page with cards can be drawn: '+ cardPaths.length+ ' of them, the cards and their children among them, in each view of the preview - and so can their tools and the buttons that add to them', builder.VIEWS.every( function( view ) {
	builder._setView( view );
	return cardPaths.filter( function( at ) {
		return drawn( 'settings '+ at.join('.')+ ' in '+ view, function() {
			builder._openSettings( at );
			inspect( 'settings '+ at.join('.')+ ' in '+ view, dialogBox() );
			if( at[at.length - 1] !== 'x' && at.length > 0 )
				builder._tools( at );
		} ) === false;
	} ).length === 0 && drawn( 'preview '+ view, function() {
		builder._pickComponent( cardAt, made('button') );
		builder._addsRow( cardAt );
		inspect( 'preview '+ view, preview() );
	} );
} ) && cardPaths.length === 14 );
builder._setView('l');
builder._closeDialog();
check( '...every icon of them has a title and a label that say the same, every group of choices - the grid of the focus among them - is a radio group a screen reader reads as a field, every tab has its pane', faults.length === 0 );
if( faults.length > 0 )
	console.log( '      '+ faults.slice( 0, 6 ).join( '\n      ' ) );

check( 'a model that php sent with empty lists for maps has them as maps in the children of a container as well', ( function() {

	const model = builder._normalise( { blocks : [ { kind : 'section', settings : [], cols : [ { hidden : [], width : [], components : [ { name : 'card', attributes : [], components : [ { name : 'text', attributes : [] }, { name : 'button' } ] } ] } ] } ] } );
	const holder = model.blocks[0].cols[0].components[0];

	return Array.isArray( holder.attributes ) === false && holder.components.every( function( child ) { return Array.isArray( child.attributes ) === false && typeof child.attributes === 'object' } ) && JSON.stringify( holder.components[0].attributes ) === '{}';
} )() );

const reasonWords = function( code, language ) {
	const file = fs.readFileSync( path.join( __dirname, '../text/'+ language+ '.php' ), 'utf8' );
	const found = new RegExp( "^\\s*'\\[\\[/_admin/builder/reason/"+ code+ "\\]\\]'\\s*=>\\s*'(.*)',\\s*$", 'm' ).exec( file );
	return found === null ? null : found[1];
};
check( 'what the Reader does not take of a container is said in the words of the panel, in both languages: a loop inside it, another container inside it, and markup next to its component calls - with the name of the component and the markup, the two places of the sentence, which the Reader puts together with a line break', builder._reasonText( { line : 2, code : 'holder-stack', detail : '', text : 'x' } ) === 'Line 2: A loop inside a component that holds components'
	&& builder._reasonText( { line : 3, code : 'holder-in-holder', detail : '', text : 'x' } ) === 'Line 3: A component that holds components inside another'
	&& builder._reasonText( { line : 5, code : 'holder-content', detail : 'card\n<p>Hello there, a', text : 'x' } ) === 'Line 5: The component card holds markup next to its component calls: <p>Hello there, a'
	&& builder._reasonText( { line : 0, code : 'holder-content', detail : 'card\nx\ny', text : 'x' } ) === 'The component card holds markup next to its component calls: x\ny'
	&& [ 'holder-stack', 'holder-in-holder', 'holder-content' ].every( function( code ) {
		const places = code === 'holder-content' ? 2 : 0;
		return [ 'en_US', 'de_DE' ].every( function( language ) { const sentence = reasonWords( code, language ); return sentence !== null && sentence.split('%s').length === places + 1 } );
	} ) && reasonWords( 'holder-content', 'de_DE' ) === 'In der Komponente %s steht Markup neben den Komponentenaufrufen: %s' );

openCard();
builder._renderEditor();
builder._registry.slots.push( { uri : '/template/page-home-card/story/card-stored', label : 'Stored', width : 1600, height : 900, hasImage : true, url : '/uploads/s.jpg', alt : {} } );
cardIn( builder._doc.model ).attributes.background = '/template/page-home-card/story/card-picture';
cardIn( builder._doc.model ).slots = { background : { label : 'Card - Background picture', width : 800, height : 400 } };
builder._fresh.story = true;
builder._rename( [ 0 ], 'tale' );
check( 'a section made here is renamed by writing the sources of the children of its cards and the pictures the cards name new, since nothing of it was made', builder._doc.model.blocks[0].id === 'tale' && builder._doc.model.blocks[0].renamedFrom === undefined && builder._fresh.tale === true
	&& cardIn( builder._doc.model ).components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/tale/title,/template/page-home-card/tale/text,/template/page-home-card/tale/button'
	&& cardIn( builder._doc.model ).attributes.background === '/template/page-home-card/tale/card-picture' && cardIn( builder._doc.model ).slots.background.width === 800 && builder._doc.model.blocks[0].cols[0].components[0].source === '/template/page-home-card/tale/photo' );
cardIn( builder._doc.model ).attributes.background = '/template/page-home-card/tale/card-stored';
builder._registry.slots.push( { uri : '/template/page-home-card/tale/card-stored', label : 'Stored', width : 1600, height : 900, hasImage : true, url : '/uploads/s.jpg', alt : {} } );
builder._rename( [ 0 ], 'story' );
check( '...but for a slot that is there already: a picture was stored for it, and it stays what the attribute names, whatever the section is called', cardIn( builder._doc.model ).attributes.background === '/template/page-home-card/tale/card-stored' && cardIn( builder._doc.model ).components[0].source === '/template/page-home-card/story/title' );
openCard();
builder._rename( [ 0 ], 'tale' );
check( '...in a section that was saved nothing is moved here: the server moves what it has under the old name', builder._doc.model.blocks[0].id === 'tale' && builder._doc.model.blocks[0].renamedFrom === 'story' && cardIn( builder._doc.model ).components[0].source === '/template/page-home-card/story/title' );

check( 'a section read again by the server has its marks put back where they stood: the instruction to make the slot a card names, the key a child is made with and the texts typed for it - in the card as in the column', ( function() {

	const old = cardPage().blocks[0];
	const again = cardPage().blocks[0];
	const oldCard = old.cols[1].components[0];
	const newCard = again.cols[1].components[0];

	oldCard.attributes.background = '/template/page-home-card/story/card-picture';
	oldCard.slots = { background : { label : 'Card - Background picture', width : 800, height : 400, alt : { en_US : 'Waves' } } };
	oldCard.components[0].create = { value : 'Hello' };
	oldCard.components[1].values = { de_DE : 'Hallo' };
	newCard.attributes.background = '/template/page-home-card/story/card-picture';

	builder._carry( old, again );

	return JSON.stringify( newCard.slots ) === JSON.stringify( oldCard.slots ) && newCard.slots !== oldCard.slots && newCard.slots.background !== oldCard.slots.background && JSON.stringify( newCard.components[0].create ) === '{"value":"Hello"}'
		&& JSON.stringify( newCard.components[1].values ) === '{"de_DE":"Hallo"}' && newCard.components[2].create === undefined && again.cols[0].components[0].create === undefined;
} )() );
check( '...and not where the card was given another picture, or the mark has none to belong to', ( function() {

	const old = cardPage().blocks[0];
	const again = cardPage().blocks[0];

	old.cols[1].components[0].attributes.background = '/template/page-home-card/story/card-picture';
	old.cols[1].components[0].slots = { background : { label : 'Card - Background picture', width : 800, height : 400 } };
	again.cols[1].components[0].attributes.background = '/template/page-home-card/story/other-picture';

	builder._carry( old, again );

	return again.cols[1].components[0].slots === undefined;
} )() );

console.log( '\nWhat the other panels changed, and what this one tells them' );

const workbench = sandbox.Nino.admin;
const realCurrent = workbench.router.current;
// The counters of the workbench and what this panel remembers it read at, all at nothing; no request is out
const calm = function() {
	workbench.stamps = {};
	builder._seen = { templates : 0, routes : 0, texts : 0, elements : 0, slots : 0 };
	requests = [];
};
const asked = function() { return requests.map( function( request ) { return request.action } ).join() };

// The list is on screen where no document is open
doc = open();
builder._ready = true;
builder._doc = null;
calm();
builder.showCurrent();
check( 'the panel remembers the stamps of the data it shows when it asks for it, and compares them when it is shown: where nothing moved, it asks for nothing', asked() === '' );
workbench.touch('templates');
builder.showCurrent();
check( '...the list is asked for again when the templates moved - another panel made, changed or deleted a file -, and so when the routes moved, which the list names the pages of; the stamp is the one it was asked at, and shown again with nothing moved it asks nothing', asked() === 'builder/list' && ( requests[0].callback( 200, { templates : [] } ), builder.showCurrent(), asked() === 'builder/list' ) );
requests = [];
workbench.touch('routes');
builder.showCurrent();
check( '...a write that happens while the answer is on its way is after the stamp, and is found when the panel is shown again; a stamp of a kind the list does not show moves nothing', asked() === 'builder/list' && ( workbench.touch('templates'), requests[0].callback( 200, { templates : [] } ), requests = [], builder.showCurrent(), asked() === 'builder/list' )
	&& ( requests[0].callback( 200, { templates : [] } ), requests = [], workbench.touch('texts'), workbench.touch('elements'), workbench.touch('slots'), workbench.touch('navigations'), builder.showCurrent(), asked() === '' ) );

// The editor shows the texts, the element types, the slots and the frames
doc = open();
builder._ready = true;
builder._keysAsked = true;
workbench.router.current = function() { return { panel : 'builder', parts : [ 'page-home' ] } };
calm();
builder.showCurrent();
check( 'the editor asks for the keys of the texts when the texts moved, and for the registry - the types, the slots, the frames - when the templates, the elements or the slots moved, and for nothing where nothing did; one request for the registry, whichever of the three moved', asked() === ''
	&& ( workbench.touch('texts'), builder.showCurrent(), asked() === 'text/keys' ) && ( builder._keysAsked = true, calm(), workbench.touch('elements'), builder.showCurrent(), asked() === 'builder/registry' )
	&& ( calm(), workbench.touch('slots'), builder.showCurrent(), asked() === 'builder/registry' ) && ( calm(), workbench.touch('templates'), builder.showCurrent(), asked() === 'builder/registry' )
	&& ( calm(), workbench.touch('templates'), workbench.touch('elements'), workbench.touch('slots'), builder.showCurrent(), asked() === 'builder/registry' ) && ( calm(), workbench.touch('routes'), workbench.touch('navigations'), builder.showCurrent(), asked() === '' ) );
check( '...the answer is taken as it comes: the types, the slots, the frames are the new ones, the components and the stacks are the kernel\'s and stay - and the stamp is taken, so that the next time the panel is shown it asks nothing', ( function() {

	calm();
	workbench.touch('elements');
	builder.showCurrent();

	const components = builder._registry.components;

	requests[0].callback( 200, { types : [ { uri : '/news', title : 'News', fields : {} } ], slots : [], headers : [ 'html-header' ], footers : [], components : {}, stacks : {} } );

	const found = builder._registry.components === components && builder._registry.types.map( function( type ) { return type.uri } ).join() === '/news' && builder._registry.slots.length === 0 && builder._registry.footers.length === 0;

	requests = [];
	builder.showCurrent();

	return found && asked() === '';
} )() );
check( '...the texts are asked for when the texts moved and the answer is the keys of the project, the preview drawn with them; a person who may not read them gets none and types a key', ( function() {

	builder._keysAsked = true;
	calm();
	workbench.touch('texts');
	builder.showCurrent();
	requests[0].callback( 200, { keys : [ { key : '/template/page-home/hero/title', global : false, format : 'plain', maxlength : 160, values : { en_US : 'Changed elsewhere' } } ] } );

	const read = JSON.stringify( builder._valuesOf( '/template/page-home/hero/title' ) ) === '{"en_US":"Changed elsewhere"}' && builder._seen.texts === 1;

	requests = [];
	builder.showCurrent();

	return read && asked() === '';
} )() );

check( 'nothing is asked for while a dialog is open - what is on screen is not drawn from under a person who is typing - and what moved is asked for when the dialog is gone, whichever way it goes: the close of the form, or Escape', ( function() {

	builder._keysAsked = true;
	calm();
	builder._openSettings( [ 0, 0, 0 ] );
	workbench.touch('texts');
	workbench.touch('elements');
	builder.showCurrent();

	const held = asked() === '';

	builder._closeDialog();

	const closed = asked() === 'text/keys,builder/registry';

	builder._keysAsked = true;
	calm();
	builder._openSettings( [ 0, 0, 0 ] );
	workbench.touch('texts');
	dialogBox().close();

	const escaped = asked() === 'text/keys';

	builder._keysAsked = true;
	calm();
	builder._openSettings( [ 0, 0, 0 ] );
	workbench.touch('texts');
	builder._openSettings( [ 0, 0, 1 ] );

	// A form replaced by another is no form that is gone
	const replaced = asked() === '';

	builder._closeDialog();

	return held && closed && escaped && replaced && asked() === 'text/keys';
} )() );
workbench.router.current = realCurrent;

// What this panel writes, it tells the others: a template that is made, copied, saved or deleted, and the keys and the slots a save or an upload writes
const stampsNow = function() { return [ 'templates', 'texts', 'slots', 'elements', 'routes', 'navigations' ].map( function( topic ) { return topic+ ':'+ workbench.stamp( topic ) } ).join() };
check( 'a template that is made in the Builder raises the stamp of the templates, once it is there - and one that is refused raises nothing: the Routes panel, which lists them for a route to render, shows it without a reload', ( function() {

	doc = open();
	builder._doc = null;
	calm();
	builder._registry = registry();
	builder._newTemplate();

	const button = screen.getElementById('builder-dialog-actions').children[0];

	change( dialogContent(), 'Name', 'Fresh' );
	button.click();
	requests[0].callback( 409, { code : 'builder_exists' } );

	const refused = stampsNow() === 'templates:0,texts:0,slots:0,elements:0,routes:0,navigations:0' && asked() === 'builder/create';

	button.click();
	requests[1].callback( 200, { model : { file : 'page-fresh' } } );

	return refused && stampsNow() === 'templates:1,texts:0,slots:0,elements:0,routes:0,navigations:0' && dialogBox().open === false;
} )() );
check( '...a copy raises the stamps of the templates, of the texts and of the slots - the keys and the slots of the copy are new -, and a delete the stamp of the templates; a delete the server refused raises none', ( function() {

	doc = open();
	builder._files = [ { file : 'page-home', name : 'Home', editable : true } ];
	calm();
	builder._duplicateTemplate( 'page-home' );
	screen.getElementById('builder-dialog-actions').children[0].click();
	requests[0].callback( 200, { model : { file : 'page-home-copy' } } );

	const copied = stampsNow() === 'templates:1,texts:1,slots:1,elements:0,routes:0,navigations:0';

	calm();
	questions = [];
	builder._confirmDelete( 'page-home' );
	questions[0].onChoose( 'delete' );
	requests[0].callback( 409, { code : 'builder_in_use', params : [ 'GET://' ] } );

	const kept = stampsNow() === 'templates:0,texts:0,slots:0,elements:0,routes:0,navigations:0';

	requests[0].callback( 200, {} );

	return copied && kept && stampsNow() === 'templates:1,texts:0,slots:0,elements:0,routes:0,navigations:0' && asked().indexOf('builder/list') !== -1;
} )() );
check( '...a save raises the stamp of the templates; with keys for new texts it raises the texts, and with slots for new pictures the slots - what a save did not write is not touched; a save that failed raises none', ( function() {

	const saved = function( change ) {

		doc = open();
		builder._renderEditor();
		builder._doc.model.name = 'Start';
		change();
		calm();
		builder._save();

		const response = JSON.parse( JSON.stringify( builder._doc.model ) );
		const failed = stampsNow();

		requests[0].callback( 200, { model : response, hash : 'h2' } );

		return failed+ ' > '+ stampsNow();
	};
	const none = 'templates:0,texts:0,slots:0,elements:0,routes:0,navigations:0';

	return saved( function() {} ) === none+ ' > templates:1,texts:0,slots:0,elements:0,routes:0,navigations:0'
		&& saved( function() { builder._addComponent( [ 3, 0 ], 'title' ) } ) === none+ ' > templates:1,texts:1,slots:0,elements:0,routes:0,navigations:0'
		&& saved( function() { builder._doc.model.blocks[1].background = { slot : '/template/page-home/services/background', focus : null, create : { label : 'Background', width : 1600, height : 900 } } } ) === none+ ' > templates:1,texts:0,slots:1,elements:0,routes:0,navigations:0'
		&& saved( function() { builder._rename( [ 3 ], 'reach' ) } ) === none+ ' > templates:1,texts:1,slots:1,elements:0,routes:0,navigations:0' && ( function() {
			doc = open();
			builder._doc.model.name = 'Start';
			calm();
			builder._save();
			requests[0].callback( 400, { code : 'builder_invalid', error : 'x', params : [ 'the name is bad' ] } );
			return stampsNow() === none;
		} )();
} )() );
check( '...and what the panel wrote itself is no news to it: after its own save, shown again or with a dialog closed, it asks for nothing - the registry asked for again would draw the preview again, under the focus - '
	+ 'while a write of another panel before it, which is news, is still asked for', ( function() {

	const asksAfterSave = function( before ) {

		doc = open();
		builder._renderEditor();
		builder._ready = true;
		workbench.router.current = function() { return { panel : 'builder', parts : [ 'page-home' ] } };
		calm();
		builder._doc.model.name = 'Start';
		before();
		builder._save();
		requests[0].callback( 200, { model : JSON.parse( JSON.stringify( builder._doc.model ) ), hash : 'h2' } );

		// What a save asks for by itself, the keys of the texts and the slots of the registry, is answered
		requests.slice( 1 ).forEach( function( request ) { request.callback( 200, request.action === 'text/keys' ? { keys : [] } : { slots : [] } ) } );
		requests = [];
		builder.showCurrent();

		const shown = asked();

		requests = [];
		builder._pickComponent( [ 3, 0 ] );
		builder._closeDialog();

		return shown+ '|'+ asked();
	};

	return asksAfterSave( function() {} ) === '|' && asksAfterSave( function() { builder._addComponent( [ 3, 0 ], 'title' ) } ) === '|'
		&& asksAfterSave( function() { workbench.touch('templates') } ) === 'builder/registry|builder/registry' && asksAfterSave( function() { workbench.touch('elements') } ) === 'builder/registry|builder/registry';
} )() );
check( '...and the picture a person stores - the slot that is made for it, the picture, the alt text - raises the stamp of the slots at each step, so that the Images panel shows it', ( function() {

	doc = open();
	builder._renderEditor();
	builder._addComponent( [ 0, 0 ], 'image' );
	builder._openSettings( [ 0, 0, 3 ] );
	calm();
	change( contentPane(), altLabel, 'A house' );
	choose( contentPane(), house );
	requests[0].callback( 200, { ok : true, uri : '/template/page-home/hero/image' } );

	const made = stampsNow() === 'templates:0,texts:0,slots:1,elements:0,routes:0,navigations:0';

	requests[1].callback( 200, { filename : 'hero/image.1600x900.jpg', url : '/uploads/image.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );

	const stored = stampsNow() === 'templates:0,texts:0,slots:2,elements:0,routes:0,navigations:0';

	requests[2].callback( 200, { alt : { en_US : 'A house' } } );
	builder._closeDialog();

	return made && stored && stampsNow() === 'templates:0,texts:0,slots:3,elements:0,routes:0,navigations:0';
} )() );
builder._ready = false;

console.log( '\nThe toolbars are named by what they are of, and the buttons of a source by their field' );

/*	Every toolbar of the preview, by the frame it is in: a section by its id, a block of HTML+ so, a column by its place in its section and a component by the label the registry gives
	it - the words of the sentence are the panel's, the label is the kernel's, as the fixture the PHP suite wrote has it	*/
const toolbarsNamed = function() {

	const model = builder._doc.model;
	const sentence = function( name ) { return words['/_admin/builder/menu/tools-of'].replace( '%s', name ) };
	const named = function( path ) {

		if( path.length === 1 )
			return model.blocks[path[0]].kind === 'section' ? words['/_admin/builder/tree/section']+ ' '+ model.blocks[path[0]].id : words['/_admin/builder/tree/html-block'];

		if( path.length === 2 )
			return words['/_admin/builder/menu/tools-col'].replace( '%s', String( path[1] + 1 ) ).replace( '%s', model.blocks[path[0]].id );

		return registryFixture.components[ builder._get( model, path ).name ].label;
	};
	const bars = preview().querySelectorAll('.builder-tools');

	return bars.length > 0 && bars.every( function( bar ) {

		const frame = bar.closest('[data-path]');

		return frame !== null && bar.getAttribute('aria-label') === sentence( named( frame.dataset.path.split('.').map( Number ) ) );
	} ) && bars.every( function( bar ) { return bar.getAttribute('aria-label') !== words['/_admin/builder/menu/tools'] } );
};

doc = open();
builder._renderEditor();
check( 'every toolbar of the preview is named by what its tools are of - "Tools: " and then a section by its id, a block of HTML+ so, a column by its place in its section, a component by the label the registry gives it - and none is called "Tools" alone: eight Settings and eight Delete are not the same words eight times to a screen reader', toolbarsNamed() );
check( '...the columns of different sections do not share a name, and the sections do not', ( function() {
	const names = function( selector ) { return preview().querySelectorAll( selector ).map( function( bar ) { return bar.getAttribute('aria-label') } ) };
	const columns = names('.builder-pcol-head .builder-tools');
	const sections = names('.builder-frame-head .builder-tools');
	return columns.length === 4 && new Set( columns ).size === 4 && sections.length === 4 && new Set( sections ).size === 4;
} )() );
doc = openCard();
builder._renderEditor();
check( '...in a page with a card the card is named by its label and the components in it by theirs, one level deeper', toolbarsNamed() && preview().querySelector('[data-path="0.1.0"] .builder-tools') !== null
	&& preview().querySelector('[data-path="0.1.0.0"] .builder-tools') !== null );

check( 'the tools are a hit area of 24px at least each way and of 44px where the pointer is a finger, with the icon the size it was', ( function() {

	const pixels = function( rule, property ) {
		const found = new RegExp( '\\b'+ property+ ': (\\d+)px;' ).exec( rule );
		return found === null ? 0 : parseInt( found[1], 10 );
	};
	const coarse = /@media \(pointer: coarse\) \{\n\t\.builder-tools \.builder-icon-btn \{([^}]*)\}/.exec( stylesheet );

	return pixels( ruleOf('.builder-tools .builder-icon-btn'), 'min-width' ) >= 24 && pixels( ruleOf('.builder-tools .builder-icon-btn'), 'min-height' ) >= 24
		&& coarse !== null && pixels( coarse[1], 'min-width' ) >= 44 && pixels( coarse[1], 'min-height' ) >= 44
		&& ruleOf('.builder-tools .builder-icon').split('\n').indexOf( '\twidth: .9rem;' ) !== -1 && ruleOf('.builder-tools .builder-icon').split('\n').indexOf( '\theight: .9rem;' ) !== -1;
} )() );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0, 2 ] );
check( 'the button that opens the dialog of a source is named by its field where the field has a name over it - "Link, Source", for the dialog of a button has two of them, the source of its text and its link, and both would say "Source" - by the name and by itself, and the name is there with the id the button names; a row with no name over it has the word alone', ( function() {

	const rows = dialogContent().querySelectorAll('.builder-source');
	const named = rows.filter( function( row ) { return row.querySelector('.builder-source-name').hidden === false } );
	const plain = rows.filter( function( row ) { return row.querySelector('.builder-source-name').hidden === true } );
	const ids = rows.map( function( row ) { return row.querySelector('button').id } ).filter( function( id ) { return id !== '' } );

	return rows.length === 2 && named.length === 1 && plain.length === 1 && named.every( function( row ) {

		const button = row.querySelector('button');
		const name = row.querySelector('.builder-source-name');
		const joined = ( button.getAttribute('aria-labelledby') ?? '' ).split(' ');

		return name.id !== '' && button.id !== '' && joined.length === 2 && joined[0] === name.id && joined[1] === button.id && screen.getElementById( joined[0] ) === name && screen.getElementById( joined[1] ) === button
			&& name.textContent === registryFixture.components.button.attributes.href.label && button.getAttribute('aria-label') === words['/_admin/builder/source/title'] && button.title === words['/_admin/builder/source/title'];
	} ) && plain.every( function( row ) { return row.querySelector('button').getAttribute('aria-labelledby') === null && row.querySelector('button').getAttribute('aria-label') === words['/_admin/builder/source/title'] } )
		&& ids.length === 1 && new Set( ids ).size === ids.length;
} )() );
builder._closeDialog();
doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
builder._openSettings( [ 0, 0, 2 ] );
check( '...and the ids are not used twice where the dialog is opened again: each row of a source has numbers of its own', ( function() {
	const ids = dialogContent().querySelectorAll('.builder-source-name, .builder-source button').map( function( el ) { return el.id } ).filter( function( id ) { return id !== '' } );
	return ids.length === 2 && new Set( ids ).size === 2 && ids.every( function( id ) { return /^builder-source-\d+-(name|button)$/.test( id ) } );
} )() );
builder._closeDialog();

console.log( '\nA column that is added is a column of its own' );

const sharedBy = function( row, viewport ) { return row.cols.map( function( col ) { return col.width[viewport] } ).join() };
check( 'a column added to a row is the last of it and has the whole width in every viewport: the columns the row has keep the widths they have - what was set is the editor\'s, and a share made behind its back is not', ( function() {

	const first = builder._newCol();
	const second = builder._newCol();

	first.width = { s : 100, m : 50, l : 50 };
	second.width = { s : 100, m : 25, l : 25 };
	second.hidden = { l : true };

	const row = { cols : [ first, second ] };
	const added = builder._addedCol( row );

	return row.cols[2] === added && sharedBy( row, 'l' ) === '50,25,100' && sharedBy( row, 'm' ) === '50,25,100' && sharedBy( row, 's' ) === '100,100,100' && builder._share === undefined && builder.BESIDE === undefined;
} )() );
check( '...the new column is a column of its own in every way: it is not hidden anywhere, has no loop and no components, and none of the six sides of its spacing is set', ( function() {

	const row = { cols : [ builder._newCol() ] };
	const added = builder._addedCol( row );

	return JSON.stringify( added.hidden ) === '{}' && added.stack === null && added.components.length === 0 && builder.SIDES.every( function( side ) { return added[side] === '' } ) && added.p === undefined;
} )() );
doc = open();
builder._renderEditor();
builder._addCol( [ 0 ] );
check( 'the button at the end of a row adds a column: the hero has two, the first as wide as it was and the new one the whole width, the preview shows it - and the new column is the selection, in one change', ( function() {
	const hero = builder._doc.model.blocks[0];
	return changes === 1 && builder._same( builder._sel, [ 0, 1 ] ) && sharedBy( hero, 'l' ) === '66,100' && sharedBy( hero, 'm' ) === '100,100' && sharedBy( hero, 's' ) === '100,100'
		&& texts( frameOf('0').querySelectorAll('.builder-col-name') ) === '66%,100%' && frameOf('0.1').classList.contains('is-selected');
} )() );
builder._addCol( [ 1 ] );
check( '...in a row of two that stand side by side in the large screen and below each other in the medium one the same holds: the two keep what they have, the third has the whole width', ( function() {
	const services = builder._doc.model.blocks[1];
	return sharedBy( services, 'l' ) === '50,50,100' && sharedBy( services, 'm' ) === '100,100,100' && sharedBy( services, 's' ) === '100,100,100';
} )() );
check( 'what the editor makes new has the six sides of the spacing, none of them set: a section in its settings, a column, a component in its node - and the card, the one component that paints a ground, the two offsets of it as well; the column has no padding of its own as one setting', ( function() {
	const section = builder._newSection( page() );
	const col = builder._newCol();
	const text = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'text', [] );
	const card = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'card', [] );
	const none = function( node ) { return builder.SIDES.every( function( side ) { return node[side] === '' } ) };

	return none( section.settings ) && none( col ) && none( text ) && none( card ) && text.bgPl === undefined && card.bgPl === '' && card.bgPr === '' && card.radius === '' && col.p === undefined
		&& JSON.stringify( Object.keys( section.settings ).filter( function( key ) { return builder.SIDES.indexOf( key ) !== -1 } ) ) === JSON.stringify( builder.SIDES );
} )() );

console.log( '\nA new component is made with no text of its own' );

check( 'a text that is new is made with the text typed for it and with no other: the mark of its key says no more than that the key is made - no text of it is written into the model, not the label of the component, in no language', ( function() {
	const made = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'title', [] );
	return made.source.charAt(0) === '/' && JSON.stringify( made.create ) === '{}' && made.values === undefined;
} )() );
check( '...what the key holds is nothing in each language of the site, and a text typed in one language is that language\'s alone - typed back to nothing it is none', ( function() {

	const made = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'title', [] );
	const infoIn = function( locale ) { return builder._textOf( made, locale, [], registry() ) };
	const empty = registry().locales.every( function( entry ) { return infoIn( entry.locale ) !== null && infoIn( entry.locale ).value === '' && infoIn( entry.locale ).stored === '' && infoIn( entry.locale ).staged === false } );

	builder._stageValue( made, infoIn( 'de_DE' ), 'de_DE', 'Willkommen' );

	const typed = JSON.stringify( made.values ) === '{"de_DE":"Willkommen"}' && infoIn( 'de_DE' ).value === 'Willkommen' && infoIn( 'en_US' ).value === '';

	builder._stageValue( made, infoIn( 'de_DE' ), 'de_DE', '' );

	return empty && typed && made.values === undefined;
} )() );
check( '...what an empty field of a component says is its label in the language it is typed in: the label the registry gives it there, and where it has none for a language the one of the workbench', registry().locales.every( function( entry ) {
	return Object.keys( registry().components ).every( function( name ) {
		return registryFixture.components[name].labels === undefined || builder._labelIn( registry(), name, entry.locale ) === labelIn( name, entry.locale );
	} );
} ) && builder._labelIn( registry(), 'title', 'fr_FR' ) === registryFixture.components.title.label && builder._labelIn( registry(), 'nothing-of-the-kind', 'en_US' ) === 'nothing-of-the-kind' );
check( '...and the slot of a picture that is new is made with the alt text of each language, which is the label of its component in it: a placeholder alt text is better than none, and an alt text has no fallback to stand in for one', ( function() {
	const made = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'image', [] );
	return JSON.stringify( made.create.alt ) === JSON.stringify( { en_US : labelIn( 'image', 'en_US' ), de_DE : labelIn( 'image', 'de_DE' ) } ) && made.values === undefined && made.create.label === registryFixture.components.image.label;
} )() );
check( '...for every component of the registry that has a text of its own or a picture: a text makes its key with nothing in it, a picture its slot with the label of the component in each language of the site as its alt text', ( function() {

	const reg = registry();
	const section = page();
	const results = [];
	let texts = 0;
	let pictures = 0;

	Object.keys( reg.components ).forEach( function( name ) {

		const kind = builder._sourceKind( reg, name );

		if( kind !== 'text' && kind !== 'image' )
			return;

		const made = builder._newComponent( section, reg, 'page-home', [ 3, 0 ], name, [] );

		if( kind === 'text' ) {
			texts++;
			results.push( made.create !== undefined && JSON.stringify( made.create ) === '{}' && made.values === undefined );
		} else {
			pictures++;
			results.push( reg.locales.every( function( entry ) { return made.create.alt[entry.locale] === labelIn( name, entry.locale ) } ) );
		}
	} );

	return texts > 0 && pictures > 0 && results.every( function( ok ) { return ok === true } );
} )() );
check( '...a site with one language has nothing typed for a second, and one with three has nothing for either: a text is made with nothing in each, and the slot with its alt text in the languages the site has - and a language the registry has no label for is shown the one of the workbench', ( function() {

	const alone = registry();
	const three = registry();

	alone.locales = [ alone.locales[0] ];
	three.locales.push( { locale : 'fr_FR', name : 'Français' } );

	const text = builder._newComponent( page(), alone, 'page-home', [ 3, 0 ], 'title', [] );
	const picture = builder._newComponent( page(), alone, 'page-home', [ 3, 0 ], 'image', [] );
	const more = builder._newComponent( page(), three, 'page-home', [ 3, 0 ], 'title', [] );
	const moreImage = builder._newComponent( page(), three, 'page-home', [ 3, 0 ], 'image', [] );

	return text.values === undefined && JSON.stringify( text.create ) === '{}' && JSON.stringify( picture.create.alt ) === JSON.stringify( { en_US : labelIn( 'image', 'en_US' ) } )
		&& more.values === undefined && JSON.stringify( more.create ) === '{}' && moreImage.create.alt.fr_FR === registryFixture.components.image.label && Object.keys( moreImage.create.alt ).length === 3;
} )() );
check( '...a component that stands where no key is made - the cell of a loop - has no placeholder to write', ( function() {
	const made = builder._newComponent( page(), registry(), 'page-home', [ 1, 1 ], 'title', [] );
	return made.create === undefined && made.values === undefined;
} )() );
check( '...the placeholders are written for a picture alone: a text that has nothing typed gets none, whatever the languages of the site', ( function() {

	const made = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'title', [] );
	const picture = builder._newComponent( page(), registry(), 'page-home', [ 3, 0 ], 'image', [] );

	builder._placeholders( registry(), made );
	delete picture.create.alt;
	builder._placeholders( registry(), picture );

	return made.values === undefined && JSON.stringify( made.create ) === '{}' && Object.keys( picture.create.alt ).length === 2;
} )() );

console.log( '\nA copy takes the picture and the texts of each language of the original' );

check( 'the slot of a picture that is copied with its card says which slot it is a copy of, and has its size: the save hands the picture and the alt texts on - the same for the picture of a column and the one behind a section', ( function() {

	const reg = registry();
	const model = cardPage();
	const holder = cardIn( model );
	const label = registryFixture.components.card.label+ ' - '+ registryFixture.components.card.attributes.background.label;

	holder.attributes.background = '/template/page-home-card/story/card-picture';
	reg.slots.push( { uri : '/template/page-home-card/story/card-picture', label : 'Card picture', width : 800, height : 400, hasImage : true, url : '/uploads/card.jpg', alt : { en_US : 'A card', de_DE : 'Eine Karte' } } );

	const card = builder._get( model, builder._duplicate( model, reg, 'page-home-card', cardAt ) );
	const cardMark = JSON.stringify( card.slots.background ) === JSON.stringify( { label : label, width : 800, height : 400, from : '/template/page-home-card/story/card-picture' } ) && card.attributes.background === '/template/page-home-card/story/card-picture-2';

	reg.slots.push( { uri : '/template/page-home-card/story/photo', label : 'Photo', width : 1200, height : 600, hasImage : true, url : '/uploads/photo.jpg', alt : { en_US : 'A photo' } } );

	const column = builder._get( model, builder._duplicate( model, reg, 'page-home-card', [ 0, 0 ] ) );
	const columnMark = column.components[0].source === '/template/page-home-card/story/photo-2' && JSON.stringify( column.components[0].create ) === JSON.stringify( { label : registryFixture.components.image.label, width : 1200, height : 600, from : '/template/page-home-card/story/photo' } );

	const home = page();
	const section = builder._duplicate( home, registry(), 'page-home', [ 0 ] );
	const sectionMark = home.blocks[section[0]].background.slot === '/template/page-home/hero-2/background'
		&& JSON.stringify( home.blocks[section[0]].background.create ) === JSON.stringify( { label : words['/_admin/builder/source/background'], width : 1600, height : 900, from : '/template/page-home/hero/background' } );

	return cardMark && columnMark && sectionMark;
} )() );
check( '...a picture whose slot the panel does not know has the size a new slot starts with - and still says what it is a copy of, which the save looks into', ( function() {
	const model = cardPage();
	const copy = builder._get( model, builder._duplicate( model, registry(), 'page-home-card', [ 0, 0 ] ) );
	return copy.components[0].create.width === builder.SLOT_SIZE.width && copy.components[0].create.height === builder.SLOT_SIZE.height && copy.components[0].create.from === '/template/page-home-card/story/photo';
} )() );
check( 'a copy has the text of the original in each language it has one in: the key of the copy is made with them, typed for the copy - and a key every language shares has one text, which the copy has in each language of the site', ( function() {

	const model = page();

	builder._keys = known();

	const title = builder._get( model, builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], builder._valuesOf ) );
	const subtitle = builder._get( model, builder._duplicate( model, registry(), 'page-home', [ 0, 0, 2 ], builder._valuesOf ) );

	return JSON.stringify( title.create ) === '{}' && JSON.stringify( title.values ) === '{"en_US":"Welcome","de_DE":"Willkommen"}' && subtitle.name === 'subtitle' && JSON.stringify( subtitle.create ) === '{}'
		&& JSON.stringify( subtitle.values ) === '{"en_US":"We build","de_DE":"We build"}';
} )() );
check( '...a language in which the original has no text - or an empty one - is no language of the copy: nothing is written for it', ( function() {

	builder._keys = [
		{ key : '/template/page-home/hero/title', global : false, format : 'plain', maxlength : 160, values : { en_US : 'Only English', de_DE : '' } },
		{ key : '/template/page-home/hero/subtitle', global : false, format : 'plain', maxlength : 160, values : { en_US : null, de_DE : '' } },
		{ key : '/template/page-home/hero/empty', global : true, format : 'plain', maxlength : 160, values : { '*' : '' } },
	];

	return JSON.stringify( builder._valuesOf( '/template/page-home/hero/title' ) ) === '{"en_US":"Only English"}' && JSON.stringify( builder._valuesOf( '/template/page-home/hero/subtitle' ) ) === '{}'
		&& JSON.stringify( builder._valuesOf( '/template/page-home/hero/empty' ) ) === '{}' && JSON.stringify( builder._valuesOf( '/template/page-home/hero/unknown' ) ) === '{}';
} )() );
check( '...what was typed for the key of the original and is not saved yet is the copy\'s too, over the text the key has in that language - and the original keeps its own', ( function() {

	const model = page();

	builder._keys = known();
	builder._get( model, [ 0, 0, 0 ] ).values = { de_DE : 'Hallo' };

	const copy = builder._get( model, builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], builder._valuesOf ) );

	return JSON.stringify( copy.values ) === '{"en_US":"Welcome","de_DE":"Hallo"}' && copy.values !== builder._get( model, [ 0, 0, 0 ] ).values && JSON.stringify( builder._get( model, [ 0, 0, 0 ] ).values ) === '{"de_DE":"Hallo"}';
} )() );
check( '...a copy made without the texts of the languages - where the keys of the project are not known - has none: its key is made empty, and the label of the component is no text of it', ( function() {
	const model = page();
	const copy = builder._get( model, builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ] ) );
	return JSON.stringify( copy.create ) === '{}' && copy.values === undefined;
} )() );
check( 'the copy that the tool makes is made with them: the text of every language the key has - read from the keys of the project, which a duplicate asks for', ( function() {
	doc = open();
	builder._renderEditor();
	builder._duplicateAt( [ 0, 0, 0 ] );
	const copy = builder._get( builder._doc.model, [ 0, 0, 1 ] );
	return copy.source === '/template/page-home/hero/title-2' && JSON.stringify( copy.create ) === '{}' && JSON.stringify( copy.values ) === '{"en_US":"Welcome","de_DE":"Willkommen"}';
} )() );

console.log( '\nText on a picture reads better with the picture dimmed' );

const dimLabel = words['/_admin/builder/section/dim'];
// The question mark of the switch for the dimming: the advice is behind it, and it is there for as long as the advice applies
const dimHelp = function() { return controlOf( blockOf('surface'), dimLabel ).el.closest('.builder-row').querySelector('.builder-help') };

doc = open();
builder._renderEditor();
builder._doc.model.blocks[0].settings.dim = false;
builder._openSettings( [ 0 ] );
check( 'a section that has a picture, text on it and the dimming off has the advice that the text reads better with the picture dimmed behind the question mark of the switch - which blocks nothing: it is a hint, and none of it stands under the switch', dimHelp() !== null && dimHelp().hidden === false
	&& tipOf( controlOf( blockOf('surface'), dimLabel ) ) === words['/_admin/builder/hint/dim-text'] && blockOf('surface').querySelector('.builder-dim-hint') === null );
check( '...the mark is gone with the dimming on, and there again with it off', ( change( dialogContent(), dimLabel, true ), dimHelp().hidden === true ) && ( change( dialogContent(), dimLabel, false ), dimHelp().hidden === false ) );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._doc.model.blocks[1].settings.dim = false;
builder._openSettings( [ 1 ] );
check( '...a section that has no picture has no mark, however much text it has', dimHelp() !== null && dimHelp().hidden === true );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._doc.model.blocks[0].settings.dim = false;
builder._doc.model.blocks[0].cols[0].components = [ { name : 'image', source : '', text : null, attributes : {} }, { name : 'spacer', source : '', text : null, attributes : {} } ];
builder._openSettings( [ 0 ] );
const quiet = dimHelp().hidden === true;
builder._closeDialog();
builder._doc.model.blocks[0].cols[0].components.push( { name : 'card', source : '', text : null, attributes : {}, components : [ { name : 'card-title', source : '', text : null, attributes : {} } ] } );
builder._openSettings( [ 0 ] );
const spoken = dimHelp().hidden === false;
builder._closeDialog();
check( '...nor does one with nothing on it but pictures and space - and one with a title in a card in its column has text on it', quiet && spoken );
builder._doc.model.blocks[0].cols[0].components = [ { name : 'html', source : '', text : null, attributes : {}, content : '   ' } ];
builder._openSettings( [ 0 ] );
const emptyHtml = dimHelp().hidden === true;
builder._closeDialog();
builder._doc.model.blocks[0].cols[0].components[0].content = '<p>Hello</p>';
builder._openSettings( [ 0 ] );
check( '...a block of HTML has text on it where it is not empty', emptyHtml && dimHelp().hidden === false );
builder._closeDialog();

console.log( '\nThe list, the refusal for the id of a section, and the focus where the preview is drawn again' );

check( 'the list of templates has no sort button for its column of buttons: that is the only column with no label, and it is passed as one that is not sorted - the others are as they were', ( function() {

	tables = [];
	builder._files = [ { file : 'page-home', name : 'Home', header : 'html-header', footer : '', sections : 3, foreign : 1, readable : true, reason : null, editable : true, usedBy : [] } ];
	builder._renderList();

	const columns = tables[0].columns;

	builder._files = [];
	builder._renderList();

	return columns.length === 7 && columns.filter( function( column ) { return column.label === '' } ).length === 1 && columns.filter( function( column ) { return column.label === '' } ).every( function( column ) { return column.sortable === false } )
		&& columns.filter( function( column ) { return column.label !== '' } ).every( function( column ) { return column.sortable === undefined } );
} )() );
check( 'a refusal that names ids - the id of a section that a loop of sections makes for its own - is said in the sentence of the panel with the ids in it, where its words have a place for them, and in no other', ( function() {
	const said = builder._errorText( 409, { code : 'builder_section_id_taken', params : [ 'news-2', 'news-3' ] }, '/x' );
	return said === words['/_admin/builder/error/section-id-taken'].replace( '%s', 'news-2, news-3' ) && said.indexOf('%s') === -1 && builder._errorText( 409, { code : 'builder_conflict', params : [ 'x' ] }, '/x' ) === words['/_admin/builder/error/conflict'];
} )() );
doc = open();
builder._renderEditor();
builder._save();
requests[0].callback( 409, { error : 'a section has an id that a loop of sections makes', code : 'builder_section_id_taken', params : [ 'services-2' ] } );
check( '...a save that is refused for it says so in the bar, with the id: nothing was written, so the document stays as it is - and Save is on again', builder._status.state === 'error' && builder._status.text === words['/_admin/builder/error/section-id-taken'].replace( '%s', 'services-2' )
	&& builder._saving === false && screen.getElementById('builder-save').disabled === false && builder._outcome( 409, { code : 'builder_section_id_taken', params : [ 'services-2' ] } ).kind === 'error' && builder._doc.hash === 'h1' );

// The focus is on a tool of the preview, another panel's change is asked for and answered: the preview is drawn again, and the tool the focus was on is gone with the old one
const focused = function( ask, answer ) {

	doc = open();
	builder._renderEditor();

	const tool = toolOf( '3.0.0', 'duplicate' );

	tool.focus();
	ask();
	answer( requests[requests.length - 1] );

	return tool;
};
const kept = function( tool ) { return tool.isConnected === false && screen.activeElement !== null && screen.activeElement.isConnected === true && screen.activeElement === toolOf( '3.0.0', 'duplicate' ) };

check( 'the preview that is drawn again for what another panel changed gives the focus back to the tool it was on - the keys of the project, the slots and the registry each: the tool that stands in its place, found by its frame and its name', kept( focused( function() { builder._keysAsked = false; builder._loadKeys() }, function( request ) { request.callback( 200, { keys : known() } ) } ) )
	&& kept( focused( function() { builder._refreshSlots() }, function( request ) { request.callback( 200, { slots : registry().slots } ) } ) )
	&& kept( focused( function() { builder._refreshRegistry() }, function( request ) { request.callback( 200, { types : registry().types, slots : registry().slots, headers : [ 'html-header' ], footers : [] } ) } ) ) );
check( '...a focus that is not on a tool of the preview is not the preview\'s to move: one in the dialog stays where it is, and none stays none', ( function() {

	doc = open();
	builder._renderEditor();
	builder._openSettings( [ 3, 0, 0 ] );

	const field = dialogContent().querySelector('input, select, textarea, button');

	field.focus();
	builder._refreshSlots();
	requests[requests.length - 1].callback( 200, { slots : registry().slots } );

	const inDialog = screen.activeElement === field;

	builder._closeDialog();
	screen.focused = null;
	builder._refreshSlots();
	requests[requests.length - 1].callback( 200, { slots : registry().slots } );

	return inDialog && screen.activeElement === null;
} )() );

check( 'a tool that cannot be used - one that is off, as the tool to move the first section up, or one the frame has not, as the tool for HTML+ in a component - is no place for the focus: it is found for none, and the others are found where they are', ( function() {

	doc = open();
	builder._renderEditor();

	return toolOf( '0', 'up' ).disabled === true && builder._toolAt( [ 0 ], 'up' ) === null && builder._toolAt( [ 1 ], 'up' ) === toolOf( '1', 'up' ) && builder._toolAt( [ 1 ], 'up' ) !== null
		&& toolOf( '3.0.0', 'html' ).hidden === true && builder._toolAt( [ 3, 0, 0 ], 'html' ) === null && builder._toolAt( [ 0 ], 'settings' ) === toolOf( '0', 'settings' ) && builder._toolAt( [ 0 ], 'settings' ) !== null;
} )() );

doc = open();
builder._renderEditor();

const htmlTool = toolOf( '0', 'html' );

htmlTool.focus();
htmlTool.click();
requests[0].callback( 200, { parts : [ { kind : 'section', block : 0, source : '<section id="hero"></section>' } ] } );
dialogBox().close();
check( 'the editor of HTML+ that is opened from the tool for it and cancelled leaves the focus on that tool - and not on the settings tool of the block, where a form that drew the preview again sends it: the tool is still there, so the browser has somewhere to put the focus back', dialogBox().open === false
	&& htmlTool.isConnected === true && screen.activeElement === htmlTool && screen.activeElement !== toolOf( '0', 'settings' ) );

console.log( '\nThe screens' );

check( 'the list and the editor are drawn without a page to complain', ( function() {
	builder._files = [ { file : 'page-home', name : 'Home', header : 'html-header', footer : '', sections : 3, foreign : 1, readable : true, reason : null, editable : true, usedBy : [ { route : 'GET://', uri : '/home' } ] } ];
	builder._renderList();
	builder._files = [];
	builder._renderList();
	return true;
} )() );
check( 'the address of a template is checked before a request is made of it', builder.FILE.test( 'page-home' ) === true && builder.FILE.test( 'page-' ) === false && builder.FILE.test( '../x' ) === false && builder.FILE.test( 'page-Home' ) === false );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
