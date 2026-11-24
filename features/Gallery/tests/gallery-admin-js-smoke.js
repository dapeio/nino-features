/**
 *	Nino features
 *	gallery-admin-js-smoke.js	What the script of the panel does with the address, over the kernel's stand-in of a page, the real Nino.adminUi and the
 *											real router of the workbench, a few pins:
 *
 *											A. an album is a key and its settings are a word: the key settings is an album, @settings the part of any album
 *											B. what the panel writes into the address is read back by the router as the same screen and the same parts
 *											C. an address the panel does not know - an album that is not there, a word it has not, a key written as a word - is the albums
 *											D. saving the name and the sizes of an album says "Saved." once
 *
 *											The helpers - check(), done(), findAll(), fire(), element(), documentOf(), load() and shellRouter() - are those of tests/harness.js
 *											of the checkout, three levels up or the one NINO_ROOT names. gallery-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Gallery/tests/gallery-admin-js-smoke.js
 *	       NINO_ROOT=../nino node features/Gallery/tests/gallery-admin-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, element, findAll, documentOf, shellRouter } = require( path.join( root, 'tests/harness.js' ) );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const words = fills( path.relative( root, path.join( featureDir, 'text/en_US.php' ) ) );
const workbench = fills('_admin/text/en_US.php');

const album = function( key ) {
	return { key : key, name : 'Album '+ key, images : [], settings : { thumbWidth : 500, thumbHeight : 500, largeWidth : 1600, largeHeight : 1600, columns : 3, keepRatio : false } };
};

const LIST = {
	albums : [ album( 'summer' ), album( 'settings' ), album( 'new' ) ],
	ranges : { thumbWidth : [ 50, 2000 ], thumbHeight : [ 50, 2000 ], largeWidth : [ 100, 4000 ], largeHeight : [ 100, 4000 ], columns : [ 1, 6 ] },
	native : 'en_US', maxText : 300, maxKey : 64, maxImages : 200,
};

/** The script in a page of its own, over the router of the workbench: the address is a hash, the requests are answered by the test */
function world( hash ) {

	const page = element('div');
	const list = element('div');
	const view = element('div');
	const requests = [];
	const toasts = [];
	const state = { hash : hash ?? '#gallery' };

	list.id = 'gallery-list';
	view.id = 'gallery-album';
	page.appendChild( list );
	page.appendChild( view );

	const doc = documentOf( page );

	doc.documentElement = { lang : 'en-US' };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) { return element( tag ) };

	// The router of the workbench itself, over the panes this panel has: what the panel writes is read back by it
	const codec = shellRouter( { gallery : [ [ 'gallery', 'gallery' ], [ 'gallery-settings', 'settings' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		isActive : function() { return true },
		go : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
	} );

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return words[key] ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, callback : callback } ) } },
		admin : { router : router, dirty : { register : function() {}, isDirty : function() { return false }, refresh : function() {}, guard : function( names, proceed ) { proceed() } }, workLocale : { current : 'en_US', native : 'en_US' } },
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, CSS : { escape : function( value ) { return String( value ) } } };

	sandbox.window = { Nino : Nino, location : { hash : '', href : 'http://workbench.test/_admin/' }, setTimeout : function() { return 0 }, clearTimeout : function() {} };

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	Nino.adminUi.say = function( text ) { toasts.push( text ) };
	load( relative, context );

	const w = {
		Nino : Nino, list : list, view : view, state : state, codec : codec, panel : Nino.admin.gallery, requests : requests, toasts : toasts,
		// The shell shows the panel at an address, and the server answers the list
		at : function( where ) {
			state.hash = where;
			w.panel.showCurrent();
			requests.shift().callback( { status : 200, responseJSON : JSON.parse( JSON.stringify( LIST ) ) } );
		},
	};

	return w;
}

// The server's answer to the oldest request that is waiting
const answer = function( w, body ) { w.requests.shift().callback( { status : 200, responseJSON : body } ) };
const hidden = function( node ) { return node.classList.contains('admin-hidden') };
// The key of the album on screen, and the part of it that is open: the tab that is active
const title = function( root ) { return findAll( root, function( node ) { return node.className === 'nino-admin-contextbar-code' } ).map( function( node ) { return node.textContent } ).join() };
const open = function( w ) { return findAll( w.view, function( node ) { return String( node.className ).indexOf('nino-admin-tab is-active') !== -1 } ).map( function( node ) { return node.textContent } ).join() };

// --- A. a key or a word ---------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.at('#gallery/gallery/summer');
	check( 'A. an album is opened by its key, at its pictures: the address stays what it is', hidden( w.view ) === false && title( w.view ) === 'summer' && w.state.hash === '#gallery/gallery/summer' && open( w ) === 'Pictures' );

	w.at('#gallery/gallery/summer/@settings');
	check( 'A. the word @settings is the part of the album with its name and its sizes, and the address keeps it', open( w ) === 'Name and sizes' && w.state.hash === '#gallery/gallery/summer/@settings' );

	const called = world();

	called.at('#gallery/gallery/settings');
	check( 'A. an album called settings is an album: its key is no word, and it opens at its pictures', hidden( called.view ) === false && title( called.view ) === 'settings' && open( called ) === 'Pictures' && called.state.hash === '#gallery/gallery/settings' );

	called.at('#gallery/gallery/settings/@settings');
	check( 'A. the album called settings has the part @settings, and the two are told apart by the segment as it is written', open( called ) === 'Name and sizes' && title( called.view ) === 'settings' && called.state.hash === '#gallery/gallery/settings/@settings' );

	called.at('#gallery/gallery/settings/settings');
	check( 'A. the key settings behind an album is no word, and no part: the pictures of the album stand, written without it', open( called ) === 'Pictures' && called.state.hash === '#gallery/gallery/settings' );

	check( 'A. every address the panel wrote or drew was read back by the router as the same screen and the same parts, the word as a word and the key as a key', [ w, called ].every( function( one ) { return one.codec.mismatches.length === 0 } )
		&& w.codec.written.some( function( written ) { return written.parts.length === 2 && typeof written.parts[1] === 'object' } ) && called.codec.written.some( function( written ) { return written.parts[0] === 'settings' && typeof written.parts[1] === 'object' } ) );
}

// --- C. what the panel does not know ---------------------------------------------------------------------------------------------

{
	[ '#gallery/gallery/nosuch', '#gallery/gallery/%40settings', '#gallery/gallery/@settings', '#gallery/gallery/@zzz/summer' ].forEach( function( hash ) {

		const w = world();

		w.at( hash );
		check( 'C. '+ hash+ ' is no album of the panel: the albums stand, and the address is written over by the one of the albums', w.state.hash === '#gallery' && hidden( w.list ) === false );
	} );

	const w = world();

	w.at('#gallery/gallery/summer/@zzz');
	check( 'C. a word of the part that the panel has not is the pictures of the album', w.state.hash === '#gallery/gallery/summer' );
}

// --- D. saving ----------------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.at('#gallery/gallery/summer/@settings');
	findAll( w.view, function( node ) { return node.tagName === 'FORM' } )[0].listeners.submit[0]( { preventDefault : function() {} } );

	const sent = w.requests[0];

	check( 'D. the form of an album is sent as album-save, and nothing is said before the server has answered', sent !== undefined && sent.action === 'gallery/album-save' && w.toasts.length === 0 );

	answer( w, { albums : JSON.parse( JSON.stringify( LIST.albums ) ) } );

	check( 'D. a saved album says so once: the bar of the form says it, and the panel adds nothing', w.requests.length === 0 && w.toasts.length === 1 && w.toasts[0] === workbench['/_admin/common/msg/saved'] && workbench['/_admin/common/msg/saved'] !== '' );
}

done();
