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
 *									ESLint see what that Nino can run. bin/catalogue.php does
 *									not: it describes every feature of this repository, whatever
 *									it runs on.
 *
 *									A feature the kernel delivers itself (bin/delivered.php: Basis,
 *									where the kernel still carries it as a module) is not named
 *									either: the kernel refuses a directory of that name, the copy
 *									here rests, and the line on stderr says so. With --delivered
 *									the script prints those directories instead, one per line, for
 *									bin/check.sh, which lets their tests run against the kernel's.
 *
 *									A manifest the kernel cannot read is not skipped: it is named
 *									like the others, so the step that validates every manifest
 *									is the one that fails on it.
 *
 *	Usage: php bin/applicable.php <path to a Nino checkout> [--delivered]
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

require __DIR__. '/delivered.php';

$delivered = deliveredFeatures();

if( ( $problems = deliveredProblems() ) !== [] ) {
	fwrite( STDERR, implode( "\n", $problems ). "\n" );
	exit( 1 );
}

if( in_array( '--delivered', $argv, true ) === true ) {

	foreach( $delivered as $directory )
		if( is_dir( NINO_FEATURES_DIR. '/'. $directory ) === true )
			echo $directory, "\n";

	exit( 0 );
}

foreach( scandir( NINO_FEATURES_DIR ) ?: [] as $directory ) {

	if( $directory[0] === '.' || is_dir( NINO_FEATURES_DIR. '/'. $directory ) === false )
		continue;

	if( in_array( $directory, $delivered, true ) === true ) {
		fwrite( STDERR, $directory. ': delivered by Nino '. \Nino\VERSION. ' itself (_nino/Nino/Modules/'. $directory. '): not copied, not read, its tests run against that one'. "\n" );
		continue;
	}

	$manifest = \Nino\Features::manifest( NINO_FEATURES_DIR. '/'. $directory );

	if( is_array( $manifest ) === true && \Nino\Features::satisfies( (string) $manifest['nino'] ) === false ) {
		fwrite( STDERR, $directory. ': skipped on Nino '. \Nino\VERSION. ': needs '. $manifest['nino']. "\n" );
		continue;
	}

	echo $directory, "\n";
}
