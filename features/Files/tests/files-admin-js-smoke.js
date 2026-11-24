/**
 *	Nino features
 *	files-admin-js-smoke.js	What the script of the panel does over the real shell of the workbench (adminPage() of tests/harness.js: the router, the registry of
 *													unsaved input, dialogs that open modally) and the real blocks - the directory of the folders, the folder as a view of its own with
 *													the directory of its files, the record of one file - one pin for each thing:
 *
 *													A. the directory: a row for each folder by its id, names that are text and never markup, the group as the select of the directory only
 *													   where there are two groups, the upload mark as a badge, the buttons of a row - the first opens the folder - and the one bar
 *													B. what narrows it: the group and the search, and what the box of the head chooses; a deletion of the chosen asks and says how many
 *													C. a folder is made and renamed in dialogs, with the refusals of the server said at their field; the mark is an immediate action
 *													D. the address: #files/files/<folder> is the folder with its files, #files/files/<folder>/<file> the record over it, a folder that
 *													   is not there is the directory and the address says so - and a reload at each of them draws it
 *													E. the folder: its files as rows, a removed file offers nothing but deletion, the bar has the danger at the left, the main button at the right
 *													F. the record of a file: the form holds what the file has, posts only what changed, refusals at the name, the deletion asks nested
 *													G. what is chosen in the folder: show and hide (one request each, one mail for all), move, remove from the server, delete - each asks
 *													   and says the number
 *													H. the upload: the chunks in order with the right fields, a refusal stops, 413 halves the chunk, files the limits refuse are said by name
 *													I. the data and the actions apart from the screen, and every word the script reads in both languages
 *
 *													The helpers - check(), done(), findAll(), fire(), element(), load(), adminPage() and fills() - are those of tests/harness.js of the
 *													checkout, three levels up or the one NINO_ROOT names. files-smoke.php runs this where node is on the path.
 *
 *	Usage: node features/Files/tests/files-admin-js-smoke.js
 *	       NINO_ROOT=../nino node features/Files/tests/files-admin-js-smoke.js
 */

'use strict';

/* global require, __dirname, process */

const fs = require('fs');
const path = require('path');

const root = fs.realpathSync( process.env.NINO_ROOT || path.join( __dirname, '../../..' ) );
const { check, done, fills, load, adminPage, element, findAll, fire } = require( path.join( root, 'tests/harness.js' ) );

// A suite that is stopped by an error says so as a failed check and ends with its count, as every other failure does - it is no crash that the run would have to read
let ended = false;
const stopped = function( error ) {

	if( ended === true )
		return;

	ended = true;
	check( 'the suite ran to its end - it was stopped by: '+ String( error && error.message ? error.message : error ).slice( 0, 120 ), false );
	done();
};

process.on( 'uncaughtException', stopped );
process.on( 'unhandledRejection', stopped );

const featureDir = path.join( __dirname, '..' );
const relative = path.relative( root, path.join( featureDir, 'assets/admin.js' ) );
const own = function( locale ) { return fills( path.relative( root, path.join( featureDir, 'text/'+ locale+ '.php' ) ) ) };
const english = Object.assign( {}, fills('_admin/text/en_US.php'), own('en_US') );
const german = Object.assign( {}, fills('_admin/text/de_DE.php'), own('de_DE') );
const W = function( key ) { return english['/_admin/files/'+ key] };

const hasClass = function( node, name ) { return String( node.className ).split(/\s+/).indexOf( name ) !== -1 };
const byClass = function( root, name ) { return findAll( root, function( node ) { return hasClass( node, name ) } ) };
const buttons = function( root ) { return findAll( root, function( node ) { return node.tagName === 'BUTTON' } ) };
const named = function( root, label ) { return buttons( root ).filter( function( button ) { return button.textContent === label || button.attributes['aria-label'] === label } ) };
const atFolder = function( id ) { return function( h ) { return h.router.href( 'files', [ id ] ) } };
const atFile = function( folder, file ) { return function( h ) { return h.router.href( 'files', [ folder, file ] ) } };
const choose = function( root, ids ) { ids.forEach( function( id ) { const row = rowsOf( root ).filter( function( each ) { return each.dataset.row === id } )[0]; const box = findAll( row, function( node ) { return node.tagName === 'INPUT' && node.type === 'checkbox' } )[0]; box.checked = true; fire( box, 'click' ) } ) };
const clone = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
const text = function( root ) { return findAll( root, function() { return true } ).map( function( node ) { return node.textContent } ).join('\n') };

const ROOM = 'aaaaaaaaaaaa', LOBBY = 'bbbbbbbbbbbb', EMPTY = 'cccccccccccc', SIDE = 'dddddddddddd';
const F1 = '111111111111', F2 = '222222222222', F3 = '333333333333';

// What the server answers: the folders of every group, with the files of the one that is open
const folder = function( id, group, name, upload, count, bytes ) {
	return { id : id, key : id, group : group, name : name, upload : upload, created : '2026-01-01T10:00:00Z', count : count, bytes : bytes };
};

const file = function( folderId, id, name, extra ) {
	return Object.assign( { id : id, key : id, folder : folderId, name : name, ext : name.split('.').pop(), size : 2400, mime : 'audio/mpeg', sha256 : 'a'.repeat( 64 ), by : { kind : 'owner', id : '', label : '' }, visible : true, final : false, created : '2026-11-02T10:15:00Z', url : '/.files/'+ id }, extra ?? {} );
};

const FOLDERS = [
	folder( ROOM, 'staff', 'Room', true, 3, 7200 ),
	folder( SIDE, 'staff', '<img src=x onerror=alert(1)>', false, 0, 0 ),
	folder( EMPTY, 'tourists', 'Left behind', false, 0, 0 ),
	folder( LOBBY, 'guests', 'Lobby', false, 1, 100 ),
];

const FILES = [
	file( ROOM, F1, 'Demo.03.mp3' ),
	file( ROOM, F2, '<b>bold</b> [csrf].mp3', { visible : false, by : { kind : 'member', id : 'abc', label : 'Ada Lovelace' } } ),
	file( ROOM, F3, 'Gone.mp3', { gone : '2026-11-05T09:00:00Z', visible : true, by : { kind : 'member', id : '', label : '' } } ),
];

const LIST = function( open ) {
	return { groups : [ 'guests', 'staff', 'tourists' ], members : [ 'guests', 'staff' ], group : '', folders : clone( FOLDERS ), files : open === undefined ? [] : clone( FILES ).filter( function( each ) { return each.folder === open } ),
		types : [ 'mp3', 'pdf', 'txt' ], notify : false, limits : { chunk : 1048576, max : 5242880, php : 2097152, quota : 0, used : 0 } };
};

/** The script over the real shell: the address is the shell's, the requests are answered by the test, the dialogs are the blocks' */
function world( list, hash ) {

	const h = adminPage( { files : [ [ 'files', 'files' ] ] } );
	const requests = [];
	const seen = [];
	const toasts = [];
	const forms = [];
	const questions = [];
	const root = element('div');
	const folders = element('div');
	const view = element('div');

	folders.id = 'files-list';
	view.id = 'files-folder';
	root.appendChild( folders );
	root.appendChild( view );
	h.page.appendChild( root );
	h.show('files');
	h.router.start();
	h.router.set( 'files', [] );
	h.Nino.content.getText = function( key ) { return english[key] ?? key };
	h.Nino.http = { sendRequest : function( uri, method, callback, data ) { const request = { action : data.action, payload : JSON.parse( data.data ), extra : data, callback : callback }; requests.push( request ); seen.push( request ) } };
	h.ui.say = function( words ) { toasts.push( words ) };
	h.ui.formDialog = function( options ) { forms.push( options ); return true };
	h.ui.confirm = function( level, options ) { questions.push( { level : level, options : options } ); return true };

	if( hash !== undefined ) {
		h.box.location.hash = typeof hash === 'function' ? hash( h ) : hash;
	}

	const levels = [];
	const made = [];
	const directory = h.ui.directory;

	h.ui.directory = function( options ) { made.push( options ); return directory.call( h.ui, options ) };
	const shown = h.ui.showLevel;

	h.ui.showLevel = function( name, options ) { levels.push( name ); return shown.call( h.ui, name, options ) };
	load( relative, h.context );

	const answered = { list : list ?? null };
	const w = {
		h : h, panel : h.admin.files, requests : requests, seen : seen, levels : levels, made : made, toasts : toasts, forms : forms, questions : questions, folders : folders, view : view,
		answer : function( status, body ) {
			const request = requests.shift();
			request.callback( { status : status, responseJSON : body } );
			return request;
		},
		// The shell shows the panel, and the server answers the list - for the folder the address names
		open : function() {
			w.panel.showCurrent();
			const asked = requests[0].payload.folder;
			w.answer( 200, clone( answered.list ?? LIST( asked === '' ? undefined : asked ) ) );
		},
		controller : function() {
			const said = { closed : 0, failed : [], idle : 0 };
			return { said : said, ctl : { close : function() { said.closed++ }, fail : function( key, words ) { said.failed.push( [ key, words ] ) }, idle : function() { said.idle++ } } };
		},
	};

	return w;
}

const rowsOf = function( root ) { return findAll( root, function( node ) { return node.tagName === 'TR' && node.dataset.row !== undefined } ) };
const idsOf = function( root ) { return rowsOf( root ).map( function( row ) { return row.dataset.row } ).join() };

// --- A. the directory ---------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open();

	check( 'A. the first showing asked for the list with no folder named, and for nothing else', w.seen.length === 1 && w.seen[0].action === 'files/list' && w.seen[0].payload.folder === '' );
	check( 'A. a row for each folder by its id, in the order the server sent them', idsOf( w.folders ) === [ ROOM, SIDE, EMPTY, LOBBY ].join() );
	check( 'A. a name is text and never markup: nothing of it that spells an element is one', findAll( w.folders, function( node ) { return node.tagName === 'IMG' } ).length === 0 && text( w.folders ).indexOf('<img src=x onerror=alert(1)>') !== -1 );

	const select = findAll( w.folders, function( node ) { return node.tagName === 'SELECT' && node.id === 'files-group' } )[0];

	check( 'A. the group is the category of the directory with every group and "all" first - there are three groups', select !== undefined && select.children.map( function( option ) { return option.value } ).join('|') === '|guests|staff|tourists' );
	check( 'A. the upload folder of a group wears a badge with a sign and a word', text( rowsOf( w.folders )[0] ).indexOf('✓ '+ W('state/upload') ) !== -1 && text( rowsOf( w.folders )[1] ).indexOf( W('state/upload') ) === -1 );
	check( 'A. a row has the buttons of a folder: the first opens it, then rename and the mark, the deletion last and in red', ( function() {
		const row = rowsOf( w.folders )[0];
		const labels = buttons( row ).map( function( button ) { return button.textContent } );
		return labels[0] === W('label/open') && buttons( row )[0].attributes['aria-label'] === fmt( W('label/open-folder'), 'Room' ) && labels.indexOf( W('label/rename') ) !== -1 && labels.indexOf( W('label/unmark') ) !== -1 && labels.indexOf( words('/_admin/common/label/delete') ) !== -1;
	} )() );
	check( 'A. the one bar of the list has the one main button, New folder', named( w.folders, W('label/new') ).length === 1 && byClass( w.folders, 'nino-admin-btn-primary' ).length === 1 );
}

function words( key ) { return english[key] }

{
	const one = LIST();
	one.groups = [ 'staff' ];
	one.folders = one.folders.filter( function( each ) { return each.group === 'staff' } );

	const w = world( one );

	w.open();
	check( 'A. a project with one group has no select of it: a lone option is no choice', findAll( w.folders, function( node ) { return node.tagName === 'SELECT' && node.id === 'files-group' } ).length === 0 && rowsOf( w.folders ).length === 2 );
}

{
	const none = LIST();
	none.groups = [];
	none.members = [];
	none.folders = [];

	const w = world( none );

	w.open();
	check( 'A. no group and no folder is a box with the sentence that says what to do first - no table, no bar, no button', rowsOf( w.folders ).length === 0 && text( w.folders ).indexOf( W('empty/groups') ) !== -1 && buttons( w.folders ).length === 0 );

	const some = LIST();
	some.folders = [];

	const v = world( some );

	v.open();
	check( 'A. a group that has no folder yet is a box with the button that makes the first', named( v.folders, W('label/new') ).length === 1 && rowsOf( v.folders ).length === 0 && text( v.folders ).indexOf( W('empty/folders') ) !== -1 );
}

// --- B. narrowing and choosing -------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open();

	const select = findAll( w.folders, function( node ) { return node.tagName === 'SELECT' && node.id === 'files-group' } )[0];

	select.value = 'staff';
	fire( select, 'change' );
	check( 'B. a group leaves the folders of that group', idsOf( w.folders ) === [ ROOM, SIDE ].join() );

	const search = findAll( w.folders, function( node ) { return node.tagName === 'INPUT' && node.type === 'search' } )[0];

	search.value = 'tourists';
	fire( search, 'input' );
	select.value = '';
	fire( select, 'change' );
	check( 'B. the search reads the group as well as the name', idsOf( w.folders ) === EMPTY );

	search.value = '';
	fire( search, 'input' );

	choose( w.folders, [ SIDE, EMPTY ] );

	const bulk = byClass( w.folders, 'nino-admin-btn-danger' );

	check( 'B. choosing rows offers the deletion of them', bulk.length >= 1 );
	fire( bulk[0], 'click' );

	const question = w.questions[0];

	check( 'B. the deletion of the chosen asks first, at the level of what is lost for good, names how many folders and how many files go - and sends nothing before the answer',
		question !== undefined && question.level === 'destructive' && question.options.message === fmt( W('confirm/folders'), 2 ) && question.options.details.length >= 2 && w.requests.length === 0 );
	question.options.onChoose( true );
	check( 'B. a yes is one call of delete for each folder - the right and the token are asked for each', w.requests.length === 1 && w.requests[0].action === 'files/folder/delete' && w.requests[0].payload.id === SIDE );
	w.answer( 200, { deleted : 0 } );
	check( 'B. ... in order', w.requests.length === 1 && w.requests[0].payload.id === EMPTY );
}

// --- C. a folder is made, renamed, marked ----------------------------------------------------------------------------------------

{
	const w = world();

	w.open();
	named( w.folders, W('label/new') )[0].listeners.click[0]();

	const dialog = w.forms[0];

	check( 'C. the dialog of a new folder has the name (required) and the group, which is one of the groups the members have - and no help at a tick',
		dialog.title === W('dialog/new') && dialog.fields.map( function( field ) { return field.key } ).join() === 'name,group' && dialog.fields[0].required === true && dialog.fields[1].options.map( function( option ) { return option.value } ).join() === 'guests,staff' );

	let made = w.controller();

	dialog.onSubmit( { name : 'Concert tapes', group : 'staff' }, made.ctl );
	check( 'C. a folder posts its group and its name', w.requests[0].action === 'files/folder/add' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { group : 'staff', name : 'Concert tapes' } ) );
	w.answer( 400, { error : 'x', code : 'files_group', field : 'group' } );
	check( 'C. a group the members do not have is said at the group, the dialog stays open and no toast says it is done', made.said.failed[0][0] === 'group' && made.said.closed === 0 && w.toasts.length === 0 );

	made = w.controller();
	dialog.onSubmit( { name : 'x', group : 'staff' }, made.ctl );
	w.answer( 400, { error : 'x', code : 'files_long', params : [ 80 ], field : 'name' } );
	check( 'C. a name that is too long is said at the name', made.said.failed[0][0] === 'name' );

	made = w.controller();
	dialog.onSubmit( { name : 'Fine', group : 'staff' }, made.ctl );
	w.answer( 200, { folder : { id : ROOM } } );
	check( 'C. a made folder closes the dialog, reads the list again and says so in a toast', made.said.closed === 1 && w.requests[0].action === 'files/list' );
	w.answer( 200, LIST() );
	check( 'C. ... the toast comes with the list', w.toasts.length === 1 && w.toasts[0] === W('msg/folder-added') );
}

{
	const w = world();

	w.open();
	named( rowsOf( w.folders )[0], W('label/rename') )[0].listeners.click[0]();

	const dialog = w.forms[0];
	let made = w.controller();

	check( 'C. the dialog of a rename holds the name the folder has and names its group above it', dialog.title === W('dialog/rename') && dialog.message === 'staff' && dialog.fields[0].value === 'Room' );
	dialog.onSubmit( { name : 'Room' }, made.ctl );
	check( 'C. a name that did not change posts nothing and closes', w.requests.length === 0 && made.said.closed === 1 );
	made = w.controller();
	dialog.onSubmit( { name : 'Rooms' }, made.ctl );
	check( 'C. a new name posts the id and the name', JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : ROOM, name : 'Rooms' } ) && w.requests[0].action === 'files/folder/save' );
	w.answer( 404, { error : 'x', code : 'files_unknown' } );
	check( 'C. a folder that is gone is said in the dialog', made.said.failed.length === 1 && made.said.closed === 0 );

	named( rowsOf( w.folders )[1], W('label/mark') )[0].listeners.click[0]();
	check( 'C. the mark is an immediate action: it posts the id with on true, and the toast says it', w.requests[0].action === 'files/folder/upload-target' && w.requests[0].payload.on === true );
	w.answer( 200, { folder : {} } );
	w.answer( 200, LIST() );
	check( 'C. ... and a folder that has the mark takes it off with on false', w.toasts[0] === W('msg/marked') && ( function() {
		named( rowsOf( w.folders )[0], W('label/unmark') )[0].listeners.click[0]();
		return w.requests[0].payload.on === false;
	} )() );
}

// --- D. the address ----------------------------------------------------------------------------------------------------------

{
	const w = world();

	w.open();
	buttons( rowsOf( w.folders )[0] )[0].listeners.click[0]();

	check( 'D. opening a folder writes #files/files/<folder> as a new entry of the history and asks for the list of that folder', w.h.hash() === atFolder( ROOM )( w.h ) && w.requests[0].action === 'files/list' && w.requests[0].payload.folder === ROOM );
	w.answer( 200, LIST( ROOM ) );
	check( 'D. ... the files are the rows of the directory of the folder, by their ids', idsOf( w.view ) === [ F1, F2, F3 ].join() );
	check( 'D. ... its name is the title with the group beside it, and the one way back goes to the folders, an address that is a real link', byClass( w.view, 'nino-admin-contextbar-title' )[0].textContent === 'Room' && byClass( w.view, 'nino-admin-back-link' ).length === 1
		&& byClass( w.view, 'nino-admin-back-link' )[0].href === w.h.router.href( 'files', [] ) );
}

{
	const w = world( null, atFolder( ROOM ) );

	w.open();
	check( 'D. a reload at the address of a folder asks for that folder and draws it', idsOf( w.view ).split(',').length === 3 );
}

{
	const w = world( null, atFile( ROOM, F2 ) );

	w.open();
	check( 'D. a reload at the address of a file opens the record of it over its folder', w.h.dialogs.filter( function( d ) { return d.open } ).length === 1 && idsOf( w.view ).split(',').length === 3
		&& byClass( w.h.dialogs.filter( function( d ) { return d.open } )[0], 'nino-admin-dialog-title' )[0].textContent === FILES[1].name );
}

{
	const w = world( null, atFolder('ffffffffffff') );

	w.open();
	check( 'D. a folder the server does not have is the directory of the folders, and the address says so', idsOf( w.folders ).split(',').length === 4 && w.h.hash() === w.h.router.href( 'files', [] ) );
}

// --- E. the folder -------------------------------------------------------------------------------------------------------------

{
	const w = world( null, atFolder( ROOM ) );

	w.open();

	const rows = rowsOf( w.view );

	check( 'E. a name with markup and a key in it is text', findAll( w.view, function( node ) { return node.tagName === 'B' } ).length === 0 && text( rows[1] ).indexOf('<b>bold</b> [csrf].mp3') !== -1 );
	check( 'E. the state is a badge with a word and a sign; a member who is gone is said as such, the owner as the owner, a member by name', text( rows[0] ).indexOf('✓ '+ W('state/visible') ) !== -1 && text( rows[1] ).indexOf('● '+ W('state/hidden') ) !== -1 && text( rows[2] ).indexOf( W('state/removed') ) !== -1
		&& text( rows[2] ).indexOf( W('label/by-gone') ) !== -1 && text( rows[0] ).indexOf( W('label/by-owner') ) !== -1 && text( rows[1] ).indexOf('Ada Lovelace') !== -1 );
	check( 'E. a file with bytes has its pencil, the download and the deletion in its row - one that was removed from the server has the pencil and the deletion', buttons( rows[0] ).length >= 3 && named( rows[0], W('label/download') ).length === 1 && named( rows[2], W('label/download') ).length === 0 );
	check( 'E. the bar has the deletion of the folder at the left, rename and the mark beside it and the upload as the one main button at the right', ( function() {
		const bar = byClass( w.view, 'nino-admin-actionbar' )[0];
		const labels = buttons( bar ).map( function( button ) { return button.textContent } );
		return labels[0] === W('label/delete-folder') && labels[labels.length - 1] === W('label/upload') && labels.indexOf( W('label/rename') ) !== -1 && byClass( bar, 'nino-admin-btn-primary' ).length === 1 && byClass( bar, 'nino-admin-btn-danger' ).length === 1;
	} )() );
	check( 'E. no paragraph stands over the directory of the files: between the way back and the table there is the box of the directory and nothing that is text of its own', findAll( w.view, function( node ) { return node.tagName === 'P' && !hasClass( node, 'nino-admin-empty' ) && node.textContent !== '' } ).length === 0 );
	check( 'E. the state of a file is a filter of the directory, with "all" first', findAll( w.view, function( node ) { return node.tagName === 'SELECT' && node.id === 'files-state' } )[0].children.map( function( option ) { return option.value } ).join('|') === '|visible|hidden|removed' );

	// F. the record
	fire( rows[0], 'click', { target : rows[0] } );

	const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];

	check( 'F. a click on a row opens the record of the file at #files/files/<folder>/<file>, with the name in its head and its id under it', dialog !== undefined && w.h.hash() === atFile( ROOM, F1 )( w.h ) && byClass( dialog, 'nino-admin-dialog-title' )[0].textContent === FILES[0].name
		&& byClass( dialog, 'nino-admin-dialog-subtitle' )[0].textContent === F1 );
	check( 'F. its form holds the name, the other folders of the group, whether the members see it and whether it is final - and reads the sender, the day, the size, the type and the checksum', findAll( dialog, function( node ) { return node.tagName === 'INPUT' && node.type === 'text' } ).length >= 1
		&& findAll( dialog, function( node ) { return node.tagName === 'SELECT' } ).length === 1 && findAll( dialog, function( node ) { return node.tagName === 'SELECT' } )[0].children.length === 2 && byClass( dialog, 'nino-admin-readline' ).length === 5 );
}

{
	const w = world( null, atFile( ROOM, F2 ) );

	w.open();

	const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];
	const save = named( dialog, words('/_admin/common/label/save') )[0];
	const switches = findAll( dialog, function( node ) { return node.tagName === 'INPUT' && node.type === 'checkbox' } );

	check( 'F. a record of a file that is hidden shows the switch off and posts nothing when nothing changed - it closes', switches[0].checked === false && ( function() { fire( save, 'click' ); return w.requests.length === 0 } )() );

	switches[0].checked = true;
	fire( switches[0], 'change' );
	fire( save, 'click' );
	check( 'F. a change is posted as the one field that changed, with the id and no mail', w.requests.length === 1 && w.requests[0].action === 'files/save' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : F2, notify : false, visible : true } ) );
	w.answer( 200, { file : Object.assign( {}, FILES[1], { visible : true } ) } );
	check( 'F. the row of the directory shows what was saved', text( rowsOf( w.view )[1] ).indexOf('✓ '+ W('state/visible') ) !== -1 );

	const delete_ = named( dialog, W('label/delete-file') )[0];

	fire( delete_, 'click' );
	check( 'F. the deletion from the record asks - nested, naming the file - and posts nothing before the answer', delete_ !== undefined && w.questions.length === 1 && w.questions[0].options.nested === true && w.questions[0].options.details[0] === FILES[1].name && w.requests.length === 0 );
	w.questions[0].options.onChoose( true );
	check( 'F. a yes posts the one action of the server for this id', w.requests.length === 1 && w.requests[0].action === 'files/delete' && w.requests[0].payload.id === F2 );
	w.answer( 200, { deleted : true } );
	check( 'F. the file goes from the directory and the record is closed', idsOf( w.view ) === [ F1, F3 ].join() && w.h.dialogs.filter( function( d ) { return d.open } ).length === 0 );
}

{
	const w = world( null, atFile( ROOM, F2 ) );

	w.open();

	const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];
	const name = findAll( dialog, function( node ) { return node.tagName === 'INPUT' && node.type === 'text' } )[0];
	const save = named( dialog, words('/_admin/common/label/save') )[0];

	name.value = 'x'.repeat( 3 )+ '.mp3';
	fire( name, 'input' );
	fire( save, 'click' );
	w.answer( 400, { error : 'x', code : 'files_extension', field : 'name' } );
	check( 'F. a refusal of the server leaves the record open with the input as typed and says what was refused', w.h.dialogs.filter( function( d ) { return d.open } ).length === 1 && name.value === 'xxx.mp3' && text( dialog ).indexOf( english['/_admin/error/files_extension'] ) !== -1 );
}

// --- K. the cards, the record's bar ------------------------------------------------------------------------------------------------

{
	const w = world( null, atFolder( ROOM ) );

	w.open();

	const folders = w.made.filter( function( options ) { return options.rowKey === 'key' && options.columns[0].label === W('label/name') && options.columns.length === 5 } )[0];
	const files = w.made[w.made.length - 1];
	const sorting = { sortBy : english['/_admin/common/table/sort-by'], sortNone : english['/_admin/common/table/sort-default'], sortUp : english['/_admin/common/table/sort-up'], sortDown : english['/_admin/common/table/sort-down'] };
	const roles = function( options ) { return options.columns.map( function( column ) { return column.key+ ':'+ ( column.primary === true ? 'primary' : String( column.narrow ) ) } ).join() };

	check( 'K. the columns of the two directories say their roles for the cards: the name is the name of the card, the group and the sender are the second line, the sizes are left out', folders !== undefined && roles( folders ) === 'name:primary,group:second,count:undefined,bytes:false,upload:undefined' && roles( files ) === 'name:primary,size:false,who:second,day:undefined,state:undefined' );
	check( 'K. the four words of the select that sorts the cards are in the labels of both directories, the workbench\'s own words', folders !== undefined && Object.keys( sorting ).every( function( key ) { return folders.labels[key] === sorting[key] && files.labels[key] === sorting[key] && sorting[key] !== undefined && german['/_admin/common/table/'+ { sortBy : 'sort-by', sortNone : 'sort-default', sortUp : 'sort-up', sortDown : 'sort-down' }[key]] !== undefined } ) );
}


{
	// A file moved to another folder by its record: the record stays on it, the address has the other folder as its prefix and the screen is that folder's
	const w = world( null, atFile( ROOM, F1 ) );

	w.open();

	const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];
	const target = findAll( dialog, function( node ) { return node.tagName === 'SELECT' } )[0];
	const save = named( dialog, words('/_admin/common/label/save') )[0];

	target.value = SIDE;
	fire( target, 'change' );
	fire( save, 'click' );
	check( 'F. the move of a file by its record posts the other folder', w.requests.length === 1 && w.requests[0].action === 'files/save' && w.requests[0].payload.folder === SIDE );
	w.answer( 200, { file : Object.assign( {}, FILES[0], { folder : SIDE } ) } );
	check( 'F. ... the address is the one of the file in the other folder, and the other folder is read', w.h.hash() === atFile( SIDE, F1 )( w.h ) && w.requests.length === 1 && w.requests[0].action === 'files/list' && w.requests[0].payload.folder === SIDE );
}

// --- G. a selection in the folder ---------------------------------------------------------------------------------------------

{
	const w = world( null, atFolder( ROOM ) );

	w.open();

	choose( w.view, [ F2, F3 ] );

	const bulk = function( label ) {
		return findAll( w.view, function( node ) { return node.tagName === 'BUTTON' && node.textContent === label && rowsOf( w.view ).every( function( row ) { return findAll( row, function( inner ) { return inner === node } ).length === 0 } ) } )[0];
	};

	fire( bulk( W('label/show') ), 'click' );
	check( 'G. showing the chosen posts one save for each file that can be shown - the removed one is left out - with the id, visible true and no mail', w.requests.length === 1 && w.requests[0].action === 'files/save' && JSON.stringify( w.requests[0].payload ) === JSON.stringify( { id : F2, visible : true, notify : false } ) );
}

// --- H. the upload -------------------------------------------------------------------------------------------------------------

{
	const w = world( null, atFolder( ROOM ) );

	w.open();
	fire( named( byClass( w.view, 'nino-admin-actionbar' )[0], W('label/upload') )[0], 'click' );

	const dialog = w.forms[0];

	check( 'H. the upload is a dialog with a body of its own: the file field that takes several files, the list and the two switches', dialog.title === fmt( W('dialog/upload'), 'Room' ) && dialog.body !== undefined && findAll( dialog.body, function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } ).length === 1 );

	const input = findAll( dialog.body, function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } )[0];
	const blob = function( size ) { return { name : 'Take.mp3', size : size, slice : function( from, to ) { return { from : from, to : to } } } };
	const pick = function( files ) { input.files = files; fire( input, 'change' ) };

	check( 'H. the input is told the types the server takes, and several files at once', input.multiple === true && input.accept === '.mp3,.pdf,.txt' );

	pick( [ { name : 'a.exe', size : 5, slice : null }, { name : 'empty.mp3', size : 0, slice : null }, { name : 'big.mp3', size : 6000000, slice : null }, blob( 2500000 ) ] );

	const rows = byClass( dialog.body, 'files-admin-row' );

	check( 'H. a file the limits refuse - a type that is not taken, an empty one, one over the largest - is said by name at the field and left out; the rest is the list', rows.length === 1 && rows[0].children[0].textContent === 'Take.mp3'
		&& [ 'a.exe', 'empty.mp3', 'big.mp3' ].every( function( name ) { return text( dialog.body ).indexOf( name ) !== -1 } ) );

	let made = w.controller();

	dialog.onSubmit( {}, made.ctl );
	check( 'H. the first chunk is as large as the server says - the setting, one megabyte here - and posts the folder, the name, the size, no upload id, index 0 of 3, visible - never notify', w.requests[0].action === 'files/upload'
		&& JSON.stringify( w.requests[0].payload ) === JSON.stringify( { folder : ROOM, name : 'Take.mp3', size : 2500000, upload : '', index : 0, count : 3, visible : true, notify : false } ) && w.requests[0].extra.chunk.from === 0 && w.requests[0].extra.chunk.to === 1048576 );
	w.answer( 200, { upload : '0123456789abcdef', index : 0 } );
	check( 'H. the next chunk names the upload the first was answered with', w.requests[0].payload.upload === '0123456789abcdef' && w.requests[0].payload.index === 1 && w.requests[0].extra.chunk.from === 1048576 );
	w.answer( 200, { upload : '0123456789abcdef', index : 1 } );
	w.answer( 200, { id : F1, name : 'Take.mp3', size : 2500000, mailed : 0 } );
	check( 'H. the last chunk ends the upload: the dialog closes, the list is read again, and the toast says how many are in', made.said.closed === 1 && w.requests[0].action === 'files/list' );
	w.answer( 200, LIST( ROOM ) );
	check( 'H. ... said once', w.toasts.length === 1 && w.toasts[0] === W('msg/uploaded-one') );

	// a refusal stops it
	const second = w.forms.length;

	fire( named( byClass( w.view, 'nino-admin-actionbar' )[0], W('label/upload') )[0], 'click' );

	const again = w.forms[second];
	const input2 = findAll( again.body, function( node ) { return node.tagName === 'INPUT' && node.type === 'file' } )[0];

	input2.files = [ blob( 2500000 ) ];
	fire( input2, 'change' );
	made = w.controller();
	again.onSubmit( {}, made.ctl );
	w.answer( 400, { error : 'x', code : 'files_type' } );
	check( 'H. a refusal of the server stops the upload, is said at the foot of the dialog in the words of its code, and the dialog stays open', made.said.failed.length === 1 && made.said.failed[0][0] === null && made.said.closed === 0 && w.requests.length === 0 );

	made = w.controller();
	again.onSubmit( {}, made.ctl );
	w.answer( 413, { error : 'x' } );
	check( 'H. a chunk the web server answers 413 starts the file again as a new upload in chunks of half the size', w.requests[0].payload.index === 0 && w.requests[0].payload.upload === '' && w.requests[0].payload.count === 5 && w.requests[0].extra.chunk.to === 524288 );
}

// --- I. the data and the actions, apart from the screen --------------------------------------------------------------------------

{
	const w = world();
	const data = w.panel.model;

	check( 'I. the cut of a file, the halving, the size and the day are pure: they answer without a page', JSON.stringify( data.chunks( 10, 4 ) ) === '[[0,4],[4,8],[8,10]]' && data.chunks( 0, 4 ).length === 0 && data.halve( 1000000, 1000000 ) === 500000 && data.halve( 100000, 100000 ) === 0
		&& data.size( 1536 ) === '1.5 '+ W('unit/kilobyte') && data.day( '2026-11-02T10:15:00Z' ) === '2026-11-02' && data.day( 'x' ) === '' );
	check( 'I. what a file may be done with follows its state: one whose bytes are gone can only be deleted', JSON.stringify( data.can( { state : 'removed' } ) ) === '["delete"]' && data.can( { state : 'visible' } ).indexOf('hide') !== -1 && data.can( { state : 'hidden' } ).indexOf('show') !== -1 );
	check( 'I. the record posts what changed and nothing else', JSON.stringify( data.changes( { name : 'a.mp3', folder : 'x', visible : true, final : false }, { name : 'a.mp3', folder : 'x', visible : false, final : false } ) ) === '{"visible":false}'
		&& Object.keys( data.changes( { name : 'a', folder : 'x', visible : true, final : false }, { name : 'a', folder : 'x', visible : true, final : false } ) ).length === 0 );
	check( 'I. the folders a file may be moved to are the others of its group', data.targets( FOLDERS, FOLDERS[0] ).map( function( each ) { return each.id } ).join() === SIDE );
	check( 'I. the actions are the ten of the server', Object.keys( w.panel.actions ).sort().join() === 'add,chunk,delete,list,mark,notify,remove,removeFolder,rename,save' );
}

// every word the script reads is in both languages
{
	const source = fs.readFileSync( path.join( featureDir, 'assets/admin.js' ), 'utf8' );
	const keys = new Set();
	const missing = [];

	( source.match( /\bw\(\s*'([a-z0-9/-]+)'\s*\)/g ) ?? [] ).forEach( function( call ) { keys.add( '/_admin/files/'+ /'([^']+)'/.exec( call )[1] ) } );
	( source.match( /'(?:label|msg|hint|confirm|dialog|empty|error|filter|note|select|state|unit)\/[a-z0-9-]+'/g ) ?? [] ).forEach( function( literal ) { keys.add( '/_admin/files/'+ literal.slice( 1, -1 ) ) } );

	keys.forEach( function( key ) {
		if( ( english[key] ?? '' ) === '' || ( german[key] ?? '' ) === '' )
			missing.push( key );
	} );

	check( 'I. every word the script reads is in both languages of the panel'+ ( missing.length === 0 ? '' : ' - missing: '+ missing.join(', ') ), keys.size > 60 && missing.length === 0 );
	check( 'I. the two files of words declare the same keys', Object.keys( own('en_US') ).join('|') === Object.keys( own('de_DE') ).join('|') );
}

// --- J. the address is the one of what is on screen ---------------------------------------------------------------------------

async function addresses() {

	const hrefOf = function( w, parts ) { return w.h.router.href( 'files', parts ) };
	const raw = function( tail ) { return function( h ) { return h.router.href( 'files', [] )+ '/files'+ tail } };

	// A part nobody knows is written over by the address of what is shown: the folders, or the folder
	const inFolder = function( w ) { return hrefOf( w, [ ROOM ] ) };
	const cases = [
		[ 'a key nobody knows', raw('/zzz'), '', function( w ) { return hrefOf( w, [] ) } ],
		[ 'a word nobody knows', raw('/@nope'), '', function( w ) { return hrefOf( w, [] ) } ],
		[ 'a word in front of a key', raw('/@folder/'+ ROOM), '', function( w ) { return hrefOf( w, [] ) } ],
		[ 'a key that is no key of a folder', raw('/'+ ROOM+ 'x'), '', function( w ) { return hrefOf( w, [] ) } ],
		[ 'an escape that is none', raw('/%zz'), '', function( w ) { return hrefOf( w, [] ) } ],
		[ 'a key that is no key of a file', raw('/'+ ROOM+ '/'+ F2+ 'x'), ROOM, inFolder ],
		[ 'a part behind the file', raw('/'+ ROOM+ '/'+ F2+ '/extra'), ROOM, inFolder ],
		[ 'a word behind the folder', raw('/'+ ROOM+ '/@nope'), ROOM, inFolder ],
		[ 'a file the folder has not', raw('/'+ ROOM+ '/000000000000'), ROOM, inFolder ],
	];

	cases.forEach( function( each ) {

		const w = world( null, each[1] );

		w.open();
		check( 'J. '+ each[0]+ ': what the address names is not there, so the screen shows '+ ( each[2] === '' ? 'the folders' : 'the folder' )+ ' and the address says so', w.levels[w.levels.length - 1] === ( each[2] === '' ? 'files-list' : 'files-folder' ) && w.h.hash() === each[3]( w ) );
	} );

	{
		const w = world( null, raw('/'+ ROOM+ '/000000000000') );

		w.open();
		check( 'J. a key that is one of a file and names none is said over the folder ("not found")', text( w.view ).indexOf( W('error/missing-file') ) !== -1 );

		const e = world( null, raw('/'+ ROOM+ '/'+ F2+ '/extra') );

		e.open();
		check( 'J. a part behind a file that is there is only written over - the file exists, so no sentence says it does not', text( e.view ).indexOf( W('error/missing-file') ) === -1 && e.h.hash() === hrefOf( e, [ ROOM ] ) );

		const v = world( null, raw('/zzz') );

		v.open();
		check( 'J. a part that could be no identifier is only written over: no sentence about a file or a folder', text( v.folders ).indexOf( W('error/missing-file') ) === -1 && text( v.folders ).indexOf( W('error/missing-folder') ) === -1 );

		const u = world( null, function( h ) { return h.router.href( 'files', [ 'ffffffffffff' ] ) } );

		u.open();
		check( 'J. a folder that could be one and is not there is said over the folders, and the address is theirs', text( u.folders ).indexOf( W('error/missing-folder') ) !== -1 && u.h.hash() === hrefOf( u, [] ) );
	}

	{
		// A part typed with an escape in it that names a file opens the record, whose address is the one the router writes
		const w = world( null, raw('/'+ ROOM+ '/%'+ F1.charCodeAt( 0 ).toString( 16 )+ F1.slice( 1 )) );

		w.open();
		check( 'J. an id typed with an escape in it opens the record of the file over the folder and the address is written as the router writes it', w.h.dialogs.filter( function( d ) { return d.open } ).length === 1 && w.h.hash() === atFile( ROOM, F1 )( w.h ) );
	}

	{
		// The room the unfinished uploads hold is said where the quota is said: in the upload dialog, once, where something is held
		const held = LIST( ROOM );

		held.limits = Object.assign( {}, held.limits, { quota : 10485760, used : 1048576, held : 2097152 } );

		const w = world( held, atFolder( ROOM ) );

		w.open();
		fire( named( byClass( w.view, 'nino-admin-actionbar' )[0], W('label/upload') )[0], 'click' );

		const plain = LIST( ROOM );

		plain.limits = Object.assign( {}, plain.limits, { quota : 10485760, used : 1048576, held : 0 } );

		const v = world( plain, atFolder( ROOM ) );

		v.open();
		fire( named( byClass( v.view, 'nino-admin-actionbar' )[0], W('label/upload') )[0], 'click' );

		check( 'J. where unfinished uploads hold room the upload dialog says how much, beside the quota; where none do it says nothing of it',
			text( w.forms[0].body ).indexOf( fmt( W('note/held'), '2 MB' ) ) !== -1 && text( v.forms[0].body ).indexOf( W('note/held').split('%s')[1] ) === -1 && text( v.forms[0].body ).indexOf( fmt( W('note/quota'), '1 MB', '10 MB' ) ) !== -1 );
	}

	{
		// A record that a typed address opened: closed, the address is the one of the folder that stays on screen, and a reload shows the same
		const w = world( null, atFile( ROOM, F1 ) );

		w.open();

		const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];
		const close = byClass( dialog, 'nino-admin-dialog-close' )[0];

		fire( close, 'click' );
		await Promise.resolve();
		check( 'J. the cross of a record that a typed address opened writes the address of the folder, which stays on screen', w.h.dialogs.filter( function( d ) { return d.open } ).length === 0 && w.h.hash() === hrefOf( w, [ ROOM ] ) && idsOf( w.view ).split(',').length === 3 );

		// The same by a step to a file that is not there: the record says so and the folder's address is written, not the directory's
		w.h.box.location.hash = atFile( ROOM, '999999999999' )( w.h );
		w.h.hashchange();
		w.panel.showCurrent();
		await Promise.resolve();
		check( 'J. a step to a file that the folder has not leaves the folder on screen with its address and a sentence', w.h.hash() === hrefOf( w, [ ROOM ] ) && text( w.view ).indexOf( W('error/missing-file') ) !== -1 );
	}

	{
		// A step, with a record open, to a part that could be no id: the record is gone, the folder's address is written, and no sentence says that a file is missing
		const w = world( null, atFile( ROOM, F1 ) );

		w.open();
		w.h.box.location.hash = w.h.router.href( 'files', [ ROOM ] )+ '/zzz';
		w.h.hashchange();
		w.panel.showCurrent();
		await Promise.resolve();
		check( 'J. a step from a record to a part that could be no id closes the record, writes the folder\'s address and says nothing of a missing file', w.h.dialogs.filter( function( d ) { return d.open } ).length === 0 && w.h.hash() === hrefOf( w, [ ROOM ] ) && text( w.view ).indexOf( W('error/missing-file') ) === -1 );
	}
	{
		// The record: "Save and continue" beside Save, the deletion at the left, and "Remove from the server" drawn red - it takes the bytes away - with a question that says what stays and what goes
		const w = world( null, atFile( ROOM, F1 ) );

		w.open();
		await Promise.resolve();

		const dialog = w.h.dialogs.filter( function( d ) { return d.open } )[0];
		const bar = byClass( dialog, 'nino-admin-record-bar' )[0] ?? byClass( dialog, 'nino-admin-actionbar' )[0];
		const labels = buttons( bar ).map( function( button ) { return button.textContent } );
		const remove = named( bar, W('label/remove') )[0];
		const bulk = w.made[w.made.length - 1].select.actions.filter( function( action ) { return action.key === 'remove' } )[0];

		check( 'K. the record has Save and "Save and continue" at the right and Delete file at the left', labels[0] === W('label/delete-file') && labels.indexOf( english['/_admin/common/label/save'] ) !== -1 && labels.some( function( label ) { return label !== english['/_admin/common/label/save'] && /continue/i.test( label ) } ) );
		check( 'K. "Remove from the server" is a button of the record - its one red place, at the left, is the deletion of the file - and a red action of the selection: it takes the bytes away', remove !== undefined && byClass( bar, 'nino-admin-btn-danger' ).length === 1 && byClass( bar, 'nino-admin-btn-danger' )[0].textContent === W('label/delete-file') && bulk !== undefined && bulk.danger === true );

		fire( remove, 'click' );
		check( 'K. its question stands on the record (nested), is of the level destructive, and says what stays - the entry with its name - and what goes - the bytes - before anything is sent', w.questions.length === 1 && w.questions[0].level === 'destructive' && w.questions[0].options.nested === true && w.questions[0].options.message === W('confirm/remove-one')
			&& /bytes/.test( W('confirm/remove-one') ) && /stays/.test( W('confirm/remove-one') ) && w.requests.length === 0 );
	}
}

addresses().then( done );

function fmt( pattern ) { const rest = Array.prototype.slice.call( arguments, 1 ); let at = 0; return pattern.replace( /%s/g, function() { return String( rest[at++] ) } ) }
