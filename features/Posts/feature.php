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
			'[posts section="news" limit="3"]' => [
				'en_US' => 'A second section by its key, and the newest three without paging - a teaser rather than page one of something. A page past the end is an empty list.',
				'de_DE' => 'Ein zweiter Bereich über seinen Schlüssel, und die neuesten drei ohne Blättern – ein Teaser statt Seite eins von etwas. Eine Seite hinter dem Ende ist eine leere Liste.',
			],
		],
		'markup' => [
			'[[.url]]' => [
				'en_US' => 'Inside those four, beside [[.image]], [[.body]] and [[.id]]: what an element cannot know by itself. Every other field is Elements\' own [[title]].',
				'de_DE' => 'In diesen vieren, neben [[.image]], [[.body]] und [[.id]]: was ein Element nicht selbst wissen kann. Jedes andere Feld ist das [[title]] der Elemente.',
			],
			'[[.image]], [[.body]], [[.id]], [[.rel]]' => [
				'en_US' => '[[.image]] is the whole <img> or nothing, [[.body]] the body field as paragraphs (a blank line starts one), [[.id]] the number in the list from 0, [[.rel]] prev or next inside [post-nav].',
				'de_DE' => '[[.image]] ist das ganze <img> oder nichts, [[.body]] das Body-Feld als Absätze (eine Leerzeile beginnt einen), [[.id]] die Nummer in der Liste ab 0, [[.rel]] prev oder next innerhalb von [post-nav].',
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
		'callbacks' => [
			'/seo/pages' => [
				'en_US' => 'Answered, so the SEO feature\'s sitemap.xml and llms.txt carry the section and every post in it - no route names them.',
				'de_DE' => 'Wird beantwortet, damit sitemap.xml und llms.txt des SEO-Features die Section und jeden Beitrag darin führen - keine Route nennt sie.',
			],
		],
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
			'data/posts.php' => [
				'en_US' => 'Not installed: the sections (type, path, index, post, sort, perPage) are written by hand here as `[\'sections\' => [\'<key>\' => [...]]]` - Posts has no panel. Without the file there is one section, /posts at /blog. A post is an element of the section\'s type - the installed /posts carries title, summary, date, author, image, imageAlt, body and tags - and a future or empty date keeps it unpublished.',
				'de_DE' => 'Wird nicht installiert: Die Bereiche (Typ, Pfad, index, post, Sortierung, perPage) werden hier von Hand als `[\'sections\' => [\'<key>\' => [...]]]` geschrieben – Posts hat kein Panel. Ohne die Datei gibt es einen Bereich, /posts unter /blog. Ein Beitrag ist ein Element des Typs des Bereichs – der installierte /posts trägt title, summary, date, author, image, imageAlt, body und tags – und ein zukünftiges oder leeres Datum lässt ihn unveröffentlicht.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The eight words the two templates say, into the Text panel.',
				'de_DE' => 'Die acht Worte, die die beiden Templates sagen, ins Panel Texte.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.1.0',
	/*	1.4, because the words the unit delivers and the page details a post
		sets are Nino 1.4's text keys (/template/..., /_nino/webpage<uri>/...),
		which no earlier Nino has. The sectioned 'manual' above is read from
		1.3.0-beta on - v1.2.0-beta refuses it - and wildcard routes, element
		queries with sort and runtime fills are 1.0 kernel. */
	'nino'				=> '^1.4',
	'requires'		=> [],
	// Which element type is a section of the site, under which path, through
	// which templates. Not the posts - those are ordinary elements and live in
	// /elements/, where a backup finds them on their own
	'data'				=> [ '/data/posts.php' ],
	'settings'		=> [],
];
