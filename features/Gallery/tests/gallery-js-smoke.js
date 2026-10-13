/**
 *	Nino
 *	gallery-js-smoke.js		What the Gallery panel's script does over a dom
 *												stand-in, on the workbench's own helpers: the
 *												Nino.js and the Nino.admin.js of the checkout it runs
 *												against are the real ones, and the stand-in is only
 *												what a browser and the shell are - a document that is
 *												loading until the test says the page is ready, the
 *												words a page carries, the wire, the router and the
 *												language the workbench works in. The tiles it
 *												draws for an album, the alt text and the caption in
 *												the language the switch is on, what it saves when a
 *												field is left and what it does not, what moving and
 *												deleting an image or an album do, and what an upload
 *												of several files leaves on the screen - when all of
 *												them arrive, when one of them does not, and when one
 *												is too big to be sent at all. And the three levels of
 *												the panel - the list, the page of a new album, one
 *												album's page - with the address that names each: the
 *												button below the list, the page that makes an album
 *												from a key, a name and the sizes of its pictures, and
 *												the album's own page with its key shown, its name and
 *												its sizes saved. A refusal is said in the words of its
 *												code.
 *
 *												The panel talks to Admin/Admin.php through the
 *												workbench's request, and every answer carries the
 *												whole album list. The stand-in server answers the list
 *												itself and keeps every other request until the test
 *												answers it, so a batch can be stopped halfway and two
 *												saves can be answered in the wrong order. The panel
 *												asks for the list when the page is ready, and it is
 *												the kernel's Nino.events that says when: the test
 *												fires DOMContentLoaded at the document and runs the
 *												timers Nino.js asked for, as a browser does.
 *
 *												No jsdom: the element stand-in of the checkout's
 *												tests/harness.js, which is there from Nino 1.8 on,
 *												as the feature is. The checkout is the one NINO_ROOT
 *												names, else the one three levels up.
 *												gallery-smoke.php runs this through node when node is
 *												on the path, so bin/check.sh and CI cover it; it also
 *												runs on its own.
 *
 *	Usage: node features/Gallery/tests/gallery-js-smoke.js
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

/** A sentence of the words with its placeholders filled in the order they stand */
function fill( key, ...params ) {
	let at = 0;
	return WORDS[key].replace( /%[sdn]/g, function( token ) { return at < params.length ? String( params[at++] ) : token } );
}

/** The text of an element and of everything below it - a text node is a child here */
function textOf( el ) {
	return el.textContent + el.children.map( textOf ).join('');
}

/** Every descendant of $root with a class */
function withClass( root, name ) {
	return findAll( root, function( el ) { return String( el.className ).split(/\s+/).indexOf( name ) !== -1 } );
}

/** Every descendant of $root with a tag */
function withTag( root, tag ) {
	return findAll( root, function( el ) { return el.tagName === tag } );
}

/** The two JSON shapes the same, key order and all - what a request is compared by */
function same( a, b ) {
	return JSON.stringify( a ) === JSON.stringify( b );
}

// What the answer of gallery/list says about the sizes: what a new album starts with, and the range each number may take
const DEFAULTS = { thumbWidth : 500, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : true, columns : 4 };
const RANGES = { thumbWidth : [ 40, 1200 ], thumbHeight : [ 40, 1200 ], largeWidth : [ 200, 4000 ], largeHeight : [ 200, 4000 ], columns : [ 1, 8 ] };

// What the album of the tests is made with - not what a new one starts with, so a screen that reads the wrong one is seen
const SIZES = { thumbWidth : 300, thumbHeight : 200, largeWidth : 1200, largeHeight : 800, keepRatio : true, columns : 3 };

// A project with two languages
const BILINGUAL = { locales : [ 'de_DE', 'en_US' ], native : 'de_DE' };

/** One album as the panel is handed it - always with its six settings, which the server resolves */
function album( images, settings ) {
	return { key : 'haus', name : 'Haus am See', images : images, settings : Object.assign( {}, SIZES, settings || {} ) };
}

/** One image as the panel is handed it: a text is a string or a map of language to string */
function picture( id, caption, alt ) {
	return { id : id, caption : caption, alt : alt === undefined ? '' : alt, thumbUrl : '/content/gallery/haus/'+ id+ '-thumb.jpg' };
}

/**
 *	The panel, shown by the shell and answered by a stand-in server
 *
 *	@param		{Object}	options		{ images, albums, address, locale, list, fail, hold, confirm, start } -
 *																images: the pictures of the album 'haus' the list answers, none unless it says,
 *																albums: the albums it answers instead,
 *																address: what the address is, #gallery/haus unless it says,
 *																locale: the language the workbench is working in,
 *																list: more of what gallery/list answers - the languages, the limits, the length of a key,
 *																fail: { status, body } answers the list with a failure,
 *																hold: true keeps the answer to the list until release() gives it,
 *																confirm: what window.confirm() answers,
 *																start: 'show' (the shell shows the panel - the default), 'ready' (the page is ready),
 *																'both' or 'none' - a page that is still loading, whose ready() the test calls
 */
function panel( options ) {

	options = options || {};

	const body = element('body');
	const list = element('div');
	const pane = element('div');

	list.id = 'gallery-list';
	pane.id = 'gallery-album';
	body.appendChild( list );
	body.appendChild( pane );

	// Every request the panel made, the list included, and the ones the test has not answered yet
	const calls = [];
	const requests = [];
	const held = [];

	// What window.confirm() was asked, and what the panel did to the address
	const asked = [];
	const written = [];
	const moved = [];
	const chosen = [];

	// What gallery/list answers: the server's, which a test may change before the panel asks again
	const listing = Object.assign( {
		albums : options.albums || [ album( options.images || [] ) ],
		defaults : DEFAULTS,
		ranges : RANGES,
		locales : [ 'de_DE' ],
		native : 'de_DE',
		limits : { bytes : 0, text : '' },
	}, options.list || {} );

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

		const endpoint = data.action.replace( /^gallery\//, '' );

		const request = {
			uri			 : uri,
			method	 : method,
			action	 : data.action,
			endpoint : endpoint,
			json		 : data.data,
			payload	 : JSON.parse( data.data ),
			// The file of an upload goes with the request as a field of its own
			extra		 : data.file === undefined ? {} : { file : data.file },
			answer	 : function( status, response ) { callback( { status : status, responseJSON : response } ) },
		};

		calls.push( request );

		if( endpoint !== 'list' )
			return requests.push( request );

		const give = function() { options.fail ? request.answer( options.fail.status, options.fail.body ) : request.answer( 200, listing ) };

		if( options.hold === true )
			return held.push( give );

		give();
	};

	/*	The shell: the router reads the address and writes it over the entry the browser is on (set), and
		adds an entry for a move the person made (go) - both only for the panel that is on screen. The
		language the workbench is working in, and the toolbar every level of a panel has	*/
	let hash = options.address || '#gallery/haus';
	let onScreen = hash.replace( /^#/, '' ).split('/')[0] === 'gallery';

	const address = function( name, parts ) { return '#'+ [ name ].concat( parts ).join('/') };

	Nino.admin = {
		formToolbar : function( child ) { const bar = element('div'); bar.className = 'nino-admin-toolbar'; bar.appendChild( child ); return bar },
		sessionLocale : {
			current : options.locale || null,
			init : function( locale ) { if( Nino.admin.sessionLocale.current === null ) Nino.admin.sessionLocale.current = locale },
			set : function( locale ) { Nino.admin.sessionLocale.current = locale; chosen.push( locale ) },
		},
		router : {
			current : function() {
				const parts = hash.replace( /^#/, '' ).split('/').filter( function( part ) { return part !== '' } );
				return { panel : parts[0] || '', parts : parts.slice( 1 ) };
			},
			set : function( name, parts ) {
				if( onScreen === false || address( name, parts ) === hash )
					return;
				written.push( address( name, parts ) );
				hash = address( name, parts );
			},
			go : function( name, parts ) {
				if( onScreen === false || address( name, parts ) === hash )
					return;
				moved.push( address( name, parts ) );
				hash = address( name, parts );
			},
		},
	};

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

	const gallery = Nino.admin.gallery;
	const start = options.start || 'show';

	// The page is ready, and the shell shows the panel: either one asks for the list
	if( start === 'ready' || start === 'both' )
		ready();
	if( start === 'show' || start === 'both' )
		gallery.showCurrent();

	return {
		gallery		: gallery,
		list			: list,
		pane			: pane,
		calls			: calls,
		requests	: requests,
		asked			: asked,
		/** The page becomes ready */
		ready			: ready,
		/** What the panel wrote into the address, and what it asked the history to keep as a step */
		written		: written,
		moved			: moved,
		/** The languages the workbench was told were chosen */
		chosen		: chosen,
		/** What gallery/list answers: the albums may be changed before the panel asks again */
		listing		: listing,
		/** The answer the list was kept for */
		release		: function() { held.splice( 0 ).forEach( function( give ) { give() } ) },
		/** The answer to the request the panel is waiting for */
		answer		: function( status, response ) { requests.shift().answer( status, response ) },
		/** The address now */
		address		: function() { return hash },
		/** A step through the browser's history: the address changes, and the panel it names is the one on screen. The shell answers by asking that panel to show again */
		step			: function( to ) { hash = to; onScreen = to.replace( /^#/, '' ).split('/')[0] === 'gallery' },
		/** The panel is brought on screen - from the rail - while the address is still the one of the panel that was */
		rail			: function() { onScreen = true },
		/** Any element of the page - the two panes included - by its id */
		byId			: function( id ) { return findAll( body, function( el ) { return el.id === id } )[0] || null },
		/** Which of the two panes is on screen: 'list', 'album' - or null where neither is */
		shown			: function() { return list.classList.contains('admin-hidden') === false ? 'list' : ( pane.classList.contains('admin-hidden') === false ? 'album' : null ) },
		/** The tiles on the screen, in the order they stand in */
		tiles			: function() { return withClass( pane, 'gallery-tile' ) },
		/** One tile's caption field, its alt text field and the line it reports into */
		caption		: function( index ) { return findAll( this.tiles()[index], function( el ) { return el.dataset.field === 'caption' } )[0] },
		alt				: function( index ) { return findAll( this.tiles()[index], function( el ) { return el.dataset.field === 'alt' } )[0] },
		line			: function( index ) { return withClass( this.tiles()[index], 'gallery-tile-msg' )[0] },
		/** One tile's buttons: move earlier, delete, move later */
		buttons		: function( index ) { return withTag( this.tiles()[index], 'BUTTON' ) },
		/** The language switch in the toolbar, or undefined where there is none */
		select		: function() { return withTag( pane, 'SELECT' )[0] },
		/** The file field, and the line under it the panel says things in */
		upload		: function() { return this.byId('gallery-upload') },
		said			: function() { const said = this.byId('gallery-upload-msg'); return said === null ? null : said.textContent },
		/** Choose files, the way a visitor does - of one kilobyte each unless it says */
		choose		: function( names, sizes ) {
			const input = this.upload();
			input.files = names.map( function( name, index ) { return { name : name, size : ( sizes || [] )[index] || 1024 } } );
			fire( input, 'change' );
		},
	};
}


// --- The panel on the workbench's own helpers ----------------------------------------

console.log( 'The panel' );

const waiting = panel( { start : 'none' } );

check( 'the panel has asked for nothing while the page is loading and the shell has not shown it', waiting.calls.length === 0 );

waiting.ready();

check( '...and asks for the list once the page is ready: Nino.js runs the callback the panel bound to the event',
	waiting.calls.length === 1 && waiting.calls[0].action === 'gallery/list' && waiting.shown() === 'album' );

const both = panel( { start : 'both', hold : true } );

check( 'the page being ready and the shell\'s first showing, with the answer still out, ask for the list once',
	both.calls.length === 1 );
check( '...through the workbench\'s own request: the project\'s _admin, a post, the action and a json payload',
	both.calls[0].uri === '/_admin/' && both.calls[0].method === 'POST' && both.calls[0].action === 'gallery/list' && both.calls[0].json === '{}' );

both.release();

check( 'the answer draws the level the address names, and nothing else', both.shown() === 'album' && both.byId('gallery-album-key').value === 'haus'
	&& both.list.children.length === 0 && both.calls.length === 1 );

const failed = panel( { fail : { status : 503, body : { error : 'Busy' } } } );

check( 'a load that failed is said in the list\'s pane in the workbench\'s words for it: the status and the message',
	withClass( failed.list, 'nino-admin-error' ).length === 1 && withClass( failed.list, 'nino-admin-error' )[0].textContent === '(503) Busy' );

failed.gallery.showCurrent();

check( '...and the next showing asks again, since the panel is not loaded', failed.calls.length === 2 && failed.gallery.ready === false );

console.log('');


// --- One album's screen ----------------------------------------------------------

console.log( 'The tiles' );

const two = panel( { images : [ picture( 'a1', 'Vorderseite' ), picture( 'a2', '' ) ] } );

check( 'one tile per image, in the order the album has them',
	two.tiles().length === 2 && two.tiles()[0].dataset.image === 'a1' && two.tiles()[1].dataset.image === 'a2' );
check( '...each with the caption it carries in the field that changes it',
	two.caption( 0 ).value === 'Vorderseite' && two.caption( 1 ).value === '' );
check( '...and an alt text field of its own beside it, both labelled for somebody who cannot see the placeholder',
	two.alt( 0 ).value === '' && two.alt( 0 ).placeholder === 'Alt text' && two.alt( 0 ).getAttribute('aria-label') === 'Alt text'
	&& two.caption( 0 ).getAttribute('aria-label') === 'Caption' );
check( '...the picture is named by the caption only while it has no alt text of its own',
	withTag( two.tiles()[0], 'IMG' )[0].alt === 'Vorderseite' && withTag( two.tiles()[1], 'IMG' )[0].alt === '' );

const described = panel( { images : [ picture( 'a1', 'Vorderseite', 'Das Haus von vorn' ) ] } );

check( 'with an alt text the picture is named by it, not by the caption', withTag( described.tiles()[0], 'IMG' )[0].alt === 'Das Haus von vorn'
	&& described.alt( 0 ).value === 'Das Haus von vorn' && described.caption( 0 ).value === 'Vorderseite' );

check( 'a project with one language has no switch', two.select() === undefined );

const alone = panel( { images : [] } );

check( 'an album with nothing in it says so rather than drawing an empty grid',
	alone.tiles().length === 0 && withClass( alone.pane, 'nino-admin-empty' ).length === 1
	&& withClass( alone.pane, 'nino-admin-empty' )[0].textContent === WORDS['/_admin/gallery/hint/no-images'] );

console.log('');


// --- The caption -----------------------------------------------------------------

console.log( 'The caption' );

const captioned = panel( { images : [ picture( 'a1', '' ) ] } );

captioned.caption( 0 ).value = 'Gartenseite';
fire( captioned.caption( 0 ), 'blur' );

check( 'a caption that was changed is saved when the field is left, with the language it was written in',
	captioned.requests.length === 1 && captioned.requests[0].endpoint === 'image-save'
	&& captioned.requests[0].payload.caption === 'Gartenseite' && captioned.requests[0].payload.id === 'a1'
	&& captioned.requests[0].payload.album === 'haus' && captioned.requests[0].payload.locale === 'de_DE' );
check( '...and only the caption: the alt text stays as the server has it', captioned.requests[0].payload.alt === undefined );

captioned.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );

check( '...and the tile is left standing while it is, rather than rebuilt under the cursor',
	captioned.tiles().length === 1 && captioned.caption( 0 ).value === 'Gartenseite' );

fire( captioned.caption( 0 ), 'blur' );

check( 'a caption nobody changed is not sent again', captioned.requests.length === 0 );

/*	The tile is not rebuilt after a caption was saved, so the image object it
	was drawn from is the only record of what is saved. Where that record stays
	at the caption the page was loaded with, going back to it - clearing a
	caption that was empty when the screen was drawn - reads as "nothing
	changed" while the server holds what it was sent in between	*/
captioned.caption( 0 ).value = '';
fire( captioned.caption( 0 ), 'blur' );

check( 'a caption put back to what the page was loaded with is saved too, not read as no change at all',
	captioned.requests.length === 1 && captioned.requests[0].endpoint === 'image-save'
	&& captioned.requests[0].payload.caption === '' );

captioned.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );

const texted = panel( { images : [ picture( 'a1', '' ) ] } );

texted.alt( 0 ).value = 'Das Haus von vorn';
fire( texted.alt( 0 ), 'blur' );

check( 'an alt text is saved the same way, and only it',
	texted.requests.length === 1 && texted.requests[0].payload.alt === 'Das Haus von vorn' && texted.requests[0].payload.caption === undefined
	&& texted.requests[0].payload.locale === 'de_DE' );

texted.answer( 200, { albums : [ album( [ picture( 'a1', '', { de_DE : 'Das Haus von vorn' } ) ] ) ] } );
fire( texted.alt( 0 ), 'blur' );

check( '...and what the server answered is what the field is compared with next', texted.requests.length === 0 );

/*	The server keeps a text trimmed and cut to 300 bytes. A field holding it as it
	was typed - with blanks round it, or longer - never equals what comes back,
	and every blur sent it again	*/
const padded = panel( { images : [ picture( 'a1', '' ) ] } );

padded.caption( 0 ).value = '  Gartenseite \n';
fire( padded.caption( 0 ), 'blur' );

check( 'a text with blanks round it is sent as typed', padded.requests.length === 1 && padded.requests[0].payload.caption === '  Gartenseite \n' );

padded.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );
fire( padded.caption( 0 ), 'blur' );

check( '...and is not sent again on the next blur, the server having kept it trimmed', padded.requests.length === 0 );

padded.caption( 0 ).value = 'Gartenseite';
fire( padded.caption( 0 ), 'blur' );

check( '...nor when the blanks are taken off by hand', padded.requests.length === 0 );

padded.caption( 0 ).value = 'Gartenseite ';
fire( padded.caption( 0 ), 'blur' );

check( 'a no-break space is not a blank to php\'s trim(), so that one is a change', padded.requests.length === 1 );

padded.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite ' } ) ] ) ] } );

const longText = 'ä'.repeat( 200 );
const kept = 'ä'.repeat( 150 );
const cut = panel( { images : [ picture( 'a1', '' ) ] } );

cut.caption( 0 ).value = longText;
fire( cut.caption( 0 ), 'blur' );
cut.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : kept } ) ] ) ] } );
fire( cut.caption( 0 ), 'blur' );

check( 'a text longer than the server keeps is sent once, not on every blur', cut.requests.length === 0 );
check( '...300 bytes on a character boundary: a two-byte character is never cut in half', cut.gallery._kept( longText ) === kept
	&& cut.gallery._kept( 'a'+ 'ä'.repeat( 200 ) ) === 'a'+ 'ä'.repeat( 149 )
	&& cut.gallery._kept( '\u{1F600}'.repeat( 100 ) ) === '\u{1F600}'.repeat( 75 ) );

cut.gallery._maxText = 10;

check( '...and the limit is the one the server named', cut.gallery._kept( 'abcdefghijklmnop' ) === 'abcdefghij' );

const limited10 = panel( { images : [], list : { maxText : 120 } } );

check( 'the answer of the list names how much of a text the server keeps', limited10.gallery._maxText === 120 );

// Two saves in flight, answered the wrong way round: the answer to the alt
// text predates the caption's, and must not take the caption back
const twice = panel( { images : [ picture( 'a1', '' ) ] } );

twice.alt( 0 ).value = 'Alt';
fire( twice.alt( 0 ), 'blur' );
twice.caption( 0 ).value = 'Caption';
fire( twice.caption( 0 ), 'blur' );
twice.requests[1].answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Caption' }, '' ) ] ) ] } );
twice.requests[0].answer( 200, { albums : [ album( [ picture( 'a1', '', { de_DE : 'Alt' } ) ] ) ] } );
twice.requests.length = 0;
fire( twice.caption( 0 ), 'blur' );
fire( twice.alt( 0 ), 'blur' );

check( 'an answer that predates the other field\'s save does not make that field read as unsaved', twice.requests.length === 0 );

console.log('');


// --- One language at a time -------------------------------------------------------

console.log( 'The languages' );

const images = [ picture( 'a1', { de_DE : 'Vorderseite', en_US : 'Front' }, { de_DE : 'Das Haus', en_US : 'The house' } ), picture( 'a2', 'Gartenseite', '' ), picture( 'a3', { en_US : 'Only English' }, '' ) ];
const languages = panel( { images : images, list : BILINGUAL } );

check( 'a project with more than one language has the workbench\'s own switch in the album\'s toolbar',
	languages.select() !== undefined && withClass( languages.pane, 'nino-admin-toolbar' )[0].children.indexOf( languages.select() ) !== -1
	&& languages.select().className.indexOf('nino-admin-locale-select') !== -1 && languages.select().getAttribute('aria-label') === 'Language' );
check( '...one option per language, the native one selected while the workbench has chosen nothing',
	withTag( languages.select(), 'OPTION' ).map( function( el ) { return el.value+ ( el.selected ? '*' : '' ) } ).join(' ') === 'de_DE* en_US' );
check( 'the fields hold that language\'s texts, and a plain string is every language\'s',
	languages.alt( 0 ).value === 'Das Haus' && languages.caption( 0 ).value === 'Vorderseite' && languages.caption( 1 ).value === 'Gartenseite' );
check( '...a language a text was not written in is an empty field, not another language\'s text', languages.caption( 2 ).value === '' );
check( 'the thumbnail is named in that language too - the caption standing in only where there is no alt text',
	withTag( languages.tiles()[0], 'IMG' )[0].alt === 'Das Haus' && withTag( languages.tiles()[1], 'IMG' )[0].alt === 'Gartenseite'
	&& withTag( languages.tiles()[2], 'IMG' )[0].alt === 'Only English' );

languages.select().value = 'en_US';
fire( languages.select(), 'change' );

check( 'switching the language tells the workbench, and redraws the fields in it', languages.chosen.join() === 'en_US'
	&& languages.alt( 0 ).value === 'The house' && languages.caption( 0 ).value === 'Front' && languages.caption( 1 ).value === 'Gartenseite'
	&& languages.caption( 2 ).value === 'Only English' && withTag( languages.select(), 'OPTION' ).filter( function( el ) { return el.selected } )[0].value === 'en_US' );

languages.caption( 1 ).value = 'Garden';
fire( languages.caption( 1 ), 'blur' );

check( 'a text written after the switch is saved for the new language', languages.requests.length === 1
	&& languages.requests[0].payload.locale === 'en_US' && languages.requests[0].payload.caption === 'Garden' && languages.requests[0].payload.id === 'a2' );

languages.answer( 200, { albums : [ album( [ images[0], picture( 'a2', { de_DE : 'Gartenseite', en_US : 'Garden' }, '' ), images[2] ] ) ] } );

languages.select().value = 'de_DE';
fire( languages.select(), 'change' );

check( '...and the other language still has what it had', languages.caption( 1 ).value === 'Gartenseite' && languages.alt( 0 ).value === 'Das Haus' );

const remembered = panel( { images : images, list : BILINGUAL, locale : 'en_US' } );

check( 'the language the workbench was last working in is the one the screen opens in', remembered.alt( 0 ).value === 'The house' );

const stale = panel( { images : images, list : BILINGUAL, locale : 'fr_FR' } );

check( '...unless the project no longer has it', stale.alt( 0 ).value === 'Das Haus' );

// What the list action answers is taken as it comes: the languages, the one the
// workbench is in, and what php takes
const listed0 = panel( { images : [], list : Object.assign( { selectedLocale : 'en_US', limits : { bytes : 2097152, text : '2 MB' } }, BILINGUAL ) } );

check( 'the answer to gallery/list sets the languages, the one to start in and the upload limit',
	listed0.gallery._locales.join() === 'de_DE,en_US' && listed0.gallery._native === 'de_DE' && listed0.gallery._limits.bytes === 2097152
	&& listed0.gallery._locale() === 'en_US' );

listed0.listing.albums = [ album( [] ) ];
delete listed0.listing.locales;
delete listed0.listing.limits;
listed0.gallery.reload();

check( '...and an answer that carries none of it leaves them as they were', listed0.gallery._locales.length === 2 && listed0.gallery._limits.text === '2 MB' );

console.log('');


// --- Uploading several at once ---------------------------------------------------

console.log( 'The upload' );

const batch = panel( { images : [] } );

check( 'the field takes several files at once - a gallery is filled from a folder', batch.upload().multiple === true );

batch.choose( [ 'haus-1.jpg', 'haus-2.jpg' ] );

check( 'one request at a time, because every answer carries the whole album list',
	batch.requests.length === 1 && batch.requests[0].endpoint === 'upload' && batch.requests[0].extra.file.name === 'haus-1.jpg'
	&& same( batch.requests[0].payload, { album : 'haus' } ) );
check( '...and the screen says which file is on its way', batch.said() === 'Uploading haus-1.jpg …' );

batch.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );

check( 'the next file follows the answer to the one before it',
	batch.requests.length === 1 && batch.requests[0].extra.file.name === 'haus-2.jpg' );

batch.answer( 200, { albums : [ album( [ picture( 'a1', '' ), picture( 'a2', '' ) ] ) ] } );

check( 'a finished batch leaves every picture of it on the screen, and the word about it where the field says things',
	batch.tiles().length === 2 && batch.said() === '2 added.' && batch.upload().disabled === false );

const halted = panel( { images : [] } );

halted.choose( [ 'haus-1.jpg', 'kaputt.tif' ] );
halted.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );
halted.answer( 400, { error : 'the file could not be read', code : 'gallery_upload' } );

/*	The picture that did arrive is on the server and in _albums; a grid that
	goes on showing the album as it was before the batch says the upload did
	nothing at all	*/
check( 'a batch that stopped halfway draws the pictures that did arrive, and says which file went wrong and why, in the words of the code',
	halted.tiles().length === 1 && halted.said() === 'kaputt.tif: The file could not be read.' );
check( '...and the field takes files again rather than staying disabled', halted.upload().disabled === false );

const silent = panel( { images : [] } );

silent.choose( [ 'haus-1.jpg' ] );
silent.answer( 403, null );

check( 'an answer with nothing in it - a request php dropped - is still said as the file it belongs to, with the status',
	silent.said() === 'haus-1.jpg: (403) The file could not be read.' );

const heavy = panel( { images : [] } );

heavy.choose( [ 'riesig.png' ] );
heavy.answer( 400, { error : 'the image is larger than 8 MB', code : 'image_too_large', params : [ 8 ] } );

check( 'a refusal of the kernel\'s own image rules is said in the kernel\'s words, with its figures in them',
	heavy.said() === 'riesig.png: The image is larger than 8 MB.' );

const wrongType = panel( { images : [] } );

wrongType.choose( [ 'notiz.txt' ] );
wrongType.answer( 400, { error : 'not an image', code : 'image_type' } );

check( '...and so is a file that is no image', wrongType.said() === 'notiz.txt: '+ WORDS['/_admin/error/image_type'] );

const unmade = panel( { images : [] } );

unmade.choose( [ 'haus-1.jpg' ] );
unmade.answer( 400, { error : 'the server could not make the two sizes from this image', code : 'gallery_process' } );

check( 'a picture the server could not make the sizes of is said in the panel\'s own words', unmade.said() === 'haus-1.jpg: '+ WORDS['/_admin/error/gallery_process'] );

const broken = panel( { images : [] } );

broken.choose( [ 'haus-1.jpg' ] );
broken.answer( 500, { error : 'the albums could not be written' } );

check( 'a failure with no code - the albums could not be written - is told with its status and the message the server gave',
	broken.said() === 'haus-1.jpg: (500) the albums could not be written' );

console.log('');


// --- A file too big to send ---------------------------------------------------------

console.log( 'The limit' );

const limited = panel( { images : [], list : { limits : { bytes : 2 * 1024 * 1024, text : '2 MB' } } } );

limited.choose( [ 'haus-1.jpg', 'riesig.jpg', 'haus-3.jpg' ], [ 1024, 5 * 1024 * 1024, 1024 ] );

check( 'a file under the limit goes to the server as it always did', limited.requests.length === 1 && limited.requests[0].extra.file.name === 'haus-1.jpg' );

limited.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );

check( 'one over it is not sent at all - php would drop the whole request and answer with a refusal that names no cause', limited.requests.length === 0 );
check( '...it is named, with the limit', limited.said() === 'riesig.jpg: The file is larger than 2 MB.' );
check( '...the batch stops there, the picture before it is on the screen, and the field takes files again',
	limited.tiles().length === 1 && limited.upload().disabled === false );

const unlimited = panel( { images : [] } );

unlimited.choose( [ 'riesig.jpg' ], [ 5 * 1024 * 1024 * 1024 ] );

check( 'where php sets no limit nothing is held back', unlimited.requests.length === 1 );

console.log('');


// --- The list of albums --------------------------------------------------------------
//
// The list is the design system's row of buttons, the shape every other list
// a screen drills into has: the whole row opens the album and a chevron says
// so. It used to be a row with two buttons on it, a red Delete first - the one
// thing on a list a hand hits by mistake - so Delete lives on the album's own
// screen now, where the pictures it takes with it are in view

console.log( 'The list' );

const listed = panel( { address : '#gallery', images : [ picture( 'a', '' ) ] } );
const rowLists = withClass( listed.list, 'nino-admin-list-buttons' );
const rows = rowLists.length === 1 ? rowLists[0].children.filter( function( el ) { return el.tagName === 'BUTTON' } ) : [];
const row = rows[0] || element('div');

check( 'the list is a row of buttons, one per album', rowLists.length === 1 && rows.length === 1 && row.dataset.album === 'haus' );
check( '...that names the album, its shortcode and how many pictures it holds', withTag( row, 'STRONG' )[0].textContent === 'Haus am See'
	&& withTag( row, 'SMALL' )[0].textContent === fill( '/_admin/gallery/hint/shortcode', '[gallery album="haus"]' )+ ' · '+ fill( '/_admin/gallery/label/count', 1 ) );
check( '...with nothing on it but the way in', withClass( listed.list, 'nino-admin-btn-danger' ).length === 0 && withClass( row, 'admin-view-button-chev' ).length === 1 );

fire( row, 'click' );

check( 'the row opens the album, as a step the history keeps', listed.gallery._open === 'haus' && same( listed.moved, [ '#gallery/haus' ] ) && listed.shown() === 'album' && listed.tiles().length === 1 );
check( '...and Delete is on the album\'s screen, under its pictures', withClass( listed.pane, 'gallery-album-actions' ).length === 1
	&& withClass( withClass( listed.pane, 'gallery-album-actions' )[0], 'nino-admin-btn-danger' ).length === 1 );

console.log('');


// --- Saying what happened ------------------------------------------------------------

console.log( 'The status line' );

const lined = panel( { images : [ picture( 'a1', '' ) ] } );
const tileLine = lined.line( 0 );

check( 'the message of a tile is the workbench\'s status line, and quiet until something is said',
	tileLine.classList.contains('nino-admin-status') && tileLine.getAttribute('role') === 'status' && tileLine.textContent === '' );

lined.caption( 0 ).value = 'Gartenseite';
fire( lined.caption( 0 ), 'blur' );

check( 'it says the text is on its way', tileLine.dataset.state === 'saving' && tileLine.textContent === 'Saving …' );

lined.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );

check( '...and when it is saved, and at what time',
	tileLine.dataset.state === 'saved' && /^Saved at \d\d:\d\d\.$/.test( tileLine.textContent ) );

lined.caption( 0 ).value = 'Zu lang';
fire( lined.caption( 0 ), 'blur' );
lined.answer( 400, { error : 'there is no album with this key', code : 'gallery_album' } );

check( 'a refusal is told in the words of its code - and as an alert, not as a status',
	tileLine.dataset.state === 'error' && tileLine.textContent === 'No album has that key.' && tileLine.getAttribute('role') === 'alert' );

lined.caption( 0 ).value = 'Noch länger';
fire( lined.caption( 0 ), 'blur' );
lined.answer( 500, { error : 'the albums could not be written' } );

check( 'a failure of the server\'s own, which has no code, is told as it is, with its status', tileLine.textContent === '(500) the albums could not be written' );

lined.caption( 0 ).value = 'Wieder etwas anderes';
fire( lined.caption( 0 ), 'blur' );
lined.answer( 403, null );

check( 'an answer with nothing in it is told in the panel\'s own words for a save that failed, with its status', tileLine.textContent === '(403) Failed to save.' );

console.log('');


// --- Moving and deleting an image ---------------------------------------------------------

console.log( 'Moving and deleting' );

const three = panel( { images : [ picture( 'a1', '' ), picture( 'a2', '' ), picture( 'a3', '' ) ] } );
const first = three.buttons( 0 );
const last = three.buttons( 2 );

check( 'a tile carries its three controls in the workbench\'s row, spread to both edges: move earlier, delete, move later',
	withClass( three.tiles()[0], 'nino-admin-actions--between' ).length === 1 && withClass( three.tiles()[0], 'nino-admin-actions' ).length === 1
	&& first.length === 3 && first[0].textContent === '‹' && first[1].textContent === 'Delete' && first[2].textContent === '›' );
check( '...all small, the delete the red one and the moves the quiet ones', first.every( function( button ) { return button.className.indexOf('nino-admin-btn--small') !== -1 } )
	&& first[1].className.indexOf('nino-admin-btn-danger') !== -1 && first[0].className.indexOf('nino-admin-btn-secondary') !== -1 );
check( '...the arrows are named for a screen reader, which would say "button ‹"', first[0].getAttribute('aria-label') === 'Move one place earlier' && first[0].title === 'Move one place earlier'
	&& first[2].getAttribute('aria-label') === 'Move one place later' );
check( '...and the first image cannot be moved earlier, nor the last one later', first[0].disabled === true && first[2].disabled === false && last[2].disabled === true && last[0].disabled === false );

fire( first[2], 'click' );

check( 'moving an image posts the whole order, not the move - the server checks it against what it has',
	three.requests.length === 1 && three.requests[0].endpoint === 'reorder' && same( three.requests[0].payload, { album : 'haus', order : [ 'a2', 'a1', 'a3' ] } ) );

three.answer( 200, { albums : [ album( [ picture( 'a2', '' ), picture( 'a1', '' ), picture( 'a3', '' ) ] ) ] } );

check( '...and the answer is what the grid is drawn from', three.tiles().map( function( tile ) { return tile.dataset.image } ).join() === 'a2,a1,a3' && three.buttons( 0 )[0].disabled === true );

fire( three.buttons( 1 )[0], 'click' );
three.answer( 400, { error : 'the order did not name every image of the album', code : 'gallery_order' } );

check( 'an order the server refuses is said on the tile in the words of its code, and the grid stays as it was',
	three.line( 1 ).textContent === 'The order did not name every image of the album - nothing was changed.' && three.tiles().map( function( tile ) { return tile.dataset.image } ).join() === 'a2,a1,a3' );

fire( three.buttons( 0 )[1], 'click' );

check( 'deleting an image asks first', three.asked.length === 1 && three.asked[0] === WORDS['/_admin/gallery/confirm/image'] );
check( '...then posts the album and the image, and holds the button until it is answered',
	three.requests.length === 1 && three.requests[0].endpoint === 'image-delete' && same( three.requests[0].payload, { album : 'haus', id : 'a2' } )
	&& three.buttons( 0 )[1].disabled === true && three.line( 0 ).textContent === 'Deleting …' );

three.answer( 400, { error : 'that image is not in this album', code : 'gallery_image' } );

check( 'a refusal gives the button back and says why', three.buttons( 0 )[1].disabled === false && three.line( 0 ).textContent === 'That image is not in this album.' );

fire( three.buttons( 0 )[1], 'click' );
three.answer( 200, { albums : [ album( [ picture( 'a1', '' ), picture( 'a3', '' ) ] ) ] } );

check( 'an image that is deleted is gone from the grid', three.tiles().map( function( tile ) { return tile.dataset.image } ).join() === 'a1,a3' );

const unsure = panel( { images : [ picture( 'a1', '' ) ], confirm : false } );

fire( unsure.buttons( 0 )[1], 'click' );

check( 'an image is not deleted when the question is answered no', unsure.asked.length === 1 && unsure.requests.length === 0 && unsure.buttons( 0 )[1].disabled === false );

const doomed = panel( { images : [ picture( 'a1', '' ), picture( 'a2', '' ) ] } );
const bar = withClass( doomed.pane, 'gallery-album-actions' )[0];
const remove = withTag( bar, 'BUTTON' )[0];

check( 'the album\'s own delete stands in the bar under the grid, with the line it answers in beside it',
	bar.className.indexOf('nino-admin-actionbar') !== -1 && remove.className.indexOf('nino-admin-btn-danger') !== -1 && remove.textContent === 'Delete'
	&& withClass( bar, 'nino-admin-status' ).length === 1 );

fire( remove, 'click' );

check( 'deleting an album asks first, with its name and how many images go with it', doomed.asked.length === 1 && doomed.asked[0] === fill( '/_admin/gallery/confirm/album', 'Haus am See', 2 ) );
check( '...then posts the album, and holds the button until it is answered',
	doomed.requests.length === 1 && doomed.requests[0].endpoint === 'album-delete' && same( doomed.requests[0].payload, { album : 'haus' } )
	&& remove.disabled === true && withClass( bar, 'nino-admin-status' )[0].textContent === 'Deleting …' );

doomed.answer( 400, { error : 'there is no album with this key', code : 'gallery_album' } );

check( 'a refusal gives the button back and says why', remove.disabled === false && withClass( bar, 'nino-admin-status' )[0].textContent === 'No album has that key.' );

fire( remove, 'click' );
doomed.answer( 200, { albums : [] } );

check( 'an album that was deleted takes its screen with it: the list is shown, and the address goes back to it',
	doomed.shown() === 'list' && doomed.address() === '#gallery' && doomed.gallery._open === '' && withClass( doomed.list, 'nino-admin-empty' ).length === 1 );

const spared = panel( { images : [], confirm : false } );

fire( withTag( withClass( spared.pane, 'gallery-album-actions' )[0], 'BUTTON' )[0], 'click' );

check( 'an album is not deleted when the question is answered no', spared.asked.length === 1 && spared.requests.length === 0 );

console.log('');


// --- The way to a new album -----------------------------------------------------------
//
// The list ends in one button, the way the Elements panel's does, and an album is
// made on a page of its own

console.log( 'The button below the list' );

const noAlbums = panel( { address : '#gallery', albums : [] } );

check( 'the list holds no field: the key and the name are asked on a page of their own',
	withTag( noAlbums.list, 'FORM' ).length === 0 && withTag( noAlbums.list, 'INPUT' ).length === 0 );
check( 'it ends in one button, in the bar the workbench pins below a list, that says what it does',
	withClass( noAlbums.list, 'nino-admin-list-actions' ).length === 1 && withTag( noAlbums.list, 'BUTTON' ).length === 1
	&& withTag( noAlbums.list, 'BUTTON' )[0].textContent === 'Add album' && withTag( noAlbums.list, 'BUTTON' )[0].className === 'nino-admin-btn-primary'
	&& noAlbums.list.children[noAlbums.list.children.length - 1].className.indexOf( 'nino-admin-list-actions' ) !== -1 );
check( '...below the empty state, and below the rows where there are some', noAlbums.list.children[0].className === 'nino-admin-empty'
	&& withClass( listed.list, 'nino-admin-list-actions' ).length === 1 && listed.list.children.indexOf( withClass( listed.list, 'nino-admin-list-actions' )[0] ) > listed.list.children.indexOf( rowLists[0] ) );
check( 'the list is on screen at the address #gallery', noAlbums.shown() === 'list' && noAlbums.address() === '#gallery' );

withTag( noAlbums.list, 'BUTTON' )[0].listeners.click.forEach( function( fn ) { fn( {} ) } );

check( 'the button opens the page of a new album: a step the history keeps, the address #gallery/new, in the pane of an album\'s screen',
	same( noAlbums.moved, [ '#gallery/new' ] ) && noAlbums.address() === '#gallery/new' && noAlbums.shown() === 'album' && noAlbums.gallery._creating === true
	&& noAlbums.byId( 'gallery-new-key' ) !== null );

console.log('');


// --- The page of a new album ------------------------------------------------------------

console.log( 'The page of a new album' );

const fresh = panel( { address : '#gallery/new' } );
const freshKey = fresh.byId( 'gallery-new-key' );
const freshName = fresh.byId( 'gallery-new-name' );
const freshForm = fresh.byId( 'gallery-new-form' );
const freshInput = function( name ) { return findAll( freshForm, function( el ) { return el.dataset.key === name } )[0] || null };

check( 'it is the pane of an album\'s screen, the list\'s pane hidden, and the address says new - and the list was never drawn to get there',
	fresh.shown() === 'album' && fresh.address() === '#gallery/new' && fresh.list.children.length === 0 );
check( '...with the toolbar every level of the panel has: the way back, and no language to switch - nothing here is written in one',
	withClass( fresh.pane, 'nino-admin-toolbar' ).length === 1 && withClass( fresh.pane, 'nino-admin-back-link' ).length === 1
	&& withClass( fresh.pane, 'nino-admin-back-link' )[0].textContent === 'Back to list' && fresh.select() === undefined );
check( '...and a heading that says what the page is for', withTag( fresh.pane, 'H3' ).length === 1 && withTag( fresh.pane, 'H3' )[0].textContent === 'Add album' );

check( 'the key is a text field of its own, which a screen reader is told cannot be left empty - and not through the browser\'s own check, which takes a key of blanks',
	freshKey.getAttribute('aria-required') === 'true' && freshKey.required === true && freshForm.noValidate === true && freshKey.readOnly !== true && freshKey.value === '' );
check( '...limited to the length of key the server will make an album with: 64, until the answer of the list names another',
	freshKey.maxLength === 64 );

const longer = panel( { address : '#gallery/new', list : { maxKey : 40 } } );

check( '...and then that one: the server holds a key to the same number', longer.byId( 'gallery-new-key' ).maxLength === 40 && longer.gallery._maxKey === 40 );

const keyName = freshKey.parentNode.children[0];

check( '...with the mark after its name that is drawn for the eye and hidden from a reader, and the hint under it that tells what a key is made of and that it stays',
	textOf( keyName ) === 'Key*' && withClass( keyName, 'nino-admin-required' ).length === 1 && withClass( keyName, 'nino-admin-required' )[0].getAttribute('aria-hidden') === 'true'
	&& withClass( freshForm, 'nino-admin-field-hint' )[0].textContent === WORDS['/_admin/gallery/hint/key']
	&& freshForm.children.indexOf( withClass( freshForm, 'nino-admin-field-hint' )[0] ) === freshForm.children.indexOf( freshKey.parentNode ) + 1 );
check( 'the name is a field of its own, empty and not required', freshName.value === '' && freshName.getAttribute('aria-required') === null && textOf( freshName.parentNode.children[0] ) === 'Name' );

const group = withTag( fresh.pane, 'DETAILS' );

check( 'the sizes are one group named Image sizes - open here, because they are part of what is decided',
	group.length === 1 && group[0].open === true && withTag( group[0], 'SUMMARY' )[0].textContent === 'Image sizes' );
check( '...six fields in the order the Features screen has them: the sizes, whether the ratio is kept, and the columns',
	[ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'keepRatio', 'columns' ].every( function( name ) { return freshInput( name ) !== null } )
	&& withTag( group[0], 'INPUT' ).map( function( input ) { return input.dataset.key } ).join() === 'thumbWidth,thumbHeight,largeWidth,largeHeight,keepRatio,columns' );
check( '...that start as what the feature\'s settings are, and not as the sizes of any album', [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ].every( function( name ) { return freshInput( name ).value === String( DEFAULTS[name] ) } )
	&& freshInput( 'keepRatio' ).checked === true );
check( '...each number limited by the range the server will hold it to', freshInput( 'thumbWidth' ).min === 40 && freshInput( 'thumbWidth' ).max === 1200
	&& freshInput( 'largeHeight' ).min === 200 && freshInput( 'largeHeight' ).max === 4000 && freshInput( 'columns' ).min === 1 && freshInput( 'columns' ).max === 8 );
check( '...and the fields that have a word to say have it', withTag( group[0], 'SMALL' ).map( function( small ) { return small.textContent } ).join('|')
	=== [ 'thumb-width', 'large-width', 'keep-ratio', 'columns' ].map( function( name ) { return WORDS['/_admin/gallery/hint/'+ name] } ).join('|') );

const freshUpload = fresh.upload();

check( 'the upload field is drawn - and greyed out, with the word that says when it will be on',
	freshUpload !== null && freshUpload.disabled === true && freshUpload.parentNode.className.indexOf( 'gallery-upload-off' ) !== -1 && fresh.said() === 'Create the album, then you can upload images.'
	&& freshUpload.getAttribute('aria-describedby') === 'gallery-upload-msg' );

const freshBar = withClass( freshForm, 'nino-admin-actionbar' );
const freshMsg = fresh.byId( 'gallery-new-msg' );

check( 'the page ends in the action bar of a form: the button that makes the album, and the status line it answers in',
	freshBar.length === 1 && withTag( freshBar[0], 'BUTTON' ).length === 1 && withTag( freshBar[0], 'BUTTON' )[0].type === 'submit' && withTag( freshBar[0], 'BUTTON' )[0].textContent === 'Create'
	&& freshMsg.parentNode === freshBar[0] && freshMsg.classList.contains('nino-admin-status') );

// What a form says of a key it has none of
freshKey.value = '   ';
fire( freshForm, 'submit' );

check( 'a key left empty is not sent: the field is marked, has the focus, and the line says what a key is - the sentence of the code the server would have refused it with',
	fresh.requests.length === 0 && freshKey.getAttribute('aria-invalid') === 'true' && freshKey.focused === true && freshMsg.textContent === WORDS['/_admin/error/gallery_key']
	&& freshMsg.dataset.state === 'error' );

fire( freshKey, 'input' );

check( '...and what is typed into it takes the mark away', freshKey.getAttribute('aria-invalid') === null );

freshKey.value = ' neu ';
freshName.value = ' Neu ';
freshInput( 'thumbWidth' ).value = '120';
freshInput( 'columns' ).value = '6';
freshInput( 'keepRatio' ).checked = false;
fire( freshForm, 'submit' );

check( 'the button makes the album with album-save: the key and the name trimmed, the six sizes as numbers and a boolean, and the word that this is a new album',
	fresh.requests.length === 1 && fresh.requests[0].endpoint === 'album-save'
	&& same( fresh.requests[0].payload, { album : 'neu', name : 'Neu', settings : { thumbWidth : 120, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : false, columns : 6 }, isNew : true } ) );
check( '...and nothing can be pressed twice while it is on its way', withTag( freshBar[0], 'BUTTON' )[0].disabled === true && freshMsg.textContent === 'Saving …' && freshMsg.dataset.state === 'saving' );

fresh.answer( 400, { error : 'there is an album with this key already', code : 'gallery_taken' } );

check( 'a refusal - the key is taken - is said in the line in the words of its code, and the page stays as it is, with what was typed',
	freshMsg.textContent === 'An album with this key exists already.' && withTag( freshBar[0], 'BUTTON' )[0].disabled === false
	&& fresh.gallery._creating === true && freshKey.value === ' neu ' && freshInput( 'thumbWidth' ).value === '120' && fresh.address() === '#gallery/new' );

fresh.moved.length = 0;
fresh.written.length = 0;
fire( freshForm, 'submit' );
fresh.answer( 200, { albums : [ { key : 'neu', name : 'Neu', images : [], settings : { thumbWidth : 120, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : false, columns : 6 } } ] } );

check( 'a new album opens its own page: the address is written over the one of the new-album page - no step to come back to a page that would make it twice - and names the album',
	fresh.gallery._open === 'neu' && fresh.gallery._creating === false && fresh.moved.length === 0 && same( fresh.written, [ '#gallery/neu' ] ) && fresh.address() === '#gallery/neu' && fresh.shown() === 'album' );
check( '...with its key shown and read only, its name in a field, and the upload field on', fresh.byId( 'gallery-album-key' ).value === 'neu' && fresh.byId( 'gallery-album-key' ).readOnly === true
	&& fresh.byId( 'gallery-album-name' ).value === 'Neu' && fresh.upload().disabled === false && fresh.byId( 'gallery-new-key' ) === null );
check( '...and the sizes it was made with are the ones the page says an upload will become', withClass( fresh.pane, 'gallery-sizes' )[0].textContent === fill( '/_admin/gallery/hint/sizes-crop', '120×500', '1800×1800' ) );

const back = panel( { address : '#gallery/new' } );

fire( withClass( back.pane, 'nino-admin-back-link' )[0], 'click' );

check( 'the back link is a step the history keeps, and leaves the page for the list', same( back.moved, [ '#gallery' ] ) && back.shown() === 'list' && back.gallery._creating === false && back.address() === '#gallery' );

console.log('');


// --- One album's page ---------------------------------------------------------------------

console.log( 'One album\'s page' );

const one = panel( { images : [ picture( 'a1', 'Vorderseite' ) ] } );
const oneKey = one.byId( 'gallery-album-key' );
const oneName = one.byId( 'gallery-album-name' );
const oneTitle = withTag( one.pane, 'H3' )[0];
const oneForm = withClass( one.pane, 'gallery-name-form' )[0];
const oneLine = function() { return withClass( oneForm, 'gallery-form-msg' )[0] };

check( 'the key of an album is shown and cannot be changed - the shortcode names it and the files are filed under it - with the line to put into a template',
	oneKey.value === 'haus' && oneKey.readOnly === true && textOf( oneKey.parentNode.children[0] ) === 'Key'
	&& withClass( oneForm, 'nino-admin-field-hint' )[0].textContent === fill( '/_admin/gallery/hint/shortcode', '[gallery album="haus"]' ) );
check( 'the name is a field that can be written', oneName.value === 'Haus am See' && oneName.readOnly !== true && oneTitle.textContent === 'Haus am See' );
check( '...with a Save of its own and a status line to answer in, in the workbench\'s row of actions',
	withClass( oneForm, 'nino-admin-actions' ).length === 1 && withTag( oneForm, 'BUTTON' ).length === 1 && withTag( oneForm, 'BUTTON' )[0].type === 'submit'
	&& withTag( oneForm, 'BUTTON' )[0].textContent === 'Save' && oneLine().classList.contains('nino-admin-status') && oneLine().textContent === '' );

const oneTile = one.tiles()[0];

oneName.value = ' Haus am Meer ';
fire( oneForm, 'submit' );

check( 'the name is saved with album-save and the name alone: no sizes, and not as a new album',
	one.requests.length === 1 && one.requests[0].endpoint === 'album-save' && same( one.requests[0].payload, { album : 'haus', name : 'Haus am Meer' } ) && oneLine().textContent === 'Saving …' );

one.answer( 200, { albums : [ Object.assign( album( [ picture( 'a1', 'Vorderseite' ) ] ), { name : 'Haus am Meer' } ) ] } );

check( 'what the server kept is what the page says - the heading, the field and the list - and the page is not drawn again: the pictures and the cursor stay',
	oneTitle.textContent === 'Haus am Meer' && oneName.value === 'Haus am Meer' && one.gallery._album( 'haus' ).name === 'Haus am Meer' && one.tiles()[0] === oneTile
	&& oneLine().dataset.state === 'saved' && /^Saved at \d\d:\d\d\.$/.test( oneLine().textContent ) );

oneName.value = '';
fire( oneForm, 'submit' );
one.answer( 200, { albums : [ Object.assign( album( [ picture( 'a1', 'Vorderseite' ) ] ), { name : 'Haus am Meer' } ) ] } );

check( 'a name emptied is kept as it was by the server, and the field says so', oneName.value === 'Haus am Meer' && oneTitle.textContent === 'Haus am Meer' );

oneName.value = 'x';
fire( oneForm, 'submit' );
one.answer( 500, { error : 'the albums could not be written' } );

check( 'a failure is said in the line, and nothing on the page changes', oneLine().textContent === '(500) the albums could not be written' && oneLine().dataset.state === 'error'
	&& oneTitle.textContent === 'Haus am Meer' && one.gallery._album( 'haus' ).name === 'Haus am Meer' );

oneName.value = 'y';
fire( oneForm, 'submit' );
one.answer( 400, { error : 'there is no album with this key', code : 'gallery_album' } );

check( 'a refusal is said in the words of its code', oneLine().textContent === 'No album has that key.' );

// The sizes
const sizesForm = withClass( one.pane, 'gallery-sizes-form' )[0];
const sizesGroup = withTag( sizesForm, 'DETAILS' )[0];
const sizeInput = function( name ) { return findAll( sizesForm, function( el ) { return el.dataset.key === name } )[0] || null };
const sizesLine = function() { return withClass( sizesGroup, 'gallery-form-msg' )[0] };

check( 'the sizes of the album are a group that is folded away, named Image sizes', withClass( one.pane, 'gallery-sizes-group' ).length === 1 && sizesGroup.open === false
	&& withTag( sizesGroup, 'SUMMARY' )[0].textContent === 'Image sizes' );
check( '...whose fields hold the sizes of this album - not what a new one starts with - limited by the same ranges', [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ].every( function( name ) { return sizeInput( name ).value === String( SIZES[name] ) } )
	&& sizeInput( 'keepRatio' ).checked === true && sizeInput( 'columns' ).max === 8 );
check( '...with the word that they apply to what is uploaded from then on, and a Save of their own',
	withClass( sizesGroup, 'nino-admin-hint' ).filter( function( p ) { return p.textContent === WORDS['/_admin/gallery/hint/settings'] } ).length === 1
	&& withTag( sizesGroup, 'BUTTON' ).length === 1 && withTag( sizesGroup, 'BUTTON' )[0].type === 'submit' && withTag( sizesGroup, 'BUTTON' )[0].textContent === 'Save' );
check( 'what an upload becomes is said in the album\'s own sizes, above the field that uploads', withClass( one.pane, 'gallery-sizes' )[0].textContent === fill( '/_admin/gallery/hint/sizes-fit', '300×200', '1200×800' )
	&& one.pane.children.indexOf( withClass( one.pane, 'gallery-sizes' )[0] ) < one.pane.children.indexOf( one.upload().parentNode ) );

sizesGroup.open = true;
sizeInput( 'thumbWidth' ).value = '150';
sizeInput( 'columns' ).value = '5';
sizeInput( 'keepRatio' ).checked = false;
fire( sizesForm, 'submit' );

check( 'the sizes are saved with album-save and the sizes alone: the album and the six values, no name',
	one.requests.length === 1 && same( one.requests[0].payload, { album : 'haus', settings : { thumbWidth : 150, thumbHeight : 200, largeWidth : 1200, largeHeight : 800, keepRatio : false, columns : 5 } } ) );

one.answer( 200, { albums : [ album( [ picture( 'a1', 'Vorderseite' ) ], { thumbWidth : 150, keepRatio : false, columns : 5 } ) ] } );

check( 'what was saved is what the page says of an upload from now on, and the group stays open under the cursor rather than folding away',
	withClass( one.pane, 'gallery-sizes' )[0].textContent === fill( '/_admin/gallery/hint/sizes-crop', '150×200', '1200×800' ) && sizesGroup.open === true
	&& one.gallery._album( 'haus' ).settings.columns === 5 && sizesLine().dataset.state === 'saved' && one.tiles()[0] === oneTile );

sizeInput( 'columns' ).value = '9';
fire( sizesForm, 'submit' );
one.answer( 400, { error : 'these sizes are outside what is allowed: columns', code : 'gallery_settings', params : [ 'Columns (1–8)' ] } );

check( 'sizes the server refuses are said in the words of the code, and the page keeps what it had',
	sizesLine().textContent === 'These values are outside what is allowed: Columns (1–8).'
	&& withClass( one.pane, 'gallery-sizes' )[0].textContent === fill( '/_admin/gallery/hint/sizes-crop', '150×200', '1200×800' ) && one.gallery._album( 'haus' ).settings.columns === 5 );

sizeInput( 'thumbHeight' ).value = '';
fire( sizesForm, 'submit' );

check( 'a number that is none is sent as none: the server refuses it with the rest, rather than the panel guessing', one.requests.length === 1 && one.requests[0].payload.settings.thumbHeight === null );

one.requests.length = 0;

const backOne = panel( { images : [] } );

fire( withClass( backOne.pane, 'nino-admin-back-link' )[0], 'click' );

check( 'the back link of an album\'s page is a step the history keeps, and leads to the list', same( backOne.moved, [ '#gallery' ] ) && backOne.shown() === 'list' && backOne.gallery._open === '' );
check( 'the address of an album\'s page is its key', panel( { images : [] } ).address() === '#gallery/haus' );

console.log('');


// --- The address --------------------------------------------------------------------------

console.log( 'The address' );

/*	The address is read before anything is drawn, because drawing writes it: a list drawn first
	and the album after would have taken the album's address back to #gallery by then	*/
const reload = panel( { address : '#gallery/haus' } );

check( 'a page opened on the address of an album opens on that album - and the list is never drawn first, so the address is never taken back',
	reload.shown() === 'album' && reload.gallery._open === 'haus' && reload.written.length === 0 && reload.address() === '#gallery/haus'
	&& reload.list.children.length === 0 && reload.byId( 'gallery-album-key' ).value === 'haus' );
check( '...with what the list answered: what a new album starts with, and the ranges', same( reload.gallery._defaults, DEFAULTS ) && same( reload.gallery._ranges, RANGES ) );

const reloadNew = panel( { address : '#gallery/new' } );

check( '...and on the address of the page of a new album it opens that page, with the sizes the feature has', reloadNew.shown() === 'album' && reloadNew.gallery._creating === true
	&& reloadNew.written.length === 0 && reloadNew.list.children.length === 0 && findAll( reloadNew.byId( 'gallery-new-form' ), function( el ) { return el.dataset.key === 'columns' } )[0].value === '4' );

const reloadGone = panel( { address : '#gallery/gone' } );

check( 'an address that names an album nobody has is the list, and the address says so', reloadGone.shown() === 'list' && reloadGone.gallery._open === '' && reloadGone.gallery._creating === false
	&& reloadGone.address() === '#gallery' && same( reloadGone.written, [ '#gallery' ] ) );

const reloadBare = panel( { address : '#gallery' } );

check( 'the plain address is the list', reloadBare.shown() === 'list' && reloadBare.address() === '#gallery' && reloadBare.written.length === 0 );

const reloadOther = panel( { address : '#dashboard' } );

check( 'an address that names another panel leaves this one on its list, and the address as it is: the panel is not the one on screen',
	reloadOther.shown() === 'list' && reloadOther.gallery._open === '' && reloadOther.gallery._creating === false && reloadOther.address() === '#dashboard' && reloadOther.written.length === 0 );

const newKey = panel( { address : '#gallery/new', albums : [ Object.assign( album( [] ), { key : 'new', name : 'Neues' } ) ] } );

check( 'an album whose key is "new" is opened by its address, not mistaken for the page of a new one', newKey.shown() === 'album' && newKey.gallery._open === 'new' && newKey.gallery._creating === false
	&& newKey.byId( 'gallery-album-key' ).value === 'new' );

/*	A step through the browser's history changes the address and nothing else; the shell answers
	by asking the panel to show again, and the level the address names is the one shown	*/
const stepped = panel( { address : '#gallery', albums : [ album( [] ) ] } );

stepped.step( '#gallery/haus' );
stepped.gallery.showCurrent();

check( 'a step through the history to an album shows it', stepped.shown() === 'album' && stepped.gallery._open === 'haus' && stepped.byId( 'gallery-album-key' ).value === 'haus' );

stepped.step( '#gallery/new' );
stepped.gallery.showCurrent();

check( '...to the page of a new album shows that', stepped.gallery._creating === true && stepped.byId( 'gallery-new-key' ) !== null && stepped.gallery._open === '' );

stepped.step( '#gallery' );
stepped.gallery.showCurrent();

check( '...and back to the plain address shows the list', stepped.shown() === 'list' && stepped.gallery._creating === false && stepped.gallery._open === '' );

stepped.step( '#gallery/haus' );
stepped.gallery.showCurrent();
stepped.step( '#elements/services' );
stepped.rail();
stepped.gallery.showCurrent();

check( 'an address that names another panel leaves the level in memory as it is: the panel keeps its drill-down state while another is on screen, and writes its own address when it is brought back',
	stepped.gallery._open === 'haus' && stepped.shown() === 'album' && stepped.address() === '#gallery/haus' );

/*	The panel is opened again - from the rail, after another panel was on screen. A page that is drawn for
	the level the panel is on is shown as it was: the key that was half typed is still there	*/
const away = panel( { address : '#gallery/new' } );
const typed = away.byId( 'gallery-new-key' );

typed.value = 'half-typed';
away.step( '#dashboard' );
away.rail();
away.gallery.showCurrent();

check( 'the page of a new album is shown as it was when the panel is opened again: the field is the same and holds what was typed, and the address is the page\'s',
	away.byId( 'gallery-new-key' ) === typed && typed.value === 'half-typed' && away.address() === '#gallery/new' && away.shown() === 'album' );

const awayAlbum = panel( { images : [ picture( 'a1', '' ) ] } );
const awayName = awayAlbum.byId( 'gallery-album-name' );

awayName.value = 'half-typed';
awayAlbum.step( '#dashboard' );
awayAlbum.rail();
awayAlbum.gallery.showCurrent();

check( '...and so is the page of an album, with the name that was being changed', awayAlbum.byId( 'gallery-album-name' ) === awayName && awayName.value === 'half-typed' && awayAlbum.address() === '#gallery/haus' );

awayAlbum.step( '#gallery' );
awayAlbum.gallery.showCurrent();
awayAlbum.step( '#gallery/haus' );
awayAlbum.gallery.showCurrent();

check( 'a page for another level is drawn: back from the list to the album, the field is a new one with the name the album has', awayAlbum.shown() === 'album' && awayAlbum.byId( 'gallery-album-name' ).value === 'Haus am See' );

// An album that is gone by hand, in the file, is not there when the list is asked for again
const deleted = panel( { images : [] } );

deleted.listing.albums = [];
deleted.gallery.reload();

check( 'an album that is gone takes its page with it, and the address goes back to the list', deleted.shown() === 'list' && deleted.address() === '#gallery' && deleted.gallery._open === '' );

done();
