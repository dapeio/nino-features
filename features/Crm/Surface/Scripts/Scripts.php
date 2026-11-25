<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Scripts		The scripts and the style sheets a package adds to the page /crm: files of the package that the page loads after the scripts of the core and that register what they bring with the points of
	 *							the script (Nino.crm.settings, .cards, .overview, .lists, ... see the kit in crm-kit.js). A package tells the page in its init() with add() and addStyle(), as it tells the rail with Menu::add() and
	 *							the devices with Devices::allow(); the surface names no package. A file is served at /crm/assets/<hash>-<name> like the files of the surface (the name is the one the package gave, and a request
	 *							names a registered name, never a path; the file is checked as add() says, for each request); without a package that adds one the page loads nothing more. The page links the files in the
	 *							order they were added: a style sheet that is added before the script it belongs to is read before it.
	 *
	 *	@package						Dape/Nino
	 */
	class Scripts {

		// What the module keeps for the request: the files a package has added, and the refusals of the request, name => rule
		private const string ENTRIES = './crm/surface/scripts/entries';
		private const string REFUSED = './crm/surface/scripts/refused';

		/**
		 *	Add a script to the page. The first script of a name stays; a second one with the same name is refused. The file is the package's own code and is checked, here and again when it is served
		 *	(file()): an absolute path that resolves, symlinks followed, to a regular file whose real name ends in .js and that lies in the folder of the package that adds it (Register::package(): the folder the kernel has for that feature, with a separator at its end) - and not
		 *	below a directory named private or data, nor below the private half of the project. Anything else is refused with a warning of the page /crm (see report()) that says which rule it broke, so that
		 *	a mistake in a package does not make a file of the project public
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name					Lower case letters, digits and single hyphens, ending in .js; not the name of a file of the surface
		 *	@param		string		$file					The absolute path of the script
		 *	@param		array 		$opts					package (required): the key of the package that adds it, see Register::package()
		 *
		 *	@return		bool											Whether the script is on the list now
		 */
		public static function add( array &$appData, string $name, string $file, array $opts = [] ): bool {

			return self::enter( $appData, $name, $file, '.js', $opts );
		}

		/**
		 *	Add a style sheet to the page, on the same terms as a script (see add()): the name ends in .css
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$file					The absolute path of the style sheet
		 *	@param		array 		$opts					package (required)
		 *
		 *	@return		bool											Whether the style sheet is on the list now
		 */
		public static function addStyle( array &$appData, string $name, string $file, array $opts = [] ): bool {

			return self::enter( $appData, $name, $file, '.css', $opts );
		}

		/**
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$file
		 *	@param		string		$extension
		 *	@param		array 		$opts
		 *
		 *	@return		bool
		 */
		private static function enter( array &$appData, string $name, string $file, string $extension, array $opts ): bool {

			if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*'. preg_quote( $extension, '/' ). '$/D', $name ) !== 1 || strlen( $name ) > 64 || \Nino\Modules\Crm\Surface::assetName( $appData, $name ) !== '' )
				return self::refuse( $appData, $name, 'the name is lower case letters, digits and single hyphens, ends in '. $extension. ' and is no file of the surface' );

			$package = array_diff_key( $opts, [ 'package' => 1 ] ) === [] ? \Nino\Modules\Crm\Register::package( $appData, $opts['package'] ?? null ) : null;
			$refused = $package === null ? 'the package is none' : self::refusal( $appData, $file, $extension, $package['folder'] );

			if( $refused !== '' )
				return self::refuse( $appData, $name, $refused );

			foreach( (array) ( $appData[self::ENTRIES] ?? [] ) as $entry )
				if( $entry['name'] === $name )
					return false;

			$appData[self::ENTRIES][] = [ 'name' => $name, 'file' => $file, 'package' => $package['key'], 'folder' => $package['folder'] ];

			return true;
		}

		/**
		 *	A refusal is kept, not warned about here: a package calls add() in its init(), which runs for every request - the files the page links, the calls of its endpoint - and a mistake in it would fill the
		 *	log with a line for each. The warning is made by report(), which the page /crm calls, so that it comes once for each name and each time the page is opened: where the author looks, and not for the
		 *	requests of the files and of the API
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *	@param		string		$reason
		 *
		 *	@return		bool											false
		 */
		private static function refuse( array &$appData, string $name, string $reason ): bool {

			if( isset( $appData[self::REFUSED][$name] ) === false )
				$appData[self::REFUSED][$name] = $reason;

			return false;
		}

		/**
		 *	The files that were refused in this request, by name
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return		array											name => the rule it broke
		 */
		public static function refused( array &$appData ): array {

			return (array) ( $appData[self::REFUSED] ?? [] );
		}

		/**
		 *	Warn of the files that were refused, once for each name: the page /crm calls it, the requests of its files and of its endpoint do not
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return		void
		 */
		public static function report( array &$appData ): void {

			foreach( self::refused( $appData ) as $name => $reason )
				trigger_error( 'Crm: the file "'. $name. '" was not added to the page: '. $reason, E_USER_WARNING );

			unset( $appData[self::REFUSED] );
		}

		/**
		 *	Why a file is no file of a package
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$file
		 *	@param		string		$extension		.js for a script, .css for a style sheet
		 *	@param		string		$owner				The real path of the folder of the package that added it
		 *
		 *	@return		string										'' for a file that may be served; else the rule it breaks
		 */
		private static function refusal( array &$appData, string $file, string $extension, string $owner ): string {

			if( $file === '' || str_contains( $file, "\0" ) === true || $file[0] !== '/' )
				return 'the path is not absolute';

			$real = realpath( $file );

			if( $real === false || is_file( $real ) === false || is_readable( $real ) === false )
				return 'the path is no readable file';

			if( str_ends_with( $real, $extension ) === false )
				return 'the file is no '. $extension. ' file';

			$private = realpath( dirname( \Nino\Filesystem::path( $appData, '/config.php' ) ) );

			if( $private !== false && str_starts_with( $real, rtrim( $private, '/' ). '/' ) === true )
				return 'the file lies in the private half of the project';

			if( \Nino\Modules\Crm\Register::within( $real, $owner ) === false )
				return 'the file lies outside the folder of the package that adds it';

			if( array_intersect( [ 'private', 'data' ], explode( '/', substr( $real, strlen( rtrim( $owner, '/' ) ) + 1 ) ) ) !== [] )
				return 'the file lies below a directory named private or data';

			return '';
		}

		/**
		 *	@param		array 		&$appData
		 *
		 *	@return		array											name and file of each, in the order they were added
		 */
		public static function entries( array &$appData ): array {

			return array_values( (array) ( $appData[self::ENTRIES] ?? [] ) );
		}

		/**
		 *	The name a file of a package is linked by: the first twelve characters of the hash of its content, then the name
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return		string										'' for a name no package added
		 */
		public static function assetName( array &$appData, string $name ): string {

			$file = self::file( $appData, $name );

			if( $file === '' )
				return '';

			// The file is checked when the request is answered, not only when it was added: it may have been replaced since
			$hash = hash_file( 'sha256', $file );

			return $hash === false ? '' : substr( $hash, 0, 12 ). '-'. $name;
		}

		/**
		 *	The file of a script or a style sheet a package added
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$name
		 *
		 *	@return		string										'' for a name no package added, and for a file that is refused now
		 */
		public static function file( array &$appData, string $name ): string {

			foreach( self::entries( $appData ) as $entry )
				if( $entry['name'] === $name ) {

					// The file is checked again against the folder of the package that added it: it may have been replaced since
					return self::refusal( $appData, $entry['file'], str_ends_with( $name, '.css' ) === true ? '.css' : '.js', $entry['folder'] ) === '' ? (string) realpath( $entry['file'] ) : '';
				}

			return '';
		}

		/**
		 *	The elements for the page that load files of the surface by name, one on a line of its own
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *	@param		array 		$names				Keys of Surface::ASSETS
		 *
		 *	@return		string
		 */
		public static function tags( array &$appData, string $dir, array $names ): string {

			$markup = '';

			foreach( $names as $name )
				$markup .= "\n\t". self::tag( $appData, $dir. '/crm/assets/'. \Nino\Modules\Crm\Surface::assetName( $appData, (string) $name ), (string) $name );

			return $markup;
		}

		/**
		 *	The elements for the page that load the files of the packages, one on a line of its own, in the order they were added; '' where there are none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$dir					The directory the project is served from, without the closing slash
		 *
		 *	@return		string
		 */
		public static function markup( array &$appData, string $dir ): string {

			$markup = '';

			foreach( self::entries( $appData ) as $entry ) {

				$linked = self::assetName( $appData, $entry['name'] );

				// A file that is not one any more is not linked
				if( $linked !== '' )
					$markup .= "\n\t". self::tag( $appData, $dir. '/crm/assets/'. $linked, $entry['name'] );
			}

			return $markup;
		}

		/**
		 *	The element that loads a file: a link for a style sheet, a script element for a script
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$href
		 *	@param		string		$name
		 *
		 *	@return		string
		 */
		private static function tag( array &$appData, string $href, string $name ): string {

			return \Nino\Modules\Crm\Surface::fragment( $appData, str_ends_with( $name, '.css' ) === true ? 'style' : 'script', [ 'href' => \Nino\Modules\Crm\Surface::text( $href ) ] );
		}
	}
}
