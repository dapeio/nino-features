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
		'en_US' => <<<'TXT'
			Nino has the content half of a blog already: an element type is
			records of one shape, the Elements panel edits them, [elements]
			lists them and a backup carries them. What it has no answer for is
			the other half - every record wants a page of its own at a readable
			url, and the list wants to be more than one page long.

			A section names an element type and a path. From there this
			registers two routes: the list under that path, and a page per
			record under it. /blog and /blog/my-first-post, from
			/elements/posts.php.

			Four shortcodes for the templates: [posts] lists the page that is
			on, [post] is the record the current url is for, [posts-pager] is
			the way to the next page, and [post-nav] the way to the next post.
			Inside all of them the fields are Elements' own [[title]], plus
			[[.url]], which is the one value an element cannot know by itself.

			A post dated in the future is not published, so a post can be
			written today and appear on Monday without anything having to run
			on Monday.

			The posts are not this feature's. Remove it and the pages go, the
			records stay - written where every other element is written,
			searchable and translatable.
			TXT,
		'de_DE' => <<<'TXT'
			Die inhaltliche Hälfte eines Blogs hat Nino längst: Ein Elementtyp
			sind Datensätze einer Form, das Panel Elemente bearbeitet sie,
			[elements] listet sie, und ein Backup nimmt sie mit. Wofür es keine
			Antwort gibt, ist die andere Hälfte - jeder Datensatz will eine
			eigene Seite unter einer lesbaren Adresse, und die Liste will länger
			sein als eine Seite.

			Eine Rubrik nennt einen Elementtyp und einen Pfad. Daraus entstehen
			zwei Routen: die Liste unter diesem Pfad, und eine Seite je
			Datensatz darunter. /blog und /blog/mein-erster-beitrag, aus
			/elements/posts.php.

			Vier Shortcodes für die Templates: [posts] listet die Seite, auf der
			man ist, [post] ist der Datensatz zur aktuellen Adresse,
			[posts-pager] der Weg zur nächsten Seite und [post-nav] der Weg zum
			nächsten Beitrag. In allen vieren sind die Felder die von Elements,
			[[title]], dazu [[.url]] - der eine Wert, den ein Element nicht von
			sich aus kennen kann.

			Ein Beitrag mit einem Datum in der Zukunft ist nicht
			veröffentlicht. So lässt sich heute schreiben, was am Montag
			erscheint, ohne dass am Montag etwas laufen muss.

			Die Beiträge gehören nicht diesem Feature. Wird es entfernt,
			verschwinden die Seiten und die Datensätze bleiben - dort, wo jedes
			andere Element liegt, durchsuchbar und übersetzbar.
			TXT,
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
