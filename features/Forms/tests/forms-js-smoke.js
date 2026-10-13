/**
 *	Nino
 *	forms-js-smoke.js		What the panel's admin.js does over a dom stand-in:
 *												that the list screen carries exactly one fixed action
 *												bar - the one with New form - and the editor exactly
 *												one of its own, that the two submission settings are a
 *												card with a Save and a status line of their own rather
 *												than a second bar over the first, that saving them
 *												posts forms/settings with both values, keeps the
 *												button off while the request runs and says in the card
 *												whether it worked, and that coming back to the panel
 *												draws the list again.
 *
 *												And the editor's own part of a field: the name a field
 *												takes from its label (and keeps to itself once it is
 *												typed into, through add, retype and move), the pair of
 *												buttons that moves it, the type names in the
 *												workbench's language, the mail templates as a list that
 *												keeps what the form names, the options of a select or
 *												of radio buttons as a list of rows that tells the
 *												field what it holds (typed, added, moved, removed -
 *												kept through a retype between the two, left out by
 *												one to anything else, never with a row that is
 *												empty), and where a refused save is marked, the list
 *												as a whole with the focus in it, and the element that
 *												has the focus described by the sentence as well until
 *												the marks are taken off.
 *
 *												The panel is what it builds on Nino.adminUi.panel(): the
 *												list asked for when the page is ready, the unsaved input
 *												of its editor reported to the shell, and a refused save
 *												said by the sentence of its code and the words it carries
 *												for each field and control, looked up in the language of
 *												the workbench.
 *
 *												No jsdom, no dependency: the same element stand-in the
 *												other feature tests build. The kernel's _nino/Nino.js
 *												runs in it as it is - from NINO_ROOT, or three levels up
 *												when the feature sits in a checkout - for the slug a
 *												field's name is made with and for the ready event the
 *												panel starts on, and the workbench (Nino.adminUi,
 *												Nino.admin, Nino.content) is a stand-in laid over it,
 *												kept to what docs/features.md says of it: the action
 *												bar carries the class string the kernel's own
 *												listActions() gives it, and the list of options does
 *												what stringList() does for a form: its value is the
 *												json of a hidden input and onChange hears the list -
 *												so this runs with nothing but node.
 *
 *	Usage: node features/Forms/tests/forms-js-smoke.js
 *	       NINO_ROOT=../nino node features/Forms/tests/forms-js-smoke.js
 */

'use strict';

/* global require, __dirname, process, Buffer */

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

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const ninoJs = fs.readFileSync( path.join( root, '_nino/Nino.js' ), 'utf8' );
const source = fs.readFileSync( path.join( __dirname, '../assets/admin.js' ), 'utf8' );

/** Every descendant of $root, depth first */
function descendants( root ) {
	let all = [];
	for( const child of root.children ) {
		all.push( child );
		all = all.concat( descendants( child ) );
	}
	return all;
}

/**
 *	Whether $node answers one simple selector: .class, #id, tag, [data-x] or
 *	[data-x="value"]
 */
function matches( node, selector ) {
	if( selector.charAt( 0 ) === '.' ) return node.matchesClass( selector.slice( 1 ) );
	if( selector.charAt( 0 ) === '#' ) return node.id === selector.slice( 1 );
	if( selector.charAt( 0 ) === '[' ) {
		const parts = /^\[([a-z-]+)(?:="([^"]*)")?\]$/.exec( selector );
		if( parts === null ) return false;
		// data-x is the element's dataset, anything else one of its attributes
		const own = parts[1].indexOf('data-') === 0 ? node.dataset : node.attributes;
		const name = parts[1].indexOf('data-') === 0 ? parts[1].slice( 5 ) : parts[1];
		return Object.prototype.hasOwnProperty.call( own, name ) && ( parts[2] === undefined || own[name] === parts[2] );
	}
	return node.tagName === selector.toUpperCase();
}

/**
 *	One element, with just enough of the interface the panel reaches for
 */
function element( tag ) {

	const el = {
		tagName					: tag.toUpperCase(),
		attributes			: {},
		children				: [],
		parent					: null,
		dataset					: {},
		id							: '',
		type						: '',
		value						: '',
		href						: '',
		disabled				: false,
		checked					: false,
		listeners				: {},
		_text						: '',
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		parentNode			: null,
		appendChild			: function( child ) { child.parent = this; child.parentNode = this; this.children.push( child ); return child },
		after						: function( node ) {
			const siblings = this.parent.children;
			node.parent = this.parent;
			node.parentNode = this.parent;
			siblings.splice( siblings.indexOf( this ) + 1, 0, node );
		},
		remove					: function() {
			if( this.parent !== null )
				this.parent.children.splice( this.parent.children.indexOf( this ), 1 );
			this.parent = null;
			this.parentNode = null;
		},
		focus						: function() { element.focused = this },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		addEventListener: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		dispatch				: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {} } ) } ) },
		click						: function() { this.dispatch('click') },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll: function( selector ) {
			// A comma lists alternatives, a space steps down to a descendant
			return selector.split( ',' ).reduce( function( found, alternative ) {
				let scope = [ el ];
				alternative.trim().split( /\s+/ ).forEach( function( simple ) {
					scope = scope.reduce( function( next, root ) {
						return next.concat( descendants( root ).filter( function( node ) { return matches( node, simple ) } ) );
					}, [] );
				} );
				return found.concat( scope.filter( function( node ) { return found.indexOf( node ) === -1 } ) );
			}, [] );
		},
	};

	el.classList = {
		add				: function( name ) { if( el.matchesClass( name ) === false ) el.attributes['class'] = ( ( el.attributes['class'] || '' )+ ' '+ name ).trim() },
		remove		: function( name ) { el.attributes['class'] = ( el.attributes['class'] || '' ).split( /\s+/ ).filter( function( n ) { return n !== name && n !== '' } ).join(' ') },
		contains	: function( name ) { return el.matchesClass( name ) },
		toggle		: function( name, on ) { if( on === true ) el.classList.add( name ); else el.classList.remove( name ) },
	};

	Object.defineProperty( el, 'textContent', {
		get : function() { return el._text + el.children.map( function( c ) { return c.textContent } ).join('') },
		set : function( value ) { el._text = String( value ); el.children = [] },
	} );

	Object.defineProperty( el, 'className', {
		get : function() { return el.attributes['class'] || '' },
		set : function( value ) { el.attributes['class'] = String( value ) },
	} );

	// The panel only ever empties a pane with it - a stand-in that parsed
	// html would be a browser, and what is under test here is what is drawn
	Object.defineProperty( el, 'innerHTML', {
		get : function() { return '' },
		set : function() { el.children = [] },
	} );

	return el;
}

// The element the last focus() call was made on
element.focused = null;

// Nino.adminUi.status() in miniature: what it was told, which form it follows, and
// the role it takes on the line it is given
const lines = [];
const statusStub = function( el ) {
	el.setAttribute( 'role', 'status' );
	const line = { element : el, told : [], bound : null,
		saving : function() { line.told.push('saving') },
		saved : function() { line.told.push('saved') },
		error : function( status, response, key ) { line.told.push( [ 'error', status, response.error, key ].join(' ') ) },
		bind : function( form ) { line.bound = form },
	};
	lines.push( line );
	return line;
};

/**
 *	The shell's registry of unsaved input, in miniature: the entries panels
 *	registered under their uri, and the question the shell asks of them
 *
 *	@return			{Object}
 */
function shellRegistry() {
	const registry = { entries : {},
		register : function( name, entry ) { registry.entries[name] = entry },
		isDirty : function( names ) { return names.some( function( name ) { return registry.entries[name] !== undefined && registry.entries[name].isDirty() === true } ) },
	};
	return registry;
}

/**
 *	The panel, drawn into the two panes the shell hands it
 *
 *	@param			{Object}	options			{ forms } - what forms/list answers with;
 *									hold: true keeps the answer to forms/settings
 *									back until release() is called, status: the
 *									status that answer carries, api: stands in for
 *									Nino.adminUi.api, templates: what forms/list
 *									names as mail templates, types: the field types
 *									it names, text: what a fill key reads as (the
 *									key itself where nothing is said), refuse: the
 *									body forms/save answers with, status 400
 */
function panel( options ) {

	options = options || {};

	const body = element('body');
	const list = element('div');
	const form = element('div');

	list.id = 'forms-list';
	form.id = 'forms-form';
	body.appendChild( list );
	body.appendChild( form );
	body.scrollLeft = 0;
	body.scrollTop = 0;

	const calls = [];
	const lists = [];
	const numbers = [];
	const timers = [];
	const registry = shellRegistry();
	let pending = null;
	let status = 200;

	const dc = {
		body				: body,
		documentElement		: { clientHeight : 640, clientWidth : 1024, scrollLeft : 0, scrollTop : 0, style : {}, classList : { add : function() {}, remove : function() {} } },
		cookie					: '',
		readyState				: 'complete',
		createElement			: function( tag ) { return element( tag ) },
		getElementById		: function( id ) { return descendants( body ).filter( function( n ) { return n.id === id } )[0] || null },
		querySelector			: function() { return null },
		querySelectorAll	: function() { return [] },
		addEventListener	: function() {},
	};

	// The page Nino.js loads into: what it asks of the browser at load. The timers
	// are kept, not run, so that the page's ready event is the test's to fire
	const sandbox = {
		console					: console,
		document				: dc,
		navigator					: { userAgent : 'node', language : 'en-US' },
		location				: { pathname : '/', origin : 'https://example.com', href : 'https://example.com/' },
		XMLHttpRequest			: function() {},
		FormData				: function() {},
		TextEncoder				: TextEncoder,
		btoa					: function( binary ) { return Buffer.from( binary, 'latin1' ).toString('base64') },
		atob					: function( b64 ) { return Buffer.from( b64, 'base64' ).toString('latin1') },
		setTimeout				: function( fn ) { timers.push( fn ); return timers.length },
		clearTimeout			: function() {},
		Event						: function( type ) { this.type = type },
		dispatchEvent			: function() {},
		addEventListener	: function() {},
		requestAnimationFrame : function() { return 0 },
		matchMedia				: function() { return { matches : false, addEventListener : function() {} } },
		confirm					: function() { return true },
	};
	sandbox.window = sandbox;
	sandbox.self = sandbox;

	const context = vm.createContext( sandbox );
	vm.runInContext( ninoJs, context, { filename : 'Nino.js' } );

	// The kernel's own Nino.js is what is running; the workbench is laid over it
	const Nino = sandbox.Nino;

	const answer = function( callback, payload ) {
		if( status !== 200 )
			return callback( status, { error : 'Refused' } );
		const values = JSON.parse( payload.data );
		callback( 200, { retention : values.retention, store : values.store } );
	};

	// Nino.adminUi.format(): the placeholders %s %d %n, filled in order
	const format = function( text, ...params ) {
		let at = 0;
		return String( text ).replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } );
	};

	Nino.content = { getText : function( key ) { return typeof options.text === 'function' ? options.text( key ) : key } };

	// The kernel's own pieces in miniature: listActions() is the fixed bar
	// every list screen ends in, with the class string it really carries,
	// and the shared fields hand their key back as data-key
	Nino.adminUi = {
		// Nino.adminUi.api.call() calls back with the status and the body of the
		// answer, and errorText() says a failure by the workbench's rule: the
		// sentence of its code, else "(status) message"
		api						: {
			call : function( action, payload, callback ) {
				const sent = { action : action, data : JSON.stringify( payload ) };
				calls.push( sent );
				if( action === 'forms/list' )
					return callback( 200, {
						forms				: options.forms || [],
						types				: options.types || [ 'text', 'email', 'select' ],
						templates		: options.templates || [],
						reserved	: [ 'form', 'location', '_csrf', '_t', 'id', 'date', 'ip' ],
						default			: false,
						endpoint	: true,
						retention		: 3,
						store				: true,
					} );
				if( action === 'forms/save' && options.refuse )
					return callback( 400, options.refuse );
				if( action === 'forms/save' && status === 200 )
					return callback( 200, { form : JSON.parse( sent.data ).form } );
				if( options.hold === true )
					return pending = function() { answer( callback, sent ) };
				answer( callback, sent );
			},
			errorText : function( status, response, key ) {
				const body = response !== null && typeof response === 'object' ? response : {};
				const known = typeof body.code === 'string' ? Nino.content.getText( '/_admin/error/'+ body.code ) : '';
				return known !== '' ? format( known, ...( body.params || [] ) ) : '('+ status+ ') '+ ( body.error || key );
			},
		},
		format				: format,
		status				: statusStub,
		hint				: function( text, kind ) { const el = element('p'); el.className = kind === 'error' ? 'nino-admin-hint nino-admin-error' : 'nino-admin-hint'; el.textContent = text; return el },
		el						: function( tag, className, text ) { const el = element( tag ); if( className ) el.className = className; if( text !== undefined ) el.textContent = text; return el },
		button				: function( text, onClick, config ) {
			config = config || {};
			const el = element('button');
			el.type = config.type === 'submit' ? 'submit' : 'button';
			if( config.className ) el.className = config.className;
			el.textContent = text;
			if( config.name ) { el.title = config.name; el.setAttribute( 'aria-label', config.name ) }
			el.disabled = config.disabled === true;
			if( config.id ) el.id = config.id;
			if( typeof onClick === 'function' ) el.addEventListener( 'click', onClick );
			return el;
		},
		textField			: function( config ) {
			const el = element('label');
			el.className = config.className || 'nino-admin-field';
			const name = element('span');
			name.textContent = config.label ?? '';
			if( config.required === true ) {
				const star = element('span');
				star.className = 'nino-admin-required';
				star.textContent = '*';
				name.appendChild( star );
			}
			el.appendChild( name );
			const input = element('input');
			input.className = 'nino-admin-input';
			input.type = config.type || 'text';
			input.value = config.value ?? '';
			input.autocomplete = config.autocomplete || '';
			if( config.required === true ) {
				input.required = true;
				input.setAttribute( 'aria-required', 'true' );
			}
			el.appendChild( input );
			if( config.hint ) {
				const small = element('small');
				small.className = 'nino-admin-hint';
				small.textContent = config.hint;
				el.appendChild( small );
			}
			return el;
		},
		showError			: function( container, status, response, key ) {
			container.innerHTML = '';
			const p = element('p');
			p.className = 'nino-admin-error';
			p.textContent = Nino.adminUi.api.errorText( status, response, key );
			container.appendChild( p );
			return p;
		},
		// levels(): the ids that start alike, one of them on show at a time
		levels				: function( prefix, names ) {
			return { show : function( name ) { names.forEach( function( level ) { dc.getElementById( prefix+ '-'+ level ).classList.toggle( 'admin-hidden', level !== name ) } ) } };
		},
		// panel(): the lifecycle the manual describes - the list asked for when the
		// page is ready (or when the panel is first shown), drawn by render() from the
		// answer, a failure said in the mount with the panel's key, and the spec's dirty
		// entry handed to the shell's registry at the first request
		panel					: function( spec ) {
			let registered = false;
			const self = {
				uri				: spec.uri,
				ready			: false,
				loading		: false,
				data			: null,
				mount				: function() { return dc.getElementById( spec.mount ) },
				call			: function( action, payload, done ) { Nino.adminUi.api.call( spec.uri+ '/'+ action, payload, done ) },
				fail			: function( container, status, response, key ) { return Nino.adminUi.showError( container, status, response, key || spec.error || '/_admin/'+ spec.uri+ '/error/load' ) },
				init			: function( then ) {
					if( self.loading === true || self.mount() === null )
						return;
					self.loading = true;
					if( registered === false && typeof spec.dirty === 'object' ) {
						Nino.admin.dirty.register( spec.uri, spec.dirty );
						registered = true;
					}
					self.call( spec.action || 'list', {}, function( status, response ) {
						self.loading = false;
						if( status === 200 ) {
							self.data = response;
							self.ready = true;
							spec.render( response, self );
							if( typeof then === 'function' )
								then();
							return;
						}
						self.fail( self.mount(), status, response );
						self.ready = false;
					} );
				},
				reload				: function( then ) { self.init( then ) },
				showCurrent		: function() { if( self.ready === false || spec.show === 'always' ) self.init() },
			};
			if( spec.boot === 'ready' )
				Nino.events.bindCallback( 'ready', function() { self.init() } );
			return self;
		},
		emptyState		: function( text ) { const el = element('p'); el.className = 'nino-admin-empty'; el.textContent = text; return el },
		actionBar			: function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
		listActions		: function( buttons ) { const el = element('div'); el.className = 'nino-admin-actionbar nino-admin-list-actions'; buttons.forEach( function( b ) { el.appendChild( b ) } ); return el },
		contextBar		: function( back ) { const el = element('div'); el.className = 'nino-admin-contextbar'; el.appendChild( back ); return el },
		numberField		: function( config ) {
			numbers.push( config );
			const el = element('label');
			el.className = 'nino-admin-field';
			const input = element('input');
			input.type = 'number';
			input.value = config.value;
			input.dataset.key = config.key;
			el.appendChild( input );
			return el;
		},
		selectField		: function( config ) {
			const el = element('label');
			el.className = config.className || 'nino-admin-field';
			const select = element('select');
			select.className = 'nino-admin-input';
			select.dataset.key = config.key;
			config.options.forEach( function( entry ) {
				const option = element('option');
				option.value = entry.value;
				option.textContent = entry.label;
				select.appendChild( option );
			} );
			select.value = config.value;
			select.addEventListener( 'change', function() { if( typeof config.onChange === 'function' ) config.onChange( select.value ) } );
			el.appendChild( select );
			return el;
		},
		switchField		: function( config ) {
			const el = element('label');
			el.className = 'nino-admin-switch';
			const input = element('input');
			input.type = 'checkbox';
			input.checked = config.checked === true;
			input.dataset.key = config.key;
			el.appendChild( input );
			return el;
		},
		// stringList() in miniature: its label first, the value as the json of a
		// hidden input, a row of a text and its three buttons for each text, and the
		// button that adds one, with the classes the kernel's own gives them. A row
		// that is empty is not an entry: each change rewrites the input without it
		// and tells onChange the list as a form reads it
		stringList		: function( config ) {

			lists.push( { key : config.key, label : config.label, value : ( config.value || [] ).slice(), text : config.text } );

			const texts = ( config.value || [] ).map( String );

			const el = element('div');
			el.className = 'nino-admin-field nino-admin-stringlist';

			const name = element('span');
			name.textContent = config.label;
			el.appendChild( name );

			const store = element('input');
			store.type = 'hidden';
			store.dataset.field = config.key;
			store.dataset.type = 'array';
			store.value = JSON.stringify( config.value || [] );
			el.appendChild( store );

			const rows = element('ul');
			rows.className = 'nino-admin-stringlist-rows';
			el.appendChild( rows );

			const commit = function() {
				store.value = JSON.stringify( texts.filter( function( text ) { return text.trim() !== '' } ) );
				if( typeof config.onChange === 'function' )
					config.onChange( JSON.parse( store.value ) );
			};

			const button = function( className, title, glyph, disabled, onClick ) {
				const control = element('button');
				control.type = 'button';
				control.className = className;
				control.textContent = glyph;
				control.setAttribute( 'aria-label', title );
				control.disabled = disabled === true;
				control.addEventListener( 'click', onClick );
				return control;
			};

			const draw = function() {

				rows.innerHTML = '';

				if( texts.length === 0 ) {
					const empty = element('li');
					empty.className = 'nino-admin-stringlist-empty';
					empty.textContent = config.text.empty;
					rows.appendChild( empty );
					return;
				}

				texts.forEach( function( text, index ) {

					const item = element('li');

					const input = element('input');
					input.type = 'text';
					input.className = 'nino-admin-stringlist-input';
					input.value = text;
					input.setAttribute( 'aria-label', config.text.item.replace( '%d', String( index + 1 ) ) );
					input.addEventListener( 'input', function() { texts[index] = input.value; commit() } );
					item.appendChild( input );

					// A button at the end of the list is off, and a click on it moves nothing
					const move = function( delta ) {
						if( texts[index + delta] === undefined )
							return;
						const moved = texts[index];
						texts[index] = texts[index + delta];
						texts[index + delta] = moved;
						commit();
						draw();
					};

					const actions = element('span');
					actions.className = 'nino-admin-stringlist-actions';
					actions.appendChild( button( '', config.text.up, '↑', index === 0, function() { move( -1 ) } ) );
					actions.appendChild( button( '', config.text.down, '↓', index === texts.length - 1, function() { move( 1 ) } ) );
					actions.appendChild( button( 'nino-admin-stringlist-remove', config.text.remove, '✕', false, function() { texts.splice( index, 1 ); commit(); draw() } ) );
					item.appendChild( actions );

					rows.appendChild( item );
				} );
			};

			el.appendChild( button( 'nino-admin-stringlist-add', '', config.text.add, false, function() { texts.push(''); draw() } ) );

			draw();

			return el;
		},
	};

	// Where the real shell defines them (script.js): the back link's row, and the
	// registry of unsaved input a panel reports to
	Nino.admin = {
		formToolbar		: function( back ) { return Nino.adminUi.contextBar( back ) },
		dirty					: registry,
	};

	if( options.api )
		Nino.adminUi.api = options.api;

	vm.runInContext( source, context, { filename : 'admin.js' } );

	// The page is ready: the timers Nino.js asked for run, and with them the callbacks
	// bound to its ready event - which is where the panel starts. An error such a
	// callback threw is thrown again from a timer, and so it is here
	while( timers.length > 0 )
		timers.shift()();

	// The settings form is the one that is a form on the list screen
	const settings = function() { return list.querySelectorAll('.forms-settings')[0] || null };

	return {
		list			: list,
		form			: form,
		calls				: calls,
		lists				: lists,
		numbers			: numbers,
		registry		: registry,
		panel				: Nino.admin.forms,
		settings	: settings,
		save			: function() { return settings().querySelector('button') },
		msg					: function() { return settings().querySelector('#forms-settings-msg') },
		field				: function( key ) { return settings().querySelector('[data-key="'+ key+ '"]') },
		bars			: function( mount ) { return mount.querySelectorAll('.nino-admin-actionbar').length },
		rows			: function() { return form.querySelectorAll('.forms-field') },
		hidden			: function( mount ) { return mount.classList.contains('admin-hidden') },
		status			: function( value ) { status = value },
		release			: function() { const run = pending; pending = null; run() },
	};
}

/**
 *	The list of options in the row of a field, as the stand-in draws it: the
 *	texts of its rows, the json its hidden input carries, the three buttons
 *	of a row (up, down, remove), the button that adds a row, and typing into
 *	a row - the way a person reaches each of them
 */
function optionTexts( row ) { return row.querySelectorAll('.nino-admin-stringlist-input').map( function( input ) { return input.value } ) }
function optionStore( row ) { return row.querySelector('[data-field="options"]').value }
function optionButtons( row, index ) { return row.querySelectorAll('.nino-admin-stringlist-actions')[index].children }
function addOption( row ) { row.querySelector('.nino-admin-stringlist-add').click() }
function typeOption( row, index, text ) {
	const input = row.querySelectorAll('.nino-admin-stringlist-input')[index];
	input.value = text;
	input.dispatch('input');
}

const two = [
	{ key : 'contact', name : 'Contact', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', fields : [ { name : 'email', label : '', type : 'email', required : true, options : [] } ], entries : 4 },
	{ key : 'quote', name : 'Quote', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', fields : [ { name : 'budget', label : '', type : 'text', required : false, options : [] } ], entries : 0 },
];


/*	A refused save names what is wrong by words: the code forms_fields carries the
	three maps of the server as its params - the word of what is wrong with a field,
	by its place in the list, the control of that field the word belongs to, and the
	words of the controls of the form itself - and the script says each in the
	language of the workbench, the sentence of the code on top. These are the
	workbench's fills, in English; a key nobody gave a sentence reads as itself	*/
const sentences = {
	'/_admin/error/forms_fields'			: 'Not saved.',
	'/_admin/error/forms_duplicate'		: 'Another form has that key.',
	'/_admin/error/forms_invalid'		: 'The form needs a key of its own.',
	'/_admin/forms/problem/duplicate'	: 'Taken.',
	'/_admin/forms/problem/options'		: 'Needs one.',
	'/_admin/forms/problem/field'		: 'Where?',
	'/_admin/forms/problem/to'			: 'No address.',
	'/_admin/forms/problem/ownertpl'	: 'Gone.',
	'/_admin/forms/problem/fields'		: 'Needs a field.',
	'/_admin/forms/problem/keytaken'	: 'This key is taken.',
};
const said = function( key ) { return sentences[key] ?? key };
const refusal = function( fields, controls, about ) {
	return { error : 'the form could not be saved as it is - see the marked fields', code : 'forms_fields', params : [ fields, controls, about ] };
};


// --- The list ------------------------------------------------------------------

const open = panel( { forms : two } );

check( 'the panel asks for the list once and draws one card per form, and the settings card after them',
	open.calls.length === 1 && open.calls[0].action === 'forms/list'
	&& open.list.querySelectorAll('[data-form]').length === 2 && open.settings() !== null );

/*	The workbench's fixed action bar is the screen's: position:fixed at the
	foot of the viewport, so a second one lies over the first and over the
	button that is in it. The list's is New form - the retention's Save was
	laid over it on every screen size, and a click on New form saved the
	retention instead	*/
check( 'the list screen holds exactly one fixed action bar, and it is the one with New form',
	open.bars( open.list ) === 1
	&& open.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].textContent === '/_admin/forms/label/new' );
check( '...and the settings are a card of their own, not a bar',
	open.settings().tagName === 'FORM' && open.settings().matchesClass('nino-admin-card') === true
	&& open.settings().matchesClass('nino-admin-actionbar') === false
	&& open.settings().querySelectorAll('.nino-admin-actionbar').length === 0 );
check( '...with its own submit button and its own status line inside it, in a row of the workbench\'s actions',
	open.save().type === 'submit' && open.save().textContent === '/_admin/common/label/save'
	&& open.msg() !== null && open.msg().getAttribute('role') === 'status'
	&& open.settings().querySelectorAll('.nino-admin-actions').length === 1 && open.settings().querySelectorAll('.nino-admin-actions')[0].children[0] === open.save() );
check( '...and it comes after the list\'s bar in the page, where it always did',
	open.list.children.indexOf( open.settings() ) > open.list.children.indexOf( open.list.querySelectorAll('.nino-admin-list-actions')[0] ) );
check( 'each form\'s card has its Edit and its Delete in a row of the workbench\'s actions', open.list.querySelectorAll('[data-form]').every( function( card ) {
	const row = card.querySelectorAll('.nino-admin-actions')[0];
	return row !== undefined && row.children.length === 2 && row.children[0].matchesClass('nino-admin-btn-primary') && row.children[1].matchesClass('nino-admin-btn-danger');
} ) );

const phrased = panel( { forms : two, text : function( key ) { return { '/_admin/forms/hint/shortcode' : 'Write %s', '/_admin/forms/label/entries' : '%s submissions' }[key] || key } } );
check( 'a card names the shortcode that draws the form, with its key, and what the form collects and how much has come in through it',
	phrased.list.querySelectorAll('[data-form]')[0].querySelectorAll('.nino-admin-hint').map( function( hint ) { return hint.textContent } ).join('|') === 'Write [form key="contact"]|email · 4 submissions' );


// --- Saving the settings -------------------------------------------------------

const save = panel( { forms : two, hold : true } );
const saveLine = lines[lines.length - 1];

check( 'the retention is a number field of the workbench from 1 to 60, counted in months - the unit is the field\'s to write, the label does not carry it',
	save.numbers.length === 1 && save.numbers[0].key === 'retention' && save.numbers[0].min === 1 && save.numbers[0].max === 60 && save.numbers[0].unit === 'months'
	&& save.numbers[0].label === '/_admin/forms/label/retention' );

save.field('retention').value = '6';
save.field('store').checked = false;
save.settings().dispatch('submit');

check( 'saving posts forms/settings with the retention as a number and the switch as a boolean',
	save.calls.length === 2 && save.calls[1].action === 'forms/settings'
	&& save.calls[1].data === JSON.stringify( { retention : 6, store : false } ) );
check( '...keeps the card\'s button off while the request runs and tells the status line it is saving',
	save.save().disabled === true && saveLine.told.join() === 'saving' );

save.release();

check( '...and tells it when the answer is in, with the button on again',
	saveLine.told.join() === 'saving,saved' && save.save().disabled === false );
check( '...and what the endpoint answered is what the panel keeps', save.panel._retention === 6 && save.panel._store === false );

save.status( 422 );
save.settings().dispatch('submit');
save.release();

check( 'a refused save is told to the status line as the error it is, with the status, the body and the panel\'s own sentence for it, and gives the button back',
	saveLine.told[saveLine.told.length - 1] === 'error 422 Refused /_admin/common/error/save' && save.save().disabled === false );

save.status( 200 );
save.settings().dispatch('submit');

check( '...and the next try is told as a save again', saveLine.told[saveLine.told.length - 1] === 'saving' );

save.release();


// --- The editor ----------------------------------------------------------------

const edit = panel( { forms : two } );

edit.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();

check( 'New form opens the editor in place of the list',
	edit.hidden( edit.list ) === true && edit.hidden( edit.form ) === false && edit.panel._editing !== null && edit.panel._editing.key === '' );
check( '...and the editor holds exactly one fixed action bar, its own Save',
	edit.bars( edit.form ) === 1 && edit.form.querySelectorAll('button').filter( function( b ) { return b.type === 'submit' } ).length === 1 );
check( '...and the settings card is not drawn into it', edit.form.querySelectorAll('.forms-settings').length === 0 );
check( '...with the key a required field, marked the way the workbench marks one', edit.form.querySelector('[data-about="key"]').required === true
	&& edit.form.querySelector('[data-about="key"]').getAttribute('aria-required') === 'true'
	&& edit.form.querySelector('[data-about="key"]').parentNode.querySelectorAll('.nino-admin-required').length === 1
	&& edit.form.querySelector('[data-about="name"]').required !== true );


// --- Coming back ---------------------------------------------------------------

const back = panel( { forms : two } );

let threw = null;
try {
	back.panel.showCurrent();
} catch( error ) {
	threw = error;
}

check( 'showCurrent() on the list redraws it without throwing, with one bar and one settings card',
	threw === null && back.bars( back.list ) === 1 && back.list.querySelectorAll('.forms-settings').length === 1
	&& back.list.querySelectorAll('[data-form]').length === 2 );

back.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
threw = null;
try {
	back.panel.showCurrent();
} catch( error ) {
	threw = error;
}

check( '...and on the editor it draws the editor again, still with its one bar',
	threw === null && back.hidden( back.form ) === false && back.bars( back.form ) === 1 );

back.form.querySelectorAll('.nino-admin-back-link')[0].click();

check( 'the way back from the editor puts the list on, with one bar',
	back.hidden( back.list ) === false && back.hidden( back.form ) === true && back.bars( back.list ) === 1 );


// --- Asking the workbench ------------------------------------------------------

const routed = [];
const viaApi = panel( { api : { call : function( action, payload, callback ) {
	routed.push( [ action, payload ] );
	callback( 200, { forms : two, types : [ 'text' ], reserved : [], default : false, endpoint : true, retention : 3, store : true } );
} } } );
check( 'the panel asks the workbench\'s request helper for \'forms/<action>\'',
	routed.length === 1 && routed[0][0] === 'forms/list' && viaApi.list.querySelectorAll('[data-form]').length === 2 );


// --- Saying what happened ------------------------------------------------------

const lined = panel( { forms : two } );
const cardLine = lines[lines.length - 1];
check( 'the settings card\'s message is a status line of the shell\'s, following the card', cardLine.element === lined.msg() && cardLine.bound === lined.settings() );

const typed = panel( { forms : two } );
typed.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
const editorLine = lines[lines.length - 1];
check( 'the editor\'s message is one too, following the editor\'s form', editorLine.bound !== null && editorLine.bound.tagName === 'FORM' && typed.form.querySelectorAll('form').length === 1 );

const errors = panel( { forms : two, api : { call : function( action, payload, callback ) {
	if( action === 'forms/list' )
		return callback( 500, { error : 'Down' } );
}, errorText : function( status, response, key ) { return 'told '+ status+ ' '+ response.error+ ' ['+ key+ ']' } } } );
check( 'a list that could not be read says so in the shell\'s words', errors.list.querySelectorAll('.nino-admin-error')[0].textContent === 'told 500 Down [/_admin/common/error/load]' );


// --- Unsaved input -------------------------------------------------------------

/*	The panel hands the shell an entry for its editor when it first asks for its
	list (the dirty of its spec, which the shell's Nino.admin.dirty.register()
	takes), and the shell asks it: is there input nobody has saved, save it, take
	it for saved. The registry is the shell's and a stand-in here	*/
const guarded = panel( { forms : two } );
const entry = guarded.registry.entries['forms'] || null;

check( 'the panel registers its editor with the shell under its own uri, with the three things the shell asks of it',
	entry !== null && typeof entry.isDirty === 'function' && typeof entry.save === 'function' && typeof entry.discard === 'function' );
check( '...and nothing is unsaved while there is no editor', entry.isDirty() === false && guarded.registry.isDirty( [ 'forms' ] ) === false );

guarded.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
check( '...nor while the editor holds what it was drawn with', entry.isDirty() === false );

guarded.form.querySelector('[data-about="key"]').value = 'renamed';
check( '...but there is once something is typed into it', entry.isDirty() === true && guarded.registry.isDirty( [ 'forms' ] ) === true );

// Another panel and back again: an editor that holds input is left as it is
const mark = element('span');
guarded.form.appendChild( mark );
guarded.panel.showCurrent();
check( 'coming back to the panel does not draw an editor over what is typed into it', guarded.form.children.indexOf( mark ) !== -1 );
entry.discard();
check( 'discarding takes what is typed for saved', entry.isDirty() === false );
guarded.panel.showCurrent();
check( '...and an editor that holds nothing unsaved is drawn again as before', guarded.form.children.indexOf( mark ) === -1 && guarded.hidden( guarded.form ) === false );

// The shell's Save saves from its own question
guarded.form.querySelector('[data-about="key"]').value = 'Renamed';
let told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save posts the form and says it was saved - once, before the list is read again',
	told.length === 1 && told[0] === true && guarded.calls.filter( function( call ) { return call.action === 'forms/save' } ).length === 1
	&& JSON.parse( guarded.calls.filter( function( call ) { return call.action === 'forms/save' } )[0].data ).form.key === 'renamed' );

guarded.status( 422 );
guarded.panel._editing = JSON.parse( JSON.stringify( two[0] ) );
guarded.panel._renderForm();
told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( '...and says it was not when the server refused it', told.length === 1 && told[0] === false );

guarded.panel._editing = null;
told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( '...as it does when there is no editor to save', told.length === 1 && told[0] === false );


// --- A field's name follows its label -------------------------------------------

const allTypes = [ 'text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'date' ];
const reservedNames = [ 'form', 'location', '_csrf', '_t', 'id', 'date', 'ip' ];
const named = panel( { forms : two, types : allTypes } ).panel;

/*	The pure part: what a label becomes. The slug is the kernel's Nino.slug(), the
	one that is running here: the umlauts and the sharp s are spelled out, so are
	the other letters its table has, and any other letter is a separator. The
	result is a name the engine takes - a letter first, 64 characters at most,
	never a reserved name and never one that is taken	*/
[
	[ 'Straße', 'strasse' ], [ 'Ihre Nachricht', 'ihre-nachricht' ], [ 'Größe', 'groesse' ], [ 'ÄÖÜ', 'aeoeue' ], [ 'Café', 'cafe' ],
	[ 'Ærøskøbing', 'aeroskobing' ], [ 'Łódź', 'lod' ],
	[ '1. Wahl', 'field-1-wahl' ], [ '  E-Mail (privat) ', 'e-mail-privat' ], [ '', 'field' ], [ '???', 'field' ],
	[ '[[/template/common/form/email]]', 'email' ], [ '[[/x/y/Ihre Größe]]', 'ihre-groesse' ], [ 'date', 'date-field' ], [ 'Date', 'date-field' ], [ 'ID', 'id-field' ],
].forEach( function( fixture ) {
	check( '_deriveName: "'+ fixture[0]+ '" gives "'+ fixture[1]+ '"', named._deriveName( fixture[0], [], reservedNames ) === fixture[1] );
} );
check( '_deriveName: a name that is taken gets -2, then -3', named._deriveName( 'Nachricht', [ 'nachricht' ], reservedNames ) === 'nachricht-2'
	&& named._deriveName( 'Nachricht', [ 'nachricht', 'nachricht-2' ], reservedNames ) === 'nachricht-3' );
check( '_deriveName: a reserved name that is taken as well keeps both suffixes', named._deriveName( 'date', [ 'date-field' ], reservedNames ) === 'date-field-2' );
const long = named._deriveName( 'a'.repeat( 70 ), [], reservedNames );
check( '_deriveName: a long label stays within 64 characters', long.length === 64 && /^[a-z][a-z0-9-]*$/.test( long ) );
check( '...and so do the suffixes of a long one', named._deriveName( 'a'.repeat( 70 ), [ long ], reservedNames ).length === 64
	&& named._deriveName( 'a'.repeat( 70 ), [ long ], reservedNames ).slice( -2 ) === '-2'
	&& named._deriveName( 'date'.repeat( 20 ), [], [ 'date'.repeat( 16 ) ] ).length <= 64 );

/*	The editor: a new field takes its name from its label until the name is
	typed into by hand, and that survives what redraws the editor - adding
	another field, retyping, moving. A field that was loaded is never renamed	*/
const follow = panel( { forms : two, types : allTypes } );
follow.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
const addField = function() { follow.form.querySelectorAll('.nino-admin-btn-secondary')[0].click() };
const nameOf = function( index ) { return follow.rows()[index].querySelector('[data-role="name"]') };
const labelOf = function( index ) { return follow.rows()[index].querySelector('[data-role="label"]') };
const typeIn = function( input, text ) { input.value = text; input.dispatch('input') };

check( 'a new form starts with the one field it always had, whose name is its own', follow.rows().length === 1 && nameOf( 0 ).value === 'name' );
typeIn( labelOf( 0 ), 'Ihre Nachricht' );
check( '...and which a label does not rename', nameOf( 0 ).value === 'name' );

addField();
check( 'Add field draws a second row that already has a name a form can be saved with', follow.rows().length === 2 && nameOf( 1 ).value === 'field' );
typeIn( labelOf( 1 ), 'Straße' );
check( '...which follows what is typed into the label', nameOf( 1 ).value === 'strasse' );

addField();
typeIn( labelOf( 2 ), 'Straße' );
check( 'a name another field has is made unique', nameOf( 2 ).value === 'strasse-2' );
typeIn( labelOf( 2 ), 'date' );
check( '...and a name the form keeps for itself is made another', nameOf( 2 ).value === 'date-field' );

const typeSelect = function( index ) { return follow.rows()[index].querySelector('[data-role="type"]') };
typeSelect( 1 ).value = 'textarea';
typeSelect( 1 ).dispatch('change');
typeIn( labelOf( 1 ), 'Größe' );
check( 'it goes on following after the field is retyped, which draws the editor again', follow.rows().length === 3 && nameOf( 1 ).value === 'groesse'
	&& typeSelect( 1 ).value === 'textarea' );

follow.rows()[1].querySelector('[data-role="up"]').click();
check( '...and after it is moved, which draws it again in another place', nameOf( 0 ).value === 'groesse' && nameOf( 1 ).value === 'name' );
typeIn( labelOf( 0 ), 'Café' );
typeIn( labelOf( 1 ), 'Zeit' );
check( '...it still follows, and the field that had its own name still does not', nameOf( 0 ).value === 'cafe' && nameOf( 1 ).value === 'name' );

// Typing into a name is the end of it for that field - and only for that one
const hand = follow.rows().map( function( row ) { return row.querySelector('[data-role="name"]').value } );
nameOf( 2 ).value = 'mine';
nameOf( 2 ).dispatch('input');
typeIn( labelOf( 2 ), 'Something else' );
check( 'a name that was typed into by hand stays what it was', nameOf( 2 ).value === 'mine' );
addField();
typeIn( labelOf( 2 ), 'Still not' );
check( '...through the next redraw as well', nameOf( 2 ).value === 'mine' && hand.length === 3 );

// Loaded fields: the name that is saved is never rewritten
const loaded = panel( { forms : two, types : allTypes } );
loaded.list.querySelectorAll('[data-form] button')[0].click();
loaded.rows()[0].querySelector('[data-role="label"]').value = 'Totally different';
loaded.rows()[0].querySelector('[data-role="label"]').dispatch('input');
check( 'a field that was loaded is never renamed by its label', loaded.rows()[0].querySelector('[data-role="name"]').value === 'email' );
loaded.panel._collect();
check( '...nor after the editor is read back and drawn again', loaded.panel._editing.fields[0].auto === false && ( loaded.panel._renderForm(), loaded.rows()[0].querySelector('[data-role="name"]').value === 'email' ) );

// What is posted is the form, not how a name came about
const posting = panel( { forms : two, types : allTypes } );
posting.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
posting.form.querySelectorAll('.nino-admin-btn-secondary')[0].click();
posting.form.querySelector('[data-about="key"]').value = 'fresh';
posting.panel._submit();
const posted = JSON.parse( posting.calls.filter( function( call ) { return call.action === 'forms/save' } )[0].data ).form;
check( 'saving posts the fields without the marker that says a name follows its label', posted.fields.length === 2 && posted.fields.every( function( field ) { return Object.prototype.hasOwnProperty.call( field, 'auto' ) === false } )
	&& posted.fields[1].name === 'field' );


// --- Sorting -------------------------------------------------------------------

const sorting = panel( { forms : [ { key : 'q', name : 'Q', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0, fields : [
	{ name : 'one', label : 'One', type : 'text', required : false, options : [] },
	{ name : 'two', label : 'Two', type : 'radio', required : true, options : [ 'A', 'B' ] },
	{ name : 'three', label : 'Three', type : 'text', required : false, options : [] },
] } ], types : allTypes } );
sorting.list.querySelectorAll('[data-form] button')[0].click();
const orderOf = function() { return sorting.rows().map( function( row ) { return row.querySelector('[data-role="name"]').value } ).join() };
const stepOf = function( index, which ) { return sorting.rows()[index].querySelector('[data-role="'+ which+ '"]') };

check( 'every row has a button up and a button down, with a title and a label of their own', sorting.rows().every( function( row ) {
	const up = row.querySelector('[data-role="up"]'), down = row.querySelector('[data-role="down"]');
	return up.type === 'button' && down.type === 'button' && up.title === '/_admin/common/label/moveup' && up.getAttribute('aria-label') === '/_admin/common/label/moveup'
		&& down.title === '/_admin/common/label/movedown' && down.getAttribute('aria-label') === '/_admin/common/label/movedown';
} ) );
check( '...side by side in the workbench\'s own pair of move buttons', sorting.rows().every( function( row ) {
	return row.querySelectorAll('.nino-admin-move').length === 1 && row.querySelectorAll('.nino-admin-move')[0].children.length === 2;
} ) );
check( '...the first cannot go up and the last cannot go down', stepOf( 0, 'up' ).disabled === true && stepOf( 0, 'down' ).disabled === false
	&& stepOf( 2, 'down' ).disabled === true && stepOf( 2, 'up' ).disabled === false && stepOf( 1, 'up' ).disabled === false && stepOf( 1, 'down' ).disabled === false );

stepOf( 0, 'down' ).click();
check( 'down swaps a field with the one below it, and what was typed in the editor travels with it', orderOf() === 'two,one,three' );
check( '...and the focus is on the button that was pressed, on the field in its new place', element.focused === stepOf( 1, 'down' ) );

stepOf( 1, 'down' ).click();
check( 'a field that reaches the end has its down button off, so the focus goes to its up button', orderOf() === 'two,three,one' && stepOf( 2, 'down' ).disabled === true && element.focused === stepOf( 2, 'up' ) );

stepOf( 2, 'up' ).click();
stepOf( 1, 'up' ).click();
check( 'up swaps the other way, and a field that reaches the top moves the focus to its down button', orderOf() === 'one,two,three' && element.focused === stepOf( 0, 'down' ) );

sorting.rows()[0].querySelector('[data-role="label"]').value = 'Typed before moving';
typeOption( sorting.rows()[1], 0, 'X' );
typeOption( sorting.rows()[1], 1, 'Y' );
addOption( sorting.rows()[1] );
typeOption( sorting.rows()[1], 2, 'Z' );
stepOf( 1, 'up' ).click();
check( 'the options and the labels typed before a move are in the field that moved', orderOf() === 'two,one,three'
	&& optionTexts( sorting.rows()[0] ).join() === 'X,Y,Z' && optionStore( sorting.rows()[0] ) === '["X","Y","Z"]'
	&& sorting.rows()[1].querySelector('[data-role="label"]').value === 'Typed before moving' );


/*	What a save replaces is the key the editor was opened with. The key box is
	read back into the working copy on every redraw - a move, an added field -
	so a typed key must not become the one a save is told it replaces: a taken
	key would overwrite that form, a rename would become a second form	*/
const replaced = function( run ) {
	const probe = panel( { forms : two, types : allTypes } );
	run( probe );
	probe.panel._submit();
	return JSON.parse( probe.calls.filter( function( call ) { return call.action === 'forms/save' } )[0].data );
};
const taken = replaced( function( probe ) {
	probe.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
	probe.form.querySelector('[data-about="key"]').value = 'quote';
	probe.form.querySelectorAll('.nino-admin-btn-secondary')[0].click();
	probe.form.querySelectorAll('.nino-admin-btn-secondary')[0].click();
	probe.rows()[1].querySelector('[data-role="up"]').click();
} );
check( 'a new form with a key that is taken, after its fields were moved, is still saved as new - the server then refuses it', taken.key === '' && taken.form.key === 'quote' );
const renamed = replaced( function( probe ) {
	probe.list.querySelectorAll('[data-form] button')[0].click();
	probe.form.querySelector('[data-about="key"]').value = 'renamed';
	probe.form.querySelectorAll('.nino-admin-btn-secondary')[0].click();
	probe.rows()[0].querySelector('[data-role="down"]').click();
} );
check( '...and a renamed form that was moved afterwards still replaces the key it was opened with', renamed.key === 'contact' && renamed.form.key === 'renamed' );


// --- Types, options and templates ----------------------------------------------

const worded = panel( { forms : two, types : allTypes, text : function( key ) { return key.indexOf('/_admin/forms/type/') === 0 && key !== '/_admin/forms/type/tel' ? 'Type '+ key.slice( 19 ) : '' } } );
worded.list.querySelectorAll('[data-form] button')[0].click();
const typeOptions = worded.rows()[0].querySelector('[data-role="type"]').children;
check( 'the type list is in the words of the workbench, with the type itself as the value', typeOptions.length === 10
	&& typeOptions[0].textContent === 'Type text' && typeOptions[0].value === 'text' && typeOptions[8].textContent === 'Type radio' && typeOptions[9].value === 'date' );
check( '...and a type nobody has a word for is shown as itself', typeOptions[2].value === 'tel' && typeOptions[2].textContent === 'tel' );

check( 'the list of options is there for a radio group and a select, and for no other type - and never a box to type them into', ( function() {
	const kinds = {};
	allTypes.forEach( function( kind ) {
		worded.panel._editing.fields = [ { name : 'x', label : '', type : kind, required : false, options : [ 'A' ] } ];
		worded.panel._renderForm();
		kinds[kind] = worded.rows()[0].querySelector('[data-role="options"]') !== null
			&& worded.rows()[0].querySelector('[data-role="options"]') === worded.rows()[0].querySelector('.nino-admin-stringlist')
			&& worded.rows()[0].querySelectorAll('textarea').length === 0;
	} );
	return Object.keys( kinds ).filter( function( kind ) { return kinds[kind] } ).join() === 'select,radio';
} )() );

const mailed = panel( { forms : [ Object.assign( {}, two[0], { ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-gone' } ) ], types : allTypes, templates : [ '/templates/mail-owner', '/templates/mail-user' ] } );
mailed.list.querySelectorAll('[data-form] button')[0].click();
const ownerSelect = mailed.form.querySelector('[data-about="ownertpl"]');
const userSelect = mailed.form.querySelector('[data-about="usertpl"]');
check( 'each mail template is a list of what the project has', ownerSelect.tagName === 'SELECT' && userSelect.tagName === 'SELECT'
	&& ownerSelect.children.map( function( option ) { return option.value } ).join() === '/templates/mail-owner,/templates/mail-user' && ownerSelect.value === '/templates/mail-owner' );
check( '...and what the form names is in it even where the project has no such file, so that the list does not switch to another one by itself',
	userSelect.value === '/templates/mail-gone' && userSelect.children[0].value === '/templates/mail-gone' && userSelect.children.length === 3 );
mailed.panel._collect();
check( '...and is what is read back', mailed.panel._editing.ownerTemplate === '/templates/mail-owner' && mailed.panel._editing.userTemplate === '/templates/mail-gone' );
check( 'only the second one carries the hint', userSelect.parent.querySelectorAll('small').length === 1 && ownerSelect.parent.querySelectorAll('small').length === 0 );
const hinted = panel( { forms : two, types : allTypes, text : function( key ) { return key === '/_admin/forms/hint/templates' ? 'Shown by %s.' : key } } );
hinted.list.querySelectorAll('[data-form] button')[0].click();
check( '...which names the placeholder that carries every field into a mail, put in by the script', hinted.form.querySelector('[data-about="usertpl"]').parent.querySelectorAll('small')[0].textContent === 'Shown by [[fields]].' );


// --- The options: a list of rows ------------------------------------------------

/*	The options of a select and of radio buttons are the workbench's list of
	texts: a row for each, moved and removed one by one. It keeps its rows
	itself and tells onChange what it holds, and the working copy follows -
	which is where a redraw, a move, a retype and a save start from	*/
const plan = { key : 'q', name : 'Q', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0, fields : [
	{ name : 'plan', label : 'Plan', type : 'radio', required : true, options : [ 'Small', 'Large' ] },
	{ name : 'note', label : 'Note', type : 'text', required : false, options : [] },
] };
const savedForms = function( probe ) { return probe.calls.filter( function( call ) { return call.action === 'forms/save' } ).map( function( call ) { return JSON.parse( call.data ) } ) };
const retype = function( probe, index, kind ) {
	const select = probe.rows()[index].querySelector('[data-role="type"]');
	select.value = kind;
	select.dispatch('change');
};
const editPlan = function() {
	const probe = panel( { forms : [ plan ], types : allTypes } );
	probe.list.querySelectorAll('[data-form] button')[0].click();
	return probe;
};

const choice = editPlan();
const planRow = function() { return choice.rows()[0] };

check( 'a field of radio buttons has a list of options in its row, a row of it for each option, and no box to type them into',
	planRow().querySelectorAll('.nino-admin-stringlist').length === 1 && planRow().querySelectorAll('textarea').length === 0
	&& optionTexts( planRow() ).join() === 'Small,Large' && choice.rows()[1].querySelectorAll('.nino-admin-stringlist').length === 0 );
check( '...handed its key, its label, the options of the field and the panel\'s word for each of its six controls',
	choice.lists.length === 1 && choice.lists[0].key === 'options' && choice.lists[0].label === '/_admin/forms/label/options' && choice.lists[0].value.join() === 'Small,Large'
	&& Object.keys( choice.lists[0].text ).join() === 'add,remove,up,down,empty,item'
	&& Object.keys( choice.lists[0].text ).every( function( name ) { return choice.lists[0].text[name] === '/_admin/forms/options/'+ name } ) );
check( '...as wide as the row, and found by the role the editor reads it by',
	planRow().querySelector('[data-role="options"]') === planRow().querySelector('.nino-admin-stringlist')
	&& planRow().querySelector('[data-role="options"]').matchesClass('nino-admin-field-wide') === true );
check( '...and its hidden input carries the options as json, which is what the shell\'s check for unsaved input reads of them',
	planRow().querySelector('[data-field="options"]').type === 'hidden' && optionStore( planRow() ) === '["Small","Large"]' );

typeOption( planRow(), 0, 'Tiny' );
check( 'a text typed into a row is in the working copy at once, before anything has read the editor back',
	choice.panel._editing.fields[0].options.join() === 'Tiny,Large' && optionStore( planRow() ) === '["Tiny","Large"]' );
addOption( planRow() );
check( '...a row that was added is no option until it holds a text',
	optionTexts( planRow() ).length === 3 && choice.panel._editing.fields[0].options.join() === 'Tiny,Large' );
typeOption( planRow(), 2, 'Huge' );
optionButtons( planRow(), 0 )[2].click();
check( '...a row that was removed is gone from it', choice.panel._editing.fields[0].options.join() === 'Large,Huge' && optionTexts( planRow() ).join() === 'Large,Huge' );
optionButtons( planRow(), 1 )[0].click();
check( '...and so is the order the rows were moved to', choice.panel._editing.fields[0].options.join() === 'Huge,Large' && optionTexts( planRow() ).join() === 'Huge,Large' );

choice.form.querySelectorAll('.nino-admin-btn-secondary')[0].click();
check( 'a field that is added afterwards leaves them as they are', choice.rows().length === 3 && optionTexts( planRow() ).join() === 'Huge,Large' && choice.panel._editing.fields[0].options.join() === 'Huge,Large' );

const blanks = editPlan();
addOption( blanks.rows()[0] );
addOption( blanks.rows()[0] );
typeOption( blanks.rows()[0], 3, '   ' );
addOption( blanks.rows()[0] );
typeOption( blanks.rows()[0], 4, 'Medium' );
typeOption( blanks.rows()[0], 0, '' );
blanks.panel._submit();
check( 'options with empty rows among them arrive without them - a row nobody typed into, one of spaces only and one that was emptied',
	savedForms( blanks ).length === 1 && savedForms( blanks )[0].form.fields[0].options.join('|') === 'Large|Medium' );

const retyped = editPlan();
typeOption( retyped.rows()[0], 1, 'Medium' );
retype( retyped, 0, 'select' );
check( 'a field of radio buttons retyped to a select keeps its options - in the list, and in the working copy',
	retyped.rows()[0].querySelector('[data-role="type"]').value === 'select' && retyped.panel._editing.fields[0].type === 'select'
	&& optionTexts( retyped.rows()[0] ).join() === 'Small,Medium' && retyped.panel._editing.fields[0].options.join() === 'Small,Medium' );
retype( retyped, 0, 'radio' );
check( '...and back to radio buttons', retyped.panel._editing.fields[0].type === 'radio' && optionTexts( retyped.rows()[0] ).join() === 'Small,Medium' );
retype( retyped, 0, 'text' );
check( 'retyped to text it has no list, and no options - not in the working copy either',
	retyped.rows()[0].querySelector('[data-role="options"]') === null && retyped.rows()[0].querySelectorAll('.nino-admin-stringlist').length === 0
	&& retyped.panel._editing.fields[0].type === 'text' && retyped.panel._editing.fields[0].options.length === 0 );
retype( retyped, 0, 'select' );
check( '...a select it is retyped to afterwards starts with a list that says it has no option yet',
	optionTexts( retyped.rows()[0] ).length === 0 && retyped.rows()[0].querySelector('.nino-admin-stringlist-empty').textContent === '/_admin/forms/options/empty' );
retype( retyped, 0, 'text' );
retyped.panel._submit();
check( 'a field retyped to text is saved without options', savedForms( retyped ).length === 1 && savedForms( retyped )[0].form.fields[0].type === 'text' && savedForms( retyped )[0].form.fields[0].options.length === 0 );

// A definition written by hand can carry options on a field that does not use them
const stray = panel( { forms : [ Object.assign( {}, plan, { fields : [ { name : 'note', label : 'Note', type : 'text', required : false, options : [ 'Left over' ] } ] } ) ], types : allTypes } );
stray.list.querySelectorAll('[data-form] button')[0].click();
retype( stray, 0, 'select' );
check( 'the options a text field was stored with are not given to the select it is retyped to',
	stray.rows()[0].querySelector('[data-role="options"]') !== null && optionTexts( stray.rows()[0] ).length === 0 && stray.panel._editing.fields[0].options.length === 0 );

/*	The working copy is written by the place of the field in it, not through
	the object the row was drawn from: reading the editor back puts new objects
	into it. A save that is refused leaves the editor as it is - and what is
	done to the list after that is what the next save has to send	*/
const again = { forms : [ Object.assign( {}, plan, { fields : [ { name : 'plan', label : 'Plan', type : 'radio', required : true, options : [] } ] } ) ], types : allTypes,
	text : said, refuse : refusal( { 0 : 'options' }, { 0 : 'options' }, [] ) };
const second = panel( again );
second.list.querySelectorAll('[data-form] button')[0].click();
second.panel._submit();
delete again.refuse;
addOption( second.rows()[0] );
typeOption( second.rows()[0], 0, 'Yes' );
second.panel._submit();
check( 'what is done to the list after a refused save is what the next save sends',
	savedForms( second ).length === 2 && savedForms( second )[0].form.fields[0].options.length === 0 && savedForms( second )[1].form.fields[0].options.join() === 'Yes' );


// --- Where a refused save went wrong -------------------------------------------

const refused = panel( { forms : [ { key : 'q', name : 'Q', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0, fields : [
	{ name : 'one', label : 'One', type : 'text', required : false, options : [] },
	{ name : 'one', label : 'Two', type : 'text', required : false, options : [] },
	{ name : 'three', label : 'Three', type : 'radio', required : false, options : [] },
] } ], types : allTypes, templates : [ '/templates/mail-owner', '/templates/mail-user' ],
	text : said,
	refuse : refusal( { 1 : 'duplicate', 2 : 'options', 9 : 'field' }, { 1 : 'name', 2 : 'options', 9 : 'name' }, { to : 'to', ownertpl : 'ownertpl', fields : 'fields' } ) } );
refused.list.querySelectorAll('[data-form] button')[0].click();
refused.panel._submit();

const marks = function() { return refused.form.querySelectorAll('[aria-invalid="true"]') };
check( 'a refused save marks the control of every field it names - the name, or the list of options - and the controls of the form itself',
	marks().length === 4
	&& refused.rows()[1].querySelector('[data-role="name"]').getAttribute('aria-invalid') === 'true'
	&& refused.rows()[2].querySelector('.nino-admin-stringlist').getAttribute('aria-invalid') === 'true'
	&& refused.form.querySelector('[data-about="to"]').getAttribute('aria-invalid') === 'true'
	&& refused.form.querySelector('[data-about="ownertpl"]').getAttribute('aria-invalid') === 'true'
	&& refused.rows()[0].querySelectorAll('[aria-invalid]').length === 0 );
check( '...with the sentence under it, which the control points to', refused.form.querySelectorAll('.nino-admin-field-error').length === 4 && marks().every( function( control ) {
	const sentence = refused.form.querySelectorAll('.nino-admin-field-error').filter( function( error ) { return error.id === control.getAttribute('aria-describedby') } )[0];
	// Right after the label the control is in - the list of options has none, it is a wrapper itself
	const under = control.dataset.role === 'options' ? control : control.parentNode;
	return sentence !== undefined && sentence.parent === under.parent && under.parent.children[ under.parent.children.indexOf( under ) + 1 ] === sentence;
} ) );
check( '...the sentence is after the label of the control, not in it', refused.rows()[1].querySelector('[data-role="name"]').parentNode.querySelectorAll('.nino-admin-field-error').length === 0
	&& refused.rows()[1].children.some( function( child ) { return child.textContent === 'Taken.' } ) );
check( '...and after the list of options, not in it', ( function() {
	const wrapper = refused.rows()[2].querySelector('[data-role="options"]');
	return wrapper.querySelectorAll('.nino-admin-field-error').length === 0
		&& refused.rows()[2].children[ refused.rows()[2].children.indexOf( wrapper ) + 1 ].textContent === 'Needs one.';
} )() );
check( '...and the first one the person meets is focused: the form\'s own controls come before the fields', element.focused === refused.form.querySelector('[data-about="to"]') );
check( 'the summary at the top of the editor says what the server said, and what has no control to mark - a form without fields, a field that is no more there',
	refused.form.querySelector('#forms-summary').hidden === false
	&& refused.form.querySelector('#forms-summary').textContent === 'Not saved. Needs a field. Where?' );
check( '...and the editor is still the editor, with nothing lost', refused.hidden( refused.form ) === false && refused.panel._editing !== null && refused.rows().length === 3 );

// The next save starts from a clean editor
refused.panel._submit();
check( 'a second save takes the marks of the first off before it marks again', refused.form.querySelectorAll('.nino-admin-field-error').length === 4 && marks().length === 4
	&& refused.form.querySelectorAll('#forms-summary').length === 1 );
refused.panel._unmark();
check( 'and they can be taken off altogether', refused.form.querySelectorAll('.nino-admin-field-error').length === 0 && marks().length === 0
	&& refused.form.querySelector('#forms-summary').hidden === true && refused.form.querySelector('#forms-summary').textContent === '' );

// A wrapper takes no focus, so the focus goes into the list - and what the refusal names may be nothing else
const listed = panel( { forms : [ { key : 'q', name : 'Q', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0, fields : [
	{ name : 'one', label : 'One', type : 'text', required : false, options : [] },
	{ name : 'three', label : 'Three', type : 'radio', required : false, options : [] },
] } ], types : allTypes, templates : [ '/templates/mail-owner', '/templates/mail-user' ],
	text : said, refuse : refusal( { 1 : 'options' }, { 1 : 'options' }, [] ) } );
listed.list.querySelectorAll('[data-form] button')[0].click();
listed.panel._submit();
check( 'a refusal that names only the list of options puts the focus on the button of the list while it has no row', element.focused === listed.rows()[1].querySelector('.nino-admin-stringlist-add') );

/*	The wrapper of the list is a div: it takes no focus and has no role, so what
	it is described by is not read where the focus is. The element that has the
	focus is described by the sentence as well - and by nothing once the marks
	are off, so that no link outlives the sentence it points to	*/
const listWrapper = function() { return listed.rows()[1].querySelector('[data-role="options"]') };
const listSentence = function() { return listed.rows()[1].querySelectorAll('.nino-admin-field-error')[0] || null };
const onButton = element.focused;
check( '...and that button is described by the sentence, as the wrapper of the list is',
	listSentence() !== null && listSentence().id !== '' && listSentence().textContent === 'Needs one.'
	&& onButton.getAttribute('aria-describedby') === listSentence().id && listWrapper().getAttribute('aria-describedby') === listSentence().id );
check( '...the list stays the one that is invalid, not the button', listWrapper().getAttribute('aria-invalid') === 'true' && onButton.getAttribute('aria-invalid') === null );
addOption( listed.rows()[1] );
listed.panel._submit();
check( '...and on the first text of the list once it has one', element.focused === listed.rows()[1].querySelector('.nino-admin-stringlist-input') );
const onText = element.focused;
check( '...which is described by the sentence now, and the button, which no longer has the focus, is not',
	listSentence() !== null && onText.getAttribute('aria-describedby') === listSentence().id && onText.getAttribute('aria-invalid') === null
	&& listWrapper().getAttribute('aria-describedby') === listSentence().id && onButton.getAttribute('aria-describedby') === null );
listed.panel._unmark();
check( 'taking the marks off takes the description off the element that had the focus as well: the sentence is gone, and nothing points to one',
	onText.getAttribute('aria-describedby') === null && listWrapper().getAttribute('aria-describedby') === null
	&& listed.form.querySelectorAll('.nino-admin-field-error').length === 0 && listed.form.querySelectorAll('[aria-describedby]').length === 0 );

// The marks come off when the next save starts, so one that goes through leaves no description behind
const mendable = { forms : [ Object.assign( {}, plan, { fields : [ { name : 'plan', label : 'Plan', type : 'radio', required : true, options : [] } ] } ) ], types : allTypes,
	text : said, refuse : refusal( { 0 : 'options' }, { 0 : 'options' }, [] ) };
const mended = panel( mendable );
mended.list.querySelectorAll('[data-form] button')[0].click();
mended.panel._submit();
const focusedAfter = element.focused;
const mendedSentence = mended.rows()[0].querySelectorAll('.nino-admin-field-error')[0] || null;
check( 'a refused save names its sentence on the element that has the focus - the button of a list with no row, here',
	focusedAfter === mended.rows()[0].querySelector('.nino-admin-stringlist-add') && mendedSentence !== null && mendedSentence.id !== ''
	&& focusedAfter.getAttribute('aria-describedby') === mendedSentence.id );
delete mendable.refuse;
addOption( mended.rows()[0] );
typeOption( mended.rows()[0], 0, 'Yes' );
mended.panel._submit();
check( '...and the next save, which goes through, has taken that off the element, and the sentence with it',
	savedForms( mended ).length === 2 && mended.panel._editing === null && focusedAfter.getAttribute('aria-describedby') === null
	&& mended.form.querySelectorAll('.nino-admin-field-error').length === 0 && mended.form.querySelectorAll('[aria-describedby]').length === 0 );

// A refusal that names nothing is only a sentence
const plainly = panel( { forms : two, types : allTypes, text : said, refuse : { error : 'the form needs a key of its own and at least one field', code : 'forms_invalid' } } );
plainly.list.querySelectorAll('[data-form] button')[0].click();
plainly.panel._submit();
check( 'a refusal that names no field marks nothing, and still says its sentence inside the form', plainly.form.querySelectorAll('[aria-invalid]').length === 0
	&& plainly.form.querySelector('#forms-summary').textContent === 'The form needs a key of its own.' );

// A key that is taken has no field: it is marked at the key, by the word the code carries for it
const duplicated = panel( { forms : two, types : allTypes, text : said, refuse : { error : 'another form has that key', code : 'forms_duplicate', params : [ [], [], { key : 'keytaken' } ] } } );
duplicated.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
duplicated.panel._submit();
check( 'a key that is taken is marked at the key control, with the word the code carries for it, and the summary says the sentence of the code',
	duplicated.form.querySelector('[data-about="key"]').getAttribute('aria-invalid') === 'true'
	&& duplicated.form.querySelectorAll('.nino-admin-field-error').length === 1 && duplicated.form.querySelectorAll('.nino-admin-field-error')[0].textContent === 'This key is taken.'
	&& duplicated.form.querySelector('#forms-summary').textContent === 'Another form has that key.' && element.focused === duplicated.form.querySelector('[data-about="key"]') );


// A failure that is not a refusal of the form has no control to mark
const failing = panel( { forms : two, types : allTypes } );
failing.list.querySelectorAll('[data-form] button')[0].click();
failing.status( 500 );
failing.panel._submit();
check( 'a failure that names no control - a server error, a refused permission - is said inside the editor as well',
	failing.form.querySelector('#forms-summary').hidden === false && failing.form.querySelector('#forms-summary').textContent === '(500) Refused'
	&& failing.form.querySelectorAll('[aria-invalid]').length === 0 );
failing.panel._unmark();
check( '...and is taken off with the rest', failing.form.querySelector('#forms-summary').hidden === true );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
