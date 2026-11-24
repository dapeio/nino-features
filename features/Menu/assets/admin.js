/**
 *	Nino features
 *	admin.js		The panel of the Menu feature: the menus a project has in a table, one menu on a view
 *							of its own with its entries in order, and one entry on a view of its own with its
 *							name, its target and the columns of its panel. A menu is saved as a whole (model A of
 *							docs/admin-ui.md), from either view: the entries, the parts of a column and the texts
 *							are one working copy that the views edit, and nothing is sent before Save - the
 *							pictures included, which are posted to the Images panel once the menu has made their
 *							slots. A part of a column is a dialog of at most four fields; the HTML+ part has the
 *							editor of the kernel. Built from the blocks of the workbench: panel(), table(), list(),
 *							formDialog(), pickDialog(), the question dialog, contextBar() and actionBar().
 *
 *							The addresses are #menu (the table), #menu/menu/<key> (a menu) and #menu/menu/<key>/<entry>. A menu
 *							and an entry are keys, whatever they are called: the panel has no word.
 *							An entry that is not saved yet, and a menu that is not, are their working copy: the
 *							address of one that does not exist when the page loads is the table.
 *
 *							The words are text keys /feature/menu/<menu>/<name>, and a name is never used twice:
 *							a new entry or part is given a name that no entry or part of the menu has, and none
 *							that a text key or a slot has had (the server holds the same rule). The texts of the
 *							working language are what a dialog edits; the name of an entry is in every language.
 *
 *							Admin/Admin.php beside this file does the reading and writing.
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	const t = function( key ) { return Nino.content.getText( key ) };
	const w = function( key ) { return Nino.content.getText( '/_admin/menu/'+ key ) };
	const el = function( tag, className, text ) { return Nino.adminUi.el( tag, className, text ) };

	// What the last answer of the server holds, and what the screen keeps of its own: the menu that is open (a working copy) and the search of the table
	const S = { menus : [], limit : 12, pages : [], locales : [], names : {}, native : '', slots : [], view : null, query : '' };

	const KINDS = [ 'title', 'subtitle', 'image', 'page', 'link', 'html' ];
	const SLUG = /^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/;
	const MAX_SLUG = 32;
	const RESERVED = 'panel';
	const MAX_ENTRIES = 24;
	const MAX_ITEMS = 24;
	const MAX_HTML = 20000;
	const MAX_TEXT = 300;

	const copy = function( value ) { return JSON.parse( JSON.stringify( value ) ) };
	const work = function() { return String( ( Nino.admin.workLocale || {} ).current || S.native || S.locales[0] || '' ) };
	const escapeRegex = function( text ) { return String( text ).replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) };

	// --- the words of a menu -----------------------------------------------------------------------------------------------------------

	const base = function() { return '/feature/menu/'+ S.view.key+ '/' };

	// The text of a name in the working language, else of the first language that has one
	const textOf = function( name ) {

		const texts = ( S.view.texts || {} )[name] || {};

		return texts[ work() ] || S.locales.map( function( code ) { return texts[code] } ).find( function( value ) { return typeof value === 'string' && value !== '' } ) || '';
	};

	const pageOf = function( uri ) { return S.pages.find( function( page ) { return page.uri === uri } ) ?? null };

	const pageName = function( uri ) {

		const page = pageOf( uri );

		return page === null ? uri : ( page.names[ work() ] || page.name );
	};

	// What a link says: the text of its name, else the name of its page
	const wording = function( link ) {

		const own = link.label === '' ? '' : textOf( link.label );

		return own !== '' ? own : ( link.kind === 'page' ? pageName( link.uri ) : link.href );
	};

	// Every name the draft has: the labels, the keys of the texts and the slots of the pictures
	const draftNames = function() {

		const names = [];

		S.view.entries.forEach( function( entry ) {

			if( entry.label !== '' )
				names.push( entry.label );

			( entry.panel === null ? [] : entry.panel.cols ).forEach( function( items ) {
				items.forEach( function( item ) {

					const name = item.kind === 'image' ? item.slot : ( item.label || item.key );

					if( typeof name === 'string' && name !== '' )
						names.push( name );
				} );
			} );
		} );

		return names;
	};

	// The id of a new entry: words from what it is called, one that neither an entry has nor a name of the menu's past
	const newId = function( text ) {

		let stem = String( text ).normalize('NFD').replace( /[̀-ͯ]/g, '' ).toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );

		if( stem === '' || /^[a-z]/.test( stem ) === false )
			stem = 'entry'+ ( stem === '' ? '' : '-'+ stem );

		stem = stem.slice( 0, 24 ).replace( /-[0-9]+$/, '' ).replace( /-+$/, '' );
		stem = stem === 'list' ? 'list-entry' : stem;

		const view = S.view;
		const used = function( id ) {

			const own = new RegExp( '^'+ escapeRegex( base()+ id )+ '(-[1-9][0-9]*)?$' );

			return view.entries.some( function( entry ) { return entry.id === id } ) || view.taken.concat( draftNames() ).some( function( name ) { return own.test( name ) } );
		};

		const letters = 'bcdefghijklmnopqrstuvwxyz';
		let id = stem;

		for( let n = 0; used( id ) === true; n++ )
			id = stem+ '-'+ ( n < letters.length ? letters[n] : letters[ Math.floor( n / letters.length ) - 1 ]+ letters[ n % letters.length ] );

		return id;
	};

	// The name of the next part of an entry: the first number whose key and slot nobody has had
	const newName = function( entry ) {

		const taken = S.view.taken.concat( draftNames() );

		for( let n = 1; n < 1000; n++ )
			if( taken.indexOf( base()+ entry.id+ '-'+ n ) === -1 )
				return base()+ entry.id+ '-'+ n;

		return '';
	};

	const setTexts = function( name, map ) {

		const texts = S.view.texts[name] = S.view.texts[name] || {};

		Object.keys( map ).forEach( function( code ) { texts[code] = map[code] } );
	};

	const changed = function() { if( typeof Nino.admin.dirty === 'object' ) Nino.admin.dirty.refresh() };

	// --- the view that is open --------------------------------------------------------------------------------------------------------

	/**
	 *	A working copy of a menu from what the server answered: the entries, the texts of their names, the pictures waiting for
	 *	their slot and the alt texts typed for them, and the names the menu's key has ever made
	 */
	const makeView = function( key, was, loaded ) {

		return { key : key, was : was, version : loaded.version || '', entries : copy( loaded.entries || [] ), texts : copy( loaded.texts || {} ), alts : {}, files : {}, taken : loaded.taken || [], problems : loaded.problems || [],
			saved : '', form : null, bar : null, context : null, listMount : null, add : null, entry : null, changed : changed };
	};

	const fingerprint = function() {

		if( S.view === null )
			return '';

		commit();

		return JSON.stringify( [ S.view.entries, S.view.texts, S.view.alts, Object.keys( S.view.files ).sort().map( function( slot ) { return [ slot, S.view.files[slot].name, S.view.files[slot].size ] } ) ] );
	};

	// The row of an ordered list that stands for the item at an index: a list item with its place in data-row
	const rowOf = function( mount, index ) {
		return Array.prototype.find.call( mount.querySelectorAll('li.nino-admin-list-row'), function( li ) { return li.dataset.row === String( index ) } ) ?? null;
	};

	const focusOn = function( target ) {

		if( target === null || target === undefined )
			return;

		if( target.hasAttribute('tabindex') === false && target.tagName !== 'BUTTON' && target.tagName !== 'A' )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	/**
	 *	An ordered list as the guide has it: the rows of list(), a row opens what it stands for, and a cluster of three icon buttons
	 *	at the end of the row moves it up and down or takes it out of the draft. The cluster is a child of the row and not of its
	 *	button, which is the overlay that opens it. The focus goes with the row that was moved and, where one is taken out,
	 *	to the next, else the previous, else the button that adds
	 *
	 *	@param		{Element}		mount
	 *	@param		{Array}			items			The array of the draft that the list shows: changed in place
	 *	@param		{Object}		spec			{ label, empty, name( item ), describe( item, index ) -> a row, changed(), add }
	 *
	 *	@return		{Array}								[ [ up, down, remove ], ... ] for every row
	 */
	const ordered = function( mount, items, spec ) {

		mount.innerHTML = '';

		const moves = [];

		if( items.length === 0 ) {
			mount.appendChild( Nino.adminUi.emptyState( spec.empty ) );
			return moves;
		}

		mount.appendChild( Nino.adminUi.list( items.map( function( item, index ) { return Object.assign( { key : String( index ) }, spec.describe( item, index ) ) } ), { label : spec.label } ) );

		const redraw = function() { return ordered( mount, items, spec ) };

		const move = function( from, to ) {

			if( to < 0 || to >= items.length )
				return;

			items.splice( to, 0, items.splice( from, 1 )[0] );

			const next = redraw();

			spec.changed();

			// To the same button of the new place, else to the one that is left
			const again = next[to][ from < to ? 1 : 0 ];

			( again.disabled === true ? ( next[to][0].disabled === true ? next[to][1] : next[to][0] ) : again ).focus();
		};

		const remove = function( index ) {

			items.splice( index, 1 );

			const next = redraw();

			spec.changed();

			const row = rowOf( mount, Math.min( index, items.length - 1 ) );

			focusOn( row !== null ? row.querySelector('button, a') : ( mount.querySelector('.nino-admin-empty') || spec.add ) );

			return next;
		};

		Array.prototype.forEach.call( mount.querySelectorAll('li.nino-admin-list-row'), function( li, index ) {

			const cluster = el( 'span', 'nino-admin-move' );
			const name = spec.name( items[index] );
			const up = Nino.adminUi.iconButton( 'arrow-up', Nino.adminUi.format( w('label/move-up'), name ), function() { move( index, index - 1 ) }, { small : true, disabled : index === 0 } );
			const down = Nino.adminUi.iconButton( 'arrow-down', Nino.adminUi.format( w('label/move-down'), name ), function() { move( index, index + 1 ) }, { small : true, disabled : index === items.length - 1 } );
			const drop = Nino.adminUi.iconButton( 'trash-2', Nino.adminUi.format( w('label/remove'), name ), function() { remove( index ) }, { small : true, danger : true } );

			[ up, down, drop ].forEach( function( button ) { cluster.appendChild( button ) } );
			li.appendChild( cluster );
			moves.push( [ up, down, drop ] );
		} );

		return moves;
	};

	// --- the table of the menus ---------------------------------------------------------------------------------------------------------

	const focusAround = function( mount, keys, gone ) {

		const at = keys.indexOf( gone );
		const around = keys.filter( function( key ) { return key !== gone } );
		const key = around[ Math.min( at, around.length - 1 ) ];

		focusOn( key === undefined ? mount.querySelector('.nino-admin-empty') : mount.querySelector( '[data-row="'+ CSS.escape( key ) +'"] button, [data-row="'+ CSS.escape( key ) +'"]' ) );
	};

	// What the header draws once the first menu is gone: a call that names no menu draws the first one made
	const firstWords = function( key ) {

		if( S.menus.length === 0 || S.menus[0].key !== key )
			return '';

		return S.menus.length > 1 ? Nino.adminUi.format( w('confirm/delete-first'), S.menus[1].key ) : w('confirm/delete-last');
	};

	const removeMenu = function( key, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: w('confirm/delete-title'),
			message	: Nino.adminUi.format( w('confirm/delete'), key )+ ' '+ firstWords( key ),
			yes			: w('confirm/delete-title'),
			no			: t('/_admin/common/label/cancel'),
			opener	: opener,
			onChoose : function( ok ) {

				if( ok !== true )
					return;

				Nino.admin.menu.call( 'delete', { key : key }, function( status, response ) {

					if( status !== 200 || response === null )
						return Nino.adminUi.showError( dc.getElementById('menu-list'), status, response, '/_admin/menu/error/delete' );

					const keys = S.menus.map( function( row ) { return row.key } );
					const fromList = S.view === null;

					S.menus = S.menus.filter( function( row ) { return row.key !== key } );
					S.view = null;

					if( fromList === true ) {
						drawList();
						focusAround( dc.getElementById('menu-list'), keys, key );
					}
					else {
						Nino.admin.router.go( 'menu', [] );
						follow( {} );
					}

					Nino.adminUi.say( t('/_admin/common/msg/deleted') );
				} );
			},
		} );
	};

	// A new menu is asked for its key alone: a dialog of one field, which opens the working copy of the menu - nothing is stored before it is saved
	const openNew = function( opener ) {

		Nino.adminUi.formDialog( {
			title		: w('dialog/new'),
			submit	: w('dialog/new'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			fields	: [ { key : 'key', label : w('label/key'), help : Nino.adminUi.format( w('hint/key'), MAX_SLUG, RESERVED ), required : true, maxLength : MAX_SLUG } ],
			onSubmit : function( values, ctl ) {

				const key = values.key.trim();

				if( SLUG.test( key ) === false || key.length > MAX_SLUG || key === RESERVED )
					return ctl.fail( 'key', Nino.adminUi.format( w('error/invalid'), w('label/key'), Nino.adminUi.format( w('hint/key'), MAX_SLUG, RESERVED ) ) );

				if( S.menus.some( function( row ) { return row.key === key } ) === true )
					return ctl.fail( 'key', Nino.adminUi.format( w('error/taken'), w('label/key') ) );

				// What the key has made before - texts and pictures a menu of that key left - is what the new entries must not take again
				Nino.admin.menu.call( 'load', { key : key, fresh : true }, function( status, response ) {

					if( status !== 200 || response === null )
						return ctl.fail( 'key', Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/load' ) );

					// A menu of that key that has been made since the table was read is no new menu
					if( response.exists === true )
						return ctl.fail( 'key', Nino.adminUi.format( w('error/taken'), w('label/key') ) );

					ctl.close();
					S.view = makeView( key, '', response );
					Nino.admin.router.go( 'menu', [ key ] );
					showMenu( {} );
				} );
			},
		} );
	};

	const drawList = function() {

		const mount = dc.getElementById('menu-list');

		if( mount === null )
			return;

		mount.innerHTML = '';

		const holder = el('div');

		mount.appendChild( holder );

		Nino.adminUi.table( {
			mount		: holder,
			rowKey	: 'key',
			search	: true,
			query		: S.query,
			onQuery	: function( value ) { S.query = value },
			rows		: S.menus,
			labels	: { search : w('label/search'), empty : w('empty/menus'), noMatch : w('empty/nomatch') },
			columns	: [
				{ key : 'key', label : w('label/name'), type : 'string', render : function( value ) { return el( 'code', '', value ) } },
				{ key : 'entries', label : w('label/entries'), type : 'integer' },
				{ key : 'panels', label : w('label/panels'), type : 'integer' },
			],
			actions	: function( row ) {

				const edit = Nino.adminUi.button( w('label/edit'), function() { go( [ row.key ] ) }, { small : true } );
				const drop = Nino.adminUi.button( t('/_admin/common/label/delete'), function() { removeMenu( row.key, drop ) }, { small : true, kind : 'danger' } );

				return [ edit, drop ];
			},
			onRowClick : function( row ) { go( [ row.key ] ) },
		} );

		const make = Nino.adminUi.button( w('label/new'), function() { openNew( make ) }, { kind : 'primary' } );

		mount.appendChild( Nino.adminUi.actionBar( { list : true, primary : make } ).el );
	};

	// --- one part of a column, in a dialog --------------------------------------------------------------------------------------------------

	const pageOptions = function( uri ) {

		const options = S.pages.map( function( page ) { return { value : page.uri, label : ( page.names[ work() ] || page.name )+ ' ('+ page.uri+ ')' } } );

		// A page that has gone from the site stays the choice of the entry that names it, so that saving does not change it
		if( uri !== '' && pageOf( uri ) === null )
			options.unshift( { value : uri, label : uri } );

		return options;
	};

	const targetOptions = function() {
		return [ { value : '', label : w('target/same') }, { value : '_blank', label : w('target/blank') } ];
	};

	const language = function() { return Nino.adminUi.localeName( work() ) };

	// What a part shows of itself in the list: the words it says, the page it leads to, the address
	const summary = function( item ) {

		switch( item.kind ) {

			case 'title':
			case 'subtitle':
				return textOf( item.key );

			case 'image': {
				const slot = S.slots.find( function( one ) { return one.uri === item.slot } );
				const file = S.view.files[item.slot];

				return file !== undefined ? file.name : ( slot !== undefined && slot.url !== null ? w('label/picture') : w('label/picture-none') );
			}

			case 'html':
				return ( new DOMParser().parseFromString( item.html, 'text/html' ).body.textContent || '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 80 );

			default:
				return wording( item );
		}
	};

	/**
	 *	One part of a column made or changed in a dialog of at most four fields. The working copy changes and nothing is
	 *	sent: a title and a subtitle are a text of the working language, a page and a link have a name of their own, a
	 *	picture is the file that is posted when the menu is saved, and HTML+ is the editor of the kernel
	 *
	 *	@param		{Object}		entry			The entry the panel belongs to
	 *	@param		{number}		column
	 *	@param		{number}		index			The place of the part in the column, -1 for a new one
	 *	@param		{string}		kind
	 *	@param		{Element}		opener
	 *	@param		{Function}	done( index )	Called when the draft has changed, with the place of the part
	 */
	const openPart = function( entry, column, index, kind, opener, done ) {

		const items = entry.panel.cols[column];
		const creating = index === -1;
		const item = creating === true ? { kind : kind } : items[index];
		const title = w( creating === true ? 'dialog/add-'+ kind : 'dialog/edit-'+ kind );
		const text = function( key, label, required ) {
			return { key : key, label : Nino.adminUi.format( label, language() ), help : w('hint/'+ key), value : item[ kind === 'title' || kind === 'subtitle' ? 'key' : 'label' ] ? textOf( item[ kind === 'title' || kind === 'subtitle' ? 'key' : 'label' ] ) : '', required : required, maxLength : MAX_TEXT };
		};

		// The text of a part in the working language, written under the name of the part - a name it has, else a new one
		const say = function( name, value ) {

			const own = {};

			own[ work() ] = value;
			setTexts( name, own );
		};

		const finish = function( ctl, made ) {

			if( creating === true )
				items.push( made );
			else
				items[index] = made;

			ctl.close();
			done( creating === true ? items.length - 1 : index );
		};

		const open = function( options ) {
			return Nino.adminUi.formDialog( Object.assign( { title : title, submit : creating === true ? title : t('/_admin/common/label/save'), cancel : t('/_admin/common/label/cancel'), opener : opener }, options ) );
		};

		if( kind === 'title' || kind === 'subtitle' )
			return open( {
				fields	: [ text( 'text', w('label/text-in'), true ) ],
				onSubmit : function( values, ctl ) {

					const name = creating === true ? newName( entry ) : item.key;

					say( name, values.text.trim() );
					finish( ctl, { kind : kind, key : name } );
				},
			} );

		if( kind === 'page' )
			return open( {
				fields	: [
					{ key : 'uri', type : 'select', label : w('label/page'), help : w('hint/page'), options : pageOptions( creating === true ? '' : item.uri ), value : creating === true ? ( S.pages[0] || { uri : '' } ).uri : item.uri, required : true },
					text( 'label', w('label/text-in'), false ),
				],
				onSubmit : function( values, ctl ) {

					if( values.uri === '' )
						return ctl.fail( 'uri', Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/page') ) );

					let name = creating === true ? '' : item.label;

					// A page says its own name where no words are given; words once given stay under their name
					if( values.label.trim() !== '' ) {
						name = name === '' ? newName( entry ) : name;
						say( name, values.label.trim() );
					}

					finish( ctl, { kind : 'page', uri : values.uri, label : name } );
				},
			} );

		if( kind === 'link' )
			return open( {
				fields	: [
					{ key : 'href', label : w('label/address'), help : w('hint/address'), value : creating === true ? '' : item.href, required : true, maxLength : 500 },
					{ key : 'target', type : 'select', label : w('label/target'), options : targetOptions(), value : creating === true ? '' : item.target },
					text( 'label', w('label/text-in'), true ),
				],
				onSubmit : function( values, ctl ) {

					if( addressOk( values.href ) === false )
						return ctl.fail( 'href', Nino.adminUi.format( w('error/invalid'), w('label/address'), w('hint/address') ) );

					const name = creating === true ? newName( entry ) : item.label;

					say( name, values.label.trim() );
					finish( ctl, { kind : 'link', href : values.href.trim(), target : values.target, label : name } );
				},
			} );

		if( kind === 'image' ) {

			const body = el( 'div', 'nino-admin-dialog-fields' );
			const file = Nino.adminUi.fileField( { key : 'file', label : w('label/picture'), help : w('hint/picture'), accept : 'image/*', required : creating === true } );
			const alt = Nino.adminUi.textField( { key : 'alt', label : Nino.adminUi.format( w('label/alt-in'), language() ), help : w('hint/alt'), value : creating === true ? '' : altOf( item.slot ), maxLength : 250 } );

			body.appendChild( file.el );
			body.appendChild( alt );

			return open( {
				body		: body,
				onSubmit : function( values, ctl ) {

					const chosen = file.value();

					if( creating === true && chosen === null ) {
						file.error( Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/picture') ) );
						return ctl.idle();
					}

					const slot = creating === true ? newName( entry ) : item.slot;

					if( chosen !== null )
						S.view.files[slot] = chosen;

					const texts = S.view.alts[slot] = S.view.alts[slot] || {};

					texts[ work() ] = alt.querySelector('input').value.trim();
					finish( ctl, { kind : 'image', slot : slot } );
				},
			} );
		}

		// HTML+, as the editor of the kernel writes it: paragraphs, lists and the inline tags; the server cleans it again
		const body = el( 'div', 'nino-admin-dialog-fields' );
		const field = el( 'div', 'nino-admin-field' );
		const caption = el( 'span', '', w('label/html') );
		const mount = el('div');
		const editor = Nino.admin.htmlEditor.create( mount, creating === true ? '' : item.html, MAX_HTML, 8, 'blocks' );

		// Enter is a paragraph of the editor and no send of the dialog
		// ...and Enter in the field of an address is the answer of its bar - "Apply" - and no implicit send of the form
		field.addEventListener( 'keydown', function( event ) {

			if( event.key !== 'Enter' )
				return;

			event.stopPropagation();

			if( event.target.tagName === 'INPUT' ) {
				event.preventDefault();
				mount.querySelector('.nino-admin-richtext-linkbar button').click();
			}
		} );
		field.setAttribute( 'role', 'group' );
		field.setAttribute( 'aria-label', w('label/html') );
		field.appendChild( caption );
		field.appendChild( mount );
		body.appendChild( field );

		return open( {
			body		: body,
			onSubmit : function( values, ctl ) {

				const html = editor.getValue();

				if( html.length > MAX_HTML ) {
					Nino.adminUi.clearFieldErrors( body );
					Nino.adminUi.fieldError( field, null, 'menu-html-error', Nino.adminUi.format( w('error/invalid'), w('label/html'), Nino.adminUi.format( w('hint/html-long'), MAX_HTML ) ) );
					editor.focus();
					return ctl.idle();
				}

				editor.destroy();
				finish( ctl, { kind : 'html', html : html } );
			},
			onCancel : function() { editor.destroy() },
		} );
	};

	// The alt text of a picture in the working language: what was typed, else what the slot has
	const altOf = function( slot ) {

		const typed = ( S.view.alts[slot] || {} )[ work() ];

		if( typeof typed === 'string' )
			return typed;

		const known = S.slots.find( function( one ) { return one.uri === slot } );

		return known === undefined || known.alt === null || typeof known.alt !== 'object' ? '' : String( known.alt[ work() ] || '' );
	};

	// What the server holds an address to: a fragment, a path of the site, https, http, mailto and tel - the server holds it again
	const addressOk = function( href ) {

		const text = href.trim();

		if( text === '' || text.length > 500 || /[\u0000- \u007f]/.test( text ) === true )
			return false;

		if( text.charAt( 0 ) === '#' )
			return true;

		if( text.charAt( 0 ) === '/' )
			return text.charAt( 1 ) !== '/' && text.charAt( 1 ) !== '\\' && /(^|\/)\.{1,2}(\/|$)/.test( text.split( /[?#]/ )[0] ) === false && /%2e|\\/i.test( text.split( /[?#]/ )[0] ) === false;

		return /^(?:https?:\/\/[^/?#]\S*|mailto:\S+|tel:[+0-9().;-]+)$/i.test( text );
	};

	// --- one entry, on a view of its own ----------------------------------------------------------------------------------------------------

	// The columns of the panel of an entry as sections, each an ordered list of its parts and the button that adds one
	const drawColumns = function( holder, entry, open ) {

		const view = S.view;

		holder.innerHTML = '';

		if( entry.panel === null )
			return;

		const focusPart = function( mount, at, fallback ) {

			const row = rowOf( mount, at );

			focusOn( row !== null ? row.querySelector('button') : fallback );
		};

		entry.panel.cols.forEach( function( items, column ) {

			const section = el( 'section', 'menu-column' );
			const mount = el('div');
			const add = Nino.adminUi.button( w('label/add-part'), function() {

				Nino.adminUi.pickDialog( {
					title		: w('dialog/pick'),
					cancel	: t('/_admin/common/label/cancel'),
					opener	: add,
					items		: KINDS.map( function( kind ) { return { value : kind, label : w('kind/'+ kind), description : w('kind-hint/'+ kind) } } ),
					onChoose : function( kind ) {
						openPart( entry, column, -1, kind, add, function( at ) {
							redraw();
							view.changed();
							focusPart( mount, at, add );
						} );
					},
				} );
			}, { kind : 'secondary' } );

			const redraw = function() {

				ordered( mount, items, {
					label		: Nino.adminUi.format( w('label/column'), column + 1 ),
					empty		: w('empty/column'),
					add			: add,
					name		: function( item ) { return w('kind/'+ item.kind) },
					changed	: function() { view.changed() },
					describe : function( item, index ) {
						return {
							title		: w('kind/'+ item.kind),
							detail	: summary( item ),
							onOpen	: function() {
								openPart( entry, column, index, item.kind, rowOf( mount, index ).querySelector('button'), function( at ) {
									redraw();
									view.changed();
									focusPart( mount, at, add );
								} );
							},
						};
					},
				} );
			};

			section.appendChild( Nino.adminUi.sectionTitle( Nino.adminUi.format( w('label/column'), column + 1 ), 3 ) );
			section.appendChild( mount );
			section.appendChild( add );
			holder.appendChild( section );
			redraw();
		} );
	};

	// The controls of the entry that is open, written back into the draft: called before the draft is read or the view left
	const commit = function() {

		const open = S.view === null ? null : S.view.entry;

		if( open === null )
			return;

		const entry = open.entry;
		const kind = open.controls.kind.querySelector('select').value;
		const names = open.controls.name.value();
		const trimmed = {};

		Object.keys( names ).forEach( function( code ) { trimmed[code] = names[code].trim() } );

		entry.kind = kind;

		if( kind === 'page' ) {
			entry.uri = open.controls.page.querySelector('select').value;
			delete entry.href;
			delete entry.target;
		}
		else {
			entry.href = open.controls.href.querySelector('input').value.trim();
			entry.target = open.controls.target.querySelector('select').value;
			delete entry.uri;
		}

		// A name once given stays under its key, so that the key is never another entry's; a link has its key from the start
		if( entry.label === '' && ( kind === 'link' || Object.keys( trimmed ).some( function( code ) { return trimmed[code] !== '' } ) ) )
			entry.label = base()+ entry.id;

		if( entry.label !== '' )
			setTexts( entry.label, trimmed );
	};

	// What is wrong with an entry that the server would refuse, said at its control: null where it is right
	const wrong = function( entry ) {

		const names = entry.label === '' ? {} : ( S.view.texts[entry.label] || {} );
		const named = Object.keys( names ).some( function( code ) { return String( names[code] ).trim() !== '' } );

		if( entry.kind === 'link' && named === false )
			return { control : 'name', text : Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/name') ) };

		if( entry.kind === 'link' && addressOk( entry.href || '' ) === false )
			return { control : 'href', text : Nino.adminUi.format( w('error/invalid'), w('label/address'), w('hint/address') ) };

		if( entry.kind === 'page' && ( entry.uri || '' ) === '' )
			return { control : 'page', text : Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/page') ) };

		if( entry.panel !== null && entry.panel.cols.some( function( items ) { return items.length > MAX_ITEMS } ) === true )
			return { control : 'columns', text : Nino.adminUi.format( w('msg/part-limit'), MAX_ITEMS ) };

		return null;
	};

	const showEntry = function( id, focus ) {

		const view = S.view;
		const entry = view.entries.find( function( one ) { return one.id === id } );

		if( entry === undefined )
			return false;

		commit();

		const mount = dc.getElementById('menu-entry');
		const controls = {};
		const open = { entry : entry, controls : controls, form : null, bar : null, redraw : function() {} };

		mount.innerHTML = '';
		view.entry = open;

		const bar = Nino.adminUi.contextBar( { back : { to : view.key, href : Nino.admin.router.href( 'menu', [ view.key ] ), onClick : function() { go( [ view.key ] ) } }, title : wording( entry ) } );

		mount.appendChild( bar );
		Nino.adminUi.localeBadge( work(), bar );

		// The way back to the menu keeps what is typed: the entry and the menu are one working copy, which the menu's own way back asks about
		bar.querySelector('a.nino-admin-back-link').dataset.keepDraft = '';

		const form = el( 'form' );
		form.noValidate = true;
		open.form = form;

		// The entry: its name in every language, where it leads
		const about = el( 'fieldset' );
		const grid = el( 'div', 'nino-admin-fieldgrid' );

		about.appendChild( el( 'legend', '', w('label/entry') ) );
		about.appendChild( grid );
		form.appendChild( about );

		controls.name = Nino.adminUi.localeRows( { key : 'name', label : w('label/name'), help : w('hint/name'), locales : S.locales, values : entry.label === '' ? {} : copy( view.texts[entry.label] || {} ), maxLength : MAX_TEXT } );
		controls.kind = Nino.adminUi.selectField( { key : 'kind', label : w('label/kind'), help : w('hint/kind'), value : entry.kind, options : [ { value : 'page', label : w('kind/page') }, { value : 'link', label : w('kind/link') } ] } );
		controls.page = Nino.adminUi.selectField( { key : 'uri', label : w('label/page'), help : w('hint/page'), value : entry.uri || '', options : pageOptions( entry.uri || '' ) } );
		controls.href = Nino.adminUi.textField( { key : 'href', label : w('label/address'), help : w('hint/address'), value : entry.href || '', maxLength : 500, required : true } );
		controls.target = Nino.adminUi.selectField( { key : 'target', label : w('label/target'), value : entry.target || '', options : targetOptions() } );

		[ controls.name.el, controls.kind, controls.page, controls.href, controls.target ].forEach( function( field ) { grid.appendChild( field ) } );

		const sync = function() {

			const page = controls.kind.querySelector('select').value === 'page';

			controls.page.classList.toggle( 'nino-admin-hidden', page === false );
			controls.href.classList.toggle( 'nino-admin-hidden', page === true );
			controls.target.classList.toggle( 'nino-admin-hidden', page === true );
		};

		sync();
		controls.kind.querySelector('select').addEventListener( 'change', function() { sync(); view.changed() } );
		[ controls.page.querySelector('select'), controls.target.querySelector('select'), controls.href.querySelector('input') ].forEach( function( control ) {
			control.addEventListener( 'input', function() { view.changed() } );
		} );
		controls.name.el.addEventListener( 'input', function() { view.changed() } );

		// The panel: whether the entry opens one, how many columns it has and what stands in them
		const panel = el( 'fieldset' );
		const panelGrid = el( 'div', 'nino-admin-fieldgrid' );
		const columns = el( 'div' );

		panel.appendChild( el( 'legend', '', w('label/panel') ) );
		panel.appendChild( panelGrid );

		controls.panel = Nino.adminUi.switchField( { key : 'panel', checked : entry.panel !== null, label : w('label/panel-on'), help : w('hint/panel-on'), on : t('/_admin/common/label/on'), off : t('/_admin/common/label/off') } );
		panelGrid.appendChild( controls.panel );

		const group = el( 'div', 'nino-admin-field' );
		const caption = el( 'span', '', w('label/columns') );

		caption.id = 'menu-columns-label';
		group.appendChild( caption );

		controls.columns = Nino.adminUi.steps( { labelledby : 'menu-columns-label', steps : [ 1, 2, 3, 4 ].map( function( count ) { return { value : count, label : String( count ) } } ), value : entry.panel === null ? 2 : entry.panel.columns, onChange : function( count ) {

			// Fewer columns keep what stands in the first ones and put what stood in the others behind it, in order
			const cols = entry.panel.cols;

			if( cols.length > count ) {
				const rest = cols.splice( count );
				cols[ count - 1 ] = cols[ count - 1 ].concat.apply( cols[ count - 1 ], rest );
			}

			while( cols.length < count )
				cols.push( [] );

			entry.panel.columns = count;
			open.redraw();
			view.changed();
		} } );

		group.appendChild( controls.columns.el );
		panelGrid.appendChild( group );

		open.redraw = function() { drawColumns( columns, entry, open ) };

		const toggle = function() {
			group.classList.toggle( 'nino-admin-hidden', entry.panel === null );
			columns.classList.toggle( 'nino-admin-hidden', entry.panel === null );
		};

		controls.panel.querySelector('input').addEventListener( 'change', function() {

			const count = controls.columns.get();

			entry.panel = controls.panel.querySelector('input').checked === false ? null : ( entry.panel || { columns : count, cols : Array.from( { length : count }, function() { return [] } ) } );
			open.redraw();
			toggle();
			view.changed();
		} );

		panel.appendChild( columns );
		form.appendChild( panel );
		open.redraw();
		toggle();

		const drop = Nino.adminUi.button( w('label/remove-entry'), function() {

			view.entries.splice( view.entries.indexOf( entry ), 1 );
			view.entry = null;
			view.changed();
			go( [ view.key ] );
		}, { kind : 'danger' } );

		open.bar = Nino.adminUi.actionBar( {
			danger	: drop,
			status	: true,
			primary	: Nino.adminUi.button( view.was === '' ? w('label/create') : t('/_admin/common/label/save'), null, { type : 'submit', kind : 'primary' } ),
		} );

		form.appendChild( open.bar.el );
		open.bar.line.bind( form );
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); save( function() {} ) } );
		mount.appendChild( form );

		Nino.adminUi.showLevel( 'menu-entry', focus === undefined ? { focus : controls.name.controls[ work() ] || controls.name.el } : focus );

		return true;
	};

	// --- one menu, on a view of its own -----------------------------------------------------------------------------------------------------

	const entryDetail = function( entry ) {

		const target = entry.kind === 'page' ? pageName( entry.uri ) : entry.href;
		const panel = entry.panel === null ? w('label/no-panel') : Nino.adminUi.format( w( entry.panel.columns === 1 ? 'label/columns-one' : 'label/columns-many' ), entry.panel.columns );

		return target+ ' · '+ panel;
	};

	const drawEntries = function() {

		const view = S.view;

		return ordered( view.listMount, view.entries, {
			label		: w('label/entries'),
			empty		: w('empty/entries'),
			add			: view.add,
			name		: function( entry ) { return wording( entry ) },
			changed	: function() { view.changed() },
			describe : function( entry ) {
				return {
					title		: wording( entry ),
					detail	: entryDetail( entry ),
					badge		: entry.panel === null ? null : { text : w('label/panel'), state : 'muted' },
					onOpen	: function() { go( [ view.key, entry.id ] ) },
				};
			},
		} );
	};

	// A new entry is made in a dialog of its name and where it leads: the name gives the entry its id, which is never used twice
	const addEntry = function( opener ) {

		const view = S.view;

		Nino.adminUi.formDialog( {
			title		: w('dialog/add-entry'),
			submit	: w('dialog/add-entry'),
			cancel	: t('/_admin/common/label/cancel'),
			opener	: opener,
			fields	: [
				{ key : 'name', type : 'locales', label : w('label/name'), help : w('hint/name'), locales : S.locales, values : {}, maxLength : MAX_TEXT },
				{ key : 'kind', type : 'select', label : w('label/kind'), help : w('hint/kind'), options : [ { value : 'page', label : w('kind/page') }, { value : 'link', label : w('kind/link') } ], value : 'page' },
				{ key : 'uri', type : 'select', label : w('label/page'), help : w('hint/page'), options : pageOptions( '' ), value : ( S.pages[0] || { uri : '' } ).uri },
				{ key : 'href', label : w('label/address'), help : w('hint/address'), value : '', maxLength : 500 },
			],
			onSubmit : function( values, ctl ) {

				const names = {};

				Object.keys( values.name ).forEach( function( code ) { names[code] = String( values.name[code] ).trim() } );

				const spoken = names[ work() ] || Object.keys( names ).map( function( code ) { return names[code] } ).find( function( text ) { return text !== '' } ) || '';

				if( values.kind === 'link' && spoken === '' )
					return ctl.fail( 'name', Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/name') ) );

				if( values.kind === 'link' && addressOk( values.href ) === false )
					return ctl.fail( 'href', Nino.adminUi.format( w('error/invalid'), w('label/address'), w('hint/address') ) );

				if( values.kind === 'page' && values.uri === '' )
					return ctl.fail( 'uri', Nino.adminUi.format( t('/_admin/common/error/missing'), w('label/page') ) );

				const entry = values.kind === 'page' ? { id : '', kind : 'page', uri : values.uri, label : '', panel : null } : { id : '', kind : 'link', href : values.href.trim(), target : '', label : '', panel : null };

				entry.id = newId( spoken !== '' ? spoken : pageName( values.uri ) );

				// A name is the entry's own key from the first word; a page that is given none says its own
				if( spoken !== '' || values.kind === 'link' ) {
					entry.label = base()+ entry.id;
					setTexts( entry.label, names );
				}

				view.entries.push( entry );
				ctl.close();
				view.changed();
				go( [ view.key, entry.id ] );
			},
		} );
	};

	const showMenu = function( focus ) {

		const mount = dc.getElementById('menu-form');
		const view = S.view;

		mount.innerHTML = '';
		view.entry = null;

		const bar = Nino.adminUi.contextBar( { back : { to : dc.getElementById('menu-list'), href : Nino.admin.router.href( 'menu', [] ), onClick : function() { go( [] ) } }, title : view.key } );

		mount.appendChild( bar );
		Nino.adminUi.localeBadge( work(), bar );
		view.context = bar;

		const form = el( 'form' );
		form.noValidate = true;
		view.form = form;

		if( view.problems.length > 0 )
			form.appendChild( Nino.adminUi.notice( Nino.adminUi.format( w('hint/problems'), view.problems.join( ', ' ) ) ) );

		const title = Nino.adminUi.sectionTitle( w('label/entries') );

		form.appendChild( title );
		Nino.adminUi.sectionHelp( title, form, Nino.adminUi.format( w('hint/entries'), MAX_ENTRIES ) );

		view.listMount = el('div');
		form.appendChild( view.listMount );

		view.add = Nino.adminUi.button( w('label/add-entry'), function() {

			commit();

			if( view.entries.length >= MAX_ENTRIES )
				return view.bar.line.error( 400, { code : 'menu_list' }, '/_admin/menu/error/save' );

			addEntry( view.add );
		}, { kind : 'secondary' } );

		form.appendChild( view.add );

		const drop = view.was === '' ? undefined : Nino.adminUi.button( w('label/delete-menu'), function() { removeMenu( view.key, drop ) }, { kind : 'danger' } );

		view.bar = Nino.adminUi.actionBar( {
			danger	: drop,
			status	: true,
			primary	: Nino.adminUi.button( view.was === '' ? w('label/create') : t('/_admin/common/label/save'), null, { type : 'submit', kind : 'primary' } ),
		} );

		form.appendChild( view.bar.el );
		view.bar.line.bind( form );
		form.addEventListener( 'submit', function( event ) { event.preventDefault(); save( function() {} ) } );
		mount.appendChild( form );

		drawEntries();

		if( view.saved === '' )
			view.saved = fingerprint();

		Nino.adminUi.showLevel( 'menu-form', focus.focus === false ? { focus : false } : { focus : view.add } );
	};

	// --- saving -----------------------------------------------------------------------------------------------------------------------------

	// The posted texts: the words of the names the entries have, in the languages of the site, and no word of a name that is gone
	const postedTexts = function() {

		const texts = {};

		draftNames().forEach( function( name ) {

			if( S.view.texts[name] === undefined )
				return;

			texts[name] = {};

			S.locales.forEach( function( code ) {
				if( typeof S.view.texts[name][code] === 'string' )
					texts[name][code] = S.view.texts[name][code];
			} );
		} );

		return texts;
	};

	// The slots of the pictures of the draft that have a file or an alt text waiting
	const waiting = function( view ) {

		const slots = [];

		view.entries.forEach( function( entry ) {
			( entry.panel === null ? [] : entry.panel.cols ).forEach( function( items ) {
				items.forEach( function( item ) {
					if( item.kind === 'image' && ( view.files[item.slot] !== undefined || view.alts[item.slot] !== undefined ) && slots.indexOf( item.slot ) === -1 )
						slots.push( item.slot );
				} );
			} );
		} );

		return slots;
	};

	// The pictures and their alt texts, posted to the Images panel once the menu has made the slots
	const sendPictures = function( view, done ) {

		const slots = waiting( view );

		const next = function() {

			if( slots.length === 0 )
				return done( true );

			const slot = slots.shift();
			const sendAlt = function() {

				if( view.alts[slot] === undefined )
					return next();

				Nino.adminUi.api.call( 'images/alt', { uri : slot, alt : view.alts[slot] }, function( status, response ) {

					if( status !== 200 || response === null )
						return done( false );

					next();
				} );
			};

			if( view.files[slot] === undefined )
				return sendAlt();

			Nino.adminUi.api.call( 'images/upload', { uri : slot }, function( status, response ) {

				if( status !== 200 || response === null )
					return done( false );

				if( typeof Nino.admin.touch === 'function' )
					Nino.admin.touch('slots');

				sendAlt();
			}, { file : view.files[slot] } );
		};

		next();
	};

	// What the server holds of the menu, in place of the working copy: the entry that is open stays open where it is still there
	const reloadView = function( view ) {

		const at = view.entry === null ? '' : view.entry.entry.id;

		Nino.admin.menu.call( 'load', { key : view.key }, function( status, response ) {

			// The menu is gone: what is there is the table
			if( status === 404 ) {
				S.view = null;
				Nino.admin.router.go( 'menu', [] );
				Nino.admin.menu.reload( function() { follow( {} ) } );
				return;
			}

			if( status !== 200 || response === null )
				return Nino.adminUi.showError( dc.getElementById('menu-list'), status, response, '/_admin/menu/error/load' );

			S.view = makeView( view.key, view.key, response );
			showMenu( { focus : false } );

			if( at !== '' && showEntry( at, { focus : false } ) === false )
				Nino.admin.router.set( 'menu', [ view.key ] );

			S.view.saved = fingerprint();
			changed();
		} );
	};

	const save = function( done, force ) {

		const view = S.view;

		// The control that had the focus is the one that has it after the view is drawn again
		const held = dc.activeElement && dc.activeElement.dataset ? dc.activeElement.dataset : {};
		const heldSelector = held.key ? '[data-key="'+ held.key+ '"]'+ ( held.locale ? '[data-locale="'+ held.locale+ '"]' : '' ) : '';

		commit();

		const where = function() { return view.entry === null ? view.bar : view.entry.bar };

		Nino.adminUi.clearFieldErrors( view.entry === null ? view.form : view.entry.form );

		// What the server would refuse is said where it is typed: the entry that is open, else the entry that is wrong is opened
		const bad = view.entries.find( function( entry ) { return wrong( entry ) !== null } );

		if( bad !== undefined ) {

			const fault = wrong( bad );

			if( view.entry === null || view.entry.entry !== bad )
				showEntry( bad.id, { focus : false } );

			const controls = view.entry.controls;
			const control = fault.control === 'name' ? controls.name.el : ( fault.control === 'columns' ? controls.columns.el : controls[ fault.control ] );
			const input = fault.control === 'name' ? ( controls.name.controls[ work() ] || controls.name.el ) : ( fault.control === 'columns' ? controls.columns.el.querySelector('button') : control.querySelector('input, select') );

			view.entry.bar.line.error( 400, { code : 'menu_invalid' }, '/_admin/menu/error/save' );
			Nino.adminUi.fieldError( control, input, 'menu-error-0', fault.text );
			Nino.admin.router.set( 'menu', [ view.key, bad.id ] );
			input.focus();
			done( false );
			return;
		}

		where().line.saving();

		Nino.admin.menu.call( 'save', { key : view.key, entries : view.entries, texts : postedTexts(), version : view.version, force : force === true }, function( status, response ) {

			if( status !== 200 || response === null ) {

				where().line.error( status, response, '/_admin/menu/error/save' );

				// Another session deleted the menu since it was read: there is nothing to overwrite, and the only way on is to see what is there
				if( status === 409 && response !== null && response.code === 'menu_gone' )
					Nino.adminUi.confirm( 'big', {
						title		: w('confirm/gone-title'),
						message	: Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/save' ),
						yes			: w('confirm/gone-yes'),
						no			: t('/_admin/common/label/cancel'),
						opener	: where().el,
						onChoose : function( ok ) { if( ok === true ) reloadView( view ) },
					} );

				// Another session wrote the menu since it was read: the kernel's question - overwrite, reload or leave it. A menu made in the meantime is never written over
				if( status === 409 && response !== null && response.code === 'menu_changed' && view.was !== '' )
					Nino.adminUi.conflict( {
						message		: Nino.adminUi.api.errorText( status, response, '/_admin/menu/error/save' ),
						opener		: where().el,
						onOverwrite : function() { save( done, true ) },
						onReload	: function() { reloadView( view ) },
						onCancel	: function() {},
					} );

				done( false );
				return;
			}

			sendPictures( view, function( sent ) {

				// What the server wrote is what the menu is now: its names, its texts and the slots come back with the next read
				Nino.admin.menu.call( 'load', { key : view.key }, function( status2, response2 ) {

					if( status2 !== 200 || response2 === null ) {
						where().line.error( status2, response2, '/_admin/menu/error/load' );
						done( false );
						return;
					}

					const at = view.entry === null ? '' : view.entry.entry.id;

					Nino.admin.menu.reload( function() {

						S.view = makeView( view.key, view.key, response2 );
						showMenu( { focus : false } );

						if( at !== '' )
							showEntry( at, { focus : false } );

						const line = ( S.view.entry === null ? S.view.bar : S.view.entry.bar ).line;
						const again = heldSelector === '' ? null : ( S.view.entry === null ? S.view.form : S.view.entry.form ).querySelector( heldSelector );

						if( sent === true )
							line.saved();
						else
							line.error( 400, { code : 'menu_picture' }, '/_admin/menu/error/upload' );

						S.view.saved = fingerprint();
						Nino.admin.router.set( 'menu', at === '' ? [ view.key ] : [ view.key, at ] );
						changed();

						if( again !== null )
							again.focus();

						done( true );
					} );
				} );
			} );
		} );
	};

	// --- the address -----------------------------------------------------------------------------------------------------------------------------

	const go = function( parts ) {

		const proceed = function() {
			Nino.admin.router.go( 'menu', parts );
			follow( {} );
		};

		// Leaving the menu that is open, for another or for the table, asks first - the entry and the menu are one working copy
		if( S.view !== null && ( parts[0] !== S.view.key ) && typeof Nino.admin.dirty === 'object' )
			return Nino.admin.dirty.guard( [ 'menu' ], proceed, function() {} );

		proceed();
	};

	const showTable = function() {

		S.view = null;
		drawList();
		Nino.adminUi.showLevel( 'menu-list', { focus : false } );
		Nino.admin.router.set( 'menu', [] );
	};

	// The key a part of an address is: '' where there is none, or where the part is a word - a menu and an entry are keys, and the panel has no word
	const keyAt = function( parts, at ) {
		return parts[at] === undefined || Nino.admin.router.wordOf( parts, at ) !== '' ? '' : String( parts[at] );
	};

	// Show what the address names: the table, a menu, or an entry of a menu
	const follow = function( focus ) {

		const at = Nino.admin.router.current();
		const parts = at.panel === 'menu' ? at.parts : [];
		const key = keyAt( parts, 0 );
		const id = key === '' ? '' : keyAt( parts, 1 );

		if( key === '' )
			return showTable();

		const show = function() {

			const view = S.view;
			const entry = id === '' ? undefined : view.entries.find( function( one ) { return one.id === id } );

			// The menu has not been drawn yet, or an entry was left: it is drawn from the working copy
			if( view.form === null || ( entry === undefined && view.entry !== null ) ) {

				commit();
				showMenu( focus );
			}

			if( entry === undefined ) {
				drawEntries();
				Nino.adminUi.showLevel( 'menu-form', focus.focus === false ? { focus : false } : { focus : view.add } );
				Nino.admin.router.set( 'menu', [ key ] );
				return;
			}

			if( view.entry === null || view.entry.entry !== entry )
				showEntry( id, focus.focus === false ? { focus : false } : undefined );
			else
				Nino.adminUi.showLevel( 'menu-entry', { focus : false } );

			Nino.admin.router.set( 'menu', [ key, id ] );
		};

		// The menu that is open is its working copy; another is read from the server
		if( S.view !== null && S.view.key === key )
			return show();

		if( S.menus.some( function( row ) { return row.key === key } ) === false )
			return showTable();

		Nino.admin.menu.call( 'load', { key : key }, function( status, response ) {

			if( status !== 200 || response === null )
				return Nino.adminUi.showError( dc.getElementById('menu-list'), status, response, '/_admin/menu/error/load' );

			S.view = makeView( key, key, response );
			show();
		} );
	};

	const addressOfView = function() {

		if( S.view === null )
			return [];

		return S.view.entry === null ? [ S.view.key ] : [ S.view.key, S.view.entry.entry.id ];
	};

	Nino.admin.menu = Nino.adminUi.panel( {
		uri			: 'menu',
		mount		: 'menu-list',
		show		: 'always',
		error		: '/_admin/menu/error/load',
		// What the shell asks before a menu is left with input nobody has saved
		dirty		: {
			isDirty	: function() { return S.view !== null && fingerprint() !== S.view.saved },
			save		: function( done ) { save( done ) },
			discard	: function() { if( S.view !== null ) S.view.saved = fingerprint() },
			// The bar of the view that is on screen carries the badge: the menu's or the entry's
			bar			: function() { return S.view === null ? null : ( S.view.entry !== null ? S.view.entry.bar.el : ( S.view.bar === null ? null : S.view.bar.el ) ) },
		},
		render	: function( data ) {

			S.menus = data.menus || [];
			S.limit = data.limit || 12;
			S.pages = data.pages || [];
			S.locales = data.locales || [];
			S.names = data.names || {};
			S.native = data.native || '';
			S.slots = data.slots || [];

			// A list that was asked for again while a menu is open (after a save) only brings the data: what is on screen stays
			if( S.view === null )
				follow( { focus : false } );
		},
	} );

	Object.assign( Nino.admin.menu, {

		/**
		 *	Show what the address names, once the data is there; a move to another panel leaves what is on screen as it is
		 *
		 *	@return		void
		 */
		showCurrent : function() {

			const at = Nino.admin.router.current();

			if( Nino.admin.menu.ready === true && at.panel !== 'menu' ) {
				Nino.admin.router.set( 'menu', addressOfView() );
				return;
			}

			if( Nino.admin.menu.ready === false ) {
				Nino.admin.menu.init();
				return;
			}

			const leaving = S.view !== null && keyAt( at.parts, 0 ) !== S.view.key;

			// The router runs this once it is allowed to: a move inside the menu that is open (to an entry and back) asks nothing and keeps the working copy, and only a move that
			// leaves the menu - after the question - gives it up
			const proceed = function() {

				if( leaving === true )
					S.view = null;

				follow( { focus : false } );
			};

			Nino.admin.router.leave( [ 'menu' ], leaving, proceed, function() {
				Nino.admin.router.set( 'menu', addressOfView() );
			} );
		},
	} );

	// What is shown by name is shown in the working language: it is drawn again when that moves. What is typed in a dialog or in a field stays
	if( typeof Nino.admin.workLocale === 'object' && Nino.admin.workLocale !== null && typeof Nino.admin.workLocale.follow === 'function' )
		Nino.admin.workLocale.follow( null, function( code ) {

			if( S.view === null || S.view.listMount === null )
				return;

			Nino.adminUi.localeBadge( code, S.view.entry === null ? S.view.context : null );

			if( S.view.entry === null )
				drawEntries();
			else
				S.view.entry.redraw();
		} );

} )(window,document);
