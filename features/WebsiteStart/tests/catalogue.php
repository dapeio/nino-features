<?php
declare(strict_types=1);

/**
 *	Nino features
 *	catalogue.php	What the tests of the catalogue need to know about this feature (tests/declarations.php says the keys)
 */
return [
	// Its pages are the routes of its unit, not of its code
	'pages'   => [ '/home' => 'install/manifest.php', '/contact' => 'install/manifest.php', '/404' => 'install/manifest.php' ],
];
