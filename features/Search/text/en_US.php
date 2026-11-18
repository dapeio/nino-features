<?php
declare(strict_types=1);

// The panel's own words, one file for each interface language (see
// Admin::text()): mixed into the fills of the workbench while the feature is
// active, never into the text files of the project. The words of the page - of
// the search field and of the list of hits - are the other set, in
// features/Search/install/text/. A %s, %d and %n is filled by the script
return [

	// The entry in the navigation and the name over the panel
	'[[/_admin/nav/search]]'								=> 'Search',

	'[[/_admin/search/label/tile]]'					=> 'Elements searchable',
	'[[/_admin/search/label/tile-stale]]'		=> 'Searchable (index stale)',

	// The three tabs
	'[[/_admin/search/tab/index]]'						=> 'Index',
	'[[/_admin/search/tab/types]]'						=> 'Types',
	'[[/_admin/search/tab/probe]]'						=> 'Try it',

	// The help behind the (?) of the sections
	'[[/_admin/search/hint/index]]'						=> 'One row for each element type this project has. What is indexed is what can be found - everything else the search will not return, however plainly it is on the page. An index rebuilds itself on every save of an element; here you renew it by hand.',
	'[[/_admin/search/hint/types]]'						=> 'Which fields of a type its index holds. A type with no fields is not indexed.',
	'[[/_admin/search/hint/fields]]'						=> 'Up to four fields, the strongest first: in the order you tick them they count 1.00, 0.70, 0.45 and 0.25. The weight decides the order of the hits, not whether a word counts as found. The fields that carry text are offered; images, references and yes/no fields are not, nothing can be searched in them.',
	'[[/_admin/search/hint/stale]]'						=> 'The index is older than the type, was built out of other fields than the ones listed here, or comes from an earlier version of the feature - with words folded another way, or without the elements a language shows from another language. Save and build it again.',
	'[[/_admin/search/hint/probe]]'						=> 'Searches exactly as the page would - with the index that is on the disk now, in the language you are working in. Whoever changes the fields sees the order tip here, without building a page for it.',
	'[[/_admin/search/hint/probe-limit]]'			=> 'The best %d hits.',

	'[[/_admin/search/label/type]]'					=> 'Type',
	'[[/_admin/search/label/fields]]'				=> 'Indexed fields',
	'[[/_admin/search/label/index]]'					=> 'Search index',
	'[[/_admin/search/label/elements]]'			=> 'Elements',
	'[[/_admin/search/label/state]]'					=> 'State',
	'[[/_admin/search/label/edit]]'					=> 'Edit',
	'[[/_admin/search/label/rebuild]]'				=> 'Rebuild',
	'[[/_admin/search/label/rebuild-all]]'		=> 'Rebuild all',
	'[[/_admin/search/label/search]]'				=> 'Search types',
	'[[/_admin/search/label/search-hits]]'		=> 'Search hits',
	'[[/_admin/search/label/uri]]'						=> 'Address of the element',
	'[[/_admin/search/label/slot]]'						=> 'Priority %d, counts %s',
	'[[/_admin/search/label/saveandbuild]]'	=> 'Save and build',

	'[[/_admin/search/label/query]]'					=> 'Search term',
	'[[/_admin/search/label/probe-types]]'		=> 'Search types',
	'[[/_admin/search/label/run]]'						=> 'Search',
	'[[/_admin/search/label/hit]]'						=> 'Hit',
	'[[/_admin/search/label/score]]'					=> 'Score',
	'[[/_admin/search/label/coverage]]'			=> 'Coverage',
	'[[/_admin/search/label/matched]]'				=> 'found in',

	'[[/_admin/search/empty/types]]'					=> 'No element types yet. Create one in the Types panel, then there is something to index here.',
	'[[/_admin/search/empty/nomatch]]'				=> 'No types found.',
	'[[/_admin/search/empty/probe]]'					=> 'No hit.',
	'[[/_admin/search/empty/probe-types]]'		=> 'No type is indexed yet. Tick the fields of a type under Types.',

	'[[/_admin/search/state/current]]'				=> 'current',
	'[[/_admin/search/state/stale]]'					=> 'stale',
	'[[/_admin/search/state/missing]]'				=> 'not built',
	'[[/_admin/search/state/off]]'						=> 'not indexed',
	'[[/_admin/search/state/broken]]'					=> 'broken',
	'[[/_admin/search/state/built]]'					=> 'built: %s',

	'[[/_admin/search/msg/none]]'						=> 'No type is configured for indexing.',
	'[[/_admin/search/msg/created]]'					=> '%d search index created for %n elements.',
	'[[/_admin/search/msg/created-plural]]'	=> '%d search indexes created for %n elements.',
	'[[/_admin/search/msg/removed]]'					=> 'Saved. The type is not indexed any more, and its index was removed.',
	'[[/_admin/search/msg/rebuild]]'					=> 'Saved. Until the index is rebuilt only the fields that the old and the new choice share are searched.',

	// What is wrong with the configuration in config.php, in the words of the interface; the script fills the %s
	'[[/_admin/search/issue/name]]'					=> 'That is not the name of an element type.',
	'[[/_admin/search/issue/list]]'					=> 'The entry is not a list of fields.',
	'[[/_admin/search/issue/notype]]'					=> 'There is no element type "%s".',
	'[[/_admin/search/issue/priority]]'				=> 'Priority "%s" does not exist - the four places are 0, 1, 2 and 3.',
	'[[/_admin/search/issue/noname]]'					=> 'Priority %s does not name a field.',
	'[[/_admin/search/issue/nofield]]'				=> 'The model of "%s" has no field "%s".',
	'[[/_admin/search/issue/none]]'					=> 'No field is named.',

	'[[/_admin/search/error/create]]'				=> 'The search indexes could not be created.',
	'[[/_admin/search/error/save]]'					=> 'The configuration could not be saved.',
	'[[/_admin/search/error/probe]]'					=> 'The search could not be run.',

	// The sentences for the codes Admin::apiSave() names
	'[[/_admin/error/search_type]]'					=> 'That is not the name of an element type.',
	'[[/_admin/error/search_notype]]'				=> 'There is no element type "%s".',
	'[[/_admin/error/search_fields]]'				=> 'The priorities were not sent as field names.',
	'[[/_admin/error/search_priority]]'			=> 'Priority %s does not exist - the four places are 0, 1, 2 and 3.',
	'[[/_admin/error/search_field]]'					=> 'The model of "%s" has no field "%s".',
	'[[/_admin/error/search_text]]'					=> 'The field "%s" holds no text that could be searched.',
];
