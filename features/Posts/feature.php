<?php
// features/Posts/feature.php - what the Features panel reads. The class is
// not declared here: features/Posts/ can only ever serve \Nino\Modules\Posts.
return [
	'key'					=> 'posts',
	'name'				=> [ 'en_US' => 'Posts', 'de_DE' => 'Beiträge' ],
	'description'	=> [
		'en_US' => 'A page per element and a list with paging: what turns an element type into a blog, a news section or a journal - the posts stay ordinary elements.',
		'de_DE' => 'Eine Seite je Element und eine Liste mit Seitenzahlen: was aus einem Elementtyp einen Blog, eine News-Rubrik oder ein Journal macht - die Beiträge bleiben gewöhnliche Elemente.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[posts]' => [
				'en_US' => 'The page of the list that is on.',
				'de_DE' => 'Die Seite der Liste, die gerade dran ist.',
			],
			'[post]' => [
				'en_US' => 'The record the current url is for.',
				'de_DE' => 'Der Datensatz, für den die aktuelle Adresse steht.',
			],
			'[posts-pager]' => [
				'en_US' => 'The way to the next page of the list.',
				'de_DE' => 'Der Weg zur nächsten Seite der Liste.',
			],
			'[post-nav]' => [
				'en_US' => 'The way to the next and the previous post.',
				'de_DE' => 'Der Weg zum nächsten und vorherigen Beitrag.',
			],
		],
		'markup' => [
			'[[.url]]' => [
				'en_US' => 'Inside those four: the one value an element cannot know by itself. Every other field is Elements\' own [[title]].',
				'de_DE' => 'In diesen vieren: der eine Wert, den ein Element nicht selbst kennen kann. Jedes andere Feld ist das [[title]] der Elemente.',
			],
		],
		'routes' => [
			'/blog' => [
				'en_US' => 'The list, a page at a time. The path is the section\'s.',
				'de_DE' => 'Die Liste, seitenweise. Der Pfad gehört der Section.',
			],
			'/blog/my-first-post' => [
				'en_US' => 'One record, at a readable url.',
				'de_DE' => 'Ein Datensatz, unter einer lesbaren Adresse.',
			],
		],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'elements/posts.php' => [
				'en_US' => 'An element type to start from, if the project has none.',
				'de_DE' => 'Ein Elementtyp zum Anfangen, falls das Projekt keinen hat.',
			],
			'templates/page-posts.tpl' => [
				'en_US' => 'The list page.',
				'de_DE' => 'Die Listenseite.',
			],
			'templates/page-post.tpl' => [
				'en_US' => 'The page of one post.',
				'de_DE' => 'Die Seite eines Beitrags.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	/*	1.1 is where \Nino\Features arrived and a feature could bring an install
		unit and a runtime module of its own at all. Nothing here needs more
		than that: wildcard routes, element queries with sort and the runtime
		fills this overrides a page title with are all 1.0 kernel. */
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Which element type is a section of the site, under which path, through
	// which templates. Not the posts - those are ordinary elements and live in
	// /elements/, where a backup finds them on their own
	'data'				=> [ '/data/posts.php' ],
	'settings'		=> [],
];
