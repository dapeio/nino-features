/**
 *	Nino
 *	redirects-js-smoke.js	What the panel's admin.js does over a dom stand-in,
 *													on the workbench's own helpers: the Nino.js and the
 *													Nino.admin.js of the checkout it runs against are the
 *													real ones, and the stand-in is only what a browser and
 *													the shell are - a document that is loading until the
 *													test says the page is ready, the words a page carries,
 *													the wire, the shell's registry of unsaved input.
 *													Which of the two screens is on, that the strip
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
 *													leads nowhere carries a flag in the table, that what
 *													a save warned about stands beside the line that says
 *													it was saved while the editor is closed, that a
 *													refusal is said in the words of its code, and that a
 *													showing of the panel draws what the saves and the
 *													deletes since the first answer left, not the answer -
 *													and that the panel starts when the shell shows it and
 *													not when the page is ready.
 *
 *													No jsdom: the element stand-in of the checkout's
 *													tests/harness.js, which is there from Nino 1.8 on,
 *													as the feature is. The checkout is the one NINO_ROOT
 *													names, else the one three levels up.
 *
 *	Usage: node features/Redirects/tests/redirects-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve( process.env.NINO_ROOT || path.join( __dirname, '..', '..', '..' ) );
const { check, done, element, documentOf, findAll, fire, fills, load } = require( path.join( root, 'tests', 'harness.js' ) );

const source = fs.readFileSync( path.join( __dirname, '..', 'assets', 'admin.js' ), 'utf8' );

// The words as the workbench has them in English: the shell's own and this panel's, from their files
const WORDS = Object.assign( fills( '_admin/text/en_US.php' ), fills( path.relative( root, path.join( __dirname, '..', 'text', 'en_US.php' ) ) ) );

/** The text of an element and of everything below it - a text node is a child here */
function textOf( el ) {
	return el.textContent + el.children.map( textOf ).join('');
}

/** The children of $parent that carry the class $name */
function childrenWith( parent, name ) {
	return parent === null ? [] : parent.children.filter( function( child ) { return child.classList.contains( name ) } );
}

/** The answer of a list: what the panel draws both screens from */
function listed( options ) {
	return {
		rules			: options.rules || [],
		routes		: options.routes || [],
		misses		: options.misses || [],
		statuses	: options.statuses === null ? undefined : ( options.statuses || [ 301, 302 ] ),
		recording	: options.recording !== false,
		limit			: 50,
		notes			: [],
	};
}

/**
 *	The panel, drawn into the two mounts panes() hands the shell - inside the
 *	pane the shell renders for it, with the head over them (see
 *	Panels::panesHtml() in the kernel): the panel's name and the actions slot
 *
 *	@param		{Object}	options		{ rules, routes, misses, statuses, recording, save, head, fail, hold, dirty, confirm, start } -
 *																routes: the pages a target can be picked from,
 *																save: what a save answers,
 *																fail: { action: { status, body } } answers that action with a failure,
 *																hold: the action whose answer is kept until release() gives it,
 *																dirty: stands in for the shell's Nino.admin.dirty registry,
 *																head: false renders the pane without a head,
 *																confirm: what window.confirm() answers,
 *																start: 'show' (the shell shows the panel - the default) or 'none' - a page that
 *																is still loading, whose ready() the test calls
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
	const held = [];
	const asked = [];

	/** What the server answers to one action: [ status, body ] - a delete answers the rules that are left */
	function answer( action, data ) {

		if( options.fail && options.fail[action] )
			return [ options.fail[action].status, options.fail[action].body ];
		if( action === 'redirects/list' )
			return [ 200, listed( options ) ];
		if( action === 'redirects/save' )
			return [ 200, options.save || { saved : '', rules : [], warnings : [], notes : [] } ];
		if( action === 'redirects/delete' )
			return [ 200, { deleted : JSON.parse( data ).from, rules : ( options.rules || [] ).filter( function( rule ) { return rule.from !== JSON.parse( data ).from } ) } ];

		return [ 200, {} ];
	}

	/*	The page Nino.js loads into. It is still loading, so what is bound to the
		ready event waits for DOMContentLoaded, and the timers Nino.js asks for are
		kept for the test to run: a callback that throws is thrown again from one
		of them, and a smoke that never ran them would not see it. The words are
		what the [jstext] shortcode puts on a page, which the real
		Nino.content.getText() reads	*/
	const heard = {};
	const timers = [];

	const page = documentOf( body );
	page.readyState = 'loading';
	page.addEventListener = function( name, fn ) { ( heard[name] = heard[name] || [] ).push( fn ) };

	/*	table() takes a node that a cell hands back in as one when it is an Object
		with a nodeType: the elements here are made in this realm and carry no
		type, and the script runs in a context of its own. A node is given its
		type, and the context this realm's Object	*/
	page.createElement = function( tag ) { return Object.assign( element( tag ), { nodeType : 1 } ) };

	const sandbox = {
		console : console, Object : Object, document : page, navigator : { userAgent : 'node' }, NinoJstext : WORDS,
		Event : function( type ) { this.type = type },
		addEventListener : function() {},
		dispatchEvent : function() {},
		requestAnimationFrame : function() {},
		setTimeout : function( fn ) { timers.push( fn ); return timers.length },
		confirm : function( text ) { asked.push( text ); return options.confirm !== false },
	};
	sandbox.window = sandbox;

	const context = vm.createContext( sandbox );
	load( '_nino/Nino.js', context );

	const Nino = sandbox.Nino;

	// The wire is the page's: the real api of Nino.admin.js posts through it
	Nino.http.sendRequest = function( uri, method, callback, data ) {

		calls.push( { uri : uri, method : method, action : data.action, data : data.data } );

		const answered = answer( data.action, data.data );
		const give = function() { callback( { status : answered[0], responseJSON : answered[1] } ) };

		if( options.hold === data.action )
			return held.push( give );

		give();
	};

	// The shell's registry of unsaved input, with nothing unsaved in it and an exit that is never refused
	Nino.admin = { dirty : options.dirty || { register : function() {}, isDirty : function() { return false }, guard : function( names, proceed ) { proceed() } } };

	load( '_admin/assets/Nino.admin.js', context );
	vm.runInContext( source, context, { filename : 'admin.js' } );

	/*	The page is ready: the document fires DOMContentLoaded, and the timers
		Nino.js asked for run - the callbacks of the ready event are Nino.js's
		to call, and so is the error one of them throws again	*/
	const ready = function() {
		( heard['DOMContentLoaded'] || [] ).forEach( function( fn ) { fn( { type : 'DOMContentLoaded' } ) } );
		while( timers.length > 0 )
			timers.shift()();
	};

	// The shell shows the panel; the first showing is what asks for the list
	if( options.start !== 'none' )
		Nino.admin.redirects.showCurrent();

	return {
		ready		: ready,
		rules		: rules,
		misses	: misses,
		head		: head,
		calls		: calls,
		asked		: asked,
		panel		: Nino.admin.redirects,
		release	: function() { held.splice( 0 ).forEach( function( give ) { give() } ) },
		byId		: function( id ) { return findAll( body, function( el ) { return el.id === id } )[0] || null },
		hidden	: function( mount ) { return mount.classList.contains('admin-hidden') },
		tabs		: function( mount ) { return mount.querySelectorAll('.nino-admin-tabs').length },
		// The strips standing in the head itself, which is where a strip of
		// the panel's own goes
		strips	: function() { return childrenWith( head, 'nino-admin-tabs' ) },
		tab			: function( screen ) {
			return findAll( body, function( el ) { return el.dataset.screen === screen } )[0] || null;
		},
		// The button that opens the editor on a new rule, the first of the bar below the rules
		add			: function() { return rules.querySelectorAll('.nino-admin-list-actions')[0].children[0] },
		// The bar of the editor, where its Save and its message line stand
		bar			: function() { return rules.querySelectorAll('.nino-admin-actionbar')[0] },
		select	: function( key ) { return rules.querySelectorAll('select').filter( function( node ) { return node.getAttribute('data-key') === key } )[0] || null },
	};
}


// --- The two screens -----------------------------------------------------------

const open = panel( {
	rules		: [ { from : '/old/page', to : '/new/page', status : 301, subtree : false, hits : 3, last : '2026-09-12 10:14:02' } ],
	misses	: [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ],
} );

check( 'the panel reads everything both screens draw in one call, through the workbench\'s own request: the project\'s _admin, a post, the action and a json payload',
	open.calls.length === 1 && open.calls[0].action === 'redirects/list' && open.calls[0].uri === '/_admin/' && open.calls[0].method === 'POST' && open.calls[0].data === '{}' );
check( 'the rules are what is on screen when the panel opens',
	open.hidden( open.rules ) === false && open.hidden( open.misses ) === true );
/*	The shell renders a head over the pane - the panel's name, a slot for
	buttons - and a panel with screens of its own puts its strip there, beside
	the name, rather than opening a screen with a second row. One strip, and
	in neither mount	*/
const firstStrip = open.strips()[0] || null;
check( 'the strip goes into the pane\'s head, after the panel\'s name, and the rules mount holds the table and the probe',
	open.strips().length === 1 && open.head.children.indexOf( firstStrip ) === 1
	&& firstStrip.classList.contains('admin-panel-tabs') === true && firstStrip.getAttribute('role') === 'tablist'
	&& open.tabs( open.rules ) === 0 && open.tabs( open.misses ) === 0
	&& open.rules.querySelectorAll('.nino-admin-table').length === 1
	&& open.rules.querySelectorAll('.redirects-probe').length === 1 );

/*	The strip is a tablist over two mounts the shell hands the panel as two
	divs in the same panel, so which of them is on is the script's to say. Both
	at once is the one answer that is wrong: the addresses were appended under
	a rules table that was never taken off the screen	*/
fire( open.tab('missing'), 'click' );

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

fire( open.tab('rules'), 'click' );

check( 'and back again, one screen at a time, under the one strip in the head',
	open.hidden( open.rules ) === false && open.hidden( open.misses ) === true
	&& open.strips().length === 1 && open.tab('rules').getAttribute('aria-selected') === 'true'
	&& open.tabs( open.rules ) === 0 && open.tabs( open.misses ) === 0 );

fire( open.tab('missing'), 'click' );
fire( open.misses.querySelectorAll('.redirects-row-actions')[0].children[1], 'click' );

check( 'the count in the head\'s strip follows the list when an address is forgotten',
	open.strips().length === 1 && open.tab('missing').textContent === WORDS['/_admin/redirects/label/tile']+ ' (0)' );

// The buttons of a row are the workbench's small ones, and the pair stays on one line
check( 'the buttons in the rows are the small ones of the workbench',
	open.rules.querySelectorAll('.redirects-row-actions').length === 0
	&& panel( { rules : [ { from : '/a', to : '/b', status : 301, subtree : false, hits : 0, last : '' } ] } ).rules.querySelectorAll('.redirects-row-actions')[0].children
		.every( function( button ) { return button.classList.contains('nino-admin-btn--small') } ) === true
	&& panel( { misses : [ { path : '/a', count : 1, last : '' } ] } ).misses.querySelectorAll('.redirects-row-actions').length === 0 );


// --- From an address to a rule -------------------------------------------------

const made = panel( { misses : [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ] } );

fire( made.tab('missing'), 'click' );
check( 'the buttons of an address are the small ones too, the rule first', made.misses.querySelectorAll('.redirects-row-actions')[0].children
	.every( function( button ) { return button.classList.contains('nino-admin-btn--small') } ) === true );
fire( made.misses.querySelectorAll('.redirects-row-actions')[0].children[0], 'click' );

check( 'making a rule out of an address puts the rules screen on, with the address already in the editor',
	made.hidden( made.rules ) === false && made.hidden( made.misses ) === true
	&& made.panel._editing !== null && made.panel._editing.from === '/old/press' );
check( '...and the editor is what stands there rather than the table',
	made.rules.querySelectorAll('.nino-admin-contextbar').length === 1
	&& made.rules.querySelectorAll('.nino-admin-table').length === 0 );
const madeInputs = made.rules.querySelectorAll('input');
check( '...the fields are the workbench\'s text fields, named and with the address in the first',
	textOf( madeInputs[0].parentNode.children[0] ) === WORDS['/_admin/redirects/label/from'] && madeInputs[0].value === '/old/press'
	&& madeInputs[0].placeholder === '/old/page' && madeInputs[1].value === '' && madeInputs[1].placeholder === '/new/page'
	&& madeInputs[0].className === 'nino-admin-input' );


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
	&& flags[0].textContent === WORDS['/_admin/redirects/answer/nothing'] && flags[1].textContent === WORDS['/_admin/redirects/answer/loop']
	&& flags[2].textContent === WORDS['/_admin/redirects/answer/rule'] );
check( '...the first two as errors, the last as a note, and a rule that is answered says nothing',
	flags[0].classList.contains('nino-admin-error') === true && flags[1].classList.contains('nino-admin-error') === true
	&& flags[2].classList.contains('nino-admin-error') === false
	&& picking.rules.querySelectorAll('tbody').length === 1 && picking.rules.querySelectorAll('tbody')[0].children.length === 4 );

// A chain that is too long is flagged as the error it is, in words of its own - it is not a loop
const chained = { from : '/e', to : '/long-0', status : 301, subtree : false, hits : 0, last : '', answer : 'chain' };
const chaining = panel( { rules : [ chained ], routes : [], save : { saved : '/e', rules : [ chained ], warnings : [], notes : [] } } );
const chainFlags = chaining.rules.querySelectorAll('.redirects-flag');
check( 'a chain of too many rules carries its own flag, as an error', chainFlags.length === 1 && chainFlags[0].textContent === WORDS['/_admin/redirects/answer/chain']
	&& chainFlags[0].classList.contains('nino-admin-error') === true && WORDS['/_admin/redirects/answer/chain'] !== WORDS['/_admin/redirects/answer/loop'] );

fire( picking.add(), 'click' );

const select = picking.select('pick');

check( 'the editor offers the pages of the site under the free text, after an empty first entry',
	select !== null && select.children.length === 3 && select.children[0].value === ''
	&& select.children[1].value === '/blog' && select.children[2].value === '/about' );
check( '...a page that has a name with the name and the path, and one that has none with the path alone',
	select.children[1].textContent === 'The blog (/blog)' && select.children[2].textContent === '/about' );

const inputs = picking.rules.querySelectorAll('input');
select.value = '/blog';
fire( select, 'change' );

check( 'picking a page writes its path into the target field and into the working copy',
	inputs[1].value === '/blog' && picking.panel._editing.to === '/blog' );
check( '...and the picker goes back to its first entry, so the next pick is a change again', select.value === '' );

// The statuses are the server's: the editor offers what the list answered,
// and an answer that names none leaves it none of its own
const statusOptions = function( drawn ) {
	fire( drawn.add(), 'click' );
	const field = drawn.select('status');
	return field === null ? null : field.children.map( function( option ) { return option.value } ).join(' ');
};
check( 'the editor offers the statuses the list answered, and none of its own where the answer names none',
	statusOptions( panel( { rules : [], routes : [], statuses : [ 301, 302, 308 ] } ) ) === '301 302 308'
	&& statusOptions( panel( { rules : [], routes : [], statuses : null } ) ) === '' );

// Typing stays what decides: another site, a page that is not made yet
inputs[1].value = '/typed';
fire( inputs[1], 'input' );
check( 'the free text still wins over what was picked', picking.panel._editing.to === '/typed' );

fire( picking.bar().children[0], 'click' );

const line = picking.byId('redirects-msg');
const warning = findAll( picking.rules, function( el ) { return el.getAttribute('role') === 'alert' } );
check( 'after a save that came with a warning the editor is closed and the table is back',
	picking.panel._editing === null && picking.rules.querySelectorAll('.nino-admin-table').length === 1
	&& picking.rules.querySelectorAll('.nino-admin-contextbar').length === 0 );
check( '...the line says it was saved, in the workbench\'s own words and state',
	line !== null && /^Saved at \d\d:\d\d\.$/.test( line.textContent ) && line.dataset.state === 'saved' && line.classList.contains('nino-admin-status') );
check( '...and the warning stands beside it, as an error and as text, put into the page as an alert so that a reader is told',
	warning.length === 1 && warning[0].textContent === 'Saved - but nothing answers "/blog" yet.' && warning[0].classList.contains('nino-admin-error') === true
	&& warning[0].classList.contains('nino-admin-hint') === true && warning[0].tagName === 'P'
	&& line.parentNode.children.indexOf( warning[0] ) === line.parentNode.children.indexOf( line ) + 1 );

const plain = panel( { rules : [ fine ], save : { saved : '/new', rules : [ fine ], warnings : [], notes : [] } } );
fire( plain.add(), 'click' );

const plainSelect = plain.select('pick');

fire( plain.bar().children[0], 'click' );

const plainLine = plain.byId('redirects-msg');
check( 'a save without a warning says only that it saved',
	plainLine !== null && /^Saved at \d\d:\d\d\.$/.test( plainLine.textContent )
	&& findAll( plain.rules, function( el ) { return el.getAttribute('role') === 'alert' } ).length === 0 );
check( '...and a panel with no pages to offer still has a picker, with its one empty entry',
	plain.panel._routes.length === 0 && plainSelect !== null && plainSelect.children.length === 1 && plainSelect.children[0].value === '' );


// --- What a save says while it is on its way, and when it is refused -------------

const saving = panel( { rules : [ fine ], hold : 'redirects/save', save : { saved : '/b', rules : [ fine ], warnings : [], notes : [] } } );
fire( saving.add(), 'click' );
const savingLine = saving.byId('redirects-msg');
const savingButton = saving.bar().children[0];

fire( savingButton, 'click' );
check( 'while a save is on its way the line says so, in the workbench\'s state, and the button cannot be pressed twice',
	savingLine.dataset.state === 'saving' && savingLine.textContent === WORDS['/_admin/common/msg/saving'] && savingButton.disabled === true );
fire( savingButton, 'click' );
check( '...a second press sends nothing', saving.calls.filter( function( call ) { return call.action === 'redirects/save' } ).length === 1 );
saving.release();
check( '...and the answer ends it', savingButton.disabled === false && saving.panel._editing === null );

// A refusal is said in the words of its code, the status number left out; one with a param has it in its place
const refusals = [
	[ { error : 'the old address is not a path of this site', code : 'redirects_from' }, WORDS['/_admin/error/redirects_from'] ],
	[ { error : 'there is already a rule for "/taken"', code : 'redirects_taken', params : [ '/taken' ] }, 'There is already a rule for "/taken". Delete or edit that one first.' ],
	[ { error : 'the rule would send a visitor back into itself', code : 'redirects_loop_subtree' }, WORDS['/_admin/error/redirects_loop_subtree'] ],
];
refusals.forEach( function( refusal ) {
	const refused = panel( { rules : [ fine ], fail : { 'redirects/save' : { status : 400, body : refusal[0] } } } );
	fire( refused.add(), 'click' );
	fire( refused.bar().children[0], 'click' );
	check( 'a save refused with '+ refusal[0].code+ ' says its sentence on the line of the editor, as an error, and the editor stays',
		refusal[1] !== '' && refused.byId('redirects-msg').textContent === refusal[1] && refused.byId('redirects-msg').dataset.state === 'error'
		&& refused.panel._editing !== null && refused.bar().children[0].disabled === false );
} );

const down = panel( { rules : [ fine ], fail : { 'redirects/save' : { status : 500, body : { error : 'the rules could not be written' } } } } );
fire( down.add(), 'click' );
fire( down.bar().children[0], 'click' );
check( 'a save the server could not write says what it says, with the status in front: no code, no sentence of the panel\'s', down.byId('redirects-msg').textContent === '(500) the rules could not be written' );


// --- A rule deleted, an address forgotten ---------------------------------------

const rowRule = { from : '/a$&b', to : '/b', status : 301, subtree : false, hits : 0, last : '', answer : 'route' };
const deleting = panel( { rules : [ rowRule, fine ] } );

fire( deleting.rules.querySelectorAll('.redirects-row-actions')[0].children[1], 'click' );
check( 'deleting asks first, naming the rule as it was typed - a "$&" in it is a "$&" and not a pattern', deleting.asked.length === 1
	&& deleting.asked[0] === 'Delete the redirect for "/a$&b"?' );
check( '...and sends the address, and says "Deleted." on the line of the screen, in the state of a line that is idle',
	deleting.calls[1].action === 'redirects/delete' && deleting.calls[1].data === '{"from":"/a$&b"}'
	&& deleting.byId('redirects-msg').textContent === WORDS['/_admin/redirects/msg/deleted'] && deleting.byId('redirects-msg').dataset.state === 'idle' );

const unasked = panel( { rules : [ fine ], confirm : false } );
fire( unasked.rules.querySelectorAll('.redirects-row-actions')[0].children[1], 'click' );
check( 'a "no" to the question sends nothing', unasked.calls.length === 1 );

const unknown = panel( { rules : [ fine ], fail : { 'redirects/delete' : { status : 404, body : { error : 'there is no rule for "/b"', code : 'redirects_unknown', params : [ '/b' ] } } } } );
fire( unknown.rules.querySelectorAll('.redirects-row-actions')[0].children[1], 'click' );
check( 'a rule that is gone already is said as the code says it, on the line, and stays in the table',
	unknown.byId('redirects-msg').textContent === 'There is no rule for "/b".' && unknown.byId('redirects-msg').dataset.state === 'error'
	&& unknown.rules.querySelectorAll('tbody')[0].children.length === 1 );

const forgetting = panel( { misses : [ { path : '/old/press', count : 7, last : '' } ], fail : { 'redirects/forget' : { status : 500, body : { error : 'the list could not be written' } } } } );
fire( forgetting.tab('missing'), 'click' );
fire( forgetting.misses.querySelectorAll('.nino-admin-list-actions')[0].children[0], 'click' );
check( 'forgetting all asks first, and a list that could not be written is said on the line of the addresses screen',
	forgetting.asked.length === 1 && forgetting.byId('redirects-msg').textContent === '(500) the list could not be written'
	&& forgetting.misses.querySelectorAll('tbody')[0].children.length === 1 );


// --- Starting, and showing the panel again ---------------------------------------

/*	A page opened on the panel's address has the shell show the panel, and the
	page being ready is a second moment the page has: a panel that bound itself
	to it asked for its list twice. This one has no such bootstrap - Nino.js
	says when the page is ready, and nothing is bound to it	*/
const loading = panel( { start : 'none' } );

loading.ready();

check( 'the page being ready asks for nothing: the panel starts when the shell shows it', loading.calls.length === 0 );

loading.panel.showCurrent();

check( '...and the shell\'s showing asks for the list, once', loading.calls.length === 1 && loading.calls[0].action === 'redirects/list' );

const shown = panel();

shown.ready();

check( 'a page that is ready after the shell has shown the panel asks for nothing more', shown.calls.length === 1 );

/*	The first answer is panel.data, and 'keep' draws it again with every
	showing: the fields are what the saves and the deletes since changed, so a
	showing that went back to the answer would bring back the rule that was
	deleted and take away the one that was saved	*/
const again = panel( { rules : [ fine, dead ], misses : [ { path : '/old/press', count : 7, last : '' } ] } );

fire( again.rules.querySelectorAll('.redirects-row-actions')[1].children[1], 'click' );
again.panel.showCurrent();
check( 'a showing after a delete draws the rules the delete left, not the answer of the first request',
	again.rules.querySelectorAll('tbody')[0].children.length === 1 && again.calls.length === 2 );

fire( again.tab('missing'), 'click' );
fire( again.misses.querySelectorAll('.redirects-row-actions')[0].children[1], 'click' );
again.panel.showCurrent();
check( '...and one after an address was forgotten does not bring it back', again.tab('missing').textContent === WORDS['/_admin/redirects/label/tile']+ ' (0)'
	&& again.misses.querySelectorAll('.nino-admin-empty').length === 1 );

again.panel.reload();
check( 'a reload asks again, and what it answers is taken into the fields', again.calls.length === 4 && again.tab('missing').textContent === WORDS['/_admin/redirects/label/tile']+ ' (1)' );

const failedLoad = panel( { fail : { 'redirects/list' : { status : 500, body : null } } } );
check( 'a list that could not be read is said in the first mount, with its status and the panel\'s own sentence for it',
	failedLoad.rules.querySelectorAll('.nino-admin-error')[0].textContent === '(500) '+ WORDS['/_admin/redirects/error/load'] && failedLoad.panel.ready === false );


// --- A failed probe ----------------------------------------------------------------

function probeFailure() {
	const failing = panel( { fail : { 'redirects/probe' : { status : 500, body : { error : 'boom' } } } } );
	fire( failing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('button')[0], 'click' );
	return failing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('p').filter( function( node ) { return node.classList.contains('nino-admin-hint') } ).pop();
}
const failedProbe = probeFailure();
check( 'a failed probe is said as the workbench says it, with the status and the body and no sentence of the panel\'s own',
	failedProbe.textContent === '(500) boom' && failedProbe.classList.contains('nino-admin-error') === true );

const probing = panel( { rules : [], fail : {} } );
probing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('input')[0].value = '/old';
fire( probing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('input')[0], 'input' );
fire( probing.rules.querySelectorAll('.redirects-probe')[0].querySelectorAll('button')[0], 'click' );
check( 'the probe sends the address as typed', probing.calls[1].action === 'redirects/probe' && probing.calls[1].data === '{"path":"/old"}' );


// --- Unsaved input -------------------------------------------------------------

const registry = { entries : {}, asked : [] };
registry.register = function( name, entry ) { registry.entries[name] = entry };
// The shell's guard in miniature: it records what it was asked and lets the exit go
registry.guard = function( names, proceed ) { registry.asked.push( names ); proceed() };
registry.isDirty = function( names ) { return names.some( function( name ) { return registry.entries[name] !== undefined && registry.entries[name].isDirty() === true } ) };

const guarded = panel( { rules : [ fine ], dirty : registry, save : { saved : '/b', rules : [ fine ], warnings : [], notes : [] } } );
const entry = registry.entries['redirects'] || null;

check( 'the panel registers under its own uri when it first asks, with the spec\'s three members', entry !== null && typeof entry.isDirty === 'function' && typeof entry.save === 'function' && typeof entry.discard === 'function' );
check( '...with the list on screen there is nothing unsaved', entry.isDirty() === false );

fire( guarded.add(), 'click' );
check( '...and a freshly opened editor holds nothing unsaved either', entry.isDirty() === false );

const typing = guarded.rules.querySelectorAll('input')[0];
typing.value = '/typed';
fire( typing, 'input' );
check( '...but what is typed into it is', entry.isDirty() === true );

typing.value = '';
fire( typing, 'input' );
check( '...and typing it back to what it was is not', entry.isDirty() === false );

typing.value = '/typed';
fire( typing, 'input' );
let told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( 'the shell\'s Save saves the rule and says it did, once, and the editor is closed',
	told.length === 1 && told[0] === true && guarded.panel._editing === null
	&& guarded.calls.filter( function( call ) { return call.action === 'redirects/save' } ).length === 1 );

told = [];
entry.save( function( ok ) { told.push( ok ) } );
check( '...a Save with no editor open says it did not', told.length === 1 && told[0] === false );

// The way out of the editor, and the strip between the screens, ask first
fire( guarded.add(), 'click' );
fire( guarded.rules.querySelectorAll('.nino-admin-contextbar')[0].children[0], 'click' );
check( 'the editor\'s Back asks the shell first, naming this panel, and then goes back', registry.asked.length === 1 && registry.asked[0][0] === 'redirects' && guarded.panel._editing === null );

fire( guarded.add(), 'click' );
fire( guarded.tab('missing'), 'click' );
check( '...and so does the strip between the two screens', registry.asked.length === 2 && guarded.panel._screen === 'missing' && guarded.panel._editing === null );

// A Save the shell asked for that fails brings the panel on screen again
// (showCurrent()): the reason it gave, and the rule, have to still be there
const refused = panel( { rules : [ fine ], dirty : registry, fail : { 'redirects/save' : { status : 500, body : { error : 'boom' } } } } );
fire( refused.add(), 'click' );
const refusedInput = refused.rules.querySelectorAll('input')[0];
refusedInput.value = '/typed';
fire( refusedInput, 'input' );
told = [];
registry.entries['redirects'].save( function( ok ) { told.push( ok ) } );
check( 'a Save the shell asked for that the server refuses says it did not save, with the reason on screen',
	told.length === 1 && told[0] === false && refused.byId('redirects-msg').textContent === '(500) boom' );
refused.panel.showCurrent();
check( '...and the shell showing the panel again keeps the reason, and the rule that was not saved',
	refused.byId('redirects-msg').textContent === '(500) boom' && refused.panel._editing !== null && registry.entries['redirects'].isDirty() === true );

// What the shell throws away when it is told to discard is not asked about again
registry.entries['redirects'].discard();
check( 'the shell\'s Discard takes the editor for saved, whatever is typed into it', registry.entries['redirects'].isDirty() === false );


// --- Nothing to draw -----------------------------------------------------------

const quiet = panel( { recording : false } );

fire( quiet.tab('missing'), 'click' );

check( 'with nothing on the list the screen says so rather than standing empty',
	quiet.misses.querySelectorAll('.nino-admin-empty').length === 1 );
check( '...and says that nothing is being written down at all, which is why it is empty',
	quiet.misses.querySelectorAll('.nino-admin-error').length === 1 && quiet.misses.querySelectorAll('.nino-admin-error')[0].classList.contains('nino-admin-hint') === true );

const limited = panel( { misses : [ { path : '/old/press', count : 7, last : '' } ] } );
fire( limited.tab('missing'), 'click' );
check( 'the ceiling of the list is said in the words that name it, the number filled in',
	findAll( limited.misses, function( el ) { return el.textContent === 'At most 50 are kept. A scanner finds more in an afternoon than anybody reads.' } ).length === 1 );


// --- Where there is no head --------------------------------------------------

/*	A pane without a head - the script drawn somewhere other than its pane. The
	strip is the way back, so it stands at the top of whichever mount is on
	rather than in the rules mount	*/
const headless = panel( { head : false, misses : [ { path : '/old/press', count : 7, last : '2026-09-12 09:58:41' } ] } );
const before = headless.tabs( headless.rules ) === 1 && headless.rules.children[0].classList.contains('redirects-tabs') === true;

fire( headless.tab('missing'), 'click' );

check( 'with a pane without a head the strip opens the screen that is on, and goes with it to the addresses',
	before === true && headless.hidden( headless.misses ) === false
	&& headless.tabs( headless.misses ) === 1 && headless.misses.children[0].classList.contains('redirects-tabs') === true
	&& headless.tabs( headless.rules ) === 0 );


done();
