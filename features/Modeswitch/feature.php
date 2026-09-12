<?php
// features/Modeswitch/feature.php - what the Features panel reads. The class
// is not declared here: features/Modeswitch/ can only ever serve
// \Nino\Modules\Modeswitch.
return [
	'key'					=> 'modeswitch',
	'name'				=> [ 'en_US' => 'Light/Dark Switch', 'de_DE' => 'Hell/Dunkel-Schalter' ],
	'description'	=> [
		'en_US' => 'Lets a visitor read the site light, dark, or the way their system asks for it - a three-state switch put anywhere with [mode-switch]. The dark palette is the one the project already ships.',
		'de_DE' => 'Lässt Besucher die Seite hell, dunkel oder so lesen, wie ihr System es vorgibt - ein Schalter mit drei Zuständen, den [mode-switch] an eine beliebige Stelle setzt. Die dunkle Palette ist die, die das Projekt ohnehin mitbringt.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			Put `[mode-switch]` where the switch belongs - the header template is
			the usual place, the footer works as well. It renders three buttons:
			light, follow the system, dark.

			`[mode-switch icons]` leaves out the words and keeps the icons, which
			is what a crowded header bar usually wants.

			The switch writes `data-nino-mode` on the page and remembers the
			choice in the browser. Nothing on the server changes, and nothing is
			stored about the visitor anywhere else - so it needs no consent and
			works on a cached page.

			Dark is not something this feature invents: every Nino project's
			`assets/theme.css` already carries a dark palette for readers whose
			system asks for one. This is the control that lets a visitor say so
			themselves.
			TXT,
		'de_DE' => <<<'TXT'
			Setze `[mode-switch]` dorthin, wo der Schalter hingehört - das
			Header-Template ist der übliche Platz, der Footer tut es auch. Es
			rendert drei Schaltflächen: hell, dem System folgen, dunkel.

			`[mode-switch icons]` lässt die Wörter weg und behält die Symbole, was
			eine volle Kopfzeile meist besser gebrauchen kann.

			Der Schalter schreibt `data-nino-mode` auf die Seite und merkt sich die
			Wahl im Browser. Auf dem Server ändert sich nichts, und über den
			Besucher wird auch sonst nirgends etwas gespeichert - es braucht also
			keine Einwilligung, und auf einer gecachten Seite funktioniert es
			genauso.

			Dunkel erfindet dieses Feature nicht: die `assets/theme.css` jedes
			Nino-Projekts bringt bereits eine dunkle Palette für Leser mit, deren
			System danach fragt. Das hier ist die Bedienung, mit der ein Besucher
			es selbst sagen kann.
			TXT,
	],
	// It adds a control to a page that changes how what is already there is
	// read - the Features panel files that with the sliders and the lightboxes
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	// 1.2 is where the dark half of the palette became part of what a project
	// is delivered with (assets/theme.css carries :root[data-nino-mode="dark"]
	// and the matching prefers-color-scheme block). Before that there is
	// nothing for this switch to switch
	'nino'				=> '^1.2',
	'requires'		=> [],
	// Nothing under data/: the choice belongs to the reader's browser and is
	// kept there. A site that stored it would be storing a preference about a
	// person, which is a consent question this feature deliberately does not
	// raise
	'data'				=> [],
	// ...and no settings. Which of the three the switch starts on is not the
	// site's decision - it is "whatever this reader chose last, and their
	// system until they choose". The only thing a project can vary is whether
	// the buttons carry their words, and that belongs to the one place the
	// switch is written (see manual: [mode-switch icons])
	'settings'		=> [],
];
