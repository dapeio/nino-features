<?php
// features/Posts/install/manifest.php - what a project gets when the feature
// is activated. A unit is applied without overwriting (see
// \Nino\Features::activate()), so a project that already has an element type
// called posts, or a template of these names, keeps every one of them.
//
// No routes: a section's two routes are registered per request by
// \Nino\Modules\Posts::init() out of /data/posts.php, so they follow the
// section rather than a copy of it made at install time - and they go away
// with the feature instead of leaving a path nothing answers.
//
// The list page is offered to the main menu, which is how a page that exists
// at run time gets into one: by its Element-URI, the key of the default section
// (Posts::INDEX_URI after Sections::DEFAULT_KEY). The kernel does it once, when
// the unit is first applied, and only where the project has a menu main; the
// entry is drawn while the feature is on, goes with it when it is removed, and
// a project that takes it out of the menu keeps it out.
return [
	'elementTypes'	=> [ 'posts.php' ],
	'templates'			=> [ 'page-posts.tpl', 'page-post.tpl' ],
	'menus'					=> [ 'main' => [ '/blog' ] ],
];
