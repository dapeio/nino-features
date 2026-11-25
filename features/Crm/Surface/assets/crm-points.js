/**
 *	Crm		The surface of the CRM in the browser, the second of its four files: the registration points. A script of a package shows something on the surface through these and through nothing else: Nino.crm.settings (a tab
 *				and a section of the settings), .menu (an entry of the rail), .pages (a page with its addresses), .cards (the kinds of card, their tabs, the parts of their head and their facts), .lists (a mark and a column of a list), .overview
 *				(a tile, a block, a need, what to load), .setup (a step), .extras (a part of a sentence), .sites (the address of a thing on the customer's side), .search (a source of hits), .logbook (a kind of entry), .forms (a part of a form),
 *				.wipe (a line of the test phase), .keys (a letter that goes to a screen), .records (a kind of record), .apps (an app of its own), .state (the memory of a package), .kit (the helpers a package draws with, and the names it
 *				publishes for the packages after it).
 *
 *				The order of what is registered is by rank, and by the order of the registration where the ranks are the same; a key that is registered twice is an error. What a package gives is guarded where the surface draws it: code that
 *				throws, or answers what is no node, or draws nothing, leaves a sentence in the entry's place and nothing else is taken down. A package that registers late - its script loads after the surface is up - has the screen
 *				drawn again, unless the person has typed something.
 */

( function( wn, dc ) {

	'use strict';

	const core = wn.Nino.crm.core;
	const { ADDRESS, CLIENT_FIELDS, PAGE_SIZE, PHONE, SEARCH_MAX, api, append, ask, badge, call, card, cardList, charCount, clear, clientField, clientTitle, dialog, dmy, el, errorText, failed, formList, formOf, head, hm, keepRoles, keepSearch, kv, link, loadFailed, loading, menu, note, pagerOf, phone, pickerClients, post, projectLabel, projectNo, projectText, quietField, quietText, refresh, refuseAt, refusedText, reveal, saver, search, showLink, sortHead, stamp, state, step, submitter, t, toast, today, unsaver, valuesOfForm } = core;

	/**
	 *	The hits of a text in the lists the search sources have loaded: for each source, at most SEARCH_MAX hits, in the order of the source and of its list
	 *
	 *	@param		{Array}		loaded					[ { source, rows } ]: the sources in order, each with what it loaded
	 *	@param		{string}	text
	 *	@param		{Object}	kit
	 *
	 *	@return		{Array}									[ { kind, word, label, hint, href } ]
	 */
	const hitsOf = function( loaded, text, kit ) {

		const query = String( text ).trim().toLowerCase();

		if( query === '' )
			return [];

		const holds = function( parts ) { return parts.map( function( part ) { return String( part ?? '' ) } ).join( ' ' ).toLowerCase().includes( query ) };
		const hits = [];

		loaded.forEach( function( entry ) {

			const made = guarded( function() { return entry.source.hits( kit, entry.rows, holds ) } );

			if( made.ok === false || Array.isArray( made.value ) === false )
				return;

			made.value.filter( function( hit ) {
				return hit !== null && typeof hit === 'object' && typeof hit.label === 'string' && hit.label !== '' && typeof hit.href === 'string' && ADDRESS.test( hit.href ) === true;
			} ).slice( 0, SEARCH_MAX ).forEach( function( hit ) {
				hits.push( { kind : entry.source.key, word : entry.source.word, label : hit.label, hint : typeof hit.hint === 'string' ? hit.hint : '', href : hit.href } );
			} );
		} );

		return hits;
	};
	// --- The registration point of the settings ------------------------------------------------------------------------
	// The screen is a row of tabs over the sections of the chosen tab, and both are registered: a tab by its key, its word and its rank, a section by its key, the key of the tab it stands in, its word, its rank
	// and the function that builds it. The core registers its own the same way (registerSettings() below), and a script of a feature does it through Nino.crm.settings. The order is by rank, and by the order of
	// the registration where the ranks are the same; a key that is registered twice is an error. A tab with no section is not drawn

	const SETTINGS_KEY = /^[a-z][a-z0-9-]{0,39}$/;
	const SETTINGS_RANK = 500;
	const settingsList = { tabs : [], sections : [], count : 0 };
	/**
	 *	@param		{*}				word						A word, or a function that answers it when the screen is drawn (the words of the page are read from the data block, after the script is loaded)
	 *
	 *	@return		{boolean}
	 */
	const wordGiven = function( word ) {
		return typeof word === 'function' || ( typeof word === 'string' && word.trim() !== '' );
	};
	/**
	 *	The word of a tab or of a pill when the screen is drawn: the code of a feature runs here (a function is given the kit, which has the words of the page), and a word that throws or is no text is the fallback,
	 *	the failure logged, so that the screen is drawn
	 *
	 *	@param		{string|Function}	word
	 *	@param		{string}					fallback
	 *
	 *	@return		{string}
	 */
	const wordOf = function( word, fallback ) {

		let said = word;

		try {
			said = typeof word === 'function' ? word( kitOf( state.view ) ) : word;
		}
		catch( error ) {
			console.error( error );
			return fallback;
		}

		return typeof said === 'string' && said.trim() !== '' ? said : fallback;
	};
	/**
	 *	@param		{Object}	spec						The key, the word and the rank of the entry
	 *	@param		{string}	kind						'tab' or 'section'
	 *
	 *	@return		{void}										Throws for an entry that is none
	 */
	const settingsCheck = function( spec, kind ) {

		if( spec === null || typeof spec !== 'object' )
			throw new TypeError( 'A settings '+ kind+ ' is registered with an object' );

		if( typeof spec.key !== 'string' || SETTINGS_KEY.test( spec.key ) === false )
			throw new TypeError( 'The key of a settings '+ kind+ ' is lower case letters, digits and hyphens' );

		if( spec.rank !== undefined && Number.isFinite( spec.rank ) === false )
			throw new TypeError( 'The rank of the settings '+ kind+ ' "'+ spec.key+ '" is a number' );
	};
	/**
	 *	Register a tab of the settings
	 *
	 *	@param		{Object}	spec						key, word (what the tab says), rank (default 500)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addSettingsTab = function( spec ) {

		settingsCheck( spec, 'tab' );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The settings tab "'+ spec.key+ '" has a word' );

		if( settingsList.tabs.some( function( one ) { return one.key === spec.key } ) === true )
			throw new Error( 'The settings tab "'+ spec.key+ '" is registered already' );

		settingsList.tabs.push( { key : spec.key, word : spec.word, rank : spec.rank ?? SETTINGS_RANK, at : settingsList.count++ } );
		settingsChanged();
	};
	/**
	 *	Register a section of the settings
	 *
	 *	@param		{Object}	spec						key (the id of the section's block), tab (the key of its tab), word (its pill), or blocks ([ [ id, word ] ] - a pill for each block it holds, for a form that has several),
	 *																	rank (default 500), build( kit ): the node
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addSettingsSection = function( spec ) {

		settingsCheck( spec, 'section' );

		if( typeof spec.tab !== 'string' || SETTINGS_KEY.test( spec.tab ) === false )
			throw new TypeError( 'The settings section "'+ spec.key+ '" names the key of its tab' );

		if( typeof spec.build !== 'function' )
			throw new TypeError( 'The settings section "'+ spec.key+ '" has a build function' );

		const blocks = spec.blocks === undefined ? [ [ spec.key, spec.word ] ] : spec.blocks;

		if( Array.isArray( blocks ) === false || blocks.length === 0 || blocks.every( function( block ) { return Array.isArray( block ) === true && typeof block[0] === 'string' && SETTINGS_KEY.test( block[0] ) === true && wordGiven( block[1] ) === true } ) === false )
			throw new TypeError( 'The settings section "'+ spec.key+ '" has a word, or blocks with an id and a word each' );

		if( settingsList.sections.some( function( one ) { return one.key === spec.key } ) === true )
			throw new Error( 'The settings section "'+ spec.key+ '" is registered already' );

		settingsList.sections.push( { key : spec.key, tab : spec.tab, blocks : blocks, rank : spec.rank ?? SETTINGS_RANK, build : spec.build, at : settingsList.count++ } );
		settingsChanged();
	};
	/**
	 *	@param		{Array}		list
	 *
	 *	@return		{Array}										The entries by rank, by registration for the same rank
	 */
	const settingsOrdered = function( list ) {
		return list.slice().sort( function( a, b ) { return a.rank - b.rank || a.at - b.at } );
	};
	/**
	 *	@return		{Array}										The tabs that hold a section, in order, each with its sections in order
	 */
	const settingsTabs = function() {

		const sections = settingsOrdered( settingsList.sections );

		return settingsOrdered( settingsList.tabs ).map( function( tab ) {
			return Object.assign( {}, tab, { sections : sections.filter( function( section ) { return section.tab === tab.key } ) } );
		} ).filter( function( tab ) { return tab.sections.length > 0 } );
	};
	/**
	 *	A script that registers after the screen is drawn (it loads after crm.js) gets the screen drawn again, unless the person has typed something
	 *
	 *	@return		{void}
	 */
	const settingsChanged = function() {
		if( typeof state.settingsRedraw === 'function' )
			state.settingsRedraw();
	};
	core.expose( 'settings', { addTab : addSettingsTab, addSection : addSettingsSection, tabs : function() { return settingsTabs().map( function( tab ) { return { key : tab.key, word : tab.word, rank : tab.rank, sections : tab.sections.map( function( section ) { return section.key } ) } } ) } } );
	// --- The search ---

	// The sources the search of the overview asks: the core registers the clients and the projects, a package registers its own
	const searchList = { list : [], count : 0 };
	/**
	 *	Register a search source
	 *
	 *	@param		{Object}	spec						key (the kind of its hits, lower case letters, digits and hyphens), rank (500), word (the heading of its hits: a text or a function( kit ); search.group-<key> by default), load( kit ) (a promise of the rows to search, asked
	 *																		once when the field is first used), hits( kit, rows, holds ) (the hits of the text in the rows: [ { label, hint, href } ]; holds( [ parts ] ) says whether the parts hold the text)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addSearch = function( spec ) {

		entryCheck( spec, 'search source', SETTINGS_KEY );
		entryShape( searchList.list, spec, 'search source', [ 'load', 'hits' ] );

		if( spec.word !== undefined && wordGiven( spec.word ) === false )
			throw new TypeError( 'The search source "'+ spec.key+ '" has a word or none' );

		searchList.list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, word : spec.word ?? null, load : spec.load, hits : spec.hits, at : searchList.count++ } );
	};
	core.expose( 'search', { add : addSearch, sources : function() { return settingsOrdered( searchList.list ).map( function( one ) { return { key : one.key, rank : one.rank } } ) } } );
	// --- What a package loads for the overview ---

	const dataList = { list : [], count : 0 };
	/**
	 *	Register what the overview loads for a package: the answer is given to its tiles and blocks as overview[key]. A package that needs nothing but what crm/overview carries under packages needs none
	 *
	 *	@param		{Object}	spec						key, rank (500), load( kit ) (a promise of the value; one that fails leaves the value null)
	 *
	 *	@return		{void}										Throws where the key is taken or is a name of the overview
	 */
	const addData = function( spec ) {

		entryCheck( spec, 'overview data', SETTINGS_KEY );

		if( [ 'summary', 'needs', 'packages' ].includes( spec.key ) === true )
			throw new TypeError( 'The overview has "'+ spec.key+ '" already' );

		entryShape( dataList.list, spec, 'overview data', [ 'load' ] );
		dataList.list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, load : spec.load, at : dataList.count++ } );
		entryLate( function( found ) { return found.where.screen === 'overview' } );
	};
	// --- The kinds of the logbook ---

	const logbookKinds = [];
	/**
	 *	Register a kind of entry of the logbook that a package writes without a sentence: its script says it
	 *
	 *	@param		{Object}	spec						kind (the kind of the entry), source (the key of the package that writes it; the entries of any source for none), sentence( kit, entry ) (the text), link( kit, entry ) ({ href, text } of the place the entry leads to, or null)
	 *
	 *	@return		{void}										Throws where the kind of that source is taken
	 */
	const addLogbookKind = function( spec ) {

		entryCheck( Object.assign( {}, spec, { key : spec.kind } ), 'logbook kind', SETTINGS_KEY );
		entryShape( [], spec, 'logbook kind "'+ spec.kind+ '"', [ 'sentence' ], [ 'link' ] );

		if( spec.source !== undefined && ( typeof spec.source !== 'string' || SETTINGS_KEY.test( spec.source ) === false ) )
			throw new TypeError( 'The source of the logbook kind "'+ spec.kind+ '" is the key of a package' );

		if( logbookKinds.some( function( one ) { return one.kind === spec.kind && one.source === ( spec.source ?? null ) } ) === true )
			throw new Error( 'The logbook kind "'+ spec.kind+ '" is registered already' );

		logbookKinds.push( { kind : spec.kind, source : spec.source ?? null, sentence : spec.sentence, link : spec.link ?? null } );
	};
	/**
	 *	@param		{Object}	entry						An entry of the logbook
	 *
	 *	@return		{Object|null}						The kind a package registered for it: of its source first, else of any source
	 */
	const logbookKind = function( entry ) {
		return logbookKinds.find( function( one ) { return one.kind === entry.kind && one.source === entry.source } ) ?? logbookKinds.find( function( one ) { return one.kind === entry.kind && one.source === null && entry.source !== 'crm' } ) ?? null;
	};
	core.expose( 'logbook', { addKind : addLogbookKind, kinds : function() { return logbookKinds.map( function( one ) { return { kind : one.kind, source : one.source } } ) } } );
	// --- The forms of the clients and of the projects ---

	// The forms a package may add to: the form of a new client, the form of the master data of a client, the form of a new project. What a package adds is a node in the form, and the values it posts travel under packages.<key>
	const FORM_KINDS = [ 'client-new', 'client-master', 'project-new' ];
	const formList_ = { 'client-new' : { list : [], count : 0 }, 'client-master' : { list : [], count : 0 }, 'project-new' : { list : [], count : 0 } };
	/**
	 *	Add to a form
	 *
	 *	@param		{string}	kind						client-new, client-master or project-new
	 *	@param		{Object}	spec						key (the key of the package: the values are posted under it), rank (500), draw( kit, form ) (the node: its rows are made with kit.formList( rows, form ), so that the page guards what was typed and
	 *																		says a refusal at the field), values( kit ) (what is posted for the package: an object), done( kit, answer ) (what is done with the answer of the save)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const extendForm = function( kind, spec ) {

		if( FORM_KINDS.includes( kind ) === false )
			throw new TypeError( 'A form to add to is one of '+ FORM_KINDS.join( ', ' ) );

		entryCheck( spec, kind+ ' form part', SETTINGS_KEY );
		entryShape( formList_[kind].list, spec, kind+ ' form part', [ 'draw' ], [ 'values', 'done' ] );
		formList_[kind].list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, draw : spec.draw, values : spec.values ?? null, done : spec.done ?? null, at : formList_[kind].count++ } );
	};
	/**
	 *	The nodes the packages add to a form, in order: what each function draws, or the sentence that stands in its place
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	kit
	 *	@param		{Object}	form						The form of the screen
	 *
	 *	@return		{Array}
	 */
	const formParts = function( kind, kit, form ) {

		return settingsOrdered( formList_[kind].list ).map( function( part ) {
			return entryNode( function() { return part.draw( kit, form ) }, 'extension.form-failed', part.key );
		} ).filter( function( node ) { return node !== null } );
	};
	/**
	 *	What the packages post with a form
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	kit
	 *
	 *	@return		{Object}									{ packages : { key : values } }, or nothing for a form no package has values in
	 */
	const formValues = function( kind, kit ) {

		const packages = {};

		formList_[kind].list.filter( function( part ) { return part.values !== null } ).forEach( function( part ) {

			const made = guarded( function() { return part.values( kit ) } );

			if( made.ok === true && made.value !== null && typeof made.value === 'object' && Array.isArray( made.value ) === false )
				packages[part.key] = made.value;
		} );

		return Object.keys( packages ).length === 0 ? {} : { packages : packages };
	};
	/**
	 *	Tell the packages how the save went: each is given the answer
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	kit
	 *	@param		{Object}	answer
	 *
	 *	@return		{void}
	 */
	const formDone = function( kind, kit, answer ) {
		formList_[kind].list.filter( function( part ) { return part.done !== null } ).forEach( function( part ) { guarded( function() { return part.done( kit, answer ) } ) } );
	};
	/**
	 *	The fields of a client's form the core posts: the form holds the controls of the packages as well, and they travel under packages
	 *
	 *	@param		{Object}	form
	 *
	 *	@return		{Object}
	 */
	const coreValues = function( form ) {

		const all = valuesOfForm( form );
		const values = {};

		CLIENT_FIELDS.concat( [ 'business' ] ).forEach( function( name ) {
			if( all[name] !== undefined )
				values[name] = all[name];
		} );

		return values;
	};
	core.expose( 'forms', { extend : extendForm, kinds : function() { return FORM_KINDS.slice() } } );
	// --- The keys ---

	// A letter that goes to a screen: the core's own and the ones a package registers. The letter is a word of the language, so a registration gives it as a function; one that is taken is refused
	const keyList = { list : [], count : 0 };
	const KEY_TAKEN = [ 'g', 'n', '?', '/' ];
	/**
	 *	Register a key that goes to a screen
	 *
	 *	@param		{Object}	spec						key (one letter, or a function that answers it when the page is up), href (an address of the page), word (what the list of the keys says: a text or a function)
	 *
	 *	@return		{void}										Throws for a letter that is taken, in the list of the keys of the page and by the keys the page binds itself
	 */
	const addKey = function( spec ) {

		if( spec === null || typeof spec !== 'object' )
			throw new TypeError( 'A key is registered with an object' );

		if( typeof spec.key !== 'string' && typeof spec.key !== 'function' )
			throw new TypeError( 'The key of a shortcut is a letter, or a function that answers it' );

		if( typeof spec.href !== 'string' || ADDRESS.test( spec.href ) === false )
			throw new TypeError( 'The shortcut goes to an address of the page' );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The shortcut has a word' );

		if( typeof spec.key === 'string' && ( /^[a-z]$/.test( spec.key ) === false || KEY_TAKEN.includes( spec.key ) === true || keyLetters().includes( spec.key ) === true ) )
			throw new Error( 'The key "'+ spec.key+ '" is not free: one letter, not taken' );

		keyList.list.push( { key : spec.key, href : spec.href, word : spec.word, at : keyList.count++ } );
	};
	/**
	 *	The letter of a shortcut now
	 *
	 *	@param		{Object}	one
	 *
	 *	@return		{string}									'' for one that gives none
	 */
	const keyLetter = function( one ) {

		const made = guarded( function() { return typeof one.key === 'function' ? one.key( kitOf( state.view ) ) : one.key } );

		return made.ok === true && typeof made.value === 'string' && /^[a-z]$/.test( made.value ) === true ? made.value : '';
	};
	/**
	 *	@return		{Array}										The letters that are taken now
	 */
	const keyLetters = function() {
		return keyList.list.map( keyLetter ).filter( function( letter ) { return letter !== '' } );
	};
	/**
	 *	The shortcuts that go to a screen, by letter: a letter that two of them give belongs to the first (the one registered first) and the second is left out, with a line in the console
	 *
	 *	@return		{Object}									letter => { href, word }
	 */
	const keyTable = function() {

		const table = {};

		keyList.list.forEach( function( one ) {

			const letter = keyLetter( one );

			if( letter === '' || KEY_TAKEN.includes( letter ) === true )
				return;

			if( table[letter] !== undefined ) {
				console.error( new Error( 'The key "'+ letter+ '" is taken by "'+ table[letter].href+ '"; "'+ one.href+ '" has none' ) );
				return;
			}

			table[letter] = { href : one.href, word : one.word };
		} );

		return table;
	};
	core.expose( 'keys', { add : addKey, letters : function() { return Object.keys( keyTable() ) } } );
	// --- The records a customer left ---

	const recordKinds = [];
	/**
	 *	Register a kind of record a package keeps of what a customer did (a consent, an approval): how it is called in the dialog that shows it, and what it says besides the facts every record has
	 *
	 *	@param		{Object}	spec						kind, title( kit, record ) (the title of the dialog), body( kit, record ) (more facts: [ [ label, value ] ])
	 *
	 *	@return		{void}										Throws where the kind is taken
	 */
	const addRecordKind = function( spec ) {

		entryCheck( Object.assign( {}, spec, { key : spec.kind } ), 'record kind', SETTINGS_KEY );
		entryShape( recordKinds.map( function( one ) { return { key : one.kind } } ), Object.assign( {}, spec, { key : spec.kind } ), 'record kind', [ 'title' ], [ 'body' ] );
		recordKinds.push( { kind : spec.kind, title : spec.title, body : spec.body ?? null } );
	};
	/**
	 *	@param		{Object}	record						A record a package keeps of a customer
	 *
	 *	@return		{string}									Who agreed: the name of the account, its address where it has no name
	 */
	const recordWho = function( record ) {
		return record.member_name !== '' ? record.member_name : record.member_email;
	};
	/**
	 *	What the customer was shown, in a dialog: the text or the JSON as text, with its hash over it
	 *
	 *	@param		{Object}	record
	 *
	 *	@return		{Promise}
	 */
	const showRecord = function( record ) {

		const kit = kitOf( state.view );
		const entered = recordKinds.find( function( one ) { return one.kind === record.kind } );
		const facts = [ [ t( 'record.at' ), stamp( record.at ) ], [ t( 'record.ip' ), record.ip ], [ t( 'record.agent' ), record.agent ] ];
		const more = entered === undefined || entered.body === null ? { ok : false } : guarded( function() { return entered.body( kit, record ) } );
		const title = entered === undefined ? { ok : false } : guarded( function() { return entered.title( kit, record ) } );

		if( more.ok === true && Array.isArray( more.value ) === true )
			more.value.filter( function( fact ) { return Array.isArray( fact ) === true && typeof fact[0] === 'string' } ).forEach( function( fact ) { facts.push( [ fact[0], String( fact[1] ?? '' ) ] ) } );

		return dialog( { title: title.ok === true && typeof title.value === 'string' && title.value !== '' ? title.value : t( 'record.title' ), text: t( 'record.hash', record.sha256 ), facts: facts, pre: record.shown, close: true, wide: true } );
	};
	core.expose( 'records', { addKind : addRecordKind, kinds : function() { return recordKinds.map( function( one ) { return one.kind } ) } } );
	// --- The state of a package ---

	// What a package keeps of the page, by its key: made once with the state it starts at, given back to it on every call, and set to the start again where the page starts anew (boot) or forgets what it read (the wipe)
	const packageStates = [];
	/**
	 *	The state of a package
	 *
	 *	@param		{string}	key							The key of the package
	 *	@param		{Object}	initial					What the state starts at: a plain object of values that can be copied (JSON)
	 *
	 *	@return		{Object}									The state: the same object whenever it is asked for with the key
	 */
	const ownState = function( key, initial ) {

		if( typeof key !== 'string' || SETTINGS_KEY.test( key ) === false )
			throw new TypeError( 'The key of the state of a package is lower case letters, digits and hyphens' );

		if( initial === null || typeof initial !== 'object' || Array.isArray( initial ) === true )
			throw new TypeError( 'The state of a package starts as an object' );

		const known = packageStates.find( function( one ) { return one.key === key } );

		if( known !== undefined )
			return known.state;

		const start = JSON.stringify( initial );
		const own = { key : key, state : JSON.parse( start ), reset : function() {
			Object.keys( own.state ).forEach( function( name ) { delete own.state[name] } );
			Object.assign( own.state, JSON.parse( start ) );
		} };

		packageStates.push( own );

		return own.state;
	};
	core.expose( 'state', { own : ownState } );
	// --- The helpers the kit has that are not a screen's ---

	/**
	 *	@param		{string}	key
	 *
	 *	@return		{boolean}								Whether the page has a word
	 */
	const has = function( key ) {
		return typeof state.texts[key] === 'string';
	};
	/**
	 *	Put text on the clipboard of the browser
	 *
	 *	@param		{string}	text
	 *
	 *	@return		{Promise}								true where it is there, false where the browser has no clipboard or refuses it
	 */
	const copyText = function( text ) {

		if( wn.navigator === undefined || wn.navigator.clipboard === undefined || typeof wn.navigator.clipboard.writeText !== 'function' )
			return Promise.resolve( false );

		return wn.navigator.clipboard.writeText( text ).then( function() { return true }, function() { return false } );
	};
	/**
	 *	Save from an address: a link that is clicked, which the browser takes for a download
	 *
	 *	@param		{string}	href
	 *
	 *	@return		{void}
	 */
	const saveFrom = function( href ) {

		const anchor = el( 'a', { href : href, download : '' } );

		dc.body.appendChild( anchor );
		anchor.click();
		dc.body.removeChild( anchor );
	};
	/**
	 *	What a package page of the surface shows from its data: what the server put under the key of the package into the data block
	 *
	 *	@param		{string}	key
	 *
	 *	@return		{Object|null}
	 */
	const dataOf = function( key ) {
		return state.packages !== null && typeof state.packages === 'object' && Object.prototype.hasOwnProperty.call( state.packages, key ) === true && state.packages[key] !== undefined ? state.packages[key] : null;
	};
	// What the packages added to the kit, by the name they gave: the first of a name stays
	const kitAdded = {};
	/**
	 *	The parts of the kit that need no screen: the helpers of the surface as they are
	 *
	 *	@return		{Object}
	 */
	const kitCommon = function() {

		const base = {
			PAGE_SIZE : PAGE_SIZE, PHONE : PHONE,
			el : el, t : t, api : api, call : call, toast : toast, link : link, note : note, kv : kv, formOf : formOf, formList : formList, valuesOfForm : valuesOfForm, refuseAt : refuseAt, submitter : submitter,
			head : head, crumb : core.crumb, search : search, menu : menu, saver : saver, unsaver : unsaver,
			// What a package draws its own parts with: the date and the time as the surface writes them in the language of the page, the money, the node helpers, the dialog and the question, the label of a project
			format : wn.Nino.crm.format, dmy : dmy, hm : hm, stamp : stamp, today : today, clear : clear, append : append, dialog : dialog, ask : ask, projectLabel : projectLabel, projectText : projectText, projectNo : projectNo,
			// Whether the page has a word, the link to the workbench and what a package says of its own at the places the surface asks for it
			has : has, extra : extra, extraText : extraText, wordOf : wordOf, data : dataOf,
			get workbench() { return state.links.workbench },
			// The badge, the card, the sentence of an error and of a refusal, the step that posts and reads the count again, and the screens that ask again
			badge : badge, card : card, errorText : errorText, failed : failed, refusedText : refusedText, step : step, refresh : refresh, loading : loading, reveal : reveal, loadFailed : loadFailed, route : core.route,
			// The clients and the lists: the title of a client, the picker, the table that becomes cards on the phone, the sort, the pages, the search that keeps its cursor
			clientTitle : clientTitle, pickerClients : pickerClients, cardList : cardList, sortHead : sortHead, keepRoles : keepRoles, phone : phone, pagerOf : pagerOf, keepSearch : keepSearch,
			// The cards: the tabs, the body of a tab, the parts of the head
			cardTabs : cardTabs, cardBody : cardBody, headParts : headParts, headItems : headItems, headChoice : headChoice, headHasAction : headHasAction, headLines : headLines,
			settingsOrdered : settingsOrdered, sitePage : sitePage, quietField : quietField, quietText : quietText, saveFrom : saveFrom, showLink : showLink, charCount : charCount, copyText : copyText,
			recordWho : recordWho, showRecord : showRecord,
			// The picker of a client of the surface (the one of the new project and of the releases): clients are the list of crm-clients/list, the value of the picker is the number of the client (K-0001) and not its id, a preset is a number
			clientField : function( setup, clients, preset ) {

				const number = function( one ) { return one.number };
				const picker = clientField( setup, pickerClients( clients, number ), preset );

				// A section that reads its clients later fills the picker with them: fill( clients, number )
				picker.fill = function( list, chosen ) { picker.items( pickerClients( list, number ) ); picker.set( chosen ?? '' ) };

				return picker;
			},
			// The words of a package for the codes its endpoint answers a refusal with: error.<code>, which refuseAt() and call() say at the field and in the toast. A key the page has stays
			words : function( map ) {
				Object.keys( map || {} ).forEach( function( key ) {
					if( /^[a-z][a-z0-9._-]*$/.test( key ) === true && typeof map[key] === 'string' && typeof state.texts[key] !== 'string' )
						state.texts[key] = map[key];
				} );
			},
			// A post to an address of a package below /crm, with the token of the session: the answer has the shape api() gives
			post : function( path, data ) {

				if( typeof path !== 'string' || /^\/crm\/[a-z0-9][a-z0-9\/_.-]*$/.test( path ) === false || path.indexOf( '..' ) !== -1 )
					throw new TypeError( 'A package posts to an address below /crm' );

				return post( { headers : { 'Content-Type' : 'application/json', 'X-CSRF-Token' : state.csrf }, body : JSON.stringify( data || {} ) }, path );
			},
		};

		return Object.assign( base, kitAdded );
	};
	/**
	 *	The kit: what every function that is registered with the surface - a section of the settings, a page, a tab of a card, a tile or a block of the overview, a step of the setup - is given to build
	 *	its part with, the same object everywhere. A place adds the fields of its own data to it
	 *
	 *	@param		{number}	mine						The number of this drawing (state.view)
	 *	@param		{Object}	[form]					The form object of the screen; a new one by default
	 *
	 *	@return		{Object}
	 */
	const kitOf = function( mine, form ) {

		// The form object is made when a function asks for it: making one makes it the form the page asks about when a link leaves the screen
		let own = form;
		const formHere = function() {

			if( own === undefined )
				own = formOf();

			return own;
		};

		const base = kitCommon();

		// What a package draws the hosts of the settings with: the block of a host, and the sentence of a line of the status
		base.hostCard = function( spec, settings ) { return core.hostCard( spec, settings, formHere() ) };
		base.statusLine = core.statusLine;
		base.mine = mine;
		base.alive = function() { return state.view === mine };

		Object.defineProperty( base, 'form', { get : function() { return formHere() }, enumerable : true } );

		// The page asks before it lets go of a form that holds what was not stored: a section hands its form over while it does, and takes it back when it is saved
		base.arm = function( held ) { if( state.draft === null || state.draft.dirty !== true ) state.draft = held };
		base.release = function( held ) { if( state.draft === held ) state.draft = formHere().dirty === true ? own : null };

		return base;
	};
	/**
	 *	The kit for a script at load time: what a function that is registered is given, without what belongs to a screen that is drawn (main, alive, title, the form)
	 *
	 *	@param		{string}	key							The key of the package
	 *
	 *	@return		{Object}									Frozen
	 */
	const makeKit = function( key ) {

		if( typeof key !== 'string' || SETTINGS_KEY.test( key ) === false )
			throw new TypeError( 'The kit is made for a package with its key' );

		return Object.freeze( Object.assign( kitCommon(), { key : key } ) );
	};
	/**
	 *	Add a helper to the kit: a package that draws on a card it owns hands the packages that draw on it what they need to do it (a step on its own card, the card drawn again, the link for the
	 *	customer). The helper is in the kit of everything that is drawn from then on
	 *
	 *	@param		{string}	key							A name in camel case that the kit does not have
	 *	@param		{Function}	run							The helper
	 *
	 *	@return		{void}										Throws a TypeError for a name or a helper that is none, an Error where the name is taken (the first helper of a name stays)
	 */
	const addKit = function( key, run ) {

		if( typeof key !== 'string' || /^[a-z][A-Za-z0-9]{0,39}$/.test( key ) === false )
			throw new TypeError( 'The name of a helper of the kit is camel case letters and digits' );

		if( typeof run !== 'function' )
			throw new TypeError( 'The helper "'+ key+ '" of the kit is a function' );

		if( Object.prototype.hasOwnProperty.call( kitOf( 0 ), key ) === true )
			throw new Error( 'The kit has the helper "'+ key+ '"' );

		kitAdded[key] = run;
	};
	// The names of Nino.crm that belong to the core: a package publishes its own namespace beside them, never over one
	const RESERVED = [ 'kit', 'state', 'app', 'menu', 'pages', 'lists', 'cards', 'overview', 'setup', 'settings', 'extras', 'sites', 'needs', 'search', 'forms', 'wipe', 'keys', 'logbook', 'records', 'apps', 'format' ];
	/**
	 *	Publish the names of a package for the other packages: wn.Nino.crm.<key> is the object it gives, frozen. The core names none of them; it is the way a package offers a point of its own (a browser of its own
	 *	offers columns and rows to the script that comments on them). A key the core or another package has is refused, and so is the same key a second time
	 *
	 *	@param		{string}	key							The key of the package: lower case letters, digits and hyphens, one to forty
	 *	@param		{Object}	api							What the package offers
	 *
	 *	@return		{Object}									The frozen object
	 */
	const publish = function( key, api ) {

		if( typeof key !== 'string' || MENU_KEY.test( key ) === false || key.length > 40 )
			throw new TypeError( 'The name a package publishes is its key: lower case letters, digits and hyphens' );

		if( api === null || typeof api !== 'object' || Array.isArray( api ) === true )
			throw new TypeError( 'A package publishes an object' );

		if( RESERVED.includes( key ) === true || Object.prototype.hasOwnProperty.call( wn.Nino.crm, key ) === true )
			throw new Error( 'The name "'+ key+ '" is taken' );

		if( Object.isExtensible( wn.Nino.crm ) === false )
			throw new Error( 'The page has started: a name is published while the script loads' );

		return core.expose( key, api );
	};
	core.expose( 'kit', { add : addKit, publish : publish, make : makeKit } );
	// --- The apps ---

	// An app is a page with no rail that the script draws from the key the data block names (app): the core draws the tasks, a package draws its own
	const appList = { list : [], count : 0 };
	/**
	 *	Register an app
	 *
	 *	@param		{Object}	spec						key (lower case letters, the key of the app the data block says), route (the path of its page, /crm/<name>), draw( kit, where ) (draws the app into kit.main; where is what where() read from the hash),
	 *																		where( hash ) (the screen the hash names, an object; the app is one screen by default), home (the start of the keys of the words of its hint for the home screen: <home>title, <home>ios, <home>android)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addApp = function( spec ) {

		entryCheck( spec, 'app', MENU_KEY );
		entryShape( appList.list, spec, 'app', [ 'draw' ], [ 'where' ] );

		if( typeof spec.route !== 'string' || /^\/crm\/[a-z][a-z0-9-]*$/.test( spec.route ) === false )
			throw new TypeError( 'The app "'+ spec.key+ '" has a route below /crm' );

		if( spec.home !== undefined && ( typeof spec.home !== 'string' || /^[a-z][a-z0-9.-]*-$/.test( spec.home ) === false ) )
			throw new TypeError( 'The home of the app "'+ spec.key+ '" is the start of the keys of its words' );

		appList.list.push( { key : spec.key, route : spec.route, draw : spec.draw, where : spec.where ?? null, home : spec.home ?? null, at : appList.count++ } );
	};
	/**
	 *	The app the page is: the one of the key the data block says
	 *
	 *	@return		{Object|null}						None for the page of the surface
	 */
	const appOf = function() {
		return appList.list.find( function( app ) { return app.key === state.app } ) ?? null;
	};
	core.expose( 'apps', { add : addApp, list : function() { return appList.list.map( function( app ) { return { key : app.key, route : app.route } } ) } } );
	// --- test phase ---------------------------------------------------------------------------------------------------

	// What the test phase deletes is a list the core and the packages fill (wipe.addCount): a line of what goes and the sentence of its count in the toast. What it leaves is the core's alone, the key after wipe.stays-
	const WIPE_STAYS = [ 'settings', 'values', 'site', 'others' ];
	const wipeList = { list : [], count : 0 };
	/**
	 *	Register a line of what the test phase deletes
	 *
	 *	@param		{Object}	spec						key (the key of the count in the answer of crm/wipe, lower case letters), rank (500), goes (the line of the dialog: a text, or a function( kit ) that answers it), count (the sentence of the
	 *																	count in the toast: a text with %s for the number, or a function( kit, number ); none for a line that has no count)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addWipe = function( spec ) {

		entryCheck( spec, 'wipe line', SETTINGS_KEY );

		if( wordGiven( spec.goes ) === false )
			throw new TypeError( 'The wipe line "'+ spec.key+ '" says what goes' );

		if( spec.count !== undefined && wordGiven( spec.count ) === false )
			throw new TypeError( 'The count of the wipe line "'+ spec.key+ '" is a text or a function' );

		entryShape( wipeList.list, spec, 'wipe line', [] );
		wipeList.list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, goes : spec.goes, count : spec.count ?? null, at : wipeList.count++ } );
	};
	/**
	 *	What goes, in the words of the dialog, by rank, and then every step the server runs that no line speaks of, as a plain line with its key: the dialog says what the server does
	 *
	 *	@param		{Array}		[steps]					The keys of the steps the packages entered, as crm/settings-get has them
	 *
	 *	@return		{Array}										Texts
	 */
	const wipeLines = function( steps ) {

		const lines = settingsOrdered( wipeList.list ).map( function( line ) { return wordOf( line.goes, line.key ) } );

		( Array.isArray( steps ) === true ? steps : [] ).forEach( function( key ) {
			if( typeof key === 'string' && wipeList.list.some( function( line ) { return line.key === key } ) === false )
				lines.push( t( 'wipe.line-other', key ) );
		} );

		return lines;
	};
	/**
	 *	The sentence of the toast: what was deleted, count by count, in the order the server names the counts; a count nobody has a sentence for is left out
	 *
	 *	@param		{Object}	counts					As crm/wipe has them
	 *
	 *	@return		{string}
	 */
	const wipeSentence = function( counts ) {

		const kit = kitOf( state.view );
		const parts = [];

		Object.keys( counts ).forEach( function( key ) {

			const line = wipeList.list.find( function( one ) { return one.key === key } );

			if( line === undefined || line.count === null )
				return;

			const made = guarded( function() { return typeof line.count === 'function' ? line.count( kit, counts[key] ) : line.count.replace( /%s/g, String( counts[key] ) ) } );

			if( made.ok === true && typeof made.value === 'string' && made.value !== '' )
				parts.push( made.value );
		} );

		return t( 'wipe.done', parts.join( ', ' ) );
	};
	core.expose( 'wipe', { addCount : addWipe, lines : function() { return settingsOrdered( wipeList.list ).map( function( line ) { return { key : line.key, rank : line.rank } } ) } } );
	// --- The registration points -------------------------------------------------------------------------------------------
	// Besides the settings (above) the surface has five more places where a package shows something: the Main menu, the pages, the tabs of the card of a client and of a project, the tiles and the blocks of the
	// overview, and the steps of the setup; the head of the card of a client (marks, notes, the menu ⋯) is a sixth. Each is a list of entries that the core fills with its own and a script of a feature fills with its own through the same function (Nino.crm.menu, .pages, .cards,
	// .overview, .setup); the order is by rank, and by the order of the registration where the ranks are the same; a key that is registered twice is an error. What a feature gives is guarded where the surface draws
	// it: code that throws, or answers what is no node, or draws nothing, leaves a sentence in the entry's place and nothing else is taken down

	const ENTRY_RANK = 500;
	const MENU_KEY = /^[a-z0-9-]{1,40}$/;
	const PAGE_HASH = /^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/;
	const menuList = { list : [], count : 0 };
	const pageList = { list : [], count : 0 };
	const tileList = { list : [], count : 0 };
	const blockList = { list : [], count : 0 };
	const stepList = { list : [], count : 0 };
	/**
	 *	@param		{*}				spec
	 *	@param		{string}	what						What is registered, for the message
	 *	@param		{RegExp}	rule						The rule of the key
	 *
	 *	@return		{void}										Throws for an entry that is none
	 */
	const entryCheck = function( spec, what, rule ) {

		if( spec === null || typeof spec !== 'object' )
			throw new TypeError( 'A '+ what+ ' is registered with an object' );

		if( typeof spec.key !== 'string' || rule.test( spec.key ) === false )
			throw new TypeError( 'The key of a '+ what+ ' is lower case letters, digits and hyphens' );

		if( spec.rank !== undefined && Number.isFinite( spec.rank ) === false )
			throw new TypeError( 'The rank of the '+ what+ ' "'+ spec.key+ '" is a number' );
	};
	/**
	 *	@param		{Array}		list
	 *	@param		{Object}	spec
	 *	@param		{string}	what
	 *	@param		{Array}		functions				The names of the fields that are functions and must be
	 *	@param		{Array}		[optional]			The names of the fields that are functions when they are given
	 *
	 *	@return		{void}										Throws where the key is taken or a field is wrong
	 */
	const entryShape = function( list, spec, what, functions, optional ) {

		functions.forEach( function( name ) {
			if( typeof spec[name] !== 'function' )
				throw new TypeError( 'The '+ what+ ' "'+ spec.key+ '" has a '+ name+ ' function' );
		} );

		( optional || [] ).forEach( function( name ) {
			if( spec[name] !== undefined && spec[name] !== null && typeof spec[name] !== 'function' )
				throw new TypeError( 'The '+ name+ ' of the '+ what+ ' "'+ spec.key+ '" is a function' );
		} );

		if( list.some( function( one ) { return one.key === spec.key } ) === true )
			throw new Error( 'The '+ what+ ' "'+ spec.key+ '" is registered already' );
	};
	/**
	 *	What a function of a feature answers, guarded: the code of a feature runs here
	 *
	 *	@param		{Function}	run
	 *
	 *	@return		{Object}									ok (false where it threw) and value
	 */
	const guarded = function( run ) {

		try {
			return { ok : true, value : run() };
		}
		catch( error ) {
			console.error( error );
			return { ok : false, value : null };
		}
	};
	/**
	 *	Whether a node shows nothing: no child and no text
	 *
	 *	@param		{Object}	node
	 *
	 *	@return		{boolean}
	 */
	const nothingDrawn = function( node ) {
		return ( node.children === undefined || node.children.length === 0 ) && String( node.textContent ?? '' ).trim() === '';
	};
	/**
	 *	The sentence that stands in the place of an entry that cannot be drawn
	 *
	 *	@param		{string}	word						The key of the sentence
	 *	@param		{string}	key							The key of the entry
	 *
	 *	@return		{Object}
	 */
	const entryFailed = function( word, key ) {
		return note( 'bad', t( word ), ' ', el( 'span', { class: 'state-note', text: t( 'extension.key', key ) } ) );
	};
	/**
	 *	What a function that is to answer a node of a feature answers, as a node for the place: the node; a sentence where it threw, answered what is no node or an empty one; null where it answered null
	 *
	 *	@param		{Function}	run
	 *	@param		{string}		word					The key of the sentence
	 *	@param		{string}		key
	 *
	 *	@return		{Object|null}
	 */
	const entryNode = function( run, word, key ) {

		const made = guarded( run );

		if( made.ok === false )
			return entryFailed( word, key );

		if( made.value === null || made.value === undefined )
			return null;

		if( typeof made.value.appendChild !== 'function' ) {
			console.error( new TypeError( 'The '+ key+ ' drew no node' ) );
			return entryFailed( word, key );
		}

		return nothingDrawn( made.value ) === true ? entryFailed( word, key ) : made.value;
	};
	/**
	 *	A script that registers after the surface is up gets the screen drawn again, unless the person has typed something
	 *
	 *	@param		{Function}	whether					Whether the screen that is open is one that shows the entry; asked with the screen's name
	 *
	 *	@return		{void}
	 */
	const entryLate = function( whether ) {
		if( state.booted === true && ( state.draft === null || state.draft.dirty !== true ) && whether( resolveHash( wn.location.hash ) ) === true )
			core.route();
	};
	// --- Main menu ---

	/**
	 *	The text of a count in the rail: a number or a text; nothing where the entry has none
	 *
	 *	@param		{Object}	entry
	 *
	 *	@return		{string}
	 */
	const menuCount = function( entry ) {

		const made = guarded( entry.count );

		if( made.ok === false || made.value === null || made.value === undefined )
			return '';

		return typeof made.value === 'number' && Number.isFinite( made.value ) === true ? String( made.value ) : ( typeof made.value === 'string' ? made.value : '' );
	};
	/**
	 *	Register an entry of the rail
	 *
	 *	@param		{Object}	spec						key, word (text or a function that answers it), rank (500), page (the key of a page of the surface) or href (an address on this site), count (a function that answers the little number), visible (a function)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addMenu = function( spec ) {

		entryCheck( spec, 'menu entry', MENU_KEY );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The menu entry "'+ spec.key+ '" has a word' );

		const page = typeof spec.page === 'string' && SETTINGS_KEY.test( spec.page ) === true;
		const href = typeof spec.href === 'string' && ADDRESS.test( spec.href ) === true;

		if( page === href )
			throw new TypeError( 'The menu entry "'+ spec.key+ '" has a page or an address on this site (href), and one of them' );

		entryShape( menuList.list, spec, 'menu entry', [], [ 'count', 'visible' ] );
		menuList.list.push( { key : spec.key, word : spec.word, rank : spec.rank ?? ENTRY_RANK, page : page ? spec.page : null, href : href ? spec.href : null, count : spec.count ?? null, visible : spec.visible ?? null, source : spec.source === 'server' ? 'server' : '', at : menuList.count++ } );

		if( state.booted === true )
			drawMenu();
	};
	/**
	 *	The rail: an anchor for each entry, in order. The anchor of a page of the surface has data-r (the key of the entry), which the router marks; the anchor of an address has data-page and is left alone.
	 *	The page brings the rail in its markup (the entries of the core and those the server knows); where it is what the list says, its anchors stay - no flicker, the focus stays - and where it is not (a script of
	 *	a feature added an entry), the rail is drawn from the list
	 *
	 *	@return		{void}
	 */
	const drawMenu = function() {

		const nav = dc.getElementById( 'nav' );

		if( nav === null || nav === undefined )
			return;

		const wanted = [];

		settingsOrdered( menuList.list ).forEach( function( entry ) {

			const own = entry.page === null ? null : pageList.list.find( function( one ) { return one.key === entry.page } );

			if( entry.page !== null && own === undefined )
				return;

			const shown = entry.visible === null ? { ok : true, value : true } : guarded( entry.visible );
			const attrs = { href : entry.page === null ? entry.href : '#'+ own.hashes[0], id : 'nav-'+ entry.key, hidden : shown.ok === true && shown.value === false };

			attrs[entry.page === null ? 'data-page' : 'data-r'] = entry.key;
			wanted.push( { entry : entry, attrs : attrs, word : wordOf( entry.word, t( 'extension.word' ) ) } );
		} );

		// What the markup of the page holds is what the list says: the same attributes, the same word, a count where the entry has one
		const have = Array.from( nav.children );
		const same = have.length === wanted.length && wanted.every( function( one, at ) {

			const anchor = have[at];
			const count = anchor.querySelector( '.n' );

			return Object.keys( one.attrs ).every( function( name ) { return name === 'hidden' ? anchor.hidden === one.attrs.hidden : ( name === 'id' ? anchor.id === one.attrs.id : anchor.getAttribute( name ) === one.attrs[name] ) } )
				&& ( count !== null && count !== undefined ) === ( one.entry.count !== null ) && ( count === null || count === undefined || count.id === 'n-'+ one.entry.key )
				&& anchor.firstChild !== null && anchor.firstChild !== undefined && String( anchor.firstChild.textContent ) === one.word+ ( one.entry.count === null ? '' : ' ' );
		} );

		if( same === false ) {
			clear( nav );
			wanted.forEach( function( one ) {
				nav.appendChild( el( 'a', one.attrs, one.word+ ( one.entry.count === null ? '' : ' ' ), one.entry.count === null ? null : el( 'span', { class : 'n', id : 'n-'+ one.entry.key, text : menuCount( one.entry ) } ) ) );
			} );
		}

		menuCounts();
		markRail();
	};
	/**
	 *	The counts in the rail: each entry that has one is asked again
	 *
	 *	@return		{void}
	 */
	const menuCounts = function() {
		menuList.list.forEach( function( entry ) {

			const node = entry.count === null ? null : dc.getElementById( 'n-'+ entry.key );

			if( node !== null && node !== undefined )
				node.textContent = menuCount( entry );
		} );
	};
	/**
	 *	The entry of the page that is open is marked
	 *
	 *	@return		{void}
	 */
	const markRail = function() {

		dc.querySelectorAll( '[data-r]' ).forEach( function( anchor ) {

			const entry = menuList.list.find( function( one ) { return one.key === anchor.dataset.r } );
			const on = entry !== undefined && entry.page !== null && entry.page === state.lit;

			anchor.classList.toggle( 'on', on );

			if( on === true )
				anchor.setAttribute( 'aria-current', 'page' );
			else
				anchor.removeAttribute( 'aria-current' );
		} );
	};
	/**
	 *	The entries the server added to the rail (Surface\Menu::add) enter the list here, in the order it names them
	 *
	 *	@param		{*}		list							[ { key, word, href } ]
	 *
	 *	@return		{void}
	 */
	const menuFeed = function( list ) {

		menuList.list = menuList.list.filter( function( entry ) { return entry.source !== 'server' } );

		( Array.isArray( list ) === true ? list : [] ).forEach( function( one ) {

			const made = guarded( function() { addMenu( { key : one.key, word : one.word, href : one.href, source : 'server' } ) } );

			if( made.ok === false )
				console.error( new Error( 'The server entry of the menu "'+ String( one.key )+ '" is refused' ) );
		} );
	};
	core.expose( 'menu', { add : addMenu, refresh : menuCounts, entries : function() { return settingsOrdered( menuList.list ).map( function( entry ) { return { key : entry.key, word : entry.word, rank : entry.rank, page : entry.page, href : entry.href } } ) } } );
	// --- Pages ---

	/**
	 *	Register a page of the surface. The addresses of a page are #<hash>, #<hash>-<more> and #<hash>/<more>; no hash of one page begins with the hash of another followed by a hyphen or a slash
	 *
	 *	@param		{Object}	spec						key, hash (a string; the core names a list of them), title (text or a function), draw( kit, params ), parse( text, hash ) (the params of an address, or null where it is none)
	 *
	 *	@return		{void}										Throws where the key or a hash is taken
	 */
	const addPage = function( spec ) {

		entryCheck( spec, 'page', SETTINGS_KEY );

		if( wordGiven( spec.title ) === false )
			throw new TypeError( 'The page "'+ spec.key+ '" has a title' );

		const hashes = Array.isArray( spec.hash ) === true ? spec.hash : [ spec.hash ];

		if( hashes.length === 0 || hashes.every( function( one ) { return typeof one === 'string' && PAGE_HASH.test( one ) === true } ) === false )
			throw new TypeError( 'The hash of the page "'+ spec.key+ '" is lower case letters and digits, parts joined by hyphens' );

		entryShape( pageList.list, spec, 'page', [ 'draw' ], [ 'parse' ] );

		pageList.list.forEach( function( other ) {
			other.hashes.forEach( function( taken ) {
				hashes.forEach( function( one ) {
					if( one === taken || one.startsWith( taken+ '-' ) === true || taken.startsWith( one+ '-' ) === true )
						throw new Error( 'The hash "'+ one+ '" of the page "'+ spec.key+ '" is taken by the page "'+ other.key+ '"' );
				} );
			} );
		} );

		pageList.list.push( { key : spec.key, title : spec.title, hashes : hashes, draw : spec.draw, parse : spec.parse ?? null, at : pageList.count++ } );
		menuLate();
		entryLate( function( found ) { return found.entry.key === spec.key } );
	};
	/**
	 *	A page that arrives after the rail was drawn may be the page of an entry that waits for it
	 *
	 *	@return		{void}
	 */
	const menuLate = function() {
		if( state.booted === true )
			drawMenu();
	};
	/**
	 *	The page an address belongs to, and what the address says. An address that no page owns, and one that its page does not know, is the overview
	 *
	 *	@param		{string}	hash
	 *
	 *	@return		{Object}									entry (the page) and where (screen, and the params of the address)
	 */
	const resolveHash = function( hash ) {

		const text = String( hash || '' ).replace( /^#/, '' );

		for( const entry of pageList.list )
			for( const one of entry.hashes )
				if( text === one || text.startsWith( one+ '-' ) === true || text.startsWith( one+ '/' ) === true ) {

					const more = text.length > one.length ? text.slice( one.length + 1 ) : '';
					const read = entry.parse === null ? { ok : true, value : { hash : text, rest : more, sep : text.length > one.length ? text.charAt( one.length ) : '' } } : guarded( function() { return entry.parse( text, one ) } );

					// The code of a feature that reads its address and throws is the failure of its page: the address is the page's, and nothing else is touched
					if( read.ok === false )
						return { entry : entry, where : { screen : entry.key }, failed : true };

					if( read.value === null || read.value === undefined || typeof read.value !== 'object' )
						return { entry : pageOf( 'overview' ), where : { screen : 'overview' } };

					return { entry : entry, where : Object.assign( { screen : entry.key }, read.value ) };
				}

		return { entry : pageOf( 'overview' ), where : { screen : 'overview' } };
	};
	/**
	 *	@param		{string}	key
	 *
	 *	@return		{Object}									The page with the key
	 */
	const pageOf = function( key ) {
		return pageList.list.find( function( one ) { return one.key === key } );
	};
	/**
	 *	The sentence of a page that cannot be drawn, under its title; the rail and the rest of the surface stay
	 *
	 *	@param		{Object}	entry
	 *
	 *	@return		{void}
	 */
	const pageFailed = function( entry ) {

		reveal();
		state.main.appendChild( head( wordOf( entry.title, t( 'extension.word' ) ) ) );
		state.main.appendChild( entryFailed( 'extension.page-failed', entry.key ) );
	};
	/**
	 *	Draw a page: its function is given the kit and the params of the address. It draws into the main region itself (kit.main) or answers a node that is put there
	 *
	 *	@param		{Object}	found						entry and where
	 *	@param		{number}	mine
	 *
	 *	@return		{void}
	 */
	const showPage = function( found, mine ) {

		const entry = found.entry;

		if( found.failed === true ) {
			pageFailed( entry );
			return;
		}

		const kit = Object.assign( kitOf( mine ), { title : wordOf( entry.title, t( 'extension.word' ) ), main : state.main } );
		// The page draws into an empty region: what it leaves there, or answers, is what it drew
		clear( state.main );

		const made = guarded( function() { return entry.draw( kit, found.where ) } );

		if( made.ok === false ) {
			pageFailed( entry );
			return;
		}

		if( made.value === null || made.value === undefined ) {
			if( state.main.firstChild === null || state.main.firstChild === undefined )
				pageFailed( entry );

			return;
		}

		if( typeof made.value.appendChild !== 'function' || nothingDrawn( made.value ) === true ) {
			pageFailed( entry );
			return;
		}

		reveal();
		state.main.appendChild( made.value );
	};
	core.expose( 'pages', { add : addPage, list : function() { return pageList.list.map( function( entry ) { return { key : entry.key, hash : entry.hashes.slice(), title : entry.title } } ) } } );
	// --- The cards ---

	// The kinds of card a screen can draw: the core defines client and project, a package defines the kind of its own (cards.define). A kind has the screen that draws it (entries added late redraw that screen) and
	// the slots of its head: marks (small texts after the number, nodes), notes (blocks under the head, nodes), menu (items of the menu, a list of items and 'sep'), actions (a button or a menu in the row of
	// the head: nodes), and for a kind that has them primary (the action of the head: { action, run }, the first that answers one is the one) and history (lines of the history: [ [ time, text ] ])
	const SLOTS = [ 'marks', 'notes', 'menu', 'actions', 'primary', 'history' ];
	const cardKinds = {};
	const cardTabList = {};
	const headList = {};
	const factList = {};
	/**
	 *	Define a kind of card
	 *
	 *	@param		{string}	kind						Lower case letters, digits and hyphens
	 *	@param		{Object}	spec						screen (the key of the screen that draws the card), slots (the slots of its head, of marks, notes, menu, actions, primary, history)
	 *
	 *	@return		{void}										Throws where the kind is defined with another spec
	 */
	const defineCard = function( kind, spec ) {

		if( typeof kind !== 'string' || MENU_KEY.test( kind ) === false )
			throw new TypeError( 'The kind of a card is lower case letters, digits and hyphens' );

		if( spec === null || typeof spec !== 'object' || typeof spec.screen !== 'string' || Array.isArray( spec.slots ) === false || spec.slots.some( function( slot ) { return SLOTS.includes( slot ) === false } ) === true )
			throw new TypeError( 'The card "'+ kind+ '" is defined with a screen and slots of '+ SLOTS.join( ', ' ) );

		if( cardKinds[kind] !== undefined ) {

			if( cardKinds[kind].screen !== spec.screen || cardKinds[kind].slots.join() !== spec.slots.join() )
				throw new Error( 'The card "'+ kind+ '" is defined already' );

			return;
		}

		cardKinds[kind] = { screen : spec.screen, slots : spec.slots.slice() };
		cardTabList[kind] = { list : [], count : 0 };
		headList[kind] = { list : [], count : 0 };
		factList[kind] = { list : [], count : 0 };
	};
	/**
	 *	@param		{string}	kind
	 *	@param		{string}	what
	 *
	 *	@return		{void}										Throws for a kind nobody defined
	 */
	const knownCard = function( kind, what ) {
		if( cardKinds[kind] === undefined )
			throw new TypeError( 'A '+ what+ ' is for the card of a kind that was defined: '+ Object.keys( cardKinds ).join( ', ' ) );
	};
	/**
	 *	Register a tab of a card
	 *
	 *	@param		{string}	kind						A kind that was defined
	 *	@param		{Object}	spec						key, word, rank (500), count( record ) (the little number), draw( kit, record ); the record is the data of the card
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addCardTab = function( kind, spec ) {

		knownCard( kind, 'tab' );
		entryCheck( spec, kind+ ' tab', SETTINGS_KEY );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The '+ kind+ ' tab "'+ spec.key+ '" has a word' );

		entryShape( cardTabList[kind].list, spec, kind+ ' tab', [ 'draw' ], [ 'count' ] );
		cardTabList[kind].list.push( { key : spec.key, word : spec.word, rank : spec.rank ?? ENTRY_RANK, count : spec.count ?? null, draw : spec.draw, at : cardTabList[kind].count++ } );
		entryLate( function( found ) { return found.where.screen === cardKinds[kind].screen } );
	};
	/**
	 *	@param		{string}	kind
	 *
	 *	@return		{Array}										The tabs of the card, in order
	 */
	const cardTabs = function( kind ) {
		return settingsOrdered( cardTabList[kind].list );
	};
	// --- The head of a card ---

	/**
	 *	Register an addition to the head of a card
	 *
	 *	@param		{string}	kind						A kind that was defined
	 *	@param		{Object}	spec						key, slot (one of the slots of the kind), rank (500), draw( kit, record ): the node (marks, notes, actions; null for nothing), the list of items of the menu
	 *																	([ { label, run, danger? } | 'sep' ]), the action of the head ({ action, run }) or the lines of the history ([ [ time, text ] ]); the record is the data of the card
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addCardHead = function( kind, spec ) {

		knownCard( kind, 'part of the head' );
		entryCheck( spec, kind+ ' head part', SETTINGS_KEY );

		if( cardKinds[kind].slots.includes( spec.slot ) === false )
			throw new TypeError( 'The slot of the '+ kind+ ' head part "'+ spec.key+ '" is '+ cardKinds[kind].slots.join( ', ' ) );

		entryShape( headList[kind].list, spec, kind+ ' head part', [ 'draw' ] );
		headList[kind].list.push( { key : spec.key, slot : spec.slot, rank : spec.rank ?? ENTRY_RANK, draw : spec.draw, at : headList[kind].count++ } );
		entryLate( function( found ) { return found.where.screen === cardKinds[kind].screen } );
	};
	/**
	 *	Register a fact of a card: a row of the list of facts under its head
	 *
	 *	@param		{string}	kind						A kind that was defined
	 *	@param		{Object}	spec						key, rank (500), word (the name of the row: text or a function), draw( kit, record ): the value of the row (a node or text; null or '' for no row)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addCardFact = function( kind, spec ) {

		knownCard( kind, 'fact' );
		entryCheck( spec, kind+ ' fact', SETTINGS_KEY );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The '+ kind+ ' fact "'+ spec.key+ '" has a word' );

		entryShape( factList[kind].list, spec, kind+ ' fact', [ 'draw' ] );
		factList[kind].list.push( { key : spec.key, word : spec.word, rank : spec.rank ?? ENTRY_RANK, draw : spec.draw, at : factList[kind].count++ } );
		entryLate( function( found ) { return found.where.screen === cardKinds[kind].screen } );
	};
	/**
	 *	The rows the packages give the list of facts of a card, in order: each function's value under its word; a function that throws leaves its row out and the others stay
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	record					The card's data
	 *
	 *	@return		{Array}										[ [ term, node or text ] ], for kv()
	 */
	const cardFacts = function( kind, record ) {

		const kit = kitOf( record.mine );

		return settingsOrdered( factList[kind].list ).map( function( fact ) {

			const made = guarded( function() { return fact.draw( kit, record ) } );

			return [ wordOf( fact.word, t( 'extension.word' ) ), made.ok === true && made.value !== undefined && made.value !== null ? made.value : '' ];
		} );
	};
	// The two kinds the core draws: the card of a client and the card of a project
	defineCard( 'client', { screen : 'client', slots : [ 'marks', 'notes', 'menu', 'actions' ] } );
	defineCard( 'project', { screen : 'project', slots : [ 'notes', 'menu', 'actions' ] } );
	// --- The lists ---

	// The lists of the surface whose rows a package can mark and whose columns it can add to: the list of the clients (a mark after the name of the company), the list of the projects and the list of the projects
	// of a client (a column of its own). A package defines the list of its own (lists.define)
	const listKinds = {};
	const markList = {};
	const columnList = {};
	/**
	 *	Define a list whose rows are marked and whose columns packages add to
	 *
	 *	@param		{string}	kind						Lower case letters, digits and hyphens
	 *
	 *	@return		{void}
	 */
	const defineList = function( kind ) {

		if( typeof kind !== 'string' || MENU_KEY.test( kind ) === false )
			throw new TypeError( 'The kind of a list is lower case letters, digits and hyphens' );

		if( listKinds[kind] !== undefined )
			return;

		listKinds[kind] = true;
		markList[kind] = { list : [], count : 0 };
		columnList[kind] = { list : [], count : 0, loaded : null };
	};
	/**
	 *	Register a mark of the rows of a list
	 *
	 *	@param		{string}	list						A list that was defined
	 *	@param		{Object}	spec						key, rank (500), draw( kit, row ): the node of the mark for a row, or null for none
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addListMark = function( list, spec ) {

		if( listKinds[list] === undefined )
			throw new TypeError( 'A mark is for the rows of a list that was defined: '+ Object.keys( listKinds ).join( ', ' ) );

		entryCheck( spec, list+ ' mark', SETTINGS_KEY );
		entryShape( markList[list].list, spec, list+ ' mark', [ 'draw' ] );
		markList[list].list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, draw : spec.draw, at : markList[list].count++ } );
		entryLate( function( found ) { return found.where.screen === list+ 's' || found.where.screen === list } );
	};
	/**
	 *	Register a column of a list
	 *
	 *	@param		{string}	list						A list that was defined
	 *	@param		{Object}	spec						key, rank (500), head (the word of the column), align ('r' for a column of numbers), narrow (how the cell stands in a card: false leaves it out, 'second' puts it in the second line; none keeps the
	 *																	word of the column in front of it), cell( kit, row ) (the node or the text of the cell; null for an empty one), load( kit, rows ) (a promise that is answered once before the rows are drawn - what the cells need)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addColumn = function( list, spec ) {

		if( listKinds[list] === undefined )
			throw new TypeError( 'A column is for a list that was defined: '+ Object.keys( listKinds ).join( ', ' ) );

		entryCheck( spec, list+ ' column', SETTINGS_KEY );

		if( wordGiven( spec.head ) === false )
			throw new TypeError( 'The '+ list+ ' column "'+ spec.key+ '" has a head' );

		if( spec.align !== undefined && spec.align !== 'r' )
			throw new TypeError( 'The align of the '+ list+ ' column "'+ spec.key+ '" is r or none' );

		if( spec.narrow !== undefined && spec.narrow !== false && spec.narrow !== 'second' )
			throw new TypeError( 'The narrow of the '+ list+ ' column "'+ spec.key+ '" is false or second' );

		entryShape( columnList[list].list, spec, list+ ' column', [ 'cell' ], [ 'load' ] );
		columnList[list].list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, head : spec.head, align : spec.align ?? '', narrow : spec.narrow ?? true, cell : spec.cell, load : spec.load ?? null, at : columnList[list].count++ } );
		entryLate( function( found ) { return found.where.screen === list+ 's' || found.where.screen === list } );
	};
	/**
	 *	The columns the packages added to a list, in order
	 *
	 *	@param		{string}	list
	 *
	 *	@return		{Array}
	 */
	const listColumns = function( list ) {
		return settingsOrdered( columnList[list].list );
	};
	/**
	 *	What the columns of a list need before its rows are drawn: each load( kit, rows ) is asked once for the rows, and a promise that fails leaves its column empty - never the list
	 *
	 *	@param		{string}	list
	 *	@param		{Array}		rows
	 *
	 *	@return		{Promise}
	 */
	const loadColumns = function( list, rows ) {

		const kit = kitOf( state.view );

		return Promise.all( listColumns( list ).filter( function( column ) { return column.load !== null } ).map( function( column ) {

			const made = guarded( function() { return column.load( kit, rows ) } );

			return Promise.resolve( made.ok === true ? made.value : null ).catch( function( error ) { console.error( error ) } );
		} ) );
	};
	/**
	 *	The cell of a column for a row: the node or text its function draws, or the sentence that stands in its place; an empty cell is a dash
	 *
	 *	@param		{Object}	column
	 *	@param		{Object}	row
	 *
	 *	@return		{Object|string}
	 */
	const columnCell = function( column, row ) {

		const kit = kitOf( state.view );
		const made = guarded( function() { return column.cell( kit, row ) } );

		if( made.ok === false )
			return entryFailed( 'extension.column-failed', column.key );

		if( made.value === null || made.value === undefined || made.value === '' )
			return el( 'span', { class: 'muted', text: '–' } );

		return typeof made.value === 'string' || typeof made.value === 'number' || typeof made.value.appendChild === 'function' ? made.value : entryFailed( 'extension.column-failed', column.key );
	};
	/**
	 *	The marks of a row, in order: the node each function draws for it, or the sentence that stands in its place; nothing for a function that draws null
	 *
	 *	@param		{string}	list
	 *	@param		{Object}	row
	 *
	 *	@return		{Array}										Nodes
	 */
	const listMarks = function( list, row ) {

		const kit = kitOf( state.view );

		// A mark may be a node with nothing in it (a sign that CSS draws), so that only a failure and what is no node are a sentence
		return settingsOrdered( markList[list].list ).map( function( mark ) {

			const made = guarded( function() { return mark.draw( kit, row ) } );

			if( made.ok === false )
				return entryFailed( 'extension.mark-failed', mark.key );

			if( made.value === null || made.value === undefined )
				return null;

			if( typeof made.value.appendChild !== 'function' ) {
				console.error( new TypeError( 'The mark '+ mark.key+ ' drew no node' ) );
				return entryFailed( 'extension.mark-failed', mark.key );
			}

			return made.value;
		} ).filter( function( node ) { return node !== null } );
	};
	/**
	 *	The name a column of a package has in the card the table becomes on the phone: its key behind x-, and with :l the word of the column in front of it
	 *
	 *	@param		{Object}	column
	 *
	 *	@return		{string}
	 */
	const columnRole = function( column ) {
		return 'x-'+ column.key+ ( column.narrow === true ? ':l' : '' );
	};
	/**
	 *	The head of a column of a package
	 *
	 *	@param		{Object}	column
	 *
	 *	@return		{Object}
	 */
	const columnTh = function( column ) {
		return el( 'th', { scope: 'col', class: column.align === 'r' ? 'r' : null, text: wordOf( column.head, column.key ) } );
	};
	/**
	 *	The cell of a column of a package in a row of a table: how it stands in the card on the phone is said by data-narrow (off or second)
	 *
	 *	@param		{Object}	column
	 *	@param		{Object}	row
	 *
	 *	@return		{Object}
	 */
	const columnTd = function( column, row ) {
		return el( 'td', { class: column.align === 'r' ? 'r num' : null, dataset: column.narrow === true ? {} : { narrow : column.narrow === false ? 'off' : column.narrow } }, columnCell( column, row ) );
	};
	defineList( 'client' );
	defineList( 'project' );
	defineList( 'client-project' );
	core.expose( 'lists', { define : defineList, addMark : addListMark, addColumn : addColumn, kinds : function() { return Object.keys( listKinds ) }, marks : function( list ) { return listKinds[list] === undefined ? [] : settingsOrdered( markList[list].list ).map( function( mark ) { return { key : mark.key, rank : mark.rank } } ) }, columns : function( list ) { return listKinds[list] === undefined ? [] : listColumns( list ).map( function( column ) { return { key : column.key, rank : column.rank } } ) } } );
	/**
	 *	The nodes of a slot of the head, in order: what each function draws, or the sentence that stands in its place; nothing for a function that draws null
	 *
	 *	@param		{string}	kind
	 *	@param		{string}	slot						marks or notes
	 *	@param		{Object}	record					The card's data
	 *
	 *	@return		{Array}										Nodes
	 */
	const headParts = function( kind, slot, record ) {

		const kit = kitOf( record.mine );

		return settingsOrdered( headList[kind].list ).filter( function( part ) { return part.slot === slot } ).map( function( part ) {
			return entryNode( function() { return part.draw( kit, record ) }, 'extension.head-failed', part.key );
		} ).filter( function( node ) { return node !== null } );
	};
	/**
	 *	The items the packages add to the menu ⋯ of the head, in order: what each function answers, the ones that are an item or 'sep'; the failure of one function takes none of the others
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	record					The card's data
	 *
	 *	@return		{Array}										Items for menu()
	 */
	const headItems = function( kind, record ) {

		const kit = kitOf( record.mine );
		const items = [];

		settingsOrdered( headList[kind].list ).filter( function( part ) { return part.slot === 'menu' } ).forEach( function( part ) {

			const made = guarded( function() { return part.draw( kit, record ) } );

			if( made.ok === false || Array.isArray( made.value ) === false )
				return;

			made.value.forEach( function( item ) {
				if( item === 'sep' || ( item !== null && typeof item === 'object' && typeof item.label === 'string' && typeof item.run === 'function' ) )
					items.push( item );
			} );
		} );

		return items;
	};
	/**
	 *	The action a package gives the head of a card: the first part of the slot primary, by rank, that answers one
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	record					The card's data
	 *
	 *	@return		{Object|null}							{ action, run? }; null where no part answered one
	 */
	const headChoice = function( kind, record ) {

		const kit = kitOf( record.mine );
		const parts = settingsOrdered( headList[kind].list ).filter( function( part ) { return part.slot === 'primary' } );
		let chosen = null;

		parts.forEach( function( part ) {

			const made = guarded( function() { return part.draw( kit, record ) } );

			if( made.ok === false || made.value === null || made.value === undefined )
				return;

			const given = made.value;

			if( typeof given !== 'object' || typeof given.action !== 'string' || typeof given.run !== 'function' ) {
				console.error( new TypeError( 'The head part "'+ part.key+ '" gave an action that is none: an action and its run' ) );
				return;
			}

			if( chosen === null )
				chosen = given;
			else
				console.warn( 'The head part "'+ part.key+ '" gave an action as well, and the part before it by rank and registration has it: this one is not drawn' );
		} );

		return chosen;
	};
	/**
	 *	Whether a part gives the head an action: where one is registered, what it answers is the whole of the action - none included
	 *
	 *	@param		{string}	kind
	 *
	 *	@return		{boolean}
	 */
	const headHasAction = function( kind ) {
		return headList[kind].list.some( function( part ) { return part.slot === 'primary' } );
	};
	/**
	 *	The lines the packages give the history of a card, each with the rank of its part
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	record					The card's data
	 *
	 *	@return		{Array}										[ { rank, at, lines: [ [ time, text ] ] } ], in order
	 */
	const headLines = function( kind, record ) {

		const kit = kitOf( record.mine );

		return settingsOrdered( headList[kind].list ).filter( function( part ) { return part.slot === 'history' } ).map( function( part ) {

			const made = guarded( function() { return part.draw( kit, record ) } );
			const lines = made.ok === true && Array.isArray( made.value ) === true ? made.value.filter( function( line ) { return Array.isArray( line ) === true && typeof line[0] === 'string' && typeof line[1] === 'string' } ) : [];

			return { rank : part.rank, at : part.at, lines : lines };
		} );
	};
	/**
	 *	The body of a tab: what its function draws, or the sentence that stands in its place
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	tab
	 *	@param		{Object}	record
	 *
	 *	@return		{Object}
	 */
	const cardBody = function( kind, tab, record ) {

		const kit = kitOf( record.mine );

		// null says there is nothing to show: the body stays empty
		return entryNode( function() { return tab.draw( kit, record ) }, 'extension.tab-failed', tab.key );
	};
	/**
	 *	The tab strip of a card: a button for each tab, with its little number, and the body of the tab that is open under it
	 *
	 *	@param		{string}	kind
	 *	@param		{Object}	record					The card's data
	 *	@param		{Object}	body						The node the tab is drawn into
	 *	@param		{Function}	[before]			Called with the key before a tab is drawn
	 *
	 *	@return		{Object}									strip, badges (key to node) and show( key )
	 */
	const cardStrip = function( kind, record, body, before ) {

		const tabs = cardTabs( kind );
		const start = tabs.some( function( tab ) { return tab.key === state.tab } ) === true ? state.tab : tabs[0].key;
		const buttons = [];
		const badges = {};

		record.mine = state.view;
		state.tab = start;

		const show = function( key ) {

			state.tab = key;
			buttons.forEach( function( button ) {
				button.setAttribute( 'aria-selected', button.dataset.tab === key ? 'true' : 'false' );
				button.classList.toggle( 'on', button.dataset.tab === key );
			} );
			clear( body );

			if( before !== undefined )
				before( key );

			const drawn = cardBody( kind, tabs.find( function( tab ) { return tab.key === key } ), record );

			if( drawn !== null )
				body.appendChild( drawn );
		};

		const strip = el( 'div', { class: 'tabs', role: 'tablist' }, tabs.map( function( tab ) {

			const counted = tab.count === null ? { ok : true, value : null } : guarded( function() { return tab.count( record ) } );
			const count = counted.ok === true && ( typeof counted.value === 'number' || typeof counted.value === 'string' ) ? counted.value : null;
			const badge = count === null ? null : el( 'span', { class: 'n', text: String( count ) } );
			const button = el( 'button', { type: 'button', role: 'tab', 'aria-selected': tab.key === start ? 'true' : 'false', class: tab.key === start ? 'on' : '', dataset: { tab: tab.key }, on: { click: function() {
				if( state.view === record.mine )
					show( tab.key );
			} } }, wordOf( tab.word, t( 'extension.word' ) ), badge );

			badges[tab.key] = badge;
			buttons.push( button );

			return button;
		} ) );

		return { strip : strip, badges : badges, show : show, start : start };
	};
	core.expose( 'cards', { define : defineCard, addTab : addCardTab, addHead : addCardHead, addFact : addCardFact, kinds : function() { return Object.keys( cardKinds ) }, heads : function( kind ) { return cardKinds[kind] === undefined ? [] : settingsOrdered( headList[kind].list ).map( function( part ) { return { key : part.key, slot : part.slot, rank : part.rank } } ) }, tabs : function( kind ) { return cardKinds[kind] !== undefined ? cardTabs( kind ).map( function( tab ) { return { key : tab.key, word : tab.word, rank : tab.rank } } ) : [] }, facts : function( kind ) { return cardKinds[kind] === undefined ? [] : settingsOrdered( factList[kind].list ).map( function( fact ) { return { key : fact.key, rank : fact.rank } } ) } } );
	// --- overview ---

	/**
	 *	Register a tile of the row of numbers on the overview
	 *
	 *	@param		{Object}	spec						key, rank (500), draw( kit, overview ): the tile, or null for no tile; overview is what the core read for the screen: summary, needs and packages (crm/overview) and what the packages registered to load (overview.addData)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addTile = function( spec ) {

		entryCheck( spec, 'tile', SETTINGS_KEY );
		entryShape( tileList.list, spec, 'tile', [ 'draw' ] );
		tileList.list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, draw : spec.draw, at : tileList.count++ } );
		entryLate( function( found ) { return found.where.screen === 'overview' } );
	};
	/**
	 *	Register a block of the overview, under the tiles
	 *
	 *	@param		{Object}	spec						key, rank (500), size ('full' by default; two 'half' blocks one after the other stand side by side), draw( kit, overview ): the block, or null for none
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addBlock = function( spec ) {

		entryCheck( spec, 'block', SETTINGS_KEY );

		if( spec.size !== undefined && [ 'full', 'half' ].includes( spec.size ) === false )
			throw new TypeError( 'The size of the block "'+ spec.key+ '" is full or half' );

		entryShape( blockList.list, spec, 'block', [ 'draw' ] );
		blockList.list.push( { key : spec.key, rank : spec.rank ?? ENTRY_RANK, size : spec.size ?? 'full', draw : spec.draw, at : blockList.count++ } );
		entryLate( function( found ) { return found.where.screen === 'overview' } );
	};
	/**
	 *	The row of tiles
	 *
	 *	@param		{Object}	overview
	 *	@param		{number}	mine
	 *
	 *	@return		{Object}
	 */
	const overviewTiles = function( overview, mine ) {

		const kit = kitOf( mine );

		return el( 'div', { class: 'tiles' }, settingsOrdered( tileList.list ).map( function( entry ) {
			return entryNode( function() { return entry.draw( kit, overview ) }, 'extension.tile-failed', entry.key );
		} ) );
	};
	/**
	 *	The blocks under the tiles, in order; the blocks of the size half are laid two to a row
	 *
	 *	@param		{Object}	overview
	 *	@param		{number}	mine
	 *
	 *	@return		{Array}										The nodes to put into the screen
	 */
	const overviewBlocks = function( overview, mine ) {

		const kit = kitOf( mine );
		const nodes = [];
		let row = null;

		settingsOrdered( blockList.list ).forEach( function( entry ) {

			const node = entryNode( function() { return entry.draw( kit, overview ) }, 'extension.block-failed', entry.key );

			if( node === null )
				return;

			if( entry.size !== 'half' ) {
				row = null;
				nodes.push( node );
				return;
			}

			if( row === null || row.children.length >= 2 ) {
				row = el( 'div', { class: 'cols' } );
				nodes.push( row );
			}

			row.appendChild( node );
		} );

		// A half block that stands alone in its row takes the whole row: no empty half beside it
		nodes.forEach( function( node ) {
			if( node.className === 'cols' && node.children.length === 1 )
				node.className = 'cols one';
		} );

		return nodes;
	};
	core.expose( 'overview', { addTile : addTile, addData : addData, addBlock : addBlock, addNeed : function( spec ) { return addNeed( spec ) }, needs : function() { return needList.list.map( function( entry ) { return { key : entry.key } } ) }, tiles : function() { return settingsOrdered( tileList.list ).map( function( entry ) { return { key : entry.key, rank : entry.rank } } ) }, blocks : function() { return settingsOrdered( blockList.list ).map( function( entry ) { return { key : entry.key, rank : entry.rank, size : entry.size } } ) } } );
	// --- Setup ---

	/**
	 *	Register a step of the checklist of the setup
	 *
	 *	@param		{Object}	spec						key, word, rank (500), done( status ) (true or false; null for no row), hint( status ) (true where the step is told and does not keep the list from being whole), draw( kit, status ) (the sentence under the word)
	 *
	 *	@return		{void}										Throws where the key is taken
	 */
	const addStep = function( spec ) {

		entryCheck( spec, 'setup step', SETTINGS_KEY );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The setup step "'+ spec.key+ '" has a word' );

		entryShape( stepList.list, spec, 'setup step', [ 'done' ], [ 'hint', 'draw' ] );
		stepList.list.push( { key : spec.key, word : spec.word, rank : spec.rank ?? ENTRY_RANK, done : spec.done, hint : spec.hint ?? null, draw : spec.draw ?? null, at : stepList.count++ } );
		entryLate( function( found ) { return found.where.screen === 'setup' } );
	};
	/**
	 *	How a step stands for a status: done (true, false or null for no row) and whether it is only a hint. A step whose function throws is not done
	 *
	 *	@param		{Object}	step
	 *	@param		{Object}	status
	 *
	 *	@return		{Object}
	 */
	const stepStand = function( step, status ) {

		const done = guarded( function() { return step.done( status ) } );
		const hint = step.hint === null ? { ok : true, value : false } : guarded( function() { return step.hint( status ) } );

		return { done : done.ok === true ? ( done.value === null || done.value === undefined ? null : done.value === true ) : false, failed : done.ok === false, hint : hint.ok === true && hint.value === true };
	};
	/**
	 *	Whether every step is done, or a hint
	 *
	 *	@param		{Object}	status					As crm/status has it: lines
	 *
	 *	@return		{boolean}
	 */
	const setupWhole = function( status ) {
		return stepList.list.every( function( step ) {

			const stand = stepStand( step, status );

			return stand.done !== false || stand.hint === true || stand.failed === true;
		} ) && setupStray( status ).every( function( line ) { return core.complete( line ) || line.hint === true } );
	};
	/**
	 *	The lines of the status that no step is registered for - the lines of the core and the lines a package entered on the server - are drawn as rows of their own
	 *
	 *	@param		{Object}	status
	 *
	 *	@return		{Array}
	 */
	const setupStray = function( status ) {
		return status.lines.filter( function( line ) { return stepList.list.some( function( step ) { return step.key === line.key } ) === false } );
	};
	core.expose( 'setup', { addStep : addStep, steps : function() { return settingsOrdered( stepList.list ).map( function( step ) { return { key : step.key, word : step.word, rank : step.rank } } ) } } );
	// --- What a package adds of sentences, addresses and colors ---
	// Four more places, for what a package says of its own where it is there and the core does not say at all: the extras (a part of a sentence, a whole sentence, or a node that stands after one), the pages of the customer's side
	// (the address a thing has there), the kinds of what needs the owner (the overview), and what a package loads for the overview. They are lists like the others: the order is
	// the rank, a key that is taken is an error, what is not what it should be is a TypeError, what a package gives is guarded where the surface uses it, and a package that registers late has the screen drawn again

	// The names of the extras the surface asks for: the surface says what each is for where it asks (extra( name, ...params ))
	const EXTRA_KEYS = [ 'projects-missing', 'values-no-types', 'crm-host-note', 'crm-host-hint', 'brand-color', 'mails-note' ];
	// A package that has a place of its own for a sentence (kit.extraText( '<package>:<name>' )) names it with its own key in front, so that another package may add to it: the core
	// asks for no such name and holds the text until the place asks, and a bare name that is no name of the core is still an error (a typo of one of the six)
	const EXTRA_OWN = /^[a-z][a-z0-9-]{0,39}:[a-z][a-z0-9-]{0,39}$/;
	const extraList = { list : [], count : 0 };
	/**
	 *	Register an extra
	 *
	 *	@param		{Object}	spec						key (one of the names the surface asks for, or '<package>:<name>' for a place a package has of its own), word (a text, or a function( kit, ...params ) that answers a text, or a list of texts and nodes)
	 *
	 *	@return		{void}										Throws where the key is taken or is no name of the surface and no name with a package in front
	 */
	const addExtra = function( spec ) {

		entryCheck( spec, 'extra', typeof spec?.key === 'string' && EXTRA_OWN.test( spec.key ) === true ? EXTRA_OWN : SETTINGS_KEY );

		if( EXTRA_KEYS.includes( spec.key ) === false && EXTRA_OWN.test( spec.key ) === false )
			throw new TypeError( 'The surface has no place for the extra "'+ spec.key+ '"' );

		if( wordGiven( spec.word ) === false )
			throw new TypeError( 'The extra "'+ spec.key+ '" has a word' );

		entryShape( extraList.list, spec, 'extra', [] );
		extraList.list.push( { key : spec.key, word : spec.word, rank : ENTRY_RANK, at : extraList.count++ } );
		entryLate( function() { return true } );
	};
	/**
	 *	What the packages give a place of the surface: the extra of that name, guarded
	 *
	 *	@param		{string}	key
	 *	@param		{...*}		params					What the place gives the extra to say
	 *
	 *	@return		{string|Array|null}				A text, or a list of texts and nodes; null where no package gave one, or where it threw or gave nothing of the kind
	 */
	const extra = function( key, ...params ) {

		const entry = extraList.list.find( function( one ) { return one.key === key } );

		if( entry === undefined )
			return null;

		const made = guarded( function() { return typeof entry.word === 'function' ? entry.word( kitOf( state.view ), ...params ) : entry.word } );

		return made.ok === true && ( typeof made.value === 'string' || Array.isArray( made.value ) === true ) ? made.value : null;
	};
	/**
	 *	@param		{string}	key
	 *	@param		{...*}		params
	 *
	 *	@return		{string}									The text of the extra; '' where there is none
	 */
	const extraText = function( key, ...params ) {

		const made = extra( key, ...params );

		return typeof made === 'string' ? made : '';
	};
	/**
	 *	A sentence of the surface with what a package adds to its end: the sentence and the text that begins the extra are one text node, the rest of the extra (nodes, and the texts between them) follow
	 *
	 *	@param		{string}	text
	 *	@param		{string}	key							The name of the extra
	 *	@param		{...*}		params
	 *
	 *	@return		{Array}										The things to put into a node
	 */
	const sentenceWith = function( text, key, ...params ) {

		const made = extra( key, ...params );

		if( made === null )
			return [ text ];

		const parts = typeof made === 'string' ? [ made ] : made.slice();

		return typeof parts[0] === 'string' ? [ text+ parts.shift() ].concat( parts ) : [ text ].concat( parts );
	};
	core.expose( 'extras', { add : addExtra, keys : function() { return EXTRA_KEYS.slice() }, list : function() { return extraList.list.map( function( one ) { return { key : one.key } } ) } } );
	// --- The pages of the customer's side ---

	// What a thing is that a package knows a page for on the customer's side is a kind a package defines (sites.define): the core knows none
	const siteKinds = [];
	const siteList = { list : [], count : 0 };
	/**
	 *	Define a kind of thing that has a page on the customer's side
	 *
	 *	@param		{string}	kind						Lower case letters, digits and hyphens
	 *
	 *	@return		{void}
	 */
	const defineSite = function( kind ) {

		if( typeof kind !== 'string' || SETTINGS_KEY.test( kind ) === false )
			throw new TypeError( 'The kind of a customer page is lower case letters, digits and hyphens' );

		if( siteKinds.includes( kind ) === false )
			siteKinds.push( kind );
	};
	/**
	 *	Register the address a kind of thing has on the customer's side. Where nobody registered one the surface offers no link and no invitation for that kind
	 *
	 *	@param		{Object}	spec						key (a kind that was defined), address( kit, thing ): the address as the server takes it ('/...'), or null where the thing has no page; what the thing is
	 *																	is the package's, which defines the kind
	 *
	 *	@return		{void}										Throws where the key is taken or is no kind
	 */
	const addSite = function( spec ) {

		entryCheck( spec, 'customer page', SETTINGS_KEY );

		if( siteKinds.includes( spec.key ) === false )
			throw new TypeError( 'The customer\'s side has no page of the kind "'+ spec.key+ '"' );

		entryShape( siteList.list, spec, 'customer page', [ 'address' ] );
		siteList.list.push( { key : spec.key, address : spec.address, at : siteList.count++ } );
		entryLate( function() { return true } );
	};
	/**
	 *	@param		{string}	kind
	 *	@param		{Object}	thing
	 *
	 *	@return		{string|null}							The address of the thing on the customer's side; null where no package has a page for it
	 */
	const sitePage = function( kind, thing ) {

		const kit = kitOf( state.view );
		const entry = siteList.list.find( function( one ) { return one.key === kind } );

		if( entry === undefined )
			return null;

		const made = guarded( function() { return entry.address( kit, thing ) } );

		return made.ok === true && typeof made.value === 'string' && made.value.startsWith( '/' ) === true ? made.value : null;
	};
	/**
	 *	@param		{string}	kind
	 *
	 *	@return		{boolean}									Whether a package has registered the kind
	 */
	const siteHas = function( kind ) {
		return siteList.list.some( function( one ) { return one.key === kind } );
	};
	core.expose( 'sites', { define : defineSite, add : addSite, has : siteHas, kinds : function() { return siteKinds.slice() }, list : function() { return siteList.list.map( function( one ) { return { key : one.key } } ) } } );
	// --- What needs the owner ---

	// The kinds of need are the packages': the overview names what needs the owner, and the package that names a kind draws its line
	const needList = { list : [], count : 0 };
	/**
	 *	Register the line of a kind of need
	 *
	 *	@param		{Object}	spec						key (the kind the server names: lower case letters, digits and hyphens), draw( kit, need ): the li, or a list of them, or null for none; the lines stand in the order the server names them
	 *
	 *	@return		{void}										Throws where the kind is taken
	 */
	const addNeed = function( spec ) {

		entryCheck( spec, 'need', SETTINGS_KEY );

		entryShape( needList.list, spec, 'need', [ 'draw' ] );
		needList.list.push( { key : spec.key, draw : spec.draw, at : needList.count++ } );
		entryLate( function( found ) { return found.where.screen === 'overview' } );
	};
	/**
	 *	@param		{Object}	need						An entry of needs
	 *
	 *	@return		{boolean}									Whether a line can be drawn for it: a package registered the kind
	 */
	const needKnown = function( need ) {
		return needList.list.some( function( one ) { return one.key === need.kind } );
	};
	/**
	 *	@param		{Object}	need
	 *
	 *	@return		{Object|Array|null}				What the package registered for the kind draws: its line or lines, the sentence where it threw or gave what is no line, null for none
	 */
	const needExtra = function( need ) {

		const entry = needList.list.find( function( one ) { return one.key === need.kind } );
		const kit = kitOf( state.view );
		const made = guarded( function() { return entry.draw( kit, need ) } );

		if( made.ok === false )
			return el( 'li', {}, entryFailed( 'extension.block-failed', entry.key ) );

		if( made.value === null || made.value === undefined )
			return null;

		const lines = Array.isArray( made.value ) === true ? made.value : [ made.value ];

		if( lines.every( function( one ) { return one !== null && typeof one === 'object' && typeof one.appendChild === 'function' } ) === false ) {
			console.error( new TypeError( 'The need '+ entry.key+ ' drew no node' ) );
			return el( 'li', {}, entryFailed( 'extension.block-failed', entry.key ) );
		}

		return made.value;
	};
	// --- What the core registers ---

	/**
	 *	A pattern for the tab at the end of the address of a card: the keys of the tabs that are registered
	 *
	 *	@param		{string}	kind
	 *	@param		{Array}		[more]					Keys that are there besides
	 *
	 *	@return		{string}
	 */
	const tabPattern = function( kind, more ) {
		return cardTabList[kind].list.map( function( tab ) { return tab.key } ).concat( more || [] ).join( '|' );
	};

	Object.assign( core, { WIPE_STAYS, addApp, addBlock, addCardTab, addKey, addMenu, addPage, addSearch, addSettingsSection, addSettingsTab, addTile, addWipe, appList, appOf, cardFacts, cardStrip, columnRole, columnTd, columnTh, copyText, coreValues, dataList, drawMenu, entryFailed, entryNode, extraText, formDone, formParts, formValues, guarded, has, headItems, headParts, hitsOf, keyTable, kitOf, listColumns, listMarks, loadColumns, logbookKind, markRail, menuCounts, menuFeed, needExtra, needKnown, overviewBlocks, overviewTiles, packageStates, resolveHash, searchList, sentenceWith, settingsOrdered, settingsTabs, setupStray, setupWhole, showPage, stepList, stepStand, tabPattern, wipeLines, wipeSentence, wordOf } );

} )( window, document );
