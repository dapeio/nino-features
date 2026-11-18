<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"): the page the
// gate answers with, the words of the form and of the page, and the section of the privacy policy. No route
// of the page's own: the gate in ProtectedArea.php renders the template for every protected address at once
return [
	'templates' => [ 'page-protected.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's type and never replacing a
	// section - see \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements' => [ 'privacy' => 'elements/privacy.php' ],
];
