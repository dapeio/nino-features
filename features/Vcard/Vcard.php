<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\\Vcard			One quiet contact page, and the contact as a file
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino-features
 */
namespace Nino\Modules {

	/**
	 *	vCard. The details of the card live in one file, /data/vcard.php, which the
	 *	panel vCard writes (\Nino\Modules\Vcard\Admin) and this class reads: card()
	 *	answers what the page shows, with the details of the project - the name,
	 *	the phone number, the address of its setup - filling every detail the card
	 *	leaves empty, so that there is one place for each fact and no copy. The page
	 *	is [vcard] (the install unit's page template draws it, the feature's routes
	 *	put it at / where that is free and at /vcard otherwise) and the contact
	 *	file is /vcard.vcf, a vCard 3.0 that file() writes.
	 *
	 *	Everything the card holds is an editor's, and treated as untrusted: a value
	 *	is escaped with the bracket neutralised before it is filled into a
	 *	template (what a shortcode returns is rendered again), an address of a link
	 *	is held to http, https, mailto and tel, and a value that goes into the file
	 *	is cleaned of its line breaks and masked, so that no field can add a
	 *	property of its own.
	 */
	class Vcard {

		public const string STORAGE		= '/data/vcard.php';
		public const string TEMPLATES	= '/features/Vcard/templates';

		// The Element-URI of the page, the slot of the photo and the file's address
		public const string PAGE			= '/vcard';
		public const string PHOTO			= '/vcard/photo';
		public const string FILE			= '/vcard.vcf';

		// The photo is cut square to this many pixels: shown at 8rem it is sharp on a screen of three times
		public const int PHOTO_SIZE		= 480;

		// What a detail may hold, in characters
		public const int LIMIT_NAME			= 120;
		public const int LIMIT_LINE			= 120;
		public const int LIMIT_TEXT			= 1000;
		public const int LIMIT_HOURS		= 160;
		public const int LIMIT_ADDRESS	= 240;
		public const int LIMIT_ALT			= 250;
		public const int LIMIT_LABEL		= 60;
		public const int LIMIT_URL			= 500;
		public const int LIMIT_EMAIL		= 254;
		public const int LIMIT_PHONE		= 30;
		public const int MAX_LINKS			= 20;
		public const int MAX_LINES			= 5;

		// A text of more than this many characters is set from the left: centred lines of that length are hard to read
		public const int LONG_TEXT			= 160;

		public const array PICTURES		= [ 'auto', 'photo', 'logo', 'none' ];
		public const array KINDS			= [ 'person', 'organization' ];

		// The blocks of the card the panel switches one by one: a block that is off is not on the page, in the data
		// or in the contact file, and keeps what it holds. The name itself stays in the file and the data, which have
		// no card without one; the line and the text go with the block
		public const array BLOCKS			= [ 'picture', 'name', 'contact', 'links', 'times', 'legal' ];

		// The longest a line of the file is, in octets, the line break not counted (RFC 2426, 2.6)
		private const int FOLD				= 75;

		// An id of a link as the panel makes one
		private const string LINK_ID	= '/^[a-z0-9]{1,12}$/D';

		// The characters that turn the order of what is read around or hide it: the embeddings and overrides, the isolates
		private const string BIDI			= '/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

		// What an address may not hold: a space or control character of any kind, a mark that is no letter, a quote, a bracket of a tag
		private const string URL_FORBIDDEN	= '/[\s\p{Cc}\p{Cf}\x{2028}\x{2029}"\'<>\\\\`{}|^]/u';

		// The titles of a name that the file keeps apart from it - a short fixed list, nothing is guessed beyond it
		private const array TITLES		= [ 'prof.', 'prof', 'dr.', 'dr', 'mag.', 'dipl.-ing.', 'ing.', 'mr.', 'mr', 'mrs.', 'mrs', 'ms.', 'ms', 'sir', 'dame' ];

		// The scheme of an address a link may have, and what a phone number is allowed to hold
		private const string URL_SCHEME	= '/^(?:https?|mailto|tel):/i';
		private const string PHONE			= '/^\+?[0-9][0-9 ()\/.\-]{1,28}[0-9]$/D';

		// The picture of the photo slot, drawn by the kernel: cut as the slot says, with the alt text the slot keeps
		private static string $image		= '[image /vcard/photo alt=""]';

		// The map link: a search at OpenStreetMap, which sets no cookie and wants no account
		private static string $mapSearch	= 'https://www.openstreetmap.org/search?query=';

		// The words of the card, by the place they stand in: the fill each template line reads
		private const array LABELS			= [
			'phone'		=> '[[/feature/vcard/label/phone]]',
			'email'		=> '[[/feature/vcard/label/email]]',
			'website'	=> '[[/feature/vcard/label/website]]',
			'address'	=> '[[/feature/vcard/label/address]]',
			'hours'		=> '[[/feature/vcard/label/hours]]',
		];
		private const array LEGAL				= [
			'imprint'	=> '[[/feature/vcard/legal/imprint]]',
			'privacy'	=> '[[/feature/vcard/legal/privacy]]',
		];

		/**
		 *	Module initiating: the shortcode, the page where it has no address yet,
		 *	the file and the callback that answers it. The stylesheet reaches the
		 *	site's bundle through the manifest's assets
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	void
		 */
		public static function init( array &$appData ): void {

			\Nino\Feature::shortcode( $appData, 'vcard', [ self::class, 'doShortcode' ] );

			/*	The install unit puts the page at / where the project has no start page.
				Everywhere else the page has to be reachable all the same, at /vcard - but
				only where no route shows it already (at / or at an address of the Routes
				panel) and the address is free: a route of the project is never replaced */
			if( \Nino\Http::findRouteUri( $appData, self::PAGE, \Nino\Locales::getCurrentLocale( $appData ) ) === null && isset( $appData['/nino/http/routes']['GET://vcard'] ) === false )
				$appData['/nino/http/routes']['GET://vcard'] = [ 'uri' => self::PAGE, 'body' => '[template /templates/page-vcard]' ];

			if( isset( $appData['/nino/http/routes']['GET://vcard.vcf'] ) === false )
				$appData['/nino/http/routes']['GET://vcard.vcf'] = [ 'uri' => self::FILE, 'body' => '', 'header' => [ 'Content-Type' => 'text/vcard; charset=utf-8' ] ];

			\Nino\Callbacks::registerCallback( $appData, '/nino/http/response/GET:/'. self::FILE, [ self::class, 'callbackFile' ] );
		}

		public static function adminPanels( array &$appData ): array {
			return [ \Nino\Modules\Vcard\Admin::class ];
		}

		// --- What is stored -------------------------------------------------------------

		/**
		 *	The details of the card as the file holds them, in the shape they always have:
		 *	every key there, a value of the wrong kind dropped to its default, a language the
		 *	site does not have and a link without a safe address left out. A file that was
		 *	edited by hand is read as it is, not repaired
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function data( array &$appData ): array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::STORAGE, [] );

			return self::normalize( $appData, is_array( $stored ) === true ? $stored : [] );
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$stored				What a file or a request holds
		 *
		 *	@return 	array
		 */
		public static function normalize( array &$appData, array $stored ): array {

			$locales	= \Nino\Locales::getAvailableLocales( $appData );
			$string		= static fn( mixed $value ): string => is_string( $value ) === true ? $value : '';

			$data = [
				'format'	=> 1,
				'picture'	=> in_array( $stored['picture'] ?? null, self::PICTURES, true ) === true ? $stored['picture'] : 'auto',
				'kind'		=> in_array( $stored['kind'] ?? null, self::KINDS, true ) === true ? $stored['kind'] : 'organization',
				'name'		=> self::line( $string( $stored['name'] ?? null ), self::LIMIT_NAME ),
				'phone'		=> self::line( $string( $stored['phone'] ?? null ), self::LIMIT_PHONE ),
				'email'		=> self::line( $string( $stored['email'] ?? null ), self::LIMIT_EMAIL ),
				'website'	=> self::line( $string( $stored['website'] ?? null ), self::LIMIT_URL ),
				'address'	=> self::lines( $string( $stored['address'] ?? null ), self::LIMIT_ADDRESS, self::MAX_LINES ),
				'map'			=> ( $stored['map'] ?? true ) !== false,
				'save'		=> ( $stored['save'] ?? true ) !== false,
				'blocks'	=> [],
				'texts'		=> [],
				'links'		=> [],
			];

			foreach( self::BLOCKS as $block )
				$data['blocks'][$block] = ( $stored['blocks'][$block] ?? true ) !== false;

			foreach( $locales as $locale ) {

				$own = is_array( $stored['texts'][$locale] ?? null ) === true ? $stored['texts'][$locale] : [];

				$data['texts'][$locale] = [
					'line'		=> self::line( $string( $own['line'] ?? null ), self::LIMIT_LINE ),
					'text'		=> self::lines( $string( $own['text'] ?? null ), self::LIMIT_TEXT, 30 ),
					'hours'		=> self::line( $string( $own['hours'] ?? null ), self::LIMIT_HOURS ),
				];
			}

			foreach( is_array( $stored['links'] ?? null ) === true ? array_values( $stored['links'] ) : [] as $link ) {

				if( is_array( $link ) === false || count( $data['links'] ) >= self::MAX_LINKS )
					continue;

				$url = self::address( $string( $link['url'] ?? null ) );

				if( $url === null )
					continue;

				$labels = [];
				foreach( $locales as $locale )
					$labels[$locale] = self::line( $string( $link['labels'][$locale] ?? null ), self::LIMIT_LABEL );

				$id = $string( $link['id'] ?? null );

				$data['links'][] = [ 'id' => preg_match( self::LINK_ID, $id ) === 1 ? $id : substr( md5( $url ), 0, 8 ), 'url' => $url, 'labels' => $labels ];
			}

			return $data;
		}

		/**
		 *	A text as one line: every control character, a line break among them, becomes a
		 *	space, runs of spaces become one, the ends are trimmed and the text is cut at
		 *	$max characters. Not valid UTF-8 gives ''
		 *
		 *	@param		string		$value
		 *	@param		int				$max
		 *
		 *	@return 	string
		 */
		public static function line( string $value, int $max ): string {

			if( preg_match( '//u', $value ) !== 1 )
				return '';

			$value = (string) preg_replace( self::BIDI, '', $value );
			$value = trim( (string) preg_replace( '/[\x00-\x1F\x7F\x{2028}\x{2029}]+|\s{2,}/u', ' ', $value ) );

			return mb_substr( $value, 0, $max );
		}

		/**
		 *	A text of several lines: the line breaks are kept as "\n" (CR LF and CR are one),
		 *	every other control character becomes a space, a line is trimmed, empty lines at
		 *	the ends go and several empty lines in a row are one; at most $lines lines and
		 *	$max characters
		 *
		 *	@param		string		$value
		 *	@param		int				$max
		 *	@param		int				$lines
		 *
		 *	@return 	string
		 */
		public static function lines( string $value, int $max, int $lines ): string {

			if( preg_match( '//u', $value ) !== 1 )
				return '';

			$value	= (string) preg_replace( self::BIDI, '', $value );
			$value	= (string) preg_replace( '/\r\n?|\x{2028}|\x{2029}/u', "\n", $value );
			$value	= (string) preg_replace( '/[\x00-\x09\x0B-\x1F\x7F]+/u', ' ', $value );
			$rows		= array_map( static fn( string $row ): string => trim( (string) preg_replace( '/[ \t]{2,}/', ' ', $row ) ), explode( "\n", $value ) );
			$text		= trim( (string) preg_replace( '/\n{3,}/', "\n\n", implode( "\n", array_slice( $rows, 0, $lines ) ) ) );

			return mb_substr( $text, 0, $max );
		}

		/**
		 *	The address of a link as it may be kept: http, https, mailto or tel and nothing
		 *	else, no space, control character, quote, angle bracket or backslash in it, and
		 *	a host for a web address. Null for one that does not pass
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string|null
		 */
		public static function address( string $url ): ?string {

			$url = trim( $url );

			if( preg_match( '//u', $url ) !== 1 || $url === '' || strlen( $url ) > self::LIMIT_URL || preg_match( self::URL_SCHEME, $url ) !== 1 || preg_match( self::URL_FORBIDDEN, $url ) === 1 )
				return null;

			$scheme = strtolower( (string) strstr( $url, ':', true ) );

			if( $scheme === 'mailto' )
				return self::email( substr( $url, 7 ) ) === null ? null : 'mailto:'. substr( $url, 7 );

			if( $scheme === 'tel' )
				return preg_match( '/^tel:\+?[0-9][0-9().\-]{1,28}[0-9]$/i', $url ) === 1 ? 'tel:'. substr( $url, 4 ) : null;

			$parts = parse_url( $url );

			if( is_array( $parts ) === false || preg_match( '#^https?://[^/?\#]+#i', $url ) !== 1 || isset( $parts['host'] ) === false || preg_match( '/^[\p{L}\p{N}]([\p{L}\p{N}.-]*[\p{L}\p{N}])?$/u', $parts['host'] ) !== 1 || isset( $parts['user'] ) === true || isset( $parts['pass'] ) === true )
				return null;

			return $scheme. substr( $url, strlen( $scheme ) );
		}

		/**
		 *	The address of a website as a person writes it - with or without https:// - in the
		 *	form it is kept: a web address with a host that has a dot, else null
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string|null
		 */
		public static function website( string $value ): ?string {

			$value = trim( $value );

			if( $value === '' )
				return null;

			$url = preg_match( '#^[a-z][a-z0-9+.-]*:#i', $value ) === 1 ? $value : 'https://'. $value;

			if( preg_match( '#^https?://#i', $url ) !== 1 )
				return null;

			$url = self::address( $url );
			$host = $url === null ? null : parse_url( $url, PHP_URL_HOST );

			return is_string( $host ) === true && str_contains( $host, '.' ) === true ? $url : null;
		}

		/**
		 *	An e-mail address in the plain form: no name, no comment, no quoting, no space
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string|null
		 */
		public static function email( string $value ): ?string {

			$value = trim( $value );

			if( $value === '' || strlen( $value ) > self::LIMIT_EMAIL || preg_match( '/^[A-Za-z0-9.!#$%&*+\/=?^_`{|}~-]+@[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$/D', $value ) !== 1 || filter_var( $value, FILTER_VALIDATE_EMAIL ) === false )
				return null;

			return $value;
		}

		/**
		 *	A phone number the way people write them - digits, a leading +, spaces, brackets,
		 *	slash, dot and hyphen - with at least five digits and at most fifteen
		 *
		 *	@param		string		$value
		 *
		 *	@return 	bool
		 */
		public static function phone( string $value ): bool {

			$digits = strlen( (string) preg_replace( '/\D/', '', $value ) );

			return preg_match( self::PHONE, $value ) === 1 && $digits >= 5 && $digits <= 15;
		}

		// --- What the page shows ---------------------------------------------------------

		/**
		 *	The languages to look for a text in: the current one, the native one, then the rest
		 *	of the site's in their order - the chain the kernel's fills have
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array
		 */
		public static function chain( array &$appData ): array {
			return array_values( array_unique( array_merge( [ \Nino\Locales::getCurrentLocale( $appData ), \Nino\Locales::getNativeLocale( $appData ) ], \Nino\Locales::getAvailableLocales( $appData ) ) ) );
		}

		/**
		 *	The fact a project has of itself under a key of its setup, one of
		 *	/project/company/ or /project/website/general/ - in the current language, the
		 *	fills inside it resolved, written as the plain text it is
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$key
		 *	@param		bool			$multiline		Whether the fact may have several lines
		 *
		 *	@return 	string										'' where the project has none
		 */
		public static function fact( array &$appData, string $key, bool $multiline = false ): string {

			$value = \Nino\Html::resolveTextfill( $appData, $key );

			if( is_string( $value ) === false )
				return '';

			$value = html_entity_decode( strip_tags( $value ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

			return $multiline === true ? self::lines( $value, self::LIMIT_ADDRESS, self::MAX_LINES ) : self::line( $value, self::LIMIT_URL );
		}

		/**
		 *	What the page shows and the file holds: every detail of the card in the language
		 *	of the request, the project's own where the card has none
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											kind, name, company, line, text, hours, phone, email, website, address, country, map, save, links, picture
		 */
		public static function card( array &$appData ): array {

			$data			= self::data( $appData );
			$chain		= self::chain( $appData );
			$pick			= static function( string $field ) use ( $data, $chain ): string {
				foreach( $chain as $locale )
					if( ( $data['texts'][$locale][$field] ?? '' ) !== '' )
						return $data['texts'][$locale][$field];
				return '';
			};
			$company	= self::fact( $appData, '/project/company/general/name' );
			$own			= $data['name'] !== '';
			$address	= $data['address'] !== '' ? $data['address'] : self::fact( $appData, '/project/company/contact/address', true );
			$website	= self::website( $data['website'] !== '' ? $data['website'] : self::fact( $appData, '/project/website/general/url' ) );
			$email		= self::email( $data['email'] !== '' ? $data['email'] : self::fact( $appData, '/project/company/contact/email' ) );
			$phone		= $data['phone'] !== '' ? $data['phone'] : self::fact( $appData, '/project/company/contact/phone' );
			$links		= [];
			$on				= $data['blocks'];

			foreach( $data['links'] as $link ) {

				$label = '';
				foreach( $chain as $locale )
					if( ( $link['labels'][$locale] ?? '' ) !== '' ) {
						$label = $link['labels'][$locale];
						break;
					}

				$links[] = [ 'url' => $link['url'], 'label' => $label !== '' ? $label : self::shown( $link['url'] ) ];
			}

			return [
				// What the panel says is what is written; a person without a name of its own is an organization, which the panel refuses and a file edited by hand may hold
				'kind'		=> $own === true && $data['kind'] === 'person' ? 'person' : 'organization',
				'name'		=> $own === true ? $data['name'] : $company,
				'company'	=> $company,
				'line'		=> $on['name'] === true ? $pick( 'line' ) : '',
				// The description of the company is the text of the company; a person has the words they wrote, or none
				'text'		=> $on['name'] === false ? '' : ( $pick( 'text' ) !== '' || $own === true ? $pick( 'text' ) : self::fact( $appData, '/project/company/general/description', true ) ),
				'hours'		=> $on['times'] === true ? $pick( 'hours' ) : '',
				'phone'		=> $on['contact'] === true && self::phone( $phone ) === true ? $phone : '',
				'email'		=> $on['contact'] === true ? $email ?? '' : '',
				'website'	=> $on['contact'] === true ? $website ?? '' : '',
				'address'	=> $on['contact'] === true ? $address : '',
				'country'	=> $on['contact'] === true && $data['address'] === '' && $address !== '' ? self::fact( $appData, '/project/company/contact/country' ) : '',
				'map'			=> $data['map'],
				'save'		=> $data['save'],
				'links'		=> $on['links'] === true ? $links : [],
				'picture'	=> $on['picture'] === true ? self::picture( $appData, $data['picture'] ) : '',
				'blocks'	=> $on,
			];
		}

		/**
		 *	What a link is called when nobody named it: the address without its scheme and
		 *	without the slash at its end
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string
		 */
		public static function shown( string $url ): string {
			return rtrim( (string) preg_replace( '#^(?:https?://|mailto:|tel:)(?:www\.)?#i', '', $url ), '/' );
		}

		/**
		 *	The address a phone number is called at: the digits and a leading +. Where the number
		 *	starts with + the (0) of "+49 (0) 170 1234567" is left out, the trunk prefix that is
		 *	not dialled from abroad
		 *
		 *	@param		string		$phone				As a person wrote it, which phone() has passed
		 *
		 *	@return 	string										'tel:+491701234567'
		 */
		public static function telHref( string $phone ): string {

			$phone = trim( $phone );

			if( str_starts_with( $phone, '+' ) === true )
				$phone = (string) preg_replace( '/\(\s*0\s*\)/', '', $phone );

			return 'tel:'. preg_replace( '/[^0-9+]/', '', $phone );
		}

		/**
		 *	Which picture the card shows: 'photo' where the choice allows it and the slot has a
		 *	file, 'logo' where it allows that and the project has a logo, else ''
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$choice				One of PICTURES
		 *
		 *	@return 	string
		 */
		public static function picture( array &$appData, string $choice ): string {

			$has = static function( string $uri ) use ( &$appData ): bool {
				$slot = \Nino\Images::getSlot( $appData, $uri );
				return $slot !== false && is_string( $slot['filename'] ?? null ) === true && $slot['filename'] !== '';
			};

			if( ( $choice === 'auto' || $choice === 'photo' ) && $has( self::PHOTO ) === true )
				return 'photo';

			if( ( $choice === 'auto' || $choice === 'logo' ) && ( $has( '/logo' ) === true || $has( '/logo/dark' ) === true ) )
				return 'logo';

			return '';
		}

		/**
		 *	[vcard] - the card. Every part that has nothing to show is left out whole. With the flag foot
		 *	the card is the page itself and carries its copyright line and the language picker
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$args					Shortcode arguments (the flag foot)
		 *
		 *	@return 	string										'' where the card holds nothing at all
		 */
		public static function doShortcode( array &$appData, array $args ): string {

			$card		= self::card( $appData );
			$escape	= static fn( string $value ): string => \Nino\Modules\Components::escape( $value );
			$part		= static fn( string $name, array $tokens ): string => str_replace( array_keys( $tokens ), array_values( $tokens ), \Nino\Feature::template( $appData, self::TEMPLATES, $name ) );

			// With the flag foot the card is the page: its name is the heading of the page
			$whole = \Nino\Feature::flag( $args, 'foot' );
			$items = '';

			// A fixed order, the one a person is asked for it in the panel
			if( $card['phone'] !== '' )
				$items .= $part( 'vcard-item', [ '[[label]]' => self::LABELS['phone'], '[[value]]' => $part( 'vcard-link', [ '[[href]]' => $escape( self::telHref( $card['phone'] ) ), '[[attributes]]' => '', '[[text]]' => $escape( $card['phone'] ) ] ) ] );

			if( $card['email'] !== '' )
				$items .= $part( 'vcard-item', [ '[[label]]' => self::LABELS['email'], '[[value]]' => $part( 'vcard-link', [ '[[href]]' => $escape( 'mailto:'. $card['email'] ), '[[attributes]]' => '', '[[text]]' => $escape( $card['email'] ) ] ) ] );

			if( $card['website'] !== '' )
				$items .= $part( 'vcard-item', [ '[[label]]' => self::LABELS['website'], '[[value]]' => $part( 'vcard-link', [ '[[href]]' => $escape( $card['website'] ), '[[attributes]]' => self::rel( $card['website'] ), '[[text]]' => $escape( self::shown( $card['website'] ) ) ] ) ] );

			if( $card['address'] !== '' ) {

				$map = $card['map'] === true ? $part( 'vcard-map', [ '[[href]]' => $escape( self::$mapSearch. rawurlencode( str_replace( "\n", ', ', $card['address'] ) ) ) ] ) : '';

				$items .= $part( 'vcard-item', [ '[[label]]' => self::LABELS['address'], '[[value]]' => $part( 'vcard-address', [ '[[text]]' => $escape( $card['address'] ), '[[map]]' => $map ] ) ] );
			}

			if( $card['hours'] !== '' )
				$items .= $part( 'vcard-item', [ '[[label]]' => self::LABELS['hours'], '[[value]]' => $part( 'vcard-hours', [ '[[text]]' => $escape( $card['hours'] ) ] ) ] );

			$links = '';
			foreach( $card['links'] as $link )
				$links .= $part( 'vcard-more', [ '[[href]]' => $escape( $link['url'] ), '[[attributes]]' => self::rel( $link['url'] ), '[[text]]' => $escape( $link['label'] ) ] );

			$legal = '';
			foreach( self::LEGAL as $page => $label ) {

				$url = $card['blocks']['legal'] === true && class_exists( '\\Nino\\Modules\\Legal' ) === true ? \Nino\Modules\Legal::url( $appData, $page ) : '';

				if( $url !== '' )
					$legal .= $part( 'vcard-legal-link', [ '[[href]]' => $escape( $url ), '[[text]]' => $label ] );
			}

			$parts = [
				'[[picture]]'	=> $card['picture'] === 'photo' ? $part( 'vcard-photo', [ '[[image]]' => self::$image ] ) : ( $card['picture'] === 'logo' ? $part( 'vcard-logo', [] ) : '' ),
				'[[name]]'		=> $card['name'] !== '' && $card['blocks']['name'] === true ? $part( 'vcard-name', [ '[[tag]]' => $whole === true ? 'h1' : 'h2', '[[name]]' => $escape( $card['name'] ) ] ) : '',
				'[[line]]'		=> $card['line'] !== '' ? $part( 'vcard-line', [ '[[line]]' => $escape( $card['line'] ) ] ) : '',
				'[[text]]'		=> $card['text'] !== '' ? $part( 'vcard-text', [ '[[modifier]]' => mb_strlen( $card['text'] ) > self::LONG_TEXT ? ' nino-vcard-text--long' : '', '[[text]]' => $escape( $card['text'] ) ] ) : '',
				'[[contacts]]'	=> $items !== '' ? $part( 'vcard-contacts', [ '[[items]]' => $items ] ) : '',
				'[[links]]'		=> $links !== '' ? $part( 'vcard-links', [ '[[items]]' => $links ] ) : '',
				'[[save]]'		=> $card['save'] === true && self::file( $appData ) !== '' ? $part( 'vcard-save', [ '[[href]]' => $escape( rtrim( \Nino\Filesystem::getDir( $appData ), '/' ). self::FILE ), '[[file]]' => $escape( self::filename( $card ) ) ] ) : '',
				'[[legal]]'		=> $legal !== '' ? $part( 'vcard-legal', [ '[[items]]' => $legal ] ) : '',
				'[[foot]]'		=> $whole === true ? self::foot( $appData, $card, $part, $escape ) : '',
				'[[data]]'		=> $card['name'] !== '' ? $part( 'vcard-data', [ '[[json]]' => self::structured( $appData, $card ) ] ) : '',
			];

			// Nothing to show: no name, no way to reach anybody, no picture. The card says so in one sentence of the project's words
			// - and keeps the links to the legal pages, the one thing a page of this website owes a visitor
			// - and offers nothing to save, since the file would hold a name that is shown nowhere
			if( $parts['[[name]]'] === '' && $items === '' && $links === '' && $parts['[[picture]]'] === '' && $parts['[[text]]'] === '' ) {
				$parts['[[name]]'] = $part( 'vcard-empty', [] );
				$parts['[[save]]'] = '';
			}
			// The name of a card whose block is off is still the heading of a page that is the card alone, for those who do not see it
			else if( $whole === true && $parts['[[name]]'] === '' && $card['name'] !== '' )
				$parts['[[name]]'] = $part( 'vcard-title', [ '[[name]]' => $escape( $card['name'] ) ] );

			return str_replace( array_keys( $parts ), array_values( $parts ), \Nino\Feature::template( $appData, self::TEMPLATES, 'vcard' ) );
		}

		/**
		 *	The foot of a page that is the card alone: the language picker of the kernel where the
		 *	website has several languages and its module is on, and the copyright line with the year
		 *	and the name of the company
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$card
		 *	@param		callable	$part					Fills a template of the feature
		 *	@param		callable	$escape
		 *
		 *	@return 	string
		 */
		private static function foot( array &$appData, array $card, callable $part, callable $escape ): string {

			$copyright = $card['company'] !== '' ? $part( 'vcard-copyright', [ '[[company]]' => $escape( $card['company'] ) ] ) : '';
			$locales = count( \Nino\Locales::getAvailableLocales( $appData ) ) > 1 && in_array( '\\Nino\\Modules\\Localepicker', (array) ( $appData['/nino/modules'] ?? [] ), true ) === true ? $part( 'vcard-locales', [] ) : '';

			return $copyright === '' && $locales === '' ? '' : $part( 'vcard-foot', [ '[[locales]]' => $locales, '[[copyright]]' => $copyright ] );
		}

		/**
		 *	The attribute of a link to another website: the destination learns nothing of this page
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string
		 */
		private static function rel( string $url ): string {
			return preg_match( '#^https?://#i', $url ) === 1 ? ' rel="noopener noreferrer"' : '';
		}

		/**
		 *	The card as schema.org data, a Person, or an Organization where the card has no
		 *	name of its own: the same details the page shows, written as json that can sit in
		 *	a script element and holds no bracket for the next rendering pass to read
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$card
		 *
		 *	@return 	string
		 */
		public static function structured( array &$appData, array $card ): string {

			$person	= $card['kind'] === 'person';
			$graph	= [ '@context' => 'https://schema.org', '@type' => $person === true ? 'Person' : 'Organization', 'name' => $card['name'] ];

			if( $person === true && $card['line'] !== '' )
				$graph['jobTitle'] = $card['line'];

			if( $person === true && $card['company'] !== '' && $card['company'] !== $card['name'] )
				$graph['worksFor'] = [ '@type' => 'Organization', 'name' => $card['company'] ];

			if( $card['text'] !== '' )
				$graph['description'] = $card['text'];

			foreach( [ 'phone' => 'telephone', 'email' => 'email', 'website' => 'url' ] as $field => $property )
				if( $card[$field] !== '' )
					$graph[$property] = $card[$field];

			if( $card['address'] !== '' )
				$graph['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => str_replace( "\n", ', ', $card['address'] ) ]
					+ ( $card['country'] !== '' ? [ 'addressCountry' => $card['country'] ] : [] );

			$same = array_values( array_filter( array_column( $card['links'], 'url' ), static fn( string $url ): bool => preg_match( '#^https?://#i', $url ) === 1 ) );

			if( $same !== [] )
				$graph['sameAs'] = $same;

			if( $card['picture'] === 'photo' ) {

				$slot	= \Nino\Images::getSlot( $appData, self::PHOTO );
				$path	= is_array( $slot ) === true && is_string( $slot['filename'] ?? null ) === true ? \Nino\Images::getUrl( $appData, $slot['filename'] ) : '';
				$dir	= rtrim( \Nino\Filesystem::getDir( $appData ), '/' );
				$url	= $path !== '' ? \Nino\Html::absoluteUrl( $appData, $dir !== '' && str_starts_with( $path, $dir. '/' ) === true ? substr( $path, strlen( $dir ) ) : $path ) : '';

				if( $url !== '' )
					$graph['image'] = $url;
			}

			/*	A bracket is written as [ inside a string, which is the same string for json and
				nothing for a fill or a shortcode. The brackets of the list sameAs stand in no string:
				they are followed by a quote and no word, and open nothing	*/
			array_walk_recursive( $graph, static function( mixed &$value ): void {
				if( is_string( $value ) === true )
					$value = str_replace( '[', "\x01", $value );
			} );

			$json = json_encode( $graph, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );

			return str_replace( '\\u0001', '\\u005b', $json === false ? '{}' : $json );
		}

		// --- The file ----------------------------------------------------------------------

		/**
		 *	The file's name for the download: the name as a slug, else "contact"
		 *
		 *	@param		array			$card
		 *
		 *	@return 	string
		 */
		public static function filename( array $card ): string {

			$slug = \Nino\Feature::slug( (string) $card['name'], 40 );

			return ( $slug !== '' ? $slug : 'contact' ). '.vcf';
		}

		/**
		 *	The contact as a vCard 3.0 (RFC 2426): CRLF after every line, lines of at most 75
		 *	octets folded with a space, a comma, a semicolon, a backslash and a line break in
		 *	a value written as \, \; \\ and \n. Every value is cleaned first, so that a line
		 *	break in a detail cannot start a property of its own. No photo: the picture is
		 *	stored as WebP, which an address book does not take as a PHOTO, and the file is a
		 *	public download
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										'' where the card names nobody
		 */
		public static function file( array &$appData ): string {
			return self::compose( self::card( $appData ) );
		}

		/**
		 *	@param		array			$card					What card() answers
		 *
		 *	@return 	string
		 */
		public static function compose( array $card ): string {

			$name = self::line( (string) $card['name'], self::LIMIT_NAME );

			if( $name === '' )
				return '';

			$person	= $card['kind'] === 'person';
			$text		= static fn( string $value ): string => self::mask( $value );
			$lines	= [ 'BEGIN:VCARD', 'VERSION:3.0' ];

			if( $person === true ) {

				// The titles at the start (a short fixed list) are the prefix; the last word of the rest is the family name:
				// "Dr. Ada Lovelace" is Lovelace;Ada;;Dr. - a name of one word is the family name alone
				$words	= explode( ' ', $name );
				$prefix	= [];
				while( count( $words ) > 1 && in_array( mb_strtolower( $words[0] ), self::TITLES, true ) === true )
					$prefix[] = array_shift( $words );

				$rest		= implode( ' ', $words );
				$cut		= strrpos( $rest, ' ' );
				$family	= $cut === false ? $rest : substr( $rest, $cut + 1 );
				$given	= $cut === false ? '' : substr( $rest, 0, $cut );

				$lines[] = 'N:'. $text( $family ). ';'. $text( $given ). ';;'. $text( implode( ' ', $prefix ) ). ';';
				$lines[] = 'FN:'. $text( $name );

				$company = self::line( (string) $card['company'], self::LIMIT_NAME );
				if( $company !== '' && $company !== $name )
					$lines[] = 'ORG:'. $text( $company );

				if( (string) $card['line'] !== '' )
					$lines[] = 'TITLE:'. $text( self::line( (string) $card['line'], self::LIMIT_LINE ) );
			}
			else {

				$lines[] = 'N:'. $text( $name ). ';;;;';
				$lines[] = 'FN:'. $text( $name );
				$lines[] = 'ORG:'. $text( $name );
				$lines[] = 'X-ABShowAs:COMPANY';
			}

			if( (string) $card['phone'] !== '' && self::phone( (string) $card['phone'] ) === true )
				$lines[] = 'TEL;TYPE=WORK,VOICE:'. $text( (string) $card['phone'] );

			if( (string) $card['email'] !== '' && self::email( (string) $card['email'] ) !== null )
				$lines[] = 'EMAIL;TYPE=INTERNET,WORK:'. self::plain( (string) $card['email'] );

			if( (string) $card['website'] !== '' && self::address( (string) $card['website'] ) !== null )
				$lines[] = 'URL:'. self::plain( (string) $card['website'] );

			if( (string) $card['address'] !== '' )
				$lines[] = 'ADR;TYPE=WORK:;;'. $text( (string) $card['address'] ). ';;;;'. $text( (string) $card['country'] );

			$group = 0;
			foreach( (array) $card['links'] as $link ) {

				$url = self::address( (string) ( $link['url'] ?? '' ) );

				if( $url === null )
					continue;

				$group++;
				$lines[] = 'item'. $group. '.URL:'. self::plain( $url );
				$lines[] = 'item'. $group. '.X-ABLabel:'. $text( self::line( (string) ( $link['label'] ?? '' ), self::LIMIT_LABEL ) );
			}

			$note = array_values( array_filter( [ $person === true ? '' : (string) $card['line'], (string) $card['text'], (string) $card['hours'] ], static fn( string $row ): bool => $row !== '' ) );

			if( $note !== [] )
				$lines[] = 'NOTE:'. $text( implode( "\n", $note ) );

			$lines[] = 'END:VCARD';

			return implode( "\r\n", array_map( [ self::class, 'fold' ], $lines ) ). "\r\n";
		}

		/**
		 *	A value of the type TEXT: the control characters but the line break go, the
		 *	backslash, the semicolon and the comma are masked, a line break is \n
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function mask( string $value ): string {

			if( preg_match( '//u', $value ) !== 1 )
				return '';

			$value = (string) preg_replace( '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value );

			return strtr( $value, [ '\\' => '\\\\', ';' => '\\;', ',' => '\\,', "\r\n" => '\\n', "\n" => '\\n', "\r" => '\\n' ] );
		}

		/**
		 *	A value of the type URI or an e-mail address: not masked, as the format has it,
		 *	and with nothing in it that could end the line
		 *
		 *	@param		string		$value
		 *
		 *	@return 	string
		 */
		public static function plain( string $value ): string {
			return (string) preg_replace( '/[\x00-\x20\x7F]+/', '', $value );
		}

		/**
		 *	One line folded at 75 octets: the continuation lines start with a space, which
		 *	counts, and a character of UTF-8 is never cut
		 *
		 *	@param		string		$line
		 *
		 *	@return 	string										The line, with "\r\n " between its parts
		 */
		public static function fold( string $line ): string {

			if( strlen( $line ) <= self::FOLD )
				return $line;

			$parts	= [];
			$part		= '';
			$limit	= self::FOLD;

			foreach( preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY ) ?: [] as $character ) {

				if( strlen( $part ) + strlen( $character ) > $limit ) {
					$parts[]	= $part;
					$part			= '';
					$limit		= self::FOLD - 1;
				}

				$part .= $character;
			}

			$parts[] = $part;

			return implode( "\r\n ", $parts );
		}

		// --- Where the page is ---------------------------------------------------------------

		/**
		 *	Whether a route draws the card: its body is, or includes, a template that holds [vcard].
		 *	A route that names the Element-URI of the card but has been given another template does not
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			$route
		 *
		 *	@return 	bool
		 */
		public static function shows( array &$appData, array $route ): bool {

			$body = is_string( $route['body'] ?? null ) === true ? $route['body'] : '';

			if( str_contains( $body, '[vcard' ) === true )
				return true;

			if( preg_match( '#\[template (/templates/[A-Za-z0-9._-]+)#', $body, $hit ) !== 1 )
				return false;

			$template = \Nino\Filesystem::getFileContent( $appData, $hit[1]. '.tpl', '' );

			return is_string( $template ) === true && str_contains( $template, '[vcard' ) === true;
		}

		/**
		 *	Where the card is, in the words of the panel: 'start' (a route for / draws it), 'page' (a route for
		 *	its own address draws it), 'detached' (a route names the card's Element-URI and draws something else),
		 *	'none'. And what else holds /: the Element-URI of the page that does
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array											[ 'state', 'path' (the address of the route, '' for none), 'taken' (the Element-URI of the other start page) ]
		 */
		public static function placement( array &$appData ): array {

			$routes	= (array) ( $appData['/nino/http/routes'] ?? [] );
			$home		= is_array( $routes['GET://'] ?? null ) === true ? $routes['GET://'] : null;
			$uri		= $home !== null ? (string) ( $home['uri'] ?? '/' ) : '';
			$taken	= $home !== null && $uri !== self::PAGE ? $uri : '';

			if( $home !== null && $uri === self::PAGE )
				return [ 'state' => self::shows( $appData, $home ) === true ? 'start' : 'detached', 'path' => '/', 'taken' => '' ];

			foreach( $routes as $key => $route )
				if( is_array( $route ) === true && str_starts_with( (string) $key, 'GET:/' ) === true && ( $route['uri'] ?? '' ) === self::PAGE )
					return [ 'state' => self::shows( $appData, $route ) === true ? 'page' : 'detached', 'path' => substr( (string) $key, 5 ), 'taken' => $taken ];

			return [ 'state' => 'none', 'path' => '', 'taken' => $taken ];
		}

		/**
		 *	A free address for the page that holds / now: the first of /home, /start, the page's own
		 *	Element-URI and a few more that no route answers, as itself or as a subtree, that is no
		 *	address a page cannot have and is made of lower case words
		 *
		 *	@param		array			$routes				Every route there is, the project's and the modules'
		 *	@param		string		$uri					The Element-URI of the page that moves
		 *
		 *	@return 	string|null								The address, null where none is free
		 */
		public static function freePath( array $routes, string $uri ): ?string {

			$candidates = [ '/home', '/start', $uri, '/startseite', '/index', '/start-page', '/start-2', '/start-3' ];

			foreach( $candidates as $path )
				if( preg_match( '#^/[a-z0-9]+(?:-[a-z0-9]+)*$#', $path ) === 1 && \Nino\Http::isReservedUri( $path ) === false
					&& isset( $routes['GET:/'. $path] ) === false && isset( $routes['GET:/'. $path. '/*'] ) === false )
					return $path;

			return null;
		}

		/**
		 *	The route table after the card takes /: the page that holds it moves, whole - its Element-URI,
		 *	its body, its menus, its language - to a free address, the card's route is the one for /, and no
		 *	route that draws the card is left at another address. Only a route that draws the card goes to /:
		 *	a route that carries the card's name and another template stays as it is, and the route of the
		 *	unit takes its place. The route for / serves every language, so it has no language of its own.
		 *	Pure: what it answers is written by the caller in one write, so that / answers at every moment.
		 *	Nothing but the route for / is touched of the project
		 *
		 *	@param		array			$stored				The routes config.php holds
		 *	@param		array			$live					The routes of the request, the modules' included: an address one of them answers is not free
		 *	@param		callable|null	$shows			( array $route ): bool - whether a route draws the card; every route does where none is given
		 *
		 *	@return 	array											[ 'routes' => the new table, 'from' => the Element-URI that moved, 'to' => its new address ('' where / was free), 'locale' => the language of the route that moved ('' for every language), 'gone' => the addresses of the card that stop answering ],
		 *																	[ 'already' => true ] where the card holds /, or [ 'error' => 'taken' ] where there is no address to move to,
		 *																	[ 'error' => 'template' ] where the route of the unit would not draw the card either
		 */
		public static function takeStart( array $stored, array $live, ?callable $shows = null ): array {

			$shows ??= static fn( array $route ): bool => true;
			$home = $stored['GET://'] ?? null;

			if( is_array( $home ) === true && ( $home['uri'] ?? '' ) === self::PAGE )
				return [ 'already' => true ];

			// A route a module answers / with and the config does not hold is not the project's to move
			if( $home === null && isset( $live['GET://'] ) === true )
				return [ 'error' => 'taken' ];

			$drawing = [];
			foreach( $stored as $key => $route )
				if( is_array( $route ) === true && str_starts_with( (string) $key, 'GET:/' ) === true && ( $route['uri'] ?? '' ) === self::PAGE && $shows( $route ) === true )
					$drawing[] = $key;

			$card = $drawing !== [] ? $stored[ $drawing[0] ] : [ 'uri' => self::PAGE, 'body' => '[template /templates/page-vcard]' ];
			unset( $card['locale'] );

			if( $shows( $card ) === false )
				return [ 'error' => 'template' ];

			$from		= '';
			$to			= '';
			$locale	= '';

			if( is_array( $home ) === true ) {

				$from		= (string) ( $home['uri'] ?? '' );
				$locale	= is_string( $home['locale'] ?? null ) === true ? $home['locale'] : '';
				$path		= self::freePath( $stored + $live, $from );

				if( $path === null )
					return [ 'error' => 'taken' ];

				$to = $path;
				$stored['GET:/'. $to] = $home;
			}

			foreach( $drawing as $key )
				unset( $stored[$key] );

			$stored['GET://'] = $card;

			return [ 'routes' => $stored, 'from' => $from, 'to' => $to, 'locale' => $locale, 'gone' => array_map( static fn( string $key ): string => substr( $key, 5 ), $drawing ) ];
		}

		/**
		 *	The text files that hold the address of a page that moved, as a fill the setup wrote: the file of
		 *	each language the route served, and the one for every language where it served all of them or
		 *	the native one
		 *
		 *	@param		string		$locale				The language of the route that moved, '' for every language
		 *	@param		string		$native				The native language of the website
		 *	@param		array			$locales			The languages of the website
		 *
		 *	@return 	array											Paths below the project, '/text/global.php' and '/text/<locale>.php'
		 */
		public static function addressFiles( string $locale, string $native, array $locales ): array {

			$files = [];

			if( $locale === '' || $locale === $native )
				$files[] = '/text/global.php';

			foreach( $locales as $each )
				if( $locale === '' || $locale === $each )
					$files[] = '/text/'. $each. '.php';

			return $files;
		}

		/**
		 *	The response of /vcard.vcf: the file itself, as a download. It runs after the
		 *	page engine (which saw an empty body) and writes the body, so nothing renders
		 *	the file again - a bracket in a detail stays a bracket
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		array			&$request			(reference) Current server request
		 *
		 *	@return 	void
		 */
		public static function callbackFile( array &$appData, array &$request ): void {

			$file = self::file( $appData );

			if( $file === '' ) {
				$request['/nino/http/response']['statusCode']	= 404;
				$request['/nino/http/response']['body']				= '';
				return;
			}

			$request['/nino/http/response']['header']['Content-Disposition']	= 'attachment; filename="'. self::filename( self::card( $appData ) ). '"';
			$request['/nino/http/response']['header']['Cache-Control']				= 'no-cache';
			$request['/nino/http/response']['body']														= $file;
		}
	}
}
