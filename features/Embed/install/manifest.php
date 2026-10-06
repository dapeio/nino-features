<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Embed ships no template, no route
// and no config default of its own - a project writes [embed ...] where an
// embed belongs, the way docs/recipes/feature.md's own example leaves a
// feature's markup to the project. All this unit carries is the four
// fills an unreleased embed reads - the button and the note on the
// surface, the link out without JavaScript and the frame's own name -
// merged into text/<locale>.php for every available locale, and the
// feature's sections of the privacy policy.
return [
	// Added to the Legal module's type, never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
