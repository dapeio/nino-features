/**
 *	Nino features
 *	files-js-smoke.js	What the script of the site, files.js, does over the stand-in of a page and of Nino: the cut of a size into chunks and the
 *											progress, which are pure, and the form it takes over - the rows of its list, the requests it makes, the word of each
 *											refusal, the halving of the chunk on a 413 and what is kept of it, the files the person chose before anything is sent,
 *											the reload when every file is in. Only Nino itself is a stand-in: the requests are kept for the test to answer, and the
 *											words are the ones of the text files of the install unit.
 *
 *											The panel has a suite of its own, files-admin-js-smoke.js.
 *
 *	Usage: node features/Files/tests/files-js-smoke.js
 *	       NINO_ROOT=../nino node features/Files/tests/files-js-smoke.js
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

/** A file the browser hands a script: a name, a size and a slice of it */
function stood( name, size ) {
	return { name : name, size : size, slice : function( from, to ) { return { from : from, to : to, size : to - from } } };
}

// --- The site's script ----------------------------------------------------------

console.log( '\nNino.files - the script of the form on the site\n' );

/**
 *	files.js in a page of its own: a form as [files-upload] writes it, over a Nino that keeps the requests and the
 *	words of the unit's text file
 *
 *	@param		{Object}	[options]		{ chunk, max, multiple, supported }
 *
 *	@return		{Object}
 */
function site( options ) {

	options = Object.assign( { chunk : '1048576', max : '5242880', multiple : '1', supported : true, without : [], words : {}, storage : undefined, chosen : false, transfer : false }, options );

	const words = Object.assign( fills( own( 'install/text/en_US.php' ) ), options.words );
	options.without.forEach( function( key ) { delete words[key] } );
	const requests = [];
	const ready = [];
	const assigned = [];
	const missing = [];

	const form = element('form');
	form.className = 'files-upload';
	form.setAttribute( 'action', '/club/.files/upload' );
	form.setAttribute( 'data-files-chunk', options.chunk );
	form.setAttribute( 'data-files-max', options.max );
	form.setAttribute( 'data-files-multiple', options.multiple );

	const make = function( tag, attributes ) {
		const el = element( tag );
		Object.keys( attributes ).forEach( function( name ) { el.setAttribute( name, attributes[name] ) } );
		form.appendChild( el );
		return el;
	};

	const folder = make( 'input', { type : 'hidden', name : 'folder' } );
	const token = make( 'input', { type : 'hidden', name : '_csrf' } );
	const input = make( 'input', { type : 'file', name : 'chunk', 'aria-describedby' : 'base-hint', 'aria-invalid' : 'false' } );
	const button = make( 'button', { type : 'submit' } );
	const list = make( 'ul', {} );

	folder.value = 'aaaaaaaaaaaa';
	token.value = 'the-token';
	list.className = 'files-upload-list';
	list.hidden = true;

	// The list of the chosen files and the sentence under it, as templates/files-upload.tpl has them; a form without them is the form of before
	const chosen = options.chosen === true ? make( 'ul', {} ) : null;
	const note = options.chosen === true ? make( 'p', {} ) : null;

	if( chosen !== null ) {
		chosen.className = 'files-upload-chosen';
		chosen.hidden = true;
		note.className = 'files-upload-chosen-hint';
		note.hidden = true;
		// A list that is emptied by assigning '' to its text, as the browser's is
		[ chosen, list ].forEach( function( el ) {
			Object.defineProperty( el, 'textContent', { get : function() { return '' }, set : function() { el.children.length = 0 } } );
		} );
		// An input that is emptied by its value, as the browser's is
		Object.defineProperty( input, 'value', { get : function() { return '' }, set : function() { input.files = [] } } );
	}

	const page = documentOf( form );
	page.querySelectorAll = function( selector ) { return selector === 'form.files-upload' ? [ form ] : [] };

	const Nino = {
		dir : '/club',
		content : { getText : function( key ) {
			if( words[key] === undefined )
				missing.push( key );
			return words[key] ?? '';
		} },
		events : { bindCallback : function( event, callback ) { ready.push( { event : event, callback : callback } ) } },
		http : { sendRequest : function( uri, method, callback, data ) { requests.push( { uri : uri, method : method, callback : callback, data : data, answered : false } ) } },
	};

	const window = {
		Nino : Nino,
		location : { href : 'https://example.org/club/files?x=1', assign : function( to ) { assigned.push( to ) } },
		FormData : options.supported === true ? function() {} : undefined,
		sessionStorage : options.storage,
		File : options.supported === true ? function() {} : undefined,
		// The stand-in of the object a script sets the files of an input by: the files that were added are the files of the list
		DataTransfer : options.transfer === true ? function() {
			const added = [];
			this.items = { add : function( file ) { added.push( file ) } };
			Object.defineProperty( this, 'files', { get : function() { return added.slice() } } );
		} : undefined,
	};

	const context = vm.createContext( { console : console, document : page, Nino : Nino, window : window, URL : URL } );
	load( own( 'assets/files.js' ), context, 'files.js' );

	return {
		Nino : Nino, form : form, input : input, button : button, list : list, chosen : chosen, note : note, requests : requests, ready : ready, assigned : assigned, missing : missing, window : window,
		word : function( key ) { return words[key] },
		rows : function() { return findAll( list, function( el ) { return el.tagName === 'LI' } ) },
		picks : function() { return chosen === null ? [] : findAll( chosen, function( el ) { return el.tagName === 'LI' } ) },
		choose : function( files ) { input.files = files; fire( input, 'change' ) },
		answer : function( status, body, headers ) {
			const open = requests.filter( function( request ) { return request.answered === false } );
			open[open.length - 1].answered = true;
			open[open.length - 1].callback( { status : status, responseJSON : body, getAllResponseHeaders : function() { return headers ?? 'content-type: application/json' } } );
		},
		submit : function() {
			const result = { prevented : false };
			fire( form, 'submit', { preventDefault : function() { result.prevented = true } } );
			return result;
		},
	};
}

{
	const s = site();
	const chunks = s.window.Nino.files.chunks;

	check( 'the cut of a size into chunks: the right count and the right bounds - every one but the last exactly a chunk long', JSON.stringify( chunks( 2500000, 1048576 ) ) === '[[0,1048576],[1048576,2097152],[2097152,2500000]]'
		&& chunks( 1048576, 1048576 ).length === 1 && chunks( 1048577, 1048576 ).length === 2 && chunks( 3, 1 ).length === 3 && JSON.stringify( chunks( 1, 4 ) ) === '[[0,1]]' );
	check( '...none for a size or a chunk that is no whole number above nothing', chunks( 0, 4 ).length === 0 && chunks( 4, 0 ).length === 0 && chunks( -4, 2 ).length === 0 && chunks( 4.5, 2 ).length === 0 && chunks( 4, '2' ).length === 0 );
	check( 'the progress is whole percent, held between 0 and 100', s.window.Nino.files.percent( 0, 10 ) === 0 && s.window.Nino.files.percent( 5, 10 ) === 50 && s.window.Nino.files.percent( 10, 10 ) === 100 && s.window.Nino.files.percent( 1, 3 ) === 33
		&& s.window.Nino.files.percent( 20, 10 ) === 100 && s.window.Nino.files.percent( 1, 0 ) === 0 );
}

{
	const s = site();

	check( 'the script waits for the page: it binds its init to the ready event of Nino and takes nothing over before', s.ready.length === 1 && s.ready[0].event === 'ready' && s.form.dataset.filesBound === undefined && s.input.multiple !== true );
	s.ready[0].callback();
	check( 'a form is taken over once, with the input asked to take several files because the form says so', s.form.dataset.filesBound === '1' && s.input.multiple === true && ( s.form.listeners.submit || [] ).length === 1 );
	s.window.Nino.files.init();
	check( '...and not twice', ( s.form.listeners.submit || [] ).length === 1 );

	const unsaid = site();
	unsaid.form.removeAttribute( 'data-files-multiple' );
	unsaid.ready[0].callback();
	check( 'a form that says nothing about several files gets an input that takes one', unsaid.input.multiple === false );

	const single = site( { multiple : '0' } );
	single.ready[0].callback();
	check( 'a form that says one file at a time gets an input that takes one', single.input.multiple === false );

	const bare = site( { supported : false } );
	bare.ready[0].callback();
	check( 'a browser without FormData and File leaves the form to the browser, which posts it whole', bare.form.dataset.filesBound === undefined && ( bare.form.listeners.submit || [] ).length === 0 );

	const noChunk = site( { chunk : '0' } );
	noChunk.ready[0].callback();
	check( 'a form that names no chunk is left alone as well', noChunk.form.dataset.filesBound === undefined );
}

{
	const s = site();
	s.ready[0].callback();

	const none = s.submit();
	check( 'a submit with no file chosen is left to the browser, which asks for one', none.prevented === false && s.requests.length === 0 );

	s.input.files = [ stood( 'One.mp3', 2500000 ), stood( 'Two.txt', 5 ) ];
	s.input.value = 'C:\\fakepath\\One.mp3';
	const taken = s.submit();
	const first = s.requests[0];

	check( 'a submit with files is taken over: the browser does not post, the list is shown, the button is off', taken.prevented === true && s.list.hidden === false && s.button.disabled === true );
	check( 'the first chunk goes to /.files/upload below the directory of the project, as a post, with the fields of the form and of the upload - and the bytes of the chunk', first.uri === '/club/.files/upload' && first.method === 'POST'
		&& JSON.stringify( Object.assign( {}, first.data, { chunk : undefined } ) ) === JSON.stringify( { folder : 'aaaaaaaaaaaa', name : 'One.mp3', size : 2500000, upload : '', index : 0, count : 3, chunk : undefined, _csrf : 'the-token' } ) && first.data.chunk.from === 0 && first.data.chunk.to === 1048576 );

	const second = s.submit();
	check( 'a second submit while it runs is stopped and sends nothing', second.prevented === true && s.requests.length === 1 );

	const spans = function( row ) { return findAll( row, function( el ) { return el.tagName === 'SPAN' } ).map( function( el ) { return el.textContent } ) };
	const bar = function( row ) { return Number( findAll( row, function( el ) { return el.tagName === 'PROGRESS' } )[0].value ) };

	check( 'every file has its row from the start: the file at its turn says it is on its way, the other waits - each is named as text, and their progress is at nothing', s.rows().length === 2 && s.rows()[0].dataset.state === 'sending' && s.rows()[1].dataset.state === 'waiting'
		&& spans( s.rows()[0] ).join('|') === 'One.mp3|'+ s.word('/feature/files/script/sending') && spans( s.rows()[1] ).join('|') === 'Two.txt|'+ s.word('/feature/files/script/waiting') && bar( s.rows()[0] ) === 0 && bar( s.rows()[1] ) === 0 );

	s.answer( 200, { upload : '0123456789abcdef', index : 0 } );
	check( 'the answer of a chunk brings the upload id, which every chunk after it names, and the next index', s.requests[1].data.upload === '0123456789abcdef' && s.requests[1].data.index === 1 && s.requests[1].data.chunk.from === 1048576
		&& Number( findAll( s.rows()[0], function( el ) { return el.tagName === 'PROGRESS' } )[0].value ) === 41 );
	s.answer( 200, { upload : '0123456789abcdef', index : 1 } );
	check( '...and the third is the rest of the file, as the script of the site sends it', s.requests[2].data.index === 2 && s.requests[2].data.chunk.to === 2500000 && s.requests[2].data.upload === '0123456789abcdef' );
	s.answer( 200, { id : 'ffffffffffff', name : 'One.mp3', size : 2500000 } );
	check( 'a file that is in is done, with the word for it, and the next one begins', s.rows()[0].dataset.state === 'done' && spans( s.rows()[0] )[1] === s.word('/feature/files/script/done') && s.rows()[1].dataset.state === 'sending' && s.requests[3].data.name === 'Two.txt' && s.requests[3].data.count === 1 );

	s.answer( 200, { id : 'eeeeeeeeeeee', name : 'Two.txt', size: 5 } );
	check( 'when every file is in the page is loaded again with ?files=sent, the other parts of its address kept - the form is given back, and the files that were chosen are forgotten', s.assigned.length === 1 && s.assigned[0] === '/club/files?x=1&files=sent' && s.button.disabled === false && s.input.value === '' );
}

{
	const s = site();
	s.ready[0].callback();
	s.input.files = [ stood( 'Big.mp3', 2000 ), stood( 'Next.mp3', 5 ) ];
	s.submit();
	s.answer( 400, { error : 'x', code : 'files_quota', params : [ 3 ] } );
	check( 'a refusal shows the word of its code in the row of the file - the unit\'s word, read through Nino.content - and the file is failed', s.rows()[0].dataset.state === 'failed' && findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === s.word('/feature/files/state/quota') );
	check( 'the field that takes files is invalid once a file is refused, and the sentence of the refusal - an element with an id, an alert - is what describes it, beside what described it before',
		s.input.getAttribute('aria-invalid') === 'true' && ( function() {
			const row = findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' && el.id !== '' } )[0];
			return row !== undefined && row.getAttribute('role') === 'alert' && s.input.getAttribute('aria-describedby').split(' ').indexOf( row.id ) !== -1 && s.input.getAttribute('aria-describedby').split(' ').indexOf('base-hint') !== -1;
		} )() );
	check( '...the next file is sent all the same, and the page is not loaded again when one of them failed', s.requests.length === 2 && s.requests[1].data.name === 'Next.mp3' );
	s.answer( 200, { id : 'eeeeeeeeeeee', name : 'Next.mp3', size : 5 } );
	check( '...and when all are over the form is given back', s.assigned.length === 0 && s.button.disabled === false && s.input.value === '' );
}

{
	const kinds = [ [ 403, '', 'content-type: x', 'script/expired' ], [ 500, '', '', 'script/offline' ], [ 500, { error : 'x' }, 'content-type: x', 'state/write' ], [ 400, { error : 'x', code : 'files_nonsense' }, 'content-type: x', 'state/write' ],
		[ 404, { error : 'x', code : 'files_folder' }, 'content-type: x', 'state/folder' ], [ 429, { error : 'x', code : 'files_limit' }, 'content-type: x', 'state/limit' ] ];
	const said = kinds.map( function( kind ) {
		const s = site();
		s.ready[0].callback();
		s.input.files = [ stood( 'A.mp3', 5 ) ];
		s.submit();
		s.answer( kind[0], kind[1], kind[2] );
		return findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === site().word( '/feature/files/'+ kind[3] );
	} );

	check( 'each way an answer can fail has a word: a post the csrf guard refused (a 403 with no body) says the session ran out, no answer at all that the connection failed, a failure with no code that the file could not be stored, a code the script does not know the same, and the folder and the limit their own',
		said.every( function( same ) { return same === true } ) );
}

{
	const s = site( { chosen : true, transfer : true } );
	s.ready[0].callback();
	s.choose( [ stood( 'Huge.mp3', 5242881 ) ] );
	s.submit();
	const flagged = s.input.getAttribute('aria-invalid') === 'true' && s.input.getAttribute('aria-describedby').split(' ').length === 2;
	s.choose( [ stood( 'Small.txt', 3 ) ] );
	check( 'a new choice takes the refusal off the field: it is valid again and described by what described it before', flagged === true && s.input.getAttribute('aria-invalid') === 'false' && s.input.getAttribute('aria-describedby') === 'base-hint' );
}

{
	const s = site();
	s.ready[0].callback();
	s.input.files = [ stood( 'Huge.mp3', 5242881 ), stood( 'Empty.mp3', 0 ) ];
	s.submit();

	check( 'a file over data-files-max, and an empty one, are refused before a byte is sent, each with the word of its refusal', s.requests.length === 0 && s.rows().length === 2 && s.rows().every( function( row ) { return row.dataset.state === 'failed' } )
		&& findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === s.word('/feature/files/state/size') && findAll( s.rows()[1], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === s.word('/feature/files/state/chunk') );
	check( '...and nothing is loaded again, the form is given back', s.assigned.length === 0 && s.button.disabled === false );
}

{
	const s = site();
	s.ready[0].callback();
	s.input.files = [ stood( '<img src=x onerror=alert(1)>.mp3', 5 ) ];
	s.submit();

	check( 'the name of a file is text in its row: markup in it is a string and no element', findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[0].textContent === '<img src=x onerror=alert(1)>.mp3' && findAll( s.list, function( el ) { return el.tagName === 'IMG' } ).length === 0 );
	check( 'every word the script asked for is a fill of the unit\'s text file', s.missing.length === 0 );
}

{
	// A web server that takes less than the chunk: its 413 makes the upload start again in halves
	const s = site();
	const halve = s.window.Nino.files.halve;
	const takes = 300000;
	const say = function( row ) { return findAll( row, function( el ) { return el.tagName === 'SPAN' } )[1].textContent };
	const shape = function( request ) { return request.data.index+ '/'+ request.data.count+ ':'+ ( request.data.chunk.to - request.data.chunk.from )+ ':'+ request.data.upload };
	const serve = function() {
		const request = s.requests[s.requests.length - 1];
		const size = request.data.chunk.to - request.data.chunk.from;
		if( size > takes )
			s.answer( 413, null );
		else if( request.data.index + 1 < request.data.count )
			s.answer( 200, { upload : 'abcdef0123456789', index : request.data.index } );
		else
			s.answer( 200, { id : 'ffffffffffff', name : request.data.name, size : request.data.size } );
	};

	check( 'the halving is pure: half of the chunk that was refused, a file shorter than the chunk halves its own length, and nothing below 128 KiB - 0 where the chunk is there already',
		halve( 2500000, 1048576 ) === 524288 && halve( 2500000, 262144 ) === 131072 && halve( 2500000, 131072 ) === 0 && halve( 200000, 1048576 ) === 131072 && halve( 100000, 1048576 ) === 0 && s.window.Nino.files.floor === 131072 );

	s.ready[0].callback();
	s.input.files = [ stood( 'Big.mp3', 2500000 ), stood( 'Next.mp3', 1500000 ) ];
	s.submit();
	s.answer( 200, { upload : 'abcdef0123456789', index : 0 } );
	const before = s.requests.length;
	serve();
	check( 'a chunk the web server answers 413 starts the file again from its first chunk with no upload id, in chunks of half the size - and the row says the parts were made smaller',
		s.requests.length === before + 1 && shape( s.requests[s.requests.length - 1] ) === '0/5:524288:' && s.rows()[0].dataset.state === 'sending' && say( s.rows()[0] ) === s.word('/feature/files/script/smaller')
		&& s.word('/feature/files/script/smaller') !== s.word('/feature/files/script/sending') && Number( findAll( s.rows()[0], function( el ) { return el.tagName === 'PROGRESS' } )[0].value ) === 0 );
	serve();
	check( '...refused again, half once more, from the first chunk again', shape( s.requests[s.requests.length - 1] ) === '0/10:262144:' && s.requests.filter( function( request ) { return request.data.index === 0 } ).length === 3 );

	while( s.requests[s.requests.length - 1].data.name === 'Big.mp3' )
		serve();

	check( '...the file goes on with the upload id of the new upload and is done; the next file of the form begins at the size that worked, not at the setting', s.rows()[0].dataset.state === 'done' && say( s.rows()[0] ) === s.word('/feature/files/script/done')
		&& shape( s.requests[s.requests.length - 1] ) === '0/6:262144:' );

	while( s.assigned.length === 0 )
		serve();

	check( '...and when every file is in the page is loaded again as before', s.rows().every( function( row ) { return row.dataset.state === 'done' } ) && s.assigned.length === 1 );

	s.input.files = [ stood( 'Third.mp3', 2500000 ) ];
	s.submit();
	check( '...a later submit of the same page starts at the working size too', shape( s.requests[s.requests.length - 1] ) === '0/10:262144:' );
	s.answer( 200, { upload : 'abcdef0123456789', index : 0 } );
	const sent = s.requests.length;
	s.answer( 500, null, '' );
	check( 'a connection that dropped (no headers at all) is not a 413: the file fails as it did, with the word of the connection, and nothing is sent again', s.requests.length === sent && s.rows()[2].dataset.state === 'failed' && say( s.rows()[2] ) === s.word('/feature/files/script/offline') );
}

{
	// At the floor a 413 is a failure that names the cause
	const s = site();
	const sizes = [];

	s.ready[0].callback();
	s.input.files = [ stood( 'Never.mp3', 2500000 ), stood( 'After.txt', 10 ) ];
	s.submit();

	while( s.requests[s.requests.length - 1].data.name === 'Never.mp3' && sizes.length < 20 ) {
		const request = s.requests[s.requests.length - 1];
		sizes.push( request.data.chunk.to - request.data.chunk.from );
		s.answer( 413, null );
	}

	check( 'a server that refuses every size is tried at the setting and at each half down to 128 KiB, and no further', sizes.join() === '1048576,524288,262144,131072' );
	check( '...the file then fails with the sentence of the floor, the next file goes on and the page is not loaded again',
		s.rows()[0].dataset.state === 'failed' && findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === s.word('/feature/files/script/floor') && s.word('/feature/files/script/floor') !== s.word('/feature/files/state/write')
		&& s.requests[s.requests.length - 1].data.name === 'After.txt' );

	s.answer( 200, { id : 'ffffffffffff', name : 'After.txt', size : 10 } );
	check( '...and a small file of 10 bytes follows at the floor without a word of halving', s.rows()[1].dataset.state === 'done' && s.assigned.length === 0 && s.missing.length === 0 );
}

{
	// A 413 that carries a code the script has a word for says that word: the cause is named
	const s = site();
	s.ready[0].callback();
	s.input.files = [ stood( 'Tiny.txt', 10 ) ];
	s.submit();
	s.answer( 413, { code : 'files_size' } );
	check( 'a 413 at the floor that names a code the script knows says the word of the code, not the sentence of the web server', findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent === s.word('/feature/files/state/size') );
}

{
	// The working size is kept over the reload that ends a submit, in the storage of the tab, by the action of the form
	const store = { data : {}, getItem : function( key ) { return Object.prototype.hasOwnProperty.call( this.data, key ) ? this.data[key] : null }, setItem : function( key, value ) { this.data[key] = String( value ) } };
	const takes = 300000;
	const run = function( s ) {
		s.ready[0].callback();
		s.input.files = [ stood( 'Kept.mp3', 2500000 ) ];
		s.submit();
		while( s.assigned.length === 0 && s.rows()[s.rows().length - 1].dataset.state !== 'failed' ) {
			const request = s.requests[s.requests.length - 1];
			if( request.data.chunk.to - request.data.chunk.from > takes )
				s.answer( 413, null );
			else
				s.answer( 200, request.data.index + 1 < request.data.count ? { upload : 'abcdef0123456789', index : request.data.index } : { id : 'ffffffffffff', name : 'Kept.mp3', size : 2500000 } );
		}
		return s;
	};
	const first = run( site( { storage : store } ) );
	check( 'the size that worked is written to the storage of the tab, once, under a key of its own', Object.keys( store.data ).length === 1 && store.data[Object.keys( store.data )[0]] === '262144' && first.assigned.length === 1 );

	const second = site( { storage : store } );
	second.ready[0].callback();
	second.input.files = [ stood( 'Again.mp3', 2500000 ) ];
	second.submit();
	check( '...and the next submit, on the page the reload loaded, starts at that size - no request is wasted on the setting', second.requests.length === 1 && second.requests[0].data.count === 10 && second.requests[0].data.chunk.to - second.requests[0].data.chunk.from === 262144 );

	const odd = [ 'abc', '10', '-5', '', '99999999999' ].map( function( value ) {
		const bad = { getItem : function() { return value }, setItem : function() {} };
		const s = site( { storage : bad } );
		s.ready[0].callback();
		s.input.files = [ stood( 'Odd.mp3', 2500000 ) ];
		s.submit();
		return s.requests[0].data.chunk.to - s.requests[0].data.chunk.from;
	} );
	check( '...a value that is no chunk (words, below the floor, negative, empty) is not taken: the submit starts at the setting; one above the setting is held to it', odd.slice( 0, 4 ).every( function( size ) { return size === 1048576 } ) && odd[4] === 1048576 );

	const none = [ undefined, { getItem : function() { throw new Error('blocked') }, setItem : function() { throw new Error('blocked') } } ].map( function( broken ) {
		const s = run( site( { storage : broken } ) );
		return s.assigned.length === 1 && s.rows()[0].dataset.state === 'done';
	} );
	check( '...storage that is missing or throws is harmless: the upload halves, goes through and the page is loaded again', none.every( function( ok ) { return ok === true } ) );
}

{
	// A refusal that is not a 413 is not a sign of a web server limit: nothing is halved
	[ [ 429, { code : 'files_limit' }, 'limit' ], [ 400, { code : 'files_chunk' }, 'chunk' ], [ 404, { code : 'files_folder' }, 'folder' ] ].forEach( function( each ) {
		const s = site();
		s.ready[0].callback();
		s.input.files = [ stood( 'Plain.mp3', 2500000 ) ];
		s.submit();
		s.answer( each[0], each[1] );
		const said = findAll( s.rows()[0], function( el ) { return el.tagName === 'SPAN' } )[1].textContent;
		check( 'a '+ each[0]+ ' is not halved: one request, the file fails with the word of its code, no word about smaller parts', s.requests.length === 1 && s.rows()[0].dataset.state === 'failed' && said === s.word('/feature/files/state/'+ each[2]) && said !== s.word('/feature/files/script/smaller') );
	} );
}

chosenChecks();

done();

// --- The files before they are sent ---------------------------------------------

/**
 *	The list of the files the person chose, before anything is sent: what it shows, how a file is taken out of the selection, and that nothing goes to the server for it
 *
 *	@return		{void}
 */
function chosenChecks() {

	console.log( '\nThe chosen files, before the submit\n' );

	const names = function( s ) { return s.picks().map( function( pick ) { return findAll( pick, function( el ) { return el.tagName === 'SPAN' } )[0].textContent } ) };
	const sizes = function( s ) { return s.picks().map( function( pick ) { return findAll( pick, function( el ) { return el.tagName === 'SPAN' } )[1].textContent } ) };
	const removers = function( s ) { return s.picks().map( function( pick ) { return findAll( pick, function( el ) { return el.tagName === 'BUTTON' } )[0] } ) };
	const sent = function( s ) { return s.requests.map( function( request ) { return request.data.name } ) };

	{
		const s = site( { chosen : true, transfer : true } );
		const readable = s.window.Nino.files.readable;

		check( 'the size is read as a person reads it: bytes below a KB, then KB and MB with one decimal at most - and nothing for what is no size', readable( 0 ) === '0 B' && readable( 1023 ) === '1023 B' && readable( 1024 ) === '1 KB' && readable( 1048575 ) === '1024 KB' && readable( 1048576 ) === '1 MB' && readable( 1536 ) === '1.5 KB' && readable( 1048576 ) === '1 MB' && readable( 5242880 ) === '5 MB'
			&& readable( 1572864 ) === '1.5 MB' && readable( -1 ) === '' && readable( NaN ) === '' && readable( Infinity ) === '' );

		s.ready[0].callback();
		check( 'before a file is chosen the list and the sentence under it are hidden, and the progress list is hidden as before', s.chosen.hidden === true && s.note.hidden === true && s.list.hidden === true && s.picks().length === 0 );

		s.choose( [ stood( 'Song.mp3', 3145728 ), stood( '<img src=x onerror=alert(1)>.wav', 900 ), stood( 'Notes.txt', 1536 ) ] );
		check( 'choosing files lists each one - its name, its size and a button to take it out - and shows the sentence under the list', s.chosen.hidden === false && s.note.hidden === false && names( s ).join( '|' ) === 'Song.mp3|<img src=x onerror=alert(1)>.wav|Notes.txt'
			&& sizes( s ).join( '|' ) === '3 MB|900 B|1.5 KB' && removers( s ).length === 3 && removers( s ).every( function( button ) { return button.type === 'button' } ) );
		check( '...a name is text in its row: markup in it is a string and no element, and nothing is asked of the server for a list', findAll( s.chosen, function( el ) { return el.tagName === 'IMG' } ).length === 0 && s.requests.length === 0 && s.list.hidden === true );
		check( '...the sentence under the list is the word of the script, set as text', s.note.textContent === s.word( '/feature/files/script/chosen' ) && s.word( '/feature/files/script/chosen' ) !== '' );
		check( '...the button says what it does with the word of the unit, with the name of the file for a reader of the screen, and has an icon that is hidden from that reader', removers( s ).every( function( button, at ) { return button.title === s.word( '/feature/files/script/remove' ) && button.getAttribute( 'aria-label' ) === s.word( '/feature/files/script/remove' )+ ': '+ names( s )[at]
			&& findAll( button, function( el ) { return el.className === 'files-upload-icon' && el.getAttribute( 'aria-hidden' ) === 'true' } ).length === 1 } ) && s.word( '/feature/files/script/remove' ) !== '' );

		fire( removers( s )[1], 'click' );
		check( 'the button takes the file out of the selection: the list shows the rest, and the input holds the rest - the file is in no request', names( s ).join( '|' ) === 'Song.mp3|Notes.txt' && s.input.files.map( function( file ) { return file.name } ).join( '|' ) === 'Song.mp3|Notes.txt' && s.requests.length === 0 );
		check( '...the focus goes to the cross that took the place of the one pressed, so that a keyboard goes on where it was', removers( s )[1].focused === true );
		s.submit();
		check( '...the submit sends the files that are left and none other', sent( s ).join() === 'Song.mp3' && s.rows().length === 2 && s.rows().map( function( row ) { return findAll( row, function( el ) { return el.tagName === 'SPAN' } )[0].textContent } ).join( '|' ) === 'Song.mp3|Notes.txt' );
		check( '...and the progress takes the place of the chosen files: the list and the sentence are hidden, the progress list shows its rows with the bar', s.chosen.hidden === true && s.note.hidden === true && s.list.hidden === false && findAll( s.list, function( el ) { return el.tagName === 'PROGRESS' } ).length === 2 );
		fire( removers( s )[0], 'click' );
		check( '...a button of the list does nothing while the files are sent', s.rows().length === 2 && s.input.files.length === 2 );
	}

	{
		const s = site( { chosen : true, transfer : true } );
		s.ready[0].callback();
		s.choose( [ stood( 'One.mp3', 10 ), stood( 'Two.mp3', 20 ) ] );
		fire( removers( s )[0], 'click' );
		const firstFocus = removers( s )[0].focused === true;
		fire( removers( s )[0], 'click' );
		check( 'the focus does not fall to the page: it stays on the cross that is left, and where the list is empty it goes to the field that chooses files', firstFocus === true && s.input.focused === true );
		check( 'the last file taken out leaves nothing: the list and the sentence are hidden again and the input is empty, so that the browser asks for a file as it does for a form with none', s.chosen.hidden === true && s.note.hidden === true && s.input.files.length === 0 && s.picks().length === 0 );
		const result = s.submit();
		check( '...and the submit sends nothing and leaves the answer to the browser', s.requests.length === 0 && result.prevented === false );
		s.choose( [ stood( 'Three.mp3', 30 ) ] );
		check( 'a new choice lists the new files', names( s ).join() === 'Three.mp3' && s.chosen.hidden === false );
	}

	{
		// The last cross taken out of a longer list: the focus goes to the one before
		const s = site( { chosen : true, transfer : true } );
		s.ready[0].callback();
		s.choose( [ stood( 'A.mp3', 1 ), stood( 'B.mp3', 2 ), stood( 'C.mp3', 3 ) ] );
		fire( removers( s )[2], 'click' );
		check( 'the cross of the last file taken out gives the focus to the one before', names( s ).join() === 'A.mp3,B.mp3' && removers( s )[1].focused === true );
	}

	{
		// A browser that does not let a script set the files of an input: the file is left out of the submit all the same
		const s = site( { chosen : true, transfer : false } );
		s.ready[0].callback();
		s.choose( [ stood( 'Keep.mp3', 10 ), stood( 'Drop.mp3', 20 ), stood( 'Also.mp3', 30 ) ] );
		fire( removers( s )[1], 'click' );
		check( 'where the input cannot be set to the rest the list shows the rest and the input is not touched', names( s ).join( '|' ) === 'Keep.mp3|Also.mp3' && s.input.files.length === 3 );
		s.submit();
		check( '...and the submit leaves the file out: it is in no request and has no row of progress', sent( s ).join() === 'Keep.mp3' && s.rows().length === 2 && s.rows().every( function( row ) { return findAll( row, function( el ) { return el.tagName === 'SPAN' } )[0].textContent !== 'Drop.mp3' } ) );
	}

	{
		const s = site( { chosen : true, transfer : false } );
		s.ready[0].callback();
		s.choose( [ stood( 'Only.mp3', 10 ) ] );
		fire( removers( s )[0], 'click' );
		check( 'a selection the input cannot be emptied of... is emptied by its value, as the browser does it', s.picks().length === 0 && s.input.files.length === 0 );
	}

	{
		// The input that cannot be emptied by its value and has no way to be set: every file is left out
		const s = site( { chosen : true, transfer : false } );
		Object.defineProperty( s.input, 'value', { get : function() { return '' }, set : function() {} } );
		s.ready[0].callback();
		s.choose( [ stood( 'Only.mp3', 10 ), stood( 'Other.mp3', 20 ) ] );
		fire( removers( s )[0], 'click' );
		fire( removers( s )[0], 'click' );
		const result = s.submit();
		check( 'where nothing can be set, a selection that is all taken out sends nothing: the submit is held back, no row, no request', s.picks().length === 0 && s.requests.length === 0 && result.prevented === true && s.rows().length === 0 && s.button.disabled === false && s.assigned.length === 0 );
	}

	{
		// A second choice replaces the first and clears what the progress of an earlier submit left
		const s = site( { chosen : true, transfer : true } );
		s.ready[0].callback();
		s.choose( [ stood( 'Bad.mp3', 5242881 ) ] );
		s.submit();
		check( 'a file the submit refused leaves its row of progress, and the page is not loaded again', s.rows().length === 1 && s.rows()[0].dataset.state === 'failed' && s.assigned.length === 0 );
		s.choose( [ stood( 'Good.mp3', 10 ) ] );
		check( 'a new choice puts the list of the new files where the progress was: the old rows are gone, the list is shown', s.rows().length === 0 && s.list.hidden === true && names( s ).join() === 'Good.mp3' && s.chosen.hidden === false );
	}

	{
		// A form that has no list of the chosen files is the form of before: choosing draws nothing, the submit sends what the input holds
		const s = site( { transfer : true } );
		s.ready[0].callback();
		s.choose( [ stood( 'Plain.mp3', 10 ) ] );
		s.submit();
		check( 'a form without the list is left as it was: the change draws nothing, the submit sends the file', s.chosen === null && sent( s ).join() === 'Plain.mp3' && ( s.form.listeners.change || [] ).length === 0 && ( s.input.listeners.change || [] ).length === 0 );
	}

	{
		const s = site( { chosen : true, transfer : true } );
		s.ready[0].callback();
		check( 'every word the script asked for is a fill of the unit\'s text file, and the sentence under the list is one of them, in both languages', s.missing.length === 0
			&& [ 'de_DE', 'en_US' ].every( function( locale ) { const own = fills( own_( locale ) ); return typeof own['/feature/files/script/chosen'] === 'string' && own['/feature/files/script/chosen'] !== '' && typeof own['/feature/files/script/remove'] === 'string' && own['/feature/files/script/remove'] !== '' } ) );
	}
}

/**
 *	The text file of the unit in a language
 *
 *	@param		{string}	locale
 *
 *	@return		{string}
 */
function own_( locale ) {
	return own( 'install/text/'+ locale+ '.php' );
}
