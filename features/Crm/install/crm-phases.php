<?php
declare(strict_types=1);

// features/Crm/install/crm-phases.php - the element type the phases of a project are kept in, copied to /elements/crm-phases.php where the project has none (add-only). A phase is an element of this type,
// entered in the screen of the values of the surface or in the Elements panel; Projects reads them, the row of a project (project_states) holds the name of the element.
//
// The name of an element - the last part of its uri, lower-case words divided by hyphens, at most thirty characters - is the key the phase has in the row of a project and does not change once the phase is
// made. The title is per language; the order is a whole number and decides where the phase stands among the others. The four phases of the first release come as elements through the unit's elements
// (elements/crm-phases.php), not in this file: the type is empty
return [
	'title' => 'CRM project phases',
	'model' => [
		'title' => [
			'type'			=> 'string',
			'locale'		=> true,
			'required'	=> true,
			'maxlength'	=> 60,
			'label'			=> [ 'en_US' => 'Title', 'de_DE' => 'Titel' ],
		],
		// Where the phase stands among the others: the lower number is the earlier phase
		'order' => [
			'type'			=> 'integer',
			'label'			=> [ 'en_US' => 'Order', 'de_DE' => 'Reihenfolge' ],
		],
	],
	'*' => [
		'*' => [],
	],
];
