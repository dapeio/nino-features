<?php
declare(strict_types=1);

namespace Nino\Modules\Themes {

	// The API of the workbench for the themes, and the panel that draws its answers (assets/admin.js). Every action guards
	// itself with the right /_admin/themes/manage, and so does the entry in the rail: an account without it has no panel
	//
	//	themes/list			the themes there are, the active one, whether there is a last state
	//	themes/open			one theme, its mix, the brand and what they come to: the part of each group, the knobs and their steps, the variants
	//	themes/thumb		a small picture of a theme: its start page in a draft of its own, one that does not count among the five of the previews
	//	themes/preview	a draft - a mix, a brand - built for one request in the session that asks, answered as a token, the query that names it, when it runs out and for how many seconds it holds
	//	themes/save			the mix of an own theme - written as the folder of the theme, private/themes/<key>/ - and the brand where it is posted
	//	themes/brand		the brand alone: the project's, whichever theme is open
	//	themes/save-as	a new own theme - a new folder - from a draft
	//	themes/duplicate	a new own theme from a theme as it is saved: a copy of its folder; the brand is the project's and is not copied
	//	themes/apply		the website shows a theme: a copy of it, in the form of a package, is the state of the website
	//	themes/keep			the state of the website saved as an own theme: a folder made of the copy it holds
	//	themes/restore	the last state in place of this one, and this one in place of the last - asked with the state it was seen in
	//	themes/delete		an own theme: its folder is taken away
	//	themes/upload-check	a theme package that was chosen, read and checked, and what is in it - nothing is written; the bytes are held for this session and this account
	//	themes/upload		the package that was shown, read and checked again from the held bytes and put in place, a theme of the panel replaced only where asked
	//	themes/upload-drop	the bytes that are held for a preview that was left: let go of
	//	themes/remove		a theme that was uploaded here, taken away - not the one of the website, not the one the last state shows
	class Admin {

		// How many pages of the site a list names for the preview
		private const int PAGES = 100;

		public static function perm(): string {
			return \Nino\Modules\Themes::PERM;
		}

		public static function actions(): array {

			return [
				'themes/list'			=> [ self::class, 'apiList' ],
				'themes/open'			=> [ self::class, 'apiOpen' ],
				'themes/preview'	=> [ self::class, 'apiPreview' ],
				'themes/thumb'		=> [ self::class, 'apiThumb' ],
				'themes/save'			=> [ self::class, 'apiSave' ],
				'themes/save-as'	=> [ self::class, 'apiSaveAs' ],
				'themes/duplicate'	=> [ self::class, 'apiDuplicate' ],
				'themes/brand'		=> [ self::class, 'apiBrand' ],
				'themes/apply'		=> [ self::class, 'apiApply' ],
				'themes/keep'		=> [ self::class, 'apiKeep' ],
				'themes/restore'	=> [ self::class, 'apiRestore' ],
				'themes/export'		=> [ self::class, 'apiExport' ],
				'themes/export-blocks'	=> [ self::class, 'apiExportBlocks' ],
				'themes/blocks'		=> [ self::class, 'apiBlocks' ],
				'themes/blocks-take'	=> [ self::class, 'apiBlocksTake' ],
				'themes/delete'		=> [ self::class, 'apiDelete' ],
				'themes/upload-check'	=> [ self::class, 'apiUploadCheck' ],
				'themes/upload'		=> [ self::class, 'apiUpload' ],
				'themes/upload-drop'	=> [ self::class, 'apiUploadDrop' ],
				'themes/upload-own'	=> [ self::class, 'apiUploadOwn' ],
				'themes/remove'		=> [ self::class, 'apiRemove' ],
			];
		}

		// The panel is called Theme, in the rail and over the pane - a person edits the theme of the site - and the first tab of the pane, the list, keeps the word Themes
		public static function nav(): array {
			return [ 'themes', '/_admin/themes/nav', 60 ];
		}

		public static function tab(): string {
			return '/_admin/themes/tab/list/name';
		}

		// The tabs the panel brings: the fonts of the project (see Typefaces) and its own css (see ProjectCss), which has a right of its own. The tab of the settings, which the registry attaches to a
		// feature that declares some, comes after it
		public static function tabs(): array {
			return [ Typefaces::class, ProjectCss::class ];
		}

		// The mounts of the panel: its list of themes and the screen of one theme, which assets/admin.js draws
		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/panel' );
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.6-.7 1.6-1.6 0-.4-.2-.8-.5-1.1-.3-.3-.4-.7-.4-1.1 0-.9.7-1.6 1.6-1.6H16c3.1 0 6-2.5 6-5.5C22 6 17.5 2 12 2z"/></svg>';
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {

			return match( $action ) {
				'themes/save'			=> 'Save Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				'themes/brand'		=> 'Save Brand',
				'themes/save-as'	=> 'Save Theme As '. self::_shown( (string) ( is_array( $data['mix'] ?? null ) === true ? \Nino\Admin\Admin::postString( $data['mix'], 'name' ) : '' ) ),
				'themes/duplicate'	=> 'Duplicate Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ). ' As '. self::_shown( \Nino\Admin\Admin::postString( $data, 'name' ) ),
				'themes/apply'		=> 'Apply Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				'themes/keep'			=> 'Keep Website State As Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'name' ) ),
				'themes/restore'	=> 'Restore Last Theme State',
				'themes/delete'		=> 'Delete Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				'themes/upload'		=> 'Upload Theme',
				'themes/upload-own'	=> 'Copy Uploaded Theme As Own Theme',
				'themes/export'		=> 'Export Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				'themes/blocks-take'	=> 'Take Blocks Of Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				'themes/remove'		=> 'Remove Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				default						=> '',
			};
		}

		public static function apiList( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			// The list is where a theme that was put in place by hand over an uploaded one is told from it: the marks are held to their folders
			Upload::sweepMarks( $appData, true );

			$read = Reader::read( $appData );
			$state = Store::read( $appData );
			$themes = [];

			foreach( $read['themes'] as $key => $theme ) {

				$parts = [];

				foreach( $theme['parts'] as $group => $part )
					$parts[$group] = [ 'ok' => $part['ok'], 'problem' => $part['problem'], 'why' => Reader::explain( $part['problem'] ) ];

				$origin = Upload::origin( $appData, (string) $key );

				// The css of the theme as a whole - how many lines it has, and why it is not used where the rule refuses it - and the frames the theme brings that show texts
				// of the website: each with the keys it reads, which a person who puts the theme on looks at first
				$css = [ 'lines' => (string) $theme['css'] === '' ? 0 : substr_count( (string) $theme['css'], "\n" ) + 1, 'why' => Reader::explain( (string) $theme['cssProblem'] ) ];
				$themes[] = [ 'key' => $key, 'kind' => 'theme', 'origin' => $origin, 'version' => $theme['version'], 'name' => $theme['name'], 'description' => $theme['description'], 'brand' => $theme['brand'], 'parts' => $parts, 'css' => $css, 'frames' => Upload::framesOf( $appData, (string) $key ), 'blocks' => Package::counted( (string) $theme['dir'] ), 'active' => ( $state['active']['theme'] ?? '' ) === $key ];
			}

			// An own theme says where each of its parts comes from - the installed theme whose part it is the same as, which is all that is told of it - so that a card can say what it is
			// made of, which of its parts rest and why, and what is the matter with its css
			$own = Reader::own( $appData );

			foreach( $state['own'] as $key => $mix ) {

				$parts = [];

				foreach( $own['themes'][$key]['parts'] as $group => $part )
					$parts[$group] = [ 'ok' => $part['ok'], 'problem' => $part['problem'], 'why' => Reader::explain( $part['problem'] ) ];

				$css = [ 'lines' => trim( (string) ( $mix['css'] ?? '' ) ) === '' ? 0 : substr_count( trim( (string) $mix['css'] ), "\n" ) + 1, 'why' => Reader::explain( (string) $own['themes'][$key]['cssProblem'] ) ];
				// A part that rests is said to rest, as the editor says it, and not to be changed
				$from = array_map( static fn( array $entry ): string => (string) ( $entry['from'] ?? 'standard' ), (array) ( $mix['parts'] ?? [] ) );

				foreach( $parts as $group => $part )
					if( $part['ok'] === false && isset( $from[$group] ) === true )
						$from[$group] = 'rests';

				$themes[] = [ 'key' => 'own:'. $key, 'kind' => 'own', 'name' => (string) ( $mix['name'] ?? $key ), 'from' => $from, 'parts' => $parts, 'css' => $css, 'stamp' => self::_ownStamp( $appData, $key ), 'active' => ( $state['active']['theme'] ?? '' ) === 'own:'. $key , 'blocks' => Package::counted( Reader::ownDir( $appData ). '/'. $key ) ];
			}

			$groups = [];

			foreach( \Nino\Components::groups( $appData ) as $group => $declared )
				$groups[] = [ 'key' => $group, 'label' => $declared['label'], 'feature' => $declared['feature'], 'components' => $declared['components'], 'demo' => $declared['demo'] !== '' ];

			$rejected = [];

			foreach( $read['rejected'] as $folder => $reason )
				$rejected[] = [ 'folder' => basename( (string) $folder ), 'reason' => $reason, 'why' => Reader::explain( $reason ), 'own' => false ];

			foreach( $own['rejected'] as $folder => $reason )
				$rejected[] = [ 'folder' => basename( (string) $folder ), 'reason' => $reason, 'why' => Reader::explain( $reason ), 'own' => true ];

			$fonts = [];

			foreach( Brand::fonts( $appData ) as $slug => $font )
				$fonts[] = [ 'key' => $slug, 'name' => $font['name'], 'system' => $font['system'] ];

			\Nino\Preview::tidy( $appData );
			self::_dropOld( $appData );

			\Nino\Http::ok( $request, [ 'blocksTake' => Package::blocksAvailable( $appData ), 'themes' => self::_objects( [ 'themes' => $themes ], [ 'themes.*.from', 'themes.*.parts', 'themes.*.brand' ] )['themes'], 'rejected' => $rejected, 'groups' => $groups, 'fonts' => $fonts, 'package' => [ 'bytes' => \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] ], 'limits' => [ 'css' => Mix::CSS_LENGTH, 'name' => Mix::NAME_LENGTH, 'key' => \Nino\Components::NAME_LENGTH ], 'pages' => self::pages( $appData ) ] + self::_state( $appData, $state ) );
		}

		/**
		 *	The pages of the site a preview may show: the paths of the routes that answer html, as the kernel lists them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ [ 'path' => ..., 'title' => ... ], ... ], at most PAGES
		 */
		public static function pages( array &$appData ): array {

			$pages = [];

			foreach( \Nino\Http::pages( (array) ( $appData['/nino/http/routes'] ?? [] ) ) as $page )
				if( $page['html'] === true && $page['wildcard'] === false && $page['status'] < 400 && isset( $pages[$page['path']] ) === false )
					$pages[$page['path']] = [ 'path' => $page['path'], 'title' => trim( \Nino\Html::renderTextfill( $appData, '/_nino/webpage'. $page['uri']. '/title' ) ) ];

			return array_slice( array_values( $pages ), 0, self::PAGES );
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			$plan = Compiler::plan( $appData, $theme['mix'], $state['brand'] );

			\Nino\Http::ok( $request, [ 'theme' => [ 'key' => $theme['key'], 'kind' => $theme['kind'], 'name' => $theme['name'] ], 'mix' => self::_wired( $theme['mix'] ), 'cssRule' => Mix::cssRule( (string) ( $theme['mix']['css'] ?? '' ) ), 'stamp' => $theme['kind'] === 'own' ? self::_ownStamp( $appData, substr( $theme['key'], 4 ) ) : '' ] + self::_describe( $appData, $plan, $state['brand'] ) + self::_state( $appData, $state ) );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$mix = Mix::clean( $appData, $data['mix'] ?? null, false );

			if( is_string( $mix ) === true ) {
				\Nino\Http::fail( $request, 400, 'the mix is refused: '. $mix, 'themes_mix', [ $mix, Mix::where( $mix ) ], 'mix' );
				return;
			}

			$brand = array_key_exists( 'brand', $data ) === true ? Brand::clean( $appData, $data['brand'], $state['brand'] ) : $state['brand'];

			if( is_string( $brand ) === true ) {
				\Nino\Http::fail( $request, 400, 'the brand is refused at '. $brand, 'themes_brand', [ $brand ], 'brand' );
				return;
			}

			$built = Compiler::compile( $appData, $mix, $brand );
			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Themes::KIND, self::perm(), [ 'css' => $built['css'], 'variants' => $built['variants'] ], \Nino\Admin\Admin::postString( $data, 'token' ), \Nino\Modules\Themes::PREVIEWS_MAX );

			if( $token === '' ) {
				\Nino\Http::fail( $request, 500, 'the preview could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'cssFaults' => $built['faults'], 'cssRule' => $built['rule'] ] + self::_describe( $appData, $built['plan'], $brand ) );
		}

		public static function apiThumb( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			// What is saved of the theme and the brand of the project: a thumbnail shows what the website would show, and nothing of a draft
			$mix = Mix::clean( $appData, $theme['mix'], $theme['kind'] === 'own' );

			if( is_string( $mix ) === true ) {
				\Nino\Http::fail( $request, 409, 'the mix is refused: '. $mix, 'themes_mix', [ $mix, Mix::where( $mix ) ], 'mix' );
				return;
			}

			$built = Compiler::compile( $appData, $mix, $state['brand'] );
			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Themes::PICTURES, self::perm(), [ 'css' => $built['css'], 'variants' => $built['variants'] ], \Nino\Admin\Admin::postString( $data, 'token' ), \Nino\Modules\Themes::PICTURES_MAX );

			if( $token === '' ) {
				\Nino\Http::fail( $request, 500, 'the preview could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = \Nino\Admin\Admin::postString( $data, 'theme' );

			if( str_starts_with( $theme, 'own:' ) === false ) {

				self::_fail( $request, isset( Reader::read( $appData )['themes'][$theme] ) === true
					? [ 409, 'a theme that comes with a package is not changed - save it as an own theme', 'themes_not_own', [], 'theme' ]
					: [ 404, 'there is no such theme', 'themes_unknown', [ self::_named( $theme ) ], 'theme' ] );
				return;
			}

			$key = substr( $theme, 4 );

			if( isset( $state['own'][$key] ) === false ) {
				self::_fail( $request, [ 404, 'there is no such theme', 'themes_unknown', [ self::_named( $theme ) ], 'theme' ] );
				return;
			}

			$prepared = self::_prepare( $appData, $data, $state['brand'] );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			// The stamp of the theme and of the brand and the name are looked at with the lock held, on the folder as it is then: a request that read the theme before another one
			// wrote it is told so as well. The stamps and the stylesheet that is not the feature's are separate answers, and force is only for the last
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $prepared, $data ): ?array {

				$current = Reader::own( $appData, true )['themes'][$key] ?? null;

				if( $current === null || self::_changed( $current['stamp'], $data ) === true )
					return self::_changedFailure( (string) ( $current['label'] ?? $key ) );

				if( isset( $prepared['brand'] ) === true && ( $failure = self::_brandFailure( $appData, $next, $data ) ) !== null )
					return $failure;

				// A name that another own theme has - or whose key it is - is no rename: two cards would carry one name
				foreach( self::_ownNames( $appData ) as $other => $name )
					if( (string) $other !== $key && self::_sameName( (string) $prepared['mix']['name'], $name ) === true )
						return [ 409, 'an own theme of that name is there', 'themes_name_taken', [ $name ], 'name' ];

				if( ( $failure = self::_write( $appData, $next, $key, $prepared, $current, true ) ) !== null )
					return $failure;

				// The theme that is on the website is a copy in the state: where this is the one, the copy is made anew, so that what is saved is what the website shows
				$active = \Nino\Filesystem::getFileContent( $appData, $next. '/active.json', [] );

				return is_array( $active ) === true && ( $active['theme'] ?? '' ) === 'own:'. $key ? self::_hold( $appData, $next, 'own:'. $key ) : null;
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, $theme, $failure );
		}

		public static function apiSaveAs( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$prepared = self::_prepare( $appData, $data, $state['brand'] );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			// The name, the key and the number of the themes are looked at with the lock held, on the state that is being copied: two requests that save
			// a theme of one name are not two cards of it. A key that is taken by a theme that has another name is not the name's: the new theme has the next free one
			$made = '';
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( &$made, $prepared, $data ): ?array {

				if( isset( $prepared['brand'] ) === true && ( $failure = self::_brandFailure( $appData, $next, $data ) ) !== null )
					return $failure;

				return self::_create( $appData, $next, $prepared, null, $made );
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		public static function apiDuplicate( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			// What is copied is what the folder of an own theme is with the lock held - the stamp says it is the one the person saw - or a theme of a package as it is at that
			// time. The name is the new one; the brand is the project's, so it is not part of it
			$made = '';
			$key = $theme['key'];
			$name = \Nino\Admin\Admin::postString( $data, 'name' );
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( &$made, $key, $theme, $name, $data ): ?array {

				$mix = $theme['mix'];
				$base = Reader::read( $appData )['themes'][$key] ?? null;

				if( $theme['kind'] === 'own' ) {

					$base = Reader::own( $appData, true )['themes'][substr( $key, 4 )] ?? null;

					if( $base === null || self::_changed( $base['stamp'], $data ) === true )
						return self::_changedFailure( (string) ( $base['label'] ?? substr( $key, 4 ) ) );

					$mix = (array) Mix::ofOwn( $appData, substr( $key, 4 ) );
				}

				$mix['name'] = $name;
				$prepared = self::_prepare( $appData, [ 'mix' => $mix ], [] );

				if( isset( $prepared['failure'] ) === true )
					return $prepared['failure'];

				return self::_create( $appData, $next, $prepared, $base, $made );
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		public static function apiBrand( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$brand = Brand::clean( $appData, $data['brand'] ?? null, $state['brand'] );

			if( is_string( $brand ) === true )
				self::_fail( $request, [ 400, 'the brand is refused at '. self::_shown( $brand ), 'themes_brand', [ self::_shown( $brand ) ], 'brand' ] );
			else
				self::_finish( $appData, $request, \Nino\Admin\Admin::postString( $data, 'theme' ), Store::commit( $appData, static function( array &$appData, string $next ) use ( $brand, $data ): ?array {

					if( ( $failure = self::_brandFailure( $appData, $next, $data ) ) !== null )
						return $failure;

					return \Nino\Filesystem::putFileContent( $appData, $next. '/brand.json', $brand ) === true ? null : [ 500, 'the state could not be written', '', [], '' ];
				}, ( $data['force'] ?? false ) === true ) );
		}

		public static function apiApply( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			$key = $theme['key'];

			// The theme is copied into the state, in the form of a package, and the stylesheet is built from the copy: the site hangs on neither the package nor the folder of an own
			// theme. An own theme is applied as the one who asks saw it - the stamp is looked at with the lock held, as it is at a save, and force is no answer to it
			self::_finish( $appData, $request, $key, Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $theme, $data ): ?array {

				if( $theme['kind'] === 'own' ) {

					$current = Reader::own( $appData, true )['themes'][substr( $key, 4 )] ?? null;

					if( $current === null || self::_changed( $current['stamp'], $data ) === true )
						return self::_changedFailure( (string) ( $current['label'] ?? substr( $key, 4 ) ) );
				}

				return self::_hold( $appData, $next, $key );
			}, ( $data['force'] ?? false ) === true ) );
		}

		/**
		 *	The state of the website saved as an own theme: its name is asked, its folder is made of the copy of the theme the state holds - every part, byte for byte, the
		 *	licence and the readme - and the state is then that theme: the stylesheet is the one it was, the website shows the own theme, and the note of where each part came
		 *	from is gone, since the theme holds them. Asked with the state it was seen in, as a restore is
		 */
		public static function apiKeep( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );

			if( $state['held'] === null || $state['active'] === null ) {
				self::_fail( $request, [ 409, 'the website shows no theme to keep', 'themes_none_held', [], '' ] );
				return;
			}

			$named = Mix::clean( $appData, [ 'name' => \Nino\Admin\Admin::postString( $data, 'name' ) ], true );

			if( is_string( $named ) === true ) {
				self::_fail( $request, [ 400, 'the mix is refused: '. $named, 'themes_mix', [ $named, Mix::where( $named ) ], 'name' ] );
				return;
			}

			$made = '';
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( &$made, $named, $data ): ?array {

				// The state the one who asks has seen, looked at with the lock held: a change that came between is not kept unseen
				if( is_string( $data['base'] ?? null ) === false || hash_equals( Store::stamp( $appData ), $data['base'] ) === false )
					return [ 409, 'the themes were changed since they were opened', 'themes_state', [], '' ];

				return Reader::using( $appData, \Nino\Filesystem::path( $appData, $next. '/theme' ), static function( array|string $held ) use ( &$appData, $next, $named, &$made ): ?array {

					if( is_string( $held ) === true )
						return [ 409, 'the state holds no theme: '. $held, 'themes_none_held', [], '' ];

					$mix = Mix::ofCurrent( $appData, $held );
					$mix['name'] = $named['name'];
					$prepared = self::_prepare( $appData, [ 'mix' => $mix ], [] );

					if( isset( $prepared['failure'] ) === true )
						return $prepared['failure'];

					if( ( $failure = self::_create( $appData, $next, $prepared, $held, $made ) ) !== null )
						return $failure;

					@unlink( \Nino\Filesystem::path( $appData, $next. '/origin.json' ) );

					return \Nino\Filesystem::putFileContent( $appData, $next. '/active.json', [ 'theme' => 'own:'. $made ] ) === true ? null : [ 500, 'the state could not be written', '', [], '' ];
				} );
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		/**
		 *	A theme copied into the state that is being made: the folder of the theme - an installed one or an own one - in the form of a package, as the theme the website
		 *	shows, and the note of where each of its parts comes from; the stylesheet is built from it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		string		$key					The key of an installed theme, or own:<key>
		 *
		 *	@return 	array|null
		 */
		private static function _hold( array &$appData, string $next, string $key ): ?array {

			$own = str_starts_with( $key, 'own:' ) === true;
			$source = $own === true ? Reader::own( $appData, true )['themes'][substr( $key, 4 )]['dir'] ?? '' : Reader::read( $appData )['themes'][$key]['dir'] ?? '';
			$into = \Nino\Filesystem::path( $appData, $next. '/theme' );

			\Nino\Filesystem::removeDir( $into );

			if( $source === '' || Mix::copy( $source, $into ) === false )
				return [ 500, 'the theme could not be copied into the state', '', [], '' ];

			if( \Nino\Filesystem::putFileContent( $appData, $next. '/origin.json', Mix::origins( $appData, $key ) ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $next. '/active.json', [ 'theme' => $key ] ) === false )
				return [ 500, 'the state could not be written', '', [], '' ];

			return null;
		}

		public static function apiRestore( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			// The state the one who asks has seen is looked at by the store with its lock held; a request that names none has seen none
			$failure = Store::restore( $appData, ( $data['force'] ?? false ) === true, is_string( $data['base'] ?? null ) === true ? $data['base'] : '-' );

			if( $failure !== null )
				self::_fail( $request, $failure );
			else {
				\Nino\Preview::tidy( $appData );
				\Nino\Http::ok( $request, [ 'restored' => true ] + self::_state( $appData, Store::read( $appData ) ) );
			}
		}

		/**
		 *	The guard of the actions of the upload: the right of the panel, and the bytes of a preview that was left are let go of when their time is out
		 */
		private static function _gate( array &$appData, array &$request ): bool {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return false;

			// The bytes of a preview that was left are let go of when their time is out, whatever the action is; and a mark whose theme is gone - taken away where the packages are managed - goes with it
			Upload::sweep();
			Upload::sweepMarks( $appData );

			return true;
		}

		/**
		 *	The locale a name is told in: the one the panel asks for, else the native one of the site
		 */
		private static function _locale( array &$appData, array $data ): string {

			$asked = \Nino\Admin\Admin::postString( $data, 'locale' );

			return preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', $asked ) === 1 ? $asked : \Nino\Locales::getNativeLocale( $appData );
		}

		/**
		 *	A package that was chosen: read in memory, held to the rule of the catalogue's themes, and shown. Nothing is written and nothing of the file is kept but its bytes,
		 *	which are held in the session of the one who sent them, for a quarter of an hour, under a key that is made here
		 */
		public static function apiUploadCheck( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, \Nino\Admin\Admin::postData() );
			$failure = \Nino\Admin\Admin::uploadError( $_FILES['file'] ?? null );

			if( $failure !== null ) {
				self::_fail( $request, [ $failure['status'], $failure['error'], $failure['code'], $failure['params'], 'file' ] );
				return;
			}

			$tmp = $_FILES['file']['tmp_name'] ?? null;
			$size = is_string( $tmp ) === true ? (int) @filesize( $tmp ) : 0;

			if( is_string( $tmp ) === false || $size < 1 ) {
				self::_fail( $request, [ 400, 'the file is empty', 'themes_upload_nofile', [], 'file' ] );
				return;
			}

			if( $size > \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] ) {
				self::_fail( $request, [ 400, 'the file is none a theme package comes in', 'themes_upload_large', [ \Nino\Catalogue::ARCHIVE_BYTES[\Nino\Packages::THEME] ], 'file' ] );
				return;
			}

			// Read from where php put it, and never written: nothing of the file is left behind when the request ends, and no name of the request is a path
			$bytes = (string) @file_get_contents( $tmp );
			$read = Upload::read( $appData, $bytes );

			if( isset( $read['failure'] ) === true ) {
				self::_fail( $request, $read['failure'] );
				return;
			}

			\Nino\Http::ok( $request, [ 'id' => Upload::hold( $appData, $bytes ), 'seconds' => Upload::HOLD, 'theme' => Upload::listed( $appData, $read, $locale ) ] );
		}

		/**
		 *	A preview that was left lets go of the bytes that are held for it, when the key is the one that is held
		 */
		public static function apiUploadDrop( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			if( Upload::held( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'id' ) ) !== null )
				Upload::release();

			\Nino\Http::ok( $request );
		}

		/**
		 *	The package that was shown, put in place: read and held to the rule AGAIN from the bytes that are held - what the request says of it is the key and whether to replace,
		 *	and nothing of the package. The theme is in the list afterwards and is not applied
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$bytes = Upload::held( $appData, \Nino\Admin\Admin::postString( $data, 'id' ) );

			if( $bytes === null ) {
				self::_fail( $request, [ 403, 'there is no such package held for this session', 'themes_upload_unknown', [], 'id' ] );
				return;
			}

			$done = Upload::install( $appData, $bytes, ( $data['replace'] ?? false ) === true, self::_locale( $appData, $data ) );

			// The bytes are let go of once the package is written or is refused; where another change of the packages was at work they stay, and the person asks again
			if( ( $done['failure'][2] ?? '' ) !== 'themes_upload_package_busy' )
				Upload::release();

			if( isset( $done['failure'] ) === true ) {
				self::_fail( $request, $done['failure'] );
				return;
			}

			\Nino\Preview::tidy( $appData );
			\Nino\Http::ok( $request, [ 'installed' => $done ] + self::_state( $appData, Store::read( $appData ) ) );
		}

		/**
		 *	The package that was shown, copied as an own theme: read and held to the rule AGAIN from the bytes that are held, and made a folder of private/themes/ with its files as they are
		 *	- only the key in its theme.json is the key of the folder, and its name is the one that is asked for, where one is. Nothing of the package is installed and nothing is
		 *	applied: the theme is in the list afterwards, as any own theme is, and is put on the website where a person asks for that
		 */
		public static function apiUploadOwn( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$bytes = Upload::held( $appData, \Nino\Admin\Admin::postString( $data, 'id' ) );

			if( $bytes === null ) {
				self::_fail( $request, [ 403, 'there is no such package held for this session', 'themes_upload_unknown', [], 'id' ] );
				return;
			}

			$read = Upload::read( $appData, $bytes );

			if( isset( $read['failure'] ) === true ) {
				self::_fail( $request, $read['failure'] );
				return;
			}

			$asked = trim( \Nino\Admin\Admin::postString( $data, 'name' ) );
			$theme = $read['theme'];
			$files = $read['files'];

			// Its name is the one the reader of the own themes will call it by - the name in the native language of the site, else English, else the first - which is what names are compared by
			$name = $asked !== '' ? $asked : ( is_string( $theme['name'] ?? null ) === true ? $theme['name'] : (string) ( $theme['name'][\Nino\Locales::getNativeLocale( $appData )] ?? $theme['name']['en_US'] ?? reset( $theme['name'] ) ) );

			// The name of an own theme is one line of at most the length the panel lets it have, and a package has no markup in it
			if( mb_strlen( $name ) > Mix::NAME_LENGTH || preg_match( '/[\x00-\x1F\x7F<>]|\[\[/', $name ) === 1 ) {
				self::_fail( $request, [ 400, 'the name is text of 1 to '. Mix::NAME_LENGTH. ' characters, in one line', 'themes_import_name', [ Mix::NAME_LENGTH ], 'name' ] );
				return;
			}

			$made = '';
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( &$made, $name, $asked, $theme, $files ): ?array {

				$failure = self::_room( $appData, $name, $made );

				if( $failure !== null )
					return $failure;

				// The folder is the package's; its theme.json is the one thing that is made over, for the key of the folder and the name that is asked for
				$theme['key'] = $made;

				if( $asked !== '' )
					$theme['name'] = $asked;

				$files['theme.json'] = json_encode( $theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ). "\n";
				$problem = \Nino\Packages::check( $appData, \Nino\Packages::THEME, $files );

				if( $problem !== null )
					return Upload::problem( $problem )['failure'];

				$done = Mix::place( $appData, $made, $files );

				return is_string( $done ) === true ? [ 500, 'the theme could not be written: '. $done, 'themes_write', [ self::_shown( $done ) ], '' ] : null;
			}, ( $data['force'] ?? false ) === true );

			// The bytes are let go of once the theme is made; where it was refused - the name is taken, another change was at work - they stay, and the person asks again
			if( $failure === null )
				Upload::release();

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		/**
		 *	An own theme as a package, to save: its folder is packed after the whole check of the kernel - what the catalogue would not take is not handed out - and an answer that says it is made
		 *	(`check`) is what the panel asks first and again where the state of the theme changes, so that a theme that cannot be exported is said so, with the reason, and the download
		 *	that follows is the file. Nothing is written. The name of the file is the key and the version of the theme, which the check has seen
		 */
		public static function apiExport( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$theme = \Nino\Admin\Admin::postString( $data, 'theme' );

			if( str_starts_with( $theme, 'own:' ) === false ) {
				self::_fail( $request, [ 404, 'there is no such own theme', 'themes_export_unknown', [], 'theme' ] );
				return;
			}

			// Blocks of the project are put in by an account that holds the right of the feature that lists them: for any other the request is refused, not made without them
			$blocks = is_array( $data['blocks'] ?? null ) === true ? array_values( $data['blocks'] ) : [];

			if( $blocks !== [] && Package::blocksAvailable( $appData ) === false ) {
				self::_fail( $request, [ 403, 'blocks of the project are not put in a package by this account', 'themes_blocks_right', [], 'blocks' ] );
				return;
			}

			$made = Package::export( $appData, substr( $theme, 4 ), $blocks );

			if( isset( $made['failure'] ) === true ) {
				self::_fail( $request, $made['failure'] );
				return;
			}

			if( ( $data['check'] ?? false ) === true ) {
				\Nino\Http::ok( $request, [ 'file' => $made['file'], 'size' => strlen( $made['bytes'] ), 'files' => $made['files'], 'replaced' => $made['replaced'] ] );
				return;
			}

			\Nino\Http::download( $request, $made['bytes'], $made['file'], 'application/gzip' );
		}

		/**
		 *	What the dialog of the export asks of the project: the blocks of the project that may go into the package, the blocks and the files the folder of the theme carries already, and the
		 *	limits of a package - so that what is too much is said before anything is packed. For an account that does not hold the right of the feature that lists the blocks, or where that feature
		 *	is off, the answer is that there is nothing to choose, and the export is the folder alone. Nothing is written
		 */
		public static function apiExportBlocks( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$theme = \Nino\Admin\Admin::postString( $data, 'theme' );
			$read = str_starts_with( $theme, 'own:' ) === true ? Package::files( $appData, substr( $theme, 4 ) ) : [ 'missing' => true ];

			if( isset( $read['missing'] ) === true ) {
				self::_fail( $request, [ 404, 'there is no such own theme', 'themes_export_unknown', [], 'theme' ] );
				return;
			}

			if( isset( $read['rejected'] ) === true ) {
				self::_fail( $request, [ 400, 'the folder is no own theme, the reader refuses it', 'themes_export_rejected', [], 'theme' ] );
				return;
			}

			if( Package::blocksAvailable( $appData ) === false ) {
				\Nino\Http::ok( $request, [ 'available' => false ] );
				return;
			}

			if( isset( $read['problem'] ) === true ) {
				self::_fail( $request, Upload::problem( $read['problem'] )['failure'] );
				return;
			}

			$base = [ 'files' => 0, 'bytes' => 0 ];
			$carried = [];

			foreach( $read['files'] as $path => $bytes ) {

				if( preg_match( '~^blocks/([^/]+)/~', (string) $path, $found ) === 1 ) {
					$carried[$found[1]]['files'] = ( $carried[$found[1]]['files'] ?? 0 ) + 1;
					$carried[$found[1]]['bytes'] = ( $carried[$found[1]]['bytes'] ?? 0 ) + strlen( $bytes );
				}
				else {
					$base['files']++;
					$base['bytes'] += strlen( $bytes );
				}
			}

			$limits = \Nino\Packages::LIMITS[\Nino\Packages::THEME];

			\Nino\Http::ok( $request, [ 'available' => true, 'blocks' => \Nino\Modules\Themelab\Package::choices( $appData, self::_locale( $appData, $data ) ), 'base' => $base,
				'carried' => $carried === [] ? new \stdClass() : $carried, 'limits' => [ 'files' => $limits['files'], 'bytes' => $limits['bytes'] ] ] );
		}

		/**
		 *	The blocks a theme carries in its folder - an installed theme or an own one - as a person who may put blocks into the project is shown them: each with its name, its category and
		 *	what stands in the way of its key. They are read as files of a package and held to the rule of the kernel for blocks, as a package of blocks is; nothing is written
		 */
		public static function apiBlocks( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$locale = self::_locale( $appData, $data );
			$found = self::_carried( $appData, \Nino\Admin\Admin::postString( $data, 'theme' ), $locale );

			if( isset( $found['failure'] ) === true ) {
				self::_fail( $request, $found['failure'] );
				return;
			}

			\Nino\Http::ok( $request, [ 'theme' => $found['theme'], 'blocks' => \Nino\Modules\Themelab\Package::listed( $appData, [ 'blocks' => $found['blocks'] ], $locale ) ] );
		}

		/**
		 *	The blocks that were chosen, put into the project - by the way blocks come into it, the import of the feature that lists them, with all its checks: a block of a key that the
		 *	project has is left, or is a copy under a key of its own, and never replaces. Nothing is registered by it and nothing is applied: a block is a block of the project, and a
		 *	header or a footer among them is one that can be chosen
		 */
		public static function apiBlocksTake( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$locale = self::_locale( $appData, $data );
			$found = self::_carried( $appData, \Nino\Admin\Admin::postString( $data, 'theme' ), $locale );

			if( isset( $found['failure'] ) === true ) {
				self::_fail( $request, $found['failure'] );
				return;
			}

			$items = is_array( $data['items'] ?? null ) === true ? $data['items'] : [];

			// The one way blocks come in does not replace: what a request says of "replace" is a "skip"
			foreach( $items as $key => $item )
				if( is_array( $item ) === true && ( $item['how'] ?? '' ) === 'replace' )
					$items[$key]['how'] = 'skip';

			\Nino\Http::ok( $request, [ 'results' => \Nino\Modules\Themelab\Package::take( $appData, [ 'blocks' => $found['blocks'] ], $items, $locale ) ] );
		}

		/**
		 *	The blocks that the folder of a theme carries, read and held to the rule of the kernel for blocks: for the right of the feature that lists the blocks, a theme that is an
		 *	installed one or an own one, and nothing else - the key is a key and the folder is a folder, not a link
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$theme				'own:<key>' or the key of an installed theme
		 *	@param		string		$locale
		 *
		 *	@return 	array											'theme' => the name, 'blocks' => as the import reads them; or 'failure'
		 */
		private static function _carried( array &$appData, string $theme, string $locale ): array {

			if( Package::blocksAvailable( $appData ) === false )
				return [ 'failure' => [ 403, 'blocks are not put into the project by this account', 'themes_blocks_right', [], 'theme' ] ];

			$own = str_starts_with( $theme, 'own:' ) === true;
			$key = $own === true ? substr( $theme, 4 ) : $theme;
			$dir = '';
			$name = $key;

			if( preg_match( \Nino\Components::NAME, $key ) === 1 && strlen( $key ) <= \Nino\Components::NAME_LENGTH ) {

				if( $own === true && isset( Reader::own( $appData )['themes'][$key] ) === true ) {
					$dir = Reader::ownDir( $appData ). '/'. $key;
					$name = (string) Reader::own( $appData )['themes'][$key]['label'];
				}
				else if( $own === false && isset( Reader::read( $appData )['themes'][$key] ) === true ) {
					$dir = (string) Reader::read( $appData )['themes'][$key]['dir'];
					$name = (string) ( is_array( Reader::read( $appData )['themes'][$key]['name'] ) === true ? ( Reader::read( $appData )['themes'][$key]['name'][$locale] ?? reset( Reader::read( $appData )['themes'][$key]['name'] ) ) : Reader::read( $appData )['themes'][$key]['name'] );
				}
			}

			if( $dir === '' || is_link( $dir ) === true || is_dir( $dir ) === false )
				return [ 'failure' => [ 404, 'there is no such theme', 'themes_unknown', [ self::_named( $theme ) ], 'theme' ] ];

			$carried = Package::carried( $dir );

			if( isset( $carried['problem'] ) === true )
				return Upload::problem( $carried['problem'] );

			$blocks = \Nino\Modules\Themelab\Package::ofTheme( $carried['files'] );

			if( $blocks === [] )
				return [ 'failure' => [ 404, 'the theme carries no blocks', 'themes_blocks_none', [], 'theme' ] ];

			$refused = \Nino\Modules\Themelab\Package::refuse( $appData, $carried['files'], $locale );

			return $refused ?? [ 'theme' => $name, 'blocks' => $blocks ];
		}

		/**
		 *	A theme that was uploaded here, taken away
		 */
		public static function apiRemove( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$theme = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'theme' );
			$failed = Upload::remove( $appData, $theme, self::_locale( $appData, \Nino\Admin\Admin::postData() ) );

			if( $failed !== null ) {
				self::_fail( $request, $failed['failure'] );
				return;
			}

			\Nino\Preview::tidy( $appData );
			\Nino\Http::ok( $request, [ 'removed' => true ] + self::_state( $appData, Store::read( $appData ) ) );
		}

		public static function apiDelete( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = \Nino\Admin\Admin::postString( $data, 'theme' );
			$key = str_starts_with( $theme, 'own:' ) === true ? substr( $theme, 4 ) : '';

			if( isset( $state['own'][$key] ) === false )
				self::_fail( $request, str_starts_with( $theme, 'own:' ) === false && isset( Reader::read( $appData )['themes'][$theme] ) === true
					? [ 409, 'only an own theme is deleted', 'themes_not_own', [], 'theme' ]
					: [ 404, 'there is no such theme', 'themes_unknown', [ self::_named( $theme ) ], 'theme' ] );
			else if( ( $state['active']['theme'] ?? '' ) === $theme )
				self::_fail( $request, [ 409, 'the theme of the website is not deleted', 'themes_active', [ $state['own'][$key]['name'] ?? $key ], 'theme' ] );
			else {

				// The folder is put aside under the lock and taken away when the change holds: the lock is the one of every change, and the store settles the folder with it held, so that a theme that is saved is not deleted under its save
				$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $data ): ?array {

					$current = Reader::own( $appData, true )['themes'][$key] ?? null;

					if( $current === null || self::_changed( $current['stamp'], $data ) === true )
						return self::_changedFailure( (string) ( $current['label'] ?? $key ) );

					$done = Mix::drop( $appData, $key );

					return is_string( $done ) === true ? [ 500, 'the theme could not be deleted: '. $done, 'themes_write', [ $done ], '' ] : null;
				}, ( $data['force'] ?? false ) === true );


				if( $failure !== null )
					self::_fail( $request, $failure );
				else {
					\Nino\Preview::tidy( $appData );
					\Nino\Http::ok( $request, [ 'deleted' => true ] + self::_state( $appData, Store::read( $appData ) ) );
				}
			}
		}

		/**
		 *	What the panel draws a mix as - a saved theme in open, a draft in preview, the same shape:
		 *
		 *	groups    group => label, from (standard or theme@version), theme, version, origin (standard, installed, copy), package (installed,
		 *	          missing - the package of the theme is not installed, and the part is the project's copy -, or '' for the standard part),
		 *	          problem (why the part rests, '' where it stands), missing (the theme brings no part for the group), knobs, components, offers
		 *	knobs     key, label, extra (a knob of the part), small (for small screens, the page's), step -2 to 2, default (the middle step of
		 *	          the ladder) and ladder (its five values; for a knob of the page five maps of tokens)
		 *	components  name, label (a text or a locale => text map), variants (every one there is: the schema's and the part's, each with its label - null where the schema has no
		 *	          word for it - and where it comes from) and standard
		 *	offers    the themes that have a part for the group, with whether it holds and why not
		 *	brand     effective (every key as it comes out), named (what the brand in force says: the project's own or the theme's suggestion),
		 *	          suggested, defaults (what Nino.css says where nothing else does), unavailable (the fonts it names that no folder has), the stored brand and the slugs of the fonts there are
		 *	palette   source - brand, or reset where the brand names no colour and the reset's colours stand -, veil (the ground of a picture under a section), colors and the contrast of
		 *	          every pair a part reads
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$plan					As Compiler::plan() answers it
		 *	@param		array			$brand				As Brand::clean() answers it: the brand of the draft or the stored one
		 *
		 *	@return 	array											'groups', 'brand', 'palette', 'notes'
		 */
		private static function _describe( array &$appData, array $plan, array $brand ): array {

			$read = Reader::read( $appData );
			$schemas = \Nino\Components::components( $appData );
			$groups = [];

			foreach( [ 'page' => $plan['page'] ] + $plan['groups'] as $group => $entry ) {

				$installed = $entry['theme'] !== '' ? ( $read['themes'][$entry['theme']]['version'] ?? '' ) === $entry['version'] : false;
				$groups[$group] = [
					'label'			=> $group === 'page' ? '' : $entry['label'],
					'from'			=> $entry['from'],
					'theme'			=> $entry['theme'],
					'version'		=> $entry['version'],
					'origin'		=> $entry['origin'],
					'package'		=> $entry['theme'] === '' ? '' : ( $installed === true ? 'installed' : 'missing' ),
					'problem'		=> $entry['problem'],
					'why'				=> Reader::explain( $entry['problem'] ),
					'missing'		=> $entry['missing'],
					'knobs'			=> [],
					'components'	=> [],
					'offers'		=> [],
				];

				foreach( $entry['knobs'] as $key => $knob )
					$groups[$group]['knobs'][] = [ 'key' => $key, 'label' => $knob['label'], 'extra' => $knob['extra'] ?? false, 'small' => $knob['small'] ?? false, 'step' => $knob['step'], 'delivered' => $knob['delivered'] ?? 0, 'default' => $knob['ladder'][2], 'ladder' => $knob['ladder'] ];

				foreach( $entry['variants'] ?? [] as $component => [ $added, $standard ] ) {

					$variants = [];

					foreach( (array) ( $schemas[$component]['variants'] ?? [ 'regular' ] ) as $name )
						$variants[] = [ 'name' => $name, 'label' => null, 'source' => 'schema' ];

					foreach( $added as $name )
						$variants[] = [ 'name' => $name, 'label' => $entry['part']['variants'][$component][$name] ?? null, 'source' => 'part' ];

					$groups[$group]['components'][] = [ 'name' => $component, 'label' => $schemas[$component]['label'] ?? $component, 'variants' => $variants, 'standard' => $standard ];
				}

				foreach( $read['themes'] as $key => $offered )
					if( isset( $offered['parts'][$group] ) === true )
						$groups[$group]['offers'][] = [ 'theme' => $key, 'version' => $offered['version'], 'ok' => $offered['parts'][$group]['ok'], 'problem' => $offered['parts'][$group]['problem'], 'why' => Reader::explain( $offered['parts'][$group]['problem'] ), 'values' => (array) ( $offered['parts'][$group]['part']['values'] ?? [] ) ];
			}

			$values = $plan['brand'];

			if( $plan['palette'] === true ) {
				$source = 'brand';
				$palette = Palette::make( $values['color-1'], $values['color-2'], $values['tone'] );
				$palette['color-1'] = $values['color-1'];
				$palette['color-2'] = $values['color-2'];
			}
			else {
				$source = 'reset';
				$palette = Brand::reset( $appData );
			}

			// The fonts the brand names that no folder has: they stay the value, and the stylesheet draws the stack of the system for them
			$fonts = Brand::fonts( $appData );
			$unavailable = array_values( array_filter( [ 'font-title', 'font-text', 'font-button' ], static fn( string $key ): bool => in_array( $key, $plan['named'], true ) === true && isset( $fonts[$values[$key]] ) === false ) );

			// The maps of the answer stay maps where they are empty: a group whose theme brings no values, a page that suggests no brand, a knob of the page whose step sets no token
			return self::_objects( [
				'groups'	=> $groups,
				'brand'		=> [ 'stored' => $brand, 'effective' => $values, 'named' => $plan['named'], 'suggested' => $plan['page']['brand'], 'defaults' => Brand::DEFAULTS, 'unavailable' => $unavailable, 'fonts' => array_keys( $fonts ) ],
				'palette'	=> [ 'source' => $source, 'veil' => Palette::VEIL, 'colors' => $palette, 'contrast' => $palette === [] ? [] : array_map( static fn( float $ratio ): float => floor( $ratio * 100 + 1e-9 ) / 100, Palette::pairs( $palette, $palette['color-1'], $palette['color-2'] ) ) ],
				'notes'		=> $plan['notes'],
			], [ 'groups.*.offers.*.values', 'groups.*.knobs.*.default', 'groups.*.knobs.*.ladder.*', 'brand.stored', 'brand.effective', 'brand.suggested', 'palette.colors', 'palette.contrast' ] );
		}

		/**
		 *	An answer with its empty maps as objects: PHP has no empty map, json_encode() writes [] for one, which a client takes for a list. The paths name the maps - dotted,
		 *	a * for every key of a list or of a map - and an array there that is empty is made an object; what is not empty, or not an array, is left as it is
		 *
		 *	@param		array			$data					The answer, or a part of it
		 *	@param		array			$paths				Dotted paths, eg. 'groups.*.offers.*.values'
		 *
		 *	@return 	array
		 */
		private static function _objects( array $data, array $paths ): array {

			foreach( $paths as $path )
				$data = self::_object( $data, explode( '.', $path ) );

			return $data;
		}

		private static function _object( mixed $node, array $path ): mixed {

			if( $path === [] )
				return $node === [] ? new \stdClass() : $node;

			if( is_array( $node ) === false )
				return $node;

			$head = array_shift( $path );

			foreach( $node as $key => $child )
				if( $head === '*' || (string) $key === $head )
					$node[$key] = self::_object( $child, $path );

			return $node;
		}

		/**
		 *	What a save is made of: the mix of an own theme, checked, and the brand where one is posted
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$data					The posted data
		 *
		 *	@return 	array											'mix' => ..., 'brand' => ... (where posted), or 'failure'
		 */
		private static function _prepare( array &$appData, array $data, array $keep ): array {

			$mix = Mix::clean( $appData, $data['mix'] ?? null, true );

			if( is_string( $mix ) === true )
				return [ 'failure' => [ 400, 'the mix is refused: '. $mix, 'themes_mix', [ $mix, Mix::where( $mix ) ], in_array( strtok( $mix, ':' ), [ 'name', 'css' ], true ) === true ? (string) strtok( $mix, ':' ) : 'mix' ] ];

			// The css of a theme is held to the rule of a theme, as a package's is: what breaks it is not written, and every line that does is said at the field
			if( ( $rule = Mix::cssRule( (string) ( $mix['css'] ?? '' ) ) ) !== [] )
				return [ 'failure' => [ 400, 'the css breaks the rule of a theme: '. implode( '; ', $rule ), 'themes_css_rule', [ implode( '; ', $rule ) ], 'css' ] ];

			if( array_key_exists( 'brand', $data ) === false )
				return [ 'mix' => $mix ];

			$brand = Brand::clean( $appData, $data['brand'], $keep );

			return is_string( $brand ) === true ? [ 'failure' => [ 400, 'the brand is refused at '. self::_shown( $brand ), 'themes_brand', [ self::_shown( $brand ) ], 'brand' ] ] : [ 'mix' => $mix, 'brand' => $brand ];
		}

		/**
		 *	Write an own theme - its folder - and the brand where there is one, into a state being made. The folder is settled by the store when the change is done
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		string		$key					The key of the own theme
		 *	@param		array			$prepared			As _prepare() answers it
		 *	@param		array|null								$base					The theme the folder is made of or made over, as the reader has read it
		 *	@param		bool			$over					Whether the folder of the theme is replaced
		 *
		 *	@return 	array|null
		 */
		private static function _write( array &$appData, string $next, string $key, array $prepared, ?array $base, bool $over ): ?array {

			$done = Mix::write( $appData, $key, $prepared['mix'], $base, $over );

			if( is_string( $done ) === true )
				return [ 500, 'the theme could not be written: '. $done, 'themes_write', [ self::_shown( $done ) ], '' ];

			if( isset( $prepared['brand'] ) === true && \Nino\Filesystem::putFileContent( $appData, $next. '/brand.json', $prepared['brand'] ) === false )
				return [ 500, 'the state could not be written', '', [], '' ];

			return null;
		}

		/**
		 *	The room for a new own theme: its name is new, there is room for one more, and the key it is made under - the slug of its name, behind a letter where it has none, or the
		 *	next free one - a key is taken by any entry of the folder, whatever it is: a folder the reader refuses, a link, a file
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The name of the new theme
		 *	@param		string		&$made				(reference) The key
		 *
		 *	@return 	array|null								The failure, or null
		 */
		private static function _room( array &$appData, string $name, string &$made ): ?array {

			$names = self::_ownNames( $appData );

			foreach( $names as $other )
				if( self::_sameName( $name, $other ) === true )
					return [ 409, 'an own theme of that name is there', 'themes_name_taken', [ $other ], 'name' ];

			// A folder that the reader refuses is a folder all the same: there is no room for a theme where the folders are as many as there may be
			$own = Reader::own( $appData, true );

			if( count( $own['themes'] ) + count( $own['rejected'] ) >= Store::MAX_OWN )
				return [ 409, 'there are as many own themes as there may be', 'themes_limit', [ Store::MAX_OWN ], '' ];

			$base_key = \Nino\Feature::slug( $name, \Nino\Components::NAME_LENGTH );

			if( $base_key === '' )
				return [ 400, 'the name has no letter or digit to make a key of', 'themes_name', [], 'name' ];

			// A key begins with a letter: a name that begins with a digit - a year, a number - has the key of its slug behind a letter
			if( preg_match( \Nino\Components::NAME, $base_key ) !== 1 )
				$base_key = rtrim( substr( 't-'. $base_key, 0, \Nino\Components::NAME_LENGTH ), '-' );

			if( preg_match( \Nino\Components::NAME, $base_key ) !== 1 )
				return [ 400, 'the name has no letter or digit to make a key of', 'themes_name', [], 'name' ];

			$dir = Reader::ownDir( $appData );
			$made = $base_key;

			for( $count = 2; isset( $names[$made] ) === true || file_exists( $dir. '/'. $made ) === true || is_link( $dir. '/'. $made ) === true; $count++ )
				$made = substr( $base_key, 0, \Nino\Components::NAME_LENGTH - strlen( '-'. $count ) ). '-'. $count;

			return null;
		}

		/**
		 *	Make a new own theme in a state being made: its name must be new, there must be room for it, and its key is the name's - or the next free one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		array			$prepared			As _prepare() answers it
		 *	@param		array|null								$base					The theme it is made of, as the reader has read it
		 *	@param		string		&$made				(reference) The key it was made under
		 *
		 *	@return 	array|null								The failure, or null
		 */
		private static function _create( array &$appData, string $next, array $prepared, ?array $base, string &$made ): ?array {

			$failure = self::_room( $appData, (string) $prepared['mix']['name'], $made );

			if( $failure !== null )
				return $failure;

			return self::_write( $appData, $next, $made, $prepared, $base, false );
		}

		/**
		 *	Answer what a change came to
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) The request
		 *	@param		string		$theme				The key of the theme it was about
		 *	@param		array|null								$failure			What Store::commit() answered
		 *
		 *	@return 	void
		 */
		private static function _finish( array &$appData, array &$request, string $theme, ?array $failure ): void {

			if( $failure !== null ) {
				self::_fail( $request, $failure );
				return;
			}

			$state = Store::read( $appData );
			\Nino\Preview::tidy( $appData );

			\Nino\Http::ok( $request, [ 'saved' => true, 'theme' => $theme, 'applied' => ( $state['active']['theme'] ?? '' ) === $theme, 'stamp' => str_starts_with( $theme, 'own:' ) === true && isset( $state['own'][substr( $theme, 4 )] ) === true ? self::_ownStamp( $appData, substr( $theme, 4 ) ) : '' ] + self::_state( $appData, $state ) );
		}

		/**
		 *	A mix as it goes over the wire: the parts are a map, and an empty one stays a map - PHP has no empty map, json_encode() writes [] for it, which a
		 *	client takes for a list and sends no parts with the next save
		 *
		 *	@param		array			$mix					An own theme or a theme as a mix
		 *
		 *	@return 	array
		 */
		private static function _wired( array $mix ): array {

			return self::_objects( [ 'mix' => $mix ], [ 'mix.parts' ] )['mix'];
		}

		/**
		 *	The stamp of a map of the project - its brand: what a change of it, by this tab or by another, is told from. The order of the keys is not part of
		 *	it. The stamp of an own theme is the one of its folder (_ownStamp())
		 *
		 *	@param		array			$mix					The map
		 *
		 *	@return 	string										A sha256
		 */
		private static function _stamp( array $mix ): string {

			$sort = static function( mixed $value ) use ( &$sort ): mixed {

				if( is_array( $value ) === false )
					return $value;

				ksort( $value );

				return array_map( $sort, $value );
			};

			return hash( 'sha256', (string) json_encode( $sort( $mix ) ) );
		}

		/**
		 *	The stamp of the folder of an own theme: what its files say, as the reader has them - a theme that was saved here and one that somebody changed by hand are told from the one that was
		 *	opened by it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key of the own theme
		 *
		 *	@return 	string										A sha256, '' for a theme that is not there
		 */
		private static function _ownStamp( array &$appData, string $key ): string {
			return (string) ( Reader::own( $appData )['themes'][$key]['stamp'] ?? '' );
		}

		/**
		 *	What is answered where the stamp is not the one of the theme
		 *
		 *	@param		string		$name					The name of the theme, its key where it is gone
		 *
		 *	@return 	array											As Store::commit() answers a failure
		 */
		private static function _changedFailure( string $name ): array {
			return [ 409, 'the theme was changed since it was opened', 'themes_changed', [ $name ], 'theme' ];
		}

		/**
		 *	Whether the theme a request is about was changed since the one who asks opened it: the stamp it posts as base is not the one the theme has now. A request that posts
		 *	none has not seen the theme. The one who asks takes the new stamp from open to write over it
		 *
		 *	@param		string		$stamp				The stamp of the theme as it is
		 *	@param		array			$data					The posted data
		 *
		 *	@return 	bool
		 */
		private static function _changed( string $stamp, array $data ): bool {
			return is_string( $data['base'] ?? null ) === false || hash_equals( $stamp, $data['base'] ) === false;
		}

		/**
		 *	The refusal for a brand that was changed since the one who asks saw it, looked at in the state that is being made, with the lock held
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		array			$data					The posted data
		 *
		 *	@return 	array|null								The failure, or null
		 */
		private static function _brandFailure( array &$appData, string $next, array $data ): ?array {

			$stored = Brand::read( $appData, \Nino\Filesystem::getFileContent( $appData, $next. '/brand.json', [] ) );

			return self::_brandChanged( $stored, $data ) === true ? [ 409, 'the brand was changed since it was opened', 'themes_brand_changed', [], 'brand' ] : null;
		}

		/**
		 *	The own themes there are, by key, with their names, read as they are with the lock held
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											key => name
		 */
		private static function _ownNames( array &$appData ): array {
			return array_map( static fn( array $theme ): string => (string) $theme['label'], Reader::own( $appData, true )['themes'] );
		}

		/**
		 *	Whether the brand was changed since the one who asks saw it: the stamp it posts as brandBase is not the one the brand has now
		 *
		 *	@param		array			$brand				As Store::read() answers it
		 *	@param		array			$data					The posted data
		 *
		 *	@return 	bool
		 */
		private static function _brandChanged( array $brand, array $data ): bool {
			return is_string( $data['brandBase'] ?? null ) === false || hash_equals( self::_stamp( $brand ), $data['brandBase'] ) === false;
		}

		/**
		 *	Whether a name is the one a card has: names are told as their keys are, which fold case and marks. A key that is taken and a name that no
		 *	card carries is no collision - the theme that has the key is called something else
		 *
		 *	@param		string		$name					The name that is asked for
		 *	@param		string		$otherName		The name of the other theme
		 *
		 *	@return 	bool
		 */
		private static function _sameName( string $name, string $otherName ): bool {

			$key = \Nino\Feature::slug( $name, \Nino\Components::NAME_LENGTH );

			return $key !== '' && $key === \Nino\Feature::slug( $otherName, \Nino\Components::NAME_LENGTH );
		}

		/**
		 *	One theme as a mix, by the key a person gives it: the key of a theme that is installed, or own:<key>
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$state				As Store::read() answers it
		 *	@param		string		$theme
		 *
		 *	@return 	array											'key', 'kind', 'name', 'mix'; or 'failure' => [ status, message, code, params, field ]
		 */
		private static function _theme( array &$appData, array $state, string $theme ): array {

			if( str_starts_with( $theme, 'own:' ) === true ) {

				$key = substr( $theme, 4 );

				if( isset( $state['own'][$key] ) === true )
					return [ 'key' => $theme, 'kind' => 'own', 'name' => (string) ( $state['own'][$key]['name'] ?? $key ), 'mix' => $state['own'][$key] ];
			}
			else if( ( $mix = Mix::of( $appData, $theme ) ) !== null )
				return [ 'key' => $theme, 'kind' => 'theme', 'name' => Reader::read( $appData )['themes'][$theme]['name'], 'mix' => $mix ];

			return [ 'failure' => [ 404, 'there is no such theme', 'themes_unknown', [ self::_named( $theme ) ], 'theme' ] ];
		}

		/**
		 *	What a person needs to know of the state after any answer: the theme the website shows and whether its stylesheet is
		 *	the one the current register would make, whether the stylesheet of the project is one this feature did not write, and
		 *	whether there is a last state
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$state				As Store::read() answers it
		 *
		 *	@return 	array											'active' => [ 'theme', 'built', 'stale', 'back' (the fonts that are there again, by the key of the brand: the stylesheet has the stack of the system for them until it is built again) ] or null, 'last' => when the last state was saved and which theme the website showed in it, or null, 'foreign' => bool, 'unreadable' => bool (the file is there and cannot be read: it is somebody else's), 'canRestore' => bool, 'state' => the stamp of the
		 *																state, which a restore is asked against, 'brandStamp' => the stamp of the brand
		 */
		private static function _state( array &$appData, array $state ): array {

			$active = $state['active'];
			$file = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::SHEET );
			$text = Store::sheet( $file );
			$back = [];
			$drifted = Typefaces::drifted( $appData, (string) $text );

			// A font that had no folder when the stylesheet was built has the stack of the system in it; the folder that is there again is not in the
			// stylesheet before the next build
			if( $active !== null && is_array( $active['gone'] ?? null ) === true && $active['gone'] !== [] ) {

				$fonts = Brand::fonts( $appData );

				foreach( $active['gone'] as $key => $slug )
					if( in_array( $key, [ 'font-title', 'font-text', 'font-button' ], true ) === true && is_string( $slug ) === true && isset( $fonts[$slug] ) === true )
						$back[$key] = $slug;
			}

			return [
				'active'		=> $active === null ? null : [
					'theme'		=> $active['theme'],
					'built'		=> (string) ( $active['built'] ?? '' ),
					'stale'		=> ( $active['feature'] ?? '' ) !== (string) ( \Nino\Features::get( $appData, 'themes' )['version'] ?? '' ) || ( $active['register'] ?? '' ) !== \Nino\Components::version( $appData ) || $back !== [] || $drifted === true,
					// The stylesheet names files of a font that its folder has not: the list says that, and not that the feature changed
					'fonts'		=> $drifted,
					// The fonts that are there again, by the key of the brand: a map, and an empty one goes out as an object
					'back'		=> $back === [] ? new \stdClass() : $back,
				],
				'foreign'		=> $text === false || ( $text !== null && $text !== '' && Compiler::isOwn( $text ) === false ),
				'unreadable'	=> $text === false,
				'canRestore'	=> $state['previous'],
				'last'			=> $state['previous'] === true ? Store::last( $appData ) : null,
				'state'			=> Store::stamp( $appData ),
				'current'		=> [ 'theme' => $active === null ? '' : $active['theme'], 'name' => $state['held']['name'] ?? '', 'origin' => $state['origin'] === [] ? new \stdClass() : $state['origin'] ],
				'brandStamp'	=> self::_stamp( $state['brand'] ),
			];
		}

		/**
		 *	Answer a failure of the store, a theme or an input
		 *
		 *	@param		array 		&$request			(reference) The request
		 *	@param		array			$failure			[ status, message, code, params, field ]
		 *
		 *	@return 	void
		 */
		private static function _fail( array &$request, array $failure ): void {
			\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
		}

		/**
		 *	Take away the folder of the drafts this feature kept itself: the folders in the form of a token that it made, no link and nothing else; the
		 *	folder goes with them where nothing else is in it, and the list does not look again once it is gone
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _dropOld( array &$appData ): void {

			$base = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DRAFTS );

			if( is_dir( $base ) === false || is_link( $base ) === true )
				return;

			foreach( (array) glob( $base. '/*', GLOB_ONLYDIR ) as $folder )
				if( preg_match( \Nino\Preview::TOKEN, basename( (string) $folder ) ) === 1 && is_link( (string) $folder ) === false )
					\Nino\Filesystem::removeDir( (string) $folder );

			@rmdir( $base );
		}

		/**
		 *	A theme as a sentence names it: its key, without the prefix the API puts before an own one, in the form of a word of a request
		 *
		 *	@param		string		$theme
		 *
		 *	@return 	string
		 */
		private static function _named( string $theme ): string {
			return self::_shown( str_starts_with( $theme, 'own:' ) === true ? substr( $theme, 4 ) : $theme );
		}

		/**
		 *	A word of a request as it may stand in a sentence or the log: short, one line
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string
		 */
		private static function _shown( string $text ): string {
			return mb_substr( (string) preg_replace( '/[\x00-\x1F\x7F]/', '?', $text ), 0, 60 );
		}
	}
}
