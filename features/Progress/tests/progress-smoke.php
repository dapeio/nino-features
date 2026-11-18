<?php
declare(strict_types=1);

// The test of the feature Progress: structure and behaviour, one pin each. php features/Progress/tests/progress-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no setting and no data', is_array( $manifest ) === true && $manifest['key'] === 'progress' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'the script is in the bundle and the look is the part', $manifest['assets'] === [ '/features/Progress/assets/progress.js' ] );

$appData = ninoSandbox( 'progress' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'progress' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Html::addFills( $appData, [ '/template/page-home/read/name' => 'Reading "progress" <b>' ], '*' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'progress' ) === [] );
$schema = \Nino\Components::components( $appData )['progress'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table, every attribute labelled and a standard part', ( $schema['group'] ?? '' ) === 'progress' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['progress']['part'] ?? false ) === true );
$part = (string) file_get_contents( $dir. '/groups/progress/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'progress' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.progress-is-ready' ) === true );
check( 'the schema places it in the page and the flow, a tool offers it in a column, and the group has the one knob, the height',
	( $schema['in'] ?? [] ) === [ 'page', 'flow' ] && in_array( 'progress', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true && array_keys( \Nino\Components::groups( $appData )['progress']['knobs'] ?? [] ) === [ 'progress.height' ] );
check( 'the part shows nothing until the script has measured, so that a page without JavaScript has no stripe across it', preg_match( '/\.progress:not\(\.progress-is-ready\)\s*\{\s*display:\s*none/', $part ) === 1 );

check( 'it draws an empty element for the script, measuring #article where the call says nothing, and without a name',
	$draw( '[progress]' ) === '<div class="progress" data-progress-of="#article" data-progress-label=""></div>' );
check( 'of and label are what the call says; an empty of measures the page, and the label is a reference or the text itself',
	$draw( '[progress of="" label="Reading"]' ) === '<div class="progress" data-progress-of="" data-progress-label="Reading"></div>'
	&& str_contains( $draw( '[section-wrap id="read"][progress of=".article" label="~name"][/section-wrap]' ), 'data-progress-of=".article" data-progress-label="Reading &quot;progress&quot; &lt;b&gt;"' ) === true );
$hostile = $draw( '[progress of="\"><script>alert(1)</script>[csrf]" label="[csrf] <b>x</b> [[/project/company/general/name]]"]' );
check( 'a selector and a label that spell a tag, a call or a key stay text and cannot leave their attributes',
	str_contains( $hostile, '<script' ) === false && str_contains( $hostile, '<b>' ) === false && str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $hostile, '[[/project' ) === false
	&& substr_count( $hostile, '<div ' ) === 1 && str_ends_with( $hostile, '"></div>' ) === true );

// The script reads the selector with querySelector, and the label only as an attribute
$script = (string) file_get_contents( $dir. '/assets/progress.js' );
check( 'the script writes no markup, takes the selector only to querySelector inside a try and sets the label as an attribute', preg_match( '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|eval\(|Nino\.ui\./', $script ) === 0
	&& preg_match( '/try \{ subject = document\.querySelector\( of \) \}/', $script ) === 1 && str_contains( $script, "setAttribute( 'aria-label', label )" ) === true );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the bar', ( $groups['progress']['demo'] ?? '' ) !== '' && str_contains( $draw( $groups['progress']['demo'] ), 'class="progress"' ) === true );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'progress-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[progress]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-progress|nino-is-ready|--color-|--space-[0-9]|--radius\b|--fontsize-|--text-[0-9]|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/templates' ) === false );

// The script runs over the kernel's stand-in of a page (progress-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: progress-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/progress-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'progress-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
