<?php
declare(strict_types=1);

// The test of the feature Modeswitch: structure and behaviour, one pin each. php features/Modeswitch/tests/modeswitch-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no setting and no data', is_array( $manifest ) === true && $manifest['key'] === 'modeswitch' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'the script is in the bundle, the look is the part, and the early script is no asset of its own', $manifest['assets'] === [ '/features/Modeswitch/assets/modeswitch.js' ] );

/**
 *	A project with Basis and the feature switched on, the register read as \Nino\init() reads it
 *
 *	@return 	array										The project's app data
 */
function modeSite( string $name ): array {

	global $root;

	$appData = ninoSandbox( $name );
	$appData['./nino/filesystem/path'] = $root;
	check( 'Basis and the feature are switched on in '. $name, \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'modeswitch' ) === true );
	$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );
	ninoWarnings();

	return $appData;
}

$appData = modeSite( 'modeswitch' );
\Nino\Html::addFills( $appData, [ '/feature/modeswitch/switch/label' => 'Appearance', '/feature/modeswitch/mode/light' => 'Light', '/feature/modeswitch/mode/system' => 'System', '/feature/modeswitch/mode/dark' => 'Dark' ], '*' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'modeswitch' ) === [] );
$schema = \Nino\Components::components( $appData )['mode-switch'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table, every attribute labelled and a standard part', ( $schema['group'] ?? '' ) === 'mode' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['mode']['part'] ?? false ) === true );
$part = (string) file_get_contents( $dir. '/groups/mode/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'mode-switch' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.mode-switch-button' ) === true );
check( 'the schema places it in the flow, in the header actions and in a footer column, and a tool offers it in each of them', array_diff( [ 'flow', 'header-actions', 'footer-col' ], (array) ( $schema['in'] ?? [] ) ) === []
	&& array_filter( [ 'section-col', 'header-actions', 'footer-col' ], static fn( string $place ): bool => in_array( 'mode-switch', \Nino\Components::allowed( $appData, $place ), true ) === false ) === [] );

$html = $draw( '[mode-switch]' );
check( 'it is a group of three buttons - light, system, dark - hidden until the script has seen that the brand leaves the mode open, pressed on nothing: which is on is the browser\'s to say',
	str_starts_with( $html, '<div class="mode-switch" hidden role="group" aria-label="Appearance">' ) === true && preg_match( '/data-mode="light".*data-mode="system".*data-mode="dark"/s', $html ) === 1
	&& substr_count( $html, 'aria-pressed="false"' ) === 3 && substr_count( $html, 'aria-pressed="true"' ) === 0 && str_contains( $html, '[[' ) === false );
check( 'the words are the words of the project, on the button and as its title; a button without the words in the markup would be unreadable to a screen reader',
	str_contains( $html, 'title="Dark"' ) === true && str_contains( $html, '<span class="mode-switch-name">Dark</span>' ) === true );
check( 'icons is a class of the register, and the words stay in the markup', str_contains( $draw( '[mode-switch icons]' ), 'class="mode-switch mode-switch--icons"' ) === true && str_contains( $draw( '[mode-switch icons]' ), 'mode-switch-name' ) === true
	&& preg_match( '/\.mode-switch--icons \.mode-switch-name[^}]*clip/', $part ) === 1 );
$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the switch twice', ( $groups['mode']['demo'] ?? '' ) !== '' && substr_count( $draw( $groups['mode']['demo'] ), 'role="group"' ) === 2 );

// The output phase: the early script goes into the head, once, of the pages of the site and nowhere else
$nonce = 'abc123';
$appData['./nino/jstext/nonce'] = $nonce;
$page = static fn( string $body, string $uri = '/', string $type = 'text/html; charset=UTF-8' ): array => [ '/nino/http/request' => [ 'uri' => $uri ], '/nino/http/response' => [ 'uri' => $uri, 'body' => $body, 'header' => [ 'Content-Type' => $type ] ] ];
$run = static function( array $request ) use ( &$appData ): string {
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );
	return is_string( $request['/nino/http/response']['body'] ) === true ? $request['/nino/http/response']['body'] : '';
};
$document = '<html><head><title>x</title></head><body><main></main></body></html>';
$out = $run( $page( $document ) );
$code = trim( (string) file_get_contents( $dir. '/assets/mode-early.js' ) );
check( 'a page of the site gets the early script once, last in its head, under the nonce of the page, with the code of the file', substr_count( $out, '<script nonce="abc123">' ) === 1 && str_contains( $out, '<script nonce="abc123">'. $code. '</script>'. "\n". '</head>' ) === true && $code !== '' );
check( 'the workbench, a response that is no page, a page with no head, a draft that is previewed and a page that has no nonce get none',
	$run( $page( $document, '/_admin/' ) ) === $document && $run( $page( $document, '/_admin' ) ) === $document && $run( $page( $document, '/x', 'application/json' ) ) === $document
	&& $run( $page( '<body>x</body>' ) ) === '<body>x</body>' && $run( $page( '' ) ) === '' && ( static function() use ( &$appData, $page, $run, $document ): bool {
		$appData['./nino/preview/held'] = [ 'kind' => 'themes', 'token' => 'probe' ];
		$previewed = $run( $page( $document ) ) === $document;
		unset( $appData['./nino/preview/held'] );
		$appData['./nino/jstext/nonce'] = '';
		$none = $run( $page( $document ) ) === $document;
		$appData['./nino/jstext/nonce'] = 'abc123';
		return $previewed && $none;
	} )() );
check( 'a page that merely starts with the same letters as the workbench is a page of the site', str_contains( $run( $page( $document, '/_adminx' ) ), '<script nonce' ) === true );

// The two scripts make one decision: the same key in the browser's storage, the same token on the root element, the same brand that leaves the mode open
$bundle = (string) file_get_contents( $dir. '/assets/modeswitch.js' );
check( 'the early script and the script of the bundle read the same key, set the same token and ask the same of the brand', array_filter( [ "'nino-mode'", "'--brand-scheme'", "'light dark'" ], static fn( string $word ): bool => str_contains( $code, $word ) === false || str_contains( $bundle, $word ) === false ) === []
	&& preg_match( '/<\/script|<!--/i', $code ) === 0 );

// Words: the same keys in both languages, every one the template reads among them
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
preg_match_all( '/\[\[(\/feature\/modeswitch\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. '/components/mode-switch/template.tpl' ), $read );
check( 'both languages bring the same four words, and every word the template reads is among them', $words['de_DE'] === $words['en_US'] && count( $words['en_US'] ) === 4 && array_diff( array_map( static fn( string $key ): string => '[['. $key. ']]', array_unique( $read[1] ) ), $words['en_US'] ) === [] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'modeswitch-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[mode-switch]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-modeswitch|nino-sr-only|data-nino-mode|--color-|--space-[0-9]|--radius\b|--fontsize-/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/templates' ) === false );

// The scripts run over the kernel's stand-in of a page (modeswitch-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: modeswitch-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/modeswitch-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'modeswitch-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
