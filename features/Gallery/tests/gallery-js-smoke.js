/**
 *	Nino
 *	gallery-js-smoke.js		What the Gallery panel's script does over a dom
 *												stand-in: the tiles it draws for an album, the
 *												caption it saves when a field is left, the one it
 *												does not, and what an upload of several files
 *												leaves on the screen - when all of them arrive and
 *												when one of them does not.
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
	'/_admin/gallery/label/caption'	: 'Caption',
	'/_admin/common/msg/saving'			: 'Saving …',
	'/_admin/common/msg/saved'			: 'Saved.',
	'/_admin/common/error/save'			: 'Failed to save.',
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

/** One image as the panel is handed it */
function picture( id, caption ) {
	return { id : id, caption : caption, thumbUrl : '/content/gallery/haus/'+ id+ '-thumb.jpg' };
}

/**
 *	The panel on one album's screen, the script loaded over it, and the
 *	requests it makes held until the test answers them
 *
 *	@param		{Object}	options		{ images }
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

	const Nino = {
		admin : {
			formToolbar : function( child ) { const bar = element('div'); bar.className = 'nino-admin-toolbar'; bar.appendChild( child ); return bar },
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
	gallery._open = 'haus';
	gallery._render();

	return {
		gallery : gallery,
		requests : requests,
		pane : panes['gallery-album'],
		/** The tiles on the screen, in the order they stand in */
		tiles : function() { return byClass( panes['gallery-album'], 'gallery-tile' ) },
		/** One tile's caption field */
		caption : function( index ) { return byClass( this.tiles()[index], 'nino-admin-input' )[0] },
		/** The file field, and the line under it the panel says things in */
		upload : function() { return byId( 'gallery-upload' ) },
		said : function() { const said = byId( 'gallery-upload-msg' ); return said === null ? null : said.textContent },
		/** Choose files, the way a visitor does */
		choose : function( names ) {
			const input = this.upload();
			input.files = names.map( function( name ) { return { name : name } } );
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
check( '...and the picture named by its own caption for somebody who cannot see it',
	byTag( two.tiles()[0], 'img' )[0].alt === 'Vorderseite' );

const alone = panel( { images : [] } );

check( 'an album with nothing in it says so rather than drawing an empty grid',
	alone.tiles().length === 0 && byClass( alone.pane, 'nino-admin-empty' ).length === 1 );

console.log('');


// --- The caption -----------------------------------------------------------------

console.log( 'The caption' );

const captioned = panel( { images : [ picture( 'a1', '' ) ] } );

captioned.caption( 0 ).value = 'Gartenseite';
captioned.caption( 0 ).fire( 'blur' );

check( 'a caption that was changed is saved when the field is left',
	captioned.requests.length === 1 && captioned.requests[0].endpoint === 'image-save'
	&& captioned.requests[0].payload.caption === 'Gartenseite' && captioned.requests[0].payload.id === 'a1' );

captioned.answer( 200, { albums : [ album( [ picture( 'a1', 'Gartenseite' ) ] ) ] } );

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
check( 'a batch that stopped halfway draws the pictures that did arrive, and says what went wrong',
	halted.tiles().length === 1 && halted.said() === 'The file could not be read.' );
check( '...and the field takes files again rather than staying disabled', halted.upload().disabled === false );

console.log( '\n'+ checks+ ' checks, '+ failures+ ' failed' );
process.exit( failures === 0 ? 0 : 1 );
