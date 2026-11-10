<?php
// features/Copy/feature.php - what the Features panel reads. The class is not
// declared here: features/Copy/ can only ever serve \Nino\Modules\Copy.
return [
	'key'					=> 'copy',
	'name'				=> [ 'en_US' => 'Copy to Clipboard', 'de_DE' => 'In die Zwischenablage' ],
	'description'	=> [
		'en_US' => 'Copy to Clipboard lets you put a copy button on anything that would otherwise be typed out: a code block, an IBAN, a voucher code. The button says it worked. Where it cannot, the text stays selectable.',
		'de_DE' => 'In die Zwischenablage ermöglicht einen Kopierknopf an allem, was sonst abgetippt wird: ein Codeblock, eine IBAN, ein Gutscheincode. Der Knopf sagt, dass es geklappt hat. Wo er nicht kann, bleibt ein markierbarer Text.',
	],
	'manual'			=> [
		'shortcodes' => [
			'[copy]IBAN DE02 1203 0000 0000 2020 51[/copy]' => [
				'en_US' => 'Shows the text with a button that copies exactly it; label="IBAN" tells readers what the button is for.',
				'de_DE' => 'Zeigt den Text mit einem Knopf, der genau ihn kopiert; label="IBAN" sagt Lesern, wofür der Knopf da ist.',
			],
			'[copy block value="DE02120300000000202051"]DE02 1203 ...[/copy]' => [
				'en_US' => 'A block instead of a line, with the whitespace kept (block="1" works too), and value copies something other than what is shown.',
				'de_DE' => 'Block statt Zeile, mit erhaltenen Leerräumen (block="1" gilt ebenso), und value kopiert etwas anderes als das Gezeigte.',
			],
			'[copy /project/company/bank/iban]' => [
				'en_US' => 'Takes the text from a text key or from text="DE02 ...", which is what the Builder writes; class="my-class" adds a class of your own.',
				'de_DE' => 'Nimmt den Text aus einem Textschlüssel oder aus text="DE02 ...", der Form, die der Builder schreibt; class="my-class" hängt eine eigene Klasse an.',
			],
		],
		'markup' => [
			'class="nino-copy"' => [
				'en_US' => 'This is what the shortcode writes: a span.nino-copy with the text in nino-copy-text and the button nino-copy-btn, which the script shows.',
				'de_DE' => 'Das schreibt der Shortcode: einen span.nino-copy mit dem Text in nino-copy-text und dem Knopf nino-copy-btn, den das Skript einblendet.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/html/shortcode/copy' => [
				'en_US' => 'Runs ahead of the component and hands value on as copied, block as block="1" and the text between the tags as text, as the Builder writes them.',
				'de_DE' => 'Läuft vor der Komponente und gibt value als copied, block als block="1" und den Text zwischen den Tags als text weiter, wie der Builder sie schreibt.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Puts the three words of the button into the Text panel.',
				'de_DE' => 'Bringt die drei Wörter des Knopfs ins Panel Texte.',
			],
		],
	],
	// It adds a control to something already on the page - the Features panel
	// files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.1.2',
	'nino'				=> '^1.8',
	'requires'		=> [],
	'data'				=> [],
	// The button's stylesheet and its script, into the site's two bundles
	'assets'			=> [ '/features/Copy/assets/copy.css', '/features/Copy/assets/copy.js' ],
	// No settings: what is copyable is decided where it is written, one
	// element at a time, which is the only place that knows
	'settings'		=> [],
	/*	The shortcode as the Builder offers it: the text is its source, a key or text="...",
		never a body - the Builder edits a body as html and would write a paragraph into it,
		and the line draws its text escaped. The body of the manual stays a form of the
		shortcode, which the callback of the class hands on as the text. value is a name
		the kernel keeps for itself, so what is copied is called copied here, and the callback
		of the class keeps value= of the manual as it was	*/
	'components'	=> [
		'copy' => [
			'label'			=> [ 'en_US' => 'Copy text', 'de_DE' => 'Text zum Kopieren' ],
			'source'		=> 'text',
			'attributes'	=> [
				'copied' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Text that is copied', 'de_DE' => 'Kopierter Text' ],
					'hint'		=> [
						'en_US' => 'Copy this instead of what is shown - a number grouped for reading, copied without the spaces. Empty copies what is shown.',
						'de_DE' => 'Kopiert dies statt des Gezeigten - eine zum Lesen gruppierte Nummer, ohne die Leerzeichen kopiert. Leer kopiert das Gezeigte.',
					],
				],
				'label' => [
					'type'		=> 'string',
					'default'	=> '',
					'label'		=> [ 'en_US' => 'Name of the button', 'de_DE' => 'Name des Knopfs' ],
					'hint'		=> [
						'en_US' => 'What the button is for, for a reader who cannot see what it stands beside, such as IBAN.',
						'de_DE' => 'Wofür der Knopf da ist, für Leser, die nicht sehen, woneben er steht, etwa IBAN.',
					],
				],
				'block' => [
					'type'		=> 'bool',
					'default'	=> false,
					'label'		=> [ 'en_US' => 'As a block', 'de_DE' => 'Als Block' ],
					'hint'		=> [
						'en_US' => 'A block rather than a line, with the whitespace kept.',
						'de_DE' => 'Ein Block statt einer Zeile, mit erhaltenen Leerräumen.',
					],
				],
			],
			'preview'		=> 'text',
			'draws'			=> 'span .nino-copy',
			'in'			=> [ 'section', 'card' ],
			'icon'			=> [
				[ 'rect', [ 'width' => '14', 'height' => '14', 'x' => '8', 'y' => '8', 'rx' => '2', 'ry' => '2' ] ],
				[ 'path', [ 'd' => 'M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2' ] ],
			],
		],
	],
];
