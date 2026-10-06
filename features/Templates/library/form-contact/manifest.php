<?php return [
	'name' => 'Form, contact',
	'description' => 'The project contact form, either centered on its own or beside the company details.',
	'category' => 'Forms',
	'tags' => [ 'contact', 'form', 'message', 'email', 'address', 'static' ],
	'version' => 3,
	'weight' => 160,
	'recommend' => [
		'layout' => 'split',
		'frame' => [ 'background' => 'default', 'container' => 'default', 'padding' => 'default', 'margin' => 'none' ],
	],
	'layouts' => [
		'centered' => [ 'label' => 'Centered form', 'template' => 'section-centered.tpl' ],
		'split' => [ 'label' => 'Details beside the form', 'template' => 'section-split.tpl' ],
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
		'/template/common/form/name' => 'Your name',
		'/template/common/form/email' => 'Your email',
		'/template/common/form/message' => 'Your message',
		'/template/common/form/required' => 'Required fields',
		'/template/common/form/submit' => 'Send message',
		'/project/company/general/name' => 'Example Company',
		'/project/company/contact/address' => 'Example Street 12<br>12345 Example City',
		'/project/company/contact/email' => 'hello@example.com',
		'/project/company/contact/phone' => '+49 123 456789',
	],
];
