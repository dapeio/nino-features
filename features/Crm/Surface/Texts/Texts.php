<?php
declare(strict_types=1);

namespace Nino\Modules\Crm\Surface {

	/**
	 *	Texts		The words of the page /crm, in the language of the person who looks at it: the language of the session of the workbench where there is one, else the native language of the site, and
	 *						a language the surface has no words of is none - the surface says it in en_US then, whatever the site's own is. The words are flat keys, group.name, one file for each language
	 *						(Surface/text/<locale>.php), the sentences of the refusals of the endpoint (error.<code>), of the status (status.<key>) and of the mending of it (setup.<key>), which the panel
	 *						of the workbench has as its own words and the page takes from them, and the files of the packages (Surface::addTexts()).
	 *
	 *						A package brings a directory with one file for each language, <locale>.php, the keys flat as the surface's own; a file with a key the surface or another package has is refused as a
	 *						whole, with a warning once for each request: a package never overwrites a sentence of another. What was read stands in $appData for the request and never in a static field.
	 *
	 *	@package						Dape/Nino
	 */
	class Texts {

		/**	The languages the surface speaks	*/
		public const array LOCALES = [ 'de_DE', 'en_US' ];

		// What the module keeps for the request: the directories the packages added, the locale and the words read
		private const string DIRECTORIES = './crm/surface/texts/directories';
		private const string LOCALE = './crm/surface/texts/locale';
		private const string WORDS = './crm/surface/texts/words';
		private const string WARNED = './crm/surface/texts/warned';

		// The files whose sentences the page takes besides its own: the panel of the core and the two panels of the workbench the actions of the values and of the texts of the company run through
		private const array PANELS = [ '/_admin/Nino/Modules/Elements/text', '/_admin/Nino/Modules/Text/text' ];

		/**
		 *	Add the words of a package: a directory that holds <locale>.php for each language. Only the path is checked here and nothing is read: the directory is an absolute path that resolves to a
		 *	directory in the folder of the package that adds it (Register::package()), not below a directory named private or data. Anything else is a warning and false. The same directory again is as good as the first
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$directory		The absolute path
		 *	@param		array 		$opts					package (required): the key of the package that adds the words
		 *
		 *	@return 	bool											Whether the directory is on the list of the request: its path is good (it says nothing of the keys of its files)
		 */
		public static function add( array &$appData, string $directory, array $opts = [] ): bool {

			$package = array_diff_key( $opts, [ 'package' => 1 ] ) === [] ? \Nino\Modules\Crm\Register::package( $appData, $opts['package'] ?? null ) : null;
			$refused = $package === null ? 'the package is none' : self::refusal( $directory, $package['folder'] );

			if( $refused !== '' ) {
				trigger_error( 'Crm: the words of "'. $directory. '" were not added: '. $refused, E_USER_WARNING );

				return false;
			}

			$real = (string) realpath( $directory );

			if( in_array( $real, (array) ( $appData[self::DIRECTORIES] ?? [] ), true ) === false ) {
				$appData[self::DIRECTORIES][] = $real;
				unset( $appData[self::WORDS] );
			}

			return true;
		}

		/**
		 *	Why a directory is no directory of words of a package
		 *
		 *	@param		string		$directory
		 *	@param		string		$owner				The folder of the package that adds it
		 *
		 *	@return 	string										'' for one that may be read; else the rule it breaks
		 */
		private static function refusal( string $directory, string $owner ): string {

			if( $directory === '' || str_contains( $directory, "\0" ) === true || $directory[0] !== '/' )
				return 'the path is not absolute';

			$real = realpath( $directory );

			if( $real === false || is_dir( $real ) === false || is_readable( $real ) === false )
				return 'the path is no readable directory';

			if( \Nino\Modules\Crm\Register::within( $real, $owner ) === false )
				return 'the directory lies outside the folder of the package that adds it';

			return array_intersect( [ 'private', 'data' ], explode( '/', substr( $real, strlen( rtrim( $owner, '/' ) ) + 1 ) ) ) === [] ? '' : 'the directory lies below a directory named private or data';
		}

		/**
		 *	The language of the page: the one of the session of the workbench, else the native language of the site, and en_US where the surface has no words in it
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function locale( array &$appData ): string {

			if( is_string( $appData[self::LOCALE] ?? null ) === true )
				return $appData[self::LOCALE];

			$chosen = \Nino\Modules\Crm\Values::locale( $appData );

			return $appData[self::LOCALE] = in_array( $chosen, self::LOCALES, true ) === true ? $chosen : 'en_US';
		}

		/**
		 *	The tag of the language for the html element and the manifest: de-DE
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		public static function tag( array &$appData ): string {

			return str_replace( '_', '-', self::locale( $appData ) );
		}

		/**
		 *	The words of the page in its language
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											key => text
		 */
		public static function all( array &$appData ): array {

			if( is_array( $appData[self::WORDS] ?? null ) === true )
				return $appData[self::WORDS];

			$locale = self::locale( $appData );
			$words = (array) require dirname( __DIR__ ). '/text/'. $locale. '.php';

			$files = [ dirname( __DIR__, 2 ). '/text/'. $locale. '.php' ];

			foreach( self::PANELS as $panel )
				$files[] = \Nino\Filesystem::path( $appData, $panel. '/'. $locale. '.php' );

			foreach( $files as $file ) {

				if( is_file( $file ) === false )
					continue;

				foreach( (array) require $file as $key => $text )
					if( preg_match( '~^\[\[/_admin/(?:(error)/([a-z][a-z0-9_]*)|crm/(status|setup)/([a-z][a-z0-9-]*)|text/error/(save|load))\]\]$~D', (string) $key, $found ) === 1 )
						$words[( $found[5] ?? '' ) !== '' ? 'text.error-'. $found[5] : ( $found[1] !== '' ? 'error.'. $found[2] : $found[3]. '.'. $found[4] )] = (string) $text;
			}

			foreach( (array) ( $appData[self::DIRECTORIES] ?? [] ) as $directory ) {

				$file = $directory. '/'. $locale. '.php';
				$sentences = is_file( $file ) === true && is_readable( $file ) === true ? array_map( 'strval', array_filter( (array) require $file, 'is_string' ) ) : [];
				$taken = array_keys( array_intersect_key( $sentences, $words ) );

				if( $taken === [] ) {
					$words += $sentences;
					continue;
				}

				// Once for each request and file, whatever number of times the words are asked for
				$once = $file. '|'. implode( ',', $taken );

				if( isset( $appData[self::WARNED][$once] ) === false ) {
					$appData[self::WARNED][$once] = true;
					trigger_error( 'Crm: the words of "'. $file. '" were not added: it has the key of '. implode( ', ', $taken ). ' that exists', E_USER_WARNING );
				}
			}

			return $appData[self::WORDS] = $words;
		}

		/**
		 *	The words of one group: what a page that nobody is signed in to is given of them
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$prefix				The start of the keys, with the dot: login.
		 *
		 *	@return 	array											key => text
		 */
		public static function group( array &$appData, string $prefix ): array {

			return array_filter( self::all( $appData ), static fn( string $key ): bool => str_starts_with( $key, $prefix ), ARRAY_FILTER_USE_KEY );
		}

		/**
		 *	One word
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	string										The key itself where there is no such word
		 */
		public static function word( array &$appData, string $key ): string {

			return self::all( $appData )[$key] ?? $key;
		}
	}
}
