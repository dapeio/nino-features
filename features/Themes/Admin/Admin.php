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
	//	themes/save			the mix of an own theme, and the brand where it is posted
	//	themes/brand		the brand alone: the project's, whichever theme is open
	//	themes/save-as	a new own theme from a draft
	//	themes/duplicate	a new own theme from a theme as it is saved, its mix and its own css; the brand is the project's and is not copied
	//	themes/apply		the website shows a theme
	//	themes/restore	the last state in place of this one, and this one in place of the last - asked with the state it was seen in
	//	themes/delete		an own theme
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
				'themes/restore'	=> [ self::class, 'apiRestore' ],
				'themes/delete'		=> [ self::class, 'apiDelete' ],
			];
		}

		public static function nav(): array {
			return [ 'themes', '/_admin/themes/nav', 60 ];
		}

		// The one tab the panel brings: the fonts of the project (see Typefaces). The tab of the settings, which the registry attaches to a
		// feature that declares some, comes after it
		public static function tabs(): array {
			return [ Typefaces::class ];
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
				'themes/restore'	=> 'Restore Last Theme State',
				'themes/delete'		=> 'Delete Theme '. self::_shown( \Nino\Admin\Admin::postString( $data, 'theme' ) ),
				default						=> '',
			};
		}

		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$read = Reader::read( $appData );
			$state = Store::read( $appData );
			$themes = [];

			foreach( $read['themes'] as $key => $theme ) {

				$parts = [];

				foreach( $theme['parts'] as $group => $part )
					$parts[$group] = [ 'ok' => $part['ok'], 'problem' => $part['problem'] ];

				$themes[] = [ 'key' => $key, 'kind' => 'theme', 'version' => $theme['version'], 'name' => $theme['name'], 'description' => $theme['description'], 'brand' => $theme['brand'], 'parts' => $parts, 'active' => ( $state['active']['theme'] ?? '' ) === $key ];
			}

			// An own theme says where each of its parts comes from, so that a card can say what it is made of
			foreach( $state['own'] as $key => $mix )
				$themes[] = [ 'key' => 'own:'. $key, 'kind' => 'own', 'name' => (string) ( $mix['name'] ?? $key ), 'from' => array_map( static fn( array $entry ): string => (string) ( $entry['from'] ?? 'standard' ), (array) ( $mix['parts'] ?? [] ) ), 'stamp' => self::_stamp( $mix ), 'active' => ( $state['active']['theme'] ?? '' ) === 'own:'. $key ];

			$groups = [];

			foreach( \Nino\Components::groups( $appData ) as $group => $declared )
				$groups[] = [ 'key' => $group, 'label' => $declared['label'], 'feature' => $declared['feature'], 'components' => $declared['components'], 'demo' => $declared['demo'] !== '' ];

			$rejected = [];

			foreach( $read['rejected'] as $folder => $reason )
				$rejected[] = [ 'folder' => basename( (string) $folder ), 'reason' => $reason ];

			$fonts = [];

			foreach( Brand::fonts( $appData ) as $slug => $font )
				$fonts[] = [ 'key' => $slug, 'name' => $font['name'], 'system' => $font['system'] ];

			// The pages of the site a preview may show: the paths of the routes that answer html, as the kernel lists them
			$pages = [];

			foreach( \Nino\Http::pages( (array) ( $appData['/nino/http/routes'] ?? [] ) ) as $page )
				if( $page['html'] === true && $page['wildcard'] === false && $page['status'] < 400 && isset( $pages[$page['path']] ) === false )
					$pages[$page['path']] = [ 'path' => $page['path'], 'title' => trim( \Nino\Html::renderTextfill( $appData, '/_nino/webpage'. $page['uri']. '/title' ) ) ];

			self::_tidy( $appData );

			\Nino\Http::ok( $request, [ 'themes' => $themes, 'rejected' => $rejected, 'groups' => $groups, 'fonts' => $fonts, 'limits' => [ 'css' => Mix::CSS_LENGTH, 'name' => Mix::NAME_LENGTH, 'key' => \Nino\Components::NAME_LENGTH ], 'pages' => array_slice( array_values( $pages ), 0, self::PAGES ) ] + self::_state( $appData, $state ) );
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			$plan = Compiler::plan( $appData, $theme['mix'], $state['brand'] );

			\Nino\Http::ok( $request, [ 'theme' => [ 'key' => $theme['key'], 'kind' => $theme['kind'], 'name' => $theme['name'] ], 'mix' => $theme['mix'], 'stamp' => $theme['kind'] === 'own' ? self::_stamp( $theme['mix'] ) : '' ] + self::_describe( $appData, $plan, $state['brand'] ) + self::_state( $appData, $state ) );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$mix = Mix::clean( $appData, $data['mix'] ?? null, false );

			if( is_string( $mix ) === true ) {
				\Nino\Http::fail( $request, 400, 'the mix is refused: '. $mix, 'themes_mix', [ $mix ], 'mix' );
				return;
			}

			$brand = array_key_exists( 'brand', $data ) === true ? Brand::clean( $appData, $data['brand'], $state['brand'] ) : $state['brand'];

			if( is_string( $brand ) === true ) {
				\Nino\Http::fail( $request, 400, 'the brand is refused at '. $brand, 'themes_brand', [ $brand ], 'brand' );
				return;
			}

			$built = Compiler::compile( $appData, $mix, $brand );
			$made = self::_draft( $appData, $built, \Nino\Admin\Admin::postString( $data, 'token' ), \Nino\Modules\Themes::SESSION, 5 );

			if( $made === null ) {
				\Nino\Http::fail( $request, 500, 'the preview could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $made['token'], 'query' => \Nino\Modules\Themes::QUERY. '='. $made['token'], 'expires' => $made['expires'], 'ttl' => \Nino\Modules\Themes::TTL, 'cssFaults' => $built['faults'] ] + self::_describe( $appData, $built['plan'], $brand ) );
		}

		public static function apiThumb( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
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
				\Nino\Http::fail( $request, 409, 'the mix is refused: '. $mix, 'themes_mix', [ $mix ], 'mix' );
				return;
			}

			$made = self::_draft( $appData, Compiler::compile( $appData, $mix, $state['brand'] ), \Nino\Admin\Admin::postString( $data, 'token' ), \Nino\Modules\Themes::THUMBS, \Nino\Modules\Themes::THUMBS_MAX );

			if( $made === null ) {
				\Nino\Http::fail( $request, 500, 'the preview could not be written' );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $made['token'], 'query' => \Nino\Modules\Themes::QUERY. '='. $made['token'], 'expires' => $made['expires'], 'ttl' => \Nino\Modules\Themes::TTL ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
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

			// The stamp of the theme and of the brand and the name are looked at with the lock held, on the state that is being copied: a request that read
			// the state before another one wrote it is told so as well. The stamps and the stylesheet that is not the feature's are separate answers, and
			// force is only for the last
			self::_finish( $appData, $request, $theme, Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $prepared, $data ): ?array {

				$current = \Nino\Filesystem::getFileContent( $appData, $next. '/own/'. $key. '.json', [] );

				if( is_array( $current ) === false || self::_changed( $current, $data ) === true )
					return self::_changedFailure( is_array( $current ) === true ? $current : [], $key );

				if( isset( $prepared['brand'] ) === true && ( $failure = self::_brandFailure( $appData, $next, $data ) ) !== null )
					return $failure;

				// A name that another own theme has - or whose key it is - is no rename: two cards would carry one name
				foreach( self::_ownNames( $appData, $next ) as $other => $name )
					if( (string) $other !== $key && self::_sameName( (string) $prepared['mix']['name'], $name ) === true )
						return [ 409, 'an own theme of that name is there', 'themes_name_taken', [ $name ], 'name' ];

				return self::_write( $appData, $next, $key, $prepared );
			}, ( $data['force'] ?? false ) === true ) );
		}

		public static function apiSaveAs( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
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

				return self::_create( $appData, $next, $prepared, $made );
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		public static function apiDuplicate( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			// The mix to copy is the one the state has with the lock held: for an own theme the stamp says it is the one the person saw, a theme of a
			// package is the mix it is at that time. The name is the new one; the brand is the project's, so it is not part of it
			$made = '';
			$key = $theme['key'];
			$name = \Nino\Admin\Admin::postString( $data, 'name' );
			$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( &$made, $key, $theme, $name, $data ): ?array {

				$mix = $theme['mix'];

				if( $theme['kind'] === 'own' ) {

					$current = \Nino\Filesystem::getFileContent( $appData, $next. '/own/'. substr( $key, 4 ). '.json', [] );

					if( is_array( $current ) === false || self::_changed( $current, $data ) === true )
						return self::_changedFailure( is_array( $current ) === true ? $current : [], substr( $key, 4 ) );

					$mix = $current;
				}

				$mix['name'] = $name;
				$prepared = self::_prepare( $appData, [ 'mix' => $mix ], [] );

				if( isset( $prepared['failure'] ) === true )
					return $prepared['failure'];

				return self::_create( $appData, $next, $prepared, $made );
			}, ( $data['force'] ?? false ) === true );

			self::_finish( $appData, $request, 'own:'. $made, $failure );
		}

		public static function apiBrand( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
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

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$state = Store::read( $appData );
			$theme = self::_theme( $appData, $state, \Nino\Admin\Admin::postString( $data, 'theme' ) );

			if( isset( $theme['failure'] ) === true ) {
				self::_fail( $request, $theme['failure'] );
				return;
			}

			$key = $theme['key'];

			// A theme of a package is applied as the mix it is at that time, kept in the state: the site does not hang on the package. An own theme
			// is applied as the one who asks saw it - the stamp is looked at with the lock held, as it is at a save, and force is no answer to it
			self::_finish( $appData, $request, $key, Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $theme, $data ): ?array {

				if( $theme['kind'] === 'own' ) {

					$current = \Nino\Filesystem::getFileContent( $appData, $next. '/own/'. substr( $key, 4 ). '.json', [] );

					if( is_array( $current ) === false || self::_changed( $current, $data ) === true )
						return self::_changedFailure( is_array( $current ) === true ? $current : [], substr( $key, 4 ) );
				}

				$active = [ 'theme' => $key ] + ( $theme['kind'] === 'theme' ? [ 'mix' => $theme['mix'] ] : [] );

				return \Nino\Filesystem::putFileContent( $appData, $next. '/active.json', $active ) === true ? null : [ 500, 'the state could not be written', '', [], '' ];
			}, ( $data['force'] ?? false ) === true ) );
		}

		public static function apiRestore( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();

			// The state the one who asks has seen is looked at by the store with its lock held; a request that names none has seen none
			$failure = Store::restore( $appData, ( $data['force'] ?? false ) === true, is_string( $data['base'] ?? null ) === true ? $data['base'] : '-' );

			if( $failure !== null )
				self::_fail( $request, $failure );
			else {
				self::_tidy( $appData );
				\Nino\Http::ok( $request, [ 'restored' => true ] + self::_state( $appData, Store::read( $appData ) ) );
			}
		}

		public static function apiDelete( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
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

				$failure = Store::commit( $appData, static function( array &$appData, string $next ) use ( $key, $data ): ?array {

					$current = \Nino\Filesystem::getFileContent( $appData, $next. '/own/'. $key. '.json', [] );

					if( is_array( $current ) === false || self::_changed( $current, $data ) === true )
						return self::_changedFailure( is_array( $current ) === true ? $current : [], $key );

					@unlink( \Nino\Filesystem::path( $appData, $next. '/own/'. $key. '.json' ) );

					return null;
				}, ( $data['force'] ?? false ) === true );

				if( $failure !== null )
					self::_fail( $request, $failure );
				else {
					self::_tidy( $appData );
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
					'missing'		=> $entry['missing'],
					'knobs'			=> [],
					'components'	=> [],
					'offers'		=> [],
				];

				foreach( $entry['knobs'] as $key => $knob )
					$groups[$group]['knobs'][] = [ 'key' => $key, 'label' => $knob['label'], 'extra' => $knob['extra'] ?? false, 'small' => $knob['small'] ?? false, 'step' => $knob['step'], 'default' => $knob['ladder'][2], 'ladder' => $knob['ladder'] ];

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
						$groups[$group]['offers'][] = [ 'theme' => $key, 'version' => $offered['version'], 'ok' => $offered['parts'][$group]['ok'], 'problem' => $offered['parts'][$group]['problem'] ];
			}

			$values = $plan['brand'];

			if( $plan['palette'] === true ) {
				$source = 'brand';
				$palette = Palette::make( $values['color-1'], $values['color-2'], $values['tone'] );
				$palette['color-1'] = $values['color-1'];
			}
			else {
				$source = 'reset';
				$palette = Brand::reset( $appData );
			}

			// The fonts the brand names that no folder has: they stay the value, and the stylesheet draws the stack of the system for them
			$fonts = Brand::fonts( $appData );
			$unavailable = array_values( array_filter( [ 'font-title', 'font-text', 'font-button' ], static fn( string $key ): bool => in_array( $key, $plan['named'], true ) === true && isset( $fonts[$values[$key]] ) === false ) );

			return [
				'groups'	=> $groups,
				'brand'		=> [ 'stored' => $brand, 'effective' => $values, 'named' => $plan['named'], 'suggested' => $plan['page']['brand'], 'defaults' => Brand::DEFAULTS, 'unavailable' => $unavailable, 'fonts' => array_keys( $fonts ) ],
				'palette'	=> [ 'source' => $source, 'veil' => Palette::VEIL, 'colors' => $palette, 'contrast' => $palette === [] ? [] : array_map( static fn( float $ratio ): float => floor( $ratio * 100 + 1e-9 ) / 100, Palette::pairs( $palette, $palette['color-1'] ) ) ],
				'notes'		=> $plan['notes'],
			];
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
				return [ 'failure' => [ 400, 'the mix is refused: '. $mix, 'themes_mix', [ $mix ], in_array( strtok( $mix, ':' ), [ 'name', 'css' ], true ) === true ? (string) strtok( $mix, ':' ) : 'mix' ] ];

			if( array_key_exists( 'brand', $data ) === false )
				return [ 'mix' => $mix ];

			$brand = Brand::clean( $appData, $data['brand'], $keep );

			return is_string( $brand ) === true ? [ 'failure' => [ 400, 'the brand is refused at '. self::_shown( $brand ), 'themes_brand', [ self::_shown( $brand ) ], 'brand' ] ] : [ 'mix' => $mix, 'brand' => $brand ];
		}

		/**
		 *	Write an own theme, and the brand where there is one, into a state being made
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		string		$key					The key of the own theme
		 *	@param		array			$prepared			As _prepare() answers it
		 *
		 *	@return 	array|null
		 */
		private static function _write( array &$appData, string $next, string $key, array $prepared ): ?array {

			$wrote = \Nino\Filesystem::putFileContent( $appData, $next. '/own/'. $key. '.json', $prepared['mix'] );

			if( $wrote === true && isset( $prepared['brand'] ) === true )
				$wrote = \Nino\Filesystem::putFileContent( $appData, $next. '/brand.json', $prepared['brand'] );

			// The parts an own theme takes are the project's from the day it names them
			Store::keep( $appData, Compiler::plan( $appData, $prepared['mix'], [] ) );

			return $wrote === true ? null : [ 500, 'the state could not be written', '', [], '' ];
		}

		/**
		 *	Make a new own theme in a state being made: its name must be new, there must be room for it, and its key is the name's - or the next free one
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *	@param		array			$prepared			As _prepare() answers it
		 *	@param		string		&$made				(reference) The key it was made under
		 *
		 *	@return 	array|null								The failure, or null
		 */
		private static function _create( array &$appData, string $next, array $prepared, string &$made ): ?array {

			$names = self::_ownNames( $appData, $next );

			foreach( $names as $name )
				if( self::_sameName( (string) $prepared['mix']['name'], $name ) === true )
					return [ 409, 'an own theme of that name is there', 'themes_name_taken', [ $name ], 'name' ];

			if( count( $names ) >= Store::MAX_OWN )
				return [ 409, 'there are as many own themes as there may be', 'themes_limit', [ Store::MAX_OWN ], '' ];

			$base = \Nino\Feature::slug( (string) $prepared['mix']['name'], \Nino\Components::NAME_LENGTH );

			if( $base === '' )
				return [ 400, 'the name has no letter or digit to make a key of', 'themes_name', [], 'name' ];

			$made = $base;

			for( $count = 2; isset( $names[$made] ) === true; $count++ )
				$made = substr( $base, 0, \Nino\Components::NAME_LENGTH - strlen( '-'. $count ) ). '-'. $count;

			return self::_write( $appData, $next, $made, $prepared );
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
			self::_tidy( $appData );

			\Nino\Http::ok( $request, [ 'saved' => true, 'theme' => $theme, 'applied' => ( $state['active']['theme'] ?? '' ) === $theme, 'stamp' => str_starts_with( $theme, 'own:' ) === true && isset( $state['own'][substr( $theme, 4 )] ) === true ? self::_stamp( $state['own'][substr( $theme, 4 )] ) : '' ] + self::_state( $appData, $state ) );
		}

		/**
		 *	The stamp of a saved mix: what a change of it - by this tab or by another - is told from. The name and the own css are part of it
		 *	and the order of the keys is not
		 *
		 *	@param		array			$mix					An own theme as the state holds it
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
		 *	What is answered where the stamp is not the one of the theme
		 *
		 *	@param		array			$mix					The own theme as the state holds it, [] where it is gone
		 *	@param		string		$key
		 *
		 *	@return 	array											As Store::commit() answers a failure
		 */
		private static function _changedFailure( array $mix, string $key ): array {
			return [ 409, 'the theme was changed since it was opened', 'themes_changed', [ (string) ( $mix['name'] ?? $key ) ], 'theme' ];
		}

		/**
		 *	Whether the theme a request is about was changed since the one who asks opened it: the stamp it posts as base is not the one
		 *	the mix has now. A request that posts none has not seen the theme. The one who asks takes the new stamp from open to write over it
		 *
		 *	@param		array			$mix					The own theme as the state holds it
		 *	@param		array			$data					The posted data
		 *
		 *	@return 	bool
		 */
		private static function _changed( array $mix, array $data ): bool {
			return is_string( $data['base'] ?? null ) === false || hash_equals( self::_stamp( $mix ), $data['base'] ) === false;
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
		 *	The own themes of a state that is being made, by key, with their names
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$next					The virtual path of the state
		 *
		 *	@return 	array											key => name
		 */
		private static function _ownNames( array &$appData, string $next ): array {

			$names = [];

			foreach( (array) glob( \Nino\Filesystem::path( $appData, $next. '/own' ). '/*.json' ) as $file ) {

				$key = basename( (string) $file, '.json' );
				$mix = \Nino\Filesystem::getFileContent( $appData, $next. '/own/'. $key. '.json', [] );
				$names[$key] = (string) ( is_array( $mix ) === true ? ( $mix['name'] ?? $key ) : $key );
			}

			return $names;
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
					'back'		=> $back,
				],
				'foreign'		=> $text === false || ( $text !== null && $text !== '' && Compiler::isOwn( $text ) === false ),
				'unreadable'	=> $text === false,
				'canRestore'	=> $state['previous'],
				'last'			=> $state['previous'] === true ? Store::last( $appData ) : null,
				'state'			=> Store::stamp( $appData ),
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
		 *	Write a draft - the stylesheet and the variants of a mix as they were compiled, and whose it is - and hold its token for the session: the token
		 *	the session had for it where it still holds, else a new one. The session keeps the newest of its tokens and the folders of the others go
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$built				As Compiler::compile() answers it
		 *	@param		string		$asked				The token the one who asks holds, '' for none
		 *	@param		string		$session			The key of the session that holds the tokens
		 *	@param		int				$most					How many tokens the session keeps
		 *
		 *	@return 	array|null								'token', 'expires'; null where the draft could not be written
		 */
		private static function _draft( array &$appData, array $built, string $asked, string $session, int $most ): ?array {

			$held = (array) \Nino\Runtime::getSessionValue( $appData, $session, [] );
			$token = isset( $held[$asked] ) === true && $held[$asked] > time() && preg_match( \Nino\Modules\Themes::TOKEN, $asked ) === 1 ? $asked : bin2hex( random_bytes( 16 ) );
			$user = \Nino\Auth::getCurrentUser( $appData );
			$expires = time() + \Nino\Modules\Themes::TTL;
			$dir = \Nino\Modules\Themes::DRAFTS. '/'. $token;

			if( \Nino\Filesystem::putFileContent( $appData, $dir. '/theme.css', $built['css'] ) === false || \Nino\Filesystem::putFileContent( $appData, $dir. '/variants.php', $built['variants'] ) === false
				|| \Nino\Filesystem::putFileContent( $appData, $dir. '/meta.json', [ 'user' => $user === false ? '' : $user['mail'], 'expires' => $expires ] ) === false )
				return null;

			// The tokens this session holds: those that have not run out, the newest ones
			$held = array_filter( $held, static fn( mixed $until ): bool => is_int( $until ) === true && $until > time() );
			unset( $held[$token] );
			$held[$token] = $expires;
			$kept = array_slice( $held, -$most, null, true );

			// The draft that falls out is not kept for nobody to ask for
			foreach( array_diff_key( $held, $kept ) as $dropped => $until )
				if( preg_match( \Nino\Modules\Themes::TOKEN, (string) $dropped ) === 1 )
					\Nino\Filesystem::removeDir( \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DRAFTS. '/'. $dropped ) );

			\Nino\Runtime::setSessionValue( $appData, $session, $kept );
			self::_tidy( $appData );

			return [ 'token' => $token, 'expires' => $expires ];
		}

		/**
		 *	Remove the drafts whose time is out - at every action that writes, and at the list: the drafts of this session that are in time stay
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		private static function _tidy( array &$appData ): void {

			$held = \Nino\Runtime::getSessionValue( $appData, \Nino\Modules\Themes::SESSION, [] );
			$thumbs = \Nino\Runtime::getSessionValue( $appData, \Nino\Modules\Themes::THUMBS, [] );

			self::_prune( $appData, array_merge( is_array( $held ) === true ? array_keys( $held ) : [], is_array( $thumbs ) === true ? array_keys( $thumbs ) : [] ) );
		}

		/**
		 *	Remove the drafts that no session holds or whose time is out. A folder is looked at only where its name is a token
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$keep					The tokens of the session that asked
		 *
		 *	@return 	void
		 */
		private static function _prune( array &$appData, array $keep ): void {

			$base = \Nino\Filesystem::path( $appData, \Nino\Modules\Themes::DRAFTS );

			foreach( (array) glob( $base. '/*', GLOB_ONLYDIR ) as $folder ) {

				$token = basename( (string) $folder );

				if( preg_match( \Nino\Modules\Themes::TOKEN, $token ) !== 1 || in_array( $token, $keep, true ) === true || is_link( (string) $folder ) === true )
					continue;

				$meta = \Nino\Filesystem::getFileContent( $appData, \Nino\Modules\Themes::DRAFTS. '/'. $token. '/meta.json', [] );

				if( is_array( $meta ) === false || ( $meta['expires'] ?? 0 ) <= time() )
					\Nino\Filesystem::removeDir( (string) $folder );
			}
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
