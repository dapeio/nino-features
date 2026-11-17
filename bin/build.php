<?php
declare(strict_types=1);
/**
 *	Nino features
 *	build.php			Builds what getnino.dev publishes: one .tar.gz per package, and
 *								the catalogue.json that lists them in format 2 - what
 *								\Nino\Catalogue in Nino fetches, verifies and installs from.
 *								A package is a feature (features/<Name>/, exactly the directory
 *								that lands below a project's features/), a theme (themes/<key>/)
 *								or a package of blocks (blocks/<key>/); the archive of the
 *								two latter holds the folder of their key. Every package is read
 *								through the kernel of a Nino checkout first, with the function
 *								that installs it - \Nino\Packages::check() over the files, in
 *								the register of the components of every feature of this
 *								repository (bin/packages.php) -: the catalogue never lists what
 *								Nino would refuse. What an entry says of what a package needs
 *								(requires, parts, blocks) is computed from its files; and a svg
 *								whose rebuild by the kernel loses an element or an attribute
 *								stops the build. A problem is a message naming the file, and no
 *								file is written.
 *
 *								The applications below applications/ (bin/applications.php)
 *								are held to their rules the same way, and a single one that
 *								is not stops the build with a message naming the file. They
 *								are published as the key "applications" of catalogue.json,
 *								above the packages, by key with the one that has the most
 *								features last.
 *
 *								What main carries is the catalogue. Every archive is built
 *								on every run and replaces the one in the output directory;
 *								the archives are deterministic, so a package that did not
 *								change gives the same bytes and its file is left alone. A
 *								package that did change gives other bytes under the same
 *								name <kind>-<key>-<version>.tar.gz, and its entry carries the
 *								new sha256 and size. There is one entry per package, the
 *								version of its manifest, theme.json or pack.json; an archive
 *								or a picture in the output directory that no entry names is
 *								removed. The picture of a theme or a package of blocks
 *								(preview.webp of its folder) stands beside the archive as
 *								<kind>-<key>-<version>.webp, and its entry names its digest.
 *
 *								Every archive is read back with \Nino\Archive, the reader that
 *								installs it - no PharData -, and must hold the files it was
 *								built from; the finished document goes through
 *								\Nino\Catalogue::parse(), and an entry it cannot read is an
 *								error here.
 *
 *								The catalogue.json already in the output directory (bin/
 *								release.sh builds into public/, the directory the web server
 *								delivers, so it is the published one) is read for two
 *								things only: an entry's 'released' stays the day of the
 *								previous entry while its sha256 is the same, and 'generated'
 *								stays while the rest of the document is. A run that changes
 *								nothing leaves catalogue.json and its signature untouched.
 *
 *								Pure PHP: the archives are plain ustar tars written here, the
 *								entries sorted and every one stamped with the same fixed time,
 *								so two builds of the same tree are the same bytes - \PharData
 *								would stamp the clock in, and a rebuild a second later would
 *								differ. The signature is openssl_sign(). Nothing is shelled out.
 *
 *	Usage: php bin/build.php <nino-checkout> <out-dir> [--base-url https://catalogue.getnino.dev] [--key private.pem]
 *
 *	  --base-url   where the archives will be served from: the archive url of
 *	               an entry is <base-url>/<kind>-<key>-<version>.tar.gz
 *	  --key        the PEM private key to sign catalogue.json with (ECDSA over
 *	               P-256, see README.md). Without it no signature is written
 *	               and the openssl one-liner to sign by hand is printed
 *
 *	Exit status 0 when everything was built, 1 on any problem, 2 on
 *	a usage error.
 */

const BUILD_DEFAULT_BASE_URL		= 'https://catalogue.getnino.dev';
const BUILD_FORMAT							= 2;

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

	fail( ( $why === '' ? '' : $why. "\n" ). 'Usage: php bin/build.php <nino-checkout> <out-dir> [--base-url '. BUILD_DEFAULT_BASE_URL. '] [--key private.pem]', 2 );
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
 *	The tar of a folder as <directory>/..., its entries in the order given, with fixed modes and the fixed time
 *
 *	@param		string		$directory		The one top-level directory of the archive
 *	@param		array			$entries			path => whether it is a directory
 *	@param		array			$files				path => bytes of the files among them
 *
 *	@return 	string
 */
function tarOf( string $directory, array $entries, array $files ): string {

	$tar = tarHeader( $directory. '/', 0, '5', 0755 );

	foreach( $entries as $relative => $isDirectory ) {

		$inside = $directory. '/'. $relative;

		if( $isDirectory === true ) {
			$tar .= tarHeader( $inside. '/', 0, '5', 0755 );
			continue;
		}

		$tar .= tarHeader( $inside, strlen( $files[$relative] ), '0', 0644 );
		$tar .= $files[$relative]. str_repeat( "\0", ( 512 - strlen( $files[$relative] ) % 512 ) % 512 );
	}

	return $tar. str_repeat( "\0", 1024 );
}

/**
 *	Build one archive in memory: the folder of a package as <directory>/..., gzipped - and read back the way the kernel reads it, which has to give the
 *	files it was built from. Nothing is written
 *
 *	@param		string		$kind					One of \Nino\Packages::KINDS
 *	@param		string		$label				What the package is called in a sentence, 'themes/craft'
 *	@param		array			$entries			path => whether it is a directory, as packageCollect() answers
 *	@param		array			$files				path => bytes of the files among them
 *	@param		string		$directory		The one top-level directory of the archive
 *
 *	@return 	array										[ the archive, the files as the kernel reads them back ]
 */
function buildArchive( string $kind, string $label, array $entries, array $files, string $directory ): array {

	$packed = gzencode( tarOf( $directory, $entries, $files ), 9 );
	if( $packed === false )
		fail( $label. ' cannot be compressed' );

	if( strlen( $packed ) > \Nino\Catalogue::ARCHIVE_BYTES[$kind] )
		fail( $label. ' is '. strlen( $packed ). ' bytes packed, the kernel accepts at most '. \Nino\Catalogue::ARCHIVE_BYTES[$kind] );

	// The limits the kernel reads it by, as \Nino\Catalogue::_place() gives them
	$limits	= \Nino\Packages::LIMITS[$kind];
	$each		= $kind === \Nino\Packages::FEATURE ? $limits['bytes'] : \Nino\Blocks::MAX_PICTURE;
	$back		= \Nino\Archive::read( $packed, $directory, $limits['files'], $each, $limits['bytes'] );

	if( is_string( $back ) === true ) {

		// Which file the reader refuses, for the sentence: each one of them alone in an archive of its own
		$named = '';

		foreach( $files as $path => $bytes )
			if( \Nino\Archive::read( (string) gzencode( tarOf( $directory, [ $path => false ], $files ) ), $directory, $limits['files'], $each, $limits['bytes'] ) === $back ) {
				$named = '/'. $path;
				break;
			}

		fail( $label. $named. ' is refused by the reader of the kernel when it is read back: '. ( \Nino\Catalogue::ARCHIVE_WHY[$back] ?? $back ) );
	}

	ksort( $back, SORT_STRING );

	if( $back !== $files )
		fail( $label. ' does not read back as the files it was built from' );

	return [ $packed, $back ];
}


// --- Arguments ---------------------------------------------------------------

$positional	= [];
$options		= [ 'base-url' => BUILD_DEFAULT_BASE_URL, 'key' => '' ];

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



// --- The packages, through the kernel ----------------------------------------

// This repository's own features/, not the checkout's - the checkout may
// carry a copy of them, but the catalogue publishes what is here
$repository = dirname( __DIR__ );

define( 'NINO_FEATURES_DIR', $repository. '/features' );

require $root. '/_nino/Nino.php';

if( class_exists( '\Nino\Packages' ) === false || class_exists( '\Nino\Archive' ) === false || defined( '\Nino\Catalogue::FORMAT' ) === false || \Nino\Catalogue::FORMAT !== BUILD_FORMAT )
	fail( 'The Nino checkout at '. $root. ' ('. \Nino\VERSION. ') has no \\Nino\\Packages, \\Nino\\Archive and catalogue format '. BUILD_FORMAT. ' - the catalogue needs the Nino that reads packages, 2.0 or later (docs/features.md)', 2 );

require __DIR__. '/packages.php';
require __DIR__. '/applications.php';

[ $features, $problems ] = readFeatures( NINO_FEATURES_DIR );

if( $problems !== [] )
	fail( implode( "\n", $problems ) );

if( $features === [] )
	fail( 'No feature below '. NINO_FEATURES_DIR );

[ $register, $problems ] = packageRegister( $features );

if( $problems !== [] )
	fail( implode( "\n", $problems ) );

// What there is to publish: kind, key, the folder it is read from and the one its archive holds
$sources = [];

foreach( $features as $key => $manifest )
	$sources[] = [ 'kind' => \Nino\Packages::FEATURE, 'key' => $key, 'source' => $manifest['dir'], 'directory' => basename( $manifest['dir'] ), 'label' => 'features/'. basename( $manifest['dir'] ) ];

foreach( PACKAGES_FOLDERS as $kind => $folder )
	foreach( packageFolders( $repository, $kind ) as $name => $path )
		$sources[] = [ 'kind' => $kind, 'key' => $name, 'source' => $path, 'directory' => $name, 'label' => $folder. '/'. $name ];


// --- The applications --------------------------------------------------------

[ $applications, $problems ] = readApplications( $repository. '/applications', array_keys( $features ) );

if( $problems !== [] )
	fail( 'Not every application is valid:'. "\n  ". implode( "\n  ", $problems ) );

say( 'checked  '. count( $applications ). ' '. ( count( $applications ) === 1 ? 'application' : 'applications' ). ( $applications === [] ? '' : ' ('. implode( ', ', array_column( $applications, 'key' ) ). ')' ) );


// --- The catalogue so far ----------------------------------------------------

if( is_dir( $out ) === false && @mkdir( $out, 0755, true ) === false )
	fail( 'Could not create the output directory '. $out );

if( is_writable( $out ) === false )
	fail( 'The output directory '. $out. ' is not writable' );

$cataloguePath	= $out. '/catalogue.json';
$signaturePath	= $cataloguePath. '.sig';
$before					= null;		// the catalogue.json as it was, the bytes
$previous				= [];			// kind:key => the entry it had
$generated			= '';

if( is_file( $cataloguePath ) === true ) {

	$before		= (string) file_get_contents( $cataloguePath );
	$document	= json_decode( $before, true );

	if( is_array( $document ) === false || ( $document['format'] ?? null ) !== BUILD_FORMAT || is_array( $document['packages'] ?? null ) === false )
		fail( $cataloguePath. ' is not a format '. BUILD_FORMAT. ' catalogue - not building over it' );

	foreach( $document['packages'] as $index => $entry ) {

		if( is_array( $entry ) === false || is_string( $entry['kind'] ?? null ) === false || is_string( $entry['key'] ?? null ) === false || is_string( $entry['version'] ?? null ) === false )
			fail( $cataloguePath. ': entry '. $index. ' names no kind, key and version - not building over it' );

		$id = $entry['kind']. ':'. $entry['key'];

		// Per package the highest version: the entry the next one follows
		if( isset( $previous[$id] ) === false || version_compare( $entry['version'], $previous[$id]['version'] ) > 0 )
			$previous[$id] = $entry;
	}

	$generated = is_string( $document['generated'] ?? null ) === true ? $document['generated'] : '';

	say( 'reading  '. $cataloguePath. ' ('. count( $document['packages'] ). ' '. ( count( $document['packages'] ) === 1 ? 'entry' : 'entries' ). ')' );
}


// --- Check and build every package -------------------------------------------

// Every package is looked at before a file is written: a run that stops on the last of them leaves the output directory as it was
$today		= gmdate( 'Y-m-d' );
$built		= [];		// kind:key => [ entry, archive, picture ]

foreach( $sources as $source ) {

	$kind				= $source['kind'];
	$label			= $source['label'];
	$id					= $kind. ':'. $source['key'];

	[ $entries, $files, $problems ] = packageCollect( $source['source'], $kind, $label );

	if( $problems !== [] )
		fail( implode( "\n", $problems ) );

	// What the kernel installs a package by is asked of the files as they lie in the folder, so that a problem names the file; the archive that is built
	// after it is read back and has to hold the same files, which are the ones the kernel is asked of when it installs
	if( $kind === \Nino\Packages::FEATURE ) {

		$problem = \Nino\Packages::check( $register, $kind, $files );

		if( $problem !== null )
			fail( $label. ( $problem['path'] === '' ? ': ' : '/' ). \Nino\Packages::why( $problem ) );

		$entry = featureEntry( $features[ $source['key'] ] );
	}
	else {

		[ $entry, $problems ] = packageEntry( $register, $kind, $source['key'], $files, $features );

		if( $problems !== [] )
			fail( implode( "\n", $problems ) );
	}

	[ $packed, $back ] = buildArchive( $kind, $label, $entries, $files, $source['directory'] );

	$built[$id] = [ 'entry' => $entry, 'archive' => $packed, 'preview' => $kind === \Nino\Packages::FEATURE ? null : $back[PACKAGES_PREVIEW], 'label' => $label, 'count' => count( $entries ) + 1, 'bytes' => array_sum( array_map( 'strlen', $back ) ) ];
}

// --- The entries -------------------------------------------------------------

// In the order they were read: the features, the themes, the packages of blocks, each by key
$entries = [];		// kind:key => entry, as the catalogue carries it

foreach( $built as $id => &$one ) {

	$entry		= $one['entry'];
	$name			= $entry['kind']. '-'. $entry['key']. '-'. $entry['version'];
	$sha256		= hash( 'sha256', $one['archive'] );

	$one['name']	= $name;
	$one['sha256']	= $sha256;

	// The day of the entry before while the archive is the same bytes, today when it is not
	$released = $today;
	if( isset( $previous[$id] ) === true && strtolower( (string) ( $previous[$id]['sha256'] ?? '' ) ) === $sha256 && is_string( $previous[$id]['released'] ?? null ) === true && trim( $previous[$id]['released'] ) !== '' )
		$released = $previous[$id]['released'];

	if( $one['preview'] !== null )
		$entry['preview'] = [ 'url' => $baseUrl. '/'. $name. '.webp', 'sha256' => hash( 'sha256', $one['preview'] ), 'size' => strlen( $one['preview'] ) ];

	$entries[$id] = $entry + [
		'archive'			=> $baseUrl. '/'. $name. '.tar.gz',
		'sha256'			=> $sha256,
		'size'				=> strlen( $one['archive'] ),
		'released'		=> $released,
	];
}

unset( $one );

// --- Write and sign ----------------------------------------------------------

$encode = static function( array $packages, string $generated ) use ( $applications ): string {

	try {
		return json_encode( [ 'format' => BUILD_FORMAT, 'generated' => $generated, 'applications' => $applications, 'packages' => $packages ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ). "\n";
	}
	catch( \JsonException $e ) {
		fail( 'The catalogue cannot be encoded: '. $e->getMessage() );
	}
};

// 'generated' is the time of the last change: while the entries are the
// ones the catalogue already carries, it stays, and so do the bytes
$json			= $encode( array_values( $entries ), $generated !== '' ? $generated : gmdate( 'Y-m-d\TH:i:s\Z' ) );
$changed	= $json !== $before;

if( $changed === true )
	$json = $encode( array_values( $entries ), gmdate( 'Y-m-d\TH:i:s\Z' ) );

// What the kernel on the other end will make of it: every entry is read, and one it leaves out is an error here
$parsed = \Nino\Catalogue::parse( $json );

if( is_string( $parsed ) === true )
	fail( 'This kernel would refuse the catalogue: '. $parsed );

if( $parsed['unreadable'] !== [] )
	fail( 'This kernel cannot read every entry:'. "\n  ". implode( "\n  ", array_map( static fn( array $one ): string => 'entry '. $one['index']. ' ('. $one['kind']. ' '. $one['key']. '): '. $one['reason'], $parsed['unreadable'] ) ) );

if( count( $parsed['packages'] ) !== count( $entries ) )
	fail( 'This kernel reads '. count( $parsed['packages'] ). ' of '. count( $entries ). ' entries' );

/**
 *	Write a file whole: into a temporary name beside it, then renamed over it.
 *	The output directory may be the one a web server delivers, and a reader
 *	never sees half a catalogue
 */
function place( string $path, string $data ): void {

	$staged = $path. '.tmp';

	if( @file_put_contents( $staged, $data ) !== strlen( $data ) || @rename( $staged, $path ) === false ) {
		@unlink( $staged );
		fail( 'Could not write '. $path );
	}
}

// Nothing was written until now: what the kernel reads of the finished document is what is put in the directory
foreach( $built as $one ) {

	$path		= $out. '/'. $one['name']. '.tar.gz';
	$label	= str_pad( $one['entry']['kind']. ' '. $one['entry']['key']. ' '. $one['entry']['version'], 34 );
	$same		= is_file( $path ) === true && hash_file( 'sha256', $path ) === $one['sha256'];

	// The same bytes are already there: the file stays, with the time it has, so that what syncs the directory sees no change
	if( $same === false )
		place( $path, $one['archive'] );

	say( ( $same === true ? 'same     ' : 'built    ' ). $label. $one['name']. '.tar.gz ('. $one['count']. ' entries, '. human( strlen( $one['archive'] ) ). ' packed, '. human( $one['bytes'] ). ' unpacked, sha256 '. substr( $one['sha256'], 0, 12 ). '…)' );

	if( $one['preview'] !== null ) {

		$picture = $out. '/'. $one['name']. '.webp';

		if( is_file( $picture ) === false || hash_file( 'sha256', $picture ) !== hash( 'sha256', $one['preview'] ) )
			place( $picture, $one['preview'] );
	}
}

// An archive or a picture no entry names is what an earlier version or a package that is gone left behind: the directory holds what the catalogue lists,
// nothing else
$named = [];

foreach( $entries as $entry ) {

	$named[] = basename( $entry['archive'] );

	if( isset( $entry['preview'] ) === true )
		$named[] = basename( $entry['preview']['url'] );
}

foreach( [ '*.tar.gz', '*.webp' ] as $pattern )
	foreach( glob( $out. '/'. $pattern ) ?: [] as $file )
		if( in_array( basename( $file ), $named, true ) === false ) {
			@unlink( $file );
			say( 'removed  '. basename( $file ). ' - no entry names it' );
		}

if( $changed === true ) {

	place( $cataloguePath, $json );

	say( 'wrote    '. $cataloguePath. ' ('. count( $entries ). ' '. ( count( $entries ) === 1 ? 'entry' : 'entries' ). ', accepted by \\Nino\\Catalogue::parse())' );
}
else
	say( 'same     '. $cataloguePath. ' ('. count( $entries ). ' '. ( count( $entries ) === 1 ? 'entry' : 'entries' ). ', nothing changed)' );

// Signed are the bytes on disk, not the string in memory
$bytes = (string) file_get_contents( $cataloguePath );

if( $privateKey === null ) {

	if( $changed === true && is_file( $signaturePath ) === true ) {
		@unlink( $signaturePath );
		say( 'removed  '. $signaturePath. ' - it signed an earlier catalogue.json' );
	}

	if( is_file( $signaturePath ) === false ) {
		say( 'not signed - sign by hand with the private key:' );
		say( '  openssl dgst -sha256 -sign catalogue-key.pem '. $cataloguePath. ' | base64 -w0 > '. $signaturePath );
	}

	exit( 0 );
}

// The signature that is there already holds for the same bytes: it stays
if( $changed === false && is_file( $signaturePath ) === true && \Nino\Catalogue::verify( $bytes, (string) file_get_contents( $signaturePath ), $publicKey ) === true ) {
	say( 'same     '. $signaturePath. ' (verified with the public key)' );
	exit( 0 );
}

if( openssl_sign( $bytes, $der, $privateKey, OPENSSL_ALGO_SHA256 ) !== true )
	fail( 'Signing failed: '. ( openssl_error_string() ?: 'openssl_sign() returned false' ) );

$signature = base64_encode( $der ). "\n";

// The public half of the same key has to accept it - the way the kernel
// checks, and with openssl
$holds = openssl_verify( $bytes, $der, $publicKey, OPENSSL_ALGO_SHA256 ) === 1
	&& \Nino\Catalogue::verify( $bytes, $signature, $publicKey ) === true;

if( $holds === false )
	fail( 'The signature does not verify with the public key derived from '. $options['key'] );

place( $signaturePath, $signature );

$details = openssl_pkey_get_details( $privateKey );
$keyKind = ( $details['type'] ?? null ) === OPENSSL_KEYTYPE_EC ? 'EC '. ( $details['ec']['curve_name'] ?? '' ) : ( ( $details['type'] ?? null ) === OPENSSL_KEYTYPE_RSA ? 'RSA '. ( $details['bits'] ?? '' ) : 'key' );

say( 'signed   '. $signaturePath. ' ('. trim( $keyKind ). ', verified with the public key)' );

exit( 0 );
