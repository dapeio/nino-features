<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The API of the workbench for the Builder, and the panel that draws its answers (assets/admin.js). Every action guards itself with the right
	// /_admin/builder/manage, and so does the entry in the rail: an account without it has no panel
	//
	//	builder/list			the pages and the two frames, the language of the view
	//	builder/open			one template: its tree, where each call reads its text, the words of its namespace in every language, its stamp
	//	builder/preview		a draft - a tree and words, not saved - as a token, the query that names it and the address of the page it is shown on
	//	builder/save			the tree and the words in one step: the last state, the words, the slots, the template
	//	builder/restore		the last state in place of this one, and this one in place of the last
	//	builder/blocks		the blocks of the dialog "Insert block": what \Nino\Blocks offers for a page, and what it left out for a feature that is off
	//	builder/block			one block as it is put in: its tree laid out, its texts in every language and where its sources are
	//	builder/tile			a draft of a page with one block in it, for the small picture of the block in the dialog
	class Admin {

		public static function perm(): string {
			return \Nino\Modules\Builder::PERM;
		}

		public static function actions(): array {

			return [
				'builder/list'		=> [ self::class, 'apiList' ],
				'builder/open'		=> [ self::class, 'apiOpen' ],
				'builder/preview'	=> [ self::class, 'apiPreview' ],
				'builder/save'		=> [ self::class, 'apiSave' ],
				'builder/restore'	=> [ self::class, 'apiRestore' ],
				'builder/blocks'	=> [ self::class, 'apiBlocks' ],
				'builder/block'		=> [ self::class, 'apiBlock' ],
				'builder/tile'		=> [ self::class, 'apiTile' ],
				'builder/offer'		=> [ self::class, 'apiOffer' ],
				'builder/notation'	=> [ self::class, 'apiNotation' ],
			];
		}

		/**
		 *	The permissions of the three steps for the roles tab: the area of each, and the one action that stands for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function scopes( array &$appData ): array {

			$areas = [];

			// The label of an area is shown as it is, and so the words are the ones of the language of the workbench; the label of an action is a fill key the roles tab
			// resolves itself
			foreach( [ 'content' => \Nino\Modules\Builder::CONTENT, 'design' => \Nino\Modules\Builder::DESIGN, 'develop' => \Nino\Modules\Builder::DEVELOP ] as $area => $perm )
				$areas[] = [
					'id'				=> $area,
					'label'			=> self::_word( $appData, 'scope/'. $area ),
					'perm'			=> \Nino\Modules\Builder::SCOPE. $area. '/*',
					'actions'		=> [ [ 'id' => 'edit', 'label' => '/_admin/builder/scope/'. $area. '-edit', 'perm' => $perm ] ],
				];

			return [ [ 'scope' => \Nino\Modules\Builder::SCOPE, 'door' => self::perm(), 'label' => '/_admin/builder/nav', 'areas' => $areas ] ];
		}

		/**
		 *	A word of the panel in the language of the workbench, for a place that shows what it is given as it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key					The key below /_admin/builder/
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $key ): string {

			$locale = \Nino\Locales::getCurrentLocale( $appData );
			$file = dirname( __DIR__ ). '/text/'. ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/D', $locale ) === 1 && is_file( dirname( __DIR__ ). '/text/'. $locale. '.php' ) === true ? $locale : 'en_US' ). '.php';
			$words = require $file;

			return (string) ( $words['[[/_admin/builder/'. $key. ']]'] ?? $key );
		}

		public static function nav(): array {
			return [ 'builder', '/_admin/builder/nav', 61 ];
		}

		public static function template(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/templates/panel' );
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="6" rx="1"/><rect x="3" y="11" width="18" height="4" rx="1"/><rect x="3" y="17" width="18" height="4" rx="1"/></svg>';
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {

			return match( $action ) {
				'builder/save'		=> 'Save Page '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				'builder/restore'	=> 'Restore Last Page State '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				default						=> '',
			};
		}

		public static function apiList( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$frames = [];

			// The frames are written for every page: from the design on
			foreach( Store::FRAMES as $key => $template )
				if( $level >= \Nino\Modules\Builder::LEVEL_DESIGN && is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === true )
					$frames[] = [ 'key' => $key, 'template' => $template ];

			\Nino\Http::ok( $request, [ 'pages' => Pages::all( $appData, $locale ), 'frames' => $frames ] + self::_view( $appData, $locale, $level ) );
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, $nodes ] = $opened;
			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$registry = \Nino\Components::components( $appData );
			$page = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'page' );

			\Nino\Http::ok( $request, [
				'template'	=> $template,
				'frame'			=> Store::named( $template )['frame'],
				'tree'			=> $nodes,
				'sources'		=> Tree::sources( $nodes, $registry, $template ),
				'texts'			=> $state['texts'],
				'known'			=> Tree::known( $nodes, $registry ),
				'slots'			=> array_keys( $state['slots'] ),
				'stamp'			=> Store::stamp( $state ),
				'address'		=> self::_address( $appData, $template, $locale, $page ),
				'last'			=> self::_last( $appData, $template ),
			] + self::_more( $appData, $nodes, $registry, $level, $locale ) + self::_view( $appData, $locale, $level ) );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [], $data['inserted'] ?? [], $level );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$fills = [];

			// A text that is empty draws what the website would draw for it: the text of the next language that has one - the chain the fills of the kernel follow, the
			// native language first. Where no language has one, the key has no fill and the page draws nothing there
			$chain = array_diff( array_unique( array_merge( [ \Nino\Locales::getNativeLocale( $appData ) ], \Nino\Locales::getAvailableLocales( $appData ), \Nino\Locales::getTextLocales( $appData ) ) ), [ $locale ] );

			foreach( $prepared['texts'] as $key => $values ) {

				if( isset( $values[$locale] ) === false )
					continue;

				$text = trim( $values[$locale] ) !== '' ? $values[$locale] : '';

				foreach( $chain as $other )
					if( $text === '' && trim( (string) ( $values[$other] ?? '' ) ) !== '' )
						$text = $values[$other];

				if( $text !== '' )
					$fills[$key] = $text;
			}

			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Builder::KIND, self::perm(), [ 'template' => $template, 'source' => $prepared['draft'], 'fills' => $fills, 'slots' => $prepared['shown'], 'locale' => $locale ], \Nino\Admin\Admin::postString( $data, 'token' ) );

			if( $token === '' ) {
				self::_fail( $request, [ 500, 'the preview could not be written', 'builder_preview', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [
				'token'			=> $token,
				'query'			=> \Nino\Preview::QUERY. '='. $token,
				'ttl'				=> \Nino\Preview::TTL,
				'sources'		=> $prepared['sources'],
				'address'		=> self::_address( $appData, $template, $locale, \Nino\Admin\Admin::postString( $data, 'page' ) ),
				'findings'	=> $prepared['findings'],
				'notation'	=> $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? self::_notation( $appData, $prepared['tree'], $data['look'] ?? null ) : [],
			] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [], $data['inserted'] ?? [], $level );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			// A page that was changed since the one who asks opened it - by another tab, or in the panel of the texts - is not written over unless force says so
			if( ( $data['force'] ?? false ) !== true && ( is_string( $data['base'] ?? null ) === false || hash_equals( Store::stamp( $state ), $data['base'] ) === false ) ) {
				self::_fail( $request, self::_changed() );
				return;
			}

			$changes = Store::changes( $state, $prepared, $template );
			$target = [ 'source' => $prepared['source'], 'texts' => $state['texts'], 'slots' => array_diff_key( $state['slots'], array_flip( $changes['dropSlots'] ) ) + $prepared['slots'] ];

			// The words as they will stand: the old ones, the changed ones laid over them, the ones nothing reads taken away
			foreach( $changes['set'] as $key => $values )
				$target['texts'][$key] = $values + ( $state['texts'][$key] ?? [] );

			foreach( $changes['drop'] as $key )
				unset( $target['texts'][$key] );

			ksort( $target['texts'] );

			if( $target['source'] === $state['source'] && $changes['set'] === [] && $changes['drop'] === [] && $changes['dropSlots'] === [] && $prepared['slots'] === [] ) {
				\Nino\Http::ok( $request, [ 'saved' => true, 'changed' => false, 'stamp' => Store::stamp( $state ) ] + self::_after( $appData, $template, $level ) );
				return;
			}

			$failed = Store::commit( $appData, $template, $state, $target, $changes, $prepared['formats'], null, $prepared['files'], $level );

			if( $failed !== null ) {
				self::_fail( $request, $failed === 'changed' ? self::_changed() : [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] );
				return;
			}

			$now = Store::state( $appData, $template ) ?? $state;
			\Nino\Http::ok( $request, [ 'saved' => true, 'changed' => true, 'stamp' => Store::stamp( $now ), 'texts' => $now['texts'] ] + self::_after( $appData, $template, $level ) );
		}

		public static function apiRestore( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, $nodes ] = $opened;
			$previous = Store::previous( $appData, $template );

			if( $previous === null ) {
				self::_fail( $request, [ 409, 'there is no last state yet', 'builder_nothing', [], '' ] );
				return;
			}

			if( ( $data['force'] ?? false ) !== true && ( is_string( $data['base'] ?? null ) === false || hash_equals( Store::stamp( $state ), $data['base'] ) === false ) ) {
				self::_fail( $request, self::_changed() );
				return;
			}

			// What the last state brings back is held to the rule of the step of the account, as a save of it would be: for the content the page as it is must be the
			// last state with its blocks moved, copied or left out - that is how it came to be -; for the design and the development the last state is the tree that
			// is written, and pieces the account may not put in are not brought back. Only the words and the slots of the namespace of the page are written, whatever
			// the file of the last state says
			$registry = \Nino\Components::components( $appData );
			$back = Tree::read( $appData, $previous['source'] );

			// A last state that was kept by a save of this step or a lower one is that save taken back, and is the account's to swap: nobody is held to what they wrote
			// themselves. One that a higher step kept - or one that says no step is the content's - is held to the rule of the account as a save would be
			$wrote = is_int( $previous['level'] ?? null ) === true ? $previous['level'] : \Nino\Modules\Builder::LEVEL_CONTENT;

			if( $level >= \Nino\Modules\Builder::LEVEL_DESIGN ) {

				$taken = Tree::taken( $nodes, $registry, array_merge( array_keys( $state['texts'] ), array_keys( $state['slots'] ) ) );
				$why = $level >= $wrote ? null : Tree::designed( $appData, $nodes, $back, $registry, $taken, $level >= \Nino\Modules\Builder::LEVEL_DEVELOP );
			}
			else {

				$taken = Tree::taken( $back, $registry, array_merge( array_keys( $previous['texts'] ), array_keys( $previous['slots'] ) ) );
				$why = Tree::refused( $back, $nodes, $registry, $taken, Library::offers( $appData, $nodes, $taken ) );
			}

			if( $why !== null ) {

				$code = $level < \Nino\Modules\Builder::LEVEL_DEVELOP && in_array( $why, [ 'html', 'free', 'unit' ], true ) === true ? 'builder_restore_develop' : 'builder_refused_'. $why;
				self::_fail( $request, [ 403, 'the last state may not be put back: '. $why, $code, [], 'tree' ] );
				return;
			}

			$target = [
				'source'	=> $previous['source'],
				'texts'		=> array_filter( $previous['texts'], static fn( mixed $key ): bool => is_string( $key ) === true && Tree::own( $key, $template ) === true, ARRAY_FILTER_USE_KEY ),
				'slots'		=> array_filter( $previous['slots'], static fn( mixed $uri ): bool => is_string( $uri ) === true && Tree::own( $uri, $template ) === true, ARRAY_FILTER_USE_KEY ),
			];

			$failed = Store::commit( $appData, $template, $state, $target, null, [], null, [], $level );

			if( $failed !== null ) {
				self::_fail( $request, $failed === 'changed' ? self::_changed() : [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'restored' => true ] + self::_after( $appData, $template, $level ) );
		}

		public static function apiBlocks( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT ) === null )
				return;

			\Nino\Http::ok( $request, Library::all( $appData ) );
		}

		public static function apiBlock( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$block = Library::block( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' ) );

			if( $block === null ) {
				self::_fail( $request, [ 404, 'there is no such block', 'builder_block', [], 'key' ] );
				return;
			}

			// A person who designs gets the schemas of its components as well: the block is theirs to open at once
			if( $level >= \Nino\Modules\Builder::LEVEL_DESIGN )
				$block['schemas'] = Tree::schemas( $appData, $block['tree'], \Nino\Components::components( $appData ) );

			\Nino\Http::ok( $request, $block );
		}

		public static function apiTile( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template ] = $opened;
			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$tile = Store::named( $template )['frame'] === true ? null : Library::tile( $appData, $template, \Nino\Admin\Admin::postString( $data, 'key' ), $locale );

			if( $tile === null ) {
				self::_fail( $request, [ 404, 'there is no such block', 'builder_block', [], 'key' ] );
				return;
			}

			$token = \Nino\Preview::issue( $appData, Library::KIND, self::perm(), $tile, \Nino\Admin\Admin::postString( $data, 'token' ), Library::TILES );

			if( $token === '' ) {
				self::_fail( $request, [ 500, 'the preview could not be written', 'builder_preview', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'address' => self::_address( $appData, $template, $locale ) ] );
		}

		public static function apiNotation( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();

			if( self::_open( $appData, $request, $level ) === null )
				return;

			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );

			if( $canonical === null ) {
				self::_fail( $request, [ 400, 'the tree is none the Builder takes', 'builder_tree', [], 'tree' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'notation' => self::_notation( $appData, $canonical[1], $data['look'] ?? null ) ] );
		}

		public static function apiOffer( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );
			$at = \Nino\Admin\Admin::postString( $data, 'at' );
			$registry = \Nino\Components::components( $appData );
			$route = $canonical === null ? null : Tree::route( $canonical[1], $at );
			$container = $canonical === null ? null : Tree::at( $canonical[1], $at );

			if( $route === null || $container === null || isset( $registry[$route[count( $route ) - 1]]['offers'] ) === false ) {
				self::_fail( $request, [ 400, 'there is no place for a component there', 'builder_place', [], 'at' ] );
				return;
			}

			$groups = \Nino\Components::groups( $appData );
			$entries = [];
			$names = [];

			foreach( \Nino\Components::offer( $appData, $route, (array) ( $container['c'] ?? [] ) ) as $name => $answer ) {

				// The call of html is for the one who may develop and for nobody else: it is not offered, not even grey
				if( $answer === \Nino\Components::CODE_HTML ) {

					if( $level < \Nino\Modules\Builder::LEVEL_DEVELOP )
						continue;

					$answer = true;
				}

				$schema = $registry[$name];
				$names[] = (string) $name;
				$entries[] = [
					'name'				=> (string) $name,
					'label'				=> $schema['label'],
					'group'				=> (string) ( $schema['group'] ?? '' ),
					'groupLabel'	=> $groups[$schema['group'] ?? '']['label'] ?? null,
					'ok'					=> $answer === true,
					'why'					=> $answer === true ? '' : (string) $answer,
					'node'				=> [ 'n' => (string) $name, 's' => '', 'a' => [], 'c' => isset( $schema['offers'] ) === true ? [] : null ],
				];
			}

			// What stands between two calls at this depth, as the kernel writes a template: the blanks of a new call come from there
			$call = [ 'n' => 'spacer', 's' => '', 'a' => [], 'c' => null ];
			$laid = \Nino\Markup::layout( [ $call, $call ], count( $route ) );

			\Nino\Http::ok( $request, [ 'offers' => $entries, 'blank' => (string) ( $laid[1]['t'] ?? "\n" ), 'schemas' => Tree::schemas( $appData, [], $registry, $names ), 'container' => $route[count( $route ) - 1] ] );
		}

		/**
		 *	The template a request is about, read: its name checked, the file there, its markup one that is read as it stands and written as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		int				$level				The step the account holds: a frame is for the design and up
		 *
		 *	@return 	array|null								[ template, state, nodes ]; null after the answer that says why not
		 */
		private static function _open( array &$appData, array &$request, int $level ): ?array {

			$data = \Nino\Admin\Admin::postData();
			$template = \Nino\Admin\Admin::postString( $data, 'template' );

			if( Store::named( $template ) === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return null;
			}

			if( Store::named( $template )['frame'] === true && $level < \Nino\Modules\Builder::LEVEL_DESIGN ) {
				self::_fail( $request, self::_level() );
				return null;
			}

			$state = Store::state( $appData, $template );

			if( $state === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return null;
			}

			$notes = [];
			$nodes = Tree::read( $appData, $state['source'], $notes );

			// A template that is not written as it was read would be changed by being saved: it is left to the one who wrote it
			if( $notes !== [] || \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) ) !== $state['source'] ) {
				self::_fail( $request, [ 409, 'the template cannot be read as it stands', 'builder_unreadable', [ $template ], 'template' ] );
				return null;
			}

			return [ $template, $state, $nodes ];
		}

		/**
		 *	What the screen needs of the language: the one of the view, and the languages that have words
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		private static function _view( array &$appData, string $locale, int $level ): array {
			return [ 'contentLocale' => $locale, 'native' => \Nino\Locales::getNativeLocale( $appData ), 'locales' => \Nino\Admin\Admin::textLocales( $appData ), 'dir' => (string) ( $appData['/nino/dir'] ?? '' ), 'level' => $level ];
		}

		/**
		 *	The path the page is shown at: the address of the language of the view, or of the first page for a frame - or of the page a frame is opened from
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		string		$locale
		 *	@param		string		$page					The page a frame is shown on, its name without page-; '' for the first
		 *
		 *	@return 	string
		 */
		private static function _address( array &$appData, string $template, string $locale, string $page = '' ): string {

			$pages = Pages::all( $appData, $locale );

			if( ( Store::named( $template )['frame'] ?? false ) === true && preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $page ) === 1 )
				foreach( $pages as $entry )
					if( $entry['template'] === 'page-'. $page )
						return $entry['path'];

			foreach( $pages as $page )
				if( $page['template'] === $template )
					return $page['path'];

			return $pages[0]['path'] ?? '/';
		}

		/**
		 *	When the last state was kept, 0 for none
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *
		 *	@return 	int
		 */
		private static function _last( array &$appData, string $template ): int {
			return (int) ( Store::previous( $appData, $template )['saved'] ?? 0 );
		}

		/**
		 *	What a person needs to know after a save or a restore
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		int				$level
		 *
		 *	@return 	array
		 */
		private static function _after( array &$appData, string $template, int $level ): array {

			$state = Store::state( $appData, $template );
			$nodes = $state === null ? [] : Tree::read( $appData, $state['source'] );
			$registry = \Nino\Components::components( $appData );

			return [ 'last' => self::_last( $appData, $template ), 'stamp' => $state === null ? '' : Store::stamp( $state ), 'tree' => $nodes, 'texts' => $state['texts'] ?? [], 'sources' => Tree::sources( $nodes, $registry, $template ), 'known' => Tree::known( $nodes, $registry ), 'slots' => array_keys( $state['slots'] ?? [] ) ]
				+ ( $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? [ 'findings' => Tree::findings( $appData, $nodes ), 'schemas' => Tree::schemas( $appData, $nodes, $registry ) ] : [] );
		}

		/**
		 *	What only the screen of a person who designs needs of a page that is opened: the schemas of its components, what the register has against it and the pages
		 *	of the site a link can name
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *	@param		int				$level
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		private static function _more( array &$appData, array $nodes, array $registry, int $level, string $locale ): array {

			if( $level < \Nino\Modules\Builder::LEVEL_DESIGN )
				return [];

			$links = [];

			foreach( \Nino\Pages::all( $appData ) as $page )
				if( preg_match( '#^/(?:[A-Za-z0-9._~-]+(?:/[A-Za-z0-9._~-]+)*)?$#D', (string) $page['uri'] ) === 1 )
					$links[] = [ 'uri' => (string) $page['uri'], 'name' => (string) ( $page['names'][$locale] ?? '' ) !== '' ? (string) $page['names'][$locale] : (string) $page['uri'] ];

			return [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ), 'findings' => Tree::findings( $appData, $nodes ), 'links' => $links ];
		}

		/**
		 *	The notation the screen shows beside the settings: the whole block and the first line of the component that is chosen, as the template has them written
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$nodes				The tree of the draft
		 *	@param		mixed			$look					[ 'block' => position, 'node' => position ] as the screen sends it
		 *
		 *	@return 	array											'block' and 'node', where the positions name calls
		 */
		private static function _notation( array &$appData, array $nodes, mixed $look ): array {

			$out = [];

			if( is_array( $look ) === false )
				return $out;

			$containers = \Nino\Components::containers( $appData );
			$block = is_string( $look['block'] ?? null ) === true ? Tree::at( $nodes, $look['block'] ) : null;
			$node = is_string( $look['node'] ?? null ) === true ? Tree::at( $nodes, $look['node'] ) : null;

			if( $block !== null && isset( $block['n'] ) === true )
				$out['block'] = \Nino\Markup::write( [ $block ], $containers );

			if( $node !== null && isset( $node['n'] ) === true )
				$out['node'] = Tree::opening( $node, $containers );

			return $out;
		}

		/**
		 *	The door and the step: the account holds the right of the panel and a step of at least $least
		 *
		 *	@param		array 		&$appData
		 *	@param		array			&$request
		 *	@param		int				$least
		 *
		 *	@return 	int|null									The step; null after the answer that says why not
		 */
		private static function _gate( array &$appData, array &$request, int $least ): ?int {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return null;

			$level = \Nino\Modules\Builder::level( $appData );

			if( $level < $least ) {
				self::_fail( $request, self::_level() );
				return null;
			}

			return $level;
		}

		private static function _level(): array {
			return [ 403, 'the account does not hold the right for this', 'builder_level', [], '' ];
		}

		private static function _changed(): array {
			return [ 409, 'the page was changed since it was opened', 'builder_changed', [], 'template' ];
		}

		private static function _fail( array &$request, array $failure ): void {
			\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
		}
	}
}
