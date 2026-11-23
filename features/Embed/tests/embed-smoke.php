<?php
declare(strict_types=1);

// The test of the feature Embed: structure and behaviour, one pin each. php features/Embed/tests/embed-smoke.php
$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );
check( 'the manifest validates and is written for this kernel, with no component of the old form, no data and Basis as its one required feature', is_array( $manifest ) === true && $manifest['key'] === 'embed' && \Nino\Features::satisfies( (string) $manifest['nino'] ) === true
	&& isset( $manifest['components'] ) === false && $manifest['data'] === [] && $manifest['requires'] === [ 'basis' ] && $manifest['assets'] === [ '/features/Embed/assets/embed.js' ] );
check( 'the settings are the Consent category that releases an embed without a press, empty to never, and the flag that lets one press carry the provider\'s others', array_keys( $manifest['settings'] ) === [ 'category', 'remember' ]
	&& $manifest['settings']['category']['default'] === 'external' && $manifest['settings']['remember']['default'] === false );

$appData = ninoSandbox( 'embed' );
$appData['./nino/filesystem/path'] = $root;
check( 'Basis and the feature are switched on', \Nino\Features::activate( $appData, 'basis' ) === true && \Nino\Features::activate( $appData, 'embed' ) === true );
$appData['/nino/modules'] = (array) ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'] ?? [] );
\Nino\Html::init( $appData );
\Nino\Features::registerComponents( $appData );
\Nino\Modules::callModules( $appData, 'init' );
\Nino\Features::registerParts( $appData );
// A picture slot of the project, for the surface
\Nino\Images::putSlot( $appData, '/template/page-home/clip/still', [ 'label' => 'Still', 'width' => 800, 'height' => 450, 'filename' => 'elements/clip/still.800x450.jpg' ] );
// By reference: the page is drawn from the app data as it is when it is drawn, a setting saved between two drawings included
$draw = static function( string $source ) use ( &$appData ): string {
	return trim( \Nino\Html::renderSource( $appData, '[section-wrap id="clip"][section-row][section-col]'. $source. '[/section-col][/section-row][/section-wrap]', [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) );
};
$ignored = ninoWarnings();

check( 'the folders are read without a reason', \Nino\Features::componentProblems( $appData, 'embed' ) === [] );
$schema = \Nino\Components::components( $appData )['embed'] ?? [];
check( 'the component is in its group, labelled in both languages, with an icon of the table and a standard part', ( $schema['group'] ?? '' ) === 'embed' && isset( $schema['label']['de_DE'], $schema['label']['en_US'] ) === true
	&& in_array( $schema['icon'] ?? '', \Nino\Components::ICONS, true ) === true && ( \Nino\Components::groups( $appData )['embed']['part'] ?? false ) === true );
check( 'every attribute has a label in both languages, the picture on the surface is a picture slot and the title is a text', array_filter( $schema['attributes'] ?? [], static fn( array $a ): bool => isset( $a['label']['de_DE'], $a['label']['en_US'] ) === false ) === []
	&& ( $schema['attributes']['poster']['type'] ?? '' ) === 'image' && ( $schema['attributes']['title']['type'] ?? '' ) === 'text' );
$part = (string) file_get_contents( $dir. '/groups/embed/part/part.css' );
check( 'the standard part keeps the rule of mixing, is in the style bundle and fetches nothing: no url(), no import', \Nino\Components::partProblems( $part, [ 'embed' ] ) === [] && str_contains( \Nino\Modules\Assets::stylesheet( $appData, '/.cache/style.css' ), '.embed-open' ) === true
	&& preg_match( '/url\(|@import/i', $part ) === 0 );
check( 'a tool offers it in a column', in_array( 'embed', \Nino\Components::allowed( $appData, 'section-col' ), true ) === true );

// THE BOUNDARY: nothing is asked of the provider before somebody presses
$pasted = $draw( '[embed youtube="https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s" title="A film"][embed vimeo="76979871"][embed url="https://www.openstreetmap.org/export/embed.html?bbox=1%2C2" poster="~still"]' );
$fetched = [];
foreach( [ '/<(?:iframe|embed|object|video|audio|source|track|script)\b/i', '/<link\b/i' ] as $pattern )
	if( preg_match( $pattern, $pasted ) === 1 )
		$fetched[] = $pattern;
preg_match_all( '/\s(?:src|srcset|data|poster|data-src|href|action|formaction|ping|background)\s*=\s*"([^"]*)"/i', $pasted, $attributes );
$provider = array_filter( $attributes[1], static fn( string $value ): bool => preg_match( '/youtu|vimeo|openstreetmap/i', $value ) === 1 );
check( 'what the server sends has no iframe and nothing a browser fetches: no element that loads, and no src, srcset, poster or href that names a provider but the link out of the <noscript>',
	$fetched === [] && count( $provider ) === 3 && array_filter( $provider, static fn( string $value ): bool => preg_match( '/<a href="'. preg_quote( $value, '/' ). '" target="_blank" rel="noopener noreferrer">/', $pasted ) !== 1 ) === [] );
check( 'the address is carried in a data attribute, once for each embed, and the link out of the <noscript> opens in a new tab with no opener and no referrer',
	substr_count( $pasted, 'data-embed-src="' ) === 3 && substr_count( $pasted, '<noscript><p class="embed-fallback"><a href="' ) === 3 && substr_count( $pasted, 'target="_blank" rel="noopener noreferrer"' ) === 3 );
check( 'the surface is hidden until the script has it, and the name of a frame that will be there is on the box', substr_count( $pasted, '<button type="button" class="embed-open" hidden>' ) === 3 && str_contains( $pasted, 'data-embed-title="A film"' ) === true );
check( 'the picture on the surface is a picture slot of the project - and with none there is no picture at all, never the provider\'s thumbnail', preg_match( '/<img class="embed-poster" src="[^"]*still\.800x450\.jpg" alt=""/', $pasted ) === 1 && substr_count( $pasted, '<img' ) === 1
	&& str_contains( $draw( '[embed youtube="dQw4w9WgXcQ"]' ), '<img' ) === false && str_contains( $draw( '[embed youtube="dQw4w9WgXcQ" poster="https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg"]' ), '<img' ) === false && str_contains( $draw( '[embed youtube="dQw4w9WgXcQ" poster="photo.jpg"]' ), '<img' ) === false );

// THE BOUNDARY: only a video of the provider's own hosts, an id of its own shape, or an https address that names an origin becomes a frame address
$src = static fn( string $call ): string => preg_match( '/data-embed-src="([^"]*)"/', $draw( $call ), $found ) === 1 ? html_entity_decode( $found[1] ) : '';
$youtube = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0';
$vimeo = 'https://player.vimeo.com/video/76979871?dnt=1';
$lost = array_filter( [ 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://m.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=7', 'http://youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtu.be/dQw4w9WgXcQ?si=abc', 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ', ' dQw4w9WgXcQ ' ], static fn( string $to ): bool => $src( '[embed youtube="'. $to. '"]' ) !== $youtube );
check( 'youtube takes the id and every address the browser has for a video - watch, share, short, short link, embed - and writes the no-cookie player, only: nothing of the rest of the address'. ( $lost === [] ? '' : ' - lost: '. implode( ', ', $lost ) ), $lost === [] );
$lost = array_filter( [ '76979871', 'https://vimeo.com/76979871', 'https://vimeo.com/channels/staffpicks/76979871', 'https://player.vimeo.com/video/76979871', 'https://vimeo.com/showcase/123/video/76979871' ], static fn( string $to ): bool => $src( '[embed vimeo="'. $to. '"]' ) !== $vimeo );
check( 'vimeo takes the id and the addresses of its video pages and writes the player with the do-not-track flag, and an unlisted video keeps its hash and nothing else'. ( $lost === [] ? '' : ' - lost: '. implode( ', ', $lost ) ), $lost === []
	&& $src( '[embed vimeo="https://vimeo.com/76979871/abcdef1234?utm_source=x"]' ) === 'https://player.vimeo.com/video/76979871?h=abcdef1234&dnt=1' );
$ignored = ninoWarnings();
$refused = [
	'youtube' => [ '../../evil', 'dQw4w9WgXc"onload="x', 'dQw4w9WgXcQ/../x', 'javascript:alert(1)', 'data:text/html,x', '//www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com@evil.example/watch?v=dQw4w9WgXcQ', 'https://user:pw@www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com:8443/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/playlist?list=PL1234567890', 'https://www.youtube.com/embed/videoseries?list=PL1', 'https://vimeo.com/76979871', 'ftp://www.youtube.com/watch?v=dQw4w9WgXcQ' ],
	'vimeo'   => [ '12', 'abc', 'https://vimeo.com.evil.example/76979871', 'https://vimeo.com/channels/staffpicks', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'javascript:alert(1)' ],
];
$drawn = [];
$unsaid = [];
$dirty = [];
foreach( $refused as $attribute => $values )
	foreach( $values as $value ) {
		ninoWarnings();
		if( \Nino\Modules\Embed::source( [ $attribute => $value ] ) !== '' || $src( '[embed '. $attribute. '="'. str_replace( '"', '&quot;', $value ). '"]' ) !== '' )
			$drawn[] = $attribute. '='. $value;
		ninoWarnings();
		$logged = [];
		\Nino\Modules\Embed::source( [ $attribute => $value ] );
		$logged = ninoWarnings();
		if( count( $logged ) !== 1 )
			$unsaid[] = $attribute. '='. $value;
		if( array_filter( $logged, static fn( string $w ): bool => preg_match( '/[\x00-\x1F]/', $w ) === 1 ) !== [] )
			$dirty[] = $attribute. '='. $value;
	}
check( 'an id that is none and an address that is no video of the provider - another host that only begins or ends like it, credentials, a port, another scheme, a playlist, a channel - draw nothing'. ( $drawn === [] ? '' : ' - drawn: '. implode( ' | ', $drawn ) ), $drawn === [] );
check( 'a video address that is refused is said out loud, once each, and an empty one is skipped silently'. ( $unsaid === [] ? '' : ' - not once: '. implode( ' | ', $unsaid ) ), $unsaid === [] && $dirty === [] && $src( '[embed youtube=""]' ) === '' && ninoWarnings() === [] );
\Nino\Modules\Embed::source( [ 'youtube' => "x\n[FORGED] ". str_repeat( 'y', 400 ) ] );
$long = ninoWarnings();
check( 'a long address with a line end in it reaches the log on one line, cut', count( $long ) === 1 && preg_match( '/[\x00-\x1F]/', $long[0] ) === 0 && strlen( $long[0] ) < 320 );

$kept = array_filter( [ 'https://www.openstreetmap.org/export/embed.html?bbox=1%2C2' => 'https://www.openstreetmap.org/export/embed.html?bbox=1%2C2', 'https://maps.example.org:8443/embed?a=1&b=2' => 'https://maps.example.org:8443/embed?a=1&b=2', 'https://player.vimeo.com/video/76979871?h=abc&autoplay=1' => 'https://player.vimeo.com/video/76979871?h=abc&autoplay=1' ], static fn( string $want, string $given ): bool => $src( '[embed url="'. $given. '"]' ) !== $want, ARRAY_FILTER_USE_BOTH );
check( 'url takes an https address of any other host as it was written, a player address with its own parameters too, and the & of a query is written once as an entity', $kept === []
	&& str_contains( $draw( '[embed url="https://maps.example.org/embed?a=1&b=2"]' ), 'data-embed-src="https://maps.example.org/embed?a=1&amp;b=2"' ) === true );
check( 'url with the watch page of a video is the player of the provider, the same as youtube would write', $src( '[embed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1"]' ) === $youtube && $src( '[embed url="https://vimeo.com/76979871"]' ) === $vimeo );
$allowed = array_filter( [ 'http://maps.example.org/embed', 'javascript:alert(1)', 'data:text/html,<b>x</b>', '//maps.example.org/embed', 'maps.example.org/embed', 'https://user:pw@maps.example.org/embed', 'https://127.0.0.1/embed', 'https://[::1]/embed', 'https://0x7f.0.0.1/embed', 'https://2130706433/embed', 'https://m' . "\u{00fc}" . 'nchen.example/embed', "https://maps.example.org/embed\nhttps://evil.example/", 'file:///etc/passwd', 'ftp://maps.example.org/embed' ], static fn( string $to ): bool => $src( '[embed url="'. $to. '"]' ) !== '' );
check( 'url draws nothing for another scheme, an address with credentials, an IP address in any form, a name that is not plain ASCII or one with a line end in it: only https and an origin the policy can name'. ( $allowed === [] ? '' : ' - drawn: '. implode( ' | ', $allowed ) ), $allowed === [] );
check( 'a call that names nothing, or only a title and a picture, draws nothing: a box with no frame behind it is worse than no box', $src( '[embed]' ) === '' && str_contains( $draw( '[embed title="A film" poster="~still"]' ), 'class="embed' ) === false );

// Title, surface, shape, the category and the flag
\Nino\Html::addFills( $appData, [ '/feature/embed/placeholder/button' => 'Load it', '/feature/embed/frame/title' => 'External thing' ], '*' );
$plain = $draw( '[embed youtube="dQw4w9WgXcQ"]' );
check( 'without a title the surface and the frame still have a name from the words of the project, and the host that is named on the surface loses its www.', preg_match( '/data-embed-title="([^"]+)"/', $plain, $name ) === 1 && $name[1] !== '' && str_contains( $name[1], '[[' ) === false
	&& str_contains( $plain, 'data-embed-host="youtube-nocookie.com"' ) === true && str_contains( $plain, 'class="embed-note"' ) === true && str_contains( $plain, 'embed-note">' ) === true && str_contains( $plain, ' www.youtube-nocookie.com' ) === false );
$hostile = $draw( '[embed youtube="dQw4w9WgXcQ" title="&quot;><script>alert(1)</script> [csrf] & more"]' );
check( 'a title that spells a tag, a call or an attribute is text, escaped once, on the box, the surface and the frame', str_contains( $hostile, '<script' ) === false && str_contains( $hostile, 'name="csrf"' ) === false
	&& str_contains( $hostile, 'data-embed-title="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] &amp; more"' ) === true && str_contains( $hostile, '<span class="embed-label">&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt; [csrf] &amp; more</span>' ) === true );
check( 'the default shape is 16:9 and writes no class, a named one is a class and one that is none of the four is the default', str_contains( $plain, 'class="embed"' ) === true && str_contains( $draw( '[embed youtube="dQw4w9WgXcQ" ratio="21-9"]' ), 'class="embed embed--ratio-21-9"' ) === true
	&& str_contains( $draw( '[embed youtube="dQw4w9WgXcQ" ratio="9-16;x"]' ), 'class="embed"' ) === true );
check( 'the consent category of the setting is written for the script to read, and an empty one is written empty: the embed can only be pressed', str_contains( $plain, 'data-embed-consent="external"' ) === true && str_contains( $plain, 'data-embed-remember' ) === false
	&& \Nino\Features::saveSettings( $appData, 'embed', [ 'category' => 'marketing', 'remember' => true ] ) === [] && str_contains( $draw( '[embed youtube="dQw4w9WgXcQ"]' ), 'data-embed-consent="marketing" data-embed-host="youtube-nocookie.com" data-embed-remember="1"' ) === true
	&& \Nino\Features::saveSettings( $appData, 'embed', [ 'category' => '' ] ) === [] && str_contains( $draw( '[embed youtube="dQw4w9WgXcQ"]' ), 'data-embed-consent=""' ) === true
	&& array_keys( \Nino\Features::saveSettings( $appData, 'embed', [ 'category' => 'a"b;c' ] ) ) === [ 'category' ] );
\Nino\Features::saveSettings( $appData, 'embed', [ 'category' => 'external', 'remember' => false ] );

// The policy: the frames of the page's embeds, and nothing else
$request = [ 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/' ];
\Nino\Http::request( $appData, $request );
$shipped = (string) $request['/nino/http/response']['header']['Content-Security-Policy'];

/**
 *	The policy one page ends up with: its body rendered from $source, the output callbacks run over a response that carries $policy
 *
 *	@param		array			&$appData
 *	@param		array			$request			A response from \Nino\Http::request()
 *	@param		string		$source				The page
 *	@param		string		$policy				What the response carries before the callbacks
 *	@param		mixed			$body					The body to run them over, where it is not the rendered html
 *
 *	@return 	string
 */
function embedPolicy( array &$appData, array $request, string $source, string $policy, mixed $body = null ): string {

	unset( $appData['./embed/frames'] );
	$rendered = \Nino\Html::renderSource( $appData, $source, \Nino\Html::context( $appData ) );
	$request['/nino/http/response']['body'] = $body ?? $rendered;
	$request['/nino/http/response']['header']['Content-Security-Policy'] = $policy;
	\Nino\Callbacks::doCallbacks( $appData, '/nino/http/output', $request );

	return (string) $request['/nino/http/response']['header']['Content-Security-Policy'];
}

check( 'the shipped policy has a default-src and no frame-src, and a page with an embed names the origin of each in a frame-src of its own - the port too, once for two embeds of one host',
	str_contains( $shipped, "default-src 'self'" ) === true && str_contains( $shipped, 'frame-src' ) === false
	&& embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"][embed youtube="abcdefghijk"]', $shipped ) === $shipped. "; frame-src 'self' https://www.youtube-nocookie.com"
	&& embedPolicy( $appData, $request, '[embed vimeo="76979871"]', $shipped ) === $shipped. "; frame-src 'self' https://player.vimeo.com"
	&& str_ends_with( embedPolicy( $appData, $request, '[embed url="https://maps.example.org:8443/embed"]', $shipped ), "frame-src 'self' https://maps.example.org:8443" ) === true );
check( 'a page with no embed, or one whose call names no address, leaves the policy byte for byte, and so does markup that only says embed: the hosts come from what the component wrote and not from the body',
	embedPolicy( $appData, $request, '<p>nothing here</p>', $shipped ) === $shipped && embedPolicy( $appData, $request, '[embed youtube="../../evil"]', $shipped ) === $shipped
	&& embedPolicy( $appData, $request, '<div class="embed" data-embed-src="https://evil.example/x"></div>', $shipped ) === $shipped );
check( 'a frame-src of \'none\' is the project\'s decision and stays, and so does an api answer with no html body', embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', "default-src 'self'; frame-src 'none'" ) === "default-src 'self'; frame-src 'none'"
	&& embedPolicy( $appData, $request, '[embed youtube="dQw4w9WgXcQ"]', $shipped, [ 'ok' => true ] ) === $shipped );
$adminRequest = $request;
$adminRequest['/nino/http/request']['uri'] = '/_admin/panel';
$notAdmin = $request;
$notAdmin['/nino/http/request']['uri'] = '/_administrator';
check( 'a response of the workbench is left alone, its policy byte for byte, while a page that only starts with the same letters is a page of the site', embedPolicy( $appData, $adminRequest, '[embed youtube="dQw4w9WgXcQ"]', $shipped ) === $shipped
	&& embedPolicy( $appData, $notAdmin, '[embed youtube="dQw4w9WgXcQ"]', $shipped ) === $shipped. "; frame-src 'self' https://www.youtube-nocookie.com" );
unset( $appData['./embed/frames'] );

// Words, the privacy sections, the demo, the block
$words = [ 'de_DE' => array_keys( (array) include $dir. '/install/text/de_DE.php' ), 'en_US' => array_keys( (array) include $dir. '/install/text/en_US.php' ) ];
preg_match_all( '/\[\[(\/feature\/embed\/[a-z-]+\/[a-z-]+)\]\]/', (string) file_get_contents( $dir. '/components/embed/template.tpl' ). (string) file_get_contents( $dir. '/Embed.php' ), $read );
$read = array_unique( array_merge( $read[1], [ '/feature/embed/frame/title', '/feature/embed/placeholder/button' ] ) );
check( 'both languages bring the same four words, and every word the template and the renderer read is among them', $words['de_DE'] === $words['en_US'] && count( $words['en_US'] ) === 4 && array_diff( array_map( static fn( string $key ): string => '[['. $key. ']]', $read ), $words['en_US'] ) === [] );
$unit = (array) include $dir. '/install/manifest.php';
$privacy = (array) include $dir. '/install/elements/privacy.php';
check( 'the unit brings the sections of the privacy policy for embedded content, YouTube and Vimeo, in both languages, and names the file', ( $unit['elements']['privacy'] ?? '' ) === 'elements/privacy.php' && array_keys( $privacy['*'] ?? [] ) === [ 'embed', 'embed-youtube', 'embed-vimeo' ]
	&& array_keys( $privacy['de_DE'] ?? [] ) === array_keys( $privacy['*'] ) && array_keys( $privacy['en_US'] ?? [] ) === array_keys( $privacy['*'] ) );
$groups = \Nino\Components::groups( $appData );
check( 'the demo of the group is read without a note and draws the surface twice', ( $groups['embed']['demo'] ?? '' ) !== '' && substr_count( trim( \Nino\Html::renderSource( $appData, $groups['embed']['demo'], [ 'template' => 'page-home' ] + \Nino\Html::context( $appData ) ) ), 'class="embed-open"' ) === 2 );
$block = \Nino\Blocks::get( $appData, 'embed-video' );
check( 'the block is listed with the feature as its origin, valid and without a missing feature', is_array( $block ) === true && in_array( 'embed', $block['features'] ?? [], true ) === true && ( $block['missing'] ?? [] ) === [] && \Nino\Blocks::rejected( $appData ) === [] );

// Consent: the two features meet over markup, and each names what the other reads
$consent = dirname( $dir ). '/Consent/assets/consent.js';
$script = (string) file_get_contents( $dir. '/assets/embed.js' );
check( 'the script asks the category that Consent writes on the root element and listens for the event Consent fires, and carries the attribute and the flag that Consent reads - no import', str_contains( $script, "'data-consent'" ) === true && str_contains( $script, "'nino:consent'" ) === true
	&& str_contains( $script, "'data-embed-consent'" ) === true && str_contains( $script, 'embedLoaded' ) === true && str_contains( $script, 'Nino.modules' ) === false );
if( is_file( $consent ) === true )
	check( 'the script of Consent, where the feature stands beside this one, names the same attribute and the same flag, and writes what the embed asks', str_contains( (string) file_get_contents( $consent ), 'data-embed-consent' ) === true && str_contains( (string) file_get_contents( $consent ), 'embedLoaded' ) === true
		&& str_contains( (string) file_get_contents( $consent ), "'nino:consent'" ) === true && str_contains( (string) file_get_contents( $consent ), 'dataset.consent' ) === true );
else
	echo "  --  - the feature Consent does not stand beside this one: the pin on its script was NOT run\n";
check( 'the script builds the frame itself and gives it an https address only', str_contains( $script, "createElement( 'iframe' )" ) === true && str_contains( $script, "'https://'" ) === true );

// Where the feature is off the call draws nothing
$off = ninoSandbox( 'embed-off' );
\Nino\Html::init( $off );
\Nino\Features::registerComponents( $off );
check( 'where the feature is off the call draws nothing', \Nino\Html::renderSource( $off, '[embed youtube="dQw4w9WgXcQ"]', \Nino\Html::context( $off ) ) === '' );
$ignored = ninoWarnings();

// Nothing of the old vocabulary is left in the folder
$old = [];
foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) ) as $file )
	if( str_contains( (string) $file, '/tests/' ) === false && preg_match( '/Feature::shortcode|addShortcode|Modules\\\\Components|nino-embed|nino-video|nino-is-loaded|--color-|--space-[0-9]|--radius\b|--fontsize-|rgba\(|#fff/', (string) file_get_contents( (string) $file ) ) === 1 )
		$old[] = (string) $file;
check( 'no file of the feature names the vocabulary of Nino 1, and none writes a colour of its own', $old === [] && is_dir( $dir. '/templates' ) === false );

// The script that builds the frame runs over the kernel's stand-in of a page (embed-js-smoke.js)
$node = function_exists( 'shell_exec' ) === true ? trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) ) : '';
if( $node === '' || function_exists( 'exec' ) === false )
	echo "  --  - node is not available here: embed-js-smoke.js was NOT run\n";
else {
	$output = [];
	$exit = 1;
	exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/embed-js-smoke.js' ). ' 2>&1', $output, $exit );
	check( 'embed-js-smoke.js passes - '. ( $output === [] ? 'no output' : end( $output ) ), $exit === 0 );
}

ninoDone( $appData );
