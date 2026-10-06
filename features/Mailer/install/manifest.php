<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Mailer ships no template, no route
// and no text of its own - it delivers what the kernel's Mail sends. All this
// unit carries is the feature's section of the privacy policy.
return [
	// Added to the Legal module's type, never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
