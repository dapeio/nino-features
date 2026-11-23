<?php
declare(strict_types=1);

/**
 *	Nino features
 *	themelab-move-smoke.php	Contract test for the move from the feature Blocks: the right of it is given once to what named the right of Blocks, and what the old feature left in config.php goes with it
 *
 *	Usage: php features/Themelab/tests/themelab-move-smoke.php
 *				 NINO_ROOT=../nino php features/Themelab/tests/themelab-move-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$site = ninoSandbox( 'themelab-move' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

echo "\nF. The move from the feature Blocks\n";

$M = '/_admin/builder/manage';
$D = '/_admin/builder/develop/edit';
$OLD = \Nino\Modules\Themelab::BEFORE;
$moving = $site;
$moving['/nino/auth/roles'] = [
	'worker'	=> [ 'label' => 'Worker', 'perms' => [ $M, $OLD ] ],
	'wild'		=> [ 'label' => 'Wild', 'perms' => [ '/_admin/blocks/*' ] ],
	'nodoor'	=> [ 'label' => 'Develops without a door', 'perms' => [ $D ] ],
	'panel'		=> [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ],
	'all'			=> [ 'label' => 'All', 'perms' => [ '/*' ] ],
	'site'		=> [ 'label' => 'Site', 'perms' => [ '/_admin/*' ] ],
	'develop'	=> [ 'label' => 'Develops', 'perms' => [ $M, $D ] ],
];
$moving['/nino/auth/user'] = [
	'mine'		=> [ 'perms' => [ $OLD, $M ], 'role' => '' ],
	'star'		=> [ 'perms' => [ '/_admin/*' ], 'role' => '' ],
	'devs'		=> [ 'perms' => [ $D ], 'role' => '' ],
	'plain'		=> [ 'perms' => [ $M ], 'role' => '' ],
];
unset( $moving[\Nino\Modules\Themelab::MOVED] );
$moving['/nino/modules'] = array_merge( [ '\\Nino\\Modules\\Blocks' ], array_values( (array) ( $moving['/nino/modules'] ?? [] ) ) );
\Nino\Filesystem::mutate( $moving, '/config.php', static function( array $content ) use ( $moving ): array {

	$content['/nino/modules'] = $moving['/nino/modules'];
	unset( $content[\Nino\Modules\Themelab::MOVED] );
	$content['/nino/auth/roles'] = $moving['/nino/auth/roles'];
	$content['/nino/auth/user'] = $moving['/nino/auth/user'];
	$content[\Nino\Features::STATE_KEY]['blocks'] = [ 'version' => '2.0.0', 'settings' => [] ];

	return $content;
} );
$moving['./nino/auth/baseline'] = $moving['/nino/auth/user'];
\Nino\Modules\Themelab::init( $moving );

$rol = static function( string $id ) use ( &$moving ): array { return $moving['/nino/auth/roles'][$id]['perms']; };
$usr = static function( string $mail ) use ( &$moving ): array { return $moving['/nino/auth/user'][$mail]['perms']; };
$stored = (array) \Nino\Filesystem::getFileContent( $moving, '/config.php', [] );

check( 'the first request after Themelab is on gives the right of it to what named the right of Blocks - the entry, or the entry of all of its rights, in a role and in an account - and takes the old one away; a blanket, the step without a door and an account that never had it get nothing, and nothing else is given',
	$rol( 'worker' ) === [ $M, \Nino\Modules\Themelab::PERM ] && $rol( 'wild' ) === [ '/_admin/themelab/*' ] && $rol( 'nodoor' ) === [ $D ] && $rol( 'panel' ) === [ '/_admin/builder/*' ] && $rol( 'all' ) === [ '/*' ] && $rol( 'site' ) === [ '/_admin/*' ] && $rol( 'develop' ) === [ $M, $D ] && $usr( 'star' ) === [ '/_admin/*' ] && $usr( 'devs' ) === [ $D ]
	&& $usr( 'mine' ) === [ \Nino\Modules\Themelab::PERM, $M ] && $usr( 'plain' ) === [ $M ] && in_array( $OLD, $rol( 'worker' ), true ) === false );

check( 'what the old feature left in config.php is taken with it - its class in the list of the modules and the record of its version - and the mark of the move is written, so that the move is made once',
	in_array( '\\Nino\\Modules\\Blocks', array_map( static fn( mixed $class ): string => '\\'. ltrim( (string) $class, '\\' ), (array) ( $stored['/nino/modules'] ?? [] ) ), true ) === false && isset( $stored[\Nino\Features::STATE_KEY]['blocks'] ) === false && ( $stored[\Nino\Modules\Themelab::MOVED] ?? false ) === true && $moving[\Nino\Modules\Themelab::MOVED] === true );

// a right of the old name that is written later - a restored backup, a hand - is not moved again: the mark says it was made
$moving['/nino/auth/roles']['late'] = [ 'label' => 'Late', 'perms' => [ $OLD ] ];
\Nino\Modules\Themelab::init( $moving );

check( 'the move is made once: a role that names the old right after the mark is written is left as it is - Themelab gives nothing a second time',
	$rol( 'late' ) === [ $OLD ] );

// the mark is asked again under the lock config.php is written under: a request that read the roles before another moved them - and so has no mark in memory - finds the mark in the file, writes nothing, and
// takes the mark into memory
$settled = (string) @hash_file( 'sha256', $sandbox. '/private/config.php' );
$stale = $moving;
unset( $stale[\Nino\Modules\Themelab::MOVED] );
$stale['/nino/auth/roles']['late'] = [ 'label' => 'Late', 'perms' => [ $OLD ] ];
\Nino\Modules\Themelab::move( $stale );

check( 'a request that comes with no mark in memory while the file has it moves nothing under the lock: config.php is as it was, the role that names the old right is as it was, the mark is in memory after',
	(string) @hash_file( 'sha256', $sandbox. '/private/config.php' ) === $settled && $stale['/nino/auth/roles']['late']['perms'] === [ $OLD ] && $stale[\Nino\Modules\Themelab::MOVED] === true );

ninoDone( $site );
