<?php
// features/Modeswitch/feature.php - what the Features panel reads. The class
// is not declared here: features/Modeswitch/ can only ever serve
// \Nino\Modules\Modeswitch.
return [
	'key'					=> 'modeswitch',
	'name'				=> [ 'en_US' => 'Light/Dark Switch', 'de_DE' => 'Hell/Dunkel-Schalter' ],
	'description'	=> [
		'en_US' => 'Light/Dark Switch lets visitors read the site light, dark, or the way their system asks for it. A three-state switch can be put anywhere with a shortcode. The dark palette is the one the project already ships.',
		'de_DE' => 'Der Hell/Dunkel-Schalter ermöglicht es Besuchern, die Seite hell, dunkel oder so zu lesen, wie ihr System es vorgibt. Ein Schalter mit drei Zuständen lässt sich mit einem Shortcode an jede Stelle setzen. Die dunkle Palette ist die, die das Projekt ohnehin mitbringt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[mode-switch]' => [
				'en_US' => 'Three buttons: light, follow the system, dark.',
				'de_DE' => 'Drei Knöpfe: hell, dem System folgen, dunkel.',
			],
			'[mode-switch icons]' => [
				'en_US' => 'The same without the words, for a crowded header bar; icons="1", true, yes and on work too, and class="my-class" adds a class of your own.',
				'de_DE' => 'Dasselbe ohne die Wörter, für eine volle Kopfzeile; icons="1" und true, yes und on gelten ebenso, und class="my-class" hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'data-nino-mode="dark"' => [
				'en_US' => 'This is what the switch writes on <html>; assets/theme.css answers to it, and no attribute means follow the system.',
				'de_DE' => 'Das schreibt der Schalter auf <html>; assets/theme.css hört darauf, und kein Attribut heißt: dem System folgen.',
			],
			'localStorage[\'nino-mode\']' => [
				'en_US' => 'This is where the choice is kept, in the reader\'s browser only: no cookie, nothing sent to the server.',
				'de_DE' => 'Hier liegt die Wahl, nur im Browser des Lesers: kein Cookie, nichts geht an den Server.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the four words of the switch into the Text panel.',
				'de_DE' => 'Bringt die vier Worte des Schalters ins Panel Texte.',
			],
		],
	],
	// It adds a control to a page that changes how what is already there is
	// read - the Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.1.1',
	// The kernel whose toolset it calls: the template, class and flag helpers,
	// the shortcode registration, the manifest's assets and Nino.events. Every
	// such kernel has the dark half of the palette this switches to
	// (assets/theme.css carries :root[data-nino-mode="dark"] and the matching
	// prefers-color-scheme block)
	'nino'				=> '^1.8',
	'requires'		=> [],
	// Nothing under data/: the choice belongs to the reader's browser and is
	// kept there. A site that stored it would be storing a preference about a
	// person, which is a consent question this feature deliberately does not
	// raise
	'data'				=> [],
	// The switch's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Modeswitch/assets/modeswitch.css', '/features/Modeswitch/assets/modeswitch.js' ],
	// No settings. Which of the three the switch starts on is not the
	// site's decision - it is "whatever this reader chose last, and their
	// system until they choose". The only thing a project can vary is whether
	// the buttons carry their words, and that belongs to the one place the
	// switch is written (see manual: [mode-switch icons])
	'settings'		=> [],
	/*	The shortcode as the Builder offers it: the flag of the manual is the attribute
		icons here, which componentModeSwitch() hands to doShortcode() as the bare word	*/
	'components'	=> [
		'mode-switch' => [
			'label'			=> [ 'en_US' => 'Mode switch', 'de_DE' => 'Modus-Schalter' ],
			'source'		=> 'none',
			'loop'			=> false,
			'attributes'	=> [
				'icons' => [
					'type'		=> 'bool',
					'default'	=> false,
					'label'		=> [ 'en_US' => 'Icons only', 'de_DE' => 'Nur Icons' ],
					'hint'		=> [
						'en_US' => 'Without the words - for a crowded header bar.',
						'de_DE' => 'Ohne die Wörter - für eine volle Kopfzeile.',
					],
				],
			],
			'preview'		=> 'button',
			'draws'			=> 'div .nino-modeswitch',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'path', [ 'd' => 'M12 2v2' ] ],
				[ 'path', [ 'd' => 'M14.837 16.385a6 6 0 1 1-7.223-7.222c.624-.147.97.66.715 1.248a4 4 0 0 0 5.26 5.259c.589-.255 1.396.09 1.248.715' ] ],
				[ 'path', [ 'd' => 'M16 12a4 4 0 0 0-4-4' ] ],
				[ 'path', [ 'd' => 'm19 5-1.256 1.256' ] ],
				[ 'path', [ 'd' => 'M20 12h2' ] ],
			],
		],
	],
];
