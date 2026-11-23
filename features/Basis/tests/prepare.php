<?php
declare(strict_types=1);

/**
 *	Nino features
 *	prepare.php		What every suite of Basis settles before it loads the kernel: the checkout it runs against ($root, resolved) and the features directory of the
 *								run (NINO_FEATURES_DIR). The checkout is NINO_ROOT, or three folders up where the suite stands in a checkout (bin/check.sh puts it there); one
 *								that is no checkout is said so, and so is one that carries a Basis of its own. Basis is the folder features/Basis of the checkout: the parts of its
 *								groups are read from there, so a suite started by hand needs it there, which is said when it is not. A suite that draws pages for fixtures names a
 *								features directory of its own in $featuresDir before it requires this file.
 *
 *								Required by the suites before the harness. Not run by itself.
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/_nino/Nino.php' ) === false || is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout at '. $root. ' - set NINO_ROOT to one (clone https://github.com/dapeio/nino), or run the suite from features/Basis/tests/ of a checkout'. "\n" );
	exit( 2 );
}

if( is_dir( $root. '/_nino/Nino/Modules/Basis' ) === true ) {
	fwrite( STDERR, 'The Nino checkout at '. $root. ' carries a Basis of its own (_nino/Nino/Modules/Basis): this catalogue needs a Nino without a Basis of its own'. "\n" );
	exit( 2 );
}

if( defined( 'NINO_FEATURES_DIR' ) === false ) {

	if( is_file( $root. '/features/Basis/feature.php' ) === false ) {
		fwrite( STDERR, 'The Nino checkout at '. $root. ' has no features/Basis: copy the folder there (bin/check.sh does) to run a suite of Basis by hand'. "\n" );
		exit( 2 );
	}

	define( 'NINO_FEATURES_DIR', $featuresDir ?? $root. '/features' );
}

/**
 *	The folder of Basis as a feature below a features directory of a suite's own: made again, without its suites, every file with the one time given - so that the
 *	version of a bundle that holds a file of it is the same in every run. The path is the suite's to keep the same
 *
 *	@param		string		$features			The features directory
 *	@param		int				$time					The time of every file
 *
 *	@return 	void
 */
function basisFeaturesDir( string $features, int $time ): void {

	$remove = static function( string $path ) use ( &$remove ): void {

		if( is_dir( $path ) === true && is_link( $path ) === false ) {

			foreach( array_diff( scandir( $path ) ?: [], [ '.', '..' ] ) as $entry )
				$remove( $path. '/'. $entry );

			rmdir( $path );
		}
		elseif( file_exists( $path ) === true || is_link( $path ) === true )
			unlink( $path );
	};

	$copy = static function( string $from, string $to ) use ( &$copy, $time ): void {

		mkdir( $to, 0755, true );

		foreach( array_diff( scandir( $from ) ?: [], [ '.', '..' ] ) as $entry ) {

			if( $from === $GLOBALS['root']. '/features/Basis' && $entry === 'tests' )
				continue;

			is_dir( $from. '/'. $entry ) === true ? $copy( $from. '/'. $entry, $to. '/'. $entry ) : copy( $from. '/'. $entry, $to. '/'. $entry );
		}

		touch( $to, $time );
	};

	$remove( $features );
	$copy( $GLOBALS['root']. '/features/Basis', $features. '/Basis' );

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $features. '/Basis', \FilesystemIterator::SKIP_DOTS ) ) as $file )
		touch( $file->getPathname(), $time );
}
