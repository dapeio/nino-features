<?php
declare(strict_types=1);

// The test of the feature Compare: structure and behaviour, one pin each. php features/Compare/tests/compare-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no setting and no data', is_array( $manifest ) === true && $manifest['key'] === 'compare' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['settings'] === [] && $manifest['data'] === [] && $manifest['assets'] === [ '/features/Compare/assets/compare.js' ] );

$appData = ninoSandbox( 'compare' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'compare' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
// The two pictures of the page: slots of the project, the first with an alternative text stored
\Nino\Images::putSlot( $appData, '/template/page-home/work/before', [ 'label' => 'Before', 'width' => 800, 'height' => 600, 'filename' => 'elements/work/before.800x600.jpg' ] );
\Nino\Images::putSlot( $appData, '/template/page-home/work/after', [ 'label' => 'After', 'width' => 800, 'height' => 600, 'filename' => 'elements/work/after.800x600.jpg' ] );
\Nino\Images::setSlotAlt( $appData, '/template/page-home/work/before', [ 'de_DE' => 'Gespeichert', 'en_US' => 'Stored' ] );
// By reference: the page is drawn from the app data as it is when it is drawn
$draw = static function( string $source ) use ( &$appData ): string {
	return trim( \Nino\Html::renderSource( $appData, '[section-wrap id="work"][section-row][section-col]'. $source. '[/section-col][/section-row][/section-wrap]', [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
};
$ignored = ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'compare' ) === [] );
$schema = \Nino\Components::components( $appData )['compare'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table and a standard part', ( $schema['group'] ?? '' ) === 'compare' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && ( \Nino\Components::groups( $appData )['compare']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === [] );
check( 'a picture is a picture slot, never a file name: the two are of the type image, and the sides and the description are texts', ( $schema['attributes']['before']['type'] ?? '' ) === 'image' && ( $schema['attributes']['after']['type'] ?? '' ) === 'image'
	&& array_filter( [ 'alt', 'beforeLabel', 'afterLabel' ], static fn( string $name ): bool => ( $schema['attributes'][$name]['type'] ?? '' ) !== 'text' ) === [] );
$part = (string) file_get_contents( $dir. '/groups/compare/part/part.css' );
check( 'the standard part keeps the rule of mixing and is in the style bundle', \Nino\Components::partProblems( $part, [ 'compare' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.compare-range' ) === true );
check( 'a tool offers it in a column', in_array( 'compare', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true );

// Two pictures under one another: what the markup reads like, and the layout the script switches on
$html = $draw( '[compare before="~before" after="~after" alt="Two views" beforeLabel="Raw" afterLabel="Done" ratio="16-9" start="20"]' );
check( 'it draws both pictures from their slots, the first described and the second hidden from a screen reader, each with its side, under a figure whose class is the ratio',
	preg_match( '/<figure class="compare compare--ratio-16-9" style="--compare-position:20%">/', $html ) === 1 && preg_match( '/<img class="compare-image compare-image--before" src="[^"]*before\.800x600\.jpg" alt="Two views"/', $html ) === 1
	&& preg_match( '/<img class="compare-image compare-image--after" src="[^"]*after\.800x600\.jpg" alt="" [^>]*aria-hidden="true"/', $html ) === 1 && str_contains( $html, '>Raw</span>' ) === true && str_contains( $html, '>Done</span>' ) === true );
check( 'the divider is a range control with a name, hidden until the script has taken the pair over, at the start the call says', preg_match( '/<input class="compare-range" type="range" min="0" max="100" step="1" value="20" aria-label="[^"]+" hidden>/', $html ) === 1 );
$rules = [];
preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $part, $found, PREG_SET_ORDER );
foreach( $found as $rule )
	$rules[trim( $rule[1] )] = $rule[2];
$stacked = array_filter( $rules, static fn( string $body ): bool => preg_match( '/position:\s*absolute|clip-path|aspect-ratio:\s*var\(--compare-ratio\)/', $body ) === 1 );
check( 'without the script it stays two pictures: everything that lays them over one another or clips one belongs to a selector the script\'s class opens', $stacked !== [] && array_filter( array_keys( $stacked ), static fn( string $selector ): bool => str_contains( $selector, '.compare-is-ready' ) === false && str_contains( $selector, '.compare-range' ) === false ) === []
	&& isset( $rules['.compare .compare-range[hidden]'] ) === true );
$thumbs = array_filter( $rules, static fn( string $body, string $selector ): bool => str_contains( $selector, 'slider-thumb' ) === true || str_contains( $selector, 'range-thumb' ) === true, ARRAY_FILTER_USE_BOTH );
check( 'the grip of the divider is a target of at least 24 pixels, in both engines', count( array_filter( $thumbs, static fn( string $body ): bool => preg_match( '/width:\s*([0-9.]+)rem/', $body, $w ) === 1 && $w[1] * 16 >= 24 && preg_match( '/height:\s*([0-9.]+)rem/', $body, $h ) === 1 && $h[1] * 16 >= 24 ) ) === 2 );

// The start, the shape, the words
check( 'the start is held to 0 to 100 by the register and what is no number is the middle: nothing but digits reaches the style', str_contains( $draw( '[compare before="~before" after="~after" start="150"]' ), '--compare-position:100%' ) === true
	&& str_contains( $draw( '[compare before="~before" after="~after" start="-4"]' ), '--compare-position:0%' ) === true && str_contains( $draw( '[compare before="~before" after="~after" start="50;background:url(//evil.example)"]' ), 'style="--compare-position:50%"' ) === true );
check( 'the shape is 4:3 and writes no class unless one of the four is named', str_contains( $draw( '[compare before="~before" after="~after"]' ), 'class="compare"' ) === true && str_contains( $draw( '[compare before="~before" after="~after" ratio="1-1"]' ), 'class="compare compare--ratio-1-1"' ) === true
	&& str_contains( $draw( '[compare before="~before" after="~after" ratio="21-9"]' ), 'class="compare"' ) === true );
$none = static fn( string $source ): bool => str_contains( $draw( $source ), '<figure' ) === false;
check( 'half a pair draws nothing, and neither does a name that is no slot: a file name, a path that climbs, a slot with no file', $none( '[compare before="~before"]' ) === true && $none( '[compare after="~after"]' ) === true
	&& $none( '[compare before="photo.jpg" after="~after"]' ) === true && $none( '[compare before="~before" after="/x/../../etc/passwd"]' ) === true && $none( '[compare before="~before" after="~nobody"]' ) === true
	&& $none( '[compare before="~before" after="~after"]' ) === false );
$bare = $draw( '[compare before="~before" after="~after"]' );
check( 'a description left out is the alternative text stored with the first picture, in the language of the page, and the caption under the pair', preg_match( '/<img class="compare-image compare-image--before" src="[^"]*" alt="(Gespeichert|Stored)"/', $bare, $stored ) === 1
	&& str_contains( $bare, '<figcaption class="compare-caption">'. $stored[1]. '</figcaption>' ) === true );
check( 'the sides are the words of the project where the call names none, and the call\'s where it does', preg_match( '/compare-side--before">([^<]+)</', $bare, $one ) === 1 && $one[1] !== '' && str_contains( $one[1], '[[' ) === false
	&& str_contains( $draw( '[compare before="~before" after="~after" beforeLabel="Rohbau"]' ), 'compare-side--before">Rohbau<' ) === true );
$hostile = $draw( '[compare before="~before" after="~after" alt="&quot;><script>alert(1)</script> [csrf] & more" beforeLabel="<b>x</b> [template /templates/evil]" afterLabel="&quot; onload=&quot;alert(1)"]' );
check( 'a description and two names that spell a tag, a call or an attribute are text, escaped once', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, '<b>' ) === false && str_contains( $hostile, 'onload="' ) === false && str_contains( $hostile, 'name="csrf"' ) === false
	&& str_contains( $hostile, 'alt="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] &amp; more"' ) === true && str_contains( $hostile, '&lt;b&gt;x&lt;/b&gt; [template /templates/evil]' ) === true );

\Nino\Images::setSlotAlt( $appData, '/template/page-home/work/before', [ 'de_DE' => '"><img src=x onerror=alert(1)> [csrf]', 'en_US' => '"><img src=x onerror=alert(1)> [csrf]' ] );
$texts = [];
foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$texts[$locale] = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', [ '[[/feature/compare/side/before]]' => '<u>"x</u> [csrf]', '[[/feature/compare/side/after]]' => '"><s>y</s>' ] + $texts[$locale] );
}
$stored = $draw( '[compare before="~before" after="~after"]' );
check( 'the alternative text stored with a picture and the words of the project for the sides are text in the markup: they break out of no attribute and open no call', str_contains( $stored, '<img src=x' ) === false && str_contains( $stored, '<u>' ) === false && str_contains( $stored, '<s>' ) === false
	&& str_contains( $stored, 'alt="&quot;&gt;&lt;img src=x onerror=alert(1)&gt; [csrf]"' ) === true && str_contains( $stored, 'compare-side--before">&lt;u&gt;&quot;x&lt;/u&gt; [csrf]<' ) === true );
foreach( $texts as $locale => $content )
	\Nino\Filesystem::putFileContent( $appData, '/text/'. $locale. '.php', $content );

// Words: the same three keys in both languages, every one the code reads among them
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
check( 'both languages bring the same three words, and they are the ones the template and the renderer read', $words['de_DE'] === $words['en_US'] && array_diff( [ '[[/feature/compare/side/before]]', '[[/feature/compare/side/after]]', '[[/feature/compare/handle/label]]' ], $words['en_US'] ) === [] && count( $words['en_US'] ) === 3
	&& str_contains( (string) file_get_contents( $dir. '/components/compare/template.tpl' ), '[[/feature/compare/handle/label]]' ) === true );

$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the pair', ( $groups['compare']['demo'] ?? '' ) !== '' && substr_count( trim( \Nino\Html::renderSource( $appData, $groups['compare']['demo'], [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) ), 'class="compare-range"' ) === 1 );
$block = \Nino\Blocks::get( $appData, 'compare-before-after' );
check( 'the block is listed with the feature as its origin, with its two picture slots, valid and without a missing feature', is_array( $block ) === true && in_array( 'compare', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === []
	&& array_keys( (array) ( $block['images'] ?? [] ) ) === [ 'before', 'after' ] && \Nino\Blocks::rejected( $appData ) === [] );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'compare-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[compare before="/nino/placeholder/landscape" after="/nino/placeholder/landscape"]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-compare|nino-is-ready|before-label|after-label|--color-|--space-[0-9]|--radius\b|--fontsize-|#fff|rgba\(/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1, and none writes a colour of its own', $old === [] && is_dir( $dir. '/templates' ) === false );

// The script that moves the divider runs over the kernel's stand-in of a page (compare-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: compare-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/compare-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'compare-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
