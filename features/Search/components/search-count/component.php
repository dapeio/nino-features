<?php
declare(strict_types=1);

return [
	'group'      => 'search',
	'label'      => [ 'de_DE' => 'Zahl der Treffer', 'en_US' => 'Number of hits' ],
	'icon'       => 'search',
	'in'         => [ 'flow' ],
	'source'     => 'none',
	'preview'    => 'text',
	'attributes' => [
		'type' => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Elementtypen', 'en_US' => 'Element types' ],
			'hint' => [ 'de_DE' => 'Ein Elementtyp oder mehrere, durch Komma getrennt, etwa /products. Leer zählt in jedem indizierten Typ.', 'en_US' => 'One element type, or several separated by commas, such as /products. Empty counts in every indexed type.' ] ],
		'key'  => [ 'type' => 'string', 'default' => 'q', 'label' => [ 'de_DE' => 'Query-Variable', 'en_US' => 'Query variable' ],
			'hint' => [ 'de_DE' => 'Der Name des Eingabefelds im Suchfeld. Leer nimmt q.', 'en_US' => 'The name of the input of the search field. Empty takes q.' ] ],
		'bare' => [ 'type' => 'bool', 'default' => false, 'label' => [ 'de_DE' => 'Nur die Zahl', 'en_US' => 'The number alone' ],
			'hint' => [ 'de_DE' => 'Ohne das Wort dahinter und im Satz: für einen eigenen Satz um die Zahl herum.', 'en_US' => 'Without the word behind it, and inline: for a sentence of your own around the number.' ] ],
	],
];
