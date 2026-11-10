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
			'[hello name="Ada" shout="yes"]' => [
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
	'version'			=> '1.1.2',
	'nino'				=> '^1.8',					// the kernel whose vocabulary it uses
	'data'				=> [ '/data/hello.php' ],					// what a backup carries
	'assets'			=> [ '/features/Hello/assets/hello.css' ],	// into the site's style bundle
	'settings'		=> [
		'greeting'	=> [ 'type' => 'string', 'label' => 'Greeting', 'default' => 'Hello', 'maxlength' => 24 ],
		'longest'		=> [ 'type' => 'int', 'label' => 'Longest name', 'min' => 1, 'max' => 60, 'unit' => 'characters', 'default' => 40 ],
	],
	// A shortcode with a schema; the class has no componentHello(), so doShortcode() renders it
	'components'	=> [ 'hello' => [ 'label' => 'Greeting', 'source' => 'none', 'preview' => 'text', 'draws' => 'p .nino-hello', 'in' => [ 'section', 'card' ],
		'icon' => [
			[ 'path', [ 'd' => 'M18 11V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2' ] ],
			[ 'path', [ 'd' => 'M14 10V4a2 2 0 0 0-2-2a2 2 0 0 0-2 2v2' ] ],
			[ 'path', [ 'd' => 'M10 10.5V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2v8' ] ],
			[ 'path', [ 'd' => 'M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15' ] ],
		],
		'attributes' => [
			'name'	=> [ 'type' => 'string', 'default' => '', 'label' => 'Name' ],
			'shout'	=> [ 'type' => 'bool', 'default' => false, 'label' => 'Capitals' ],
		],
	] ],
];
