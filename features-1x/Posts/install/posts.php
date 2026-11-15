<?php
// features/Posts/install/posts.php - the element type a section is, copied to
// /elements/posts.php where a project has none. Eight fields, and every one of
// them is a decision the feature's defaults already name: 'title', 'summary'
// and 'date' are what /data/posts.php points at.
//
// The shape is the one \Nino\Elements::insertElementType() writes: the model,
// then one bucket per locale plus '*' for what is not translated. Empty here -
// a blog starts empty, and a demo post in somebody's project is a demo post
// they have to find and delete.
return [
	'title' => 'Posts',
	'model' => [
		'title' => [
			'type' 			=> 'string',
			'locale' 		=> true,
			'required' 	=> true,
			'maxlength' => 200,
		],
		// What a list shows and what a search engine gets as the description.
		// Its own field rather than the first paragraph of the body: the
		// sentence that makes somebody click is rarely the one that opens the
		// article
		'summary' => [
			'type' 			=> 'string',
			'locale' 		=> true,
			'maxlength' => 400,
		],
		// The date is the publishing decision: a post dated in the future is
		// not published (see Posts::published()), and the default order is
		// newest first
		'date' => [
			'type' 			=> 'date',
			'required' 	=> true,
		],
		'author' => [
			'type' 			=> 'string',
			'maxlength' => 120,
		],
		'image' => [
			'type' 		=> 'image',
			'width' 	=> 1200,
			'height' 	=> 675,
		],
		'imageAlt' => [
			'type' 			=> 'string',
			'locale' 		=> true,
			'maxlength' => 200,
		],
		// Released for inline html, which is what an article body is. The
		// kernel sanitizes it on the way into a page either way
		'body' => [
			'type' 		=> 'string',
			'locale' 	=> true,
			'html' 		=> true,
		],
		'tags' => [
			'type' 		=> 'array',
		],
	],
	'*' => [
		'*' => [],
	],
];
