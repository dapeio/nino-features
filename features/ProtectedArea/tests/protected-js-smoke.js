/**
 *	Nino
 *	protected-js-smoke.js	What the script of the panel of the Protected area feature does over the kernel's own
 *												Nino.admin.js: the form is drawn from the answer of the state with the paths as lines and two
 *												fields for a new password; a password that is short or typed differently the second time is
 *												refused before anything is sent; the save sends the lines and the password, and draws the
 *												form again from the answer with the password fields empty; a value the server refused is said
 *												under its field; "Sign everybody out" is a question of the level big, in red, and sends
 *												nothing before the answer is yes. Only Nino itself is a stand-in: the requests are kept for the
 *												test to answer, the question is kept and the words are the ones of the text files.
 *
 *	Usage: node features/ProtectedArea/tests/protected-js-smoke.js
 *	       NINO_ROOT=../nino node features/ProtectedArea/tests/protected-js-smoke.js
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
const questions = [];
const toasts = [];
const registered = {};

const mount = element('div');
mount.id = 'protected-form';
const page = documentOf( mount );

const stub = {
	content : { getText : function( key ) { return words[key] ?? '' } },
	events : { bindCallback : function() {} },
	admin : { router : { set : function() {} }, dirty : { register : function( uri, spec ) { registered[uri] = spec }, isDirty : function() { return false }, refresh : function() {} } },
};
const context = vm.createContext( { console : console, document : page, Nino : stub, window : { Nino : stub, sessionStorage : { getItem : function() { return null }, setItem : function() {} }, location : { hash : '' } } } );

load( '_admin/assets/Nino.admin.js', context );
stub.adminUi.api.call = function( action, payload, callback ) { requests.push( { action : action, payload : payload, callback : callback } ) };
stub.adminUi.confirm = function( level, options ) { questions.push( { level : level, options : options } ); return true };
stub.adminUi.say = function( text ) { toasts.push( text ) };
load( own('assets/admin.js'), context, 'admin.js' );

const panel = stub.admin.protected;
const state = ( extra ) => Object.assign( { paths : [ '/intern', '/members' ], hasPassword : true, minLength : 8, maxLength : 200 }, extra ?? {} );
const last = ( action ) => requests.filter( r => r.action === action ).pop();
const control = ( id ) => findAll( mount, el => el.id === id )[0];
const type = ( id, value ) => { control( id ).value = value };
const submit = () => findAll( mount, el => el.tagName === 'FORM' )[0].dispatchEvent( { type : 'submit', preventDefault : function() {} } );
const errors = () => findAll( mount, el => ( el.className || '' ).includes('nino-admin-field-error') );

console.log('Protected area - the panel\n');

panel.showCurrent();
check( 'the screen is asked for from protected/state', last('protected/state') !== undefined );
last('protected/state').callback( 200, state() );

const form = findAll( mount, el => el.tagName === 'FORM' )[0];
const buttons = findAll( mount, el => el.tagName === 'BUTTON' );
check( 'one form: a fieldset with its grid, the paths as the lines of a field, two password fields, one main button and the red one beside it',
	form.noValidate === true && findAll( form, el => el.tagName === 'FIELDSET' ).length === 1 && findAll( form, el => ( el.className || '' ).includes('nino-admin-fieldgrid') ).length === 1
	&& control('protected-paths').value === '/intern\n/members' && control('protected-pw').type === 'password' && control('protected-pw2').type === 'password'
	&& buttons.filter( b => b.className.includes('nino-admin-btn-primary') ).length === 1 && buttons.filter( b => b.className.includes('nino-admin-btn-danger') ).length === 1 );
check( 'no password is ever put into a field: both are empty, and what is said is whether one is set', control('protected-pw').value === '' && control('protected-pw2').value === ''
	&& findAll( mount, el => el.textContent === words['/_admin/protected/msg/haspw'] ).length === 1 );

type( 'protected-pw', 'short' );
type( 'protected-pw2', 'short' );
const sent = requests.length;
submit();
const barLine = () => findAll( mount, el => ( el.className || '' ).includes('nino-admin-status') )[0];
check( 'a password that is too short is refused before anything is sent, said under its field and in the line of the bar', requests.length === sent && errors().length === 1 && barLine().textContent === words['/_admin/protected/error/fields'] && words['/_admin/protected/error/fields'] !== '' );

type( 'protected-pw', 'a-long-enough-password' );
type( 'protected-pw2', 'another-one-entirely' );
submit();
check( 'two entries that differ are refused before anything is sent, said under the second field', requests.length === sent && errors().length === 1 );

type( 'protected-paths', '/intern\n/members\n/club' );
type( 'protected-pw2', 'a-long-enough-password' );
submit();
const save = last('protected/save');
check( 'the save sends the lines of the field and the password, and nothing else', save !== undefined && save.payload.paths === '/intern\n/members\n/club' && save.payload.pw === 'a-long-enough-password' && Object.keys( save.payload ).length === 2 );
page.activeElement = control('protected-pw2');
save.callback( 200, state( { paths : [ '/intern', '/members', '/club' ] } ) );
check( 'after the save the keyboard keeps its place: the field that had the focus has it in the form that is drawn again', control('protected-pw2').focused === true );
check( 'the form is drawn again from the answer: the new lines, both password fields empty, and a toast that says what a new password did',
	control('protected-paths').value === '/intern\n/members\n/club' && control('protected-pw').value === '' && control('protected-pw2').value === '' && toasts.includes( words['/_admin/protected/msg/pwsaved'] ) );

type( 'protected-paths', 'intern' );
submit();
last('protected/save').callback( 400, { error : 'x', code : 'protected_path', params : [ 'intern' ], field : 'paths' } );
check( 'a value the server refused is said under its field and the field keeps what was typed', errors().length === 1 && control('protected-paths').value === 'intern' );

const unsaved = registered.protected;
unsaved.discard();
check( 'unsaved input is reported to the shell: the field is not what the server holds, or a password is typed, and discarding puts it back', unsaved !== undefined && unsaved.isDirty() === false
	&& ( type( 'protected-paths', '/other' ), unsaved.isDirty() === true ) && ( unsaved.discard(), unsaved.isDirty() === false, control('protected-paths').value === '/intern\n/members\n/club' )
	&& ( type( 'protected-pw', 'x' ), unsaved.isDirty() === true ) );
unsaved.discard();

const out = findAll( mount, el => el.id === 'protected-signout' )[0];
const asked = requests.length;
out.dispatchEvent( { type : 'click' } );
check( 'Sign everybody out is a question of the level big, red, worded like the button, and sends nothing before the answer', questions.length === 1 && questions[0].level === 'big' && questions[0].options.kind === 'danger'
	&& questions[0].options.yes === words['/_admin/protected/label/signoutall'] && questions[0].options.title === words['/_admin/protected/label/signoutall'] && requests.length === asked );
questions[0].options.onChoose( false );
check( 'no sends nothing', requests.length === asked );
questions[0].options.onChoose( true );
check( 'yes sends protected/signout, and its answer is a toast', last('protected/signout') !== undefined && ( last('protected/signout').callback( 200, { signedOut : true } ), toasts.includes( words['/_admin/protected/msg/signedout'] ) ) );

done();
