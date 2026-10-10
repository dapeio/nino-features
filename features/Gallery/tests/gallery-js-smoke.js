/**
 *	Nino
 *	gallery-js-smoke.js		What the Gallery panel's script does over a dom
 *												stand-in: the tiles it draws for an album, the
 *												alt text and the caption in the language the
 *												switch is on, what it saves when a field is left
 *												and what it does not, and what an upload of
 *												several files leaves on the screen - when all of
 *												them arrive, when one of them does not, and when
 *												one is too big to be sent at all. And the three
 *												levels of the panel - the list, the page of a new
 *												album, one album's page - with the address that
 *												names each: the button below the list, the page
 *												that makes an album from a key, a name and the
 *												sizes of its pictures, and the album's own page
 *												with its key shown, its name and its sizes saved.
 *
 *												The panel talks to Admin/Admin.php through one
 *												method, _apiCall(), and every answer carries the
 *												whole album list. That method is the seam this test
 *												holds: it records what was asked and hands the
 *												answer back when the test says so, so a batch can
 *												be stopped halfway.
 *
 *												No jsdom, no dependency: the same element stand-in
 *												the other feature tests build, so this runs with
 *												nothing but node. gallery-smoke.php runs it through
 *												node when node is on the path, so bin/check.sh and
 *												CI cover it; it also runs on its own.
 *
 *	Usage: node features/Gallery/tests/gallery-js-smoke.js
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

// The panel's own words, as text/en_US.php and the workbench's common texts
// carry them - the ones this test reads back off the screen
const TEXT = {
	'/_admin/gallery/msg/uploading'	: 'Uploading %s …',
	'/_admin/gallery/msg/uploaded'	: '%s added.',
	'/_admin/gallery/error/upload'	: 'The file could not be read.',
	'/_admin/gallery/error/size'		: 'The file is larger than %s.',
	'/_admin/gallery/label/caption'	: 'Caption',
	'/_admin/gallery/label/alt'			: 'Alt text',
	'/_admin/gallery/label/locale'	: 'Language',
	'/_admin/common/msg/saving'			: 'Saving …',
	'/_admin/common/msg/saved'			: 'Saved.',
	'/_admin/common/error/save'			: 'Failed to save.',
	'/_admin/gallery/hint/shortcode'	: 'Put %s into a template or a text',
	'/_admin/gallery/label/count'		: '%s images',
	'/_admin/gallery/label/new'			: 'Add album',
	'/_admin/gallery/label/key'			: 'Key',
	'/_admin/gallery/label/name'		: 'Name',
	'/_admin/gallery/label/sizes'		: 'Image sizes',
	'/_admin/gallery/label/upload'	: 'Add images',
	'/_admin/gallery/label/thumb-width'		: 'Thumbnail width',
	'/_admin/gallery/label/thumb-height'	: 'Thumbnail height',
	'/_admin/gallery/label/large-width'		: 'Large width',
	'/_admin/gallery/label/large-height'	: 'Large height',
	'/_admin/gallery/label/keep-ratio'		: 'Keep the original ratio',
	'/_admin/gallery/label/columns'				: 'Columns',
	'/_admin/gallery/hint/key'						: 'Lower case letters, digits and hyphens.',
	'/_admin/gallery/hint/settings'				: 'These sizes apply to images uploaded from now on.',
	'/_admin/gallery/hint/upload-after'		: 'Create the album, then you can upload images.',
	'/_admin/gallery/hint/thumb-width'		: 'Thumbnails are cropped to exactly this.',
	'/_admin/gallery/hint/large-width'		: 'The size behind a thumbnail.',
	'/_admin/gallery/hint/keep-ratio'			: 'On, the large view is the whole picture.',
	'/_admin/gallery/hint/columns'				: 'How many thumbnails a row holds.',
	'/_admin/gallery/hint/sizes-fit'			: 'A thumbnail cropped to %1, and a large view scaled into %2.',
	'/_admin/gallery/hint/sizes-crop'			: 'A thumbnail cropped to %1, and a large view cropped to %2.',
	'/_admin/gallery/error/key'						: 'An album key is a slug.',
	'/_admin/gallery/error/taken'					: 'An album with this key exists already.',
	'/_admin/common/label/back'						: 'Back to list',
	'/_admin/common/label/create'					: 'Create',
	'/_admin/common/label/save'						: 'Save',
};

// The elements that were given the focus, in the order they were
const focused = [];

/**
 *	One element, with just enough of the interface admin.js reaches for
 */
function element( tag ) {

	const el = {
		tagName			: String( tag ).toUpperCase(),
		children		: [],
		parent			: null,
		dataset			: {},
		attributes	: {},
		classes			: {},
		listeners		: {},
		className		: '',
		textContent	: '',
		value				: '',
		disabled		: false,
		readOnly		: false,
		open				: false,
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		removeAttribute	: function( name ) { delete this.attributes[name] },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		fire						: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {} } ) } ) },
		focus						: function() { focused.push( this ) },
		// By tag, or by the key a field carries: the two ways the script finds an input in a field
		querySelector		: function( selector ) {
			const key = /^\[data-key="([^"]+)"\]$/.exec( selector );
			return descendants( this ).filter( function( child ) { return key === null ? child.tagName === selector.toUpperCase() : child.dataset.key === key[1] } )[0] || null;
		},
	};

	el.classList = {
		toggle		: function( name, on ) { if( on === true ) el.classes[name] = true; else delete el.classes[name] },
		contains	: function( name ) { return el.classes[name] === true },
	};

	// The one thing the panel empties a pane with
	Object.defineProperty( el, 'innerHTML', {
		get : function() { return '' },
		set : function( value ) {
			if( String( value ) !== '' )
				throw new Error( 'the stand-in only takes innerHTML = \'\'' );
			el.children.forEach( function( child ) { child.parent = null } );
			el.children = [];
		},
	} );

	return el;
}

/** Every element below $root, depth first */
function descendants( root ) {
	let all = [];
	root.children.forEach( function( child ) {
		all.push( child );
		all = all.concat( descendants( child ) );
	} );
	return all;
}

/** Every element below $root carrying $className */
function byClass( root, className ) {
	return descendants( root ).filter( function( el ) {
		return ( ' '+ el.className+ ' ' ).indexOf( ' '+ className+ ' ' ) !== -1;
	} );
}

/** Every element below $root of that tag */
function byTag( root, tag ) {
	return descendants( root ).filter( function( el ) { return el.tagName === tag.toUpperCase() } );
}

// What the answer of gallery/list says about the sizes: what a new album starts with, and the range each number may take
const DEFAULTS = { thumbWidth : 500, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : true, columns : 4 };
const RANGES = { thumbWidth : [ 40, 1200 ], thumbHeight : [ 40, 1200 ], largeWidth : [ 200, 4000 ], largeHeight : [ 200, 4000 ], columns : [ 1, 8 ] };

// What the album of the tests is made with - not what a new one starts with, so a screen that reads the wrong one is seen
const SIZES = { thumbWidth : 300, thumbHeight : 200, largeWidth : 1200, largeHeight : 800, keepRatio : true, columns : 3 };

/** One album as the panel is handed it - always with its six settings, which the server resolves */
function album( images, settings ) {
	return { key : 'haus', name : 'Haus am See', images : images, settings : Object.assign( {}, SIZES, settings || {} ) };
}

/** One image as the panel is handed it: a text is a string or a map of language to string */
function picture( id, caption, alt ) {
	return { id : id, caption : caption, alt : alt === undefined ? '' : alt, thumbUrl : '/content/gallery/haus/'+ id+ '-thumb.jpg' };
}

/**
 *	The panel on one album's screen, the script loaded over it, and the
 *	requests it makes held until the test answers them
 *
 *	@param		{Object}	options		{ images, locales, native, locale, limits, albums, open, creating, address,
 *														router, init } - one language, German, no limit and the album's screen
 *														unless it says otherwise. An address is a hash ('#gallery/haus'), the
 *														router the shell has - false for none, 'set' for one that cannot add
 *														an entry to the history, the Nino 1.3 one - and init loads the list
 *														instead of setting the state up
 */
function panel( options ) {

	options = options || {};

	const panes = {
		'gallery-list'	: element('div'),
		'gallery-album'	: element('div'),
	};

	const dc = {
		documentElement	: element('html'),
		body						: element('body'),
		createElement		: function( tag ) { return element( tag ) },
		getElementById	: function( id ) { return byId( id ) },
	};

	/*	The panel reaches for the two panes and, after a draw, for the upload
		field's own message by id - that element is a new one every time the
		screen is drawn, which is the point of asking for it by id	*/
	function byId( id ) {
		if( panes[id] !== undefined )
			return panes[id];
		const found = descendants( panes['gallery-album'] ).filter( function( el ) { return el.id === id } );
		return found[0] || null;
	}

	const requests = [];
	const sessionSet = [];

	// What the panel did to the address: each write, and each move the person made
	const written = [];
	const moved = [];

	// The address, as the shell's router reads it: #gallery/haus is the panel and its parts
	let hash = options.address || '#gallery';

	const Nino = {
		admin : {
			formToolbar : function( child ) { const bar = element('div'); bar.className = 'nino-admin-toolbar'; bar.appendChild( child ); return bar },
			// The workbench's own: the language every panel with a switch shares
			sessionLocale : {
				current : null,
				init : function( locale ) { if( Nino.admin.sessionLocale.current === null ) Nino.admin.sessionLocale.current = locale },
				set : function( locale ) { Nino.admin.sessionLocale.current = locale; sessionSet.push( locale ) },
			},
		},
		adminUi : {
			emptyState : function( text ) { const p = element('p'); p.className = 'nino-admin-empty'; p.textContent = text; return p },
			// The bar the workbench pins to the bottom, over a list
			listActions : function( buttons ) { const bar = element('div'); bar.className = 'nino-admin-actionbar nino-admin-list-actions'; buttons.forEach( function( button ) { bar.appendChild( button ) } ); return bar },
			// The design system's fields, as far as the panel reads them back: the input, with the key and the bounds it was given
			numberField : function( field ) {
				const label = element('label'); label.className = 'nino-admin-field';
				const name = element('span'); name.textContent = field.label; label.appendChild( name );
				const input = element('input'); input.type = 'number'; input.dataset.key = field.key; input.value = String( field.value ); input.min = field.min; input.max = field.max;
				label.appendChild( input );
				if( field.hint ) { const hint = element('small'); hint.className = 'nino-admin-hint'; hint.textContent = field.hint; label.appendChild( hint ) }
				return label;
			},
			switchField : function( field ) {
				const label = element('label'); label.className = 'nino-admin-switch';
				const input = element('input'); input.type = 'checkbox'; input.dataset.key = field.key; input.checked = field.checked === true;
				label.appendChild( input );
				const copy = element('span'); copy.textContent = field.label; label.appendChild( copy );
				if( field.hint ) { const hint = element('small'); hint.textContent = field.hint; copy.appendChild( hint ) }
				return label;
			},
		},
		content : {
			getText : function( key ) { return TEXT[key] !== undefined ? TEXT[key] : key },
		},
		events : {
			bindCallback : function() {},
		},
		http : {
			sendRequest : options.send || function() { throw new Error( 'every request goes through _apiCall, which this test holds' ) },
		},
	};

	// A newer workbench's request helper and status line, where a test gives them
	if( options.api )
		Nino.adminUi.api = options.api;
	if( options.status )
		Nino.adminUi.status = options.status;

	// The shell's router: it reads the address and writes it over the entry the browser is on (set),
	// and from Nino 1.4 adds an entry for a move the person made (go)
	if( options.router !== false ) {
		const path = function( panel, parts ) { return '#'+ [ panel ].concat( parts ).join('/') };
		Nino.admin.router = {
			current : function() { const parts = hash.replace( /^#/, '' ).split('/').filter( function( part ) { return part !== '' } ); return { panel : parts[0] || '', parts : parts.slice( 1 ) } },
			set : function( panel, parts ) { written.push( path( panel, parts ) ); hash = path( panel, parts ) },
		};
		if( options.router !== 'set' )
			Nino.admin.router.go = function( panel, parts ) { moved.push( path( panel, parts ) ); hash = path( panel, parts ) };
	}

	const sandbox = { console : console, document : dc, Nino : Nino, confirm : function() { return true } };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	const gallery = Nino.admin.gallery;

	/*	The seam: what the panel asked for, and the answer handed back when
		the test is ready for it. Every action answers the whole album list,
		so what a test answers with is the state the panel goes on from	*/
	if( options.raw !== true )
		gallery._apiCall = function( endpoint, payload, callback, extra ) {
			requests.push( { endpoint : endpoint, payload : payload, answer : callback, extra : extra || {} } );
		};

	Nino.admin.sessionLocale.current = options.locale || null;

	if( options.init === true )
		gallery.init();
	else {
		gallery._ready = true;
		gallery._albums = options.albums || [ album( options.images || [] ) ];
		gallery._defaults = DEFAULTS;
		gallery._ranges = RANGES;
		gallery._locales = options.locales || [ 'de_DE' ];
		gallery._native = options.native || 'de_DE';
		gallery._limits = options.limits || { bytes : 0, text : '' };
		gallery._open = options.open === undefined ? 'haus' : options.open;
		gallery._creating = options.creating === true;
		gallery._render();
	}

	return {
		gallery : gallery,
		requests : requests,
		pane : panes['gallery-album'],
		list : panes['gallery-list'],
		/** What the panel wrote into the address, and what it asked the history to keep as a step */
		written : written,
		moved : moved,
		/** The address now, and a step through the history to another one (the shell answers it with showCurrent()) */
		address : function() { return hash },
		navigate : function( to ) { hash = to },
		/** Any element on the album's pane - or one of the two panes - by its id */
		byId : byId,
		/** Which of the two panes is on screen: 'list', 'album' - or null where neither is */
		shown : function() { return panes['gallery-list'].classes['admin-hidden'] !== true ? 'list' : ( panes['gallery-album'].classes['admin-hidden'] !== true ? 'album' : null ) },
		/** The tiles on the screen, in the order they stand in */
		tiles : function() { return byClass( panes['gallery-album'], 'gallery-tile' ) },
		/** One tile's caption field, and its alt text field */
		caption : function( index ) { return byClass( this.tiles()[index], 'nino-admin-input' ).filter( function( el ) { return el.dataset.field === 'caption' } )[0] },
		alt : function( index ) { return byClass( this.tiles()[index], 'nino-admin-input' ).filter( function( el ) { return el.dataset.field === 'alt' } )[0] },
		/** The language switch in the toolbar, or undefined where there is none */
		select : function() { return byTag( panes['gallery-album'], 'select' )[0] },
		/** The languages the workbench was told were chosen */
		chosen : sessionSet,
		/** The file field, and the line under it the panel says things in */
		upload : function() { return byId( 'gallery-upload' ) },
		said : function() { const said = byId( 'gallery-upload-msg' ); return said === null ? null : said.textContent },
		/** Choose files, the way a visitor does - of one kilobyte each unless it says */
		choose : function( names, sizes ) {
			const input = this.upload();
			input.files = names.map( function( name, index ) { return { name : name, size : ( sizes || [] )[index] || 1024 } } );
			input.fire( 'change' );
		},
		/** The answer to the request the panel is waiting for */
		answer : function( status, response ) { requests.shift().answer( status, response ) },
	};
}


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
	byTag( two.tiles()[0], 'img' )[0].alt === 'Vorderseite' && byTag( two.tiles()[1], 'img' )[0].alt === '' );

const described = panel( { images : [ picture( 'a1', 'Vorderseite', 'Das Haus von vorn' ) ] } );
check( 'with an alt text the picture is named by it, not by the caption', byTag( described.tiles()[0], 'img' )[0].alt === 'Das Haus von vorn'
	&& described.alt( 0 ).value === 'Das Haus von vorn' && described.caption( 0 ).value === 'Vorderseite' );

check( 'a project with one language has no switch', two.select() === undefined );

const alone = panel( { images : [] } );

check( 'an album with nothing in it says so rather than drawing an empty grid',
	alone.tiles().length === 0 && byClass( alone.pane, 'nino-admin-empty' ).length === 1 );

console.log('');


// --- The caption -----------------------------------------------------------------

console.log( 'The caption' );

const captioned = panel( { images : [ picture( 'a1', '' ) ] } );

captioned.caption( 0 ).value = 'Gartenseite';
captioned.caption( 0 ).fire( 'blur' );

check( 'a caption that was changed is saved when the field is left, with the language it was written in',
	captioned.requests.length === 1 && captioned.requests[0].endpoint === 'image-save'
	&& captioned.requests[0].payload.caption === 'Gartenseite' && captioned.requests[0].payload.id === 'a1'
	&& captioned.requests[0].payload.locale === 'de_DE' );
check( '...and only the caption: the alt text stays as the server has it', captioned.requests[0].payload.alt === undefined );

captioned.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );

check( '...and the tile is left standing while it is, rather than rebuilt under the cursor',
	captioned.tiles().length === 1 && captioned.caption( 0 ).value === 'Gartenseite' );

captioned.caption( 0 ).fire( 'blur' );

check( 'a caption nobody changed is not sent again', captioned.requests.length === 0 );

/*	The tile is not rebuilt after a caption was saved, so the image object it
	was drawn from is the only record of what is saved. Where that record stays
	at the caption the page was loaded with, going back to it - clearing a
	caption that was empty when the screen was drawn - reads as "nothing
	changed" while the server holds what it was sent in between	*/
captioned.caption( 0 ).value = '';
captioned.caption( 0 ).fire( 'blur' );

check( 'a caption put back to what the page was loaded with is saved too, not read as no change at all',
	captioned.requests.length === 1 && captioned.requests[0].endpoint === 'image-save'
	&& captioned.requests[0].payload.caption === '' );

captioned.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );

const texted = panel( { images : [ picture( 'a1', '' ) ] } );
texted.alt( 0 ).value = 'Das Haus von vorn';
texted.alt( 0 ).fire( 'blur' );
check( 'an alt text is saved the same way, and only it',
	texted.requests.length === 1 && texted.requests[0].payload.alt === 'Das Haus von vorn' && texted.requests[0].payload.caption === undefined
	&& texted.requests[0].payload.locale === 'de_DE' );
texted.answer( 200, { albums : [ album( [ picture( 'a1', '', { de_DE : 'Das Haus von vorn' } ) ] ) ] } );
texted.alt( 0 ).fire( 'blur' );
check( '...and what the server answered is what the field is compared with next', texted.requests.length === 0 );

/*	The server keeps a text trimmed and cut to 300 bytes. A field holding it as it
	was typed - with blanks round it, or longer - never equals what comes back,
	and every blur sent it again	*/
const padded = panel( { images : [ picture( 'a1', '' ) ] } );
padded.caption( 0 ).value = '  Gartenseite \n';
padded.caption( 0 ).fire( 'blur' );
check( 'a text with blanks round it is sent as typed', padded.requests.length === 1 && padded.requests[0].payload.caption === '  Gartenseite \n' );
padded.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );
padded.caption( 0 ).fire( 'blur' );
check( '...and is not sent again on the next blur, the server having kept it trimmed', padded.requests.length === 0 );
padded.caption( 0 ).value = 'Gartenseite';
padded.caption( 0 ).fire( 'blur' );
check( '...nor when the blanks are taken off by hand', padded.requests.length === 0 );
padded.caption( 0 ).value = 'Gartenseite\u00A0';
padded.caption( 0 ).fire( 'blur' );
check( 'a no-break space is not a blank to php\'s trim(), so that one is a change', padded.requests.length === 1 );
padded.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite\u00A0' } ) ] ) ] } );

const longText = 'ä'.repeat( 200 );
const kept = 'ä'.repeat( 150 );
const cut = panel( { images : [ picture( 'a1', '' ) ] } );
cut.caption( 0 ).value = longText;
cut.caption( 0 ).fire( 'blur' );
cut.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : kept } ) ] ) ] } );
cut.caption( 0 ).fire( 'blur' );
check( 'a text longer than the server keeps is sent once, not on every blur', cut.requests.length === 0 );
check( '...300 bytes on a character boundary: a two-byte character is never cut in half', cut.gallery._kept( longText ) === kept
	&& cut.gallery._kept( 'a'+ 'ä'.repeat( 200 ) ) === 'a'+ 'ä'.repeat( 149 )
	&& cut.gallery._kept( '\u{1F600}'.repeat( 100 ) ) === '\u{1F600}'.repeat( 75 ) );
cut.gallery._maxText = 10;
check( '...and the limit is the one the server named', cut.gallery._kept( 'abcdefghijklmnop' ) === 'abcdefghij' );

// Two saves in flight, answered the wrong way round: the answer to the alt
// text predates the caption's, and must not take the caption back
const twice = panel( { images : [ picture( 'a1', '' ) ] } );
twice.alt( 0 ).value = 'Alt';
twice.alt( 0 ).fire( 'blur' );
twice.caption( 0 ).value = 'Caption';
twice.caption( 0 ).fire( 'blur' );
twice.requests[1].answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Caption' }, '' ) ] ) ] } );
twice.requests[0].answer( 200, { albums : [ album( [ picture( 'a1', '', { de_DE : 'Alt' } ) ] ) ] } );
twice.requests.length = 0;
twice.caption( 0 ).fire( 'blur' );
twice.alt( 0 ).fire( 'blur' );
check( 'an answer that predates the other field\'s save does not make that field read as unsaved', twice.requests.length === 0 );

console.log('');


// --- One language at a time -------------------------------------------------------

console.log( 'The languages' );

const images = [ picture( 'a1', { de_DE : 'Vorderseite', en_US : 'Front' }, { de_DE : 'Das Haus', en_US : 'The house' } ), picture( 'a2', 'Gartenseite', '' ), picture( 'a3', { en_US : 'Only English' }, '' ) ];
const languages = panel( { images : images, locales : [ 'de_DE', 'en_US' ], native : 'de_DE' } );

check( 'a project with more than one language has the workbench\'s own switch in the album\'s toolbar',
	languages.select() !== undefined && byClass( languages.pane, 'nino-admin-toolbar' )[0].children.indexOf( languages.select() ) !== -1
	&& languages.select().className.indexOf('nino-admin-locale-select') !== -1 && languages.select().getAttribute('aria-label') === 'Language' );
check( '...one option per language, the native one selected while the workbench has chosen nothing',
	byTag( languages.select(), 'option' ).map( function( el ) { return el.value+ ( el.selected ? '*' : '' ) } ).join(' ') === 'de_DE* en_US' );
check( 'the fields hold that language\'s texts, and a plain string is every language\'s',
	languages.alt( 0 ).value === 'Das Haus' && languages.caption( 0 ).value === 'Vorderseite' && languages.caption( 1 ).value === 'Gartenseite' );
check( '...a language a text was not written in is an empty field, not another language\'s text', languages.caption( 2 ).value === '' );
check( 'the thumbnail is named in that language too - the caption standing in only where there is no alt text',
	byTag( languages.tiles()[0], 'img' )[0].alt === 'Das Haus' && byTag( languages.tiles()[1], 'img' )[0].alt === 'Gartenseite'
	&& byTag( languages.tiles()[2], 'img' )[0].alt === 'Only English' );

languages.select().value = 'en_US';
languages.select().fire( 'change' );
check( 'switching the language tells the workbench, and redraws the fields in it', languages.chosen.join() === 'en_US'
	&& languages.alt( 0 ).value === 'The house' && languages.caption( 0 ).value === 'Front' && languages.caption( 1 ).value === 'Gartenseite'
	&& languages.caption( 2 ).value === 'Only English' && byTag( languages.select(), 'option' ).filter( function( el ) { return el.selected } )[0].value === 'en_US' );

languages.caption( 1 ).value = 'Garden';
languages.caption( 1 ).fire( 'blur' );
check( 'a text written after the switch is saved for the new language', languages.requests.length === 1
	&& languages.requests[0].payload.locale === 'en_US' && languages.requests[0].payload.caption === 'Garden' && languages.requests[0].payload.id === 'a2' );
languages.answer( 200, { albums : [ album( [ images[0], picture( 'a2', { de_DE : 'Gartenseite', en_US : 'Garden' }, '' ), images[2] ] ) ] } );

languages.select().value = 'de_DE';
languages.select().fire( 'change' );
check( '...and the other language still has what it had', languages.caption( 1 ).value === 'Gartenseite' && languages.alt( 0 ).value === 'Das Haus' );

const remembered = panel( { images : images, locales : [ 'de_DE', 'en_US' ], native : 'de_DE', locale : 'en_US' } );
check( 'the language the workbench was last working in is the one the screen opens in', remembered.alt( 0 ).value === 'The house' );
const stale = panel( { images : images, locales : [ 'de_DE', 'en_US' ], native : 'de_DE', locale : 'fr_FR' } );
check( '...unless the project no longer has it', stale.alt( 0 ).value === 'Das Haus' );

// What the list action answers is taken as it comes: the languages, the one the
// workbench is in, and what php takes
const listed0 = panel( { images : [] } );
listed0.gallery._take( { albums : [ album( [] ) ], locales : [ 'de_DE', 'en_US' ], native : 'de_DE', selectedLocale : 'en_US', limits : { bytes : 2097152, text : '2 MB' } } );
check( 'the answer to gallery/list sets the languages, the one to start in and the upload limit',
	listed0.gallery._locales.join() === 'de_DE,en_US' && listed0.gallery._native === 'de_DE' && listed0.gallery._limits.bytes === 2097152
	&& listed0.gallery._locale() === 'en_US' );
listed0.gallery._take( { albums : [ album( [] ) ] } );
listed0.gallery._take( { albums : [ album( [] ) ], maxText : 120 } );
check( 'the answer names how much of a text the server keeps', listed0.gallery._maxText === 120 );
listed0.gallery._take( { albums : [ album( [] ) ] } );
check( '...and an answer without it leaves that as it was', listed0.gallery._maxText === 120 );
check( '...and an answer that carries none of it leaves them as they were', listed0.gallery._locales.length === 2 && listed0.gallery._limits.text === '2 MB' );

console.log('');


// --- Uploading several at once ---------------------------------------------------

console.log( 'The upload' );

const batch = panel( { images : [] } );

check( 'the field takes several files at once - a gallery is filled from a folder', batch.upload().multiple === true );

batch.choose( [ 'haus-1.jpg', 'haus-2.jpg' ] );

check( 'one request at a time, because every answer carries the whole album list',
	batch.requests.length === 1 && batch.requests[0].endpoint === 'upload' && batch.requests[0].extra.file.name === 'haus-1.jpg' );
check( '...and the screen says which file is on its way', batch.said() === 'Uploading haus-1.jpg …' );

batch.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );

check( 'the next file follows the answer to the one before it',
	batch.requests.length === 1 && batch.requests[0].extra.file.name === 'haus-2.jpg' );

batch.answer( 200, { albums : [ album( [ picture( 'a1', '' ), picture( 'a2', '' ) ] ) ] } );

check( 'a finished batch leaves every picture of it on the screen, and the word about it where the field says things',
	batch.tiles().length === 2 && batch.said() === '2 added.' );

const halted = panel( { images : [] } );

halted.choose( [ 'haus-1.jpg', 'kaputt.tif' ] );
halted.answer( 200, { albums : [ album( [ picture( 'a1', '' ) ] ) ] } );
halted.answer( 500, { error : 'The file could not be read.' } );

/*	The picture that did arrive is on the server and in _albums; a grid that
	goes on showing the album as it was before the batch says the upload did
	nothing at all	*/
check( 'a batch that stopped halfway draws the pictures that did arrive, and says which file went wrong and what',
	halted.tiles().length === 1 && halted.said() === 'kaputt.tif: The file could not be read.' );
check( '...and the field takes files again rather than staying disabled', halted.upload().disabled === false );

const silent = panel( { images : [] } );

silent.choose( [ 'haus-1.jpg' ] );
silent.answer( 403, null );
check( 'an answer with nothing in it - a request php dropped - is still said as the file it belongs to',
	silent.said() === 'haus-1.jpg: The file could not be read.' );

console.log('');


// --- A file too big to send ---------------------------------------------------------

console.log( 'The limit' );

const limited = panel( { images : [], limits : { bytes : 2 * 1024 * 1024, text : '2 MB' } } );

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

// --- The list of albums --------------------------------------------------------------
//
// The list is the design system's row of buttons, the shape every other list
// a screen drills into has: the whole row opens the album and a chevron says
// so. It used to be a row with two buttons on it, a red Delete first - the one
// thing on a list a hand hits by mistake - so Delete lives on the album's own
// screen now, where the pictures it takes with it are in view

const listed = panel( { images : [ picture( 'a', '' ) ] } );
listed.gallery._open = '';
listed.gallery._render();

const rowLists = byClass( listed.list, 'nino-admin-list-buttons' );
const rows = rowLists.length === 1 ? rowLists[0].children.filter( function( el ) { return el.tagName === 'BUTTON' } ) : [];
const row = rows[0] || element('div');
check( 'the list is a row of buttons, one per album', rowLists.length === 1 && rows.length === 1 && row.dataset.album === 'haus' );
check( '...that names the album, its shortcode and how many pictures it holds', ( byTag( row, 'strong' )[0] || {} ).textContent === 'Haus am See'
	&& ( byTag( row, 'small' )[0] || {} ).textContent === 'Put [gallery album="haus"] into a template or a text · 1 images' );
check( '...with nothing on it but the way in', byClass( listed.list, 'nino-admin-btn-danger' ).length === 0 && byClass( row, 'admin-view-button-chev' ).length === 1 );

row.fire( 'click' );
check( 'the row opens the album', listed.gallery._open === 'haus' && listed.tiles().length === 1 );
check( '...and Delete is on the album\'s screen, under its pictures', byClass( listed.pane, 'gallery-album-actions' ).length === 1
	&& byClass( byClass( listed.pane, 'gallery-album-actions' )[0], 'nino-admin-btn-danger' ).length === 1 );

// --- Asking the workbench ------------------------------------------------------------

console.log( 'The workbench' );

/*	Where the shell has a request helper the panel asks it - the file of an
	upload goes with it; where it has not the panel posts from the project's own
	directory, the literal the asset bundle fills in, as Nino.dir does not exist
	before Nino 1.3.2	*/
const wasPosted = [];
const noHelper = panel( { raw : true, send : function( uri, method, callback, data ) { wasPosted.push( [ uri, method, data ] ); callback( { status : 200, responseJSON : { ok : true } } ) } } );
noHelper.gallery._apiCall( 'upload', { album : 'haus' }, function() {}, { file : 'F' } );
check( 'without the shell\'s request helper the panel posts to the project\'s own _admin, with the action, the json and the file',
	wasPosted.length === 1 && wasPosted[0][0] === '[[/nino/dir]]/_admin/' && wasPosted[0][1] === 'POST'
	&& wasPosted[0][2].action === 'gallery/upload' && wasPosted[0][2].data === '{"album":"haus"}' && wasPosted[0][2].file === 'F' );

const wasRouted = [];
const withHelper = panel( { raw : true, api : { call : function( action, payload, callback, extra ) { wasRouted.push( [ action, payload, extra ] ); callback( 200, {} ) } } } );
withHelper.gallery._apiCall( 'upload', { album : 'haus' }, function() {}, { file : 'F' } );
check( 'with it the action, the payload and the file go to the helper, and nothing is posted by hand',
	wasRouted.length === 1 && wasRouted[0][0] === 'gallery/upload' && wasRouted[0][1].album === 'haus' && wasRouted[0][2].file === 'F' );

check( 'a failed load says "(status) message" as it always did where the shell has no errorText()',
	noHelper.gallery._errorText( 503, { error : 'Busy' }, '/_admin/common/error/load' ) === '(503) Busy' );
const withText = panel( { raw : true, api : { errorText : function( status, response, key ) { return 'told '+ status+ ' ['+ key+ ']' } } } );
check( '...and what errorText() makes of it where it has one', withText.gallery._errorText( 503, { error : 'Busy' }, '/_admin/common/error/load' ) === 'told 503 [/_admin/common/error/load]' );

console.log('');


// --- Saying what happened ------------------------------------------------------------

console.log( 'The status line' );

// Nino.adminUi.status() in miniature: what it was told, on which line
const lines = [];
const statusStub = function( el ) {
	const line = { el : el, told : [],
		saving : function() { line.told.push('saving') },
		saved : function() { line.told.push('saved') },
		fail : function( text ) { line.told.push( 'fail '+ text ) },
	};
	lines.push( line );
	return line;
};

const lined = panel( { images : [ picture( 'a1', '' ) ], status : statusStub } );
lined.caption( 0 ).value = 'Gartenseite';
lined.caption( 0 ).fire( 'blur' );
const tileLine = lines[lines.length - 1];
check( 'where the shell has a status line the tile\'s message is one, told when the text is on its way',
	tileLine.el === byClass( lined.tiles()[0], 'gallery-tile-msg' )[0] && tileLine.told.join() === 'saving' );
lined.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );
check( '...and when it is saved, without a sentence of the panel\'s own beside it', tileLine.told.join() === 'saving,saved' && tileLine.el.textContent === '' );

lined.caption( 0 ).value = 'Zu lang';
lined.caption( 0 ).fire( 'blur' );
const failLine = lines[lines.length - 1];
lined.answer( 400, { error : 'Die Beschriftung ist zu lang.' } );
check( '...a refusal is told as it is - the sentence the server wrote in the workbench\'s language, with no status number in front',
	failLine.told.join() === 'saving,fail Die Beschriftung ist zu lang.' );

const plainly = panel( { images : [ picture( 'a1', '' ) ] } );
plainly.caption( 0 ).value = 'Gartenseite';
plainly.caption( 0 ).fire( 'blur' );
const plainMsg = byClass( plainly.tiles()[0], 'gallery-tile-msg' )[0];
check( 'without one the paragraph says what it always said', plainMsg.textContent === 'Saving …' );
plainly.answer( 200, { albums : [ album( [ picture( 'a1', { de_DE : 'Gartenseite' } ) ] ) ] } );
check( '...and "Saved." when it is saved', plainMsg.textContent === 'Saved.' );

console.log('');


// --- The way to a new album -----------------------------------------------------------
//
// The footer of the list - a key, a name and a button in the bar the workbench pins to
// the bottom - is gone. The list ends in one button, the way the Elements panel's does,
// and an album is made on a page of its own

console.log( 'The button below the list' );

/** The two JSON shapes the same, key order and all - what a request is compared by */
function same( a, b ) {
	return JSON.stringify( a ) === JSON.stringify( b );
}

const noAlbums = panel( { open : '', albums : [] } );

check( 'the list holds no field any more: the key and the name are asked on a page of their own',
	byTag( noAlbums.list, 'form' ).length === 0 && byTag( noAlbums.list, 'input' ).length === 0 );
check( 'it ends in one button, in the bar the workbench pins below a list, that says what it does',
	byClass( noAlbums.list, 'nino-admin-list-actions' ).length === 1 && byTag( noAlbums.list, 'button' ).length === 1
	&& byTag( noAlbums.list, 'button' )[0].textContent === 'Add album' && byTag( noAlbums.list, 'button' )[0].className === 'nino-admin-btn-primary'
	&& noAlbums.list.children[noAlbums.list.children.length - 1].className.indexOf( 'nino-admin-list-actions' ) !== -1 );
check( '...below the empty state, and below the rows where there are some', noAlbums.list.children[0].className === 'nino-admin-empty'
	&& byClass( listed.list, 'nino-admin-list-actions' ).length === 1 && listed.list.children.indexOf( byClass( listed.list, 'nino-admin-list-actions' )[0] ) > listed.list.children.indexOf( rowLists[0] ) );
check( 'the list writes its address, #gallery', noAlbums.written[noAlbums.written.length - 1] === '#gallery' && noAlbums.shown() === 'list' );

noAlbums.written.length = 0;
byTag( noAlbums.list, 'button' )[0].fire( 'click' );

check( 'the button opens the page of a new album: a step the history keeps, the address #gallery/new, in the pane of an album\'s screen',
	same( noAlbums.moved, [ '#gallery/new' ] ) && noAlbums.address() === '#gallery/new' && noAlbums.shown() === 'album' && noAlbums.gallery._creating === true
	&& noAlbums.byId( 'gallery-new-key' ) !== null );

listed.gallery._open = '';
listed.gallery._render();
listed.moved.length = 0;
byClass( listed.list, 'nino-admin-list-buttons' )[0].children[0].fire( 'click' );
check( 'a row of the list is a step the history keeps too: #gallery/haus', same( listed.moved, [ '#gallery/haus' ] ) && listed.gallery._open === 'haus' && listed.shown() === 'album' );

console.log('');


// --- The page of a new album ------------------------------------------------------------

console.log( 'The page of a new album' );

const fresh = panel( { open : '', creating : true } );
const freshKey = fresh.byId( 'gallery-new-key' );
const freshName = fresh.byId( 'gallery-new-name' );
const freshForm = fresh.byId( 'gallery-new-form' );
const freshInput = function( name ) { return freshForm.querySelector( '[data-key="'+ name+ '"]' ) };

check( 'it is the pane of an album\'s screen, the list\'s pane hidden, and the address says new',
	fresh.shown() === 'album' && fresh.written[fresh.written.length - 1] === '#gallery/new' );
check( '...with the toolbar every level of the panel has: the way back, and no language to switch - nothing here is written in one',
	byClass( fresh.pane, 'nino-admin-toolbar' ).length === 1 && byClass( fresh.pane, 'nino-admin-back-link' ).length === 1
	&& byClass( fresh.pane, 'nino-admin-back-link' )[0].textContent === 'Back to list' && fresh.select() === undefined );
check( '...and a heading that says what the page is for', byTag( fresh.pane, 'h3' ).length === 1 && byTag( fresh.pane, 'h3' )[0].textContent === 'Add album' );

check( 'the key is a text field of its own, which a screen reader is told cannot be left empty - and not through the browser\'s own required, which takes a key of blanks',
	freshKey.getAttribute('aria-required') === 'true' && freshKey.getAttribute('required') === null && freshKey.readOnly === false && freshKey.value === '' );
check( '...limited to the length of key the server will make an album with: 64, until the answer of the list names another',
	freshKey.maxLength === 64 );
const longer = panel( { open : '', creating : true } );
longer.gallery._take( { albums : [], maxKey : 40 } );
check( '...and then that one: the server holds a key to the same number', longer.byId( 'gallery-new-key' ).maxLength === 40 && longer.gallery._maxKey === 40 );
const keyName = freshKey.parent.children[0];
check( '...with the mark after its name that is drawn for the eye and hidden from a reader, and the hint under it that tells what a key is made of and that it stays',
	keyName.textContent === 'Key' && byClass( keyName, 'nino-admin-required' ).length === 1 && byClass( keyName, 'nino-admin-required' )[0].textContent === '*'
	&& byClass( keyName, 'nino-admin-required' )[0].getAttribute('aria-hidden') === 'true'
	&& byClass( freshForm, 'nino-admin-field-hint' )[0].textContent === 'Lower case letters, digits and hyphens.'
	&& freshForm.children.indexOf( byClass( freshForm, 'nino-admin-field-hint' )[0] ) === freshForm.children.indexOf( freshKey.parent ) + 1 );
check( 'the name is a field of its own, empty and not required', freshName.value === '' && freshName.getAttribute('aria-required') === null && freshName.parent.children[0].textContent === 'Name' );

const group = byTag( fresh.pane, 'details' );
check( 'the sizes are one group named Image sizes - open here, because they are part of what is decided',
	group.length === 1 && group[0].open === true && byTag( group[0], 'summary' )[0].textContent === 'Image sizes' );
check( '...six fields in the order the Features screen has them: the sizes, whether the ratio is kept, and the columns',
	[ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'keepRatio', 'columns' ].every( function( name ) { return freshInput( name ) !== null } )
	&& byTag( group[0], 'input' ).map( function( input ) { return input.dataset.key } ).join() === 'thumbWidth,thumbHeight,largeWidth,largeHeight,keepRatio,columns' );
check( '...that start as what the feature\'s settings are, and not as the sizes of any album', [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ].every( function( name ) { return freshInput( name ).value === String( DEFAULTS[name] ) } )
	&& freshInput( 'keepRatio' ).checked === true );
check( '...each number limited by the range the server will hold it to', freshInput( 'thumbWidth' ).min === 40 && freshInput( 'thumbWidth' ).max === 1200
	&& freshInput( 'largeHeight' ).min === 200 && freshInput( 'largeHeight' ).max === 4000 && freshInput( 'columns' ).min === 1 && freshInput( 'columns' ).max === 8 );
check( '...and the fields that have a word to say have it', byTag( group[0], 'small' ).map( function( small ) { return small.textContent } ).join('|')
	=== 'Thumbnails are cropped to exactly this.|The size behind a thumbnail.|On, the large view is the whole picture.|How many thumbnails a row holds.' );

const freshUpload = fresh.upload();
check( 'the upload field is drawn - and greyed out, with the word that says when it will be on',
	freshUpload !== null && freshUpload.disabled === true && freshUpload.parent.className.indexOf( 'gallery-upload-off' ) !== -1 && fresh.said() === 'Create the album, then you can upload images.'
	&& freshUpload.getAttribute('aria-describedby') === 'gallery-upload-msg' );

const freshBar = byClass( freshForm, 'nino-admin-actionbar' );
check( 'the page ends in the action bar of a form: the button that makes the album, and the line it answers in',
	freshBar.length === 1 && byTag( freshBar[0], 'button' ).length === 1 && byTag( freshBar[0], 'button' )[0].type === 'submit' && byTag( freshBar[0], 'button' )[0].textContent === 'Create'
	&& fresh.byId( 'gallery-new-msg' ).parent === freshBar[0] );

// What a form says of a key it has none of
freshKey.value = '   ';
fresh.byId( 'gallery-new-form' ).fire( 'submit' );
check( 'a key left empty is not sent: the field is marked, has the focus, and the line says what a key is',
	fresh.requests.length === 0 && freshKey.getAttribute('aria-invalid') === 'true' && focused[focused.length - 1] === freshKey && fresh.byId( 'gallery-new-msg' ).textContent === 'An album key is a slug.' );
freshKey.fire( 'input' );
check( '...and what is typed into it takes the mark away', freshKey.getAttribute('aria-invalid') === null );

freshKey.value = ' neu ';
freshName.value = ' Neu ';
freshInput( 'thumbWidth' ).value = '120';
freshInput( 'columns' ).value = '6';
freshInput( 'keepRatio' ).checked = false;
fresh.byId( 'gallery-new-form' ).fire( 'submit' );

check( 'the button makes the album with album-save: the key and the name trimmed, the six sizes as numbers and a boolean, and the word that this is a new album',
	fresh.requests.length === 1 && fresh.requests[0].endpoint === 'album-save'
	&& same( fresh.requests[0].payload, { album : 'neu', name : 'Neu', settings : { thumbWidth : 120, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : false, columns : 6 }, isNew : true } ) );
check( '...and nothing can be pressed twice while it is on its way', byTag( freshBar[0], 'button' )[0].disabled === true && fresh.byId( 'gallery-new-msg' ).textContent === 'Saving …' );

fresh.answer( 400, { error : 'An album with this key exists already.' } );
check( 'a refusal - the key is taken - is said in the line as the server worded it, and the page stays as it is, with what was typed',
	fresh.byId( 'gallery-new-msg' ).textContent === 'An album with this key exists already.' && byTag( freshBar[0], 'button' )[0].disabled === false
	&& fresh.gallery._creating === true && freshKey.value === ' neu ' && freshInput( 'thumbWidth' ).value === '120' && fresh.address() === '#gallery/new' );

fresh.moved.length = 0;
fresh.written.length = 0;
fresh.byId( 'gallery-new-form' ).fire( 'submit' );
fresh.answer( 200, { albums : [ { key : 'neu', name : 'Neu', images : [], settings : { thumbWidth : 120, thumbHeight : 500, largeWidth : 1800, largeHeight : 1800, keepRatio : false, columns : 6 } } ] } );

check( 'a new album opens its own page: the address is written over the one of the new-album page - no step to come back to a page that would make it twice - and names the album',
	fresh.gallery._open === 'neu' && fresh.gallery._creating === false && fresh.moved.length === 0 && fresh.address() === '#gallery/neu' && fresh.shown() === 'album' );
check( '...with its key shown and read only, its name in a field, and the upload field on', fresh.byId( 'gallery-album-key' ).value === 'neu' && fresh.byId( 'gallery-album-key' ).readOnly === true
	&& fresh.byId( 'gallery-album-name' ).value === 'Neu' && fresh.upload().disabled === false && fresh.byId( 'gallery-new-key' ) === null );
check( '...and the sizes it was made with are the ones the page says an upload will become', byClass( fresh.pane, 'gallery-sizes' )[0].textContent === 'A thumbnail cropped to 120×500, and a large view cropped to 1800×1800.' );

const back = panel( { open : '', creating : true } );
byClass( back.pane, 'nino-admin-back-link' )[0].fire( 'click' );
check( 'the back link is a step the history keeps, and leaves the page for the list', same( back.moved, [ '#gallery' ] ) && back.shown() === 'list' && back.gallery._creating === false && back.address() === '#gallery' );

const old13 = panel( { router : 'set', open : '', albums : [] } );
byTag( old13.list, 'button' )[0].fire( 'click' );
check( 'a shell that cannot add an entry to the history (Nino 1.3) is written to all the same', old13.shown() === 'album' && old13.address() === '#gallery/new' && old13.moved.length === 0 );
const unrouted = panel( { router : false, open : '', albums : [] } );
byTag( unrouted.list, 'button' )[0].fire( 'click' );
check( '...and one without a router at all draws the page', unrouted.shown() === 'album' && unrouted.byId( 'gallery-new-key' ) !== null );

console.log('');


// --- One album's page ---------------------------------------------------------------------

console.log( 'One album\'s page' );

const one = panel( { images : [ picture( 'a1', 'Vorderseite' ) ] } );
const oneKey = one.byId( 'gallery-album-key' );
const oneName = one.byId( 'gallery-album-name' );
const oneTitle = byTag( one.pane, 'h3' )[0];
const oneForm = byClass( one.pane, 'gallery-name-form' )[0];

check( 'the key of an album is shown and cannot be changed - the shortcode names it and the files are filed under it - with the line to put into a template',
	oneKey.value === 'haus' && oneKey.readOnly === true && oneKey.parent.children[0].textContent === 'Key'
	&& byClass( oneForm, 'nino-admin-field-hint' )[0].textContent === 'Put [gallery album="haus"] into a template or a text' );
check( 'the name is a field that can be written', oneName.value === 'Haus am See' && oneName.readOnly === false && oneTitle.textContent === 'Haus am See' );

const oneTile = one.tiles()[0];
oneName.value = ' Haus am Meer ';
oneForm.fire( 'submit' );
check( 'the name is saved with album-save and the name alone: no sizes, and not as a new album',
	one.requests.length === 1 && one.requests[0].endpoint === 'album-save' && same( one.requests[0].payload, { album : 'haus', name : 'Haus am Meer' } ) );

one.answer( 200, { albums : [ Object.assign( album( [ picture( 'a1', 'Vorderseite' ) ] ), { name : 'Haus am Meer' } ) ] } );
check( 'what the server kept is what the page says - the heading, the field and the list - and the page is not drawn again: the pictures and the cursor stay',
	oneTitle.textContent === 'Haus am Meer' && oneName.value === 'Haus am Meer' && one.gallery._album( 'haus' ).name === 'Haus am Meer' && one.tiles()[0] === oneTile
	&& byClass( oneForm, 'gallery-form-msg' )[0].textContent === 'Saved.' );

oneName.value = '';
oneForm.fire( 'submit' );
one.answer( 200, { albums : [ Object.assign( album( [ picture( 'a1', 'Vorderseite' ) ] ), { name : 'Haus am Meer' } ) ] } );
check( 'a name emptied is kept as it was by the server, and the field says so', oneName.value === 'Haus am Meer' && oneTitle.textContent === 'Haus am Meer' );

oneName.value = 'x';
oneForm.fire( 'submit' );
one.answer( 400, { error : 'The albums could not be written.' } );
check( 'a refusal is said in the line, and nothing on the page changes', byClass( oneForm, 'gallery-form-msg' )[0].textContent === 'The albums could not be written.' && oneTitle.textContent === 'Haus am Meer'
	&& one.gallery._album( 'haus' ).name === 'Haus am Meer' );

// The sizes
const sizesForm = byClass( one.pane, 'gallery-sizes-form' )[0];
const sizesGroup = byTag( sizesForm, 'details' )[0];
const sizeInput = function( name ) { return sizesForm.querySelector( '[data-key="'+ name+ '"]' ) };

check( 'the sizes of the album are a group that is folded away, named Image sizes', byClass( one.pane, 'gallery-sizes-group' ).length === 1 && sizesGroup.open === false
	&& byTag( sizesGroup, 'summary' )[0].textContent === 'Image sizes' );
check( '...whose fields hold the sizes of this album - not what a new one starts with - limited by the same ranges', [ 'thumbWidth', 'thumbHeight', 'largeWidth', 'largeHeight', 'columns' ].every( function( name ) { return sizeInput( name ).value === String( SIZES[name] ) } )
	&& sizeInput( 'keepRatio' ).checked === true && sizeInput( 'columns' ).max === 8 );
check( '...with the word that they apply to what is uploaded from then on, and a Save of their own',
	byClass( sizesGroup, 'nino-admin-hint' ).filter( function( p ) { return p.textContent === 'These sizes apply to images uploaded from now on.' } ).length === 1
	&& byTag( sizesGroup, 'button' ).length === 1 && byTag( sizesGroup, 'button' )[0].type === 'submit' && byTag( sizesGroup, 'button' )[0].textContent === 'Save' );
check( 'what an upload becomes is said in the album\'s own sizes, above the field that uploads', byClass( one.pane, 'gallery-sizes' )[0].textContent === 'A thumbnail cropped to 300×200, and a large view scaled into 1200×800.'
	&& one.pane.children.indexOf( byClass( one.pane, 'gallery-sizes' )[0] ) < one.pane.children.indexOf( one.upload().parent ) );

sizesGroup.open = true;
sizeInput( 'thumbWidth' ).value = '150';
sizeInput( 'columns' ).value = '5';
sizeInput( 'keepRatio' ).checked = false;
sizesForm.fire( 'submit' );
check( 'the sizes are saved with album-save and the sizes alone: the album and the six values, no name',
	one.requests.length === 1 && same( one.requests[0].payload, { album : 'haus', settings : { thumbWidth : 150, thumbHeight : 200, largeWidth : 1200, largeHeight : 800, keepRatio : false, columns : 5 } } ) );

one.answer( 200, { albums : [ album( [ picture( 'a1', 'Vorderseite' ) ], { thumbWidth : 150, keepRatio : false, columns : 5 } ) ] } );
check( 'what was saved is what the page says of an upload from now on, and the group stays open under the cursor rather than folding away',
	byClass( one.pane, 'gallery-sizes' )[0].textContent === 'A thumbnail cropped to 150×200, and a large view cropped to 1200×800.' && sizesGroup.open === true
	&& one.gallery._album( 'haus' ).settings.columns === 5 && byClass( sizesGroup, 'gallery-form-msg' )[0].textContent === 'Saved.' && one.tiles()[0] === oneTile );

sizeInput( 'columns' ).value = '9';
sizesForm.fire( 'submit' );
one.answer( 400, { error : 'These values are outside what is allowed: Columns (1–8).' } );
check( 'sizes the server refuses are said as it worded them, and the page keeps what it had', byClass( sizesGroup, 'gallery-form-msg' )[0].textContent === 'These values are outside what is allowed: Columns (1–8).'
	&& byClass( one.pane, 'gallery-sizes' )[0].textContent === 'A thumbnail cropped to 150×200, and a large view cropped to 1200×800.' && one.gallery._album( 'haus' ).settings.columns === 5 );

sizeInput( 'thumbHeight' ).value = '';
sizesForm.fire( 'submit' );
check( 'a number that is none is sent as none: the server refuses it with the rest, rather than the panel guessing', one.requests.length === 1 && one.requests[0].payload.settings.thumbHeight === null );
one.requests.length = 0;

const backOne = panel( { images : [] } );
byClass( backOne.pane, 'nino-admin-back-link' )[0].fire( 'click' );
check( 'the back link of an album\'s page is a step the history keeps, and leads to the list', same( backOne.moved, [ '#gallery' ] ) && backOne.shown() === 'list' && backOne.gallery._open === '' );
check( 'the address of an album\'s page is its key', panel( { images : [] } ).address() === '#gallery/haus' );

console.log('');


// --- The address --------------------------------------------------------------------------

console.log( 'The address' );

const answer = function( albums ) { return { albums : albums, defaults : DEFAULTS, ranges : RANGES, locales : [ 'de_DE' ], native : 'de_DE', limits : { bytes : 0, text : '' } } };

/*	The address is read before anything is drawn, because drawing writes it: a list drawn first
	and the album after would have taken the album's address back to #gallery by then	*/
const reload = panel( { init : true, address : '#gallery/haus' } );
reload.answer( 200, answer( [ album( [] ) ] ) );
check( 'a page opened on the address of an album opens on that album - and the list is never drawn first, so the address is never taken back',
	reload.shown() === 'album' && reload.gallery._open === 'haus' && same( reload.written, [ '#gallery/haus' ] ) && reload.byId( 'gallery-album-key' ).value === 'haus' );
check( '...with what the list answered: what a new album starts with, and the ranges', same( reload.gallery._defaults, DEFAULTS ) && same( reload.gallery._ranges, RANGES ) );

const reloadNew = panel( { init : true, address : '#gallery/new' } );
reloadNew.answer( 200, answer( [ album( [] ) ] ) );
check( '...and on the address of the page of a new album it opens that page, with the sizes the feature has', reloadNew.shown() === 'album' && reloadNew.gallery._creating === true
	&& same( reloadNew.written, [ '#gallery/new' ] ) && reloadNew.byId( 'gallery-new-form' ).querySelector( '[data-key="columns"]' ).value === '4' );

const reloadGone = panel( { init : true, address : '#gallery/gone' } );
reloadGone.answer( 200, answer( [ album( [] ) ] ) );
check( 'an address that names an album nobody has is the list', reloadGone.shown() === 'list' && reloadGone.gallery._open === '' && reloadGone.gallery._creating === false && reloadGone.address() === '#gallery' );

const reloadBare = panel( { init : true } );
reloadBare.answer( 200, answer( [ album( [] ) ] ) );
check( 'the plain address is the list', reloadBare.shown() === 'list' && reloadBare.address() === '#gallery' );

const reloadOther = panel( { init : true, address : '#dashboard' } );
reloadOther.answer( 200, answer( [ album( [] ) ] ) );
check( 'an address that names another panel leaves this one on its list', reloadOther.shown() === 'list' && reloadOther.gallery._open === '' && reloadOther.gallery._creating === false );

const reloadNone = panel( { init : true, router : false } );
reloadNone.answer( 200, answer( [ album( [] ) ] ) );
check( 'a shell with no router has no address to read: the list', reloadNone.shown() === 'list' );

const newKey = panel( { init : true, address : '#gallery/new' } );
newKey.answer( 200, answer( [ Object.assign( album( [] ), { key : 'new', name : 'Neues' } ) ] ) );
check( 'an album whose key is "new" is opened by its address, not mistaken for the page of a new one', newKey.shown() === 'album' && newKey.gallery._open === 'new' && newKey.gallery._creating === false
	&& newKey.byId( 'gallery-album-key' ).value === 'new' );

/*	A step through the browser's history changes the address and nothing else; the shell answers
	by asking the panel to show again, and the level the address names is the one shown	*/
const stepped = panel( { open : '', albums : [ album( [] ) ] } );
stepped.navigate( '#gallery/haus' );
stepped.gallery.showCurrent();
check( 'a step through the history to an album shows it', stepped.shown() === 'album' && stepped.gallery._open === 'haus' && stepped.byId( 'gallery-album-key' ).value === 'haus' );
stepped.navigate( '#gallery/new' );
stepped.gallery.showCurrent();
check( '...to the page of a new album shows that', stepped.gallery._creating === true && stepped.byId( 'gallery-new-key' ) !== null && stepped.gallery._open === '' );
stepped.navigate( '#gallery' );
stepped.gallery.showCurrent();
check( '...and back to the plain address shows the list', stepped.shown() === 'list' && stepped.gallery._creating === false && stepped.gallery._open === '' );
stepped.gallery._open = 'haus';
stepped.navigate( '#elements/services' );
stepped.gallery.showCurrent();
check( 'an address that names another panel leaves the level in memory as it is: the panel keeps its drill-down state while another is on screen', stepped.gallery._open === 'haus' && stepped.shown() === 'album' );

/*	The panel is opened again - from the rail, after another panel was on screen. A page that is drawn for
	the level the panel is on is shown as it was: the key that was half typed is still there	*/
const away = panel( { open : '', creating : true } );
const typed = away.byId( 'gallery-new-key' );
typed.value = 'half-typed';
away.navigate( '#dashboard' );
away.gallery.showCurrent();
check( 'the page of a new album is shown as it was when the panel is opened again: the field is the same and holds what was typed, and the address is the page\'s',
	away.byId( 'gallery-new-key' ) === typed && typed.value === 'half-typed' && away.address() === '#gallery/new' && away.shown() === 'album' );

const awayAlbum = panel( { images : [ picture( 'a1', '' ) ] } );
const awayName = awayAlbum.byId( 'gallery-album-name' );
awayName.value = 'half-typed';
awayAlbum.navigate( '#dashboard' );
awayAlbum.gallery.showCurrent();
check( '...and so is the page of an album, with the name that was being changed', awayAlbum.byId( 'gallery-album-name' ) === awayName && awayName.value === 'half-typed' && awayAlbum.address() === '#gallery/haus' );

awayAlbum.navigate( '#gallery' );
awayAlbum.gallery.showCurrent();
awayAlbum.navigate( '#gallery/haus' );
awayAlbum.gallery.showCurrent();
check( 'a page for another level is drawn: back from the list to the album, the field is a new one with the name the album has', awayAlbum.shown() === 'album' && awayAlbum.byId( 'gallery-album-name' ).value === 'Haus am See' );

const deleted = panel( { images : [] } );
deleted.gallery._take( { albums : [] } );
check( 'an album that is gone takes its page with it, and the address goes back to the list', deleted.shown() === 'list' && deleted.address() === '#gallery' && deleted.gallery._open === '' );

console.log('');

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
