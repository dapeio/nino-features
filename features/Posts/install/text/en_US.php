<?php
// features/Posts/install/text/en_US.php - what the two delivered templates
// say, merged into the project's own text file without overwriting a key it
// already has. /feature/posts/pager/prev and /feature/posts/pager/next are also what the pager reads
// before it falls back to its own words (see Shortcodes::_text())
return [
	'[[/template/page-posts/intro/title]]'	=> 'Posts',
	'[[/template/page-posts/intro/text]]'	=> 'What we have been writing about.',
	'[[/feature/posts/pager/label]]'		=> 'Pages',
	'[[/feature/posts/pager/prev]]'			=> 'Newer',
	'[[/feature/posts/pager/next]]'			=> 'Older',
	'[[/feature/posts/navigation/label]]'	=> 'More posts',
	'[[/feature/posts/navigation/prev]]'	=> 'Previous post',
	'[[/feature/posts/navigation/next]]'	=> 'Next post',
];
