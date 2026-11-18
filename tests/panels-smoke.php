<?php
declare(strict_types=1);

/**
 *	Nino features
 *	panels-smoke.php	The panel script of every feature (assets/admin.js) keeps the rules of the workbench guide that a test can hold, the
 *										ones docs/admin-ui.md of the kernel marks [T]: it builds no button, dialog or file field of its own, asks the browser for
 *										no confirm(), alert() or prompt(), gives a form noValidate, says a success with say() and puts one primary button on a bar.
 *										Every /_admin/ text key a script reads is in both languages of the panel (or, under /common/, in the kernel's texts), the
 *										two languages of a panel have the same keys, and a panel stylesheet names no colour and only tokens of the workbench.
 *
 *										Reads features/ only, and the stylesheet and texts of the kernel NINO_ROOT names (default: ../nino).
 *
 *	Usage: php tests/panels-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/panels-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

$failures	= 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

if( is_file( $root. '/_admin/assets/style.css' ) === false || is_file( $root. '/_admin/text/en_US.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with a workbench at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

/*	The panels that are work surfaces and keep rules of their own (docs/admin-ui.md, "Work surface"): the Builder and Themes	*/
const PANELS_SURFACES = [
	'Builder' => 'a work surface: a draft with a preview that is saved as a whole, with its own bar and its own dialogs',
	'Themes'  => 'a work surface: a draft with a preview that is saved as a whole, with its own bar and its own dialogs',
];

/*	The panels that save nothing, so that there is no success to say: they read	*/
const PANELS_READING = [
	'Greeting' => 'the card of the Dashboard: it draws a greeting and facts, and changes nothing',
	'Stats'    => 'a report: it reads a month and draws it, and changes nothing',
];

/**
 *	A script as the two views of its code, of the same length: with the comments blanked, and with the strings blanked as well - except a
 *	string that is the name of a dialog, which is how window['confirm'] is written. The code of a placeholder of a template is code. A
 *	scanner and no regular expression, since a '/*' in a string ('image/*') or after a '//' is no comment, and what a string says is no call
 *
 *	@param		string		$source
 *
 *	@return		array<int, string>		[ the code with its strings, the code without them ]
 */
function panelsCode( string $source ): array {

	$plain = '';
	$masked = '';
	$length = strlen( $source );
	$at = 0;
	$last = '';
	$word = '';
	$depth = 0;
	$stack = [];
	$template = false;

	$blank = static fn( string $text ): string => (string) preg_replace( '/[^\n]/', ' ', $text );

	while( $at < $length ) {

		$c = $source[$at];
		$next = $source[$at + 1] ?? '';

		if( $template === true ) {

			if( $c === '\\' ) {
				$plain .= substr( $source, $at, 2 );
				$masked .= '  ';
				$at += 2;
			}
			elseif( $c === '$' && $next === '{' ) {
				$plain .= '${';
				$masked .= '${';
				$stack[] = $depth;
				$depth = 0;
				$template = false;
				$at += 2;
			}
			else {
				$plain .= $c;
				$masked .= $c === '`' || $c === "\n" ? $c : ' ';
				$template = $c !== '`';
				$at++;
			}

			continue;
		}

		if( $c === '/' && $next === '/' ) {
			$end = strpos( $source, "\n", $at );
			$end = $end === false ? $length : $end;
			$plain .= $blank( substr( $source, $at, $end - $at ) );
			$masked .= $blank( substr( $source, $at, $end - $at ) );
			$at = $end;
			continue;
		}

		if( $c === '/' && $next === '*' ) {
			$end = strpos( $source, '*/', $at + 2 );
			$end = $end === false ? $length : $end + 2;
			$plain .= $blank( substr( $source, $at, $end - $at ) );
			$masked .= $blank( substr( $source, $at, $end - $at ) );
			$at = $end;
			continue;
		}

		if( $c === '`' ) {
			$plain .= $c;
			$masked .= $c;
			$template = true;
			$at++;
			continue;
		}

		$regex = $c === '/' && ( $last === '' || str_contains( '(,=:[!&|?{};', $last ) === true || in_array( $word, [ 'return', 'typeof', 'case' ], true ) === true );

		if( $c === "'" || $c === '"' || $regex === true ) {

			$close = $regex === true ? '/' : $c;
			$start = $at + 1;
			$at++;
			$inClass = false;

			while( $at < $length ) {

				$d = $source[$at];

				if( $d === '\\' ) {
					$at += 2;
					continue;
				}

				if( $regex === true && $d === '[' )
					$inClass = true;
				elseif( $regex === true && $d === ']' )
					$inClass = false;
				elseif( $d === $close && $inClass === false )
					break;
				elseif( $d === "\n" )
					break;

				$at++;
			}

			$inside = substr( $source, $start, $at - $start );
			$plain .= $c. $inside. ( $source[$at] ?? '' );
			$masked .= $c. ( in_array( $inside, [ 'confirm', 'alert', 'prompt' ], true ) === true ? $inside : $blank( $inside ) ). ( $source[$at] ?? '' );
			$at++;
			$last = $regex === true ? ')' : $c;
			$word = '';
			continue;
		}

		if( $c === '{' )
			$depth++;
		elseif( $c === '}' ) {

			if( $depth === 0 && $stack !== [] ) {
				$depth = (int) array_pop( $stack );
				$plain .= '}';
				$masked .= '}';
				$template = true;
				$at++;
				continue;
			}

			$depth--;
		}

		$plain .= $c;
		$masked .= $c;
		$at++;

		if( ctype_space( $c ) === false ) {
			$last = $c;
			$word = preg_match( '/[\w$]/', $c ) === 1 ? $word. $c : '';
		}
	}

	return [ $plain, $masked ];
}

/**
 *	The calls of the browser's own dialogs a script makes: a bare confirm(), alert() or prompt(), or one on the window - by a dot or by
 *	a name in brackets - and not Nino.adminUi.confirm(), a function or method of the script's own of that name, a word in a comment or a string
 *
 *	@param		string		$source
 *
 *	@return		array<int, string>		The calls found, as they are written
 */
function panelsDialogs( string $source ): array {

	$code = panelsCode( $source )[1];

	preg_match_all( '#(?<![\w$.])(?:confirm|alert|prompt)\s*\(#', $code, $bare, PREG_OFFSET_CAPTURE );

	$bare = array_filter( $bare[0], static function( array $hit ) use ( $code ): bool {

		if( preg_match( '#\bfunction\s*\*?\s*$#', substr( $code, 0, $hit[1] ) ) === 1 )
			return false;

		$depth = 1;
		$to = $hit[1] + strlen( $hit[0] );

		while( $to < strlen( $code ) && $depth > 0 ) {
			$depth += $code[$to] === '(' ? 1 : ( $code[$to] === ')' ? -1 : 0 );
			$to++;
		}

		// A method of the script's own: its parameters are followed by a body
		return preg_match( '#\G\s*\{#', $code, $none, 0, $to ) !== 1;
	} );

	preg_match_all( '#(?<![\w$.])(?:window|wn|self|globalThis|top|parent)\s*(?:\.\s*(?:confirm|alert|prompt)|\[\s*[\'"`](?:confirm|alert|prompt)[\'"`]\s*\])\s*\(#', $code, $onWindow );

	return array_merge( array_column( $bare, 0 ), $onWindow[0] );
}

/**
 *	The text of the argument of each call of a name: from the bracket after it to its mate
 *
 *	@param		string		$masked				The code without strings
 *	@param		string		$name
 *
 *	@return		array<int, array{0: int, 1: int}>	[ from, to ] of each argument list, in the code
 */
function panelsCalls( string $masked, string $name ): array {

	$found = [];

	preg_match_all( '#(?<![\w$])'. preg_quote( $name, '#' ). '\s*\(#', $masked, $hits, PREG_OFFSET_CAPTURE );

	foreach( $hits[0] as $hit ) {

		$from = $hit[1] + strlen( $hit[0] );
		$depth = 1;
		$to = $from;

		while( $to < strlen( $masked ) && $depth > 0 ) {
			$depth += str_contains( '([{', $masked[$to] ) === true ? 1 : ( str_contains( ')]}', $masked[$to] ) === true ? -1 : 0 );
			$to++;
		}

		$found[] = [ $from, $to - 1 ];
	}

	return $found;
}

/**
 *	How many primary buttons stand on the bars of a script: for each actionBar( { ... } ), the slot called primary and every button of another slot that
 *	is written with kind : 'primary', in the call itself or in the const that the slot names
 *
 *	@param		string		$source
 *
 *	@return		array<int, int>				The count of each bar
 */
function panelsPrimaries( string $source ): array {

	[ $plain, $masked ] = panelsCode( $source );
	$counts = [];

	foreach( panelsCalls( $masked, 'actionBar' ) as [ $from, $to ] ) {

		$inside = substr( $masked, $from, $to - $from );
		$slots = [];
		$depth = 0;
		$start = 0;

		// The slots at the top of the object: key : value
		for( $i = 0, $n = strlen( $inside ); $i <= $n; $i++ ) {

			$c = $inside[$i] ?? ',';

			if( str_contains( '([{', $c ) === true )
				$depth++;
			elseif( str_contains( ')]}', $c ) === true )
				$depth--;
			elseif( $c === ',' && $depth === 1 ) {
				$slots[] = [ $start, $i ];
				$start = $i + 1;
			}
		}

		$slots[] = [ $start, $n ];
		$count = 0;

		foreach( $slots as [ $a, $b ] ) {

			$text = substr( $inside, $a, $b - $a );
			$written = substr( $plain, $from + $a, $b - $a );

			if( preg_match( '/^[\s{]*(\w+)\s*:/', $text, $key ) !== 1 )
				continue;

			$marked = preg_match_all( '/kind\s*:\s*[\'"]primary[\'"]/', $written ) ?: 0;

			foreach( preg_match_all( '/(?<![\w$.\'"])([a-z]\w*)(?![\w(\'":])/', substr( $text, strlen( $key[0] ) ), $names ) !== false ? array_unique( $names[1] ) : [] as $name )
				if( preg_match( '/\bconst\s+'. preg_quote( $name, '/' ). '\s*=[^;]*kind\s*:\s*[\'"]primary[\'"]/', $plain ) === 1 )
					$marked++;

			$count += $key[1] === 'primary' ? 1 : $marked;
		}

		$counts[] = $count;
	}

	return $counts;
}

/**
 *	The /_admin/ keys a script reads, and the prefixes it puts a key together from
 *
 *	@param		string		$source
 *
 *	@return		array{0: array<int, string>, 1: array<int, string>}		[ whole keys, prefixes ending in a slash ]
 */
function panelsKeys( string $source ): array {

	$plain = panelsCode( $source )[0];
	$keys = [];
	$prefixes = [];
	$helpers = [];

	// A function that puts a prefix before the name it is given: word( 'msg/sent' ) reads /_admin/sysadmin/msg/sent
	preg_match_all( '#const\s+(\w+)\s*=\s*function\(\s*\w+\s*\)\s*\{\s*return\s+[\w.]+\(\s*[\'"](/_admin/[A-Za-z0-9_/-]*/)[\'"]\s*\+#', $plain, $made, PREG_SET_ORDER );

	foreach( $made as $helper )
		$helpers[$helper[1]] = $helper[2];

	preg_match_all( '#([\'"])(/_admin/[A-Za-z0-9_/-]*)\1(\s*\+)?#', $plain, $literals, PREG_SET_ORDER );

	foreach( $literals as $literal )
		if( ( $literal[3] ?? '' ) !== '' && str_ends_with( $literal[2], '/' ) === true )
			$prefixes[] = $literal[2];
		elseif( in_array( $literal[2], array_values( $helpers ), true ) === false || ( $literal[3] ?? '' ) === '' )
			$keys[] = $literal[2];

	foreach( $helpers as $name => $prefix ) {

		$prefixes = array_diff( $prefixes, [ $prefix ] );

		preg_match_all( '#(?<![\w$.])'. preg_quote( $name, '#' ). '\(\s*([\'"])([A-Za-z0-9_/-]+)\1(\s*\+)?#', $plain, $calls, PREG_SET_ORDER );

		foreach( $calls as $call )
			if( ( $call[3] ?? '' ) !== '' )
				$prefixes[] = $prefix. $call[2];
			else
				$keys[] = $prefix. $call[2];
	}

	return [ array_values( array_unique( $keys ) ), array_values( array_unique( $prefixes ) ) ];
}

/**
 *	@param		string		$file
 *
 *	@return		array<int, string>		The keys of a text file, without their brackets
 */
function panelsTexts( string $file ): array {

	return is_file( $file ) === true ? array_map( static fn( string|int $key ): string => trim( (string) $key, '[]' ), array_keys( (array) include $file ) ) : [];
}


$locales = [ 'de_DE', 'en_US' ];
$panels = [];
$texts = [];

foreach( (array) scandir( $repo. '/features' ) as $name ) {

	$dir = $repo. '/features/'. $name;

	if( $name[0] === '.' || is_dir( $dir ) === false )
		continue;

	foreach( $locales as $locale )
		$texts[$name][$locale] = panelsTexts( $dir. '/text/'. $locale. '.php' );

	if( is_file( $dir. '/assets/admin.js' ) === true && isset( PANELS_SURFACES[$name] ) === false )
		$panels[$name] = (string) file_get_contents( $dir. '/assets/admin.js' );
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );

check( 'the panel scripts were read', count( $panels ) > 8 );

echo "\nWhat a panel does not build itself\n";

$own = [];
$asking = [];

foreach( $panels as $name => $source ) {

	[ $plain ] = panelsCode( $source );

	if( preg_match( '/\b(?:el|createElement)\s*\(\s*[\'"](?:button|dialog)[\'"]|<(?:button|dialog)\b|nino-admin-btn|[\'"]type[\'"]\s*,\s*[\'"]file[\'"]|\btype\s*[:=]\s*[\'"]file[\'"]|\[type=[\'"]?file/', $plain ) === 1 )
		$own[] = $name;

	if( panelsDialogs( $source ) !== [] )
		$asking[] = $name;
}

check( 'no panel script builds a button, a dialog or a file field of its own'. $what( $own ), $own === [] );
check( 'no panel script calls the browser\'s confirm(), alert() or prompt()'. $what( $asking ), $asking === [] );
check( 'the detector finds a bare call, one on the window and on its alias, in the code of a template placeholder - and not a method, a string or a comment',
	panelsDialogs( "if( confirm('x') ) {}" ) !== [] && panelsDialogs( "wn['confirm']( 'x' )" ) !== [] && panelsDialogs( "window . prompt('x')" ) !== []
	&& panelsDialogs( 'x = `a ${ wn.confirm("x") } b`;' ) !== [] && panelsDialogs( "x = 'image/*'; alert(1);" ) !== []
	&& panelsDialogs( "x = { confirm( q ) { return q } }; Nino.adminUi.confirm( 'big', {} );" ) === [] && panelsDialogs( "// alert('x')\nx = 'confirm(1)'; /* prompt(1) */" ) === [] );

echo "\nHow a panel saves\n";

$forms = [];
$saying = [];

foreach( $panels as $name => $source ) {

	[ $plain ] = panelsCode( $source );

	preg_match_all( '/(\w+)\s*=\s*(?:[\w.]+\.)?el\(\s*[\'"]form[\'"]/', $plain, $made );

	foreach( $made[1] as $variable )
		if( preg_match( '/\b'. preg_quote( $variable, '/' ). '\.noValidate\s*=\s*true\b/', $plain ) !== 1 )
			$forms[] = $name. ' '. $variable;

	if( isset( PANELS_READING[$name] ) === false && preg_match( '/\bNino\.adminUi\.say\(/', $plain ) !== 1 )
		$saying[] = $name;
	elseif( isset( PANELS_READING[$name] ) === true && preg_match( '/\bNino\.adminUi\.say\(/', $plain ) === 1 )
		$saying[] = $name. ' says something and is listed as reading';
}

check( 'every form a panel makes has noValidate'. $what( $forms ), $forms === [] );
check( 'every panel that changes something says its success with Nino.adminUi.say(), and no panel listed as reading does'. $what( $saying ), $saying === [] );

$bars = [];
$counted = 0;

foreach( $panels as $name => $source )
	foreach( panelsPrimaries( $source ) as $count ) {

		$counted++;

		if( $count > 1 )
			$bars[] = $name. ': '. $count. ' primary buttons on one bar';
	}

check( 'no bar of a panel has more than one primary button ('. $counted. ' bars read)'. $what( $bars ), $bars === [] && $counted > 10 );

echo "\nThe words of a panel\n";

$unsaid = [];
$unfound = [];

foreach( $panels as $name => $source ) {

	[ $keys, $prefixes ] = panelsKeys( $source );

	foreach( $locales as $locale ) {

		$own = $texts[$name][$locale];
		$kernel = panelsTexts( $root. '/_admin/text/'. $locale. '.php' );

		foreach( $keys as $key )
			if( in_array( $key, $own, true ) === false && ( str_starts_with( $key, '/_admin/common/' ) === false || in_array( $key, $kernel, true ) === false ) )
				$unsaid[$name. ' '. $key] = true;

		foreach( $prefixes as $prefix )
			if( array_filter( array_merge( $own, str_starts_with( $prefix, '/_admin/common/' ) === true ? $kernel : [] ), static fn( string $key ): bool => str_starts_with( $key, $prefix ) ) === [] )
				$unsaid[$name. ' '. $prefix. '...'] = true;
	}

	if( $keys === [] && $prefixes === [] )
		$unfound[] = $name;
}

// Known, and to be fixed by the feature: a key that stands in neither language of the panel and is no key of /common/. Each panel works - the
// kernel's Features module has the word - but a panel owns every word it reads (the kernel has /_admin/common/split/settings as well). An entry
// that the scripts stop offending against fails the test, so the list is one of what is still to be fixed
$known = [];

$open = array_keys( array_diff_key( $unsaid, $known ) );
$gone = array_keys( array_diff_key( $known, $unsaid ) );

check( 'every /_admin/ key a panel script reads is in both languages of the panel, or under /common/ in the kernel\'s texts - but the known ones'. $what( $open ), $open === [] );
check( 'every known offender still offends, so that the list is one of what is to be fixed'. $what( $gone ), $gone === [] );
check( 'the scripts name keys at all, so the reading finds what it is for'. $what( $unfound ), $unfound === [] );

$apart = [];

foreach( $texts as $name => $files )
	if( array_diff( $files['de_DE'], $files['en_US'] ) !== [] || array_diff( $files['en_US'], $files['de_DE'] ) !== [] )
		$apart[] = $name. ': '. implode( ', ', array_slice( array_merge( array_diff( $files['de_DE'], $files['en_US'] ), array_diff( $files['en_US'], $files['de_DE'] ) ), 0, 3 ) );

check( 'the two languages of every panel have the same keys'. $what( $apart ), $apart === [] );

echo "\nThe stylesheet of a panel\n";

$declared = [];
preg_match_all( '/(--[a-z0-9-]+)\s*:/', (string) file_get_contents( $root. '/_admin/assets/style.css' ), $tokens );
$declared = array_unique( $tokens[1] );

$sheets = [];
$coloured = [];
$foreign = [];

foreach( (array) glob( $repo. '/features/*/assets/admin.css' ) as $file ) {

	$name = basename( dirname( (string) $file, 2 ) );

	if( isset( PANELS_SURFACES[$name] ) === true )
		continue;

	$sheets[] = $name;
	$css = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( (string) $file ) );

	// A token is no colour in a stylesheet: what a var() names stays out of the reading
	$bare = (string) preg_replace( '/var\([^)]*\)/', '', $css );

	if( preg_match( '/#[0-9a-fA-F]{3,8}\b|\b(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color|color-mix)\s*\(|:\s*[^;{}]*\b(?:red|green|blue|white|black|gray|grey|orange|yellow|purple|pink|brown|silver|gold|navy|teal|cyan|magenta)\b/i', $bare ) === 1 )
		$coloured[] = $name;

	preg_match_all( '/var\(\s*(--[a-z0-9-]+)/', $css, $used );

	foreach( array_unique( $used[1] ) as $token )
		if( in_array( $token, $declared, true ) === false )
			$foreign[] = $name. ' '. $token;
}

check( 'no panel stylesheet names a colour ('. count( $sheets ). ' read)'. $what( $coloured ), $coloured === [] && count( $sheets ) > 2 );
check( 'every var() of a panel stylesheet names a token that the workbench\'s stylesheet declares'. $what( $foreign ), $foreign === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
