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
 *											content is german on purpose, and so is a fixture.
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

$offenders = [];

foreach( $sources as $file ) {

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

		foreach( $allowed as $name )
			$comment = str_replace( $name, '', $comment );

		if( preg_match( '/[\x{00e4}\x{00f6}\x{00fc}\x{00df}\x{00c4}\x{00d6}\x{00dc}]|\b('. $words. ')\b/u', $comment ) === 1 )
			$offenders[] = substr( $file, strlen( $root ) + 1 ). ':'. ( $no + 1 );
	}
}

check( 'no comment in the catalogue is written in german'. ( $offenders === [] ? '' : ' - '. implode( ' | ', $offenders ) ), $offenders === [] );
check( '...and the rule has something to find: the sources were actually read', count( $sources ) > 60 );

echo "\n". $checks. " checks, ". $failures. " failed\n";
exit( $failures === 0 ? 0 : 1 );
