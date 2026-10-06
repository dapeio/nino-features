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
 *												keeps what the form names, the options box of a radio
 *												group, and where a refused save is marked.
 *
 *												No jsdom, no dependency: the same element stand-in the
 *												other feature tests build, with just enough of
 *												Nino.adminUi for the panel to draw itself - the
 *												action bar carries the class string the kernel's own
 *												listActions() gives it - so this runs with nothing but
 *												node.
 *
 *	Usage: node features/Forms/tests/forms-js-smoke.js
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

/**
 *	The panel, drawn into the two panes the shell hands it
 *
 *	@param		{Object}	options		{ forms } - what forms/list answers with;
 *																hold: true keeps the answer to forms/settings
 *																back until release() is called, status: the
 *																status that answer carries, api and status: stand in for
 *																Nino.adminUi.api and Nino.adminUi.status (a newer workbench),
 *																dirty: for the shell's Nino.admin.dirty registry,
 *																templates: what forms/list names as mail templates,
 *																types: the field types it names, text: what a fill key
 *																reads as (the key itself where nothing is said),
 *																refuse: the body forms/save answers with, status 400
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

	const calls = [];
	const uris = [];
	let pending = null;
	let status = 200;

	const dc = {
		createElement		: function( tag ) { return element( tag ) },
		getElementById	: function( id ) { return descendants( body ).filter( function( n ) { return n.id === id } )[0] || null },
	};

	const answer = function( callback, payload ) {
		if( status !== 200 )
			return callback( { status : status, responseJSON : { error : 'Refused' } } );
		const values = JSON.parse( payload.data );
		callback( { status : 200, responseJSON : { retention : values.retention, store : values.store } } );
	};

	const Nino = {
		content	: { getText : function( key ) { return typeof options.text === 'function' ? options.text( key ) : key } },
		events	: { bindCallback : function() {} },
		// Nino.http.sendRequest() calls back with the xhr, and the panel reads
		// .status and .responseJSON off it - so that is what stands in for one
		http		: { sendRequest : function( uri, method, callback, payload ) {
			calls.push( payload );
			uris.push( uri );
			if( payload.action === 'forms/list' )
				return callback( { status : 200, responseJSON : {
					forms			: options.forms || [],
					types			: options.types || [ 'text', 'email', 'select' ],
					templates	: options.templates || [],
					reserved	: [ 'form', 'location', '_csrf', '_t', 'id', 'date', 'ip' ],
					default		: false,
					endpoint	: true,
					retention	: 3,
					store			: true,
				} } );
			if( payload.action === 'forms/save' && options.refuse )
				return callback( { status : 400, responseJSON : options.refuse } );
			if( payload.action === 'forms/save' && status === 200 )
				return callback( { status : 200, responseJSON : { form : JSON.parse( payload.data ).form } } );
			if( options.hold === true )
				return pending = function() { answer( callback, payload ) };
			answer( callback, payload );
		} },
		// The kernel's own pieces in miniature: listActions() is the fixed bar
		// every list screen ends in, with the class string it really carries,
		// and the shared fields hand their key back as data-key
		adminUi	: {
			emptyState	: function( text ) { const el = element('p'); el.className = 'nino-admin-empty'; el.textContent = text; return el },
			listActions	: function( buttons ) { const el = element('div'); el.className = 'nino-admin-actionbar nino-admin-list-actions'; buttons.forEach( function( b ) { el.appendChild( b ) } ); return el },
			contextBar	: function( back ) { const el = element('div'); el.className = 'nino-admin-contextbar'; el.appendChild( back ); return el },
			numberField	: function( config ) {
				const el = element('label');
				el.className = 'nino-admin-field';
				const input = element('input');
				input.type = 'number';
				input.value = config.value;
				input.dataset.key = config.key;
				el.appendChild( input );
				return el;
			},
			selectField	: function( config ) {
				const el = element('label');
				el.className = 'nino-admin-field';
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
				el.appendChild( select );
				return el;
			},
			switchField	: function( config ) {
				const el = element('label');
				el.className = 'nino-admin-switch';
				const input = element('input');
				input.type = 'checkbox';
				input.checked = config.checked === true;
				input.dataset.key = config.key;
				el.appendChild( input );
				return el;
			},
		},
	};

	// Where the real shell defines it (script.js): the back link's row
	Nino.admin = { formToolbar : function( back ) { return Nino.adminUi.contextBar( back ) } };

	if( options.api )
		Nino.adminUi.api = options.api;
	if( options.status )
		Nino.adminUi.status = options.status;
	if( options.dirty )
		Nino.admin.dirty = options.dirty;

	const sandbox = { console : console, document : dc, Nino : Nino, window : { Nino : Nino, confirm : function() { return true } } };
	sandbox.window.window = sandbox.window;
	sandbox.document.documentElement = element('html');
	sandbox.document.body = body;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	Nino.admin.forms.init();

	// The settings form is the one that is a form on the list screen
	const settings = function() { return list.querySelectorAll('.forms-settings')[0] || null };

	return {
		list		: list,
		form		: form,
		calls		: calls,
		uris		: uris,
		panel		: Nino.admin.forms,
		settings: settings,
		save		: function() { return settings().querySelector('button') },
		msg			: function() { return settings().querySelector('#forms-settings-msg') },
		field		: function( key ) { return settings().querySelector('[data-key="'+ key+ '"]') },
		bars		: function( mount ) { return mount.querySelectorAll('.nino-admin-actionbar').length },
		rows		: function() { return form.querySelectorAll('.forms-field') },
		hidden	: function( mount ) { return mount.classList.contains('admin-hidden') },
		status	: function( value ) { status = value },
		release	: function() { const run = pending; pending = null; run() },
	};
}

const two = [
	{ key : 'contact', name : 'Contact', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', fields : [ { name : 'email', label : '', type : 'email', required : true, options : [] } ], entries : 4 },
	{ key : 'quote', name : 'Quote', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', fields : [ { name : 'budget', label : '', type : 'text', required : false, options : [] } ], entries : 0 },
];


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
check( '...with its own submit button and its own status line inside it',
	open.save().type === 'submit' && open.save().textContent === '/_admin/common/label/save'
	&& open.msg() !== null && open.msg().getAttribute('role') === 'status' );
check( '...and it comes after the list\'s bar in the page, where it always did',
	open.list.children.indexOf( open.settings() ) > open.list.children.indexOf( open.list.querySelectorAll('.nino-admin-list-actions')[0] ) );


// --- Saving the settings -------------------------------------------------------

const save = panel( { forms : two, hold : true } );

save.field('retention').value = '6';
save.field('store').checked = false;
save.settings().dispatch('submit');

check( 'saving posts forms/settings with the retention as a number and the switch as a boolean',
	save.calls.length === 2 && save.calls[1].action === 'forms/settings'
	&& save.calls[1].data === JSON.stringify( { retention : 6, store : false } ) );
check( '...keeps the card\'s button off while the request runs and says so on the status line',
	save.save().disabled === true && save.msg().textContent === '/_admin/common/msg/saving' );

save.release();

check( '...and writes the saved text into the card, with the button on again',
	save.msg().textContent === '/_admin/common/msg/saved' && save.msg().matchesClass('nino-admin-error') === false
	&& save.save().disabled === false );
check( '...and what the endpoint answered is what the panel keeps', save.panel._retention === 6 && save.panel._store === false );

save.status( 422 );
save.settings().dispatch('submit');
save.release();

check( 'a refused save marks the status line as an error and gives the button back',
	save.msg().matchesClass('nino-admin-error') === true && save.msg().textContent === '(422) Refused'
	&& save.save().disabled === false );

save.status( 200 );
save.settings().dispatch('submit');

check( '...and the next try takes the error off the line again',
	save.msg().matchesClass('nino-admin-error') === false && save.msg().textContent === '/_admin/common/msg/saving' );

save.release();


// --- The editor ----------------------------------------------------------------

const edit = panel( { forms : two } );

edit.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();

check( 'New form opens the editor in place of the list',
	edit.hidden( edit.list ) === true && edit.hidden( edit.form ) === false && edit.panel._editing !== null && edit.panel._editing.key === '' );
check( '...and the editor holds exactly one fixed action bar, its own Save',
	edit.bars( edit.form ) === 1 && edit.form.querySelectorAll('button').filter( function( b ) { return b.type === 'submit' } ).length === 1 );
check( '...and the settings card is not drawn into it', edit.form.querySelectorAll('.forms-settings').length === 0 );


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

/*	Where the shell has a request helper the panel asks it; where it has not it
	posts from the project's own directory - the literal the asset bundle fills
	in, as Nino.dir does not exist before Nino 1.3.2	*/
const bare = panel( { forms : two } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	bare.uris.length === 1 && bare.uris[0] === '[[/nino/dir]]/_admin/' && bare.calls[0].action === 'forms/list' && bare.calls[0].data === '{}' );

const routed = [];
const viaApi = panel( { api : { call : function( action, payload, callback ) {
	routed.push( [ action, payload ] );
	callback( 200, { forms : two, types : [ 'text' ], reserved : [], default : false, endpoint : true, retention : 3, store : true } );
} } } );
check( 'with it the panel asks the helper for \'forms/<action>\' and posts nothing by hand',
	routed.length === 1 && routed[0][0] === 'forms/list' && viaApi.uris.length === 0 && viaApi.list.querySelectorAll('[data-form]').length === 2 );


// --- Saying what happened ------------------------------------------------------

// Nino.adminUi.status() in miniature: what it was told, and which form it follows
const lines = [];
const statusStub = function( el ) {
	const line = { el : el, told : [], bound : null,
		saving : function() { line.told.push('saving') },
		saved : function() { line.told.push('saved') },
		error : function( status, response, key ) { line.told.push( [ 'error', status, response.error, key ].join(' ') ) },
		bind : function( form ) { line.bound = form },
	};
	lines.push( line );
	return line;
};

const lined = panel( { forms : two, status : statusStub } );
const cardLine = lines[lines.length - 1];
check( 'where the shell has a status line the settings card\'s message is one, following the card', cardLine.el === lined.msg() && cardLine.bound === lined.settings() );
lined.settings().dispatch('submit');
check( '...told when the save starts and when it is done, and the panel\'s own sentences are not written beside it',
	cardLine.told.join() === 'saving,saved' && lined.msg().textContent === '' );
lined.status( 422 );
lined.settings().dispatch('submit');
check( '...and a refused save is told as the error it is, with the status, the body and the panel\'s own sentence for it',
	cardLine.told[cardLine.told.length - 1] === 'error 422 Refused /_admin/common/error/save' && lined.save().disabled === false );

const typed = panel( { forms : two, status : statusStub } );
typed.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
const editorLine = lines[lines.length - 1];
check( 'the editor\'s message is one too, following the editor\'s form', editorLine.bound !== null && editorLine.bound.tagName === 'FORM' && typed.form.querySelectorAll('form').length === 1 );

const errors = panel( { forms : two, api : { call : function( action, payload, callback ) {
	if( action === 'forms/list' )
		return callback( 500, { error : 'Down' } );
}, errorText : function( status, response, key ) { return 'told '+ status+ ' '+ response.error+ ' ['+ key+ ']' } } } );
check( 'a list that could not be read says so in the shell\'s words where it has them', errors.list.querySelectorAll('.nino-admin-error')[0].textContent === 'told 500 Down [/_admin/common/error/load]' );
check( '...and in the old "(status) message" where it has not', ( function() {
	const old = panel( { forms : two } );
	old.panel._showError( old.list, 503, { error : 'Busy' } );
	return old.list.querySelectorAll('.nino-admin-error')[0].textContent === '(503) Busy';
} )() );


// --- Unsaved input -------------------------------------------------------------

// The shell's registry in miniature: what the panel registered, and what it
// was asked to take for saved
const registry = { watched : {}, snapshots : [], dirty : false };
registry.watchForm = function( name, getter, save ) { registry.watched[name] = { getter : getter, save : save } };
registry.snapshot = function( name ) { registry.snapshots.push( name ) };
registry.isDirty = function() { return registry.dirty };

const guarded = panel( { forms : two, dirty : registry } );
const watched = registry.watched['forms'] || null;

check( 'where the shell has the registry the panel watches its editor under its own uri', watched !== null && watched.getter() === guarded.form && typeof watched.save === 'function' );
check( '...and what is on screen is taken for saved when the editor opens, and not before', registry.snapshots.length === 0 );

guarded.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
check( '...opened', registry.snapshots.length === 1 && registry.snapshots[0] === 'forms' );

// Another panel and back again: an editor that holds input is left as it is
const mark = element('span');
guarded.form.appendChild( mark );
registry.dirty = true;
guarded.panel.showCurrent();
check( 'coming back to the panel does not draw an editor over what is typed into it', guarded.form.children.indexOf( mark ) !== -1 );
registry.dirty = false;
guarded.panel.showCurrent();
check( '...and an editor that holds nothing is drawn again as before', guarded.form.children.indexOf( mark ) === -1 && guarded.hidden( guarded.form ) === false );

// The shell's Save saves from its own question
guarded.form.querySelector('[data-about="key"]').value = 'Renamed';
let told = [];
watched.save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save posts the form and says it was saved - once, before the list is read again',
	told.length === 1 && told[0] === true && guarded.calls.filter( function( call ) { return call.action === 'forms/save' } ).length === 1
	&& JSON.parse( guarded.calls.filter( function( call ) { return call.action === 'forms/save' } )[0].data ).form.key === 'renamed' );

guarded.status( 422 );
guarded.panel._editing = JSON.parse( JSON.stringify( two[0] ) );
guarded.panel._renderForm();
told = [];
watched.save( function( ok ) { told.push( ok ) } );
check( '...and says it was not when the server refused it', told.length === 1 && told[0] === false );

guarded.panel._editing = null;
told = [];
watched.save( function( ok ) { told.push( ok ) } );
check( '...as it does when there is no editor to save', told.length === 1 && told[0] === false );

// A workbench without the registry draws the editor again as it always did
const plain = panel( { forms : two } );
plain.list.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
plain.form.appendChild( mark );
plain.panel.showCurrent();
check( 'a workbench without the registry draws the editor again as it always did', plain.form.children.indexOf( mark ) === -1 );


// --- A field's name follows its label -------------------------------------------

const allTypes = [ 'text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'date' ];
const reservedNames = [ 'form', 'location', '_csrf', '_t', 'id', 'date', 'ip' ];
const named = panel( { forms : two, types : allTypes } ).panel;

/*	The pure part: what a label becomes. The German umlauts and the sharp s
	are spelled out, any other accent is dropped, and the result is a name the
	engine takes - a letter first, 64 characters at most, never a reserved name
	and never one that is taken	*/
[
	[ 'Straße', 'strasse' ], [ 'Ihre Nachricht', 'ihre-nachricht' ], [ 'Größe', 'groesse' ], [ 'ÄÖÜ', 'aeoeue' ], [ 'Café', 'cafe' ],
	[ '1. Wahl', 'field-1-wahl' ], [ '  E-Mail (privat) ', 'e-mail-privat' ], [ '', 'field' ], [ '???', 'field' ],
	[ '[[/form/label/email]]', 'email' ], [ '[[/x/y/Ihre Größe]]', 'ihre-groesse' ], [ 'date', 'date-field' ], [ 'Date', 'date-field' ], [ 'ID', 'id-field' ],
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
sorting.rows()[1].querySelector('[data-role="options"]').value = 'X\nY\nZ';
stepOf( 1, 'up' ).click();
check( 'the options and the labels typed before a move are in the field that moved', orderOf() === 'two,one,three'
	&& sorting.rows()[0].querySelector('[data-role="options"]').value === 'X\nY\nZ' && sorting.rows()[1].querySelector('[data-role="label"]').value === 'Typed before moving' );


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

check( 'the box for options is there for a radio group and a select, and for no other type', ( function() {
	const kinds = {};
	allTypes.forEach( function( kind ) {
		worded.panel._editing.fields = [ { name : 'x', label : '', type : kind, required : false, options : [ 'A' ] } ];
		worded.panel._renderForm();
		kinds[kind] = worded.rows()[0].querySelector('[data-role="options"]') !== null;
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


// --- Where a refused save went wrong -------------------------------------------

const refused = panel( { forms : [ { key : 'q', name : 'Q', to : '', subject : '', confirm : false, ownerTemplate : '/templates/mail-owner', userTemplate : '/templates/mail-user', entries : 0, fields : [
	{ name : 'one', label : 'One', type : 'text', required : false, options : [] },
	{ name : 'one', label : 'Two', type : 'text', required : false, options : [] },
	{ name : 'three', label : 'Three', type : 'radio', required : false, options : [] },
] } ], types : allTypes, templates : [ '/templates/mail-owner', '/templates/mail-user' ],
	refuse : {
		error : 'Not saved.', fields : { 1 : 'Taken.', 2 : 'Needs one.', 9 : 'Where?' }, controls : { 1 : 'name', 2 : 'options', 9 : 'name' },
		about : { to : 'No address.', ownertpl : 'Gone.', fields : 'Needs a field.' },
	} } );
refused.list.querySelectorAll('[data-form] button')[0].click();
refused.panel._submit();

const marks = function() { return refused.form.querySelectorAll('[aria-invalid="true"]') };
check( 'a refused save marks the control of every field it names - the name, or the box of options - and the controls of the form itself',
	marks().length === 4
	&& refused.rows()[1].querySelector('[data-role="name"]').getAttribute('aria-invalid') === 'true'
	&& refused.rows()[2].querySelector('[data-role="options"]').getAttribute('aria-invalid') === 'true'
	&& refused.form.querySelector('[data-about="to"]').getAttribute('aria-invalid') === 'true'
	&& refused.form.querySelector('[data-about="ownertpl"]').getAttribute('aria-invalid') === 'true'
	&& refused.rows()[0].querySelectorAll('[aria-invalid]').length === 0 );
check( '...with the sentence under it, which the control points to', refused.form.querySelectorAll('.nino-admin-field-error').length === 4 && marks().every( function( control ) {
	const sentence = refused.form.querySelectorAll('.nino-admin-field-error').filter( function( error ) { return error.id === control.getAttribute('aria-describedby') } )[0];
	return sentence !== undefined && sentence.parent === control.parentNode.parent;
} ) );
check( '...the sentence is after the label of the control, not in it', refused.rows()[1].querySelector('[data-role="name"]').parentNode.querySelectorAll('.nino-admin-field-error').length === 0
	&& refused.rows()[1].children.some( function( child ) { return child.textContent === 'Taken.' } ) );
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

// A refusal that names nothing is only a sentence
const plainly = panel( { forms : two, types : allTypes, refuse : { error : 'Another form already has that key.' } } );
plainly.list.querySelectorAll('[data-form] button')[0].click();
plainly.panel._submit();
check( 'a refusal that names no field marks nothing, and still says it inside the form', plainly.form.querySelectorAll('[aria-invalid]').length === 0
	&& plainly.form.querySelector('#forms-summary').textContent === 'Another form already has that key.' );


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
