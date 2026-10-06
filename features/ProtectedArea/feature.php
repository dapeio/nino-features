<?php
// features/ProtectedArea/feature.php - what the Features panel reads. The
// directory (and so the class, \Nino\Modules\ProtectedArea) is not named
// "Protected" because that is a reserved php word; the key is "protected"
return [
	'key'					=> 'protected',
	'name'				=> [ 'en_US' => 'Protected area', 'de_DE' => 'Geschützter Bereich' ],
	'description'	=> [
		'en_US' => 'Puts one or more pages behind one shared password - a members\' area, a client preview, an internal page - without accounts.',
		'de_DE' => 'Stellt eine oder mehrere Seiten hinter ein gemeinsames Passwort - einen Mitgliederbereich, eine Kundenvorschau, eine interne Seite - ganz ohne Benutzerkonten.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[protected-logout]' => [
				'en_US' => 'A lock-again link, for a protected page.',
				'de_DE' => 'Ein Wieder-sperren-Link, für eine geschützte Seite.',
			],
			'[protected-error]' => [
				'en_US' => 'What the password form says when the password was wrong.',
				'de_DE' => 'Was das Passwortformular sagt, wenn das Passwort falsch war.',
			],
		],
		'markup' => [],
		'routes' => [
			'/.protected' => [
				'en_US' => 'Where the password form posts to.',
				'de_DE' => 'Wohin das Passwortformular sendet.',
			],
		],
		'panel' => [
			'Protected area' => [
				'en_US' => 'The password, the protected pages as a list to tick off, and a button that signs everybody out. A new password signs everybody out too.',
				'de_DE' => 'Das Passwort, die geschützten Seiten als Liste zum Ankreuzen und ein Knopf, der alle abmeldet. Ein neues Passwort meldet ebenfalls alle ab.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'Puts the password form in front of every protected page.',
				'de_DE' => 'Stellt das Passwortformular vor jede geschützte Seite.',
			],
			'/seo/exclude' => [
				'en_US' => 'Tells the SEO feature, where it is installed, to keep the protected pages out of sitemap.xml and llms.txt.',
				'de_DE' => 'Sagt dem Feature SEO, wo es installiert ist, die geschützten Seiten aus sitemap.xml und llms.txt herauszuhalten.',
			],
		],
		'install' => [
			'templates/page-protected.tpl' => [
				'en_US' => 'The password form.',
				'de_DE' => 'Das Passwortformular.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Its words, into the Text panel.',
				'de_DE' => 'Seine Worte, ins Panel Texte.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '1.0.0',
	'nino'				=> '^1.4',
	'requires'		=> [],
	// The attempt-cap counter and the session epoch this feature owns - what a
	// backup carries
	'data'				=> [ '/data/protected.php', '/data/protected-session.php' ],
	'settings'		=> [
		'paths' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Protected paths', 'de_DE' => 'Geschützte Pfade' ],
			'hint'	=> [
				'en_US' => 'One uri prefix per line, eg. /intern - protects that page and everything below it. Leave empty to protect nothing.',
				'de_DE' => 'Ein Uri-Präfix pro Zeile, zB /intern - schützt diese Seite und alles darunter. Leer lassen, um nichts zu schützen.',
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
			'unit'		=> 'per hour',
			'default'	=> 5,
		],
	],
];
