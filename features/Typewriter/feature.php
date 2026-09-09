<?php
// features/Typewriter/feature.php - what the Features panel reads. The class
// is not declared here: features/Typewriter/ can only ever serve
// \Nino\Modules\Typewriter.
return [
	'key'					=> 'typewriter',
	'name'				=> [ 'en_US' => 'Typewriter', 'de_DE' => 'Schreibmaschine' ],
	'description'	=> [
		'en_US' => 'Types the lines of a container one after the other, with a cursor at the writing head - a headline that writes itself, timed per element with data attributes.',
		'de_DE' => 'Tippt die Zeilen eines Containers nacheinander, mit Cursor am Schreibkopf - eine Überschrift, die sich selbst schreibt, je Element über data-Attribute getaktet.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			Give a container the class `nino-typewriter`; the `<p>` lines inside it
			are typed one after the other, with the cursor at the writing head.

			Every timing is a data attribute on that same container:
			`data-typewriter-speed` is milliseconds per character,
			`data-typewriter-hold` how long a finished line stands,
			`data-typewriter-exit="backspace"` erases instead of fading, and
			`data-typewriter-loop="0"` stops on the last line. It starts when the
			container scrolls into view, `data-typewriter-start="load"` right away.
			TXT,
		'de_DE' => <<<'TXT'
			Gib einem Container die Klasse `nino-typewriter`; die `<p>`-Zeilen darin
			werden nacheinander getippt, mit dem Cursor am Schreibkopf.

			Jede Taktung ist ein data-Attribut auf demselben Container:
			`data-typewriter-speed` sind Millisekunden je Zeichen,
			`data-typewriter-hold`, wie lange eine fertige Zeile stehen bleibt,
			`data-typewriter-exit="backspace"` löscht statt auszublenden, und
			`data-typewriter-loop="0"` hält auf der letzten Zeile an. Los geht es,
			wenn der Container ins Bild scrollt, mit `data-typewriter-start="load"`
			sofort.
			TXT,
	],
	// What it is for: it changes how what is already on the page behaves and
	// brings nothing of its own to write - the Features panel files it with
	// the sliders and the lightboxes rather than with the content
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Nothing under data/: the feature keeps no state at all - what it
	// animates is the markup a project's own template already carries
	'data'				=> [],
	// No settings either, and that is the design: every timing belongs to
	// the element that is being typed (data-typewriter-speed, -hold, -loop,
	// ... - see README.md), not to the site. typewriter.js is a static
	// asset, never rendered through the fill engine (docs/development.md,
	// "Assets Are Not Templates"), so a site-wide default here could not
	// reach it in the first place - the way consent.js has to carry its
	// cookie name through the banner's own data attributes
	'settings'		=> [],
];
