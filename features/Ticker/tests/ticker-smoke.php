<?php
declare(strict_types=1);

/**
 *	Nino
 *	ticker-smoke.php	Contract test for the Ticker feature (Modules\Ticker): the
 *										manifest, the activation through \Nino\Features, the two
 *										files it lists under assets, which the kernel puts into
 *										the site's own asset bundles
 *										(\Nino\Features::registerAssets()), the one text fill its
 *										install unit merges for the pause button, what those two
 *										files promise, the component [ticker] that holds components - its
 *										schema as the Builder reads it, the row it renders around the
 *										calls between its tags, and the data attributes it writes only
 *										where a call differs from what ticker.js does without them - and
 *										deactivation. A row written by hand is still the markup a
 *										project's own template carries, and the manual keeps its lines.
 *
 *										What the two files then do is the browser's, and
 *										ticker-js-smoke.js beside this file measures it, over
 *										the kernel's own Nino.js and Nino.ui.js from the same
 *										checkout; this test runs that one too where node is on
 *										the path.
 *
 *	Usage: php features/Ticker/tests/ticker-smoke.php
 *	       NINO_ROOT=../nino php features/Ticker/tests/ticker-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'ticker' );
$appData['/nino/dir'] = '';


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "ticker"', is_array( $manifest ) && $manifest['key'] === 'ticker' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

check( 'it is filed under ui', $manifest['category'] === 'ui' );

/*	What a row is timed with belongs to the row being run rather than to the site - a logo bar in a footer and a line of
	announcements in a header are not one speed - so there is no setting, and the component carries the timing as attributes	*/
check( 'it brings no route, and its manual names the shortcode of the component and every attribute it takes, in both languages',
	$manifest['manual']['routes'] === [] && array_keys( $manifest['manual']['shortcodes'] ) === [ '[ticker]...[/ticker]', '[ticker speed="60"]', '[ticker direction="right"]', '[ticker loop="1"]', '[ticker toggle="1"]', '[ticker pause="0"]' ]
	&& array_filter( $manifest['manual']['shortcodes'], static fn( array $line ): bool => ( $line['en_US'] ?? '' ) === '' || ( $line['de_DE'] ?? '' ) === '' || $line['en_US'] === $line['de_DE'] ) === [] );
check( '...and the markup of a row written by hand, the data attributes below it',
	array_key_exists( 'data-ticker-speed="40"', $manifest['manual']['markup'] ) === true );
check( '...and the track inside the row, the one element the script runs',
	array_key_exists( 'class="nino-ticker-track"', $manifest['manual']['markup'] ) === true );
check( '...and the loop and the pause button, which are opt-in',
	array_key_exists( 'data-ticker-loop="1"', $manifest['manual']['markup'] ) === true
	&& array_key_exists( 'data-ticker-toggle="[[/feature/ticker/pause/label]]"', $manifest['manual']['markup'] ) === true );
check( 'it requires no other feature, keeps no data and carries no settings',
	$manifest['requires'] === [] && $manifest['data'] === [] && $manifest['settings'] === [] );
check( 'it lists its stylesheet and its script under assets, which is all it asks the kernel for',
	$manifest['assets'] === [ '/features/Ticker/assets/ticker.css', '/features/Ticker/assets/ticker.js' ] );
check( 'its install unit brings the pause button\'s label and nothing a project has to write for itself, and the manual lists it',
	array_keys( $manifest['manual']['install'] ) === [ 'text/<locale>.php' ] && is_file( $dir. '/install/manifest.php' ) === true
	&& is_file( $dir. '/install/text/en_US.php' ) === true && is_file( $dir. '/install/text/de_DE.php' ) === true );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'ticker' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Ticker', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'ticker' )['installed'] === $manifest['version'] );
check( 'activation merged the pause button\'s label into the text file of both locales',
	( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Pause animation'
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/de_DE.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Animation pausieren' );
check( '...and wrote no template into the project', is_dir( ninoSandboxDir( $appData ). '/templates' ) === false );

/*	Add-only: what a project has written for that key is its own, and an update
	- activating an active feature - leaves it as it is	*/
\Nino\Filesystem::putFileContent( $appData, '/text/en_US.php', [ '[[/feature/ticker/pause/label]]' => 'Stop it' ] );
check( 'an update keeps the value a project has for that key', \Nino\Features::activate( $appData, 'ticker' ) === true
	&& ( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' ) === 'Stop it' );

echo "\n";


// --- The assets --------------------------------------------------------------

echo "The manifest's assets - the bundle, and nothing besides\n";

// The order \Nino\init() takes: the assets of the manifests, then the init() of every module
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'ticker.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Ticker/assets/ticker.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'ticker.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Ticker/assets/ticker.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'and it registered no route at all', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );
/*	The manifest declares the component and the Components module finds the renderer by its name, as it does for a feature
	that has none to register: there is nothing for an init() to do	*/
check( 'the class has no init() and no state: the renderer is componentTicker(), which the module derives from the name of the component',
	method_exists( \Nino\Modules\Ticker::class, 'init' ) === false && method_exists( \Nino\Modules\Ticker::class, 'componentTicker' ) === true );

check( 'the generated cache file carries this feature\'s css', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/style.css]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/style.css', '' ), '.nino-ticker' );
} )() );
check( '...and its js', ( static function() use ( &$appData ): bool {
	\Nino\Html::renderHtml( $appData, '[assets /.cache/script.js]' );
	return str_contains( (string) \Nino\Filesystem::getFileContent( $appData, '/.cache/script.js', '' ), 'nino-ticker-track' );
} )() );

echo "\n";


// --- The component -------------------------------------------------------------

echo "The component - [ticker] as the Builder offers it\n";

$declared = $manifest['components']['ticker'] ?? [];

check( 'the manifest declares one component, a row that holds components: the content is its source, the place of a section is what it holds, and a loop does not take it',
	array_keys( $manifest['components'] ?? [] ) === [ 'ticker' ] && ( $declared['source'] ?? '' ) === 'content' && ( $declared['holds'] ?? '' ) === 'section' && ( $declared['loop'] ?? true ) === false );
check( '...it says what the Builder draws it as, what it draws and where it stands: a block, a div of the class a theme addresses the row by, in a section - not in a card, which holds components itself',
	( $declared['preview'] ?? '' ) === 'block' && ( $declared['draws'] ?? '' ) === 'div .nino-ticker' && ( $declared['in'] ?? [] ) === [ 'section' ] );
check( '...its picture is two chevrons: paths of a 24 by 24 stroke icon, each a tag and a map of strings',
	count( $declared['icon'] ?? [] ) === 2 && array_filter( $declared['icon'], static fn( mixed $part ): bool => is_array( $part ) === true && $part[0] === 'path' && array_keys( $part[1] ) === [ 'd' ] && is_string( $part[1]['d'] ) === true ) === $declared['icon'] );
check( '...and its label is told in both languages, the words of the two differing',
	is_array( $declared['label'] ?? null ) === true && ( $declared['label']['en_US'] ?? '' ) !== '' && ( $declared['label']['de_DE'] ?? '' ) !== '' && $declared['label']['en_US'] !== $declared['label']['de_DE'] );

$attributes = $declared['attributes'] ?? [];
check( 'it takes the five things a row is timed with, in this order: speed, direction, loop, toggle and pause - each with a label and, but for the direction, a hint in both languages',
	array_keys( $attributes ) === [ 'speed', 'direction', 'loop', 'toggle', 'pause' ]
	&& array_filter( $attributes, static fn( array $attribute, string $name ): bool => is_array( $attribute['label'] ?? null ) === false || ( $attribute['label']['en_US'] ?? '' ) === '' || ( $attribute['label']['de_DE'] ?? '' ) === ''
		|| ( $name !== 'direction' && ( ( $attribute['hint']['en_US'] ?? '' ) === '' || ( $attribute['hint']['de_DE'] ?? '' ) === '' ) ), ARRAY_FILTER_USE_BOTH ) === [] );
check( '...the types are the ones the data attributes mean: the speed a whole number of pixels a second, the direction a choice between the two ways, the other three switches',
	( $attributes['speed']['type'] ?? '' ) === 'int' && ( $attributes['speed']['min'] ?? 0 ) >= 1 && ( $attributes['direction']['type'] ?? '' ) === 'select' && ( $attributes['direction']['options'] ?? null ) === '\\Nino\\Modules\\Ticker::directions'
	&& array_filter( [ 'loop', 'toggle', 'pause' ], static fn( string $name ): bool => ( $attributes[$name]['type'] ?? '' ) !== 'bool' ) === [] );

$js		= (string) file_get_contents( $dir. '/assets/ticker.js' );
$css	= (string) file_get_contents( $dir. '/assets/ticker.css' );
preg_match( '/var SPEED_DEFAULT = (\d+);/', $js, $speed );
check( 'every default is what the script and the stylesheet do for a row that says nothing: the speed of the script\'s SPEED_DEFAULT, to the left, one cycle, no pause button - and the pointer stops the row',
	( $attributes['speed']['default'] ?? null ) === (int) ( $speed[1] ?? 0 ) && (int) ( $speed[1] ?? 0 ) === \Nino\Modules\Ticker::SPEED_DEFAULT
	&& ( $attributes['direction']['default'] ?? null ) === 'left' && ( $attributes['loop']['default'] ?? null ) === false && ( $attributes['toggle']['default'] ?? null ) === false && ( $attributes['pause']['default'] ?? null ) === true
	&& str_contains( $css, '.nino-ticker[data-ticker-pause="off"]' ) === true && str_contains( $css, '.nino-ticker[data-ticker-direction="right"]' ) === true
	&& preg_match( "/speed\s*:\s*\{[^}]*fallback : SPEED_DEFAULT/", $js ) === 1 && preg_match( "/loop\s*:\s*\{[^}]*fallback : false/", $js ) === 1 );

// The Components module registers the component from the manifest, as a boot does, on a copy of the project that nothing else here sees
$probe = $appData;
$probe['/nino/modules'][] = '\\Nino\\Modules\\Components';
\Nino\Modules\Components::init( $probe );
ninoWarnings();

$issued = \Nino\Modules\Components::components( $probe )['ticker'] ?? [];
$issuedWay = $issued['attributes']['direction'] ?? [];
$asDeclared = $declared;
$asIssued = $issued;
unset( $asDeclared['attributes']['direction'], $asIssued['attributes']['direction'] );

check( 'the Components module has it as the manifest says but for the direction, whose options it asked the method for, and its defaults are strings: 40, left, off, off and on - the class last',
	$asIssued === $asDeclared && ( $issuedWay['type'] ?? '' ) === 'select' && ( $issuedWay['default'] ?? '' ) === 'left'
	&& \Nino\Modules\Components::defaults( $probe, 'ticker' ) === [ 'speed' => '40', 'direction' => 'left', 'loop' => '0', 'toggle' => '0', 'pause' => '1', 'class' => '' ] );

check( 'the options of the direction are asked of a method of the feature\'s own class and the schema that is issued carries them: the two values the data attribute knows, and for each a label in both languages, the words of the two differing',
	( $issuedWay['options'] ?? [] ) === [ 'left', 'right' ] && array_keys( $issuedWay['optionLabels'] ?? [] ) === [ 'left', 'right' ] && in_array( $issuedWay['default'] ?? null, $issuedWay['options'] ?? [], true ) === true
	&& array_filter( $issuedWay['optionLabels'] ?? [], static fn( mixed $label ): bool => is_array( $label ) === false || ( $label['en_US'] ?? '' ) === '' || ( $label['de_DE'] ?? '' ) === '' || $label['en_US'] === $label['de_DE'] ) === []
	&& ( $issuedWay['optionLabels']['left'] ?? null ) !== ( $issuedWay['optionLabels']['right'] ?? null ) && ninoWarnings() === [] );

/**
 *	A rendered row as a document: the row and what stands in it, read the way a browser reads it
 *
 *	@param		string		$html
 *
 *	@return 	\DOMXPath
 */
$parsed = static function( string $html ): \DOMXPath {

	$document = new \DOMDocument();
	@$document->loadHTML( '<?xml encoding="utf-8"?><body>'. $html. '</body>' );

	return new \DOMXPath( $document );
};

\Nino\Locales::useLocale( $probe, 'en_US' );
// What the project made of the label - the add-only check above left it as the project wrote it
$label = (string) ( \Nino\Filesystem::getFileContent( $probe, '/text/en_US.php', [] )['[[/feature/ticker/pause/label]]'] ?? '' );

$row = \Nino\Html::renderHtml( $probe, '[ticker][title text="Fast"][text text="Slow"][button text="Go"][/ticker]' );
$dom = $parsed( $row );

check( 'a row is a box around a track around what stands between the tags: one div of the class nino-ticker in the body, one div of the class nino-ticker-track as its only child',
	$dom->query( '/html/body/*' )->length === 1 && $dom->query( '/html/body/div[@class="nino-ticker"]/*' )->length === 1 && $dom->query( '/html/body/div[@class="nino-ticker"]/div[@class="nino-ticker-track"]' )->length === 1 );
check( '...the calls are rendered, in the order they were written, and each is a child of the track - the items that run past - with no call left in the page',
	$dom->query( '//div[@class="nino-ticker-track"]/*' )->length === 3
	&& $dom->query( '//div[@class="nino-ticker-track"]/h3[@class="nino-section-title"]' )->item( 0 )?->textContent === 'Fast'
	&& str_contains( (string) $dom->query( '//div[@class="nino-ticker-track"]/div' )->item( 0 )?->textContent, 'Slow' ) === true
	&& $dom->query( '//div[@class="nino-ticker-track"]/span[@class="nino-btn"]' )->item( 0 )?->textContent === 'Go' && preg_match( '/\[\/?(?:ticker|title|text|button)[ \]]/', $row ) === 0 );
check( '...and a row that says nothing carries no data attribute at all - the box and the track are what a row written by hand is',
	str_contains( $row, 'data-ticker' ) === false && $dom->query( '/html/body/div/@*' )->length === 1 );
check( '...with every attribute written out at its default it is the same row',
	\Nino\Html::renderHtml( $probe, '[ticker speed="40" direction="left" loop="0" toggle="0" pause="1"][title text="Fast"][text text="Slow"][button text="Go"][/ticker]' ) === $row
	&& \Nino\Html::renderHtml( $probe, '[ticker loop="no" toggle="off" pause="yes"][title text="Fast"][text text="Slow"][button text="Go"][/ticker]' ) === $row );

$attributesOf = static function( string $call ) use ( $probe, $parsed ): array {

	$html		= \Nino\Html::renderHtml( $probe, $call );
	$names	= [];

	foreach( $parsed( $html )->query( '/html/body/div/@*' ) as $attribute )
		$names[$attribute->nodeName] = $attribute->nodeValue;

	return $names;
};

check( 'a data attribute is written only where the call differs: another speed writes data-ticker-speed and nothing else',
	$attributesOf( '[ticker speed="60"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-speed' => '60' ] );
check( '...the other direction writes data-ticker-direction="right"',
	$attributesOf( '[ticker direction="right"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-direction' => 'right' ] );
check( '...the loop writes data-ticker-loop="1", which is the word the script reads',
	$attributesOf( '[ticker loop="1"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-loop' => '1' ] );
check( '...the pause button writes data-ticker-toggle with the label the install unit merged - the fill resolved, as the markup of a row written by hand has it',
	$attributesOf( '[ticker toggle="1"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-toggle' => $label ] );
check( '...and a row that keeps running under the pointer writes data-ticker-pause="off"',
	$attributesOf( '[ticker pause="0"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-pause' => 'off' ] );
check( '...all of them at once, with a class of its own that comes after the row\'s',
	$attributesOf( '[ticker speed="90" direction="right" loop="1" toggle="1" pause="0" class="logos"][text text="a"][/ticker]' )
		=== [ 'class' => 'nino-ticker logos', 'data-ticker-speed' => '90', 'data-ticker-direction' => 'right', 'data-ticker-loop' => '1', 'data-ticker-toggle' => $label, 'data-ticker-pause' => 'off' ] );
check( '...a speed that is no number, or none the script would run, is the default, and a speed out of the range is held to it',
	$attributesOf( '[ticker speed="fast"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker' ] && $attributesOf( '[ticker speed="0"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-speed' => '1' ]
	&& $attributesOf( '[ticker speed="99999"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker', 'data-ticker-speed' => '1000' ] && $attributesOf( '[ticker direction="up"][text text="a"][/ticker]' ) === [ 'class' => 'nino-ticker' ] );
check( '...a class with a quote and a bracket in it is escaped and opens nothing in the second pass',
	str_contains( \Nino\Html::renderHtml( $probe, '[ticker class="a\'b [c"][text text="a"][/ticker]' ), 'class="nino-ticker a&quot;b &#91;c"' ) === true );
/*	The pictures of a row: the markup the component writes for [image] - a bare <img>, or the frame round it that a ratio, a focus or a
	class of its own call for - and the classes the picture rules of ticker.css select (checked below, against the file)	*/
$probe['/nino/html/images']['/logo'] = [ 'label' => 'Logo', 'width' => 1600, 'height' => 900, 'filename' => 'logo.1600x900.png' ];
$logos = $parsed( \Nino\Html::renderHtml( $probe, '[ticker][image /logo alt=""][image /logo alt="" ratio="1-1"][image /logo alt="" focus="5"][card-image /logo alt="" ratio="16-9"][/ticker]' ) );
$cls = static fn( string $name ): string => "[contains(concat(' ',normalize-space(@class),' '),' ". $name. " ')]";
check( 'a picture in the row is an item of the track: a bare [image] is an <img> that is a child of the track with no class of its own, and a ratio, a focus or a card-image with a ratio is a div of the class nino-image around it',
	$logos->query( '//div'. $cls( 'nino-ticker-track' ). '/img[not(@class)]' )->length === 1
	&& $logos->query( '//div'. $cls( 'nino-ticker-track' ). '/div'. $cls( 'nino-image' ). '/img' )->length === 3
	&& $logos->query( '//div'. $cls( 'nino-ticker-track' ). '/div'. $cls( 'nino-image--1-1' ). '/img' )->length === 1
	&& $logos->query( '//div'. $cls( 'nino-ticker-track' ). '/div'. $cls( 'nino-card-img' ). $cls( 'nino-image--16-9' ). '/img' )->length === 1
	&& $logos->query( '//div'. $cls( 'nino-ticker-track' ). '/*' )->length === 4 );

check( 'a row with nothing between its tags renders nothing: not a track with nothing to run',
	\Nino\Html::renderHtml( $probe, '[ticker][/ticker]' ) === '' && \Nino\Html::renderHtml( $probe, '[ticker speed="60"]  [/ticker]' ) === '' );
check( 'what a component writes in the row is rendered as it would be outside it: a text typed with a bracket is no call, and the call of a component of another place is its own business',
	str_contains( \Nino\Html::renderHtml( $probe, '[ticker][text text="[title text=&quot;x&quot;]"][/ticker]' ), '<h3' ) === false );
/*	Where German addresses the reader it is Du, capitalised: the lower case forms are what is refused	*/
$german = [];
$gather = static function( array $node ) use ( &$gather, &$german ): void {
	foreach( $node as $key => $value )
		if( is_array( $value ) === true )
			$gather( $value );
		else if( $key === 'de_DE' && is_string( $value ) === true )
			$german[] = $value;
};
$gather( [ $manifest['manual'], $manifest['components'] ] );
check( 'the German words of the manual and of the component address the reader in no line as du, dir, dich or dein',
	count( $german ) > 20 && preg_match( '/\b(?:du|dir|dich|dein\w*)\b/u', implode( "\n", $german ) ) === 0 );
check( 'the markup is the template\'s, which holds the box, the track and the places of the attributes and of the content - the content last, where nothing is filled in after it',
	is_file( $dir. '/templates/ticker.tpl' ) === true && preg_match( '/^<div class="nino-ticker\[\[classes\]\]"\[\[speed\]\]\[\[direction\]\]\[\[loop\]\]\[\[toggle\]\]\[\[pause\]\]>\s*<div class="nino-ticker-track">\[\[content\]\]<\/div>\s*<\/div>\s*$/', (string) file_get_contents( $dir. '/templates/ticker.tpl' ) ) === 1
	&& ninoWarnings() === [] );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/ticker.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/ticker.js' );

/*	A browser runs an animation off the main thread and stops paying for it in
	a background tab - neither of which is true of a script that moves something
	every frame	*/
check( 'the movement is an animation, not a script moving something every frame',
	str_contains( $css, 'animation-name: nino-ticker-run' ) === true
	&& str_contains( $js, 'requestAnimationFrame' ) === false && str_contains( $js, 'setInterval' ) === false );
check( '...and what the script sets is the distance and the duration',
	str_contains( $js, '--nino-ticker-distance' ) === true && str_contains( $js, '--nino-ticker-duration' ) === true );

/*	The seam is the whole problem: the animation ends on the copy standing
	where the original stood, so starting again from zero moves nothing	*/
check( 'the row is copied until it covers the box plus one length of itself',
	str_contains( $js, 'row.clientWidth + one' ) === true );
check( '...and there is a ceiling on the copying, so one narrow logo in a wide box does not make a thousand elements',
	str_contains( $js, 'COPIES_MAX' ) === true );
check( 'the copies are hidden from a screen reader - the row is read once, which is how many times it is there',
	str_contains( $js, "setAttribute( 'aria-hidden', 'true' )" ) === true );

/*	One cycle by default: the copy ends where the original stood, so the last
	frame is the first and nothing jumps when it stops. The endless loop is the
	opt-in, and only the class the script writes switches it on - the list of
	values that mean "on" is in one place	*/
check( 'a row runs one cycle by default',
	preg_match( '/\.nino-ticker\.nino-is-running \.nino-ticker-track\s*\{[^}]*animation-iteration-count:\s*1;/s', $css ) === 1 );
check( '...and the endless loop is only for a row the script marked as looping',
	substr_count( $css, 'animation-iteration-count: infinite' ) === 1
	&& preg_match( '/\.nino-ticker\.nino-is-looping\.nino-is-running \.nino-ticker-track\s*\{[^}]*animation-iteration-count:\s*infinite;/s', $css ) === 1
	&& str_contains( $css, '[data-ticker-loop' ) === false );
check( 'a row that has not been seen is held, and one the visitor paused is, whatever the pointer does',
	preg_match( '/\.nino-ticker\.nino-is-waiting \.nino-ticker-track\s*\{[^}]*animation-play-state:\s*paused/s', $css ) === 1
	&& preg_match( '/\.nino-ticker\.nino-is-paused \.nino-ticker-track\s*\{[^}]*animation-play-state:\s*paused/s', $css ) === 1
	&& str_contains( $css, ':not(.nino-is-paused)' ) === true );
check( 'a finished pass has no animation at all, so a resize cannot start it over',
	preg_match( '/\.nino-ticker\.nino-is-done \.nino-ticker-track\s*\{[^}]*animation:\s*none/s', $css ) === 1 );
check( 'the pause button\'s pressed state hangs off the class the script gives it',
	str_contains( $css, '.nino-ticker-toggle[aria-pressed="true"]' ) === true );

check( 'a row stops while it is pointed at, so a logo can be looked at',
	str_contains( $css, ':hover .nino-ticker-track' ) === true && str_contains( $css, 'animation-play-state: paused' ) === true );
check( '...and while something inside it has the keyboard focus, because a row that runs away under a tab stop is unusable',
	str_contains( $css, ':focus-within .nino-ticker-track' ) === true );
check( 'a row that asked to keep running under the pointer does',
	str_contains( $css, '[data-ticker-pause="off"]' ) === true );

/*	Somebody asked for less movement, and a row running across the screen is
	the most literal reading of that there is	*/
check( 'a visitor who asked for less motion gets a row that stands still and scrolls by hand',
	str_contains( $css, 'prefers-reduced-motion' ) === true && str_contains( $css, 'overflow-x: auto' ) === true );

check( 'the script draws the pause button with createElement() and textContent, never inside the track, and removes it rather than hiding it',
	str_contains( $js, "createElement( 'button' )" ) === true && str_contains( $js, 'row.nextSibling' ) === true
	&& str_contains( $js, 'innerHTML' ) === false && str_contains( $js, "'hidden'" ) === false );
check( 'a single pass is held until the row has been seen, and ends on the animation\'s own end',
	str_contains( $js, 'Nino.ui.whenVisible(' ) === true && str_contains( $js, "'animationend'" ) === true );

check( 'nothing about a ticker reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );

/*	The height of a picture: one property, with a default, and the rules that read it. The rules are found in the file as a
	stylesheet reads them - a selector list and its declarations - and each selector is put to the rendered row above, so a
	rule that names a class the markup does not carry, or a nesting it does not have, is the failure	*/
$bare = preg_replace( '~/\*.*?\*/~s', '', $css );
$rule = static function( string $selector ) use ( $bare ): array {

	foreach( explode( '}', $bare ) as $chunk ) {

		if( substr_count( $chunk, '{' ) !== 1 )
			continue;

		[ $head, $body ] = explode( '{', $chunk );

		if( in_array( $selector, array_map( 'trim', explode( ',', $head ) ), true ) === false )
			continue;

		$declared = [];
		foreach( array_filter( array_map( 'trim', explode( ';', $body ) ) ) as $line )
			if( str_contains( $line, ':' ) === true )
				$declared[trim( explode( ':', $line, 2 )[0] )] = trim( explode( ':', $line, 2 )[1] );

		return $declared;
	}

	return [];
};
// A selector as a path: the child axis after a ">", the descendant axis between the other compounds
$selectorXpath = static function( string $selector ): string {

	$xpath = '';
	$axis = '//';
	foreach( preg_split( '/\s+/', trim( $selector ) ) as $token ) {

		if( $token === '>' ) {
			$axis = '/';
			continue;
		}

		preg_match( '/^([a-z]*)((?:\.[\w-]+)*)$/', $token, $parts );
		$xpath .= $axis. ( $parts[1] ?: '*' ). implode( '', array_map( static fn( string $name ): string => "[contains(concat(' ',normalize-space(@class),' '),' ". $name. " ')]", array_filter( explode( '.', $parts[2] ?? '' ) ) ) );
		$axis = '//';
	}

	return $xpath;
};

check( 'the height of a picture in the row is one property, --ticker-picture-height, with a length as its default in :where(:root) - which has no specificity, so a project\'s own rule on :root or on the row moves it',
	preg_match( '/:where\(:root\)\s*\{[^}]*--ticker-picture-height:\s*\d*\.?\d+(?:rem|em|px)\s*;/', $bare ) === 1
	&& substr_count( $bare, '--ticker-picture-height:' ) === 1 );
$flat = $rule( '.nino-ticker-track img' );
check( '...a bare picture and the frame of [image] and [card-image] are that high, as wide as that makes them and not held to the width of the box: one rule reads the property for both, with height, width auto and no max-width',
	$flat !== [] && $rule( '.nino-ticker-track .nino-image' ) === $flat
	&& ( $flat['height'] ?? '' ) === 'var(--ticker-picture-height)' && ( $flat['width'] ?? '' ) === 'auto' && ( $flat['max-width'] ?? '' ) === 'none' );
$inFrame = $rule( '.nino-ticker-track .nino-image > img' );
check( '...the picture in a frame follows the frame\'s height with a width of auto, so that the ratio of a frame - which the kernel gives frame and picture alike - decides the width of the box and never its height',
	( $inFrame['height'] ?? '' ) === '100%' && ( $inFrame['width'] ?? '' ) === 'auto' && ( $inFrame['max-width'] ?? '' ) === 'none' );
$pictures = static fn( string $selector ): int => $logos->query( $selectorXpath( $selector ) )->length;
check( '...and the selectors of those rules find the pictures of a rendered row: the four pictures, the three frames, and the three pictures inside the frames',
	$selectorXpath( '.nino-ticker-track .nino-image > img' ) === "//*[contains(concat(' ',normalize-space(@class),' '),' nino-ticker-track ')]//*[contains(concat(' ',normalize-space(@class),' '),' nino-image ')]/img"
	&& $pictures( '.nino-ticker-track img' ) === 4 && $pictures( '.nino-ticker-track .nino-image' ) === 3 && $pictures( '.nino-ticker-track .nino-image > img' ) === 3 );
check( '...the row keeps the gap of its items: the track still declares its gap, and no picture rule touches it',
	str_contains( (string) ( $rule( '.nino-ticker-track' )['gap'] ?? '' ), 'var(--space-3)' ) === true && array_key_exists( 'gap', $flat ) === false && array_key_exists( 'margin', $flat ) === false );
check( 'the stylesheet writes no colour of its own: no hex, rgb(), hsl(), hwb(), lab(), lch(), oklab() or oklch() value, and no !important',
	preg_match( '/#[0-9a-fA-F]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\s*\(|!important/', $bare ) === 0 );

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so ticker-js-smoke.js is not run here\n\n";
else {
	echo "ticker-js-smoke.js\n";
	// The checkout this test runs against is the one whose Nino.js and Nino.ui.js the script runs over
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/ticker-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'ticker' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Ticker', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the feature is still on disk, listed and inactive', ( \Nino\Features::get( $appData, 'ticker' )['active'] ?? true ) === false );

ninoDone( $appData );
