<?php
declare(strict_types=1);

// The words the site says - merged into the project's own text/en_US.php
// once, at activation (add-only: a key the project already has stays). From
// then on an editor keeps them current in the Text panel and never opens a
// feature directory. The panel's own words are a different set, in
// features/Hello/text/.
return [

	'[[/feature/hello/greeting/note]]'	=> 'Rendered by the Hello World feature.',

	'[[/template/page-hello/intro/title]]'	=> 'Hello World',
	'[[/template/page-hello/intro/text]]'	=> 'This page and the greeting below it came with a feature. Both are yours now: the words are in the Text panel, the page in templates/page-hello.tpl.',
];
