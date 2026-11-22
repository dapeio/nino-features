<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\Upload		see _nino/Nino/Modules/Modules.php for the
 *													package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		A theme package of the catalogue's form, chosen in the panel: read in memory by \Nino\Archive::read() and held to the rule of
	 *										\Nino\Packages::check() before anything of it is written, shown in a preview, and put in place by \Nino\Packages::place() into
	 *										private/packages/themes - where the themes of the catalogue live. The bytes that were read are held for the session and the
	 *										account that sent them, for a quarter of an hour, and read and checked once more before they are written: what the request
	 *										says of the package is its key and the choice to replace, and nothing of the package
	 *
	 *										The catalogue and a package that was chosen here put a theme in the same folder, and nothing in the folder tells them apart:
	 *										the keys that were put there from the panel are kept in data/theme/uploads.json, each with the hash of every file of the folder
	 *										that was written, so that a theme counts as uploaded while the folder is the one that was put there. The file is read, changed
	 *										and written under a lock of its own. Such a theme is replaced - by a higher version, and not while a state of the website
	 *										holds it - and removed here; the one that comes with the feature, the one of the catalogue and the one a folder of the hook has
	 *										are not. The right of the panel is the one that is asked of all of it
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Upload {

		// How long the bytes of a package that was read stay held for the one who is shown what is in it, and the key of the session they are held under
		public const int HOLD = 900;
		public const string SESSION = 'nino-themes-upload';

		// The keys that were put in place from the panel, each with the hash of the folder; the lock that reads, changes and writes them
		public const string MARKS = '/data/theme/uploads.json';
		public const string LOCK = '/data/theme/uploads.lock';

		// The most themes that were uploaded here and are installed: a package is up to 4 MiB and its copies stay as long as the project does
		public const int MAX_UPLOADED = 20;

		// The files a theme package may hold, as \Nino\Packages::check() says them - and nothing the check would refuse is asked of the reader
		public const string ALLOWED = '~^(?:theme\.(?:json|css)|LICENSE\.txt|README\.md|preview\.webp|parts/[a-z][a-z0-9]*(?:-[a-z0-9]+)*/part\.(?:json|css)|blocks/[a-z][a-z0-9]*(?:-[a-z0-9]+)*/(?:block\.(?:json|tpl)|text/[a-z]{2}_[A-Z]{2}\.json|images/[a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)))$~D';

		// The longest text of a licence that a preview carries
		private const int LICENCE = 65536;

		// What a name may not hold: a control character, a character of format - the ones that turn a line round - or a break of line
		private const string UNSAFE = '/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u';

		private static function _fail( int $status, string $code, array $params = [], string $field = 'file', string $message = '' ): array {
			// A code is made of letters, digits and underscores, which is what the panel looks a sentence up by
			return [ 'failure' => [ $status, $message === '' ? 'the theme package is not taken: '. $code : $message, 'themes_upload_'. str_replace( '-', '_', $code ), $params, $field ] ];
		}

		private static function _shown( string $text, int $limit = 120 ): string {

			$text = trim( (string) preg_replace( '/[^\P{C}]+/u', ' ', $text ) );

			if( mb_strlen( $text ) <= $limit )
				return $text;

			// Cut at the end of a word, and say that it goes on
			$cut = mb_substr( $text, 0, $limit );
			$space = mb_strrpos( $cut, ' ' );

			return rtrim( $space !== false && $space > $limit / 2 ? mb_substr( $cut, 0, $space ) : $cut, ' ,;:.-' ). '…';
		}

		/**
		 *	Why a package or a file of it is refused, as a code of the panel: what \Nino\Packages::check() says, by its code
		 *
		 *	@param		array			$problem			What check() or place() answered
		 *
		 *	@return 	array											'failure' => [ ... ]
		 */
		public static function problem( array $problem ): array {

			$code = (string) ( $problem['code'] ?? '' );
			$path = (string) ( $problem['path'] ?? '' );
			$params = array_map( 'strval', (array) ( $problem['params'] ?? [] ) );
			$known = [ 'empty', 'files-many', 'bytes-many', 'path', 'twice', 'not-allowed', 'missing', 'long', 'text', 'json', 'file-key', 'file-slug', 'file-version', 'file-words', 'file-markup', 'file-map', 'parts-many', 'css', 'theme-css', 'block', 'preview', 'busy', 'unlockable', 'stage', 'swap' ];

			// What the kernel says of the css of a theme and of the steps of a part is told by its parameters: the line, the knob
			if( $code === 'theme-css' ) {

				$line = preg_match( '/\(line (\d+)\)/', (string) ( $params[0] ?? '' ), $found ) === 1 ? $found[1] : '';

				return self::_fail( 400, $line === '' ? 'package_theme_css_any' : 'package_theme_css', [ self::_shown( $path === '' ? '-' : $path ), $line ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );
			}

			if( $code === 'part-values' )
				return self::_fail( 400, 'package_part_values', [ self::_shown( $path === '' ? '-' : $path ), self::_shown( (string) ( $params[1] ?? '' ) ) ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );

			if( str_starts_with( $code, 'part-' ) === true || str_starts_with( $code, 'page-' ) === true || str_starts_with( $code, 'group-' ) === true || in_array( $code, [ 'ladder-unknown', 'extra-knob', 'variants', 'standard' ], true ) === true )
				$code = 'part';

			if( $code === 'theme' )
				$code = 'brand';

			if( in_array( $code, $known, true ) === false && $code !== 'part' && $code !== 'brand' )
				$code = 'refused';

			$say = [ self::_shown( $path === '' ? '-' : $path ), self::_shown( $params[0] ?? '' ) ];

			// What the kernel refuses of a block is told as what it is: the block, and the component, the words or the source that stand where they may not
			if( $code === 'block' ) {

				$reason = (string) ( $params[0] ?? '' );
				$block = self::_shown( basename( $path ) );

				if( preg_match( '~ - /[a-z_][a-z0-9_-]* - is neither ~', $reason ) === 1 )
					return self::_fail( 400, 'package_block_loop', [ $block ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );

				if( preg_match( '~, at [0-9.]+: place \(([a-z0-9-]+)\) - against the rules of the register~', $reason, $found ) === 1 )
					return self::_fail( 400, 'package_block_place', [ $block, self::_shown( $found[1] ) ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );

				if( preg_match( '~"(name|description)": a text has no <~', $reason, $found ) === 1 )
					return self::_fail( 400, 'package_block_text', [ $block, $found[1] ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );

				if( preg_match( '~a source of "([^"]+)" - ([^ ]+) - is neither~', $reason, $found ) === 1 )
					return self::_fail( 400, 'package_block_source', [ $block, self::_shown( $found[2] ) ], 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );

				$say = [ $block, '' ];
			}

			return self::_fail( 400, 'package_'. $code, $say, 'file', 'the theme package is refused: '. \Nino\Packages::why( $problem ) );
		}

		/**
		 *	A text that is a string or a locale => string map, as the one a person reads
		 */
		private static function _words( mixed $value, string $locale, string $else = '' ): string {

			if( is_string( $value ) === true )
				return $value;

			if( is_array( $value ) === false || $value === [] )
				return $else;

			$text = $value[$locale] ?? $value[\Nino\Blocks::FALLBACK] ?? reset( $value );

			return is_string( $text ) === true ? $text : $else;
		}

		/**
		 *	What a theme is called, for a sentence: its name where it is installed, else its key
		 */
		private static function _nameOf( array &$appData, string $key, string $locale ): string {

			$name = Reader::read( $appData )['themes'][$key]['name'] ?? $key;

			return self::_shown( self::_words( $name, $locale, $key ) );
		}

		/**
		 *	What this feature refuses of a theme package beyond what the kernel checks, as the failure of the panel: the suggestions for the brand are what the reader of the themes takes, and a
		 *	name is drawn in a list, a card and a sentence - the length of a name of a block, and no character that turns a line round. Where a package is made - an export - it is held to the
		 *	same, so that what leaves is what comes in
		 *
		 *	@param		array			$theme				The theme.json as a map
		 *
		 *	@return 	array|null								'failure' => [ ... ], or null
		 */
		public static function refuse( array $theme ): ?array {

			$brand = Brand::suggestion( $theme['brand'] ?? [] );

			// What the reader of the themes would refuse of the folder that is written, said before it is written
			if( is_string( $brand ) === true )
				return self::problem( [ 'code' => 'theme', 'path' => 'theme.json', 'params' => [ $brand ] ] );

			foreach( [ 'name' => \Nino\Blocks::MAX_NAME, 'description' => \Nino\Blocks::MAX_DESCRIPTION ] as $field => $limit )
				foreach( is_array( $theme[$field] ?? null ) === true ? $theme[$field] : [ $theme[$field] ?? '' ] as $text )
					if( is_string( $text ) === false || mb_strlen( $text ) > $limit || preg_match( self::UNSAFE, $text ) === 1 )
						return self::_fail( 400, 'package_words', [ $field, $limit ], 'file', 'the theme package is refused: "'. $field. '" is longer than '. $limit. ' characters or holds a control character' );

			return null;
		}

		/**
		 *	The files of a package that somebody gave, read and held to the rule of the catalogue's themes
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$bytes				The .tar.gz
		 *
		 *	@return 	array											'files' => path => bytes, 'theme' => the theme.json as a map, 'brand' => what Brand::suggestion() makes of it; or 'failure'
		 */
		public static function read( array &$appData, string $bytes ): array {

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];
			$largest = \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME];

			if( $bytes === '' || strlen( $bytes ) > $largest )
				return self::_fail( 400, 'large', [ $largest ] );

			// The entries are the files and the folders they lie in: each file has at most three folders of its own above it
			$files = \Nino\Archive::read( $bytes, null, 3 * $limits['files'], max( \Nino\Blocks::MAX_PICTURE, \Nino\Packages::MAX_PREVIEW ), $limits['bytes'], self::ALLOWED );

			if( is_string( $files ) === true )
				return self::_fail( 400, 'archive_'. ( preg_match( '/^[a-z-]+$/D', $files ) === 1 ? $files : 'refused' ), [ $files ], 'file', 'the archive is refused: '. $files );

			$problem = \Nino\Packages::check( $appData, \Nino\Packages::THEME, $files );

			if( $problem !== null )
				return self::problem( $problem );

			$theme = (array) json_decode( (string) $files['theme.json'], true );
			$brand = Brand::suggestion( $theme['brand'] ?? [] );
			$refused = self::refuse( $theme );

			if( $refused !== null )
				return $refused;

			return [ 'files' => $files, 'theme' => $theme, 'brand' => $brand ];
		}

		// --- where a theme comes from: the marks -----------------------------------------------------------------------------

		/**
		 *	The hash of the folder of a theme: every file of it, by path, with its bytes - what was written is what the mark has seen, and a file that is changed, added
		 *	or taken away makes it another folder
		 *
		 *	@param		string		$dir					The folder
		 *
		 *	@return 	string										'' for a folder that cannot be read
		 */
		private static function _hash( string $dir ): string {

			if( is_dir( $dir ) === false || is_link( $dir ) === true )
				return '';

			$parts = [];
			$walk = static function( string $path, string $below ) use ( &$walk, &$parts ): bool {

				foreach( (array) scandir( $path ) as $entry ) {

					if( $entry === '.' || $entry === '..' )
						continue;

					if( is_link( $path. '/'. $entry ) === true )
						return false;

					if( is_dir( $path. '/'. $entry ) === true ) {

						if( $walk( $path. '/'. $entry, $below. $entry. '/' ) === false )
							return false;
					}
					else {

						$bytes = @file_get_contents( $path. '/'. $entry );

						if( is_string( $bytes ) === false )
							return false;

						$parts[$below. $entry] = $bytes;
					}
				}

				return true;
			};

			if( $walk( $dir, '' ) === false )
				return '';

			ksort( $parts, SORT_STRING );
			$sum = hash_init( 'sha256' );

			foreach( $parts as $path => $bytes )
				hash_update( $sum, strlen( (string) $path ). ':'. $path. ':'. strlen( $bytes ). ':'. $bytes );

			return hash_final( $sum );
		}

		/**
		 *	The keys that were put in place from the panel
		 *
		 *	@return 	array											key => hash
		 */
		public static function marks( array &$appData ): array {

			$marks = \Nino\Filesystem::getFileContent( $appData, self::MARKS, [] );

			return is_array( $marks ) === true ? array_filter( $marks, static fn( mixed $hash, mixed $key ): bool => is_string( $hash ) === true && is_string( $key ) === true, ARRAY_FILTER_USE_BOTH ) : [];
		}

		// Whether this request holds the lock of the marks: the lock of the kernel is one per file, and one that is taken twice is let go of by the first to let go
		private static bool $_holding = false;

		/**
		 *	Run something with the lock of the marks held - waiting for it, not going on without it - and let go of it after; where the request holds it already, it is what runs in
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$run
		 *
		 *	@return 	mixed											What $run answers
		 */
		private static function _under( array &$appData, callable $run ): mixed {

			if( self::$_holding === true )
				return $run();

			$locked = \Nino\Filesystem::lockFile( $appData, self::LOCK, true );
			self::$_holding = $locked === true;

			try {
				return $run();
			}
			finally {

				self::$_holding = false;

				if( $locked === true )
					\Nino\Filesystem::unlockFile( $appData, self::LOCK );
			}
		}

		/**
		 *	Change the marks: read, changed and written with the lock held, so that what another request wrote between is in what is changed and is not written over
		 *
		 *	@param		array 		&$appData
		 *	@param		callable	$change				function( array $marks ): array - the marks as they are now, and the marks as they are to be
		 *
		 *	@return 	array											The marks that were written
		 */
		public static function changeMarks( array &$appData, callable $change ): array {

			return self::_under( $appData, static function() use ( &$appData, $change ): array {

				$now = self::marks( $appData );
				$next = $change( $now );

				if( $next !== $now )
					\Nino\Filesystem::putFileContent( $appData, self::MARKS, $next );

				return $next;
			} );
		}

		/**
		 *	Let go of the marks that no folder carries - the theme was taken away where the packages are managed - and, where asked, of those whose folder is not the one that was
		 *	written: no folder is the mark's any more, and a theme that comes later with the same files is the catalogue's
		 *
		 *	@param		array 		&$appData
		 *	@param		bool			$folders			Whether the folders are hashed too
		 */
		public static function sweepMarks( array &$appData, bool $folders = false ): void {

			$marks = self::marks( $appData );

			if( $marks === [] )
				return;

			$stale = static function( array $marks ) use ( &$appData, $folders ): array {

				foreach( $marks as $key => $hash ) {

					$dir = \Nino\Packages::dir( $appData, \Nino\Packages::THEME, $key );

					if( $dir === '' || is_dir( $dir ) === false || ( $folders === true && hash_equals( $hash, self::_hash( $dir ) ) === false ) )
						unset( $marks[$key] );
				}

				return $marks;
			};

			if( $stale( $marks ) !== $marks )
				self::changeMarks( $appData, $stale );
		}

		/**
		 *	Where the theme of a key comes from
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	string										'' where no theme has the key, 'shipped' (the feature's), 'uploaded' (put in place from the panel and not changed since), 'catalogue' (any other package) or 'project' (a folder of the hook)
		 */
		public static function origin( array &$appData, string $key ): string {

			if( preg_match( \Nino\Components::NAME, $key ) !== 1 || strlen( $key ) > \Nino\Components::NAME_LENGTH )
				return '';

			if( is_dir( \Nino\Features::dir(). '/Themes/themes/'. $key ) === true )
				return 'shipped';

			$dir = \Nino\Packages::dir( $appData, \Nino\Packages::THEME, $key );

			if( $dir !== '' && is_dir( $dir ) === true ) {

				$mark = self::marks( $appData )[$key] ?? '';
				$hash = $mark === '' ? '' : self::_hash( $dir );

				return $hash !== '' && hash_equals( $mark, $hash ) === true ? 'uploaded' : 'catalogue';
			}

			return isset( Reader::read( $appData )['themes'][$key] ) === true ? 'project' : '';
		}

		/**
		 *	The key a package may not have: one that is the origin of blocks of the kernel, a feature or the project - the blocks of a theme are told by its key, and a block
		 *	of the theme "project" would be a block of the project's own - or that differs from the key of a theme that is there in nothing but its case
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	string|null								null where the key is free to take; 'reserved' or the key it looks like
		 */
		private static function _taken( array &$appData, string $key ): ?string {

			$fold = strtolower( $key );
			$names = Store::RESERVED;

			foreach( \Nino\Features::all( $appData ) as $feature => $unused )
				$names[] = (string) $feature;

			foreach( (array) glob( \Nino\Features::dir(). '/*', GLOB_ONLYDIR ) as $folder )
				$names[] = basename( (string) $folder );

			foreach( \Nino\Blocks::roots( $appData ) as $root )
				$names[] = (string) $root['origin'];

			// The themes there are are told by their origin, which is their key: what one of them is called is the origin of its blocks
			$themes = array_keys( Reader::read( $appData )['themes'] );

			foreach( array_diff( $names, $themes ) as $name )
				if( strtolower( $name ) === $fold )
					return 'reserved';

			foreach( array_merge( $themes, array_keys( \Nino\Packages::installed( $appData, \Nino\Packages::THEME ) ) ) as $name )
				if( (string) $name !== $key && strtolower( (string) $name ) === $fold )
					return (string) $name;

			return null;
		}

		// --- what keeps a theme in place ---------------------------------------------------------------------------------------

		/**
		 *	What keeps a theme of the panel in place: the state of the website shows it, or the last state does. An own theme holds no theme in place: it keeps every part it
		 *	has in its own folder
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *
		 *	@return 	array|null								null where nothing does; else [ 'active' ] or [ 'last' ]
		 */
		public static function heldBy( array &$appData, string $key ): ?array {

			$state = Store::read( $appData );

			if( ( $state['active']['theme'] ?? '' ) === $key )
				return [ 'active' ];

			if( $state['previous'] === true ) {

				$saved = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Themes::DIR. '/previous/active.json', [] );

				if( is_array( $saved ) === true && ( $saved['theme'] ?? '' ) === $key )
					return [ 'last' ];
			}

			return null;
		}

		/**
		 *	The refusal that a hold is, as a failure: the website shows the theme, the last state does
		 */
		private static function _held( array $by, string $key, string $name, string $verb ): array {

			$what = [ 'active' => 'active', 'last' => 'last' ][$by[0]];

			return self::_fail( 409, $verb. '_'. $what, isset( $by[1] ) === true ? [ self::_shown( $by[1] ), $name ] : [ $name ], 'theme', 'the theme is held: '. $by[0] );
		}

		/**
		 *	The blocks of a package as the kernel keeps them: for each, whether it comes into the library or stays out - because another origin has a block of its key - and
		 *	whether it would put out the block of another theme that is installed, which the order of the themes decides: the kernel reads them by key and the first find wins
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the theme of the package
		 *	@param		array			$files
		 *	@param		string		$locale
		 *
		 *	@return 	array											[ [ key, name, category, frame, state ('in', 'out' or 'displaces'), origin (what a person reads, '' for 'in'), theme (the name of the theme that is put out, for 'displaces') ] ]
		 */
		private static function _blocks( array &$appData, string $key, array $files, string $locale ): array {

			$all = \Nino\Blocks::all( $appData );
			$themes = Reader::read( $appData )['themes'];
			$out = [];

			foreach( $files as $path => $bytes )
				if( preg_match( '~^blocks/([a-z][a-z0-9]*(?:-[a-z0-9]+)*)/block\.json$~D', (string) $path, $found ) === 1 ) {

					$meta = (array) json_decode( (string) $bytes, true );
					$has = (string) ( $all[$found[1]]['origin'] ?? '' );
					$state = 'in';
					$label = '';
					$theme = '';

					if( $has !== '' && $has !== $key ) {

						$label = self::_shown( self::_words( $all[$found[1]]['label'] ?? $has, $locale, $has ) );

						// Another theme has the key: the one that is read first keeps it - the themes are read by their keys
						if( isset( $themes[$has] ) === true && strcmp( $key, $has ) < 0 ) {
							$state = 'displaces';
							$theme = self::_nameOf( $appData, $has, $locale );
						}
						else
							$state = 'out';
					}

					$out[] = [ 'key' => $found[1], 'name' => self::_words( $meta['name'] ?? '', $locale, $found[1] ), 'category' => (string) ( $meta['category'] ?? '' ), 'frame' => ( $meta['category'] ?? '' ) === 'frame', 'state' => $state, 'origin' => $label, 'theme' => $theme, 'reads' => self::_reads( $appData, $found[1], $files ) ];
				}

			return $out;
		}

		/**
		 *	What a frame of a package reads of the project - the keys of the texts and the logo it names, which it shows on every page once it is put in; a block of the pages reads none.
		 *	Asked of the kernel, which reads the template as it will when the package is in place
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$block				The key of the block
		 *	@param		array			$files				The files of the package
		 *
		 *	@return 	array											The keys, each once, sorted
		 */
		private static function _reads( array &$appData, string $block, array $files ): array {

			$base = 'blocks/'. $block. '/';
			$texts = [];

			foreach( $files as $path => $bytes )
				if( preg_match( '~^'. preg_quote( $base, '~' ). 'text/([a-z]{2}_[A-Z]{2})\.json$~D', (string) $path, $found ) === 1 )
					$texts[$found[1]] = (string) $bytes;

			$checked = \Nino\Blocks::validate( $appData, $block, [ 'json' => (string) ( $files[$base. 'block.json'] ?? '' ), 'tpl' => (string) ( $files[$base. 'block.tpl'] ?? '' ), 'texts' => $texts ] );

			if( is_array( $checked ) === false )
				return [];

			$checked['tree'] = \Nino\Markup::parse( (string) $checked['source'], \Nino\Components::containers( $appData ) );

			return self::_texts( $appData, array_map( 'strval', \Nino\Blocks::reads( $checked ) ) );
		}

		/**
		 *	The keys a frame reads with what the site says at each today, in the working language: a person who is shown a key sees what it is
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$keys					The keys
		 *
		 *	@return 	array											[ [ 'key', 'text' (a line, cut at the end of a word; null where the key has no text), 'logo' (whether the key is a picture) ] ]
		 */
		private static function _texts( array &$appData, array $keys ): array {

			$was = \Nino\Locales::getCurrentLocale( $appData );
			\Nino\Locales::useLocale( $appData, \Nino\Admin\Admin::contentLocale( $appData, true ) );
			$out = [];

			foreach( $keys as $key ) {

				$logo = in_array( $key, [ '/logo', '/logo/dark' ], true );
				$text = $logo === true ? null : \Nino\Html::resolveTextfill( $appData, $key );

				$out[] = [ 'key' => $key, 'text' => $text === null || trim( strip_tags( $text ) ) === '' ? null : self::_shown( html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 80 ), 'logo' => $logo ];
			}

			\Nino\Locales::useLocale( $appData, $was );

			return $out;
		}

		/**
		 *	The frames an installed theme brings that read texts of the project, each with the keys: what a person who puts the frame in shows on every page
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key					The key of the theme
		 *
		 *	@return 	array											[ [ 'key', 'name' (locale => text), 'reads' ] ], the frames that read nothing left out
		 */
		public static function framesOf( array &$appData, string $key ): array {

			$out = [];

			foreach( \Nino\Blocks::all( $appData ) as $block => $entry ) {

				if( ( $entry['origin'] ?? '' ) !== $key || ( $entry['category'] ?? '' ) !== 'frame' )
					continue;

				$whole = \Nino\Blocks::get( $appData, (string) $block );
				$reads = is_array( $whole ) === true && is_array( $whole['tree'] ?? null ) === true ? \Nino\Blocks::reads( $whole ) : [];

				if( $reads !== [] )
					$out[] = [ 'key' => (string) $block, 'name' => $entry['name'], 'reads' => self::_texts( $appData, array_map( 'strval', $reads ) ) ];
			}

			return $out;
		}

		/**
		 *	Why a package of a key that is installed is not put in place as it is, as a failure that is told - null where it may replace it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		string		$version			The version of the package
		 *	@param		string		$locale
		 *
		 *	@return 	array|null
		 */
		private static function _replace( array &$appData, string $key, string $version, string $locale ): ?array {

			$name = self::_nameOf( $appData, $key, $locale );
			$installed = (string) ( Reader::read( $appData )['themes'][$key]['version'] ?? '' );

			// Only a higher version: a card never shows another theme than the one the website draws, and a version that is the same or lower is not an update
			if( version_compare( $version, $installed ) <= 0 )
				return self::_fail( 409, version_compare( $version, $installed ) === 0 ? 'not_newer_same' : 'not_newer_older', [ $name, $installed, $version ], 'file', 'the version is not higher than the one that is installed' );

			$by = self::heldBy( $appData, $key );

			if( $by !== null )
				return self::_held( $by, $key, $name, 'replace_held' );

			return null;
		}

		/**
		 *	What a package holds, for the one who is asked whether to put it in: the theme with its words, the picture, the parts and whether each is good, the blocks and the frames
		 *	and which of them come into the library, the licence, the fonts the brand names that the project has not, what is installed of that key - and what stands in the way
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$read					As read() answers
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		public static function listed( array &$appData, array $read, string $locale ): array {

			$files = $read['files'];
			$theme = $read['theme'];
			$key = (string) $theme['key'];
			$version = (string) $theme['version'];
			$parts = [];

			foreach( $files as $path => $bytes )
				if( preg_match( '~^parts/([a-z][a-z0-9]*(?:-[a-z0-9]+)*)/part\.json$~D', (string) $path, $found ) === 1 ) {

					$checked = Reader::check( $appData, $found[1], [ 'json' => (array) json_decode( (string) $bytes, true ), 'css' => (string) ( $files['parts/'. $found[1]. '/part.css'] ?? '' ), 'problem' => '' ] );
					$parts[] = [ 'group' => $found[1], 'ok' => $checked['ok'], 'why' => Reader::explain( $checked['problem'] ) ];
				}

			$blocks = self::_blocks( $appData, $key, $files, $locale );
			$fonts = Brand::fonts( $appData );
			$missing = [];

			foreach( $read['brand'] as $name => $value )
				if( str_starts_with( (string) $name, 'font-' ) === true && isset( $fonts[$value] ) === false && in_array( $value, $missing, true ) === false )
					$missing[] = (string) $value;

			$css = trim( (string) ( $files['theme.css'] ?? '' ) );
			$picture = isset( $files['preview.webp'] ) === true ? 'data:image/webp;base64,'. base64_encode( $files['preview.webp'] ) : '';
			$licence = isset( $files['LICENSE.txt'] ) === true && strlen( $files['LICENSE.txt'] ) <= self::LICENCE ? $files['LICENSE.txt'] : '';
			$origin = self::origin( $appData, $key );
			$refused = [];
			$installed = null;

			// What is told in the preview is what the write does: each of these is a failure that install() has too
			$taken = self::_taken( $appData, $key );

			if( $taken === 'reserved' )
				$refused[] = [ 'code' => 'key_reserved', 'params' => [ $key ] ];
			else if( $taken !== null )
				$refused[] = [ 'code' => 'key_like', 'params' => [ $key, $taken ] ];

			if( $origin !== '' ) {

				$old = (string) ( Reader::read( $appData )['themes'][$key]['version'] ?? ( \Nino\Packages::installed( $appData, \Nino\Packages::THEME )[$key] ?? '' ) );
				$installed = [ 'origin' => $origin, 'version' => $old, 'replace' => false ];

				if( $origin !== 'uploaded' )
					$refused[] = [ 'code' => 'taken_'. $origin, 'params' => [ self::_nameOf( $appData, $key, $locale ) ] ];
				else if( ( $failed = self::_replace( $appData, $key, $version, $locale ) ) !== null )
					$refused[] = [ 'code' => str_replace( 'themes_upload_', '', (string) $failed['failure'][2] ), 'params' => $failed['failure'][3] ];
				else
					$installed['replace'] = true;
			}
			else {

				if( count( self::marks( $appData ) ) >= self::MAX_UPLOADED )
					$refused[] = [ 'code' => 'too_many', 'params' => [ self::MAX_UPLOADED ] ];
			}

			foreach( $blocks as $block )
				if( $block['state'] === 'displaces' )
					$refused[] = [ 'code' => 'block_displaces', 'params' => [ $block['name'], $block['theme'] ] ];

			return [
				'key'				=> $key,
				'version'		=> $version,
				'name'			=> self::_words( $theme['name'] ?? '', $locale, $key ),
				'description'	=> self::_words( $theme['description'] ?? '', $locale ),
				'picture'		=> $picture,
				'parts'			=> $parts,
				'blocks'		=> $blocks,
				'frames'		=> count( array_filter( $blocks, static fn( array $block ): bool => $block['frame'] === true ) ),
				'fonts'			=> $missing,
				'licence'		=> $licence,
				'css'				=> [ 'lines' => $css === '' ? 0 : substr_count( $css, "\n" ) + 1, 'text' => mb_strlen( $css ) <= self::LICENCE ? $css : '' ],
				'installed'		=> $installed,
				'refused'		=> $refused,
				'canInstall'	=> $refused === [],
			];
		}

		// --- the bytes that are held -------------------------------------------------------------------------------------------

		/**
		 *	Hold the bytes of a package that was read, for this session and this account, and give the key to them: a number nobody can guess. One package at a time is held
		 *
		 *	@return 	string										The key
		 */
		public static function hold( array &$appData, string $bytes ): string {

			$id = bin2hex( random_bytes( 16 ) );
			$_SESSION[self::SESSION] = [ 'id' => $id, 'until' => time() + self::HOLD, 'sum' => hash( 'sha256', $bytes ), 'bytes' => $bytes, 'account' => self::_account( $appData ) ];

			return $id;
		}

		/**
		 *	The account that is signed in, as the hold names it
		 */
		private static function _account( array &$appData ): string {

			$user = \Nino\Auth::getCurrentUser( $appData );

			return $user === false ? '' : (string) ( $user['mail'] ?? '' );
		}

		/**
		 *	The bytes that were held under a key, for the session and the account that hold them and while they are held
		 *
		 *	@return 	string|null
		 */
		public static function held( array &$appData, string $id ): ?string {

			$held = $_SESSION[self::SESSION] ?? null;

			if( is_array( $held ) === false || is_string( $held['id'] ?? null ) === false || is_string( $held['bytes'] ?? null ) === false || hash_equals( $held['id'], $id ) === false )
				return null;

			$account = self::_account( $appData );

			if( (int) ( $held['until'] ?? 0 ) < time() || hash_equals( (string) ( $held['sum'] ?? '' ), hash( 'sha256', $held['bytes'] ) ) === false ) {
				unset( $_SESSION[self::SESSION] );
				return null;
			}

			// Another account in the same session is not the one who was shown the package
			if( $account === '' || hash_equals( (string) ( $held['account'] ?? '' ), $account ) === false )
				return null;

			return $held['bytes'];
		}

		/**
		 *	Let go of bytes whose time is out: asked at every action of the panel, so that a preview that was left is not carried by every request of the session until it ends
		 */
		public static function sweep(): void {

			$held = $_SESSION[self::SESSION] ?? null;

			if( $held !== null && ( is_array( $held ) === false || (int) ( $held['until'] ?? 0 ) < time() ) )
				unset( $_SESSION[self::SESSION] );
		}

		/**
		 *	Let go of what is held: it was written, or it is not wanted
		 */
		public static function release(): void {
			unset( $_SESSION[self::SESSION] );
		}

		/**
		 *	What a read of the themes holds is read again: the memory of the request and the cache of the feature, which is stamped by the size and the second of a file
		 *	and does not tell a package that was put in place in the second of the one before it
		 */
		private static function _forget( array &$appData ): void {

			unset( $appData['./nino/themes/read'] );
			\Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::CACHE. '/read.php', [] );
		}

		// --- put in place, take away -------------------------------------------------------------------------------------------

		/**
		 *	Put a package in place - read and held to the rule again from the bytes that were held, a theme of that key only where it is one of the panel's, the person said to
		 *	replace it and the version is higher, and no state of the website holds it
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$bytes				The held bytes
		 *	@param		bool			$replace			Whether a theme of the panel with that key is to be replaced
		 *	@param		string		$locale
		 *
		 *	@return 	array											'key', 'version', 'name', 'replaced', 'parts' (the groups that rest, with why), 'blocks' (the keys of the blocks that are there, and 'left' the ones that are not, with the origin that has the key); or 'failure'
		 */
		public static function install( array &$appData, string $bytes, bool $replace, string $locale ): array {

			$read = self::read( $appData, $bytes );

			if( isset( $read['failure'] ) === true )
				return $read;

			$key = (string) $read['theme']['key'];
			$version = (string) $read['theme']['version'];
			$name = self::_nameOf( $appData, $key, $locale );
			$taken = self::_taken( $appData, $key );

			if( $taken === 'reserved' )
				return self::_fail( 409, 'key_reserved', [ self::_shown( $key ) ], 'file', 'the key is the origin of blocks of the kernel, a feature or the project' );

			if( $taken !== null )
				return self::_fail( 409, 'key_like', [ self::_shown( $key ), self::_shown( $taken ) ], 'file', 'the key differs from the key of a theme in its case only' );

			$origin = self::origin( $appData, $key );

			if( in_array( $origin, [ 'shipped', 'catalogue', 'project' ], true ) === true )
				return self::_fail( 409, 'taken_'. $origin, [ $name ], 'file', 'a theme of that key is there and an upload does not replace it' );

			if( $origin === 'uploaded' ) {

				if( $replace !== true )
					return self::_fail( 409, 'exists', [ $name ], 'file', 'a theme of that key is there: it is replaced only when asked' );

				$failed = self::_replace( $appData, $key, $version, $locale );

				if( $failed !== null )
					return $failed;
			}
			else {

				if( count( self::marks( $appData ) ) >= self::MAX_UPLOADED )
					return self::_fail( 409, 'too_many', [ self::MAX_UPLOADED ], 'file', 'there are as many uploaded themes as there may be' );

			}

			foreach( self::_blocks( $appData, $key, $read['files'], $locale ) as $block )
				if( $block['state'] === 'displaces' )
					return self::_fail( 409, 'block_displaces', [ self::_shown( $block['name'] ), self::_shown( $block['theme'] ) ], 'file', 'a block of the package would put out the block of another theme' );

			// What the reader of the themes says of the folder that is written, asked of the staging folder before it is exchanged for the one that is there
			$verify = static function( string $folder ) use ( $key ): ?array {

				$theme = Reader::theme( $folder, $key );

				return is_string( $theme ) === true ? [ 'code' => 'theme', 'path' => 'theme.json', 'params' => [ $theme ] ] : null;
			};

			// The folder and its mark are one change: the one who is at work holds the lock, and the one who comes in between waits
			$failure = self::_under( $appData, static function() use ( &$appData, $key, $read, $verify ): ?array {

				$placed = \Nino\Packages::place( $appData, \Nino\Packages::THEME, $key, $read['files'], $verify );

				if( $placed !== true )
					return self::problem( $placed );

				$hash = self::_hash( \Nino\Packages::dir( $appData, \Nino\Packages::THEME, $key ) );

				// Only this key is changed: what another request wrote is in the marks the lock gives
				self::changeMarks( $appData, static function( array $marks ) use ( $key, $hash ): array {

					$marks[$key] = $hash;

					return $marks;
				} );

				return null;
			} );

			if( $failure !== null )
				return $failure;

			self::_forget( $appData );
			$now = Reader::read( $appData );
			$rests = [];

			foreach( $now['themes'][$key]['parts'] ?? [] as $group => $part )
				if( $part['ok'] === false )
					$rests[] = [ 'group' => (string) $group, 'why' => Reader::explain( $part['problem'] ) ];

			// A block the kernel left out of its list - another origin has the key, or it does not hold - is said, with the block
			$all = \Nino\Blocks::all( $appData );
			$left = [];
			$there = [];

			foreach( $read['files'] as $path => $content )
				if( preg_match( '~^blocks/([a-z][a-z0-9]*(?:-[a-z0-9]+)*)/block\.json$~D', (string) $path, $found ) === 1 ) {

					if( ( $all[$found[1]]['origin'] ?? '' ) === $key )
						$there[] = $found[1];
					else
						$left[] = [ 'key' => $found[1], 'name' => self::_words( ( (array) json_decode( (string) $content, true ) )['name'] ?? '', $locale, $found[1] ), 'origin' => isset( $all[$found[1]] ) === true ? self::_shown( self::_words( $all[$found[1]]['label'] ?? $all[$found[1]]['origin'], $locale ) ) : '' ];
				}

			return [ 'key' => $key, 'version' => $version, 'name' => self::_words( $read['theme']['name'] ?? '', $locale, $key ), 'replaced' => $origin === 'uploaded', 'parts' => $rests, 'blocks' => $there, 'left' => $left ];
		}

		/**
		 *	Take a theme of the panel away: not where it is the one of the website, an own theme takes a part from it, a last state holds it, and not one of any other origin
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$key
		 *	@param		string		$locale
		 *
		 *	@return 	array|null								null where it is gone; else 'failure' => [ ... ]
		 */
		public static function remove( array &$appData, string $key, string $locale = 'en_US' ): ?array {

			$origin = self::origin( $appData, $key );

			if( $origin === '' )
				return self::_fail( 404, 'unknown', [ self::_shown( $key ) ], 'theme', 'there is no such theme' );

			$name = self::_nameOf( $appData, $key, $locale );

			// A theme that is installed and has no mark of its own - the catalogue's, or one whose folder is not the one that was written - is not taken away here
			if( $origin !== 'uploaded' )
				return self::_fail( 409, 'not_uploaded', [ $name ], 'theme', 'only a theme that was uploaded here is removed here' );

			$lock = \Nino\Modules\Themes::DIR. '/state.lock';

			// The state is held while the theme is looked at and taken away: the website is not given it in between
			if( \Nino\Filesystem::lockFile( $appData, $lock, false ) === false )
				return self::_fail( 409, 'busy', [], 'theme', 'another change of the themes is running' );

			try {

				return self::_under( $appData, static function() use ( &$appData, $key, $name ): ?array {

					$by = self::heldBy( $appData, $key );

					if( $by !== null )
						return self::_held( $by, $key, $name, 'in_use' );

					$gone = \Nino\Packages::remove( $appData, \Nino\Packages::THEME, $key );

					if( $gone !== true )
						return self::problem( $gone );

					self::changeMarks( $appData, static function( array $marks ) use ( $key ): array {

						unset( $marks[$key] );

						return $marks;
					} );
					self::_forget( $appData );

					return null;
				} );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, $lock );
			}
		}
	}
}
