<?php
declare(strict_types=1);

/**
 *	Nino features
 *	catalogue.php	What the tests of the catalogue need to know about this feature (tests/declarations.php says the keys)
 */
return [
	// The positions of its sections of the privacy policy
	'legal'   => [ 800, 899 ],
	// The pages it makes with a route of its own, and where its code makes the route
	'pages'   => [ '/hello' => 'Hello.php' ],
];
