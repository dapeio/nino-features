<?php
declare(strict_types=1);
/**
 *	Nino features
 *	build.php			Builds what getnino.dev publishes: one .tar.gz per feature
 *								version, holding exactly the directory that lands below a
 *								project's features/, and the catalogue.json that lists them
 *								in format 1 - what \Nino\Catalogue in Nino fetches, verifies
 *								and installs from. Every manifest below features/ is read
 *								through the kernel of a Nino checkout first, --only or not:
 *								the catalogue never lists what Nino would skip.
 *
 *								A published version is immutable. An archive that already
 *								exists in the output directory is neither rebuilt nor
 *								overwritten, and the entry the catalogue carries for it
 *								stays as it is; an entry whose archive is missing here is
 *								rebuilt and has to come out byte for byte the same. A change
 *								to a feature is a new version in feature.php, nothing else.
 *
 *								The catalogue is merged, not replaced: a catalogue.json
 *								already in the output directory keeps every entry of every
 *								other key and version. That is how one release joins what
 *								is published - .github/workflows/release.yml fetches the
 *								published catalogue into the output directory first.
 *
 *								Pure PHP: the archives are plain ustar tars written here, the
 *								entries sorted and every one stamped with the same fixed time,
 *								so two builds of the same tree are the same bytes - \PharData
 *								would stamp the clock in, and a rebuild a second later would
 *								differ. The signature is openssl_sign(). Nothing is shelled out.
 *
 *	Usage: php bin/build.php <nino-checkout> <out-dir> [--base-url https://catalogue.getnino.dev] [--key private.pem] [--only <key>]
 *
 *	  --base-url   where the archives will be served from: the archive url of
 *	               an entry is <base-url>/<key>-<version>.tar.gz
 *	  --key        the PEM private key to sign catalogue.json with (ECDSA over
 *	               P-256, see README.md). Without it no signature is written
 *	               and the openssl one-liner to sign by hand is printed
 *	  --only       build one feature, by key; every other entry is kept
 *
 *	Exit status 0 when everything was built or kept, 1 on any problem, 2 on
 *	a usage error.
 */

const BUILD_DEFAULT_BASE_URL		= 'https://catalogue.getnino.dev';
const BUILD_FORMAT							= 1;

// What \Nino\Catalogue refuses - refused here first, where it can be fixed
const BUILD_MAX_ARCHIVE_BYTES		= 20 * 1024 * 1024;
const BUILD_MAX_UNPACKED_BYTES	= 50 * 1024 * 1024;
const BUILD_MAX_ENTRIES					= 5000;

// Left out of every archive: the feature's own tests, and what git, an
// editor or a desktop leaves behind. A pattern is matched against every
// path segment; 'tests' only against the first
const BUILD_EXCLUDED						= [ '.git*', '.DS_Store', 'Thumbs.db', '.idea', '.vscode', '*~', '*.swp', '*.swo', '*.swx', '.#*', '#*#', '*.orig', '*.rej', '*.bak' ];

const BUILD_ENTRY_FIELDS				= [ 'key', 'name', 'description', 'version', 'nino', 'php', 'requires', 'directory', 'archive', 'sha256', 'size', 'released' ];

// The modification time every archive entry carries - one fixed instant,
// 2026-01-01T00:00:00Z, never the clock: what makes a rebuild the same bytes
const BUILD_MTIME								= 1767225600;


// --- Output ------------------------------------------------------------------

/**
 *	@param		string		$line
 *
 *	@return 	void
 */
function say( string $line ): void {

	echo $line, "\n";
}

/**
 *	@param		string		$why
 *	@param		int				$status
 *
 *	@return 	never
 */
function fail( string $why, int $status = 1 ): never {

	fwrite( STDERR, $why. "\n" );
	exit( $status );
}

/**
 *	@param		string		$why					'' for the bare usage line
 *
 *	@return 	never
 */
function usage( string $why = '' ): never {

	fail( ( $why === '' ? '' : $why. "\n" ). 'Usage: php bin/build.php <nino-checkout> <out-dir> [--base-url '. BUILD_DEFAULT_BASE_URL. '] [--key private.pem] [--only <key>]', 2 );
}

/**
 *	@param		int				$bytes
 *
 *	@return 	string									'12.3 kB'
 */
function human( int $bytes ): string {

	return $bytes < 1024 ? $bytes. ' B' : ( $bytes < 1024 * 1024 ? sprintf( '%.1f kB', $bytes / 1024 ) : sprintf( '%.1f MB', $bytes / ( 1024 * 1024 ) ) );
}


// --- Files -------------------------------------------------------------------

/**
 *	@param		string		$target				A file or a directory, removed with everything below it
 *
 *	@return 	void
 */
function removeTree( string $target ): void {

	if( is_link( $target ) === true || is_file( $target ) === true ) {
		@unlink( $target );
		return;
	}

	if( is_dir( $target ) === false )
		return;

	foreach( scandir( $target ) ?: [] as $entry )
		if( $entry !== '.' && $entry !== '..' )
			removeTree( $target. '/'. $entry );

	@rmdir( $target );
}

/**
 *	Whether a path relative to the feature directory stays out of the archive
 *
 *	@param		string		$relative			eg. 'tests/search-smoke.php'
 *
 *	@return 	bool
 */
function excluded( string $relative ): bool {

	$segments = explode( '/', $relative );

	if( $segments[0] === 'tests' )
		return true;

	foreach( $segments as $segment )
		foreach( BUILD_EXCLUDED as $pattern )
			if( fnmatch( $pattern, $segment, FNM_PERIOD ) === true || fnmatch( $pattern, $segment ) === true )
				return true;

	return false;
}

/**
 *	Every file and directory below the feature directory that goes into the
 *	archive, sorted - so two builds of the same tree add the same entries in
 *	the same order. A symlink or anything else that is neither a file nor a
 *	directory fails the build, because the kernel refuses it on the other end.
 *
 *	@param		string		$source				The feature directory
 *
 *	@return 	array										relative path => whether it is a directory
 */
function collectFiles( string $source ): array {

	$files = [];

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS ),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach( $iterator as $file ) {

		$relative = substr( (string) $file->getPathname(), strlen( $source ) + 1 );

		if( excluded( $relative ) === true )
			continue;

		if( $file->isLink() === true || ( $file->isFile() === false && $file->isDir() === false ) )
			fail( basename( $source ). '/'. $relative. ' is neither a file nor a directory - an archive holds nothing else' );

		if( $file->isFile() === true && $file->isReadable() === false )
			fail( basename( $source ). '/'. $relative. ' is not readable' );

		$files[$relative] = $file->isDir();
	}

	ksort( $files, SORT_STRING );

	return $files;
}

/**
 *	Look at a finished archive the way \Nino\Catalogue does before it unpacks
 *	one: every entry inside the one directory, bounded in count and size
 *
 *	@param		string		$path					Absolute path of the .tar.gz
 *	@param		string		$directory		The one top-level directory it may hold
 *
 *	@return 	array										[ entries, unpacked bytes ]
 */
function inspectArchive( string $path, string $directory ): array {

	$entries	= 0;
	$bytes		= 0;

	try {
		$phar = new \PharData( $path );

		foreach( new \RecursiveIteratorIterator( $phar, \RecursiveIteratorIterator::SELF_FIRST ) as $file ) {

			$inside = substr( (string) $file->getPathname(), strlen( 'phar://'. $path. '/' ) );

			if( $inside !== $directory && str_starts_with( $inside, $directory. '/' ) === false )
				fail( basename( $path ). ' holds "'. $inside. '" outside "'. $directory. '/"' );

			if( $file->isLink() === true || ( $file->isFile() === false && $file->isDir() === false ) )
				fail( basename( $path ). ' holds "'. $inside. '", which is neither a file nor a directory' );

			$entries++;
			$bytes += $file->isFile() === true ? (int) $file->getSize() : 0;
		}
	}
	catch( \Throwable $e ) {
		fail( basename( $path ). ' cannot be read back: '. $e->getMessage() );
	}

	if( $entries === 0 )
		fail( basename( $path ). ' is empty' );

	if( $entries > BUILD_MAX_ENTRIES )
		fail( basename( $path ). ' holds '. $entries. ' entries, the kernel accepts at most '. BUILD_MAX_ENTRIES );

	if( $bytes > BUILD_MAX_UNPACKED_BYTES )
		fail( basename( $path ). ' unpacks to '. $bytes. ' bytes, the kernel accepts at most '. BUILD_MAX_UNPACKED_BYTES );

	return [ $entries, $bytes ];
}

/**
 *	One ustar header block - 512 bytes naming an entry, with the fixed time,
 *	no owner, and the checksum tar readers verify. A name longer than 100
 *	bytes is split at a slash into the ustar prefix and name fields.
 *
 *	@param		string		$name					Path inside the archive; a directory ends in '/'
 *	@param		int				$size					Content length, 0 for a directory
 *	@param		string		$type					'0' for a file, '5' for a directory
 *	@param		int				$mode					0644 or 0755
 *
 *	@return 	string
 */
function tarHeader( string $name, int $size, string $type, int $mode ): string {

	$prefix = '';

	if( strlen( $name ) > 100 ) {
		$cut = strrpos( substr( $name, 0, 156 ), '/' );
		if( $cut === false || $cut === 0 || strlen( $name ) - $cut - 1 > 100 || strlen( $name ) - $cut - 1 === 0 )
			fail( 'the path "'. $name. '" is too long for a tar archive' );
		$prefix	= substr( $name, 0, $cut );
		$name		= substr( $name, $cut + 1 );
	}

	$header  = str_pad( $name, 100, "\0" );
	$header .= sprintf( "%07o\0", $mode );
	$header .= "0000000\0". "0000000\0";
	$header .= sprintf( "%011o\0", $size );
	$header .= sprintf( "%011o\0", BUILD_MTIME );
	$header .= '        ';
	$header .= $type;
	$header .= str_repeat( "\0", 100 );
	$header .= "ustar\0". '00';
	$header .= str_repeat( "\0", 32 ). str_repeat( "\0", 32 );
	$header .= "0000000\0". "0000000\0";
	$header .= str_pad( $prefix, 155, "\0" );
	$header  = str_pad( $header, 512, "\0" );

	$sum = 0;
	for( $i = 0; $i < 512; $i++ )
		$sum += ord( $header[$i] );

	return substr( $header, 0, 148 ). sprintf( "%06o\0 ", $sum ). substr( $header, 156 );
}

/**
 *	Build one archive: the feature directory as <directory>/..., its files
 *	in sorted order with fixed modes and the fixed time, as one tar in
 *	memory, gzipped, written into a temporary directory, read back and
 *	checked the way the kernel reads it, then moved into place
 *
 *	@param		string		$source				The feature directory
 *	@param		string		$directory		Its name - the one top-level directory of the archive
 *	@param		string		$target				<out-dir>/<key>-<version>.tar.gz
 *
 *	@return 	array										[ entries, unpacked bytes ]
 */
function buildArchive( string $source, string $directory, string $target ): array {

	$files = collectFiles( $source );

	$tar = tarHeader( $directory. '/', 0, '5', 0755 );

	foreach( $files as $relative => $isDirectory ) {

		$inside = $directory. '/'. $relative;

		if( $isDirectory === true ) {
			$tar .= tarHeader( $inside. '/', 0, '5', 0755 );
			continue;
		}

		$content = file_get_contents( $source. '/'. $relative );
		if( $content === false )
			fail( 'could not read '. $directory. '/'. $relative );

		$tar .= tarHeader( $inside, strlen( $content ), '0', 0644 );
		$tar .= $content. str_repeat( "\0", ( 512 - strlen( $content ) % 512 ) % 512 );
	}

	$tar .= str_repeat( "\0", 1024 );

	$packed = gzencode( $tar, 9 );
	if( $packed === false )
		fail( 'could not compress '. basename( $target ) );

	if( strlen( $packed ) > BUILD_MAX_ARCHIVE_BYTES )
		fail( basename( $target ). ' is '. strlen( $packed ). ' bytes packed, the kernel accepts at most '. BUILD_MAX_ARCHIVE_BYTES );

	$tmp = sys_get_temp_dir(). '/nino-build-'. bin2hex( random_bytes( 6 ) );
	if( @mkdir( $tmp, 0700, true ) === false )
		fail( 'could not create a temporary directory below '. sys_get_temp_dir() );

	$staged = $tmp. '/'. basename( $target );

	if( file_put_contents( $staged, $packed ) !== strlen( $packed ) ) {
		removeTree( $tmp );
		fail( 'could not write '. basename( $target ). ' to the temporary directory' );
	}

	$stats = inspectArchive( $staged, $directory );

	if( @rename( $staged, $target ) === false && ( @copy( $staged, $target ) === false || (int) filesize( $target ) !== strlen( $packed ) ) ) {
		removeTree( $tmp );
		@unlink( $target );
		fail( 'could not write '. $target );
	}

	removeTree( $tmp );

	return $stats;
}


// --- Arguments ---------------------------------------------------------------

$positional	= [];
$options		= [ 'base-url' => BUILD_DEFAULT_BASE_URL, 'key' => '', 'only' => '' ];

for( $i = 1; $i < count( $argv ); $i++ ) {

	$arg = (string) $argv[$i];

	if( str_starts_with( $arg, '--' ) === false ) {
		$positional[] = $arg;
		continue;
	}

	[ $name, $value ] = array_pad( explode( '=', substr( $arg, 2 ), 2 ), 2, null );

	if( array_key_exists( $name, $options ) === false )
		usage( 'Unknown option --'. $name );

	if( $value === null ) {
		if( isset( $argv[ $i + 1 ] ) === false )
			usage( '--'. $name. ' needs a value' );
		$value = (string) $argv[ ++$i ];
	}

	$options[$name] = $value;
}

if( count( $positional ) !== 2 )
	usage();

$root	= rtrim( $positional[0], '/' );
$out	= rtrim( $positional[1], '/' );

if( $root === '' || is_file( $root. '/_nino/Nino.php' ) === false )
	usage( 'No Nino checkout at "'. $positional[0]. '" - no _nino/Nino.php there' );

if( $out === '' )
	usage( 'The output directory must be named' );

$baseUrl = rtrim( trim( $options['base-url'] ), '/' );
if( preg_match( '#^https://[a-z0-9.-]+(?::\d+)?(?:/[^\s?\#]*)?$#i', $baseUrl ) !== 1 )
	usage( '--base-url must be an https url, the kernel fetches nothing else: "'. $options['base-url']. '"' );

$only = trim( $options['only'] );

// The signing key is read before anything is built: a run that cannot sign
// is a run that leaves nothing half done
$privateKey	= null;
$publicKey	= '';

if( $options['key'] !== '' ) {

	$pem = @file_get_contents( $options['key'] );
	if( $pem === false || trim( $pem ) === '' )
		fail( 'The signing key '. $options['key']. ' cannot be read' );

	$privateKey = openssl_pkey_get_private( $pem );
	if( $privateKey === false )
		fail( 'The signing key '. $options['key']. ' is not a PEM private key: '. ( openssl_error_string() ?: 'unreadable' ) );

	$publicKey = (string) ( openssl_pkey_get_details( $privateKey )['key'] ?? '' );
	if( $publicKey === '' )
		fail( 'No public key can be derived from '. $options['key'] );
}


// --- The features, through the kernel ----------------------------------------

// This repository's own features/, not the checkout's - the checkout may
// carry a copy of them, but the catalogue publishes what is here
define( 'NINO_FEATURES_DIR', dirname( __DIR__ ). '/features' );

$warnings = [];
set_error_handler( static function( int $level, string $message ) use ( &$warnings ): bool {
	$warnings[] = $message;
	return true;
} );

require $root. '/_nino/Nino.php';

if( class_exists( '\Nino\Features' ) === false )
	fail( 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \\Nino\\Features - the catalogue needs a Nino that carries the feature contract (docs/features.md)', 2 );

$features = [];		// key => manifest, every directory below features/

foreach( scandir( NINO_FEATURES_DIR ) ?: [] as $entry ) {

	$dir = NINO_FEATURES_DIR. '/'. $entry;

	if( $entry[0] === '.' || is_dir( $dir ) === false )
		continue;

	$manifest = \Nino\Features::manifest( $dir );

	if( $manifest === null )
		fail( 'features/'. $entry. ' does not validate:'. "\n  ". implode( "\n  ", $warnings ) );

	if( isset( $features[ $manifest['key'] ] ) === true )
		fail( 'features/'. $entry. ' claims the key "'. $manifest['key']. '" that features/'. basename( $features[ $manifest['key'] ]['dir'] ). ' already holds' );

	$features[ $manifest['key'] ] = $manifest;
}

if( $warnings !== [] )
	fail( 'Not every feature validates:'. "\n  ". implode( "\n  ", $warnings ) );

restore_error_handler();

if( $features === [] )
	fail( 'No feature below '. NINO_FEATURES_DIR );

ksort( $features );

if( $only !== '' && isset( $features[$only] ) === false )
	fail( 'No feature has the key "'. $only. '" - there are: '. implode( ', ', array_keys( $features ) ) );

$selected = $only === '' ? $features : [ $only => $features[$only] ];


// --- The catalogue so far ----------------------------------------------------

if( is_dir( $out ) === false && @mkdir( $out, 0755, true ) === false )
	fail( 'Could not create the output directory '. $out );

if( is_writable( $out ) === false )
	fail( 'The output directory '. $out. ' is not writable' );

$cataloguePath	= $out. '/catalogue.json';
$signaturePath	= $cataloguePath. '.sig';
$entries				= [];		// key@version => entry, as the catalogue carries it

if( is_file( $cataloguePath ) === true ) {

	$document = json_decode( (string) file_get_contents( $cataloguePath ), true );

	if( is_array( $document ) === false || ( $document['format'] ?? null ) !== BUILD_FORMAT || is_array( $document['features'] ?? null ) === false )
		fail( $cataloguePath. ' is not a format '. BUILD_FORMAT. ' catalogue - not merging into it' );

	foreach( $document['features'] as $index => $entry ) {

		if( is_array( $entry ) === false || is_string( $entry['key'] ?? null ) === false || is_string( $entry['version'] ?? null ) === false )
			fail( $cataloguePath. ': entry '. $index. ' names no key and version - not merging into it' );

		$entries[ $entry['key']. '@'. $entry['version'] ] = $entry;
	}

	say( 'merging into '. $cataloguePath. ' ('. count( $entries ). ' '. ( count( $entries ) === 1 ? 'entry' : 'entries' ). ')' );
}


// --- Build or keep -----------------------------------------------------------

$today = gmdate( 'Y-m-d' );

foreach( $selected as $key => $manifest ) {

	$version		= $manifest['version'];
	$directory	= basename( $manifest['dir'] );
	$name				= $key. '-'. $version. '.tar.gz';
	$path				= $out. '/'. $name;
	$id					= $key. '@'. $version;
	$label			= str_pad( $key. ' '. $version, 24 );
	$existing		= $entries[$id] ?? null;

	// A file of zero bytes is not an archive that was ever published -
	// publish.php takes nothing the catalogue does not name with a size -
	// but what a web server that answers 200 with an empty body for a
	// missing file leaves behind in dist/. Removed and built afresh
	if( is_file( $path ) === true && (int) filesize( $path ) === 0 ) {
		say( 'removed  '. $label. $name. ' - an empty file is not an archive (does the server answer 404 for a missing file?)' );
		@unlink( $path );
	}

	if( is_file( $path ) === true ) {

		$sha256	= (string) hash_file( 'sha256', $path );
		$size		= (int) filesize( $path );

		if( $existing !== null ) {

			if( strtolower( (string) ( $existing['sha256'] ?? '' ) ) !== $sha256 || ( $existing['size'] ?? null ) !== $size )
				fail( $name. ' is not the archive the catalogue entry for '. $id. ' names (sha256 '. ( $existing['sha256'] ?? '?' ). ', '. ( $existing['size'] ?? '?' ). ' bytes) - a published version is immutable; if this archive was never published, remove it and build again, else fetch the published one' );

			say( 'kept     '. $label. $name. ' - already exists, a published version is immutable' );
			continue;
		}

		say( 'indexed  '. $label. $name. ' - already exists, its entry was missing ('. human( $size ). ', sha256 '. substr( $sha256, 0, 12 ). '…)' );
	}
	else {

		[ $count, $unpacked ] = buildArchive( $manifest['dir'], $directory, $path );

		$sha256	= (string) hash_file( 'sha256', $path );
		$size		= (int) filesize( $path );

		// An entry without its archive here came from the published catalogue:
		// the rebuild has to be the very bytes projects verify against
		if( $existing !== null && ( strtolower( (string) ( $existing['sha256'] ?? '' ) ) !== $sha256 || ( $existing['size'] ?? null ) !== $size ) ) {
			@unlink( $path );
			fail( $id. ' is already in the catalogue with sha256 '. ( $existing['sha256'] ?? '?' ). ' and '. ( $existing['size'] ?? '?' ). ' bytes, and a rebuild does not give the same archive - a published version is immutable: fetch the published '. $name. ' into '. $out. ' to keep it, or release a new version' );
		}

		say( 'built    '. $label. $name. ' ('. $count. ' entries, '. human( $size ). ' packed, '. human( $unpacked ). ' unpacked, sha256 '. substr( $sha256, 0, 12 ). '…)' );
	}

	$released = is_string( $existing['released'] ?? null ) === true && trim( $existing['released'] ) !== '' ? $existing['released'] : $today;

	$entry = [
		'key'					=> $key,
		'name'				=> $manifest['name'],
		'description'	=> $manifest['description'],
		'version'			=> $version,
		'nino'				=> $manifest['nino'],
		'php'					=> [ 'ext' => array_values( $manifest['php']['ext'] ) ],
		'requires'		=> array_values( $manifest['requires'] ),
		'directory'		=> $directory,
		'archive'			=> $baseUrl. '/'. $name,
		'sha256'			=> $sha256,
		'size'				=> $size,
		'released'		=> $released,
	];

	// The kernel refuses an empty description where it accepts a missing one
	if( is_string( $entry['description'] ) === true && trim( $entry['description'] ) === '' )
		unset( $entry['description'] );

	$entries[$id] = $entry;
}


// Where an archive is served from is this build's --base-url, for every
// entry, kept or built: the url is derived from the place, not part of the
// version - a catalogue that moves to another host keeps its archives and
// their digests and names them where they are now
foreach( $entries as &$entry )
	$entry['archive'] = $baseUrl. '/'. $entry['key']. '-'. $entry['version']. '.tar.gz';
unset( $entry );


// --- Write and sign ----------------------------------------------------------

uasort( $entries, static fn( array $a, array $b ): int => strcmp( $a['key'], $b['key'] ) ?: version_compare( $b['version'], $a['version'] ) );

$document = [
	'format'		=> BUILD_FORMAT,
	'generated'	=> gmdate( 'Y-m-d\TH:i:s\Z' ),
	'features'	=> array_values( $entries ),
];

try {
	$json = json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ). "\n";
}
catch( \JsonException $e ) {
	fail( 'The catalogue cannot be encoded: '. $e->getMessage() );
}

// What the kernel on the other end will make of it - when this checkout
// carries the kernel side already
$checked = '';
if( class_exists( '\Nino\Catalogue' ) === true ) {

	$parsed = \Nino\Catalogue::parse( $json );

	if( is_string( $parsed ) === true )
		fail( 'This kernel would refuse the catalogue: '. $parsed );

	if( count( $parsed['features'] ) !== count( $entries ) )
		fail( 'This kernel reads '. count( $parsed['features'] ). ' of '. count( $entries ). ' entries - two entries name the same key and version' );

	$checked = ', accepted by \\Nino\\Catalogue::parse()';
}
else
	$checked = ', not checked against \\Nino\\Catalogue (this Nino has none yet)';

if( @file_put_contents( $cataloguePath, $json ) !== strlen( $json ) )
	fail( 'Could not write '. $cataloguePath );

say( 'wrote    '. $cataloguePath. ' ('. count( $entries ). ' '. ( count( $entries ) === 1 ? 'entry' : 'entries' ). $checked. ')' );

// Signed are the bytes on disk, not the string in memory
$bytes = (string) file_get_contents( $cataloguePath );

if( $privateKey === null ) {

	if( is_file( $signaturePath ) === true ) {
		@unlink( $signaturePath );
		say( 'removed  '. $signaturePath. ' - it signed an earlier catalogue.json' );
	}

	say( 'not signed - sign by hand with the private key:' );
	say( '  openssl dgst -sha256 -sign catalogue-key.pem '. $cataloguePath. ' | base64 -w0 > '. $signaturePath );
	exit( 0 );
}

if( openssl_sign( $bytes, $der, $privateKey, OPENSSL_ALGO_SHA256 ) !== true )
	fail( 'Signing failed: '. ( openssl_error_string() ?: 'openssl_sign() returned false' ) );

$signature = base64_encode( $der ). "\n";

// The public half of the same key has to accept it - the way the kernel
// checks, where this checkout has the kernel side, and with openssl either way
$holds = openssl_verify( $bytes, $der, $publicKey, OPENSSL_ALGO_SHA256 ) === 1
	&& ( class_exists( '\Nino\Catalogue' ) === false || \Nino\Catalogue::verify( $bytes, $signature, $publicKey ) === true );

if( $holds === false )
	fail( 'The signature does not verify with the public key derived from '. $options['key'] );

if( @file_put_contents( $signaturePath, $signature ) !== strlen( $signature ) )
	fail( 'Could not write '. $signaturePath );

$details = openssl_pkey_get_details( $privateKey );
$keyKind = ( $details['type'] ?? null ) === OPENSSL_KEYTYPE_EC ? 'EC '. ( $details['ec']['curve_name'] ?? '' ) : ( ( $details['type'] ?? null ) === OPENSSL_KEYTYPE_RSA ? 'RSA '. ( $details['bits'] ?? '' ) : 'key' );

say( 'signed   '. $signaturePath. ' ('. trim( $keyKind ). ', verified with the public key)' );

exit( 0 );
