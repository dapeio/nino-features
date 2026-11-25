<?php
declare(strict_types=1);

// features/Crm/install/elements/privacy.php - the section of the privacy policy of the CRM, in German and English, written for it. Added to the type 'privacy' of Nino's Legal module by
// \Nino\Features::activate() through the unit's 'elements' key, which only ever adds: a section an editor changed or deleted is not touched again. There is no 'model' on purpose - the type is the module's.
//
// 'order' is the position among the module's sections, further features stand in 800-899; 880 is the CRM's. The text names what the code does: the data of a client the owner enters (company, name, address,
// postal code, city, country, email address, telephone, VAT id, reference of the buyer, notes), the number of the client, the logbook of the clients and of the projects (what happened, when, by whom: the
// account of the owner or system), the cookie
// crm_remember with which a device of the owner stays signed in (a random value, of which only the hash, the time, the end and the browser are kept) and the deletion of a client, which removes all of it.
return [
	'*' => [
		'crm' => [
			'order' => 880,
		],
	],
	'de_DE' => [
		'crm' => [
			'title' => 'Kundenverwaltung',
			'text'  => '<p>Zur Verwaltung unserer Kundenbeziehungen führen wir eine Kundendatei. Zu jedem Kunden speichern wir die Angaben, die wir bei der Zusammenarbeit von Dir bekommen oder selbst eintragen: Firma, Name, Anschrift, Telefonnummer, E-Mail-Adresse, Umsatzsteuer-Identifikationsnummer, eine Referenz des Käufers, Notizen und eine laufende Kundennummer. Zu Deinen Projekten halten wir fest, wann sie begonnen wurden, in welcher Phase sie sind, welche Aufgaben offen sind und welchen Kunden sie freigegeben sind. Ein Logbuch hält zu Kunden und Projekten fest, was wann geschah und wer es tat (das Konto des Inhabers oder das System). Alle diese Daten liegen in einer Datenbankdatei auf unserem Server und werden nicht an Dritte weitergegeben. Die verschlüsselten Sicherungen der Website enthalten diese Datei ebenfalls, und eine Löschung erreicht sie nicht. Die tägliche Sicherung entsteht, wenn sich an dem Tag jemand anmeldet; es bleiben die jüngsten drei, je nach Einstellung der Website bis zu dreißig, und ältere werden gelöscht. Eine von Hand ausgelöste Sicherung bleibt, bis eine neuere von Hand ausgelöste sie verdrängt (ebenfalls drei, je nach Einstellung bis zu dreißig); sie kann also länger bestehen.</p><p>Der Inhaber der Website bleibt auf einem Gerät angemeldet, wenn er das wünscht: Dafür setzt die Website das Cookie crm_remember mit einem Zufallswert; gespeichert werden davon nur der Hashwert, der Zeitpunkt, das Ende der Gültigkeit und der Browser des Geräts.</p><p>Rechtsgrundlage ist die Durchführung des Vertrags und vorvertraglicher Maßnahmen (Art. 6 Abs. 1 lit. b DSGVO) und unsere gesetzlichen Aufbewahrungspflichten (Art. 6 Abs. 1 lit. c DSGVO), für das Logbuch Dein berechtigtes Interesse und unseres (Art. 6 Abs. 1 lit. f DSGVO). Wir löschen die Daten eines Kunden, wenn keine Pflicht zur Aufbewahrung mehr besteht und Du es wünschst: Eine Löschung entfernt den Kunden mit allem, was an ihm hängt, auch das Logbuch, aus der Datenbank, nicht aus den schon gemachten Sicherungen. Fragen und Wünsche: #/project/company/contact/email#</p>',
		],
	],
	'en_US' => [
		'crm' => [
			'title' => 'Customer management',
			'text'  => '<p>To manage our customer relations we keep a file of clients. For each client we store what we receive from you in working together or enter ourselves: company, name, address, telephone number, email address, VAT id, a reference of the buyer, notes and a running client number. For your projects we keep when they began, which phase they are in, which tasks are open and which clients they are released to. A logbook keeps for clients and projects what happened when and who did it (the account of the owner, or the system). All of this data lies in one database file on our server and is not passed on to third parties. The encrypted backups of the website contain this file as well, and a deletion does not reach them. The daily backup is made when somebody signs in that day; the newest three are kept, up to thirty as the website is set up, and older ones are deleted. A backup made by hand stays until a newer one made by hand replaces it (also three, up to thirty as set up), so it may last longer.</p><p>The owner of the website stays signed in on a device when he wants to: for that the website sets the cookie crm_remember with a random value; of it only the hash, the time, the end of its validity and the browser of the device are stored.</p><p>The legal basis is the performance of the contract and of steps before it (Art. 6(1)(b) GDPR) and our legal duties of retention (Art. 6(1)(c) GDPR), for the logbook your legitimate interest and ours (Art. 6(1)(f) GDPR). We delete the data of a client when no duty of retention remains and you wish it: a deletion removes the client with everything that hangs on it, the logbook too, from the database, not from the backups that were already made. Questions and wishes: #/project/company/contact/email#</p>',
		],
	],
];
