<?php
declare(strict_types=1);

/**
 *	Nino features
 *	support.php		What the suites of the feature Basis have in common: where Basis stands in the run.
 *
 *								Basis is a feature like the others: its folder is features/Basis of the checkout, and every suite runs against it. Where it stands is the kernel's
 *								to say - \Nino\Features::get() - and no suite knows a path of it; the parts of its groups are read from the checkout below /features/Basis, so a
 *								folder that stands elsewhere is none of this run's.
 *
 *								Required by the suites after the harness. Not run by itself.
 */

/**
 *	Where Basis stands in this run
 *
 *	@return 	string										The folder of Basis
 */
function basisUnderTest(): string {

	static $found = null;

	if( $found === null ) {

		$probe = [];
		$basis = \Nino\Features::get( $probe, 'basis' );

		if( $basis === null ) {
			fwrite( STDERR, "No feature Basis: features/Basis is not there\n" );
			exit( 2 );
		}

		$found = rtrim( (string) $basis['dir'], '/' );

		if( str_starts_with( $found, $GLOBALS['root']. '/' ) === false ) {
			fwrite( STDERR, 'Basis stands at '. $found. ', not below the checkout '. $GLOBALS['root']. ': copy it to features/Basis of the checkout (bin/check.sh does) to run a suite of Basis by hand'. "\n" );
			exit( 2 );
		}
	}

	return $found;
}
