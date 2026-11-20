/**
 *	Nino features
 *	Themes						The tab "Own CSS" of the pane of the themes (#themes-css, see Modules\Themes\ProjectCss): one field for the stylesheet of the
 *										project - private/assets/style.css, which belongs to no theme and is on every page under every theme - beside the website
 *										as that text would draw it, and the bar of a work surface that saves it.
 *
 *										The text is a draft until it is saved: what is typed is sent to the preview once the hand has rested (Nino.adminUi.previewFrame()),
 *										and the answer says what is wrong with its braces - which keeps Save from writing it. A save is made on the stamp the text was
 *										opened with; another tab or another person that wrote it since is a conflict, and the question is the kernel's (Nino.adminUi.conflict()).
 *										Unsaved text is reported to the registry of the shell, which asks before it is lost.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The mount of the tab, which the workbench renders from the registry, and the name of its entry in the registry of unsaved input
	const MOUNT = 'themes-css-list';
	const ENTRY = 'themes-css';

	// What the screen holds: the answer it is drawn from, the text as it is typed and as it is saved, the stamp of the saved one, the page the preview shows, and
	// which draw it is - of two requests for the screen the answer of the later one is drawn
	const C = {
		data		: null,
		css			: '',
		saved		: '',
		stamp		: '',
		page		: '',
		faults	: [],
		view		: null,
		loop		: null,
		run			: 0,
	};

	/**
	 *	A word of the view in the language of the interface: a fill /_admin/themes/css/<key>
	 *
	 *	@param		{string}		key
	 *	@param		{...*}			params			What goes where the text has %s, %d or %n
	 *
	 *	@return		{string}
	 */
	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/themes/css/'+ key ), ...params );
	};

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	/**
	 *	One action of the API of the own css
	 *
	 *	@param		{string}		action			'css', 'css-save' or 'css-preview'
	 *	@param		{Object}		payload
	 *	@param		{Function}	done				( status, response )
	 *
	 *	@return		{void}
	 */
	const call = function( action, payload, done ) {
		Nino.adminUi.api.call( 'themes/'+ action, payload, done );
	};

	// The length of a text in bytes, which is what the limit counts
	const bytesOf = function( text ) {
		return new TextEncoder().encode( text ).length;
	};

	// A size in kilobytes as the counter says it: one decimal below ten, none from there on
	const kilobytes = function( bytes ) {

		const kb = bytes / 1024;
		const digits = kb < 10 ? 1 : 0;

		return kb.toLocaleString( String( dc.documentElement.lang || '' ).replace( '_', '-' ) || undefined, { minimumFractionDigits : digits, maximumFractionDigits : digits } );
	};

	/**
	 *	What keeps the text from being saved: it is longer than the limit, or its braces, strings, comments or url() are not closed - the sentences of the
	 *	faults the preview answered. '' where nothing does
	 *
	 *	@return		{string}
	 */
	const blockedBy = function() {

		if( bytesOf( C.css ) > C.data.max )
			return Nino.adminUi.format( Nino.content.getText('/_admin/error/themes_css_big'), C.data.max );

		// What could end a style element or a comment of html: the rule of the server, said while the text stands - a file that holds it is shown and cannot be saved
		const mark = /<\/style|<!--|-->/i.exec( C.css );

		if( mark !== null )
			return Nino.adminUi.format( Nino.content.getText('/_admin/error/themes_css_markup'), mark[0].toLowerCase() );

		return C.faults.map( function( fault ) { return word( 'fault/'+ fault ) } ).join(' ');
	};

	// The bar says what is the case: unsaved text, and what keeps it from being saved
	const paint = function() {

		if( C.view === null )
			return;

		const bytes = bytesOf( C.css );

		C.view.size.textContent = bytes < 1024 ? word( 'size/bytes', bytes.toLocaleString( String( dc.documentElement.lang || '' ).replace( '_', '-' ) || undefined ), kilobytes( C.data.max ) ) : word( 'size/note', kilobytes( bytes ), kilobytes( C.data.max ) );
		C.view.bar.set( { dirty : C.css !== C.saved, blocked : blockedBy() } );
	};

	// The address of the page of the preview: a page of this site and nothing else, with the token of the draft
	const addressOf = function( query ) {

		const dir = typeof Nino.dir === 'string' ? Nino.dir : '';
		const page = /^\/(?![\/\\])/.test( C.page ) === true ? C.page : '/';

		return dir+ page+ '?'+ query;
	};

	/**
	 *	Save the text: on the stamp it was opened with, or over whatever is there where the person answered the conflict with Overwrite
	 *
	 *	@param		{boolean}		force
	 *	@param		{Function}	done					done( ok )
	 *
	 *	@return		{void}
	 */
	const save = function( force, done ) {

		C.view.bar.line.saving();

		call( 'css-save', { css : C.css, base : C.stamp, force : force }, function( status, response ) {

			if( status === 200 ) {

				C.stamp = response.stamp;
				C.saved = C.css;
				C.view.bar.line.idle('');
				paint();
				Nino.adminUi.say( Nino.content.getText('/_admin/common/msg/saved') );
				done( true );
				return;
			}

			// Another tab or another person wrote the file since this one opened it: Reload shows what is there, Overwrite writes over it
			if( response !== null && typeof response === 'object' && response.code === 'themes_css_changed' ) {

				C.view.bar.line.error( status, response, '/_admin/themes/css/error/save' );
				Nino.adminUi.conflict( {
					opener		: C.view.bar.save,
					message		: Nino.adminUi.api.errorText( status, response, '/_admin/themes/css/error/save' ),
					onCancel	: function() { done( false ) },
					onReload	: function() {

						// The work of this tab is what the question says it drops
						discard();
						show();
						done( true );
					},
					onOverwrite : function() { save( true, done ) },
				} );
				return;
			}

			C.view.bar.line.error( status, response, '/_admin/themes/css/error/save' );
			done( false );
		} );
	};

	// The draft is given up where the person left the screen without saving it: what the shell's question was answered with
	const discard = function() {
		C.css = C.saved;
	};

	/**
	 *	The screen: the field and its counter beside the preview, and the bar under them
	 *
	 *	@param		{Element}		mount
	 *	@param		{Object}		data					What the API answered
	 *
	 *	@return		{void}
	 */
	const build = function( mount, data ) {

		C.data = data;
		C.view = null;

		// A file that cannot be shown - longer than the limit, a NUL byte, no utf-8 - is said, and nothing is offered to write over it
		if( data.problem !== '' ) {
			mount.appendChild( Nino.adminUi.notice( word( 'state/unshown', word( 'why/'+ data.problem.replace( /^themes_css_/, '' ) ) ) ) );
			return;
		}

		C.css = data.css;
		C.saved = data.css;
		C.stamp = data.stamp;
		C.faults = [];
		C.page = data.pages.some( function( page ) { return page.path === '/' } ) === true ? '/' : ( data.pages[0] !== undefined ? data.pages[0].path : '/' );

		const level = el( 'div', 'themes-css-level' );
		const settings = el( 'div', 'themes-css-column' );
		const size = el( 'p', 'nino-admin-hint themes-css-size' );
		const field = Nino.adminUi.codeField( { label : word('field/label'), help : word('field/help'), value : C.css, rows : 24, id : 'themes-css-field', onInput : function( value ) {
			C.css = value;
			paint();
			C.loop.schedule();
		} } );

		field.classList.add('themes-css-field');
		size.setAttribute( 'role', 'status' );

		settings.appendChild( field );
		settings.appendChild( size );

		const bar = Nino.adminUi.saveBar( {
			id			: 'themes-css-bar',
			restore	: false,
			onSave	: function( done ) {

				save( false, function( ok ) {

					done( ok );

					// Save is off while the save is under way: where it ended without a write, Save - on again - has the focus
					if( ok !== true && ( dc.activeElement === null || dc.activeElement === dc.body ) && bar.save.disabled === false )
						bar.save.focus();
				} );
			},
		} );

		const tools = Nino.adminUi.previewTools( {
			pages		: data.pages.map( function( page ) { return { value : page.path, label : page.title === '' ? page.path : page.title+ ' ('+ page.path+ ')' } } ),
			page		: C.page,
			width		: 'desktop',
			onPage	: function( path ) {
				C.page = path;
				C.loop.reload();
			},
			onWidth	: function( name ) { C.loop.width( name ) },
			reload	: false,
		} );

		const split = Nino.adminUi.split( { id : 'themes-css-split', settings : settings, tools : tools.el, preview : { src : '', title : word('preview/title') } } );

		// The loop that keeps the preview in step with the text: it is sent once the hand has rested, and what comes back says what is wrong with the braces
		C.loop = Nino.adminUi.previewFrame( {
			split		: split,
			mark		: 'loose',
			send		: function( token, done ) { call( 'css-preview', { css : C.css, token : token }, done ) },
			address	: addressOf,
			onError	: function( status, response ) {
				bar.line.error( status, response, '/_admin/themes/css/error/preview' );
			},
			onUpdate	: function( response ) {
				C.faults = Array.isArray( response.faults ) === true ? response.faults : [];
				paint();
			},
		} );

		C.view = { level : level, field : field, size : size, bar : bar, split : split, tools : tools };

		// The shell asks the bar about unsaved text and presses its Save for the keyboard
		if( typeof Nino.admin.dirty === 'object' )
			Nino.admin.dirty.register( ENTRY, bar.entry( { discard : discard } ) );

		level.appendChild( split.el );
		level.appendChild( bar.el );
		mount.appendChild( level );
		paint();
		C.loop.flush();
	};

	/**
	 *	The screen of the tab: the mount is made empty, then drawn from what the API says. Asked for every time the tab is shown - but not over a text
	 *	that is typed and not saved: that stays
	 *
	 *	@return		{void}
	 */
	const show = function() {

		const mount = dc.getElementById( MOUNT );

		if( mount === null )
			return;

		// The pane keeps its tabs in memory, and a move from one tab to another asks nothing: a text that is typed and not saved is still on the screen when
		// the tab is shown again, and is not drawn anew from the file over it
		if( C.view !== null && C.view.level.parentNode === mount && C.css !== C.saved )
			return;

		C.run++;

		const run = C.run;

		if( C.loop !== null )
			C.loop.stop();

		C.loop = null;
		C.view = null;
		mount.innerHTML = '';

		call( 'css', {}, function( status, response ) {

			// The tab was shown once more while the request was out: the later answer is the screen
			if( run !== C.run )
				return;

			if( status !== 200 || response === null || typeof response.css !== 'string' ) {
				Nino.adminUi.showError( mount, status, response, '/_admin/themes/css/error/list' );
				return;
			}

			build( mount, response );
		} );
	};

	// The tab, as the workbench asks for it: by its uri
	wn.Nino.admin['themes-css'] = {
		showCurrent : show,
	};

} )(window,document);
