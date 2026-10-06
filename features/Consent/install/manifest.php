<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Consent ships no template, no
// route and no config default of its own - the site owner puts [consent]
// into the project's own page frame and [consent-settings] into the
// footer by hand, the way docs/recipes/feature.md's own example leaves a
// feature's markup to the project. All this unit carries is the banner's
// texts, merged into text/<locale>.php for every available locale, and the
// feature's section of the privacy policy.
return [
	// Added to the Legal module's type, never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
