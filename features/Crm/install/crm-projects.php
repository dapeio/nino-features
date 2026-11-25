<?php
declare(strict_types=1);

// features/Crm/install/crm-projects.php - the element type the projects are kept in, copied to /elements/crm-projects.php where the project has none (the unit's elementTypes, add-only: a type of this
// name that the project has stays as it is, whatever fields it changed). A project is an element of this type, entered in the screen of the projects of the surface or in the Elements panel; Projects
// reads them, a package names one by its id (the last part of its uri).
//
// The shape is the one \Nino\Elements::insertElementType() writes: the model, then one bucket per locale plus '*' for what is not translated, and the counter of a numbered type. A project has no
// name of its own that a person would have to invent: it is numbered (00001, 00002 ...) as the Elements panel numbers the elements of a type that says so, and the title is a field. Empty - a project
// that was seeded is a project somebody has to find and delete. The prefix crm- is there because a type of a unit is add-only: a site's own type called projects would be the CRM's.
//
// Nothing of a project is per language: it has one name, one note and one day, whatever language the site is shown in. The client is the number as Clients writes it (K-0001). What a package keeps
// of a project - the folder of its files, say - is the package's own, in a table of its own
return [
	'title' => 'CRM projects',
	'autoincrement' => 1,
	'model' => [
		'title' => [
			'type'			=> 'string',
			'required'	=> true,
			'maxlength'	=> 200,
			'label'			=> [ 'en_US' => 'Title', 'de_DE' => 'Titel' ],
		],
		// The number of the client the project is for, K-0001
		'client' => [
			'type'			=> 'string',
			'required'	=> true,
			'maxlength'	=> 10,
			'label'			=> [ 'en_US' => 'Client (number)', 'de_DE' => 'Kunde (Nummer)' ],
		],
		'description' => [
			'type'			=> 'string',
			'breaks'		=> true,
			'inputsize'	=> 4,
			'maxlength'	=> 2000,
			'label'			=> [ 'en_US' => 'Description', 'de_DE' => 'Beschreibung' ],
		],
		// The day the project begins
		'started' => [
			'type'			=> 'date',
			'label'			=> [ 'en_US' => 'Begins', 'de_DE' => 'Beginn' ],
		],
		// Done. Off is the usual case: a new project is under way without anybody touching the switch
		'done' => [
			'type'			=> 'boolean',
			'label'			=> [ 'en_US' => 'Done', 'de_DE' => 'Abgeschlossen' ],
		],
	],
	'*' => [
		'*' => [ 'done' => false ],
	],
];
