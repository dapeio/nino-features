<?php
// features/Mailer/feature.php - what the Features panel reads. The class is
// not declared here: features/Mailer/ can only ever serve \Nino\Modules\Mailer.
return [
	'key'					=> 'mailer',
	'name'				=> [ 'en_US' => 'Mailer', 'de_DE' => 'Mailer' ],
	'description'	=> [
		'en_US' => 'Delivers every mail Nino sends over SMTP instead of the server\'s mail() - reliable on hosts where mail() is missing or unreliable.',
		'de_DE' => 'Versendet jede von Nino verschickte Mail über SMTP statt über das mail() des Servers - zuverlässig auch dort, wo mail() fehlt oder unzuverlässig ist.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'Mailer' => [
				'en_US' => 'The test mail and the last errors. Send yourself one before relying on it - from then on every mail Nino sends goes over SMTP.',
				'de_DE' => 'Die Testmail und die letzten Fehler. Schick Dir eine, bevor Du Dich darauf verlässt – ab dann geht jede Mail von Nino über SMTP.',
			],
		],
		'callbacks' => [],
		'install' => [
			'elements/privacy.php' => [
				'en_US' => 'Its section of the privacy policy, added to the Legal module\'s type - never replacing a section. Where there is no such module, nothing happens.',
				'de_DE' => 'Sein Abschnitt der Datenschutzerklärung, dem Typ des Moduls Legal hinzugefügt – ohne einen Abschnitt zu ersetzen. Wo es das Modul nicht gibt, passiert nichts.',
			],
		],
	],
	'category'		=> 'system',
	'version'			=> '1.0.0',
	'nino'				=> '^1.4',
	'requires'		=> [],
	// The last five failures, newest first (no password, never): what the
	// panel lists. The newest also lives for the current request alone, under
	// ./mailer/last, for the test button. Beyond that nothing is persisted
	// but the settings the Features panel already carries
	'data'				=> [ '/data/mailer.php' ],
	'settings'		=> [
		'host' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'SMTP host', 'de_DE' => 'SMTP-Host' ],
			'hint'			=> [
				'en_US' => 'The mail server to send through, eg. smtp.example.com. Empty means not configured - mail keeps going out through mail() until this is set.',
				'de_DE' => 'Der Mailserver für den Versand, zB smtp.example.com. Leer heißt nicht konfiguriert - Mail geht weiterhin über mail() hinaus, bis dies gesetzt ist.',
			],
			'maxlength'	=> 253,
			'default'		=> '',
		],
		'port' => [
			'type'			=> 'int',
			'label'			=> [ 'en_US' => 'Port', 'de_DE' => 'Port' ],
			'hint'			=> [
				'en_US' => '0 = from the encryption: 587 STARTTLS, 465 TLS, 25 none; any other number is used as it is.',
				'de_DE' => '0 = aus der Verschlüsselung: 587 STARTTLS, 465 TLS, 25 keine; jede andere Zahl wird so verwendet.',
			],
			'min'				=> 0,
			'max'				=> 65535,
			'default'		=> 0,
		],
		'encryption' => [
			'type'			=> 'select',
			'label'			=> [ 'en_US' => 'Encryption', 'de_DE' => 'Verschlüsselung' ],
			'hint'			=> [
				'en_US' => 'How the connection is secured. Most providers want STARTTLS on port 587.',
				'de_DE' => 'Wie die Verbindung gesichert wird. Die meisten Provider wollen STARTTLS auf Port 587.',
			],
			'options'		=> [
				'starttls'	=> [ 'en_US' => 'STARTTLS (port 587)', 'de_DE' => 'STARTTLS (Port 587)' ],
				'tls'				=> [ 'en_US' => 'TLS from the start (port 465)', 'de_DE' => 'TLS von Anfang an (Port 465)' ],
				'none'			=> [ 'en_US' => 'None (only for a local relay)', 'de_DE' => 'Keine (nur für ein lokales Relay)' ],
			],
			'default'		=> 'starttls',
		],
		'username' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'Username', 'de_DE' => 'Benutzername' ],
			'hint'			=> [
				'en_US' => 'Usually the full mailbox address. Empty skips authentication, for a local relay that needs none.',
				'de_DE' => 'Meist die vollständige Mailbox-Adresse. Leer überspringt die Authentifizierung, für ein lokales Relay ohne Anmeldung.',
			],
			'maxlength'	=> 200,
			'default'		=> '',
		],
		'password' => [
			'type'			=> 'secret',
			'label'			=> [ 'en_US' => 'Password', 'de_DE' => 'Passwort' ],
			'hint'			=> [
				'en_US' => 'Kept only on this server, never shown again once saved.',
				'de_DE' => 'Bleibt nur auf diesem Server, wird nach dem Speichern nicht mehr angezeigt.',
			],
			'maxlength'	=> 200,
		],
		'from' => [
			'type'			=> 'email',
			'label'			=> [ 'en_US' => 'From address', 'de_DE' => 'Absenderadresse' ],
			'hint'			=> [
				'en_US' => 'Used when a mail brings none of its own - has to be an address the provider allows you to send as.',
				'de_DE' => 'Wird verwendet, wenn eine Mail keine eigene mitbringt - muss eine Adresse sein, für die der Provider den Versand erlaubt.',
			],
			'default'		=> '',
		],
		'fromName' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'From name', 'de_DE' => 'Absendername' ],
			'hint'			=> [
				'en_US' => 'The display name beside the From address, eg. the site\'s name.',
				'de_DE' => 'Der Anzeigename neben der Absenderadresse, zB der Name der Website.',
			],
			'maxlength'	=> 100,
			'default'		=> '',
		],
		'timeout' => [
			'type'			=> 'int',
			'label'			=> [ 'en_US' => 'Timeout', 'de_DE' => 'Zeitlimit' ],
			'hint'			=> [
				'en_US' => 'How long to wait for the server before giving up.',
				'de_DE' => 'Wie lange auf den Server gewartet wird, bevor abgebrochen wird.',
			],
			'min'				=> 5,
			'max'				=> 60,
			'unit'			=> 'seconds',
			'default'		=> 15,
		],
		'verify' => [
			'type'			=> 'bool',
			'label'			=> [ 'en_US' => 'Verify the server certificate', 'de_DE' => 'Serverzertifikat prüfen' ],
			'hint'			=> [
				'en_US' => 'Turn off only for a development server with a self-signed certificate.',
				'de_DE' => 'Nur für einen Entwicklungsserver mit selbstsigniertem Zertifikat ausschalten.',
			],
			'default'		=> true,
		],
	],
];
