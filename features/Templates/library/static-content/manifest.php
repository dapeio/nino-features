<?php return [
	'name' => 'Static, content',
	'description' => 'A flexible heading, body and action section for editorial page content.',
	'category' => 'Static',
	'tags' => [ 'content', 'text', 'intro', 'heading', 'cta', 'template', 'flexible' ],
	'version' => 3,
	'weight' => 110,
	'recommend' => [
		'layout' => 'default',
		'frame' => [ 'background' => 'default', 'container' => 'default', 'padding' => 'default', 'margin' => 'none' ],
	],
	'layouts' => [
		'default' => [ 'label' => 'Heading, body and action', 'template' => 'section.tpl' ],
	],
	'areas' => [
		'heading' => [
			'label' => 'Heading',
			'labelKey' => '/_admin/templates/area/heading',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'html' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mb-3' ],
			'styles' => [
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
				'right' => [ 'label' => 'Right', 'class' => 'nino-text-right' ],
			],
			'recommend' => [ 'style' => 'left', 'components' => [
				[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
				[ 'id' => 'subtitle', 'type' => 'subtitle', 'bindings' => [ 'text' => 'subtitle' ] ],
			] ],
			'render' => [ 'title' => [ 'tag' => 'h2', 'class' => 'nino-section-title' ] ],
		],
		'body' => [
			'label' => 'Body',
			'labelKey' => '/_admin/templates/area/body',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'text', 'image', 'html', 'template' ],
			'container' => [ 'class' => 'nino-grid-100' ],
			'recommend' => [ 'components' => [
				[ 'id' => 'content', 'type' => 'text', 'style' => 'loud', 'bindings' => [ 'text' => 'content' ] ],
			] ],
		],
		'action' => [
			'label' => 'Action',
			'labelKey' => '/_admin/templates/area/action',
			'source' => 'single',
			'allowed' => [ 'description', 'button', 'html', 'template' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mt-3' ],
			'styles' => [
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
				'right' => [ 'label' => 'Right', 'class' => 'nino-text-right' ],
			],
			'recommend' => [ 'style' => 'left', 'components' => [] ],
		],
	],
];
