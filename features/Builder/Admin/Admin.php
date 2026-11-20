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
	//	builder/frametexts	the texts a frame of the site holds as its own, to read and to write: the content of it, from the content step on, and no structure
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
				'builder/variant'	=> [ self::class, 'apiVariant' ],
				'builder/keys'		=> [ self::class, 'apiKeys' ],
				'builder/upload'	=> [ self::class, 'apiUpload' ],
				'builder/unstage'	=> [ self::class, 'apiUnstage' ],
				'builder/frametexts'	=> [ self::class, 'apiFrameTexts' ],
				'builder/frametextssave'	=> [ self::class, 'apiFrameTextsSave' ],
				'builder/framechoices'	=> [ self::class, 'apiFrameChoices' ],
				'builder/frametile'	=> [ self::class, 'apiFrameTile' ],
				'builder/pageframes'	=> [ self::class, 'apiPageFrames' ],
				'builder/pageframetile'	=> [ self::class, 'apiPageFrameTile' ],
				'builder/scaffold'	=> [ self::class, 'apiScaffold' ],
				'builder/framechoose'	=> [ self::class, 'apiFrameChoose' ],
				'builder/framerestore'	=> [ self::class, 'apiFrameRestore' ],
				'builder/create'	=> [ self::class, 'apiCreate' ],
				'builder/copyof'	=> [ self::class, 'apiCopyOf' ],
				'builder/duplicate'	=> [ self::class, 'apiDuplicate' ],
				'builder/uses'		=> [ self::class, 'apiUses' ],
				'builder/remove'	=> [ self::class, 'apiRemove' ],
			];
		}

		/**
		 *	The permissions of the two steps for the roles tab: the area of each, and the one action that stands for it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function scopes( array &$appData ): array {

			$areas = [];

			// The label of an area is shown as it is, and so the words are the ones of the language of the workbench; the label of an action is a fill key the roles tab
			// resolves itself
			foreach( [ 'content' => \Nino\Modules\Builder::CONTENT, 'design' => \Nino\Modules\Builder::DESIGN ] as $area => $perm )
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
				'builder/frametextssave'	=> 'Save Frame Texts '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				'builder/restore'	=> 'Restore Last Page State '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				'builder/framechoose'	=> 'Choose Frame Block '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ). ' '. Store::shown( \Nino\Admin\Admin::postString( $data, 'key' ) ),
				'builder/scaffold'	=> 'Turn Scaffold '. Store::shown( \Nino\Admin\Admin::postString( $data, 'side' ) ),
				'builder/framerestore'	=> 'Restore Last Frame State '. Store::shown( \Nino\Admin\Admin::postString( $data, 'template' ) ),
				'builder/upload'	=> 'Upload Page Picture '. Store::shown( \Nino\Admin\Admin::postString( $data, 'uri' ) ),
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

			// What was uploaded for a draft of a page that nobody opens any more is taken away with the next look at the list
			$pages = Pages::listed( $appData, $locale );

			foreach( $pages as $page )
				Pictures::sweep( $appData, $page['template'] );

			// A page is made by the one who may design and may write routes: the list says so, and the action asks again
			$create = $level >= \Nino\Modules\Builder::LEVEL_DESIGN && \Nino\Auth::checkPermission( $appData, \Nino\Modules\Builder::ROUTES ) === true;

			\Nino\Http::ok( $request, [ 'pages' => $pages, 'frames' => $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? self::_frames( $appData, $locale ) : [], 'create' => $create, 'bridge' => \Nino\Modules\Builder::bridge( $appData ), 'navs' => \Nino\Pages::navKeys( $appData ) ] + self::_view( $appData, $locale, $level ) );
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

			$templates = array_values( Store::frames( $appData ) );

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

			$token = \Nino\Preview::issue( $appData, ( $appData[\Nino\Modules\Builder::AS] ?? false ) === true ? \Nino\Modules\Builder::KIND_FRAME : \Nino\Modules\Builder::KIND, ( $appData[\Nino\Modules\Builder::AS] ?? false ) === true ? \Nino\Modules\Builder::BRIDGE : self::perm(), [ 'template' => $template, 'source' => $prepared['draft'], 'fills' => $fills, 'slots' => $prepared['shown'], 'locale' => $locale ], \Nino\Admin\Admin::postString( $data, 'token' ) );

			if( $token === '' ) {
				self::_fail( $request, [ 500, 'the preview could not be written', 'builder_preview', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [
				'token'			=> $token,
				'query'			=> \Nino\Preview::QUERY. '='. $token,
				'ttl'				=> \Nino\Preview::TTL,
				'sources'		=> $prepared['sources'],
				'staged'		=> $prepared['staged'],
				'address'		=> self::_address( $appData, $template, $locale, \Nino\Admin\Admin::postString( $data, 'page' ) ),
				'findings'	=> $prepared['findings'],
				'notation'	=> $level >= \Nino\Modules\Builder::LEVEL_DEVELOP ? self::_notation( $appData, $prepared['tree'], $data['look'] ?? null ) : [],
			] + ( $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? [ 'variants' => Library::variants( $appData, $prepared['tree'], \Nino\Components::components( $appData ), is_array( $data['inserted'] ?? null ) === true ? $data['inserted'] : [] ) ] : [] ) + ( \Nino\Modules\Builder::bridge( $appData ) === true ? [ 'own' => Library::owned( $appData, $prepared['tree'], \Nino\Components::components( $appData ) ) ] : [] ) );
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

			self::_commit( $appData, $request, $template, $state, $prepared, $level );
		}

		/**
		 *	A draft that was prepared, written: what changed - the template, the words, the slots - is committed under the lock of the store, and the answer is what the screen needs
		 *	of the state that stands now; a draft that changes nothing is answered as saved and writes nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		string		$template
		 *	@param		array			$state				The state the draft was made against
		 *	@param		array			$prepared			Store::prepare()
		 *	@param		int				$level
		 *
		 *	@return 	void
		 */
		private static function _commit( array &$appData, array &$request, string $template, array $state, array $prepared, int $level ): void {

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

			// A frame the restored state names that the site has no more: the page says so at once - the website draws the standard one
			$gone = [];

			foreach( Tree::read( $appData, $target['source'] ) as $node )
				if( ( $choice = Tree::frameChoice( $node ) ) !== null && in_array( $choice[1], [ 'default', 'none' ], true ) === false && in_array( Store::FRAMES[$choice[0]]. '-'. $choice[1], Store::frames( $appData ), true ) === false )
					$gone[] = $choice[1];

			\Nino\Http::ok( $request, [ 'restored' => true, 'missingFrames' => array_values( array_unique( $gone ) ) ] + self::_after( $appData, $template, $level ) );
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
				$why = Tree::refused( $back, $nodes, $registry );
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

		/**
		 *	The root a block must have to be offered in the editor that is asked: a frame of the site is a block of its own, and only a block of its own kind stands in its place
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	string
		 */
		private static function _wrap( array &$appData ): string {
			return ( $appData[\Nino\Modules\Builder::AS] ?? false ) === true ? Library::wrapOf( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) ) : Library::PAGE;
		}

		public static function apiBlocks( array &$appData, array &$request ): void {

			if( self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN ) === null )
				return;

			\Nino\Http::ok( $request, Library::all( $appData, self::_wrap( $appData ) ) );
		}

		public static function apiBlock( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$block = Library::block( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'key' ), self::_wrap( $appData ) );

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

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			self::_tile( $appData, $request, $level );
		}

		/**
		 *	The picture of a block for the choice of a frame, from the list of the pages: the same tile as the dialog of the blocks draws - the block alone on the bare page - for a block of the
		 *	kind of the frame. The door is the one of the choice
		 */
		public static function apiFrameTile( array &$appData, array &$request ): void {

			if( self::_frameDoor( $appData, $request ) === null )
				return;

			self::_tile( $appData, $request, \Nino\Modules\Builder::LEVEL_DEVELOP );
		}

		/**
		 *	The draft of a tile, held for the session, and where to ask for it: the answer of apiTile() and apiFrameTile(). A frame's tile is for a block of the kind of that frame
		 */
		private static function _tile( array &$appData, array &$request, int $level ): void {

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, , , $locale ] = $opened;
			$tile = Library::tile( $appData, $template, \Nino\Admin\Admin::postString( $data, 'key' ), $locale, $level >= \Nino\Modules\Builder::LEVEL_DESIGN, Library::wrapOf( $template ) );

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

		// Where a component stands in the dialog that offers them - the one place that says it, for the blocks, the frames and every other place a component is put in. It is the
		// dialog's own order and no group of the register, which belongs to the themes: a part of a section, a part of a card, what is of a group of components, the loops, what a
		// feature brings under the name of the feature, and the rest as general
		private const array DIALOG = [
			'section-row'			=> 'section',
			'section-col'			=> 'section',
			'section-title'		=> 'section',
			'section-subtitle'	=> 'section',
			'card'						=> 'card',
			'card-title'			=> 'card',
			'card-subtitle'		=> 'card',
			'card-text'				=> 'card',
			'card-image'			=> 'card',
			'text'						=> 'general',
			'button'					=> 'general',
			'image'						=> 'general',
			'logo'						=> 'general',
			'html'						=> 'general',
			'spacer'					=> 'general',
			'scroll-hint'			=> 'general',
			'button-group'		=> 'groups',
			'tabs'						=> 'groups',
			'accordion'				=> 'groups',
			'collection-filter'	=> 'loops',
		];

		// The order of the tiles in a part of the dialog, the common ones first: this is the one place that says it, as DIALOG says the parts. What is not named stands behind them
		// in the order the register has it, and the filter, which holds loops and is none, is the last of the loops
		private const array DIALOG_ORDER = [ 'section-title', 'section-subtitle', 'section-row', 'section-col', 'card', 'card-title', 'card-subtitle', 'card-text', 'card-image', 'text', 'button', 'image', 'logo', 'spacer', 'scroll-hint', 'html', 'button-group', 'tabs', 'accordion', 'collection', 'collection-list', 'collection-table', 'collection-filter' ];

		/**
		 *	The part of the dialog a component is in
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$name					The component
		 *	@param		bool			$loop					Whether it is a loop
		 *
		 *	@return 	array											'section' (section, card, general, features, groups or loops), 'feature' (the key of the feature, for features), 'featureLabel' (its name)
		 */
		public static function dialogGroup( array &$appData, string $name, bool $loop ): array {

			$owner = $appData[\Nino\Components::OWNERS]['names'][$name] ?? null;
			$feature = is_string( $owner ) === true && $owner !== 'basis' ? $owner : null;
			$section = self::DIALOG[$name] ?? ( $loop === true ? 'loops' : ( $feature !== null ? 'features' : 'general' ) );

			// A feature is told by its name where it brings a component of its own: the loops are the loops whoever brings them
			$manifest = $feature === null || $section !== 'features' ? null : \Nino\Features::get( $appData, $feature );

			return [ 'section' => $section, 'feature' => $section === 'features' ? $feature : null, 'featureLabel' => $manifest === null ? null : ( $manifest['name'] ?? $feature ) ];
		}

		/**
		 *	What the register offers at a place of a tree: each component that may stand there, as the call it would be, with the schemas of them - the answer of the action
		 *	"offer", for any panel that edits a tree with the code of the Builder. The html is offered to the step of the development and to no other
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$nodes				The tree
		 *	@param		string		$at						The place of the container, 'a.b.c'
		 *	@param		int				$level				The step the account holds
		 *
		 *	@return 	array|null								'offers', 'blank', 'schemas', 'container' - null where the register has no place for a component there
		 */
		public static function offered( array &$appData, array $nodes, string $at, int $level ): ?array {

			$registry = \Nino\Components::components( $appData );
			$route = Tree::route( $nodes, $at );
			$container = Tree::at( $nodes, $at );

			if( $route === null || $container === null || isset( $registry[$route[count( $route ) - 1]]['offers'] ) === false )
				return null;

			$groups = \Nino\Components::groups( $appData );
			$entries = [];
			$names = [];
			$offered = \Nino\Components::offer( $appData, $route, (array) ( $container['c'] ?? [] ) );

			// What the register has that this place does not take is in the dialog as well, grey and with its reason: the person sees what there is, and why not here. The
			// frames' own parts and what stands at the top of a page - a section - are no choice of any place inside a block
			foreach( $registry as $name => $schema )
				if( isset( $offered[$name] ) === false && in_array( (string) ( $schema['group'] ?? '' ), [ 'header', 'footer' ], true ) === false && ( $schema['in'] ?? [] ) !== [ 'page' ] && ( $name !== 'html' || $level >= \Nino\Modules\Builder::LEVEL_DEVELOP ) )
					$offered[$name] = 'place';

			$schemas = Tree::schemas( $appData, [], $registry, array_map( 'strval', array_keys( $offered ) ) );

			foreach( $offered as $name => $answer ) {

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
					'icon'				=> (string) ( $schema['icon'] ?? '' ),
					'group'				=> (string) ( $schema['group'] ?? '' ),
					'groupLabel'	=> $groups[$schema['group'] ?? '']['label'] ?? null,
				] + self::dialogGroup( $appData, (string) $name, $schemas[$name]['loop'] ?? false ) + [
					'ok'					=> $answer === true,
					'why'					=> $answer === true ? '' : (string) $answer,
					'node'				=> [ 'n' => (string) $name, 's' => '', 'a' => [], 'c' => isset( $schema['offers'] ) === true ? [] : null ],
				];
			}

			// The tiles of a part stand in the order of DIALOG_ORDER, and a tile that is named like another says what it is a part of
			$rank = static function( string $name ) use ( $entries ): int {

				$at = array_search( $name, self::DIALOG_ORDER, true );

				return $at === false ? 1000 + (int) array_search( $name, array_column( $entries, 'name' ), true ) : (int) $at;
			};
			usort( $entries, static fn( array $one, array $other ): int => $rank( $one['name'] ) <=> $rank( $other['name'] ) );

			// Of two tiles with one name the one a feature brings says what it is a part of; where no feature is in it, both do
			$named = $entries;

			foreach( $named as $index => $entry ) {

				$same = array_filter( $named, static fn( array $other ): bool => $other['name'] !== $entry['name'] && $other['label'] == $entry['label'] );
				$mine = ( $appData[\Nino\Components::OWNERS]['names'][$entry['name']] ?? 'basis' ) !== 'basis';
				$theirs = array_filter( $same, static fn( array $other ): bool => ( $appData[\Nino\Components::OWNERS]['names'][$other['name']] ?? 'basis' ) !== 'basis' );

				if( $same === [] || ( $mine === false && $theirs !== [] ) )
					continue;

				$parent = null;

				foreach( (array) ( $registry[$entry['name']]['in'] ?? [] ) as $above )
					if( isset( $registry[$above] ) === true ) {
						$parent = $registry[$above]['label'];
						break;
					}

				if( is_array( $parent ) === true && is_array( $entry['label'] ) === true )
					foreach( $entry['label'] as $locale => $text )
						$entries[$index]['label'][$locale] = $text. ' ('. ( $parent[$locale] ?? reset( $parent ) ). ')';
			}

			// What stands between two calls at this depth, as the kernel writes a template: the blanks of a new call come from there
			$call = [ 'n' => 'spacer', 's' => '', 'a' => [], 'c' => null ];
			$laid = \Nino\Markup::layout( [ $call, $call ], count( $route ) );

			return [ 'offers' => $entries, 'blank' => (string) ( $laid[1]['t'] ?? "\n" ), 'schemas' => $schemas, 'container' => $route[count( $route ) - 1] ];
		}

		/**
		 *	The variant of a block of the page: the one who designs names the place of the block in the draft and the name of a variant, and the answer is the block with that variant
		 *	set. The block of the catalogue and the variant it has are told from the tree and from the catalogue - the request names neither - and what is set is what
		 *	\Nino\Blocks::setVariant() makes of a block that was checked: nothing else of the draft is touched, and the draft that comes of it is held to the rule of a draft
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function apiVariant( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state ] = $opened;
			$canonical = Tree::canonical( $appData, $data['tree'] ?? null );
			$at = \Nino\Admin\Admin::postString( $data, 'at' );
			$name = \Nino\Admin\Admin::postString( $data, 'name' );
			$registry = \Nino\Components::components( $appData );

			if( $canonical === null || preg_match( '/^(?:0|[1-9]\d{0,5})$/D', $at ) !== 1 || isset( $canonical[1][(int) $at]['n'] ) === false ) {
				self::_fail( $request, [ 400, 'there is no block there', 'builder_place', [], 'at' ] );
				return;
			}

			$node = $canonical[1][(int) $at];
			$origin = Library::origin( $appData, $node, $registry, is_array( $data['inserted'] ?? null ) === true ? $data['inserted'] : [] );

			if( $origin === null || array_key_exists( $name, array_column( $origin['items'], 1, 0 ) ) === false ) {
				self::_fail( $request, [ 403, 'the block has no such variant', 'builder_variant', [], 'name' ] );
				return;
			}

			$tree = $canonical[1];
			$tree[(int) $at] = \Nino\Blocks::setVariant( $origin['block'], [ $node ], $name )[0];

			// The draft that comes of it is a draft like any other: held to the rule of the step, and nothing of it is written here
			$prepared = Store::prepare( $appData, $template, $state, $tree, $data['texts'] ?? [], $data['slots'] ?? [], $data['inserted'] ?? [], $level, $data['pictures'] ?? [] );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			\Nino\Http::ok( $request, [ 'node' => $tree[(int) $at], 'variant' => \Nino\Blocks::variantOf( $origin['block'], [ $tree[(int) $at] ] ) ] );
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

				\Nino\Http::ok( $request, self::fieldsOf( $appData, \Nino\Admin\Admin::postString( $data, 'type' ), $locale ) );
				return;
			}

			// What a loop may show: the types of elements the project has, with their titles - the example type of the kernel is none of them
			if( \Nino\Admin\Admin::postString( $data, 'kind' ) === 'types' ) {

				\Nino\Http::ok( $request, [ 'types' => self::typesOf( $appData ) ] );
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

		/**
		 *	A block of the page as a block of the project: private/blocks/<key>/, only if the kernel takes it as one - the page is not changed. The caller held the right of the editor of
		 *	the blocks, and this panel asks for its own door and step for the page; whether a link may name a page the site has not yet is not for a request to say
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	void
		 */
		public static function adopt( array &$appData, array &$request ): void {

			// The page is read by the door and the step of this panel, as every action on a page does: the right of the editor of the blocks alone opens no page
			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			unset( $data['lenient'] );
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

		/**
		 *	A frame of the site as a block of the project: private/blocks/<key>/, the frame as it is SAVED - the file of its template and the words of it - mapped by the one rule that
		 *	maps a block of a page, and only if the kernel takes it as a block; the frame is not changed. Only the editor of the blocks reaches it (its mark is the whole of the door: a frame is
		 *	no business of this panel for anybody), and the request names a frame of the site and the key, the names and the descriptions - never what is in the frame
		 */
		public static function apiFrameAdopt( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_CONTENT );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, $nodes ] = $opened;
			$at = null;

			foreach( $nodes as $index => $node )
				if( isset( $node['n'] ) === true && $at === null )
					$at = $index;

			if( $at === null ) {
				self::_fail( $request, [ 409, 'the frame holds no block', 'builder_unreadable', [ Store::shown( $template ), '' ], 'template' ] );
				return;
			}

			// A name and a description are a text, or a text for each language: the one of the language of the request stands for the ones that are not given
			$words = static function( mixed $given ) use ( $opened ): array {

				$map = is_array( $given ) === true ? array_filter( $given, 'is_string' ) : [];

				return [ is_string( $given ) === true ? trim( $given ) : trim( (string) ( $map[$opened[3]] ?? '' ) ), $map ];
			};
			[ $name, $names ] = $words( $data['name'] ?? '' );
			[ $about, $abouts ] = $words( $data['description'] ?? '' );
			$made = Custom::save( $appData, $template, $state, $nodes, [ 'at' => (string) $at, 'key' => \Nino\Admin\Admin::postString( $data, 'key' ), 'name' => $name, 'names' => $names, 'description' => $about, 'descriptions' => $abouts, 'category' => 'frame', 'locale' => $opened[3], 'fill' => \Nino\Admin\Admin::postString( $data, 'fill' ), 'check' => ( $data['check'] ?? false ) === true ] );

			if( isset( $made['failure'] ) === true ) {
				self::_fail( $request, $made['failure'] );
				return;
			}

			\Nino\Http::ok( $request, $made );
		}

		/**
		 *	The door of the texts of a frame: this panel, the content step and a template that is a frame - nothing of the structure of a frame is in what it answers or takes
		 *
		 *	@param		array 		&$appData
		 *	@param		array			&$request
		 *
		 *	@return 	int|null									The step; null after the answer that says why not
		 */
		private static function _gateTexts( array &$appData, array &$request ): ?int {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return null;

			$level = \Nino\Modules\Builder::level( $appData );

			if( $level < \Nino\Modules\Builder::LEVEL_CONTENT ) {
				self::_fail( $request, self::_level() );
				return null;
			}

			if( ( Store::named( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) )['frame'] ?? false ) !== true ) {
				self::_fail( $request, [ 400, 'the request names no frame of the site', 'builder_unknown', [], 'template' ] );
				return null;
			}

			return $level;
		}

		/**
		 *	The texts a frame holds as its own: the key, what the component is, the words in each language. The tree is the frame's as it is saved and never one a request brings
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$template
		 *	@param		array			$state
		 *
		 *	@return 	array											[ [ key, label, values ] ]
		 */
		private static function _frameTexts( array &$appData, string $template, array $state ): array {

			$nodes = Tree::read( $appData, $state['source'] );
			$registry = \Nino\Components::components( $appData );
			$out = [];

			foreach( Tree::sources( $nodes, $registry, $template ) as $source )
				if( $source['kind'] === 'text' && $source['editable'] === true && $source['key'] !== '' && isset( $out[$source['key']] ) === false )
					$out[$source['key']] = [ 'key' => $source['key'], 'label' => $source['name'], 'values' => (object) ( $state['texts'][$source['key']] ?? [] ) ];

			return array_values( $out );
		}

		public static function apiFrameTexts( array &$appData, array &$request ): void {

			if( self::_gateTexts( $appData, $request ) === null )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$template = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' );
			$state = Store::state( $appData, $template );

			if( $state === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'texts' => self::_frameTexts( $appData, $template, $state ), 'stamp' => Store::stamp( $state ) ] + self::_view( $appData, $locale, \Nino\Modules\Builder::LEVEL_CONTENT ) );
		}

		/**
		 *	The texts of a frame written: the words of the keys that the frame reads as its own, and nothing else - the tree is the one that is saved, the keys are the ones the
		 *	register finds in it, and the step is the account's own
		 */
		public static function apiFrameTextsSave( array &$appData, array &$request ): void {

			$level = self::_gateTexts( $appData, $request );

			if( $level === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$template = \Nino\Admin\Admin::postString( $data, 'template' );
			$state = Store::state( $appData, $template );

			if( $state === null ) {
				self::_fail( $request, [ 404, 'there is no such page', 'builder_unknown', [ Store::shown( $template ) ], 'template' ] );
				return;
			}

			$own = array_column( self::_frameTexts( $appData, $template, $state ), 'key' );
			$given = is_array( $data['texts'] ?? null ) === true ? $data['texts'] : [];

			if( array_diff( array_keys( $given ), $own ) !== [] ) {
				self::_fail( $request, [ 403, 'the frame reads no such text', 'builder_refused_source', [], 'texts' ] );
				return;
			}

			if( ( $data['force'] ?? false ) !== true && ( is_string( $data['base'] ?? null ) === false || hash_equals( Store::stamp( $state ), $data['base'] ) === false ) ) {
				self::_fail( $request, self::_changed() );
				return;
			}

			$prepared = Store::prepare( $appData, $template, $state, Tree::read( $appData, $state['source'] ), $given, [], [], $level, [] );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			$changes = Store::changes( $state, $prepared, $template );
			$target = [ 'source' => $state['source'], 'texts' => $state['texts'], 'slots' => $state['slots'] ];

			foreach( $changes['set'] as $key => $values )
				$target['texts'][$key] = $values + ( $state['texts'][$key] ?? [] );

			foreach( $changes['drop'] as $key )
				unset( $target['texts'][$key] );

			ksort( $target['texts'] );

			if( $changes['set'] !== [] || $changes['drop'] !== [] ) {

				$failed = Store::commit( $appData, $template, $state, $target, $changes, [], null, [], $level );

				if( $failed !== null ) {
					self::_fail( $request, self::_unsaved( $failed ) );
					return;
				}
			}

			$now = Store::state( $appData, $template ) ?? $state;

			\Nino\Http::ok( $request, [ 'saved' => true, 'stamp' => Store::stamp( $now ), 'texts' => self::_frameTexts( $appData, $template, $now ) ] );
		}

		/**
		 *	The two frames of the site as the list of the pages shows them, for the one who designs: which frame, whether there is a last state to go back to, and where the way to the
		 *	editor of the frames is open - Themelab is on and the account holds its right
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											[ [ key, template, last ] ]
		 */
		private static function _frames( array &$appData, string $locale ): array {

			$out = [];

			foreach( Store::frameList( $appData, $locale ) as $entry )
				if( ( $state = Store::state( $appData, $entry['template'] ) ) !== null ) {

					$template = $entry['template'];
					$stamp = Store::stamp( $state );

					// The way back is for the frame the choice wrote: as long as the frame is that one, and no other
					$out[] = $entry + [ 'stamp' => $stamp, 'last' => self::_last( $appData, $template ), 'undo' => self::_last( $appData, $template ) > 0 && self::_chosen( $appData, $template ) === $stamp ];
				}

			return $out;
		}

		// Where the stamp of the frame that a choice wrote is kept, to tell the frame the choice made from one that was changed since
		private const string CHOSEN = Store::FRAME_MARK;

		private static function _chosen( array &$appData, string $template ): string {

			$kept = \Nino\Filesystem::getFileContent( $appData, self::CHOSEN. '/'. $template. '.php', [] );

			return is_array( $kept ) === true && is_string( $kept['stamp'] ?? null ) === true ? $kept['stamp'] : '';
		}

		/**
		 *	A request for a frame that comes from a list that saw another frame than the one that stands: the stamp it names is not the stamp of the frame now - a save in the editor of
		 *	the frames came between - so nothing is written, and the answer says so
		 */
		private static function _frameChanged(): array {
			return [ 409, 'the frame was changed since the list was drawn', 'builder_frame_changed', [], '' ];
		}

		/**
		 *	The door of the choice of a frame: the panel, the step of the design and a template that is a frame of the site - the choice is the design's and no other step has it,
		 *	and no other template is a frame
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *
		 *	@return 	string|null								The template; null after the answer that says why not
		 */
		private static function _frameDoor( array &$appData, array &$request ): ?string {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return null;

			if( \Nino\Modules\Builder::level( $appData ) < \Nino\Modules\Builder::LEVEL_DESIGN ) {
				self::_fail( $request, self::_level() );
				return null;
			}

			$template = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' );

			if( Store::isFrame( $appData, $template ) === false ) {
				self::_fail( $request, [ 400, 'the request names no frame of the site', 'builder_unknown', [], 'template' ] );
				return null;
			}

			return $template;
		}

		/**
		 *	What a page chooses for its frame: of the header and of the footer the choice it has now - the standard one, none, or the word of a named frame - and what there is to choose from,
		 *	and whether the scaffold of the site carries the place for the choice at all. The door is the one of the design of a page
		 */
		public static function apiPageFrames( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			$current = [ 'header' => null, 'footer' => null ];
			$locked = [ 'header' => false, 'footer' => false ];

			foreach( Tree::read( $appData, (string) $opened[1]['source'] ) as $node ) {

				if( ( $choice = Tree::frameChoice( $node ) ) !== null )
					$current[$choice[0]] = $choice[1];
				else if( ( $node['n'] ?? '' ) === 'template' && in_array( $node['s'] ?? '', Tree::FRAME_CALLS, true ) === true ) {

					$side = $node['s'] === Tree::FRAME_CALLS[0] ? 'header' : 'footer';
					$current[$side] = 'default';

					// A call of the scaffold with an attribute or a content is one that cannot be rewritten as the call of a frame without changing what it does
					if( ( $node['a'] ?? [] ) !== [] || ( $node['c'] ?? null ) !== null )
						$locked[$side] = true;
				}
				else if( ( $node['n'] ?? '' ) === 'frame' && preg_match( '#^/templates/frame-(header|footer)(?:-|$)#', (string) ( $node['s'] ?? '' ), $found ) === 1 )
					$locked[$found[1]] = true;
			}

			$words = [ 'header' => [], 'footer' => [] ];

			foreach( Store::frames( $appData ) as $key => $template )
				$words[(string) Store::side( $template )][] = $key === Store::side( $template ) ? 'default' : substr( $template, strlen( 'frame-'. Store::side( $template ) ) + 1 );

			$framed = Store::framed( $appData );
			$sides = [];

			foreach( [ 'header', 'footer' ] as $side ) {

				// The frame the page chooses is a file the site has no more: the website draws the standard one, and the page says which word it is
				$gone = $current[$side] !== null && in_array( $current[$side], [ 'default', 'none' ], true ) === false && in_array( $current[$side], $words[$side], true ) === false;
				$sides[$side] = [ 'locked' => $locked[$side], 'missing' => $gone, 'current' => $current[$side], 'options' => array_merge( array_values( array_filter( $words[$side], static fn( string $word ): bool => $word !== 'default' || is_file( \Nino\Filesystem::path( $appData, '/templates/'. Store::FRAMES[$side]. '.tpl' ) ) === true ) ), [ 'none' ] ), 'framed' => ( $framed[$side] ?? false ) === true ];
			}

			$locale = self::_locale( $appData, $request );
			$called = [ 'header' => [], 'footer' => [] ];

			foreach( Store::frameList( $appData, $locale ) as $entry )
				if( $entry['default'] === false && $entry['name'] !== '' )
					$called[$entry['side']][substr( $entry['template'], strlen( 'frame-'. $entry['side'] ) + 1 )] = $entry['name'];

			\Nino\Http::ok( $request, [ 'sides' => $sides, 'names' => $called, 'level' => $level ] );
		}

		/**
		 *	The picture of a frame for the choice of a page: the scaffold with that frame in it and the other side empty, drawn the way the tiles of the blocks are. The side and the word are
		 *	what the request names; the call is built here, from a word of a frame of the site and no other
		 */
		public static function apiPageFrameTile( array &$appData, array &$request ): void {

			$level = self::_gate( $appData, $request, \Nino\Modules\Builder::LEVEL_DESIGN );

			if( $level === null )
				return;

			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$side = \Nino\Admin\Admin::postString( $data, 'side' );
			$word = \Nino\Admin\Admin::postString( $data, 'word' );
			$template = $word === 'default' ? ( Store::FRAMES[$side] ?? '' ) : ( Store::FRAMES[$side] ?? 'x' ). '-'. $word;

			if( isset( Store::FRAMES[$side] ) === false || Store::isFrame( $appData, $template ) === false ) {
				self::_fail( $request, [ 404, 'there is no such frame', 'builder_unknown', [], 'word' ] );
				return;
			}

			$call = static fn( string $of, string $name ): string => '[frame /templates/'. $name. ']';
			$source = $call( 'header', $side === 'header' ? $template : 'frame-header-none' ). "\n". $call( 'footer', $side === 'footer' ? $template : 'frame-footer-none' ). "\n";
			$tile = [ 'template' => $opened[0], 'source' => $source, 'fills' => [], 'slots' => [], 'locale' => $opened[3] ];
			$token = \Nino\Preview::issue( $appData, Library::KIND, self::perm(), $tile, \Nino\Admin\Admin::postString( $data, 'token' ), Library::TILES );

			if( $token === '' ) {
				self::_fail( $request, [ 500, 'the preview could not be written', 'builder_preview', [], '' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'token' => $token, 'query' => \Nino\Preview::QUERY. '='. $token, 'ttl' => \Nino\Preview::TTL, 'address' => self::_address( $appData, $opened[0], $opened[3] ) ] );
		}

		/**
		 *	The scaffold of a site that is older - html-header.tpl or html-footer.tpl draws its frame by [template /templates/frame-header] - turned to the one that carries the place for the choice of
		 *	a page, [[header]] or [[footer]]: that one call and nothing else, only where the file holds it exactly once and the scaffold does not carry the place yet. The door is the one that writes a
		 *	frame: the right of this panel and the step of the design
		 */
		public static function apiScaffold( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return;

			if( \Nino\Modules\Builder::level( $appData ) < \Nino\Modules\Builder::LEVEL_DESIGN ) {
				self::_fail( $request, self::_level() );
				return;
			}

			$side = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'side' );

			if( isset( Store::FRAMES[$side] ) === false ) {
				self::_fail( $request, [ 400, 'the request names no side of the page', 'builder_unknown', [], 'side' ] );
				return;
			}

			$file = '/templates/html-'. $side. '.tpl';
			$call = '/templates/'. Store::FRAMES[$side];
			$old = '[template '. $call. ']';
			$new = '[['. $side. ']]';
			$source = \Nino\Filesystem::getFileContent( $appData, $file, null );
			$framed = Store::framed( $appData );

			// What is in a comment of html is blanked, to the byte, before the text is searched: the place of the call is the place in the text, and a call that the notation reads inside a
			// comment is none that the turn counts
			$bare = is_string( $source ) === true ? (string) preg_replace_callback( '/<!--.*?-->/s', static fn( array $found ): string => str_repeat( ' ', strlen( $found[0] ) ), $source ) : '';

			// The calls are read as the notation reads them: a call that is written in another way - with an attribute, with blanks - is one the text search would not find, which the turn
			// does not touch: it is told, with the two lines to do by hand
			$real = 0;
			$walk = static function( array $nodes ) use ( &$walk, &$real, $call, $bare ): void {

				foreach( $nodes as $node )
					if( isset( $node['n'] ) === true ) {

						if( $node['n'] === 'template' && ( $node['s'] ?? '' ) === $call && ( $bare[(int) ( $node['o'] ?? -1 )] ?? '' ) === '[' )
							$real++;

						if( is_array( $node['c'] ?? null ) === true )
							$walk( $node['c'] );
					}
			};

			if( is_string( $source ) === true )
				$walk( \Nino\Markup::parse( $source, \Nino\Components::containers( $appData ) ) );

			$at = strpos( $bare, $old );

			if( is_string( $source ) === false || ( $framed[$side] ?? false ) === true || $real !== 1 || $at === false || substr_count( $bare, $old ) !== 1 ) {
				self::_fail( $request, [ 409, 'the scaffold does not carry the call exactly once', 'builder_scaffold', [ 'html-'. $side. '.tpl', $old, $new ], 'side' ] );
				return;
			}

			// The file as it was is kept beside the others of the Builder, so that there is a way back; and the turn is the turn of the notation too: where the scaffold does not carry the
			// place afterwards, the file is as it was
			$kept = Store::SCAFFOLD. '/html-'. $side. '.tpl';
			\Nino\Filesystem::putFileContent( $appData, $kept, $source );
			\Nino\Filesystem::putFileContent( $appData, $file, substr_replace( $source, $new, $at, strlen( $old ) ) );
			unset( $appData['./nino/html/cache'] );
			$now = Store::framed( $appData );

			if( ( $now[$side] ?? false ) !== true ) {

				\Nino\Filesystem::putFileContent( $appData, $file, $source );
				self::_fail( $request, [ 409, 'the scaffold does not carry the call exactly once', 'builder_scaffold', [ 'html-'. $side. '.tpl', $old, $new ], 'side' ] );
				return;
			}

			\Nino\Http::ok( $request, [ 'side' => $side, 'kept' => 'private'. $kept ] );
		}

		/**
		 *	The blocks a frame can be given: the blocks of its kind - a header or a footer - of every source the kernel lists, the project's own and those of every installed theme, each
		 *	with where it comes from
		 */
		public static function apiFrameChoices( array &$appData, array &$request ): void {

			$template = self::_frameDoor( $appData, $request );

			if( $template === null )
				return;

			\Nino\Http::ok( $request, Library::all( $appData, Library::wrapOf( $template ) ) );
		}

		/**
		 *	A frame given a block of its kind: the block that stands in the frame is the one that is chosen - the kind of it, where it comes from and the rule of the kernel are asked of
		 *	\Nino\Blocks, never of the request, which names only the frame and the key - and the frame is written as the editor of the frames writes it, with the words of the block
		 *	under the ids it has in the frame. The last state is kept, so that the choice can be taken back
		 */
		public static function apiFrameChoose( array &$appData, array &$request ): void {

			if( self::_frameDoor( $appData, $request ) === null )
				return;

			$level = \Nino\Modules\Builder::LEVEL_DEVELOP;
			$opened = self::_open( $appData, $request, $level );

			if( $opened === null )
				return;

			[ $template, $state, $nodes, $locale ] = $opened;
			$data = \Nino\Admin\Admin::postData();

			// The frame the list saw is the frame that is replaced: a frame that is another now - a save in the editor came between - is not written over
			if( is_string( $data['base'] ?? null ) === false || hash_equals( Store::stamp( $state ), $data['base'] ) === false ) {
				self::_fail( $request, self::_frameChanged() );
				return;
			}

			$key = \Nino\Admin\Admin::postString( $data, 'key' );
			$block = Library::block( $appData, $key, Library::wrapOf( $template ) );

			if( $block === null ) {
				self::_fail( $request, [ 404, 'there is no such block', 'builder_block', [], 'key' ] );
				return;
			}

			$at = null;

			foreach( $nodes as $index => $node )
				if( isset( $node['n'] ) === true && $at === null )
					$at = $index;

			if( $at === null ) {
				self::_fail( $request, [ 409, 'the frame holds no block', 'builder_unreadable', [ Store::shown( $template ), '' ], 'template' ] );
				return;
			}

			// The frame stands on the id of the block and nothing else stands in it: the words and the picture places of the block are the frame's under the same ids
			$tree = $nodes;
			$tree[$at] = $block['tree'][0];
			$native = \Nino\Locales::getNativeLocale( $appData );
			$texts = [];
			$pictures = [];

			foreach( $block['slots'] as $slot ) {

				$id = $slot['scope'] === '' ? $block['id'] : $block['id']. '-'. $slot['scope'];
				$place = '/template/'. $template. '/'. $id. '/'. $slot['word'];

				if( $slot['kind'] === 'image' ) {

					$spec = $block['images'][$slot['name']] ?? null;

					if( is_array( $spec ) === true ) {

						$label = (array) ( $spec['label'] ?? [] );
						$pictures[$place] = [ 'label' => (string) ( $label[$locale] ?? $label['en_US'] ?? ( $label === [] ? $slot['word'] : reset( $label ) ) ), 'width' => (int) $spec['width'], 'height' => (int) $spec['height'] ];
					}

					continue;
				}

				foreach( \Nino\Locales::getTextLocales( $appData ) as $code ) {

					$own = $block['texts'][$code][$slot['name']] ?? null;
					$text = is_string( $own ) === true ? $own : ( $code === $native ? ( $block['texts']['en_US'][$slot['name']] ?? null ) : null );

					if( is_string( $text ) === true )
						$texts[$place][$code] = $text;
				}
			}

			$prepared = Store::prepare( $appData, $template, $state, $tree, $texts, [], [ $block['id'] => $key ], $level, $pictures );

			if( isset( $prepared['failure'] ) === true ) {
				self::_fail( $request, $prepared['failure'] );
				return;
			}

			self::_commit( $appData, $request, $template, $state, $prepared, $level );

			// The frame that the choice wrote, by its stamp: only this frame is one the way back may restore
			if( ( $request['/nino/http/response']['statusCode'] ?? 200 ) === 200 && ( $now = Store::state( $appData, $template ) ) !== null )
				\Nino\Filesystem::putFileContent( $appData, self::CHOSEN. '/'. $template. '.php', [ 'stamp' => Store::stamp( $now ) ] );
		}

		/**
		 *	The frame as it was before the last choice or save: the last state, put back. The door is the choice's - the design - and the write is the one of the restore of a page, which
		 *	takes a frame for the length of this call only
		 */
		public static function apiFrameRestore( array &$appData, array &$request ): void {

			$template = self::_frameDoor( $appData, $request );

			if( $template === null )
				return;

			$state = Store::state( $appData, $template );
			$data = \Nino\Admin\Admin::postData();
			$stamp = $state === null ? '' : Store::stamp( $state );

			// The way back is for the frame the choice wrote and for the frame the list saw: a frame that was changed since is left as it is
			if( $stamp === '' || is_string( $data['base'] ?? null ) === false || hash_equals( $stamp, $data['base'] ) === false || self::_chosen( $appData, $template ) !== $stamp ) {
				self::_fail( $request, self::_frameChanged() );
				return;
			}

			$appData[\Nino\Modules\Builder::AS] = true;

			try {
				self::apiRestore( $appData, $request );
			}
			finally {
				unset( $appData[\Nino\Modules\Builder::AS] );
			}
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
		 *	@param		int				$level				The step the account holds: a frame is for the step of the editor of the blocks
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

			// A frame is written in the editor of the blocks and nowhere else: the panel of the pages has none for anybody
			if( Store::named( $template )['frame'] === true && $level < \Nino\Modules\Builder::LEVEL_DEVELOP ) {
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
				+ [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ) ] + ( $level >= \Nino\Modules\Builder::LEVEL_DESIGN ? [ 'findings' => Tree::findings( $appData, $nodes ), 'variants' => Library::variants( $appData, $nodes, $registry ) ] : [] );
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
				return [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ), 'titles' => self::_titles( $appData, $nodes ) ];

			$links = [];

			foreach( \Nino\Pages::all( $appData ) as $page )
				if( preg_match( '#^/(?:[A-Za-z0-9._~-]+(?:/[A-Za-z0-9._~-]+)*)?$#D', (string) $page['uri'] ) === 1 )
					$links[] = [ 'uri' => (string) $page['uri'], 'name' => (string) ( $page['names'][$locale] ?? '' ) !== '' ? (string) $page['names'][$locale] : (string) $page['uri'] ];

			return [ 'schemas' => Tree::schemas( $appData, $nodes, $registry ), 'findings' => Tree::findings( $appData, $nodes ), 'links' => $links, 'variants' => Library::variants( $appData, $nodes, $registry ), 'titles' => self::_titles( $appData, $nodes ) ];
		}

		/**
		 *	The titles of the types of elements the loops of a page show - only those, for every step: a person who may write the content reads "Team" where the loop says /team
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$nodes
		 *
		 *	@return 	array											'/type' => title
		 */
		private static function _titles( array &$appData, array $nodes ): array {

			$used = [];
			$walk = static function( array $list ) use ( &$walk, &$used ): void {

				foreach( $list as $node )
					if( isset( $node['n'] ) === true ) {

						if( is_string( $node['s'] ?? null ) === true && preg_match( '/^\/[a-z][a-z0-9_-]{0,63}$/D', $node['s'] ) === 1 )
							$used[$node['s']] = true;

						if( is_array( $node['c'] ?? null ) === true )
							$walk( $node['c'] );
					}
			};

			$walk( $nodes );

			if( $used === [] )
				return [];

			$titles = [];

			foreach( \Nino\Modules\Elements\Types::summaries( $appData ) as $type )
				if( isset( $used['/'. $type['uri']] ) === true && (string) $type['title'] !== '' )
					$titles['/'. $type['uri']] = (string) $type['title'];

			return $titles;
		}

		/**
		 *	The notation of the whole block and of the first line of a component, for any panel that shows it beside the settings
		 *
		 *	@param		array 		&$appData
		 *	@param		array			$nodes				The tree of the draft
		 *	@param		mixed			$look					[ 'block' => position, 'node' => position ]
		 *
		 *	@return 	array											'block' and 'node'
		 */
		public static function notationOf( array &$appData, array $nodes, mixed $look ): array {
			return self::_notation( $appData, $nodes, $look );
		}

		/**
		 *	The fields of the elements of a type and the words the model gives them: for the source that is a field of the element of a loop
		 *
		 *	@param		array 		&$appData
		 *	@param		string		$type					'/team'
		 *	@param		string		$locale
		 *
		 *	@return 	array											'fields' and 'labels'
		 */
		public static function fieldsOf( array &$appData, string $type, string $locale ): array {

			$known = preg_match( '/^\/[a-z][a-z0-9_-]{0,63}$/D', $type ) === 1 || isset( \Nino\Elements::PLACEHOLDERS[$type] ) === true;
			$labels = [];

			// The words a field has in the model of its type, where it has some: a text, or the words of each language
			foreach( $known === true ? \Nino\Elements::getElementModel( $appData, $type ) : [] as $name => $field ) {

				$label = $field['label'] ?? null;
				$label = is_array( $label ) === true ? ( $label[$locale] ?? reset( $label ) ) : $label;

				if( is_string( $label ) === true && $label !== '' && str_contains( $label, '/' ) === false )
					$labels[(string) $name] = mb_substr( $label, 0, 60 );
			}

			return [ 'fields' => $known === true ? Tree::fields( $appData, $type ) : [], 'labels' => $labels ];
		}

		/**
		 *	What a loop may show: the types of elements the project has, with their titles - the example type of the kernel is none of them
		 *
		 *	@param		array 		&$appData
		 *
		 *	@return 	array											[ [ type, title ] ]
		 */
		public static function typesOf( array &$appData ): array {
			return array_map( static fn( array $type ): array => [ 'type' => '/'. $type['uri'], 'title' => (string) $type['title'] ], \Nino\Modules\Elements\Types::summaries( $appData ) );
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

			// The editor of the blocks passes a request on for a frame of the site: it held its own right and set the mark - in the memory of this request, which nothing a
			// request brings can set - and the request is for a frame and for nothing else. The step it has is the last, which may write a frame down to its markup
			if( ( $appData[\Nino\Modules\Builder::AS] ?? false ) === true ) {

				if( ( Store::named( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) )['frame'] ?? false ) !== true ) {
					self::_fail( $request, self::_level() );
					return null;
				}

				return \Nino\Modules\Builder::LEVEL_DEVELOP;
			}

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::perm() ) === false )
				return null;

			$level = \Nino\Modules\Builder::level( $appData );

			// A frame is written in the editor of the blocks and nowhere else: no action of this panel is for one, whatever the account holds
			if( $level < $least || ( Store::named( \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) )['frame'] ?? false ) === true ) {
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
