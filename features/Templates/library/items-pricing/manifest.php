<?php return [
	'name' => 'Items, pricing plans',
	'description' => 'One card per plan: name, price and what it includes, from an Elements collection.',
	'category' => 'Items',
	'tags' => [ 'pricing', 'plans', 'packages', 'price', 'cards', 'elements' ],
	'version' => 3,
	'weight' => 130,
	'recommend' => [
		'layout' => 'equal',
		'frame' => [ 'background' => 'alt', 'container' => 'default', 'padding' => 'default', 'margin' => 'none' ],
	],
	'layouts' => [
		'equal' => [ 'label' => 'Equal cards', 'template' => 'section-equal.tpl' ],
		'feature-middle' => [ 'label' => 'Three cards, middle one highlighted', 'template' => 'section-feature-middle.tpl' ],
		'four' => [ 'label' => 'Four equal cards', 'template' => 'section-four.tpl' ],
		'four-feature-first' => [ 'label' => 'Four cards below one full-width card', 'template' => 'section-four-feature-first.tpl' ],
		'four-feature-last' => [ 'label' => 'Four cards above one full-width card', 'template' => 'section-four-feature-last.tpl' ],
	],
	'areas' => [
		'heading' => [
			'label' => 'Title area',
			'labelKey' => '/_admin/templates/area/title-area',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'html' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mb-3' ],
			'styles' => [
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
			],
			'recommend' => [ 'style' => 'center', 'components' => [
				[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
				[ 'id' => 'subtitle', 'type' => 'subtitle', 'bindings' => [ 'text' => 'subtitle' ] ],
			] ],
			'render' => [ 'title' => [ 'tag' => 'h2', 'class' => 'nino-section-title' ] ],
		],
		'plans' => [
			'label' => 'Plans',
			'labelKey' => '/_admin/templates/area/plans',
			'source' => 'elements',
			'allowed' => [ 'title', 'price', 'description', 'button', 'html' ],
			'item' => [ 'tag' => 'div', 'class' => 'nino-pricing-item' ],
			'typeTitle' => 'Pricing plans',
			'model' => [
				'title' => [ 'type' => 'string', 'locale' => true, 'required' => true, 'maxlength' => 120 ],
				'price' => [ 'type' => 'string', 'maxlength' => 40 ],
				'suffix' => [ 'type' => 'string', 'locale' => true, 'maxlength' => 40 ],
				'description' => [ 'type' => 'string', 'locale' => true, 'html' => true, 'maxlength' => 1200 ],
				'linkLabel' => [ 'type' => 'string', 'locale' => true, 'maxlength' => 120 ],
				'link' => [ 'type' => 'string', 'maxlength' => 500 ],
			],
			'shortcode' => [ 'locale' => '', 'callback' => '', 'limit' => 3, 'query' => '' ],
			'render' => [
				'title' => [ 'tag' => 'h3', 'class' => 'nino-pricing-title' ],
				'price' => [ 'class' => 'nino-pricing-price' ],
				'description' => [ 'tag' => 'div', 'class' => '' ],
			],
			'recommend' => [ 'components' => [
				[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
				[ 'id' => 'price', 'type' => 'price', 'bindings' => [ 'value' => 'price', 'suffix' => 'suffix' ] ],
				[ 'id' => 'description', 'type' => 'description', 'bindings' => [ 'text' => 'description' ] ],
				[ 'id' => 'action', 'type' => 'button', 'style' => 'primary', 'bindings' => [ 'label' => 'linkLabel', 'href' => 'link' ] ],
			] ],
		],
	],
	// What the preview shows for the fills this section does not create -
	// the fields its loop repeats and the project texts its layout writes
	// in. %n is the item's number, a list gives each item its own
	'samples' => [
		'title' => 'Thoughtful item %n',
		'price' => [ '99', '149', '199' ],
		'suffix' => '€',
		'description' => 'Useful supporting copy that gives this item enough context.',
		'linkLabel' => 'Learn more',
		'link' => '#',
	],
];
