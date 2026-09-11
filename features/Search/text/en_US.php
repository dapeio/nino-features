<?php
// The Search feature's own workbench strings, merged into its fills while
// the feature is active (see the panel's text()) - same keys and shape the
// workbench's own text/<locale>.php has. A %s is filled by the script
return [
	'[[/_admin/nav/search]]'								=> 'Search',

	'[[/_admin/search/label/title]]'				=> 'Elements search index',
	'[[/_admin/search/label/tile]]'					=> 'Elements searchable',
	'[[/_admin/search/label/tile-stale]]'		=> 'Searchable (index stale)',
	'[[/_admin/search/hint/intro]]'					=> 'One row per Element type this project has. What is indexed is what can be found - everything else the search will not return, however plainly it is on the page.',

	'[[/_admin/search/label/type]]'					=> 'Type',
	'[[/_admin/search/label/fields]]'				=> 'Indexed fields',
	'[[/_admin/search/label/elements]]'			=> 'Elements',
	'[[/_admin/search/label/state]]'				=> 'State',
	'[[/_admin/search/label/edit]]'					=> 'Edit',
	'[[/_admin/search/label/rebuild]]'			=> 'Rebuild',
	'[[/_admin/search/label/rebuild-all]]'	=> 'Rebuild all',
	'[[/_admin/search/label/probe]]'				=> 'Try it',
	'[[/_admin/search/label/back]]'					=> 'Back to the list',

	'[[/_admin/search/state/current]]'			=> 'current',
	'[[/_admin/search/state/stale]]'				=> 'stale',
	'[[/_admin/search/state/missing]]'			=> 'not built',
	'[[/_admin/search/state/off]]'					=> 'not indexed',
	'[[/_admin/search/state/broken]]'				=> 'broken',
	'[[/_admin/search/state/built]]'				=> 'built %s',

	'[[/_admin/search/label/slot]]'					=> 'Priority %d',
	'[[/_admin/search/label/weight]]'				=> 'weighs %s',
	'[[/_admin/search/label/nofield]]'			=> '— no field —',
	'[[/_admin/search/label/save]]'					=> 'Save',
	'[[/_admin/search/label/saveandbuild]]'	=> 'Save and build',
	'[[/_admin/search/hint/slots]]'					=> 'Four slots, strongest to weakest. The weight decides the order of the hits, not whether a word counts as found: what sits in priority 3 is found exactly as well as priority 0 - just further down.',
	'[[/_admin/search/hint/indexable]]'			=> 'Offered are the model fields that carry text. Images, references and yes/no fields are not on the list - there is nothing in them to search.',
	'[[/_admin/search/hint/empty]]'					=> 'This project has no Element types yet. Add one in the Types panel, then there is something here to index.',
	'[[/_admin/search/hint/nofields]]'			=> 'No field chosen - this type is not indexed, and saving removes an index it already has.',
	'[[/_admin/search/hint/stale]]'					=> 'The index is older than the type, or was built from other fields than the ones here. Rebuild it.',

	'[[/_admin/search/label/query]]'				=> 'Search term',
	'[[/_admin/search/label/locale]]'				=> 'Language',
	'[[/_admin/search/label/run]]'					=> 'Search',
	'[[/_admin/search/label/hit]]'					=> 'Hit',
	'[[/_admin/search/label/score]]'				=> 'Score',
	'[[/_admin/search/label/coverage]]'			=> 'Coverage',
	'[[/_admin/search/label/matched]]'			=> 'found in',
	'[[/_admin/search/hint/probe]]'					=> 'Searches exactly the way a page would, against the index that is on disk right now. Change the priorities and the order changes here, without a page to test it on.',
	'[[/_admin/search/hint/probe-empty]]'		=> 'No hit.',
	'[[/_admin/search/hint/probe-limit]]'		=> 'The best %d hits.',

	'[[/_admin/search/msg/creating]]'				=> 'Creating search indexes …',
	'[[/_admin/search/msg/none]]'						=> 'No type is configured for indexing.',
	'[[/_admin/search/msg/created]]'				=> 'Created %d search index for %n elements.',
	'[[/_admin/search/msg/created-plural]]'	=> 'Created %d search indexes for %n elements.',
	'[[/_admin/search/msg/saved]]'					=> 'Saved. The index is rewritten on the next build, or the next save of an element.',
	'[[/_admin/search/msg/removed]]'				=> 'Saved. The type is no longer indexed, and its index was removed.',
	'[[/_admin/search/error/create]]'				=> 'The search indexes could not be created.',
	'[[/_admin/search/error/save]]'					=> 'The configuration could not be saved.',
];
