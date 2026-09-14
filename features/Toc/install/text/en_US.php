<?php
declare(strict_types=1);

// The two words a list carries - merged into the project's text/en_US.php
// once, at activation (add-only: a key the project already has stays);
// editors keep them current in the Text panel from then on. See README.md.
return [

	// What stands over the list, where the shortcode did not say
	'[[/toc/title]]'	=> 'On this page',

	// What the "#" beside a heading is, for somebody who reaches it with the
	// keyboard and cannot see that it is a link to the passage under it
	'[[/toc/anchor]]'	=> 'Link to this section',
];
