<?php
declare(strict_types=1);

return [
	'label' => [ 'de_DE' => 'Inhaltsverzeichnis', 'en_US' => 'Table of contents' ],
	'knobs' => [
		'toc.indent' => [ 'token' => '--toc-indent', 'label' => [ 'de_DE' => 'Einzug der Unterebene', 'en_US' => 'Indent of the sub-level' ],
			'ladder' => [ '0rem', 'var(--page-space-1)', 'var(--page-space-2)', 'calc( var(--page-space-2) * 1.5 )', 'var(--page-space-3)' ] ],
		'toc.gap'    => [ 'token' => '--toc-gap', 'label' => [ 'de_DE' => 'Abstand der Einträge', 'en_US' => 'Space between the entries' ],
			'ladder' => [ '0em', '.2em', '.35em', '.6em', '1em' ] ],
	],
];
