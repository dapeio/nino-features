/**
 *	Nino
 *	vcard-js-smoke.js	What the panel's script does over the kernel's own Nino.admin.js:
 *										it asks for vcard/list when the pane is first shown and draws
 *										every detail into a control the server can name, read() sends
 *										what the controls hold in the order of the screen, a link can
 *										be moved, removed and added with the numbers of its fields
 *										following its place, the entry the shell asks about unsaved
 *										input follows the controls, a refusal is told at its field,
 *										and the button Use as start page asks before it asks the
 *										server and draws the answer without throwing the form away.
 *										Only Nino itself is a stand-in: the requests a panel makes
 *										through Nino.adminUi.api.call() are kept for the test to
 *										answer, a question of Nino.adminUi.confirm() is kept to be
 *										answered, and the words are the ones of the text files, in
 *										both interface languages.
 *
 *										The helpers are those of tests/harness.js of the checkout, three
 *										levels up or the one NINO_ROOT names. vcard-smoke.php runs this
 *										where node is on the path.
 *
 *	Usage: node features/Vcard/tests/vcard-js-smoke.js
 *	       NINO_ROOT=../nino node features/Vcard/tests/vcard-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, findAll, fire, element, documentOf } = require( path.join( root, 'tests/harness.js' ) );

/** A file of the feature the way load() and fills() want it: from the root of the checkout */
function own( relative ) {
	return path.relative( root, fs.realpathSync( path.join( __dirname, '..', relative ) ) );
}

/**
 *	The panel's script in a page of its own, in one interface language
 *
 *	@param		{string}		locale		en_US or de_DE
 *
 *	@return		{Object}
 */
function world( locale ) {

	const words = Object.assign( fills( '_admin/text/'+ locale+ '.php' ), fills( own( 'text/'+ locale+ '.php' ) ) );
	const missing = [];
	const requests = [];
	const registered = [];
	const asked = [];
	let refreshed = 0;

	const mount = element('div');
	mount.id = 'vcard-list';

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
		admin : { dirty : { register : function( name, entry ) { registered.push( { name : name, entry : entry } ) }, refresh : function() { refreshed++ } } },
	};

	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : { Nino : Nino } } );
	load( '_admin/assets/Nino.admin.js', context );

	Nino.adminUi.api.call = function( action, payload, callback, extra ) {
		requests.push( { action : action, payload : payload, callback : callback, extra : extra, answered : false } );
	};
	Nino.adminUi.confirm = function( level, options ) { asked.push( { level : level, options : options } ); return true };
	Nino.adminUi.checkImage = function( file, then ) { then( null ) };

	load( own( 'assets/admin.js' ), context, 'admin.js' );

	const all = function( predicate ) { return findAll( mount, predicate ) };
	const field = function( name ) { return all( function( el ) { return el.dataset.field === name } )[0] ?? null };

	return {
		panel : Nino.admin.vcard,
		mount : mount,
		requests : requests,
		registered : registered,
		asked : asked,
		missing : missing,
		refreshed : function() { return refreshed },
		entry : function() { return registered[0].entry },
		field : field,
		fields : function() { return all( function( el ) { return el.dataset.field !== undefined } ).map( function( el ) { return el.dataset.field } ) },
		button : function( label ) { return all( function( el ) { return el.tagName === 'BUTTON' && ( el.textContent === label || el.attributes['aria-label'] === label ) } )[0] ?? null },
		buttons : function( label ) { return all( function( el ) { return el.tagName === 'BUTTON' && el.attributes['aria-label'] === label } ) },
		rows : function() { return all( function( el ) { return el.classList.contains('vcard-link') } ) },
		line : function() { return all( function( el ) { return el.classList.contains('nino-admin-status') } )[0] },
		text : function() { return all( function() { return true } ).map( function( el ) { return el.textContent } ).join('|') },
		live : function() { return all( function( el ) { return el.attributes.role === 'status' && el.classList.contains('nino-admin-sr-only') } )[0] },
		answer : function( status, body ) {
			const open = requests.filter( function( request ) { return request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( status, body );
		},
	};
}

function screen( over ) {
	return Object.assign( {
		data : { format : 1, picture : 'auto', kind : 'person', name : 'Ada', phone : '', email : '', website : '', address : '', map : true, save : true,
			texts : { de_DE : { line : 'Zeile', text : '', hours : '' }, en_US : { line : 'Line', text : '', hours : '' } },
			links : [
				{ id : 'a1', url : 'https://a.test/1', labels : { de_DE : 'Eins', en_US : 'One' } },
				{ id : 'b2', url : 'https://a.test/2', labels : { de_DE : 'Zwei', en_US : 'Two' } },
				{ id : 'c3', url : 'https://a.test/3', labels : { de_DE : 'Drei', en_US : 'Three' } },
			] },
		locales : [ 'de_DE', 'en_US' ], native : 'de_DE', names : { de_DE : 'Deutsch', en_US : 'English (US)' },
		inherited : { name : 'Acme', phone : '', email : '', website : '', address : '', text : { de_DE : '', en_US : '' } },
		photo : { url : null, size : 480, alt : {} }, logo : false,
		page : { state : 'page', path : '/vcard', takenUri : '/home', file : '/vcard.vcf', start : { blocked : false, reason : '', from : '/home', name : 'Startseite', to : '/home', moves : true, gone : [ '/vcard' ] } },
		legal : { active : true, imprint : '/impressum', privacy : '/datenschutz' },
		limits : { name : 120, line : 120, text : 1000, hours : 160, address : 240, alt : 250, label : 60, url : 500, phone : 30, email : 254, links : 20 },
	}, over || {} );
}

function drawn( locale, over ) {
	const w = world( locale );
	w.panel.showCurrent();
	w.answer( 200, screen( over ) );
	return w;
}

const urls = function( w ) { return w.rows().map( function( row ) { return findAll( row, function( el ) { return el.tagName === 'INPUT' } )[0].value } ) };

console.log( 'Nino.admin.vcard - the screen\n' );

{
	const w = world('en_US');

	check( 'nothing is asked before the pane is shown', w.requests.length === 0 && w.registered.length === 0 );
	w.panel.showCurrent();
	check( 'showing it asks for vcard/list, and registers the entry under its uri', w.requests.length === 1 && w.requests[0].action === 'vcard/list' && w.registered[0].name === 'vcard' );
	w.answer( 200, screen() );

	check( 'every detail is a control the server can name: the details, a field for each language, and the links by their place', JSON.stringify( w.fields() ) === JSON.stringify( [
		'picture', 'name', 'kind', 'texts.de_DE.line', 'texts.en_US.line', 'texts.de_DE.text', 'texts.en_US.text', 'phone', 'email', 'website', 'address',
		'links.0.url', 'links.0.labels.de_DE', 'links.0.labels.en_US', 'links.1.url', 'links.1.labels.de_DE', 'links.1.labels.en_US', 'links.2.url', 'links.2.labels.de_DE', 'links.2.labels.en_US',
		'texts.de_DE.hours', 'texts.en_US.hours' ] ) );
	check( 'a language is named as the project names it, and the control by it for a screen reader', w.text().includes('Deutsch') && w.text().includes('English (US)') && w.field('texts.de_DE.line').attributes['aria-label'] === 'Line below the name (Deutsch)' );
	check( 'what the project knows is the placeholder of the detail it fills in, and the detail itself is what was stored', w.field('name').placeholder === 'Acme' && w.field('name').value === 'Ada' );
	check( 'the entry holds nothing unsaved while nothing was changed, and every word the script asked for is in the text files', w.entry().isDirty() === false && w.missing.length === 0 );
}


console.log( '\nNino.admin.vcard - what is sent\n' );

{
	const w = drawn( 'en_US' );
	const told = [];

	w.field('phone').value = '+44 20 7946 0000';
	w.field('texts.en_US.text').value = 'Likes to compute.';
	w.entry().save( function( ok ) { told.push( ok ) } );

	const sent = w.requests[1];
	check( 'a save posts vcard/save with every detail, the languages and the links in the order of the screen', sent.action === 'vcard/save' && sent.payload.name === 'Ada' && sent.payload.phone === '+44 20 7946 0000' && sent.payload.kind === 'person' && sent.payload.picture === 'auto'
		&& sent.payload.map === true && sent.payload.save === true && sent.payload.texts.en_US.text === 'Likes to compute.' && sent.payload.texts.de_DE.line === 'Zeile'
		&& JSON.stringify( sent.payload.links.map( function( link ) { return [ link.id, link.url, link.labels.de_DE ] } ) ) === JSON.stringify( [ [ 'a1', 'https://a.test/1', 'Eins' ], [ 'b2', 'https://a.test/2', 'Zwei' ], [ 'c3', 'https://a.test/3', 'Drei' ] ] )
		&& JSON.stringify( sent.payload.alt ) === '{}' );
	check( '...and the line says it is saving meanwhile', w.line().dataset.state === 'saving' );

	const answer = screen();
	answer.data.phone = '+44 20 7946 0000';
	w.answer( 200, answer );
	check( '...the screen is drawn from the answer, the line says saved, the shell is told, the focus is on the button that saved, and done( true )', w.line().dataset.state === 'saved' && w.refreshed() === 1 && told.join() === 'true'
		&& w.field('phone').value === '+44 20 7946 0000' && w.entry().isDirty() === false );
}


console.log( '\nNino.admin.vcard - the links\n' );

{
	const w = drawn( 'en_US' );

	check( 'the first link cannot move up and the last cannot move down', w.buttons('Move the link up')[0].disabled === true && w.buttons('Move the link down')[2].disabled === true && w.buttons('Move the link down')[0].disabled === false );

	fire( w.buttons('Move the link down')[0], 'click' );
	check( 'a link moved down changes the order, and the numbers of the fields follow the places', urls( w ).join() === 'https://a.test/2,https://a.test/1,https://a.test/3' && w.field('links.1.url').value === 'https://a.test/1' && w.field('links.0.labels.de_DE').value === 'Zwei' );
	check( '...the ends are the ends again, the entry says it holds something unsaved and the shell is asked', w.buttons('Move the link up')[0].disabled === true && w.entry().isDirty() === true && w.refreshed() === 1 );

	fire( w.buttons('Move the link up')[1], 'click' );
	check( '...and put back it holds nothing unsaved', urls( w ).join() === 'https://a.test/1,https://a.test/2,https://a.test/3' && w.entry().isDirty() === false );

	fire( w.buttons('Remove the link')[1], 'click' );
	check( 'a link removed is gone, the rest keep their order and their numbers, and the removal is said to a screen reader', urls( w ).join() === 'https://a.test/1,https://a.test/3' && w.field('links.1.url').value === 'https://a.test/3' && w.field('links.2.url') === null && w.live().textContent === 'The link is removed.' );
	check( '...the focus goes to the link that took its place, and the entry holds something unsaved', w.field('links.1.url').focused === true && w.entry().isDirty() === true );
	check( '...and the payload has two links, in order', ( function() { w.entry().save( function() {} ); return w.requests[w.requests.length - 1].payload.links.map( function( link ) { return link.id } ).join() === 'a1,c3' } )() );
}

{
	const w = drawn( 'en_US' );
	const add = w.button('Add a link');

	fire( add, 'click' );
	check( 'a link added is empty, has its fields and the cursor in its address, and a new id of its own', w.rows().length === 4 && w.field('links.3.url').value === '' && w.field('links.3.url').focused === true && w.entry().isDirty() === true );

	const limited = drawn( 'en_US', { limits : Object.assign( screen().limits, { links : 3 } ) } );
	check( 'the button to add stops at the limit the server named', limited.button('Add a link').disabled === true );

	const none = drawn( 'en_US', { data : Object.assign( screen().data, { links : [] } ) } );
	check( 'with no link the screen says so, and the button to add is there', none.text().includes('No links yet.') && none.rows().length === 0 && none.button('Add a link').disabled === false );
}


console.log( '\nNino.admin.vcard - unsaved input\n' );

{
	const w = drawn( 'en_US' );

	w.field('name').value = 'Grace';
	check( 'a changed detail is unsaved, and a line typed into the form says so', w.entry().isDirty() === true );
	w.field('name').dispatchEvent( { type : 'input', bubbles : true } );
	check( '...the status line says unsaved changes', w.line().dataset.state === 'dirty' );

	w.entry().discard();
	check( 'discard draws the saved details again', w.field('name').value === 'Ada' && w.entry().isDirty() === false );
}


console.log( '\nNino.admin.vcard - a refusal\n' );

{
	const w = drawn( 'en_US' );
	const told = [];

	w.field('links.1.url').value = 'javascript:alert(1)';
	w.entry().save( function( ok ) { told.push( ok ) } );
	w.answer( 400, { error : 'x', code : 'vcard_link', field : 'links.1.url' } );

	const control = w.field('links.1.url');
	const error = findAll( w.mount, function( el ) { return el.classList.contains('nino-admin-field-error') } )[0];
	check( 'a refused link is told in the sentence of its code, in the line and at the field', w.line().textContent.startsWith('This address is not allowed') && error !== undefined && error.textContent === w.line().textContent );
	check( '...the field is marked invalid, points at the sentence with aria-describedby, has the cursor and keeps what was typed', control.attributes['aria-invalid'] === 'true' && control.attributes['aria-describedby'] === error.id && control.focused === true && control.value === 'javascript:alert(1)' );
	check( '...done( false ) is told, and nothing is said saved', told.join() === 'false' && w.refreshed() === 0 );

	w.entry().save( function() {} );
	check( 'the next save takes the sentence at the field away before it asks', findAll( w.mount, function( el ) { return el.classList.contains('nino-admin-field-error') } ).length === 0 );
	w.answer( 500, null );
	check( 'a failure nobody can cause is told with its status', w.line().textContent === '(500) The vCard could not be saved.' );
}


console.log( '\nNino.admin.vcard - the button Use as start page\n' );

{
	const w = drawn( 'en_US' );
	const start = w.button('Use as start page');

	check( 'the card is not on /: the button is there, enabled, and the screen says where the card is and what holds /', start !== null && start.disabled === false && w.text().includes('The vCard is on the page /vcard.') && w.text().includes('The start page is another page of the project (/home)') );

	fire( start, 'click' );
	check( 'it asks first, as heavily as the workbench asks a big thing, and says where the page that holds / goes and which addresses of the card stop answering', w.asked.length === 1 && w.asked[0].level === 'big' && w.requests.length === 1
		&& w.asked[0].options.message === 'The vCard takes the start page /. The page “Startseite” moves from / to /home. The menus follow the pages, so they link to the new address. What stops answering afterwards: /vcard. Go on?'
		&& w.asked[0].options.yes === 'Use as start page' && w.asked[0].options.no === 'Cancel' && w.asked[0].options.opener === start );

	w.asked[0].options.onChoose( false );
	check( 'a no asks nothing of the server', w.requests.length === 1 );

	w.field('name').value = 'Grace';
	w.asked[0].options.onChoose( true );
	check( 'a yes posts vcard/start with nothing', w.requests.length === 2 && w.requests[1].action === 'vcard/start' && JSON.stringify( w.requests[1].payload ) === '{}' );

	w.answer( 200, Object.assign( screen(), { page : { state : 'start', path : '/', takenUri : '', file : '/vcard.vcf', start : null }, moved : { from : '/home', to : '/home', name : 'Startseite' } } ) );
	check( 'the answer redraws only what says where the page is: the button is gone, the status says start page, the page that moved is told', w.button('Use as start page') === null && w.text().includes('The vCard is the start page of the website.') && w.line().textContent === 'The vCard is the start page. The page “Startseite” moved from / to /home.' );
	check( '...and what was typed is still there, and unsaved', w.field('name').value === 'Grace' && w.entry().isDirty() === true );
	check( '...the screen warns what to do before the feature is switched off, and says it to a screen reader', w.text().includes('Before you switch this feature off') && w.live().textContent === w.line().textContent );
}

{
	const w = drawn( 'en_US', { page : { state : 'start', path : '/', takenUri : '', file : '/vcard.vcf', start : null } } );
	check( 'where the card is on / there is no button', w.button('Use as start page') === null && w.text().includes('Before you switch this feature off') );
}

{
	const w = drawn( 'en_US', { page : { state : 'page', path : '/vcard', takenUri : '', file : '/vcard.vcf', start : { blocked : false, reason : '', from : '', name : '', to : '', moves : false, gone : [ '/vcard' ] } } } );
	fire( w.button('Use as start page'), 'click' );
	check( 'where / is free the question is the short one', w.asked[0].options.message === 'The vCard takes the address /. What stops answering afterwards: /vcard. Go on?' );
	w.asked[0].options.onChoose( true );
	w.answer( 200, Object.assign( screen(), { page : { state : 'start', path : '/', takenUri : '', file : '/vcard.vcf', start : null }, moved : { from : '', to : '', name : '' } } ) );
	check( '...and nothing is said to have moved', w.line().textContent === 'The vCard is the start page.' );
}

{
	const w = drawn( 'en_US', { page : { state : 'page', path : '/vcard', takenUri : '/old', file : '/vcard.vcf', start : { blocked : true, reason : 'taken', from : '/old', name : 'Old', to : '/', moves : false, gone : [] } } } );
	check( 'where no address is free the button is off and says why', w.button('Use as start page').disabled === true && w.text().includes('No address is free for the page that holds /') );
}

{
	const w = drawn( 'en_US' );
	fire( w.button('Use as start page'), 'click' );
	w.asked[0].options.onChoose( true );
	w.answer( 409, { error : 'x', code : 'vcard_start_taken' } );
	check( 'a refusal of the server is told in the sentence of its code, and the screen stays as it was', w.line().textContent.startsWith('No address is free for the page that holds /') && w.button('Use as start page') !== null );
}

{
	const w = drawn( 'en_US', { page : { state : 'detached', path : '/', takenUri : '', file : '/vcard.vcf', start : null } } );
	check( 'a route for / that is named for the card and draws another template is not said to show it, and no button pretends to mend it', w.text().includes('The route / is meant for the vCard but draws another template') && w.text().includes('The vCard is the start page of the website.') === false && w.button('Use as start page') === null );
}


{
	const w = drawn( 'en_US', { page : { state : 'detached', path : '/card', takenUri : '/home', file : '/vcard.vcf', start : { blocked : true, reason : 'template', from : '/home', name : 'Home', to : '/', moves : false, gone : [] } } } );
	check( 'where the template of the card does not draw it the button is off and says that, not that no address is free', w.button('Use as start page').disabled === true && w.text().includes('The template page-vcard does not draw the card') && w.text().includes('No address is free') === false );
}

{
	const w = drawn( 'en_US', { page : { state : 'detached', path : '/card', takenUri : '/home', file : '/vcard.vcf', start : { blocked : false, reason : '', from : '/home', name : 'Home', to : '/home', moves : true, gone : [] } } } );
	check( 'a card that is detached from its template can still be given /: the button is there, and the screen says where the card is not', w.button('Use as start page').disabled === false && w.text().includes('The route /card is meant for the vCard but draws another template') );
	fire( w.button('Use as start page'), 'click' );
	check( '...and the question names no address that stops answering, because none does', w.asked.length === 1 && w.asked[0].options.message === 'The vCard takes the start page /. The page “Home” moves from / to /home. The menus follow the pages, so they link to the new address. Go on?' );
}


console.log( '\nNino.admin.vcard - the photo\n' );

{
	const w = drawn( 'en_US', { photo : { url : '/public/images/vcard/photo.480x480.webp', size : 480, alt : { de_DE : 'Ada' } } } );

	check( 'a photo has its alt text in every language, and the buttons to replace and remove it', w.field('alt.de_DE').value === 'Ada' && w.field('alt.en_US').value === '' && w.button('Replace the photo') !== null && w.button('Remove the photo') !== null );

	fire( w.button('Remove the photo'), 'click' );
	check( 'removing the photo asks first, as a thing that cannot be taken back, and asks nothing of the server on a no', w.asked.length === 1 && w.asked[0].level === 'destructive' && w.asked[0].options.message === 'Remove the photo? The file is deleted.' && w.requests.length === 1 );
	w.asked[0].options.onChoose( false );
	check( '...', w.requests.length === 1 );
	w.asked[0].options.onChoose( true );
	check( 'a yes posts vcard/remove', w.requests[1].action === 'vcard/remove' );
	w.answer( 200, screen() );
	check( '...and the photo is gone from the screen, without the form being drawn again', w.field('alt.de_DE') === null && w.button('Choose a photo') !== null && w.text().includes('No photo yet.') );
}

{
	const w = drawn( 'en_US' );
	check( 'a project without a logo is told where it is set, with the way there', w.text().includes('The project has no logo yet') && findAll( w.mount, function( el ) { return el.tagName === 'A' && el.href === '#images' } ).length === 1 );
}


console.log( '\nNino.admin.vcard - the words, in both languages\n' );

[ [ 'en_US', 'Use as start page', 'This address is not allowed' ], [ 'de_DE', 'Als Startseite verwenden', 'Diese Adresse ist nicht erlaubt' ] ].forEach( function( entry ) {

	const w = drawn( entry[0] );

	check( entry[0]+ ': the button says its word', w.button( entry[1] ) !== null );
	w.entry().save( function() {} );
	w.answer( 400, { error : 'x', code : 'vcard_link', field : 'links.0.url' } );
	check( entry[0]+ ': the sentence of a refusal is in the language', w.line().textContent.startsWith( entry[2] ) );
	check( entry[0]+ ': every word the script asked for is in the text files', w.missing.length === 0 );
} );

done();
