<?php
declare(strict_types=1);

// The test of the feature Typewriter: structure and behaviour, one pin each. php features/Typewriter/tests/typewriter-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no setting and no data', is_array( $manifest ) === true && $manifest['key'] === 'typewriter' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['settings'] === [] && $manifest['data'] === [] );
check( 'the script is in the bundle and the look is the part', $manifest['assets'] === [ '/features/Typewriter/assets/typewriter.js' ] );

$appData = ninoSandbox( 'typewriter' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'typewriter' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
\Nino\Html::addFills( $appData, [ '/feature/typewriter/pause/label' => 'Pause "all" <b>', '/template/page-home/hero/lines' => "First line\n\n  Second <strong>line</strong> &lt;b&gt;x&lt;/b&gt;\r\nThird" ], '*' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'typewriter' ) === [] );
$schema = \Nino\Components::components( $appData )['typewriter'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table, every attribute labelled and a standard part', ( $schema['group'] ?? '' ) === 'typewriter' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['typewriter']['part'] ?? false ) === true );
$part = (string) file_get_contents( $dir. '/groups/typewriter/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'typewriter' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.typewriter-cursor' ) === true );
check( 'it is placed in the flow, so that a tool offers it in a column; its element is one of a fixed list and never free',
	( $schema['in'] ?? [] ) === [ 'flow' ] && in_array( 'typewriter', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& ( $schema['attributes']['tag']['options'] ?? [] ) === [ 'p', 'h1', 'h2', 'h3', 'h4', 'span' ] );

$html = $draw( '[typewriter tag="h2" text="One<br>Two"]' );
check( 'it draws the container as the element asked for, with the defaults as data the script reads and the label of the pause button, and the lines in it',
	str_starts_with( $html, '<h2 class="typewriter" data-typewriter-speed="45" data-typewriter-hold="1600" data-typewriter-loop="0" data-typewriter-label="' ) === true && str_ends_with( $html, '</h2>' ) === true
	&& str_contains( $html, '[[' ) === false );
$lines = $draw( '[section-wrap id="hero"][section-row][section-col][typewriter ~lines][/section-col][/section-row][/section-wrap]' );
check( 'a line of the text is a line of the typewriter, an empty one is none, a line is text: a tag of the editor is taken away and one the text spells stays text',
	substr_count( $lines, '<span class="typewriter-line">' ) === 3
	&& str_contains( $lines, '<span class="typewriter-line">First line</span><span class="typewriter-line">Second line &lt;b&gt;x&lt;/b&gt;</span><span class="typewriter-line">Third</span>' ) === true
	&& str_contains( $lines, '<strong>' ) === false && str_contains( $lines, '<b>' ) === false );
check( 'the element, speed, hold and loop are what the call says, and what is outside its bounds or list is held to them',
	str_contains( $draw( '[typewriter text="a" tag="h3" speed="60" hold="0" loop]' ), '<h3 class="typewriter" data-typewriter-speed="60" data-typewriter-hold="0" data-typewriter-loop="1"' ) === true
	&& str_contains( $draw( '[typewriter text="a" speed="99999" hold="-4"]' ), 'data-typewriter-speed="1000" data-typewriter-hold="0"' ) === true
	&& str_starts_with( $draw( '[typewriter text="a" tag="script"]' ), '<p class="typewriter"' ) === true && str_starts_with( $draw( '[typewriter text="a" tag="div onclick=x"]' ), '<p class="typewriter"' ) === true );
$hostile = $draw( '[typewriter tag="h2\"><script>alert(1)</script>" speed="[csrf]" text="[csrf] <script>alert(1)</script> &lt;script&gt;alert(2)&lt;/script&gt; [template /templates/evil]"]' );
check( 'what a call writes stays text: no tag, no call, and the label of the pause button, which is a word of the project, cannot leave its attribute',
	str_contains( $hostile, '<script' ) === false && str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $hostile, 'evil' ) === true && str_contains( $hostile, '&lt;script&gt;alert(2)' ) === true && substr_count( $hostile, '<p class="typewriter"' ) === 1
	&& str_contains( $html, 'data-typewriter-label="Pause &quot;all&quot; &lt;b&gt;"' ) === true );
check( 'a text without a line draws nothing', $draw( '[typewriter text=""]' ) === '' && $draw( '[typewriter ~nobody]' ) === '' && $draw( '[typewriter text="  "]' ) === '' );

// Where the script does not run, and for a visitor who asked for less motion, the lines stand below one another, readable
check( 'every rule that hides, stacks or fades a line is bound to the class the script writes, so that without it the lines stand below one another',
	preg_match_all( '/^[^\n{]*opacity: 0;|grid-area/m', $part ) >= 1 && preg_match( '/^\.typewriter-line \{ display: block; \}$/m', $part ) === 1
	&& preg_match_all( '/^\.typewriter[^{]*> \.typewriter-line[^{]*\{[^}]*(opacity|grid-area)/m', $part, $stacked ) >= 1 && array_filter( $stacked[0], static fn( string $rule ): bool => str_contains( $rule, '.typewriter-is-running' ) === false ) === [] );
check( 'the part stops the fade and the blink for a visitor who asked for less motion, keeps the cursor from taking room and the button a target of 24 pixels that says its state in more than a colour',
	preg_match( '/@media \( prefers-reduced-motion: reduce \)\s*\{[^}]*transition: none;[^}]*\}\s*\.typewriter-cursor \{ animation: none; \}/', $part ) === 1
	&& preg_match( '/\.typewriter-cursor \{[^}]*width: 0;[^}]*margin-right: calc\( var\(--typewriter-cursor-width\) \* -1 \)/', $part ) === 1
	&& preg_match( '/\.typewriter-toggle \{[^}]*min-width: 24px; min-height: 24px;/', $part ) === 1 && preg_match( '/\.typewriter-toggle\[aria-pressed="true"\] \{[^}]*box-shadow: inset/', $part ) === 1 );

// The script: lines are written as text, and the line is told to assistive technology once
$script = (string) file_get_contents( $dir. '/assets/typewriter.js' );
check( 'the script writes the lines as text and never as markup, hides what it splits a line into from assistive technology and leaves the line once in a reader, and asks nothing of the vocabulary of Nino 1',
	preg_match( '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|createContextualFragment|DOMParser|\.append\(|Nino\.ui\./', $script ) === 0 && preg_match_all( '/\.textContent = /', $script ) >= 4
	&& preg_match( '/written\.setAttribute\( \'aria-hidden\', \'true\' \)/', $script ) === 1 && preg_match( '/rest\.setAttribute\( \'aria-hidden\', \'true\' \)/', $script ) === 1 && preg_match( '/reader\.textContent = source/', $script ) === 1
	&& preg_match( '/reader\.className = \'typewriter-reader\'/', $script ) === 1 && preg_match( '/cursor\.setAttribute\( \'aria-hidden\', \'true\' \)/', $script ) === 1 );
check( 'the script does not run at all for a visitor who asked for less motion', preg_match( '/function init\(\) \{[^}]*reducedMotion\(\) === true \)\s*return;/s', $script ) === 1 );

$groups = \Nino\Components::groups( $appData );
$demo = $draw( $groups['typewriter']['demo'] ?? '' );
check( 'the demo of the group is read without a note and draws the typewriter with two lines', ( $groups['typewriter']['demo'] ?? '' ) !== '' && substr_count( $demo, '<span class="typewriter-line">' ) === 2 && str_contains( $demo, 'data-typewriter-loop="1"' ) === true );
$block = \Nino\Blocks::get( $appData, 'typewriter-headline' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature, and draws its three lines', is_array( $block ) === true && in_array( 'typewriter', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === []
	&& substr_count( $draw( str_replace( '~lines', 'text="'. str_replace( "\n", '&#10;', (string) ( $block['texts']['en_US']['lines'] ?? '' ) ). '"', (string) ( $block['source'] ?? '' ) ) ), '<span class="typewriter-line">' ) === 3 );

// Words: the same keys in both languages, the one the template reads among them
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
preg_match_all( '/\[\[(\/feature\/typewriter\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. '/components/typewriter/template.tpl' ), $read );
check( 'both languages bring the same one word, and it is the one the template reads', $words['de_DE'] === $words['en_US'] && $words['en_US'] === [ '[[/feature/typewriter/pause/label]]' ] && array_unique( $read[0] ) === $words['en_US'] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'typewriter-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[typewriter text="a"]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-typewriter|nino-is-|nino-btn|nino-sr-only|--color-|--space-[0-9]|--radius\b|--fontsize-|--text-[0-9]|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/templates' ) === false && is_dir( $dir. '/design' ) === false );

// The script runs over the kernel's stand-in of a page (typewriter-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: typewriter-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/typewriter-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'typewriter-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
