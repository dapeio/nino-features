<?php
declare(strict_types=1);

/**
 *	Nino features
 *	panels-smoke.php	How a panel script of the catalogue talks to the
 *										workbench. The request helper, Nino.adminUi.api, is in
 *										every Nino from 1.4.0 on, so a panel whose constraint
 *										names 1.4 or later posts through it and through
 *										nothing else. Gallery, Hello and Search still name 1.3
 *										and are loaded by a Nino that has no helper, so they
 *										post through it where it exists and through a post of
 *										their own where it does not. That post's base has to
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

echo "\nOnly Gallery, Hello and Search post by hand, and ask the shell's helper first\n";

$workbench = [];
foreach( $panels as $name => $source )
	if( str_ends_with( $name, '/assets/admin.js' ) === true )
		$workbench[ $name ] = $source;

$posting = [];
foreach( $workbench as $name => $source )
	if( str_contains( $source, '[[/nino/dir]]/_admin/' ) === true )
		$posting[ $name ] = $source;

check( 'the panels that post by hand are the three that still name Nino 1.3',
	array_keys( $posting ) === [ 'features/Gallery/assets/admin.js', 'features/Hello/assets/admin.js', 'features/Search/assets/admin.js' ] );

foreach( $posting as $name => $source ) {
	$helper	= strpos( $source, 'Nino.adminUi.api.call(' );
	$post		= strpos( $source, 'Nino.http.sendRequest(' );
	check( $name. ' goes through Nino.adminUi.api.call() where there is one, before its own post', $helper !== false && $post !== false && $helper < $post );
}

echo "\nEvery other panel goes through the kernel's helper alone\n";

check( 'there are panels besides those three', count( $workbench ) > count( $posting ) );

foreach( $workbench as $name => $source )
	if( isset( $posting[ $name ] ) === false )
		check( $name. ' posts through Nino.adminUi.api.call() and has no post of its own',
			str_contains( $source, 'Nino.adminUi.api.call(' ) === true && str_contains( $source, 'Nino.http.sendRequest(' ) === false );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
