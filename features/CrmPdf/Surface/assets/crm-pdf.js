/**
 *	CrmPdf		The screens of the PDF feature inside the CRM, drawn on the registration points of the surface: one page, #pdf, with the templates (#pdf), the text blocks (#pdf-blocks, #pdf-block-<key>,
 *						#pdf-new-block), the layout (#pdf-layout), the design (#pdf-design) and the editor of a template (#pdf-template-<key>, #pdf-new-template, #pdf-new-template-<tag>), one entry of the rail and the
 *						state of the package. Everything comes from the fifteen actions crm-pdf/... of the endpoint and is drawn from data, never from markup. The screens ask the account's right of the page (the data
 *						block of the package) and say so where it is missing, without a request; the actions ask it again.
 */

( function( wn, dc ) {

	'use strict';

	const crm = wn.Nino.crm;
	const kit = crm.kit.make( 'crm-pdf' );
	const { el, t, has, api, call, toast, link, note, menu, head, crumb, search, badge, dialog, ask, dmy, clear, append, sortHead, keepSearch, cardList, formOf, formList, refuseAt, failed, errorText, saver, submitter, reveal, loading, loadFailed, data } = kit;

	// What the screens remember: the search and the sort of the list of templates
	const own = crm.state.own( 'crm-pdf', { q : '', sort : 'name', dir : 1 } );

	// The kinds of block the editor knows, and the codes of the save of a template that name a block, by the number in their params; the others belong to the template
	const BLOCK_CODES = [ 'pdf_block_type', 'pdf_block_html', 'pdf_block_ref', 'pdf_block_name', 'pdf_block_label', 'pdf_signature_party', 'pdf_signature_page', 'pdf_signature_box' ];

	// The page numbers, which a header and a footer know
	const PAGE_TOKENS = [ 'page', 'pages' ];

	// The tabs of the screens, in order: the key and the hash
	const TABS = [ [ 'templates', '#pdf' ], [ 'blocks', '#pdf-blocks' ], [ 'layout', '#pdf-layout' ], [ 'design', '#pdf-design' ] ];

	// A key of a template or of a block, as the feature writes it
	const KEY = '([a-z][a-z0-9-]{1,39})';

	// The most a picture may weigh before it is read and sent: 4 MB, the number the server holds a request for it to (CrmPdf::PICTURE_MAX)
	const PICTURE_MAX = 4 * 1048576;

	/**
	 *	@param		{string}	address					An address of the page without the hash
	 *
	 *	@return		{Object|null}							The screen the address names, and what it says; null where it is none of the package's
	 */
	const parse = function( address ) {

		const fixed = { 'pdf' : 'templates', 'pdf-blocks' : 'blocks', 'pdf-layout' : 'layout', 'pdf-design' : 'design' };

		if( Object.prototype.hasOwnProperty.call( fixed, address ) === true )
			return { screen : fixed[address] };

		if( address === 'pdf-new-block' )
			return { screen : 'block' };

		if( address === 'pdf-new-template' )
			return { screen : 'template', tag : '' };

		let found = new RegExp( '^pdf-template-'+ KEY+ '$' ).exec( address );

		if( found !== null )
			return { screen : 'template', key : found[1] };

		found = new RegExp( '^pdf-new-template-'+ KEY+ '$' ).exec( address );

		if( found !== null )
			return { screen : 'template', tag : found[1] };

		found = new RegExp( '^pdf-block-'+ KEY+ '$' ).exec( address );

		return found === null ? null : { screen : 'block', key : found[1] };
	};

	/**
	 *	@return		{boolean}									Whether the account holds the permission of the PDF panel, as the page says
	 */
	const allowed = function() {

		const mine = data( 'crm-pdf' );

		return mine !== null && mine.allowed === true;
	};

	/**
	 *	The screens need the permission of the PDF panel: without it the screen says so, and no request is made
	 *
	 *	@param		{Object}	inner						The kit of the drawing
	 *
	 *	@return		{boolean}
	 */
	const permitted = function( inner ) {

		if( allowed() === true )
			return true;

		reveal();
		inner.main.appendChild( head( t( 'pdf.title' ) ) );
		inner.main.appendChild( note( 'bad', t( 'pdf.forbidden' ) ) );

		return false;
	};

	/**
	 *	@param		{string}	tag
	 *
	 *	@return		{string}									The word of a tag of the feature; the tag itself where the surface has no word for it, and the word for none
	 */
	const tagWord = function( tag ) {

		if( tag === '' )
			return t( 'pdf.tag-none' );

		return has( 'pdf.tagword-'+ tag ) === true ? t( 'pdf.tagword-'+ tag ) : tag;
	};

	/**
	 *	@param		{string}	path						/project/company/general/name
	 *
	 *	@return		{string}									The word of a text of the site, else the key without the company
	 */
	const siteWord = function( path ) {

		const rest = path.split( '/' ).slice( 3 ).join( '-' );

		return has( 'pdf.site-'+ rest ) === true ? t( 'pdf.site-'+ rest ) : path.split( '/' ).slice( 3 ).join( '/' );
	};

	/**
	 *	The placeholders as the menu and the legend group them: what the callers of the feature offer, the texts of the site, and the page numbers where the text is a header or a footer
	 *
	 *	@param		{Object}	context					The answer of crm-pdf/context
	 *	@param		{boolean}	band						Whether the page numbers belong to them
	 *
	 *	@return		{Array}										[ { title, tokens: [ { token, label } ] } ]
	 */
	const tokenGroups = function( context, band ) {

		const groups = context.groups.map( function( group ) { return { title : group.label, tokens : group.tokens.map( function( one ) { return { token : one.token, label : one.label } } ) } } );

		if( context.site.length > 0 )
			groups.push( { title : t( 'pdf.group-site' ), tokens : context.site.map( function( path ) { return { token : path, label : siteWord( path ) } } ) } );

		if( band === true )
			groups.push( { title : t( 'pdf.group-page' ), tokens : PAGE_TOKENS.map( function( name ) { return { token : name, label : t( 'pdf.tok-'+ name ) } } ) } );

		return groups;
	};

	/**
	 *	Put a text into a field where the caret is, in place of what is selected, and leave the caret behind it
	 *
	 *	@param		{Object}	area
	 *	@param		{string}	text
	 *
	 *	@return		{void}
	 */
	const insertAt = function( area, text ) {

		const from = typeof area.selectionStart === 'number' ? area.selectionStart : area.value.length;
		const to = typeof area.selectionEnd === 'number' ? area.selectionEnd : from;
		const at = from+ text.length;

		area.value = area.value.slice( 0, from )+ text+ area.value.slice( to );
		area.focus();
		area.selectionStart = at;
		area.selectionEnd = at;
	};

	/**
	 *	The menu of the placeholders of a text: what the callers offer by group, then the texts of the site; a choice goes into the text where the caret is
	 *
	 *	@param		{Object}	context
	 *	@param		{Object}	area						The textarea
	 *	@param		{boolean}	band						Whether it is a header or a footer
	 *	@param		{Function}	changed					Called after an insert, as the text was typed
	 *
	 *	@return		{Object}
	 */
	const placeholderMenu = function( context, area, band, changed ) {

		const items = [];

		tokenGroups( context, band ).forEach( function( group ) {

			items.push( { heading : group.title } );
			group.tokens.forEach( function( one ) {
				items.push( { label : one.label+ '  [['+ one.token+ ']]', run : function() {
					insertAt( area, '[['+ one.token+ ']]' );
					changed();
				} } );
			} );
		} );

		return menu( { label : t( 'pdf.placeholders' ), aria : t( 'pdf.placeholders-aria' ), button : 'sm', tall : true, items : items } );
	};

	/**
	 *	A text of the feature with the menu of the placeholders under it
	 *
	 *	@param		{Object}	context
	 *	@param		{Object}	area
	 *	@param		{boolean}	band
	 *	@param		{Function}	changed
	 *	@param		{Object}	[hint]
	 *
	 *	@return		{Object}
	 */
	const withPlaceholders = function( context, area, band, changed, hint ) {
		return el( 'div', { class : 'textbox' }, area, el( 'div', { class : 'tools' }, placeholderMenu( context, area, band, changed ), hint === undefined ? null : hint ) );
	};

	/**
	 *	The tabs of the screens
	 *
	 *	@param		{string}	active					templates, blocks, layout or design
	 *
	 *	@return		{Object}
	 */
	const tabs = function( active ) {

		return el( 'nav', { class : 'tabs top', id : 'pdf-tabs', 'aria-label' : t( 'pdf.tabs' ) }, TABS.map( function( tab ) {
			return el( 'a', { href : tab[1], class : tab[0] === active ? 'on' : '', 'aria-current' : tab[0] === active ? 'page' : null, text : t( 'pdf.tab-'+ tab[0] ) } );
		} ) );
	};

	/**
	 *	Read the lists a screen starts from, and draw it when they all came; the context of the package is always read as the last
	 *
	 *	@param		{Object}	inner						The kit of the drawing
	 *	@param		{Array}		asks						[ [ action, data ] ... ]
	 *	@param		{Function}	draw						Called with the bodies of the answers and then the context
	 *	@param		{Function}	[missing]				Called with a refusal that says something is not there (404) instead of the sentence with a button to try again, which would not help
	 *
	 *	@return		{void}
	 */
	const read = function( inner, asks, draw, missing ) {

		loading();

		Promise.all( asks.concat( [ [ 'crm-pdf/context' ] ] ).map( function( ask ) { return api( ask[0], ask[1] ) } ) ).then( function( answers ) {

			if( inner.alive() === false )
				return;

			const failure = answers.find( function( answer ) { return answer.ok !== true } );

			if( failure !== undefined ) {

				if( failure.status === 404 && missing !== undefined )
					missing( failure );
				else
					loadFailed( failure, 'error.load', function() { inner.route() } );

				return;
			}

			draw( ...answers.map( function( answer ) { return answer.body } ) );
		} );
	};

	/**
	 *	Show a PDF the server made as bytes in an answer: a link that opens it and a link that saves it, in a dialog. The bytes live in the memory of the page and in an address it makes for them, which goes with the dialog;
	 *	the page's policy has no frame for them, and a new tab is the person's to open
	 *
	 *	@param		{Object}	body						pdf (base64) and file (the name it is saved under)
	 *
	 *	@return		{Promise}									Settled when the dialog is closed, or at once where there is nothing to show
	 */
	const showPdf = function( body ) {

		let url = '';

		try {
			const binary = wn.atob( String( body.pdf ) );
			const bytes = new Uint8Array( binary.length );

			for( let at = 0; at < binary.length; at++ )
				bytes[at] = binary.charCodeAt( at );

			url = wn.URL.createObjectURL( new wn.Blob( [ bytes ], { type : 'application/pdf' } ) );
		}
		catch( error ) {
			toast( t( 'pdf.preview-failed' ), true );
			return Promise.resolve();
		}

		return dialog( { title : t( 'pdf.preview' ), text : t( 'pdf.preview-ready' ), close : true, body : el( 'p', { class : 'actions' },
			el( 'a', { class : 'btn primary', id : 'preview-open', href : url, target : '_blank', rel : 'noopener', text : t( 'pdf.preview-open' ) } ),
			el( 'a', { class : 'btn', id : 'preview-save', href : url, download : String( body.file ).replace( /[^A-Za-z0-9._-]/g, '' ) || 'preview.pdf', text : t( 'pdf.preview-save' ) } ) ) } ).then( function() {
			wn.URL.revokeObjectURL( url );
		} );
	};

	/**
	 *	Ask the server for a preview and show it; the button is off while the request runs
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	button
	 *	@param		{string}	action					crm-pdf/preview or crm-pdf/layout-preview
	 *	@param		{Object}	[posted]
	 *	@param		{Function}	[refuse]				What to do with a refusal; it is a toast by default
	 *
	 *	@return		{void}
	 */
	const preview = function( inner, button, action, posted, refuse ) {

		button.disabled = true;

		api( action, posted ).then( function( answer ) {

			// The button had the focus when it was switched off, and a node that is off has none: it has it again before the dialog opens, and after it closes
			button.disabled = false;
			button.focus();

			if( inner.alive() === false )
				return;

			if( answer.ok !== true ) {

				if( refuse === undefined )
					failed( answer, 'pdf.error-preview' );
				else
					refuse( answer, 'pdf.error-preview' );

				return;
			}

			showPdf( answer.body ).then( function() { button.focus() } );
		} );
	};

	/**
	 *	@param		{string}	html
	 *
	 *	@return		{string}									The text of a piece of HTML, in one line and short
	 */
	const plainText = function( html ) {

		const text = String( html ).replace( /<[^>]*>/g, ' ' ).replace( /&nbsp;/g, ' ' ).replace( /\s+/g, ' ' ).trim();

		return text.length > 90 ? text.slice( 0, 90 )+ '…' : text;
	};

	// --- The templates: the list ---------------------------------------------------------------------------------------

	const SORTS = {
		name : function( one ) { return one.name.toLowerCase() },
		tag : function( one ) { return tagWord( one.tag ).toLowerCase() },
		changed : function( one ) { return one.changed },
		used : function( one ) { return one.used },
	};

	/**
	 *	The templates of the list the filter shows, in its order
	 *
	 *	@param		{Array}		rows						The templates with changed and used
	 *	@param		{Object}	filter					q, sort, dir
	 *
	 *	@return		{Array}
	 */
	const visible = function( rows, filter ) {

		const query = filter.q.trim().toLowerCase();
		const key = Object.prototype.hasOwnProperty.call( SORTS, filter.sort ) === true ? SORTS[filter.sort] : SORTS.name;

		return rows.filter( function( one ) {
			return query === '' || ( one.name+ ' '+ one.key+ ' '+ tagWord( one.tag ) ).toLowerCase().indexOf( query ) !== -1;
		} ).sort( function( a, b ) {

			const left = key( a );
			const right = key( b );
			const order = typeof left === 'string' ? left.localeCompare( right ) : ( left < right ? -1 : ( left > right ? 1 : 0 ) );

			return order === 0 ? ( a.key < b.key ? -1 : 1 ) : order * filter.dir;
		} );
	};

	/**
	 *	@param		{Object}	inner
	 *
	 *	@return		{void}
	 */
	const templatesScreen = function( inner ) {

		if( permitted( inner ) === false )
			return;

		read( inner, [ [ 'crm-pdf/templates-list' ] ], function( list, context ) {

			const rows = list.templates.map( function( one ) {
				return Object.assign( {}, one, { changed : context.changed[one.key] || '', used : context.used[one.key] || 0, by : context.by[one.key] || {}, unsure : context.unsure.indexOf( one.key ) !== -1 } );
			} );

			drawTemplates( inner, rows, list, context );
		} );
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Array}		rows
	 *	@param		{Object}	list						The answer of the list: tags, library
	 *	@param		{Object}	context
	 *
	 *	@return		{void}
	 */
	const drawTemplates = function( inner, rows, list, context ) {

		const filter = own;
		const shown = visible( rows, filter );
		const again = function() { drawTemplates( inner, rows, list, context ) };
		const heading = function( key, label, right ) { return sortHead( filter, key, label, right, again, key === 'changed' || key === 'used' ? -1 : 1 ) };
		const usedBy = function( one ) { return Object.keys( one.by ).map( function( name ) { return name+ ' ('+ one.by[name]+ ')' } ).join( ', ' ) };

		const body = el( 'tbody', {}, shown.length === 0
			? el( 'tr', { class : 'plain' }, el( 'td', { colspan : '4', class : 'muted', text : rows.length === 0 ? t( 'pdf.none' ) : t( 'pdf.nomatch' ) } ) )
			: shown.map( function( one ) {
				return el( 'tr', { dataset : { key : one.key }, on : { click : function() { wn.location.hash = '#pdf-template-'+ one.key } } },
					el( 'td', {}, el( 'b', {}, link( '#pdf-template-'+ one.key, one.name ) ), el( 'div', { class : 'muted small', text : one.key } ) ),
					el( 'td', {}, one.tag === '' ? el( 'span', { class : 'muted', text : '–' } ) : badge( 'open', tagWord( one.tag ) ), one.known === false ? [ ' ', badge( 'warn', t( 'pdf.tag-gone' ) ) ] : null ),
					el( 'td', { class : 'num', text : dmy( one.changed ) } ),
					el( 'td', { class : 'r num' },
						one.used > 0 ? String( one.used ) : el( 'span', { class : 'muted', text : '–' } ), one.unsure === true ? [ ' ', badge( 'warn', t( 'pdf.used-unsure' ) ) ] : null,
						one.used > 0 ? el( 'div', { class : 'muted small used-by', text : usedBy( one ) } ) : null ) );
			} ) );

		const table = el( 'div', { class : 'card wrap-x' }, el( 'table', { class : 'tbl', id : 'pdf-templates' },
			el( 'thead', {}, el( 'tr', {}, heading( 'name', t( 'pdf.col-name' ) ), heading( 'tag', t( 'pdf.col-tag' ) ), heading( 'changed', t( 'pdf.col-changed' ) ), heading( 'used', t( 'pdf.col-used' ), true ) ) ),
			body ) );

		const fresh = list.tags.length === 0
			? link( '#pdf-new-template', t( 'pdf.new-template' ), { class : 'btn primary', id : 'new-template' } )
			: menu( { label : t( 'pdf.new-template' )+ ' ▾', button : 'primary', aria : t( 'pdf.new-template' ), items : list.tags.map( function( tag ) {
				return { label : tagWord( tag ), run : function() { wn.location.hash = '#pdf-new-template-'+ tag } };
			} ).concat( [ 'sep', { label : t( 'pdf.tag-none' ), run : function() { wn.location.hash = '#pdf-new-template' } } ] ) } );

		reveal();

		inner.main.appendChild( head( t( 'pdf.title' ), el( 'span', { class : 'sub', text : t( 'pdf.count', rows.length, list.library.length ) } ), el( 'span', { class : 'grow' } ),
			search( t( 'pdf.search' ), filter.q, { input : function( event ) {
				filter.q = event.target.value;
				keepSearch( again, true );
			} } ), fresh ) );
		inner.main.appendChild( tabs( 'templates' ) );
		append( inner.main, [ cardList( table, 'pdf-templates', [ 'id', 'state', 'date:l', 'x-used:l' ] ), el( 'p', { class : 'state-note top', text : t( 'pdf.list-note' ) } ) ] );
	};

	// --- The editor of a template --------------------------------------------------------------------------------------

	/**
	 *	A number of millimetres as it is posted: a number where the text is one (a decimal comma is read as a point), else the text, which the store refuses
	 *
	 *	@param		{string}	text
	 *
	 *	@return		{number|string}
	 */
	const millimetres = function( text ) {

		const clean = String( text ).trim().replace( ',', '.' );

		return /^-?\d{1,4}(?:\.\d+)?$/.test( clean ) === true ? Number( clean ) : clean;
	};

	/**
	 *	A block of the store as the editor holds it: the numbers of a signature as the text of their fields, the page as the word the person chooses
	 *
	 *	@param		{Object}	block
	 *
	 *	@return		{Object}
	 */
	const blockDraft = function( block ) {

		if( block.type === 'html' )
			return block.block !== undefined ? { type : 'html', mode : 'ref', block : block.block, html : '' } : { type : 'html', mode : 'own', block : '', html : block.html ?? '' };

		if( block.type === 'slot' )
			return { type : 'slot', name : block.name };

		if( block.type === 'input' )
			return { type : 'input', name : block.name, label : block.label, multiline : block.multiline === true, required : block.required === true };

		if( block.type === 'signature' )
			return { type : 'signature', name : block.name, party : block.party, label : block.label, page : block.page === 'last' ? 'last' : ( block.page === 1 ? 'first' : 'number' ), number : block.page === 'last' ? '2' : String( block.page ),
				x : String( block.x ), y : String( block.y ), w : String( block.w ), h : String( block.h ), line : block.line === true };

		return { type : 'pagebreak' };
	};

	/**
	 *	A block of the editor as the store takes it
	 *
	 *	@param		{Object}	b
	 *
	 *	@return		{Object}
	 */
	const blockPayload = function( b ) {

		if( b.type === 'html' )
			return b.mode === 'ref' ? { type : 'html', block : b.block } : { type : 'html', html : b.html };

		if( b.type === 'slot' )
			return { type : 'slot', name : b.name };

		if( b.type === 'input' )
			return { type : 'input', name : b.name, label : b.label, multiline : b.multiline, required : b.required };

		if( b.type === 'signature' ) {

			const number = b.number.trim();
			const page = b.page === 'last' ? 'last' : ( b.page === 'first' ? 1 : ( /^[1-9][0-9]{0,3}$/.test( number ) === true ? Number( number ) : number ) );

			return { type : 'signature', name : b.name, party : b.party, label : b.label, page : page, x : millimetres( b.x ), y : millimetres( b.y ), w : millimetres( b.w ), h : millimetres( b.h ), line : b.line };
		}

		return { type : 'pagebreak' };
	};

	/**
	 *	@param		{string}	name						What a person called a template
	 *	@param		{string}	tag
	 *	@param		{number}	[attempt]				1 for the first key, 2 and on for the keys after a key that was taken
	 *
	 *	@return		{string}									The key the template is saved under: the name as a slug (umlauts spelled out), else the tag with -template; -2, -3 ... on a later attempt
	 */
	const templateKey = function( name, tag, attempt ) {

		const umlauts = { 'ä' : 'ae', 'ö' : 'oe', 'ü' : 'ue', 'ß' : 'ss' };
		let slug = String( name ).toLowerCase().replace( /[äöüß]/g, function( char ) { return umlauts[char] } ).normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' ).slice( 0, 34 ).replace( /-+$/, '' );

		if( /^[a-z][a-z0-9-]+$/.test( slug ) === false )
			slug = ( ( tag === '' ? '' : tag+ '-' )+ 'template' ).slice( 0, 34 );

		return attempt === undefined || attempt < 2 ? slug : slug+ '-'+ attempt;
	};

	/**
	 *	A name no other block of the template has: field, field-2, ...
	 *
	 *	@param		{Array}		blocks
	 *	@param		{string}	base
	 *
	 *	@return		{string}
	 */
	const freeName = function( blocks, base ) {

		const taken = blocks.map( function( block ) { return block.name } );
		let name = base;
		let at = 2;

		while( taken.includes( name ) === true )
			name = base+ '-'+ ( at++ );

		return name;
	};

	/**
	 *	A block of a kind, as "Add" makes it
	 *
	 *	@param		{Array}		blocks					The blocks there are
	 *	@param		{string}	kind						html, ref (with a key), slot (with a name), input, signature, pagebreak
	 *	@param		{string}	[what]
	 *
	 *	@return		{Object}
	 */
	const blockNew = function( blocks, kind, what ) {

		if( kind === 'ref' )
			return { type : 'html', mode : 'ref', block : what, html : '' };

		if( kind === 'slot' )
			return { type : 'slot', name : what };

		if( kind === 'input' )
			return { type : 'input', name : freeName( blocks, t( 'pdf.name-input' ) ), label : '', multiline : false, required : false };

		if( kind === 'signature' )
			return { type : 'signature', name : freeName( blocks, t( 'pdf.name-signature' ) ), party : 'owner', label : '', page : 'last', number : '2', x : '20', y : '230', w : '70', h : '20', line : true };

		return kind === 'pagebreak' ? { type : 'pagebreak' } : { type : 'html', mode : 'own', block : '', html : '' };
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Object}	where						key (a template of the library) or tag (a new one)
	 *
	 *	@return		{void}
	 */
	const templateScreen = function( inner, where ) {

		if( permitted( inner ) === false )
			return;

		const asks = where.key === undefined ? [ [ 'crm-pdf/templates-list' ] ] : [ [ 'crm-pdf/template-get', { key : where.key } ] ];

		read( inner, asks, function( body, context ) {

			const used = where.key === undefined ? 0 : ( context.used[where.key] || 0 );

			if( where.key === undefined )
				drawTemplate( inner, { isNew : true, key : '', name : '', tag : where.tag ?? '', blocks : [] }, body, context );
			else
				drawTemplate( inner, { isNew : false, key : body.key, name : body.template.name, tag : body.template.tag, blocks : body.template.blocks.map( blockDraft ) }, body, context, used );
		}, function( failure ) {

			// A template that is not there: the sentence and the way back, the tabs where the person came from
			reveal();
			inner.main.appendChild( head( t( 'pdf.title' ) ) );
			inner.main.appendChild( tabs( 'templates' ) );
			inner.main.appendChild( note( 'bad', el( 'span', { text : errorText( failure, 'error.load' ) } ), ' ', link( '#pdf', t( 'pdf.back' ) ) ) );
		} );
	};

	/**
	 *	The editor of a template: its name and tag, its blocks as cards in order, and what Add offers
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	start						isNew, key, name, tag, blocks (as blockDraft makes them)
	 *	@param		{Object}	offer						tags, library, ranges, paper (what the get and the list answer)
	 *	@param		{Object}	context
	 *	@param		{number}	[used]					How many things name the template
	 *
	 *	@return		{void}
	 */
	const drawTemplate = function( inner, start, offer, context, used ) {

		const draft = Object.assign( { dirty : false, form : true, errors : {} }, start );
		const message = el( 'p', { class : 'state-note bad', id : 'tpl-note', role : 'alert' } );
		const box = el( 'div', { class : 'blocks', id : 'blocks' } );
		const library = offer.library;
		const slotWord = function( name ) {
			const known = context.slots.find( function( slot ) { return slot.name === name } );
			return known === undefined ? name : known.label;
		};

		inner.arm( draft );

		const touch = function() {
			draft.dirty = true;
			message.textContent = '';
			nameField.classList.remove( 'err' );
			tagField.classList.remove( 'err' );
		};

		const nameField = el( 'input', { id : 'tpl-name', type : 'text', value : draft.name, maxlength : String( offer.ranges.title ), autocomplete : 'off', placeholder : t( 'pdf.name' ), 'aria-label' : t( 'pdf.name' ), on : { input : function( event ) { draft.name = event.target.value; touch() } } } );
		const tags = draft.tag === '' || offer.tags.indexOf( draft.tag ) !== -1 ? offer.tags : [ draft.tag ].concat( offer.tags );
		const tagField = el( 'select', { id : 'tpl-tag', value : draft.tag, 'aria-label' : t( 'pdf.col-tag' ), on : { change : function( event ) { draft.tag = event.target.value; touch() } } },
			[ el( 'option', { value : '', text : t( 'pdf.tag-none' ) } ) ].concat( tags.map( function( tag ) {
				return el( 'option', { value : tag, text : tagWord( tag )+ ( offer.tags.indexOf( tag ) === -1 ? ' ('+ t( 'pdf.tag-gone' )+ ')' : '' ) } );
			} ) ) );

		// The cards are drawn again after a change, and the focus would go with the node that was taken away: it goes to the control the person used, where the card now stands - to the other arrow where this one has nowhere to go
		const focusAt = function( index, which ) {

			const card = box.children[index];

			if( card === undefined || card === null )
				return;

			const order = which === 'up' ? [ 'button.up', 'button.down' ] : ( which === 'down' ? [ 'button.down', 'button.up' ] : [ which ] );
			const target = order.map( function( selector ) { return card.querySelector( selector ) } ).find( function( node ) { return node !== null && node !== undefined && node.disabled !== true } );

			if( target !== undefined )
				target.focus();
		};

		const shift = function( index, by ) {

			const to = index+ by;

			if( to < 0 || to >= draft.blocks.length )
				return;

			draft.blocks.splice( to, 0, draft.blocks.splice( index, 1 )[0] );
			draft.errors = {};
			draft.dirty = true;
			drawBlocks();
			focusAt( to, by < 0 ? 'up' : 'down' );
		};

		const drop = function( index ) {
			draft.blocks.splice( index, 1 );
			draft.errors = {};
			draft.dirty = true;
			drawBlocks();

			if( draft.blocks.length === 0 )
				dc.getElementById( 'add-html' ).focus();
			else
				focusAt( Math.min( index, draft.blocks.length - 1 ), 'button.del' );
		};

		const field = function( label, control ) {
			return el( 'label', { class : 'sf' }, el( 'span', { text : label } ), control );
		};

		const text = function( b, key, label, size ) {
			return field( label, el( 'input', { type : 'text', value : b[key], maxlength : size, autocomplete : 'off', dataset : { f : key }, on : { input : function( event ) { b[key] = event.target.value; draft.dirty = true } } } ) );
		};

		const check = function( b, key, label ) {
			return el( 'label', { class : 'check sf' }, el( 'input', { type : 'checkbox', checked : b[key] === true, dataset : { f : key }, on : { change : function( event ) { b[key] = event.target.checked; draft.dirty = true } } } ), el( 'span', { text : label } ) );
		};

		const choose = function( b, key, label, options ) {
			return field( label, el( 'select', { value : b[key], dataset : { f : key }, on : { change : function( event ) { b[key] = event.target.value; draft.dirty = true; drawBlocks() } } }, options.map( function( option ) { return el( 'option', { value : option[0], text : option[1] } ) } ) ) );
		};

		const kindOf = function( b ) {

			if( b.type === 'html' && b.mode === 'ref' ) {
				const known = library.find( function( one ) { return one.key === b.block } );
				return [ t( 'pdf.kind-html' ), t( 'pdf.kind-ref', known === undefined ? b.block : known.title ) ];
			}

			const extra = { slot : slotWord( b.name ), input : b.label, signature : t( 'pdf.party-'+ b.party )+ ( b.label === '' ? '' : ' · '+ b.label ) }[b.type];

			return [ t( 'pdf.kind-'+ b.type ), extra === undefined || extra === '' ? null : extra ];
		};

		const bodyOf = function( b ) {

			if( b.type === 'html' && b.mode === 'ref' ) {

				const known = library.some( function( one ) { return one.key === b.block } );
				const options = ( known === true ? [] : [ [ b.block, b.block ] ] ).concat( library.map( function( one ) { return [ one.key, one.title ] } ) );

				return [ choose( b, 'block', t( 'pdf.block-of' ), options ), el( 'p', { class : 'state-note' }, t( 'pdf.ref-note' ), ' ', known === true ? link( '#pdf-block-'+ b.block, t( 'pdf.ref-edit' ) ) : null ) ];
			}

			if( b.type === 'html' ) {

				const area = el( 'textarea', { class : 'code', rows : 7, spellcheck : 'false', maxlength : String( offer.ranges.html ), 'aria-label' : t( 'pdf.kind-html' ), value : b.html, on : { input : function( event ) { b.html = event.target.value; draft.dirty = true } } } );

				return withPlaceholders( context, area, false, function() { b.html = area.value; draft.dirty = true }, el( 'span', { class : 'state-note', text : t( 'pdf.html-note' ) } ) );
			}

			if( b.type === 'slot' ) {

				const names = context.slots.map( function( slot ) { return slot.name } );

				return choose( b, 'name', t( 'pdf.slot' ), ( names.indexOf( b.name ) === -1 ? [ b.name ] : [] ).concat( names ).map( function( name ) { return [ name, slotWord( name ) ] } ) );
			}

			if( b.type === 'input' )
				return [ el( 'div', { class : 'sig' }, text( b, 'name', t( 'pdf.input-name' ), '40' ), text( b, 'label', t( 'pdf.input-label' ), String( offer.ranges.label ) ) ), el( 'div', { class : 'sig' }, check( b, 'multiline', t( 'pdf.input-multiline' ) ), check( b, 'required', t( 'pdf.input-required' ) ) ), el( 'p', { class : 'state-note', text : t( 'pdf.input-note' ) } ) ];

			if( b.type === 'signature' )
				return [
					el( 'div', { class : 'sig' }, choose( b, 'party', t( 'pdf.sig-party' ), [ [ 'owner', t( 'pdf.party-owner' ) ], [ 'member', t( 'pdf.party-member' ) ] ] ), text( b, 'name', t( 'pdf.input-name' ), '40' ), text( b, 'label', t( 'pdf.input-label' ), String( offer.ranges.label ) ) ),
					el( 'div', { class : 'sig' }, choose( b, 'page', t( 'pdf.sig-page' ), [ [ 'first', t( 'pdf.page-first' ) ], [ 'last', t( 'pdf.page-last' ) ], [ 'number', t( 'pdf.page-number' ) ] ] ), b.page === 'number' ? text( b, 'number', t( 'pdf.page-no' ), '4' ) : null ),
					el( 'div', { class : 'sig' }, text( b, 'x', t( 'pdf.sig-x' ), '8' ), text( b, 'y', t( 'pdf.sig-y' ), '8' ), text( b, 'w', t( 'pdf.sig-w' ), '8' ), text( b, 'h', t( 'pdf.sig-h' ), '8' ) ),
					check( b, 'line', t( 'pdf.sig-line' ) ),
					el( 'p', { class : 'state-note', text : t( 'pdf.sig-note', offer.paper[0], offer.paper[1] ) } ),
				];

			return el( 'p', { class : 'state-note', text : t( 'pdf.pagebreak-note' ) } );
		};

		const blockCard = function( b, index ) {

			const kind = kindOf( b );
			const err = el( 'p', { class : 'state-note bad berr', role : 'alert', text : draft.errors[index] || '' } );

			return el( 'div', { class : 'block'+ ( draft.errors[index] === undefined ? '' : ' err' ), dataset : { i : index, type : b.type }, on : { input : function() { err.textContent = ''; delete draft.errors[index] } } },
				el( 'div', { class : 'bmain' },
					el( 'span', { class : 'kind' }, kind[0], kind[1] === null ? null : [ ' ', el( 'span', { class : 'tag', text : kind[1] } ) ] ),
					bodyOf( b ), err ),
				el( 'div', { class : 'ctl' },
					el( 'button', { type : 'button', class : 'up', 'aria-label' : t( 'pdf.up' ), title : t( 'pdf.up' ), text : '↑', disabled : index === 0, on : { click : function() { shift( index, -1 ) } } } ),
					el( 'button', { type : 'button', class : 'down', 'aria-label' : t( 'pdf.down' ), title : t( 'pdf.down' ), text : '↓', disabled : index === draft.blocks.length - 1, on : { click : function() { shift( index, 1 ) } } } ),
					el( 'button', { type : 'button', class : 'del', 'aria-label' : t( 'pdf.remove' ), title : t( 'pdf.remove' ), text : '×', on : { click : function() { drop( index ) } } } ) ) );
		};

		const drawBlocks = function() {

			clear( box );
			box.setAttribute( 'aria-label', t( 'pdf.blocks' ) );

			if( draft.blocks.length === 0 )
				box.appendChild( el( 'p', { class : 'state-note', id : 'no-blocks', text : t( 'pdf.no-blocks' ) } ) );

			draft.blocks.forEach( function( b, index ) { box.appendChild( blockCard( b, index ) ) } );
		};

		const add = function( kind, what ) {
			draft.blocks.push( blockNew( draft.blocks, kind, what ) );
			draft.dirty = true;
			drawBlocks();

			// The new card is where the person looks next: its first field has the focus
			const added = box.children[box.children.length - 1];
			const first = added === undefined ? null : added.querySelector( 'textarea, input, select, button' );

			if( first !== null && first !== undefined ) {
				first.focus();

				if( typeof added.scrollIntoView === 'function' )
					added.scrollIntoView();
			}
		};

		const payload = function( attempt ) {
			return { key : draft.isNew === true ? templateKey( draft.name, draft.tag, attempt ) : draft.key, isNew : draft.isNew, name : draft.name, tag : draft.tag, blocks : draft.blocks.map( blockPayload ) };
		};

		// The refusal of a save or a preview: beside the block it names, else beside the name or the tag, else under the head
		const refuse = function( answer, fallback ) {

			const sentence = failed( answer, fallback );
			const body = answer.body !== null && typeof answer.body === 'object' ? answer.body : {};
			const at = Array.isArray( body.params ) === true ? Number( body.params[0] ) : 0;

			if( body.field === 'blocks' && BLOCK_CODES.includes( body.code ) === true && Number.isInteger( at ) === true && at >= 1 && at <= draft.blocks.length ) {
				draft.errors[at - 1] = sentence;
				drawBlocks();

				if( typeof box.children[at - 1].scrollIntoView === 'function' )
					box.children[at - 1].scrollIntoView();

				return;
			}

			message.textContent = sentence;

			if( body.field === 'name' || body.field === 'key' ) {
				nameField.classList.add( 'err' );
				nameField.focus();
			}
			else if( body.field === 'tag' ) {
				tagField.classList.add( 'err' );
				tagField.focus();
			}
		};

		const saveButton = el( 'button', { type : 'button', class : 'btn primary', id : 'tpl-save', title : t( 'save.shortcut' ), text : t( 'pdf.save' ), on : { click : function() { save( 1 ) } } } );
		const previewButton = el( 'button', { type : 'button', class : 'btn', id : 'tpl-preview', text : t( 'pdf.preview' ), on : { click : function() {
			// What the editor holds is previewed as it is, saved or not: nothing is stored for it
			preview( inner, previewButton, 'crm-pdf/preview', payload( 1 ), refuse );
		} } } );

		const save = function( attempt ) {

			saveButton.disabled = true;

			api( 'crm-pdf/template-save', payload( attempt ) ).then( function( answer ) {

				saveButton.disabled = false;

				if( inner.alive() === false )
					return;

				if( answer.ok === true ) {
					draft.dirty = false;
					toast( t( 'pdf.saved' ) );

					if( draft.isNew === true )
						wn.location.hash = '#pdf-template-'+ answer.body.key;

					return;
				}

				// A key the name gives that another template has: the next one, a few times
				if( draft.isNew === true && answer.body !== null && typeof answer.body === 'object' && answer.body.code === 'pdf_key_taken' && attempt < 9 ) {
					save( attempt+ 1 );
					return;
				}

				refuse( answer, 'pdf.error-save' );
			} );
		};

		const duplicate = function() {
			dialog( { title : t( 'pdf.duplicate' ), text : t( 'pdf.duplicate-text' ), yes : t( 'pdf.duplicate' ), fields : [ { name : 'name', label : t( 'pdf.name' ), value : t( 'pdf.copy-of', draft.name ) } ] } ).then( function( values ) {

				if( values === null || inner.alive() === false )
					return;

				call( 'crm-pdf/template-duplicate', { key : draft.key, name : values.name }, 'pdf.error-duplicate' ).then( function( answer ) {
					if( answer.ok === true && inner.alive() === true ) {
						draft.dirty = false;
						toast( t( 'pdf.duplicated' ) );
						wn.location.hash = '#pdf-template-'+ answer.body.key;
					}
				} );
			} );
		};

		const users = Object.keys( context.by[draft.key] || {} ).map( function( name ) { return name+ ' ('+ context.by[draft.key][name]+ ')' } ).join( ', ' );

		const remove = function() {

			if( used > 0 ) {
				toast( t( 'pdf.delete-used', users ), true );
				return;
			}

			ask( { title : t( 'pdf.delete' ), text : t( 'pdf.delete-text', draft.name ), yes : t( 'pdf.delete' ), danger : true } ).then( function( yes ) {

				if( yes !== true || inner.alive() === false )
					return;

				call( 'crm-pdf/template-delete', { key : draft.key }, 'pdf.error-delete' ).then( function( answer ) {
					if( answer.ok === true && inner.alive() === true ) {
						draft.dirty = false;
						toast( t( 'pdf.deleted' ) );
						wn.location.hash = '#pdf';
					}
				} );
			} );
		};

		const more = draft.isNew === true ? null : menu( { label : '⋯', aria : t( 'pdf.more' ), items : [ { label : t( 'pdf.duplicate' ), run : duplicate }, 'sep', { label : t( 'pdf.delete' ), danger : true, run : remove } ] } );

		const adder = el( 'div', { class : 'addblock', id : 'add' }, el( 'span', { class : 'state-note', text : t( 'pdf.add' ) } ),
			el( 'button', { type : 'button', class : 'btn sm', id : 'add-html', text : t( 'pdf.kind-html' ), on : { click : function() { add( 'html' ) } } } ),
			menu( { label : t( 'pdf.add-ref' )+ ' ▾', button : 'sm', aria : t( 'pdf.add-ref' ), items : library.length === 0 ? [ { heading : t( 'pdf.add-ref-none' ) } ] : library.map( function( one ) { return { label : one.title, run : function() { add( 'ref', one.key ) } } } ) } ),
			menu( { label : t( 'pdf.add-slot' )+ ' ▾', button : 'sm', aria : t( 'pdf.add-slot' ), items : context.slots.length === 0 ? [ { heading : t( 'pdf.add-slot-none' ) } ] : context.slots.map( function( slot ) { return { label : slot.label, run : function() { add( 'slot', slot.name ) } } } ) } ),
			el( 'button', { type : 'button', class : 'btn sm', id : 'add-input', text : t( 'pdf.kind-input' ), on : { click : function() { add( 'input' ) } } } ),
			el( 'button', { type : 'button', class : 'btn sm', id : 'add-pagebreak', text : t( 'pdf.kind-pagebreak' ), on : { click : function() { add( 'pagebreak' ) } } } ),
			el( 'button', { type : 'button', class : 'btn sm', id : 'add-signature', text : t( 'pdf.kind-signature' ), on : { click : function() { add( 'signature' ) } } } ) );

		drawBlocks();
		reveal();

		inner.main.appendChild( crumb( '#pdf', t( 'pdf.title' ), draft.isNew === true ? t( 'pdf.new-title' ) : draft.name ) );
		inner.main.appendChild( el( 'div', { class : 'head' }, el( 'div', { class : 'tagline' }, nameField, tagField ), el( 'span', { class : 'grow' } ), previewButton, saveButton, more ) );
		append( inner.main, [ used > 0 ? el( 'p', { class : 'state-note', id : 'tpl-used', text : t( 'pdf.used-by', users ) } ) : null, message, box, adder, el( 'p', { class : 'state-note top', id : 'tpl-help', text : t( 'pdf.editor-note' ) } ) ] );
		saver( 'template', inner.main, function() { save( 1 ) }, undefined, function() { return saveButton.disabled === true } );
	};

	// --- The text blocks -----------------------------------------------------------------------------------------------

	/**
	 *	The legend of the placeholders: what the callers offer and the texts of the site, in one card
	 *
	 *	@param		{Object}	context
	 *
	 *	@return		{Object}
	 */
	const legendCard = function( context ) {

		return el( 'div', { class : 'card pad', id : 'legend' }, el( 'h3', { text : t( 'pdf.legend' ) } ), el( 'p', { class : 'state-note', text : t( 'pdf.legend-note' ) } ),
			tokenGroups( context, true ).map( function( group ) {
				return [ el( 'h4', { text : group.title } ), el( 'table', { class : 'tbl static' }, el( 'tbody', {}, group.tokens.map( function( one ) {
					return el( 'tr', {}, el( 'td', {}, el( 'span', { class : 'num', text : '[['+ one.token+ ']]' } ) ), el( 'td', { class : 'muted', text : one.label } ) );
				} ) ) ) ];
			} ) );
	};

	/**
	 *	@param		{Object}	inner
	 *
	 *	@return		{void}
	 */
	const blocksScreen = function( inner ) {

		if( permitted( inner ) === false )
			return;

		read( inner, [ [ 'crm-pdf/blocks-list' ] ], function( list, context ) {

			const rows = list.blocks.map( function( block ) { return Object.assign( {}, block, { used : context.blocks[block.key] || [] } ) } );

			reveal();

			inner.main.appendChild( head( t( 'pdf.blocks-title' ), el( 'span', { class : 'sub', text : t( 'pdf.blocks-sub' ) } ), el( 'span', { class : 'grow' } ), link( '#pdf-new-block', t( 'pdf.new-block' ), { class : 'btn primary', id : 'new-block' } ) ) );
			inner.main.appendChild( tabs( 'blocks' ) );
			inner.main.appendChild( el( 'div', { class : 'tpl-grid' },
				el( 'div', {}, cardList( el( 'div', { class : 'card wrap-x' }, el( 'table', { class : 'tbl', id : 'pdf-blocks' },
					el( 'thead', {}, el( 'tr', {}, el( 'th', { scope : 'col', text : t( 'pdf.col-block' ) } ), el( 'th', { scope : 'col', text : t( 'pdf.col-title' ) } ), el( 'th', { scope : 'col', class : 'r', text : t( 'pdf.col-used-in' ) } ) ) ),
					el( 'tbody', {}, rows.length === 0
						? el( 'tr', { class : 'plain' }, el( 'td', { colspan : '3', class : 'muted', text : t( 'pdf.blocks-none' ) } ) )
						: rows.map( function( block ) {
							return el( 'tr', { dataset : { key : block.key }, on : { click : function() { wn.location.hash = '#pdf-block-'+ block.key } } },
								el( 'td', { class : 'num' }, link( '#pdf-block-'+ block.key, block.key ) ),
								el( 'td', { class : 'wrap' }, el( 'b', { text : block.title } ), el( 'div', { class : 'muted small', text : plainText( block.html ) } ) ),
								el( 'td', { class : 'r num', text : block.used.length > 0 ? String( block.used.length ) : '–' } ) );
						} ) ) ) ), 'pdf-blocks', [ 'id', 'sub', 'x-used:l' ] ) ),
				legendCard( context ) ) );
		} );
	};

	/**
	 *	@param		{Object}	inner
	 *	@param		{Object}	where						key of a block, none for a new one
	 *
	 *	@return		{void}
	 */
	const blockScreen = function( inner, where ) {

		if( permitted( inner ) === false )
			return;

		read( inner, [ [ 'crm-pdf/blocks-list' ] ], function( list, context ) {

			const found = where.key === undefined ? { key : '', title : '', html : '' } : list.blocks.find( function( block ) { return block.key === where.key } );

			if( found === undefined ) {
				reveal();
				inner.main.appendChild( head( t( 'pdf.blocks-title' ) ) );
				inner.main.appendChild( tabs( 'blocks' ) );
				inner.main.appendChild( note( 'bad', el( 'span', { text : t( 'error.pdf_block_missing' ) } ), link( '#pdf-blocks', t( 'pdf.back' ) ) ) );
				return;
			}

			drawBlock( inner, found, where.key === undefined, list.ranges, context.blocks[found.key] || [], context );
		} );
	};

	/**
	 *	The editor of a block: its key (a slug that stays after the first save), its title and its HTML with the menu of the placeholders
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	block						key, title, html
	 *	@param		{boolean}	isNew
	 *	@param		{Object}	ranges					The limits of the fields
	 *	@param		{Array}		users						The names of the templates that use it
	 *	@param		{Object}	context
	 *
	 *	@return		{void}
	 */
	const drawBlock = function( inner, block, isNew, ranges, users, context ) {

		const form = formOf();
		const area = el( 'textarea', { id : 'f-html', class : 'code', rows : 14, spellcheck : 'false', maxlength : String( ranges.html ), 'aria-label' : t( 'pdf.html' ), value : block.html } );
		const areaNote = el( 'span', { class : 'state-note bad', role : 'alert' } );

		form.controls.html = area;
		form.notes.html = areaNote;
		area.addEventListener( 'input', function() {
			area.classList.remove( 'err' );
			areaNote.textContent = '';
			form.dirty = true;
		} );

		const rows = formList( [
			{ name : 'key', label : t( 'pdf.block-key' ), control : el( 'input', { id : 'f-key', type : 'text', value : block.key, maxlength : '40', autocomplete : 'off', disabled : isNew === false } ), hint : t( 'pdf.block-key-hint' ) },
			{ name : 'title', label : t( 'pdf.block-title' ), control : el( 'input', { id : 'f-title', type : 'text', value : block.title, maxlength : String( ranges.title ), autocomplete : 'off' } ) },
		], form );

		const save = el( 'button', { type : 'submit', class : 'btn primary', id : 'block-save', title : t( 'save.shortcut' ), text : t( 'pdf.save' ) } );

		const remove = function() {

			if( users.length > 0 ) {
				toast( t( 'error.pdf_block_used', users.join( ', ' ) ), true );
				return;
			}

			ask( { title : t( 'pdf.delete' ), text : t( 'pdf.block-delete-text', block.title ), yes : t( 'pdf.delete' ), danger : true } ).then( function( yes ) {

				if( yes !== true || inner.alive() === false )
					return;

				call( 'crm-pdf/block-delete', { key : block.key }, 'pdf.error-delete' ).then( function( answer ) {
					if( answer.ok === true && inner.alive() === true ) {
						form.dirty = false;
						toast( t( 'pdf.deleted' ) );
						wn.location.hash = '#pdf-blocks';
					}
				} );
			} );
		};

		const submit = function( event ) {

			event.preventDefault();
			save.disabled = true;

			const key = form.controls.key.value;

			api( 'crm-pdf/block-save', { key : key, isNew : isNew, title : form.controls.title.value, html : area.value } ).then( function( answer ) {

				save.disabled = false;

				if( inner.alive() === false )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'pdf.error-save' );
					return;
				}

				form.dirty = false;
				toast( t( 'pdf.saved' ) );

				// The answer is the library again: the block as the store keeps it - its HTML held to the list of the feature - is in it
				const kept = answer.body.blocks.find( function( one ) { return one.key === key } );

				if( isNew === true )
					wn.location.hash = '#pdf-block-'+ key;
				else if( kept !== undefined )
					area.value = kept.html;
			} );
		};

		reveal();

		inner.main.appendChild( crumb( '#pdf-blocks', t( 'pdf.blocks-title' ), isNew === true ? t( 'pdf.new-block' ) : block.title ) );

		const box = el( 'form', { id : 'block-form', novalidate : 'novalidate', on : { submit : submit } },
			head( isNew === true ? t( 'pdf.new-block' ) : block.title, el( 'span', { class : 'grow' } ),
				isNew === true ? null : menu( { label : '⋯', aria : t( 'pdf.more' ), items : [ { label : t( 'pdf.delete' ), danger : true, run : remove } ] } ), save ),
			el( 'div', { class : 'card pad' }, rows,
				el( 'div', { class : 'field' }, el( 'span', { text : t( 'pdf.html' ) } ), withPlaceholders( context, area, false, function() { form.dirty = true }, el( 'span', { class : 'state-note', text : t( 'pdf.html-note' ) } ) ), areaNote ),
				isNew === true ? null : el( 'p', { class : 'state-note', id : 'block-used', text : users.length === 0 ? t( 'pdf.block-unused' ) : t( 'pdf.block-used-by', users.join( ', ' ) ) } ) ) );

		inner.main.appendChild( box );
		submitter( 'block', box, save );
	};

	// --- The layout ----------------------------------------------------------------------------------------------------

	/**
	 *	@param		{string}	text
	 *
	 *	@return		{number|string}						A whole number where the text is one; else the text, which the server refuses
	 */
	const wholeNumber = function( text ) {
		return /^\d{1,6}$/.test( text.trim() ) === true ? Number( text.trim() ) : text;
	};

	/**
	 *	The layout the way the server takes it: what a save posts of the fields that are on the screen
	 *
	 *	@param		{Object}	layout					The layout as the server has it
	 *
	 *	@return		{Object}
	 */
	const layoutPost = function( layout ) {
		return { format : layout.format, margins : layout.margins.slice(), font : layout.font, size : layout.size, header : layout.header, footer : layout.footer };
	};

	/**
	 *	The bytes of a file as base64, in pieces, so that a picture of megabytes is no call with that many arguments
	 *
	 *	@param		{ArrayBuffer}	buffer
	 *
	 *	@return		{string}
	 */
	const base64Of = function( buffer ) {

		const bytes = new Uint8Array( buffer );
		let text = '';

		for( let at = 0; at < bytes.length; at += 8192 )
			text += String.fromCharCode.apply( null, bytes.subarray( at, at + 8192 ) );

		return wn.btoa( text );
	};

	/**
	 *	The sentence for a picture that was refused: the word of the code where the package has one (what the kernel says of an upload and of an image), else the sentence of the code, else the word for what was being done
	 *
	 *	@param		{Object}	answer
	 *
	 *	@return		{string}
	 */
	const pictureText = function( answer ) {

		const code = answer.body !== null && typeof answer.body === 'object' && typeof answer.body.code === 'string' ? answer.body.code : '';

		if( /^[a-z_]+$/.test( code ) === true && has( 'pdf.err-'+ code ) === true )
			return t( 'pdf.err-'+ code, ...( Array.isArray( answer.body.params ) === true ? answer.body.params : [] ) );

		return errorText( answer, 'pdf.error-image' );
	};

	/**
	 *	@param		{Object}	inner
	 *
	 *	@return		{void}
	 */
	const layoutScreen = function( inner ) {

		if( permitted( inner ) === false )
			return;

		read( inner, [ [ 'crm-pdf/layout-get' ] ], function( body, context ) { drawLayout( inner, body, context ) } );
	};

	/**
	 *	The card of a picture of the layout (the logo, the owner's signature): the picture, what it is for, the upload and the removal. The answer of either is the layout again, which draws the card again
	 *
	 *	@param		{Object}	inner
	 *	@param		{string}	kind						logo or signature
	 *	@param		{Object}	view						The answer of crm-pdf/layout-get (the current one in .now)
	 *
	 *	@return		{Object}
	 */
	const pictureCard = function( inner, kind, view ) {

		const stored = view.now.layout[kind];
		const shown = view.now.images[kind];
		const said = el( 'p', { class : 'state-note bad', role : 'alert' } );
		const take = function( answer ) {

			if( inner.alive() === false )
				return;

			if( answer.ok !== true ) {

				said.textContent = pictureText( answer );

				if( answer.handled !== true )
					toast( said.textContent, true );

				return;
			}

			view.now = answer.body;
			toast( t( 'pdf.saved' ) );
			card.replaceWith( card = pictureCard( inner, kind, view ) );
		};

		const file = el( 'input', { type : 'file', id : 'file-'+ kind, class : 'file', accept : 'image/png,image/jpeg', 'aria-label' : t( 'pdf.upload-'+ kind ), on : { change : function( event ) {

			const chosen = event.target.files !== undefined && event.target.files !== null ? event.target.files[0] : undefined;

			if( chosen === undefined )
				return;

			said.textContent = '';

			if( chosen.size > PICTURE_MAX ) {
				said.textContent = t( 'pdf.err-image_too_large', PICTURE_MAX / 1048576 );
				return;
			}

			chosen.arrayBuffer().then( function( buffer ) {
				return api( 'crm-pdf/layout-image', { kind : kind, data : base64Of( buffer ) } );
			} ).then( take, function() {
				said.textContent = t( 'pdf.err-upload_missing' );
			} );
		} } } );

		const drop = function() {
			ask( { title : t( 'pdf.delete' ), text : t( 'pdf.picture-delete' ), yes : t( 'pdf.delete' ), danger : true } ).then( function( yes ) {

				if( yes !== true || inner.alive() === false )
					return;

				api( 'crm-pdf/layout-image-delete', { kind : kind } ).then( take );
			} );
		};

		let card = el( 'div', { class : 'card pad', id : 'pic-'+ kind }, el( 'h3', { text : t( 'pdf.'+ kind ) } ),
			stored === '' ? el( 'p', { class : 'state-note', text : t( 'pdf.picture-none' ) } )
				: ( shown === '' ? el( 'p', { class : 'state-note', text : t( 'pdf.picture-large', stored ) } ) : el( 'div', { class : 'pic' }, el( 'img', { src : shown, alt : t( 'pdf.'+ kind ) } ) ) ),
			el( 'p', { class : 'state-note', text : t( 'pdf.picture-hint-'+ kind ) } ),
			el( 'div', { class : 'actions' }, el( 'label', { class : 'btn sm', for : 'file-'+ kind }, stored === '' ? t( 'pdf.upload' ) : t( 'pdf.replace' ) ), file,
				stored === '' ? null : el( 'button', { type : 'button', class : 'btn sm danger', id : 'drop-'+ kind, text : t( 'pdf.delete' ), on : { click : drop } } ) ),
			said );

		return card;
	};

	/**
	 *	The screen Layout: the paper and the font, the margins, the header and the footer, the two pictures, and a preview of a page
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	body						The answer of crm-pdf/layout-get
	 *	@param		{Object}	context
	 *
	 *	@return		{void}
	 */
	const drawLayout = function( inner, body, context ) {

		const layout = body.layout;
		const form = formOf();
		const view = { now : body };
		const margins = layout.margins.map( function( mm, at ) {
			return el( 'input', { type : 'text', inputmode : 'numeric', value : String( mm ), maxlength : '3', autocomplete : 'off', class : 'mm', 'aria-label' : t( 'pdf.margin-'+ [ 'top', 'right', 'bottom', 'left' ][at] ), dataset : { margin : at } } );
		} );
		const marginBox = el( 'div', { id : 'f-margins', class : 'pair' }, margins, el( 'span', { class : 'state-note', text : t( 'pdf.margins-hint' ) } ) );
		const header = el( 'textarea', { id : 'f-header', class : 'code', rows : 5, spellcheck : 'false', maxlength : String( body.ranges.band ), value : layout.header } );
		const footer = el( 'textarea', { id : 'f-footer', class : 'code', rows : 5, spellcheck : 'false', maxlength : String( body.ranges.band ), value : layout.footer } );
		const owned = body.owned === undefined ? {} : body.owned;
		const fonts = body.fonts.map( function( font ) { return [ font, t( 'pdf.font-'+ font ) ] } ).concat( Object.keys( owned ).map( function( font ) { return [ font, String( owned[font] ) ] } ) );

		margins.forEach( function( input ) { input.addEventListener( 'input', function() { form.dirty = true } ) } );
		marginBox.focus = function() { margins[0].focus() };

		const fields = formList( [
			{ name : 'format', label : t( 'pdf.paper' ), control : el( 'select', { id : 'f-format', value : layout.format }, body.papers.map( function( paper ) { return el( 'option', { value : paper, text : t( 'pdf.paper-'+ paper ) } ) } ) ) },
			{ name : 'font', label : t( 'pdf.font' ), control : el( 'select', { id : 'f-font', value : layout.font }, fonts.map( function( font ) { return el( 'option', { value : font[0], text : font[1] } ) } ) ),
				hint : body.ignored === undefined ? undefined : t( 'pdf.fonts-ignored', body.ignored.join( ', ' ) ) },
			{ name : 'size', label : t( 'pdf.size' ), control : el( 'input', { id : 'f-size', type : 'text', inputmode : 'numeric', value : String( layout.size ), maxlength : '3', autocomplete : 'off', class : 'short' } ), hint : t( 'pdf.size-hint', body.ranges.size[0], body.ranges.size[1] ) },
			{ name : 'margins', label : t( 'pdf.margins' ), control : marginBox },
		], form );

		const band = function( name, control, label ) {

			const said = el( 'span', { class : 'state-note bad', role : 'alert' } );

			form.controls[name] = control;
			form.notes[name] = said;
			control.addEventListener( 'input', function() {
				control.classList.remove( 'err' );
				said.textContent = '';
				form.dirty = true;
			} );

			return el( 'div', { class : 'card pad', id : 'band-'+ name }, el( 'h3', { text : label } ), withPlaceholders( context, control, true, function() { form.dirty = true }, el( 'span', { class : 'state-note', text : t( 'pdf.band-note' ) } ) ), said );
		};

		const save = el( 'button', { type : 'submit', class : 'btn primary', id : 'layout-save', title : t( 'save.shortcut' ), text : t( 'pdf.save' ) } );
		const previewButton = el( 'button', { type : 'button', class : 'btn', id : 'layout-preview', text : t( 'pdf.preview' ), on : { click : function() { preview( inner, previewButton, 'crm-pdf/layout-preview' ) } } } );

		const submit = function( event ) {

			event.preventDefault();
			save.disabled = true;

			const posted = { format : form.controls.format.value, font : form.controls.font.value, size : wholeNumber( form.controls.size.value ), margins : margins.map( function( input ) { return wholeNumber( input.value ) } ), header : header.value, footer : footer.value };

			api( 'crm-pdf/layout-save', posted ).then( function( answer ) {

				save.disabled = false;

				if( inner.alive() === false )
					return;

				if( answer.ok !== true ) {
					refuseAt( form, answer, 'pdf.error-save' );
					return;
				}

				form.dirty = false;
				view.now = answer.body;
				toast( t( 'pdf.saved' ) );
			} );
		};

		reveal();

		const box = el( 'form', { id : 'layout-form', novalidate : 'novalidate', on : { submit : submit } },
			head( t( 'pdf.layout-title' ), el( 'span', { class : 'sub', text : t( 'pdf.layout-sub' ) } ), el( 'span', { class : 'grow' } ), previewButton, save ),
			tabs( 'layout' ),
			el( 'div', { class : 'stack' },
				el( 'div', { class : 'card pad' }, el( 'h3', { text : t( 'pdf.paper-font' ) } ), fields ),
				band( 'header', header, t( 'pdf.header' ) ),
				band( 'footer', footer, t( 'pdf.footer' ) ),
				el( 'div', { class : 'cols' }, pictureCard( inner, 'logo', view ), pictureCard( inner, 'signature', view ) ),
				el( 'p', { class : 'state-note', text : t( 'pdf.layout-note' ) } ) ) );

		inner.main.appendChild( box );
		submitter( 'layout', box, save );
	};

	// --- The design ----------------------------------------------------------------------------------------------------

	/**
	 *	Select a line of a text in its field, so that the person lands where a refusal says
	 *
	 *	@param		{Object}	area
	 *	@param		{number}	line						From 1
	 *
	 *	@return		{void}
	 */
	const selectLine = function( area, line ) {

		const lines = area.value.split( '\n' );

		if( line < 1 || line > lines.length )
			return;

		const from = lines.slice( 0, line - 1 ).reduce( function( sum, one ) { return sum+ one.length+ 1 }, 0 );

		area.focus();
		area.selectionStart = from;
		area.selectionEnd = from+ lines[line - 1].length;
	};

	/**
	 *	@param		{Object}	inner
	 *
	 *	@return		{void}
	 */
	const designScreen = function( inner ) {

		if( permitted( inner ) === false )
			return;

		read( inner, [ [ 'crm-pdf/layout-get' ] ], function( body ) { drawDesign( inner, body ) } );
	};

	/**
	 *	The screen Design: the rules of the feature as they are, read only, and the owner's rules over them with the refusal that a text which is no sheet gets - the line, and the sentence - and a preview of the design as it is saved
	 *
	 *	@param		{Object}	inner
	 *	@param		{Object}	body						The answer of crm-pdf/layout-get
	 *
	 *	@return		{void}
	 */
	const drawDesign = function( inner, body ) {

		const form = formOf();
		const area = el( 'textarea', { id : 'f-design', class : 'sheet', rows : 12, spellcheck : 'false', maxlength : String( body.ranges.design ), placeholder : t( 'pdf.design-placeholder' ), 'aria-label' : t( 'pdf.design-own' ), value : body.layout.design } );
		const said = el( 'p', { class : 'state-note bad', id : 'design-refusal', role : 'alert' } );
		const refused = body.refusal !== null && body.refusal !== undefined;
		const stored = el( 'p', { class : 'note bad', id : 'design-stored', role : 'alert', hidden : refused === false, text : refused === false ? '' : t( 'pdf.design-stored', errorText( { status : 400, body : body.refusal } ) ) } );

		form.controls.design = area;
		form.notes.design = said;
		area.addEventListener( 'input', function() {
			area.classList.remove( 'err' );
			said.textContent = '';
			form.dirty = true;
		} );

		const save = el( 'button', { type : 'submit', class : 'btn primary', id : 'design-save', title : t( 'save.shortcut' ), text : t( 'pdf.save' ) } );
		const previewButton = el( 'button', { type : 'button', class : 'btn', id : 'design-preview', text : t( 'pdf.preview' ), on : { click : function() { preview( inner, previewButton, 'crm-pdf/layout-preview' ) } } } );

		const submit = function( event ) {

			event.preventDefault();
			save.disabled = true;

			api( 'crm-pdf/layout-save', Object.assign( layoutPost( body.layout ), { design : area.value } ) ).then( function( answer ) {

				save.disabled = false;

				if( inner.alive() === false )
					return;

				if( answer.ok !== true ) {

					const sentence = refuseAt( form, answer, 'pdf.error-save' );
					const params = answer.body !== null && typeof answer.body === 'object' && Array.isArray( answer.body.params ) === true ? answer.body.params : [];

					// A refusal of the design names a line: it is selected
					if( answer.body !== null && typeof answer.body === 'object' && answer.body.field === 'design' && /^pdf_design_/.test( String( answer.body.code ) ) === true && Number.isInteger( Number( params[0] ) ) === true ) {
						said.textContent = sentence;
						selectLine( area, Number( params[0] ) );
					}

					return;
				}

				form.dirty = false;
				body = answer.body;
				stored.hidden = true;
				stored.textContent = '';
				toast( t( 'pdf.saved' ) );
			} );
		};

		reveal();

		const box = el( 'form', { id : 'design-form', novalidate : 'novalidate', on : { submit : submit } },
			head( t( 'pdf.design-title' ), el( 'span', { class : 'sub', text : t( 'pdf.design-sub' ) } ), el( 'span', { class : 'grow' } ), previewButton, save ),
			tabs( 'design' ),
			stored,
			el( 'div', { class : 'cols' },
				el( 'div', { class : 'card pad' }, el( 'h3', { text : t( 'pdf.design-sheet' ) } ), el( 'pre', { class : 'sheet ro', id : 'design-sheet', tabIndex : 0, text : body.sheet } ), el( 'p', { class : 'state-note', text : t( 'pdf.design-sheet-note' ) } ) ),
				el( 'div', { class : 'card pad' }, el( 'h3', { text : t( 'pdf.design-own' ) } ), area, said, el( 'p', { class : 'state-note', text : t( 'pdf.design-own-note' ) } ) ) ) );

		inner.main.appendChild( box );
		submitter( 'design', box, save );
	};

	// --- The places of the surface the screens dock to -----------------------------------------------------------------

	const SCREENS = { templates : templatesScreen, blocks : blocksScreen, layout : layoutScreen, design : designScreen, template : templateScreen, block : blockScreen };

	crm.pages.add( { key : 'pdf', hash : 'pdf', title : function() { return t( 'pdf.title' ) }, parse : parse, draw : function( inner, where ) {
		SCREENS[where.screen]( inner, where );
	} } );

	crm.menu.add( { key : 'pdf', word : function() { return t( 'pdf.nav' ) }, rank : 800, page : 'pdf', visible : allowed } );
}( window, document ) );
