/**
 *	Nino features
 *	redirects-admin-js-smoke.js	What the script of the panel does with the address, over the kernel's stand-in of a page, the real Nino.adminUi and the
 *											real router of the workbench, a few pins:
 *
 *											A. the tabs and the probe are words: @missing and @probe, written as they are, and the rules are the screen as it starts
 *											B. what the panel writes into the address is read back by the router as the same screen and the same parts
 *											C. an address the panel does not know - a key where a word stands, a word it has not - is the rules
 *
 *											The helpers - check(), done(), findAll(), fire(), element(), documentOf(), load() and shellRouter() - are those of tests/harness.js
 *											of the checkout, three levels up or the one NINO_ROOT names. redirects-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Redirects/tests/redirects-admin-js-smoke.js
 *	       NINO_ROOT=../nino node features/Redirects/tests/redirects-admin-js-smoke.js
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

const LIST = {
	rules : [], routes : [], misses : [ { path : '/old', count : 2, last : '2026-01-01 10:00:00' } ], statuses : [ 301, 302 ], recording : true, limit : 500, notes : [],
};

/** The script in a page of its own, over the router of the workbench: the address is a hash, the requests are answered by the test */
function world( hash ) {

	const page = element('div');
	const list = element('div');
	const probe = element('div');
	const requests = [];
	const state = { hash : hash ?? '#redirects' };

	list.id = 'redirects-list';
	probe.id = 'redirects-probe';
	page.appendChild( list );
	page.appendChild( probe );

	const doc = documentOf( page );

	doc.documentElement = { lang : 'en-US' };
	doc.body = element('body');
	doc.activeElement = null;
	doc.createElement = function( tag ) { return element( tag ) };

	// The router of the workbench itself, over the panes this panel has: what the panel writes is read back by it
	const codec = shellRouter( { redirects : [ [ 'redirects', 'redirects' ], [ 'redirects-settings', 'settings' ] ] } );
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
	load( relative, context );

	const w = {
		Nino : Nino, list : list, probe : probe, state : state, codec : codec, panel : Nino.admin.redirects,
		// The shell shows the panel at an address, and the server answers the list
		at : function( where, extra ) {
			state.hash = where;
			w.panel.showCurrent();
			requests.shift().callback( { status : 200, responseJSON : Object.assign( JSON.parse( JSON.stringify( LIST ) ), extra ?? {} ) } );
		},
	};

	return w;
}

const hidden = function( node ) { return node.classList.contains('admin-hidden') };
// The tab of the strip that is active
const open = function( w ) { return findAll( w.list, function( node ) { return String( node.className ).indexOf('nino-admin-tab is-active') !== -1 } ).map( function( node ) { return node.textContent } ).join() };

// --- A. the words ------------------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.at('#redirects');
	check( 'A. the rules are the screen as it starts: the tab of the rules is active and the address is the panel', open( w ) === words['/_admin/redirects/tab/rules'] && w.state.hash === '#redirects' && hidden( w.probe ) === true );

	w.at('#redirects/redirects/@missing');
	check( 'A. the word @missing is the tab of the addresses nothing answered, and the address keeps it', open( w ).indexOf( words['/_admin/redirects/tab/missing'] ) === 0 && w.state.hash === '#redirects/redirects/@missing' );

	w.at('#redirects/redirects/@probe');
	check( 'A. the word @probe is the probe, a level of its own: the list is behind it', hidden( w.probe ) === false && hidden( w.list ) === true && w.state.hash === '#redirects/redirects/@probe' );

	check( 'A. every address the panel wrote or drew was read back by the router as the same screen and the same parts, the words as words', w.codec.mismatches.length === 0 && w.codec.written.some( function( written ) { return typeof written.parts[0] === 'object' && String( written.parts[0] ) === '@probe' } )
		&& w.codec.written.some( function( written ) { return typeof written.parts[0] === 'object' && String( written.parts[0] ) === '@missing' } ) );
}

// --- the way to the settings is an address the router writes -----------------------------------------------------------------------

{
	const w = world();

	w.at( '#redirects/redirects/@missing', { recording : false } );

	const links = findAll( w.list, function( node ) { return node.tagName === 'A' && node.href !== undefined && String( node.href ).indexOf('#redirects') === 0 } );

	check( 'A. where remembering is off the second tab says so, with a link to the tab of the settings: the address the router writes for it, #redirects/settings, which it reads back as that tab', links.length === 1 && links[0].href === '#redirects/settings' && w.codec.read( links[0].href ).panel === 'redirects-settings' );
}

// --- C. what the panel does not know -------------------------------------------------------------------------------------------

{
	[ [ '#redirects/redirects/probe', 'a key' ], [ '#redirects/redirects/%40probe', 'a key written as the word' ], [ '#redirects/redirects/@zzz', 'a word it has not' ], [ '#redirects/redirects/missing', 'a key' ] ].forEach( function( pair ) {

		const w = world();

		w.at( pair[0] );
		check( 'C. '+ pair[0]+ ' ('+ pair[1]+ ') is no tab of the panel: the rules stand and the address is written over by the one of the rules', open( w ) === words['/_admin/redirects/tab/rules'] && w.state.hash === '#redirects' && hidden( w.probe ) === true );
	} );
}

done();
