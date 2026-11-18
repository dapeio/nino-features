<?php
declare(strict_types=1);

// The words of the search field and of the list of hits - taken into the
// project's text/en_US.php at every activation (add-only: a key the project
// has stays, one that is missing is added); from then on editors keep them in
// the Texts panel.
return [

	// The name of the input for a screen reader; on screen the placeholder stands
	'[[/feature/search/form/label]]'	=> 'Search this website',
	'[[/feature/search/form/placeholder]]'	=> 'Search term',
	'[[/feature/search/form/button]]'	=> 'Search',

	// What the list says when something was searched for and nothing fits - and the call has no text of its own
	'[[/feature/search/results/empty]]'	=> 'Nothing found.',

	// The word behind the number of hits, for one hit and for every other number
	'[[/feature/search/count/hit]]'	=> 'hit',
	'[[/feature/search/count/hits]]'	=> 'hits',
];
