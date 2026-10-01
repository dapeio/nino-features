<?php return [
	'name' => 'Image, list split',
	'description' => 'A checked list of what is included, next to one supporting image.',
	'category' => 'Image',
	'tags' => [ 'features', 'checklist', 'benefits', 'services', 'split', 'elements' ],
	'version' => 3,
	'weight' => 70,
	'recommend' => [
		'layout' => 'media-right',
		'frame' => [ 'background' => 'alt', 'container' => 'wide', 'padding' => 'default', 'margin' => 'none' ],
	],
	'layouts' => [
		'media-right' => [ 'label' => 'Image right', 'template' => 'section-media-right.tpl' ],
		'media-left' => [ 'label' => 'Image left', 'template' => 'section-media-left.tpl' ],
	],
	'areas' => [
		'content' => [
			'label' => 'Content',
			'labelKey' => '/_admin/templates/area/content',
			'help' => 'The copy above the checked list.',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'html' ],
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
		'features' => [
			'label' => 'Checklist',
			'labelKey' => '/_admin/templates/area/checklist',
			'help' => 'One entry per line. The check mark comes from the list, not from the content.',
			'source' => 'elements',
			'allowed' => [ 'text', 'title' ],
			'item' => [ 'tag' => 'li', 'class' => '' ],
			'typeTitle' => 'Feature list',
			'model' => [
				'title' => [ 'type' => 'string', 'locale' => true, 'required' => true, 'maxlength' => 200 ],
			],
			'shortcode' => [ 'locale' => '', 'callback' => '', 'limit' => 5, 'query' => '' ],
			'render' => [ 'text' => [ 'tag' => 'span', 'class' => '' ], 'title' => [ 'tag' => 'strong', 'class' => '' ] ],
			'recommend' => [ 'components' => [
				[ 'id' => 'feature', 'type' => 'text', 'bindings' => [ 'text' => 'title' ] ],
			] ],
		],
		'media' => [
			'label' => 'Image',
			'labelKey' => '/_admin/templates/area/image',
			'help' => 'The image column. Layout controls which side it occupies.',
			'source' => 'single',
			'allowed' => [ 'image' ],
			'container' => [ 'class' => 'nino-grid-100 nino-grid-m-50 nino-img-cover' ],
			'recommend' => [ 'components' => [
				[ 'id' => 'image', 'type' => 'image', 'bindings' => [ 'src' => 'image', 'alt' => 'image-alt' ] ],
			] ],
			'render' => [ 'image' => [ 'class' => 'nino-img-cover', 'width' => 1200, 'height' => 900 ] ],
		],
	],
	// What the preview shows for the fills this section does not create -
	// the fields its loop repeats and the project texts its layout writes
	// in. %n is the item's number, a list gives each item its own
	'samples' => [
		'title' => 'Thoughtful item %n',
	],
];
