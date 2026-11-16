/**
 *	Nino features
 *	Themes						The panel of the workbench for the themes: the list of them, one theme as the groups of components and
 *										their parts, the settings of one group - the part it comes from, the standard variant and the knobs of
 *										each component - and the website beside them as the draft draws it. Everything it shows is the
 *										answer of the API (see Modules\Themes\Admin): the saved state of a theme from open, the draft
 *										from preview. A change in the panel is a change of the working mix; the mix is sent to preview
 *										once the hand has rested, and the page in the frame is loaded once more with the token the answer
 *										gives. Nothing is written before Save.
 *
 *										The address is #themes (the list), #themes/<theme> (the groups) and
 *										#themes/<theme>/<group>/<component> (the settings of a group, one component open), for the
 *										page <group> is page and the section a set of its knobs.
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */

( function(wn,dc) {

	wn.Nino.admin = wn.Nino.admin || {};

	// The steps of a knob, how long the hand rests before the draft is asked for, how long before a token runs out - in milliseconds - a
	// new one is asked for, and the widths of the preview
	const STEPS		= [ -2, -1, 0, 1, 2 ];
	const QUIET		= 150;
	const GRACE		= 5000;
	const ANNOUNCED	= 4000;
	const WIDTHS	= [ 'mobile', 'tablet', 'desktop' ];

	// The width a page is laid out at for each of them: the frame has it and is scaled down to the room the preview has
	const LAYOUT	= { mobile : 390, tablet : 768, desktop : 1280 };

	// Below this width of the preview - in pixels - a person who has chosen no width is shown the page as a phone shows it
	const NARROW	= 520;

	// The page has no components: its knobs stand in these sections, in this order, and a knob that is in none stands in 'more'
	const PAGE_SECTIONS	= [ [ 'type', [ 'size', 'text', 'weight' ] ], [ 'measures', [ 'width', 'space', 'radius', 'line', 'density' ] ], [ 'motion', [ 'motion' ] ] ];
	const MORE					= 'more';

	// The names of the sections on the first level of a theme: the groups, and the two of the second part of the panel. No group of the register
	// takes one of them, and a group that is called one is not shown
	const RESERVED	= [ 'groups', 'brand', 'settings' ];

	// A value of a ladder that is written beside the name of its knob as it stands: a length, a number, a share. A calculation is measured
	const PLAIN_VALUE		= /^-?\d*\.?\d+[a-z%]{0,3}$/;
	const CALCULATED		= /calc\(|var\(|min\(|max\(|clamp\(/;
	const LENGTH				= /^-?\d*\.?\d+(?:px|rem|em|ch|vw|vh|pt)$/;

	/**
	 *	What the panel holds: the list, the theme that is open with its saved description and its working mix, the
	 *	description of the draft and the token of its preview
	 */
	const S = {
		list			: null,
		ready			: false,
		key				: '',
		theme			: null,
		saved			: null,
		mix				: null,
		described	: null,
		active		: null,
		token			: '',
		until			: 0,
		query			: '',
		page			: '/',
		width			: 'desktop',
		mark			: '',
		timer			: null,
		inFlight	: false,
		again			: false,
		waiting		: [],
		structural : null,
		view			: null,
		at				: [],
		chosen		: false,
		hints			: [],
		healed		: '',
		said			: false,
		announced	: false,
		stamp			: '',
	};

	/**
	 *	A word of the panel in the language of the interface: a fill /_admin/themes/<key>
	 *
	 *	@param		{string}		key
	 *	@param		{...*}			params			What goes where the text has %s, %d or %n
	 *
	 *	@return		{string}
	 */
	const word = function( key, ...params ) {
		return Nino.adminUi.format( Nino.content.getText( '/_admin/themes/'+ key ), ...params );
	};

	/**
	 *	A text the API sends as a locale => text map, in the language of the interface; the English one where there is
	 *	none of it, else the first
	 *
	 *	@param		{string|Object}		value
	 *
	 *	@return		{string}
	 */
	const localized = function( value ) {

		if( typeof value === 'string' )
			return value;

		if( value === null || typeof value !== 'object' )
			return '';

		const tag			= String( ( dc.documentElement && dc.documentElement.lang ) || '' ).replace( '-', '_' );
		const names		= Object.keys( value );
		const near		= names.find( function( name ) { return tag !== '' && name.slice( 0, 2 ) === tag.slice( 0, 2 ) } );

		return String( value[tag] ?? ( near !== undefined ? value[near] : undefined ) ?? value.en_US ?? value[names[0]] ?? '' );
	};

	const clone = function( value ) {
		return JSON.parse( JSON.stringify( value ) );
	};

	const el = function( tag, className, text ) {
		return Nino.adminUi.el( tag, className, text );
	};

	// An element emptied, and the pieces put into it
	const fill = function( parent, ...pieces ) {

		parent.innerHTML = '';
		pieces.forEach( function( piece ) { parent.appendChild( piece ) } );

		return parent;
	};

	/**
	 *	The address of a theme: the key of an own theme is own:<key> for the API and own.<key> in the address, a form with nothing to be written
	 *	as a code in it; no theme of a package can have a dot in its key, so that the two kinds of key cannot meet
	 *
	 *	@param		{Array}		parts				[ theme, group, component ] with the key of the API
	 *
	 *	@return		{Array}									The same with the theme as the address says it
	 */
	const addressOf = function( parts ) {

		if( parts.length === 0 || parts[0].indexOf('own:') !== 0 )
			return parts;

		return [ 'own.'+ parts[0].slice( 4 ) ].concat( parts.slice( 1 ) );
	};

	// The address of the window, with the key of the API for the theme
	const here = function() {

		const at = Nino.admin.router.current();
		const parts = at.parts.slice();

		if( parts.length > 0 && parts[0].indexOf('own.') === 0 )
			parts[0] = 'own:'+ parts[0].slice( 4 );

		return { panel : at.panel, parts : parts };
	};

	// Whether a node is the root or stands below it
	const within = function( root, node ) {

		for( let at = node; at !== null && at !== undefined; at = at.parentNode )
			if( at === root )
				return true;

		return false;
	};

	// --- what the working mix says --------------------------------------------------------------------------

	/**
	 *	A mix as a string that says the same for the same mix: the parts in order, a part that stands for the standard part
	 *	and says nothing more left out, a knob on its middle step left out
	 *
	 *	@param		{Object}		mix
	 *
	 *	@return		{string}
	 */
	const canon = function( mix ) {

		const parts = {};

		Object.keys( mix.parts ?? {} ).sort().forEach( function( group ) {

			const entry		= mix.parts[group];
			const values	= {};
			const standard	= {};

			Object.keys( entry.values ?? {} ).sort().forEach( function( key ) {
				if( entry.values[key] !== 0 )
					values[key] = entry.values[key];
			} );

			Object.keys( entry.standard ?? {} ).sort().forEach( function( key ) {
				standard[key] = entry.standard[key];
			} );

			if( ( entry.from ?? 'standard' ) === 'standard' && Object.keys( values ).length === 0 && Object.keys( standard ).length === 0 )
				return;

			parts[group] = { from : entry.from ?? 'standard', values : values, standard : standard };
		} );

		return JSON.stringify( { css : mix.css ?? '', parts : parts } );
	};

	const isDirty = function() {
		return S.ready === true && canon( S.mix ) !== canon( S.saved.mix );
	};

	// The entry of the working mix for a group; one is made for a group the mix does not name
	const partOf = function( group ) {
		S.mix.parts = S.mix.parts ?? {};
		S.mix.parts[group] = S.mix.parts[group] ?? { from : 'standard' };
		return S.mix.parts[group];
	};

	const stepOf = function( mix, group, key ) {
		return ( ( ( mix.parts ?? {} )[group] ?? {} ).values ?? {} )[key] ?? 0;
	};

	// The component of a group a knob belongs to: <component>.<name>
	const componentOf = function( key ) {
		return key.indexOf('.') === -1 ? '' : key.slice( 0, key.indexOf('.') );
	};

	const described = function( group ) {
		return S.described.groups[group];
	};

	// The standard variant of a component as the draft has it: what the mix says, else what the part says
	const standardOf = function( group, name ) {

		const said = ( partOf( group ).standard ?? {} )[name];

		if( said !== undefined )
			return said;

		const found = described( group ).components.find( function( component ) { return component.name === name } );

		return found === undefined ? 'regular' : found.standard;
	};

	const savedStandardOf = function( group, name ) {

		const entry = ( S.saved.groups[group] ?? { components : [] } ).components.find( function( component ) { return component.name === name } );

		return entry === undefined ? 'regular' : entry.standard;
	};

	const groupLabel = function( group ) {
		return group === 'page' ? word('word/page') : localized( described( group ).label );
	};

	const themeName = function( key ) {

		const found = S.list === null ? undefined : S.list.themes.find( function( theme ) { return theme.key === key } );

		return found === undefined ? key : localized( found.name );
	};

	// Where the part of a group comes from, as a name: the standard part, a theme, or the project's copy of one
	const sourceOf = function( group ) {

		const entry = described( group );

		if( entry.theme === '' )
			return word('word/standard');

		return themeName( entry.theme )+ ( entry.package === 'missing' ? ' ('+ word('word/copy')+ ')' : '' );
	};

	// Whether a name is one of a group of the register: not one of the sections of the first level, and one the draft describes
	const isGroup = function( name ) {
		return RESERVED.indexOf( name ) === -1 && S.described.groups[name] !== undefined;
	};

	// The groups as a page is read from the top down: the page, the header, the rest by name in the language of the interface, the footer last.
	// The register's order is the order of its keys, which is none of these
	const groupKeys = function() {

		const names = S.list.groups.map( function( group ) { return group.key } ).filter( function( group ) { return group !== 'page' && isGroup( group ) === true && group !== 'header' && group !== 'footer' } );

		names.sort( function( a, b ) { return groupLabel( a ).localeCompare( groupLabel( b ) ) } );

		return [ 'page' ].concat( isGroup('header') === true ? [ 'header' ] : [], names, isGroup('footer') === true ? [ 'footer' ] : [] );
	};

	/**
	 *	The sections of a group, each a set of knobs and, for a component with more than one variant, the choice of its
	 *	standard variant: one for each component, and for the page the sets named above
	 *
	 *	@param		{string}		group
	 *
	 *	@return		{Array}										[ { key, label, component, variants, knobs } ]
	 */
	const sectionsOf = function( group ) {

		const entry = described( group );

		if( group === 'page' ) {

			const taken = [];
			const list = [];

			PAGE_SECTIONS.forEach( function( set ) {

				const knobs = entry.knobs.filter( function( knob ) { return set[1].indexOf( knob.key ) !== -1 } );
				knobs.forEach( function( knob ) { taken.push( knob.key ) } );

				if( knobs.length > 0 )
					list.push( { key : set[0], label : word( 'word/'+ set[0] ), component : null, variants : null, knobs : knobs } );
			} );

			const rest = entry.knobs.filter( function( knob ) { return taken.indexOf( knob.key ) === -1 } );

			if( rest.length > 0 )
				list.push( { key : MORE, label : word('word/more'), component : null, variants : null, knobs : rest } );

			return list;
		}

		const names = entry.components.map( function( component ) { return component.name } );

		// The components in the order their knobs come in, those with none after them in the order of the register
		const rank = function( name ) {
			const at = entry.knobs.findIndex( function( knob ) { return componentOf( knob.key ) === name } );
			return at === -1 ? entry.knobs.length : at;
		};

		const ordered = entry.components.map( function( component, at ) { return [ component, at ] } ).sort( function( x, y ) { return rank( x[0].name ) - rank( y[0].name ) || x[1] - y[1] } ).map( function( pair ) { return pair[0] } );

		const list = ordered.map( function( component ) {
			const label = localized( component.label );

			return {
				key			: component.name,
				// The register may call the group and its component by one word: the component is then told by its name too
				label		: label === groupLabel( group ) ? label+ ' ('+ component.name+ ')' : label,
				component	: component.name,
				variants	: component.variants.length > 1 ? component.variants : null,
				knobs		: entry.knobs.filter( function( knob ) { return componentOf( knob.key ) === component.name } ),
			};
		} );

		// A knob of the part for a component the group has not: it is shown, and not lost
		const rest = entry.knobs.filter( function( knob ) { return names.indexOf( componentOf( knob.key ) ) === -1 } );

		if( rest.length > 0 )
			list.push( { key : MORE, label : word('word/more'), component : null, variants : null, knobs : rest } );

		return list;
	};

	const variantLabel = function( variant ) {

		if( typeof variant.label === 'object' && variant.label !== null )
			return localized( variant.label );

		const known = Nino.content.getText( '/_admin/themes/variant/'+ variant.name );

		return known !== '' ? known : Nino.adminUi.humanize( variant.name );
	};

	// "Soft surface . 1 of 2 knobs changed": what a closed head of a section says
	const summaryOf = function( group, section ) {

		const moved = section.knobs.filter( function( knob ) { return stepOf( S.mix, group, knob.key ) !== 0 } ).length;
		const parts = [];

		if( section.variants !== null ) {
			const chosen = section.variants.find( function( variant ) { return variant.name === standardOf( group, section.component ) } );
			parts.push( chosen === undefined ? standardOf( group, section.component ) : variantLabel( chosen ) );
		}

		if( section.knobs.length > 0 ) {
			if( moved === 0 )
				parts.push( word( section.knobs.length === 1 ? 'msg/knob-one' : 'msg/knobs', section.knobs.length ) );
			else if( section.knobs.length === 1 )
				parts.push( word('msg/moved-one') );
			else
				parts.push( word( 'msg/moved', moved, section.knobs.length ) );
		}
		else if( section.variants === null )
			parts.push( word('msg/follows') );

		return parts.join(' · ');
	};

	// Whether a section differs from the saved state: the same part, and another variant or step in it
	const changedIn = function( group, section ) {

		const entry	= partOf( group );
		const was		= ( S.saved.mix.parts ?? {} )[group] ?? { from : 'standard' };

		if( ( was.from ?? 'standard' ) !== entry.from )
			return false;

		if( section.variants !== null && standardOf( group, section.component ) !== savedStandardOf( group, section.component ) )
			return true;

		return section.knobs.some( function( knob ) { return stepOf( S.mix, group, knob.key ) !== stepOf( S.saved.mix, group, knob.key ) } );
	};

	// Whether a group differs from the saved state: another part, or a section that does
	const groupChanged = function( group ) {

		const was = ( S.saved.mix.parts ?? {} )[group] ?? { from : 'standard' };

		return partOf( group ).from !== ( was.from ?? 'standard' ) || sectionsOf( group ).some( function( section ) { return changedIn( group, section ) } );
	};

	// What the ladder of a knob says at a step, where it is a plain length or number: calc() and var() say nothing beside a name
	const valueOf = function( knob, step ) {

		let value = knob.ladder[step + 2];

		if( typeof value === 'object' && value !== null ) {
			const tokens = Object.keys( value );
			value = tokens.length === 1 ? value[tokens[0]] : '';
		}

		return typeof value === 'string' && PLAIN_VALUE.test( value ) === true ? value : '';
	};

	/**
	 *	What a calculation comes to, in pixels: the value is given to a box in the page of the frame, which has the tokens of the draft, and the
	 *	box is measured. Nothing where the frame is not loaded, where the page cannot be read or where the value is none that a box can have
	 *
	 *	@param		{string}		value
	 *
	 *	@return		{string}
	 */
	const measure = function( value ) {

		try {
			const frame = S.view === null ? null : S.view.frame;

			if( frame === null || frame.getAttribute('aria-busy') === 'true' || frame.contentDocument === null || frame.contentDocument === undefined || !frame.contentDocument.body )
				return '';

			const probe = frame.contentDocument.createElement('div');

			probe.style.position = 'absolute';
			probe.style.visibility = 'hidden';
			probe.style.width = value;

			if( probe.style.width === '' )
				return '';

			frame.contentDocument.body.appendChild( probe );

			const width = probe.getBoundingClientRect().width;

			probe.remove();

			return isFinite( width ) === true && width > 0 ? ( Math.round( width * 10 ) / 10 )+ 'px' : '';
		} catch( e ) {
			return '';
		}
	};

	// What stands beside the name of a knob at a step: the value that is measured, in pixels, where it is a length or a calculation and the frame
	// has loaded the draft; else the value as it is written, where that is plain
	const hintOf = function( knob, step ) {

		let raw = knob.ladder[step + 2];

		if( typeof raw === 'object' && raw !== null )
			raw = Object.keys( raw ).length === 1 ? raw[Object.keys( raw )[0]] : '';

		if( typeof raw === 'string' && ( LENGTH.test( raw ) === true || CALCULATED.test( raw ) === true ) ) {

			const measured = measure( raw );

			if( measured !== '' )
				return measured;
		}

		return valueOf( knob, step );
	};

	// The values beside the knobs that are on screen, once more: the frame has loaded and has the tokens of the draft
	const refreshHints = function() {

		S.hints.forEach( function( entry ) {
			entry.hint.textContent = hintOf( entry.knob, stepOf( S.mix, entry.group, entry.knob.key ) );
		} );
	};

	// --- the draft --------------------------------------------------------------------------------------------

	/**
	 *	The mix as preview and save take it: the css and the parts of the working mix, and the name where the theme is an own one
	 *
	 *	@return		{Object}
	 */
	const sendable = function() {

		const mix = { parts : clone( S.mix.parts ?? {} ) };

		if( typeof S.mix.css === 'string' )
			mix.css = S.mix.css;

		if( S.theme.kind === 'own' )
			mix.name = S.theme.name;

		return mix;
	};

	/**
	 *	What the draft describes, taken in: the groups of the answer and what the mix may keep of them - a step of a knob the
	 *	part has, a variant the component has
	 *
	 *	@param		{Object}		answer				The body of open or of preview
	 *
	 *	@return		{Array}										What the person had set and the part does not have: the names of the knobs and of the variants
	 */
	const take = function( answer ) {

		const old = S.described;
		const dropped = [];

		S.described = answer;

		Object.keys( S.mix.parts ?? {} ).forEach( function( group ) {

			const entry = S.mix.parts[group];
			const known = answer.groups[group];

			if( known === undefined )
				return;

			Object.keys( entry.values ?? {} ).forEach( function( key ) {

				if( known.knobs.some( function( knob ) { return knob.key === key } ) === true )
					return;

				// What the person set and the new part has no knob for: told by its name
				const was = old !== null && old !== undefined && old.groups[group] !== undefined ? old.groups[group].knobs.find( function( knob ) { return knob.key === key } ) : undefined;

				if( entry.values[key] !== 0 )
					dropped.push( was === undefined ? key : localized( was.label ) );

				delete entry.values[key];
			} );

			Object.keys( entry.standard ?? {} ).forEach( function( name ) {

				const component = known.components.find( function( candidate ) { return candidate.name === name } );

				if( component !== undefined && component.variants.some( function( variant ) { return variant.name === entry.standard[name] } ) === true )
					return;

				const before = old !== null && old !== undefined && old.groups[group] !== undefined ? old.groups[group].components.find( function( candidate ) { return candidate.name === name } ) : undefined;
				const chosen = before === undefined ? undefined : before.variants.find( function( variant ) { return variant.name === entry.standard[name] } );

				dropped.push( word( 'msg/variant-of', chosen === undefined ? entry.standard[name] : variantLabel( chosen ), before === undefined ? name : localized( before.label ) ) );
				delete entry.standard[name];
			} );
		} );

		return dropped;
	};

	/**
	 *	The address of the page of the preview: the page, the token of the draft and the component to outline
	 *
	 *	@return		{string}
	 */
	const previewUri = function() {

		const dir = typeof Nino.dir === 'string' ? Nino.dir : '';

		// A page of this site and nothing else: a path that starts with one slash, as the list of the API gives them
		const page = /^\/(?![\/\\])/.test( S.page ) === true ? S.page : '/';

		return dir+ page+ '?'+ S.query+ ( S.mark === '' ? '' : '&mark='+ encodeURIComponent( S.mark ) );
	};

	const reloadFrame = function() {

		if( S.view === null || S.query === '' )
			return;

		// A token that has run out draws the page as the website has it, and says nothing: the draft is asked for again, which gives a new one.
		// The time it holds is counted from the answer, on this clock, so that a clock that is off cannot ask for ever
		if( S.until > 0 && Date.now() >= S.until ) {
			S.view.line.idle( word('msg/preview-renewed') );
			flush();
			return;
		}

		// The page replaces what the frame shows: a frame that is given another address adds a step to the history of the window, and the
		// Back key would have to walk back over every reload of the preview before it came to the address of the panel
		const frame = S.view.frame;

		frame.setAttribute( 'aria-busy', 'true' );

		try {
			frame.contentWindow.location.replace( previewUri() );
		} catch( e ) {
			frame.setAttribute( 'src', previewUri() );
		}
	};

	/**
	 *	A page the frame has loaded that is not the draft - a link in the preview was followed: the page is taken for the one that is chosen, where the
	 *	list has it, and loaded with the token; else the chosen page is loaded once more. A page that is loaded without the token twice in a row is
	 *	left as it is, so that a page that sends the frame elsewhere cannot keep it loading for ever
	 *
	 *	@return		{void}
	 */
	const heal = function() {

		if( S.view === null || S.query === '' )
			return;

		// A frame that has no window has no page yet
		if( S.view.frame.contentWindow === null || S.view.frame.contentWindow === undefined )
			return;

		// A page of another site cannot be read: what cannot be read is no page of the preview
		let href = '';
		let path = '';
		let held = false;

		try {
			const at = S.view.frame.contentWindow.location;

			if( at === null || at === undefined )
				return;

			if( String( at.protocol ) === 'about:' )
				return;

			held = String( at.search ).indexOf( S.query ) !== -1;
			href = String( at.href );

			const dir = typeof Nino.dir === 'string' ? Nino.dir : '';

			path = String( at.pathname ).indexOf( dir+ '/' ) === 0 || String( at.pathname ) === dir ? String( at.pathname ).slice( dir.length ) || '/' : String( at.pathname );
		} catch( e ) {
			href = '(unreadable)';
			path = '';
		}

		if( held === true ) {
			S.healed = '';
			return;
		}

		if( href === S.healed )
			return;

		S.healed = href;

		if( pagePaths().indexOf( path ) !== -1 ) {
			S.page = path;
			S.view.tools.pages.value = path;
		}
		else
			S.view.line.idle( word('msg/page-unknown') );

		reloadFrame();
	};

	// The frame at the width that is chosen, scaled down to the room the preview has
	const fitFrame = function() {

		if( S.view !== null )
			Nino.adminUi.scaleFrame( S.view.frame, S.view.port, LAYOUT[S.width] );
	};

	/**
	 *	Ask for the draft as the working mix says it, one request at a time: what is changed while one is out is asked for in the
	 *	next, once, and the frame is loaded when nothing newer is waiting
	 *
	 *	@param		{Function}		[then]			Called with true after the answer of the last request, false where it failed
	 *
	 *	@return		{void}
	 */
	const flush = function( then ) {

		if( typeof then === 'function' )
			S.waiting.push( then );

		if( S.inFlight === true ) {
			S.again = true;
			return;
		}

		S.inFlight = true;
		S.again = false;

		Nino.admin.themes.call( 'preview', { mix : sendable(), token : S.token }, function( status, response ) {

			S.inFlight = false;

			const finish = function( ok ) {
				const waiting = S.waiting;
				S.waiting = [];
				waiting.forEach( function( call ) { call( ok ) } );
			};

			if( status !== 200 ) {
				S.again = false;
				S.structural = null;
				failed( status, response );
				finish( false );
				return;
			}

			S.token		= response.token;
			S.query		= response.query;
			S.until		= Date.now() + response.ttl * 1000 - GRACE;

			if( S.again === true ) {
				flush();
				return;
			}

			const dropped = take( response );

			// The structure of the screen changed with this answer - another part of a group: it is drawn again
			if( S.structural !== null ) {
				const group = S.structural;
				S.structural = null;
				draw( group );
			}

			reloadFrame();

			// A refusal is told until the state is right again, which it is with a draft that was made; and what the part does not have is said
			if( S.view !== null && dropped.length > 0 ) {
				S.view.line.idle( word( 'msg/dropped', dropped.join(', ') ) );
				S.said = true;
			}
			else if( S.view !== null && ( S.view.line.state === 'error' || S.said === true ) ) {
				S.view.line.idle();
				S.said = false;
			}

			finish( true );
		} );
	};

	// The hand has moved: the draft is asked for when it rests
	const schedule = function() {

		wn.clearTimeout( S.timer );
		S.timer = wn.setTimeout( function() { flush() }, QUIET );
	};

	// Say what failed in the bar, and keep what is on screen: the mix is the person's, a failed draft takes nothing of it
	const failed = function( status, response ) {

		if( S.view !== null )
			S.view.line.error( status, response, '/_admin/themes/error/preview' );
	};

	// --- the screen of one theme -----------------------------------------------------------------------------

	const changed = function() {

		paintBar();

		if( typeof Nino.admin.dirty === 'object' && Nino.admin.dirty !== null && typeof Nino.admin.dirty.refresh === 'function' )
			Nino.admin.dirty.refresh();
	};

	/**
	 *	A knob moved to a step: the section says it, the value beside the name follows, and the draft is asked for when the hand rests
	 *
	 *	@return		{void}
	 */
	const setKnob = function( group, section, knob, step, hint ) {

		const entry = partOf( group );

		entry.values = entry.values ?? {};

		if( step === 0 )
			delete entry.values[knob.key];
		else
			entry.values[knob.key] = step;

		hint.textContent = hintOf( knob, step );
		paintSection( group, section );
		changed();
		schedule();
	};

	// A standard variant chosen. Where it is the saved one and the saved mix says nothing, the mix says nothing again
	const setVariant = function( group, section, name ) {

		const entry		= partOf( group );
		const was		= ( S.saved.mix.parts ?? {} )[group] ?? { from : 'standard' };

		entry.standard = entry.standard ?? {};

		if( ( was.from ?? 'standard' ) === entry.from && savedStandardOf( group, section.component ) === name && ( was.standard ?? {} )[section.component] === undefined )
			delete entry.standard[section.component];
		else
			entry.standard[section.component] = name;

		paintSection( group, section );
		changed();
		schedule();
	};

	const paintSection = function( group, section ) {

		if( S.view !== null && S.view.accordion !== null )
			S.view.accordion.update( section.key, { summary : summaryOf( group, section ), badge : changedIn( group, section ) === true ? word('state/changed-mark') : '' } );
	};

	/**
	 *	One section of the settings of a group: the choice of the standard variant and a row of steps for each knob, and
	 *	where there is neither the sentence that the component follows the brand and the page
	 *
	 *	@return		{Element}
	 */
	const bodyOf = function( group, section ) {

		const body = el('div');

		if( section.variants !== null ) {

			const id = 'themes-'+ group+ '-'+ section.key+ '-variant';
			const choices = section.variants.map( function( variant ) { return { value : variant.name, label : variantLabel( variant ) } } );
			const choose = function( value ) { setVariant( group, section, value ) };

			if( choices.length <= 4 ) {
				const name = el( 'div', 'nino-admin-field-label', word('label/variant') );
				name.id = id;
				name.appendChild( el( 'span', 'nino-admin-hint themes-value', word('hint/variant') ) );
				body.appendChild( name );
				body.appendChild( Nino.adminUi.steps( { labelledby : id, wide : true, steps : choices, value : standardOf( group, section.component ), onChange : choose } ).el );
			}
			else
				body.appendChild( Nino.adminUi.selectField( { key : id, label : word('label/variant'), options : choices, value : standardOf( group, section.component ), onChange : choose } ) );
		}

		section.knobs.forEach( function( knob ) {

			const id = 'themes-'+ group+ '-'+ knob.key.replace( '.', '-' );
			const name = el( 'div', 'nino-admin-field-label', localized( knob.label ) );
			const hint = el( 'span', 'nino-admin-hint themes-value', hintOf( knob, stepOf( S.mix, group, knob.key ) ) );

			S.hints.push( { knob : knob, group : group, hint : hint } );

			name.id = id;

			if( knob.extra === true )
				name.appendChild( el( 'span', 'nino-admin-badge themes-extra', word( 'chip/extra', sourceOf( group ) ) ) );

			name.appendChild( hint );
			body.appendChild( name );
			body.appendChild( Nino.adminUi.steps( { labelledby : id, wide : true, steps : STEPS, value : stepOf( S.mix, group, knob.key ), standard : 0, onChange : function( value ) { setKnob( group, section, knob, value, hint ) } } ).el );
		} );

		if( section.knobs.length === 0 && section.variants === null )
			body.appendChild( Nino.adminUi.hint( word('hint/follows') ) );

		return body;
	};

	// The part a group takes: the standard part and each theme that has one - one that rests is named with the reason and cannot be chosen
	const partChoice = function( group ) {

		const entry		= described( group );
		const options	= [ { value : 'standard', label : word('label/standard-part'), disabled : false } ];
		let current		= entry.from === 'standard' ? 'standard' : entry.from;

		entry.offers.forEach( function( offer ) {

			const value = offer.theme+ '@'+ offer.version;

			options.push( { value : value, label : themeName( offer.theme )+ ( offer.ok === true ? '' : ' ('+ word('word/rests')+ ')' ), disabled : offer.ok === false } );

			if( entry.theme === offer.theme && entry.package === 'installed' )
				current = value;
		} );

		// The part of a theme that is not installed: the project's copy, with the version it was made from
		if( options.some( function( option ) { return option.value === current } ) === false )
			options.push( { value : current, label : sourceOf( group ), disabled : false } );

		const field = Nino.adminUi.selectField( { key : 'part-'+ group, label : word( 'label/part-of', groupLabel( group ) ), options : options, value : current, onChange : function( value ) { changePart( group, value ) } } );
		const select = field.querySelector('select');

		select.id = 'themes-part-'+ group;

		Array.from( select.children ).forEach( function( option ) {
			option.disabled = options.find( function( candidate ) { return candidate.value === option.value } ).disabled === true;
		} );

		return field;
	};

	/**
	 *	Another part for a group: the mix takes it, and the draft is asked for at once - what the group is made of is in the
	 *	answer, and the screen of the group is drawn from it. A draft that cannot be made leaves the group as it was
	 *
	 *	@return		{void}
	 */
	const changePart = function( group, value ) {

		const entry = partOf( group );
		const before = clone( entry );

		wn.clearTimeout( S.timer );
		entry.from = value;
		S.structural = group;
		changed();

		flush( function( ok ) {

			if( ok === true )
				return;

			S.mix.parts[group] = before;
			S.structural = null;
			changed();
			draw( group );
		} );
	};

	// The note on the screen of a group whose part rests: why, in the words of the rule of mixing
	const restNote = function( group ) {

		const entry = described( group );

		if( entry.problem === '' )
			return null;

		return Nino.adminUi.notice( word( 'msg/rests', sourceOf( group ), entry.problem ) );
	};

	const lead = function( group ) {
		return group === 'page' ? word('hint/page') : word('hint/group');
	};

	/**
	 *	The settings of a group - level two: the way back, the part it comes from, and a section for each component
	 *
	 *	@param		{string}		group
	 *	@param		{string}		[open]					The key of the section that is open; the first when none is named or there is none of that name
	 *
	 *	@return		{Element}
	 */
	const settingsOf = function( group, open ) {

		const root		= el( 'div', 'themes-settings' );
		// Not the class of the back link of a form: the shell asks Save, Discard or Cancel before that one is followed, and this move keeps the draft
		const back		= el( 'a', 'themes-back', word('label/back-groups') );
		const sections = sectionsOf( group );

		back.href = Nino.admin.router.href( 'themes', addressOf( [ S.key ] ) );
		back.id = 'themes-back-groups';
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.themes.go( [ S.key ], { from : group } );
		} );

		root.appendChild( back );
		root.appendChild( el( 'h3', 'themes-title', groupLabel( group ) ) );
		root.appendChild( partChoice( group ) );

		const note = restNote( group );

		if( note !== null )
			root.appendChild( note );

		S.view.accordion = null;

		if( sections.length > 0 ) {

			const keys = sections.map( function( section ) { return section.key } );
			const accordion = Nino.adminUi.accordion( {
				id			: 'themes-acc',
				level		: 4,
				address		: { panel : 'themes', parts : addressOf( [ S.key, group ] ) },
				open		: keys.indexOf( open ) === -1 ? keys[0] : open,
				sections	: sections.map( function( section ) {
					return { key : section.key, label : section.label, summary : summaryOf( group, section ), badge : changedIn( group, section ) === true ? word('state/changed-mark') : '', content : function() { return bodyOf( group, section ) } };
				} ),
				onToggle	: function( keysOpen ) { marked( group, sections, keysOpen[0] ) },
			} );

			S.view.accordion = accordion;
			root.appendChild( accordion.el );
			marked( group, sections, accordion.get()[0], true );
		}
		else
			root.appendChild( Nino.adminUi.hint( word('hint/page-none') ) );

		root.appendChild( Nino.adminUi.hint( lead( group ) ) );

		return root;
	};

	// The component of the section that is open is outlined in the preview
	const marked = function( group, sections, key, quiet ) {

		const section = sections.find( function( candidate ) { return candidate.key === key } );

		S.mark = section === undefined || section.component === null ? '' : section.component;

		if( S.view !== null )
			S.view.marked.textContent = S.mark === '' ? word('msg/whole-page') : word( 'msg/marked', section.label );

		if( quiet !== true )
			reloadFrame();
	};

	/**
	 *	The groups of a theme - level one: for each the part it comes from, and where it rests that and why
	 *
	 *	@return		{Element}
	 */
	const groupsOf = function() {

		const root = el( 'div', 'themes-settings' );
		const list = el( 'ul', 'nino-admin-list themes-groups' );

		groupKeys().forEach( function( group ) {

			const item		= el('li');
			const link		= el('a');
			const copy		= el( 'span', 'nino-admin-list-copy' );
			const entry		= described( group );
			const edited	= groupChanged( group );

			link.href = Nino.admin.router.href( 'themes', addressOf( [ S.key, group ] ) );
			link.dataset.group = group;
			link.addEventListener( 'click', function( event ) {
				event.preventDefault();
				Nino.admin.themes.go( [ S.key, group ], { focus : 'back' } );
			} );

			copy.appendChild( el( 'strong', '', groupLabel( group ) ) );

			if( entry.problem !== '' )
				copy.appendChild( el( 'small', '', word( 'msg/rests-short', entry.problem ) ) );

			link.appendChild( copy );

			if( edited === true ) {
				const mark = el( 'span', 'nino-admin-badge', word('state/changed-mark') );
				mark.dataset.state = 'warn';
				link.appendChild( mark );
			}

			if( entry.problem !== '' ) {
				const rests = el( 'span', 'nino-admin-badge', word('word/rests') );
				rests.dataset.state = 'warn';
				link.appendChild( rests );
			}

			const source = el( 'span', 'themes-source', sourceOf( group ) );

			source.title = word( 'msg/part-from', sourceOf( group ) );
			link.appendChild( source );
			item.appendChild( link );
			list.appendChild( item );
		} );

		const accordion = Nino.adminUi.accordion( { id : 'themes-groups', level : 4, address : { panel : 'themes', parts : addressOf( [ S.key ] ) }, open : 'groups', sections : [ { key : 'groups', label : word('label/groups'), summary : word( 'msg/groups', groupKeys().length ), content : list } ] } );

		S.view.accordion = null;
		root.appendChild( accordion.el );
		root.appendChild( Nino.adminUi.hint( word('hint/groups') ) );

		return root;
	};

	// --- the bar -----------------------------------------------------------------------------------------------

	const isActive = function() {
		return S.active !== null && S.active.theme === S.key;
	};

	const paintBar = function() {

		const view	= S.view;

		if( view === null )
			return;

		const dirty	= isDirty();

		view.state.dataset.state = dirty === true ? 'warn' : ( isActive() === true ? 'ok' : 'muted' );
		view.state.textContent = dirty === true ? '\u25CF '+ word('state/changed') : '\u2713 '+ word( isActive() === true ? 'state/applied' : 'state/saved' );
		view.reset.disabled = dirty === false;
		view.save.disabled = dirty === false;
		view.save.textContent = S.theme.kind === 'theme' ? word('label/save-as') : word('label/save');
		view.apply.hidden = isActive();
		view.apply.disabled = dirty === true;
		view.apply.title = dirty === true ? word('hint/apply-saved') : '';
		fill( view.head, view.title, chipsOf() );

		// The line announces the change once, where the draft begins, and is silent while it goes on: a screen reader hears the chip as a word of
		// the page and not as a change. The sentence goes after a while, so that the chip is not said twice to the eye; a refusal goes when the state is right
		if( dirty === true && S.announced === false ) {

			const said = word('msg/changed');

			S.announced = true;

			if( view.line.state !== 'error' ) {
				view.line.idle( said );
				wn.setTimeout( function() {
					if( S.view === view && view.line.element.textContent === said )
						view.line.idle();
				}, ANNOUNCED );
			}
		}
		else if( dirty === false ) {
			S.announced = false;

			if( view.line.state === 'error' )
				view.line.idle();
		}
	};

	const flushWait = function( then ) {

		wn.clearTimeout( S.timer );
		flush( then );
	};

	// Take what an answer says of the state of the website into the screen
	const settle = function( response ) {

		if( response !== null && typeof response === 'object' && 'active' in response )
			S.active = response.active;
	};

	/**
	 *	Where the focus goes when an action is done: what was pressed, where that is still there and can be operated, else the place that is
	 *	given - the name of the view, which takes the focus without being a stop of the Tab key. One place decides it, for the bar and for the list
	 *
	 *	@param		{Element|null}		preferred			What the person pressed, or null
	 *	@param		{Element|null}		fallback			Where the focus goes where that is gone
	 *
	 *	@return		{void}
	 */
	const land = function( preferred, fallback ) {

		const usable = function( node ) {
			return node !== null && node !== undefined && node.isConnected !== false && node.disabled !== true && node.hidden !== true && typeof node.focus === 'function';
		};

		const target = usable( preferred ) === true ? preferred : fallback;

		if( usable( target ) === false )
			return;

		if( target.hasAttribute('tabindex') === false && /^(A|BUTTON|INPUT|SELECT|TEXTAREA)$/.test( target.tagName ) === false )
			target.setAttribute( 'tabindex', '-1' );

		target.focus();
	};

	/**
	 *	What the draft is made of, in a sentence: the groups whose part comes from another theme than most, and the theme that most come from
	 *	- "Section from Craft, otherwise Nino"
	 *
	 *	@return		{string}
	 */
	const mixSummary = function() {

		const by = {};

		groupKeys().forEach( function( group ) {
			const source = sourceOf( group );
			by[source] = ( by[source] ?? [] ).concat( [ group ] );
		} );

		const sources = Object.keys( by ).sort( function( a, b ) { return by[b].length - by[a].length } );
		const main = sources[0];
		const rest = sources.slice( 1 ).map( function( source ) { return word( 'msg/made-of', by[source].map( groupLabel ).join(', '), source ) } );

		return rest.length === 0 ? word( 'msg/mix-all', main ) : rest.join(', ')+ ', '+ word( 'msg/mix-rest', main );
	};

	// What the website shows, as a phrase: the theme by its name, or the standard parts where no theme is on
	const shownNow = function() {
		return S.active === null ? word('word/standard-parts') : word( 'msg/shown-theme', themeName( S.active.theme ) );
	};

	/**
	 *	The name of an own theme, asked in a dialog with a field: Enter in the field saves. What was wrong with the last name is said at the field
	 *	and the dialog is drawn once more, so that it stays where it was
	 *
	 *	@param		{string}		suggestion
	 *	@param		{string}		problem				What was wrong with the last name, said at the field; '' for none
	 *	@param		{Function}	then					Called with the name, or null where the dialog was left
	 *
	 *	@return		{void}
	 */
	const askName = function( suggestion, problem, then ) {

		const content = el( 'div', 'themes-dialog' );
		const field = Nino.adminUi.textField( { label : word('label/name'), id : 'themes-name', value : suggestion, required : true, maxLength : 60 } );
		const input = field.querySelector('input');
		const message = word( 'msg/save-as', S.theme.name, mixSummary(), shownNow() );

		input.name = 'name';
		content.appendChild( Nino.adminUi.hint( message ) );
		content.appendChild( field );

		if( problem !== '' )
			Nino.adminUi.fieldError( field, input, 'themes-name-error', problem );

		Nino.adminUi.choiceDialog( {
			title		: word('label/save-as'),
			message		: message,
			content		: content,
			choices		: [ { value : 'save', label : word('label/save'), kind : 'primary' }, { value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' } ],
			onChoose	: function( value ) {

				if( value !== 'save' ) {
					then( null );
					return;
				}

				// A name that says nothing is no answer: the same dialog, with the sentence at the field
				if( input.value.trim() === '' )
					askName( '', word('error/name-empty'), then );
				else
					then( input.value.trim() );
			},
		} );

		input.addEventListener( 'keydown', function( event ) {

			if( event.key !== 'Enter' )
				return;

			const dialog = input.closest('dialog');
			const primary = dialog === null ? null : dialog.querySelector('.nino-admin-btn-primary');

			event.preventDefault();

			if( primary !== null )
				primary.click();
		} );

		input.focus();
	};

	/**
	 *	Save: an own theme is written as it stands; a theme that comes with a package is never written, its draft is saved as an
	 *	own theme under a name
	 *
	 *	@param		{Function}		done				Called with true when it is saved, false where it is not
	 *
	 *	@return		{void}
	 */
	const save = function( done ) {

		if( S.ready === false || isDirty() === false ) {
			done( true );
			return;
		}

		if( S.theme.kind === 'theme' ) {
			askName( S.theme.name+ ' 2', '', function( name ) {
				if( name === null ) {
					done( false );
					return;
				}
				saveAs( name, false, done );
			} );
			return;
		}

		const go = function( force ) {

			S.view.line.saving();

			Nino.admin.themes.call( 'save', { theme : S.key, mix : sendable(), base : S.stamp, force : force }, function( status, response ) {

				if( status !== 200 ) {
					refused( status, response, function( stamp ) {
						// Two answers: Overwrite is the answer to the stamp, and the stylesheet that is not the feature's is asked about after it, as it is at any save
						if( stamp !== undefined ) {
							S.stamp = stamp;
							go( false );
						}
						else
							go( true );
					}, done );
					return;
				}

				settle( response );
				afterSave( S.key, response, done );
			} );
		};

		if( isActive() === true )
			Nino.adminUi.confirm( 'big', { title : word( 'confirm/save-title' ), message : word( 'confirm/save', S.theme.name ), yes : word('label/save'), no : Nino.content.getText('/_admin/common/label/cancel'), onChoose : function( ok ) { if( ok === true ) go( false ); else done( false ) } } );
		else
			go( false );
	};

	const saveAs = function( name, force, done ) {

		const mix = sendable();

		mix.name = name;
		S.view.line.saving();

		Nino.admin.themes.call( 'save-as', { mix : mix, force : force }, function( status, response ) {

			if( status === 400 || status === 409 ) {
				if( response !== null && typeof response === 'object' && response.field === 'name' ) {
					S.view.line.idle();
					askName( name, Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ), function( again ) {
						if( again === null )
							done( false );
						else
							saveAs( again, force, done );
					} );
					return;
				}
			}

			if( status !== 200 ) {
				refused( status, response, function() { saveAs( name, true, done ) }, done );
				return;
			}

			settle( response );
			afterSave( response.theme, response, done );
		} );
	};

	/**
	 *	Another tab - or another person - wrote the theme since this one opened it: Overwrite takes the stamp of what is there now and writes over
	 *	it, Reload drops the draft, after a question, and shows what is there, Cancel leaves the draft as it is. The same way is for every change
	 *	of a saved theme that says which state it was made on
	 *
	 *	@param		{number}			status
	 *	@param		{Object}			response
	 *	@param		{Function}		force					Does it once more, with the stamp it is given
	 *	@param		{Function}		[done]
	 *
	 *	@return		{void}
	 */
	const conflict = function( status, response, force, done ) {

		const leave = function( ok ) {
			if( typeof done === 'function' )
				done( ok );
		};

		Nino.adminUi.choiceDialog( {
			title		: word('confirm/changed-title'),
			message		: Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ),
			choices		: [
				{ value : 'over', label : word('label/overwrite'), kind : 'danger' },
				{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
				{ value : 'reload', label : word('label/reload'), kind : 'secondary' },
			],
			onChoose	: function( value ) {

				if( value === 'over' ) {

					Nino.admin.themes.call( 'open', { theme : S.key }, function( openStatus, now ) {

						if( openStatus !== 200 ) {
							S.view.line.error( openStatus, now, '/_admin/themes/error/save' );
							leave( false );
							return;
						}

						force( now.stamp );
					} );

					return;
				}

				if( value !== 'reload' ) {
					leave( false );
					return;
				}

				Nino.adminUi.confirm( 'destructive', {
					title		: word('confirm/reload-title'),
					message		: word('confirm/reload'),
					yes			: word('label/reload'),
					no			: Nino.content.getText('/_admin/common/label/cancel'),
					kind		: 'danger',
					onChoose	: function( ok ) {

						if( ok === false ) {
							leave( false );
							return;
						}

						open( S.key, function() {
							show( S.at, { focus : false } );
							land( null, S.view === null ? null : S.view.title );
							leave( true );
						} );
					},
				} );
			},
		} );
	};

	/**
	 *	A change the server refused: said in the bar, and where the stylesheet of the project is not the feature's own, asked
	 *	whether the last state may keep it
	 *
	 *	@param		{number}			status
	 *	@param		{Object}			response
	 *	@param		{Function}		force					Does it again with the question answered
	 *	@param		{Function}		[done]
	 *
	 *	@return		{void}
	 */
	const refused = function( status, response, force, done ) {

		const code = response !== null && typeof response === 'object' ? response.code : '';

		S.view.line.error( status, response, '/_admin/themes/error/save' );

		if( code === 'themes_changed' ) {
			conflict( status, response, force, done );
			return;
		}

		if( code === 'themes_foreign' ) {
			Nino.adminUi.confirm( 'destructive', {
				title	: word('confirm/foreign-title'),
				message	: Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ),
				yes		: word('label/replace'),
				no		: Nino.content.getText('/_admin/common/label/cancel'),
				kind	: 'danger',
				onChoose : function( ok ) { if( ok === true ) force(); else if( typeof done === 'function' ) done( false ) },
			} );
			return;
		}

		if( typeof done === 'function' )
			done( false );
	};

	/**
	 *	A theme is saved: what the server holds of it is read once more - the mix it made of the draft - and the screen is that
	 *
	 *	@param		{string}			key						The key of the theme that was written
	 *	@param		{Object}			response			What save or save-as answered
	 *	@param		{Function}		done
	 *
	 *	@return		{void}
	 */
	const afterSave = function( key, response, done ) {

		const moved = key !== S.key;
		const group = here().parts[1] ?? '';
		const section = here().parts[2] ?? '';

		refreshList( function() {

			if( moved === true ) {
				Nino.admin.router.go( 'themes', addressOf( group === '' ? [ key ] : [ key, group, section ] ) );
				open( key, function() {
					show( group === '' ? [ key ] : [ key, group, section ], { focus : false } );
					S.view.line.saved();
					land( null, S.view.title );
					done( true );
				} );
				return;
			}

			open( key, function() {
				show( here().parts, { focus : false } );
				S.view.line.saved();
				land( null, S.view.title );
				done( true );
			} );
		} );
	};

	/**
	 *	The list asked for again, after something was written: what it says of the active theme and of the own ones is drawn from the
	 *	answer. A list that cannot be had does not hold the screen up - what was done stands, and the next showing asks again
	 *
	 *	@param		{Function}		then
	 *
	 *	@return		{void}
	 */
	const refreshList = function( then ) {

		Nino.admin.themes.call( 'list', {}, function( status, response ) {

			if( status === 200 ) {
				Nino.admin.themes.data = response;
				render( response );
			}

			then();
		} );
	};

	const apply = function( opener ) {

		const name = S.theme === null ? '' : S.theme.name;

		applyKey( S.key, name, opener, function( ok ) {
			if( ok === true && S.view !== null ) {
				paintBar();
				S.view.line.idle( word( 'msg/applied', name ) );
				land( opener, S.view.title );
			}
		} );
	};

	/**
	 *	The website shows a theme: asked first, because visitors see it at once
	 *
	 *	@param		{string}			key
	 *	@param		{string}			name
	 *	@param		{Element}			opener
	 *	@param		{Function}		done				Called with true when it is applied
	 *
	 *	@return		{void}
	 */
	const applyKey = function( key, name, opener, done ) {

		const go = function( force ) {
			Nino.admin.themes.call( 'apply', { theme : key, force : force }, function( status, response ) {

				if( status !== 200 ) {
					refused( status, response, function() { go( true ) }, done );
					return;
				}

				settle( response );
				refreshList( function() { done( true ) } );
			} );
		};

		Nino.adminUi.confirm( 'big', {
			title		: word( 'confirm/apply-title', name ),
			message		: word('confirm/apply'),
			yes			: word('label/apply'),
			no			: Nino.content.getText('/_admin/common/label/cancel'),
			opener		: opener,
			onChoose	: function( ok ) { if( ok === true ) go( false ); else done( false ) },
		} );
	};

	// The draft is dropped: the working mix is the saved one, and the preview shows it
	const reset = function() {

		S.mix = clone( S.saved.mix );
		S.mix.parts = S.mix.parts ?? {};
		take( S.saved );
		changed();
		S.view.line.idle( word('msg/reset') );
		draw( here().parts[1] ?? '' );
		flushWait();
		land( S.view.reset, S.view.title );
	};

	/**
	 *	The bar at the foot of the screen: the state of the theme, what the last action said, and the three things to do
	 *
	 *	@return		{Object}
	 */
	const barOf = function() {

		const bar		= Nino.adminUi.actionBar( el( 'div', 'themes-bar' ) );
		const state	= el( 'span', 'nino-admin-badge themes-state' );
		const line	= Nino.adminUi.status( el( 'p' ) );
		const resetButton = Nino.adminUi.button( word('label/reset'), function() { reset() } );
		const saveButton = Nino.adminUi.button( word('label/save'), function() { save( function() {} ) }, { className : 'nino-admin-btn-primary' } );
		const applyButton = Nino.adminUi.button( word('label/apply'), function() { apply( applyButton ) } );

		[ state, line.element, resetButton, saveButton, applyButton ].forEach( function( piece ) { bar.appendChild( piece ) } );

		return { el : bar, state : state, line : line, reset : resetButton, save : saveButton, apply : applyButton };
	};

	// --- the tools over the preview ----------------------------------------------------------------------------

	// The paths of the pages the preview may show
	const pagePaths = function() {
		return S.list.pages.map( function( page ) { return page.path } );
	};

	const toolsOf = function() {

		const tools = el( 'div', 'themes-tools' );
		const marked = el( 'span', 'nino-admin-hint themes-marked', word('msg/whole-page') );
		const pages = el( 'select', 'nino-admin-input themes-page' );

		pages.setAttribute( 'aria-label', word('label/page') );

		// The title of a page with its path behind it, where the page has a title; the path alone where it has none
		S.list.pages.forEach( function( page ) {
			const option = el( 'option', '', page.title === '' ? page.path : page.title+ ' ('+ page.path+ ')' );
			option.value = page.path;
			pages.appendChild( option );
		} );

		pages.value = pagePaths().indexOf( S.page ) === -1 ? ( pagePaths()[0] ?? '/' ) : S.page;
		pages.addEventListener( 'change', function() {
			S.page = pages.value;
			reloadFrame();
		} );

		// The widths are a strip of steps, so that the one that is chosen is the one that is filled; where the view is too narrow for
		// the preview beside the settings there is no choice of width (see the stylesheet)
		const caption = el( 'span', 'nino-admin-hint themes-caption', word('word/width') );
		const box = el( 'div', 'themes-widthbox' );

		caption.id = 'themes-width-label';

		const widths = Nino.adminUi.steps( {
			labelledby	: 'themes-width-label',
			steps		: WIDTHS.map( function( name ) { return { value : name, label : word( 'word/'+ name ) } } ),
			value		: S.width,
			onChange	: function( name ) {
				S.width = name;
				S.chosen = true;
				fitFrame();
			},
		} );

		widths.el.classList.add('themes-widths');
		box.appendChild( caption );
		box.appendChild( widths.el );
		tools.appendChild( marked );

		if( S.list.pages.length > 1 )
			tools.appendChild( pages );

		tools.appendChild( box );

		return { el : tools, marked : marked, pages : pages, widths : widths, paint : widths.set };
	};

	// --- one theme: opened, drawn, shown --------------------------------------------------------------------

	/**
	 *	Open a theme: what the server holds of it, and the draft of that - the preview shows the theme and not the website
	 *
	 *	@param		{string}			key
	 *	@param		{Function}		then				Called with true when the screen is built, false where the theme could not be opened
	 *
	 *	@return		{void}
	 */
	const open = function( key, then ) {

		const level = dc.getElementById('themes-theme');

		Nino.admin.themes.call( 'open', { theme : key }, function( status, response ) {

			if( status !== 200 ) {
				fail( level, status, response );
				then( false );
				return;
			}

			S.key			= key;
			S.theme		= { key : response.theme.key, kind : response.theme.kind, name : localized( response.theme.name ) };
			S.saved		= response;
			S.stamp		= typeof response.stamp === 'string' ? response.stamp : '';
			S.mix		= clone( response.mix );
			S.mix.parts	= S.mix.parts ?? {};
			S.described	= response;
			settle( response );

			Nino.admin.themes.call( 'preview', { mix : sendable(), token : S.token }, function( previewStatus, preview ) {

				if( previewStatus !== 200 ) {
					fail( level, previewStatus, preview );
					then( false );
					return;
				}

				S.token		= preview.token;
				S.query		= preview.query;
				S.until		= Date.now() + preview.ttl * 1000 - GRACE;
				S.ready		= true;
				take( preview );
				build( level );
				then( true );
			} );
		} );
	};

	const fail = function( level, status, response ) {

		S.ready = false;
		S.view = null;
		level.innerHTML = '';

		const back = el( 'a', 'nino-admin-back-link', word('label/back-list') );
		const said = el('div');

		back.href = Nino.admin.router.href( 'themes', [] );
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.themes.go( [], {} );
		} );

		level.appendChild( Nino.adminUi.contextBar( back ) );
		level.appendChild( said );
		Nino.admin.themes.fail( said, status, response );
	};

	// The chips under the name of the theme: whether the website shows it, where it comes from, what rests in it
	const chipsOf = function() {

		const row = el( 'div', 'themes-chips' );
		const chip = function( text, state ) {
			const badge = el( 'span', 'nino-admin-badge', text );
			if( state !== undefined )
				badge.dataset.state = state;
			row.appendChild( badge );
		};

		if( isActive() === true )
			chip( word('chip/active'), 'ok' );

		chip( S.theme.kind === 'own' ? word('chip/own') : word( 'chip/package', S.list.themes.find( function( theme ) { return theme.key === S.key } )?.version ?? '' ), 'muted' );

		return row;
	};

	/**
	 *	Build the screen of the theme that is open: the way back, its name, the split view with the settings of a level and the
	 *	preview, and the bar. The settings are drawn once more for each level that is shown (see draw())
	 *
	 *	@param		{Element}		level
	 *
	 *	@return		{void}
	 */
	const build = function( level ) {

		level.innerHTML = '';

		const back = el( 'a', 'nino-admin-back-link', word('label/back-list') );

		back.href = Nino.admin.router.href( 'themes', [] );
		back.id = 'themes-back-list';
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.themes.go( [], {} );
		} );

		const head = el( 'div', 'themes-head' );
		const title = el( 'h3', 'themes-name', S.theme.name );

		S.page = pagePaths().indexOf( S.page ) === -1 ? ( pagePaths()[0] ?? '/' ) : S.page;

		const tools = toolsOf();
		const bar = barOf();
		const settings = el( 'div', 'themes-column' );

		// The preview is a frame of its own in a port of its own: it is laid out at the width of the choice and scaled to the room there is
		const port = el( 'div', 'themes-port' );
		const frame = el( 'iframe', 'themes-frame' );

		frame.title = word('label/preview-title');
		frame.setAttribute( 'aria-busy', 'true' );
		frame.addEventListener( 'load', function() {
			frame.removeAttribute('aria-busy');
			heal();
			refreshHints();
		} );
		frame.setAttribute( 'src', previewUri() );
		port.appendChild( frame );

		S.announced = false;
		S.view = { level : level, head : head, title : title, tools : tools, settings : settings, accordion : null, marked : tools.marked, state : bar.state, line : bar.line, reset : bar.reset, save : bar.save, apply : bar.apply, split : null, port : port, frame : frame };

		const split = Nino.adminUi.split( { id : 'themes-split', settings : settings, tools : tools.el, preview : port, onReload : reloadFrame, onView : function( view ) {

			// The first look at a preview that is as narrow as a phone's: the page as a phone shows it, unless a width was chosen
			if( view === 'preview' && S.chosen === false && port.getBoundingClientRect().width < NARROW ) {
				S.width = 'mobile';
				tools.paint( 'mobile' );
				fitFrame();
			}
		} } );

		S.view.split = split;
		fitFrame();

		head.appendChild( title );
		head.appendChild( chipsOf() );
		level.appendChild( Nino.adminUi.contextBar( back ) );
		level.appendChild( head );
		level.appendChild( split.el );
		level.appendChild( bar.el );

		paintBar();
	};

	/**
	 *	Draw the settings of the level the address names into the column of the split view: the groups, or the settings of one
	 *	group
	 *
	 *	@param		{string}		group				'' for the groups
	 *	@param		{string}		[open]			The section that is open
	 *
	 *	@return		{void}
	 */
	const draw = function( group, open ) {

		if( S.view === null || S.ready === false )
			return;

		const view = S.view;
		const held = dc.activeElement;

		S.hints = [];
		const inside = within( view.settings, held ) === true ? held.id : '';
		const drawn = isGroup( group ) === false ? groupsOf() : settingsOf( group, open ?? ( here().parts[2] ?? '' ) );

		fill( view.settings, drawn );
		view.split.words( { settings : isGroup( group ) === false ? word('label/groups') : word('label/settings') } );

		if( isGroup( group ) === false ) {
			S.mark = '';
			view.marked.textContent = word('msg/whole-page');
		}

		if( inside !== '' ) {
			const again = dc.getElementById( inside );
			if( again !== null && typeof again.focus === 'function' )
				again.focus();
		}
	};

	/**
	 *	Show what an address names - the groups of a theme, or the settings of a group - and move the focus there where the
	 *	person asked for the move
	 *
	 *	@param		{Array}			parts				[ theme, group, component ]
	 *	@param		{Object}		[options]		{ focus : 'back' - into the settings of a group, from - the group row of the level that is left, false - nowhere }
	 *
	 *	@return		{void}
	 */
	const show = function( parts, options ) {

		options = options ?? {};

		// A step of the browser's history between the levels puts the focus where the person's own step does
		if( options.history === true ) {

			const was = S.at[1] ?? '';
			const group = parts[1] ?? '';

			if( isGroup( group ) === true && group !== was )
				options = { focus : 'back' };
			else if( isGroup( group ) === false && isGroup( was ) === true )
				options = { from : was };
		}

		S.at = parts.slice();

		const group = parts[1] ?? '';
		const known = isGroup( group );

		const before = S.mark;

		draw( known === true ? group : '', parts[2] );

		if( S.mark !== before )
			reloadFrame();

		if( known === true )
			Nino.admin.router.set( 'themes', addressOf( [ S.key, group ].concat( S.view.accordion !== null ? S.view.accordion.get() : [] ) ) );
		else
			Nino.admin.router.set( 'themes', addressOf( [ S.key ] ) );

		Nino.adminUi.showLevel( 'themes-theme', { focus : false } );

		if( options.focus === false )
			return;

		let target = null;

		if( options.focus === 'back' )
			target = dc.getElementById('themes-back-groups');
		else if( options.from !== undefined )
			target = S.view.settings.querySelector( '[data-group="'+ options.from+ '"]' );
		else
			target = S.view.title;

		if( target !== null && typeof target.focus === 'function' ) {
			if( target.hasAttribute('tabindex') === false && /^(A|BUTTON|INPUT|SELECT|TEXTAREA)$/.test( target.tagName ) === false )
				target.setAttribute( 'tabindex', '-1' );
			target.focus();
		}
	};

	// --- the list ----------------------------------------------------------------------------------------------

	/**
	 *	What an own theme is made of, in a line: its groups by the theme their part comes from
	 *
	 *	@param		{Object}		theme				An own theme of the list
	 *
	 *	@return		{string}
	 */
	const madeOf = function( theme ) {

		const by = {};

		Object.keys( theme.from ?? {} ).sort().forEach( function( group ) {

			const from = theme.from[group];
			const source = from === 'standard' ? word('word/standard') : themeName( from.split('@')[0] );

			by[source] = ( by[source] ?? [] ).concat( [ groupLabelOf( group ) ] );
		} );

		return Object.keys( by ).map( function( source ) { return word( 'msg/made-of', by[source].join(', '), source ) } ).join(' · ');
	};

	// The name of a group as the list has it, before any theme is open
	const groupLabelOf = function( group ) {

		const known = S.list.groups.find( function( candidate ) { return candidate.key === group } );

		return group === 'page' ? word('word/page') : ( known === undefined ? group : localized( known.label ) );
	};

	const cardOf = function( theme ) {

		const card = el( 'article', 'nino-admin-card themes-card' );
		const head = el( 'div', 'themes-card-head' );
		const resting = theme.kind === 'theme' ? Object.keys( theme.parts ).filter( function( group ) { return theme.parts[group].ok === false } ) : [];

		card.dataset.theme = theme.key;
		head.appendChild( el( 'h3', '', localized( theme.name ) ) );

		const chip = function( text, state ) {
			const badge = el( 'span', 'nino-admin-badge', text );
			badge.dataset.state = state;
			head.appendChild( badge );
		};

		if( theme.active === true )
			chip( word('chip/active'), 'ok' );

		chip( theme.kind === 'own' ? word('chip/own') : word( 'chip/package', theme.version ), 'muted' );

		if( resting.length > 0 )
			chip( word( 'chip/rests', resting.length ), 'warn' );

		card.appendChild( head );

		if( theme.kind === 'theme' )
			card.appendChild( el( 'p', 'nino-admin-hint', localized( theme.description ) ) );
		else
			card.appendChild( el( 'p', 'nino-admin-hint', madeOf( theme ) ) );

		resting.forEach( function( group ) {
			card.appendChild( el( 'p', 'nino-admin-hint themes-rests', word( 'msg/card-rests', groupLabelOf( group ), theme.parts[group].problem ) ) );
		} );

		const actions = el( 'div', 'nino-admin-actions' );
		const edit = Nino.adminUi.button( word('label/edit'), function() { Nino.admin.themes.go( [ theme.key ], {} ) }, { className : 'nino-admin-btn-primary nino-admin-btn--small' } );

		edit.dataset.edit = theme.key;
		actions.appendChild( edit );

		if( theme.active !== true ) {
			const use = Nino.adminUi.button( word('label/apply'), function() {
				applyKey( theme.key, localized( theme.name ), use, function( ok ) {
					if( ok !== true )
						return;

					Nino.admin.themes.say( word( 'msg/applied', localized( theme.name ) ) );

					// The list was drawn again: the card of the theme that was applied is a new one, and its Edit is where the focus goes
					land( dc.getElementById('themes-list').querySelector( '[data-edit="'+ theme.key+ '"]' ), null );
				} );
			}, { className : 'nino-admin-btn--small' } );
			use.dataset.apply = theme.key;
			actions.appendChild( use );
		}

		card.appendChild( actions );

		return card;
	};

	// The list: the themes that come with a package, then the own ones, and what the website shows
	const render = function( data ) {

		S.list = data;
		S.active = data.active;

		const mount = dc.getElementById('themes-list');
		const wrap = el( 'div', 'themes-cards' );
		const packaged = data.themes.filter( function( theme ) { return theme.kind === 'theme' } );
		const own = data.themes.filter( function( theme ) { return theme.kind === 'own' } );
		const sorted = function( list ) {
			return list.slice().sort( function( a, b ) { return localized( a.name ).localeCompare( localized( b.name ) ) } );
		};

		mount.innerHTML = '';

		const say = el( 'p', 'nino-admin-hint themes-say' );

		say.setAttribute( 'role', 'status' );
		say.id = 'themes-say';
		mount.appendChild( say );

		if( data.foreign === true )
			mount.appendChild( Nino.adminUi.notice( word('msg/foreign') ) );

		if( data.active !== null && data.active.stale === true )
			mount.appendChild( Nino.adminUi.notice( word('msg/stale') ) );

		sorted( packaged ).concat( sorted( own ) ).forEach( function( theme ) { wrap.appendChild( cardOf( theme ) ) } );

		if( data.themes.length === 0 )
			mount.appendChild( Nino.adminUi.emptyState( word('msg/none') ) );

		mount.appendChild( wrap );

		data.rejected.forEach( function( rejected ) {
			mount.appendChild( Nino.adminUi.hint( word( 'msg/rejected', rejected.folder, rejected.reason ), 'error' ) );
		} );

		mount.appendChild( Nino.adminUi.hint( ( data.active === null ? word('hint/list-none') : word('hint/list') ) ) );
	};

	// --- the panel --------------------------------------------------------------------------------------------

	Nino.admin.themes = Nino.adminUi.panel( {
		uri		: 'themes',
		mount	: 'themes-list',
		render	: render,
		onError	: function() { Nino.adminUi.showLevel( 'themes-list', { focus : false } ) },
		dirty	: {
			isDirty	: isDirty,
			save	: save,
			bar		: function() { return null },
			discard	: function() {
				if( S.ready === false )
					return;
				S.mix = clone( S.saved.mix );
				S.mix.parts = S.mix.parts ?? {};
				take( S.saved );
				paintBar();
				draw( here().parts[1] ?? '' );
				flushWait();
			},
		},
	} );

	Object.assign( Nino.admin.themes, {

		// A line in the list: what was done
		say : function( text ) {
			const line = dc.getElementById('themes-say');
			if( line !== null )
				line.textContent = text;
		},

		/**
		 *	Move to another level of the panel: the address is written for the person's own move, and a draft that would be lost is asked about
		 *
		 *	@param		{Array}			parts				The address after #themes
		 *	@param		{Object}		options			What show() takes
		 *
		 *	@return		{void}
		 */
		go : function( parts, options ) {

			const leaves = S.ready === true && ( parts.length === 0 || parts[0] !== S.key );

			Nino.admin.dirty.guard( leaves === true ? [ 'themes' ] : [], function() {
				Nino.admin.router.go( 'themes', addressOf( parts ) );
				Nino.admin.themes.follow( options );
			} );
		},

		/**
		 *	Show what the address names. A theme is opened when it is not the one that is open; leaving one with a draft asks first
		 *
		 *	@param		{Object}		[options]
		 *
		 *	@return		{void}
		 */
		follow : function( options ) {

			const parts = here().panel === 'themes' ? here().parts : [];

			if( parts.length === 0 ) {
				S.ready = false;
				S.view = null;
				dc.getElementById('themes-theme').innerHTML = '';
				Nino.admin.router.set( 'themes', [] );
				Nino.adminUi.showLevel( 'themes-list', { focus : options !== undefined && options.focus === false ? false : true } );
				return;
			}

			if( parts[0] === S.key && S.ready === true ) {
				show( parts, options );
				return;
			}

			open( parts[0], function( ok ) {
				if( ok === true )
					show( parts, options );
				else
					Nino.adminUi.showLevel( 'themes-theme', { focus : false } );
			} );
		},

		showCurrent : function() {

			const at = here();

			// The address names another panel: what is on screen stays
			if( Nino.admin.themes.ready === true && at.panel !== 'themes' )
				return;

			// The first address of a page is no step of the history: nothing is given the focus when a page is loaded
			const first = Nino.admin.themes.ready === false;
			const proceed = function() { Nino.admin.themes.follow( { focus : false, history : first === false } ) };

			if( Nino.admin.themes.ready === false ) {
				Nino.admin.themes.init( proceed );
				return;
			}

			const leaving = S.ready === true && ( at.parts.length === 0 || at.parts[0] !== S.key );

			Nino.admin.router.leave( [ 'themes' ], leaving, proceed, function() {
				Nino.admin.router.set( 'themes', S.ready === true ? addressOf( S.at ) : [] );
			} );
		},
	} );

} )(window,document);
