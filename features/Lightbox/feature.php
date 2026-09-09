<?php
// features/Lightbox/feature.php - what the Features panel reads. The class
// is not declared here: features/Lightbox/ can only ever serve
// \Nino\Modules\Lightbox.
return [
	'key'					=> 'lightbox',
	'name'				=> [ 'en_US' => 'Lightbox', 'de_DE' => 'Lightbox' ],
	'description'	=> [
		'en_US' => 'Opens any link to an image full screen, with the group it belongs to as a set - arrows, swipe, captions and a focus trap, and no library.',
		'de_DE' => 'Öffnet jeden Link auf ein Bild bildschirmfüllend, mit seiner Gruppe als Serie - Pfeile, Wischen, Bildunterschriften und ein Fokusrahmen, ohne Bibliothek.',
	],
	'manual'			=> [
		'en_US' => <<<'TXT'
			A link to an image opens full screen as soon as it carries
			`data-lightbox`:

			`<a href="/images/pass.jpg" data-lightbox="trip"><img
			src="/images/pass.200x200.jpg" alt="Above the pass"></a>`

			Links sharing one value are one set - arrows, swipe and the keyboard
			move between them. The caption is `data-caption`, else the image's
			`alt`, else the link's `title`. There is nothing to switch on: a link
			without the attribute is left to the browser.
			TXT,
		'de_DE' => <<<'TXT'
			Ein Link auf ein Bild öffnet sich bildschirmfüllend, sobald er
			`data-lightbox` trägt:

			`<a href="/images/pass.jpg" data-lightbox="trip"><img
			src="/images/pass.200x200.jpg" alt="Über dem Pass"></a>`

			Links mit demselben Wert sind eine Serie – Pfeile, Wischen und die
			Tastatur gehen zwischen ihnen. Die Bildunterschrift ist `data-caption`,
			sonst das `alt` des Bildes, sonst das `title` des Links. Einzuschalten
			gibt es nichts: Ein Link ohne das Attribut bleibt dem Browser
			überlassen.
			TXT,
	],
	// It changes how something already on the page behaves and brings
	// nothing of its own to show
	'category'		=> 'ui',
	'version'			=> '1.0.0',
	'nino'				=> '^1.1',
	'requires'		=> [],
	// Nothing: what it opens is markup the page already carries
	'data'				=> [],
	// No settings either, and for the reason typewriter.js has none: the two
	// files are static assets that never pass the fill engine (see
	// docs/development.md, "Assets Are Not Templates"), so a site-wide value
	// could not reach them. What one lightbox does differently from another
	// is a data attribute on the link - see README.md
	'settings'		=> [],
];
