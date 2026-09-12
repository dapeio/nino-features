<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design			see _nino/Nino/Modules/Modules.php for the
 *											package-level docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Design						The look of a site, chosen per part of a page rather than
	 *										per page, and compiled into the one stylesheet the css
	 *										bundle already names: assets/theme.css.
	 *
	 *										Nino 1.2 delivers that file fixed, out of the setup
	 *										wizard's base unit. This feature writes its own over it,
	 *										from a set per part - ATF, Section, Article, Buttons,
	 *										Forms, Lists & tables, Blocks - plus a header and a footer,
	 *										which are the two parts that bring markup with them.
	 *
	 *										Four pieces, and they are deliberately separable:
	 *
	 *										  Setup			what was chosen (/data/design.php)
	 *										  Compiler	what that produces (assets/theme.css)
	 *										  Preview		what that would look like, written nowhere
	 *										  Admin			the screen that edits the first
	 *
	 *										Nothing here runs on a public request. The compiled
	 *										stylesheet is an ordinary file the bundle picks up, so a
	 *										site keeps working with the feature removed - and keeps
	 *										its setup, so installing it again carries on where it
	 *										left off rather than starting over.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link								https://github.com/dapeio/nino
	 */
	class Design {

		public const string KEY = 'design';

		/**
		 *	The sets, frames and the token layer this feature ships
		 *
		 *	@return 	string								An absolute directory
		 */
		public static function libraryDir(): string {
			return __DIR__. '/library';
		}

		/**
		 *	The /_admin screen this feature brings along - collected by
		 *	Admin::panels() through Modules::collect(), so it appears in the
		 *	workbench exactly while this feature is active
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										Panel class names
		 */
		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Design\Admin::class ];
		}

		/**
		 *	Read the setup, compile it, write it. The one path the panel, the
		 *	upgrade hook and a test all take, so there is one answer to "what
		 *	does applying actually do"
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$notes				(reference) What was replaced or missing
		 *	@param		bool			$force				Overwrite a theme.css this never wrote
		 *
		 *	@return 	true|string							true, or why not
		 */
		public static function apply( array &$appData, array &$notes = [], bool $force = false ): true|string {

			$library 	= self::libraryDir();
			$setup 		= Design\Setup::read( $appData, $library, $notes );
			$css 			= Design\Compiler::compile( $setup, $library, $notes );

			$written = Design\Compiler::write( $appData, $css, $force );

			if( $written !== true )
				return $written;

			/*	The other half of a frame. A header set is a stylesheet AND the
				markup it was drawn against, so compiling one without writing the
				other is how a page ends up with v3's css over v1's html */
			foreach( Design\Setup::PARTS as $part => $kind ) {

				if( $kind !== 'frame' )
					continue;

				$set 			= (string) ( $setup['parts'][$part]['set'] ?? '' );
				$template = Design\Setup::file( $library, $part, $set, 'template' );

				if( $template === '' ) {
					$notes[] = 'no template for "'. $part. '" set "'. $set. '" - the project keeps the one it has';
					continue;
				}

				$frame = Design\Compiler::writeFrame( $appData, $part, (string) file_get_contents( $template ), $set, $force );

				if( $frame !== true )
					return $frame;
			}

			// What was compiled, so the panel can say whether the file on disk
			// still answers to the setup beside it
			$setup['compiled'] = [ 'at' => gmdate( 'c' ), 'sha' => hash( 'sha256', $css ) ];

			foreach( array_keys( Design\Setup::PARTS ) as $part ) {
				$file = Design\Setup::file( $library, $part, (string) ( $setup['parts'][$part]['set'] ?? '' ) );
				$setup['parts'][$part]['sha'] = $file === '' ? '' : hash_file( 'sha256', $file );
			}

			return Design\Setup::write( $appData, $setup ) === true ? true : 'compiled, but could not write '. Design\Setup::PATH;
		}

		/**
		 *	A new version of the feature may ship changed sets. Recompiling on
		 *	its own would move a site nobody asked to move, so this only
		 *	refreshes what the setup records - the panel is where the difference
		 *	is then visible, and applying is a decision
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$from					The version recorded before this one
		 *
		 *	@return 	bool
		 */
		public static function upgrade( array &$appData, string $from ): bool {

			$notes = [];
			$setup = Design\Setup::read( $appData, self::libraryDir(), $notes );

			return Design\Setup::write( $appData, $setup );
		}

	}
}
