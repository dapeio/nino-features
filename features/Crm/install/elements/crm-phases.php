<?php
declare(strict_types=1);

// features/Crm/install/elements/crm-phases.php - the four phases a project can be put into, brought to the type 'crm-phases' by the unit's elements key, which only ever adds (\Nino\Elements::seed()): a phase
// the owner changed is not touched and one the owner deleted does not come back (the core remembers it, see Projects::callbackCommitted()). The names are the keys the rows of the projects hold, so they do not
// change; the titles are the words of the surface in each language, the order the order of the work. There is no model: the type is the unit's own file
return [
	'*' => [
		'recording' => [ 'order' => 10 ],
		'mix'       => [ 'order' => 20 ],
		'master'    => [ 'order' => 30 ],
		'review'    => [ 'order' => 40 ],
	],
	'de_DE' => [
		'recording' => [ 'title' => 'Aufnahme' ],
		'mix'       => [ 'title' => 'Mix' ],
		'master'    => [ 'title' => 'Master' ],
		'review'    => [ 'title' => 'Abnahme' ],
	],
	'en_US' => [
		'recording' => [ 'title' => 'Recording' ],
		'mix'       => [ 'title' => 'Mix' ],
		'master'    => [ 'title' => 'Master' ],
		'review'    => [ 'title' => 'Review' ],
	],
];
