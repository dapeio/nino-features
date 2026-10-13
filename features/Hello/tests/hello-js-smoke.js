/**
 *	Nino
 *	hello-js-smoke.js	What the panel's script does over the kernel's own
 *										Nino.admin.js: it asks for hello/list when the pane is
 *										first shown and draws the name it is given, a save posts
 *										the field and the status line says it, a refusal is told
 *										in the sentence of its code and ends in done( false ),
 *										and the entry the shell asks about unsaved input follows
 *										the field. Only Nino itself is a stand-in: the requests
 *										a panel makes through Nino.adminUi.api.call() are kept
 *										for the test to answer, and the words are the ones of
 *										the text files, in both interface languages.
 *
 *										The helpers - check(), done(), fills(), load(), findAll(),
 *										fire(), element() and documentOf() - are those of
 *										tests/harness.js of the checkout, three levels up or the
 *										one NINO_ROOT names. hello-smoke.php runs this where node
 *										is on the path.
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
const { check, done, fills, load, findAll, fire, element, documentOf } = require( path.join( root, 'tests/harness.js' ) );

/**
 *	A file of the feature the way load() and fills() want it: from the root of
 *	the checkout, which the feature sits outside of when it is tested from the
 *	catalogue
 *
 *	@param		{string}	relative		A path below features/Hello/
 *
 *	@return		{string}
 */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

/**
 *	The panel's script in a page of its own, in one interface language: the
 *	kernel's Nino.admin.js and the feature's admin.js, over a Nino that keeps
 *	the requests, the entry the panel registers and the words it asks for
 *
 *	@param		{string}		locale		en_US or de_DE: the words of the text files
 *	@param		{boolean}		[shell]		Whether the page has the workbench's shell, which registers the dirty entries; true by default
 *
 *	@return		{Object}
 */
function world( locale, shell ) {

	// The words as the workbench has them: its own, and the feature's merged in
	const words = Object.assign( fills( '_admin/text/'+ locale+ '.php' ), fills( own( 'text/'+ locale+ '.php' ) ) );
	const missing = [];
	const requests = [];
	const registered = [];
	let refreshed = 0;

	const mount = element('div');
	mount.id = 'hello-list';

	// A control's name is its attribute as well, as in a page: the status line finds the field the server refused by it
	const page = documentOf( mount );
	const make = page.createElement;
	page.createElement = function( tag ) {
		const el = make( tag );
		Object.defineProperty( el, 'name', { get : function() { return el.attributes.name ?? '' }, set : function( value ) { el.setAttribute( 'name', value ) } } );
		return el;
	};

	const Nino = {
		content : { getText : function( key ) {
			if( words[key] === undefined )
				missing.push( key );
			return words[key] ?? '';
		} },
	};

	if( shell !== false )
		Nino.admin = { dirty : { register : function( name, entry ) { registered.push( { name : name, entry : entry } ) }, refresh : function() { refreshed++ } } };

	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : { Nino : Nino } } );
	load( '_admin/assets/Nino.admin.js', context );

	// The one way a panel talks to the server is the kernel's api.call(): what it is asked for is kept, to be answered
	Nino.adminUi.api.call = function( action, payload, callback ) {
		requests.push( { action : action, payload : payload, callback : callback, answered : false } );
	};

	load( own( 'assets/admin.js' ), context, 'admin.js' );

	const find = function( predicate ) { return findAll( mount, predicate )[0] ?? null };

	return {
		panel : Nino.admin.hello,
		mount : mount,
		requests : requests,
		registered : registered,
		missing : missing,
		refreshed : function() { return refreshed },
		entry : function() { return registered[0].entry },
		input : function() { return find( function( el ) { return el.tagName === 'INPUT' } ) },
		button : function() { return find( function( el ) { return el.tagName === 'BUTTON' } ) },
		line : function() { return find( function( el ) { return el.classList.contains('nino-admin-status') } ) },
		labels : function() { return findAll( mount, function( el ) { return el.tagName === '#TEXT' } ).map( function( el ) { return el.textContent } ) },

		// The answer of the server to the request that is out, as api.call() hands it to its callback
		answer : function( status, body ) {
			const open = requests.filter( function( request ) { return request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( status, body );
		},
	};
}

/** The panel shown and its list answered: the screen is drawn */
function drawn( locale, data, shell ) {
	const w = world( locale, shell );
	w.panel.showCurrent();
	w.answer( 200, data );
	return w;
}

const LIST = { name : 'Ada', longest : 40 };
const LONG = { error : 'the name is too long', code : 'hello_long', params : [ 40 ], field : 'name' };


console.log( 'Nino.admin.hello - the screen\n' );

{
	const w = world('en_US');

	check( 'nothing is asked before the pane is shown, and the shell has no entry yet', w.requests.length === 0 && w.registered.length === 0 );

	w.panel.showCurrent();
	w.panel.showCurrent();
	check( 'showing it asks for hello/list, once however often the shell shows it while the answer is out', w.requests.length === 1 && w.requests[0].action === 'hello/list' && JSON.stringify( w.requests[0].payload ) === '{}' );
	check( '...and it registers its entry under its uri, which holds nothing unsaved while nothing is drawn', w.registered.length === 1 && w.registered[0].name === 'hello' && w.entry().isDirty() === false );

	w.answer( 200, LIST );
	check( 'the stored name is drawn into a field called name, which takes no more than the longest name the list gave', w.input().value === 'Ada' && w.input().name === 'name' && w.input().maxLength === 40 );
	check( '...with the word for the label of a name, and a button that saves with its word', w.labels().join() === 'Name' && w.button().textContent === 'Save' && w.button().className === 'nino-admin-btn-primary' );

	w.panel.showCurrent();
	check( 'showing the pane again asks nothing: the screen is the answer of the first showing', w.requests.length === 1 );
}


console.log( '\nNino.admin.hello - saving\n' );

{
	const w = drawn( 'en_US', LIST );

	w.input().value = ' Grace ';
	fire( w.button(), 'click' );
	check( 'a save posts hello/save with what the field holds, and the status line says it is saving meanwhile', w.requests.length === 2 && w.requests[1].action === 'hello/save'
		&& JSON.stringify( w.requests[1].payload ) === '{"name":" Grace "}' && w.line().dataset.state === 'saving' );

	w.answer( 200, { name : 'Grace' } );
	check( '...the field then holds the name the server stored, and the line says saved', w.input().value === 'Grace' && w.line().dataset.state === 'saved' );
	check( '...the shell is told the panel is clean, and the entry says so', w.refreshed() === 1 && w.entry().isDirty() === false );

	w.input().dispatchEvent( { type : 'input', bubbles : true } );
	check( 'typing into the field turns the line into unsaved changes', w.line().dataset.state === 'dirty' );
}

{
	const w = drawn( 'en_US', LIST, false );

	let threw = null;
	try {
		fire( w.button(), 'click' );
		w.answer( 200, { name : 'Ada' } );
	}
	catch( error ) { threw = error }
	check( 'a page without the shell\'s registry saves all the same', threw === null && w.line().dataset.state === 'saved' && w.registered.length === 0 );
}


console.log( '\nNino.admin.hello - a refusal\n' );

{
	const w = drawn( 'en_US', LIST );
	const told = [];

	w.input().value = 'x'.repeat( 41 );
	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 400, LONG );
	check( 'a refused save is told in the sentence of its code, with the limit in it', w.line().dataset.state === 'error' && w.line().textContent === 'The name may have up to 40 characters.' );
	check( '...and ends in done( false ), the shell not told that anything was saved', told.length === 1 && told[0] === false && w.refreshed() === 0 );
	check( '...the field the server named is marked and has the cursor, and keeps what was typed', w.input().attributes['aria-invalid'] === 'true' && w.input().focused === true
		&& w.input().value === 'x'.repeat( 41 ) && w.entry().isDirty() === true );

	fire( w.input(), 'input' );
	check( '...and the mark goes with the next thing typed', w.input().hasAttribute('aria-invalid') === false );
}

{
	const w = drawn( 'en_US', LIST );
	const told = [];

	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 500, { error : 'could not write the name' } );
	check( 'a failure nobody can cause shows what the server said, the status in front of it, and ends in done( false )', w.line().dataset.state === 'error'
		&& w.line().textContent === '(500) could not write the name' && told.join() === 'false' );

	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 502, null );
	check( '...and with nothing said at all, the panel\'s own sentence for a save that failed', w.line().textContent === '(502) It could not be saved.' && told.join() === 'false,false' );

	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 200, { name : 'Ada' } );
	check( '...and the next save that goes through ends in done( true )', told[2] === true && w.line().dataset.state === 'saved' );
}


console.log( '\nNino.admin.hello - unsaved input\n' );

{
	const w = drawn( 'en_US', LIST );
	const entry = w.entry();

	check( 'the entry holds nothing unsaved while the field has what was saved', entry.isDirty() === false );

	w.input().value = 'Adam';
	check( '...and does while the field differs from it', entry.isDirty() === true );

	entry.discard();
	check( '...discard puts the saved name back into the field', w.input().value === 'Ada' && entry.isDirty() === false );
}


console.log( '\nNino.admin.hello - a failed load\n' );

{
	const w = world('en_US');

	w.panel.showCurrent();
	w.answer( 500, null );
	check( 'a failed load is said in the mount, in the panel\'s own sentence', w.mount.children.length === 1 && w.mount.children[0].className === 'nino-admin-error'
		&& w.mount.children[0].textContent === '(500) It could not be loaded.' );

	w.panel.showCurrent();
	w.answer( 200, LIST );
	check( '...and showing the pane again asks again, and draws the screen', w.requests.length === 2 && w.input().value === 'Ada' );
}


console.log( '\nNino.admin.hello - the words, in both languages\n' );

const sentences = {
	en_US : { long : 'The name may have up to 40 characters.', load : '(500) It could not be loaded.', save : '(502) It could not be saved.', name : 'Name', button : 'Save' },
	de_DE : { long : 'Der Name darf bis zu 40 Zeichen haben.', load : '(500) Es konnte nicht geladen werden.', save : '(502) Es konnte nicht gespeichert werden.', name : 'Name', button : 'Speichern' },
};

Object.keys( sentences ).forEach( function( locale ) {

	const says = sentences[locale];
	const w = drawn( locale, LIST );

	check( locale+ ': the label and the button say their words', w.labels().join() === says.name && w.button().textContent === says.button );

	w.entry().save( function() {} );
	w.answer( 400, LONG );
	check( locale+ ': the code hello_long is told in its sentence', w.line().textContent === says.long );

	w.entry().save( function() {} );
	w.answer( 502, null );
	check( locale+ ': a save that failed with nothing said is told in the panel\'s sentence', w.line().textContent === says.save );

	const failed = world( locale );
	failed.panel.showCurrent();
	failed.answer( 500, null );
	check( locale+ ': a load that failed is told in the panel\'s sentence', failed.mount.children[0].textContent === says.load );

	check( locale+ ': every word the script asked for is in the text files', w.missing.length === 0 && failed.missing.length === 0 );
} );

done();
