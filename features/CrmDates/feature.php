<?php
// The class is not declared here: features/CrmDates/ can only serve \Nino\Modules\CrmDates
return [
	'key'					=> 'crm-dates',
	'name'				=> [ 'en_US' => 'CRM dates', 'de_DE' => 'CRM-Termine' ],
	'description'	=> [
		'en_US' => 'The dates of a client in the surface of the CRM: a list of what is coming and what is over, a form to make, change and delete a date, a tab on the card of the client and the next date on the overview. The dates are the elements of the feature Events, filed under the group of the client, and the owner sees and changes only those of a client.',
		'de_DE' => 'Die Termine eines Kunden in der Oberfläche des CRM: eine Liste dessen, was ansteht und was vorbei ist, ein Formular zum Anlegen, Ändern und Löschen eines Termins, ein Reiter an der Karte des Kunden und der nächste Termin auf der Übersicht. Die Termine sind die Elemente des Features Termine, unter der Gruppe des Kunden abgelegt, und der Inhaber sieht und ändert nur die eines Kunden.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [],
		'routes' => [],
		'panel' => [
			'/crm#dates' => [
				'en_US' => 'The page Dates of the surface of the CRM, with the list of the dates of all clients, the form of a new date and the date itself; the tab Dates stands on the card of every client.',
				'de_DE' => 'Die Seite Termine der Oberfläche des CRM, mit der Liste der Termine aller Kunden, dem Formular eines neuen Termins und dem Termin selbst; der Reiter Termine steht an der Karte jedes Kunden.',
			],
			'crm-dates/list, crm-dates/get, crm-dates/save, crm-dates/delete' => [
				'en_US' => 'The actions of the endpoint of the CRM that read, make, change and delete the dates of the clients, and no other element of the type Events.',
				'de_DE' => 'Die Aktionen des Endpunkts des CRM, die die Termine der Kunden lesen, anlegen, ändern und löschen, und kein anderes Element des Typs Termine.',
			],
			'crm-dates/adopt' => [
				'en_US' => 'Gives the dates that carry the group of a client and are nobody\'s to that client, or deletes them, one group at a time: deleting asks for the group typed, giving asks for a yes naming the client.',
				'de_DE' => 'Ordnet Termine, die die Gruppe eines Kunden tragen und niemandem gehören, diesem Kunden zu oder löscht sie, je Gruppe: Löschen verlangt die getippte Gruppe, Zuordnen ein Ja, das den Kunden nennt.',
			],
			'crm_dates (table)' => [
				'en_US' => 'Names the element and the client of every date in the database of the CRM, so that a number that is given to another client later inherits no date.',
				'de_DE' => 'Hält Element und Kunde jedes Termins in der Datenbank des CRM, damit eine Nummer, die später an einen anderen Kunden geht, keinen Termin erbt.',
			],
		],
		'callbacks' => [
			'/crm/overview' => [
				'en_US' => 'Answered with the next date of a client that is coming, for the tile of the overview.',
				'de_DE' => 'Wird mit dem nächsten anstehenden Termin eines Kunden beantwortet, für die Kachel der Übersicht.',
			],
			'/crm/client/holds' => [
				'en_US' => 'Answered with a hold that keeps a client from being deleted while the dates of the type Events cannot be read, so that none stays behind.',
				'de_DE' => 'Wird mit einem Halt beantwortet, der einen Kunden vor dem Löschen zurückhält, solange sich die Termine des Typs Termine nicht lesen lassen, damit keiner zurückbleibt.',
			],
			'/events/organizer' => [
				'en_US' => 'Answered with the name and the contact of the owner where the setting is on, which it is not at first, so that a calendar file says whom the dates are with.',
				'de_DE' => 'Wird mit dem Namen und dem Kontakt des Inhabers beantwortet, wo die Einstellung an ist, was sie zunächst nicht ist, damit eine Kalenderdatei sagt, bei wem die Termine sind.',
			],
		],
		'install' => [],
	],
	'category'		=> 'business',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [],
	'requires'		=> [ 'crm', 'events' ],
	// The dates are elements of the feature Events, which keeps them below /elements and its feed tokens in /data/events.php: nothing of this feature's own is kept
	'data'				=> [],  // the dates are elements of Events; the rows that name them are in the database of the CRM, which its backup carries
	'assets'			=> [],
	'settings'		=> [
		'feedContact' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Contact in the calendar file', 'de_DE' => 'Kontakt in der Kalenderdatei' ],
			'hint'		=> [
				'en_US' => 'Whether the calendar file of the public dates names you and your contact - the name, email, phone and address of the seller settings - as the organizer. The file is public: on, every visitor reads them. Off, it carries what Events says alone.',
				'de_DE' => 'Ob die Kalenderdatei der öffentlichen Termine Dich und Deinen Kontakt nennt - Name, E-Mail, Telefon und Anschrift aus den Einstellungen des Rechnungsstellers - als Veranstalter. Die Datei ist öffentlich: An, liest sie jeder Besucher. Aus, trägt sie nur, was Termine selbst sagt.',
			],
			'default'	=> false,
		],
	],
];
