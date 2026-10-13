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
 *												builder-smoke.php holds to what the Reader makes of
 *												page-home.tpl) and the registry of the kernel's own
 *												components (registry.json).
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
 *												measured, and so are the heads of the frames, the tabs, the
 *												groups, the lines of two fields, the radio groups, the icons,
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
 *												The grounds and the places: the Background tab of a column (its
 *												colour with counter, which the patch resolves by the pairs of
 *												Nino.css, the picture behind it and the slot an upload makes for
 *												it, the dimming that goes with the picture), the border of a
 *												section as a strength and a switch that waits for one, the
 *												cover of a section - a switch, its height and the place of its
 *												content - and the parallax and the dimming of its picture, which
 *												wait for the picture and for a cover or a parallax, the
 *												frame of a column in the ground it paints - dark where its
 *												picture is dimmed -, the alignment of a column in one line and
 *												as an icon in its head, the picker that
 *												offers the components of its place, and the second line of a
 *												placeholder that says what a component draws.
 *
 *												The card, a component that holds components, over the page of
 *												the fixtures that has two (fixtures/page-home-card.json): a path
 *												one level deeper and the moving, copying and deleting that work
 *												at it, the keys and slots of a copy, the sources that are red in
 *												it, the picker by place - the card in a column and in the bare
 *												cell of a loop, its children inside it, nothing a loop does not
 *												take -, its frame in the preview with the tint it has by the
 *												pairs and the mark of its picture, its tools at depth four, its
 *												form - the ground as the colour select, the words of the Builder
 *												for the values, the focus and the dimming that wait for the
 *												picture, the picture from a slot, from a field or from an upload
 *												- and the spacing of a column, which is a line of its Layout tab.
 *
 *												The icon of a component in the picker: each item holds the svg
 *												the parts of the registry's entry are built into, tag by tag
 *												and attribute by attribute - the twelve of the kernel, and
 *												the two of a feature that has its own - or, for a component
 *												that gives none, the puzzle of the sprite; the parts the
 *												script does not know are left out, nothing is written as
 *												markup, and the stylesheet lays an item out as a row. The
 *												stand-in remembers the namespace an element is made in, to
 *												tell an svg element from an html one.
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
		const was = this.document === undefined ? null : this.document.activeElement;
		if( this.document !== undefined ) this.document.activeElement = this;
		if( was !== null && was !== this ) was.dispatch('blur');
	}
	select() {}
	scrollIntoView() {}
	getBoundingClientRect() { return { top : 0, left : 0, bottom : 0, right : 0, width : 0, height : 0 } }
	showModal() { this.open = true }
	close() { this.open = false; this.dispatch('close') }

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
		table : function() { return { setRows : function() {} } },
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

const pressKey = function( el, key ) { return el.dispatch( 'keydown', { key : key } ) };

// ------------------------------------------------------------ What stands on the page

const dialogBox = function() { return screen.getElementById('builder-dialog') };
const pickerBox = function() { return screen.getElementById('builder-picker') };
const dialogText = function( id ) { return screen.getElementById( id ).textContent };
const paneOf = function( id ) { return screen.getElementById( 'builder-pane-'+ id ) };
const pickerPane = function( id ) { return screen.getElementById( 'builder-picker-pane-'+ id ) };
const dialogContent = function() { return screen.getElementById('builder-dialog-content') };
const preview = function() { return screen.getElementById('builder-preview') };

// The tabs of a strip: [ id, word ] each, in the order they are drawn
const tabsOf = function( strip, prefix ) {
	return strip.querySelectorAll('[role="tab"]').map( function( tab ) { return [ tab.id.replace( prefix+ '-tab-', '' ), tab.textContent ] } );
};
const dialogTabs = function() { return tabsOf( screen.getElementById('builder-dialog-tabs'), 'builder' ) };

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

check( 'a copy of a section takes an id of its own, and keys and slots of its own where the original has them: new ones, with the text the key has now',
	( function() {
		const at = builder._duplicate( model, registry(), 'page-home', [ 0 ], function( key ) { return key === '/template/page-home/hero/title' ? 'Welcome' : '' } );
		const copy = model.blocks[1];
		return builder._same( at, [ 1 ] ) && copy.id === 'hero-2' && model.blocks[0].id === 'hero' && model.blocks.length === 5
			&& copy.cols[0].components[0].source === '/template/page-home/hero-2/title' && copy.cols[0].components[0].create.value === 'Welcome'
			&& copy.cols[0].components[1].source === '/template/page-home/hero-2/subtitle' && copy.cols[0].components[1].create.value === 'Subtitle (Section)'
			&& copy.background.slot === '/template/page-home/hero-2/background' && copy.background.create.label === 'Background' && copy.background.focus === 5
			&& copy.cols[0].components[2].source === '/_nino/webpage/contact/name' && copy.cols[0].components[2].create === undefined
			&& model.blocks[0].cols[0].components[0].source === '/template/page-home/hero/title' && model.blocks[0].cols[0].components[0].create === undefined && model.blocks[0].background.create === undefined;
	} )() );
check( '...a second copy takes the next id', builder._same( builder._duplicate( model, registry(), 'page-home', [ 0 ], function() { return '' } ), [ 1 ] ) && model.blocks[1].id === 'hero-3' );
model = page();
check( 'a copy of a component in its section has a name beside the original (title-2), and one that is not the section\'s own keeps its source', ( function() {
	const first = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return 'Welcome' } );
	const second = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return 'Welcome' } );
	const third = builder._duplicate( model, registry(), 'page-home', [ 0, 0, 4 ], function() { return '' } );
	const components = model.blocks[0].cols[0].components;
	return builder._same( first, [ 0, 0, 1 ] ) && components[1].source === '/template/page-home/hero/title-3' && components[1].create.value === 'Welcome' && builder._same( second, [ 0, 0, 1 ] )
		&& components[2].source === '/template/page-home/hero/title-2' && components[0].source === '/template/page-home/hero/title' && components[0].create === undefined && builder._same( third, [ 0, 0, 5 ] ) && components[5].source === '/_nino/webpage/contact/name' && components[5].create === undefined;
} )() );
model = page();
check( 'a copy of a column takes new keys beside the original for every component, and the copy shares nothing with it', ( function() {
	builder._duplicate( model, registry(), 'page-home', [ 0, 0 ], function() { return '' } );
	const copy = model.blocks[0].cols[1];
	copy.components[0].attributes.tag = 'h4';
	return copy.components[0].source === '/template/page-home/hero/title-2' && copy.components[1].source === '/template/page-home/hero/subtitle-2' && model.blocks[0].cols[0].components[0].attributes.tag === 'h3' && model.blocks[0].cols.length === 2;
} )() );
model = page();
model.blocks[1].cols[0].background = { slot : '/template/page-home/services/column-background', focus : 3 };
check( 'a copy of a section takes a slot of its own for the picture behind each of its columns, and a copy of a column one beside the original: new, labelled as a background, with the focus - and nothing is shared with the original', ( function() {

	builder._duplicate( model, registry(), 'page-home', [ 1 ], function() { return '' } );

	const copy = model.blocks[2].cols[0].background;
	const whole = copy.slot === '/template/page-home/services-2/column-background' && copy.create.label === 'Background' && copy.focus === 3 && model.blocks[1].cols[0].background.create === undefined && copy !== model.blocks[1].cols[0].background;

	builder._duplicate( model, registry(), 'page-home', [ 1, 0 ], function() { return '' } );

	const beside = model.blocks[1].cols[1].background;

	return whole && beside.slot === '/template/page-home/services/column-background-2' && beside.create.label === 'Background' && beside.focus === 3 && beside !== model.blocks[1].cols[0].background
		&& model.blocks[1].cols[0].background.slot === '/template/page-home/services/column-background' && model.blocks[1].cols[0].background.create === undefined;
} )() );
model = page();
check( 'a column that has no picture is copied without one', ( function() {
	builder._duplicate( model, registry(), 'page-home', [ 1, 0 ], function() { return '' } );
	return model.blocks[1].cols[1].background === null && model.blocks[1].cols[1].color === '' && model.blocks[1].cols[1].dim === false;
} )() );
model = page();
check( 'a block of html is copied as it is, a stack cannot be copied', ( function() {
	const html = builder._duplicate( model, registry(), 'page-home', [ 2 ], function() { return '' } );
	return builder._same( html, [ 3 ] ) && model.blocks[3].kind === 'html' && model.blocks[3].source === model.blocks[2].source && model.blocks[3] !== model.blocks[2] && builder._duplicate( model, registry(), 'page-home', [ 1, 1, 'x' ], function() { return '' } ) === null;
} )() );

model = page();
check( 'delete takes a section, a column or a component out', builder._remove( model, [ 1, 1, 0 ] ) === true && model.blocks[1].cols[1].components.length === 3
	&& builder._remove( model, [ 2 ] ) === true && model.blocks.length === 3 && builder._remove( model, [ 0, 0 ] ) === true && model.blocks[0].cols.length === 0 && builder._remove( model, [ 9 ] ) === false && builder._remove( model, [] ) === false );
model = page();
check( '...a loop is not taken out like that: it is set to Static in the Loop tab of its column, and the components stay', builder._remove( model, [ 1, 1, 'x' ] ) === false && model.blocks[1].cols[1].stack !== null && model.blocks[1].cols[1].components.length === 4
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
check( '...a loop is no frame and has no tools: it is set in the Loop tab of its column', builder._toolbar( model, [ 1, 1, 'x' ] ) === null );
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

check( 'a new section is made with the animation of the template: the bare class where it animates its sections, none where it does not - and it is like the template either way, two switches off, the settings in the order the Reader reads them', ( function() {
	const off = page();
	const on = page();
	on.animate = true;
	const first = builder._newSection( on );
	const second = builder._newSection( off );
	return first.settings.vpa === '' && second.settings.vpa === null && builder._animationMode( true, first.settings ) === 'like' && builder._animationMode( false, second.settings ) === 'like'
		&& first.settings.fullwidth === false && first.settings.fullheight === false && first.settings.vpaSpeed === '' && first.settings.vpaDelay === ''
		&& Object.keys( first.settings ).join() === Object.keys( fixture('page-home.json').blocks[0].settings ).join();
} )() );

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
	return title.source === '/template/page-home/hero/title-2' && title.create.value === 'Title (Section)' && title.attributes.tag === 'h3' && title.text === null
		&& image.source === '/template/page-home/hero/image' && image.create.label === 'Image' && image.create.width === 1600 && html.content === '' && html.source === '' && html.create === undefined && spacer.source === '' && spacer.create === undefined;
} )() );
check( '...and in a stack none: its source is a field, chosen in the form', builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).source === '' && builder._newComponent( model, reg, 'page-home', [ 1, 1 ], 'title', [] ).create === undefined );

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
	builder._closing = 0;
	builder._viewport = 'l';
	builder._selectView('l');
	dialogBox().open = false;
	pickerBox().open = false;
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
check( '...and the loop is set in the Loop tab of the column: the status opens the column dialog on it', ( function() {
	loopItem().click();
	const opened = dialogBox().open === true && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'loop' && dialogText('builder-dialog-kind') === 'Column of "services"' && builder._same( builder._sel, [ 1, 1, 'x' ] );
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
check( '...and a component is chosen from the registry\'s, those that belong where the column is: the first section is the hero, anywhere else is a section - the cell of a loop among it, which holds no place of its own - and what names no place is offered in all of them; the card is for the hero and for a section, and a loop takes it as it takes the rest', ( function() {
	const menu = screen.getElementById('builder-menu');
	const offered = function( path ) { builder._pickComponent( path, made('button') ); return texts( menu.children ) };
	return offered( [ 0, 0 ] ) === 'Title (Hero),Subtitle (Hero),Text,Card,Button,Image,HTML,Spacer' && offered( [ 1, 0 ] ) === 'Title (Section),Subtitle (Section),Text,Card,Button,Image,HTML,Spacer'
		&& offered( [ 1, 1 ] ) === offered( [ 1, 0 ] ) && offered( [ 3, 0 ] ) === offered( [ 1, 0 ] );
} )() );
check( '...they stand in the order of the registry and in none of the script\'s own: the components as the kernel registers them, then those of a feature in the order it registered them - and a registry in another order is offered in that order, not in the alphabet\'s', ( function() {

	const kept = builder._registry;
	const offered = function( path ) { builder._pickComponent( path, made('button') ); return texts( screen.getElementById('builder-menu').children ) };
	const kernel = offered( [ 1, 0 ] );
	const flipped = {};

	builder._registry = Object.assign( {}, kept, { components : Object.assign( {}, kept.components, { zulu : { label : 'Zulu', preview : 'block' }, alpha : { label : 'Alpha', preview : 'block' } } ) } );

	const featured = offered( [ 1, 0 ] );

	Object.keys( kept.components ).reverse().forEach( function( name ) { flipped[name] = kept.components[name] } );
	builder._registry = Object.assign( {}, kept, { components : flipped } );

	const other = offered( [ 1, 0 ] );

	builder._registry = kept;

	return kernel === 'Title (Section),Subtitle (Section),Text,Card,Button,Image,HTML,Spacer' && featured === kernel+ ',Zulu,Alpha' && other === 'Spacer,HTML,Image,Button,Card,Text,Subtitle (Section),Title (Section)';
} )() );
check( '...a component that says where it belongs (its "in") is offered there and nowhere else - the hero is the first section, whether or not it is a cover, a section is any other column, the cell of a loop included -, one that says nothing is offered everywhere, and one a loop does not take is offered in no column that runs one: the script names no component', ( function() {

	const menu = screen.getElementById('builder-menu');
	const components = builder._registry.components;
	const blocks = builder._doc.model.blocks;
	const offered = function( path ) {
		builder._pickComponent( path, made('button') );
		return menu.children.filter( function( item ) { return /^Stub/.test( item.textContent ) } ).map( function( item ) { return item.textContent } ).join();
	};

	components['stub-card'] = { label : 'Stub card', preview : 'block', in : [ 'card' ] };
	components['stub-hero'] = { label : 'Stub hero', preview : 'block', in : [ 'hero' ] };
	components['stub-two'] = { label : 'Stub two', preview : 'block', in : [ 'section', 'card' ] };
	components['stub-any'] = { label : 'Stub any', preview : 'block' };
	components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false };

	const found = [ offered( [ 0, 0 ] ), offered( [ 1, 0 ] ), offered( [ 1, 1 ] ), offered( [ 3, 0 ] ) ].join(' | ');

	// The first section is no cover: it is the hero all the same; the second is one, and is no hero for that
	blocks[0].settings.cover = null;
	blocks[1].settings.cover = 90;

	return found === 'Stub hero,Stub any,Stub alone | Stub two,Stub any,Stub alone | Stub two,Stub any | Stub two,Stub any,Stub alone'
		&& offered( [ 0, 0 ] ) === 'Stub hero,Stub any,Stub alone' && offered( [ 1, 0 ] ) === 'Stub two,Stub any,Stub alone' && offered( [ 1, 1 ] ) === 'Stub two,Stub any';
} )() );

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
check( '...a click on it opens the dialog of the column on its Layout tab, with the column selected', ( function() {
	alignOf('1.0')[1].click();
	const opened = dialogBox().open === true && dialogText('builder-dialog-kind') === 'Column of "services"' && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout' && builder._same( builder._sel, [ 1, 0 ] );
	builder._closeDialog();
	builder._openSettings( [ 1, 0 ] );
	buttonOf( screen.getElementById('builder-dialog-tabs'), 'Loop' ).click();
	alignOf('1.0')[0].click();
	return opened && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout';
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
check( '...the stylesheet tints the frame of a column as it tints the frame of a section, and the frame of a card the same: the same ground for each colour, painted as a colour alone so that the stripes of a hidden column stay, and the plain ground for the counter of a light one', [ 'alt', 'tint', 'dark', 'black', 'primary', 'brand-alt' ].every( function( color ) {
	return stylesheet.indexOf( '.builder-frame.builder-color-'+ color+ ',\n.builder-pcol.builder-color-'+ color+ ',\n.builder-card.builder-color-'+ color+ ' {\n\tbackground-color: var(--builder-ground-'+ color+ ');' ) !== -1;
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
	return asked && builder._doc.model.blocks[4].id === 'questions' && builder._doc.model.blocks[4].renamedFrom === undefined && component.source === '/template/page-home/questions/title' && component.create.value === 'Title (Section)' && builder._fresh.questions === true;
} )() );

console.log( '\nRenaming a section in the title of its dialog' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
const heading = screen.getElementById('builder-dialog-heading');
const titleName = screen.getElementById('builder-dialog-name');
const problemLine = screen.getElementById('builder-dialog-problems');
check( 'the title of the dialog of a section is the kind and the name, with the pencil right of the name', dialogText('builder-dialog-kind') === 'Section' && titleName.hidden === false && titleName.textContent === 'hero' && heading.querySelectorAll('.builder-rename').length === 1
	&& iconOf( heading.querySelector('.builder-rename') ) === 'pencil' && heading.querySelector('.builder-rename').title === 'Rename' );
check( '...and the form has no field for the id any more', controlsIn( dialogContent() ).filter( function( control ) { return control.label === 'Id' } ).length === 0 );
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
console.log( '\nThe dialog of the template' );

doc = open();
builder._doc.model.wrapClass = 'site-wrap';
builder._renderEditor();
builder._openSettings( [] );
const templateBody = dialogContent();
const templateKeep = function( model ) { return JSON.stringify( [ model.animate, model.vpa, model.vpaSpeed, model.wrapClass ] ) };
const fold = templateBody.querySelector('details');
check( 'the dialog of the template has no tabs and no name in its title; its fields are in groups under their headings: General, Frames, Viewport animation - and, folded away in the last, the kind of animation for this template', dialogText('builder-dialog-kind') === 'Template' && screen.getElementById('builder-dialog-name').hidden === true
	&& screen.getElementById('builder-dialog-tabs').hidden === true && titlesIn( templateBody ) === 'General,Frames,Viewport animation,Kind for this template' && fold.localName === 'details' && fold.hasAttribute('open') === false && fold.open === false
	&& fold.parentNode.parentNode.querySelector('.builder-group-title').textContent === 'Viewport animation' && fold.querySelector('summary').textContent === 'Kind for this template' );
check( '...the fields in the groups: the name and the file, the header and the footer on a line, the switch of the animation, and in the fold the effect, its strength and the speed', labelsIn( templateBody ) === 'Name,File,Header,Footer,Animate the sections,Effect,Strength,Speed'
	&& templateBody.querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2,2' && fold.querySelectorAll('.builder-line').length === 1 && labelsIn( fold ) === 'Effect,Strength,Speed' );
check( '...the file is text that cannot be changed, with the slug the text keys of the page are made of in its hint; the name is a field', controlOf( templateBody, 'File' ).el.readOnly === true && valueAt( templateBody, 'File' ) === 'page-home.tpl' && controlOf( templateBody, 'Name' ).el.readOnly === false && valueAt( templateBody, 'Name' ) === 'Home'
	&& controlOf( templateBody, 'File' ).el.closest('label').querySelector('.nino-admin-hint').textContent === 'The slug is "home". The name of the file is the category of the text keys of the page (/template/page-home/...), so it does not change.' );
check( '...the effect is a select with words - the plain one first, the others in the order of Nino.css, the two slides named for the motion the visitor sees: slide-left starts at a positive translateX, to the right of its place, and ends at 0, so it slides left -, the strength a group of radio buttons, the speed a select whose first word is the frame', controlOf( templateBody, 'Effect' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join()
	=== '=Default - fade in and rise,zoom=Zoom,zoom-out=Zoom out,slide-left=Slides left,slide-right=Slides right,flip=Flip,blur=Blur'
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
builder._openSettings( [ 0 ] );
const settingsOf = function() { return builder._doc.model.blocks[0].settings };
const stripOf = function() { return screen.getElementById('builder-dialog-tabs') };
check( 'the dialog of a section has four tabs: Layout, Background, Viewport animation and CSS classes', JSON.stringify( dialogTabs() ) === '[["layout","Layout"],["background","Background"],["animation","Viewport animation"],["custom","CSS classes"]]' && stripOf().hidden === false );
check( '...the tabs are tabs of a tab list and the panes are tab panels they name: a tab controls its pane, a pane is labelled by its tab, and the first is chosen and shown', stripOf().getAttribute('role') === 'tablist' && stripOf().querySelectorAll('[role="tab"]').every( function( tab ) {
	const pane = screen.getElementById( tab.getAttribute('aria-controls') );
	return pane !== null && pane.getAttribute('role') === 'tabpanel' && pane.getAttribute('aria-labelledby') === tab.id && dialogContent().contains( pane );
} ) && stripOf().querySelectorAll('[role="tab"]').map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'true,false,false,false' && shownTab( stripOf(), 'builder' ) === 'layout'
	&& stripOf().querySelectorAll('[role="tab"]').map( function( tab ) { return tab.tabIndex } ).join() === '0,-1,-1,-1' );
buttonOf( stripOf(), 'Background' ).click();
check( '...and a tab pressed shows its pane and no other, and is the one stop of the tab list', shownTab( stripOf(), 'builder' ) === 'background' && stripOf().querySelectorAll('[role="tab"]').map( function( tab ) { return tab.getAttribute('aria-selected') } ).join() === 'false,true,false,false'
	&& stripOf().querySelectorAll('[role="tab"]').map( function( tab ) { return tab.tabIndex } ).join() === '-1,0,-1,-1' );
buttonOf( stripOf(), 'Layout' ).click();

const layout = paneOf('layout');
check( 'Layout: the width of the row, the two switches, the cover with its height and the place of its content, the two alignments as icons, the spacing as a table', JSON.stringify( labelList( layout ) ) === JSON.stringify( [ 'Row width', 'Full width', 'Full height', 'Cover', 'Height in percent of the window', 'Position of the content', 'Vertical alignment', 'Horizontal alignment', 'Above, Margin', 'Above, Padding', 'Below, Margin', 'Below, Padding' ] )
	&& JSON.stringify( controlsIn( layout ).map( function( control ) { return control.kind } ) ) === JSON.stringify( [ 'select', 'switch', 'switch', 'switch', 'number', 'select', 'group', 'group', 'select', 'select', 'select', 'select' ] ) );
check( '...the width of the row is a select of the three it knows', controlOf( layout, 'Row width' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Normal,narrow=Narrow,wide=Wide' && valueAt( layout, 'Row width' ) === 'wide' );
check( '...full width and full height are two switches in one line, both on at once if you like', layout.querySelectorAll('.builder-line')[0].children.length === 2 && layout.querySelectorAll('.builder-line')[0].querySelectorAll('.nino-admin-switch').length === 2
	&& valueAt( layout, 'Full width' ) === true && valueAt( layout, 'Full height' ) === false && ( change( layout, 'Full height', true ), settingsOf().fullheight === true && settingsOf().fullwidth === true && valueAt( layout, 'Full width' ) === true )
	&& ( change( layout, 'Full width', false ), settingsOf().fullwidth === false && settingsOf().fullheight === true ) );
check( '...the alignments are two lines of icons in one line: radio buttons, each with its word as its title and its label - the vertical one Top, Middle and Bottom, the horizontal one Inherited as a word and Left, Centre and Right', layout.querySelectorAll('.builder-line')[2].children.length === 2 && ( function() {
	const vertical = controlOf( layout, 'Vertical alignment' ).el;
	const horizontal = controlOf( layout, 'Horizontal alignment' ).el;
	const options = function( group ) { return group.querySelectorAll('.builder-segment-option').map( function( option ) { return [ option.querySelector('input').value, option.title, option.querySelector('input').getAttribute('aria-label'), iconOf( option ) ].join(':') } ).join() };
	return vertical.getAttribute('role') === 'radiogroup' && horizontal.getAttribute('role') === 'radiogroup'
		&& options( vertical ) === ':Top:Top:align-top,middle:Middle:Middle:align-middle,bottom:Bottom:Bottom:align-bottom'
		&& options( horizontal ) === ':::,left:Left:Left:align-left,center:Centre:Centre:align-center,right:Right:Right:align-right'
		&& horizontal.querySelector('.builder-segment-face').textContent === 'Inherited'
		&& radiosOf( vertical ).every( function( radio ) { return radio.type === 'radio' && radio.name === radiosOf( vertical )[0].name } ) && radiosOf( vertical )[0].name !== radiosOf( horizontal )[0].name;
} )() );
check( '...and the choice is written: the vertical alignment as it is chosen, the horizontal one too', valueAt( layout, 'Vertical alignment' ) === 'middle' && ( change( layout, 'Vertical alignment', 'bottom' ), settingsOf().rowAlign === 'bottom' ) && ( change( layout, 'Vertical alignment', '' ), settingsOf().rowAlign === '' )
	&& valueAt( layout, 'Horizontal alignment' ) === '' && ( change( layout, 'Horizontal alignment', 'center' ), settingsOf().text === 'center' ) && valueAt( layout, 'Horizontal alignment' ) === 'center' );
check( '...the spacing is a table: Margin and Padding over the columns, Above and Below at the head of the rows, a select of 0 to 6 in each cell - with the word of the cell for its label', ( function() {
	const table = layout.querySelector('table');
	const heads = table.querySelectorAll('thead th');
	const rows = table.querySelectorAll('tbody tr');
	return texts( heads ) === 'Spacing,Margin,Padding' && heads.every( function( head ) { return head.getAttribute('scope') === 'col' } ) && rows.length === 2 && rows.map( function( row ) { return row.querySelector('th').textContent } ).join() === 'Above,Below'
		&& rows.every( function( row ) { return row.querySelector('th').getAttribute('scope') === 'row' && row.querySelectorAll('select').length === 2 } )
		&& texts( table.querySelector('select').children ) === 'Default,0,1,2,3,4,5,6' && table.querySelector('select').children.map( function( option ) { return option.value } ).join() === ',0,1,2,3,4,5,6';
} )() );
check( '...and each cell writes its own setting: above the margin and the padding, below the margin and the padding', ( function() {
	change( layout, 'Above, Margin', '3' );
	change( layout, 'Above, Padding', '1' );
	change( layout, 'Below, Margin', '5' );
	change( layout, 'Below, Padding', '2' );
	return [ settingsOf().mt, settingsOf().pt, settingsOf().mb, settingsOf().pb ].join() === '3,1,5,2' && valueAt( layout, 'Below, Margin' ) === '5' && changes === 9;
} )() );

const heightLabel = 'Height in percent of the window';
check( 'the cover is one line of three fields: a switch, its height in percent of the window\'s beside it, and the place of its content - the hero opens with the cover on, 100 in the number, and its content in the middle', layout.querySelectorAll('.builder-line')[1].children.length === 3
	&& controlOf( layout, 'Cover' ).kind === 'switch' && controlOf( layout, heightLabel ).kind === 'number' && controlOf( layout, 'Position of the content' ).kind === 'select'
	&& valueAt( layout, 'Cover' ) === true && String( valueAt( layout, heightLabel ) ) === '100' && controlOf( layout, heightLabel ).el.disabled === false && valueAt( layout, 'Position of the content' ) === '' && controlOf( layout, 'Position of the content' ).el.disabled === false );
check( '...the place of the content is the middle, the top or the bottom, with the words of the panel - and the switch and the place say what they are for under their names, the number does not', controlOf( layout, 'Position of the content' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Middle,top=Top,bottom=Bottom'
	&& controlOf( layout, 'Cover' ).el.closest('label').querySelector('small').textContent === words['/_admin/builder/hint/cover'] && controlOf( layout, 'Position of the content' ).el.closest('label').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/content-pos']
	&& controlOf( layout, heightLabel ).el.closest('label').querySelector('small') === null );

const ground = paneOf('background');
check( 'Background: the colour, the border with the switch for the brand colour beside it, the picture - and under it, where the picture is, the upload of a picture and its alt text -, whether it is a parallax and whether it is dimmed, the focus - and the lines hold two fields each', JSON.stringify( labelList( ground ) ) === JSON.stringify( [ 'Colour', 'Border', 'In the brand colour', 'Upload a picture', 'Alternative text (English (US))', 'Parallax', 'Dim the picture', 'No focus', 'Focus' ] )
	&& ground.querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2,2' );
check( '...the parallax and the dimming are two switches on one line, in reach where there is a picture and, for the dimming, a cover or a parallax: the hero has the dimming on and the parallax off, and each says what it is for under its name', controlOf( ground, 'Parallax' ).kind === 'switch' && controlOf( ground, 'Dim the picture' ).kind === 'switch'
	&& controlOf( ground, 'Parallax' ).el.closest('.builder-line') === controlOf( ground, 'Dim the picture' ).el.closest('.builder-line') && valueAt( ground, 'Parallax' ) === false && valueAt( ground, 'Dim the picture' ) === true
	&& controlOf( ground, 'Parallax' ).el.disabled === false && controlOf( ground, 'Dim the picture' ).el.disabled === false
	&& controlOf( ground, 'Parallax' ).el.closest('label').querySelector('small').textContent === words['/_admin/builder/hint/parallax'] && controlOf( ground, 'Dim the picture' ).el.closest('label').querySelector('small').textContent === words['/_admin/builder/hint/dim'] );
check( '...the colour is a select with a patch beside it that follows the choice - and the patch is a patch of the page, not of the picture', ( function() {
	const swatch = ground.querySelector('.builder-swatch');
	const first = swatch.dataset.color;
	change( ground, 'Colour', 'dark' );
	const second = swatch.dataset.color;
	change( ground, 'Colour', '' );
	return first === 'black' && second === 'dark' && swatch.dataset.color === 'plain' && swatch.getAttribute('aria-hidden') === 'true' && swatch.parentNode.contains( controlOf( ground, 'Colour' ).el ) && settingsOf().color === ''
		&& controlOf( ground, 'Colour' ).el.children.map( function( option ) { return option.value } ).join() === ',alt,tint,dark,black,primary,brand-alt' && swatch.style.getPropertyValue('--builder-swatch') === '';
} )() );
check( '...the colours and the border have words of their own, not the names of the classes of Nino.css - and the empty value of the border has its word, not the lower case none of the label', controlOf( ground, 'Colour' ).el.children.map( function( option ) { return option.textContent } ).join() === 'Default,Alternate,Tinted,Dark,Black,Brand colour,Second colour'
	&& controlOf( ground, 'Border' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=None,1=Thin,2=Medium,3=Strong' );
check( '...the border is a strength and, beside it on the line, a switch for the brand colour: the select writes the strength, the switch the colour - two settings of the section, and neither takes the other along; a switch alone is a border of the brand colour with no strength', ( function() {

	const state = function() { return settingsOf().border+ '/'+ settingsOf().borderPrimary };
	const line = controlOf( ground, 'Border' ).el.closest('.builder-line');
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

	return start === '/false' && strength === '2/false' && both === '2/true' && alone === '/true' && state() === '/false' && line.children.length === 2 && line.children[0].contains( controlOf( ground, 'Border' ).el ) && line.children[1].contains( controlOf( ground, 'In the brand colour' ).el )
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

	const alone = paneOf('background');
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
change( paneOf('background'), 'Colour', 'dark' );
const patch = function() { return paneOf('background').querySelector('.builder-swatch').style.getPropertyValue('--builder-swatch') };
const darkPatch = patch();
change( paneOf('background'), 'Colour', 'primary' );
const primaryPatch = patch();
change( paneOf('background'), 'Colour', 'tint' );
check( '...the colour of the patch is read at the time from the custom properties of Nino.css the preview has - and where the page does not know one the patch has no colour of its own, and is the neutral grey of the stylesheet', darkPatch === '#112233' && primaryPatch === 'rgb(10, 20, 30)' && patch() === '' && paneOf('background').querySelector('.builder-swatch').dataset.color === 'tint' );
delete sandbox.getComputedStyle;
check( 'the picture behind the section: a row with the slot as text and an icon button beside it that has the source for its title and its label', ( function() {
	const row = paneOf('background').querySelector('.builder-source');
	const button = row.querySelector('button');
	return row.querySelector('.builder-source-caption').textContent === 'Current picture:' && row.querySelector('.builder-source-current').textContent === '/template/page-home/hero/background' && button.className === 'builder-icon-btn' && button.title === 'Source'
		&& button.getAttribute('aria-label') === 'Source' && iconOf( button ) === 'source' && row.querySelector('.builder-source-line').contains( button );
} )() );

const focusGrid = function() { return controlOf( paneOf('background'), 'Focus' ).el };
check( 'the focus of the picture is a grid of nine radio buttons in the order of Nino.css, each with its place for its label, and a switch that says there is none - the focus the section has is the one that is on', ( function() {
	return focusGrid().getAttribute('role') === 'radiogroup' && radiosOf( focusGrid() ).map( function( radio ) { return radio.value+ '='+ radio.getAttribute('aria-label') } ).join() === '1=Top left,2=Top centre,3=Top right,4=Centre left,5=Centre,6=Centre right,7=Bottom left,8=Bottom centre,9=Bottom right'
		&& valueAt( paneOf('background'), 'Focus' ) === '5' && valueAt( paneOf('background'), 'No focus' ) === false && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === false } ) && focusGrid().classList.contains('is-disabled') === false;
} )() );
change( paneOf('background'), 'Focus', '9' );
check( '...a place is written as its number', builder._doc.model.blocks[0].background.focus === 9 && valueAt( paneOf('background'), 'Focus' ) === '9' );
change( paneOf('background'), 'No focus', true );
check( '...no focus is null, and the grid is greyed and takes no choice while there is none', builder._doc.model.blocks[0].background.focus === null && focusGrid().classList.contains('is-disabled') && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === true && radio.checked === false } )
	&& change( paneOf('background'), 'Focus', '3' ) === false && builder._doc.model.blocks[0].background.focus === null );
change( paneOf('background'), 'No focus', false );
check( '...and a focus again starts in the middle', builder._doc.model.blocks[0].background.focus === 5 && valueAt( paneOf('background'), 'Focus' ) === '5' && focusGrid().classList.contains('is-disabled') === false );
change( paneOf('background'), 'Parallax', true );
buttonOf( paneOf('background'), 'Remove the picture' ).click();
check( 'the picture is taken away with its button: no background, the row says none, the focus and the button are off', builder._doc.model.blocks[0].background === null && paneOf('background').querySelector('.builder-source-current').textContent === 'none' && focusGrid().classList.contains('is-disabled')
	&& controlOf( paneOf('background'), 'No focus' ).el.disabled === true && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === true && preview().children[0].querySelector('.is-background') === null );
check( '...a section that loses its picture loses the parallax and the dimming with it, and both are off and out of reach again', settingsOf().parallax === false && settingsOf().dim === false && valueAt( paneOf('background'), 'Parallax' ) === false && valueAt( paneOf('background'), 'Dim the picture' ) === false
	&& controlOf( paneOf('background'), 'Parallax' ).el.disabled === true && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === true && settingsOf().cover === 100 );
builder._openSettings( [ 1 ] );
const sectionOne = function() { return builder._doc.model.blocks[1].settings };
check( '...so is a section that has none to begin with, and so are its parallax and its dimming', controlOf( paneOf('background'), 'No focus' ).el.disabled === true && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === true } ) && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === true
	&& controlOf( paneOf('background'), 'Parallax' ).el.disabled === true && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === true );
check( 'a section that is no cover has the switch off, a number that is empty and takes no input, and the place of its content out of reach - a parallax has the place as a cover has, and a section that is neither stands in the flow of the page', valueAt( paneOf('layout'), 'Cover' ) === false && valueAt( paneOf('layout'), heightLabel ) === ''
	&& controlOf( paneOf('layout'), heightLabel ).el.disabled === true && controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === true && sectionOne().cover === null && sectionOne().parallax === false );
change( paneOf('layout'), 'Cover', true );
check( '...the cover switched on is 90 high, what Nino.ui.js makes of a cover that names no height: the model has it, the number shows it and takes input, and the place of the content wakes', sectionOne().cover === 90 && valueAt( paneOf('layout'), 'Cover' ) === true
	&& String( valueAt( paneOf('layout'), heightLabel ) ) === '90' && controlOf( paneOf('layout'), heightLabel ).el.disabled === false && controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === false );
change( paneOf('layout'), heightLabel, 60 );
check( '...a height typed is written', sectionOne().cover === 60 && valueAt( paneOf('layout'), 'Cover' ) === true );
check( '...and a field on its way to a number does not take the cover away: one emptied, or one out of bounds, leaves the height as it is - and once the typing is over the field says the height in force', [ '', 0, 101, -5 ].every( function( typed ) { change( paneOf('layout'), heightLabel, typed ); return sectionOne().cover === 60 } ) && String( valueAt( paneOf('layout'), heightLabel ) ) === '60' );
change( paneOf('layout'), 'Position of the content', 'bottom' );
change( paneOf('layout'), heightLabel, 150 );
check( '...and once the typing is over a value out of bounds goes back to the height in force: 150 typed, 60 read', String( valueAt( paneOf('layout'), heightLabel ) ) === '60' && sectionOne().cover === 60 );
const bottom = sectionOne().contentPos;
change( paneOf('layout'), 'Position of the content', 'top' );
const top = sectionOne().contentPos;
change( paneOf('layout'), 'Position of the content', '' );
check( '...the place of the content is written as its word, the top or the bottom, and the middle as none', bottom === 'bottom' && top === 'top' && sectionOne().contentPos === '' && valueAt( paneOf('layout'), 'Position of the content' ) === '' );
sectionOne().dim = true;
change( paneOf('layout'), 'Cover', false );
check( 'the cover switched off is none in the model, the number is empty and out of reach, the place of the content sleeps again - and the scrim goes with the cover, as there is nothing left to dim', sectionOne().cover === null && valueAt( paneOf('layout'), 'Cover' ) === false
	&& valueAt( paneOf('layout'), heightLabel ) === '' && controlOf( paneOf('layout'), heightLabel ).el.disabled === true && controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === true && sectionOne().dim === false );
sectionOne().parallax = true;
sectionOne().dim = true;
builder._refreshTab('background');
check( 'a parallax a template wrote without a picture, or that lost it, is not stuck: a switch that is on is in reach, and switched off it takes the scrim with it and both sleep', controlOf( paneOf('background'), 'Parallax' ).el.disabled === false && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === false
	&& change( paneOf('background'), 'Parallax', false ) === true && sectionOne().parallax === false && sectionOne().dim === false
	&& controlOf( paneOf('background'), 'Parallax' ).el.disabled === true && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === true );
builder._doc.model.blocks[1].background = { slot : '/template/page-home/services/background', focus : 5 };
builder._refreshTab('background');
check( 'with a picture the parallax is in reach, and the dimming is not while the section is neither a cover nor a parallax - there is no scrim in Nino.css for it', controlOf( paneOf('background'), 'Parallax' ).el.disabled === false && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === true );
change( paneOf('background'), 'Parallax', true );
check( '...a parallax wakes the dimming in its own tab and the place of the content in the Layout tab, where the cover stays off', sectionOne().parallax === true && sectionOne().cover === null && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === false
	&& controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === false && valueAt( paneOf('layout'), 'Cover' ) === false );
change( paneOf('background'), 'Dim the picture', true );
change( paneOf('background'), 'Parallax', false );
check( '...and switched off in a section that is no cover it takes the scrim with it: both sleep again, here and in the Layout tab', sectionOne().parallax === false && sectionOne().dim === false && valueAt( paneOf('background'), 'Dim the picture' ) === false
	&& controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === true && controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === true );
change( paneOf('layout'), 'Cover', true );
check( '...a cover wakes the dimming in the Background tab as well', sectionOne().cover === 90 && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === false );
change( paneOf('background'), 'Dim the picture', true );
change( paneOf('background'), 'Parallax', true );
change( paneOf('background'), 'Parallax', false );
check( '...the parallax switched off in a cover keeps the scrim, which is the cover\'s', sectionOne().cover === 90 && sectionOne().parallax === false && sectionOne().dim === true && valueAt( paneOf('background'), 'Dim the picture' ) === true );
change( paneOf('background'), 'Parallax', true );
change( paneOf('layout'), 'Cover', false );
check( '...and so is the cover switched off in a parallax: the scrim stays, and so does the place of the content', sectionOne().cover === null && sectionOne().parallax === true && sectionOne().dim === true
	&& controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === false && valueAt( paneOf('background'), 'Dim the picture' ) === true );
change( paneOf('layout'), 'Cover', true );
change( paneOf('layout'), heightLabel, 60 );
change( paneOf('layout'), 'Position of the content', 'bottom' );
change( paneOf('background'), 'Border', '3' );
change( paneOf('background'), 'In the brand colour', true );
check( '...the other fields of the picture and of the cover write their settings: the parallax and the dimming in the Background tab, the cover, its height and the place of the content in the Layout tab', ( function() {
	const settings = sectionOne();
	return settings.parallax === true && settings.dim === true && settings.contentPos === 'bottom' && settings.cover === 60 && settings.border === '3' && settings.borderPrimary === true;
} )() );
buttonOf( paneOf('background'), 'Remove the picture' ).click();
check( '...and the picture taken away takes the parallax and the dimming with it, and nothing of the cover: its height and the place of its content stay', sectionOne().parallax === false && sectionOne().dim === false && sectionOne().cover === 60 && sectionOne().contentPos === 'bottom'
	&& controlOf( paneOf('background'), 'Parallax' ).el.disabled === true && controlOf( paneOf('layout'), heightLabel ).el.disabled === false );
change( paneOf('layout'), 'Cover', false );
builder._doc.model.blocks[1].background = { slot : '/template/page-home/services/background', focus : 5 };
builder._refreshTab('background');
change( paneOf('background'), 'Parallax', true );
const reach = controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === false;
buttonOf( paneOf('background'), 'Remove the picture' ).click();
check( '...and from a parallax that is no cover the picture taken away takes the reach of the place of the content with it, in the Layout tab: the parallax was all that gave it', reach === true && sectionOne().parallax === false && sectionOne().cover === null
	&& controlOf( paneOf('layout'), 'Position of the content' ).el.disabled === true );

doc = open();
builder._openSettings( [ 0 ] );
const animationPane = function() { return paneOf('animation') };
check( 'Viewport animation: the section carries what the template gives, none, or an animation of its own - with a template that does not animate, none is like the template and there is no off', controlOf( animationPane(), 'Animation' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'like=Like the template (off),own=Own'
	&& controlOf( animationPane(), 'Animation' ).el.title === words['/_admin/builder/hint/vpa-section-off'] );
check( '...a section with the plain animation has an animation of its own where the template has none: its fields are there - the effect and its strength, the speed and how often, the delay and the duration, a line each', valueAt( animationPane(), 'Animation' ) === 'own'
	&& JSON.stringify( labelList( animationPane() ) ) === JSON.stringify( [ 'Animation', 'Effect', 'Strength', 'Speed', 'Repeat', 'Delay', 'Duration' ] ) && animationPane().querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2,2,2' );
check( '...its effects are those of Nino.css and no word for none - that is the choice above -, its repeat is once or repeat, its speed the three', controlOf( animationPane(), 'Effect' ).el.children.map( function( option ) { return option.value } ).join() === ',zoom,zoom-out,slide-left,slide-right,flip,blur'
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
	&& valueAt( animationPane(), 'Animation' ) === 'like' && labelList( animationPane() ).length === 1 && controlOf( animationPane(), 'Animation' ).el.title === words['/_admin/builder/hint/vpa-section'] );
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

const custom = paneOf('custom');
check( 'CSS classes: the class of the section and the class of the row on a line, with the words about classes of Nino.css above them', JSON.stringify( labelList( custom ) ) === JSON.stringify( [ 'Class of the section', 'Class of the row' ] ) && custom.querySelectorAll('.builder-line')[0].children.length === 2
	&& custom.children[0].textContent === words['/_admin/builder/hint/custom'] && ( change( custom, 'Class of the section', 'my-hero' ), change( custom, 'Class of the row', 'my-row' ), settingsOf().custom === 'my-hero' && settingsOf().rowCustom === 'my-row' ) );

doc = open();
builder._problems = [ 'the section "services": the source "x" means nothing here', 'the section "hero": the name is bad' ];
builder._openSettings( [ 1 ] );
check( 'what a refused save said of a section stands over its first tab', paneOf('layout').children[0].className === 'nino-admin-error' && paneOf('layout').children[0].textContent === 'the section "services": the source "x" means nothing here' && paneOf('background').children[0].className !== 'nino-admin-error' );

doc = open();
settingsOf().rowAlign = 'center';
builder._openSettings( [ 0 ] );
check( 'a vertical alignment written by hand that the form has no button for - nino-grid-center - is left as it is: no button is chosen, until one is', valueAt( paneOf('layout'), 'Vertical alignment' ) === null && settingsOf().rowAlign === 'center'
	&& ( change( paneOf('layout'), 'Vertical alignment', 'middle' ), settingsOf().rowAlign === 'middle' ) );
console.log( '\nThe dialog of a column, and the viewports in its tables' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0 ] );
const colOf = function() { return builder._doc.model.blocks[0].cols[0] };
check( 'the dialog of a column has five tabs: Layout, Background, Loop, Viewport animation and CSS classes - the name of the section is not part of its title, no pencil is', dialogText('builder-dialog-kind') === 'Column of "hero"' && screen.getElementById('builder-dialog-name').hidden === true
	&& JSON.stringify( dialogTabs() ) === '[["layout","Layout"],["background","Background"],["loop","Loop"],["animation","Viewport animation"],["custom","CSS classes"]]' && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout' && screen.getElementById('builder-dialog-heading').querySelectorAll('.builder-rename').length === 0 );

const widths = function() { return paneOf('layout').querySelector('table') };
check( 'in a viewport the table of the widths has the row of that viewport only - the device with its icon and its word, the width, whether the column is hidden there - and under it a link to all of them', ( function() {
	const rows = widths().querySelectorAll('tbody tr');
	return texts( widths().querySelectorAll('thead th') ) === 'Device,Width,Hidden' && rows.length === 1 && rows[0].querySelector('th').textContent === 'Desktop' && iconOf( rows[0] ) === 'monitor' && rows[0].querySelector('th').getAttribute('scope') === 'row'
		&& valueAt( paneOf('layout'), 'Width, Desktop' ) === '66' && valueAt( paneOf('layout'), 'Hidden, Desktop' ) === false && controlOf( paneOf('layout'), 'Hidden, Desktop' ).kind === 'box'
		&& controlOf( paneOf('layout'), 'Width, Desktop' ).el.children.map( function( option ) { return option.value } ).join() === '25,33,50,66,75,100' && paneOf('layout').querySelectorAll('.builder-link-btn').map( function( link ) { return link.textContent } ).join() === 'All viewports'
		&& widths().nextSibling === paneOf('layout').querySelector('.builder-link-btn');
} )() );
check( '...and the rest of the tab: one line with the vertical alignment of the components and the horizontal alignment, in this order, both as icons - the vertical one with Off as a word, and with its hint -, the gap on a line of its own below, and under it the spacing', JSON.stringify( labelList( paneOf('layout') ) ) === JSON.stringify( [ 'Width, Desktop', 'Hidden, Desktop', 'Vertical alignment', 'Horizontal alignment', 'Gap (--space-*)', 'Inside', 'Top', 'Bottom' ] )
	&& valueAt( paneOf('layout'), 'Horizontal alignment' ) === 'left' && valueAt( paneOf('layout'), 'Vertical alignment' ) === '' && controlOf( paneOf('layout'), 'Vertical alignment' ).el.querySelectorAll('.builder-segment-option').map( function( option ) { return option.querySelector('input').value+ ':'+ ( iconOf( option ) ?? option.textContent ) } ).join()
		=== ':Off,start:stack-start,center:stack-center,end:stack-end' && controlOf( paneOf('layout'), 'Gap (--space-*)' ).el.children.map( function( option ) { return option.value } ).join() === ',0,1,2,3,4,5,6'
	&& ( function() {
		const lines = paneOf('layout').querySelectorAll('.builder-line');
		const gap = controlOf( paneOf('layout'), 'Gap (--space-*)' ).el;
		return lines.length === 2 && lines[0].children.length === 2 && lines[0].children[0].contains( controlOf( paneOf('layout'), 'Vertical alignment' ).el ) && lines[0].children[1].contains( controlOf( paneOf('layout'), 'Horizontal alignment' ).el )
			&& lines[0].children[0].querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/stack-align'] && lines[0].nextSibling.contains( gap ) && lines[0].contains( gap ) === false;
	} )() );
change( paneOf('layout'), 'Width, Desktop', '33' );
change( paneOf('layout'), 'Horizontal alignment', 'right' );
change( paneOf('layout'), 'Vertical alignment', 'center' );
change( paneOf('layout'), 'Gap (--space-*)', '3' );
check( '...what the tab writes is the width of the viewport, the alignments and the gap', colOf().width.l === 33 && colOf().text === 'right' && colOf().stackAlign === 'center' && colOf().stackGap === '3' && JSON.stringify( colOf().width ) === '{"s":100,"m":100,"l":33}' );
check( '...the spacing is one line under the gap with its caption in front: Inside, Top and Bottom, a select of the steps 0 to 6 each, in the words the section has for them - none is the default - and none set to begin with', ( function() {
	const lines = paneOf('layout').querySelectorAll('.builder-line');
	const spacing = lines[1];
	return spacing.classList.contains('has-caption') && spacing.children[0].className === 'builder-line-caption' && spacing.children[0].textContent === 'Spacing' && spacing.children.length === 4
		&& lines[0].nextSibling.contains( controlOf( paneOf('layout'), 'Gap (--space-*)' ).el ) && lines[0].nextSibling.nextSibling === spacing
		&& [ 'Inside', 'Top', 'Bottom' ].every( function( label ) {
			const control = controlOf( paneOf('layout'), label );
			return control.kind === 'select' && spacing.contains( control.el ) && control.el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Default,0=0,1=1,2=2,3=3,4=4,5=5,6=6' && valueAt( paneOf('layout'), label ) === '';
		} ) && colOf().p === '' && colOf().mt === '' && colOf().mb === '';
} )() );
change( paneOf('layout'), 'Inside', '3' );
change( paneOf('layout'), 'Top', '1' );
change( paneOf('layout'), 'Bottom', '0' );
check( '...what the line writes is the padding, the margin above and the margin below of the column, each a step as text - 0 is a step of its own and is not none - and into nothing else', colOf().p === '3' && colOf().mt === '1' && colOf().mb === '0' && colOf().stackGap === '3'
	&& valueAt( paneOf('layout'), 'Inside' ) === '3' && valueAt( paneOf('layout'), 'Bottom' ) === '0' && builder._doc.model.blocks[0].settings.mt === '' && builder._doc.model.blocks[0].settings.pt === '' );
change( paneOf('layout'), 'Top', '' );
check( '...none puts the step away again', colOf().mt === '' && colOf().p === '3' );
change( paneOf('layout'), 'Inside', '' );
change( paneOf('layout'), 'Bottom', '' );

buttonOf( paneOf('layout'), 'All viewports' ).click();
check( 'the link shows all the viewports: the preview is in the view of all of them (and its button the pressed one), and the tables of the dialog have a row for each - the link is gone, the tab is where it was', builder._viewport === 'g' && preview().dataset.viewport === 'g' && screen.getElementById('builder-viewport-g').getAttribute('aria-pressed') === 'true'
	&& widths().querySelectorAll('tbody tr').map( function( row ) { return row.querySelector('th').textContent+ ':'+ iconOf( row ) } ).join() === 'Mobile:smartphone,Tablet:tablet,Desktop:monitor' && paneOf('layout').querySelector('.builder-link-btn') === null
	&& JSON.stringify( labelList( paneOf('layout') ).slice( 0, 6 ) ) === JSON.stringify( [ 'Width, Mobile', 'Hidden, Mobile', 'Width, Tablet', 'Hidden, Tablet', 'Width, Desktop', 'Hidden, Desktop' ] ) && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout' && dialogBox().open === true );
builder._openSettings( [ 0, 0 ] );
check( 'in the view of all the viewports a dialog opened has the three rows from the start - and no link', widths().querySelectorAll('tbody tr').length === 3 && paneOf('layout').querySelector('.builder-link-btn') === null );
change( paneOf('layout'), 'Width, Mobile', '50' );
change( paneOf('layout'), 'Width, Desktop', '25' );
change( paneOf('layout'), 'Hidden, Tablet', true );
change( paneOf('layout'), 'Hidden, Mobile', true );
change( paneOf('layout'), 'Hidden, Mobile', false );
check( '...what the table writes is the width of each viewport, and the viewports that hide the column: none that shows it', colOf().width.s === 50 && colOf().width.l === 25 && colOf().width.m === 100 && JSON.stringify( colOf().hidden ) === '{"m":true}' );

[ [ 's', 'Mobile', 'smartphone' ], [ 'm', 'Tablet', 'tablet' ] ].forEach( function( view ) {
	builder._setView( view[0] );
	builder._openSettings( [ 0, 0 ] );
	check( 'in the view of '+ view[1]+ ' the table has its row alone, with the link under it', widths().querySelectorAll('tbody tr').length === 1 && widths().querySelector('th[scope="row"]').textContent === view[1] && iconOf( widths() ) === view[2]
		&& JSON.stringify( labelList( paneOf('layout') ).slice( 0, 2 ) ) === JSON.stringify( [ 'Width, '+ view[1], 'Hidden, '+ view[1] ] ) && paneOf('layout').querySelectorAll('.builder-link-btn').length === 1 );
} );
builder._setView('l');

console.log( '\nThe background of a column' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
buttonOf( stripOf(), 'Background' ).click();
const colGround = function() { return paneOf('background') };
const groundOf = function() { return builder._doc.model.blocks[1].cols[0] };
check( 'Background of a column: the colour first, with the patch beside it, then the rows of the picture - the upload and its alt text, the focus - and the dimming at the end; whether the picture is a parallax is the section\'s alone, and is not here', JSON.stringify( labelList( colGround() ) ) === JSON.stringify( [ 'Colour', 'Upload a picture', 'Alternative text (English (US))', 'No focus', 'Focus', 'Dim the picture' ] )
	&& colGround().children[0].className === 'builder-with-swatch' && colGround().children[0].contains( controlOf( colGround(), 'Colour' ).el ) && colGround().children[0].querySelector('.builder-swatch') !== null && shownTab( stripOf(), 'builder' ) === 'background' );
check( '...the colour is none of its own - which is the surroundings the column is in -, counter, and the colours of a section, in the words the section has for them', controlOf( colGround(), 'Colour' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join()
	=== '=Like the surroundings,counter=Counter,alt=Alternate,tint=Tinted,dark=Dark,black=Black,primary=Brand colour,brand-alt=Second colour' && valueAt( colGround(), 'Colour' ) === '' );
check( '...the choice is written into the column and into nothing else', ( function() {

	change( colGround(), 'Colour', 'counter' );

	const counter = groundOf().color;

	change( colGround(), 'Colour', 'dark' );

	const dark = groundOf().color;
	const rest = builder._doc.model.blocks[1].settings.color+ '/'+ builder._doc.model.blocks[1].cols[1].color;

	change( colGround(), 'Colour', '' );

	return counter === 'counter' && dark === 'dark' && groundOf().color === '' && rest === '/';
} )() );
check( '...the patch is the ground the column ends up with - the section\'s where the column has none of its own - and for counter the counter of the ground of the section, by the pairs of Nino.css: default and alt, tint to default, dark and black, primary and brand-alt', ( function() {

	const pairs = { '' : 'alt', alt : 'plain', tint : 'plain', dark : 'black', black : 'dark', primary : 'brand-alt', 'brand-alt' : 'primary' };

	return Object.keys( pairs ).every( function( around ) {

		builder._closeDialog();
		builder._doc.model.blocks[1].settings.color = around;
		builder._openSettings( [ 1, 0 ] );
		buttonOf( stripOf(), 'Background' ).click();

		const swatch = colGround().querySelector('.builder-swatch');
		const own = swatch.dataset.color;

		change( colGround(), 'Colour', 'counter' );

		const counter = swatch.dataset.color;

		change( colGround(), 'Colour', 'tint' );

		const tint = swatch.dataset.color;

		change( colGround(), 'Colour', '' );

		return own === ( around === '' ? 'plain' : around ) && counter === pairs[around] && tint === 'tint' && swatch.getAttribute('aria-hidden') === 'true' && groundOf().color === '';
	} );
} )() );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
buttonOf( stripOf(), 'Background' ).click();
check( '...the dimming is a switch under the rows of the picture: off while there is no picture to darken - it is greyed out and the column has none', controlOf( colGround(), 'Dim the picture' ).kind === 'switch' && controlOf( colGround(), 'Dim the picture' ).el.disabled === true && valueAt( colGround(), 'Dim the picture' ) === false && groundOf().dim === false );
groundOf().background = { slot : '/template/page-home/services/column-background', focus : null };
builder._refreshTab('background');
check( '...with a picture it can be turned on, and writes the dim of the column', controlOf( colGround(), 'Dim the picture' ).el.disabled === false && ( change( colGround(), 'Dim the picture', true ), groundOf().dim === true ) && valueAt( colGround(), 'Dim the picture' ) === true );
change( colGround(), 'No focus', false );

const middle = groundOf().background.focus;

change( colGround(), 'Focus', '9' );
check( '...the focus of the picture is the grid of the section\'s: it starts in the middle, a place is written as its number into the background of the column - none is null - and the section is left alone', middle === 5 && groundOf().background.focus === 9 && ( change( colGround(), 'No focus', true ), groundOf().background.focus === null ) && builder._doc.model.blocks[1].background === null );
buttonOf( colGround(), 'Remove the picture' ).click();
check( '...a picture taken away takes the dimming with it: the column has no background, its dim is off in the model, and the switch is off and greyed out', groundOf().background === null && groundOf().dim === false && controlOf( colGround(), 'Dim the picture' ).el.disabled === true
	&& valueAt( colGround(), 'Dim the picture' ) === false && buttonOf( colGround(), 'Remove the picture' ).disabled === true );

console.log( '\nThe loop of a column' );

const loopPane = function() { return paneOf('loop') };

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1 ] );
check( 'in the view of one viewport the cells of a loop are a row, with the link to all of them under the table', loopPane().querySelectorAll('tbody tr').length === 1 && loopPane().querySelectorAll('.builder-link-btn').length === 1 && loopPane().querySelector('tbody th').textContent === 'Desktop' );
buttonOf( paneOf('layout'), 'All viewports' ).click();
check( '...the link pressed in the Layout tab has the Loop tab show all of them as well, which is not the tab that is shown', loopPane().querySelectorAll('tbody tr').length === 3 && loopPane().querySelectorAll('.builder-link-btn').length === 0 && paneOf('layout').querySelectorAll('tbody tr').length === 3
	&& shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout' );
doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1, 'x' ] );
buttonOf( loopPane(), 'All viewports' ).click();
check( '...and the other way round', paneOf('layout').querySelectorAll('tbody tr').length === 3 && loopPane().querySelectorAll('tbody tr').length === 3 && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'loop' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0 ] );
check( 'the Loop tab of a column with no loop has the choice of the loop and nothing else: Static, and the loops of the registry - the plain one called the element loop', JSON.stringify( labelList( paneOf('loop') ) ) === JSON.stringify( [ 'Loop' ] ) && valueAt( paneOf('loop'), 'Loop' ) === ''
	&& controlOf( paneOf('loop'), 'Loop' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Static,stack=Element loop,slider=Slider,filter=Filter,list=List' && paneOf('loop').querySelectorAll('.builder-group').length === 0 );
change( paneOf('loop'), 'Loop', 'stack' );
check( '...a loop that is chosen is the kernel\'s, over the first type of the project, with the attributes it has - and the tab shows its form in groups: the data it runs over, the order, the grid of the cells', ( function() {
	const stack = colOf().stack;
	return stack.name === 'stack' && stack.source === '/services' && stack.attributes.cols === '100 50 33' && stack.attributes.gap === '2' && stack.attributes.id === '' && titlesIn( paneOf('loop') ) === 'Data,Order,Grid' && builder._doc.model.blocks[0].cols[0].components.length === 3;
} )() );

doc = open();
builder._renderEditor();
builder._setView('g');
builder._openSettings( [ 1, 1, 'x' ] );
check( 'the loop of a column opens the form of the column on its Loop tab (the loop is no frame, but the column\'s own settings)', dialogText('builder-dialog-kind') === 'Column of "services"' && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'loop' );
check( 'the form of the loop: the kind, then the groups - Data (the type, a note, the query), Order (the field and the direction, the limit and the offset), Grid (the cells of each viewport, the gap and the equal height, the id) - and no field for the cards of the cells: the loop is the grid alone', titlesIn( loopPane() ) === 'Data,Order,Grid'
	&& JSON.stringify( labelList( loopPane() ) ) === JSON.stringify( [ 'Loop', 'Type', 'Query', 'Sort by', 'Direction', 'Limit', 'Offset', 'Cell width, Mobile', 'Cell width, Tablet', 'Cell width, Desktop', 'Gap between the cells', 'Cells of equal height', 'Id of the loop' ] ) );
check( '...the groups hold what they are called after: the type and the query in Data, the order in Order - the limit and the offset on a line -, the cells in Grid with the gap and the equal height on a line and the id below', ( function() {
	const groups = loopPane().querySelectorAll('.builder-group');
	return groups.map( function( group ) { return labelList( group ).join('|') } ).join('~') === 'Type|Query~Sort by|Direction|Limit|Offset~Cell width, Mobile|Cell width, Tablet|Cell width, Desktop|Gap between the cells|Cells of equal height|Id of the loop'
		&& groups[1].querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2' && groups[2].querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2';
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
const colAnimation = function() { return paneOf('animation') };
const pickedOff = function( label ) { return controlOf( colAnimation(), label ).el.disabled === true };
check( 'the animation of a column is its own, and has no like, off or own: the effect and its strength, the speed and how often - the effect is none before it is the plain one, and the words under it are those of a column', JSON.stringify( labelList( colAnimation() ) ) === JSON.stringify( [ 'Effect', 'Strength', 'Speed', 'Repeat' ] )
	&& controlOf( colAnimation(), 'Effect' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'none=No animation,=Default - fade in and rise,zoom=Zoom,zoom-out=Zoom out,slide-left=Slides left,slide-right=Slides right,flip=Flip,blur=Blur'
	&& valueAt( colAnimation(), 'Effect' ) === 'none' && colAnimation().querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2,2'
	&& controlOf( colAnimation(), 'Effect' ).el.title === words['/_admin/builder/hint/vpa-col'] && colAnimation().querySelector('.nino-admin-hint').textContent === 'Whether the animation runs when the column comes into view, and with which effect.' );
check( '...it has no delay and no duration, whatever the animation is: the file keeps a column\'s animation as classes, and a column has no attribute for either - the animation of a section has both', ( function() {
	const column = labelList( colAnimation() );
	builder._openSettings( [ 0 ] );
	const section = labelList( paneOf('animation') );
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
change( paneOf('custom'), 'Class of the column', 'my-col' );
check( 'the class of a column is the only field of the CSS classes tab, with the hint about classes of Nino.css', JSON.stringify( labelList( paneOf('custom') ) ) === JSON.stringify( [ 'Class of the column' ] ) && colOf().custom === 'my-col' && paneOf('custom').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/custom'] );
console.log( '\nThe dialog of a component' );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
const componentAt = function( at ) { return builder._doc.model.blocks[at[0]].cols[at[1]].components[at[2]] };
check( 'the dialog of a component has three tabs: Content, Properties and CSS classes - the title names the component by its label, and no pencil is in it', dialogText('builder-dialog-kind') === 'Component "Title (Section)"' && JSON.stringify( dialogTabs() ) === '[["content","Content"],["properties","Properties"],["custom","CSS classes"]]'
	&& screen.getElementById('builder-dialog-name').hidden === true && dialogBox().classList.contains('is-wide') === false );
check( '...Content: the source as text, with an icon button beside it that opens the dialog of the sources - and under it, for a key the project knows, the text it holds in the language the editor works in', ( function() {
	const row = paneOf('content').querySelector('.builder-source');
	const button = row.querySelector('button');
	return paneOf('content').children.length === 2 && paneOf('content').children[0] === row && paneOf('content').children[1].className === 'builder-content-extra' && row.querySelector('.builder-source-caption').textContent === 'Current:' && row.querySelector('.builder-source-caption').hidden === false
		&& row.querySelector('.builder-source-current').textContent === '/template/page-home/hero/title' && row.querySelector('.builder-source-name').hidden === true
		&& button.className === 'builder-icon-btn' && button.title === 'Source' && button.getAttribute('aria-label') === 'Source' && iconOf( button ) === 'source' && JSON.stringify( labelList( paneOf('content') ) ) === '["Text (English (US))"]';
} )() );
check( '...Properties: the attributes of the schema in the order of the schema, the two of them on a line', JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Tag', 'Style' ] ) && paneOf('properties').children.length === 1 && paneOf('properties').children[0].className === 'builder-line' && paneOf('properties').children[0].children.length === 2
	&& controlOf( paneOf('properties'), 'Tag' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === 'div=div,h3=h3,h4=h4' && controlOf( paneOf('properties'), 'Style' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=none,loud=loud,quiet=quiet'
	&& valueAt( paneOf('properties'), 'Tag' ) === 'h3' && valueAt( paneOf('properties'), 'Style' ) === 'loud' );
change( paneOf('properties'), 'Tag', 'h4' );
change( paneOf('properties'), 'Style', '' );
change( paneOf('custom'), 'Class of the component', 'big' );
check( '...and write the attributes, CSS classes the class of the component', componentAt( [ 0, 0, 0 ] ).attributes.tag === 'h4' && componentAt( [ 0, 0, 0 ] ).attributes.style === '' && componentAt( [ 0, 0, 0 ] ).attributes['class'] === 'big' && JSON.stringify( labelList( paneOf('custom') ) ) === JSON.stringify( [ 'Class of the component' ] )
	&& paneOf('custom').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/custom'] && changes === 3 );

builder._openSettings( [ 0, 0, 2 ] );
check( 'a link is a source of its own, a line of its own - the other attributes follow in the order of the schema, two to a line where both are short, a select that is left over on a line to itself', dialogText('builder-dialog-kind') === 'Component "Button"'
	&& classes( paneOf('properties').children ) === 'builder-source,builder-line,nino-admin-field' && JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Style', 'Size', 'Opens in' ] ) && paneOf('properties').children[1].children.length === 2
	&& paneOf('properties').children[0].querySelector('.builder-source-name').textContent === 'Link' && paneOf('properties').children[0].querySelector('.builder-source-current').textContent === '/_nino/webpage/contact/uri'
	&& paneOf('properties').children[0].querySelector('.builder-source-caption').hidden === true );
builder._openSettings( [ 1, 1, 0 ] );
check( '...a text of its own is alone when the select after it is a focus, which is a grid and has a line to itself - the switch for none and the nine places -, and the select after that is alone: alt, focus, ratio', dialogText('builder-dialog-kind') === 'Component "Image"' && classes( paneOf('properties').children ) === 'nino-admin-field,builder-focus,nino-admin-field' && JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Alternative text', 'No focus', 'Focus', 'Ratio' ] )
	&& controlsIn( paneOf('properties') ).map( function( control ) { return control.kind } ).join() === 'text,switch,group,select' );

check( 'the attributes of a schema are paired in the order of the schema: a select, a switch, a number or a line of text goes with the next of them; a lines field, a link, a picture, a key or the focus of a picture - a select over none and the nine places - has a line to itself', ( function() {
	const places = [ '', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
	const declared = { a : { type : 'select' }, b : { type : 'bool' }, c : { type : 'lines' }, d : { type : 'int' }, e : { type : 'string' }, f : { type : 'href' }, g : { type : 'select' }, h : { type : 'image' }, i : { type : 'key' }, j : { type : 'select' }, k : { type : 'select' }, l : { type : 'select' }, m : { type : 'select', options : places }, n : { type : 'bool' }, o : { type : 'select', options : [ '', 'left' ] } };
	return JSON.stringify( builder._attributeLines( declared ) ) === '[["a","b"],["c"],["d","e"],["f"],["g"],["h"],["i"],["j","k"],["l"],["m"],["n","o"]]' && JSON.stringify( builder._attributeLines( {} ) ) === '[]'
		&& JSON.stringify( builder._attributeLines( registry().components.button.attributes ) ) === '[["href"],["style","size"],["target"]]' && JSON.stringify( builder._attributeLines( registry().components.image.attributes ) ) === '[["alt"],["focus"],["ratio"]]'
		&& JSON.stringify( builder._attributeLines( registry().components.title.attributes ) ) === '[["tag","style"]]';
} )() );

builder._doc.model.blocks[0].cols[0].components.push( { name : 'spacer', source : '', text : null, attributes : { size : '2', 'class' : '' } } );
builder._doc.model.blocks[0].cols[0].components.push( { name : 'unknown-one', source : '', text : null, attributes : { 'class' : '' } } );
builder._doc.model.blocks[0].cols[0].components.push( { name : 'html', source : '', text : null, attributes : { 'class' : '' }, content : '<p>x</p>' } );
const tabsOfComponent = function( at ) { builder._openSettings( at ); return dialogTabs().map( function( tab ) { return tab[0] } ).join() };
const registryWith = registry();
registryWith.components.divider = { label : 'Divider', source : 'none', loop : true, preview : 'block', attributes : [], defaults : { 'class' : '' } };
builder._doc.model.blocks[0].cols[0].components.push( { name : 'divider', source : '', text : null, attributes : { 'class' : '' } } );
builder._registry = registryWith;
check( 'a component has the tabs it has something for: no Content where it has no source (a spacer), no Properties where its schema declares none (a component the registry does not know, the editor of the content), and CSS classes always',
	tabsOfComponent( [ 0, 0, 3 ] ) === 'properties,custom' && tabsOfComponent( [ 0, 0, 4 ] ) === 'content,custom' && tabsOfComponent( [ 0, 0, 5 ] ) === 'content,custom' && tabsOfComponent( [ 0, 0, 6 ] ) === 'custom' );
builder._openSettings( [ 0, 0, 3 ] );
check( '...the spacer holds its size, and its title is its label', dialogText('builder-dialog-kind') === 'Component "Spacer"' && JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Size' ] ) && valueAt( paneOf('properties'), 'Size' ) === '2' );
builder._openSettings( [ 0, 0, 4 ] );
check( '...a component the registry does not know is called by its name, and has a source of the text kind', dialogText('builder-dialog-kind') === 'Component "unknown-one"' && paneOf('content').querySelector('.builder-source') !== null );
builder._openSettings( [ 0, 0, 6 ] );
check( '...and the tab that is the only one is the one that is shown', shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'custom' && dialogTabs().length === 1 );
console.log( '\nThe dialog of a source' );

const sourceButton = function( pane ) { return pane.querySelector('.builder-source button') };
const pickerStrip = function() { return screen.getElementById('builder-picker-tabs') };
const pickItems = function() { return pickerPane('pick').querySelectorAll('.builder-pick-item') };

doc = open();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
sourceButton( paneOf('content') ).click();
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
	&& pickerBox().open === false && dialogBox().open === true && paneOf('content').querySelector('.builder-source-current').textContent === '/template/page-home/hero/subtitle' && changes === 1 );

sourceButton( paneOf('content') ).click();
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
check( '...and using it makes the key the source, with what it is made with: the label of the component for its text - the dialog closes', componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/cta' && componentAt( [ 0, 0, 0 ] ).create.value === 'Title (Section)' && pickerBox().open === false
	&& paneOf('content').querySelector('.builder-source-current').textContent === '/template/page-home/hero/cta' );

sourceButton( paneOf('content') ).click();
buttonOf( pickerStrip(), 'Static' ).click();
const fixedField = function() { return pickerPane('fixed').querySelector('input') };
check( 'Static: a value that stands in the template - a field with a hint and a button to use it', shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && labelsIn( pickerPane('fixed') ) === 'Fixed value' && fixedField().value === '' && buttonOf( pickerPane('fixed'), 'Use it' ).textContent === 'Use it'
	&& pickerPane('fixed').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/fixed'] );
fixedField().value = 'Mehr';
const used = pressKey( fixedField(), 'Enter' );
check( '...the value is the text of the call and no source - Enter uses it as the button does, and goes no further: the key is not for the button the dialog gives the focus back to -, and the row shows it in quotes', used.defaultPrevented === true && componentAt( [ 0, 0, 0 ] ).text === 'Mehr' && componentAt( [ 0, 0, 0 ] ).source === '' && componentAt( [ 0, 0, 0 ] ).create === undefined && pickerBox().open === false
	&& paneOf('content').querySelector('.builder-source-current').textContent === '“Mehr”' );
sourceButton( paneOf('content') ).click();
check( '...and a source that is a fixed value opens on the tab for it, with the value in the field', shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && fixedField().value === 'Mehr' );
screen.getElementById('builder-picker-close').click();
check( '...closing the dialog of the source gives no answer', pickerBox().open === false && dialogBox().open === true && componentAt( [ 0, 0, 0 ] ).text === 'Mehr' && changes === 3 );

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
buttonOf( paneOf('background'), 'Source' ).click();
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
	&& paneOf('background').querySelector('.builder-source-current').textContent === '/project/logo/header/image' );

doc = open();
builder._openSettings( [ 0 ] );
buttonOf( paneOf('background'), 'Source' ).click();
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
		buttonOf( paneOf('background'), 'Source' ).click();
		buttonOf( pickerStrip(), 'Create new' ).click();
		if( kind === 'key' ) {
			pickerBox().open = false;
			builder._doc.model.blocks[0].cols[0].components.length = 1;
			builder._openSettings( [ 0, 0, 0 ] );
			sourceButton( paneOf('content') ).click();
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
	buttonOf( paneOf('background'), 'Source' ).click();
	pickerPane('pick').querySelector('input[type="search"]').value = '';
	pickerPane('pick').querySelector('input[type="search"]').dispatch('input');
	return pickItems().map( function( item ) { return item.querySelector('img') === null ? '' : item.querySelector('img').getAttribute('src') } ).join() === '/uploads/hero.1600x900.jpg,';
} )() );

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 1, 0 ] );
sourceButton( paneOf('content') ).click();
check( 'in a loop the source of a picture is a field of the element first - the fields of the type that hold a picture, and none - then the slots of the project', ( function() {
	const field = controlOf( pickerPane('pick'), 'Field of the element' );
	return pickerPane('pick').querySelector('.builder-source-current').textContent === 'image' && field.el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=none,image=image' && valueAt( pickerPane('pick'), 'Field of the element' ) === 'image'
		&& field.el.closest('label').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/field-image'] && pickerPane('pick').querySelector('.builder-label').textContent === 'Image slot' && pickItems().length === 0;
} )() );
change( pickerPane('pick'), 'Field of the element', '' );
check( '...and the choice of none is the choice of no source', componentAt( [ 1, 1, 0 ] ).source === '' && pickerBox().open === false && paneOf('content').querySelector('.builder-source-current').textContent === 'none' );
builder._openSettings( [ 1, 1, 1 ] );
sourceButton( paneOf('content') ).click();
check( 'the source of a text in a loop is a field of the element that holds a text, the id of the element, or a key - the keys of the section first', controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value } ).join() === ',title,summary,.id'
	&& valueAt( pickerPane('pick'), 'Field of the element' ) === 'title' && pickItems().length === 2 && ( change( pickerPane('pick'), 'Field of the element', 'summary' ), componentAt( [ 1, 1, 1 ] ).source === 'summary' ) );
builder._openSettings( [ 1, 1, 3 ] );
check( 'the button in a loop has the source it has and the fixed text it has: the dialog opens on the fixed value', paneOf('content').querySelector('.builder-source-current').textContent === '.uri' && ( sourceButton( paneOf('content') ).click(), shownTab( pickerStrip(), 'builder-picker' ) === 'fixed' && fixedField().value === 'Mehr' ) );
builder._openSettings( [ 1, 1, 3 ] );
buttonOf( paneOf('properties'), 'Source' ).click();
check( 'the link of a button in a loop may be the uri or the id of the element - or a fixed address, which is a value that stands in the template', controlOf( pickerPane('pick'), 'Field of the element' ).el.children.map( function( option ) { return option.value } ).join() === ',title,summary,.id,.uri'
	&& JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","fixed"]' );
buttonOf( pickerStrip(), 'Static' ).click();
fixedField().value = '/contact';
buttonOf( pickerPane('fixed'), 'Use it' ).click();
check( '...an attribute has no key to make: it has no Create new, and what is used as a fixed value is written as it is', componentAt( [ 1, 1, 3 ] ).attributes.href === '/contact' && pickerBox().open === false && paneOf('properties').querySelector('.builder-source-current').textContent === '“/contact”' );

doc = open();
builder._openSettings( [ 0, 0, 2 ] );
buttonOf( paneOf('properties'), 'Source' ).click();
pickItems();
check( 'the link of a button outside a loop is a key; typed addresses are shown as the fixed values they are', JSON.stringify( tabsOf( pickerStrip(), 'builder-picker' ).map( function( tab ) { return tab[0] } ) ) === '["pick","fixed"]' && pickerPane('pick').querySelector('.builder-source-current').textContent === '/_nino/webpage/contact/uri'
	&& pickerPane('pick').querySelector('.builder-label').textContent === 'Text key' );

doc = open();
builder._keys = [];
builder._keysAnswered = true;
builder._openSettings( [ 0, 0, 0 ] );
sourceButton( paneOf('content') ).click();
check( 'where the keys of the project may not be read the key is typed: a field with the hint, and a key that will do is the answer', ( function() {
	const field = controlOf( pickerPane('pick'), 'Text key' );
	field.el.value = 'nothing';
	field.el.dispatch('input');
	field.el.dispatch('change');
	const refused = componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/title';
	field.el.value = '/template/page-home/hero/other';
	field.el.dispatch('change');
	return field.el.closest('label').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/typed'] && refused && componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/other' && pickerBox().open === false;
} )() );
builder._keysAnswered = false;

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
check( 'a text: a key from the list, a new key with what it is made with, a fixed value - the answers are the arguments of the two functions, and each closes the dialog', JSON.stringify( answer ) === '[["/template/page-home/hero/subtitle",null],["/template/page-home/hero/title-2",{"value":"Title"}],"Hello"]' && pickerBox().open === false );
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
check( 'a block of html has no form: it has the editor, which is a dialog of its own, titled as such, wide, with nothing in it to choose but the markup', dialogText('builder-dialog-kind') === words['/_admin/builder/html/title'] && dialogBox().classList.contains('is-wide') && screen.getElementById('builder-dialog-tabs').hidden === true
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
	return dialogText('builder-dialog-kind') === 'Duplicate' && asked === 'Copy of Home' && texts( screen.getElementById('builder-dialog-actions').children ) === 'Duplicate,Cancel' && screen.getElementById('builder-dialog-tabs').hidden === true
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
	return first[1].name === 'title' && first[1].source === '/template/page-home/hero/title-2' && first[1].create.value === 'Welcome' && builder._doc.model.blocks[0].cols.length === 2 && builder._same( builder._sel, [ 0, 1 ] );
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
	&& builder._animationMode( true, builder._doc.model.blocks[4].settings ) === 'like' && builder._doc.model.blocks[0].cols.length === 2 && JSON.stringify( builder._doc.model.blocks[0].cols[1].width ) === '{"s":100,"m":100,"l":100}'
	&& builder._doc.model.blocks[0].cols[0].components.length === 4 && builder._doc.model.blocks[0].cols[0].components[3].source === '/template/page-home/hero/button' && builder._doc.model.blocks[0].cols[0].components[3].create.value === 'Button' && builder._same( builder._sel, [ 0, 0, 3 ] ) );
builder._addCol( [ 1 ] );
builder._addComponent( [ 1, 1 ], 'title' );
check( '...at the end of the level they are meant for: the column of the services is the third, the component stands last in the loop', builder._doc.model.blocks[1].cols.length === 3 && builder._doc.model.blocks[1].cols[1].components.length === 5 && builder._doc.model.blocks[1].cols[1].components[4].name === 'title'
	&& builder._doc.model.blocks[1].cols[1].components[4].source === '' && builder._doc.model.blocks[1].cols[1].components[4].create === undefined && builder._doc.model.blocks.length === 5 );
check( '...the buttons of the preview do the same: a section, a column - and a component, which is chosen from the menu the button opens', ( function() {
	doc = open();
	builder._renderEditor();
	screen.getElementById('builder-add-section').click();
	preview().children[0].querySelector('.builder-add-col').click();
	preview().children[0].querySelector('.builder-adds .builder-add-button').click();
	const menu = screen.getElementById('builder-menu');
	const opened = menu.hidden === false && menu.getAttribute('role') === 'menu' && menu.children.length === 8 && menu.children.every( function( item ) { return item.getAttribute('role') === 'menuitem' } );
	buttonOf( menu, 'Button' ).click();
	return opened && menu.hidden === true && builder._doc.model.blocks.length === 5 && builder._doc.model.blocks[0].cols.length === 2 && builder._doc.model.blocks[0].cols[0].components.length === 4 && builder._doc.model.blocks[0].cols[0].components[3].name === 'button'
		&& preview().children.length === 5 && changes === 3;
} )() );

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
	&& builder._doc.model.blocks[4].cols[0].components[0].create.value === 'Title (Section)' && builder._fresh.faq === true && builder._fresh.section === undefined );
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

check( 'the stylesheet keeps what the script hides hidden (a button with display set is shown all the same), paints the sections in grey levels, shows the tools of the frame the pointer is over and of the selected one, gives the dialog 44rem and the dialog of a source 34rem - and has no picture of its own: the icons are the sprite\'s', ( function() {
	const colours = stylesheet.split( '.builder-frame.builder-color-' ).slice( 1 ).map( function( rule ) { return rule.slice( 0, rule.indexOf( '}' ) ) } );
	return stylesheet.indexOf( '.builder-icon-btn[hidden] {\n\tdisplay: none;' ) !== -1 && colours.length === 6 && colours.every( function( rule ) { return /var\(--editor-(blue|orange)/.test( rule ) === false } )
		&& stylesheet.indexOf( '.builder-pick.is-hover > header > .builder-tools' ) !== -1 && stylesheet.indexOf( '.builder-pick.is-selected > .builder-tools' ) !== -1 && stylesheet.indexOf( 'width: min(44rem, calc(100vw - 2rem));' ) !== -1
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
	// The script names them as an icon (_icon, _iconButton, _statusItem), as the icon of an option, in DEVICES and in ALIGN_ICONS
	source.replace( /_(?:icon|iconButton)\( '([a-z-]+)'/g, function( all, name ) { named.push( name ) } );
	source.replace( /_statusItem\( '[a-z]+', '([a-z-]+)'/g, function( all, name ) { named.push( name ) } );
	source.replace( /icon\s*:\s*'([a-z-]+)'/g, function( all, name ) { named.push( name ) } );
	Object.keys( builder.DEVICES ).forEach( function( key ) { named.push( builder.DEVICES[key] ) } );
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
const contentPane = function() { return paneOf('content') };
const field = function() { return controlOf( contentPane(), lineLabel ) };
check( 'a component with a text key has the text of its key under its source, in the language the editor works in: a line of text for a key of the plain format, with the limit of the key, a hint that says when it is written - and no editor of the workbench', field().kind === 'text'
	&& field().el.localName === 'input' && field().el.value === 'Welcome' && field().el.maxLength === 160 && field().el.spellcheck === true && contentPane().children[1].className === 'builder-content-extra'
	&& contentPane().children[1].querySelector('.nino-admin-hint').textContent === 'Written to the key when the template is saved.' && textEditors.length === 0 );
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
	const children = paneOf('content').children.length;
	builder._closeDialog();
	return children === 1 || console.log( '      '+ what+ ': '+ children+ ' children' ) === undefined;
} ) );
check( '...nor for a component that has a fixed text and a key both - the model of a source that was left half changed: the text is fixed', ( function() {
	const both = Object.assign( {}, heroTitle(), { text : 'Fixed' } );
	return both.source === '/template/page-home/hero/title' && builder._textOf( both, 'en_US', builder._keys, builder._registry ) === null && builder._textOf( heroTitle(), 'en_US', builder._keys, builder._registry ) !== null;
} )() );
builder._keys = [];
builder._openSettings( [ 0, 0, 0 ] );
check( '...nor for an account that cannot read the keys: it knows no text to show, and it would only be the text of a key it cannot see', paneOf('content').children.length === 1 );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._addComponent( [ 0, 0 ], 'title' );
builder._addComponent( [ 0, 0 ], 'text' );
const newTitle = builder._doc.model.blocks[0].cols[0].components[3];
const newText = builder._doc.model.blocks[0].cols[0].components[4];
builder._openSettings( [ 0, 0, 3 ] );
check( 'a key the save is going to make has a field too: it holds the text the key is made with, a line of text for a component that asks for no format', field().el.value === 'Title (Section)' && field().el.localName === 'input' && newTitle.create.value === 'Title (Section)' && textEditors.length === 0 );
check( '...a text typed there is kept as for a key that is there - the key is made with its first text and written with this one', ( change( contentPane(), lineLabel, 'My title' ), JSON.stringify( newTitle.values ) === '{"en_US":"My title"}' && newTitle.create.value === 'Title (Section)' ) );
builder._closeDialog();
builder._openSettings( [ 0, 0, 4 ] );
const madeEditor = textEditors[textEditors.length - 1];
check( 'a component with a format of its own - [text] - makes its key in that format, and the field is the editor of the workbench in the profile of it: blocks by default, with a limit and not none', madeEditor.format === 'blocks' && madeEditor.value === 'Text' && madeEditor.maxlength === builder.CONTENT_MAX
	&& madeEditor.mount.parentNode.className === 'builder-value' && madeEditor.mount.className.indexOf('builder-content') !== -1 && contentPane().querySelector('.builder-value .builder-label').textContent === lineLabel && contentPane().querySelectorAll('input').length === 0 );
const valueGroup = contentPane().querySelector('.builder-value');
check( '...an editor is no input that a label wraps, so the field is a group that its label names and its hint describes, as the Text panel names its editors', valueGroup.getAttribute('role') === 'group' && valueGroup.getAttribute('aria-labelledby') === valueGroup.querySelector('.builder-label').id
	&& valueGroup.getAttribute('aria-describedby') === valueGroup.querySelector('.nino-admin-hint').id && valueGroup.querySelector('.builder-label').id !== '' && valueGroup.querySelector('.nino-admin-hint').id !== '' && valueGroup.querySelector('.builder-label').id !== valueGroup.querySelector('.nino-admin-hint').id );
madeEditor.value = '<p>Hi</p>';
madeEditor.mount.dispatch('input');
check( '...what is typed in it is kept as the editor has it, and the preview says its first line', JSON.stringify( newText.values ) === '{"en_US":"<p>Hi</p>"}' && lines()[4] === 'Hi' );
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
check( '...and the key the source makes is a key to fill: a new one has the field of a key the save makes', valueAt( contentPane(), lineLabel ) === 'Subtitle (Section)' && builder._doc.model.blocks[0].cols[0].components[1].create.value === 'Subtitle (Section)' );
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
builder._doc.model.blocks[0].cols[0].components.push( { name : 'title', source : '/template/page-home/hero/new', text : null, attributes : { tag : 'h3', style : '', 'class' : '' }, create : { value : 'Made when saved' } } );
builder._renderPreview();
check( '...and so is the text a key is going to be made with', lines()[3] === 'Made when saved' );
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
const altLabel = 'Alternative text (English (US))';
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
	&& thumbOf( contentPane() ).hidden === true && contentPane().querySelector('.builder-picture-controls .nino-admin-hint').textContent === 'At most 8 MB and 20 megapixels.'
	&& contentPane().querySelector('.builder-picture-controls small').textContent === 'The first picture makes the slot and is stored at once.' && noteOf( contentPane() ).hidden === true );
check( '...the alt text is the one of the language the editor works in; a slot that is not there yet cannot take it, so it waits in the mark that makes the slot and is said so', valueAt( contentPane(), altLabel ) === ''
	&& controlOf( contentPane(), altLabel ).el.disabled === false && controlOf( contentPane(), altLabel ).el.closest('label').querySelector('.nino-admin-hint').textContent === 'Stored with the slot as soon as it is made.' );
change( contentPane(), altLabel, 'A house' );
check( '...what is typed goes into the mark of the slot, under the language, as a change - and nothing is sent', JSON.stringify( image().create ) === '{"label":"Image","width":1600,"height":900,"alt":{"en_US":"A house"}}' && changes === 1 && requests.length === 0 );
builder._closeDialog();
builder._setLocale('de_DE');
builder._openSettings( imageAt );
check( '...in another language it is another text; typed, it joins the first - and emptied, it is gone again', valueAt( contentPane(), 'Alternative text (Deutsch)' ) === '' && ( change( contentPane(), 'Alternative text (Deutsch)', 'Ein Haus' ), JSON.stringify( image().create.alt ) === '{"en_US":"A house","de_DE":"Ein Haus"}' )
	&& ( change( contentPane(), 'Alternative text (Deutsch)', '' ), JSON.stringify( image().create.alt ) === '{"en_US":"A house"}' ) && ( change( contentPane(), 'Alternative text (Deutsch)', 'Ein Haus' ), true ) );
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
	&& controlOf( contentPane(), altLabel ).el.closest('label').querySelector('.nino-admin-hint').textContent === 'Stored at once, with the slot.' && preview().querySelector('[data-path="0.0.3"] .builder-ph-picture').src.indexOf( '/uploads/image.jpg' ) === 0
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
	&& ( builder._closeDialog(), builder._setLocale('de_DE'), builder._openSettings( [ 0, 0, 3 ] ), valueAt( contentPane(), 'Alternative text (Deutsch)' ) === 'Ein Held' ) );
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
buttonOf( stripOf(), 'Background' ).click();
check( 'the picture behind a section has the same under its source: the upload and the alt text - and where the section has none the alt text has no slot to wait in, and says that a picture comes first', fileField( paneOf('background') ) !== null && thumbOf( paneOf('background') ).hidden === true
	&& controlOf( paneOf('background'), altLabel ).el.disabled === true && controlOf( paneOf('background'), altLabel ).el.closest('label').querySelector('.nino-admin-hint').textContent === 'Upload a picture first, or choose a slot with the Source button.' );
requests = [];
changes = 0;
choose( paneOf('background'), house );
check( '...a picture chosen for a section that has none makes a slot of the kind of a new one - the name background, free in the section, the size it starts with, and for its label the name of the section and the word for a background - and the background is that slot, without a mark', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/background","label":"services: Background","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the section has the picture behind it, with no focus until it is chosen and nothing to make', JSON.stringify( builder._doc.model.blocks[1].background ) === '{"slot":"/template/page-home/services/background","focus":null}' && changes === 1 && actionsOf() === 'slots/create,images/upload' );
requests[1].callback( 200, { filename : 'f', url : '/uploads/services.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the tab is drawn again with it: the thumbnail, the focus and the button that takes the picture away are on, the alt text can be written - and the preview says that a picture is behind the section', thumbOf( paneOf('background') ).hidden === false && thumbOf( paneOf('background') ).src.indexOf( '/uploads/services.jpg' ) === 0
	&& controlOf( paneOf('background'), 'No focus' ).el.disabled === false && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === false && controlOf( paneOf('background'), altLabel ).el.disabled === false && noteOf( paneOf('background') ).textContent === 'The picture is stored.'
	&& preview().children[1].querySelector('.is-background') !== null && shownTab( stripOf(), 'builder' ) === 'background' );
builder._closeDialog();
builder._doc.model.blocks[1].id = '2col';
builder._openSettings( [ 1 ] );
buttonOf( stripOf(), 'Background' ).click();
builder._doc.model.blocks[1].background = null;
builder._refreshTab('background');
requests = [];
choose( paneOf('background'), house );
check( 'a section whose id starts with a digit cannot have a slot made of its name: it is told so, as the form of a new slot tells it, and nothing is sent', requests.length === 0 && noteOf( paneOf('background') ).textContent === words['/_admin/builder/source/name-slot'] && noteOf( paneOf('background') ).classList.contains('is-error') );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 0 ] );
buttonOf( stripOf(), 'Background' ).click();
check( 'the slot a section has shows its picture and the alt text it holds', thumbOf( paneOf('background') ).src === '/uploads/hero.1600x900.jpg' && valueAt( paneOf('background'), altLabel ) === '' );
builder._doc.model.blocks[0].background = { slot : '/template/page-home/hero/background-2', focus : 5, create : { label : 'Background', width : 800, height : 400, alt : { en_US : 'Waves' } } };
builder._refreshTab('background');
check( '...one that is made when the document is saved holds the alt text in its mark', valueAt( paneOf('background'), altLabel ) === 'Waves' && ( change( paneOf('background'), altLabel, 'More waves' ), builder._doc.model.blocks[0].background.create.alt.en_US === 'More waves' ) && changes > 0 );
requests = [];
choose( paneOf('background'), house );
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
buttonOf( stripOf(), 'Background' ).click();
const columnAt = function( at ) { return builder._doc.model.blocks[1].cols[at] };
check( 'the picture behind a column has what the one behind a section has under its source: the upload and the alt text - which has no slot to wait in while there is no picture, and says that a picture comes first - and not whether the picture is a parallax, which is the section\'s alone', fileField( paneOf('background') ) !== null && thumbOf( paneOf('background') ).hidden === true
	&& controlOf( paneOf('background'), altLabel ).el.disabled === true && controlOf( paneOf('background'), altLabel ).el.closest('label').querySelector('.nino-admin-hint').textContent === 'Upload a picture first, or choose a slot with the Source button.'
	&& paneOf('background').querySelector('.builder-source-current').textContent === 'none' && labelList( paneOf('background') ).indexOf('Parallax') === -1 );
requests = [];
changes = 0;
choose( paneOf('background'), house );
check( '...a picture chosen for a column that has none makes a slot of the kind of a new one - the name column-background, free in the section, the size it starts with, and for its label the name of the section, the number of the column and the word for a background', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background","label":"services: Column 1: background","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the background of the column is that slot, with no focus until it is chosen and nothing to make - the section has none of its own', JSON.stringify( columnAt( 0 ).background ) === '{"slot":"/template/page-home/services/column-background","focus":null}' && builder._doc.model.blocks[1].background === null && changes === 1 && actionsOf() === 'slots/create,images/upload' );
requests[1].callback( 200, { filename : 'f', url : '/uploads/column.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the tab is drawn again with it: the thumbnail, the focus, the button that takes the picture away, the alt text and the dimming are on - and the preview says that a picture is behind the column, not behind the section', thumbOf( paneOf('background') ).hidden === false && thumbOf( paneOf('background') ).src.indexOf( '/uploads/column.jpg' ) === 0
	&& controlOf( paneOf('background'), 'No focus' ).el.disabled === false && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === false && controlOf( paneOf('background'), altLabel ).el.disabled === false && controlOf( paneOf('background'), 'Dim the picture' ).el.disabled === false
	&& noteOf( paneOf('background') ).textContent === 'The picture is stored.' && preview().querySelector('[data-path="1.0"] .is-background') !== null && preview().children[1].querySelector('.builder-frame-head .is-background') === null && shownTab( stripOf(), 'builder' ) === 'background' );
builder._closeDialog();
builder._openSettings( [ 1, 1 ] );
buttonOf( stripOf(), 'Background' ).click();
requests = [];
choose( paneOf('background'), house );
check( 'the picture behind the next column of the section takes the next name, and the number of the column is in its label', JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background-2","label":"services: Column 2: background","width":1600,"height":900}' );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
buttonOf( stripOf(), 'Background' ).click();
columnAt( 0 ).background = { slot : '/template/page-home/services/column-background-2', focus : 5, create : { label : 'services: Column 1: background', width : 800, height : 400, alt : { en_US : 'Waves' } } };
builder._refreshTab('background');
check( 'a picture that is made when the document is saved holds its alt text in its mark, as the one behind a section does', valueAt( paneOf('background'), altLabel ) === 'Waves' && ( change( paneOf('background'), altLabel, 'More waves' ), columnAt( 0 ).background.create.alt.en_US === 'More waves' ) );
requests = [];
choose( paneOf('background'), house );
requests[0].callback( 200, { ok : true } );
check( '...it is made with the label and the size of the mark, the focus stays, and the alt text goes to the slot once the picture is stored', JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home/services/column-background-2","label":"services: Column 1: background","width":800,"height":400}' && columnAt( 0 ).background.create === undefined && columnAt( 0 ).background.focus === 5 );
requests[1].callback( 200, { filename : 'f', url : '/uploads/c2.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...(the alt text is written for the slot of the column)', actionsOf() === 'slots/create,images/upload,images/alt' && JSON.stringify( requests[2].payload ) === '{"uri":"/template/page-home/services/column-background-2","alt":{"en_US":"More waves"}}' );
builder._closeDialog();

doc = open();
builder._renderEditor();
builder._openSettings( [ 1, 0 ] );
buttonOf( stripOf(), 'Background' ).click();
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
buttonOf( stripOf(), 'Background' ).click();
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
	JSON.stringify( builder._keys ) === '[]' && builder._keysAnswered === true && lines()[0] === 'Title (Section)' && paneOf('content').children.length === 1 );
builder._closeDialog();

doc = open();
heroTitle().values = { en_US : 'Typed' };
registered.discard();
check( 'the shell that drops what is unsaved drops the texts with it: they are part of the document and of nothing else', heroTitle().values === undefined && registered.isDirty() === false );

check( 'a copy of a component has the texts of the original: the key of the copy is new, made with the text the original has, and the texts typed for the original are typed for the copy', ( function() {
	const model = page();
	model.blocks[0].cols[0].components[0].values = { de_DE : 'Hallo' };
	builder._duplicate( model, registry(), 'page-home', [ 0, 0, 0 ], function() { return 'Welcome' } );
	const copy = model.blocks[0].cols[0].components[1];
	return copy.source === '/template/page-home/hero/title-2' && copy.create.value === 'Welcome' && JSON.stringify( copy.values ) === '{"de_DE":"Hallo"}' && copy.values !== model.blocks[0].cols[0].components[0].values;
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
		&& child.source === '/template/page-home-card/story/card-title' && child.create.value === 'Title (Card)' && Object.prototype.hasOwnProperty.call( child, 'components' ) === false
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
	holder.slots = { background : { label : 'Card - Picture', width : 800, height : 400 } };

	const to = builder._duplicate( model, registry(), 'page-home-card', cardAt, function( source ) { return 'text of '+ source.split('/').pop() } );
	const copy = builder._get( model, to );

	return JSON.stringify( to ) === '[0,1,1]' && model.blocks[0].cols[1].components.length === 2 && copy !== holder && copy.components !== holder.components && copy.components.length === 3
		&& copy.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story/title-2,/template/page-home-card/story/text-2,/template/page-home-card/story/button-2'
		&& JSON.stringify( copy.components.map( function( child ) { return child.create } ) ) === '[{"value":"text of title"},{"value":"text of text"},{"value":"text of button"}]'
		&& copy.attributes.background === '/template/page-home-card/story/card-picture-2' && JSON.stringify( copy.slots ) === '{"background":{"label":"Card - Picture","width":1600,"height":900}}'
		&& holder.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story/title,/template/page-home-card/story/text,/template/page-home-card/story/button' && holder.components[0].create === undefined
		&& holder.attributes.background === '/template/page-home-card/story/card-picture' && holder.slots.background.width === 800;
} )() );
check( '...a copy of a child stands right after it in its container, with a key of its own', ( function() {

	const model = cardPage();
	const to = builder._duplicate( model, registry(), 'page-home-card', [ 0, 1, 0, 0 ], function() { return 'Hello' } );

	return JSON.stringify( to ) === '[0,1,0,1]' && cardIn( model ).components.length === 4 && builder._get( model, to ).name === 'card-title' && builder._get( model, to ).source === '/template/page-home-card/story/title-2'
		&& builder._get( model, to ).create.value === 'Hello' && builder._get( model, [ 0, 1, 0, 0 ] ).source === '/template/page-home-card/story/title';
} )() );
check( '...a copy of a section takes everything it owns under its new id: the keys of the children and the picture behind a card', ( function() {

	const model = cardPage();

	cardIn( model ).attributes.background = '/template/page-home-card/story/card-picture';
	builder._duplicate( model, registry(), 'page-home-card', [ 0 ], function() { return 'Hello' } );

	const copy = model.blocks[1];
	const holder = builder._get( model, [ 1, 1, 0 ] );

	return copy.id === 'story-2' && holder.components.map( function( child ) { return child.source } ).join() === '/template/page-home-card/story-2/title,/template/page-home-card/story-2/text,/template/page-home-card/story-2/button'
		&& holder.attributes.background === '/template/page-home-card/story-2/card-picture' && JSON.stringify( holder.slots ) === '{"background":{"label":"Card - Picture","width":1600,"height":900}}' && cardIn( model ).attributes.background === '/template/page-home-card/story/card-picture'
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

const menuOf = function( path ) { builder._pickComponent( path, made('button') ); return texts( screen.getElementById('builder-menu').children ) };
const inCard = 'Title (Card),Subtitle (Card),Text,Button,Image,HTML,Spacer';
const inSection = 'Title (Section),Subtitle (Section),Text,Card,Button,Image,HTML,Spacer';
const inHero = 'Title (Hero),Subtitle (Hero),Text,Card,Button,Image,HTML,Spacer';

check( 'the picker offers the card where the card says it belongs - a column of a section, and the bare cell of a loop, which holds no place of its own - and what a column is offered besides', menuOf( [ 1, 0 ] ) === inSection );
check( '...in the first section it is offered as well, whether it is a cover or none: the hero is a place the card belongs in, and a cover does not make it one', ( function() {
	const without = [ menuOf( [ 0, 0 ] ), menuOf( [ 0, 1 ] ) ];
	builder._doc.model.blocks[0].settings.cover = 90;
	const offered = menuOf( [ 0, 0 ] );
	builder._doc.model.blocks[0].settings.cover = null;
	return without.join() === [ inHero, inHero ].join() && offered === inHero;
} )() );
check( '...inside a card it offers what belongs to the place the card holds - the title and the subtitle of a card - and not the card itself, and not the title of a section nor the title of a hero, whose places are others', menuOf( cardAt ) === inCard && menuOf( [ 1, 0, 0 ] ) === inCard
	&& menuOf( cardAt ).split(',').indexOf('Card') === -1 && menuOf( [ 0, 1 ] ).split(',').indexOf('Title (Card)') === -1 && menuOf( [ 1, 0 ] ).split(',').indexOf('Title (Card)') === -1 );
check( '...the button of the card in the preview opens the same menu, and the button of its column the column\'s', ( function() {

	const menu = screen.getElementById('builder-menu');

	preview().querySelector('[data-path="0.1.0"] .builder-adds .builder-add-button').click();

	const own = texts( menu.children );

	const adds = preview().querySelector('[data-path="0.1"]').querySelectorAll('.builder-adds');

	adds[adds.length - 1].querySelector('.builder-add-button').click();

	return adds.length === 2 && own === inCard && texts( menu.children ) === inHero;
} )() );

check( '...a component a loop does not take is offered in no column that runs one, and in no card inside it - at depth three and at depth four alike - and everywhere else; a container that says it belongs in a card is offered in none', ( function() {

	const components = builder._registry.components;

	components['stub-alone'] = { label : 'Stub alone', preview : 'block', loop : false };
	components['stub-holder'] = { label : 'Stub holder', source : 'content', preview : 'block', in : [ 'hero', 'section', 'card' ], holds : 'card' };

	const stub = function( path ) {
		builder._pickComponent( path, made('button') );
		return screen.getElementById('builder-menu').children.filter( function( item ) { return /^Stub/.test( item.textContent ) } ).map( function( item ) { return item.textContent } ).join();
	};
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

	return card.components.length === 4 && added.name === 'card-subtitle' && added.source === '/template/page-home-card/story/card-subtitle' && added.create.value === 'Subtitle (Card)'
		&& looped.name === 'card-subtitle' && looped.source === '' && looped.create === undefined && builder._same( builder._sel, [ 1, 0, 0, 1 ] ) && preview().querySelector('[data-path="0.1.0"]').querySelectorAll('.builder-ph').length === 4
		&& preview().querySelector('[data-path="1.0.0.1"]').classList.contains('is-selected') && builder._doc.model.blocks[1].cols[0].components.length === 1;
} )() );
check( '...and the document that came of it is unsaved, with the children where the Reader puts them and the marks the panel keeps', builder._unsaved( builder._doc ) === true && builder._saveRequest( builder._doc, false ).model.blocks[0].cols[1].components[0].components.length === 4
	&& builder._saveRequest( builder._doc, false ).model.blocks[0].cols[1].components[0].components[3].create.value === 'Subtitle (Card)' );

console.log( '\nThe icon of a component in the picker' );

doc = openCard();
builder._renderEditor();

// The items of the picker of a column, or of a component that holds components, as the menu draws them
const pickedItems = function( path ) { builder._pickComponent( path, made('button') ); return screen.getElementById('builder-menu').children };
// What the parts of an icon say, and what an svg is made of: the tag of each part and its attributes, by name in the alphabet's order
const partsSaid = function( parts ) { return JSON.stringify( parts.map( function( part ) { return [ part[0], Object.keys( part[1] ).sort().map( function( name ) { return [ name, part[1][name] ] } ) ] } ) ) };
const partsBuilt = function( svg ) { return JSON.stringify( svg.children.map( function( part ) { return [ part.localName, Object.keys( part.attrs ).sort().map( function( name ) { return [ name, part.attrs[name] ] } ) ] } ) ) };

check( 'every item of the picker holds an icon first and its label after it, the label in a span of its own - and the text of the item is the label alone, so that the lists of the picker are what they were and an item is found by its text', ( function() {

	const menu = screen.getElementById('builder-menu');
	const items = pickedItems( [ 1, 0 ] );

	return items.length === 8 && items.every( function( item ) {
		return item.localName === 'button' && item.children.length === 2 && item.children[0].localName === 'svg' && item.children[1].localName === 'span' && item.children[1].className === 'builder-menu-label' && item.textContent === item.children[1].textContent;
	} ) && texts( items ) === inSection && buttonOf( menu, 'Button' ) === items[4] && buttonOf( menu, 'Button' ).children[1].textContent === 'Button';
} )() );
check( '...the icon of a component of the kernel is the one the registry gives it: the svg holds the parts of its entry, tag by tag and attribute by attribute - all twelve of them, in the pickers of the hero, of a section and of a card', ( function() {

	const components = builder._registry.components;
	const names = Object.keys( components );
	const seen = {};
	const same = [ [ 0, 0 ], [ 1, 0 ], cardAt ].every( function( path ) {
		return pickedItems( path ).every( function( item ) {

			const name = names.find( function( candidate ) { return components[candidate].label === item.textContent } );

			seen[name] = true;

			return name !== undefined && Array.isArray( components[name].icon ) === true && partsBuilt( item.children[0] ) === partsSaid( components[name].icon );
		} );
	} );

	return same && names.length === 12 && Object.keys( seen ).length === 12 && names.every( function( name ) { return components[name].icon.length > 0 } );
} )() );
check( '...it is an svg of its own and decoration: made in the namespace of svg, as every part of it is - an element of the page is not -, 24 by 24, out of reach of assistive technology, with the class of the icons and the menu\'s, and it draws with its parts, not with the sprite', ( function() {

	const svg = pickedItems( [ 1, 0 ] )[0].children[0];

	return svg.namespaceURI === SVG_NS && svg.children.length === 3 && svg.all().every( function( el ) { return el.namespaceURI === SVG_NS } ) && made('button').namespaceURI === HTML_NS && screen.getElementById('builder-menu').namespaceURI === HTML_NS
		&& svg.getAttribute('viewBox') === '0 0 24 24' && svg.getAttribute('aria-hidden') === 'true' && svg.getAttribute('focusable') === 'false' && svg.className === 'builder-icon builder-menu-icon' && svg.querySelector('use') === null;
} )() );

( function() {

	const components = builder._registry.components;
	const every = [
		[ 'path', { d : 'M4 4h16' } ], [ 'circle', { cx : '12', cy : '12', r : '3' } ], [ 'ellipse', { cx : '12', cy : '12', rx : '8', ry : '4' } ], [ 'rect', { x : '3', y : '3', width : '18', height : '18' } ],
		[ 'rect', { x : '5', y : '5', width : '14', height : '14', rx : '2', ry : '2' } ], [ 'line', { x1 : '4', y1 : '4', x2 : '20', y2 : '20' } ], [ 'polyline', { points : '4 4 12 12 20 4' } ], [ 'polygon', { points : '12 3 21 21 3 21' } ],
	];
	const second = [ [ 'path', { d : 'M2 12h20' } ], [ 'path', { d : 'M12 2v20' } ] ];

	// Two components of a feature with an icon of their own, and four that name none - or name something that is no list of parts
	components['stub-own'] = { label : 'Stub own', preview : 'block', icon : every };
	components['stub-second'] = { label : 'Stub second', preview : 'block', icon : second };
	components['stub-none'] = { label : 'Stub none', preview : 'block' };
	components['stub-text'] = { label : 'Stub text', preview : 'block', icon : 'heading' };
	components['stub-empty'] = { label : 'Stub empty', preview : 'block', icon : [] };
	components['stub-map'] = { label : 'Stub map', preview : 'block', icon : { d : 'M0 0' } };

	const menu = screen.getElementById('builder-menu');
	const items = pickedItems( [ 1, 0 ] );
	const stub = {};

	items.filter( function( item ) { return /^Stub/.test( item.textContent ) } ).forEach( function( item ) { stub[item.textContent] = item } );

	check( '...a component of a feature is offered with its own icon, built the same whichever of the seven tags its parts name - a rect with its radii and one without -, and after the kernel\'s as it was', texts( items ) === inSection+ ',Stub own,Stub second,Stub none,Stub text,Stub empty,Stub map'
		&& partsBuilt( stub['Stub own'].children[0] ) === partsSaid( every ) && stub['Stub own'].children[0].children.map( function( part ) { return part.localName } ).join() === 'path,circle,ellipse,rect,rect,line,polyline,polygon'
		&& partsBuilt( stub['Stub second'].children[0] ) === partsSaid( second ) && stub['Stub own'].children[0].className === 'builder-icon builder-menu-icon' && stub['Stub own'].children[0].namespaceURI === SVG_NS );

	const puzzle = screen.getElementById('builder-icon-puzzle');
	const worn = [ 'Stub none', 'Stub text', 'Stub empty', 'Stub map' ].map( function( label ) { return stub[label] } );

	check( '...a component that names no icon - or names something that is no list of parts - wears the puzzle of the sprite: the svg of the icons with a use of the symbol, the class of the others and nothing built into it', worn.every( function( item ) {
		const svg = item.children[0];
		return svg.localName === 'svg' && svg.namespaceURI === SVG_NS && svg.children.length === 1 && svg.children[0].localName === 'use' && svg.children[0].getAttribute('href') === '#builder-icon-puzzle' && iconOf( item ) === 'puzzle'
			&& svg.className === 'builder-icon builder-menu-icon' && svg.getAttribute('aria-hidden') === 'true';
	} ) && stub['Stub own'].querySelector('use') === null && stub['Stub second'].querySelector('use') === null );
	check( '...the puzzle is a symbol of the sprite, a path of 24 by 24, and the picker passes the inspection every form passes: each icon in it is a symbol of the sprite, and a control that is an icon has a name', puzzle !== null && puzzle.localName === 'symbol' && puzzle.getAttribute('viewBox') === '0 0 24 24'
		&& puzzle.children.length === 1 && puzzle.children[0].localName === 'path' && iconsKnown( menu ) === true && unnamed( menu ).length === 0 );

	components['stub-odd'] = { label : 'Stub odd', preview : 'block', icon : [
		[ 'script', { src : 'x.js' } ], [ 'path', { d : 'M0 0h24', onclick : 'x()', fill : 'red', style : 'x' } ], [ 'constructor', { d : 'M1 1' } ], [ 'circle', 'cx=1' ], [ 'rect', null ], 'path', null, [ 'line' ],
		[ 'circle', { r : '2', cx : '12', cy : '12', d : 'M2 2' } ], [ 'svg', {} ], [ [ 'path' ], { d : 'M3 3' } ],
	] };

	const odd = pickedItems( [ 1, 0 ] ).filter( function( item ) { return item.textContent === 'Stub odd' } )[0].children[0];

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
check( '...the stylesheet lays an item out as a row - the icon, a gap of .5rem, the label, in the middle of the line - and keeps the icon a square of a rem that does not shrink; the menu keeps its width and its place', ( function() {

	const lines = function( selector ) { return ruleOf( selector ).split('\n') };

	return [ '\tdisplay: flex;', '\talign-items: center;', '\tgap: .5rem;' ].every( function( line ) { return lines('.builder-menu-item').indexOf( line ) !== -1 } )
		&& [ '\tflex: none;', '\twidth: 1rem;', '\theight: 1rem;' ].every( function( line ) { return lines('.builder-menu-icon').indexOf( line ) !== -1 } )
		&& [ '\tposition: absolute;', '\tz-index: 6;', '\tdisplay: grid;', '\tmin-width: 11rem;' ].every( function( line ) { return lines('.builder-menu').indexOf( line ) !== -1 } );
} )() );

console.log( '\nThe form of a card' );

openCard();
builder._renderEditor();
builder._openSettings( cardAt );

const holderPane = function() { return paneOf('properties') };
const holderNow = function() { return cardIn( builder._doc.model ) };
const optionsOf = function( pane, label ) { return controlOf( pane, label ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() };

check( 'the form of a card has the label of the component for its title and two tabs: Properties and CSS classes - the attributes the schema declares, and the classes of its own; the form of a column or a plain component is not it, and the card has no tab for a content', dialogText('builder-dialog-kind') === 'Card'
	&& JSON.stringify( dialogTabs() ) === '[["properties","Properties"],["custom","CSS classes"]]' && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'properties' && screen.getElementById('builder-dialog-name').hidden === true && paneOf('content') === null );
check( '...Properties: a field for each attribute in the order of the schema - the ground, the padding, the full height, the vertical and the horizontal alignment, the picture with its focus and its dimming, the border - by the type the schema gives', JSON.stringify( labelList( holderPane() ) )
	=== JSON.stringify( [ 'Ground', 'Padding', 'Full height', 'Vertical', 'Horizontal', 'Upload a picture', 'No focus', 'Focus', 'Dim the picture', 'Without border' ] ) && controlOf( holderPane(), 'Full height' ).kind === 'switch' && controlOf( holderPane(), 'Dim the picture' ).kind === 'switch'
	&& controlOf( holderPane(), 'Without border' ).kind === 'switch' && controlOf( holderPane(), 'Focus' ).kind === 'group' );
check( '...two to a line where both are short, a line of its own for the picture and for the focus: the ground and the padding, the full height and the vertical alignment, the horizontal alignment alone, then the picture, the focus, and the dimming with the border', classes( holderPane().children )
	=== 'builder-line,builder-line,nino-admin-field,builder-background,builder-focus,builder-line' && holderPane().children.map( function( el ) { return labelList( el ).join('|') } ).join('~') === 'Ground|Padding~Full height|Vertical~Horizontal~Upload a picture~No focus|Focus~Dim the picture|Without border' );
check( '...the ground is the colour select of a column - none of its own, which is the surroundings, counter and the six - with the patch of the colour it ends up in beside it, in the words of the grounds of a column', optionsOf( holderPane(), 'Ground' )
	=== '=Like the surroundings,counter=Counter,alt=Alternate,tint=Tinted,dark=Dark,black=Black,primary=Brand colour,brand-alt=Second colour' && holderPane().querySelector('.builder-with-swatch').contains( controlOf( holderPane(), 'Ground' ).el ) && holderPane().querySelector('.builder-with-swatch').contains( holderPane().querySelector('.builder-swatch') )
	&& holderPane().querySelector('.builder-swatch').getAttribute('aria-hidden') === 'true' && valueAt( holderPane(), 'Ground' ) === 'counter' );
check( '...the hint of the ground stands under the select and the patch, not between them: the patch is level with the select', holderPane().querySelector('.builder-with-swatch').parentNode.className === 'builder-with-hint' && holderPane().querySelector('.builder-with-swatch').nextSibling.className === 'nino-admin-hint'
	&& holderPane().querySelector('.builder-with-swatch').nextSibling.textContent === registry().components.card.attributes.ground.hint && holderPane().querySelector('.builder-with-swatch').querySelector('.nino-admin-hint') === null && stylesheet.indexOf( '.builder-with-hint > .nino-admin-hint' ) !== -1 );
check( '...the padding has the steps of the section\'s spacing, the vertical alignment its own words - top, middle, bottom, last element at the bottom - and the horizontal alignment the words of the alignment of a text; each attribute has the words of the Builder, and the values the file has', optionsOf( holderPane(), 'Padding' ) === '=Default,0=0,1=1,2=2,3=3,4=4,5=5,6=6'
	&& optionsOf( holderPane(), 'Vertical' ) === '=Top,middle=Middle,bottom=Bottom,last-bottom=Last element at the bottom' && optionsOf( holderPane(), 'Horizontal' ) === '=Inherited,left=Left,center=Centre,right=Right' && valueAt( holderPane(), 'Padding' ) === '3' && valueAt( holderPane(), 'Vertical' ) === '' );

const swatchOf = function() { return holderPane().querySelector('.builder-swatch').dataset.color };
check( '...the patch is the ground the card ends up with: the ground of its column - or of its section where the column has none - for none of its own, the counter of that, by the pairs of Nino.css, for counter, and the ground itself for the rest', ( function() {

	const pairs = { '' : 'alt', alt : 'plain', tint : 'plain', dark : 'black', black : 'dark', primary : 'brand-alt', 'brand-alt' : 'primary' };

	return Object.keys( pairs ).every( function( around ) {

		builder._closeDialog();
		builder._doc.model.blocks[0].settings.color = around;
		builder._openSettings( cardAt );

		const counter = swatchOf();

		change( holderPane(), 'Ground', '' );

		const none = swatchOf();

		change( holderPane(), 'Ground', 'tint' );

		const own = swatchOf();

		change( holderPane(), 'Ground', 'counter' );

		return counter === pairs[around] && none === ( around === '' ? 'plain' : around ) && own === 'tint' && holderNow().attributes.ground === 'counter';
	} );
} )() );
check( '...a column that paints a ground of its own is what the card stands on - counter is the counter of that, none is that -, and a column whose picture is dimmed is dark, whatever it paints', ( function() {

	builder._closeDialog();
	builder._doc.model.blocks[0].settings.color = 'primary';
	builder._doc.model.blocks[0].cols[1].color = 'alt';
	builder._openSettings( cardAt );

	const own = swatchOf();

	change( holderPane(), 'Ground', '' );

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

check( '...what the fields write is the attributes of the card, as the call writes them - a switch is 1 or 0 -, into the card and into nothing else', ( function() {

	const column = JSON.stringify( builder._doc.model.blocks[0].cols[1].components.slice( 1 ) )+ JSON.stringify( builder._doc.model.blocks[0].cols[0] );

	change( holderPane(), 'Ground', 'dark' );
	change( holderPane(), 'Padding', '5' );
	change( holderPane(), 'Full height', true );
	change( holderPane(), 'Vertical', 'last-bottom' );
	change( holderPane(), 'Horizontal', 'center' );
	change( holderPane(), 'Without border', true );

	return JSON.stringify( holderNow().attributes ) === '{"ground":"dark","padding":"5","fill":"1","vertical":"last-bottom","align":"center","background":"","focus":"","dim":"0","borderless":"1","class":""}'
		&& column === JSON.stringify( builder._doc.model.blocks[0].cols[1].components.slice( 1 ) )+ JSON.stringify( builder._doc.model.blocks[0].cols[0] ) && holderNow().components.length === 3 && Object.prototype.hasOwnProperty.call( holderNow(), 'content' ) === false
		&& valueAt( holderPane(), 'Vertical' ) === 'last-bottom' && valueAt( holderPane(), 'Full height' ) === true;
} )() );
change( holderPane(), 'Full height', false );
change( holderPane(), 'Without border', false );
change( holderPane(), 'Vertical', '' );
change( holderPane(), 'Horizontal', '' );
check( '...switched off, a switch is 0 again, and an empty choice is the empty word', holderNow().attributes.fill === '0' && holderNow().attributes.borderless === '0' && holderNow().attributes.vertical === '' && holderNow().attributes.align === '' );

check( 'the focus of the picture is the grid of the one behind a section - a switch for none and nine places - and waits for a picture: while the card has none the switch and the grid are off, and so is the dimming', ( function() {

	const grid = controlOf( holderPane(), 'Focus' ).el;

	return grid.getAttribute('role') === 'radiogroup' && radiosOf( grid ).length === 9 && radiosOf( grid ).every( function( radio ) { return radio.disabled === true && radio.checked === false } ) && grid.classList.contains('is-disabled') === true
		&& controlOf( holderPane(), 'No focus' ).el.disabled === true && valueAt( holderPane(), 'No focus' ) === true && controlOf( holderPane(), 'Dim the picture' ).el.disabled === true && valueAt( holderPane(), 'Dim the picture' ) === false;
} )() );

holderNow().attributes.background = '/template/page-home-card/story/card-picture';
holderNow().attributes.focus = '';
builder._refreshTab('properties');
check( '...with a picture the switch for none is on, as the focus is none, and turning it off starts in the middle, as the picture of a section does; the grid chooses a place, written as its number - and none is empty', ( function() {

	const none = controlOf( holderPane(), 'No focus' ).el;
	const before = [ none.disabled, valueAt( holderPane(), 'No focus' ), holderNow().attributes.focus ].join();

	change( holderPane(), 'No focus', false );

	const middle = holderNow().attributes.focus;
	const checkedAfter = valueAt( holderPane(), 'Focus' );

	change( holderPane(), 'Focus', '9' );

	const corner = holderNow().attributes.focus;

	change( holderPane(), 'Focus', '1' );

	const other = holderNow().attributes.focus;

	change( holderPane(), 'No focus', true );

	return before === 'false,true,' && middle === '5' && checkedAfter === '5' && corner === '9' && other === '1' && holderNow().attributes.focus === '' && valueAt( holderPane(), 'No focus' ) === true
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
		&& controlOf( holderPane(), 'No focus' ).el.disabled === true && buttonOf( holderPane(), 'Remove the picture' ).disabled === true && holderNow().slots === undefined;
} )() );
check( '...and the focus goes with it: a place chosen for the picture is none again, the attribute is empty, and the switch for none is on and greyed out with no place of the grid chosen', ( function() {

	holderNow().attributes.background = '/template/page-home-card/story/card-picture';
	builder._refreshTab('properties');
	change( holderPane(), 'No focus', false );
	change( holderPane(), 'Focus', '9' );

	const placed = holderNow().attributes.focus;

	buttonOf( holderPane(), 'Remove the picture' ).click();

	return placed === '9' && holderNow().attributes.background === '' && holderNow().attributes.focus === '' && valueAt( holderPane(), 'No focus' ) === true && controlOf( holderPane(), 'No focus' ).el.disabled === true
		&& radiosOf( controlOf( holderPane(), 'Focus' ).el ).every( function( radio ) { return radio.checked === false && radio.disabled === true } );
} )() );

openCard();
builder._renderEditor();
builder._openSettings( [ 0, 0, 0 ] );
check( 'the focus of a component that is a picture itself - [image], whose picture is its source - is the grid as well, and is on from the start: no attribute of the type image comes before it, so there is no picture it would wait for', controlOf( paneOf('properties'), 'Focus' ).kind === 'group'
	&& controlOf( paneOf('properties'), 'No focus' ).el.disabled === false && valueAt( paneOf('properties'), 'No focus' ) === true && radiosOf( controlOf( paneOf('properties'), 'Focus' ).el ).every( function( radio ) { return radio.disabled === true } )
	&& ( change( paneOf('properties'), 'No focus', false ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '5' ) && ( change( paneOf('properties'), 'Focus', '3' ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '3' )
	&& ( change( paneOf('properties'), 'No focus', true ), builder._get( builder._doc.model, [ 0, 0, 0 ] ).attributes.focus === '' ) );
builder._closeDialog();

console.log( '\nThe picture behind a card' );

openCard();
builder._renderEditor();
builder._openSettings( cardAt );

check( 'the picture behind a card is a source row - the label of the attribute over it, what it is now below it, the button of the dialog of the sources beside it - and under it the upload and the way to take it away; it has no alt text: the picture behind a content is decoration', ( function() {

	const row = holderPane().querySelector('.builder-source');

	return row !== null && row.querySelector('.builder-source-name').hidden === false && row.querySelector('.builder-source-name').textContent === 'Picture' && row.querySelector('.builder-source-current').textContent === 'none'
		&& row.querySelector('.builder-source-caption').textContent === 'Current picture:' && iconOf( row.querySelector('button') ) === 'source' && fileField( holderPane() ) !== null && thumbOf( holderPane() ).hidden === true
		&& labelList( holderPane() ).filter( function( label ) { return /^Alternative text/.test( label ) } ).length === 0 && buttonOf( holderPane(), 'Remove the picture' ).disabled === true && holderPane().querySelector('.builder-source-red').hidden === true;
} )() );

requests = [];
changes = 0;
choose( holderPane(), house );
check( '...a picture chosen for a card that has none makes a slot of the kind of a new one - named after the component and the attribute, free in the section, the size a new slot starts with, and for its label the section, the component and the attribute', actionsOf() === 'slots/create'
	&& JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home-card/story/card-background","label":"story: Card - Picture","width":1600,"height":900}' );
requests[0].callback( 200, { ok : true } );
check( '...the picture of the card is that slot, with nothing left to make - the slot is there - and the document is changed', holderNow().attributes.background === '/template/page-home-card/story/card-background' && holderNow().slots === undefined && actionsOf() === 'slots/create,images/upload' && changes === 1 );
requests[1].callback( 200, { filename : 'f', url : '/uploads/card.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
check( '...and the form is drawn again with it: the thumbnail, the focus and the dimming are on, the picture can be taken away, and the preview says that a picture is behind the card, not behind the column or the section', thumbOf( holderPane() ).hidden === false && thumbOf( holderPane() ).src.indexOf('/uploads/card.jpg') === 0
	&& controlOf( holderPane(), 'No focus' ).el.disabled === false && controlOf( holderPane(), 'Dim the picture' ).el.disabled === false && buttonOf( holderPane(), 'Remove the picture' ).disabled === false && noteOf( holderPane() ).textContent === 'The picture is stored.'
	&& preview().querySelector('[data-path="0.1.0"] .is-background') !== null && preview().querySelector('[data-path="0.1.0"] .is-background').title === 'Picture behind the card' && preview().querySelector('[data-path="0.1"] .builder-pcol-head .is-background') === null
	&& preview().children[0].querySelector('.builder-frame-head .is-background') === null && shownTab( stripOf(), 'builder' ) === 'properties' );
builder._closeDialog();

openCard();
builder._renderEditor();
builder._openSettings( cardAt );
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
	&& JSON.stringify( holderNow().slots ) === '{"background":{"label":"Card - Picture","width":800,"height":900}}' && holderNow().create === undefined && pickerBox().open === false && preview().querySelector('[data-path="0.1.0"] .is-background') !== null
	&& holderPane().querySelector('.builder-source-current').textContent === '/template/page-home-card/story/card-background' );
check( '...a slot that is yet to be made has an upload: the first picture makes it from the mark, the size it says, and the mark is gone once it is there', ( function() {

	requests = [];
	choose( holderPane(), house );

	const first = actionsOf() === 'slots/create' && JSON.stringify( requests[0].payload ) === '{"uri":"/template/page-home-card/story/card-background","label":"Card - Picture","width":800,"height":900}';

	requests[0].callback( 200, { ok : true } );

	return first && holderNow().slots === undefined && holderNow().attributes.background === '/template/page-home-card/story/card-background' && actionsOf() === 'slots/create,images/upload';
} )() );
requests[1].callback( 200, { filename : 'f', url : '/uploads/made.jpg', source : { width : 2000, height : 1000 }, belowTarget : false } );
buttonOf( holderPane(), 'Remove the picture' ).click();
check( '...taking the picture away empties the attribute and puts the mark away with it: nothing is left to make, and the preview says no picture is behind the card', holderNow().attributes.background === '' && holderNow().slots === undefined && preview().querySelector('[data-path="0.1.0"] .is-background') === null
	&& holderPane().querySelector('.builder-source-current').textContent === 'none' && buttonOf( holderPane(), 'Remove the picture' ).disabled === true );
builder._closeDialog();

holderNow().attributes.background = '/template/page-home-card/story/card-picture';
holderNow().slots = { background : { label : 'Card - Picture', width : 800, height : 400, alt : { en_US : 'Waves' } } };
builder._openSettings( cardAt );
check( 'the mark of a slot that is made later holds an alt text the card does not show or edit: the picture behind a card has none to give, and the mark is kept as it is', holderNow().slots.background.alt.en_US === 'Waves' && labelList( holderPane() ).filter( function( label ) { return /^Alternative text/.test( label ) } ).length === 0 );
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

const frameOf = function( at ) { return preview().querySelector('[data-path="'+ at+ '"]') };
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
const toolOf = function( at, tool ) { return frameOf( at ).querySelectorAll('button').find( function( button ) { return button.dataset.tool === tool } ) };
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
	&& builder._get( builder._doc.model, [ 0, 1, 0, 3 ] ).create.value === 'Text' && frameOf('0.1.0.3').classList.contains('is-selected') && builder._fresh.story === undefined );
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
check( 'a double click on the card opens the form of the card, one on a child the form of that component', dialogBox().open === true && dialogText('builder-dialog-kind') === 'Card' && JSON.stringify( dialogTabs() ) === '[["properties","Properties"],["custom","CSS classes"]]' );
frameOf('0.1.0.1').dispatch('dblclick');
check( '...the form of a component at depth four is the form every component has: Content, Properties and CSS classes', dialogBox().open === true && dialogText('builder-dialog-kind') === 'Component "Text"' && JSON.stringify( dialogTabs().map( function( tab ) { return tab[0] } ) ) === '["content","properties","custom"]' );
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
cardIn( builder._doc.model ).slots = { background : { label : 'Card - Picture', width : 800, height : 400 } };
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
	oldCard.slots = { background : { label : 'Card - Picture', width : 800, height : 400, alt : { en_US : 'Waves' } } };
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
	old.cols[1].components[0].slots = { background : { label : 'Card - Picture', width : 800, height : 400 } };
	again.cols[1].components[0].attributes.background = '/template/page-home-card/story/other-picture';

	builder._carry( old, again );

	return again.cols[1].components[0].slots === undefined;
} )() );

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
