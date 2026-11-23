<?php
declare(strict_types=1);

/**
 *	Nino features
 *	catalogue.php	What the tests of the catalogue need to know about this feature (tests/declarations.php says the keys)
 */
return [
	// The address is built from a format and the key of the default section
	'pages'   => [
		'/blog'      => [ 'Posts.php', 'INDEX_URI', [ 'Sections/Sections.php', 'DEFAULT_KEY' ] ],
		'/blog/post' => [ 'Posts.php', 'POST_URI', [ 'Sections/Sections.php', 'DEFAULT_KEY' ] ],
	],
];
