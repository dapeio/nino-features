<?php
declare(strict_types=1);

// The words of the panel, one file per interface language (see Admin::text()), merged into the
// workbench's fills while the feature is active. The word of the toggle that opens a panel, which the
// site reads, is delivered by install/text/
return [

	// The rail entry, named by Admin::nav() - and the name the head over the panel's screen shows
	'[[/_admin/menu/nav]]'	=> 'Menü',


	'[[/_admin/menu/label/new]]'	=> 'Neues Menü',
	'[[/_admin/menu/label/kernel-key]]'	=> 'Menü zur Navigation des Kerns',
	'[[/_admin/menu/label/own-key]]'	=> 'Ein eigener Schlüssel',
	'[[/_admin/menu/label/key]]'	=> 'Schlüssel',
	'[[/_admin/menu/label/create]]'	=> 'Anlegen',
	'[[/_admin/menu/label/delete]]'	=> 'Löschen',
	'[[/_admin/menu/label/entries]]'	=> 'Einträge: %s',
	'[[/_admin/menu/label/replaces]]'	=> 'Ersetzt die Navigation des Kerns',

	'[[/_admin/menu/confirm/delete]]'	=> 'Das Menü „%s“ löschen? Seine Texte und Bilder bleiben in den Panels Texte und Bilder.',

	'[[/_admin/menu/state/no-menus]]'	=> 'Es gibt noch kein Menü. Ein Menü mit dem Schlüssel einer Navigation des Kerns tritt an ihre Stelle.',
	'[[/_admin/menu/state/key-invalid]]'	=> 'Ein Schlüssel besteht aus kleingeschriebenen Wörtern mit Bindestrichen, beginnt mit einem Buchstaben und hat bis zu 32 Zeichen.',
	'[[/_admin/menu/state/key-taken]]'	=> 'Es gibt schon ein Menü „%s“.',

	'[[/_admin/menu/label/back]]'	=> 'Menüs',
	'[[/_admin/menu/label/language]]'	=> 'Sprache',
	'[[/_admin/menu/label/save]]'	=> 'Speichern',
	'[[/_admin/menu/label/seed]]'	=> 'Seiten des Menüs „%s“ des Kerns übernehmen',

	'[[/_admin/menu/state/kernel]]'	=> 'Das Menü „%s“ tritt überall dort an die Stelle der Navigation des Kerns gleichen Namens, wo das Design es einsetzt.',
	'[[/_admin/menu/state/no-entries]]'	=> 'Noch keine Einträge.',

	'[[/_admin/menu/label/entry]]'	=> 'Eintrag %s',
	'[[/_admin/menu/label/kind]]'	=> 'Der Eintrag ist',

	'[[/_admin/menu/kind/page]]'	=> 'Eine Seite der Website',
	'[[/_admin/menu/kind/link]]'	=> 'Eine Adresse',

	'[[/_admin/menu/label/page]]'	=> 'Seite',
	'[[/_admin/menu/label/address]]'	=> 'Adresse',
	'[[/_admin/menu/label/target]]'	=> 'Öffnet',

	'[[/_admin/menu/target/same]]'	=> 'Im selben Fenster',
	'[[/_admin/menu/target/blank]]'	=> 'In einem neuen Fenster',

	'[[/_admin/menu/label/label]]'	=> 'Text auf %s',
	'[[/_admin/menu/label/add-entry]]'	=> 'Eintrag',
	'[[/_admin/menu/label/up]]'	=> 'Nach oben',
	'[[/_admin/menu/label/down]]'	=> 'Nach unten',
	'[[/_admin/menu/label/remove]]'	=> 'Entfernen',
	'[[/_admin/menu/label/panel]]'	=> 'Dropdown-Panel',
	'[[/_admin/menu/label/on]]'	=> 'an',
	'[[/_admin/menu/label/off]]'	=> 'aus',
	'[[/_admin/menu/label/columns]]'	=> 'Spalten',
	'[[/_admin/menu/label/edit-panel]]'	=> 'Panel bearbeiten',
	'[[/_admin/menu/label/close-panel]]'	=> 'Panel schließen',
	'[[/_admin/menu/label/column]]'	=> 'Spalte %s',
	'[[/_admin/menu/label/item]]'	=> 'Baustein',

	'[[/_admin/menu/item/title]]'	=> 'Titel',
	'[[/_admin/menu/item/subtitle]]'	=> 'Untertitel',
	'[[/_admin/menu/item/image]]'	=> 'Bild',
	'[[/_admin/menu/item/page]]'	=> 'Seite der Website',
	'[[/_admin/menu/item/link]]'	=> 'Adresse',
	'[[/_admin/menu/item/html]]'	=> 'HTML+',

	'[[/_admin/menu/label/text]]'	=> 'Text auf %s',
	'[[/_admin/menu/label/html]]'	=> 'HTML+, so wie getippt',
	'[[/_admin/menu/label/add-item]]'	=> 'Baustein',
	'[[/_admin/menu/label/upload]]'	=> 'Bild hochladen',
	'[[/_admin/menu/label/alt]]'	=> 'Alt-Text auf %s',

	'[[/_admin/menu/picture/busy]]'	=> 'Das Bild wird gespeichert ...',
	'[[/_admin/menu/picture/stored]]'	=> 'Das Bild ist gespeichert.',
	'[[/_admin/menu/picture/small]]'	=> 'Das Bild ist gespeichert, aber mit %s kleiner als der Platz, auf den es zugeschnitten wird.',
	'[[/_admin/menu/picture/alt-saved]]'	=> 'Der Alt-Text ist gespeichert.',

	'[[/_admin/menu/error/load]]'	=> 'Es konnte nicht geladen werden.',
	'[[/_admin/menu/error/registry]]'	=> 'Die Seiten und Sprachen konnten nicht geladen werden.',
	'[[/_admin/menu/error/save]]'	=> 'Es konnte nicht gespeichert werden.',
	'[[/_admin/menu/error/delete]]'	=> 'Das Menü konnte nicht gelöscht werden.',
	'[[/_admin/menu/error/upload]]'	=> 'Das Bild konnte nicht gespeichert werden.',
	'[[/_admin/menu/error/slot]]'	=> 'Der Platz für das Bild konnte nicht angelegt werden.',
	'[[/_admin/menu/error/alt]]'	=> 'Der Alt-Text konnte nicht gespeichert werden.',

	// The sentences of the codes the panel answers with, %s a name or a permission (Admin)
	'[[/_admin/error/menu_key]]'	=> 'Ein Menüschlüssel besteht aus kleingeschriebenen Wörtern mit Bindestrichen und beginnt mit einem Buchstaben.',
	'[[/_admin/error/menu_invalid]]'	=> 'Diese Einträge sind nicht gültig: %s. Ihre Ids, Adressen und Schlüssel werden beim Speichern geprüft.',
	'[[/_admin/error/menu_list]]'	=> 'Die Einträge sind eine Liste mit höchstens %s Einträgen.',
	'[[/_admin/error/menu_texts]]'	=> 'Die Texte gehören zu den Schlüsseln dieses Menüs, in den Sprachen der Website, je eine Zeile mit höchstens 300 Zeichen.',
	'[[/_admin/error/menu_missing]]'	=> 'Es gibt kein Menü „%s“.',
	'[[/_admin/error/menu_permission]]'	=> 'Beim Speichern dieses Menüs werden Texte oder Bildplätze angelegt oder geändert; dafür braucht es die Berechtigung %s.',
	'[[/_admin/error/menu_key_made]]'	=> 'Der Textschlüssel %s konnte nicht angelegt werden.',
	'[[/_admin/error/menu_slot_made]]'	=> 'Der Bildplatz %s konnte nicht angelegt werden.',
];
