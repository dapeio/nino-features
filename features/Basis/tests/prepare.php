<?php
declare(strict_types=1);

/**
 *	Nino features
 *	prepare.php		What every suite of Basis settles before it loads the kernel: the checkout it runs against ($root, resolved) and the features directory of the
 *								run (NINO_FEATURES_DIR). The checkout is NINO_ROOT, or three folders up where the suite stands in a checkout (bin/check.sh puts it there); one
 *								that is no checkout is said so. Where the kernel delivers Basis the features directory is the checkout's own, which holds no Basis; where it does
 *								not, Basis is the folder below the features directory and the parts of its groups are read from the checkout, so the folder has to stand
 *								below the features/ of the checkout - bin/check.sh puts it there, and a suite started by hand needs it there as well, which is said when it is not.
 *
 *								Required by the suites before the harness. Not run by itself.
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/_nino/Nino.php' ) === false || is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout at '. $root. ' - set NINO_ROOT to one (clone https://github.com/dapeio/nino), or run the suite from features/Basis/tests/ of a checkout'. "\n" );
	exit( 2 );
}

if( defined( 'NINO_FEATURES_DIR' ) === false ) {

	if( is_dir( $root. '/_nino/Nino/Modules/Basis' ) === true )
		define( 'NINO_FEATURES_DIR', $root. '/features' );
	elseif( is_file( $root. '/features/Basis/feature.php' ) === true )
		define( 'NINO_FEATURES_DIR', $root. '/features' );
	else {
		fwrite( STDERR, 'The Nino checkout at '. $root. ' delivers no Basis and has no features/Basis: copy the folder there (bin/check.sh does) to run a suite of Basis by hand'. "\n" );
		exit( 2 );
	}
}
