/**
 *	Nino features
 *	hello-js-smoke.js	What the script of the panel of the Hello feature does over the kernel's own Nino.admin.js: the form is drawn from the answer
 *										with the stored name, the save sends the name and draws the name the server kept, and says "Saved." once. Only Nino itself is
 *										a stand-in: the requests are kept for the test to answer and the words are the ones of the text files.
 *
 *										The helpers - check(), done(), findAll(), element(), documentOf() and load() - are those of tests/harness.js of the checkout, three
 *										levels up or the one NINO_ROOT names. hello-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Hello/tests/hello-js-smoke.js
 *	       NINO_ROOT=../nino node features/Hello/tests/hello-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, findAll, element, documentOf } = require( path.join( root, 'tests/harness.js' ) );

/** A file of the feature the way load() and fills() want it: from the root of the checkout */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

const words = Object.assign( fills( '_admin/text/en_US.php' ), fills( own('text/en_US.php') ) );
const requests = [];
const toasts = [];
const registered = {};

const mount = element('div');
mount.id = 'hello-list';
const page = documentOf( mount );

const stub = {
	content : { getText : function( key ) { return words[key] ?? '' } },
	events : { bindCallback : function() {} },
	admin : { router : { set : function() {} }, dirty : { register : function( uri, spec ) { registered[uri] = spec }, isDirty : function() { return false }, refresh : function() {} } },
};
const context = vm.createContext( { console : console, document : page, Nino : stub, window : { Nino : stub, sessionStorage : { getItem : function() { return null }, setItem : function() {} }, location : { hash : '' } } } );

load( '_admin/assets/Nino.admin.js', context );
stub.adminUi.api.call = function( action, payload, callback ) { requests.push( { action : action, payload : payload, callback : callback } ) };
stub.adminUi.say = function( text ) { toasts.push( text ) };
load( own('assets/admin.js'), context, 'admin.js' );

const panel = stub.admin.hello;
const last = ( action ) => requests.filter( r => r.action === action ).pop();
const input = () => findAll( mount, el => el.id === 'hello-name' )[0];

console.log('Hello - the panel\n');

panel.showCurrent();
check( 'the screen is asked for from hello/list', last('hello/list') !== undefined );
last('hello/list').callback( 200, { name : 'World', longest : 40 } );

check( 'one form with the stored name in its field and one main button', findAll( mount, el => el.tagName === 'FORM' ).length === 1 && input().value === 'World'
	&& findAll( mount, el => ( el.className || '' ).includes('nino-admin-btn-primary') ).length === 1 );

input().value = 'Ada';
findAll( mount, el => el.tagName === 'FORM' )[0].dispatchEvent( { type : 'submit', preventDefault : function() {} } );

check( 'the save sends the name, and nothing is said before the server has answered', last('hello/save') !== undefined && last('hello/save').payload.name === 'Ada' && toasts.length === 0 );

last('hello/save').callback( 200, { name : 'Ada' } );

check( 'a saved name says so once: the bar of the form says it, and the panel adds nothing', toasts.length === 1 && toasts[0] === words['/_admin/common/msg/saved'] && words['/_admin/common/msg/saved'] !== '' );
check( 'the field holds what the server kept, and the panel reports no unsaved input', input().value === 'Ada' && registered.hello.isDirty() === false );

done();
