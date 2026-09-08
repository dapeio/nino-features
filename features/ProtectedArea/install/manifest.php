<?php
// features/ProtectedArea/install/manifest.php - the password form's own
// page template. No route: the gate in ProtectedArea.php renders it inline (see
// ProtectedArea::callbackGate()/_answerForm()), the way a route callback
// would, but for every protected uri at once rather than one of its own.
return [
	'templates' => [ 'page-protected.tpl' ],
];
