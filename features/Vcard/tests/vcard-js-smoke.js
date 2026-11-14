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
	const touched = [];
	let refreshed = 0;

	const mount = element('div');
	mount.id = 'vcard-list';

	const page = documentOf( mount );
	const pageListeners = {};
	const make = page.createElement;

	// The page itself hears what the "?" listens for beyond its own button: a press elsewhere, Escape
	page.addEventListener = function( name, fn ) { ( pageListeners[name] = pageListeners[name] || [] ).push( fn ) };
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
		admin : { dirty : { register : function( name, entry ) { registered.push( { name : name, entry : entry } ) }, refresh : function() { refreshed++ } }, touch : function( topic ) { touched.push( topic ) } },
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
		pressed : function( name, event ) { ( pageListeners[name] || [] ).forEach( function( fn ) { fn( event ) } ) },
		mount : mount,
		requests : requests,
		touched : touched,
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
		data : { format : 1, picture : 'auto', kind : 'person', name : 'Ada', phone : '', email : '', website : '', address : '', map : true, save : true, design : '', scheme : 'auto', switch : false, accent : '',
			blocks : { picture : true, name : true, contact : true, links : true, times : true, legal : true },
			texts : { de_DE : { line : 'Zeile', text : '', hours : '' }, en_US : { line : 'Line', text : '', hours : '' } },
			links : [
				{ id : 'a1', url : 'https://a.test/1', labels : { de_DE : 'Eins', en_US : 'One' } },
				{ id : 'b2', url : 'https://a.test/2', labels : { de_DE : 'Zwei', en_US : 'Two' } },
				{ id : 'c3', url : 'https://a.test/3', labels : { de_DE : 'Drei', en_US : 'Three' } },
			] },
		locales : [ 'de_DE', 'en_US' ], native : 'de_DE', names : { de_DE : 'Deutsch', en_US : 'English (US)' },
		inherited : { name : 'Acme', phone : '', email : '', website : '', address : '', text : { de_DE : '', en_US : '' } },
		designs : { klar : 'data:image/webp;base64,a', karte : 'data:image/webp;base64,b', foto : 'data:image/webp;base64,c' },
		photo : { url : null, size : 480, alt : {} }, logo : { manage : true, slots : [ { uri : '/logo', url : '/public/images/logo.500x100.webp' }, { uri : '/logo/dark', url : null } ] },
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

const notices = function( w ) { return findAll( w.mount, function( el ) { return el.classList.contains('nino-admin-notice') } ) };
const byId = function( w, id ) { return findAll( w.mount, function( el ) { return el.id === id } )[0] ?? null };

/** The "?" that describes a control: its button, and the text it opens */
const helpOf = function( w, control ) {
	const text = byId( w, control.attributes['aria-describedby'] );
	return { text : text, button : text.parentNode.children[0] };
};

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
	check( 'the answer redraws only what says where the page is: the button is gone, the status says start page, the page that moved is told', w.button('Use as start page') === null && notices( w ).length === 0 && w.line().textContent === 'The vCard is the start page. The page “Startseite” moved from / to /home.' );
	check( '...and what was typed is still there, and unsaved', w.field('name').value === 'Grace' && w.entry().isDirty() === true );
	check( '...it says it to a screen reader too', w.live().textContent === w.line().textContent );
}

{
	const w = drawn( 'en_US', { page : { state : 'start', path : '/', takenUri : '', file : '/vcard.vcf', start : null } } );
	check( 'where the card is on / there is no button', w.button('Use as start page') === null && notices( w ).length === 0 );
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



console.log( '\nNino.admin.vcard - the "?"\n' );

{
	const w = drawn( 'en_US' );
	const helps = findAll( w.mount, function( el ) { return el.classList.contains('vcard-help') } );

	check( 'no hint paragraph is left on the screen', findAll( w.mount, function( el ) { return el.classList.contains('nino-admin-hint') } ).length === 0 && helps.length >= 14 );
	check( 'every "?" is a button that is no submit, is named by what it explains, says it is closed and points at a text that is hidden', helps.every( function( help ) {
		const button = help.children[0];
		const body = help.children[1];
		return button.tagName === 'BUTTON' && button.type === 'button' && /^Help: \S/.test( button.attributes['aria-label'] ) && button.attributes['aria-expanded'] === 'false' && button.attributes['aria-describedby'] === body.id && body.hidden === true && body.textContent !== '';
	} ) );
	check( '...and no two of them have the same name', new Set( helps.map( function( help ) { return help.children[0].attributes['aria-label'] } ) ).size === helps.length );

	const name = helpOf( w, w.field('name') );
	check( 'the control it belongs to is described by the text, and named by its words alone', w.field('name').attributes['aria-describedby'] === name.text.id && byId( w, w.field('name').attributes['aria-labelledby'] ).textContent === 'Name' && name.button.attributes['aria-label'] === 'Help: Name' );

	const wrap = name.text.parentNode;
	check( 'the pointer is heard on the span that holds the button and its text, so that moving from the one to the other keeps the text open', wrap.listeners.mouseenter.length === 1 && wrap.listeners.mouseleave.length === 1 && ( name.button.listeners.mouseleave || [] ).length === 0 && ( name.text.listeners.mouseleave || [] ).length === 0 );

	fire( wrap, 'mouseenter' );
	check( 'the pointer over it shows the text, and leaving hides it', name.text.hidden === false && name.button.attributes['aria-expanded'] === 'true' );
	w.pressed( 'keydown', { key : 'Escape' } );
	check( 'Escape closes it with the pointer over it and no focus on it', name.text.hidden === true && name.button.attributes['aria-expanded'] === 'false' );
	fire( wrap, 'mouseenter' );
	fire( wrap, 'mouseleave' );
	check( '...', name.text.hidden === true );
	fire( name.button, 'focus' );
	check( 'the focus shows it and leaving the control hides it', name.text.hidden === false );
	fire( wrap, 'mouseenter' );
	fire( name.button, 'blur' );
	check( 'it stays while the pointer is over it, though the focus has gone', name.text.hidden === false );
	fire( wrap, 'mouseleave' );
	check( '...and goes with the pointer', name.text.hidden === true );

	fire( wrap, 'mouseenter' );
	fire( name.button, 'click' );
	fire( wrap, 'mouseleave' );
	check( 'a click - a tap on a touch screen - keeps it open when the pointer has gone', name.text.hidden === false && name.button.attributes['aria-expanded'] === 'true' );
	w.pressed( 'keydown', { key : 'Escape' } );
	check( 'Escape closes it', name.text.hidden === true );

	fire( name.button, 'click' );
	fire( name.button, 'click' );
	check( 'a second click closes it', name.text.hidden === true );

	const line = helpOf( w, findAll( w.mount, function( el ) { return el.attributes.role === 'group' && el.attributes['aria-describedby'] !== undefined } )[0] );
	fire( name.button, 'click' );
	fire( line.button, 'click' );
	check( 'opening another one closes the one that was kept open', name.text.hidden === true && line.text.hidden === false );
	w.pressed( 'pointerdown', { target : element('div') } );
	check( 'a press elsewhere closes it', line.text.hidden === true );
	fire( line.button, 'click' );
	w.pressed( 'pointerdown', { target : line.button } );
	check( '...but not a press on a "?"', line.text.hidden === false );
	w.pressed( 'keydown', { key : 'Escape' } );
	check( 'and Escape anywhere closes it', line.text.hidden === true );

	const switches = findAll( w.mount, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'map' } )[0];
	check( 'a switch is described by its text as a field is', byId( w, switches.attributes['aria-describedby'] ) !== null && byId( w, switches.attributes['aria-labelledby'] ).textContent !== '' );
}

{
	const start = drawn( 'en_US', { page : { state : 'start', path : '/', takenUri : '', file : '/vcard.vcf', start : null } } );
	const taken = drawn( 'en_US', { page : { state : 'page', path : '/vcard', takenUri : '/home', file : '/vcard.vcf', start : null } } );
	const where = function( w ) { return findAll( w.mount, function( el ) { return el.classList.contains('vcard-where') } )[0] };
	const aboutOff = function( w ) { return findAll( where( w ), function( el ) { return el.classList.contains('vcard-help-text') } ) };

	check( 'where the card is the start page a "?" at the link to the page says what to do before the feature is switched off, and nothing else is said there', aboutOff( start ).length === 1 && aboutOff( start )[0].textContent.indexOf('Routes') !== -1 && notices( start ).length === 0 && findAll( where( start ), function( el ) { return el.tagName === 'A' } )[0].parentNode === where( start ) );
	check( 'where it is not, there is none, but a notice with the "?" that says why the card has an address of its own', aboutOff( taken ).length === 1 && aboutOff( taken )[0].textContent.indexOf('/home') !== -1 && notices( taken ).length === 1 );
}

console.log( '\nNino.admin.vcard - the blocks\n' );

{
	const w = drawn( 'en_US' );
	const sets = findAll( w.mount, function( el ) { return el.tagName === 'FIELDSET' } );
	const switchOf = function( set ) { return findAll( set, function( el ) { return el.tagName === 'INPUT' && el.type === 'checkbox' && ( el.dataset.key || '' ).indexOf('block.') === 0 } )[0] };
	const block = function( name ) { return sets.filter( function( set ) { return set.dataset.block === name } )[0] };
	const turned = function( set ) { return findAll( set, function( el ) { return [ 'INPUT', 'SELECT', 'TEXTAREA', 'BUTTON' ].indexOf( el.tagName ) !== -1 && el.parentNode.tagName !== 'LEGEND' && el.classList.contains('vcard-help-button') === false && el.dataset.key !== undefined && el.dataset.key.indexOf('block.') === 0 ? false : [ 'INPUT', 'SELECT', 'TEXTAREA', 'BUTTON' ].indexOf( el.tagName ) !== -1 && el.classList.contains('vcard-help-button') === false && findAll( set.children[0], function( inner ) { return inner === el } ).length === 0 } ) };

	check( 'six groups have a switch in their legend, one for each block of the card, on', [ 'picture', 'name', 'contact', 'links', 'times', 'legal' ].every( function( name ) { const input = switchOf( block( name ) ); return input !== undefined && input.checked === true && block( name ).classList.contains('is-off') === false && input.attributes['aria-label'] !== undefined } ) );
	const plain = sets.filter( function( set ) { return set.dataset.block === undefined } );
	check( 'the logo, the design and the rest have none: the logo is the website\'s and not a block of the card, the design is the look of the whole card, and what the rest holds is a switch itself', sets.length === 9 && plain.length === 3 && plain.every( function( set ) { return switchOf( set ) === undefined } ) );
	check( 'the legend of a block that has something to explain carries a "?" - the name, the ways to reach, the links, the imprint - and a plain one has none', [ 'name', 'contact', 'links', 'legal' ].every( function( name ) { return findAll( block( name ).children[0], function( el ) { return el.classList.contains('vcard-help') } ).length === 1 } ) && [ 'times' ].every( function( name ) { return findAll( block( name ).children[0], function( el ) { return el.classList.contains('vcard-help') } ).length === 0 } ) );
	check( 'the switch is in the legend', block('contact').children[0].tagName === 'LEGEND' && block('contact').children[0].querySelectorAll('input').length === 1 );

	const input = switchOf( block('contact') );
	input.checked = false;
	fire( input, 'change' );
	const inside = turned( block('contact') );
	check( 'off: every control of the block is disabled and the block is marked for the dimming', inside.length >= 5 && inside.every( function( control ) { return control.disabled === true } ) && block('contact').classList.contains('is-off') === true );
	const helpsIn = findAll( block('contact'), function( el ) { return el.classList.contains('vcard-help-button') } );
	check( '...but not the "?" that explains it, nor the switch: what is off can still be read about', helpsIn.length >= 2 && helpsIn.every( function( button ) { return button.disabled !== true } ) && input.disabled !== true );
	check( '...and that is something unsaved', w.entry().isDirty() === true );
	w.entry().save( function() {} );
	const sent = w.requests[w.requests.length - 1].payload;
	check( 'what a block holds is sent whatever the block says, and the switches with it', sent.blocks.contact === false && sent.blocks.name === true && sent.blocks.legal === true && sent.phone === '' && Object.keys( sent.blocks ).length === 6 );
	input.checked = true;
	fire( input, 'change' );
	check( 'on again: enabled, not dimmed, nothing unsaved', inside.every( function( control ) { return control.disabled === false } ) && block('contact').classList.contains('is-off') === false && w.entry().isDirty() === false );

	const links = switchOf( block('links') );
	const upFirst = w.buttons('Move the link up')[0];
	links.checked = false;
	fire( links, 'change' );
	check( 'a control that was disabled by itself - the first link cannot move up - is disabled when the block is back on, as it was', upFirst.disabled === true && ( links.checked = true ) && ( fire( links, 'change' ), upFirst.disabled === true ) && w.buttons('Move the link down')[0].disabled === false );
}

{
	const off = drawn( 'en_US', { data : Object.assign( screen().data, { blocks : { picture : true, name : false, contact : true, links : true, times : true, legal : true } } ) } );
	const set = findAll( off.mount, function( el ) { return el.tagName === 'FIELDSET' && el.dataset.block === 'name' } )[0];
	check( 'a block that is stored off is drawn off, and the entry holds nothing unsaved for it', set.classList.contains('is-off') === true && off.field('name').disabled === true && off.entry().isDirty() === false );
	off.entry().save( function() {} );
	check( '...and is sent as off', off.requests[off.requests.length - 1].payload.blocks.name === false );

	const none = drawn( 'en_US', { legal : { active : false, imprint : '', privacy : '' }, data : Object.assign( screen().data, { blocks : { picture : true, name : true, contact : true, links : true, times : true, legal : false } } ) } );
	none.entry().save( function() {} );
	check( 'without the Legal module there is no switch for it, and what the block holds is sent as it is', findAll( none.mount, function( el ) { return el.tagName === 'FIELDSET' && el.dataset.block === 'legal' } ).length === 0 && none.requests[none.requests.length - 1].payload.blocks.legal === false );
}

{
	const w = drawn( 'en_US' );
	const photo = findAll( w.mount, function( el ) { return el.classList.contains('vcard-photo') } )[0];

	check( 'the photo and what is said of it show where the choice can show a photo', photo.hidden === false );
	w.field('picture').value = 'logo';
	fire( w.field('picture'), 'change' );
	check( '...and go where it cannot: with only the logo, or with none', photo.hidden === true );
	w.field('picture').value = 'none';
	fire( w.field('picture'), 'change' );
	check( '...', photo.hidden === true );
	w.field('picture').value = 'photo';
	fire( w.field('picture'), 'change' );
	check( '...and are back with only the photo', photo.hidden === false );
	check( 'a card that is stored as showing the logo draws no photo part', drawn( 'en_US', { data : Object.assign( screen().data, { picture : 'logo' } ) } ).mount !== null && findAll( drawn( 'en_US', { data : Object.assign( screen().data, { picture : 'logo' } ) } ).mount, function( el ) { return el.classList.contains('vcard-photo') } )[0].hidden === true );
}

console.log( '\nNino.admin.vcard - the logo\n' );

{
	const w = drawn( 'en_US', { page : screen().page } );
	const sets = findAll( w.mount, function( el ) { return el.tagName === 'FIELDSET' } );
	const group = sets.filter( function( set ) { return findAll( set, function( el ) { return el.dataset.slot !== undefined } ).length > 0 } )[0];
	const rows = findAll( w.mount, function( el ) { return el.dataset.slot !== undefined } );
	const imgOf = function( row ) { return findAll( row, function( el ) { return el.tagName === 'IMG' } )[0] };

	check( 'the logo is a group of its own, not part of the block of the picture, with a sentence that is not a "?" and no switch', group !== undefined && group.dataset.block === undefined && findAll( group, function( el ) { return el.classList.contains('vcard-note') } ).length === 1 && findAll( group.children[0], function( el ) { return el.classList.contains('vcard-help') } ).length === 0 );
	check( 'both pictures of the logo are drawn, the one there as a picture and the one missing as nothing', rows.length === 2 && imgOf( rows[0] ).src === '/public/images/logo.500x100.webp' && imgOf( rows[1] ) === undefined );

	const picture = sets.filter( function( set ) { return set.dataset.block === 'picture' } )[0];
	const switchOf = findAll( picture, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'block.picture' } )[0];
	switchOf.checked = false;
	fire( switchOf, 'change' );
	check( 'with the block of the picture off the logo can still be changed: its buttons are not disabled', findAll( group, function( el ) { return el.tagName === 'BUTTON' } ).every( function( button ) { return button.disabled !== true } ) );

	const file = findAll( rows[1], function( el ) { return el.tagName === 'INPUT' && el.type === 'file' } )[0];
	file.files = [ { name : 'l.png' } ];
	fire( file, 'change' );
	const sent = w.requests[w.requests.length - 1];
	check( 'a chosen file goes to the kernel\'s own action of the Images panel, with the slot and the file', sent.action === 'images/upload' && sent.payload.uri === '/logo/dark' && sent.extra.file.name === 'l.png' );
	w.answer( 200, { filename : 'logo/dark.500x100.webp', url : '/public/images/logo/dark.500x100.webp' } );
	const after = findAll( w.mount, function( el ) { return el.dataset.slot === '/logo/dark' } )[0];
	check( 'the answer draws the new picture, and nothing of the card was saved with it', imgOf( after ).src.indexOf( '/public/images/logo/dark.500x100.webp' ) === 0 && w.requests.filter( function( request ) { return request.action === 'vcard/save' } ).length === 0 );
	check( 'the picture carries a stamp, the address the panel keeps does not: a replacement has the name of what it replaced, which the browser has in its cache', /[?&]t=\d+$/.test( imgOf( after ).src ) && w.touched.join() === 'slots' );
	check( '...and the Images panel is told, as it tells itself', w.touched.length === 1 );
	check( 'a picture that is large enough is said saved, with no warning', findAll( w.mount, function( el ) { return el.classList.contains('vcard-logo-msg') } )[0].classList.contains('is-warning') === false );

	const small = findAll( rows[0], function( el ) { return el.type === 'file' } )[0];
	small.files = [ { name : 's.png' } ];
	fire( small, 'change' );
	w.answer( 200, { filename : 'logo.500x100.webp', url : '/public/images/logo.500x100.webp', belowTarget : true, source : { width : 120, height : 30 } } );
	const message = findAll( w.mount, function( el ) { return el.classList.contains('vcard-logo-msg') } )[0];
	check( 'a picture below the size of the slot is saved and says so, with its size, in words and as a warning', message.classList.contains('is-warning') === true && message.textContent.indexOf('120 × 30 px') !== -1 && message.attributes.role === 'status' );

	fire( w.button('Remove the logo (For a light ground)'), 'click' );
	check( 'removing asks first, as a thing that cannot be taken back', w.asked.length === 1 && w.asked[0].level === 'destructive' );
	w.asked[0].options.onChoose( false );
	check( '...a no asks the server nothing', w.requests[w.requests.length - 1].action === 'images/upload' );
	w.asked[0].options.onChoose( true );
	check( '...a yes posts the kernel\'s images/remove for that slot', w.requests[w.requests.length - 1].action === 'images/remove' && w.requests[w.requests.length - 1].payload.uri === '/logo' );
	w.answer( 200, { filename : null } );
	check( '...and the picture is gone from the screen, and the Images panel is told', imgOf( findAll( w.mount, function( el ) { return el.dataset.slot === '/logo' } )[0] ) === undefined && w.touched.length === 3 );
}

{
	const w = drawn( 'en_US', { logo : { manage : false, slots : [ { uri : '/logo', url : '/public/images/logo.500x100.webp' }, { uri : '/logo/dark', url : null } ] } } );
	const rows = findAll( w.mount, function( el ) { return el.dataset.slot !== undefined } );
	check( 'an account that may not manage images sees the logo and no control to change it', rows.length === 2 && findAll( rows[0], function( el ) { return el.tagName === 'IMG' } ).length === 1 && rows.every( function( row ) { return findAll( row, function( el ) { return el.tagName === 'BUTTON' || el.type === 'file' } ).length === 0 } ) );

	const none = drawn( 'en_US', { logo : { manage : true, slots : [] } } );
	check( 'a project without logo slots has no logo group', findAll( none.mount, function( el ) { return el.dataset.slot !== undefined || el.classList.contains('vcard-logo') } ).length === 0 );
}

console.log( '\nNino.admin.vcard - the design\n' );

{
	const choices = function( w, name ) { return findAll( w.mount, function( el ) { return el.tagName === 'INPUT' && el.type === 'radio' && el.attributes.name === 'vcard-'+ name } ) };
	const on = function( w, name ) { return choices( w, name ).filter( function( input ) { return input.checked === true } ).map( function( input ) { return input.value } ) };
	const choose = function( w, name, value ) {
		choices( w, name ).forEach( function( input ) { input.checked = input.value === value } );
		fire( choices( w, name ).filter( function( input ) { return input.value === value } )[0], 'change' );
	};
	const colour = function( w ) { return findAll( w.mount, function( el ) { return el.tagName === 'INPUT' && el.type === 'color' } )[0] };
	const holder = function( w ) { return findAll( w.mount, function( el ) { return el.tagName === 'INPUT' && el.dataset.key === 'switch' } )[0] };
	const sent = function( w ) {
		w.entry().save( function() {} );
		return w.requests[w.requests.length - 1].payload;
	};
	const dependent = function( w ) { return choices( w, 'scheme' ).concat( choices( w, 'accent' ), [ holder( w ), colour( w ) ] ) };

	{
		const w = drawn( 'en_US' );
		const group = findAll( w.mount, function( el ) { return el.tagName === 'FIELDSET' && el.dataset.block === undefined && findAll( el, function( inner ) { return inner === choices( w, 'design' )[0] } ).length > 0 } )[0];

		check( 'the design offers the card as the theme draws it and three designs, in this order, and none of them has been chosen', JSON.stringify( choices( w, 'design' ).map( function( input ) { return input.value } ) ) === JSON.stringify( [ '', 'klar', 'karte', 'foto' ] ) && JSON.stringify( on( w, 'design' ) ) === '[""]' );
		check( 'each of the three has the picture the server sent, the card as the theme draws it has none', findAll( group, function( el ) { return el.tagName === 'IMG' } ).map( function( img ) { return img.src } ).join() === 'data:image/webp;base64,a,data:image/webp;base64,b,data:image/webp;base64,c' );
		check( 'the section has the one "?" and no note, hint or notice of its own', findAll( group, function( el ) { return el.classList.contains('vcard-help-button') } ).length === 1 && findAll( group, function( el ) { return el.classList.contains('vcard-note') || el.classList.contains('nino-admin-hint') || el.classList.contains('nino-admin-notice') } ).length === 0 );
		check( 'the order is the designs, then light, dark or auto, then the switch for the visitor, then the accent', ( function() {
			const at = function( node ) { return findAll( group, function() { return true } ).indexOf( node ) };
			return at( choices( w, 'design' )[0] ) < at( choices( w, 'scheme' )[0] ) && at( choices( w, 'scheme' )[0] ) < at( holder( w ) ) && at( holder( w ) ) < at( choices( w, 'accent' )[0] ) && JSON.stringify( choices( w, 'scheme' ).map( function( input ) { return input.value } ) ) === JSON.stringify( [ 'light', 'dark', 'auto' ] );
		}() ) );
		check( 'without a design the scheme, the switch and the accent are turned off', dependent( w ).every( function( control ) { return control.disabled === true } ) );
		check( 'a save without any change sends the defaults: no design, auto, no switch, the colour of the project', ( function() { const payload = sent( w ); return payload.design === '' && payload.scheme === 'auto' && payload.switch === false && payload.accent === '' } )() );

		choose( w, 'design', 'karte' );
		check( 'a design turns them on', dependent( w ).every( function( control ) { return control.disabled === false } ) );
		choose( w, 'scheme', 'dark' );
		holder( w ).checked = true;
		colour( w ).value = '#b0401c';
		fire( colour( w ), 'input' );
		check( 'a colour typed into the field makes the own colour the choice', JSON.stringify( on( w, 'accent' ) ) === '["own"]' );
		check( 'the save sends what was chosen', ( function() { const payload = sent( w ); return payload.design === 'karte' && payload.scheme === 'dark' && payload.switch === true && payload.accent === '#b0401c' } )() );
		check( 'the entry follows the choices', w.entry().isDirty() === true );
		choose( w, 'accent', 'project' );
		check( 'the colour of the project sends no colour', sent( w ).accent === '' );
		choose( w, 'design', '' );
		check( 'no design again: the three are off and keep what they held', dependent( w ).every( function( control ) { return control.disabled === true } ) && sent( w ).scheme === 'dark' );
		check( 'every word the section asked for is in the text files', w.missing.length === 0 );
	}

	{
		const w = drawn( 'en_US' );
		const answer = screen();
		delete answer.designs;
		w.entry().save( function() {} );
		w.answer( 200, answer );
		check( 'the answer of a save has no pictures of the designs and the screen keeps those it had', findAll( w.mount, function( el ) { return el.tagName === 'IMG' && el.classList.contains('vcard-design-picture') } ).length === 3 );
	}

	{
		const w = drawn( 'de_DE' );
		const answer = screen();
		answer.warning = 'vcard_words';
		w.entry().save( function() {} );
		w.answer( 200, answer );
		check( 'a save that could not add the words says so in the line, in the language of the panel, with the card saved', w.line().textContent.indexOf( 'gespeichert' ) !== -1 && w.line().textContent.indexOf( 'Textdateien' ) !== -1 && w.missing.length === 0 && w.entry().isDirty() === false );
	}

	{
		const stored = screen();
		stored.data.design = 'foto';
		stored.data.scheme = 'light';
		stored.data.switch = true;
		stored.data.accent = '#112233';
		const w = drawn( 'de_DE', stored );

		check( 'stored values are what is drawn: the design, the scheme, the switch, the own colour in its field', JSON.stringify( on( w, 'design' ) ) === '["foto"]' && JSON.stringify( on( w, 'scheme' ) ) === '["light"]' && holder( w ).checked === true && JSON.stringify( on( w, 'accent' ) ) === '["own"]' && colour( w ).value === '#112233' && dependent( w ).every( function( control ) { return control.disabled === false } ) );
		check( 'and an untouched form sends them back as they are', ( function() { const payload = sent( w ); return payload.design === 'foto' && payload.scheme === 'light' && payload.switch === true && payload.accent === '#112233' } )() && w.entry().isDirty() === false );
	}
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
