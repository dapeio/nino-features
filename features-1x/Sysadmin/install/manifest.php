<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"). The words
// of the mail come from install/text/<locale>.php, which the activation merges into the project's text files
// of every language the site has; the unit lists no template and no route - the mail is made of the feature's own
// template and nothing a visitor can open.
return [
	// Added to the Legal module's type, never replacing a section - see \Nino\Elements::seed(). A Nino
	// without the module reads no such key
	'elements'	=> [ 'privacy' => 'elements/privacy.php' ],
];
