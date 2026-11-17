<?php
declare(strict_types=1);

// The test of the feature Copy: structure and behaviour, one pin each. php features/Copy/tests/copy-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel', is_array( $manifest ) === true && $manifest['key'] === 'copy' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true );

$appData = ninoSandbox( 'copy' );
$appData['./nino/filesystem/path'] = $root;
$appData['/nino/modules'] = [ '\\Nino\\Modules\\Basis', '\\Nino\\Modules\\Copy' ];
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Html::addFills( $appData, [ '/template/page-home/bank/iban' => 'DE02 1203', '/feature/copy/button/label' => 'Copy', '/feature/copy/button/success' => 'Copied', '/feature/copy/button/error' => 'Press Ctrl+C' ], '*' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );

check( 'the folders are read without a warning', ninoWarnings() === [] && \Nino\Features::componentProblems( $appData, 'copy' ) === [] );
$schema = \Nino\Components::components( $appData )['copy'] ?? [];
check( 'the component is registered in its group, with a label in both languages, an icon of the table and a standard part',
	( $schema['group'] ?? '' ) === 'copy' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true && in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true
	&& ( \Nino\Components::groups( $appData )['copy']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === [] );
check( 'the standard part keeps the rule of mixing', \Nino\Components::partProblems( (string) file_get_contents( $dir. '/groups/copy/part/part.css' ), [ 'copy' ] ) === [] );
check( 'a tool offers it in the flow of a column', in_array( 'copy', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true );

$html = $draw( '[section-wrap id="bank"][section-row][section-col][copy ~iban copied="DE021203" name="IBAN"][/section-col][/section-row][/section-wrap]' );
check( 'it draws the text of the key, the button hidden, what is copied and what it is named',
	str_contains( $html, '<span class="copy-text">DE02 1203</span>' ) === true && str_contains( $html, 'class="copy-button" hidden data-copy-value="DE021203" data-copy-named="IBAN"' ) === true );
check( 'the words of the button are filled', str_contains( $html, 'data-copy-done="Copied"' ) === true && str_contains( $html, '[[' ) === false );
check( 'block is a class of the register', str_contains( $draw( '[copy text="a" block]' ), 'class="copy copy--block"' ) === true );
$hostile = $draw( '[copy text="[csrf] <b>x</b>" copied="\"><script>alert(1)</script> [template /templates/evil]" name="[[/project/company/general/name]]"]' );
check( 'a text, a value and a name that spell a call or a tag are text', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $hostile, '<b>' ) === false );
check( 'a value is escaped once', str_contains( $draw( '[copy text="a & b" copied="x & y"]' ), 'data-copy-value="x &amp; y"' ) === true && str_contains( $draw( '[copy text="a & b"]' ), '>a &amp; b<' ) === true );
check( 'a source that is none draws nothing', $draw( '[copy ~nobody]' ) === '' && $draw( '[copy text=""]' ) === '' );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the component', ( $groups['copy']['demo'] ?? '' ) !== '' && substr_count( $draw( $groups['copy']['demo'] ), 'class="copy-button"' ) === 2 );
$block = \Nino\Blocks::get( $appData, 'copy-line' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'copy', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

$bundle = \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' );
check( 'the part is in the style bundle, in the layer of the components', str_contains( $bundle, '.copy-button' ) === true );

$off = ninoSandbox( 'copy-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[copy text="a"]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-copy|--color-|--space-[0-9]|--radius-|--fontsize-/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] );

// The script that makes the button work runs over the kernel's stand-in of a page (copy-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: copy-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/copy-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'copy-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
