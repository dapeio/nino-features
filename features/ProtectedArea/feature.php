<?php
// The class is not named "Protected": that is a reserved php word, and features/ProtectedArea/ can only serve
// \Nino\Modules\ProtectedArea. The key is "protected"
return [
	'key'					=> 'protected',
	'name'				=> [ 'en_US' => 'Protected area', 'de_DE' => 'Geschützter Bereich' ],
	'description'	=> [
		'en_US' => 'Protected area lets you put one or more pages behind one shared password. It suits a members\' area, a client preview or an internal page, without accounts. The password form is a component and the sign-in page is made of components, so it can be built and styled like any other page.',
		'de_DE' => 'Der geschützte Bereich ermöglicht es, eine oder mehrere Seiten hinter ein gemeinsames Passwort zu stellen. Das passt für einen Mitgliederbereich, eine Kundenvorschau oder eine interne Seite, ganz ohne Benutzerkonten. Das Passwortformular ist eine Komponente und die Anmeldeseite besteht aus Komponenten, sie lässt sich also wie jede andere Seite bauen und gestalten.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[protected-form]' => [
				'en_US' => 'The password form, a field with its error line that posts to /.protected and works without a script.',
				'de_DE' => 'Das Passwortformular, ein Feld mit seiner Fehlerzeile, das an /.protected sendet und ohne Skript funktioniert.',
			],
			'[protected-logout]' => [
				'en_US' => 'A link that locks the session again, drawn only for a visitor who has unlocked.',
				'de_DE' => 'Ein Link, der die Sitzung wieder sperrt, nur für einen Besucher gezeichnet, der entsperrt hat.',
			],
		],
		'markup' => [],
		'routes' => [
			'/.protected' => [
				'en_US' => 'Where the password form posts to; a right password unlocks the session and a wrong one counts as an attempt.',
				'de_DE' => 'Wohin das Passwortformular sendet; ein richtiges Passwort entsperrt die Sitzung, ein falsches zählt als Versuch.',
			],
			'/.protected/logout' => [
				'en_US' => 'Locks the session again and goes to the front page, a plain GET link, which is what [protected-logout] writes.',
				'de_DE' => 'Sperrt die Sitzung wieder und geht zur Startseite, ein einfacher GET-Link, wie [protected-logout] ihn schreibt.',
			],
		],
		'panel' => [
			'Protected area' => [
				'en_US' => 'Lets you write the protected paths as lines, set the password and sign everybody out; a new password signs everybody out too.',
				'de_DE' => 'Ermöglicht es, die geschützten Pfade als Zeilen zu schreiben, das Passwort zu setzen und alle abzumelden; ein neues Passwort meldet ebenfalls alle ab.',
			],
			'Password' => [
				'en_US' => 'The password is kept as a hash, and a plain text password from an older installation opens nothing until it is set again in this panel.',
				'de_DE' => 'Das Passwort wird als Hash gehalten, und ein Klartext-Passwort aus einer älteren Installation öffnet nichts, bis es in diesem Panel neu gesetzt wird.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'Puts the password form in front of every protected page, at every address the page has, so in every language, and keeps the workbench out of its reach.',
				'de_DE' => 'Stellt das Passwortformular vor jede geschützte Seite, an jeder Adresse, die die Seite hat, also auch in allen Sprachen, und lässt die Werkbank außen vor.',
			],
			'/seo/exclude' => [
				'en_US' => 'Tells the SEO feature, where it is installed, to keep the protected pages out of sitemap.xml and llms.txt.',
				'de_DE' => 'Sagt dem Feature SEO, wo es installiert ist, die geschützten Pfade aus sitemap.xml und llms.txt herauszuhalten.',
			],
			'/nino/admin/restore' => [
				'en_US' => 'Gives the backup that is restored a new session epoch, so that a restore signs everybody out.',
				'de_DE' => 'Gibt der Sicherung, die zurückgespielt wird, eine neue Sitzungsepoche, damit ein Zurückspielen alle abmeldet.',
			],
			'/nino/admin/action' => [
				'en_US' => 'Stores a password set in the Features panel as a hash and signs everybody out, as the panel of this feature does.',
				'de_DE' => 'Speichert ein im Panel Features gesetztes Passwort als Hash und meldet alle ab, wie es das Panel dieses Features tut.',
			],
		],
		'install' => [
			'templates/page-protected.tpl' => [
				'en_US' => 'The sign-in page the gate answers with, made of components and the frame of the site.',
				'de_DE' => 'Die Anmeldeseite, mit der das Tor antwortet, aus Komponenten und dem Rahmen der Website.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the form and of the page into the Text panel.',
				'de_DE' => 'Bringt die Worte des Formulars und der Seite ins Panel Texte.',
			],
			'blocks/protected-login/' => [
				'en_US' => 'A block: a headline, a line of text and the password form.',
				'de_DE' => 'Ein Block: eine Überschrift, eine Zeile Text und das Passwortformular.',
			],
			'/data/protected.php' => [
				'en_US' => 'The counter of wrong attempts per client and hour, which a backup carries.',
				'de_DE' => 'Der Zähler der Fehlversuche je Besucher und Stunde, den eine Sicherung mitnimmt.',
			],
			'/data/protected-session.php' => [
				'en_US' => 'The session epoch that signing everybody out replaces, which a backup carries.',
				'de_DE' => 'Die Sitzungsepoche, die das Abmelden aller ersetzt und die eine Sicherung mitnimmt.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'security',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [],
	// The attempt-cap counter and the session epoch this feature owns - what a backup carries
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
				'en_US' => 'The one password every visitor uses to unlock the protected paths, kept as a hash. Empty means nothing is protected - the feature stays inert until this is set.',
				'de_DE' => 'Das eine Passwort, mit dem jeder Besucher die geschützten Pfade entsperrt, als Hash gespeichert. Leer heißt, es wird nichts geschützt - das Feature bleibt wirkungslos, bis dies gesetzt ist.',
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
];
