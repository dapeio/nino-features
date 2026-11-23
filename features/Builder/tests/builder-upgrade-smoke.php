<?php
declare(strict_types=1);

/**
 *	Nino features
 *	builder-upgrade-smoke.php	Contract test for the right of the editor of the blocks that the update of the Builder gives: the move is made once, nobody holds more than before, and the account of the development that nobody upgraded still designs
 *
 *	Usage: php features/Builder/tests/builder-upgrade-smoke.php
 *				 NINO_ROOT=../nino php features/Builder/tests/builder-upgrade-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$site = ninoSandbox( 'builder-upgrade' );
$sandbox = ninoSandboxDir( $site );
$site['./nino/filesystem/path'] = $root;
$site['/nino/html/assets'] = [];
$site['/nino/html/navs'] = [ 'main' ];
$site['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder', '\\Nino\\Modules\\Themelab' ];
\Nino\Html::init( $site );
\Nino\Features::registerComponents( $site );
\Nino\Modules::callModules( $site, 'init' );

echo "\nE. The way up\n";

$M = '/_admin/builder/manage';
$D = '/_admin/builder/develop/edit';
$B = '/_admin/themelab/manage';
$DS = '/_admin/builder/design/edit';
$site['/nino/auth/roles'] = [
	'dev'			=> [ 'label' => 'Dev', 'perms' => [ $M, $D ] ],
	'devonly'	=> [ 'label' => 'Dev without door', 'perms' => [ $D ] ],
	'door'		=> [ 'label' => 'Door', 'perms' => [ $M ] ],
	'design'	=> [ 'label' => 'Design', 'perms' => [ $M, $DS ] ],
	'content'	=> [ 'label' => 'Content', 'perms' => [ $M, '/_admin/builder/content/edit' ] ],
	'themelab'	=> [ 'label' => 'Themelab', 'perms' => [ $B ] ],
	'all'			=> [ 'label' => 'All', 'perms' => [ '/*' ] ],
	'panel'		=> [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ],
];
$site['/nino/auth/user'] = [
	'direct'		=> [ 'perms' => [ $M, $D ], 'role' => '' ],
	'wild'			=> [ 'perms' => [ $M, '/_admin/builder/develop/*' ], 'role' => '' ],
	'nodoor'		=> [ 'perms' => [ $D ], 'role' => '' ],
	'nodoorrole'=> [ 'perms' => [], 'role' => 'devonly' ],
	'doorplus'	=> [ 'perms' => [ $M ], 'role' => 'devonly' ],
	'devplus'		=> [ 'perms' => [ $D ], 'role' => 'door' ],
	'viarole'		=> [ 'perms' => [], 'role' => 'dev' ],
	'plain'			=> [ 'perms' => [ $M, $DS ], 'role' => '' ],
];
$before = $site;
$gone = \Nino\Modules\Builder::upgrade( $site, '1.0.0' );
$r = static fn( string $id ): array => $site['/nino/auth/roles'][$id]['perms'];
$u = static fn( string $mail ): array => $site['/nino/auth/user'][$mail]['perms'];
$can = static fn( string $mail ): bool => \Nino\Auth::holds( \Nino\Auth::permissions( $site, $site['/nino/auth/user'][$mail] ), '/_admin/themelab/manage' );
$again = $site;
\Nino\Modules\Builder::upgrade( $again, '1.0.0' );

check( 'the upgrade gives the right of the editor of the blocks to what could develop - the door and the step together, by an own permission, a blanket, a wildcard or a role - and to nothing else: the step without the door, in an account or in a role, gets nothing and loses the dead permission; and it is the same the second time',
	$gone === true && $r( 'dev' ) === [ $M, $B, $DS ] && $r( 'devonly' ) === [] && $r( 'door' ) === [ $M ] && $r( 'design' ) === [ $M, $DS ] && $r( 'content' ) === $before['/nino/auth/roles']['content']['perms'] && $r( 'themelab' ) === [ $B ] && $r( 'all' ) === [ '/*' ] && $r( 'panel' ) === [ '/_admin/builder/*', $B ]
	&& $u( 'direct' ) === [ $M, $B, $DS ] && $u( 'wild' ) === [ $M, $B, $DS ] && $u( 'nodoor' ) === [] && $can( 'nodoor' ) === false && $can( 'nodoorrole' ) === false && $u( 'nodoorrole' ) === []
	&& $can( 'doorplus' ) === true && in_array( $B, $u( 'doorplus' ), true ) === true && $can( 'devplus' ) === true && in_array( $B, $u( 'devplus' ), true ) === true && $can( 'viarole' ) === true && $u( 'viarole' ) === [] && $u( 'plain' ) === [ $M, $DS ]
	&& $again['/nino/auth/roles'] === $site['/nino/auth/roles'] && $again['/nino/auth/user'] === $site['/nino/auth/user'] );

// the move is made once: an update that comes from the version with the move, or later, gives nobody the right - not back to the account it was taken from, not to one that was made after
// the move with the blanket of the panel - and writes nothing where nothing moved
$config = static fn(): string => (string) @hash_file( 'sha256', $sandbox. '/private/config.php' );
$late = $site;
$late['/nino/auth/roles'] = [ 'panel' => [ 'label' => 'Panel', 'perms' => [ '/_admin/builder/*' ] ], 'dev' => [ 'label' => 'Dev', 'perms' => [ $M, $D ] ] ];
$late['/nino/auth/user'] = [ 'taken' => [ 'perms' => [ '/_admin/builder/*' ], 'role' => '' ], 'made' => [ 'perms' => [ $M, $DS, '/_admin/builder/*' ], 'role' => '' ] ];
$was = $late;
$hash = $config();
$lateDone = \Nino\Modules\Builder::upgrade( $late, '1.1.0' ) === true && \Nino\Modules\Builder::upgrade( $late, '1.2.0' ) === true;
$quiet = $config() === $hash;
$first = $late;
\Nino\Modules\Builder::upgrade( $first, '1.0.0' );
$plain = $site;
$plain['/nino/auth/roles'] = [ 'design' => [ 'label' => 'Design', 'perms' => [ $M, $DS ] ] ];
$plain['/nino/auth/user'] = [];
$hash = $config();
\Nino\Modules\Builder::upgrade( $plain, '1.0.0' );

check( 'the move of the right is made once: an update from the version with the move or from a later one gives nobody the right - the account that had it taken away keeps it taken away, the one made later with the blanket of the panel has none - and an update that moves nothing writes no configuration',
	$lateDone === true && $late['/nino/auth/roles'] === $was['/nino/auth/roles'] && $late['/nino/auth/user'] === $was['/nino/auth/user'] && $quiet === true
	&& in_array( $B, $first['/nino/auth/roles']['panel']['perms'], true ) === true && in_array( $B, $first['/nino/auth/roles']['dev']['perms'], true ) === true && $config() === $hash );

ninoUser( $site, 'old@example.test', [ '/_admin/builder/manage', '/_admin/builder/develop/edit' ] );

check( 'the account of the development that nobody upgraded still designs - the step is gone and what it was is no more than the design here - and has no right of the editor of the blocks it was not given',
	\Nino\Modules\Builder::level( $site ) === \Nino\Modules\Builder::LEVEL_DESIGN && \Nino\Auth::checkPermission( $site, \Nino\Modules\Themelab::PERM ) === false );


ninoDone( $site );
