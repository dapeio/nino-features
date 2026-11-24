<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"): the words of the components, the
// script and the two mails, and the section of the privacy policy. No page and no route: the two components stand where the Builder puts
// them, on a page behind the protected paths of the Members feature, and the two endpoints are registered by Files::init(). Nothing is
// filled at request time as a text key - the request-time values of the components and of the mails are bare tokens the class fills -
// so there is no blacklist
return [
	// The feature's section of the privacy policy, added to the Legal module's type and never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements' => [ 'privacy' => 'elements/privacy.php' ],
];
