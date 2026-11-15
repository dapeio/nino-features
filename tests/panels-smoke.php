<?php
declare(strict_types=1);

/**
 *	Nino features
 *	panels-smoke.php	How a panel script of the catalogue talks to the
 *										workbench: through Nino.adminUi.api.call(), which is in every
 *										Nino from 1.4.0 on, or through Nino.adminUi.panel() of 1.8,
 *										whose call() and whose first request are that same helper -
 *										and through nothing else. A post of a panel's own was the
 *										fallback of a panel loaded by a Nino that has no helper, one
 *										that names 1.3, and its base had to be the project's
 *										directory: a root-absolute '/_admin/' reaches the workbench
 *										of a project that lives at the domain's root and nobody
 *										else's, and Nino.dir, which names the directory in a script,
 *										does not exist before 1.3.2 - the literal '[[/nino/dir]]' the
 *										asset bundle fills in does. The fallback went with the ^1.8
 *										floor of the panel that carried it: since Gallery and Search,
 *										the last two, are on the toolset, no panel of the catalogue
 *										posts by hand, and the set that may is empty. Each feature's
 *										own test runs its panel under node; this is the one place that
 *										holds every panel to the post - and every script of the
 *										catalogue to the workbench's own dialog: a question is put
 *										with Nino.adminUi.confirm() and a message goes to the status
 *										line of the bar, never to the browser's confirm(), alert() or
 *										prompt(), which no theme, no mode of the workbench and no
 *										keyboard rule of its dialogs reaches.
 *
 *	Usage: php tests/panels-smoke.php
 */

$failures = 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

$root		= dirname( __DIR__ );
$panels	= [];

foreach( (array) glob( $root. '/features/*/assets/*.js' ) as $file )
	$panels[ substr( (string) $file, strlen( $root ) + 1 ) ] = (string) file_get_contents( (string) $file );

check( 'the panel scripts were actually read', count( $panels ) > 20 );

echo "\nNo panel posts to a root-absolute /_admin/\n";

$rooted = [];
foreach( $panels as $name => $source )
	if( preg_match( '#sendRequest\(\s*[\'"]/_admin/#', $source ) === 1 )
		$rooted[] = $name;

check( 'every post to the workbench starts at the project\'s directory'. ( $rooted === [] ? '' : ' - '. implode( ' | ', $rooted ) ), $rooted === [] );

echo "\nNo panel posts by hand any more - the set that may is empty\n";

// Empty since Gallery and Search, the last two, went onto the toolset; a
// panel that posts by hand again is listed here with its reason, or fails
$byHand = [];

$workbench = [];
foreach( $panels as $name => $source )
	if( str_ends_with( $name, '/assets/admin.js' ) === true )
		$workbench[ $name ] = $source;

$posting = [];
foreach( $workbench as $name => $source )
	if( str_contains( $source, '[[/nino/dir]]/_admin/' ) === true )
		$posting[ $name ] = $source;

check( 'the panels that post by hand are '. ( $byHand === [] ? 'none' : 'the ones that still name Nino 1.3, '. implode( ' and ', $byHand ) ),
	array_keys( $posting ) === array_map( static fn( string $feature ): string => 'features/'. $feature. '/assets/admin.js', $byHand ) );

foreach( $posting as $name => $source ) {
	$helper	= strpos( $source, 'Nino.adminUi.api.call(' );
	$post		= strpos( $source, 'Nino.http.sendRequest(' );
	check( $name. ' goes through Nino.adminUi.api.call() where there is one, before its own post', $helper !== false && $post !== false && $helper < $post );
}

echo "\nEvery other panel goes through the kernel's helper or its panel() alone\n";

check( 'there are panels besides those', count( $workbench ) > count( $posting ) );

foreach( $workbench as $name => $source )
	if( isset( $posting[ $name ] ) === false )
		check( $name. ' posts through Nino.adminUi.api.call() or Nino.adminUi.panel() and has no post of its own',
			( str_contains( $source, 'Nino.adminUi.api.call(' ) === true || str_contains( $source, 'Nino.adminUi.panel(' ) === true )
			&& str_contains( $source, 'Nino.http.sendRequest(' ) === false );

echo "\nNo script asks the browser: confirm(), alert() and prompt() are the workbench's dialog\n";

/**
 *	A script as its code alone: the comments gone, and the inside of every string, template and
 *	regular expression blanked - except a string that is the name of a dialog, which is how
 *	window['confirm'] is written. A scanner and no regular expression, since a /* in a string
 *	('image/*') or after a // is no comment, and what a string says is no call
 *
 *	@param		string	$source
 *
 *	@return		string
 */
function scriptCode( string $source ): string {

	$out = '';
	$at = 0;
	$length = strlen( $source );
	$last = '';
	$word = '';

	while( $at < $length ) {

		$c = $source[$at];
		$next = $source[$at + 1] ?? '';

		if( $c === '/' && $next === '/' ) {
			$end = strpos( $source, "\n", $at );
			$at = $end === false ? $length : $end;
			continue;
		}

		if( $c === '/' && $next === '*' ) {
			$end = strpos( $source, '*/', $at + 2 );
			$at = $end === false ? $length : $end + 2;
			$out .= ' ';
			continue;
		}

		$regex = $c === '/' && ( $last === '' || str_contains( '(,=:[!&|?{};', $last ) === true || in_array( $word, [ 'return', 'typeof', 'case' ], true ) === true );

		if( $c === "'" || $c === '"' || $c === '`' || $regex === true ) {

			$close = $regex === true ? '/' : $c;
			$start = $at + 1;
			$at++;
			$inClass = false;

			$masked = '';

			while( $at < $length ) {

				$d = $source[$at];

				if( $d === '\\' ) {
					$masked .= '  ';
					$at += 2;
					continue;
				}

				// The code of a placeholder of a template is code
				if( $c === '`' && $d === '$' && ( $source[$at + 1] ?? '' ) === '{' ) {

					$depth = 1;
					$from = $at + 2;
					$to = $from;

					while( $to < $length && $depth > 0 ) {
						$depth += $source[$to] === '{' ? 1 : ( $source[$to] === '}' ? -1 : 0 );
						$to++;
					}

					$masked .= '${'. scriptCode( substr( $source, $from, $to - 1 - $from ) ). '}';
					$at = $to;
					continue;
				}

				if( $regex === true && $d === '[' )
					$inClass = true;
				elseif( $regex === true && $d === ']' )
					$inClass = false;
				elseif( $d === $close && $inClass === false )
					break;
				elseif( $d === "\n" && $c !== '`' )
					break;

				$masked .= ' ';
				$at++;
			}

			$inside = substr( $source, $start, $at - $start );
			$out .= $c. ( in_array( $inside, [ 'confirm', 'alert', 'prompt' ], true ) === true ? $inside : $masked ). ( $regex === true ? '/' : $c );
			$at++;
			$last = $regex === true ? ')' : $c;
			$word = '';
			continue;
		}

		$out .= $c;
		$at++;

		if( ctype_space( $c ) === false ) {
			$last = $c;
			$word = preg_match( '/[\w$]/', $c ) === 1 ? $word. $c : '';
		}
	}

	return $out;
}

/**
 *	The calls of the browser's own dialogs a script makes: a bare confirm(), alert() or
 *	prompt(), or one on the window - by a dot or by a name in brackets - not
 *	Nino.adminUi.confirm(), which is the workbench's, and not a word in a comment or a string
 *
 *	@param		string	$source
 *
 *	@return		array<int, string>		The calls found, as they are written
 */
function browserDialogs( string $source ): array {

	$code = scriptCode( $source );
	$found = [];

	preg_match_all( '#(?<![\w$.])(?:confirm|alert|prompt)\s*\(#', $code, $bare, PREG_OFFSET_CAPTURE );

	$bare = array_filter( $bare[0], static function( array $hit ) use ( $code ): bool {

		// A function or a method of that name is the script's own: it stands after function, or its parameters are followed by a body
		if( preg_match( '#\bfunction\s*\*?\s*$#', substr( $code, 0, $hit[1] ) ) === 1 )
			return false;

		$depth = 1;
		$to = $hit[1] + strlen( $hit[0] );

		while( $to < strlen( $code ) && $depth > 0 ) {
			$depth += $code[$to] === '(' ? 1 : ( $code[$to] === ')' ? -1 : 0 );
			$to++;
		}

		return preg_match( '#\G\s*\{#', $code, $none, 0, $to ) !== 1;
	} );
	$bare = [ array_column( $bare, 0 ) ];

	preg_match_all( '#(?<![\w$.])(?:window|wn|self|globalThis|top|parent)\s*(?:\.\s*(?:confirm|alert|prompt)|\[\s*[\'"`](?:confirm|alert|prompt)[\'"`]\s*\])\s*\(#', $code, $onWindow );

	foreach( array_merge( $bare[0], $onWindow[0] ) as $call )
		$found[] = $call;

	return $found;
}

check( 'the detector finds a bare call, a call on the window and on the script\'s alias of it, however it is spaced',
	browserDialogs( "if( confirm('x') ) {}" ) !== [] && browserDialogs( "alert ( 'x' );" ) !== []
	&& browserDialogs( "wn.confirm( 'x' )" ) !== [] && browserDialogs( "window . prompt('x')" ) !== [] && browserDialogs( "wn['confirm']( 'x' )" ) !== [] );
check( '...in the code of a placeholder of a template, and leaves a function or a method of the script\'s own of that name alone',
	browserDialogs( 'x = `a ${ wn.confirm("x") } b`;' ) !== [] && browserDialogs( 'x = `a ${ y( `b ${ alert(1) }` ) }`;' ) !== []
	&& browserDialogs( "x = { confirm( q ) { return q } };" ) === [] && browserDialogs( "function confirm( q ) { return q }" ) === [] && browserDialogs( "x = { async prompt( q ) { return q } };" ) === []
	&& browserDialogs( "if( x ) { confirm( q ) }" ) !== [] && browserDialogs( "x = confirm( q ) ? { a : 1 } : 2;" ) !== [] );
check( '...also after a /* that is inside a string, and after a // that has a /* in it',
	browserDialogs( "x = 'a/*'; confirm(1); /* c */" ) !== [] && browserDialogs( "x = 'image/*'; wn.confirm(1);" ) !== [] && browserDialogs( "// a /* b\nconfirm(1);\n" ) !== []
	&& browserDialogs( "x = /a'b/; alert(1);" ) !== [] && browserDialogs( "x = `a/*`; alert(1);" ) !== [] );
check( '...and leaves the workbench\'s dialog, a member of another name and a comment alone',
	browserDialogs( "Nino.adminUi.confirm( 'big', {} );" ) === [] && browserDialogs( "const confirm = form.confirm === true;" ) === []
	&& browserDialogs( "/* a confirm() of the browser */\n// alert('x')\nconst a = 1; // prompt('x')\n" ) === [] && browserDialogs( "reconfirm( 1 ); malert( 2 );" ) === []
	&& browserDialogs( "x = 1;// alert('x')\n" ) === [] && browserDialogs( "x = 'javascript:alert(1)'; y = \"confirm(1)\";" ) === [] );

$asking = [];
foreach( $panels as $name => $source )
	if( browserDialogs( $source ) !== [] )
		$asking[] = $name;

check( 'no script of a feature calls the browser\'s confirm(), alert() or prompt()'. ( $asking === [] ? '' : ' - '. implode( ' | ', $asking ) ), $asking === [] );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
