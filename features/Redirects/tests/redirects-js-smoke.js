/**
 *	Nino
 *	redirects-js-smoke.js	What the panel's admin.js does over a dom stand-in:
 *													which of the two screens is on, that the strip
 *													between them goes into the pane's head beside the
 *													panel's name and a redraw puts it back there in
 *													place of the one before, that where there is no head
 *													it travels with the screen that is on so the way back
 *													is always on screen, that the rules table and the
 *													probe are gone while the addresses are up, that a
 *													message line exists on whichever screen is on, and
 *													that making a rule out of an address takes the
 *													operator back to the rules with the address already
 *													in the editor - and that a target can be picked from
 *													the pages of the site, that a rule whose target
 *													leads nowhere carries a flag in the table, and that
 *													what a save warned about is on the message line
 *													while the editor is closed.
 *
 *													No jsdom, no dependency: the same element stand-in
 *													the other feature tests build, with just enough of
 *													Nino.adminUi for the panel to draw itself, so this
 *													runs with nothing but node.
 *
 *	Usage: node features/Redirects/tests/redirects-js-smoke.js
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
		placeholder			: '',
		disabled				: false,
		checked					: false,
		listeners				: {},
		_text						: '',
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		addEventListener: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		click						: function() { ( this.listeners['click'] || [] ).forEach( function( fn ) { fn() } ) },
		change					: function() { ( this.listeners['change'] || [] ).forEach( function( fn ) { fn() } ) },
		matchesClass		: function( name ) { return ( this.attributes['class'] || '' ).split( /\s+/ ).indexOf( name ) !== -1 },
		querySelector		: function( selector ) { return this.querySelectorAll( selector )[0] || null },
		querySelectorAll: function( selector ) {
			const wanted = selector.split( ',' ).map( function( s ) { return s.trim() } );
			return descendants( this ).filter( function( node ) {
				return wanted.some( function( s ) {
					if( s.charAt( 0 ) === '.' ) return node.matchesClass( s.slice( 1 ) );
					if( s.charAt( 0 ) === '#' ) return node.id === s.slice( 1 );
					return node.tagName === s.toUpperCase();
				} );
			} );
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
	// html would be a browser, and what is under test here is which pane is
	// filled at all
	Object.defineProperty( el, 'innerHTML', {
		get : function() { return '' },
		set : function() { el.children = [] },
	} );

	Object.defineProperty( el, 'lastChild', {
		get : function() { return el.children[el.children.length - 1] || null },
	} );

	return el;
}

/** The children of $parent that carry the class $name */
function childrenWith( parent, name ) {
	return parent === null ? [] : parent.children.filter( function( child ) { return child.matchesClass( name ) } );
}

/**
 *	The panel, drawn into the two mounts panes() hands the shell - inside the
 *	pane the shell renders for it, with the head over them (see
 *	Panels::panesHtml() in the kernel): the panel's name and the actions slot
 *
 *	@param		{Object}	options		{ rules, routes, misses, recording, save, head, panelHead, fail, api, dirty } -
 *																routes: the pages a target can be picked from,
 *																save: what a save answers,
 *																fail: { action: { status, body } } answers that action with a failure,
 *																api: stands in for Nino.adminUi.api (a newer workbench),
 *																dirty: stands in for the shell's Nino.admin.dirty registry,
 *																head: false renders the pane without a head,
 *																panelHead: false stands in for a kernel from
 *																before the head, which has no panelHead() at all
 */
function panel( options ) {

	options = options || {};

	const body = element('body');
	const pane = element('div');
	const rules = element('div');
	const misses = element('div');

	pane.id = 'admin-content-redirects';
	pane.dataset.panel = 'redirects';
	body.appendChild( pane );

	let head = null;
	if( options.head !== false ) {
		head = element('div');
		head.className = 'admin-panel-head';
		const title = element('h2');
		title.className = 'admin-panel-title';
		title.textContent = 'Redirects';
		const actions = element('div');
		actions.className = 'admin-panel-actions';
		head.appendChild( title );
		head.appendChild( actions );
		pane.appendChild( head );
	}

	rules.id = 'redirects-rules';
	misses.id = 'redirects-misses';
	pane.appendChild( rules );
	pane.appendChild( misses );

	const calls = [];
	const uris = [];

	const dc = {
		createElement		: function( tag ) { return element( tag ) },
		getElementById	: function( id ) { return descendants( body ).filter( function( n ) { return n.id === id } )[0] || null },
	};

	// Everything the panel asks the workbench for. buttonRow() is the real
	// one in miniature, because which screen a click puts on is exactly what
	// is under test
	const Nino = {
		content	: { getText : function( key ) { return key } },
		// Nino.http.sendRequest() calls back with the xhr, and the panel reads
		// .status and .responseJSON off it - so that is what stands in for one
		http		: { sendRequest : function( uri, method, callback, payload ) {
			calls.push( payload );
			uris.push( uri );
			if( options.fail && options.fail[payload.action] )
				return callback( { status : options.fail[payload.action].status, responseJSON : options.fail[payload.action].body } );
			if( payload.action === 'redirects/list' )
				return callback( { status : 200, responseJSON : {
					rules			: options.rules || [],
					routes		: options.routes || [],
					misses		: options.misses || [],
					statuses	: [ 301, 302 ],
					recording	: options.recording !== false,
					limit			: 50,
					notes			: [],
				} } );
			if( payload.action === 'redirects/save' )
				return callback( { status : 200, responseJSON : options.save || { saved : '', rules : [], warnings : [], notes : [] } } );
			callback( { status : 200, responseJSON : {} } );
		} },
		adminUi	: {
			text				: function( value ) { return String( value === undefined || value === null ? '' : value ) },
			emptyState	: function( text ) { const el = element('p'); el.className = 'nino-admin-empty'; el.textContent = text; return el },
			listActions	: function( buttons ) { const el = element('div'); el.className = 'nino-admin-list-actions'; buttons.forEach( function( b ) { el.appendChild( b ) } ); return el },
			actionBar		: function( bar ) { const el = element('div'); el.className = 'nino-admin-action-bar'; el.appendChild( bar ); return el },
			contextBar	: function( back ) { const el = element('div'); el.className = 'nino-admin-context-bar'; el.appendChild( back ); return el },
			// A select with its options and its change, because what a pick
			// writes where is exactly what is under test
			selectField	: function( config ) {
				const el = element('label');
				el.className = 'nino-admin-field';
				const select = element('select');
				select.setAttribute( 'data-key', config.key );
				( config.options || [] ).forEach( function( option ) {
					const choice = element('option');
					choice.value = String( option.value );
					choice.textContent = option.label;
					select.appendChild( choice );
				} );
				select.value = String( config.value );
				select.addEventListener( 'change', function() { config.onChange( select.value ) } );
				el.appendChild( select );
				return el;
			},
			switchField	: function() { const el = element('div'); el.className = 'nino-admin-field'; el.appendChild( element('input') ); return el },
			table				: function( config ) {
				const el = element('table');
				el.className = 'nino-admin-table';
				config.rows.forEach( function( row ) {
					const line = element('tr');
					line.className = 'nino-admin-row';
					config.columns.forEach( function( column ) {
						const cell = element('td');
						if( typeof column.render === 'function' ) {
							const rendered = column.render( row[column.key], row );
							if( rendered !== null && typeof rendered === 'object' ) cell.appendChild( rendered );
						}
						line.appendChild( cell );
					} );
					el.appendChild( line );
				} );
				config.mount.appendChild( el );
				return el;
			},
			// The kernel's panelHead() in miniature (Nino.admin.js): the head of
			// the pane an element stands in, null where the pane has none, and
			// tabs() putting a strip after the name in place of whatever strip
			// stood there
			panelHead		: function( el ) {
				let at = el;
				while( at !== null && at.dataset.panel === undefined )
					at = at.parent;
				const row = at === null ? null : childrenWith( at, 'admin-panel-head' )[0] || null;
				if( row === null )
					return null;
				return {
					element	: row,
					title		: childrenWith( row, 'admin-panel-title' )[0] || null,
					actions	: childrenWith( row, 'admin-panel-actions' )[0] || null,
					tabs		: function( strip ) {
						row.children = row.children.filter( function( child ) { return child !== strip && child.matchesClass('admin-panel-tabs') === false } );
						strip.classList.add('admin-panel-tabs');
						strip.parent = row;
						row.children.splice( row.children.indexOf( childrenWith( row, 'admin-panel-title' )[0] ) + 1, 0, strip );
						return strip;
					},
				};
			},
			buttonRow		: function( buttons, active, onSelect, flag ) {
				const paint = function( key ) {
					Object.keys( buttons ).forEach( function( candidate ) {
						buttons[candidate].classList.toggle( 'is-active', candidate === key );
						buttons[candidate].setAttribute( flag || 'aria-pressed', candidate === key ? 'true' : 'false' );
					} );
				};
				Object.keys( buttons ).forEach( function( key ) {
					buttons[key].addEventListener( 'click', function() { paint( key ); onSelect( key ) } );
				} );
				paint( active );
				return paint;
			},
		},
	};

	if( options.panelHead === false )
		delete Nino.adminUi.panelHead;

	if( options.api )
		Nino.adminUi.api = options.api;

	if( options.dirty )
		Nino.admin = { dirty : options.dirty };

	const sandbox = { console : console, document : dc, Nino : Nino, window : { Nino : Nino, confirm : function() { return true } } };
	sandbox.window.window = sandbox.window;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	Nino.admin.redirects.init();

	return {
		rules		: rules,
		misses	: misses,
		head		: head,
		calls		: calls,
		uris		: uris,
		panel		: Nino.admin.redirects,
		byId		: function( id ) { return dc.getElementById( id ) },
		hidden	: function( mount ) { return mount.classList.contains('admin-hidden') },
		tabs		: function( mount ) { return mount.querySelectorAll('.nino-admin-tabs').length },
		// The strips standing in the head itself, which is where a strip of
		// the panel's own goes
		strips	: function() { return childrenWith( head, 'nino-admin-tabs' ) },
		tab			: function( screen ) {
			return descendants( body ).filter( function( n ) { return n.dataset.screen === screen } )[0] || null;
		},
	};
}


// --- The two screens -----------------------------------------------------------

const open = panel( {
	rules		: [ { from : '/old/page', to : '/new/page', status : 301, subtree : false, hits : 3, last : '2026-09-12 10:14:02' } ],
	misses	: [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ],
} );

check( 'the panel reads everything both screens draw in one call', open.calls.length === 1 && open.calls[0].action === 'redirects/list' );
check( 'the rules are what is on screen when the panel opens',
	open.hidden( open.rules ) === false && open.hidden( open.misses ) === true );
/*	The shell renders a head over the pane - the panel's name, a slot for
	buttons - and a panel with screens of its own puts its strip there, beside
	the name, rather than opening a screen with a second row. One strip, and
	in neither mount	*/
const firstStrip = open.strips()[0] || null;
check( 'the strip goes into the pane\'s head, after the panel\'s name, and the rules mount holds the table and the probe',
	open.strips().length === 1 && open.head.children.indexOf( firstStrip ) === 1
	&& firstStrip.matchesClass('admin-panel-tabs') === true && firstStrip.getAttribute('role') === 'tablist'
	&& open.tabs( open.rules ) === 0 && open.tabs( open.misses ) === 0
	&& open.rules.querySelectorAll('.nino-admin-table').length === 1
	&& open.rules.querySelectorAll('.redirects-probe').length === 1 );

/*	The strip is a tablist over two mounts the shell hands the panel as two
	divs in the same panel, so which of them is on is the script's to say. Both
	at once is the one answer that is wrong: the addresses were appended under
	a rules table that was never taken off the screen	*/
open.tab('missing').click();

check( 'the addresses screen replaces the rules screen rather than appearing under it',
	open.hidden( open.rules ) === true && open.hidden( open.misses ) === false );
check( '...so the rules table and the probe are not on screen beside them',
	open.rules.querySelectorAll('.nino-admin-table').length === 0
	&& open.rules.querySelectorAll('.redirects-probe').length === 0 );
// Drawn again on every switch, since the counts in it follow the lists - and
// handed to the head again, which takes it in place of the one before
check( '...and the head carries the strip that was drawn with it, in place of the one before rather than beside it',
	open.strips().length === 1 && open.strips()[0] !== firstStrip && open.head.children.indexOf( open.strips()[0] ) === 1
	&& open.tab('missing').getAttribute('aria-selected') === 'true'
	&& open.tabs( open.misses ) === 0 && open.tabs( open.rules ) === 0 );
check( 'the addresses are a table of their own, with the button that makes a rule',
	open.misses.querySelectorAll('.nino-admin-table').length === 1
	&& open.misses.querySelectorAll('.redirects-row-actions').length === 1 );
// A call that fails says so on the screen it was made from, and the line it
// says it on is the one with this id
check( '...and a message line, because forgetting one of them can fail',
	open.misses.querySelectorAll('#redirects-msg').length === 1 );

open.tab('rules').click();

check( 'and back again, one screen at a time, under the one strip in the head',
	open.hidden( open.rules ) === false && open.hidden( open.misses ) === true
	&& open.strips().length === 1 && open.tab('rules').getAttribute('aria-selected') === 'true'
	&& open.tabs( open.rules ) === 0 && open.tabs( open.misses ) === 0 );

open.tab('missing').click();
open.misses.querySelectorAll('.redirects-row-actions')[0].children[1].click();

check( 'the count in the head\'s strip follows the list when an address is forgotten',
	open.strips().length === 1 && open.tab('missing').textContent === '/_admin/redirects/label/tile (0)' );


// --- From an address to a rule -------------------------------------------------

const made = panel( { misses : [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ] } );

made.tab('missing').click();
made.misses.querySelectorAll('.redirects-row-actions')[0].children[0].click();

check( 'making a rule out of an address puts the rules screen on, with the address already in the editor',
	made.hidden( made.rules ) === false && made.hidden( made.misses ) === true
	&& made.panel._editing !== null && made.panel._editing.from === '/old/press' );
check( '...and the editor is what stands there rather than the table',
	made.rules.querySelectorAll('.nino-admin-context-bar').length === 1
	&& made.rules.querySelectorAll('.nino-admin-table').length === 0 );


// --- A target from the pages of the site ---------------------------------------

const dead = { from : '/a', to : '/dead', status : 301, subtree : false, hits : 0, last : '', answer : 'nothing' };
const fine = { from : '/b', to : '/here', status : 301, subtree : false, hits : 0, last : '', answer : 'route' };
const looped = { from : '/c', to : '/c-2', status : 301, subtree : false, hits : 0, last : '', answer : 'loop' };
const twice = { from : '/d', to : '/a', status : 301, subtree : false, hits : 0, last : '', answer : 'rule' };

const picking = panel( {
	rules		: [ dead, fine, looped, twice ],
	routes	: [ { path : '/blog', label : 'The blog', locale : '' }, { path : '/about', label : '/about', locale : '' } ],
	save		: {
		saved		: '/new',
		rules		: [ dead, fine ],
		warnings	: [ 'Saved - but nothing answers "/blog" yet.' ],
		notes		: [],
	},
} );

/*	A rule whose target leads nowhere looks exactly like one that works until
	somebody follows it, so the table says so: nothing, a loop and another rule
	are flagged, and a target a page or a file or another site answers is not	*/
const flags = picking.rules.querySelectorAll('.redirects-flag');
check( 'a rule whose target nothing answers carries a flag in the table, and so do a loop and a rule through another rule',
	flags.length === 3
	&& flags[0].textContent === '/_admin/redirects/answer/nothing' && flags[1].textContent === '/_admin/redirects/answer/loop'
	&& flags[2].textContent === '/_admin/redirects/answer/rule' );
check( '...the first two as errors, the last as a note, and a rule that is answered says nothing',
	flags[0].matchesClass('nino-admin-error') === true && flags[1].matchesClass('nino-admin-error') === true
	&& flags[2].matchesClass('nino-admin-error') === false
	&& picking.rules.querySelectorAll('.nino-admin-row').length === 4 );

// A chain that is too long is flagged as the error it is, in words of its own - it is not a loop
const chained = { from : '/e', to : '/long-0', status : 301, subtree : false, hits : 0, last : '', answer : 'chain' };
const chaining = panel( { rules : [ chained ], routes : [], save : { saved : '/e', rules : [ chained ], warnings : [], notes : [] } } );
const chainFlags = chaining.rules.querySelectorAll('.redirects-flag');
check( 'a chain of too many rules carries its own flag, as an error', chainFlags.length === 1 && chainFlags[0].textContent === '/_admin/redirects/answer/chain'
	&& chainFlags[0].matchesClass('nino-admin-error') === true );

picking.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();

const select = picking.rules.querySelectorAll('select').filter( function( node ) { return node.getAttribute('data-key') === 'pick' } )[0] || null;

check( 'the editor offers the pages of the site under the free text, after an empty first entry',
	select !== null && select.children.length === 3 && select.children[0].value === ''
	&& select.children[1].value === '/blog' && select.children[2].value === '/about' );
check( '...a page that has a name with the name and the path, and one that has none with the path alone',
	select.children[1].textContent === 'The blog (/blog)' && select.children[2].textContent === '/about' );

const inputs = picking.rules.querySelectorAll('input');
select.value = '/blog';
select.change();

check( 'picking a page writes its path into the target field and into the working copy',
	inputs[1].value === '/blog' && picking.panel._editing.to === '/blog' );
check( '...and the picker goes back to its first entry, so the next pick is a change again', select.value === '' );

// Typing stays what decides: another site, a page that is not made yet
inputs[1].value = '/typed';
inputs[1].listeners['input'].forEach( function( fn ) { fn() } );
check( 'the free text still wins over what was picked', picking.panel._editing.to === '/typed' );

const bar = picking.rules.querySelectorAll('.nino-admin-action-bar')[0];
bar.children[0].children[0].click();

const line = picking.rules.querySelectorAll('#redirects-msg')[0] || null;
check( 'after a save that came with a warning the editor is closed and the table is back',
	picking.panel._editing === null && picking.rules.querySelectorAll('.nino-admin-table').length === 1
	&& picking.rules.querySelectorAll('.nino-admin-context-bar').length === 0 );
check( '...and the warning stands on the message line, as an error and as text',
	line !== null && line.textContent === 'Saved - but nothing answers "/blog" yet.' && line.matchesClass('nino-admin-error') === true );

const plain = panel( { rules : [ fine ], save : { saved : '/new', rules : [ fine ], warnings : [], notes : [] } } );
plain.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();

const plainSelect = plain.rules.querySelectorAll('select').filter( function( node ) { return node.getAttribute('data-key') === 'pick' } )[0] || null;

plain.rules.querySelectorAll('.nino-admin-action-bar')[0].children[0].children[0].click();

const plainLine = plain.rules.querySelectorAll('#redirects-msg')[0] || null;
check( 'a save without a warning says only that it saved',
	plainLine !== null && plainLine.textContent === '/_admin/redirects/msg/saved' && plainLine.matchesClass('nino-admin-error') === false );
check( '...and a panel with no pages to offer still has a picker, with its one empty entry',
	plain.panel._routes.length === 0 && plainSelect !== null && plainSelect.children.length === 1 && plainSelect.children[0].value === '' );


// --- Asking the workbench ------------------------------------------------------

/*	Before the workbench had a request helper a panel posted by itself, from
	the project's directory (the literal the asset bundle fills in); where there
	is one, the helper posts and the panel's own post is never reached	*/
const bare = panel( {} );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action and the json',
	bare.uris.length === 1 && bare.uris[0] === '[[/nino/dir]]/_admin/' && bare.calls[0].action === 'redirects/list' && bare.calls[0].data === '{}' );

const routed = [];
const viaApi = panel( {
	rules	: [ fine ],
	api		: {
		call : function( action, payload, callback ) {
			routed.push( [ action, payload ] );
			callback( 200, { rules : [ fine ], routes : [], misses : [], statuses : [ 301, 302 ], recording : true, limit : 5, notes : [] } );
		},
	},
} );
check( 'with it the panel asks the helper for \'redirects/<action>\' - and posts nothing by hand',
	routed.length === 1 && routed[0][0] === 'redirects/list' && viaApi.uris.length === 0
	&& viaApi.rules.querySelectorAll('.nino-admin-row').length === 1 );

// What a failure says: the shell's errorText() where there is one, the old
// "(status) message" where there is not. The probe shows it
function probeFailure( errorText ) {
	const failing = panel( {
		fail	: { 'redirects/probe' : { status : 500, body : { error : 'boom' } } },
		// Where the shell has the helper it answers for the panel: the list as
		// the stand-in's own http would, the probe as a failure
		api		: errorText === undefined ? undefined : {
			errorText	: errorText,
			call			: function( action, payload, callback ) {
				if( action === 'redirects/probe' )
					return callback( 500, { error : 'boom' } );
				callback( 200, { rules : [], routes : [], misses : [], statuses : [ 301, 302 ], recording : true, limit : 5, notes : [] } );
			},
		},
	} );
	failing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('button')[0].click();
	return failing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('p').filter( function( node ) { return node.matchesClass('nino-admin-hint') } ).pop().textContent;
}
check( 'a failed probe reads "(500) boom" as it always did where the shell has no errorText()', probeFailure( undefined ) === '(500) boom' );
check( '...and what errorText() makes of it where it has one, with the status, the body and no sentence of the panel\'s own',
	probeFailure( function( status, response, key ) { return 'told '+ status+ ' '+ response.error+ ' ['+ key+ ']' } ) === 'told 500 boom []' );


// --- Unsaved input -------------------------------------------------------------

const registry = { entries : {}, asked : [] };
registry.register = function( name, entry ) { registry.entries[name] = entry };
// The shell's guard in miniature: it records what it was asked and lets the exit go
registry.guard = function( names, proceed ) { registry.asked.push( names ); proceed() };
registry.isDirty = function( names ) { return names.some( function( name ) { return registry.entries[name] !== undefined && registry.entries[name].isDirty() === true } ) };

const guarded = panel( { rules : [ fine ], dirty : registry, save : { saved : '/b', rules : [ fine ], warnings : [], notes : [] } } );
const entry = registry.entries['redirects'] || null;

check( 'where the shell has the registry the panel registers under its own uri', entry !== null && typeof entry.isDirty === 'function' && typeof entry.save === 'function' && typeof entry.discard === 'function' );
check( '...with the list on screen there is nothing unsaved', entry.isDirty() === false );

guarded.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
check( '...and a freshly opened editor holds nothing unsaved either', entry.isDirty() === false );

const typing = guarded.rules.querySelectorAll('input')[0];
typing.value = '/typed';
typing.listeners['input'].forEach( function( fn ) { fn() } );
check( '...but what is typed into it is', entry.isDirty() === true );

typing.value = '';
typing.listeners['input'].forEach( function( fn ) { fn() } );
check( '...and typing it back to what it was is not', entry.isDirty() === false );

typing.value = '/typed';
typing.listeners['input'].forEach( function( fn ) { fn() } );
let told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save saves the rule and says it did, once, and the editor is closed',
	told.length === 1 && told[0] === true && guarded.panel._editing === null
	&& guarded.calls.filter( function( call ) { return call.action === 'redirects/save' } ).length === 1 );

told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( '...a Save with no editor open says it did not', told.length === 1 && told[0] === false );

// The way out of the editor, and the strip between the screens, ask first
guarded.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
guarded.rules.querySelectorAll('.nino-admin-context-bar')[0].children[0].click();
check( 'the editor\'s Back asks the shell first, naming this panel, and then goes back', registry.asked.length === 1 && registry.asked[0][0] === 'redirects' && guarded.panel._editing === null );

guarded.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
guarded.tab('missing').click();
check( '...and so does the strip between the two screens', registry.asked.length === 2 && guarded.panel._screen === 'missing' && guarded.panel._editing === null );

// A Save the shell asked for that fails brings the panel on screen again
// (showCurrent()): the reason it gave, and the rule, have to still be there
const refused = panel( { rules : [ fine ], dirty : registry, fail : { 'redirects/save' : { status : 500, body : { error : 'boom' } } } } );
refused.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
const refusedInput = refused.rules.querySelectorAll('input')[0];
refusedInput.value = '/typed';
refusedInput.listeners['input'].forEach( function( fn ) { fn() } );
told = [];
registry.entries['redirects'].save( function( ok ) { told.push( ok ) } );
check( 'a Save the shell asked for that the server refuses says it did not save, with the reason on screen',
	told.length === 1 && told[0] === false && refused.byId('redirects-msg').textContent === '(500) boom' );
refused.panel.showCurrent();
check( '...and the shell showing the panel again keeps the reason, and the rule that was not saved',
	refused.byId('redirects-msg').textContent === '(500) boom' && refused.panel._editing !== null && registry.entries['redirects'].isDirty() === true );

// Without the registry nothing asks, and the exits are the ones they were
const plainly = panel( { rules : [ fine ] } );
plainly.rules.querySelectorAll('.nino-admin-list-actions')[0].children[0].click();
plainly.rules.querySelectorAll('.nino-admin-context-bar')[0].children[0].click();
check( 'on a workbench without the registry Back leaves the editor at once', plainly.panel._editing === null && plainly.rules.querySelectorAll('.nino-admin-table').length === 1 );


// --- Nothing to draw -----------------------------------------------------------

const quiet = panel( { recording : false } );

quiet.tab('missing').click();

check( 'with nothing on the list the screen says so rather than standing empty',
	quiet.misses.querySelectorAll('.nino-admin-empty').length === 1 );
check( '...and says that nothing is being written down at all, which is why it is empty',
	quiet.misses.querySelectorAll('.nino-admin-error').length === 1 );


// --- Where there is no head --------------------------------------------------

/*	The Features panel's fallback: a pane without a head - the script drawn
	somewhere other than its pane - and a kernel from before the head, which
	has no panelHead() at all. Either way the strip is the way back, so it
	stands at the top of whichever mount is on rather than in the rules mount	*/
[ [ 'a pane without a head', { head : false } ], [ 'a kernel without panelHead()', { head : false, panelHead : false } ] ].forEach( function( variant ) {

	const bare = panel( Object.assign( { misses : [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ] }, variant[1] ) );
	const before = bare.tabs( bare.rules ) === 1 && bare.rules.children[0].matchesClass('redirects-tabs') === true;

	bare.tab('missing').click();

	check( 'with '+ variant[0]+ ' the strip opens the screen that is on, and goes with it to the addresses',
		before === true && bare.hidden( bare.misses ) === false
		&& bare.tabs( bare.misses ) === 1 && bare.misses.children[0].matchesClass('redirects-tabs') === true
		&& bare.tabs( bare.rules ) === 0 );
} );


console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
