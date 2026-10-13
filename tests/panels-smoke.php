<?php
declare(strict_types=1);

/**
 *	Nino features
 *	panels-smoke.php	How a panel script of the catalogue talks to the
 *										workbench: through Nino.adminUi.api.call(), which is in every
 *										Nino from 1.4.0 on, or through Nino.adminUi.panel() of 1.8,
 *										whose call() and whose first request are that same helper -
 *										and through nothing else. A post of a panel's own was the
 *										fallback of a panel loaded by a Nino that has no helper, one
 *										that names 1.3, and its base had to be the project's
 *										directory: a root-absolute '/_admin/' reaches the workbench
 *										of a project that lives at the domain's root and nobody
 *										else's, and Nino.dir, which names the directory in a script,
 *										does not exist before 1.3.2 - the literal '[[/nino/dir]]' the
 *										asset bundle fills in does. The fallback goes with the ^1.8
 *										floor of the panel that carries it: Gallery and Search are the
 *										two that still do, and the set that may post by hand is empty
 *										the day both are on the toolset. Each feature's own test runs
 *										its panel under node; this is the one place that holds every
 *										panel to the post.
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

echo "\nOnly the panels that still name Nino 1.3 post by hand, and ask the shell's helper first\n";

// Gallery and Search: the set is empty the day both are on the toolset
$byHand = [ 'Gallery', 'Search' ];

$workbench = [];
foreach( $panels as $name => $source )
	if( str_ends_with( $name, '/assets/admin.js' ) === true )
		$workbench[ $name ] = $source;

$posting = [];
foreach( $workbench as $name => $source )
	if( str_contains( $source, '[[/nino/dir]]/_admin/' ) === true )
		$posting[ $name ] = $source;

check( 'the panels that post by hand are '. ( $byHand === [] ? 'none' : 'the ones that still name Nino 1.3, '. implode( ' and ', $byHand ) ),
	array_keys( $posting ) === array_map( static fn( string $feature ): string => 'features/'. $feature. '/assets/admin.js', $byHand ) );

foreach( $posting as $name => $source ) {
	$helper	= strpos( $source, 'Nino.adminUi.api.call(' );
	$post		= strpos( $source, 'Nino.http.sendRequest(' );
	check( $name. ' goes through Nino.adminUi.api.call() where there is one, before its own post', $helper !== false && $post !== false && $helper < $post );
}

echo "\nEvery other panel goes through the kernel's helper or its panel() alone\n";

check( 'there are panels besides those', count( $workbench ) > count( $posting ) );

foreach( $workbench as $name => $source )
	if( isset( $posting[ $name ] ) === false )
		check( $name. ' posts through Nino.adminUi.api.call() or Nino.adminUi.panel() and has no post of its own',
			( str_contains( $source, 'Nino.adminUi.api.call(' ) === true || str_contains( $source, 'Nino.adminUi.panel(' ) === true )
			&& str_contains( $source, 'Nino.http.sendRequest(' ) === false );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
