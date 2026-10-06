/**
 *	Nino
 *	gallery-js-smoke.js		What the Gallery panel's script does over a dom
 *												stand-in: the tiles it draws for an album, the
 *												alt text and the caption in the language the
 *												switch is on, what it saves when a field is left
 *												and what it does not, and what an upload of
 *												several files leaves on the screen - when all of
 *												them arrive, when one of them does not, and when
 *												one is too big to be sent at all.
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
};

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
		getAttribute		: function( name ) { return Object.prototype.hasOwnProperty.call( this.attributes, name ) ? this.attributes[name] : null },
		setAttribute		: function( name, value ) { this.attributes[name] = String( value ) },
		appendChild			: function( child ) { child.parent = this; this.children.push( child ); return child },
		addEventListener	: function( type, fn ) { ( this.listeners[type] = this.listeners[type] || [] ).push( fn ) },
		fire						: function( type ) { ( this.listeners[type] || [] ).forEach( function( fn ) { fn( { preventDefault : function() {} } ) } ) },
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

/** One album as the panel is handed it */
function album( images ) {
	return { key : 'haus', name : 'Haus am See', images : images };
}

/** One image as the panel is handed it: a text is a string or a map of language to string */
function picture( id, caption, alt ) {
	return { id : id, caption : caption, alt : alt === undefined ? '' : alt, thumbUrl : '/content/gallery/haus/'+ id+ '-thumb.jpg' };
}

/**
 *	The panel on one album's screen, the script loaded over it, and the
 *	requests it makes held until the test answers them
 *
 *	@param		{Object}	options		{ images, locales, native, locale, limits } - one language, German,
 *														and no limit unless it says otherwise
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
		},
		content : {
			getText : function( key ) { return TEXT[key] !== undefined ? TEXT[key] : key },
		},
		events : {
			bindCallback : function() {},
		},
		http : {
			sendRequest : function() { throw new Error( 'every request goes through _apiCall, which this test holds' ) },
		},
	};

	const sandbox = { console : console, document : dc, Nino : Nino, confirm : function() { return true } };
	sandbox.window = sandbox;

	vm.runInContext( source, vm.createContext( sandbox ), { filename : 'admin.js' } );

	const gallery = Nino.admin.gallery;

	/*	The seam: what the panel asked for, and the answer handed back when
		the test is ready for it. Every action answers the whole album list,
		so what a test answers with is the state the panel goes on from	*/
	gallery._apiCall = function( endpoint, payload, callback, extra ) {
		requests.push( { endpoint : endpoint, payload : payload, answer : callback, extra : extra || {} } );
	};

	gallery._albums = [ album( options.images || [] ) ];
	gallery._locales = options.locales || [ 'de_DE' ];
	gallery._native = options.native || 'de_DE';
	gallery._limits = options.limits || { bytes : 0, text : '' };
	Nino.admin.sessionLocale.current = options.locale || null;
	gallery._open = 'haus';
	gallery._render();

	return {
		gallery : gallery,
		requests : requests,
		pane : panes['gallery-album'],
		list : panes['gallery-list'],
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

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
