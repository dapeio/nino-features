<?php
// features/Vcard/feature.php - what the Features panel reads. The class is not
// declared here: features/Vcard/ can only ever serve \Nino\Modules\Vcard.
return [
	'key'					=> 'vcard',
	'name'				=> [ 'en_US' => 'vCard', 'de_DE' => 'vCard' ],
	'description'	=> [
		'en_US' => 'vCard turns the website into one quiet contact page: picture, name, a line about you, the ways to reach you and a button that saves you into the address book. Everything is set in one place, the panel vCard, and what the project already knows from its setup is filled in for you.',
		'de_DE' => 'vCard macht die Website zu einer einzigen ruhigen Kontaktseite: Bild, Name, eine Zeile über Dich, die Wege, Dich zu erreichen, und ein Knopf, der Dich ins Adressbuch speichert. Alles wird an einer Stelle eingerichtet, im Panel vCard, und was das Projekt aus seiner Einrichtung schon weiß, ist für Dich eingetragen.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[vcard]' => [
				'en_US' => 'Draws the contact card from the panel; with the flag foot it is the whole page and carries the copyright line and the language picker.',
				'de_DE' => 'Zeichnet die Kontaktkarte aus dem Panel; mit dem Flag foot ist sie die ganze Seite und trägt Copyright-Zeile und Sprachauswahl.',
			],
		],
		'markup' => [
			'.nino-vcard' => [
				'en_US' => 'The card and its parts: picture, name, line, text, list of contact ways, list of links, button and the links to the legal pages, which open in a new tab.',
				'de_DE' => 'Die Karte und ihre Teile: Bild, Name, Zeile, Text, Liste der Kontaktwege, Liste der Links, Knopf und die Links zu den Rechtstexten, die in einem neuen Tab öffnen.',
			],
			'.nino-vcard-wrap' => [
				'en_US' => 'The box around the card and, on a page that is the card alone, its foot.',
				'de_DE' => 'Die Box um die Karte und, auf einer Seite, die nur die Karte ist, ihren Fuß.',
			],
			'.nino-vcard-foot' => [
				'en_US' => 'The foot of a page that is the card alone: the language picker of the website where there are several languages, and the copyright line.',
				'de_DE' => 'Der Fuß einer Seite, die nur die Karte ist: die Sprachauswahl der Website, wo es mehrere Sprachen gibt, und die Copyright-Zeile.',
			],
			'data-vcard-design' => [
				'en_US' => 'The design the card wears, klar, karte or foto, with data-vcard-mode where the owner fixed light or dark, and the class nino-vcard--page on a card that is the page alone.',
				'de_DE' => 'Das Design der Karte, klar, karte oder foto, mit data-vcard-mode, wo der Besitzer hell oder dunkel festgelegt hat, und der Klasse nino-vcard--page an einer Karte, die die ganze Seite ist.',
			],
			'data-vcard-address' => [
				'en_US' => 'Under a design, a link with no name of its own shows its address as text, which is set without capitals and may break at its marks.',
				'de_DE' => 'Unter einem Design zeigt ein Link ohne eigenen Namen seine Adresse als Text, der ohne Versalien gesetzt ist und an seinen Zeichen umbrechen darf.',
			],
			'.nino-vcard-scheme' => [
				'en_US' => 'The switch in the foot, where the owner offers it: three buttons for light, auto and dark whose choice stays in the browser of the visitor, under the key of the Modeswitch.',
				'de_DE' => 'Der Schalter im Fuß, wo der Besitzer ihn anbietet: drei Knöpfe für hell, auto und dunkel, deren Wahl im Browser des Besuchers bleibt, unter dem Schlüssel des Hell/Dunkel-Schalters.',
			],
			'href="tel:" and "mailto:"' => [
				'en_US' => 'The phone number and the e-mail address are links that open the phone or the mail program; a link of your own may be http, https, mailto or tel and nothing else.',
				'de_DE' => 'Telefonnummer und E-Mail-Adresse sind Links, die Telefon oder Mailprogramm öffnen; ein eigener Link darf http, https, mailto oder tel sein und nichts sonst.',
			],
			'script type="application/ld+json"' => [
				'en_US' => 'The same details as schema.org data, a Person or, without a name of your own, an Organization.',
				'de_DE' => 'Dieselben Angaben als schema.org-Daten, eine Person oder, ohne eigenen Namen, eine Organisation.',
			],
		],
		'routes' => [
			'/' => [
				'en_US' => 'The start page, when the project has none yet; a project that has one keeps it until the panel button Use as start page moves it.',
				'de_DE' => 'Die Startseite, wenn das Projekt noch keine hat; hat es eine, bleibt sie, bis der Knopf Als Startseite verwenden im Panel sie verschiebt.',
			],
			'/ after the feature is switched off' => [
				'en_US' => 'Where the card is the start page, / shows the unfilled shortcode, so give / to another page in the Routes panel first.',
				'de_DE' => 'Wo die Karte die Startseite ist, zeigt / den nicht ausgefüllten Shortcode, gib / also vorher im Panel Routen einer anderen Seite.',
			],
			'/vcard' => [
				'en_US' => 'The page of the card, wherever it is not already the start page or put somewhere else in the Routes panel.',
				'de_DE' => 'Die Seite der Karte, soweit sie nicht schon die Startseite ist oder im Panel Routen woandershin gelegt wurde.',
			],
			'/vcard.vcf' => [
				'en_US' => 'The contact as a file in the vCard 3.0 format, without a photo, that the button Save contact downloads.',
				'de_DE' => 'Der Kontakt als Datei im Format vCard 3.0, ohne Foto, die der Knopf Kontakt speichern lädt.',
			],
		],
		'panel' => [
			'vCard' => [
				'en_US' => 'All details of the card from top to bottom, a switch for each block, the design with its colours, the logo of the website, the button Use as start page and the way to the imprint and privacy policy.',
				'de_DE' => 'Alle Angaben der Karte von oben nach unten, ein Schalter je Baustein, das Design mit seinen Farben, das Logo der Website, der Knopf Als Startseite verwenden und der Weg zu Impressum und Datenschutz.',
			],
		],
		'callbacks' => [
			'/nino/http/response/GET://vcard.vcf' => [
				'en_US' => 'Answers the file itself, with the headers of a download, after the page engine has had its turn.',
				'de_DE' => 'Antwortet mit der Datei selbst, mit den Headern eines Downloads, nachdem die Seiten-Engine an der Reihe war.',
			],
		],
		'install' => [
			'templates/page-vcard.tpl' => [
				'en_US' => 'The page /vcard renders: a document of its own with the head, the scripts and the styles of every page, but without the header and the footer of the theme, around [vcard foot].',
				'de_DE' => 'Das Template der Seite /vcard: ein eigenes Dokument mit Kopf, Skripten und Stilen jeder Seite, aber ohne Header und Footer des Themes, um [vcard foot].',
			],
			'text/<locale>.php' => [
				'en_US' => 'The words of the card, such as Phone and Save contact, and the title of the page, only added, never replaced; a save in the panel adds those a project lacks.',
				'de_DE' => 'Die Worte der Karte, etwa Telefon und Kontakt speichern, und der Titel der Seite, nur hinzugefügt, nie ersetzt; ein Speichern im Panel ergänzt, was einem Projekt fehlt.',
			],
			'routes: GET://' => [
				'en_US' => 'The start page points at the card, added only where the project has no route for / yet.',
				'de_DE' => 'Die Startseite zeigt auf die Karte, nur hinzugefügt, wo das Projekt noch keine Route für / hat.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '0.1.0',
	'nino'				=> '^1.9',
	'requires'		=> [],
	// The details of the card: one file, which a backup carries
	'data'				=> [ '/data/vcard.php' ],
	// Into the site's style bundle, behind the kernel's own files; the sheet of the design the card wears and the script of the
	// switch for the visitor are added by init() while the card needs them
	'assets'			=> [ '/features/Vcard/assets/vcard.css' ],
	// Nothing to set in the Features panel: every detail has its place in the panel vCard
	'settings'		=> [],
];
