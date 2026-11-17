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
 *										The address is #themes (the list: the first view of the panel, which has a strip of tabs where there is a second one -
 *										#themes/<view>), #themes/<theme>/<section> (the first level of a theme: groups, settings or css, the one that is open),
 *										#themes/<theme>/<group>/<component> (the settings of a group, one component open), for the page <group> is page and the
 *										section a set of its knobs, and #themes/<theme>/brand/<section> (the brand of the project: colours, type, mode, logo).
 *										The accordions close: a level whose address names no section is a level with every section closed, and the way in -
 *										a row, a link, the address a page is loaded on - opens the first one. The key of an own theme is own.<key> in the address.
 *
 *										The preview shows the page that is chosen for the theme - "Automatically" first: the demo of the group that is open, where
 *										the register has one, else the start page - and a thumbnail on each card of the list shows the start page of that theme.
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

	// The page a thumbnail of a theme draws, in pixels, and the box it is drawn into is as high as these two say
	const THUMB		= { width : 1280, height : 800 };

	// The query that asks the website for the demo of a group in place of a page (see Modules\Themes)
	const DEMO		= 'nino-theme-demo';

	// The page has no components: its knobs stand in these sections, in this order, and a knob that is in none stands in 'more'
	const PAGE_SECTIONS	= [ [ 'type', [ 'size', 'text', 'weight' ] ], [ 'measures', [ 'width', 'space', 'radius', 'line', 'density' ] ], [ 'motion', [ 'motion' ] ] ];
	const MORE					= 'more';

	// The names of the sections on the first level of a theme - the groups, the settings, the own css - and of the brand, which has a level of its
	// own. No group of the register takes one of them, and a group that is called one is not shown
	const RESERVED	= [ 'groups', 'brand', 'settings', 'css' ];
	const LEVEL_ONE	= [ 'groups', 'settings', 'css' ];

	// The values of the brand, and the sections they stand in on its level
	const BRAND_KEYS		= [ 'color-1', 'color-2', 'tone', 'mode', 'font-title', 'font-text', 'font-button' ];
	const BRAND_SECTIONS	= [ [ 'colours', [ 'color-1', 'color-2', 'tone' ] ], [ 'type', [ 'font-title', 'font-text', 'font-button' ] ], [ 'mode', [ 'mode' ] ], [ 'logo', [] ] ];
	const TONES				= [ 'warm', 'neutral', 'cool' ];
	const MODES				= [ 'light', 'dark', 'auto' ];

	// The pairs of the palette that are told, by the text and the ground they are made of; the dark mode has the picture under the veil as well
	const PAIR_ROLES		= [ 'ink', 'quiet', 'accent' ];
	const PAIR_GROUNDS		= { light : [ 'base', 'alt', 'tint' ], dark : [ 'base', 'alt', 'tint', 'picture' ] };
	const ROLES				= [ 'base', 'alt', 'tint', 'ink', 'quiet', 'line', 'accent', 'on' ];

	// How long the hand rests while a text is typed before the draft is asked for
	const TYPING	= 400;

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
		picked		: {},
		demo			: '',
		cssFaults	: [],
		listing		: true,
		markedLabel	: '',
		thumbs		: {},
		looking		: null,
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
		brand			: null,
		brandStamp	: '',
		state			: '',
		canRestore	: false,
		last			: null,
		invalid		: {},
		blocked		: '',
		leftFor		: '',
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

		return JSON.stringify( { name : mix.name ?? '', css : cssOf( mix ), parts : parts } );
	};

	// The own css as the server keeps it: one line ending, no space round it
	const cssOf = function( mix ) {
		return String( mix.css ?? '' ).replace( /\r\n?/g, '\n' ).trim();
	};

	// The brand as a string that says the same for the same brand: each value, and where it is the project's own, what it is
	const canonBrand = function( brand ) {
		return JSON.stringify( BRAND_KEYS.map( function( key ) { return brand[key].own === true ? [ key, brand[key].value ] : [ key ] } ) );
	};

	const mixDirty = function() {
		return S.ready === true && canon( S.mix ) !== canon( S.saved.mix );
	};

	const brandDirty = function() {
		return S.ready === true && canonBrand( S.brand ) !== canonBrand( S.saved.brand.stored );
	};

	// The draft is the state the server holds: the mix and the brand of an answer
	const draftFrom = function( saved ) {

		S.mix = clone( saved.mix );
		S.mix.parts = S.mix.parts ?? {};
		S.brand = clone( saved.brand.stored );
		S.invalid = {};
	};

	// The draft is dropped: what the part has of knobs and variants is what the saved state says
	const dropDraft = function() {

		draftFrom( S.saved );
		take( S.saved );
	};

	const isDirty = function() {
		return mixDirty() === true || brandDirty() === true;
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

		// A css that the server would refuse is not sent: the draft is made without it, and the field says why
		if( typeof S.mix.css === 'string' && cssProblem() === '' )
			mix.css = S.mix.css;

		if( S.theme.kind === 'own' )
			mix.name = S.mix.name ?? S.theme.name;

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
	 *	The page the preview shows: the one the person chose for this theme, or '' where "Automatically" is chosen - the demo of the group that is
	 *	open, or the start page where there is none
	 *
	 *	@return		{string}
	 */
	const pageOf = function() {

		const picked = S.picked[S.key] ?? '';

		return pagePaths().indexOf( picked ) === -1 ? '' : picked;
	};

	// The start page: the home of the site where the list has it, else the first page it names
	const homeOf = function() {
		return pagePaths().indexOf('/') !== -1 ? '/' : ( pagePaths()[0] ?? '/' );
	};

	// The group whose demo the screen shows: the one that is named, where the register has a demo for it and the screen has the group; else ''
	const demoOf = function( group ) {

		const known = S.list.groups.find( function( candidate ) { return candidate.key === group } );

		return known !== undefined && known.demo === true && isGroup( group ) === true ? group : '';
	};

	/**
	 *	The address of the page of the preview: the page, the token of the draft, the component to outline and - where "Automatically" is chosen and
	 *	the group that is open has a demo - the group whose demo is drawn in place of the page
	 *
	 *	@return		{string}
	 */
	const previewUri = function() {

		const dir = typeof Nino.dir === 'string' ? Nino.dir : '';
		const chosen = pageOf();

		// The demo is the answer of the start address of the site and of no other
		const shown = chosen === '' ? ( S.demo !== '' ? '/' : homeOf() ) : chosen;

		// A page of this site and nothing else: a path that starts with one slash, as the list of the API gives them
		const page = /^\/(?![\/\\])/.test( shown ) === true ? shown : '/';

		return dir+ page+ '?'+ S.query+ ( S.mark === '' || chosen === '' ? '' : '&mark='+ encodeURIComponent( S.mark ) )+ ( chosen === '' && S.demo !== '' ? '&'+ DEMO+ '='+ encodeURIComponent( S.demo ) : '' );
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

		// The split view puts the page in the place of what the frame shows: a frame that is given another address adds a step to the history of
		// the window, and the Back key would have to walk back over every reload of the preview before it came to the address of the panel
		S.view.split.reload( previewUri() );
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
			S.picked[S.key] = path;
			S.view.tools.pages.value = path;
		}
		else
			S.view.line.idle( word('msg/page-unknown') );

		reloadFrame();
	};

	// The frame at the width that is chosen, scaled down to the room the preview has
	const fitFrame = function() {

		if( S.view !== null )
			S.view.split.width( LAYOUT[S.width] );
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

		Nino.admin.themes.call( 'preview', { mix : sendable(), brand : S.brand, token : S.token }, function( status, response ) {

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

			// An answer that is no draft - no token, nothing but a "false" - is no page to load: the last picture stays, and the bar says that none is
			// drawn for now. The frame is loaded only on an answer that holds
			if( response === null || typeof response !== 'object' || typeof response.token !== 'string' || typeof response.query !== 'string' ) {
				S.again = false;
				S.structural = null;

				if( S.view !== null ) {
					S.view.line.idle( word('msg/preview-unavailable') );
					S.said = true;
				}

				finish( false );
				return;
			}

			S.token		= response.token;
			S.query		= response.query;
			S.until		= Date.now() + response.ttl * 1000 - GRACE;
			S.cssFaults	= Array.isArray( response.cssFaults ) === true ? response.cssFaults : [];
			paintCssFaults();

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

			if( S.view !== null && typeof S.view.repaint === 'function' )
				S.view.repaint();

			// A refusal is told until the state is right again, which it is with a draft that was made; and what the part does not have is said
			if( S.view !== null && dropped.length > 0 ) {
				S.view.line.idle( word( 'msg/dropped', dropped.join(', ') ) );
				S.said = true;
			}
			else if( S.view !== null && S.blocked === '' && ( S.view.line.state === 'error' || S.said === true ) ) {
				S.view.line.idle();
				S.said = false;
			}

			finish( true );
		} );
	};

	// The hand has moved: the draft is asked for when it rests - longer where the hand types a text
	const schedule = function( quiet ) {

		wn.clearTimeout( S.timer );
		S.timer = wn.setTimeout( function() { flush() }, quiet ?? QUIET );
	};

	// Say what failed in the bar, and keep what is on screen: the mix is the person's, a failed draft takes nothing of it
	const failed = function( status, response ) {

		if( S.view !== null )
			S.view.line.error( status, response, '/_admin/themes/error/preview' );
	};

	// --- the screen of one theme -----------------------------------------------------------------------------

	const changed = function() {

		paintBar();
		paintHeads();

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
	 *	@param		{string|null}	[open]				The key of the section that is open; the first when none is named or there is none of that name; null for none
	 *
	 *	@return		{Element}
	 */
	const settingsOf = function( group, open ) {

		const root		= el( 'div', 'themes-settings' );
		const sections = sectionsOf( group );

		root.appendChild( backLink( group ) );
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
				collapsible	: true,
				address		: { panel : 'themes', parts : addressOf( [ S.key, group ] ) },
				open		: open === null ? [] : ( keys.indexOf( open ) === -1 ? keys[0] : open ),
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

		S.markedLabel = section === undefined ? '' : section.label;
		paintMarked();

		if( quiet !== true )
			reloadFrame();
	};

	/**
	 *	What the line over the preview says: the component that is outlined, where a page is chosen - the demo of a group is all the component, and the
	 *	start page that stands for a group without a demo does not show it, so neither is marked - and what "Automatically" shows
	 *
	 *	@return		{void}
	 */
	const paintMarked = function() {

		if( S.view === null )
			return;

		const chosen = pageOf() !== '';

		S.view.marked.textContent = chosen === false ? '' : ( S.mark === '' ? word('msg/whole-page') : word( 'msg/marked', S.markedLabel ) );
		S.view.tools.auto.textContent = S.demo !== '' ? word('label/page-auto') : word('label/page-auto-home');
	};

	/**
	 *	The first level of a theme: three sections - the groups, each with the part it comes from and where it rests that and why, the settings
	 *	of the theme and its own css. The brand stands in the groups, before the page
	 *
	 *	@param		{string|null}	[open]				The section that is open, groups where none is named; null for none
	 *
	 *	@return		{Element}
	 */
	const groupsOf = function( open ) {

		const root = el( 'div', 'themes-settings' );
		const list = el( 'ul', 'nino-admin-list themes-groups' );

		list.appendChild( brandRowOf() );

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

		const accordion = Nino.adminUi.accordion( {
			id			: 'themes-groups',
			level		: 4,
			collapsible	: true,
			address		: { panel : 'themes', parts : addressOf( [ S.key ] ) },
			open		: open === null ? [] : ( LEVEL_ONE.indexOf( open ) === -1 ? 'groups' : open ),
			sections	: [
				{ key : 'groups', label : word('label/groups'), summary : word( 'msg/groups', groupKeys().length + 1 ), content : function() { const box = el('div'); box.appendChild( list ); box.appendChild( Nino.adminUi.hint( word('hint/groups') ) ); reservedNotes( box ); return box } },
				{ key : 'settings', label : word('label/settings'), summary : S.mix.name ?? S.theme.name, badge : S.theme.kind === 'own' && ( S.mix.name ?? '' ) !== ( S.saved.mix.name ?? '' ) ? word('state/changed-mark') : '', content : settingsOne },
				{ key : 'css', label : word('label/css'), summary : cssSummary(), badge : cssOf( S.mix ) !== cssOf( S.saved.mix ) ? word('state/changed-mark') : '', content : cssOne },
			],
		} );

		S.view.accordion = accordion;
		root.appendChild( accordion.el );

		return root;
	};

	// A group of the register that is called as a section of the panel is not shown as a group, and not left out unsaid: it is named, with why
	const reservedNotes = function( box ) {

		S.list.groups.filter( function( group ) { return RESERVED.indexOf( group.key ) !== -1 } ).forEach( function( group ) {
			const note = Nino.adminUi.hint( word( 'msg/group-reserved', localized( group.label ), group.key ) );
			note.classList.add('themes-reserved');
			box.appendChild( note );
		} );
	};

	// --- the own css -------------------------------------------------------------------------------------------

	// How many bytes a text comes to as the server counts them: UTF-8, a line ending as it was typed
	const bytesOf = function( text ) {
		return new TextEncoder().encode( text ).length;
	};

	/**
	 *	What is wrong with the own css of the draft, as a sentence; '' where the server takes it. The text is not read: it is too long, or it has a null byte,
	 *	and nothing else is a fault of it
	 *
	 *	@return		{string}
	 */
	const cssProblem = function() {

		const css = String( S.mix.css ?? '' );

		if( css === '' )
			return '';

		if( css.indexOf('\0') !== -1 )
			return word('error/css-null');

		return bytesOf( css ) > S.list.limits.css ? word( 'error/css-long', Math.round( S.list.limits.css / 1024 ) ) : '';
	};

	/**
	 *  What keeps the draft from being saved, as a sentence; '' where nothing does: a colour that is no colour, or the css that the server would refuse. The
	 *  bar says it whichever section is open
	 *
	 *  @return   {string}
	 */
	const blockReason = function() {

		const colour = BRAND_KEYS.find( function( key ) { return S.invalid[key] === true } );

		return colour !== undefined ? word( 'error/colour-invalid', word( 'label/'+ colour ) ) : cssProblem();
	};

	// What the closed head of the own css says: that there is none, or how many lines
	const cssSummary = function() {

		const css = cssOf( S.mix );

		if( css === '' )
			return word('msg/css-none');

		const lines = css.split('\n').length;

		return lines === 1 ? word('msg/css-line') : word( 'msg/css-lines', lines );
	};

	/**
	 *	The own css - a section of the first level: one field of code, which goes into the website as it is
	 *
	 *	@return		{Element}
	 */
	const cssOne = function() {

		const body = el('div');
		const field = el( 'label', 'nino-admin-field' );
		const text = el( 'textarea', 'nino-admin-input themes-code' );
		const hint = Nino.adminUi.hint( word( 'hint/css', Math.round( S.list.limits.css / 1024 ) ) );

		hint.id = 'themes-css-hint';
		text.id = 'themes-css';
		text.name = 'css';
		text.rows = 14;
		text.value = S.mix.css ?? '';
		text.spellcheck = false;
		text.setAttribute( 'wrap', 'off' );
		text.setAttribute( 'autocomplete', 'off' );
		text.setAttribute( 'autocapitalize', 'off' );
		text.setAttribute( 'aria-describedby', 'themes-css-hint' );
		field.appendChild( el( 'span', '', word('label/css') ) );
		field.appendChild( text );

		// The sentence at the field where the css cannot be taken, and none where it can
		const say = function() {

			const old = dc.getElementById('themes-css-problem');

			if( old !== null )
				old.remove();

			text.removeAttribute('aria-invalid');
			text.setAttribute( 'aria-describedby', 'themes-css-hint' );

			if( cssProblem() !== '' )
				Nino.adminUi.fieldError( field, text, 'themes-css-problem', cssProblem() );
		};

		text.addEventListener( 'input', function() {

			S.mix.css = text.value;
			say();
			changed();

			if( cssProblem() === '' )
				schedule( TYPING );
		} );

		const faults = el( 'p', 'nino-admin-hint' );

		faults.id = 'themes-css-faults';
		faults.setAttribute( 'role', 'status' );
		text.setAttribute( 'aria-describedby', 'themes-css-hint' );
		body.appendChild( field );
		body.appendChild( faults );
		body.appendChild( hint );
		say();
		paintCssFaults( faults );

		return body;
	};

	/**
	 *	What the own css leaves open, said quietly under the field: a hint and no refusal - the css is saved as it is written, and the stylesheet closes
	 *	what is open at its end. Nothing where it closes, which is where a person has not typed yet as well as where all is well
	 *
	 *	@param		{Element}		[node]			The line, where it is not in the page yet
	 *
	 *	@return		{void}
	 */
	const paintCssFaults = function( node ) {

		const line = node ?? dc.getElementById('themes-css-faults');

		if( line === null )
			return;

		const said = {
			'open-block'	: function() { return word('msg/css-open-block') },
			'stray-close'	: function() { return word('msg/css-stray-close') },
			'open-comment'	: function() { return word('msg/css-open-comment') },
			'open-string'	: function() { return word('msg/css-open-string') },
			'open-url'		: function() { return word('msg/css-open-url') },
		};

		line.textContent = S.cssFaults.filter( function( code ) { return said[code] !== undefined } ).map( function( code ) { return said[code]() } ).join(' ');
		line.hidden = line.textContent === '';
	};

	// --- the settings of the theme -------------------------------------------------------------------------------

	/**
	 *	The name is changed in the draft: an own theme is renamed by saving it. A name that says nothing is said at the field and not taken
	 *
	 *	@param		{string}		value
	 *
	 *	@return		{void}
	 */
	const renamed = function( value ) {

		nameProblem( '' );

		if( value.trim() === '' ) {
			nameProblem( word('error/name-empty') );
			return;
		}

		S.mix.name = value.trim();
		changed();
	};

	/**
	 *  What is wrong with the name of the theme, said at its field: the sentence, or nothing, which takes the last one away
	 *
	 *  @param    {string}    text
	 *
	 *  @return    {void}
	 */
	const nameProblem = function( text ) {

		const input = dc.getElementById('themes-theme-name');
		const old = dc.getElementById('themes-name-error');

		if( input === null )
			return;

		if( old !== null )
			old.remove();

		input.removeAttribute('aria-invalid');
		input.setAttribute( 'aria-describedby', 'themes-name-hint' );

		if( text !== '' )
			Nino.adminUi.fieldError( input.closest('label') ?? input.parentNode, input, 'themes-name-error', text );
	};

	/**
	 *	The settings of the theme - a section of the first level: its name, what it is made of, and what can be done with it as a whole
	 *
	 *	@return		{Element}
	 */
	const settingsOne = function() {

		const body = el('div');
		const own = S.theme.kind === 'own';
		const field = Nino.adminUi.textField( { label : word('label/name'), id : 'themes-theme-name', value : own === true ? ( S.mix.name ?? S.theme.name ) : S.theme.name, required : own, maxLength : S.list.limits.name, onInput : function( value ) { if( own === true ) renamed( value ) } } );
		const input = field.querySelector('input');
		const version = S.list.themes.find( function( theme ) { return theme.key === S.key } )?.version ?? '';
		const hint = Nino.adminUi.hint( own === true ? word('hint/name-own') : word( 'hint/name-package', version ) );
		const actions = el( 'div', 'nino-admin-actions themes-actions' );
		const saveNew = Nino.adminUi.button( word('label/save-as'), function() { saveAsNew( saveNew ) }, { className : 'nino-admin-btn--small', id : 'themes-save-as' } );
		const copyButton = Nino.adminUi.button( word('label/duplicate'), function() { duplicateOpen( copyButton ) }, { className : 'nino-admin-btn--small', id : 'themes-duplicate' } );
		const copyHint = Nino.adminUi.hint('');
		const restoreButton = Nino.adminUi.button( word('label/restore'), function() { restore( restoreButton ) }, { className : 'nino-admin-btn--small', id : 'themes-restore' } );
		const restoreHint = Nino.adminUi.hint('');
		const removeButton = own === true ? Nino.adminUi.button( word('label/delete'), function() { remove( removeButton ) }, { className : 'nino-admin-btn--small nino-admin-btn-danger', id : 'themes-delete' } ) : null;
		const removeHint = Nino.adminUi.hint('');

		input.name = 'name';
		input.readOnly = own === false;
		input.setAttribute( 'aria-describedby', 'themes-name-hint' );
		hint.id = 'themes-name-hint';
		restoreHint.id = 'themes-restore-hint';
		removeHint.id = 'themes-delete-hint';
		copyHint.id = 'themes-duplicate-hint';
		restoreButton.setAttribute( 'aria-describedby', 'themes-restore-hint' );
		copyButton.setAttribute( 'aria-describedby', 'themes-duplicate-hint' );

		if( removeButton !== null )
			removeButton.setAttribute( 'aria-describedby', 'themes-delete-hint' );

		body.appendChild( field );
		body.appendChild( hint );
		body.appendChild( el( 'div', 'nino-admin-field-label themes-fieldname', word('label/mix') ) );
		body.appendChild( Nino.adminUi.hint( mixSummary()+ '.' ) );
		actions.appendChild( saveNew );
		actions.appendChild( copyButton );
		actions.appendChild( restoreButton );

		if( removeButton !== null )
			actions.appendChild( removeButton );

		body.appendChild( actions );
		body.appendChild( copyHint );
		body.appendChild( restoreHint );
		body.appendChild( removeHint );

		S.view.one = { saveNew : saveNew, copy : copyButton, copyHint : copyHint, restore : restoreButton, restoreHint : restoreHint, remove : removeButton, removeHint : removeHint };
		paintSettings();

		return body;
	};

	// The buttons of the settings, as the state is: a restore waits for a state to go back to and for a saved draft, a theme that the website shows is not deleted
	const paintSettings = function() {

		const one = S.view === null ? null : S.view.one;

		if( one === null || one === undefined )
			return;

		const dirty = isDirty();

		one.saveNew.disabled = blockReason() !== '';
		one.copy.disabled = dirty === true;
		one.copyHint.textContent = dirty === true ? word('hint/restore-dirty') : word('hint/duplicate');
		one.restore.disabled = S.canRestore !== true || dirty === true;
		one.restoreHint.textContent = S.canRestore !== true ? word('hint/restore-none') : ( dirty === true ? word('hint/restore-dirty') : word( 'hint/restore', lastSaid() ) );

		if( one.remove !== null ) {
			one.remove.disabled = isActive() === true;
			one.removeHint.textContent = isActive() === true ? word('hint/delete-active') : word('hint/delete');
		}
		else
			one.removeHint.textContent = word('hint/delete-package');
	};

	// The heads of the sections that say what the draft holds: the name and the css on the first level, the values on the level of the brand
	const paintHeads = function() {

		const view = S.view;

		if( view === null || view.accordion === null || view.kind === 'group' )
			return;

		if( view.kind === 'brand' )
			BRAND_SECTIONS.forEach( function( set ) {
				view.accordion.update( set[0], { summary : brandSummary( set[0] ), badge : brandChangedIn( set[0] ) === true ? word('state/changed-mark') : '' } );
			} );
		else {
			view.accordion.update( 'settings', { summary : S.mix.name ?? S.theme.name, badge : S.theme.kind === 'own' && ( S.mix.name ?? '' ) !== ( S.saved.mix.name ?? '' ) ? word('state/changed-mark') : '' } );
			view.accordion.update( 'css', { summary : cssSummary(), badge : cssOf( S.mix ) !== cssOf( S.saved.mix ) ? word('state/changed-mark') : '' } );
		}

		paintSettings();
	};

	// --- the brand -----------------------------------------------------------------------------------------------

	// The name of the theme the page comes from: the one whose suggestion stands for the values the project has not set
	const pageTheme = function() {

		const theme = S.described.groups.page.theme;

		return theme === '' ? word('word/standard') : themeName( theme );
	};

	// What a value of the brand is where the project has not set it: what the theme the page comes from suggests, else what Nino.css says
	const themeValue = function( key ) {
		return ( S.described.brand.suggested ?? {} )[key] ?? S.described.brand.defaults[key];
	};

	const brandValue = function( key ) {
		return S.brand[key].own === true ? S.brand[key].value : themeValue( key );
	};

	const fontName = function( slug ) {

		const font = S.list.fonts.find( function( candidate ) { return candidate.key === slug } );

		if( font === undefined )
			return slug;

		const known = font.system === true ? word( 'word/font-'+ slug ) : '';

		return known !== '' ? known : font.name;
	};

	// A value of the brand as a word of the panel; a colour is its code
	const valueName = function( key, value ) {

		if( key === 'tone' )
			return word( 'word/tone-'+ value );

		if( key === 'mode' )
			return word( 'word/mode-'+ value );

		return key.indexOf('font-') === 0 ? fontName( value ) : String( value );
	};

	const brandOne = function( brand, key ) {
		return brand[key].own === true ? brand[key].value : null;
	};

	// Whether a value of two brands is the same: both follow the theme, or both are the project's own and the same
	const brandSame = function( one, other, key ) {
		return one[key].own === other[key].own && brandOne( one, key ) === brandOne( other, key );
	};

	// Whether a section of the brand differs from the saved one
	const brandChangedIn = function( section ) {

		const keys = BRAND_SECTIONS.find( function( set ) { return set[0] === section } )[1];

		return keys.some( function( key ) { return brandSame( S.brand, S.saved.brand.stored, key ) === false } );
	};

	// What the closed head of a section of the brand says: how many values are the project's own
	const brandSummary = function( section ) {

		if( section === 'logo' )
			return '/logo · /logo/dark';

		const keys = BRAND_SECTIONS.find( function( set ) { return set[0] === section } )[1];
		const own = keys.filter( function( key ) { return S.brand[key].own === true } ).length;

		return own === 0 ? word('msg/from-theme') : ( own === 1 ? word('msg/own-one') : word( 'msg/own-values', own ) );
	};

	// A value of the brand is set: from now on it is the project's own
	const setBrand = function( key, value ) {

		S.brand[key] = { own : true, value : value };

		if( S.view.rows[key] !== undefined )
			S.view.rows[key]();

		changed();
		schedule();
	};

	/**
	 *	One value of the brand: the control, and under it whether the value is the project's own or the theme's and the way back to the theme's
	 *
	 *	@param		{string}		key
	 *	@param		{Object}		made		{ head, control, set( value ), focus() } - the control of the value
	 *
	 *	@return		{Element}
	 */
	const brandRow = function( key, made ) {

		const row = el( 'div', 'themes-brand-field' );
		const origin = el( 'div', 'themes-origin' );
		const chip = el( 'span', 'nino-admin-badge' );

		const paint = function() {

			const own = S.brand[key].own === true;
			const suggested = ( S.described.brand.suggested ?? {} )[key] !== undefined;

			chip.textContent = own === true ? word('chip/own-value') : ( suggested === true ? word( 'chip/from-theme', pageTheme() ) : word('chip/from-standard') );
			chip.dataset.state = own === true ? 'ok' : 'muted';
			back.hidden = own === false;
			back.textContent = word( suggested === true ? 'label/take-back' : 'label/take-back-standard', valueName( key, themeValue( key ) ) );
		};

		// The value of the theme is the value again: nothing is the project's own
		const back = Nino.adminUi.button( '', function() {

			S.brand[key] = { own : false };
			made.set( themeValue( key ) );

			if( typeof made.confirm === 'function' )
				made.confirm();

			paint();
			changed();
			schedule();
			land( made.focus(), S.view.title );
		}, { className : 'nino-admin-btn--small', id : 'themes-brand-back-'+ key } );

		if( made.head !== null )
			row.appendChild( made.head );

		row.appendChild( made.control );
		origin.appendChild( chip );
		origin.appendChild( back );
		row.appendChild( origin );
		S.view.rows[key] = paint;
		paint();

		return row;
	};

	// A colour: the field of the workbench with its code, its picker and the contrast of the text that is written on it. A code that is typed on the way
	// to a colour is a colour for a moment - #123 on the way to #12345 - and the preview may follow it, but the draft keeps the last colour that was left in
	// the field: a field that is no colour at the end puts the draft back to it, and cannot be saved
	const colourField = function( key ) {

		const on = function() { return ( S.described.palette.colors ?? {} )['on-'+ key] ?? undefined };
		let confirmed = clone( S.brand[key] );

		const field = Nino.adminUi.color( {
			label    : word( 'label/'+ key ),
			id      : 'themes-brand-'+ key,
			value    : brandValue( key ),
			required  : true,
			against    : on(),
			onChange  : function( value ) {
				delete S.invalid[key];
				setBrand( key, value );
			},
			onInvalid  : function() {
				S.invalid[key] = true;
				S.brand[key] = clone( confirmed );

				if( S.view.rows[key] !== undefined )
					S.view.rows[key]();

				changed();
				schedule();
			},
		} );

		// The field is left with a colour in it: that is the colour of the draft
		field.input.addEventListener( 'change', function() {
			if( S.invalid[key] !== true )
				confirmed = clone( S.brand[key] );
		} );

		return {
			head      : null,
			control    : field.el,
			set      : function( value ) { field.set( value ) },
			focus    : function() { return field.input },
			against    : function() { field.against( on() ) },
			confirm    : function() { confirmed = clone( S.brand[key] ); delete S.invalid[key]; },
		};
	};

	// A choice among a few values - the tone of the grounds, the mode of the page
	const choiceField = function( key, values ) {

		const id = 'themes-brand-'+ key;
		const head = el( 'div', 'nino-admin-field-label themes-fieldname', word( 'label/'+ key ) );
		const strip = Nino.adminUi.steps( { labelledby : id, wide : true, steps : values.map( function( value ) { return { value : value, label : valueName( key, value ) } } ), value : brandValue( key ), onChange : function( value ) { setBrand( key, value ) } } );

		head.id = id;

		return { head : head, control : strip.el, set : function( value ) { strip.set( value ) }, focus : function() { return strip.el.querySelector('[aria-checked="true"]') } };
	};

	// A font: the system's stacks and the folders of public/fonts/ the list names
	const fontField = function( key ) {

		const options = S.list.fonts.map( function( font ) { return { value : font.key, label : fontName( font.key ) } } );
		const shown = brandValue( key );

		// A font that is named and is no folder any more is still the value: it is shown as it is, with a word that it is not there, and the site draws the
		// stack of the system for it until the folder is back
		const gone = ( S.described.brand.unavailable ?? [] ).indexOf( key ) !== -1;

		// A font that was gone when the stylesheet was built and is there again is not in it before the next build
		const back = S.active !== null && ( S.active.back ?? {} )[key] === shown;

		if( options.some( function( option ) { return option.value === shown } ) === false )
			options.push( { value : shown, label : word( 'msg/font-gone', shown ) } );

		const field = Nino.adminUi.selectField( { key : key, label : word( 'label/'+ key ), options : options, value : shown, onChange : function( value ) { setBrand( key, value ) } } );
		const select = field.querySelector('select');
		const control = el('div');

		select.id = 'themes-brand-'+ key;
		control.appendChild( field );

		if( gone === true || back === true ) {
			const note = Nino.adminUi.hint( gone === true ? word( 'msg/font-gone-note', shown ) : word( 'msg/font-back', shown ) );

			note.id = 'themes-brand-'+ key+ ( gone === true ? '-gone' : '-back' );
			select.setAttribute( 'aria-describedby', note.id );
			control.appendChild( note );
		}

		return { head : null, control : control, set : function( value ) { select.value = value }, focus : function() { return select } };
	};

	// A contrast as it is said everywhere: cut, not rounded, as the field of the colour cuts it, and written the way the language of the interface writes a number
	const ratioSaid = function( ratio ) {
		return word( 'msg/ratio', Nino.adminUi.colorModel.truncate( ratio ) );
	};

	// One pair of the palette: the text on its ground, the number, and whether it reaches what a text needs - a word and a mark, never the colour alone
	const pairItem = function( label, ratio, ink, ground ) {

		const item = el( 'li', 'themes-pair' );
		const enough = ratio >= Nino.adminUi.colorModel.ENOUGH;
		const swatch = el( 'span', 'themes-pair-swatch', 'Aa' );
		const mark = el( 'span', '', enough === true ? '✓' : '✕' );

		swatch.setAttribute( 'aria-hidden', 'true' );
		swatch.style.color = ink;
		swatch.style.backgroundColor = ground;
		mark.setAttribute( 'aria-hidden', 'true' );
		item.dataset.state = enough === true ? 'ok' : 'error';
		item.appendChild( swatch );
		item.appendChild( el( 'span', 'themes-pair-name', label ) );
		item.appendChild( el( 'span', 'themes-pair-ratio', ratioSaid( ratio ) ) );
		item.appendChild( mark );
		item.appendChild( el( 'span', 'themes-pair-verdict', word( enough === true ? 'word/reaches' : 'word/falls-short' ) ) );

		return item;
	};

	/**
	 *	The palette of the draft: the eight colours of each mode and the contrast of every pair a part reads. Where the brand names no colour and no
	 *	tone, the colours are those of the reset and the section says so
	 *
	 *	@param		{Element}		box
	 *
	 *	@return		{void}
	 */
	const paintPalette = function( box ) {

		const palette = S.described.palette;
		const colors = palette.colors ?? {};

		box.innerHTML = '';

		if( colors.light === undefined || colors.dark === undefined )
			return;

		const ratios = Object.keys( palette.contrast ).map( function( pair ) { return palette.contrast[pair] } );
		const least = Math.min( ...ratios );
		const short = ratios.filter( function( ratio ) { return ratio < Nino.adminUi.colorModel.ENOUGH } ).length;
		const list = el( 'details', 'themes-allpairs' );

		if( palette.source === 'reset' )
			box.appendChild( Nino.adminUi.notice( word('msg/palette-reset') ) );

		box.appendChild( Nino.adminUi.hint( word('hint/palette') ) );

		[ 'light', 'dark' ].forEach( function( mode ) {

			const roles = el( 'ul', 'themes-roles' );
			const pairs = el( 'ul', 'themes-pairs' );

			box.appendChild( el( 'h5', 'themes-palette-mode', word( 'word/mode-'+ mode ) ) );

			ROLES.forEach( function( role ) {

				const color = el( 'li', 'themes-role' );
				const swatch = el( 'span', 'themes-role-swatch' );

				swatch.setAttribute( 'aria-hidden', 'true' );
				swatch.style.backgroundColor = colors[mode][role];
				color.title = word( 'role/'+ role )+ ' '+ colors[mode][role];
				color.appendChild( swatch );
				color.appendChild( el( 'span', 'nino-admin-sr-only', word( 'role/'+ role )+ ' '+ colors[mode][role] ) );
				roles.appendChild( color );
			} );

			PAIR_GROUNDS[mode].forEach( function( ground ) {
				PAIR_ROLES.forEach( function( role ) {

					const ratio = palette.contrast[mode+ ' '+ role+ '/'+ ground];

					if( ratio !== undefined )
						pairs.appendChild( pairItem( word( 'msg/pair', word( 'role/'+ role ), word( 'role/'+ ground ) ), ratio, colors[mode][role], ground === 'picture' ? palette.veil : colors[mode][ground] ) );
				} );
			} );

			if( palette.contrast[mode+ ' button'] !== undefined )
				pairs.appendChild( pairItem( word('role/on'), palette.contrast[mode+ ' button'], colors[mode].on, colors[mode].accent ) );

			box.appendChild( roles );
			list.appendChild( el( 'h5', 'themes-palette-mode', word( 'word/mode-'+ mode ) ) );
			list.appendChild( pairs );
		} );

		if( palette.contrast.primary !== undefined ) {

			const primary = el( 'ul', 'themes-pairs' );

			primary.appendChild( pairItem( word('role/on-primary'), palette.contrast.primary, colors['on-color-1'], colors['color-1'] ) );
			list.appendChild( primary );
		}

		// The eight colours and one sentence are what is seen; the pairs are there to be opened, and open of themselves where one of them falls short
		box.appendChild( el( 'p', 'themes-least', short === 0 ? word( 'msg/palette-ok', Nino.adminUi.colorModel.truncate( least ) ) : word( 'msg/palette-bad', short, Nino.adminUi.colorModel.truncate( least ) ) ) );
		list.open = short > 0;
		list.insertBefore( el( 'summary', '', word( 'label/all-pairs', ratios.length ) ), list.firstChild );
		box.appendChild( list );
	};

	// What was done to colour 1 for the text and the buttons that are drawn with it, where something was: the colour that is read on a ground is not always the
	// colour the project chose, and a person who chose a bright one is told why a button is not that colour
	const adjusted = function() {

		const palette = S.described.palette;

		if( palette.source !== 'brand' || palette.colors.light === undefined )
			return [];

		const model = Nino.adminUi.colorModel;
		const first = palette.colors['color-1'];

		// Only the modes the brand shows: a mode that is fixed is the one the sentence is about
		const shown = S.described.brand.effective.mode === 'auto' ? [ 'light', 'dark' ] : [ S.described.brand.effective.mode ];

		return shown.filter( function( mode ) { return palette.colors[mode].accent !== first } ).map( function( mode ) {
			return word( model.luminance( palette.colors[mode].accent ) < model.luminance( first ) ? 'msg/adjust-darker' : 'msg/adjust-lighter', word( 'word/mode-'+ mode ), palette.colors[mode].accent );
		} );
	};

	// The logo is no value of the brand: it is two image places of the project, and the panel of the images has them
	const logoOne = function() {

		const body = el('div');
		const open = el( 'a', 'nino-admin-btn nino-admin-btn--small', word('label/logo-open') );

		open.href = Nino.admin.router.href( 'images', [ 'logo' ] );
		open.id = 'themes-logo-open';

		// The way back from the images ends here, on the link that was followed
		open.addEventListener( 'click', function() { S.leftFor = 'logo' } );
		body.appendChild( Nino.adminUi.hint( word('hint/logo') ) );
		body.appendChild( open );

		return body;
	};

	// The content of a section of the brand
	const brandSection = function( section ) {

		const body = el('div');

		if( section === 'logo' )
			return logoOne();

		if( section === 'colours' ) {

			const one = colourField('color-1');
			const two = colourField('color-2');
			const box = el( 'div', 'themes-palette' );

			const said = el( 'p', 'nino-admin-hint themes-adjusted' );

			body.appendChild( brandRow( 'color-1', one ) );
			body.appendChild( said );
			body.appendChild( brandRow( 'color-2', two ) );
			body.appendChild( Nino.adminUi.hint( word('hint/colour') ) );
			body.appendChild( brandRow( 'tone', choiceField( 'tone', TONES ) ) );
			body.appendChild( box );
			const paintAdjusted = function() {
				said.textContent = adjusted().join(' ');
				said.hidden = said.textContent === '';
			};

			paintPalette( box );
			paintAdjusted();
			S.view.repaint = function() {
				paintPalette( box );
				paintAdjusted();
				one.against();
				two.against();
			};

			return body;
		}

		if( section === 'type' )
			[ 'font-title', 'font-text', 'font-button' ].forEach( function( key ) { body.appendChild( brandRow( key, fontField( key ) ) ) } );
		else
			body.appendChild( brandRow( 'mode', choiceField( 'mode', MODES ) ) );

		return body;
	};

	// The way back to the groups, from the screen of a group or of the brand. It keeps the draft: the shell asks nothing of a back link that says so
	const backLink = function( group ) {

		const back = el( 'a', 'nino-admin-back-link', word('label/back-groups') );

		back.href = Nino.admin.router.href( 'themes', addressOf( [ S.key ] ) );
		back.id = 'themes-back-groups';
		back.setAttribute( 'data-keep-draft', '' );
		back.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.themes.go( [ S.key ], { from : group } );
		} );

		return back;
	};

	/**
	 *	The brand - level two: the way back, and a section for the colours, the type, the mode and the logo
	 *
	 *	@param		{string|null}	[open]		The key of the section that is open; null for none
	 *
	 *	@return		{Element}
	 */
	const brandOf = function( open ) {

		const root = el( 'div', 'themes-settings' );
		const title = el( 'h3', 'themes-title', word('word/brand') );
		const keys = BRAND_SECTIONS.map( function( set ) { return set[0] } );

		title.appendChild( el( 'span', 'nino-admin-badge themes-project', word('word/project') ) );
		root.appendChild( backLink( 'brand' ) );
		root.appendChild( title );
		root.appendChild( Nino.adminUi.hint( word('hint/brand') ) );

		// Where no theme is on the website the brand has nothing to hold in: it is saved, and says so, and what to do
		if( nothingShown() === true )
			root.appendChild( Nino.adminUi.notice( word('msg/brand-later') ) );

		const accordion = Nino.adminUi.accordion( {
			id			: 'themes-acc',
			level		: 4,
			collapsible	: true,
			address		: { panel : 'themes', parts : addressOf( [ S.key, 'brand' ] ) },
			open		: open === null ? [] : ( keys.indexOf( open ) === -1 ? keys[0] : open ),
			sections	: keys.map( function( key ) {
				return { key : key, label : word( 'word/'+ key ), summary : brandSummary( key ), badge : brandChangedIn( key ) === true ? word('state/changed-mark') : '', content : function() { return brandSection( key ) } };
			} ),
		} );

		S.view.accordion = accordion;
		root.appendChild( accordion.el );

		return root;
	};

	// The row of the brand in the list of the groups: it is the project's and no theme's, and stands before the page
	const brandRowOf = function() {

		const item = el('li');
		const link = el('a');
		const copy = el( 'span', 'nino-admin-list-copy' );
		const source = el( 'span', 'themes-source', word('word/project') );

		link.href = Nino.admin.router.href( 'themes', addressOf( [ S.key, 'brand' ] ) );
		link.dataset.group = 'brand';
		link.addEventListener( 'click', function( event ) {
			event.preventDefault();
			Nino.admin.themes.go( [ S.key, 'brand' ], { focus : 'back' } );
		} );

		copy.appendChild( el( 'strong', '', word('word/brand') ) );
		link.appendChild( copy );

		if( brandDirty() === true ) {
			const mark = el( 'span', 'nino-admin-badge', word('state/changed-mark') );
			mark.dataset.state = 'warn';
			link.appendChild( mark );
		}

		source.title = word('hint/brand-row');
		link.appendChild( source );
		item.appendChild( link );

		return item;
	};

	// --- what is done with the theme as a whole ------------------------------------------------------------------

	// What a request that asks again with the answer of an open takes of it: the stamp of what the refusal was about
	const adopt = function( fresh ) {

		if( fresh === undefined || fresh === null )
			return;

		if( typeof fresh.stamp === 'string' )
			S.stamp = fresh.stamp;

		if( typeof fresh.brandStamp === 'string' )
			S.brandStamp = fresh.brandStamp;

		if( typeof fresh.state === 'string' )
			S.state = fresh.state;
	};

	/**
	 *  The sentence that says what happens to the last state: the one that is kept is replaced by the state as it is now. Nothing where there is none
	 *
	 *  @return   {string}
	 */
	const replacesLast = function() {
		return S.canRestore !== true ? '' : ( S.last === null || S.last === undefined ? word('msg/replaces-last-plain') : word( 'msg/replaces-last', lastSaid() ) );
	};

	// Where nothing is on the website, what is saved reaches no visitor: the question that says "at once" would be untrue
	const nothingShown = function() {
		return S.active === null;
	};

	/**
	 *  Ask before what is saved reaches the website: the theme that it shows, or the brand, which holds for every theme. Where nothing reaches it, go on -
	 *  and where no theme is on the website the brand reaches nobody yet: it is saved, and the level of the brand says that it holds once a theme is applied
	 *
	 *  @param    {Function}    go
	 *  @param    {Function}    done            Called with false where the answer is no
	 *  @param    {boolean}    [newTheme]      The draft is saved as a theme of its own, which the website does not show: only the brand reaches it
	 *
	 *  @return    {void}
	 */
	const reaching = function( go, done, newTheme ) {

		const brand = brandDirty() === true && nothingShown() === false;

		if( brand === false && ( isActive() === false || newTheme === true ) ) {
			go();
			return;
		}

		Nino.adminUi.confirm( 'big', {
			title    : word( brand === true ? 'confirm/brand-title' : 'confirm/save-title' ),
			message    : ( brand === true ? word('confirm/brand') : word( 'confirm/save', S.theme.name ) )+ ' '+ replacesLast(),
			yes      : word('label/save'),
			no      : Nino.content.getText('/_admin/common/label/cancel'),
			onChoose  : function( ok ) { if( ok === true ) go(); else done( false ) },
		} );
	};

	/**
	 *  One change of the state, asked of the API, the way every change is: the payload is made for each try - with the stamps the person has now - and
	 *  a refusal is answered by the one way for them all. Overwrite is the answer to a stamp and sends no force; Replace is the answer to a stylesheet that
	 *  is not the feature's and sends the force and takes no stamp
	 *
	 *  @param    {string}    action
	 *  @param    {Function}    build        ( force ) - the payload
	 *  @param    {Function}    after        Called with the answer when it was written
	 *  @param    {Function}    done        Called with false where the change is not made
	 *  @param    {Function}    [early]      ( status, response, force ) - answers a refusal of its own and says so by true
	 *
	 *  @return    {void}
	 */
	const change = function( action, build, after, done, early ) {

		const go = function( force ) {

			// A change that is made from the list has no screen of a theme to say that it is under way
			if( S.view !== null )
				S.view.line.saving();

			Nino.admin.themes.call( action, build( force ), function( status, response ) {

				if( status !== 200 ) {

					if( typeof early === 'function' && early( status, response, force ) === true )
						return;

					refused( status, response, function( fresh ) { adopt( fresh ); go( fresh === undefined ) }, done );
					return;
				}

				settle( response );
				after( response );
			} );
		};

		go( false );
	};

	// The brand alone: the draft changed nothing of the theme. It is the project's, so it is written whichever theme is open
	const saveBrand = function( done ) {

		reaching( function() {
			change( 'brand', function( force ) {
				return { theme : S.key, brand : S.brand, brandBase : S.brandStamp, force : force };
			}, function( response ) { afterSave( S.key, response, done ) }, done );
		}, done );
	};

	// "Save as own theme": the draft is a theme of its own under a name, whatever theme is open
	const saveAsNew = function( opener ) {

		const cancelled = function() { land( opener, S.view.title ) };

		reaching( function() {
			askName( S.theme.name+ ' 2', '', function( name ) {
				if( name === null )
					cancelled();
				else
					saveAs( name, function() {} );
			} );
		}, cancelled, true );
	};

	/**
	 *	The name a copy is suggested under: the name and "(copy)", cut to the length a name may have
	 *
	 *	@param		{string}		name
	 *
	 *	@return		{string}
	 */
	const copyName = function( name ) {

		// The key a name makes is the slug of the kernel, the one the server makes of it (\Nino\Feature::slug()): an umlaut is ae, oe or ue in both
		const keyOf = function( text, max ) {
			return Nino.slug( text, max );
		};

		// A key as long as its name makes it: longer than any name may be
		const WHOLE = 10000;

		// The names the own themes have, as the server tells two names apart: by the key they make, which is cut at the length of a key
		const taken = S.list.themes.filter( function( theme ) { return theme.kind === 'own' } ).map( function( theme ) { return keyOf( localized( theme.name ), S.list.limits.key ) } );

		for( let number = 1; ; number++ ) {

			const made = function( base ) { return number === 1 ? word( 'msg/copy-name', base ) : word( 'msg/copy-name-n', base, number ) };

			// The name is cut until the whole of the suggestion is in the key and in the length a name may have - a name that is cut at the end of its
			// key is the name it was made from
			const letters = Array.from( name );
			const whole = letters.length;

			// A name that was cut does not end where the cut fell on a space or a mark of punctuation: "Lindner & Sons (copy)", not "Lindner & (copy)"
			const base = function() {
				return letters.length === whole ? letters.join('') : letters.join('').replace( /[\s\p{P}\p{S}]+$/u, '' );
			};

			while( letters.length > 0 && ( Array.from( made( base() ) ).length > S.list.limits.name || keyOf( made( base() ), WHOLE ).length > S.list.limits.key ) )
				letters.pop();

			const suggestion = made( base() );

			if( taken.indexOf( keyOf( suggestion, S.list.limits.key ) ) === -1 )
				return suggestion;
		}
	};

	/**
	 *	A theme is copied as a new own theme, under a name: its mix and its own css as they are saved - not the draft, which "Save as own theme" takes -
	 *	and not the brand, which is the project's. Asked as every other change is, by change(); the stamp is that of the theme as the one who asks saw it
	 *
	 *	@param		{Object}			source			{ key, name, stamp } - stamp is a function that answers the stamp the person has now, '' for a theme of a package
	 *	@param		{Element}			opener
	 *	@param		{Function}		finished		Called with the answer when the copy is written
	 *
	 *	@return		{void}
	 */
	const duplicate = function( source, opener, finished ) {

		// The card the button was on is drawn again where the list was: the button of the same theme in the list as it is now takes the focus
		const cancelled = function() {
			land( S.view === null ? dc.getElementById('themes-list').querySelector( '[data-duplicate="'+ source.key+ '"]' ) ?? opener : opener, S.view === null ? null : S.view.title );
		};

		const about = { title : word('label/duplicate-title'), message : word( 'msg/duplicate', source.name, shownNow() ) };

		const send = function( name ) {

			change( 'duplicate', function( force ) {

				const posted = { theme : source.key, name : name, force : force };

				if( source.stamp() !== '' )
					posted.base = source.stamp();

				return posted;
			}, finished, cancelled, function( status, response ) {

				if( response === null || typeof response !== 'object' )
					return false;

				// A name that is taken, or says nothing, is asked again in the dialog, with the sentence at the field
				if( ( status === 400 || status === 409 ) && response.field === 'name' ) {

					if( S.view !== null )
						S.view.line.idle();

					askName( name, Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ), function( again ) {
						if( again === null )
							cancelled();
						else
							send( again );
					}, about );

					return true;
				}

				// A theme that is not open and was changed since the list was drawn: the list is drawn again and says so
				if( response.code === 'themes_changed' && ( S.ready !== true || S.key !== source.key ) ) {
					refreshList( function() {
						Nino.admin.themes.say( word( 'msg/changed-list', source.name ) );
						cancelled();
					} );

					return true;
				}

				// A theme that is open was saved otherwise meanwhile: nothing is overwritten by a copy, but what would be copied is not what the person saw
				if( response.code === 'themes_changed' ) {

					Nino.adminUi.choiceDialog( {
						title		: word('confirm/duplicate-changed-title'),
						message		: word( 'msg/duplicate-changed', source.name ),
						choices		: [
							{ value : 'copy', label : word('label/copy-as-saved'), kind : 'primary' },
							{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
							{ value : 'reload', label : word('label/reload'), kind : 'secondary' },
						],
						onChoose	: function( value ) {

							// Nothing is under way any more: the line that said so says what the theme is
							if( value === 'cancel' ) {
								S.view.line.idle();
								cancelled();
								return;
							}

							Nino.admin.themes.call( 'open', { theme : S.key }, function( openStatus, fresh ) {

								if( openStatus !== 200 ) {
									S.view.line.error( openStatus, fresh, '/_admin/themes/error/save' );
									cancelled();
									return;
								}

								if( value === 'copy' ) {
									adopt( fresh );
									send( name );
									return;
								}

								open( S.key, function() {
									show( S.at, { focus : false } );
									land( null, S.view === null ? null : S.view.title );
								} );
							} );
						},
					} );

					return true;
				}

				return false;
			} );
		};

		askName( copyName( source.name ), '', function( name ) {
			if( name === null )
				cancelled();
			else
				send( name );
		}, about );
	};

	// The theme that is open is copied, as it is saved: the screen goes on to the copy, where the person finds it
	const duplicateOpen = function( opener ) {

		duplicate( { key : S.key, name : S.theme.name, stamp : function() { return S.theme.kind === 'own' ? S.stamp : '' } }, opener, function( response ) {
			afterSave( response.theme, response, function() {
				if( S.view !== null )
					S.view.line.idle( word( 'msg/duplicated', themeName( response.theme ) ) );
			} );
		} );
	};

	/**
	 *	Where a theme is left for the list: the screen of it is gone, and what was done is said in the list
	 *
	 *	@param		{string}		message
	 *
	 *	@return		{void}
	 */
	const leaveTheme = function( message ) {

		S.ready = false;
		Nino.admin.router.go( 'themes', [] );
		Nino.admin.themes.follow( {} );
		Nino.admin.themes.say( message );
	};

	/**
	 *  When the last state was saved, and with which theme, as a person says it: "16 Nov 2026, 10:52, with the theme Nino" - or a word that says it is
	 *  not known
	 *
	 *  @return    {string}
	 */
	const lastSaid = function() {

		if( S.last === null || S.last === undefined )
			return word('msg/last-unknown');

		const lang = String( ( dc.documentElement && dc.documentElement.lang ) || '' ).replace( '_', '-' );
		const when = new Date( S.last.at * 1000 ).toLocaleString( lang === '' ? undefined : lang, { dateStyle : 'medium', timeStyle : 'short' } );

		return word( 'msg/last-state', when, S.last.theme === '' ? word('word/standard-parts') : word( 'msg/shown-theme', localized( S.last.name ) ) );
	};

	// The state of before the last save, in place of this one - which becomes the last, so that a second restore is the state as it is now
	const restore = function( opener ) {

		const cancelled = function() { land( opener, S.view.title ) };

		const go = function() {
			change( 'restore', function( force ) {
				return { base : S.state, force : force };
			}, function() {

				refreshList( function() {

					if( S.list.themes.some( function( theme ) { return theme.key === S.key } ) === false ) {
						leaveTheme( word( 'msg/restored-gone', S.theme.name ) );
						return;
					}

					open( S.key, function( ok ) {

						if( ok !== true )
							return;

						show( here().parts, { focus : false } );
						S.view.line.idle( word('msg/restored') );
						land( dc.getElementById('themes-restore'), S.view.title );
					} );
				} );
			}, cancelled );
		};

		Nino.adminUi.confirm( 'big', {
			title    : word('confirm/restore-title'),
			message    : word( 'confirm/restore', lastSaid() ),
			yes      : word('label/restore'),
			no      : Nino.content.getText('/_admin/common/label/cancel'),
			opener    : opener,
			onChoose  : function( ok ) { if( ok === true ) go(); else cancelled() },
		} );
	};

	// An own theme is deleted: asked as a thing that cannot be taken back, and it changes nothing the website shows
	const remove = function( opener ) {

		const name = S.theme.name;
		const cancelled = function() { land( opener, S.view.title ) };

		Nino.adminUi.confirm( 'destructive', {
			title    : word( 'confirm/delete-title', name ),
			message    : word('confirm/delete')+ ' '+ replacesLast(),
			yes      : word('label/delete'),
			no      : Nino.content.getText('/_admin/common/label/cancel'),
			kind    : 'danger',
			visitors  : false,
			opener    : opener,
			onChoose  : function( ok ) {

				if( ok !== true ) {
					cancelled();
					return;
				}

				change( 'delete', function( force ) {
					return { theme : S.key, base : S.stamp, force : force };
				}, function() { refreshList( function() { leaveTheme( word( 'msg/deleted', name ) ) } ) }, cancelled );
			},
		} );
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

		// A colour that is no colour yet is not in the draft - the draft keeps the last one that was - and is not saved either: the state is not "saved"
		const unsaved = dirty === true || Object.keys( S.invalid ).length > 0;

		view.state.dataset.state = unsaved === true ? 'warn' : ( isActive() === true ? 'ok' : 'muted' );
		view.state.textContent = unsaved === true ? '\u25CF '+ word('state/changed') : '\u2713 '+ word( isActive() === true ? 'state/applied' : 'state/saved' );
		view.reset.disabled = unsaved === false;
		view.save.disabled = dirty === false || blockReason() !== '';
		view.save.textContent = S.theme.kind === 'theme' && mixDirty() === true ? word('label/save-as') : word('label/save');
		view.apply.hidden = isActive();
		view.apply.disabled = dirty === true;
		view.apply.title = dirty === true ? word('hint/apply-saved') : '';
		view.rebuild.hidden = isActive() === false || S.active.stale !== true;
		view.rebuild.disabled = dirty === true;
		view.rebuild.title = dirty === true ? word('hint/apply-saved') : '';
		fill( view.head, view.title, chipsOf() );
		paintSettings();

		// What keeps the draft from being saved is said in the bar, where the person is looking, whichever section is open; it goes when the cause does
		const why = blockReason();

		if( why !== '' ) {

			if( S.blocked !== why || view.line.state !== 'error' )
				view.line.fail( why );

			S.blocked = why;
			return;
		}

		if( S.blocked !== '' ) {
			S.blocked = '';

			if( view.line.state === 'error' )
				view.line.idle();
		}

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

		// The state the answer leaves: what a restore is asked against, and whether there is one to go back to
		if( response !== null && typeof response === 'object' && typeof response.state === 'string' ) {
			S.state = response.state;
			S.canRestore = response.canRestore === true;
			S.last = response.last ?? null;
		}
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
	 *	and the dialog is drawn once more, so that it stays where it was. The dialog says what is done and - where there is a last state that is kept -
	 *	that it is replaced, as every question of the panel that writes does
	 *
	 *	@param		{string}		suggestion
	 *	@param		{string}		problem				What was wrong with the last name, said at the field; '' for none
	 *	@param		{Function}	then					Called with the name, or null where the dialog was left
	 *	@param		{Object}		[about]				{ title, message } - what the dialog is for; the draft of the theme that is open saved under a name where none is given
	 *
	 *	@return		{void}
	 */
	const askName = function( suggestion, problem, then, about ) {

		about = about ?? { title : word('label/save-as'), message : word( 'msg/save-as', S.theme.name, mixSummary(), shownNow() ) };

		const content = el('div');
		const field = Nino.adminUi.textField( { label : word('label/name'), id : 'themes-name', value : suggestion, required : true, maxLength : 60 } );
		const input = field.querySelector('input');
		const message = about.message+ ( replacesLast() === '' ? '' : ' '+ replacesLast() );

		input.name = 'name';
		content.appendChild( Nino.adminUi.hint( message ) );
		content.appendChild( field );

		if( problem !== '' )
			Nino.adminUi.fieldError( field, input, 'themes-name-error', problem );

		Nino.adminUi.choiceDialog( {
			title		: about.title,
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
					askName( '', word('error/name-empty'), then, about );
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

		// What keeps it from being saved is in the bar already, and so a save by the keyboard is told no
		if( blockReason() !== '' ) {
			done( false );
			return;
		}

		// A theme of a package is never written: where its mix changed the draft is saved under a name, and where only the brand did, the brand is written
		if( S.theme.kind === 'theme' && mixDirty() === false ) {
			saveBrand( done );
			return;
		}

		if( S.theme.kind === 'theme' ) {
			reaching( function() {
				askName( S.theme.name+ ' 2', '', function( name ) {
					if( name === null ) {
						done( false );
						return;
					}
					saveAs( name, done );
				} );
			}, done, true );
			return;
		}

		reaching( function() {
			change( 'save', function( force ) {

				const posted = { theme : S.key, mix : sendable(), base : S.stamp, force : force };

				if( brandDirty() === true ) {
					posted.brand = S.brand;
					posted.brandBase = S.brandStamp;
				}

				return posted;
			}, function( response ) { afterSave( S.key, response, done ) }, done );
		}, done );
	};

	const saveAs = function( name, done ) {

		change( 'save-as', function( force ) {

			const mix = sendable();
			const posted = { mix : mix, force : force };

			mix.name = name;

			if( brandDirty() === true ) {
				posted.brand = S.brand;
				posted.brandBase = S.brandStamp;
			}

			return posted;
		}, function( response ) { afterSave( response.theme, response, done ) }, done, function( status, response ) {

			// A name that is taken, or says nothing, is asked again in the dialog, with the sentence at the field
			if( ( status !== 400 && status !== 409 ) || response === null || typeof response !== 'object' || response.field !== 'name' )
				return false;

			S.view.line.idle();
			askName( name, Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ), function( again ) {
				if( again === null )
					done( false );
				else
					saveAs( again, done );
			} );

			return true;
		} );
	};

	/**
	 *	Another tab - or another person - wrote the theme since this one opened it: Overwrite takes the stamp of what is there now and writes over
	 *	it, Reload drops the draft, after a question, and shows what is there, Cancel leaves the draft as it is. The same way is for every change
	 *	of a saved theme that says which state it was made on
	 *
	 *	@param		{number}			status
	 *	@param		{Object}			response
	 *	@param		{Function}		force					Does it once more, with the stamp the refusal was about as the open gave it
	 *	@param		{Function}		[done]
	 *
	 *	@return		{void}
	 */
	const conflict = function( status, response, force, done ) {

		const leave = function( ok ) {
			if( typeof done === 'function' )
				done( ok );
		};

		// What of the brand this tab set and the other one set as well: the values of both, which Overwrite settles for this tab
		const both = function( now ) {
			return BRAND_KEYS.filter( function( key ) {
				return brandSame( S.brand, S.saved.brand.stored, key ) === false && brandSame( now.brand.stored, S.saved.brand.stored, key ) === false && brandSame( S.brand, now.brand.stored, key ) === false;
			} );
		};

		const ask = function( now ) {

			const clash = now === null ? [] : both( now );

			Nino.adminUi.choiceDialog( {
				title    : word('confirm/changed-title'),
				message    : Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' )+ ( now === null ? '' : ' '+ word('msg/brand-merge')+ ( clash.length === 0 ? '' : ' '+ word( 'msg/brand-both', clash.map( function( key ) { return word( 'label/'+ key ) } ).join(', ') ) ) ),
				choices    : [
					{ value : 'over', label : word('label/overwrite'), kind : 'danger' },
					{ value : 'cancel', label : Nino.content.getText('/_admin/common/label/cancel'), kind : 'secondary' },
					{ value : 'reload', label : word('label/reload'), kind : 'secondary' },
				],
				onChoose  : function( value ) {

					if( value === 'over' ) {

						// The brand of the other tab is the saved one now; what this tab set stays, what it did not touch is as the other tab saved it
						if( now !== null ) {

							BRAND_KEYS.forEach( function( key ) {
								if( brandSame( S.brand, S.saved.brand.stored, key ) === true )
									S.brand[key] = clone( now.brand.stored[key] );
							} );

							S.saved.brand = Object.assign( {}, S.saved.brand, { stored : clone( now.brand.stored ) } );
							force( { brandStamp : now.brandStamp } );
							return;
						}

						Nino.admin.themes.call( 'open', { theme : S.key }, function( openStatus, fresh ) {

							if( openStatus !== 200 ) {
								S.view.line.error( openStatus, fresh, '/_admin/themes/error/save' );
								leave( false );
								return;
							}

							// Only what the refusal was about is taken over: a stamp the person has not been told is stale stays the one it was
							force( response.code === 'themes_state' ? { state : fresh.state } : { stamp : fresh.stamp } );
						} );

						return;
					}

					if( value !== 'reload' ) {
						leave( false );
						return;
					}

					Nino.adminUi.confirm( 'destructive', {
						title    : word('confirm/reload-title'),
						message    : word('confirm/reload'),
						yes      : word('label/reload'),
						no      : Nino.content.getText('/_admin/common/label/cancel'),
						kind    : 'danger',
						onChoose  : function( ok ) {

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

		if( response.code !== 'themes_brand_changed' ) {
			ask( null );
			return;
		}

		// The brand of the other tab is looked at first: what the two tabs set is told in the question
		Nino.admin.themes.call( 'open', { theme : S.key }, function( openStatus, now ) {

			if( openStatus !== 200 ) {
				S.view.line.error( openStatus, now, '/_admin/themes/error/save' );
				leave( false );
				return;
			}

			ask( now );
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

		// Where no theme is open - an apply from the list - the list says it
		if( S.view !== null && S.ready === true )
			S.view.line.error( status, response, '/_admin/themes/error/save' );
		else
			Nino.admin.themes.say( Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ) );

		// A name that is refused is said at the field of the name too, with the name of the card it collides with, where the field is on the screen
		if( code === 'themes_name_taken' && dc.getElementById('themes-theme-name') !== null )
			nameProblem( Nino.adminUi.api.errorText( status, response, '/_admin/themes/error/save' ) );

		if( code === 'themes_changed' || code === 'themes_brand_changed' || code === 'themes_state' ) {
			conflict( status, response, force, done );
			return;
		}

		// A stylesheet that cannot be read is the same question, with the sentence that what it holds is not kept as the last state
		if( code === 'themes_foreign' || code === 'themes_unreadable' ) {
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
		const at = [ key, here().parts[1] ?? '', here().parts[2] ?? '' ].filter( function( part ) { return part !== '' } );

		refreshList( function() {

			if( moved === true ) {
				Nino.admin.router.go( 'themes', addressOf( at ) );
				open( key, function() {
					show( at, { focus : false } );
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
		}, S.theme !== null && S.theme.kind === 'own' ? S.stamp : undefined );
	};

	/**
	 *	The stylesheet of the website is built again from the theme as it is saved - what a save with nothing changed does: it writes the state
	 *	again, and the state is built when it is written. Offered where the stylesheet is not what the register and the fonts make now
	 *
	 *	@param		{Element}		opener
	 *
	 *	@return		{void}
	 */
	const rebuild = function( opener ) {

		const cancelled = function() { land( opener, S.view.title ) };

		const finished = function() {
			if( S.view !== null ) {
				paintBar();
				S.view.line.idle( word('msg/rebuilt') );
				land( opener, S.view.title );
			}
		};

		// A theme of a package is the one the website was given: it is applied once more
		if( S.theme.kind !== 'own' ) {
			applyKey( S.key, S.theme.name, opener, function( ok ) { if( ok === true ) finished(); else cancelled() } );
			return;
		}

		reaching( function() {
			change( 'save', function( force ) {
				return { theme : S.key, mix : sendable(), base : S.stamp, force : force };
			}, function( response ) { afterSave( S.key, response, finished ) }, cancelled );
		}, cancelled );
	};

	/**
	 *  The website shows a theme: asked first, because visitors see it at once
	 *
	 *  @param    {string}    key
	 *  @param    {string}    name
	 *  @param    {Element}    opener
	 *  @param    {Function}    done        Called with true when it is applied
	 *  @param    {string}    [stamp]      The stamp of the own theme as the one who asks saw it: another tab that saved it since is not applied over unseen
	 *
	 *  @return    {void}
	 */
	const applyKey = function( key, name, opener, done, stamp ) {

		// The theme that is open has the stamp this tab holds now, after an Overwrite
		const stampOf = function() {
			return S.ready === true && S.key === key ? S.stamp : stamp;
		};

		const go = function( force ) {

			const posted = { theme : key, force : force };

			if( stampOf() !== undefined )
				posted.base = stampOf();

			Nino.admin.themes.call( 'apply', posted, function( status, response ) {

				if( status !== 200 ) {

					// From the list the theme is not open: what another tab saved is said in the list, which is drawn again to show it
					if( response !== null && typeof response === 'object' && response.code === 'themes_changed' && ( S.ready !== true || S.key !== key ) ) {
						refreshList( function() {
							Nino.admin.themes.say( word( 'msg/changed-list', name ) );
							done( false );
						} );
						return;
					}

					refused( status, response, function( fresh ) { adopt( fresh ); go( fresh === undefined ) }, done );
					return;
				}

				settle( response );
				refreshList( function() { done( true ) } );
			} );
		};

		Nino.adminUi.confirm( 'big', {
			title    : word( 'confirm/apply-title', name ),
			message    : word('confirm/apply')+ ' '+ replacesLast(),
			yes      : word('label/apply'),
			no      : Nino.content.getText('/_admin/common/label/cancel'),
			opener    : opener,
			onChoose  : function( ok ) { if( ok === true ) go( false ); else done( false ) },
		} );
	};

	// The draft is dropped: the working mix is the saved one, and the preview shows it
	const reset = function() {

		dropDraft();
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
		const rebuildButton = Nino.adminUi.button( word('label/rebuild'), function() { rebuild( rebuildButton ) } );

		[ state, line.element, resetButton, saveButton, rebuildButton, applyButton ].forEach( function( piece ) { bar.appendChild( piece ) } );

		return { el : bar, state : state, line : line, reset : resetButton, save : saveButton, rebuild : rebuildButton, apply : applyButton };
	};

	// --- the tools over the preview ----------------------------------------------------------------------------

	// The paths of the pages the preview may show
	const pagePaths = function() {
		return S.list.pages.map( function( page ) { return page.path } );
	};

	const toolsOf = function() {

		const tools = el( 'div', 'themes-tools' );
		const marked = el( 'span', 'nino-admin-hint themes-marked', '' );
		const pages = el( 'select', 'nino-admin-input themes-page' );

		pages.setAttribute( 'aria-label', word('label/page') );

		// "Automatically" first: the demo of the group that is open, or the start page where there is none
		const auto = el( 'option', '', word('label/page-auto') );

		auto.value = '';
		pages.appendChild( auto );

		// The title of a page with its path behind it, where the page has a title; the path alone where it has none
		S.list.pages.forEach( function( page ) {
			const option = el( 'option', '', page.title === '' ? page.path : page.title+ ' ('+ page.path+ ')' );
			option.value = page.path;
			pages.appendChild( option );
		} );

		pages.value = pageOf();
		pages.addEventListener( 'change', function() {
			S.picked[S.key] = pages.value;
			paintMarked();
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

		if( S.list.pages.length > 0 )
			tools.appendChild( pages );

		tools.appendChild( box );

		return { el : tools, marked : marked, auto : auto, pages : pages, widths : widths, paint : widths.set };
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
			S.brandStamp	= typeof response.brandStamp === 'string' ? response.brandStamp : '';

			// The name of an own theme is part of what is changed and saved: the mix holds it, as the draft does
			if( S.theme.kind === 'own' )
				response.mix.name = S.theme.name;

			draftFrom( response );
			S.described	= response;
			settle( response );

			Nino.admin.themes.call( 'preview', { mix : sendable(), brand : S.brand, token : S.token }, function( previewStatus, preview ) {

				if( previewStatus !== 200 ) {
					fail( level, previewStatus, preview );
					then( false );
					return;
				}

				S.token		= preview.token;
				S.query		= preview.query;
				S.until		= Date.now() + preview.ttl * 1000 - GRACE;
				S.cssFaults	= Array.isArray( preview.cssFaults ) === true ? preview.cssFaults : [];
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

		// The first address of the frame is the one the screen will have: the demo of the group the address names, and nothing outlined
		S.mark = '';
		S.demo = demoOf( here().parts[1] ?? '' );

		const tools = toolsOf();
		const bar = barOf();
		const settings = el( 'div', 'themes-column' );

		S.announced = false;
		S.blocked = '';
		S.view = { level : level, kind : 'groups', rows : {}, one : null, repaint : null, head : head, title : title, tools : tools, settings : settings, accordion : null, group : '', marked : tools.marked, state : bar.state, line : bar.line, reset : bar.reset, save : bar.save, rebuild : bar.rebuild, apply : bar.apply, split : null, frame : null };

		// The preview is the page laid out at the width of the choice and scaled to the room there is, which the split view does
		const split = Nino.adminUi.split( { id : 'themes-split', settings : settings, tools : tools.el, preview : { src : previewUri(), title : word('label/preview-title'), width : LAYOUT[S.width] },
			onLoad : function() {
				heal();
				refreshHints();
			},
			onView : function( view ) {

				// The first look at a preview that is as narrow as a phone's: the page as a phone shows it, unless a width was chosen
				if( view === 'preview' && S.chosen === false && split.frame.parentNode.getBoundingClientRect().width < NARROW ) {
					S.width = 'mobile';
					tools.paint( 'mobile' );
					fitFrame();
				}
			} } );

		S.view.split = split;
		S.view.frame = split.frame;

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
	 *	@param		{string|null}	[open]			The section that is open, null for none; where it is not given, what is open on the screen where it is this level that is drawn
	 *														again, else what the address names
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
		const kind = group === 'brand' ? 'brand' : ( isGroup( group ) === true ? 'group' : 'groups' );
		const level = kind === 'groups' ? '' : group;
		let wanted = open;

		if( wanted === undefined )
			wanted = view.accordion !== null && view.kind === kind && view.group === level ? ( view.accordion.get()[0] ?? null ) : ( ( kind === 'groups' ? here().parts[1] : here().parts[2] ) ?? '' );

		view.kind = kind;
		view.group = level;
		view.rows = {};
		view.one = null;
		view.repaint = null;
		S.demo = demoOf( level );

		const drawn = kind === 'groups' ? groupsOf( wanted ) : ( kind === 'brand' ? brandOf( wanted ) : settingsOf( group, wanted ) );

		fill( view.settings, drawn );
		view.split.words( { settings : kind === 'groups' ? word('label/groups') : word('label/settings') } );

		if( kind !== 'group' ) {
			S.mark = '';
			S.markedLabel = '';
		}

		paintMarked();

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

		// The images of the logo were opened from the brand, and the way back from them ends on the link that went there
		if( S.leftFor === 'logo' ) {
			S.leftFor = '';

			if( ( parts[1] ?? '' ) === 'brand' && ( parts[2] ?? '' ) === 'logo' && options.focus === false )
				options = { focus : 'logo' };
		}

		// A step of the browser's history between the levels puts the focus where the person's own step does
		if( options.history === true ) {

			const was = S.at[1] ?? '';
			const group = parts[1] ?? '';

			if( ( isGroup( group ) === true || group === 'brand' ) && group !== was )
				options = { focus : 'back' };
			else if( isGroup( group ) === false && group !== 'brand' && ( isGroup( was ) === true || was === 'brand' ) )
				options = { from : was };
		}

		S.at = parts.slice();

		const group = parts[1] ?? '';
		const known = isGroup( group ) === true || group === 'brand';

		const before = previewUri();

		// A level whose address names no section is one with every section closed - unless it is a way in: the address a person opens a theme, a group or
		// the brand by names none, and the first section is what they come to see
		const section = known === true ? ( parts[2] ?? '' ) : group;

		draw( known === true ? group : '', options.entry !== true && section === '' ? null : section );

		if( previewUri() !== before )
			reloadFrame();

		// The address says what is open: the section, or the level itself where there is none
		S.at = [ S.key ].concat( known === true ? [ group ] : [], S.view.accordion !== null ? S.view.accordion.get() : [] );
		Nino.admin.router.set( 'themes', addressOf( S.at ) );

		Nino.adminUi.showLevel( 'themes-theme', { focus : false } );
		thumbsAway();

		if( options.focus === false )
			return;

		let target = null;

		if( options.focus === 'back' )
			target = dc.getElementById('themes-back-groups');
		else if( options.focus === 'logo' )
			target = dc.getElementById('themes-logo-open');
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

	/**
	 *	The picture of a theme on its card: the start page as the theme draws it, small. Nothing is asked for until the card is on screen; then the API
	 *	makes the page a draft of its own, which is no preview of the person's own, and the frame is given its address. A picture is not a control: it
	 *	takes no focus, says nothing to a screen reader and takes no click (see the stylesheet)
	 *
	 *	@param		{Object}		theme				A theme of the list
	 *
	 *	@return		{Element}
	 */
	const thumbOf = function( theme ) {

		const box = el( 'div', 'themes-thumb' );

		box.setAttribute( 'aria-hidden', 'true' );
		box.dataset.thumb = theme.key;
		box.dataset.seen = theme.kind === 'own' ? theme.stamp : theme.version;

		if( S.looking !== null && S.listing === true )
			S.looking.observe( box );

		return box;
	};

	// The frame of a thumbnail, once there is an address for it
	const thumbFrame = function( box, query ) {

		const frame = el( 'iframe', 'themes-thumb-frame' );
		const dir = typeof Nino.dir === 'string' ? Nino.dir : '';

		frame.setAttribute( 'loading', 'lazy' );
		frame.setAttribute( 'tabindex', '-1' );
		frame.setAttribute( 'aria-hidden', 'true' );
		frame.setAttribute( 'src', dir+ homeOf()+ '?'+ query );
		box.appendChild( frame );
		Nino.adminUi.scaleFrame( frame, box, THUMB.width, THUMB.height );
	};

	// The pictures of the list are the list's: where the screen of a theme or of another view is shown they are taken away - thirty pages are not kept
	// loaded for a list that is not on screen - and no box is watched, so that a theme that is opened by its address asks for none; where the list
	// comes back its boxes are watched, and it draws what is on screen
	const thumbsAway = function() {

		const mount = dc.getElementById('themes-list');

		if( mount === null )
			return;

		Array.from( mount.querySelectorAll('.themes-thumb-frame') ).forEach( function( frame ) { frame.remove() } );

		if( S.looking !== null )
			Array.from( mount.querySelectorAll('.themes-thumb') ).forEach( function( box ) { S.looking.unobserve( box ) } );
	};

	const thumbsBack = function() {

		const mount = dc.getElementById('themes-list');

		if( mount !== null && S.looking !== null )
			Array.from( mount.querySelectorAll('.themes-thumb') ).forEach( function( box ) { S.looking.observe( box ) } );
	};

	// A card is on screen: its picture is asked for - once for as long as the answer holds, whatever is drawn in between
	const thumbAsk = function( box ) {

		const key = box.dataset.thumb+ '|'+ box.dataset.seen;
		const held = S.thumbs[key];

		if( box.querySelector('iframe') !== null )
			return;

		if( held !== undefined && Date.now() < held.until ) {
			thumbFrame( box, held.query );
			return;
		}

		Nino.admin.themes.call( 'thumb', { theme : box.dataset.thumb, token : held === undefined ? '' : held.token }, function( status, response ) {

			if( status !== 200 )
				return;

			S.thumbs[key] = { token : response.token, query : response.query, until : Date.now() + response.ttl * 1000 - GRACE };

			if( box.isConnected !== false && box.querySelector('iframe') === null )
				thumbFrame( box, response.query );
		} );
	};

	const cardOf = function( theme ) {

		const card = el( 'article', 'nino-admin-card themes-card' );
		const head = el( 'div', 'themes-card-head' );
		const resting = theme.kind === 'theme' ? Object.keys( theme.parts ).filter( function( group ) { return theme.parts[group].ok === false } ) : [];

		card.dataset.theme = theme.key;
		card.appendChild( thumbOf( theme ) );
		const title = el( 'h3', '', localized( theme.name ) );

		// A name that is longer than the card is cut; the whole of it is the title of the heading, and the text of it is whole for a screen reader
		title.title = localized( theme.name );
		head.appendChild( title );

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

		// What the card says is laid on a fixed number of lines, so that the cards of a list are as high as one another: the text that is cut is whole in
		// the title of its line and for a screen reader
		const about = el( 'p', 'nino-admin-hint themes-clamp', theme.kind === 'theme' ? localized( theme.description ) : madeOf( theme ) );

		about.title = about.textContent;
		card.appendChild( about );

		// A part that rests is told once, the first, and the chip counts the others: all of them are in the title
		if( resting.length > 0 ) {

			const note = el( 'p', 'nino-admin-hint themes-rests themes-clamp', word( 'msg/card-rests', groupLabelOf( resting[0] ), theme.parts[resting[0]].problem ) );

			note.title = resting.map( function( group ) { return word( 'msg/card-rests', groupLabelOf( group ), theme.parts[group].problem ) } ).join('\n');
			card.appendChild( note );
		}

		const actions = el( 'div', 'nino-admin-actions' );
		const edit = Nino.adminUi.button( word('label/edit'), function() { Nino.admin.themes.go( [ theme.key ], {} ) }, { className : 'nino-admin-btn-primary nino-admin-btn--small' } );

		edit.dataset.edit = theme.key;
		actions.appendChild( edit );

		const copy = Nino.adminUi.button( word('label/duplicate'), function() {
			duplicate( { key : theme.key, name : localized( theme.name ), stamp : function() { return theme.stamp ?? '' } }, copy, function( response ) {
				refreshList( function() {
					Nino.admin.themes.say( word( 'msg/duplicated', themeName( response.theme ) ) );

					// The list was drawn again: the card of the copy is a new one, and its Edit is where the focus goes
					land( dc.getElementById('themes-list').querySelector( '[data-edit="'+ response.theme+ '"]' ), null );
				} );
			} );
		}, { className : 'nino-admin-btn--small' } );

		copy.dataset.duplicate = theme.key;
		actions.appendChild( copy );

		if( theme.active !== true ) {
			const use = Nino.adminUi.button( word('label/apply'), function() {
				applyKey( theme.key, localized( theme.name ), use, function( ok ) {
					if( ok !== true )
						return;

					Nino.admin.themes.say( word( 'msg/applied', localized( theme.name ) ) );

					// The list was drawn again: the card of the theme that was applied is a new one, and its Edit is where the focus goes
					land( dc.getElementById('themes-list').querySelector( '[data-edit="'+ theme.key+ '"]' ), null );
				}, theme.stamp );
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
		S.canRestore = data.canRestore === true;
		S.last = data.last ?? null;
		S.state = typeof data.state === 'string' ? data.state : S.state;

		const mount = dc.getElementById('themes-list');
		const wrap = el( 'div', 'themes-cards' );
		const packaged = data.themes.filter( function( theme ) { return theme.kind === 'theme' } );
		const own = data.themes.filter( function( theme ) { return theme.kind === 'own' } );
		const sorted = function( list ) {
			return list.slice().sort( function( a, b ) { return localized( a.name ).localeCompare( localized( b.name ) ) } );
		};

		mount.innerHTML = '';

		// The cards that are on screen ask for their picture; the ones of the list that was drawn before are no cards any more
		if( S.looking !== null )
			S.looking.disconnect();

		S.listing = here().panel !== 'themes' || here().parts.length === 0;
		S.looking = new wn.IntersectionObserver( function( entries ) {
			entries.forEach( function( entry ) {
				if( entry.isIntersecting === true ) {
					S.looking.unobserve( entry.target );
					thumbAsk( entry.target );
				}
			} );
		}, { rootMargin : '120px' } );

		const say = el( 'p', 'nino-admin-hint themes-say' );

		say.setAttribute( 'role', 'status' );
		say.id = 'themes-say';
		mount.appendChild( say );

		if( data.foreign === true )
			mount.appendChild( Nino.adminUi.notice( word( data.unreadable === true ? 'msg/unreadable' : 'msg/foreign' ) ) );

		if( data.active !== null && data.active.stale === true )
			mount.appendChild( Nino.adminUi.notice( data.active.fonts === true ? word('msg/stale-fonts') : word('msg/stale') ) );

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
				dropDraft();
				paintBar();
				draw( here().parts[1] ?? '' );
				flushWait();
			},
		},
	} );

	Object.assign( Nino.admin.themes, {

		// Where the focus goes when an action is done (see land()): the tab of the fonts, assets/admin-fonts.js, lands the same way
		land : land,

		// The fonts of public/fonts/ changed on the tab of the fonts: the settings of an open theme are drawn again from the list, the draft as it is
		redraw : function() {
			if( S.view !== null )
				draw( S.view.group );
		},

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

			options = Object.assign( {}, options, { entry : true } );

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
				thumbsBack();
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
			const proceed = function() { Nino.admin.themes.follow( { focus : false, history : first === false, entry : first === true } ) };

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
