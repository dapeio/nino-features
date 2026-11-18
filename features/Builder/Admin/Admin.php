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
	//	builder/keys			the texts of the project - or the picture slots - to choose a source from, found by name or by text
	//	builder/upload		a picture for a slot of the page, made at once under a name no slot has; the save gives it to the slot
	//	builder/unstage		the pictures of a draft that is thrown away: the files nobody has been given
	//	builder/blocksave	a block of the page as a block of the project: private/blocks/<key>/, only if the kernel takes it as one
	//	builder/blockdrop	a block of the project taken away again
	//	builder/create		a page: its template, its routes, its menu entries and the words of its first block, in one step
	class Admin {

		// How many texts a search of the dialog Source answers with
		private const int KEYS = 60;

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
				'builder/keys'		=> [ self::class, 'apiKeys' ],
				'builder/upload'	=> [ self::class, 'apiUpload' ],
				'builder/unstage'	=> [ self::class, 'apiUnstage' ],
				'builder/blocksave'	=> [ self::class, 'apiBlockSave' ],
				'builder/blockdrop'	=> [ self::class, 'apiBlockDrop' ],
				'builder/create'	=> [ self::class, 'apiCreate' ],
				'builder/copyof'	=> [ self::class, 'apiCopyOf' ],
				'builder/duplicate'	=> [ self::class, 'apiDuplicate' ],
				'builder/uses'		=> [ self::class, 'apiUses' ],
				'builder/remove'	=> [ self::class, 'apiRemove' ],
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
		 *	@param		string		$locale				The language of the word; '' for the one of the workbench
		 *
		 *	@return 	string
		 */
		private static function _word( array &$appData, string $key, string $locale = '' ): string {

			$locale = $locale === '' ? \Nino\Locales::getCurrentLocale( $appData ) : $locale;
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
				'builder/upload'	=> 'Upload Page Picture '. Store::shown( \Nino\Admin\Admin::postString( $data, 'uri' ) ),
				'builder/blocksave'	=> 'Save Own Block '. Store::shown( \Nino\Admin\Admin::postString( $data, 'key' ) ),
				'builder/blockdrop'	=> 'Delete Own Block '. Store::shown( \Nino\Admin\Admin::postString( $data, 'key' ) ),
				'builder/create'	=> 'Create Page '. Store::shown( is_array( $data['addresses'] ?? null ) === true ? implode( ' ', array_filter( array_map( 'strval', array_slice( array_values( $data['addresses'] ), 0, 3 ) ), 'is_string' ) ) : '' ),
				'builder/duplicate'	=> 'Duplicate Page '. Store::shown( \Nino\Admin\Admin::postString( $data, 'from' ) ). ' as '. Store::shown( is_array( $data['addresses'] ?? null ) === true ? implode( ' ', array_filter( array_map( 'strval', array_slice( array_values( $data['addresses'] ), 0, 3 ) ), 'is_string' ) ) : '' ),
				'builder/remove'	=> 'Delete Page '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				default						=> '',
			};
		}

		public static function apiList( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$frames = [];

			// The frames are written for every page: from the design on
			foreach( Store::FRAMES as $key => $template )
				if( $level >= \Nino\Modules\Builder::LEVEL_DESIGN && is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === true )
					$frames[] = [ 'key' => $key, 'template' => $template ];

			// What was uploaded for a draft of a page that nobody opens any more is taken away with the next look at the list
			$pages = Pages::listed( $appData, $locale );

			foreach( $pages as $page )
				Pictures::sweep( $appData, $page['template'] );

			// A page is made by the one who may design and may write routes: the list says so, and the action asks again
			$create = $level >= \Nino\Modules\Builder::LEVEL_DESIGN && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Builder::ROUTES ) === true;

			\Nino\Http::ok( $request, [ 'pages' => $pages, 'frames' => $frames, 'create' => $create, 'navs' => \Nino\Pages::navKeys( $appData ) ] + self::_view( $appData, $locale, $level ) );
		}

		public static function apiOpen( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, $nodes, $locale ] = $opened;
			$registry = \Nino\Components::components( $appData );
			$page = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'page' );

			// The pictures that were uploaded for a draft nobody saved and are older than a day are taken away when a page is opened
			Pictures::sweep( $appData, $template );
			$sources = Tree::sources( $nodes, $registry, $template );

			\Nino\Http::ok( $request, [
				'template'	=> $template,
				'frame'			=> Store::named( $template )['frame'],
				'tree'			=> $nodes,
				'sources'		=> $sources,
				'shared'		=> $shared = self::_shared( $appData, $sources, $template, $locale ),
				'uses'			=> self::_uses( $appData, array_keys( $shared ), $locale ),
				'texts'			=> $state['texts'],
				'known'			=> Tree::known( $nodes, $registry ),
				'slots'			=> array_keys( $state['slots'] ),
				'images'		=> Pictures::shown( $appData, $state['slots'] ),
				'stamp'			=> Store::stamp( $state ),
				'address'		=> self::_address( $appData, $template, $locale, $page ),
				'last'			=> self::_last( $appData, $template ),
				'unback'		=> self::_unbacked( $appData, $template, $state, $nodes, $level ),
				'fresh'			=> array_combine( \Nino\Locales::getTextLocales( $appData ), array_map( static fn( string $text ): string => self::_word( $appData, 'new/text', $text ), \Nino\Locales::getTextLocales( $appData ) ) ),
			] + self::_more( $appData, $nodes, $registry, $level, $locale ) + self::_view( $appData, $locale, $level ) );
		}

		/**
		 *	The words of the texts a page reads from another place - the facts of the company, the words several templates share -, as the page shows them in the language of the
		 *	view: what the screen tells the account that may only write the content, which cannot change them here. Only the keys a text may name as a textfill are offered
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$sources			Tree::sources()
		 *	@param		string		$template
		 *	@param		string		$locale
		 *
		 *	@return 	array											key => text
		 */
		private static function _shared( array &$appData, array $sources, string $template, string $locale ): array {

			$out = [];
			$native = \Nino\Locales::getNativeLocale( $appData );
			$prefixes = \Nino\Text::FILL_FORMS['textfill']['prefixes'];

			foreach( $sources as $entry ) {

				$key = (string) $entry['key'];

				if( $entry['kind'] !== 'text' || $key === '' || isset( $out[$key] ) === true || Tree::own( $key, $template ) === true || count( explode( '/', $key ) ) !== 5 )
					continue;

				if( array_filter( $prefixes, static fn( string $prefix ): bool => str_starts_with( $key, $prefix ) ) === [] )
					continue;

				$values = \Nino\Text::values( $appData, dirname( $key, 2 ) )[$key] ?? [];
				$text = (string) ( $values[$locale] ?? $values[$native] ?? reset( $values ) ?: '' );

				if( $text !== '' )
					$out[$key] = mb_substr( $text, 0, 200, 'UTF-8' );
			}

			return $out;
		}

		/**
		 *	On how many templates of the site - the pages and the two frames - each of some keys is written: what the screen says of a text that more than one page reads. A key
		 *	of another place is always written with its slash in front, so that the count is a search for it in the files, which reads no markup and costs a file each
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$keys					The keys of the texts of another place
		 *	@param		string		$locale
		 *
		 *	@return 	array											key => the number of templates, for the keys that are on two or more
		 */
		private static function _uses( array &$appData, array $keys, string $locale ): array {

			if( $keys === [] )
				return [];

			$templates = [ 'frame-header', 'frame-footer' ];

			foreach( Pages::all( $appData, $locale ) as $page )
				$templates[] = (string) $page['template'];

			$count = array_fill_keys( $keys, 0 );

			foreach( array_unique( $templates ) as $template ) {

				$source = Store::named( $template ) === null ? null : \Nino\Filesystem::getFileContent( $appData, '/templates/'. $template. '.tpl', null );

				if( is_string( $source ) === false )
					continue;

				// The whole key: one that goes on - `/x/y/z/text` in `/x/y/z/textlong` - is another key
				foreach( $keys as $key )
					if( preg_match( '#(?<![a-z0-9_/-])'. preg_quote( $key, '#' ). '(?![a-z0-9_/-])#', $source ) === 1 )
						$count[$key]++;
			}

			return array_filter( $count, static fn( int $number ): bool => $number > 1 );
		}

		public static function apiPreview( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, , $locale ] = $opened;
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [], $data['inserted'] ?? [], $level, $data['pictures'] ?? [] );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

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
			$prepared = Store::prepare( $appData, $template, $state, $data['tree'] ?? null, $data['texts'] ?? [], $data['slots'] ?? [], $data['inserted'] ?? [], $level, $data['pictures'] ?? [] );

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
			$target = [ 'source' => $prepared['source'], 'texts' => $state['texts'], 'slots' => array_replace( array_diff_key( $state['slots'], array_flip( $changes['dropSlots'] ) ), $prepared['slots'] ) ];

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
				self::_fail( $request, self::_unsaved( $failed ) );
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

			$failure = self::_unback( $appData, $template, $state, $nodes, $previous, $level );

			if( $failure !== null ) {
				self::_fail( $request, $failure );
				return;
			}

			$target = [
				'source'	=> $previous['source'],
				'texts'		=> array_filter( $previous['texts'], static fn( mixed $key ): bool => is_string( $key ) === true && Tree::own( $key, $template ) === true, ARRAY_FILTER_USE_KEY ),
				'slots'		=> array_filter( $previous['slots'], static fn( mixed $uri ): bool => is_string( $uri ) === true && Tree::own( $uri, $template ) === true, ARRAY_FILTER_USE_KEY ),
			];

			$failed = Store::commit( $appData, $template, $state, $target, null, [], null, [], $level );

			if( $failed !== null ) {
				self::_fail( $request, self::_unsaved( $failed ) );
				return;
			}

			\Nino\Http::ok( $request, [ 'restored' => true ] + self::_after( $appData, $template, $level ) );
		}

		/**
		 *	Whether the last state of a page may be put back by an account of this step of permission, as a restore holds it - and what is against it where it may not
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state				As Store::state() answers
		 *	@param		array			$nodes				The tree of the page as it is
		 *	@param		array			$previous			As Store::previous() answers, not null
		 *	@param		int				$level
		 *
		 *	@return 	array|null								The failure as _fail() takes it; null where the last state may be put back
		 */
		private static function _unback( array &$appData, string $template, array $state, array $nodes, array $previous, int $level ): ?array {

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
				return [ 403, 'the last state may not be put back: '. $why, $code, [], 'tree' ];
			}

			// What the last state brings back is held to the rule for where a link goes, as a save of this step is
			$links = Tree::linksOk( $appData, $back, $registry );

			foreach( Tree::sources( $back, $registry, $template ) as $entry )
				if( $entry['kind'] === 'link' && Tree::own( $entry['key'], $template ) === true )
					foreach( (array) ( $previous['texts'][$entry['key']] ?? [] ) as $word )
						$links = $links === true && Tree::linkTarget( $appData, (string) $word ) === true;

			if( $links === false ) {
				return [ 403, 'the last state may not be put back: link', 'builder_refused_link', [], 'tree' ];
			}

			return null;
		}

		/**
		 *	The code of what keeps this account from putting the last state of a page back, '' where nothing does or there is no last state: the screen leaves the button off and says why
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		array			$state
		 *	@param		array			$nodes
		 *	@param		int				$level
		 *
		 *	@return 	string
		 */
		private static function _unbacked( array &$appData, string $template, array $state, array $nodes, int $level ): string {

			$previous = Store::previous( $appData, $template );

			return $previous === null ? '' : (string) ( self::_unback( $appData, $template, $state, $nodes, $previous, $level )[2] ?? '' );
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

			[ $template, , , $locale ] = $opened;
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

		public static function apiKeys( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN ) === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$query = mb_strtolower( trim( mb_substr( \Nino\Admin\Admin::postString( $data, 'query' ), 0, 80 ) ) );
			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$template = \Nino\Admin\Admin::postString( $data, 'template' );
			$prefix = Store::named( $template ) === null ? '/' : '/template/'. $template. '/';
			$found = [];

			if( $template !== '' && Store::named( $template ) === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return;
			}

			// The fields of the elements of a type, for the source that is a field of the element of a loop
			if( \Nino\Admin\Admin::postString( $data, 'kind' ) === 'fields' ) {

				$type = \Nino\Admin\Admin::postString( $data, 'type' );
				$known = preg_match( '/^\/[a-z][a-z0-9_-]{0,63}$/D', $type ) === 1 || isset( \Nino\Elements::PLACEHOLDERS[$type] ) === true;
				$labels = [];

				// The words a field has in the model of its type, where it has some: a text, or the words of each language
				foreach( $known === true ? \Nino\Elements::getElementModel( $appData, $type ) : [] as $name => $field ) {

					$label = $field['label'] ?? null;
					$label = is_array( $label ) === true ? ( $label[$locale] ?? reset( $label ) ) : $label;

					if( is_string( $label ) === true && $label !== '' && str_contains( $label, '/' ) === false )
						$labels[(string) $name] = mb_substr( $label, 0, 60 );
				}

				\Nino\Http::ok( $request, [ 'fields' => $known === true ? Tree::fields( $appData, $type ) : [], 'labels' => $labels ] );
				return;
			}

			// What a loop may show: the types of elements the project has, with their titles - the example type of the kernel is none of them
			if( \Nino\Admin\Admin::postString( $data, 'kind' ) === 'types' ) {

				\Nino\Http::ok( $request, [ 'types' => array_map( static fn( array $type ): array => [ 'type' => '/'. $type['uri'], 'title' => (string) $type['title'] ], \Nino\Modules\Elements\Types::summaries( $appData ) ) ] );
				return;
			}

			$places = [];

			foreach( \Nino\Pages::all( $appData ) as $page ) {

				$places[(string) $page['template']] = (string) ( $page['names'][$locale] ?? '' ) !== '' ? (string) $page['names'][$locale] : (string) $page['template'];
				$places[(string) $page['uri']] = $places[(string) $page['template']];
			}

			if( \Nino\Admin\Admin::postString( $data, 'kind' ) === 'image' ) {

				foreach( \Nino\Images::getSlots( $appData ) as $uri => $slot ) {

					$label = is_array( $slot ) === true ? (string) ( $slot['label'] ?? $uri ) : '';

					if( is_string( $uri ) === false || $label === '' || ( $query !== '' && str_contains( mb_strtolower( $label. ' '. $uri ), $query ) === false ) )
						continue;

					$found[] = [ 'key' => $uri, 'name' => $label, 'text' => '', 'own' => str_starts_with( $uri, $prefix ), 'url' => is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '' ? \Nino\Images::getUrl( $appData, $slot['filename'] ) : '' ];
				}
			}
			else {

				foreach( \Nino\Text::entries( $appData, false ) as $entry ) {

					$all = array_filter( array_map( static fn( string $text ): string => trim( (string) preg_replace( '/\s+/', ' ', html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ), $entry['values'] ), static fn( string $text ): bool => $text !== '' );

					if( $all === [] || ( $query !== '' && str_contains( mb_strtolower( $entry['key']. ' '. implode( ' ', $all ) ), $query ) === false ) )
						continue;

					$found[] = [ 'key' => $entry['key'], 'name' => substr( $entry['key'], (int) strrpos( $entry['key'], '/' ) + 1 ), 'text' => mb_substr( $all[$locale] ?? $all['*'] ?? reset( $all ), 0, 120 ), 'own' => str_starts_with( $entry['key'], $prefix ) ] + self::_area( $entry['key'], $places );
				}
			}

			// What belongs to the page stands first
			usort( $found, static fn( array $a, array $b ): int => [ $b['own'], $a['key'] ] <=> [ $a['own'], $b['key'] ] );

			\Nino\Http::ok( $request, [ 'entries' => array_slice( $found, 0, self::KEYS ), 'more' => count( $found ) > self::KEYS ] );
		}

		/**
		 *	Where a text of the project belongs, for a person: the part of the project it is of, or the page - with its name - it is the words of
		 *
		 *	@param		string		$key
		 *	@param		array			$places				template or Element-URI of a page => its name
		 *
		 *	@return 	array											[ 'area' => company, website, mail, project, common, page, feature or other, 'of' => the name of the page or the key of the feature, '' for none ]
		 */
		private static function _area( string $key, array $places ): array {

			$parts = explode( '/', $key );

			if( ( $parts[1] ?? '' ) === 'project' )
				return [ 'area' => in_array( $parts[2] ?? '', [ 'company', 'website', 'mail' ], true ) === true ? $parts[2] : 'project', 'of' => '' ];

			if( ( $parts[1] ?? '' ) === 'template' )
				return ( $parts[2] ?? '' ) === 'common' ? [ 'area' => 'common', 'of' => '' ] : [ 'area' => 'page', 'of' => $places[$parts[2] ?? ''] ?? (string) ( $parts[2] ?? '' ) ];

			if( ( $parts[1] ?? '' ) === '_nino' && ( $parts[2] ?? '' ) === 'webpage' )
				return [ 'area' => 'page', 'of' => $places['/'. ( $parts[3] ?? '' )] ?? (string) ( $parts[3] ?? '' ) ];

			return ( $parts[1] ?? '' ) === 'feature' ? [ 'area' => 'feature', 'of' => (string) ( $parts[2] ?? '' ) ] : [ 'area' => 'other', 'of' => '' ];
		}

		public static function apiUpload( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$uri = \Nino\Admin\Admin::postString( $data, 'uri' );
			$slot = $state['slots'][$uri] ?? [];
			$width = (int) ( $slot['width'] ?? $data['width'] ?? 0 );
			$height = (int) ( $slot['height'] ?? $data['height'] ?? 0 );

			if( Tree::own( $uri, $template ) === false || \Nino\Images::isSlotUri( $uri ) === false || $width < 1 || $height < 1 || $width > Pictures::MAX_SIDE || $height > Pictures::MAX_SIDE ) {
				self::_fail( $request, [ 403, 'the picture is not one of the page', 'builder_picture', [], 'uri' ] );
				return;
			}

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

			$rejection = \Nino\Images::reject( $bytes );

			if( $rejection !== null ) {
				\Nino\Http::fail( $request, 400, 'the image was refused: '. $rejection['code'], $rejection['code'], $rejection['params'] );
				return;
			}

			// Whatever was uploaded for a draft that nobody saved, and is older than a day, goes before another is made
			Pictures::sweep( $appData, $template );

			$file = Pictures::stage( $appData, $uri, $bytes, $width, $height );

			if( $file === false ) {
				\Nino\Http::fail( $request, 400, 'the image could not be processed', 'image_unreadable' );
				return;
			}

			$shown = \Nino\Images::size( $bytes );

			\Nino\Http::ok( $request, [ 'file' => $file, 'url' => \Nino\Images::getUrl( $appData, $file ), 'belowTarget' => $shown !== false && ( $shown['width'] < $width || $shown['height'] < $height ) ] );
		}

		public static function apiUnstage( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			$files = is_array( $data['files'] ?? null ) === true ? array_slice( array_values( array_filter( $data['files'], 'is_string' ) ), 0, 40 ) : [];

			\Nino\Http::ok( $request, [ 'removed' => Pictures::sweep( $appData, $opened[0], $files ) ] );
		}

		public static function apiBlockSave( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );

			if( $canonical === null || Store::named( $opened[0] )['frame'] === true ) {
				self::_fail( $request, [ 400, 'the tree is none the Builder takes', 'builder_tree', [], 'tree' ] );
				return;
			}

			$made = Custom::save( $appData, $opened[0], $opened[1], $canonical[1], $data );

			if( isset( $made['failure'] ) === true ) {
				self::_fail( $request, $made['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $made );
		}

		public static function apiBlockDrop( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN ) === null )
				return;

			$gone = Custom::drop( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' ) );

			if( $gone !== null ) {
				self::_fail( $request, $gone['failure'] );
				return;
			}

			\Nino\Http::ok( $request );
		}

		public static function apiCreate( array &$appData, array &$request ): void {

			if( self::_maker( $appData, $request ) === false )
				return;

			$made = Pages::create( $appData, \Nino\Admin\Admin::postData() );

			if( isset( $made['failure'] ) === true ) {
				self::_fail( $request, $made['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $made );
		}

		/**
		 *	The door of what makes, copies and takes away a page: the step of the design and the right of the routes, both - the right that makes a page is the one that takes it
		 *	away and copies it
		 *
		 *	@param		array 		&$appData
		 *	@param		array			&$request
		 *
		 *	@return 	bool											false after the answer that says why not
		 */
		private static function _maker( array &$appData, array &$request ): bool {
			return self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN ) !== null && \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Builder::ROUTES ) === true;
		}

		public static function apiCopyOf( array &$appData, array &$request ): void {

			if( self::_maker( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$template = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' );
			$origin = Pages::origin( $appData, $template );

			if( $origin === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return;
			}

			// The name of the copy is the name of the page and a word that says so, in the language of each name
			foreach( $origin['names'] as $each => $name )
				$origin['names'][$each] = $name === '' ? '' : $name. ' '. self::_word( $appData, 'new/copy', $each );

			\Nino\Http::ok( $request, $origin + [ 'label' => Pages::label( $appData, $template, $locale ) ] );
		}

		public static function apiDuplicate( array &$appData, array &$request ): void {

			if( self::_maker( $appData, $request ) === false )
				return;

			$made = Pages::duplicate( $appData, \Nino\Admin\Admin::postData() );

			if( isset( $made['failure'] ) === true ) {
				self::_fail( $request, $made['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $made );
		}

		public static function apiUses( array &$appData, array &$request ): void {

			if( self::_maker( $appData, $request ) === false )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$asked = Pages::uses( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ), $locale );

			if( isset( $asked['failure'] ) === true ) {
				self::_fail( $request, $asked['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $asked );
		}

		public static function apiRemove( array &$appData, array &$request ): void {

			if( self::_maker( $appData, $request ) === false )
				return;

			$gone = Pages::remove( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) );

			if( isset( $gone['failure'] ) === true ) {
				self::_fail( $request, $gone['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $gone );
		}

		/**
		 *	The template a request is about, read: its name checked, the file there, its markup one that is read as it stands and written as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		int				$level				The step the account holds: a frame is for the design and up
		 *
		 *	@return 	array|null								[ template, state, nodes, locale ]; null after the answer that says why not
		 */
		private static function _open( array &$appData, array &$request, int $level ): ?array {

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return null;

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

			// A template that is not written as it was read would be changed by being saved: it is left to the one who wrote it, and the line where the reading
			// stops is told - a start without an end, an end without a start
			$written = \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) );

			if( $notes !== [] || $written !== $state['source'] ) {

				$offset = $notes !== [] ? (int) ( $notes[0]['offset'] ?? 0 ) : strspn( $state['source'] ^ $written, "\0" );

				self::_fail( $request, [ 409, 'the template cannot be read as it stands', 'builder_unreadable', [ Store::shown( Store::named( $template )['frame'] === true ? self::_word( $appData, 'word/'. $template ) : Pages::label( $appData, $template, $locale ) ), Store::line( $state['source'], $offset ) ], 'template' ] );
				return null;
			}

			return [ $template, $state, $nodes, $locale ];
		}

		/**
		 *	What the screen needs of the language: the one of the view, and the languages that have words, and how the page shows the block under the pointer (the setting "dim")
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$locale
		 *
		 *	@return 	array
		 */
		private static function _view( array &$appData, string $locale, int $level ): array {
			return [ 'contentLocale' => $locale, 'native' => \Nino\Locales::getNativeLocale( $appData ), 'locales' => \Nino\Admin\Admin::textLocales( $appData ), 'dir' => (string) ( $appData['/nino/dir'] ?? '' ), 'level' => $level, 'dim' => (string) \Nino\Features::setting( $appData, 'builder', 'dim', 'frame' ) ];
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

			return [ 'last' => self::_last( $appData, $template ), 'unback' => $state === null ? '' : self::_unbacked( $appData, $template, $state, $nodes, $level ), 'stamp' => $state === null ? '' : Store::stamp( $state ), 'tree' => $nodes, 'texts' => $state['texts'] ?? [], 'sources' => Tree::sources( $nodes, $registry, $template ), 'known' => Tree::known( $nodes, $registry ), 'slots' => array_keys( $state['slots'] ?? [] ), 'images' => Pictures::shown( $appData, $state['slots'] ?? [] ) ]
				+ [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ) ] + ( $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? [ 'findings' => Tree::findings( $appData, $nodes ) ] : [] );
		}

		/**
		 *	What the screen needs of a page that is opened beyond the page itself: the schemas of its components - for every step - and, for the one that designs, what the
		 *	register has against the page and the pages of the site a link can name
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

			// What the lightbox of a component is drawn from - its kind, what it reads and what it offers - is the register's and is told to every step: it shows the account that may write the
			// content the source to read and the content to write. The findings and the pages a link may go to are for the design
			if( $level < \Nino\Modules\Builder::LEVEL_DESIGN )
				return [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ) ];

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

		/**
		 *	The language a request is about: the one it names, one of the languages the project has texts in. The session's working language is never read - the one
		 *	who asks may have changed it in another tab since the page was drawn, and what is drawn or written follows what the page shows
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	string|null								null after the answer that says the request names none or a language the project does not have
		 */
		private static function _locale( array &$appData, array &$request ): ?string {

			$locale = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'locale' );

			if( $locale === '' || in_array( $locale, \Nino\Locales::getTextLocales( $appData ), true ) === false ) {
				self::_fail( $request, [ 400, 'the request names no language of the website', 'builder_language', [], 'locale' ] );
				return null;
			}

			return $locale;
		}

		private static function _level(): array {
			return [ 403, 'the account does not hold the right for this', 'builder_level', [], '' ];
		}

		/**
		 *	The failure of a commit as the panel says it: a page that was changed since it was opened, a page that was taken away meanwhile, or a write that failed
		 *
		 *	@param		string		$failed				What Store::commit() answered
		 *
		 *	@return 	array
		 */
		private static function _unsaved( string $failed ): array {

			if( $failed === 'gone' )
				return [ 404, 'the page was taken away meanwhile', 'builder_gone', [], 'template' ];

			return $failed === 'changed' ? self::_changed() : [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ];
		}

		private static function _changed(): array {
			return [ 409, 'the page was changed since it was opened', 'builder_changed', [], 'template' ];
		}

		private static function _fail( array &$request, array $failure ): void {
			\Nino\Http::fail( $request, $failure[0], $failure[1], $failure[2], $failure[3], $failure[4] );
		}
	}
}
