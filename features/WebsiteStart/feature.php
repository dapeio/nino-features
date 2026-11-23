<?php
// The class is not declared: features/WebsiteStart/ can only serve \Nino\Modules\WebsiteStart
return [
	'key'					=> 'websitestart',
	'name'				=> [ 'en_US' => 'Website start', 'de_DE' => 'Website-Start' ],
	'description'	=> [
		'en_US' => 'Website start puts the pages a new site starts from into the project, built from the components of Basis: a header and a footer, a start page, a contact page and a page for an address that is not there, with their words in English and German and a picture place for the title area. The application Website and the application Blog use it. Switched on alone it places the whole start of a site where the project has no page yet, and nothing where it has one - a page that only answers with an error status, a 404 page, is none; the pages are the project\'s from then on.',
		'de_DE' => 'Website-Start legt die Seiten, mit denen eine neue Website beginnt, im Projekt an, gebaut aus den Komponenten von Basis: Kopf- und Fußbereich, eine Startseite, eine Kontaktseite und eine Seite für eine Adresse, die es nicht gibt, mit ihren Wörtern auf Deutsch und Englisch und einem Bildplatz für den Titelbereich. Die Anwendungen Website und Blog benutzen es. Einzeln eingeschaltet legt es den ganzen Anfang einer Website an, wo das Projekt noch keine Seite hat, und nichts, wo es eine hat - eine Seite, die nur mit einem Fehlerstatus antwortet, eine 404-Seite, ist keine; die Seiten gehören danach dem Projekt.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [
			'/' => [
				'en_US' => 'The start page, in the main menu and the footer menu.',
				'de_DE' => 'Die Startseite, im Hauptmenü und im Fußmenü.',
			],
			'/contact' => [
				'en_US' => 'The contact page with its form, in the main menu and the footer menu.',
				'de_DE' => 'Die Kontaktseite mit ihrem Formular, im Hauptmenü und im Fußmenü.',
			],
			'/404' => [
				'en_US' => 'The page that answers an address that is not there, with the status 404 and in no menu.',
				'de_DE' => 'Die Seite, die auf eine Adresse antwortet, die es nicht gibt, mit dem Status 404 und in keinem Menü.',
			],
		],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'install/manifest.php' => [
				'en_US' => 'Says whenNoPages: the unit is applied as a whole where the project has no page, and not at all where it has one.',
				'de_DE' => 'Sagt whenNoPages: die Einheit wird ganz angewendet, wo das Projekt keine Seite hat, und gar nicht, wo es eine hat.',
			],
			'templates/frame-header.tpl' => [
				'en_US' => 'The header of every page: the logo or the name, the main menu and a button to the contact page.',
				'de_DE' => 'Der Kopf jeder Seite: das Logo oder der Name, das Hauptmenü und ein Button zur Kontaktseite.',
			],
			'templates/frame-footer.tpl' => [
				'en_US' => 'The footer of every page: the logo, the description of the company, the footer menu and the legal pages.',
				'de_DE' => 'Der Fuß jeder Seite: das Logo, die Beschreibung des Unternehmens, das Fußmenü und die Rechtsseiten.',
			],
			'templates/page-home.tpl' => [
				'en_US' => 'The start page, a title area with a picture and three cards.',
				'de_DE' => 'Die Startseite, ein Titelbereich mit Bild und drei Karten.',
			],
			'templates/page-contact.tpl' => [
				'en_US' => 'The contact page, the address of the company beside the contact form.',
				'de_DE' => 'Die Kontaktseite, die Adresse des Unternehmens neben dem Kontaktformular.',
			],
			'templates/page-404.tpl' => [
				'en_US' => 'The page for an address that is not there, with a button to the start page.',
				'de_DE' => 'Die Seite für eine Adresse, die es nicht gibt, mit einem Button zur Startseite.',
			],
			'images/template/page-home/hero/background.svg' => [
				'en_US' => 'The placeholder picture of the title area, in the picture place of the start page that the Images panel replaces.',
				'de_DE' => 'Das Platzhalter-Bild des Titelbereichs, im Bildplatz der Startseite, den das Panel Bilder ersetzt.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The words of the pages and the name, title and description of each page, in English and German.',
				'de_DE' => 'Die Wörter der Seiten und Name, Titel und Beschreibung jeder Seite, auf Deutsch und Englisch.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
	'data'				=> [],
	'settings'		=> [],
];
