<?php
// Die eigenen Workbench-Texte des Design-Features, in seine Fills gemischt,
// solange das Feature aktiv ist (siehe text() des Panels) - dieselben
// Schlüssel und dieselbe Form wie text/<locale>.php der Workbench
return [
	'[[/_admin/nav/design]]'								=> 'Design',
	'[[/_admin/design/label/title]]'				=> 'Design',
	'[[/_admin/design/hint/intro]]'					=> 'Je ein Set pro Bauteil einer Seite. Ein ganzes Theme wählt hier niemand – laute Überschriften aus einem Entwurf und runde Buttons aus einem anderen ist der Sinn der Sache.',

	'[[/_admin/design/label/global]]'				=> 'Global',
	'[[/_admin/design/label/knob]]'					=> 'Finetuning',
	'[[/_admin/design/label/size]]'					=> 'Grundgröße',
	'[[/_admin/design/label/follow]]'				=> 'Wieder folgen lassen',
	'[[/_admin/design/label/save]]'					=> 'Auswahl speichern',
	'[[/_admin/design/label/apply]]'				=> 'Speichern und kompilieren',
	'[[/_admin/design/label/takeover]]'			=> 'Datei übernehmen und kompilieren',

	'[[/_admin/design/part/header]]'				=> 'Header',
	'[[/_admin/design/part/footer]]'				=> 'Footer',
	'[[/_admin/design/part/atf]]'						=> 'ATF (Titelbereich)',
	'[[/_admin/design/part/section]]'				=> 'Section',
	'[[/_admin/design/part/article]]'				=> 'Artikel',
	'[[/_admin/design/part/buttons]]'				=> 'Buttons',
	'[[/_admin/design/part/forms]]'					=> 'Formulare',
	'[[/_admin/design/part/lists]]'					=> 'Listen & Tabellen',
	'[[/_admin/design/part/blocks]]'				=> 'Bausteine',

	'[[/_admin/design/step/less]]'					=> '−1',
	'[[/_admin/design/step/default]]'				=> '0',
	'[[/_admin/design/step/more]]'					=> '+1',
	'[[/_admin/design/size/s]]'							=> 'klein',
	'[[/_admin/design/size/m]]'							=> 'normal',
	'[[/_admin/design/size/l]]'							=> 'groß',

	'[[/_admin/design/hint/knob]]'					=> 'Jedes Set erklärt für die Regler, auf die es hört, drei Stufen. Das Finetuning wählt eine davon – es rechnet nicht. Ein Bauteil folgt dem globalen Stand eines Reglers, bis jemand es hier bewegt; danach bleibt es, wo es steht.',
	'[[/_admin/design/hint/size]]'					=> 'Skaliert die ganze Seite über die Schriftgröße der Wurzel – als Prozentsatz der Browser-Voreinstellung der Besucherin, nie als feste Pixelzahl.',
	'[[/_admin/design/hint/frames]]'				=> 'Header und Footer bringen ihr eigenes Markup mit: Beim Kompilieren werden die beiden Templates des Projekts überschrieben.',

	'[[/_admin/design/state/current]]'			=> 'Die Datei entspricht dieser Auswahl.',
	'[[/_admin/design/state/drifted]]'			=> 'Die Auswahl ist gespeichert, aber noch nicht kompiliert – auf der Seite steht noch der vorige Stand.',
	'[[/_admin/design/state/missing]]'			=> 'Noch nie kompiliert.',
	'[[/_admin/design/state/foreign]]'			=> 'Die Datei %s stammt nicht von hier: entweder die ausgelieferte oder eine von Hand bearbeitete. Sie wird nicht überschrieben, solange Du es nicht ausdrücklich sagst.',
	'[[/_admin/design/state/compiled]]'			=> 'Zuletzt kompiliert: %s',

	'[[/_admin/design/msg/saved]]'					=> 'Auswahl gespeichert.',
	'[[/_admin/design/msg/applied]]'				=> 'Kompiliert. Die Seite sieht ab sofort so aus.',
	'[[/_admin/design/msg/takenover]]'			=> 'Datei übernommen und kompiliert. Von jetzt an schreibt Design sie.',
	'[[/_admin/design/error/save]]'					=> 'Die Auswahl konnte nicht gespeichert werden.',
	'[[/_admin/design/error/apply]]'				=> 'Es konnte nicht kompiliert werden.',

	'[[/_admin/design/label/preview]]'			=> 'Vorschau',
	'[[/_admin/design/label/width]]'				=> 'Breite',
	'[[/_admin/design/width/phone]]'				=> 'Telefon',
	'[[/_admin/design/width/tablet]]'				=> 'Tablet',
	'[[/_admin/design/width/desktop]]'			=> 'Desktop',
	'[[/_admin/design/label/reload]]'				=> 'Vorschau neu laden',
	'[[/_admin/design/label/jump]]'					=> 'Folgt dem Bauteil',
	'[[/_admin/design/msg/previewing]]'			=> 'Vorschau wird erstellt …',
	'[[/_admin/design/error/preview]]'			=> 'Die Vorschau konnte nicht erstellt werden.',
	'[[/_admin/design/preview/page]]'				=> 'Vorschau',

	'[[/_admin/design/label/picker]]'				=> 'Bauteil',
	'[[/_admin/design/label/variant]]'			=> 'Variante',
	'[[/_admin/design/label/state]]'				=> 'So wird es verwendet',

	'[[/_admin/design/knob/empty]]'					=> 'Diese Variante hört auf keinen Regler.',

	'[[/_admin/design/knob/volume/label]]'	=> 'Überschriften',
	'[[/_admin/design/knob/volume/note]]'		=> 'wie weit sie wachsen',
	'[[/_admin/design/knob/volume/less]]'		=> 'Ruhig',
	'[[/_admin/design/knob/volume/default]]'=> 'Standard',
	'[[/_admin/design/knob/volume/more]]'		=> 'Kräftig',

	'[[/_admin/design/knob/spacing/label]]'		=> 'Abstände',
	'[[/_admin/design/knob/spacing/note]]'		=> 'Lücken und Zeilenhöhe',
	'[[/_admin/design/knob/spacing/less]]'		=> 'Eng',
	'[[/_admin/design/knob/spacing/default]]'	=> 'Standard',
	'[[/_admin/design/knob/spacing/more]]'		=> 'Luftig',

	'[[/_admin/design/knob/shaping/label]]'		=> 'Ecken',
	'[[/_admin/design/knob/shaping/note]]'		=> 'wie rund',
	'[[/_admin/design/knob/shaping/less]]'		=> 'Scharf',
	'[[/_admin/design/knob/shaping/default]]'	=> 'Standard',
	'[[/_admin/design/knob/shaping/more]]'		=> 'Rund',

	'[[/_admin/design/knob/measure/label]]'		=> 'Breite',
	'[[/_admin/design/knob/measure/note]]'		=> 'wie breit der Inhalt läuft',
	'[[/_admin/design/knob/measure/less]]'		=> 'Schmal',
	'[[/_admin/design/knob/measure/default]]'	=> 'Standard',
	'[[/_admin/design/knob/measure/more]]'		=> 'Weit',
];
