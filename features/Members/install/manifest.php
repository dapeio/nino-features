<?php
declare(strict_types=1);

// The unit \Nino\Features::activate() applies, add-only (see docs/features.md, "The install unit"): the two pages of the
// feature - the login page and the page that asks a link to be confirmed -, the words of the forms, the pages and the
// mails, and the section of the privacy policy. No route: the gate in Members.php answers with the login page in place of a
// protected page, the link of a mail answers with the confirm page, and a link that does not lead in answers with the
// login page (see Members::callbackGate(), callbackLoginLink() and _refuse()). A project that wants an address of its own for
// one of them gives it a route in the Routes panel. Nothing is filled at request time as a text key - the request-time values of
// the components are bare tokens the class fills, and the words the components choose between are all in the text files - so
// there is no blacklist
return [
	'templates' => [ 'page-members-login.tpl', 'page-members-confirm.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's type and never replacing a section - see
	// \Nino\Elements::seed(). A Nino without the module reads no such key
	'elements' => [ 'privacy' => 'elements/privacy.php' ],
];
