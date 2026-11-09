<?php
// features/ProtectedArea/feature.php - what the Features panel reads. The
// directory (and so the class, \Nino\Modules\ProtectedArea) is not named
// "Protected" because that is a reserved php word; the key is "protected"
return [
	'key'					=> 'protected',
	'name'				=> [ 'en_US' => 'Protected area', 'de_DE' => 'Geschützter Bereich' ],
	'description'	=> [
		'en_US' => 'Protected area lets you put one or more pages behind one shared password. It suits a members\' area, a client preview or an internal page, without accounts.',
		'de_DE' => 'Der geschützte Bereich ermöglicht es, eine oder mehrere Seiten hinter ein gemeinsames Passwort zu stellen. Das passt für einen Mitgliederbereich, eine Kundenvorschau oder eine interne Seite, ganz ohne Benutzerkonten.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[protected-logout]' => [
				'en_US' => 'A link that locks the session again, for a protected page; class="my-class" adds a class of your own.',
				'de_DE' => 'Ein Link, der die Sitzung wieder sperrt, für eine geschützte Seite; class="my-class" hängt eine eigene Klasse an.',
			],
			'[protected-error]' => [
				'en_US' => 'What the password form says when the password was wrong.',
				'de_DE' => 'Das, was das Passwortformular sagt, wenn das Passwort falsch war.',
			],
		],
		'markup' => [],
		'routes' => [
			'/.protected' => [
				'en_US' => 'Where the password form posts to.',
				'de_DE' => 'Wohin das Passwortformular sendet.',
			],
			'/.protected/logout' => [
				'en_US' => 'Locks the session again and goes to the front page, a plain GET link, which is what [protected-logout] writes.',
				'de_DE' => 'Sperrt die Sitzung wieder und geht zur Startseite, ein einfacher GET-Link, wie [protected-logout] ihn schreibt.',
			],
		],
		'panel' => [
			'Protected area' => [
				'en_US' => 'Lets you set the password, the protected pages as a list to tick off, and a button that signs everybody out; a new password signs everybody out too.',
				'de_DE' => 'Ermöglicht es, das Passwort einzustellen, die geschützten Seiten als Liste zum Ankreuzen und einen Knopf, der alle abmeldet; ein neues Passwort meldet ebenfalls alle ab.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'Puts the password form in front of every protected page, at every address the page has, so in every language.',
				'de_DE' => 'Stellt das Passwortformular vor jede geschützte Seite, an jeder Adresse, die die Seite hat, also auch in allen Sprachen.',
			],
			'/seo/exclude' => [
				'en_US' => 'Tells the SEO feature, where it is installed, to keep the protected pages out of sitemap.xml and llms.txt.',
				'de_DE' => 'Sagt dem Feature SEO, wo es installiert ist, die geschützten Seiten aus sitemap.xml und llms.txt herauszuhalten.',
			],
			'/nino/admin/action' => [
				'en_US' => 'Signs everybody out when the password was changed through the feature settings instead of the panel.',
				'de_DE' => 'Meldet alle ab, wenn das Passwort über die Feature-Einstellungen statt über das Panel geändert wurde.',
			],
		],
		'install' => [
			'templates/page-protected.tpl' => [
				'en_US' => 'The password form.',
				'de_DE' => 'Das Passwortformular.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the form into the Text panel.',
				'de_DE' => 'Bringt die Worte des Formulars ins Panel Texte.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '1.1.1',
	'nino'				=> '^1.8',
	'requires'		=> [],
	// The attempt-cap counter and the session epoch this feature owns - what a
	// backup carries
	'data'				=> [ '/data/protected.php', '/data/protected-session.php' ],
	'settings'		=> [
		'paths' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Protected paths', 'de_DE' => 'Geschützte Pfade' ],
			'hint'	=> [
				'en_US' => 'One uri prefix per line, eg. /intern - protects that page and everything below it, at every address the page has. Enter the page\'s uri (its Element URI in the Routes panel): a page with a route for each language is then shut in every language, and the sitemap leaves every one of its addresses out. Leave empty to protect nothing.',
				'de_DE' => 'Ein Uri-Präfix pro Zeile, zB /intern - schützt diese Seite und alles darunter, an jeder Adresse, die die Seite hat. Trag die Uri der Seite ein (ihre Element-URI im Panel Routen): Eine Seite mit einer Route je Sprache ist dann in jeder Sprache zu, und die Sitemap lässt jede ihrer Adressen aus. Leer lassen, um nichts zu schützen.',
			],
		],
		'password' => [
			'type'	=> 'secret',
			'label'	=> [ 'en_US' => 'Password', 'de_DE' => 'Passwort' ],
			'hint'	=> [
				'en_US' => 'The one password every visitor uses to unlock the protected paths. Empty means nothing is protected - the feature stays inert until this is set.',
				'de_DE' => 'Das eine Passwort, mit dem jeder Besucher die geschützten Pfade entsperrt. Leer heißt, es wird nichts geschützt - das Feature bleibt wirkungslos, bis dies gesetzt ist.',
			],
		],
		'attempts' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Attempts', 'de_DE' => 'Versuche' ],
			'hint'		=> [
				'en_US' => 'Wrong passwords allowed per visitor and hour before the form refuses for the rest of that hour.',
				'de_DE' => 'Falsche Passwörter, die pro Besucher und Stunde erlaubt sind, bevor das Formular für den Rest der Stunde verweigert.',
			],
			'min'			=> 1,
			'max'			=> 50,
			'unit'		=> 'per-hour',
			'default'	=> 5,
		],
	],
	/*	The one shortcode of the two that is meant for a page: the link that locks the
		area again. [protected-error] belongs into the password form's own markup and
		stays a shortcode	*/
	'components'	=> [
		'protected-logout' => [
			'label'			=> [ 'en_US' => 'Lock again', 'de_DE' => 'Wieder sperren' ],
			'source'		=> 'none',
			'loop'			=> false,
			'preview'		=> 'button',
			'draws'			=> 'a .nino-protected-logout',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'm16 17 5-5-5-5' ] ],
				[ 'path', [ 'd' => 'M21 12H9' ] ],
				[ 'path', [ 'd' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4' ] ],
			],
		],
	],
];
