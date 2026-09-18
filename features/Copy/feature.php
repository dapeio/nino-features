<?php
// features/Copy/feature.php - what the Features panel reads. The class is not
// declared here: features/Copy/ can only ever serve \Nino\Modules\Copy.
return [
	'key'					=> 'copy',
	'name'				=> [ 'en_US' => 'Copy to Clipboard', 'de_DE' => 'In die Zwischenablage' ],
	'description'	=> [
		'en_US' => 'A copy button on anything worth copying by hand - a code block, an IBAN, a voucher code - that says it worked, and stays a piece of selectable text where it cannot.',
		'de_DE' => 'Ein Kopierknopf an allem, was sonst abgetippt wird - ein Codeblock, eine IBAN, ein Gutscheincode -, der sagt, dass es geklappt hat, und dort, wo er nicht kann, ein markierbarer Text bleibt.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[copy]IBAN DE02 1203 0000 0000 2020 51[/copy]' => [
				'en_US' => 'The text, with a button that copies exactly it.',
				'de_DE' => 'Der Text, mit einem Knopf, der genau ihn kopiert.',
			],
			'[copy label="IBAN"]DE02 ...[/copy]' => [
				'en_US' => 'What the button is for, for a reader who cannot see what it stands beside.',
				'de_DE' => 'Wofür der Knopf da ist, für Leser, die nicht sehen, woneben er steht.',
			],
			'[copy block]...[/copy]' => [
				'en_US' => 'A block rather than a line - a <pre>, with the whitespace kept.',
				'de_DE' => 'Ein Block statt einer Zeile - ein <pre>, mit erhaltenen Leerräumen.',
			],
			'[copy value="DE02120300000000202051"]DE02 1203 ...[/copy]' => [
				'en_US' => 'Copy this instead of what is shown - a number grouped for reading, copied without the spaces.',
				'de_DE' => 'Kopiert dies statt des Gezeigten - eine zum Lesen gruppierte Nummer, ohne die Leerzeichen kopiert.',
			],
		],
		'markup' => [],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'The three words the button says, into the Text panel.',
				'de_DE' => 'Die drei Wörter des Knopfs, ins Panel Texte.',
			],
		],
	],
	// It adds a control to something already on the page - the Features panel
	// files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.3',
	'requires'		=> [],
	'data'				=> [],
	// And no settings: what is copyable is decided where it is written, one
	// element at a time, which is the only place that knows
	'settings'		=> [],
];
