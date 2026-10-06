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
check( 'it declares the one callback it registers - the output phase, where the finished response is in hand',
	array_keys( $manifest['manual']['callbacks'] ) === [ '/nino/http/output' ] );
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
	&& \Nino\Features::get( $appData, 'embed' )['installed'] === $manifest['version'] );

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
check( 'it hooks the output phase, where the body is rendered - the response phase runs before it',
	in_array( [ \Nino\Modules\Embed::class, 'callbackOutput' ], array_merge( ...( $appData['./nino/callbacks']['/nino/http/output'] ?? [ [] ] ) ), true ) === true );
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

/*	What a person has in the clipboard is a page address, not an id. The id is
	the one thing taken from it, so what reaches an iframe's src is still a
	validated id and nothing of the address around it	*/
$youtube = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0';
$vimeo = 'https://player.vimeo.com/video/76979871?dnt=1';

foreach( [
	'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10'									=> 'a watch address with a time code',
	'https://www.youtube.com/watch?feature=share&amp;v=dQw4w9WgXcQ'		=> 'one copied out of an html field, where & is &amp;',
	'https://youtu.be/dQw4w9WgXcQ?si=abc'															=> 'a short link with its tracking parameter',
	'https://www.youtube.com/shorts/dQw4w9WgXcQ'												=> 'a short',
	'https://www.youtube.com/live/dQw4w9WgXcQ?feature=share'						=> 'a live stream',
	'https://www.youtube.com/embed/dQw4w9WgXcQ'												=> 'an embed address',
	'https://m.youtube.com/watch?v=dQw4w9WgXcQ'												=> 'the mobile host',
	'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'								=> 'the no-cookie host itself',
	'https://WWW.YouTube.com/watch?v=dQw4w9WgXcQ'											=> 'a host in capitals',
	'http://www.youtube.com/watch?v=dQw4w9WgXcQ'												=> 'http, since the player address is https whatever was pasted',
	'  https://youtu.be/dQw4w9WgXcQ  '																	=> 'surrounding space',
] as $address => $what )
	check( 'youtube= takes '. $what, \Nino\Modules\Embed::source( [ 'youtube' => $address ] ) === $youtube );

foreach( [
	'https://vimeo.com/76979871'																		=> 'a page address',
	'https://vimeo.com/channels/staffpicks/76979871'								=> 'a channel video',
	'https://vimeo.com/groups/shortfilms/videos/76979871'						=> 'a group video',
	'https://vimeo.com/showcase/123456/video/76979871'							=> 'a showcase video',
	'https://player.vimeo.com/video/76979871?autoplay=1'						=> 'a player address',
	'https://www.vimeo.com/76979871'																=> 'the www. host',
] as $address => $what )
	check( 'vimeo= takes '. $what, \Nino\Modules\Embed::source( [ 'vimeo' => $address ] ) === $vimeo );

check( 'an unlisted Vimeo video keeps its hash - in the path or in h= - and nothing else of the address',
	\Nino\Modules\Embed::source( [ 'vimeo' => 'https://vimeo.com/76979871/abcdef1234' ] ) === 'https://player.vimeo.com/video/76979871?h=abcdef1234&dnt=1'
	&& \Nino\Modules\Embed::source( [ 'vimeo' => 'https://player.vimeo.com/video/76979871?h=abcdef1234&autoplay=1' ] ) === 'https://player.vimeo.com/video/76979871?h=abcdef1234&dnt=1'
	&& \Nino\Modules\Embed::source( [ 'vimeo' => 'https://vimeo.com/76979871?h=not-hex!' ] ) === $vimeo );
check( 'video() answers the id, the hash and whether the address already was the provider\'s player',
	\Nino\Modules\Embed::video( 'youtube', 'https://youtu.be/dQw4w9WgXcQ' ) === [ 'id' => 'dQw4w9WgXcQ', 'hash' => '', 'embed' => false ]
	&& \Nino\Modules\Embed::video( 'youtube', 'dQw4w9WgXcQ' ) === [ 'id' => 'dQw4w9WgXcQ', 'hash' => '', 'embed' => false ]
	&& \Nino\Modules\Embed::video( 'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ' )['embed'] === true
	&& \Nino\Modules\Embed::video( 'vimeo', 'https://vimeo.com/76979871/abcdef1234' ) === [ 'id' => '76979871', 'hash' => 'abcdef1234', 'embed' => false ]
	&& \Nino\Modules\Embed::video( 'twitch', 'https://twitch.tv/x' ) === null );

/*	What is refused is refused with a line in the log: the shortcode renders
	nothing, and the page says why nowhere else	*/
ninoWarnings();
foreach( [
	[ 'youtube', 'https://vimeo.com/76979871', 'a Vimeo address for youtube=' ],
	[ 'vimeo', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'a YouTube address for vimeo=' ],
	[ 'youtube', 'https://www.youtube.com/playlist?list=PLabcdef123456', 'a playlist' ],
	[ 'youtube', 'https://www.youtube.com/embed/videoseries?list=PLabcdef123456', 'a playlist embed, whose "id" has an id\'s shape' ],
	[ 'youtube', 'https://www.youtube.com/@channel', 'a channel' ],
	[ 'youtube', 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', 'a host that only begins with YouTube\'s' ],
	[ 'youtube', 'https://www.youtube.com@evil.example/watch?v=dQw4w9WgXcQ', 'a host hidden behind credentials' ],
	[ 'youtube', 'https://user:pw@www.youtube.com/watch?v=dQw4w9WgXcQ', 'credentials' ],
	[ 'youtube', 'https://www.youtube.com:8443/watch?v=dQw4w9WgXcQ', 'a port' ],
	[ 'youtube', 'https://evil.example/watch?v=dQw4w9WgXcQ', 'another host' ],
	[ 'youtube', 'javascript:alert(1)', 'a javascript: address' ],
	[ 'youtube', 'https://www.youtube.com/watch?v=../../x', 'an id that is not one' ],
	[ 'youtube', 'https://www.youtube.com/watch?v=a"onload="x', 'an id with a quote in it' ],
	[ 'vimeo', 'https://vimeo.com/channels/staffpicks', 'a Vimeo channel' ],
] as [ $provider, $address, $what ] ) {
	ninoWarnings();
	$refusedSource = \Nino\Modules\Embed::source( [ $provider => $address ] );
	$logged = ninoWarnings();
	check( $provider. '= refuses '. $what. ' and says so in the log', $refusedSource === '' && count( $logged ) === 1 && str_contains( (string) json_encode( $logged ), 'is not a video address this can read' ) );
}
check( 'an empty youtube= is skipped silently, as before', \Nino\Modules\Embed::source( [ 'youtube' => '' ] ) === '' && ninoWarnings() === [] );
check( 'a pasted address that is refused logs once, cut and without a line break in it', ( static function(): bool {
	ninoWarnings();
	\Nino\Modules\Embed::source( [ 'youtube' => "https://evil.example/\n". str_repeat( 'x', 400 ) ] );
	$logged = (string) json_encode( ninoWarnings() );
	return str_contains( $logged, '\\n' ) === false && strlen( $logged ) < 500;
} )() );

/*	url= is for what has no provider rule: a map, a widget. A page address of
	the two that have one is turned into the player, since the page itself
	refuses to be framed; their own player addresses and every other host stay
	as written	*/
check( 'url= with a YouTube watch address is the no-cookie player',
	\Nino\Modules\Embed::source( [ 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10' ] ) === $youtube
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://youtu.be/dQw4w9WgXcQ' ] ) === $youtube );
check( 'url= with a Vimeo page address is the dnt player',
	\Nino\Modules\Embed::source( [ 'url' => 'https://vimeo.com/76979871' ] ) === $vimeo );
check( 'url= with a player address is left as it was written - its own start and autoplay stay',
	\Nino\Modules\Embed::source( [ 'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ?start=30' ] ) === 'https://www.youtube.com/embed/dQw4w9WgXcQ?start=30'
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://player.vimeo.com/video/76979871?autoplay=1' ] ) === 'https://player.vimeo.com/video/76979871?autoplay=1' );
check( 'url= with another host is left as it was written',
	\Nino\Modules\Embed::source( [ 'url' => 'https://maps.example.org/embed?q=1' ] ) === 'https://maps.example.org/embed?q=1'
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://www.youtube.com.evil.example/watch?v=dQw4w9WgXcQ' ] ) === 'https://www.youtube.com.evil.example/watch?v=dQw4w9WgXcQ' );
check( 'url= with a host a policy cannot name safely - credentials, an ip address, a non-ascii name - renders nothing',
	\Nino\Modules\Embed::source( [ 'url' => 'https://user:pw@maps.example.org/embed' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://192.0.2.7/embed' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://[2001:db8::1]/embed' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://2130706433/embed' ] ) === ''
	&& \Nino\Modules\Embed::source( [ 'url' => 'https://m\u{fc}nchen.example/embed' ] ) === ''
	&& \Nino\Html::renderHtml( $appData, '[embed url="https://192.0.2.7/embed" title="x"]' ) === '' );

/*	The pasted address, rendered: the frame is still only a data attribute	*/
$pasted = \Nino\Html::renderHtml( $appData, '[embed youtube="https://youtu.be/dQw4w9WgXcQ" title="x"]' );
check( 'a pasted address renders the surface with the no-cookie address in a data attribute and no iframe',
	str_contains( $pasted, 'data-embed-src="'. $youtube. '"' ) === true && str_contains( $pasted, '<iframe' ) === false
	&& preg_match( '/\s(?:src|srcset|data-src)\s*=\s*"[^"]*youtu/i', $pasted ) !== 1 );

echo "\n";


// --- The policy ------------------------------------------------------------------

echo "The Content-Security-Policy - the hosts the page's embeds talk to\n";

/*	The shipped policy has no frame-src, so default-src 'self' refuses every
	provider's frame: an embed that was pressed showed a blocked frame. The
	hosts are named in the output phase, from what the shortcodes rendered	*/
$request = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/' ];
\Nino\Http::request( $appData, $request );
$shipped = (string) $request['/nino/http/response']['header']['Content-Security-Policy'];

/**
 *	The policy one page ends up with: its body rendered from $html, the output
 *	callbacks run over a response that carries $policy
 *
 *	@param		array			&$appData
 *	@param		array			$request			A response from \Nino\Http::request()
 *	@param		string		$html					The page
 *	@param		string		$policy				What the response carries before the callbacks
 *	@param		mixed			$body					The body to run them over, where it is not the rendered html
 *
 *	@return 	string
 */
function embedPolicy( array &$appData, array $request, string $html, string $policy, mixed $body = null ): string {

	unset( $appData['./embed/frames'] );
	$rendered = \Nino\Html::renderHtml( $appData, $html );
	$request['/nino/http/response']['body'] = $body ?? $rendered;
	$request['/nino/http/response']['header']['Content-Security-Policy'] = $policy;
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );

	return (string) $request['/nino/http/response']['header']['Content-Security-Policy'];
}

check( 'the shipped policy has a default-src and no frame-src - the case this exists for',
	str_contains( $shipped, "default-src 'self'" ) === true && str_contains( $shipped, 'frame-src' ) === false );
check( 'a YouTube embed adds frame-src with \'self\' and the no-cookie host to it',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', $shipped ) === $shipped. "; frame-src 'self' https://www.youtube-nocookie.com" );
check( 'a Vimeo embed adds the player host',
	embedPolicy( $appData, $request, '[embed vimeo="76979871"]', $shipped ) === $shipped. "; frame-src 'self' https://player.vimeo.com" );
check( 'a url= embed keeps its port, since a source without one means 443 only',
	str_ends_with( embedPolicy( $appData, $request, '[embed url="https://www.openstreetmap.org:8443/export/embed.html?bbox=1"]', $shipped ), "frame-src 'self' https://www.openstreetmap.org:8443" ) === true );
check( 'a page with no embed leaves the policy byte for byte as it was',
	embedPolicy( $appData, $request, '<p>nothing here</p>', $shipped ) === $shipped
	&& embedPolicy( $appData, $request, '[embed youtube="../../evil"]', $shipped ) === $shipped );

$two = embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"][embed youtube="abcdefghijk"][embed vimeo="76979871"]', $shipped );
check( 'two embeds of one host give one source', substr_count( $two, 'https://www.youtube-nocookie.com' ) === 1 && substr_count( $two, 'https://player.vimeo.com' ) === 1 );

$has = "default-src 'self'; frame-src https://maps.example.org; img-src *";
check( 'an existing frame-src is extended in place, never given a second directive',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', $has ) === "default-src 'self'; frame-src https://maps.example.org https://www.youtube-nocookie.com; img-src *" );
check( '...and a host it already lists is not added again, in whatever case',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "default-src 'self'; frame-src HTTPS://WWW.YOUTUBE-NOCOOKIE.COM" ) === "default-src 'self'; frame-src HTTPS://WWW.YOUTUBE-NOCOOKIE.COM" );
check( 'a child-src-only policy gets a frame-src built from child-src\'s list - not child-src widened, which would widen workers too',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "child-src 'self' https://cdn.example; default-src 'none'" ) === "child-src 'self' https://cdn.example; default-src 'none'; frame-src 'self' https://cdn.example https://www.youtube-nocookie.com" );
check( 'a default-src that lists more than \'self\' is carried over rather than narrowed to \'self\'',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "default-src 'self' https://cdn.example" ) === "default-src 'self' https://cdn.example; frame-src 'self' https://cdn.example https://www.youtube-nocookie.com" );
check( 'a frame-src of \'none\' is the project\'s decision and stays',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "default-src 'self'; frame-src 'none'" ) === "default-src 'self'; frame-src 'none'" );
check( 'a default-src of \'none\' with nothing nearer stays too',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "default-src 'none'; img-src *" ) === "default-src 'none'; img-src *" );
check( 'a policy with no default-src is unrestricted already, and creating a frame-src would restrict it - it stays',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "img-src *; style-src 'self'" ) === "img-src *; style-src 'self'" );
check( 'an array body - an api answer - is not touched, nor is a response with no policy',
	embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', $shipped, [ 'ok' => true ] ) === $shipped
	&& embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', '' ) === '' );
check( 'the hosts come from the shortcode and not from the body: markup that says "nino-embed" without a shortcode adds nothing',
	embedPolicy( $appData, $request, '<div class="nino-embed" data-embed-src="https://evil.example/x"></div>', $shipped ) === $shipped );

// Another request must not inherit this one's hosts
unset( $appData['./embed/frames'] );

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
