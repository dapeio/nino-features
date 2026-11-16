<?php
declare(strict_types=1);

// The words of the API of the workbench, one file per interface language (see Admin::text()): the rail entry and the sentence
// of each failure a person can cause, %s where a param goes
return [

	'[[/_admin/themes/nav]]'						=> 'Themes',
	'[[/_admin/themes/panel/active]]'			=> 'The website shows the theme',
	'[[/_admin/themes/panel/none]]'				=> 'none - the standard parts',
	'[[/_admin/themes/panel/settings]]'		=> 'The settings of the themes come with the screen of this panel.',

	'[[/_admin/error/themes_unknown]]'			=> 'There is no theme "%s".',
	'[[/_admin/error/themes_mix]]'					=> 'The mix is not right: %s',
	'[[/_admin/error/themes_brand]]'				=> 'The brand value "%s" is not right.',
	'[[/_admin/error/themes_not_own]]'			=> 'A theme of a package is not changed. Save it as an own theme instead.',
	'[[/_admin/error/themes_name]]'					=> 'The name needs at least one letter or digit.',
	'[[/_admin/error/themes_name_taken]]'		=> 'There is an own theme called "%s" already.',
	'[[/_admin/error/themes_limit]]'				=> 'There can be %s own themes at most.',
	'[[/_admin/error/themes_active]]'				=> 'The theme "%s" is the one of the website and is not deleted.',
	'[[/_admin/error/themes_nothing]]'			=> 'There is no last state yet.',
	'[[/_admin/error/themes_foreign]]'			=> 'The stylesheet of the project was not written by this feature. Replace it only if the last state may keep it.',
	'[[/_admin/error/themes_busy]]'					=> 'The themes are being changed by another request. Try once more in a moment.',
];
