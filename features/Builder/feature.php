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
			'<div class="nino-grid-bg">[image /slot alt=""]</div>' => [
				'en_US' => 'The picture behind a column: the first thing in it, one image call with the slot and an empty alt text - the alt text belongs to the slot. A column paints a ground of its own with nino-grid--<ground> (alt, tint, dark, black, primary or brand-alt) or the counter ground of its section with nino-grid--counter; nino-grid--dim darkens the picture and is read only while the column has one. A block that is not the first in the column, a second one, or another call in it makes the section an HTML+ block.',
				'de_DE' => 'Das Bild hinter einer Spalte: das Erste in ihr, ein Bildaufruf mit dem Bildplatz und leerem Alternativtext - der Alternativtext gehört zum Bildplatz. Eine Spalte malt eine eigene Fläche mit nino-grid--<Fläche> (alt, tint, dark, black, primary oder brand-alt) oder die Gegenfläche ihrer Section mit nino-grid--counter; nino-grid--dim dunkelt das Bild ab und wird nur gelesen, solange die Spalte eins hat. Ein Block, der nicht das Erste in der Spalte ist, ein zweiter oder ein anderer Aufruf darin macht die Section zum HTML+-Block.',
			],
		],
		'routes' => [],
		'panel' => [
			'Builder' => [
				'en_US' => 'The page-*.tpl files of the project. #builder lists them by file - name (marked where the Builder cannot read it completely), frames, sections, routes - with New, Open, Duplicate (a copy under a new name, with copies of its text keys and image slots) and Delete (a template a route renders is kept). #builder/<file> is the editor, and the editor is the preview: sections, columns and components as frames, and every level ends in a button that adds to it. Saving checks the hash of the file; a save or copy that makes keys or slots needs the permissions of the Text Keys and Image Slots panels.',
				'de_DE' => 'Die page-*.tpl-Dateien des Projekts. #builder listet sie nach Datei - Name (markiert, wo der Builder sie nicht ganz liest), Rahmen, Sections, Routen - mit Neu, Öffnen, Duplizieren (Kopie unter neuem Namen, samt Textschlüsseln und Bildplätzen) und Löschen (mit Route bleibt es). #builder/<Datei> ist der Editor, und der Editor ist die Vorschau: Sections, Spalten und Komponenten als Rahmen, und jede Ebene endet in einem Knopf, der etwas hinzufügt. Das Speichern prüft den Hash der Datei; was Schlüssel oder Bildplätze anlegt, braucht auch die Rechte der Panels Textschlüssel und Bildplätze.',
			],
			'Heads of the frames' => [
				'en_US' => 'Every frame has a head of three parts: its title, what is said of it - a picture behind a section or a column, the loop a column runs, an alignment of a column that is not the default (its icon, which opens the Layout tab), a column hidden in the view - and its tools: settings, up, down, duplicate, delete, and for a section edit as HTML+. The pencil beside the name of a section, in its head and in the title of its dialog, turns the name into a field where it stands: Enter or leaving takes it, Escape drops it, and a section with keys under its name is asked first, since they move with it. A loop is no frame: it is set in the Loop tab of its column.',
				'de_DE' => 'Jeder Rahmen hat einen Kopf aus drei Teilen: seinen Titel, was über ihn gesagt wird - ein Bild hinter einer Section oder einer Spalte, der Loop einer Spalte, eine Ausrichtung der Spalte, die nicht die Vorgabe ist (ihr Icon, das den Tab Layout öffnet), eine Spalte, die in der Ansicht verborgen ist - und seine Werkzeuge: Einstellungen, hoch, runter, Duplizieren, Löschen, bei einer Section Als HTML+ bearbeiten. Der Stift neben dem Namen einer Section, im Kopf und im Titel ihres Dialogs, macht den Namen an Ort und Stelle zum Feld: Enter oder Verlassen übernimmt ihn, Escape verwirft ihn, und eine Section mit Schlüsseln unter ihrem Namen wird vorher gefragt, denn sie ziehen mit. Ein Loop ist kein Rahmen: Man stellt ihn im Tab Loop seiner Spalte ein.',
			],
			'Views of the preview' => [
				'en_US' => 'The preview is shown as Mobile, Tablet, Desktop or Global. The tables in the dialogs - the width and visibility of a column, the cells of a loop - have the row of the view that is shown; in Global they have all three, and a link under a table of one row, All viewports, switches to it.',
				'de_DE' => 'Die Vorschau zeigt Mobile, Tablet, Desktop oder Global. Die Tabellen in den Dialogen - Breite und Sichtbarkeit einer Spalte, die Zellen eines Loops - haben die Zeile der gezeigten Ansicht; in Global haben sie alle drei, und ein Link unter einer Tabelle aus einer Zeile, Alle Viewports, schaltet darauf um.',
			],
			'Settings dialogs' => [
				'en_US' => 'The template: its name and file, its frames, and its animation - Animate the sections, and folded below it the kind: effect, strength and speed (the classes of the wrap); turning the switch asks whether the sections that follow the template follow it. A section: Layout, Background (the colour, the border - none, thin, medium or strong, with a switch for the brand colour that waits for a strength -, the picture), Viewport animation and CSS classes - its animation is like the template, off or its own. A column: Layout (the vertical and the horizontal alignment on one line, the gap below), Background (the ground it paints - counter sets it off from its section -, the picture behind it and whether that is dimmed), Loop, Viewport animation and CSS classes. A component: Content, Properties and CSS classes.',
				'de_DE' => 'Das Template: Name und Datei, Rahmen und Animation - Sections animieren, darunter eingeklappt die Art: Effekt, Stärke und Speed (die Klassen des Wraps); wer den Schalter umlegt, wird gefragt, ob die Sections, die dem Template folgen, mitziehen. Eine Section: Layout, Hintergrund (die Farbe, der Rahmen - keiner, dünn, mittel oder stark, mit einem Schalter für die Markenfarbe, der auf eine Stärke wartet -, das Bild), Viewport-Animation und CSS-Klassen - ihre Animation ist wie das Template, aus oder eigene. Eine Spalte: Layout (die vertikale und die horizontale Ausrichtung in einer Zeile, darunter der GAP), Hintergrund (die Fläche, die sie malt - abgesetzt hebt sie von ihrer Section ab -, das Bild dahinter und ob es abgedunkelt wird), Loop, Viewport-Animation und CSS-Klassen. Eine Komponente: Inhalt, Eigenschaften und CSS-Klassen.',
			],
			'Cards of a loop' => [
				'en_US' => 'The Loop tab of a column with a grid loop has Cards: None, a cell is the plain div it was; Card makes it a card on the ground it sits on, Counter or a ground a card on that ground. It is the attribute article of the loop, written only where it is set. A loop with no grid, or whose kind has no such attribute, has no such row.',
				'de_DE' => 'Der Tab Loop einer Spalte mit Raster-Loop hat Karten: Keine lässt eine Zelle das schlichte div, das sie war; Karte macht sie zur Karte auf der Fläche, auf der sie steht, Abgesetzt oder eine Fläche zur Karte auf dieser Fläche. Es ist das Attribut article des Loops, nur geschrieben, wo es gesetzt ist. Ein Loop ohne Raster oder ohne dieses Attribut hat die Zeile nicht.',
			],
			'Components by place' => [
				'en_US' => 'The button + Component offers the components that belong where the column is: in the cell of a loop those a loop takes, in the first section of the page when it has a picture (the hero), and in any other section. A component that names the places it belongs to - the titles do - is offered there and nowhere else; one that names none is offered everywhere. The Builder knows no component by name: it reads this from the registry.',
				'de_DE' => 'Der Knopf + Komponente bietet die Komponenten an, die dorthin gehören, wo die Spalte steht: in der Zelle eines Loops die, die ein Loop nimmt, in der ersten Section der Seite, wenn sie ein Bild hat (der Hero), und in jeder anderen Section. Eine Komponente, die ihre Orte nennt - die Titel tun es -, wird dort angeboten und sonst nirgends; eine, die keinen nennt, überall. Der Builder kennt keine Komponente beim Namen: Er liest es aus dem Register.',
			],
			'Placeholders' => [
				'en_US' => 'A component is a placeholder in the preview: its image and its name, or the first line of its text. A component that says what it draws has a second line under it, in the code font: the tag and the class - H3 .nino-section-title for a title of level 3.',
				'de_DE' => 'Eine Komponente ist in der Vorschau ein Platzhalter: ihr Bild und ihr Name oder die erste Zeile ihres Textes. Eine Komponente, die sagt, was sie zeichnet, hat darunter eine zweite Zeile in der Code-Schrift: das Tag und die Klasse - H3 .nino-section-title für einen Titel der Ebene 3.',
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
				'en_US' => 'Under the source of a picture, and in the Background tab of a section or a column, stands the choice of a file: the first picture makes the image slot at once and stores the picture, as the Image Slots and Images panels do; it does not wait for the save. The alternative text in the language of the picker stands beside it: it is stored with the slot at once, or, for a slot that only the save makes, with it. Slots need the permission of the Image Slots panel, pictures and alternative texts that of the Images panel.',
				'de_DE' => 'Unter der Quelle eines Bildes und im Tab Hintergrund einer Section oder einer Spalte steht die Wahl einer Datei: Das erste Bild legt den Bildplatz sofort an und speichert das Bild, wie es die Panels Bildplätze und Bilder tun; es wartet nicht aufs Speichern. Der Alternativtext in der Sprache der Sprachwahl steht daneben: Er wird sofort beim Bildplatz gespeichert, bei einem Bildplatz, den erst das Speichern anlegt, mit ihm. Bildplätze brauchen die Berechtigung des Panels Bildplätze, Bilder und Alternativtexte die des Panels Bilder.',
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
