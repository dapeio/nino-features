<?php
declare(strict_types=1);

/**
 *	Nino
 *	progress-smoke.php	Contract test for the Progress feature (Modules\Progress): the
 *										manifest, the activation through \Nino\Features, the
 *										[progress] shortcode that writes the bar's element and
 *										what it does with the two values it is given, the
 *										component the Builder places where the Components module
 *										is, the two files the feature lists under assets, which
 *										the kernel puts into the site's own asset bundles
 *										(\Nino\Features::registerAssets()), what those two files
 *										promise, and deactivation. The bar is one empty element,
 *										which a project's own template may still carry by hand;
 *										the shortcode and the component only write it.
 *
 *										Where the Components module is switched off nothing owns
 *										the name [progress] and the feature registers the
 *										shortcode itself; that is the half of this test that
 *										runs first. Where the module is on, the component is
 *										checked besides - registered from the manifest, and
 *										answering what the shortcode answers.
 *
 *										What the two files then do is the browser's, and
 *										progress-js-smoke.js beside this file measures it, over
 *										the kernel's own Nino.js from the same checkout; this
 *										test runs that one too where node is on the path.
 *
 *	Usage: php features/Progress/tests/progress-smoke.php
 *	       NINO_ROOT=../nino php features/Progress/tests/progress-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'progress' );
$appData['/nino/dir'] = '';


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "progress"', is_array( $manifest ) && $manifest['key'] === 'progress' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under ui', $manifest['category'] === 'ui' );

/*	The bar is one empty element, pointed at what it measures with data
	attributes: the Builder places it as a component, the shortcode writes it
	where there is no Builder, and a project's own template may carry it by
	hand. Nothing for a route to do and nothing for a site-wide setting	*/
check( 'its manual names the shortcode and the class a call adds, and no route',
	array_keys( $manifest['manual']['shortcodes'] ) === [ '[progress of="#article" label="Lesefortschritt"]', '[progress ... class="my-class"]' ]
	&& $manifest['manual']['routes'] === [] );
check( '...and names the data attributes a bar is pointed with in the markup',
	array_key_exists( 'data-progress-of="#article"', $manifest['manual']['markup'] ) === true
	&& array_key_exists( 'data-progress-label="Lesefortschritt"', $manifest['manual']['markup'] ) === true );
check( '...and says in both languages who writes the element: the Builder, as a component, or whoever writes the page, by hand',
	preg_match( '/Builder.*by hand/', \Nino\Features::localized( $manifest['manual']['markup']['class="nino-progress"'] ?? '', 'en_US' ) ) === 1
	&& preg_match( '/Builder.*von Hand/', \Nino\Features::localized( $manifest['manual']['markup']['class="nino-progress"'] ?? '', 'de_DE' ) ) === 1 );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'and ships no install unit - there is nothing for it to write into a project',
	$manifest['manual']['install'] === [] );
check( 'it lists its stylesheet and its script under assets, which is all it asks the kernel for',
	$manifest['assets'] === [ '/features/Progress/assets/progress.css', '/features/Progress/assets/progress.js' ] );
check( 'it is written for the Nino whose toolset it calls - the template, the class and the shortcode helpers, the assets',
	$manifest['nino'] === '^1.8' );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'progress' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Progress', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'progress' )['installed'] === $manifest['version'] );

echo "\n";


// --- init() and the assets ---------------------------------------------------

echo "Modules\\Progress::init() and the manifest's assets - the shortcode and the bundle\n";

// The order \Nino\init() takes: the assets of the manifests, then the init() of every module
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

/*	This project has not booted the Components module, so there is no registry
	for the bar to be in: the feature registers the shortcode itself, which is
	what it does wherever that module is switched off	*/
check( 'the shortcode is registered as [progress] where no registry has it', ( $appData['./nino/html/shortcodes']['progress'] ?? null ) !== null );
check( 'progress.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Progress/assets/progress.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'progress.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Progress/assets/progress.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about a bar reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-progress' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'data-progress-of' );
} )() );

echo "\n";


// --- [progress] --------------------------------------------------------------

echo "[progress] - the element, and what happens to the two values in it\n";

check( 'a bar with nothing said about it is the element over the article, with no name',
	\Nino\Html::renderHtml( $appData, '[progress]' ) === '<div class="nino-progress" data-progress-of="#article" data-progress-label=""></div>' );
check( 'what it measures and what it is called are written where the call says',
	\Nino\Html::renderHtml( $appData, '[progress of="main.post" label="Lesefortschritt"]' ) === '<div class="nino-progress" data-progress-of="main.post" data-progress-label="Lesefortschritt"></div>' );
check( 'an empty "of" is the whole page, and is not taken for the article',
	\Nino\Html::renderHtml( $appData, '[progress of=""]' ) === '<div class="nino-progress" data-progress-of="" data-progress-label=""></div>' );
check( 'a value of nothing but spaces is as empty - a label of spaces would be a progressbar with no name',
	\Nino\Html::renderHtml( $appData, '[progress of="  " label="  "]' ) === '<div class="nino-progress" data-progress-of="" data-progress-label=""></div>' );
check( 'a class of its own comes after the bar\'s, and none leaves no space behind',
	\Nino\Html::renderHtml( $appData, '[progress class="thick"]' ) === '<div class="nino-progress thick" data-progress-of="#article" data-progress-label=""></div>'
	&& \Nino\Html::renderHtml( $appData, '[progress class=""]' ) === \Nino\Html::renderHtml( $appData, '[progress]' ) );

/*	Both values are editor text, and the element is rendered once more with the
	page: a quote must not end its attribute, and a bracket must not open a fill
	or a shortcode there	*/
check( 'a label with markup in it is escaped, not written through',
	\Nino\Html::renderHtml( $appData, '[progress label="Ada & <b>Co</b>"]' ) === '<div class="nino-progress" data-progress-of="#article" data-progress-label="Ada &amp; &lt;b&gt;Co&lt;/b&gt;"></div>' );
check( 'a quote in a value is escaped - the shortcode reads an apostrophe as one - and cannot end its attribute',
	\Nino\Html::renderHtml( $appData, '[progress of="#a\'b" label="say \'hi\'"]' ) === '<div class="nino-progress" data-progress-of="#a&quot;b" data-progress-label="say &quot;hi&quot;"></div>' );
check( 'a bracket is written as a character reference, which is what keeps it from opening anything',
	\Nino\Html::renderHtml( $appData, '[progress of="a[b" label="c [d"]' ) === '<div class="nino-progress" data-progress-of="a&#91;b" data-progress-label="c &#91;d"></div>' );

$typed = [ 'of' => 'main > .post[data-kind="long"], #a & #b', 'label' => 'Ada & "Co" <b> \'x\' [[/test/progress/leak]] [y', 'class' => 'x"y [z' ];
$element = \Nino\Modules\Progress::doShortcode( $appData, $typed );

check( 'whatever the values hold, the element stays one element with its three attributes',
	preg_match( '/^<div class="[^"<>]*" data-progress-of="[^"<>]*" data-progress-label="[^"<>]*"><\/div>$/', $element ) === 1 );
check( '...and the browser reads back from them what was typed, selector and label alike',
	preg_match( '/^<div class="([^"]*)" data-progress-of="([^"]*)" data-progress-label="([^"]*)">/', $element, $found ) === 1
	&& html_entity_decode( $found[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) === 'nino-progress '. $typed['class']
	&& html_entity_decode( $found[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) === $typed['of']
	&& html_entity_decode( $found[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) === $typed['label'] );

$leaky = $appData;
\Nino\Html::addFills( $leaky, [ '[[/test/progress/leak]]' => 'LEAKED' ], '*' );
$page = \Nino\Html::renderHtml( $leaky, \Nino\Modules\Progress::doShortcode( $leaky, [ 'of' => '[[/test/progress/leak]]', 'label' => '[progress of="#nested"]', 'class' => '[[/test/progress/leak]]' ] ) );

check( 'the fill and the shortcode the next two checks use are ones a page would take, written bare',
	\Nino\Html::renderHtml( $leaky, '[[/test/progress/leak]]' ) === 'LEAKED'
	&& str_contains( \Nino\Html::renderHtml( $leaky, '[progress of="#nested"]' ), 'data-progress-of="#nested"' ) === true );
check( 'a fill in a value stays text when the page is rendered - in the selector and in the class',
	str_contains( $page, 'LEAKED' ) === false && substr_count( $page, '&#91;&#91;/test/progress/leak]]' ) === 2 );
check( '...and a shortcode in a value is not run',
	substr_count( $page, '<div' ) === 1 && str_contains( $page, 'data-progress-of="#nested"' ) === false
	&& str_contains( $page, 'data-progress-label="&#91;progress of=&quot;#nested&quot;]"' ) === true );

/*	htmlspecialchars() answers '' for a value that is not valid UTF-8 unless it
	is told to substitute, and a label that renders as nothing is a bar that is
	announced with no name	*/
check( 'a value that is not valid UTF-8 is substituted rather than dropped',
	str_contains( \Nino\Modules\Progress::doShortcode( $appData, [ 'label' => "Lese\xB1fortschritt" ] ), 'data-progress-label="Lese'. "\u{FFFD}". 'fortschritt"' ) === true );

check( '...and nothing is logged', ninoWarnings() === [] );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/progress.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/progress.js' );

/*	The whole page counts the footer as part of the article, so "finished"
	arrives after the last paragraph rather than at it	*/
check( 'a bar can be pointed at the one element that is the text, rather than at the whole page',
	str_contains( $js, "getAttribute( 'data-progress-of' )" ) === true
	&& str_contains( $js, 'box.height - seen' ) === true );
check( '...and an element shorter than the screen is finished the moment it is on it, not never',
	str_contains( $js, 'if( travel <= 0 )' ) === true );

check( 'the width is a custom property the stylesheet uses, so a scroll costs a paint',
	str_contains( $js, "setProperty( '--nino-progress'" ) === true
	&& str_contains( $css, 'width: var(--nino-progress' ) === true );
check( 'scrolling costs one frame at most, not one call per event - the scroll and the resize are the kernel\'s throttled events',
	str_contains( $js, "Nino.events.bindCallback( 'scroll', paint )" ) === true && str_contains( $js, "Nino.events.bindCallback( 'resize', paint )" ) === true
	&& str_contains( $js, 'addEventListener' ) === false );

/*	A progressbar with no name is announced as a number nobody asked for, which
	is worse than a decoration a screen reader never mentions	*/
check( 'a bar that was given a name is a named progressbar',
	str_contains( $js, "setAttribute( 'role', 'progressbar' )" ) === true
	&& str_contains( $js, "setAttribute( 'aria-valuemax', '100' )" ) === true );
check( '...and one that was not is hidden from a screen reader rather than announced unnamed',
	str_contains( $js, "setAttribute( 'aria-hidden', 'true' )" ) === true );

check( 'the bar is not on the page at all until something has been measured',
	str_contains( $css, '.nino-progress:not(.nino-is-ready)' ) === true );
check( 'it paints from the project\'s own accent rather than a palette of its own',
	str_contains( $css, 'var(--color-accent' ) === true && preg_match( '/#[0-9a-f]{6}/i', $css ) !== 1 );
check( 'and a visitor who asked for less motion gets the value itself, with nothing in between - the clamp of Nino.css cuts the plain transition rule, so there is no block of its own',
	preg_match( '/\.nino-progress::before\s*\{\s*transition:\s*width/', $css ) === 1 && str_contains( $css, 'prefers-reduced-motion' ) === false );

check( 'nothing about a bar reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so progress-js-smoke.js is not run here\n\n";
else {
	echo "progress-js-smoke.js\n";
	// The checkout this test runs against is the one whose Nino.js the script runs over
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/progress-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Components ----------------------------------------------------------------

echo "Components - [progress] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];
	$schema = $declared['progress'] ?? [];
	$attributes = $schema['attributes'] ?? [];

	check( 'the manifest declares one component, [progress]: no source, meant once per page, drawn as a block',
		array_keys( $declared ) === [ 'progress' ] && ( $schema['source'] ?? null ) === 'none'
		&& ( $schema['loop'] ?? null ) === false && ( $schema['preview'] ?? null ) === 'block' );
	check( '...with the two attributes of the markup, both strings: "of" over the article, "label" with no name',
		array_keys( $attributes ) === [ 'of', 'label' ]
		&& ( $attributes['of']['type'] ?? null ) === 'string' && ( $attributes['of']['default'] ?? null ) === \Nino\Modules\Progress::OF_DEFAULT
		&& ( $attributes['label']['type'] ?? null ) === 'string' && ( $attributes['label']['default'] ?? null ) === '' );

	$words = [ $schema['label'] ?? '' ];
	foreach( $attributes as $attribute )
		array_push( $words, $attribute['label'] ?? '', $attribute['hint'] ?? '' );

	check( '...and each of them is named and explained in both interface languages',
		array_filter( $words, static fn( mixed $word ): bool => \Nino\Features::localized( $word, 'en_US' ) === ''
			|| \Nino\Features::localized( $word, 'de_DE' ) === \Nino\Features::localized( $word, 'en_US' ) ) === [] );

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[progress]',
		'[progress of="#article" label="Lesefortschritt"]',
		'[progress of="main.post" label="Lesefortschritt"]',
		'[progress label="Lesefortschritt"]',
		'[progress of=""]',
		'[progress label="Ada & <b>Co</b> \'x\' [y"]',
		'[progress class="thick"]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcode the feature registered above is the one of a
		boot that has no registry to read; this boot registers it once	*/
	$probe = $appData;
	foreach( [ 'progress' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Progress::init( $probe );
	ninoWarnings();

	check( 'the component is registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['progress'] ?? null ) === ( $declared['progress'] ?? false ) );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['progress']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the component by its wrapper, the feature adding none of its own',
		\Nino\Features::shortcodes( $probe, 'progress' ) === [ 'progress' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/progress' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'progress' ) === [ 'of' => '#article', 'label' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	// A call written the Builder's way: what differs from the defaults, nothing
	// else - and the same call with every default written out
	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[progress of="#article" label="" class=""]' ) === \Nino\Html::renderHtml( $probe, '[progress]' ) );
	check( 'a bar with no name is the element with an empty label, which progress.js hides from a screen reader',
		\Nino\Html::renderHtml( $probe, '[progress of="main.post"]' ) === '<div class="nino-progress" data-progress-of="main.post" data-progress-label=""></div>' );

	/*	The component's callback is the kernel's wrapper around doShortcode(), the
		renderer it falls back to: it is handed the arguments a shortcode could not
		carry in its own brackets - a quote, a bracket - and makes them ready	*/
	$component = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/progress' )[0];

	$args = [ 'of' => '#a"b', 'label' => 'say "hi" [[/x]]', 'class' => 'thick [y' ];
	check( 'the component writes the element from the arguments it is handed, every value escaped and its brackets inert',
		$component( $probe, $args )
		=== '<div class="nino-progress thick &#91;y" data-progress-of="#a&quot;b" data-progress-label="say &quot;hi&quot; &#91;&#91;/x]]"></div>' );

	\Nino\Html::addFills( $probe, [ '[[/test/progress/leak]]' => 'LEAKED' ], '*' );
	$args = [ 'of' => '[[/test/progress/leak]]', 'label' => '[progress of="#nested"]', 'class' => '' ];
	$page = \Nino\Html::renderHtml( $probe, $component( $probe, $args ) );
	check( 'a fill or a shortcode in what the Builder wrote opens nothing when the page is rendered',
		str_contains( $page, 'LEAKED' ) === false && substr_count( $page, '<div' ) === 1 && str_contains( $page, 'data-progress-of="#nested"' ) === false );
	ninoWarnings();
} )( $appData );

echo "\n";


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'progress' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Progress', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'progress' )['active'] ?? true ) === false );

ninoDone( $appData );
