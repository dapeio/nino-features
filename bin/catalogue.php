<?php
declare(strict_types=1);
/**
 *	Nino features
 *	catalogue.php	The preview: reads every feature's manifest through a Nino
 *								checkout and prints what the catalogue would list, as JSON -
 *								key, name, description, version, the Nino version constraint,
 *								PHP extensions, what it requires and the directory it lives
 *								in - without building an archive, without a signature. A
 *								manifest that does not validate fails the run: the catalogue
 *								never lists what Nino would skip. bin/check.sh and CI use it
 *								as the manifest check; .github/workflows/release.yml reads
 *								the directory and version of a feature from it.
 *
 *								What getnino.dev publishes - the archives and the signed
 *								catalogue.json in format 1 - is built by bin/build.php.
 *
 *	Usage: php bin/catalogue.php <path to a Nino checkout> > catalogue.json
 */

$root = rtrim( (string) ( $argv[1] ?? getenv( 'NINO_ROOT' ) ?: '' ), '/' );

if( $root === '' || is_file( $root. '/_nino/Nino.php' ) === false ) {
	fwrite( STDERR, "Usage: php bin/catalogue.php <path to a Nino checkout>\n" );
	exit( 2 );
}

// This repository's own features/, not the checkout's - the checkout may
// carry a copy of them (see .github/workflows/ci.yml), but the catalogue
// describes what is here
define( 'NINO_FEATURES_DIR', dirname( __DIR__ ). '/features' );

$warnings = [];
set_error_handler( static function( int $level, string $message ) use ( &$warnings ): bool {
	$warnings[] = $message;
	return true;
} );

require $root. '/_nino/Nino.php';

// A checkout that predates the feature contract has no \Nino\Features: say
// so instead of dying with a class-not-found - Nino 1.0.0-beta is such a
// checkout, and so is any main that has not taken the contract in yet
if( class_exists( '\Nino\Features' ) === false ) {
	fwrite( STDERR, "The Nino checkout at ". $root. " (". \Nino\VERSION. ") has no \\Nino\\Features - the catalogue needs a Nino that carries the feature contract (docs/features.md)\n" );
	exit( 2 );
}

$appData	= [];
$catalogue	= [];

foreach( \Nino\Features::all( $appData ) as $feature )
	$catalogue[] = [
		'key'					=> $feature['key'],
		'name'				=> $feature['name'],
		'description'	=> $feature['description'],
		'version'			=> $feature['version'],
		'nino'				=> $feature['nino'],
		'php'					=> $feature['php'],
		'requires'		=> $feature['requires'],
		'directory'		=> basename( $feature['dir'] ),
	];

// Every directory has to be in the catalogue: one Nino skipped is a manifest
// to fix, not a feature to leave out silently
$directories = array_values( array_filter( scandir( NINO_FEATURES_DIR ) ?: [], static fn( string $entry ): bool => $entry[0] !== '.' && is_dir( NINO_FEATURES_DIR. '/'. $entry ) ) );
if( count( $directories ) !== count( $catalogue ) || $warnings !== [] ) {
	fwrite( STDERR, "Not every feature validates:\n  ". implode( "\n  ", $warnings ). "\n" );
	exit( 1 );
}

echo json_encode( [
	'nino'			=> \Nino\VERSION,
	'generated'	=> gmdate( 'c' ),
	'features'	=> $catalogue,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), "\n";
