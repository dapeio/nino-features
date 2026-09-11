<?php
// features/Design/feature.php - what the Features panel reads. The class is
// not declared here: features/Design/ can only ever serve \Nino\Modules\Design.
return [
	'key'					=> 'design',
	'name'				=> [ 'en_US' => 'Design', 'de_DE' => 'Design' ],
	'description'	=> [
		'en_US' => 'The look, per part of a page rather than per page: a set for headings, surfaces, articles, buttons, forms, lists and blocks, compiled into one stylesheet.',
		'de_DE' => 'Das Aussehen, pro Bauteil einer Seite statt pro Seite: je ein Set für Überschriften, Flächen, Artikel, Buttons, Formulare, Listen und Bausteine, in ein Stylesheet kompiliert.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			The look of a Nino site is one file, assets/theme.css. The setup wizard
			delivers a fixed one; this feature compiles its own over it, out of a
			set per part of a page.

			Seven parts, each with a handful of sets to choose from: ATF, Section,
			Article, Buttons, Forms, Lists & tables, Blocks. A header and a footer
			are chosen the same way and bring their own markup with them. Nothing
			asks you to pick a whole theme - bold headings from one design and
			round buttons from another is the point.

			A finetune knob picks among the three steps a set declares for each of
			its values, globally or for one part on its own.

			The setup lives in data/design.php and travels in every backup.
			Removing the feature leaves the compiled stylesheet working; installing
			it again finds the setup and carries on.
			TXT,
		'de_DE' => <<<'TXT'
			Das Aussehen einer Nino-Seite ist eine Datei, assets/theme.css. Der
			Einrichtungsassistent liefert eine feste aus; dieses Feature kompiliert
			seine eigene darüber, aus je einem Set pro Bauteil einer Seite.

			Sieben Bauteile mit je einer Handvoll Sets zur Auswahl: ATF, Section,
			Article, Buttons, Formulare, Listen & Tabellen, Bausteine. Header und
			Footer werden genauso gewählt und bringen ihr eigenes Markup mit. Ein
			ganzes Theme wählt hier niemand - laute Überschriften aus einem Design
			und runde Buttons aus einem anderen ist der Sinn der Sache.

			Ein Feinregler wählt unter den drei Stufen, die ein Set für jeden seiner
			Werte erklärt - global oder für ein Bauteil allein.

			Das Setup liegt in data/design.php und reist in jedem Backup mit. Wird
			das Feature entfernt, arbeitet das kompilierte Stylesheet weiter; wird es
			erneut installiert, findet es sein Setup und macht dort weiter.
			TXT,
	],
	'category'		=> 'ui',
	'version'			=> '0.1.0',
	/*	1.2 is where the wizard stopped asking about the look and the base unit
		started delivering assets/theme.css as one file. Everything here writes
		over that file, so a kernel that still spread the look over
		style.design.css, style.theme.*.css and two frame stylesheets would be
		compiled for a bundle it does not have. */
	'nino'				=> '^1.2',
	'requires'		=> [],
	// The whole setup: which set per part, the knob positions, the deviations,
	// and the fingerprint of what was last compiled. Small, and the one thing
	// that cannot be derived again if it is lost
	'data'				=> [ '/data/design.php' ],
	'settings'		=> [],
];
