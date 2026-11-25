<?php
declare(strict_types=1);

// The schema of the probe, version 1: one table that names a client, and an index
return [
	'create' => [
		'CREATE TABLE crm_probe_items (
			id INTEGER PRIMARY KEY,
			client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT,
			label TEXT NOT NULL DEFAULT \'\'
		)',
		'CREATE INDEX crm_probe_items_client ON crm_probe_items ( client_id )',
	],
	'steps' => [],
];
