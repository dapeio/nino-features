<?php
declare(strict_types=1);

return [
	'group'      => 'search',
	'label'      => [ 'de_DE' => 'Trefferliste', 'en_US' => 'List of hits' ],
	'icon'       => 'list',
	'in'         => [ 'flow', 'section-row' ],
	'offers'     => [ 'flow', 'section-row', 'section-col' ],
	'through'    => true,
	'children'   => 'nodes',
	'preview'    => 'cells',
	'attributes' => [
		'type'  => [ 'type' => 'string', 'default' => '', 'label' => [ 'de_DE' => 'Elementtypen', 'en_US' => 'Element types' ],
			'hint' => [ 'de_DE' => 'Ein Elementtyp oder mehrere, durch Komma getrennt, etwa /products. Leer sucht in jedem indizierten Typ. Nur Typen mit Index werden durchsucht.', 'en_US' => 'One element type, or several separated by commas, such as /products. Empty searches every indexed type. Only types with an index are searched.' ] ],
		'key'   => [ 'type' => 'string', 'default' => 'q', 'label' => [ 'de_DE' => 'Query-Variable', 'en_US' => 'Query variable' ],
			'hint' => [ 'de_DE' => 'Der Name des Eingabefelds im Suchfeld. Leer nimmt q.', 'en_US' => 'The name of the input of the search field. Empty takes q.' ] ],
		'limit' => [ 'type' => 'int', 'default' => 20, 'min' => 1, 'max' => 200, 'label' => [ 'de_DE' => 'Höchstens', 'en_US' => 'At most' ],
			'hint' => [ 'de_DE' => 'Die Zahl der Treffer, die gezeichnet werden: höchstens 200.', 'en_US' => 'The number of hits that are drawn: 200 at most.' ] ],
		'empty' => [ 'type' => 'text', 'default' => '', 'label' => [ 'de_DE' => 'Text ohne Treffer', 'en_US' => 'Text without hits' ],
			'hint' => [ 'de_DE' => 'Was steht, wenn gesucht wurde und nichts passt. Leer sagt es mit einem Wort des Features.', 'en_US' => 'What stands when something was searched for and nothing fits. Empty says it with a word of the feature.' ] ],
	],
];
