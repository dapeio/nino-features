<?php
// features/Modeswitch/feature.php - what the Features panel reads. The class
// is not declared here: features/Modeswitch/ can only ever serve
// \Nino\Modules\Modeswitch.
return [
	'key'					=> 'modeswitch',
	'name'				=> [ 'en_US' => 'Light/Dark Switch', 'de_DE' => 'Hell/Dunkel-Schalter' ],
	'description'	=> [
		'en_US' => 'Light/Dark Switch lets visitors read the site light, dark, or the way their system asks for it. A three-state switch can be put into the header, the footer or any page. It shows only where the brand leaves the mode to the visitor.',
		'de_DE' => 'Der Hell/Dunkel-Schalter ermöglicht es Besuchern, die Seite hell, dunkel oder so zu lesen, wie ihr System es vorgibt. Ein Schalter mit drei Zuständen lässt sich in den Kopfbereich, den Fußbereich oder auf jede Seite setzen. Er erscheint nur, wo die Marke den Modus dem Besucher überlässt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[mode-switch]' => [
				'en_US' => 'Three buttons: light, follow the system, dark; shown only where the brand leaves the mode to the visitor.',
				'de_DE' => 'Drei Knöpfe: hell, dem System folgen, dunkel; nur zu sehen, wo die Marke den Modus dem Besucher überlässt.',
			],
			'[mode-switch icons]' => [
				'en_US' => 'The same without the words, for a crowded header bar.',
				'de_DE' => 'Dasselbe ohne die Wörter, für einen vollen Kopfbereich.',
			],
		],
		'markup' => [
			'--brand-scheme' => [
				'en_US' => 'This is what the switch sets on <html> for light or dark and removes for the system; the brand\'s own value stands where nothing is set.',
				'de_DE' => 'Das setzt der Schalter auf <html> für hell oder dunkel und nimmt es für das System wieder weg; wo nichts gesetzt ist, gilt der Wert der Marke.',
			],
			'localStorage[\'nino-mode\']' => [
				'en_US' => 'This is where the choice is kept, in the reader\'s browser only: no cookie, nothing sent to the server.',
				'de_DE' => 'Hier liegt die Wahl, nur im Browser des Lesers: kein Cookie, nichts geht an den Server.',
			],
		],
		'routes' => [],
		'panel' => [
			'Themes: the part of the group Light/dark' => [
				'en_US' => 'The Themes panel offers the switch as the component mode-switch with a knob for its corners; the mode of the brand there decides whether it shows.',
				'de_DE' => 'Das Panel Themes bietet den Schalter als Komponente mode-switch an, mit einem Regler für seine Ecken; der Modus der Marke dort entscheidet, ob er erscheint.',
			],
		],
		'callbacks' => [
			'/nino/http/output' => [
				'en_US' => 'Puts a few lines of script into the head of every page of the site, so that the choice is on the page before it is painted.',
				'de_DE' => 'Setzt ein paar Zeilen Skript in den Kopf jeder Seite der Website, damit die Wahl auf der Seite steht, bevor sie gezeichnet wird.',
			],
		],
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
	'version'			=> '2.0.0',
	// The kernel whose vocabulary it uses: the register of components with their groups and
	// parts, the token --brand-scheme that the palette turns on, and the output phase
	'nino'				=> '^2.0',
	'requires'		=> [],
	// Nothing under data/: the choice belongs to the reader's browser and is
	// kept there. A site that stored it would be storing a preference about a
	// person, which is a consent question this feature deliberately does not
	// raise
	'data'				=> [],
	// The script, into the site's script bundle; the look is the standard part of the group
	'assets'			=> [ '/features/Modeswitch/assets/modeswitch.js' ],
	// No settings. Which of the three the switch starts on is not the
	// site's decision - it is "whatever this reader chose last, and their
	// system until they choose". The only thing a project can vary is whether
	// the buttons carry their words, and that belongs to the one place the
	// switch is written (see manual: [mode-switch icons])
	'settings'		=> [],
];
