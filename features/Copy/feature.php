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
				'en_US' => 'A block rather than a line, with the whitespace kept - painted as one by copy.css, because everything this writes has to be able to stand inside a paragraph.',
				'de_DE' => 'Ein Block statt einer Zeile, mit erhaltenen Leerräumen - von copy.css als solcher gezeichnet, denn alles, was hier geschrieben wird, muss in einem Absatz stehen können.',
			],
			'[copy value="DE02120300000000202051"]DE02 1203 ...[/copy]' => [
				'en_US' => 'Copy this instead of what is shown - a number grouped for reading, copied without the spaces.',
				'de_DE' => 'Kopiert dies statt des Gezeigten - eine zum Lesen gruppierte Nummer, ohne die Leerzeichen kopiert.',
			],
			'[copy /project/company/bank/iban]' => [
				'en_US' => 'The line of a text key, or of text="DE02 ..." written out - what the Builder writes, as plain text, with block="1" for a block. Nino 1.6 and later.',
				'de_DE' => 'Die Zeile eines Textschlüssels, oder von text="DE02 ..." ausgeschrieben - das schreibt der Builder, als reiner Text, mit block="1" für einen Block. Ab Nino 1.6.',
			],
			'[copy ... class="my-class"]' => [
				'en_US' => 'A class of your own, added to those of the line. What the Builder writes for its custom classes.',
				'de_DE' => 'Eine eigene Klasse, zusätzlich zu denen der Zeile. Das schreibt der Builder für seine eigenen Klassen.',
			],
		],
		'markup' => [],
		'routes' => [],
		'panel' => [],
		'callbacks' => [
			'/nino/html/shortcode/copy' => [
				'en_US' => 'Runs ahead of the component: value= of a call is handed on as the attribute copied, the word block as block="1" and the text between the tags as text=, which the Builder writes. Nino 1.5 has no component and no need for it.',
				'de_DE' => 'Läuft vor der Komponente: value= eines Aufrufs wird als das Attribut copied weitergegeben, das Wort block als block="1" und der Text zwischen den Marken als text=, wie der Builder es schreibt. Nino 1.5 kennt keine Komponente und braucht es nicht.',
			],
		],
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
	/*	The shortcode as the Builder offers it: the text is its source, a key or text="...",
		never a body - the Builder edits a body as html and would write a paragraph into it,
		and the line draws its text escaped. The body of the manual stays a form of the
		shortcode, which the callback of the class hands on as the text. value is a name
		the kernel keeps for itself, so what is copied is called copied here, and the callback
		of the class keeps value= of the manual as it was. Nino 1.5 ignores the key	*/
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
		],
	],
];
