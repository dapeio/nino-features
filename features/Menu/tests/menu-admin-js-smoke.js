/**
 *	Nino features
 *	menu-admin-js-smoke.js	What the script of the panel does with the address, over the kernel's stand-in of a page, the real Nino.adminUi and the
 *											real router of the workbench, a few pins:
 *
 *											A. a menu and an entry are keys, whatever they are called: new, settings, @new - the panel has no word, and none of them is one
 *											B. what the panel writes into the address is read back by the router as the same screen and the same parts
 *											C. an address the panel does not know - a menu that is not there, a word - is the table
 *											D. saving a menu says "Saved." once
 *
 *											The helpers - check(), done(), findAll(), fire(), element(), documentOf(), load() and shellRouter() - are those of tests/harness.js
 *											of the checkout, three levels up or the one NINO_ROOT names. menu-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Menu/tests/menu-admin-js-smoke.js
 *	       NINO_ROOT=../nino node features/Menu/tests/menu-admin-js-smoke.js
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

const MENUS = [ 'footer', 'new', 'settings', '@new' ];
const entry = function( id ) { return { id : id, kind : 'page', uri : '/', panel : null, name : id } };

const LIST = { menus : MENUS.map( function( key ) { return { key : key, entries : 2, panels : 0 } } ), limit : 12, pages : [ { uri : '/', name : 'Home', names : {} } ], locales : [ 'en_US' ], names : {}, native : 'en_US', slots : [] };
const LOAD = function( key ) { return { key : key, exists : true, version : 'v1', entries : [ entry('about'), entry('#2'), entry('new') ], texts : {}, taken : [], problems : [] } };

/** The script in a page of its own, over the router of the workbench: the address is a hash, the requests are answered by the test */
function world( hash ) {

	const page = element('div');
	const requests = [];
	const toasts = [];
	const registered = {};
	const state = { hash : hash ?? '#menu' };

	[ 'menu-list', 'menu-form', 'menu-entry' ].forEach( function( id ) {
		const mount = element('div');
		mount.id = id;
		page.appendChild( mount );
	} );

	const doc = documentOf( page );

	doc.documentElement = { lang : 'en-US' };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) { return element( tag ) };

	// The router of the workbench itself, over the panes this panel has: what the panel writes is read back by it
	const codec = shellRouter( { menu : [ [ 'menu', 'menu' ] ] } );
	const router = codec.stub( {
		current : function() { return codec.read( state.hash ) },
		isActive : function() { return true },
		go : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		set : function( panel, parts ) { state.hash = codec.check( panel, parts ) },
		leave : function( names, leaving, proceed ) { proceed() },
	} );

	const Nino = {
		dir : '/site',
		events : { bindCallback : function() {} },
		content : { getText : function( key ) { return words[key] ?? workbench[key] ?? '' } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { action : data.action, payload : JSON.parse( data.data ), callback : callback } ) } },
		admin : { router : router, dirty : { register : function( name, entry ) { registered[name] = entry }, isDirty : function() { return false }, refresh : function() {}, guard : function( names, proceed ) { proceed() } }, workLocale : { current : 'en_US', native : 'en_US' } },
	};

	const sandbox = { console : console, document : doc, Nino : Nino, URL : URL, encodeURIComponent : encodeURIComponent, CSS : { escape : function( value ) { return String( value ) } } };

	sandbox.window = { Nino : Nino, location : { hash : '', href : 'http://workbench.test/_admin/' }, setTimeout : function() { return 0 }, clearTimeout : function() {} };

	const context = vm.createContext( sandbox );

	load( '_admin/assets/Nino.admin.js', context );
	Nino.adminUi.say = function( text ) { toasts.push( text ) };
	load( relative, context );

	const w = {
		Nino : Nino, page : page, state : state, codec : codec, panel : Nino.admin.menu, requests : requests, toasts : toasts, registered : registered,
		// The shell shows the panel at an address; the server answers the list, and then the menu the address names
		at : function( where ) {
			state.hash = where;
			w.panel.showCurrent();
			requests.shift().callback( { status : 200, responseJSON : JSON.parse( JSON.stringify( LIST ) ) } );

			while( requests.length > 0 ) {
				const asked = requests.shift();
				asked.callback( { status : 200, responseJSON : LOAD( asked.payload.key ) } );
			}
		},
		// The server answers what is asked until nothing is: a save, the menu as it is held now, and the list that follows
		settle : function() {

			while( requests.length > 0 ) {
				const asked = requests.shift();
				const body = asked.action === 'menu/save' ? {} : ( asked.action === 'menu/load' ? LOAD( asked.payload.key ) : JSON.parse( JSON.stringify( LIST ) ) );
				asked.callback( { status : 200, responseJSON : body } );
			}
		},
	};

	return w;
}

const shown = function( w, id ) { return findAll( w.page, function( node ) { return node.id === id } )[0].classList.contains('admin-hidden') === false };

// --- A. keys, all of them ----------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.at('#menu');
	check( 'A. the table is the screen as it starts: the address is the panel', shown( w, 'menu-list' ) === true && w.state.hash === '#menu' );

	[ 'footer', 'new', 'settings' ].forEach( function( key ) {

		const m = world();

		m.at('#menu/menu/'+ key);
		check( 'A. the menu called '+ key+ ' is a menu: its view is open, with its entries, and the address keeps its key', shown( m, 'menu-form' ) === true && shown( m, 'menu-list' ) === false && m.state.hash === '#menu/menu/'+ key );
	} );

	const at = world();

	at.at('#menu/menu/%40new');
	check( 'A. the menu called @new is a menu: it is the key %40new - the panel has no word, and what a person calls a menu is what it is', shown( at, 'menu-form' ) === true && at.state.hash === '#menu/menu/%40new' );

	const entryNew = world();

	entryNew.at('#menu/menu/footer/new');
	check( 'A. an entry called new is an entry: it opens on its view and the address keeps its key', shown( entryNew, 'menu-entry' ) === true && entryNew.state.hash === '#menu/menu/footer/new' );

	const number = world();

	number.at('#menu/menu/footer/%232');
	check( 'A. the entry #2, which has no name of its own, is a key that needs encoding: one segment, read back as itself', shown( number, 'menu-entry' ) === true && number.state.hash === '#menu/menu/footer/%232' );

	check( 'A. every address the panel wrote or drew was read back by the router as the same screen and the same parts, the keys as keys', [ w, at, entryNew, number ].every( function( one ) { return one.codec.mismatches.length === 0 } )
		&& entryNew.codec.written.some( function( written ) { return written.parts.length === 2 && written.parts[1] === 'new' && written.parts.every( function( part ) { return typeof part === 'string' } ) } ) );
}

// --- C. what the panel does not know ---------------------------------------------------------------------------------------------

{
	[ '#menu/menu/@new', '#menu/menu/@zzz/footer' ].forEach( function( hash ) {

		const w = world();

		w.at( hash );
		check( 'C. '+ hash+ ' names no menu - a word is none of the panel\'s: the table stands, written #menu', shown( w, 'menu-list' ) === true && w.state.hash === '#menu' );
	} );
}

// --- D. saving ----------------------------------------------------------------------------------------------------------------------

{
	const w = world();
	const ends = [];

	w.at('#menu/menu/footer');
	w.registered.menu.save( function( ok ) { ends.push( ok ) } );

	check( 'D. the menu is sent as menu/save, and nothing is said before the server has answered', w.requests.length === 1 && w.requests[0].action === 'menu/save' && w.toasts.length === 0 );

	w.settle();

	check( 'D. a saved menu says so once: the bar of the view says it, and the panel adds nothing', ends.join() === 'true' && w.toasts.length === 1 && w.toasts[0] === workbench['/_admin/common/msg/saved'] && workbench['/_admin/common/msg/saved'] !== '' );
}

done();
