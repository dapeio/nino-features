<?php
declare(strict_types=1);

namespace Nino\Modules\Themelab {

	use Nino\Modules\Builder\Custom;

	// The own blocks of the project as a package of the catalogue's form, and a package of that form as own blocks. A package is a folder with a pack.json and, for each block, its folder
	// with block.json, block.tpl, text/ and images/ - written by \Nino\Archive::write(), held to \Nino\Packages::check() before it leaves, and read by \Nino\Archive::read() and held to the
	// same check before anything of it is written. No catalogue and no signature are in it: it is the way of the one who made it
	class Package {

		// How long the bytes of a package that was read stay held for the one who is shown what is in it
		public const int HOLD = 900;

		// The key of the session the bytes are held under
		public const string SESSION = 'nino-themelab-import';

		// The files a package of blocks may hold, as \Nino\Packages::check() says them - and nothing the check would refuse is asked of the reader
		private const string ALLOWED = '~^(?:pack\.json|LICENSE\.txt|preview\.webp|[a-z][a-z0-9]*(?:-[a-z0-9]+)*/(?:block\.(?:json|tpl)|text/[a-z]{2}_[A-Z]{2}\.json|images/[a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)))$~D';
		private const string VERSION = '/^\d{1,4}\.\d{1,4}\.\d{1,4}$/D';
		private const string UNSAFE = '/[<>\x00-\x1F\x7F]|\[\[/u';

		/**
		 *	The most entries a package of $blocks blocks has: the folder, each block's folder with its text/ and images/, and the files
		 */
		private static function _entries( int $blocks ): int {
			return 1 + 3 * $blocks + \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS]['files'];
		}

		/**
		 *	The file the project has for a licence of its own blocks: LICENSE.txt in the folder of them - and none is made up
		 *
		 *	@return 	string|null
		 */
		private static function _licence( array &$appData ): ?string {

			$path = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). \Nino\Blocks::OWN. '/LICENSE.txt';

			if( is_file( $path ) === false || is_link( $path ) === true || (int) @filesize( $path ) < 1 || (int) @filesize( $path ) > \Nino\Packages::MAX_TEXT )
				return null;

			$text = @file_get_contents( $path );

			return is_string( $text ) === true && mb_check_encoding( $text, 'UTF-8' ) === true && str_contains( $text, "\0" ) === false ? $text : null;
		}

		/**
		 *	The files of a block of the project as they lie in its folder, byte for byte: block.json, block.tpl, text/<language>.json, images/<slot>.<type>
		 *
		 *	@return 	array|null								path below the folder of the block => bytes; null for a folder that is no folder of the project's or holds what a block does not
		 */
		private static function _folder( array &$appData, string $key ): ?array {

			$content = (string) ( $appData['./nino/filesystem/contentpath'] ?? '' );
			$own = realpath( $content. \Nino\Blocks::OWN );
			$dir = $own === false ? false : realpath( $own. '/'. $key );

			if( $content === '' || $own === false || $dir === false || $dir !== $own. '/'. $key || is_link( $dir ) === true )
				return null;

			$files = [];

			foreach( [ '' => [ 'block.json', 'block.tpl' ], 'text' => null, 'images' => null ] as $inner => $names ) {

				$base = $inner === '' ? $dir : $dir. '/'. $inner;

				if( $inner !== '' && ( is_dir( $base ) === false || is_link( $base ) === true ) )
					continue;

				foreach( $names ?? array_values( array_filter( (array) scandir( $base ), static fn( string $entry ): bool => $entry !== '.' && $entry !== '..' ) ) as $name ) {

					$path = $base. '/'. $name;

					if( is_file( $path ) === false || is_link( $path ) === true )
						return null;

					$bytes = @file_get_contents( $path );

					if( is_string( $bytes ) === false )
						return null;

					$files[$inner === '' ? $name : $inner. '/'. $name] = $bytes;
				}
			}

			return $files;
		}

		private static function _fail( int $status, string $code, array $params = [], string $field = '', string $message = '' ): array {
			return [ 'failure' => [ $status, $message === '' ? 'the package is not made: '. $code : $message, 'themelab_'. $code, $params, $field ] ];
		}

		/**
		 *	Why a package or a file of it is refused, as a code of the panel: what \Nino\Packages::check() says, by its code
		 *
		 *	@param		array			$problem			What check() answered
		 *
		 *	@return 	array											'failure' => [ ... ]
		 */
		private static function _problem( array $problem, string $stage, array &$appData, string $locale ): array {

			$path = (string) ( $problem['path'] ?? '' );
			$code = (string) ( $problem['code'] ?? '' );
			$known = [ 'empty', 'files-many', 'bytes-many', 'path', 'twice', 'not-allowed', 'missing', 'long', 'text', 'json', 'file-key', 'file-slug', 'file-version', 'file-words', 'file-map', 'block', 'preview' ];
			$shown = static fn( string $text ): string => mb_substr( (string) preg_replace( '/[^\P{C}]+/u', ' ', $text ), 0, 120 );

			// A name or a description with markup is said in the words of the import, whichever of the two checks finds it: the kernel's or the one of the import itself
			if( $code === 'file-markup' )
				return self::_fail( 400, 'import_words', [ (string) ( $problem['params'][0] ?? '' ) ], 'file' );

			if( in_array( $code, $known, true ) === false )
				$code = 'refused';

			$params = array_map( 'strval', (array) ( $problem['params'] ?? [] ) );
			$say = [ $shown( $path === '' ? '-' : $path ), $shown( (string) ( $params[0] ?? '' ) ), $shown( \Nino\Packages::why( $problem ) ) ];

			// What the kernel refuses of a block is told as what it is: the component that stands where it may not, the words that hold what they may not, the source that is read where none is
			if( $code === 'block' ) {

				$reason = (string) ( $params[0] ?? '' );

				if( preg_match( '~ - /[a-z_][a-z0-9_-]* - is neither ~', $reason ) === 1 )
					$code = 'loop';
				else if( preg_match( '~, at [0-9.]+: place \(([a-z0-9-]+)\) - against the rules of the register~', $reason, $found ) === 1 ) {

					$code = 'block_place';
					$say = [ $shown( $path ), self::_kind( $appData, $found[1], $locale ) ];
				}
				else if( preg_match( '~"(name|description)": a text has no <~', $reason, $found ) === 1 ) {

					$code = 'block_text';
					$say = [ $shown( $path ), $found[1] ];
				}
				else if( preg_match( '~a source of "([^"]+)" - ([^ ]+) - is neither~', $reason, $found ) === 1 ) {

					$code = 'block_source';
					$say = [ $shown( $path ), $shown( $found[2] ) ];
				}
			}

			// The sentence of the kernel is English and for the log: a person is told where, and which block, and the sentence itself is the last of what the answer carries
			return self::_fail( 400, 'package_'. $code, $say, 'file', 'the package is refused: '. \Nino\Packages::why( $problem ) );
		}

		/**
		 *	What the editor calls a kind of component: the word of the Builder for it, else the name
		 */
		private static function _kind( array &$appData, string $name, string $locale ): string {

			$file = dirname( __DIR__, 2 ). '/Builder/text/'. ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', $locale ) === 1 && is_file( dirname( __DIR__, 2 ). '/Builder/text/'. $locale. '.php' ) === true ? $locale : \Nino\Blocks::FALLBACK ). '.php';
			$words = is_file( $file ) === true ? (array) require $file : [];

			if( isset( $words['[[/_admin/builder/kind/'. $name. ']]'] ) === true )
				return (string) $words['[[/_admin/builder/kind/'. $name. ']]'];

			$label = \Nino\Components::components( $appData )[$name]['label'] ?? $name;

			return is_array( $label ) === true ? (string) ( $label[$locale] ?? $label[\Nino\Blocks::FALLBACK] ?? reset( $label ) ?: $name ) : (string) $label;
		}

		/**
		 *	A package of the blocks of the project
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$keys					The keys of the blocks; each is a block of the project
		 *	@param		array			$given				'key', 'version', 'name', 'description' of the package, as the request has them
		 *
		 *	@return 	array											'bytes', 'file' (the name of the download), 'blocks' (the keys), 'files' (the number of files); or 'failure' => [ status, message, code, params, field ]
		 */
		public static function export( array &$appData, array $keys, array $given, string $locale = 'en_US' ): array {

			$key = is_string( $given['key'] ?? null ) === true ? $given['key'] : '';
			$version = is_string( $given['version'] ?? null ) === true ? $given['version'] : '';
			$name = is_string( $given['name'] ?? null ) === true ? trim( $given['name'] ) : '';
			$description = is_string( $given['description'] ?? null ) === true ? trim( $given['description'] ) : '';

			if( $key === '' || strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 )
				return self::_fail( 400, 'export_key', [ \Nino\Components::NAME_LENGTH ], 'key' );

			if( preg_match( self::VERSION, $version ) !== 1 )
				return self::_fail( 400, 'export_version', [], 'version' );

			if( $name === '' || mb_strlen( $name ) > \Nino\Blocks::MAX_NAME || preg_match( self::UNSAFE, $name ) === 1 )
				return self::_fail( 400, 'export_name', [ \Nino\Blocks::MAX_NAME ], 'name' );

			if( mb_strlen( $description ) > \Nino\Blocks::MAX_DESCRIPTION || preg_match( self::UNSAFE, $description ) === 1 )
				return self::_fail( 400, 'export_description', [ \Nino\Blocks::MAX_DESCRIPTION ], 'description' );

			$keys = array_values( array_unique( array_filter( $keys, 'is_string' ) ) );

			if( $keys === [] || count( $keys ) > 100 )
				return self::_fail( 400, 'export_none', [], 'keys' );

			$all = \Nino\Blocks::all( $appData );
			$files = [];

			foreach( $keys as $block ) {

				// Only a block of the project: the blocks of the theme and of the kernel are not the project's to hand out
				if( preg_match( \Nino\Components::NAME, $block ) !== 1 || ( $all[$block]['origin'] ?? '' ) !== 'project' )
					return self::_fail( 404, 'export_unknown', [ self::_shown( $block ) ], 'keys' );

				$folder = self::_folder( $appData, $block );

				if( $folder === null )
					return self::_fail( 500, 'export_read', [ $block ], 'keys' );

				foreach( $folder as $path => $bytes ) {

					// A svg leaves as the picture that cleanSvg() rebuilds of it: what the project has written before the rule was held is cleaned on its way out
					if( str_ends_with( $path, '.svg' ) === true && ( $bytes = \Nino\Images::cleanSvg( $bytes ) ) === null )
						return self::_fail( 500, 'export_read', [ $block ], 'keys' );

					$files[$block. '/'. $path] = $bytes;
				}
			}

			$pack = [ 'key' => $key, 'version' => $version, 'name' => $name, 'description' => $description === '' ? $name : $description ];
			$files['pack.json'] = json_encode( $pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";

			if( ( $licence = self::_licence( $appData ) ) !== null )
				$files['LICENSE.txt'] = $licence;

			// What the catalogue would not take is not handed out
			$problem = \Nino\Packages::check( $appData, \Nino\Packages::BLOCKS, $files );

			if( $problem !== null )
				return self::_problem( $problem, 'export', $appData, $locale );

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS];
			$bytes = \Nino\Archive::write( $key, $files, self::_entries( count( $keys ) ), \Nino\Blocks::MAX_PICTURE, $limits['bytes'], time(), self::ALLOWED );

			if( is_array( $bytes ) === true )
				return self::_fail( 400, 'export_archive', [ self::_shown( (string) $bytes['path'] ), (string) $bytes['code'] ], 'keys', 'the archive is not made: '. $bytes['code'] );

			if( strlen( $bytes ) > \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] )
				return self::_fail( 400, 'export_large', [ \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] ], 'keys' );

			// What leaves is what a reader takes back as the same files
			if( self::same( $bytes, $key, $files, count( $keys ) ) === false )
				return self::_fail( 500, 'export_archive', [ '', 'different' ], 'keys' );

			return [ 'bytes' => $bytes, 'file' => $key. '-'. $version. '.tar.gz', 'blocks' => $keys, 'files' => count( $files ) ];
		}

		/**
		 *	Whether an archive is read back as the files it was made of: the proof that what leaves is what a reader takes in
		 *
		 *	@param		string		$bytes				The .tar.gz
		 *	@param		string		$key					The folder it holds
		 *	@param		array			$files				path => bytes
		 *	@param		int				$blocks				How many blocks it holds
		 *
		 *	@return 	bool
		 */
		public static function same( string $bytes, string $key, array $files, int $blocks ): bool {

			$back = \Nino\Archive::read( $bytes, $key, self::_entries( $blocks ), \Nino\Blocks::MAX_PICTURE, \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS]['bytes'], self::ALLOWED );

			return is_array( $back ) === true && $back == $files;
		}

		/**
		 *	Make a text safe to show in a sentence: no control character, not long
		 */
		private static function _shown( string $text ): string {
			return mb_substr( (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $text ), 0, 80 );
		}

		/**
		 *	The files of a package that somebody gave, read and held to the rule of the catalogue's blocks
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$bytes				The .tar.gz
		 *
		 *	@return 	array											'files' => path => bytes, 'blocks' => key => [ 'json' => text, 'tpl' => text, 'texts' => locale => text, 'pictures' => file => bytes ], 'pack' => the pack.json as a map; or 'failure'
		 */
		public static function read( array &$appData, string $bytes, string $locale = 'en_US' ): array {

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::BLOCKS];

			if( $bytes === '' || strlen( $bytes ) > \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] )
				return self::_fail( 400, 'import_large', [ \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] ], 'file' );

			$files = \Nino\Archive::read( $bytes, null, self::_entries( $limits['files'] ), \Nino\Blocks::MAX_PICTURE, $limits['bytes'], self::ALLOWED );

			if( is_string( $files ) === true )
				return self::_fail( 400, 'import_archive_'. ( preg_match( '/^[a-z-]+$/D', $files ) === 1 ? $files : 'refused' ), [ $files ], 'file', 'the archive is refused: '. $files );

			$problem = \Nino\Packages::check( $appData, \Nino\Packages::BLOCKS, $files );

			if( $problem !== null )
				return self::_problem( $problem, 'import', $appData, $locale );

			$blocks = [];

			foreach( $files as $path => $content ) {

				if( preg_match( '#^([a-z][a-z0-9]*(?:-[a-z0-9]+)*)/(block\.json|block\.tpl|text/([a-z]{2}_[A-Z]{2})\.json|images/([a-z0-9]+(?:-[a-z0-9]+)*\.(?:svg|webp|png|jpg)))$#D', (string) $path, $found ) !== 1 )
					continue;

				if( $found[2] === 'block.json' )
					$blocks[$found[1]]['json'] = $content;
				else if( $found[2] === 'block.tpl' )
					$blocks[$found[1]]['tpl'] = $content;
				else if( ( $found[3] ?? '' ) !== '' )
					$blocks[$found[1]]['texts'][$found[3]] = $content;
				else
					$blocks[$found[1]]['pictures'][$found[4]] = $content;
			}

			if( $blocks === [] )
				return self::_fail( 400, 'import_empty', [], 'file' );

			// The name and the description of the package are words, as the export holds them to be: no markup and no key of the project
			$pack = (array) json_decode( (string) $files['pack.json'], true );

			foreach( [ 'name' => \Nino\Blocks::MAX_NAME, 'description' => \Nino\Blocks::MAX_DESCRIPTION ] as $field => $limit )
				foreach( is_array( $pack[$field] ?? null ) === true ? $pack[$field] : [ $pack[$field] ?? '' ] as $text )
					if( is_string( $text ) === false || mb_strlen( $text ) > $limit || preg_match( self::UNSAFE, $text ) === 1 )
						return self::_fail( 400, 'import_words', [ $field ], 'file' );

			foreach( $blocks as $key => $parts ) {
				$blocks[$key]['texts'] = $parts['texts'] ?? [];
				$blocks[$key]['pictures'] = $parts['pictures'] ?? [];
			}

			return [ 'files' => $files, 'blocks' => $blocks, 'pack' => (array) json_decode( (string) $files['pack.json'], true ) ];
		}

		/**
		 *	What a package holds, for the one who is asked what of it is to come in: each block with its name, category, variants and pictures, and what stands in the way of its key
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$read					As read() answers
		 *	@param		string		$locale
		 *
		 *	@return 	array											[ [ key, name, category, variants (the labels), images, state ('free' or 'taken'), origin ('' or where the block of that key is from), replace (whether it may be replaced) ] ]
		 */
		public static function listed( array &$appData, array $read, string $locale ): array
		{
			$all = \Nino\Blocks::all( $appData );
			$out = [];

			foreach( $read['blocks'] as $key => $parts ) {

				$meta = (array) json_decode( (string) $parts['json'], true );
				$name = (array) ( $meta['name'] ?? [] );
				$taken = Sheet::taken( $appData, (string) $key );
				$origin = isset( $all[$key] ) === true ? (string) $all[$key]['origin'] : '';
				$variants = [];

				foreach( (array) ( $meta['variants'] ?? [] ) as $one )
					$variants[] = (string) ( ( (array) ( $one['label'] ?? [] ) )[$locale] ?? reset( $one['label'] ) ?: '' );

				$out[] = [
					'key'			=> (string) $key,
					'name'		=> (string) ( $name[$locale] ?? $name[\Nino\Blocks::FALLBACK] ?? reset( $name ) ?: $key ),
					'category'	=> (string) ( $meta['category'] ?? '' ),
					'variants'	=> $variants,
					'images'		=> count( (array) ( $meta['images'] ?? [] ) ),
					'state'		=> $taken === true ? 'taken' : 'free',
					'origin'		=> $origin,
					'replace'		=> $origin === 'project',
				];
			}

			return $out;
		}

		/**
		 *	Hold the bytes of a package that was read, for this session, and give the key to them: a number nobody can guess. One package at a time is held
		 *
		 *	@param		string		$bytes
		 *
		 *	@return 	string										The key
		 */
		public static function hold( string $bytes ): string {

			$id = bin2hex( random_bytes( 16 ) );
			$_SESSION[self::SESSION] = [ 'id' => $id, 'until' => time() + self::HOLD, 'sum' => hash( 'sha256', $bytes ), 'bytes' => $bytes ];

			return $id;
		}

		/**
		 *	The bytes that were held under a key, for the session that holds them and while they are held
		 *
		 *	@return 	string|null
		 */
		public static function held( string $id ): ?string {

			$held = $_SESSION[self::SESSION] ?? null;

			if( is_array( $held ) === false || is_string( $held['id'] ?? null ) === false || is_string( $held['bytes'] ?? null ) === false || hash_equals( $held['id'], $id ) === false )
				return null;

			if( (int) ( $held['until'] ?? 0 ) < time() || hash_equals( (string) ( $held['sum'] ?? '' ), hash( 'sha256', $held['bytes'] ) ) === false ) {
				unset( $_SESSION[self::SESSION] );
				return null;
			}

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
		 *	The first key that is free for a copy of a block: the key with a number behind it
		 *
		 *	@return 	string|null
		 */
		private static function _copyKey( array &$appData, string $key ): ?string {

			for( $count = 2; $count < 100; $count++ ) {

				$candidate = rtrim( substr( $key, 0, \Nino\Components::NAME_LENGTH - strlen( '-'. $count ) ), '-' ). '-'. $count;

				if( Sheet::taken( $appData, $candidate ) === false )
					return $candidate;
			}

			return null;
		}

		/**
		 *	The parts of a block with names no other block of the project has: a name that is another block's in a language gets the word for a copy behind it - the one change an import
		 *	makes to a file that it is not asked to make
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$parts
		 *	@param		string		$except				The key of the block that may keep its name
		 *	@param		bool			$always				Whether the name is changed whatever it is
		 *
		 *	@return 	array
		 */
		private static function _named( array &$appData, array $parts, string $except, bool $always ): array {

			$meta = (array) json_decode( (string) $parts['json'], true );
			$names = array_map( 'strval', (array) ( $meta['name'] ?? [] ) );
			$taken = [];

			foreach( \Nino\Blocks::all( $appData ) as $key => $block ) {

				if( (string) $key === $except )
					continue;

				$has = is_array( $block['name'] ?? null ) === true ? $block['name'] : [ \Nino\Blocks::FALLBACK => (string) ( $block['name'] ?? '' ) ];

				foreach( $has as $locale => $text )
					$taken[(string) $locale][mb_strtolower( trim( (string) $text ), 'UTF-8' )] = true;
			}

			$suffix = Sheet::words( array_keys( $names ), 'copy/suffix' );
			$changed = false;

			foreach( $names as $locale => $text ) {

				if( $always === false && isset( $taken[$locale][mb_strtolower( trim( $text ), 'UTF-8' )] ) === false )
					continue;

				$now = $text. ( $suffix[$locale] ?? ' copy' );

				for( $count = 2; isset( $taken[$locale][mb_strtolower( trim( $now ), 'UTF-8' )] ) === true && $count < 100; $count++ )
					$now = $text. ( $suffix[$locale] ?? ' copy' ). ' '. $count;

				if( mb_strlen( $now ) <= \Nino\Blocks::MAX_NAME ) {
					$names[$locale] = $now;
					$changed = true;
				}
			}

			if( $changed === true ) {
				$meta['name'] = $names;
				$parts['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";
			}

			return $parts;
		}

		/**
		 *	Write the chosen blocks of a package as blocks of the project, one after the other, each the way the editor saves one - held to the rule of the kernel and moved into its place in
		 *	one step. The first that cannot be written ends it: what was written stays, and the rest is not tried
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$read					As read() answers
		 *	@param		array			$choices			key => [ 'take' => bool, 'how' => 'skip' | 'copy' | 'replace' ]
		 *
		 *	@return 	array											[ [ key, result ('written', 'copied', 'replaced', 'skipped', 'left', 'failed', 'untried'), as (the key it was written under), code, reason ] ]
		 */
		public static function import( array &$appData, array $read, array $choices ): array {

			$results = [];
			$stopped = false;

			foreach( $read['blocks'] as $key => $parts ) {

				$key = (string) $key;
				$choice = is_array( $choices[$key] ?? null ) === true ? $choices[$key] : [];

				if( ( $choice['take'] ?? false ) !== true ) {
					$results[] = [ 'key' => $key, 'result' => 'left', 'as' => '' ];
					continue;
				}

				if( $stopped === true ) {
					$results[] = [ 'key' => $key, 'result' => 'untried', 'as' => '' ];
					continue;
				}

				$how = is_string( $choice['how'] ?? null ) === true && in_array( $choice['how'], [ 'skip', 'copy', 'replace' ], true ) === true ? $choice['how'] : 'skip';
				$origin = (string) ( \Nino\Blocks::all( $appData )[$key]['origin'] ?? '' );
				$taken = Sheet::taken( $appData, $key );

				// A key that is taken is no key for a block that came with a package: it is left, or it is the choice of a copy under a key of its own, or - and only where the block that has it is
				// the project's - it is replaced
				if( $taken === true && $how === 'skip' ) {
					$results[] = [ 'key' => $key, 'result' => 'skipped', 'as' => '' ];
					continue;
				}

				$made = [];

				if( $taken === false )
					$made = Custom::write( $appData, $key, Sheet::reidParts( $appData, self::_named( $appData, $parts, '', false ), $key ) ) + [ 'as' => $key, 'result' => 'written' ];
				else if( $how === 'replace' ) {

					if( $origin !== 'project' )
						$made = [ 'failure' => [ 403, 'only a block of the project is replaced', 'builder_own_block', [], 'key' ] ];
					else
						$made = Custom::replace( $appData, $key, self::_named( $appData, $parts, $key, false ) ) + [ 'as' => $key, 'result' => 'replaced' ];
				}
				else {

					$copy = self::_copyKey( $appData, $key );
					$made = $copy === null ? [ 'failure' => [ 409, 'taken', 'builder_own_taken', [], 'key' ] ] : Custom::write( $appData, $copy, Sheet::reidParts( $appData, self::_named( $appData, $parts, '', true ), $copy ) ) + [ 'as' => $copy, 'result' => 'copied' ];
				}

				if( isset( $made['failure'] ) === true ) {

					$stopped = true;
					$results[] = [ 'key' => $key, 'result' => 'failed', 'as' => '', 'code' => str_replace( 'builder_own_', '', (string) $made['failure'][2] ), 'status' => (int) $made['failure'][0] ];
					continue;
				}

				// What was written is a block of the project from now on: the list of the blocks is read again for the next
				\Nino\Blocks::forget( $appData );
				$results[] = [ 'key' => $key, 'result' => $made['result'], 'as' => $made['as'] ];
			}

			return $results;
		}
	}
}
