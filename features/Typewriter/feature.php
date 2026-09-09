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
