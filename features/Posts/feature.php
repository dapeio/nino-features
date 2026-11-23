<?php
// features/Posts/feature.php - what the Features panel reads. The class is
// not declared here: features/Posts/ can only ever serve \Nino\Modules\Posts.
return [
	'key'					=> 'posts',
	'name'				=> [ 'en_US' => 'Posts', 'de_DE' => 'Beiträge' ],
	'description'	=> [
		'en_US' => 'Posts lets you turn an element type into a blog, a news section or a journal, with a page per element and a list with paging. The list, the post, the pager and the way to the next post are components, and two blocks bring them. The posts stay ordinary elements.',
		'de_DE' => 'Beiträge ermöglichen eine Seite je Element und eine Liste mit Seitenzahlen. So wird aus einem Elementtyp ein Blog, eine News-Rubrik oder ein Journal. Liste, Beitrag, Blättern und der Weg zum nächsten Beitrag sind Komponenten, und zwei Blöcke bringen sie mit. Die Beiträge bleiben gewöhnliche Elemente.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[posts section="news" limit="3"]…[/posts]' => [
				'en_US' => 'Draws what it holds once for each post of the page of the list that is on, or with limit the newest posts without paging.',
				'de_DE' => 'Zeichnet, was zwischen den Marken steht, für jeden Beitrag der Seite der Liste, die gerade dran ist, oder mit limit die neuesten ohne Blättern.',
			],
			'[posts-entry]…[/posts-entry]' => [
				'en_US' => 'Draws what it holds once, for the post the address is for, and nothing where no post is meant.',
				'de_DE' => 'Zeichnet, was zwischen den Marken steht, einmal für den Beitrag, für den die Adresse steht, und nichts, wo kein Beitrag gemeint ist.',
			],
			'[posts-pager section="news"]' => [
				'en_US' => 'The numbers of the pages of the list and the way to the newer and the older page, on another page of the site as links to that page; prev, next and label take words of your own.',
				'de_DE' => 'Die Nummern der Seiten der Liste und der Weg zur neueren und zur älteren Seite, auf einer anderen Seite als Links auf diese Seite; prev, next und label nehmen eigene Wörter.',
			],
			'[posts-nav]' => [
				'en_US' => 'The way to the post before and the post after the one that is read, in the order of its section.',
				'de_DE' => 'Der Weg zum vorherigen und zum nächsten Beitrag des Beitrags, der gelesen wird, in der Reihenfolge seiner Rubrik.',
			],
		],
		'markup' => [
			'[section-title .title href=".url"] [text .summary] [image .image] [text .body]' => [
				'en_US' => 'Inside posts and posts-entry a component reads .title, .summary, .date, .image, .body and .url, the address made of the section\'s path and the element\'s name.',
				'de_DE' => 'In posts und posts-entry liest eine Komponente .title, .summary, .date, .image, .body und .url, die Adresse aus Pfad der Rubrik und Name des Elements.',
			],
			'[posts /_placeholder] [text .text] [section-title .title href=".link"]' => [
				'en_US' => 'Inside a block the names of the example elements work: .text is the summary of a list and the body of a post, .link the address.',
				'de_DE' => 'In einem Block gelten die Namen der Beispielelemente: .text ist die Zusammenfassung in einer Liste und der Text in einem Beitrag, .link die Adresse.',
			],
		],
		'routes' => [
			'/blog' => [
				'en_US' => 'The list of the section\'s path, a page at a time as /blog?page=2, and a 404 for a page that is none.',
				'de_DE' => 'Die Liste unter dem Pfad der Rubrik, seitenweise als /blog?page=2, und ein 404 für eine Seite, die es nicht gibt.',
			],
			'/blog/my-first-post' => [
				'en_US' => 'One post at a readable url, its title and summary the title and description of the page, and a 404 for one hidden or dated in the future.',
				'de_DE' => 'Ein Beitrag unter einer lesbaren Adresse, Titel und Zusammenfassung als Titel und Beschreibung der Seite, und ein 404 für einen verborgenen oder künftig datierten.',
			],
		],
		'panel' => [],
		'callbacks' => [
			'/seo/pages' => [
				'en_US' => 'Answered, so the sitemap.xml and llms.txt of the SEO feature carry the section and every published post in it.',
				'de_DE' => 'Wird beantwortet, damit sitemap.xml und llms.txt des Features SEO den Bereich und jeden veröffentlichten Beitrag darin führen.',
			],
			'/search/hits' => [
				'en_US' => 'Answered, so the search of the Elements search feature does not find a post that is not published.',
				'de_DE' => 'Wird beantwortet, damit die Suche des Features Elemente-Suche keinen Beitrag findet, der nicht veröffentlicht ist.',
			],
			'/search/hit' => [
				'en_US' => 'Answered, so a hit of the json endpoint of the search that is a post carries its url.',
				'de_DE' => 'Wird beantwortet, damit ein Treffer des JSON-Endpoints der Suche, der ein Beitrag ist, seine url trägt.',
			],
		],
		'install' => [
			'elements/posts.php' => [
				'en_US' => 'An element type to start from, if the project has none.',
				'de_DE' => 'Ein Elementtyp zum Anfangen, falls das Projekt keinen hat.',
			],
			'install/manifest.php' => [
				'en_US' => 'Offers the list to the main menu at the first switch-on if the project has one; a renamed section key is another page, which you enter in the Navigations panel.',
				'de_DE' => 'Bietet die Liste beim ersten Einschalten dem Hauptmenü an, wenn das Projekt eines hat; ein umbenannter Rubrik-Schlüssel ist eine andere Seite, die Du im Panel „Navigationen“ einträgst.',
			],
			'templates/page-posts.tpl' => [
				'en_US' => 'The list page and, as templates/page-post.tpl, the page of one post, both made of components; an installed template is not written again.',
				'de_DE' => 'Die Listenseite und, als templates/page-post.tpl, die Seite eines Beitrags, beide aus Komponenten; ein installiertes Template wird nicht noch einmal geschrieben.',
			],
			'data/posts.php' => [
				'en_US' => 'Not installed: whoever writes it sets the path and the type of a section, so a path can take over a page and a type is published; without the file /posts is at /blog.',
				'de_DE' => 'Wird nicht installiert: Wer sie schreibt, bestimmt Pfad und Typ einer Rubrik, ein Pfad kann eine Seite übernehmen und ein Typ wird veröffentlicht; ohne die Datei gibt es /posts unter /blog.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the list page, of the pager and of the navigation, and the name, title and description of the default section\'s two pages into the Text panel.',
				'de_DE' => 'Bringt die Worte der Listenseite, des Blätterns und der Navigation und Name, Titel und Beschreibung der zwei Seiten des Standard-Bereichs ins Panel Texte.',
			],
			'blocks/posts-list/, blocks/posts-entry/' => [
				'en_US' => 'Two blocks: the posts of a section as cards with page numbers, and the post of the address with the way to its neighbours.',
				'de_DE' => 'Zwei Blöcke: die Beiträge einer Rubrik als Karten mit Seitenzahlen und der Beitrag der Adresse mit dem Weg zu seinen Nachbarn.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
	// Which element type is a section of the site, under which path, through
	// which templates. Not the posts - those are ordinary elements and live in
	// /elements/, where a backup finds them on their own
	'data'				=> [ '/data/posts.php' ],
	'settings'		=> [],
	'assets'			=> [],
];
