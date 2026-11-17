<?php
// The class is not declared: features/Collections/ can only serve \Nino\Modules\Collections
return [
	'key'					=> 'collections',
	'name'				=> [ 'en_US' => 'Collections', 'de_DE' => 'Sammlungen' ],
	'description'	=> [
		'en_US' => 'Collections draws a list over the elements of a type, as components: a loop of any components, a list, a table and a filter. Cards in a row, a table of prices or the rows of a team page are written as a call, with the fields of each element in it.',
		'de_DE' => 'Sammlungen zeichnet eine Liste über die Elemente eines Typs als Komponenten: eine Schleife über beliebige Komponenten, eine Liste, eine Tabelle und einen Filter. Karten in einer Zeile, eine Preistabelle oder die Zeilen einer Teamseite schreibst Du als Aufruf, mit den Feldern jedes Elements darin.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[collection /services sort="title" limit="6"]' => [
				'en_US' => 'Draws what it holds once for each element of the type, the fields read with .name; empty="…" is the text where there is none.',
				'de_DE' => 'Zeichnet, was darin steht, einmal für jedes Element des Typs, die Felder mit .name gelesen; empty="…" ist der Text, wenn es keines gibt.',
			],
			'[collection-list /services variant="check"]' => [
				'en_US' => 'A list with one row for each element, drawn from what it holds; the variants are check, numbered and plain.',
				'de_DE' => 'Eine Liste mit einer Zeile je Element, gezeichnet aus dem, was darin steht; die Varianten sind check, numbered und plain.',
			],
			'[collection-table /services caption="Prices"]' => [
				'en_US' => 'A table with one row for each element and one cell in it for each collection-col; the box round it scrolls where the page is narrow.',
				'de_DE' => 'Eine Tabelle mit einer Zeile je Element und darin einer Zelle je collection-col; der Rahmen um sie scrollt, wo die Seite schmal ist.',
			],
			'[collection-col .price title="~price" align="end"]' => [
				'en_US' => 'A column of the table: the field of the cell, the heading from a key or a text, the alignment and rowhead for cells that name the row.',
				'de_DE' => 'Eine Spalte der Tabelle: das Feld der Zelle, der Kopf aus einem Schlüssel oder einem Text, die Ausrichtung und rowhead für Zellen, die die Zeile benennen.',
			],
			'[collection /services][button href="/leistungen/[[.slug]]" text="Mehr zu [[title]]"][/collection]' => [
				'en_US' => 'The fields of the element are read in the attributes of what a collection holds too: the slug in a link, the title in a text.',
				'de_DE' => 'Die Felder des Elements werden auch in den Attributen dessen gelesen, was eine Sammlung umschließt: der Slug in einem Link, der Titel in einem Text.',
			],
			'[collection /team][image .photo][/collection]' => [
				'en_US' => 'The image field of the element is drawn as a picture, with the alt text its model names.',
				'de_DE' => 'Das Bildfeld des Elements wird als Bild gezeichnet, mit dem Alt-Text, den sein Modell nennt.',
			],
			'[collection-filter by=".category"]' => [
				'en_US' => 'Holds a collection, a list or a table and shows a button for each value of the field, which hides the rows of the others in the browser; all="…" labels the first one.',
				'de_DE' => 'Umschließt eine Sammlung, eine Liste oder eine Tabelle und zeigt für jeden Wert des Felds einen Knopf, der die Zeilen der anderen im Browser ausblendet; all="…" beschriftet den ersten.',
			],
		],
		'markup' => [
			'data-collection-value' => [
				'en_US' => 'The attribute that marks a row of a filtered collection with the value of its field; the script reads it.',
				'de_DE' => 'Das Attribut, das eine Zeile einer gefilterten Sammlung mit dem Wert ihres Felds kennzeichnet; das Skript liest es.',
			],
			'data-order' => [
				'en_US' => 'The attribute of the filter that lists the values in the order of the kernel\'s sort in the language of the page; the script puts the buttons in it.',
				'de_DE' => 'Das Attribut des Filters, das die Werte in der Ordnung der Sortierung des Kernels in der Sprache der Seite aufführt; das Skript stellt die Knöpfe danach.',
			],
			'lang' => [
				'en_US' => 'The row of a list for an element whose text is all from another language than the page\'s carries that language as its lang.',
				'de_DE' => 'Die Zeile einer Liste für ein Element, dessen Text ganz aus einer anderen Sprache als der der Seite stammt, trägt diese Sprache als lang.',
			],
			'sort, query' => [
				'en_US' => 'Read as [elements] reads them, but a field name or a character the kernel never uses ends the list: a query that cannot be read selects nothing.',
				'de_DE' => 'Gelesen wie bei [elements], aber ein Feldname oder ein Zeichen, das der Kernel nie nutzt, beendet die Liste: Eine Abfrage, die sich nicht lesen lässt, wählt nichts aus.',
			],
		],
		'routes' => [],
		'panel' => [],
		'callbacks' => [],
		'install' => [
			'blocks/<key>/' => [
				'en_US' => 'Two blocks for the Builder, a list and a table over elements, which show the example elements until you choose what they show.',
				'de_DE' => 'Zwei Blocks für den Builder, eine Liste und eine Tabelle über Elemente, die die Beispielelemente zeigen, bis Du wählst, was sie zeigen.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Puts the four words of the filter and the table - All, Filter, Showing, Table - into the Text panel, for the buttons and the names a screen reader reads.',
				'de_DE' => 'Bringt die vier Worte von Filter und Tabelle - Alle, Filter, Angezeigt, Tabelle - ins Panel Texte, für die Knöpfe und die Namen, die ein Screenreader liest.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '1.0.0',
	'nino'				=> '^2.0',					// the kernel with the register of components
	'php'					=> [],
	'requires'		=> [],
	'data'				=> [],
	'settings'		=> [],
	'assets'			=> [ '/features/Collections/assets/collection-filter.js' ],
];
