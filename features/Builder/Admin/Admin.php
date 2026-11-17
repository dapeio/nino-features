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
			];
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

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$frames = [];

			foreach( Store::FRAMES as $key => $template )
				if( is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === true )
					$frames[] = [ 'key' => $key, 'template' => $template ];

			\Nino\Http::ok( $request, [ 'pages' => Pages::all( $appData, $locale ), 'frames' => $frames ] + self::_view( $appData, $locale ) );
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$opened = self::_open( $appData, $request );

			if( $opened === null )
				return;

			[ $template, $state, $nodes ] = $opened;
			$locale = \Nino\Admin\Admin::contentLocale( $appData );
			$registry = \Nino\Components::components( $appData );

			\Nino\Http::ok( $request, [
				'template'	=> $template,
				'frame'			=> Store::named( $template )['frame'],
				'tree'			=> $nodes,
				'sources'		=> Tree::sources( $nodes, $registry, $template ),
				'texts'			=> $state['texts'],
				'known'			=> self::_known( $nodes, $registry ),
				'slots'			=> array_keys( $state['slots'] ),
				'stamp'			=> Store::stamp( $state ),
				'address'		=> self::_address( $appData, $template, $locale ),
				'last'			=> self::_last( $appData, $template ),
			] + self::_view( $appData, $locale ) );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [] );

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

			$token = \Nino\Preview::issue( $appData, \Nino\Modules\Builder::KIND, self::perm(), [ 'template' => $template, 'source' => $prepared['source'], 'fills' => $fills, 'slots' => $prepared['shown'], 'locale' => $locale ], \Nino\Admin\Admin::postString( $data, 'token' ) );

			if( $token === '' ) {
				self::_fail( $request, [ 500, 'the preview could not be written', 'builder_preview', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'sources' => $prepared['sources'], 'address' => self::_address( $appData, $template, $locale ) ] );
		}

		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [] );

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
				\Nino\Http::ok( $request, [ 'saved' => true, 'changed' => false, 'stamp' => Store::stamp( $state ) ] + self::_after( $appData, $template ) );
				return;
			}

			$failed = Store::commit( $appData, $template, $state, $target, $changes, $prepared['formats'], null, $prepared['files'] );

			if( $failed !== null ) {
				self::_fail( $request, $failed === 'changed' ? self::_changed() : [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] );
				return;
			}

			$now = Store::state( $appData, $template ) ?? $state;
			\Nino\Http::ok( $request, [ 'saved' => true, 'changed' => true, 'stamp' => Store::stamp( $now ), 'texts' => $now['texts'] ] + self::_after( $appData, $template ) );
		}

		public static function apiRestore( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request );

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

			// What the last state brings back is held to the rule the other way round: the page as it is must be the last state with its blocks moved, copied or left
			// out - that is how it came to be -, and no other piece is brought back. Only the words and the slots of the namespace of the page are written, whatever
			// the file of the last state says
			$registry = \Nino\Components::components( $appData );
			$back = Tree::read( $appData, $previous['source'] );
			$taken = Tree::taken( $back, $registry, array_merge( array_keys( $previous['texts'] ), array_keys( $previous['slots'] ) ) );

			if( ( $why = Tree::refused( $back, $nodes, $registry, Store::named( $template )['frame'], $taken ) ) !== null ) {
				self::_fail( $request, [ 403, 'the last state may not be put back: '. $why, 'builder_refused_'. $why, [], 'tree' ] );
				return;
			}

			$target = [
				'source'	=> $previous['source'],
				'texts'		=> array_filter( $previous['texts'], static fn( mixed $key ): bool => is_string( $key ) === true && Tree::own( $key, $template ) === true, ARRAY_FILTER_USE_KEY ),
				'slots'		=> array_filter( $previous['slots'], static fn( mixed $uri ): bool => is_string( $uri ) === true && Tree::own( $uri, $template ) === true, ARRAY_FILTER_USE_KEY ),
			];

			$failed = Store::commit( $appData, $template, $state, $target, null );

			if( $failed !== null ) {
				self::_fail( $request, $failed === 'changed' ? self::_changed() : [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'restored' => true ] + self::_after( $appData, $template ) );
		}

		/**
		 *	The template a request is about, read: its name checked, the file there, its markup one that is read as it stands and written as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	array|null								[ template, state, nodes ]; null after the answer that says why not
		 */
		private static function _open( array &$appData, array &$request ): ?array {

			$data = \Nino\Admin\Admin::postData();
			$template = \Nino\Admin\Admin::postString( $data, 'template' );

			if( Store::named( $template ) === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
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
		 *	The names of the calls in a tree
		 *
		 *	@param		array			$nodes
		 *	@param		array			&$names				(reference)
		 *
		 *	@return 	void
		 */
		private static function _names( array $nodes, array &$names ): void {

			foreach( $nodes as $node )
				if( isset( $node['n'] ) === true ) {
					$names[$node['n']] = true;

					if( is_array( $node['c'] ?? null ) === true )
						self::_names( $node['c'], $names );
				}
		}

		/**
		 *	What the screen needs of the language: the one of the view, and the languages that have words
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		private static function _view( array &$appData, string $locale ): array {
			return [ 'contentLocale' => $locale, 'locales' => \Nino\Admin\Admin::textLocales( $appData ), 'dir' => (string) ( $appData['/nino/dir'] ?? '' ) ];
		}

		/**
		 *	What the screen knows of the calls of a tree: for each name the register has, its label and the attribute that is its id
		 *
		 *	@param		array			$nodes
		 *	@param		array			$registry
		 *
		 *	@return 	array											name => [ 'label' => ..., 'id' => attribute or '', 'source' => text, image, link or none ]
		 */
		private static function _known( array $nodes, array $registry ): array {

			$names = [];
			self::_names( $nodes, $names );
			$known = [];

			foreach( array_keys( $names ) as $name )
				if( isset( $registry[$name] ) === true )
					$known[$name] = [ 'label' => $registry[$name]['label'], 'id' => Tree::idAttribute( $registry[$name] ), 'source' => (string) ( $registry[$name]['source'] ?? 'none' ) ];

			return $known;
		}

		/**
		 *	The path the page is shown at: the address of the language of the view, or of the first page for a frame
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		string		$locale
		 *
		 *	@return 	string
		 */
		private static function _address( array &$appData, string $template, string $locale ): string {

			$pages = Pages::all( $appData, $locale );

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
		 *
		 *	@return 	array
		 */
		private static function _after( array &$appData, string $template ): array {

			$state = Store::state( $appData, $template );
			$nodes = $state === null ? [] : Tree::read( $appData, $state['source'] );
			$registry = \Nino\Components::components( $appData );

			return [ 'last' => self::_last( $appData, $template ), 'stamp' => $state === null ? '' : Store::stamp( $state ), 'tree' => $nodes, 'texts' => $state['texts'] ?? [], 'sources' => Tree::sources( $nodes, $registry, $template ), 'known' => self::_known( $nodes, $registry ), 'slots' => array_keys( $state['slots'] ?? [] ) ];
		}

		private static function _changed(): array {
			return [ 409, 'the page was changed since it was opened', 'builder_changed', [], 'template' ];
		}

		private static function _fail( array &$request, array $failure ): void {
			\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
		}
	}
}
