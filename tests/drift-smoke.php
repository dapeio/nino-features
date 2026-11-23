<?php
declare(strict_types=1);

/**
 *	Nino features
 *	drift-smoke.php	While the kernel delivers Basis, the copy below features/Basis rests and no suite runs it: its suites run against the kernel's folder. So that the two
 *									do not part unseen, the copy (without tests/) is the folder of the kernel but for the lines that the catalogue needs and the kernel does not -
 *									the nine files of fixtures/drift/basis-differences.json, each with the reason and exactly the lines that go and the lines that come. A
 *									change of a file on one side only, a file on one side only and a change in a line of the nine is a failure; so is a line the list names and the
 *									folders no longer part by. Where the kernel does not deliver Basis the copy is the feature, the suites run it, and the line "skipped" says there is
 *									nothing to compare.
 *
 *	Usage: php tests/drift-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/drift-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'drift' );

if( is_dir( $root. '/_nino/Nino/Modules/Basis' ) === false ) {

	echo "  skipped - the kernel delivers no Basis: the copy below features/ is the feature, its suites run it, and there is no folder of the kernel to part from\n";
	ninoDone( $appData );
}

/**
 *	The files below a folder but for its tests/, relative, sorted
 */
$files = static function( string $dir ): array {

	$found = [];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$relative = substr( $file->getPathname(), strlen( $dir ) + 1 );

		if( str_starts_with( $relative, 'tests/' ) === false )
			$found[] = $relative;
	}

	sort( $found, SORT_STRING );

	return $found;
};

$kernel = $root. '/_nino/Nino/Modules/Basis';
$copy = $repo. '/features/Basis';
$listed = (array) json_decode( (string) file_get_contents( __DIR__. '/fixtures/drift/basis-differences.json' ), true );

check( 'the copy and the folder of the kernel hold the same files', $files( $kernel ) === $files( $copy ) && count( $files( $copy ) ) > 90 );

$parted = [];

foreach( $files( $kernel ) as $relative ) {

	$one = (string) file_get_contents( $kernel. '/'. $relative );
	$other = (string) file_get_contents( $copy. '/'. $relative );

	if( $one === $other ) {

		if( isset( $listed[$relative] ) === true )
			$parted[] = $relative. ' is listed and does not differ';

		continue;
	}

	// The lines that go and the lines that come, as multisets: where a line stands is no matter
	$gone = array_count_values( explode( "\n", $one ) );
	$came = array_count_values( explode( "\n", $other ) );
	$removed = [];
	$added = [];

	foreach( $gone as $line => $count )
		for( $n = ( $came[$line] ?? 0 ); $n < $count; $n++ )
			$removed[] = (string) $line;

	foreach( $came as $line => $count )
		for( $n = ( $gone[$line] ?? 0 ); $n < $count; $n++ )
			$added[] = (string) $line;

	sort( $removed, SORT_STRING );
	sort( $added, SORT_STRING );

	if( isset( $listed[$relative] ) === false || $removed !== $listed[$relative]['removed'] || $added !== $listed[$relative]['added'] )
		$parted[] = $relative;
}

check( 'the files differ in the lines of the list and in no other: '. count( $listed ). ' files, each with the lines that go and the lines that come'. ( $parted === [] ? '' : ' - NOT: '. implode( ', ', $parted ) ),
	$parted === [] && count( $listed ) === 9 && array_filter( $listed, static fn( array $entry ): bool => is_string( $entry['reason'] ?? null ) === true && $entry['reason'] !== '' ) === $listed );

ninoDone( $appData );
