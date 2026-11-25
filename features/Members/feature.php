<?php
// The class is not declared here: features/Members/ can only serve \Nino\Modules\Members
return [
	'key'					=> 'members',
	'name'				=> [ 'en_US' => 'Members', 'de_DE' => 'Mitglieder' ],
	'description'	=> [
		'en_US' => 'Members gives the visitors of the website accounts without passwords: a member signs in with a code or a link that is sent by mail. Pages can be put behind the sign-in, and a panel manages the accounts. The sign-in form, the sign-out button and the name of the member are components, and the pages of the sign-in are made of components.',
		'de_DE' => 'Mitglieder gibt den Besuchern der Website Konten ohne Passwörter: Ein Mitglied meldet sich mit einem Code oder einem Link an, der per E-Mail kommt. Seiten lassen sich hinter die Anmeldung legen, und ein Panel verwaltet die Konten. Das Anmeldeformular, der Abmelde-Knopf und der Name des Mitglieds sind Komponenten, und die Seiten der Anmeldung bestehen aus Komponenten.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[members-login]' => [
				'en_US' => 'The sign-in form in two steps, the address and then the six digits of the code, or the name of the member and a button to sign out for a member who is signed in.',
				'de_DE' => 'Das Anmeldeformular in zwei Schritten, die Adresse und dann die sechs Ziffern des Codes, oder für ein angemeldetes Mitglied sein Name und ein Knopf zum Abmelden.',
			],
			'[members-login inline]' => [
				'en_US' => 'The field and the button of the first step in one line, with the label for screen readers only; the second step is never in one line.',
				'de_DE' => 'Feld und Knopf des ersten Schritts in einer Zeile, die Beschriftung nur für Screenreader; der zweite Schritt steht nie in einer Zeile.',
			],
			'[members-logout]' => [
				'en_US' => 'A button, in a form with the token of the session, that signs the member out; it draws nothing for a visitor, so a header can carry it for everybody.',
				'de_DE' => 'Ein Knopf in einem Formular mit dem Token der Sitzung, der das Mitglied abmeldet; für einen Besucher zeichnet er nichts, ein Kopfbereich kann ihn also für alle tragen.',
			],
			'[members-name]' => [
				'en_US' => 'The name of the member who is signed in, or the address where the account has no name; it draws nothing for a visitor.',
				'de_DE' => 'Der Name des angemeldeten Mitglieds, oder die Adresse, wo das Konto keinen Namen hat; für einen Besucher zeichnet er nichts.',
			],
			'[members-confirm]' => [
				'en_US' => 'The button of the page a link of the mail opens: the name of the account the link signs in and one button that does it; on any other page it draws nothing.',
				'de_DE' => 'Der Knopf der Seite, die ein Link der Mail öffnet: der Name des Kontos, das der Link anmeldet, und ein Knopf, der es tut; auf jeder anderen Seite zeichnet er nichts.',
			],
		],
		'markup' => [
			'.members-login, .members-confirm' => [
				'en_US' => 'The classes of the forms, with -label, -input, -button, -state, -intro, -email and -again for their parts and --code, --signed and --inline for their shapes.',
				'de_DE' => 'Die Klassen der Formulare, mit -label, -input, -button, -state, -intro, -email und -again für ihre Teile und --code, --signed und --inline für ihre Formen.',
			],
			'--members-login-space, --members-login-radius' => [
				'en_US' => 'The tokens of the standard part: the space inside the forms and the corners of form, field and button, which the knobs of the group turn.',
				'de_DE' => 'Die Tokens des Standard-Teils: der Innenabstand der Formulare und die Ecken von Formular, Feld und Knopf, die die Regler der Gruppe drehen.',
			],
			'[[message]] (mail-invite.tpl, mail-send.tpl)' => [
				'en_US' => 'The place of the message of the owner in the mail of an invitation or of a link the owner sends; without a message it is empty, and the message is written as text.',
				'de_DE' => 'Der Platz der Nachricht des Inhabers in der Mail einer Einladung oder eines Links, den der Inhaber schickt; ohne Nachricht bleibt er leer, und die Nachricht wird als Text geschrieben.',
			],
			'[mail-head]' => [
				'en_US' => 'The first line of every mail, the place of a band that a module of the project may fill; where none does, it is empty.',
				'de_DE' => 'Die erste Zeile jeder Mail, der Platz eines Bandes, den ein Modul des Projekts füllen darf; wo keines es tut, bleibt er leer.',
			],
		],
		'routes' => [
			'POST /.members/login' => [
				'en_US' => 'Asks for a code: it counts the sender and the address, looks at the outbox, queues the mail for every address alike - to nobody where there is no account - and answers all of them with one redirect.',
				'de_DE' => 'Fordert einen Code an: Sie zählt Absender und Adresse, prüft den Postausgang, reiht die Mail für jede Adresse gleich ein – an niemanden ohne Konto – und antwortet allen mit einer Umleitung.',
			],
			'POST /.members/login, the server' => [
				'en_US' => 'The form hides which addresses have an account only where the server lets the client go before the work after the answer (php-fpm, LiteSpeed) and has a free worker; the panel says where.',
				'de_DE' => 'Das Formular verbirgt die Konten nur, wo der Server den Client vor der Arbeit nach der Antwort entlässt (php-fpm, LiteSpeed) und einen freien Worker hat; das Panel sagt, wo.',
			],
			'POST /.members/login, the outbox' => [
				'en_US' => 'The mail of a code waits in the outbox, readable, and lapses with the code: after its minutes nobody delivers it, its text is gone once a run takes it out, and where none comes it lies there.',
				'de_DE' => 'Die Mail eines Codes liegt lesbar im Postausgang und verfällt mit dem Code: Nach seinen Minuten stellt sie niemand zu, ihr Text ist weg, sobald ein Lauf sie wegräumt, und wo keiner kommt, liegt sie.',
			],
			'POST /.members/login, what stays open' => [
				'en_US' => 'A failing mail transport shows in the time before the answer even where the server lets go first, and where it does not the time of the answer and the next request tell an address with an account.',
				'de_DE' => 'Ein ausfallender Mail-Transport zeigt sich in der Zeit vor der Antwort auch dort, wo der Server zuerst entlässt, und sonst verraten Antwortzeit und nächste Anfrage eine Adresse mit Konto.',
			],
			'POST /.members/login, after a refusal' => [
				'en_US' => 'Where a transport refused a mail that then lapsed, the run that takes it out differs for an address with an account: file operations, a log line, a failed count; that lies with the kernel.',
				'de_DE' => 'Hat ein Transport eine Mail abgelehnt und verfällt sie, unterscheidet sich der Lauf, der sie wegräumt, für eine Adresse mit Konto: Dateioperationen, Logzeile, Fehlerzähler; das liegt beim Kernel.',
			],
			'POST /.members/code' => [
				'en_US' => 'Takes the code: a right one signs the member in and leads on, a wrong one counts as a try, and the fifth ends the request.',
				'de_DE' => 'Nimmt den Code: Ein richtiger meldet das Mitglied an und leitet weiter, ein falscher zählt als Versuch, und der fünfte beendet die Anforderung.',
			],
			'/.members/login?t=<token>' => [
				'en_US' => 'The link of an invitation or of a link the owner sends: it changes nothing and opens a page with one button, or the sign-in page for a link that is not valid.',
				'de_DE' => 'Der Link einer Einladung oder eines Links, den der Inhaber schickt: Er ändert nichts und öffnet eine Seite mit einem Knopf, oder für einen ungültigen Link die Anmeldeseite.',
			],
			'POST /.members/confirm' => [
				'en_US' => 'The button of that page: the token is spent on the spot, the member is signed in and sent to the page that asked for the sign-in or to the start page.',
				'de_DE' => 'Der Knopf dieser Seite: Der Token wird sofort verbraucht, das Mitglied angemeldet und auf die Seite geschickt, die die Anmeldung verlangt hat, oder auf die Startseite.',
			],
			'POST /.members/logout' => [
				'en_US' => 'Signs the member out and the device with it, and goes to the start page; a GET is answered with 405 and signs nobody out.',
				'de_DE' => 'Meldet das Mitglied und sein Gerät ab und geht zur Startseite; ein GET wird mit 405 beantwortet und meldet niemanden ab.',
			],
		],
		'panel' => [
			'Members' => [
				'en_US' => 'The accounts as a table with search, group and state; each opens in a record with name, group, state, a sign-in link, a sign-out everywhere and the deletion; chosen rows are deleted together.',
				'de_DE' => 'Die Konten als Tabelle mit Suche, Gruppe und Zustand; jedes öffnet sich in einem Eintrag mit Name, Gruppe, Zustand, Anmelde-Link, Abmelden überall und Löschen; gewählte Zeilen löscht man gemeinsam.',
			],
			'Notice about the server' => [
				'en_US' => 'A notice over the table where the server does not let the client go before the work after the answer: there the sign-in form cannot hide which addresses have an account.',
				'de_DE' => 'Ein Hinweis über der Tabelle, wo der Server den Client nicht vor der Arbeit nach der Antwort entlässt: Dort kann das Anmeldeformular nicht verbergen, welche Adressen ein Konto haben.',
			],
			'Notice about the brakes' => [
				'en_US' => 'Over the table: where the promise of the sign-in form holds and where it ends, that no sign-in mail is queued while mails wait, and a setting above the mail cap.',
				'de_DE' => 'Über der Tabelle: wo das Versprechen des Anmeldeformulars gilt und wo es endet, dass keine Anmelde-Mail eingereiht wird, solange Mails warten, und eine Einstellung über der Mail-Grenze.',
			],
			'Sign everybody out' => [
				'en_US' => 'A button under the table signs every member out at once, the devices that stay signed in among them.',
				'de_DE' => 'Ein Knopf unter der Tabelle meldet alle Mitglieder auf einmal ab, die Geräte, die angemeldet bleiben, eingeschlossen.',
			],
		],
		'callbacks' => [
			'/nino/http/response' => [
				'en_US' => 'The gate: a protected path, at every address and in every spelling, is answered with the sign-in page and the status 401 for a visitor who is not signed in.',
				'de_DE' => 'Das Tor: Ein geschützter Pfad wird an jeder Adresse und in jeder Schreibweise mit der Anmeldeseite und dem Status 401 beantwortet, wenn der Besucher nicht angemeldet ist.',
			],
			'/nino/http/response + Protected area' => [
				'en_US' => 'Where Protected area shuts the same path both gates must be passed, and a visitor who passed neither sees the form of the module that stands last in /nino/modules.',
				'de_DE' => 'Wo Geschützter Bereich denselben Pfad schließt, müssen beide Tore passiert werden, und wer keines passiert hat, sieht das Formular des Moduls, das in /nino/modules zuletzt steht.',
			],
			'/seo/exclude' => [
				'en_US' => 'Tells the SEO feature, where it is installed, to keep the protected paths out of the sitemap and out of llms.txt.',
				'de_DE' => 'Sagt dem Feature SEO, wo es installiert ist, die geschützten Pfade aus der Sitemap und aus llms.txt herauszuhalten.',
			],
			'/members/login' => [
				'en_US' => 'Fired when a member has signed in, by a code, a link or the cookie of a device that stays signed in, with the account as the argument.',
				'de_DE' => 'Wird ausgelöst, wenn sich ein Mitglied angemeldet hat, mit einem Code, einem Link oder dem Cookie eines angemeldeten Geräts, mit dem Konto als Argument.',
			],
			'/members/logout' => [
				'en_US' => 'Fired when a signed-in member signs out, with the same account as the argument.',
				'de_DE' => 'Wird ausgelöst, wenn sich ein angemeldetes Mitglied abmeldet, mit demselben Konto als Argument.',
			],
			'/members/start' => [
				'en_US' => 'Fired when a sign-in is about to lead on, with the account, the target and to, which a callback may set to a path of this site.',
				'de_DE' => 'Wird ausgelöst, wenn eine Anmeldung weiterleiten will, mit dem Konto, dem Ziel und to, das ein Callback auf einen Pfad dieser Website setzen darf.',
			],
			'/members/login-form' => [
				'en_US' => 'Fired when the form that asks for a code is drawn, with return and note, which a callback sets to finished markup: it stands as it is and is read by nobody, so a fill or call in it stays text.',
				'de_DE' => 'Wird beim Zeichnen des Code-Formulars ausgelöst, mit return und note, das ein Callback auf fertiges Markup setzt: Es steht, wie es ist, und wird nicht gelesen.',
			],
			'/members/open' => [
				'en_US' => 'Asked for the paths a feature opens under a protected prefix, such as the manifest of a web app; a callback adds the uris of its routes to the list.',
				'de_DE' => 'Fragt nach den Pfaden, die ein Feature unter einem geschützten Präfix öffnet, etwa das Manifest einer Web-App; ein Callback fügt der Liste die Uris seiner Routen hinzu.',
			],
			'/members/changed' => [
				'en_US' => 'Fired when the address of an account was changed, with the account, the old address and who changed it, after every link, code and device of the account is gone.',
				'de_DE' => 'Wird ausgelöst, wenn die Adresse eines Kontos geändert wurde, mit dem Konto, der alten Adresse und wer sie änderte, nachdem jeder Link, Code und jedes Gerät des Kontos verworfen ist.',
			],
			'/members/removed' => [
				'en_US' => 'Fired for every account that was deleted, after the write, with its id, address and group, so that others clear away what they keep of it.',
				'de_DE' => 'Wird für jedes gelöschte Konto nach dem Schreiben ausgelöst, mit Kennung, Adresse und Gruppe, damit andere wegräumen, was sie von ihm halten.',
			],
			'/members/language' => [
				'en_US' => 'Asked for the language a mail to an account is written in; a callback may name a locale of the site.',
				'de_DE' => 'Fragt nach der Sprache, in der eine Mail an ein Konto geschrieben wird; ein Callback darf eine Sprache der Website nennen.',
			],
		],
		'install' => [
			'templates/page-members-login.tpl' => [
				'en_US' => 'The sign-in page the gate answers with, made of components and the header and footer of the site.',
				'de_DE' => 'Die Anmeldeseite, mit der das Tor antwortet, aus Komponenten und dem Kopf- und Fußbereich der Website.',
			],
			'templates/page-members-confirm.tpl' => [
				'en_US' => 'The page a link of the mail opens, with the one button that signs the member in; nothing is used up before it is pressed.',
				'de_DE' => 'Die Seite, die ein Link der Mail öffnet, mit dem einen Knopf, der das Mitglied anmeldet; vor dem Drücken wird nichts verbraucht.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the words of the pages, the forms and the six mails into the Text panel, where the project keeps them.',
				'de_DE' => 'Bringt die Worte der Seiten, der Formulare und der sechs Mails ins Panel Texte, wo das Projekt sie pflegt.',
			],
			'blocks/members-login/' => [
				'en_US' => 'A block: a headline, a line of text and the sign-in form.',
				'de_DE' => 'Ein Block: eine Überschrift, eine Zeile Text und das Anmeldeformular.',
			],
			'/data/members.php' => [
				'en_US' => 'The accounts with their groups and the tokens of the devices that stay signed in, which a backup carries.',
				'de_DE' => 'Die Konten mit ihren Gruppen und den Tokens der Geräte, die angemeldet bleiben, die eine Sicherung mitnimmt.',
			],
			'/data/members-tokens.php' => [
				'en_US' => 'The links and codes that are out, kept as hashes, which a backup carries.',
				'de_DE' => 'Die Links und Codes, die unterwegs sind, als Hashwerte, die eine Sicherung mitnimmt.',
			],
			'/data/members-session.php' => [
				'en_US' => 'The session epoch that signing everybody out replaces, which a backup carries.',
				'de_DE' => 'Die Sitzungsepoche, die das Abmelden aller ersetzt und die eine Sicherung mitnimmt.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module, a starting point and no legal advice; a project that has the section copies changed words by hand.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit, ein Ausgangspunkt ohne Rechtsberatung; ein Projekt, das den Abschnitt hat, übernimmt geänderte Worte von Hand.',
			],
		],
	],
	'category'		=> 'security',
	// The kernel whose outbox takes the field 'expires' of a mail (\Nino\Mail::queue()): the mail of a code lapses with the code only there. A kernel without the field - the constraint cannot tell the two
	// apart - ignores it, and the mail then waits in the outbox as any mail does, up to two days; sign-in works the same and nothing tells a member from a stranger, but the section of the privacy policy is untrue for it
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'requires'		=> [ 'basis' ],
	// The accounts, the links that are out and the session epoch - what a backup carries. A restore replaces the three whole, and an epoch
	// from the backup ends every session of the day, which is right. The counter of the requests for a code is the kernel's (the folder .counters/members-tries) and stays
	// out of a backup: it is an hour long
	'data'				=> [ '/data/members.php', '/data/members-tokens.php', '/data/members-session.php' ],
	'settings'		=> [
		'paths' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'Protected paths', 'de_DE' => 'Geschützte Pfade' ],
			'hint'	=> [
				'en_US' => 'One uri prefix per line, eg. /club - protects that page and everything below it, in every spelling of the address: only a signed-in member sees it, everybody else is shown the sign-in page. A page that has a route for each language is shut at all of them when its uri is the one that is written here. Leave empty to protect nothing.',
				'de_DE' => 'Ein Uri-Präfix pro Zeile, zB /club - schützt diese Seite und alles darunter, in jeder Schreibweise der Adresse: Nur ein angemeldetes Mitglied sieht sie, allen anderen wird die Anmeldeseite gezeigt. Eine Seite mit einer Route je Sprache ist an allen zu, wenn hier ihre Uri steht. Leer lassen, um nichts zu schützen.',
			],
		],
		'start' => [
			'type'			=> 'string',
			'label'			=> [ 'en_US' => 'Start page', 'de_DE' => 'Startseite' ],
			'hint'			=> [
				'en_US' => 'The page a member is sent to after signing in when no page asked for the sign-in (a link requested from a public page), and after signing out. A path of this site, eg. /club.',
				'de_DE' => 'Die Seite, auf die ein Mitglied nach der Anmeldung geschickt wird, wenn keine Seite die Anmeldung verlangt hat (ein Link, der von einer öffentlichen Seite angefordert wurde), und nach dem Abmelden. Ein Pfad dieser Website, zB /club.',
			],
			'default'		=> '/',
			'maxlength'	=> 200,
			'pattern'		=> '~^/(?![/\\\\])(?![^/]*:)[^\s\x00-\x1f\x7f?#]*$~D',
		],
		'linkMinutes' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Code valid for', 'de_DE' => 'Code gültig für' ],
			'hint'		=> [
				'en_US' => 'How long the code a member asks for can be used, once - the code to sign in and the code for a new sign-in address alike. The link the owner sends from the panel is valid just as long. The mail that carries a code lapses after the same minutes. The section of the privacy policy names fifteen minutes - change it with this.',
				'de_DE' => 'Wie lange der Code, den ein Mitglied anfordert, benutzt werden kann, einmal – der Code zum Anmelden ebenso wie der für eine neue Anmeldeadresse. Der Link, den der Inhaber im Panel schickt, gilt ebenso lange. Die Mail, die einen Code trägt, verfällt nach denselben Minuten. Der Abschnitt der Datenschutzerklärung nennt fünfzehn Minuten – ändere ihn mit.',
			],
			'min'			=> 5,
			'max'			=> 1440,
			'unit'		=> 'minutes',
			'default'	=> 15,
		],
		'inviteDays' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Invitation valid for', 'de_DE' => 'Einladung gültig für' ],
			'hint'		=> [
				'en_US' => 'How long the link of an invitation can be used, once. The section of the privacy policy names seven days - change it with this.',
				'de_DE' => 'Wie lange der Link einer Einladung benutzt werden kann, einmal. Der Abschnitt der Datenschutzerklärung nennt sieben Tage – ändere ihn mit.',
			],
			'min'			=> 1,
			'max'			=> 30,
			'unit'		=> 'days',
			'default'	=> 7,
		],
		'attempts' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Requests', 'de_DE' => 'Anforderungen' ],
			'hint'		=> [
				'en_US' => 'Codes one visitor may ask for per hour before the form refuses for the rest of that hour - for a sign-in and for a new sign-in address alike; at most 10. The mail cap of the kernel per visitor (/nino/mail/cap, 5 by default) acts first where it is lower, and these mails share it with the mails of the other features. Beside it, one address gets at most 3 codes per hour, whoever asks, and no sign-in mail is queued while 30 mails wait in the outbox.',
				'de_DE' => 'Codes, die ein Besucher pro Stunde anfordern darf, bevor das Formular für den Rest der Stunde verweigert – zum Anmelden ebenso wie für eine neue Anmeldeadresse; höchstens 10. Die Mail-Grenze des Kernels je Besucher (/nino/mail/cap, standardmäßig 5) greift zuerst, wo sie niedriger ist, und diese Mails teilen sie mit den Mails der anderen Features. Daneben bekommt eine Adresse höchstens 3 Codes pro Stunde, wer auch fragt, und keine Anmelde-Mail wird eingereiht, solange 30 Mails im Postausgang warten.',
			],
			'min'			=> 1,
			'max'			=> 10,
			'unit'		=> 'per-hour',
			'default'	=> 5,
		],
		'rememberDays' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Stay signed in for', 'de_DE' => 'Angemeldet bleiben für' ],
			'hint'		=> [
				'en_US' => 'How long a device stays signed in after a member ticked the box of the code form - counted from its last visit, the cookie nino_remember being renewed with every one. Changing the email address of an account, switching it off or signing it out of all devices ends every one of its devices at once.',
				'de_DE' => 'Wie lange ein Gerät angemeldet bleibt, nachdem ein Mitglied das Kästchen des Code-Formulars angekreuzt hat – gezählt ab dem letzten Besuch, das Cookie nino_remember wird bei jedem erneuert. Die Änderung der E-Mail-Adresse eines Kontos, sein Abschalten und das Abmelden von allen Geräten beenden alle seine Geräte auf einmal.',
			],
			'min'			=> 1,
			'max'			=> 365,
			'unit'		=> 'days',
			'default'	=> 90,
		],
	],
];
