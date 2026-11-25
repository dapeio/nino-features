<?php
declare(strict_types=1);

// Die Worte des Panels, eine Datei je Oberflächensprache (siehe Admin::text()): Sie werden in die Fills der Werkbank gemischt, solange das
// Feature aktiv ist, nie in die Textdateien des Projekts. Die Worte der Website sind der andere Satz, in features/Members/install/text/
return [

	// Der Eintrag im Rail, von Admin::nav() genannt - und der Name, den der Kopf über dem Bildschirm des Panels zeigt
	'[[/_admin/members/nav]]'					=> 'Mitglieder',

	// Die Kachel des Dashboards (Admin::summary())
	'[[/_admin/members/label/count]]'			=> 'Mitglieder',

	// Das Verzeichnis: was es eingrenzt, wie seine Spalten heißen, was es sagt, wo es leer ist
	'[[/_admin/members/label/search]]'			=> 'Mitglieder suchen',
	'[[/_admin/members/label/filter]]'			=> 'Gruppe',
	'[[/_admin/members/filter/all]]'			=> 'Alle Gruppen',
	'[[/_admin/members/filter/none]]'			=> 'Keine Gruppe',
	'[[/_admin/members/label/name]]'			=> 'Name',
	'[[/_admin/members/label/state]]'			=> 'Zustand',
	'[[/_admin/members/label/active]]'			=> 'Aktiv',
	'[[/_admin/members/label/login]]'			=> 'Letzte Anmeldung',
	'[[/_admin/members/state/active]]'			=> 'Aktiv',
	'[[/_admin/members/state/inactive]]'		=> 'Inaktiv',
	'[[/_admin/members/empty]]'					=> 'Noch keine Mitglieder.',
	'[[/_admin/members/empty/filter]]'			=> 'Keine Mitglieder gefunden.',

	// Die Leiste der Liste und die Knöpfe einer Zeile
	'[[/_admin/members/label/new]]'				=> 'Neues Mitglied',
	'[[/_admin/members/label/signout]]'			=> 'Alle abmelden',
	'[[/_admin/members/label/link]]'			=> 'Link senden',

	// Der Dialog eines neuen Mitglieds
	'[[/_admin/members/dialog/new]]'			=> 'Mitglied anlegen',
	'[[/_admin/members/label/create]]'			=> 'Mitglied anlegen',
	'[[/_admin/members/label/email]]'			=> 'E-Mail-Adresse',
	'[[/_admin/members/label/group]]'			=> 'Gruppe',
	'[[/_admin/members/hint/group]]'			=> 'Ein Wort, zB premium. Leer lassen für keine Gruppe.',
	'[[/_admin/members/label/invite]]'			=> 'Einladung',
	'[[/_admin/members/hint/invite]]'			=> 'Das Mitglied bekommt einen Link, mit dem es sich zum ersten Mal anmeldet. Ohne E-Mail erstellt, wird der Link Dir einmal gezeigt und nirgends gespeichert.',
	'[[/_admin/members/invite/mail]]'			=> 'Per E-Mail senden',
	'[[/_admin/members/invite/link]]'			=> 'Link erstellen, keine E-Mail senden',
	'[[/_admin/members/invite/none]]'			=> 'Keine Einladung',
	'[[/_admin/members/msg/added]]'				=> 'Mitglied angelegt.',
	'[[/_admin/members/msg/invited]]'			=> 'Mitglied angelegt, Einladung gesendet.',
	'[[/_admin/members/msg/invited-quiet]]'		=> 'Mitglied angelegt, Einladung ohne E-Mail erstellt.',

	// Der Dialog einer Änderung
	'[[/_admin/members/dialog/edit]]'			=> 'Mitglied ändern',
	'[[/_admin/members/hint/active]]'			=> 'Ein Mitglied, das nicht aktiv ist, kann sich nicht anmelden und wird sofort abgemeldet.',

	// Ein Link zum Anmelden, aus einer Zeile gesendet, und der Link, der einmal gezeigt wird
	'[[/_admin/members/dialog/link-send]]'		=> 'Link senden',
	'[[/_admin/members/hint/notify]]'			=> 'Der Link gilt einmal und so lange wie ein Code. Ohne E-Mail erstellt, wird er Dir einmal gezeigt und nirgends gespeichert; wer ihn hat, meldet sich als dieses Mitglied an.',
	'[[/_admin/members/invite/link-only]]'		=> 'Link erstellen, keine E-Mail senden',
	'[[/_admin/members/msg/sent]]'				=> 'Link gesendet.',
	'[[/_admin/members/msg/sent-quiet]]'		=> 'Link ohne E-Mail erstellt.',
	'[[/_admin/members/dialog/link]]'			=> 'Link zum Anmelden',
	'[[/_admin/members/label/the-link]]'		=> 'Link',
	'[[/_admin/members/hint/link-once]]'		=> 'Dieser Link wird nur jetzt gezeigt und nirgends gespeichert. Wer ihn hat, meldet sich als dieses Mitglied an, gib ihn also selbst weiter.',
	'[[/_admin/members/label/copy]]'			=> 'Link kopieren',
	'[[/_admin/members/msg/copied]]'			=> 'Link kopiert.',

	// Die Fragen
	'[[/_admin/members/confirm/delete-title]]'	=> 'Mitglied löschen',
	'[[/_admin/members/confirm/delete]]'		=> '%s löschen? Das Konto ist dann für immer weg.',
	'[[/_admin/members/confirm/signout]]'		=> 'Jedes Mitglied wird abgemeldet, die Geräte, die angemeldet bleiben, eingeschlossen, und muss einen neuen Link oder Code anfordern.',
	'[[/_admin/members/msg/signedout]]'			=> 'Alle sind abgemeldet.',

	'[[/_admin/members/loading]]'				=> 'Mitglieder werden geladen ...',
	'[[/_admin/members/state/all]]'				=> 'Alle Zustände',
	'[[/_admin/members/row/open]]'				=> '%s öffnen',
	'[[/_admin/members/row/link]]'				=> '%s einen Link senden',
	'[[/_admin/members/row/delete]]'			=> '%s löschen',
	'[[/_admin/members/select/page]]'			=> 'Die Mitglieder dieser Seite auswählen',
	'[[/_admin/members/select/row]]'			=> '%s auswählen',
	'[[/_admin/members/confirm/delete-many-title]]'	=> 'Mitglieder löschen',
	'[[/_admin/members/sort/by]]'				=> 'Mitglieder sortieren nach',
	'[[/_admin/members/sort/none]]'				=> 'Reihenfolge der Mitglieder',
	'[[/_admin/members/sort/up]]'				=> '%s, aufsteigend',
	'[[/_admin/members/sort/down]]'				=> '%s, absteigend',
	'[[/_admin/members/confirm/delete-many]]'	=> '%s Mitglieder löschen? Die Konten sind dann endgültig weg.',
	'[[/_admin/members/msg/deleted-many]]'		=> '%s Mitglieder gelöscht.',
	'[[/_admin/members/label/signout-one]]'		=> 'Überall abmelden',
	'[[/_admin/members/confirm/signout-one]]'	=> '%s wird auf allen Geräten abgemeldet und muss einen neuen Link oder Code anfordern.',
	'[[/_admin/members/msg/signedout-one]]'		=> 'Das Mitglied ist abgemeldet.',
	'[[/_admin/members/missing]]'				=> 'Dieses Mitglied gibt es nicht (mehr).',

	// Der Hinweis über der Tabelle, wo der Server den Client nicht vor der Arbeit nach der Antwort entlässt (Admin::apiList())
	'[[/_admin/members/note/server]]'			=> 'Wo der Server den Client vor der Arbeit nach der Antwort entlässt (php-fpm, LiteSpeed), zeigt das Anmeldeformular keinen Unterschied zwischen einer Adresse mit und einer ohne Konto. Dieser Server tut es nicht, die Zeit der Antwort verrät sie also. Auch wo er es tut, kann ein ausgelasteter Server die nächste Anfrage warten lassen, und ein ausfallender Mail-Transport ist nicht gedeckt.',
	'[[/_admin/members/note/braked]]'			=> 'Anmeldungen sind gebremst: Mails warten im Postausgang, deshalb wird keine neue Anmelde-Mail eingereiht, bis sie zugestellt sind.',
	'[[/_admin/members/note/server-ok]]'		=> 'Das Anmeldeformular verbirgt, welche Adressen ein Konto haben, solange der Server den Client vor der Arbeit entlässt, wie dieser es tut. Zwei Grenzen bleiben: Ein ausgelasteter Server kann die nächste Anfrage warten lassen, und ein ausfallender Mail-Transport zeigt sich.',
	'[[/_admin/members/note/cap]]'				=> 'Die Einstellung Anforderungen erlaubt mehr als die Mail-Grenze des Kernels je Besucher, %s in der Stunde (/nino/mail/cap); sie greift zuerst, und die Mails der anderen Features teilen sie.',

	// Was das Panel zu einer Anfrage sagt, zu der der Server kein Wort gab
	'[[/_admin/members/error/load]]'			=> 'Die Mitglieder konnten nicht geladen werden.',
	'[[/_admin/members/error/add]]'				=> 'Das Mitglied konnte nicht angelegt werden.',
	'[[/_admin/members/error/save]]'			=> 'Die Änderung konnte nicht gespeichert werden.',
	'[[/_admin/members/error/link]]'			=> 'Der Link konnte nicht gesendet werden.',
	'[[/_admin/members/error/delete]]'			=> 'Das Mitglied konnte nicht gelöscht werden.',
	'[[/_admin/members/error/signout]]'			=> 'Niemand konnte abgemeldet werden.',

	// Die Sätze der Codes, mit denen das Panel antwortet (Admin::_failed(), apiAdd(), apiLink())
	'[[/_admin/error/members_invalid]]'			=> 'Das ist keine gültige E-Mail-Adresse.',
	'[[/_admin/error/members_taken]]'			=> 'Ein Mitglied mit dieser Adresse gibt es schon.',
	'[[/_admin/error/members_long]]'			=> 'Der Name darf bis zu %s Zeichen haben.',
	'[[/_admin/error/members_unknown]]'			=> 'Dieses Mitglied gibt es nicht mehr.',
	'[[/_admin/error/members_inactive]]'		=> 'Einem Mitglied, das nicht aktiv ist, kann kein Link gesendet werden.',
	'[[/_admin/error/members_mail]]'			=> 'Die Mail konnte nicht gesendet werden. Prüfe die Mail-Einstellungen und die Adresse der Website und versuche es dann noch einmal.',
	'[[/_admin/error/members_ratelimit]]'		=> 'Die Obergrenze der Mails pro Stunde von dieser Adresse ist erreicht. Versuche es später wieder.',
];
