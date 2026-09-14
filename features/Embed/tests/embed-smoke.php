<?php
declare(strict_types=1);

/**
 *	Nino
 *	embed-smoke.php		Contract test for the Embed feature (Modules\Embed): the
 *										manifest, the activation through \Nino\Features and the
 *										four words it merges, the [embed] shortcode over every
 *										provider and every way of getting it wrong, the two
 *										files it puts into the site's own asset bundles
 *										(\Nino\Html::addAsset()), and deactivation.
 *
 *										The one thing this feature exists for is checked here
 *										rather than described: what the shortcode renders holds
 *										no iframe and no provider address in any attribute a
 *										browser fetches. What happens after a press is the
 *										browser's, and embed-js-smoke.js beside this file
 *										measures it; this test runs that one too where node is
 *										on the path. Travels with the feature and runs against
 *										the checkout three levels up, or the one NINO_ROOT names
 *										(see tests/harness.php there).
 *
 *	Usage: php features/Embed/tests/embed-smoke.php
 *	       NINO_ROOT=../nino php features/Embed/tests/embed-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'embed' );
$appData['/nino/dir'] = '';
/*	\Nino\AppData::prepare() (what ninoSandbox() calls) only seeds the handful
	of keys needed before config.php loads - everything else in ::DEFAULTS,
	textfiles' own directory included, arrives through the real ::init() a
	sandboxed test never runs. Rendering the surface's words needs it	*/
$appData['/nino/locales/textfiles'] = '/text';
// ninoSandbox() defaults the current locale to 'de_DE'; switched to English
// here so the assertions below compare against install/text/en_US.php
$appData['./nino/locales/current'] = 'en_US';

/*	\Nino\Filesystem::path()'s fallback resolves a virtual path outside
	PRIVATE_DIRS/PUBLIC_DIRS against the project root - exactly how
	'/_nino/Nino.css' reaches the kernel's own file. The sandbox's project root
	is a fresh temp directory, not this feature's real parent, so the two files
	the asset bundler actually has to read are mirrored into it here, the way a
	real project's features/ directory holds them	*/
$assetsDir = ninoSandboxDir( $appData ). '/features/Embed/assets';
mkdir( $assetsDir, 0755, true );
copy( dirname( __DIR__ ). '/assets/embed.css', $assetsDir. '/embed.css' );
copy( dirname( __DIR__ ). '/assets/embed.js', $assetsDir. '/embed.js' );


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "embed"', is_array( $manifest ) && $manifest['key'] === 'embed' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

$raw = include $dir. '/feature.php';
check( 'it is filed under ui', ( $raw['category'] ?? '' ) === 'ui' );

/*	Consent is deliberately not a requirement. Without it every embed waits for
	a press, which is the safe half and needs nothing configured; with it, a
	visitor who already allowed external media is not asked twice	*/
check( 'it requires no other feature - the press works on its own', $manifest['requires'] === [] );
check( 'it keeps no data of its own', $manifest['data'] === [] );
check( 'it carries the consent category and the remember flag as settings',
	array_keys( $manifest['settings'] ) === [ 'category', 'remember' ]
	&& $manifest['settings']['category']['default'] === 'external'
	&& $manifest['settings']['remember']['default'] === false );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'the registry lists it inactive, with nothing in the way', ( static function() use ( &$appData ): bool {
	$feature = \Nino\Features::get( $appData, 'embed' );
	return $feature !== null && $feature['active'] === false && $feature['installed'] === null && $feature['problems'] === [];
} )() );

check( 'activation succeeds', \Nino\Features::activate( $appData, 'embed' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Embed', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'embed' )['installed'] === '1.0.0' );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the surface\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/embed/load]]', '[[/embed/note]]', '[[/embed/open]]', '[[/embed/frame]]' ] ), $text ) === [] );
}
check( 'it wrote no template and no route of its own - where an embed goes is the project\'s call',
	is_dir( ninoSandboxDir( $appData ). '/templates' ) === false
	&& ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Embed::init() - the shortcode and the bundle\n";

$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [embed]', ( $appData['./nino/html/shortcodes']['embed'] ?? null ) !== null );
check( 'embed.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Embed/assets/embed.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'embed.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Embed/assets/embed.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route - nothing about an embed reaches the server', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-embed' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'data-embed-src' );
} )() );

echo "\n";


// --- Nothing is fetched --------------------------------------------------------

echo "What the shortcode renders - and what it does not\n";

$html = \Nino\Html::renderHtml( $appData, '[embed youtube="dQw4w9WgXcQ" title="Ein Video"]' );

/*	The whole reason this feature is not an iframe in a template. Hiding an
	iframe does not stop it: one inside a container with the hidden attribute,
	with display:none or with visibility:hidden is fetched exactly like a
	visible one, so the visitor's address has reached the provider before they
	were asked. Measured as the absence of every attribute a browser fetches	*/
check( 'there is no iframe in what the server sends', str_contains( $html, '<iframe' ) === false );
check( '...and no src or srcset pointing at the provider either - those are what a browser fetches',
	preg_match( '/\s(?:src|srcset|data-src)\s*=\s*"[^"]*youtube/i', $html ) !== 1 );

/*	The one href that does name the provider is the way out inside <noscript>,
	and that is not a fetch: with scripting on, a browser parses noscript's
	content as text and there is no element at all; with it off there is a link,
	and a link is a thing somebody clicks	*/
check( 'the only mention of the provider in an href is the <noscript> way out',
	preg_match_all( '/href\s*=\s*"[^"]*youtube/i', $html ) === 1
	&& preg_match( '/<noscript>.*youtube.*<\/noscript>/s', $html ) === 1 );
check( 'the address is carried in a data attribute instead, which nothing fetches',
	str_contains( $html, 'data-embed-src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0"' ) === true );

/*	A still from a YouTube video lives on a YouTube server, so showing one
	would make the very request this prevents, one image earlier	*/
check( 'no thumbnail is fetched from the provider - a poster is one of the project\'s own images or none',
	preg_match( '/<img[^>]+src="(?!\/)[^"]*\/\//', $html ) !== 1
	&& str_contains( $html, 'ytimg' ) === false && str_contains( $html, 'vumbnail' ) === false );

check( 'the surface is hidden until the script has it - a button nothing wires up is worse than none',
	preg_match( '/<button[^>]*class="nino-video-poster nino-embed-open"[^>]*\shidden/', $html ) === 1 );
check( '...and a visitor without JavaScript gets the link out instead',
	str_contains( $html, '<noscript>' ) === true
	&& str_contains( $html, 'rel="noopener noreferrer"' ) === true );

check( 'it uses the kernel\'s own surface classes, which have been in Nino.css since 1.0 with nothing driving them',
	str_contains( $html, 'nino-video-poster' ) === true && str_contains( $html, 'nino-video-play' ) === true );
check( 'the host is named on the surface, because that is what somebody decides on',
	str_contains( $html, 'data-embed-host="youtube-nocookie.com"' ) === true
	&& str_contains( $html, 'youtube-nocookie.com</span>' ) === true );
check( 'the consent category the settings name is written into the markup for the script to read',
	str_contains( $html, 'data-embed-consent="external"' ) === true );
check( 'every word is a text fill the project owns, resolved by the time it renders',
	str_contains( $html, '[[/embed/' ) === false && str_contains( $html, 'loads content from' ) === true );
check( 'a title given in the shortcode names both the surface and the frame that will be there',
	str_contains( $html, 'data-embed-title="Ein Video"' ) === true && str_contains( $html, '>Ein Video</span>' ) === true );

echo "\n";


// --- The providers -------------------------------------------------------------

echo "Providers, and the addresses they are given\n";

check( 'youtube goes through the no-cookie host, not youtube.com',
	\Nino\Modules\Embed::source( [ 'youtube' => 'dQw4w9WgXcQ' ] ) === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0' );
check( 'vimeo carries the provider\'s own do-not-track flag',
	\Nino\Modules\Embed::source( [ 'vimeo' => '76979871' ] ) === 'https://player.vimeo.com/video/76979871?dnt=1' );
check( 'an https address of any other kind is taken as it is',
	\Nino\Modules\Embed::source( [ 'url' => 'https://maps.example.org/embed?q=1' ] ) === 'https://maps.example.org/embed?q=1' );

/*	What goes in here ends up in an iframe's src once it is released, so an id
	is checked against that provider's own shape rather than pasted in	*/
check( 'an id that is not one is refused rather than pasted into an address',
	\Nino\Modules\Embed::source( [ 'youtube' => '../../evil' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'youtube' => 'a"onload="x' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'vimeo' => 'not-a-number' ] ) === '' );
check( 'http, javascript: and a bare word are all refused - https or nothing',
	\Nino\Modules\Embed::source( [ 'url' => 'http://maps.example.org/embed' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'javascript:alert(1)' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'maps.example.org' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://' ] ) === '' );
check( 'and an [embed] naming nothing at all renders nothing at all - a box with no frame behind it is worse than no box',
	\Nino\Modules\Embed::source( [] ) === ''
	&& \Nino\Html::renderHtml( $appData, '[embed]' ) === ''
	&& \Nino\Html::renderHtml( $appData, '[embed youtube="../../evil"]' ) === '' );

check( 'the host on the surface loses its www., because that is not what anybody reads',
	\Nino\Modules\Embed::host( 'https://www.youtube-nocookie.com/embed/x' ) === 'youtube-nocookie.com'
	&& \Nino\Modules\Embed::host( 'https://player.vimeo.com/video/1' ) === 'player.vimeo.com' );

echo "\n";


// --- The box, the picture and the words ----------------------------------------

echo "The shape, the poster and the fallbacks\n";

check( 'the default shape is 16:9 and a named one is taken',
	str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871"]' ), 'nino-embed--16-9' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" ratio="4-3"]' ), 'nino-embed--4-3' ) === true );
check( '...and a shape that is not one of the four falls back rather than reaching the stylesheet',
	str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" ratio="7-3"]' ), 'nino-embed--16-9' ) === true
	&& str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" ratio="7-3"]' ), '7-3' ) === false );

$poster = \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" poster="video/still.jpg"]' );
check( 'a poster is served from the project\'s own images', str_contains( $poster, 'still.jpg' ) === true
	&& str_contains( $poster, 'class="nino-embed-poster"' ) === true && str_contains( $poster, 'loading="lazy"' ) === true );
check( '...and one that climbs out of that directory is left out rather than linked',
	str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" poster="../../config.php"]' ), 'nino-embed-poster' ) === false
	&& str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" poster="/etc/passwd"]' ), 'nino-embed-poster' ) === false );
check( 'without a title the frame still gets a name - "iframe" is what a screen reader says otherwise',
	str_contains( \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871"]' ), 'data-embed-title="External content"' ) === true );

/*	A title is editor text that may be a textfill, and what comes out of the
	fill engine is still text - so it is rendered first and escaped after	*/
$quoted = \Nino\Html::renderHtml( $appData, '[embed vimeo="76979871" title="Ada & Co"]' );
check( 'a title with html in it is escaped, not written through',
	str_contains( $quoted, 'Ada &amp; Co' ) === true && str_contains( $quoted, 'Ada & Co' ) === false );

$off = $appData;
$off['./nino/features'] = null;
unset( $off['./nino/features'] );
$off[ \Nino\Features::STATE_KEY ]['embed']['settings'] = [ 'category' => '', 'remember' => true ];
check( 'an empty category renders an embed that can only ever be pressed',
	str_contains( \Nino\Html::renderHtml( $off, '[embed vimeo="76979871"]' ), 'data-embed-consent=""' ) === true );
check( '...and the remember flag reaches the markup only when it is on',
	str_contains( \Nino\Html::renderHtml( $off, '[embed vimeo="76979871"]' ), 'data-embed-remember="1"' ) === true
	&& str_contains( $html, 'data-embed-remember' ) === false );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/embed.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/embed.js' );

check( 'the script builds the frame rather than unhiding one', str_contains( $js, "createElement( 'iframe' )" ) === true );
check( 'nothing about an embed reaches this site\'s server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );
check( 'it meets Consent over markup rather than over code - the attribute and the event, no import',
	str_contains( $js, 'data-consent' ) === true && str_contains( $js, "'nino:consent'" ) === true
	&& str_contains( $js, 'Nino.consent' ) === false );
check( 'a frame only ever gets an https address, whatever the markup says',
	str_contains( $js, "'https://'" ) === true );
check( 'the button is removed once the frame is there, not hidden - a hidden button is still in the tab order',
	str_contains( $js, 'removeChild' ) === true );
check( 'the stylesheet paints from the project\'s own custom properties and carries no palette',
	str_contains( $css, 'var(--color-' ) === true && preg_match( '/#[0-9a-f]{6}/i', $css ) !== 1 );
check( '...and it answers to a visitor who asked for less motion',
	str_contains( $css, 'prefers-reduced-motion' ) === true );

/*	The hidden attribute is display:none out of the browser's own stylesheet,
	and this file gives the same element a display of its own - which beats it.
	Without the guard the surface written hidden is on the screen for the one
	visitor it must not be shown to: the one whose browser never ran embed.js	*/
check( '...and it keeps the hidden surface hidden, which its own display rule would otherwise undo',
	str_contains( $css, '.nino-video-poster[hidden]' ) === true );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so embed-js-smoke.js is not run here\n\n";
else {
	echo "embed-js-smoke.js\n";
	$out = (string) @shell_exec( escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/embed-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'embed' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Embed', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/embed/note]]'] ) === true );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'embed' )['active'] ?? true ) === false );

ninoDone( $appData );
