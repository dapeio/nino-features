<?php
declare(strict_types=1);

// The test of the feature Ticker: structure and behaviour, one pin each. php features/Ticker/tests/ticker-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no setting and no data', is_array( $manifest ) === true && $manifest['key'] === 'ticker' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'the script is in the bundle and the look is the part', $manifest['assets'] === [ '/features/Ticker/assets/ticker.js' ] );

$appData = ninoSandbox( 'ticker' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'ticker' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Html::addFills( $appData, [ '/feature/ticker/pause/label' => 'Pause "all" <b>' ], '*' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'ticker' ) === [] );
$schema = \Nino\Components::components( $appData )['ticker'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table, every attribute labelled and a standard part', ( $schema['group'] ?? '' ) === 'ticker' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['ticker']['part'] ?? false ) === true );
$part = (string) file_get_contents( $dir. '/groups/ticker/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'ticker' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.ticker-track' ) === true );
check( 'it is a container: placed in the page and the flow, offering the flow to what it holds, so that a tool offers it in a column and a text in it',
	( $schema['in'] ?? [] ) === [ 'page', 'flow' ] && ( $schema['offers'] ?? [] ) === [ 'flow' ] && in_array( 'ticker', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& in_array( 'text', \Nino\Components::allowed( $appData, 'ticker' ), true ) === true );

$html = $draw( '[ticker][text text="a" format="inline"][/ticker]' );
check( 'it draws the row and the track round what it holds, with the defaults as data the script reads and the label of the pause button',
	str_starts_with( $html, '<div class="ticker" data-ticker-speed="40" data-ticker-direction="left" data-ticker-loop="0" data-ticker-toggle="0" data-ticker-pause="1" data-ticker-label="' ) === true
	&& str_contains( $html, '<div class="ticker-track"><div class="text">a</div></div></div>' ) === true && str_contains( $html, '[[' ) === false );
check( 'speed, direction, loop, the pause button and the pointer are what the call says',
	str_contains( $draw( '[ticker speed="60" direction="right" loop toggle pause="0"][text text="a"][/ticker]' ), 'data-ticker-speed="60" data-ticker-direction="right" data-ticker-loop="1" data-ticker-toggle="1" data-ticker-pause="0"' ) === true );
check( 'a speed outside its bounds and a direction outside its options are held to them', str_contains( $draw( '[ticker speed="99999" direction="up"][/ticker]' ), 'data-ticker-speed="1000" data-ticker-direction="left"' ) === true
	&& str_contains( $draw( '[ticker speed="0"][/ticker]' ), 'data-ticker-speed="1"' ) === true );
$hostile = $draw( '[ticker speed="[csrf]" direction="\"><script>alert(1)</script>"][text text="[csrf] <script>alert(1)</script>"][/ticker]' );
check( 'what a call writes stays text, and the label of the pause button, which is a word of the project, cannot leave its attribute',
	str_contains( $hostile, '<script' ) === false && str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $html, 'data-ticker-label="Pause &quot;all&quot; &lt;b&gt;"' ) === true && substr_count( $hostile, '<div class="ticker"' ) === 1 );

// Less motion, and the row of a page whose script does not run
check( 'the part holds the row still for a visitor who asked for less motion, lets a row without the script scroll, and keeps the animation in its own keyframes',
	preg_match( '/@media \( prefers-reduced-motion: reduce \)\s*\{[^}]*\.ticker \{ overflow-x: auto; \}\s*\.ticker\.ticker-is-running \.ticker-track \{ animation: none; \}/', $part ) === 1
	&& preg_match( '/^\.ticker \{ overflow-x: auto;/m', $part ) === 1 && str_contains( $part, '@keyframes ticker-run' ) === true && preg_match( '/\.ticker\.ticker-is-running \{ overflow: hidden; \}/', $part ) === 1 );
check( 'the pause button is a target of at least 24 pixels and says its pressed state in more than a colour',
	preg_match( '/\.ticker-toggle \{[^}]*min-width: 24px; min-height: 24px;/', $part ) === 1 && preg_match( '/\.ticker-toggle\[aria-pressed="true"\] \{[^}]*box-shadow: inset/', $part ) === 1 );
check( 'the row stops under the pointer and under the focus, and runs on under the pointer only where the call says so',
	str_contains( $part, '.ticker.ticker-is-running:focus-within .ticker-track' ) === true && str_contains( $part, '.ticker[data-ticker-pause="0"]' ) === true );

// The script: the copies are hidden and cannot take the focus, and it writes no markup
$script = (string) file_get_contents( $dir. '/assets/ticker.js' );
check( 'the script hides the copies from assistive technology, makes them inert and takes their stops out of the tab order; it writes no markup and asks nothing of the vocabulary of Nino 1',
	preg_match( '/setAttribute\( \'aria-hidden\', \'true\' \)/', $script ) === 1 && preg_match( '/setAttribute\( \'inert\', \'\' \)/', $script ) === 1 && preg_match( '/setAttribute\( \'tabindex\', \'-1\' \)/', $script ) === 1
	&& preg_match( '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|Nino\.ui\./', $script ) === 0 && str_contains( $script, 'textContent = entry.label' ) === true );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws a row of three items with the pause button asked for', ( $groups['ticker']['demo'] ?? '' ) !== '' && substr_count( $draw( $groups['ticker']['demo'] ), '<div class="text">' ) === 3 && str_contains( $draw( $groups['ticker']['demo'] ), 'data-ticker-toggle="1"' ) === true );
$block = \Nino\Blocks::get( $appData, 'ticker-line' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'ticker', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

// Words: the same keys in both languages, the one the template reads among them
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
preg_match_all( '/\[\[(\/feature\/ticker\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. '/components/ticker/template.tpl' ), $read );
check( 'both languages bring the same one word, and it is the one the template reads', $words['de_DE'] === $words['en_US'] && $words['en_US'] === [ '[[/feature/ticker/pause/label]]' ] && array_unique( $read[0] ) === $words['en_US'] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'ticker-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[ticker][text text="a"][/ticker]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-ticker|nino-is-|nino-btn|--color-|--space-[0-9]|--radius\b|--fontsize-|--text-[0-9]|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/templates' ) === false );

// The script runs over the kernel's stand-in of a page (ticker-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: ticker-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/ticker-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'ticker-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
