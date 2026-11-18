<?php
declare(strict_types=1);

// The test of the feature Toc: structure and behaviour, one pin each. php features/Toc/tests/toc-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form and no data', is_array( $manifest ) === true && $manifest['key'] === 'toc' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['data'] === [] );
check( 'the one setting is the switch of the anchors, on; the script is in the bundle and the look is the part', array_keys( $manifest['settings'] ) === [ 'anchors' ] && $manifest['settings']['anchors']['type'] === 'bool' && $manifest['settings']['anchors']['default'] === true
	&& $manifest['assets'] === [ '/features/Toc/assets/toc.js' ] );

/**
 *	A project with Basis and the feature switched on, the register read as \Nino\init() reads it
 *
 *	@param		string		$name					The sandbox
 *	@param		array			$settings			What the settings panel would save
 *
 *	@return 	array										The project's app data
 */
function tocSite( string $name, array $settings = [] ): array {

	global $root;

	$appData = ninoSandbox( $name );
	$appData['./nino/filesystem/path'] = $root;
	check( 'Basis and the feature are switched on in '. $name, \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'toc' ) === true
		&& ( $settings === [] || \Nino\Features::saveSettings( $appData, 'toc', $settings ) === [] ) );
	$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
	\Nino\Html::init( $appData );
	\Nino\Features::registerComponents( $appData );
	\Nino\Modules::callModules( $appData, 'init' );
	\Nino\Features::registerParts( $appData );
	\Nino\Html::addFills( $appData, [ '/feature/toc/list/title' => 'On this page', '/feature/toc/anchor/label' => 'Link to "this" <section>' ], '*' );
	ninoWarnings();

	return $appData;
}

$appData = tocSite( 'toc' );
$draw = static fn( string $source ): string => trim( \Nino\Html::renderSource( $appData, $source, [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'toc' ) === [] );
$schema = \Nino\Components::components( $appData )['toc'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table, every attribute labelled and a standard part', ( $schema['group'] ?? '' ) === 'toc' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( \Nino\Components::groups( $appData )['toc']['part'] ?? false ) === true );
$part = (string) file_get_contents( $dir. '/groups/toc/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'toc' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.toc-link' ) === true );
check( 'a tool offers it in a column; it has the variants numbered and box and two knobs of the group', in_array( 'toc', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true
	&& array_keys( \Nino\Components::variants( $appData, 'toc' )['variants'] ) === [ 'regular', 'numbered', 'box' ] && array_keys( \Nino\Components::groups( $appData )['toc']['knobs'] ?? [] ) === [ 'toc.indent', 'toc.gap' ] );

$html = $draw( '[toc]' );
check( 'it draws a nav that is hidden, named by its title, with an empty list for the script to fill and the data the script reads',
	str_starts_with( $html, '<nav class="toc" data-toc-levels="2,3" data-toc-within="" data-toc-anchors="1"' ) === true && str_contains( $html, 'aria-labelledby="toc-title" hidden>' ) === true
	&& str_contains( $html, '<h2 class="toc-title" id="toc-title">On this page</h2><ol class="toc-list"></ol></nav>' ) === true && str_contains( $html, '[[' ) === false );
check( 'without JavaScript nothing is drawn: the nav is written hidden and the part keeps it so', str_contains( $html, ' hidden>' ) === true && preg_match( '/\.toc\[hidden\]\s*\{\s*display:\s*none/', $part ) === 1 );
$shaped = $draw( '[toc levels="3" within="#article" title="Mine" sticky variant="numbered"]' );
check( 'levels, the element it is taken from and the title are what the call says, and sticky and the variant are classes of the register',
	str_contains( $shaped, 'class="toc toc--sticky toc--variant-numbered" data-toc-levels="3" data-toc-within="#article"' ) === true && str_contains( $shaped, '>Mine</h2>' ) === true
	&& str_contains( $draw( '[toc variant="box"]' ), 'class="toc toc--variant-box"' ) === true );
check( 'levels outside the options and an empty title are the default and the word of the project', str_contains( $draw( '[toc levels="2,3,4,5" title=""]' ), 'data-toc-levels="2,3"' ) === true && str_contains( $draw( '[toc title=""]' ), '>On this page</h2>' ) === true );

// What lands in the markup is text: the selector, the title and the word of the anchor
$hostile = $draw( '[toc within="\"><script>alert(1)</script>[csrf]" title="[csrf] <b>x</b> [[/project/company/general/name]]"]' );
check( 'a selector and a title that spell a tag, a call or a key stay text, and the word of the anchor cannot leave its attribute',
	str_contains( $hostile, '<script' ) === false && str_contains( $hostile, '<b>' ) === false && str_contains( $hostile, 'name="csrf"' ) === false && str_contains( $hostile, '[[/project' ) === false
	&& str_contains( $hostile, 'data-toc-label="Link to &quot;this&quot; &lt;section&gt;"' ) === true && substr_count( $hostile, '<nav ' ) === 1 );

$anchorsOff = tocSite( 'toc-anchors-off', [ 'anchors' => 'false' ] );
check( 'the setting anchors is what data-toc-anchors says', str_contains( $html, 'data-toc-anchors="1"' ) === true && str_contains( trim( \Nino\Html::renderSource( $anchorsOff, '[toc]', [ 'template' => 'page-home' ] + \Nino\Html::context( $anchorsOff ) ) ), 'data-toc-anchors="0"' ) === true );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the list with headings for it to list', ( $groups['toc']['demo'] ?? '' ) !== '' && str_contains( $draw( $groups['toc']['demo'] ), '<nav class="toc toc--sticky"' ) === true && substr_count( $draw( $groups['toc']['demo'] ), '<h' ) >= 4 );
$block = \Nino\Blocks::get( $appData, 'toc-article' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'toc', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );
check( 'the list of the block takes its headings from the column of the article, which has that id, as a block names its ids', str_contains( (string) file_get_contents( $dir. '/blocks/toc-article/block.tpl' ), 'within="#toc-article-text"' ) === true && str_contains( (string) file_get_contents( $dir. '/blocks/toc-article/block.tpl' ), 'id="toc-article-text"' ) === true );

// The script: it never writes markup, it reads what the page has
$script = (string) file_get_contents( $dir. '/assets/toc.js' );
check( 'the script sets words as text and writes no markup, and it asks nothing of the vocabulary of Nino 1', preg_match( '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|Nino\.ui\./', $script ) === 0 && str_contains( $script, 'textContent' ) === true );

// Words: the same keys in both languages, every one the template reads among them
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
preg_match_all( '/\[\[(\/feature\/toc\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. '/components/toc/template.tpl' ). (string) file_get_contents( $dir. '/Toc.php' ), $read );
check( 'both languages bring the same two words, and every word the component reads is among them', $words['de_DE'] === $words['en_US'] && count( $words['en_US'] ) === 2 && array_diff( array_map( static fn( string $key ): string => '[['. $key. ']]', array_unique( $read[1] ) ), $words['en_US'] ) === [] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'toc-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[toc]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-toc|--color-|--space-[0-9]|--radius\b|--fontsize-|--text-[0-9]|Nino\.ui\./', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1', $old === [] && is_dir( $dir. '/design' ) === false && is_dir( $dir. '/templates' ) === false );

// The script runs over the kernel's stand-in of a page (toc-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: toc-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/toc-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'toc-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
