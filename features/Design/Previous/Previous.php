<?php
declare(strict_types=1);
/**
 *	Nino									A compact filesystembased php framework
 *	Modules\Design\Previous		see features/Design/Design.php for the feature's
 *												own docblock
 *
 *	@package							Dape/Nino
 *	@author								David Perchermeier <mail@dape.io>
 *	@link									https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Previous					The one version before: what the three files Design writes
	 *										and the setup beside them were, the moment before an apply
	 *										or a restore replaced them.
	 *
	 *										One slot rather than a history. "The previous version" is
	 *										what the panel promises, and a list of them would need ids,
	 *										a pruning rule and a screen to choose from - none of which
	 *										is what somebody wanting their hand-edited header back
	 *										after one wrong click has to wade through. Restoring swaps
	 *										the slot with the present, so it can be undone, and the
	 *										daily Backups archive keeps what each day's slot held.
	 *
	 *										The file is a php array (/data/design-previous.php), written
	 *										in one atomic putFileContent(). var_export() carries any
	 *										bytes a stylesheet or a template can hold. Nothing in it is
	 *										an id and nothing a request names, so no path can come from
	 *										one: the three targets are the constants of Compiler.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Previous {

		public const string PATH = '/data/design-previous.php';

		/**
		 *	The files Design writes, as virtual paths: the stylesheet, then the
		 *	templates of the frames in the order the parts are declared in
		 *
		 *	@return 	array									Virtual paths
		 */
		public static function targets(): array {

			$targets = [ Compiler::TARGET ];

			foreach( Setup::PARTS as $part => $kind )
				if( $kind === 'frame' )
					$targets[] = sprintf( Compiler::FRAME_TARGET, $part );

			return $targets;
		}

		/**
		 *	What these files and the setup are right now, as bytes in memory.
		 *	A file that is not there is null, so a restore knows it has nothing
		 *	to put back - and never deletes a frame the page includes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$targets			Virtual paths, see targets()
		 *
		 *	@return 	array									[ 'at' => ISO 8601, 'setup' => array|null, 'files' => [ target => bytes|null ] ]
		 */
		public static function capture( array &$appData, array $targets ): array {

			$files = [];

			foreach( $targets as $target ) {

				$path = \Nino\Filesystem::path( $appData, $target );

				$files[$target] = ( $path !== '' && is_file( $path ) === true ) ? (string) @file_get_contents( $path ) : null;
			}

			$setup = null;
			$path = \Nino\Filesystem::path( $appData, Setup::PATH );

			// is_file() first: reading a .php file through the Filesystem is an
			// include, and a directory in that place is not one to include
			if( $path !== '' && is_file( $path ) === true ) {
				$raw = \Nino\Filesystem::getFileContent( $appData, Setup::PATH, [] );
				$setup = is_array( $raw ) === true ? $raw : null;
			}

			return [ 'at' => gmdate( 'c' ), 'setup' => $setup, 'files' => $files ];
		}

		/**
		 *	The slot, or null when there is none - or when what is there is not
		 *	a slot, which is the same answer for everyone who asks
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null						The shape capture() returns
		 */
		public static function read( array &$appData ): ?array {

			$path = \Nino\Filesystem::path( $appData, self::PATH );

			if( $path === '' || is_file( $path ) === false )
				return null;

			$raw = \Nino\Filesystem::getFileContent( $appData, self::PATH, null );

			if( is_array( $raw ) === false || is_string( $raw['at'] ?? null ) === false || is_array( $raw['files'] ?? null ) === false )
				return null;

			$files = [];

			foreach( self::targets() as $target )
				$files[$target] = is_string( $raw['files'][$target] ?? null ) === true ? $raw['files'][$target] : null;

			return [
				'at' 		=> $raw['at'],
				'setup'	=> is_array( $raw['setup'] ?? null ) === true ? $raw['setup'] : null,
				'files'	=> $files,
			];
		}

		/**
		 *	Make a state the slot. One atomic write: either the old slot or this
		 *	one is on disk, never half of either
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		$state				The shape capture() returns
		 *
		 *	@return 	bool
		 */
		public static function write( array &$appData, array $state ): bool {
			return \Nino\Filesystem::putFileContent( $appData, self::PATH, [
				'at' 		=> (string) ( $state['at'] ?? gmdate( 'c' ) ),
				'setup'	=> is_array( $state['setup'] ?? null ) === true ? $state['setup'] : null,
				'files'	=> (array) ( $state['files'] ?? [] ),
			] );
		}

	}
}
