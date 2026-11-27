<?php
declare(strict_types=1);

// The schema of the package, version 1: which element of the type of Events is a date of which client. The id of the client is what holds: a number that is given to another client after the test phase
// names another row of clients, so that the new client has none of these. The column created is the second the row was made in, so that a row whose element is not there yet is told from one whose element is gone. The foreign key restricts: a path that forgets the rows of a client cannot delete it
return [
	'create' => [
		'CREATE TABLE crm_dates (
			name TEXT PRIMARY KEY,
			client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT,
			created INTEGER NOT NULL DEFAULT 0
		)',
		'CREATE INDEX crm_dates_client ON crm_dates ( client_id )',
	],
	'steps' => [],
];
