<?php
declare(strict_types=1);
/**
 *	Nino								A compact filesystembased php framework
 *	Modules\Design\Webfonts	see features/Design/Design.php for the feature's
 *											own docblock
 *
 *	@package						Dape/Nino
 *	@author							David Perchermeier <mail@dape.io>
 *	@link								https://github.com/dapeio/nino
 */
namespace Nino\Modules\Design {

	/**
	 *	Nino							A compact filesystembased php framework
	 *	Webfonts					The typefaces a project can install: the signed list of
	 *										webfonts.getnino.dev (or of a repository of your own, see
	 *										the feature's two settings), fetched when the panel asks
	 *										for it and never on its own, and the archive of one family,
	 *										installed whole into assets/fonts/<slug>/ below the
	 *										project's public root, where Fonts lists it.
	 *
	 *										The catalogue of features, one size smaller, and the same
	 *										rules. The list is a webfonts.json (format 1) beside a
	 *										detached signature over its exact bytes - ECDSA over
	 *										SHA-256, the scheme \Nino\Catalogue::verify() checks - and
	 *										every entry names an archive by its sha256 and its size:
	 *
	 *										{ "format": 1, "generated": "...", "families": [ {
	 *											"slug": "inter", "family": "Inter", "category": "sans",
	 *											"version": "4.1", "license": "OFL-1.1", "source": "https://...",
	 *											"weights": [ 400, 700 ], "styles": [ "normal", "italic" ],
	 *											"files": [ { "weight": 400, "style": "normal", "file": "400.woff2" }, ... ],
	 *											"archive": "https://.../inter-4.1.tar.gz",
	 *											"sha256": "...", "size": 123456,
	 *											"preview": "https://.../inter-4.1-preview.woff2",
	 *											"released": "2026-10-12"
	 *										} ] }
	 *
	 *										The first nine fields, from "slug" to "files", are the
	 *										family's manifest (see manifest()): the archive carries
	 *										them as its font.json, a reader has one shape. A weight is
	 *										a number from 100 to 900, or "100 900" for a file that
	 *										carries the weight axis. Keys this reader does not know
	 *										are ignored, so that a feature written before a field
	 *										reads a list that has it; anything a reader would have to
	 *										understand is what format 2 is for.
	 *
	 *										Trust is the signature. PUBLIC_KEY is the public half of
	 *										the key the list is signed with, and the setting
	 *										'webfontsKey' replaces it for a repository of your own.
	 *										It is empty until the owner has made the key pair of
	 *										webfonts.getnino.dev (README of nino-webfonts, "The signing
	 *										key") and pasted the public half in: as long as it and the
	 *										setting are empty, fetch() refuses, and says so. The
	 *										setting 'webfontsUrl' names the list; '' switches the
	 *										repository off. Every url the list names is fetched over
	 *										https and nothing else.
	 *
	 *										A family is installed whole: the archive is downloaded,
	 *										checked against the size and the sha256 the signed list
	 *										names, unpacked into a staging directory below data/ with
	 *										the kernel's rules for an archive (see _unpack()), the
	 *										directory that came out is looked at - its font.json is
	 *										the entry's manifest, every file is a woff2 file, the
	 *										licence is there and nothing else is - and only then moved
	 *										into place, the old directory of an update removed first.
	 *										A family a role uses may be updated, and not removed.
	 *
	 *	@package					Dape/Nino
	 *	@author						David Perchermeier <mail@dape.io>
	 *	@link							https://github.com/dapeio/nino
	 */
	class Webfonts {

		// Where Nino's own typefaces are published. '' under the setting 'webfontsUrl'
		// switches the repository off altogether
		public const string DEFAULT_URL = 'https://webfonts.getnino.dev/webfonts.json';

		// The public half of the key the list is signed with, PEM. Empty until the owner has made
		// the key pair of webfonts.getnino.dev: paste the public half here, or name it in the
		// setting 'webfontsKey' for a repository of your own. An empty key refuses the list - there
		// is no unsigned fallback
		public const string PUBLIC_KEY = '';

		public const int FORMAT = 1;

		// What \Nino\Catalogue holds a catalogue and an archive to
		public const int MAX_LIST_BYTES			= 1024 * 1024;
		public const int MAX_ARCHIVE_BYTES	= 20 * 1024 * 1024;
		public const int MAX_UNPACKED_BYTES	= 50 * 1024 * 1024;
		public const int MAX_ENTRIES				= 5000;

		// Where a fetched list is kept between requests - see fetch() and cached(). Overwritten
		// whole on every fetch and never merged; a project deletes it like any other cache without
		// losing anything
		public const string CACHE = '/data/design-webfonts.php';

		// Archives wait here, below the private data directory, until they are verified - never
		// below the public root
		public const string STAGING = '/data/.fonts';

		// What a refusal of the list is about, for the screen to say in the words of the workbench - the
		// reason itself is English and the server's, and stays as what lies behind the sentence: the
		// repository is switched off, there is no key to check it with, it could not be reached, its
		// signature does not hold, or what it says is no list this reads
		public const array REFUSALS = [ 'off', 'nokey', 'unreachable', 'signature', 'parse' ];

		// A slug is the name of the directory a family lives in, and lower-case ascii
		public const string SLUG = '/^(?=.{1,64}$)[a-z0-9]+(?:-[a-z0-9]+)*$/D';

		// The fields of a family's manifest, in the order they are written
		public const array MANIFEST = [ 'slug', 'family', 'category', 'version', 'license', 'source', 'weights', 'styles', 'files' ];

		// The grouping of Google Fonts, the three licences that allow redistribution and
		// self-hosting, and the two styles a file can have
		public const array CATEGORIES	= [ 'sans', 'serif', 'display', 'handwriting', 'mono', 'other' ];
		public const array LICENSES		= [ 'OFL-1.1', 'Apache-2.0', 'UFL-1.0' ];
		public const array STYLES			= [ 'normal', 'italic' ];

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The url of the list, '' when the repository is switched off (or the url is none that can be fetched)
		 */
		public static function url( array &$appData ): string {

			$url = \Nino\Features::setting( $appData, 'design', 'webfontsUrl', self::DEFAULT_URL );

			return is_string( $url ) === true && \Nino\Fetch::isHttpsUrl( $url ) === true ? $url : '';
		}

		/**
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	string									The PEM public key the list has to be signed with - the setting 'webfontsKey', else PUBLIC_KEY; '' when there is none
		 */
		public static function key( array &$appData ): string {

			$key = \Nino\Features::setting( $appData, 'design', 'webfontsKey', '' );
			$key = is_string( $key ) === true ? trim( $key ) : '';

			return $key !== '' ? $key : self::PUBLIC_KEY;
		}

		/**
		 *	Fetch, verify and parse the list. Two requests - the json and its
		 *	detached signature - and nothing is believed before the signature holds
		 *	over the exact bytes. Only the panel's button calls it: nothing here
		 *	fetches on its own. What it fetched is kept for cached().
		 *
		 *	A refusal says what it is about as well as why: $kind is one of REFUSALS,
		 *	so that the screen tells it in its own language and keeps the reason, which
		 *	is English, for whoever has to find out what is wrong.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		&$kind				(reference) What a refusal is about, one of REFUSALS; '' where the list was read
		 *
		 *	@return 	array|string						The families (see parse()), or why not
		 */
		public static function fetch( array &$appData, string &$kind = '' ): array|string {

			$kind = '';
			$url 	= self::url( $appData );

			if( $url === '' ) {
				$kind = 'off';
				return 'the typeface repository is switched off';
			}

			$key = self::key( $appData );

			if( $key === '' ) {
				$kind = 'nokey';
				return 'no public key for the repository - set it in the feature\'s settings or in Webfonts::PUBLIC_KEY';
			}

			$json = \Nino\Fetch::get( $appData, $url, [ 'maxBytes' => self::MAX_LIST_BYTES ] );

			if( $json['ok'] === false ) {
				$kind = 'unreachable';
				return 'the typeface list could not be fetched: '. $json['error'];
			}

			$signature = \Nino\Fetch::get( $appData, self::_signatureUrl( $url ), [ 'maxBytes' => 4096 ] );

			if( $signature['ok'] === false ) {
				$kind = 'unreachable';
				return 'the typeface list signature could not be fetched: '. $signature['error'];
			}

			if( \Nino\Catalogue::verify( $json['body'], $signature['body'], $key ) === false ) {
				$kind = 'signature';
				return 'the typeface list signature does not verify';
			}

			$list = self::parse( $json['body'] );

			if( is_string( $list ) === true ) {
				$kind = 'parse';
				return $list;
			}

			// Best effort: a write that fails (a read-only data/) does not undo a fetch that
			// itself succeeded, it only leaves nothing to show on the next request
			\Nino\Filesystem::putFileContent( $appData, self::CACHE, [
				'fetched'		=> time(),
				'url'				=> $url,
				'families'	=> $list['families'],
			] );

			return $list['families'];
		}

		/**
		 *	The list as it was last fetched, read from data/ - never fetched, so
		 *	this is safe to call on every request the panel answers. null when there
		 *	is nothing to show: no successful fetch yet, a file that does not hold
		 *	what fetch() writes, or one fetched under a url that is not the
		 *	configured one any more - which is also what switching the repository
		 *	off looks like, since '' never matches a url a fetch was made under
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array|null							{ fetched (unix time), url, families } - the families as parse() answers them; null for nothing cached
		 */
		public static function cached( array &$appData ): ?array {

			$stored = \Nino\Filesystem::getFileContent( $appData, self::CACHE, null );

			if( is_array( $stored ) === false || is_int( $stored['fetched'] ?? null ) === false || is_string( $stored['url'] ?? null ) === false || is_array( $stored['families'] ?? null ) === false )
				return null;

			if( $stored['url'] !== self::url( $appData ) )
				return null;

			// Held to the same rules as the document it came from: install() builds paths and
			// requests out of these entries, and a file is only as good as the last hand on it
			$families = [];

			foreach( $stored['families'] as $entry ) {

				$clean = self::_entry( $entry );

				if( is_string( $clean ) === true )
					return null;

				$families[] = $clean;
			}

			return [ 'fetched' => $stored['fetched'], 'url' => $stored['url'], 'families' => $families ];
		}

		/**
		 *	Validate and normalize a list document. Anything that is not what format
		 *	1 says is refused as a whole: a list that is half right is not offered
		 *	half way.
		 *
		 *	@param		string		$json
		 *
		 *	@return 	array|string						{ format, generated, families: [ entry, ... ] } or why not
		 */
		public static function parse( string $json ): array|string {

			$document = json_decode( $json, true );

			if( is_array( $document ) === false )
				return 'the typeface list is not valid json';

			if( ( $document['format'] ?? null ) !== self::FORMAT )
				return 'the typeface list has format '. json_encode( $document['format'] ?? null ). ', this feature reads format '. self::FORMAT;

			if( is_array( $document['families'] ?? null ) === false || array_is_list( $document['families'] ) === false )
				return 'the typeface list names no families';

			$families	= [];
			$seen			= [];

			foreach( $document['families'] as $index => $entry ) {

				$clean = self::_entry( $entry );

				if( is_string( $clean ) === true )
					return 'typeface list entry '. $index. ': '. $clean;

				if( isset( $seen[ $clean['slug'] ] ) === true )
					return 'typeface list entry '. $index. ': the slug "'. $clean['slug']. '" is listed twice';

				$seen[ $clean['slug'] ] = true;
				$families[] = $clean;
			}

			return [
				'format'		=> self::FORMAT,
				'generated'	=> is_string( $document['generated'] ?? null ) ? $document['generated'] : '',
				'families'	=> $families,
			];
		}

		/**
		 *	Validate and normalize the manifest of a family: the nine fields an entry of
		 *	the list begins with, which the archive carries as its font.json and an
		 *	installed directory keeps. Anything else in $data is not looked at.
		 *
		 *	The name of a family reaches a stylesheet between quotes, so it is held to
		 *	letters, digits, spaces, dots and hyphens, the first of them a letter or a digit
		 *	as the build of the repository holds it. A file is named by what it is -
		 *	400.woff2, 700-italic.woff2, 100-900.woff2 for a weight axis - and the name
		 *	has to be the one its weight and its style give it; the weights and the
		 *	styles are those of the files, once each.
		 *
		 *	@param		mixed			$data					A decoded json object
		 *
		 *	@return 	array|string						The nine fields, normalized, in the order of MANIFEST - or what is wrong with them
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

			// Once each, and exactly what the files are
			$weights = self::_distinct( $data['weights'] ?? null, array_column( $files, 'weight' ), static fn( mixed $weight ): bool => self::_weight( $weight ) !== null );

			if( $weights === false )
				return '"weights" must list the weights of "files", once each';

			$styles = self::_distinct( $data['styles'] ?? null, array_column( $files, 'style' ), static fn( mixed $style ): bool => in_array( $style, self::STYLES, true ) );

			if( $styles === false )
				return '"styles" must list the styles of "files", once each';

			return [
				'slug'			=> $slug,
				'family'		=> $family,
				'category'	=> $category,
				'version'		=> $version,
				'license'		=> $license,
				'source'		=> $source,
				'weights'		=> $weights,
				'styles'		=> $styles,
				'files'			=> $files,
			];
		}

		/**
		 *	Install one family of the cached list, whole: download its archive, check
		 *	size and sha256 against the entry, unpack it into a staging directory
		 *	below data/ with every entry looked at, make sure what came out is the
		 *	family the signed list promised, and move it into place - the directory
		 *	an earlier version left removed first, and put back if the move fails.
		 *	The staging directory is gone on every way out.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					A slug of the cached list
		 *
		 *	@return 	true|string							True, or why not
		 */
		public static function install( array &$appData, string $slug ): true|string {

			if( preg_match( self::SLUG, $slug ) !== 1 )
				return 'that is no slug of a typeface';

			$entry = null;

			foreach( self::cached( $appData )['families'] ?? [] as $candidate )
				if( $candidate['slug'] === $slug )
					$entry = $candidate;

			if( $entry === null )
				return 'the typeface "'. $slug. '" is not in the list - load the list first';

			if( isset( Fonts::builtin()[$slug] ) === true )
				return 'the slug "'. $slug. '" is one of the families the feature brings';

			$archive = \Nino\Fetch::get( $appData, $entry['archive'], [ 'maxBytes' => $entry['size'] + 1, 'timeout' => 60 ] );

			if( $archive['ok'] === false )
				return 'the archive could not be fetched: '. $archive['error'];

			if( strlen( $archive['body'] ) !== $entry['size'] || hash( 'sha256', $archive['body'] ) !== $entry['sha256'] )
				return 'the archive does not match what the list promised';

			\Nino\Filesystem::forceDir( $appData, self::STAGING );
			$staging = \Nino\Filesystem::path( $appData, self::STAGING ). '/'. $slug. '-'. bin2hex( random_bytes( 6 ) );

			if( @mkdir( $staging, 0755, true ) === false )
				return 'could not create the staging directory';

			try {
				return self::_place( $appData, $archive['body'], $entry, $staging );
			}
			// However that came back. A staging directory left standing is a copy of the archive
			// below data/ that nothing else ever removes, and the name carries random_bytes(): one
			// install is never the staging directory of another, whose files it would remove
			finally {
				\Nino\Filesystem::removeDir( $staging );
			}
		}

		/**
		 *	Remove an installed family - unless a role of the saved setup uses it, or the
		 *	directory is not one a family lives in. A directory that is none (see
		 *	Fonts::broken()) can be removed all the same: it uses nothing.
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug					The directory's name
		 *
		 *	@return 	true|string							True, or why not
		 */
		public static function remove( array &$appData, string $slug ): true|string {

			if( preg_match( self::SLUG, $slug ) !== 1 )
				return 'that is no slug of a typeface';

			$dir = Fonts::dir( $appData ). '/'. $slug;

			if( is_dir( $dir ) === false || is_link( $dir ) === true )
				return 'the typeface "'. $slug. '" is not installed';

			// A directory that is no family (see Fonts::broken()) is used by nothing - whatever its name
			// is, it is not the family of that name a role may have
			$roles = isset( Fonts::installed( $appData )[$slug] ) === true ? self::uses( $appData, $slug ) : [];

			if( $roles !== [] )
				return 'used by '. implode( ', ', $roles );

			\Nino\Filesystem::removeDir( $dir );

			return file_exists( $dir ) === false ? true : 'could not remove '. ltrim( Fonts::DIR, '/' ). '/'. $slug;
		}

		/**
		 *	The roles that have a family as theirs, by the setup that is saved
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$slug
		 *
		 *	@return 	array										Roles, in the order of Fonts::ROLES; [] for a family nothing uses
		 */
		public static function uses( array &$appData, string $slug ): array {

			return self::usage( $appData )[$slug] ?? [];
		}

		/**
		 *	Which family each role has, by the setup that is saved - read the way the
		 *	panel reads it, so a family that is none any more (a directory somebody
		 *	broke) is the default of its role already and is used by nothing
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *
		 *	@return 	array										slug => the roles that have it, in the order of Fonts::ROLES; the empty family, which is no slug, is left out
		 */
		public static function usage( array &$appData ): array {

			$setup 	= Setup::read( $appData, \Nino\Modules\Design::libraryDir() );
			$usage 	= [];

			foreach( Fonts::ROLES as $role ) {

				$slug = (string) ( $setup['fonts'][$role]['family'] ?? '' );

				if( $slug !== '' )
					$usage[$slug][] = $role;
			}

			return $usage;
		}

		/**
		 *	The address of the signature of a list: '.sig' on the end of the path,
		 *	not on the end of the url - a list behind a token, 'https://host/x.json?token=abc',
		 *	has its signature at 'https://host/x.json.sig?token=abc'
		 *
		 *	@param		string		$url					The list's own url
		 *
		 *	@return 	string
		 */
		private static function _signatureUrl( string $url ): string {

			$pathLength = strcspn( $url, '?#' );

			return substr( $url, 0, $pathLength ). '.sig'. substr( $url, $pathLength );
		}

		/**
		 *	Validate and normalize one entry of the list
		 *
		 *	@param		mixed			$entry
		 *
		 *	@return 	array|string						The clean entry, or what is wrong with it
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

			$preview = $entry['preview'] ?? null;

			if( is_string( $preview ) === false || \Nino\Fetch::isHttpsUrl( $preview ) === false )
				return '"preview" must be an https url';

			$released = $entry['released'] ?? null;

			if( is_string( $released ) === false || preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $released, $day ) !== 1 || checkdate( (int) $day[2], (int) $day[3], (int) $day[1] ) === false )
				return '"released" must be the day the archive was published, YYYY-MM-DD';

			return $manifest + [
				'archive'		=> $archive,
				'sha256'		=> $sha256,
				'size'			=> $size,
				'preview'		=> $preview,
				'released'	=> $released,
			];
		}

		/**
		 *	The font files of a manifest: a list of { weight, style, file }, each one
		 *	valid, none listed twice
		 *
		 *	@param		mixed			$files
		 *
		 *	@return 	array|string
		 */
		private static function _files( mixed $files ): array|string {

			if( is_array( $files ) === false || $files === [] || array_is_list( $files ) === false )
				return '"files" must be a list of the font files';

			$clean = [];
			$names = [];

			foreach( $files as $file ) {

				if( is_array( $file ) === false )
					return '"files" must be a list of objects';

				$weight = self::_weight( $file['weight'] ?? null );

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
		 *	@param		mixed			$weight				400, or '100 900' for a weight axis
		 *
		 *	@return 	int|string|null					The weight as it is kept, null where it is none
		 */
		private static function _weight( mixed $weight ): int|string|null {

			if( is_int( $weight ) === true )
				return $weight >= 100 && $weight <= 900 ? $weight : null;

			if( is_string( $weight ) === true && preg_match( '/^([1-9]\d*) ([1-9]\d*)$/D', $weight, $axis ) === 1 )
				return (int) $axis[1] >= 100 && (int) $axis[1] < (int) $axis[2] && (int) $axis[2] <= 900 ? (int) $axis[1]. ' '. (int) $axis[2] : null;

			return null;
		}

		/**
		 *	Whether a list names exactly the values the files have, once each - and is
		 *	made of nothing else
		 *
		 *	@param		mixed			$given				What the manifest says
		 *	@param		array 		$derived			What the files say
		 *	@param		callable	$valid				Whether one value of $given can be one at all
		 *
		 *	@return 	array|false							$given as it is, or false
		 */
		private static function _distinct( mixed $given, array $derived, callable $valid ): array|false {

			if( is_array( $given ) === false || array_is_list( $given ) === false || $given === [] )
				return false;

			foreach( $given as $value )
				if( $valid( $value ) === false )
					return false;

			$words 		= array_map( 'strval', $given );
			$derived 	= array_unique( array_map( 'strval', $derived ) );

			sort( $words, SORT_STRING );
			sort( $derived, SORT_STRING );

			return $words === $derived ? $given : false;
		}

		/**
		 *	Look at what came out of the archive, and put it in place
		 *
		 *	@param		array 		&$appData			(reference) Array with current app data
		 *	@param		string		$bytes				The verified archive
		 *	@param		array 		$entry				Its entry of the list
		 *	@param		string		$staging			An empty directory of its own
		 *
		 *	@return 	true|string
		 */
		private static function _place( array &$appData, string $bytes, array $entry, string $staging ): true|string {

			// A name of its own for every install: php keeps an archive it opened for as long as the process
			// lives, by its path, and the next one of this slug that is written to the same path would be
			// read as the first. The extension is the whole of the name PharData looks at
			$archivePath = $staging. '/archive-'. bin2hex( random_bytes( 6 ) ). '.tar.gz';

			if( file_put_contents( $archivePath, $bytes ) !== strlen( $bytes ) )
				return 'could not write the archive to the staging directory';

			$unpacked = self::_unpack( $archivePath, $entry['slug'], $staging. '/unpacked' );

			if( $unpacked !== true )
				return $unpacked;

			$source = $staging. '/unpacked/'. $entry['slug'];
			$looked = self::_look( $source, $entry );

			if( $looked !== true )
				return $looked;

			$target		= Fonts::dir( $appData ). '/'. $entry['slug'];
			$previous	= $staging. '/previous';

			if( is_dir( dirname( $target ) ) === false && @mkdir( dirname( $target ), 0755, true ) === false && is_dir( dirname( $target ) ) === false )
				return 'could not create '. ltrim( Fonts::DIR, '/' );

			// An update replaces the whole family: the directory that is there goes first, to
			// the staging directory, where it waits for the move to have worked
			if( is_link( $target ) === true )
				@unlink( $target );

			if( file_exists( $target ) === true && @rename( $target, $previous ) === false )
				return 'could not move the installed typeface aside';

			if( @rename( $source, $target ) === false ) {

				if( file_exists( $previous ) === true )
					@rename( $previous, $target );

				return 'could not move the typeface into place';
			}

			return true;
		}

		/**
		 *	Unpack a .tar.gz after looking at every entry: one directory named after the
		 *	family, nothing outside it, nothing that is not a plain file or directory,
		 *	and bounded in count and size. Only then is the archive extracted, into the
		 *	given directory - the rules of \Nino\Catalogue for an archive of a feature,
		 *	which are private to it.
		 *
		 *	@param		string		$archivePath
		 *	@param		string		$directory		The one top-level directory the archive may hold
		 *	@param		string		$into
		 *
		 *	@return 	true|string
		 */
		private static function _unpack( string $archivePath, string $directory, string $into ): true|string {

			try {
				$phar = new \PharData( $archivePath );

				// The archive's own path, not the one it was opened by: PharData names every
				// entry by the canonical path, symlinks resolved, so a private root reached
				// through one (a hosting home directory, a bind mount) put the cut anywhere
				// but at the entry
				$prefix		= 'phar://'. $phar->getPath(). '/';
				$entries	= 0;
				$bytes		= 0;

				foreach( new \RecursiveIteratorIterator( $phar, \RecursiveIteratorIterator::SELF_FIRST ) as $file ) {

					$pathname = (string) $file->getPathname();

					if( str_starts_with( $pathname, $prefix ) === false )
						return 'the archive holds an entry outside itself';

					$path = substr( $pathname, strlen( $prefix ) );

					if( $path !== $directory && str_starts_with( $path, $directory. '/' ) === false )
						return 'the archive holds "'. $path. '" outside "'. $directory. '/"';

					// A file of the directory's name is not the directory
					if( $path === $directory && $file->isDir() === false )
						return 'the archive holds "'. $path. '" as a file, not a directory';

					foreach( explode( '/', $path ) as $segment )
						if( $segment === '' || $segment === '.' || $segment === '..' || str_contains( $segment, '\\' ) === true )
							return 'the archive holds an unsafe path "'. $path. '"';

					// A link is neither a file nor a directory, and isLink() never says so
					// here: PharData names a tar's link entries as plain files of no size. The
					// wrapper does know - it will not open one as a file
					if( $file->isDir() === false ) {

						$handle = @fopen( $pathname, 'rb' );

						if( $handle === false )
							return 'the archive holds "'. $path. '", which is neither a file nor a directory';

						fclose( $handle );
					}

					$entries++;
					$bytes += $file->isFile() === true ? (int) $file->getSize() : 0;

					if( $entries > self::MAX_ENTRIES )
						return 'the archive holds more than '. self::MAX_ENTRIES. ' entries';

					if( $bytes > self::MAX_UNPACKED_BYTES )
						return 'the archive unpacks to more than '. self::MAX_UNPACKED_BYTES. ' bytes';
				}

				if( $entries === 0 )
					return 'the archive is empty';

				if( @mkdir( $into, 0755, true ) === false )
					return 'could not create the unpacking directory';

				/*	The @: an entry the probe above let through and the extraction cannot write - a hard link
					names a file the way a file is named - is a warning of PharData's before it is the
					exception the catch below turns into a refusal, and under the kernel's Runtime a
					warning ends the request, with the staging directory standing	*/
				@$phar->extractTo( $into, null, true );

				// PharData drops what it will not name - a "../" it silently strips - so what
				// came out is looked at once more, on disk: exactly the one directory, no links
				if( ( scandir( $into ) ?: [] ) !== [ '.', '..', $directory ] )
					return 'the archive unpacked to more than the directory "'. $directory. '"';

				if( is_dir( $into. '/'. $directory ) === false )
					return 'the archive unpacked to "'. $directory. '", which is not a directory';

				foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $into. '/'. $directory, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST ) as $file )
					if( $file->isLink() === true || ( $file->isFile() === false && $file->isDir() === false ) )
						return 'the archive unpacked to "'. substr( (string) $file->getPathname(), strlen( $into ) + 1 ). '", which is neither a file nor a directory';
			}
			catch( \Throwable $e ) {
				// PharData names the archive by its whole path on the server; the refusal names it by the staging directory
				return 'the archive could not be read: '. str_replace( $into. '/', '', $e->getMessage() );
			}

			return true;
		}

		/**
		 *	Whether the directory that came out of an archive is the family the list
		 *	promised - and nothing else. Its font.json is the entry's manifest and
		 *	names only files that are there; every file begins the way a woff2 file
		 *	does; the licence is there and says something; no file or directory
		 *	besides those is. A family is these files and nothing more.
		 *
		 *	@param		string		$dir					The unpacked directory
		 *	@param		array 		$entry				The entry of the list
		 *
		 *	@return 	true|string
		 */
		private static function _look( string $dir, array $entry ): true|string {

			$raw = @file_get_contents( $dir. '/font.json', false, null, 0, self::MAX_LIST_BYTES );

			if( $raw === false )
				return 'the archive holds no font.json';

			$data = json_decode( $raw, true );

			if( is_array( $data ) === false )
				return 'the font.json of the archive is not valid json';

			$manifest = self::manifest( $data );

			if( is_string( $manifest ) === true )
				return 'the font.json of the archive: '. $manifest;

			foreach( self::MANIFEST as $field )
				if( $manifest[$field] !== $entry[$field] )
					return 'the font.json of the archive says another "'. $field. '" than the list promised';

			$names = array_column( $manifest['files'], 'file' );

			if( is_file( $dir. '/LICENSE' ) === false )
				return 'the archive holds no LICENSE';

			if( trim( (string) @file_get_contents( $dir. '/LICENSE' ) ) === '' )
				return 'the LICENSE of the archive is empty';

			foreach( $names as $name )
				if( is_file( $dir. '/'. $name ) === false )
					return 'font.json names "'. $name. '", which the archive does not hold';

			$more = array_values( array_diff( @scandir( $dir ) ?: [], [ '.', '..', 'font.json', 'LICENSE' ], $names ) );

			if( $more !== [] )
				return 'the archive holds "'. $more[0]. '", which font.json does not name';

			foreach( $names as $name ) {

				if( @file_get_contents( $dir. '/'. $name, false, null, 0, 4 ) !== 'wOF2' )
					return '"'. $name. '" is no woff2 file - it does not begin with wOF2';

				// What a web server has to be able to read
				@chmod( $dir. '/'. $name, 0644 );
			}

			@chmod( $dir. '/font.json', 0644 );
			@chmod( $dir. '/LICENSE', 0644 );
			@chmod( $dir, 0755 );

			return true;
		}

	}
}
