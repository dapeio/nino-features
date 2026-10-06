<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Nino\Modules\Templates\Library	The section presets: manifest-backed entry points for the Section Composer
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Templates {

	/**
	 *	Manifest-backed entry points for the named-area Section Composer.
	 *	Only version-3 manifests are exposed; their section templates remain
	 *	code-authored while the UI edits normalized areas and bindings.
	 */
	class Library {

		private const string DIRECTORY = __DIR__. '/../library';
		private const int MAX_PREVIEW_CSS_BYTES = 2_000_000;
		private const int MAX_PREVIEW_FONT_BYTES = 2_000_000;
		private const array PREVIEW_FONT_MIME_TYPES = [
			'woff2'	=> 'font/woff2',
			'woff'	=> 'font/woff',
			'ttf'	=> 'font/ttf',
			'otf'	=> 'font/otf',
		];
		/*	The library is its directory. Every library/<key>/manifest.php of
			version 3 that normalizes is offered - nothing lists the presets a
			second time, so one a project drops in is on offer without a line
			anywhere else, and one that is taken out is gone with it. Where a
			preset stands in the list is the manifest's own 'weight', ascending,
			the way a panel's nav() weight places it in the rail; the shipped
			ones run from 10 to 170 in steps of ten so a project's own takes any
			number between, and a manifest that names no weight comes after
			every one that does, in key order. templates-smoke.php holds the rule
			with two presets it writes into the directory for the length of its
			run	*/
		private const string KEY_PATTERN = '/^[a-z0-9][a-z0-9-]*$/';

		public static function actions(): array {
			return [
				'library/list'		=> [ self::class, 'apiList' ],
				'library/compose'	=> [ self::class, 'apiCompose' ],
				'library/preview'	=> [ self::class, 'apiPreview' ],
			];
		}

		public static function apiList( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;
			$presets = array_values( self::presets() );
			foreach( $presets as &$preset ) {
				$preset = self::publicPreset( $preset );
				$preset['defaults'] = AreaComposer::defaults( $preset, 'preview', 'preview-'. $preset['key'] );
				$preview = Composer::preview( [
					'preset' => $preset['key'],
					'pageId' => 'preview',
					'id' => 'preview-'. $preset['key'],
				], self::_text( $appData ) );
				if( $preview !== null )
					$preset['preview'] = $preview;
			}
			unset( $preset );
			\Nino\Http::ok( $request, [
				'presets'	=> $presets,
				'choices'	=> AreaComposer::choices(),
				'fallbacks'	=> AreaComposer::fallbacks(),
				'previewCss' => self::_previewCss( $appData ),
			] );
		}

		public static function apiCompose( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$data = Admin::postData();
			$name = (string) ( $data['name'] ?? '' );
			$category = Documents::category( $appData, $name );

			if( $category === null ) {
				\Nino\Http::fail( $request, 400, 'the page template is unknown, or its name gives no category' );
				return;
			}

			try {
				$result = Composer::compose( $data );
			} catch( \InvalidArgumentException $exception ) {
				\Nino\Http::fail( $request, 400, $exception->getMessage() );
				return;
			}

			// The keys a section creates are /template/<category>/<its id>/...,
			// and the category is the document's, not the draft's to choose
			if( (string) $result['spec']['pageId'] !== $category ) {
				\Nino\Http::fail( $request, 400, 'the page ID of a section is the category of its page template, '. $category );
				return;
			}

			// The sections of the panel's open draft, which may not be saved yet:
			// a section inserted a moment ago has written its keys already, and
			// updating it is not a clash with itself
			$draftIds = array_values( array_filter( (array) ( $data['sectionIds'] ?? [] ), static fn( mixed $id ): bool => is_string( $id ) ) );
			$taken = self::_takenId( $appData, $name, $category, (string) $result['spec']['id'], $draftIds );
			if( $taken !== null ) {
				\Nino\Http::fail( $request, 409, $taken['error'], 'section-id-taken', $taken['params'] );
				return;
			}

			\Nino\Http::ok( $request, $result );
		}

		/**
		 *	Whether a new section's id would make it write keys that are not its
		 *	own. A hand-written page-services.tpl reads /template/page-services/
		 *	intro/title; a section with the id "intro" creates exactly that key,
		 *	and whoever edited the section would be editing the introduction too,
		 *	without a word about it. Keys of a section the document already holds
		 *	are that section's, and a section being updated finds its own - one
		 *	the file holds, or one the open draft holds and the file does not yet
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The page template's name
		 *	@param		string		$category			Its category
		 *	@param		string		$id						The section's id
		 *	@param		array			$draftIds			The ids of the Builder sections in the panel's open draft
		 *
		 *	@return 	array|null						Why not, as { error, params: [ id, key, a free id ] }, or null
		 */
		private static function _takenId( array &$appData, string $name, string $category, string $id, array $draftIds ): ?array {

			if( in_array( $id, $draftIds, true ) === true || in_array( $id, Documents::sectionIds( $appData, $name ), true ) === true )
				return null;

			$keys = array_merge(
				array_map( static fn( array $entry ): string => (string) ( $entry['key'] ?? '' ), \Nino\Text::entries( $appData, true ) ),
				array_map( 'strval', array_keys( (array) ( $appData['/nino/html/images'] ?? [] ) ) )
			);

			$first = static function( string $candidate ) use ( $category, $keys ): ?string {
				$prefix = '/template/'. $category. '/'. $candidate. '/';
				foreach( $keys as $key )
					if( str_starts_with( $key, $prefix ) === true )
						return $key;
				return null;
			};

			$found = $first( $id );
			if( $found === null )
				return null;

			$free = 2;
			while( $first( $id. '-'. $free ) !== null && $free < 100 )
				$free++;

			return [
				'error' => 'the key '. $found. ' already belongs to this page template, and a section with the id "'. $id. '" would write it too - use another id, such as "'. $id. '-'. $free. '"',
				'params' => [ $id, $found, $id. '-'. $free ],
			];
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( Admin::guard( $appData, $request ) === false )
				return;

			$preview = Composer::preview( Admin::postData(), self::_text( $appData ) );
			if( $preview === null ) {
				\Nino\Http::fail( $request, 400, 'could not render section preview' );
				return;
			}

			\Nino\Http::ok( $request, [ 'html' => $preview ] );
		}

		/**
		 *	The workbench's own words for a fill key, which is what a preview
		 *	says in an empty field: the sample of a component's property is a
		 *	fill of this panel's text files, read in the language the
		 *	workbench is in (see \Nino\Admin\Admin::init())
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	callable
		 */
		private static function _text( array &$appData ): callable {
			return static function( string $key ) use ( &$appData ): string {
				return \Nino\Html::renderTextfill( $appData, $key );
			};
		}

		public static function presets(): array {

			static $cache = null;

			if( $cache !== null )
				return $cache;

			$found = [];

			foreach( glob( self::DIRECTORY. '/*/manifest.php' ) ?: [] as $path ) {

				$key = basename( dirname( $path ) );
				if( preg_match( self::KEY_PATTERN, $key ) !== 1 )
					continue;

				$manifest = include $path;
				if( is_array( $manifest ) === false )
					continue;
				if( (int) ( $manifest['version'] ?? 0 ) !== 3 )
					continue;

				try {
					$preset = AreaComposer::normalizePreset( $key, $manifest, dirname( $path ) );
				} catch( \Throwable ) {
					continue;
				}

				$found[$key] = [ 'weight' => isset( $manifest['weight'] ) === true ? (int) $manifest['weight'] : null, 'preset' => $preset ];
			}

			// Weighted first, ascending, the unweighted after them, and key
			// order between equals - so two presets that weigh the same still
			// come out the same way on every filesystem
			uksort( $found, static function( string $a, string $b ) use ( $found ): int {
				$weightA = $found[$a]['weight'];
				$weightB = $found[$b]['weight'];
				if( $weightA === null && $weightB === null )
					return strcmp( $a, $b );
				if( $weightA === null || $weightB === null )
					return $weightA === null ? 1 : -1;
				return ( $weightA <=> $weightB ) ?: strcmp( $a, $b );
			} );

			$cache = array_map( static fn( array $entry ): array => $entry['preset'], $found );

			return $cache;
		}

		public static function preset( string $key ): ?array {
			return self::presets()[$key] ?? null;
		}

		public static function publicPreset( array $preset ): array {
			unset( $preset['_layouts'] );
			return $preset;
		}

		/**
		 * Return the current project stylesheet for inert srcdoc previews.
		 * Run the regular asset shortcode first so a missing or stale cache is
		 * generated exactly as it is for the frontend. Embedding the result avoids
		 * a browser request to a dot-directory, which some webserver configurations
		 * answer with an HTML error page.
		 */
		private static function _previewCss( array &$appData ): string {

			if( \Nino\Html::getAssets( $appData, '/.cache/style.css' ) !== [] )
				\Nino\Modules\Assets::doShortcode( $appData, [ '/.cache/style.css' ] );

			$path = \Nino\Filesystem::path( $appData, '/.cache/style.css' );

			if( is_file( $path ) === false || is_readable( $path ) === false )
				return '';

			$bytes = filesize( $path );
			if( $bytes === false || $bytes > self::MAX_PREVIEW_CSS_BYTES )
				return '';

			$css = file_get_contents( $path );

			if( $css === false || strlen( $css ) > self::MAX_PREVIEW_CSS_BYTES )
				return '';

			return self::_inlinePreviewFonts( $appData, $css );
		}

		/**
		 * Inline project fonts so sandboxed srcdoc previews do not request them
		 * from their opaque `null` origin. A font rule that cannot be resolved
		 * locally is omitted from the preview instead of producing one CORS error
		 * per iframe; the frontend bundle itself remains untouched.
		 */
		private static function _inlinePreviewFonts( array &$appData, string $css ): string {

			$publicUrl = parse_url( \Nino\Filesystem::getPublicDir( $appData ) );
			$fontRoot = realpath( \Nino\Filesystem::path( $appData, '/fonts' ) );

			if( is_array( $publicUrl ) === false || $fontRoot === false )
				return preg_replace( '~@font-face\s*\{[^{}]*\}~i', '', $css ) ?? $css;

			$embeddedBytes = 0;
			$embeddedFonts = [];
			$result = preg_replace_callback( '~@font-face\s*\{[^{}]*\}~i', function( array $fontRule ) use ( &$appData, $publicUrl, $fontRoot, &$embeddedBytes, &$embeddedFonts ): string {
				$unresolved = false;
				$rule = preg_replace_callback( '~url\(\s*(?:(["\'])(.*?)\1|([^\)"\']+))\s*\)~i', function( array $urlMatch ) use ( &$appData, $publicUrl, $fontRoot, &$embeddedBytes, &$embeddedFonts, &$unresolved ): string {
					$source = trim( $urlMatch[2] !== '' ? $urlMatch[2] : ( $urlMatch[3] ?? '' ) );

					if( str_starts_with( strtolower( $source ), 'data:' ) === true )
						return $urlMatch[0];

					$dataUri = self::_previewFontDataUri( $appData, $source, $publicUrl, $fontRoot, $embeddedBytes, $embeddedFonts );
					if( $dataUri === null ) {
						$unresolved = true;
						return $urlMatch[0];
					}

					return 'url("'. $dataUri. '")';
				}, $fontRule[0] );

				return $unresolved === true || is_string( $rule ) === false ? '' : $rule;
			}, $css );

			return is_string( $result ) ? $result : $css;
		}

		/**
		 * Resolve one same-project public font URL to a bounded data URI.
		 */
		private static function _previewFontDataUri( array &$appData, string $source, array $publicUrl, string $fontRoot, int &$embeddedBytes, array &$embeddedFonts ): ?string {

			$url = parse_url( $source );
			if( is_array( $url ) === false )
				return null;

			foreach( [ 'scheme', 'host', 'port' ] as $originPart )
				if( isset( $url[$originPart] ) === true && ( isset( $publicUrl[$originPart] ) === false || strcasecmp( (string) $url[$originPart], (string) $publicUrl[$originPart] ) !== 0 ) )
					return null;

			$publicPath = rtrim( (string) ( $publicUrl['path'] ?? '' ), '/' );
			$fontPath = rawurldecode( (string) ( $url['path'] ?? '' ) );
			if(
				$publicPath === ''
				|| str_starts_with( $fontPath, $publicPath. '/fonts/' ) === false
				|| str_contains( $fontPath, "\0" ) === true
				|| str_contains( $fontPath, '\\' ) === true
				|| preg_match( '~(?:^|/)\.\.(?:/|$)~', $fontPath ) === 1
			)
				return null;

			$virtualPath = substr( $fontPath, strlen( $publicPath ) );
			$extension = strtolower( pathinfo( $virtualPath, PATHINFO_EXTENSION ) );
			$mimeType = self::PREVIEW_FONT_MIME_TYPES[$extension] ?? null;
			if( $mimeType === null )
				return null;

			$file = realpath( \Nino\Filesystem::path( $appData, $virtualPath ) );
			if( $file === false || str_starts_with( $file, $fontRoot. DIRECTORY_SEPARATOR ) === false || is_file( $file ) === false || is_readable( $file ) === false )
				return null;

			if( isset( $embeddedFonts[$file] ) === true )
				return $embeddedFonts[$file];

			$bytes = filesize( $file );
			if( $bytes === false || $bytes > self::MAX_PREVIEW_FONT_BYTES - $embeddedBytes )
				return null;

			$content = file_get_contents( $file );
			if( $content === false )
				return null;

			$embeddedBytes += strlen( $content );
			$embeddedFonts[$file] = 'data:'. $mimeType. ';base64,'. base64_encode( $content );

			return $embeddedFonts[$file];
		}

		public static function normalizeModel( mixed $model ): array {

			if( is_array( $model ) === false )
				return [];

			$result = [];
			foreach( $model as $field => $definition ) {
				if( preg_match( '/^[a-z][A-Za-z0-9]*$/', (string) $field ) !== 1 || is_array( $definition ) === false )
					throw new \InvalidArgumentException( 'area model has an invalid field name' );
				$type = (string) ( $definition['type'] ?? 'string' );
				if( in_array( $type, [ 'string', 'integer', 'double', 'boolean', 'image', 'element' ], true ) === false )
					throw new \InvalidArgumentException( 'area model field '. $field. ' has an unsupported type' );
				$result[(string) $field] = $definition;
				$result[(string) $field]['type'] = $type;
				$result[(string) $field]['label'] = trim( (string) ( $definition['label'] ?? '' ) )
					?: ucwords( preg_replace( '/(?<!^)[A-Z]/', ' $0', (string) $field ) );
			}

			return $result;
		}
	}

}
