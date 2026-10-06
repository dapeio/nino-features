<?php
declare(strict_types=1);

/**
 *	Nino features
 *	panels-smoke.php	How a panel script of the catalogue talks to the
 *										workbench. The panels are loaded by whatever Nino a
 *										project runs - the constraint admits 1.3.0 on - and
 *										the request helper of the newer ones is not in the
 *										older ones, so a panel posts through
 *										Nino.adminUi.api where that exists and through a post
 *										of its own where it does not. That post's base has to
 *										be the project's directory: a root-absolute '/_admin/'
 *										reaches the workbench of a project that lives at the
 *										domain's root and nobody else's, and Nino.dir, which
 *										names the directory in a script, does not exist before
 *										1.3.2 - the literal '[[/nino/dir]]' the asset bundle
 *										fills in does. Each feature's own test runs its
 *										panel under node; this is the one place that holds
 *										every panel to the post.
 *
 *	Usage: php tests/panels-smoke.php
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

$root		= dirname( __DIR__ );
$panels	= [];

foreach( (array) glob( $root. '/features/*/assets/*.js' ) as $file )
	$panels[ substr( (string) $file, strlen( $root ) + 1 ) ] = (string) file_get_contents( (string) $file );

check( 'the panel scripts were actually read', count( $panels ) > 20 );

echo "\nNo panel posts to a root-absolute /_admin/\n";

$rooted = [];
foreach( $panels as $name => $source )
	if( preg_match( '#sendRequest\(\s*[\'"]/_admin/#', $source ) === 1 )
		$rooted[] = $name;

check( 'every post to the workbench starts at the project\'s directory'. ( $rooted === [] ? '' : ' - '. implode( ' | ', $rooted ) ), $rooted === [] );

echo "\nA panel that posts by hand asks the shell's helper first\n";

$posting = [];
foreach( $panels as $name => $source )
	if( str_contains( $source, '[[/nino/dir]]/_admin/' ) === true )
		$posting[ $name ] = $source;

check( 'the panels that post are the panels with a workbench', count( $posting ) >= 10 );

foreach( $posting as $name => $source ) {
	$helper	= strpos( $source, 'Nino.adminUi.api.call(' );
	$post		= strpos( $source, 'Nino.http.sendRequest(' );
	check( $name. ' goes through Nino.adminUi.api.call() where there is one, before its own post', $helper !== false && $post !== false && $helper < $post );
}

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
