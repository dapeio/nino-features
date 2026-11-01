<?php
declare(strict_types=1);

// The name of a picture that has neither an alt text nor a caption - merged into
// the project's text/en_US.php on every activation (add-only: a key the project
// already has stays, one it lacks is added). The two %s are the name of the
// album and the place of the picture in it, in that order: "Trip, picture 3".
// Without it a thumbnail would be a link with no name, and a screen reader
// would read the address of the picture instead.
return [
	'[[/feature/gallery/image/unnamed]]'	=> '%s, picture %s',
];
