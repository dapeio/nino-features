<?php
declare(strict_types=1);

namespace Nino\Modules\Themelab {

	use Nino\Modules\Builder\Custom;
	use Nino\Modules\Builder\Library;
	use Nino\Modules\Builder\Tree;

	// The API of the workbench for the blocks of the project, and the panel that draws its answers (assets/admin.js). Every action guards itself with the right
	// /_admin/themelab/manage, and so does the entry in the rail: an account without it has no panel. Only a block of the project is written or taken away
	//
	//	themelab/list			the blocks of a source - the project, or the theme in use - and what the dialog of a new block offers to begin with
	//	themelab/open			one block as a sheet of the editor: its tree between the frame of the site, its words and its slots, its stamp
	//	themelab/preview	a draft of the sheet as a token, the query that names it and the address of the page it is drawn on
	//	themelab/save			the sheet over the block of the project it was opened from, after the stamp it was opened with - with the details of the block and its variants, which the
	//							server builds block.json from itself and the kernel's \Nino\Blocks::validate() has the last word on
	//	themelab/saveas		the sheet as a new block of the project under the key its name makes
	//	themelab/create		a new block of the project: with a section, a row and a column, or the copy of any block of the library
	//	themelab/duplicate	a block of the list as a new block of the project, with a name of its own
	//	themelab/rename		the details of a block of the project - name and description in each language, category, rank - without the sheet; the key stays
	//	themelab/remove		a block of the project taken away
	//	themelab/export		own blocks as a package of the catalogue's form, checked by the rule of its packages, and handed over as a file to save
	//	themelab/import-check	a package that was chosen, read and checked, and what is in it - nothing is written; the bytes are held for this session
	//	themelab/import		the blocks of that package that were chosen, written as own blocks, each the way the editor saves one
	//	themelab/import-drop	the bytes that are held for a preview that was left: let go of
	//	themelab/offer		what the register offers at a place of the tree of the sheet
	//	themelab/notation	the notation of the block and of a component, as the template has them written
	//	themelab/keys			the fields and the types a loop of the sheet is told by - and nothing of the texts or the pictures of the site, which a block does not read
	//	themelab/frames		the frames of the site, header and footer
	//	themelab/frame-<x>	the actions of the editor, for a frame: the code of the Builder, after the right of this panel
	//	themelab/adopt		a block of a page as a block of the project, the page unchanged
	//	themelab/tile			a draft of a page with one block in it, for the small picture of the block in the list
	class Admin {

		// The size of a picture slot that is made without one: width and height of a picture of a page
		private const array PICTURE = [ 1200, 800 ];

		public static function perm(): string {
			return \Nino\Modules\Themelab::PERM;
		}

		public static function actions(): array {

			return [
				'themelab/list'			=> [ self::class, 'apiList' ],
				'themelab/open'			=> [ self::class, 'apiOpen' ],
				'themelab/preview'	=> [ self::class, 'apiPreview' ],
				'themelab/save'			=> [ self::class, 'apiSave' ],
				'themelab/saveas'		=> [ self::class, 'apiSaveAs' ],
				'themelab/create'		=> [ self::class, 'apiCreate' ],
				'themelab/duplicate'	=> [ self::class, 'apiDuplicate' ],
				'themelab/rename'		=> [ self::class, 'apiRename' ],
				'themelab/remove'		=> [ self::class, 'apiRemove' ],
				'themelab/offer'		=> [ self::class, 'apiOffer' ],
				'themelab/notation'	=> [ self::class, 'apiNotation' ],
				'themelab/keys'			=> [ self::class, 'apiKeys' ],
				'themelab/tile'			=> [ self::class, 'apiTile' ],
				'themelab/picture'	=> [ self::class, 'apiPicture' ],
				'themelab/picture-drop'	=> [ self::class, 'apiPictureDrop' ],
				'themelab/picture-show'	=> [ self::class, 'apiPictureShow' ],
				'themelab/frames'		=> [ self::class, 'apiFrames' ],
				'themelab/adopt'		=> [ self::class, 'apiAdopt' ],
				'themelab/export'		=> [ self::class, 'apiExport' ],
				'themelab/import-check'	=> [ self::class, 'apiImportCheck' ],
				'themelab/import'		=> [ self::class, 'apiImport' ],
				'themelab/import-drop'	=> [ self::class, 'apiImportDrop' ],
				'themelab/frame-open'	=> [ self::class, 'apiFrameOpen' ],
				'themelab/frame-preview'	=> [ self::class, 'apiFramePreview' ],
				'themelab/frame-save'	=> [ self::class, 'apiFrameSave' ],
				'themelab/frame-restore'	=> [ self::class, 'apiFrameRestore' ],
				'themelab/frame-blocks'	=> [ self::class, 'apiFrameBlocks' ],
				'themelab/frame-block'	=> [ self::class, 'apiFrameBlock' ],
				'themelab/frame-tile'	=> [ self::class, 'apiFrameTile' ],
				'themelab/frame-keys'	=> [ self::class, 'apiFrameKeys' ],
				'themelab/frame-variant'	=> [ self::class, 'apiFrameVariant' ],
				'themelab/frame-offer'	=> [ self::class, 'apiFrameOffer' ],
				'themelab/frame-notation'	=> [ self::class, 'apiFrameNotation' ],
				'themelab/frame-upload'	=> [ self::class, 'apiFrameUpload' ],
				'themelab/frame-unstage'	=> [ self::class, 'apiFrameUnstage' ],
			];
		}

		// The entry of the rail, directly behind the Builder's
		public static function nav(): array {
			return [ 'themelab', '/_admin/themelab/nav', 62 ];
		}

		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/panel' );
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		// Lucide blocks, geometry and no view
		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="14" y="3" rx="1"/><path d="M10 21V8a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H3"/></svg>';
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {

			$key = static fn( string $name ): string => \Nino\Modules\Builder\Store::shown( \Nino\Admin\Admin::postString( $data, $name ) );

			return match( $action ) {
				'themelab/save'			=> 'Save Block '. $key( 'key' ),
				'themelab/picture'	=> 'Upload Block Picture '. $key( 'key' ),
				'themelab/saveas'		=> 'Save Block As '. $key( 'name' ),
				'themelab/create'		=> 'Create Block '. $key( 'name' ),
				'themelab/duplicate'	=> 'Duplicate Block '. $key( 'key' ),
				'themelab/rename'		=> 'Rename Block '. $key( 'key' ),
				'themelab/remove'		=> 'Delete Block '. $key( 'key' ),
				'themelab/export'		=> 'Export Blocks '. $key( 'key' ),
				'themelab/import'		=> 'Import Blocks',
				'themelab/adopt'		=> 'Adopt Block '. $key( 'key' ),
				'themelab/frame-save'	=> 'Save Frame '. $key( 'template' ),
				'themelab/frame-restore'	=> 'Restore Last Frame State '. $key( 'template' ),
				'themelab/frame-upload'	=> 'Upload Frame Picture '. $key( 'uri' ),
				default						=> '',
			};
		}

		// --- the guard and the small things every action does ---------------------------------------------------------------

		private static function _gate( array &$appData, array &$request ): bool {

			$allowed = \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() );

			// The bytes of a package that was shown and left are let go of when their time is out, whatever the next request of the session is
			if( $allowed === true ) {

				Package::sweep();
				self::_sweepStaged( $appData );
			}

			return $allowed;
		}

		private static function _fail( array &$request, int $status, string $message, string $code, array $params = [], string $field = '' ): void {
			\Nino\Http::fail( $request, $status, $message, $code, $params, $field );
		}

		/**
		 *	The language a request is about: one of the languages the project has texts in. The session's working language is never read
		 *
		 *	@return 	string|null								null after the answer that says the request names none
		 */
		private static function _locale( array &$appData, array &$request ): ?string {

			$locale = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'locale' );

			if( $locale === '' || in_array( $locale, \Nino\Locales::getTextLocales( $appData ), true ) === false ) {
				self::_fail( $request, 400, 'the request names no language of the website', 'themelab_language', [], 'locale' );
				return null;
			}

			return $locale;
		}

		/**
		 *	The page a draft is drawn on, or the answer that says there is none
		 *
		 *	@return 	array|null
		 */
		private static function _shell( array &$appData, array &$request, string $locale ): ?array {

			$shell = Sheet::shell( $appData, $locale );

			if( $shell === null )
				self::_fail( $request, 409, 'the site has no page to draw a block on', 'themelab_shell' );

			return $shell;
		}

		/**
		 *	The key of a block of the project, as a request names it: a key of the form of a key - no path, no capital, no null byte - that the kernel lists with the origin
		 *	'project'. Anything else is refused with its answer
		 *
		 *	@return 	string|null								null after the answer
		 */
		private static function _own( array &$appData, array &$request, string $key ): ?string {

			if( strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 || ( \Nino\Blocks::all( $appData )[$key]['origin'] ?? '' ) !== 'project' ) {
				self::_fail( $request, 404, 'there is no such block of the project', 'themelab_unknown', [], 'key' );
				return null;
			}

			return $key;
		}

		/**
		 *	The block of the library a request names, for the sheet: a block of a page that the kernel lists, of the project or the theme in use - and, where the
		 *	request asks to copy, of any source
		 *
		 *	@return 	array|null								Library::block() of it; null after the answer
		 */
		private static function _block( array &$appData, array &$request, string $key, bool $any ): ?array {

			$listed = \Nino\Blocks::all( $appData )[$key] ?? null;
			$theme = Sheet::theme( $appData );

			if( strlen( $key ) > \Nino\Components::NAME_LENGTH || preg_match( \Nino\Components::NAME, $key ) !== 1 || $listed === null || ( $any === false && (string) $listed['origin'] !== 'project' && ( $theme === null || (string) $listed['origin'] !== $theme['key'] ) ) ) {
				self::_fail( $request, 404, 'there is no such block', 'themelab_unknown', [], 'key' );
				return null;
			}

			$block = Library::block( $appData, $key );

			if( $block === null )
				self::_fail( $request, 404, 'there is no such block', 'themelab_unknown', [], 'key' );

			return $block;
		}

		/**
		 *	The name a request gives a block, in each language of the site: the working language is required, a language that is left empty has the name of the working language
		 *	- a name typed in one language is not made up in another, it is given the one name there is
		 *
		 *	@param		mixed			$given				language => name, or one name for each language
		 *	@param		string		$work					The language the form was in
		 *
		 *	@return 	array|null								locale => name; null after the answer
		 */
		private static function _names( array &$appData, array &$request, mixed $given, string $field, string $work ): ?array {

			$locales = array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getTextLocales( $appData ) ) ) );
			$names = [];

			if( is_string( $given ) === true )
				$given = array_fill_keys( $locales, $given );

			$main = is_array( $given ) === true && is_string( $given[$work] ?? null ) === true ? trim( $given[$work] ) : '';

			foreach( $locales as $locale ) {

				$name = is_array( $given ) === true && is_string( $given[$locale] ?? null ) === true ? trim( $given[$locale] ) : '';
				$name = $name === '' ? $main : $name;

				if( $name === '' || mb_strlen( $name ) > \Nino\Blocks::MAX_NAME || preg_match( '/[<>\x00-\x1F\x7F]|\[\[/u', $name ) === 1 ) {
					self::_fail( $request, 400, 'the name is none', 'themelab_name', [ \Nino\Blocks::MAX_NAME ], $field );
					return null;
				}

				$names[$locale] = $name;
			}

			return $names;
		}

		/**
		 *	Whether another block of the library has the name - of any source: two blocks of one name are two tiles that a person cannot tell apart in the dialog "Insert block"
		 *
		 *	@param		array			$names				locale => name
		 *	@param		string		$except				The key of the block that is named - it may keep its name
		 *
		 *	@return 	bool											False after the answer
		 */
		private static function _distinct( array &$appData, array &$request, array $names, string $except, string $field ): bool {

			foreach( \Nino\Blocks::all( $appData ) as $key => $block ) {

				if( (string) $key === $except )
					continue;

				$has = is_array( $block['name'] ?? null ) === true ? $block['name'] : [ \Nino\Blocks::FALLBACK => (string) ( $block['name'] ?? '' ) ];

				foreach( $names as $locale => $name )
					if( is_string( $has[$locale] ?? null ) === true && mb_strtolower( trim( $has[$locale] ), 'UTF-8' ) === mb_strtolower( $name, 'UTF-8' ) ) {
						self::_fail( $request, 409, 'the name is taken', 'themelab_name_taken', [ $name ], $field );
						return false;
					}
			}

			return true;
		}

		/**
		 *	A text of each language as the languages of the block have it: what the map has for the language, else for English, else the first it has
		 *
		 *	@param		array			$map					language => text
		 *	@param		array			$locales
		 *
		 *	@return 	array
		 */
		private static function _filled( array $map, array $locales ): array {

			$out = [];

			foreach( $locales as $locale )
				$out[(string) $locale] = (string) ( $map[$locale] ?? $map[\Nino\Blocks::FALLBACK] ?? reset( $map ) );

			return $out;
		}

		private static function _category( array &$request, string $category ): ?string {

			if( in_array( $category, array_diff( \Nino\Blocks::CATEGORIES, [ 'frame' ] ), true ) === false ) {
				self::_fail( $request, 400, 'the category is none', 'themelab_category', [], 'category' );
				return null;
			}

			return $category;
		}

		/**
		 *	The failure of a step of Custom, as the panel answers it
		 */
		private static function _failed( array &$request, array $failure ): void {

			// The reason the kernel gives for a block it takes for none is told in the sentence: a person reads what to change
			self::_fail( $request, $failure[0], $failure[1], str_replace( 'builder_own_', 'themelab_', (string) $failure[2] ), $failure[3], (string) $failure[4] );
		}

		/**
		 *	The parts of a block as the sheet has them, held to the rule of the kernel: what \Nino\Blocks::validate() refuses is the answer, with its reason
		 *
		 *	@return 	array|null								The parts; null after the answer
		 */
		private static function _checked( array &$appData, array &$request, string $key, array $parts ): ?array {

			$reason = \Nino\Blocks::validate( $appData, $key, $parts );

			if( is_string( $reason ) === true ) {
				self::_fail( $request, 400, 'the block is not valid: '. $reason, 'themelab_invalid', [ $reason ], 'at' );
				return null;
			}

			return $parts;
		}

		/**
		 *	The tree of a draft - the sheet - and the block in it: canonical, in the form of a sheet, and held to the rule of a draft of the design against the block as it is
		 *	saved: nothing that a block may not hold and no source that is not the block's
		 *
		 *	@param		string		$key					The block the sheet was opened from; '' for a draft of a block that is not there yet
		 *
		 *	@return 	array|null								[ 'nodes', 'at', 'registry', 'template', 'shell' ]; null after the answer
		 */
		private static function _sheet( array &$appData, array &$request, string $key, string $locale ): ?array {

			$data = \Nino\Admin\Admin::postData();
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );
			$shell = self::_shell( $appData, $request, $locale );

			if( $shell === null )
				return null;

			$at = $canonical === null ? null : Sheet::rootAt( $canonical[1] );

			if( $canonical === null || $at === null ) {
				self::_fail( $request, 400, 'the tree is none of a block', 'themelab_tree', [], 'tree' );
				return null;
			}

			$registry = \Nino\Components::components( $appData );
			$block = $key === '' ? null : Library::block( $appData, $key );

			// A block that is not there yet is held to the rule against the empty block of its own id: a draft of a block that does not exist may add what a block may have, and no more
			$was = $block !== null ? $block['tree'] : Sheet::blank( (string) ( $canonical[1][$at]['a']['id'] ?? 'block' ) )['tree'];
			$old = Tree::read( $appData, Sheet::source( $appData, $was ) );

			if( Tree::designed( $appData, $old, $canonical[1], $registry, Tree::taken( $old, $registry, [] ), false ) !== null ) {
				self::_fail( $request, 403, 'the block may not be changed like that', 'themelab_refused', [], 'tree' );
				return null;
			}

			return [ 'nodes' => $canonical[1], 'at' => $at, 'registry' => $registry, 'template' => $shell['template'], 'shell' => $shell ];
		}

		// --- the actions -----------------------------------------------------------------------------------------------------

		public static function apiList( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$theme = Sheet::theme( $appData );
			$asked = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'tab' );
			$tab = in_array( $asked, [ 'frames', 'info' ], true ) === true ? $asked : ( $asked === 'theme' && $theme !== null ? 'theme' : 'project' );
			$blocks = [];

			foreach( $tab === 'frames' || $tab === 'info' ? [] : Sheet::listed( $appData, $tab === 'theme' ? $theme['key'] : 'project' ) as $key => $block )
				$blocks[] = [ 'key' => $key, 'name' => $block['name'], 'description' => $block['description'], 'category' => (string) $block['category'], 'group' => Library::group( $appData, (string) $key, $block ), 'variants' => count( (array) ( $block['alternatives'] ?? [] ) ), 'variantNames' => array_values( array_map( static fn( array $variant ): array => array_map( 'strval', (array) $variant['label'] ), (array) ( $block['alternatives'] ?? [] ) ) ), 'rank' => (int) $block['rank'] ];

			usort( $blocks, static fn( array $one, array $other ): int => $one['rank'] <=> $other['rank'] ?: strcmp( $one['key'], $other['key'] ) );

			$library = [];

			foreach( Sheet::listed( $appData, '' ) as $key => $block )
				$library[] = [ 'key' => $key, 'name' => $block['name'], 'origin' => is_array( $block['label'] ?? null ) === true ? $block['label'] : (string) ( $block['label'] ?? $block['origin'] ), 'own' => $block['origin'] === 'project' ];

			\Nino\Http::ok( $request, [
				'tab'				=> $tab,
				'theme'			=> $theme === null ? null : [ 'name' => $theme['name'], 'version' => $theme['version'], 'origin' => $theme['origin'] ],
				// What the project holds of its own, for the page of the theme: the blocks and the frames
				'counts'		=> [ 'blocks' => count( Sheet::listed( $appData, 'project' ) ), 'frames' => count( Frames::listed( $appData ) ) ],
				'blocks'		=> $blocks,
				'frames'		=> Frames::listed( $appData ),
				'library'		=> $library,
				'categories'	=> array_values( array_diff( \Nino\Blocks::CATEGORIES, [ 'frame' ] ) ),
				'contentLocale'	=> $locale,
				'native'		=> \Nino\Locales::getNativeLocale( $appData ),
				'locales'		=> \Nino\Admin\Admin::textLocales( $appData ),
				'dir'				=> (string) ( $appData['/nino/dir'] ?? '' ),
				'shell'			=> Sheet::shell( $appData, $locale ) !== null,
				// What the forms of a name begin with, in each language: a copy of "Hero" is "Hero Kopie" in German and "Hero copy" in English; a new block is "Neuer Block" and "New block"
				'copy'			=> Sheet::words( \Nino\Locales::getTextLocales( $appData ), 'copy/suffix' ),
				'fresh'			=> Sheet::words( \Nino\Locales::getTextLocales( $appData ), 'new/default-name' ),
				// What the dialogs of the export and the import say of themselves: the host the site is served on, the largest package, the words for a package that has no name yet
				'site'			=> self::_host( $request ),
				'package'		=> [ 'bytes' => \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS], 'version' => '1.0.0', 'name' => Sheet::words( \Nino\Locales::getTextLocales( $appData ), 'export/name-site' ) ],
			] );
		}

		/**
		 *	The host the request came to, as a name of a host: what the name of a package begins with where the project has no other
		 */
		private static function _host( array &$request ): string {

			$host = (string) preg_replace( '/:\d+$/', '', strtolower( (string) ( $request['/nino/http/request']['header']['Host'] ?? '' ) ) );

			return preg_match( '/^[a-z0-9][a-z0-9.-]{0,60}$/D', $host ) === 1 ? $host : 'nino';
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$key = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' );
			$block = self::_block( $appData, $request, $key, false );
			$shell = $block === null ? null : self::_shell( $appData, $request, $locale );

			if( $block === null || $shell === null )
				return;

			$own = ( \Nino\Blocks::all( $appData )[$key]['origin'] ?? '' ) === 'project';

			\Nino\Http::ok( $request, [ 'key' => $key, 'editable' => $own, 'stamp' => Sheet::stamp( $appData, $key ), 'name' => \Nino\Blocks::all( $appData )[$key]['name'], 'fresh' => new \stdClass() ] + Sheet::draft( $appData, $block, $shell, $locale ) );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = \Nino\Admin\Admin::postString( $data, 'key' );
			$sheet = self::_sheet( $appData, $request, $key, $locale );

			if( $sheet === null )
				return;

			// The words a block reads, as plain text in this language: nothing a person typed is markup or a fill of the site
			$template = $sheet['template'];
			$source = \Nino\Markup::write( $sheet['nodes'], \Nino\Components::containers( $appData ) );
			$sources = Tree::sources( $sheet['nodes'], $sheet['registry'], $template );
			$words = is_array( $data['texts'] ?? null ) === true ? $data['texts'] : [];
			$fills = [];
			$pictures = [];
			$chosen = is_array( $data['pictures'] ?? null ) === true ? $data['pictures'] : [];

			foreach( Tree::readKeys( $sheet['nodes'], $sheet['registry'], $template, $source ) as $read ) {

				$text = is_array( $words[$read] ?? null ) === true && is_string( $words[$read][$locale] ?? null ) === true ? $words[$read][$locale] : '';
				$text = trim( (string) preg_replace( "/[\\x00-\\x09\\x0B-\\x1F\\x7F]+/", ' ', str_replace( [ '[[', ']]' ], '', strip_tags( $text ) ) ) );

				if( $text !== '' )
					$fills[$read] = mb_substr( $text, 0, \Nino\Blocks::MAX_TEXT, 'UTF-8' );
			}

			foreach( $sources as $entry ) {

				if( $entry['kind'] !== 'image' || $entry['key'] === '' )
					continue;

				$slot = is_array( $chosen[$entry['key']] ?? null ) === true ? $chosen[$entry['key']] : [];
				$width = (int) ( $slot['width'] ?? 0 );
				$height = (int) ( $slot['height'] ?? 0 );
				$pictures[$entry['key']] = $width >= $height * 1.25 ? '/nino/placeholder/landscape' : '/nino/placeholder/mark';
			}

			// The sheet as it is written - the two calls of the frame and the block - with the placeholder of the kernel for each picture that has no file
			$draft = [ 'template' => $template, 'source' => \Nino\Markup::write(																																																																								Sheet::hinted( $appData, Tree::shown( $sheet['nodes'], $sources, $pictures ), \Nino\Admin\Admin::postString( $data, 'hint' ) ), \Nino\Components::containers( $appData ) ), 'fills' => $fills, 'slots' => new \stdClass(), 'locale' => $locale ];
			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Themelab::KIND, self::perm(), $draft, \Nino\Admin\Admin::postString( $data, 'token' ) );

			if( $token === '' ) {
				self::_fail( $request, 500, 'the preview could not be written', 'themelab_preview' );
				return;
			}

			\Nino\Http::ok( $request, [
				'token'			=> $token,
				'query'			=> \Nino\Preview::QUERY. '='. $token,
				'ttl'				=> \Nino\Preview::TTL,
				'sources'		=> $sources,
				'address'		=> $sheet['shell']['address'],
				'findings'	=> Tree::findings( $appData, $sheet['nodes'] ),
				'notation'	=> \Nino\Modules\Builder\Admin::notationOf( $appData, $sheet['nodes'], $data['look'] ?? null ),
				'variants'	=> [],
			] );
		}

		/**
		 *	The details of a block as a request brings them over what the block has: the names and the descriptions in each language, the category, the rank and the places of the
		 *	pictures. What the request does not bring stays as it is; what it brings is held to the rule of each - nothing is taken over as it comes
		 *
		 *	@param		array			$listed				The block as \Nino\Blocks::all() lists it
		 *
		 *	@return 	array|null								'names', 'descriptions', 'category', 'rank', 'images' (name => [ placeholder, width, height, label ] of those the request names); null after the answer
		 */
		private static function _details( array &$appData, array &$request, array $data, array $listed, string $locale ): ?array {

			$given = is_array( $data['details'] ?? null ) === true ? $data['details'] : [];
			$out = [ 'names' => array_map( 'strval', (array) $listed['name'] ), 'descriptions' => array_map( 'strval', (array) $listed['description'] ), 'category' => (string) $listed['category'], 'rank' => (int) $listed['rank'], 'images' => [] ];

			if( array_key_exists( 'names', $given ) === true ) {

				$out['names'] = self::_names( $appData, $request, $given['names'], 'name', $locale );

				if( $out['names'] === null )
					return null;
			}

			if( array_key_exists( 'descriptions', $given ) === true ) {

				$locales = array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getTextLocales( $appData ) ) ) );
				$words = is_array( $given['descriptions'] ) === true ? $given['descriptions'] : [];
				$text = [];

				foreach( $locales as $one ) {

					// A language the request says nothing of keeps the description it has; one it empties has the name where the kernel asks for a text, and nothing is made up of another language
					$line = is_string( $words[$one] ?? null ) === true ? trim( $words[$one] ) : trim( (string) ( ( (array) $listed['description'] )[$one] ?? '' ) );
					$line = $line === '' ? (string) ( $out['names'][$one] ?? reset( $out['names'] ) ) : $line;

					if( mb_strlen( $line ) > \Nino\Blocks::MAX_DESCRIPTION || preg_match( '/[<>\x00-\x1F\x7F]|\[\[/u', $line ) === 1 ) {
						self::_fail( $request, 400, 'the description is none', 'themelab_description', [ \Nino\Blocks::MAX_DESCRIPTION ], 'description' );
						return null;
					}

					$text[$one] = $line;
				}

				$out['descriptions'] = $text;
			}

			if( array_key_exists( 'category', $given ) === true ) {

				$out['category'] = self::_category( $request, is_string( $given['category'] ) === true ? $given['category'] : '' );

				if( $out['category'] === null )
					return null;
			}

			if( array_key_exists( 'rank', $given ) === true ) {

				if( ( is_int( $given['rank'] ) === false && ( is_string( $given['rank'] ) === false || preg_match( '/^\d{1,3}$/D', $given['rank'] ) !== 1 ) ) || (int) $given['rank'] < 0 || (int) $given['rank'] > 999 ) {
					self::_fail( $request, 400, 'the rank is a number from 0 to 999', 'themelab_rank', [], 'rank' );
					return null;
				}

				$out['rank'] = (int) $given['rank'];
			}

			foreach( is_array( $given['images'] ?? null ) === true ? $given['images'] : [] as $name => $image ) {

				$label = [];
				$words = is_array( $image ) === true && is_array( $image['label'] ?? null ) === true ? $image['label'] : [];
				$main = is_string( $words[$locale] ?? null ) === true ? trim( $words[$locale] ) : '';

				foreach( array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getTextLocales( $appData ) ) ) ) as $one ) {

					$text = is_string( $words[$one] ?? null ) === true && trim( $words[$one] ) !== '' ? trim( $words[$one] ) : $main;

					if( $text === '' || mb_strlen( $text ) > \Nino\Blocks::MAX_NAME || preg_match( '/[<>\x00-\x1F\x7F]|\[\[/u', $text ) === 1 ) {
						self::_fail( $request, 400, 'the label of a picture is none', 'themelab_picture_label', [ (string) $name ], 'images' );
						return null;
					}

					$label[$one] = $text;
				}

				$width = is_array( $image ) === true ? ( $image['width'] ?? null ) : null;
				$height = is_array( $image ) === true ? ( $image['height'] ?? null ) : null;
				$placeholder = is_array( $image ) === true && is_string( $image['placeholder'] ?? null ) === true ? $image['placeholder'] : '';

				if( is_string( $name ) === false || preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $name ) !== 1 || is_int( $width ) === false || is_int( $height ) === false || $width < 16 || $width > \Nino\Blocks::MAX_SIDE || $height < 16 || $height > \Nino\Blocks::MAX_SIDE || isset( \Nino\Images::PLACEHOLDERS[$placeholder] ) === false ) {
					self::_fail( $request, 400, 'a place of a picture is none', 'themelab_picture_place', [ (string) $name ], 'images' );
					return null;
				}

				$out['images'][$name] = [ 'placeholder' => $placeholder, 'width' => $width, 'height' => $height, 'label' => $label ];
			}

			return $out;
		}

		/**
		 *	What the details and the variants of a request do to the block.json that the sheet made: the rank, the places of the pictures that the tree reads, and the variants - made
		 *	from the draft, or, where the request brings none, the ones the block has
		 *
		 *	@param		array			&$meta				block.json as the parts have it
		 *	@param		array			$details			What _details() answered
		 *	@param		array			$was					block.json as it is saved
		 *	@param		array			$sheet
		 *
		 *	@return 	bool											false after the answer
		 */
		private static function _applied( array &$appData, array &$request, array &$meta, array $details, array $was, array $sheet, array $data, string $locale ): bool {

			$meta['rank'] = $details['rank'];

			foreach( $meta['images'] ?? [] as $name => $image )
				if( isset( $details['images'][$name] ) === true )
					$meta['images'][$name] = [ 'placeholder' => $details['images'][$name]['placeholder'], 'width' => $details['images'][$name]['width'], 'height' => $details['images'][$name]['height'], 'label' => $details['images'][$name]['label'] ];

			if( array_key_exists( 'variants', $data ) === false ) {

				if( isset( $was['variants'] ) === true )
					$meta['variants'] = $was['variants'];

				return true;
			}

			$built = Variants::built( $appData, $sheet['nodes'][$sheet['at']], $data['variants'], array_keys( (array) ( $meta['name'] ?? [] ) ), $locale );

			if( isset( $built['failure'] ) === true ) {
				self::_fail( $request, $built['failure'][0], $built['failure'][1], $built['failure'][2], $built['failure'][3], $built['failure'][4] );
				return false;
			}

			if( $built['variants'] === null )
				unset( $meta['variants'] );
			else
				$meta['variants'] = $built['variants'];

			return true;
		}

		/**
		 *	The parts of the block a sheet is, from the tree and the words the request brings: Custom::parts() against the words and the slots the block has. The names and the
		 *	descriptions are the block's in each language - the ones given, never one language made into another -; the rank and the variants it has stay
		 *
		 *	@param		array			$names				locale => name
		 *	@param		array			$descriptions	locale => description
		 *
		 *	@return 	array|null								parts() answer - 'key', 'parts'; null after the answer
		 */
		private static function _parts( array &$appData, array &$request, array $sheet, array $block, string $key, array $names, array $descriptions, string $category ): ?array {

			$data = \Nino\Admin\Admin::postData();

			// A place has one call: two calls that read the same place of the block would show one set of words, and a name given to one of them would take the words from the other
			$reads = [];

			foreach( Tree::sources( $sheet['nodes'], $sheet['registry'], $sheet['template'] ) as $entry )
				if( is_string( $entry['key'] ?? null ) === true && str_starts_with( $entry['key'], '/template/'. $sheet['template']. '/' ) === true )
					$reads[$entry['key']] = ( $reads[$entry['key']] ?? 0 ) + 1;

			foreach( $reads as $place => $number )
				if( $number > 1 ) {
					self::_fail( $request, 400, 'two components read one place of the block', 'themelab_place_twice', [ substr( $place, (int) strrpos( $place, '/' ) + 1 ) ], 'tree' );
					return null;
				}

			$state = Sheet::state( $block, $sheet['template'], \Nino\Admin\Admin::postString( $data, 'locale' ) );
			$pictures = self::_pictured( $appData, $sheet, $state, is_array( $data['pictures'] ?? null ) === true ? $data['pictures'] : [] );
			$input = [
				'at'					=> (string) $sheet['at'],
				'key'					=> $key,
				'name'				=> (string) reset( $names ),
				'description'	=> (string) reset( $descriptions ),
				'category'		=> $category,
				'texts'				=> is_array( $data['texts'] ?? null ) === true ? $data['texts'] : [],
				'pictures'		=> $pictures,
				'lenient'			=> true,
			];

			return self::_described( $request, Custom::parts( $appData, $sheet['template'], [ 'source' => '', 'texts' => $state['texts'], 'slots' => $state['slots'] ], $sheet['nodes'], $input ), $names, $descriptions );
		}

		/**
		 *	The places of the pictures a sheet has, each with a size: one the person has not given a size - a picture that was just put in - is as large as a picture of a page is
		 *	made, so that the block can be saved and the size is changed where the picture is
		 *
		 *	@param		array			$chosen				uri => [ label, width, height ] as the request brings them
		 *
		 *	@return 	array											The same, and an entry for each place that had none
		 */
		private static function _pictured( array &$appData, array $sheet, array $state, array $chosen ): array {

			foreach( Tree::sources( $sheet['nodes'], $sheet['registry'], $sheet['template'] ) as $entry ) {

				if( $entry['kind'] !== 'image' || $entry['key'] === '' || isset( $state['slots'][$entry['key']] ) === true )
					continue;

				$slot = is_array( $chosen[$entry['key']] ?? null ) === true ? $chosen[$entry['key']] : [];

				if( is_int( $slot['width'] ?? null ) === false || is_int( $slot['height'] ?? null ) === false || $slot['width'] < 16 || $slot['height'] < 16 )
					$chosen[$entry['key']] = [ 'label' => is_string( $slot['label'] ?? null ) === true && trim( $slot['label'] ) !== '' ? $slot['label'] : substr( $entry['key'], (int) strrpos( $entry['key'], '/' ) + 1 ), 'width' => self::PICTURE[0], 'height' => self::PICTURE[1] ];
			}

			return $chosen;
		}

		/**
		 *	The answer of Custom::parts() with the names and the descriptions of the block in each language it has texts in
		 *
		 *	@return 	array|null								The answer; null after the failure was said
		 */
		private static function _described( array &$request, array $made, array $names, array $descriptions ): ?array {

			if( isset( $made['failure'] ) === true ) {
				self::_failed( $request, $made['failure'] );
				return null;
			}

			$locales = array_keys( $made['parts']['texts'] );
			$meta = json_decode( (string) $made['parts']['json'], true );
			$meta['name'] = self::_filled( $names, $locales );
			$meta['description'] = self::_filled( $descriptions, $locales );
			$made['parts']['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";

			return $made;
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( $data, 'key' ) );

			if( $key === null )
				return;

			// Another session changed the block since it was opened: it is not written over unless force says so
			if( ( $data['force'] ?? false ) !== true && ( is_string( $data['base'] ?? null ) === false || hash_equals( Sheet::stamp( $appData, $key ), $data['base'] ) === false ) ) {
				self::_fail( $request, 409, 'the block was changed since it was opened', 'themelab_changed', [], 'key' );
				return;
			}

			$sheet = self::_sheet( $appData, $request, $key, $locale );
			$block = Library::block( $appData, $key );

			if( $sheet === null || $block === null )
				return;

			$listed = \Nino\Blocks::all( $appData )[$key];
			$details = self::_details( $appData, $request, $data, $listed, $locale );

			if( $details === null || self::_distinct( $appData, $request, $details['names'], $key, 'name' ) === false )
				return;

			$made = self::_parts( $appData, $request, $sheet, $block, $key, $details['names'], $details['descriptions'], $details['category'] );

			if( $made === null )
				return;

			// What the block names and the sheet does not touch stays: the variants - which the rule of the kernel holds to the new template - unless the draft brings them
			$old = Sheet::partsOf( $appData, $key );
			$meta = json_decode( (string) $made['parts']['json'], true );
			$was = is_array( $old ) === true ? json_decode( $old['json'], true ) : [];

			if( self::_applied( $appData, $request, $meta, $details, $was, $sheet, $data, $locale ) === false )
				return;

			$made['parts']['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";

			// A block that is saved as it is stays as it is, to the byte: the file that a hand or an older version wrote is not made over for the order of its languages or the way
			// it writes an empty set
			$raw = (string) @file_get_contents( (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). \Nino\Blocks::OWN. '/'. $key. '/block.json' );

			if( $raw !== '' && json_decode( $raw, true ) !== null && json_decode( $made['parts']['json'], true ) == json_decode( $raw, true ) )
				$made['parts']['json'] = $raw;

			// The pictures the block has of its own stay, for the slots that are still there: a save does not take them away
			$made['parts']['pictures'] = array_filter( is_array( $old ) === true ? (array) $old['pictures'] : [], static fn( string $file ): bool => isset( $meta['images'][(string) preg_replace( '/\.[a-z]+$/D', '', $file )] ), ARRAY_FILTER_USE_KEY );

			// The picture a person chose for a place, or took away: a staged file becomes the place's own picture - one at a time, whatever it was - and "none" takes it away
			$chosen = is_array( $data['own'] ?? null ) === true ? $data['own'] : [];

			foreach( $chosen as $place => $token ) {

				$place = (string) $place;

				// A name is the form the places have - never a path - and a token is the form apiPicture() gives
				if( preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $place ) !== 1 || strlen( $place ) > \Nino\Components::NAME_LENGTH ) {
					self::_fail( $request, 400, 'the name of the place is none', 'themelab_picture_name', [], 'own' );
					return;
				}

				if( isset( $meta['images'][$place] ) === false || ( $token !== null && $token !== '' && ( is_string( $token ) === false || preg_match( '/^[a-f0-9]{32}\.(?:svg|webp|png|jpg)$/D', $token ) !== 1 ) ) ) {
					self::_fail( $request, 400, 'the picture of the place is none', 'themelab_picture_own', [ $place ], 'own' );
					return;
				}

				// A file that was put aside is the session's that did it and the block's it was for
				if( is_string( $token ) === true && $token !== '' && ( $_SESSION[self::STAGED][$token]['key'] ?? null ) !== $key ) {
					self::_fail( $request, 403, 'the picture was put aside for another', 'themelab_picture_own', [ $place ], 'own' );
					return;
				}

				foreach( [ 'svg', 'webp', 'png', 'jpg' ] as $kind )
					unset( $made['parts']['pictures'][$place. '.'. $kind] );

				if( is_string( $token ) === true && $token !== '' ) {

					$bytes = @file_get_contents( self::_staged( $appData ). $token );

					if( is_string( $bytes ) === false ) {
						self::_fail( $request, 400, 'the picture of the place is gone', 'themelab_picture_own', [ $place ], 'own' );
						return;
					}

					$made['parts']['pictures'][$place. '.'. substr( $token, 33 )] = $bytes;
				}
			}

			if( self::_checked( $appData, $request, $key, $made['parts'] ) === null )
				return;

			$written = Custom::replace( $appData, $key, $made['parts'] );

			if( isset( $written['failure'] ) === true ) {
				self::_failed( $request, $written['failure'] );
				return;
			}

			foreach( $chosen as $token )
				if( is_string( $token ) === true && preg_match( '/^[a-f0-9]{32}\.(?:svg|webp|png|jpg)$/D', $token ) === 1 )
					self::_letGo( $appData, $token );

			self::_answer( $appData, $request, $key, $locale, true );
		}

		// Where the session keeps what it put aside: token => [ the block it was for, until when ]
		private const string STAGED = 'nino_themelab_pictures';

		/**
		 *	The folder the pictures are in that a person chose for a place and did not save: files of an hour or less, made by apiPicture()
		 */
		private static function _staged( array &$appData ): string {
			return (string) ( $appData['./nino/filesystem/contentpath'] ?? '' ). '/data/.preview/themelab-picture/';
		}

		/**
		 *	A file that was put aside is let go of: the file and the session's note of it
		 */
		private static function _letGo( array &$appData, string $token ): void {

			unset( $_SESSION[self::STAGED][$token] );

			if( preg_match( '/^[a-f0-9]{32}\.(?:svg|webp|png|jpg)$/D', $token ) === 1 )
				@unlink( self::_staged( $appData ). $token );
		}

		/**
		 *	What was put aside and is old goes, whichever action of the panel is asked: the notes of the session that ran out, and the files of any session that nobody asked for in an hour
		 */
		private static function _sweepStaged( array &$appData ): void {

			foreach( (array) ( $_SESSION[self::STAGED] ?? [] ) as $token => $note )
				if( is_array( $note ) === false || (int) ( $note['until'] ?? 0 ) < time() )
					self::_letGo( $appData, (string) $token );

			foreach( (array) glob( self::_staged( $appData ). '*' ) as $old )
				if( is_file( $old ) === true && filemtime( $old ) < time() - 3600 )
					@unlink( $old );
		}

		/**
		 *	A picture for the place of a block: the file is looked at as the kernel looks at a picture, and a raster picture is written anew by the kernel - nothing that was
		 *	appended to it, no foreign data of it is kept - a svg is cleaned, and any other kind is refused here and not when the block is saved. It is kept apart, for this session and
		 *	this block, until the block is saved. Nothing of the block is written; the answer is the name the save asks for
		 */
		public static function apiPicture( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( $data, 'key' ) );

			if( $key === null )
				return;

			$failure = \Nino\Admin\Admin::uploadError( $_FILES['file'] ?? null );

			if( $failure !== null ) {
				\Nino\Http::fail( $request, $failure['status'], $failure['error'], $failure['code'], $failure['params'] );
				return;
			}

			$bytes = is_string( $_FILES['file']['tmp_name'] ?? null ) === true ? file_get_contents( $_FILES['file']['tmp_name'] ) : false;

			if( $bytes === false ) {
				\Nino\Http::fail( $request, 500, 'could not read upload', 'upload_server' );
				return;
			}

			if( $bytes === '' ) {
				\Nino\Http::fail( $request, 400, 'the file is empty', 'themelab_picture_empty' );
				return;
			}

			if( str_starts_with( ltrim( $bytes ), '<' ) === true ) {

				$bytes = \Nino\Images::cleanSvg( $bytes );

				if( $bytes === null ) {
					\Nino\Http::fail( $request, 400, 'the image was refused: svg', 'image_unreadable' );
					return;
				}

				$kind = 'svg';
			}
			else {

				$rejection = \Nino\Images::reject( $bytes );

				if( $rejection !== null ) {
					\Nino\Http::fail( $request, 400, 'the image was refused: '. $rejection['code'], $rejection['code'], $rejection['params'] );
					return;
				}

				$size = \Nino\Images::size( $bytes );

				// A block carries a png, a jpeg, a webp or a svg: another kind - a gif - is told so at once
				if( $size === false || in_array( $size['type'], [ IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP ], true ) === false ) {
					\Nino\Http::fail( $request, 400, 'a block takes no picture of that kind', 'themelab_picture_type' );
					return;
				}

				// Written anew by the kernel, as every picture it is given is: the whole picture, upright, never larger than it was
				$made = \Nino\Images::fit( $appData, $bytes, \Nino\Modules\Builder\Pictures::MAX_SIDE, \Nino\Modules\Builder\Pictures::MAX_SIDE, 'themelab-picture/'. bin2hex( random_bytes( 8 ) ) );
				$again = $made === false ? false : \Nino\Images::read( $appData, $made );

				if( $made !== false )
					\Nino\Images::delete( $appData, $made );

				// fit() makes the folder it writes into and the file only passes through it: the folder is not left behind empty (rmdir takes nothing that has a file in it)
				@rmdir( \Nino\Filesystem::path( $appData, '/images/themelab-picture' ) );

				if( $again === false || $again === '' ) {
					\Nino\Http::fail( $request, 400, 'the image could not be processed', 'image_unreadable' );
					return;
				}

				$bytes = $again;
				$kind = str_starts_with( $bytes, "\x89PNG" ) === true ? 'png' : ( str_starts_with( $bytes, 'RIFF' ) === true ? 'webp' : 'jpg' );
			}

			$dir = self::_staged( $appData );
			$token = bin2hex( random_bytes( 16 ) ). '.'. $kind;

			if( ( is_dir( $dir ) === false && @mkdir( $dir, 0755, true ) === false && is_dir( $dir ) === false ) || file_put_contents( $dir. $token, $bytes ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not keep the picture', 'themelab_write' );
				return;
			}

			$_SESSION[self::STAGED][$token] = [ 'key' => $key, 'until' => time() + 3600 ];

			\Nino\Http::ok( $request, [ 'file' => $token, 'type' => $kind ] );
		}

		/**
		 *	The picture that was put aside, for the preview of the editor: the bytes of the file as an address of data, to the session that put it aside and for the block it was for - no
		 *	address of the site is made for it, and nothing is written
		 */
		public static function apiPictureShow( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( $data, 'key' ) );

			if( $key === null )
				return;

			$token = \Nino\Admin\Admin::postString( $data, 'file' );

			if( preg_match( '/^[a-f0-9]{32}\.(?:svg|webp|png|jpg)$/D', $token ) !== 1 ) {
				self::_fail( $request, 400, 'the picture of the place is none', 'themelab_picture_own', [ '' ], 'own' );
				return;
			}

			if( ( $_SESSION[self::STAGED][$token]['key'] ?? null ) !== $key ) {
				self::_fail( $request, 403, 'the picture was put aside for another', 'themelab_picture_own', [ '' ], 'own' );
				return;
			}

			$bytes = @file_get_contents( self::_staged( $appData ). $token );

			if( is_string( $bytes ) === false ) {
				self::_fail( $request, 400, 'the picture of the place is gone', 'themelab_picture_own', [ '' ], 'own' );
				return;
			}

			$mime = [ 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg' ][substr( $token, 33 )];

			\Nino\Http::ok( $request, [ 'url' => 'data:'. $mime. ';base64,'. base64_encode( $bytes ) ] );
		}

		/**
		 *	Pictures that were put aside and are not wanted - the draft was given up, the editor was left, another picture came for the place: let go of, those of this session and this block
		 */
		public static function apiPictureDrop( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( $data, 'key' ) );

			if( $key === null )
				return;

			$gone = 0;

			foreach( is_array( $data['files'] ?? null ) === true ? array_slice( $data['files'], 0, 64 ) : [] as $token )
				if( is_string( $token ) === true && ( $_SESSION[self::STAGED][$token]['key'] ?? null ) === $key ) {
					self::_letGo( $appData, $token );
					$gone++;
				}

			\Nino\Http::ok( $request, [ 'dropped' => $gone ] );
		}

		/**
		 *	The answer of an action that wrote a block: the sheet as it is saved now - the editor takes it as the answer of a save - and its key and stamp
		 */
		private static function _answer( array &$appData, array &$request, string $key, string $locale, bool $changed ): void {

			unset( $appData['./nino/blocks/state'] );

			$block = Library::block( $appData, $key );
			$shell = Sheet::shell( $appData, $locale );

			if( $block === null || $shell === null ) {
				self::_fail( $request, 500, 'the block could not be read', 'themelab_write' );
				return;
			}

			\Nino\Http::ok( $request, [ 'saved' => true, 'changed' => $changed, 'key' => $key, 'stamp' => Sheet::stamp( $appData, $key ), 'name' => \Nino\Blocks::all( $appData )[$key]['name'] ] + Sheet::draft( $appData, $block, $shell, $locale ) );
		}

		public static function apiSaveAs( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$from = \Nino\Admin\Admin::postString( $data, 'key' );
			$origin = self::_block( $appData, $request, $from, false );
			$names = $origin === null ? null : self::_names( $appData, $request, $data['name'] ?? null, 'name', $locale );

			if( $origin === null || $names === null || self::_distinct( $appData, $request, $names, '', 'name' ) === false )
				return;

			$key = Sheet::keyOf( (string) ( $names[$locale] ?? reset( $names ) ) );

			if( Sheet::taken( $appData, $key ) === true ) {
				self::_fail( $request, 409, 'the key is taken', 'themelab_taken', [ $key ], 'name' );
				return;
			}

			$sheet = self::_sheet( $appData, $request, $from, $locale );

			if( $sheet === null )
				return;

			$listed = \Nino\Blocks::all( $appData )[$from];
			$details = self::_details( $appData, $request, $data, $listed, $locale );

			if( $details === null )
				return;

			$made = self::_parts( $appData, $request, $sheet, $origin, $key, $names, $details['descriptions'], $details['category'] );

			if( $made === null )
				return;

			// The draft brings its variants: they are the new block's. Without them the block it is made from gives its own where the new template still holds them
			if( array_key_exists( 'variants', $data ) === true ) {

				$meta = json_decode( (string) $made['parts']['json'], true );

				if( self::_applied( $appData, $request, $meta, $details, [], $sheet, $data, $locale ) === false )
					return;

				$made['parts']['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";
			}
			else
				$made['parts'] = self::_varied( $appData, $made['parts'], $key, $from );

			// A block that is saved under a key is its own block, and is told from the one it was made of by the id of its root: the id follows the key
			$made['parts'] = Sheet::reidParts( $appData, $made['parts'], $key );

			if( self::_checked( $appData, $request, $key, $made['parts'] ) === null )
				return;

			$written = Custom::write( $appData, $key, $made['parts'] );

			if( isset( $written['failure'] ) === true ) {
				self::_failed( $request, $written['failure'] );
				return;
			}

			self::_answer( $appData, $request, $key, $locale, true );
		}

		public static function apiCreate( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$names = self::_names( $appData, $request, $data['name'] ?? null, 'name', $locale );
			$category = $names === null ? null : self::_category( $request, \Nino\Admin\Admin::postString( $data, 'category' ) );

			if( $names === null || $category === null || self::_distinct( $appData, $request, $names, '', 'name' ) === false )
				return;

			$key = Sheet::keyOf( (string) ( $names[$locale] ?? reset( $names ) ) );

			if( Sheet::taken( $appData, $key ) === true ) {
				self::_fail( $request, 409, 'the key is taken', 'themelab_taken', [ $key ], 'name' );
				return;
			}

			$from = \Nino\Admin\Admin::postString( $data, 'from' );
			$block = $from === '' ? Sheet::blank( $key ) : self::_block( $appData, $request, $from, true );

			if( $block === null )
				return;

			self::_make( $appData, $request, $block, $key, $names, $category, $locale, $from === '' ? $names : array_map( 'strval', (array) \Nino\Blocks::all( $appData )[$from]['description'] ), $from );
		}

		/**
		 *	The variants of the block a block is made from go along where the new template still holds them: the rule of the kernel decides, and a block that it would refuse
		 *	with them is made without
		 *
		 *	@return 	array											The parts
		 */
		private static function _varied( array &$appData, array $parts, string $key, string $from ): array {

			$was = $from === '' ? null : Sheet::partsOf( $appData, $from );
			$old = $was === null ? [] : (array) json_decode( $was['json'], true );

			if( isset( $old['variants'] ) === false )
				return $parts;

			$meta = (array) json_decode( (string) $parts['json'], true );
			$meta['variants'] = $old['variants'];
			$tried = $parts;
			$tried['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";

			return is_string( \Nino\Blocks::validate( $appData, $key, $tried ) ) === true ? $parts : $tried;
		}

		/**
		 *	Own blocks as a package, to save: the package is made and held to the rule of the catalogue's packages before it leaves, and an answer that says it is made (`check`) is
		 *	what the panel asks first, so that a refusal is said in the dialog and the download that follows is the file. The name of the file is the key and the version - both
		 *	checked, never the request's text - and nothing else of the request is in a header
		 */
		public static function apiExport( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$made = Package::export( $appData, is_array( $data['keys'] ?? null ) === true ? array_values( $data['keys'] ) : [], [
				'key'					=> \Nino\Admin\Admin::postString( $data, 'key' ),
				'version'			=> \Nino\Admin\Admin::postString( $data, 'version' ),
				'name'				=> \Nino\Admin\Admin::postString( $data, 'name' ),
				'description'	=> \Nino\Admin\Admin::postString( $data, 'description' ),
			], $locale );

			if( isset( $made['failure'] ) === true ) {
				self::_failed( $request, $made['failure'] );
				return;
			}

			if( ( $data['check'] ?? false ) === true ) {
				\Nino\Http::ok( $request, [ 'file' => $made['file'], 'size' => strlen( $made['bytes'] ), 'blocks' => count( $made['blocks'] ) ] );
				return;
			}

			\Nino\Http::download( $request, $made['bytes'], $made['file'], 'application/gzip' );
		}

		/**
		 *	A package that was chosen: read in memory, held to the rule of the catalogue's packages, and shown. Nothing is written and nothing of the file is kept but its bytes, which are held
		 *	in the session of the one who sent them, for a quarter of an hour, under a key that is made here
		 */
		public static function apiImportCheck( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$failure = \Nino\Admin\Admin::uploadError( $_FILES['file'] ?? null );

			if( $failure !== null ) {
				self::_fail( $request, $failure['status'], $failure['error'], $failure['code'], $failure['params'], 'file' );
				return;
			}

			$tmp = $_FILES['file']['tmp_name'] ?? null;
			$size = is_string( $tmp ) === true ? (int) @filesize( $tmp ) : 0;

			if( is_string( $tmp ) === true && $size < 1 ) {
				self::_failed( $request, [ 400, 'the file is empty', 'themelab_import_nofile', [], 'file' ] );
				return;
			}

			if( is_string( $tmp ) === false || $size > \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] ) {
				self::_failed( $request, [ 400, 'the file is none a package comes in', 'themelab_import_large', [ \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::BLOCKS] ], 'file' ] );
				return;
			}

			// Read from where php put it, and never written: nothing of the file is left behind when the request ends, and no name of the request is a path
			$bytes = (string) @file_get_contents( $tmp );
			$read = Package::read( $appData, $bytes, $locale );

			if( isset( $read['failure'] ) === true ) {
				self::_failed( $request, $read['failure'] );
				return;
			}

			$pack = $read['pack'];
			$name = is_array( $pack['name'] ?? null ) === true ? (string) ( $pack['name'][$locale] ?? reset( $pack['name'] ) ) : (string) ( $pack['name'] ?? '' );

			\Nino\Http::ok( $request, [
				'id'			=> Package::hold( $bytes ),
				'seconds'	=> Package::HOLD,
				'pack'		=> [ 'key' => (string) $pack['key'], 'version' => (string) $pack['version'], 'name' => $name ],
				'blocks'	=> Package::listed( $appData, $read, $locale ),
			] );
		}

		/**
		 *	A preview that was left - Cancel, Escape - lets go of the bytes that are held for it, when the key is the one that is held
		 */
		public static function apiImportDrop( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			if( Package::held( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'id' ) ) !== null )
				Package::release();

			\Nino\Http::ok( $request );
		}

		/**
		 *	The blocks of a package that was shown, written as own blocks: the package is read and held to the rule AGAIN from the bytes that are held - what the request says of it is
		 *	the key and the choices and nothing of the package - and each block is written the way the editor saves one. The first that cannot be written ends it
		 */
		public static function apiImport( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$bytes = Package::held( \Nino\Admin\Admin::postString( $data, 'id' ) );

			if( $bytes === null ) {
				self::_failed( $request, [ 403, 'there is no such package held for this session', 'themelab_import_unknown', [], 'id' ] );
				return;
			}

			$read = Package::read( $appData, $bytes, $locale );

			if( isset( $read['failure'] ) === true ) {
				Package::release();
				self::_failed( $request, $read['failure'] );
				return;
			}

			$items = is_array( $data['items'] ?? null ) === true ? $data['items'] : [];
			$choices = [];

			foreach( $read['blocks'] as $key => $parts ) {

				$item = is_array( $items[$key] ?? null ) === true ? $items[$key] : [];
				$choices[(string) $key] = [ 'take' => ( $item['take'] ?? false ) === true, 'how' => is_string( $item['how'] ?? null ) === true ? $item['how'] : 'skip' ];
			}

			$names = [];

			foreach( Package::listed( $appData, $read, $locale ) as $one )
				$names[$one['key']] = $one['name'];

			$results = Package::import( $appData, $read, $choices );
			Package::release();

			foreach( $results as $at => $result ) {

				$results[$at]['name'] = $names[$result['key']] ?? $result['key'];

				if( ( $result['result'] ?? '' ) === 'failed' )
					$results[$at]['message'] = Sheet::words( [ $locale ], 'import/failed-'. ( in_array( $result['code'] ?? '', [ 'taken', 'invalid', 'write', 'key', 'block' ], true ) === true ? $result['code'] : 'unknown' ) )[$locale];
			}

			\Nino\Http::ok( $request, [ 'results' => $results ] );
		}

		/**
		 *	Make the block of the project from a block as the library has it - or a blank one - under a key and a name. A copy has the texts, the descriptions and the labels of the
		 *	pictures in the languages the block has them in
		 *
		 *	@param		array			$descriptions	locale => description
		 */
		private static function _make( array &$appData, array &$request, array $block, string $key, array $names, string $category, string $locale, array $descriptions, string $from = '' ): void {

			$shell = self::_shell( $appData, $request, $locale );

			if( $shell === null )
				return;

			// A copy is its own block and is told from its template by the id of its root: a block that is put in on a page keeps that id, and blocks of one form have one each
			if( $from !== '' )
				$block = Sheet::reid( $appData, $block, $key );

			$nodes = Tree::read( $appData, Sheet::source( $appData, $block['tree'] ) );
			$state = Sheet::state( $block, $shell['template'], $locale );
			$at = Sheet::rootAt( $nodes );
			$made = $at === null ? [ 'failure' => [ 400, 'the block is none', 'builder_own_block', [], 'from' ] ] : Custom::parts( $appData, $shell['template'], [ 'source' => '', 'texts' => $state['texts'], 'slots' => $state['slots'] ], $nodes, [ 'at' => (string) $at, 'key' => $key, 'name' => (string) reset( $names ), 'description' => (string) reset( $descriptions ), 'category' => $category, 'texts' => [], 'pictures' => [], 'lenient' => true ] );
			$made = self::_described( $request, $made, $names, $descriptions );

			if( $made === null )
				return;

			$made['parts'] = self::_varied( $appData, $made['parts'], $key, $from );

			if( self::_checked( $appData, $request, $key, $made['parts'] ) === null )
				return;

			$written = Custom::write( $appData, $key, $made['parts'] );

			if( isset( $written['failure'] ) === true ) {
				self::_failed( $request, $written['failure'] );
				return;
			}

			unset( $appData['./nino/blocks/state'] );
			\Nino\Http::ok( $request, [ 'key' => $key ] );
		}

		public static function apiDuplicate( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$from = \Nino\Admin\Admin::postString( $data, 'key' );
			$block = self::_block( $appData, $request, $from, false );
			$names = $block === null ? null : self::_names( $appData, $request, $data['name'] ?? null, 'name', $locale );

			if( $block === null || $names === null || self::_distinct( $appData, $request, $names, '', 'name' ) === false )
				return;

			$key = Sheet::keyOf( (string) ( $names[$locale] ?? reset( $names ) ) );

			if( Sheet::taken( $appData, $key ) === true ) {
				self::_fail( $request, 409, 'the key is taken', 'themelab_taken', [ $key ], 'name' );
				return;
			}

			$listed = \Nino\Blocks::all( $appData )[$from];

			self::_make( $appData, $request, $block, $key, $names, (string) $listed['category'] === 'frame' ? 'content' : (string) $listed['category'], $locale, array_map( 'strval', (array) $listed['description'] ), $from );
		}

		public static function apiRename( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( $data, 'key' ) );
			$names = $key === null ? null : self::_names( $appData, $request, $data['name'] ?? null, 'name', $locale );

			if( $key === null || $names === null || self::_distinct( $appData, $request, $names, $key, 'name' ) === false )
				return;

			$parts = Sheet::partsOf( $appData, $key );

			if( $parts === null ) {
				self::_fail( $request, 404, 'there is no such block of the project', 'themelab_unknown', [], 'key' );
				return;
			}

			// The details beside the name - the description, the category, the rank - are the block's when the request brings them
			$more = array_filter( [ 'descriptions' => $data['description'] ?? null, 'category' => $data['category'] ?? null, 'rank' => $data['rank'] ?? null ], static fn( mixed $value ): bool => $value !== null );
			$details = self::_details( $appData, $request, [ 'details' => $more ], \Nino\Blocks::all( $appData )[$key], $locale );

			if( $details === null )
				return;

			$meta = json_decode( $parts['json'], true );
			$meta['name'] = $names + (array) $meta['name'];
			$meta['description'] = $details['descriptions'] + (array) $meta['description'];
			$meta['category'] = $details['category'];
			$meta['rank'] = $details['rank'];
			$parts['json'] = json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). "\n";

			if( self::_checked( $appData, $request, $key, $parts ) === null )
				return;

			$written = Custom::replace( $appData, $key, $parts );

			if( isset( $written['failure'] ) === true ) {
				self::_failed( $request, $written['failure'] );
				return;
			}

			unset( $appData['./nino/blocks/state'] );
			\Nino\Http::ok( $request, [ 'key' => $key ] );
		}

		public static function apiRemove( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$key = self::_own( $appData, $request, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' ) );

			if( $key === null )
				return;

			$dropped = Custom::drop( $appData, $key );

			if( $dropped !== null ) {
				self::_failed( $request, $dropped['failure'] );
				return;
			}

			unset( $appData['./nino/blocks/state'] );
			\Nino\Http::ok( $request, [ 'key' => $key ] );
		}

		public static function apiOffer( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );
			$offered = $canonical === null || Sheet::rootAt( $canonical[1] ) === null ? null : \Nino\Modules\Builder\Admin::offered( $appData, $canonical[1], \Nino\Admin\Admin::postString( $data, 'at' ), \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $offered === null ) {
				self::_fail( $request, 400, 'there is no place for a component there', 'themelab_place', [], 'at' );
				return;
			}

			\Nino\Http::ok( $request, $offered );
		}

		public static function apiNotation( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );

			if( $canonical === null ) {
				self::_fail( $request, 400, 'the tree is none of a block', 'themelab_tree', [], 'tree' );
				return;
			}

			\Nino\Http::ok( $request, [ 'notation' => \Nino\Modules\Builder\Admin::notationOf( $appData, $canonical[1], $data['look'] ?? null ) ] );
		}

		public static function apiKeys( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$kind = \Nino\Admin\Admin::postString( $data, 'kind' );

			if( $kind === 'fields' )
				\Nino\Http::ok( $request, \Nino\Modules\Builder\Admin::fieldsOf( $appData, \Nino\Admin\Admin::postString( $data, 'type' ), $locale ) );
			else if( $kind === 'types' )
				\Nino\Http::ok( $request, [ 'types' => [ [ 'type' => '/_placeholder', 'title' => (string) \Nino\Elements::PLACEHOLDERS['/_placeholder']['title'] ] ] ] );
			else
				\Nino\Http::ok( $request, [ 'entries' => [], 'more' => false ] );
		}

		public static function apiFrameOpen( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiOpen' );
		}

		public static function apiFramePreview( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiPreview' );
		}

		public static function apiFrameSave( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiSave' );
		}

		public static function apiFrameRestore( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiRestore' );
		}

		public static function apiFrameBlocks( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiBlocks' );
		}

		public static function apiFrameBlock( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiBlock' );
		}

		public static function apiFrameTile( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiTile' );
		}

		public static function apiFrameKeys( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiKeys' );
		}

		public static function apiFrameVariant( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiVariant' );
		}

		public static function apiFrameOffer( array &$appData, array &$request ): void {

			if( Frames::guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );
			$offered = $canonical === null ? null : \Nino\Modules\Builder\Admin::offered( $appData, $canonical[1], \Nino\Admin\Admin::postString( $data, 'at' ), \Nino\Modules\Builder::LEVEL_DEVELOP );

			if( $offered === null ) {
				self::_fail( $request, 400, 'there is no place for a component there', 'themelab_place', [], 'at' );
				return;
			}

			\Nino\Http::ok( $request, $offered );
		}

		public static function apiFrameNotation( array &$appData, array &$request ): void {

			if( Frames::guard( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );

			if( $canonical === null ) {
				self::_fail( $request, 400, 'the tree is none of a frame', 'themelab_tree', [], 'tree' );
				return;
			}

			\Nino\Http::ok( $request, [ 'notation' => \Nino\Modules\Builder\Admin::notationOf( $appData, $canonical[1], $data['look'] ?? null ) ] );
		}

		public static function apiFrameUpload( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiUpload' );
		}

		public static function apiFrameUnstage( array &$appData, array &$request ): void {
			Frames::pass( $appData, $request, 'apiUnstage' );
		}

		public static function apiFrames( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			\Nino\Http::ok( $request, [ 'frames' => Frames::listed( $appData ) ] );
		}

		/**
		 *	A block of a page as a block of the project - the page is not changed. The right of this panel is the whole of what it takes: an account that holds it is given the
		 *	page's block to keep, without the door of the panel of the pages
		 */
		public static function apiAdopt( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			\Nino\Modules\Builder\Admin::adopt( $appData, $request );
		}

		public static function apiTile( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$shell = self::_shell( $appData, $request, $locale );
			$key = \Nino\Admin\Admin::postString( $data, 'key' );
			$tile = $shell === null || isset( Sheet::listed( $appData, '' )[$key] ) === false ? null : Library::tile( $appData, $shell['template'], $key, $locale, true );

			if( $shell === null )
				return;

			if( $tile === null ) {
				self::_fail( $request, 404, 'there is no such block', 'themelab_unknown', [], 'key' );
				return;
			}

			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Themelab::TILE, self::perm(), $tile, \Nino\Admin\Admin::postString( $data, 'token' ), Library::TILES );

			if( $token === '' ) {
				self::_fail( $request, 500, 'the preview could not be written', 'themelab_preview' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'address' => $shell['address'] ] );
		}
	}
}
