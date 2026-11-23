<?php
declare(strict_types=1);
/**
 *	Nino features
 *	catalogue.php	The preview: reads every package through a Nino checkout and
 *								prints what the catalogue would list, as JSON - the features
 *								below features/, the themes below themes/ and the packages of
 *								blocks below blocks/ as the entries of format 2, with what is
 *								computed of them (what they require, the parts of a theme, the
 *								blocks) - without building an archive, without a picture and
 *								without a signature. A package that does not validate fails the
 *								run, with the file and the sentence: the catalogue never lists
 *								what Nino would refuse. bin/check.sh and CI use it as the
 *								manifest check. The applications of applications/
 *								(bin/applications.php) are listed above the packages - name,
 *								features, entry point - and an invalid one fails the run as
 *								well.
 *
 *								What getnino.dev publishes - the archives and the signed
 *								catalogue.json in format 2 - is built by bin/build.php.
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

require $root. '/_nino/Nino.php';

// A checkout that predates the packages: say so instead of dying with a class-not-found
if( class_exists( '\Nino\Packages' ) === false || class_exists( '\Nino\Archive' ) === false || defined( '\Nino\Catalogue::FORMAT' ) === false || \Nino\Catalogue::FORMAT !== 2 ) {
	fwrite( STDERR, "The Nino checkout at ". $root. " (". \Nino\VERSION. ") has no \\Nino\\Packages, \\Nino\\Archive and catalogue format 2 - the catalogue needs the Nino that reads packages, 2.0 or later (docs/features.md)\n" );
	exit( 2 );
}

require __DIR__. '/packages.php';
require __DIR__. '/applications.php';

ignoreDeliveredWarnings();

[ $features, $problems, $delivered ] = readFeatures( NINO_FEATURES_DIR );

if( $problems === [] ) {
	[ $register, $found ] = packageRegister( $features, $delivered );
	$problems = $found;
}

$packages = array_map( 'featureEntry', array_values( $features ) );

foreach( $problems === [] ? PACKAGES_FOLDERS : [] as $kind => $folder )
	foreach( packageFolders( dirname( __DIR__ ), $kind ) as $name => $path ) {

		[ , $files, $found ] = packageCollect( $path, $kind, $folder. '/'. $name );

		if( $found === [] )
			[ $entry, $found ] = packageEntry( $register, $kind, $name, $files, $features );

		$problems = array_merge( $problems, $found );

		if( $found === [] )
			$packages[] = $entry;
	}

// The applications, every one - which of them a kernel offers is the kernel's
// to say, as the features it can run are for bin/applicable.php
if( $problems === [] ) {

	[ $applications, $found ] = readApplications( dirname( __DIR__ ). '/applications', array_merge( array_keys( $features ), array_keys( $delivered ) ), array_map( static fn( array $manifest ): array => $manifest['requires'], $features ), array_keys( $delivered ) );

	if( $found !== [] )
		$problems[] = "Not every application is valid:\n  ". implode( "\n  ", $found );
}

// What the kernel delivers is not listed, and a copy of it here rests - say it, so that a run shows which of the two cases it is
foreach( $problems === [] ? $delivered : [] as $directory )
	if( is_dir( NINO_FEATURES_DIR. '/'. $directory ) === true )
		fwrite( STDERR, 'features/'. $directory. ': delivered by the kernel, not listed. An index of this catalogue is built with a kernel that does not deliver it'. "\n" );

if( $problems !== [] ) {
	fwrite( STDERR, implode( "\n", $problems ). "\n" );
	exit( 1 );
}

echo json_encode( [
	'nino'					=> \Nino\VERSION,
	'generated'			=> gmdate( 'c' ),
	'applications'	=> $applications,
	'packages'			=> $packages,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), "\n";
