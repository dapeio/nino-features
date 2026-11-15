<?php
declare(strict_types=1);

/**
 *	Nino features
 *	language-smoke.php	The catalogue is written in English - code, comments
 *											and docblocks alike - and German belongs in the two
 *											places that are about German: a locale's text file
 *											and a *.de.md manual. What keeps turning up instead
 *											is a comment naming the screen it belongs to in the
 *											language the screenshot was taken in ("Elemente nach
 *											Typ", "Abstände", "Löschen"), which reads fine to
 *											whoever wrote it and not at all to the next person.
 *
 *											Comment lines only, and by two marks together: an
 *											umlaut, and a word list of german function words
 *											that are not also english ones. Neither alone is
 *											enough - "Elemente nach Typ" carries no umlaut, and
 *											an umlaut on its own flags a transliteration table
 *											and a person's name. Words that are german and
 *											english both ("die", "man", "war", "hat", "fast",
 *											"also") are deliberately not in the list, and the
 *											whole catalogue is matched against it, so a false
 *											positive shows up here rather than in somebody's
 *											next patch.
 *
 *											A feature's tests are left out: a test of german
 *											content is german on purpose, and so is a fixture - but for
 *											the heads of the variants a fixture brings, the .css files
 *											of a design/ directory in the tests/fixtures/features/<Dir>/
 *											of a feature: they are read like the heads of a feature's
 *											variants anywhere, so that the rule below is seen at work
 *											on them.
 *
 *											A template's name, description and style label in another
 *											language are left out as well: a line that starts with
 *											'@name-de', '@description-de' or '@style-de' (or the tag of
 *											another language) in a .css file whose path contains
 *											'/library/' - the library of a feature that brings templates -
 *											is what the panel shows, the text of a locale in the file that
 *											names the template. So is the head of a variant a feature
 *											brings, a .css file below a '/design/' directory: there the
 *											lines of '@knob-de' and '@option-de' are the labels of its
 *											knobs. Only such a line is left out; the rest of the comment
 *											is read as everywhere, and so is the same line in
 *											any other file.
 *
 *	Usage: php tests/language-smoke.php
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

echo "Comments in the catalogue are written in one language\n";

$words = 'nach|nicht|wird|werden|oder|aber|wenn|damit|durch|zwischen|schon|noch|immer|eine|einen|einem|einer|kein|keine|jede|jeden|jedes|dieser|diese|dieses|welche|und|sich|auch|sind|nur|beim|zum|zur|vom|der|das|des|dem|ist|sein|Datei|Zeile|Seite|Elemente|Abstände|Löschen|sowie|bereits|etwa|zwar';

// Somebody's name is not a language. The one entry here names its reason, so
// the list cannot quietly become a habit
$allowed = [ 'Björn Ottosson' ];

$sources	= [];
$root			= dirname( __DIR__ );
$walk			= static function( string $dir ) use ( &$walk, &$sources ): void {
	foreach( (array) glob( $dir. '/*' ) as $path ) {
		$path = (string) $path;
		if( str_contains( $path, '/text' ) === true || str_contains( $path, '/tests' ) === true || str_contains( $path, 'de_DE' ) === true )
			continue;
		if( is_dir( $path ) === true )
			$walk( $path );
		elseif( preg_match( '/\.(php|js|css)$/', $path ) === 1 )
			$sources[] = $path;
	}
};
$walk( $root. '/features' );

// The heads of the variants a fixture brings are read all the same (see above)
foreach( (array) glob( $root. '/features/*/tests/fixtures/features/*/design/*.css' ) as $fixture )
	$sources[] = (string) $fixture;

$offenders = [];

foreach( $sources as $file ) {

	$below = substr( $file, strlen( $root ) + 1 );

	// Inside a block comment or not. The catalogue's block comments are
	// written as '/*	text' followed by lines that start with the text itself,
	// not with a '*' - so a line's own first character says nothing about
	// whether it is a comment, and the half of every block that carried no
	// '*' used to go unread here
	$inBlock = false;

	foreach( explode( "\n", (string) file_get_contents( $file ) ) as $no => $line ) {

		$opening	= ltrim( $line, " \t" );
		$trailing	= strpos( $line, '//' );

		// The comment half of the line, and only that: a trailing '// ...' is
		// one (the '//' of a url is not), and everything left of it is code
		if( $inBlock === true ) {
			$comment = $line;
			if( str_contains( $line, '*/' ) === true )
				$inBlock = false;
		}
		elseif( $opening !== '' && ( $opening[0] === '*' || str_starts_with( $opening, '//' ) || str_starts_with( $opening, '/*' ) ) ) {
			$comment = $line;
			if( str_starts_with( $opening, '/*' ) === true && str_contains( $line, '*/' ) === false )
				$inBlock = true;
		}
		elseif( $trailing !== false && $trailing > 0 && $line[$trailing - 1] !== ':' )
			$comment = substr( $line, $trailing );
		else
			continue;

		// A template's name or description in another language is the text of a
		// locale, not commentary on the file that carries it - in a library, the
		// one place the panel reads it from, and in the head of a variant a feature
		// brings, where the labels of its knobs are such lines too; the same line
		// anywhere else is read like any other
		$tags = str_contains( $below, '/library/' ) === true ? 'name|description|style' : ( str_contains( $below, '/design/' ) === true ? 'name|description|knob|option' : '' );

		if( $tags !== '' && str_ends_with( $file, '.css' ) === true && preg_match( '/^\s*\*?\s*@(?:'. $tags. ')-[a-z]{2}\s/', $line ) === 1 )
			continue;

		foreach( $allowed as $name )
			$comment = str_replace( $name, '', $comment );

		if( preg_match( '/[\x{00e4}\x{00f6}\x{00fc}\x{00df}\x{00c4}\x{00d6}\x{00dc}]|\b('. $words. ')\b/u', $comment ) === 1 )
			$offenders[] = $below. ':'. ( $no + 1 );
	}
}

check( 'no comment in the catalogue is written in german'. ( $offenders === [] ? '' : ' - '. implode( ' | ', $offenders ) ), $offenders === [] );
check( '...and the rule has something to find: the sources were actually read', count( $sources ) > 60 );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
