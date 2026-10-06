/**
 *	Nino Template Builder — visual Section Library and progressive composer.
 */

( function(wn,dc) {

	'use strict';

	const pd = Nino.admin.templates;
	// Scripts are still removed and denied by CSP. allow-scripts only prevents
	// browser extensions from producing one sandbox warning per srcdoc frame;
	// omitting allow-same-origin keeps every preview in an opaque origin.
	const PREVIEW_SANDBOX = 'allow-scripts';

	function element( tag, className, text ) {
		const node = dc.createElement( tag );
		if( className )
			node.className = className;
		if( text !== undefined )
			node.textContent = text;
		return node;
	}

	function clone( value ) {
		return JSON.parse( JSON.stringify( value ) );
	}

	function humanize( value ) {
		return pd.sectionsUI.humanize( value );
	}

	function matchesPreset( preset, query, category ) {
		const categoryMatch = category === '*' || preset.category === category;
		const needle = String( query || '' ).trim().toLowerCase();
		const haystack = [ Nino.adminUi.text( preset.name ), Nino.adminUi.text( preset.description ), Nino.adminUi.text( preset.category ) ].concat( preset.tags || [] ).join(' ').toLowerCase();
		return categoryMatch && ( needle === '' || haystack.includes( needle ) );
	}

	/*	There used to be an isAreaPreset( preset ) beside this, asking whether
		a preset's version is 3, and four places asked it. Library::presets()
		drops every manifest whose version is not 3 before the panel ever sees
		one, so over pd._library.presets it was a tautology - a find() for it
		was the first entry, a filter() for it was the whole list.

		And a gallery of reusable includes: reusableIncludes(), matchesInclude(),
		selectedInclude(), selectInclude() and a _includePath they turned on.
		Nothing called selectInclude(), so _includePath was null from the first
		line to the last, and every branch that asked about it had one answer.
		The includes themselves are not gone - area-composer.js offers them
		where an area takes one.

		And the composer from before named areas: a module catalogue the
		library answered beside its presets (Composer::modules(), 28 section
		types), a moduleFor() over it, and a single-screen settings form with
		its own fields, summary, validation and submit, which area-composer.js
		stood in front of for every preset whose version is 3 - which is every
		preset there is. Only a preset of another version reached them, and
		the library hands the panel none. The configuration of a section is
		area-composer.js's, from the first step to the insert.	*/

	function selectedPreset() {
		return pd._library.presets.find( function( preset ) { return preset.key === pd.composer._presetKey } ) || null;
	}

	function presetKind( preset ) {
		return Nino.content.getText('/_admin/templates/label/preset-areas').replace( '%d', String( Object.keys( preset && preset.areas || {} ).length ) );
	}

	function escapeAttribute( value ) {
		return String( value || '' ).replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' ).replace( /</g, '&lt;' );
	}

	function escapeStyleText( value ) {
		return String( value || '' ).replace( /<\/style/gi, '<\\/style' );
	}

	function sanitizePreviewMarkup( markup ) {
		return String( markup || '' )
			.replace( /<script\b[^>]*>[\s\S]*?<\/script\s*>/gi, '' )
			.replace( /<script\b[^>]*>[\s\S]*$/gi, '' )
			.replace( /<\/?script\b[^>]*>/gi, '' )
			.replace( /\s+on[a-z0-9:_-]+\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+)/gi, '' )
			.replace( /\s+(href|src|action|formaction|xlink:href)\s*=\s*(["'])\s*javascript:[\s\S]*?\2/gi, ' $1="#"' )
			.replace( /\s+(href|src|action|formaction|xlink:href)\s*=\s*javascript:[^\s>]*/gi, ' $1="#"' );
	}

	/**
	 *	The sandboxed preview page around one composed section.
	 *
	 *	focusArea is the area the panel is editing. The composer marks every
	 *	area of a preview with data-pd-area, so naming one here dims the rest -
	 *	the frame then says which part of the section the controls belong to
	 *	without a second legend. A key that is not a slug is ignored rather
	 *	than escaped: it could only come from a manifest, and every area key a
	 *	manifest may declare is one.
	 *
	 *	@param		{string}	markup
	 *	@param		{string}	[focusArea]
	 *
	 *	@return		{string}
	 */
	function previewDocument( markup, focusArea ) {
		const origin = wn.location && /^https?:$/.test( wn.location.protocol ) ? wn.location.origin : '';
		const projectSource = origin ? ' '+ origin : '';
		const policy = "default-src 'none'; style-src 'unsafe-inline'; img-src data:"+ projectSource+ '; font-src data:'+ projectSource+ '; media-src'+ projectSource+ "; script-src 'none'; frame-src 'none'; connect-src 'none'; form-action 'none'; base-uri 'none'";
		const projectCss = escapeStyleText( pd._library && pd._library.previewCss || '' );
		const focus = /^[a-z][a-z0-9-]*$/.test( focusArea || '' )
			? '[data-pd-area]:not([data-pd-area="'+ focusArea+ '"]){opacity:.5}'
			: '';
		const previewCss = 'html,body{min-height:100%;margin:0}body{overflow:auto}a,button,input,textarea,select,form{pointer-events:none!important}'
			+ '[data-cover-height="50"]{min-height:50vh!important}[data-cover-height="75"]{min-height:75vh!important}'
			+ '[data-cover-height="90"]{min-height:90vh!important}[data-cover-height="100"]{min-height:100vh!important}'
			+ '.nino-parallex>img{top:0!important;height:100%!important;transform:none!important}'
			+ focus;
		return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="Content-Security-Policy" content="'+ escapeAttribute( policy )+ '">'
			+ '<style>'+ projectCss+ '\n'+ previewCss+ '</style>'
			+ '</head><body>'+ sanitizePreviewMarkup( markup )+ '</body></html>';
	}

	function fitPreviewFrame( frame ) {
		if( !frame )
			return;
		const iframe = frame.querySelector('iframe');
		if( !iframe || frame.clientWidth === 0 )
			return;
		const width = Number( frame.dataset.viewportWidth || 1200 );
		const height = Number( frame.dataset.viewportHeight || 760 );
		const scale = frame.clientWidth / width;
		iframe.style.width = width+ 'px';
		iframe.style.height = height+ 'px';
		iframe.style.transform = 'scale('+ scale+ ')';
		iframe.setAttribute( 'sandbox', PREVIEW_SANDBOX );
		frame.style.height = Math.max( 1, Math.round( height * scale ) )+ 'px';
	}

	function fitPreviewFrames() {
		dc.querySelectorAll('.pd-real-preview').forEach( fitPreviewFrame );
	}

	function setPreviewFrame( frame, markup, title, focusArea ) {
		if( !frame )
			return;
		const iframe = frame.querySelector('iframe');
		if( !iframe )
			return;
		const source = previewDocument( markup, focusArea );
		iframe._pdMarkup = markup;
		iframe.title = title || Nino.content.getText('/_admin/templates/label/preview');
		if( iframe._pdSource !== source ) {
			iframe._pdSource = source;
			iframe.srcdoc = source;
		}
		wn.requestAnimationFrame( function() { fitPreviewFrame( frame ) } );
	}

	Object.assign( pd, { composer : {

		matchesPreset : matchesPreset,
		previewDocument : previewDocument,
		_context : null,
		_presetKey : null,
		_category : '*',
		_libraryCards : {},
		_librarySignature : null,
		_draft : null,
		_step : 'library',
		_idTouched : false,
		_previewTimer : null,
		_previewToken : 0,
		_contentToken : 0,
		_textEntries : [],
		_textValues : {},
		_touched : new Set(),

		libraryReady : function() {
			if( pd.composer._presetKey === null && pd._library.presets.length )
				pd.composer._presetKey = pd._library.presets[0].key;
			const dialog = dc.getElementById('pd-composer');
			if( dialog && dialog.open && pd.composer._step === 'library' ) {
				pd.composer.renderCategories();
				pd.composer.renderLibrary();
			}
		},

		/**
		 *	What a category chip is called. Two of them are this panel's own -
		 *	'*' for everything and 'tpl' for the reusable templates - and are
		 *	named here; every other chip is a section preset's own category,
		 *	which the manifest supplies
		 *
		 *	@param		{string}	category
		 *
		 *	@return		{string}
		 */
		categoryLabel : function( category ) {
			if( category === '*' )
				return Nino.content.getText('/_admin/templates/label/category-all');
			return category;
		},

		open : function( context ) {
			if( pd._current === null || pd._library.presets.length === 0 ) {
				pd.toast( Nino.content.getText('/_admin/templates/msg/library-loading'), true );
				return;
			}

			context = Object.assign( { mode : 'insert', afterId : null, targetId : null, spec : null }, context || {} );
			pd.composer._context = context;
			pd.composer._idTouched = context.spec !== null;
			pd.composer._category = '*';
			pd.composer._step = 'library';
			pd.composer._textEntries = [];
			pd.composer._textValues = {};
			pd.composer._touched = new Set();
			if( pd.areaComposer )
				pd.areaComposer._areaKey = '';

			const fallback = pd._library.presets[0].key;
			const requested = context.spec && context.spec.preset ? context.spec.preset : fallback;
			pd.composer._presetKey = pd._library.presets.some( function( preset ) { return preset.key === requested } ) ? requested : fallback;
			const preset = selectedPreset();
			const suggestedId = pd.model.nextId( pd._current.segments, preset.key );
			pd.composer._draft = clone( context.spec || preset.defaults );
			pd.composer._draft.preset = preset.key;
			pd.composer._draft.pageId = pd._current.pageId;
			pd.composer._draft.pageMotion = pd._pageMotion;
			pd.composer._draft.id = context.spec && context.spec.id ? context.spec.id : suggestedId;
			if( !context.spec )
				pd.composer.resetGeneratedBindings();
			// An edit skips the library - the section already carries its preset
			// - and lands on the design step
			if( context.mode === 'replace' )
				pd.composer._step = 'design';

			const dialog = dc.getElementById('pd-composer');
			dialog.classList.toggle( 'is-edit', context.mode === 'replace' );
			dialog.classList.toggle( 'is-add', context.mode !== 'replace' );
			dc.getElementById('pd-composer-title').textContent = context.mode === 'replace' ? Nino.content.getText('/_admin/templates/label/composer-edit') : Nino.content.getText('/_admin/templates/label/composer-add');
			dc.getElementById('pd-compose-submit').textContent = context.mode === 'replace' ? Nino.content.getText('/_admin/templates/label/composer-update') : Nino.content.getText('/_admin/templates/label/composer-insert');
			dc.getElementById('pd-composer-error').textContent = '';
			dc.getElementById('pd-library-search').value = '';
			pd.composer.render();
			dialog.showModal();
			wn.requestAnimationFrame( fitPreviewFrames );

			const activeContext = context;
			Promise.all( [ pd.sectionsUI.ensureTypes(), pd.sectionsUI.ensureImages(), pd.api( 'content/keys', {} ) ] ).then( function( responses ) {
				pd.composer._textEntries = responses[2].entries || [];
				if( pd.areaComposer )
					pd.areaComposer.reconcileAvailableCollections();
				if( pd.composer._context === activeContext && pd.composer.configStep() === true )
					pd.composer.renderSettings();
			} ).catch( function() {} );
		},

		selectPreset : function( key ) {
			const preset = pd._library.presets.find( function( entry ) { return entry.key === key } );
			if( !preset )
				return;
			if( key === pd.composer._presetKey ) {
				pd.composer.renderLibrary();
				return;
			}

			const keep = pd.composer._draft || {};
			pd.composer._presetKey = key;
			const suggestedId = pd.model.nextId( pd._current.segments, key );
			pd.composer._draft = clone( preset.defaults );
			pd.composer._draft.preset = key;
			pd.composer._draft.pageId = pd._current.pageId;
			pd.composer._draft.pageMotion = pd._pageMotion;
			pd.composer._draft.id = pd.composer._idTouched ? keep.id : suggestedId;
			pd.composer.resetGeneratedBindings();
			pd.composer._textValues = {};
			pd.composer._touched = new Set();
			pd.composer.renderLibrary();
		},

		/*	The dialog has three steps: the library, then the design of the
			section and then its content. Deciding how a section looks and
			filling it with content are two jobs, and doing both on one screen
			is the wall of controls this dialog was accused of being. An edit
			skips the library - the section already has its preset - and walks
			the other two	*/
		STEPS : [ 'library', 'design', 'content' ],

		/**
		 *	Whether the dialog is past the library, on the design or the
		 *	content step - the one question every renderer asks
		 *
		 *	@return		{boolean}
		 */
		configStep : function() {
			return pd.composer._step !== 'library';
		},

		/**
		 *	The area the preview frame should put in front, '' for a preview
		 *	that stays evenly lit. Both configuration steps carry the area tabs,
		 *	so both point the frame at the area whose editor is open
		 *
		 *	@return		{string}
		 */
		previewFocus : function() {
			return pd.areaComposer && typeof pd.areaComposer.previewFocus === 'function' ? pd.areaComposer.previewFocus() : '';
		},

		/**
		 *	Dim the preview for the area that just became the active one,
		 *	reusing the markup the frame already has - switching tabs changes
		 *	which area is in front, not what the section renders
		 *
		 *	@return		void
		 */
		refocusPreview : function() {
			const frame = dc.getElementById('pd-composer-preview');
			const iframe = frame ? frame.querySelector('iframe') : null;
			if( !iframe || typeof iframe._pdMarkup !== 'string' )
				return;
			setPreviewFrame( frame, iframe._pdMarkup, iframe.title, pd.composer.previewFocus() );
		},

		/**
		 *	The step the primary button leads to, '' where it is the last one
		 *	and the button submits instead
		 *
		 *	@return		{string}
		 */
		nextStep : function() {
			if( pd.composer._step === 'library' )
				return 'design';
			return pd.composer._step === 'design' ? 'content' : '';
		},

		/**
		 *	One step back: out of the content step into the design step, and
		 *	out of the design step into the library
		 *
		 *	@return		void
		 */
		back : function() {
			pd.composer.setStep( pd.composer._step === 'content' ? 'design' : 'library' );
		},

		setStep : function( step ) {
			if( pd.composer.STEPS.includes( step ) === false )
				return;
			if( step === 'library' && pd.composer._context && pd.composer._context.mode === 'replace' )
				return;
			pd.composer._step = step;
			pd.composer.renderStep();
			if( step !== 'library' ) {
				pd.composer.renderConfiguration();
				pd.composer.loadTextValues();
				wn.requestAnimationFrame( fitPreviewFrames );
			} else {
				pd.composer.renderLibrary();
				dc.getElementById('pd-library-search').focus();
			}
		},

		render : function() {
			pd.composer.renderCategories();
			pd.composer.renderLibrary();
			pd.composer.renderStep();
			if( pd.composer.configStep() === true ) {
				pd.composer.renderConfiguration();
				pd.composer.loadTextValues();
			}
		},

		renderStep : function() {
			const library = dc.getElementById('pd-composer-library-step');
			const config = dc.getElementById('pd-composer-config-step');
			const back = dc.getElementById('pd-compose-back');
			const next = dc.getElementById('pd-compose-next');
			const submit = dc.getElementById('pd-compose-submit');
			const onLibrary = pd.composer._step === 'library';
			const editing = pd.composer._context && pd.composer._context.mode === 'replace';
			const nextStep = pd.composer.nextStep();
			library.classList.toggle( 'pd-hidden', !onLibrary );
			config.classList.toggle( 'pd-hidden', onLibrary );
			back.classList.toggle( 'pd-hidden', onLibrary || ( editing && pd.composer._step !== 'content' ) );
			back.textContent = pd.composer._step === 'content'
				? Nino.content.getText('/_admin/templates/label/back-to-design')
				: Nino.content.getText('/_admin/templates/label/back-to-library');
			next.classList.toggle( 'pd-hidden', nextStep === '' );
			// Three labels for one button: what it leads to is a different
			// thing on each step, and "Continue" three times over says none of
			// them
			if( nextStep !== '' )
				next.textContent = Nino.content.getText( '/_admin/templates/label/next-'+ nextStep );
			pd.composer.renderComposerHeading();
			submit.classList.toggle( 'pd-hidden', nextStep !== '' );
			pd.composer.renderStepper();
		},

		/**
		 *	The numbered strip in the dialog's header. Its first entry is
		 *	drawn only where there is a library to go back to - not in an edit -
		 *	and the numbers are written here rather than in the markup so that
		 *	two of them read 1-2 and three of them 1-2-3
		 *
		 *	@return		void
		 */
		renderStepper : function() {
			const editing = pd.composer._context && pd.composer._context.mode === 'replace';
			const active = pd.composer._step;
			const steps = [ [ 'pd-step-library', 'library', !editing ], [ 'pd-step-design', 'design', true ], [ 'pd-step-content', 'content', true ] ];
			// One step would be no progress to show, and the bar would say so by
			// not being there - an edit still walks two
			const stepper = dc.getElementById('pd-composer-stepper');
			if( stepper )
				stepper.classList.toggle( 'pd-hidden', steps.filter( function( entry ) { return entry[2] } ).length < 2 );
			let number = 0;
			steps.forEach( function( entry ) {
				const item = dc.getElementById( entry[0] );
				if( !item )
					return;
				item.classList.toggle( 'pd-hidden', entry[2] === false );
				if( entry[2] === false ) {
					item.classList.remove('is-active');
					item.removeAttribute('aria-current');
					return;
				}
				const index = item.querySelector('span');
				if( index )
					index.textContent = String( ++number );
				item.classList.toggle( 'is-active', entry[1] === active );
				if( entry[1] === active )
					item.setAttribute( 'aria-current', 'step' );
				else
					item.removeAttribute('aria-current');
			} );
		},

		renderCategories : function() {
			const wrap = dc.getElementById('pd-library-categories');
			wrap.innerHTML = '';
			const scopedPresets = pd._library.presets;
			// '*' is this panel's own chip rather than a preset's category, so it
			// is a slug: it is compared as well as shown, and a comparison
			// against a translated word would hold in one language only. The
			// rest come from the section library's manifests
			const categories = [ '*' ].concat( Array.from( new Set( scopedPresets.map( function( preset ) { return preset.category } ) ) ).sort() );
			categories.forEach( function( category ) {
				const count = category === '*' ? scopedPresets.length : scopedPresets.filter( function( preset ) { return preset.category === category } ).length;
				const button = element( 'button', 'pd-chip'+ ( pd.composer._category === category ? ' is-active' : '' ) );
				button.type = 'button';
				button.append( element( 'span', '', pd.composer.categoryLabel( category ) ), element( 'small', '', String( count ) ) );
				button.addEventListener( 'click', function() {
					pd.composer._category = category;
					pd.composer.renderCategories();
					pd.composer.renderLibrary();
				} );
				wrap.appendChild( button );
			} );
		},

		/**
		 *	One card per preset, built once and afterwards only shown or hidden.
		 *
		 *	A card does not depend on the search text - only on whether it
		 *	matches it - but the gallery used to be emptied and rebuilt on every
		 *	keystroke, and every rebuilt card carried a fresh <iframe> whose
		 *	srcdoc embeds the whole project stylesheet and its base64 fonts.
		 *	Typing five letters over the shipped library of seventeen wrote 29
		 *	preview documents, 2.8 MB of them, and 230 ms of main thread. Now it
		 *	toggles a class.
		 *
		 *	@return		void
		 */
		renderLibrary : function() {
			const wrap = dc.getElementById('pd-library-list');
			const search = dc.getElementById('pd-library-search');
			if( !wrap || !search )
				return;

			pd.composer.buildLibrary( wrap );

			let shown = 0;
			pd._library.presets.forEach( function( preset ) {
				const card = pd.composer._libraryCards[preset.key];
				if( !card )
					return;
				const match = matchesPreset( preset, search.value, pd.composer._category );
				const active = preset.key === pd.composer._presetKey;
				card.classList.toggle( 'pd-hidden', match === false );
				card.classList.toggle( 'is-active', active );
				const choose = card.querySelector('.pd-preset-select');
				if( choose )
					choose.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
				if( match )
					shown++;
			} );

			const empty = wrap.querySelector('.pd-library-empty');
			if( empty )
				empty.classList.toggle( 'pd-hidden', shown > 0 );

			wn.requestAnimationFrame( fitPreviewFrames );
		},

		/**
		 *	Fill the gallery, once.
		 *
		 *	Rebuilt when the library itself changed - a feature installed while
		 *	the panel is open reloads the presets - and when the workbench built
		 *	the shell again, which leaves the cached cards outside the new list
		 *	element. Both are read off what is there rather than announced.
		 *
		 *	@param		{Element}	wrap
		 *
		 *	@return		void
		 */
		buildLibrary : function( wrap ) {
			const signature = pd._library.presets.map( function( preset ) { return preset.key } ).join('|');
			const first = pd._library.presets.length ? pd.composer._libraryCards[ pd._library.presets[0].key ] : null;
			if( pd.composer._librarySignature === signature && first && first.parentNode === wrap )
				return;

			pd.composer._libraryCards = {};
			pd.composer._librarySignature = signature;
			wrap.innerHTML = '';

			pd._library.presets.forEach( function( preset ) {
				const card = element( 'article', 'pd-preset' );
				const frame = element( 'div', 'pd-real-preview' );
				// One viewport for every card: a gallery of tiles that are all
				// the same size compares presets, one of tiles in six heights
				// compares nothing
				frame.dataset.viewportWidth = '1200';
				frame.dataset.viewportHeight = '760';
				const iframe = element('iframe');
				iframe.loading = 'lazy';
				iframe.tabIndex = -1;
				iframe.setAttribute( 'sandbox', PREVIEW_SANDBOX );
				frame.appendChild( iframe );
				card.appendChild( frame );

				const copy = element( 'div', 'pd-preset-copy' );
				const meta = element( 'div', 'pd-preset-meta' );
				meta.appendChild( element( 'span', 'pd-preset-category', Nino.adminUi.text( preset.category ) ) );
				const facts = [ presetKind( preset ), preset.layouts && preset.layouts[preset.recommend.layout] ? Nino.adminUi.text( preset.layouts[preset.recommend.layout].label ) : '' ];
				const visibleFacts = facts.filter( function( fact ) { return fact && ![ 'none', 'auto' ].includes( fact ) } );
				if( visibleFacts.length < 2 && ( preset.tags || [] ).length )
					visibleFacts.push( preset.tags[0] );
				visibleFacts.slice( 0, 2 ).forEach( function( fact ) { meta.appendChild( element( 'span', '', humanize( fact ) ) ); } );
				copy.append( meta, element( 'strong', '', Nino.adminUi.text( preset.name ) ), element( 'p', '', Nino.adminUi.text( preset.description ) ) );
				card.appendChild( copy );

				const choose = element( 'button', 'pd-preset-select' );
				choose.type = 'button';
				choose.setAttribute( 'aria-label', Nino.content.getText('/_admin/templates/label/choose').replace( '%s', Nino.adminUi.text( preset.name ) ) );
				choose.addEventListener( 'click', function() { pd.composer.selectPreset( preset.key ) } );
				card.appendChild( choose );

				wrap.appendChild( card );
				pd.composer._libraryCards[preset.key] = card;
				setPreviewFrame( frame, preset.preview || '', Nino.content.getText('/_admin/templates/label/preset-preview').replace( '%s', Nino.adminUi.text( preset.name ) ) );
			} );

			const empty = element( 'div', 'pd-library-empty pd-hidden' );
			empty.append( element( 'strong', '', Nino.content.getText('/_admin/templates/empty/library') ), element( 'p', '', Nino.content.getText('/_admin/templates/empty/library-detail') ) );
			wrap.appendChild( empty );
		},

		renderConfiguration : function() {
			pd.composer.renderSelectedPreset();
			pd.composer.renderSettings();
			pd.composer.renderSummary();
			pd.composer.requestPreview( true );
		},

		/**
		 *	The dialog's own heading says what it is doing while a preset is
		 *	still being picked, and says which one was picked once step 2 is on
		 *	screen - where naming the dialog again is the one thing nobody needs
		 *	and the preset's name is what everything below refers to. The
		 *	description went with it: it sold the preset in the library, and the
		 *	preview beside this shows the thing itself.
		 *
		 *	@return		void
		 */
		renderComposerHeading : function() {
			const eyebrow = dc.querySelector('.pd-composer-heading .pd-eyebrow');
			const title = dc.getElementById('pd-composer-title');
			const preset = selectedPreset();
			if( !eyebrow || !title )
				return;
			if( pd.composer.configStep() === true && preset ) {
				eyebrow.textContent = Nino.adminUi.text( preset.category )+ ' · '+ presetKind( preset );
				title.textContent = Nino.adminUi.text( preset.name );
				return;
			}
			eyebrow.textContent = Nino.content.getText('/_admin/templates/label/section-composer');
			title.textContent = pd.composer._context && pd.composer._context.mode === 'replace'
				? Nino.content.getText('/_admin/templates/label/composer-edit')
				: Nino.content.getText('/_admin/templates/label/composer-add');
		},

		renderSelectedPreset : function() {
			const wrap = dc.getElementById('pd-selected-preset');
			const preset = selectedPreset();
			if( !wrap || !preset )
				return;
			wrap.innerHTML = '';
			pd.composer.renderComposerHeading();
			// What is left of this block is the way back to the library. In
			// replace mode there is none - the preset of an existing section is
			// not something this dialog changes
			if( pd.composer._context && pd.composer._context.mode === 'replace' )
				return;
			const change = element( 'button', '', Nino.content.getText('/_admin/templates/label/change-preset') );
			change.type = 'button';
			change.addEventListener( 'click', function() { pd.composer.setStep('library') } );
			wrap.appendChild( change );
		},

		captureValues : function() {
			const wrap = dc.getElementById('pd-composer-settings');
			if( !wrap )
				return;
			wrap.querySelectorAll('[data-text-key]').forEach( function( input ) {
				pd.composer._textValues[input.dataset.textKey] = input.value;
			} );
		},

		requestPreview : function( immediate ) {
			const draft = pd.composer._draft;
			if( !draft || pd.composer.configStep() === false )
				return;
			wn.clearTimeout( pd.composer._previewTimer );
			const token = ++pd.composer._previewToken;
			const status = dc.getElementById('pd-preview-status');
			status.textContent = Nino.content.getText('/_admin/templates/msg/updating');
			pd.composer._previewTimer = wn.setTimeout( function() {
				pd.api( 'library/preview', Object.assign( {}, draft, { texts : pd.areaComposer ? pd.areaComposer.previewTexts() : {} } ) ).then( function( response ) {
					if( token !== pd.composer._previewToken )
						return;
					setPreviewFrame( dc.getElementById('pd-composer-preview'), response.html || '', Nino.content.getText('/_admin/templates/label/live-preview').replace( '%s', Nino.adminUi.text( selectedPreset().name ) ), pd.composer.previewFocus() );
					status.textContent = Nino.content.getText('/_admin/templates/msg/current');
				} ).catch( function( error ) {
					if( token === pd.composer._previewToken )
						status.textContent = error.message;
				} );
			}, immediate ? 0 : 180 );
		},

		cancelAsync : function() {
			wn.clearTimeout( pd.composer._previewTimer );
			pd.composer._previewToken++;
			pd.composer._contentToken++;
		},

		init : function() {
			const form = dc.getElementById('pd-composer-form');
			if( !form )
				return;
			form.addEventListener( 'submit', function( event ) {
				event.preventDefault();
				const nextStep = pd.composer.nextStep();
				if( nextStep !== '' )
					pd.composer.setStep( nextStep );
				else
					pd.composer.submit();
			} );
			dc.getElementById('pd-library-search').addEventListener( 'input', pd.composer.renderLibrary );
			dc.getElementById('pd-compose-next').addEventListener( 'click', function() {
				const nextStep = pd.composer.nextStep();
				if( nextStep !== '' )
					pd.composer.setStep( nextStep );
			} );
			dc.getElementById('pd-compose-back').addEventListener( 'click', pd.composer.back );
			dc.querySelectorAll('.pd-dialog-close').forEach( function( close ) {
				close.addEventListener( 'click', function() { dc.getElementById('pd-composer').close() } );
			} );
			dc.getElementById('pd-composer').addEventListener( 'close', pd.composer.cancelAsync );
			wn.addEventListener( 'resize', function() { wn.requestAnimationFrame( fitPreviewFrames ) } );
			pd.composer.libraryReady();
		},
	} } );

	Nino.events.bindCallback( 'ready', pd.composer.init );

})(window, document);
