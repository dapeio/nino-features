<?php
declare(strict_types=1);
/**
 *	Nino							A compact filesystembased php framework
 *	Modules\Builder\Frames	see _nino/Nino/Modules/Modules.php for the
 *										package-level docblock
 *
 *	@package					Dape/Nino
 *	@author						David Perchermeier <mail@dape.io>
 *	@link							https://github.com/dapeio/nino
 */
namespace Nino\Modules\Builder {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Builder		The frames of the site - the header and the footer every page has round its blocks, the standard ones and the named ones: the door every request for one goes
	 *										through, and the frames made, renamed and taken away (the tab "Header/footer" of the pane of the pages, assets/admin-frames.js)
	 *
	 *										A frame is a template like a page's, and its right is the right of the pages: whoever holds the door of this panel and the step of the design - the account that
	 *										designs a page - makes, writes, renames and takes away a frame. What goes further is the right of Themelab, and only in the editor of the frame: the call of html, free
	 *										values, units and the notation (see Builder::frameLevel(), which reads it from what the account holds and from nothing a request brings). The right of Themelab on its
	 *										own opens no frame
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Frames {

		/**
		 *	The frames the site has: the header and the footer and the named ones, each as the file of its template stands
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language of the names; '' for the native one
		 *
		 *	@return 	array											[ [ key, side, template, default, names, name, uses, pages ] ]
		 */
		public static function listed( array &$appData, string $locale = '' ): array {

			$locale = $locale === '' ? \Nino\Locales::getNativeLocale( $appData ) : $locale;

			return array_values( array_filter( Store::frameList( $appData, $locale ), static fn( array $entry ): bool => is_file( \Nino\Filesystem::path( $appData, '/templates/'. $entry['template']. '.tpl' ) ) === true ) );
		}

		/**
		 *	The door of every request for a frame: the right of this panel, a step of the account that designs, and - where the request names one - a template that is a frame of the site and
		 *	nothing else. The step is the account's, read here from what it holds (Builder::frameLevel()); the request brings none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) The request
		 *	@param		bool			$named				Whether the request names a frame - the list and the making of a frame name none
		 *
		 *	@return 	int|null									The step the account has for a frame; null after the answer that says why not
		 */
		public static function door( array &$appData, array &$request, bool $named = true ): ?int {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, \Nino\Modules\Builder::PERM ) === false )
				return null;

			$level = \Nino\Modules\Builder::frameLevel( $appData );

			if( $level < \Nino\Modules\Builder::LEVEL_DESIGN ) {
				\Nino\Http::fail( $request, 403, 'the account does not hold the right for this', 'builder_level' );
				return null;
			}

			if( $named === true && Store::isFrame( $appData, \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' ) ) === false ) {
				\Nino\Http::fail( $request, 400, 'the request names no frame of the site', 'builder_unknown', [], 'template' );
				return null;
			}

			return $level;
		}

		/**
		 *	The language a request is about: one of the languages the project has texts in. The session's working language is never read
		 *
		 *	@return 	string|null								null after the answer that says the request names none
		 */
		private static function _locale( array &$appData, array &$request ): ?string {

			$locale = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'locale' );

			if( $locale === '' || in_array( $locale, \Nino\Locales::getTextLocales( $appData ), true ) === false ) {
				\Nino\Http::fail( $request, 400, 'the request names no language of the website', 'builder_language', [], 'locale' );
				return null;
			}

			return $locale;
		}

		/**
		 *	The name a request gives a frame, in each language of the site: the working language is required, a language that is left empty has the name of the working language
		 *
		 *	@param		mixed			$given				language => name, or one name for each language
		 *	@param		string		$work					The language the form was in
		 *
		 *	@return 	array|null								locale => name; null after the answer
		 */
		private static function _names( array &$appData, array &$request, mixed $given, string $work ): ?array {

			$locales = array_values( array_unique( array_merge( [ \Nino\Blocks::FALLBACK ], \Nino\Locales::getTextLocales( $appData ) ) ) );
			$names = [];

			if( is_string( $given ) === true )
				$given = array_fill_keys( $locales, $given );

			$main = is_array( $given ) === true && is_string( $given[$work] ?? null ) === true ? trim( $given[$work] ) : '';

			foreach( $locales as $locale ) {

				$name = is_array( $given ) === true && is_string( $given[$locale] ?? null ) === true ? trim( $given[$locale] ) : '';
				$name = $name === '' ? $main : $name;

				if( $name === '' || mb_strlen( $name ) > \Nino\Blocks::MAX_NAME || preg_match( '/[<>\x00-\x1F\x7F\p{Cf}\p{Zl}\p{Zp}]|\[\[/u', $name ) === 1 ) {
					\Nino\Http::fail( $request, 400, 'the name is none', 'builder_frames_name', [ \Nino\Blocks::MAX_NAME ], 'name' );
					return null;
				}

				$names[$locale] = $name;
			}

			return $names;
		}

		/**
		 *	A new frame of the site: the template frame-<side>-<word> and its words in every language, made of the frame of that side that is named or of a block of that side's kind, and the name it
		 *	is called by. The request names the side, the word, the source and the names - the word is held to the form of one, free among the templates, and the source is a frame of the same side or
		 *	a block with the root of the side; nothing of the template is taken from it
		 */
		public static function apiCreate( array &$appData, array &$request ): void {

			if( self::door( $appData, $request, false ) === null )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$side = \Nino\Admin\Admin::postString( $data, 'side' );
			$word = \Nino\Admin\Admin::postString( $data, 'word' );
			$from = \Nino\Admin\Admin::postString( $data, 'from' );
			$pick = \Nino\Admin\Admin::postString( $data, 'block' );

			if( isset( Store::FRAMES[$side] ) === false ) {
				\Nino\Http::fail( $request, 400, 'the request names no side of the page', 'builder_frames_side', [], 'side' );
				return;
			}

			$template = Store::FRAMES[$side]. '-'. $word;

			if( $word === '' || Store::side( $template ) !== $side ) {
				\Nino\Http::fail( $request, 400, 'the word is none of a frame', 'builder_frames_word', [ Store::FRAME_WORD_MAX ], 'word' );
				return;
			}

			$names = self::_names( $appData, $request, $data['name'] ?? null, $locale );

			if( $names === null )
				return;

			if( ( $from === '' ) === ( $pick === '' ) ) {
				\Nino\Http::fail( $request, 400, 'the request names one source', 'builder_frames_source', [], 'from' );
				return;
			}

			$block = $pick === '' ? null : Library::block( $appData, $pick, Library::sideOf( $template ) );

			if( $pick !== '' && $block === null ) {
				\Nino\Http::fail( $request, 404, 'there is no such block for this frame', 'builder_frames_source', [], 'block' );
				return;
			}

			if( $from !== '' && ( Store::isFrame( $appData, $from ) === false || Store::side( $from ) !== $side ) ) {
				\Nino\Http::fail( $request, 404, 'there is no such frame', 'builder_frames_source', [], 'from' );
				return;
			}

			if( \Nino\Filesystem::lockFile( $appData, Store::LOCK ) === false ) {
				\Nino\Http::fail( $request, 500, 'the lock of the Builder could not be taken', 'builder_frames_lock' );
				return;
			}

			try {

				if( Store::isFrame( $appData, $template ) === true || is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === true ) {
					\Nino\Http::fail( $request, 409, 'the word is taken', 'builder_frames_taken', [ $word ], 'word' );
					return;
				}

				if( ( $taken = Store::frameNameTaken( $appData, $side, $template, $names ) ) !== null ) {
					\Nino\Http::fail( $request, 409, 'another frame of this side has the name', 'builder_frames_name_taken', [ $taken ], 'name' );
					return;
				}

				$set = [];
				$formats = [];

				if( $block === null ) {

					$source = \Nino\Filesystem::getFileContent( $appData, '/templates/'. $from. '.tpl', null );

					if( is_string( $source ) === false ) {
						\Nino\Http::fail( $request, 404, 'there is no such frame', 'builder_frames_source', [], 'from' );
						return;
					}

					$meta = \Nino\Text::meta( $appData );
					$source = str_replace( '/template/'. $from. '/', '/template/'. $template. '/', $source );

					foreach( \Nino\Text::values( $appData, '/template/'. $from ) as $key => $values ) {

						$now = '/template/'. $template. substr( (string) $key, strlen( '/template/'. $from ) );
						$set[$now] = $values;

						if( isset( $meta[$key]['format'] ) === true )
							$formats[$now] = $meta[$key]['format'];
					}
				}
				else {

					$source = rtrim( \Nino\Markup::write( $block['tree'], \Nino\Components::containers( $appData ) ) ). "\n";
					$native = \Nino\Locales::getNativeLocale( $appData );

					foreach( $block['slots'] as $slot ) {

						if( $slot['kind'] === 'image' )
							continue;

						$place = '/template/'. $template. '/'. ( $slot['scope'] === '' ? $block['id'] : $block['id']. '-'. $slot['scope'] ). '/'. $slot['word'];

						foreach( \Nino\Locales::getTextLocales( $appData ) as $code ) {

							$own = $block['texts'][$code][$slot['name']] ?? null;
							$text = is_string( $own ) === true ? $own : ( $code === $native ? ( $block['texts']['en_US'][$slot['name']] ?? null ) : null );

							if( is_string( $text ) === true )
								$set[$place][$code] = $text;
						}
					}
				}

				if( \Nino\Filesystem::putFileContent( $appData, '/templates/'. $template. '.tpl', $source ) !== true || ( $set !== [] && \Nino\Text::put( $appData, $set, [], $formats ) === null ) ) {
					\Nino\Http::fail( $request, 500, 'the frame could not be written', 'builder_frames_write' );
					return;
				}

				Store::setFrameName( $appData, $template, $names );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, Store::LOCK );
			}

			\Nino\Http::ok( $request, [ 'key' => substr( $template, 6 ), 'template' => $template, 'side' => $side ] );
		}

		/**
		 *	The name of a named frame - one for each language - changed: the word, the template and the pages that choose it stay as they are
		 */
		public static function apiRename( array &$appData, array &$request ): void {

			if( self::door( $appData, $request ) === null )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$data = \Nino\Admin\Admin::postData();
			$template = \Nino\Admin\Admin::postString( $data, 'template' );

			if( in_array( $template, Store::FRAMES, true ) === true ) {
				\Nino\Http::fail( $request, 403, 'the standard frame has no name to change', 'builder_frames_standard_name', [], 'template' );
				return;
			}

			$names = self::_names( $appData, $request, $data['name'] ?? null, $locale );

			if( $names === null )
				return;

			if( \Nino\Filesystem::lockFile( $appData, Store::LOCK ) === false ) {
				\Nino\Http::fail( $request, 500, 'the lock of the Builder could not be taken', 'builder_frames_lock' );
				return;
			}

			try {

				if( ( $taken = Store::frameNameTaken( $appData, (string) Store::side( $template ), $template, $names ) ) !== null ) {
					\Nino\Http::fail( $request, 409, 'another frame of this side has the name', 'builder_frames_name_taken', [ $taken ], 'name' );
					return;
				}

				if( Store::setFrameName( $appData, $template, $names ) === false ) {
					\Nino\Http::fail( $request, 500, 'the name could not be written', 'builder_frames_write' );
					return;
				}

				// A frame that is written in any way is no longer the one a choice wrote - its name is part of it
				Store::unchoose( $appData, $template );
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, Store::LOCK );
			}

			\Nino\Http::ok( $request, [ 'template' => $template ] );
		}

		/**
		 *	A named frame taken away - with its words, its names and the last state of it - where no page chooses it; where a page does, it is refused, 409, with the number of the pages and
		 *	their names: nothing is put on another frame by itself. The standard frame of a side is never taken away. The pages are looked at under the lock of the Builder, the same one that
		 *	saves a page
		 */
		public static function apiRemove( array &$appData, array &$request ): void {

			if( self::door( $appData, $request ) === null )
				return;

			$locale = self::_locale( $appData, $request );

			if( $locale === null )
				return;

			$template = \Nino\Admin\Admin::postString( \Nino\Admin\Admin::postData(), 'template' );

			if( in_array( $template, Store::FRAMES, true ) === true ) {
				\Nino\Http::fail( $request, 403, 'the standard frame is not taken away', 'builder_frames_standard', [], 'template' );
				return;
			}

			if( \Nino\Filesystem::lockFile( $appData, Store::LOCK ) === false ) {
				\Nino\Http::fail( $request, 500, 'the lock of the Builder could not be taken', 'builder_frames_lock' );
				return;
			}

			try {

				$all = Store::frameUsage( $appData, $locale )[$template] ?? [];
				$uses = array_values( array_filter( $all, static fn( array $use ): bool => $use['last'] === false ) );
				$kept = array_values( array_filter( $all, static fn( array $use ): bool => $use['last'] === true ) );
				$say = static fn( array $page ): string => $page['name']. ( $page['path'] === '' ? '' : ' ('. $page['path']. ')' );

				if( $uses !== [] ) {

					\Nino\Http::fail( $request, 409, 'the frame is chosen by a page', 'builder_frames_used', [ count( $uses ), implode( ', ', array_map( $say, array_slice( $uses, 0, 8 ) ) ). ( count( $uses ) > 8 ? ' …' : '' ) ], 'template' );
					return;
				}

				// The last state of a page that names the frame: a restore would write it back
				if( $kept !== [] ) {

					\Nino\Http::fail( $request, 409, 'the frame is named by the last state of a page', 'builder_frames_used_last', [ implode( ', ', array_map( $say, array_slice( $kept, 0, 8 ) ) ). ( count( $kept ) > 8 ? ' …' : '' ) ], 'template' );
					return;
				}

				if( Store::dropFrame( $appData, $template ) === false ) {
					\Nino\Http::fail( $request, 500, 'the frame could not be taken away', 'builder_frames_write' );
					return;
				}
			}
			finally {
				\Nino\Filesystem::unlockFile( $appData, Store::LOCK );
			}

			\Nino\Http::ok( $request, [ 'template' => $template ] );
		}
	}
}
