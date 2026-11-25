<?php
declare(strict_types=1);

// The schema of the core, version 1, in one piece: what Db::ensure() runs on a file that has none of it. A later version adds its steps under 'steps',
// n => the statements that lead from n - 1 to n, and changes 'create' to the whole schema of that version. The tables are the ones Db::TABLES names.
// A foreign key that restricts names the client, so that a path that forgets the rows of a client cannot delete it. A project is an element of the
// type crm-projects, which holds its id as text, so no foreign key points at it. The first statement is the register of the schemas of this file:
// one row for every package that made tables here, with the tables it owns and the name of the folder the kernel had for it (empty where none was kept: such a package is taken to be installed).
return [

	'create' => [

		'CREATE TABLE schema_versions (
			package TEXT PRIMARY KEY,
			version INTEGER NOT NULL,
			tables TEXT NOT NULL,
			applied TEXT NOT NULL,
			folder TEXT NOT NULL DEFAULT \'\'
		)',

		'CREATE TABLE clients (
			id INTEGER PRIMARY KEY,
			number TEXT NOT NULL UNIQUE,
			company TEXT NOT NULL DEFAULT \'\',
			name TEXT NOT NULL DEFAULT \'\',
			street TEXT NOT NULL DEFAULT \'\',
			zip TEXT NOT NULL DEFAULT \'\',
			city TEXT NOT NULL DEFAULT \'\',
			country TEXT NOT NULL DEFAULT \'DE\',
			email TEXT NOT NULL DEFAULT \'\',
			phone TEXT NOT NULL DEFAULT \'\',
			vat_id TEXT NOT NULL DEFAULT \'\',
			business INTEGER NOT NULL DEFAULT 0,
			buyer_reference TEXT NOT NULL DEFAULT \'\',
			notes TEXT NOT NULL DEFAULT \'\',
			active INTEGER NOT NULL DEFAULT 1,
			created TEXT NOT NULL,
			updated TEXT NOT NULL
		)',

		'CREATE TABLE settings (
			key TEXT PRIMARY KEY,
			value TEXT NOT NULL
		)',

		'CREATE TABLE sequences (
			category TEXT NOT NULL,
			year INTEGER NOT NULL,
			next INTEGER NOT NULL,
			PRIMARY KEY ( category, year )
		)',

		'CREATE TABLE records (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			kind TEXT NOT NULL,
			origin TEXT NOT NULL,
			client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT,
			member_id TEXT NOT NULL,
			member_email TEXT NOT NULL DEFAULT \'\',
			member_name TEXT NOT NULL DEFAULT \'\',
			thing TEXT NOT NULL DEFAULT \'\',
			thing_id TEXT NOT NULL DEFAULT \'\',
			project TEXT NOT NULL DEFAULT \'\',
			purpose TEXT NOT NULL DEFAULT \'\',
			parent INTEGER REFERENCES records( id ) ON DELETE RESTRICT,
			sha256 TEXT NOT NULL,
			shown TEXT NOT NULL,
			at TEXT NOT NULL,
			ip TEXT NOT NULL DEFAULT \'\',
			agent TEXT NOT NULL DEFAULT \'\'
		)',
		'CREATE INDEX records_client_kind ON records ( client_id, kind )',
		'CREATE INDEX records_thing ON records ( thing, thing_id )',
		'CREATE INDEX records_client_purpose ON records ( client_id, purpose )',
		'CREATE UNIQUE INDEX records_parent ON records ( parent ) WHERE parent IS NOT NULL',

		'CREATE TABLE project_states (
			project TEXT PRIMARY KEY,
			phase TEXT NOT NULL DEFAULT \'\',
			favorite INTEGER NOT NULL DEFAULT 0
		)',

		'CREATE TABLE project_shares (
			project TEXT NOT NULL,
			client_id INTEGER NOT NULL REFERENCES clients( id ) ON DELETE RESTRICT,
			since TEXT NOT NULL,
			"by" TEXT NOT NULL DEFAULT \'system\',
			source TEXT NOT NULL DEFAULT \'crm\',
			ref TEXT NOT NULL DEFAULT \'\',
			PRIMARY KEY ( project, client_id )
		)',
		'CREATE INDEX project_shares_client ON project_shares ( client_id, since, project )',

		'CREATE TABLE tasks (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			project TEXT NOT NULL,
			text TEXT NOT NULL,
			done INTEGER NOT NULL DEFAULT 0,
			done_at TEXT NOT NULL DEFAULT \'\',
			position INTEGER NOT NULL,
			source TEXT NOT NULL DEFAULT \'manual\',
			ref TEXT NOT NULL DEFAULT \'\',
			created_by TEXT NOT NULL DEFAULT \'\',
			created_at TEXT NOT NULL,
			updated_at TEXT NOT NULL
		)',
		'CREATE INDEX tasks_project ON tasks ( project, done, position )',
		'CREATE UNIQUE INDEX tasks_ref ON tasks ( source, ref ) WHERE ref <> \'\'',

		'CREATE TABLE logbook (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			client_id INTEGER,
			project TEXT NOT NULL DEFAULT \'\',
			at TEXT NOT NULL,
			source TEXT NOT NULL,
			kind TEXT NOT NULL,
			text TEXT NOT NULL DEFAULT \'\',
			data TEXT NOT NULL DEFAULT \'{}\',
			ref TEXT NOT NULL DEFAULT \'\',
			"by" TEXT NOT NULL DEFAULT \'\'
		)',
		'CREATE INDEX logbook_client ON logbook ( client_id, at, id )',
		'CREATE INDEX logbook_project ON logbook ( project, at, id )',
		'CREATE INDEX logbook_ref ON logbook ( source, kind, ref )',

		'CREATE TABLE owner_devices (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_mail TEXT NOT NULL,
			token_sha256 TEXT NOT NULL,
			previous_sha256 TEXT NOT NULL DEFAULT \'\',
			rotated_at INTEGER NOT NULL DEFAULT 0,
			created_at INTEGER NOT NULL,
			last_seen INTEGER NOT NULL,
			expires INTEGER NOT NULL,
			pw_mark TEXT NOT NULL,
			ua TEXT NOT NULL DEFAULT \'\'
		)',
		'CREATE INDEX owner_devices_user ON owner_devices ( user_mail )',
	],

	'steps' => [],
];
