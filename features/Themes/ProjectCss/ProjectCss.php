<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\ProjectCss	see _nino/Nino/Modules/Modules.php for the
 *														package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The own css of the project: the stylesheet the kernel gives every site for it, private/assets/style.css, which belongs to
	 *										no theme and is in no package, and that the website shows under every theme - and the tab "Own CSS" of the pane of
	 *										the themes, which writes it (assets/admin-css.js)
	 *
	 *										The file is the one the kernel names for the layer "project" (\Nino\Modules\Themes::PROJECT_SHEET; docs/features.md): the
	 *										bundle puts it after the layer nino.theme, so whatever theme is on, the rules of the file win over the ones of the theme.
	 *										The kernel loads it with this feature off as well. What is in it - the comment the setup leaves, or what somebody wrote by
	 *										hand - is what the tab shows. It is never written by the compiler, never kept by the last state of the themes, never part
	 *										of a package: a theme change, a restore and an upload leave it as it is. (The stylesheet that the compiler writes is
	 *										another file, private/assets/theme.css, and so is the one that Store calls somebody else's where it is not the compiler's.)
	 *
	 *										The tab is one of the kernel's own kind (see Admin::tabs()) with a right of its own, \Nino\Modules\Themes::CSS_PERM: css that nobody has
	 *										checked is on every page of the site, which is more than to choose a theme. Without the right there is no tab and every
	 *										action answers 403. An account that has this right alone lands in the tab.
	 *
	 *										css				the text, its stamp and the pages a preview may show. Writes nothing
	 *										css-save	the text, over the one the stamp is of - or over any, with force - after it was checked
	 *										css-preview	a draft of the text for one request of the preview, with what is wrong with its braces
	 *
	 *										What is checked, and no more: the text is utf-8, has no NUL byte and is at most MAX_BYTES; it holds no "</style", no
	 *										"<!--" and no "-->" - the file is always delivered as a file of the bundle, but the preview puts the bundle into a style
	 *										element, and a text that could end it is refused in every case - and its braces, strings, comments and url() are closed,
	 *										which is what Compiler::balance() tells: a text that is not would end the block of its layer in the bundle and put what
	 *										follows into another. Everything else is css of the one who wrote it: elements, ids, url(), @import, !important.
	 *
	 *										The stamp is the sha256 of the bytes of the file, the one of no file is that of the empty text. A save that was made on
	 *										another stamp is 409 and says so; the file is looked at with a lock held, so that two saves are not both the first.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class ProjectCss {

		// The longest text, in bytes
		public const int MAX_BYTES = 262144;

		// What the tab of the pane of the themes is: after the fonts, before the tab of the settings
		public static function nav(): array {
			return [ 'themes-css', '/_admin/themes/css/right/label', 30 ];
		}

		// The name of the tab; the label of the right in the list of the roles is the one of nav()
		public static function tab(): string {
			return '/_admin/themes/css/tab/name';
		}

		public static function perm(): string {
			return \Nino\Modules\Themes::CSS_PERM;
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-css.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin-css.css' ) ];
		}

		// The words of the tab are those of the feature
		public static function text(): string {
			return Admin::text();
		}

		public static function actions(): array {

			return [
				'themes/css'					=> [ self::class, 'apiCss' ],
				'themes/css-save'			=> [ self::class, 'apiSave' ],
				'themes/css-preview'	=> [ self::class, 'apiPreview' ],
			];
		}

		public static function log( string $action, array $data ): string {
			return $action === 'themes/css-save' ? 'Save Project CSS' : '';
		}

		/**
		 *	The stamp of a text: what a save says it was made on
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string										A sha256
		 */
		public static function stamp( string $text ): string {
			return hash( 'sha256', $text );
		}

		/**
		 *	What the file holds, as the bytes it has: '' where there is none, false where it cannot be read
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string|false
		 */
		public static function read( array &$appData ): string|false {
			return Store::sheet( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::PROJECT_SHEET ) ) ?? '';
		}

		/**
		 *	Whether a text may be the file: the size, the encoding, no NUL, nothing that could end a style element or a comment of html. The braces are
		 *	not part of it (see faults())
		 *
		 *	@param		string		$css
		 *
		 *	@return 	array|null								null where it may, else [ status, message, code, params, field ]
		 */
		public static function refusal( string $css ): ?array {

			if( strlen( $css ) > self::MAX_BYTES )
				return [ 413, 'the css is longer than '. self::MAX_BYTES. ' bytes', 'themes_css_big', [ self::MAX_BYTES ], 'css' ];

			if( str_contains( $css, "\0" ) === true )
				return [ 400, 'the css has a NUL byte', 'themes_css_nul', [], 'css' ];

			if( mb_check_encoding( $css, 'UTF-8' ) === false )
				return [ 400, 'the css is not utf-8', 'themes_css_utf8', [], 'css' ];

			if( preg_match( '/<\/style|<!--|-->/i', $css, $found ) === 1 )
				return [ 400, 'the css has '. strtolower( $found[0] ), 'themes_css_markup', [ strtolower( $found[0] ) ], 'css' ];

			return null;
		}

		/**
		 *	What is wrong with the braces, strings, comments and url() of a text
		 *
		 *	@param		string		$css
		 *
		 *	@return 	array											open-comment, open-string, open-url, open-block, stray-close - each once; none where it is closed
		 */
		public static function faults( string $css ): array {
			return Compiler::balance( $css )[1];
		}

		/**
		 *	Answer a refusal
		 *
		 *	@param		array 		&$request			(reference) The request
		 *	@param		array			$failure			[ status, message, code, params, field ], as refusal() gives it
		 *
		 *	@return 	void
		 */
		private static function _fail( array &$request, array $failure ): void {
			\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
		}

		public static function apiCss( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$text = self::read( $appData );

			if( $text === false ) {
				\Nino\Http::fail( $request, 409, 'the stylesheet cannot be read', 'themes_css_locked', [], '' );
				return;
			}

			// What the file holds is shown, whatever the rule says of it: a text that breaks the rule of markup or braces is shown and cannot be saved until it keeps
			// it, so nothing is replaced that was not seen. What cannot be shown - longer than the limit, a NUL byte, bytes that are no utf-8 - is said and not
			// shown, and the tab offers nothing to write over it
			$problem = strlen( $text ) > self::MAX_BYTES ? 'themes_css_big' : ( str_contains( $text, "\0" ) === true ? 'themes_css_nul' : ( mb_check_encoding( $text, 'UTF-8' ) === false ? 'themes_css_utf8' : '' ) );

			\Nino\Http::ok( $request, [ 'css' => $problem === '' ? $text : '', 'stamp' => self::stamp( $text ), 'bytes' => strlen( $text ), 'max' => self::MAX_BYTES, 'problem' => $problem, 'exists' => is_file( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::PROJECT_SHEET ) ), 'pages' => Admin::pages( $appData ) ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_string( $data['css'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'the css is missing', 'themes_css_missing', [], 'css' );
				return;
			}

			$css = $data['css'];

			if( ( $failure = self::refusal( $css ) ) !== null ) {
				self::_fail( $request, $failure );
				return;
			}

			if( ( $faults = self::faults( $css ) ) !== [] ) {
				\Nino\Http::fail( $request, 400, 'the braces of the css are not closed: '. implode( ', ', $faults ), 'themes_css_faults', [ implode( ', ', $faults ) ], 'css' );
				return;
			}

			$base = is_string( $data['base'] ?? null ) === true ? $data['base'] : '';
			$force = ( $data['force'] ?? false ) === true;

			// The file is looked at with the lock held: two saves that were made on the same stamp are not both written
			if( \Nino\Filesystem::lockFile( $appData, \Nino\Modules\Themes::PROJECT_SHEET, false ) === false ) {
				\Nino\Http::fail( $request, 409, 'the css is being saved by another request', 'themes_css_busy', [], '' );
				return;
			}

			$path = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::PROJECT_SHEET );
			$was = Store::sheet( $path );

			if( $was === false ) {
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::PROJECT_SHEET );
				\Nino\Http::fail( $request, 409, 'the stylesheet cannot be read', 'themes_css_locked', [], '' );
				return;
			}

			if( $force === false && hash_equals( self::stamp( $was ?? '' ), $base ) === false ) {
				\Nino\Filesystem::unlockFile( $appData, \Nino\Modules\Themes::PROJECT_SHEET );
				\Nino\Http::fail( $request, 409, 'the css was changed since it was opened', 'themes_css_changed', [], 'css' );
				return;
			}

			$before = $was === null ? null : (int) filemtime( $path );

			// The write releases the lock
			if( \Nino\Filesystem::putFileContent( $appData, \Nino\Modules\Themes::PROJECT_SHEET, $css, true ) === false || Store::sheet( $path ) !== $css ) {
				\Nino\Http::fail( $request, 500, 'the css could not be written', 'themes_css_write', [], '' );
				return;
			}

			// The stamp of the bundle is the time of the file: a text saved in the second of the one before it, in the same size, is a new stamp as well
			clearstatcache( true, $path );

			if( $before !== null && (int) filemtime( $path ) <= $before )
				touch( $path, $before + 1 );

			unset( $appData['./nino/html/cache'] );
			\Nino\Preview::tidy( $appData );

			\Nino\Http::ok( $request, [ 'saved' => true, 'stamp' => self::stamp( $css ), 'bytes' => strlen( $css ) ] );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( is_string( $data['css'] ?? null ) === false ) {
				\Nino\Http::fail( $request, 400, 'the css is missing', 'themes_css_missing', [], 'css' );
				return;
			}

			if( ( $failure = self::refusal( $data['css'] ) ) !== null ) {
				self::_fail( $request, $failure );
				return;
			}

			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Themes::CSS_KIND, self::perm(), [ 'css' => $data['css'] ], \Nino\Admin\Admin::postString( $data, 'token' ), \Nino\Modules\Themes::PREVIEWS_MAX );

			if( $token === '' ) {
				\Nino\Http::fail( $request, 500, 'the preview could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'faults' => self::faults( $data['css'] ) ] );
		}
	}
}
