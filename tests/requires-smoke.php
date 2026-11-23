<?php
declare(strict_types=1);

/**
 *	Nino features
 *	requires-smoke.php	Which features of this catalogue require Basis, held to what their files use of it: a feature that uses what Basis brings requires it, and a
 *											feature that requires it uses something. What Basis brings is read from the folder of Basis, so it is the same in either case the kernel can
 *											be in and no list of names is kept by hand: its components (the names of the folder), the classes its parts and templates write, with the
 *											modifiers of a component (<name>--<word>), and the tokens its parts declare - those of the page and of the brand are the kernel's and
 *											stand outside. A use is a call of a component in a template (read by the parser of the kernel), a call in a string of a class or a script
 *											([name ...] or a quoted name with a hyphen), a class of Basis - one with a hyphen, a modifier <name>--<word>, or the bare name of a
 *											component such as button - in the class list of a template (with placeholders in it, in either kind of quotes), as a selector in a
 *											stylesheet, and in code where it is a class (an .html file of a feature is read like a template): in class="...", 'class' => '...', className and classList, or a selector in a string
 *											('.header-nav-toggle'), and a token of Basis in a stylesheet or a script. Comments are read as no code: a call named in a comment is
 *											none. What a feature calls of a feature that stands elsewhere is none of this test's.
 *
 *											Two alarms are false and stay, since a name written in a string is what a call looks like: a quoted name with a hyphen that is a
 *											word of the script and no component ('footer-nav' as a key of a map), and a Basis class in a string that no class context holds - the
 *											test cannot tell it from a call. A feature that says so requires Basis or is changed.
 *
 *	Usage: php tests/requires-smoke.php
 *	       NINO_ROOT=/path/to/nino php tests/requires-smoke.php
 */

$repo = dirname( __DIR__ );
$root = getenv( 'NINO_ROOT' ) ?: dirname( $repo ). '/nino';
$root = realpath( $root ) ?: $root;

if( is_file( $root. '/tests/harness.php' ) === false ) {
	fwrite( STDERR, 'No Nino checkout with tests/harness.php at '. $root. ' - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT'. "\n" );
	exit( 2 );
}

defined( 'NINO_FEATURES_DIR' ) === true || define( 'NINO_FEATURES_DIR', $repo. '/features' );
require $root. '/tests/harness.php';

$appData = ninoSandbox( 'requires' );
$appData[\Nino\Html::TREES_SWITCH] = false;

// Every feature of this repository by the key of its manifest
$features = [];

foreach( (array) scandir( $repo. '/features' ) as $directory ) {

	if( $directory[0] === '.' || is_file( $repo. '/features/'. $directory. '/feature.php' ) === false )
		continue;

	$manifest = (array) ( static fn( string $path ): mixed => include $path )( $repo. '/features/'. $directory. '/feature.php' );
	$features[(string) $manifest['key']] = [ 'directory' => $directory, 'requires' => (array) ( $manifest['requires'] ?? [] ) ];
}

check( 'the features were read, Basis among them, and Basis requires nothing', count( $features ) > 20 && isset( $features['basis'] ) === true && $features['basis']['requires'] === [] );

// The register of the components of all of them, as the parser needs the containers
$appData['/nino/modules'] = array_map( static fn( string $directory ): string => '\\Nino\\Modules\\'. $directory, array_column( $features, 'directory' ) );
\Nino\Features::registerComponents( $appData );
$ignored = ninoWarnings();
$containers = \Nino\Components::containers( $appData );

// What Basis brings, from its folder
$basis = $repo. '/features/Basis';
$names = \Nino\Features::componentNames( $basis );
$classes = [];
$tokens = [];

foreach( array_merge( glob( $basis. '/groups/*/part/part.css' ) ?: [], glob( $basis. '/components/*/template.tpl' ) ?: [] ) as $file ) {

	$text = (string) file_get_contents( $file );

	if( str_ends_with( $file, '.css' ) === true ) {
		preg_match_all( '/\.([a-z][a-z0-9]*(?:-[a-z0-9]+)+)(?![a-z0-9-])/', (string) preg_replace( '~/\*.*?\*/~s', '', $text ), $found );
		preg_match_all( '/(?<![\w-])(--[a-z][a-z0-9-]*)\s*:/', $text, $declared );
		$tokens = array_merge( $tokens, $declared[1] );
	}
	else
		preg_match_all( '/class="([^"\[]*)/', $text, $found ) === 0 ?: $found[1] = array_merge( ...array_map( static fn( string $list ): array => explode( ' ', trim( $list ) ), $found[1] ) );

	$classes = array_merge( $classes, array_filter( $found[1], static fn( string $class ): bool => str_contains( $class, '-' ) === true ) );
}

foreach( glob( $basis. '/groups/*/group.php' ) ?: [] as $file )
	foreach( (array) ( include $file )['knobs'] as $knob )
		$tokens[] = (string) $knob['token'];

// A component without a hyphen is a class of the same name where a page is drawn - button, image, spacer, text, html: a stylesheet that styles .button styles Basis's
$bare = array_values( array_filter( $names, static fn( string $name ): bool => str_contains( $name, '-' ) === false ) );
$classes = array_values( array_unique( array_merge( $classes, array_filter( $names, static fn( string $name ): bool => str_contains( $name, '-' ) === true ) ) ) );
$tokens = array_values( array_unique( array_filter( $tokens, static fn( string $token ): bool => preg_match( '/^--(?:page|brand)-/', $token ) !== 1 ) ) );

check( 'what Basis brings is read from its folder: twenty components, classes with a hyphen and tokens of its parts', count( $names ) === 20 && count( $classes ) > 20 && count( $tokens ) > 20 && count( $containers ) > 5 );

/**
 *	The names a source calls, at any depth
 *
 *	@param		array			$nodes				\Nino\Markup::parse()
 *
 *	@return 	array
 */
function requiresCalls( array $nodes ): array {

	$called = [];

	foreach( $nodes as $node ) {

		if( isset( $node['n'] ) === true )
			$called[$node['n']] = true;

		if( is_array( $node['c'] ?? null ) === true )
			$called += requiresCalls( $node['c'] );
	}

	return $called;
}

/**
 *	Whether a list of classes holds one of Basis: a class with a hyphen it writes, a modifier of a component (<name>--<word>) or the bare name of a component. A placeholder
 *	of the template ([[extra]]) in the list is no class
 *
 *	@param		string		$list					The classes, as they stand in an attribute
 *
 *	@return 	bool
 */
function requiresClassList( string $list ): bool {

	global $classes, $names, $bare;

	foreach( preg_split( '/[\s\\\\]+/', trim( (string) preg_replace( '/\[\[[^\]]*\]\]/', ' ', $list ) ) ) ?: [] as $token )
		if( $token !== '' && ( in_array( $token, $classes, true ) === true || in_array( $token, $bare, true ) === true || preg_match( '/^(?:'. implode( '|', array_map( 'preg_quote', $names ) ). ')--[a-z0-9]/', $token ) === 1 ) )
			return true;

	return false;
}

/**
 *	A source without its comments: the blocks, and a line that starts with // or #
 */
function requiresUncommented( string $text ): string {

	return (string) preg_replace( [ '~/\*.*?\*/~s', '~^[ \t]*(?://|#)[^\n]*~m' ], '', $text );
}

$callPattern = '/(?<![\[\\\\])\[\/?(?:'. implode( '|', array_map( 'preg_quote', $names ) ). ')(?=[\s\]\/=])/';
$quotedPattern = '/[\'"](?:'. implode( '|', array_map( 'preg_quote', array_filter( $names, static fn( string $name ): bool => str_contains( $name, '-' ) === true ) ) ). ')[\'"]/';
// A selector in a string of code, '.header-nav-toggle': the whole string is a selector and holds a class of Basis with a hyphen. A bare name is no selector there ('.html' is an ending)
$literalPattern = '/\'((?:[^\'\\\\\n]|\\\\.)*)\'|"((?:[^"\\\\\n]|\\\\.)*)"|`([^`\n]*)`/';
$selectorClasses = '/\.((?:'. implode( '|', array_map( 'preg_quote', $classes ) ). ')(?![\w-])|(?:'. implode( '|', array_map( 'preg_quote', $names ) ). ')--[a-z0-9])/';
// A class context in code: class="...", class='...', 'class' => '...', className, classList
$contextPattern = '/(?:\bclass\s*=\s*\\\\?["\']|[\'"]class[\'"]\s*=>\s*[\'"]|\bclassName\s*[+]?=\s*[\'"]|\bclassList\.(?:add|toggle|contains|remove|replace)\(\s*[\'"])([^\'"\n]*)/';
$tokenPattern = '/'. implode( '|', array_map( static fn( string $token ): string => preg_quote( $token ). '(?![\w-])', $tokens ) ). '/';

$uses = [];

foreach( $features as $key => $feature ) {

	if( $key === 'basis' )
		continue;

	$uses[$key] = [];
	$base = $repo. '/features/'. $feature['directory'];

	foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {

		$relative = substr( $file->getPathname(), strlen( $base ) + 1 );
		$extension = $file->getExtension();

		if( str_starts_with( $relative, 'tests/' ) === true || in_array( $extension, [ 'tpl', 'html', 'css', 'js', 'php' ], true ) === false )
			continue;

		$text = (string) file_get_contents( $file->getPathname() );

		if( $extension === 'tpl' || $extension === 'html' ) {

			foreach( array_keys( requiresCalls( \Nino\Markup::parse( $text, $containers ) ) ) as $name )
				if( in_array( $name, $names, true ) === true )
					$uses[$key]['a call in a template'][$relative] = $name;

			// A class an element of the template carries: a list with a placeholder in it as well, in either kind of quotes
			preg_match_all( '/\bclass\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $text, $found );

			foreach( array_merge( $found[1], $found[2] ) as $list )
				if( requiresClassList( $list ) === true )
					$uses[$key]['a class in a template'][$relative] = true;
		}
		elseif( $extension === 'css' ) {

			// A class as a selector; what a comment says is none
			preg_match_all( '/\.([a-z][a-z0-9_-]*)/i', (string) preg_replace( '~/\*.*?\*/~s', '', $text ), $found );

			if( requiresClassList( implode( ' ', $found[1] ) ) === true )
				$uses[$key]['a class in a stylesheet'][$relative] = true;
		}
		else {

			$code = requiresUncommented( $text );

			if( preg_match( $callPattern, $code ) === 1 || preg_match( $quotedPattern, $code ) === 1 )
				$uses[$key]['a call or a name in code'][$relative] = true;

			preg_match_all( $literalPattern, $code, $literals );

			foreach( array_merge( $literals[1], $literals[2], $literals[3] ) as $literal )
				if( preg_match( '/^[\w\s>+~,.#*:()\[\]="-]+$/', $literal ) === 1 && preg_match( $selectorClasses, $literal ) === 1 )
					$uses[$key]['a class as a selector in code'][$relative] = true;

			preg_match_all( $contextPattern, $code, $found );

			foreach( $found[1] as $list )
				if( requiresClassList( $list ) === true )
					$uses[$key]['a class in a class context of code'][$relative] = true;
		}

		if( $tokens !== [] && preg_match( $tokenPattern, $text ) === 1 )
			$uses[$key]['a token of a part'][$relative] = true;
	}
}

$needs = array_keys( array_filter( $uses, static fn( array $found ): bool => $found !== [] ) );
$requires = array_keys( array_filter( $features, static fn( array $feature ): bool => in_array( 'basis', $feature['requires'], true ) === true ) );

$lacking = array_values( array_diff( $needs, $requires ) );
$needless = array_values( array_diff( $requires, $needs ) );
$say = static fn( array $keys ): string => $keys === [] ? '' : ' - NOT: '. implode( ', ', array_map( static fn( string $key ): string => $key. ' ('. implode( ', ', array_keys( $uses[$key] ?? [] ) ). ')', $keys ) );

check( 'a feature that uses what Basis brings - a component, a class, a token, in a template, a stylesheet or code - requires Basis'. $say( $lacking ), $lacking === [] && count( $needs ) >= 24 );
check( 'a feature that requires Basis uses something of it'. $say( $needless ), $needless === [] );

ninoDone( $appData );
