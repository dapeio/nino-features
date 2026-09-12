<?php
declare(strict_types=1);

/*	The install unit - what \Nino\Features::activate() applies, once, when the
	feature is switched on. There is no install hook and no activate() to
	write: this file *is* the installer, and everything in it is add-only. A
	key, a route, a template or a text the project already has is left exactly
	as it is, so activating twice changes nothing and an operator's own edits
	are never overwritten.

	The whole vocabulary \Nino\Features::applyUnit() reads (see
	docs/features.md, "The Install Unit"):

		routes					entries for config.php's /nino/http/routes
		templates				files from install/templates/ into the project's
		files						anything else, copied verbatim
		elementTypes		element types to create
		blacklist				config keys the workbench must not show
		config					defaults merged into config.php

	...plus install/text/<locale>.php beside this file, whose keys are merged
	into the project's own text/<locale>.php for every locale it has.

	And that is all of it. In particular there is no 'moduleClass' here: the
	class comes from the directory name, so features/Hello/ can only ever
	serve \Nino\Modules\Hello, and activation adds it to /nino/modules
	without being told. (Nino's *own* modules under _nino/Nino/Modules/ carry
	install units that do declare one - those are read by the setup wizard,
	not by this path, and a key from one of them here does nothing.)

	Hello ships two things: the page /hello renders, and the words that page
	and [hello] are written in. The route itself is deliberately not here -
	Hello::init() registers it at runtime, so it vanishes when the feature is
	deactivated. A route written here would stay behind as the project's own,
	which is what you want for a page an editor is meant to keep and not what
	you want for one that only works while your code is there.	*/
return [

	/*	Copied from install/templates/ into the project's templates/, and only
		where no file of that name is there yet. From then on it is the
		project's file: an editor may rewrite it, and the next version of this
		feature will not take it back	*/
	'templates' 	=> [ 'page-hello.tpl' ],
];
