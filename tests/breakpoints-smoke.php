<?php
declare(strict_types=1);

/**
 *	Nino features
 *	breakpoints-smoke.php	The one line a catalogue of features holds for the
 *												widths its stylesheets answer to: three steps - a phone
 *												under 768 px, a tablet from 768 px, a desktop from
 *												1024 px - and four thresholds between and above them,
 *												640, 768, 1024 and 1280 px, each written in px and each
 *												as min-width. A rule is the phone's until a wider step
 *												sets it otherwise ("mobile first"), so the kernel's own
 *												sheet, Nino.css, is answered at the same widths and a
 *												feature never has a width of its own to explain.
 *
 *												This reads every file of a feature that a visitor is
 *												sent or that writes what a visitor is sent - .css, .php,
 *												.tpl, .html, .htm, .svg, .js, .mjs, but for the workbench's
 *												own assets/admin.css and assets/admin.js of a feature and
 *												the tests - and fails on a condition on the width that is
 *												not one of the four min-widths, wherever a browser is
 *												asked for it: @media, @import with a media query,
 *												@custom-media, the media and sizes attributes of a tag,
 *												the media property of a link, matchMedia. A max-width
 *												(the rule is turned round: the phone's value is the rule,
 *												the wider step sets the other), 900 px, 767.98 px, a
 *												threshold in rem or em, a range, a query level 4 writes
 *												as `width >= 48rem`, a negation are breaches. A query on
 *												anything but the width (the pointer, the motion, the
 *												colour scheme, the print) is not its business.
 *
 *												A container query (@container) asks the width of a box
 *												and not of the window, so it is no breakpoint of the
 *												page and is not held to the line: a component may size
 *												itself by the room it has. Written down here so that it
 *												is a decision and no gap.
 *
 *												A condition that php or a script puts together from
 *												parts - `'@media '. $c`, `"(min-width: ". $width. "px)"` - cannot be
 *												read, and is a breach, but for a place listed in COMPOSED
 *												below: the file, a piece of the line, and where the
 *												values come from - a constant or a table of the code,
 *												which the test reads and which may hold 640, 768, 1024
 *												and 1280 and nothing else. An entry of COMPOSED that
 *												matches no line fails as well.
 *
 *												The exceptions are a short list in EXCEPTIONS below, a
 *												file and the one condition in it that stays as it is,
 *												with its reason, for a rule that cannot be turned round
 *												without a side effect - the condition stands once in the
 *												file, a second breach of the same words is refused, and an exception that no
 *												longer is one fails this too, so the list cannot outlive
 *												what it was for. It holds one.
 *
 *	Usage: php tests/breakpoints-smoke.php
 */

$here = dirname( __DIR__ );

/*	The four thresholds, in px	*/
const THRESHOLDS = [ 640, 768, 1024, 1280 ];

/*	The conditions on the width that may stay as they are though they are not one of the four min-widths: relative path => condition as it is written => reason.
	A rule goes here only when turning it round has a side effect that the rule cannot take away itself	*/
const EXCEPTIONS = [
	'features/Design/library/footer/one-legal-row/style.css' => [
		'@media (max-width: 767.98px)' => 'the footer a site starts with: the Design\'s compile of the library at its defaults is the theme.css the kernel delivers, byte for byte (design-smoke.php), and the kernel\'s file carries this rule as it is written - it is turned round in the release that brings the kernel\'s file with it',
	],
];

/*	The places where php or a script puts a condition together from parts, each with where its widths come from: relative path => list of [ 'line' => a piece of the line as it is written,
	'values' => [ the file the widths are written in, a pattern whose first group holds them ] ]. The test reads the widths there and holds them to the four thresholds.
	The 0 of the Design's loop is the part without a query and stands outside the group	*/
const COMPOSED = [
	'features/Design/Compiler/Compiler.php' => [
		[ 'line' => '"@media (min-width: ". $width. "px) {\n"', 'values' => [ 'features/Design/Compiler/Compiler.php', '/foreach\(\s*\[\s*0\s*,([^\]]*)\]\s*as\s*\$width\s*\)/' ] ],
	],
];

$failures	= 0;
$checks		= 0;

/**
 *	Assert a condition and print the result
 *
 *	@param		string		$label				Description of the check
 *	@param		bool			$condition		Result to assert
 *
 *	@return		void
 */
function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	if( $condition === true ) {
		echo "  ok  - $label\n";
		return;
	}
	$failures++;
	echo "FAIL  - $label\n";
}

/**
 *	The conditions on the width in a media query that are not one of the four min-widths
 *
 *	The four are taken out first, whole, each as `(min-width: 768px)` - the number one of the four, the unit px, nothing else in the
 *	parentheses -, and whatever still speaks of the width afterwards is a breach: another threshold, another unit, max-width, a
 *	range in either writing, the device's width. A query that names the width and is negated or put as an alternative with `not`
 *	is a breach as well, since `not (min-width: 768px)` is a max-width written the other way round
 *
 *	@param		string		$condition		What stands between `@media` and the brace, or in a media or sizes attribute
 *
 *	@return		array											The breaches, as they are written; none for a query that holds the line or does not speak of the width
 */
function widthBreaches( string $condition ): array {

	$condition = strtolower( trim( (string) preg_replace( '/\s+/', ' ', $condition ) ) );

	if( preg_match( '/width/', $condition ) !== 1 )
		return [];

	$allowed = '(?:'. implode( '|', THRESHOLDS ). ')px';
	$rest		 = (string) preg_replace( '/\(\s*min-width\s*:\s*'. $allowed. '\s*\)/', '', $condition );

	if( preg_match( '/width/', $rest ) === 1 )
		return [ $condition ];

	return preg_match( '/(?:^|[\s,(])not\b/', $condition ) === 1 ? [ $condition ] : [];
}

/**
 *	What a text asks the browser about the width: the breaches, and the offsets of the places that put a condition together from parts
 *
 *	@param		string		$text					A stylesheet, or the text of a string or a file that writes one
 *	@param		bool			$isCss				A stylesheet: only the at-rules are looked for
 *
 *	@return		array											[ breaches as they are written, offsets of what cannot be read ]
 */
function scanText( string $text, bool $isCss ): array {

	$breaches	= [];
	$composed	= [];
	$literal	= $isCss === true ? '[^{;]*' : '[^{;"\'`]*';

	// @media: the prelude up to the brace; in code a prelude that a quote ends is put together
	if( preg_match_all( '/@media\b/i', $text, $found, PREG_OFFSET_CAPTURE ) > 0 )
		foreach( $found[0] as [ , $at ] ) {

			$rest = substr( $text, $at + 6 );

			if( preg_match( '/^('. $literal. ')\{/', $rest, $prelude ) === 1 )
				foreach( widthBreaches( $prelude[1] ) as $breach )
					$breaches[] = '@media '. $breach;
			else if( $isCss === false )
				$composed[] = $at;
		}

	// @import with a media query, @custom-media: what is left of the statement when the address is cut out
	if( preg_match_all( '/@(import|custom-media)\b([^;{]*);/i', $text, $found, PREG_SET_ORDER ) > 0 )
		foreach( $found as $statement ) {

			$query = (string) preg_replace( '/url\([^)]*\)|"[^"]*"|\'[^\']*\'|--[\w-]+/i', '', $statement[2] );

			foreach( widthBreaches( $query ) as $breach )
				$breaches[] = '@'. strtolower( $statement[1] ). ' '. $breach;
		}

	if( $isCss === true )
		return [ $breaches, $composed ];

	// The attributes of a tag a browser reads a query from
	if( preg_match_all( '/<(?:link|source|style|img)\b[^>]*>/i', $text, $tags, PREG_OFFSET_CAPTURE ) > 0 )
		foreach( $tags[0] as [ $tag, $at ] )
			if( preg_match_all( '/\b(media|sizes)\s*=\s*(["\'])(.*?)\2/is', $tag, $attributes, PREG_SET_ORDER ) > 0 )
				foreach( $attributes as $attribute ) {

					if( str_contains( $attribute[3], '[[' ) === true || str_contains( $attribute[3], '$' ) === true )
						$composed[] = $at;

					foreach( widthBreaches( $attribute[3] ) as $breach )
						$breaches[] = $attribute[1]. ' '. $breach;
				}

	// A script: the media property of a link, the attribute set by hand, matchMedia - a literal is read, anything else is put together
	if( preg_match_all( '/(?:\.\s*media\s*=|setAttribute\s*\(\s*[\'"](?:media|sizes)[\'"]\s*,|matchMedia\s*\()\s*(?:([\'"`])((?:(?!\1).)*)\1(?=\s*[;,)]|\s*$)|)/sm', $text, $found, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) > 0 )
		foreach( $found as $call ) {

			$value = $call[2][0] ?? '';

			if( ( $call[1][0] ?? '' ) === '' || str_contains( $value, '${' ) === true )
				$composed[] = $call[0][1];
			else
				foreach( widthBreaches( $value ) as $breach )
					$breaches[] = 'script '. $breach;
		}

	return [ $breaches, $composed ];
}

/**
 *	What one file of a feature asks the browser about the width
 *
 *	@param		string		$source				The file
 *	@param		string		$extension		Lower case
 *
 *	@return		array											[ breaches, lines that put a condition together, each as the file writes it ]
 */
function scanFile( string $source, string $extension ): array {

	$lines		= explode( "\n", $source );
	$breaches	= [];
	$lineTexts	= [];

	if( $extension === 'css' ) {

		[ $found ] = scanText( (string) preg_replace( '~/\*.*?\*/~s', '', $source ), true );

		return [ $found, [] ];
	}

	// php: the strings only - a comment that names a query is no query
	if( $extension === 'php' ) {

		foreach( token_get_all( $source ) as $token ) {

			if( is_array( $token ) === false || in_array( $token[0], [ T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML ], true ) === false )
				continue;

			[ $found, $composed ] = scanText( $token[1], false );
			$breaches = array_merge( $breaches, $found );

			foreach( $composed as $offset )
				$lineTexts[] = trim( (string) preg_replace( '/\s+/', ' ', $lines[ $token[2] - 1 + substr_count( substr( $token[1], 0, $offset ), "\n" ) ] ?? '' ) );
		}

		return [ $breaches, $lineTexts ];
	}

	if( in_array( $extension, [ 'js', 'mjs' ], true ) === true )
		$source = (string) preg_replace( [ '~/\*.*?\*/~s', '~(?<![:\'"])//[^\n]*~' ], '', $source );
	else
		$source = (string) preg_replace( '/<!--.*?-->/s', '', $source );

	[ $breaches, $composed ] = scanText( $source, false );
	$lines = explode( "\n", $source );

	foreach( $composed as $offset )
		$lineTexts[] = trim( (string) preg_replace( '/\s+/', ' ', $lines[ substr_count( substr( $source, 0, $offset ), "\n" ) ] ?? '' ) );

	return [ $breaches, $lineTexts ];
}

/**
 *	What is wrong with the lines that put a condition together, held to a table of the places that may
 *
 *	@param		string		$file					Relative path of the file the lines are in
 *	@param		array			$lines				The lines, as the file writes them
 *	@param		array			$table				The COMPOSED shape: path => list of [ line, values ]
 *	@param		callable	$read					Relative path => the file's text, '' where there is none
 *
 *	@return		array											One message per problem
 */
function composedProblems( string $file, array $lines, array $table, callable $read ): array {

	$problems = [];

	foreach( $lines as $line )
		if( array_filter( $table[$file] ?? [], static fn( array $entry ): bool => str_contains( $line, $entry['line'] ) === true ) === [] )
			$problems[] = $file. ': a condition is put together from parts and the file is not in COMPOSED for it - '. $line;

	foreach( $table[$file] ?? [] as $entry ) {

		if( array_filter( $lines, static fn( string $line ): bool => str_contains( $line, $entry['line'] ) === true ) === [] ) {
			$problems[] = $file. ': nothing is put together in '. $entry['line'];
			continue;
		}

		[ $source, $pattern ] = $entry['values'];

		if( preg_match( $pattern, $read( $source ), $widths ) !== 1 || preg_match_all( '/\d+(?:\.\d+)?/', $widths[1] ?? '', $numbers ) < 1 ) {
			$problems[] = $file. ': the widths of '. $entry['line']. ' are not found in '. $source;
			continue;
		}

		foreach( $numbers[0] as $number )
			if( in_array( (float) $number, array_map( 'floatval', THRESHOLDS ), true ) === false )
				$problems[] = $file. ': the table of '. $entry['line']. ' holds '. $number. ', which is no threshold';
	}

	return $problems;
}

echo "The widths the stylesheets answer to\n";

/*	The rule itself, on conditions as they are written. What holds the line, and what each breach is	*/
$holds = [
	'(min-width: 640px)', '(min-width:768px)', '( min-width : 1024px )', '(MIN-WIDTH: 1280PX)', 'screen and (min-width: 768px)',
	'(min-width: 768px) and (prefers-reduced-motion: reduce)', '(prefers-reduced-motion: reduce)', '(pointer: coarse)', 'print', 'not all and (pointer: coarse)',
	'(min-width: 768px) and (min-width: 1024px)', '(min-width: 768px) 50vw, 100vw',
];
$breaks = [
	'a max-width'											=> '(max-width: 767px)',
	'a max-width at the tablet'				=> '(max-width: 1023.98px)',
	'a max-width of 640 px'						=> '(max-width: 640px)',
	'a range of both'									=> '(min-width: 768px) and (max-width: 1023px)',
	'a threshold of 900 px'						=> '(min-width: 900px)',
	'the same as a max-width'					=> '(max-width: 900px)',
	'a threshold of 767 px'						=> '(min-width: 767px)',
	'a fraction of a pixel'						=> '(min-width: 767.98px)',
	'a threshold in rem'							=> '(min-width: 40rem)',
	'a threshold in em'								=> '(min-width: 48em)',
	'a rem that is the workbench step'	=> '(min-width: 64rem)',
	'the number of a threshold in rem'	=> '(min-width: 768rem)',
	'the number of a threshold in em'	=> '(min-width: 768em)',
	'another px'											=> '(min-width: 1100px)',
	'a bare number'										=> '(min-width: 768)',
	'a query of level 4'							=> '(width >= 768px)',
	'a query of level 4, turned'			=> '(768px <= width)',
	'a width exactly'									=> '(width: 768px)',
	'the width of the device'					=> '(min-device-width: 768px)',
	'an alternative of two'						=> '(min-width: 768px), (max-width: 400px)',
	'a negation'											=> 'not all and (min-width: 768px)',
	'a size of an image by a max-width'	=> '(max-width: 600px) 480px, 800px',
];

$wrongHolds = array_values( array_filter( $holds, static fn( string $condition ): bool => widthBreaches( $condition ) !== [] ) );
$wrongBreaks = [];

foreach( $breaks as $label => $condition )
	if( widthBreaches( $condition ) === [] )
		$wrongBreaks[] = $label;

check( 'the four thresholds in px as min-width are the line, alone or with a query on anything else'. ( $wrongHolds === [] ? '' : ' - refused: '. implode( ' | ', $wrongHolds ) ), $wrongHolds === [] );
check( '...and every other condition on the width is a breach: max-width, 900 px, 767 px, 767.98 px, rem, em, a range, level 4, the device, a negation, a size'. ( $wrongBreaks === [] ? '' : ' - let through: '. implode( ', ', $wrongBreaks ) ), $wrongBreaks === [] );

/*	Everywhere a browser is asked, each in a small source of its kind: a breach that is found, and the line that holds is let through	*/
$bad = '(max-width: 767px)';
$kinds = [
	'a media rule of a stylesheet'		=> [ 'css', ".a{color:red}\n@media $bad {\n.a{color:blue}\n}", "@media $bad" ],
	'a media rule in a php string'		=> [ 'php', "<?php \$r = '@media $bad { :root { --a: 1; } }';", "@media $bad" ],
	'an import with a query'					=> [ 'css', "@import url(\"width.css\") $bad;\n.a{}", "@import $bad" ],
	'a custom media'									=> [ 'css', "@custom-media --narrow $bad;", "@custom-media $bad" ],
	'the media attribute of a link'		=> [ 'html', "<link rel=\"stylesheet\" href=\"a.css\" media=\"$bad\">", "media $bad" ],
	'the media attribute in a template'	=> [ 'tpl', "<style media='$bad'>a{}</style>", "media $bad" ],
	'the media attribute of a source'	=> [ 'svg', "<source srcset=\"a.png\" media=\"$bad\">", "media $bad" ],
	'the sizes of an image'						=> [ 'html', "<img srcset=\"a 1x\" sizes=\"$bad 480px, 800px\">", "sizes $bad 480px, 800px" ],
	'the media property of a link'		=> [ 'js', "link.media = '$bad';", "script $bad" ],
	'an attribute set by hand'				=> [ 'mjs', "link.setAttribute( 'media', \"$bad\" );", "script $bad" ],
	'matchMedia'											=> [ 'js', "window.matchMedia('$bad').matches", "script $bad" ],
	'matchMedia with a space'					=> [ 'js', "window.matchMedia ( \"$bad\" ).matches", "script $bad" ],
	'matchMedia in a template'				=> [ 'tpl', "<script>matchMedia(`$bad`)</script>", "script $bad" ],
];
$missed = [];

foreach( $kinds as $label => [ $extension, $source, $expected ] )
	if( scanFile( $source, $extension )[0] !== [ $expected ] )
		$missed[] = $label;

check( 'a width is read wherever a browser is asked for it: @media in a stylesheet and in a php string, @import, @custom-media, the media and sizes attributes, the media property, matchMedia'. ( $missed === [] ? '' : ' - missed: '. implode( ', ', $missed ) ), $missed === [] );

$let = [
	'a comment that names a query'		=> [ 'css', "/* @media $bad { */\n.a{}" ],
	'a php comment that names a query'	=> [ 'php', "<?php\n// @media $bad\n/** the @media query $bad */\n\$a = 1;" ],
	'a js comment'										=> [ 'js', "// matchMedia('$bad')\n/* link.media = '$bad' */" ],
	'the line itself'									=> [ 'css', '@media (min-width: 768px) { .a{} } @import url("a.css") (min-width: 1024px); @media print { .a{} }' ],
	'a container query'								=> [ 'css', "@container card $bad { .a{} }" ],
	'a media attribute on the line'		=> [ 'html', '<link rel="stylesheet" href="a.css" media="(min-width: 640px)">' ],
	'a file name with width in it'		=> [ 'css', '@import url("width.css");' ],
	'matchMedia on the pointer'				=> [ 'js', "matchMedia('(pointer: coarse)'); link.media = 'print';" ],
	'an html comment'									=> [ 'html', "<!-- <link media=\"$bad\"> -->" ],
];
$wrong = [];

foreach( $let as $label => [ $extension, $source ] )
	if( scanFile( $source, $extension )[0] !== [] )
		$wrong[] = $label;

check( '...and what is no condition on the width is let through: a comment, the four min-widths, a container query - which asks the width of a box and is no breakpoint of the page -, a query on the pointer or the print'. ( $wrong === [] ? '' : ' - refused: '. implode( ', ', $wrong ) ), $wrong === [] );

/*	A condition put together from parts is a breach unless the table lists the place and its widths are the four thresholds	*/
$php = <<<'PHP'
<?php
$rule = "@media (min-width: ". $width. "px) {";
$call = 'window.matchMedia( ' . $query . ' )';
PHP;
[ $found, $lines ] = scanFile( $php, 'php' );
$js = "var q = window.matchMedia( query );\nlink.media = '(min-width: ' + width + 'px)';\n";
[ , $jsLines ] = scanFile( $js, 'js' );
$entry = [ 'line' => '"@media (min-width: ". $width. "px) {"', 'values' => [ 'w.php', '/WIDTHS\s*=\s*\[([^\]]*)\]/' ] ];
$reader = static fn( string $path ): string => [ 'w.php' => 'const WIDTHS = [ 768, 1024 ];', 'bad.php' => 'const WIDTHS = [ 768, 900 ];', 'none.php' => 'const OTHER = 1;' ][$path] ?? '';

check( 'a condition put together from parts is found in php and in a script, as the lines that write it', $found === [] && count( $lines ) === 2 && count( $jsLines ) === 2 && str_contains( $lines[0], '"@media (min-width: ". $width' ) === true );
check( '...and it is a breach unless COMPOSED lists the file and the line and the widths it draws from are 768 and 1024 - and a table of 900, a table that is not found, a line that is not listed and an entry that matches no line are refused',
	composedProblems( 'a.php', [ $lines[0] ], [ 'a.php' => [ $entry ] ], $reader ) === []
	&& composedProblems( 'a.php', [ $lines[0] ], [ 'a.php' => [ [ 'values' => [ 'bad.php', $entry['values'][1] ] ] + $entry ] ], $reader ) !== []
	&& composedProblems( 'a.php', [ $lines[0] ], [ 'a.php' => [ [ 'values' => [ 'none.php', $entry['values'][1] ] ] + $entry ] ], $reader ) !== []
	&& composedProblems( 'a.php', [ $lines[0], $lines[1] ], [ 'a.php' => [ $entry ] ], $reader ) !== []
	&& composedProblems( 'a.php', [ $lines[1] ], [ 'a.php' => [ $entry ] ], $reader ) !== []
	&& composedProblems( 'b.php', [ $lines[0] ], [ 'a.php' => [ $entry ] ], $reader ) !== [] );

/*	The files: everything a visitor is sent or that writes it	*/
$sources		= [];
$composed		= [];
$scanned		= 0;
$stylesheets	= 0;
$conditions	= 0;

$directory = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $here. '/features', FilesystemIterator::SKIP_DOTS ) );

foreach( $directory as $file ) {

	$relative = substr( $file->getPathname(), strlen( $here ) + 1 );

	// A feature's tests and the fixtures of them are not what a visitor is sent
	if( preg_match( '#/tests/#', '/'. $relative ) === 1 || $file->isFile() === false )
		continue;

	$extension = strtolower( $file->getExtension() );

	if( in_array( $extension, [ 'css', 'php', 'tpl', 'html', 'htm', 'svg', 'js', 'mjs' ], true ) === false )
		continue;

	// The workbench's own stylesheet and script of a feature, where they stand: they follow the workbench of the kernel
	if( preg_match( '#^features/[A-Za-z0-9]+/assets/admin\.(?:css|js)$#', $relative ) === 1 )
		continue;

	$text = (string) file_get_contents( $file->getPathname() );

	[ $found, $lines ] = scanFile( $text, $extension );
	$scanned++;
	$sources[$relative]	= $found;
	$composed[$relative]	= $lines;

	if( $extension === 'css' ) {
		$stylesheets++;
		$conditions += preg_match_all( '/@media[^{]*width/i', $text );
	}
}

check( 'there are files to read at all, and queries on the width in the stylesheets', $scanned > 300 && $stylesheets > 50 && $conditions > 50 );

$offenders = [];

foreach( $sources as $relative => $found ) {

	// An exception is one place: the condition as it is written, once
	$allowed = array_map( static fn( string $condition ): int => 1, EXCEPTIONS[$relative] ?? [] );

	foreach( array_count_values( $found ) as $condition => $count )
		if( $count > ( $allowed[$condition] ?? 0 ) )
			$offenders[] = $relative. ': '. $condition. ( $count > 1 ? ' ('. $count. ' times)' : '' );
}

check( 'no file of a visitor answers to a width but at 640, 768, 1024 or 1280 px as min-width'. ( $offenders === [] ? '' : ' - '. implode( ' | ', $offenders ) ), $offenders === [] );

$problems = [];

foreach( $composed as $relative => $lines )
	if( $lines !== [] || isset( COMPOSED[$relative] ) === true )
		$problems = array_merge( $problems, composedProblems( $relative, $lines, COMPOSED, static fn( string $path ): string => (string) @file_get_contents( $here. '/'. $path ) ) );

foreach( array_diff( array_keys( COMPOSED ), array_keys( $composed ) ) as $relative )
	$problems[] = $relative. ': the file is not read';

check( 'no condition is put together from parts but at a place of COMPOSED, whose widths are the four thresholds'. ( $problems === [] ? '' : ' - '. implode( ' | ', $problems ) ), $problems === [] );

/*	The other direction, and the reason the list stays short: an exception that is no longer needed is an exception somebody will copy	*/
$stale = [];

foreach( EXCEPTIONS as $relative => $conditions ) {

	foreach( array_keys( $conditions ) as $condition )
		if( in_array( $condition, $sources[$relative] ?? [], true ) === false )
			$stale[] = $relative. ': '. $condition;
}

check( 'every exception is still one'. ( $stale === [] ? '' : ' - nothing left to allow in: '. implode( ' | ', $stale ) ), $stale === [] );
check( '...and each carries its reason', array_filter( EXCEPTIONS, static fn( array $conditions ): bool => array_filter( $conditions, static fn( string $reason ): bool => strlen( $reason ) < 40 ) !== [] ) === [] );

/*	The workbench's own assets are left out where the workbench has them and nowhere else	*/
check( 'the workbench stylesheet of a feature is left out at assets/admin.css and a file of that name elsewhere is read',
	preg_match( '#^features/[A-Za-z0-9]+/assets/admin\.(?:css|js)$#', 'features/Menu/assets/admin.css' ) === 1
	&& preg_match( '#^features/[A-Za-z0-9]+/assets/admin\.(?:css|js)$#', 'features/Menu/design/admin.css' ) === 0
	&& preg_match( '#^features/[A-Za-z0-9]+/assets/admin\.(?:css|js)$#', 'features/Menu/assets/sub/admin.css' ) === 0 );

echo "\n". $checks. ' checks, '. $failures. " failed\n";

exit( $failures === 0 ? 0 : 1 );
