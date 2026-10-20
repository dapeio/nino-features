<?php
// features/Posts/install/text/en_US.php - what the two delivered templates
// say, and the details of the page the default section is (its name, its title
// and its description), merged into the project's own text file without
// overwriting a key it already has. /feature/posts/pager/prev and /feature/posts/pager/next are also what the pager reads
// before it falls back to its own words (see Shortcodes::_text())
//
// The page details are the system's keys of the section's Element-URI, which
// is the feature's own choice (Posts::INDEX_URI after Sections::DEFAULT_KEY):
// without them the list page's <title> is the key as it stands, until the
// Routes panel has been used for it. A project that has written them already
// keeps its words; the Routes panel changes them like those of any page
return [
	'[[/_nino/webpage/blog/name]]'			=> 'Blog',
	'[[/_nino/webpage/blog/title]]'			=> 'Blog',
	'[[/_nino/webpage/blog/description]]'	=> 'What we have been writing about.',
	'[[/template/page-posts/intro/title]]'	=> 'Posts',
	'[[/template/page-posts/intro/text]]'	=> 'What we have been writing about.',
	'[[/feature/posts/pager/label]]'		=> 'Pages',
	'[[/feature/posts/pager/prev]]'			=> 'Newer',
	'[[/feature/posts/pager/next]]'			=> 'Older',
	'[[/feature/posts/navigation/label]]'	=> 'More posts',
	'[[/feature/posts/navigation/prev]]'	=> 'Previous post',
	'[[/feature/posts/navigation/next]]'	=> 'Next post',
];
