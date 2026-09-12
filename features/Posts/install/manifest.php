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
return [
	'elementTypes'	=> [ 'posts.php' ],
	'templates'			=> [ 'page-posts.tpl', 'page-post.tpl' ],
];
