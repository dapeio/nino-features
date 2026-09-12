<?php
/*	features/Hello/feature.php - the manifest, and the first file to read.

	This is a complete, working feature that does one small thing, written to
	be copied. Every part of the contract appears exactly once, so the
	directory is also a checklist: a shortcode, a route, a panel, a setting,
	an install unit, text fills, an asset, stored data, an upgrade hook and a
	test. Delete what you do not need - none of it is required, and a feature
	that only registers a shortcode is a perfectly good feature.

	The companion to this directory is the recipe in Nino's own docs,
	docs/recipes/feature.md, which walks the same nine steps in prose. Where
	the two disagree, the recipe is the contract and this is one reading of it.

	To start your own: copy the directory, rename it, and rename the three
	things that have to agree with each other - the directory name, the 'key'
	below, and the class \Nino\Modules\Hello. The class is never named here:
	features/Hello/ can only ever serve \Nino\Modules\Hello, which is what
	makes a feature findable without a registry.	*/
return [

	/*	The key. Lowercase, one word, unique across the catalogue - it is what
		\Nino\Features::activate() and every setting lookup names, and what the
		panel's permission is built from. It never appears on screen	*/
	'key'					=> 'hello',

	/*	What a row in the Features panel says. Never the key, never the
		directory - so no two features may carry the same name in any locale,
		and bin/build.php refuses a pair that does. A plain string instead of an
		array is that name in every language	*/
	'name'				=> [ 'en_US' => 'Hello World', 'de_DE' => 'Hallo Welt' ],

	// One sentence, in the panel's list. What it does, not how
	'description'	=> [
		'en_US' => 'A complete feature that does one small thing, written to be copied: a shortcode, a route, a panel, a setting, an install unit and a test, each exactly once.',
		'de_DE' => 'Ein vollständiges Feature, das eine Kleinigkeit tut und zum Kopieren geschrieben ist: Shortcode, Route, Panel, Einstellung, Install-Einheit und Test, jedes genau einmal.',
	],

	/*	The manual the Features panel shows when a row is opened. One shape for
		every feature: a section per kind of thing a feature can add, each a
		handle - the shortcode, the route, the panel's name - and one line about
		it. Not prose, because what a developer does with this is look something
		up, and prose makes that a read rather than a glance.

		The sections are \Nino\Features::MANUAL_SECTIONS, and the panel draws
		every one of them including the ones left empty here: "no callbacks" is
		an answer, and a reader who does not find the question has to go and
		read the source to learn that the answer was nothing.

		Two sections nobody writes. The description comes from 'description'
		above and the settings from 'settings' below, with their own labels and
		hints - declared once and drawn from there, because the second place to
		write something is the place it drifts.

		The handle is not translated: it is what somebody types. Only the line
		beside it is, as a string or a locale => string map, exactly like the
		name and the description	*/
	'manual'			=> [

		'shortcodes' => [
			'[hello]' => [
				'en_US' => 'Greets the name the panel stores.',
				'de_DE' => 'Grüßt den Namen aus dem Panel.',
			],
			'[hello name="Ada"]' => [
				'en_US' => 'Greets whoever is named here instead.',
				'de_DE' => 'Grüßt stattdessen, wen Du hier nennst.',
			],
		],

		'routes' => [
			'/hello' => [
				'en_US' => 'A page of its own, installed with the feature.',
				'de_DE' => 'Eine eigene Seite, die mit dem Feature installiert wird.',
			],
		],

		'panel' => [
			'Hello World' => [
				'en_US' => 'One field: who the shortcode greets when it names nobody.',
				'de_DE' => 'Ein Feld: wen der Shortcode grüßt, wenn er niemanden nennt.',
			],
		],

		// Nothing registered through \Nino\Callbacks - and the panel says so,
		// which is the point of writing the section out empty
		'callbacks' => [],

		'install' => [
			'templates/page-hello.tpl' => [
				'en_US' => 'The page /hello renders. The project\'s own file afterwards.',
				'de_DE' => 'Die Seite, die /hello rendert. Danach die Datei des Projekts.',
			],
			'text/<locale>.php' => [
				'en_US' => 'Three words the site says, into the Text panel.',
				'de_DE' => 'Drei Worte, die die Seite sagt, ins Panel Texte.',
			],
		],
	],

	/*	One of six: content, ui, communication, marketing, security, system.
		It is what the Features panel groups and filters by. An example belongs
		with the tooling rather than with anything a site needs	*/
	'category'		=> 'system',

	// The feature's own version, semver. It is what the panel records on
	// activation and what upgrade() is told when it changes (see Hello.php)
	'version'			=> '1.0.0',

	/*	Which Nino this is written for, as a composer-style constraint. ^1.1
		is "1.1 or later, below 2" - the release that brought the feature
		contract this uses. Be honest here: a feature that names a kernel it
		does not run on is refused at activation, which is the good case; one
		that names an older kernel than it needs fails later and worse	*/
	'nino'				=> '^1.1',

	/*	Other features this one cannot run without, by key. They have to be
		active before this can be activated, and deactivating one of them
		warns about this. An empty list is the common case	*/
	'requires'		=> [],

	/*	What this feature writes, so \Nino\Backup::manifest() carries it and a
		restore brings it back. Paths are Nino's virtual ones under the private
		half - see \Nino\Filesystem. A feature that stores nothing writes []	*/
	'data'				=> [ '/data/hello.php' ],

	/*	Settings the Features panel edits by itself - no code, no screen of
		your own. Each is read back with \Nino\Features::setting( $appData,
		'hello', '<key>', $fallback ), which is what Hello::greeting() does.

		'type' is what the panel draws, one of \Nino\Features::SETTING_TYPES:
		bool, int, string, text, email, url, select, secret, lines.
		'default' is what it is worth before anybody saves.

		Use a setting for what belongs to the site and is one value. Use a
		panel of your own (Admin/Admin.php) for anything with more than one
		row, anything that needs a button, and anything that is content rather
		than configuration	*/
	'settings'		=> [
		'greeting' => [
			'type'		=> 'string',
			'default'	=> 'Hello',
			'label'		=> [ 'en_US' => 'Greeting', 'de_DE' => 'Gruß' ],
			'hint'		=> [
				'en_US' => 'The word [hello] opens with.',
				'de_DE' => 'Das Wort, mit dem [hello] beginnt.',
			],
		],
	],
];
