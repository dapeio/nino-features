<?php
// features/ProtectedArea/install/manifest.php - the password form's own
// page template. No route: the gate in ProtectedArea.php renders it inline (see
// ProtectedArea::callbackGate()/_answerForm()), the way a route callback
// would, but for every protected uri at once rather than one of its own.
return [
	'templates' => [ 'page-protected.tpl' ],
	// The feature's section of the privacy policy, added to the Legal module's
	// type and never replacing a section - see \Nino\Elements::seed(). A Nino
	// without the module reads no such key
	'elements' => [ 'privacy' => 'elements/privacy.php' ],
	// The template's [[/feature/protected/form/return]] is filled by the gate at request
	// time (see ProtectedArea::_answerForm()) and no text file ever answers
	// it: blacklisted, so the Text panel's scan does not report it as a gap
	'blacklist' => [ '/feature/protected/form/return' ],
];
