<?php return [
	'name' => 'Template include',
	'description' => 'Place one reusable .tpl include inside a normal managed section.',
	'category' => 'Structure',
	'tags' => [ 'template', 'include', 'shortcode', 'reusable', 'form', 'navigation' ],
	'version' => 3,
	'weight' => 170,
	'recommend' => [
		'layout' => 'default',
		'frame' => [ 'background' => 'default', 'container' => 'default', 'padding' => 'none', 'margin' => 'none' ],
	],
	'layouts' => [
		'default' => [ 'label' => 'Reusable template', 'template' => 'section.tpl' ],
	],
	'areas' => [
		'include' => [
			'label' => 'Template',
			'labelKey' => '/_admin/templates/area/template',
			'source' => 'single',
			'allowed' => [ 'html', 'template' ],
			'maxComponents' => 1,
			'container' => [ 'class' => 'nino-grid-100' ],
			'recommend' => [ 'components' => [
				[ 'id' => 'template', 'type' => 'template', 'bindings' => [ 'path' => '' ] ],
			] ],
		],
	],
];
