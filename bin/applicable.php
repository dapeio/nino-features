<?php
declare(strict_types=1);
/**
 *	Nino features
 *	applicable.php	Which features a Nino checkout can run: one directory name per
 *									line on stdout - the features whose "nino" constraint the
 *									checkout's version satisfies, by the checkout's own
 *									\Nino\Features::satisfies() - and every other one, with its
 *									reason, on stderr:
 *
 *										Seo: skipped on Nino 1.3.2: needs ^1.4
 *
 *									A feature that asks for a newer Nino than the one it runs on
 *									claims nothing about it. bin/check.sh and the CI copy only
 *									what this names into the checkout, so the tests, PHPStan and
 *									ESLint see what that Nino can run, and the tests that go over
 *									every feature (tests/build-smoke.php, tests/keys-smoke.php)
 *									ask the same question. bin/catalogue.php does not: it
 *									describes every feature of this repository, whatever it runs
 *									on.
 *
 *									A manifest the kernel cannot read is not skipped: it is named
 *									like the others, so the step that validates every manifest
 *									is the one that fails on it.
 *
 *	Usage: php bin/applicable.php <path to a Nino checkout>
 */

$root = rtrim( (string) ( $argv[1] ?? getenv( 'NINO_ROOT' ) ?: '' ), '/' );

if( $root === '' || is_file( $root. '/_nino/Nino.php' ) === false ) {
	fwrite( STDERR, "Usage: php bin/applicable.php <path to a Nino checkout>\n" );
	exit( 2 );
}

// This repository's own features/, not the checkout's: it is the one to choose from
define( 'NINO_FEATURES_DIR', dirname( __DIR__ ). '/features' );

set_error_handler( static fn(): bool => true );

require $root. '/_nino/Nino.php';

if( class_exists( '\Nino\Features' ) === false ) {
	fwrite( STDERR, "The Nino checkout at ". $root. " (". \Nino\VERSION. ") has no \\Nino\\Features - the catalogue needs a Nino that carries the feature contract (docs/features.md)\n" );
	exit( 2 );
}

foreach( scandir( NINO_FEATURES_DIR ) ?: [] as $directory ) {

	if( $directory[0] === '.' || is_dir( NINO_FEATURES_DIR. '/'. $directory ) === false )
		continue;

	$manifest = \Nino\Features::manifest( NINO_FEATURES_DIR. '/'. $directory );

	if( is_array( $manifest ) === true && \Nino\Features::satisfies( (string) $manifest['nino'] ) === false ) {
		fwrite( STDERR, $directory. ': skipped on Nino '. \Nino\VERSION. ': needs '. $manifest['nino']. "\n" );
		continue;
	}

	echo $directory, "\n";
}
