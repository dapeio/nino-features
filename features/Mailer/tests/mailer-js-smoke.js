/**
 *	Nino
 *	mailer-js-smoke.js	What the Mailer panel's script does with the status the
 *											server answers: the screen is drawn from the answer when
 *											the page is ready - the status line with the port a send
 *											really uses, the address field filled with the one the
 *											server offers, the last errors as a list of plain text,
 *											or the line that says there are none - and what a test
 *											mail does on it: the live line says sending, then sent,
 *											or the sentence of the code the server refused it with,
 *											and the field it named is marked. The status and the
 *											errors are asked for again after a test mail; what was
 *											typed is left alone.
 *
 *											No jsdom, no dependency: a dom stand-in with just enough
 *											for the panel to draw itself, so this runs with nothing
 *											but node. The workbench (Nino.adminUi, Nino.content) is
 *											a stand-in of its own, kept to what docs/features.md
 *											says of it. mailer-smoke.php runs this through node when
 *											node is on the path, so bin/check.sh and CI cover it;
 *											it also runs on its own.
 *
 *	Usage: node features/Mailer/tests/mailer-js-smoke.js
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
 *	One element, with just enough of the interface admin.js and the workbench
 *	stand-in reach for
 */
function element( tag ) {

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		className		: '',
		id					: '',
		name				: '',
		textContent	: '',
		value				: '',
		disabled		: false,
		dataset			: {},
		attributes	: {},
		listeners		: {},
		classList		: { add : function( name ) { el.className = ( el.className === '' ? '' : el.className+ ' ' )+ name } },
		setAttribute: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild	: function( child ) { this.children.push( child ); return child },
		addEventListener : function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		// A tag, or a [name="x"] attribute: all the status line looks the refused field up by
		querySelector : function( selector ) {
			const named = /^\[name="([^"]+)"\]$/.exec( selector );
			return descendants( this ).filter( function( child ) { return named === null ? child.tagName === selector.toUpperCase() : child.name === named[1] } )[0] || null;
		},
	};

	let html = '';
	Object.defineProperty( el, 'innerHTML', { get : function() { return html }, set : function( value ) { html = value; el.children = [] } } );

	return el;
}

const texts = {
	'/_admin/mailer/hint/intro' 				: 'Sends a test mail.',
	'/_admin/mailer/label/status' 			: 'Sending through %host:%port (%encryption).',
	'/_admin/mailer/label/unconfigured' : 'Not configured yet',
	'/_admin/mailer/label/to' 					: 'Send a test mail to',
	'/_admin/mailer/label/send' 				: 'Send test mail',
	'/_admin/mailer/msg/sending' 				: 'Sending …',
	'/_admin/mailer/msg/sent' 					: 'Test mail sent.',
	'/_admin/mailer/label/errors' 			: 'Last errors',
	'/_admin/mailer/hint/errors-empty' 	: 'No failed sends recorded.',
	'/_admin/mailer/error/load' 				: 'Failed to load the status.',
	'/_admin/mailer/error/send' 				: 'Failed to send the test mail.',
	'/_admin/error/mailer_address' 			: 'That is not an email address.',
};

const wrap = element('div');
wrap.id = 'mailer-form';
const requests = [];
const ready = [];
let answers = {};

/**
 *	What a failed request is told as, by the workbench's rule: the sentence of the
 *	server's code, else the server's own line, else the panel's sentence - the
 *	status number in front of the last two
 */
function errorText( status, response, key ) {
	const body = response !== null && typeof response === 'object' ? response : {};
	if( typeof body.code === 'string' && texts['/_admin/error/'+ body.code] !== undefined )
		return texts['/_admin/error/'+ body.code];
	return '('+ status+ ') '+ ( body.error || texts[key] );
}

/*	The namespace the panel script extends - a local stand-in, not the global,
	so the file lints like a browser script with the rest of them. The workbench
	is a stand-in too, kept to what the manual says of the members the script
	calls: api.call() keeps its requests and answers from the table above;
	panel() is the lifecycle the manual describes - the screen is asked for
	once, when the page is ready, and drawn by render() from the answer, a
	failure said in the mount with the panel's key	*/
const nino = {
	admin		: {},
	adminUi	: {
		emptyState : function( message ) { const empty = element('p'); empty.className = 'nino-admin-empty'; empty.textContent = message; return empty },
		hint : function( text, kind ) { const p = element('p'); p.className = kind === 'error' ? 'nino-admin-hint nino-admin-error' : 'nino-admin-hint'; p.textContent = text; return p },
		el : function( tag, className, text ) { const el = element( tag ); if( className ) el.className = className; if( text !== undefined ) el.textContent = text; return el },
		actionBar : function( bar ) { bar.classList.add('nino-admin-actionbar'); return bar },
		textField : function( options ) {
			const label = element('label');
			label.className = 'nino-admin-field';
			const input = element('input');
			input.type = options.type || 'text';
			input.id = options.id || '';
			input.required = options.required === true;
			input.value = options.value ?? '';
			label.children.push( element('span'), input );
			label.children[0].textContent = options.label;
			return label;
		},
		button : function( text, onClick, options ) {
			const button = element('button');
			button.type = options.type === 'submit' ? 'submit' : 'button';
			button.className = options.className || '';
			button.id = options.id || '';
			button.textContent = text;
			return button;
		},
		// The live line: the state and the words of it, an error through errorText() and the field the server named marked
		status : function( el, labels ) {
			const control = {
				element : el,
				state		: 'idle',
				saving	: function() { control.state = 'saving'; el.dataset.state = 'saving'; el.textContent = labels.saving },
				saved		: function() { control.state = 'saved'; el.dataset.state = 'saved'; el.textContent = labels.saved },
				error		: function( status, response, key ) {
					control.state = 'error';
					el.dataset.state = 'error';
					el.textContent = nino.adminUi.api.errorText( status, response, key );
					const field = typeof response === 'object' && response !== null && response.field ? form().querySelector( '[name="'+ response.field+ '"]' ) : null;
					if( field !== null )
						field.setAttribute( 'aria-invalid', 'true' );
				},
			};
			return control;
		},
		api : {
			call : function( action, payload, callback ) {
				requests.push( { action : action, payload : payload } );
				const answer = answers[action];
				callback( answer[0], answer[1] );
			},
			errorText : errorText,
		},
		panel : function( spec ) {
			const self = {
				uri			: spec.uri,
				ready		: false,
				loading	: false,
				data		: null,
				mount		: function() { return sandbox.document.getElementById( spec.mount ) },
				call		: function( action, payload, done ) { nino.adminUi.api.call( spec.uri+ '/'+ action, payload, done ) },
				fail		: function( container, status, response ) { container.innerHTML = ''; container.appendChild( nino.adminUi.el( 'p', 'nino-admin-error', errorText( status, response, '/_admin/'+ spec.uri+ '/error/load' ) ) ) },
				init		: function() {
					if( self.loading === true || self.mount() === null )
						return;
					self.loading = true;
					self.call( spec.action || 'list', spec.payload || {}, function( status, response ) {
						self.loading = false;
						if( status === 200 ) {
							self.data = response;
							self.ready = true;
							spec.render( response, self );
							return;
						}
						self.fail( self.mount(), status, response );
						self.ready = false;
					} );
				},
				showCurrent : function() {
					if( self.ready === false || spec.show === 'always' )
						self.init();
				},
			};
			// The ready event starts it: the test fires it when the page is
			if( spec.boot === 'ready' )
				ready.push( function() { self.init() } );
			return self;
		},
	},
	content	: { getText : function( key ) { return Object.prototype.hasOwnProperty.call( texts, key ) ? texts[key] : key } },
};
const sandbox = { console : console, Nino : nino, document : { createElement : function( tag ) { return element( tag ) }, getElementById : function( id ) { return id === 'mailer-form' ? wrap : null } } };
sandbox.window = sandbox;
vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

const mailer = nino.admin.mailer;
const find = function( id ) { return descendants( wrap ).filter( function( el ) { return el.id === id } )[0] };
const form = function() { return descendants( wrap ).filter( function( el ) { return el.tagName === 'FORM' } )[0] };
const actions = function() { return requests.map( function( request ) { return request.action } ).join(',') };

// --- A fresh screen -------------------------------------------------------------

check( 'nothing is asked, and nothing is drawn, before the page is ready', requests.length === 0 && wrap.children.length === 0 && ready.length === 1 );

answers['mailer/status'] = [ 200, { host : 'smtp.example.com', port : 465, encryption : 'tls', testTo : 'me@example.org', errors : [] } ];
ready.forEach( function( start ) { start() } );
check( 'when it is, the screen asks the server for its status once', actions() === 'mailer/status' && JSON.stringify( requests[0].payload ) === '{}' );
check( 'the screen starts with its hint, and draws no heading of its own - the head over the pane names the panel',
	wrap.children[0].className === 'nino-admin-hint' && wrap.children[0].textContent === 'Sends a test mail.'
	&& descendants( wrap ).filter( function( el ) { return /^H[1-6]$/.test( el.tagName ) } ).length === 0 );
check( 'the status line names the port a send really uses', find('mailer-status').textContent === 'Sending through smtp.example.com:465 (tls).' );
check( 'the address field is a required email field named to, holding the one the server offered', find('mailer-to').type === 'email' && find('mailer-to').required === true
	&& find('mailer-to').name === 'to' && find('mailer-to').value === 'me@example.org' );
check( '...in a label that says what it is for', descendants( wrap ).filter( function( el ) { return el.tagName === 'LABEL' } )[0].children[0].textContent === 'Send a test mail to' );
check( 'the send button submits the form, in the bar with the live line', find('mailer-send').type === 'submit' && find('mailer-send').className === 'nino-admin-btn-primary'
	&& find('mailer-send').textContent === 'Send test mail' && form().children[1].className === 'nino-admin-actionbar'
	&& form().children[1].children[0] === find('mailer-msg') && form().children[1].children[1] === find('mailer-send') );
check( 'no errors is the empty state component',  descendants( find('mailer-errors') ).some( function( el ) { return el.className === 'nino-admin-empty' } ) );
check( 'no errors says so', find('mailer-errors').children.map( function( el ) { return el.textContent } ).join('|') === 'Last errors|No failed sends recorded.' );

mailer.showCurrent();
check( 'showing the pane again asks nothing: what was typed stays', requests.length === 1 );

// --- Sending a test mail -----------------------------------------------------------

const sendTo = function( address ) {
	find('mailer-to').value = address;
	requests.length = 0;
	form().listeners.submit[0]( { preventDefault : function() {} } );
};

answers['mailer/test'] = [ 200, { sent : true } ];
answers['mailer/status'] = [ 200, { host : 'smtp.example.com', port : 587, encryption : 'starttls', testTo : 'other@example.org', errors : [
	{ date : '2026-10-02 10:00:00', reason : 'could not connect to smtp.example.com:587' },
	{ date : '2026-10-01 09:00:00', reason : '<img src=x onerror=alert(1)>' },
] } ];
sendTo('typed@example.org');
check( 'sending posts the address the field holds, and asks for the status again after it', actions() === 'mailer/test,mailer/status' && requests[0].payload.to === 'typed@example.org' );
check( 'the live line says the mail is sent, and the button is there to use again', find('mailer-msg').dataset.state === 'saved' && find('mailer-msg').textContent === 'Test mail sent.' && find('mailer-send').disabled === false );
check( 'an address somebody typed is not written over by the offered one', find('mailer-to').value === 'typed@example.org' );
check( 'the status line follows the new answer', find('mailer-status').textContent === 'Sending through smtp.example.com:587 (starttls).' );

const items = descendants( find('mailer-errors') ).filter( function( el ) { return el.tagName === 'LI' } );
check( 'a failure is listed with its date, newest first', items.length === 2 && items[0].textContent === '2026-10-02 10:00:00 - could not connect to smtp.example.com:587' );
check( 'the list is a dense list of rows', descendants( find('mailer-errors') ).some( function( el ) { return el.tagName === 'UL' && el.className === 'nino-admin-list nino-admin-list-dense' } ) );
check( 'a reason is text, never markup', items[1].textContent === '2026-10-01 09:00:00 - <img src=x onerror=alert(1)>' && items[1].children.length === 0 );
check( 'the list replaces what was there instead of adding to it', descendants( find('mailer-errors') ).filter( function( el ) { return el.className === 'nino-admin-eyebrow' } ).length === 1 );

// --- A refusal -------------------------------------------------------------------------

answers['mailer/test'] = [ 400, { error : 'not a valid email address', code : 'mailer_address', field : 'to' } ];
sendTo('not-an-address');
check( 'an address that is none is told in the sentence of its code', find('mailer-msg').dataset.state === 'error' && find('mailer-msg').textContent === 'That is not an email address.' );
check( '...and the field the server named is marked', find('mailer-to').attributes['aria-invalid'] === 'true' && find('mailer-to').value === 'not-an-address' );

answers['mailer/test'] = [ 500, { error : 'mailbox unavailable' } ];
sendTo('refused@example.org');
check( 'a transport failure shows what the mail server said, the status in front of it', find('mailer-msg').textContent === '(500) mailbox unavailable' && find('mailer-send').disabled === false );

answers['mailer/test'] = [ 502, null ];
sendTo('a@example.org');
check( 'an answer with nothing in it is told in the panel\'s own sentence', find('mailer-msg').textContent === '(502) Failed to send the test mail.' );

// --- The status cannot be had -------------------------------------------------------------

answers['mailer/test'] = [ 200, { sent : true } ];
answers['mailer/status'] = [ 503, { error : 'Busy' } ];
sendTo('typed@example.org');
check( 'a status that fails to load after a test mail is said on the status line, and the form stays', find('mailer-status').textContent === '(503) Busy'
	&& find('mailer-to').value === 'typed@example.org' && find('mailer-msg').textContent === 'Test mail sent.' );

// --- Nothing configured -------------------------------------------------------------

answers['mailer/status'] = [ 200, { host : '', port : 587, encryption : 'starttls', testTo : '', errors : [] } ];
sendTo('typed@example.org');
check( 'with no host the status line says it is not configured', find('mailer-status').textContent === 'Not configured yet' );

// --- A screen that cannot be drawn ---------------------------------------------------------

answers['mailer/status'] = [ 500, null ];
requests.length = 0;
ready.forEach( function( start ) { start() } );
check( 'a status that fails to load is said in the mount, in the panel\'s own sentence', actions() === 'mailer/status' && wrap.children.length === 1
	&& wrap.children[0].className === 'nino-admin-error' && wrap.children[0].textContent === '(500) Failed to load the status.' );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
