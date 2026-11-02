<?php
declare(strict_types=1);

/**
 *	Nino
 *	toc-smoke.php			Contract test for the Toc feature (Modules\Toc): the
 *										manifest, the activation through \Nino\Features and the
 *										two words it merges, what [toc] writes and what it
 *										deliberately does not, the two files it lists under
 *										assets, which the kernel puts into the site's own asset
 *										bundles (\Nino\Features::registerAssets()), and
 *										deactivation.
 *
 *										What it brings the Design feature - the three looks in
 *										design/, the style of the place in design/styles.css and
 *										design/styles/, the block of the preview - is held to the
 *										grammar of the Design feature's manual: the heads, the
 *										ladders, the knobs each look answers to, the place of
 *										every default in toc.css; and, where the Design feature
 *										stands beside it, read and compiled by it.
 *
 *										The list itself is the browser's, and toc-js-smoke.js
 *										beside this file measures it, over the kernel's own
 *										Nino.js from the same checkout; this test runs that one
 *										too where node is on the path. Travels with the feature
 *										and runs against the checkout three levels up, or the
 *										one NINO_ROOT names.
 *
 *	Usage: php features/Toc/tests/toc-smoke.php
 *	       NINO_ROOT=../nino php features/Toc/tests/toc-smoke.php
 */

$root = getenv( 'NINO_ROOT' ) ?: dirname( __DIR__, 3 );
defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', dirname( __DIR__, 2 ) );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'toc' );
$appData['/nino/dir'] = '';
$appData['./nino/locales/current'] = 'en_US';


/**
 *	The rules of a stylesheet, its comments left out: for each the at-rule it stands in ('' for none), its selector with the
 *	whitespace folded and the text of its body. One level of at-rule is read, which is as deep as the stylesheets here go
 *
 *	@param		string		$css
 *
 *	@return 	array<int, array{at: string, selector: string, body: string}>
 */
function tocRules( string $css ): array {

	$rules	= [];
	$read		= static function( string $text, string $at ) use ( &$read, &$rules ): void {

		$offset = 0;

		while( ( $open = strpos( $text, '{', $offset ) ) !== false ) {

			$depth	= 1;
			$close	= $open + 1;

			for( ; $close < strlen( $text ) && $depth > 0; $close++ )
				$depth += $text[$close] === '{' ? 1 : ( $text[$close] === '}' ? -1 : 0 );

			$selector	= trim( (string) preg_replace( '/\s+/', ' ', substr( $text, $offset, $open - $offset ) ) );
			$body			= substr( $text, $open + 1, $close - $open - 2 );

			if( str_starts_with( $selector, '@' ) === true )
				$read( $body, $selector );
			else
				$rules[] = [ 'at' => $at, 'selector' => $selector, 'body' => $body ];

			$offset = $close;
		}
	};

	$read( (string) preg_replace( '#/\*.*?\*/#s', '', $css ), '' );

	return $rules;
}

/**
 *	The declarations in the body of a rule, in the order they stand, the whitespace of each value folded
 *
 *	@param		string		$body
 *
 *	@return 	array<int, array{property: string, value: string}>
 */
function tocDeclarations( string $body ): array {

	preg_match_all( '/(?:^|;)\s*(-{0,2}[a-z][a-z0-9-]*)\s*:\s*([^;]*)/i', $body, $found, PREG_SET_ORDER );

	return array_map( static fn( array $match ): array => [ 'property' => $match[1], 'value' => trim( (string) preg_replace( '/\s+/', ' ', $match[2] ) ) ], $found );
}

/**
 *	A stylesheet of the Design feature's grammar, read as it is written: the tags of its head (tag => the texts after it, one
 *	for each line), the rules after the head, and the property each declaration sets (property => [ selector, value ] for
 *	each). Nothing is compared with what a head says - that is the file's to say
 *
 *	@param		string		$file
 *
 *	@return 	array{tags: array<string, array<int, string>>, rules: array<int, array{at: string, selector: string, body: string}>, declared: array<string, array<int, array{selector: string, value: string}>>}
 */
function tocSheet( string $file ): array {

	$css	= (string) file_get_contents( $file );
	$tags	= [];

	preg_match( '#^\s*/\*(.*?)\*/#s', $css, $comment );

	foreach( (array) preg_split( '/\R/', $comment[1] ?? '' ) as $line )
		if( preg_match( '/^\s*\*?\s*(@[a-z]+(?:-de)?)\s+(\S.*?)\s*$/', (string) $line, $found ) === 1 )
			$tags[ $found[1] ][] = $found[2];

	$sheet = [ 'tags' => $tags, 'rules' => tocRules( $css ), 'declared' => [] ];

	foreach( $sheet['rules'] as $rule )
		foreach( tocDeclarations( $rule['body'] ) as $declaration )
			$sheet['declared'][ $declaration['property'] ][] = [ 'selector' => $rule['selector'], 'value' => $declaration['value'] ];

	return $sheet;
}

/**
 *	The colours a stylesheet writes as a literal in the value of a declaration: a hex colour, a colour function or the name of a
 *	colour. What a value reads of the kernel - var(--color-ink) - is the name of a property and none of these; transparent,
 *	currentColor and color-mix() of the kernel's tokens are none either
 *
 *	@param		string		$css
 *
 *	@return 	array<int, string>				property: literal, for each
 */
function tocColourLiterals( string $css ): array {

	$names = explode( ' ', 'aliceblue antiquewhite aqua aquamarine azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen' );
	$literal = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\(|(?<![\w-])(?:'. implode( '|', $names ). ')(?![\w-])/i';
	$found = [];

	foreach( tocRules( $css ) as $rule )
		foreach( tocDeclarations( $rule['body'] ) as $declaration )
			if( preg_match( $literal, (string) preg_replace( '/--[a-z0-9-]+/i', '', $declaration['value'] ), $match ) === 1 )
				$found[] = $declaration['property']. ': '. $match[0];

	return $found;
}


// --- The feature -------------------------------------------------------------

echo "The feature - manifest and activation\n";

$dir = dirname( __DIR__ );
$manifest = \Nino\Features::manifest( $dir );

check( 'the manifest validates, key "toc"', is_array( $manifest ) && $manifest['key'] === 'toc' && ninoWarnings() === [] );
check( 'it is written for this kernel', is_array( $manifest ) && \Nino\Features::satisfies( $manifest['nino'] ) === true );
check( 'it names and describes itself in both interface languages', is_array( $manifest )
	&& \Nino\Features::localized( $manifest['description'], 'de_DE' ) !== \Nino\Features::localized( $manifest['description'], 'en_US' ) );

/*	It is a way through what is already written on a page rather than a control
	that changes how it looks - which is the line the Features panel draws
	between content and ui	*/
check( 'it is filed under content', $manifest['category'] === 'content' );
check( 'it requires no other feature', $manifest['requires'] === [] );
check( 'it keeps no data of its own - the list is the page, read as it stands', $manifest['data'] === [] );
check( 'and carries one setting: whether every heading gets a link of its own',
	array_keys( $manifest['settings'] ) === [ 'anchors' ] && $manifest['settings']['anchors']['default'] === true );
check( 'it lists its stylesheet and its script under assets, which is all it asks the kernel for',
	$manifest['assets'] === [ '/features/Toc/assets/toc.css', '/features/Toc/assets/toc.js' ] );

\Nino\AppData::writeContentData( $appData, [ '/nino/modules', '/nino/locales/available', '/nino/locales/native' ] );
ninoWarnings();

check( 'activation succeeds', \Nino\Features::activate( $appData, 'toc' ) === true );
check( 'the class is listed and the version recorded', in_array( '\\Nino\\Modules\\Toc', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === true
	&& \Nino\Features::get( $appData, 'toc' )['installed'] === $manifest['version'] );

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = \Nino\Filesystem::getFileContent( $appData, '/text/'. $locale. '.php', [] );
	check( 'activation merged the list\'s words into text/'. $locale. '.php',
		array_diff_key( array_flip( [ '[[/feature/toc/list/title]]', '[[/feature/toc/anchor/label]]' ] ), $text ) === [] );
}

echo "\n";


// --- init() ------------------------------------------------------------------

echo "Modules\\Toc::init() and the manifest's assets - the shortcode and the bundle\n";

// The order \Nino\init() takes: the assets of the manifests, then the init() of every module
$appData['/nino/modules'][] = '\\Nino\\Modules\\Assets';
\Nino\Features::registerAssets( $appData );
\Nino\Modules::callModules( $appData, 'init' );

check( 'the shortcode is registered as [toc]', ( $appData['./nino/html/shortcodes']['toc'] ?? null ) !== null );
check( 'toc.css joined the project\'s own /.cache/style.css bundle', in_array( '/features/Toc/assets/toc.css', \Nino\Html::getAssets( $appData, '/.cache/style.css' ), true ) === true );
check( 'toc.js joined the project\'s own /.cache/script.js bundle', in_array( '/features/Toc/assets/toc.js', \Nino\Html::getAssets( $appData, '/.cache/script.js' ), true ) === true );
check( 'it registers no route', ( \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/http/routes'] ?? [] ) === [] );

echo "\n";


// --- [toc] -------------------------------------------------------------------

echo "[toc] - the frame the browser fills\n";

$html = \Nino\Html::renderHtml( $appData, '[toc]' );

/*	A Nino page is assembled out of a template, sections, shortcodes and
	elements - what the headings finally are is only settled once all of that
	has run. So the server writes the frame and the browser fills it	*/
check( 'what the server writes is a nav with an empty list in it',
	str_contains( $html, '<nav class="nino-toc"' ) === true
	&& str_contains( $html, '<ol class="nino-toc-list"></ol>' ) === true );
check( '...hidden, because a table of contents with no contents is worse than none',
	preg_match( '/<nav class="nino-toc"[^>]*\shidden/', $html ) === 1 );
check( 'the list names itself with its own heading', str_contains( $html, 'aria-labelledby="nino-toc-title"' ) === true
	&& str_contains( $html, 'id="nino-toc-title"' ) === true );

check( 'the levels it is built from are written for the script to read, in order',
	str_contains( $html, 'data-toc-levels="2,3"' ) === true );
check( '...and a shorter list is taken, always in order - a list that reads h3 before h2 is not an outline',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc levels="2"]' ), 'data-toc-levels="2"' ) === true
	&& \Nino\Modules\Toc::levels( [ 'levels' => '3,2' ] ) === [ 2, 3 ]
	&& \Nino\Modules\Toc::levels( [ 'levels' => '7' ] ) === \Nino\Modules\Toc::LEVELS
	&& \Nino\Modules\Toc::levels( [] ) === \Nino\Modules\Toc::LEVELS );

check( 'a scope given in the shortcode is passed on for the browser to look in',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc within="#content"]' ), 'data-toc-within="#content"' ) === true );
check( '...and it is escaped on the way, because it goes into an attribute',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc within="a\" onx=\"1"]' ), 'onx=' ) === false );

check( 'the heading over the list falls back to the project\'s own fill',
	str_contains( $html, '>On this page</h2>' ) === true && str_contains( $html, '[[/feature/toc/' ) === false );
check( '...and to what the shortcode says where it says one',
	str_contains( \Nino\Html::renderHtml( $appData, '[toc title="Auf dieser Seite"]' ), '>Auf dieser Seite</h2>' ) === true );

check( 'the word for an anchor is handed over too - a static asset cannot read a fill',
	str_contains( $html, 'data-toc-label="Link to this section"' ) === true );
check( 'the anchors setting reaches the markup as the script\'s own flag',
	str_contains( $html, 'data-toc-anchors="1"' ) === true && \Nino\Modules\Toc::anchors( $appData ) === true );

$off = $appData;
$off[ \Nino\Features::STATE_KEY ]['toc']['settings'] = [ 'anchors' => false ];
check( '...and says so when a project switched them off',
	str_contains( \Nino\Html::renderHtml( $off, '[toc]' ), 'data-toc-anchors="0"' ) === true );

echo "\n";


// --- What the two files promise ------------------------------------------------

echo "The files themselves\n";

$css = (string) file_get_contents( dirname( __DIR__ ). '/assets/toc.css' );
$js	 = (string) file_get_contents( dirname( __DIR__ ). '/assets/toc.js' );

/*	An id a page already had may be linked to from somewhere else, and taking
	it away would break a link nobody here can see	*/
check( 'a heading that already has an id keeps it', str_contains( $js, "if( heading.id !== '' )" ) === true );
check( '...and a made-up one is made unique against the whole page, not just the list',
	str_contains( $js, 'document.getElementById( id ) !== null' ) === true );
check( 'a list never lists its own title', str_contains( $js, 'closest( SELECTOR )' ) === true );
check( 'nothing about a list reaches the server or outlives the page',
	str_contains( $js, 'XMLHttpRequest' ) === false && str_contains( $js, 'fetch(' ) === false
	&& str_contains( $js, 'localStorage' ) === false && str_contains( $js, 'document.cookie' ) === false );
check( 'scrolling costs one frame at most, not one call per event - the scroll and the resize are the kernel\'s throttled events',
	str_contains( $js, "Nino.events.bindCallback( 'scroll', mark )" ) === true && str_contains( $js, "Nino.events.bindCallback( 'resize', mark )" ) === true
	&& str_contains( $js, 'addEventListener' ) === false );

check( 'the section being read is said with aria-current, so the mark and the announcement are one fact',
	str_contains( $js, "setAttribute( 'aria-current', 'true' )" ) === true
	&& str_contains( $css, '[aria-current="true"]' ) === true );
check( 'the anchor on a heading stays reachable by keyboard rather than being a hover-only affordance',
	str_contains( $css, '.nino-toc-anchor:focus-visible' ) === true );
check( 'and the stylesheet keeps the hidden nav hidden', str_contains( $css, '.nino-toc[hidden]' ) === true );

echo "\n";


// --- The looks of the Design feature ----------------------------------------------

echo "design/ - the looks, the place and the block that the Design feature reads\n";

/*	What a feature brings the Design feature as files (its manual, under Base, says what a variant, a style and a block are). Read
	here by their grammar - which tags, which fields, which names, which rule where - and what a head says is never compared: the
	words are the files' to say. The base stylesheet owns the layout and declares every property a look moves, the looks move them	*/
$design 		= $dir. '/design';
$base 			= tocSheet( $dir. '/assets/toc.css' );
$looks 			= [];

foreach( glob( $design. '/*.css' ) ?: [] as $file )
	if( basename( $file ) !== 'styles.css' )
		$looks[ basename( $file, '.css' ) ] = tocSheet( $file );

ksort( $looks );

$kinds			= [ 'scale', 'choice', 'ground', 'ink', 'colour' ];
$tabs				= [ 'type', 'surfaces', 'colours', 'motion' ];
$inkWords		= [ 'ink', 'subtitle', 'title', 'accent', 'primary', 'border', 'focus' ];
$surfaces		= [ 'default', 'alt', 'tint', 'dark', 'black', 'brand', 'brand-safe', 'accent-safe', 'success', 'warning', 'danger' ];
$steps			= [ 'least', 'less', 'default', 'more', 'most' ];
$stepSuffix	= '/--(?:least|less|default|more|most)$/';
$knobTable	= [ 'line' => [ 'scale', 'surfaces' ], 'edge' => [ 'ink', 'colours' ], 'ground' => [ 'ground', 'colours' ], 'space' => [ 'scale', 'surfaces' ], 'indent' => [ 'scale', 'surfaces' ] ];
$answers		= [ 'box' => [ 'line', 'edge', 'ground', 'space', 'indent' ], 'numbered' => [ 'space', 'indent' ], 'regular' => [ 'line', 'edge', 'space', 'indent' ] ];

check( 'the feature brings three looks of the Design feature, a stylesheet each in design/ - and regular, which is where a feature starts from, is the line as it has always been drawn - with a block for the preview, and the styles of all of them in styles.css and design/styles/',
	array_keys( $looks ) === [ 'box', 'numbered', 'regular' ] && array_map( 'basename', glob( $design. '/*' ) ?: [] ) === [ 'box.css', 'numbered.css', 'preview.tpl', 'regular.css', 'styles', 'styles.css' ] );

$headless = [];

foreach( $looks as $variant => $look )
	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $look['tags'][$tag] ?? [] ) !== 1 || trim( $look['tags'][$tag][0] ) === '' )
			$headless[] = $variant. ' '. $tag;

check( 'each head names and describes its look in both languages, once each: @name, @name-de, @description, @description-de'. ( $headless === [] ? '' : ' - '. implode( ', ', $headless ) ), $headless === [] && $looks !== [] );

/** The knobs a head declares: name => kind, tab, label, default (null where the line has none), and how often it was declared */
$knobsOf = [];

foreach( $looks as $variant => $look )
	foreach( $look['tags']['@knob'] ?? [] as $text ) {
		$fields = (array) preg_split( '/\s+/', $text, 4 );
		preg_match( '/^(.*?)(?:\s+=\s+(\S+))?$/', (string) ( $fields[3] ?? '' ), $label );
		$knobsOf[$variant][ (string) $fields[0] ][] = [ 'fields' => count( $fields ), 'kind' => (string) ( $fields[1] ?? '' ), 'tab' => (string) ( $fields[2] ?? '' ), 'label' => (string) ( $label[1] ?? '' ), 'default' => $label[2] ?? null ];
	}

$knobProblems = [];

foreach( $looks as $variant => $look ) {
	foreach( $knobsOf[$variant] ?? [] as $name => $lines ) {
		$line = $lines[0];

		if( count( $lines ) !== 1 || $line['fields'] !== 4 || in_array( $line['kind'], $kinds, true ) === false || in_array( $line['tab'], $tabs, true ) === false || $line['label'] === '' )
			$knobProblems[] = $variant. ' @knob '. $name;

		$twins = array_filter( $look['tags']['@knob-de'] ?? [], static fn( string $text ): bool => preg_match( '/^'. preg_quote( $name, '/' ). '\s+\S/', $text ) === 1 );

		if( count( $twins ) !== 1 )
			$knobProblems[] = $variant. ' @knob-de '. $name;

		// A scale takes no default - its middle is the one it stands at -, a ground the surface of the palette it stands at and an ink a tone of the page
		$wrong = match( $line['kind'] ) {
			'scale'		=> $line['default'] !== null,
			'ground'	=> in_array( $line['default'], $surfaces, true ) === false,
			'ink'			=> in_array( $line['default'], $inkWords, true ) === false,
			default		=> true,
		};

		if( $wrong === true )
			$knobProblems[] = $variant. ' default of '. $name;
	}

	foreach( $look['tags']['@knob-de'] ?? [] as $text )
		if( isset( $knobsOf[$variant][ (string) strtok( $text, " \t" ) ] ) === false )
			$knobProblems[] = $variant. ' @knob-de '. strtok( $text, " \t" ). ' names no knob';
}

check( 'each @knob line has its four fields - the name, a kind, a tab, the words - once, a @knob-de beside it, a ground the surface of the palette it stands at and an ink the tone of the page'. ( $knobProblems === [] ? '' : ' - '. implode( ', ', $knobProblems ) ), $knobProblems === [] && $knobsOf !== [] );

$offTable = [];

foreach( $knobsOf as $variant => $declared )
	foreach( $declared as $name => $lines )
		if( ( $knobTable[$name] ?? null ) !== [ $lines[0]['kind'], $lines[0]['tab'] ] )
			$offTable[] = $variant. ' '. $name;

check( 'every knob is one of the five, of the same kind and on the same tab in every look: the thickness of the line, the space inside and the indent on Surface; the colour of the line and the ground of the box on Colour'. ( $offTable === [] ? '' : ' - '. implode( ', ', $offTable ) ),
	$offTable === [] && array_keys( $knobsOf['box'] ?? [] ) === array_keys( $knobTable ) );
check( 'a look declares only what it answers to: Line and Box the line and its colour, Box besides the ground it stands on, Numbered neither - it has no line - and all three the space and the indent; a knob a look does not declare is greyed out in the panel, with a line that says so',
	array_map( static fn( array $declared ): array => array_keys( $declared ), $knobsOf ) === $answers );

$ladderProblems = [];

foreach( $looks as $variant => $look ) {
	$stems = [];

	foreach( $knobsOf[$variant] ?? [] as $name => $lines )
		if( $lines[0]['kind'] === 'scale' ) {
			$stems[] = '--toc-'. $name;

			foreach( $steps as $step )
				if( count( $look['declared'][ '--toc-'. $name. '--'. $step ] ?? [] ) !== 1 || $look['declared'][ '--toc-'. $name. '--'. $step ][0]['selector'] !== ':root' )
					$ladderProblems[] = $variant. ' lacks --toc-'. $name. '--'. $step. ' on :root, once';
		}

	foreach( array_keys( $look['declared'] ) as $property )
		if( preg_match( $stepSuffix, $property ) === 1 && in_array( (string) preg_replace( $stepSuffix, '', $property ), $stems, true ) === false )
			$ladderProblems[] = $variant. ' has a ladder no knob of its head moves: '. $property;
}

check( 'a scale declares its five rungs - once each, on :root - and every ladder belongs to a knob the head declares'. ( $ladderProblems === [] ? '' : ' - '. implode( ', ', $ladderProblems ) ),
	$ladderProblems === [] && array_map( static fn( array $look ): int => count( array_filter( array_keys( $look['declared'] ), static fn( string $property ): bool => preg_match( $stepSuffix, $property ) === 1 ) ), $looks ) === [ 'box' => 15, 'numbered' => 10, 'regular' => 15 ] );

/*	Numbered has no line and no room at the sides, and says so by setting the two properties to nothing: on :root, where a look moves a default, and to a
	length that is none - the knobs it does not declare are not moved by anything else	*/
$numbered = $looks['numbered']['declared'] ?? [];

check( 'Numbered sets the thickness of the line and the room at the sides to nothing, on :root and nowhere else - a length of zero, which no knob of its own moves',
	array_column( $numbered['--toc-line'] ?? [], 'selector' ) === [ ':root' ] && array_column( $numbered['--toc-inset'] ?? [], 'selector' ) === [ ':root' ]
	&& preg_match( '/^0(?:\.0+)?[a-z%]*$/', (string) ( $numbered['--toc-line'][0]['value'] ?? '' ) ) === 1 && preg_match( '/^0(?:\.0+)?[a-z%]*$/', (string) ( $numbered['--toc-inset'][0]['value'] ?? '' ) ) === 1
	&& array_filter( [ 'box', 'regular' ], static fn( string $variant ): bool => isset( $looks[$variant]['declared']['--toc-line'] ) === true || isset( $looks[$variant]['declared']['--toc-inset'] ) === true ) === [] );

$baseDeclared	= $base['declared'];
$ruleProblems	= [];

foreach( $looks as $variant => $look )
	foreach( $look['rules'] as $rule ) {
		// The ladders and the properties Numbered moves stand on :root; a rule is on the list's own classes and on nothing else - no element, no id, no class of the kernel's
		if( $rule['selector'] === ':root' ) {
			foreach( tocDeclarations( $rule['body'] ) as $declaration )
				if( preg_match( '/^--toc-[a-z0-9-]+--(?:least|less|default|more|most)$/', $declaration['property'] ) !== 1 && in_array( $declaration['property'], [ '--toc-line', '--toc-inset' ], true ) === false )
					$ruleProblems[] = $variant. ' declares '. $declaration['property']. ' on :root, which is no rung of a ladder';
		}
		elseif( in_array( $rule['at'], [ '', '@supports (content: "" / "")' ], true ) === false || preg_match( '/^[\s>+~,]*$/', (string) preg_replace( [ '/\.nino-toc[a-z0-9-]*/', '/::before/' ], '', $rule['selector'] ) ) !== 1 || str_contains( $rule['selector'], '.nino-toc' ) === false )
			$ruleProblems[] = $variant. ' writes a rule on '. $rule['selector'];

		foreach( tocDeclarations( $rule['body'] ) as $declaration ) {
			preg_match_all( '/var\(\s*(--toc-[a-z0-9-]+)/', $declaration['value'], $reads );

			foreach( $reads[1] as $read )
				if( isset( $baseDeclared[$read] ) === false )
					$ruleProblems[] = $variant. ' reads '. $read. ', which toc.css does not declare';
		}
	}

check( 'a look is a head, the rungs of its ladders on :root and rules on the list\'s own classes - never an element, an id or a class of the kernel - and it reads only properties that toc.css declares'. ( $ruleProblems === [] ? '' : ' - '. implode( ', ', $ruleProblems ) ), $ruleProblems === [] && count( $looks ) === 3 );

$optionFiles	= glob( $design. '/styles/*.css' ) ?: [];
$colourProblems = [];

foreach( array_merge( array_map( static fn( string $variant ): string => $design. '/'. $variant. '.css', array_keys( $looks ) ), [ $design. '/styles.css', $dir. '/assets/toc.css' ], $optionFiles ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) );

	foreach( tocColourLiterals( $code ) as $literal )
		$colourProblems[] = basename( $file ). ' '. $literal;

	if( stripos( $code, '!important' ) !== false )
		$colourProblems[] = basename( $file ). ' !important';
}

check( '...and no stylesheet of the feature writes a colour as a literal - a hex colour, a function, the name of one - or declares anything !important: the colours are a ground, a tone or what the page says'. ( $colourProblems === [] ? '' : ' - '. implode( ', ', $colourProblems ) ), $colourProblems === [] );

/*	A box is a ground of its own, and what stands in it - the title and the links - is read on it: the roles the page writes with are passed on to them from the
	roles the palette solved for the ground, as Nino.css does for its grounds, and from nowhere else. The line is a tone of the page read on the box itself,
	so it is the line of the ground unless another is chosen. A look that paints no box passes nothing: Line and Numbered read on the section	*/
$boxRule	= array_values( array_filter( $looks['box']['rules'] ?? [], static fn( array $rule ): bool => $rule['selector'] === '.nino-toc' ) );
$boxMade	= array_column( tocDeclarations( $boxRule[0]['body'] ?? '' ), 'value', 'property' );
$roles		= [
	'--color-ground' => 'var(--toc-ground)', '--color-ink' => 'var(--toc-ground-on)', '--color-title' => 'var(--toc-ground-on)', '--color-subtitle' => 'var(--toc-ground-on-muted)',
	'--color-accent' => 'var(--toc-ground-link)', '--color-border' => 'var(--toc-ground-border)', '--color-focus' => 'var(--toc-ground-focus)',
];
$passed		= array_filter( $boxMade, static fn( string $property ): bool => str_starts_with( $property, '--color-' ), ARRAY_FILTER_USE_KEY );

check( 'Box passes the roles of its ground on to what stands in it: the ground itself, the ink and the title as its text colour, the subtitle as the quieter one, the accent as its link, the border and the focus - on the list and on nothing else',
	count( $boxRule ) === 1 && $passed === $roles
	&& array_filter( array_keys( $looks ), static fn( string $variant ): bool => $variant !== 'box' && array_filter( array_keys( $looks[$variant]['declared'] ), static fn( string $property ): bool => str_starts_with( $property, '--color-' ) ) !== [] ) === [] );
check( 'Box paints the list: the ground of the knob, the text colour the ground brings, the line all round it in the colour of the knob and in the thickness of the knob, and corners of the page',
	( $boxMade['background-color'] ?? '' ) === 'var(--toc-ground)' && ( $boxMade['color'] ?? '' ) === 'var(--toc-ground-on)'
	&& ( $boxMade['border'] ?? '' ) === 'var(--toc-line) solid var(--toc-edge)' && preg_match( '/^var\(--radius-[a-z]+\)$/', (string) ( $boxMade['border-radius'] ?? '' ) ) === 1 );

/*	Where the defaults stand: lengths, numbers and the ground with its parts in :where(:root), a tone of the page in :where(*), and nowhere else - a custom
	property is resolved where it is declared, and :where() has no specificity, so the line of the knob positions a look compiles beats it	*/
$tocSource	= (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $dir. '/assets/toc.css' ) );
$moved 			= [];

foreach( $knobsOf as $variant => $declared ) {
	foreach( $declared as $name => $lines ) {
		if( $lines[0]['kind'] === 'scale' )
			$moved[ '--toc-'. $name ] = ':where(:root)';

		if( $lines[0]['kind'] === 'ink' )
			$moved[ '--toc-'. $name ] = ':where(*)';

		if( $lines[0]['kind'] === 'ground' ) {
			$moved[ '--toc-'. $name ] = ':where(:root)';

			foreach( [ 'on', 'on-muted', 'link', 'border', 'focus' ] as $part )
				$moved[ '--toc-'. $name. '-'. $part ] = ':where(:root)';
		}
	}
}

// The two properties Numbered sets to nothing are defaults too
$moved['--toc-inset'] = ':where(:root)';

$strayDefaults = [];

foreach( $moved as $property => $place )
	if( array_column( $baseDeclared[$property] ?? [], 'selector' ) !== [ $place ] )
		$strayDefaults[] = $property. ' is not declared once in '. $place;

foreach( $base['rules'] as $rule )
	foreach( tocDeclarations( $rule['body'] ) as $declaration )
		if( str_starts_with( $declaration['property'], '--toc-' ) === true && in_array( $rule['selector'], [ ':where(:root)', ':where(*)' ], true ) === false )
			$strayDefaults[] = $declaration['property']. ' is declared under '. $rule['selector'];

// Each property it declares is read by a var() - in the list or in a look - since one that nothing reads is a knob that moves nothing
$readAnywhere = $tocSource. implode( "\n", array_map( static fn( string $variant ): string => (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $design. '/'. $variant. '.css' ) ), array_keys( $looks ) ) );

foreach( array_keys( $baseDeclared ) as $property )
	if( str_starts_with( $property, '--toc-' ) === true && preg_match( '/var\(\s*'. preg_quote( $property, '/' ). '\s*[,)]/', $readAnywhere ) !== 1 )
		$strayDefaults[] = $property. ' is read by nothing';

if( preg_match( '/:root\s*\{/', (string) preg_replace( '/:where\(:root\)/', '', $tocSource ) ) === 1 )
	$strayDefaults[] = 'a bare :root in toc.css';

check( 'toc.css declares every property a look moves, once, in the place of its kind and in no other: a scale, the room at the sides and the ground with its parts in :where(:root), the colour of the line in :where(*) - never in a bare :root, on a class or with !important - and each of them is read by a var()'. ( $strayDefaults === [] ? '' : ' - '. implode( ', ', $strayDefaults ) ),
	$strayDefaults === [] && count( array_filter( $moved, static fn( string $place ): bool => $place === ':where(*)' ) ) === 1 && count( array_filter( $moved, static fn( string $place ): bool => $place === ':where(:root)' ) ) === 10 );

// Line is the list as it is without the Design feature: the middle of every ladder is the default toc.css has, it writes no rule, and the tone of the line the base declares is the one the knob stands at
$unequal = [];
$plain 	 = $looks['regular'] ?? [ 'declared' => [], 'rules' => [] ];

foreach( $plain['declared'] as $property => $places )
	if( preg_match( '/^(.+)--default$/', $property, $stem ) === 1 && ( $baseDeclared[ $stem[1] ][0]['value'] ?? null ) !== $places[0]['value'] )
		$unequal[] = $property;

foreach( $plain['rules'] as $rule )
	if( $rule['selector'] !== ':root' )
		$unequal[] = 'a rule on '. $rule['selector'];

if( ( $baseDeclared['--toc-edge'][0]['value'] ?? null ) !== 'var('. \Nino\Modules\Design\Setup::INKS[ (string) ( $knobsOf['regular']['edge'][0]['default'] ?? '' ) ]. ')' )
	$unequal[] = 'the line is not the tone its knob stands at';

check( 'Line stands where toc.css stands: the middle of each of its ladders is the default the base declares, it writes no rule, and the tone of the line the base declares is the one its knob stands at - so the list under it is drawn as it is without the Design feature'. ( $unequal === [] ? '' : ' - '. implode( ', ', $unequal ) ),
	$unequal === [] && count( array_filter( array_keys( $plain['declared'] ), static fn( string $property ): bool => str_ends_with( $property, '--default' ) ) ) === 3 );

// A list written hidden stays hidden where the script never runs: no look and no style gives the list a display of its own
$displayed = [];

foreach( array_merge( array_values( $looks ), array_map( 'tocSheet', $optionFiles ) ) as $look )
	foreach( $look['rules'] as $rule )
		if( preg_match( '/(?:^|,)\s*\.nino-toc\s*(?:,|$)/', $rule['selector'] ) === 1 && in_array( 'display', array_column( tocDeclarations( $rule['body'] ), 'property' ), true ) === true )
			$displayed[] = $rule['selector'];

check( 'no look and no style gives the list itself a display: the nav the shortcode writes hidden stays hidden where the script never ran', $displayed === [] && str_contains( $tocSource, '.nino-toc[hidden]' ) === true );

// Numbered: the entries counted, down the whole list in the order they stand
$numberedRules = [];

foreach( $looks['numbered']['rules'] ?? [] as $rule )
	if( $rule['selector'] !== ':root' )
		$numberedRules[ $rule['at']. '|'. $rule['selector'] ] = array_column( tocDeclarations( $rule['body'] ), 'value', 'property' );

$counted = $numberedRules['|.nino-toc-item::before'] ?? [];
$silent  = $numberedRules['@supports (content: "" / "")|.nino-toc-item::before'] ?? [];

check( 'Numbered counts the entries with a counter: the list resets it, each entry adds to it, and the number stands before the entry in the quieter tone of the page - the same count for every entry, whatever its level',
	( $numberedRules['|.nino-toc-list']['counter-reset'] ?? '' ) === 'toc-entry' && ( $numberedRules['|.nino-toc-item']['counter-increment'] ?? '' ) === 'toc-entry'
	&& preg_match( '/^counter\(toc-entry\) "\."$/', (string) ( $counted['content'] ?? '' ) ) === 1 && ( $counted['color'] ?? '' ) === 'var(--color-subtitle)'
	&& array_keys( $numberedRules ) === [ '|.nino-toc-list', '|.nino-toc-item', '|.nino-toc-item::before', '@supports (content: "" / "")|.nino-toc-item::before' ] );
check( '...and the number is decoration: where the browser knows the alternative text of generated content, it is given an empty one, so that an ordered list that is read as numbered is not read twice',
	( $silent['content'] ?? '' ) === 'counter(toc-entry) "." / ""' && array_keys( $silent ) === [ 'content' ] );

// --- The styles: once for all the looks
$styles 			= tocSheet( $design. '/styles.css' );
$styleLines		= $styles['tags']['@style'] ?? [];
$styleName		= (string) strtok( $styleLines[0] ?? '', " \t" );
$styleProblems = [];

if( count( $styleLines ) !== 1 || count( $styles['tags']['@style-de'] ?? [] ) !== 1 || strtok( $styles['tags']['@style-de'][0], " \t" ) !== $styleName || count( (array) preg_split( '/\s+/', $styleLines[0], 2 ) ) !== 2 )
	$styleProblems[] = 'styles.css declares one style with its @style and its @style-de line';

if( isset( $styles['tags']['@name'] ) === true )
	$styleProblems[] = 'styles.css has a @name - it is no look';

// Nothing is shared between the options of a style that has one: the file is the head alone, and the rule is the option's
if( $styles['rules'] !== [] )
	$styleProblems[] = 'styles.css has a body - the rules the options share';

foreach( $optionFiles as $file ) {
	$option = tocSheet( $file );

	if( preg_match( '/^'. preg_quote( $styleName, '/' ). '-[a-z0-9]+(?:-[a-z0-9]+)*$/', basename( $file, '.css' ) ) !== 1 )
		$styleProblems[] = basename( $file ). ' is named for no option of '. $styleName;

	foreach( [ '@name', '@name-de', '@description', '@description-de' ] as $tag )
		if( count( $option['tags'][$tag] ?? [] ) !== 1 || trim( $option['tags'][$tag][0] ) === '' )
			$styleProblems[] = basename( $file ). ' '. $tag;
}

check( 'design/styles.css declares one style, the files of design/styles/ are its options - each named <style>-<option>.css and headed in both languages - and the file is the head alone: the style has one option, so there is no rule for the options to share'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ),
	$styleProblems === [] && $styleName !== '' && count( $optionFiles ) === 1 );

/*	The place is a position of the list itself, from the width where there is room beside the text, and not below it: one rule, in one media query, on the list and on nothing else.
	The offset is a space of the page, so that it follows the whitespace the Design feature sets	*/
$sticky 		= tocSheet( (string) ( $optionFiles[0] ?? '' ) );
$stickyRule = $sticky['rules'][0] ?? [ 'at' => '', 'selector' => '', 'body' => '' ];
$stickyMade = array_column( tocDeclarations( $stickyRule['body'] ), 'value', 'property' );

check( 'the option sticks the list: position sticky and an offset from the top of a space of the page, on the list itself, in one media query that starts at 1024 px - below it the list stays where it was written',
	count( $sticky['rules'] ) === 1 && $stickyRule['at'] === '@media (min-width: 1024px)' && $stickyRule['selector'] === '.nino-toc'
	&& ( $stickyMade['position'] ?? '' ) === 'sticky' && preg_match( '/^var\(--space-[1-6]\)$/', (string) ( $stickyMade['top'] ?? '' ) ) === 1
	&& array_filter( $looks, static fn( array $look ): bool => isset( $look['declared']['position'] ) === true ) === [] && isset( $baseDeclared['position'] ) === false );

// --- The block: the list as the script leaves it
$block 			= (string) @file_get_contents( $design. '/preview.tpl' );
$blockIds 	= [];
preg_match_all( '/<[a-z][^>]*>/i', $block, $blockTags );

foreach( $blockTags[0] as $blockTag )
	if( preg_match( '/\sid="([^"]*)"/', $blockTag, $blockId ) === 1 )
		$blockIds[] = $blockId[1];

preg_match( '/<nav\b([^>]*)>(.*?)<\/nav>/s', $block, $blockNav );
preg_match_all( '/<li class="nino-toc-item nino-toc-item--(\d)">(.*?)<\/li>/s', $block, $blockItems, PREG_SET_ORDER );
preg_match_all( '/\[\[(\/feature\/toc\/[a-z0-9\/-]+)\]\]/', $block, $blockFills );

check( 'the feature brings a block to the specimen, design/preview.tpl: one grid row, every id in it toc-something and each once, no section of its own and no inline handler',
	str_starts_with( trim( $block ), '<div class="nino-grid-row">' ) === true && str_ends_with( trim( $block ), '</div>' ) === true && substr_count( $block, 'class="nino-grid-row"' ) === 1
	&& $blockIds !== [] && array_filter( $blockIds, static fn( string $id ): bool => str_starts_with( $id, 'toc-' ) === false ) === [] && count( $blockIds ) === count( array_unique( $blockIds ) )
	&& preg_match( '/<section\b/i', $block ) !== 1 && preg_match( '/\son[a-z]+\s*=/i', $block ) !== 1 );
check( '...the list is the markup of the shortcode as the script leaves it, finished: one nav with its title - the words of the feature\'s text key, in both languages - over an ordered list of three entries, one of them of the second level, each a link to nothing - # - with the words of the specimen, and none of it hidden',
	count( $blockNav ) === 3 && preg_match( '/\bhidden\b/', $blockNav[1] ) !== 1 && str_contains( $blockNav[1], 'class="nino-toc"' ) === true
	&& preg_match( '/<h2 class="nino-toc-title"[^>]*>\[\[\/feature\/toc\/list\/title\]\]<\/h2>/', $blockNav[2] ) === 1 && substr_count( $blockNav[2], '<ol class="nino-toc-list"' ) === 1
	&& array_column( $blockItems, 1 ) === [ '2', '3', '2' ] && substr_count( $block, 'href="#"' ) === 3 && substr_count( $block, '[[short]]' ) === 3
	&& array_filter( $blockItems, static fn( array $item ): bool => preg_match( '/^<a class="nino-toc-link"[^>]*>\[\[short\]\]<\/a>$/', $item[2] ) !== 1 ) === []
	&& array_filter( $blockFills[1], static function( string $key ) use ( $dir ): bool {
		foreach( [ 'en_US', 'de_DE' ] as $locale )
			if( isset( ( include $dir. '/install/text/'. $locale. '.php' )[ '[['. $key. ']]' ] ) === false )
				return true;

		return false;
	} ) === [] && $blockFills[1] !== [] );

$blockClasses = [];
preg_match_all( '/class="([^"]*)"/', $block, $blockClassLists );

foreach( $blockClassLists[1] as $list )
	foreach( explode( ' ', $list ) as $class )
		if( str_starts_with( $class, 'nino-toc' ) === true )
			$blockClasses[$class] = true;

$writtenBy = (string) file_get_contents( $dir. '/templates/toc.tpl' ). (string) file_get_contents( $dir. '/assets/toc.js' );

check( '...and no class of the list is one that the shortcode\'s own template and the script do not write',
	$blockClasses !== [] && array_filter( array_keys( $blockClasses ), static fn( string $class ): bool => str_contains( $writtenBy, preg_replace( '/--\d$/', '', $class ) ) === false ) === [] );

// --- The manual
$manualProblems = [];

foreach( [ 'en_US', 'de_DE' ] as $locale ) {
	$text = ( (array) ( $manifest['manual']['panel'] ?? [] ) )['Design: the looks of the list'][$locale] ?? null;

	if( is_string( $text ) === false || trim( $text ) === '' || max( array_map( 'strlen', explode( "\n", $text ) ) ) >= 1000 )
		$manualProblems[] = $locale;
	// The reader is addressed as Du, capitalised, where the German speaks to them at all
	elseif( $locale === 'de_DE' && preg_match( '/\b(?:du|dir|dich|dein[a-z]*)\b/u', $text ) === 1 )
		$manualProblems[] = $locale. ' Du';
}

check( 'the manual names the looks, the knobs and the place under panel, in both languages, every line under 1000 bytes, and speaks to the reader as Du'. ( $manualProblems === [] ? '' : ' - '. implode( ', ', $manualProblems ) ), $manualProblems === [] );

echo "\n";


// --- ...run through the Design feature ---------------------------------------------

echo "design/ - as the Design feature reads and compiles it\n";

$designSetup			= '\\Nino\\Modules\\Design\\Setup';
$designPreview		= '\\Nino\\Modules\\Design\\Preview';
$designCompiler		= '\\Nino\\Modules\\Design\\Compiler';

if( class_exists( $designSetup ) === false || class_exists( $designCompiler ) === false )
	echo "  note - there is no Design feature beside this one: the looks are not run through it here, bin/check.sh runs them with it in the checkout\n";
else {
	$library			= \Nino\Features::dir(). '/Design/library';
	$modulesKept	= (array) ( $appData['/nino/modules'] ?? [] );
	$localeKept		= $appData['./nino/locales/current'];

	/** Which of the features are on: their classes are listed in /nino/modules, and what the kernel and the Design feature have read of the features is read again */
	$featuresOn = static function( array $names ) use ( &$appData, $modulesKept ): void {
		$appData['/nino/modules'] = array_merge( array_values( array_diff( $modulesKept, [ '\\Nino\\Modules\\Toc', '\\Nino\\Modules\\Design' ] ) ), array_map( static fn( string $name ): string => '\\Nino\\Modules\\'. $name, $names ) );
		unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );
	};

	/** The sheet a setup makes of the raw entry given */
	$compile = static function( array $raw, array &$notes = [] ) use ( &$appData, $designSetup, $designCompiler, $library ): string {
		$setup = $designSetup::normalize( $appData, $raw, $library, $notes );

		return $designCompiler::compile( $appData, $setup, $library, $notes );
	};

	ninoWarnings();
	$featuresOn( [ 'Toc', 'Design' ] );
	$appData['./nino/locales/current'] = 'en_US';

	$scanNotes	= [];
	$scan				= $designSetup::features( $appData, $scanNotes );
	$feature		= $scan['toc'] ?? [];
	$catalogue	= $feature['catalogue'] ?? [];

	check( 'with the Design feature on, the scan lists this feature by its three looks, regular the one it has before anything is chosen, the anchor of its block - and has nothing to say of any file: no line dropped, no ladder missing, no style without a file',
		isset( $scan['toc'] ) === true && realpath( (string) $feature['dir'] ) === realpath( $dir ) && array_keys( $catalogue ) === array_keys( $looks ) && $feature['default'] === 'regular'
		&& $feature['anchor'] === 'feature-toc' && $scanNotes === [] );

	$publishes = [];

	foreach( $catalogue as $variant => $look )
		$publishes[$variant] = array_map( static fn( array $knob ): array => [ $knob['kind'], $knob['tab'] ], $look['knobs'] );

	check( 'each look publishes the knobs it declares and no other, with the kind and the tab of its head: five for Box, four for Line, two for Numbered - and a scale by the ladder of its own name',
		$publishes === array_map( static fn( array $names ): array => array_intersect_key( $knobTable, array_flip( $names ) ), $answers )
		&& array_filter( $catalogue, static function( array $look ): bool {
			foreach( $look['knobs'] as $name => $knob )
				if( $knob['kind'] === 'scale' && ( $knob['ladder'] ?? [] ) !== [ $name ] )
					return true;

			return false;
		} ) === [] );

	$styled = $feature['styles'] ?? [];

	check( 'the styles the scan finds are the one design/styles.css declares, with the option its file gives, the file of it there',
		array_keys( $styled ) === [ $styleName ] && count( $styled[$styleName]['options'] ?? [] ) === 1 && count( $optionFiles ) === 1
		&& array_filter( $styled[$styleName]['options'] ?? [], static fn( array $option ): bool => realpath( (string) $option['file'] ) !== realpath( (string) $optionFiles[0] ) ) === [] );

	// The block is the feature's, not a look's: Design makes the section round it, with the feature's anchor
	$blockTold		= [];
	$blockInside	= $designSetup::previewOf( (string) $feature['dir'], 'toc', $blockTold );
	$blocks				= $designPreview::blocks( $appData );

	check( 'the block stands the check Design holds it to and the preview makes the section of it, with the anchor the scan names, before the footer',
		$blockInside === trim( $block ) && $blockInside !== '' && $blockTold === [] && array_keys( $blocks ) === [ 'toc' ]
		&& preg_match( '~^<section id="feature-toc" class="nino-section" aria-label="[^"]+">\s*(.*?)\s*</section>$~s', $blocks['toc'] ?? '', $blockSection ) === 1 && str_contains( $blockSection[1] ?? '', 'id="toc-nav"' ) === true
		&& str_contains( $blocks['toc'], '[[short]]' ) === false );

	$featuresOn( [ 'Toc', 'Design' ] );
	$withNone	= $compile( [] );
	$featuresOn( [ 'Design' ] );
	$withOff	= $compile( [] );
	$featuresOn( [ 'Toc', 'Design' ] );

	check( 'with no look chosen the feature compiles nothing: the sheet of a project that has the feature on is the sheet of one that has it off, byte for byte', $withNone === $withOff && str_contains( $withNone, 'feature toc' ) === false );

	$sheetProblems = [];

	foreach( $catalogue as $variant => $look ) {
		$sheetNotes	= [];
		$sheet			= $compile( [ 'features' => [ 'toc' => [ 'set' => $variant ] ] ], $sheetNotes );
		$ladders		= [];

		foreach( $look['knobs'] as $name => $knob )
			if( $knob['kind'] === 'scale' ) {
				$ladders[] = $name. ' default';

				foreach( $knob['ladder'] ?? [] as $ladder )
					if( str_contains( $sheet, "\n\t--toc-". $ladder. ': var('. sprintf( $designSetup::KNOB_TOKEN, 'toc', $ladder, 'default' ). ");\n" ) === false )
						$sheetProblems[] = $variant. ' selects no '. $ladder;
			}
			elseif( $knob['kind'] === 'ink' && preg_match( '~\n:root, :root \* \{\n(?:\t[^\n]*\n)*?\t--toc-'. preg_quote( $name, '~' ). ': var\('. preg_quote( $designSetup::INKS[ (string) $knob['default'] ] ?? '', '~' ). '\);\n~', $sheet ) !== 1 )
				$sheetProblems[] = $variant. ' writes no ink '. $name;
			elseif( $knob['kind'] === 'ground' && str_contains( $sheet, "\n\t--toc-". $name. ': var(--nino-'. $knob['default']. ");\n" ) === false )
				$sheetProblems[] = $variant. ' writes no ground '. $name;

		if( preg_match( '~^/\* ==== \d+\. feature toc: '. preg_quote( $variant, '~' ). ' \((.*)\) ==== \*/$~m', $sheet, $title ) !== 1 || $title[1] !== implode( ', ', $ladders ) )
			$sheetProblems[] = $variant. ' has no section of its own, or one that does not say its ladders';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) === false )
			$sheetProblems[] = $variant. ' is not in its section as the file has it';

		if( $sheetNotes !== [] || str_contains( $sheet, $designCompiler::SHARED ) === true )
			$sheetProblems[] = $variant. ' compiled with a note, or with the shared rules of a style that was not chosen';
	}

	check( 'with a look chosen the sheet carries its section, its stylesheet as the file has it, a line for every ladder of every knob it declares and the tone of its line and the ground of its box where it has them - and none of the shared rules of the styles, which stay out where no style is chosen'. ( $sheetProblems === [] ? '' : ' - '. implode( ', ', $sheetProblems ) ),
		$sheetProblems === [] && $catalogue !== [] );

	$groundSheet = $compile( [ 'features' => [ 'toc' => [ 'set' => 'box', 'knobs' => [ 'ground' => 'dark', 'edge' => 'accent' ] ] ] ] );

	check( 'a ground moved is written as the surface and every part of it - the colour, the text on it, the quieter one, the link, the line, the focus - the properties the stylesheet reads, and a tone moved for the line is written as the tone of the page',
		str_contains( $groundSheet, "\n\t--toc-ground: var(--nino-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--toc-ground-on: var(--nino-on-dark);\n" ) === true && str_contains( $groundSheet, "\n\t--toc-ground-on-muted: var(--nino-on-dark-muted);\n" ) === true
		&& str_contains( $groundSheet, "\n\t--toc-ground-link: var(--nino-dark-link);\n" ) === true && str_contains( $groundSheet, "\n\t--toc-ground-border: var(--nino-dark-border);\n" ) === true && str_contains( $groundSheet, "\n\t--toc-ground-focus: var(--nino-dark-focus);\n" ) === true
		&& str_contains( $groundSheet, "\n\t--toc-edge: var(--color-accent);\n" ) === true );

	// The style chosen: the option under its own line, behind the css of the look - whichever it is - and nothing shared in front of it
	$styleProblems = [];
	$option 			 = (string) array_key_first( $styled[$styleName]['options'] ?? [] );

	foreach( array_keys( $catalogue ) as $variant ) {
		$sheet	= $compile( [ 'features' => [ 'toc' => [ 'set' => $variant, 'styles' => [ $styleName => $option ] ] ] ] );
		$line 	= sprintf( $designCompiler::STYLE, $styleName, $option );

		if( substr_count( $sheet, $line ) !== 1 || str_contains( $sheet, $designCompiler::SHARED ) === true || $designSetup::featureShared( $appData, 'toc' ) !== '' )
			$styleProblems[] = $variant. ': the option stands once under its own line, and nothing is shared';
		elseif( str_contains( $sheet, rtrim( (string) file_get_contents( (string) $optionFiles[0] ), "\n" ) ) === false || strpos( $sheet, $line ) < strpos( $sheet, rtrim( (string) file_get_contents( $design. '/'. $variant. '.css' ), "\n" ) ) )
			$styleProblems[] = $variant. ': the option is not in the sheet as the file has it, behind the look';
	}

	check( 'the place chosen puts the file of the option under its own line behind the css of the look, whichever it is, and writes no shared rules'. ( $styleProblems === [] ? '' : ' - '. implode( ', ', $styleProblems ) ), $styleProblems === [] && count( $catalogue ) === 3 && $option !== '' );

	// The bundle of a project the wizard has set up lists the sheet of the feature after the theme the Design feature writes
	$bundled = $appData;
	$bundled['/nino/html/assets'] = [ '/.cache/style.css' => array_merge( (array) ( \Nino\AppData::DEFAULTS['/nino/html/assets']['/.cache/style.css'] ?? [] ), [ $designCompiler::TARGET, '/assets/style.css' ] ) ];
	\Nino\Features::registerAssets( $bundled );
	$bundle = $bundled['/nino/html/assets']['/.cache/style.css'];

	check( 'in a booted project the bundle lists toc.css after the theme.css the Design feature writes its sheet to - the order the :where() of its defaults is for',
		in_array( '/features/Toc/assets/toc.css', $bundle, true ) === true && array_search( '/features/Toc/assets/toc.css', $bundle, true ) > array_search( $designCompiler::TARGET, $bundle, true ) );

	$appData['/nino/modules']					= $modulesKept;
	$appData['./nino/locales/current']	= $localeKept;
	unset( $appData['./nino/features/all'], $appData['./nino/features/listed'], $appData['./design/features'] );

	ninoWarnings();
}

echo "\n";


// --- The browser half ----------------------------------------------------------

$node = trim( (string) @shell_exec( 'command -v node 2>/dev/null' ) );

if( $node === '' )
	echo "  --  node is not on the path, so toc-js-smoke.js is not run here\n\n";
else {
	echo "toc-js-smoke.js\n";
	// The checkout this test runs against is the one whose Nino.js the script runs over
	$out = (string) @shell_exec( 'NINO_ROOT='. escapeshellarg( $root ). ' '. escapeshellarg( $node ). ' '. escapeshellarg( __DIR__. '/toc-js-smoke.js' ). ' 2>&1' );
	echo $out;
	check( 'the browser half passes too', preg_match( '/^\d+ checks, 0 failed$/m', $out ) === 1 );
	echo "\n";
}


// --- Components ----------------------------------------------------------------

echo "Components - [toc] as the Builder offers it\n";

( static function( array $appData ): void {

	$declared = \Nino\Features::manifest( dirname( __DIR__ ) )['components'] ?? [];

	// Every documented call form, as it renders before the wrapper is there
	$calls = [
		'[toc]',
		'[toc levels="2"]',
		'[toc levels="3,2"]',
		'[toc within="#content"]',
		'[toc title="Auf dieser Seite"]',
		'[toc title="Ada & Co" within="main > .x"]',
	];

	$before = [];
	foreach( $calls as $call )
		$before[$call] = \Nino\Html::renderHtml( $appData, $call );

	/*	The kernel reads the manifest ahead of the feature's own init() - that is
		the order here too, on a copy of the project's data that nothing else in
		this test sees. The shortcodes the feature registered above are those of
		a boot that has none to read; this boot registers each of them once	*/
	$probe = $appData;
	foreach( [ 'toc' ] as $shortcode )
		\Nino\Callbacks::removeCallbacks( $probe, '/nino/html/shortcode/'. $shortcode );
	\Nino\Modules\Components::init( $probe );
	\Nino\Modules\Toc::init( $probe );
	ninoWarnings();

	check( 'the component is registered with the schema the manifest declares',
		( \Nino\Modules\Components::components( $probe )['toc'] ?? null ) === ( $declared['toc'] ?? false ) );
	check( '...as one meant once per page: the registered schema says it stays out of a loop',
		( \Nino\Modules\Components::components( $probe )['toc']['loop'] ?? null ) === false );
	check( '...and what the feature registers is answered once - the component by its wrapper',
		\Nino\Features::shortcodes( $probe, 'toc' ) === [ 'toc' ]
		&& count( $registered = \Nino\Callbacks::registered( $probe, '/nino/html/shortcode/toc' ) ) === 1 && $registered[0] instanceof \Closure );
	check( '...with the defaults of the schema, as strings',
		\Nino\Modules\Components::defaults( $probe, 'toc' ) === [ 'levels' => '', 'within' => '', 'title' => '', 'class' => '' ] );

	foreach( $calls as $call )
		check( 'renders as it did: '. $call, \Nino\Html::renderHtml( $probe, $call ) === $before[$call] );

	ninoWarnings();

	check( 'every attribute written out at its default is the call without it',
		\Nino\Html::renderHtml( $probe, '[toc levels="" within="" title="" class=""]' ) === $before['[toc]'] );
	check( 'a class of its own comes after the list\'s own, no class leaves no space behind',
		str_contains( \Nino\Html::renderHtml( $probe, '[toc class="side"]' ), '<nav class="nino-toc side" ' ) === true
		&& str_contains( $before['[toc]'], '<nav class="nino-toc" ' ) === true );

} )( $appData );

echo "\n";


// --- Deactivation --------------------------------------------------------------

echo "Deactivation\n";

check( 'deactivation succeeds', \Nino\Features::deactivate( $appData, 'toc' ) === true );
check( 'the class is gone from /nino/modules', in_array( '\\Nino\\Modules\\Toc', \Nino\Filesystem::getFileContent( $appData, '/config.php', [] )['/nino/modules'], true ) === false );
check( 'the words stay in the project\'s text files - they are the project\'s now',
	isset( \Nino\Filesystem::getFileContent( $appData, '/text/en_US.php', [] )['[[/feature/toc/list/title]]'] ) === true );

ninoDone( $appData );
