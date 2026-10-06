<?php
declare(strict_types=1);

// The label of the pause button, merged into the project's own
// text/en_US.php at activation (add-only: a key the project already has is
// kept); from then on editors keep it in the Text panel. A container asks for
// the button with data-ticker-toggle="[[/feature/ticker/pause/label]]", and the
// value goes into the page as it stands - so it must not contain a double
// quote. See README.md.
return [
	'[[/feature/ticker/pause/label]]'	=> 'Pause animation',
];
