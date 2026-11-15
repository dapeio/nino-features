<?php
// features/Posts/feature.php - what the Features panel reads. The class is
// not declared here: features/Posts/ can only ever serve \Nino\Modules\Posts.
return [
	'key'					=> 'posts',
	'name'				=> [ 'en_US' => 'Posts', 'de_DE' => 'Beiträge' ],
	'description'	=> [
		'en_US' => 'Posts lets you turn an element type into a blog, a news section or a journal, with a page per element and a list with paging. The posts stay ordinary elements.',
		'de_DE' => 'Beiträge ermöglichen eine Seite je Element und eine Liste mit Seitenzahlen. So wird aus einem Elementtyp ein Blog, eine News-Rubrik oder ein Journal, und die Beiträge bleiben gewöhnliche Elemente.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[posts]' => [
				'en_US' => 'Draws the page of the list that is on; [posts-pager] leads to the next page and [post-nav] to the next and the previous post.',
				'de_DE' => 'Zeichnet die Seite der Liste, die gerade dran ist; [posts-pager] führt zur nächsten Seite und [post-nav] zum nächsten und vorherigen Beitrag.',
			],
			'[post]' => [
				'en_US' => 'Draws the record the current url is for.',
				'de_DE' => 'Zeichnet den Datensatz, für den die aktuelle Adresse steht.',
			],
			'[posts section="news" limit="3"]' => [
				'en_US' => 'Takes a second section by its key and the newest three without paging, as a teaser; class="my-class" on [posts-pager] adds a class of your own.',
				'de_DE' => 'Nimmt einen zweiten Bereich über seinen Schlüssel und die neuesten drei ohne Blättern, als Teaser; class="my-class" an [posts-pager] hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'[[.url]], [[.image]], [[.body]], [[.id]], [[.rel]]' => [
				'en_US' => 'The fills inside [posts], [post], [posts-pager] and [post-nav] that an element cannot know itself; every other field is Elements\' own [[title]].',
				'de_DE' => 'Die Fills in [posts], [post], [posts-pager] und [post-nav], die ein Element nicht selbst kennt; jedes andere Feld ist das [[title]] der Elemente.',
			],
			'[[.lang]]' => [
				'en_US' => 'The lang attribute of the language the words came from, with the space in front; put it on the element that shows the text.',
				'de_DE' => 'Das lang-Attribut der Sprache, aus der die Worte kamen, mit dem Leerzeichen davor; es kommt an das Element, das den Text zeigt.',
			],
		],
		'routes' => [
			'/blog' => [
				'en_US' => 'The list, a page at a time; the path is the section\'s.',
				'de_DE' => 'Die Liste, seitenweise; der Pfad gehört dem Bereich.',
			],
			'/blog/my-first-post' => [
				'en_US' => 'One record at a readable url; its title and summary are the title and the description of the page, as text.',
				'de_DE' => 'Ein Datensatz unter einer lesbaren Adresse; Titel und Zusammenfassung sind Titel und Beschreibung der Seite, als Text.',
			],
		],
		'panel' => [],
		'callbacks' => [
			'/seo/pages' => [
				'en_US' => 'Answered, so the SEO feature\'s sitemap.xml and llms.txt carry the section and every post in it.',
				'de_DE' => 'Wird beantwortet, damit sitemap.xml und llms.txt des Features SEO den Bereich und jeden Beitrag darin führen.',
			],
			'/search/hits' => [
				'en_US' => 'Answered, so the search of the Elements search feature does not find a post that is not published yet.',
				'de_DE' => 'Wird beantwortet, damit die Suche des Features Elemente-Suche keinen Beitrag findet, der noch nicht veröffentlicht ist.',
			],
		],
		'install' => [
			'elements/posts.php' => [
				'en_US' => 'An element type to start from, if the project has none.',
				'de_DE' => 'Ein Elementtyp zum Anfangen, falls das Projekt keinen hat.',
			],
			'templates/page-posts.tpl' => [
				'en_US' => 'The list page and, as templates/page-post.tpl, the page of one post; an installed template is not written again.',
				'de_DE' => 'Die Listenseite und, als templates/page-post.tpl, die Seite eines Beitrags; ein installiertes Template wird nicht noch einmal geschrieben.',
			],
			'data/posts.php' => [
				'en_US' => 'Not installed: you write the sections (type, path, index, post, sort, perPage) here by hand, as Posts has no panel; without the file there is /posts at /blog.',
				'de_DE' => 'Wird nicht installiert: Die Bereiche (Typ, Pfad, index, post, Sortierung, perPage) schreibst Du hier von Hand, denn Posts hat kein Panel; ohne die Datei gibt es /posts unter /blog.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the two templates and the name, title and description of the default section\'s page into the Text panel.',
				'de_DE' => 'Bringt die Worte der beiden Templates und Name, Titel und Beschreibung der Seite des Standard-Bereichs ins Panel Texte.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.3.2',
	/*	1.9, because the class reads an element with its fallback
		(getElementWithFallback()) and calls the toolset (\Nino\Feature::template()
		and shortcode()) - and with it everything the floors before it named: the
		words the unit delivers and the page details a post sets are Nino 1.4's
		text keys (/template/..., /_nino/webpage<uri>/...), and the cards of
		page-posts.tpl have the classes of nino-card. */
	'nino'				=> '^1.9',
	'requires'		=> [],
	// Which element type is a section of the site, under which path, through
	// which templates. Not the posts - those are ordinary elements and live in
	// /elements/, where a backup finds them on their own
	'data'				=> [ '/data/posts.php' ],
	'settings'		=> [],
	/*	Only the pager as the Builder offers it. [posts], [post] and [post-nav] are written
		around the markup of one record, with [[field]] for what it holds, and the Builder
		can neither carry that markup nor edit it as it is: it reads a [[title]] as a call of
		the title component and its editor for a body would flatten the record. They stay
		shortcodes, like [consent]	*/
	'components'	=> [
		'posts-pager' => [
			'label'			=> [ 'en_US' => 'Pager', 'de_DE' => 'Blättern' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'section' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Section', 'de_DE' => 'Bereich' ],
					'hint'		=> [
						'en_US' => 'The key of a section in /data/posts.php, such as news. Empty takes the section of the page, else the first one.',
						'de_DE' => 'Der Schlüssel eines Bereichs aus /data/posts.php, etwa news. Leer nimmt den Bereich der Seite, sonst den ersten.',
					],
				],
				'prev' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Word for the newer page', 'de_DE' => 'Wort für die neuere Seite' ],
					'hint'		=> [
						'en_US' => 'Empty takes the words of the Text panel.',
						'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
				'next' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Word for the older page', 'de_DE' => 'Wort für die ältere Seite' ],
					'hint'		=> [
						'en_US' => 'Empty takes the words of the Text panel.',
						'de_DE' => 'Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
				'label' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Name of the navigation', 'de_DE' => 'Name der Navigation' ],
					'hint'		=> [
						'en_US' => 'What a screen reader calls it. Empty takes the words of the Text panel.',
						'de_DE' => 'Wie ein Screenreader sie nennt. Leer nimmt die Wörter aus dem Panel Texte.',
					],
				],
			],
			'preview'		=> 'block',
			'draws'			=> 'nav',
			'in'			=> [ 'section' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M8 3 4 7l4 4' ] ],
				[ 'path', [ 'd' => 'M4 7h16' ] ],
				[ 'path', [ 'd' => 'm16 21 4-4-4-4' ] ],
				[ 'path', [ 'd' => 'M20 17H4' ] ],
			],
		],
	],
];
