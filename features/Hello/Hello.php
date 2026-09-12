<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	Modules\Hello			see _nino/Nino/Modules/Modules.php for the
 *										package-level docblock
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino						A compact filesystembased php framework
	 *	Hello						The example feature: one small thing, done through every
	 *									layer a feature can use, so the directory can be copied
	 *									and cut down rather than assembled from scratch.
	 *
	 *									Read feature.php first - it is the manifest and it says
	 *									what each field is for. Then this file, then
	 *									Admin/Admin.php. The recipe in Nino's own docs,
	 *									docs/recipes/feature.md, walks the same ground in prose.
	 *
	 *									What it does: [hello] greets, /hello is a page, and the
	 *									greeting comes half from a setting the Features panel
	 *									edits and half from a name this feature's own panel
	 *									stores. That split is the one thing worth taking away
	 *									from the example - see greeting() and name() below.
	 *
	 *									Three methods are the whole contract with the kernel, and
	 *									all three are optional:
	 *
	 *										init()					called on every request, while the
	 *																		feature is active. Register things.
	 *																		Never do I/O here - it runs for every
	 *																		page view including the ones that
	 *																		never reach your code.
	 *										upgrade()				called once, when the version on disk
	 *																		is newer than the one recorded.
	 *																		Migrate stored data.
	 *										adminPanels()		which /_admin screens come along.
	 *
	 *	@package				Dape/Nino
	 *	@author					David Perchermeier <mail@dape.io>
	 *	@link						https://github.com/dapeio/nino
	 */
	class Hello {

		/*	Where this feature's own state lives. One virtual path under the
			private half, declared under 'data' in feature.php so a backup
			carries it and a restore brings it back.

			A feature that stores nothing needs none of this. A feature that
			stores a lot should still keep it to one file or one directory:
			what is not named in the manifest is not backed up	*/
		public const string PATH = '/data/hello.php';

		// What name the greeting falls back to before anybody has set one
		public const string DEFAULT_NAME = 'World';

		/**
		 *	The /_admin screen this feature brings along.
		 *
		 *	Collected by \Nino\Admin\Admin::panels() through Modules::collect(),
		 *	so the panel appears exactly while the feature is active and vanishes
		 *	with it. Return [] - or leave the method out - for a feature with no
		 *	screen of its own; a setting in feature.php needs none.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Hello\Admin::class ];
		}

		/**
		 *	Everything this feature adds to a request, registered on every one
		 *	of them.
		 *
		 *	Registration only: no file is read here, nothing is written, nothing
		 *	is computed that a page might not need. init() runs for the whole
		 *	site, including every page that never renders a single thing of
		 *	yours - a stat() here is a stat() on every request.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			// 1. A shortcode. The name is what a page writes between brackets
			\Nino\Html::addShortcode( $appData, 'hello', [ self::class, 'doShortcode' ] );

			/*	2. A route, registered at runtime rather than written into
				config.php by the install unit. The difference is what happens on
				deactivation: this one vanishes with the feature, where a route
				the install unit wrote stays behind as the project's own - which
				is right for a page an editor is meant to keep and wrong for one
				that only works while your code is there.

				'uri' is the path a template resolves its fills against; 'body'
				is what is rendered	*/
			$appData['/nino/http/routes']['GET://hello'] = [
				'uri' 	=> '/hello',
				'body' 	=> '[template /templates/page-hello]',
			];

			/*	3. A stylesheet, added to the site's own bundle - the same one
				the base install's html-header.tpl already loads on every page,
				and the same way the kernel bundles its own Nino.css. A bundle of
				your own is a second request for a file every page needs anyway.

				The path resolves against the project root, which is how
				'/_nino/Nino.css' reaches the kernel's copy. A project that moved
				its features with NINO_FEATURES_DIR has to say so itself	*/
			\Nino\Html::addAsset( $appData, '/.cache/style.css', '/features/Hello/assets/hello.css' );
		}

		/**
		 *	Called once, when the version in feature.php is newer than the one
		 *	the project recorded at activation. The only hook there is: there is
		 *	no install hook, because activation is the install unit and nothing
		 *	else (see install/manifest.php).
		 *
		 *	Migrate stored data here, and nothing else. It runs with the new
		 *	code and the old data, exactly once, and a project that installs
		 *	this feature fresh never calls it at all - so anything a fresh
		 *	install also needs belongs in the install unit, not here.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					The version that was recorded
		 *	@param		string		$to						The version in feature.php now
		 *
		 *	@return 	void
		 */
		public static function upgrade( array &$appData, string $from, string $to ): void {

			/*	Nothing to migrate at 1.0.0 - there has been no earlier shape of
				/data/hello.php to move out of. What a real one looks like:

					if( version_compare( $from, '1.1.0', '<' ) === true ) {
						$stored = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );
						$stored['name'] = $stored['who'] ?? self::DEFAULT_NAME;
						unset( $stored['who'] );
						\Nino\Filesystem::putFileContent( $appData, self::PATH, $stored );
					}

				Guarded by the version it belongs to, so a project skipping two
				releases runs every step it missed and none it did not	*/
		}

		/**
		 *	[hello] and [hello name="Ada"] - what a page writes.
		 *
		 *	A shortcode gets the request's app data by reference and its own
		 *	arguments, and returns html. What it returns is rendered again, so
		 *	fills and other shortcodes inside it resolve; that is also why a
		 *	shortcode must not return something that contains itself.
		 *
		 *	Arguments arrive as key => value for name="value", and as a plain
		 *	list entry for a bare word - \Nino\Html's parser only reads a value
		 *	that is in double quotes.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments
		 *
		 *	@return 	string									The greeting, as html
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$name = trim( (string) ( $args['name'] ?? '' ) );

			if( $name === '' )
				$name = self::name( $appData );

			/*	Escaped, because it is not a text fill: everything between [[ and
				]] goes through the fill engine, which an editor's own text does.
				A shortcode argument comes straight out of a template and a
				panel's stored value straight out of a form, so both are escaped
				here - the one rule that is never optional	*/
			return '<p class="nino-hello">'
				. htmlspecialchars( self::greeting( $appData ), ENT_QUOTES )
				. ', '
				. htmlspecialchars( $name, ENT_QUOTES )
				. '! <span class="nino-hello-note">[[/hello/note]]</span></p>';
		}

		/**
		 *	The greeting word - a setting, so the Features panel edits it and
		 *	this feature needs no screen for it.
		 *
		 *	\Nino\Features::setting() takes the feature key, the setting name
		 *	and what it is worth when nobody has saved one. Reading it costs a
		 *	lookup in config.php, which is already in memory.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function greeting( array &$appData ): string {

			$greeting = trim( (string) \Nino\Features::setting( $appData, 'hello', 'greeting', 'Hello' ) );

			return $greeting === '' ? 'Hello' : $greeting;
		}

		/**
		 *	...and the name it falls back to - stored by this feature's own
		 *	panel, because it is the kind of thing a screen with a button
		 *	belongs to rather than a settings row.
		 *
		 *	The line between the two is worth drawing deliberately: a setting is
		 *	one value that belongs to the site and needs no code; a panel is
		 *	anything with more than one row, anything that needs an action, and
		 *	anything that is content rather than configuration.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function name( array &$appData ): string {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::PATH, [] );
			$name 	= trim( (string) ( $stored['name'] ?? '' ) );

			return $name === '' ? self::DEFAULT_NAME : $name;
		}

		/**
		 *	Store it. The panel's save calls this; nothing else does.
		 *
		 *	One writer for one file is worth keeping to even when it is this
		 *	small - it is the only way a later reader knows what may be in
		 *	there.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					What [hello] greets without an argument
		 *
		 *	@return 	bool										Whether it was written
		 */
		public static function setName( array &$appData, string $name ): bool {

			$name = trim( $name );

			return \Nino\Filesystem::putFileContent( $appData, self::PATH, [
				'name' => $name === '' ? self::DEFAULT_NAME : $name,
			] ) === true;
		}
	}

}
