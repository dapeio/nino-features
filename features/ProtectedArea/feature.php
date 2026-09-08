<?php
// features/ProtectedArea/feature.php - what the Features panel reads. The
// directory (and so the class, \Nino\Modules\ProtectedArea) is not named
// "Protected" because that is a reserved php word; the key is "protected"
return [
	'key'					=> 'protected',
	'name'				=> [ 'en_US' => 'Protected', 'de_DE' => 'Geschützt' ],
	'description'	=> [
		'en_US' => 'Puts one or more pages behind one shared password - a members\' area, a client preview, an internal page - without accounts.',
		'de_DE' => 'Stellt eine oder mehrere Seiten hinter ein gemeinsames Passwort - einen Mitgliederbereich, eine Kundenvorschau, eine interne Seite - ganz ohne Benutzerkonten.',
	],
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// The attempt-cap counter this feature owns - what a backup carries
	'data'				=> [ '/data/protected.php' ],
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
