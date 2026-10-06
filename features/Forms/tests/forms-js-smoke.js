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
		const parts = /^\[data-([a-z]+)(?:="([^"]*)")?\]$/.exec( selector );
		if( parts === null ) return false;
		return Object.prototype.hasOwnProperty.call( node.dataset, parts[1] ) && ( parts[2] === undefined || node.dataset[parts[1]] === parts[2] );
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
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
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

/**
 *	The panel, drawn into the two panes the shell hands it
 *
 *	@param		{Object}	options		{ forms } - what forms/list answers with;
 *																hold: true keeps the answer to forms/settings
 *																back until release() is called, status: the
 *																status that answer carries, api and status: stand in for
 *																Nino.adminUi.api and Nino.adminUi.status (a newer workbench),
 *																dirty: for the shell's Nino.admin.dirty registry
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
		content	: { getText : function( key ) { return key } },
		events	: { bindCallback : function() {} },
		// Nino.http.sendRequest() calls back with the xhr, and the panel reads
		// .status and .responseJSON off it - so that is what stands in for one
		http		: { sendRequest : function( uri, method, callback, payload ) {
			calls.push( payload );
			uris.push( uri );
			if( payload.action === 'forms/list' )
				return callback( { status : 200, responseJSON : {
					forms			: options.forms || [],
					types			: [ 'text', 'email', 'select' ],
					reserved	: [ 'form', 'location' ],
					default		: false,
					endpoint	: true,
					retention	: 3,
					store			: true,
				} } );
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


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
