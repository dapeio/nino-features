<?php
declare(strict_types=1);

// The Hello feature's contract test. Runs against the checkout three levels up,
// or the one NINO_ROOT names
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'hello' );
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Components' ];
\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );

check( 'the manifest validates without a warning', is_array( \Nino\Features::manifest( dirname( __DIR__ ) ) ) && ninoWarnings() === [] );
check( 'activation lists the class, records the version and applies the unit', \Nino\Features::activate( $appData, 'hello' ) === true
	&& \Nino\Features::get( $appData, 'hello' )['installed'] === '1.1.0' && is_file( \Nino\Filesystem::path( $appData, '/templates/page-hello.tpl' ) ) === true );

\Nino\Html::init( $appData );
\Nino\Modules::callModules( $appData, 'init' );
check( 'the component is registered from the manifest and renders through the template', isset( \Nino\Modules\Components::components( $appData )['hello'] ) === true
	&& \Nino\Html::renderHtml( $appData, '[hello name="Ada" shout="yes" class="wide"]' ) === '<p class="nino-hello wide">HELLO, ADA!</p>' );

ninoUser( $appData, 'dev@example.com' );
[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'name' => 'Grace' ], 'hello/save' );
check( 'hello/save stores the name and hello/list answers it', $status === 200 && $body['name'] === 'Grace'
	&& ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [], 'hello/list' )[1] === [ 'name' => 'Grace', 'longest' => 40 ] );

[ $status, $body ] = ninoCall( $appData, [ \Nino\Admin\Admin::class, 'handlePost' ], [ 'name' => str_repeat( 'x', 41 ) ], 'hello/save' );
check( 'a name too long is refused with the code, its limit and the field', $status === 400 && $body['code'] === 'hello_long' && $body['params'] === [ 40 ] && $body['field'] === 'name' );
foreach( [ 'en_US', 'de_DE' ] as $locale )
	check( "...and the code has its sentence in $locale", str_contains( \Nino\Admin\Admin::textFills( $appData, '/features/Hello/text', $locale )['[[/_admin/error/'. $body['code']. ']]'] ?? '', '%s' ) );

// The project as 1.0.0 left it; all() keeps its answer for the request, so it is dropped to be asked again
\Nino\Filesystem::putFileContent( $appData, '/data/hello.php', [ 'name' => 'Ada' ] );
$appData['/nino/features']['hello']['version'] = '1.0.0';
\Nino\AppData::writeContentData( $appData, [ '/nino/features' ] );
unset( $appData['./nino/features/all'] );
check( 'a recorded version behind the manifest runs the upgrade hook, which adds the format key', \Nino\Features::activate( $appData, 'hello' ) === true
	&& \Nino\Filesystem::getFileContent( $appData, '/data/hello.php', [] ) === [ 'format' => 1, 'name' => 'Ada' ] );
check( 'deactivation takes the panel away and keeps the data', \Nino\Features::deactivate( $appData, 'hello' ) === true
	&& isset( \Nino\Admin\Admin::panels( $appData )['hello'] ) === false && \Nino\Modules\Hello::name( $appData ) === 'Ada' );

// The panel's script, where node is on the path: hello-js-smoke.js beside this
// file runs it over the kernel's tests/harness.js, so bin/check.sh and CI cover
// both halves in one go
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';

if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: hello-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/hello-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'hello-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
