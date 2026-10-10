<?php
// features/Builder/feature.php - what the Features panel reads. The class is not
// declared here: features/Builder/ can only ever serve \Nino\Modules\Builder, and
// the parts below it - Admin/, Document/, Reader/, Writer/ - resolve the same
// way, one directory per class.
return [
	'key'					=> 'builder',
	'name'				=> [ 'en_US' => 'Builder', 'de_DE' => 'Builder' ],
	'description'	=> [
		'en_US' => 'Builds a page template out of sections, columns and components: it reads the file into a model and writes it back, and what it does not read it leaves byte for byte.',
		'de_DE' => 'Baut ein Seitentemplate aus Sections, Spalten und Komponenten: Es liest die Datei in ein Modell und schreibt sie zurück, und was es nicht liest, lässt es Byte für Byte stehen.',
	],
	'manual'			=> [
		'shortcodes' => [],
		'markup' => [
			'<div class="nino-wrap nino-wrap--vpa nino-vpa--zoom-soft nino-vpa--speed-medium">' => [
				'en_US' => 'Around all the sections of a page template, between its head and its foot: the first line after the head opens it, the last before the foot closes it. Its classes are the animation of the template: nino-wrap--vpa animates the sections, an effect with its strength and a speed say how, every other class is kept. A section is animated by carrying nino-vpa. A file with no wrap is given one with the first change; the head line nino:template-vpa of an earlier Builder is read as the wrap and not written again.',
				'de_DE' => 'Um alle Sections eines Seitentemplates, zwischen Kopf und Fuß: Die erste Zeile nach dem Kopf öffnet es, die letzte vor dem Fuß schließt es. Seine Klassen sind die Animation des Templates: nino-wrap--vpa animiert die Sections, ein Effekt mit seiner Stärke und eine Geschwindigkeit sagen wie, jede andere Klasse bleibt stehen. Eine Section wird animiert, indem sie nino-vpa trägt. Eine Datei ohne Wrap bekommt einen mit der ersten Änderung; die Kopfzeile nino:template-vpa eines früheren Builders wird als Wrap gelesen und nicht mehr geschrieben.',
			],
		],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'The page-*.tpl files of the project. #builder lists them by file - name (marked where the Builder cannot read it completely), frames, sections, routes - with New, Open, Duplicate (a copy under a new name, with copies of its text keys and image slots) and Delete (a template a route renders is kept). #builder/<file> is the editor, and the editor is the preview: sections, columns and components as frames, and every level ends in a button that adds to it. Saving checks the hash of the file; a save or copy that makes keys or slots needs the permissions of the Text Keys and Image Slots panels.',
				'de_DE' => 'Die page-*.tpl-Dateien des Projekts. #builder listet sie nach Datei - Name (markiert, wo der Builder sie nicht ganz liest), Rahmen, Sections, Routen - mit Neu, Öffnen, Duplizieren (Kopie unter neuem Namen, samt Textschlüsseln und Bildplätzen) und Löschen (mit Route bleibt es). #builder/<Datei> ist der Editor, und der Editor ist die Vorschau: Sections, Spalten und Komponenten als Rahmen, und jede Ebene endet in einem Knopf, der etwas hinzufügt. Das Speichern prüft den Hash der Datei; was Schlüssel oder Bildplätze anlegt, braucht auch die Rechte der Panels Textschlüssel und Bildplätze.',
			],
			'Heads of the frames' => [
				'en_US' => 'Every frame has a head of three parts: its title, what is said of it - a picture behind a section, the loop a column runs, a column hidden in the view - and its tools: settings, up, down, duplicate, delete, and for a section edit as HTML+. The pencil beside the name of a section, in its head and in the title of its dialog, turns the name into a field where it stands: Enter or leaving takes it, Escape drops it, and a section with keys under its name is asked first, since they move with it. A loop is no frame: it is set in the Loop tab of its column.',
				'de_DE' => 'Jeder Rahmen hat einen Kopf aus drei Teilen: seinen Titel, was über ihn gesagt wird - ein Bild hinter einer Section, der Loop einer Spalte, eine Spalte, die in der Ansicht verborgen ist - und seine Werkzeuge: Einstellungen, hoch, runter, Duplizieren, Löschen, bei einer Section Als HTML+ bearbeiten. Der Stift neben dem Namen einer Section, im Kopf und im Titel ihres Dialogs, macht den Namen an Ort und Stelle zum Feld: Enter oder Verlassen übernimmt ihn, Escape verwirft ihn, und eine Section mit Schlüsseln unter ihrem Namen wird vorher gefragt, denn sie ziehen mit. Ein Loop ist kein Rahmen: Man stellt ihn im Tab Loop seiner Spalte ein.',
			],
			'Views of the preview' => [
				'en_US' => 'The preview is shown as Mobile, Tablet, Desktop or Global. The tables in the dialogs - the width and visibility of a column, the cells of a loop - have the row of the view that is shown; in Global they have all three, and a link under a table of one row, All viewports, switches to it.',
				'de_DE' => 'Die Vorschau zeigt Mobile, Tablet, Desktop oder Global. Die Tabellen in den Dialogen - Breite und Sichtbarkeit einer Spalte, die Zellen eines Loops - haben die Zeile der gezeigten Ansicht; in Global haben sie alle drei, und ein Link unter einer Tabelle aus einer Zeile, Alle Viewports, schaltet darauf um.',
			],
			'Settings dialogs' => [
				'en_US' => 'The template: its name and file, its frames, and its animation - Animate the sections, and folded below it the kind: effect, strength and speed (the classes of the wrap); turning the switch asks whether the sections that follow the template follow it. A section: Layout, Background, Viewport animation and CSS classes - its animation is like the template, off or its own. A column: Layout, Loop, Viewport animation and CSS classes. A component: Content, Properties and CSS classes.',
				'de_DE' => 'Das Template: Name und Datei, Rahmen und Animation - Sections animieren, darunter eingeklappt die Art: Effekt, Stärke und Speed (die Klassen des Wraps); wer den Schalter umlegt, wird gefragt, ob die Sections, die dem Template folgen, mitziehen. Eine Section: Layout, Hintergrund, Viewport-Animation und CSS-Klassen - ihre Animation ist wie das Template, aus oder eigene. Eine Spalte: Layout, Loop, Viewport-Animation und CSS-Klassen. Eine Komponente: Inhalt, Eigenschaften und CSS-Klassen.',
			],
			'Source' => [
				'en_US' => 'Beside every text and picture in a form stands its source as text, with a button for the dialog of the sources: a text key or an image slot to choose, a new one to make, and for a text a fixed value - in a loop a field of the element. A source that means nothing where it stands is red and not saved.',
				'de_DE' => 'Neben jedem Text und jedem Bild in einem Formular steht seine Quelle als Text, mit einem Knopf für den Dialog der Quellen: ein Textschlüssel oder Bildplatz zum Wählen, ein neuer zum Anlegen und bei einem Text ein fester Wert - im Loop ein Feld des Elements. Eine Quelle, die dort nichts bedeutet, ist rot und wird nicht gespeichert.',
			],
			'Language of the texts' => [
				'en_US' => 'The language picker over the preview, next to the viewports, sets the language of the texts. The preview says the first line of every text in it - and shows the picture of a slot where the slot has one - and the dialogs show and write the texts of that language. It starts in the language of the site and is not kept; a site with one language has no picker.',
				'de_DE' => 'Die Sprachwahl über der Vorschau, neben den Viewports, stellt die Sprache der Texte ein. Die Vorschau sagt in ihr die erste Zeile jedes Textes - und zeigt das Bild eines Bildplatzes, wo er eins hat -, und die Dialoge zeigen und schreiben die Texte dieser Sprache. Sie beginnt in der Sprache der Website und wird nicht gemerkt; eine Website mit einer Sprache hat keine Sprachwahl.',
			],
			'Texts of the keys' => [
				'en_US' => 'Under the source of a text component stands the text of its key in the language of the picker: a line of text for a key of the plain format, the editor of the Text Keys panel in the profile of the format for an inline, lines or blocks key. A key that only the save makes is made in the format of its component, plain where it has none. What is typed is kept with the template and written to the key when it is saved, in the language it was typed for; where the save fails, the texts are put back as they were - a language in which a key had no text at all keeps an empty one, which is rendered as no text. A fixed value and the field of a loop have no text field. A text is read with the permission of the Text panel and written with that of the Text Keys panel.',
				'de_DE' => 'Unter der Quelle einer Textkomponente steht der Text ihres Schlüssels in der Sprache der Sprachwahl: eine Zeile für einen Schlüssel im Format plain, der Editor des Panels Textschlüssel im Profil des Formats für einen Schlüssel im Format inline, lines oder blocks. Ein Schlüssel, den erst das Speichern anlegt, wird im Format seiner Komponente angelegt, plain, wo sie keins hat. Was getippt wird, bleibt beim Template und wird beim Speichern in den Schlüssel geschrieben, in der Sprache, für die es getippt wurde; schlägt das Speichern fehl, kommen die Texte zurück, wie sie waren - eine Sprache, in der ein Schlüssel gar keinen Text hatte, behält einen leeren, der wie kein Text gerendert wird. Ein fester Wert und das Feld eines Loops haben kein Textfeld. Ein Text wird mit der Berechtigung des Panels Texte gelesen und mit der des Panels Textschlüssel geschrieben.',
			],
			'Pictures of the slots' => [
				'en_US' => 'Under the source of a picture, and in the Background tab of a section, stands the choice of a file: the first picture makes the image slot at once and stores the picture, as the Image Slots and Images panels do; it does not wait for the save. The alternative text in the language of the picker stands beside it: it is stored with the slot at once, or, for a slot that only the save makes, with it. Slots need the permission of the Image Slots panel, pictures and alternative texts that of the Images panel.',
				'de_DE' => 'Unter der Quelle eines Bildes und im Tab Hintergrund einer Section steht die Wahl einer Datei: Das erste Bild legt den Bildplatz sofort an und speichert das Bild, wie es die Panels Bildplätze und Bilder tun; es wartet nicht aufs Speichern. Der Alternativtext in der Sprache der Sprachwahl steht daneben: Er wird sofort beim Bildplatz gespeichert, bei einem Bildplatz, den erst das Speichern anlegt, mit ihm. Bildplätze brauchen die Berechtigung des Panels Bildplätze, Bilder und Alternativtexte die des Panels Bilder.',
			],
		],
		'callbacks' => [],
		'install' => [],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	// The Components module of 1.6: the registry the builder reads and the
	// shortcodes it writes calls of
	'nino'				=> '^1.6',
	'requires'		=> [],
	// The page templates it edits are the project's own, in private/templates/,
	// and a backup carries them as project content - none of it belongs to this
	// feature, which is why switching it off leaves every page as it is
	'data'				=> [],
	'settings'		=> [],
];
