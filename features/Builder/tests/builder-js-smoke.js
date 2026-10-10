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
			add( ...names ) { const now = this.list(); names.forEach( function( name ) { if( now.indexOf( name ) === -1 ) now.push( name ) } ); self.className = now.join(' ') },
			remove( ...names ) { self.className = this.list().filter( function( name ) { return names.indexOf( name ) === -1 } ).join(' ') },
			contains( name ) { return this.list().indexOf( name ) !== -1 },
			toggle( name, force ) {
				const on = force === undefined ? this.contains( name ) === false : force === true;
				if( on === true ) this.add( name ); else this.remove( name );
				return on;
			},
		};
	}

	get className() { return this.attrs['class'] ?? '' }
	set className( value ) { if( String( value ) === '' ) delete this.attrs['class']; else this.attrs['class'] = String( value ) }

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
		api : { call : function( action, payload, callback ) { requests.push( { action : action, payload : payload, callback : callback } ) }, errorText : function( status, response, key ) { return '('+ status+ ') '+ key } },
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
		slots		: [ { uri : '/template/page-home/hero/background', label : 'Background', width : 1600, height : 900, hasImage : true, url : '/uploads/hero.1600x900.jpg' }, { uri : '/project/logo/header/image', label : 'Logo', width : 300, height : 100, hasImage : false, url : null } ],
		headers	: [ 'html-header', 'html-header-slim' ],
		footers	: [ 'html-footer' ],
		fieldTypes : [ 'string', 'integer', 'double', 'boolean', 'array', 'date', 'datetime', 'image', 'element' ],
	} );
};

const page = function() { return builder._normalise( fixture('page-home.json') ) };
const known = function() { return [ { key : '/template/page-home/hero/title', global : false, values : { en_US : 'Welcome' } }, { key : '/template/page-home/hero/subtitle', global : true, values : { '*' : 'We build' } } ] };

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
	&& JSON.stringify( model.blocks[0].cols[0].hidden ) === '{}' && model.blocks[0].cols[0].components[0].attributes.level === '1' );
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
			&& copy.cols[0].components[1].source === '/template/page-home/hero-2/subtitle' && copy.cols[0].components[1].create.value === 'Subtitle'
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
	copy.components[0].attributes.level = '3';
	return copy.components[0].source === '/template/page-home/hero/title-2' && copy.components[1].source === '/template/page-home/hero/subtitle-2' && model.blocks[0].cols[0].components[0].attributes.level === '1' && model.blocks[0].cols.length === 2;
} )() );
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
	return title.source === '/template/page-home/hero/title-2' && title.create.value === 'Title' && title.attributes.level === '2' && title.text === null
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
check( 'a component is a placeholder with the image the registry names - the kernel\'s five, and block for the rest', plan('l')[0].cols[0].components.map( function( component ) { return component.image+ ':'+ component.label } ).join() === 'title:Title,title:Subtitle,button:Button' );
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
		&& col.querySelector('.builder-head-status').contains( item ) && classes( col.querySelector('.builder-col-body').children ) === 'builder-ph builder-pick,builder-ph builder-pick,builder-ph builder-pick,builder-ph builder-pick,builder-adds'
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
check( '...and a component is chosen from the registry\'s, those a loop takes where the column has one', ( function() {
	const menu = screen.getElementById('builder-menu');
	builder._pickComponent( [ 0, 0 ], made('button') );
	const all = menu.children.length;
	builder._pickComponent( [ 1, 1 ], made('button') );
	const registryNow = builder._registry.components;
	return all === Object.keys( registryNow ).length && menu.children.length === Object.keys( registryNow ).filter( function( name ) { return registryNow[name].loop !== false } ).length;
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
	return asked && builder._doc.model.blocks[4].id === 'questions' && builder._doc.model.blocks[4].renamedFrom === undefined && component.source === '/template/page-home/questions/title' && component.create.value === 'Title' && builder._fresh.questions === true;
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
check( 'Layout: the width of the row, the two switches, the two alignments as icons, the spacing as a table', JSON.stringify( labelList( layout ) ) === JSON.stringify( [ 'Row width', 'Full width', 'Full height', 'Vertical alignment', 'Horizontal alignment', 'Above, Margin', 'Above, Padding', 'Below, Margin', 'Below, Padding' ] )
	&& JSON.stringify( controlsIn( layout ).map( function( control ) { return control.kind } ) ) === JSON.stringify( [ 'select', 'switch', 'switch', 'group', 'group', 'select', 'select', 'select', 'select' ] ) );
check( '...the width of the row is a select of the three it knows', controlOf( layout, 'Row width' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Normal,narrow=Narrow,wide=Wide' && valueAt( layout, 'Row width' ) === 'wide' );
check( '...full width and full height are two switches in one line, both on at once if you like', layout.querySelectorAll('.builder-line')[0].children.length === 2 && layout.querySelectorAll('.builder-line')[0].querySelectorAll('.nino-admin-switch').length === 2
	&& valueAt( layout, 'Full width' ) === true && valueAt( layout, 'Full height' ) === false && ( change( layout, 'Full height', true ), settingsOf().fullheight === true && settingsOf().fullwidth === true && valueAt( layout, 'Full width' ) === true )
	&& ( change( layout, 'Full width', false ), settingsOf().fullwidth === false && settingsOf().fullheight === true ) );
check( '...the alignments are two lines of icons in one line: radio buttons, each with its word as its title and its label - the vertical one Top, Middle and Bottom, the horizontal one Inherited as a word and Left, Centre and Right', layout.querySelectorAll('.builder-line')[1].children.length === 2 && ( function() {
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

const ground = paneOf('background');
check( 'Background: the colour with the border beside it, the picture with its dimming, where the picture is, its position and the height of the cover, the focus - and the lines hold two fields each', JSON.stringify( labelList( ground ) ) === JSON.stringify( [ 'Colour', 'Border', 'Picture', 'Dim the picture', 'Position of the picture', 'Height of the cover', 'No focus', 'Focus' ] )
	&& ground.querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2,2,2' && controlOf( ground, 'Picture' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=None,cover=Cover,parallax=Parallax' );
check( '...the colour is a select with a patch beside it that follows the choice - and the patch is a patch of the page, not of the picture', ( function() {
	const swatch = ground.querySelector('.builder-swatch');
	const first = swatch.dataset.color;
	change( ground, 'Colour', 'dark' );
	const second = swatch.dataset.color;
	change( ground, 'Colour', '' );
	return first === 'black' && second === 'dark' && swatch.dataset.color === 'plain' && swatch.getAttribute('aria-hidden') === 'true' && swatch.parentNode.contains( controlOf( ground, 'Colour' ).el ) && settingsOf().color === ''
		&& controlOf( ground, 'Colour' ).el.children.map( function( option ) { return option.value } ).join() === ',alt,tint,dark,black,primary,brand-alt' && swatch.style.getPropertyValue('--builder-swatch') === '';
} )() );
check( '...the colours, the border and the places of the picture have words of their own, not the names of the classes of Nino.css - and the empty value of the border and of the position has its word, not the lower case none of the label', controlOf( ground, 'Colour' ).el.children.map( function( option ) { return option.textContent } ).join() === 'Default,Alternate,Tinted,Dark,Black,Brand colour,Second colour'
	&& controlOf( ground, 'Border' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=None,1=Thin,2=Medium,3=Strong,primary=Brand colour'
	&& controlOf( ground, 'Position of the picture' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=Default,top=Top,center=Centre,bottom=Bottom' );
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
buttonOf( paneOf('background'), 'Remove the picture' ).click();
check( 'the picture is taken away with its button: no background, the row says none, the focus and the button are off', builder._doc.model.blocks[0].background === null && paneOf('background').querySelector('.builder-source-current').textContent === 'none' && focusGrid().classList.contains('is-disabled')
	&& controlOf( paneOf('background'), 'No focus' ).el.disabled === true && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === true && preview().children[0].querySelector('.is-background') === null );
builder._openSettings( [ 1 ] );
check( '...so is a section that has none to begin with', controlOf( paneOf('background'), 'No focus' ).el.disabled === true && radiosOf( focusGrid() ).every( function( radio ) { return radio.disabled === true } ) && buttonOf( paneOf('background'), 'Remove the picture' ).disabled === true );
change( paneOf('background'), 'Picture', 'parallax' );
change( paneOf('background'), 'Dim the picture', true );
change( paneOf('background'), 'Position of the picture', 'bottom' );
change( paneOf('background'), 'Height of the cover', 60 );
change( paneOf('background'), 'Border', 'primary' );
check( '...the other fields of the picture write their settings', ( function() {
	const settings = builder._doc.model.blocks[1].settings;
	return settings.image === 'parallax' && settings.dim === true && settings.imagePos === 'bottom' && settings.cover === 60 && settings.border === 'primary';
} )() );
change( paneOf('background'), 'Height of the cover', '' );
check( '...and an emptied height is none', builder._doc.model.blocks[1].settings.cover === null );

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
check( 'the dialog of a column has four tabs: Layout, Loop, Viewport animation and CSS classes - the name of the section is not part of its title, no pencil is', dialogText('builder-dialog-kind') === 'Column of "hero"' && screen.getElementById('builder-dialog-name').hidden === true
	&& JSON.stringify( dialogTabs() ) === '[["layout","Layout"],["loop","Loop"],["animation","Viewport animation"],["custom","CSS classes"]]' && shownTab( screen.getElementById('builder-dialog-tabs'), 'builder' ) === 'layout' && screen.getElementById('builder-dialog-heading').querySelectorAll('.builder-rename').length === 0 );

const widths = function() { return paneOf('layout').querySelector('table') };
check( 'in a viewport the table of the widths has the row of that viewport only - the device with its icon and its word, the width, whether the column is hidden there - and under it a link to all of them', ( function() {
	const rows = widths().querySelectorAll('tbody tr');
	return texts( widths().querySelectorAll('thead th') ) === 'Device,Width,Hidden' && rows.length === 1 && rows[0].querySelector('th').textContent === 'Desktop' && iconOf( rows[0] ) === 'monitor' && rows[0].querySelector('th').getAttribute('scope') === 'row'
		&& valueAt( paneOf('layout'), 'Width, Desktop' ) === '66' && valueAt( paneOf('layout'), 'Hidden, Desktop' ) === false && controlOf( paneOf('layout'), 'Hidden, Desktop' ).kind === 'box'
		&& controlOf( paneOf('layout'), 'Width, Desktop' ).el.children.map( function( option ) { return option.value } ).join() === '25,33,50,66,75,100' && paneOf('layout').querySelectorAll('.builder-link-btn').map( function( link ) { return link.textContent } ).join() === 'All viewports'
		&& widths().nextSibling === paneOf('layout').querySelector('.builder-link-btn');
} )() );
check( '...and the rest of the tab: the horizontal alignment as icons, the alignment of the components as icons with Off as a word, the gap', JSON.stringify( labelList( paneOf('layout') ) ) === JSON.stringify( [ 'Width, Desktop', 'Hidden, Desktop', 'Horizontal alignment', 'Alignment of the components', 'Gap (--space-*)' ] )
	&& valueAt( paneOf('layout'), 'Horizontal alignment' ) === 'left' && controlOf( paneOf('layout'), 'Alignment of the components' ).el.querySelectorAll('.builder-segment-option').map( function( option ) { return option.querySelector('input').value+ ':'+ ( iconOf( option ) ?? option.textContent ) } ).join()
		=== ':Off,start:stack-start,center:stack-center,end:stack-end' && paneOf('layout').querySelectorAll('.builder-line')[0].children.length === 2 && controlOf( paneOf('layout'), 'Gap (--space-*)' ).el.children.map( function( option ) { return option.value } ).join() === ',0,1,2,3,4,5,6' );
change( paneOf('layout'), 'Width, Desktop', '33' );
change( paneOf('layout'), 'Horizontal alignment', 'right' );
change( paneOf('layout'), 'Alignment of the components', 'center' );
change( paneOf('layout'), 'Gap (--space-*)', '3' );
check( '...what the tab writes is the width of the viewport, the alignments and the gap', colOf().width.l === 33 && colOf().text === 'right' && colOf().stackAlign === 'center' && colOf().stackGap === '3' && JSON.stringify( colOf().width ) === '{"s":100,"m":100,"l":33}' );

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
check( 'the form of the loop: the kind, then the groups - Data (the type, a note, the query), Order (the field and the direction, the limit and the offset), Grid (the cells of each viewport, the gap and the equal height, the id)', titlesIn( loopPane() ) === 'Data,Order,Grid'
	&& JSON.stringify( labelList( loopPane() ) ) === JSON.stringify( [ 'Loop', 'Type', 'Query', 'Sort by', 'Direction', 'Limit', 'Offset', 'Cell width, Mobile', 'Cell width, Tablet', 'Cell width, Desktop', 'Gap between the cells', 'Cells of equal height', 'Id of the loop' ] ) );
check( '...the groups hold what they are called after: the type and the query in Data, the order in Order - the limit and the offset on a line -, the cells in Grid with the gap and the equal height on a line and the id below', ( function() {
	const groups = loopPane().querySelectorAll('.builder-group');
	return groups.map( function( group ) { return labelList( group ).join('|') } ).join('~') === 'Type|Query~Sort by|Direction|Limit|Offset~Cell width, Mobile|Cell width, Tablet|Cell width, Desktop|Gap between the cells|Cells of equal height|Id of the loop'
		&& groups[1].querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2' && groups[2].querySelectorAll('.builder-line').map( function( line ) { return line.children.length } ).join() === '2';
} )() );
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
check( 'the dialog of a component has three tabs: Content, Properties and CSS classes - the title names the component by its label, and no pencil is in it', dialogText('builder-dialog-kind') === 'Component "Title"' && JSON.stringify( dialogTabs() ) === '[["content","Content"],["properties","Properties"],["custom","CSS classes"]]'
	&& screen.getElementById('builder-dialog-name').hidden === true && dialogBox().classList.contains('is-wide') === false );
check( '...Content: the source as text, with an icon button beside it that opens the dialog of the sources', ( function() {
	const row = paneOf('content').querySelector('.builder-source');
	const button = row.querySelector('button');
	return paneOf('content').children.length === 1 && row.querySelector('.builder-source-caption').textContent === 'Current:' && row.querySelector('.builder-source-caption').hidden === false && row.querySelector('.builder-source-current').textContent === '/template/page-home/hero/title' && row.querySelector('.builder-source-name').hidden === true
		&& button.className === 'builder-icon-btn' && button.title === 'Source' && button.getAttribute('aria-label') === 'Source' && iconOf( button ) === 'source' && labelList( paneOf('content') ).length === 0;
} )() );
check( '...Properties: the attributes of the schema in the order of the schema, the two of them on a line', JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Level', 'Style' ] ) && paneOf('properties').children.length === 1 && paneOf('properties').children[0].className === 'builder-line' && paneOf('properties').children[0].children.length === 2
	&& controlOf( paneOf('properties'), 'Level' ).el.children.map( function( option ) { return option.value } ).join() === '1,2,3,4' && controlOf( paneOf('properties'), 'Style' ).el.children.map( function( option ) { return option.value+ '='+ option.textContent } ).join() === '=none,loud=loud,quiet=quiet'
	&& valueAt( paneOf('properties'), 'Level' ) === '1' && valueAt( paneOf('properties'), 'Style' ) === 'loud' );
change( paneOf('properties'), 'Level', '3' );
change( paneOf('properties'), 'Style', '' );
change( paneOf('custom'), 'Class of the component', 'big' );
check( '...and write the attributes, CSS classes the class of the component', componentAt( [ 0, 0, 0 ] ).attributes.level === '3' && componentAt( [ 0, 0, 0 ] ).attributes.style === '' && componentAt( [ 0, 0, 0 ] ).attributes['class'] === 'big' && JSON.stringify( labelList( paneOf('custom') ) ) === JSON.stringify( [ 'Class of the component' ] )
	&& paneOf('custom').querySelector('.nino-admin-hint').textContent === words['/_admin/builder/hint/custom'] && changes === 3 );

builder._openSettings( [ 0, 0, 2 ] );
check( 'a link is a source of its own, a line of its own - the other attributes follow in the order of the schema, two to a line where both are short, a select that is left over on a line to itself', dialogText('builder-dialog-kind') === 'Component "Button"'
	&& classes( paneOf('properties').children ) === 'builder-source,builder-line,nino-admin-field' && JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Style', 'Size', 'Opens in' ] ) && paneOf('properties').children[1].children.length === 2
	&& paneOf('properties').children[0].querySelector('.builder-source-name').textContent === 'Link' && paneOf('properties').children[0].querySelector('.builder-source-current').textContent === '/_nino/webpage/contact/uri'
	&& paneOf('properties').children[0].querySelector('.builder-source-caption').hidden === true );
builder._openSettings( [ 1, 1, 0 ] );
check( '...a text of its own and a select go together, the select that is left over is alone: alt and focus, then ratio', dialogText('builder-dialog-kind') === 'Component "Image"' && classes( paneOf('properties').children ) === 'builder-line,nino-admin-field' && JSON.stringify( labelList( paneOf('properties') ) ) === JSON.stringify( [ 'Alternative text', 'Focus', 'Ratio' ] )
	&& controlsIn( paneOf('properties') ).map( function( control ) { return control.kind } ).join() === 'text,select,select' );

check( 'the attributes of a schema are paired in the order of the schema: a select, a switch, a number or a line of text goes with the next of them; a lines field, a link, a picture or a key has a line to itself', ( function() {
	const declared = { a : { type : 'select' }, b : { type : 'bool' }, c : { type : 'lines' }, d : { type : 'int' }, e : { type : 'string' }, f : { type : 'href' }, g : { type : 'select' }, h : { type : 'image' }, i : { type : 'key' }, j : { type : 'select' }, k : { type : 'select' }, l : { type : 'select' } };
	return JSON.stringify( builder._attributeLines( declared ) ) === '[["a","b"],["c"],["d","e"],["f"],["g"],["h"],["i"],["j","k"],["l"]]' && JSON.stringify( builder._attributeLines( {} ) ) === '[]'
		&& JSON.stringify( builder._attributeLines( registry().components.button.attributes ) ) === '[["href"],["style","size"],["target"]]' && JSON.stringify( builder._attributeLines( registry().components.image.attributes ) ) === '[["alt","focus"],["ratio"]]'
		&& JSON.stringify( builder._attributeLines( registry().components.title.attributes ) ) === '[["level","style"]]';
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
check( '...and using it makes the key the source, with what it is made with: the label of the component for its text - the dialog closes', componentAt( [ 0, 0, 0 ] ).source === '/template/page-home/hero/cta' && componentAt( [ 0, 0, 0 ] ).create.value === 'Title' && pickerBox().open === false
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
	const opened = menu.hidden === false && menu.getAttribute('role') === 'menu' && menu.children.length === 7 && menu.children.every( function( item ) { return item.getAttribute('role') === 'menuitem' } );
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
	&& builder._doc.model.blocks[4].cols[0].components[0].create.value === 'Title' && builder._fresh.faq === true && builder._fresh.section === undefined );
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
