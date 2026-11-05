<?php
// features/Logos/install/logo.php - the element type a logo is, copied to
// /elements/logo.php where a project has none.
//
// Four fields, all global: the name of a customer and its address are the same
// in every language, and a type file is copied byte for byte. 'name' is the alt
// text of the picture. 'image' is cropped to the box it declares when it is
// uploaded - 540 by 210 pixels, the ratio of a row of logos at its middle height
// (18 to 7) - so a logo that is not of that ratio loses its edges, and one that
// is keeps all of them. 'image-dark' is the picture for a dark ground and is
// optional: [logos] draws it beside the first, and the ground it stands on says
// which of the two is seen. 'url' is where a click goes, left empty for a logo
// that links nowhere. 'order' is the position: [logos] sorts by it, the smaller
// first, and a logo with none, an empty one or 0 stands last, in the order of the
// elements - the Elements panel saves a position that was left empty as 0, so 0
// and empty are the same here.
// [logos sort="name"] asks for another.
//
// No element is shipped: a logo is a customer's picture, and one that is not
// theirs is one they have to find and delete.
return [
	'title' => 'Logos',
	'model' => [
		'name' => [
			'type' 			=> 'string',
			'required' 	=> true,
			'maxlength' => 120,
		],
		'image' => [
			'type' 		=> 'image',
			'width' 	=> 540,
			'height' 	=> 210,
		],
		'url' => [
			'type' 			=> 'string',
			'maxlength' => 500,
		],
		'image-dark' => [
			'type' 		=> 'image',
			'width' 	=> 540,
			'height' 	=> 210,
		],
		'order' => [
			'type' 			=> 'integer',
		],
	],
	'*' => [
		'*' => [],
	],
];
