<?php
// The class is not declared: features/Hello/ can only serve \Nino\Modules\Hello
return [
	'key'					=> 'hello',
	'name'				=> [ 'en_US' => 'Hello World', 'de_DE' => 'Hallo Welt' ],
	'description'	=> [ 'en_US' => 'Greets the name its panel stores.', 'de_DE' => 'Grüßt den Namen, den sein Panel speichert.' ],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [ '[hello name="Ada" shout="yes"]' => 'Greets Ada, in capitals.' ], 'markup' => [],
		'routes' => [ '/hello' => 'The page of the greeting.' ], 'panel' => [ 'Hello World' => 'Who the shortcode greets.' ],
		'callbacks' => [], 'install' => [ 'templates/page-hello.tpl' => 'The page /hello renders.', 'elements/privacy.php' => 'Its section of the privacy policy.' ],
	],
	'category'		=> 'system',				// one of \Nino\Features::CATEGORIES
	'maturity'		=> 'Example',				// the badge beside the name, 24 characters at most
	'version'			=> '1.1.0',
	'nino'				=> '^1.8',					// the kernel whose vocabulary it uses
	'data'				=> [ '/data/hello.php' ],					// what a backup carries
	'assets'			=> [ '/features/Hello/assets/hello.css' ],	// into the site's style bundle
	'settings'		=> [
		'greeting'	=> [ 'type' => 'string', 'label' => 'Greeting', 'default' => 'Hello', 'maxlength' => 24 ],
		'longest'		=> [ 'type' => 'int', 'label' => 'Longest name', 'min' => 1, 'max' => 60, 'unit' => 'characters', 'default' => 40 ],
	],
	// A shortcode with a schema; the class has no componentHello(), so doShortcode() renders it
	'components'	=> [ 'hello' => [ 'label' => 'Greeting', 'source' => 'none', 'attributes' => [
		'name'	=> [ 'type' => 'string', 'default' => '', 'label' => 'Name' ],
		'shout'	=> [ 'type' => 'bool', 'default' => false, 'label' => 'Capitals' ],
	] ] ],
];
