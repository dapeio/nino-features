<?php
declare(strict_types=1);

/**
 *	Nino
 *	copy-smoke.php		Contract test for the Copy feature (Modules\Copy): the
 *										manifest, the activation through \Nino\Features and the
 *										three words it merges, the [copy] shortcode over its
 *										body, its value and its label, the two files it puts
 *										into the site's own asset bundles, and deactivation.
 *
 *										What happens on a press is the browser's, and
 *										copy-js-smoke.js beside this file measures it; this test
 *										runs that one too where node is on the path.
 *
 *	Usage: php features/Copy/tests/copy-smoke.php
 *	       NINO_ROOT=../nino php features/Copy/tests/copy-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'copy' );
$appData['/nino/dir'] = '';
$appData['/nino/locales/textfiles'] = '/text';
$appData['./nino/locales/current'] = 'en_US';

$assetsDir = ninoSandboxDir( $appData ). '/features/Copy/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/copy.css', $assetsDir. '/copy.css' );
copy( dirname( __DIR__ ). '/assets/copy.js', $assetsDir. '/copy.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "copy"', is_array( $manifest ) && $manifest['key'] === 'copy' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'copy' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Copy', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'copy' )['installed'] === '1.0.0' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the button\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/copy/do]]', '[[/copy/done]]', '[[/copy/failed]]' ] ), $text ) === [] );
}

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Copy::init() - the shortcode and the bundle\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [copy]', ( $appData['./nino/html/shortcodes']['copy'] ?? null ) !== null );
check( 'copy.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Copy/assets/copy.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'copy.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Copy/assets/copy.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [copy] ------------------------------------------------------------------

echo "[copy] - the text, and the button beside it\n";

$html = \Nino\Html::renderHtml( $appData, '[copy]DE02 1203 0000 0000 2020 51[/copy]' );

check( 'what is shown is the body, as it was written',
	str_contains( $html, '<span class="nino-copy-text">DE02 1203 0000 0000 2020 51</span>' ) === true );

/*	Without JavaScript what is left is the text, selectable, which is what it
	was before - so the button is written hidden, the way [mode-switch]'s switch
	is. A button that copies nothing is worse than no button	*/
check( 'the button is hidden until the script has it', preg_match( '/<button[^>]*class="nino-copy-btn"[^>]*\shidden/', $html ) === 1 );

check( 'all three words reach the browser on the button, resolved before the page was sent',
	str_contains( $html, 'data-copy-do="Copy"' ) === true
	&& str_contains( $html, 'data-copy-done="Copied"' ) === true
	&& str_contains( $html, 'data-copy-failed="Press Ctrl+C"' ) === true
	&& str_contains( $html, '[[/copy/' ) === false );
check( '...and the first of them is the word on it', str_contains( $html, '<span class="nino-copy-word">Copy</span>' ) === true );

check( 'nothing is copied by default that is not what is shown', str_contains( $html, 'data-copy-value' ) === false );

$value = \Nino\Html::renderHtml( $appData, '[copy value="DE02120300000000202051"]DE02 1203 0000 0000 2020 51[/copy]' );
check( 'a value given in the shortcode is what gets copied, while the grouping stays on screen',
	str_contains( $value, 'data-copy-value="DE02120300000000202051"' ) === true
	&& str_contains( $value, '>DE02 1203 0000 0000 2020 51</span>' ) === true );

$named = \Nino\Html::renderHtml( $appData, '[copy label="IBAN"]DE02[/copy]' );
check( 'what the button copies is named for a reader who cannot see what it stands beside',
	str_contains( $named, 'data-copy-named="IBAN"' ) === true );

$block = \Nino\Html::renderHtml( $appData, '[copy block]php bin/check.sh[/copy]' );
check( 'the block form is a <pre>, because there the whitespace is content',
	str_contains( $block, '<pre class="nino-copy-text">php bin/check.sh</pre>' ) === true
	&& str_contains( $block, 'nino-copy--block' ) === true );
check( '...and the line form is not', str_contains( $html, '<pre' ) === false && str_contains( $html, 'nino-copy--block' ) === false );

$quoted = \Nino\Html::renderHtml( $appData, '[copy value="a\" onx=\"1" label="Ada & Co"]<b>x</b>[/copy]' );
check( 'a body, a value and a label with html in them are escaped, not written through',
	str_contains( $quoted, 'onx=' ) === false && str_contains( $quoted, '<b>x</b>' ) === false
	&& str_contains( $quoted, 'Ada &amp; Co' ) === true );

check( 'an empty [copy] renders nothing - a copy button for an empty string does nothing anybody can tell apart',
	\Nino\Html::renderHtml( $appData, '[copy][/copy]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[copy]   [/copy]' ) === '' );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/copy.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/copy.js' );

/*	The clipboard API is secure-context only, so a site served over http has
	none at all - and where it is there it may still be refused	*/
check( 'there are two ways of copying, and the older one is tried before anything is said',
	str_contains( $js, 'navigator.clipboard' ) === true && str_contains( $js, "execCommand( 'copy' )" ) === true );
check( 'the field the older way needs is taken off the page again',
	str_contains( $js, 'removeChild( field )' ) === true );
check( 'nothing about a copy reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

/*	A colour says it to whoever can see it and to nobody else - the word on the
	button is what a screen reader has	*/
check( 'what happened is said in the word on the button, not only in its colour',
	str_contains( $js, 'word.textContent = said' ) === true );
check( '...and the button goes back to being a copy button afterwards',
	str_contains( $js, 'clearTimeout' ) === true && str_contains( $js, 'word.textContent = back' ) === true );

check( 'the stylesheet keeps the hidden button hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-copy-btn[hidden]' ) === true );
check( 'and a block is allowed to scroll rather than to stretch the page it is on',
	str_contains( $css, 'overflow-x: auto' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so copy-js-smoke.js is not run here\n\n";
else {
	echo "copy-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/copy-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'copy' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Copy', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/copy/do]]'] ) === true );

ninoDone( $appData );
