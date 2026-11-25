<?php
declare(strict_types=1);

// The schema of the probe, version 2: a column more, whole for a file that has none of it and as a step for a file that has version 1
return [
	'create' => [
		'CREATE TABLE crm_probe_items (
			id INTEGER PRIMARY KEY,
			client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT,
			label TEXT NOT NULL DEFAULT \'\',
			note TEXT NOT NULL DEFAULT \'\'
		)',
		'CREATE INDEX crm_probe_items_client ON crm_probe_items ( client_id )',
	],
	'steps' => [
		2 => [ 'ALTER TABLE crm_probe_items ADD COLUMN note TEXT NOT NULL DEFAULT \'\'' ],
	],
];
