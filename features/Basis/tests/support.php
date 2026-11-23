<?php
declare(strict_types=1);

/**
 *	Nino features
 *	support.php		What the suites of the feature Basis have in common: where Basis stands in the run, and the line that says so.
 *
 *								A suite of Basis runs in one of two cases, and says which. Where the kernel delivers Basis itself (a module of the kernel,
 *								_nino/Nino/Modules/Basis) the suites run against that one and bin/check.sh copies only their tests/ into the checkout; where it
 *								does not, Basis is the feature below features/ and the suites run against that. Where Basis stands is the kernel's to say -
 *								\Nino\Features::get() - and no suite knows a path of it. What the one case does that the other cannot show is said by the suite
 *								with a line that begins "skipped".
 *
 *								Required by the suites after the harness. Not run by itself.
 */

/**
 *	Where Basis stands in this run, and the line that says it
 *
 *	@return 	array										[ the folder of Basis, whether the kernel delivers it ]
 */
function basisUnderTest(): array {

	static $found = null;

	if( $found === null ) {

		$probe = [];
		$basis = \Nino\Features::get( $probe, 'basis' );

		if( $basis === null ) {
			fwrite( STDERR, "No feature Basis: neither the kernel delivers it nor features/Basis is there\n" );
			exit( 2 );
		}

		$found = [ rtrim( (string) $basis['dir'], '/' ), $basis['shipped'] === true ];

		// The parts of its groups are read from the checkout below /features/Basis: a folder that stands elsewhere is none of this run's
		if( $found[1] === false && str_starts_with( $found[0], $GLOBALS['root']. '/' ) === false ) {
			fwrite( STDERR, 'Basis stands at '. $found[0]. ', not below the checkout '. $GLOBALS['root']. ': copy it to features/Basis of the checkout (bin/check.sh does) to run a suite of Basis by hand'. "\n" );
			exit( 2 );
		}

		echo 'Basis under test: '. ( $found[1] === true ? 'the one the kernel delivers (_nino/Nino/Modules/Basis)' : 'the feature below features/' ). "\n\n";
	}

	return $found;
}
