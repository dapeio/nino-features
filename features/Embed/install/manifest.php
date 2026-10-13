<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The install unit"). Embed ships no template, no route
// and no config default of its own - a project writes [embed ...] where an
// embed belongs, and the markup is the project's to place. All this unit
// carries is the four fills an unreleased embed reads - the button and the
// note on the surface, the link out without JavaScript and the frame's own
// name - merged into text/<locale>.php for every available locale, and the
// feature's sections of the privacy policy.
return [
	// Added to the Legal module's type, never replacing a section - see
	// \Nino\Elements::seed(). A project without that module has no such type,
	// and the unit then adds nothing
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
