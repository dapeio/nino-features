<?php
// features/Social/feature.php - what the Features panel reads. The class is not
// declared here: features/Social/ can only ever serve \Nino\Modules\Social.
return [
	'key'					=> 'social',
	'name'				=> [ 'en_US' => 'Social links', 'de_DE' => 'Social-Media-Links' ],
	'description'	=> [
		'en_US' => 'Social links lets you link to the profiles a site keeps elsewhere, each with its icon. The editors keep them as an element type under Elements, and a block or a component draws them where the page asks for them. A link always carries its name for a screen reader, and an address is only drawn if its scheme is one a link may have.',
		'de_DE' => 'Social-Media-Links ermöglichen Links zu den Profilen, die eine Website anderswo führt, jeder mit seinem Icon. Die Redaktion pflegt sie als Elementtyp unter Elemente, und ein Block oder eine Komponente zeichnet sie, wo die Seite danach fragt. Ein Link trägt seinen Namen immer auch für Vorleseprogramme, und eine Adresse wird nur gezeichnet, wenn ihr Schema eines ist, das ein Link haben darf.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[social]' => [
				'en_US' => 'Draws every link that is not hidden as a row of icons in the order of the field Position; only="instagram,youtube" takes only these, exclude="telegram" leaves some out.',
				'de_DE' => 'Zeichnet alle nicht ausgeblendeten Links als Reihe von Icons in der Reihenfolge des Felds Position; only="instagram,youtube" nimmt nur diese, exclude="telegram" lässt welche weg.',
			],
			'[social show="both" size="large" variant="badges"]' => [
				'en_US' => 'show chooses icon, text (the name alone) or both; size is regular, small or large; variant is badges (a round ground behind each icon) or outline (a ring round it), and none is plain.',
				'de_DE' => 'show wählt icon, text (nur der Name) oder both; size ist regular, small oder large; variant ist badges (ein runder Grund hinter jedem Icon) oder outline (ein Ring darum), keine Angabe ist schlicht.',
			],
			'[social-link profile="instagram" show="both"]' => [
				'en_US' => 'One link with icon and name, for running text; profile is the id of an element of the type, and a profile that is none draws nothing.',
				'de_DE' => 'Ein einzelner Link mit Icon und Name, für den Fließtext; profile ist die ID eines Elements des Typs, und ein Profil, das es nicht gibt, zeichnet nichts.',
			],
			'[social-icon name="telegram" label="Telegram"]' => [
				'en_US' => 'The icon alone, one of the 23 of the feature or else the link icon; with a label it is named for a screen reader, without one it is hidden from it.',
				'de_DE' => 'Nur das Icon, eines der 23 des Features oder sonst das Link-Icon; mit label trägt es einen Namen für Vorleseprogramme, ohne ist es dafür verborgen.',
			],
		],
		'markup' => [
			'target="_blank" rel="me noopener"' => [
				'en_US' => 'On every http(s) link and no other; javascript:, data:, an address that begins with two slashes and one with a space are refused and the link is left out.',
				'de_DE' => 'Steht an jedem http(s)-Link und an keinem anderen; javascript:, data:, eine Adresse, die mit zwei Schrägstrichen beginnt, und eine mit Leerzeichen werden abgelehnt, der Link bleibt weg.',
			],
			'href="/contact"' => [
				'en_US' => 'A path of the website is written without the directory and gets the directory the website is served from.',
				'de_DE' => 'Ein Pfad der Website wird ohne das Verzeichnis geschrieben und bekommt das Verzeichnis, in dem die Website liegt.',
			],
			'<span class="social-label">' => [
				'en_US' => 'The name of a link; with show="icon" it stays in the page for a screen reader and is hidden from the eye.',
				'de_DE' => 'Der Name eines Links; bei show="icon" bleibt er für Vorleseprogramme in der Seite und ist dem Auge verborgen.',
			],
			'--social-size, --social-gap' => [
				'en_US' => 'The size of an icon and the space between two links: the knobs of the group, which a theme turns or a project sets in its own css on :root.',
				'de_DE' => 'Die Größe eines Icons und der Abstand zweier Links: die Regler der Gruppe, die ein Theme dreht oder ein Projekt in seinem CSS auf :root setzt.',
			],
		],
		'routes' => [],
		'panel' => [
			'Elements: Social Media' => [
				'en_US' => 'The links are elements of the type Social Media with a name, one of the 23 icons, an address, a position and a switch to hide; the icon is chosen from the list, never typed.',
				'de_DE' => 'Die Links sind Elemente des Typs Social Media mit Name, einem der 23 Icons, Adresse, Position und einem Schalter zum Ausblenden; das Icon wird aus der Liste gewählt, nie getippt.',
			],
		],
		'callbacks' => [],
		'install' => [
			'elements/social.php' => [
				'en_US' => 'The element type Social Media with four links to edit, hide or delete, under Elements.',
				'de_DE' => 'Der Elementtyp Social Media mit vier Links zum Bearbeiten, Ausblenden oder Löschen, unter Elemente.',
			],
			'templates/social-links.tpl' => [
				'en_US' => 'Holds [social], for a template of the project to include.',
				'de_DE' => 'Enthält [social], damit ein Template des Projekts es einbinden kann.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the labels of the type\'s fields into the Elements panel, only added, never replaced.',
				'de_DE' => 'Bringt die Beschriftungen der Typfelder ins Panel Elemente, nur hinzugefügt, nie ersetzt.',
			],
			'icons/*.svg (not installed)' => [
				'en_US' => 'One Lucide icon per option, named after its purpose; replacing icons/telegram.svg changes every Telegram link.',
				'de_DE' => 'Ein Lucide-Icon je Auswahl, benannt nach dem Zweck; wer icons/telegram.svg ersetzt, ändert jeden Telegram-Link.',
			],
			'blocks/social-row/' => [
				'en_US' => 'A block: a headline and the row of links.',
				'de_DE' => 'Ein Block: eine Überschrift und die Reihe der Links.',
			],
		],
	],
	// It brings content an editor maintains, the reasoning Gallery files itself under
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// The links are elements, in /elements/social.php - the project's own type file, which a backup carries like every other
	'data'				=> [],
	'assets'			=> [],
	// Nothing to set: which links, how many and how large is said where they are drawn, and a header and a footer want different answers
	'settings'		=> [],
];
