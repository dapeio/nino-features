<?php
// features/Posts/install/text/en_US.php - what the two delivered templates
// say, merged into the project's own text file without overwriting a key it
// already has. /posts/prev and /posts/next are also what the pager reads
// before it falls back to its own words (see Shortcodes::_text())
return [
	'[[/posts/index/title]]'	=> 'Posts',
	'[[/posts/index/intro]]'	=> 'What we have been writing about.',
	'[[/posts/label]]'				=> 'Pages',
	'[[/posts/prev]]'					=> 'Newer',
	'[[/posts/next]]'					=> 'Older',
	'[[/posts/nav/label]]'		=> 'More posts',
	'[[/posts/nav/prev]]'			=> 'Previous post',
	'[[/posts/nav/next]]'			=> 'Next post',
];
