<?php
declare(strict_types=1);

/**
 *	Nino features
 *	markup-smoke.php	The one rule a catalogue of features has to keep for the
 *										framework to keep its own: markup belongs in a template,
 *										not in php. A feature's php decides what is shown; a .tpl
 *										under features/<Name>/templates/ decides what it looks
 *										like, is read through \Nino\Filesystem and filled with
 *										str_replace(). See AGENTS.md, "Markup belongs in a
 *										template".
 *
 *										This reads every class in the catalogue and fails on a
 *										string that opens an html tag, with two things left out:
 *										an icon, which is geometry rather than a view and is what
 *										the workbench's own panel contract asks a panel for as a
 *										string, and the handful of files in ALLOWED below. Each
 *										of those names its reason here as well as in the file, so
 *										the list of exceptions is one list rather than a habit -
 *										and a file that stops needing its exception fails this
 *										too, so the list cannot quietly outlive what it was for.
 *
 *										Not a lint pass over what the markup says: this is about
 *										where it lives. What a template says is the business of
 *										whoever writes the template.
 *
 *	Usage: php tests/markup-smoke.php
 */

$here = dirname( __DIR__ );

/*	Where markup in php is allowed, and why. A file is listed here only when
	moving it into a template would make the code worse rather than better, and
	the file itself carries the same reason where the markup is	*/
const ALLOWED = [
	'features/Templates/AreaComposer/AreaComposer.php'
		=> 'the Template Builder composes template source - markup is its product, not its view',
	'features/Templates/Composer/Composer.php'
		=> 'the same builder, rendering a section preview and its placeholder image',
	'features/Seo/Seo.php'
		=> 'sitemap.xml is a document format rather than a view of one, like the robots.txt and llms.txt builders beside it',
	'features/Modeswitch/Modeswitch.php'
		=> 'three icons as path geometry, declared once as a named property - the switch around them is a template',
	'features/Design/Admin/Admin.php'
		=> 'the <style> and <main> the preview document is handed, as one-line fragments in a named property (the shape \Nino\Modules\Navigation::$html uses)',
	'features/Posts/Shortcodes/Shortcodes.php'
		=> 'a newline in a stored body becomes the <br> it already means - a transformation of the text, not a view of it',
	'features/Templates/SectionDocument/SectionDocument.php'
		=> 'a message that names a tag in its own words ("self-closing <section> is not valid HTML")',
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
 *	Every string in one php file that opens an html tag, minus the ones an
 *	icon is made of.
 *
 *	Read as tokens rather than with a regular expression over the source: a
 *	docblock explaining markup, and a comment naming a tag the way this very
 *	file does, are not markup - and telling them apart is exactly what a
 *	tokeniser is for.
 *
 *	@param		string		$file
 *
 *	@return		array									One entry per string, trimmed to a line
 */
function markupStrings( string $file ): array {

	$found = [];

	foreach( token_get_all( (string) file_get_contents( $file ) ) as $token ) {

		if( is_array( $token ) === false )
			continue;

		if( in_array( $token[0], [ T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML ], true ) === false )
			continue;

		if( preg_match( '/<(?!\/)[a-zA-Z][a-zA-Z0-9]*[\s>\/]/', $token[1] ) !== 1 )
			continue;

		// An icon, which every panel answers the workbench with as a string
		// (see the panel contract in docs/features.md) and which is geometry
		// rather than a view
		if( preg_match( '/^\W*<svg/', trim( $token[1] ) ) === 1 )
			continue;

		$found[] = trim( (string) preg_replace( '/\s+/', ' ', substr( $token[1], 0, 70 ) ) );
	}

	return $found;
}

echo "Markup belongs in a template\n";

$files = [];

foreach( glob( $here. '/features/*', GLOB_ONLYDIR ) ?: [] as $feature )
	foreach( [ '/*.php', '/*/*.php' ] as $pattern )
		foreach( glob( $feature. $pattern ) ?: [] as $file ) {

			$relative = substr( $file, strlen( $here ) + 1 );

			// A feature's own tests, its install unit's data, its text files and
			// its manifest are not the feature's classes
			if( preg_match( '#/(tests|install|text)/|/feature\.php$#', '/'. $relative ) === 1 )
				continue;

			$files[$relative] = markupStrings( $file );
		}

check( 'there are classes to read at all', count( $files ) > 20 );

$offenders = [];

foreach( $files as $relative => $strings )
	if( $strings !== [] && isset( ALLOWED[$relative] ) === false )
		$offenders[] = $relative. ': '. $strings[0];

check( 'no feature builds markup in php'. ( $offenders === [] ? '' : ' - '. implode( ' | ', $offenders ) ), $offenders === [] );

/*	The other direction, and the reason this list stays short: an exception
	that is no longer needed is an exception somebody will copy	*/
$stale = [];

foreach( array_keys( ALLOWED ) as $relative )
	if( isset( $files[$relative] ) === false || $files[$relative] === [] )
		$stale[] = $relative;

check( 'every exception is still one'. ( $stale === [] ? '' : ' - nothing left to allow in: '. implode( ', ', $stale ) ), $stale === [] );

/*	And the half the rule is for: the markup that left those classes has to be
	somewhere. A feature that fills templates carries them, and every token it
	fills is one a file really has	*/
$templates	= 0;
$empty			= [];

foreach( glob( $here. '/features/*/templates/*.tpl' ) ?: [] as $file ) {

	$templates++;

	if( trim( (string) file_get_contents( $file ) ) === '' )
		$empty[] = substr( $file, strlen( $here ) + 1 );
}

check( 'the catalogue ships templates for it', $templates > 20 );
check( '...and none of them is empty'. ( $empty === [] ? '' : ': '. implode( ', ', $empty ) ), $empty === [] );

/*	A template is output: what is in it is sent to whoever asked for the page,
	so an explanation of what the file is for belongs in the class that fills
	it rather than in the file. The exception is a panel's own template -
	panel.tpl, panel-*.tpl - which is only ever rendered into the workbench, by
	somebody who is logged in and is quite likely reading the source anyway	*/
$commented = [];

foreach( glob( $here. '/features/*/templates/*.tpl' ) ?: [] as $file )
	if( str_starts_with( basename( $file ), 'panel' ) === false
		&& str_contains( (string) file_get_contents( $file ), '<!--' ) === true )
		$commented[] = substr( $file, strlen( $here ) + 1 );

check( 'no template a visitor is sent explains itself to them'. ( $commented === [] ? '' : ': '. implode( ', ', $commented ) ), $commented === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";

exit( $failures === 0 ? 0 : 1 );
