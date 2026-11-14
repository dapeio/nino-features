<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Vcard\Admin		The /_admin panel of the vCard feature
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Vcard {

	use Nino\Modules\Vcard;

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Vcard\Admin				The "vCard" panel: every detail of the card on one screen, in
	 *											the order they are filled in from the top - picture, name, line,
	 *											text, the ways to reach, links, times - with the page one click
	 *											away and the way to the imprint and the privacy policy.
	 *
	 *											Four actions: vcard/list answers the details, the details the
	 *											project already knows (what an empty field falls back to) and
	 *											where the page is; vcard/save writes all of them at once, after
	 *											a check of each; vcard/upload and vcard/remove are the photo,
	 *											which lives in an image slot of the kernel, /vcard/photo, made
	 *											on the first upload.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Admin {

		public const string MANAGE_PERM = '/_admin/vcard/manage';

		public static function perm(): string {
			return self::MANAGE_PERM;
		}

		public static function actions(): array {
			return [
				'vcard/list'		=> [ self::class, 'apiList' ],
				'vcard/save'		=> [ self::class, 'apiSave' ],
				'vcard/upload'	=> [ self::class, 'apiUpload' ],
				'vcard/remove'	=> [ self::class, 'apiRemove' ],
				'vcard/start'		=> [ self::class, 'apiStart' ],
			];
		}

		public static function nav(): array {
			return [ 'vcard', '/_admin/nav/vcard', 85 ];
		}

		public static function icon(): string {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 2v2"/><path d="M7 22v-2a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/><path d="M8 2v2"/><circle cx="12" cy="11" r="3"/><rect x="3" y="4" width="18" height="18" rx="2"/></svg>';
		}

		public static function assets(): array {
			return [ \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.js' ), \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/assets/admin.css' ) ];
		}

		public static function text(): string {
			return \Nino\Admin\Panels::relative( dirname( __DIR__ ). '/text' );
		}

		public static function log( string $action, array $data ): string {
			return match( $action ) {
				'vcard/save'		=> 'Save vCard',
				'vcard/upload'	=> 'Upload vCard photo',
				'vcard/remove'	=> 'Remove vCard photo',
				'vcard/start'		=> 'Use vCard as start page',
				default					=> '',
			};
		}

		/**
		 *	Everything the screen is drawn from
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiList( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			\Nino\Http::ok( $request, self::_screen( $appData ) + [ 'designs' => self::_previews() ] );
		}

		/**
		 *	Save every detail. Each is checked, and the first that fails is answered with its
		 *	code and the field: nothing is written then. The alt text of the photo goes to its
		 *	slot, where the Images panel keeps it too
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiSave( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$posted = \Nino\Admin\Admin::postData();
			$result = self::validate( $appData, $posted );

			if( isset( $result['error'] ) === true ) {
				\Nino\Http::fail( $request, 400, $result['error']['message'], $result['error']['code'], $result['error']['params'], $result['error']['field'] );
				return;
			}

			$data = $result['data'];

			if( \Nino\Filesystem::mutate( $appData, Vcard::STORAGE, static fn(): array => $data ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write the details' );
				return;
			}

			if( is_array( $posted['alt'] ?? null ) === true && \Nino\Images::getSlot( $appData, Vcard::PHOTO ) !== false ) {

				$alt = [];
				foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale )
					if( is_string( $posted['alt'][$locale] ?? null ) === true )
						$alt[$locale] = mb_substr( (string) \Nino\Images::cleanAlt( $posted['alt'][$locale] ), 0, Vcard::LIMIT_ALT );

				if( \Nino\Images::setSlotAlt( $appData, Vcard::PHOTO, $alt ) === false ) {
					\Nino\Http::fail( $request, 500, 'could not save the description of the photo' );
					return;
				}
			}

			// The words the feature brought with its install unit, for a project that was activated before they existed: added
			// where the project lacks one, never replaced, only on this command of the owner and not when none is missing. The
			// card is saved either way; a project whose text files cannot be written is told so, and the switch stays hidden
			$warning = self::_missingWords( $appData ) === true && is_int( \Nino\Features::applyUnitTexts( $appData, \Nino\Features::dir(). '/Vcard/install', \Nino\Locales::getAvailableLocales( $appData ), false ) ) === false ? [ 'warning' => 'vcard_words' ] : [];

			\Nino\Http::ok( $request, self::_screen( $appData ) + $warning );
		}

		/**
		 *	Whether a word of the install unit is not in the text files of the project, in a language of the website
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool
		 */
		private static function _missingWords( array &$appData ): bool {

			$unit = include \Nino\Features::dir(). '/Vcard/install/text/en_US.php';

			foreach( \Nino\Locales::getAvailableLocales( $appData ) as $locale ) {

				$have = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );

				if( array_diff_key( (array) $unit, is_array( $have ) === true ? $have : [] ) !== [] )
					return true;
			}

			return false;
		}

		/**
		 *	The photo: cut square to the size of the slot, stored as the kernel stores a slot's
		 *	picture. The slot is made here on the first upload - the install unit cannot bring
		 *	one - and an upload that fails leaves the old photo as it was
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiUpload( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$failure = \Nino\Admin\Admin::uploadError( $_FILES['file'] ?? null );
			if( $failure !== null ) {
				\Nino\Http::fail( $request, $failure['status'], $failure['error'], $failure['code'], $failure['params'] );
				return;
			}

			$bytes = file_get_contents( $_FILES['file']['tmp_name'] );
			if( $bytes === false ) {
				\Nino\Http::fail( $request, 500, 'could not read upload', 'upload_server' );
				return;
			}

			$rejection = \Nino\Images::reject( $bytes );
			if( $rejection !== null ) {
				\Nino\Http::fail( $request, 400, 'the image was refused: '. $rejection['code'], $rejection['code'], $rejection['params'] );
				return;
			}

			if( self::_slot( $appData ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not make the image slot' );
				return;
			}

			$slot			= \Nino\Images::getSlot( $appData, Vcard::PHOTO );
			$oldName	= is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '' ? $slot['filename'] : null;
			$oldBytes	= $oldName !== null ? \Nino\Images::read( $appData, $oldName ) : false;
			$filename	= \Nino\Images::process( $appData, $bytes, Vcard::PHOTO_SIZE, Vcard::PHOTO_SIZE, ltrim( Vcard::PHOTO, '/' ) );

			if( $filename === false ) {
				\Nino\Http::fail( $request, 400, 'the image could not be processed', 'image_unreadable' );
				return;
			}

			if( \Nino\Images::setSlotFilename( $appData, Vcard::PHOTO, $filename ) === false ) {

				if( $filename === $oldName && is_string( $oldBytes ) === true )
					\Nino\Images::restore( $appData, $filename, $oldBytes );
				else
					\Nino\Images::delete( $appData, $filename );

				\Nino\Http::fail( $request, 500, 'could not save the photo' );
				return;
			}

			if( $oldName !== null && $oldName !== $filename && str_starts_with( $oldName, ltrim( Vcard::PHOTO, '/' ). '.' ) === true )
				\Nino\Images::delete( $appData, $oldName );

			\Nino\Http::ok( $request, self::_screen( $appData ) );
		}

		/**
		 *	Take the photo away: the slot stays, the card shows none. Only a file the slot made
		 *	is deleted, and only after the record no longer names it
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiRemove( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$slot = \Nino\Images::getSlot( $appData, Vcard::PHOTO );
			$old	= is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true ? $slot['filename'] : '';

			if( $old !== '' ) {

				if( \Nino\Images::setSlotFilename( $appData, Vcard::PHOTO, null ) === false ) {
					\Nino\Http::fail( $request, 500, 'could not save the slot' );
					return;
				}

				if( str_starts_with( $old, ltrim( Vcard::PHOTO, '/' ). '.' ) === true )
					\Nino\Images::delete( $appData, $old );
			}

			\Nino\Http::ok( $request, self::_screen( $appData ) );
		}

		/**
		 *	Give / to the card. The page that holds it moves to a free address in the same write that puts the card
		 *	on /, so that / answers at every moment, and the fill that names the moved page's address is
		 *	written after it. The one place in the feature that changes a route of the project, and only on this
		 *	command: an activation and an update never do. Nothing is written where no address is free (409)
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array 		&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function apiStart( array &$appData, array &$request ): void {

			if( \Nino\Admin\Admin::guardPerm( $appData, $request, self::MANAGE_PERM ) === false )
				return;

			$outcome	= [];
			$live			= (array) ( $appData['/nino/http/routes'] ?? [] );

			$written = \Nino\Filesystem::mutate( $appData, '/config.php', static function( mixed $content ) use ( &$outcome, $live, &$appData ): ?array {

				if( is_array( $content ) === false )
					return null;

				$stored		= is_array( $content['/nino/http/routes'] ?? null ) === true ? $content['/nino/http/routes'] : \Nino\AppData::DEFAULTS['/nino/http/routes'];
				$outcome	= Vcard::takeStart( $stored, $live, static fn( array $route ): bool => Vcard::shows( $appData, $route ) );

				if( isset( $outcome['routes'] ) === false )
					return null;

				$content['/nino/http/routes'] = $outcome['routes'];

				return $content;
			} );

			if( isset( $outcome['error'] ) === true ) {

				if( $outcome['error'] === 'template' )
					\Nino\Http::fail( $request, 409, 'the template of the card does not draw it', 'vcard_start_template' );
				else
					\Nino\Http::fail( $request, 409, 'no free address for the page that holds /', 'vcard_start_taken' );

				return;
			}

			if( isset( $outcome['routes'] ) === false && isset( $outcome['already'] ) === false ) {
				\Nino\Http::fail( $request, 500, 'could not write the routes' );
				return;
			}

			$name = '';

			if( isset( $outcome['routes'] ) === true ) {

				if( $written === false ) {
					\Nino\Http::fail( $request, 500, 'could not write the routes' );
					return;
				}

				$appData['/nino/http/routes'] = $outcome['routes'] + array_diff_key( $live, [ 'GET://vcard' => 1 ] );

				$name = $outcome['to'] !== '' ? \Nino\Html::renderTextfill( $appData, '/_nino/webpage'. $outcome['from']. '/name' ) : '';

				// The address a page is linked by is a fill the setup wrote for it, in the file of every language the route served;
				// it names the address the page moved to wherever it said /, and is left where it said another address
				if( $outcome['to'] !== '' ) {

					$key = '[[/_nino/webpage'. $outcome['from']. '/uri]]';

					foreach( Vcard::addressFiles( $outcome['locale'], \Nino\Locales::getNativeLocale( $appData ), \Nino\Locales::getAvailableLocales( $appData ) ) as $file )
						\Nino\Filesystem::mutate( $appData, $file, static function( mixed $texts ) use ( $key, $outcome ): ?array {
							return is_array( $texts ) === true && ( $texts[$key] ?? null ) === '/' ? array_merge( $texts, [ $key => $outcome['to'] ] ) : null;
						} );
				}
			}

			\Nino\Http::ok( $request, self::_screen( $appData ) + [ 'moved' => [ 'from' => $outcome['from'] ?? '', 'to' => $outcome['to'] ?? '', 'name' => $name !== '' ? $name : (string) ( $outcome['from'] ?? '' ) ] ] );
		}

		/**
		 *	The posted details, checked and in the shape the file holds them. Nothing is repaired
		 *	silently but what is only form: line breaks in a one-line field become a space, a
		 *	website without its scheme gets https://. A detail that is wrong is refused with the
		 *	code of the panel and the posted key
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$posted
		 *
		 *	@return 	array											[ 'data' => ... ] or [ 'error' => [ message, code, params, field ] ]
		 */
		public static function validate( array &$appData, array $posted ): array {

			$fail		= static fn( string $message, string $code, array $params, string $field ): array => [ 'error' => [ 'message' => $message, 'code' => $code, 'params' => $params, 'field' => $field ] ];
			$string	= static fn( mixed $value ): string => is_string( $value ) === true ? $value : '';
			$locales	= \Nino\Locales::getAvailableLocales( $appData );

			// Longer than allowed is refused, not cut: a cut name or text is a mistake nobody sees
			$long		= static function( string $value, int $max, bool $multiple = false ): bool {
				return mb_strlen( $multiple === true ? Vcard::lines( $value, PHP_INT_MAX, PHP_INT_MAX ) : Vcard::line( $value, PHP_INT_MAX ) ) > $max;
			};
			$lines	= static fn( string $value ): int => $value === '' ? 0 : substr_count( Vcard::lines( $value, PHP_INT_MAX, PHP_INT_MAX ), "\n" ) + 1;

			if( in_array( $posted['picture'] ?? null, Vcard::PICTURES, true ) === false )
				return $fail( 'the picture is none of auto, photo, logo and none', 'vcard_picture', [], 'picture' );

			if( in_array( $posted['kind'] ?? null, Vcard::KINDS, true ) === false )
				return $fail( 'the kind is person or organization', 'vcard_kind', [], 'kind' );

			if( $posted['kind'] === 'person' && Vcard::line( $string( $posted['name'] ?? null ), PHP_INT_MAX ) === '' )
				return $fail( 'a person has a name', 'vcard_name', [], 'name' );

			if( $long( $string( $posted['name'] ?? null ), Vcard::LIMIT_NAME ) === true )
				return $fail( 'the name is too long', 'vcard_long', [ Vcard::LIMIT_NAME ], 'name' );

			$phone = Vcard::line( $string( $posted['phone'] ?? null ), PHP_INT_MAX );
			if( $phone !== '' && Vcard::phone( $phone ) === false )
				return $fail( 'the phone number is not one', 'vcard_phone', [], 'phone' );

			$email = Vcard::line( $string( $posted['email'] ?? null ), PHP_INT_MAX );
			if( $email !== '' && Vcard::email( $email ) === null )
				return $fail( 'the e-mail address is not one', 'vcard_email', [], 'email' );

			$website = Vcard::line( $string( $posted['website'] ?? null ), PHP_INT_MAX );
			if( $website !== '' && Vcard::website( $website ) === null )
				return $fail( 'the website is not a web address', 'vcard_website', [], 'website' );

			$address = $string( $posted['address'] ?? null );
			if( $long( $address, Vcard::LIMIT_ADDRESS, true ) === true )
				return $fail( 'the address is too long', 'vcard_long', [ Vcard::LIMIT_ADDRESS ], 'address' );
			if( $lines( $address ) > Vcard::MAX_LINES )
				return $fail( 'the address has too many lines', 'vcard_lines', [ Vcard::MAX_LINES ], 'address' );

			$texts = [];
			foreach( is_array( $posted['texts'] ?? null ) === true ? $posted['texts'] : [] as $locale => $own ) {

				if( is_string( $locale ) === false || in_array( $locale, $locales, true ) === false || is_array( $own ) === false )
					return $fail( 'a language the website does not have', 'vcard_locale', [], 'texts' );

				foreach( [ 'line' => [ Vcard::LIMIT_LINE, false ], 'text' => [ Vcard::LIMIT_TEXT, true ], 'hours' => [ Vcard::LIMIT_HOURS, false ] ] as $field => [ $max, $multiple ] )
					if( $long( $string( $own[$field] ?? null ), $max, $multiple ) === true )
						return $fail( 'the '. $field. ' is too long', 'vcard_long', [ $max ], 'texts.'. $locale. '.'. $field );

				$texts[$locale] = [ 'line' => $string( $own['line'] ?? null ), 'text' => $string( $own['text'] ?? null ), 'hours' => $string( $own['hours'] ?? null ) ];
			}

			$links = [];
			$posted['links'] = is_array( $posted['links'] ?? null ) === true ? array_values( $posted['links'] ) : [];

			if( count( $posted['links'] ) > Vcard::MAX_LINKS )
				return $fail( 'too many links', 'vcard_links', [ Vcard::MAX_LINKS ], 'links' );

			foreach( $posted['links'] as $index => $link ) {

				if( is_array( $link ) === false )
					return $fail( 'a link is a list of details', 'vcard_link', [], 'links.'. $index. '.url' );

				$url = Vcard::address( $string( $link['url'] ?? null ) );
				if( $url === null )
					return $fail( 'the address of a link is http, https, mailto or tel and has no space', 'vcard_link', [], 'links.'. $index. '.url' );

				$labels = [];
				foreach( is_array( $link['labels'] ?? null ) === true ? $link['labels'] : [] as $locale => $label ) {

					if( is_string( $locale ) === false || in_array( $locale, $locales, true ) === false )
						return $fail( 'a language the website does not have', 'vcard_locale', [], 'links.'. $index. '.labels' );

					if( $long( $string( $label ), Vcard::LIMIT_LABEL ) === true )
						return $fail( 'the name of a link is too long', 'vcard_long', [ Vcard::LIMIT_LABEL ], 'links.'. $index. '.labels.'. $locale );

					$labels[$locale] = $string( $label );
				}

				$links[] = [ 'id' => $string( $link['id'] ?? null ), 'url' => $url, 'labels' => $labels ];
			}

			// A client that does not send these (a panel that was loaded before they existed) leaves them as they are stored
			$own = Vcard::data( $appData );
			$posted += [ 'design' => $own['design'], 'scheme' => $own['scheme'], 'switch' => $own['switch'], 'accent' => $own['accent'] ];

			if( in_array( $posted['design'] ?? null, array_merge( [ '' ], Vcard::DESIGNS ), true ) === false )
				return $fail( 'the design is none of the designs', 'vcard_design', [], 'design' );

			if( in_array( $posted['scheme'] ?? null, Vcard::SCHEMES, true ) === false )
				return $fail( 'the scheme is none of auto, light and dark', 'vcard_scheme', [], 'scheme' );

			$accent = is_string( $posted['accent'] ?? null ) === true ? strtolower( $posted['accent'] ) : null;
			if( $accent === null || ( $accent !== '' && preg_match( Vcard::ACCENT, $accent ) !== 1 ) )
				return $fail( 'the accent is a colour of six hex digits', 'vcard_accent', [], 'accent' );

			if( is_array( $posted['alt'] ?? null ) === true )
				foreach( $posted['alt'] as $locale => $alt )
					if( is_string( $locale ) === false || in_array( $locale, $locales, true ) === false || is_string( $alt ) === false )
						return $fail( 'a language the website does not have', 'vcard_locale', [], 'alt' );
					else if( mb_strlen( (string) \Nino\Images::cleanAlt( $alt ) ) > Vcard::LIMIT_ALT )
						return $fail( 'the description of the photo is too long', 'vcard_long', [ Vcard::LIMIT_ALT ], 'alt.'. $locale );

			return [ 'data' => Vcard::normalize( $appData, [
				'picture'	=> $posted['picture'],
				'kind'		=> $posted['kind'],
				'name'		=> $string( $posted['name'] ?? null ),
				'phone'		=> $phone,
				'email'		=> $email,
				'website'	=> $website === '' ? '' : (string) Vcard::website( $website ),
				'address'	=> $address,
				'map'			=> ( $posted['map'] ?? true ) !== false,
				'save'		=> ( $posted['save'] ?? true ) !== false,
				'design'	=> $posted['design'],
				'scheme'	=> $posted['scheme'],
				'switch'	=> ( $posted['switch'] ?? false ) === true,
				'accent'	=> $accent,
				'blocks'	=> is_array( $posted['blocks'] ?? null ) === true ? $posted['blocks'] : [],
				'texts'		=> $texts,
				'links'		=> $links,
			] ) ];
		}

		/**
		 *	The slot of the photo, made in config.php where it is not there. A slot somebody made
		 *	with the same uri stays as it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	bool											Whether the slot is there
		 */
		private static function _slot( array &$appData ): bool {

			if( \Nino\Images::getSlot( $appData, Vcard::PHOTO ) !== false )
				return true;

			$label	= \Nino\Html::getFills( $appData, \Nino\Locales::getNativeLocale( $appData ) )['[[/feature/vcard/label/slot]]'] ?? '';
			$slot		= [ 'label' => $label !== '' ? $label : 'vCard photo', 'width' => Vcard::PHOTO_SIZE, 'height' => Vcard::PHOTO_SIZE, 'filename' => null ];

			$written = \Nino\Filesystem::mutate( $appData, '/config.php', static function( mixed $content ) use ( $slot ): ?array {

				if( is_array( $content ) === false )
					return null;

				if( is_array( $content['/nino/html/images'] ?? null ) === false )
					$content['/nino/html/images'] = [];

				if( isset( $content['/nino/html/images'][ Vcard::PHOTO ] ) === false )
					$content['/nino/html/images'][ Vcard::PHOTO ] = $slot;

				return $content;
			} );

			if( $written === false )
				return false;

			$appData['/nino/html/images'][ Vcard::PHOTO ] ??= $slot;

			return true;
		}

		/**
		 *	The name of a page as the project names it, its Element-URI where it has no name
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$uri
		 *
		 *	@return 	string
		 */
		private static function _pageName( array &$appData, string $uri ): string {

			$name = $uri === '' ? '' : \Nino\Html::renderTextfill( $appData, '/_nino/webpage'. $uri. '/name' );

			return $name !== '' ? $name : $uri;
		}

		/**
		 *	The pictures the panel shows the designs by, one each, rendered once and kept in assets/: the card of a
		 *	made-up person in the design, the light scheme on the left half and the dark one on the right
		 *
		 *	@return 	array											design => the picture as a data address
		 */
		private static function _previews(): array {

			$previews = [];

			foreach( Vcard::DESIGNS as $design ) {
				$file = \Nino\Features::dir(). '/Vcard/assets/preview-'. $design. '.webp';
				$previews[$design] = is_file( $file ) === true ? 'data:image/webp;base64,'. base64_encode( (string) file_get_contents( $file ) ) : '';
			}

			return $previews;
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											What the script draws the screen from
		 */
		private static function _screen( array &$appData ): array {

			$data			= Vcard::data( $appData );
			$locales	= \Nino\Locales::getAvailableLocales( $appData );
			$slot			= \Nino\Images::getSlot( $appData, Vcard::PHOTO );
			$logo			= [ 'manage' => \Nino\Auth::checkPermission( $appData, \Nino\Modules\Images\Admin::MANAGE_PERM ), 'slots' => [] ];
			$routes		= (array) ( $appData['/nino/http/routes'] ?? [] );
			$dir			= rtrim( \Nino\Filesystem::getDir( $appData ), '/' );

			// The two pictures of the logo, the image slots of the kernel the card's logo template draws
			foreach( [ '/logo', '/logo/dark' ] as $uri ) {

				$own = \Nino\Images::getSlot( $appData, $uri );

				if( is_array( $own ) === true )
					$logo['slots'][] = [ 'uri' => $uri, 'url' => is_string( $own['filename'] ?? null ) === true && $own['filename'] !== '' ? \Nino\Images::getUrl( $appData, $own['filename'] ) : null ];
			}

			// What a detail left empty falls back to: the project's own, as the page reads it
			$descriptions = [];
			foreach( $locales as $locale ) {
				$fills = \Nino\Html::getFills( $appData, $locale );
				$descriptions[$locale] = Vcard::line( strip_tags( (string) ( $fills['[[/project/company/general/description]]'] ?? '' ) ), Vcard::LIMIT_TEXT );
			}

			// Where the page is, what holds / instead, and what the button would do
			$place	= Vcard::placement( $appData );
			$plan		= $place['state'] === 'start' ? [] : Vcard::takeStart( \Nino\AppData::stored( $appData, '/nino/http/routes', [] ), $routes, static fn( array $route ): bool => Vcard::shows( $appData, $route ) );
			$names	= [];
			foreach( $locales as $locale ) {
				$name = \Nino\Html::renderTextfill( $appData, '/_nino/locale/'. $locale. '/name' );
				$names[$locale] = $name !== '' ? $name : $locale;
			}

			$legal = [ 'active' => class_exists( '\\Nino\\Modules\\Legal' ) === true && \Nino\Modules\Legal::url( $appData, 'imprint' ) !== '', 'imprint' => '', 'privacy' => '' ];
			if( $legal['active'] === true ) {
				$legal['imprint']	= \Nino\Modules\Legal::url( $appData, 'imprint' );
				$legal['privacy']	= \Nino\Modules\Legal::url( $appData, 'privacy' );
			}

			return [
				'data'			=> $data,
				'locales'		=> $locales,
				'native'		=> \Nino\Locales::getNativeLocale( $appData ),
				'inherited'	=> [
					'name'			=> Vcard::fact( $appData, '/project/company/general/name' ),
					'phone'			=> Vcard::fact( $appData, '/project/company/contact/phone' ),
					'email'			=> Vcard::fact( $appData, '/project/company/contact/email' ),
					'website'		=> Vcard::fact( $appData, '/project/website/general/url' ),
					'address'		=> Vcard::fact( $appData, '/project/company/contact/address', true ),
					'text'			=> $descriptions,
				],
				'photo'			=> [
					'url'		=> is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '' ? \Nino\Images::getUrl( $appData, $slot['filename'] ) : null,
					'size'	=> Vcard::PHOTO_SIZE,
					'alt'		=> is_array( $slot ) === true && is_array( $slot['alt'] ?? null ) === true ? $slot['alt'] : [],
				],
				'logo'			=> $logo,
				'page'			=> [
					'state'		=> $place['state'],
					'path'		=> $place['path'] === '' ? '' : $dir. $place['path'],
					'takenUri'	=> $place['taken'],
					'file'		=> $dir. Vcard::FILE,
					// What the button does: where the page that holds / goes, or that nothing is free
					'start'		=> $place['state'] === 'start' || isset( $plan['already'] ) === true ? null : [ 'blocked' => isset( $plan['error'] ) === true, 'reason' => (string) ( $plan['error'] ?? '' ), 'from' => (string) ( $plan['from'] ?? '' ), 'name' => self::_pageName( $appData, (string) ( $plan['from'] ?? '' ) ), 'to' => $dir. (string) ( $plan['to'] ?? '' ), 'moves' => (string) ( $plan['to'] ?? '' ) !== '', 'gone' => array_map( static fn( string $path ): string => $dir. $path, ( $plan['gone'] ?? [] ) !== [] ? array_values( (array) $plan['gone'] ) : ( $place['state'] === 'page' && $place['path'] !== '' ? [ $place['path'] ] : [] ) ) ],
				],
				'names'			=> $names,
				'legal'			=> $legal,
				'limits'		=> [
					'name'		=> Vcard::LIMIT_NAME,
					'line'		=> Vcard::LIMIT_LINE,
					'text'		=> Vcard::LIMIT_TEXT,
					'hours'		=> Vcard::LIMIT_HOURS,
					'address'	=> Vcard::LIMIT_ADDRESS,
					'alt'			=> Vcard::LIMIT_ALT,
					'label'		=> Vcard::LIMIT_LABEL,
					'url'			=> Vcard::LIMIT_URL,
					'phone'		=> Vcard::LIMIT_PHONE,
					'email'		=> Vcard::LIMIT_EMAIL,
					'links'		=> Vcard::MAX_LINKS,
				],
			];
		}
	}
}
