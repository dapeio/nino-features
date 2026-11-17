<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\Typefaces	see _nino/Nino/Modules/Modules.php for the
 *														package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The fonts of the project: the families that stand in public/fonts/, the one that is installed from the repository
	 *										and the one that is deleted - and the tab "Fonts" of the pane of the themes, which asks for them
	 *										(assets/admin-fonts.js)
	 *
	 *										The tab is one of the kernel's own kind (see Admin::tabs()): its uri, its word, its mount, its files and its
	 *										actions, under the right of the panel. The two settings of the repository are no part of it: the workbench
	 *										draws their form from the manifest, as the tab "Settings" that follows this one.
	 *
	 *										fonts					the families that are installed, where the brand has each, and what the repository is: its state, the list
	 *																	that was last fetched. Fetches nothing
	 *										fonts-load		fetch the signed list now (the one request the repository gets until an install), verify it and keep it
	 *										fonts-install	install one family of the list that was kept
	 *										fonts-remove	delete one installed family
	 *										fonts-license	the licence text of an installed family
	 *
	 *										An install is whole or it is nothing. The archive named by the list is fetched over https, held to the size and
	 *										the sha256 the signed list gives it, read in memory (see \Nino\Archive) - one directory, plain files, none that is not
	 *										named - and looked at: its font.json says what the list said, every file named is there and begins and measures
	 *										as a woff2 file does, the licence is there and is a text. Only then is the family written - into a folder of
	 *										its own, public/fonts/.work-<random>/, with the font.json the brand reads (see Brand::fonts()) and the licence as
	 *										LICENSE.txt - and moved into public/fonts/<slug>/ with one rename. The work stands beside its target because a
	 *										folder is renamed within one filesystem only; its name begins with a dot, so the web server delivers nothing of
	 *										it (router.php and .htaccess of the kernel refuse a dot path) and the brand does not read it. A family of that
	 *										name that this feature installed is moved aside first and put back where the move fails; a folder of that name
	 *										that it did not install is not touched. The folder of the work is gone on every way out, what a request that
	 *										was killed left is cleared by the next one (see _sweep()), and a lock that is not waited for lets one install or
	 *										delete at a time.
	 *
	 *										What stands in public/fonts/<slug>/ is delivered to everybody, so every file there has an ending the web server
	 *										knows a type for: .woff2, .json and .txt.
	 *
	 *										A family the brand uses - an own value of the brand, or what the theme on the website suggests - is not deleted:
	 *										a font that is taken from under a stylesheet leaves the site in the stack of the system, and that is for a
	 *										person to choose in the brand, not to be the result of a click in a list. And a delete takes what was installed
	 *										from here: a folder somebody put into public/fonts/ by hand may hold what no repository brings back.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Typefaces {

		// The lock that lets one install or delete at a time, beside the list that is kept
		public const string LOCK = '/data/theme/.fonts/install.lock';

		// What the folder of an install or a delete is called, below public/fonts/ and followed by twelve random characters - and how old one
		// that a killed request left has to be, in seconds, before the next request clears it
		public const string WORK = '.work-';
		public const int WORK_AGE = 3600;

		// The licence of a family as it stands in its folder: the archive brings LICENSE, and a file without an ending is one a web server
		// sends without a type, for a browser to guess
		public const string LICENSE = 'LICENSE.txt';

		// A name of a file of a family in its archive: a plain name, in the folder of the family itself and in none below it
		private const string FILE_NAME = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,80}$/D';

		// The licence text a panel is sent
		public const int MAX_LICENSE_BYTES = 65536;

		// What the fallback of font.json is for each category of the list
		private const array FALLBACKS = [ 'sans' => 'sans-serif', 'serif' => 'serif', 'display' => 'sans-serif', 'handwriting' => 'sans-serif', 'mono' => 'monospace', 'other' => 'sans-serif' ];

		// The keys of the brand that name a font, in the order of their roles
		private const array ROLES = [ 'font-title', 'font-text', 'font-button' ];

		// The tab of the pane of the themes: after the panel's own screen, before the tab of the settings
		public static function nav(): array {
			return [ 'themes-fonts', '/_admin/themes/fonts/tab/fonts', 20 ];
		}

		public static function perm(): string {
			return Admin::perm();
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-fonts.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-fonts.css' ) ];
		}

		// The words of the tab are those of the feature
		public static function text(): string {
			return Admin::text();
		}

		public static function actions(): array {

			return [
				'themes/fonts'				=> [ self::class, 'apiFonts' ],
				'themes/fonts-load'		=> [ self::class, 'apiLoad' ],
				'themes/fonts-install'	=> [ self::class, 'apiInstall' ],
				'themes/fonts-remove'	=> [ self::class, 'apiRemove' ],
				'themes/fonts-license'	=> [ self::class, 'apiLicense' ],
			];
		}

		public static function log( string $action, array $data ): string {

			$slug = mb_substr( (string) preg_replace( '/[^a-z0-9-]/', '?', \Nino\Admin\Admin::postString( $data, 'slug' ) ), 0, 60 );

			return match( $action ) {
				'themes/fonts-load'		=> 'Load Font List',
				'themes/fonts-install'	=> 'Install Font '. $slug,
				'themes/fonts-remove'	=> 'Delete Font '. $slug,
				default								=> '',
			};
		}

		public static function apiFonts( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Admin::perm() ) === false )
				return;

			\Nino\Http::ok( $request, self::view( $appData ) );
		}

		public static function apiLoad( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Admin::perm() ) === false )
				return;

			$kind = '';
			$list = Repository::fetch( $appData, $kind );

			if( is_string( $list ) === true ) {
				$failure = self::_refused( $kind, $list );
				\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
				return;
			}

			\Nino\Http::ok( $request, [ 'loaded' => count( $list ) ] + self::view( $appData ) );
		}

		public static function apiInstall( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Admin::perm() ) === false )
				return;

			$slug = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'slug' );
			$updated = isset( Brand::fonts( $appData )[$slug] ) === true;
			$failure = self::install( $appData, $slug );

			if( $failure !== null ) {
				\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
				return;
			}

			\Nino\Http::ok( $request, [ 'result' => [ 'slug' => $slug, 'updated' => $updated ] ] + self::view( $appData ) );
		}

		public static function apiRemove( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Admin::perm() ) === false )
				return;

			$slug = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'slug' );
			$failure = self::remove( $appData, $slug );

			if( $failure !== null ) {
				\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
				return;
			}

			\Nino\Http::ok( $request, [ 'result' => [ 'slug' => $slug ] ] + self::view( $appData ) );
		}

		public static function apiLicense( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, Admin::perm() ) === false )
				return;

			$slug = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'slug' );
			$text = self::license( $appData, $slug );

			if( $text === null ) {
				\Nino\Http::fail( $request, 404, 'there is no licence of that font', 'themes_fonts_unknown', [], 'slug' );
				return;
			}

			\Nino\Http::ok( $request, [ 'slug' => $slug, 'text' => $text ] );
		}

		/**
		 *	What the screen of the fonts is drawn from: the families that are installed, the repository and the list that was last fetched,
		 *	and the fonts of the brand as the list of the themes says them. Nothing is fetched
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											'installed', 'repository', 'available', 'fonts'
		 */
		public static function view( array &$appData ): array {

			$installed = self::installed( $appData );
			$have = array_column( $installed, null, 'key' );
			$cached = Repository::cached( $appData );
			$available = null;

			if( $cached !== null ) {

				$families = [];

				foreach( $cached['families'] as $entry )
					$families[] = [
						'slug'			=> $entry['slug'],
						'family'		=> $entry['family'],
						'category'		=> $entry['category'],
						'version'		=> $entry['version'],
						'license'		=> $entry['license'],
						'source'		=> $entry['source'],
						'weights'		=> $entry['weights'],
						'styles'		=> $entry['styles'],
						'size'			=> $entry['size'],
						'problem'		=> Repository::problem( $entry ),
						'installed'	=> isset( $have[$entry['slug']] ) === true && $have[$entry['slug']]['made'] === true ? $have[$entry['slug']]['version'] : null,
						'foreign'		=> isset( $have[$entry['slug']] ) === true && $have[$entry['slug']]['made'] === false,
					];

				$available = [ 'fetched' => $cached['fetched'], 'stale' => Repository::old( $cached['fetched'] ), 'families' => $families ];
			}

			$url = Repository::url( $appData );
			$own = \Nino\Features::setting( $appData, 'themes', 'fontsKey', '' );

			return [
				'installed'		=> $installed,
				'repository'	=> [
					'state'		=> Repository::state( $appData ),
					'host'		=> $url === '' ? '' : (string) parse_url( $url, PHP_URL_HOST ),
					'ownKey'	=> is_string( $own ) === true && trim( $own ) !== '',
					// Whether the workbench has the form of the two settings for this account: the Settings tab of the feature's panel, which the kernel gives it
					'settings'	=> class_exists( '\\Nino\\Modules\\Features\\Settings' ) === true && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Features\Settings::perm() ) === true,
				],
				'available'		=> $available,
				'fonts'				=> self::fonts( $appData ),
			];
		}

		/**
		 *	The fonts there are, as the list of the themes says them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											A list of { key, name, system }
		 */
		public static function fonts( array &$appData ): array {

			$fonts = [];

			foreach( Brand::fonts( $appData ) as $slug => $font )
				$fonts[] = [ 'key' => $slug, 'name' => $font['name'], 'system' => $font['system'] ];

			return $fonts;
		}

		/**
		 *	The families of public/fonts/, each as the screen shows it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											A list of { key, name, faces, size, license, version, category, source, made (installed by this feature), licensed (it has a licence text), used (the keys of the brand) }
		 */
		public static function installed( array &$appData ): array {

			$usage = self::usage( $appData );
			$list = [];

			foreach( Brand::fonts( $appData ) as $slug => $font ) {

				if( $font['system'] === true )
					continue;

				$dir = \Nino\Filesystem::path( $appData, '/fonts/'. $slug );
				$declared = \Nino\Filesystem::getFileContent( $appData, '/fonts/'. $slug. '/font.json', [] );
				$declared = is_array( $declared ) === true ? $declared : [];
				$size = 0;

				foreach( array_slice( (array) @scandir( $dir ), 0, 60 ) as $name )
					if( is_file( $dir. '/'. $name ) === true )
						$size += (int) @filesize( $dir. '/'. $name );

				$source = $declared['source'] ?? null;

				$list[] = [
					'key'			=> $slug,
					'name'		=> $font['name'],
					'faces'		=> $font['faces'],
					'size'		=> $size,
					'license'	=> is_string( $declared['license'] ?? null ) === true && in_array( $declared['license'], Repository::LICENSES, true ) === true ? $declared['license'] : '',
					'version'	=> self::_made( $declared, $slug ) === true ? $declared['version'] : '',
					'category'	=> is_string( $declared['category'] ?? null ) === true && in_array( $declared['category'], Repository::CATEGORIES, true ) === true ? $declared['category'] : '',
					'source'	=> is_string( $source ) === true && \Nino\Fetch::isHttpsUrl( $source ) === true ? $source : '',
					'made'		=> self::_made( $declared, $slug ),
					'licensed'	=> is_file( $dir. '/'. self::LICENSE ) === true && is_link( $dir. '/'. self::LICENSE ) === false,
					'used'		=> $usage[$slug] ?? [],
				];
			}

			return $list;
		}

		/**
		 *	Which keys of the brand each font stands for: an own value of the brand, and what the theme that is on the website suggests where the
		 *	brand has none of its own. That is what the stylesheet of the website draws and what it loads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											slug => [ key, ... ], in the order of the roles
		 */
		public static function usage( array &$appData ): array {

			$state = Store::read( $appData );
			$active = $state['active'];
			$mix = null;

			if( $active !== null ) {

				$own = str_starts_with( $active['theme'], 'own:' ) === true;
				$given = $own === true ? ( $state['own'][substr( $active['theme'], 4 )] ?? null ) : ( $active['mix'] ?? null );
				$clean = $given === null ? '' : Mix::clean( $appData, $given, $own );
				$mix = is_array( $clean ) === true ? $clean : null;
			}

			$values = [];

			if( $mix !== null ) {

				$plan = Compiler::plan( $appData, $mix, $state['brand'] );

				foreach( self::ROLES as $key )
					if( in_array( $key, $plan['named'], true ) === true )
						$values[$key] = (string) $plan['brand'][$key];
			}
			else
				foreach( self::ROLES as $key )
					if( ( $state['brand'][$key]['own'] ?? false ) === true )
						$values[$key] = (string) $state['brand'][$key]['value'];

			$usage = [];

			foreach( $values as $key => $slug )
				$usage[$slug][] = $key;

			return $usage;
		}

		/**
		 *	Whether a stylesheet loads a family by other files than its folder holds: an update brought the family other faces, and the stylesheet
		 *	that was built before it names the ones that were there - or the folder of the family is gone, and the stylesheet names files nobody
		 *	delivers. Building it once more writes what is there: the faces the folder has, or the stack of the system by the rule of the brand
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$css					The stylesheet as the compiler wrote it
		 *
		 *	@return 	bool
		 */
		public static function drifted( array &$appData, string $css ): bool {

			if( (int) preg_match_all( '#url\("\[\[/nino/public\]\]/fonts/([a-z0-9-]+)/([A-Za-z0-9._-]+)"\)#', $css, $found, PREG_SET_ORDER ) < 1 )
				return false;

			$fonts = Brand::fonts( $appData );
			$loaded = [];

			foreach( $found as $one )
				$loaded[$one[1]][$one[2]] = true;

			foreach( $loaded as $slug => $files ) {

				if( isset( $fonts[$slug] ) === false )
					return true;

				$has = array_column( $fonts[$slug]['faces'], 2 );
				$named = array_keys( $files );

				sort( $has, SORT_STRING );
				sort( $named, SORT_STRING );

				if( $has !== $named )
					return true;
			}

			return false;
		}

		/**
		 *	Install one family of the list that was fetched, whole
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					A slug of the list
		 *
		 *	@return 	array|null								null where it is installed, else [ status, message, code, params, field ]
		 */
		public static function install( array &$appData, string $slug ): ?array {

			if( preg_match( Repository::SLUG, $slug ) !== 1 )
				return [ 400, 'that is no slug of a font', 'themes_fonts_unknown', [], 'slug' ];

			$state = Repository::state( $appData );

			if( $state !== 'ready' )
				return self::_refused( $state, 'the font repository cannot be asked: '. $state );

			$cached = Repository::cached( $appData );
			$entry = null;

			foreach( $cached['families'] ?? [] as $candidate )
				if( $candidate['slug'] === $slug )
					$entry = $candidate;

			if( $entry === null )
				return [ 404, 'the font "'. $slug. '" is not in the list - load the list first', 'themes_fonts_unknown', [], 'slug' ];

			if( Repository::old( $cached['fetched'] ) === true )
				return [ 409, 'the list was fetched more than a day ago - load it again', 'themes_fonts_stale', [], '' ];

			if( Repository::problem( $entry ) !== '' )
				return [ 409, 'the font "'. $slug. '" cannot be installed here: '. Repository::problem( $entry ), 'themes_fonts_not_installable', [ $entry['family'] ], 'slug' ];

			$addresses = \Nino\Fetch::check( $appData, $entry['archive'] );

			if( is_string( $addresses ) === true )
				return [ 409, 'the archive of the font is not at a public host', 'themes_fonts_internal', [], '' ];

			$lock = self::_lock( $appData );

			if( is_array( $lock ) === true )
				return $lock;

			$work = '';

			try {

				$archive = \Nino\Fetch::get( $appData, $entry['archive'], [ 'maxBytes' => $entry['size'] + 1, 'timeout' => Repository::TIMEOUT_ARCHIVE, 'addresses' => $addresses ] );

				if( $archive['ok'] === false )
					return [ 502, 'the archive could not be fetched: '. $archive['error'], 'themes_fonts_download', [], '' ];

				if( strlen( $archive['body'] ) !== $entry['size'] || hash_equals( $entry['sha256'], hash( 'sha256', $archive['body'] ) ) === false )
					return [ 502, 'the archive does not match what the list promised', 'themes_fonts_mismatch', [], '' ];

				// The files of a family are the faces, the manifest and the licence - and the folder is an entry of the archive as well; a name that is no plain
				// name of a file, as a file in a folder of the folder, is none the family names
				$files = \Nino\Archive::read( $archive['body'], $slug, Repository::MAX_FACES + 3, Repository::MAX_FILE_BYTES, Repository::MAX_FAMILY_BYTES, self::FILE_NAME );
				$files = is_string( $files ) === true ? ( \Nino\Catalogue::ARCHIVE_WHY[$files] ?? $files ) : $files;
				$looked = is_array( $files ) === true ? self::_look( $files, $entry ) : $files;

				if( $looked !== true )
					return [ 502, 'the archive is refused: '. $looked, 'themes_fonts_archive', [], '' ];

				\Nino\Filesystem::forceDir( $appData, '/fonts' );
				self::_sweep( $appData );

				$work = \Nino\Filesystem::path( $appData, '/fonts' ). '/'. self::WORK. bin2hex( random_bytes( 6 ) );

				return self::_place( $appData, $files, $entry, $work );
			}
			finally {

				// However that came back: the work is no copy of the archive to leave below data/, and the lock is the next install's
				if( $work !== '' )
					\Nino\Filesystem::removeDir( $work );

				flock( $lock, LOCK_UN );
				fclose( $lock );
			}
		}

		/**
		 *	Delete one family that was installed from here - unless the brand uses it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					The folder's name
		 *
		 *	@return 	array|null								null where it is deleted, else [ status, message, code, params, field ]
		 */
		public static function remove( array &$appData, string $slug ): ?array {

			if( preg_match( Repository::SLUG, $slug ) !== 1 || isset( Brand::SYSTEM[$slug] ) === true )
				return [ 400, 'that is no slug of a font', 'themes_fonts_unknown', [], 'slug' ];

			$dir = \Nino\Filesystem::path( $appData, '/fonts/'. $slug );

			// The folders the brand reads are the ones that are fonts: nothing else of public/fonts/ is deleted from here
			if( isset( Brand::fonts( $appData )[$slug] ) === false || is_dir( $dir ) === false || is_link( $dir ) === true )
				return [ 404, 'the font "'. $slug. '" is not installed', 'themes_fonts_unknown', [], 'slug' ];

			$lock = self::_lock( $appData );

			if( is_array( $lock ) === true )
				return $lock;

			$trash = \Nino\Filesystem::path( $appData, '/fonts' ). '/'. self::WORK. bin2hex( random_bytes( 6 ) );

			try {

				self::_sweep( $appData );

				$declared = \Nino\Filesystem::getFileContent( $appData, '/fonts/'. $slug. '/font.json', [] );

				// What was put there by hand is not this feature's to delete: nothing brings it back
				if( self::_made( is_array( $declared ) === true ? $declared : [], $slug ) === false )
					return [ 409, 'the folder "'. $slug. '" was not installed from here and is not deleted from here', 'themes_fonts_handmade', [ $slug ], 'slug' ];

				// Looked at with the lock held: what a brand that was saved a moment ago says is what counts
				if( ( self::usage( $appData )[$slug] ?? [] ) !== [] )
					return [ 409, 'the brand uses the font "'. $slug. '"', 'themes_fonts_used', [], 'slug' ];

				// One rename takes the family out of the folders the brand reads and the web server delivers - what is left of a delete that stops
				// half way is a folder of the work and not a font without its files
				if( @rename( $dir, $trash ) === false )
					return [ 500, 'the font could not be moved out of public/fonts', 'themes_fonts_remove', [], '' ];

				return null;
			}
			finally {

				\Nino\Filesystem::removeDir( $trash );
				flock( $lock, LOCK_UN );
				fclose( $lock );
			}
		}

		/**
		 *	The licence text of an installed family
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug
		 *
		 *	@return 	string|null								As text - what is not utf-8 is replaced - and cut at MAX_LICENSE_BYTES; null where there is none
		 */
		public static function license( array &$appData, string $slug ): ?string {

			if( preg_match( Repository::SLUG, $slug ) !== 1 || isset( Brand::fonts( $appData )[$slug] ) === false )
				return null;

			$file = \Nino\Filesystem::path( $appData, '/fonts/'. $slug. '/'. self::LICENSE );

			if( is_file( $file ) === false || is_link( $file ) === true )
				return null;

			$text = @file_get_contents( $file, false, null, 0, self::MAX_LICENSE_BYTES );

			return is_string( $text ) === true ? mb_scrub( $text, 'UTF-8' ) : null;
		}

		/**
		 *	Whether this feature installed the folder: its font.json says which family and which version
		 *
		 *	@param		array			$declared			The decoded font.json
		 *	@param		string		$slug
		 *
		 *	@return 	bool
		 */
		private static function _made( array $declared, string $slug ): bool {
			return ( $declared['slug'] ?? null ) === $slug && is_string( $declared['version'] ?? null ) === true && $declared['version'] !== '';
		}

		/**
		 *	The lock that lets one install or one delete work at a time: not waited for
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	resource|array						The handle, or the failure
		 */
		private static function _lock( array &$appData ): mixed {

			\Nino\Filesystem::forceDir( $appData, dirname( self::LOCK ) );

			$handle = @fopen( \Nino\Filesystem::path( $appData, self::LOCK ), 'c' );

			if( $handle === false )
				return [ 500, 'the work folder of the fonts cannot be written', 'themes_fonts_write', [], '' ];

			if( flock( $handle, LOCK_EX | LOCK_NB ) === false ) {
				fclose( $handle );
				return [ 409, 'the fonts are being changed by another request', 'themes_fonts_busy', [], '' ];
			}

			return $handle;
		}

		/**
		 *	Clear what a request that was killed in its work left in public/fonts/, with the lock held. A family that was moved aside for an update
		 *	whose new one never came is put back at once - the lock says that no request is between its two moves. A folder of the work is removed
		 *	once it is older than WORK_AGE: where the lock does not hold, on a filesystem that does not know one, a younger one may be at work.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _sweep( array &$appData ): void {

			$fonts = \Nino\Filesystem::path( $appData, '/fonts' );

			foreach( is_dir( $fonts ) === true ? (array) scandir( $fonts ) : [] as $name ) {

				if( is_string( $name ) === false || preg_match( '/^'. preg_quote( self::WORK, '/' ). '[a-f0-9]{12}$/D', $name ) !== 1 || is_link( $fonts. '/'. $name ) === true || is_dir( $fonts. '/'. $name ) === false )
					continue;

				$previous = $fonts. '/'. $name. '/previous';
				$declared = is_file( $previous. '/font.json' ) === true ? json_decode( (string) file_get_contents( $previous. '/font.json' ), true ) : null;
				$slug = is_array( $declared ) === true && is_string( $declared['slug'] ?? null ) === true && preg_match( Repository::SLUG, $declared['slug'] ) === 1 ? $declared['slug'] : '';

				// Put back only between the two moves of an update: the new family is still in the folder of the work. Where it is gone the
				// update was done and what is set aside is the old one, which a later delete must not bring back; and a link is no family
				if( $slug !== '' && is_link( $previous ) === false && is_dir( $previous ) === true && file_exists( $fonts. '/'. $name. '/new' ) === true
					&& file_exists( $fonts. '/'. $slug ) === false && is_link( $fonts. '/'. $slug ) === false )
					@rename( $previous, $fonts. '/'. $slug );

				if( time() - (int) filemtime( $fonts. '/'. $name ) > self::WORK_AGE )
					\Nino\Filesystem::removeDir( $fonts. '/'. $name );
			}
		}

		/**
		 *	The failure that answers a refusal of the repository
		 *
		 *	@param		string		$kind					One of Repository::REFUSALS
		 *	@param		string		$reason				The English reason
		 *
		 *	@return 	array
		 */
		private static function _refused( string $kind, string $reason ): array {

			if( in_array( $kind, Repository::REFUSALS, true ) === false )
				return [ 502, $reason, 'themes_fonts_unreachable', [], '' ];

			return [ in_array( $kind, [ 'unreachable', 'signature', 'parse' ], true ) === true ? 502 : 409, $reason, 'themes_fonts_'. $kind, [], '' ];
		}

		/**
		 *	Whether what came out of the archive is the family the signed list promised - and nothing else. Its font.json is the entry's manifest,
		 *	every file the manifest names is there and is a woff2 file, the licence is there and says something, and no file besides them is.
		 *
		 *	@param		array			$files				name => content, as \Nino\Archive::read() answers it
		 *	@param		array			$entry				The entry of the list
		 *
		 *	@return 	true|string
		 */
		private static function _look( array $files, array $entry ): true|string {

			$names = array_column( $entry['files'], 'file' );
			$more = array_values( array_diff( array_keys( $files ), $names, [ 'font.json', 'LICENSE' ] ) );

			if( $more !== [] )
				return 'it holds "'. $more[0]. '", which the list does not name';

			if( isset( $files['font.json'] ) === false )
				return 'it holds no font.json';

			$manifest = Repository::manifest( json_decode( $files['font.json'], true ) );

			if( is_string( $manifest ) === true )
				return 'its font.json: '. $manifest;

			foreach( Repository::MANIFEST as $field )
				if( $manifest[$field] !== $entry[$field] )
					return 'its font.json says another "'. $field. '" than the list promised';

			$licence = $files['LICENSE'] ?? '';

			if( trim( $licence ) === '' )
				return 'it holds no licence text';

			// A text and nothing a browser or a server would take for another kind of file
			if( str_contains( $licence, "\0" ) === true || mb_check_encoding( $licence, 'UTF-8' ) === false )
				return 'its licence is no text: it has to be utf-8 without a null byte';

			foreach( $names as $name ) {

				if( isset( $files[$name] ) === false )
					return 'font.json names "'. $name. '", which it does not hold';

				$bytes = $files[$name];

				// The signature, and the length a woff2 file says of itself in its header
				if( strlen( $bytes ) < 48 || str_starts_with( $bytes, 'wOF2' ) === false || unpack( 'N', substr( $bytes, 8, 4 ) )[1] !== strlen( $bytes ) )
					return '"'. $name. '" is no woff2 file';
			}

			return true;
		}

		/**
		 *	Write the family into a folder of the work and move it into place
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$files				As \Nino\Archive::read() answers it, looked at
		 *	@param		array			$entry				The entry of the list
		 *	@param		string		$work					The folder of this install, which does not exist yet
		 *
		 *	@return 	array|null
		 */
		private static function _place( array &$appData, array $files, array $entry, string $work ): ?array {

			$new = $work. '/new';

			if( @mkdir( $new, 0755, true ) === false )
				return [ 500, 'the work folder of the fonts cannot be written', 'themes_fonts_write', [], '' ];

			$faces = [];

			foreach( $entry['files'] as $file )
				$faces[] = [ 'weight' => $file['weight'], 'style' => $file['style'], 'file' => $file['file'] ];

			// The font.json the brand reads, with what the list says of the family beside it
			$declared = json_encode( [
				'name'			=> $entry['family'],
				'fallback'	=> self::FALLBACKS[$entry['category']],
				'faces'			=> $faces,
				'slug'			=> $entry['slug'],
				'category'	=> $entry['category'],
				'version'		=> $entry['version'],
				'license'		=> $entry['license'],
				'source'		=> $entry['source'],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

			$write = [ 'font.json' => (string) $declared. "\n", self::LICENSE => $files['LICENSE'] ] + array_diff_key( $files, [ 'font.json' => 1, 'LICENSE' => 1 ] );

			foreach( $write as $name => $bytes )
				if( @file_put_contents( $new. '/'. $name, $bytes ) !== strlen( $bytes ) || @chmod( $new. '/'. $name, 0644 ) === false )
					return [ 500, 'the work folder of the fonts cannot be written', 'themes_fonts_write', [], '' ];

			@chmod( $new, 0755 );

			$target = \Nino\Filesystem::path( $appData, '/fonts/'. $entry['slug'] );
			$previous = $work. '/previous';
			$had = file_exists( $target ) === true || is_link( $target ) === true;

			if( $had === true ) {

				$declaredBefore = \Nino\Filesystem::getFileContent( $appData, '/fonts/'. $entry['slug']. '/font.json', [] );

				// What this feature did not install is not its to replace
				if( is_link( $target ) === true || is_dir( $target ) === false || self::_made( is_array( $declaredBefore ) === true ? $declaredBefore : [], $entry['slug'] ) === false )
					return [ 409, 'a folder of that name is in public/fonts and this feature did not install it', 'themes_fonts_foreign', [ $entry['slug'] ], 'slug' ];

				if( @rename( $target, $previous ) === false )
					return [ 500, 'the installed font could not be moved aside', 'themes_fonts_write', [], '' ];
			}

			if( @rename( $new, $target ) === false ) {

				if( $had === true )
					@rename( $previous, $target );

				return [ 500, 'the font could not be moved into public/fonts', 'themes_fonts_write', [], '' ];
			}

			return null;
		}
	}
}
