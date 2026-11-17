<?php
declare(strict_types=1);
/**
 *	Nino										A compact filesystembased php framework
 *	Modules\Themes\Repository	see _nino/Nino/Modules/Modules.php for the
 *														package-level docblock
 *
 *	@package								Dape/Nino
 *	@author									David Perchermeier <mail@dape.io>
 *	@link										https://github.com/dapeio/nino
 */
namespace Nino\Modules\Themes {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Modules\Themes		The repository of fonts: the signed list of webfonts.getnino.dev - or of one of your own, see the two settings of the
	 *										feature - fetched when the panel asks for it and never on its own, read and kept for the install that follows
	 *
	 *										The list is a webfonts.json (format 1) beside a detached signature over its exact bytes - ECDSA over SHA-256, the
	 *										scheme \Nino\Catalogue::verify() checks - and every entry names the archive of a family by its sha256 and its size:
	 *
	 *										{ "format": 1, "generated": "...", "families": [ {
	 *											"slug": "inter", "family": "Inter", "category": "sans", "version": "4.1", "license": "OFL-1.1",
	 *											"source": "https://...", "weights": [ 400, 700 ], "styles": [ "normal", "italic" ],
	 *											"files": [ { "weight": 400, "style": "normal", "file": "400.woff2" }, ... ],
	 *											"archive": "https://.../inter-4.1.tar.gz", "sha256": "...", "size": 123456 } ] }
	 *
	 *										The first nine fields, from "slug" to "files", are the family's manifest (see manifest()): the archive carries them
	 *										as its font.json. A weight is a number from 100 to 900, or "100 900" for a file that carries the weight axis. What
	 *										this reader does not use - a preview, a day of release - is not looked at, and a key it does not know is ignored.
	 *
	 *										Trust is the signature: the list is believed after it holds over the bytes that were fetched, and for no other
	 *										reason. PUBLIC_KEY is the public half of the key of webfonts.getnino.dev, the setting "fontsKey" replaces it for a
	 *										repository of your own, and the setting "fontsUrl" names the list - '' switches the repository off. Only https is
	 *										fetched, a redirect is never followed (\Nino\Fetch does neither), and a host that is no public name - an address
	 *										in place of a name, a name of the local network, a name that is looked up as an internal address - is no host
	 *										(see host()).
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Repository {

		// Where Nino's own fonts are published
		public const string DEFAULT_URL = 'https://webfonts.getnino.dev/webfonts.json';

		// The public half of the key the list is signed with, PEM. A repository of one's own names its own in the setting; where that is empty
		// this one stands, and a list that is not signed with it is not believed - there is no unsigned way in
		public const string PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE6CoCfpg9S1duMrigHnxDBKq5md/U
MewISxkh026g8EmCieoyDZanhJ/ZD326y0DuaDbSTCXc4Ma8CrZTEP813w==
-----END PUBLIC KEY-----
PEM;

		public const int FORMAT = 1;

		// What is read and kept: the list and its signature, an archive, a file of a family, a family unpacked, the faces of a family (the brand
		// reads twelve), the families of a list. The sizes are those of a family of woff2 files with room to spare, measured against what a host
		// of 128 MB of memory holds at once: the archive, its tar and the files are in memory together
		public const int MAX_LIST_BYTES = 1024 * 1024;
		public const int MAX_SIGNATURE_BYTES = 4096;
		public const int MAX_ARCHIVE_BYTES = 10 * 1024 * 1024;
		public const int MAX_FILE_BYTES = 4 * 1024 * 1024;
		public const int MAX_FAMILY_BYTES = 16 * 1024 * 1024;
		public const int MAX_FACES = 12;
		public const int MAX_FAMILIES = 2000;

		// Seconds a request may take: the list, and an archive
		public const int TIMEOUT_LIST = 15;
		public const int TIMEOUT_ARCHIVE = 60;

		// How long what was fetched may be installed from, in seconds: a list older than a day is fetched again before a family is installed from it
		public const int FRESH = 86400;

		// Where the list and its signature are kept between requests: below a folder whose name begins with a dot, which a backup does not
		// carry. Overwritten whole on every fetch and never merged
		public const string CACHE = '/data/theme/.fonts/list.json';

		// What a refusal of the list is about; the panel says each in its own words
		public const array REFUSALS = [ 'off', 'internal', 'unsupported', 'badkey', 'unreachable', 'signature', 'parse' ];

		// A slug is the name of the folder a family lives in, and lower-case ascii
		public const string SLUG = '/^(?=.{1,64}$)[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		// The fields of a family's manifest, in the order they are written
		public const array MANIFEST = [ 'slug', 'family', 'category', 'version', 'license', 'source', 'weights', 'styles', 'files' ];

		public const array CATEGORIES = [ 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ];
		public const array LICENSES = [ 'OFL-1.1', 'Apache-2.0', 'UFL-1.0' ];
		public const array STYLES = [ 'normal', 'italic' ];

		// What the brand takes for the name of a family - it stands between quotes in a stylesheet - and for the name of its folder (see
		// Brand::fonts()). A family of the list that the two do not let through is listed and not installed
		public const string BRAND_FAMILY = '/^[A-Za-z0-9][A-Za-z0-9 _-]{0,59}$/D';
		public const int BRAND_SLUG_LENGTH = 60;

		// The names a host may not end in: they name a machine of a local network and no public one
		private const array LOCAL_ENDINGS = [ 'localhost', 'local', 'localdomain', 'internal', 'intranet', 'lan', 'home', 'corp', 'arpa' ];

		/**
		 *	The address of the list, '' where the repository is switched off: the setting is empty or no https address
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function url( array &$appData ): string {

			$url = \Nino\Features::setting( $appData, 'themes', 'fontsUrl', self::DEFAULT_URL );

			return is_string( $url ) === true && \Nino\Fetch::isHttpsUrl( trim( $url ) ) === true ? trim( $url ) : '';
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string										The PEM key the list has to be signed with: the setting, else PUBLIC_KEY
		 */
		public static function key( array &$appData ): string {

			$key = \Nino\Features::setting( $appData, 'themes', 'fontsKey', '' );
			$key = is_string( $key ) === true ? trim( $key ) : '';

			return $key !== '' ? $key : self::PUBLIC_KEY;
		}

		/**
		 *	Whether the repository can be asked: the first of REFUSALS that stands in the way, or 'ready'
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string
		 */
		public static function state( array &$appData ): string {

			$url = self::url( $appData );

			if( $url === '' )
				return 'off';

			// By its spelling alone: a name is looked up where a request follows (see fetch()), and showing the panel asks nobody
			if( self::host( $appData, $url, false ) === false )
				return 'internal';

			// The signature is checked with openssl and the archive is read with zlib: where one is not there, the half that installs is off
			if( function_exists( 'openssl_verify' ) === false || function_exists( 'openssl_pkey_get_public' ) === false || Archive::supported() === false )
				return 'unsupported';

			// A key is a block of PEM, as the setting is pasted: openssl would read the path of a file of the server as well, and no setting names one
			$key = self::key( $appData );

			return str_contains( $key, '-----BEGIN PUBLIC KEY-----' ) === false || @openssl_pkey_get_public( $key ) === false ? 'badkey' : 'ready';
		}

		/**
		 *	Whether an address names a host that may be asked. What is checked: the address is https; its host is a name - an address in place of a
		 *	name, dotted, whole, hexadecimal or an IPv6 one, is none - of two labels or more, with a top level of letters that is no name of a local
		 *	network; and, with the lookup, every address the system gives the name is one of the public internet (see _public()). A name the system
		 *	gives no address is not asked.
		 *
		 *	What is not checked, and what stands against it:
		 *	- the request that follows makes a lookup of its own: a name that answers another address the second time is not caught here;
		 *	- the IPv6 addresses of a name are read with dns_get_record(), and where php has not got it they are not read: a name with a public
		 *	  IPv4 address and an internal IPv6 one passes there;
		 *	- a server that sends its requests through a proxy of its environment (HTTPS_PROXY) lets the proxy do the lookup, and this one says
		 *	  nothing about where the proxy goes.
		 *	Against all three: the repository is asked only where an account that may change the setting named it, a certificate has to hold for
		 *	the name, the list is believed for its signature alone and an archive for the digest the signed list names.
		 *
		 *	A test replaces the lookup by putting a callable under './nino/themes/resolve' - it receives the host and answers the addresses, or
		 *	false. Without the lookup the name is judged by its spelling: that is what the panel is shown by, which asks no name server for being
		 *	opened.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$url
		 *	@param		bool			$lookup				Whether the name is looked up as well
		 *
		 *	@return 	bool
		 */
		public static function host( array &$appData, string $url, bool $lookup = true ): bool {

			if( \Nino\Fetch::isHttpsUrl( $url ) === false )
				return false;

			$host = rtrim( strtolower( (string) parse_url( $url, PHP_URL_HOST ) ), '.' );
			$labels = explode( '.', $host );

			if( $host === '' || strlen( $host ) > 253 || count( $labels ) < 2 || preg_match( '/^[a-z][a-z0-9-]*$/D', end( $labels ) ) !== 1 || in_array( end( $labels ), self::LOCAL_ENDINGS, true ) === true )
				return false;

			foreach( $labels as $label )
				if( preg_match( '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/D', $label ) !== 1 )
					return false;

			if( $lookup === false )
				return true;

			$resolve = $appData['./nino/themes/resolve'] ?? null;
			$addresses = is_callable( $resolve ) === true ? $resolve( $host ) : self::_resolve( $host );

			// No answer is no leave: a name nobody knows now may be an internal one a moment later
			if( is_array( $addresses ) === false || $addresses === [] )
				return false;

			foreach( $addresses as $address )
				if( is_string( $address ) === false || self::_public( $address ) === false )
					return false;

			return true;
		}

		/**
		 *	Fetch, verify and parse the list: two requests - the json and its detached signature - and nothing is believed before the signature
		 *	holds over the exact bytes. Only the panel's button calls it. What was read is kept for cached().
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		&$kind				(reference) What a refusal is about, one of REFUSALS; '' where the list was read
		 *
		 *	@return 	array|string								The families (see parse()), or why not
		 */
		public static function fetch( array &$appData, string &$kind = '' ): array|string {

			$kind = '';
			$state = self::state( $appData );

			if( $state !== 'ready' ) {
				$kind = $state;
				return 'the font repository cannot be asked: '. $state;
			}

			$url = self::url( $appData );

			if( self::host( $appData, $url ) === false ) {
				$kind = 'internal';
				return 'the font list is not at a public host';
			}

			$json = \Nino\Fetch::get( $appData, $url, [ 'maxBytes' => self::MAX_LIST_BYTES, 'timeout' => self::TIMEOUT_LIST ] );

			if( $json['ok'] === false ) {
				$kind = 'unreachable';
				return 'the font list could not be fetched: '. $json['error'];
			}

			$signature = \Nino\Fetch::get( $appData, self::_signatureUrl( $url ), [ 'maxBytes' => self::MAX_SIGNATURE_BYTES, 'timeout' => self::TIMEOUT_LIST ] );

			if( $signature['ok'] === false ) {
				$kind = 'unreachable';
				return 'the font list signature could not be fetched: '. $signature['error'];
			}

			if( \Nino\Catalogue::verify( $json['body'], $signature['body'], self::key( $appData ) ) === false ) {
				$kind = 'signature';
				return 'the font list signature does not verify';
			}

			$list = self::parse( $json['body'] );

			if( is_string( $list ) === true ) {
				$kind = 'parse';
				return $list;
			}

			// A write that fails does not undo a fetch that itself succeeded: the next request has nothing to show, and that is all
			\Nino\Filesystem::putFileContent( $appData, self::CACHE, [ 'fetched' => time(), 'url' => $url, 'list' => $json['body'], 'signature' => $signature['body'] ] );

			return $list['families'];
		}

		/**
		 *	The list as it was last fetched, read from data/ and never fetched. What is kept is the document and its signature, byte for byte, and
		 *	it is believed the way it was when it came: the signature has to hold over the bytes with the key that is set now, and the document
		 *	has to be what format 1 says. So a file another hand wrote is no list, and neither is one that was signed with a key the settings do not
		 *	name any more. null as well where nothing was fetched yet or the list was fetched under another address than the setting names -
		 *	which is also what switching the repository off looks like.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null								{ fetched (unix time), url, families } - the families as parse() answers them
		 */
		public static function cached( array &$appData ): ?array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::CACHE, null );

			if( is_array( $stored ) === false || is_int( $stored['fetched'] ?? null ) === false || is_string( $stored['url'] ?? null ) === false || is_string( $stored['list'] ?? null ) === false
				|| is_string( $stored['signature'] ?? null ) === false || strlen( $stored['list'] ) > self::MAX_LIST_BYTES || strlen( $stored['signature'] ) > self::MAX_SIGNATURE_BYTES
				|| $stored['url'] !== self::url( $appData ) || self::state( $appData ) !== 'ready' )
				return null;

			if( \Nino\Catalogue::verify( $stored['list'], $stored['signature'], self::key( $appData ) ) === false )
				return null;

			$list = self::parse( $stored['list'] );

			return is_array( $list ) === true ? [ 'fetched' => $stored['fetched'], 'url' => $stored['url'], 'families' => $list['families'] ] : null;
		}

		/**
		 *	Whether a list that was fetched at a time is too old to install from: fetched more than FRESH seconds ago - or at a time that is still
		 *	to come, which a clock that was set back or a hand on the file makes, and which would never grow old
		 *
		 *	@param		int				$fetched			Unix time
		 *
		 *	@return 	bool
		 */
		public static function old( int $fetched ): bool {

			$age = time() - $fetched;

			return $age < 0 || $age > self::FRESH;
		}

		/**
		 *	Validate and normalize a list document. What is not what format 1 says is refused as a whole: a list that is half right is not
		 *	offered half way.
		 *
		 *	@param		string		$json
		 *
		 *	@return 	array|string								{ format, generated, families: [ entry, ... ] } or why not
		 */
		public static function parse( string $json ): array|string {

			$document = json_decode( $json, true );

			if( is_array( $document ) === false )
				return 'the font list is not valid json';

			if( ( $document['format'] ?? null ) !== self::FORMAT )
				return 'the font list has format '. json_encode( $document['format'] ?? null ). ', this feature reads format '. self::FORMAT;

			if( is_array( $document['families'] ?? null ) === false || array_is_list( $document['families'] ) === false )
				return 'the font list names no families';

			if( count( $document['families'] ) > self::MAX_FAMILIES )
				return 'the font list names more than '. self::MAX_FAMILIES. ' families';

			$families = [];
			$seen = [];

			foreach( $document['families'] as $index => $entry ) {

				$clean = self::_entry( $entry );

				if( is_string( $clean ) === true )
					return 'font list entry '. $index. ': '. $clean;

				if( isset( $seen[$clean['slug']] ) === true )
					return 'font list entry '. $index. ': the slug "'. $clean['slug']. '" is listed twice';

				$seen[$clean['slug']] = true;
				$families[] = $clean;
			}

			return [ 'format' => self::FORMAT, 'generated' => is_string( $document['generated'] ?? null ) ? $document['generated'] : '', 'families' => $families ];
		}

		/**
		 *	Validate and normalize the manifest of a family: the nine fields an entry of the list begins with, which the archive carries as its
		 *	font.json. Anything else in $data is not looked at.
		 *
		 *	A file is named by what it is - 400.woff2, 700-italic.woff2, 100-900.woff2 for a weight axis - and the name has to be the one its
		 *	weight and its style give it; the weights and the styles are those of the files, once each.
		 *
		 *	@param		mixed			$data					A decoded json object
		 *
		 *	@return 	array|string								The nine fields, normalized, in the order of MANIFEST - or what is wrong with them
		 */
		public static function manifest( mixed $data ): array|string {

			if( is_array( $data ) === false )
				return 'must be an object';

			$slug = $data['slug'] ?? null;

			if( is_string( $slug ) === false || preg_match( self::SLUG, $slug ) !== 1 )
				return '"slug" must be lower-case ascii letters, digits and single hyphens, at most 64 characters';

			$family = $data['family'] ?? null;

			if( is_string( $family ) === false || preg_match( '/^[A-Za-z0-9][A-Za-z0-9 .-]{0,63}$/D', $family ) !== 1 || trim( $family ) !== $family )
				return '"family" must be a name of 1 to 64 letters, digits, spaces, dots and hyphens, beginning with a letter or a digit and with no space at its end';

			$category = $data['category'] ?? null;

			if( in_array( $category, self::CATEGORIES, true ) === false )
				return '"category" must be one of '. implode( ', ', self::CATEGORIES );

			$version = $data['version'] ?? null;

			if( is_string( $version ) === false || preg_match( '/^[A-Za-z0-9.-]{1,32}$/D', $version ) !== 1 )
				return '"version" must be 1 to 32 letters, digits, dots and hyphens';

			$license = $data['license'] ?? null;

			if( in_array( $license, self::LICENSES, true ) === false )
				return '"license" must be one of '. implode( ', ', self::LICENSES );

			$source = $data['source'] ?? null;

			if( is_string( $source ) === false || \Nino\Fetch::isHttpsUrl( $source ) === false )
				return '"source" must be an https url';

			$files = self::_files( $data['files'] ?? null );

			if( is_string( $files ) === true )
				return $files;

			$weights = self::_distinct( $data['weights'] ?? null, array_column( $files, 'weight' ), static fn( mixed $weight ): bool => Brand::weight( $weight ) !== null );

			if( $weights === false )
				return '"weights" must list the weights of "files", once each';

			$styles = self::_distinct( $data['styles'] ?? null, array_column( $files, 'style' ), static fn( mixed $style ): bool => in_array( $style, self::STYLES, true ) );

			if( $styles === false )
				return '"styles" must list the styles of "files", once each';

			return [ 'slug' => $slug, 'family' => $family, 'category' => $category, 'version' => $version, 'license' => $license, 'source' => $source, 'weights' => $weights, 'styles' => $styles, 'files' => $files ];
		}

		/**
		 *	Why an entry of the list is listed and cannot be installed here, '' where it can: its family or its slug is a name the brand does not
		 *	take, or its slug is one of the stacks of the system
		 *
		 *	@param		array			$entry				As parse() answers it
		 *
		 *	@return 	string										'', 'family' or 'system'
		 */
		public static function problem( array $entry ): string {

			if( isset( Brand::SYSTEM[$entry['slug']] ) === true )
				return 'system';

			return preg_match( self::BRAND_FAMILY, $entry['family'] ) === 1 && strlen( $entry['slug'] ) <= self::BRAND_SLUG_LENGTH ? '' : 'family';
		}

		/**
		 *	The address of the signature of a list: '.sig' on the end of the path, not on the end of the url - a list behind a token has its
		 *	signature at 'https://host/x.json.sig?token=abc'
		 *
		 *	@param		string		$url
		 *
		 *	@return 	string
		 */
		private static function _signatureUrl( string $url ): string {

			$pathLength = strcspn( $url, '?#' );

			return substr( $url, 0, $pathLength ). '.sig'. substr( $url, $pathLength );
		}

		/**
		 *	The addresses the system gives a name: the IPv4 ones, and the IPv6 ones where php can ask for them (see host() for where it cannot)
		 *
		 *	@param		string		$host
		 *
		 *	@return 	array|false
		 */
		private static function _resolve( string $host ): array|false {

			$found = @gethostbynamel( $host );
			$found = is_array( $found ) === true ? $found : [];

			if( function_exists( 'dns_get_record' ) === true ) {

				$records = @dns_get_record( $host, DNS_AAAA );

				foreach( is_array( $records ) === true ? $records : [] as $record )
					if( is_string( $record['ipv6'] ?? null ) === true )
						$found[] = $record['ipv6'];
			}

			return $found === [] ? false : $found;
		}

		/**
		 *	Whether an address is one of the public internet: no private, reserved, loopback or link-local range - and not the shared range of a
		 *	carrier's network either, which filter_var() has no flag for. An IPv6 address behind the prefix of a NAT64 gateway (64:ff9b::/96) is
		 *	judged by the IPv4 address it carries. One that is an IPv4 address by mapping (::ffff:a.b.c.d) or by its last four bytes alone
		 *	(::a.b.c.d) is none: filter_var() calls the second kind reserved from php 8.4 on and public before, and no repository lives there
		 *
		 *	@param		string		$address
		 *
		 *	@return 	bool
		 */
		private static function _public( string $address ): bool {

			if( filter_var( $address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false )
				return false;

			$packed = (string) @inet_pton( $address );

			// 100.64.0.0/10
			if( strlen( $packed ) === 4 )
				return ord( $packed[0] ) !== 100 || ( ord( $packed[1] ) & 0xC0 ) !== 64;

			if( strlen( $packed ) !== 16 || str_starts_with( $packed, str_repeat( "\0", 10 ). "\xff\xff" ) === true || str_starts_with( $packed, str_repeat( "\0", 12 ) ) === true )
				return false;

			if( str_starts_with( $packed, "\0\x64\xff\x9b". str_repeat( "\0", 8 ) ) === true )
				return self::_public( (string) inet_ntop( substr( $packed, 12 ) ) );

			return true;
		}

		/**
		 *	Validate and normalize one entry of the list
		 *
		 *	@param		mixed			$entry
		 *
		 *	@return 	array|string								The clean entry, or what is wrong with it
		 */
		private static function _entry( mixed $entry ): array|string {

			if( is_array( $entry ) === false )
				return 'must be an object';

			$manifest = self::manifest( $entry );

			if( is_string( $manifest ) === true )
				return $manifest;

			$archive = $entry['archive'] ?? null;

			if( is_string( $archive ) === false || \Nino\Fetch::isHttpsUrl( $archive ) === false )
				return '"archive" must be an https url';

			$sha256 = strtolower( is_string( $entry['sha256'] ?? null ) === true ? $entry['sha256'] : '' );

			if( preg_match( '/^[a-f0-9]{64}$/D', $sha256 ) !== 1 )
				return '"sha256" must be the hex digest of the archive';

			$size = $entry['size'] ?? null;

			if( is_int( $size ) === false || $size < 1 || $size > self::MAX_ARCHIVE_BYTES )
				return '"size" must be the archive size in bytes, at most '. self::MAX_ARCHIVE_BYTES;

			return $manifest + [ 'archive' => $archive, 'sha256' => $sha256, 'size' => $size ];
		}

		/**
		 *	The font files of a manifest: a list of { weight, style, file }, each one valid, none listed twice, no more than the brand reads
		 *
		 *	@param		mixed			$files
		 *
		 *	@return 	array|string
		 */
		private static function _files( mixed $files ): array|string {

			if( is_array( $files ) === false || $files === [] || array_is_list( $files ) === false )
				return '"files" must be a list of the font files';

			if( count( $files ) > self::MAX_FACES )
				return '"files" lists more than '. self::MAX_FACES. ' files';

			$clean = [];
			$names = [];

			foreach( $files as $file ) {

				if( is_array( $file ) === false )
					return '"files" must be a list of objects';

				$weight = Brand::weight( $file['weight'] ?? null );

				if( $weight === null )
					return 'the "weight" of a file must be a number from 100 to 900, or "100 900" for a weight axis that runs from the lower number to the higher';

				$style = $file['style'] ?? null;

				if( in_array( $style, self::STYLES, true ) === false )
					return 'the "style" of a file must be normal or italic';

				$name = $file['file'] ?? null;

				if( $name !== str_replace( ' ', '-', (string) $weight ). ( $style === 'italic' ? '-italic' : '' ). '.woff2' )
					return 'the "file" of a file must be the name its weight and style give it - 400.woff2, 700-italic.woff2, 100-900.woff2';

				if( isset( $names[$name] ) === true )
					return 'the file "'. $name. '" is listed twice';

				$names[$name] = true;
				$clean[] = [ 'weight' => $weight, 'style' => $style, 'file' => $name ];
			}

			return $clean;
		}

		/**
		 *	Whether a list names exactly the values the files have, once each - and is made of nothing else
		 *
		 *	@param		mixed			$given				What the manifest says
		 *	@param		array 		$derived			What the files say
		 *	@param		callable	$valid				Whether one value of $given can be one at all
		 *
		 *	@return 	array|false								$given as it is, or false
		 */
		private static function _distinct( mixed $given, array $derived, callable $valid ): array|false {

			if( is_array( $given ) === false || array_is_list( $given ) === false || $given === [] )
				return false;

			foreach( $given as $value )
				if( $valid( $value ) === false )
					return false;

			$words = array_map( 'strval', $given );
			$derived = array_unique( array_map( 'strval', $derived ) );

			sort( $words, SORT_STRING );
			sort( $derived, SORT_STRING );

			return $words === $derived ? $given : false;
		}
	}
}
