<?php
declare(strict_types=1);

/**
 *	Nino features
 *	translation.php		The table that says how an action of the old CRM is answered by the core now, for the comparison of the two on the same data: the name of the action, the types of the elements, the codes that were
 *										renamed, and for each action what of its answer belongs to a package and is not the core's any more (drop: the dotted path of a key, * for each member of a list) or is built in another way (restructured).
 *										An answer of the old CRM that is the answer of the core after the names are changed is the same; one that is it after what belongs to a package is taken out of both is explained; anything else is a fault.
 *										The table holds no datum of any installation.
 */
return [

	// The actions the core answers, old name => new name
	'actions' => [
		'crm-clients/list' => 'crm-clients/list', 'crm-clients/get' => 'crm-clients/get', 'crm-clients/delete-preview' => 'crm-clients/delete-preview',
		'crm-projekte/list' => 'crm-projects/list', 'crm-projekte/get' => 'crm-projects/get', 'crm-projekte/shares' => 'crm-projects/shares',
		'crm-tasks/list' => 'crm-tasks/list', 'crm-tasks/open' => 'crm-tasks/open', 'crm-logbook/list' => 'crm-logbook/list',
		'crm/status' => 'crm/status', 'crm/notifications' => 'crm/notifications',
		'crm-documents/settings-get' => 'crm/settings-get', 'crm-documents/overview' => 'crm/overview',
		'elements/types' => 'elements/types', 'elements/list' => 'elements/list', 'elements/get' => 'elements/get', 'text/keys' => 'text/keys',
	],

	// The element types of the core, old name => new name; the other types the old endpoint answered belong to a package (positionen: the documents, begruessungen: the portal, events: the dates)
	'types' => [ 'projekte' => 'crm-projects', 'phasen' => 'crm-phases' ],
	'packages' => [ 'positionen' => 'crm-documents', 'begruessungen' => 'crm-portal', 'events' => 'crm-dates' ],

	// The codes that were renamed: a prefix of the old code => the prefix of the new
	'codes' => [ 'crm_projekte_' => 'crm_projects_', 'crm_positionen_type' => 'crm_values_type', 'crm_documents_database' => 'crm_database', 'crm_crm_database' => 'crm_database', 'crm_clients_database' => 'crm_database' ],

	// What of an answer belongs to a package: action (old name) => path => the package
	'drop' => [
		'crm-clients/list' => [ 'clients.*.accounts' => 'crm-portal', 'clients.*.active_accounts' => 'crm-portal', 'clients.*.signed_accounts' => 'crm-portal', 'clients.*.group' => 'crm-portal', 'clients.*.privacy' => 'crm-portal', 'clients.*.message' => 'crm-portal', 'clients.*.message_at' => 'crm-portal', 'clients.*.ready' => 'crm-documents' ],
		'crm-clients/get' => [ 'client.accounts' => 'crm-portal', 'client.active_accounts' => 'crm-portal', 'client.signed_accounts' => 'crm-portal', 'client.group' => 'crm-portal', 'client.privacy' => 'crm-portal', 'client.message' => 'crm-portal', 'client.message_at' => 'crm-portal', 'client.ready' => 'crm-documents', 'client.documents' => 'crm-documents', 'accounts' => 'crm-portal' ],
		'crm-clients/delete-preview' => [ 'preview.counts.fixed' => 'crm-documents', 'preview.counts.drafts' => 'crm-documents', 'preview.counts.accounts' => 'crm-portal', 'preview.counts.folders' => 'crm-files', 'preview.counts.files' => 'crm-files', 'preview.counts.comments' => 'crm-mediareview',
			'preview.counts.events' => 'crm-dates', 'preview.counts.problems' => 'crm-portal', 'preview.counts.signs' => 'crm-documents', 'preview.folders' => 'crm-files', 'preview.features' => 'crm-workshops' ],
		'crm-projekte/list' => [ 'files' => 'crm-files', 'allowed' => 'crm-files', 'projects.*.folder' => 'crm-files', 'projects.*.documents' => 'crm-documents', 'projects.*.open' => 'crm-documents', 'projects.*.download' => 'crm-files', 'projects.*.comments' => 'crm-mediareview', 'projects.*.comment_files' => 'crm-mediareview',
			'projects.*.files' => 'crm-files', 'projects.*.approval' => 'crm-files', 'projects.*.consent_social' => 'crm-portal', 'projects.*.client_accounts' => 'crm-portal', 'projects.*.client_signed' => 'crm-portal', 'shared.*.folder' => 'crm-files', 'shared.*.download' => 'crm-files' ],
		'crm-projekte/get' => [ 'files' => 'crm-files', 'allowed' => 'crm-files', 'review' => 'crm-mediareview', 'folder' => 'crm-files', 'list' => 'crm-files', 'events' => 'crm-dates', 'next_event' => 'crm-dates', 'notify' => 'crm-files', 'project.folder' => 'crm-files', 'project.documents' => 'crm-documents', 'project.open' => 'crm-documents',
			'project.download' => 'crm-files', 'project.download_at' => 'crm-files', 'project.comments' => 'crm-mediareview', 'project.comment_files' => 'crm-mediareview', 'project.files' => 'crm-files', 'project.approval' => 'crm-files', 'project.consent_social' => 'crm-portal', 'project.consents' => 'crm-portal',
			'project.titles' => 'crm-files', 'project.client_accounts' => 'crm-portal', 'project.client_signed' => 'crm-portal', 'client.active_accounts' => 'crm-portal', 'client.signed_accounts' => 'crm-portal', 'shares.*.folder' => 'crm-files' ],
		'crm-tasks/list' => [ 'tasks.*.link' => 'crm-files' ],
		'crm-logbook/list' => [ 'entries.*.document' => 'crm-documents' ],
		'crm/notifications' => [ 'switches' => 'the packages that own them', 'features' => 'the packages that own them' ],
		'crm-documents/settings-get' => [ 'settings.*' => 'the packages that own the keys', 'addresses.*' => 'crm-portal', 'next.offer' => 'crm-documents', 'next.invoice' => 'crm-documents', 'next.credit' => 'crm-documents', 'templates' => 'crm-documents', 'pdf' => 'crm-documents', 'portal' => 'crm-portal', 'packages' => 'the packages' ],
		'elements/list' => [ 'elements.*.values.folder' => 'crm-files', 'elements.*.from' => 'the kernel (the panel of Nino 2 names what an element comes from)' ],
		'elements/get' => [ 'element.folder' => 'crm-files', 'global.folder' => 'crm-files', 'raw.*.folder' => 'crm-files' ],
	],

	// A value that is taken out of a list of values, action (old name) => path of the list => the values: a column of a package
	'dropValues' => [ 'elements/list' => [ 'columns' => [ 'folder' ] ] ],

	// A refusal of the panel that had no code gets one from the core (Values::coded()): the code and the field are the core's addition, what else the answer says is the panel's
	'coded' => [ 'crm_elements_' ],

	// A reason that stops the deletion of a client and belongs to a package: the old preview says it, the core has no such reason
	'reasons' => [ 'fixed' => 'crm-documents' ],

	// What the core answers and the old CRM did not: path (taken out of the answer of the core alone) => the reason, which says it is the core's
	'coreAdded' => [ 'crm-tasks/list' => [ 'tasks.*.ref' => 'the core: the reference column of the tasks table of the schema, with which a package finds the task it made' ] ],

	// The actions of the old CRM that are no action of the core any more because the core answers them in another: old action => the new one
	'folded' => [ 'crm-documents/status' => 'crm/status (the lines of the status, Bauplan 4.1)' ],

	// The lines of the old status of the core's concerns, old key => the new line of the core or the package that has it now; the others are the packages' own
	'statusLines' => [
		'data' => [ 'database', 'writable', 'schema' ], 'home' => [ 'addresses' ], 'mail-cap' => [ 'mail' ],
		'module' => 'crm-documents', 'members' => 'crm-portal', 'files' => 'crm-files', 'review' => 'crm-mediareview', 'sign' => 'crm-documents', 'pdf' => 'crm-pdf', 'events' => 'crm-dates', 'portal' => 'crm-portal',
		'features-units' => 'the packages', 'pdf-templates' => 'crm-pdf', 'signature' => 'crm-documents', 'notify' => 'the packages that own the switches', 'privacy' => 'crm-portal',
	],

	// What the type of the phases says of itself: the title is one string in the language of the installation (the kernel keeps no title per language: the old CRM said it in German, the type file of the core in English), the note is the text of the unit and is compared
	// The note of the phases says the same without the clause that names the portal: it is the portal's to say
	'typeNote' => 'the clause "der Kunde sieht ihren Titel im Portal" belongs to the portal',
	'typeTitle' => 'the kernel keeps one title for a type, in the native language of the installation',

	// What is built another way: the answer is not compared but for what is named under keep (dotted paths that must be equal)
	'restructured' => [
		'crm/status' => [ 'reason' => 'the lines of the status are the core\'s own, entered by it and by the packages (items became lines)', 'keep' => [] ],
		'crm-documents/overview' => [ 'reason' => 'the summary, the needs and the recent documents belong to the documents, the comments to the review, the stale projects to the files; the core answers the count of the clients', 'keep' => [ 'summary.clients' ] ],
		'elements/types' => [ 'reason' => 'the types of the screen of the values are the core\'s and the packages\' that were entered, and the labels are in the model', 'keep' => [] ],
	],
];
