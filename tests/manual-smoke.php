<?php
declare(strict_types=1);

/**
 *	Nino features
 *	manual-smoke.php	The shape of the overview every feature gives of itself,
 *										held for the whole catalogue in one place: the description
 *										and the manual of each features/<Name>/feature.php. The
 *										manual is the feature's overview and no documentation, a
 *										tool's account of what it enables, and what is held of it
 *										here is its shape and never its words: the six sections of
 *										Nino's feature manual and no other, an entry that is a
 *										handle (what a developer types, never translated) with a
 *										line in each of the two languages, each line one sentence
 *										of at most 200 characters, the two lines of an entry
 *										different, a description of two to four sentences that
 *										differs between the languages, and the German written
 *										with a capital Du. The code and the tests are the detail.
 *
 *	Usage: php tests/manual-smoke.php
 */

$failures	= 0;
$checks		= 0;

function check( string $label, bool $condition ): void {
	global $failures, $checks;
	$checks++;
	echo ( $condition === true ? '  ok  ' : 'FAIL  ' ), '- ', $label, "\n";
	if( $condition === false )
		$failures++;
}

$here			= dirname( __DIR__ );
$sections	= [ 'shortcodes', 'markup', 'routes', 'panel', 'callbacks', 'install' ];	// \Nino\Features::MANUAL_SECTIONS
$locales	= [ 'en_US', 'de_DE' ];
$features	= [];

foreach( glob( $here. '/features/*/feature.php' ) ?: [] as $file )
	$features[ basename( dirname( $file ) ) ] = (array) ( static fn( string $path ): mixed => require $path )( $file );

// One sentence: no full stop, exclamation or question mark followed by a space and anything else, and no line feed
$oneSentence	= static fn( mixed $text ): bool => is_string( $text ) === true && $text !== '' && mb_strlen( $text ) <= 200 && preg_match( '/[.!?]\s+\S|\n/u', $text ) !== 1;
$sentences		= static fn( string $text ): int => count( array_filter( (array) preg_split( '/(?<=[.!?])\s+/u', trim( $text ) ) ) );
// Every offender of a rule, named by feature, section, handle and language
$offenders		= static function( callable $rule ) use ( $features, $locales ): array {
	$found = [];

	foreach( $features as $name => $manifest )
		foreach( (array) ( $manifest['manual'] ?? [] ) as $section => $entries )
			foreach( (array) $entries as $handle => $line )
				foreach( $locales as $locale )
					if( $rule( $handle, $line, $locale ) === false )
						$found[] = $name. ' '. $section. ' '. $handle. ' '. $locale;

	return $found;
};
$said = static fn( array $found ): string => $found === [] ? '' : ' - '. implode( ' | ', array_slice( $found, 0, 8 ) );

check( 'the features were actually read', count( $features ) > 20 );

echo "\nThe sections\n";

$badSections = array_keys( array_filter( $features, static fn( array $manifest ): bool => array_keys( (array) ( $manifest['manual'] ?? [] ) ) !== $sections ) );
check( 'every manual has the six sections of the kernel in their order, the empty ones too, and none besides'. $said( $badSections ), $badSections === [] );

echo "\nThe entries\n";

$noHandle = $offenders( static fn( mixed $handle, mixed $line, string $locale ): bool => is_string( $handle ) === true && trim( $handle ) !== '' && mb_strlen( $handle ) <= 120 );
check( 'every entry has a handle, a string of 1 to 120 characters, and no plain list item'. $said( $noHandle ), $noHandle === [] );

$noLine = $offenders( static fn( mixed $handle, mixed $line, string $locale ): bool => is_array( $line ) === true && array_keys( $line ) === [ 'en_US', 'de_DE' ] );
check( 'every entry is a line in en_US and de_DE under one handle, and in no other form'. $said( $noLine ), $noLine === [] );

$long = $offenders( static fn( mixed $handle, mixed $line, string $locale ): bool => $oneSentence( is_array( $line ) === true ? ( $line[$locale] ?? null ) : null ) );
check( 'every line is one sentence of at most 200 characters in both languages'. $said( $long ), $long === [] );

$same = $offenders( static fn( mixed $handle, mixed $line, string $locale ): bool => is_array( $line ) === true && ( $line['en_US'] ?? '' ) !== ( $line['de_DE'] ?? '' ) );
check( 'the two lines of an entry differ'. $said( $same ), $same === [] );

$du = $offenders( static fn( mixed $handle, mixed $line, string $locale ): bool => $locale !== 'de_DE' || is_array( $line ) === false || preg_match( '/\b(du|dein\w*|dir|dich)\b/u', (string) ( $line['de_DE'] ?? '' ) ) !== 1 );
check( 'the German lines address the reader with a capital Du'. $said( $du ), $du === [] );

echo "\nThe description\n";

$badDescription = [];

foreach( $features as $name => $manifest ) {
	$description = (array) ( $manifest['description'] ?? [] );

	foreach( $locales as $locale )
		if( is_string( $description[$locale] ?? null ) === false || $sentences( $description[$locale] ) < 2 || $sentences( $description[$locale] ) > 4 )
			$badDescription[] = $name. ' '. $locale;

	if( ( $description['en_US'] ?? '' ) === ( $description['de_DE'] ?? '' ) )
		$badDescription[] = $name. ' same';
}

$duInDescription = array_keys( array_filter( $features, static fn( array $manifest ): bool => preg_match( '/\b(du|dein\w*|dir|dich)\b/u', (string) ( $manifest['description']['de_DE'] ?? '' ) ) === 1 ) );
check( 'the German descriptions address the reader with a capital Du'. $said( $duInDescription ), $duInDescription === [] );
check( 'every description is two to four sentences in each language, and differs between them'. $said( $badDescription ), $badDescription === [] );

echo "\n". $checks. ' checks, '. $failures. " failed\n";

exit( $failures === 0 ? 0 : 1 );
