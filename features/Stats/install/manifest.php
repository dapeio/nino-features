<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see
// docs/features.md, "The Install Unit"). Stats ships no template, no route and
// no text of its own - the counter and the panel are all there is. All this
// unit carries is the feature's section of the privacy policy, which says
// that no personal data arise.
return [
	// Added to the Legal module's type, never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
