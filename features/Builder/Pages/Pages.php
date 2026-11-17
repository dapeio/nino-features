<?php
declare(strict_types=1);

namespace Nino\Modules\Builder {

	// The pages of the site the Builder lists - the routes that answer with a template of a page, once for each template - and the one it makes: a template
	// of the frame calls the site's other pages have and a block that is offered, with the route, the menu entry and the words of the block, in one step
	class Pages {

		// The longest word an address or a template is made of, so that page- and the word stay a name that Store::named() takes
		public const int MAX_SLUG = 60;

		/**
		 *	The pages that can be opened, in the order of the routes
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language of the view: what a page is called, and the address it is shown at
		 *	@param		array			$navKeys			The menus the site has, for the menus a page is in
		 *
		 *	@return 	array											[ [ template, name, path, locales, navs, complete ], ... ] - navs the menus the page is in, complete whether every
		 *																		language of the site has a name and an address for it
		 */
		public static function all( array &$appData, string $locale, array $navKeys = [] ): array {

			$texts = \Nino\Locales::getTextLocales( $appData );
			$available = \Nino\Locales::getAvailableLocales( $appData );
			$pages = [];

			foreach( \Nino\Modules\Routes\Admin::pages( $appData, (array) ( $appData['/nino/http/routes'] ?? [] ), $texts, $navKeys ) as $page ) {

				$template = (string) $page['template'];

				if( isset( $pages[$template] ) === true || ( \Nino\Modules\Builder\Store::named( $template )['frame'] ?? true ) === true || is_file( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) ) === false )
					continue;

				$words = (array) ( $page['text'][$locale] ?? [] );
				$name = trim( (string) ( $words['name'] ?? '' ) ) !== '' ? (string) $words['name'] : ( trim( (string) ( $words['title'] ?? '' ) ) !== '' ? (string) $words['title'] : $template );
				$has = array_keys( (array) $page['httpUris'] );
				$complete = true;

				foreach( $available as $each )
					if( trim( (string) ( $page['text'][$each]['name'] ?? '' ) ) === '' || trim( (string) ( $page['httpUris'][$each] ?? $page['httpUris'][''] ?? '' ) ) === '' )
						$complete = false;

				$pages[$template] = [
					'template'	=> $template,
					'name'			=> $name,
					'path'			=> (string) ( $page['httpUris'][$locale] ?? $page['httpUris'][''] ?? $page['httpUri'] ),
					'locales'		=> in_array( '', $has, true ) === true ? $available : array_values( array_filter( $has ) ),
					'navs'			=> self::_menus( $appData, $page, $navKeys ),
					'complete'	=> $complete,
				];
			}

			return array_values( $pages );
		}

		/**
		 *	The menus a page is in: the ones its routes name and, for a page a module routes at run time, the ones '/nino/html/navroutes' puts it in by its Element-URI -
		 *	in the order of the menus of the site
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$page					An entry of \Nino\Modules\Routes\Admin::pages()
		 *	@param		array			$navKeys			The menus the site has
		 *
		 *	@return 	array											The keys of the menus
		 */
		private static function _menus( array &$appData, array $page, array $navKeys ): array {

			$in = (array) $page['navs'];
			$runtime = $appData['/nino/html/navroutes'][(string) $page['uri']] ?? [];

			return array_values( array_filter( $navKeys, static fn( string $nav ): bool => in_array( $nav, $in, true ) === true || ( is_array( $runtime ) === true && is_int( $runtime[$nav] ?? null ) === true ) ) );
		}

		/**
		 *	The pages as the list of the panel shows them: what all() says and, for each, how many blocks its template has - the calls on its top level that the register
		 *	has, the calls of the frame and free markup are none - and when it was written last, the later of the file of the template and the state the Builder kept
		 *	with its last save
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$locale				The language of the view
		 *
		 *	@return 	array											[ [ template, name, path, locales, navs, complete, blocks, saved ], ... ]; saved is a time, 0 where none is known
		 */
		public static function listed( array &$appData, string $locale ): array {

			$registry = \Nino\Components::components( $appData );
			$rows = [];

			foreach( self::all( $appData, $locale, \Nino\Pages::navKeys( $appData ) ) as $page ) {

				$template = (string) $page['template'];
				$nodes = Tree::read( $appData, (string) \Nino\Filesystem::getFileContent( $appData, '/templates/'. $template. '.tpl', '' ) );
				$time = @filemtime( \Nino\Filesystem::path( $appData, '/templates/'. $template. '.tpl' ) );

				$page['blocks'] = count( array_filter( $nodes, static fn( array $node ): bool => isset( $node['n'] ) === true && isset( $registry[(string) $node['n']] ) === true ) );
				$page['saved'] = max( $time === false ? 0 : $time, (int) ( Store::previous( $appData, $template )['saved'] ?? 0 ) );
				$rows[] = $page;
			}

			return $rows;
		}

		/**
		 *	What a page is called in the language of the view: its name, else its title, else its template. A frame has no name of its own and keeps the template
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$template
		 *	@param		string		$locale
		 *
		 *	@return 	string
		 */
		public static function label( array &$appData, string $template, string $locale ): string {

			foreach( self::all( $appData, $locale ) as $page )
				if( $page['template'] === $template )
					return (string) $page['name'];

			return $template;
		}

		/**
		 *	The words of a name or an address as a name of a template: lower case letters and digits, joined by hyphens - the umlauts of German as the words they
		 *	are written out as, any other letter with a mark as the letter
		 *
		 *	@param		string		$text
		 *
		 *	@return 	string										'' where nothing is left
		 */
		public static function slug( string $text ): string {

			$text = mb_strtolower( $text, 'UTF-8' );

			if( class_exists( '\\Normalizer' ) === true )
				$text = (string) preg_replace( '/\p{Mn}+/u', '', (string) \Normalizer::normalize( strtr( $text, [ 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss' ] ), \Normalizer::FORM_D ) );
			else
				$text = strtr( $text, [ 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'à' => 'a', 'á' => 'a', 'ç' => 'c', 'ñ' => 'n' ] );

			return substr( trim( (string) preg_replace( '/[^a-z0-9]+/', '-', $text ), '-' ), 0, self::MAX_SLUG );
		}

		/**
		 *	The calls the pages of the site have round their blocks: the first and the last call of the first template of a page that begins and ends with one - the
		 *	header and the footer of the starter site. Where no page has them, the two the base unit brings, if the project has them
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ the first call or null, the last call or null ]
		 */
		private static function _frames( array &$appData ): array {

			$names = \Nino\Pages::templates( $appData );
			$ends = [ null, null ];

			foreach( $names as $name ) {

				$nodes = array_values( array_filter( Tree::read( $appData, (string) \Nino\Filesystem::getFileContent( $appData, '/templates/'. $name. '.tpl', '' ) ), static fn( array $node ): bool => isset( $node['n'] ) === true || trim( (string) ( $node['t'] ?? 'x' ), " \t\r\n" ) !== '' ) );

				if( count( $nodes ) >= 2 && ( $nodes[0]['n'] ?? '' ) === 'template' && ( $nodes[count( $nodes ) - 1]['n'] ?? '' ) === 'template' ) {
					$ends = [ $nodes[0], $nodes[count( $nodes ) - 1] ];
					break;
				}
			}

			if( $ends[0] === null )
				$ends = [ [ 'n' => 'template', 's' => '/templates/html-header', 'a' => [], 'c' => null ], [ 'n' => 'template', 's' => '/templates/html-footer', 'a' => [], 'c' => null ] ];

			foreach( $ends as $at => $call )
				$ends[$at] = is_array( $call ) === true && is_string( $call['s'] ?? null ) === true && preg_match( '#^/templates/([a-z][a-z0-9-]*)$#D', $call['s'], $found ) === 1
					&& is_file( \Nino\Filesystem::path( $appData, '/templates/'. $found[1]. '.tpl' ) ) === true ? [ 'n' => 'template', 's' => $call['s'], 'a' => [], 'c' => null ] : null;

			return $ends;
		}

		/**
		 *	Make a page: the template - the two calls of the frame the other pages have and the block that was chosen between them, which is a block that is offered
		 *	and nothing a request wrote -, the route of each language, the entry in the menus, the words and the pictures of the block, all of it or none of it. The words
		 *	and the pictures are written first and taken back where the kernel refuses the page, which is where it says what is wrong with an address
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$data					names (language => name), addresses (language => address), navs (the menus), block (the key of a block, or '')
		 *
		 *	@return 	array											[ 'template', 'slug' ] - or 'failure' => [ status, message, code, params, field ]
		 */
		public static function create( array &$appData, array $data ): array {

			$locales = \Nino\Locales::getAvailableLocales( $appData );
			$native = \Nino\Locales::getNativeLocale( $appData );
			$names = is_array( $data['names'] ?? null ) === true ? $data['names'] : [];
			$addresses = is_array( $data['addresses'] ?? null ) === true ? $data['addresses'] : [];
			$navs = is_array( $data['navs'] ?? null ) === true ? array_values( array_filter( $data['navs'], 'is_string' ) ) : [];
			$key = is_string( $data['block'] ?? null ) === true ? $data['block'] : '';
			$text = [];
			$given = [];

			foreach( $locales as $locale ) {

				$name = is_string( $names[$locale] ?? null ) === true ? trim( $names[$locale] ) : '';

				if( $name === '' )
					return [ 'failure' => [ 400, 'missing name for '. $locale, 'builder_page_missing_name', [], 'name-'. $locale ] ];

				$text[$locale] = [ 'name' => $name, 'title' => $name, 'description' => '' ];
				$given[$locale] = is_string( $addresses[$locale] ?? null ) === true ? $addresses[$locale] : '';
			}

			// The word of the page is the one of its address in the native language, else of its name
			$slug = self::slug( (string) preg_replace( '#^/#', '', (string) ( \Nino\Pages::normalizeUri( $given[$native] ?? '' ) ?? '' ) ) );

			if( $slug === '' )
				$slug = self::slug( $text[$native]['name'] ?? '' );

			if( $slug === '' )
				return [ 'failure' => [ 400, 'invalid uri', 'builder_page_invalid_uri', [ (string) ( $given[$native] ?? '' ) ], 'httpUri-'. $native ] ];

			$template = 'page-'. $slug;
			$block = $key === '' ? null : Library::block( $appData, $key );

			// The pages that a feature or a module routes at run time - the imprint, the privacy policy - are in no config.php, and the kernel does not look at them: an address
			// of theirs is theirs, and is said at the field of its language
			$runtime = [];

			foreach( \Nino\Pages::all( $appData ) as $page )
				if( $page['runtime'] === true )
					foreach( $page['addresses'] as $address )
						$runtime[strtolower( rtrim( (string) $address, '/' ) )] = true;

			foreach( $locales as $locale ) {

				$address = \Nino\Pages::normalizeUri( $given[$locale] );

				if( $address !== null && isset( $runtime[strtolower( rtrim( $address, '/' ) )] ) === true )
					return [ 'failure' => [ 409, 'http uri already belongs to another route: "'. $address. '"', 'builder_page_http_uri_taken', [ $address ], 'httpUri-'. $locale ] ];
			}

			// A page that is there is not made again: nothing is written for it - not a word, not a picture - that the page that is there would lose
			$stored = \Nino\AppData::stored( $appData, '/nino/http/routes', [] );

			if( \Nino\Pages::keys( is_array( $stored ) === true ? $stored : [], '/'. $slug ) !== [] )
				return [ 'failure' => [ 400, 'duplicate uri: "/'. $slug. '"', 'builder_page_duplicate_uri', [ '/'. $slug ], 'uri' ] ];

			if( \Nino\Filesystem::fileExists( $appData, '/templates/'. $template. '.tpl' ) === true )
				return [ 'failure' => [ 409, 'template already exists: "'. $template. '"', 'builder_page_template_exists', [ $template ], 'template' ] ];

			if( $key !== '' && $block === null )
				return [ 'failure' => [ 403, 'the block is none that is offered', 'builder_block', [], 'block' ] ];

			$registry = \Nino\Components::components( $appData );
			[ $head, $foot ] = self::_frames( $appData );
			// The name stands in a comment, which the reading of a template reads like the rest: no mark and no end of the comment may be in it
			$label = trim( (string) preg_replace( '/[\x00-\x1F\x7F<>\[\]\\\\]|--/u', ' ', $text[$native]['name'] ?? $slug ) );
			$nodes = [ [ 't' => '<!-- nino:template-name '. mb_substr( $label, 0, 60 ). " -->\n" ] ];

			foreach( [ $head, $block === null ? null : $block['tree'][0], $foot ] as $node )
				if( $node !== null ) {
					$nodes[] = $node;
					$nodes[] = [ 't' => "\n" ];
				}

			$source = \Nino\Markup::write( $nodes, \Nino\Components::containers( $appData ) );
			$notes = [];

			if( \Nino\Markup::write( Tree::read( $appData, $source, $notes ), \Nino\Components::containers( $appData ) ) !== $source || $notes !== [] )
				return [ 'failure' => [ 500, 'the template of the page is none that is read as it stands', 'builder_write', [], '' ] ];

			// The words and the pictures of the block, under the id it has, as they are put in a page
			$set = [];
			$formats = [];
			$slots = [];
			$files = [];

			if( $block !== null ) {

				foreach( Tree::sources( Tree::read( $appData, $source ), $registry, $template ) as $entry )
					if( $entry['key'] !== '' && in_array( $entry['kind'], [ 'text', 'link' ], true ) === true )
						$formats[$entry['key']] ??= $entry['editable'] === true ? $entry['format'] : 'plain';

				foreach( Library::places( $block, (string) $block['id'], $template ) as $place ) {

					if( $place['kind'] === 'image' ) {

						$spec = $block['images'][$place['name']] ?? null;

						if( $spec === null )
							continue;

						// A slot that is there - of a page that was taken away, its words and pictures left - is the slot it is, and its file is no one's to write over
						if( \Nino\Images::getSlot( $appData, $place['key'] ) !== false )
							continue;

						$file = ltrim( $place['key'], '/' ). '.svg';
						$slots[$place['key']] = [ 'label' => (string) ( $spec['label'][$native] ?? $spec['label'][\Nino\Blocks::FALLBACK] ?? reset( $spec['label'] ) ), 'width' => $spec['width'], 'height' => $spec['height'], 'filename' => $file ];
						$files[$file] = $spec['placeholder'];
						continue;
					}

					foreach( $locales as $locale ) {

						$words = $block['texts'][$locale][$place['name']] ?? ( $locale === $native ? ( $block['texts'][\Nino\Blocks::FALLBACK][$place['name']] ?? null ) : null );

						if( is_string( $words ) === true )
							$set[$place['key']][$locale] = $words;
					}
				}
			}

			// What a link of the block reads is a target by the rule of every link - the block is the kernel's to accept, and the target is this rule's
			foreach( Tree::sources( Tree::read( $appData, $source ), $registry, $template ) as $entry )
				if( $entry['kind'] === 'link' && $entry['key'] !== '' )
					foreach( (array) ( $set[$entry['key']] ?? [] ) as $words )
						if( Tree::linkTarget( $appData, (string) $words, false ) === false )
							return [ 'failure' => [ 403, 'the block is none that is offered', 'builder_block', [], 'block' ] ];

			$before = $set === [] ? [] : \Nino\Text::put( $appData, $set, [], $formats );
			$was = [];
			$made = [];

			if( $before === null ) {
				self::_undo( $appData, $set, [], [], [] );
				return [ 'failure' => [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] ];
			}

			foreach( $slots as $uri => $slot ) {

				$bytes = \Nino\Images::PLACEHOLDERS[(string) $files[$slot['filename']]]['svg'] ?? false;

				if( $bytes !== false && \Nino\Images::restore( $appData, (string) $slot['filename'], $bytes ) === true ) {
					$made[] = $slot['filename'];
					$done = \Nino\Images::putSlot( $appData, (string) $uri, $slot );
				}
				else
					$done = false;

				if( $done === false ) {
					self::_undo( $appData, $set, $before, $was, $made );
					return [ 'failure' => [ 500, 'the page could not be written, and is as it was', 'builder_write', [], '' ] ];
				}

				$was[$uri] = $done;
			}

			$put = \Nino\Pages::put( $appData, [ 'uri' => '/'. $slug, 'originalUri' => '', 'template' => $template, 'httpUris' => $given, 'text' => $text, 'navs' => $navs, 'source' => $source ] );

			if( isset( $put['error'] ) === true ) {

				self::_undo( $appData, $set, $before, $was, $made );
				$error = $put['error'];
				$code = str_starts_with( (string) $error['code'], 'routes_' ) === true ? 'builder_page_'. substr( (string) $error['code'], 7 ) : 'builder_write';

				return [ 'failure' => [ (int) $error['status'], (string) $error['message'], $code, (array) $error['params'], (string) $error['field'] ] ];
			}

			return [ 'template' => $template, 'slug' => $slug ];
		}

		/**
		 *	Take back what a page that is refused had written before: a key that was not there goes, one that was has the words it had; a slot that was not there goes
		 *	and one that was is as it was; the files that were made for them go
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$set					key => language => text, what was written
		 *	@param		array			$before				What Text::put() answered, key => language => the text it was
		 *	@param		array			$slots				What Images::putSlot() answered for each slot that was written: what it was, [] for one that was not there
		 *	@param		array			$made					The files made, below /images/
		 *
		 *	@return 	void
		 */
		private static function _undo( array &$appData, array $set, array $before, array $slots, array $made ): void {

			$back = [];
			$drop = [];

			foreach( array_keys( $set ) as $key ) {

				$had = array_filter( (array) ( $before[$key] ?? [] ), 'is_string' );

				if( $had === [] )
					$drop[] = $key;
				else
					$back[$key] = $had;
			}

			if( $back !== [] || $drop !== [] )
				\Nino\Text::put( $appData, $back, $drop );

			foreach( $slots as $uri => $was )
				if( $was === [] )
					\Nino\Images::dropSlot( $appData, (string) $uri );
				else
					\Nino\Images::putSlot( $appData, (string) $uri, $was );

			// The folders that were made for the files go with them, as far as they are empty: a page that is refused leaves no folder of its pictures
			foreach( $made as $file ) {

				\Nino\Images::delete( $appData, (string) $file );

				for( $folder = dirname( (string) $file ); $folder !== '.' && $folder !== '/' && $folder !== 'template' && str_starts_with( $folder, 'template/' ) === true; $folder = dirname( $folder ) )
					if( @rmdir( \Nino\Filesystem::path( $appData, '/images/'. $folder ) ) === false )
						break;
			}
		}
	}
}
