<?php
// The class is not declared: features/Copy/ can only serve \Nino\Modules\Copy
return [
	'key'					=> 'copy',
	'name'				=> [ 'en_US' => 'Copy to Clipboard', 'de_DE' => 'In die Zwischenablage' ],
	'description'	=> [
		'en_US' => 'Copy to Clipboard puts a copy button beside what is otherwise typed off: a code, an IBAN, a voucher. The button says that it worked, and where it cannot copy the text stays selectable.',
		'de_DE' => 'In die Zwischenablage ermöglicht einen Kopierknopf an allem, was sonst abgetippt wird: ein Code, eine IBAN, ein Gutscheincode. Der Knopf sagt, dass es geklappt hat, und wo er nicht kopieren kann, bleibt der Text markierbar.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[copy ~iban]' => [ 'en_US' => 'The text of a key with a button that copies it.', 'de_DE' => 'Der Text eines Schlüssels mit einem Knopf, der ihn kopiert.' ],
			'[copy text="DE02 1203" copied="DE021203" name="IBAN" block]' => [ 'en_US' => 'A fixed text; copied is what the button copies, name what it is for, block keeps the whitespace.', 'de_DE' => 'Ein fester Text; copied ist, was der Knopf kopiert, name, wofür er da ist, block erhält die Leerräume.' ],
		],
		'markup' => [], 'routes' => [], 'panel' => [], 'callbacks' => [],
		'install' => [
			'text/<locale>.php' => [ 'en_US' => 'The three words of the button.', 'de_DE' => 'Die drei Wörter des Knopfs.' ],
			'blocks/copy-line/' => [ 'en_US' => 'A block: a headline and a line to copy.', 'de_DE' => 'Ein Block: eine Überschrift und eine Zeile zum Kopieren.' ],
		],
	],
	'category'		=> 'ui',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'basis' ],
	'data'				=> [],
	'settings'		=> [],
	'assets'			=> [ '/features/Copy/assets/copy.js' ],
];
