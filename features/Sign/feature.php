<?php
// The class is not declared here: features/Sign/ can only serve \Nino\Modules\Sign
return [
	'key'					=> 'sign',
	'name'				=> [ 'en_US' => 'Signature', 'de_DE' => 'Unterschrift' ],
	'description'	=> [
		'en_US' => 'A member signs a document of the PDF feature on a page of the website: the owner asks in the panel or from code, the member gets a link by mail, reads the document, agrees and signs by drawing or by typing a name. The document is made again with the signature and a last page that records who signed, when, from where and which document by its hash; both PDFs are kept. A simple electronic signature, no more.',
		'de_DE' => 'Ein Mitglied unterschreibt ein Dokument des Features PDF auf einer Seite der Website: Der Inhaber fragt im Panel oder aus dem Code an, das Mitglied bekommt einen Link per E-Mail, liest das Dokument, stimmt zu und unterschreibt durch Zeichnen oder Tippen seines Namens. Das Dokument wird mit der Unterschrift und einer letzten Seite neu erzeugt, die festhält, wer wann von wo und welches Dokument anhand seines Hashs unterschrieben hat; beide PDFs werden aufbewahrt. Eine einfache elektronische Signatur, nicht mehr.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[sign-form]' => [
				'en_US' => 'The form of the signing page: the document to read, the sentence to agree to, a pad to draw on and a field to type a name, and the buttons Sign and Decline.',
				'de_DE' => 'Das Formular der Unterschriftsseite: das Dokument zum Lesen, der Satz zum Zustimmen, ein Feld zum Zeichnen und eines zum Tippen des Namens, und die Knöpfe Unterschreiben und Ablehnen.',
			],
			'[sign-list]' => [
				'en_US' => 'The requests made of the member who is signed in, the newest first, with a link to each page and to the signed document; for a visitor it draws nothing.',
				'de_DE' => 'Die Anfragen an das angemeldete Mitglied, die neueste zuerst, mit Link zur Seite und zum unterschriebenen Dokument; für einen Besucher zeichnet sie nichts.',
			],
			'[sign-list open="0"]' => [
				'en_US' => 'With open off the signed requests are listed as well; on, the default, the list is what still waits or was declined, taken back or run out.',
				'de_DE' => 'Ist open aus, stehen auch die unterschriebenen Anfragen in der Liste; an, der Standard, zeigt sie, was noch wartet oder abgelehnt, zurückgezogen oder abgelaufen ist.',
			],
			'[sign-form sample]' => [
				'en_US' => 'Draws the form, or the list with sample, without any request behind it and with its buttons off, for a tool that shows what the knobs of the group do.',
				'de_DE' => 'Zeichnet das Formular, oder die Liste mit sample, ohne eine Anfrage dahinter und mit abgeschalteten Knöpfen, für ein Werkzeug, das zeigt, was die Regler der Gruppe tun.',
			],
		],
		'markup' => [
			'.sign-form, .sign-pad, .sign-list' => [
				'en_US' => 'The classes of the markup, with -title, -intro, -input, -button and -state for the parts of the form and -row, -title, -state and -date for the list.',
				'de_DE' => 'Die Klassen des Markups, mit -title, -intro, -input, -button und -state für die Teile des Formulars und -row, -title, -state und -date für die Liste.',
			],
			'data-sign-pad, data-sign-clear, data-sign-name' => [
				'en_US' => 'What sign.js looks for in the form; the typed name is a way to sign as good as the pad and works without the script, with the keyboard alone.',
				'de_DE' => 'Wonach sign.js im Formular sucht; der getippte Name ist ein Weg zur Unterschrift, so gut wie das Feld, und geht ohne das Skript, allein mit der Tastatur.',
			],
			'--sign-form-space, --sign-form-radius' => [
				'en_US' => 'The tokens of the standard part: the space inside the form and the corners of form, field and button, which the knobs of the group turn.',
				'de_DE' => 'Die Tokens des Standard-Teils: der Innenabstand des Formulars und die Ecken von Formular, Feld und Knopf, die die Regler der Gruppe drehen.',
			],
			'templates/record.tpl' => [
				'en_US' => 'The record of a signing, the page appended to the signed PDF: who, when, from which address and browser, the hash of the shown document, how, and the sentence agreed to.',
				'de_DE' => 'Der Beleg einer Unterzeichnung, die Seite am Ende des unterschriebenen PDF: wer, wann, von welcher Adresse und mit welchem Browser, der Hash des gezeigten Dokuments, wie, und der Satz.',
			],
		],
		'routes' => [
			'GET /.sign?t=<token>' => [
				'en_US' => 'The link of the mail: it only marks the request as viewed and answers the form, the state of a closed request, or a 404 for a token nobody has or a member signed in as another opens.',
				'de_DE' => 'Der Link der Mail: Er markiert die Anfrage als angesehen und zeigt Formular oder Zustand; 404 für einen unbekannten Token und für ein anderes angemeldetes Mitglied, Besucher kommen mit Token hinein.',
			],
			'GET /.sign?r=<id>' => [
				'en_US' => 'The same page for the signed-in member the request is of; an id nobody has, another member\'s id and a visitor\'s ask are all the same 404.',
				'de_DE' => 'Dieselbe Seite für das angemeldete Mitglied, dessen Anfrage es ist; eine unbekannte Id, die eines anderen Mitglieds und die Anfrage eines Besuchers sind alle dieselbe 404.',
			],
			'POST /.sign' => [
				'en_US' => 'Signs or declines: the post is counted per visitor and hour, the box has to be ticked, the document is made again and has to be the shown one, and the answer is a redirect.',
				'de_DE' => 'Unterschreibt oder lehnt ab: Die Absendung wird je Besucher und Stunde gezählt, das Kästchen muss angehakt sein, das Dokument muss neu erzeugt das gezeigte sein, und die Antwort ist eine Umleitung.',
			],
			'GET /.sign/pdf?t=|r=&kind=shown|signed' => [
				'en_US' => 'A PDF of a request, shown in the browser with nosniff and a sandbox policy, to a visitor with the token, the member it is of and the owner; all others get the same 404.',
				'de_DE' => 'Ein PDF einer Anfrage, im Browser mit nosniff und Sandbox-Richtlinie angezeigt, für Besucher mit dem Token, das Mitglied und den Betreiber; alle anderen bekommen dieselbe 404.',
			],
		],
		'panel' => [
			'Signatures' => [
				'en_US' => 'The requests as a table with search and state; each opens in a record with the proof of the signing, the PDFs, Remind, Cancel and Delete, and chosen rows are handled together.',
				'de_DE' => 'Die Anfragen als Tabelle mit Suche und Zustand; jede öffnet sich in einem Eintrag mit Beleg, PDFs, Erinnern, Zurückziehen und Löschen, gewählte Zeilen bearbeitet man gemeinsam.',
			],
			'Ask for a signature' => [
				'en_US' => 'A view of its own at #sign/sign/@new: the member, a template and its free texts, a title, and whether the member is mailed or the link is shown once.',
				'de_DE' => 'Eine eigene Ansicht unter #sign/sign/@new: das Mitglied, eine Vorlage mit ihren Freitexten, ein Titel, und ob das Mitglied eine Mail bekommt oder der Link einmal gezeigt wird.',
			],
			'Backup' => [
				'en_US' => 'The daily backup carries /data/sign whole, and a signed document weighs about 400 KB with a logo like a photo and a few KB with a flat logo.',
				'de_DE' => 'Die tägliche Sicherung nimmt /data/sign ganz mit, und ein unterschriebenes Dokument wiegt mit fotoähnlichem Logo etwa 400 KB, mit flachem Logo wenige KB.',
			],
			'Backup size' => [
				'en_US' => 'The daily backup carries about 52 MB in all at 128 MB of memory: some 65 signed requests with a photo-like logo, each with two PDFs, fill it - then the PDFs need a server backup.',
				'de_DE' => 'Die tägliche Sicherung trägt bei 128 MB Speicher etwa 52 MB: rund 65 unterschriebene Anfragen mit fotoähnlichem Logo, je zwei PDFs, füllen sie - dann brauchen die PDFs eine Serversicherung.',
			],
		],
		'callbacks' => [
			'/sign/signed' => [
				'en_US' => 'Fired when a member has signed, with the request as the argument, for the features that build on it.',
				'de_DE' => 'Wird ausgelöst, wenn ein Mitglied unterschrieben hat, mit der Anfrage als Argument, für die Features, die darauf aufbauen.',
			],
			'/sign/declined' => [
				'en_US' => 'Fired when a member declined, with the same request as the argument.',
				'de_DE' => 'Wird ausgelöst, wenn ein Mitglied abgelehnt hat, mit derselben Anfrage als Argument.',
			],
			'/members/removed' => [
				'en_US' => 'Heard: of a deleted account every request that is not signed is deleted with its files; a signed one stays untouched, as the owner\'s record, until the owner deletes it in the panel.',
				'de_DE' => 'Gehört: Von einem gelöschten Konto wird jede Anfrage, die nicht unterschrieben ist, samt Dateien gelöscht; eine unterschriebene bleibt unberührt als Beleg des Betreibers, bis er sie im Panel löscht.',
			],
			'/nino/http/output' => [
				'en_US' => 'The mails a signing or a decline sets off go out after the answer, or as the request ends where the server cannot let the client go before.',
				'de_DE' => 'Die Mails, die eine Unterschrift oder Ablehnung auslöst, gehen nach der Antwort hinaus, sonst mit dem Ende der Anfrage, wo der Server den Client nicht vorher freigeben kann.',
			],
			'/nino/admin/restore' => [
				'en_US' => 'A restore drops the requests of the index whose shown.pdf is neither in the backup nor on the disk, so that nobody is offered a document that is not there.',
				'de_DE' => 'Eine Wiederherstellung lässt die Anfragen des Index weg, deren shown.pdf weder in der Sicherung noch auf der Platte liegt, damit niemandem ein fehlendes Dokument angeboten wird.',
			],
		],
		'install' => [
			'templates/page-sign.tpl' => [
				'en_US' => 'The signing page: the header and footer of the site, a title and [sign-form]; the route /.sign renders it in place for the link of a mail.',
				'de_DE' => 'Die Unterschriftsseite: Kopf und Fuß der Website, ein Titel und [sign-form]; die Route /.sign rendert sie an Ort und Stelle für den Link einer Mail.',
			],
			'blocks/sign-list/' => [
				'en_US' => 'A block: a headline, a line of text and the list of the documents a signed-in member is to sign.',
				'de_DE' => 'Ein Block: eine Überschrift, eine Zeile Text und die Liste der Dokumente, die ein angemeldetes Mitglied unterschreiben soll.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the page, the form, the list, the record and the three mails into the Text panel, with the sentence the member agrees to.',
				'de_DE' => 'Bringt die Worte der Seite, des Formulars, der Liste, des Belegs und der drei Mails ins Panel Texte, mit dem Satz, dem das Mitglied zustimmt.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; ein Ausgangspunkt, keine Rechtsberatung.',
			],
			'/data/sign' => [
				'en_US' => 'The index requests.php and a directory for each request with shown.pdf, signature.png and signed.pdf, which a backup carries.',
				'de_DE' => 'Der Index requests.php und je Anfrage ein Verzeichnis mit shown.pdf, signature.png und signed.pdf, das eine Sicherung mitnimmt.',
			],
		],
	],
	'category'		=> 'communication',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [ 'ext' => [ 'gd' ] ],
	'requires'		=> [ 'basis', 'pdf', 'members' ],
	// The index and one directory for each request with its PDFs and the picture of the signature. The counter of the posts lives beside them (sign-tries.php) and stays out of a backup: it is an hour long
	'data'				=> [ '/data/sign' ],
	// The script of the pad: the look is the standard part of the group
	'assets'			=> [ '/features/Sign/assets/sign.js' ],
	'settings'		=> [
		'days' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Link valid for', 'de_DE' => 'Link gültig für' ],
			'hint'		=> [
				'en_US' => 'How long the link of a request can be used, in days; the member can read and sign until then, and a reminder gives it that time again. The section of the privacy policy names fourteen days - change it with this.',
				'de_DE' => 'Wie lange der Link einer Anfrage benutzt werden kann, in Tagen; bis dahin kann das Mitglied lesen und unterschreiben, und eine Erinnerung gibt ihm diese Zeit noch einmal. Der Abschnitt der Datenschutzerklärung nennt vierzehn Tage - ändere ihn mit.',
			],
			'min'			=> 1,
			'max'			=> 90,
			'unit'		=> 'days',
			'default'	=> 14,
		],
		'notifyOwner' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Mail the owner', 'de_DE' => 'Betreiber benachrichtigen' ],
			'hint'		=> [
				'en_US' => 'Mail the contact address of the website when a member signs or declines - one mail for each.',
				'de_DE' => 'Der Kontaktadresse der Website eine Mail schicken, wenn ein Mitglied unterschreibt oder ablehnt - eine Mail für jedes.',
			],
			'default'	=> true,
		],
		'attempts' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Posts', 'de_DE' => 'Absendungen' ],
			'hint'		=> [
				'en_US' => 'Posts of the signing page one visitor may send per hour - signing and declining, the refused ones too - before the page refuses for the rest of that hour. The address of the visitor is counted as a hash.',
				'de_DE' => 'Absendungen der Unterschriftsseite, die ein Besucher pro Stunde senden darf - Unterschreiben und Ablehnen, auch die abgewiesenen -, bevor die Seite für den Rest dieser Stunde verweigert. Die Adresse des Besuchers wird als Hash gezählt.',
			],
			'min'			=> 1,
			'max'			=> 100,
			'unit'		=> 'per-hour',
			'default'	=> 20,
		],
		'tags' => [
			'type'		=> 'lines',
			'label'		=> [ 'en_US' => 'Tags of the templates', 'de_DE' => 'Tags der Vorlagen' ],
			'hint'		=> [
				'en_US' => 'The tags of the PDF feature whose templates the panel offers to ask a signature for, one per line - lower case letters, digits and hyphens. Left empty, the panel offers every template that has a field for a member. A template of another tag can still be asked for from code, with Sign::request().',
				'de_DE' => 'Die Tags des Features PDF, deren Vorlagen das Panel für eine Unterschriftsanfrage anbietet, ein Tag pro Zeile - Kleinbuchstaben, Ziffern und Bindestriche. Leer gelassen, bietet das Panel jede Vorlage mit einem Feld für ein Mitglied an. Eine Vorlage eines anderen Tags lässt sich weiter aus dem Code anfragen, mit Sign::request().',
			],
			'default'	=> [],
		],
	],
];
