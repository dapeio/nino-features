<?php
declare(strict_types=1);

return [
	'group'      => 'search',
	'label'      => [ 'de_DE' => 'Suchfeld', 'en_US' => 'Search field' ],
	'icon'       => 'search',
	'in'         => [ 'flow', 'header-actions' ],
	'source'     => 'none',
	'preview'    => 'button',
	'attributes' => [
		'action' => [ 'type' => 'link', 'default' => '', 'label' => [ 'de_DE' => 'Ergebnisseite', 'en_US' => 'Results page' ],
			'hint' => [ 'de_DE' => 'Die Seite mit der Trefferliste. Leer sucht auf der Seite, auf der das Feld steht.', 'en_US' => 'The page with the list of hits. Empty searches on the page the field stands on.' ] ],
		'key'    => [ 'type' => 'string', 'default' => 'q', 'label' => [ 'de_DE' => 'Query-Variable', 'en_US' => 'Query variable' ],
			'hint' => [ 'de_DE' => 'Der Name des Eingabefelds in der Adresse. Trefferliste und Zahl lesen denselben Namen; leer nimmt q.', 'en_US' => 'The name of the input in the address. The list of hits and their number read the same name; empty takes q.' ] ],
	],
];
