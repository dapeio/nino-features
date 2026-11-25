<?php
// The class is not declared here: features/Crm/ can only serve \Nino\Modules\Crm
return [
	'key'					=> 'crm',
	'name'				=> [ 'en_US' => 'CRM', 'de_DE' => 'CRM' ],
	'description'	=> [
		'en_US' => 'The CRM keeps the clients and the projects of a business in one database file of its own, with a schema that every package built on it adds its tables to. It brings the clients with their projects, phases, tasks and logbook, the evidence of what a person agreed to, the deletion of a client and the test phase, the settings, the hosts, the band at the head of mails and the endpoint /crm/api that the surface of the CRM talks to, and a panel that says what is there and what is missing. Packages for documents, a portal and files dock to it.',
		'de_DE' => 'Das CRM hält die Kunden und die Projekte eines Betriebs in einer eigenen Datenbankdatei, in deren Schema jedes darauf gebaute Paket seine Tabellen einträgt. Es bringt die Kunden mit ihren Projekten, Phasen, Aufgaben und dem Logbuch, die Nachweise dessen, wozu jemand zugestimmt hat, das Löschen eines Kunden und die Testphase, die Einstellungen, die Hosts, das Band am Kopf der Mails und den Endpunkt /crm/api, mit dem die Oberfläche des CRM spricht, und ein Panel, das sagt, was da ist und was fehlt. Pakete für Belege, ein Portal und Dateien docken daran an.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[mail-head]' => [
				'en_US' => 'The first line of every mail of the packages: the band of the logo or of the wordmark of the settings; a band another module drew before stands.',
				'de_DE' => 'Die erste Zeile jeder Mail der Pakete: das Band des Logos oder der Wortmarke aus den Einstellungen; ein Band, das ein anderes Modul zuvor zeichnete, bleibt stehen.',
			],
		],
		'markup' => [
			'[[word]], [[domain]], [[sign]], [[brand-color]]' => [
				'en_US' => 'The places of the band of the wordmark in crm-head-word.tpl, which the core fills in one pass, escaped; the sign and the second line are templates of their own.',
				'de_DE' => 'Die Plätze des Wortmarken-Bandes in crm-head-word.tpl, die der Kern in einem Durchgang und maskiert füllt; das Zeichen und die zweite Zeile sind Vorlagen für sich.',
			],
		],
		'routes' => [
			'/crm' => [
				'en_US' => 'The surface of the CRM: the login, the overview, the clients, the projects, the tasks, the settings and the values, for an account that holds the permission of the CRM.',
				'de_DE' => 'Die Oberfläche des CRM: Anmeldung, Übersicht, Kunden, Projekte, Aufgaben, Einstellungen und Werte, für ein Konto mit dem Recht des CRM.',
			],
			'/crm/todo' => [
				'en_US' => 'The app of the open tasks of the projects, with its own manifest and icon.',
				'de_DE' => 'Die App der offenen Aufgaben der Projekte, mit eigenem Manifest und Symbol.',
			],
			'/crm/assets/*' => [
				'en_US' => 'The scripts and the style sheet of the surface and of the packages, by the hash of their content, for a year.',
				'de_DE' => 'Die Skripte und der Stil der Oberfläche und der Pakete, nach dem Hash ihres Inhalts, ein Jahr lang.',
			],
			'/.crm/place' => [
				'en_US' => 'Answers a request that is turned away from the host it was made on: 404, or the 301 of a bookmark of the workbench to the host that owns it.',
				'de_DE' => 'Beantwortet eine Anfrage, die vom Host, an den sie ging, abgewiesen wird: mit 404 oder mit der 301 eines Lesezeichens der Werkstatt auf den Host, dem sie gehört.',
			],
			'/.crm/probe' => [
				'en_US' => 'Shows that a name is this installation: the hash of a number made for the question, for ten minutes, and 404 for everybody else.',
				'de_DE' => 'Zeigt, dass ein Name diese Installation ist: den Hash einer für die Frage gemachten Zahl, zehn Minuten lang, und für alle anderen 404.',
			],
			'/crm/api' => [
				'en_US' => 'One POST with an action and its data, answered as json under the permission of the CRM; like every action of the workbench it starts the daily backup and syncs the base texts.',
				'de_DE' => 'Ein POST mit einer Aktion und ihren Daten, als json beantwortet unter dem Recht des CRM; wie jede Aktion der Werkstatt stößt er die tägliche Sicherung an und gleicht die Basis-Texte ab.',
			],
		],
		'panel' => [
			'CRM' => [
				'en_US' => 'The status of the core in a table, with a button at a line that has a fix; the way to the surface; the data of removed packages, which a typed confirmation deletes.',
				'de_DE' => 'Der Zustand des Kerns in einer Tabelle, mit einem Knopf an einer Zeile, die sich beheben lässt; der Weg zur Oberfläche; die Daten entfernter Pakete, die eine getippte Bestätigung löscht.',
			],
		],
		'callbacks' => [
			'/crm/client/added' => [
				'en_US' => 'Tells a package that a client was made, with the client and what its form posted; a listener answers under its key, and the answers go to the page.',
				'de_DE' => 'Meldet einem Paket, dass ein Kunde angelegt wurde, mit dem Kunden und dem, was sein Formular schickte; ein Listener antwortet unter seinem Schlüssel, die Antworten gehen an die Seite.',
			],
			'/crm/client/changed' => [
				'en_US' => 'Tells a package that the master data of a client were changed, with the client and what its form posted.',
				'de_DE' => 'Meldet einem Paket, dass die Stammdaten eines Kunden geändert wurden, mit dem Kunden und dem, was sein Formular schickte.',
			],
			'/crm/client/active' => [
				'en_US' => 'Asked before a client is switched on or off; a package that cannot follow says so in failed and the client stays as it was.',
				'de_DE' => 'Wird gefragt, bevor ein Kunde ein- oder ausgeschaltet wird; ein Paket, das nicht folgen kann, sagt es in failed, und der Kunde bleibt, wie er war.',
			],
			'/crm/client/holds' => [
				'en_US' => 'Asked what a package holds on a client; a listener adds an entry to holds, and one that blocks keeps the client from being deleted.',
				'de_DE' => 'Fragt, was ein Paket an einem Kunden hält; ein Listener ergänzt holds um einen Eintrag, und ein sperrender hält den Kunden vor dem Löschen zurück.',
			],
			'/crm/client/deleted' => [
				'en_US' => 'Tells a package that a client is gone, with its number and addresses; it answers in gone (how many it deleted) or in failed.',
				'de_DE' => 'Meldet einem Paket, dass ein Kunde gelöscht ist, mit Nummer und Adressen; es antwortet in gone (wie viel es gelöscht hat) oder in failed.',
			],
			'/crm/project/added' => [
				'en_US' => 'Tells a package that a project was made, with what the form posted; a listener may answer with a refusal, which takes the project back.',
				'de_DE' => 'Meldet einem Paket, dass ein Projekt angelegt wurde, mit dem, was das Formular schickte; ein Listener darf mit einer Absage antworten, die das Projekt zurücknimmt.',
			],
			'/crm/project/removed' => [
				'en_US' => 'Tells a package that a project is gone, so that it forgets what it kept of it.',
				'de_DE' => 'Meldet einem Paket, dass ein Projekt gelöscht ist, damit es vergisst, was es davon hielt.',
			],
			'/crm/project/holds' => [
				'en_US' => 'Asked what keeps a project from being deleted or from changing its client; a listener adds holds with a count, a sentence and the codes of the refusals.',
				'de_DE' => 'Fragt, was ein Projekt am Löschen oder am Wechsel seines Kunden hindert; ein Listener ergänzt Halte mit Zahl, Satz und den Codes der Absagen.',
			],
			'/crm/project/figures' => [
				'en_US' => 'Asked for the numbers and marks a package adds to the rows of the list of projects; one question for all the rows, answered under the ids.',
				'de_DE' => 'Fragt nach den Zahlen und Marken, die ein Paket den Zeilen der Projektliste hinzufügt; eine Frage für alle Zeilen, beantwortet unter den Kennungen.',
			],
			'/crm/project/withheld' => [
				'en_US' => 'Asked whether a package holds back from the client of a project what it shows it; the answer names its key, an id and a label.',
				'de_DE' => 'Fragt, ob ein Paket dem Kunden eines Projekts vorenthält, was es ihm zeigt; die Antwort nennt seinen Schlüssel, eine Kennung und eine Bezeichnung.',
			],
			'/crm/wiped' => [
				'en_US' => 'Tells a package that the test phase deleted the clients, so that it forgets the numbers and ids it kept; what it cannot forget it names in failed.',
				'de_DE' => 'Meldet einem Paket, dass die Testphase die Kunden gelöscht hat, damit es vergisst, was es an Nummern und Kennungen hielt; was es nicht vergessen kann, nennt es in failed.',
			],
			'/crm/overview' => [
				'en_US' => 'Asked for what a package shows on the first screen: a part under packages, under its key, and the needs of the owner.',
				'de_DE' => 'Fragt, was ein Paket auf dem ersten Bildschirm zeigt: einen Teil unter packages, unter seinem Schlüssel, und die Anliegen des Inhabers.',
			],
			'/crm/settings/save' => [
				'en_US' => 'Asked before the settings are written; a package may add the keys it keeps of its own to clean or refuse the whole post.',
				'de_DE' => 'Wird gefragt, bevor die Einstellungen geschrieben werden; ein Paket darf die Schlüssel, die es selbst führt, zu clean hinzufügen oder den ganzen Post ablehnen.',
			],
			'/crm/element/check' => [
				'en_US' => 'Asked before an element of the screens of the values is saved; a package may refuse it with a status, a sentence, a code and the field.',
				'de_DE' => 'Wird gefragt, bevor ein Element der Werte-Bildschirme gespeichert wird; ein Paket darf es mit Status, Satz, Code und Feld ablehnen.',
			],
			'/crm/element/start' => [
				'en_US' => 'Asked what a new element of a type starts with and which fields the form leaves out.',
				'de_DE' => 'Fragt, womit ein neues Element eines Typs beginnt und welche Felder das Formular auslässt.',
			],
			'/nino/http/request' => [
				'en_US' => 'Turns a request away from a host that does not own its place: the CRM host, the app host and the website each answer their own paths; 503 while the settings cannot be read.',
				'de_DE' => 'Weist eine Anfrage ab, wenn der Host den Ort nicht besitzt: CRM-Host, App-Host und Website beantworten je ihre eigenen Pfade; 503, solange sich die Einstellungen nicht lesen lassen.',
			],
			'/nino/html/absolute-url' => [
				'en_US' => 'Writes the address of the CRM and of the workbench on the hosts the settings name for them.',
				'de_DE' => 'Schreibt die Adresse des CRM und der Werkstatt auf die Hosts, die die Einstellungen für sie nennen.',
			],
			'/crm/settings/changed' => [
				'en_US' => 'Fired when a setting was written, with the key and the value, for packages that keep a copy of one.',
				'de_DE' => 'Wird ausgelöst, wenn eine Einstellung geschrieben wurde, mit Schlüssel und Wert, für Pakete, die eine Kopie davon halten.',
			],
			'/crm/settings/view' => [
				'en_US' => 'Asked for what a package shows of itself in the settings; a callback puts its part under packages, under its key.',
				'de_DE' => 'Fragt, was ein Paket von sich in den Einstellungen zeigt; ein Callback legt seinen Teil unter packages ab, unter seinem Schlüssel.',
			],
			'/crm/hosts/shared' => [
				'en_US' => 'Asked whether a path is answered on every host; a callback sets shared to true for a path of its package.',
				'de_DE' => 'Fragt, ob ein Pfad auf jedem Host beantwortet wird; ein Callback setzt shared für einen Pfad seines Pakets auf true.',
			],
		],
		'install' => [
			'templates/mail/crm-head.tpl' => [
				'en_US' => 'Four templates of the band at the head of mails (crm-head, -word, -domain, -sign), add-only: the project may change them.',
				'de_DE' => 'Vier Vorlagen des Bandes am Kopf der Mails (crm-head, -word, -domain, -sign), nur ergänzend: Das Projekt darf sie ändern.',
			],
			'imageSlots /logo/mail' => [
				'en_US' => 'The picture place of the logo in mails, 440 by 88 pixels; it stays empty until the owner uploads a picture.',
				'de_DE' => 'Der Bildplatz des Logos in Mails, 440 mal 88 Pixel; er bleibt leer, bis der Inhaber ein Bild hochlädt.',
			],
			'text/<locale>.php' => [
				'en_US' => 'The four words of the test mail of the wordmark, in the Text panel, where the project keeps them.',
				'de_DE' => 'Die vier Worte der Testmail der Wortmarke, im Panel Texte, wo das Projekt sie pflegt.',
			],
			'/data/crm.sqlite' => [
				'en_US' => 'The database, of which a backup carries a whole copy that the core makes, kept without a write-ahead log - the price is the readers, who can wait up to about 0.15 s for a write, with the log 0.015 s.',
				'de_DE' => 'Die Datenbank, von der eine Sicherung eine ganze Kopie trägt, die der Kern macht, ohne Write-ahead-Log geführt - Leser warten dafür bis zu etwa 0,15 s auf ein Schreiben, mit Log 0,015 s.',
			],
			'/data/crm.sqlite empty or cut off' => [
				'en_US' => 'A file at this place that is empty, or cut to less than the 72 bytes of its header, is not the core\'s database: the backup packs it as it lies, without an error.',
				'de_DE' => 'Eine Datei an dieser Stelle, die leer oder auf weniger als die 72 Bytes ihres Kopfes gekürzt ist, ist nicht die Datenbank des Kerns: Die Sicherung packt sie, wie sie liegt, ohne Fehler.',
			],
			'elements crm-projects, crm-phases' => [
				'en_US' => 'The element types of the projects and of their phases, with the four phases of the work as elements in both languages; add-only, the project may change them.',
				'de_DE' => 'Die Elementtypen der Projekte und ihrer Phasen, mit den vier Phasen der Arbeit als Elemente in beiden Sprachen; nur ergänzend, das Projekt darf sie ändern.',
			],
			'elements/privacy.php' => [
				'en_US' => 'The section of the privacy policy of the CRM at position 880: the clients, the logbook, the evidence and the cookie of a device; a starting point and no legal advice.',
				'de_DE' => 'Der Abschnitt der Datenschutzerklärung des CRM an Position 880: Kunden, Logbuch, Nachweise und das Cookie eines Geräts; ein Ausgangspunkt und keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [ 'ext' => [ 'pdo_sqlite' ] ],
	'requires'		=> [],
	// The one file of the database, which a backup carries as a copy the core makes (callbackBackup() of the core): the journal is DELETE, so that there is no -wal or -shm beside it
	'data'				=> [ '/data/crm.sqlite' ],
	'settings'		=> [],
];
