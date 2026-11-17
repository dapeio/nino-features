/**
 *	Nino features
 *	Themes						The tab "Fonts" of the pane of the themes (#themes-fonts, see Modules\Themes\Typefaces): the families that stand in
 *										public/fonts/ with their licence, the place the brand uses them and a button that deletes one, and the signed list
 *										of the repository - loaded when the button is pressed and never on its own - to install a family from.
 *
 *										The two settings of the repository, its address and its public key, are no part of this file: they are settings of
 *										the manifest, and their form is the tab "Settings" the workbench gives the pane. Where the repository cannot be
 *										asked, the tab says why and leads there.
 *
 *										Everything it shows is the answer of the API. A change - a load, an install, a delete - is one request, and what
 *										it answers is what the screen is drawn from again. The brand picks its font from the list of the fonts of the
 *										panel of the themes: an install or a delete puts the answer's list there, so that the brand is offered what is in
 *										public/fonts/ at once.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The mount of the tab, which the workbench renders from the registry
	const MOUNT = 'themes-fonts-list';

	// How many families a page of the list shows
	const PAGE = 12;

	// The categories of the list, in the order the select has them
	const CATEGORIES = [ 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ];

	// What the screen holds: the answer it is drawn from, the search and the page of the list, the licences that were asked for and which are open,
	// and which draw it is - of two requests for the screen the answer of the later one is drawn
	const F = {
		data		: null,
		query		: '',
		category	: 'all',
		page		: 0,
		licenses	: {},
		open		: {},
		run			: 0,
	};

	/**
	 *	A word of the view in the language of the interface: a fill /_admin/themes/fonts/<key>
	 *
	 *	@param		{string}		key
	 *	@param		{...*}			params			What goes where the text has %s, %d or %n
	 *
	 *	@return		{string}
	 */
	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/themes/fonts/'+ key ), ...params );
	};

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	/**
	 *	One action of the API of the fonts
	 *
	 *	@param		{string}		action			'fonts', 'fonts-load', 'fonts-install', 'fonts-remove' or 'fonts-license'
	 *	@param		{Object}		payload
	 *	@param		{Function}	done				( status, response )
	 *
	 *	@return		{void}
	 */
	const call = function( action, payload, done ) {
		Nino.adminUi.api.call( 'themes/'+ action, payload, done );
	};

	/**
	 *	Where the focus goes when an action is done: the one way of the panel of the themes (its land()), which this tab is a part of
	 *
	 *	@param		{Element|null}		preferred			What the person pressed, or null
	 *	@param		{Element|null}		fallback			Where the focus goes where that is gone
	 *
	 *	@return		{void}
	 */
	const land = function( preferred, fallback ) {
		wn.Nino.admin.themes.land( preferred, fallback );
	};

	// The language the page is in, as the tag of a browser says it, for a date and a number
	const lang = function() {
		return String( ( dc.documentElement && dc.documentElement.lang ) || '' ).replace( '_', '-' );
	};

	/**
	 *	A size in bytes as a person reads it: kilobytes below a megabyte, else megabytes with one decimal
	 *
	 *	@param		{number}		bytes
	 *
	 *	@return		{string}
	 */
	const sizeOf = function( bytes ) {

		const tag = lang() === '' ? undefined : lang();

		if( bytes < 1024 * 1024 )
			return Math.max( 1, Math.round( bytes / 1024 ) ).toLocaleString( tag )+ ' KB';

		return ( Math.round( bytes / 1024 / 1024 * 10 ) / 10 ).toLocaleString( tag )+ ' MB';
	};

	/**
	 *	A weight as it is said: 400, or "variable 100–900" for a file that carries the weight axis
	 *
	 *	@param		{number|string}		weight
	 *
	 *	@return		{string}
	 */
	const weightOf = function( weight ) {
		return typeof weight === 'string' ? word( 'installed/variable', weight.replace( ' ', '–' ) ) : String( weight );
	};

	/**
	 *	The faces of an installed family: "400, 700, 400 italic"
	 *
	 *	@param		{Array}		faces				[ [ weight, style, file ], ... ]
	 *
	 *	@return		{string}
	 */
	const facesOf = function( faces ) {
		return faces.map( function( face ) { return weightOf( face[0] )+ ( face[1] === 'italic' ? ' '+ word('installed/italic') : '' ) } ).join(', ');
	};

	/**
	 *	A line in the view: what was done, or why it was not. Polite, for a screen reader
	 *
	 *	@param		{string}		text
	 *	@param		{boolean}		[failed]
	 *
	 *	@return		{void}
	 */
	const say = function( text, failed ) {

		const line = dc.getElementById('themes-fonts-say');

		if( line === null )
			return;

		line.textContent = text;
		line.classList.toggle( 'nino-admin-error', failed === true );
	};

	/**
	 *	The fonts of the list of the themes - the one the brand picks from - are what the answer says, and where they are others than before, the
	 *	settings of a theme that is open on the other tab are drawn again: the choice of the brand has what is in public/fonts/
	 *
	 *	@param		{Object}		response
	 *
	 *	@return		{void}
	 */
	const adopt = function( response ) {

		F.data = response;

		// Null until the list of the themes was shown, which asks for the fonts itself then
		const themes = wn.Nino.admin.themes;
		const list = typeof themes === 'object' && themes !== null ? themes.data : null;

		if( list === null || typeof list !== 'object' || Array.isArray( list.fonts ) === false || Array.isArray( response.fonts ) === false )
			return;

		const keys = function( fonts ) { return fonts.map( function( font ) { return font.key } ).join(',') };
		const changed = keys( list.fonts ) !== keys( response.fonts );

		list.fonts.splice( 0, list.fonts.length, ...response.fonts );

		if( changed === true )
			themes.redraw();
	};

	/**
	 *	The sentence of a repository that cannot be asked, or '' where it can
	 *
	 *	@param		{string}		state				One of the states the API names
	 *
	 *	@return		{string}
	 */
	const stateSaid = function( state ) {
		return state === 'ready' ? '' : word( 'available/'+ state );
	};

	/**
	 *	The link to the settings of the feature, where the workbench has them for the account: the Settings tab of the pane
	 *
	 *	@param		{string}		text
	 *
	 *	@return		{Element}
	 */
	const settingsLink = function( text ) {

		const link = el( 'a', 'nino-admin-btn-primary themes-fonts-link-button', text );

		link.href = '#themes-settings';

		return link;
	};

	// --- the installed ----------------------------------------------------------------------------------------------

	/**
	 *	One installed family as a card: its name, what it is, the faces it has, where the brand uses it, its licence and the button that deletes it
	 *
	 *	@param		{Object}		font
	 *
	 *	@return		{Element}
	 */
	const cardOf = function( font ) {

		const card = el( 'div', 'nino-admin-card themes-font' );
		const head = el( 'div', 'themes-font-head' );
		const title = el( 'h4', '', font.name );
		const used = font.used.length > 0;

		card.dataset.font = font.key;
		title.id = 'themes-font-title-'+ font.key;
		title.title = font.name;
		title.tabIndex = -1;
		head.appendChild( title );

		const chip = function( text, state ) {
			const badge = el( 'span', 'nino-admin-badge', text );
			badge.dataset.state = state;
			head.appendChild( badge );
		};

		if( font.made === true )
			chip( word( 'installed/version', font.version ), 'muted' );
		else
			chip( word('installed/own'), 'muted' );

		if( font.category !== '' )
			chip( word( 'category/'+ font.category ), 'muted' );

		if( font.license !== '' )
			chip( font.license, 'muted' );

		card.appendChild( head );
		card.appendChild( el( 'p', 'nino-admin-hint', word( 'installed/faces', facesOf( font.faces ) )+ ' · '+ sizeOf( font.size ) ) );

		const where = el( 'p', 'nino-admin-hint', used === true ? word( 'installed/used', font.used.map( function( key ) { return word( 'role/'+ key ) } ).join(', ') ) : word('installed/unused') );

		where.id = 'themes-font-used-'+ font.key;
		card.appendChild( where );

		if( font.made === false )
			card.appendChild( el( 'p', 'nino-admin-hint', word('installed/own-note') ) );

		const actions = el( 'div', 'nino-admin-actions' );
		const license = Nino.adminUi.button( word('installed/license-show'), function() { toggleLicense( font, license, holder ) }, { className : 'nino-admin-btn--small' } );
		const holder = el( 'div', 'themes-fonts-license' );

		license.setAttribute( 'aria-expanded', F.open[font.key] === true ? 'true' : 'false' );
		license.setAttribute( 'aria-controls', 'themes-license-'+ font.key );
		holder.id = 'themes-license-'+ font.key;
		license.disabled = font.licensed === false;

		if( font.licensed === false )
			license.title = word('installed/license-none');

		actions.appendChild( license );

		if( font.source !== '' ) {

			const source = el( 'a', 'themes-fonts-link', word('installed/source') );

			source.href = font.source;
			source.rel = 'noopener noreferrer';
			source.target = '_blank';
			actions.appendChild( source );
		}

		// What was put there by hand is not deleted from here (see Typefaces::remove()): the card says so, and has no button for it
		if( font.made === true ) {

			const remove = Nino.adminUi.button( word('installed/remove'), function() { removeFont( font, remove ) }, { className : 'nino-admin-btn--small nino-admin-btn-danger', disabled : used } );

			// A button that cannot be pressed says why in the text beside it - the line of the brand's use - and for a screen reader here
			if( used === true )
				remove.setAttribute( 'aria-describedby', where.id );

			actions.appendChild( remove );
		}
		card.appendChild( actions );
		card.appendChild( holder );

		if( F.open[font.key] === true )
			showLicense( font, license, holder );

		if( used === true )
			card.appendChild( el( 'p', 'nino-admin-hint', word('installed/used-note') ) );

		return card;
	};

	/**
	 *	The licence of a family drawn into its holder, as text: asked for once and kept for as long as the view lives
	 *
	 *	@param		{Object}		font
	 *	@param		{Element}		button
	 *	@param		{Element}		holder
	 *
	 *	@return		{void}
	 */
	const showLicense = function( font, button, holder ) {

		const draw = function( text ) {

			holder.innerHTML = '';

			const pre = el( 'pre', 'themes-fonts-license-text', text );

			pre.tabIndex = 0;
			pre.setAttribute( 'role', 'region' );
			pre.setAttribute( 'aria-label', word( 'available/license', font.name ) );
			holder.appendChild( pre );
			button.textContent = word('installed/license-hide');
			button.setAttribute( 'aria-expanded', 'true' );
		};

		if( typeof F.licenses[font.key] === 'string' ) {
			draw( F.licenses[font.key] );
			return;
		}

		call( 'fonts-license', { slug : font.key }, function( status, response ) {

			if( status !== 200 || response === null || typeof response.text !== 'string' ) {
				F.open[font.key] = false;
				say( Nino.adminUi.api.errorText( status, response, '/_admin/themes/fonts/error/license' ), true );
				return;
			}

			F.licenses[font.key] = response.text;
			draw( response.text );
		} );
	};

	const toggleLicense = function( font, button, holder ) {

		if( F.open[font.key] === true ) {
			F.open[font.key] = false;
			holder.innerHTML = '';
			button.textContent = word('installed/license-show');
			button.setAttribute( 'aria-expanded', 'false' );
			return;
		}

		F.open[font.key] = true;
		showLicense( font, button, holder );
	};

	/**
	 *	Delete a family, after a question: one request, and the screen is drawn again from its answer
	 *
	 *	@param		{Object}		font
	 *	@param		{Element}		opener
	 *
	 *	@return		{void}
	 */
	const removeFont = function( font, opener ) {

		Nino.adminUi.confirm( 'destructive', {
			title		: word( 'installed/remove-title', font.name ),
			message		: word( 'installed/remove-message', font.name ),
			yes			: word('installed/remove'),
			no			: Nino.content.getText('/_admin/common/label/cancel'),
			kind		: 'danger',
			visitors	: false,
			opener		: opener,
			onChoose	: function( ok ) {

				if( ok !== true )
					return;

				opener.disabled = true;

				call( 'fonts-remove', { slug : font.key }, function( status, response ) {

					if( status !== 200 || response === null || typeof response.result !== 'object' ) {
						opener.disabled = font.used.length > 0;
						say( Nino.adminUi.api.errorText( status, response, '/_admin/themes/fonts/error/remove' ), true );
						land( opener, dc.getElementById('themes-fonts-installed-title') );
						return;
					}

					delete F.licenses[font.key];
					delete F.open[font.key];
					adopt( response );
					draw();
					say( word( 'msg/removed', font.name ) );
					land( null, dc.getElementById('themes-fonts-installed-title') );
				} );
			},
		} );
	};

	/**
	 *	The half of the installed families
	 *
	 *	@return		{Element}
	 */
	const installedHalf = function() {

		const half = el( 'section', 'themes-fonts-half' );
		const title = el( 'h3', '', word('installed/title') );

		title.id = 'themes-fonts-installed-title';
		title.tabIndex = -1;
		half.setAttribute( 'aria-labelledby', title.id );
		half.appendChild( title );

		if( F.data.installed.length === 0 ) {
			half.appendChild( Nino.adminUi.emptyState( word('installed/empty') ) );
			return half;
		}

		const wrap = el( 'div', 'themes-fonts-cards' );

		F.data.installed.slice().sort( function( a, b ) { return a.name.localeCompare( b.name ) } ).forEach( function( font ) { wrap.appendChild( cardOf( font ) ) } );
		half.appendChild( wrap );

		return half;
	};

	// --- the available ----------------------------------------------------------------------------------------------

	/**
	 *	Load the list: the one request the repository gets until an install, asked for by the button and by nothing else
	 *
	 *	@param		{Element}		opener
	 *
	 *	@return		{void}
	 */
	const load = function( opener ) {

		opener.disabled = true;
		say( word('msg/loading') );

		call( 'fonts-load', {}, function( status, response ) {

			if( status !== 200 || response === null || typeof response.installed !== 'object' ) {

				opener.disabled = false;
				say( Nino.adminUi.api.errorText( status, response, '/_admin/themes/fonts/error/load' ), true );
				land( opener, dc.getElementById('themes-fonts-available-title') );
				return;
			}

			F.page = 0;
			adopt( response );
			draw();
			say( word( 'msg/loaded', response.loaded ) );

			// What there is to do next is to look for a font
			land( dc.getElementById('themes-fonts-search'), dc.getElementById('themes-fonts-available-title') );
		} );
	};

	/**
	 *	Install a family - or update the one that is there: one request, and the screen is drawn again from its answer
	 *
	 *	@param		{Object}		entry				A family of the list
	 *	@param		{Element}		opener
	 *
	 *	@return		{void}
	 */
	const install = function( entry, opener ) {

		opener.disabled = true;
		say( word( 'msg/installing', entry.family ) );

		call( 'fonts-install', { slug : entry.slug }, function( status, response ) {

			if( status !== 200 || response === null || typeof response.result !== 'object' ) {

				opener.disabled = false;
				say( Nino.adminUi.api.errorText( status, response, '/_admin/themes/fonts/error/install' ), true );
				land( opener, dc.getElementById('themes-fonts-available-title') );
				return;
			}

			delete F.licenses[entry.slug];
			adopt( response );
			draw();
			// A family the brand uses was built into the stylesheet of the website with the faces it had: the list of the themes offers to build it once more
			const now = response.installed.find( function( font ) { return font.key === entry.slug } );

			say( response.result.updated !== true ? word( 'msg/installed', entry.family ) : word( now !== undefined && now.used.length > 0 ? 'msg/updated-used' : 'msg/updated', entry.family ) );

			// The result is the card of the family: that is where a person looks for it
			land( dc.getElementById( 'themes-font-title-'+ entry.slug ), dc.getElementById('themes-fonts-installed-title') );
		} );
	};

	/**
	 *	The families of the list that match the search and the category, by name
	 *
	 *	@return		{Array}
	 */
	const matching = function() {

		const query = F.query.trim().toLocaleLowerCase();

		return F.data.available.families.filter( function( entry ) {
			return ( F.category === 'all' || entry.category === F.category ) && ( query === '' || entry.family.toLocaleLowerCase().indexOf( query ) !== -1 || entry.slug.indexOf( query ) !== -1 );
		} ).sort( function( a, b ) { return a.family.localeCompare( b.family ) } );
	};

	/**
	 *	One family of the list as a row: what it is and the button that installs it - or what stands in the way
	 *
	 *	@param		{Object}		entry
	 *
	 *	@return		{Element}
	 */
	const rowOf = function( entry ) {

		const row = el( 'li', 'themes-fonts-row' );
		const text = el( 'div', 'themes-fonts-row-text' );
		const name = el( 'strong', '', entry.family );
		const weights = entry.weights.map( weightOf ).join(', ')+ ( entry.styles.indexOf('italic') !== -1 ? ' '+ word('installed/italic') : '' );

		row.dataset.slug = entry.slug;
		text.appendChild( name );
		text.appendChild( el( 'span', 'nino-admin-hint', word( 'category/'+ entry.category )+ ' · '+ word( 'available/license', entry.license )+ ' · '+ word( 'installed/version', entry.version )+ ' · '+ weights+ ' · '+ word( 'available/size', sizeOf( entry.size ) ) ) );

		if( entry.problem !== '' )
			text.appendChild( el( 'span', 'nino-admin-hint nino-admin-error', word( 'available/problem-'+ entry.problem ) ) );
		else if( entry.foreign === true )
			text.appendChild( el( 'span', 'nino-admin-hint nino-admin-error', word('available/foreign') ) );

		row.appendChild( text );

		if( entry.problem !== '' || entry.foreign === true )
			return row;

		const actions = el( 'div', 'themes-fonts-row-actions' );

		if( entry.installed !== null && entry.installed === entry.version ) {
			const done = el( 'span', 'nino-admin-badge', word('available/installed') );

			done.dataset.state = 'ok';
			actions.appendChild( done );
		}
		else {
			const button = Nino.adminUi.button( entry.installed === null ? word('available/install') : word( 'available/update', entry.version ), function() { install( entry, button ) }, { className : 'nino-admin-btn--small', disabled : F.data.available.stale === true } );

			// A list that is too old installs nothing: the sentence above the list says to load it once more
			button.setAttribute( 'aria-label', button.textContent+ ': '+ entry.family );
			actions.appendChild( button );
		}

		row.appendChild( actions );

		return row;
	};

	/**
	 *	The search, the category and the page of the list of families, and the pager - drawn again in place, so that what is typed keeps its focus
	 *
	 *	@param		{Element}		list				Where the rows stand
	 *	@param		{Element}		pager				Where the pager stands
	 *	@param		{Element}		count				The line that says how many match
	 *
	 *	@return		{void}
	 */
	const rows = function( list, pager, count ) {

		const found = matching();
		const pages = Math.max( 1, Math.ceil( found.length / PAGE ) );

		F.page = Math.min( F.page, pages - 1 );
		list.innerHTML = '';
		pager.innerHTML = '';
		count.textContent = word( 'available/count', found.length );

		found.slice( F.page * PAGE, ( F.page + 1 ) * PAGE ).forEach( function( entry ) { list.appendChild( rowOf( entry ) ) } );

		if( found.length === 0 )
			list.appendChild( el( 'li', 'nino-admin-empty', F.data.available.families.length === 0 ? word('available/none') : word('available/nomatch') ) );

		if( pages < 2 )
			return;

		const step = function( by, text ) {

			// The focus stays on the direction that was pressed where it can be pressed again, else it goes to the other, else to the line that counts
			return Nino.adminUi.button( text, function() {
				F.page += by;
				rows( list, pager, count );

				const steps = Array.prototype.slice.call( pager.querySelectorAll('[data-step]') ).filter( function( button ) { return button.disabled !== true } );

				land( steps.find( function( button ) { return button.dataset.step === String( by ) } ) ?? steps[0] ?? null, count );
			}, { className : 'nino-admin-btn--small', disabled : by < 0 ? F.page === 0 : F.page >= pages - 1 } );
		};
		const before = step( -1, word('page/previous') );
		const after = step( 1, word('page/next') );

		before.dataset.step = '-1';
		after.dataset.step = '1';
		pager.appendChild( before );
		pager.appendChild( el( 'span', 'nino-admin-hint', word( 'page/of', F.page + 1, pages ) ) );
		pager.appendChild( after );
	};

	/**
	 *	The half of the repository: its state, the button that loads the list and what the list holds
	 *
	 *	@return		{Element}
	 */
	const availableHalf = function() {

		const half = el( 'section', 'themes-fonts-half' );
		const title = el( 'h3', '', word('available/title') );
		const repository = F.data.repository;

		title.id = 'themes-fonts-available-title';
		title.tabIndex = -1;
		half.setAttribute( 'aria-labelledby', title.id );
		half.appendChild( title );

		if( repository.state !== 'ready' ) {

			const notice = Nino.adminUi.notice( stateSaid( repository.state ) );

			notice.id = 'themes-fonts-off';
			half.appendChild( notice );

			if( repository.settings === true )
				half.appendChild( el( 'div', 'nino-admin-actions' ) ).appendChild( settingsLink( word('available/settings') ) );

			return half;
		}

		const bar = el( 'div', 'nino-admin-actions themes-fonts-bar' );
		const available = F.data.available;
		const button = Nino.adminUi.button( available === null ? word('available/load') : word('available/reload'), function() { load( button ) }, { className : 'nino-admin-btn-primary nino-admin-btn--small', id : 'themes-fonts-load' } );

		bar.appendChild( button );

		if( available !== null )
			bar.appendChild( el( 'span', 'nino-admin-hint', word( 'available/loaded', new Date( available.fetched * 1000 ).toLocaleString( lang() === '' ? undefined : lang(), { dateStyle : 'medium', timeStyle : 'short' } ) ) ) );

		half.appendChild( bar );

		// Which host the button asks, and whether its list is checked with a key of the project's own
		half.appendChild( el( 'p', 'nino-admin-hint', word( repository.ownKey === true ? 'available/from-own' : 'available/from', repository.host ) ) );

		if( available === null ) {
			half.appendChild( el( 'p', 'nino-admin-hint', word('available/unloaded') ) );
			return half;
		}

		if( available.stale === true )
			half.appendChild( Nino.adminUi.notice( word('available/stale') ) );

		const tools = el( 'div', 'themes-fonts-tools' );
		const search = Nino.adminUi.textField( { label : word('available/search'), type : 'search', value : F.query, id : 'themes-fonts-search', autocomplete : 'off', maxLength : 60, onInput : function( value ) { F.query = value; F.page = 0; rows( list, pager, count ) } } );
		const select = Nino.adminUi.selectField( { label : word('available/category'), value : F.category, options : [ { value : 'all', label : word('category/all') } ].concat( CATEGORIES.map( function( category ) { return { value : category, label : word( 'category/'+ category ) } } ) ), onChange : function( value ) { F.category = value; F.page = 0; rows( list, pager, count ) } } );
		const count = el( 'p', 'nino-admin-hint' );
		const list = el( 'ul', 'themes-fonts-rows' );
		const pager = el( 'div', 'themes-fonts-pager' );

		count.setAttribute( 'role', 'status' );
		tools.appendChild( search );
		tools.appendChild( select );
		half.appendChild( tools );
		half.appendChild( count );
		half.appendChild( list );
		half.appendChild( pager );
		rows( list, pager, count );

		return half;
	};

	/**
	 *	The screen of the fonts, drawn from the answer it holds - the line that says what was done stays, as the box the screen is in does
	 *
	 *	@return		{void}
	 */
	const draw = function() {

		const said = dc.getElementById('themes-fonts-say');
		const keep = said === null ? '' : said.textContent;
		const failed = said !== null && said.classList.contains('nino-admin-error');
		const content = dc.getElementById('themes-fonts-box');

		if( content === null )
			return;

		content.innerHTML = '';
		content.appendChild( installedHalf() );
		content.appendChild( availableHalf() );

		if( keep !== '' )
			say( keep, failed );
	};

	/**
	 *	The screen of the tab: the mount is made empty with its line, then drawn from what the API says. Asked for every time the tab is shown: where
	 *	the brand uses a family may have changed on the tab of the themes
	 *
	 *	@return		{void}
	 */
	const show = function() {

		const mount = dc.getElementById( MOUNT );

		if( mount === null )
			return;

		F.run++;

		const run = F.run;
		const line = el( 'p', 'nino-admin-hint themes-fonts-say', '' );
		const content = el( 'div', '' );

		line.id = 'themes-fonts-say';
		line.setAttribute( 'role', 'status' );
		content.id = 'themes-fonts-box';
		mount.innerHTML = '';
		mount.appendChild( line );
		mount.appendChild( content );

		call( 'fonts', {}, function( status, response ) {

			// The tab was shown once more while the request was out: the later answer is the screen
			if( run !== F.run )
				return;

			if( status !== 200 || response === null || typeof response.installed !== 'object' ) {
				Nino.adminUi.showError( content, status, response, '/_admin/themes/fonts/error/list' );
				return;
			}

			adopt( response );
			draw();
		} );
	};

	// The tab, as the workbench asks for it: by its uri
	wn.Nino.admin['themes-fonts'] = {
		showCurrent : show,
	};

} )(window,document);
