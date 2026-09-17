<?php
declare(strict_types=1);

/**
 *	Nino features
 *	escaping-smoke.php	htmlspecialchars() returns '' for input that is not
 *											valid utf-8, unless ENT_SUBSTITUTE is among its flags -
 *											and php's own default carries it only as long as no
 *											flags are given at all. Every call in the catalogue
 *											spells its flags out, so every call has to spell out
 *											that one too: a value that renders as nothing is a
 *											bug nobody sees, and it has bitten the framework twice
 *											(a submission mailed as nothing, a field rendered as
 *											nothing) before a grep was put in front of the third.
 *											This is that grep for the catalogue; the framework's
 *											own is in its kernel-smoke.php.
 *
 *	Usage: php tests/escaping-smoke.php
 */

$failures = 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

$root			= realpath( __DIR__. '/..' );
$sources	= [];
$walk			= static function( string $dir ) use ( &$walk, &$sources ): void {
	foreach( (array) glob( $dir. '/*' ) as $path ) {
		if( is_dir( $path ) === true )
			$walk( $path );
		elseif( str_ends_with( (string) $path, '.php' ) === true )
			$sources[] = (string) $path;
	}
};
$walk( $root. '/features' );

$offenders = [];
foreach( $sources as $file ) {
	foreach( explode( "\n", (string) file_get_contents( $file ) ) as $no => $line ) {
		if( str_contains( $line, 'htmlspecialchars(' ) === false || str_contains( $line, 'ENT_' ) === false )
			continue;
		if( str_contains( $line, 'ENT_SUBSTITUTE' ) === true )
			continue;
		$offenders[] = substr( (string) realpath( $file ), strlen( (string) $root ) + 1 ). ':'. ( $no + 1 );
	}
}

check( 'no escape in the catalogue drops ENT_SUBSTITUTE'. ( $offenders === [] ? '' : ' - '. implode( ' | ', $offenders ) ), $offenders === [] );
check( '...and the rule has something to find: the sources were actually read', count( $sources ) > 60 );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
