<?php
// The class is not declared: features/Hello/ can only serve \Nino\Modules\Hello
return [
	'key'					=> 'hello',
	'name'				=> [ 'en_US' => 'Hello World', 'de_DE' => 'Hallo Welt' ],
	'description'	=> [
		'en_US' => 'Hello World lets you greet a name that its panel stores. It is the example a new feature takes its shape from.',
		'de_DE' => 'Hallo Welt ermöglicht es, einen Namen zu grüßen, den sein Panel speichert. Es ist das Beispiel, an dem sich ein neues Feature in der Form orientiert.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[hello name="Ada" shout]' => [
				'en_US' => 'Greets Ada, in capitals.',
				'de_DE' => 'Grüßt Ada, in Großbuchstaben.',
			],
		],
		'markup' => [],
		'routes' => [
			'/hello' => [
				'en_US' => 'The page of the greeting.',
				'de_DE' => 'Die Seite des Grußes.',
			],
		],
		'panel' => [
			'Hello World' => [
				'en_US' => 'Sets who the shortcode greets.',
				'de_DE' => 'Legt fest, wen der Shortcode grüßt.',
			],
		],
		'callbacks' => [],
		'install' => [
			'templates/page-hello.tpl' => [
				'en_US' => 'The template the page /hello renders.',
				'de_DE' => 'Das Template der Seite /hello.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung mit.',
			],
		],
	],
	'category'		=> 'system',				// one of \Nino\Features::CATEGORIES
	'maturity'		=> 'Example',				// the badge beside the name, 24 characters at most
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',					// the kernel whose vocabulary it uses
	'data'				=> [ '/data/hello.php' ],					// what a backup carries
	'settings'		=> [
		'greeting'	=> [ 'type' => 'string', 'label' => 'Greeting', 'default' => 'Hello', 'maxlength' => 24 ],
		'longest'		=> [ 'type' => 'int', 'label' => 'Longest name', 'min' => 1, 'max' => 60, 'unit' => 'characters', 'default' => 40 ],
	],
];
