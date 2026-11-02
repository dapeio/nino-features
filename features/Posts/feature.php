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
			'[posts-pager ... class="my-class"]' => [
				'en_US' => 'A class of your own, set on the pager\'s navigation. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, an der Navigation des Blätterns. Das schreibt der Builder für seine eigenen Klassen.',
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
			'[[.lang]]' => [
				'en_US' => 'A post is the one the language has, with every text it has none of - none, empty or only spaces - taken from the native language, then from the others. [[.lang]] is the lang attribute of the language the words came from, with the space in front - lang="de-DE" - or nothing; put it on the element that shows the text, as the templates do. A field of the type that no language has is nothing, never a leftover.',
				'de_DE' => 'Ein Beitrag ist der Beitrag, den die Sprache hat; jeden Text, den sie nicht hat - keinen, einen leeren oder nur Leerzeichen -, nimmt er aus der Muttersprache und dann aus den anderen. [[.lang]] ist das lang-Attribut der Sprache, aus der die Worte kamen, mit dem Leerzeichen davor - lang="de-DE" - oder nichts; es kommt an das Element, das den Text zeigt, wie in den Templates. Ein Feld des Typs, das keine Sprache hat, ist nichts, nie ein Rest.',
			],
		],
		'routes' => [
			'/blog' => [
				'en_US' => 'The list, a page at a time. The path is the section\'s.',
				'de_DE' => 'Die Liste, seitenweise. Der Pfad gehört der Section.',
			],
			'/blog/my-first-post' => [
				'en_US' => 'One record, at a readable url. Its title and summary are the title and the description of the page - as text, so a tag in them is shown and not run. A post no language has a title or a summary for takes the name and the description of its section\'s page for them, and the company\'s where there are none: never the key of a text nobody wrote.',
				'de_DE' => 'Ein Datensatz, unter einer lesbaren Adresse. Titel und Zusammenfassung sind Titel und Beschreibung der Seite - als Text, ein Tag darin wird angezeigt und nicht ausgeführt. Hat keine Sprache einen Titel oder eine Zusammenfassung für den Beitrag, nimmt die Seite dafür Namen und Beschreibung der Seite seines Bereichs und, wo es die nicht gibt, die der Firma: nie den Schlüssel eines Textes, den niemand geschrieben hat.',
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
				'en_US' => 'The list page; each post is a card in the classes of Nino 1.7 (nino-card), with [[.lang]] on the card and on its title. A project that installed the page earlier renames the classes of its cards and adds [[.lang]] by hand - an installed template is not written again.',
				'de_DE' => 'Die Listenseite; jeder Beitrag ist eine Karte in den Klassen von Nino 1.7 (nino-card), mit [[.lang]] an der Karte und an ihrem Titel. Ein Projekt, das die Seite früher installiert hat, benennt die Klassen seiner Karten um und setzt [[.lang]] von Hand - ein installiertes Template wird nicht noch einmal geschrieben.',
			],
			'templates/page-post.tpl' => [
				'en_US' => 'The page of one post, with [[.lang]] on the article, on its heading and on the link to the post beside it. A project that installed it earlier adds [[.lang]] by hand.',
				'de_DE' => 'Die Seite eines Beitrags, mit [[.lang]] am Artikel, an seiner Überschrift und am Link zum Beitrag daneben. Ein Projekt, das sie früher installiert hat, setzt [[.lang]] von Hand.',
			],
			'data/posts.php' => [
				'en_US' => 'Not installed: the sections (type, path, index, post, sort, perPage) are written by hand here as `[\'sections\' => [\'<key>\' => [...]]]` - Posts has no panel. Without the file there is one section, /posts at /blog. A post is an element of the section\'s type - the installed /posts carries title, summary, date, author, image, imageAlt, body and tags - and a future or empty date keeps it unpublished.',
				'de_DE' => 'Wird nicht installiert: Die Bereiche (Typ, Pfad, index, post, Sortierung, perPage) werden hier von Hand als `[\'sections\' => [\'<key>\' => [...]]]` geschrieben – Posts hat kein Panel. Ohne die Datei gibt es einen Bereich, /posts unter /blog. Ein Beitrag ist ein Element des Typs des Bereichs – der installierte /posts trägt title, summary, date, author, image, imageAlt, body und tags – und ein zukünftiges oder leeres Datum lässt ihn unveröffentlicht.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The eight words the two templates say, into the Text panel - and the name, the title and the description of the page of the default section, /_nino/webpage/blog/..., so that its title is a text and not a key until the Routes panel has been used. A key the project has written already stays as it is; a project that has the feature already writes the three in the Routes panel.',
				'de_DE' => 'Die acht Worte, die die beiden Templates sagen, ins Panel Texte - und Name, Titel und Beschreibung der Seite des Standard-Bereichs, /_nino/webpage/blog/..., damit ihr Titel ein Text ist und kein Schlüssel, bis das Panel Routen benutzt wurde. Ein Schlüssel, den das Projekt schon geschrieben hat, bleibt, wie er ist; ein Projekt, das das Feature schon hat, schreibt die drei im Panel Routen.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.3.0',
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
