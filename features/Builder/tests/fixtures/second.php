<?php
declare(strict_types=1);

/**
 *	A second process on a sandbox that a suite made: another request of the same project, as it would run beside the one the suite plays.
 *
 *	Usage: php second.php <sandbox> <what> [<argument> ...]
 *
 *	remove <template>									takes the page away, then writes <sandbox>/second.done
 *	foreign <template> <marker>				writes a word of another page and a line of the template, writes <marker>, then a route
 *	racer <start> <rounds> <who> <block>	makes /race-<n> as each round begins at the same moment, and prints what each round answered
 *
 *	The kernel is the one NINO_ROOT names, the features are the directory above the one this file stands in.
 */

$root = (string) getenv( 'NINO_ROOT' );
define( 'NINO_FEATURES_DIR', dirname( __DIR__, 3 ) );
require $root. '/tests/harness.php';

$sandbox = $argv[1];
$app = [ './nino/uid' => $sandbox ];
\Nino\AppData::prepare( $app );
$app['./nino/filesystem/path'] = $root;
$app['./nino/filesystem/configpath'] = $sandbox. '/private';
$app['./nino/filesystem/contentpath'] = $sandbox. '/private';
$app['./nino/filesystem/publicpath'] = $sandbox. '/public';
$app['./nino/locales/current'] = 'de_DE';
$app['/nino/locales/native'] = 'de_DE';
$app['/nino/locales/available'] = [ 'de_DE', 'en_US' ];
$app['/nino/dir'] = '';
$app['/nino/install/texts'] = \Nino\VERSION;
$app['./nino/test/sandbox'] = $sandbox;
$app['/nino/html/assets'] = [];
$app['/nino/html/navs'] = [ 'main' ];
$app['/nino/modules'] = [ '\\Nino\\Modules\\Assets', '\\Nino\\Modules\\Template', '\\Nino\\Modules\\Navigation', '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Collections', '\\Nino\\Modules\\Builder' ];

$load = static function() use ( &$app ): void {

	unset( $app['./nino/filesystem/cache'] );
	$config = \Nino\Filesystem::getFileContent( $app, '/config.php', [] );
	$app['/nino/http/routes'] = $config['/nino/http/routes'] ?? [];
	$app['/nino/html/images'] = $config['/nino/html/images'] ?? [];
};

$load();
\Nino\Html::init( $app );
\Nino\Features::registerComponents( $app );
\Nino\Modules::callModules( $app, 'init' );

if( $argv[2] === 'remove' ) {

	\Nino\Pages::remove( $app, $argv[3] );
	file_put_contents( $sandbox. '/second.done', 'removed' );
}
else if( $argv[2] === 'foreign' ) {

	// Another account writes a word of another page, another tab saves the template of this page and a route is written: three writers of three files
	\Nino\Filesystem::mutate( $app, '/text/en_US.php', static function( mixed $words ): array {
		$words = (array) $words;
		$words['[[/template/page-home/hero/title]]'] = 'Written by somebody else';
		return $words;
	}, [] );
	\Nino\Filesystem::mutate( $app, '/templates/'. $argv[3]. '.tpl', static fn( mixed $text ): string => (string) $text. "<!-- other tab -->\n", '' );
	file_put_contents( $argv[4], 'written' );
	\Nino\Filesystem::mutate( $app, '/config.php', static function( mixed $config ): array {
		$config = (array) $config;
		$config['/nino/http/routes']['GET://foreign'] = [ 'uri' => '/foreign', 'body' => '[template /templates/page-contact]' ];
		return $config;
	}, [] );
}
else if( $argv[2] === 'racer' ) {

	$answers = [];

	for( $round = 1; $round <= (int) $argv[4]; $round++ ) {

		// Each round begins at the moment the first process names: the two make the same page at the same time
		while( microtime( true ) < (float) $argv[3] + $round * 0.3 )
			usleep( 100 );

		$load();
		$made = \Nino\Modules\Builder\Pages::create( $app, [ 'names' => [ 'de_DE' => $argv[5]. $round, 'en_US' => $argv[5]. $round ], 'addresses' => [ 'de_DE' => '/race-'. $round, 'en_US' => '/race-'. $argv[5]. '-'. $round ], 'navs' => [], 'block' => $argv[6] ] );
		$answers[$round] = $made['template'] ?? ( $made['failure'][2] ?? '?' );
	}

	echo json_encode( $answers );
}
