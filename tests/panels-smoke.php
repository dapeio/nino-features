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
 *										Every rule holds for every panel script but two exceptions, which a feature declares in the "surface" or "reading" of its own
 *										tests/catalogue.php (tests/declarations.php) and which only count where PANELS_RELEASED below names the feature: a surface is let
 *										off the rules of the look of a panel (a button or a dialog of its own, one primary button on a bar, colours and tokens of the
 *										stylesheet), a panel that only reads off the success it says. The browser's dialogs, noValidate and the words in both
 *										languages hold for every script. The rules are one function over a repository, run over this one and over a repository of
 *										probes - each with one fault, which each rule has to find.
 *
 *										Reads features/ only, and the stylesheet and texts of the kernel NINO_ROOT names (default: ../nino).
 *
 *	Usage: php tests/panels-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/panels-smoke.php
 */

require __DIR__. '/declarations.php';

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
		if( ( $literal[3] ?? '' ) !== '' )
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
/*	The features that may say their panel is an exception, by directory. A feature that says it and is not named here fails the test: an exception is
	released here, where it can be seen, and not by the feature that wants it. 'surface' is a work surface (docs/admin-ui.md, "Work surface"): a draft with a
	preview that is saved as a whole; 'reading' is a panel that saves nothing, so that there is no success to say	*/
const PANELS_RELEASED = [
	'surface' => [ 'Builder', 'Themes' ],
	'reading' => [ 'Greeting', 'Stats' ],
];

/**
 *	The scripts and stylesheets a feature loads into the workbench: assets/admin.js and assets/admin.css, and every file below its own assets/ that a
 *	class of the feature hands to \Nino\Admin\Panels::relative() - the way a panel or a tab of the workbench names what it loads - as '/assets/<file>'.
 *	Read from the code, the comments left out. A path of the kernel (/_admin/assets/...) names no file of the feature and is none of them
 *
 *	@param		string		$dir					The folder of the feature
 *
 *	@return		array<string, array<int, string>>		'js' and 'css': the paths below the folder, assets/admin.js first
 */
function panelsLoaded( string $dir ): array {

	$found = [ 'js' => [], 'css' => [] ];

	foreach( [ 'js', 'css' ] as $type )
		if( is_file( $dir. '/assets/admin.'. $type ) === true )
			$found[$type][] = 'assets/admin.'. $type;

	foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$relative = substr( $file->getPathname(), strlen( $dir ) + 1 );

		if( $file->getExtension() !== 'php' || preg_match( '#^(?:tests|install|text|assets)/#', $relative ) === 1 )
			continue;

		$window = [];

		foreach( token_get_all( (string) file_get_contents( $file->getPathname() ) ) as $token ) {

			if( is_array( $token ) === true && in_array( $token[0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) === true )
				continue;

			if( is_array( $token ) === true && $token[0] === T_CONSTANT_ENCAPSED_STRING && in_array( 'relative', $window, true ) === true
				&& preg_match( '#^/assets/([A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*\.(js|css))$#D', substr( $token[1], 1, -1 ), $named ) === 1 && is_file( $dir. '/assets/'. $named[1] ) === true )
				$found[$named[2]][] = 'assets/'. $named[1];

			$window[] = is_array( $token ) === true ? ( $token[0] === T_STRING ? $token[1] : '' ) : $token;
			$window = array_slice( $window, -12 );
		}
	}

	return [ 'js' => array_values( array_unique( $found['js'] ) ), 'css' => array_values( array_unique( $found['css'] ) ) ];
}

/**
 *	Every rule of this suite over the panel scripts below a repository
 *
 *	@param		string		$repo
 *	@param		string		$root					The Nino checkout: its workbench stylesheet and texts
 *	@param		array			$released			Like PANELS_RELEASED
 *
 *	@return		array<string, mixed>		rule => what offends it
 */
function panelsProblems( string $repo, string $root, array $released ): array {

	$locales = [ 'de_DE', 'en_US' ];
	$declarations = declarationsRead( $repo );
	$said = [ 'surface' => declarationsOf( $declarations, 'surface' ), 'reading' => declarationsOf( $declarations, 'reading' ) ];
	$found = array_fill_keys( [ 'unreleased', 'unscripted', 'own', 'asking', 'forms', 'saying', 'bars', 'unsaid', 'unfound', 'apart', 'coloured', 'foreign' ], [] );
	$found['form'] = declarationsProblems( $declarations, [ 'surface', 'reading' ] );
	$found['counted'] = 0;
	$found['sheets'] = 0;
	$found['panels'] = 0;
	$exempt = [ 'surface' => [], 'reading' => [] ];

	foreach( $said as $kind => $declared ) {

		foreach( $declared as $name => $reason ) {

			if( in_array( $name, $released[$kind], true ) === false )
				$found['unreleased'][] = $declarations[$name]['file']. ' says "'. $kind. '", and an exception is released in tests/panels-smoke.php, where '. $name. ' is not named';
			else
				$exempt[$kind][$name] = true;

			if( is_file( $repo. '/features/'. $name. '/assets/admin.js' ) === false )
				$found['unscripted'][] = $declarations[$name]['file']. ' says "'. $kind. '", and '. $name. ' has no assets/admin.js';
		}

		foreach( $released[$kind] as $name )
			if( isset( $declared[$name] ) === false )
				$found['unreleased'][] = 'tests/panels-smoke.php releases "'. $kind. '" for '. $name. ', and features/'. $name. '/'. DECLARATIONS_FILE. ' does not say it';
	}

	$panels = [];
	$sheets = [];
	$texts = [];

	foreach( (array) scandir( $repo. '/features' ) as $name ) {

		$dir = $repo. '/features/'. $name;

		if( $name[0] === '.' || is_dir( $dir ) === false )
			continue;

		foreach( $locales as $locale )
			$texts[$name][$locale] = panelsTexts( $dir. '/text/'. $locale. '.php' );

		$loaded = panelsLoaded( $dir );

		foreach( $loaded['js'] as $file )
			$panels[$name][$file] = (string) file_get_contents( $dir. '/'. $file );

		foreach( $loaded['css'] as $file )
			$sheets[$name][$file] = (string) file_get_contents( $dir. '/'. $file );
	}

	$found['panels'] = count( $panels );
	$found['scripts'] = array_sum( array_map( 'count', $panels ) );
	$kernel = [];

	foreach( $locales as $locale )
		$kernel[$locale] = panelsTexts( $root. '/_admin/text/'. $locale. '.php' );

	foreach( $panels as $name => $scripts ) {

		$surface = isset( $exempt['surface'][$name] );
		$says = false;
		$named = false;

		foreach( $scripts as $file => $source ) {

			[ $plain ] = panelsCode( $source );
			$label = $file === 'assets/admin.js' ? $name : $name. '/'. $file;

			// The look of a panel: a surface is let off these
			if( $surface === false && preg_match( '/\b(?:el|createElement)\s*\(\s*[\'"](?:button|dialog)[\'"]|<(?:button|dialog)\b|nino-admin-btn|[\'"]type[\'"]\s*,\s*[\'"]file[\'"]|\btype\s*[:=]\s*[\'"]file[\'"]|\[type=[\'"]?file/', $plain ) === 1 )
				$found['own'][] = $label;

			foreach( $surface === true ? [] : panelsPrimaries( $source ) as $count ) {

				$found['counted']++;

				if( $count > 1 )
					$found['bars'][] = $label. ': '. $count. ' primary buttons on one bar';
			}

			// What holds for every panel script
			if( panelsDialogs( $source ) !== [] )
				$found['asking'][] = $label;

			preg_match_all( '/(\w+)\s*=\s*(?:[\w.]+\.)?el\(\s*[\'"]form[\'"]/', $plain, $made );

			foreach( $made[1] as $variable )
				if( preg_match( '/\b'. preg_quote( $variable, '/' ). '\.noValidate\s*=\s*true\b/', $plain ) !== 1 )
					$found['forms'][] = $label. ' '. $variable;

			// The toast of a success is said by Nino.adminUi.say(), or by the line of the bar of the form, whose saved() says it
			$says = $says === true || preg_match( '/\bNino\.adminUi\.say\(|\bline\.saved\(\)/', $plain ) === 1;

			[ $keys, $prefixes ] = panelsKeys( $source );
			$named = $named === true || $keys !== [] || $prefixes !== [];

			foreach( $locales as $locale ) {

				$own = $texts[$name][$locale];

				foreach( $keys as $key )
					if( in_array( $key, $own, true ) === false && ( str_starts_with( $key, '/_admin/common/' ) === false || in_array( $key, $kernel[$locale], true ) === false ) )
						$found['unsaid'][$name. ' '. $key] = true;

				foreach( $prefixes as $prefix )
					if( array_filter( array_merge( $own, str_starts_with( $prefix, '/_admin/common/' ) === true ? $kernel[$locale] : [] ), static fn( string $key ): bool => str_starts_with( $key, $prefix ) ) === [] )
						$found['unsaid'][$name. ' '. $prefix. '...'] = true;
			}
		}

		// A panel says its success in one of its scripts, or none says it
		if( isset( $exempt['reading'][$name] ) === false && $says === false )
			$found['saying'][] = $name;
		elseif( isset( $exempt['reading'][$name] ) === true && $says === true )
			$found['saying'][] = $name. ' says something, and features/'. $name. '/'. DECLARATIONS_FILE. ' says it only reads';

		if( $named === false )
			$found['unfound'][] = $name;
	}

	foreach( $texts as $name => $files )
		if( array_diff( $files['de_DE'], $files['en_US'] ) !== [] || array_diff( $files['en_US'], $files['de_DE'] ) !== [] )
			$found['apart'][] = $name. ': '. implode( ', ', array_slice( array_merge( array_diff( $files['de_DE'], $files['en_US'] ), array_diff( $files['en_US'], $files['de_DE'] ) ), 0, 3 ) );

	preg_match_all( '/(--[a-z0-9-]+)\s*:/', (string) file_get_contents( $root. '/_admin/assets/style.css' ), $tokens );
	$declaredTokens = array_unique( $tokens[1] );

	foreach( $sheets as $name => $files ) {

		if( isset( $exempt['surface'][$name] ) === true )
			continue;

		foreach( $files as $file => $css ) {

			$found['sheets']++;
			$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
			$label = $file === 'assets/admin.css' ? $name : $name. '/'. $file;

			// A token is no colour in a stylesheet: what a var() names stays out of the reading
			$bare = (string) preg_replace( '/var\([^)]*\)/', '', $css );

			if( preg_match( '/#[0-9a-fA-F]{3,8}\b|\b(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color|color-mix)\s*\(|:\s*[^;{}]*\b(?:red|green|blue|white|black|gray|grey|orange|yellow|purple|pink|brown|silver|gold|navy|teal|cyan|magenta)\b/i', $bare ) === 1 )
				$found['coloured'][] = $label;

			preg_match_all( '/var\(\s*(--[a-z0-9-]+)/', $css, $used );

			foreach( array_unique( $used[1] ) as $token )
				if( in_array( $token, $declaredTokens, true ) === false )
					$found['foreign'][] = $label. ' '. $token;
		}
	}

	return $found;
}

$what = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 6 ) );
$found = panelsProblems( $repo, $root, PANELS_RELEASED );

check( 'the panel scripts were read', $found['panels'] > 8 );
check( 'what a feature says of its panel in tests/catalogue.php is well formed'. $what( $found['form'] ), $found['form'] === [] );
check( 'a feature that says its panel is a surface, or only reads, is released for it here, and has the panel script that this is about; a release is for a feature that says it'. $what( array_merge( $found['unreleased'], $found['unscripted'] ) ),
	$found['unreleased'] === [] && $found['unscripted'] === [] );

echo "\nWhat a panel does not build itself\n";

// A surface is let off this one: where the rule is the look of a panel, the sentence says where an exception is released
check( 'no panel script but a released surface builds a button, a dialog or a file field of its own'. $what( array_map( static fn( string $name ): string => $name. ' (a work surface is released in PANELS_RELEASED of tests/panels-smoke.php)', $found['own'] ) ), $found['own'] === [] );
check( 'no panel script calls the browser\'s confirm(), alert() or prompt()'. $what( $found['asking'] ), $found['asking'] === [] );
check( 'the detector finds a bare call, one on the window and on its alias, in the code of a template placeholder - and not a method, a string or a comment',
	panelsDialogs( "if( confirm('x') ) {}" ) !== [] && panelsDialogs( "wn['confirm']( 'x' )" ) !== [] && panelsDialogs( "window . prompt('x')" ) !== []
	&& panelsDialogs( 'x = `a ${ wn.confirm("x") } b`;' ) !== [] && panelsDialogs( "x = 'image/*'; alert(1);" ) !== []
	&& panelsDialogs( "x = { confirm( q ) { return q } }; Nino.adminUi.confirm( 'big', {} );" ) === [] && panelsDialogs( "// alert('x')\nx = 'confirm(1)'; /* prompt(1) */" ) === [] );

echo "\nHow a panel saves\n";

check( 'every form a panel makes has noValidate'. $what( $found['forms'] ), $found['forms'] === [] );
check( 'every panel that changes something says its success with Nino.adminUi.say() or the saved() of its bar, and no panel released as reading does'. $what( $found['saying'] ), $found['saying'] === [] );
check( 'no bar of a panel has more than one primary button ('. $found['counted']. ' bars read)'. $what( $found['bars'] ), $found['bars'] === [] && $found['counted'] > 10 );

echo "\nThe words of a panel\n";

// Known, and to be fixed by the feature: a key that stands in neither language of the panel and is no key of /common/. Each panel works - the
// kernel's Features module has the word - but a panel owns every word it reads (the kernel has /_admin/common/split/settings as well). An entry
// that the scripts stop offending against fails the test, so the list is one of what is still to be fixed
$known = [
	// The Builder reads the names of the picture shapes, which the words of Themelab hold, with its own prefix; found when the work surfaces came under this rule
	'Builder /_admin/themelab/details/ph-...' => true,
];

$open = array_keys( array_diff_key( $found['unsaid'], $known ) );
$gone = array_keys( array_diff_key( $known, $found['unsaid'] ) );

check( 'every /_admin/ key a panel script reads is in both languages of the panel, or under /common/ in the kernel\'s texts - but the known ones'. $what( $open ), $open === [] );
check( 'every known offender still offends, so that the list is one of what is to be fixed'. $what( $gone ), $gone === [] );
check( 'the scripts name keys at all, so the reading finds what it is for'. $what( $found['unfound'] ), $found['unfound'] === [] );
check( 'the two languages of every panel have the same keys'. $what( $found['apart'] ), $found['apart'] === [] );

echo "\nThe stylesheet of a panel\n";

check( 'no panel stylesheet but a released surface\'s names a colour ('. $found['sheets']. ' read)'. $what( $found['coloured'] ), $found['coloured'] === [] && $found['sheets'] > 2 );
check( 'every var() of a panel stylesheet but a released surface\'s names a token that the workbench\'s stylesheet declares'. $what( $found['foreign'] ), $found['foreign'] === [] );

echo "\nThe rules over a repository of probes\n";

// A panel that keeps every rule, and one with the one fault that breaks each rule. The surface and the reading are released in this table, not in PANELS_RELEASED
$clean = "const Panel = function( id ) {\n\tconst form = Nino.adminUi.el( 'form' );\n\tform.noValidate = true;\n\tNino.adminUi.say( '/_admin/probe/ok' );\n\treturn Nino.adminUi.actionBar( { primary: save, secondary: back } );\n};\n";
$words = static fn( string $id ): array => [ 'text/de_DE.php' => "<?php return [ '[[/_admin/probe/ok]]' => 'x' ];", 'text/en_US.php' => "<?php return [ '[[/_admin/probe/ok]]' => 'x' ];" ];
$sheet = 'a { color: var(--editor-blue); }';
$probes = [
	'Clean'    => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet ] + $words( 'clean' ),
	'Own'      => [ 'assets/admin.js' => $clean. "const b = el( 'button', {} );\n", 'assets/admin.css' => $sheet ] + $words( 'own' ),
	'Asking'   => [ 'assets/admin.js' => $clean. "if( confirm( 'x' ) ) {}\n", 'assets/admin.css' => $sheet ] + $words( 'asking' ),
	'Form'     => [ 'assets/admin.js' => str_replace( "\tform.noValidate = true;\n", '', $clean ), 'assets/admin.css' => $sheet ] + $words( 'form' ),
	'Silent'   => [ 'assets/admin.js' => str_replace( "\tNino.adminUi.say( '/_admin/probe/ok' );\n", "\tvoid '/_admin/probe/ok';\n", $clean ), 'assets/admin.css' => $sheet ] + $words( 'silent' ),
	'Bars'     => [ 'assets/admin.js' => $clean. "Nino.adminUi.actionBar( { primary: a, extra: { kind: 'primary' } } );\n", 'assets/admin.css' => $sheet ] + $words( 'bars' ),
	'Unsaid'   => [ 'assets/admin.js' => $clean. "const w = '/_admin/probe/missing';\n", 'assets/admin.css' => $sheet ] + $words( 'unsaid' ),
	'Apart'    => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet, 'text/de_DE.php' => "<?php return [ '[[/_admin/probe/ok]]' => 'x', '[[/_admin/probe/more]]' => 'y' ];", 'text/en_US.php' => "<?php return [ '[[/_admin/probe/ok]]' => 'x' ];" ],
	'Coloured' => [ 'assets/admin.js' => $clean, 'assets/admin.css' => 'a { color: #ff0000; }' ] + $words( 'coloured' ),
	'Foreign'  => [ 'assets/admin.js' => $clean, 'assets/admin.css' => 'a { color: var(--not-a-token); }' ] + $words( 'foreign' ),
	'Says'     => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet, 'tests/catalogue.php' => "<?php return [ 'reading' => 'a report' ];" ] + $words( 'says' ),
	'Reader'   => [ 'assets/admin.js' => str_replace( "\tNino.adminUi.say( '/_admin/probe/ok' );\n", "\tvoid '/_admin/probe/ok';\n", $clean ), 'assets/admin.css' => $sheet, 'tests/catalogue.php' => "<?php return [ 'reading' => 'a report' ];" ] + $words( 'reader' ),
	'Surface'  => [ 'assets/admin.js' => $clean. "const b = el( 'button', {} );\nNino.adminUi.actionBar( { primary: a, extra: { kind: 'primary' } } );\nif( confirm( 'x' ) ) {}\nconst w = '/_admin/probe/missing';\n", 'assets/admin.css' => 'a { color: #ff0000; border-color: var(--not-a-token); }',
		'tests/catalogue.php' => "<?php return [ 'surface' => 'a work surface' ];" ] + $words( 'surface' ),
	'Claimed'  => [ 'assets/admin.js' => $clean. "const b = el( 'button', {} );\n", 'assets/admin.css' => $sheet, 'tests/catalogue.php' => "<?php return [ 'surface' => 'a work surface' ];" ] + $words( 'claimed' ),
	'Scriptless' => [ 'tests/catalogue.php' => "<?php return [ 'reading' => 'a report' ];" ],
	'Second'   => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet, 'assets/panel.js' => "if( confirm( 'x' ) ) {}\nconst b = el( 'button', {} );\nconst f = el( 'form' );\n", 'assets/panel.css' => 'a { color: #ff0000; }',
		'assets/notloaded.js' => "if( confirm( 'x' ) ) {}\n", 'Admin/Admin.php' => "<?php class Admin { public static function assets(): array { return [ \\Nino\\Admin\\Panels::relative( dirname( __DIR__ ). '/assets/panel.js' ), \\Nino\\Admin\\Panels::relative( dirname( __DIR__ ). '/assets/panel.css' ), '/_admin/assets/ghost.js' ]; } const LOOSE = '/assets/notloaded.js'; }\n// \\Nino\\Admin\\Panels::relative( '/assets/notloaded.js' )\n" ] + $words( 'second' ),
	'cased'    => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet, 'tests/catalogue.php' => "<?php return [ 'surface' => 'a work surface' ];" ] + $words( 'cased' ),
	'Unsaying' => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet ] + $words( 'unsaying' ),
	'Both'     => [ 'assets/admin.js' => $clean, 'assets/admin.css' => $sheet, 'tests/catalogue.php' => "<?php return [ 'surface' => 'a', 'reading' => 'b' ];" ] + $words( 'both' ),
];

$work = sys_get_temp_dir(). '/nino-panels-'. bin2hex( random_bytes( 6 ) );

foreach( $probes as $name => $files )
	declarationsWrite( $work. '/features/'. $name, $files + [ 'feature.php' => '<?php return [ \'key\' => \''. strtolower( $name ). '\' ];' ] );

$probed = panelsProblems( $work, $root, [ 'surface' => [ 'Surface', 'Cased' ], 'reading' => [ 'Says', 'Reader', 'Scriptless', 'Unsaying' ] ] );
$of = static fn( array $lines ): array => array_values( array_unique( array_map( static fn( string $line ): string => rtrim( explode( ' ', $line )[0], ':' ), $lines ) ) );

check( 'a panel that keeps every rule offends none of them',
	array_filter( [ $probed['own'], $probed['asking'], $probed['forms'], $probed['saying'], $probed['bars'], $probed['coloured'], $probed['foreign'], $probed['apart'], $probed['unreleased'], $probed['unscripted'], array_keys( $probed['unsaid'] ) ],
		static fn( array $lines ): bool => in_array( 'Clean', $of( $lines ), true ) === true ) === [] );
check( 'a button of its own, a dialog of the browser, a form without noValidate, a success not said, two primary buttons, a word in one language and a word that is in no language are each found',
	$probed['own'] === [ 'Claimed', 'Own', 'Second/assets/panel.js' ] && $of( $probed['asking'] ) === [ 'Asking', 'Second/assets/panel.js', 'Surface' ] && $of( $probed['forms'] ) === [ 'Form', 'Second/assets/panel.js' ] && in_array( 'Silent', $probed['saying'], true ) === true
	&& $of( $probed['bars'] ) === [ 'Bars' ] && $of( $probed['apart'] ) === [ 'Apart' ] && $of( array_keys( $probed['unsaid'] ) ) === [ 'Surface', 'Unsaid' ] );
check( 'a colour and a token that is none are found in a stylesheet', $probed['coloured'] === [ 'Coloured', 'Second/assets/panel.css' ] && $of( $probed['foreign'] ) === [ 'Foreign' ] );
check( 'a surface that is released is let off the look - a button of its own, two primary buttons, a colour, a token - and off nothing else: its dialog and its word that is in no language are found',
	in_array( 'Surface', $probed['own'], true ) === false && $of( $probed['bars'] ) === [ 'Bars' ] && in_array( 'Surface', $probed['coloured'], true ) === false && $of( $probed['foreign'] ) === [ 'Foreign' ]
	&& in_array( 'Surface', $of( $probed['asking'] ), true ) === true && in_array( 'Surface', $of( array_keys( $probed['unsaid'] ) ), true ) === true );
check( 'a script that a class of the feature hands to Panels::relative() is held to the rules as admin.js is - one in a comment, one of the kernel and one that is none of those are not read',
	in_array( 'Second/assets/panel.js', $probed['own'], true ) === true && in_array( 'Second/assets/panel.js', $of( $probed['asking'] ), true ) === true && in_array( 'Second/assets/panel.css', $probed['coloured'], true ) === true
	&& array_filter( array_merge( $probed['asking'], $probed['own'] ), static fn( string $line ): bool => str_contains( $line, 'notloaded' ) === true || str_contains( $line, 'ghost' ) === true ) === [] );
check( 'a release names the directory of the feature exactly, in upper and lower case: a feature that says a surface in another spelling is not released and the release is not its own',
	count( array_filter( $probed['unreleased'], static fn( string $line ): bool => str_contains( $line, 'features/cased/tests/catalogue.php says "surface"' ) === true ) ) === 1
	&& count( array_filter( $probed['unreleased'], static fn( string $line ): bool => str_contains( $line, 'releases "surface" for Cased' ) === true ) ) === 1 );
check( 'a panel that says it only reads is let off the success it says where it is released, and is found where it says one',
	in_array( 'Reader', $probed['saying'], true ) === false && in_array( 'Says', array_map( static fn( string $line ): string => explode( ' ', $line )[0], $probed['saying'] ), true ) === true && in_array( 'Unsaying', $probed['saying'], true ) === false );
check( 'an exception a feature says and the test does not release is found, and the sentence says where it is released; so is a release for a feature that does not say it, and a feature that says both',
	count( array_filter( $probed['unreleased'], static fn( string $line ): bool => str_contains( $line, 'features/Claimed/tests/catalogue.php' ) === true && str_contains( $line, 'tests/panels-smoke.php' ) === true ) ) === 1
	&& count( array_filter( $probed['unreleased'], static fn( string $line ): bool => str_contains( $line, 'releases "reading" for Unsaying' ) === true ) ) === 1 && $of( $probed['form'] ) === [ 'features/Both/tests/catalogue.php' ] );
check( 'an exception of a feature without a panel script is found', $of( $probed['unscripted'] ) === [ 'features/Scriptless/tests/catalogue.php' ] );

declarationsRemove( $work );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
