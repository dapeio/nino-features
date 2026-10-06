<?php return [
	'name' => 'Static, table',
	'description' => 'A real table between an editable intro and outro. Insert it, then shape the rows in HTML+.',
	'category' => 'Static',
	'tags' => [ 'table', 'rows', 'static', 'html', 'prices', 'opening hours', 'specs' ],
	'version' => 3,
	'weight' => 90,
	'recommend' => [
		'layout' => 'default-demo',
		'frame' => [ 'background' => 'default', 'container' => 'default', 'padding' => 'default', 'margin' => 'none' ],
	],
	'layouts' => [
		'default-demo' => [ 'label' => 'Plain table · demo rows', 'template' => 'section-default-demo.tpl' ],
		'default-elements' => [ 'label' => 'Plain table · elements loop', 'template' => 'section-default-elements.tpl' ],
		'striped-demo' => [ 'label' => 'Striped table · demo rows', 'template' => 'section-striped-demo.tpl' ],
		'striped-elements' => [ 'label' => 'Striped table · elements loop', 'template' => 'section-striped-elements.tpl' ],
	],
	'areas' => [
		'intro' => [
			'label' => 'Intro',
			'labelKey' => '/_admin/templates/area/intro',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'html' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mb-3' ],
			'styles' => [
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
			],
			'recommend' => [ 'style' => 'left', 'components' => [
				[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
				[ 'id' => 'subtitle', 'type' => 'subtitle', 'bindings' => [ 'text' => 'subtitle' ] ],
			] ],
			'render' => [ 'title' => [ 'tag' => 'h2', 'class' => 'nino-section-title' ], 'subtitle' => [ 'class' => 'nino-section-subtitle' ] ],
		],
		'outro' => [
			'label' => 'Outro',
			'labelKey' => '/_admin/templates/area/outro',
			'source' => 'single',
			'allowed' => [ 'button', 'description', 'text', 'html', 'template' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mt-3' ],
			'styles' => [
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
				'right' => [ 'label' => 'Right', 'class' => 'nino-text-right' ],
			],
			'recommend' => [ 'style' => 'left', 'components' => [] ],
		],
	],
	// What the preview shows for the fills this section does not create -
	// the fields its loop repeats and the project texts its layout writes
	// in. %n is the item's number, a list gives each item its own
	'samples' => [
		'columnA' => 'Service %n',
		'columnB' => [ '45 min', '60 min', '75 min' ],
	],
];
