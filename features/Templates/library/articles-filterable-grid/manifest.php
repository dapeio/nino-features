<?php return [
	'name' => 'Articles, grid with filters',
	'description' => 'A heading, a client-side category filter and repeatable cards for services or portfolio work.',
	'category' => 'Articles',
	'tags' => [ 'services', 'portfolio', 'filter', 'cards', 'grid', 'elements' ],
	'version' => 3,
	'weight' => 40,
	'recommend' => [
		'frame' => [ 'background' => 'alt', 'container' => 'wide', 'padding' => 'default', 'margin' => 'none' ],
		'layout' => 'default',
	],
	'layouts' => [
		'default' => [ 'label' => 'Heading, filter and cards', 'template' => 'section.tpl' ],
	],
	'areas' => [
		'heading' => [
			'label' => 'Title area',
			'labelKey' => '/_admin/templates/area/title-area',
			'source' => 'single',
			'allowed' => [ 'title', 'subtitle', 'description', 'html' ],
			'container' => [ 'class' => 'nino-grid-100 nino-mb-3' ],
			'styles' => [
				'left' => [ 'label' => 'Left', 'class' => 'nino-text-left' ],
				'center' => [ 'label' => 'Centered', 'class' => 'nino-text-center' ],
			],
			'recommend' => [
				'style' => 'center',
				'components' => [
					[ 'id' => 'title', 'type' => 'title', 'bindings' => [ 'text' => 'title' ] ],
					[ 'id' => 'subtitle', 'type' => 'subtitle', 'bindings' => [ 'text' => 'subtitle' ] ],
				],
			],
			'render' => [ 'title' => [ 'tag' => 'h2', 'class' => 'nino-section-title' ] ],
		],
		'elements' => [
			'label' => 'Elements',
			'labelKey' => '/_admin/templates/area/elements',
			'source' => 'elements',
			'allowed' => [ 'image', 'title', 'description', 'button', 'html' ],
			'item' => [
				'tag' => 'article',
				'class' => 'nino-article nino-article--alt nino-mb-3 nino-article--grid nino-filter-item',
				// [[category]] is plain text until the ordinary [elements] render
				// pass at request time substitutes it per card, exactly like
				// [[title]] below - see docs/recipes/section-preset.md, "Complete
				// manifest shape".
				'data' => [ 'filter-item' => '[[category]]' ],
			],
			'styles' => [
				'two-columns' => [ 'label' => '2 columns', 'class' => 'nino-grid-m-50' ],
				'three-columns' => [ 'label' => '3 columns', 'class' => 'nino-grid-m-33' ],
				'four-columns' => [ 'label' => '4 columns', 'class' => 'nino-grid-m-25' ],
			],
			'recommend' => [
				'style' => 'three-columns',
				'components' => [
					[ 'id' => 'image', 'type' => 'image',
					  'bindings' => [ 'src' => 'image', 'alt' => 'title' ] ],
					[ 'id' => 'title', 'type' => 'title',
					  'bindings' => [ 'text' => 'title' ] ],
					[ 'id' => 'description', 'type' => 'description',
					  'bindings' => [ 'text' => 'description' ] ],
					[ 'id' => 'action', 'type' => 'button', 'style' => 'link',
					  'bindings' => [ 'label' => 'linkLabel', 'href' => 'link' ] ],
				],
			],
			'typeTitle' => 'Services',
			'model' => [
				'title' => [ 'type' => 'string', 'locale' => true, 'required' => true, 'maxlength' => 180 ],
				'description' => [ 'type' => 'string', 'locale' => true, 'html' => true, 'maxlength' => 2000 ],
				'category' => [
					'type' => 'string',
					'required' => true,
					'options' => [ 'Consulting', 'Design', 'Development' ],
				],
				'linkLabel' => [ 'type' => 'string', 'locale' => true, 'maxlength' => 120 ],
				'link' => [ 'type' => 'string', 'maxlength' => 500 ],
				'image' => [ 'type' => 'image', 'width' => 1200, 'height' => 800 ],
			],
			// No limit - a filter bar wants the whole collection to filter
			// within, not just the first few cards.
			'shortcode' => [ 'locale' => '', 'callback' => '', 'limit' => -1, 'query' => '' ],
			'render' => [
				'image' => [ 'class' => 'nino-article-img nino-article-img--maxheight' ],
				'title' => [ 'tag' => 'h3', 'class' => 'nino-article-title' ],
				'description' => [ 'class' => 'nino-article-descr' ],
			],
		],
	],
	// What the preview shows for the fills this section does not create -
	// the fields its loop repeats and the project texts its layout writes
	// in. %n is the item's number, a list gives each item its own
	'samples' => [
		'title' => 'Thoughtful item %n',
		'description' => 'Useful supporting copy that gives this item enough context.',
		'category' => [ 'Consulting', 'Design', 'Development' ],
		'linkLabel' => 'Learn more',
		'link' => '#',
	],
];
