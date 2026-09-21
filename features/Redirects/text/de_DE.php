<?php
// Die eigenen Workbench-Texte des Redirects-Features, während das Feature aktiv
// ist in seine Fills gemischt (siehe text() des Panels) - dieselben Schlüssel
// und dieselbe Form wie die text/<locale>.php der Workbench. Ein %s füllt das Skript
return [
	'[[/_admin/nav/redirects]]'								=> 'Weiterleitungen',

	'[[/_admin/redirects/label/title]]'				=> 'Weiterleitungen',
	'[[/_admin/redirects/label/tile]]'				=> 'Adressen ohne Antwort',
	'[[/_admin/redirects/hint/rules]]'				=> 'Eine alte Adresse, wohin sie schickt, und ob sie endgültig umgezogen ist. Eine Regel wird nur dort gefragt, wo sonst nichts die Adresse beantwortet - sie kann also nie eine Seite vom Netz nehmen.',
	'[[/_admin/redirects/hint/missing]]'			=> 'Adressen, nach denen jemand gefragt hat und die nichts beantwortet hat - die häufigsten zuerst. Von hier kommen die Regeln, die sich lohnen.',
	'[[/_admin/redirects/hint/off]]'					=> 'Das Merken ist in den Einstellungen dieses Features abgeschaltet, es kommt also nichts Neues dazu.',
	'[[/_admin/redirects/hint/limit]]'				=> 'Höchstens %s werden behalten. Ein Scanner findet an einem Nachmittag mehr, als je jemand liest.',
	'[[/_admin/redirects/hint/subtree]]'			=> 'Alles unterhalb der alten Adresse zieht mit um: Was hinter dem alten Pfad stand, steht hinter dem neuen.',

	'[[/_admin/redirects/label/from]]'				=> 'Alte Adresse',
	'[[/_admin/redirects/label/to]]'					=> 'Schickt nach',
	'[[/_admin/redirects/label/status]]'			=> 'Art',
	'[[/_admin/redirects/label/subtree]]'			=> 'Alles darunter auch',
	'[[/_admin/redirects/label/hits]]'				=> 'Gefolgt',
	'[[/_admin/redirects/label/last]]'				=> 'Zuletzt',
	'[[/_admin/redirects/label/path]]'				=> 'Adresse',
	'[[/_admin/redirects/label/count]]'				=> 'Gefragt',
	'[[/_admin/redirects/label/new]]'					=> 'Neue Weiterleitung',
	'[[/_admin/redirects/label/edit]]'				=> 'Bearbeiten',
	'[[/_admin/redirects/label/delete]]'			=> 'Löschen',
	'[[/_admin/redirects/label/save]]'				=> 'Speichern',
	'[[/_admin/redirects/label/cancel]]'			=> 'Abbrechen',
	'[[/_admin/redirects/label/back]]'				=> 'Zurück zu den Regeln',
	'[[/_admin/redirects/label/probe]]'				=> 'Adresse ausprobieren',
	'[[/_admin/redirects/label/probe-run]]'		=> 'Prüfen',
	'[[/_admin/redirects/label/forget]]'			=> 'Alle vergessen',
	'[[/_admin/redirects/label/forget-one]]'	=> 'Vergessen',
	'[[/_admin/redirects/label/make]]'				=> 'Regel daraus machen',
	'[[/_admin/redirects/label/search]]'			=> 'Suchen',
	'[[/_admin/redirects/label/on]]'					=> 'an',
	'[[/_admin/redirects/label/off]]'					=> 'aus',

	'[[/_admin/redirects/status/301]]'				=> 'Endgültig umgezogen (301)',
	'[[/_admin/redirects/status/302]]'				=> 'Vorübergehend umgezogen (302)',

	'[[/_admin/redirects/empty/rules]]'				=> 'Noch keine Weiterleitung.',
	'[[/_admin/redirects/empty/missing]]'			=> 'Seit dem Einschalten wurde nach nichts vergeblich gefragt.',
	'[[/_admin/redirects/empty/nomatch]]'			=> 'Nichts passt dazu.',

	'[[/_admin/redirects/msg/saved]]'					=> 'Gespeichert.',
	'[[/_admin/redirects/msg/deleted]]'				=> 'Gelöscht.',
	'[[/_admin/redirects/msg/forgotten]]'			=> 'Vergessen.',
	'[[/_admin/redirects/msg/probe-route]]'		=> 'Eine Seite beantwortet diese Adresse, es wird also keine Regel gefragt.',
	'[[/_admin/redirects/msg/probe-rule]]'		=> '"%s" beantwortet sie und schickt nach %s.',
	'[[/_admin/redirects/msg/probe-nothing]]'	=> 'Nichts beantwortet sie. Ein Besucher bekommt die 404-Seite, und die Adresse wird als fehlend gemerkt.',

	'[[/_admin/redirects/error/from]]'				=> 'Eine alte Adresse ist ein Pfad dieser Site, etwa /alte/seite.',
	'[[/_admin/redirects/error/to]]'					=> 'Ein Ziel ist ein Pfad dieser Site oder eine https-Adresse einer anderen.',
	'[[/_admin/redirects/error/loop]]'				=> 'Diese Regel würde einen Besucher in sich selbst zurückschicken: %s',
	'[[/_admin/redirects/error/unknown]]'			=> 'Es gibt keine Regel für "%s".',
	'[[/_admin/redirects/error/taken]]'				=> 'Für "%s" gibt es schon eine Regel. Diese zuerst löschen oder bearbeiten.',
	'[[/_admin/redirects/error/load]]'				=> 'Die Weiterleitungen konnten nicht gelesen werden.',

	'[[/_admin/redirects/confirm/delete]]'		=> 'Die Weiterleitung für "%s" löschen?',
	'[[/_admin/redirects/confirm/forget]]'		=> 'Jede Adresse auf dieser Liste vergessen? Die nächste Anfrage danach bringt sie zurück.',
	'[[/_admin/redirects/note/incomplete]]'	=> 'Eine Regel ohne brauchbares "von" und "nach" wurde verworfen.',
	'[[/_admin/redirects/note/duplicate]]'	=> 'Eine zweite Regel für "%s" wurde verworfen - die erste beantwortet sie.',
	'[[/_admin/redirects/note/status]]'			=> 'Regel "%s": %d ist kein Weiterleitungsstatus, es gilt 301.',
	'[[/_admin/redirects/note/dropped]]'		=> 'Regel "%s" wurde verworfen: %r',
	'[[/_admin/redirects/reason/self]]'			=> 'sie schickt die Adresse auf sich selbst',
	'[[/_admin/redirects/reason/subtree]]'	=> 'sie schickt alles darunter auf eine Adresse, die wieder darunter liegt',
	'[[/_admin/redirects/error/status]]'		=> 'Das ist kein Weiterleitungsstatus.',
];
