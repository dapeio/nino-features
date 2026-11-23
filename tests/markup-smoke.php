<?php
declare(strict_types=1);

/**
 *	Nino features
 *	markup-smoke.php	Markup belongs in a template (AGENTS.md, section 4a): no class of a feature opens an html tag in a string, but in the three forms
 *										the rule allows - a one-line fragment with [[tokens]] declared once as a named property or constant of the class, an icon
 *										(a string that begins with <svg), a last-resort fallback - and in the files named below. No file of a feature names the
 *										world of Nino 1: Modules\Components, the classes of its Nino.css, its --space-, --color-, --radius- and --fontsize- tokens,
 *										Nino.ui., the manifest keys components and stacks, a design/ folder.
 *
 *										Reads features/ only.
 *
 *	Usage: php tests/markup-smoke.php
 */

$here = dirname( __DIR__ );

/*	Where markup in php is allowed outside the three forms, and why: file => [ how many strings, reason ]. The count is exact, so that a second
	string in a file that has an exception needs a decision of its own, and a file that stops needing its exception fails the test	*/
const MARKUP_ALLOWED = [
	'features/Seo/Seo.php' => [ 2, 'sitemap.xml is a document format rather than a view of one, like the robots.txt and llms.txt builders beside it' ],
];

/*	Known, and to be fixed by the feature: markup that a method builds, which belongs in a template. Same shape as the list above; an entry that
	the file stops offending against fails the test, so the list is one of what is still to be fixed	*/
const MARKUP_KNOWN = [];

// The elements of html a string may open; what stands between the brackets otherwise (<key>, <name>) is a placeholder in a sentence
const MARKUP_ELEMENTS = [
	'a', 'article', 'aside', 'b', 'body', 'br', 'button', 'caption', 'code', 'dd', 'details', 'div', 'dl', 'dt', 'em', 'fieldset', 'figcaption', 'figure', 'footer', 'form',
	'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'i', 'iframe', 'img', 'input', 'label', 'legend', 'li', 'link', 'main', 'meta', 'nav', 'ol', 'optgroup',
	'option', 'p', 'path', 'pre', 'script', 'section', 'select', 'small', 'source', 'span', 'strong', 'style', 'summary', 'svg', 'table', 'tbody', 'td', 'template', 'textarea',
	'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'ul', 'url', 'urlset', 'video',
];

$failures	= 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

/**
 *	Every string of one php file that opens an html tag and is not one of the forms of the rule. Read as tokens rather than with a regular
 *	expression over the source: a docblock explaining markup is no markup, and a string in a method differs from a constant of the class
 *
 *	@param		string		$file
 *
 *	@return		array<int, string>		One entry per string, trimmed to a line, with its line number
 */
function markupStrings( string $file ): array {

	$found = [];
	$stack = [];
	$function = false;
	$class = false;
	$before = null;

	foreach( token_get_all( (string) file_get_contents( $file ) ) as $token ) {

		if( is_array( $token ) === true && in_array( $token[0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) === true )
			continue;

		$id = is_array( $token ) === true ? $token[0] : $token;

		if( $id === T_FUNCTION )
			$function = true;
		elseif( in_array( $id, [ T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM ], true ) === true && in_array( $before, [ T_DOUBLE_COLON, T_NEW ], true ) === false )
			$class = true;
		elseif( $id === ';' )
			$function = false;
		elseif( $id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES ) {
			$stack[] = $id === '{' && $function === true ? 'function' : ( $id === '{' && $class === true ? 'class' : 'other' );
			$function = $id === '{' ? false : $function;
			$class = $id === '{' ? false : $class;
		}
		elseif( $id === '}' )
			array_pop( $stack );

		$before = $id;

		if( is_array( $token ) === false || in_array( $token[0], [ T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML ], true ) === false )
			continue;

		if( preg_match( '/<(?!\/)(?:'. implode( '|', MARKUP_ELEMENTS ). ')(?:[\s>\/]|$)/i', $token[1] ) !== 1 )
			continue;

		// An icon, which is geometry rather than a view
		if( preg_match( '/^\W*<svg/', trim( $token[1] ) ) === 1 )
			continue;

		// A one-line fragment with tokens that the class declares once, as a constant or a property: not built inside a method, not outside a class
		$named = in_array( 'function', $stack, true ) === false && in_array( 'class', $stack, true ) === true && str_contains( $token[1], "\n" ) === false
			&& preg_match( '/\[\[[A-Za-z]+\]\]/', $token[1] ) === 1;

		if( $named === false )
			$found[] = $token[2]. ': '. trim( (string) preg_replace( '/\s+/', ' ', substr( $token[1], 0, 70 ) ) );
	}

	return $found;
}

echo "Markup belongs in a template\n";

$files = [];

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $here. '/features', FilesystemIterator::SKIP_DOTS ) ) as $file ) {

	$relative = substr( $file->getPathname(), strlen( $here ) + 1 );

	// A feature's own tests, its install unit's data, its text files and its manifest are not the feature's classes
	if( $file->getExtension() !== 'php' || preg_match( '#/(tests|install|text)/|/feature\.php$#', '/'. $relative ) === 1 )
		continue;

	$files[$relative] = markupStrings( $file->getPathname() );
}

ksort( $files );

check( 'there are classes to read at all', count( $files ) > 40 );

$offenders = [];

foreach( $files as $relative => $strings )
	if( $strings !== [] && isset( MARKUP_ALLOWED[$relative] ) === false && isset( MARKUP_KNOWN[$relative] ) === false )
		$offenders[] = $relative. ' '. $strings[0];

check( 'no feature builds markup in php but in the forms of the rule and the files named here'. ( $offenders === [] ? '' : ' - '. implode( ' | ', array_slice( $offenders, 0, 6 ) ) ), $offenders === [] );

/*	The other direction, and the reason these lists stay short: an exception that is no longer needed is an exception somebody will copy	*/
$stale = [];

foreach( array_merge( MARKUP_ALLOWED, MARKUP_KNOWN ) as $relative => $exception )
	if( count( $files[$relative] ?? [] ) !== $exception[0] )
		$stale[] = $relative. ' has '. count( $files[$relative] ?? [] ). ' strings, the list says '. $exception[0];

check( 'every exception is still one, for as many strings as it names'. ( $stale === [] ? '' : ' - '. implode( ' | ', $stale ) ), $stale === [] );

echo "\nThe markup is in templates\n";

$templates = array_merge( (array) glob( $here. "/features/*/templates/*.tpl" ), (array) glob( $here. "/features/*/components/*/template.tpl" ) );
$empty = array_filter( $templates, static fn( string $file ): bool => trim( (string) file_get_contents( $file ) ) === '' );
$commented = array_filter( $templates, static fn( string $file ): bool => str_starts_with( basename( $file ), 'panel' ) === false && str_contains( (string) file_get_contents( $file ), '<!--' ) === true );

check( 'the features ship templates, and none of them is empty', count( $templates ) > 20 && $empty === [] );
check( 'no template a visitor is sent explains itself to them'. ( $commented === [] ? '' : ' - '. implode( ', ', array_map( 'basename', $commented ) ) ), $commented === [] );

echo "\nNo feature names the world of Nino 1\n";

$old = [
	'Modules\Components'		=> '/Modules\\\\{1,2}Components\b/',
	'a class of the old Nino.css'	=> '/(?<![\w\-])nino-(?!form(?:-(?:message|note|submit|trap))?(?![\w-])|richtext(?![\w-])|sr-only(?![\w-]))(?:accordion|alert|back|badge|bento|bg|breadcrumbs|btn|card|code|contact|cover|feature|filter|footer|form|forms|fx|gallery|grid|headernav|hero|hidden|hide|hover|icon|image|img|ink|invisible|is|legal|list|localepicker|logo|m[btlrx]?|modal|nav|newsletter|opacity|p[btlr]?|pagination|parallex|preloader|pricing|profile|radius|scroll|section|slider|spacer|stack|stat|stretch|table|tabs|testimonial|text|tiles|timeline|toast|video|visible|vpa|whitespace|wrap)(?![a-z0-9])/',
	'a token of the old design'	=> '/(?<![\w\-])--(?:space|color|radius|fontsize)-/',
	'Nino.ui.'							=> '/\bNino\.ui\./',
];
$named = [];
$read = 0;

foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $here. '/features', FilesystemIterator::SKIP_DOTS ) ) as $file ) {

	$relative = substr( $file->getPathname(), strlen( $here ) + 1 );

	if( str_contains( '/'. $relative, '/tests/' ) === true )
		continue;

	if( in_array( $file->getExtension(), [ 'php', 'js', 'css', 'tpl', 'json', 'html', 'svg', 'md' ], true ) === false )
		continue;

	$read++;

	foreach( explode( "\n", (string) file_get_contents( $file->getPathname() ) ) as $number => $line )
		foreach( $old as $what => $pattern )
			if( preg_match( $pattern, $line ) === 1 )
				$named[] = $relative. ':'. ( $number + 1 ). ' '. $what;

	if( $file->getFilename() === 'feature.php' && array_intersect( [ 'components', 'stacks' ], array_keys( (array) include $file->getPathname() ) ) !== [] )
		$named[] = $relative. ' has the manifest key components or stacks';
}

$designs = array_filter( array_merge( (array) glob( $here. "/features/*/design" ), (array) glob( $here. "/features/*/*/design" ) ), 'is_dir' );

check( 'no file of a feature but its tests names Modules\Components, a class or token of the old design, Nino.ui. or a manifest key of Nino 1 ('. $read. ' files read)'
	. ( $named === [] ? '' : ' - '. implode( ' | ', array_slice( $named, 0, 6 ) ) ), $named === [] && $read > 300 );
check( 'no feature has a design/ folder'. ( $designs === [] ? '' : ' - '. implode( ', ', $designs ) ), $designs === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
